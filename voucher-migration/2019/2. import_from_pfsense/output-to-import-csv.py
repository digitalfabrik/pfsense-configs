#!/usr/bin/python

import csv
import numpy as np

# This script pares the output of the pfsense like: 
# ASDF | ASDF (0/1) good for 51840 Minutes
# GHJK | GHJK (0/2) good for 51840 MinutesA
# And returns:
# ASDF | 51840 | 0/1
# GHJK | 51840 | 0/2





def read_imports(path):
    voucher_imports = []
    with open(path, 'r') as csvfile:
        reader = csv.reader(csvfile, delimiter=',', quotechar='|')
        for row in reader:
            voucher = row[0]
            info = row[1]
            if not voucher in info:
                raise

            if " (20000/" in info:
                continue

            if "good for " in info:
                time = info[info.index("good for ") + len("good for "):-len(" Minutes")]
            elif "already used and expired" in info:
                time = "0"

            
            roll= info[info.index("(") + 1:info.index(")")]

            voucher_imports.append([voucher, time, roll])
    return voucher_imports

with open('voucher_imports.csv', mode='w') as vouchersfile:
    writer= csv.writer(vouchersfile, delimiter=',', quotechar='"', quoting=csv.QUOTE_MINIMAL)

    a = read_imports('output-before-upgrade.csv')
    b = read_imports('output-after.csv')

    result = [xv if xv[1] <= yv[1] else yv for xv, yv in zip(a, b)]

    used_a = sum(1 if row[1] == "0" else 0 for row in a)
    active_a = sum(1 if row[1] != "0" and row[1] != "51840" else 0 for row in a)
    used_b = sum(1 if row[1] == "0" else 0 for row in b)
    active_b = sum(1 if row[1] != "0" and row[1] != "51840" else 0 for row in b)

    used_result = sum(1 if row[1] == "0" else 0 for row in result)
    active_result = sum(1 if row[1] != "0" and row[1] != "51840" else 0 for row in result)
    print("All %s" % len(result)) # 3250 (no test vouchers)

    print("Used before %s" % used_a) 
    print("Used after %s" % used_b)
    print("Used result %s" % used_result)

    print("Active before %s" % active_a) 
    print("Active after %s" % active_b) 
    print("Active result %s" % active_result) 

    for row in result:
        writer.writerow(row)

