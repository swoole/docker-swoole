This example shows how to debug a Swoole-based web server using [yasd](https://github.com/swoole/yasd) (Yet Another Swoole Debugger) in _Phpstorm_.

# How to Use Yasd With Phpstorm

* In _Phpstorm_, please
  * Update Xdebug settings under _Languages & Frameworks_ > _PHP_ > _Debug_ > _Xdebug_  (please check the bottom of this page for screenshots):
    * Debug port: `9000`
    * Option "can accept external connections" is checked.
  * Add a new server (please check the bottom of this page for screenshots)
    * **Name**: demo
    * **Host**: 127.0.0.1
    * **Port**: 80
    * **Debugger**: Xdebug
    * Check checkbox "**Use path mapping**", and map local directory "examples/35-debug-with-yasd/rootfilesystem" to the absolute path "/" on the server.
* In _Phpstorm_, add some breakpoints in file [server.php](https://github.com/swoole/docker-swoole/blob/master/examples/35-debug-with-yasd/rootfilesystem/var/www/server.php).
* Enable option "Start listening for PHP Debug connections" in _Phpstorm_ (by clicking menu _Run_ -> _Start Listening to PHP Debug Connections_).
  * We use port `9000` on the host for debugging. Please make sure it's not used by any other processes. You can use command `lsof -i :9000` to check which program is using port `9000`.
* Run command `./bin/example.sh start 35` to start our sample Docker container.
* After the container is started, you can use command `php --ri yasd` inside the container to check yasd settings.
* To start debugging, visit URL `http://127.0.0.1` by running command `curl -i http://127.0.0.1` or through a browser.

## Additional Notes

* To use _yasd_, the PHP script has to ben executed with option "-e" in use. For example, we start the web server in this example with an executable "php -e /var/www/server.php".
* Browser extensions/plugins like `Xdebug helper` are not needed.
* In the PHP _.ini_ file, use `zend_extension=yasd.so` instead of `extension=yasd.so` to load the `yasd` extension.
* Before starting the Docker container, please make sure Docker environment variable `PHP_IDE_CONFIG` is properly set. In this example, it's set to "serverName=demo", where "demo" is the server name set in _Phpstorm_.
* Starting from _yasd_ v0.3.9, you can use a domain/host name instead of an address IP for option `yasd.remote_host`, e.g., "yasd.remote_host=host.docker.internal".
* To debug HTTP calls, you don't have to use a browser. In some cases, `curl` is a better choice, especially when debugging HTTP POST/PUT/DELETE requests.

## Additional Commands for Local Development

```bash
docker exec -ti $(docker ps -qf "name=app") env | grep PHP_IDE_CONFIG
docker exec -ti $(docker ps -qf "name=app") php --ri yasd
docker exec -ti $(docker ps -qf "name=app") cat /usr/local/etc/php/conf.d/docker-php-ext-yasd.ini
docker exec -ti $(docker ps -qf "name=app") tail -f debug.log # Only when option "yasd.log_level" is set to 0.

docker exec -ti $(docker ps -qf "name=app") sh # To get access to a shell
```

<img src="https://github.com/swoole/docker-swoole/blob/master/examples/35-debug-with-yasd/images/phpstorm-debug.png" width="85%" />

<img src="https://github.com/swoole/docker-swoole/blob/master/examples/35-debug-with-yasd/images/phpstorm-server.png" width="85%" />
