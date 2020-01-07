In this example, we have the Swoole extension [zookeeper](https://github.com/swoole/ext-zookeeper) installed and enabled.
You can run following command to see if it is enabled or not:

```bash
docker run --rm -t $(docker build -q .) "php -m" | grep swoole
```
