#!/usr/bin/env bash
#
# How to run this script:
#     To build Swoole 4.3.5 images for PHP 7.1, 7.2, 7.3 under all supported architectures (including amd64 and arm64v8):
#     IMAGE_NAME=swoole/swoole SWOOLE_VERSION=4.3.5 ./bin/build.sh
#
#     To build Swoole 4.3.5 images for PHP 7.1, 7.2, 7.3 under amd64:
#     IMAGE_NAME=swoole/swoole SWOOLE_VERSION=4.3.5 ./bin/build.sh default
#     or,
#     IMAGE_NAME=swoole/swoole SWOOLE_VERSION=4.3.5 ./bin/build.sh amd64
#
#     To build Swoole 4.3.5 images for PHP 7.1, 7.2, 7.3 under arm64v8:
#     IMAGE_NAME=swoole/swoole SWOOLE_VERSION=4.3.5 ./bin/build.sh arm64v8
#
#     To build Swoole 4.3.5 images for PHP 7.3 under all supported architectures (including amd64 and arm64v8):
#     IMAGE_NAME=swoole/swoole SWOOLE_VERSION=4.3.5 PHP_VERSION=7.3 ./bin/build.sh
#
#     To build image "swoole/swoole:4.3.5-php7.3":
#     IMAGE_NAME=swoole/swoole SWOOLE_VERSION=4.3.5 PHP_VERSION=7.3 ./bin/build.sh default
#     or,
#     IMAGE_NAME=swoole/swoole SWOOLE_VERSION=4.3.5 PHP_VERSION=7.3 ./bin/build.sh amd64
#
#     To build image "swoole/swoole:4.3.5-php7.3-arm64v8":
#     IMAGE_NAME=swoole/swoole SWOOLE_VERSION=4.3.5 PHP_VERSION=7.3 ./bin/build.sh arm64v8
#
#     To build image "swoole/swoole":
#     IMAGE_NAME=swoole/swoole SWOOLE_VERSION=master ./bin/build.sh
#

set -ex

# Switch to directory where this shell script sits.
pushd `dirname $0` > /dev/null
CURRENT_SCRIPT_PATH=`pwd -P`
# Switch back to current directory.
popd > /dev/null

if [[ -z "${IMAGE_NAME}" ]] ; then
    if [[ ! -z "${TRAVIS_REPO_SLUG}" ]] ; then
        # If you don't pass in an image name, we assume your Docker ID is the same as your GitLab ID, and use it in
        # the image name. The image name should look like "your-github-id/swoole".
        IMAGE_NAME=${TRAVIS_REPO_SLUG%%/*}/swoole
    else
        echo "Error: Docker image name is empty."
        exit 1
    fi
fi
if [[ -z "${SWOOLE_VERSION}" ]] ; then
    if [[ ! -z "${TRAVIS_BRANCH}" ]] ; then
        # If you don't pass in a Swoole version, we will try to extract it from current branch name. e.g.,
        # 1. if current branch name is "4.3.5", we assume Swoole version is "4.3.5";
        # 2. if current branch name is "4.4.2-1", we assume Swoole version is "4.4.2".
        SWOOLE_VERSION=${TRAVIS_BRANCH%%-*}
    else
        echo "Error: Swoole version is empty."
        exit 1
    fi
fi

if [[ ! -z "${ARCHITECTURE}" ]] ; then
    ARCHITECTURES=(${ARCHITECTURE})
else
    if [[ -z "${1}" ]] ; then
        ARCHITECTURES=(amd64 arm64v8)
    else
        case "$1" in
            "amd64"|"default")
                ARCHITECTURES=(amd64)
                ;;
            "arm64v8")
                ARCHITECTURES=(arm64v8)
                ;;
            *)
                echo "Error: First command line parameter must be one of \"php\", \"amd64\", \"default\", or \"arm64v8\"."
                exit 1
        esac
    fi
fi

IMAGE_CONFIG_FILE="${CURRENT_SCRIPT_PATH}/../config/${SWOOLE_VERSION}.yml"

if [[ ! -f "${IMAGE_CONFIG_FILE}" ]] ; then
    # If a version-based configuration file is not found, it means we might want to build an image from a Swoole branch.
    # Most time this is for development purpose so we will use that Swoole branch to build one amd64 image only.
    echo "INFO: configuration file '${IMAGE_CONFIG_FILE}' not found."
    IMAGE_CONFIG_FILE="${CURRENT_SCRIPT_PATH}/../config/latest.yml"
    ARCHITECTURES=(amd64)
    IMAGE_TAGS=(latest)
    echo "      Will build image '${IMAGE_NAME}' based on configuration file '${IMAGE_CONFIG_FILE}'."
fi

if egrep -q '^status\:\s*"under development"\s*($|\#)' "${IMAGE_CONFIG_FILE}" ; then
    for ARCHITECTURE in "${ARCHITECTURES[@]}" ; do
        if [[ -z "${IMAGE_TAGS}" ]] ; then
            if [[ -z "${PHP_VERSION}" ]] ; then
                IMAGE_TAGS=(`ls temp/dockerfiles/${ARCHITECTURE}/${SWOOLE_VERSION}-*.Dockerfile | xargs -n 1 basename -s .Dockerfile`)
            else
                IMAGE_TAGS=("${SWOOLE_VERSION}-php${PHP_VERSION}")
            fi
        fi

        for IMAGE_TAG in "${IMAGE_TAGS[@]}" ; do
            DOCKERFILE="temp/dockerfiles/${ARCHITECTURE}/${IMAGE_TAG}.Dockerfile"
            if [[ -f "${DOCKERFILE}" ]] ; then
                if [[ "${ARCHITECTURE}" == "amd64" ]] ; then
                    IMAGE_TAG_POSTFIX=
                else
                    IMAGE_TAG_POSTFIX="-${ARCHITECTURE}"
                fi

                IMAGE_FULL_NAME="${IMAGE_NAME}:${IMAGE_TAG}${IMAGE_TAG_POSTFIX}"
                docker build -t "${IMAGE_FULL_NAME}" -f "${DOCKERFILE}" "${CURRENT_SCRIPT_PATH}/.."

                # Push the image built only when running in Travis CI.
                if [[ "${TRAVIS}" == "true" ]] ; then
                    docker push "${IMAGE_FULL_NAME}"
                fi
            else
                echo "Error: Dockerfile '${DOCKERFILE}' not found."
                exit 1
            fi
        done
    done
else
    echo "INFO: Current branch is not marked under active development. Nothing to build."
fi
