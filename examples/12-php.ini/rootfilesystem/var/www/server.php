#!/usr/bin/env php
<?php

declare(strict_types=1);

$http = new Swoole\Http\Server('0.0.0.0', 9501);
$http->on(
    'request',
    function (Swoole\Http\Request $request, Swoole\Http\Response $response) {
        $response->end(
            <<<'EOT'
                <pre>
                In this example we have PHP option "date.timezone" customized with value "America/Los_Angeles",
                as you can see from file "/usr/local/etc/php/php.ini" in the image built.
                </pre>
            EOT
        );
    }
);
$http->start();
