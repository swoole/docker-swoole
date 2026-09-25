#!/usr/bin/env bash

set -e

if [[ "${BOOT_MODE}" == "SERVICE" ]] ; then
    if [[ -n "${AUTORELOAD_PROGRAMS}" ]] ; then
        enable-supervisord-program.sh autoreload
    fi
fi
