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
 *   - The one number in the build workflows that is not derivable from anything else: each workflow declares
 *     EXPECTED_DIGESTS, and its merge job refuses to publish a tag unless it collected that many digests. The number
 *     has to match the size of the platform matrix in the build job, but it lives in a different job, so nothing in
 *     GitHub Actions can keep the two in sync. Adding or removing a platform without updating EXPECTED_DIGESTS would
 *     otherwise only surface as a failed nightly build.
 *   - Supply-chain settings (actions referenced by version tag, restricted permissions, SBOM, provenance and
 *     signing), which would otherwise go missing silently when a workflow is added or edited.
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
     * Actions are referenced by version tag (a major-version tag like "v7" where the action publishes one), never by
     * branch, so that a push to an action's default branch can't change how the images are built.
     */
    #[DataProvider('dataWorkflows')]
    public function testActionsAreReferencedByVersionTag(string $workflow): void
    {
        preg_match_all('/^\s*uses:\s*(\S+)/m', file_get_contents($workflow), $matches);
        self::assertNotEmpty($matches[1], "no actions found in {$workflow}");

        foreach ($matches[1] as $action) {
            self::assertMatchesRegularExpression('/@v\d+(\.\d+){0,2}$/', $action, "action {$action} is not referenced by a version tag in {$workflow}");
        }
    }

    #[DataProvider('dataWorkflows')]
    public function testPermissionsAreRestricted(string $workflow): void
    {
        self::assertSame(
            ['contents' => 'read'],
            Yaml::parseFile($workflow)['permissions'] ?? null,
            "{$workflow} does not restrict the permissions of its jobs to reading the repository",
        );
    }

    /**
     * @return array<string, array{string}>
     */
    public static function dataWorkflows(): array
    {
        $data = [];
        foreach (glob(__DIR__ . '/../.github/workflows/*.yml') as $workflow) {
            $data[basename($workflow)] = [$workflow];
        }

        return $data;
    }

    #[DataProvider('dataBuildWorkflows')]
    public function testImagesAreAttestedAndSigned(string $workflow): void
    {
        $data = Yaml::parseFile($workflow);

        $pushes = array_filter(
            $data['jobs']['build']['steps'],
            fn (array $step): bool => str_contains($step['with']['outputs'] ?? '', 'push-by-digest=true'),
        );
        self::assertCount(1, $pushes, "{$workflow} should push each platform's image by digest exactly once");
        $push = reset($pushes);
        self::assertTrue($push['with']['sbom'] ?? null, "{$workflow} doesn't attach an SBOM to the images");
        self::assertSame('mode=max', $push['with']['provenance'] ?? null, "{$workflow} doesn't attach full provenance to the images");
        self::assertStringContainsString('org.opencontainers.image.source=', $push['with']['labels'] ?? '', "{$workflow} doesn't label the images");

        self::assertSame('write', $data['jobs']['merge']['permissions']['id-token'] ?? null, "the merge job of {$workflow} can't sign images keylessly");
        $scripts = implode("\n", array_column($data['jobs']['merge']['steps'], 'run'));
        self::assertStringContainsString('cosign sign --yes', $scripts, "the merge job of {$workflow} doesn't sign the images");
        self::assertStringContainsString('--annotation "index:org.opencontainers.image.source=', $scripts, "the merge job of {$workflow} doesn't annotate the image index");
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
