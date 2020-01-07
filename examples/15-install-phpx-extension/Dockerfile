FROM phpswoole/swoole:4.4.14-php7.3

RUN \
    install-phpx.sh 0.2.0 && \
    install-swoole-ext-zookeeper.sh 4ae5cdf2aa630dfd590fa63f1779eb2a8a8d2950 && \
    docker-php-ext-enable swoole_zookeeper
