FROM php:7.1.33-cli-alpine3.12

RUN \
    curl -sfL http://getcomposer.org/installer | php -- --install-dir="/usr/bin" --filename=composer && \
    chmod +x "/usr/bin/composer"                                                                     && \
    composer self-update --clean-backups 1.10.15                                      && \
    apk update && \
    apk add --no-cache libressl-dev libstdc++ && \
    apk add --no-cache --virtual .build-deps $PHPIZE_DEPS && \
    docker-php-ext-install sockets && \
    docker-php-source extract && \
    mkdir /usr/src/php/ext/swoole && \
    curl -sfL https://github.com/swoole/swoole-src/archive/v4.5.5.tar.gz -o swoole.tar.gz && \
    tar xfz swoole.tar.gz --strip-components=1 -C /usr/src/php/ext/swoole && \
    docker-php-ext-configure swoole --enable-http2 --enable-mysqlnd --enable-openssl --enable-sockets && \
    docker-php-ext-install -j$(nproc) swoole && \
    rm -f swoole.tar.gz $HOME/.composer/*-old.phar && \
    docker-php-source delete && \
    apk del .build-deps

WORKDIR "/var/www/"
