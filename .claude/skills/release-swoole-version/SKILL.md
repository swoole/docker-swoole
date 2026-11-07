---
name: release-swoole-version
description: Use when asked to cut a new Swoole patch release for docker-swoole — prepare its version branch, mark the previous patch released, update config/CHANGELOG.md/README.md, and commit. Each release series has its own work branch (master for 6.3.x, 6.2.x for 6.2, 6.1.x for 6.1); check it out first. This copy, on branch 6.2.x, covers 6.2.x releases only.
---

# Release a New Swoole Version

## Overview

Reproduces this repo's release flow (see CLAUDE.md "Image Build & Release Flow"): on the release series' work branch (see below), first mark the previous patch of the same minor series as `"released"` in its own commit, then in a second commit add the new version's config, regenerate its Dockerfiles, update `CHANGELOG.md` and `README.md`, and finally create (but don't push) a branch named after the new version pointing at that commit.

Scoped to a **patch release within an existing minor series** (e.g. 6.2.3 → 6.2.4).

## Which branch to work on

Each release series is maintained on its own work branch, and its version branches are cut from that branch's tip:

| Release series        | Work branch | Version branches cut from it |
|-----------------------|-------------|------------------------------|
| Nightly images, 6.3.x | `master`    | `6.3.0-rc1`, `6.3.0`, ...    |
| 6.2.x                 | `6.2.x`     | `6.2.3`, `6.2.4`, ...        |
| 6.1.x                 | `6.1.x`     | `6.1.10`, `6.1.11`, ...      |

**This copy of the skill, on branch `6.2.x`, covers 6.2.x releases only.** For any other series, check out that
series' work branch (`git checkout <work-branch> && git pull --ff-only`) and follow the copy of this skill on that
branch: it matches that branch's generator and templates, which differ from this one's (e.g. `master` requires
SHA-256 checksums in every versioned config, and keeps its templates under `templates/`). Never cut a 6.2.x release
from `master`, and never bring `master`'s templates, `rootfilesystem/` or generator into this branch as part of a
release: `master` carries changes (e.g. tini as PID 1, the removed `-dev` packages) that 6.2.x images don't get.

## Steps

1. **Confirm the target and the branch.** Make sure you're on `6.2.x`, up to date with `origin/6.2.x`. Find the config with `status: "under development"` in the 6.2 series (e.g. `config/6.2.3.yml`) — this is the version being superseded. The new version is its next patch number (e.g. `6.2.4`). Check that the new version is tagged in [swoole-src](https://github.com/swoole/swoole-src/releases) before going further.

2. **Commit 1 — mark the previous patch released**, on `6.2.x`:
   - In `config/<previous>.yml`, change `status: "under development"` to `status: "released"`. Nothing else in that file changes.
   - Commit alone: `mark <previous> as released`.

3. **Prepare the new version's config**: copy `config/<previous>.yml` to `config/<new>.yml`, update the header comment's version references, keep `status: "under development"`, and refresh the values against upstream. Don't add extension _igbinary_: it's only in 6.3.0+ images.
   - `php:` — for each PHP minor already listed, the latest published patch. Cross-check against Docker Hub, since `Dockerfile.twig` does `FROM php:{{ php_version }}-...` directly — pinning a patch that isn't published yet breaks the build. Check https://www.php.net/downloads for the latest patch per minor, and confirm the matching tag exists on Docker Hub (`library/php`) before pinning it — for Alpine images, the tag with the Alpine version that `ALPINE_VERSIONS` in `src/Dockerfile.php` maps that PHP minor to (e.g. `php:8.4.27-cli-alpine3.24`).
   - `image.composer.version` and each `image.php_extensions.<name>.version` — Composer via `https://api.github.com/repos/composer/composer/releases/latest`, PECL extensions via `https://pecl.php.net/rest/r/<name>/allreleases.xml`, taking the newest version whose name has no `RC`/`alpha`/`beta` suffix (PECL labels pre-releases "stable", so `stable.txt` can't be trusted).

4. **Regenerate**: `./bin/generate-dockerfiles.php <new>`.

5. **Update `CHANGELOG.md`**: add `## <new>` right after the `# Swoole 6.2` heading (newest patch first), with a `### Changed` list. Include a bullet for every notable change on `6.2.x` since the previous patch's release commit that isn't just a routine version bump — check `git log <previous>..HEAD -- Dockerfile.twig Dockerfile.alpine.twig rootfilesystem/ src/ .github/workflows/` — plus a bullet for each PHP/Composer/extension bump, e.g. `- Upgrade _Composer_ from 2.10.2 to 2.10.3.`. Bold bullets that call out a real behavior change, the way existing entries do (e.g. `**Enabled option ...**`). Add the matching `* [<new>](#new-anchor)` line to the Table of Contents at the top, right above the previous patch's entry.

6. **Update `README.md`**: the version table and the link-reference footer both hardcode the previous patch's tag throughout the 6.2 rows (e.g. every `6.2.3` in those two blocks). Replace `<previous>` with `<new>` there — a scoped find-and-replace is fine, but run `git diff README.md` after and confirm every changed line belongs to one of those two blocks and nothing else moved.

7. **Commit 2 — build the new version**, on `6.2.x`, in one commit covering `config/<new>.yml`, the new `dockerfiles/<new>/` tree, `CHANGELOG.md`, and `README.md`: `build Swoole <new> images`.

8. **Verify**: `./bin/generate-dockerfiles.php <new>` again must leave the tree unchanged; `./vendor/bin/php-cs-fixer fix -q --dry-run` and `./vendor/bin/phpunit` must pass.

9. **Create the version branch** at that commit, without pushing: `git branch <new>`. Pushing it (which triggers the versioned build/release workflows) is left for a maintainer to do deliberately — see CLAUDE.md.

## Re-releasing an existing version

A fix that lands on `6.2.x` after a version branch was pushed does not reach that version's images until the branch
itself is moved — the branch tip is what CI builds from. 6.2.x version branches are pointers into `6.2.x`'s history,
so this is a fast-forward, which `--ff-only` refuses to do if the version branch has diverged:

```bash
git checkout <version> && git merge --ff-only 6.2.x && git checkout 6.2.x
git push origin 6.2.x <version>
```

Never move a 6.2.x version branch to `master`.

That re-runs the three versioned workflows and republishes every tag that version owns, floating ones included.
Only do it while `config/<version>.yml` still says `status: "under development"`; a version marked `"released"`
publishes nothing, which is the point of the flip.

## New minor series

New minor series (e.g. 6.4.0) are cut from `master`, not from this branch; see the copy of this skill on `master`.
