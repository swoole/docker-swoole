# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What This Repository Is

Source for the official Swoole Docker images (`phpswoole/swoole` on Docker Hub). A small PHP/Twig generator renders Dockerfiles from per-version YAML configs; GitHub Actions build and push the images. The PHP code here is tooling only — it never ships in the images.

## Commands

```bash
composer update                                  # install dev dependencies (PHP >= 8.3)

./bin/generate-dockerfiles.php 6.2.1             # regenerate Dockerfiles for a Swoole version
./bin/generate-dockerfiles.php nightly           # regenerate nightly Dockerfiles

./vendor/bin/phpunit                             # run unit tests
./vendor/bin/phpunit --filter testGetPhpMajorVersion   # run a single test

./vendor/bin/php-cs-fixer fix                    # fix code style (CI runs with --dry-run)

./bin/example.sh start 01                        # start an example (docker-compose) by number prefix
./bin/example.sh stop 01

./bin/test-image.sh phpswoole/swoole:php8.4      # smoke-test a built image (works for cli/zts/alpine/dev);
                                                 # runs bin/test-image.php inside the container
```

CI (`.github/workflows/tests.yml`) runs `php-cs-fixer fix -q --dry-run` and `phpunit` on every push.

## Architecture

The generation pipeline (all rendering logic lives in `src/Dockerfile.php`):

1. **Config**: `config/<swoole-version>.yml` (e.g. `config/6.2.1.yml`, `config/nightly.yml`) lists the PHP patch versions to build, the Composer version, and PECL extensions with options. The root `config.yml` is an annotated sample documenting the format. The `status` field ("under development" / "released" / "end-of-life") controls whether CI applies floating tags like `latest` — only "under development" versions get them — and whether a push builds the version at all: a released or end-of-life version is only rebuilt on request (see "Image Build & Release Flow").
2. **Templates**: `Dockerfile.twig` (Debian-based, used for both `cli` and `zts` image types) and `Dockerfile.alpine.twig`. For Alpine, the PHP-major-version → Alpine-version mapping is the `ALPINE_VERSIONS` constant in `src/Dockerfile.php` — supporting a new PHP minor version requires adding an entry there.
3. **Output**: rendered to `dockerfiles/<swoole-version>/php<major>/<cli|zts|alpine>/Dockerfile` and **committed to git**. Never hand-edit files under `dockerfiles/` — edit the templates or configs and rerun the generator.

`rootfilesystem/` is copied verbatim into every image: `entrypoint.sh`, helper scripts in `usr/local/bin/` (e.g. `install-swoole.sh`, `autoreload.sh`), boot scripts in `usr/local/boot/`, and supervisord config in `etc/supervisor/`.

`examples/` contains numbered docker-compose examples (referenced from the README) driven by `bin/example.sh`.

## Image Build & Release Flow

This repository does not use git tags; Swoole versions are tracked entirely as git branches (`6.2.1`, `6.1.9`, etc.), and each such branch's tip is what CI builds from.

- **Nightly images**: cron-scheduled workflows build from `dockerfiles/nightly/` on `master` and push `phpswoole/swoole:php<X.Y>[-zts|-alpine|-dev]` tags.
- **Versioned images**: pushing a branch named like a version (e.g. `6.2.1`) triggers the versioned workflows, which build from `dockerfiles/<branch>/` and tag `6.2.1-php8.4`, `6.2-php8.4`, `6.2`, plus `latest` when that branch's `config/<version>.yml` has `status: "under development"`.
- **Work branches**: each release series is maintained on its own branch, and its version branches are cut from it. `master` is only for the nightly images and the 6.3.x series (`6.3.0-rc1`, `6.3.0`, ...); the 6.2.x series lives on branch `6.2.x` (`6.2.3`, `6.2.4`, ...) and the 6.1.x series on branch `6.1.x` (`6.1.10`, `6.1.11`, ...). Older series have work branches too (`6.0.x`, `5.1.x`, `4.8.x`). Each work branch has its own generator, templates, `CHANGELOG.md` and `README.md`; `master`'s `CHANGELOG.md` and `README.md` list the releases of every series, so a release made on another work branch is added to them as well. **This branch is `6.2.x`**: it is only for 6.2.x releases; `dockerfiles/nightly/` here is unused, since nightly images are built from `master`.
- Releasing a new Swoole version means, on its series' work branch (here, `6.2.x`): add `config/<x.y.z>.yml`, run the generator, commit the generated `dockerfiles/<x.y.z>/` together with the updated `CHANGELOG.md`/`README.md` in a single commit (e.g. "build Swoole 6.2.4 images"), then push a branch named `<x.y.z>` pointing at that commit. Skill `release-swoole-version` walks through it. Never bring `master`'s templates, `rootfilesystem/` or generator into this branch as part of a release: `master` carries changes that 6.2.x images don't get.
- A fix for an already-pushed version reaches its images by fast-forwarding the version branch to its work branch (e.g. `6.2.3` to `6.2.x`) and pushing it.
- A released or end-of-life version builds nothing on push. To rebuild one (e.g. to pick up refreshed base images and OS packages, or to publish tags a failed run left out), fast-forward its version branch as above so that it has the current workflows, push it, and once that run is done, start the three versioned workflows by hand on it: `for w in build_versioned_images build_versioned_alpine_images build_versioned_dev_images ; do gh workflow run "${w}.yml" --ref 6.2.4 ; done`. A manual run republishes only the version's own tags (`6.2.4-php8.4`, `6.2.4-php8.4-alpine`, ...), never the floating ones (`6.2-php8.4`, `6.2`, `latest`), which belong to the newer version; on a version under development it does the same as a push. `bin/build-mode.sh` makes that decision. The Dockerfiles pin the PHP patch version, so a rebuild doesn't bring a newer PHP.
- Once the new version's images are confirmed working, a follow-up commit ("mark 6.2.1 as released") flips the *previous* version's `status` in its `config/<x.y.z>.yml` from `"under development"` to `"released"`, so it stops receiving floating tags like `latest` on any future rebuild, and pushes stop rebuilding it.
- Pushing version branches and cutting releases requires push access to this repository, so it's normally done by a maintainer. External contributors proposing a new version should do so via pull request against the series' work branch and let a maintainer push the resulting branch.

