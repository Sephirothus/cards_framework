<?php

use yii\db\Schema;
use yii\db\Migration;

class m141226_114418_new_table_quests extends Migration
{
    public function safeUp()
    {
    	$this->createTable('users_quests', [
    		'id' => Schema::TYPE_PK,
            'user_id' => Schema::TYPE_INTEGER . ' NOT NULL DEFAULT 0',
            'quest_templates_id' => Schema::TYPE_INTEGER . ' NOT NULL',
            'count' => Schema::TYPE_INTEGER . ' NOT NULL',
            'action' => Schema::TYPE_STRING . '(32) NOT NULL',
            'target' => Schema::TYPE_STRING . '(32) NOT NULL',
            'target_id' => Schema::TYPE_INTEGER . ' NOT NULL',
            'holder' => Schema::TYPE_STRING . '(32) NOT NULL',
            'holder_id' => Schema::TYPE_INTEGER . ' NOT NULL',
            'pre_text_id' => Schema::TYPE_INTEGER . ' NOT NULL',
            'after_text_id' => Schema::TYPE_INTEGER . ' NOT NULL',
    	], 'ENGINE=InnoDB');

        $this->createTable('texts', [
            'id' => Schema::TYPE_PK,
            'talker_id' => Schema::TYPE_INTEGER . ' NOT NULL DEFAULT 0',
            'listener_id' => Schema::TYPE_INTEGER . ' NOT NULL DEFAULT 0',
            'text' => Schema::TYPE_STRING . '(32) NOT NULL',
            'parent_id' => Schema::TYPE_INTEGER . ' NOT NULL',
            'left_key' => Schema::TYPE_INTEGER . ' NOT NULL',
            'right_key' => Schema::TYPE_INTEGER . ' NOT NULL',
            'chain_to' => Schema::TYPE_STRING . '(32) NOT NULL',
            'chain_to_id' => Schema::TYPE_INTEGER . ' NOT NULL',
        ], 'ENGINE=InnoDB');
    	
    	/*$this->createTable('quest_templates', [
    		'id' => Schema::TYPE_PK,
            'user_id' => Schema::TYPE_INTEGER . ' NOT NULL',
            
    	], 'ENGINE=InnoDB');*/
    }

    public function down()
    {
        $this->dropTable('users_quests');
        $this->dropTable('texts');
        //$this->dropTable('quest_templates');
    }
}
