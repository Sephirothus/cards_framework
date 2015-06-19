/**
 * Params
 *
 *
 */
var Params = {
	imgExt: 'jpg',
	imgPath: '/imgs/cards/',
	typeImgPage: '/imgs/',
	cardPath: function(id, is_small) {
		return Params.imgPath+id+(is_small ? '-small' : '')+'.'+Params.imgExt;
	},
	typePath: function(type) {
		return Params.typeImgPage+type+'.'+Params.imgExt;
	},
};

/*
1. переделать структуру игрального стола и после обновить метод actions
2. возможно обернуть все картинки в дивки
3. перебор приходящего массива, в restoreGame, должен быть универсальный
4. создать настройки дефолтных классов для разных позиций карт (на руке, в игре, в поле)
5. класс decks - заменить

6. класс MiniMap
7. класс Dice
8. класс Map
9. класс Dungeon
*/

function CardActions(settings) {
	this.classes = {
		// blocks
		'hand_block': 'js_hand_cards',
		'play_block': 'js_play_cards',
		'player_block': 'js_players',
		'user_info': 'js_user_info',
		// cards
		'hand_card': 'js_hand_card',
		'play_card': 'js_play_card',
		'field_card': 'js_field_card',
		// temps
		'enlarge_card': 'js_enlarge_card',
		'temp_pic': 'js_temp_pic'
	};
	// default user classes
	this.defClasses = {
		'hand_card': 'on_hand card',
		'play_card': 'card',
		'field_card': 'card'
	};
	this.fieldId = 'main_field';
	this.fieldPlaceId = 'card_field';
	this.exampleBlockId = 'example';
	this.actionTypes = {
		'from_hand_to_play': 'from_hand_to_play',
		'from_hand_to_field': 'from_hand_to_field',
		'from_play_to_field': 'from_play_to_field',
		'from_field_to_hand': 'from_field_to_hand',
		'open_door': 'open_door',
		'get_treasures_card': 'get_treasures_card',
		'get_doors_card': 'get_doors_card',
		'discard_from_hand': 'discard_from_hand',
		'discard_from_play': 'discard_from_play',
		'discard_from_field': 'discard_from_field',
		'sell_cards': 'sell_cards',
		'turn_card_off': 'turn_card_off',
		'turn_card_on': 'turn_card_on',
		'throw_dice': 'throw_dice',
		'end_move': 'end_move'
	};
	this.socketTypes = {
		'not_all_users': 'not_all_users',
		'start_game': 'start_game'
	};
	this.framesColors = {
		'sell': 'rgb(31, 122, 31)',
		'select': 'red'
	};
	this.phases = {
		'place_cards': 'Выкладивание-продажа-обмен',
		'get_boss': 'Битва с боссом',
		'get_boss_lose': 'Смываемся, бросай кубик',
		'get_boss_win': 'Победа, бери сокровища',
		'boss_bad_stuff': 'Босс творит непотребство',
		'get_curse': 'Получи проклятие',
		'get_other': 'Разная хня',
		'not_boss': 'Чистим нычки или Бьемся со своим',
		'final_place_cards': 'Готовимся передать ход'
	};
	this.gameId = 1;
	this.userId = 1;
	this.ajaxUrl = '/index';
	// set settings
	for (var el in settings) {
		if (this[el]) this[el] = settings[el];
	}

	this.html;
	this.defActions;
	this.chain;
	this.allowedActions = {};
	this.notAllowedActions = {};
	this.curPhase;
}

