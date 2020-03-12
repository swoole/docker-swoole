#!/usr/bin/env bash

set -e

if [[ ! -z "${BLACKFIRE_SERVER_ID}" ]] && [[ ! -z "${BLACKFIRE_SERVER_TOKEN}" ]] && [[ -x /usr/bin/blackfire-agent ]] ; then
    enable-supervisord-program.sh blackfire-agent
    update-token.sh BLACKFIRE_SERVER_ID    /etc/supervisor/conf.d
    update-token.sh BLACKFIRE_SERVER_TOKEN /etc/supervisor/conf.d
fi
