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
    apk add --no-cache libstdc++ libpq libucontext unixodbc && \
    apk add --no-cache --virtual .build-deps $PHPIZE_DEPS curl-dev linux-headers brotli-dev postgresql-dev openssl-dev pcre-dev pcre2-dev sqlite-dev zlib-dev libucontext-dev unixodbc-dev && \
# PHP extension pdo_mysql is included since 4.8.12+ and 5.0.1+.
    docker-php-ext-install pdo_mysql && \
    pecl channel-update pecl.php.net && \
    pecl install --configureoptions 'enable-redis-igbinary="no" enable-redis-lzf="yes" enable-redis-zstd="yes" enable-redis-msgpack="no" enable-redis-lz4="no" with-liblz4="yes"' redis-6.3.0 && \
# PHP extension Redis is included since 4.8.12+ and 5.0.1+.
    docker-php-ext-enable redis && \
    docker-php-ext-install sockets && \
    docker-php-source extract && \
    mkdir /usr/src/php/ext/swoole && \
    curl -sfL https://github.com/swoole/swoole-src/archive/v6.1.10.tar.gz -o swoole.tar.gz && \
    tar xfz swoole.tar.gz --strip-components=1 -C /usr/src/php/ext/swoole && \
    export LDFLAGS="${LDFLAGS} -lucontext" && \
    docker-php-ext-configure swoole --enable-openssl \
        --enable-brotli \
        --enable-mysqlnd \
        --enable-sockets \
        --enable-swoole-curl \
        --enable-swoole-pgsql \
        --enable-swoole-sqlite \
        --enable-swoole-stdext \
        --enable-zstd \
        --with-swoole-odbc=unixODBC,/usr && \
    docker-php-ext-install -j$(nproc) swoole && \
    rm -f swoole.tar.gz && \
    docker-php-source delete && \
    apk del .build-deps

WORKDIR "/var/www/"
