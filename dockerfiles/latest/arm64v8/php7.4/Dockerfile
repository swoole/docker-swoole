FROM arm64v8/php:7.4-cli

ENV DEBIAN_FRONTEND noninteractive
ENV TERM            xterm-color

ARG DEV_MODE
ENV DEV_MODE $DEV_MODE

COPY ./rootfilesystem/ /

RUN \
    curl -sfL http://getcomposer.org/installer | php -- --install-dir="/usr/bin" --filename=composer && \
    chmod +x "/usr/bin/composer"                                                                     && \
    composer self-update 1.10.17                                                      && \
    apt-get update              && \
    apt-get install -y             \
        inotify-tools              \
        libssl-dev                 \
        supervisor                 \
        unzip                      \
        zlib1g-dev                 \
        libpq-dev                  \
        --no-install-recommends && \
    install-swoole.sh master && \
    mkdir -p /var/log/supervisor && \
    rm -rf /var/lib/apt/lists/* $HOME/.composer/*-old.phar /usr/bin/qemu-*-static

ENTRYPOINT ["/entrypoint.sh"]
CMD []

WORKDIR "/var/www/"
