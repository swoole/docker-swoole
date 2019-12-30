#!/usr/bin/env bash

set -e

if [[ ! -z "${BLACKFIRE_SERVER_ID}" ]] && [[ ! -z "${BLACKFIRE_SERVER_TOKEN}" ]] ; then
    enable-supervisord-program.sh blackfire-agent
    update-token.sh BLACKFIRE_SERVER_ID    /etc/supervisor/conf.d
    update-token.sh BLACKFIRE_SERVER_TOKEN /etc/supervisor/conf.d
fi
