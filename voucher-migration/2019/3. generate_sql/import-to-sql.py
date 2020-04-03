#!/usr/bin/python

import csv

# This script ready a csv like:
# ASDF | 51840 | 0/1
# GHJK | 51840 | 0/2
# And create a SQL script to import this data into a radius db


def read_imports(path):
    voucher_imports = []
    with open(path, 'r') as csvfile:
        reader = csv.reader(csvfile, delimiter=',', quotechar='|')
        for row in reader:
            voucher_imports.append(row)
    return voucher_imports

voucher_imports = read_imports("voucher_imports.csv")
with open("voucher.sql", "w") as text_file:
    for row in voucher_imports:
        text_file.write("INSERT into radcheck (username, attribute, op, value) VALUES ('%s','Max-All-Session',':=','%s');\n" % (row[0], int(row[1]) * 60))
        text_file.write("insert into radcheck (username, attribute, op, value) values ('%s','Cleartext-Password', ':=', 'dummy');\n" % (row[0]))

