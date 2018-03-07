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

var_dump($target_voucher);
http_response_code(200);