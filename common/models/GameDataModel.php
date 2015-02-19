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
}