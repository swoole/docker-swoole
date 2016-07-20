dd=$(cd `dirname $0`; pwd)
dt=`date +%Y%m%d%H%M%S`
echo $dt

#link outer 8080 to inner 9501
#docker run -p 8080:9501 -v ${dd}:/root/ cmptech/auto_alpine_php7_runtime_with_swoole php /root/test_swoole.php &

#using net=host to get the max performance...
#mac (not solve yet.):
#docker run -ti -p 9501:9501 -v `pwd`:/root/ -d cmptech/auto_alpine_php7_runtime_with_swoole php7 /root/test_swoole_server.php
#linux:
docker run -ti --net=host -p 9501 -v `pwd`:/root/ -d cmptech/auto_alpine_php7_runtime_with_swoole php7 /root/test_swoole_server.php

sleep 2

#old version of docker in mac:
#siege -c 10 -b -q -t 10s http://192.168.99.100:8080/
#siege -c 10 -b -q -t 10s http://127.0.0.1:8080/
siege -c 100 -b -q -t 30s http://127.0.0.1:9501/

#echo if ok now go on with
#echo wget https://github.com/swoole/swoole-src/raw/master/examples/proxy.php