### CPU architectures

Images are built for `linux/amd64`, `linux/arm64/v8`, `linux/ppc64le` and `linux/s390x`. Each build workflow under
`.github/workflows/` declares them once, as a `platform` matrix dimension: every platform is built by its own job
and pushed to the registry by digest, and a final `merge` job joins those digests into one multi-architecture tag,
with `bin/publish-image.sh`. A platform that fails to build doesn't hold the others back: they are still published,
and for the failed platform each tag keeps the image it already had, so users of that platform don't lose the tag. A
tag published for the first time has no such image, and goes without that platform. Whether the run fails depends on
the platform: `linux/ppc64le` and `linux/s390x` are optional (marked `optional` in the `platform` matrix and listed in
`OPTIONAL_PLATFORMS`), so a failed build of either doesn't fail the run, only a warning is left. A missing
`linux/amd64` or `linux/arm64/v8` fails the `merge` job, so the run shows it. Either way, rerunning the failed build
jobs (within a week, while the digests of the other platforms are kept) publishes the complete image: rerunning a job
reruns the `merge` jobs that depend on it. When only an optional platform failed, the run is green, so rerun that
platform's build job from the run page rather than the whole run. Until then a floating tag (e.g. `6.2`) can point to
different versions on different platforms; for an optional platform nothing forces that rerun, so check the warnings
of green runs too.

GitHub hosts x86 and arm64 runners, so those two are built natively and the rest are emulated with QEMU. The
functional tests in `bin/test-image.sh` run the image, so they only run on the platforms that are native to their
runner; the others are covered by the assertion at the end of every Dockerfile, which fails the build if extension
Swoole cannot be loaded.

`linux/riscv64` is the only architecture that *could* be added: every base image publishes it (the PHP images for
8.1 through 8.5 on both Debian and Alpine, and the Composer images pulled in via `COPY --from`), and Swoole ships
context-switching assembly for it. It is deliberately left out for now, for two reasons:

- **Build time.** Everything except amd64 and arm64 is emulated through QEMU, since GitHub only offers runners
  for those two architectures. Adding a fifth architecture costs roughly a quarter more runner time per build, and
  riscv64 emulates more slowly than the architectures already in the list.
- **No one has asked for it.** The issue tracker has no request for riscv64, or for any other architecture.

Neither reason is permanent — add it if riscv64 hardware becomes common enough that users ask for it, or if the
build stops being emulated. It is one extra entry in the `platform` matrix of each build workflow, plus the matching
entry in `PLATFORMS`, and in `OPTIONAL_PLATFORMS` if the entry is marked `optional: true` (as an emulated
riscv64 would likely be); `tests/WorkflowTest.php` fails if these disagree. Before adding one, run the
`Probe Platform Support` workflow from the Actions tab: it builds and runs a Debian and an Alpine image for the
architecture without publishing anything, and record what it prints — an unrecorded experiment is why this question
came up twice.

No other architecture can be added. Swoole dropped 32-bit CPUs in 5.1, which rules out `linux/386`,
`linux/arm/v5`, `linux/arm/v6` and `linux/arm/v7`: its `config.m4` still maps those CPUs to assembly files that
were deleted upstream, so `configure` succeeds and the build then fails on a missing source file. `linux/mips64le`
has no PHP base image at all.

Swoole only ships that assembly for some CPUs (x86_64, arm64, riscv64, mips64, loongarch64); on the others its
coroutine scheduler falls back to `ucontext`, which musl does not implement. That is why the Alpine images install
`libucontext` — without it the extension cannot be loaded on ppc64le or s390x. Each Dockerfile ends by asserting
that extension Swoole actually loads, so an architecture that builds but cannot run fails the build instead of
being published.

## Contributing

- Non-release changes (templates, configs, `rootfilesystem/`, examples, tooling, docs) go through pull requests against `master`; `.github/workflows/tests.yml` runs `php-cs-fixer` and `phpunit` on every push and on PRs targeting `master`.
- Match the existing commit message style: short, lowercase, imperative, no trailing period (e.g. `fix ZTS check: constant PHP_ZTS is an integer under PHP 8.3 and earlier`, `upgrade Composer from 2.9.5 to 2.9.7 in latest images`).
