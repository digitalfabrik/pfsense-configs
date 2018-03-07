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
global $g, $cpzone;

var_dump($g);
var_dump($cpzone);

$v = $_POST["voucher"];

$result = exec("/usr/local/bin/voucher -c {$g['varetc_path']}/voucher_{$cpzone}.cfg -k {$g['varetc_path']}/voucher_{$cpzone}.public -- $v");
list($status, $roll, $nr) = explode(" ", $result);
if ($status == "OK") {
    var_dump($roll);
    var_dump($nr);
} else {
    printf(gettext('%1$s invalid: %2$s !!'), $voucher, $result);
}