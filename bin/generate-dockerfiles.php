#!/usr/bin/env php
<?php
/**
 * How to use the script:
 *    ./bin/generate-dockerfiles.php swoole-version
 *    e.g.,
 *    ./bin/generate-dockerfiles.php 4.3.5
 * Above command will have dockerfiles created for Swoole 4.3.5 under folder images/dockerfiles/.
 */

use Swoole\Docker\Dockerfile;

require_once dirname(__DIR__) . '/vendor/autoload.php';

(new Dockerfile($argv[1]))->render();