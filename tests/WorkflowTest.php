<?php

declare(strict_types=1);

namespace Swoole\Tests\Docker;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * Class WorkflowTest
 *
 * Guards properties of the workflows that nothing in GitHub Actions checks:
 *   - The list of platforms each build workflow declares in PLATFORMS, which its merge job (bin/publish-image.sh)
 *     uses to tell which platforms failed to build. It has to match the platform matrix of the build job, but it lives
 *     in a different job, so nothing in GitHub Actions can keep the two in sync. A platform missing from PLATFORMS
 *     would make every merge job fail; an extra one would be reported as missing on every run.
 *   - OPTIONAL_PLATFORMS, which must list exactly the platforms marked "optional" in the platform matrix, and the build
 *     job's "continue-on-error: ${{ matrix.platform.optional }}": together they make a failed build of an optional
 *     platform leave the run green, and one of a required platform fail it.
 *
 * @internal
 * @coversNothing
 */
class WorkflowTest extends TestCase
{
    #[DataProvider('dataBuildWorkflows')]
    public function testPlatformsMatchThePlatformMatrix(string $workflow): void
    {
        $data = Yaml::parseFile($workflow);

        $platforms = $data['jobs']['build']['strategy']['matrix']['platform'] ?? null;
        self::assertIsArray($platforms, "no platform matrix found in {$workflow}");

        $declared = $data['env']['PLATFORMS'] ?? null;
        self::assertIsString($declared, "no PLATFORMS found in {$workflow}");

        self::assertSame(
            array_column($platforms, 'name'),
            preg_split('/\s+/', trim($declared)),
            "PLATFORMS in {$workflow} does not list the platforms of its platform matrix, in the same order",
        );

        $optional = $data['env']['OPTIONAL_PLATFORMS'] ?? null;
        self::assertIsString($optional, "no OPTIONAL_PLATFORMS found in {$workflow}");
        self::assertSame(
            array_column(array_filter($platforms, fn (array $platform): bool => $platform['optional'] ?? false), 'name'),
            preg_split('/\s+/', trim($optional), -1, PREG_SPLIT_NO_EMPTY),
            "OPTIONAL_PLATFORMS in {$workflow} does not list the platforms marked optional in its platform matrix",
        );
        // A failed build of an optional platform must not fail the run, and one of a required platform must.
        self::assertSame(
            '${{ matrix.platform.optional }}',
            $data['jobs']['build']['continue-on-error'] ?? null,
            "the build job of {$workflow} doesn't let optional platforms fail",
        );
    }

    /**
     * The merge job tells the platforms apart by the content of the digest files, and runs bin/publish-image.sh from
     * a checkout of the repository.
     */
    #[DataProvider('dataBuildWorkflows')]
    public function testMergeJobPublishesWithTheScript(string $workflow): void
    {
        $data = Yaml::parseFile($workflow);

        $exports = array_filter(
            $data['jobs']['build']['steps'],
            fn (array $step): bool => str_contains($step['run'] ?? '', '/tmp/digests/${DIGEST#sha256:}'),
        );
        self::assertCount(1, $exports, "{$workflow} should export each platform's digest exactly once");
        $export = reset($exports);
        self::assertStringContainsString('echo "${PLATFORM}" >', $export['run'], "{$workflow} doesn't record the platform of each digest");
        self::assertSame('${{ matrix.platform.name }}', $export['env']['PLATFORM'] ?? null, "{$workflow} doesn't pass the platform to the export step");

        // Without "!cancelled()" a failed build job would skip every merge job, so nothing would be published at all.
        self::assertStringContainsString('!cancelled()', $data['jobs']['merge']['if'] ?? '', "the merge job of {$workflow} doesn't run once a build job failed");

        $steps = $data['jobs']['merge']['steps'];
        self::assertNotEmpty(preg_grep('#^actions/checkout@#', array_column($steps, 'uses')), "the merge job of {$workflow} doesn't check out the repository");
        // The script alone decides whether a missing platform fails the run (only a required one does), so no step of
        // the merge job may swallow its exit status.
        self::assertSame([], array_filter(array_column($steps, 'continue-on-error')), "a step of the merge job of {$workflow} ignores failures");
        $scripts = implode("\n", array_column($steps, 'run'));
        self::assertMatchesRegularExpression('#\./bin/publish-image\.sh .+ /tmp/digests#', $scripts, "the merge job of {$workflow} doesn't publish with bin/publish-image.sh");
        self::assertStringContainsString('--tag ', $scripts, "the merge job of {$workflow} doesn't pass any tag to bin/publish-image.sh");
        self::assertStringNotContainsString('imagetools create', $scripts, "the merge job of {$workflow} publishes without bin/publish-image.sh");
    }

    /**
     * Option "emulated" is negated in the workflows ("if: ${{ !matrix.platform.emulated }}"). Were it ever quoted, it
     * would be a non-empty string, the negation would evaluate to false, and the image would silently stop being
     * tested on every platform instead of failing.
     */
    #[DataProvider('dataBuildWorkflows')]
    public function testPlatformsAreDescribedConsistently(string $workflow): void
    {
        $platforms = Yaml::parseFile($workflow)['jobs']['build']['strategy']['matrix']['platform'];

        foreach ($platforms as $platform) {
            $name = $platform['name'] ?? '(unnamed)';
            self::assertIsBool($platform['emulated'] ?? null, "option \"emulated\" of platform {$name} is not a boolean in {$workflow}");
            self::assertIsBool($platform['optional'] ?? null, "option \"optional\" of platform {$name} is not a boolean in {$workflow}");
            self::assertIsString($platform['runner'] ?? null, "platform {$name} has no runner in {$workflow}");
            self::assertIsString($platform['slug'] ?? null, "platform {$name} has no slug in {$workflow}");
        }

        self::assertSameSize(
            $platforms,
            array_unique(array_column($platforms, 'slug')),
            "two platforms share a slug in {$workflow}; the slug distinguishes their digest artifacts",
        );
    }

    /**
     * @return array<string, array{string}>
     */
    public static function dataBuildWorkflows(): array
    {
        $data = [];
        foreach (glob(__DIR__ . '/../.github/workflows/build_*.yml') as $workflow) {
            $data[basename($workflow)] = [$workflow];
        }

        return $data;
    }
}
