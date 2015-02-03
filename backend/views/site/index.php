<?php
namespace backend\views;

use Yii;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'mUAnchkin';

$gamesBlock = '';

foreach ($games as $game) {
    $gamesBlock .= Html::tag('div', 
        Html::a('Игра на '.$game['count_users'], Url::to(['/game/index', 'id' => (string)$game['_id']]), ['class' => 'btn btn-primary']),
        ['class' => 'col-md-12']
    );
}
if (empty($games)) $gamesBlock .= Html::tag('div', 'Нету доступных игр', ['class' => 'col-md-12']);

echo Html::tag('div', 
    Html::tag('div', 
        Html::a('Создать игру', Url::to(['/game/create']), ['class' => 'btn btn-success']),
        ['class' => 'col-md-6']
    ).Html::tag('div', 
        Html::tag('div', 
            $gamesBlock,
            ['class' => 'row']
        ),
        ['class' => 'col-md-6']
    ),
    ['class' => 'row', 'style' => 'margin-top:300px;']
);
