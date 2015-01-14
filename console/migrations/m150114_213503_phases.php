<?php

class m150114_213503_phases extends \yii\mongodb\Migration
{
    public function up()
    {
    	$this->createCollection('phases');
    	$this->insert('phases', [
    		'_id' => 'main',
    		'children' => [
    			[
	    			'_id' => 0,
		    		'subphases' => [
		    			'from_hand_to_play', 'trade_items', 'sell_items'
		    		]
		    	],
		    	[
	    			'_id' => 1,
		    		'subphases' => [
		    			'monster_battle', 'curse', 'other_cards_from_deck'
		    		],
		    		'active_cards' => [
		    			'on' => [
		    				'types' => ['js_play_card', 'js_hand_card'],
		    				'exceptions' => [
		    					'js_hand_card' => []
		    				]
		    			],
		    		]
		    	],
		    	[
	    			'_id' => 2,
		    		'subphases' => [
		    			
		    		]
		    	],
		    	[
	    			'_id' => 3,
		    		'subphases' => [
		    			
		    		]
		    	],
		    	[
	    			'_id' => 4,
		    		'subphases' => [
		    			
		    		]
		    	],
    		]
    	]);
    	$this->insert('phases', [
    		'_id' => 'subphases',
    		'children' => [
    			[
		    		'_id' => 'from_hand_to_play',
		    		
		    	],
		    	[
		    		'_id' => 'trade_items',
		    		
		    	],
		    	[
		    		'_id' => 'sell_items',
		    		
		    	],
		    	[
		    		'_id' => 'monster_battle',
		    		'on' => ['monsters'],
		    		
		    	],
		    	[
		    		'_id' => 'curse',
		    		'on' => ['curses'],
		    		'permanent' => [''],
		    		'holder' => [
		    			'deck' => [],
		    			'player' => []
		    		]
		    	],
		    	[
		    		'_id' => 'other_cards_from_deck',
		    		'where_put' => ['play', 'hand']
		    	],
    		]
    	]);
    }

    public function down()
    {
        $this->dropCollection('phases');
    }
}
