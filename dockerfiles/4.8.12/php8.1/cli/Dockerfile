FROM php:8.1.10-cli

ENV DEBIAN_FRONTEND noninteractive
ENV TERM            xterm-color

ARG DEV_MODE
ENV DEV_MODE $DEV_MODE

COPY ./rootfilesystem/ /

RUN \
    set -ex && \
    curl -sfL https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer && \
    chmod +x /usr/bin/composer                                                                     && \
    composer self-update 2.2.18                                                    && \
    apt-get update              && \
    apt-get install -y             \
        libcurl4-openssl-dev       \
        libssl-dev                 \
        supervisor                 \
        unzip                      \
        zlib1g-dev                 \
        --no-install-recommends && \
    docker-php-ext-install pdo_mysql && \
    pecl channel-update pecl.php.net && \
    pecl install --configureoptions 'enable-redis-igbinary="no" enable-redis-lzf="no" enable-redis-zstd="no"' redis-5.3.7 && \
    docker-php-ext-enable redis && \
    install-swoole.sh 4.8.12 \
        --enable-http2   \
        --enable-mysqlnd \
        --enable-openssl \
        --enable-sockets --enable-swoole-curl --enable-swoole-json && \
    mkdir -p /var/log/supervisor && \
    rm -rf /var/lib/apt/lists/* $HOME/.composer/*-old.phar /usr/bin/qemu-*-static

ENTRYPOINT ["/entrypoint.sh"]
CMD []

WORKDIR "/var/www/"
