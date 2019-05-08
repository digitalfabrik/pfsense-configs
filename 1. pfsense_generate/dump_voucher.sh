#!/bin/bash

# This script pipes vouchers from the pfsense and dumps their validity

cat vouchers.csv | pfSsh.php -f dump_vouchers.php
