#!/usr/bin/env bash
#
# Set a GitHub personal access token to the composer configuration at the global level.
#

set -e

if [[ ! -z "${GITHUB_PAT}" ]] ; then
    composer config --global github-oauth.github.com "${GITHUB_PAT}"
fi
