#!/usr/bin/env php
<?php

declare(strict_types=1);

use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;

function p(): void
{
    static $i = 0;
    $i++;
    echo $i, "\n"; // You may mark this line as a breakpoint in your IDE to debug with Xdebug.
}

$http = new Server("0.0.0.0", 9501);
$http->on("request", function (Request $request, Response $response) {
    switch ($request->server["request_uri"]) {
        case "/breakpoint":
            xdebug_enable(); // Enables stack traces.

            p();
            xdebug_break();
            p();

            $response->end("For debugging purpose, please open the server.php script and add a breakpoint in it.");
            break;
        case "/phpinfo":
            $response->header("Content-Type", "text/plain");
            ob_start();
            phpinfo();
            $response->end(ob_get_clean());
            break;
        default:
            $response->end("OK");
            break;
    }
});
$http->start();
