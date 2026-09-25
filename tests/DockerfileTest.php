<?php

declare(strict_types=1);

namespace Swoole\Tests\Docker;

use CrowdStar\Reflection\Reflection;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Swoole\Docker\Dockerfile;

/**
 * Class DockerfileTest
 *
 * @internal
 * @coversNothing
 */
#[CoversMethod(Dockerfile::class, 'getPhpExtensions')]
#[CoversMethod(Dockerfile::class, 'getPhpMajorVersion')]
#[CoversMethod(Dockerfile::class, 'isSwoole620OrLater')]
#[CoversMethod(Dockerfile::class, 'isSwoole630OrLater')]
#[CoversMethod(Dockerfile::class, 'isSwooleStdextSupported')]
#[CoversMethod(Dockerfile::class, 'isValidSwooleVersion')]
class DockerfileTest extends TestCase
{
    /**
     * @throws \ReflectionException
     */
    #[DataProvider('dataIsSwoole620OrLater')]
    public function testIsSwoole620OrLater(bool $expected, string $swooleVersion, string $message): void
    {
        $dockerfile = (new \ReflectionClass(Dockerfile::class))
            ->newInstanceWithoutConstructor()
            ->setSwooleVersion($swooleVersion)
        ;
        self::assertSame($expected, Reflection::callMethod($dockerfile, 'isSwoole620OrLater'), $message);
    }

    public static function dataIsSwoole620OrLater(): array
    {
        return [
            [
                true,
                'nightly',
                'nightly images build the master branch of Swoole',
            ],
            [
                true,
                '6.2.0',
                'the first version with FTP and SSH2 support, and without option --enable-openssl',
            ],
            [
                true,
                '6.2.1',
                'a patch version after 6.2.0',
            ],
            [
                true,
                '6.10.0',
                'a minor version # over 10 (must not be compared as a string)',
            ],
            [
                true,
                '7.0.0',
                'a major version after 6.x',
            ],
            [
                true,
                '6.3.0-rc1',
                'a pre-release of a version after 6.2.0',
            ],
            [
                false,
                '6.1.8',
                'a 6.1.x version',
            ],
            [
                false,
                '6.1.99',
                'a large 6.1.x patch version (must not be compared as a string)',
            ],
            [
                false,
                '5.1.7',
                'a 5.x version',
            ],
        ];
    }

    /**
     * @throws \ReflectionException
     */
    #[DataProvider('dataIsSwoole630OrLater')]
    public function testIsSwoole630OrLater(bool $expected, string $swooleVersion, string $message): void
    {
        $dockerfile = (new \ReflectionClass(Dockerfile::class))
            ->newInstanceWithoutConstructor()
            ->setSwooleVersion($swooleVersion)
        ;
        self::assertSame($expected, Reflection::callMethod($dockerfile, 'isSwoole630OrLater'), $message);
    }

    public static function dataIsSwoole630OrLater(): array
    {
        return [
            [
                true,
                'nightly',
                'nightly images build the master branch of Swoole',
            ],
            [
                true,
                '6.3.0-rc1',
                'a pre-release of 6.3.0 (must count as 6.3.0, although version_compare() orders it before 6.3.0)',
            ],
            [
                true,
                '6.3.0',
                'the first version built with c-ares',
            ],
            [
                true,
                '6.10.0',
                'a minor version # over 10 (must not be compared as a string)',
            ],
            [
                true,
                '7.0.0',
                'a major version after 6.x',
            ],
            [
                false,
                '6.2.3',
                'a 6.2.x version',
            ],
            [
                false,
                '6.2.99',
                'a large 6.2.x patch version (must not be compared as a string)',
            ],
            [
                false,
                '5.1.7',
                'a 5.x version',
            ],
        ];
    }

    /**
     * @throws \ReflectionException
     */
    #[DataProvider('dataIsSwooleStdextSupported')]
    public function testIsSwooleStdextSupported(bool $expected, string $swooleVersion, string $message): void
    {
        $dockerfile = (new \ReflectionClass(Dockerfile::class))
            ->newInstanceWithoutConstructor()
            ->setSwooleVersion($swooleVersion)
        ;
        self::assertSame($expected, Reflection::callMethod($dockerfile, 'isSwooleStdextSupported'), $message);
    }

    public static function dataIsSwooleStdextSupported(): array
    {
        return [
            [
                false,
                'nightly',
                'nightly images build the master branch of Swoole, which dropped stdext support',
            ],
            [
                false,
                '6.3.0-rc1',
                'a pre-release of 6.3.0, the first release without stdext support',
            ],
            [
                false,
                '6.3.0',
                'the first release without stdext support',
            ],
            [
                false,
                '7.0.0',
                'a major version after 6.x',
            ],
            [
                true,
                '6.2.3',
                'a 6.2.x version, built before stdext support was dropped',
            ],
            [
                true,
                '6.1.8',
                'a 6.1.x version',
            ],
        ];
    }

