# Docker Image for Swoole

[![Build Status](https://travis-ci.org/swoole/docker-swoole.svg?branch=master)](https://travis-ci.org/swoole/docker-swoole)
[![Docker Pulls](https://img.shields.io/docker/pulls/phpswoole/swoole.svg)](https://hub.docker.com/r/phpswoole/swoole)
[![License](https://img.shields.io/badge/license-apache2-blue.svg)](https://github.com/swoole/docker-swoole/blob/master/LICENSE)

This image is built for general-purpose. We have different examples included in this Git repository to help developers
to get familiar with the image and _Swoole_.

You can get the image from [here](https://hub.docker.com/r/phpswoole/swoole).

# Supported Tags and Respective `Dockerfile` Links

* [latest](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/latest/amd64/php7.4/Dockerfile), [latest-dev](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/latest/amd64/php7.4/Dockerfile) (built daily)
* amd64
    * PHP 7.4: [4.5.7-php7.4](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/4.5.7/amd64/php7.4/Dockerfile), [4.5.7-php7.4-dev](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/4.5.7/amd64/php7.4/Dockerfile)
    * PHP 7.3: [4.5.7-php7.3](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/4.5.7/amd64/php7.3/Dockerfile), [4.5.7-php7.3-dev](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/4.5.7/amd64/php7.3/Dockerfile)
    * PHP 7.2: [4.5.7-php7.2](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/4.5.7/amd64/php7.2/Dockerfile), [4.5.7-php7.2-dev](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/4.5.7/amd64/php7.2/Dockerfile)
* arm64v8
    * PHP 7.4: [4.5.7-php7.4-arm64v8](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/4.5.7/arm64v8/php7.4/Dockerfile), [4.5.7-php7.4-arm64v8-dev](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/4.5.7/arm64v8/php7.4/Dockerfile)
    * PHP 7.3: [4.5.7-php7.3-arm64v8](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/4.5.7/arm64v8/php7.3/Dockerfile), [4.5.7-php7.3-arm64v8-dev](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/4.5.7/arm64v8/php7.3/Dockerfile)
    * PHP 7.2: [4.5.7-php7.2-arm64v8](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/4.5.7/arm64v8/php7.2/Dockerfile), [4.5.7-php7.2-arm64v8-dev](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/4.5.7/arm64v8/php7.2/Dockerfile)
* Alpine
    * PHP 7.4: [4.5.7-php7.4-alpine](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/4.5.7/alpine/php7.4/Dockerfile)
    * PHP 7.3: [4.5.7-php7.3-alpine](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/4.5.7/alpine/php7.3/Dockerfile)
    * PHP 7.2: [4.5.7-php7.2-alpine](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/4.5.7/alpine/php7.2/Dockerfile)

# List of Images

Image _phpswoole/swoole_ is built using a recent commit from the master branch of the [Swoole](https://github.com/swoole/swoole-src) project.

Besides that, we build images with major versions of PHP (7.2 to 7.4) under different architectures (_amd64_ and
_arm64v8_ only for now). For example, we have following images built for Swoole 4.5.7:

* amd64 images:
    * `phpswoole/swoole:4.5.7-php7.4`
    * `phpswoole/swoole:4.5.7-php7.3`
    * `phpswoole/swoole:4.5.7-php7.2`
* arm64v8 images:
    * `phpswoole/swoole:4.5.7-php7.4-arm64v8`
    * `phpswoole/swoole:4.5.7-php7.3-arm64v8`
    * `phpswoole/swoole:4.5.7-php7.2-arm64v8`
* Alpine images:
    * `phpswoole/swoole:4.5.7-php7.4-alpine`
    * `phpswoole/swoole:4.5.7-php7.3-alpine`
    * `phpswoole/swoole:4.5.7-php7.2-alpine`

We also build development images where extra tools are included for testing, debugging, and monitoring purpose.
Development images are tagged in the format of _&lt;image name&gt;:&lt;image tag&gt;-dev_ (a "dev" postfix added to the
original image tag). e.g.,

* `phpswoole/swoole:latest-dev`
* `phpswoole/swoole:latest-arm64v8-dev`
* `phpswoole/swoole:4.5.7-php7.4-dev`
* `phpswoole/swoole:4.5.7-php7.4-arm64v8-dev`

Here is the list of commands and tools available in development images:

* [gdb](https://www.gnu.org/s/gdb)
* git
* lsof
* [strace](https://strace.io)
* [tcpdump](https://www.tcpdump.org)
* [Valgrind](http://www.valgrind.org)
* vim

NOTE:

1. We don't have development tools built in for Alpine images. There is no Docker images like `phpswoole/swoole:4.5.7-php7.4-alpine-dev`.
2. [PHP-X](https://github.com/swoole/phpx) and Swoole extensions are not installed by default. Please check section "Examples" below to see how to install them manually.

# Feature List

Our _amd64_ and _arm64v8_ images have following features included:

* Built-in scripts to manage _Swoole_ extensions and _Supervisord_ programs.
* Easy to manage booting scripts in Docker.
* Allow running PHP scripts and other commands directly in different environments (including ECS).
* Use one root filesystem for simplicity (one Docker `COPY` command only in dockerfiles).
* _Composer_ included.
* Built for different architectures (for now only amd64 and arm64v8 images are built).
* Support auto-reloading for local development.
* Support code debugging for local development.

# Examples

Following examples are for the latest images, the _amd64_ images, and the _arm64v8_ images only. We don't have examples
included for the Alpine images.

You can use the image to serve an HTTP/WebSocket server, or run some one-off command with it. e.g.,

```bash
docker run --rm phpswoole/swoole "php -m"
docker run --rm phpswoole/swoole "php --ri swoole"
docker run --rm phpswoole/swoole "composer --version"
```

We have various examples included under folder "_examples/_" to help developers better use the image. These examples are
numerically ordered. Each example has a _docker-compose.yml_ file included, along with some other files. To run an
example, please start Docker containers using the _docker-compose.yml_ file included, then check HTTP output from URL
http://127.0.0.1 unless otherwise noted. You may use the following commands to start/stop/restart Docker containers:

```bash
./bin/example.sh start   00 # To start container(s) of the first example.
./bin/example.sh stop    00 # To stop container(s) of the first example.
./bin/example.sh restart 00 # To restart container(s) of the first example.
```

To run another example, just replace the last command line parameter _00_ with an example number (e.g., _05_).

Here is a list of the examples under folder "_examples/_":

* Basic examples:
    * **00-autoload**: Restart the Swoole web server automatically if file changes detected under web root.
    * **01-basic**: print out "Hello, World!" using Swoole as backend HTTP server.
    * **02-www**: to use some customized PHP script(s) in the Docker image built.
    * **03-nginx**: to use Swoole behind an Nginx server.
    * **04-entrypoint**: to use a self-defined entrypoint script in the Docker image built.
    * **05-boot**: to update content in the Docker container through a booting script.
    * **06-update-token**: to show how to update server configurations with built-in script _update-token.sh_.
    * **07-disable-default-server**: Please check the [docker-compose.yml](https://github.com/swoole/docker-swoole/blob/master/examples/07-disable-default-server/docker-compose.yml) file included to see show how to disable the default web server created with _Swoole_.
* Manage PHP extensions and configurations:
    * **10-install-php-extension**: how to install/enable PHP extensions.
    * **11-customize-extension-options**: how to overwrite/customize PHP extension options.
    * **12-php.ini**: how to overwrite/customize PHP options.
    * **13-install-swoole-extension**: Please check the [README](https://github.com/swoole/docker-swoole/tree/master/examples/13-install-swoole-extension) file included to see how to install Swoole extensions like [async](https://github.com/swoole/ext-async), [orm](https://github.com/swoole/ext-orm), [postgresql](https://github.com/swoole/ext-postgresql), and [serialize](https://github.com/swoole/ext-serialize).
    * **14-install-phpx**: Please check the [README](https://github.com/swoole/docker-swoole/tree/master/examples/14-install-phpx) file included to see how to install [PHP-X](https://github.com/swoole/phpx).
    * **15-install-phpx-extension**: Please check the [README](https://github.com/swoole/docker-swoole/tree/master/examples/15-install-phpx-extension) file included to see how to install [PHP-X](https://github.com/swoole/phpx) based extensions like [zookeeper](https://github.com/swoole/ext-zookeeper).
* Manage Supervisord programs:
    * **20-supervisord-services**: to show how to run Supervisord program(s) in Docker.
    * **21-supervisord-tasks**: to show how to run Supervisord program(s) when launching a one-off command with Docker. Please check the [README](https://github.com/swoole/docker-swoole/tree/master/examples/21-supervisord-tasks) file included to see how to run the example.
    * **22-supervisord-enable-program**: to show how to enable program(s) in Supervisord program.
    * **23-supervisord-disable-program**: to show how to disable Supervisord program(s).
* Debugging:
    * **30-debug-with-gdb**: Please check the [README](https://github.com/swoole/docker-swoole/tree/master/examples/30-debug-with-gdb) file included to see how to debug your PHP code with _gdb_.
    * **31-debug-with-valgrind**: Please check the [README](https://github.com/swoole/docker-swoole/tree/master/examples/31-debug-with-valgrind) file included to see how to debug your PHP code with _Valgrind_.
    * **32-debug-with-strace**: Please check the [README](https://github.com/swoole/docker-swoole/tree/master/examples/32-debug-with-strace) file included to see how to debug your PHP code with _strace_.
    * **33-debug-with-blackfire**: Please check the [README](https://github.com/swoole/docker-swoole/tree/master/examples/33-debug-with-blackfire) file included to see how to debug your PHP code with [Blackfire](https://blackfire.io).
    * **34-debug-with-sdebug**: Please check the [README](https://github.com/swoole/docker-swoole/tree/master/examples/34-debug-with-sdebug) file included to see how to debug your PHP code in different ways with [sdebug](https://github.com/swoole/sdebug) (forked from Xdebug to work with Swoole).

# Build Images Manually

The Docker images are built and pushed out automatically through Travis. If you want to build some image manually, please
follow these three steps.

**1**. Install Composer packages. If you have command "composer" installed already, just run `composer update -n`.

**2**. Use commands like following to create dockerfiles:

```bash
./bin/generate-dockerfiles.php latest # Generate dockerfiles to build images from the master branch of Swoole.
./bin/generate-dockerfiles.php 4.5.7 # Generate dockerfiles to build images for Swoole 4.5.7.
```

**3**. Build Docker images with commands like:

```bash
docker build -t phpswoole/swoole                       -f dockerfiles/latest/amd64/php7.4/Dockerfile   .
docker build -t phpswoole/swoole:latest-arm64v8        -f dockerfiles/latest/arm64v8/php7.4/Dockerfile .
docker build -t phpswoole/swoole:4.5.7-php7.4         -f dockerfiles/4.5.7/amd64/php7.4/Dockerfile   .
docker build -t phpswoole/swoole:4.5.7-php7.4-arm64v8 -f dockerfiles/4.5.7/arm64v8/php7.4/Dockerfile .
docker build -t phpswoole/swoole:4.5.7-php7.4-alpine  -f dockerfiles/4.5.7/alpine/php7.4/Dockerfile .
```

To build development images (where extra tools are included), add an argument _DEV_MODE_:

```bash
docker build --build-arg DEV_MODE=true -t phpswoole/swoole:latest-dev                -f dockerfiles/latest/amd64/php7.4/Dockerfile   .
docker build --build-arg DEV_MODE=true -t phpswoole/swoole:latest-arm64v8-dev        -f dockerfiles/latest/arm64v8/php7.4/Dockerfile .
docker build --build-arg DEV_MODE=true -t phpswoole/swoole:4.5.7-php7.4-dev         -f dockerfiles/4.5.7/amd64/php7.4/Dockerfile   .
docker build --build-arg DEV_MODE=true -t phpswoole/swoole:4.5.7-php7.4-arm64v8-dev -f dockerfiles/4.5.7/arm64v8/php7.4/Dockerfile .
```

# Credits

Current implementation borrows ideas from [Demin](https://github.com/deminy)'s work at [Glu Mobile](https://glu.com).
