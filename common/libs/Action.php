<?php
namespace common\libs;

class Action extends RulesData {

	const FROM_HAND_TO_PLAY = 'from_hand_to_play';
	const FROM_HAND_TO_FIELD = 'from_hand_to_field';
	const FROM_PLAY_TO_FIELD = 'from_play_to_field';
	const FROM_FIELD_TO_HAND = 'from_field_to_hand';
	const OPEN_DOOR = 'open_door';
	const GET_TREASURES_CARD = 'get_treasures_card';
	const GET_DOORS_CARD = 'get_doors_card';
	const DISCARD_FROM_HAND = 'discard_from_hand';
	const DISCARD_FROM_PLAY = 'discard_from_play';
	const DISCARD_FROM_FIELD = 'discard_from_field';
	const SELL_CARDS = 'sell_cards';
	const TURN_CARD_OFF = 'turn_card_off';
	const TURN_CARD_ON = 'turn_card_on';
	const THROW_DICE = 'throw_dice';
	const END_MOVE = 'end_move';

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	public function init() {
		$action = $this->eventData['action'];
		$this->eventData['action'] = [];
		$this->add($action);
		return $this;
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	public function get() {
		return $this->eventData['action'];
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	public function add($action) {
		if (!is_array($this->eventData['action'])) $this->init();
		$this->eventData['action'][] = $action;
		return $this;
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	public function remove($action) {
		$key = array_search($action, $this->eventData['action']);
		unset($this->eventData['action'][$key]);
		return $this;
	}
}