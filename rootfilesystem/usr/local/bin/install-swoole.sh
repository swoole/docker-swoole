#!/usr/bin/env bash
# This script is used to install Swoole and its extensions in the official Swoole image.
#
# You may also use the script locally. To do that, set environment variables "{$EXT_NAME}_VERSION" when running the
# script, where EXT_NAME should be "SWOOLE" or name of a Swoole extension (in uppercase), and the value should be a
# branch name, a tag or a Git commit number. For example,
#
#     SWOOLE_VERSION=alpine # To install Swoole with latest code from branch "alpine".
#     ASYNC_VERSION=16d9c484d7abde7dbd47d1f8f411fae80611ee1f # To install Swoole extension "async" with code from a Git commit.
#     ORM_VERSION=1.01 # To install version 1.01 of Swoole extension "orm".
#     POSTGRESQL_VERSION=1ccd2ffbdc6e6d1f7b067509817f4bf93fe1982a # To install Swoole extension "postgresql" with code from a Git commit.
#     SERIALIZE_VERSION=master# To install Swoole extension "serialize" with latest code from branch "master".
#
# Here is an example command to install Swoole and its extension async, orm, postgresql and serialize:
#
#     SWOOLE_VERSION=4.4.3                                   \
#     ASYNC_VERSION=4a2b4b69a0d208de25406b39f602f32409ecba63 \
#     ORM_VERSION=master                                     \
#     POSTGRESQL_VERSION=1.01                                \
#     SERIALIZE_VERSION=master                               \
#     bash <(curl -s https://raw.githubusercontent.com/swoole/docker-swoole/master/rootfilesystem/usr/local/bin/install-swoole.sh)
#
# Here is another example command to install Swoole and its extension async only:
#
#     SWOOLE_VERSION=4.4.3                                   \
#     ASYNC_VERSION=4a2b4b69a0d208de25406b39f602f32409ecba63 \
#     bash <(curl -s https://raw.githubusercontent.com/swoole/docker-swoole/master/rootfilesystem/usr/local/bin/install-swoole.sh)
#
# You can specify other predefined variables if needed. For example, on macOS Mojave you may need to specify LDFLAGS,
# CFLAGS and CPPFLAGS like following:
#
#     SWOOLE_VERSION=4.4.3                                   \
#     ASYNC_VERSION=4a2b4b69a0d208de25406b39f602f32409ecba63 \
#     LDFLAGS="-L/usr/local/opt/openssl/lib -L/usr/local/lib -L/usr/local/opt/expat/lib"               \
#     CFLAGS="-I/usr/local/opt/openssl/include/ -I/usr/local/include -I/usr/local/opt/expat/include"   \
#     CPPFLAGS="-I/usr/local/opt/openssl/include/ -I/usr/local/include -I/usr/local/opt/expat/include" \
#     bash <(curl -s https://raw.githubusercontent.com/swoole/docker-swoole/master/rootfilesystem/usr/local/bin/install-swoole.sh)
#
# Before using this script, you should have PHP extension sockets installed, and have packages like openssl installed
# already.

set -ex

# VERSION parameters used in this script could be one of following:
#     * master                                   # "master" is a branch name.
#     * v4.3.3                                   # "v4.3.3" is a tag.
#     * e52c4b78b4a016fffb049490555a8858ca16edb6 # a full Git commit number.

