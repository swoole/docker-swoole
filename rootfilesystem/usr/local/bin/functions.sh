#!/usr/bin/env bash
#
# Functions to download, build and install Swoole from source code, used by script install-swoole.sh.
#
# Environment variables used:
#     * DEV_MODE: When set to "true", the source code of Swoole is kept after the installation.
#     * SWOOLE_SRC_DIR: The folder the source code of Swoole is put in; set by function initSwooleDir() below.
#     * SWOOLE_FUNCTIONS_LOADED: Set to "true" once this script has been loaded.
#

# Download the source code of Swoole from GitHub into folder $SWOOLE_SRC_DIR, replacing anything already there.
#
# @param The version to download: a branch name (e.g., "master"; the default), a tag with or without the leading "v"
#        (e.g., "6.2.3", "v6.2.3" or "6.3.0-rc1"), or a full Git commit hash.
function download()
{
    local version="${1:-master}"
    if [[ "${version}" =~ ^[0-9]+\.[0-9]+\.[0-9]+(-?[A-Za-z0-9]+)?$ ]] ; then
        version="v${version}"
    fi

    local url="https://github.com/swoole/swoole-src/archive/${version}.tar.gz"
    local archive
    archive="$(mktemp)"
    if ! curl -sSfL --retry 5 --retry-all-errors --connect-timeout 20 "${url}" -o "${archive}" ; then
        echo "Error: failed to download from URL '${url}'."
        rm -f "${archive}"
        exit 1
    fi

    # The archive has a single top-level folder whose name depends on the version (e.g., "swoole-src-6.2.3"), so it is
    # stripped rather than guessed.
    rm -rf "${SWOOLE_SRC_DIR}"
    mkdir -p "${SWOOLE_SRC_DIR}"
    tar xzf "${archive}" --strip-components=1 -C "${SWOOLE_SRC_DIR}"
    rm -f "${archive}"
}

# Build and install Swoole from the source code in folder $SWOOLE_SRC_DIR.
#
# @param The configure options. Options not recognized by the version of Swoole being built fail the build, same as
#        with command docker-php-ext-configure.
function build()
{
    (
        cd "${SWOOLE_SRC_DIR}"
        phpize
        ./configure --enable-option-checking=fatal "$@"
        make -j"$(nproc)"
        make install
        make clean
    )
}

function cleanupSwoole()
{
    if [[ "true" = "${DEV_MODE}" ]] ; then
        echo "Swoole is installed for development purpose with source code included in folder \"${SWOOLE_SRC_DIR}\"."
    else
        rm -rf "${SWOOLE_SRC_DIR}"
    fi
}

function initSwooleDir()
{
    if [[ -d /usr/src ]] ; then
        SWOOLE_SRC_DIR=/usr/src/swoole-src
    else
        if [[ $(pwd) == "/" ]] ; then
           SWOOLE_SRC_DIR=/swoole-src
        else
           SWOOLE_SRC_DIR="$(pwd)/swoole-src"
        fi
    fi

    export SWOOLE_SRC_DIR="${SWOOLE_SRC_DIR}"
}

initSwooleDir
SWOOLE_FUNCTIONS_LOADED=true
