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
        $userId = (string)Yii::$app->user->identity->_id;
    	if (!intval($model->count_users)) $model->count_users = 2;
        $user = \common\models\User::findOne(['_id' => $userId]);
        $model->host_id = $userId;
        $model->users = [$userId => ['lvl' => 1, 'gender' => $user['gender']]];
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
    	$userId = (string)Yii::$app->user->identity->_id;
    	$model = self::findOne(['_id' => $id]);
        $user = [$userId => ['lvl' => 1, 'gender' => \common\models\User::findOne(['_id' => $userId])['gender']]];
    	$model->users = array_merge($model->users, $user);
    	if ($model->save()) (new GameDataModel)->refresh($id, $user);
    }
}