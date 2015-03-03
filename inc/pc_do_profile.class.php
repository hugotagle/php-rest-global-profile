
<?php

class Profile {
  
  public function __construct() {

    $this->image_base_url = "/index.php?a=pm_pilotprofile_action&nohead=true&do=getimage&id=";
  }
  		
	public function get_profile($pro_id) {

    error_log("\nget profile\n{$pro_id}", 3, '/tmp/bulkprolog');
    $contact = $this->get_contact($pro_id);
    error_log("\nget contact\n".print_r($contact, true), 3, '/tmp/bulkprolog');
    $address = $this->get_address($pro_id);
    error_log("\nget address\n".print_r($address, true), 3, '/tmp/bulkprolog');
    
    $payload = array( 
      "pro_id" => $pro_id,
      "headshot" => $this->get_headshot($pro_id),
      "summary" => $this->get_summary($pro_id),
      "pic" => $contact[0]->PIC_HOURS,  //can derive these from sum() the columns in flight hours
      "contact" => array(
                      'first_name' => ucfirst($contact[0]->FIRST_NAME),
                      'last_name' => ucfirst($contact[0]->LAST_NAME),
                      'middle_initial' => $contact[0]->MIDDLE_INITIAL,
                      'email' => $contact[0]->EMAIL,
                      'alternate_email' => $contact[0]->ALTERNATE_EMAIL,
                      'primary_phone' => $contact[0]->PRIMARY_PHONE,
                      'secondary_phone' => $contact[0]->SECONDARY_PHONE,
                      'country' => $contact[0]->COUNTRY,
                      'address1' => $address[0]->ADDR_STREET_1,
                      'address2' => $address[0]->ADDR_STREET_2,
                      'city' => $address[0]->ADDR_CITY,
                      'state' => $address[0]->ADDR_STATE,
                      'zip' => $address[0]->ADDR_ZIP,
                      'available_now_flag' => $contact[0]->AVAILABLE_NOW_FLAG,
                      'availability_date' => $contact[0]->AVAILABILITY_DATE,
                      'availability' => $contact[0]->AVAILABILITY,
                      'source_training' => $contact[0]->SOURCE_TRAINING,
                      'visible' => $contact[0]->VISIBLE,
                      'gender' => $contact[0]->GENDER,
                      'ethnicity' => $contact[0]->ETHNICITY,
                   ),
     "flight_hours" => $this->get_flight_hours($pro_id),
     "certifications" => $this->get_certifications($pro_id),
     "employment_history" => $this->get_employment($pro_id),
     "education" => $this->get_education($pro_id),
     
    );
    return $payload;
	}

  public function get_headshot($pro_id) {
		global $config;
    //if we base64 encode these here then we can stash them into local storage
    $default_images = array('img/PC01_WM.jpg',
                            'img/PC02_WM.jpg',
                            'img/PC03_WM.jpg',
                            'img/PC04_WM.jpg',
                            'img/PC05_WM.jpg',
                            'img/PC06_WM.jpg',
                            'img/PC07_WM.jpg',
                            'img/PC08_WM.jpg',
                            'img/PC09_WM.jpg',
                            'img/PC10_WM.jpg',
                            'img/PC11_WM.jpg',
                            'img/PC12_WM.jpg',
                            'img/PC13_WM.jpg',
                            'img/PC14_WM.jpg',
                            'img/PC15_WM.jpg',
                            'img/PC16_WM.jpg'); 

    $sql = "select max(attachmentid) as ATTACHMENT_ID from pc_profile_attachments a where a.attachmenttypeid = 1581 and profileid = ". $pro_id;
    if (!$config->dbcon->select_data($sql)) $config->dbcon->manage_error();
    $result = $config->dbcon->get_result_set();
    $headshot_attachment_id = $result[0]->ATTACHMENT_ID;
    $headshot =  ($headshot_attachment_id == NULL)? $default_images[rand(0, 15)] : $this->image_base_url . $headshot_attachment_id . "&loadkey=attachments&pro_id=" . $pro_id;

    return $headshot;
  }
  
  public function get_summary($pro_id) {
    global $config;
    //$sql = "select pro_intro_text from pc_profile_extend where pro_id = '{$pro_id}'";
    $sql = "select pro_intro_text from pc_profile_master_ext where pro_id = '{$pro_id}'";

    if (!$config->dbcon->select_data($sql)) $config->dbcon->manage_error();
    $summary = $config->dbcon->get_result_set();
    return $summary[0]->PRO_INTRO_TEXT;
  }

