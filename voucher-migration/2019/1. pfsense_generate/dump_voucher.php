<?php
$input_data = file_get_contents("php://stdin");

require("/etc/inc/voucher.inc");
global $cpzone;
$cpzone="tatdf";
$output = voucher_auth($input_data, 1);

file_put_contents("output.csv", join("\n", $output));
?>

