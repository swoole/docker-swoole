In this example we launch an HTTP server at port 9502 in a one-off container (which is created only to
run a command). You may use following commands to see if it works as should:

```bash
docker build -t app examples/21-supervisord-tasks

docker run --rm -it app bash -c \
    "sleep 2; curl http://127.0.0.1:9502; echo; echo; curl http://127.0.0.1:9501"
```

The first Docker command is to build the "app" image. The second Docker command is to run a list of
commands with the image built, which has two HTTP calls made to:

1. URL "http://127.0.0.1:9502". This should return HTTP 200 back since the Supervisord program "swoole2"
   is started.
2. URL "http://127.0.0.1:9501". This fails with curl message saying "Connection refused". The reason is
   that the built-in Supervisord program "swoole" is not started when the container is created only to
   run an one-off command.
