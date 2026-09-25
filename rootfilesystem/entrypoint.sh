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
    # A command given as one single string is split into words, to support Docker commands invoked in ECS (via command
    # "aws ecs run-task"), kind of like following:
    #     docker run --rm phpswoole/swoole "composer --version"
    # Otherwise, arguments are passed through as they are, e.g.,
    #     docker run --rm phpswoole/swoole bash -c "composer --version"
    #     docker run --rm phpswoole/swoole php -r 'echo "hello world", PHP_EOL;'
    if [[ -z "$(ls /etc/supervisor/conf.d/*.conf 2>/dev/null)" ]] ; then
        if [[ $# -eq 1 ]] ; then
            exec $1
        else
            exec "$@"
        fi
    fi

    # Supervisor programs are to run alongside the command (e.g., those under folder /etc/supervisor/task.d/). The
    # command runs as a child process instead of replacing this script, so that once it exits (or the container is
    # stopped), Supervisor stops its programs gracefully rather than having them killed along with the container.
    /usr/bin/supervisord -c /etc/supervisor/supervisord.conf # Run supervisord in the background.

    # Standard input is passed on explicitly, since a command started in the background reads from /dev/null otherwise.
    # Signals SIGINT and SIGQUIT are reset to their defaults too: a non-interactive shell starts background commands with
    # both ignored, so Ctrl+C (forwarded below) wouldn't interrupt the command.
    if [[ $# -eq 1 ]] ; then
        ( trap - INT QUIT ; exec $1 ) <&0 &
    else
        ( trap - INT QUIT ; exec "$@" ) <&0 &
    fi
    pid=$!
    trap 'kill -TERM "${pid}" 2>/dev/null' TERM
    trap 'kill -INT "${pid}" 2>/dev/null' INT

    # Command "wait" returns early when a signal is trapped; keep waiting until the command has actually exited, to get
    # its exit status.
    set +e
    wait "${pid}"
    status=$?
    while kill -0 "${pid}" 2>/dev/null ; do
        wait "${pid}"
        status=$?
    done

    supervisordPid="$(cat /var/run/supervisord.pid 2>/dev/null)"
    supervisorctl -c /etc/supervisor/supervisord.conf shutdown > /dev/null
    while [[ -n "${supervisordPid}" ]] && kill -0 "${supervisordPid}" 2>/dev/null ; do
        sleep 0.1
    done
    exit "${status}"
fi
