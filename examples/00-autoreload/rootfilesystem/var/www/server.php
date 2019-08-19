#!/usr/bin/env php
<?php

$http = new Swoole\Http\Server("0.0.0.0", 9501);
$http->on(
    "request",
    function (Swoole\Http\Request $request, Swoole\Http\Response $response) {
        $response->end(
            <<<EOT
                <pre>
                Once the Swoole web server is started and you can see this message from http://127.0.0.1,
                please update this message manually in the PHP script then refresh the URL. The response
                from the URL should have been changed.
                </pre>
            EOT
        );
    }
);
$http->start();
