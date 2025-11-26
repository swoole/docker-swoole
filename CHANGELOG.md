Table of Contents
=================

* [Swoole 6.1](#swoole-61)
   * [6.1.3](#613)
   * [6.1.2](#612)
   * [6.1.1](#611)
   * [6.1.0](#610)
* [Swoole 6.0](#swoole-60)
   * [6.0.2](#602)
   * [6.0.1](#601)
   * [6.0.0](#600)
* [Swoole 5.1](#swoole-51)
   * [5.1.8](#518)
   * [5.1.7](#517)
   * [5.1.6](#516)
   * [5.1.5](#515)
   * [5.1.4](#514)
   * [5.1.3](#513)
   * [5.1.2](#512)
   * [5.1.1](#511)
   * [5.1.0](#510)
* [Swoole 5.0](#swoole-50)
   * [5.0.3](#503)
   * [5.0.2](#502)
   * [5.0.1](#501)
   * [5.0.0](#500)
* [Swoole 4.8](#swoole-48)
   * [4.8.13](#4813)
   * [4.8.12](#4812)
   * [4.8.11](#4811)
   * [4.8.10](#4810)
   * [4.8.9](#489)
   * [4.8.8](#488)
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

# Swoole 6.1

## 6.1.3

## 6.1.2

## 6.1.1

### Changed
- Upgrade PHP extension _Redis_ from 6.2.0 to 6.3.0.
- Upgrade _Composer_ from 2.8.12 to 2.9.2.

## 6.1.0

### Changed
- **Enabled option _--enable-swoole-stdext_ and _--enable-zstd_ when installing Swoole.**

# Swoole 6.0

## 6.0.2

### Changed
- Upgrade PHP extension _Redis_ from 6.1.0 to 6.2.0.
- Upgrade _Composer_ from 2.8.5 to 2.8.12.

## 6.0.1

### Changed
- Upgrade _Composer_ from 2.8.4 to 2.8.5.

## 6.0.0

### Changed
- Upgrade _Composer_ from 2.8.3 to 2.8.4.
- Enabled SQLite support in Swoole.

# Swoole 5.1

## 5.1.8

### Changed
- Upgrade _Composer_ from 2.8.5 to 2.8.12.

## 5.1.7

### Changed
- Upgrade _Composer_ from 2.8.3 to 2.8.5.

## 5.1.6

### Changed
- Enabled SQLite support in Swoole.

## 5.1.5

### Changed
- Upgrade PHP extension _Redis_ from 6.0.2 to 6.1.0.
- Upgrade _Composer_ from 2.7.8 to 2.8.3.

## 5.1.4

### Changed
- Upgrade _Composer_ from 2.7.7 to 2.7.8.

## 5.1.3

### Changed
- Upgrade _Composer_ from 2.6.6 to 2.7.7.
- Enabled Brotli support in Swoole.

## 5.1.2

### Changed
- Upgrade _Composer_ from 2.6.5 to 2.6.6.

## 5.1.1

### Changed
- Upgrade PHP extension _Redis_ from 6.0.1 to 6.0.2.

## 5.1.0

### Changed
- Upgrade PHP extension _Redis_ from 5.3.7 to 6.0.1.
- Upgrade _Composer_ from 2.5.5 to 2.6.5.

# Swoole 5.0

## 5.0.3

### Changed
- Upgrade _Composer_ from 2.5.2 to 2.5.5.

## 5.0.2

### Changed
- Upgrade _Composer_ from 2.4.4 to 2.5.2.

## 5.0.1

### Added
- New Docker images built for PHP 8.2.
- [The Redis extension](https://pecl.php.net/package/redis) is included and enabled.
- PHP extension _pdo_mysql_ is included and enabled.

### Changed
- Upgrade _Composer_ from 2.3.10 to 2.4.4.

## 5.0.0

### Added
- the Swoole-based PostgreSQL client (formerly the [swoole_postgresql](https://github.com/swoole/ext-postgresql) extension) is included.

### Changed
- Upgrade _Composer_ from 2.2.16 to 2.3.10.

### Removed
- Swoole 5.0.0+ works with PHP 8.0+ only. No more PHP 7 images built.

# Swoole 4.8

## 4.8.13

### Changed
- Upgrade _Composer_ from 2.2.18 to 2.2.21.

## 4.8.12

### Added
- New Docker images built for PHP 8.2.
- [The Redis extension](https://pecl.php.net/package/redis) is included and enabled.
- PHP extension _pdo_mysql_ is included and enabled.

### Changed
- Upgrade _Composer_ from 2.2.16 to 2.2.18.

## 4.8.11

### Changed
- Upgrade _Composer_ from 2.2.12 to 2.2.16.

## 4.8.10

## 4.8.9

### Changed
- Upgrade _Composer_ from 2.1.14 to 2.2.12.

## 4.8.8

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
