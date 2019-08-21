#!/usr/bin/env bash
#
# To disable a program in Supervisord.
# Once a program is disabled, you can't enable it with script enable-supervisord-program.sh directly.
#

set -e

for conf_dir in "available.d" "conf.d" "service.d" "task.d" ; do
    conf_file="/etc/supervisor/${conf_dir}/${1}.conf"
    if [[ -f "${conf_file}" ]] ; then
        mv "${conf_file}" "${conf_file}.disabled"
    fi
done
