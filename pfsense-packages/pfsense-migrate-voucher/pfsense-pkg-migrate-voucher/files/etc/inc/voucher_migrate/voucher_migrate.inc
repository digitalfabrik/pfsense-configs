<?php
namespace voucher_migrate;

require_once('globals.inc');
require_once("config.inc");

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
