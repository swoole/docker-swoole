#!/usr/bin/env php
<?php

use Co\Socket;

$socket = new Socket(AF_INET, SOCK_STREAM, 0);
$socket->bind("0.0.0.0", 9501);
$socket->listen(128);
go(function () use ($socket) {
    while (true) {
        $client = $socket->accept();
        go(function() use ($client) {
            $client->send("Hello, World!\n");
            $client->close();
        });
    }
});
