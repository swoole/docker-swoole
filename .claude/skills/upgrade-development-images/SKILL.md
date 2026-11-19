---
name: upgrade-development-images
description: Use when asked to upgrade, update or refresh the latest ("under development") images of docker-swoole across its active branches — bumping PHP patch versions, Composer, or PECL extensions (Redis, igbinary) in the nightly images and in the newest 6.3.x, 6.2.x and 6.1.x images. Not for building images of a new Swoole release.
---

# Upgrade the Images under Development

## Overview

Brings every image that is still `status: "under development"` up to the latest upstream releases of PHP, Composer
and the PECL extensions, on each active work branch: edit the config, regenerate the Dockerfiles, commit. Nothing is
pushed. The upgrade only starts once the new base images are on Docker Hub for every architecture (step 4).

| Work branch | Images it covers                                  | Config to edit                                   |
|-------------|---------------------------------------------------|--------------------------------------------------|
| `master`    | nightly, and the 6.3.x version under development  | `config/nightly.yml`, e.g. `config/6.3.0-rc1.yml` |
| `6.2.x`     | the 6.2.x version under development               | e.g. `config/6.2.5.yml`                          |
| `6.1.x`     | the 6.1.x version under development               | e.g. `config/6.1.12.yml`                         |

If this table no longer matches CLAUDE.md ("Work branches"), follow CLAUDE.md and update the table.

**Version numbers in this document are examples.** The versions under development change with every release:
always read them from the configs, as in step 2, never from this document.

**Scope: existing configs only.** This skill never adds a `config/<version>.yml`, never changes a `status`, and never
touches a version marked `"released"` or `"end-of-life"`. If upstream has tagged a newer Swoole than the version
under development of a series — one newer patch or several — and that newer version has no config on the series'
work branch yet, leave it alone and mention it in the report: that is a release, done with skill `release-swoole-version`. Other branches (`6.0.x`, `5.1.x`, `4.8.x`, ...)
are not active; skip them.

## Steps

1. **Start clean.** `git status` must show a clean working copy; `git fetch origin`. The work switches between the
   work branches, so stop and ask if there are uncommitted changes.

2. **Find the configs.** On each work branch (`git checkout <branch> && git pull --ff-only`), list the configs under
   development **of that branch's own series**, e.g. on `6.2.x`:
   `grep -l '^status: "under development"' config/6.2.*.yml` (`config/6.3.*.yml` on `master`, `config/6.1.*.yml` on
   `6.1.x`). Normally there is exactly one, the newest of the series. If there is none, skip the branch and say so; if
   there are several, upgrade the newest only and report the others. `config/nightly.yml` (on `master` only) is
   always included.

   **Ignore the configs of other series on a branch.** Each work branch still carries old copies of the other
   series' configs, some of them left at "under development" (e.g. 6.2.x and 6.1.x configs on
   `master`). They are stale: nothing is built from them, and the real ones live on their own work branch.

   Note for each config: its `php:` list, `image.composer.version`, and each `image.php_extensions.<name>.version`
   (with any `version_overrides`).

3. **Look up the latest releases, once for all branches.**
   - **PHP** (versioned configs only): for each minor listed in a config, the latest patch:
     `curl -s 'https://www.php.net/releases/index.php?json&version=8.4' | jq -r .version`. Never add or drop a minor;
     a minor that no longer gets releases (e.g. 8.1) keeps its patch. Nightly lists minors only (`"8.4"`), which
     Docker resolves at build time, so nightly's `php:` list is never edited.
   - **Composer**: `tag_name` of `https://api.github.com/repos/composer/composer/releases/latest`.
   - **PECL extensions** (`redis` everywhere; `igbinary` on `master` only):
     `https://pecl.php.net/rest/r/<name>/allreleases.xml` — the newest `<v>` whose name has no `RC`/`alpha`/`beta`
     suffix. Don't trust PECL's stability flag or `stable.txt`; see skill `update-nightly-images` for why, and for
     igbinary's pin (a stable release, overridden by a release candidate on the newest PHP) and when to drop it.

   All branches get the same Composer and the same extension versions, unless something doesn't build on a branch's
   PHP versions. A new **major or minor** release of an extension (e.g. Redis 6.3 → 7.0) is not a routine bump:
   check its `package.xml` for new or removed configure options against `configureoptions` in the config, and ask
   before applying it.

   **If nothing is newer than what the configs already have, stop here**: edit nothing, regenerate nothing, create
   no commit, push nothing. Go back to `master` and report that every image under development is current (step 8).
   The same holds per branch: a branch whose configs are all current is left exactly as it is.

