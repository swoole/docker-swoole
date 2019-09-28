This example shows how to debug script _/var/www/test.php_ using `gdb`. `gdb` is included in image
_phpswoole/swoole:latest-dev_ (and any image with "-dev" postfixed to its image tag).

1. Start the Docker container with one of following commands
    * `./bin/example.sh start 30` if under root directory of the repository.
    * `docker-compose  up --build -d` if under same directory of this file.
2. Get a bash shell in the container with command `docker exec -ti $(docker ps -qf "name=app") bash`.
3. Inside the container, run command `gdb php`.

Now you can start debugging PHP code. For demonstration purpose a list of gdb commands is listed here to debug the PHP
script _test.php_ included. Any time when prompted with a question, please type in "y" and press the return key to
continue.

```text
(gdb) source /usr/src/swoole/gdbinit # Swoole source code is under folder /usr/src/swoole.
(gdb) b zif_swoole_coroutine_create  # Add a breakpoint.
(gdb) r test.php                     # Run the PHP script test.php.
(gdb) co_list                        # Show list of coroutines.
(gdb) c                              # Continue code execution.
(gdb) co_list                        # Show list of coroutines.
(gdb) bt                             # Print a stack trace.
(gdb) q                              # Quit gdb.
```
