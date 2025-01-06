FROM php:8.2.26-zts

ENV DEBIAN_FRONTEND=noninteractive
ENV TERM=xterm-color

ARG DEV_MODE
ENV DEV_MODE=$DEV_MODE

COPY --from=composer:2.8.4 /usr/bin/composer /usr/bin/
COPY ./rootfilesystem/ /

RUN \
    set -ex && \
    apt-get update  && \
    apt-get install -y \
        libcurl4-openssl-dev       \
        libbrotli-dev              \
        libpq-dev                  \
        libsqlite3-dev             \
        libssl-dev                 \
        supervisor                 \
        unzip                      \
        zlib1g-dev                 \
        --no-install-recommends && \
# PHP extension pdo_mysql is included since 4.8.12+ and 5.0.1+.
    docker-php-ext-install pdo_mysql && \
    pecl channel-update pecl.php.net && \
    pecl install --configureoptions 'enable-redis-igbinary="no" enable-redis-lzf="no" enable-redis-zstd="no"' redis-6.1.0 && \
# PHP extension Redis is included since 4.8.12+ and 5.0.1+.
    docker-php-ext-enable redis && \
    install-swoole.sh 6.0.0 \
        --enable-swoole-thread \
        --enable-swoole-curl   \
        --enable-mysqlnd       \
        --enable-swoole-pgsql  \
        --enable-swoole-sqlite \
        --enable-brotli        \
        --enable-openssl       \
        --enable-sockets    && \
    mkdir -p /var/log/supervisor && \
    rm -rf /var/lib/apt/lists/* /usr/bin/qemu-*-static

ENTRYPOINT ["/entrypoint.sh"]
CMD []

WORKDIR "/var/www/"
