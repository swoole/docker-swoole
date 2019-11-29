FROM arm64v8/php:7.3-cli

ENV DEBIAN_FRONTEND noninteractive
ENV TERM            xterm-color

ARG DEV_MODE
ENV DEV_MODE $DEV_MODE

COPY ./rootfilesystem/ /

RUN \
    curl                      \
        -sfL                  \
        --connect-timeout 5   \
        --max-time         15 \
        --retry            5  \
        --retry-delay      2  \
        --retry-max-time   60 \
        http://getcomposer.org/installer | php -- --install-dir="/usr/bin" --filename=composer && \
    chmod +x "/usr/bin/composer"                                                               && \
    composer self-update 1.9.1                                                                 && \
    apt-get update              && \
    apt-get install -y             \
        cmake                      \
        inotify-tools              \
        libargon2-0-dev libcurl4-gnutls-dev libedit-dev libncurses5-dev libsqlite3-dev libxml2-dev \
        libpq-dev                  \
        libssl-dev                 \
        supervisor                 \
        unzip                      \
        zlib1g-dev                 \
        --no-install-recommends && \
    SWOOLE_VERSION=master \
    ASYNC_VERSION=master \
    ORM_VERSION=master \
    POSTGRESQL_VERSION=master \
    SERIALIZE_VERSION=master \
    ZOOKEEPER_VERSION=master \
        install-swoole.sh && \
    docker-php-ext-enable swoole_async && \
    docker-php-ext-enable swoole_orm && \
    docker-php-ext-enable swoole_postgresql && \
    docker-php-ext-enable swoole_serialize && \
    docker-php-ext-enable swoole_zookeeper && \
    mkdir -p /var/log/supervisor && \
    rm -r /var/lib/apt/lists/*

ENTRYPOINT ["/entrypoint.sh"]
CMD []

WORKDIR "/var/www/"
