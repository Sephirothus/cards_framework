<?php
namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\base\Model;
use yii\mongodb\Query;

/**
 * Cards model
 */
class Cards extends Model {

	private $_cards = [];
	private $_decks = [];

	/**
     * Определение имени таблицы
     *
     * @return string название таблицы
     */
    public static function tableName() {
        return 'cards';
    }

	/**
	 * Получаем все карты
	 *
	 * @return void
	 * @author 
	 **/
	public function getCards() {
		$sorted = [];
		$this->_sortCardsArr((new Query)->from(self::tableName())->all(), $sorted);
		$sorted = $this->_reduceCardsLvls($sorted);
		$this->_decks = $this->_cards = $sorted;
		return $this;
	}

	/**
	 * Убираем лишние уровни вложенности
	 *
	 * @return void
	 * @author 
	 **/
	public function _reduceCardsLvls($cards) {
		$new = [];
		foreach ($cards as $type => $val) {
			foreach ($val as $subtype => $cards) {
				foreach ($cards as $key => $card) {
					$card['card_info'] = [
						'type' => $type,
						'subtype' => $subtype,
						'id' => $key
					];
					$new[$type][$key] = $card;
				}
			}
		}
		return $new;
	}

	/**
	 * Сортируем данные из базы
	 *
	 * @return void
	 * @author 
	 **/
	private function _sortCardsArr($data, &$new) {
		foreach ($data as $row) {
			if (isset($row['children'])) {
				$this->_sortCardsArr($row['children'], $new[$row['_id']]);
			} else {
				if (isset($row['cards_count'])) {
					for ($i=1; $i<=$row['cards_count']; $i++) {
						$new[$row['_id'].'-'.$i] = $row;	
					}
				} else $new[$row['_id']] = $row;
			}
		}
	}

	/**
	 * Тасуем карты (сохраняя ключи)
	 *
	 * @return void
	 * @author 
	 **/
	public function shuffleCards($deck=false) {
		$flag = false;
		if (!$deck) {
			$flag = true;
			$deck = $this->_decks;
		}
		$new = [];
		foreach ($deck as $type => $val) {
			foreach ($val as $ind => $card) {
				if (is_array($card)) {
					$keys = array_keys($val);
			        shuffle($keys);
			        foreach($keys as $key) {
			            $new[$type][$key] = $deck[$type][$key];
			        }
			    }
		    }
	    }
	    $deck = $new;
	    if ($flag) $this->_decks = $deck;
        return $deck;
	}

	/**
	 * Раздаем карты
	 *
	 * @param data ['название колоды' => 'сколько карт на руки из данной колоды'] 
	 * @author 
	 **/
	public function dealCards($data, $player_counts=1) {
		$cards = [];
		for ($i=1; $i<=$player_counts; $i++) {
			$cur = [];
			foreach ($this->_decks as $key => $val) {
				if (isset($data[$key]) && intval($data[$key]) > 0) {
					$cur[$key] = array_splice($this->_decks[$key], 0, $data[$key]);
				}
			}
			$cards[] = $cur;
		}
		$this->saveCurDecks();
		return $cards;
	}

	/**
	 * Получаем инфо о карте
	 *
	 * @return void
	 * @author 
	 **/
	public function getCardInfo($cardID) {
		return (new Query)->from(self::tableName())->where(['_id' => $cardID])->one();
	}

	/**
	 * Сохраняем текущее состояние колод
	 *
	 * @return void
	 * @author 
	 **/
	public function saveCurDecks() {

	}
}