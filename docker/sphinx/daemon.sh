#!/bin/bash

indexer --config /etc/sphinx/sphinx.conf --all
searchd --config /etc/sphinx/sphinx.conf --iostats
./etc/sphinx/sphinxCron.sh

sleep infinity