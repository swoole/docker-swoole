#!/usr/bin/env bash

set -e

sed -i 's/Hello, World!/In this example we use a customized entrypoint script in the image built./g' /var/www/server.php

. /entrypoint.sh
