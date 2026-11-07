<?php

declare(strict_types=1);

namespace Swoole\Tests\Docker;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * Class BuildModeTest
 *
 * Runs bin/build-mode.sh, which decides whether a run of a versioned workflow publishes a version with its floating
 * tags, rebuilds a released version under its version tags only, or builds nothing.
 *
 * @internal
 * @coversNothing
 */
class BuildModeTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/build-mode-' . bin2hex(random_bytes(6));
        mkdir("{$this->dir}/bin", 0777, true);
        mkdir("{$this->dir}/config");
        copy(__DIR__ . '/../bin/build-mode.sh', "{$this->dir}/bin/build-mode.sh");
        chmod("{$this->dir}/bin/build-mode.sh", 0755);
    }

    protected function tearDown(): void
    {
        exec('rm -rf ' . escapeshellarg($this->dir));
    }

    #[DataProvider('dataModes')]
    public function testMode(string $status, string $event, string $expected): void
    {
        $this->config('6.2.4', "status: \"{$status}\"\n");

        self::assertSame([0, $expected], $this->run_('refs/heads/6.2.4', $event));
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function dataModes(): array
    {
        return [
            'a push of a version under development'       => ['under development', 'push', 'release'],
            'a manual run of a version under development' => ['under development', 'workflow_dispatch', 'release'],
            'a push of a released version'                => ['released', 'push', 'skip'],
            'a manual run of a released version'          => ['released', 'workflow_dispatch', 'rebuild'],
            'a push of an end-of-life version'            => ['end-of-life', 'push', 'skip'],
            'a manual run of an end-of-life version'      => ['end-of-life', 'workflow_dispatch', 'rebuild'],
        ];
    }

    public function testReadsAStatusFollowedByAComment(): void
    {
        $this->config('6.2.4', "status: \"released\" # since 6.2.5\n");

        self::assertSame([0, 'rebuild'], $this->run_('refs/heads/6.2.4', 'workflow_dispatch'));
    }

    public function testRebuildsAPreRelease(): void
    {
        $this->config('6.3.0-rc1', "status: \"released\"\n");

        self::assertSame([0, 'rebuild'], $this->run_('refs/heads/6.3.0-rc1', 'workflow_dispatch'));
    }

    #[DataProvider('dataRefsThatAreNotVersionBranches')]
    public function testManualRunFailsOnARefThatIsNotAVersionBranch(string $ref): void
    {
        $this->config('6.2.4', "status: \"released\"\n");

        [$status, $output] = $this->run_($ref, 'workflow_dispatch');

        self::assertSame(1, $status);
        self::assertStringContainsString('::error::run this workflow on a version branch', $output);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function dataRefsThatAreNotVersionBranches(): array
    {
        return [
            'a work branch'                   => ['refs/heads/6.2.x'],
            'master'                          => ['refs/heads/master'],
            'a branch ending in a version'    => ['refs/heads/feature/6.2.4'],
            'a tag'                           => ['refs/tags/6.2.4'],
            'a version without configuration' => ['refs/heads/6.2.9'],
        ];
    }

    public function testManualRunFailsOnAnUnknownStatus(): void
    {
        $this->config('6.2.4', "status: \"frozen\"\n");

        [$status, $output] = $this->run_('refs/heads/6.2.4', 'workflow_dispatch');

        self::assertSame(1, $status);
        self::assertStringContainsString('has no valid status', $output);
    }

    /**
     * Pushes have always built nothing for a version whose status isn't "under development", whatever it is.
     */
    public function testPushSkipsAVersionWithAnUnknownStatusOrNoConfiguration(): void
    {
        $this->config('6.2.4', "status: \"frozen\"\n");

        self::assertSame([0, 'skip'], $this->run_('refs/heads/6.2.4', 'push'));
        self::assertSame([0, 'skip'], $this->run_('refs/heads/6.2.9', 'push'));
    }

    /**
     * The script reads the status with a regular expression, so every configuration file of this branch is checked
     * against what a YAML parser reads from it: a status written in a way the script doesn't recognize would make
     * pushes of a new release build nothing.
     */
    public function testReadsTheStatusOfEveryConfigurationFile(): void
    {
        $modes = ['under development' => 'release', 'released' => 'rebuild', 'end-of-life' => 'rebuild'];
        $files = glob(__DIR__ . '/../config/[0-9]*.yml');
        self::assertNotEmpty($files);

        foreach ($files as $file) {
            $version = basename($file, '.yml');
            $status  = Yaml::parseFile($file)['status'] ?? null;
            self::assertArrayHasKey($status, $modes, "config/{$version}.yml has an unknown status");

            $command = 'env GITHUB_REF=' . escapeshellarg("refs/heads/{$version}") . ' GITHUB_EVENT_NAME=workflow_dispatch '
                . escapeshellarg(__DIR__ . '/../bin/build-mode.sh') . ' 2>&1';
            exec($command, $lines, $exit);
            self::assertSame([0, $modes[$status]], [$exit, trim(implode("\n", $lines))], "bin/build-mode.sh misreads the status of config/{$version}.yml");
            $lines = [];
        }
    }

    private function config(string $version, string $content): void
    {
        file_put_contents("{$this->dir}/config/{$version}.yml", $content);
    }

    /**
     * @return array{int, string} The exit status, and what the script printed (standard error included).
     */
    private function run_(string $ref, string $event): array
    {
        $command = 'env GITHUB_REF=' . escapeshellarg($ref) . ' GITHUB_EVENT_NAME=' . escapeshellarg($event)
            . ' ' . escapeshellarg("{$this->dir}/bin/build-mode.sh") . ' 2>&1';
        exec($command, $lines, $status);

        return [$status, trim(implode("\n", $lines))];
    }
}
