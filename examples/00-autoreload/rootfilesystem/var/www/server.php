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
                </pre>
            EOT
        );
    }
);
$http->start();
