FROM php:7.2.26-cli

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
    SWOOLE_VERSION=4.4.14 \
    ASYNC_VERSION=a075f28d7aba2cc1cfd5a22e1e5440c05c108cf3 \
    ORM_VERSION=877667f36a0ed2ddaf4bec8f3ca86550766cf119 \
    POSTGRESQL_VERSION=bdac440cbb9f011cc84e037a36ca7d2cf56253f4 \
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
