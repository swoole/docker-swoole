---
name: release-swoole-version
description: Use when asked to cut a new Swoole patch release for docker-swoole — prepare its version branch, mark the previous patch released, update config/CHANGELOG.md/README.md, and commit. Releases numbered 6.3.0+ additionally enable igbinary serializer support in extension Redis, in both the new versioned images and the nightly images.
---

# Release a New Swoole Version

## Overview

Reproduces this repo's release flow (see CLAUDE.md "Image Build & Release Flow"): on `master`, first mark the previous patch of the same minor series as `"released"` in its own commit, then in a second commit add the new version's config, regenerate its Dockerfiles, update `CHANGELOG.md` and `README.md`, and finally create (but don't push) a branch named after the new version pointing at that commit.

Scoped to a **patch release within an existing minor series** (e.g. 6.2.2 → 6.2.3). A brand-new minor/major series
(e.g. 6.3.0) needs extra structural work — see "New minor series" below.

Releases numbered **6.3.0 or higher** also carry the igbinary change described in "igbinary support" below, which
touches `config/nightly.yml` as well as the new version's config. Check that section before step 3.

## Steps

1. **Confirm the target.** Find the config with `status: "under development"` in the minor series you're releasing (e.g. `config/6.2.2.yml`) — this is the version being superseded. The new version is its next patch number (e.g. `6.2.3`).

2. **Commit 1 — mark the previous patch released**, on `master`:
   - In `config/<previous>.yml`, change `status: "under development"` to `status: "released"`. Nothing else in that file changes.
   - Commit alone: `mark <previous> as released`.

3. **Prepare the new version's config**: copy `config/<previous>.yml` to `config/<new>.yml`, update the header comment's version references, keep `status: "under development"`, and refresh the values against upstream. For 6.3.0+, also apply the config changes in "igbinary support" below — copying an older config forward will not bring them with it.
   - `php:` — for each PHP minor already listed, the latest published patch. Cross-check against Docker Hub, since `Dockerfile.twig` does `FROM php:{{ php_version }}-...` directly — pinning a patch that isn't published yet breaks the build. Check https://www.php.net/downloads for the latest patch per minor, and confirm the matching tag exists on Docker Hub (`library/php`) before pinning it.
   - `image.composer.version` and each `image.php_extensions.<name>.version` — same lookup as in `update-nightly-images` (Composer via GitHub releases, PECL extensions via pecl.php.net).

4. **Regenerate**: `./bin/generate-dockerfiles.php <new>`.

5. **Update `CHANGELOG.md`**: add `## <new>` right after the `# Swoole <X.Y>` heading (newest patch first), with a `### Changed` list. Include a bullet for every notable change on `master` since the previous patch's release commit that isn't just a routine version bump — check `git log <previous>..HEAD -- Dockerfile.twig Dockerfile.alpine.twig partials/ rootfilesystem/ src/ .github/workflows/` — plus a closing bullet for the Composer/extension bump, e.g. `- Upgrade _Composer_ from 2.10.2 to 2.10.3.`. Bold bullets that call out a real behavior change, the way existing entries do (e.g. `**Enabled option ...**`) — for 6.3.0+ that includes the igbinary bullet given in "igbinary support" below. Add the matching `* [<new>](#new-anchor)` line to the Table of Contents at the top, right above the previous patch's entry.

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

Images for **6.3.0 and later** ship extension _Redis_ built with the igbinary serializer, plus extension _igbinary_
itself. This closes [issue #64](https://github.com/swoole/docker-swoole/issues/64); the full analysis, including the
options that were rejected and why, is in `temp/researches/64-report.md` (local only — `temp/` is gitignored).

Apply it to **both** `config/nightly.yml` and the new version's config. Older series (6.1.x, 6.2.x) are deliberately
left alone: they only carry the earlier change that lists all six phpredis configure options explicitly and enables
the lzf and zstd compressions.

**If `config/nightly.yml` already lists `igbinary`, the nightly half is done** — but still check the new version's
config, since step 3 copies an older config forward and will not bring igbinary with it.

### Pre-flight gates — clear these before editing anything

1. **Pin a genuinely stable igbinary.** PECL marks pre-releases as `<s>stable</s>`, so the usual lookup lies: on
   2026-09-18 `https://pecl.php.net/rest/r/igbinary/stable.txt` returned `3.2.17RC1`, and an unpinned
   `pecl install igbinary` installed that RC. From `https://pecl.php.net/rest/r/igbinary/allreleases.xml`, take the
   newest `<v>` **whose name has no `RC`/`alpha`/`beta` suffix** (3.2.16 at that time). Never leave it unpinned.
   `https://api.github.com/repos/igbinary/igbinary/releases` is the reliable cross-check — its `prerelease` flag is
   correct where PECL's is not.
2. **Confirm igbinary builds on every PHP minor listed in `config/nightly.yml`**, the newest one especially — nightly
   builds them all, so one failure blocks every nightly push for that minor:
   ```bash
   docker run --rm php:8.5-cli-alpine sh -c \
       'apk add --no-cache --virtual .b $PHPIZE_DEPS >/dev/null && pecl install igbinary-3.2.16 >/dev/null 2>&1 && docker-php-ext-enable igbinary && php --ri igbinary'
   ```
   If the newest PHP minor fails, stop and report it instead of shipping a nightly that cannot build.

   **Known blocker as of 2026-09-19 — re-check before assuming it still holds.** igbinary **3.2.16**, the newest
   non-RC release, does **not** compile on PHP 8.5:

   ```
   src/php7/php_igbinary.h:35:10: fatal error: ext/standard/php_smart_string.h: No such file or directory
   ```

   That header was removed in PHP 8.5; support landed in igbinary PRs #403/#404 and ships in **3.2.17RC1**, which
   builds cleanly there — the full chain (igbinary + redis with `enable-redis-igbinary="yes"`) was verified on
   `php:8.5-cli-alpine` and printed `Available serializers => php, json, igbinary`. 3.2.16 is fine on PHP 8.2, 8.3
   and 8.4.

   Since `config/nightly.yml` builds 8.5, this forces a decision before Tier 1 can ship:
   - **Preferred:** wait for 3.2.17 stable and pin that.
   - Ship the RC deliberately, as a maintainer's call, documented in the release notes.
   - Delay the igbinary change to a later patch release while 6.3.0 ships without it.

   Do not silently pin the RC to get past the gate.

### Config changes

In `config/nightly.yml` and `config/<new>.yml`, add `igbinary` **before** `redis` and flip `enable-redis-igbinary`
to `"yes"`:

```yaml
  php_extensions:
    igbinary:
      version: "3.2.16"
      enabled: true
    redis:
      version: "6.3.0"
      configureoptions: "enable-redis-igbinary=\"yes\" enable-redis-lzf=\"yes\" enable-redis-zstd=\"yes\" enable-redis-msgpack=\"no\" enable-redis-lz4=\"no\" with-liblz4=\"yes\""
      enabled: true
```

Order matters: the templates render `php_extensions` in map order, and phpredis's `configure` needs `igbinary.h`,
which `pecl install igbinary` writes to `/usr/local/include/php/ext/igbinary/`. The templates install every extension
first and enable them afterwards, so igbinary does **not** need to be enabled before redis is built — the headers are
enough.

Regenerate both (`./bin/generate-dockerfiles.php <new>` and `./bin/generate-dockerfiles.php nightly`) and confirm the
rendered lines, which are identical for Debian and Alpine:

```
    pecl install igbinary-3.2.16 && \
    pecl install --configureoptions '... enable-redis-igbinary="yes" ...' redis-6.3.0 && \
    docker-php-ext-enable igbinary && \
    docker-php-ext-enable redis && \
```

No `apt`/`apk` package is added on either distro. Expect roughly +0.45 MB per image and ~15 s of build time.

### Tests

Both assertions must be **version-gated at 6.3.0**, mirroring the idiom each script already uses for the 6.2.0+ SSH2
checks. An ungated assertion would break the next patch release of an older series: those are cut from `master`, so
they pick up these scripts while their own config has no igbinary.

- `bin/test-image.sh` — collect the redis patterns first, then add the serializer line conditionally:
  ```bash
  redis_patterns=(
      "Redis Support => enabled"
      "Available compression => lzf, zstd"
  )
  if [[ "$(printf '%s\n' "6.3.0" "${SWOOLE_VERSION}" | sort -V | head -n 1)" == "6.3.0" ]] ; then
      redis_patterns+=("Available serializers => php, json, igbinary")
  fi
  check_command_output \
      "Redis is installed correctly, with the expected serializers and compressions enabled" \
      "${redis_patterns[@]}" \
      -- php --ri redis
  ```
- `bin/test-image.php` — add a gated check next to the existing redis one:
  ```php
  if (version_compare(swoole_version(), '6.3.0', '>=')) {
      check('extension igbinary is available to extension Redis', function (): void {
          expect(extension_loaded('igbinary'), 'extension "igbinary" is not loaded');
          expect(defined('Redis::SERIALIZER_IGBINARY'), 'extension "redis" is built without igbinary serializer support');
      });
  }
  ```

Caveat: nightly images report the Swoole version of `swoole-src` **master**, which lags the released number (nightly
reported 6.2.1 while 6.2.2 was current). Until master reports 6.3.0+, both gates simply skip on nightly — the checks
pass without verifying anything. That is a coverage gap, not a failure; verify nightly by hand until master catches up.

### README

Three edits, all in `README.md`:

1. Feature list — the _Redis_ bullet currently reads "The _igbinary_ and _msgpack_ serializers are not enabled; ...".
   Narrow it to _msgpack_ and note igbinary is enabled from `<new>` onwards.
2. Section "Serializer and Compression Support in Extension Redis" — state that igbinary is enabled from `<new>`,
   update the sample `php --ri redis` output to `Available serializers => php, json, igbinary`, and keep the
   uninstall/reinstall recipe: it is still the answer for older tags, and for msgpack on any tag.
3. Section "Disable Installed/Enabled PHP Extensions" — **this one is mandatory.** Add a warning that on 6.3.0+
   images, removing `docker-php-ext-igbinary.ini` also breaks extension _Redis_, which then fails to load with
   `Cannot load module "redis" because required module "igbinary" is not loaded`. That section currently teaches
   exactly the deletion pattern that triggers it.

### CHANGELOG

Under the new version's `### Changed`, bold the entry — it is a real behavior change — and name **both**
consequences:

```markdown
- **Build PHP extension _Redis_ with igbinary serializer support, and include PHP extension _igbinary_.** Code that
  feature-detects `extension_loaded('igbinary')` now takes its igbinary path; extension _Redis_ also stops loading if
  extension _igbinary_ is disabled afterwards.
```

### Commits

Two commits, in this order:

1. The nightly half on its own, so nightly bakes the change before any versioned image ships it:
   `enable igbinary serializer support for extension Redis in nightly images` — covering `config/nightly.yml`,
   `dockerfiles/nightly/`, and the two test scripts.
2. The versioned half rides in the existing `build Swoole <new> images` commit from step 7, alongside
   `config/<new>.yml`, `dockerfiles/<new>/`, `CHANGELOG.md` and `README.md`.

### Verify before handing off

```bash
./bin/generate-dockerfiles.php nightly && ./bin/generate-dockerfiles.php <new>   # must be idempotent
./vendor/bin/php-cs-fixer fix -q --dry-run
./vendor/bin/phpunit
docker build -t phpswoole/swoole:test -f dockerfiles/<new>/php8.4/alpine/Dockerfile .
./bin/test-image.sh phpswoole/swoole:test
docker run --rm phpswoole/swoole:test php --ri redis | grep Available
```

The last command must print `Available serializers => php, json, igbinary`. Build at least one ZTS and one Debian
variant too — the earlier verification covered Alpine/NTS most heavily — and remember CI also builds arm64, ppc64le
and s390x, which a local build does not exercise.

### What to tell the maintainer

This is the one change in the series that is **not** backward compatible, and it cannot be engineered around:
phpredis calls `igbinary_serialize()`/`igbinary_unserialize()` and registers a hard module dependency, so
`Redis::SERIALIZER_IGBINARY` cannot exist unless igbinary is loaded. Two consequences worth stating in the release
notes rather than discovering in the wild:

- Code that feature-detects `extension_loaded('igbinary')` changes behavior. Symfony Cache's `DefaultMarshaller`
  does exactly this in 4.4 through 7.1 (auto-detection was removed in 7.2), so apps on those versions — including
  6.4 LTS — silently switch cache payload format on redeploy. Symfony reads both formats back, so it is a format
  change rather than an outage. This repo has been burned by the same mechanism before (issue #33, Doctrine).
- `Redis::OPT_SERIALIZER` still defaults to the PHP serializer, so phpredis itself changes no stored-data format.

## New minor series (e.g. 6.2.x → 6.3.0)

Beyond the steps above: `.github/workflows/build_versioned_images.yml`, `build_versioned_alpine_images.yml`, and `build_versioned_dev_images.yml` each hardcode which minor series gets the bare `latest` tag (`if [ ${major_version} == '6.2' ]`) — decide when to flip that to the new series. `CHANGELOG.md` also needs a new `# Swoole <X.Y>` heading and ToC group, and `README.md` needs a new table section. Treat this as a separate, human-reviewed change rather than something to run unattended.
