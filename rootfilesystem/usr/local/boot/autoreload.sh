#!/usr/bin/env bash

set -e

if [[ "${BOOT_MODE}" == "SERVICE" ]] ; then
    if [[ ! -z "${AUTORELOAD_PROGRAMS}" ]] ; then
        if ! hash inotifywait 2>/dev/null ; then
            if [[ "${EUID}" -ne 0 ]] ; then
                echo "Error: command \"inotifywait\" not found, and package \"inotify-tools\" can only be installed by root."
                exit 1
            fi
            echo "INFO: command \"inotifywait\" not found. Will install package \"inotify-tools\" first for that."

            apt-get update
            apt-get install -y inotify-tools --no-install-recommends
            rm -r /var/lib/apt/lists/*
        fi

        enable-supervisord-program.sh autoreload
    fi
fi
