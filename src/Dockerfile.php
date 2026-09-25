<?php

declare(strict_types=1);

namespace Swoole\Docker;

use Symfony\Component\Yaml\Yaml;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Loader\FilesystemLoader;

/**
 * Class Dockerfile
 */
class Dockerfile
{
    protected const ALPINE = 'alpine';

    protected const CLI = 'cli';

    protected const ZTS = 'zts';

    protected const TYPES = [
        self::ALPINE,
        self::CLI,
        self::ZTS,
    ];

    protected const VERSION_NIGHTLY = 'nightly';

    protected const ALPINE_VERSIONS = [
        // PHP major version => Alpine version,
        '7.1' => '3.10',
        '7.2' => '3.12',
        '7.3' => '3.13',
        '7.4' => '3.15',
        '8.0' => '3.16',
        '8.1' => '3.22',
        '8.2' => '3.23',
        '8.3' => '3.23',
        '8.4' => '3.23',
        '8.5' => '3.23',
    ];

    protected string $basePath;

    protected string $swooleVersion;

    protected array $config;

    private ?Environment $twig = null;

    /**
     * Dockerfile constructor.
     *
     * @throws Exception
     */
    public function __construct(string $swooleVersion)
    {
        $this
            ->setBasePath(dirname(__DIR__))
            ->setSwooleVersion($swooleVersion)
            ->setConfig(Yaml::parseFile("{$this->getConfigFilePath()}"))
        ;
    }

    /**
     * @throws Exception
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function render(): void
    {
        foreach ($this->getConfig()['php'] as $phpVersion) {
            foreach (self::TYPES as $type) {
                $content = $this->renderToString($phpVersion, $type);
                $dir     = $this->getDockerFileDir($type, $phpVersion);
                if (!file_exists($dir)) {
                    mkdir($dir, 0777, true);
                }
                file_put_contents("{$dir}/Dockerfile", $content);
            }
        }
    }

    public function getBasePath(): string
    {
        return $this->basePath;
    }

    /**
     * @throws Exception
     */
    public function setBasePath(string $basePath): self
    {
        if (!is_dir($basePath) || !is_readable($basePath)) {
            throw new Exception("base path '{$basePath}' does not point to a directory or not readable");
        }

        $this->basePath = $basePath;

        return $this;
    }

    public function getSwooleVersion(): string
    {
        return $this->swooleVersion;
    }

