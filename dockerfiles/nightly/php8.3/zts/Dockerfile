FROM php:8.3-zts

ENV DEBIAN_FRONTEND=noninteractive
ENV TERM=xterm-color

ARG DEV_MODE
ENV DEV_MODE=$DEV_MODE

COPY --from=composer:2.10.3 /usr/bin/composer /usr/bin/
COPY ./rootfilesystem/ /

RUN \
    set -ex && \
# Packages marked as manually installed in the base image, to keep when the build-only packages are removed below.
    savedAptMark="$(apt-mark showmanual)" && \
    apt-get update && \
    apt-get install -y \
# The auto-reloading feature is supported for non-Alpine images only.
#
# Package "inotify-tools" is pre-installed in nightly images and in 6.3.0-rc1+ images. For older versioned images, it
# will be installed automatically when needed. For details, please check script "autoreload.sh" and example
# "00-autoreload".
#
# @see https://github.com/swoole/docker-swoole/blob/master/rootfilesystem/usr/local/boot/autoreload.sh
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
        unixodbc-dev               \
        unzip                      \
        zlib1g-dev                 \
        --no-install-recommends && \
# PHP extension pdo_mysql is included since 4.8.12+ and 5.0.1+.
    docker-php-ext-install pdo_mysql && \
    pecl channel-update pecl.php.net && \
    MAKEFLAGS="-j$(nproc)" pecl install igbinary-3.2.16 && \
    MAKEFLAGS="-j$(nproc)" pecl install --configureoptions 'enable-redis-igbinary="yes" enable-redis-lzf="yes" enable-redis-zstd="yes" enable-redis-msgpack="no" enable-redis-lz4="no" with-liblz4="yes"' redis-6.3.0 && \
# PHP extension Redis is included since 4.8.12+ and 5.0.1+.
    docker-php-ext-enable igbinary && \
    docker-php-ext-enable redis && \
    install-swoole.sh master \
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
        apt-mark manual $savedAptMark inotify-tools supervisor unzip > /dev/null && \
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

ENTRYPOINT ["/entrypoint.sh"]
CMD []

WORKDIR "/var/www/"
