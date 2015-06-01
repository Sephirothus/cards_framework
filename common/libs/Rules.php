<?php
namespace common\libs;

class Rules {

	public $defRules = [
		'arms' => 2,
		'head' => 1,
		'armor' => 1,
		'foot' => 1,
		'big_item' => 1,
		'on_hand_cards' => 5,
		'game_win_lvl' => 10
	];

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	public function check($cardId, $userId, $gameId, $action) {
		$card = \common\models\CardsModel::getOne($cardId);
		return true;
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	private function checkType() {

	}
}