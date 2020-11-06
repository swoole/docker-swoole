FROM php:7.1.33-cli-alpine3.12

ENV DEBIAN_FRONTEND noninteractive
ENV TERM            xterm-color

RUN \
    curl -sfL http://getcomposer.org/installer | php -- --install-dir="/usr/bin" --filename=composer && \
    chmod +x "/usr/bin/composer"                                                                     && \
    composer self-update 1.10.17                                                      && \
    apk update && \
    apk add --no-cache --virtual .build-deps $PHPIZE_DEPS && \
    pecl install swoole-4.5.7 --enable-http2 --enable-mysqlnd --enable-openssl --enable-sockets && \
    apk del .build-deps && \
    docker-php-ext-enable swoole

WORKDIR "/var/www/"