    /**
     * @throws \ReflectionException
     */
    #[DataProvider('dataGetPhpExtensions')]
    public function testGetPhpExtensions(array $expected, string $phpVersion, string $message): void
    {
        $dockerfile = (new \ReflectionClass(Dockerfile::class))
            ->newInstanceWithoutConstructor()
            ->setConfig([
                'image' => [
                    'php_extensions' => [
                        'igbinary' => [
                            'version'           => '3.2.16',
                            'version_overrides' => ['8.5' => '3.2.17RC1'],
                            'enabled'           => true,
                        ],
                        'redis' => [
                            'version' => '6.3.0',
                            'enabled' => true,
                        ],
                    ],
                ],
            ])
        ;
        self::assertSame($expected, Reflection::callMethod($dockerfile, 'getPhpExtensions', [$phpVersion]), $message);
    }

    public static function dataGetPhpExtensions(): array
    {
        return [
            [
                [
                    'igbinary' => ['version' => '3.2.16', 'enabled' => true],
                    'redis'    => ['version' => '6.3.0', 'enabled' => true],
                ],
                '8.4.26',
                'a PHP version without any override',
            ],
            [
                [
                    'igbinary' => ['version' => '3.2.17RC1', 'enabled' => true],
                    'redis'    => ['version' => '6.3.0', 'enabled' => true],
                ],
                '8.5.11',
                'a PHP patch version whose major version has an override',
            ],
            [
                [
                    'igbinary' => ['version' => '3.2.17RC1', 'enabled' => true],
                    'redis'    => ['version' => '6.3.0', 'enabled' => true],
                ],
                '8.5',
                'a PHP major version (as used by nightly images) that has an override',
            ],
        ];
    }

    /**
     * @throws \ReflectionException
     */
    #[DataProvider('dataGetPhpMajorVersion')]
    public function testGetPhpMajorVersion(string $expected, string $phpVersion, string $message): void
    {
        self::assertSame(
            $expected,
            Reflection::callMethod(
                $this->getStubBuilder(Dockerfile::class)->disableOriginalConstructor()->getStub(),
                'getPhpMajorVersion',
                [
                    $phpVersion,
                ]
            ),
            $message
        );
    }

    public static function dataGetPhpMajorVersion(): array
    {
        return [
            [
                '7.3',
                '7.3',
                'a typical semantic version # without patch version included',
            ],
            [
                '7.3',
                '7.3.6',
                'a typical semantic version #',
            ],
        ];
    }

    /**
     * @throws \ReflectionException
     */
    #[DataProvider('dataIsValidSwooleVersion')]
    public function testIsValidSwooleVersion(bool $expected, string $imageTag, string $message): void
    {
        self::assertSame(
            $expected,
            Reflection::callMethod(
                $this->getStubBuilder(Dockerfile::class)->disableOriginalConstructor()->getStub(),
                'isValidSwooleVersion',
                [
                    $imageTag,
                ]
            ),
            $message
        );
    }

    public static function dataIsValidSwooleVersion(): array
    {
        return [
            [
                true,
                '4.3.6',
                'a typical semantic version #',
            ],
            [
                true,
                '701.301.201',
                'a typical semantic version # where each part is over 100',
            ],

            [
                true,
                '6.3.0-rc1',
                'a release candidate',
            ],
            [
                true,
                '6.0.0-alpha',
                'an alpha release',
            ],
            [
                false,
                '',
                'an empty string',
            ],
            [
                false,
                ' ',
                'one space',
            ],
            [
                false,
                'a',
                'character "a"',
            ],
            [
                false,
                '4.3',
                'no patch part included in the version #',
            ],
            [
                false,
                ' 4.3.6',
                'leading space found',
            ],
            [
                false,
                '4.3.6 ',
                'trailing space found',
            ],
            [
                false,
                ' 4.3.6 ',
                'spaces around',
            ],
            [
                false,
                ' 4.3.6a',
                'letter(s) found',
            ],
            [
                false,
                '4.3.6-',
                'no image revision included',
            ],
            [
                false,
                '4.3.6-1',
                'the pre-release part does not start with a letter',
            ],
            [
                false,
                '4.3.6rc1',
                'no hyphen before the pre-release part',
            ],
            [
                false,
                '4.3.6-@',
                'invalid character(s) in the revision part',
            ],
            [
                false,
                '04.3.6',
                'leading zero(s) found in major version',
            ],
            [
                false,
                '4.03.6',
                'leading zero(s) found in minor version',
            ],
            [
                false,
                '4.3.06',
                'leading zero(s) found in the patch part',
            ],
        ];
    }

    #[DataProvider('dataSetSwooleVersionThrows')]
    public function testSetSwooleVersionThrows(string $invalidVersion): void
    {
        $this->expectException(\Swoole\Docker\Exception::class);
        (new \ReflectionClass(Dockerfile::class))
            ->newInstanceWithoutConstructor()
            ->setSwooleVersion($invalidVersion)
        ;
    }

    public static function dataSetSwooleVersionThrows(): array
    {
        return [
            [''],
            ['not-a-version'],
            ['4.3'],
            ['04.3.6'],
        ];
    }
}
