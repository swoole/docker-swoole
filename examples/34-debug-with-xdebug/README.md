This example shows how to use [Xdebug][1], PhpStorm, and the Swoole image together to debug PHP scripts.

Please note that Xdebug 3.1.0+ works with Swoole 5.0.2+ on PHP 8.1+ only. Lower versions of Xdebug don't work with Swoole.

# Settings

## Dockerized Development Environment

The _docker-compose.yml_ file should look similar to the following:

```yaml
version: '3'

services:
  app:
    build: .
    environment:
      PHP_IDE_CONFIG: "serverName=swoole"
      XDEBUG_SESSION: "PHPSTORM"
    ports:
      - 80:9501
```

In the _docker-compose.yml_ file:

* Environment variable _PHP_IDE_CONFIG_ needs to be set to `serverName=SomeName`, where SomeName is the name of the server configured on the `PHP | Servers` page of the `Settings/Preferences` dialog in PhpStorm.
* Environment variable _XDEBUG_SESSION_ is to enable step debugging. There are other ways to enable step debugging. See [Xdebug documentation][5] for more details.

## Xdebug Configuration Options

File [xdebug.ini][3] used in the Docker image should look similar to the following:

```ini
[xdebug]
zend_extension=xdebug
xdebug.mode=develop,debug
xdebug.client_host=host.docker.internal
xdebug.client_port=9003
```

## PhpStorm Settings

In the `Settings/Preferences` dialog, click `PHP` -> `Debug` -> `Xdebug`, set _Debug port_ to 9003 (it
should be the same as the one set in [xdebug.ini][3]). Make sure to have other checkboxes checked.

In the `Settings/Preferences` dialog, click `PHP` -> `Servers`, then add a server there:

* **Name**: `swoole` (must be the same as the one in the [docker-compose.yml][2] file)
* **Host**: `localhost`
* **Port**: `9501` (must be the same as the one in the [server.php][4] file)
* **Debugger**: `Xdebug`
* **Use path mapping**: check the checkbox, and map local directory "examples/34-debug-with-xdebug/rootfilesystem" to the absolute path "/" on the server.
 
# How to Debug

1. In Phpstorm, click menu _Run_ -> _Start Listening to PHP Debug Connections_.
2. Run command `./bin/example.sh start 34` to start our sample Docker container.

Now You can try following endpoints to check how Xdebug is used:

* _http://<span></span>127.0.0.1_: The default home page from the base image.
* _http://<span></span>127.0.0.1/breakpoint_: This sample code is used to show how to do remote debugging with Xdebug.
* _http://<span></span>127.0.0.1/phpinfo_: From this phpinfo page you can see that Xdebug is enabled with customized configurations.

Please try not hitting the URLs through web browsers since they tend to send multiple requests to server automatically
(e.g., to fetch favicon.ico). It's recommended to use a command line tool like `curl` to hit the URLs.

Once done with the tests, run command `./bin/example.sh stop 34` to stop the Docker container.

[1]: https://xdebug.org
[2]: https://github.com/swoole/docker-swoole/blob/master/examples/34-debug-with-xdebug/docker-compose.yml
[3]: https://github.com/swoole/docker-swoole/blob/master/examples/34-debug-with-xdebug/rootfilesystem/usr/local/etc/php/conf.d/xdebug.ini
[4]: https://github.com/swoole/docker-swoole/blob/master/examples/34-debug-with-xdebug/rootfilesystem/var/www/server.php
[5]: https://xdebug.org/docs/step_debug
