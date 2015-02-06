<?php
namespace common\models;

use Yii;
use yii\mongodb\ActiveRecord;
use yii\mongodb\Query;

/**
 * Cards model
 */
class GameLogsModel extends ActiveRecord {

	/**
     * Определение имени таблицы
     *
     * @return string название таблицы
     */
    public static function collectionName() {
        return 'game_logs';
    }

	public function attributes() {
        return ['_id', 'games_id', 'user_id', 'card_id', 'card_coords'];
    }

    /**
     * undocumented function
     *
     * @return void
     * @author 
     **/
    public static function add($data) {
    	$model = new static;
        foreach ($data as $key => $val) {
            $model->$key = $val;
        }
        $save = $model->insert();
    	if ($save) return (string)$model->_id;
        else return false;
    }
}