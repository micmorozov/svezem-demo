# Установка codecept  
`sudo curl -LsS https://codeception.com/codecept.phar -o /usr/local/bin/codecept`  
`sudo chmod a+x /usr/local/bin/codecept`

#  Тест релевентности транспорта по категориям

## Настройки 
создать базу данных "svezem_relevant"
Установить логин пароль в файле
/tests/relevant.suite.yml

## Запуск теста
`codecept run relevant TrRelevantCest`