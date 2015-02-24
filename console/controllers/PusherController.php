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
use common\helpers\IdHelper;

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
        $gameId = IdHelper::toId($topic->getId());
        $game = GamesModel::findOne(['_id' => $gameId]);
        if ($game['status'] == GamesModel::$status['in_progress']) {
            $data = $event;
            if (isset($data['card_id'])) {
                $card = CardsModel::findOne(['id' => $event['card_id']]);
                $event['pic_id'] = $event['card_id'];
                $data['card_id'] = $event['card_id'] = (string)$card['_id'];
            }
            $data['games_id'] = $gameId;
            GameLogsModel::add($data);
            
            $gameData = GameDataModel::findOne(['games_id' => $gameId]);
            switch ($event['action']) {
                case 'from_hand_to_play':
                    /*$type = GameDataModel::findValKey($gameData->hand_cards[$event['user_id']], $event['card_id']);
                    $gameData->hand_cards[$event['user_id']][$type] = 
                    $gameData->play_cards[$event['user_id']][$type][] = $event['card_id'];*/
                    break;
                case 'from_play_to_field':
                    //$gameData->
                    break;
                case 'from_hand_to_field':
                    //$gameData->
                    break;
            }
        }
        $topic->broadcast($event);
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {

    }

    public function onSubscribe(ConnectionInterface $conn, $topic) {
        $event = [];
        $gameId = IdHelper::toId($topic->getId());
        $this->subscribedTopics[$topic->getId()] = $topic;
        $game = GamesModel::findOne(['_id' => $gameId]);
        $gameData = GameDataModel::findOne(['games_id' => $gameId]);
        if ($game['status'] == GamesModel::$status['new']) {
            if ($game['count_users'] == count($game['users'])) {
                $game->status = GamesModel::$status['in_progress'];
                $game->save();
                $event['cards'] = $gameData['hand_cards'];
                $event['decks'] = CardsModel::$deckTypes;
                $event['type'] = 'start_game';
                $event['first_move'] = (string)$game['users'][0];
            } else {
                $event['type'] = 'not_all_users';
                $event['count'] = intval($game['count_users'])-count($game['users']);
            }
        }
        $topic->broadcast($event);
    }
}
