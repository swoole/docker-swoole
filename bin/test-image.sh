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

check_command_output "Redis is installed correctly" "Redis Support => enabled" -- php --ri redis

check_command_output "Composer works" "Composer version" -- composer --version

# The functional tests run twice: once under the default seccomp profile of Docker, which blocks the io_uring
# syscalls (exercising the fallback code paths of Swoole), and once with io_uring allowed (exercising the io_uring
# code paths, when Swoole is compiled with io_uring support). Both runs must pass.
for mode in "default" "unconfined" ; do
    if [[ "${mode}" == "unconfined" ]] ; then
        security_opts=(--security-opt seccomp=unconfined)
    else
        security_opts=()
    fi

    echo "Running functional tests inside the image (seccomp profile: ${mode}) ..."
    if docker run --rm "${security_opts[@]}" -v "${CURRENT_SCRIPT_PATH}/test-image.php":/test-image.php:ro "${IMAGE}" php /test-image.php ; then
        echo "[OK] functional tests (seccomp profile: ${mode})"
    else
        echo "[FAIL] functional tests (seccomp profile: ${mode})"
        FAILURES=$((FAILURES + 1))
    fi
done

if [[ "${FAILURES}" -gt 0 ]] ; then
    echo "Docker image ${IMAGE}: ${FAILURES} check(s) failed."
    exit 1
fi

echo "Docker image ${IMAGE}: all checks passed."
