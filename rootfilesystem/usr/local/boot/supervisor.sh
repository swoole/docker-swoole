#!/usr/bin/env bash
#
# Supervisor is a client/server system that allows its users to monitor and control a number of processes on UNIX-like
# operating systems. For more details, please check http://supervisord.org.
#

set -e

case "${BOOT_MODE}" in
    "SERVICE")
        echo "INFO: Supervisord configuration files copied from folder '/etc/supervisor/${BOOT_MODE,,}.d/'."
        find /etc/supervisor/${BOOT_MODE,,}.d/ -mindepth 1 -type f -name "*.conf" -exec cp -t /etc/supervisor/conf.d/ {} +
        ;;
    "TASK")
        find /etc/supervisor/${BOOT_MODE,,}.d/ -mindepth 1 -type f -name "*.conf" -exec cp -t /etc/supervisor/conf.d/ {} +
        ;;
    *)
        echo "Error: BOOT_MODE in the Docker container must be either SERVICE or TASK."
        exit 1
esac
