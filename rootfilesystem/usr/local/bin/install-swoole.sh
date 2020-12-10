#!/usr/bin/env bash
# This script is used to install Swoole in the official Swoole image.
#
# You may also use the script locally. To do that, set environment variable "SWOOLE_VERSION" when running the script.
# The variable value should be a branch name, a tag or a Git commit number. For example,
#
#     SWOOLE_VERSION=master                                   # To install Swoole with latest code from branch "master".
#     SWOOLE_VERSION=b8a876a4b3f285c9682dabd80ae1aa15932050f9 # To install Swoole with code from a Git commit.
#     SWOOLE_VERSION=4.4.14                                   # To install Swoole 4.4.14.
#
# Here is an example command to install Swoole:
#
#     SWOOLE_VERSION=4.4.14 \
#     bash <(curl -s https://raw.githubusercontent.com/swoole/docker-swoole/master/rootfilesystem/usr/local/bin/install-swoole.sh)
#
# Here is another example:
#
#     SWOOLE_VERSION=b8a876a4b3f285c9682dabd80ae1aa15932050f9 \
#     bash <(curl -s https://raw.githubusercontent.com/swoole/docker-swoole/master/rootfilesystem/usr/local/bin/install-swoole.sh)
#
# You can specify other predefined variables if needed. For example, on macOS Mojave you may need to specify LDFLAGS,
# CFLAGS and CPPFLAGS like following:
#
#     SWOOLE_VERSION=4.4.14                                                                            \
#     LDFLAGS="-L/usr/local/opt/openssl/lib -L/usr/local/lib -L/usr/local/opt/expat/lib"               \
#     CFLAGS="-I/usr/local/opt/openssl/include/ -I/usr/local/include -I/usr/local/opt/expat/include"   \
#     CPPFLAGS="-I/usr/local/opt/openssl/include/ -I/usr/local/include -I/usr/local/opt/expat/include" \
#     bash <(curl -s https://raw.githubusercontent.com/swoole/docker-swoole/master/rootfilesystem/usr/local/bin/install-swoole.sh)
#
# Before using this script, you should have PHP extension sockets installed, and have packages like openssl installed
# already.

set -ex

[[ -z "${SWOOLE_FUNCTIONS_LOADED}" ]] && . functions.sh

if [[ ! -z ${1} ]] ; then
    SWOOLE_VERSION=$1
fi
if [[ -z ${SWOOLE_VERSION} ]] ; then
    SWOOLE_VERSION=master
fi
export SWOOLE_VERSION=$SWOOLE_VERSION

# Get PHP extension sockets installed if needed.
if ! php -m | grep -q sockets ; then
    if hash docker-php-ext-install 2>/dev/null ; then
        docker-php-ext-install sockets
    else
        echo Error: PHP extension sockets not installed. Please have it installed first.
        exit 1
    fi
fi

if [[ "true" = "${DEV_MODE}" ]] ; then
    apt-get install -y gdb git lsof strace tcpdump valgrind vim --no-install-recommends
    DEV_OPTIONS="--enable-debug --enable-debug-log --enable-trace-log"
else
    DEV_OPTIONS=""
fi
install swoole-src "${SWOOLE_VERSION}" --enable-http2 --enable-mysqlnd --enable-openssl --enable-sockets --enable-swoole-json --enable-swoole-curl ${DEV_OPTIONS}
if hash docker-php-ext-enable 2>/dev/null ; then
    docker-php-ext-enable swoole
else
    echo NOTICE: PHP extension swoole is not enabled. Please have it enabled first.
fi

cleanupSwoole
