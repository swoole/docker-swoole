Under this folder are files included by the _Supervisor_ configuration file "/etc/supervisor/supervisord.conf" when
running under service mode, where the Docker container is to start some long-running webservices like Nginx. e.g.,

```bash
docker run --rm --name=app -p 80:80 phpswoole/swoole
```

In this case, the list of programs defined under this folder (along with those already under folder _../conf.d/_) will
be started by _Supervisord_.

For the list of programs defined under this folder, file extension must be "_.conf_".
