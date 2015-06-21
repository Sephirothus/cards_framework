<?php

class m150113_145611_cards extends \yii\mongodb\Migration
{
    public function up()
    {
    	$doors = [
    		'_id' => 'doors',
    		'children' => [
    			[
    				'_id' => 'races',
    				'children' => [
    					[
    						'_id' => new \MongoId(),
    						'id' => 'dwarf',
    						'cards_count' => 3,
							'name' => 'Дварф',
							'bonus' => [
								'hand_cards' => 6,
								'big_cloth_amount' => 9999
							]
    					],
    					[
    						'_id' => new \MongoId(),
    						'id' => 'elf',
    						'cards_count' => 3,
							'name' => 'Эльф',
							'get_away' => 1,
							'condition' => [
								'when_use' => 'after_win_battle',
								'action' => 'get_lvl_for_help',
								'action_count' => 1
							]
    					],
	    				[
	    					'_id' => new \MongoId(),
	    					'id' => 'halfling',
							'cards_count' => 3,
							'name' => 'Халфлинг',
							'bonuses' => [
								'one_cloth_double_price' => [
									'phase' => 'trade_cards',
									'condition' => 'one_item_per_turn',
									'action' => 'item_price',
									'action_count' => '*2'
								],
								'after_false_get_away' => [
									'phase' => 'false_roll_dice',
									'action' => 'discard',
									'action_count' => 1,
									'action_result' => 'roll_dice'
								]
							]
						]
    				]
    			],
    			[
    				'_id' => 'classes',
    				'children' => [
						[
							'_id' => new \MongoId(),
							'id' => 'cleric',
							'cards_count' => 3,
							'name' => 'Клирик',
							'spells' => [
								'resurect' => [
									'phase' => 'take_treasures',
									'condition' => 'face_up_cards',
									'action' => 'replace_discards_with_yours_from_hand'
								],
								'expel' => [
									'phase' => 'monster_battle',
									'condition' => 'undead_monster',
									'action' => 'discard',
									'action_count' => '<=3',
									'action_result' => 'bonus_for_each_discard',
									'action_result_count' => 3
								]
							]
						],
						[
							'_id' => new \MongoId(),
							'id' => 'thief',
							'cards_count' => 3,
							'name' => 'Вор',
							'spells' => [
								'backstab' => [
									'phase' => 'monster_battle',
									'condition' => 'only_once_per_player',
									'actions' => [
										'choose_player' => 1,
										'discard' => 1
									],
									'action_result' => 'negative_bonus_to_player',
									'action_result_count' => -2
								],
								'theft' => [
									'phase' => 'cards_actions',
									'condition' => 'only_once_per_player',
									'actions' => [
										'choose_player' => 1,
										'discard' => 1,
										'roll_dice' => [
											'>=4' => 'get_small_item',
											'<4' => 'loose_1_lvl'
										]
									],
								]
							]
						],
						[
							'_id' => new \MongoId(),
							'id' => 'warrior',
							'cards_count' => 3,
							'name' => 'Воин',
							'bonuses' => [
								'draw' => 'win',
							],
							'spells' => [
								'enrage' => [
									'phase' => 'monster_battle',
									'action' => 'discard',
									'action_count' => '<=3',
									'action_result' => 'bonus',
									'action_result_count' => 1 
								],
							]
						],
						[
							'_id' => new \MongoId(),
							'id' => 'wizard',
							'cards_count' => 3,
							'name' => 'Волшебник',
							'spells' => [
								'fly_spell' => [
									'phase' => 'false_roll_dice',
									'action' => 'discard',
									'action_count' => '<=3',
									'action_result' => 'get_away',
									'action_result_count' => 1
								],
								'pacification' => [
									'phase' => 'monster_battle',
									'condition' => [
										'at_least_3_cards',
										'on_one_monster'
									],
									'action' => 'discard',
									'action_count' => 'all',
									'action_result' => 'only_treasures'
								]
							]
						],
					],
				],
				[
    				'_id' => 'curses',
    				'children' => [
						[
							'_id' => new \MongoId(),
							'id' => 'change_class',
							'name' => 'Смена класса',
							'discard' => [
								'classes' => 'all'
							],
							'auto_get_card' => [
								'from' => 'discard',
								'type' => 'classes'
							]
						],
						[
							'_id' => new \MongoId(),
							'id' => 'change_race',
							'name' => 'Смена расы',
							'discard' => [
								'races' => 'all'
							],
							'auto_get_card' => [
								'from' => 'discard',
								'type' => 'races'
							]
						],
						[
							'_id' => new \MongoId(),
							'id' => 'change_sex',
							'name' => 'Смена пола',
							//TODO
						],
						[
							'_id' => new \MongoId(),
							'id' => 'lose_class',
							'name' => 'Теряешь класс',
							'discard' => [
								'classes' => 1
							],
							'false_condition' => [
								'player_info' => [
									'lvl' => -1
								]
							]
						],
						[
							'_id' => new \MongoId(),
							'id' => 'lose_race',
							'name' => 'Теряешь расу',
							'discard' => [
								'races' => 'all'
							]
						],
						[
							'_id' => new \MongoId(),
							'id' => 'lose_1_big_item',
							'name' => 'Большая потеря',
							'discard' => [
								'card_opt' => [
									'size' => 'big'
								],
								'count' => 1
							]
						],
						[
							'_id' => new \MongoId(),
							'id' => 'lose_1_small_item',
							'cards_count' => 2,
							'name' => 'Невелика потеря',
							'discard' => [
								'card_opt' => [
									'size_not' => 'big'
								],
								'count' => 1
							]
						],
						[
							'_id' => new \MongoId(),
							'id' => 'lose_1_lvl',
							'cards_count' => 2,
						],
						[
							'_id' => new \MongoId(),
							'id' => 'lose_wear_armor'
						],
						[
							'_id' => new \MongoId(),
							'id' => 'lose_wear_foot'
						],
						[
							'_id' => new \MongoId(),
							'id' => 'lose_wear_head'
						],
						[
							'_id' => new \MongoId(),
							'id' => 'lose_2_cards'
						],
						[
							'_id' => new \MongoId(),
							'id' => 'chicken_on_head'
						],
						[
							'_id' => new \MongoId(),
							'id' => 'doom_duck'
						],
						[
							'_id' => new \MongoId(),
							'id' => 'tax'
						],
						[
							'_id' => new \MongoId(),
							'id' => 'malign_mirror'
						],
						[
							'_id' => new \MongoId(),
							'id' => 'obnoxious_curse'
						]
					]
				],
				[
    				'_id' => 'monsters',
    				'children' => [
    					[
    						'_id' => new \MongoId(),
    						'id' => 'crabs',
							'lvl' => 1,
							'treasures' => 1,
							'bad_stuff' => [
								'discard_by_parent' => ['armor', 'foot']
							]
						],
						[
							'_id' => new \MongoId(),
							'id' => 'drool_slime',
							'lvl' => 1,
							'treasures' => 1,
							'bonus' => ['type' => 'race', 'race' => 'elf', 'bonus' => 4],
							'bad_stuff' => [
								'or',
								'discard_by_parent' => ['foot'],
								'lost_lvl' => 1
							]
						],
						[
							'_id' => new \MongoId(),
							'id' => 'lame_goblin',
							'lvl' => 1,
							'treasures' => 1,
							'get_away' => 1,
							'bad_stuff' => [
								'lost_lvl' => 1
							]
						],
						[
							'_id' => new \MongoId(),
							'id' => 'maul_rat',
							'lvl' => 1,
							'treasures' => 1,
							'bonus' => ['type' => 'class', 'class' => 'cleric', 'bonus' => 3],
							'bad_stuff' => [
								'lost_lvl' => 1
							]
						],
						[
							'_id' => new \MongoId(),
							'id' => 'potted_plant',
							'lvl' => 1,
							'treasures' => 1,
							'bad_stuff' => [

							]
						],
						[
							'_id' => new \MongoId(),
							'id' => 'mr_bones',
							'lvl' => 2,
							'treasures' => 1,
							'bad_stuff' => [
								'lost_lvl' => 2
							]
						],
						[
							'_id' => new \MongoId(),
							'id' => 'large_angry_chick',
							'lvl' => 2,
							'treasures' => 1,
							'bad_stuff' => [
								'lost_lvl' => 1
							]
						],
						[
							'_id' => new \MongoId(),
							'id' => 'fly_frogs',
							'lvl' => 2,
							'treasures' => 1,
							'bad_stuff' => [
								'lost_lvl' => 2
							]
						],
						[
							'_id' => new \MongoId(),
							'id' => 'gelatine_octahedron',
							'lvl' => 2,
							'treasures' => 1,
							'bad_stuff' => [
								'discard_by_param' => [
									'size' => 'big',
									'count' => 'all'
								]
							]
						],
						[
							'_id' => new \MongoId(),
							'id' => 'pit_bull',
							'lvl' => 2,
							'treasures' => 1,
							'bad_stuff' => [
								'lost_lvl' => 2
							]
						],
						[
							'_id' => new \MongoId(),
							'id' => 'leperchaun',
							'lvl' => 4,
							'treasures' => 2,
							'bonus' => ['type' => 'race', 'race' => 'elf', 'bonus' => 5],
							'bad_stuff' => [
								
							]
						],
						[
							'_id' => new \MongoId(),
							'id' => 'harpies',
							'lvl' => 4,
							'treasures' => 2,
							'bonus' => ['type' => 'class', 'class' => 'wizard', 'bonus' => 5],
							'bad_stuff' => [
								'lost_lvl' => 2
							]
						],
						[
							'_id' => new \MongoId(),
							'id' => 'speed_snails',
							'lvl' => 4,
							'treasures' => 2,
							'get_away' => -2,
							'bad_stuff' => [
								
							]
						],
						[
							'_id' => new \MongoId(),
							'id' => 'undead_horse',
							'lvl' => 4,
							'treasures' => 2,
							'bonus' => ['type' => 'race', 'race' => 'dwarf', 'bonus' => 5],
							'bad_stuff' => [
								'lost_lvl' => 2
							]
						],
						[
							'_id' => new \MongoId(),
							'id' => 'pukachu',
							'lvl' => 6,
							'treasures' => 2,
							'bad_stuff' => [
								'discard_hand_cards' => 'all'
							]
						],
						[
							'_id' => new \MongoId(),
							'id' => 'shriek_geek',
							'lvl' => 6,
							'treasures' => 2,
							'bonus' => ['type' => 'class', 'class' => 'warrior', 'bonus' => 6],
							'bad_stuff' => [
								'discard_by_parent' => ['races', 'classes']
							]
						],
						[
							'_id' => new \MongoId(),
							'id' => 'lawyers',
							'lvl' => 6,
							'treasures' => 2,
							'bad_stuff' => [
								
							]
						],
						[
							'_id' => new \MongoId(),
							'id' => 'platycore',
							'lvl' => 6,
							'treasures' => 2,
							'bonus' => ['type' => 'class', 'class' => 'wizard', 'bonus' => 6],
							'bad_stuff' => [
								'or',
								'discard_hand_cards' => 'all',
								'lost_lvl' => 2
							]
						],
						[
							'_id' => new \MongoId(),
							'id' => 'ghoulfiends',
							'lvl' => 8,
							'treasures' => 2,
						],
						[
							'_id' => new \MongoId(),
							'id' => 'gazebo',
							'lvl' => 8,
							'treasures' => 2,
						],
						[
							'_id' => new \MongoId(),
							'id' => 'amazon',
							'lvl' => 8,
							'treasures' => 2,
						],
						[
							'_id' => new \MongoId(),
							'id' => 'face_sucker',
							'lvl' => 8,
							'treasures' => 2,
							'bonus' => ['type' => 'race', 'race' => 'elf', 'bonus' => 6]
						],
						[
							'_id' => new \MongoId(),
							'id' => 'float_nose',
							'lvl' => 10,
							'treasures' => 3,
						],
						[
							'_id' => new \MongoId(),
							'id' => 'orcs_3872',
							'lvl' => 10,
							'treasures' => 3,
							'bonus' => ['type' => 'race', 'race' => 'dwarf', 'bonus' => 6]
						],
						[
							'_id' => new \MongoId(),
							'id' => 'net_troll',
							'lvl' => 10,
							'treasures' => 3,
						],
						[
							'_id' => new \MongoId(),
							'id' => 'bigfoot',
							'lvl' => 12,
							'treasures' => 3,
							'bonus' => ['type' => 'race', 'race' => ['dwarf', 'halfling'], 'bonus' => 3]
						],
						[
							'_id' => new \MongoId(),
							'id' => 'tongue_demon',
							'lvl' => 12,
							'treasures' => 3,
							'bonus' => ['type' => 'class', 'class' => 'cleric', 'bonus' => 4]
						],
						[
							'_id' => new \MongoId(),
							'id' => 'wannabe_vampire',
							'lvl' => 12,
							'treasures' => 3,
						],
						[
							'_id' => new \MongoId(),
							'id' => 'unspeak_indescrib_horror',
							'lvl' => 14,
							'treasures' => 4,
							'bonus' => ['type' => 'class', 'class' => 'warrior', 'bonus' => 4]
						],
						[
							'_id' => new \MongoId(),
							'id' => 'stoned_golem',
							'lvl' => 14,
							'treasures' => 4,
						],
						[
							'_id' => new \MongoId(),
							'id' => 'insurance_salesman',
							'lvl' => 14,
							'treasures' => 4,
						],
						[
							'_id' => new \MongoId(),
							'id' => 'king_tut',
							'lvl' => 16,
							'treasures' => 4,
							'not_fight_lvl' => 3,
							'get_lvl' => 2
						],
						[
							'_id' => new \MongoId(),
							'id' => 'hippogriff',
							'lvl' => 16,
							'treasures' => 4,
							'not_fight_lvl' => 3,
							'get_lvl' => 2
						],
						[
							'_id' => new \MongoId(),
							'id' => 'wight_brothers',
							'lvl' => 16,
							'treasures' => 4,
							'not_fight_lvl' => 3,
							'get_lvl' => 2
						],
						[
							'_id' => new \MongoId(),
							'id' => 'squidzilla',
							'lvl' => 18,
							'treasures' => 4,
							'not_fight_lvl' => 4,
							'bonus' => ['type' => 'race', 'race' => 'elf', 'bonus_to_user' => -4],
							'get_lvl' => 2
						],
						[
							'_id' => new \MongoId(),
							'id' => 'bullrog',
							'lvl' => 18,
							'treasures' => 5,
							'not_fight_lvl' => 4,
							'get_lvl' => 2
						],
						[
							'_id' => new \MongoId(),
							'id' => 'plutonium_dragon',
							'lvl' => 20,
							'treasures' => 5,
							'not_fight_lvl' => 5,
							'get_lvl' => 2
						],
					]
				],
				[
    				'_id' => 'in_battle',
    				'children' => [
    					[
    						'_id' => new \MongoId(),
							'id' => 'help_me',
						],
						[
							'_id' => new \MongoId(),
							'id' => 'illusion',
						],
						[
							'_id' => new \MongoId(),
							'id' => 'mate',
						],
						[
							'_id' => new \MongoId(),
							'id' => 'out_lunch',
						],
    				]
    			],
    			[
    				'_id' => 'in_battle_monster_bonuses',
    				'children' => [
    					[
    						'_id' => new \MongoId(),
							'id' => 'ancient',
							'monster' => 10,
							'treasures' => 2
						],
						[
							'_id' => new \MongoId(),
							'id' => 'baby',
							'monster' => -5,
							'treasures' => -1	
						],
						[
							'_id' => new \MongoId(),
							'id' => 'enraged',
							'monster' => 5,
							'treasures' => 1
						],
						[
							'_id' => new \MongoId(),
							'id' => 'humongous',
							'monster' => 10,	
							'treasures' => 2
						],
						[
							'_id' => new \MongoId(),
							'id' => 'intelligent',
							'monster' => 5,
							'treasures' => 1
						],
					]
    			],
    			[
    				'_id' => 'other_doors',
    				'children' => [
    					[
    						'_id' => new \MongoId(),
    						'id' => 'cheat',
							'condition' => [
								'place_card' => ['head','armor','foot','arms','items']
							]
						],
						[
							'_id' => new \MongoId(),
							'id' => 'divine_intervent',
							'instant' => true
						],
						[
							'_id' => new \MongoId(),
							'id' => 'half_breed',
							'cards_count' => 2,
						],
						[
							'_id' => new \MongoId(),
							'id' => 'super_munchkin',
							'cards_count' => 2,
						],
						[
							'_id' => new \MongoId(),
							'id' => 'wander_monster',
							'cards_count' => 3,
						],
					]
				]
    		]
    	];

    	$treasures = [
    		'_id' => 'treasures',
    		'children' => [
    			[
    				'_id' => 'head',
    				'children' => [
						[
							'_id' => new \MongoId(),
							'id' => 'bad_ass_bandanna',
							'price' => 400,
							'bonus' => 3,
							'name' => 'Сорвиголовная Бандана',
							'race_type' => 'human',
						],
						[
							'_id' => new \MongoId(),
							'id' => 'courage_helm',
							'price' => 200,
							'bonus' => 1,
							'name' => 'Шлем Бесстрашия',
						],
						[
							'_id' => new \MongoId(),
							'id' => 'horny_helmet',
							'price' => 600,
							'bonus' => 1,
							'name' => 'Шлем-Рогач',
							'special_bonus' => [3, 'elf']
						],
						[
							'_id' => new \MongoId(),
							'id' => 'power_pointy_hat',
							'price' => 400,
							'bonus' => 3,
							'name' => 'Остроконечная Шляпа Могущества',
							'class_type' => 'wizard',
						],
					]
				],
				[
					'_id' => 'armor',
    				'children' => [
						[
							'_id' => new \MongoId(),
							'id' => 'flaming_armor',
							'price' => 400,
							'bonus' => 2,
							'name' => 'Палёные Доспехи',
						],
						[
							'_id' => new \MongoId(),
							'id' => 'leather_armor',
							'price' => 200,
							'bonus' => 1,
							'name' => 'Кожаный Прикид',
						],
						[
							'_id' => new \MongoId(),
							'id' => 'mithril_armor',
							'price' => 600,
							'bonus' => 3,
							'name' => 'Мифрильный Доспех',
							'class_type_not' => 'wizard',
							'size' => 'big'
						],
						[
							'_id' => new \MongoId(),
							'id' => 'short_wide_armor',
							'price' => 400,
							'bonus' => 3,
							'name' => 'Доспехи Поперёк-Себя-Шире',
							'race_type' => 'dwarf'
						],
						[
							'_id' => new \MongoId(),
							'id' => 'slimy_armor',
							'price' => 200,
							'bonus' => 1,
							'name' => 'Слизистая Оболочка',
						],
					]
				],
				[
					'_id' => 'foot',
    				'children' => [
						[
							'_id' => new \MongoId(),
							'id' => 'protect_sandals',
							'price' => 700,
							'name' => 'Сандалеты-Протекторы',
							'condition' => [
								'off' => 'doors_curses'
							]
						],
						[
							'_id' => new \MongoId(),
							'id' => 'butt_kick_boots',
							'price' => 400,
							'bonus' => 2,
							'name' => 'Башмаки Могучего Пенделя',
						],
						[
							'_id' => new \MongoId(),
							'id' => 'run_fast_boots',
							'price' => 400,
							'name' => 'Башмаки Реально Быстрого Бега',
							'get_away' => 2
						],
					]
				],
				[
					'_id' => 'arms',
    				'children' => [
						[
							'_id' => new \MongoId(),
							'id' => 'charm_tuba',
							'price' => 300,
							'size' => 'big',
							'type' => 'one_hand',
							'name' => 'Чарующая Дуда',
							'get_away' => 3,
							'condition' => [
								'after_true_get_away' => [
									'treasures' => 1,
									'treasure_get' => 'close'
								]
							]
						],
						[
							'_id' => new \MongoId(),
							'id' => 'bow_ribbons',
							'price' => 800,
							'race_type' => 'elf',
							'bonus' => 4,
							'type' => 'two_hand',
							'name' => 'Лук с Ленточками'
						],
						[
							'_id' => new \MongoId(),
							'id' => 'broad_sword',
							'price' => 400,
							'sex_type' => 'Баба',
							'bonus' => 3,
							'type' => 'one_hand',
							'name' => 'Меч Широты Взглядов'
						],
						[
							'_id' => new \MongoId(),
							'id' => 'swash_buckler',
							'price' => 400,
							'bonus' => 2,
							'type' => 'one_hand',
							'name' => 'Пафосный Баклер'
						],
						[
							'_id' => new \MongoId(),
							'id' => 'chainsaw_blood_dismember',
							'price' => 600,
							'size' => 'big',
							'bonus' => 3,
							'type' => 'two_hand',
							'name' => 'Бензопила Кровавого Расчленения'
						],
						[
							'_id' => new \MongoId(),
							'id' => 'peace_cheese_grater',
							'price' => 400,
							'bonus' => 3,
							'class_type' => 'cleric',
							'type' => 'one_hand',
							'name' => 'Тёрка Умиротворения'
						],
						[
							'_id' => new \MongoId(),
							'id' => 'treachery_dagger',
							'price' => 400,
							'bonus' => 3,
							'class_type' => 'thief',
							'type' => 'one_hand',
							'name' => 'Кинжал Измены'
						],
						[
							'_id' => new \MongoId(),
							'id' => 'eleven_foot_pole',
							'price' => 200,
							'bonus' => 1,
							'type' => 'two_hand',
							'name' => 'Одиннадцатифутовый Кий'
						],
						[
							'_id' => new \MongoId(),
							'id' => 'gentleman_club',
							'price' => 400,
							'bonus' => 3,
							'sex_type' => 'Мужик',
							'type' => 'one_hand',
							'name' => 'Дуб Джентельменов'
						],
						[
							'_id' => new \MongoId(),
							'id' => 'kneecap_hammer',
							'price' => 600,
							'bonus' => 4,
							'race_type' => 'dwarf',
							'type' => 'one_hand',
							'name' => 'Коленеотбойный Молоточек'
						],
						[
							'_id' => new \MongoId(),
							'id' => 'huge_rock',
							'price' => 0,
							'bonus' => 3,
							'size' => 'big',
							'type' => 'two_hand',
							'name' => 'Огромная Каменюга'
						],
						[
							'_id' => new \MongoId(),
							'id' => 'sharp_mace',
							'price' => 600,
							'bonus' => 4,
							'class_type' => 'cleric',
							'type' => 'one_hand',
							'name' => 'Булава Остроконечности'
						],
						[
							'_id' => new \MongoId(),
							'id' => 'unfair_rapier',
							'price' => 600,
							'bonus' => 3,
							'race_type' => 'elf',
							'type' => 'one_hand',
							'name' => 'Рапира Такнечестности'
						],
						[
							'_id' => new \MongoId(),
							'id' => 'stick_rat',
							'price' => 0,
							'bonus' => 1,
							'type' => 'one_hand',
							'name' => 'Крыска на Палочке',
							'condition' => [
								'discard' => 'auto_get_away',
								'monsters' => '<9'
							]
						],
						[
							'_id' => new \MongoId(),
							'id' => 'ubiquity_shield',
							'price' => 600,
							'bonus' => 4,
							'class_type' => 'warrior',
							'size' => 'big',
							'type' => 'one_hand',
							'name' => 'Совсехсторонний Щит'
						],
						[
							'_id' => new \MongoId(),
							'id' => 'sneaky_bastard_sword',
							'price' => 400,
							'bonus' => 2,
							'type' => 'one_hand',
							'name' => 'Меч Коварного Бастарда'
						],
						[
							'_id' => new \MongoId(),
							'id' => 'napalm_staff',
							'price' => 800,
							'bonus' => 5,
							'class_type' => 'wizard',
							'type' => 'one_hand',
							'name' => 'Посох Напалма'
						],
						[
							'_id' => new \MongoId(),
							'id' => 'swiss_army_polearm',
							'price' => 600,
							'bonus' => 4,
							'race_type' => 'human',
							'type' => 'two_hand',
							'size' => 'big',
							'name' => 'Швейцарская Армейская Алебарда'
						],
					]
				],
				[
					'_id' => 'items',
    				'children' => [
						[
							'_id' => new \MongoId(),
							'id' => 'obscurity_cloak',
							'price' => 600,
							'bonus' => 4,
							'name' => 'Плащ Замутнения',
							'class_type' => 'thief'
						],
						[
							'_id' => new \MongoId(),
							'id' => 'limburger_and_anchovy_sandwich',
							'price' => 400,
							'bonus' => 3,
							'name' => 'Сэндвич Запоздалого Прозрения С Сыром и Селедкой',
							'race_type' => 'halfling'
						],
						[
							'_id' => new \MongoId(),
							'id' => 'giant_strength_pantyhose',
							'price' => 600,
							'bonus' => 3,
							'name' => 'Колготки Великанской Силы',
							'class_type_not' => 'warrior'
						],
						[
							'_id' => new \MongoId(),
							'id' => 'impressive_title',
							'bonus' => 3,
							'name' => 'Реально Конкретный Титул',
						],
						[
							'_id' => new \MongoId(),
							'id' => 'singing_dancing_sword',
							'price' => 600,
							'bonus' => 2,
							'name' => 'Меч Песни и Пляски',
							'class_type_not' => 'thief'
						],
						[
							'_id' => new \MongoId(),
							'id' => 'spiky_knees',
							'price' => 200,
							'bonus' => 1,
							'name' => 'Острые Коленки'
						],
						[
							'_id' => new \MongoId(),
							'id' => 'stepladder',
							'price' => 400,
							'bonus' => 3,
							'size' => 'big',
							'name' => 'Боевая Стремянка',
							'race_type' => 'halfling'
						],
						[
							'_id' => new \MongoId(),
							'id' => 'allure_kneepads',
							'price' => 600,
							'name' => 'Наколенники Развода',
							'class_type_not' => 'cleric',
							'action' => 'if_player_higher_lvl_accept_help_without_treasures',
							'condition' => 'not_get_win_lvl'
						],
					]
				],
				[
					'_id' => 'get_level',
    				'children' => [
						[
							'_id' => new \MongoId(),
							'id' => 'go_up_lvl',
							'name' => 'Получи Уровень'
						],
						[
							'_id' => new \MongoId(),
							'id' => 'thousand_gold_pieces',
							'name' => '1000 голдов'
						],
						[
							'_id' => new \MongoId(),
							'id' => 'anthill_boil',
							'name' => 'Кипяток В Муравейнике'
						],
						[
							'_id' => new \MongoId(),
							'id' => 'food_bribe_gm',
							'name' => 'Прикорми Мастера'
						],
						[
							'_id' => new \MongoId(),
							'id' => 'convenient_addition_error',
							'name' => 'Выгодная Ошибка При Сложении'
						],
						[
							'_id' => new \MongoId(),
							'id' => 'invoke_obscure_rules',
							'name' => 'Используй Непонятное Правило'
						],
						[
							'_id' => new \MongoId(),
							'id' => 'kill_hireling',
							'name' => 'Рассчитайся С Наёмником',
							'condition' => 'if_hireling_in_game',
							'action' => 'hireling_discard'
						],
						[
							'_id' => new \MongoId(),
							'id' => 'mutilate_bodies',
							'name' => 'Поглумись Над Телами Врагов',
							'use_type' => 'after_any_battle'
						],
						[
							'_id' => new \MongoId(),
							'id' => 'general_studliness_potion',
							'name' => 'Зелье Крутизны'
						],
						[
							'_id' => new \MongoId(),
							'id' => 'gm_whine',
							'name' => 'Разжалоби Мастера',
							'condition' => 'not_highest_lvl'
						],
					]
				],
				[
					'_id' => 'disposables',
    				'children' => [
						[
							'_id' => new \MongoId(),
							'id' => 'yuppie_water',
							'price' => 100,
							'name' => 'Яппиток',
							'use_type' => 'any_battle',
							'condition' => [
								'bonus' => 2,
								'races' => 'elf',
								'how_many' => 'all'
							]
						],
						[
							'_id' => new \MongoId(),
							'id' => 'ponfusion_cotion',
							'price' => 100,
							'bonus' => 3,
							'name' => 'Пелье Зутаницы',
							'when_type' => 'any_battle',
							'which_side' => 'any'
						],
						[
							'_id' => new \MongoId(),
							'id' => 'electric_radioactive_acid_potion',
							'price' => 200,
							'bonus' => 5,
							'name' => 'Радиоактивно-Электрокислотное Зелье',
							'use_type' => 'any_battle',
							'which_side' => 'any'
						],
						[
							'_id' => new \MongoId(),
							'id' => 'flaming_poison_potion',
							'price' => 100,
							'bonus' => 3,
							'name' => 'Зелье Пламенной Отравы',
							'use_type' => 'any_battle',
							'which_side' => 'any'
						],
						[
							'_id' => new \MongoId(),
							'id' => 'freeze_explosive_potion',
							'price' => 100,
							'bonus' => 3,
							'name' => 'Замораживающее Взрывное Зелье',
							'use_type' => 'any_battle',
							'which_side' => 'any'
						],
						[
							'_id' => new \MongoId(),
							'id' => 'magic_missile',
							'price' => 300,
							'bonus' => 5,
							'name' => 'Магическая Ракета',
							'use_type' => 'any_battle',
							'which_side' => 'any'
						],
						[
							'_id' => new \MongoId(),
							'id' => 'nasty_taste_sport_drink',
							'price' => 200,
							'bonus' => 2,
							'name' => 'Питьё Противно-Спортивное',
							'use_type' => 'any_battle',
							'which_side' => 'any'
						],
						[
							'_id' => new \MongoId(),
							'id' => 'halitos_potion',
							'price' => 100,
							'bonus' => ['bonus' => 2, 'kill' => 'float_nose'],
							'name' => 'Зелье Ротовой Вони',
							'use_type' => 'any_battle',
							'which_side' => 'any'
						],
						[
							'_id' => new \MongoId(),
							'id' => 'idiot_brave_potion',
							'price' => 100,
							'bonus' => 2,
							'name' => 'Зелье Идиотской Храбрости',
							'use_type' => 'any_battle',
							'which_side' => 'any'
						],
						[
							'_id' => new \MongoId(),
							'id' => 'pretty_balloons',
							'price' => 0,
							'bonus' => 5,
							'name' => 'Клёвые Шарики',
							'use_type' => 'any_battle',
							'which_side' => 'any'
						],
						[
							'_id' => new \MongoId(),
							'id' => 'sleep_potion',
							'price' => 100,
							'bonus' => 2,
							'name' => 'Снотворное Зелье',
							'use_type' => 'any_battle',
							'which_side' => 'any'
						],
						[
							'_id' => new \MongoId(),
							'id' => 'doppleganger',
							'price' => 300,
							'bonus' => '*2',
							'name' => 'Дупельгангер',
							'use_type' => 'self_battle',
							'which_side' => 'self'
						],
						[
							'_id' => new \MongoId(),
							'id' => 'glue_flask',
							'price' => 100,
							'name' => 'Тюбик Клея',
							'use_type' => 'after_true_get_away',
							'action' => 'another_die_roll'
						],
						[
							'_id' => new \MongoId(),
							'id' => 'friendship_potion',
							'price' => 200,
							'name' => 'Зелье Дружбы',
							'use_type' => 'any_battle',
							'action' => 'all_monsters_discard',
							'treasures' => 'no_one_gets',
							'cur_player_action' => 'looting'
						],
						[
							'_id' => new \MongoId(),
							'id' => 'instant_wall',
							'price' => 300,
							'name' => 'Стенка-Встанька',
							'use_type' => 'after_any_battle',
							'action' => 'auto_get_away',
							'action_count' => 2
						],
						[
							'_id' => new \MongoId(),
							'id' => 'invisibility_potion',
							'price' => 200,
							'name' => 'Зелье Невидимости',
							'use_type' => 'after_false_get_away',
							'action' => 'auto_get_away',
							'action_count' => 1
						],
						[
							'_id' => new \MongoId(),
							'id' => 'loaded_die',
							'price' => 300,
							'name' => 'Читерский Кубик',
							'use_type' => 'after_die_roll',
							'action' => 'select_die_number'
						],
						[
							'_id' => new \MongoId(),
							'id' => 'magic_lamp',
							'price' => 500,
							'name' => 'Волшебная Лампа',
							'use_type' => 'on_self_move',
							'action' => 'one_monster_discard_any_time',
							'condition' => 'if_one_monster_then_get_treasures_but_no_lvl'
						],
						[
							'_id' => new \MongoId(),
							'id' => 'pollymorph_potion',
							'price' => 1300,
							'name' => 'Зелье Попуморфа',
							'use_type' => 'any_battle',
							'action' => 'one_monster_discard'
						],
						[
							'_id' => new \MongoId(),
							'id' => 'transferral_potion',
							'price' => 300,
							'name' => 'Зелье Стрелочника',
							'use_type' => 'any_battle',
							'action' => 'chosen_player_battles_monster',
							'after_action' => 'cur_player_can_loot'
						],
						[
							'_id' => new \MongoId(),
							'id' => 'dowsing_wand',
							'price' => 1100,
							'name' => 'Штырь Лозоходца',
							'use_type' => 'on_move',
							'action' => 'choose_one_card_from_discard'
						],
						[
							'_id' => new \MongoId(),
							'id' => 'wishing_ring',
							'cards_count' => 2,
							'price' => 500,
							'name' => 'Хотельное Кольцо',
							'use_type' => 'any_time',
							'condition' => [
								'off' => [
									'curses' => 1
								]
							]
						],
					]
				],
				[
					'_id' => 'hirelings',
    				'children' => [
						[
							'_id' => new \MongoId(),
							'id' => 'hireling',
							'cards_count' => 1,
							'bonus' => 1,
							'name' => 'Наёмничек',
							'action' => 'one_additional_cloth',
							'on_discard' => ['lose_caried_cloth', 'auto_get_away']
						]
					]
				],
				[
					'_id' => 'other_treasures',
    				'children' => [
						[
							'_id' => new \MongoId(),
							'id' => 'steal_lvl',
							'action' => 'choose_player_and_steal_lvl',
							'name' => 'Укради Уровень'
						],
						[
							'_id' => new \MongoId(),
							'id' => 'q_dice',
							'name' => 'Q-Кубик',
							'price' => 1000
						],
						[
							'_id' => new \MongoId(),
							'id' => 'hoard',
							'name' => 'Ура, Клад!',
							'treasures' => 3,
							'instant' => true,
							'condition' => 'if_card_face_down_treasures_same'
						],
					]
				]
    		]
		];

		$cards = [];
		$key = 1;
    	$this->_multipleCards(array_merge([$doors], [$treasures]), $cards, 0, $key);

		$this->createCollection('cards');
		foreach ($cards as $card) {
			$this->insert('cards', $card);
		}
    }

    public function down()
    {
        $this->dropCollection('cards');
    }

    /**
     * undocumented function
     *
     * @return void
     * @author 
     **/
    private function _multipleCards($cards, &$newData, $parent, &$key) {
    	foreach ($cards as &$card) {
    		if (isset($card['children'])) {
    			$card['parent'] = $parent;
    			$card['left'] = $key++;
	    		$this->_multipleCards($card['children'], $newData, $card['_id'], $key);
	    		$card['right'] = $key++;
	    	} else {
	    		$card['parent'] = $parent;
	    		$card['left'] = $key++;
	    		$card['right'] = $key++;
	    		if (isset($card['cards_count'])) {
	    			for ($i=2; $i<=$card['cards_count']; $i++) {
		    			$temp = $card;
		    			$temp['_id'] = new \MongoId();
		    			$temp['id'] = $temp['id'].'-'.$i;
		    			unset($temp['cards_count']);
		    			$cards[] = $temp;
		    		}
		    		$card['id'] = $card['id'].'-1';
		    		unset($card['cards_count']);
	    		}
	    	}
	    	unset($card['children']);
	    	$newData[] = $card;
    	}
    }
}
