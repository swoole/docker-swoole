<?php

declare(strict_types=1);

namespace Swoole\Tests\Docker;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * Class WorkflowTest
 *
 * Guards the one number in the build workflows that is not derivable from anything else: each workflow declares
 * EXPECTED_DIGESTS, and its merge job refuses to publish a tag unless it collected that many digests. The number has
 * to match the size of the platform matrix in the build job, but it lives in a different job, so nothing in GitHub
 * Actions can keep the two in sync. Adding or removing a platform without updating EXPECTED_DIGESTS would otherwise
 * only surface as a failed nightly build.
 *
 * @internal
 * @coversNothing
 */
class WorkflowTest extends TestCase
{
    #[DataProvider('dataBuildWorkflows')]
    public function testExpectedDigestsMatchesThePlatformMatrix(string $workflow): void
    {
        $data = Yaml::parseFile($workflow);

        $platforms = $data['jobs']['build']['strategy']['matrix']['platform'] ?? null;
        self::assertIsArray($platforms, "no platform matrix found in {$workflow}");

        $expected = $data['env']['EXPECTED_DIGESTS'] ?? null;
        self::assertIsInt($expected, "no EXPECTED_DIGESTS found in {$workflow}");

        self::assertCount(
            $expected,
            $platforms,
            "EXPECTED_DIGESTS in {$workflow} does not match the number of entries in its platform matrix",
        );
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
