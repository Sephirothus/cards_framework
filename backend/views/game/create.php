<?php
namespace backend\views;

use Yii;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

$this->title = 'mUAnchkin - Создание новой игры';

$select = [];
for ($i = 1; $i<10; $i++) {
	$select[$i] = $i;
}

$form = ActiveForm::begin();
echo Html::tag('div', 
	Html::tag('div', 
		$form->field($model, 'count_users')->listBox($select).
		Html::submitButton('Создать', ['class' => 'btn btn-primary', 'name' => 'login-button']),
		['class' => 'col-md-6']
	),
	['class' => 'row']
);
ActiveForm::end();