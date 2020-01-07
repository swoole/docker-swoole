In this example, we have [PHP-X](https://github.com/swoole/phpx) installed. You can run following command to see if
PHP-X is installed properly or not:

```bash
docker run --rm -t $(docker build -q .) "/usr/src/swoole-src/phpx/bin/phpx --version"
```
