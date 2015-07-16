<?php
namespace common\libs;

use common\models\CardsModel;

class GameInfo extends RulesData {

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	public function getInfo() {
		return [
			'data' => $this->gameData,
			'userInfo' => $this->getUserInfo()
		];
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	public function getUserInfo() {
		$cards = [];
		$userInfo = $this->game['users'][$this->eventData['user_id']];
		$data = $this->gameData;
		$userId = $this->eventData['user_id'];
		
		$info = ['class' => [], 'race' => [], 'gender' => $userInfo['gender'], 'lvl' => $userInfo['lvl'], 'count_big_items' => 0];
		if (isset($data['play_cards'][$userId]['doors'])) $cards = array_merge($cards, $data['play_cards'][$userId]['doors']);
		if (isset($data['play_cards'][$userId]['treasures'])) $cards = array_merge($cards, $data['play_cards'][$userId]['treasures']);

		foreach (CardsModel::getAll($cards) as $card) {
			switch ($card['parent']) {
				case 'classes':
					$info['class'][] = self::getCardName($card);
					break;
				case 'races':
					$info['race'][] = self::getCardName($card);
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
	public static function getCardName($card) {
		return explode('-', $card['id'])[0];
	}
}