CardActions.prototype.init = function() {
	var self = this, restoreGameFlag = false;
	self.html = new HtmlBuilder(self);
	self.defActions = new DefaultActions(self);

	WS.setParams({
		'topic': self.gameId
	}).init(function(resp) {
		var startUsersLen = $('.'+self.classes.player_block).length;
		self.placeUserBlocks(resp);
		if (resp.type == self.socketTypes['not_all_users']) {
			var endUsersLen = $('.'+self.classes.player_block).length;
			if (startUsersLen != endUsersLen) alert('Осталось '+resp.count+' игрок(ов)');
			return false;
		} else if (resp.type == self.socketTypes['start_game']) {
			self.setSubscribe();
			var count = resp.decks.length,
				func = function() {
					setTimeout(function() {
						var block = $('#'+self.userId+'.'+self.classes.player_block);
						self.defActions.turnCards(block.find('.'+self.classes.hand_block), function() {
							//eventsOn(block, self.userId);
						});
					}, 1000);
				};
			for (var el in resp.decks) {
				self.defActions.dealCards(resp.decks[el], resp.cards, !--count ? func : false);
			}
		} else {
			if (!restoreGameFlag) self.restoreGame();
			restoreGameFlag = true;
		}
	});

	self.events();
}

CardActions.prototype.events = function() {
	var self = this;

	$(function() {
		// on card hover
	    $(document).on({
			mouseenter: function(e) {
	        	if (e.pageX > ($(window).width()/2)) var pos = 'left:0';
	        	else var pos = 'right:0';
	        	var img = $(this).attr('src').substr($(this).attr('src').lastIndexOf('/')+1);
	        	img = Params.cardPath(img.substr(0, img.lastIndexOf('-')));
				$('body').prepend($('<img>', {
					src: img, 
					class: 'js_temp_pic', 
					style: 'z-index:9999;position:fixed;top:0;'+pos+';height:500px;'
				}));
				$(this).css({'position': 'relative', 'z-index': 999});
			},
			mouseleave: function(e) {
				self.focusCard($(this));
				$('.js_temp_pic').remove();
			}
		}, '.js_enlarge_card');

	    // card click - actions show
		$(document).on('click', '#'+self.userId+'.'+self.classes.player_block+' img', function() {
			if ($('#card_actions').attr('card_id') == $(this).attr('id') && $('#card_actions').is(':visible')) self.onOffActions($(this));
			else self.onOffActions($(this), 'on');
		});

		// card actions click
		$(document).on('click', '#card_actions button', function() {
			var card = $('#'+$(this).parent().attr('card_id')),
				action = false,
				additional = {},
				acts = self.actionTypes;
			switch($(this).attr('action')) {
				case 'discard':
					if (card.hasClass(self.classes.hand_card)) action = acts['discard_from_hand'];
					else if (card.hasClass(self.classes.play_card)) action = acts['discard_from_play'];
					else if (card.hasClass(self.classes.field_card)) action = acts['discard_from_field'];
					break;
				case 'to_play':
					action = acts['from_hand_to_play'];
					break;
				case 'to_field':
					action = card.hasClass(self.classes.hand_card) ? acts['from_hand_to_field'] : acts['from_play_to_field'];
					if (card.data('parent') == 'disposables' && card.data('bonus')) {
						if (confirm('Использовать бонус на себя?')) additional['bonus_on'] = 'self';
						else additional['bonus_on'] = 'monster';
					}
					break;
				case 'turn':
					action = card.hasClass('decks') ? acts['turn_card_on'] : acts['turn_card_off'];
					break;
				case 'sell':
					var col = self.framesColors,
						overallPrice = 0;
					if (card.css('border-right-color') == col['sell']) card.css({'border': '5px outset '+col['select']});
					else card.css({'border': '5px outset '+col['sell']});

					$('.'+self.classes.play_card+', .'+self.classes.hand_card).filter(function() {
						return $(this).css('border-right-color') == col['sell'];
					}).each(function() {
						if ($(this).attr('data-price')) overallPrice += parseInt($(this).attr('data-price'));
					});
					console.log(overallPrice)
					if (overallPrice >= 1000) {
						if (confirm('Хотите продать карт на '+overallPrice+' и получить '+(Math.floor(overallPrice/1000))+' уровень(ня)')) {
							action = acts['sell_cards'];
							var cardIds = [];
							$('.'+self.classes.play_card+', .'+self.classes.hand_card).filter(function() {
								return $(this).css('border-right-color') == col['sell'];
							}).each(function() {
								if (parseInt($(this).attr('data-price')) >= 0) cardIds.push($(this).attr('id'));
								else $(this).css({'border': ''});
							});
						}
					}
					break;
			}
			if (action) {
				self.sendAction({
					card_id: cardIds ? cardIds : card.attr('id'),
					card_coords: self.getPercentOffset(card), 
					user_id: self.userId,
					action: action,
					additional: additional
				});
			}
		});
	
		// discard all field cards
		$(document).on('click', '#discard_all', function() {
			self.discardFromField();
		});

		// get card from deck
		$(document).on('click', '#doors, #treasures', function() {
			self.sendAction({
				card_type: $(this).attr('id'), 
	    		user_id: self.userId,
	    		action: 'get_'+$(this).attr('id')+'_card'
			});
		});

		// end move
		$(document).on('click', '#end_move', function() {
			self.sendAction({
	    		user_id: self.userId,
	    		action: self.actionTypes['end_move']
			});
		});

		// throw dice
		$(document).on('click', '#throw_dice', function() {
			if ($('#dice_place').find('div').length) {
				$('#dice_place').html('');
			} else {
				self.sendAction({
		    		user_id: self.userId,
		    		action: self.actionTypes['throw_dice']
				});
			}
		});

		// from field to hand
		$(document).on('click', '#from_field_to_hand', function() {
			$('#'+self.fieldPlaceId+' img').each(function() {
				self.sendAction({
					card_id: $(this).attr('id'), 
					card_coords: self.getPercentOffset($(this)), 
		    		user_id: self.userId,
		    		action: self.actionTypes['from_field_to_hand']
				});
			});
		});
	});
}

