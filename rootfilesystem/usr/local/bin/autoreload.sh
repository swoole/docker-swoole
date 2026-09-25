#!/usr/bin/env bash

set -e

if [[ -z "${ROOT_DIR}" ]] ; then
    ROOT_DIR=/var/www
fi
if [[ ! -d "${ROOT_DIR}" ]] ; then
    echo "Error: path '${ROOT_DIR}' does not point to a directory."
    exit 1
fi

if [[ -z "${AUTORELOAD_PROGRAMS}" ]] ; then
    AUTORELOAD_PROGRAMS=all
fi

# Print the Supervisor programs to restart. "all" means all programs except this one, which would otherwise be
# restarted too, in the middle of a reload.
function get_programs()
{
    if [[ "all" == "${AUTORELOAD_PROGRAMS}" ]] ; then
        supervisorctl avail | awk '{print $1}' | grep -vx autoreload || true
    else
        echo "${AUTORELOAD_PROGRAMS}"
    fi
}

# If environment variable AUTORELOAD_ANY_FILES is set to "true", "1", "yes", or "y", reload Supervisor programs when
# any files under the root directory (/var/www by default) is changed; otherwise, reload only when PHP file(s) are
# changed.
function is_watched()
{
    [[ "${AUTORELOAD_ANY_FILES,,}" =~ ^(1|true|yes|y)$ ]] || [[ "php" == "${1##*.}" ]]
}

# One single inotifywait process watches the directory for as long as this script runs. Restarting it after each
# change would leave the previous one running: Supervisor starts its programs with signal SIGPIPE ignored, so an
# inotifywait process whose reader is gone never exits.
inotifywait -r -q -m --format "%w%f" -e close_write,create,delete,modify,move "${ROOT_DIR}" | while read -r file ; do
    if ! is_watched "${file}" ; then
        continue
    fi

    # Saving a file usually triggers several events in a row. Wait until there are no more events for one second, then
    # reload once.
    while read -r -t 1 _ ; do
        :
    done

    # Command "restart" also starts programs that are not running (e.g., in state FATAL after exiting on a syntax
    # error), which command "signal" can't do. A failure (e.g., a program that fails to start) must not stop this
    # script.
    programs=$(get_programs)
    if [[ -n "${programs}" ]] ; then
        # shellcheck disable=SC2086
        supervisorctl restart ${programs} || true
    fi
done
