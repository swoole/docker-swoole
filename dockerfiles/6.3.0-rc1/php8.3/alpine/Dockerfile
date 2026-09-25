FROM php:8.3.33-cli-alpine3.23

COPY --from=composer:2.10.3 /usr/bin/composer /usr/bin/

RUN \
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
    pecl channel-update pecl.php.net && \
    MAKEFLAGS="-j$(nproc)" pecl install igbinary-3.2.16 && \
    MAKEFLAGS="-j$(nproc)" pecl install --configureoptions 'enable-redis-igbinary="yes" enable-redis-lzf="yes" enable-redis-zstd="yes" enable-redis-msgpack="no" enable-redis-lz4="no" with-liblz4="yes"' redis-6.3.0 && \
# PHP extension Redis is included since 4.8.12+ and 5.0.1+.
    docker-php-ext-enable igbinary && \
    docker-php-ext-enable redis && \
    docker-php-ext-install sockets && \
    docker-php-source extract && \
    mkdir /usr/src/php/ext/swoole && \
    curl -sSfL --retry 5 --retry-all-errors --connect-timeout 20 https://github.com/swoole/swoole-src/archive/v6.3.0-rc1.tar.gz -o swoole.tar.gz && \
    tar xfz swoole.tar.gz --strip-components=1 -C /usr/src/php/ext/swoole && \
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
    rm -f swoole.tar.gz && \
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
