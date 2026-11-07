#!/usr/bin/env bash
#
# Print how a run of a versioned build workflow treats the version branch it runs on, as one word:
#     release  The version is under development: build it, and publish its version tags and floating tags (e.g.
#              "6.2-php8.4", "6.2", "latest").
#     rebuild  The version is released or end-of-life, and the workflow was started manually: build it, and publish
#              only its version tags (e.g. "6.2.4-php8.4"). The floating tags belong to the newer version, so they're
#              left alone.
#     skip     The version is released or end-of-life, and the run was triggered by a push: build nothing. Released
#              versions are only rebuilt on request.
# Called by the build and merge jobs of every versioned workflow under .github/workflows/.
#
# How to use this script:
#     ./bin/build-mode.sh
#
# The version is the name of the branch in GITHUB_REF, and its status is read from config/<version>.yml. A manual
# run on anything but a version branch with a configuration file, or on a version with an unknown status, fails.
#
# Environment variables:
#     GITHUB_REF         Required. Set by GitHub Actions, e.g. "refs/heads/6.2.4".
#     GITHUB_EVENT_NAME  Required. Set by GitHub Actions: "push", or "workflow_dispatch" for a manual run.
#

set -euo pipefail

: "${GITHUB_REF:?environment variable GITHUB_REF is not set}"
: "${GITHUB_EVENT_NAME:?environment variable GITHUB_EVENT_NAME is not set}"

version="${GITHUB_REF##*/}"
config="$(dirname "$0")/../config/${version}.yml"

if [[ "${GITHUB_EVENT_NAME}" == "workflow_dispatch" ]] ; then
    # The pattern matches the branch filters of the workflows. "${GITHUB_REF##*/}" alone would also accept a branch
    # like "feature/6.2.4", or a tag.
    if [[ ! "${GITHUB_REF}" =~ ^refs/heads/[4-9]\.[0-9]+\.[0-9]+(-?[a-zA-Z].*)?$ ]] || [[ ! -f "${config}" ]] ; then
        echo "::error::run this workflow on a version branch (e.g. 6.2.4), not on ${GITHUB_REF}" >&2
        exit 1
    fi
fi

status=""
if [[ -f "${config}" ]] ; then
    status="$(sed -n -E 's/^status:[[:space:]]*"([^"]*)"[[:space:]]*(#.*)?$/\1/p' "${config}")"
fi

case "${status}" in
    "under development")
        echo "release"
        ;;
    "released"|"end-of-life")
        if [[ "${GITHUB_EVENT_NAME}" == "workflow_dispatch" ]] ; then
            echo "rebuild"
        else
            echo "skip"
        fi
        ;;
    *)
        if [[ "${GITHUB_EVENT_NAME}" == "workflow_dispatch" ]] ; then
            echo "::error::config/${version}.yml has no valid status (\"under development\", \"released\" or \"end-of-life\")" >&2
            exit 1
        fi
        # Pushes have always skipped such a version rather than failed.
        echo "skip"
        ;;
esac
