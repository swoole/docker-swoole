This example shows how to debug script _/var/www/test.php_ using `gdb`. `gdb` is included in image
_phpswoole/swoole:latest-dev_ (and any image with "-dev" postfixed to its image tag).

First, start a Docker container by executing following command under same directory of this file:

```bash
docker run --rm --security-opt seccomp=unconfined -ti $(docker build -q .) bash -c "gdb php"
```

Now you can start debugging PHP code inside the container. For demonstration purpose a list of gdb commands is listed
here to debug the PHP script _test.php_ included. Any time when prompted with a question, please type in "y" and press
the return key to continue.

```text
(gdb) source /usr/src/swoole-src/gdbinit # Swoole source code is under folder /usr/src/swoole-src.
(gdb) b zif_swoole_coroutine_create      # Adds a breakpoint.
(gdb) r test.php                         # Executes the PHP script test.php.
(gdb) co_list                            # Shows list of coroutines.
(gdb) c                                  # Continues code execution.
(gdb) co_list                            # Shows list of coroutines.
(gdb) co_bt                              # Prints stack trace of current coroutine.
(gdb) co_bt 1                            # Prints stack trace of coroutine #1.
(gdb) bt                                 # Prints a stack trace.
(gdb) q                                  # Quits gdb.
```
