<?php

// see https://github.com/pfsense/FreeBSD-ports/blob/1301159156a8e3723307adf84c3941b0703b56e7/sysutils/voucher/files/voucher.c
// https://github.com/ndejong/pfsense_fauxapi/tree/master/pfSense-pkg-FauxAPI/files/usr/local/pkg
$cryptcode = 0;
const voucher = "gTQiaoZfmh4";
const charset = "1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
$base = strlen(charset);
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

function ll2buf($ll, &$buf, $len)
{
    for ($i = $len - 1; $i >= 0; $i--) {
        $buf[$i] = $ll & 0xff;
        $ll >>= 8;
    }
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

$strlen = strlen(voucher);
for ($i = $strlen - 1; $i >= 0; $i--) {
    $char = substr(voucher, $i, 1);

    if (' ' == $char) {
        break;
    }

    $cryptcode = (int)($cryptcode * $base);

    $index = strpos(charset, $char);
    if (FALSE == $index) {
        echo("illegal character (%c) found in %s\n" . $char . voucher);
        break;
    }
    $cryptcode = (int)($cryptcode + $index);
}

$cryptbuf = array();
$clearbuf = "";

$crypt_len = 8;
/* move cryptcode into cryptbuf in network order */
ll2buf($cryptcode, $cryptbuf, $crypt_len);
ksort($cryptbuf);
$cryptbuf = pack("c*", ...$cryptbuf);

// printf("ll2buf len=%d %.16llx -> ", $crypt_len, $cryptcode);

$num = openssl_public_decrypt($cryptbuf, $clearbuf, $public_key, OPENSSL_NO_PADDING);

var_dump($clearbuf);
$clearbuf = unpack("C*", $clearbuf); // from binary string to associative array
var_dump($clearbuf);
$clearcode = buf2ll($clearbuf, $crypt_len);


if ($num < 0) {
    echo("Invalid code <%s>" . voucher);
    exit(1);
} else {
    /* move clearbuf into clearcode in network order */

    /* extract info's out of decrypted code */
    $rollid = $clearcode & ((1 << roll_bits) - 1);
    $ticketid = ($clearcode >> roll_bits) & ((1 << ticket_bits) - 1);
    $checksum = $clearcode >> (ticket_bits + roll_bits);
    $checksum &= (1 << checksum_bits) - 1; // get rid of garbage
    var_dump($rollid);
    var_dump($ticketid);
    var_dump($checksum);
}


