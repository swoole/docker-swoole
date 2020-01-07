#!/usr/bin/env bash
# How to run the script?
#     install-swoole-ext-zookeeper.sh master                                   # "master" is a branch name.
#     install-swoole-ext-zookeeper.sh 0.0.1                                    # "0.0.1" is a valid tag.
#     install-swoole-ext-zookeeper.sh 0e7c4be6ec36e856b1cb334ddb2b67d97af84d42 # a full Git commit number.
#
# NOTE：You can call bash function cleanupSwoole() to remove the source code directory manually after the installation.

set -ex

[[ -z "${SWOOLE_FUNCTIONS_LOADED}" ]] && . functions.sh

if [[ ! -x "${SWOOLE_SRC_DIR}/phpx/bin/phpx" ]] ; then
    # Here we shouldn't install PHP-X automatically since we want the developer to decide which version of PHP-X to be
    # used and installed.
    echo "Error: please install PHPX first before intalling a PHPX based extension."
    exit 1
fi

apt-get update
apt-get install -y      \
    libargon2-0-dev     \
    libcurl4-gnutls-dev \
    libedit-dev         \
    libncurses5-dev     \
    libsqlite3-dev      \
    libxml2-dev         \
    --no-install-recommends
rm -r /var/lib/apt/lists/*

installExtUsingPHPX zookeeper "$@"
