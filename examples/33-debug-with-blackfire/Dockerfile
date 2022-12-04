FROM phpswoole/swoole:5.0

COPY ./rootfilesystem/ /

# @see https://blackfire.io/docs/php/integrations/php-docker
RUN \
    set -ex && \
    curl -sfL -o /tmp/blackfire-probe.tar.gz \
        https://blackfire.io/api/v1/releases/probe/php/linux/$(uname -m)/$(php -r "echo PHP_MAJOR_VERSION.PHP_MINOR_VERSION;") && \
    mkdir -p /tmp/blackfire && \
    tar zxpf /tmp/blackfire-probe.tar.gz -C /tmp/blackfire && \
    cp /tmp/blackfire/blackfire-*.so $(php-config --extension-dir)/blackfire.so && \
    mv $(php-config --ini-dir)/blackfire.ini-suggested $(php-config --ini-dir)/blackfire.ini && \
    rm -rf /tmp/blackfire /tmp/blackfire-probe.tar.gz && \
    composer update -n && \
    composer clear-cache
