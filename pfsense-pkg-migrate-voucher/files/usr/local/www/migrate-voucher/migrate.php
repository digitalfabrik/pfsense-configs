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
require_once("config.inc");

const FROM_CPZONE = 'main';
const TO_CPZONE = 'target';

function decrypt_voucher($voucher, $zone)
{
    global $g;

    $result = exec("/usr/local/bin/voucher -c {$g['varetc_path']}/voucher_{$zone}.cfg -k {$g['varetc_path']}/voucher_{$zone}.public -- $voucher");
    list($status, $roll, $nr) = explode(" ", $result);
    if ($status == "OK") {
        var_dump($roll);
        var_dump($nr);
    } else {
        printf(gettext('%1$s invalid: %2$s !!'), $voucher, $result);
        return NULL;
    }

    return array('roll' => $roll, 'nr' => $nr);
}

function generate_voucher($nr, $roll, $zone)
{
    global $g, $config;

    $privkey = base64_decode($config['voucher'][$zone]['privatekey']);
    $fd = fopen("{$g['varetc_path']}/voucher_{$zone}.private", "w");
    if (!$fd) {
        $input_errors[] = gettext("Cannot write private key file") . ".\n";
        return NULL;
    } else {
        chmod("{$g['varetc_path']}/voucher_{$zone}.private", 0600);
        fwrite($fd, $privkey);
        fclose($fd);
    }

    $count = $nr;
    $result = exec("/usr/local/bin/voucher -c {$g['varetc_path']}/voucher_{$zone}.cfg -p {$g['varetc_path']}/voucher_{$zone}.private {$roll} {$count}");
    @unlink("{$g['varetc_path']}/voucher_{$zone}.private");

    // Exec returns the last line of the output. In our case this is the voucher we want.

    return substr($result, 2, strlen($result - 3));
}

$voucher = $_POST["voucher"];

if (!$result) {
    http_response_code(500);
    die();
}

$result = decrypt_voucher($voucher, FROM_CPZONE);

if (!$result) {
    http_response_code(500);
    die();
}

$target_voucher = generate_voucher($result['nr'], $result['roll'], TO_CPZONE);

if (!$target_voucher) {
    http_response_code(500);
    die();
}

var_dump($target_voucher);
http_response_code(200);