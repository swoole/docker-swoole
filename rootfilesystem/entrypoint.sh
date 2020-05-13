#!/usr/bin/env bash

set -e

if [[ ! -z "$@" ]] ; then
    # The container is started to run some one-off command only.
    BOOT_MODE=TASK
else
    # The container is to launch some long running services (e.g., web server, job worker, etc).
    BOOT_MODE=SERVICE
fi
export BOOT_MODE

# Now run .php and .sh scripts under folder /usr/local/boot in order.
boot_scripts=()
shopt -s nullglob
for f in /usr/local/boot/*.sh ; do
    boot_scripts+=("$f")
done
shopt -u nullglob
IFS=$'\n' boot_scripts=($(sort <<<"${boot_scripts[*]}"))
unset IFS
for f in "${boot_scripts[@]}" ; do
    . "$f"
done

# We use option "-c" here to suppress following warning message from console output:
#   UserWarning: Supervisord is running as root and it is searching for its configuration file in default locations...
if [[ "SERVICE" == "${BOOT_MODE}" ]] ; then
    if [[ -n "$(ls /etc/supervisor/conf.d/*.conf 2>/dev/null)" ]] ; then
        /usr/bin/supervisord -c /etc/supervisor/supervisord.conf -n
    else
        tail -f /dev/null
    fi
else
    if [[ -n "$(ls /etc/supervisor/conf.d/*.conf 2>/dev/null)" ]] ; then
        /usr/bin/supervisord -c /etc/supervisor/supervisord.conf
    fi

    if [[ "${1}" =~ ^(ba|)sh$ ]] ; then
        # To support Docker commands like following:
        # docker run --rm phpswoole/swoole bash -c "composer --version"
        # docker run --rm phpswoole/swoole   sh -c "composer --version"
        exec "$@"
    else
        # To support Docker commands invoked in ECS (via command "aws ecs run-task"), kind of like following:
        # docker run --rm phpswoole/swoole "composer --version"
        exec $@
    fi
fi
