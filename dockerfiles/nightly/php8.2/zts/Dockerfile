# The source code of Swoole and the packages of the PECL extensions are downloaded in a stage of their own, which the
# build below mounts rather than copies from, so they don't end up in any layer of the image. ADD verifies each
# download against the checksum listed in the configuration file, if any, and records it in the build provenance.
FROM scratch AS sources
ADD https://github.com/swoole/swoole-src/archive/refs/heads/master.tar.gz /swoole-src.tar.gz
ADD --checksum=sha256:8bf25d465abc7973d9e2c9a3039a5f8eea635b23bc1477017ff3999ff95836da https://pecl.php.net/get/igbinary-3.2.16.tgz /igbinary.tgz
ADD --checksum=sha256:0d5141f634bd1db6c1ddcda053d25ecf2c4fc1c395430d534fd3f8d51dd7f0b5 https://pecl.php.net/get/redis-6.3.0.tgz /redis.tgz

FROM php:8.2-zts

ENV DEBIAN_FRONTEND=noninteractive
ENV TERM=xterm-color

ARG DEV_MODE
ENV DEV_MODE=$DEV_MODE

COPY --from=composer:2.10.3 /usr/bin/composer /usr/bin/
COPY ./rootfilesystem/ /

RUN --mount=type=bind,from=sources,target=/tmp/sources \
    set -ex && \
# Packages marked as manually installed in the base image, to keep when the build-only packages are removed below.
    savedAptMark="$(apt-mark showmanual)" && \
    apt-get update && \
    apt-get install -y \
# Package "inotify-tools" is used by the auto-reloading feature, which is supported for non-Alpine images only. For
# details, please check script "autoreload.sh" and example "00-autoreload".
#
# @see https://github.com/swoole/docker-swoole/blob/master/rootfilesystem/usr/local/bin/autoreload.sh
# @see https://github.com/swoole/docker-swoole/tree/master/examples/00-autoreload
        inotify-tools              \
        libcurl4-openssl-dev       \
        libbrotli-dev              \
        libc-ares-dev              \
        libpq-dev                  \
        libsqlite3-dev             \
        libssh2-1-dev              \
        libssl-dev                 \
        libzstd-dev                \
        supervisor                 \
        tini                       \
        unixodbc-dev               \
        unzip                      \
        zlib1g-dev                 \
        --no-install-recommends && \
# PHP extension pdo_mysql is included since 4.8.12+ and 5.0.1+.
    docker-php-ext-install pdo_mysql && \
    MAKEFLAGS="-j$(nproc)" pecl install /tmp/sources/igbinary.tgz && \
    MAKEFLAGS="-j$(nproc)" pecl install --configureoptions 'enable-redis-igbinary="yes" enable-redis-lzf="yes" enable-redis-zstd="yes" enable-redis-msgpack="no" enable-redis-lz4="no" with-liblz4="yes"' /tmp/sources/redis.tgz && \
# PHP extension Redis is included since 4.8.12+ and 5.0.1+.
    docker-php-ext-enable igbinary && \
    docker-php-ext-enable redis && \
    docker-php-ext-install sockets && \
    mkdir -p /usr/src/swoole-src && \
    tar xzf /tmp/sources/swoole-src.tar.gz --strip-components=1 -C /usr/src/swoole-src && \
# Development images build Swoole with debug and trace logging, and with debugging symbols (which the default compiler
# flags of the official PHP images leave out).
    if [ "${DEV_MODE}" = "true" ] ; then \
        devOptions="--enable-debug-log --enable-trace-log" && \
        export CFLAGS="${PHP_CFLAGS} -g" CXXFLAGS="${PHP_CFLAGS} -g" ; \
    else \
        devOptions="" ; \
    fi && \
    docker-php-ext-configure /usr/src/swoole-src ${devOptions} \
        --enable-swoole-thread \
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
# Command docker-php-ext-install strips debugging symbols, so development images build and install Swoole themselves,
# and keep its source code in folder /usr/src/swoole-src, along with debugging tools.
    if [ "${DEV_MODE}" = "true" ] ; then \
        apt-get install -y gdb git lsof strace tcpdump valgrind vim --no-install-recommends && \
        make -C /usr/src/swoole-src -j"$(nproc)" && \
        make -C /usr/src/swoole-src install && \
        make -C /usr/src/swoole-src clean && \
        docker-php-ext-enable swoole ; \
    else \
        docker-php-ext-install -j"$(nproc)" /usr/src/swoole-src && \
        rm -rf /usr/src/swoole-src ; \
    fi && \
    docker-php-source delete && \
    mkdir -p /var/log/supervisor && \
# For non-development images, strip debugging symbols from the PHP extensions and remove the build-only packages
# (headers, static libraries, and the packages they pulled in) to reduce the final image size. Same as in the official
# PHP images, every package is marked as automatically installed first, except for those installed in the base image,
# the tools used at runtime, and the packages providing the shared libraries that the PHP extensions link against;
# everything else is then removed. That also keeps a later "apt-get autoremove" (e.g., in a derived image) from
# removing a runtime library. For development images (DEV_MODE=true), symbols and headers are kept intentionally,
# alongside the retained Swoole source code, so that Swoole can be recompiled and debugged.
    if [ "${DEV_MODE}" != "true" ] ; then \
        strip --strip-all "$(php-config --extension-dir)"/*.so && \
        apt-mark auto '.*' > /dev/null && \
        apt-mark manual $savedAptMark inotify-tools supervisor tini unzip > /dev/null && \
        find /usr/local -type f -name '*.so' -exec ldd '{}' ';' \
            | awk '/=>/ { so = $(NF-1); if (index(so, "/usr/local/") == 1) { next }; gsub("^/(usr/)?", "", so); printf "*%s\n", so }' \
            | sort -u \
            | xargs -r dpkg-query --search \
            | grep -v '^diversion' \
            | cut -d: -f1 \
            | sort -u \
            | xargs -r apt-mark manual && \
        apt-get purge -y --auto-remove -o APT::AutoRemove::RecommendsImportant=false ; \
    fi && \
    rm -rf /var/lib/apt/lists/* /tmp/pear && \
# Fail the build if the extension does not actually load. Swoole is compiled here for every target platform, and
# a library that is missing at runtime (rather than at link time) produces a successful build but an image
# whose Swoole extension cannot be loaded at all. PHP only warns about that and still exits 0, so it has to
# be asserted explicitly; the published ppc64le and s390x Alpine images shipped broken this way. The PECL extensions
# are checked too, since removing the build-only packages could take a runtime library of theirs along.
    php -r 'exit(extension_loaded("swoole") && extension_loaded("igbinary") && extension_loaded("redis") ? 0 : 1);'

# Program tini runs as PID 1: it forwards signals to the entrypoint script, and reaps the zombie processes that the
# commands started in the container leave behind.
ENTRYPOINT ["/usr/bin/tini", "-s", "--", "/entrypoint.sh"]
CMD []

WORKDIR "/var/www/"
