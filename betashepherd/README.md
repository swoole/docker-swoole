docker-swoole
=============

A docker-swoole image base on centos:6, help you easily play with [swoole](https://github.com/swoole), enjoy.

*Install Docker*
[Visit Docker-Doc](https://docs.docker.com/)

**Get Docker Swoole Image**
```shell
docker pull betashepherd/docker-swoole
docker images
```

**Run docker-swoole image**
```shell
docker run -i -t betashepherd/docker-swoole:1.7.15-stable /bin/bash
bash-4.1# su swoole 
bash-4.1$ cd ~
bash-4.1$ pwd
/home/swoole
bash-4.1$ php -r "echo SWOOLE_VERSION;"
```
