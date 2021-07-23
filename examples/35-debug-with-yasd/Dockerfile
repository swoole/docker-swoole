FROM phpswoole/swoole:4.7-php7.4-alpine

RUN set -ex \
    && apk update \
    && apk add --no-cache boost-dev \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && docker-php-source extract \
    && mkdir /usr/src/php/ext/yasd \
    && curl -sfL https://github.com/swoole/yasd/archive/refs/tags/v0.3.9.tar.gz -o yasd.tar.gz \
    && tar xfz yasd.tar.gz --strip-components=1 -C /usr/src/php/ext/yasd \
    && docker-php-ext-configure yasd \
    && docker-php-ext-install -j$(nproc) yasd \
    && rm -f yasd.tar.gz \
    && docker-php-source delete \
    && apk del .build-deps \
    && rm -rf /var/cache/apk/* /tmp/* /usr/share/man /usr/src/php.tar.xz* $HOME/.composer/*-old.phar

COPY ./rootfilesystem/ /

EXPOSE 80

ENTRYPOINT ["php", "-e", "/var/www/server.php"]
