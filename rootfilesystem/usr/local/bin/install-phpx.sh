#!/usr/bin/env bash
# How to run the script?
#     install-phpx.sh master                                   # "master" is a branch name.
#     install-phpx.sh v0.2.0                                   # "v0.2.0" is a tag.
#     install-phpx.sh 0e7c4be6ec36e856b1cb334ddb2b67d97af84d42 # a full Git commit number.
#
# NOTE：You can call bash function cleanupSwoole() to remove the source code directory manually after the installation.

set -ex

[[ -z "${SWOOLE_FUNCTIONS_LOADED}" ]] && . functions.sh

if ! php --ri swoole ; then
    echo "Error: PHP exension \"Swoole\" is not installed or enabled."
    exit 1
fi

if [[ ! -d "${SWOOLE_SRC_DIR}" ]] ; then
    download swoole-src "${SWOOLE_VERSION}"
fi

if ! type cmake > /dev/null ; then
    apt-get update
    apt-get install -y cmake --no-install-recommends
    rm -r /var/lib/apt/lists/*
fi

installPHPX "$@"
