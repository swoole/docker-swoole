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

# If environment variable AUTORELOAD_ANY_FILES is set to "true", "1", "yes", or "y", reload Supervisor programs when
# any files under the root directory (/var/www by default) is changed; otherwise, reload only when PHP file(s) are
# changed.
while true ; do
    while read file ; do
        if [[ "${AUTORELOAD_ANY_FILES,,}" =~ ^(1|true|yes|y)$ ]] || [[ "php" == "${file##*.}" ]] ; then
            break
        fi
    done < <(inotifywait -r -q -m --format "%f" -e close_write,create,delete,modify,move "${ROOT_DIR}")

    supervisorctl signal TERM ${AUTORELOAD_PROGRAMS}
    sleep 2
done
