#!/usr/bin/env bash

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

    curl -sfL "${downlaod_url}" -o temp.zip
    unzip temp.zip
    if [[ ! -d "${unzipped_dir}" ]] ; then
        echo "Error: top directory in the zip file downloaded from URL '${downlaod_url}' is not '${unzipped_dir}'."
        exit 1
    fi
    mv "${unzipped_dir}" "${project_name}"
    rm -f temp.zip
    cd "${project_name}"
}

# Install a Swoole package from source code.
#
# @param Swoole package name.
# @param Version #.
function install()
{
    download $1 $2
    phpize
    ./configure
    make
    make install
}

# Install given Swoole extension if its version # is specified.
#
# @param Swoole extension name. e.g., async, orm, postgresql, serialize, zookeeper, etc.
function installExt()
{
    EXT_VERSION="${1^^}_VERSION"
    EXT_VERSION=${!EXT_VERSION}
    if [ ! -z "${EXT_VERSION}" ] ; then
        echo "Installing Swoole extension ${1} ${EXT_VERSION}..."
        download ext-$1 $EXT_VERSION
        phpize
        ./configure
        make
        make install
        cd ..
    else
        echo "Swoole extension ${1} will not be installed."
    fi
}

install swoole-src ${SWOOLE_VERSION}
for extension_name in async orm postgresql serialize zookeeper ; do
    installExt $extension_name
done

cd ..
rm -rf swoole-src
