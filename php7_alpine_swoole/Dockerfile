FROM php:7-alpine
MAINTAINER Gavin <acabin@live.com>

RUN apk add --no-cache --virtual .phpize-deps $PHPIZE_DEPS linux-headers && \
    #docker-php-ext-install json xml pdo phar opcache pdo_mysql zip iconv mcrypt bcmath dom pcntl pdo_sqlite
    pecl install swoole && \
    docker-php-ext-enable swoole && \
    apk del .phpize-deps
