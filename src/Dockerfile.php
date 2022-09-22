<?php

declare(strict_types=1);

namespace Swoole\Docker;

use Symfony\Component\Yaml\Yaml;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * Class Dockerfile
 *
 * @package Swoole\Docker
 */
class Dockerfile
{
    protected const ALPINE = 'alpine';
    protected const CLI = 'cli';

    protected const ALPINE_VERSIONS = [
        // PHP major version => Alpine version,
        '7.1' => '3.10',
        '7.2' => '3.12',
        '7.3' => '3.13',
        '7.4' => '3.15',
        '8.0' => '3.15',
        '8.1' => '3.15',
        '8.2' => '3.15',
    ];

    protected string $basePath;

    protected string $swooleVersion;

    protected array $config;

    /**
     * Dockerfile constructor.
     *
     * @param string $swooleVersion
     * @throws Exception
     */
    public function __construct(string $swooleVersion)
    {
        $this
            ->setBasePath(dirname(__DIR__))
            ->setSwooleVersion($swooleVersion)
            ->setConfig(Yaml::parseFile("{$this->getConfigFilePath()}"));
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
            foreach ([self::ALPINE, self::CLI] as $type) {
                $this->generateDockerFile($phpVersion, $type, true);
            }
        }
    }

    /**
     * @param string $phpVersion
     * @param string $type
     * @param bool $save
     * @return string
     * @throws Exception
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function generateDockerFile(string $phpVersion, string $type, bool $save = false): string
    {
        $dockerFile = (new Environment(new FilesystemLoader($this->getBasePath()), ['autoescape' => false]))
            ->load($this->getTemplateFile($type))
            ->render($this->getContext($phpVersion));

        if ($save) {
            $dockerFileDir = $this->getDockerFileDir($type, $phpVersion);
            if (!file_exists($dockerFileDir)) {
                mkdir($dockerFileDir, 0777, true);
            }

            file_put_contents("{$dockerFileDir}/Dockerfile", $dockerFile);
        }

        return $dockerFile;
    }

    /**
     * @return string
     */
    public function getBasePath(): string
    {
        return $this->basePath;
    }

    /**
     * @param string $basePath
     * @return Dockerfile
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

    /**
     * @return string
     */
    public function getSwooleVersion(): string
    {
        return $this->swooleVersion;
    }

    /**
     * @param string $swooleVersion
     * @return Dockerfile
     */
    public function setSwooleVersion(string $swooleVersion): self
    {
        $this->swooleVersion = $swooleVersion;

        return $this;
    }

    /**
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * @param array $config
     * @return Dockerfile
     */
    public function setConfig(array $config): self
    {
        $this->config = $config;

        return $this;
    }

    /**
     * @param string $phpVersion
     * @return string
     */
    protected function getPhpMajorVersion(string $phpVersion): string
    {
        return preg_replace('/^(\d+\.\d+).*$/', '$1', $phpVersion);
    }

    /**
     * @param string $type
     * @param string $phpVersion Needed only when creating Dockerfiles for a released version of Swoole.
     * @return string
     */
    protected function getDockerFileDir(string $type, string $phpVersion): string
    {
        return sprintf(
            "%s/dockerfiles/%s/php%s/%s",
            $this->getBasePath(),
            $this->getSwooleVersion(),
            $this->getPhpMajorVersion($phpVersion),
            $type,
        );
    }

    /**
     * @return string
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

    /**
     * @param string $swooleVersion
     * @return string
     */
    protected function getConfigFilePathBySwooleVersion(string $swooleVersion): string
    {
        return "{$this->getBasePath()}/config/{$swooleVersion}.yml";
    }

    /**
     * @param string $swooleVersion
     * @return bool
     */
    protected function isValidSwooleVersion(string $swooleVersion): bool
    {
        return (bool) preg_match('/^[1-9]\d*\.(0|[1-9]\d*)\.(0|[1-9]\d*)$/', $swooleVersion);
    }

    /**
     * @see https://github.com/swoole/swoole-src/releases/tag/v4.5.7
     */
    protected function getContext(string $phpVersion): array
    {
        if (
            ($this->getSwooleVersion() === 'latest')
            || (version_compare($this->getSwooleVersion(), '4.6.0-alpha') >= 0)
        ) {
            $optionCurl = ($phpVersion != '8.0.0');
        } else {
            $optionCurl = false;
        }

        if (($this->getSwooleVersion() === 'latest') || (version_compare($this->getSwooleVersion(), '4.5.7') >= 0)) {
            $optionJson = true;
        } else {
            $optionJson = false;
        }

        return array_merge(
            $this->getConfig()['image'],
            [
                'php_version'    => $phpVersion,
                'alpine_version' => $this->getAlpineVersion($phpVersion),
                'swoole_version' => $this->getSwooleVersion(),
                'option_curl'    => $optionCurl,
                'option_json'    => $optionJson,
            ]
        );
    }

    protected function getTemplateFile(string $type): string
    {
        return (self::ALPINE == $type) ? 'Dockerfile.alpine.twig' : 'Dockerfile.cli.twig';
    }

    protected function getAlpineVersion(string $phpVersion): string
    {
        $phpMajorVersion = preg_replace('/^(\d+\.\d+).*$/', '$1', $phpVersion);
        if (!array_key_exists($phpMajorVersion, self::ALPINE_VERSIONS)) {
            throw new Exception("No matching version of Alpine found for PHP {$phpVersion}.");
        }

        return self::ALPINE_VERSIONS[$phpMajorVersion];
    }
}