    public function setSwooleVersion(string $swooleVersion): self
    {
        if ($swooleVersion !== self::VERSION_NIGHTLY && !$this->isValidSwooleVersion($swooleVersion)) {
            throw new Exception("Invalid Swoole version '{$swooleVersion}'.");
        }

        $this->swooleVersion = $swooleVersion;

        return $this;
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function setConfig(array $config): self
    {
        $this->config = $config;

        return $this;
    }

    protected function getPhpMajorVersion(string $phpVersion): string
    {
        return preg_replace('/^(\d+\.\d+).*$/', '$1', $phpVersion);
    }

    /**
     * @param value-of<Dockerfile::TYPES> $type
     * @param string $phpVersion Needed only when creating Dockerfiles for a released version of Swoole.
     */
    protected function getDockerFileDir(string $type, string $phpVersion): string
    {
        return sprintf(
            '%s/dockerfiles/%s/php%s/%s',
            $this->getBasePath(),
            $this->getSwooleVersion(),
            $this->getPhpMajorVersion($phpVersion),
            $type,
        );
    }

    /**
     * @throws Exception
     */
    protected function getConfigFilePath(): string
    {
        $file = $this->getConfigFilePathBySwooleVersion($this->getSwooleVersion());

        if (!is_file($file) || !is_readable($file)) {
            throw new Exception("Unable to load configuration file '{$file}'.");
        }

        return $file;
    }

    protected function getConfigFilePathBySwooleVersion(string $swooleVersion): string
    {
        return "{$this->getBasePath()}/config/{$swooleVersion}.yml";
    }

    /**
     * A version # is either a stable release (e.g. "6.2.3"), or a pre-release named after the Git tag of Swoole
     * without its leading "v" (e.g. "6.3.0-rc1" for tag "v6.3.0-rc1").
     */
    protected function isValidSwooleVersion(string $swooleVersion): bool
    {
        return (bool) preg_match('/^[1-9]\d*\.(0|[1-9]\d*)\.(0|[1-9]\d*)(-[a-zA-Z][a-zA-Z0-9]*)?$/', $swooleVersion);
    }

    /**
     * Whether the Swoole version is 6.2.0 or later (including nightly, which builds the master branch of Swoole).
     *
     * Swoole 6.2.0 removed configure option "--enable-openssl" (OpenSSL support is always compiled in since then),
     * and added configure options "--enable-swoole-ftp" and "--with-swoole-ssh2".
     */
    protected function isSwoole620OrLater(): bool
    {
        return ($this->getSwooleVersion() === self::VERSION_NIGHTLY)
            || version_compare($this->getSwooleVersion(), '6.2.0', '>=');
    }

    /**
     * Whether configure option "--enable-swoole-stdext" is still supported.
     *
     * The stdext module was removed from the master branch of Swoole on 2026-09-02 (its PHP language extensions,
     * e.g. strongly typed arrays and basic type methods, are now implemented by the typephp project instead), so
     * nightly images, which build that branch, no longer support this option, and neither does Swoole 6.3.0 (the
     * first release cut after that removal, starting with 6.3.0-rc1). Releases of the 6.2 series and earlier still
     * support it.
     *
     * Version "6.3.0-dev" is compared against so that pre-releases of 6.3.0 (e.g. "6.3.0-rc1"), which
     * version_compare() orders before "6.3.0", count as 6.3.0.
     *
     * @see https://github.com/swoole/swoole-src/commit/dbdf559f11
     */
    protected function isSwooleStdextSupported(): bool
    {
        return ($this->getSwooleVersion() !== self::VERSION_NIGHTLY)
            && version_compare($this->getSwooleVersion(), '6.3.0-dev', '<');
    }

    /**
     * Get the PECL extensions to install for given PHP version, with field "version_overrides" of each extension
     * resolved: when it lists the PHP major version (e.g. "8.5"), that version of the extension is used instead of
     * the one in field "version".
     */
    protected function getPhpExtensions(string $phpVersion): array
    {
        $phpMajorVersion = $this->getPhpMajorVersion($phpVersion);
        $extensions      = [];
        foreach ($this->getConfig()['image']['php_extensions'] ?? [] as $name => $data) {
            if (isset($data['version_overrides'][$phpMajorVersion])) {
                $data['version'] = $data['version_overrides'][$phpMajorVersion];
            }
            unset($data['version_overrides']);
            $extensions[$name] = $data;
        }

        return $extensions;
    }

    /**
     * @param value-of<Dockerfile::TYPES> $type
     */
    protected function getContext(string $type, string $phpVersion): array
    {
        $context = array_merge(
            $this->getConfig()['image'],
            [
                'php_version'             => $phpVersion,
                'image_type'              => $type,
                'swoole_version'          => $this->getSwooleVersion(),
                'swoole_620_or_later'     => $this->isSwoole620OrLater(),
                'swoole_stdext_supported' => $this->isSwooleStdextSupported(),
                'php_extensions'          => $this->getPhpExtensions($phpVersion),
            ]
        );

        if ($type === self::ALPINE) {
            $context['alpine_version'] = $this->getAlpineVersion($phpVersion);
        }

        return $context;
    }

    /**
     * @param value-of<Dockerfile::TYPES> $type
     */
    protected function getTemplateFile(string $type): string
    {
        return ($type == self::ALPINE) ? 'Dockerfile.alpine.twig' : 'Dockerfile.twig';
    }

    protected function getAlpineVersion(string $phpVersion): string
    {
        $phpMajorVersion = $this->getPhpMajorVersion($phpVersion);
        if (!array_key_exists($phpMajorVersion, self::ALPINE_VERSIONS)) {
            throw new Exception("No matching version of Alpine found for PHP {$phpVersion}.");
        }

        return self::ALPINE_VERSIONS[$phpMajorVersion];
    }

    private function renderToString(string $phpVersion, string $type): string
    {
        return $this->getTwig()
            ->load($this->getTemplateFile($type))
            ->render($this->getContext($type, $phpVersion))
        ;
    }

    private function getTwig(): Environment
    {
        return $this->twig ??= new Environment(
            new FilesystemLoader($this->getBasePath()),
            ['autoescape' => false]
        );
    }
}