CardActions.prototype.onOffActions = function(elem, action, callback) {
	var self = this;
	if ($('#card_actions').length) {
		$('#card_actions').slideUp("fast", function() {
			self.focusCard(elem);
			if (typeof callback == 'function') callback();
		});
	} else if (typeof callback == 'function') callback();

	if (action == 'on') {
		if (!$('#card_actions').length) {
			$('body').append($('<div>', {
				css: {'z-index': 99999, 'position': 'fixed', 'bottom': 0, 'display': 'none'},
				id: 'card_actions',
				card_id: elem.attr('id'),
				html: this.html.glyph('remove', 'discard', 'Сбросить')+
					this.html.glyph('usd', 'sell', 'Продать')+
					//this.html.glyph('refresh', 'change', 'Обменять')+
					this.html.glyph('retweet', 'turn', 'Перевернуть')+
					this.html.glyph('play', 'to_play', 'В игру')+
					this.html.glyph('th-large', 'to_field', 'В поле')
			}));
			//this.html.drawDice('card_actions');
		} else {
			$('#card_actions').attr('card_id', elem.attr('id'));
		}
		$('#card_actions').slideDown("fast", function() { self.focusCard(elem); });
	}
}

CardActions.prototype.focusCard = function(card) {
	var self = this;
	card.parent().find('img').filter(function() { 
		return $(this).css('border-right-color') != self.framesColors['sell']; 
	}).css({'position': '', 'z-index': '', 'border': ''});
	if ($('#card_actions').is(':visible')) $('#'+$('#card_actions').attr('card_id')).filter(function() { 
		return $(this).css('border-right-color') != self.framesColors['sell']; 
	}).css({'position': 'relative', 'z-index': 999, 'border': '5px outset '+self.framesColors['select']});
}

CardActions.prototype.discardFromField = function() {
	var self = this;
	if (self.allowedActions.length > 0) self.allowedActions.push(self.actionTypes['discard_from_field']);
	$('#'+self.fieldPlaceId+' img').each(function() {
		self.sendAction({
			card_id: $(this).attr('id'), 
    		card_coords: self.getPercentOffset($(this)), 
    		user_id: self.userId,
    		action: self.actionTypes['discard_from_field']
		});
	});
}

