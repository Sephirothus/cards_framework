<?php
namespace console\controllers;

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\Wamp\WampServer;
use React\EventLoop\Factory;
use React\ZMQ\Context;
use React\Socket\Server;

use Yii;
use console\controllers\PusherController;

require dirname(dirname(__DIR__)) . '/vendor/autoload.php';

$loop = Factory::create();
$pusher = new PusherController('id', Yii::$app);

// Listen for the web server to make a ZeroMQ push after an ajax request
$context = new Context($loop);
$pull = $context->getSocket(\ZMQ::SOCKET_PULL);
$pull->bind('tcp://127.0.0.1:5555'); // Binding to 127.0.0.1 means the only client that can connect is itself
$pull->on('message', array($pusher, 'onPublish'));

// Set up our WebSocket server for clients wanting real-time updates
$webSock = new Server($loop);
$webSock->listen(8080, '0.0.0.0'); // Binding to 0.0.0.0 means remotes can connect
$webServer = new IoServer(
    new HttpServer(
        new WsServer(
            new WampServer(
                $pusher
            )
        )
    ),
    $webSock
);

$loop->run();