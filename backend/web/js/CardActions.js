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

/**
 * Chain class
 */
function Chain(mainObj) {
	this.copyAttrs(mainObj);
	this.chains = [];
	this.mainObj = mainObj;
}

Chain.prototype.registerCall = function(func, args, obj) {
	this.chains.push({'obj': obj ? obj : this.mainObj, 'function': func, 'args': args ? args : []});
	return this;
}

Chain.prototype.runWithCallback = function() {
	var self = this, func = self.chains.shift();
	if (func) {
		func['args'].push(function() {
			self.runWithCallback();
		});
		func['function'].apply(func['obj'], func['args']);
	}
}

Chain.prototype.run = function() {
	console.log(this.chains)
	this.runWithCallback();
}

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
	this.exampleBlockId = 'example';
	this.actionTypes = {
		'from_hand_to_play': 'from_hand_to_play',
		'from_hand_to_field': 'from_hand_to_field',
		'from_play_to_field': 'from_play_to_field',
		'from_field_to_hand': 'from_field_to_hand',
		'get_doors_card': 'get_doors_card',
		'get_treasures_card': 'get_treasures_card',
		'discard_from_hand': 'discard_from_hand',
		'discard_from_play': 'discard_from_play',
		'discard_from_field': 'discard_from_field',
		'sell_cards': 'sell_cards',
		'turn_card_off': 'turn_card_off',
		'turn_card_on': 'turn_card_on',
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
		'get_curse': 'Получи проклятие',
		'get_other': 'Разная хня',
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
}

