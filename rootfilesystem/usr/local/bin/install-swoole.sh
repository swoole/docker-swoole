#!/usr/bin/env bash
# This script is used to install Swoole in the official Swoole image.
#
# How to use this script?
#     ./install-swoole.sh [SWOOLE_VERSION] [Swoole installation options]
# For example,
#     ./install-swoole.sh
#     ./install-swoole.sh master
#     ./install-swoole.sh 4.5.10 --enable-http2 --enable-mysqlnd --enable-openssl --enable-sockets --enable-swoole-curl --enable-swoole-json
#
# The first parameter (SWOOLE_VERSION) should be a branch name, a tag or a Git commit number. For example,
#     master                                   # To install Swoole with latest code from branch "master".
#     b8a876a4b3f285c9682dabd80ae1aa15932050f9 # To install Swoole with code from a Git commit.
#     4.5.10                                   # To install Swoole 4.5.10.
#
# You can specify other predefined variables if needed. For example, on macOS Mojave you may need to specify LDFLAGS,
# CFLAGS and CPPFLAGS like following:
#
#     LDFLAGS="-L/usr/local/opt/openssl/lib -L/usr/local/lib -L/usr/local/opt/expat/lib"               \
#     CFLAGS="-I/usr/local/opt/openssl/include/ -I/usr/local/include -I/usr/local/opt/expat/include"   \
#     CPPFLAGS="-I/usr/local/opt/openssl/include/ -I/usr/local/include -I/usr/local/opt/expat/include" \
#     ./install-swoole.sh 4.5.10 --enable-http2 --enable-mysqlnd --enable-openssl --enable-sockets --enable-swoole-curl --enable-swoole-json
#
# Before using this script, you should have PHP extension sockets installed, and have packages like openssl installed
# already.

set -ex

[[ -z "${SWOOLE_FUNCTIONS_LOADED}" ]] && . functions.sh

if [[ ! -z ${1} ]] ; then
    SWOOLE_VERSION=$1
    shift 1 # Remove Swoole version # out from command line arguments.
else
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
install swoole-src "${SWOOLE_VERSION}" "$@" ${DEV_OPTIONS}
if hash docker-php-ext-enable 2>/dev/null ; then
    docker-php-ext-enable swoole
else
    echo NOTICE: PHP extension swoole is not enabled. Please have it enabled first.
fi

cleanupSwoole
