Table of Contents
=================

   * [Published Images](#published-images)
      * [Latest](#latest)
      * [4.4.14](#4414)
         * [Added](#added)
      * [4.4.13](#4413)
         * [Changed](#changed)
      * [4.4.12](#4412)
         * [Changed](#changed-1)
      * [4.4.8](#448)
         * [Added](#added-1)
         * [Changed](#changed-2)
      * [4.4.7](#447)
         * [Added](#added-2)
         * [Changed](#changed-3)
      * [4.4.6](#446)
         * [Changed](#changed-4)
      * [4.4.5](#445)
         * [Changed](#changed-5)
      * [4.3.6](#436)

# Published Images

## Latest

## 4.4.14

### Added
- Support integration with _Blackfire_.
- Add new example on debugging PHP code with _Blackfire_.

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
