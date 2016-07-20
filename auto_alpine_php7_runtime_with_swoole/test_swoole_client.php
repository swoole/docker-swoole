<?php
global $i,$jjj,$lll;
$i=0;
$jjj=0;
$lll=0;
$t0=microtime(true);
file_put_contents("log.txt",microtime(true).":".$i."\n");
function dump(){
	global $i,$jjj,$lll;
	echo("i=$i,j=$jjj,l=$lll\n");
}
do{
	$client = new swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);
	$client->on("connect", function(swoole_client $cli) {
		$cli->send("GET / HTTP/1.1\r\n\r\n");
	});
	$client->on("receive", function(swoole_client $cli, $data){
		$cli->close();
		global $i,$jjj,$lll;
		//echo "Receive: $data";
		$lll+=strlen($data);
		$jjj++;
		file_put_contents("log.txt","$i,$jjj,$lll\n",FILE_APPEND);
		//$cli->send(str_repeat('A', 100)."\n");
		//sleep(1);
	});
	$client->on("error", function(swoole_client $cli){
		global $i,$jjj,$lll;
		file_put_contents("log.txt","ERROR: $i,$jjj,$lll\n",FILE_APPEND);
		#echo "error\n";
	});
	$client->on("close", function(swoole_client $cli){
		//echo "Connection close\n";
	});
	#$client->connect('127.0.0.1', 9501);
	$client->connect('127.0.0.1', 8080);
	$i++;
	$tx=microtime(true);
	if($tx-$t0>1){
		break;
	}
}while(true);
