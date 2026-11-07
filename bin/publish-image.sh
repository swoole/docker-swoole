#!/usr/bin/env bash
#
# Join the per-platform images pushed by digest into one multi-architecture image, publish it under the given tags,
# and sign it. Called by the merge job of every build workflow under .github/workflows/.
#
# How to use this script:
#     ./bin/publish-image.sh --version <version> --tag <tag> [--tag <tag>...] <digests-directory>
# e.g.,
#     ./bin/publish-image.sh --version 6.2.5 --tag 6.2.5-php8.4 --tag 6.2-php8.4 /tmp/digests
#
# Each file in the digests directory is named after the digest of the image of one platform (without the "sha256:"
# prefix), and contains the name of that platform (e.g., "linux/s390x"). Environment variable PLATFORMS lists the
# platforms every image is built for, separated by spaces.
#
# A platform that failed to build doesn't stop the others from being published. For each tag, the image of a missing
# platform is carried over from the image the tag currently points to, together with its attestations (SBOM and
# provenance), so that users of that platform keep the image they had instead of losing the tag. A tag published for
# the first time has nothing to carry over, and goes without the missing platform. Either way the script exits with a
# non-zero status code once everything is published, so that the workflow run still fails and the missing platform
# gets rebuilt: rerunning the failed jobs of the run, within the week its digests are kept, publishes the complete
# image. Every tag is read before any of them is published, and a tag that can't be read (for any reason other than
# not existing yet) stops them all, since publishing it would take the missing platform away from it.
#
# Environment variables:
#     PLATFORMS          Required. The platforms every image is built for, e.g. "linux/amd64 linux/s390x".
#     IMAGE              The repository to publish to. Defaults to "phpswoole/swoole".
#     GITHUB_SERVER_URL  Set by GitHub Actions; used in the annotations of the image index.
#     GITHUB_REPOSITORY  Set by GitHub Actions; used in the annotations of the image index.
#     GITHUB_SHA         Set by GitHub Actions; used in the annotations of the image index.
#

set -euo pipefail

IMAGE="${IMAGE:-phpswoole/swoole}"
: "${PLATFORMS:?environment variable PLATFORMS is not set}"

usage() {
    echo "Usage: $0 --version <version> --tag <tag> [--tag <tag>...] <digests-directory>" >&2
    exit 2
}

version=""
tags=()
digests_dir=""
while [[ $# -gt 0 ]] ; do
    case "$1" in
        --version) version="${2:-}" ; shift 2 || usage ;;
        --tag)     tags+=("${2:-}") ; shift 2 || usage ;;
        -*)        usage ;;
        *)         digests_dir="$1" ; shift ;;
    esac
