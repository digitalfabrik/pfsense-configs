<?php

// see https://github.com/pfsense/FreeBSD-ports/blob/1301159156a8e3723307adf84c3941b0703b56e7/sysutils/voucher/files/voucher.c
// https://github.com/ndejong/pfsense_fauxapi/tree/master/pfSense-pkg-FauxAPI/files/usr/local/pkg


$charset = "1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
$public_key = "-----BEGIN PUBLIC KEY-----
MCQwDQYJKoZIhvcNAQEBBQADEwAwEAIJALvGa/aL7wjvAgMBAAE=
-----END PUBLIC KEY-----";
$private_key = "-----BEGIN RSA PRIVATE KEY-----
MD8CAQACCQC7xmv2i+8I7wIDAQABAgkAtSaalatpCpECBQDix33ZAgUA0/hYBwIE
Uo2OWQIEcwRZdwIFAMw/Gi8=
-----END RSA PRIVATE KEY-----";

const roll_bits = 16;
const ticket_bits = 10;
const checksum_bits = 5;
const code_bytes = 8;

function ll2buf($code, $length)
{
    $buf = array();

    for ($i = $length - 1; $i >= 0; $i--) {
        $buf[$i] = $code & 0xff;
        $code >>= 8;
    }
    return $buf;
}

function buf2ll($buf, $len)
{
    $ll = $buf[1];
    for ($i = 2; $i <= $len; $i++) {
        $ll <<= 8;
        $ll += $buf[$i];
    }

    return $ll;
}

$base = strlen($charset);
$voucher = "gTQiaoZfmh4";
$voucher_length = strlen($voucher);

$encrypted_code = 0;
for ($i = $voucher_length - 1; $i >= 0; $i--) {
    $char = substr($voucher, $i, 1);

    $encrypted_code = $encrypted_code * $base;

    $index = strpos($charset, $char);
    if ($index === FALSE) {
        printf("illegal character (%s) found in %s\n", $char, $voucher);
        return;
    }

    $encrypted_code = $encrypted_code + $index;
}

$encrypted_buffer = ll2buf($encrypted_code, code_bytes);
ksort($encrypted_buffer); // Sort so pack uses the correct order
$encrypted_buffer = pack("C*", ...$encrypted_buffer); // from sorted array to binary string

$decrypted_buffer = "";
$decrypt_result = openssl_public_decrypt($encrypted_buffer, $decrypted_buffer, $public_key, OPENSSL_NO_PADDING);

if ($decrypt_result < 0) {
    printf("Invalid code <%s>\n", $voucher);
} else {
    $decrypted_buffer = unpack("C*", $decrypted_buffer); // from binary string to associative array

    $decrypted_code = buf2ll($decrypted_buffer, code_bytes);

    /* extract info's out of decrypted code */
    $rollid = $decrypted_code & ((1 << roll_bits) - 1);
    $ticketid = ($decrypted_code >> roll_bits) & ((1 << ticket_bits) - 1);
    $checksum = $decrypted_code >> (ticket_bits + roll_bits);
    $checksum &= (1 << checksum_bits) - 1; // get rid of garbage
    var_dump($rollid);
    var_dump($ticketid);
    var_dump($checksum);
}


