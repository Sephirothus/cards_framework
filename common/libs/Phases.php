<?php
namespace common\libs;

class Phases {

	public static $phases = [
		'place_cards',
		'draw_door_card',
		''
	];

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	public function check($phase, $gameId) {
		return true;
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	public function getCurPhase($gameId) {

	}
}