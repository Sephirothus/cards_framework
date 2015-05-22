<?php
namespace common\libs;

class Phases {

	public static $phases = [
		'place_cards' => [
			'not' => ['get_treasures_card', 'from_play_to_field', 'from_hand_to_field'],
			'next_on' => 'get_doors_card'
		],
		'get_boss' => [
			'not' => ['get_treasures_card', 'get_doors_card', 'sell_cards', 'turn_card'],
			'next_on' => 'discard_from_field'
		],
		'get_curse' => [
			'yes' => ['discard_from_field'],
			'next_on' => 'discard_from_field'	
		],
		'get_other' => [
			'yes' => ['from_field_to_hand'],
			'next_on' => 'from_field_to_hand'	
		],
		'final_place_cards' => [
			'not' => ['get_treasures_card', 'get_doors_card', 'from_hand_to_field', 'from_play_to_field'],
			'next_on' => 'end_move'
		],
	];

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	public static function check($phase, $gameId) {
		return true;
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	public static function getNextPhase($curPhase, $action, $users, $curUser) {
		$phases = self::$phases;
		if (self::$phases[$curPhase]['next_on'] == $action) {
			reset($phases);
			while(key($phases) != $curPhase) next($phases);

			next($phases);
			$nextPhase = key($phases);
			if (!$nextPhase) {
				$nextPhase['next_phase'] = key(reset($phases));
				$nextPhase['next_user'] = self::getNextUser($users, $curUser);
			}
			return $nextPhase;
		} else return $curPhase;
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	public function getNextUser($users, $curUser) {
		reset($users);
		$curUserKey = array_search($curUser, $users);
		while(key($users) != $curUserKey) next($users);

		if ($nextUser = next($users)) return $nextUser;
		else return reset($users);
	}
}