CardActions.prototype.init = function() {
	var self = this, restoreGameFlag = false;
	self.html = new HtmlBuilder(self);
	self.defActions = new DefaultActions(self);
	self.chain = new Chain(self);

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
					action: action
				});
			}
		});
	
		// discard all field cards
		$(document).on('click', '#discard_all', function() {
			$('#'+self.fieldId+' img').each(function() {
				self.sendAction({
					card_id: $(this).attr('id'), 
		    		card_coords: self.getPercentOffset($(this)), 
		    		user_id: self.userId,
		    		action: self.actionTypes['discard_from_field']
				});
			});
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

		// from field to hand
		$(document).on('click', '#from_field_to_hand', function() {
			$('#'+self.fieldId+' img').each(function() {
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
	if ($('#card_actions').length) $('#card_actions').slideUp("fast", function() {
		self.focusCard(elem);
		if (typeof callback == 'function') callback();
	});

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

CardActions.prototype.focusCard = function(card) {
	var self = this;
	card.parent().find('img').filter(function() { 
		return $(this).css('border-right-color') != self.framesColors['sell']; 
	}).css({'position': '', 'z-index': '', 'border': ''});
	if ($('#card_actions').is(':visible')) $('#'+$('#card_actions').attr('card_id')).filter(function() { 
		return $(this).css('border-right-color') != self.framesColors['sell']; 
	}).css({'position': 'relative', 'z-index': 999, 'border': '5px outset '+self.framesColors['select']});
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
							$('#'+self.fieldId).append(self.html.createCard(data, 'field'));
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
		acts = self.actionTypes;

	if (resp.rule) {
		if (resp.user_id == self.userId) {
			var text = typeof resp.rule == 'string' ? resp.rule : resp.rule.text;
			$('body').append(this.html.message(text));
			setTimeout(function() { $('#message').remove(); }, 3000);
		}
		if (resp.rule.action != acts['turn_card_off']) return;
	}

	$('.'+self.classes.user_info).each(function() {
		$(this).attr({
			'data-toggle': "tooltip", 
			'data-placement': "bottom",
			'title': self.getUserInfo($(this).parents('.'+self.classes.player_block).attr('id'))
		});
		$(this).tooltip({html: true});
	});

	if (resp.action) actionOn(resp.action.shift());
	else self.phaseActions(resp);

	function actionOn(action) {
		var action = arguments[0];
		var nextAction = function(callback) { var nextAction = resp.action.shift(); if (nextAction) actionOn(nextAction); else self.phaseActions(resp); };
		switch (action) {
			case acts['from_hand_to_play']:
				self.chain.registerCall(self.defActions.moveCard, [card, $('#'+resp.user_id+'.'+self.classes.player_block+' .'+self.classes.play_block)], self.defActions);
				if (resp.user_id != self.userId) {
					self.chain.registerCall(self.defActions.turnOneCard, [card, {src: Params.cardPath(resp.pic_id, true)}, false], self.defActions);
				} 
				self.chain.registerCall(function(callback) { 
					card.attr('class', self.defClasses.play_card+' '+self.classes.enlarge_card+' '+self.classes.play_card); 
					callback(); 
				});
				break;
			case acts['from_hand_to_field']:
				self.chain.registerCall(self.defActions.moveCard, [card, $('#'+self.fieldId)], self.defActions);
				if (resp.user_id != self.userId) {
					self.chain.registerCall(self.defActions.turnOneCard, [card, {src: Params.cardPath(resp.pic_id, true)}, false], self.defActions);
				}
				self.chain.registerCall(function(callback) { 
					card.attr('class', self.defClasses.field_card+' '+self.classes.enlarge_card+' '+self.classes.field_card); 
					callback();
				});
				break;
			case acts['from_play_to_field']:
				self.chain.registerCall(self.defActions.moveCard, [card, $('#'+self.fieldId)], self.defActions) 
					registerCall(function(callback) {
						card.attr('class', self.defClasses.field_card+' '+self.classes.enlarge_card+' '+self.classes.field_card);
						callback();
					});
				break;
			case acts['discard_from_hand']:
			case acts['discard_from_play']:
				if (resp.user_id != self.userId && action == acts['discard_from_hand']) {
					self.chain.registerCall(self.defActions.turnOneCard, [card, {src: Params.cardPath(resp.pic_id, true)}, 0], self.defActions);
				}
				self.chain.registerCall(self.defActions.discard, [card], self.defActions);
				break;
			case acts['sell_cards']:
				for (var el in resp.card_id) {
					if (resp.user_id != self.userId) {
						self.chain.registerCall(self.defActions.turnOneCard, [$('#'+resp.card_id[el]), {src: Params.cardPath(''/*resp.pic_id*/, true)}, false], self.defActions);
					}
					self.chain.registerCall(self.defActions.discard, [$('#'+resp.card_id[el])], self.defActions);
				}
				self.chain.registerCall(function(callback) { 
					$('#'+resp.user_id).find('#lvl').html(resp.user_lvl+' lvl'); 
					callback(); 
				});
				break;
			case acts['from_field_to_hand']:
				self.chain.registerCall(self.defActions.moveCard, [card, $('#'+resp.user_id+' .'+self.classes.hand_block)], self.defActions);
				if (resp.user_id != self.userId) {
					self.chain.registerCall(function(callback) { card.css({'z-index': 99999}); callback(); }).
						registerCall(self.defActions.turnOneCard, [card, {src: Params.typePath(resp.card_type)}, false], self.defActions). 
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
					self.chain.registerCall(function(callback) { 
						card.attr('class', self.defClasses.hand_card+' '+self.classes.enlarge_card+' '+self.classes.hand_card);
						callback();
					});
				}
				break;
			case acts['discard_from_field']:
				self.chain.registerCall(self.defActions.discard, [card, false], self.defActions).
					registerCall(function(callback) { $('#your_str').html(''); $('#boss_str').html(''); callback(); });
				break;
			case acts['get_doors_card']:
				var newCardObj;
				self.chain.registerCall(self.defActions.getOneCard, [$.extend(self.formCardData(resp.card_info), {id: resp.card_id, type: resp.card_type}), $('#'+self.fieldId)], self.defActions).
					registerCall(function(newCard, callback) { console.log(newCard); newCardObj = newCard; newCardObj.css({'z-index': 99999}); callback(); }).
					registerCall(self.defActions.turnOneCard, [newCardObj, {src: Params.cardPath(resp.pic_id, true)}, false], self.defActions).
					registerCall(function(callback) {
						newCardObj.attr('class', self.defClasses.field_card+' '+self.classes.enlarge_card+' '+self.classes.field_card).
						newCardObj.removeAttr('style').
						newCardObj.css({'position': 'relative'});
						callback();
					});
				break;
			case acts['get_treasures_card']:
				var newCardObj;
				self.chain.registerCall(self.defActions.getOneCard, [$.extend(self.formCardData(resp.card_info), {id: resp.card_id, type: resp.card_type}), $('#'+resp.user_id+' .'+self.classes.hand_block)], self.defActions);
				if (resp.user_id == self.userId) {
					self.chain.registerCall(function(newCard, callback) { newCardObj = newCard; newCardObj.css({'z-index': 99999}); callback(); }).
						registerCall(self.defActions.turnOneCard, [newCardObj, {src: Params.cardPath(resp.pic_id, true)}, false], self.defActions).
						registerCall(function(callback) {
							newCardObj.attr('class', self.defClasses.hand_card+' '+self.classes.enlarge_card+' '+self.classes.hand_card);
							newCardObj.removeAttr('style')
							newCardObj.css({'position': 'relative'});
							callback();
							//if (self.userId = resp.user_id) eventsOn($('#'+self.userId));
						});
				}
				break;
			case acts['turn_card_off']:
			case acts['turn_card_on']:
				self.chain.registerCall(function(callback) { card.toggleClass('decks'); callback(); });
				break;
		}

		if (resp.user_id == self.userId) self.chain.registerCall(self.onOffActions, [card, 'off']);
		self.chain.registerCall(nextAction).run();
	}
}

CardActions.prototype.phaseActions = function(resp) {
	var self = this;
	console.log('next_phase out')
	if (resp.next_phase) {
		console.log('next_phase in')
		var curUser = (resp.next_user || false).firstKey();
		if (curUser && self.userId != curUser) {
			self.allowedActions = resp.next_user[curUser]['yes'];
			self.notAllowedActions = {};
		} else {
			var phaseActions = resp.next_phase.firstVal();
			if (phaseActions['not']) {
				self.notAllowedActions = phaseActions['not'];
				self.allowedActions = {};
			} else if (phaseActions['yes']) {
				self.allowedActions = phaseActions['yes'];
				self.notAllowedActions = {};
			} else {
				self.allowedActions = {};
				self.notAllowedActions = {};
			}
		}
		if (curUser) {
			$('.'+self.classes.player_block+' #lvl').parent().attr('class', 'label label-primary '+self.classes.user_info);
			$('#'+curUser+' #lvl').parent().attr('class', 'label label-success '+self.classes.user_info);
		}
		var phase = resp.next_phase.firstKey();
		$('#phase_name').html('Фаза: '+self.phases[phase]);
		if (phase == 'get_boss') {
			$('#your_str').html('Сила манчкина: '+self.getUserStr(resp.user_id ? resp.user_id : curUser));
			var bossStr = 0;
			$('#'+self.fieldId+' img[data-parent="monsters"]').each(function() {
				bossStr += parseInt($(this).data('lvl'));
			});
			$('#boss_str').html('Сила босса: '+bossStr);
		}
	}
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

CardActions.prototype.getUserStr = function(userId) {
	var str = 0;
	str += parseInt($('#'+userId+' #lvl').html());
	$('#'+userId+' .'+this.classes.play_block+' img').each(function() {
		if (parseInt($(this).attr('data-bonus')) && !$(this).hasClass('decks') && $(this).attr('data-parent') != 'disposables') str += parseInt($(this).attr('data-bonus'));
	});
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
