<?php
namespace backend\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use common\models\CardsModel;

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
            'count' => $count,
            'decksTypes' => CardsModel::$deckTypes
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
        $obj = new CardsModel();
        switch ($post['type']) {
            case 'deal_cards':
                $obj->getCards()->shuffleCards();
                $data['cards'] = $obj->dealCards(['doors' => 4, 'treasures' => 4], $post['players']);
                $data['decks'] = CardsModel::$deckTypes;
                break;
            case 'get_cards':
                $data = $obj->getCardsByIds($post['cards']);
                break;
        }
        return json_encode(['results' => $data]);
    }
}