This example shows how to debug script _/var/www/test.php_ using `Valgrind`. [Valgrind](http://valgrind.org/) is
included in image _phpswoole/swoole:latest-dev_ (and any image with "-dev" postfixed to its image tag).

1. Start the Docker container with one of following commands
    * `./bin/example.sh start 31` if under root directory of the repository.
    * `docker-compose  up --build -d` if under same directory of this file.
2. Get a bash shell in the container with command `docker exec -ti $(docker ps -qf "name=app") bash`.
3. Inside the container, run command `USE_ZEND_ALLOC=0 valgrind --log-file=/tmp/valgrind.log php ./test.php`.
4. Once it's done, run command `cat /tmp/valgrind.log` to check the logs.
