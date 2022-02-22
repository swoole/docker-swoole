Table of Contents
=================

* [Swoole 4.8](#swoole-48)
   * [4.8.7](#487)
   * [4.8.6](#486)
   * [4.8.5](#485)
   * [4.8.4](#484)
   * [4.8.3](#483)
   * [4.8.2](#482)
   * [4.8.1](#481)
   * [4.8.0](#480)
* [Swoole 4.7](#swoole-47)
   * [4.7.1](#471)
   * [4.7.0](#470)
* [Swoole 4.6](#swoole-46)
   * [4.6.7](#467)
   * [4.6.5](#465)
   * [4.6.4](#464)
   * [4.6.3](#463)
   * [4.6.0](#460)
* [Swoole 4.5](#swoole-45)
   * [4.5.10](#4510)
   * [4.5.9](#459)
   * [4.5.8](#458)
   * [4.5.7](#457)
   * [4.5.6](#456)
   * [4.5.5](#455)
   * [4.5.4](#454)
   * [4.5.3](#453)
   * [4.5.2](#452)
   * [4.5.1](#451)
   * [4.5.0](#450)
* [Swoole 4.4](#swoole-44)
   * [4.4.25](#4425)
   * [4.4.24](#4424)
   * [4.4.23](#4423)
   * [4.4.22](#4422)
   * [4.4.22](#4422-1)
   * [4.4.21](#4421)
   * [4.4.20](#4420)
   * [4.4.19](#4419)
   * [4.4.18](#4418)
   * [4.4.17](#4417)
   * [4.4.16](#4416)
   * [4.4.15](#4415)
   * [4.4.14](#4414)
   * [4.4.13](#4413)
   * [4.4.12](#4412)
   * [4.4.8](#448)
   * [4.4.7](#447)
   * [4.4.6](#446)
   * [4.4.5](#445)
* [Swoole 4.3](#swoole-43)
   * [4.3.6](#436)

# Swoole 4.8

## 4.8.7

## 4.8.6

## 4.8.5

### Changed
- Alpine images are built with Alpine Linux 3.15 for PHP 7.4+ (PHP 7.4 to PHP 8.1).

## 4.8.4

## 4.8.3

### Changed
- Upgrade _Composer_ from 2.1.12 to 2.1.14.

### Added
- Started building PHP 8.1 images.

## 4.8.2

### Changed
- Upgrade _Composer_ from 2.1.9 to 2.1.12.

## 4.8.1

## 4.8.0

### Changed
- Upgrade _Composer_ from 2.1.6 to 2.1.9.

# Swoole 4.7

## 4.7.1

### Changed
- Upgrade _Composer_ from 2.1.3 to 2.1.6.

## 4.7.0

Branched from branch _4.6.7_.

### Changed
- Upgrade _Composer_ from 2.0.13 to 2.1.3.

# Swoole 4.6

## 4.6.7

### Changed
- Upgrade _Composer_ from 2.0.12 to 2.0.13.

## 4.6.5

### Changed
- Upgrade _Composer_ from 2.0.11 to 2.0.12.

## 4.6.4

### Changed
- Upgrade _Composer_ from 2.0.9 to 2.0.11.

## 4.6.3

### Changed
- Upgrade _Composer_ from 2.0.8 to 2.0.9.

## 4.6.0

Branched from branch _4.5.10_.

# Swoole 4.5

## 4.5.10

### Changed
- Upgrade _Composer_ from 2.0.7 to 2.0.8.

## 4.5.9

### Changed
- **Upgrade _Composer_ from 1.10.17 to 2.0.7.**
- **Support building multi-architecture image with the same tag.**

## 4.5.8

### Changed
- **Added option _--enable-swoole-json_ when installing Swoole.**

## 4.5.7

### Changed
- Upgrade _Composer_ from 1.10.16 to 1.10.17.

## 4.5.6

### Changed
- Upgrade _Composer_ from 1.10.15 to 1.10.16.

## 4.5.5

### Changed
- Upgrade _Composer_ from 1.10.13 to 1.10.15.

## 4.5.4

### Changed
- Upgrade _Composer_ from 1.10.10 to 1.10.13.

## 4.5.3

### Changed
- Upgrade _Composer_ from 1.10.6 to 1.10.10.

## 4.5.2

## 4.5.1

### Changed
- Upgrade _Composer_ from 1.10.5 to 1.10.6.
- If statement "exit" or "exit 0" is used in booting scripts, you need to replace them with a "return" statement.
- **Stop allowing to run PHP scripts directly when booting containers**.

## 4.5.0

Branched from branch _4.4.18_.

# Swoole 4.4

## 4.4.25

### Changed
- Upgrade _Composer_ from 1.10.20 to 1.10.21.

## 4.4.24

### Changed
- Upgrade _Composer_ from 1.10.19 to 1.10.20.

## 4.4.23

### Changed
- Upgrade _Composer_ from 1.10.17 to 1.10.19.

## 4.4.22

### Changed
- Upgrade _Composer_ from 1.10.16 to 1.10.17.

## 4.4.22

### Changed
- Upgrade _Composer_ from 1.10.16 to 1.10.17.

## 4.4.21

### Changed
- Upgrade _Composer_ from 1.10.10 to 1.10.13.

## 4.4.20

### Changed
- Upgrade _Composer_ from 1.10.6 to 1.10.10.

## 4.4.19

### Changed
- Upgrade _Composer_ from 1.10.5 to 1.10.6.
- If statement "exit" or "exit 0" is used in booting scripts, you need to replace them with a "return" statement.
- **Stop allowing to run PHP scripts directly when booting containers**.

## 4.4.18

### Changed
- Upgrade _Composer_ from 1.10.1 to 1.10.5.

## 4.4.17

### Changed
- Upgrade _Composer_ from 1.9.3 to 1.10.1.

## 4.4.16

## 4.4.15

### Changed
- Upgrade _Composer_ from 1.9.1 to 1.9.3.
- Start building images through Docker Hub instead of Travis.

## 4.4.14

### Added
- Support PHP 7.4; latest image is built using PHP 7.4.
- Add new example on how to install [PHP-X](https://github.com/swoole/phpx).
- Add new examples on how to install Swoole extensions.
- Support integration with _Blackfire_.
- Add new example on debugging PHP code with _Blackfire_.
- Support integration with _Sdebug_ (a fork of Xdebug).
- Add new example on debugging PHP code with _Sdebug_.

### Changed
- Don't install [PHP-X](https://github.com/swoole/phpx) by default.
- Don't install Swoole extensions by default.

## 4.4.13

### Changed
- Allow to autoreload on PHP file changes only.
- Upgrade _Composer_ from 1.9.0 to 1.9.1.

## 4.4.12

### Changed
- Pass the number of CPU cores to the -j option when using make.

## 4.4.8

### Added
- [PHP-X](https://github.com/swoole/phpx) included.
- Swoole extension [zookeeper](https://github.com/swoole/ext-zookeeper) included.
- Add _git_, _lsof_, and _vim_ in the development images.
- Add examples on debugging PHP code with _Valgrind_ and _strace_.
- Add new example on disabling the default web server.

### Changed
- Allow to disable the default web server when starting Docker container.
- Allow Docker container to keep running even no any Supervisor program defined.
- Start including Dockerfiles in the source code.

## 4.4.7

### Added
- Start building development images.
- Add new example on debugging PHP code with _gdb_.

### Changed
- Allow to autoreload specific or all Supervisord programs.

## 4.4.6

### Changed
- Allow to run PHP scripts automatically when booting containers.

## 4.4.5

Branched from branch _4.3.6_.

### Changed
- Support _zlib_.

# Swoole 4.3

## 4.3.6

First base image published.
