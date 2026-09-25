#!/usr/bin/env bash

set -e

if [[ ! -z "${SWOOLE_TIMEZONE}" ]] ; then
    # Update token %%SWOOLE_TIMEZONE%% with environment variable SWOOLE_TIMEZONE under given folder.
    update-token.sh SWOOLE_TIMEZONE /usr/local/etc/php
else
    # Update token %%SWOOLE_TIMEZONE%% with a default value under given folder. Script update-token.sh takes more than
    # one folder if needed, e.g.,
    #     update-token.sh SWOOLE_TIMEZONE /usr/local/etc/php /path/to/another/folder
    SWOOLE_TIMEZONE=America/Los_Angeles update-token.sh SWOOLE_TIMEZONE /usr/local/etc/php
fi
