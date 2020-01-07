This example shows how to debug script _/var/www/test.php_ using [strace](https://strace.io). Strace is
included in image _phpswoole/swoole:latest-dev_ (and any image with "-dev" postfixed to its image tag).

First, start a Docker container by executing following command under same directory of this file:

```bash
docker run --rm --cap-add SYS_PTRACE -ti $(docker build -q .) bash
```

Inside the container, you can run command `strace -o /tmp/strace.log -f /var/www/test.php` to debug the PHP script with
_strace_; once it's done, run command `cat /tmp/strace.log` to check the logs.