CardActions.prototype.restoreGame = function() {
	var self = this, data,
		getInfo = function(info, type) {
			var data = self.formCardData(info);
			data['id'] = info['_id'] ? info['_id'] : info;
			data['type'] = type;
			data['pic_id'] = info['id'];
			return data;
		};
	self.defActions.ajaxRequest(self.ajaxUrl, {type: 'restore_game'}, function(resp) {
		resp = resp.results;
		for (var attr in resp) {
			for (var user in resp[attr]) {
				switch (attr) {
					case 'turn_cards':
						$('#'+resp[attr][user]).toggleClass('decks');
						break;
				}
				for (var type in resp[attr][user]) {
					data = getInfo(resp[attr][user][type], user);
					switch (attr) {
						case 'field_cards':
							$('#'+self.fieldPlaceId).append(self.html.createCard(data, 'field'));
							break;
						case 'discards':
							var card = self.html.createCard(data, 'discard');
							$('#'+user+'_discard div').append(card);
							break;
					}
					for (var el in resp[attr][user][type]) {
						data = getInfo(resp[attr][user][type][el], type);
						switch (attr) {
							case 'hand_cards':
								$('#'+user+'.'+self.classes.player_block+' .'+self.classes.hand_block).append(self.html.createCard(data, 'hand'));
								break;
							case 'play_cards':
								$('#'+user+'.'+self.classes.player_block+' .'+self.classes.play_block).append(self.html.createCard(data, 'play'));
								break;
						}
					}
				}
			}
		}
		self.setSubscribe();
		//eventsOn($('#'+self.userId), self.userId);
	});
}

/**
 * Actions on cards, after response
 *
 * @param resp - needed object with such parameters: 
 * 		* card_id - ID of current card, 
 *		* pic_id - image url of current card, 
 *		* card_type - type of current card, 
 *		* user_id - user ID (card holder)
 **/
