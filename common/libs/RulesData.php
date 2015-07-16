<?php
namespace common\libs;

use common\models\GameDataModel;
use common\models\GamesModel;

class RulesData {

	public $game;
	public $gameData;
	public $eventData;

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	public function setObj($name) {
		$name = "\common\libs\\" . $name;
		$obj = new $name;
		$obj->game =& $this->game;
        $obj->gameData =& $this->gameData;
        $obj->eventData =& $this->eventData;
        return $obj;
	}
}