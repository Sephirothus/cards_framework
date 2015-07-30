<?php
namespace common\libs;

use common\libs\Action;
use common\libs\Phases;
use common\models\CardsModel;
use common\models\GameDataModel;
use common\models\GameLogsModel;
use common\models\GamesModel;

class Rules extends RulesData {

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	public function __construct($gameId, $eventData) {
		$gameId = \common\helpers\IdHelper::toId($gameId);
		$this->cardInfo = [];
        $this->game = GamesModel::findOne(['_id' => $gameId])->toArray();
        $this->gameData = GameDataModel::findOne(['games_id' => $gameId])->toArray();
        if (!empty($eventData['card_id'])) $this->cardInfo = CardsModel::getCardInfo($event['card_id']);
        $this->eventData = $eventData;
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	public function check() {
		if ($this->game['status'] != GamesModel::$status['in_progress']) return $this->eventData;

		if (!$this->checkPhaseActions()) return false;
		if (!$this->checkCardRules()) return $this->eventData;

		if (isset($this->eventData['card_id']) && intval($this->eventData['card_id']) > 0) {
            $this->eventData['pic_id'] = CardsModel::findOne(['_id' => $this->eventData['card_id']])['id'];
        }

        // Actions loop
        $this->setObj('Action')->iterate();

        $this->getPhaseNext();

        //if ($isSave) {
        	$gameData = new GameDataModel;
            $gameData->setAttributes($this->gameData);
            $gameData->save();
        //}
        //GameLogsModel::add($data);
        return $this->eventData;
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	public function checkCardRules() {
		$data = $this->eventData;
		if (isset($data['card_id'])) {
			$obj = $this->setObj('CardRules');
			$this->eventData['rule'] = $obj->check();
			if ($obj->isForbidden) return false;
		}
		return true;
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	public function checkPhaseActions() {
		return Phases::check($this->gameData['cur_phase'], 
            $this->eventData['user_id'], 
            $this->eventData['action'], 
            $this->eventData['user_id'] == $this->gameData['cur_move'] ? true : false
        );
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	public function getPhaseNext() {
		if ($this->gameData['cur_phase'] != ($nextPhase = $this->setObj('Phases')->getNextPhase())) {
			if (is_array($nextPhase)) {
	            $this->gameData['cur_move'] = $nextPhase['next_user'];
	            $nextPhase['next_user'] = (string)$nextPhase['next_user'];
	            $this->eventData['next_user'][$nextPhase['next_user']] = Phases::getWaitActions($nextPhase['next_phase']);
	            $nextPhase = $nextPhase['next_phase'];
	        } else {
	            $this->eventData['next_user'][(string)$this->gameData['cur_move']] = Phases::getWaitActions($nextPhase);
	        }
	        $this->gameData['cur_phase'] = $nextPhase;
	        $this->eventData['next_phase'][$nextPhase] = Phases::getActions($nextPhase);
	        $isSave = true;
	        $this->gameData['temp_data']['in_battle']['end_move'] = [];
	        if ($nextPhase == 'get_boss_win') $this->eventData['lvl_up'] = $this->gameData['cur_move'];
	    }
	}
}