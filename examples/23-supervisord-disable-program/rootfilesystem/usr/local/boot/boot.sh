#!/usr/bin/env bash

set -e

# In this example we have the swoole program disabled from Supervisord. Because of that, no response returned from URL
# http://127.0.0.1 .

if [[ "${BOOT_MODE}" == "SERVICE" ]] ; then
    disable-supervisord-program.sh swoole
fi
