FROM php:7.4.28-cli-alpine3.15

RUN \
    curl -sfL https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer && \
    chmod +x /usr/bin/composer                                                                     && \
    composer self-update --clean-backups 2.2.12                                    && \
    apk update && \
    apk add --no-cache libstdc++ && \
    apk add --no-cache --virtual .build-deps $PHPIZE_DEPS curl-dev openssl-dev pcre-dev pcre2-dev zlib-dev && \
    docker-php-ext-install sockets && \
    docker-php-source extract && \
    mkdir /usr/src/php/ext/swoole && \
    curl -sfL https://github.com/swoole/swoole-src/archive/v4.8.9.tar.gz -o swoole.tar.gz && \
    tar xfz swoole.tar.gz --strip-components=1 -C /usr/src/php/ext/swoole && \
    docker-php-ext-configure swoole \
        --enable-http2   \
        --enable-mysqlnd \
        --enable-openssl \
        --enable-sockets --enable-swoole-curl --enable-swoole-json && \
    docker-php-ext-install -j$(nproc) swoole && \
    rm -f swoole.tar.gz $HOME/.composer/*-old.phar && \
    docker-php-source delete && \
    apk del .build-deps

WORKDIR "/var/www/"
