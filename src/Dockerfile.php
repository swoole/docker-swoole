<?php

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
    const ARCH_AMD64   = 'amd64';
    const ARCH_ARM64V8 = 'arm64v8';
    const ARCH_DEFAULT = self::ARCH_AMD64;

    const VERSION_BASED = 0;
    const BRANCH_BASED  = 1;

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
     * @var int
     */
    protected $type;

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
        $dockerFile = (new Environment(new FilesystemLoader("{$this->getBasePath()}/dockerfiles")))
            ->load($this->getDockerfileTemplate($architecture))
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

        return $this->setType();
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
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * @return Dockerfile
     */
    public function setType(): self
    {
        $this->type = $this->isValidSwooleVersion($this->getSwooleVersion()) ? self::VERSION_BASED : self::BRANCH_BASED;

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
        switch ($this->getType()) {
            case self::VERSION_BASED:
                return sprintf(
                    "%s/dockerfiles/%s/%s/php%s",
                    $this->getBasePath(),
                    $this->getSwooleVersion(),
                    $architecture,
                    $this->getPhpMajorVersion($phpVersion)
                );
            case self::BRANCH_BASED:
                if ("master" == $this->getSwooleVersion()) {
                    return "{$this->getBasePath()}/dockerfiles/latest/{$architecture}";
                } else {
                    return "{$this->getBasePath()}/dockerfiles/{$this->getSwooleVersion()}/{$architecture}";
                }
            default:
                throw new Exception("The image should be built from a stable version of Swoole or a branch of Swoole.");
        }
    }

    /**
     * @return string
     * @throws Exception
     */
    protected function getConfigFilePath(): string
    {
        switch ($this->getType()) {
            case self::VERSION_BASED:
                $file = $this->getConfigFilePathBySwooleVersion($this->getSwooleVersion());
                break;
            case self::BRANCH_BASED:
                $file = $this->getConfigFilePathBySwooleVersion('latest');
                break;
            default:
                throw new Exception("The image should be built from a stable version of Swoole or a branch of Swoole.");
                break;
        }

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
        return preg_match('/^[1-9]\d*\.(0|[1-9]\d*)\.(0|[1-9]\d*)$/', $swooleVersion);
    }

    /**
     * @param string $architecture
     * @return string
     */
    protected function getDockerfileTemplate(string $architecture): string
    {
        switch ($architecture) {
            case self::ARCH_DEFAULT:
                return 'Dockerfile.twig';
            default:
                return sprintf('Dockerfile-%s.twig', $architecture);
        }
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
        if (!array_key_exists($architecture, self::BASE_IMAGES)) {
            throw new Exception("Architecture '{$architecture}' not supported.");
        }

        return array_merge(
            $this->getConfig()['image'],
            [
                'image_name'     => self::BASE_IMAGES[$architecture],
                'php_version'    => $phpVersion,
                'swoole_version' => $this->getSwooleVersion(),
            ]
        );
    }
}
