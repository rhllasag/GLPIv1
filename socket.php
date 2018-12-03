<?php
require __DIR__ . '/vendor/autoload.php';

use ElephantIO\Client;
use ElephantIO\Engine\SocketIO\Version1X;

$client = new Client(new Version1X('http://10.1.1.1:8080'));

$client->initialize();
// send message to connected clients
$client->emit('newLanding', ['type' => 'notification', 'text' => 'Hello There!']);
$client->close();
?>