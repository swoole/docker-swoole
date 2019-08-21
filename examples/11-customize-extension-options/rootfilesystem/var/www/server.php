#!/usr/bin/env php
<?php

$http = new Swoole\Http\Server("0.0.0.0", 9501);
$http->on(
    "request",
    function (Swoole\Http\Request $request, Swoole\Http\Response $response) {
        $response->end(
            <<<EOT
                <pre>
                In this example we have PHP option "swoole.unixsock_buffer_size" customized with value "1024",
                as you can see from file "/usr/local/etc/php/conf.d/docker-php-ext-swoole.ini" in the image built. 
                </pre>
            EOT
        );
    }
);
$http->start();
