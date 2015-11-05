<?php

// {{{ aes_find_a_doctor_form()

/**
 * Find a Doctor Form
 *
 * Implements hook_form()
 * Handles the doctor search functionality on the site.
 *
 * @param array $form The Drupal form array
 * @param array $form_state The reference to the state of the form
 * @return array The processed/built form
 * @author MVC <michaelc@drinkcaffeine.com>
 */
function aes_find_a_doctor_form($form, &$form_state)
{

    // The search field itself
    $form['search'] = [
        '#title'    => t('Search Form'),
        '#type'     => 'fieldset',
        '#prefix'   => '<div id="find-a-doctor-form">',
        '#suffix'   => '</div>',
    ];

    // Handle any submitted info
    if($form_state['executed'])
    {
        # DB query
        $results = aes_doctor_db_query_find($form_state['values']['zip_code'], $form_state['values']['radius'], $form_state['values']['state'], $form_state['values']['activity']);

        if(count($results) > 0)
        {
            # Results
            foreach($results as $doctor)
            {
                $results_html.= aes_theme_build_table(
                    'dr_list',
                    [
                        'key'       => $doctor->ind_cst_key,
                        'name'      => $doctor->fullname,
                        'org'       => @$doctor->org_name,
                        'addr1'     => @$doctor->adr_line1,
                        'addr2'     => @$doctor->adr_line2,
                        'addr3'     => @$doctor->adr_line3,
                        'city'      => $doctor->adr_city,
                        'state'     => $doctor->adr_state,
                        'zip'       => @$doctor->adr_post_code,
                        'country'   => $doctor->adr_country,
                        'phone'     => @$doctor->cst_phn_number_complete_dn,
                        'activity'  => @$doctor->itc_code,
                        'radius'    => @$doctor->radius,
                    ]
                );
            }
            $form['search']['results'] = ['#markup' => $results_html];
        }
        else
        {
            # No results
            $form['search']['results'] = ['#markup' => '<div id="no-results">Sorry, no results matched your criteria.</div>'];
        }
    }

    // Build remainder of the form
    $form['search']['info'] = ['#markup' =>    ' '];
    $form['search']['zip_code'] = [
        '#title'        => t('Zip Code'),
        '#type'         => 'textfield',
        '#placeholder'  => t('Zip code...')
    ];
    $radius = [
        10  => '10 Miles',
        25  => '25 Miles',
        50  => '50 Miles',
        100 => '100 Miles',
        250 => '250 Miles',
        500 => '500 Miles'
    ];
    $form['search']['radius'] = [
        '#title'    => t('Radius'),
        '#type'     => 'select',
        '#options'  => $radius
    ];
    $form['search']['descriptor'] = ['#markup' => '<div id="find-or">OR</div>'];
    $form['search']['state'] = [
        '#title'    => t('State'),
        '#type'     => 'select',
        '#options'  => aes_common_states()
    ];
    # TODO: Suggest moving this to common
    $activity = [
        0   => 'ALL',
        1   => 'Administration',
        2   => 'Adult Neurology/Epileptology',
        3   => 'Basic Science Research',
        4   => 'Clinical Research',
        5   => 'EEG/Clinical Neurophysiology',
        6   => 'Industry/Marketing',
        7   => 'Industry/Research',
        8   => 'Neuroimaging',
        9   => 'Neurosurgery',
        10  => 'Non-Profit/Government',
        11  => 'Nursing/Advanced Practice',
        12  => 'Other',
        13  => 'Pediatric Neuro/Epileptology',
        14  => 'Pharmacology',
        15  => 'Pharmacy',
        16  => 'Physician Assistant',
        17  => 'Psychiatry/Neuropsychiatry',
        18  => 'Psychology/Neuropsychology',
        19  => 'Sleep Medicine',
        20  => 'Social Work'
    ];
    $form['search']['activity'] = [
        '#title'    => t('Predominant Professional Activity'),
        '#type'     => 'select',
        '#options'  => $activity
    ];
    $form['search']['submit'] = [
        '#type'     => 'submit',
        '#value'    => t('Locate'),
        '#prefix'   => '<div class="form-item form-type-submit form-item-submit">',
        '#suffix'   => '</div>'
    ];

    return $form;

}

// }}}
// {{{ aes_find_a_doctor_form_submit()

/**
 * Submit function.
 *
 * Implements hook_form_submit()
 * Only rebuilds the form, actually.
 *
 * @param array $form The built form from Drupal
 * @param array $form_state The reference to the current form state
 * @return none Tells Drupal to rebuild the form
 * @author MVC <michaelc@drinkcaffeine.com>
 */
