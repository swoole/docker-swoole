#!/usr/bin/env bash
#
# Download, build, install and enable Swoole from source code, e.g., to rebuild Swoole with different configure options
# in an image derived from the official Swoole images.
#
# Usage:
#     install-swoole.sh [SWOOLE_VERSION] [configure options of Swoole]
#
# SWOOLE_VERSION is a branch name ("master" by default), a tag (e.g., "6.2.3" or "v6.2.3"), or a full Git commit hash.
# For example,
#     install-swoole.sh
#     install-swoole.sh master
#     install-swoole.sh 6.2.3 --enable-mysqlnd --enable-sockets --enable-swoole-curl
#     install-swoole.sh b8a876a4b3f285c9682dabd80ae1aa15932050f9 --enable-mysqlnd --enable-sockets
#
# Configure options not recognized by the version of Swoole being built fail the build. For the options of a version,
# please check file config.m4 in the source code of Swoole.
#
# When environment variable DEV_MODE is set to "true", debugging tools (gdb, strace, valgrind, etc.) are installed too,
# Swoole is built with debug and trace logging enabled, and its source code is kept in folder /usr/src/swoole-src.
#
# PHP extension sockets is installed first if needed. The libraries that Swoole is built against (e.g., OpenSSL for
# Swoole 6.2+, or libcurl for option --enable-swoole-curl) must be installed already, with their headers.
#

set -ex

[[ -z "${SWOOLE_FUNCTIONS_LOADED}" ]] && . functions.sh

SWOOLE_VERSION="${1:-master}"
if [[ $# -gt 0 ]] ; then
    shift 1 # Remove the Swoole version from the command line arguments.
fi
export SWOOLE_VERSION

# Get PHP extension sockets installed if needed.
if ! php -m | grep -q sockets ; then
    if hash docker-php-ext-install 2>/dev/null ; then
        docker-php-ext-install sockets
    else
        echo Error: PHP extension sockets not installed. Please have it installed first.
        exit 1
    fi
fi

DEV_OPTIONS=()
if [[ "true" = "${DEV_MODE}" ]] ; then
    apt-get update
    apt-get install -y gdb git lsof strace tcpdump valgrind vim --no-install-recommends
    DEV_OPTIONS=(--enable-debug-log --enable-trace-log)
fi

download "${SWOOLE_VERSION}"
build "$@" "${DEV_OPTIONS[@]}"
if hash docker-php-ext-enable 2>/dev/null ; then
    docker-php-ext-enable swoole
else
    echo NOTICE: PHP extension swoole is not enabled. Please have it enabled first.
fi

cleanupSwoole
