#!/usr/bin/env bash
#
# This script is used by Travis CI.
#

set -e

# Switch to directory where this shell script sits.
pushd `dirname $0` > /dev/null
CURRENT_SCRIPT_PATH=`pwd -P`
# Switch back to current directory.
popd > /dev/null

cd "${CURRENT_SCRIPT_PATH}/.."

case "${TRAVIS_EVENT_TYPE}" in
    "cron")
        # @see https://docs.travis-ci.com/user/cron-jobs/#detecting-builds-triggered-by-cron
        echo "$DOCKER_PASSWORD" | docker login -u "$DOCKER_USERNAME" --password-stdin
        IMAGE_NAME=phpswoole/swoole SWOOLE_VERSION=latest PHP_VERSION=8.0 TRAVIS=true DEV_MODE=true  ./bin/build.sh amd64
        IMAGE_NAME=phpswoole/swoole SWOOLE_VERSION=latest PHP_VERSION=8.0 TRAVIS=true DEV_MODE=false ./bin/build.sh amd64
        IMAGE_NAME=phpswoole/swoole SWOOLE_VERSION=latest PHP_VERSION=8.0 TRAVIS=true DEV_MODE=false ./bin/build.sh alpine
        ;;
    *)
        composer update -n
        ./vendor/bin/phpcs --standard=PSR12 bin/generate-dockerfiles.php src tests
        ./vendor/bin/phpunit
esac
