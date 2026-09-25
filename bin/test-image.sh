#!/usr/bin/env bash
#
# Test a Docker image built from this repository, making sure it is fully functioning before it gets published.
#
# How to use this script:
#     ./bin/test-image.sh <image>
# e.g.,
#     ./bin/test-image.sh phpswoole/swoole:php8.4
#     ./bin/test-image.sh phpswoole/swoole:php8.4-zts
#     ./bin/test-image.sh phpswoole/swoole:php8.4-alpine
#
# The script exits with a non-zero status code if any of the checks fail.
#

set -eo pipefail

# Switch to directory where this shell script sits.
pushd `dirname $0` > /dev/null
CURRENT_SCRIPT_PATH=`pwd -P`
# Switch back to current directory.
popd > /dev/null

if [[ -z "${1}" ]] ; then
    echo "Error: image name missing."
    echo $"Usage: $0 <image>"
    exit 1
fi

IMAGE="${1}"
FAILURES=0

# Check that the output of a command running inside the image contains all given patterns.
#     check_command_output <description> <expected-pattern>... -- <command>...
check_command_output() {
    local description="${1}"; shift

    local patterns=()
    while [[ "${1}" != "--" ]] ; do
        patterns+=("${1}")
        shift
    done
    shift

    local output
    if ! output=$(docker run --rm "${IMAGE}" "$@" 2>&1) ; then
        echo "[FAIL] ${description}: command '$*' failed with output:"
        echo "${output}"
        FAILURES=$((FAILURES + 1))
        return 0
    fi

    local pattern
    for pattern in "${patterns[@]}" ; do
        if ! grep -qF "${pattern}" <<< "${output}" ; then
            echo "[FAIL] ${description}: pattern '${pattern}' not found in the output of command '$*'."
            FAILURES=$((FAILURES + 1))
            return 0
        fi
    done

    echo "[OK] ${description}"
}

echo "Testing Docker image ${IMAGE} ..."

# Features like FTP and SSH2 are supported since Swoole 6.2.0 only.
#
# NOTE: The version is parsed from the extension information instead of a "php -r" command, because the entrypoint
# of non-Alpine images performs word splitting on commands not started with "sh" or "bash" (for ECS support),
# breaking quoted arguments that contain spaces.
SWOOLE_VERSION=$(docker run --rm "${IMAGE}" php --ri swoole | grep -E "^Version => " | head -n 1 | awk '{print $3}' || true)
if [[ -z "${SWOOLE_VERSION}" ]] ; then
    echo "[FAIL] unable to detect the Swoole version in Docker image ${IMAGE}."
    exit 1
fi
echo "Swoole version detected: ${SWOOLE_VERSION}"

patterns=(
    "Swoole => enabled"
    "coroutine => enabled"
    "curl-native => enabled"
    "openssl => OpenSSL"
    "mysqlnd => enabled"
    "coroutine_odbc => enabled"
    "coroutine_pgsql => enabled"
    "coroutine_sqlite => enabled"
    "brotli => "
    "zstd => "
)
if [[ "$(printf '%s\n' "6.2.0" "${SWOOLE_VERSION}" | sort -V | head -n 1)" == "6.2.0" ]] ; then
    patterns+=(
        "FTP support => enabled"
        "SSH2 support => enabled"
        "libssh2 banner => "
    )
fi

# The extension information also proves that the Swoole extension loads without errors (e.g., no missing shared
# libraries), and that it was compiled with the expected features enabled.
check_command_output \
    "Swoole is installed correctly, with expected features enabled" \
    "${patterns[@]}" \
    -- php --ri swoole

# Extension Redis is built with the igbinary serializer since Swoole 6.3.0. Pre-releases of 6.3.0 (e.g. "6.3.0RC1")
# pass this check too, since "sort -V" orders them after "6.3.0".
redis_patterns=(
    "Redis Support => enabled"
    "Available compression => lzf, zstd"
)
if [[ "$(printf '%s\n' "6.3.0" "${SWOOLE_VERSION}" | sort -V | head -n 1)" == "6.3.0" ]] ; then
    redis_patterns+=("Available serializers => php, json, igbinary")
fi
check_command_output \
    "Redis is installed correctly, with the expected serializers and compressions enabled" \
    "${redis_patterns[@]}" \
    -- php --ri redis

check_command_output "Composer works" "Composer version" -- composer --version

echo "Running functional tests inside the image ..."
# SWOOLE_TEST_TIME_FACTOR is forwarded rather than set here: only the caller knows whether the image is being run
# on its own architecture or emulated, and the tests scale their timings by it.
if docker run --rm -e SWOOLE_TEST_TIME_FACTOR -v "${CURRENT_SCRIPT_PATH}/test-image.php":/test-image.php:ro "${IMAGE}" php /test-image.php ; then
    echo "[OK] functional tests"
else
    echo "[FAIL] functional tests"
    FAILURES=$((FAILURES + 1))
fi

if [[ "${FAILURES}" -gt 0 ]] ; then
    echo "Docker image ${IMAGE}: ${FAILURES} check(s) failed."
    exit 1
fi

echo "Docker image ${IMAGE}: all checks passed."
