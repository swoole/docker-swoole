#!/usr/bin/env bash

set -e

if [[ -z "${ROOT_DIR}" ]] ; then
    ROOT_DIR=/var/www
fi
if [[ ! -d "${ROOT_DIR}" ]] ; then
    echo "Error: path '${ROOT_DIR}' does not point to a directory."
    exit 1
fi

while true ; do
    inotifywait -r -q -e close_write,create,delete,modify,move /var/www/
    supervisorctl signal TERM swoole
    sleep 2
done
