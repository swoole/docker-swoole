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
                In this example we have following content at line 958 in file "/usr/local/etc/php/php.ini":
                    date.timezone = %%SWOOLE_TIMEZONE%%
                When the Docker container starts, this line will be updated with environment variable "SWOOLE_TIMEZONE",
                making it look like:
                    date.timezone = America/Los_Angeles
                So, PHP option "date.timezone" will be set to "America/Los_Angeles" when the Docker container is started.
                </pre>
            EOT
        );
    }
);
$http->start();
