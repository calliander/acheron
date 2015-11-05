<?php

// {{{ CardDog

/**
 * Pats Peak CardDog Processor
 *
 * Connects to the CardDog API and handles the specified transaction type,
 * returning details about the transaction.
 *
 * Prism uses this like:
 *      - $carddog = new CardDog();
 *      - $carddog->SetupTransaction([values]);
 *      - $carddog->[AuthorizeCharge/VoidTransaction/AddFunds/CheckBalance]();
 *      - $carddog_result = $carddog->GetResponse();
 *
 * Sometimes Prism needs a reason for a failure, in which case it calls:
 *      - $controller->displayModalError($carddog->Diagnostic());
 *
 * Prism also handles cURL errors through an override of the various functions
 * in PHP.
 *
 * @author MVC <michaelc@drinkcaffeine.com>
 */
class CardDog
{

    // {{{ properties

    /**
     * API settings
     * @var object
     */
    private $api_settings;

    /**
     * Order number
     * @var int
     */
    private $order_number = 0;

    /**
     * Card number
     * @var string
     */
    private $card_number;

    /**
     * Charge amount
     * @var float
     */
    private $charge_amount = 0;

    /**
     * Charge description
     * @var string
     */
    private $charge_description;

    /**
     * Transaction id
     * @var int
     */
    private $transaction_id = 0;

    /**
     * Response data
     * @var array
     */
    private $response_data;

    // }}} properties
    // {{{ SetupTransaction()

    /**
     * Setup Transaction
     *
     * Initializes the CardDog object.
     * Does not throw errors due to how Prism handles the object.
     *
     * @param string $process_mode The mode to use when processing
     * @param int $order_number The point of sale order number
     * @param string $card_number The CardDog card number
     * @param float $charge_amount The amount being processed
     * @param string $charge_description A short description of the transaction
     * @param int $transaction_id A CardDog transaction id
     * @return boolean Whether the object was successfully set up
     */
    public function SetupTransaction($process_mode = 'dev', $order_number = time(), $card_number = null, $charge_amount = 0, $charge_description = null, $transaction_id = 0)
    {

        // Check supplied values.
        # Process mode
        if($process_mode != 'dev' || $process_mode != 'prod') return false;
        # Order number
        if(!is_integer($order_number)) return false;
        # Card number
        $card_number = preg_replace('/\D/', '', $card_number);
        if(strlen($card_number != 15)) return false;
        # Charge amount
        if(!is_float($charge_amount)) return false;
        $charge_amount = number_format($charge_amount, 2, '.', '');
        # Charge description
        if(!empty($charge_description)) $charge_description = filter_var($charge_description, FILTER_SANITIZE_STRING);
        # Transaction id
        if(!is_integer($transaction_id)) return false;

        // Load settings.
        # Defaults to dev if not prod, in case any funky text gets through somehow.
        $this->api_settings = (object)[
            'url'       => ($process_mode == 'prod') ? 'https://prod.carddog.com/' : 'https://dev.carddog.com';
            'port'      => 61245,
            'username'  => getenv('CDOG_USER'),
            'password'  => getenv('CDOG_PASS'),
            'store'     => getenv('CDOG_STORE'),
            'merchant'  => getenv('CDOG_MERCH'),
            'terminal'  => getenv('CDOG_TERM')
        ];

        // Set values.
        $this->order_number = $order_number;
        $this->card_number = $card_number;
        $this->charge_amount = $charge_amount;
        $this->charge_description = $charge_description;
        $this->transaction_id = $transaction_id;

        return true;
    }

    // }}}
    // {{{ GetResponse()

    /**
     * Get Response Data
     *
     * Returns the value of the response data array.
     *
     * @return array The array with API response data
     */
    public function GetResponse()
    {
        return $this->response_data;
    }

    // }}}
    // {{{ ArrayToXML()

    /**
     * Array To XML
     *
     * Recursively converts an array of information to an XML object with the
     * keys and values corresponding to the array. The XML object is created
     * prior to this call, and is modified by reference so that it can keep
     * looping through.
     *
     * @param array $data The array containing the data to convert
     * @param object &$xml The reference XML object
     */
    private function ArrayToXML($data, &$xml)
    {
        foreach($data as $key => $value)
        {
            // Check for recursive handling.
            if(is_array($value))
            {
                // For arrays with numeric indexes.
                if(is_numeric($key)) $key = 'item' . $key;
                $subnode = $xml->addChild($key);
                $this->ArrayToXML($value, $subnode);
            }
            else
            {
                $xml->addChild($key, htmlspecialchars($value));
            }
        }

    }

    // }}}
    // {{{ BuildPostXML()

    /**
     * Build XML Post Data
     *
     * Creates an XML string containing the values that will be passed to the
     * API interface by the cURL request.
     *
     * @param int $type The transaction type code
     * @return string The XML string of values
     */
    private function BuildPostXML($type)
    {
        // Array structure with settings and values
        $data = [
            'Credentials'           => [
                'LogonID'               => $this->api_settings->username,
                'Password'              => $this->api_settings->password
            ],
            'POSTransactionID'      => $this->order_number,
            'TerminalID'            => [
                'MerchantNbr'           => $this->api_settings->merchant,
                'StoreNbr'              => $this->api_settings->store,
                'TerminalNbr'           => $this->api_settings->terminal
            ],
            'TransactionCode'       => $type,
            'TransactionAmount'     => $this->charge_amount,
            'PrimaryAccountInfo'    => [
                'ManualAcctInfo'        => [ 'PAN' => $this->card_number ]
            ],
            'ReversalTransID'       => $this->transaction_id
        ];

        // Create a SimpleXML object wrapper then add the array to it
        $xml = new SimpleXMLElement('<AuthorizationRQ xmlns="http://carddog.com/namespace/xmlapi"></AuthorizationRQ>');
        $this->ArrayToXML($data, $xml);

        // Drop to string
        return $xml->asXML();
    }

