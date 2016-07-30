#echo "http://nl.alpinelinux.org/alpine/latest-stable/main" > /etc/apk/repositories
#echo "http://nl.alpinelinux.org/alpine/edge/testing/" >> /etc/apk/repositories
#echo "nameserver 8.8.8.8" >> /etc/resolv.conf 

apk update
apk upgrade
apk add git autoconf build-base linux-headers libaio-dev php7-dev
ln -s /usr/bin/php-config7 /usr/bin/php-config
ln -s /usr/bin/phpize7 /usr/bin/phpize
cd /root/
git clone https://github.com/swoole/swoole-src.git
cd swoole-src/
phpize
./configure
make && make install
apk del libaio-dev php7-dev git autoconf build-base linux-headers
rm -rf /var/cache/apk/*
rm -rf /root/swoole-src/
rm -rf /tmp/*