4. **Prerequisite — the base images must be published for every architecture.** Do this once, for all branches,
   before editing anything. The Dockerfiles start `FROM php:<patch>-...` and copy Composer from
   `composer:<version>`, and the images are built for `linux/amd64`, `linux/arm64/v8`, `linux/ppc64le` and
   `linux/s390x`. On the day of a PHP release the Docker images trail behind: a tag appears as soon as its first
   architecture is built, and the others follow hours later, sometimes a day or two — and not together: `-cli` can
   be complete while `-zts` and the Alpine tag are not. A tag that exists is therefore not enough; each one must
   carry all four architectures.

   **What to check**: for every new PHP patch, on every branch that will pin it, three tags — `<patch>-cli`,
   `<patch>-zts` and `<patch>-cli-alpine<alpine>` — and, for a new Composer version, `composer:<version>`.
   `<alpine>` is the entry for that PHP minor in `ALPINE_VERSIONS` of `src/Dockerfile.php`, which each branch
   keeps its own copy of; read all three without switching branches, and check every Alpine tag they give:

   ```bash
   for b in master 6.2.x 6.1.x ; do echo "== $b" ; git show "origin/$b:src/Dockerfile.php" | grep -A12 'ALPINE_VERSIONS = ' ; done
   ```

   **Run the check from `master`** (`git checkout master`; step 2 may have left another branch checked out):

   ```bash
   ./bin/check-base-images.sh php:8.4.28-cli php:8.4.28-zts php:8.4.28-cli-alpine3.24 composer:2.11.2
   ```

   It prints `ok` or `MISSING` (with the architectures still missing) per image. Its exit status decides what
   happens next:

   - **0 — everything is complete**: continue with step 5.
   - **1 — something is `MISSING`: the upgrade waits.** Edit nothing, commit nothing, on any branch — not even the
     parts that are ready (e.g. Composer, or the PHP minors whose images are complete); a partial upgrade would
     mean rebuilding every image twice. Stay on `master` and report which images lack which architectures
     (step 8). Then either the skill is run again later, or, when asked to wait for the images, wait as described
     below. Never work around a missing image by pinning the patch anyway: the build of that architecture would
     fail, and for `amd64` or `arm64` that fails the whole run.
   - **2 — the check itself failed** (Docker Hub unreachable or rate limiting, wrong usage): nothing is known
     about the images. Don't treat it as `MISSING`, and don't go on either: try again in a few minutes, and
     report it if it keeps failing.

   **An Alpine tag that "doesn't exist" while `<patch>-cli` is complete is not a matter of waiting.** The PHP
   images drop older Alpine versions over time, so `<patch>-cli-alpine<alpine>` may never be published; waiting
   would never end. Check which Alpine versions the patch is published for
   (`curl -s 'https://hub.docker.com/v2/repositories/library/php/tags?name=<patch>-cli-alpine&page_size=20' | jq -r '.results[].name'`),
   and report that `ALPINE_VERSIONS` needs a newer Alpine version for that PHP minor. That is a change of its
   own (e.g. `upgrade Alpine from 3.23 to 3.24 for PHP 8.2+`), to be made first and not as part of this skill.

   **Waiting, when asked to.** Don't block on a foreground `sleep`: run the check in a background loop that ends
   once everything is complete, and that gives up after two days, e.g.

   ```bash
   for i in $(seq 1 96) ; do ./bin/check-base-images.sh <the same list> ; [[ $? -ne 1 ]] && break ; sleep 1800 ; done
   ```

   When the loop ends, look at the last result: on exit 0 **redo step 3 first** — a newer Composer, extension or
   PHP patch may have been released meanwhile, and its images need the same check — then continue with step 5. If
   the images are still missing after two days, stop waiting and report it.

5. **Apply, one work branch at a time** — `6.1.x`, then `6.2.x`, then `master` (last, because it also records the
   other branches' changes; see step 6). On each branch, for each config from step 2:
   - Edit the versions that changed. Leave current values untouched — no no-op diffs.
   - **Checksums, `master` only.** Configs on `master` carry a `sha256` map per PECL extension, keyed by version:
     add the new version's checksum (`curl -sSfL https://pecl.php.net/get/<name>-<version>.tgz | shasum -a 256`) and
     drop the entries of versions no longer used. `image.swoole.sha256` doesn't change: the Swoole version doesn't.
     Configs on `6.2.x` and `6.1.x` have no checksums; don't add any.
   - Update a comment in the config that names the old version (e.g. "declared by redis-6.3.0's package.xml").
   - Regenerate with that branch's own generator: `./bin/generate-dockerfiles.php <version>` (and
     `./bin/generate-dockerfiles.php nightly` on `master`). Never hand-edit `dockerfiles/`.
   - `git diff --stat`: only `config/<version>.yml` and `dockerfiles/<version>/` (plus `CHANGELOG.md`, step 6) may
     have changed.
   - `./vendor/bin/phpunit` and `./vendor/bin/php-cs-fixer fix -q --dry-run ; echo $?` must pass.

