<?php
$http = new swoole_http_server("0.0.0.0", 9501);
$http->on('request', function ($request, $response) {
#phpinfo();
#$s=system('~/opt/php7/bin/php -i 2>&1');
#exec('~/opt/php7/bin/php -r "phpinfo();"',$s);

		ob_start();
		#phpinfo();
echo 'swoole '.rand();
		$s=ob_get_clean();
$s="<pre>$s</pre>";

		$response->end($s);
		});
$http->start();
/**
// Existing socket, such as Lighttpd with mod_fastcgi:
$client = new Client('unix:///path/to/php/socket', -1);
// Fastcgi server, such as PHP-FPM:
$client = new Client('localhost', '9000');
$content = 'key=value';
echo $client->request(
array(
'GATEWAY_INTERFACE' => 'FastCGI/1.0',
'REQUEST_METHOD' => 'POST',
'SCRIPT_FILENAME' => 'test.php',
'SERVER_SOFTWARE' => 'php/fcgiclient',
'REMOTE_ADDR' => '127.0.0.1',
'REMOTE_PORT' => '9985',
'SERVER_ADDR' => '127.0.0.1',
'SERVER_PORT' => '80',
'SERVER_NAME' => 'mag-tured',
'SERVER_PROTOCOL' => 'HTTP/1.1',
'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
'CONTENT_LENGTH' => strlen($content)
),
$content
);
 */