  public function get_contact($pro_id) {
    global $config;

    $sql =  "select lower(PRO_F_NAME) as first_name,
            lower(PRO_L_NAME) as last_name,
            upper(PRO_MID_INIT) as middle_initial,
            lower(p.pro_email) as email,
            p.pro_visible_flag as visible,
            decode(p.pro_available_flag, 'Y', 'Now', 'N', to_char(p.pro_available, 'YYYY-MM-DD')) as AVAILABILITY,
            p.pro_available_flag as available_now_flag,
            p.pro_available as availability_date,
            l1.lookupvalue as source_training,
            (SELECT SUBSTR (SYS_CONNECT_BY_PATH (aircraft_name , ', '), 2) csv
               FROM (SELECT aircraft_name , ROW_NUMBER () OVER (ORDER BY af.acf_hours_pic, acf_hours_sic ) rn,
                            COUNT (*) OVER () cnt FROM pc_profile_aircraft_flown af
               LEFT join pc_aircraft_master m on af.acf_aircraftid = m.aircraft_id where af.pro_id = " . $pro_id . ")
               WHERE rn = cnt START WITH rn = 1 CONNECT BY rn = PRIOR rn + 1) as AIRCRAFT_FLOWN,
            (select sum(acf_hours_pic) from pc_profile_aircraft_flown acf where acf.pro_id = p.pro_id) as pic_hours,
            l2.lookupvalue as gender,
            l3.lookupvalue as ethnicity,
            p.pro_altemail as alternate_email,
            p.pro_prim_phone as primary_phone,
            p.pro_sec_phone as secondary_phone
            from pc_profile_master p
            left join pc_lookup_master l1 on p.pro_source_training_id = l1.lookupid
            left join pc_lookup_master l2 on p.pro_gender = l2.lookupid
            left join pc_lookup_master l3 on p.pro_ethnicity_id = l3.lookupid
            where p.pro_id = " . $pro_id;
    error_log("\ncontact sql : {$sql}", 3, '/tmp/bulkprolog');
    if (!$config->dbcon->select_data($sql)) $config->dbcon->manage_error();

    $contact = $config->dbcon->get_result_set();
    error_log("\nresult: ".print_r($contact, true), 3, '/tmp/bulkprolog');
    return $contact;
  }

  public function get_address($pro_id) {
    global $config;
    //address
    $sql = "SELECT ADDR_ID,
      PRO_ID,
      ADDR_COUNTRY_TXT,
      ADDR_STREET_1,
      ADDR_STREET_2,
      ADDR_CITY,
      ADDR_STATE,
      ADDR_ZIP,
      ADDR_LIVED_DATE_FROM,
      ADDR_LIVED_DATE_TO,
      ADDR_PRIMARY_FLAG,
      ADDR_SECONDARY_FLAG,
      ADDR_CURRENT_FLAG,
      ADDR_COUNTY_TXT,
      ADDR_COUNTRY_ID,
      ADDR_COUNTRY_TEXT,
      ADDR_LAST_NAME_USED
      FROM PC_PROFILE_ADDRESS 
      WHERE ADDR_CURRENT_FLAG = 'Y' AND PRO_ID = ". $pro_id;
    if (!$config->dbcon->select_data($sql)) $config->dbcon->manage_error();
    $address = $config->dbcon->get_result_set();
    return $address;
  }

  public function get_flight_hours($pro_id) {
    global $config;
    $sql = "select 
            m.aircraft_name as AIRCRAFT_NAME, 
            m.aircraft_id as aircract_id,
            a.acf_hours_pic as PIC_HOURS, 
            a.acf_hours_sic as SIC_HOURS, 
            a.acf_hours_ins as INST_HOURS,
            a.acf_hours_night as NIGHT_HOURS,
            a.acf_hours_last12mo as LAST_12MO_HOURS,
            a.acf_landings as NUMBER_LANDINGS,
            TO_CHAR(a.acf_last, 'MM-YYYY') as LAST_FLOWN_DATE,
            a.acf_current_flag as CUR_TYPE_RATING,
            a.acf_notes as NOTES
            from pc_profile_aircraft_flown a 
            left join pc_aircraft_master m on a.acf_aircraftid = m.aircraft_id
            where pro_id = " . $pro_id . "order by acf_hours_pic desc";

    if (!$config->dbcon->select_data($sql)) $config->dbcon->manage_error();
    $flight_hours = $config->dbcon->get_result_set();
    error_log("\nget address\n".print_r($flight_hours, true), 3, '/tmp/bulkprolog');

    return $flight_hours;
  }

  public function get_certifications($pro_id) {
    global $config;
    $sql ="SELECT 
          M.CERT_NAME as CERT_NAME, 
          decode(p.cert_issue_date, null, 'N/A', to_char(p.cert_issue_date, 'MM-YYYY')) as ISSUED_DATE,
          decode(p.cert_expire_date, null, 'N/A', TO_CHAR(P.CERT_EXPIRE_DATE, 'MM-YYYY')) as EXPIRED_DATE
          FROM PC_PROFILE_X_CERT P
          LEFT JOIN PC_CERTIFICATION_MASTER M on P.CERT_TYPE_ID = M.CERT_ID
          WHERE PRO_ID = " . $pro_id . "
          ORDER BY ISSUED_DATE";

    if (!$config->dbcon->select_data($sql)) $config->dbcon->manage_error();
    $certifications = $config->dbcon->get_result_set();
    return $certifications;
  }



