<?php

namespace voucher_migrate;
include_once('/etc/inc/voucher_migrate/voucher_migrate.inc');

const FROM_CPZONE = 'main';
const TO_CPZONE = 'target';


if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo('Invalid request method!');
    http_response_code(500);
    die();
}

if (!isset($_POST['voucher'])) {
    echo('Invalid voucher in POST!');
    http_response_code(500);
    die();
}

$voucher = $_POST["voucher"];
$result = decrypt_voucher($voucher, FROM_CPZONE);

if (!$result) {
    echo('Could not decrypt voucher!');
    http_response_code(500);
    die();
}

$target_voucher = generate_voucher($result['nr'], $result['roll'], TO_CPZONE);

if (!$target_voucher) {
    echo('Could not generate corresponding voucher!');
    http_response_code(500);
    die();
}

$new_POST = $_POST;
$new_POST['auth_voucher'] = $target_voucher;

$_SESSION['voucher_migrate_post'] = $new_POST;

header("HTTP/1.1 301 Moved Permanently");
header("Location: /migrate-voucher/alternative_login.php");
die();