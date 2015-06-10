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
		'game_win_lvl' => 10
	];

	public $itemsTypes = [
		'head', 'armor', 'foot', 'arms', 'items'
	];

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

		if ($action == 'end_move') {
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
					if (!isset($cards[$card['_id']]) && isset($card['size']) && $card['size'] == 'big' && isset($row['size']) && $row['size'] == 'big' && $data['userInfo']['race'] != 'dwarf') {
						$errors .= ($errors ? '<br>' : '').'У вас уже есть большая шмотка.';
					}
				}
				if (isset($this->defRules[$card['parent']]) && $overall > $this->defRules[$card['parent']]) $errors .= ($errors ? '<br>' : '').'У вас уже исчерпан лимит надетых шмоток такого типа.';
			}
			if ((isset($card['race_type']) && $card['race_type'] != $data['userInfo']['race']) || (isset($card['race_type_not']) && $card['race_type_not'] == $data['userInfo']['race'])) $errors .= ($errors ? '<br>' : '').'Ваша расса не может носить данную шмотку';
			if ((isset($card['class_type']) && $card['class_type'] != $data['userInfo']['class']) || (isset($card['class_type_not']) && $card['class_type_not'] == $data['userInfo']['class'])) $errors .= ($errors ? '<br>' : '').'Ваш класс не может носить данную шмотку';
			if ((isset($card['sex_type']) && $card['sex_type'] != $data['userInfo']['gender']) || (isset($card['sex_type_not']) && $card['sex_type_not'] == $data['userInfo']['gender'])) $errors .= ($errors ? '<br>' : '').'Ваш пол не может носить данную шмотку';
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
		if ($action == 'from_hand_to_play') {
			if (!in_array('human', $data['userInfo']['race']) && !$this->_checkCard($data['data']['play_cards'][$userId]['doors'], 'half_breed', true)) return 'У вас уже есть расса';
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
		if ($action == 'from_hand_to_play') {
			if (!empty($data['userInfo']['class']) && !$this->_checkCard($data['data']['play_cards'][$userId]['doors'], 'super_munchkin', true)) return 'У вас уже есть класс';
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
					if ($this->_checkCard($data['data']['play_cards'][$userId]['doors'], 'super_munchkin', true)) return 'У вас уже есть такая карта';
					break;
				case 'half_breed':
					if ($this->_checkCard($data['data']['play_cards'][$userId]['doors'], 'half_breed', true)) return 'У вас уже есть такая карта';
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
	private function _checkCard($data, $cardId, $halfOfName=false) {
		if ($halfOfName) {
			foreach (CardsModel::getAll($data) as $key => $val) {
				if (strpos($val['id'], $cardId) === 0) return true;
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
		$info = ['class' => [], 'race' => [], 'gender' => $userInfo['gender'], 'lvl' => $userInfo['lvl']];
		foreach (CardsModel::getAll($data['play_cards'][$userId]['doors']) as $card) {
			switch ($card['parent']) {
				case 'classes':
					$info['class'][] = $this->_getCardName($card);
					break;
				case 'races':
					$info['race'][] = $this->_getCardName($card);
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