CardActions.prototype.actions = function(resp) {
	var self = this,
		card = $('#'+resp.card_id).length ? $('#'+resp.card_id) : $('#'+resp.pic_id),
		acts = self.actionTypes,
		chainObj = new CChain(),
		diceObj = new Dice();

	chainObj.registerObjs([self, self.defActions, diceObj]);
	if (resp.rule) {
		if (resp.user_id == self.userId) {
			var text = typeof resp.rule == 'string' ? resp.rule : resp.rule.text;
			$('body').append(this.html.message(text));
			setTimeout(function() { $('#message').remove(); }, 3000);
		}
		if (resp.rule.action != acts['turn_card_off']) return;
	}

	if (resp.action) actionOn(resp.action.shift());
	else self.phaseActions(resp);

	function actionOn(action) {
		var action = arguments[0];
		var nextAction = function() { var nextAction = resp.action.shift(); if (nextAction) actionOn(nextAction); else self.phaseActions(resp); };
		switch (action) {
			case acts['from_hand_to_play']:
				chainObj.registerCall('moveCard', [card, $('#'+resp.user_id+'.'+self.classes.player_block+' .'+self.classes.play_block)]);
				if (resp.user_id != self.userId) {
					chainObj.registerCall('turnOneCard', [card, {src: Params.cardPath(resp.pic_id, true)}]);
				} 
				chainObj.registerCall(function(callback) { 
					card.attr('class', self.defClasses.play_card+' '+self.classes.enlarge_card+' '+self.classes.play_card); 
					callback(); 
				});
				break;
			case acts['from_hand_to_field']:
				if (resp.additional.on_bonus) card.data('on_bonus') = resp.additional.on_bonus;
				chainObj.registerCall('moveCard', [card, $('#'+self.fieldPlaceId)]);
				if (resp.user_id != self.userId) {
					chainObj.registerCall('turnOneCard', [card, {src: Params.cardPath(resp.pic_id, true)}]);
				}
				chainObj.registerCall(function(callback) { 
					card.attr('class', self.defClasses.field_card+' '+self.classes.enlarge_card+' '+self.classes.field_card); 
					callback();
				});
				break;
			case acts['from_play_to_field']:
				if (resp.additional.on_bonus) card.data('on_bonus') = resp.additional.on_bonus;
				chainObj.registerCall('moveCard', [card, $('#'+self.fieldPlaceId)]).
					registerCall(function(callback) {
						card.attr('class', self.defClasses.field_card+' '+self.classes.enlarge_card+' '+self.classes.field_card);
						callback();
					});
				break;
			case acts['discard_from_hand']:
			case acts['discard_from_play']:
				if (resp.user_id != self.userId && action == acts['discard_from_hand']) {
					chainObj.registerCall('turnOneCard', [card, {src: Params.cardPath(resp.pic_id, true)}]);
				}
				chainObj.registerCall('discard', [card]);
				break;
			case acts['sell_cards']:
				for (var el in resp.card_id) {
					if (resp.user_id != self.userId) {
						chainObj.registerCall('turnOneCard', [$('#'+resp.card_id[el]), {src: Params.cardPath(''/*resp.pic_id*/, true)}]);
					}
					chainObj.registerCall('discard', [$('#'+resp.card_id[el])]);
				}
				chainObj.registerCall(function(callback) { 
					$('#'+resp.user_id).find('#lvl').html(resp.user_lvl+' lvl'); 
					callback(); 
				});
				break;
			case acts['from_field_to_hand']:
				chainObj.registerCall('moveCard', [card, $('#'+resp.user_id+' .'+self.classes.hand_block)]);
				if (resp.user_id != self.userId) {
					chainObj.registerCall(function(callback) { card.css({'z-index': 99999}); callback(); }).
						registerCall('turnOneCard', [card, {src: Params.typePath(resp.card_type)}]). 
						registerCall(function(callback) {
							card.attr('class', self.defClasses.hand_card+' '+self.classes.hand_card);
							card.removeAttr('style');
							for (var i in card.data()) {
								card.removeAttr('data-'+i);
							}
							card.css({'position': 'relative'});
							callback();
						});
				} else {
					chainObj.registerCall(function(callback) { 
						card.attr('class', self.defClasses.hand_card+' '+self.classes.enlarge_card+' '+self.classes.hand_card);
						callback();
					});
				}
				break;
			case acts['discard_from_field']:
				chainObj.registerCall('discard', [card]).
					registerCall(function(callback) { 
						$('#your_str').html(''); 
						$('#boss_str').html('');
						callback(); 
					});
				break;
			case acts['open_door']:
				chainObj.registerCall('getOneCard', [$.extend(self.formCardData(resp.card_info), {id: resp.card_id, type: resp.card_type}), $('#'+self.fieldPlaceId)]).
					registerCall(function(callback) { 
						var newCardObj = $('#'+resp.card_id); 
						newCardObj.css({'z-index': 99999});
						self.defActions.turnOneCard(newCardObj, {src: Params.cardPath(resp.pic_id, true)}, false, callback);
					}).
					registerCall(function(callback) {
						var newCardObj = $('#'+resp.card_id); 
						newCardObj.attr('class', self.defClasses.field_card+' '+self.classes.enlarge_card+' '+self.classes.field_card);
						newCardObj.removeAttr('style');
						newCardObj.css({'position': 'relative'});
						callback();
					});
				break;
			case acts['get_doors_card']:
			case acts['get_treasures_card']:
				chainObj.registerCall('getOneCard', [$.extend(self.formCardData(resp.card_info), {id: resp.card_id, type: resp.card_type}), $('#'+resp.user_id+' .'+self.classes.hand_block)]);
				if (resp.user_id == self.userId) {
					chainObj.registerCall(function(callback) { 
							var newCardObj = $('#'+resp.card_id); 
							newCardObj.css({'z-index': 99999}); 
							self.defActions.turnOneCard(newCardObj, {src: Params.cardPath(resp.pic_id, true)}, false, callback);
						});
				} 
				chainObj.registerCall(function(callback) {
					var newCardObj = $('#'+resp.card_id); 
					newCardObj.attr('class', self.defClasses.hand_card+' '+self.classes.enlarge_card+' '+self.classes.hand_card);
					newCardObj.removeAttr('style');
					if (resp.user_id != self.userId) {
						for (var i in newCardObj.data()) {
							newCardObj.removeAttr('data-'+i);
						}
					}
					newCardObj.css({'position': 'relative'});
					callback();
					//if (self.userId = resp.user_id) eventsOn($('#'+self.userId));
				});
				break;
			case acts['turn_card_off']:
			case acts['turn_card_on']:
				chainObj.registerCall(function(callback) { card.toggleClass('decks'); callback(); });
				break;
			case acts['throw_dice']:
				chainObj.registerCall('throwDice', [$('#dice_place'), resp.dice]);
				break;
		}
		
		if (action != 'discard_from_field' && resp.next_phase && resp.next_phase.firstKey() == 'final_place_cards') {
			chainObj.registerCall(function(callback) {
				self.discardFromField();
				callback();
			});
		}
		if (resp.user_id == self.userId) chainObj.registerCall('onOffActions', [card, 'off']);
		chainObj.registerCall(nextAction).run();
	}
}

