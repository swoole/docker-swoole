<?php

namespace Swoole\Tests\Docker;

use CrowdStar\Reflection\Reflection;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use Swoole\Docker\Dockerfile;
use Swoole\Exception;

/**
 * Class DockerfileTest
 *
 * @package Swoole\Tests\Docker
 */
class DockerfileTest extends TestCase
{
    /**
     * @return array
     */
    public function dataGetPhpMajorVersion(): array
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
     * @dataProvider dataGetPhpMajorVersion
     * @covers Dockerfile::getPhpMajorVersion
     * @param string $expected
     * @param string $phpVersion
     * @param string $message
     * @throws ReflectionException
     */
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

    /**
     * @return array
     */
    public function dataIsValidSwooleVersion(): array
    {
        return [
            [
                true,
                '4.3.5',
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
                ' 4.3.5',
                'leading space found',
            ],
            [
                false,
                '4.3.5 ',
                'trailing space found',
            ],
            [
                false,
                ' 4.3.5 ',
                'spaces around',
            ],
            [
                false,
                ' 4.3.5a',
                'letter(s) found',
            ],
            [
                false,
                '4.3.5-',
                'no image revision included',
            ],
            [
                false,
                '4.3.5-@',
                'invalid character(s) in the revision part',
            ],
            [
                false,
                '04.3.5',
                'leading zero(s) found in major version',
            ],
            [
                false,
                '4.03.5',
                'leading zero(s) found in minor version',
            ],
            [
                false,
                '4.3.05',
                'leading zero(s) found in the patch part',
            ],
        ];
    }

    /**
     * @dataProvider dataIsValidSwooleVersion
     * @covers Dockerfile::isValidSwooleVersion
     * @param bool $expected
     * @param string $imageTag
     * @param string $message
     * @throws ReflectionException
     */
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
}
