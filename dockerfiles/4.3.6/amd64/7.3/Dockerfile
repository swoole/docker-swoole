FROM php:7.3.9-cli

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
    SWOOLE_VERSION=4.3.6 \
    ASYNC_VERSION=e0f48077fe59e5d3194bc4215c1cafca39a093cf \
    ORM_VERSION=798678607c98e3314f10f6945d190dc46fa6cdaa \
    POSTGRESQL_VERSION=09a5f8e5bbe4adb3473e7dec607962cf741873df \
    SERIALIZE_VERSION=838cc85420cf9f5183fc912a7436a782a2f4075e \
        install-swoole.sh && \
    docker-php-ext-enable swoole_async && \
    docker-php-ext-enable swoole_serialize && \
    mkdir -p /var/log/supervisor && \
    rm -r /var/lib/apt/lists/*

ENTRYPOINT ["/entrypoint.sh"]
CMD []

WORKDIR "/var/www/"
