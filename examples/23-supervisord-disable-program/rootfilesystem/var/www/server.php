#!/usr/bin/env php
<?php

declare(strict_types=1);

$http = new Swoole\Http\Server("0.0.0.0", ($_ENV['HTTP_PORT'] ?? 9501));
$http->on(
    "request",
    function (Swoole\Http\Request $request, Swoole\Http\Response $response) use ($http) {
        $response->end(
            <<<EOT
                <pre>
                In this example we have the built-in Supervisord program "swoole" disabled. There is no program
                listening at port 9501 in the Docker image, so when you hit URL http://127.0.0.1:9501, you get
                "Connection refused".

                This response is returned from a second Swoole program launched to listen port {$http->port}.
                </pre>
            EOT
        );
    }
);
$http->start();
