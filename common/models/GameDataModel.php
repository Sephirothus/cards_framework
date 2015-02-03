<?php
namespace common\models;

use Yii;
use yii\mongodb\ActiveRecord;
use yii\mongodb\Query;

use common\models\CardsModel;

/**
 * Cards model
 */
class GameDataModel extends ActiveRecord {

	public function attributes() {
        return ['_id', 'decks', 'hand_cards', 'play_cards', 'discards', 'field_cards'];
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
    public function update($id, $users) {
        $obj = new CardsModel();
        $model = self::find()->where(['_id' => $id]);
        if (!$model->decks) {
            $obj->getCards()->shuffleCards();
            //$users = array_keys((new User)->getUsers($users));
        } else {
            $obj->setDecks($model->decks);
        }
        $model->deal_cards = array_merge($model->decks, $obj->dealCards(['doors' => 4, 'treasures' => 4], $users));
        $model->decks = $obj->getDecks();
        $model->save();
    }
}