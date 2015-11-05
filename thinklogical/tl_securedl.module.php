<?php

// {{{ tl_securedl_menu()

/**
 * Secure Download Menu Items
 *
 * Implements hook_menu()
 * Alters the file paths to add the menu callbacks for both the private PDF
 * files and the product update center files.
 *
 * @return array $items An array of Drupal menu items.
 * @author MVC <michaelc@drinkcaffeine.com>
 */
function tl_securedl_menu()
{

	// Startup
	$items = [];

	// Private files
	$items['private'] = [
		'type'              => MENU_CALLBACK,
		'access callback'   => true,
		'page callback'     => 'tl_private_files',
		'page arguments'    => [4]
	];

	// Warranty firmware files
	$items['warranty'] = [
	    'type'              => MENU_CALLBACK,
	    'access callback'   => true,
	    'page callback'     => 'tl_warranty_files',
	    'page_arguments'    => [1]
	];

    // Reporting
    $items['admin/reports/downloads'] = [
        'title'             => 'Thinklogical File Downloads',
        'description'       => 'View a list of downloads for the firmware.',
        'page callback'     => 'tl_report_page',
        'access arguments'  => ['administer site configuration'],
        'type'              => MENU_NORMAL_ITEM,
        'options'           => ['attributes' => ['target' => '_blank']]
    ];
    $items['admin/reports/downloads/csv'] = [
        'type'              => MENU_CALLBACK,
        'access callback'   => true,
        'page callback'     => 'tl_report_csv'
    ];

	// Finish
	return $items;

}

// }}}
// {{{ tl_private_files()

/**
 * Private File Download
 *
 * Checks the session information to see whether access has been granted via the
 * download form (or if an administrator) and serves the file or sends the
 * request to the download form.
 *
 * @param string $uri The URI to the file provided by the callback.
 * @return mixed The file, if allowed, or a redirect to the download page.
 * @author MVC <michaelc@drinkcaffeine.com>
 */
function tl_private_files($uri)
{

	// Create session for anonymous users
	if(!$_SESSION) drupal_session_start();

	// Place filename in the session
	$_SESSION['tl_private_file'] = $uri;

	// Check for admin
	if(user_access('administer site configuration')) $_SESSION['tl_private_file_grant'] = 'think_access';

	// Check if access has been granted
	if($_SESSION['tl_private_file_grant'] == 'think_access')
	{
		# Are we accessing without a file somehow?
		if(!isset($_SESSION['tl_private_file'])) drupal_goto('<front>');
		# Build full file name
		$file = tl_document_root() . '/sites/default/files/private/' . $uri;
		# Set a generic filetype
		$file_mime = 'application/octet-stream';
		if(function_exists('finfo_open'))
		{
			# Attempt to get MIME via finf
			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			$file_mime = finfo_file($finfo, $file);
		}
		# Add headers for the download
		drupal_add_http_header('Content-Type', $file_mime . '; utf-8');
		drupal_add_http_header('Content-disposition', 'attachment; filename="' . $uri . '"');
		# Serve file
        readfile($file);
	}
	else
	{
		# No access, send to download form
		drupal_goto('download_form');
	}

}

// }}}
// {{{ tl_warranty_files()

/**
 * Warranty Firmware Downloads
 *
 * Checks the user information to see whether an expiration date for a purchased
 * warranty is present (or if an administrator) and serves the file or sends the
 * request to the home page.
 *
 * @param string $uri The URI to the file provided by the callback.
 * @return mixed The file, if allowed, or a redirect to the front page.
 * @author MVC <michaelc@drinkcaffeine.com>
 */
function tl_warranty_files($uri)
{

	// Create session for anonymous users
	if(!$_SESSION) drupal_session_start();

	// Place filename in the session
	$_SESSION['tl_warranty_file'] = $uri;

    // Check for warranty
    $warranty = false;
    global $user;
    $user_full = user_load($user->uid);
    if($user->uid > 0)
    {
        # Attempt convert to time (for comparison purposes, 'false' is always less than current time)
        $warranty_date = strtotime($user_full->field_warranty_expiration['und'][0]['value']);
        # If not expired or one of the admins, allow. Otherwise, $warranty stays false
        # TODO: Need to convince them to use roles instead of creating admins!!!
        if($warranty_date > time() || $user->uid == 1 || $user->uid == 797 || $user->uid == 1272) $warranty = true;
    }

	// Check if access has been granted
	if($warranty)
	{
		# Are we accessing without a file somehow?
		if(!isset($_SESSION['tl_warranty_file'])) drupal_goto('<front>');
		# Build full file name
		$file = tl_document_root() . '/sites/default/files/software/' . $uri;
		# Sets a generic filetype
		$file_mime = 'application/octet-stream';
		if(function_exists('finfo_open'))
		{
			# Attempt to get MIME via finfo
			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			$file_mime = finfo_file($finfo, $file);
		}
        # DB tracking
        $placeholders = [
            ':uid'          => $user->uid,
            ':ip'           => get_client_ip(),
            ':useragent'    => $_SERVER['HTTP_USER_AGENT'],
            ':filename'     => $uri
        ];
        # ROUTINE: INSERT INTO `firmware_tracking` (`UID`, `IP`, `UserAgent`, `FileName`) VALUES (:uid, :ip, :useragent, :filename)
        $result = db_query("CALL FirmwareTracking(:uid, :ip, :useragent, :filename)", $placeholders);
		# Add headers for the download
		drupal_add_http_header('Content-Type', $file_mime . '; utf-8');
		drupal_add_http_header('Content-disposition', 'attachment; filename="' . $uri . '"');
		# Serve file
        readfile($file);
	}
	else
	{
		# No access
		drupal_goto('<front>');
	}

}

