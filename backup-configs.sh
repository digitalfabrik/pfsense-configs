#!/bin/bash

mkdir -p config-backups

for domain in "$@"
do
	ssh backup@$domain cat /cf/conf/config.xml | gpg --encrypt -r max@maxammann.org -r seeberg@integreat-app.de -o ~/config-backups/$domain-$(date +"%Y%m%d-%H%M%S")-config.xml.gpg
done

