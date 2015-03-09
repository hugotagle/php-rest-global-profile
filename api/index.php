<?php

include '../conf/global.api.conf.inc.php';

require($BASECONFIG->incDir.$BASECONFIG->incPrefix."configuration".$BASECONFIG->classExt);
require($BASECONFIG->incDir.$BASECONFIG->incPrefix."db".$BASECONFIG->classExt);
require($BASECONFIG->incDir.$BASECONFIG->incPrefix."profile".$BASECONFIG->classExt);

$config = new configuration();
$config->configure();

//include '../inc/pc_do_profile.class.php';

/*
    API Demo
 
    This script provides a RESTful API interface for a web application
 
    Input:
 
        $_GET['format'] = [ json | html | xml ]
        $_GET['method'] = []
 
    Output: A formatted HTTP response
 
    Author: Mark Roland
 
    History:
        11/13/2012 - Created
 
*/
 
// --- Step 1: Initialize variables and functions
  
/**
 * Deliver HTTP Response
 * @param string $format The desired HTTP response content type: [json, html, xml]
 * @param string $api_response The desired HTTP response data
 * @return void
 **/
function deliver_response($format, $api_response){
 
    // Define HTTP responses
    $http_response_code = array(
        200 => 'OK',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not Found'
    );
 
    // Set HTTP Response
    header('HTTP/1.1 '.$api_response['status'].' '.$http_response_code[ $api_response['status'] ]);
    header('Access-Control-Allow-Credentials: true');
 
    // Process different content types
    if( strcasecmp($format,'json') == 0 ){
 
        // Set HTTP Response Content Type
        header('Content-Type: application/json; charset=utf-8');
 
        // Format data into a JSON response
        $json_response = json_encode($api_response, JSON_PRETTY_PRINT);
 
        // Deliver formatted data
        echo $json_response;
 
    }elseif( strcasecmp($format,'xml') == 0 ){
 
        // Set HTTP Response Content Type
        header('Content-Type: application/xml; charset=utf-8');
 
        // Format data into an XML response (This is only good at handling string data, not arrays)
        $xml_response = '<?xml version="1.0" encoding="UTF-8"?>'."\n".
            '<response>'."\n".
            "\t".'<code>'.$api_response['code'].'</code>'."\n".
            "\t".'<data>'.$api_response['data'].'</data>'."\n".
            '</response>';
 
        // Deliver formatted data
        echo $xml_response;
 
    }else{
 
        // Set HTTP Response Content Type (This is only good at handling string data, not arrays)
        header('Content-Type: text/html; charset=utf-8');
 
        // Deliver formatted data
        echo $api_response['data'];
 
    }
 
    // End script process
    exit;
 
}
 
// Define whether an HTTPS connection is required
$HTTPS_required = FALSE;
 
// Define whether user authentication is required
$authentication_required = TRUE;
 
// Define API response codes and their related HTTP response
$api_response_code = array(
    0 => array('HTTP Response' => 400, 'Message' => 'Unknown Error'),
    1 => array('HTTP Response' => 200, 'Message' => 'Success'),
    2 => array('HTTP Response' => 403, 'Message' => 'HTTPS Required'),
    3 => array('HTTP Response' => 401, 'Message' => 'Authentication Required'),
    4 => array('HTTP Response' => 401, 'Message' => 'Authentication Failed'),
    5 => array('HTTP Response' => 404, 'Message' => 'Invalid Request'),
    6 => array('HTTP Response' => 400, 'Message' => 'Invalid Response Format')
);
 
// Set default HTTP response of 'ok'
$response['code'] = 0;
$response['status'] = 404;
$response['data'] = NULL;
 
// --- Step 2: Authorization
 
// Optionally require connections to be made via HTTPS
if( $HTTPS_required && $_SERVER['HTTPS'] != 'on' ){
    $response['code'] = 2;
    $response['status'] = $api_response_code[ $response['code'] ]['HTTP Response'];
    $response['data'] = $api_response_code[ $response['code'] ]['Message'];
 
    // Return Response to browser. This will exit the script.
    deliver_response($_GET['format'], $response);
}
 
// Optionally require user authentication
session_start();

if (isset($_GET['authKey']) && $_SESSION['authKey'] == $_GET['authKey']) {
	$authentication_required = FALSE;
}

if( $authentication_required ){

    if( empty($_POST['username']) || empty($_POST['password']) ){
        $response['code'] = 3;
        $response['status'] = $api_response_code[ $response['code'] ]['HTTP Response'];
        $response['data'] = $api_response_code[ $response['code'] ]['Message'];
 
        // Return Response to browser
        deliver_response($_GET['format'], $response);
 
    }
 
    session_start();
    
    if (!$config->dbcon->open()) $config->dbcon->manage_error();
    
    $sql = "select pro_id from PC_PROFILE_MASTER where pro_username = '". $_POST["username"]."' AND pro_password_raw = '". md5($_POST["password"]) ."'";
    
    if (!$config->dbcon->select_data($sql)) $config->dbcon->manage_error();

    if ($config->dbcon->get_num_rows() > 0) {
            
        $result = $config->dbcon->get_result_set();
        $_SESSION['profile_id'] = $result[0]->PRO_ID;

    } else {
    	// User fails authentication
        $response['code'] = 4;
        $response['status'] = $api_response_code[ $response['code'] ]['HTTP Response'];
        $response['data'] = $api_response_code[ $response['code'] ]['Message'];
 
        // Return Response to browser
        deliver_response($_GET['format'], $response);    
    }
    
    $config->dbcon->close();
    
    $_SESSION['authKey'] = md5(uniqid(mt_rand(), true));

    // return authKey
    $response['code'] = 1;
    $response['status'] = $api_response_code[ $response['code'] ]['HTTP Response'];
    $response['data'] = $_SESSION['profile_id']. '|' .$_SESSION['authKey']; 
    
    // Return Response to browser
    deliver_response('json', $response);

}

// --- Step 3: Process Request
 
// Method A: Say Hello to the API
if( strcasecmp($_GET['method'],'hello') == 0){
    $response['code'] = 1;
    $response['status'] = $api_response_code[ $response['code'] ]['HTTP Response'];
    $response['data'] = 'Profile ID: '.$_SESSION['profile_id'];
}

// Method B: profile
if( strcasecmp($_GET['method'],'profile') == 0){
    
    if(!isset($_SESSION["profile_id"])) {
      echo('no profile id session variable');
      exit(0);
    } 	
    
    $profile = new Profile();
        
    $payload = $profile->get_profile(intval($_SESSION['profile_id']));
    
    $response['code'] = 1;
    $response['status'] = $api_response_code[ $response['code'] ]['HTTP Response'];
    $response['data'] = $payload;
    
    // Return Response to browser
    deliver_response('json', $payload);
}
 
// Method C: aircraft
if( strcasecmp($_GET['method'],'aircraft') == 0){
    
    if(!isset($_SESSION["profile_id"])) {
      echo('no profile id sessin variable');
      exit(0);
    } 	
    
    $profile = new Profile();
        
    $payload = $profile->get_flight_hours(intval($_SESSION['profile_id']));
    
    $response['code'] = 1;
    $response['status'] = $api_response_code[ $response['code'] ]['HTTP Response'];
    $response['data'] = $payload;
    
    // Return Response to browser
    deliver_response('json', $payload);
} 
// --- Step 4: Deliver Response
 
// Return Response to browser
deliver_response($_GET['format'], $response);
 
?>
            
