#!/usr/bin/env bash

set -e

if [[ ! -z "${SWOOLE_TIMEZONE}" ]] ; then
    # Update token %%SWOOLE_TIMEZONE%% with environment variable SWOOLE_TIMEZONE under given folder.
    update-token.sh SWOOLE_TIMEZONE /usr/local/etc/php
else
    # Update token %%SWOOLE_TIMEZONE%% with local variable SWOOLE_TIMEZONE under given folders.
    SWOOLE_TIMEZONE=America/Los_Angeles
    SWOOLE_TIMEZONE=${SWOOLE_TIMEZONE} update-token.sh SWOOLE_TIMEZONE /usr/local/etc/php /path/to/another/folder
fi
