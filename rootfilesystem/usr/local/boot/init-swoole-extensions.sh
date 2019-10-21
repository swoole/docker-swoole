#!/usr/bin/env bash

set -e

for extension_name in async orm postgresql serialize zookeeper ; do
    status_field=swoole_ext_${extension_name}
    swoole_extension_status=${status_field^^}
    swoole_extension_status=${!swoole_extension_status}

    extension_name=swoole_${extension_name}

    if [ ! -z "${swoole_extension_status}" ] ; then
        case "${swoole_extension_status}" in
            "enabled")
                if ! php -m | grep -q ${extension_name} ; then
                    docker-php-ext-enable ${extension_name}
                fi
                ;;
            "disabled")
                if php -m | grep -q ${extension_name} ; then
                    for filename in /usr/local/etc/php/conf.d/*.ini ; do
                        if grep -q "^[[:space:]]*extension=${extension_name}.so" "${filename}" ; then
                            rm -f "${filename}"
                        fi
                    done
                fi
                ;;
            *)
                echo "Error: Environment variable '${swoole_extension_status}' must be either 'enabled' or 'disabled' if present."
                exit 1
        esac
    fi
done
