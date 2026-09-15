---
name: release-swoole-version
description: Use when asked to cut a new Swoole patch release for docker-swoole — prepare its version branch, mark the previous patch released, update config/CHANGELOG.md/README.md, and commit.
---

# Release a New Swoole Version

## Overview

Reproduces this repo's release flow (see CLAUDE.md "Image Build & Release Flow"): on `master`, first mark the previous patch of the same minor series as `"released"` in its own commit, then in a second commit add the new version's config, regenerate its Dockerfiles, update `CHANGELOG.md` and `README.md`, and finally create (but don't push) a branch named after the new version pointing at that commit.

Scoped to a **patch release within an existing minor series** (e.g. 6.2.2 → 6.2.3). A brand-new minor/major series (e.g. 6.3.0) needs extra structural work — see "New minor series" below.

## Steps

1. **Confirm the target.** Find the config with `status: "under development"` in the minor series you're releasing (e.g. `config/6.2.2.yml`) — this is the version being superseded. The new version is its next patch number (e.g. `6.2.3`).

2. **Commit 1 — mark the previous patch released**, on `master`:
   - In `config/<previous>.yml`, change `status: "under development"` to `status: "released"`. Nothing else in that file changes.
   - Commit alone: `mark <previous> as released`.

3. **Prepare the new version's config**: copy `config/<previous>.yml` to `config/<new>.yml`, update the header comment's version references, keep `status: "under development"`, and refresh the values against upstream:
   - `php:` — for each PHP minor already listed, the latest published patch. Cross-check against Docker Hub, since `Dockerfile.twig` does `FROM php:{{ php_version }}-...` directly — pinning a patch that isn't published yet breaks the build. Check https://www.php.net/downloads for the latest patch per minor, and confirm the matching tag exists on Docker Hub (`library/php`) before pinning it.
   - `image.composer.version` and each `image.php_extensions.<name>.version` — same lookup as in `update-nightly-images` (Composer via GitHub releases, PECL extensions via pecl.php.net).

4. **Regenerate**: `./bin/generate-dockerfiles.php <new>`.

5. **Update `CHANGELOG.md`**: add `## <new>` right after the `# Swoole <X.Y>` heading (newest patch first), with a `### Changed` list. Include a bullet for every notable change on `master` since the previous patch's release commit that isn't just a routine version bump — check `git log <previous>..HEAD -- Dockerfile.twig Dockerfile.alpine.twig rootfilesystem/ src/ .github/workflows/` — plus a closing bullet for the Composer/extension bump, e.g. `- Upgrade _Composer_ from 2.10.2 to 2.10.3.`. Bold bullets that call out a real behavior change, the way existing entries do (e.g. `**Enabled option ...**`). Add the matching `* [<new>](#new-anchor)` line to the Table of Contents at the top, right above the previous patch's entry.

6. **Update `README.md`**: the version table and the link-reference footer both hardcode the previous patch's tag throughout its minor series' rows (e.g. every `6.2.2` in those two blocks). Replace `<previous>` with `<new>` there — a scoped find-and-replace is fine, but run `git diff README.md` after and confirm every changed line belongs to one of those two blocks and nothing else moved.

7. **Commit 2 — build the new version**, on `master`, in one commit covering `config/<new>.yml`, the new `dockerfiles/<new>/` tree, `CHANGELOG.md`, and `README.md`: `build Swoole <new> images`.

8. **Create the version branch** at that commit, without pushing: `git branch <new>`. Pushing it (which triggers the versioned build/release workflows) is left for a maintainer to do deliberately — see CLAUDE.md.

## New minor series (e.g. 6.2.x → 6.3.0)

Beyond the steps above: `.github/workflows/build_versioned_images.yml`, `build_versioned_alpine_images.yml`, and `build_versioned_dev_images.yml` each hardcode which minor series gets the bare `latest` tag (`if [ ${major_version} == '6.2' ]`) — decide when to flip that to the new series. `CHANGELOG.md` also needs a new `# Swoole <X.Y>` heading and ToC group, and `README.md` needs a new table section. Treat this as a separate, human-reviewed change rather than something to run unattended.
