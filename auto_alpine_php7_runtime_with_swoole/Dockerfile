FROM cmptech/auto_alpine_php7_runtime_base

Maintainer Wanjo Chan ( http://github.com/wanjochan/ )

###########################
#RUN echo "http://nl.alpinelinux.org/alpine/latest-stable/main" > /etc/apk/repositories
#RUN echo "http://nl.alpinelinux.org/alpine/edge/testing/" >> /etc/apk/repositories
#RUN echo "nameserver 8.8.8.8" >> /etc/resolv.conf && apk update && apk upgrade
#RUN apk add php7
#RUN apk add php7-opcache
#RUN ln -fs /usr/bin/php7 /usr/bin/php

########################### build swoole.so and copy to /root/
ADD build_swoole_so.sh /root/

RUN sh /root/build_swoole_so.sh

ADD php.ini /etc/php7/

RUN php -m
