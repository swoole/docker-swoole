#!/usr/bin/env bash
#
# Check that base images are published on Docker Hub for every architecture the Swoole images are built for.
# A tag appears on Docker Hub as soon as its first architecture is built; the others follow later, sometimes
# hours later, so a tag that exists is not necessarily complete.
#
# How to use this script:
#     ./bin/check-base-images.sh <image>:<tag>...
# e.g.,
#     ./bin/check-base-images.sh php:8.4.28-cli php:8.4.28-zts php:8.4.28-cli-alpine3.24 composer:2.11.2
#
# Only official images are supported, named without a registry or a namespace ("php:8.4.28-cli", not
# "library/php:8.4.28-cli"). The script needs curl and jq.
#
# It prints one line per image, and exits with:
#     0  every image is published for every architecture;
#     1  at least one image lacks an architecture, or doesn't exist (yet): wait and check again;
#     2  the check itself failed (wrong usage, curl or jq missing, Docker Hub unreachable or rate limiting): nothing
#        is known about the images.
#
# Environment variables:
#     ARCHITECTURES  Optional. Space-separated list of architectures as Docker Hub names them (e.g., "arm64", not
#                    "linux/arm64/v8"). It defaults to the platforms the build workflows declare in PLATFORMS;
#                    tests/WorkflowTest.php fails if the two disagree.
#

set -euo pipefail

if [[ $# -eq 0 ]] ; then
    echo "Usage: $0 <image>:<tag>..." >&2
    exit 2
fi

for command in curl jq ; do
    if ! command -v "${command}" > /dev/null ; then
        echo "ERROR    ${command} is required" >&2
        exit 2
    fi
done

for image in "$@" ; do
    if [[ ! "${image}" =~ ^[a-z0-9._-]+:[A-Za-z0-9._-]+$ ]] ; then
        echo "ERROR    \"${image}\" is not an official image in the form <image>:<tag>" >&2
        exit 2
    fi
done

wanted="$(printf '%s\n' ${ARCHITECTURES:-amd64 arm64 ppc64le s390x} | jq -R . | jq -cs .)"
response="$(mktemp)"
trap 'rm -f "${response}"' EXIT
status=0

for image in "$@" ; do
    name="${image%%:*}"
    tag="${image#*:}"
    if ! code="$(curl -s --max-time 30 -o "${response}" -w '%{http_code}' -A curl "https://hub.docker.com/v2/repositories/library/${name}/tags/${tag}")" ; then
        echo "ERROR    ${image}: cannot reach Docker Hub" >&2
        exit 2
    fi
    case "${code}" in
        200)
            # Entries whose OS is "unknown" are attestations, not images.
            if ! missing="$(jq -r --argjson wanted "${wanted}" '($wanted - ([.images[]? | select(.os == "linux") | .architecture] | unique)) | join(", ")' "${response}" 2> /dev/null)" ; then
                echo "ERROR    ${image}: unexpected response from Docker Hub" >&2
                exit 2
            fi
            if [[ -z "${missing}" ]] ; then
                echo "ok       ${image}"
            else
                echo "MISSING  ${image}: no image yet for ${missing}"
                status=1
            fi
            ;;
        404)
            echo "MISSING  ${image}: the tag doesn't exist"
            status=1
            ;;
        *)
            echo "ERROR    ${image}: Docker Hub answered HTTP ${code}" >&2
            exit 2
            ;;
    esac
done

exit ${status}
