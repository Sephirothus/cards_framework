<?php
namespace common\libs;

use common\models\GameDataModel;
use common\models\GamesModel;

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

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	public function iterate() {
		foreach ($this->eventData['action'] as $action) {
			switch ($action) {
	            case self::FROM_HAND_TO_PLAY:
	                $type = GameDataModel::findCardType($this->gameData['hand_cards'][$this->eventData['user_id']], $this->eventData['card_id']);
	                unset($this->gameData['hand_cards'][$this->eventData['user_id']][$type['type']][$type['index']]);
	                $this->gameData['play_cards'][$this->eventData['user_id']][$type['type']][] = $this->eventData['card_id'];
	                $isSave = true;
	                break;
	            case self::FROM_PLAY_TO_FIELD:
	                $type = GameDataModel::findCardType($this->gameData['play_cards'][$this->eventData['user_id']], $this->eventData['card_id']);
	                unset($this->gameData['play_cards'][$this->eventData['user_id']][$type['type']][$type['index']]);
	                $this->gameData['field_cards'][$type['type']][] = $this->eventData['card_id'];
	                $isSave = true;
	                if (!empty($this->eventData['additional']) && $this->cardInfo['parent'] == 'disposables' && !empty($this->cardInfo['bonus'])) {
	                    $this->gameData['temp_data']['in_battle']['bonuses'][$this->eventData['card_id']] = $this->eventData['additional']['bonus_on'];
	                }
	                break;
	            case self::FROM_HAND_TO_FIELD:
	                $type = GameDataModel::findCardType($this->gameData['hand_cards'][$this->eventData['user_id']], $this->eventData['card_id']);
	                unset($this->gameData['hand_cards'][$this->eventData['user_id']][$type['type']][$type['index']]);
	                $this->gameData['field_cards'][$type['type']][] = $this->eventData['card_id'];
	                $isSave = true;
	                if (!empty($this->eventData['additional']) && $this->cardInfo['parent'] == 'disposables' && !empty($this->cardInfo['bonus'])) {
	                    $this->gameData['temp_data']['in_battle']['bonuses'][$this->eventData['card_id']] = $this->eventData['additional']['bonus_on'];
	                }
	                break;
	            case self::FROM_FIELD_TO_HAND:
	                $type = GameDataModel::findCardType($this->gameData['field_cards'], $this->eventData['card_id']);
	                unset($this->gameData['field_cards'][$type['type']][$type['index']]);
	                $this->gameData['hand_cards'][$this->eventData['user_id']][$type['type']][] = $this->eventData['card_id'];
	                $this->eventData['card_type'] = $type['type'];
	                $isSave = true;
	                break;
	            case self::GET_TREASURES_CARD:
	            case self::GET_DOORS_CARD:
	                switch ($action) {
	                    case self::GET_DOORS_CARD:
	                        if ($this->gameData['cur_phase'] == 'place_cards') {
	                            $userId = false;
	                            $toType = 'field_cards';
	                            if ($this->eventData['action'] == $action) $this->eventData['action'] = 'open_door';
	                            $action = self::OPEN_DOOR;
	                        } else {
	                            $userId = $data['user_id'];
	                            $toType = 'hand_cards';
	                        }
	                        $this->eventData['card_id'] = (new CardsModel)->dealOneByType($this->game['_id'], $data['card_type'], $userId, $toType);
	                        break;
	                    case self::GET_TREASURES_CARD:
	                        $this->eventData['card_id'] = (new CardsModel)->dealOneByType($this->game['_id'], $data['card_type'], $data['user_id'], 'hand_cards');
	                        break;
	                }
	                $card = CardsModel::getCardInfo($this->eventData['card_id']);
	                $this->eventData['pic_id'] = $card['id'];
	                $this->eventData['card_info'] = $card;
	                break;
	            case self::DISCARD_FROM_HAND:
	            case self::DISCARD_FROM_PLAY:
	            case self::DISCARD_FROM_FIELD:
	                switch ($action) {
	                    case self::DISCARD_FROM_HAND:
	                        $place = 'hand_cards';
	                        break;
	                    case self::DISCARD_FROM_PLAY:
	                        $place = 'play_cards';
	                        break;
	                    case self::DISCARD_FROM_FIELD:
	                        $place = 'field_cards';
	                        break;
	                }
	                if ($action == self::DISCARD_FROM_FIELD) {
	                    $type = GameDataModel::findCardType($this->gameData[$place], $this->eventData['card_id']);
	                    unset($this->gameData[$place][$type['type']][$type['index']]);
	                } else {
	                    $type = GameDataModel::findCardType($this->gameData[$place][$this->eventData['user_id']], $this->eventData['card_id']);
	                    unset($this->gameData[$place][$this->eventData['user_id']][$type['type']][$type['index']]);
	                    if ($cardInfo['parent'] == 'get_level') {
	                        $lvl = $game['users'][$this->eventData['user_id']]['lvl'];
	                        GamesModel::changeUserInfo($this->eventData['user_id'], $this->game['_id'], ['lvl' => ++$lvl]);
	                        $this->eventData['lvl_up'] = $this->eventData['user_id'];
	                    }
	                }
	                if (is_array($this->gameData['turn_cards']) && in_array($this->eventData['card_id'], $this->gameData['turn_cards'])) unset($this->gameData['turn_cards'][array_search($this->eventData['card_id'], $this->gameData['turn_cards'])]);
	                $this->gameData['discards'][$type['type']][] = $this->eventData['card_id'];
	                $isSave = true;
	                break;
	            case self::SELL_CARDS:
	                /*foreach ($this->eventData['card_id'] as $card) {
	                    $type = GameDataModel::findCardType($this->gameData[''][$this->eventData['user_id']], $card);
	                    unset($this->gameData[$place][$this->eventData['user_id']][$type['type']][$type['index']]);
	                    $this->gameData['discards'][$type['type']][] = $card;
	                }
	                $this->eventData['user_lvl'] = 2;
	                $isSave = true;*/
	                break;
	            case self::TURN_CARD_OFF:
	            case self::TURN_CARD_ON:
	                if (isset($this->gameData['turn_cards']) && in_array($this->eventData['card_id'], $this->gameData['turn_cards'])) unset($this->gameData['turn_cards'][array_search($this->eventData['card_id'], $this->gameData['turn_cards'])]);
	                else $this->gameData['turn_cards'][] = $this->eventData['card_id'];
	                $isSave = true;
	                break;
	            case self::THROW_DICE:
	                $this->gameData['temp_data']['cur_dice'] = $this->eventData['dice'] = rand(1, 6);
	                $isSave = true;
	                break;
	            case self::END_MOVE:
	                if ($this->gameData['cur_phase'] == 'get_boss') {
	                    if (!isset($this->gameData['temp_data']['in_battle']['end_move'])) $this->gameData['temp_data']['in_battle']['end_move'] = [];
	                    if (!in_array($this->eventData['user_id'], $this->gameData['temp_data']['in_battle']['end_move'])) {
	                        $this->gameData['temp_data']['in_battle']['end_move'][] = $this->eventData['user_id'];
	                        $isSave = true;
	                    }
	                }
	                break;
	        }
		}
	}
}