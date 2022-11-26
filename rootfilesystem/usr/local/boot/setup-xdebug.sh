#!/usr/bin/env bash
#
# To enable the PHP extension Sdebug if needed when booting a Docker container.
#

set -e

if [[ "${BOOT_MODE}" == "SERVICE" ]] ; then
    if [[ ! -z "${PHP_IDE_CONFIG}" ]] && [[ ! -z "${XDEBUG_CONFIG}" ]] ; then
        cp /usr/local/etc/php/conf.d/sdebug.ini-suggested /usr/local/etc/php/conf.d/sdebug.ini
        configs=($XDEBUG_CONFIG)
        for config in ${configs[@]} ; do
            echo "xdebug.${config}" >> /usr/local/etc/php/conf.d/sdebug.ini
        done

        echo "PHP extension sdebug is enabled with customized configurations."
    fi
fi
