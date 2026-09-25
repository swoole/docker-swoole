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

CI (`.github/workflows/tests.yml`) runs `php-cs-fixer fix -q --dry-run` and `phpunit` on every push. Besides the generator, `phpunit` tests the workflows (`tests/WorkflowTest.php`): actions referenced by version tag, restricted default permissions, and images published with an SBOM, full provenance, OCI labels and a cosign signature.

The generated Dockerfiles need BuildKit (`ADD --checksum`, `RUN --mount`).

## Architecture

The generation pipeline (all rendering logic lives in `src/Dockerfile.php`):

1. **Config**: `config/<swoole-version>.yml` (e.g. `config/6.2.1.yml`, `config/nightly.yml`) lists the PHP patch versions to build, the Composer version, and PECL extensions with options, plus the SHA-256 checksums that the build verifies the downloads against: `image.swoole.sha256` for the Swoole source tarball (versioned configs only; nightly builds `master`) and a per-version `sha256` map for each PECL extension. The downloads happen in a `sources` stage, fetched with `ADD --checksum` and bind-mounted into the build, so they never end up in an image layer. The root `config.yml` is an annotated sample documenting the format. The `status` field ("under development" / "released" / "end-of-life") controls whether CI applies floating tags like `latest` — only "under development" versions get them.
2. **Templates**, all under `templates/`: `Dockerfile.twig` (Debian-based, used for both `cli` and `zts` image types) and `Dockerfile.alpine.twig`. Blocks shared by both (the downloads, the PECL extensions, the Swoole configure options, the final extension-load check) live in `templates/partials/` and are included by both templates, so a new Swoole option is added once. For Alpine, the PHP-major-version → Alpine-version mapping is the `ALPINE_VERSIONS` constant in `src/Dockerfile.php` — supporting a new PHP minor version requires adding an entry there.
3. **Output**: rendered to `dockerfiles/<swoole-version>/php<major>/<cli|zts|alpine>/Dockerfile` and **committed to git**. Never hand-edit files under `dockerfiles/` — edit the templates or configs and rerun the generator.

`rootfilesystem/` is copied verbatim into every non-Alpine image (Alpine images use the PHP image's entrypoint instead): `entrypoint.sh` (run under `tini` as PID 1), helper scripts in `usr/local/bin/` (e.g. `install-swoole.sh`, `autoreload.sh`), boot scripts in `usr/local/boot/`, and supervisord config in `etc/supervisor/`.

`examples/` contains numbered docker-compose examples (referenced from the README) driven by `bin/example.sh`.

## Image Build & Release Flow

This repository does not use git tags; Swoole versions are tracked entirely as git branches (`6.2.1`, `6.1.9`, etc.), and each such branch's tip is what CI builds from.

- **Nightly images**: cron-scheduled workflows build from `dockerfiles/nightly/` on `master` and push `phpswoole/swoole:php<X.Y>[-zts|-alpine|-dev]` tags.
- **Versioned images**: pushing a branch named like a version (e.g. `6.2.1`) triggers the versioned workflows, which build from `dockerfiles/<branch>/` and tag `6.2.1-php8.4`, `6.2-php8.4`, `6.2`, plus `latest` when that branch's `config/<version>.yml` has `status: "under development"`.
- Releasing a new Swoole version means, on `master`: add `config/<x.y.z>.yml`, run the generator, commit the generated `dockerfiles/<x.y.z>/` together with the updated `CHANGELOG.md`/`README.md` in a single commit (e.g. "build Swoole 6.2.2 images"), then push a branch named `<x.y.z>` pointing at that commit.
- Once the new version's images are confirmed working, a follow-up commit ("mark 6.2.1 as released") flips the *previous* version's `status` in its `config/<x.y.z>.yml` from `"under development"` to `"released"`, so it stops receiving floating tags like `latest` on any future rebuild.
- Pushing version branches and cutting releases requires push access to this repository, so it's normally done by a maintainer. External contributors proposing a new version should do so via pull request against `master` and let a maintainer push the resulting branch.

### CPU architectures

Images are built for `linux/amd64`, `linux/arm64/v8`, `linux/ppc64le` and `linux/s390x`. Each build workflow under
`.github/workflows/` declares them once, as a `platform` matrix dimension: every platform is built by its own job
and pushed to the registry by digest, and a final `merge` job joins those digests into one multi-architecture tag.
That job refuses to publish unless it collected a digest from every platform, so a failed architecture cannot be
silently dropped from a tag.

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
`EXPECTED_DIGESTS` (`tests/WorkflowTest.php` fails if those two disagree). Before adding one, run the
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
