# auto_alpine_php7_runtime_with_swoole
php7(latest) runtime w+ swoole based on alpine(edge)
(CHN: 基于 最新的 alpine、php7建立的docker镜像，目标是希望能做为快速部署的应用堡垒）

# Launch

```bash
dd=$(cd `dirname $0`; pwd)
docker run -ti -p 9501:9501 -v `pwd`:/root/ -d cmptech/auto_alpine_php7_runtime_with_swoole php7 /root/test_swoole_server.php
```

# Image Size
```
REPOSITORY                                     TAG                 IMAGE ID            CREATED             SIZE
cmptech/auto_alpine_php7_runtime_with_swoole   latest              931e58555b36        40 minutes ago      18.62 MB
```
# Pressure Test (small server)
```
siege -c 15 -b -q -t 10s http://127.0.0.1:9501/
```

```
Lifting the server siege...      done.

Transactions:                  93359 hits
Availability:                 100.00 %
Elapsed time:                   9.25 secs
Data transferred:               2.45 MB
Response time:                  0.00 secs
Transaction rate:           10092.87 trans/sec
Throughput:                     0.26 MB/sec
Concurrency:                   15.45
Successful transactions:       93359
Failed transactions:               0
Longest transaction:            0.03
Shortest transaction:           0.00
```
