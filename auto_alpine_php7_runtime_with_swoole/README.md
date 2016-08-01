# auto_alpine_php7_runtime_with_swoole
php7-latest-runtime w+ swoole based on alpine-edge
(ZH-CN: 基于最新的 alpine+php7 的docker镜像，目标是能做为快速部署应用层）

# Project Reference

https://github.com/cmptech/auto_cmp_php_docker_server

# Version test

```bash
docker run -ti cmptech/auto_alpine_php7_runtime_with_swoole php7 -i
```

# dockerphp.sh

running docker-php7 (using current dir as working dir)

```bash
#!/bin/bash
docker run -v `pwd`:/root/ -w /root/ -ti cmptech/auto_alpine_php7_runtime_with_swoole php $1 $2 $3 $4 $5 $6 $7 $8 $9
```

# Launch (Docker Mode)

```bash
docker run --net=host -ti -p 9501:9501 -v `pwd`:/root/ -d cmptech/auto_alpine_php7_runtime_with_swoole php7 /root/test_swoole_server.php &
echo now run browser to check the port of 9501
```

# Image Size
```
REPOSITORY                                     TAG                 IMAGE ID            CREATED             SIZE
cmptech/auto_alpine_php7_runtime_with_swoole   latest              13023ef1c592        21 hours ago        17.39 MB
cmptech/auto_alpine_php7_runtime_base          latest              d66b20382d7f        21 hours ago        14.32 MB
```

# Pressure Test
```
siege -c 15 -b -q -t 10s http://127.0.0.1:9501/
```
## result of Docker (small server)
```
Transactions:                  97932 hits
Availability:                 100.00 %
Elapsed time:                   9.51 secs
Data transferred:               2.57 MB
Response time:                  0.00 secs
Transaction rate:           10297.79 trans/sec
Throughput:                     0.27 MB/sec
Concurrency:                   14.57
Successful transactions:       97932
Failed transactions:               0
Longest transaction:            0.03
Shortest transaction:           0.00
```
## result of Native
```
Transactions:                 103207 hits
Availability:                 100.00 %
Elapsed time:                   9.68 secs
Data transferred:               2.71 MB
Response time:                  0.00 secs
Transaction rate:           10661.88 trans/sec
Throughput:                     0.28 MB/sec
Concurrency:                   14.78
Successful transactions:      103207
Failed transactions:               0
Longest transaction:            0.04
Shortest transaction:           0.00
```

## conclusion

Docker-mode (net=host) can retrieve 95% performance of native-mode, while remain good scalability 

# Links

* [SWOOLE]
```
http://swoole.com/
http://github.com/swoole/swoole-src/
```

* [LINUX] install php7 w+ swoole in user space:
```
https://github.com/wanjochan/misctools/raw/master/php-fpm-swoole-one-click.sh
```

* [MAC] install php7 with brew
```bash
# install brew
# http://brew.sh/
/usr/bin/ruby -e "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/master/install)"

# install php70 +fpm +opcache +swoole
brew install php70 --with-fpm
brew install php70-opcache
brew install php70-swoole
brew unlink php70 && brew link php70

sudo brew unlink php70
sudo brew remove php70*
```
