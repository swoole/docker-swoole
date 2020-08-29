Table of Contents
=================

   * [Published Images](#published-images)
      * [Latest](#latest)
      * [4.5.3](#453)
         * [Changed](#changed)
      * [4.4.19 and 4.5.2](#4419-and-452)
      * [4.5.1](#451)
         * [Changed](#changed-1)
      * [4.5.0](#450)
      * [4.4.18](#4418)
         * [Changed](#changed-2)
      * [4.4.17](#4417)
         * [Changed](#changed-3)
      * [4.4.16](#4416)
      * [4.4.15](#4415)
         * [Changed](#changed-4)
      * [4.4.14](#4414)
         * [Added](#added)
         * [Changed](#changed-5)
      * [4.4.13](#4413)
         * [Changed](#changed-6)
      * [4.4.12](#4412)
         * [Changed](#changed-7)
      * [4.4.8](#448)
         * [Added](#added-1)
         * [Changed](#changed-8)
      * [4.4.7](#447)
         * [Added](#added-2)
         * [Changed](#changed-9)
      * [4.4.6](#446)
         * [Changed](#changed-10)
      * [4.4.5](#445)
         * [Changed](#changed-11)
      * [4.3.6](#436)

# Published Images

## Latest

## 4.5.3

### Changed
- Upgrade _Composer_ from 1.10.6 to 1.10.10.

## 4.4.19 and 4.5.2

## 4.5.1

### Changed
- Upgrade _Composer_ from 1.10.5 to 1.10.6.
- If statement "exit" or "exit 0" is used in booting scripts, you need to replace them with a "return" statement.
- **Stop allowing to run PHP scripts directly when booting containers**.

## 4.5.0

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

### Changed
- Support _zlib_.

## 4.3.6

First base image published.
