<?php
namespace common\libs;

use common\models\CardsModel;
use common\helpers\IdHelper;

class Phases {

	public static $phases = [
		'place_cards' => [
			'not' => ['get_treasures_card', 'from_play_to_field', 'throw_dice', 'end_move'],
			'next_on' => ['open_door' => ['get_boss', 'get_curse', 'get_other']],
			'next_on_param' => 'on_card'
		],

		'get_boss' => [
			'yes' => ['from_play_to_field', 'from_hand_to_field', 'discard_from_play'],
			'next_on' => ['end_move' => [false => 'get_boss_lose', true => 'get_boss_win']],
			'next_on_param' => function($gameData, $action) {
				$str = $bossStr = 0;
				foreach (CardsModel::getAll($gameData['play_cards'][$gameData['cur_move']]['treasures']) as $val) {
					if (isset($val['bonus']) && intval($val['bonus']) > 0) $str += intval($val['bonus']);
				}
				foreach (CardsModel::getAll($gameData['field_cards']['doors']) as $val) {
					if ($val['parent'] == 'monsters' && intval($val['lvl']) > 0) $bossStr += intval($val['lvl']);
				}
				if ($str > $bossStr) return true;
				return false;
			},
			'on_card' => 'monsters'
		],
		'get_boss_lose' => [
			'yes' => ['throw_dice', 'from_hand_to_field', 'from_play_to_field'],
			'next_on' => ['throw_dice' => [false => 'boss_bad_stuff', true => 'final_place_cards'], 'from_hand_to_field' => ['final_place_cards'], 'from_play_to_field' => ['final_place_cards']],
			'next_on_param' => function($gameData, $action) {
				switch ($action) {
					case 'throw_dice':
						if ($gameData['temp_data']['cur_dice'] >= (new Rules)->defRules['get_away_dice']) return true;
						break;
					case 'from_hand_to_field':
						break;
				}
				return false;
			}
		],
		'boss_bad_stuff' => [
			'yes' => ['discard_from_play', 'discard_from_hand'],
			'next_on' => [''],
			'next_on_param' => function($gameData) {

			}
		],
		'get_boss_win' => [
			'yes' => ['get_treasures_card'],
			'next_on' => ['get_treasures_card' => 'final_place_cards'],
			'next_on_param' => function($gameData) {
				if ($gameData['temp_data']['in_battle']['cur_treasures'] == 0) return true;
				else {
					GameDataModel::updateAll(['temp_data' => --$gameData['temp_data']['in_battle']['cur_treasures']], ['_id' => $gameData['games_id']]);
				}
				return false;
			}
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
			'yes' => ['discard_from_field', 'discard_from_play', 'get_doors_card', 'from_hand_to_field'],
			'next_on' => ['get_doors_card' => 'final_place_cards', 'from_hand_to_field' => 'get_boss']
		],

		'final_place_cards' => [
			'not' => ['get_treasures_card', 'get_doors_card', 'open_door', 'from_play_to_field', 'throw_dice'],
			'next_on' => ['end_move' => false]
		],
	];

	public static $inWait = [
		'default_actions' => [
			'yes' => ['discard_from_hand', 'discard_from_play', 'from_hand_to_field'],
		],

		'get_boss' => [
			'yes' => ['from_play_to_field', 'end_move'],
		]
	];

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	public static function getNextPhase($curPhase, $curAction, $users, $gameData, $cardId=false) {
		$phases = self::$phases;
		if (isset($phases[$curPhase]['next_on'][$curAction])) {
			$nextPhase = $phases[$curPhase]['next_on'][$curAction];
			if (!isset($phases[$curPhase]['next_on_param'])) $phases[$curPhase]['next_on_param'] = false;
			switch ($phases[$curPhase]['next_on_param']) {
				case 'on_card':
					if ($cardId) {
						$card = CardsModel::findOne(['_id' => IdHelper::toId($cardId)]);
						foreach ($nextPhase as $val) {
							if (in_array($phases[$val]['on_card'], [$card['parent'], 'default'])) {
								$nextPhase = $val;
								break;
							}	
						}
					}
					break;
			}
			if (gettype($phases[$curPhase]['next_on_param']) == 'function') {
				$check = $phases[$curPhase]['next_on_param']($gameData, $curAction, $cardId);
				if (count($nextPhase) > 1) {
					$nextPhase = $nextPhase[$check];
				} else if (!$check) $nextPhase = $curPhase;
			}

			if (!$nextPhase) {
				reset($phases);
				$nextPhase['next_phase'] = key($phases);
				$nextPhase['next_user'] = self::getNextUser($users, $gameData['cur_move']);
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
		$actions = self::$inWait['default_actions'];
		if (isset(self::$inWait[$phase])) $actions = array_merge_recursive($actions, self::$inWait[$phase]);
		return $actions;
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