function aes_find_a_doctor_form_submit($form, &$form_state)
{
    $form_state['rebuild'] = true;
}

// }}}
// {{{ aes_find_a_doctor_geo()

/**
 * Geo Data
 *
 * Gets geographic information about a zip code.
 *
 * @param string $zip The specified zip code
 * @return object An object containing latitude and longitude
 */
function aes_find_a_doctor_geo($zip)
{

    // Get the ZIP code in advance.
    $zip = substr($zip, 0, 5);
    # ROUTINE: SELECT * FROM `zip_codes` WHERE `zip_code`=:zip
    $query = db_query("CALL GetZipCode(:zip)", [':zip' => $zip])->fetchObject();

    // Fall back to Google if not in DB
    if(!$query)
    {
        unset($query);
        # Make an empty array with the zip by default.
        $q = [
            'zip_code'  => $zip,
            'latitude'  => '',
            'longitude' => ''
        ];
        $google = simplexml_load_file('http://maps.googleapis.com/maps/api/geocode/xml?address=' . $zip . '&sensor=false');
        if($google->status == 'OK')
        {
            # If query limit isn't hit, fill in lat/long.
            $q['latitude'] = $google->result->geometry->location->lat;
            $q['longitude'] = $google->result->geometry->location->lng;
        }
        # Cast as object.
        $query = (object)$q;
    }

    return $query;

}

// }}}
// {{{ aes_doctor_db_query_find()

/**
 * Doctor Query
 *
 * Processes the searched information through the doctor database.
 * TODO: Make a routine for the query so the bulk of this work can be shifted
 * to MySQL's lap.
 *
 * @param string $zip The given zip code
 * @param integer $radius The number of miles to search within
 * @param string $state The US state code
 * @param integer $activity The specified field of work
 * @return object A Drupal DAL result with matched rows
 * @author MVC <michaelc@drinkcaffeine.com>
 */
function aes_doctor_db_query_find($zip = NULL, $radius = 10, $state, $activity = 0) {

    // Prep 1 for radius - default to no zip
    if($zip == 'Zip code...') $zip = '';
    $qstr = "SELECT * FROM `doctor_list` WHERE `adr_state`=:state";
    $ph = ['state' => $state];
    if($zip)
    {
        # Get geo data
        $zipgeo = aes_find_a_doctor_geo($zip);
        if(count($zipgeo) > 0)
        {
            # Complicated maths for radius
            $qstr = "SELECT *, (((acos(sin((:lat * pi() / 180)) * sin((`lat` * pi() / 180))+cos((:lat * pi() / 180)) * cos((`lat` * pi() / 180)) * cos(((:lng - `lng`) * pi() / 180)))) * 180 / pi()) * 60 * 1.1515) AS `radius` FROM `doctor_list`";
            # Redo placeholder array
            unset($ph);
            $ph = [
                'lat'   => $zipgeo->latitude,
                'lng'   => $zipgeo->longitude
            ];
        }
    }

    // Activities
    // TODO: Suggest moving this to common
    $activities = [
        1   => 'Administration',
        2   => 'Adult Neurology/Epileptology',
        3   => 'Basic Science Research',
        4   => 'Clinical Research',
        5   => 'EEG/Clinical Neurophysiology',
        6   => 'Industry/Marketing',
        7   => 'Industry/Research',
        8   => 'Neuroimaging',
        9   => 'Neurosurgery',
        10  => 'Non-Profit/Government',
        11  => 'Nursing/Advanced Practice',
        12  => 'Other',
        13  => 'Pediatric Neuro/Epileptology',
        14  => 'Pharmacology',
        15  => 'Pharmacy',
        16  => 'Physician Assistant',
        17  => 'Psychiatry/Neuropsychiatry',
        18  => 'Psychology/Neuropsychology',
        19  => 'Sleep Medicine',
        20  => 'Social Work'
    ];
    if($activity > 0)
    {
        if($zip)
        {
            $qstr.= " WHERE `itc_code`=:itc_code";
        }
        else
        {
            $qstr.= " AND `itc_code`=:itc_code";
        }
        # Add code to placeholders.
        $ph['itc_code'] = $activities[$activity];
    }

    // Prep 2 for radius.
    if($zip)
    {
        $qstr.= " HAVING `radius` <= :radius ORDER BY `radius` ASC";
        $ph['radius'] = $radius;
    }
    else
    {
        $qstr.= " ORDER BY `ind_cst_key` ASC";
    }

    // Run the query and return the results.
    $query = db_query($qstr, $ph);

    // Prepare to return the results.
    $result = [];
    if($query->rowCount() > 0) $result = $query->fetchAll();
    return $result;

}

// }}}
