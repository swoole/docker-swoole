FROM php:7.2.22-cli

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
    composer self-update 1.9.0                                                                 && \
    apt-get update              && \
    apt-get install -y             \
        cmake                      \
        libargon2-0-dev libcurl4-gnutls-dev libedit-dev libncurses5-dev libsqlite3-dev libxml2-dev \
        libpq-dev                  \
        libssl-dev                 \
        supervisor                 \
        unzip                      \
        zlib1g-dev                 \
        --no-install-recommends && \
    SWOOLE_VERSION=4.4.5 \
    ASYNC_VERSION=474364ee541874df27e2ab1ce861705f5564992d \
    ORM_VERSION=798678607c98e3314f10f6945d190dc46fa6cdaa \
    POSTGRESQL_VERSION=1ccd2ffbdc6e6d1f7b067509817f4bf93fe1982a \
    SERIALIZE_VERSION=84982d6f6c68e000c1dbbae3bc46d3630ffef798 \
        install-swoole.sh && \
    docker-php-ext-enable swoole_async && \
    docker-php-ext-enable swoole_serialize && \
    mkdir -p /var/log/supervisor && \
    rm -r /var/lib/apt/lists/*

ENTRYPOINT ["/entrypoint.sh"]
CMD []

WORKDIR "/var/www/"
