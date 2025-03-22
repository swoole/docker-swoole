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
      * [Swoole 6.0](#swoole-60)
      * [Swoole 5.1](#swoole-51)
      * [Swoole 5.0](#swoole-50)
      * [Swoole 4.8](#swoole-48)
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
    * **[24-supervisord-gracefully-shutdown](examples/24-supervisord-gracefully-shutdown)**: how to gracefully stop Swoole servers (managed by `supervisord`) in Docker containers.
* Debugging:
    * **30-debug-with-gdb**: Please check the [README](https://github.com/swoole/docker-swoole/tree/master/examples/30-debug-with-gdb) file included to see how to debug your PHP code with _gdb_.
    * **31-debug-with-valgrind**: Please check the [README](https://github.com/swoole/docker-swoole/tree/master/examples/31-debug-with-valgrind) file included to see how to debug your PHP code with _Valgrind_.
    * **32-debug-with-strace**: Please check the [README](https://github.com/swoole/docker-swoole/tree/master/examples/32-debug-with-strace) file included to see how to debug your PHP code with _strace_.
    * **33-debug-with-blackfire**: Please check the [README](https://github.com/swoole/docker-swoole/tree/master/examples/33-debug-with-blackfire) file included to see how to debug your PHP code with [Blackfire](https://blackfire.io).
    * **34-debug-with-xdebug**: Please check the [README](https://github.com/swoole/docker-swoole/tree/master/examples/34-debug-with-xdebug) file included to see how to debug your PHP code using [Xdebug](https://xdebug.org). Please note that Xdebug 3.1.0+ works with Swoole 5.0.2+ on PHP 8.1+ only. Lower versions of Xdebug don't work with Swoole.

# Image Variants

The `phpswoole/swoole` images come in three flavors, each designed for a specific use case.

## 1. `latest`, `<swoole-version>`, and `<swoole-version>-php<php-version>`

* `phpswoole/swoole:latest`
* `phpswoole/swoole:5.1`
* `phpswoole/swoole:5.1-php8.2`
* `phpswoole/swoole:5.1.7-php8.2`

This variant is based on the _php:cli_ images, with a few changes. It uses _Supervisord_ to manage booting processes, and has _Composer_ preinstalled.

Since Swoole 6.0.0, there is a Zend Thread Safety (ZTS) version of the image available, which is suffixed with `-zts`. e.g., `phpswoole/swoole:6.0-php8.3-zts`.

## 2. `latest-dev`, `<swoole-version>-dev`, and `<swoole-version>-php<php-version>-dev`

* `phpswoole/swoole:latest-dev`
* `phpswoole/swoole:5.1-dev`
* `phpswoole/swoole:5.1-php8.2-dev`
* `phpswoole/swoole:5.1.7-php8.2-dev`

This variant is very similar to the previous one, but it has extra tools added for testing, debugging, and monitoring purpose,
including [gdb](https://www.gnu.org/s/gdb), git, lsof, [strace](https://strace.io), [tcpdump](https://www.tcpdump.org), [Valgrind](http://www.valgrind.org), and vim.

Since Swoole 6.0.0, there is a Zend Thread Safety (ZTS) version of the image available, which is suffixed with `-zts-dev`. e.g., `phpswoole/swoole:6.0-php8.3-zts-dev`.

## 3. `latest-alpine`, `<swoole-version>-alpine`, and `<swoole-version>-php<php-version>-alpine`

* `phpswoole/swoole:latest-alpine`
* `phpswoole/swoole:5.1-alpine`
* `phpswoole/swoole:5.1-php8.2-alpine`
* `phpswoole/swoole:5.1.7-php8.2-alpine`

You can use this variant in the same way as using the _php:alpine_ image, except that we changed the default working directory to _/var/www_.
Also, we have _Composer_ preinstalled in the image.

Note: We don't have development tools built in for Alpine images. There is no Docker images like `phpswoole/swoole:5.1.7-php8.2-alpine-dev`.

# Supported Tags and Respective `Dockerfile` Links

## Versioned images (based on stable releases of Swoole)

### Swoole 6.0

| PHP Versions | Default Images | Dev Images | ZTS Images | Alpine Images |
|-|-|-|-|-|
| PHP 8.4 | [6.0.2-php8.4][6.0-php8.4]<br />[6.0-php8.4] | [6.0.2-php8.4-dev][6.0-php8.4]<br />[6.0-php8.4-dev][6.0-php8.4] | [6.0.2-php8.4-zts][6.0-php8.4-zts]<br />[6.0-php8.4-zts] | [6.0.2-php8.4-alpine][6.0-php8.4-alpine]<br />[6.0-php8.4-alpine] |
| PHP 8.3 | [6.0.2-php8.3][6.0-php8.3]<br />[6.0-php8.3]<br />[6.0][6.0-php8.3] | [6.0.2-php8.3-dev][6.0-php8.3]<br />[6.0-php8.3-dev][6.0-php8.3]<br />[6.0-dev][6.0-php8.3] | [6.0.2-php8.3-zts][6.0-php8.3-zts]<br />[6.0-php8.3-zts]<br />[6.0-zts][6.0-php8.3-zts] | [6.0.2-php8.3-alpine][6.0-php8.3-alpine]<br />[6.0-php8.3-alpine]<br />[6.0-alpine][6.0-php8.3-alpine] |
| PHP 8.2 | [6.0.2-php8.2][6.0-php8.2]<br />[6.0-php8.2] | [6.0.2-php8.2-dev][6.0-php8.2]<br />[6.0-php8.2-dev][6.0-php8.2] | [6.0.2-php8.2-zts][6.0-php8.2-zts]<br />[6.0-php8.2-zts] | [6.0.2-php8.2-alpine][6.0-php8.2-alpine]<br />[6.0-php8.2-alpine] |
| PHP 8.1 | [6.0.2-php8.1][6.0-php8.1]<br />[6.0-php8.1] | [6.0.2-php8.1-dev][6.0-php8.1]<br />[6.0-php8.1-dev][6.0-php8.1] | [6.0.2-php8.1-zts][6.0-php8.1-zts]<br />[6.0-php8.1-zts] | [6.0.2-php8.1-alpine][6.0-php8.1-alpine]<br />[6.0-php8.1-alpine] |

### Swoole 5.1

| PHP Versions | Default Images | Dev Images | Alpine Images |
|-|-|-|-|
| PHP 8.3 | [5.1.7-php8.3][5.1-php8.3]<br />[5.1-php8.3]<br />[5.1][5.1-php8.3]<br />[latest][5.1-php8.3] | [5.1.7-php8.3-dev][5.1-php8.3]<br />[5.1-php8.3-dev][5.1-php8.3]<br />[5.1-dev][5.1-php8.3]<br />[latest-dev][5.1-php8.3] | [5.1.7-php8.3-alpine][5.1-php8.3-alpine]<br />[5.1-php8.3-alpine]<br />[5.1-alpine][5.1-php8.3-alpine]<br />[latest-alpine][5.1-php8.3-alpine] |
| PHP 8.2 | [5.1.7-php8.2][5.1-php8.2]<br />[5.1-php8.2] | [5.1.7-php8.2-dev][5.1-php8.2]<br />[5.1-php8.2-dev][5.1-php8.2] | [5.1.7-php8.2-alpine][5.1-php8.2-alpine]<br />[5.1-php8.2-alpine] |
| PHP 8.1 | [5.1.7-php8.1][5.1-php8.1]<br />[5.1-php8.1] | [5.1.7-php8.1-dev][5.1-php8.1]<br />[5.1-php8.1-dev][5.1-php8.1] | [5.1.7-php8.1-alpine][5.1-php8.1-alpine]<br />[5.1-php8.1-alpine] |
| PHP 8.0 | [5.1.7-php8.0][5.1-php8.0]<br />[5.1-php8.0] | [5.1.7-php8.0-dev][5.1-php8.0]<br />[5.1-php8.0-dev][5.1-php8.0] | [5.1.7-php8.0-alpine][5.1-php8.0-alpine]<br />[5.1-php8.0-alpine] |

### Swoole 5.0

| PHP Versions | Default Images | Dev Images | Alpine Images |
|-|-|-|-|
| PHP 8.2 | [5.0.3-php8.2][5.0-php8.2]<br />[5.0-php8.2] | [5.0.3-php8.2-dev][5.0-php8.2]<br />[5.0-php8.2-dev][5.0-php8.2] | [5.0.3-php8.2-alpine][5.0-php8.2-alpine]<br />[5.0-php8.2-alpine] |
| PHP 8.1 | [5.0.3-php8.1][5.0-php8.1]<br />[5.0-php8.1]<br />[5.0][5.0-php8.1] | [5.0.3-php8.1-dev][5.0-php8.1]<br />[5.0-php8.1-dev][5.0-php8.1]<br />[5.0-dev][5.0-php8.1] | [5.0.3-php8.1-alpine][5.0-php8.1-alpine]<br />[5.0-php8.1-alpine]<br />[5.0-alpine][5.0-php8.1-alpine] |
| PHP 8.0 | [5.0.3-php8.0][5.0-php8.0]<br />[5.0-php8.0] | [5.0.3-php8.0-dev][5.0-php8.0]<br />[5.0-php8.0-dev][5.0-php8.0]| [5.0.3-php8.0-alpine][5.0-php8.0-alpine]<br />[5.0-php8.0-alpine] |

### Swoole 4.8

| PHP Versions | Default Images | Dev Images | Alpine Images |
|-|-|-|-|
| PHP 8.2 | [4.8.13-php8.2][4.8-php8.2]<br />[4.8-php8.2] | [4.8.13-php8.2-dev][4.8-php8.2]<br />[4.8-php8.2-dev][4.8-php8.2] | [4.8.13-php8.2-alpine][4.8-php8.2-alpine]<br />[4.8-php8.2-alpine] |
| PHP 8.1 | [4.8.13-php8.1][4.8-php8.1]<br />[4.8-php8.1] | [4.8.13-php8.1-dev][4.8-php8.1]<br />[4.8-php8.1-dev][4.8-php8.1] | [4.8.13-php8.1-alpine][4.8-php8.1-alpine]<br />[4.8-php8.1-alpine] |
| PHP 8.0 | [4.8.13-php8.0][4.8-php8.0]<br />[4.8-php8.0]<br />[4.8][4.8-php8.0] | [4.8.13-php8.0-dev][4.8-php8.0]<br />[4.8-php8.0-dev][4.8-php8.0]<br />[4.8-dev][4.8-php8.0]| [4.8.13-php8.0-alpine][4.8-php8.0-alpine]<br />[4.8-php8.0-alpine]<br />[4.8-alpine][4.8-php8.0-alpine] |
| PHP 7.4 | [4.8.13-php7.4][4.8-php7.4]<br />[4.8-php7.4] | [4.8.13-php7.4-dev][4.8-php7.4]<br />[4.8-php7.4-dev][4.8-php7.4] | [4.8.13-php7.4-alpine][4.8-php7.4-alpine]<br />[4.8-php7.4-alpine] |
| PHP 7.3 | [4.8.13-php7.3][4.8-php7.3]<br />[4.8-php7.3] | [4.8.13-php7.3-dev][4.8-php7.3]<br />[4.8-php7.3-dev][4.8-php7.3] | [4.8.13-php7.3-alpine][4.8-php7.3-alpine]<br />[4.8-php7.3-alpine] |
| PHP 7.2 | [4.8.13-php7.2][4.8-php7.2]<br />[4.8-php7.2] | [4.8.13-php7.2-dev][4.8-php7.2]<br />[4.8-php7.2-dev][4.8-php7.2] | [4.8.13-php7.2-alpine][4.8-php7.2-alpine]<br />[4.8-php7.2-alpine] |

## Nightly images (built daily using the master branch of [swoole-src](https://github.com/swoole/swoole-src))

| PHP Versions | Default Images | Dev Images | ZTS Images| Alpine Images |
|-|-|-|-|-|
| PHP 8.4 | [php8.4][nightly-php8.4] | [php8.4-dev][nightly-php8.4] | [php8.4-zts][nightly-php8.4-zts] | [php8.4-alpine][nightly-php8.4-alpine] |
| PHP 8.3 | [php8.3][nightly-php8.3] | [php8.3-dev][nightly-php8.3] | [php8.3-zts][nightly-php8.3-zts] | [php8.3-alpine][nightly-php8.3-alpine] |
| PHP 8.2 | [php8.2][nightly-php8.2] | [php8.2-dev][nightly-php8.2] | [php8.2-zts][nightly-php8.2-zts] | [php8.2-alpine][nightly-php8.2-alpine] |
| PHP 8.1 | [php8.1][nightly-php8.1] | [php8.1-dev][nightly-php8.1] | [php8.1-zts][nightly-php8.1-zts] | [php8.1-alpine][nightly-php8.1-alpine] |

# Build Images Manually

The Docker images are built and pushed out automatically through Travis. If you want to build some image manually, please
follow these three steps.

**1**. Install Composer packages. If you have command "composer" installed already, just run `composer update -n`.

**2**. Use commands like following to create dockerfiles:

```bash
./bin/generate-dockerfiles.php nightly # Generate dockerfiles to build images from the master branch of Swoole.
./bin/generate-dockerfiles.php 5.1.7   # Generate dockerfiles to build images for Swoole 5.1.7.
```

**3**. Build Docker images with commands like:

```bash
docker build -t phpswoole/swoole:php8.2              -f dockerfiles/nightly/php8.2/cli/Dockerfile   .
docker build -t phpswoole/swoole:5.1.7-php8.2        -f dockerfiles/5.1.7/php8.2/cli/Dockerfile    .
docker build -t phpswoole/swoole:5.1.7-php8.2-alpine -f dockerfiles/5.1.7/php8.2/alpine/Dockerfile .
```

To build development images (where extra tools are included), add an argument _DEV_MODE_:

```bash
docker build --build-arg DEV_MODE=true -t phpswoole/swoole:php8.2-dev       -f dockerfiles/nightly/php8.2/cli/Dockerfile .
docker build --build-arg DEV_MODE=true -t phpswoole/swoole:5.1.7-php8.2-dev -f dockerfiles/5.1.7/php8.2/cli/Dockerfile  .
```

# Credits

* Current implementation borrows ideas from [Demin](https://github.com/deminy)'s work at [Glu Mobile](https://ea.com).
* Thanks to [Blackfire](https://blackfire.io) for providing free open-source subscription for their awesome profiling tool.

[6.0-php8.4]: https://github.com/swoole/docker-swoole/blob/master/dockerfiles/6.0.2/php8.4/cli/Dockerfile
[6.0-php8.4-zts]: https://github.com/swoole/docker-swoole/blob/master/dockerfiles/6.0.2/php8.4/zts/Dockerfile
[6.0-php8.4-alpine]: https://github.com/swoole/docker-swoole/blob/master/dockerfiles/6.0.2/php8.4/alpine/Dockerfile
[6.0-php8.3]: https://github.com/swoole/docker-swoole/blob/master/dockerfiles/6.0.2/php8.3/cli/Dockerfile
[6.0-php8.3-zts]: https://github.com/swoole/docker-swoole/blob/master/dockerfiles/6.0.2/php8.3/zts/Dockerfile
[6.0-php8.3-alpine]: https://github.com/swoole/docker-swoole/blob/master/dockerfiles/6.0.2/php8.3/alpine/Dockerfile
[6.0-php8.2]: https://github.com/swoole/docker-swoole/blob/master/dockerfiles/6.0.2/php8.2/cli/Dockerfile
[6.0-php8.2-zts]: https://github.com/swoole/docker-swoole/blob/master/dockerfiles/6.0.2/php8.2/zts/Dockerfile
[6.0-php8.2-alpine]: https://github.com/swoole/docker-swoole/blob/master/dockerfiles/6.0.2/php8.2/alpine/Dockerfile
[6.0-php8.1]: https://github.com/swoole/docker-swoole/blob/master/dockerfiles/6.0.2/php8.1/cli/Dockerfile
[6.0-php8.1-zts]: https://github.com/swoole/docker-swoole/blob/master/dockerfiles/6.0.2/php8.1/zts/Dockerfile
[6.0-php8.1-alpine]: https://github.com/swoole/docker-swoole/blob/master/dockerfiles/6.0.2/php8.1/alpine/Dockerfile
[5.1-php8.3]: https://github.com/swoole/docker-swoole/blob/5.1.7/dockerfiles/5.1.7/php8.3/cli/Dockerfile
[5.1-php8.3-alpine]: https://github.com/swoole/docker-swoole/blob/5.1.7/dockerfiles/5.1.7/php8.3/alpine/Dockerfile
[5.1-php8.2]: https://github.com/swoole/docker-swoole/blob/5.1.7/dockerfiles/5.1.7/php8.2/cli/Dockerfile
[5.1-php8.2-alpine]: https://github.com/swoole/docker-swoole/blob/5.1.7/dockerfiles/5.1.7/php8.2/alpine/Dockerfile
[5.1-php8.1]: https://github.com/swoole/docker-swoole/blob/5.1.7/dockerfiles/5.1.7/php8.1/cli/Dockerfile
[5.1-php8.1-alpine]: https://github.com/swoole/docker-swoole/blob/5.1.7/dockerfiles/5.1.7/php8.1/alpine/Dockerfile
[5.1-php8.0]: https://github.com/swoole/docker-swoole/blob/5.1.7/dockerfiles/5.1.7/php8.0/cli/Dockerfile
[5.1-php8.0-alpine]: https://github.com/swoole/docker-swoole/blob/5.1.7/dockerfiles/5.1.7/php8.0/alpine/Dockerfile
[5.0-php8.2]: https://github.com/swoole/docker-swoole/blob/5.0.3/dockerfiles/5.0.3/php8.2/cli/Dockerfile
[5.0-php8.2-alpine]: https://github.com/swoole/docker-swoole/blob/5.0.3/dockerfiles/5.0.3/php8.2/alpine/Dockerfile
[5.0-php8.1]: https://github.com/swoole/docker-swoole/blob/5.0.3/dockerfiles/5.0.3/php8.1/cli/Dockerfile
[5.0-php8.1-alpine]: https://github.com/swoole/docker-swoole/blob/5.0.3/dockerfiles/5.0.3/php8.1/alpine/Dockerfile
[5.0-php8.0]: https://github.com/swoole/docker-swoole/blob/5.0.3/dockerfiles/5.0.3/php8.0/cli/Dockerfile
[5.0-php8.0-alpine]: https://github.com/swoole/docker-swoole/blob/5.0.3/dockerfiles/5.0.3/php8.0/alpine/Dockerfile
[4.8-php8.2]: https://github.com/swoole/docker-swoole/blob/4.8.13/dockerfiles/4.8.13/php8.2/cli/Dockerfile
[4.8-php8.2-alpine]: https://github.com/swoole/docker-swoole/blob/4.8.13/dockerfiles/4.8.13/php8.2/alpine/Dockerfile
[4.8-php8.1]: https://github.com/swoole/docker-swoole/blob/4.8.13/dockerfiles/4.8.13/php8.1/cli/Dockerfile
[4.8-php8.1-alpine]: https://github.com/swoole/docker-swoole/blob/4.8.13/dockerfiles/4.8.13/php8.1/alpine/Dockerfile
[4.8-php8.0]: https://github.com/swoole/docker-swoole/blob/4.8.13/dockerfiles/4.8.13/php8.0/cli/Dockerfile
[4.8-php8.0-alpine]: https://github.com/swoole/docker-swoole/blob/4.8.13/dockerfiles/4.8.13/php8.0/alpine/Dockerfile
[4.8-php7.4]: https://github.com/swoole/docker-swoole/blob/4.8.13/dockerfiles/4.8.13/php7.4/cli/Dockerfile
[4.8-php7.4-alpine]: https://github.com/swoole/docker-swoole/blob/4.8.13/dockerfiles/4.8.13/php7.4/alpine/Dockerfile
[4.8-php7.3]: https://github.com/swoole/docker-swoole/blob/4.8.13/dockerfiles/4.8.13/php7.3/cli/Dockerfile
[4.8-php7.3-alpine]: https://github.com/swoole/docker-swoole/blob/4.8.13/dockerfiles/4.8.13/php7.3/alpine/Dockerfile
[4.8-php7.2]: https://github.com/swoole/docker-swoole/blob/4.8.13/dockerfiles/4.8.13/php7.2/cli/Dockerfile
[4.8-php7.2-alpine]: https://github.com/swoole/docker-swoole/blob/4.8.13/dockerfiles/4.8.13/php7.2/alpine/Dockerfile
[nightly-php8.4]: https://github.com/swoole/docker-swoole/blob/master/dockerfiles/nightly/php8.4/cli/Dockerfile
[nightly-php8.4-zts]: https://github.com/swoole/docker-swoole/blob/master/dockerfiles/nightly/php8.4/zts/Dockerfile
[nightly-php8.4-alpine]: https://github.com/swoole/docker-swoole/blob/master/dockerfiles/nightly/php8.4/alpine/Dockerfile
[nightly-php8.3]: https://github.com/swoole/docker-swoole/blob/master/dockerfiles/nightly/php8.3/cli/Dockerfile
[nightly-php8.3-zts]: https://github.com/swoole/docker-swoole/blob/master/dockerfiles/nightly/php8.3/zts/Dockerfile
[nightly-php8.3-alpine]: https://github.com/swoole/docker-swoole/blob/master/dockerfiles/nightly/php8.3/alpine/Dockerfile
[nightly-php8.2]: https://github.com/swoole/docker-swoole/blob/master/dockerfiles/nightly/php8.2/cli/Dockerfile
[nightly-php8.2-zts]: https://github.com/swoole/docker-swoole/blob/master/dockerfiles/nightly/php8.2/zts/Dockerfile
[nightly-php8.2-alpine]: https://github.com/swoole/docker-swoole/blob/master/dockerfiles/nightly/php8.2/alpine/Dockerfile
[nightly-php8.1]: https://github.com/swoole/docker-swoole/blob/master/dockerfiles/nightly/php8.1/cli/Dockerfile
[nightly-php8.1-zts]: https://github.com/swoole/docker-swoole/blob/master/dockerfiles/nightly/php8.1/zts/Dockerfile
[nightly-php8.1-alpine]: https://github.com/swoole/docker-swoole/blob/master/dockerfiles/nightly/php8.1/alpine/Dockerfile
