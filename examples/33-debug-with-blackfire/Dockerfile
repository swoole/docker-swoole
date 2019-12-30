FROM phpswoole/swoole:4.4.14-php7.3

COPY ./rootfilesystem/ /

RUN \
    install-blackfire.sh && \
    composer require upscale/swoole-blackfire:~3.0.0 --dev
