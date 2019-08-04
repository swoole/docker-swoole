<?php

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
    const ARCH_AMD64   = 'amd64';
    const ARCH_ARM64V8 = 'arm64v8';
    const ARCH_DEFAULT = self::ARCH_AMD64;

    // @see https://github.com/docker-library/official-images#architectures-other-than-amd64
    const BASE_IMAGES = [
        // architecture    => base image,
        self::ARCH_AMD64   => 'php',
        self::ARCH_ARM64V8 => 'arm64v8/php',
    ];

    /**
     * @var string
     */
    protected $basePath;

    /**
     * @var string
     */
    protected $swooleVersion;

    /**
     * @var array
     */
    protected $config;

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
                $this->renderByArchitecture($phpVersion, $architecture, true);
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
    public function renderByArchitecture(string $phpVersion, string $architecture, bool $save = false): string
    {
        $dockerFile = (new Environment(new FilesystemLoader($this->getBasePath())))
            ->load('Dockerfile.twig')
            ->render($this->getContext($phpVersion, $architecture));

        if ($save) {
            $dockerFileDir = $this->getDockerFileDir($architecture);
            if (!file_exists($dockerFileDir)) {
                mkdir($dockerFileDir, 0777, true);
            }
            file_put_contents(
                sprintf(
                    '%s/%s-php%s.Dockerfile',
                    $dockerFileDir,
                    $this->getSwooleVersion(),
                    $this->getPhpMajorVersion($phpVersion)
                ),
                $dockerFile
            );
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
     * @throws Exception
     */
    public function setSwooleVersion(string $swooleVersion): self
    {
        if (!$this->isValidSwooleVersion($swooleVersion)) {
            throw new Exception(
                "Swoole version must be in the format of 'X.Y.Z'."
            );
        }

        $this->swooleVersion = $swooleVersion;

        if (!is_file($this->getConfigFilePath()) || !is_readable($this->getConfigFilePath())) {
            throw new Exception("Config file unreadable for Swoole version '{$swooleVersion}'.");
        }

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
     * @return string
     */
    protected function getDockerFileDir(string $architecture): string
    {
        return "{$this->getBasePath()}/temp/dockerfiles/{$architecture}";
    }

    /**
     * @return string
     */
    protected function getConfigFilePath(): string
    {
        return $this->getConfigFilePathBySwooleVersion($this->getSwooleVersion());
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
        return preg_match('/^[1-9]\d*\.(0|[1-9]\d*)\.(0|[1-9]\d*)$/', $swooleVersion);
    }

    /**
     * @param string $phpVersion
     * @param string $architecture
     * @return array
     * @throws Exception
     */
    protected function getContext(string $phpVersion, string $architecture = self::ARCH_DEFAULT): array
    {
        if (!array_key_exists($architecture, self::BASE_IMAGES)) {
            throw new Exception("Architecture '{$architecture}' not supported.");
        }

        return array_merge(
            $this->getConfig()['image'],
            [
                'image_name'  => self::BASE_IMAGES[$architecture],
                'php_version' => $phpVersion,
            ]
        );
    }
}