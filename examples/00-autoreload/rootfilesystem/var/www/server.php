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
                You can update this message manually in the PHP script and refresh URL
                http://127.0.0.1 to see the changes.

                There are two environment variables used to setup autoreloading in the Swoole image:
                1. AUTORELOAD_PROGRAMS: space-separated Supervisor program(s) to be reloaded when file changes detected.
                   e.g.,
                       AUTORELOAD_PROGRAMS: "swoole"       # Autoreload Supervisor program "swoole" only.
                       AUTORELOAD_PROGRAMS: "swoole nginx" # Autoreload Supervisor program "swoole" and "nginx".
                2. AUTORELOAD_ANY_FILES: Optional. If set to "true", "1", "yes", or "y", reload Supervisor program(s)
                   when any files under the root directory (/var/www in this example) is changed; otherwise, reload only
                   when PHP file(s) are changed.
                </i>
                </pre>
            EOT
        );
    }
);
$http->start();
