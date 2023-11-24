
# Развертывание проекта в Docker  
  
## Первый запуск  
Распаковать базу данных  
  
`tar -xvf ./docker/db/svezem.tar.gz`  
  
Получить переменные среды из файла .env.dist  
`cp .env.dist .env`  
  
Заменить локальные файла конфигураций на конфиги docker  
`cp -r ./common/config/local-docker/* ./common/config/local`  
  
  
## Запуск docker  
  
`docker-compose up -d`

### !!!При первом старте запустится импорт базы данных, поэтому это займет немного времени!!!

## Остановка docker 

`docker-compose down`

## Bash docker-контейнера

`docker-compose exec <имя сервиса> bash`

## Запуск команды в docker-контейнере

`docker-compose exec <имя сервиса> <аргументы>`