done
if [[ -z "${version}" || ${#tags[@]} -eq 0 || -z "${digests_dir}" ]] ; then
    usage
fi

# Collect the images that were built, as "<platform> <digest>" lines. The directory doesn't exist at all when no
# platform was built, since actions/download-artifact then has nothing to download.
built=()
if [[ -d "${digests_dir}" ]] ; then
    for file in "${digests_dir}"/* ; do
        [[ -f "${file}" ]] || continue
        platform="$(tr -d '[:space:]' < "${file}")"
        if [[ " ${PLATFORMS} " != *" ${platform} "* ]] ; then
            echo "::error::digest file ${file##*/} names platform \"${platform}\", which is not one of: ${PLATFORMS}"
            exit 1
        fi
        built+=("${platform} sha256:${file##*/}")
    done
fi
if [[ ${#built[@]} -eq 0 ]] ; then
    echo "::error::no platform was built, so nothing is published"
    exit 1
fi

missing=()
for platform in ${PLATFORMS} ; do
    found=""
    for entry in "${built[@]}" ; do
        if [[ "${entry%% *}" == "${platform}" ]] ; then
            if [[ -n "${found}" ]] ; then
                echo "::error::more than one digest was built for platform ${platform}"
                exit 1
            fi
            found=1
        fi
    done
    if [[ -z "${found}" ]] ; then
        missing+=("${platform}")
    fi
done

sources=()
for entry in "${built[@]}" ; do
    sources+=("${IMAGE}@${entry#* }")
done

annotations=(
    --annotation "index:org.opencontainers.image.title=Swoole"
    --annotation "index:org.opencontainers.image.source=${GITHUB_SERVER_URL:-https://github.com}/${GITHUB_REPOSITORY:-swoole/docker-swoole}"
    --annotation "index:org.opencontainers.image.revision=${GITHUB_SHA:-}"
    --annotation "index:org.opencontainers.image.version=${version}"
    --annotation "index:org.opencontainers.image.licenses=Apache-2.0"
)

signed=()
sign() {
    local digest="$1" d
    for d in ${signed[@]+"${signed[@]}"} ; do
        [[ "${d}" == "${digest}" ]] && return 0
    done
    cosign sign --yes "${IMAGE}@${digest}"
    signed+=("${digest}")
}

# Publish the images built, plus the descriptors in array "carried" (if any), under the given tags, and sign the image
# index they all point to.
carried=()
publish() {
    local -a tag_args=()
    local tag
    for tag in "$@" ; do
        tag_args+=(--tag "${IMAGE}:${tag}")
    done
    docker buildx imagetools create "${annotations[@]}" "${tag_args[@]}" "${sources[@]}" ${carried[@]+"${carried[@]}"}
    docker buildx imagetools inspect "${IMAGE}:${1}"
    sign "$(docker buildx imagetools inspect "${IMAGE}:${1}" --format '{{ .Manifest.Digest }}')"
}

if [[ ${#missing[@]} -eq 0 ]] ; then
    publish "${tags[@]}"
    exit 0
fi

missing_json="$(printf '%s\n' "${missing[@]}" | jq -R . | jq -s -c .)"

# The name of the platform of a descriptor in an image index. BuildKit records linux/arm64/v8 as plain linux/arm64 (v8
# is the default variant of arm64), so both are normalized to linux/arm64 before platforms are compared.
JQ_PLATFORM='
    def norm: if . == "linux/arm64/v8" then "linux/arm64" else . end;
    def name: (.platform.os + "/" + .platform.architecture + (if .platform.variant then "/" + .platform.variant else "" end)) | norm;
'

# The images of the missing platforms differ from one tag to the next (e.g., "6.2-php8.4" may still point to an
# older version than "6.2.5-php8.4"), so each tag is published on its own. Every tag is read before any of them is
# published, so that a tag that can't be read stops all of them, rather than leaving some published and others not.
errors="$(mktemp)"
trap 'rm -f "${errors}"' EXIT
carried_by_tag=()
for i in "${!tags[@]}" ; do
    tag="${tags[$i]}"
    if ! current="$(docker buildx imagetools inspect --raw "${IMAGE}:${tag}" 2>"${errors}")" ; then
        if grep -q -i -E 'not found|manifest unknown' "${errors}" ; then
            current=""
        else
            # Publishing without carrying the missing platforms over would take them away from an existing tag.
            cat "${errors}" >&2
            echo "::error::failed to read the current image of tag ${tag}, so no tag is published"
            exit 1
        fi
    fi

    # The descriptors of the missing platforms' images in the current index, plus the attestation manifests that
    # refer to them, one per line of JSON. They are passed to "docker buildx imagetools create" as they are, which
    # keeps their platforms and annotations, and resolves them in the repository the tag belongs to. jq runs in a
    # command substitution of its own, so that a failure stops the script instead of carrying nothing over.
    selected=""
    if [[ -n "${current}" ]] ; then
        if ! jq -e 'has("manifests")' <<< "${current}" > /dev/null ; then
            echo "::error::tag ${tag} doesn't point to an image index, so no tag is published"
            exit 1
        fi
        selected="$(jq -c --argjson missing "${missing_json}" "${JQ_PLATFORM}"'
            ($missing | map(norm)) as $missing
            | .manifests as $all
            | [$all[] | select(.platform != null and (name as $n | $missing | index($n)))] as $images
            | ($images | map(.digest)) as $digests
            | $images[], ($all[] | select(.annotations["vnd.docker.reference.type"] == "attestation-manifest"
                                         and (.annotations["vnd.docker.reference.digest"] as $d | $digests | index($d))))
        ' <<< "${current}")"
    fi
    carried_by_tag[$i]="${selected}"
done

carried_any=""
for i in "${!tags[@]}" ; do
    tag="${tags[$i]}"
    carried=()
    while IFS= read -r descriptor ; do
        [[ -n "${descriptor}" ]] && carried+=("${descriptor}")
    done <<< "${carried_by_tag[$i]}"

    if [[ ${#carried[@]} -gt 0 ]] ; then
        carried_any=1
        echo "::warning::tag ${tag}: carrying over the current image of $(jq -r -s "${JQ_PLATFORM}"'map(select(.annotations["vnd.docker.reference.type"] != "attestation-manifest") | name) | join(", ")' <<< "${carried_by_tag[$i]}")"
    else
        echo "::warning::tag ${tag}: published without ${missing[*]}, which has no current image to carry over"
    fi
    publish "${tag}"
done

echo "::error::published without a new build of ${missing[*]}$([[ -n "${carried_any}" ]] && echo "; tags that already had an image of it keep that image"). Rerun the failed jobs of this workflow run to publish the complete image."
exit 1
