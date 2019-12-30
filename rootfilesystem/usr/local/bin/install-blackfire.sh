#!/usr/bin/env bash
# @see https://blackfire.io/docs/up-and-running/installation

set -e

apt-get update
apt-get install -y gnupg --no-install-recommends

curl -sfL https://packages.blackfire.io/gpg.key| apt-key add -
echo "deb http://packages.blackfire.io/debian any main" | tee /etc/apt/sources.list.d/blackfire.list

apt-get update
apt-get -o Dpkg::Options::="--force-confdef" -o Dpkg::Options::="--force-confold" install blackfire-agent blackfire-php
rm -r /var/lib/apt/lists/*

mkdir -p /var/run/blackfire