// }}}
// {{{ get_client_ip()

/**
 * Get Client IP
 *
 * Utilizes several different methods in an attempt to get the most accurate IP
 * address of a client.
 *
 * @return string The derived IP address or 'unknown'.
 * @author MVC <michaelc@drinkcaffeine.com>
 */
function get_client_ip()
{

    // Tries in order of most reliable to least
    if(getenv('HTTP_CLIENT_IP'))        return getenv('HTTP_CLIENT_IP');
    if(getenv('HTTP_X_FORWARDED_FOR'))  return getenv('HTTP_X_FORWARDED_FOR');
    if(getenv('HTTP_X_FORWARDED'))      return getenv('HTTP_X_FORWARDED');
    if(getenv('HTTP_FORWARDED_FOR'))    return getenv('HTTP_FORWARDED_FOR');
    if(getenv('HTTP_FORWARDED'))        return getenv('HTTP_FORWARDED');
    if(getenv('REMOTE_ADDR'))           return getenv('REMOTE_ADDR');

    // HOW U DO DIS
    return 'unknown';

}

// }}}
// {{{ get_geolocation_data()

/**
 * Get Geolocation Data
 *
 * Uses an API to retrieve location data and save it in the database for
 * (hopefully) fewer API calls.
 *
 * @param string $ip The IP address to get data for.
 * @return string The location data combined into a string.
 * @author MVC <michaelc@drinkcaffeine.com>
 */
function get_geolocation_data($ip)
{

    // Get the data
    $api = json_decode(file_get_contents('http://ipinfo.io/' . $ip . '/json'));
    $location = $api->city . ', ' . $api->region . ', ' . $api->country;

    // Add entry to the database
    $ip_ph = [
        ':ip'       => $ip,
        ':location' => $location
    ];
    # ROUTINE: INSERT INTO firmware_ips (IP, Location) VALUES (:ip, :location)
    db_query("CALL FirmwareIPAdd(:ip, :location)", $ip_ph);

    return $location;

}

// }}}
// {{{ tl_report_array()

/**
 * Report Array
 *
 * Generates the report array.
 *
 * @param string $range_start A start date.
 * @param string $range_end An end date.
 * @return array An array based on UID with downloads entries.
 * @author MVC <michaelc@drinkcaffeine.com>
 */
function tl_report_array($range_start = '-7 days', $range_end = '+1 day')
{

    // Range start is the early day
    try
    {
        $dt_start = new DateTime($range_start);
    }
    catch(Exception $e)
    {
        $dt_start = new DateTime('-7 days');
        # Log the exception
        tl_log(2, $e);
    }

    // Range end is the late day
    try
    {
        $dt_end = new DateTime($range_end);
    }
    catch(Exception $e)
    {
        $dt_end = new DateTime('+1 day');
        # Log the exception
        tl_log(2, $e);
    }

    // Placeholders
    $data_ph = [
        ':dt_start' => $dt_start->format('Y-m-d'),
        ':dt_end' => $dt_end->format('Y-m-d')
    ];

    // Using Drupal hooks here _drastically_ slows performance down
    // *** DO NOT ROLL BACK CHANGES - SEE MVC
    /*
       ROUTINE: SELECT ft.UID AS uid, ft.Date AS date, ft.IP AS ip, ft.FileName AS filename, u.name AS account_name, u.mail AS email, co.field_company_name_value AS company_name, fn.field_first_name_value AS first_name, ln.field_last_name_value AS last_name, wty.field_warranty_expiration_value AS warranty_expiration, ip.Location AS location
FROM firmware_tracking ft
    LEFT JOIN users u ON ft.UID = u.uid
    LEFT JOIN field_data_field_company_name co ON ft.UID = co.entity_id
    LEFT JOIN field_data_field_first_name fn ON ft.UID = fn.entity_id
    LEFT JOIN field_data_field_last_name ln ON ft.UID = ln.entity_id
    LEFT JOIN field_data_field_warranty_expiration wty ON ft.UID = wty.entity_id
    LEFT JOIN firmware_ips ip ON ft.IP = ip.IP
WHERE ft.Date BETWEEN :dt_start AND :dt_end
ORDER BY ft.ID";
    */
    $data = db_query("CALL GetDownloadData(:dt_start, :dt_end)", $data_ph)->fetchAll();

    // Build the array
    foreach($data as $db_obj)
    {
        # Create basic info if it isn't there
        if(!array_key_exists($db_obj->uid, $report))
        {
            $report[$db_obj->uid] = [
                'account'   => $db_obj->account_name,
                'name'      => $db_obj->first_name . ' ' . $db_obj->last_name,
                'company'   => $db_obj->company_name,
                'warranty'  => ($db_obj->warranty_expiration) ?: '* Unknown *',
                'email'     => $db_obj->email
            ];
        }
        # Add entries
        $report[$db_obj->uid]['downloads'][] = [
            'date'  => $db_obj->date,
            'ip'    => ($db_obj->ip) ?: '* No IP Address *',
            'geo'   => ($db_obj->location) ?: get_geolocation_data($db_obj->ip),
            'file'  => $db_obj->filename
        ];
    }

    return $report;

}

