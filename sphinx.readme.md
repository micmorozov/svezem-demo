### [Справка](https://www.8host.com/blog/ustanovka-i-nastrojka-sphinx-v-ubuntu-14-04-2/)

# Установка Sphinx
`sudo apt-get install sphinxsearch`

# Настройки Sphinx
в файле /etc/default/sphinxsearch 
`START=yes`

# Запуск индексации
`sudo indexer --rotate --config ./console/config/local/sphinx.conf --all`
# Запуск с конфигом
`sudo searchd --config ./console/config/local/sphinx.conf --iostats`

# Запуск по CRON
`sudo ./console/config/local/sphinxCron.sh`

## Запрос через командную строку

`mysql -h0 -P9306`

`SELECT * FROM svezem_transport WHERE MATCH('камаз');`