#echo "http://nl.alpinelinux.org/alpine/latest-stable/main" > /etc/apk/repositories
#echo "http://nl.alpinelinux.org/alpine/edge/testing/" >> /etc/apk/repositories
#echo "nameserver 8.8.8.8" >> /etc/resolv.conf 

apk update && apk upgrade \
&& apk add autoconf build-base linux-headers \
libaio-dev \
zlib-dev \
php7-dev \
php7-pear \
&& ln -s /usr/bin/php-config7 /usr/bin/php-config \
&& ln -s /usr/bin/phpize7 /usr/bin/phpize \
&& sed -i "s/struct sigaction {/#ifndef __sighandler_t \ntypedef void (*__sighandler_t)(int);\n#endif\nstruct sigaction\n{/g" /usr/include/signal.h \
&& sed -i "s/union {void (*sa_handler)(int)/__sighandler_t sa_handler/g" /usr/include/signal.h \
&& sed -i "s/ -n / /" `which pecl` \
&& pecl install swoole \
&& apk del \
zlib-dev \
libaio-dev \
php7-dev \
php7-pear \
autoconf build-base linux-headers \
&& rm -rf /var/cache/apk && mkdir /var/cache/apk/ && rm -rf /tmp/*

## && cp /usr/lib/php7/modules/swoole.so /root/swoole.so \
## mv /root/swoole.so /usr/lib/php7/modules/swoole.so
