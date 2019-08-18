#!/usr/bin/env bash
#
# Sample script showing how to write a script that will be executed automatically when a
# Docker container is started.
#

set -e

if [[ "${BOOT_MODE}" == "SERVICE" ]] ; then
    echo "Docker container is running in SERVICE mode."

  sed -i 's/Hello, World!/In this example we send a customized response back for all requests./g' /var/www/server.php
fi

if [[ "${BOOT_MODE}" == "TASK" ]] ; then
    echo "Docker container is running in TASK mode."
fi

echo "This line is printed out both in SERVICE mode and in TASK mode."
