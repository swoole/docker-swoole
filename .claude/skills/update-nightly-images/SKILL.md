---
name: update-nightly-images
description: Use when asked to update, upgrade, or refresh the nightly Docker images in docker-swoole — bumping Composer, the Redis PECL extension, or other packages in config/nightly.yml to their latest stable releases.
---

# Update Nightly Images

## Overview

Bumps `image.composer.version` and each entry under `image.php_extensions` in `config/nightly.yml` to their latest stable upstream releases, regenerates the nightly Dockerfiles, and commits the result. Nightly's `php:` list only needs each PHP series' major.minor (e.g. `8.4`), not a patch — Docker resolves the latest patch automatically at build time, so it's never edited here.

## Steps

1. Read `config/nightly.yml` to see the current `composer.version` and each `php_extensions.<name>.version`.
2. Look up the latest stable release of each:
   - **Composer**: `https://api.github.com/repos/composer/composer/releases/latest` (`tag_name`), or https://getcomposer.org/download/.
   - **Each PECL extension** (currently just `redis`; `igbinary` joins it at Swoole 6.3.0): `https://pecl.php.net/rest/r/<name>/allreleases.xml`
     — take the newest `<v>` **whose version name carries no `RC`/`alpha`/`beta` suffix**. Do not select on the `<s>`
     stability flag, and do not use `https://pecl.php.net/rest/r/<name>/stable.txt`: PECL labels pre-releases
     `stable`, so both can hand back a release candidate. On 2026-09-18 igbinary's newest release was `3.2.17RC1`,
     flagged `<s>stable</s>`; `stable.txt` returned it, and https://pecl.php.net/package/igbinary listed it with
     State "stable". (`redis` was unaffected, since 6.3.0 is newer than 6.3.0RC1.) Where the project publishes
     GitHub releases, its `prerelease` flag is the reliable cross-check, e.g.
     `https://api.github.com/repos/igbinary/igbinary/releases`.

   If a value is already current, leave it unchanged — don't create a no-op diff for it.
3. Edit `config/nightly.yml` with the new version numbers.
4. Regenerate: `./bin/generate-dockerfiles.php nightly`.
5. Review the diff (`git diff`) — only `config/nightly.yml` and files under `dockerfiles/nightly/` should have changed.
6. Commit on the current branch (normally `master`) — do not push. Match the repo's commit style (short, lowercase, imperative):
   - One package bumped: `upgrade Composer to 2.10.3 in nightly images`.
   - Several bumped together: `upgrade Composer and the Redis extension in nightly images`.

## Notes

- Nightly bumps never touch `CHANGELOG.md` or `README.md` — nightly isn't a tracked release. See `release-swoole-version` for versioned releases, which do.
- Never hand-edit files under `dockerfiles/`; always regenerate them from the config.
