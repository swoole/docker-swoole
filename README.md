docker-swoole
=============

A docker-swoole image base on centos:6, help you easy play with swoole, enjoy.

Install Docker

Centos 7
```shell
yum install docker
```

Centos 6.5

```shell
rpm -ivh http://pkgs.repoforge.org/rpmforge-release/rpmforge-release-0.5.3-1.el6.rf.x86_64.rpm
yum install epel-release
yum install docker-io
```

*Other Platform
[Visit Docker-Doc](docs.docker.com)


Run Docker Service
```shell
service docker start
chkconfig docker on

docker pull betashpherd/docker-swoole
docker images
```

Run docker-swoole image

```shell
docker run -i -t betashepherd/docker-swoole:1.0 /bin/bash
bash-4.1# php -r "echo SWOOLE_VERSION;"
```

