#!/usr/bin/env php
<?php

$http = new Swoole\Http\Server("0.0.0.0", 9501);
$http->on(
    "request",
    function (Swoole\Http\Request $request, Swoole\Http\Response $response) {
        $response->end(
            <<<EOT
                <pre>
                You can update this message manually in the PHP script and refresh URL
                http://127.0.0.1 to see the changes.

                There are two environment variables used to setup autoreloading in the Swoole image:
                1. AUTORELOAD_PROGRAMS: space-separated Supervisor program(s) to be reloaded when file changes detected.
                   e.g.,
                       AUTORELOAD_PROGRAMS: "swoole"       # Autoreload Supervisor program "swoole" only.
                       AUTORELOAD_PROGRAMS: "swoole nginx" # Autoreload Supervisor program "swoole" and "nginx".
                2. AUTORELOAD_ALL_FILES: If set to "true", "1", "yes", or "y", reload Supervisor program(s) when any
                   file under the root directory (/var/www) is changed; otherwise, reload only when PHP file(s) are
                   changed.

                NOTE: The autoreloading feature works with image phpswoole/swoole:latest (or phpswoole/swoole in short)
                and phpswoole/swoole:latest-dev only. For any other image, you need to use it as a base image and
                install package inotify-tools first to make it work, like

                <i>
                FROM phpswoole/swoole:4.4.6-php7.3

                RUN \
                    apt-get update                   && \
                    apt-get install -y inotify-tools && \
                    rm -r /var/lib/apt/lists/*
                </i>
                </pre>
            EOT
        );
    }
);
$http->start();
