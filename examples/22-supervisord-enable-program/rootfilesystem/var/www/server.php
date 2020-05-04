#!/usr/bin/env php
<?php

declare(strict_types=1);

$http = new Swoole\Http\Server("0.0.0.0", intval($_ENV['HTTP_PORT'] ?? 9501));
$http->on(
    "request",
    function (Swoole\Http\Request $request, Swoole\Http\Response $response) use ($http) {
        $response->end(
            <<<EOT
                <pre>
                In this example we launch a second HTTP server at port 9502 with Supervisord in the container started.
                You may check following URLs to see if both web servers work as should:
                * http://127.0.0.1
                * http://127.0.0.1:9502

                You may check example "20-supervisord-services" to see how we launch a second Supervisord program in a
                different way.
                </pre>
            EOT
        );
    }
);
$http->start();
