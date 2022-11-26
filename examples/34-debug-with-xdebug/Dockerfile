FROM phpswoole/swoole

RUN \
    set -ex && \
    pecl channel-update pecl.php.net && \
    pecl install xdebug-stable

COPY ./rootfilesystem/ /
