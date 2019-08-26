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
                
                NOTE: The autoloading feature works with image <strong><i>phpswoole/swoole:latest</i></strong>
                (or <strong><i>phpswoole/swoole</i></strong> in short) only. For any other image, you need to
                use it as a base image and install package <i>inotify-tools</i> first to make
                it work, like
                
                <i>
                FROM phpswoole/swoole:4.4.4-php7.3

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
