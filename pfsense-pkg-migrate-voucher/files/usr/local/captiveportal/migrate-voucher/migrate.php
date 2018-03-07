<?php

namespace voucher_migrate;
include_once('/etc/inc/voucher_migrate/voucher_migrate.inc');

// todo: make these configurable
const FROM_CPZONE = 'main';
const TO_CPZONE = 'ottostrasse';

function redirect($data) {
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: /migrate-voucher/alternative_login.php?" . http_build_query($data));
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

$target_voucher = generate_voucher($result['nr'], $result['roll'], TO_CPZONE);

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