#!/usr/bin/env bash

set -e

if [[ -n "$*" ]] ; then
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
        exec /usr/bin/supervisord -c /etc/supervisor/supervisord.conf -n # Run supervisord in the foreground.
    else
        # No programs to run. Wait until the container is stopped, and exit right away when it is: as PID 1, a process
        # without signal handlers (e.g., "tail -f /dev/null") ignores SIGTERM, so "docker stop" would have to kill it.
        trap 'exit 0' TERM INT
        sleep infinity &
        wait
    fi
else
    if [[ -n "$(ls /etc/supervisor/conf.d/*.conf 2>/dev/null)" ]] ; then
        /usr/bin/supervisord -c /etc/supervisor/supervisord.conf # Run supervisord in the background.
    fi

    if [[ $# -eq 1 ]] ; then
        # A command given as one single string is split into words, to support Docker commands invoked in ECS (via
        # command "aws ecs run-task"), kind of like following:
        #     docker run --rm phpswoole/swoole "composer --version"
        exec $1
    else
        # Arguments are passed through as they are, e.g.,
        #     docker run --rm phpswoole/swoole bash -c "composer --version"
        #     docker run --rm phpswoole/swoole php -r 'echo "hello world", PHP_EOL;'
        exec "$@"
    fi
fi
