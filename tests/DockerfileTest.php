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
#[CoversMethod(Dockerfile::class, 'isValidSwooleVersion')]
class DockerfileTest extends TestCase
{
    /**
     * @throws \ReflectionException
     */
    #[DataProvider('dataGetPhpMajorVersion')]
    public function testGetPhpMajorVersion(string $expected, string $phpVersion, string $message): void
    {
        self::assertSame(
            $expected,
            Reflection::callMethod(
                $this->getMockBuilder(Dockerfile::class)->disableOriginalConstructor()->getMock(),
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
                $this->getMockBuilder(Dockerfile::class)->disableOriginalConstructor()->getMock(),
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
}
