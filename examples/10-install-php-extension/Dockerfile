FROM phpswoole/swoole

COPY ./rootfilesystem/ /

RUN \
    pecl update-channels        && \
    pecl install redis          && \
    docker-php-ext-enable redis && \
    docker-php-ext-install pcntl
