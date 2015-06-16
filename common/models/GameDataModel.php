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
        $game = GamesModel::findOne(['_id' => $gameData['games_id']]);
        $str['users'] = $game['users'][$gameData['cur_move']]['lvl'];
        $str['bosses'] = 0;
        $str['bosses_treasures'] = 0;
        $cardIds = [];
        if (isset($gameData['play_cards'][$gameData['cur_move']])) {
            if (isset($gameData['play_cards'][$gameData['cur_move']]['treasures'])) $cardIds = array_merge($cardIds, $gameData['play_cards'][$gameData['cur_move']]['treasures']);
            if (isset($gameData['play_cards'][$gameData['cur_move']]['doors'])) $cardIds = array_merge($cardIds, $gameData['play_cards'][$gameData['cur_move']]['doors']);
        }
        if (isset($gameData['field_cards']['doors'])) $cardIds = array_merge($cardIds, $gameData['field_cards']['doors']);
        foreach (CardsModel::getAll($cardIds) as $val) {
            switch ($val['parent']) {
                case 'monsters':
                    if (intval($val['lvl']) > 0) {
                        $str['bosses'] += intval($val['lvl']);
                        $str['bosses_treasures'] += intval($val['treasures']);
                    }
                    break;
                case 'in_battle_monster_bonuses':
                    $str['bosses'] += intval($val['monster']);
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
                        in_array($val['_id'], $gameData['field_cards']['doors'])) 
                            $str['users'] += intval($val['bonus']);
                    break;
            }
        }
        return $str;
    }
}