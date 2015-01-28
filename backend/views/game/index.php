<?php
namespace backend\controllers;

use Yii;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\jui\Draggable;
use yii\jui\Droppable;
use yii\jui\Sortable;

$this->title = "Игра на {$count} игроков";

$decks = '';
foreach ($decksTypes as $type) {
	$decks .= Html::tag('div', 
		Html::tag('div', 
			Html::img(Yii::getAlias('@web').'/imgs/'.$type.'.jpg', ['class' => "decks", 'id' => $type]),
			['class' => 'col-md-12 text-center']
		),
		['class' => 'row']
	).Html::tag('div', 
		Html::tag('div', 
			'',
			['class' => 'col-md-12 text-center']	
		),
		['class' => 'row', 'id' => $type.'_discard']
	);
}

echo Html::tag('div', 
	Html::tag('div', 
		Html::tag('div', 
			(count($players) > 3) ? userBlock($players, 6).userBlock($players, 6) : userBlock($players, 12),
			['class' => 'row playing_rows', 'id' => 'first_row']
		).Html::tag('div', 
			(count($players) > 1) ? userBlock($players, 6).Html::tag('div', '', ['class' => 'col-md-6 text-center', 'id' => 'main_field']) : Html::tag('div', '', ['class' => 'col-md-12 text-center', 'id' => 'main_field']),
			['class' => 'row playing_rows', 'id' => 'second_row']
		), 
		['class' => 'col-md-10']
	).Html::tag('div', 
		/*Html::tag('div', 
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
		).*/$decks, 
		['class' => 'col-md-2 decks_col']
	), 
	['class' => 'row', 'style' => 'margin: 0;']
).Html::tag('div', 
	Html::tag('div', 
		Html::tag('div', 
			(count($players) > 1) ? userBlock($players, 6).userBlock($players, 6) : (!empty($players) ? userBlock($players, 12) : ''),
			['class' => 'row playing_rows', 'id' => 'third_row']
		),
		['class' => 'col-md-10']
	).Html::tag('div', 
		'',
		['class' => 'col-md-2 playing_rows']
	),
	['class' => 'row', 'style' => 'margin: 0;']
).
(!empty($players) ? moreBlocks($players, $this) : '').
Html::input('hidden', 'ajax_url', Url::to(['/game/ajax-action'])).Html::tag('div', '', ['id' => 'message_box']);

echo Draggable::widget().Droppable::widget().Sortable::widget();
$this->registerJsFile('/js/game.js', ['depends' => [\yii\web\JqueryAsset::className()]]);

function userBlock(&$data, $width) {
	reset($data);
	$key = key($data);
	$player = $data[$key];
	unset($data[$key]);
	
	return Html::tag('div', 
		Html::tag('div', 
			Html::tag('div', 
				'', 
				['class' => 'col-md-4 js_hand_cards']
			).Html::tag('div', 
				'', 
				['class' => 'col-md-8 js_first_row', 'style' => 'height:100px;']
			), 
			['class' => 'row']
		).Html::tag('div', 
			Html::tag('div', 
				'', 
				['class' => 'col-md-12 js_second_row']
			).Html::tag('div', 
				Html::tag('span', $player['name'].' '.Html::tag('span', '1 lvl', ['id' => 'lvl']).' '.Html::tag('span', '('.$player['sex'].')', ['id' => 'sex']), ['class' => 'label label-primary']),
				['class' => 'col-md-12 text-left']
			), 
			['class' => 'row']
		), 
		['class' => 'col-md-'.$width.' text-center js_players', 'id' => $key]
	);
}

function moreBlocks(&$data) {
	$block = Html::tag('div', 
		Html::tag('div', 
			Html::tag('div', 
				(count($data) > 1) ? userBlock($data, 6).userBlock($data, 6) : userBlock($data, 12),
				['class' => 'row playing_rows', 'id' => 'third_row']
			),
			['class' => 'col-md-12']
		),
		['class' => 'row', 'style' => 'margin: 0;']
	);
	if (!empty($data)) $block .= moreBlocks($data);
	return $block;
}