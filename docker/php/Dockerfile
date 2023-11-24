FROM php:7.1.28-fpm

RUN apt-get update && apt-get upgrade -y \
    && apt-get install -y \
    libicu-dev g++ \
    procps

RUN docker-php-ext-install -j$(nproc) iconv pdo_mysql
RUN docker-php-ext-configure intl
RUN docker-php-ext-install intl pcntl
RUN pecl install redis && docker-php-ext-enable redis

RUN apt-get -y --allow-unauthenticated install libgearman-dev wget unzip \
    && cd /tmp \
    && wget https://github.com/wcgallego/pecl-gearman/archive/gearman-2.0.3.zip \
    && unzip gearman-2.0.3.zip \
    && mv pecl-gearman-gearman-2.0.3 pecl-gearman \
    && cd pecl-gearman \
    && phpize \
    && ./configure \
    && make -j$(nproc) \
    && make install \
    && cd / \
    && rm /tmp/gearman-2.0.3.zip \
    && rm -r /tmp/pecl-gearman \
    && docker-php-ext-enable gearman

RUN pecl install xdebug-2.6.1 \
    && docker-php-ext-enable xdebug

RUN apt-get install -y zlib1g zlib1g-dbg zlib1g-dev zlibc libpng-dev libzip-dev libbz2-dev
RUN docker-php-ext-install -j$(nproc) zip gd

COPY ./php.ini /usr/local/etc/php/conf.d/

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

ENV COMPOSER_ALLOW_SUPERUSER=1

RUN apt-get install -y pngquant jpegoptim gifsicle

RUN apt-get install -y openssl

RUN apt-get install -y telnet

RUN apt-get update \
    && apt-get install -y \
        librabbitmq-dev \
        libssh-dev \
    && pecl install amqp \
    && docker-php-ext-enable amqp

# Устанавливаем java
RUN mkdir -p /usr/share/man/man1
RUN apt-get update \
    && apt-get install -y default-jre

# Устанавливаем node
RUN curl -sL https://deb.nodesource.com/setup_12.x | bash -
RUN apt-get install -y nodejs
RUN npm i -g sass
