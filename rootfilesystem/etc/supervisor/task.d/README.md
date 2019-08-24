Under this folder are files included by the _Supervisor_ configuration file "/etc/supervisor/supervisord.conf" when
running under task mode, where a specified command is executed in a Docker container launched. e.g.,

```bash
docker run --rm -t phpswoole/swoole bash -c "php -v"
```

In this case, the list of programs defined under this folder (along with those already under folder _../conf.d/_) will
be started by _Supervisord_ first before executing command `php -v`.

For the list of programs defined under this folder, file extension must be "_.conf_".
