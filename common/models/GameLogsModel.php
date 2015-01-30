<?php
namespace common\models;

use Yii;
use yii\mongodb\Collection;
//use yii\base\Model;
use yii\mongodb\Query;

/**
 * Cards model
 */
class GameLogsModel extends Collection {

	/**
     * Определение имени таблицы
     *
     * @return string название таблицы
     */
    public static function collectionName() {
        return 'game_logs';
    }

	public function attributes() {
        return ['_id', 'game_id', 'user_id', 'card_id', 'card_coords'];
    }

    /**
     * undocumented function
     *
     * @return void
     * @author 
     **/
    public static function add($data) {
    	$import = Yii::$app->mongodb->getCollection(self::collectionName());
    	return $import->insert($data);
    }
}