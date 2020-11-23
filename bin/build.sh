#!/usr/bin/env bash
#
# How to run this script:
#     To build Swoole 4.3.6 images for PHP 7.1, 7.2, 7.3 under all supported architectures (including amd64 and arm64v8):
#     IMAGE_NAME=phpswoole/swoole SWOOLE_VERSION=4.3.6 ./bin/build.sh
#
#     To build Swoole 4.3.6 images for PHP 7.1, 7.2, 7.3 under amd64:
#     IMAGE_NAME=phpswoole/swoole SWOOLE_VERSION=4.3.6 ./bin/build.sh default
#     or,
#     IMAGE_NAME=phpswoole/swoole SWOOLE_VERSION=4.3.6 ./bin/build.sh amd64
#
#     To build Swoole 4.3.6 images for PHP 7.1, 7.2, 7.3 under arm64v8:
#     IMAGE_NAME=phpswoole/swoole SWOOLE_VERSION=4.3.6 ./bin/build.sh arm64v8
#
#     To build Swoole 4.3.6 images for PHP 7.3 under all supported architectures (including amd64 and arm64v8):
#     IMAGE_NAME=phpswoole/swoole SWOOLE_VERSION=4.3.6 PHP_VERSION=7.3 ./bin/build.sh
#
#     To build image "phpswoole/swoole:4.3.6-php7.3":
#     IMAGE_NAME=phpswoole/swoole SWOOLE_VERSION=4.3.6 PHP_VERSION=7.3 ./bin/build.sh default
#     or,
#     IMAGE_NAME=phpswoole/swoole SWOOLE_VERSION=4.3.6 PHP_VERSION=7.3 ./bin/build.sh amd64
#
#     To build image "phpswoole/swoole:4.3.6-php7.3-arm64v8":
#     IMAGE_NAME=phpswoole/swoole SWOOLE_VERSION=4.3.6 PHP_VERSION=7.3 ./bin/build.sh arm64v8
#
#     To build image "phpswoole/swoole":
#     IMAGE_NAME=phpswoole/swoole SWOOLE_VERSION=latest PHP_VERSION=7.4 ./bin/build.sh
#
#     To build image "phpswoole/swoole:latest-dev" for local development and debugging purpose:
#     IMAGE_NAME=phpswoole/swoole SWOOLE_VERSION=latest PHP_VERSION=7.4 DEV_MODE=true ./bin/build.sh
#

set -ex

# Switch to directory where this shell script sits.
pushd `dirname $0` > /dev/null
CURRENT_SCRIPT_PATH=`pwd -P`
# Switch back to current directory.
popd > /dev/null

DEFAULT_ARCHITECTURE=amd64
DEFAULT_BRANCH=master
DEFAULT_TAG=latest

