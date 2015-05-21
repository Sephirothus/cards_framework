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
        try {
            $gameId = IdHelper::toId($topic->getId());
            if (!(new \common\libs\Rules)->check(isset($event['card_id']) ? $event['card_id'] : 0, $event['user_id'], $gameId, $event['action'])) return false;

            $game = GamesModel::findOne(['_id' => $gameId]);
            if ($game['status'] == GamesModel::$status['in_progress']) {
                $data = $event;
                if (isset($data['card_id']) && intval($data['card_id']) > 0) {
                    $event['pic_id'] = CardsModel::findOne(['_id' => $event['card_id']])['id'];
                }
                $data['games_id'] = $gameId;

                $isSave = false;
                $gameData = GameDataModel::findOne(['games_id' => $gameId]);
                $temp = $gameData->getAttributes();
                switch ($event['action']) {
                    case 'from_hand_to_play':
                        $type = GameDataModel::findCardType($temp['hand_cards'][$event['user_id']], $event['card_id']);
                        unset($temp['hand_cards'][$event['user_id']][$type['type']][$type['index']]);
                        $temp['play_cards'][$event['user_id']][$type['type']][] = $event['card_id'];
                        $isSave = true;
                        break;
                    case 'from_play_to_field':
                        $type = GameDataModel::findCardType($temp['play_cards'][$event['user_id']], $event['card_id']);
                        unset($temp['play_cards'][$event['user_id']][$type['type']][$type['index']]);
                        $temp['field_cards'][$type['type']][] = $event['card_id'];
                        $isSave = true;
                        break;
                    case 'from_hand_to_field':
                        $type = GameDataModel::findCardType($temp['hand_cards'][$event['user_id']], $event['card_id']);
                        unset($temp['hand_cards'][$event['user_id']][$type['type']][$type['index']]);
                        $temp['field_cards'][$type['type']][] = $event['card_id'];
                        $isSave = true;
                        break;
                    case 'get_doors_card':
                    case 'get_treasures_card':
                        $data['card_id'] = $event['card_id'] = (new CardsModel)->dealOneByType($gameId, $data['card_type'], $data['user_id']);
                        $card = CardsModel::findOne(['_id' => IdHelper::toId($data['card_id'])]);
                        $event['pic_id'] = $card['id'];
                        if (isset($card['price'])) $event['price'] = $card['price'];
                        break;
                    case 'discard_from_hand':
                    case 'discard_from_play':
                    case 'discard_from_field':
                        switch ($event['action']) {
                            case 'discard_from_hand':
                                $place = 'hand_cards';
                                break;
                            case 'discard_from_play':
                                $place = 'play_cards';
                                break;
                            case 'discard_from_field':
                                $place = 'field_cards';
                                break;
                        }
                        if ($event['action'] == 'discard_from_field') {
                            $type = GameDataModel::findCardType($temp[$place], $event['card_id']);
                            unset($temp[$place][$type['type']][$type['index']]);
                        } else {
                            $type = GameDataModel::findCardType($temp[$place][$event['user_id']], $event['card_id']);
                            unset($temp[$place][$event['user_id']][$type['type']][$type['index']]);
                        }
                        $temp['discards'][$type['type']][] = $event['card_id'];
                        $isSave = true;
                        break;
                    case 'sell_cards':
                        /*foreach ($event['card_id'] as $card) {
                            $type = GameDataModel::findCardType($temp[''][$event['user_id']], $card);
                            unset($temp[$place][$event['user_id']][$type['type']][$type['index']]);
                            $temp['discards'][$type['type']][] = $card;
                        }
                        $isSave = true;*/
                        break;
                    case 'turn_card':
                        if (in_array($event['card_id'], $temp['turn_cards'])) unset($temp['turn_cards'][array_search($event['card_id'], $temp['turn_cards'])]);
                        else $temp['turn_cards'][] = $event['card_id'];
                        $isSave = true;
                        break;
                }
                if ($isSave) {
                    foreach ($gameData->getAttributes() as $attr => $val) {
                        $gameData->$attr = $temp[$attr];
                    }
                    $gameData->save();
                }
                GameLogsModel::add($data);
            }
            $topic->broadcast($event);
        } catch (\Exception $e) {
            echo $e->getMessage().' '.$e->getFile().' '.$e->getLine();
        }
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
                $event['count'] = 1;
            } else {
                $event['type'] = 'not_all_users';
                $event['count'] = intval($game['count_users'])-count($game['users']);
            }
        }
        $event['users'] = (new \common\models\User)->getUsers($game['users']);
        $topic->broadcast($event);
    }
}
