<?php
namespace console\controllers;

use Ratchet\ConnectionInterface;
use Ratchet\Wamp\WampServerInterface;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\Wamp\WampServer;
use React\EventLoop\Factory;
use React\ZMQ\Context;
use React\Socket\Server;

use Yii;
use \yii\console\Controller;
use common\models\GameLogsModel;

require dirname(dirname(__DIR__)) . '/vendor/autoload.php';

class PusherController extends Controller implements WampServerInterface {

	/**
     * A lookup of all the topics clients have subscribed to
     */
    protected $subscribedTopics = array();

    public function onUnSubscribe(ConnectionInterface $conn, $topic) {

    }

    public function onOpen(ConnectionInterface $conn) {

    }

    public function onClose(ConnectionInterface $conn) {

    }

    public function onCall(ConnectionInterface $conn, $id, $topic, array $params) {
        // In this application if clients send data it's because the user hacked around in console
        //$conn->callError($id, $topic, 'You are not allowed to make calls')->close();

    }

    public function onPublish(ConnectionInterface $conn, $topic, $event, array $exclude, array $eligible) {
        // In this application if clients send data it's because the user hacked around in console
        //$conn->close();
        /*if (!array_key_exists($entryData['category'], $this->subscribedTopics)) {
            return;
        }*/
        /*foreach ($event as $key => $ev) {
        	switch ($key) {
        		case 'game_id':
        		case 'user_id':
        		case 'card_id':
        		case 'card_coords':

        			break;
        		default:
        			unset($event['$key']);
        			break;
        	}
        }*/
        $attributes = $event['data'];
        $attributes['game_id'] = 1;
        var_dump(GameLogsModel::add($attributes));
        $topic->broadcast($event);
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {

    }

    public function onSubscribe(ConnectionInterface $conn, $topic) {
        $this->subscribedTopics[$topic->getId()] = $topic;
    }

    /**
     * @param string JSON'ified string we'll receive from ZeroMQ
     */
    /*public function onBlogEntry($entry) {
        $entryData = json_decode($entry, true);

        // If the lookup topic object isn't set there is no one to publish to
        if (!array_key_exists($entryData['category'], $this->subscribedTopics)) {
            return;
        }

        $topic = $this->subscribedTopics[$entryData['category']];

        // re-send the data to all the clients subscribed to that category
        $topic->broadcast($entryData);
    }*/
}
