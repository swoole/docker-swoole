#!/usr/bin/env bash
#
# To enable the PHP extension Xdebug if needed when booting a Docker container.
#

set -e

if [[ "${BOOT_MODE}" == "SERVICE" ]] ; then
    if [[ ! -z "${PHP_IDE_CONFIG}" ]] && [[ ! -z "${XDEBUG_CONFIG}" ]] ; then
        echo "zend_extension=xdebug" > "$(php-config --ini-dir)/xdebug.ini"
        configs=($XDEBUG_CONFIG)
        for config in ${configs[@]} ; do
            echo "xdebug.${config}" >> "$(php-config --ini-dir)/xdebug.ini"
        done

        echo "PHP extension Xdebug is enabled with customized configurations."
    fi
fi
