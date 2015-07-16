<?php
namespace common\libs;

use common\models\GameDataModel;
use common\models\GamesModel;
use common\models\CardsModel;
use common\models\GameLogsModel;

class Rules extends RulesData {

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	public function __construct($gameId, $eventData) {
		$gameId = \common\helpers\IdHelper::toId($gameId);
        $this->game = GamesModel::findOne(['_id' => $gameId])->toArray();
        $this->gameData = GameDataModel::findOne(['games_id' => $gameId])->toArray();
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

        $this->getPhaseNext();

        GameLogsModel::add($this->eventData['pic_id']);
        // save gamedata
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
			$rule = $obj->check();
			
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
		if ($temp['cur_phase'] != ($nextPhase = $this->setObj('Phases')->getNextPhase())) {
			if (is_array($nextPhase)) {
	            $temp['cur_move'] = $nextPhase['next_user'];
	            $nextPhase['next_user'] = (string)$nextPhase['next_user'];
	            $event['next_user'][$nextPhase['next_user']] = Phases::getWaitActions($nextPhase['next_phase']);
	            $nextPhase = $nextPhase['next_phase'];
	        } else {
	            $event['next_user'][(string)$temp['cur_move']] = Phases::getWaitActions($nextPhase);
	        }
	        $temp['cur_phase'] = $nextPhase;
	        $event['next_phase'][$nextPhase] = Phases::getActions($nextPhase);
	        $isSave = true;
	        $temp['temp_data']['in_battle']['end_move'] = [];
	        if ($nextPhase == 'get_boss_win') $event['lvl_up'] = $temp['cur_move'];
	    }
	}
}