# Download a Swoole package from Github.
#
# @param Swoole package name.
# @param Version #.
function download()
{
    project_name=$1
    if [[ -z "$2" ]] ; then
        version=master
    else
        version=$2
    fi

    if [[ "${version}" =~ ^v[0-9]+\.[0-9]+\.[0-9]+(\-[A-Za-z0-9]+)?$ ]] ; then
        downlaod_url="https://github.com/swoole/${project_name}/archive/${version}.zip"
        unzipped_dir="${project_name}-${version#*v}"
    elif [[ "${version}" =~ ^[0-9]+\.[0-9]+\.[0-9]+(\-[A-Za-z0-9]+)?$ ]] ; then
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
    cd "${project_name}"

    # Temporary hacks to bypass Swoole version checks.
    if [[ -f ./php_swoole_async.h ]] ; then
        sed -i.bak 's/define[[:space:]]\{1,\}PHP_SWOOLE_EXT_ASYNC_VERSION_ID[[:space:]]\{1,\}[[:digit:]]\{1,\}/define PHP_SWOOLE_EXT_ASYNC_VERSION_ID 40414/g' ./php_swoole_async.h
    fi
    if [[ -f ./swoole_postgresql_coro.h ]] ; then
        sed -i.bak 's/define[[:space:]]\{1,\}PHP_SWOOLE_EXT_POSTGRESQL_VERSION_ID[[:space:]]\{1,\}[[:digit:]]\{1,\}/define PHP_SWOOLE_EXT_POSTGRESQL_VERSION_ID 40414/g' ./swoole_postgresql_coro.h
    fi
}

# Install a Swoole package from source code.
#
# @param Swoole package name.
# @param Version #.
# @param Rest parameters are the configure options.
function install()
{
    download "$1" "$2"
    phpize
    ./configure "${@:3}"
    make -j$(nproc)
    make install
    make clean
}

# Install given Swoole extension if its version # is specified.
#
# @param Swoole extension name. e.g., async, orm, postgresql, serialize, zookeeper, etc.
# @param Rest parameters are the configure options.
function installExt()
{
    EXT_VERSION="${1^^}_VERSION"
    EXT_VERSION=${!EXT_VERSION}
    if [ -n "${EXT_VERSION}" ] ; then
        echo "Installing Swoole extension ${1} ${EXT_VERSION}..."
        install ext-"$1" "$EXT_VERSION" "${@:2}"
        cd ..
    else
        echo "Swoole extension ${1} will not be installed."
    fi
}

# Install PHP-X.
#
# @param Version #.
function installPHPX()
{
    download phpx "$1"

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

    cd ..
}

# Install given Swoole extension if its version # is specified.
#
# @param Swoole extension name. e.g., zookeeper.
function installExtUsingPHPX()
{
    EXT_VERSION="${1^^}_VERSION"
    EXT_VERSION=${!EXT_VERSION}
    if [ -n "${EXT_VERSION}" ] ; then
        echo "Installing Swoole extension ${1} ${EXT_VERSION}..."
        download ext-"$1" "$EXT_VERSION"
        ../phpx/bin/phpx build -v -d
        ../phpx/bin/phpx install
        cd ..
    else
        echo "Swoole extension ${1} will not be installed."
    fi
}

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
install swoole-src "${SWOOLE_VERSION}" --enable-http2 --enable-mysqlnd --enable-openssl --enable-sockets ${DEV_OPTIONS}
if hash docker-php-ext-enable 2>/dev/null ; then
    docker-php-ext-enable swoole
else
    # TODO: automatically get the Swoole extension enabled if not built using Docker.
    echo Error: PHP extension swoole is not enabled. Please have it enabled first.
    exit 1
fi
installPHPX "master"

for extension_name in async orm postgresql serialize ; do
    installExt $extension_name
done
for extension_name in zookeeper ; do
    installExtUsingPHPX $extension_name
done

cd ..
rm -rf swoole-src/phpx
if [[ "true" = "${DEV_MODE}" ]] ; then
    echo "Swoole is installed for development purpose with source code included:"
    # if hash docker-php-source 2>/dev/null ; then
    #     docker-php-source extract
    #     echo "    * PHP source code can be found under folder /usr/src/php."
    # fi

    if [[ $(pwd) == "/" ]] ; then
        if [[ -d /usr/src ]] && [[ ! -d /usr/src/swoole ]] ; then
            mv /swoole-src /usr/src/swoole
            swoole_src_dir=/usr/src/swoole
        else
            swoole_src_dir=/swoole-src
        fi
    else
        swoole_src_dir="$(pwd)/swoole-src"
    fi
    echo "    * Swoole source code can be found under folder ${swoole_src_dir}."
else
    rm -rf swoole-src
fi
