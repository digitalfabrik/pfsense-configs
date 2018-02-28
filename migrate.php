<?php

// see https://github.com/ironbits/pfsense-tools/blob/master/pfPorts/voucher/files/voucher.c
// https://github.com/ndejong/pfsense_fauxapi/tree/master/pfSense-pkg-FauxAPI/files/usr/local/pkg
$cryptcode = 0;
const voucher = "";
const charset = "2345678abcdefhijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ";
$base = strlen(charset);
$key = "";

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
for ($i = 0; $i < $strlen; $i++) {
    $char = substr(voucher, $i, 1);


    if (' ' == $char) {
        break;
    }

    $cryptcode *= $base;
    $index = strpos(charset, $char);
    if (-1 == $index) {
        echo("illegal character (%c) found in %s\n" . $char . voucher);
        break;
    }
    $cryptcode += $index;
}

echo($cryptcode);

$cryptbuf = "";
$clearbuf = "";

$crypt_len = 64;
/* move cryptcode into cryptbuf in network order */
ll2buf($cryptcode, $cryptbuf, $crypt_len);


$num = openssl_public_decrypt($cryptbuf, $clearbuf, $key, OPENSSL_NO_PADDING);
echo($cryptbuf);
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


