#!/usr/bin/env bash
#
# Update given token with an environment variable under given folders.
#
# Limitations:
#     1. The environment variable can not contain character "#" in it.
#
# Usage:
#     update-token.sh ENVIRONMENT_VARIABLE_NAME [FOLDER]...
#     e.g.,
#     update-token.sh PHP_VERSION /usr/local/etc/nginx /usr/local/etc/php
#

set -e

if [[ -z ${!1} ]] ; then
    echo "Error: environment variable '{$1}' is empty."
    exit 1
fi

for path in "${@:2}" ; do
    if [[ ! -d "${path}" ]] ; then
        echo "Error: Path '${path}' does not point to a folder."
        exit 1
    fi
done
for path in "${@:2}" ; do
    find "${path}" -type f -exec sed -i "s#%%${1}%%#${!1}#g" {} +
done
