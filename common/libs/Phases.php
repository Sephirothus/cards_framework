<?php
namespace common\libs;

class Phases {

	public static $phases = [
		'place_cards' => [
			'not' => ['get_treasures_card', 'from_play_to_field', 'from_hand_to_field'],
			'next_on' => ['open_door' => ['get_boss', 'get_curse', 'get_other']],
			'next_on_param' => 'on_card'
		],

		'get_boss' => [
			'not' => ['get_treasures_card', 'open_door', 'sell_cards', 'turn_card', 'from_field_to_hand', 'from_hand_to_play'],
			'next_on' => ['discard_from_field' => 'final_place_cards'],
			'on_card' => 'monsters',
			'subphase' => [
				'win' => ['get_treasures_card'],
				'lose' => []
			],
		],
		'get_curse' => [
			'yes' => ['discard_from_field', 'discard_from_play'],
			'next_on' => ['discard_from_field' => 'not_boss'],
			'on_card' => 'curses',
		],
		'get_other' => [
			'yes' => ['from_field_to_hand'],
			'next_on' => ['from_field_to_hand' => 'not_boss'],
			'on_card' => 'default',
		],

		'not_boss' => [
			'yes' => ['discard_from_field', 'discard_from_play', 'get_doors_card'],
			'next_on' => ['get_doors_card' => 'final_place_cards', 'from_hand_to_field' => 'get_boss']
		],

		'final_place_cards' => [
			'not' => ['get_treasures_card', 'open_door', 'from_hand_to_field', 'from_play_to_field'],
			'next_on' => ['end_move' => false]
		],
	];

	public static $inWait = [
		'default_actions' => [
			'yes' => ['discard_from_hand', 'discard_from_play', 'from_hand_to_field'],
		],
		'get_boss' => [
			'yes' => ['from_hand_to_field', 'from_play_to_field', 'discard_from_hand', 'discard_from_play'],
		]
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
	public static function getNextPhase($curPhase, $curAction, $users, $curUser, $cardId=false) {
		$phases = self::$phases;
		if (isset($phases[$curPhase]['next_on'][$curAction])) {
			$nextPhase = $phases[$curPhase]['next_on'][$curAction];
			if (is_array($nextPhase) && $phases[$curPhase]['next_on_param'] == 'on_card' && $cardId) {
				$card = \common\models\CardsModel::findOne(['_id' => \common\helpers\IdHelper::toId($cardId)]);
				foreach ($nextPhase as $val) {
					if (in_array($phases[$val]['on_card'], [$card['parent'], 'default'])) {
						$nextPhase = $val;
						break;
					}	
				}
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
	public static function getWaitActions($phase) {
		return isset(self::$inWait[$phase]) ? self::$inWait[$phase] : self::$inWait['default_actions'];
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	public static function getActions($phase) {
		return self::$phases[$phase];
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