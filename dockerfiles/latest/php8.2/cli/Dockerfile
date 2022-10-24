FROM php:8.2-rc-cli

ENV DEBIAN_FRONTEND noninteractive
ENV TERM            xterm-color

ARG DEV_MODE
ENV DEV_MODE $DEV_MODE

COPY ./rootfilesystem/ /

RUN \
    set -ex && \
    curl -sfL https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer && \
    chmod +x /usr/bin/composer                                                                     && \
    composer self-update 2.3.10                                                    && \
    apt-get update              && \
    apt-get install -y             \
# The auto-reloading feature is supported for non-Alpine images only.
#
# Package "inotify-tools" is pre-installed in latest non-Alpine images. For versioned non-Alpine images, it will be
# installed automatically when needed. For details, please check script "autoreload.sh" and example "00-autoreload".
#
# @see https://github.com/swoole/docker-swoole/blob/master/rootfilesystem/usr/local/boot/autoreload.sh
# @see https://github.com/swoole/docker-swoole/tree/master/examples/00-autoreload
        inotify-tools              \
        libcurl4-openssl-dev       \
        libpq-dev                  \
        libssl-dev                 \
        supervisor                 \
        unzip                      \
        zlib1g-dev                 \
        --no-install-recommends && \
    docker-php-ext-install pdo_mysql && \
    pecl channel-update pecl.php.net && \
    pecl install --configureoptions 'enable-redis-igbinary="no" enable-redis-lzf="no" enable-redis-zstd="no"' redis-5.3.7 && \
    docker-php-ext-enable redis && \
    install-swoole.sh master \
        --enable-mysqlnd      \
        --enable-swoole-pgsql \
        --enable-openssl      \
        --enable-sockets --enable-swoole-curl && \
    mkdir -p /var/log/supervisor && \
    rm -rf /var/lib/apt/lists/* $HOME/.composer/*-old.phar /usr/bin/qemu-*-static

ENTRYPOINT ["/entrypoint.sh"]
CMD []

WORKDIR "/var/www/"
