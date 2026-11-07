<?php

declare(strict_types=1);

namespace Swoole\Tests\Docker;

use PHPUnit\Framework\TestCase;

/**
 * Class PublishImageTest
 *
 * Runs bin/publish-image.sh against a fake "docker" command, which records how it was called and plays back the image
 * indexes that tags currently point to.
 *
 * @internal
 * @coversNothing
 */
class PublishImageTest extends TestCase
{
    private const PLATFORMS = 'linux/amd64 linux/arm64/v8 linux/ppc64le linux/s390x';

    private string $dir;

    /** Value of environment variable OPTIONAL_PLATFORMS passed to the script. */
    private string $optional = '';

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/publish-image-' . bin2hex(random_bytes(6));
        foreach (['bin', 'digests', 'raw', 'created'] as $sub) {
            mkdir("{$this->dir}/{$sub}", 0777, true);
        }

        // Each call is recorded as one line of JSON. "inspect --raw" plays back raw/<tag> (or raw/<tag>.error, which
        // makes it fail with that message); "inspect --format" returns a digest derived from the sources the tag was
        // created from, so that identical indexes get identical digests.
        file_put_contents("{$this->dir}/bin/docker", <<<'BASH'
            #!/usr/bin/env bash
            set -eu
            jq -cn '$ARGS.positional' --args -- "$@" >> "${FAKE_DIR}/calls.jsonl"
            [[ "$1 $2" == "buildx imagetools" ]] || exit 99
            case "$3" in
                create)
                    shift 3
                    tags=() ; sources=()
                    while [[ $# -gt 0 ]] ; do
                        case "$1" in
                            --annotation) shift 2 ;;
                            --tag) tags+=("${2#*:}") ; shift 2 ;;
                            *) sources+=("$1") ; shift ;;
                        esac
                    done
                    digest="sha256:$(printf '%s\n' "${sources[@]}" | shasum -a 256 | cut -d' ' -f1)"
                    for t in "${tags[@]}" ; do echo "${digest}" > "${FAKE_DIR}/created/${t}" ; done
                    ;;
                inspect)
                    if [[ "$4" == "--raw" ]] ; then
                        tag="${5#*:}"
                        if [[ -f "${FAKE_DIR}/raw/${tag}.error" ]] ; then cat "${FAKE_DIR}/raw/${tag}.error" >&2 ; exit 1 ; fi
                        if [[ -f "${FAKE_DIR}/raw/${tag}" ]] ; then cat "${FAKE_DIR}/raw/${tag}" ; exit 0 ; fi
                        echo "ERROR: ${5}: not found" >&2 ; exit 1
                    fi
                    tag="${4#*:}"
                    if [[ "${5:-}" == "--format" ]] ; then cat "${FAKE_DIR}/created/${tag}" ; else echo "Name: ${4}" ; fi
                    ;;
                *) exit 98 ;;
            esac
            BASH);
        chmod("{$this->dir}/bin/docker", 0755);
    }

    protected function tearDown(): void
    {
        exec('rm -rf ' . escapeshellarg($this->dir));
    }

    public function testPublishesAllPlatformsUnderAllTagsAtOnce(): void
    {
        $this->built(['linux/amd64' => 'a1', 'linux/arm64/v8' => 'b1', 'linux/ppc64le' => 'c1', 'linux/s390x' => 'd1']);

        [$status, $output, $calls] = $this->run_('--tag', '6.2.5-php8.4', '--tag', '6.2-php8.4');

        self::assertSame(0, $status, $output);
        $creates = $this->creates($calls);
        self::assertCount(1, $creates);
        self::assertSame(['6.2.5-php8.4', '6.2-php8.4'], $creates[0]['tags']);
        self::assertEqualsCanonicalizing($this->refs('a1', 'b1', 'c1', 'd1'), $creates[0]['sources']);
        self::assertStringNotContainsString('::error::', $output);
        self::assertSame([], $this->inspectsRaw($calls), 'nothing needs to be carried over');
    }

    public function testCarriesTheMissingPlatformOverFromTheCurrentImageOfEachTag(): void
    {
        $this->built(['linux/amd64' => 'a2', 'linux/arm64/v8' => 'b2', 'linux/ppc64le' => 'c2']);
        // "6.2-php8.4" currently points to the previous release, whose s390x image is kept; "6.2.5-php8.4" doesn't
        // exist yet.
        $this->current('6.2-php8.4', $this->index(['linux/amd64' => 'old-a', 'linux/arm64/v8' => 'old-b', 'linux/ppc64le' => 'old-c', 'linux/s390x' => 'old-d']));

        [$status, $output, $calls] = $this->run_('--tag', '6.2.5-php8.4', '--tag', '6.2-php8.4');

        self::assertSame(1, $status, 'the run must still fail when a platform is missing');
        self::assertStringContainsString('::error::published without a new build of linux/s390x', $output);

        $creates = $this->creates($calls);
        self::assertCount(2, $creates, 'each tag is published on its own');
        [$new, $floating] = $creates;

        self::assertSame(['6.2.5-php8.4'], $new['tags']);
        self::assertEqualsCanonicalizing($this->refs('a2', 'b2', 'c2'), $new['sources']);

        self::assertSame(['6.2-php8.4'], $floating['tags']);
        $descriptors = array_values(array_filter($floating['sources'], fn (string $s): bool => str_starts_with($s, '{')));
        self::assertEqualsCanonicalizing($this->refs('a2', 'b2', 'c2'), array_values(array_diff($floating['sources'], $descriptors)));
        self::assertCount(2, $descriptors, 'the s390x image and its attestation manifest are carried over');
        $descriptors = array_map(fn (string $d): array => json_decode($d, true), $descriptors);
        self::assertSame(['sha256:old-d', 'sha256:att-old-d'], array_column($descriptors, 'digest'));
        self::assertSame(['os' => 'linux', 'architecture' => 's390x'], $descriptors[0]['platform']);
        self::assertSame('sha256:old-d', $descriptors[1]['annotations']['vnd.docker.reference.digest'], 'the attestation keeps its annotations');
    }

    /**
     * BuildKit records platform linux/arm64/v8 as plain linux/arm64 in the image index, so a failed arm64/v8 build must
     * still find the arm64 image the tag has.
     */
    public function testCarriesOverArm64WhichTheIndexRecordsWithoutItsVariant(): void
    {
        $this->built(['linux/amd64' => 'a3', 'linux/ppc64le' => 'c3', 'linux/s390x' => 'd3']);
        $this->current('php8.4', $this->index(['linux/amd64' => 'old-a', 'linux/arm64/v8' => 'old-b', 'linux/ppc64le' => 'old-c', 'linux/s390x' => 'old-d']));

        [$status, , $calls] = $this->run_('--tag', 'php8.4');

        self::assertSame(1, $status);
        $descriptors = array_map(
            fn (string $d): array => json_decode($d, true),
            array_values(array_filter($this->creates($calls)[0]['sources'], fn (string $s): bool => str_starts_with($s, '{'))),
        );
        self::assertSame(['sha256:old-b', 'sha256:att-old-b'], array_column($descriptors, 'digest'));
    }

    public function testCarriesOverEveryMissingPlatform(): void
    {
        $this->built(['linux/amd64' => 'a6', 'linux/arm64/v8' => 'b6']);
        $this->current('php8.4', $this->index(['linux/amd64' => 'old-a', 'linux/arm64/v8' => 'old-b', 'linux/ppc64le' => 'old-c', 'linux/s390x' => 'old-d']));

        [$status, , $calls] = $this->run_('--tag', 'php8.4');

        self::assertSame(1, $status);
        $digests = array_column(array_map(fn (string $d): array => json_decode($d, true), array_values(array_filter($this->creates($calls)[0]['sources'], fn (string $s): bool => str_starts_with($s, '{')))), 'digest');
        self::assertEqualsCanonicalizing(['sha256:old-c', 'sha256:att-old-c', 'sha256:old-d', 'sha256:att-old-d'], $digests);
    }

    public function testCarriesOverFromEachTagSeparately(): void
    {
        $this->built(['linux/amd64' => 'a7', 'linux/arm64/v8' => 'b7', 'linux/ppc64le' => 'c7']);
        $this->current('6.2-php8.4', $this->index(['linux/amd64' => 'x-a', 'linux/s390x' => 'x-d']));
        $this->current('latest', $this->index(['linux/amd64' => 'y-a', 'linux/s390x' => 'y-d']));

        [$status, , $calls] = $this->run_('--tag', '6.2-php8.4', '--tag', 'latest');

        self::assertSame(1, $status);
        $carried = array_map(fn (array $c): array => array_column(array_map(fn (string $d): array => json_decode($d, true), array_values(array_filter($c['sources'], fn (string $s): bool => str_starts_with($s, '{')))), 'digest'), $this->creates($calls));
        self::assertSame([['sha256:x-d', 'sha256:att-x-d'], ['sha256:y-d', 'sha256:att-y-d']], $carried);
    }

    public function testRejectsTwoDigestsOfOnePlatform(): void
    {
        $this->built(['linux/amd64' => 'aa', 'linux/arm64/v8' => 'ba']);
        $this->built(['linux/amd64' => 'ab']);

        [$status, $output, $calls] = $this->run_('--tag', 'php8.4');

        self::assertSame(1, $status);
        self::assertStringContainsString('more than one digest was built for platform linux/amd64', $output);
        self::assertSame([], $calls);
    }

    public function testPublishesNoTagWhenTheCurrentImageOfALaterTagCannotBeRead(): void
    {
        $this->built(['linux/amd64' => 'ac', 'linux/arm64/v8' => 'bc', 'linux/ppc64le' => 'cc']);
        file_put_contents("{$this->dir}/raw/6.2-php8.4.error", "ERROR: 503 Service Unavailable\n");

        [$status, , $calls] = $this->run_('--tag', '6.2.5-php8.4', '--tag', '6.2-php8.4');

        self::assertSame(1, $status);
        self::assertSame([], $this->creates($calls));
    }

    public function testPublishesNoTagWhenATagDoesNotPointToAnImageIndex(): void
    {
        $this->built(['linux/amd64' => 'ad', 'linux/arm64/v8' => 'bd', 'linux/ppc64le' => 'cd']);
        $this->current('php8.4', json_encode(['schemaVersion' => 2, 'mediaType' => 'application/vnd.oci.image.manifest.v1+json', 'layers' => []]));

        [$status, $output, $calls] = $this->run_('--tag', 'php8.4');

        self::assertSame(1, $status);
        self::assertStringContainsString("tag php8.4 doesn't point to an image index", $output);
        self::assertSame([], $this->creates($calls));
    }

    public function testPassesWhenOnlyOptionalPlatformsAreMissing(): void
    {
        $this->optional = 'linux/ppc64le linux/s390x';
        $this->built(['linux/amd64' => 'ae', 'linux/arm64/v8' => 'be']);
        $this->current('php8.4', $this->index(['linux/amd64' => 'old-a', 'linux/arm64/v8' => 'old-b', 'linux/ppc64le' => 'old-c', 'linux/s390x' => 'old-d']));

        [$status, $output, $calls] = $this->run_('--tag', 'php8.4');

        self::assertSame(0, $status, $output);
        self::assertStringContainsString('::warning::published without a new build of linux/ppc64le linux/s390x', $output);
        self::assertStringNotContainsString('::error::', $output);
        $creates = $this->creates($calls);
        self::assertCount(1, $creates, 'the platforms that built are still published');
        self::assertContains('phpswoole/swoole@sha256:ae', $creates[0]['sources']);
        self::assertContains('phpswoole/swoole@sha256:be', $creates[0]['sources']);
        $digests = array_column(array_map(fn (string $d): array => json_decode($d, true), array_values(array_filter($creates[0]['sources'], fn (string $s): bool => str_starts_with($s, '{')))), 'digest');
        self::assertEqualsCanonicalizing(['sha256:old-c', 'sha256:att-old-c', 'sha256:old-d', 'sha256:att-old-d'], $digests, 'the optional platforms are carried over');
        self::assertStringContainsString('tags that already had an image of it keep that image', $output);
    }

    public function testPassesWhenOnlyOptionalPlatformsAreMissingFromANewTag(): void
    {
        $this->optional = 'linux/ppc64le linux/s390x';
        $this->built(['linux/amd64' => 'ai', 'linux/arm64/v8' => 'bi', 'linux/ppc64le' => 'ci']);

        [$status, $output, $calls] = $this->run_('--tag', '6.1.13-php8.4');

        self::assertSame(0, $status, $output);
        self::assertStringContainsString('::warning::published without a new build of linux/s390x.', $output);
        self::assertStringNotContainsString('keep that image', $output);
        $creates = $this->creates($calls);
        self::assertCount(1, $creates);
        self::assertSame([], array_values(array_filter($creates[0]['sources'], fn (string $s): bool => str_starts_with($s, '{'))), 'a new tag has nothing to carry over');
    }

    public function testFailsWhenARequiredPlatformIsMissing(): void
    {
        $this->optional = 'linux/ppc64le linux/s390x';
        $this->built(['linux/amd64' => 'af', 'linux/ppc64le' => 'cf', 'linux/s390x' => 'df']);
        $this->current('php8.4', $this->index(['linux/amd64' => 'old-a', 'linux/arm64/v8' => 'old-b', 'linux/ppc64le' => 'old-c', 'linux/s390x' => 'old-d']));

        [$status, $output, $calls] = $this->run_('--tag', 'php8.4');

        self::assertSame(1, $status);
        self::assertStringContainsString('::error::published without a new build of linux/arm64/v8', $output);
        self::assertStringContainsString('Required platform(s) missing: linux/arm64/v8;', $output);
        self::assertCount(1, $this->creates($calls), 'the platforms that built are still published');
    }

    public function testFailsWhenRequiredAndOptionalPlatformsAreMissing(): void
    {
        $this->optional = 'linux/ppc64le linux/s390x';
        $this->built(['linux/amd64' => 'ag', 'linux/ppc64le' => 'cg']);

        [$status, $output] = $this->run_('--tag', 'php8.4');

        self::assertSame(1, $status);
        self::assertStringContainsString('::error::published without a new build of linux/arm64/v8 linux/s390x', $output);
        self::assertStringContainsString('Required platform(s) missing: linux/arm64/v8;', $output);
    }

    public function testRejectsAnOptionalPlatformNotInPlatforms(): void
    {
        $this->optional = 'linux/riscv64';
        $this->built(['linux/amd64' => 'ah', 'linux/arm64/v8' => 'bh', 'linux/ppc64le' => 'ch', 'linux/s390x' => 'dh']);

        [$status, $output, $calls] = $this->run_('--tag', 'php8.4');

        self::assertSame(1, $status);
        self::assertStringContainsString('optional platform linux/riscv64 is not one of', $output);
        self::assertSame([], $calls);
    }

    public function testPublishesNothingWhenNoPlatformWasBuilt(): void
    {
        rmdir("{$this->dir}/digests");

        [$status, $output, $calls] = $this->run_('--tag', 'php8.4');

        self::assertSame(1, $status);
        self::assertStringContainsString('::error::no platform was built', $output);
        self::assertSame([], $calls);
    }

    public function testPublishesNothingWhenTheCurrentImageOfATagCannotBeRead(): void
    {
        $this->built(['linux/amd64' => 'a4', 'linux/arm64/v8' => 'b4', 'linux/ppc64le' => 'c4']);
        file_put_contents("{$this->dir}/raw/php8.4.error", "ERROR: unexpected status from HEAD request: 401 Unauthorized\n");

        [$status, $output, $calls] = $this->run_('--tag', 'php8.4');

        self::assertSame(1, $status);
        self::assertStringContainsString('failed to read the current image of tag php8.4, so no tag is published', $output);
        self::assertSame([], $this->creates($calls), 'publishing without carrying s390x over would drop it from the tag');
    }

    public function testRejectsADigestOfAnUnknownPlatform(): void
    {
        $this->built(['linux/amd64' => 'a5', 'linux/riscv64' => 'e5']);

        [$status, $output, $calls] = $this->run_('--tag', 'php8.4');

        self::assertSame(1, $status);
        self::assertStringContainsString('names platform "linux/riscv64"', $output);
        self::assertSame([], $calls);
    }

    /**
     * @param array<string, string> $digests Platform => digest (without "sha256:").
     */
    private function built(array $digests): void
    {
        foreach ($digests as $platform => $digest) {
            file_put_contents("{$this->dir}/digests/{$digest}", "{$platform}\n");
        }
    }

    private function current(string $tag, string $index): void
    {
        file_put_contents("{$this->dir}/raw/{$tag}", $index);
    }

    /**
     * An image index as BuildKit publishes it: one image manifest per platform, each followed by its attestation
     * manifest. Like BuildKit, it records linux/arm64/v8 as linux/arm64, without the default variant.
     *
     * @param array<string, string> $digests Platform => digest (without "sha256:").
     */
    private function index(array $digests): string
    {
        $manifests = [];
        foreach ($digests as $platform => $digest) {
            $parts    = explode('/', $platform);
            $platform = ['os' => $parts[0], 'architecture' => $parts[1]] + (isset($parts[2]) && $platform !== 'linux/arm64/v8' ? ['variant' => $parts[2]] : []);

            $manifests[] = ['mediaType' => 'application/vnd.oci.image.manifest.v1+json', 'digest' => "sha256:{$digest}", 'size' => 1000, 'platform' => $platform];
            $manifests[] = [
                'mediaType'   => 'application/vnd.oci.image.manifest.v1+json',
                'digest'      => "sha256:att-{$digest}",
                'size'        => 800,
                'annotations' => ['vnd.docker.reference.digest' => "sha256:{$digest}", 'vnd.docker.reference.type' => 'attestation-manifest'],
                'platform'    => ['os' => 'unknown', 'architecture' => 'unknown'],
            ];
        }

        return json_encode(['schemaVersion' => 2, 'mediaType' => 'application/vnd.oci.image.index.v1+json', 'manifests' => $manifests]);
    }

    /**
     * @return array{int, string, list<list<string>>}
     */
    private function run_(string ...$args): array
    {
        $command = sprintf(
            'cd %s && env -u GITHUB_SERVER_URL -u GITHUB_REPOSITORY PATH=%s:"$PATH" FAKE_DIR=%s PLATFORMS=%s OPTIONAL_PLATFORMS=%s IMAGE=phpswoole/swoole GITHUB_SHA=abc ./bin/publish-image.sh %s %s 2>&1',
            escapeshellarg(dirname(__DIR__)),
            escapeshellarg("{$this->dir}/bin"),
            escapeshellarg($this->dir),
            escapeshellarg(self::PLATFORMS),
            escapeshellarg($this->optional),
            implode(' ', array_map('escapeshellarg', $args)),
            escapeshellarg("{$this->dir}/digests"),
        );
        exec($command, $lines, $status);

        $calls = [];
        if (is_file("{$this->dir}/calls.jsonl")) {
            foreach (file("{$this->dir}/calls.jsonl", FILE_IGNORE_NEW_LINES) as $line) {
                $calls[] = json_decode($line, true);
            }
        }

        return [$status, implode("\n", $lines), $calls];
    }

    /**
     * @param list<list<string>> $calls
     * @return list<array{tags: list<string>, sources: list<string>, annotations: list<string>}>
     */
    private function creates(array $calls): array
    {
        $creates = [];
        foreach ($calls as $call) {
            if (array_slice($call, 0, 3) !== ['buildx', 'imagetools', 'create']) {
                continue;
            }
            $create = ['tags' => [], 'sources' => [], 'annotations' => []];
            for ($i = 3; $i < count($call); $i++) {
                match ($call[$i]) {
                    '--tag'        => $create['tags'][]        = substr($call[++$i], strlen('phpswoole/swoole:')),
                    '--annotation' => $create['annotations'][] = $call[++$i],
                    default        => $create['sources'][]     = $call[$i],
                };
            }
            $creates[] = $create;
        }

        return $creates;
    }

    /**
     * @param list<list<string>> $calls
     * @return list<list<string>>
     */
    private function inspectsRaw(array $calls): array
    {
        return array_values(array_filter($calls, fn (array $c): bool => ($c[3] ?? null) === '--raw'));
    }

    /**
     * @return list<string>
     */
    private function refs(string ...$digests): array
    {
        return array_map(fn (string $d): string => "phpswoole/swoole@sha256:{$d}", $digests);
    }
}
