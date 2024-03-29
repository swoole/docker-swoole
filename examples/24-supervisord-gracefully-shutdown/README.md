# Gracefully Stop Swoole Servers In Docker Containers

In this example, we will demonstrate how to gracefully stop Swoole servers in a Docker container. Note that we use
`supervisord` to manage these Swoole servers in Docker containers.

## How To Gracefully Stop Supervisord Programs In Docker Container

### 1. Use command `exec` to run `supervisord` in the foreground.

As we can see in the [entrypoint.sh] script, command `exec` is used to forward signals to `supervisord` processes:

```bash
exec /usr/bin/supervisord -c /etc/supervisor/supervisord.conf -n # Run supervisord in the foreground.
```

### 2. Set option `stopasgroup` to `true` in the `supervisord` configuration file(s).

Also, please make sure that the `stopsignal` option is commented out or set to `TERM` (the default value), as we can see
in the Supervisord configuration file [swoole.conf]:

```ini
stopasgroup=true
; stopsignal=QUIT ; DON'T set this; it prevents graceful shutdown of Supervisord programs.
```

## Docker Commands To Play With This Example

```bash
docker compose up # Start the Docker container and monitor the logs.

# You will need a separate terminal to run the following commands.

docker compose exec -ti app supervisorctl status # Check the status of Supervisord programs.
docker compose exec -ti app curl -i http://127.0.0.1:9501 # Check the status of the Swoole HTTP server.

docker compose exec -ti app supervisorctl signal SIGTERM swoole # Send SIGTERM signal to the Swoole HTTP server.
docker compose kill --signal=SIGTERM # Send SIGTERM signal to the Docker container.
docker compose stop # Stop the Docker container.
```

[entrypoint.sh]: https://github.com/swoole/docker-swoole/blob/master/rootfilesystem/entrypoint.sh
[swoole.conf]: https://github.com/swoole/docker-swoole/blob/master/rootfilesystem/etc/supervisor/service.d/swoole.conf