CardActions.prototype.phaseActions = function(resp) {
	var self = this;
	if (resp.next_phase) {
		var curUser = (resp.next_user || false).firstKey();
		if (curUser && self.userId != curUser) {
			self.allowedActions = resp.next_user[curUser]['yes'];
			self.notAllowedActions = {};
		} else {
			var phaseActions = resp.next_phase.firstVal();
			if (phaseActions['not']) {
				self.notAllowedActions = phaseActions['not'];
				self.allowedActions = [];
			} else if (phaseActions['yes']) {
				self.allowedActions = phaseActions['yes'];
				self.notAllowedActions = [];
			} else {
				self.allowedActions = [];
				self.notAllowedActions = [];
			}
		}
		if (curUser) {
			$('.'+self.classes.player_block+' #lvl').parent().attr('class', 'label label-primary '+self.classes.user_info);
			$('#'+curUser+' #lvl').parent().attr('class', 'label label-success '+self.classes.user_info);
		}
		var phase = resp.next_phase.firstKey();
		self.curPhase = phase;
		$('#phase_name').html('Фаза: '+self.phases[phase]);
	}

	if (self.curPhase == 'get_boss') {
		$('#your_str').html('Сила манчкина: '+self.getUserStr());
		$('#boss_str').html('Сила босса: '+self.getBossStr());
	}

	if (resp.lvl_up) {
		$('#'+resp.lvl_up).find('#lvl').html((parseInt($('#'+resp.user_id).find('#lvl').html())+1)+' lvl');
	}

	$('.'+self.classes.user_info).each(function() {
		$(this).attr({
			'data-toggle': "tooltip", 
			'data-placement': "bottom",
			'data-original-title': self.getUserInfo($(this).parents('.'+self.classes.player_block).attr('id'))
		});
		$(this).tooltip({html: true});
	});
}

CardActions.prototype.formCardData = function(info) {
	var data = {};
	if (typeof info == 'object') {
		for (var i in info) {
			data['data-'+i] = info[i];
		}
	}
	return data;
}

CardActions.prototype.getUserStr = function() {
	var str = 0, userId = this.findCurUser();
	str += parseInt($('#'+userId+' #lvl').html());
	$('#'+userId+' .'+this.classes.play_block+' img').each(function() {
		if (parseInt($(this).attr('data-bonus')) && !$(this).hasClass('decks') && $(this).attr('data-parent') != 'disposables') str += parseInt($(this).attr('data-bonus'));
	});
	$('#'+this.fieldPlaceId+' img').each(function() {
		switch($(this).data('parent')) {
			case 'disposables':
				if ($(this).data('on_bonus') != 'monster' && parseInt($(this).data('bonus')) > 0) str += parseInt($(this).data('bonus'));
				break;
		}
	});
	return str;
}

CardActions.prototype.getBossStr = function() {
	var bossStr = 0;
	$('#'+this.fieldPlaceId+' img').each(function() {
		switch($(this).data('parent')) {
			case 'monsters':
				bossStr += parseInt($(this).data('lvl'));
				break;
			case 'in_battle_monster_bonuses':
				bossStr += parseInt($(this).data('monster'));
				break;
			case 'disposables':
				if ($(this).data('on_bonus') == 'monster' && parseInt($(this).data('bonus')) > 0) bossStr += parseInt($(this).data('bonus'));
				break;
		}
	});
	return bossStr;
}

