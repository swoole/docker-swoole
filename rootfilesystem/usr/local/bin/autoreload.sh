#!/usr/bin/env bash

set -e

if [[ -z "${ROOT_DIR}" ]] ; then
    ROOT_DIR=/var/www
fi
if [[ ! -d "${ROOT_DIR}" ]] ; then
    echo "Error: path '${ROOT_DIR}' does not point to a directory."
    exit 1
fi

if [[ -z "${AUTORELOAD_PROGRAMS}" ]] ; then
    AUTORELOAD_PROGRAMS=all
fi
while true ; do
    inotifywait -r -q -e close_write,create,delete,modify,move "${ROOT_DIR}"
    supervisorctl signal TERM ${AUTORELOAD_PROGRAMS}
    sleep 2
done
