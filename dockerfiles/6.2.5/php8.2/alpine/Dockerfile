FROM php:8.2.35-cli-alpine3.24

COPY --from=composer:2.11.1 /usr/bin/composer /usr/bin/

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
    apk add --no-cache libstdc++ libpq libssh2 libucontext unixodbc && \
    apk add --no-cache --virtual .build-deps $PHPIZE_DEPS curl-dev linux-headers brotli-dev postgresql-dev openssl-dev pcre-dev pcre2-dev sqlite-dev zlib-dev libssh2-dev libucontext-dev unixodbc-dev && \
# PHP extension pdo_mysql is included since 4.8.12+ and 5.0.1+.
    docker-php-ext-install pdo_mysql && \
    pecl channel-update pecl.php.net && \
    pecl install --configureoptions 'enable-redis-igbinary="no" enable-redis-lzf="yes" enable-redis-zstd="yes" enable-redis-msgpack="no" enable-redis-lz4="no" with-liblz4="yes"' redis-6.3.0 && \
# PHP extension Redis is included since 4.8.12+ and 5.0.1+.
    docker-php-ext-enable redis && \
    docker-php-ext-install sockets && \
    docker-php-source extract && \
    mkdir /usr/src/php/ext/swoole && \
    curl -sfL https://github.com/swoole/swoole-src/archive/v6.2.5.tar.gz -o swoole.tar.gz && \
    tar xfz swoole.tar.gz --strip-components=1 -C /usr/src/php/ext/swoole && \
# The commands of the official PHP images only set the flags of the C compiler, which leaves the C++ compiler with the
# default of Autoconf ("-g -O2"): most of Swoole would be compiled with debugging symbols, only to have them stripped
# afterwards. The linker flags start from those of the official PHP images, which the commands only use when LDFLAGS
# isn't set.
    export CXXFLAGS="${PHP_CFLAGS}" LDFLAGS="${PHP_LDFLAGS} -lucontext" && \
    docker-php-ext-configure swoole \
        --enable-brotli \
        --enable-mysqlnd \
        --enable-sockets \
        --enable-swoole-curl \
        --enable-swoole-ftp \
        --enable-swoole-pgsql \
        --enable-swoole-sqlite \
        --enable-swoole-stdext \
        --enable-zstd \
        --with-swoole-odbc=unixODBC,/usr \
        --with-swoole-ssh2 && \
    docker-php-ext-install -j$(nproc) swoole && \
    rm -f swoole.tar.gz && \
    docker-php-source delete && \
# Option "--no-network" keeps apk from refreshing the package index, which it would otherwise leave in the image (about
# 3 MB under /var/cache/apk).
    apk del --no-network .build-deps && \
    rm -rf /var/cache/apk/* && \
# Fail the build if the extension does not actually load. Swoole is compiled here for every target platform, and
# a library that is missing at runtime (rather than at link time) produces a successful build but an image
# whose Swoole extension cannot be loaded at all. PHP only warns about that and still exits 0, so it has to
# be asserted explicitly; the published ppc64le and s390x Alpine images shipped broken this way.
    php -r 'exit(extension_loaded("swoole") ? 0 : 1);' && \
# Loading the extension doesn't prove that coroutines work: on the platforms Swoole has no context-switching assembly
# for (e.g., ppc64le and s390x), they switch through the ucontext functions, which nothing else in the build exercises,
# and the functional tests only run on the platforms native to their runners. So switch between two coroutines for real.
    php -r '$ok = false; Swoole\Coroutine\run(function () use (&$ok) { $channel = new Swoole\Coroutine\Channel(1); Swoole\Coroutine::create(function () use ($channel) { Swoole\Coroutine::sleep(0.01); $channel->push(["swoole" => 1]); }); $ok = $channel->pop(10) === ["swoole" => 1]; }); exit($ok ? 0 : 1);'

WORKDIR "/var/www/"
