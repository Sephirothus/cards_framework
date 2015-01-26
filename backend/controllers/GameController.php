<?php
namespace backend\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use common\models\Cards;

/**
 * Site controller
 */
class GameController extends Controller {
    /**
     * @inheritdoc
     */
    /*public function behaviors() {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'actions' => ['login', 'error'],
                        'allow' => true,
                    ]
                ],
            ],
        ];
    }*/

    public function actionIndex($count=3) {
        $players = [];
        for ($i=0; $i<$count; $i++) {
            $players[rand(11111, 99999)] = ['name' => 'Petya', 'sex' => 'male'];
        }
        return $this->render('index', [
            'players' => $players,
            'count' => $count
        ]);
    }

    /**
     * undocumented function
     *
     * @return void
     * @author 
     **/
    public function actionAjaxAction() {
        $post = Yii::$app->request->post();
        $obj = new Cards();
        $obj->gameType = 'online';
        switch ($post['type']) {
            case 'deal_cards':
                $obj->getCards()->shuffleCards();
                $data = $obj->dealCards(['doors' => 4, 'treasures' => 4], $post['players']);
                break;
            case 'get_cards':
                $data = $obj->getCardsByIds($post['cards']);
                break;
        }
        return json_encode(['results' => $data]);
    }
}