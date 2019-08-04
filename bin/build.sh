#!/usr/bin/env bash
#
# Sample usage:
#     IMAGE_NAME=swoole/swoole SWOOLE_VERSION=4.3.5 ./bin/build.sh
#     IMAGE_NAME=swoole/swoole SWOOLE_VERSION=4.3.5 ./bin/build.sh default
#     IMAGE_NAME=swoole/swoole SWOOLE_VERSION=4.3.5 ./bin/build.sh amd64
#     IMAGE_NAME=swoole/swoole SWOOLE_VERSION=4.3.5 ./bin/build.sh arm64v8
#     or,
#     IMAGE_NAME=swoole/swoole SWOOLE_VERSION=4.3.5 PHP_VERSION=7.3 ./bin/build.sh
#     IMAGE_NAME=swoole/swoole SWOOLE_VERSION=4.3.5 PHP_VERSION=7.3 ./bin/build.sh default
#     IMAGE_NAME=swoole/swoole SWOOLE_VERSION=4.3.5 PHP_VERSION=7.3 ./bin/build.sh amd64
#     IMAGE_NAME=swoole/swoole SWOOLE_VERSION=4.3.5 PHP_VERSION=7.3 ./bin/build.sh arm64v8
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
        IMAGE_NAME=${CI_COMMIT_REF_NAME%%/*}/swoole
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
    fi
    echo "Error: Swoole version is empty."
    exit 1
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

for ARCHITECTURE in "${ARCHITECTURES[@]}" ; do
    IMAGE_CONFIG_FILE="${CURRENT_SCRIPT_PATH}/../config/${SWOOLE_VERSION}.yml"
    if [[ -f "${IMAGE_CONFIG_FILE}" ]] ; then
        if egrep -q '^status\:\s*"under development"\s*($|\#)' "${IMAGE_CONFIG_FILE}" ; then
            if [[ -z "${PHP_VERSION}" ]] ; then
                IMAGE_TAGS=(`ls images/dockerfiles/${ARCHITECTURE}/*.Dockerfile | xargs -n 1 basename -s .Dockerfile`)
            else
                IMAGE_TAGS=("${SWOOLE_VERSION}-php${PHP_VERSION}")
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
                    docker push "${IMAGE_FULL_NAME}"
                else
                    echo "Error: Dockerfile '${DOCKERFILE}' not found."
                    exit 1
                fi
            done
        else
            echo "INFO: Docker image is not under active development. Nothing to do."
        fi
    else
        echo "Error: configuration file '${IMAGE_CONFIG_FILE}' not found."
        exit 1
    fi
done
