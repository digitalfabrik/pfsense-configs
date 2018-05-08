<?php

namespace voucher_migrate;

require_once('phpsessionmanager.inc');
require_once('globals.inc');
require_once("config.inc");
require_once("voucher.inc");
require_once("captiveportal.inc");

function decrypt_voucher($voucher, $zone)
{
    global $g;

    $result = exec("/usr/local/bin/voucher -c {$g['varetc_path']}/voucher_{$zone}.cfg -k {$g['varetc_path']}/voucher_{$zone}.public -- $voucher");
    list($status, $roll, $nr) = explode(" ", $result);
    if ($status != "OK") {
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

    return substr($result, 2, strlen($result) - 3);
}


// todo: make these configurable
const FROM_CPZONE = 'main';
const TO_CPZONE = 'tatdf';
const TARGET_ROLL_OFFSET = 10;

function redirect($data) {
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: /captiveportal-alternative_login.php?" . http_build_query($data));
    die();
}


if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo('Invalid request method!');
    http_response_code(500);
    die();
}

if (!isset($_POST['auth_voucher'])) {
    echo('Invalid voucher in POST!');
    http_response_code(500);
    die();
}

$voucher = $_POST["auth_voucher"];
// todo: validate input
$result = decrypt_voucher($voucher, FROM_CPZONE);

if (!$result) {
    // Fallback to default decryption
    redirect($_POST);
}

if (!$result) {
    echo('Could not decrypt voucher!');
    http_response_code(500);
    die();
}

global $cpzone;
$cpzone = FROM_CPZONE;

if (voucher_auth($voucher, 1)[1] == 'Access denied!') {
    echo('Voucher invalid!');
    http_response_code(500);
    die();
}

$target_voucher = generate_voucher($result['nr'], $result['roll'] + TARGET_ROLL_OFFSET, TO_CPZONE);

if (!$target_voucher) {
    echo('Could not generate corresponding voucher!');
    http_response_code(500);
    die();
}

$new_POST = $_POST;
$new_POST['auth_voucher'] = $target_voucher;

$_SESSION['voucher_migrate_post'] = $new_POST;

redirect($new_POST);
die();