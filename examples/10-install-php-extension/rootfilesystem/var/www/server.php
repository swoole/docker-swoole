#!/usr/bin/env php
<?php

$http = new Swoole\Http\Server("0.0.0.0", 9501);
$http->on(
    "request",
    function (Swoole\Http\Request $request, Swoole\Http\Response $response) {
        $response->end(
            <<<EOT
                <pre>
                In this example, we have:
                1. PHP extension "redis" installed using PECL.
                2. PHP extension "pcntl" installed.
                We also have both extensions enabled. All these are done with the Dockerfile included.
                </pre>
            EOT
        );
    }
);
$http->start();