    // }}}
    // {{{ RunTransaction()

    /**
     * Run Transaction
     *
     * Builds the post data then submits a cURL request to the CardDog API.
     * Parses the response from the API into the class variable and says
     * whether the call was successful or not.
     *
     * @param int $type The transaction type being run
     * @return boolean If the call was successful or not
     */
    private function RunTransaction($type)
    {
        // Build the post data.
        $post_data = $this->BuildPostXML();

        $ch = curl_init();

        // cURL options
        curl_setopt($ch, CURLOPT_URL, $this->api_settings->url);
        curl_setopt($ch, CURLOPT_PORT, $this->api_settings->port);
        # Server-specific timeout
        curl_setopt($ch, CURLOPT_TIMEOUT, APP_GLOBAL_CURLTIME);
        # Specify the request is sending XML
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/xml'));
        # Specify that the expected response is XML
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: text/xml'));
        # Set type as POST with data
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        # Verify the API endpoint has SSL
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
        # Verify the hostname of the certificate matches the URL specified
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        # Expect return information
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($ch);

        // Errors for cURL handled by Prism
        if(curl_errno($ch)) return false;

        curl_close($ch);

        // Turn the response into an array
        $xmlp = xml_parser_create();
        xml_parse_into_struct($xmlp, $result, $xml_arr);
        xml_parser_free($xmlp);

        // Assign the response array elements
        foreach($xml_arr as $arr) $this->response_data[strtolower($arr['tag'])] = $arr['value'];

        // Tell the caller whether it was successful or not
        return ($this->response_data['responsecode'] == '0' || $this->response_data['responsecode'] == '00');
    }

    // }}}
    // {{{ AuthorizeCharge()

    /**
     * Authorize Charge
     *
     * Public call to authorize a charge.
     *
     * @return boolean If the transaction succeeded or not
     */
    public function AuthorizeCharge()
    {
        return $this->RunTransaction(10);
    }

    // }}}
    // {{{ VoidTransaction()

    /**
     * Void Transaction
     *
     * Public call to void an existing transaction.
     *
     * @return boolean If the transaction succeeded or not
     */
    public function VoidTransaction()
    {
        return $this->RunTransaction(41);
    }

    // }}}
    // {{{ AddFunds()

    /**
     * Add Funds
     *
     * Public call to add funds to a card.
     *
     * @return boolean If the transaction succeeded or not
     */
    public function AddFunds()
    {
        return $this->RunTransaction(30);
    }

    // }}}
    // {{{ CheckBalance()

    /**
     * Check Balance
     *
     * Public call to check a card's balance.
     *
     * @return boolean If the transaction succeeded or not
     */
    public function CheckBalance()
    {
        return $this->RunTransaction(50);
    }

    // }}}
    // {{{ Diagnostic()

    /**
     * Diagnostic
     *
     * Returns detailed information based on the response code, since Prism
     * occasionally requires the detail. These get changed/added/removed at
     * varying intervals with no API to provide the reason, only a notice on
     * their web site about it.
     *
     * @return string The problem description
     */
    public function Diagnostic()
    {
        // List of codes provided by CardDog.
        $reasons = [
            '-5'    => 'Invalid Logon Credentials',
            '-3'    => 'Mising Required Element(s)',
            '-2'    => 'Improperly Formatted XML Request',
            '-1'    => 'Text Description',
            '1'     => 'Program is Invalid',
            '2'     => 'Merchant is Invalid',
            '3'     => 'Location is Invalid',
            '4'     => 'Terminal is Invalid',
            '5'     => 'Customer is Invalid',
            '6'     => 'Card is Invalid',
            '7'     => 'Transaction Code is Invalid',
            '8'     => 'Currency Code is Invalid',
            '9'     => 'Country Code is Invalid',
            '10'    => 'Amount BELOW Transaction MINIMUM',
            '11'    => 'Amount ABOVE Transaction MAXIMUM',
            '12'    => 'Insufficient Funds',
            '33'    => 'A Similar Transaction has already been processed',
            '62'    => 'Call the card issuer to resolve problems with card',
            '63'    => 'Generic Processing Error',
            '64'    => 'Used for Testing Declines',
            '65'    => 'No matching sale to authorize the credit',
            '66'    => 'Card is not valid for this terminal',
            '67'    => 'Over Pre-Approved Limit',
            '68'    => 'Card is Suspended',
            '69'    => 'Server timeout, please try again',
            '76'    => 'Invalid Location',
            '79'    => 'Invalid Card Number',
            '80'    => 'Invalid Password',
            '82'    => 'Contact TRI Support',
            '83'    => 'Card Expired',
            '84'    => 'Card programs are not compatible',
            '85'    => 'Card has already been replaced',
            '86'    => 'Invalid old card status',
            '87'    => 'Invalid new card status',
            '88'    => 'AccountID is not supported at this time',
            'X'     => 'There was an error communicating with our gift card processor gateway. Please try again.'
        ];

        // Default to communication error if the code isn't in the array.
        $response_code = (array_key_exists($this->response_data['response_code'], $reasons)) ? $this->response_data['response_code'] : 'X';

        return $reasons[$response_code];
    }

    // }}}

}
