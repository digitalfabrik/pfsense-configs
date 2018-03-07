<?php
// namespace fauxapi\v1;
// define('FAUXAPI_CALLID', uniqid());
//
// include_once('/etc/inc/fauxapi/fauxapi.inc');
//
// $action = (string)filter_input(INPUT_GET, 'action');
// if(empty($action)) {
//     $action = 'undefined';
// }
//
// $fauxapi = new fauxApi();
// $response = $fauxapi->$action($_GET, file_get_contents("php://input"));
//
// http_response_code($response->http_code);
// if(!empty($response->action)) {
//     header('fauxapi-callid: ' . FAUXAPI_CALLID);
// }
// header('Content-Type: application/json');
//
// unset($response->http_code);
// echo json_encode($response);

echo("It works!");