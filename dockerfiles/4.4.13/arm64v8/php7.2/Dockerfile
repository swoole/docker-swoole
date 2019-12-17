FROM arm64v8/php:7.2.25-cli

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
        libargon2-0-dev libcurl4-gnutls-dev libedit-dev libncurses5-dev libsqlite3-dev libxml2-dev \
        libpq-dev                  \
        libssl-dev                 \
        supervisor                 \
        unzip                      \
        zlib1g-dev                 \
        --no-install-recommends && \
    SWOOLE_VERSION=4.4.13 \
    ASYNC_VERSION=73efc26a829c49c0f9e8f1f0c47ed00c0a26a474 \
    ORM_VERSION=798678607c98e3314f10f6945d190dc46fa6cdaa \
    POSTGRESQL_VERSION=1af80f185fa4151f1ec62ff2f6574c8cf08a656d \
    SERIALIZE_VERSION=84982d6f6c68e000c1dbbae3bc46d3630ffef798 \
    ZOOKEEPER_VERSION=4ae5cdf2aa630dfd590fa63f1779eb2a8a8d2950 \
        install-swoole.sh && \
    docker-php-ext-enable swoole_async && \
    docker-php-ext-enable swoole_serialize && \
    mkdir -p /var/log/supervisor && \
    rm -r /var/lib/apt/lists/*

ENTRYPOINT ["/entrypoint.sh"]
CMD []

WORKDIR "/var/www/"
