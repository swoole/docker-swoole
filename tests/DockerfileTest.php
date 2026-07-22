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
#[CoversMethod(Dockerfile::class, 'getPhpMajorVersion')]
#[CoversMethod(Dockerfile::class, 'isSwoole620OrLater')]
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
