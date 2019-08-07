#!/usr/bin/env bash
#
# To enable an available program in Supervisord. List of available program can be found under folder
#   rootfilesystem/etc/supervisor/available.d/.
#

set -e

conf_file="/etc/supervisor/available.d/${1}.conf"

if [[ -f "${conf_file}" ]] ; then
    cp "${conf_file}" /etc/supervisor/conf.d/.
fi
