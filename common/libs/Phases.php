<?php
namespace common\libs;

class Phases {

	public static $phases = [
		'place_cards' => [
			'not' => ['get_treasures_card', 'from_play_to_field', 'from_hand_to_field'],
			'next_on' => 'get_doors_card'
		],
		'open_door' => [
			'get_boss' => [
				'not' => ['get_treasures_card', 'get_doors_card', 'sell_cards', 'turn_card', 'from_field_to_hand'],
				'next_on' => 'discard_from_field',
				'on_card' => 'monsters',
				'subphase' => [
					'win' => ['get_treasures_card'],
					'lose' => []
				]
			],
			'get_curse' => [
				'yes' => ['discard_from_field', 'discard_from_play'],
				'next_on' => 'discard_from_field',
				'on_card' => 'curses',
			],
			'get_other' => [
				'yes' => ['from_field_to_hand'],
				'next_on' => 'from_field_to_hand',
				'on_card' => 'default'
			],
		],
		'final_place_cards' => [
			'not' => ['get_treasures_card', 'get_doors_card', 'from_hand_to_field', 'from_play_to_field'],
			'next_on' => 'end_move'
		],
	];

	public static $inWait = [
		'place_cards' => [
			'yes' => ['from_hand_to_field', 'discard_from_hand', 'discard_from_play'],
		],
		'open_door' => [
			'get_boss' => [
				'yes' => ['from_hand_to_field', 'from_play_to_field', 'discard_from_hand', 'discard_from_play'],
			],
			'get_curse' => [
				'yes' => ['from_hand_to_field', 'discard_from_hand', 'discard_from_play'],
			],
			'get_other' => [
				'yes' => ['from_hand_to_field', 'discard_from_hand', 'discard_from_play'],
			],
		],
		'final_place_cards' => [
			'yes' => ['from_hand_to_field', 'discard_from_hand', 'discard_from_play'],
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
	public static function getNextPhase($curPhase, $action, $users, $curUser, $cardId=false) {
		$phases = self::$phases;
		if (self::getActions($curPhase)['next_on'] == $action) {
			if (!isset($phases[$curPhase])) $curPhase = 'open_door';
			reset($phases);
			while(key($phases) != $curPhase) next($phases);

			next($phases);
			$nextPhase = key($phases);
			if ($nextPhase && !isset(self::$phases[$nextPhase]['next_on']) && $cardId) {
				$card = \common\models\CardsModel::findOne(['_id' => \common\helpers\IdHelper::toId($cardId)]);
				$nextPhase = self::_searchKey(self::$phases[$nextPhase], 'on_card', $card['parent']);
			}
			if (!$nextPhase) {
				reset($phases);
				$nextPhase['next_phase'] = key($phases);
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
	private static function _searchKey($arr, $neededKey, $neededVal) {
		foreach ($arr as $key => $value) {
			if (isset($value[$neededKey]) && ($neededVal == $value[$neededKey] || $value[$neededKey] == 'default')) return $key;
		}
		return false;
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	public static function getWaitActions($phase) {
		return isset(self::$inWait[$phase]) ? self::$inWait[$phase] : self::$inWait['open_door'][$phase];
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	public static function getActions($phase) {
		return isset(self::$phases[$phase]) ? self::$phases[$phase] : self::$phases['open_door'][$phase];
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