if [[ -z "${IMAGE_NAME}" ]] ; then
    DOCKER_REPO=swoole
    if [[ ! -z "${DOCKER_NAMESPACE}" ]] ; then
        IMAGE_NAME=${DOCKER_NAMESPACE}/${DOCKER_REPO}
    elif [[ ! -z "${DOCKER_ID}" ]] ; then
        IMAGE_NAME=${DOCKER_ID}/${DOCKER_REPO}
    elif [[ ! -z "${TRAVIS_REPO_SLUG}" ]] ; then
        # If we couldn't figure out an image name to be used, we use "your-github-id/swoole" as the image name.
        IMAGE_NAME=${TRAVIS_REPO_SLUG%%/*}/${DOCKER_REPO}
    else
        echo "Error: Docker image name is empty."
        exit 1
    fi
fi
if [[ -z "${SWOOLE_VERSION}" ]] ; then
    if [[ ! -z "${TRAVIS_BRANCH}" ]] ; then
        # If you don't pass in a Swoole version, we will try to extract it from current branch name. e.g.,
        # 1. if current branch name is "4.3.6", we assume Swoole version is "4.3.6";
        # 2. if current branch name is "4.4.6-1", we assume Swoole version is "4.4.6".
        SWOOLE_VERSION=${TRAVIS_BRANCH%%-*}
    else
        echo "Error: Swoole version is empty."
        exit 1
    fi
fi
if [[ "${SWOOLE_VERSION}" == "${DEFAULT_BRANCH}" ]] ; then
    SWOOLE_VERSION=${DEFAULT_TAG}
fi

if [[ ! -z "${ARCHITECTURE}" ]] ; then
    ARCHITECTURES=(${ARCHITECTURE})
else
    if [[ -z "${1}" ]] ; then
        ARCHITECTURES=(amd64 arm64v8 alpine)
    else
        case "$1" in
            ${DEFAULT_ARCHITECTURE}|"default")
                ARCHITECTURES=(${DEFAULT_ARCHITECTURE})
                ;;
            "amd64")
                ARCHITECTURES=(amd64)
                ;;
            "arm64v8")
                ARCHITECTURES=(arm64v8)
                ;;
            "alpine")
                ARCHITECTURES=(alpine)
                ;;
            *)
                echo "Error: First command line parameter must be one of \"php\", \"default\", \"amd64\", \"arm64v8\", or \"alpine\"."
                exit 1
        esac
    fi
fi

IMAGE_CONFIG_FILE="${CURRENT_SCRIPT_PATH}/../config/${SWOOLE_VERSION}.yml"
if [[ ! -f "${IMAGE_CONFIG_FILE}" ]] || [[ "${SWOOLE_VERSION}" == "${DEFAULT_TAG}" ]]; then
    if [[ ! -f "${IMAGE_CONFIG_FILE}" ]] ; then
        # If a version-based configuration file is not found, it means we might want to build an image from a Swoole.
        # branch Most time this is for development purpose so we will use that Swoole branch to build one amd64 image
        # only.
        echo "INFO: configuration file '${IMAGE_CONFIG_FILE}' not found."
    fi

    IMAGE_CONFIG_FILE="${CURRENT_SCRIPT_PATH}/../config/latest.yml"
    IMAGE_TAGS=(${DEFAULT_TAG})
    echo "      Will build image '${IMAGE_NAME}' based on configuration file '${IMAGE_CONFIG_FILE}'."
fi

if egrep -q '^status\:\s*"under development"\s*($|\#)' "${IMAGE_CONFIG_FILE}" ; then
    for ARCHITECTURE in "${ARCHITECTURES[@]}" ; do
        if [[ -z "${IMAGE_TAGS}" ]] ; then
            if [[ -z "${PHP_VERSION}" ]] ; then
                IMAGE_TAGS=(`ls -d1 dockerfiles/${SWOOLE_VERSION}/${ARCHITECTURE}/*/ | cut -f4 -d'/' | xargs  -L1 -I '%' echo ${SWOOLE_VERSION}-php'$'`)
            else
                IMAGE_TAGS=("${SWOOLE_VERSION}-php${PHP_VERSION}")
            fi
        fi

        for IMAGE_TAG in "${IMAGE_TAGS[@]}" ; do
            if [[ "${IMAGE_TAG}" == ${DEFAULT_TAG} ]] ; then
                if [[ -z "${PHP_VERSION}" ]] ; then
                    echo "Error: environment variable '${PHP_VERSION}' is missing or empty."
                    exit 1
                fi
                DOCKERFILE="dockerfiles/${SWOOLE_VERSION}/${ARCHITECTURE}/php${PHP_VERSION}/Dockerfile"
            else
                DOCKERFILE="dockerfiles/${SWOOLE_VERSION}/${ARCHITECTURE}/${IMAGE_TAG:(-6)}/Dockerfile"
            fi

            if [[ -f "${DOCKERFILE}" ]] ; then
                if [[ "${ARCHITECTURE}" == "${DEFAULT_ARCHITECTURE}" ]] ; then
                    IMAGE_TAG_POSTFIX=
                else
                    IMAGE_TAG_POSTFIX="-${ARCHITECTURE}"
                fi
                if [[ "${DEV_MODE}" == "true" ]] ; then
                    IMAGE_TAG_POSTFIX="${IMAGE_TAG_POSTFIX}-dev"
                fi

                IMAGE_FULL_NAME="${IMAGE_NAME}:${IMAGE_TAG}${IMAGE_TAG_POSTFIX}"
                if [ "${DEV_MODE}" == "true" ] && [ "${ARCHITECTURE}" == "alpine" ] ; then
                    echo "NOTICE: Won't build Docker image ${IMAGE_FULL_NAME}."
                    continue 2
                else
                    echo "INFO: Building Docker image ${IMAGE_FULL_NAME}."
                fi

                docker build                         \
                    --build-arg DEV_MODE=${DEV_MODE} \
                    -t "${IMAGE_FULL_NAME}"          \
                    -f "${DOCKERFILE}"               \
                    "${CURRENT_SCRIPT_PATH}/.."

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