6. **CHANGELOG.md — only for Composer and extensions, only for versioned images.**
   - A PHP patch bump is not recorded. Nightly changes are not recorded. `README.md` is never touched: it lists the
     version, which doesn't change.
   - A Composer or extension bump goes under the version's own heading, in a `### Changed` list (create it if the
     entry is still empty): `- Upgrade _Composer_ from 2.10.3 to 2.11.1.` "From" is the version the **previous
     release of the series** shipped, so if the entry already has such a bullet, change only its "to" version
     instead of adding a second one.
   - `master`'s `CHANGELOG.md` lists the releases of every series. After a 6.2.x or 6.1.x entry changes on its own
     branch, make the same change to that entry on `master`.

7. **Commit, one commit per work branch; don't push.** Stage the config, the regenerated Dockerfiles and
   `CHANGELOG.md` together. Commit style: short, lowercase, imperative, no trailing period, naming what was bumped
   and where:
   - `upgrade PHP to latest releases in 6.2.5 images`
   - `upgrade PHP and Composer to latest releases in 6.1.12 images`
   - `upgrade Composer from 2.11.1 to 2.11.2 in nightly and 6.3.0-rc1 images` (on `master`, nightly and 6.3.x go
     in one commit)
   - on `master`, for the entries of the other series: `update CHANGELOG for Swoole 6.2.5 and 6.1.12 images`, as
     its own commit.

   Never create an empty or no-op commit: a branch with nothing to upgrade gets no commit, and if regenerating
   leaves `git status` clean, there is nothing to commit either.

8. **Finish on `master`** (`git checkout master`) and report, per branch: what was bumped from which version to
   which, what was already current, anything held back (a major extension release, a newer Swoole tag
   upstream), base images still missing if the run stopped at step 4, and the commits made.

## What happens next (not part of this skill)

If the skill made no commit, there is nothing to push and no version branch to move; skip this section.

The commits change nothing until they are pushed, and a versioned image is only rebuilt when its version branch
moves. Leave both to the maintainer, or do them when asked:

```bash
git push origin master 6.2.x 6.1.x
# then, per upgraded version: fast-forward its version branch to its work branch and push it
git push origin origin/<work-branch>:refs/heads/<version>    # e.g. origin/6.2.x:refs/heads/6.2.5
```

Nightly images pick the change up on their next scheduled build. See "Re-releasing an existing version" in skill
`release-swoole-version`.

## Common mistakes

| Mistake | Instead |
|---------|---------|
| Creating a config for a Swoole version upstream has tagged but this repo hasn't released | Report it; a new version is a release (`release-swoole-version`) |
| Upgrading a `"released"` config so that it "stays current" | Released versions keep their pinned versions; they are only rebuilt on request |
| Applying `master`'s config or Dockerfiles to `6.2.x`/`6.1.x` | Edit each branch's own config and run its own generator; no igbinary, no checksums there |
| Pinning a PHP patch because its Docker tag exists | A tag can exist with only some architectures; run `./bin/check-base-images.sh` and require all four |
| Upgrading what is ready (Composer, some PHP minors) while other base images are missing | Stop and wait; upgrade everything in one go once all images are complete |
| Waiting for an Alpine tag that will never come | If `<patch>-cli` is complete and the Alpine tag doesn't exist, `ALPINE_VERSIONS` needs a newer Alpine; report it |
| Reading exit status 2 of the check as "missing" (or as "fine") | 2 means the check failed; retry, and report if it keeps failing |
| Taking PECL's "stable" release | Take the newest version without an `RC`/`alpha`/`beta` suffix |
| Upgrading a config of another series (e.g. a 6.2.x config on `master`) because it says "under development" | It's a stale copy; a series' configs are only upgraded on its own work branch |
| Committing or pushing when everything is already current | No change, no commit, no push: just report it |
| Editing `README.md` | Not needed: the version it lists doesn't change |
