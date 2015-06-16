<?php
namespace common\libs;

use common\models\CardsModel;
use common\models\GameDataModel;
use common\models\GamesModel;

class Rules {

	public $defRules = [
		'arms' => 2,
		'head' => 1,
		'armor' => 1,
		'foot' => 1,
		'big_item' => 1,
		'on_hand_cards' => [
			'default' => 5,
			'dwarf' => 6
		],
		'max_big_items' => [
			'default' => 1,
			'except' => 'dwarf'
		],
		'max_races' => 2,
		'max_classes' => 2,
		'game_win_lvl' => 10,
		'get_away_dice' => 5
	];

	public $itemsTypes = [
		'head', 'armor', 'foot', 'arms', 'items'
	];

	private $_curPhase;

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	public function check($cardId, $userId, $gameId, $action) {
		$card = CardsModel::getOne($cardId);
		$data = $this->_getInfo($userId, $gameId);
		if (in_array($card['parent'], $this->itemsTypes)) $method = 'items';
		else $method = $card['parent'];

		if (!$this->_allowedActions($card, $userId, $gameId, $action)) return 'Данное действие запрещено.';

		if ($action == 'end_move' && $this->_curPhase == 'final_place_cards') {
			$total = 0;
			$needed = $this->defRules['on_hand_cards']['default'];
			if (in_array('dwarf', $data['userInfo']['race'])) $needed = $this->defRules['on_hand_cards']['dwarf'];
			foreach ($data['data']['hand_cards'][$userId] as $cards) {
				$total += count($cards);
			}
			if ($total > $needed) return 'У вас слишком много карт на руке';
		}

		return method_exists($this, $method) ? $this->$method($card, $data, $userId, $gameId, $action) : '';
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	private function _allowedActions($card, $userId, $gameId, $action) {
		$data = GameDataModel::findOne(['games_id' => $gameId]);
		switch ($action) {
			case 'from_hand_to_play':
				if (in_array($card['parent'], array_merge($this->itemsTypes, ['items', 'classes', 'races', 'disposables', 'hirelings'])) || $this->_exceptions($card)) return true;
				else return false;
				break;
			case 'from_hand_to_field':
				if (in_array($card['parent'], ['disposables', 'in_battle_monster_bonuses']) || $this->_exceptions($card)) return true;
				else return false;
				break;
			case 'turn_card_off':
			case 'turn_card_on':
				if (!in_array($card['parent'], array_merge($this->itemsTypes, ['items']))) return false;
				return $this->_checkCard($data['play_cards'][$userId], $card['_id']);
				break;
		}
		return true;
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	private function _exceptions($card) {
		switch ($card['parent']) {
			case 'other_doors':
				foreach (['cheat', 'half_breed', 'super_munchkin'] as $find) {
					if (strpos($card['id'], $find) === 0) return true;
				}
				break;
			case 'monsters':
				if ($this->_curPhase == 'not_boss') return true;
				break;
		}
		return false;
	}

	/* =================== Cards subtypes =======================*/

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	private function items($card, $data, $userId, $gameId, $action) {
		$errors = '';
		if (in_array($action, ['from_hand_to_play', 'turn_card_on'])) {
			if (isset($data['data']['play_cards'][$userId]['treasures'])) {
				$cards = CardsModel::getAll($data['data']['play_cards'][$userId]['treasures']);
				$overall = 0;
				if (isset($card['type']) && $card['type'] == 'two_hand') $overall += 2;
				else $overall++;
				unset($cards[$card['_id']]);

				foreach ($cards as $row) {
					if ($card['parent'] == $row['parent'] && !in_array($row['_id'], $data['data']['turn_cards'])) {
						if (isset($row['type']) && $row['type'] == 'two_hand') $overall += 2;
						else $overall++;
					}
				}
				if (isset($this->defRules[$card['parent']]) && $overall > $this->defRules[$card['parent']]) $errors .= ($errors ? '<br>' : '').'У вас уже исчерпан лимит надетых шмоток такого типа.';
			}
			if (isset($card['size']) && 
				$card['size'] == 'big' && 
				++$data['userInfo']['count_big_items'] > $this->defRules['max_big_items']['default'] && 
				!in_array($this->defRules['max_big_items']['except'], $data['userInfo']['race'])) 
					$errors .= ($errors ? '<br>' : '').'У вас уже есть большая шмотка.';
			if ((isset($card['race_type']) && !in_array($card['race_type'], $data['userInfo']['race'])) || (isset($card['race_type_not']) && in_array($card['race_type_not'], $data['userInfo']['race']))) $errors .= ($errors ? '<br>' : '').'Ваша расса не может носить данную шмотку';
			if ((isset($card['class_type']) && !in_array($card['class_type'], $data['userInfo']['class'])) || (isset($card['class_type_not']) && in_array($card['class_type_not'], $data['userInfo']['class']))) $errors .= ($errors ? '<br>' : '').'Ваш класс не может носить данную шмотку';
			if ((isset($card['sex_type']) && !in_array($card['sex_type'], $data['userInfo']['gender'])) || (isset($card['sex_type_not']) && in_array($card['sex_type_not'], $data['userInfo']['gender']))) $errors .= ($errors ? '<br>' : '').'Ваш пол не может носить данную шмотку';
		}
		return $errors ? ['text' => $errors, 'action' => 'turn_card_off'] : '';
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	private function races($card, $data, $userId, $gameId, $action) {
		$errors = '';
		switch ($action) {
			case 'from_hand_to_play':
				if (!in_array('human', $data['userInfo']['race']) && 
					isset($data['data']['play_cards'][$userId]['doors']) && 
					!$this->_checkCard($data['data']['play_cards'][$userId]['doors'], 'half_breed', true)) 
						return 'У вас уже есть расса';
				if (count($data['userInfo']['race']) >= $this->defRules['max_races']) return 'У вас уже максимальное кол-во расс';
				if ($this->_checkCard($data['userInfo']['race'], $card['id'], true, true)) return 'У вас есть такая расса'; 
				break;
			case 'discard_from_play':
				
				break;
		}
		return '';
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	private function classes($card, $data, $userId, $gameId, $action) {
		$errors = '';
		switch ($action) {
			case 'from_hand_to_play':
				if (!empty($data['userInfo']['class']) && 
					isset($data['data']['play_cards'][$userId]['doors']) && 
					!$this->_checkCard($data['data']['play_cards'][$userId]['doors'], 'super_munchkin', true)) 
						return 'У вас уже есть класс';
				if (count($data['userInfo']['class']) >= $this->defRules['max_classes']) return 'У вас уже максимальное кол-во классов';
				if ($this->_checkCard($data['userInfo']['class'], $card['id'], true, true)) return 'У вас есть такой класс'; 
				break;
			case 'discard_from_play':
				
				break;
		}
		return '';
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	private function other_doors($card, $data, $userId, $gameId, $action) {
		$errors = '';
		if ($action == 'from_hand_to_play') {
			switch ($this->_getCardName($card)) {
				case 'super_munchkin':
					if (!$data['userInfo']['class']) return 'У вас нету ниодного класса';
					if (isset($data['data']['play_cards'][$userId]['doors']) && $this->_checkCard($data['data']['play_cards'][$userId]['doors'], 'super_munchkin', true)) return 'У вас уже есть такая карта';
					break;
				case 'half_breed':
					if (isset($data['data']['play_cards'][$userId]['doors']) && $this->_checkCard($data['data']['play_cards'][$userId]['doors'], 'half_breed', true)) return 'У вас уже есть такая карта';
					break;
			}
		}
		return '';
	}



	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	private function _checkCard($data, $cardId, $halfOfName=false, $withoutDbGet=false) {
		if ($halfOfName) {
			if (!$withoutDbGet) $data = CardsModel::getAll($data);
			foreach ($data as $val) {
				if (strpos(!empty($val['id']) ? $val['id'] : $val, $cardId) === 0) return true;
			}
		} else {
			foreach ($data as $cards) {
				if (in_array($cardId, $cards)) return true;
			}
		}
		return false;
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	private function _getInfo($userId, $gameId) {
		$game = GamesModel::findOne(['_id' => $gameId]);
		$data = GameDataModel::findOne(['games_id' => $gameId]);
		$this->_curPhase = $data['cur_phase'];
		return [
			'data' => $data,
			'userInfo' => $this->_getUserInfo($game['users'][$userId], $data, $userId)
		];
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	private function _getUserInfo($userInfo, $data, $userId) {
		$cards = [];
		$info = ['class' => [], 'race' => [], 'gender' => $userInfo['gender'], 'lvl' => $userInfo['lvl'], 'count_big_items' => 0];
		if (isset($data['play_cards'][$userId]['doors'])) $cards = array_merge($cards, $data['play_cards'][$userId]['doors']);
		if (isset($data['play_cards'][$userId]['treasures'])) $cards = array_merge($cards, $data['play_cards'][$userId]['treasures']);

		foreach (CardsModel::getAll($cards) as $card) {
			switch ($card['parent']) {
				case 'classes':
					$info['class'][] = $this->_getCardName($card);
					break;
				case 'races':
					$info['race'][] = $this->_getCardName($card);
					break;
				case 'head': 
                case 'armor': 
                case 'foot': 
                case 'arms': 
                case 'items':
					if (isset($card['size']) && $card['size'] == 'big' && !in_array($card['_id'], $data['turn_cards'])) $info['count_big_items']++;
					break;
			}
		}
		if (empty($info['race'])) $info['race'][] = 'human';
		return $info;
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	private function _getCardName($card) {
		return explode('-', $card['id'])[0];
	}
}