<?php
namespace common\libs;

use common\models\CardsModel;
use common\models\GameDataModel;
use common\models\GamesModel;
use common\helpers\IdHelper;

class Phases extends RulesData {

	public static $phases = [
		'place_cards' => [
			'not' => ['get_treasures_card', 'from_play_to_field', 'throw_dice', 'end_move'],
			'next_on' => ['open_door' => ['get_boss', 'get_curse', 'get_other']],
			'next_on_param' => 'on_card'
		],

		'get_boss' => [
			'yes' => ['from_play_to_field', 'from_hand_to_field', 'discard_from_play', 'end_move'],
			'next_on' => ['end_move' => [false => 'get_boss_lose', true => 'get_boss_win']],
			'next_on_func' => 'getBossNext',
			'on_card' => 'monsters'
		],
		'get_boss_lose' => [
			'yes' => ['throw_dice', 'from_hand_to_field', 'from_play_to_field'],
			'next_on' => ['throw_dice' => [false => 'boss_bad_stuff', true => 'final_place_cards'], 'from_hand_to_field' => ['final_place_cards'], 'from_play_to_field' => ['final_place_cards']],
			'next_on_func' => 'getBossLoseNext'
		],
		'boss_bad_stuff' => [
			'yes' => ['discard_from_play', 'discard_from_hand'],
			'next_on' => ['discard_from_play' => 'final_place_cards', 'discard_from_hand' => 'final_place_cards'],
			'next_on_func' => 'bossBadStuffNext'
		],
		'get_boss_win' => [
			'yes' => ['get_treasures_card'],
			'next_on' => ['get_treasures_card' => 'final_place_cards'],
			'next_on_func' => 'getBossWinNext'
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

	private $_userId;

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	public function setUserId($userId) {
		$this->_userId = $userId;
		return $this;
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	public function getNextPhase($curPhase, $curAction, $users, $gameData, $cardId=false) {
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
			if (isset($phases[$curPhase]['next_on_func'])) {
				$check = $this->$phases[$curPhase]['next_on_func']($gameData, $curAction);
				if (count($nextPhase) > 1 && $check !== 'not_yet') $nextPhase = $nextPhase[$check];
				else if (!$check || $check === 'not_yet') $nextPhase = $curPhase;
			}
			if (!$nextPhase) {
				reset($phases);
				$nextPhase['next_phase'] = key($phases);
				$nextPhase['next_user'] = $this->getNextUser($users, $gameData['cur_move']);
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
	public static function check($phase, $userId, $action, $isMain) {
		$phase = $isMain ? self::getActions($phase) : self::getWaitActions($phase);
		if (isset($phase['not'])) {
			if (in_array($action, $phase['not'])) return false;
		} elseif (isset($phase['yes'])) {
			if (!in_array($action, $phase['yes'])) return false;
		}
		return true;
	}

	/* ==================== Special Check ========================*/

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	private function getBossNext($gameData) {
		$str = GameDataModel::getBattleStr($gameData);
		print_r($str);
		if ($str['users'] > $str['bosses']) {
			$game = GamesModel::findOne(['_id' => $gameData['games_id']]);
			if (!isset($gameData['temp_data']['in_battle']['end_move']) || count($gameData['temp_data']['in_battle']['end_move']) < count($game['users'])) return 'not_yet';
			
			$gameData['temp_data']['in_battle']['cur_treasures'] = $str['bosses_treasures'];
			$gameData['temp_data']['in_battle']['end_move'] = [];
			GameDataModel::updateAll(['temp_data' => $gameData['temp_data']], ['_id' => $gameData['_id']]);
			$lvl = $game['users'][$gameData['cur_move']]['lvl'];
            GamesModel::changeUserInfo($gameData['cur_move'], $gameData['games_id'], ['lvl' => ++$lvl]);
			return true;
		} elseif ($this->_userId == $gameData['cur_move']) return false;

		return 'not_yet';
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	private function getBossLoseNext($gameData, $action) {
		switch ($action) {
			case 'throw_dice':
				if ($gameData['temp_data']['cur_dice'] >= (new Rules)->defRules['get_away_dice']) return true;
				break;
			case 'from_hand_to_field':
				break;
		}
		return false;
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	private function getBossWinNext($gameData) {
		$gameData['temp_data']['in_battle']['cur_treasures']--;
		GameDataModel::updateAll(['temp_data' => $gameData['temp_data']], ['_id' => $gameData['_id']]);
		if ($gameData['temp_data']['in_battle']['cur_treasures'] == 0) return true;
		else return false;
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	private function bossBadStuffNext($gameData) {
		$cards = [];
		if (isset($gameData['field_cards']['treasures'])) $cards = $gameData['field_cards']['treasures'];
		if (isset($gameData['field_cards']['doors'])) $cards = $gameData['field_cards']['doors'];

		$cards = CardsModel::findOne(['_id' => array_map(function($val) { return IdHelper::toId($val); }, $cards)]);
		foreach ($cards as $card) {
			$onlyOne = isset($card['bad_stuff'][0]) && $card['bad_stuff'][0] == 'or' ? true : false;
			foreach ($card['bad_stuff'] as $key => $val) {
				switch ($key) {
					case 'discard_by_param':
						foreach ($val['type'] as $param => $value) {
							if (is_string($param)) {
								
							} else {

							}
						}
						break;
					case 'discard_hand_cards':
						
						break;
					case 'lost_lvl':

						break;
				}
				if ($onlyOne) break;
			}
		}
		return false;
	}

	/* ==========================================================*/

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