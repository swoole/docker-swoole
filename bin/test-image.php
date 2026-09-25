#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Functional tests executed inside a Docker image built from this repository, verifying that the image is fully
 * functioning: PHP extensions are loaded, coroutines are scheduled properly, TCP/UDP sockets work, and the curl/SSH
 * features of Swoole work.
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

const HTTP_HOST      = '127.0.0.1';
const HTTP_PORT      = 18080;
const TCP_PORT       = 18081;
const TCP_ECHO_PORT  = 18082;
const UDP_PORT       = 18083;

// Every timing below is multiplied by this. An emulated CPU architecture runs the same code orders of magnitude
// slower than a native one, so a timing that comfortably passes on amd64 can fail under QEMU for reasons that have
// nothing to do with the image. Callers running an emulated image set SWOOLE_TEST_TIME_FACTOR; it defaults to 1, so
// native runs are unaffected. Values below 1 are ignored, since they could only make the tests flaky.
define('TIME_FACTOR', max(1.0, (float) (getenv('SWOOLE_TEST_TIME_FACTOR') ?: '1')));

// Abort the script if the functional tests hang, e.g., when a blocking call freezes the event loop.
define('WATCHDOG_TIMEOUT_MS', (int) (60000 * TIME_FACTOR));

// Timeout of the HTTP clients used below, in seconds.
define('CLIENT_TIMEOUT', (int) ceil(10 * TIME_FACTOR));

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

check('the Redis extension is loaded, with the expected compressions enabled', function (): void {
    expect(extension_loaded('redis'), 'extension "redis" is not loaded');
    expect(class_exists(Redis::class), 'class "Redis" does not exist');

    // Extension Redis is built with the lzf and zstd compressions enabled, and with the msgpack serializer disabled
    // (the igbinary serializer is enabled since Swoole 6.3.0; see the check below); see the "configureoptions" of
    // extension "redis" in config/nightly.yml.
    expect(defined('Redis::COMPRESSION_LZF'), 'extension "redis" is built without lzf compression support');
    expect(defined('Redis::COMPRESSION_ZSTD'), 'extension "redis" is built without zstd compression support');
});

// Version "6.3.0-dev" is compared against so that pre-releases of 6.3.0 (e.g. "6.3.0RC1") count as 6.3.0.
if (version_compare(swoole_version(), '6.3.0-dev', '>=')) {
    check('extension igbinary is available to extension Redis', function (): void {
        expect(extension_loaded('igbinary'), 'extension "igbinary" is not loaded');
        expect(defined('Redis::SERIALIZER_IGBINARY'), 'extension "redis" is built without igbinary serializer support');
    });
}

check('Swoole thread support matches the PHP build', function (): void {
    // Class "Swoole\Thread" exists if and only if Swoole is compiled with option "--enable-swoole-thread", which
    // requires a ZTS build of PHP; images of type "zts" are built that way, while "cli" and "alpine" images are not.
    // Note: constant PHP_ZTS is an integer under PHP 8.3 and earlier, and a boolean under PHP 8.4+.
    expect(
        class_exists(Swoole\Thread::class) === (bool) PHP_ZTS,
        PHP_ZTS ? 'class "Swoole\Thread" is missing from the ZTS image' : 'class "Swoole\Thread" exists in a non-ZTS image',
    );
});

// SSH2 support is provided by Swoole 6.2.0+ only (built with option "--with-swoole-ssh2").
$hasSsh2Support = version_compare(swoole_version(), '6.2.0', '>=');

