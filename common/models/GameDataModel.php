<?php
namespace common\models;

use Yii;
use yii\mongodb\ActiveRecord;
use yii\mongodb\Query;

use common\models\CardsModel;
use common\helpers\IdHelper;

/**
 * Cards model
 */
class GameDataModel extends ActiveRecord {

	public function attributes() {
        return ['_id', 'games_id', 'decks', 'hand_cards', 'play_cards', 'discards', 'field_cards', 'turn_cards', 'cur_move', 'cur_phase', 'temp_data'];
    }

	/**
     * Определение имени таблицы
     *
     * @return string название таблицы
     */
    public static function collectionName() {
        return 'game_data';
    }

    /**
     * undocumented function
     *
     * @return void
     * @author 
     **/
    public function refresh($id, $users) {
        $obj = new CardsModel();
        $model = self::findOne(['games_id' => IdHelper::toId($id)]);
        if (!$model) {
            reset($users);
            reset(\common\libs\Phases::$phases);
            $model = new static;
            $obj->getCards()->shuffleCards();
            $model->games_id = $id;
            $model->cur_move = key($users);
            $model->cur_phase = key(\common\libs\Phases::$phases);
            $model->hand_cards = [];
        } else {
            $obj->setDecks($model->decks);
        }
        $model->hand_cards = array_merge($model->hand_cards, $obj->dealCards(['doors' => 4, 'treasures' => 4], $users));
        $model->decks = $obj->getDecks();
        $model->save();
    }

    /**
     * undocumented function
     *
     * @return void
     * @author 
     **/
    public static function formData($data, $attrs) {
        $obj = new CardsModel();
        $userId = (string)Yii::$app->user->identity->_id;
        $new = [];
        foreach ($attrs as $attr) {
            $new[$attr] = $data[$attr];
            switch ($attr) {
                case 'turn_cards':
                    $new[$attr] = $data[$attr];
                    break;
                case 'hand_cards':
                    if (isset($data[$attr][$userId])) {
                        foreach ($data[$attr][$userId] as $key => $val) {
                            $new[$attr][$userId][$key] = $obj->getCardsByIds($val);
                        }
                    }
                    break;
                case 'play_cards':
                    if (isset($data[$attr])) {
                        foreach ($data[$attr] as $user => $val) {
                            foreach ($data[$attr][$user] as $key => $val) {
                                $new[$attr][$user][$key] = $obj->getCardsByIds($val);
                            }
                        }
                    }
                    break;
                case 'field_cards':
                    if (isset($data[$attr])) {
                        foreach ($data[$attr] as $key => $val) {
                            $new[$attr][$key] = $obj->getCardsByIds($val);
                        }
                    }
                    break;
                case 'discards':
                    if (isset($data[$attr])) {
                        $new[$attr] = [];
                        foreach ($data[$attr] as $key => $val) {
                            $card = end($val);
                            $new[$attr][$key][$card] = $obj->getCardInfo($card, ['id', 'price', 'bonus']);
                        }
                    }
                    break;
            }
        }
        return $new;
    }

    /**
     * undocumented function
     *
     * @return void
     * @author 
     **/
    public static function findCardType($arr, $find) {
        foreach ($arr as $type => $cards) {
            foreach ($cards as $key => $val) {
                if ($val == $find) return ['type' => $type, 'index' => $key];
            }
        }
        return false;
    }

    /**
     * undocumented function
     *
     * @return void
     * @author 
     **/
    public static function getBattleStr($gameData) {
        $userId = $gameData['cur_move'];
        $game = GamesModel::findOne(['_id' => $gameData['games_id']]);
        $userInfo = (new \common\libs\Rules)->getUserInfo($game['users'][$userId], $gameData, $userId);
        $str['users'] = $game['users'][$userId]['lvl'];
        $str['bosses'] = 0;
        $str['bosses_treasures'] = 0;
        $str['get_lvl'] = 0;
        $cardIds = [];
        if (isset($gameData['play_cards'][$userId])) {
            if (isset($gameData['play_cards'][$userId]['treasures'])) $cardIds = array_merge($cardIds, $gameData['play_cards'][$userId]['treasures']);
            if (isset($gameData['play_cards'][$userId]['doors'])) $cardIds = array_merge($cardIds, $gameData['play_cards'][$userId]['doors']);
        }
        if (isset($gameData['field_cards']['doors'])) $cardIds = array_merge($cardIds, $gameData['field_cards']['doors']);
        if (isset($gameData['field_cards']['treasures'])) $cardIds = array_merge($cardIds, $gameData['field_cards']['treasures']);
        foreach (CardsModel::getAll($cardIds) as $val) {
            switch ($val['parent']) {
                case 'monsters':
                    if (intval($val['lvl']) > 0) {
                        $str['bosses'] += intval($val['lvl']);
                        $str['bosses_treasures'] += intval($val['treasures']);
                        if (isset($val['bonus']['type']) && in_array($val['bonus']['type'][$val['bonus']['type']], $userInfo[$val['bonus']['type']])) {
                            if (isset($val['bonus']['bonus'])) $str['bosses'] += intval($val['bonus']['bonus']);
                            if (isset($val['bonus']['bonus_to_user'])) $str['users'] += intval($val['bonus']['bonus_to_user']);
                        }
                        if (isset($val['get_lvl'])) $str['get_lvl'] += intval($val['get_lvl']);
                        else $str['get_lvl'] += 1;
                    }
                    break;
                case 'in_battle_monster_bonuses':
                    $str['bosses'] += intval($val['monster']);
                    $str['bosses_treasures'] += intval($val['treasures']);
                    break;
                case 'head': 
                case 'armor': 
                case 'foot': 
                case 'arms': 
                case 'items':
                    if (isset($val['bonus']) && intval($val['bonus']) > 0 && !in_array($val['_id'], $gameData['turn_cards'])) $str['users'] += intval($val['bonus']);
                    break;
                case 'disposables':
                    if (isset($val['bonus']) && 
                        intval($val['bonus']) > 0 && 
                        isset($gameData['field_cards']['treasures']) && 
                        in_array($val['_id'], $gameData['field_cards']['treasures'])) {
                            if (isset($temp['temp_data']['in_battle']['bonuses'][$val['_id']]) && $temp['temp_data']['in_battle']['bonuses'][$val['_id']] == 'monster') $str['bosses'] += intval($val['bonus']);
                            else $str['users'] += intval($val['bonus']);
                        }
                    break;
            }
        }
        return $str;
    }
}