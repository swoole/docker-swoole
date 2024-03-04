#!/usr/bin/env php
<?php

declare(strict_types=1);

use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;
use Upscale\Swoole\Blackfire\Profiler;

require_once __DIR__ . '/vendor/autoload.php';

$http = new Server('0.0.0.0', 9501);
$http->on(
    'request',
    function (Request $request, Response $response) {
        $response->end('Hello, World!');
    }
);
(new Profiler())->instrument($http);
$http->start();
