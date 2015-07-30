<?php
namespace common\libs;

use common\models\CardsModel;

class CardRules extends RulesData {

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

	public $isForbidden = true;

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	public function check() {
		$userId = $this->eventData['user_id'];
		$gameId = $this->game['_id'];
		$action = $this->eventData['action'];

		$card = CardsModel::getOne($this->eventData['card_id']);
		$data = $this->setObj('GameInfo')->getInfo();
		if (in_array($card['parent'], $this->itemsTypes)) $method = 'items';
		else $method = $card['parent'];

		if (!$this->_allowedActions($card, $userId, $gameId, $action)) return 'Данное действие запрещено.';

		if ($action == Action::END_MOVE && $this->_curPhase == 'final_place_cards') {
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
		switch ($action) {
			case Action::FROM_HAND_TO_PLAY:
				if (in_array($card['parent'], array_merge($this->itemsTypes, ['items', 'classes', 'races', 'disposables', 'hirelings'])) || $this->_exceptions($card)) return true;
				else return false;
				break;
			case Action::FROM_HAND_TO_FIELD:
				if (in_array($card['parent'], ['disposables', 'in_battle_monster_bonuses']) || $this->_exceptions($card)) return true;
				else return false;
				break;
			case Action::TURN_CARD_OFF:
			case Action::TURN_CARD_ON:
				if (!in_array($card['parent'], array_merge($this->itemsTypes, ['items']))) return false;
				return $this->_checkCard($this->gameData['play_cards'][$userId], $card['_id']);
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
		if (in_array($action, [Action::FROM_HAND_TO_PLAY, Action::TURN_CARD_ON])) {
			if (isset($data['data']['play_cards'][$userId]['treasures'])) {
				$cards = CardsModel::getAll($data['data']['play_cards'][$userId]['treasures']);
				$overall = 0;
				if (isset($card['type']) && $card['type'] == 'two_hand') $overall += 2;
				else $overall++;
				unset($cards[$card['_id']]);

				foreach ($cards as $row) {
					if ($card['parent'] == $row['parent'] && (!isset($data['data']['turn_cards']) || !in_array($row['_id'], $data['data']['turn_cards']))) {
						if (isset($row['type']) && $row['type'] == 'two_hand') $overall += 2;
						else $overall++;
					}
				}
				if (isset($this->defRules[$card['parent']]) && $overall > $this->defRules[$card['parent']]) $errors .= 'У вас уже исчерпан лимит надетых шмоток такого типа.<br>';
			}
			if (isset($card['size']) && 
				$card['size'] == 'big' && 
				++$data['userInfo']['count_big_items'] > $this->defRules['max_big_items']['default'] && 
				!in_array($this->defRules['max_big_items']['except'], $data['userInfo']['race'])) 
					$errors .= 'У вас уже есть большая шмотка.<br>';
			if ((isset($card['race_type']) && !in_array($card['race_type'], $data['userInfo']['race'])) || (isset($card['race_type_not']) && in_array($card['race_type_not'], $data['userInfo']['race']))) $errors .= 'Ваша расса не может носить данную шмотку<br>';
			if ((isset($card['class_type']) && !in_array($card['class_type'], $data['userInfo']['class'])) || (isset($card['class_type_not']) && in_array($card['class_type_not'], $data['userInfo']['class']))) $errors .= 'Ваш класс не может носить данную шмотку<br>';
			if ((isset($card['sex_type']) && !in_array($card['sex_type'], $data['userInfo']['gender'])) || (isset($card['sex_type_not']) && in_array($card['sex_type_not'], $data['userInfo']['gender']))) $errors .= 'Ваш пол не может носить данную шмотку<br>';
		}
		if ($errors) {
			$this->setObj('Action')->add(Action::TURN_CARD_OFF);
			$this->isForbidden = false;
		}
		return $errors;
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
						$errors .= 'У вас уже есть расса<br>';
				if (count($data['userInfo']['race']) >= $this->defRules['max_races']) $errors .= 'У вас уже максимальное кол-во расс<br>';
				if ($this->_checkCard($data['userInfo']['race'], $card['id'], true, true)) $errors .= 'У вас есть такая расса<br>'; 
				break;
			case 'discard_from_play':
				
				break;
		}
		return $errors;
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
						$errors .= 'У вас уже есть класс<br>';
				if (count($data['userInfo']['class']) >= $this->defRules['max_classes']) $errors .= 'У вас уже максимальное кол-во классов<br>';
				if ($this->_checkCard($data['userInfo']['class'], $card['id'], true, true)) $errors .= 'У вас есть такой класс<br>'; 
				break;
			case 'discard_from_play':
				
				break;
		}
		return $errors;
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
			switch (GameInfo::getCardName($card)) {
				case 'super_munchkin':
					if (!$data['userInfo']['class']) $errors .= 'У вас нету ниодного класса<br>';
					if (isset($data['data']['play_cards'][$userId]['doors']) && $this->_checkCard($data['data']['play_cards'][$userId]['doors'], 'super_munchkin', true)) $errors .= 'У вас уже есть такая карта<br>';
					break;
				case 'half_breed':
					if (isset($data['data']['play_cards'][$userId]['doors']) && $this->_checkCard($data['data']['play_cards'][$userId]['doors'], 'half_breed', true)) $errors .= 'У вас уже есть такая карта<br>';
					break;
			}
		}
		return $errors;
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
}