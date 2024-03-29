#!/usr/bin/env php
<?php

declare(strict_types=1);

use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;

$http = new Server('0.0.0.0', 9501);

$http->on('request', function (Request $request, Response $response): void {
    $response->end('OK' . PHP_EOL);
});
$http->on('workerStop', function (Server $http, int $workerId): void {
    // When the Docker container is stopped (e.g., by the `docker compose stop` command), event "onWorkerStop" is
    // triggered in each worker process, allowing graceful shutdown of Swoole workers.
    echo "Event \"onWorkerStop\" is triggered in worker #{$workerId}.", PHP_EOL;
});
$http->on('shutdown', function (Server $http): void {
    // When the Docker container is stopped (e.g., by the `docker compose stop` command), event "onShutdown" is
    // triggered, allowing graceful shutdown of Swoole server.
    echo 'Event "onShutdown" is triggered.', PHP_EOL;
});

$http->start();
