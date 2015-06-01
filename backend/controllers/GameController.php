<?php
namespace backend\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\helpers\Json;
use yii\helpers\Url;
use common\helpers\IdHelper;
use common\models\CardsModel;
use common\models\GamesModel;
use common\models\GameDataModel;
use common\models\User;

/**
 * Site controller
 */
class GameController extends Controller {
    /**
     * @inheritdoc
     */
    public function behaviors() {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'actions' => ['index', 'create', 'ajax-action'],
                        'allow' => true,
                        'roles' => ['@']
                    ]
                ],
            ],
        ];
    }

    public function actionIndex($id) {
        $userId = (string)Yii::$app->user->identity->_id;
        $game = GamesModel::findOne(['_id' => $id]);
        if (!$game) return $this->redirect(Url::toRoute(['/site']));
        
        $isIn = isset($game['users'][$userId]);
        if ($game['status'] == GamesModel::$status['new'] && 
            !$isIn &&
            count($game['users']) < $game['count_users']) {
                (new GamesModel)->addUser($id);
                $game = GamesModel::findOne(['_id' => $id]);
        } elseif (!$isIn) return $this->redirect(Url::toRoute(['/site'])); 

        return $this->render('index', [
            'players' => (new User)->getUsers($game['users']),
            'count' => $game['count_users'],
            'decksTypes' => CardsModel::$deckTypes,
            'gameId' => $id
        ]);
    }

    /**
     * undocumented function
     *
     * @return void
     * @author 
     **/
    public function actionCreate() {
        $model = new GamesModel;
        if ($post = Yii::$app->request->post()) {
            $model->count_users = $post['GamesModel']['count_users'];
            $id = $model->create($model);
            if ($id) $url = Url::toRoute(['/game/index', 'id' => $id]);
            else $url = Url::toRoute(['/site']);

            return $this->redirect($url);
        } else {
            return $this->render('create', [
                'model' => $model
            ]);
        }
    }

    /**
     * undocumented function
     *
     * @return void
     * @author 
     **/
    public function actionAjaxAction($id) {
        $post = Yii::$app->request->post();
        $obj = new CardsModel();
        switch ($post['type']) {
            case 'get_cards':
                $data = $obj->getCardsByIds($post['cards']);
                break;
            case 'restore_game':
                $data = GameDataModel::findOne(['games_id' => IdHelper::toId($post['game_id'])]);
                $data = GameDataModel::formData($data->getAttributes(), ['hand_cards', 'play_cards', 'field_cards', 'discards', 'turn_cards']);
                break;
        }
        return Json::encode(['results' => $data]);
    }
}