// }}}
// {{{ tl_report_page()

/**
 * File Download Report Page
 *
 * Generates the data for the page based on specified information.
 *
 * @return mixed The outputted data using the template file.
 * @author MVC <michaelc@drinkcaffeine.com>
 */
function tl_report_page()
{

    // Path variable
    $tl_path = drupal_get_path('module', 'tl_securedl');

    // Set default date range
    $start_date = new DateTime('-7 days');
    $end_date = new DateTime('+1 day');

    // Update date range if necessary
    if($_POST['submit'] == 'update')
    {
        # Start date
        try
        {
            $start_date = new DateTime($_POST['range_start']);
        }
        catch(Exception $e)
        {
        	# Log the exception
	        tl_log(2, $e);
        }
        # End date
        try
        {
            $end_date = new DateTime($_POST['range_end']);
        }
        catch(Exception $e)
        {
        	# Log the exception
	        tl_log(2, $e);
        }
    }

    // Get report
    $report = tl_report_array($start_date->format('Y-m-d'), $end_date->format('Y-m-d'));

    // Load report template
    require($tl_path . '/report.inc.php');

}

// }}}
// {{{ tl_report_csv()

/**
 * CSV Download
 *
 * Generates the CSV file for downloading the report, based on selected range.
 *
 * @return mixed The CSV file or redirect to main page if access is not granted.
 * @author MVC <michaelc@drinkcaffeine.com>
 */
function tl_report_csv()
{

    if($_POST['submit'] == 'download')
    {
        // Set default date range
        $start_date = new DateTime('-7 days');
        $end_date = new DateTime('+1 day');
        // Update start date if needed
        try
        {
            $start_date = new DateTime($_POST['range_start']);
        }
        catch(Exception $e)
        {
        	# Log the exception
	        tl_log(2, $e);
        }
        // Update end date if needed
        try
        {
            $end_date = new DateTime($_POST['range_end']);
        }
        catch(Exception $e)
        {
        	# Log the exception
	        tl_log(2, $e);
        }

        // Get report
        $report = tl_report_array($start_date->format('Y-m-d'), $end_date->format('Y-m-d'));

        // Generation date and time
        $file_name = 'TL_File_Downloads_' . $start_date->format('Y-m-d') . '_to_' . $end_date->format('Y-m-d') . '-' . date('Ymd-HisA');

        // Headers for the CSV file
        drupal_add_http_header('Content-Type', 'text/csv');
        drupal_add_http_header('Content-Disposition', 'attachment; filename=' . $file_name . '.csv');

        // File headers
        $fp = fopen('php://output', 'w');
        $first_line = [
            'Account',
            'Email',
            'Date',
            'IP',
            'Location',
            'File',
            'Name',
            'Company',
            'WarrantyExpiration'
        ];
        fputcsv($fp, $first_line);

        // Loop through the data
        foreach($report as $data)
        {
            foreach($data['downloads'] as $record)
            {
                # Create row to insert. Some data is redundant, obviously
                $this_line = [
                    $data['account'],
                    $data['email'],
                    $record['date'],
                    $record['ip'],
                    $record['geo'],
                    $record['file'],
                    $data['name'],
                    $data['company'],
                    $data['warranty']
                ];
                fputcsv($fp, $this_line);
            }
        }

        // Serve file and finish
        fclose($fp);
        drupal_exit();
    }
    else
    {
        // Front page
        drupal_goto('<front>');
    }

}

/// }}}
