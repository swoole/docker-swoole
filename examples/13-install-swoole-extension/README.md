For this example, we have following Swoole extensions installed in the image created:

* [async](https://github.com/swoole/ext-async)
* [orm](https://github.com/swoole/ext-orm)
* [postgresql](https://github.com/swoole/ext-postgresql)
* [serialize](https://github.com/swoole/ext-serialize)

Among these extensions installed, only the _async_ extension is enabled. You can run following command to create the
image and see which Swoole extensions are enabled:

```bash
docker run --rm -t $(docker build -q .) bash -c "php -m" | grep swoole
```
