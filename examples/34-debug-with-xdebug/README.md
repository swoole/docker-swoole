[TOC]

This example shows how to use Xdebug, PHPStorm, and the Swoole image together to debug PHP 8.1+ scripts.

[Xdebug][1] works with Swoole on PHP 8.1+.

# Settings

## Dockerized Development Environment

Your _docker-compose.yml_ file should look like following:

```yaml
version: '3'

services:
  app:
    build: .
    environment:
      PHP_IDE_CONFIG: "serverName=swoole"
      XDEBUG_CONFIG: idekey=PHPSTORM remote_host=host.docker.internal remote_port=9000
    ports:
      - 80:9501
```

Please note that:

* Both environment variable _PHP_IDE_CONFIG_ and _XDEBUG_CONFIG_ need to be set.
* Please update remote port number 9000 if you use a different port number in Phpstorm.
* The special DNS name `host.docker.internal` is available only from Docker 18.03 onwards.

## In IDE (PHPStorm)

In PHPStorm, go to File -> Settings -> Languages and Frameworks -> PHP > Debug -> Xdebug, set _Debug port_ to 9000 (it
should be the same as the one set in the _docker-compose.yml_ file). Make sure to have other checkboxes checked.

Now Go to File -> Settings -> Languages and Frameworks -> PHP -> Servers and add a server:

* **Name**: swoole
* **Host**: localhost
* **Port**: 9501
* **Debugger**: Xdebug
* **Use path mapping**: check the checkbox, and map local directory "examples/34-debug-with-xdebug/rootfilesystem" to the absolute path "/" on the server.
 
## In Browser (Chrome)

Install and enable Chrome extension [Xdebug helper][2].
In _Options_ page, select option _PhpStorm_ with value _PHPSTORM_ under section _IDE key_, and save the settings.

# How to Debug

1. Enable Chrome extension _Xdebug helper_.
2. In Phpstorm, click menu _Run_ -> _Start Listening to PHP Debug Connections_.
3. Run command `./bin/example.sh start 34` to start our sample Docker container.

Now You can try following endpoints to check how Xdebug is used:

* _http://<span></span>127.0.0.1_: The default home page from the base image.
* _http://<span></span>127.0.0.1/breakpoint_: This sample code is used to show how to do remote debugging with Xdebug.
* _http://<span></span>127.0.0.1/phpinfo_: From this phpinfo page you can see that Xdebug is enabled with customized configurations.

Run command `./bin/example.sh stop 34` to stop the Docker container.

[1]: https://xdebug.org
[2]: https://chrome.google.com/webstore/detail/xdebug-helper/eadndfjplgieldjbigjakmdgkmoaaaoc
