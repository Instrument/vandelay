#!/bin/bash
if 
	[ -z ${GCLOUD_DB_IP+1} ] || [ -z ${GCLOUD_DB_USER+1} ] || [ -z ${GCLOUD_DB_PASSWORD+1} ] || [ -z ${GCLOUD_DB_NAME+1} ]
then 
	echo ERROR: required environment variables: GCLOUD_DB_IP, GCLOUD_DB_USER, GCLOUD_DB_PASSWORD, GCLOUD_DB_NAME  1>&2
    echo update these variables in your .bashrc file and run "'"source ~/.bashrc"'" before proceeding. 1>&2
    echo More details in the README file. 1>&2
	exit 1
fi



export DEBIAN_FRONTEND=noninteractive

## INSTALL ALL THE THINGS!!!
apt-get install -y python-software-properties
add-apt-repository -y ppa:ondrej/php
apt-get update -y


### INSTALL AND CONFIGURE APACHE2 ###
apt-get -q -y install pkg-config unzip apache2 

cp apache2.conf /etc/apache2/apache2.conf
cp dir.conf /etc/apache2/mods-available/dir.conf
a2enmod expires
a2enmod rewrite
service apache2 restart

## DB INSTALL
echo debconf mysql-server/root_password password $GCLOUD_DB_PASSWORD | debconf-set-selections
echo debconf mysql-server/root_password_again password $GCLOUD_DB_PASSWORD | debconf-set-selections
apt-get -q install -y mysql-server # Install MySQL quietly and shut down the local server
service mysql stop

### INSTALL PHP STUFF ###
apt-get -q -y install php7.1 libapache2-mod-php7.1 \
php7.1-mysql php7.1-mbstring php-pear \
libmcrypt-dev php7.1-curl php7.1-mcrypt \
php7.1-dev libmagickwand-dev pkg-config

# install imagick quietly
printf "\n" | pecl install imagick
cp php.ini /etc/php/7.1/apache2/php.ini
service apache2 restart

### DB IMPORT ###
echo "deploying the 'craftcms' database"
unzip craft_db.zip
sed -i "/SET NAMES utf8;/a-- \n\
-- Create and use the DB; \n\
-- \n\
CREATE DATABASE IF NOT EXISTS craftcms CHARACTER SET utf8; \n\
USE craftcms; \n\
" craft_db.sql

mkdir -p /tmp/crafttmp
mv craft_db.sql /tmp/crafttmp/
mysql -h $GCLOUD_DB_IP -P 3306 -sfu $GCLOUD_DB_USER -p$GCLOUD_DB_PASSWORD < "/tmp/crafttmp/craft_db.sql"


### INSTALL CRAFT FILES  ###
echo "installing website files"
unzip craftfiles.zip
cp -r craftfiles/craft /var/www/
sed -i "s/DB_IP/$GCLOUD_DB_IP/g" db.php
sed -i "s/DB_USER/$GCLOUD_DB_USER/g" db.php
sed -i "s/DB_ROOT_PASSWORD/$GCLOUD_DB_PASSWORD/g" db.php
cp db.php /var/www/craft/config/db.php
cp general.php /var/www/craft/config/general.php
rm -r /var/www/html
cp -r craftfiles/public /var/www/html
chmod -R a+r /var/www/html
mkdir -p /var/www/craft/storage

chown -R  www-data:www-data /var/www/craft/app
chown -R  www-data:www-data /var/www/craft/config
chown -R  www-data:www-data /var/www/craft/storage
