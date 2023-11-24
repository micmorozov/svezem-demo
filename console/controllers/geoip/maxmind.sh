#!/bin/sh

CWD=`pwd`/bases
CURDATE=`date '+%Y%m%d'`
DB_FILE_NAME="GeoIPCountryCSV_$CURDATE.zip"
DB_DIR_NAME="maxmind"

echo "Download new maxmind database";
if [ ! -e "$CWD/downloads/$DB_FILE_NAME" ]; then	
	wget http://geolite.maxmind.com/download/geoip/database/GeoIPCountryCSV.zip -O $CWD/downloads/$DB_FILE_NAME;
else
	echo "File $CWD/downloads/$DB_FILE_NAME exists.";
fi

if [ ! -d "$CWD/$DB_DIR_NAME" ]; then
	mkdir "$CWD/$DB_DIR_NAME";
fi

if [ ! -e "$CWD/downloads/$DB_FILE_NAME" ]; then
	echo "ERROR: $CWD/downloads/$DB_FILE_NAME does not exist."
	exit;
fi

echo "Removing old maxmind data.";
if [ -d "$CWD/$DB_DIR_NAME/" ]; then
	rm $CWD/$DB_DIR_NAME/*
fi

echo "Extracting new geoip data.";
unzip -oj $CWD/downloads/$DB_FILE_NAME -d $CWD/$DB_DIR_NAME/

cd $CWD/../../../
php cron.php updategeoip MaxMindCountry --path=$CWD/$DB_DIR_NAME
