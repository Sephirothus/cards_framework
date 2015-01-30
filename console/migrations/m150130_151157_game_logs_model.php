<?php

class m150130_151157_game_logs_model extends \yii\mongodb\Migration
{
    public function up()
    {
    	$this->createCollection('game_logs');
    	
    }

    public function down()
    {
        $this->dropCollection('game_logs');
    }
}
