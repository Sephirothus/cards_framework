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

    public function actionIndex($players=3) {
        $obj = new Cards();
        $obj->getCards()->shuffleCards();
        $cards = $obj->dealCards(['doors' => 4, 'treasures' => 4], $players);
        return $this->render('index', [
            'players' => $players,
            'cards' => $cards
        ]);
    }
}