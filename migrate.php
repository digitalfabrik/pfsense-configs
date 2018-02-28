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

function ll2buf($ll, $buf, $len)
{
    for ($i = $len - 1; $i >= 0; $i--) {
        $buf[$i] = $ll & 0xff;
        $ll >>= 8;
    }
}

function buf2ll($buf, $len)
{
    $ll = $buf[0];
    for ($i = 1; $i < $len; $i++) {
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
    if ($cryptcode > 0xFFFFFFFFFFFFFFFF - 1) {
        $cryptcode = $cryptcode - (0xFFFFFFFFFFFFFFFF - 1);
    }

    $index = strpos(charset, $char);
    if (FALSE == $index) {
        echo("illegal character (%c) found in %s\n" . $char . voucher);
        break;
    }
    $cryptcode = (int)($cryptcode + $index);
    if ($cryptcode > 0xFFFFFFFFFFFFFFFF - 1) {
        $cryptcode = $cryptcode - 0xFFFFFFFFFFFFFFFF - 1;
    }


}
echo($cryptcode);

$cryptbuf = "";
$clearbuf = "";

$crypt_len = 8;
/* move cryptcode into cryptbuf in network order */
ll2buf($cryptcode, $cryptbuf, $crypt_len);
echo($crypt_len);
echo($cryptbuf);

$num = openssl_public_decrypt($cryptbuf, $clearbuf, $public_key, OPENSSL_NO_PADDING);

echo($clearbuf);
return;
if ($num < 0) {
    echo("Invalid code <%s>" . voucher);
    exit(1);
} else {
    /* move clearbuf into clearcode in network order */
    $clearcode = buf2ll($clearbuf, $crypt_len);

    /* extract info's out of decrypted code */
    $rollid = $clearcode & ((1 << roll_bits) - 1);
    $ticketid = ($clearcode >> roll_bits) & ((1 << ticket_bits) - 1);
    $checksum = $clearcode >> (ticket_bits + roll_bits);
    $checksum &= (1 << checksum_bits) - 1; // get rid of garbage
    echo($rollid);
}