if ($hasSsh2Support) {
    check('SSH2 functions of Swoole are available', function (): void {
        expect(function_exists('ssh2_connect'), 'function "ssh2_connect" does not exist');
    });
} else {
    check('SSH2 functions of Swoole are not available (Swoole 6.1.x and earlier)', function (): void {
        expect(!function_exists('ssh2_connect'), 'function "ssh2_connect" should not exist');
    });
}

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
        // Both the sleeps and the ceiling scale together, so that what is asserted stays the same: two sleeps that
        // overlap take about as long as one, and roughly twice as long when they do not. Scaling only the ceiling
        // would break the test rather than relax it — at a factor of 2 it would exceed the time two sequential
        // sleeps take, and the test would pass whether or not they overlapped.
        $sleep   = 0.2 * TIME_FACTOR;
        $ceiling = 1.9 * $sleep;

        $start = microtime(true);
        $wg    = new Channel(2);
        for ($i = 0; $i < 2; $i++) {
            Coroutine::create(function () use ($wg, $sleep): void {
                Coroutine::sleep($sleep);
                $wg->push(true);
            });
        }
        $wg->pop(5 * TIME_FACTOR);
        $wg->pop(5 * TIME_FACTOR);
        $duration = microtime(true) - $start;

        expect(
            $duration < $ceiling,
            sprintf('two concurrent %.3f-second sleeps took %.3f seconds in total, expected less than %.3f', $sleep, $duration, $ceiling),
        );
    });

    check('file I/O works inside coroutines', function (): void {
        // With SWOOLE_HOOK_FILE enabled, file operations inside a coroutine go through the async I/O layer of Swoole
        // (its thread pool).
        $file = tempnam(sys_get_temp_dir(), 'swoole-test-');
        $data = str_repeat('swoole', 1024);
        try {
            expect(file_put_contents($file, $data) === strlen($data), 'failed to write to a temporary file');
            expect(file_get_contents($file) === $data, 'unexpected data read back from the temporary file');
        } finally {
            unlink($file);
        }
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
        curl_setopt($ch, CURLOPT_TIMEOUT, CLIENT_TIMEOUT);
        $body = curl_exec($ch);
        expect($body !== false, 'curl request failed: ' . curl_error($ch));
        expect($body === 'pong', 'unexpected response body "' . var_export($body, true) . '" from the curl request');
    });

    check('an HTTP request via Swoole\Coroutine\Http\Client gets processed', function (): void {
        $client = new Client(HTTP_HOST, HTTP_PORT);
        $client->set(['timeout' => CLIENT_TIMEOUT]);
        expect($client->get('/ping'), 'the HTTP request failed: ' . $client->errMsg);
        expect($client->getBody() === 'pong', 'unexpected response body "' . var_export($client->getBody(), true) . '"');
        $client->close();
    });

    // The two raw socket tests below exercise the socket layer of Swoole directly (accept/connect/send/recv and
    // sendto/recvfrom), which is backed by the reactor (epoll) implementation.
    check('raw TCP sockets echo data (accept/connect/send/recv)', function (): void {
        $server = new Coroutine\Socket(AF_INET, SOCK_STREAM, 0);
        expect($server->bind(HTTP_HOST, TCP_ECHO_PORT), 'failed to bind the TCP server socket: ' . $server->errMsg);
        expect($server->listen(8), 'failed to listen on the TCP server socket: ' . $server->errMsg);
        Coroutine::create(function () use ($server): void {
            $conn = $server->accept(10);
            if ($conn !== false) {
                $data = $conn->recv(65536, 10);
                if (!empty($data)) {
                    $conn->send($data);
                }
                $conn->close();
            }
        });

        $client = new Coroutine\Socket(AF_INET, SOCK_STREAM, 0);
        expect($client->connect(HTTP_HOST, TCP_ECHO_PORT, 10), 'failed to connect to the TCP server: ' . $client->errMsg);
        $payload = str_repeat('swoole', 512);
        expect($client->send($payload, 10) === strlen($payload), 'failed to send data over TCP: ' . $client->errMsg);
        $received = '';
        while (strlen($received) < strlen($payload)) {
            $chunk = $client->recv(65536, 10);
            if (empty($chunk)) {
                break;
            }
            $received .= $chunk;
        }
        expect($received === $payload, 'the data received over TCP does not match the data sent');
        $client->close();
        $server->close();
    });

    check('UDP sockets pass messages around (sendto/recvfrom)', function (): void {
        $server = new Coroutine\Socket(AF_INET, SOCK_DGRAM, 0);
        expect($server->bind(HTTP_HOST, UDP_PORT), 'failed to bind the UDP server socket: ' . $server->errMsg);
        Coroutine::create(function () use ($server): void {
            $peer = null;
            $data = $server->recvfrom($peer, 10);
            if (!empty($data) && !empty($peer)) {
                $server->sendto($peer['address'], $peer['port'], strrev($data));
            }
        });

        $client = new Coroutine\Socket(AF_INET, SOCK_DGRAM, 0);
        expect($client->sendto(HTTP_HOST, UDP_PORT, 'swoole') !== false, 'failed to send a UDP message: ' . $client->errMsg);
        $peer = null;
        expect($client->recvfrom($peer, 10) === 'eloows', 'unexpected response received over UDP');
        $client->close();
        $server->close();
    });

    if (version_compare(swoole_version(), '6.2.0', '>=')) {
        // There is no SSH server running inside the image; instead, we start a TCP server speaking a different
        // protocol for the SSH client to talk to. A functioning libssh2 performs the banner exchange (through the
        // coroutine-aware socket layer) and then fails gracefully; a broken build would crash the process or hang
        // the event loop instead.
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
    }

    check('the ODBC driver of PDO works in coroutines', function (): void {
        // Swoole provides the PDO_ODBC driver when compiled with option "--with-swoole-odbc". There is no ODBC data
        // source available inside the image; connecting to a non-existing DSN exercises the unixODBC driver manager
        // (through coroutine-aware code paths of Swoole), which should fail gracefully with an exception.
        expect(in_array('odbc', PDO::getAvailableDrivers(), true), 'the ODBC driver of PDO is not available');
        try {
            new PDO('odbc:DSN=nonexistent_dsn_for_testing');
            throw new Exception('connecting to a non-existing DSN should fail');
        } catch (PDOException) {
            // Expected: the DSN cannot be found. A broken build would crash the process instead.
        }
    });

    $server->shutdown();
    Timer::clear($watchdog);
});

if ($failures > 0) {
    echo $failures, ' test(s) failed.', PHP_EOL;
    exit(1);
}

echo 'All functional tests passed.', PHP_EOL;
