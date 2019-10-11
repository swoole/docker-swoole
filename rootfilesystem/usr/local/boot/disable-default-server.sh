#!/usr/bin/env bash
# This script is to help to automatically stop the default web server when launching Docker containers.
#
# By default a web server will be started using script /var/www/server.php when the Docker container is booted. During
# local development you might not want to have the web server running. In this case, please have environment variable
# DISABLE_DEFAULT_SERVER set to "true" or 1.
#

set -e

if [[ "${BOOT_MODE}" == "SERVICE" ]] ; then
    if [[ "${DISABLE_DEFAULT_SERVER}" = "true" ]] || [[ "${DISABLE_DEFAULT_SERVER}" = "1" ]] ; then
        disable-supervisord-program.sh swoole
    fi
fi
