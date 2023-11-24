#!/bin/sh

CWD=`pwd`/bases
CURDATE=`date '+%Y%m%d'`
DB_FILE_NAME="geo_files-$CURDATE.tar.gz"
DB_DIR_NAME="ipgeobase"

echo "Download new geoip database..."
if [ ! -e "$CWD/downloads/$DB_FILE_NAME" ]; then	
	wget http://ipgeobase.ru/files/db/Main/geo_files.tar.gz -O $CWD/downloads/$DB_FILE_NAME
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

echo "Removing old geoip data.";
if [ -d "$CWD/$DB_DIR_NAME/" ]; then
	rm $CWD/$DB_DIR_NAME/*
fi

echo "Extracting new geoip data.";
tar zxvf $CWD/downloads/$DB_FILE_NAME -C $CWD/$DB_DIR_NAME

cd $CWD/../../../../
echo $CWD/$DB_DIR_NAME
php yii update-geo-ip/ip-geo-base --path=$CWD/$DB_DIR_NAME
