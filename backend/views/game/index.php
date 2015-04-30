<?php
namespace backend\views;

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
			'',//(count($players) > 3) ? userBlock($players, 6).userBlock($players, 6) : userBlock($players, 12),
			['class' => 'row playing_rows js_player_place', 'id' => 'first_row']
		).Html::tag('div', 
			/*(count($players) > 1) ? userBlock($players, 6).Html::tag('div', '', ['class' => 'col-md-6 text-center playing_rows', 'id' => 'main_field']) : */Html::tag('div', '', ['class' => 'col-md-12 text-center playing_rows', 'id' => 'main_field']),
			['class' => 'row playing_rows js_player_place', 'id' => 'second_row']
		), 
		['class' => 'col-md-10']
	).Html::tag('div', 
		$decks, 
		['class' => 'col-md-2 decks_col']
	), 
	['class' => 'row']
).Html::tag('div', 
	Html::tag('div', 
		Html::tag('div', 
			'',//(count($players) > 1) ? userBlock($players, 6).userBlock($players, 6) : (!empty($players) ? userBlock($players, 12) : ''),
			['class' => 'row playing_rows js_player_place', 'id' => 'third_row']
		),
		['class' => 'col-md-10']
	).Html::tag('div', 
		'',
		['class' => 'col-md-2 playing_rows']
	),
	['class' => 'row', 'id' => 'example']
).
//(!empty($players) ? moreBlocks($players, $this) : '').
Html::input('hidden', 'game_id', $gameId).
Html::input('hidden', 'user_id', Yii::$app->user->identity->_id).
Html::input('hidden', 'ajax_url', Url::to(['/game/ajax-action', 'id' => $gameId]));
//Html::tag('div', '', ['id' => 'message_box']);

echo Draggable::widget().Droppable::widget().Sortable::widget();
$this->registerJsFile('/js/websocketsWraper.js');
$this->registerJsFile('/js/CardActions.js');
$this->registerJsFile('/js/game.js');

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