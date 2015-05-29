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
			'',
			['class' => 'row playing_rows js_player_place']
		).Html::tag('div', 
			Html::tag('div', 
				Html::tag('div', 
					Html::tag('div', Html::tag('span', '', ['class' => 'label label-success', 'id' => 'your_str']), ['class' => 'col-md-6 text-left']) .
					Html::tag('div', Html::tag('span', '', ['class' => 'label label-warning', 'id' => 'boss_str']), ['class' => 'col-md-6 text-right']),
					['class' => 'row']
				) .
				Html::button('Закончить ход', ['id' => 'end_move', 'class' => 'btn btn-success', 'style' => 'position: absolute; bottom:0; left:0;']) . 
				Html::button('Забрать на руку', ['id' => 'from_field_to_hand', 'class' => 'btn btn-info', 'style' => 'position: absolute; bottom:0;']) . 
				Html::button('Сбросить все', ['id' => 'discard_all', 'class' => 'btn btn-danger', 'style' => 'position: absolute; bottom:0; right:0;']), 
				['class' => 'col-md-12 text-center playing_rows', 'id' => 'main_field']
			),
			['class' => 'row playing_rows js_player_place']
		), 
		['class' => 'col-md-10']
	).Html::tag('div', 
		Html::tag('div', Html::tag('span', '', ['class' => 'label label-primary', 'id' => 'phase_name']), ['class' => 'row text-center']) . 
		$decks, 
		['class' => 'col-md-2 decks_col']
	), 
	['class' => 'row']
).Html::tag('div', 
	Html::tag('div', 
		Html::tag('div', 
			'',
			['class' => 'row playing_rows js_player_place']
		),
		['class' => 'col-md-10']
	).Html::tag('div', 
		'',
		['class' => 'col-md-2 playing_rows']
	),
	['class' => 'row', 'id' => 'example']
).
Html::input('hidden', 'game_id', $gameId).
Html::input('hidden', 'user_id', Yii::$app->user->identity->_id).
Html::input('hidden', 'ajax_url', Url::to(['/game/ajax-action', 'id' => $gameId]));

//echo Draggable::widget().Droppable::widget().Sortable::widget();
$this->registerJsFile('/js/websocketsWraper.js');
$this->registerJsFile('/js/HtmlBuilder.js');
$this->registerJsFile('/js/DefaultActions.js');
$this->registerJsFile('/js/CardActions.js');
$this->registerJsFile('/js/game.js');
