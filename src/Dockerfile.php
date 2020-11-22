<?php

declare(strict_types=1);

namespace Swoole\Docker;

use Symfony\Component\Filesystem\Filesystem;
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

    protected const ARCH_AMD64   = 'amd64';
    protected const ARCH_ARM64V8 = 'arm64v8';
    protected const ARCH_DEFAULT = self::ARCH_AMD64;

    // @see https://github.com/docker-library/official-images#architectures-other-than-amd64
    protected const BASE_IMAGES = [
        // architecture    => base image,
        self::ARCH_AMD64   => 'php',
        self::ARCH_ARM64V8 => 'arm64v8/php',

        // For Aline images, we use the official PHP images as base images.
        self::ALPINE       => 'php',
    ];

    protected const ALPINE_VERSIONS = [
        // PHP major version => Alpine version,
        '7.1' => '3.10',
        '7.2' => '3.12',
        '7.3' => '3.12',
        '7.4' => '3.12',
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
            foreach (array_keys(self::BASE_IMAGES) as $architecture) {
                $this->generateDockerFile($phpVersion, $architecture, true);
            }

            // Generate Dockerfiles for Alpine images.
            if ($this->getSwooleVersion() != 'latest') {
                $this->generateDockerFile($phpVersion, self::ALPINE, true);
            }
        }
    }

    /**
     * @param string $phpVersion
     * @param string $architecture
     * @param bool $save
     * @return string
     * @throws Exception
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function generateDockerFile(string $phpVersion, string $architecture, bool $save = false): string
    {
        $dockerFile = (new Environment(new FilesystemLoader($this->getBasePath())))
            ->load($this->getTemplateFile($architecture))
            ->render($this->getContext($phpVersion, $architecture));

        if ($save) {
            $dockerFileDir = $this->getDockerFileDir($architecture, $phpVersion);
            if (!file_exists($dockerFileDir)) {
                mkdir($dockerFileDir, 0777, true);
            }

            file_put_contents("{$dockerFileDir}/Dockerfile", $dockerFile);

            $hookTemplatesDir = $this->getHookDir($architecture);
            if ($hookTemplatesDir) {
                $hookDir = "{$dockerFileDir}/hooks";
                if (is_dir($hookDir)) {
                    (new Filesystem())->remove($hookDir);
                }
                (new Filesystem())->mirror($hookTemplatesDir, $hookDir);
            }
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
     * @param string $architecture
     * @param string $phpVersion Needed only when creating Dockerfiles for a released version of Swoole.
     * @return string
     */
    protected function getDockerFileDir(string $architecture, string $phpVersion): string
    {
        return sprintf(
            "%s/dockerfiles/%s/%s/php%s",
            $this->getBasePath(),
            $this->getSwooleVersion(),
            $architecture,
            $this->getPhpMajorVersion($phpVersion)
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
     * @param string $architecture
     * @return string
     */
    protected function getHookDir(string $architecture): string
    {
        $dir = sprintf("%s/hooks/%s", $this->getBasePath(), $architecture);

        return (is_dir($dir) ? $dir : "");
    }

    /**
     * @param string $phpVersion
     * @param string $architecture
     * @return array
     * @throws Exception
     */
    protected function getContext(string $phpVersion, string $architecture = self::ARCH_DEFAULT): array
    {
        if (array_key_exists($architecture, self::BASE_IMAGES)) {
            $imageName = self::BASE_IMAGES[$architecture];
        } else {
            throw new Exception("Architecture '{$architecture}' not supported.");
        }

        return array_merge(
            $this->getConfig()['image'],
            [
                'image_name'     => $imageName,
                'php_version'    => $phpVersion,
                'alpine_version' => $this->getAlpineVersion($phpVersion),
                'swoole_version' => $this->getSwooleVersion(),
            ]
        );
    }

    protected function getTemplateFile(string $architecture): string
    {
        return (self::ALPINE == $architecture) ? 'Dockerfile.alpine.twig' : 'Dockerfile.twig';
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
