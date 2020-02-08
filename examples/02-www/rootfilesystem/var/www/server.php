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
                In this example we have built-in script "/var/www/server.php" in the Docker image overwritten with this
                one.
                </pre>
            EOT
        );
    }
);
$http->start();
