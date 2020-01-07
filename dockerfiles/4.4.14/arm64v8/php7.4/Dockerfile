FROM arm64v8/php:7.4.1-cli

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
        libssl-dev                 \
        supervisor                 \
        unzip                      \
        zlib1g-dev                 \
        --no-install-recommends && \
    install-swoole.sh 4.4.14 && \
    mkdir -p /var/log/supervisor && \
    rm -r /var/lib/apt/lists/*

ENTRYPOINT ["/entrypoint.sh"]
CMD []

WORKDIR "/var/www/"
