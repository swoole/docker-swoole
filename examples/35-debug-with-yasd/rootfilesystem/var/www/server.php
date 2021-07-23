<?php

declare(strict_types=1);

use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;

$http = new Server("0.0.0.0", 80);

$http->on(
    "request",
    function (Request $request, Response $response) {
        // Some dummy code here for adding breakpoints.
        $i = 0;
        $i++;
        $i++;
        for ($j = 0; $j < 5; $j++) {
            $i++;
        }

        ob_start();
        phpinfo();
        $response->header("Content-Type", "text/plain");
        $response->end(ob_get_clean() . "\n");
    }
);

$http->start();
