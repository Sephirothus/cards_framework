<?php
namespace common\models;

use Yii;
use yii\mongodb\ActiveRecord;
use yii\mongodb\Query;

use common\models\GameDataModel;

/**
 * Cards model
 */
class GamesModel extends ActiveRecord {

	public static $status = [
		'new' => 'new',
		'in_progress' => 'in_progress',
		'done' => 'done'
	];

	public function attributes() {
        return ['_id', 'host_id', 'count_users', 'users', 'game_data', 'created_date', 'status'];
    }

	/**
     * Определение имени таблицы
     *
     * @return string название таблицы
     */
    public static function collectionName() {
        return 'games';
    }

    /**
     * undocumented function
     *
     * @return void
     * @author 
     **/
    public function create($model) {
    	if (!intval($model->count_users)) $model->count_users = 2;
        $model->host_id = Yii::$app->user->identity->_id;
        $model->users = [Yii::$app->user->identity->_id];
        $model->created_date = date('Y-m-d H:i:s');
        $model->status = self::$status['new'];
        if ($model->insert()) {
        	$id = (string)$model->_id;
        	(new GameDataModel)->refresh($model->_id, $model->users);
        	return $id;
        } else return false;
    }

    /**
     * undocumented function
     *
     * @return void
     * @author 
     **/
    public function addUser($id) {
    	$userId = Yii::$app->user->identity->_id;
    	$model = self::findOne(['_id' => $id]);
    	$model->users = array_merge($model->users, [$userId]);
    	if ($model->save()) (new GameDataModel)->refresh($id, [$userId]);
    }

    /**
     * undocumented function
     *
     * @return void
     * @author 
     **/
    public static function usersToArr($users) {
    	$new = [];
    	foreach ($users as $user) {
    		$new[] = (string)$user;
    	}
    	return $new;
    }
}