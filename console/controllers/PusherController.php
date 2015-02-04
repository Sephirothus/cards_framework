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

use \yii\console\Controller;
use common\models\GameLogsModel;
use common\models\GamesModel;
use common\models\CardsModel;
use common\models\GameDataModel;

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
        $game = GamesModel::findOne(['_id' => $topic]);
        if ($game['status'] == GamesModel::$status['in_progress']) {
            $attributes = $event['data'];
            $attributes['game_id'] = $topic;
            GameLogsModel::add($attributes);
        }
        $topic->broadcast($event);
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {

    }

    public function onSubscribe(ConnectionInterface $conn, $topic) {
        $event = [];
        $this->subscribedTopics[$topic->getId()] = $topic;
        $game = GamesModel::findOne(['_id' => $topic]);
        $gameData = GameDataModel::findOne(['games_id' => new \MongoId((string)$topic)]);
        if ($game['status'] == GamesModel::$status['new']) {
            if ($game['count_users'] == count($game['users'])) {
                $game->status = GamesModel::$status['in_progress'];
                $game->save();
                $event['cards'] = $gameData['hand_cards'];
                $event['decks'] = CardsModel::$deckTypes;
                $event['type'] = 'start_game';
                $event['first_move'] = $game['users'][0];
            } else {
                $event['type'] = 'not_all_users';
                $event['count'] = intval($game['count_users'])-count($game['users']);
            }
        }
        $topic->broadcast($event);
    }
}
