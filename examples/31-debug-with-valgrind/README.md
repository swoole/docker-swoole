This example shows how to debug script _/var/www/test.php_ using `Valgrind`. [Valgrind](http://valgrind.org/) is
included in image _phpswoole/swoole:latest-dev_ (and any image with "-dev" postfixed to its image tag).

First, start a Docker container by executing following command under same directory of this file:

```bash
docker run --rm -ti $(docker build -q .) bash
```

Inside the container, you can run command `USE_ZEND_ALLOC=0 valgrind --log-file=/tmp/valgrind.log php ./test.php` first;
once it's done, run command `cat /tmp/valgrind.log` to check the logs.
