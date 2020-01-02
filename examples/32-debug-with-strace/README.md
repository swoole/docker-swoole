This example shows how to debug script _/var/www/test.php_ using [strace](https://strace.io). Strace is
included in image _phpswoole/swoole:latest-dev_ (and any image with "-dev" postfixed to its image tag).

1. Start the Docker container with one of following commands
    * `./bin/example.sh start 32` if under root directory of the repository.
    * `docker-compose up --build -d` if under same directory of this file.
2. Run command `docker exec -t $(docker ps -qf "name=app") bash -c "strace -o /tmp/strace.log -f /var/www/test.php"` to debug the PHP script with _strace_.
3. Once it's done, run command `docker exec -ti $(docker ps -qf "name=app") bash -c "cat /tmp/strace.log"` to check the logs.
