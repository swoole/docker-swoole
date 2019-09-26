#!/usr/bin/env bash

set -e

if [[ "${BOOT_MODE}" == "SERVICE" ]] ; then
    if [[ ! -z "${AUTORELOAD_PROGRAMS}" ]] ; then
        if hash inotifywait 2>/dev/null ; then
            enable-supervisord-program.sh autoreload
        else
            echo "Error: command \"inotifywait\" not found. Docker environment variable \"AUTORELOAD_PROGRAMS\" should"
            echo "       only be used with the \"latest\" Swoole image (the one built from the master branch)."
            exit 1
        fi
    fi
fi
