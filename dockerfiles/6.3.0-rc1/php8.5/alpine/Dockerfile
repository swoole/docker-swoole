# The source code of Swoole and the packages of the PECL extensions are downloaded in a stage of their own, which the
# build below mounts rather than copies from, so they don't end up in any layer of the image. ADD verifies each
# download against the checksum listed in the configuration file, if any, and records it in the build provenance.
FROM scratch AS sources
ADD --checksum=sha256:5d1d57adf16494f6cbb0211665d8fb7c7e4726c0b81d35e200812df326051e8e https://github.com/swoole/swoole-src/archive/refs/tags/v6.3.0-rc1.tar.gz /swoole-src.tar.gz
ADD --checksum=sha256:91da821443db125282a6aea039f24588dd28ff5d71e8187f6ecc41165bceafbc https://pecl.php.net/get/igbinary-3.2.17RC1.tgz /igbinary.tgz
ADD --checksum=sha256:0d5141f634bd1db6c1ddcda053d25ecf2c4fc1c395430d534fd3f8d51dd7f0b5 https://pecl.php.net/get/redis-6.3.0.tgz /redis.tgz

FROM php:8.5.11-cli-alpine3.23

COPY --from=composer:2.10.3 /usr/bin/composer /usr/bin/

RUN --mount=type=bind,from=sources,target=/tmp/sources \
    set -ex && \
# Keep only the runtime shared libraries in the final image; the matching "-dev" packages (headers) are
# installed as part of the temporary ".build-deps" virtual package and removed once Swoole is built.
# Package "libucontext" provides the ucontext functions (getcontext/makecontext/swapcontext) that musl does not
# implement. Swoole ships hand-written context-switching assembly for some CPUs only (x86_64, arm64, riscv64,
# mips64, loongarch64); on every other architecture its coroutine scheduler falls back to ucontext, and without
# this package the extension fails to load with "Error relocating swoole.so: getcontext: symbol not found".
# That is what happened to the ppc64le and s390x Alpine images. It is installed unconditionally (the package is
# built for all Alpine architectures) so the images keep working on any architecture Swoole has no assembly for.
    apk add --no-cache c-ares libstdc++ libpq libssh2 libucontext unixodbc && \
    apk add --no-cache --virtual .build-deps $PHPIZE_DEPS curl-dev linux-headers brotli-dev c-ares-dev libpq-dev openssl-dev sqlite-dev zlib-dev zstd-dev libssh2-dev libucontext-dev unixodbc-dev && \
# PHP extension pdo_mysql is included since 4.8.12+ and 5.0.1+.
    docker-php-ext-install pdo_mysql && \
    MAKEFLAGS="-j$(nproc)" pecl install /tmp/sources/igbinary.tgz && \
    MAKEFLAGS="-j$(nproc)" pecl install --configureoptions 'enable-redis-igbinary="yes" enable-redis-lzf="yes" enable-redis-zstd="yes" enable-redis-msgpack="no" enable-redis-lz4="no" with-liblz4="yes"' /tmp/sources/redis.tgz && \
# PHP extension Redis is included since 4.8.12+ and 5.0.1+.
    docker-php-ext-enable igbinary && \
    docker-php-ext-enable redis && \
    docker-php-ext-install sockets && \
    docker-php-source extract && \
    mkdir /usr/src/php/ext/swoole && \
    tar xzf /tmp/sources/swoole-src.tar.gz --strip-components=1 -C /usr/src/php/ext/swoole && \
    export LDFLAGS="${LDFLAGS} -lucontext" && \
    docker-php-ext-configure swoole \
        --enable-brotli \
        --enable-cares \
        --enable-mysqlnd \
        --enable-sockets \
        --enable-swoole-curl \
        --enable-swoole-ftp \
        --enable-swoole-pgsql \
        --enable-swoole-sqlite \
        --enable-zstd \
        --with-swoole-odbc=unixODBC,/usr \
        --with-swoole-ssh2 && \
    docker-php-ext-install -j$(nproc) swoole && \
    docker-php-source delete && \
# Strip debugging symbols from the PECL extensions (extension Swoole is stripped by docker-php-ext-install already), and
# install the packages providing the shared libraries that the PHP extensions link against, so that removing the
# build-only packages can't take any of them along. This is how the official PHP images do it.
    strip --strip-all "$(php-config --extension-dir)"/*.so && \
    runDeps="$( \
        scanelf --needed --nobanner --format '%n#p' --recursive "$(php-config --extension-dir)" \
            | tr ',' '\n' \
            | sort -u \
            | awk 'system("[ -e /usr/local/lib/" $1 " ]") == 0 { next } { print "so:" $1 }' \
    )" && \
    apk add --no-cache --virtual .swoole-rundeps $runDeps && \
    apk del .build-deps && \
    rm -rf /tmp/pear && \
# Fail the build if the extension does not actually load. Swoole is compiled here for every target platform, and
# a library that is missing at runtime (rather than at link time) produces a successful build but an image
# whose Swoole extension cannot be loaded at all. PHP only warns about that and still exits 0, so it has to
# be asserted explicitly; the published ppc64le and s390x Alpine images shipped broken this way. The PECL extensions
# are checked too, since removing the build-only packages could take a runtime library of theirs along.
    php -r 'exit(extension_loaded("swoole") && extension_loaded("igbinary") && extension_loaded("redis") ? 0 : 1);'

WORKDIR "/var/www/"