CardActions.prototype.findCurUser = function() {
	var curUser = false, self = this;
	$('.'+self.classes.user_info).each(function() {
		if ($(this).hasClass('label-success')) {
			curUser = $(this).parents('.'+self.classes.player_block).attr('id');
			return false;
		}
	});
	return curUser;
}

CardActions.prototype.getUserInfo = function(userId) {
	var self = this, 
		info = {'Класс': '', 'Раса': '', 'Сила': parseInt($('#'+userId+' .'+self.classes.user_info+' #lvl').html()), 'Бонусная сила': 0}, 
		str = '';

	$('#'+userId+' .'+self.classes.play_card).each(function() {
		switch($(this).data('parent')) {
			case 'classes':
				info['Класс'] += $(this).data('name')+'-';
				break;
			case 'races':
				info['Раса'] += $(this).data('name')+'-';
				break;
			case 'head': 
			case 'armor': 
			case 'foot': 
			case 'arms': 
			case 'items':
				if ($(this).data('bonus') && !$(this).hasClass('decks')) info['Сила'] += parseInt($(this).data('bonus'));
				break;
			case 'disposables':
				if ($(this).data('bonus')) info['Бонусная сила'] += parseInt($(this).data('bonus'));
				break;
		}
	});
	$('#'+userId+' .'+self.classes.hand_card).each(function() {
		switch($(this).data('parent')) {
			case 'disposables':
				if ($(this).data('bonus')) info['Бонусная сила'] += parseInt($(this).data('bonus'));
				break;
		}
	});
	info['Класс'] = info['Класс'].substring(0, info['Класс'].length - 1);
	info['Раса'] = info['Раса'].substring(0, info['Раса'].length - 1);
	for (var i in info) {
		str += i+': '+(info[i] ? info[i] : (i == 'Раса' ? 'Человек' : 'нет'))+"<br>";
	}
	return str;
}

CardActions.prototype.placeUserBlocks = function(resp) {
	var self = this,
		users = resp.users,
		curMove = resp.next_user.firstKey();

	for (var el in users) {
		users[el]['id'] = el;
		var curUser = users[el];
		if (!$('#'+el+'.'+self.classes.player_block).length) {
			if ($('.js_player_place:empty').length == 0) {
				var block = $('#'+this.exampleBlockId).clone();
				block.find('.js_player_place').html('');
				$('#'+this.exampleBlockId+':last').after(block);
			}
			$('.js_player_place').each(function() {
				var player_block = $(this).find('.'+self.classes.player_block),
					field = $(this).find('#'+self.fieldId);
				if (player_block.length < 2) {
					if (player_block.length > 0) {
						if (field.length > 0) return true;
						player_block.removeClass('col-md-12').addClass('col-md-6');
					}
					var width = player_block.length > 0 || field.length > 0 ? 6 : 12;
					if (field.length > 0) {
						field.removeClass('col-md-12').addClass('col-md-6');
						$(this).prepend(self.html.createUserBlock(curUser, width));
					} else $(this).append(self.html.createUserBlock(curUser, width));
					return false;
				}
			});
		}
	}
}

CardActions.prototype.getPercentOffset = function(el) {
	return {
    	'top': el.offset().top / $(document).height() * 100 + '%',
    	'left': el.offset().left / $(document).width() * 100 + '%',
    };
}

CardActions.prototype.getPosition = function(el) {
	return {
    	'top': (el.position().top + el.height()) + 'px',
    	'left': (el.position().left - 80) + 'px',
    };
}

CardActions.prototype.setSubscribe = function() {
	var self = this;
	WS.onSubscribe(function(resp) {
		console.log(resp)
		if (resp.count() > 0) self.actions(resp);
	});
}

CardActions.prototype.sendAction = function(params) {
	if (this.notAllowedActions.count() > 0 && $.inArray(params['action'], this.notAllowedActions) >= 0 || 
		this.allowedActions.count() > 0 && $.inArray(params['action'], this.allowedActions) < 0) return false;

	WS.publish(params);
}
