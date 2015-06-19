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
            $actions = [$event['action']];
            $gameId = IdHelper::toId($topic->getId());
            $game = GamesModel::findOne(['_id' => $gameId]);
            if ($game['status'] == GamesModel::$status['in_progress']) {
                $rule = (new \common\libs\Rules)->check(isset($event['card_id']) ? $event['card_id'] : 0, $event['user_id'], $gameId, $event['action']);
                if ($rule) {
                    $event['rule'] = $rule;
                    if (!isset($rule['action']) || $rule['action'] != 'turn_card_off') return $topic->broadcast($event);
                    $actions[] = $rule['action'];
                }

                $data = $event;
                if (isset($data['card_id']) && intval($data['card_id']) > 0) {
                    $event['pic_id'] = CardsModel::findOne(['_id' => $event['card_id']])['id'];
                }
                $data['games_id'] = $gameId;

                $isSave = false;
                $gameData = GameDataModel::findOne(['games_id' => $gameId]);
                $temp = $gameData->getAttributes();
                if (!\common\libs\Phases::check(
                    $temp['cur_phase'], 
                    $event['user_id'], 
                    $event['action'], 
                    $event['user_id'] == $temp['cur_move'] ? true : false
                )) return false;
                
                if (!empty($event['card_id'])) $cardInfo = CardsModel::getCardInfo($event['card_id']);
                foreach ($actions as &$action) {
                    switch ($action) {
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
                            if (!empty($event['additional']) && $cardInfo['parent'] == 'disposables' && !empty($cardInfo['bonus'])) {
                                $temp['temp_data']['in_battle']['bonuses'][$event['card_id']] = $event['additional']['bonus_on'];
                            }
                            break;
                        case 'from_hand_to_field':
                            $type = GameDataModel::findCardType($temp['hand_cards'][$event['user_id']], $event['card_id']);
                            unset($temp['hand_cards'][$event['user_id']][$type['type']][$type['index']]);
                            $temp['field_cards'][$type['type']][] = $event['card_id'];
                            $isSave = true;
                            if (!empty($event['additional']) && $cardInfo['parent'] == 'disposables' && !empty($cardInfo['bonus'])) {
                                $temp['temp_data']['in_battle']['bonuses'][$event['card_id']] = $event['additional']['bonus_on'];
                            }
                            break;
                        case 'from_field_to_hand':
                            $type = GameDataModel::findCardType($temp['field_cards'], $event['card_id']);
                            unset($temp['field_cards'][$type['type']][$type['index']]);
                            $temp['hand_cards'][$event['user_id']][$type['type']][] = $event['card_id'];
                            $event['card_type'] = $type['type'];
                            $isSave = true;
                            break;
                        case 'get_doors_card':
                        case 'get_treasures_card':
                            switch ($action) {
                                case 'get_doors_card':
                                    if ($temp['cur_phase'] == 'place_cards') {
                                        $userId = false;
                                        $toType = 'field_cards';
                                        if ($event['action'] == $action) $event['action'] = 'open_door';
                                        $action = 'open_door';
                                    } else {
                                        $userId = $data['user_id'];
                                        $toType = 'hand_cards';
                                    }
                                    $data['card_id'] = $event['card_id'] = (new CardsModel)->dealOneByType($gameId, $data['card_type'], $userId, $toType);
                                    break;
                                case 'get_treasures_card':
                                    $data['card_id'] = $event['card_id'] = (new CardsModel)->dealOneByType($gameId, $data['card_type'], $data['user_id'], 'hand_cards');
                                    break;
                            }
                            $card = CardsModel::getOne($data['card_id']);
                            $event['pic_id'] = $card['id'];
                            $event['card_info'] = $card;
                            break;
                        case 'discard_from_hand':
                        case 'discard_from_play':
                        case 'discard_from_field':
                            switch ($action) {
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
                            if ($action == 'discard_from_field') {
                                $type = GameDataModel::findCardType($temp[$place], $event['card_id']);
                                unset($temp[$place][$type['type']][$type['index']]);
                            } else {
                                $type = GameDataModel::findCardType($temp[$place][$event['user_id']], $event['card_id']);
                                unset($temp[$place][$event['user_id']][$type['type']][$type['index']]);

                                if ($cardInfo['parent'] == 'get_level') {
                                    $lvl = $game['users'][$event['user_id']]['lvl'];
                                    GamesModel::changeUserInfo($event['user_id'], $gameId, ['lvl' => ++$lvl]);
                                    $event['lvl_up'] = $event['user_id'];
                                }
                            }
                            if (is_array($temp['turn_cards']) && in_array($event['card_id'], $temp['turn_cards'])) unset($temp['turn_cards'][array_search($event['card_id'], $temp['turn_cards'])]);
                            $temp['discards'][$type['type']][] = $event['card_id'];
                            $isSave = true;
                            break;
                        case 'sell_cards':
                            /*foreach ($event['card_id'] as $card) {
                                $type = GameDataModel::findCardType($temp[''][$event['user_id']], $card);
                                unset($temp[$place][$event['user_id']][$type['type']][$type['index']]);
                                $temp['discards'][$type['type']][] = $card;
                            }
                            $event['user_lvl'] = 2;
                            $isSave = true;*/
                            break;
                        case 'turn_card_off':
                        case 'turn_card_on':
                            if (isset($temp['turn_cards']) && in_array($event['card_id'], $temp['turn_cards'])) unset($temp['turn_cards'][array_search($event['card_id'], $temp['turn_cards'])]);
                            else $temp['turn_cards'][] = $event['card_id'];
                            $isSave = true;
                            break;
                        case 'throw_dice':
                            $temp['temp_data']['cur_dice'] = $event['dice'] = rand(1, 6);
                            $isSave = true;
                            break;
                        case 'end_move':
                            if ($temp['cur_phase'] == 'get_boss') {
                                if (!isset($temp['temp_data']['in_battle']['end_move'])) $temp['temp_data']['in_battle']['end_move'] = [];
                                if (!in_array($event['user_id'], $temp['temp_data']['in_battle']['end_move'])) {
                                    $temp['temp_data']['in_battle']['end_move'][] = $event['user_id'];
                                    $isSave = true;
                                }
                            }
                            break;
                    }
                }
                if ($temp['cur_phase'] != ($nextPhase = \common\libs\Phases::getNextPhase($temp['cur_phase'], $event['action'], array_keys($game['users']), $temp, isset($event['card_id']) ? $event['card_id'] : false))) {
                    if (is_array($nextPhase)) {
                        $temp['cur_move'] = $nextPhase['next_user'];
                        $nextPhase['next_user'] = (string)$nextPhase['next_user'];
                        $event['next_user'][$nextPhase['next_user']] = \common\libs\Phases::getWaitActions($nextPhase['next_phase']);
                        $nextPhase = $nextPhase['next_phase'];
                    } else {
                        $event['next_user'][(string)$temp['cur_move']] = \common\libs\Phases::getWaitActions($nextPhase);
                    }
                    $temp['cur_phase'] = $nextPhase;
                    $event['next_phase'][$nextPhase] = \common\libs\Phases::getActions($nextPhase);
                    $isSave = true;
                    if ($nextPhase == 'get_boss_win') $event['lvl_up'] = $temp['cur_move'];
                }
                if ($isSave) {
                    foreach ($gameData->getAttributes() as $attr => $val) {
                        $gameData->$attr = $temp[$attr];
                    }
                    $gameData->save();
                }
                GameLogsModel::add($data);
            }
            $event['action'] = $actions;
            $topic->broadcast($event);
        } catch (\Exception $e) {
            echo $e->getMessage().' '.$e->getFile().' '.$e->getLine();
        }
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {

    }

    public function onSubscribe(ConnectionInterface $conn, $topic) {
        try {
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
                    $event['count'] = 1;
                } else {
                    $event['type'] = 'not_all_users';
                    $event['count'] = intval($game['count_users'])-count($game['users']);
                }
            }
            $event['next_user'][(string)$gameData['cur_move']] = \common\libs\Phases::getWaitActions($gameData['cur_phase']);
            $event['next_phase'][$gameData['cur_phase']] = \common\libs\Phases::getActions($gameData['cur_phase']);
            $event['users'] = (new \common\models\User)->getUsers($game['users']);
            $topic->broadcast($event);
        } catch (\Exception $e) {
            echo $e->getMessage().' '.$e->getFile().' '.$e->getLine();
        }
    }
}
