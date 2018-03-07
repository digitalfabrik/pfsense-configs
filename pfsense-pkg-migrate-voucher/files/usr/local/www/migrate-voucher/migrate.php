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

require_once('globals.inc');
global $g;

$from_cpzone = 'main';
$to_cpzone = 'target';

$v = $_POST["voucher"];



var_dump($v);

$result = exec("/usr/local/bin/voucher -c {$g['varetc_path']}/voucher_{$from_cpzone}.cfg -k {$g['varetc_path']}/voucher_{$from_cpzone}.public -- $v");
list($status, $roll, $nr) = explode(" ", $result);
if ($status == "OK") {
    var_dump($roll);
    var_dump($nr);
} else {
    printf(gettext('%1$s invalid: %2$s !!'), $voucher, $result);
}

$amount = $nr + 1;
$result = exec("/usr/local/bin/voucher -c {$g['varetc_path']}/voucher_{$to_cpzone}.cfg -p {$g['varetc_path']}/voucher_{$to_cpzone}.private {$roll} {$amount}");
var_dump($result);
echo("\n");
echo(explode("\n", $result)[7]);
echo(explode("\n", $result)[8]);
echo(explode("\n", $result)[7 + $nr]);