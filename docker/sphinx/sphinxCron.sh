#!/bin/sh

#Дирктория скрипта
DIR="$( cd "$( dirname "$0" )" && pwd )"

#Индексация Sphinx
indexer --rotate --config "$DIR/sphinx.conf" --all --quiet

#Очистка realtime индекса
mysql -h0 -P9306 <<QUERY
TRUNCATE RTINDEX transport_realtime;
QUERY