  public function get_employment($pro_id) {
    global $config;
    $sql = "SELECT 
            TO_CHAR(J.JOB_START_DATE, 'MM-YYYY') as START_DATE,
            TO_CHAR(J.JOB_END_DATE, 'MM-YYYY') as END_DATE,
            LU4.LOOKUPVALUE AS STATUS,
            DECODE(J.JOB_CARRIER_ID, null, J.JOB_COMPANY_NAME, C.COMPANY_NAME) AS COMPANY_NAME,
            J.JOB_CITY as CITY,
            J.JOB_STATE as STATE,
            DECODE(LU2.LOOKUPVALUE, 'UNITED STATES OF AMERICA', 'USA', LU2.LOOKUPVALUE)  AS COUNTRY,
            LU1.LOOKUPVALUE AS JOB_TYPE,
            DECODE(J.JOB_PRIM_AIRCRAFT_ID, NULL, 'NA', A.AIRCRAFT_NAME) AS PRIMARY_AIRCRAFT_FLOWN,
            DECODE(J.JOB_MILITARY_STATUS_ID, null, 'NA', LU3.LOOKUPVALUE) AS MILITARY_STATUS,
            J.JOB_DESCRIPTION AS DESCRIPTION,
            J.JOB_CURRENT_FLAG AS CURRENT_EMPLOYER,
            J.JOB_TITLE AS TITLE,
            J.JOB_DOT_FLAG AS DOT_FLAG
            FROM PC_PROFILE_JOB J
            LEFT JOIN PC_DO_LOOKUP LU1 on J.JOB_TYPE_ID = LU1.LOOKUPID
            LEFT JOIN PC_DO_LOOKUP LU2 on J.JOB_COUNTRY_ID = LU2.LOOKUPID
            LEFT JOIN PC_DO_LOOKUP LU3 on J.JOB_MILITARY_STATUS_ID = LU3.LOOKUPID
            LEFT JOIN PC_DO_LOOKUP LU4 on J.JOB_EMP_TIMEFRAME_ID = LU4.LOOKUPID
            LEFT JOIN PC_AIRCRAFT_MASTER A on J.JOB_PRIM_AIRCRAFT_ID = A.AIRCRAFT_ID
            LEFT JOIN PC_CARRIER_MASTER C on J.JOB_CARRIER_ID = C.CARRIER_ID
            WHERE PRO_ID = " . $pro_id . "
            ORDER BY START_DATE DESC";

    if (!$config->dbcon->select_data($sql)) $config->dbcon->manage_error();
    $employment = $config->dbcon->get_result_set();
    return $employment;

  }

  public function get_education($pro_id) {
    global $config;
    $sql = "SELECT 
      P.PRO_EDUCLEVEL_ID,
      LU1.LOOKUPVALUE as FORMAL_TRAINING,
      LU2.LOOKUPVALUE as HIGHEST_EDUCATION
      FROM PC_PROFILE_MASTER P
      LEFT JOIN PC_DO_LOOKUP LU1 ON P.PRO_SOURCE_TRAINING_ID = LU1.LOOKUPID
      LEFT JOIN PC_DO_LOOKUP LU2 ON P.PRO_EDUCLEVEL_ID = LU2.LOOKUPID
      WHERE P.PRO_ID = ". $pro_id;

    if (!$config->dbcon->select_data($sql)) $config->dbcon->manage_error();
    $education_highest_level = $config->dbcon->get_result_set();


    $sql = "SELECT
            TO_CHAR(S.SCH_START_DATE, 'MM-YYYY') AS START_DATE,
            TO_CHAR(S.SCH_END_DATE, 'MM-YYYY') AS END_DATE,
            DECODE(S.SCH_INSTITUTION_ID, null, S.SCH_NAME, (SELECT DISTINCT(A.INSTITUTION_NAME) FROM PC_ACCREDITATION_MASTER A WHERE S.SCH_INSTITUTION_ID = A.INSTITUTION_ID )) AS SCHOOL_NAME,
            S.SCH_CITY AS SCHOOL_CITY,
            LU1.LOOKUPVALUE AS DEGREE,
            S.SCH_MAJOR AS MAJOR
            FROM PC_PROFILE_SCHOOL S
            LEFT JOIN PC_DO_LOOKUP LU1 ON S.SCH_DEGREE_ID = LU1.LOOKUPID
            WHERE PRO_ID = ". $pro_id;

    if (!$config->dbcon->select_data($sql)) $config->dbcon->manage_error();
    $education_history = $config->dbcon->get_result_set();
    return array('highest_level' => $education_highest_level[0], 'education_history' => $education_history);
  }
  

}

?>
