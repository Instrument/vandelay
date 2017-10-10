#!/bin/bash
if
	[ -z ${GCLOUD_DB_IP+1} ] || [ -z ${GCLOUD_DB_USER+1} ] || [ -z ${GCLOUD_DB_PASSWORD+1} ]
then
	echo ERROR: required environment variables: GCLOUD_DB_IP, GCLOUD_DB_USER, GCLOUD_DB_PASSWORD  1>&2
    echo update these variables in your .bashrc file and run "'"source ~/.bashrc"'" before proceeding. 1>&2
    echo More details in the README file. 1>&2
	exit 1
fi


### DB IMPORT ###
sleep 1
mkdir -p /tmp/crafttmp
echo "Updating the 'craftcms' database"
cp /tmp/crafttmp/craft_db.sql /tmp/crafttmp/craft_db.bak

unzip craft_db.zip
sed -i "/SET NAMES utf8;/a-- \n\
-- Create and use the DB; \n\
-- \n\
CREATE DATABASE IF NOT EXISTS craftcms CHARACTER SET utf8; \n\
USE craftcms; \n\
" craft_db.sql

mv craft_db.sql /tmp/crafttmp/
# import fresh stuff
mysql -h $GCLOUD_DB_IP -P 3306 -sfu $GCLOUD_DB_USER -p$GCLOUD_DB_PASSWORD < "/tmp/crafttmp/craft_db.sql"

### INSTALL CRAFT FILES  ###
sleep 1
echo "Updating website files"
rm -r craftfiles
unzip craftfiles.zip 1>/dev/null
cp -r craftfiles/craft /var/www/
rm -r /var/www/html
cp -r craftfiles/public /var/www/html
chmod -R a+r /var/www/html

sed -i "s/DB_IP/$GCLOUD_DB_IP/g" db.php
sed -i "s/DB_USER/$GCLOUD_DB_USER/g" db.php
sed -i "s/DB_ROOT_PASSWORD/$GCLOUD_DB_PASSWORD/g" db.php
cp db.php /var/www/craft/config/db.php
cp general.php /var/www/craft/config/general.php

mkdir -p /var/www/craft/storage

chown -R  www-data:www-data /var/www/craft/app
chown -R  www-data:www-data /var/www/craft/config
chown -R  www-data:www-data /var/www/craft/storage

# CLEANUP
echo "Cleaning up."
rm craftfiles.zip
rm craft_db.zip