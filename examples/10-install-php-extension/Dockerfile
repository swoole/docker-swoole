FROM deminy/swoole:4.4.3-php7.3

COPY ./rootfilesystem/ /

RUN \
    pecl update-channels        && \
    pecl install redis          && \
    docker-php-ext-enable redis && \
    docker-php-ext-install pcntl
