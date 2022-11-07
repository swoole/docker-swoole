# Docker Image for Swoole

[![Twitter](https://badgen.net/badge/icon/twitter?icon=twitter&label)](https://twitter.com/phpswoole)
[![Discord](https://badgen.net/badge/icon/discord?icon=discord&label)](https://discord.swoole.dev)
[![Docker Pulls](https://img.shields.io/docker/pulls/phpswoole/swoole.svg)](https://hub.docker.com/r/phpswoole/swoole)
[![License](https://img.shields.io/badge/license-apache2-blue.svg)](https://github.com/swoole/docker-swoole/blob/master/LICENSE)

This image is built for general-purpose. We have different examples included in this Git repository to help developers
to get familiar with the image and _Swoole_.

You can get the image from [here](https://hub.docker.com/r/phpswoole/swoole).

Table of Contents
=================

* [Feature List](#feature-list)
* [How to Use This Image](#how-to-use-this-image)
   * [How to Install More PHP Extensions](#how-to-install-more-php-extensions)
   * [Disable Installed/Enabled PHP Extensions](#disable-installedenabled-php-extensions)
   * [More Examples](#more-examples)
* [Image Variants](#image-variants)
* [Supported Tags and Respective Dockerfile Links](#supported-tags-and-respective-dockerfile-links)
   * [Versioned images](#versioned-images-based-on-stable-releases-of-swoole)
      * [Swoole 5.0](#swoole-50)
      * [Swoole 4.8](#swoole-48)
      * [Swoole 4.7](#swoole-47)
      * [Swoole 4.6](#swoole-46)
      * [Swoole 4.5](#swoole-45)
      * [Swoole 4.4](#swoole-44)
   * [Nightly images](#nightly-images-built-daily-using-the-master-branch-of-swoole-src)
* [Build Images Manually](#build-images-manually)
* [Credits](#credits)

# Feature List

* Built-in scripts to manage _Swoole_ extensions and _Supervisord_ programs.
* Easy to manage booting scripts in Docker.
* Allow running PHP scripts and other commands directly in different environments (including ECS).
* Use one root filesystem for simplicity (one Docker `COPY` command only in dockerfiles).
* _Composer_ included (_Composer v1_ for Swoole 4.5.8 and before; _Composer v2_ for Swoole 4.5.9 and after).
* Built for different architectures.
* Support auto-reloading for local development.<sup>1</sup>
* Support code debugging for local development.
* **PHP extension _pdo_mysql_ included since 4.8.12+ and 5.0.1+.**<sup>2</sup>
* **PHP extension _Redis_ included since 4.8.12+ and 5.0.1+.**<sup>2</sup> It's installed with default options.

**NOTES**

1. The auto-reloading feature is supported for non-Alpine images only.
2. To disable extension _pdo_mysql_ and/or _Redis_, please check section [Disable Installed/Enabled PHP Extensions](#disable-installedenabled-php-extensions).

# How to Use This Image

The `phpswoole/swoole` image is built using [the official PHP image](https://hub.docker.com/_/php) as base image, with a few changes.
For basic usage, please check the description section of [the official PHP image](https://hub.docker.com/_/php).

## How to Install More PHP Extensions

Same as in the official PHP image, most PHP extensions can be installed/configured using built-in helper scripts `docker-php-ext-configure`, `docker-php-ext-install`, `docker-php-ext-enable`, and `docker-php-source`. Here are some examples.

```Dockerfile
# To install the MySQL extensions.
# NOTE: The pdo_mysql extension is included in 4.8.12+ and 5.0.1+ images.
FROM phpswoole/swoole:4.7-php7.4-alpine

RUN docker-php-ext-install mysqli pdo_mysql
```

```Dockerfile
# To install the Redis extension.
# NOTE: The Redis extension is included in 4.8.12+ and 5.0.1+ images.
FROM phpswoole/swoole:4.7-php7.4-alpine

RUN set -ex \
    && pecl channel-update pecl.php.net \
    && yes no | pecl install redis-stable \
    && docker-php-ext-enable redis
```

```Dockerfile
# To install the Couchbase extension.
FROM phpswoole/swoole:4.8-php7.4-alpine

RUN set -ex \
    && apk update \
    && apk add --no-cache libcouchbase=2.10.6-r0 \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS libcouchbase-dev=2.10.6-r0 zlib-dev \
    && pecl update-channels \
    && pecl install couchbase-2.6.2 \
    && docker-php-ext-enable couchbase \
    && apk del .build-deps \
    && rm -rf /var/cache/apk/* /tmp/* /usr/share/man /usr/src/php.tar.xz*
```

## Disable Installed/Enabled PHP Extensions

Since 4.8.12+ and 5.0.1+, PHP extensions _pdo_mysql_ and _Redis_ are installed and enabled by default. If you want to
disable them, you can use the following commands in your Dockerfile.

```Dockerfile
FROM phpswoole/swoole:4.8-alpine

RUN set -ex && \
    rm -f "$(php-config --ini-dir)/docker-php-ext-pdo_mysql.ini" && \
    rm -f "$(php-config --ini-dir)/docker-php-ext-redis.ini"
```

Note that above commands will remove the corresponding configuration files for the extensions, but won't remove the extensions themselves.

## More Examples

**Following examples are for non-Alpine images only**. We don't have examples included for the Alpine images.

You can use the image to serve an HTTP/WebSocket server, or run some one-off command with it. e.g.,

```bash
docker run --rm phpswoole/swoole php -m
docker run --rm phpswoole/swoole php --ri swoole
docker run --rm phpswoole/swoole composer --version
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
    * **00-autoreload**: Restart the Swoole web server automatically if file changes detected under web root.
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
    * **33-debug-with-blackfire**: Please check the [README](https://github.com/swoole/docker-swoole/tree/master/examples/33-debug-with-blackfire) file included to see how to debug your PHP code with [Blackfire](https://blackfire.io). When debugging Swoole applications, we recommend to use _yasd_ instead of _Blackfire_, _sdebug_, or _Xdebug_.
    * **34-debug-with-sdebug**: Please check the [README](https://github.com/swoole/docker-swoole/tree/master/examples/34-debug-with-sdebug) file included to see how to debug your PHP code in different ways with [sdebug](https://github.com/swoole/sdebug) (forked from Xdebug to work with Swoole). When debugging Swoole applications, we recommend to use _yasd_ instead of _Blackfire_, _sdebug_, or _Xdebug_.
    * **35-debug-with-yasd**: Please check the [README](https://github.com/swoole/docker-swoole/tree/master/examples/35-debug-with-yasd) file included to see how to debug a Swoole-based web server using [yasd](https://github.com/swoole/yasd) (Yet Another Swoole Debugger) in _Phpstorm_.

# Image Variants

The `phpswoole/swoole` images come in three flavors, each designed for a specific use case. **In production environment, we suggest using the Alpine images.**

## 1. `latest`, `<swoole-version>`, and `<swoole-version>-php<php-version>`

* `phpswoole/swoole:latest`
* `phpswoole/swoole:5.0`
* `phpswoole/swoole:5.0-php8.1`
* `phpswoole/swoole:5.0.1-php8.1`

This variant is based on the _php:cli_ images, with a few changes. It uses _Supervisord_ to manage booting processes, and has _Composer_ preinstalled.

## 2. `latest-dev`, `<swoole-version>-dev`, and `<swoole-version>-php<php-version>-dev`

* `phpswoole/swoole:latest-dev`
* `phpswoole/swoole:5.0-dev`
* `phpswoole/swoole:5.0-php8.1-dev`
* `phpswoole/swoole:5.0.1-php8.1-dev`

This variant is very similar to the previous one, but it has extra tools added for testing, debugging, and monitoring purpose,
including [gdb](https://www.gnu.org/s/gdb), git, lsof, [strace](https://strace.io), [tcpdump](https://www.tcpdump.org), [Valgrind](http://www.valgrind.org), and vim.

## 3. `latest-alpine`, `<swoole-version>-alpine`, and `<swoole-version>-php<php-version>-alpine`

* `phpswoole/swoole:latest-alpine`
* `phpswoole/swoole:5.0-alpine`
* `phpswoole/swoole:5.0-php8.1-alpine`
* `phpswoole/swoole:5.0.1-php8.1-alpine`

You can use this variant in the same way as using the _php:alpine_ image, except that we changed the default working directory to _/var/www_.
Also, we have _Composer_ preinstalled in the image.

Note: We don't have development tools built in for Alpine images. There is no Docker images like `phpswoole/swoole:5.0.1-php8.1-alpine-dev`.

# Supported Tags and Respective `Dockerfile` Links

## Versioned images (based on stable releases of Swoole)

### Swoole 5.0

| PHP Versions | Default Images | Dev Images | Alpine Images |
|-|-|-|-|
| PHP 8.2 | [5.0.1-php8.2, 5.0-php8.2](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/5.0.1/php8.2/cli/Dockerfile) | [5.0.1-php8.2-dev, 5.0-php8.2-dev](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/5.0.1/php8.2/cli/Dockerfile) | [5.0.1-php8.2-alpine, 5.0-php8.2-alpine](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/5.0.1/php8.2/alpine/Dockerfile) |
| PHP 8.1 | [5.0.1-php8.1, 5.0-php8.1,<br />5.0, latest](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/5.0.1/php8.1/cli/Dockerfile) | [5.0.1-php8.1-dev, 5.0-php8.1-dev,<br />5.0-dev, latest-dev](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/5.0.1/php8.1/cli/Dockerfile) | [5.0.1-php8.1-alpine, 5.0-php8.1-alpine,<br />5.0-alpine, latest-alpine](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/5.0.1/php8.1/alpine/Dockerfile) |
| PHP 8.0 | [5.0.1-php8.0, 5.0-php8.0](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/5.0.1/php8.0/cli/Dockerfile) | [5.0.1-php8.0-dev, 5.0-php8.0-dev](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/5.0.1/php8.0/cli/Dockerfile) | [5.0.1-php8.0-alpine, 5.0-php8.0-alpine](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/5.0.1/php8.0/alpine/Dockerfile) |

### Swoole 4.8

| PHP Versions | Default Images | Dev Images | Alpine Images |
|-|-|-|-|
| PHP 8.2 | [4.8.12-php8.2, 4.8-php8.2](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/4.8.12/php8.2/cli/Dockerfile) | [4.8.12-php8.2-dev, 4.8-php8.2-dev](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/4.8.12/php8.2/cli/Dockerfile) | [4.8.12-php8.2-alpine, 4.8-php8.2-alpine](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/4.8.12/php8.2/alpine/Dockerfile) |
| PHP 8.1 | [4.8.12-php8.1, 4.8-php8.1](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/4.8.12/php8.1/cli/Dockerfile) | [4.8.12-php8.1-dev, 4.8-php8.1-dev](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/4.8.12/php8.1/cli/Dockerfile) | [4.8.12-php8.1-alpine, 4.8-php8.1-alpine](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/4.8.12/php8.1/alpine/Dockerfile) |
| PHP 8.0 | [4.8.12-php8.0, 4.8-php8.0,<br />4.8](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/4.8.12/php8.0/cli/Dockerfile) | [4.8.12-php8.0-dev, 4.8-php8.0-dev,<br />4.8-dev](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/4.8.12/php8.0/cli/Dockerfile) | [4.8.12-php8.0-alpine, 4.8-php8.0-alpine,<br />4.8-alpine](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/4.8.12/php8.0/alpine/Dockerfile) |
| PHP 7.4 | [4.8.12-php7.4, 4.8-php7.4](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/4.8.12/php7.4/cli/Dockerfile) | [4.8.12-php7.4-dev, 4.8-php7.4-dev](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/4.8.12/php7.4/cli/Dockerfile) | [4.8.12-php7.4-alpine, 4.8-php7.4-alpine](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/4.8.12/php7.4/alpine/Dockerfile) |
| PHP 7.3 | [4.8.12-php7.3, 4.8-php7.3](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/4.8.12/php7.3/cli/Dockerfile) | [4.8.12-php7.3-dev, 4.8-php7.3-dev](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/4.8.12/php7.3/cli/Dockerfile) | [4.8.12-php7.3-alpine, 4.8-php7.3-alpine](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/4.8.12/php7.3/alpine/Dockerfile) |
| PHP 7.2 | [4.8.12-php7.2, 4.8-php7.2](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/4.8.12/php7.2/cli/Dockerfile) | [4.8.12-php7.2-dev, 4.8-php7.2-dev](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/4.8.12/php7.2/cli/Dockerfile) | [4.8.12-php7.2-alpine, 4.8-php7.2-alpine](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/4.8.12/php7.2/alpine/Dockerfile) |

### Swoole 4.7

| PHP Versions | Default Images | Dev Images | Alpine Images |
|-|-|-|-|
| PHP 8.0 | [4.7.1-php8.0, 4.7-php8.0,<br />4.7](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/4.7.1/php8.0/cli/Dockerfile) | [4.7.1-php8.0-dev, 4.7-php8.0-dev,<br />4.7-dev](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/4.7.1/php8.0/cli/Dockerfile) | [4.7.1-php8.0-alpine, 4.7-php8.0-alpine,<br />4.7-alpine](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/4.7.1/php8.0/alpine/Dockerfile) |
| PHP 7.4 | [4.7.1-php7.4, 4.7-php7.4](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/4.7.1/php7.4/cli/Dockerfile) | [4.7.1-php7.4-dev, 4.7-php7.4-dev](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/4.7.1/php7.4/cli/Dockerfile) | [4.7.1-php7.4-alpine, 4.7-php7.4-alpine](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/4.7.1/php7.4/alpine/Dockerfile) |
| PHP 7.3 | [4.7.1-php7.3, 4.7-php7.3](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/4.7.1/php7.3/cli/Dockerfile) | [4.7.1-php7.3-dev, 4.7-php7.3-dev](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/4.7.1/php7.3/cli/Dockerfile) | [4.7.1-php7.3-alpine, 4.7-php7.3-alpine](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/4.7.1/php7.3/alpine/Dockerfile) |
| PHP 7.2 | [4.7.1-php7.2, 4.7-php7.2](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/4.7.1/php7.2/cli/Dockerfile) | [4.7.1-php7.2-dev, 4.7-php7.2-dev](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/4.7.1/php7.2/cli/Dockerfile) | [4.7.1-php7.2-alpine, 4.7-php7.2-alpine](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/4.7.1/php7.2/alpine/Dockerfile) |

### Swoole 4.6

| PHP Versions | Default Images | Dev Images | Alpine Images |
|-|-|-|-|
| PHP 8.0 | [4.6.7-php8.0, 4.6-php8.0,<br />4.6](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/4.6.7/php8.0/cli/Dockerfile) | [4.6.7-php8.0-dev, 4.6-php8.0-dev,<br />4.6-dev](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/4.6.7/php8.0/cli/Dockerfile) | [4.6.7-php8.0-alpine, 4.6-php8.0-alpine,<br />4.6-alpine](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/4.6.7/php8.0/alpine/Dockerfile) |
| PHP 7.4 | [4.6.7-php7.4, 4.6-php7.4](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/4.6.7/php7.4/cli/Dockerfile) | [4.6.7-php7.4-dev, 4.6-php7.4-dev](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/4.6.7/php7.4/cli/Dockerfile) | [4.6.7-php7.4-alpine, 4.6-php7.4-alpine](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/4.6.7/php7.4/alpine/Dockerfile) |
| PHP 7.3 | [4.6.7-php7.3, 4.6-php7.3](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/4.6.7/php7.3/cli/Dockerfile) | [4.6.7-php7.3-dev, 4.6-php7.3-dev](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/4.6.7/php7.3/cli/Dockerfile) | [4.6.7-php7.3-alpine, 4.6-php7.3-alpine](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/4.6.7/php7.3/alpine/Dockerfile) |
| PHP 7.2 | [4.6.7-php7.2, 4.6-php7.2](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/4.6.7/php7.2/cli/Dockerfile) | [4.6.7-php7.2-dev, 4.6-php7.2-dev](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/4.6.7/php7.2/cli/Dockerfile) | [4.6.7-php7.2-alpine, 4.6-php7.2-alpine](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/4.6.7/php7.2/alpine/Dockerfile) |

### Swoole 4.5

| PHP Versions | Default Images | Dev Images | Alpine Images |
|-|-|-|-|
| PHP 8.0 | [4.5.11-php8.0, 4.5-php8.0,<br />4.5](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/4.5.11/php8.0/cli/Dockerfile) | [4.5.11-php8.0-dev, 4.5-php8.0-dev,<br />4.5-dev](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/4.5.11/php8.0/cli/Dockerfile) | [4.5.11-php8.0-alpine, 4.5-php8.0-alpine,<br />4.5-alpine](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/4.5.11/php8.0/alpine/Dockerfile) |
| PHP 7.4 | [4.5.11-php7.4, 4.5-php7.4](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/4.5.11/php7.4/cli/Dockerfile) | [4.5.11-php7.4-dev, 4.5-php7.4-dev](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/4.5.11/php7.4/cli/Dockerfile) | [4.5.11-php7.4-alpine, 4.5-php7.4-alpine](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/4.5.11/php7.4/alpine/Dockerfile) |
| PHP 7.3 | [4.5.11-php7.3, 4.5-php7.3](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/4.5.11/php7.3/cli/Dockerfile) | [4.5.11-php7.3-dev, 4.5-php7.3-dev](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/4.5.11/php7.3/cli/Dockerfile) | [4.5.11-php7.3-alpine, 4.5-php7.3-alpine](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/4.5.11/php7.3/alpine/Dockerfile) |
| PHP 7.2 | [4.5.11-php7.2, 4.5-php7.2](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/4.5.11/php7.2/cli/Dockerfile) | [4.5.11-php7.2-dev, 4.5-php7.2-dev](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/4.5.11/php7.2/cli/Dockerfile) | [4.5.11-php7.2-alpine, 4.5-php7.2-alpine](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/4.5.11/php7.2/alpine/Dockerfile) |
| PHP 7.1 | [4.5.11-php7.1, 4.5-php7.1](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/4.5.11/php7.1/cli/Dockerfile) | [4.5.11-php7.1-dev, 4.5-php7.1-dev](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/4.5.11/php7.1/cli/Dockerfile) | [4.5.11-php7.1-alpine, 4.5-php7.1-alpine](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/4.5.11/php7.1/alpine/Dockerfile) |

### Swoole 4.4

| PHP Versions | Default Images | Dev Images | Alpine Images |
|-|-|-|-|
| PHP 7.4 | [4.4.25-php7.4, 4.4-php7.4,<br />4.4](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/4.4.25/php7.4/cli/Dockerfile) | [4.4.25-php7.4-dev, 4.4-php7.4-dev,<br />4.4-dev](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/4.4.25/php7.4/cli/Dockerfile) | [4.4.25-php7.4-alpine, 4.4-php7.4-alpine,<br />4.4-alpine](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/4.4.25/php7.4/alpine/Dockerfile) |
| PHP 7.3 | [4.4.25-php7.3, 4.4-php7.3](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/4.4.25/php7.3/cli/Dockerfile) | [4.4.25-php7.3-dev, 4.4-php7.3-dev](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/4.4.25/php7.3/cli/Dockerfile) | [4.4.25-php7.3-alpine, 4.4-php7.3-alpine](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/4.4.25/php7.3/alpine/Dockerfile) |
| PHP 7.2 | [4.4.25-php7.2, 4.4-php7.2](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/4.4.25/php7.2/cli/Dockerfile) | [4.4.25-php7.2-dev, 4.4-php7.2-dev](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/4.4.25/php7.2/cli/Dockerfile) | [4.4.25-php7.2-alpine, 4.4-php7.2-alpine](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/4.4.25/php7.2/alpine/Dockerfile) |
| PHP 7.1 | [4.4.25-php7.1, 4.4-php7.1](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/4.4.25/php7.1/cli/Dockerfile) | [4.4.25-php7.1-dev, 4.4-php7.1-dev](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/4.4.25/php7.1/cli/Dockerfile) | [4.4.25-php7.1-alpine, 4.4-php7.1-alpine](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/4.4.25/php7.1/alpine/Dockerfile) |

## Nightly images (built daily using the master branch of [swoole-src](https://github.com/swoole/swoole-src))

| PHP Versions | Default Images | Dev Images | Alpine Images |
|-|-|-|-|
| PHP 8.2 | [php8.2](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/nightly/php8.2/cli/Dockerfile) | [php8.2-dev](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/nightly/php8.2/cli/Dockerfile) | [php8.2-alpine](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/nightly/php8.2/alpine/Dockerfile) |
| PHP 8.1 | [php8.1](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/nightly/php8.1/cli/Dockerfile) | [php8.1-dev](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/nightly/php8.1/cli/Dockerfile) | [php8.1-alpine](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/nightly/php8.1/alpine/Dockerfile) |
| PHP 8.0 | [php8.0](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/nightly/php8.0/cli/Dockerfile) | [php8.0-dev](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/nightly/php8.0/cli/Dockerfile) | [php8.0-alpine](https://github.com/swoole/docker-swoole/blob/master/dockerfiles/nightly/php8.0/alpine/Dockerfile) |

# Build Images Manually

The Docker images are built and pushed out automatically through Travis. If you want to build some image manually, please
follow these three steps.

**1**. Install Composer packages. If you have command "composer" installed already, just run `composer update -n`.

**2**. Use commands like following to create dockerfiles:

```bash
./bin/generate-dockerfiles.php nightly # Generate dockerfiles to build images from the master branch of Swoole.
./bin/generate-dockerfiles.php 5.0.1   # Generate dockerfiles to build images for Swoole 5.0.1.
```

**3**. Build Docker images with commands like:

```bash
docker build -t phpswoole/swoole                     -f dockerfiles/latest/php8.1/cli/Dockerfile   .
docker build -t phpswoole/swoole:5.0.1-php8.1        -f dockerfiles/5.0.1/php8.1/cli/Dockerfile    .
docker build -t phpswoole/swoole:5.0.1-php8.1-alpine -f dockerfiles/5.0.1/php8.1/alpine/Dockerfile .
```

To build development images (where extra tools are included), add an argument _DEV_MODE_:

```bash
docker build --build-arg DEV_MODE=true -t phpswoole/swoole:latest-dev       -f dockerfiles/latest/php8.1/cli/Dockerfile .
docker build --build-arg DEV_MODE=true -t phpswoole/swoole:5.0.1-php8.1-dev -f dockerfiles/5.0.1/php8.1/cli/Dockerfile  .
```

# Credits

Current implementation borrows ideas from [Demin](https://github.com/deminy)'s work at [Glu Mobile](https://glu.com).
