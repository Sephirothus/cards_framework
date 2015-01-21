<?php
namespace backend\controllers;

use Yii;
use yii\helpers\Html;
use yii\jui\Draggable;
use yii\jui\Droppable;

$this->title = "Игра на {$players} игроков";

$blocks = [];
foreach ($cards as $player => $val) {
	$blocks[] = $this->render('userBlock', ['cards' => $val, 'player' => $player]);
}

echo Html::tag('div', 
	Html::tag('div', 
		Html::tag('div', 
			(count($blocks) > 3) ? current($blocks).next($blocks) : current($blocks), 
			['class' => 'row playing_rows', 'id' => 'first_row']
		).Html::tag('div', 
			(count($blocks) > 2) ? next($blocks).Html::tag('div', '', ['class' => 'col-md-6 text-center']) : Html::tag('div', '', ['class' => 'col-md-12 text-center']),
			['class' => 'row playing_rows', 'id' => 'second_row']
		), 
		['class' => 'col-md-10']
	).Html::tag('div', 
		Html::tag('div', 
			Html::tag('div', 
				Html::tag('span', '', ['class' => "glyphicon glyphicon-play js_action_buttons", 'action' => "play", 'style' => "color:red;"]).
				Html::tag('span', '', ['class' => "glyphicon glyphicon-euro js_action_buttons", 'action' => "sell"]).
				Html::tag('span', '', ['class' => "glyphicon glyphicon-hand-down js_action_buttons", 'action' => "turn_card_down"]).
				Html::tag('span', '', ['class' => "glyphicon glyphicon-hand-up js_action_buttons", 'action' => "turn_card_up"]).
				Html::tag('span', '', ['class' => "glyphicon glyphicon-refresh js_action_buttons", 'action' => "trade"]).
				Html::tag('span', '', ['class' => "glyphicon glyphicon-remove js_action_buttons", 'action' => "discard"]), 
				['class' => 'col-md-12 text-center action_buttons_field']
			), 
			['class' => 'row']
		).Html::tag('div', 
			Html::tag('div', 
				Html::img(Yii::getAlias('@web').'/imgs/doors.jpg', ['class' => "decks"]),
				['class' => 'col-md-12 text-center']	
			),
			['class' => 'row', 'id' => 'doors']
		).Html::tag('div', 
			Html::tag('div', 
				'',
				['class' => 'col-md-12 text-center']	
			),
			['class' => 'row', 'id' => 'doors_discard']
		).Html::tag('div', 
			Html::tag('div', 
				Html::img(Yii::getAlias('@web').'/imgs/treasures.jpg', ['class' => "decks"]),
				['class' => 'col-md-12 text-center']	
			),
			['class' => 'row', 'id' => 'treasures']
		).Html::tag('div', 
			Html::tag('div', 
				'',
				['class' => 'col-md-12 text-center']	
			),
			['class' => 'row', 'id' => 'treasures_discard']
		), 
		['class' => 'col-md-2 decks_col']
	), 
	['class' => 'row', 'style' => 'margin: 0;']
).Html::tag('div', 
	Html::tag('div', 
		Html::tag('div', 
			(count($blocks) > 3) ? next($blocks).next($blocks) : next($blocks),
			['class' => 'row playing_rows', 'id' => 'third_row']
		),
		['class' => 'col-md-10']
	).Html::tag('div', 
		'',
		['class' => 'col-md-2 playing_rows']
	),
	['class' => 'row', 'style' => 'margin: 0;']
).Html::tag('div', '', ['id' => 'message_box']);
echo Draggable::widget().Droppable::widget();

$this->registerJsFile('/js/game.js', ['depends' => [\yii\web\JqueryAsset::className()]]);
