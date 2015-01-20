<?php

class m150113_145611_cards extends \yii\mongodb\Migration
{
    public function up()
    {
    	$this->createCollection('cards');
    	$this->insert('cards', [
    		'_id' => 'doors',
    		'children' => [
    			[
    				'_id' => 'races',
    				'children' => [
    					[
    						'_id' => 'dwarf',
    						'cards_count' => 3,
							'name' => 'Дварф',
							'bonus' => [
								'hand_cards' => 6,
								'big_cloth_amount' => 9999
							]
    					],
    					[
    						'_id' => 'elf',
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
	    					'_id' => 'halfling',
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
							'_id' => 'cleric',
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
							'_id' => 'thief',
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
							'_id' => 'warrior',
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
							'_id' => 'wizard',
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
							'_id' => 'change_class',
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
							'_id' => 'change_race',
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
							'_id' => 'change_sex',
							'name' => 'Смена пола',
							//TODO
						],
						[
							'_id' => 'lose_class',
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
							'_id' => 'lose_race',
							'name' => 'Теряешь расу',
							'discard' => [
								'races' => 'all'
							]
						],
						[
							'_id' => 'lose_1_big_item',
							'name' => 'Большая потеря',
							'discard' => [
								'card_opt' => [
									'size' => 'big'
								],
								'count' => 1
							]
						],
						[
							'_id' => 'lose_1_small_item',
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
							'_id' => 'lose_1_lvl',
							'cards_count' => 2,
						],
						[
							'_id' => 'lose_wear_armor'
						],
						[
							'_id' => 'lose_wear_foot'
						],
						[
							'_id' => 'lose_wear_head'
						],
						[
							'_id' => 'lose_2_cards'
						],
						[
							'_id' => 'chicken_on_head'
						],
						[
							'_id' => 'doom_duck'
						],
						[
							'_id' => 'tax'
						],
						[
							'_id' => 'malign_mirror'
						],
						[
							'_id' => 'obnoxious_curse'
						]
					]
				],
				[
    				'_id' => 'monsters',
    				'children' => [
    					[
    						'_id' => 'crabs',
							'lvl' => 1,
							'treasures' => 1,
						],
						[
							'_id' => 'drool_slime',
							'lvl' => 1,
							'treasures' => 1,
						],
						[
							'_id' => 'lame_goblin',
							'lvl' => 1,
							'treasures' => 1,
						],
						[
							'_id' => 'maul_rat',
							'lvl' => 1,
							'treasures' => 1,
						],
						[
							'_id' => 'potted_plant',
							'lvl' => 1,
							'treasures' => 1,
						],
						[
							'_id' => 'mr_bones',
							'lvl' => 2,
							'treasures' => 1,
						],
						[
							'_id' => 'large_angry_chick',
							'lvl' => 2,
							'treasures' => 1,
						],
						[
							'_id' => 'fly_frogs',
							'lvl' => 2,
							'treasures' => 1,
						],
						[
							'_id' => 'gelatine_octahedron',
							'lvl' => 2,
							'treasures' => 1,
						],
						[
							'_id' => 'pit_bull',
							'lvl' => 2,
							'treasures' => 1,
						],
						[
							'_id' => 'leperchaun',
							'lvl' => 4,
							'treasures' => 2,
						],
						[
							'_id' => 'harpies',
							'lvl' => 4,
							'treasures' => 2,
						],
						[
							'_id' => 'speed_snails',
							'lvl' => 4,
							'treasures' => 2,
						],
						[
							'_id' => 'undead_horse',
							'lvl' => 4,
							'treasures' => 2,
						],
						[
							'_id' => 'pukachu',
							'lvl' => 6,
							'treasures' => 2,
						],
						[
							'_id' => 'shriek_geek',
							'lvl' => 6,
							'treasures' => 2,
						],
						[
							'_id' => 'lawyers',
							'lvl' => 6,
							'treasures' => 2,
						],
						[
							'_id' => 'platycore',
							'lvl' => 6,
							'treasures' => 2,
						],
						[
							'_id' => 'ghoulfiends',
							'lvl' => 8,
							'treasures' => 2,
						],
						[
							'_id' => 'gazebo',
							'lvl' => 8,
							'treasures' => 2,
						],
						[
							'_id' => 'amazon',
							'lvl' => 8,
							'treasures' => 2,
						],
						[
							'_id' => 'face_sucker',
							'lvl' => 8,
							'treasures' => 2,
						],
						[
							'_id' => 'float_nose',
							'lvl' => 10,
							'treasures' => 3,
						],
						[
							'_id' => 'orcs_3872',
							'lvl' => 10,
							'treasures' => 3,
						],
						[
							'_id' => 'net_troll',
							'lvl' => 10,
							'treasures' => 3,
						],
						[
							'_id' => 'bigfoot',
							'lvl' => 12,
							'treasures' => 3,
						],
						[
							'_id' => 'tongue_demon',
							'lvl' => 12,
							'treasures' => 3,
						],
						[
							'_id' => 'wannabe_vampire',
							'lvl' => 12,
							'treasures' => 3,
						],
						[
							'_id' => 'unspeak_indescrib_horror',
							'lvl' => 14,
							'treasures' => 4,
						],
						[
							'_id' => 'stoned_golem',
							'lvl' => 14,
							'treasures' => 4,
						],
						[
							'_id' => 'insurance_salesman',
							'lvl' => 14,
							'treasures' => 4,
						],
						[
							'_id' => 'king_tut',
							'lvl' => 16,
							'treasures' => 4,
						],
						[
							'_id' => 'hippogriff',
							'lvl' => 16,
							'treasures' => 4,
						],
						[
							'_id' => 'wight_brothers',
							'lvl' => 16,
							'treasures' => 4,
						],
						[
							'_id' => 'squidzilla',
							'lvl' => 18,
							'treasures' => 4,
						],
						[
							'_id' => 'bullrog',
							'lvl' => 18,
							'treasures' => 5,
						],
						[
							'_id' => 'plutonium_dragon',
							'lvl' => 20,
							'treasures' => 5,
						],
					]
				],
				[
    				'_id' => 'in_battle',
    				'children' => [
    					[
							'_id' => 'help_me',
						],
						[
							'_id' => 'illusion',
						],
						[
							'_id' => 'mate',
						],
						[
							'_id' => 'out_lunch',
						],
    				]
    			],
    			[
    				'_id' => 'in_battle_monster_bonuses',
    				'children' => [
    					[
							'_id' => 'ancient',
							'monster' => 10,
							'treasures' => 2
						],
						[
							'_id' => 'baby',
							'monster' => -5,
							'treasures' => -1	
						],
						[
							'_id' => 'enraged',
							'monster' => 5,
							'treasures' => 1
						],
						[
							'_id' => 'humongous',
							'monster' => 10,	
							'treasures' => 2
						],
						[
							'_id' => 'intelligent',
							'monster' => 5,
							'treasures' => 1
						],
					]
    			],
    			[
    				'_id' => 'other_doors',
    				'children' => [
    					[
    						'_id' => 'cheat',
							'condition' => [
								'place_card' => ['head','armor','foot','arms','items']
							]
						],
						[
							'_id' => 'divine_intervent',
							'instant' => true
						],
						[
							'_id' => 'half_breed',
							'cards_count' => 2,
						],
						[
							'_id' => 'super_munchkin',
							'cards_count' => 2,
						],
						[
							'_id' => 'wander_monster',
							'cards_count' => 3,
						],
					]
				]
    		]
    	]);

		$this->insert('cards', [
    		'_id' => 'treasures',
    		'children' => [
    			[
    				'_id' => 'head',
    				'children' => [
						[
							'_id' => 'bad_ass_bandanna',
							'price' => 400,
							'bonus' => 3,
							'name' => 'Сорвиголовная Бандана',
							'race_type' => 'human',
						],
						[
							'_id' => 'courage_helm',
							'price' => 200,
							'bonus' => 1,
							'name' => 'Шлем Бесстрашия',
						],
						[
							'_id' => 'horny_helmet',
							'price' => 600,
							'bonus' => 1,
							'name' => 'Шлем-Рогач',
							'special_bonus' => [3, 'elf']
						],
						[
							'_id' => 'power_pointy_hat',
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
							'_id' => 'flaming_armor',
							'price' => 400,
							'bonus' => 2,
							'name' => 'Палёные Доспехи',
						],
						[
							'_id' => 'leather_armor',
							'price' => 200,
							'bonus' => 1,
							'name' => 'Кожаный Прикид',
						],
						[
							'_id' => 'mithril_armor',
							'price' => 600,
							'bonus' => 3,
							'name' => 'Мифрильный Доспех',
							'class_type_not' => 'wizard',
							'size' => 'big'
						],
						[
							'_id' => 'short_wide_armor',
							'price' => 400,
							'bonus' => 3,
							'name' => 'Доспехи Поперёк-Себя-Шире',
							'race_type' => 'dwarf'
						],
						[
							'_id' => 'slimy_armor',
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
							'_id' => 'protect_sandals',
							'price' => 700,
							'name' => 'Сандалеты-Протекторы',
							'condition' => [
								'off' => 'doors_curses'
							]
						],
						[
							'_id' => 'butt_kick_boots',
							'price' => 400,
							'bonus' => 2,
							'name' => 'Башмаки Могучего Пенделя',
						],
						[
							'_id' => 'run_fast_boots',
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
							'_id' => 'charm_tuba',
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
							'_id' => 'bow_ribbons',
							'price' => 800,
							'race_type' => 'elf',
							'bonus' => 4,
							'type' => 'two_hand',
							'name' => 'Лук с Ленточками'
						],
						[
							'_id' => 'broad_sword',
							'price' => 400,
							'sex_type' => 'Баба',
							'bonus' => 3,
							'type' => 'one_hand',
							'name' => 'Меч Широты Взглядов'
						],
						[
							'_id' => 'swash_buckler',
							'price' => 400,
							'bonus' => 2,
							'type' => 'one_hand',
							'name' => 'Пафосный Баклер'
						],
						[
							'_id' => 'chainsaw_blood_dismember',
							'price' => 600,
							'size' => 'big',
							'bonus' => 3,
							'type' => 'two_hand',
							'name' => 'Бензопила Кровавого Расчленения'
						],
						[
							'_id' => 'peace_cheese_grater',
							'price' => 400,
							'bonus' => 3,
							'class_type' => 'cleric',
							'type' => 'one_hand',
							'name' => 'Тёрка Умиротворения'
						],
						[
							'_id' => 'treachery_dagger',
							'price' => 400,
							'bonus' => 3,
							'class_type' => 'thief',
							'type' => 'one_hand',
							'name' => 'Кинжал Измены'
						],
						[
							'_id' => 'eleven_foot_pole',
							'price' => 200,
							'bonus' => 1,
							'type' => 'two_hand',
							'name' => 'Одиннадцатифутовый Кий'
						],
						[
							'_id' => 'gentleman_club',
							'price' => 400,
							'bonus' => 3,
							'sex_type' => 'Мужик',
							'type' => 'one_hand',
							'name' => 'Дуб Джентельменов'
						],
						[
							'_id' => 'kneecap_hammer',
							'price' => 600,
							'bonus' => 4,
							'race_type' => 'dwarf',
							'type' => 'one_hand',
							'name' => 'Коленеотбойный Молоточек'
						],
						[
							'_id' => 'huge_rock',
							'price' => 0,
							'bonus' => 3,
							'size' => 'big',
							'type' => 'two_hand',
							'name' => 'Огромная Каменюга'
						],
						[
							'_id' => 'sharp_mace',
							'price' => 600,
							'bonus' => 4,
							'class_type' => 'cleric',
							'type' => 'one_hand',
							'name' => 'Булава Остроконечности'
						],
						[
							'_id' => 'unfair_rapier',
							'price' => 600,
							'bonus' => 3,
							'race_type' => 'elf',
							'type' => 'one_hand',
							'name' => 'Рапира Такнечестности'
						],
						[
							'_id' => 'stick_rat',
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
							'_id' => 'ubiquity_shield',
							'price' => 600,
							'bonus' => 4,
							'class_type' => 'warrior',
							'size' => 'big',
							'type' => 'one_hand',
							'name' => 'Совсехсторонний Щит'
						],
						[
							'_id' => 'sneaky_bastard_sword',
							'price' => 400,
							'bonus' => 2,
							'type' => 'one_hand',
							'name' => 'Меч Коварного Бастарда'
						],
						[
							'_id' => 'napalm_staff',
							'price' => 800,
							'bonus' => 5,
							'class_type' => 'wizard',
							'type' => 'one_hand',
							'name' => 'Посох Напалма'
						],
						[
							'_id' => 'swiss_army_polearm',
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
							'_id' => 'obscurity_cloak',
							'price' => 600,
							'bonus' => 4,
							'name' => 'Плащ Замутнения',
							'class_type' => 'thief'
						],
						[
							'_id' => 'limburger_and_anchovy_sandwich',
							'price' => 400,
							'bonus' => 3,
							'name' => 'Сэндвич Запоздалого Прозрения С Сыром и Селедкой',
							'race_type' => 'halfling'
						],
						[
							'_id' => 'giant_strength_pantyhose',
							'price' => 600,
							'bonus' => 3,
							'name' => 'Колготки Великанской Силы',
							'class_type_not' => 'warrior'
						],
						[
							'_id' => 'impressive_title',
							'bonus' => 3,
							'name' => 'Реально Конкретный Титул',
						],
						[
							'_id' => 'singing_dancing_sword',
							'price' => 600,
							'bonus' => 2,
							'name' => 'Меч Песни и Пляски',
							'class_type_not' => 'thief'
						],
						[
							'_id' => 'spiky_knees',
							'price' => 200,
							'bonus' => 1,
							'name' => 'Острые Коленки'
						],
						[
							'_id' => 'stepladder',
							'price' => 400,
							'bonus' => 3,
							'size' => 'big',
							'name' => 'Боевая Стремянка',
							'race_type' => 'halfling'
						],
						[
							'_id' => 'allure_kneepads',
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
							'_id' => 'go_up_lvl',
							'name' => 'Получи Уровень'
						],
						[
							'_id' => 'thousand_gold_pieces',
							'name' => '1000 голдов'
						],
						[
							'_id' => 'anthill_boil',
							'name' => 'Кипяток В Муравейнике'
						],
						[
							'_id' => 'food_bribe_gm',
							'name' => 'Прикорми Мастера'
						],
						[
							'_id' => 'convenient_addition_error',
							'name' => 'Выгодная Ошибка При Сложении'
						],
						[
							'_id' => 'invoke_obscure_rules',
							'name' => 'Используй Непонятное Правило'
						],
						[
							'_id' => 'kill_hireling',
							'name' => 'Рассчитайся С Наёмником',
							'condition' => 'if_hireling_in_game',
							'action' => 'hireling_discard'
						],
						[
							'_id' => 'mutilate_bodies',
							'name' => 'Поглумись Над Телами Врагов',
							'use_type' => 'after_any_battle'
						],
						[
							'_id' => 'general_studliness_potion',
							'name' => 'Зелье Крутизны'
						],
						[
							'_id' => 'gm_whine',
							'name' => 'Разжалоби Мастера',
							'condition' => 'not_highest_lvl'
						],
					]
				],
				[
					'_id' => 'disposables',
    				'children' => [
						[
							'_id' => 'yuppie_water',
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
							'_id' => 'ponfusion_cotion',
							'price' => 100,
							'bonus' => 3,
							'name' => 'Пелье Зутаницы',
							'when_type' => 'any_battle',
							'which_side' => 'any'
						],
						[
							'_id' => 'electric_radioactive_acid_potion',
							'price' => 200,
							'bonus' => 5,
							'name' => 'Радиоактивно-Электрокислотное Зелье',
							'use_type' => 'any_battle',
							'which_side' => 'any'
						],
						[
							'_id' => 'flaming_poison_potion',
							'price' => 100,
							'bonus' => 3,
							'name' => 'Зелье Пламенной Отравы',
							'use_type' => 'any_battle',
							'which_side' => 'any'
						],
						[
							'_id' => 'freeze_explosive_potion',
							'price' => 100,
							'bonus' => 3,
							'name' => 'Замораживающее Взрывное Зелье',
							'use_type' => 'any_battle',
							'which_side' => 'any'
						],
						[
							'_id' => 'magic_missile',
							'price' => 300,
							'bonus' => 5,
							'name' => 'Магическая Ракета',
							'use_type' => 'any_battle',
							'which_side' => 'any'
						],
						[
							'_id' => 'nasty_taste_sport_drink',
							'price' => 200,
							'bonus' => 2,
							'name' => 'Питьё Противно-Спортивное',
							'use_type' => 'any_battle',
							'which_side' => 'any'
						],
						[
							'_id' => 'halitos_potion',
							'price' => 100,
							'bonus' => ['bonus' => 2, 'kill' => 'float_nose'],
							'name' => 'Зелье Ротовой Вони',
							'use_type' => 'any_battle',
							'which_side' => 'any'
						],
						[
							'_id' => 'idiot_brave_potion',
							'price' => 100,
							'bonus' => 2,
							'name' => 'Зелье Идиотской Храбрости',
							'use_type' => 'any_battle',
							'which_side' => 'any'
						],
						[
							'_id' => 'pretty_balloons',
							'price' => 0,
							'bonus' => 5,
							'name' => 'Клёвые Шарики',
							'use_type' => 'any_battle',
							'which_side' => 'any'
						],
						[
							'_id' => 'sleep_potion',
							'price' => 100,
							'bonus' => 2,
							'name' => 'Снотворное Зелье',
							'use_type' => 'any_battle',
							'which_side' => 'any'
						],
						[
							'_id' => 'doppleganger',
							'price' => 300,
							'bonus' => '*2',
							'name' => 'Дупельгангер',
							'use_type' => 'self_battle',
							'which_side' => 'self'
						],
						[
							'_id' => 'glue_flask',
							'price' => 100,
							'name' => 'Тюбик Клея',
							'use_type' => 'after_true_get_away',
							'action' => 'another_die_roll'
						],
						[
							'_id' => 'friendship_potion',
							'price' => 200,
							'name' => 'Зелье Дружбы',
							'use_type' => 'any_battle',
							'action' => 'all_monsters_discard',
							'treasures' => 'no_one_gets',
							'cur_player_action' => 'looting'
						],
						[
							'_id' => 'instant_wall',
							'price' => 300,
							'name' => 'Стенка-Встанька',
							'use_type' => 'after_any_battle',
							'action' => 'auto_get_away',
							'action_count' => 2
						],
						[
							'_id' => 'invisibility_potion',
							'price' => 200,
							'name' => 'Зелье Невидимости',
							'use_type' => 'after_false_get_away',
							'action' => 'auto_get_away',
							'action_count' => 1
						],
						[
							'_id' => 'loaded_die',
							'price' => 300,
							'name' => 'Читерский Кубик',
							'use_type' => 'after_die_roll',
							'action' => 'select_die_number'
						],
						[
							'_id' => 'magic_lamp',
							'price' => 500,
							'name' => 'Волшебная Лампа',
							'use_type' => 'on_self_move',
							'action' => 'one_monster_discard_any_time',
							'condition' => 'if_one_monster_then_get_treasures_but_no_lvl'
						],
						[
							'_id' => 'pollymorph_potion',
							'price' => 1300,
							'name' => 'Зелье Попуморфа',
							'use_type' => 'any_battle',
							'action' => 'one_monster_discard'
						],
						[
							'_id' => 'transferral_potion',
							'price' => 300,
							'name' => 'Зелье Стрелочника',
							'use_type' => 'any_battle',
							'action' => 'chosen_player_battles_monster',
							'after_action' => 'cur_player_can_loot'
						],
						[
							'_id' => 'dowsing_wand',
							'price' => 1100,
							'name' => 'Штырь Лозоходца',
							'use_type' => 'on_move',
							'action' => 'choose_one_card_from_discard'
						],
						[
							'_id' => 'wishing_ring',
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
							'_id' => 'hireling',
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
							'_id' => 'steal_lvl',
							'action' => 'choose_player_and_steal_lvl',
							'name' => 'Укради Уровень'
						],
						[
							'_id' => 'q_dice',
							'name' => 'Q-Кубик',
							'price' => 1000
						],
						[
							'_id' => 'hoard',
							'name' => 'Ура, Клад!',
							'treasures' => 3,
							'instant' => true,
							'condition' => 'if_card_face_down_treasures_same'
						],
					]
				]
    		]
		]);
    }

    public function down()
    {
        $this->dropCollection('cards');
    }
}
