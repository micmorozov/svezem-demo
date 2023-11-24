#!/bin/sh

PATH='/bin:/sbin:/usr/bin:/usr/sbin:/usr/local/bin:/usr/local/sbin'
export PATH

STARTOPER=`date "+%H:%M:%S:%N"`; 
./ipgeobase.sh;
#./maxmind.sh;

STOPOPER=`date "+%H:%M:%S:%N"`; 
echo TIME: $STARTOPER - $STOPOPER; 
