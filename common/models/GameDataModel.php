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
        return ['_id', 'games_id', 'decks', 'hand_cards', 'play_cards', 'discards', 'field_cards'];
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
            $model = new static;
            $obj->getCards()->shuffleCards();
            $model->hand_cards = [];
            $model->games_id = $id;
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
                case 'discards':
                    if (isset($data[$attr])) {
                        foreach ($data[$attr] as $key => $val) {
                            $new[$attr][$key] = $obj->getCardsByIds($val);
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
}