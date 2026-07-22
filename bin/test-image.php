#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Functional tests executed inside a Docker image built from this repository, verifying that the image is fully
 * functioning: PHP extensions are loaded, coroutines are scheduled properly, and the curl/SSH features of Swoole work.
 *
 * This script is self-contained on purpose (no Composer dependencies), so that it can be mounted into and executed
 * inside any image built from this repository. It is driven by script ./bin/test-image.sh, but can also be executed
 * manually, e.g.,
 *     docker run --rm -v $(pwd)/bin/test-image.php:/test-image.php:ro phpswoole/swoole:php8.4 php /test-image.php
 *
 * The script exits with a non-zero status code if any of the tests fail.
 */

use Swoole\Coroutine;
use Swoole\Coroutine\Channel;
use Swoole\Coroutine\Http\Client;
use Swoole\Coroutine\Http\Server;
use Swoole\Process;
use Swoole\Runtime;
use Swoole\Timer;

const HTTP_HOST = '127.0.0.1';
const HTTP_PORT = 18080;
const TCP_PORT  = 18081;

// Abort the script if the functional tests hang, e.g., when a blocking call freezes the event loop.
const WATCHDOG_TIMEOUT_MS = 60000;

$failures = 0;

function check(string $name, callable $fn): void
{
    global $failures;

    try {
        $fn();
        echo "    [OK] {$name}", PHP_EOL;
    } catch (Throwable $t) {
        $failures++;
        echo "    [FAIL] {$name}: {$t->getMessage()}", PHP_EOL;
    }
}

function expect(bool $condition, string $message): void
{
    if (!$condition) {
        throw new Exception($message);
    }
}

echo 'Running functional tests on PHP ', PHP_VERSION, ' (ZTS: ', PHP_ZTS ? 'yes' : 'no', ')', PHP_EOL;

check('the Swoole extension is loaded', function (): void {
    expect(extension_loaded('swoole'), 'extension "swoole" is not loaded');
    expect((bool) preg_match('/^\d+\.\d+\.\d+/', swoole_version()), 'unexpected Swoole version "' . swoole_version() . '"');
});

check('the Redis extension is loaded', function (): void {
    expect(extension_loaded('redis'), 'extension "redis" is not loaded');
    expect(class_exists(Redis::class), 'class "Redis" does not exist');
});

check('Swoole thread support matches the PHP build', function (): void {
    // Class "Swoole\Thread" exists if and only if Swoole is compiled with option "--enable-swoole-thread", which
    // requires a ZTS build of PHP; images of type "zts" are built that way, while "cli" and "alpine" images are not.
    expect(
        class_exists(Swoole\Thread::class) === PHP_ZTS,
        PHP_ZTS ? 'class "Swoole\Thread" is missing from the ZTS image' : 'class "Swoole\Thread" exists in a non-ZTS image',
    );
});

check('SSH2 functions of Swoole are available', function (): void {
    expect(function_exists('ssh2_connect'), 'function "ssh2_connect" does not exist');
});

// Enable runtime hooks before starting the event loop, so that curl and other blocking APIs used below are
// coroutine-aware.
Runtime::enableCoroutine(SWOOLE_HOOK_ALL);

Coroutine\run(function (): void {
    $watchdog = Timer::after(WATCHDOG_TIMEOUT_MS, function (): void {
        echo '    [FAIL] the functional tests timed out; the event loop is probably blocked', PHP_EOL;
        Process::kill(getmypid(), SIGKILL);
    });

    check('coroutines communicate through channels', function (): void {
        $channel = new Channel(1);
        Coroutine::create(function () use ($channel): void {
            $channel->push('hello');
        });
        expect($channel->pop(5) === 'hello', 'failed to pass a message between coroutines through a channel');
    });

    check('coroutines run concurrently', function (): void {
        $start = microtime(true);
        $wg    = new Channel(2);
        for ($i = 0; $i < 2; $i++) {
            Coroutine::create(function () use ($wg): void {
                Coroutine::sleep(0.2);
                $wg->push(true);
            });
        }
        $wg->pop(5);
        $wg->pop(5);
        $duration = microtime(true) - $start;
        // Two 0.2-second sleeps should take about 0.2 seconds in total when run concurrently, and 0.4 seconds or
        // more when not.
        expect($duration < 0.38, sprintf('two concurrent 0.2-second sleeps took %.3f seconds in total', $duration));
    });

    // Start an HTTP server (in a coroutine) to serve the curl and SSH tests below. Since the server runs in the same
    // process as the clients, any client call that is not coroutine-aware would block the event loop, preventing the
    // server from responding and thus failing the tests.
    $server = new Server(HTTP_HOST, HTTP_PORT);
    $server->handle('/ping', function ($request, $response): void {
        $response->end('pong');
    });
    Coroutine::create(function () use ($server): void {
        $server->start();
    });

    check('an HTTP request via the curl hook gets processed', function (): void {
        $ch = curl_init(sprintf('http://%s:%d/ping', HTTP_HOST, HTTP_PORT));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $body = curl_exec($ch);
        expect($body !== false, 'curl request failed: ' . curl_error($ch));
        curl_close($ch);
        expect($body === 'pong', 'unexpected response body "' . var_export($body, true) . '" from the curl request');
    });

    check('an HTTP request via Swoole\Coroutine\Http\Client gets processed', function (): void {
        $client = new Client(HTTP_HOST, HTTP_PORT);
        $client->set(['timeout' => 10]);
        expect($client->get('/ping'), 'the HTTP request failed: ' . $client->errMsg);
        expect($client->getBody() === 'pong', 'unexpected response body "' . var_export($client->getBody(), true) . '"');
        $client->close();
    });

    // There is no SSH server running inside the image; instead, we start a TCP server speaking a different protocol
    // for the SSH client to talk to. A functioning libssh2 performs the banner exchange (through the coroutine-aware
    // socket layer) and then fails gracefully; a broken build would crash the process or hang the event loop instead.
    $tcpServer = new Coroutine\Server(HTTP_HOST, TCP_PORT);
    $tcpServer->handle(function (Coroutine\Server\Connection $conn): void {
        $conn->send("NOT-AN-SSH-SERVER\r\n");
        $conn->close();
    });
    Coroutine::create(function () use ($tcpServer): void {
        $tcpServer->start();
    });

    check('SSH2 functions of Swoole work in coroutines', function (): void {
        $session = @ssh2_connect(HTTP_HOST, TCP_PORT);
        expect($session === false, 'an SSH handshake against a non-SSH server should fail gracefully');
    });

    $tcpServer->shutdown();
    $server->shutdown();
    Timer::clear($watchdog);
});

if ($failures > 0) {
    echo $failures, ' test(s) failed.', PHP_EOL;
    exit(1);
}

echo 'All functional tests passed.', PHP_EOL;
