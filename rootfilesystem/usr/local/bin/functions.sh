#!/usr/bin/env bash
#
# Environment variables used during Swoole installation:
#     * DEV_MODE
#     * SWOOLE_SRC_DIR: Points to directory /usr/src/swoole-src.
#     * SWOOLE_FUNCTIONS_LOADED: TRUE if this script has been loaded.
#     * SWOOLE_VERSION: Could be one of following:
#         * master                                   # "master" is a branch name.
#         * v4.3.3                                   # "v4.3.3" is a tag.
#         * e52c4b78b4a016fffb049490555a8858ca16edb6 # a full Git commit number.
#

# Download a Swoole package from Github.
#
# @param Swoole package name.
# @param Version #.
function download()
{
    if [[ -z "${SWOOLE_SRC_DIR}" ]] ; then
        echo "Error: environment variable SWOOLE_SRC_DIR is empty or not yet set."
        exit 1
    fi

    project_name=$1
    if [[ "swoole-src" = "${project_name}" ]] ; then
        if [[ ! -d "$(dirname "${SWOOLE_SRC_DIR}")" ]] ; then
            echo "Error: Parent folder \"$(dirname "${SWOOLE_SRC_DIR}")\" does not exist."
            exit 1
        fi
        cd "$(dirname "${SWOOLE_SRC_DIR}")"
    else
        if [[ ! -d "${SWOOLE_SRC_DIR}" ]] ; then
            echo "Error: environment variable SWOOLE_SRC_DIR does not point to a valid folder at \"${SWOOLE_SRC_DIR}\"."
            exit 1
        fi
        cd "${SWOOLE_SRC_DIR}"
    fi

    if [[ -z "$2" ]] ; then
        version=master
    else
        version=$2
    fi

    if [[ "${version}" =~ ^v[0-9]+\.[0-9]+\.[0-9]+(\-?[A-Za-z0-9]+)?$ ]] ; then
        downlaod_url="https://github.com/swoole/${project_name}/archive/${version}.zip"
        unzipped_dir="${project_name}-${version#*v}"
    elif [[ "${version}" =~ ^[0-9]+\.[0-9]+\.[0-9]+(\-?[A-Za-z0-9]+)?$ ]] ; then
        downlaod_url="https://github.com/swoole/${project_name}/archive/v${version}.zip"
        unzipped_dir="${project_name}-${version}"
    else
        downlaod_url="https://github.com/swoole/${project_name}/archive/${version}.zip"
        unzipped_dir="${project_name}-${version}"
    fi

    if [[ -f temp.zip ]] ; then
        rm -f temp.zip
    fi
    if [[ -d "${unzipped_dir}" ]] ; then
        rm -rf "${unzipped_dir}"
    fi
    if [[ -d "${project_name}" ]] ; then
        rm -rf "${project_name}"
    fi

    if ! curl -sfL "${downlaod_url}" -o temp.zip ; then
        echo Error: failed to download from URL "${downlaod_url}"
        exit 1
    fi
    unzip temp.zip
    if [[ ! -d "${unzipped_dir}" ]] ; then
        echo "Error: top directory in the zip file downloaded from URL '${downlaod_url}' is not '${unzipped_dir}'."
        exit 1
    fi
    mv "${unzipped_dir}" "${project_name}"
    rm -f temp.zip
    cd -
}

# Install a Swoole package from source code.
#
# @param Swoole package name.
# @param Version #.
# @param Rest parameters are the configure options.
function install()
{
    old_pwd="$(pwd)"

    download "$1" "$2"
    cd - # Last command in function download() is "cd -", so here we switch to the folder where the source code sits.
    cd "$1"
    phpize
    ./configure "${@:3}"
    make -j$(nproc)
    make install
    make clean

    cd "${old_pwd}"
}

# Install PHP-X.
#
# @param Version #.
function installPHPX()
{
    old_pwd="$(pwd)"

    download phpx "$1"

    cd - # Last command in function download() is "cd -", so here we switch to the folder where the source code sits.
    cd phpx
    # Build phpx (bin)
    ./build.sh

    # Build libphpx.so
    cmake .
    make -j$(nproc)
    make install
    make clean
    # Workaround for error loading libphpx:
    #   error while loading shared libraries: "libphpx.so: cannot open shared object file: No such file or directory"
    # The system already has /usr/local/lib listed in /etc/ld.so.conf.d/libc.conf, so running `ldconfig` fixes the
    # problem (another option is to use $LD_LIBRARY_PATH).
    ldconfig

    cd "${old_pwd}"
}

# Install given Swoole extension if its version # is specified.
#
# @param Swoole extension name. e.g., zookeeper.
# @param Version #.
function installExtUsingPHPX()
{
    old_pwd="$(pwd)"

    echo "Installing Swoole extension ${1} ..."
    download ext-"$1" "${2}"
    cd - # Last command in function download() is "cd -", so here we switch to the folder where the source code sits.
    cd ext-"$1"
    ../phpx/bin/phpx build -v -d
    ../phpx/bin/phpx install

    cd "${old_pwd}"
}

function cleanupSwoole()
{
    if [[ -d "${SWOOLE_SRC_DIR}/phpx" ]] ; then
        rm -rf "${SWOOLE_SRC_DIR}/phpx"
    fi
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
