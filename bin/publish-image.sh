#!/usr/bin/env bash
#
# Join the per-platform images pushed by digest into one multi-architecture image, and publish it under the given
# tags. Called by the merge job of every build workflow under .github/workflows/.
#
# How to use this script:
#     ./bin/publish-image.sh --tag <tag> [--tag <tag>...] <digests-directory>
# e.g.,
#     ./bin/publish-image.sh --tag 6.2.5-php8.4 --tag 6.2-php8.4 /tmp/digests
#
# Each file in the digests directory is named after the digest of the image of one platform (without the "sha256:"
# prefix), and contains the name of that platform (e.g., "linux/s390x"). Environment variable PLATFORMS lists the
# platforms every image is built for, separated by spaces.
#
# A platform that failed to build doesn't stop the others from being published. For each tag, the image of a missing
# platform is carried over from the image the tag currently points to, together with its attestations (SBOM and
# provenance, if any), so that users of that platform keep the image they had instead of losing the tag. A tag published
# for the first time has nothing to carry over, and goes without the missing platform. Either way, once everything is
# published, the script exits with a non-zero status code if a missing platform is a required one (not listed in
# OPTIONAL_PLATFORMS), so that the workflow run fails and the platform gets rebuilt: rerunning the failed jobs of the
# run, within the week its digests are kept, publishes the complete image. When only optional platforms are missing, it
# exits with status code 0 after a warning; the run then ends green (their build jobs may fail), so the image is
# completed by rerunning the failed build job of each missing platform, which reruns the merge jobs too. Every tag is
# read before any of them is published, and a tag that can't be read (for any reason other than not existing yet) stops
# them all, since publishing it would take the missing platform away from it.
#
# Environment variables:
#     PLATFORMS          Required. The platforms every image is built for, e.g. "linux/amd64 linux/s390x".
#     OPTIONAL_PLATFORMS The platforms among PLATFORMS whose images may go missing without failing the script, e.g.
#                        "linux/s390x". Defaults to none.
#     IMAGE              The repository to publish to. Defaults to "phpswoole/swoole".
#

set -euo pipefail

IMAGE="${IMAGE:-phpswoole/swoole}"
: "${PLATFORMS:?environment variable PLATFORMS is not set}"
OPTIONAL_PLATFORMS="${OPTIONAL_PLATFORMS:-}"
for platform in ${OPTIONAL_PLATFORMS} ; do
    if [[ " ${PLATFORMS} " != *" ${platform} "* ]] ; then
        echo "::error::optional platform ${platform} is not one of: ${PLATFORMS}"
        exit 1
    fi
done

usage() {
    echo "Usage: $0 --tag <tag> [--tag <tag>...] <digests-directory>" >&2
    exit 2
}

tags=()
digests_dir=""
while [[ $# -gt 0 ]] ; do
    case "$1" in
        --tag)     tags+=("${2:-}") ; shift 2 || usage ;;
        -*)        usage ;;
        *)         digests_dir="$1" ; shift ;;
    esac
done
if [[ ${#tags[@]} -eq 0 || -z "${digests_dir}" ]] ; then
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

# Publish the images built, plus the descriptors in array "carried" (if any), under the given tags.
carried=()
publish() {
    local -a tag_args=()
    local tag
    for tag in "$@" ; do
        tag_args+=(--tag "${IMAGE}:${tag}")
    done
    docker buildx imagetools create "${tag_args[@]}" "${sources[@]}" ${carried[@]+"${carried[@]}"}
    docker buildx imagetools inspect "${IMAGE}:${1}"
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
    # refer to them, one per line of JSON. They are passed to "docker buildx imagetools create", which keeps their
    # platforms and annotations. Their media types are dropped: buildx resolves a descriptor that has no repository as
    # <repository>@<digest> only when it has no media type; with one, it resolves it as <repository>:latest instead,
    # and fails with "invalid size mismatch" unless the descriptor happens to be what tag "latest" points to. jq runs
    # in a command substitution of its own, so that a failure stops the script instead of carrying nothing over.
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
            | ($images[], ($all[] | select(.annotations["vnd.docker.reference.type"] == "attestation-manifest"
                                          and (.annotations["vnd.docker.reference.digest"] as $d | $digests | index($d)))))
            | del(.mediaType)
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

summary="published without a new build of ${missing[*]}"
if [[ -n "${carried_any}" ]] ; then
    summary+="; tags that already had an image of it keep that image"
fi

required_missing=()
for platform in "${missing[@]}" ; do
    if [[ " ${OPTIONAL_PLATFORMS} " != *" ${platform} "* ]] ; then
        required_missing+=("${platform}")
    fi
done
if [[ ${#required_missing[@]} -eq 0 ]] ; then
    echo "::warning::${summary}. Only optional platforms are missing, so this job doesn't fail; to publish the complete image, rerun the failed build job of each missing platform (rerunning a job reruns the merge jobs that depend on it)."
    exit 0
fi
echo "::error::${summary}. Required platform(s) missing: ${required_missing[*]}; rerun the failed jobs of this workflow run to publish the complete image."
exit 1
