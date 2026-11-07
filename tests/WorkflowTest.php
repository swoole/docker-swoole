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

    #[DataProvider('dataVersionedWorkflows')]
    public function testVersionedWorkflowCanBeRunManually(string $workflow): void
    {
        $on = Yaml::parseFile($workflow)['on'];

        self::assertArrayHasKey('workflow_dispatch', $on, "{$workflow} can't be run manually");
        self::assertNotEmpty($on['push']['branches'] ?? null, "{$workflow} doesn't run on pushes to version branches");
    }

    /**
     * Runs the step of each job that sets its parameters, with bin/build-mode.sh replaced by a stub, and checks what
     * each mode makes the job do: a rebuild of a released version ("rebuild") is built, and published under its version
     * tags only ("version"); a push of a released version ("skip") builds nothing; a failure of the script stops the job.
     */
    #[DataProvider('dataVersionedWorkflows')]
    public function testJobsFollowTheBuildMode(string $workflow): void
    {
        $data  = Yaml::parseFile($workflow);
        $build = $this->paramsStep($data, 'build');
        $merge = $this->paramsStep($data, 'merge');
        foreach (['build' => $build, 'merge' => $merge] as $job => $step) {
            self::assertStringNotContainsString('config/', $step['run'], "the {$job} job of {$workflow} reads the configuration of the version itself instead of asking bin/build-mode.sh");
        }

        $version = ['branch_name' => '6.2.4'];
        self::assertSame([0, $version], $this->runParams($build, 'release'), "the build job of {$workflow} doesn't build a version under development");
        self::assertSame([0, $version], $this->runParams($build, 'rebuild'), "the build job of {$workflow} doesn't rebuild a released version");
        self::assertSame([0, $version + ['released_already' => 'true']], $this->runParams($build, 'skip'), "the build job of {$workflow} builds a released version on push");
        self::assertNotSame(0, $this->runParams($build, null)[0], "the build job of {$workflow} goes on when bin/build-mode.sh fails");

        self::assertSame([0, $version + ['major_version' => '6.2', 'image_type' => 'latest']], $this->runParams($merge, 'release'));
        self::assertSame([0, $version + ['major_version' => '6.2', 'image_type' => 'default']], $this->runParams($merge, 'release', php: '8.3'));
        self::assertSame([0, ['branch_name' => '6.1.11', 'major_version' => '6.1', 'image_type' => 'major']], $this->runParams($merge, 'release', branch: '6.1.11'));
        self::assertSame([0, $version + ['image_type' => 'version']], $this->runParams($merge, 'rebuild'), "the merge job of {$workflow} doesn't publish a rebuilt released version under its version tags only");
        self::assertSame([0, $version + ['released_already' => 'true']], $this->runParams($merge, 'skip'), "the merge job of {$workflow} publishes a released version on push");
        self::assertNotSame(0, $this->runParams($merge, null)[0], "the merge job of {$workflow} goes on when bin/build-mode.sh fails");
    }

    /**
     * Runs the step of the merge job that publishes an image, with bin/publish-image.sh replaced by a stub, and checks
     * which tags each image type gets, for every postfix. A rebuild of a released version ("version") must never take
     * over a floating tag from the newer version that owns it.
     */
    #[DataProvider('dataVersionedWorkflows')]
    public function testEachImageTypeGetsItsTags(string $workflow): void
    {
        $data  = Yaml::parseFile($workflow);
        $steps = array_values(array_filter(
            $data['jobs']['merge']['steps'],
            fn (array $step): bool => str_contains($step['run'] ?? '', './bin/publish-image.sh'),
        ));
        self::assertCount(1, $steps, "{$workflow} should publish with exactly one step");
        $step = $steps[0];

        // The step must get its parameters from the step that sets them.
        foreach (['BRANCH_NAME' => 'branch_name', 'MAJOR_VERSION' => 'major_version', 'IMAGE_TYPE' => 'image_type'] as $name => $output) {
            self::assertSame("\${{ steps.params.outputs.{$output} }}", $step['env'][$name] ?? null, "{$workflow} doesn't pass {$output} to the publishing step");
        }

        $postfixes = $step['env']['POSTFIX'] === '${{ matrix.image.postfix }}'
            ? array_column($data['jobs']['merge']['strategy']['matrix']['image'], 'postfix')
            : [$step['env']['POSTFIX']];
        foreach ($postfixes as $postfix) {
            $suffix   = $postfix . (str_contains($workflow, '_dev_') ? '-dev' : '');
            $expected = [
                'version' => ["6.2.4-php8.4{$suffix}"],
                'default' => ["6.2.4-php8.4{$suffix}", "6.2-php8.4{$suffix}"],
                'major'   => ["6.2.4-php8.4{$suffix}", "6.2-php8.4{$suffix}", "6.2{$suffix}"],
                'latest'  => ["6.2.4-php8.4{$suffix}", "6.2-php8.4{$suffix}", "6.2{$suffix}", "latest{$suffix}"],
            ];
            foreach ($expected as $type => $tags) {
                [$status, $published, $output] = $this->publish($step, ['IMAGE_TYPE' => $type, 'POSTFIX' => $postfix]);
                self::assertSame(0, $status, "publishing a \"{$type}\" image with postfix \"{$postfix}\" failed in {$workflow}: {$output}");
                self::assertSame($tags, $published, "a \"{$type}\" image with postfix \"{$postfix}\" doesn't get the right tags in {$workflow}");
            }

            [$status, , $output] = $this->publish($step, ['IMAGE_TYPE' => '', 'POSTFIX' => $postfix]);
            self::assertNotSame(0, $status, "{$workflow} publishes an image of no type");
            self::assertStringContainsString('unknown image type', $output);
            [$status, , $output] = $this->publish($step, ['IMAGE_TYPE' => 'default', 'MAJOR_VERSION' => '', 'POSTFIX' => $postfix]);
            self::assertNotSame(0, $status, "{$workflow} publishes a floating tag without a minor series");
            self::assertStringContainsString('MAJOR_VERSION', $output);
        }
    }

    /**
     * @return array<string, array{string}>
     */
    public static function dataVersionedWorkflows(): array
    {
        return array_filter(self::dataBuildWorkflows(), fn (string $name): bool => str_starts_with($name, 'build_versioned_'), ARRAY_FILTER_USE_KEY);
    }

    /**
     * Runs the "run" script of a publishing step for PHP 8.4 of version 6.2.4, with bin/publish-image.sh replaced by a
     * stub that prints its arguments.
     *
     * @param array<string, mixed> $step
     * @param array<string, string> $env Overrides of the environment variables of the step.
     * @return array{int, list<string>, string} The exit status, the tags the image was published under, and the
     *                                          standard error of the step.
     */
    private function publish(array $step, array $env): array
    {
        $script = strtr($step['run'], [
            '${{ matrix.php }}'      => '8.4',
            './bin/publish-image.sh' => 'printf "%s\n"',
        ]);
        self::assertStringNotContainsString('${{', $script, 'unexpected expression left in the publishing step');

        $env += ['BRANCH_NAME' => '6.2.4', 'MAJOR_VERSION' => '6.2'];
        $command = 'env';
        foreach ($env as $name => $value) {
            $command .= ' ' . $name . '=' . escapeshellarg($value);
        }
        $stderr = tempnam(sys_get_temp_dir(), 'stderr-');
        exec($command . ' bash -c ' . escapeshellarg($script) . ' 2>' . escapeshellarg($stderr), $lines, $status);
        $output = file_get_contents($stderr);
        unlink($stderr);

        $tags = [];
        foreach ($lines as $i => $line) {
            if ($line === '--tag') {
                $tags[] = $lines[$i + 1];
            }
        }

        return [$status, $tags, $output];
    }

    /**
     * @param array<string, mixed> $data A parsed workflow.
     * @return array<string, mixed> The step of the given job that sets its parameters.
     */
    private function paramsStep(array $data, string $job): array
    {
        $steps = array_values(array_filter($data['jobs'][$job]['steps'], fn (array $step): bool => ($step['id'] ?? null) === 'params'));
        self::assertCount(1, $steps, "the {$job} job should have exactly one step setting its parameters");

        return $steps[0];
    }

    /**
     * Runs the "run" script of a step setting the parameters of a job, in a directory holding the Dockerfiles of the
     * version and a stub of bin/build-mode.sh.
     *
     * @param array<string, mixed> $step
     * @param string|null $mode What the stub of bin/build-mode.sh prints, or null to make it fail.
     * @return array{int, array<string, string>} The exit status, and the parameters the step set.
     */
    private function runParams(array $step, ?string $mode, string $branch = '6.2.4', string $php = '8.4'): array
    {
        $script = strtr($step['run'], [
            '${{ matrix.php }}'        => $php,
            '${{ matrix.image.type }}' => 'cli',
        ]);
        self::assertStringNotContainsString('${{', $script, 'unexpected expression left in the step setting the parameters');

        $dir = sys_get_temp_dir() . '/params-' . bin2hex(random_bytes(6));
        try {
            foreach (['cli', 'alpine'] as $type) {
                mkdir("{$dir}/dockerfiles/{$branch}/php{$php}/{$type}", 0777, true);
                touch("{$dir}/dockerfiles/{$branch}/php{$php}/{$type}/Dockerfile");
            }
            mkdir("{$dir}/bin");
            file_put_contents("{$dir}/bin/build-mode.sh", '#!/usr/bin/env bash
' . ($mode === null ? 'exit 1
' : "echo {$mode}
"));
            chmod("{$dir}/bin/build-mode.sh", 0755);
            touch("{$dir}/output");

            $command = 'cd ' . escapeshellarg($dir) . ' && env GITHUB_REF=refs/heads/' . escapeshellarg($branch)
                . ' GITHUB_OUTPUT=' . escapeshellarg("{$dir}/output") . ' bash -c ' . escapeshellarg($script) . ' >/dev/null 2>&1';
            exec($command, $lines, $status);

            $outputs = [];
            foreach (file("{$dir}/output", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                [$name, $value]   = explode('=', $line, 2);
                $outputs[$name]   = $value;
            }

            return [$status, $outputs];
        } finally {
            exec('rm -rf ' . escapeshellarg($dir));
        }
    }
}
