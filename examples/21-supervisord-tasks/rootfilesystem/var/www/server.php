#!/usr/bin/env php
<?php

declare(strict_types=1);

$http = new Swoole\Http\Server('0.0.0.0', intval($_ENV['HTTP_PORT'] ?? 9501));
$http->on(
    'request',
    function (Swoole\Http\Request $request, Swoole\Http\Response $response) use ($http) {
        $response->end("Response received from the Swoole server at port {$http->port}.\n");
    }
);
$http->start();
