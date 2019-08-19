#!/usr/bin/env bash

set -e

if [[ "${AUTORELOAD}" == "true" ]] ; then
    enable-supervisord-program.sh autoreload
fi
