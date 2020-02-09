#!/usr/bin/env bash

set -ex

[[ -z "${SWOOLE_FUNCTIONS_LOADED}" ]] && . functions.sh

if ! php --ri swoole ; then
    echo "Error: PHP exension \"Swoole\" is not installed or enabled."
    exit 1
fi

project_name=sdebug
if [[ "7" == $(php -r "echo PHP_MAJOR_VERSION;") ]] ; then
    case $(php -r "echo PHP_MINOR_VERSION;") in
        "1"|"2")
            version=sdebug
            ;;
        "3")
            version=sdebug_2_7
            ;;
        "4")
            version=sdebug_2_9
            ;;
        *)
            echo "Error: PHP extension \"sdebug\" supports PHP 7.1 to 7.4 only."
            exit 1
    esac
else
    echo "Error: PHP extension \"sdebug\" supports PHP 7 only."
    exit 1
fi
downlaod_url="https://github.com/swoole/${project_name}/archive/${version}.zip"
unzipped_dir="${project_name}-${version}"

rm -rf temp.zip "${unzipped_dir}"

if ! curl -sfL "${downlaod_url}" -o temp.zip ; then
    echo Error: failed to download from URL "${downlaod_url}"
    exit 1
fi
unzip temp.zip
if [[ ! -d "${unzipped_dir}" ]] ; then
    echo "Error: top directory in the zip file downloaded from URL '${downlaod_url}' is not '${unzipped_dir}'."
    exit 1
fi

cd "${unzipped_dir}"

phpize
./configure
make -j$(nproc)
make install
make clean

cd -
rm -rf temp.zip "${unzipped_dir}"
