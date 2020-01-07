#!/usr/bin/env bash
#
# How to use this script:
#     ./bin/example.sh start   01
#     ./bin/example.sh stop    01
#     ./bin/example.sh restart 01
#

set -e

# Switch to directory where this shell script sits.
pushd `dirname $0` > /dev/null
CURRENT_SCRIPT_PATH=`pwd -P`
# Switch back to current directory.
popd > /dev/null

cd "${CURRENT_SCRIPT_PATH}/.."

if [[ ! -z "${2}" ]] ; then
    dir=`find examples -type d -depth 1 -name "${2}-*"`
    if [[ -z "${dir}" ]] ; then
        echo "Error: Cannot find any subfolder with prefix ${2}- under folder examples/."
        exit 1
    fi

    if [[ ! -f "${dir}/docker-compose.yml" ]] ; then
        echo "Error: File ${dir}/docker-compose.yml not found."
        exit 1
    fi
else
    echo "Error: example name missing."
    echo $"Usage: $0 [restart|start|stop] [example-name]"
    exit 1
fi

case "${1}" in
    "restart")
        docker-compose -f "${dir}/docker-compose.yml" stop
        docker-compose -f "${dir}/docker-compose.yml" up --build --force-recreate -d
        ;;
    "start")
        docker-compose -f "${dir}/docker-compose.yml" up --build --force-recreate -d
        ;;
    "stop")
        docker-compose -f "${dir}/docker-compose.yml" stop
        ;;
    *)
        echo "Invalid command ${1}"
        echo $"Usage: $0 [restart|start|stop] [example-name]"
        exit 1
        ;;
esac
