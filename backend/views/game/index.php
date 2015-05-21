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
			['class' => 'row playing_rows js_player_place', 'id' => 'first_row']
		).Html::tag('div', 
			Html::tag('div', 
				Html::button('Сбросить все', ['id' => 'discard_all', 'class' => 'btn btn-danger', 'style' => 'position: absolute; bottom:0;']), 
				['class' => 'col-md-12 text-center playing_rows', 'id' => 'main_field']
			),
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
			'',
			['class' => 'row playing_rows js_player_place', 'id' => 'third_row']
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
