---
name: release-swoole-version
description: Use when asked to cut a new Swoole patch release for docker-swoole — prepare its version branch, mark the previous patch released, update config/CHANGELOG.md/README.md, and commit. Releases numbered 6.3.0+ carry igbinary serializer support in extension Redis, whose pinned igbinary version needs checking on each release.
---

# Release a New Swoole Version

## Overview

Reproduces this repo's release flow (see CLAUDE.md "Image Build & Release Flow"): on `master`, first mark the previous patch of the same minor series as `"released"` in its own commit, then in a second commit add the new version's config, regenerate its Dockerfiles, update `CHANGELOG.md` and `README.md`, and finally create (but don't push) a branch named after the new version pointing at that commit.

Scoped to a **patch release within an existing minor series** (e.g. 6.2.2 → 6.2.3). A brand-new minor/major series
(e.g. 6.3.0) needs extra structural work — see "New minor series" below.

Releases numbered **6.3.0 or higher** carry extension _igbinary_, pinned with a PHP 8.5 override; check "igbinary
support" below during step 3.

## Steps

1. **Confirm the target.** Find the config with `status: "under development"` in the minor series you're releasing (e.g. `config/6.2.2.yml`) — this is the version being superseded. The new version is its next patch number (e.g. `6.2.3`).

2. **Commit 1 — mark the previous patch released**, on `master`:
   - In `config/<previous>.yml`, change `status: "under development"` to `status: "released"`. Nothing else in that file changes.
   - Commit alone: `mark <previous> as released`.

3. **Prepare the new version's config**: copy `config/<previous>.yml` to `config/<new>.yml`, update the header comment's version references, keep `status: "under development"`, and refresh the values against upstream. For 6.3.x, check igbinary's pinned version as described in "igbinary support" below.
   - `php:` — for each PHP minor already listed, the latest published patch. Cross-check against Docker Hub, since `templates/Dockerfile.twig` does `FROM php:{{ php_version }}-...` directly — pinning a patch that isn't published yet breaks the build. Check https://www.php.net/downloads for the latest patch per minor, and confirm the matching tag exists on Docker Hub (`library/php`) before pinning it.
   - `image.composer.version` and each `image.php_extensions.<name>.version` — same lookup as in `update-nightly-images` (Composer via GitHub releases, PECL extensions via pecl.php.net).
   - Checksums, which the build verifies: `image.swoole.sha256` for the source code of the new version (`curl -sSfL https://github.com/swoole/swoole-src/archive/refs/tags/v<new>.tar.gz | shasum -a 256`; download it twice and compare, to make sure GitHub serves it stably), and each `image.php_extensions.<name>.sha256`, a map of versions to checksums (`curl -sSfL https://pecl.php.net/get/<name>-<version>.tgz | shasum -a 256`), with one entry per version the config uses, including those in `version_overrides`. The generator refuses to render a versioned config whose checksums are missing or malformed, so a config copied from a release made before checksums existed (6.2.3 and earlier) needs all of them added.

4. **Regenerate**: `./bin/generate-dockerfiles.php <new>`.

5. **Update `CHANGELOG.md`**: add `## <new>` right after the `# Swoole <X.Y>` heading (newest patch first), with a `### Changed` list. Include a bullet for every notable change on `master` since the previous patch's release commit that isn't just a routine version bump — check `git log <previous>..HEAD -- templates/ rootfilesystem/ src/ .github/workflows/` — plus a closing bullet for the Composer/extension bump, e.g. `- Upgrade _Composer_ from 2.10.2 to 2.10.3.`. Bold bullets that call out a real behavior change, the way existing entries do (e.g. `**Enabled option ...**`) — e.g. a bold bullet if igbinary's pinned version changes (see "igbinary support" below). Add the matching `* [<new>](#new-anchor)` line to the Table of Contents at the top, right above the previous patch's entry.

6. **Update `README.md`**: the version table and the link-reference footer both hardcode the previous patch's tag throughout its minor series' rows (e.g. every `6.2.2` in those two blocks). Replace `<previous>` with `<new>` there — a scoped find-and-replace is fine, but run `git diff README.md` after and confirm every changed line belongs to one of those two blocks and nothing else moved.

7. **Commit 2 — build the new version**, on `master`, in one commit covering `config/<new>.yml`, the new `dockerfiles/<new>/` tree, `CHANGELOG.md`, and `README.md`: `build Swoole <new> images`.

8. **Create the version branch** at that commit, without pushing: `git branch <new>`. Pushing it (which triggers the versioned build/release workflows) is left for a maintainer to do deliberately — see CLAUDE.md.

## Re-releasing an existing version

A fix that lands on `master` after a version branch was pushed does not reach that version's images until the
branch itself is moved — the branch tip is what CI builds from. Version branches are pointers into `master`'s
history, so this is a fast-forward:

```bash
git branch -f <version> master
git push origin <version>
```

That re-runs the three versioned workflows and republishes every tag that version owns, floating ones included.
Only do it while `config/<version>.yml` still says `status: "under development"`; a version marked `"released"`
publishes nothing, which is the point of the flip.

## igbinary support (Swoole 6.3.0 and later)

Images for **6.3.0 and later**, and nightly images, ship extension _Redis_ built with the igbinary serializer, plus
extension _igbinary_ itself ([issue #64](https://github.com/swoole/docker-swoole/issues/64); the analysis is in
`temp/researches/archived/64-report.md`, local only — `temp/` is gitignored). This is already in place in
`config/nightly.yml` and `config/6.3.0-rc1.yml`, so a later 6.3.x config copied forward in step 3 carries it; there is
nothing to enable. Older series (6.1.x, 6.2.x) are deliberately left without it, and the igbinary checks in
`bin/test-image.sh` and `bin/test-image.php` only apply to Swoole 6.3.0+, so patch releases of those series still pass.

What to check in step 3 for a 6.3.x release:

- **igbinary's version.** igbinary 3.2.16, the newest non-RC release when 6.3.0-rc1 shipped, doesn't compile on PHP 8.5
  (it includes `ext/standard/php_smart_string.h`, which PHP 8.5 removed), so the configs pin 3.2.16 and override it
  for PHP 8.5 with the release candidate 3.2.17RC1 — a deliberate maintainer decision:
  ```yaml
      igbinary:
        version: "3.2.16"
        version_overrides:
          "8.5": "3.2.17RC1"
        sha256:
          "3.2.16": "8bf25d465abc7973d9e2c9a3039a5f8eea635b23bc1477017ff3999ff95836da"
          "3.2.17RC1": "91da821443db125282a6aea039f24588dd28ff5d71e8187f6ecc41165bceafbc"
        enabled: true
  ```
  Look for a newer non-RC release (see `update-nightly-images` for why PECL's "stable" flag can't be trusted). Once one
  builds on every PHP version listed, pin it for all of them: drop `version_overrides`, and replace the `sha256` map
  with the new version's checksum. Do the same in `config/nightly.yml`. Until then, point out in the release notes
  that PHP 8.5 images ship an igbinary release candidate — especially for 6.3.0 GA.
- **Order.** `igbinary` must stay listed before `redis`: the templates install PECL extensions in map order, and
  phpredis's `configure` needs igbinary's headers.
- **Verification.** Besides `bin/test-image.sh`, which checks the serializer on 6.3.0+ images,
  `docker run --rm <image> php --ri redis | grep Available` must print `Available serializers => php, json, igbinary`.

This is not backward compatible, and can't be made so: phpredis calls `igbinary_serialize()`/`igbinary_unserialize()`
and registers a hard module dependency, so `Redis::SERIALIZER_IGBINARY` cannot exist unless igbinary is loaded. The
6.3.0-rc1 CHANGELOG entry already states the consequences; keep them in mind for release notes:

- Code that feature-detects `extension_loaded('igbinary')` changes behavior. Symfony Cache's `DefaultMarshaller`
  does exactly this in 4.4 through 7.1 (auto-detection was removed in 7.2), so apps on those versions — including
  6.4 LTS — silently switch cache payload format on redeploy. Symfony reads both formats back, so it is a format
  change rather than an outage. This repo has been burned by the same mechanism before (issue #33, Doctrine).
- Extension _Redis_ fails to load if extension _igbinary_ is disabled afterwards.
- `Redis::OPT_SERIALIZER` still defaults to `Redis::SERIALIZER_NONE`, so phpredis itself changes no stored-data format.

## New minor series (e.g. 6.2.x → 6.3.0)

Beyond the steps above: `.github/workflows/build_versioned_images.yml`, `build_versioned_alpine_images.yml`, and `build_versioned_dev_images.yml` each hardcode which minor series gets the bare `latest` tag (`if [ ${major_version} == '6.2' ]`) — decide when to flip that to the new series. `CHANGELOG.md` also needs a new `# Swoole <X.Y>` heading and ToC group, and `README.md` needs a new table section. Treat this as a separate, human-reviewed change rather than something to run unattended.
