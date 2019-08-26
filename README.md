[![Build Status](https://travis-ci.org/swoole/docker-swoole.svg?branch=master)](https://travis-ci.org/swoole/docker-swoole)
[![License](https://img.shields.io/badge/License-Apache%202.0-blue.svg)](https://github.com/swoole/docker-swoole/blob/master/LICENSE)

# Docker Image for Swoole

This image is built for general-purpose, including production deployment. We have different examples included in this
Git repository to help developers to get familiar with the image and _Swoole_.

You may get the image from [here](https://hub.docker.com/r/phpswoole/swoole).

# Feature List

* Support auto-reloading for local development.
* Built-in scripts to manage _Swoole_ extensions and _Supervisord_ programs.
* Easy to manage booting scripts in Docker.
* Allow running PHP scripts and other commands directly in different environments (including ECS).
* Use one root filesystem for simplicity (one Docker `COPY` command only in dockerfiles).
* _Composer_ included.
* Built for different architectures (for now only amd64 and arm64v8 images are built).

# Examples

You may use the image to serve an HTTP/WebSocket server, or run some one-off command with it. e.g.,

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
* Manage PHP extensions and configurations:
    * **10-install-php-extension**: how to install/enable PHP extensions.
    * **11-customize-extension-options**: how to overwrite/customize PHP extension options.
    * **12-php.ini**: how to overwrite/customize PHP options.
* Manage Supervisord programs:
    * **20-supervisord-services**: to show how to run Supervisord program(s) in Docker.
    * **21-supervisord-tasks**: to show how to run Supervisord program(s) when launching a one-off command with Docker. Please check the [README](examples/21-supervisord-tasks/README.md) file included to see how to run the example.
    * **22-supervisord-enable-program**: to show how to enable program(s) in Supervisord program.
    * **23-supervisord-disable-program**: to show how to disable Supervisord program(s).

# TODOs

* Add more examples.
* Allow to stop the container gracefully.
* Support more architectures.
* Add Alpine image if needed.

# Credits

Current implementation borrows ideas from [Demin](https://deminy.in)'s work at [Glu Mobile](https://glu.com).
