#!/usr/bin/env bash

set -e

if [[ ! -z "$@" ]] ; then
    BOOT_MODE=TASK
else
    BOOT_MODE=SERVICE
fi

for f in /usr/local/boot/*.sh ; do
    BOOT_MODE=${BOOT_MODE} . "$f"
done

# We use option "-c" here to suppress following warning message from console output:
#   UserWarning: Supervisord is running as root and it is searching for its configuration file in default locations...
if [[ -n "$(ls /etc/supervisor/conf.d/*.conf 2>/dev/null)" ]] ; then
    if [[ "SERVICE" == "${BOOT_MODE}" ]] ; then
        /usr/bin/supervisord -c /etc/supervisor/supervisord.conf -n
    else
        /usr/bin/supervisord -c /etc/supervisor/supervisord.conf
    fi
fi

if [[ ! -z "$@" ]] ; then
    if [[ "${1}" =~ ^(ba|)sh$ ]] ; then
        # To support Docker commands like following:
        # docker run --rm swoole/swoole:4.3.5 bash -c "composer --version"
        # docker run --rm swoole/swoole:4.3.5   sh -c "composer --version"
        exec "$@"
    else
        # To support Docker commands invoked in ECS (via command "aws ecs run-task"), kind of like following:
        # docker run --rm swoole/swoole:4.3.5 "composer --version"
        exec $@
    fi
fi
