#!/usr/bin/env php
<?php

declare(strict_types=1);

$http = new Swoole\Http\Server("0.0.0.0", 9501);
$http->on(
    "request",
    function (Swoole\Http\Request $request, Swoole\Http\Response $response) {
        $response->end(
            <<<EOT
                <pre>
                In this example we use an Nginx server (at port 80) in front of Swoole (at port 9501).
                </pre>
            EOT
        );
    }
);
$http->start();
