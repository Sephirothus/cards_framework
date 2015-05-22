/**
 * Params
 *
 *
 */
var Params = {
	imgExt: 'jpg',
	cardPath: function(id, is_small) {
		return '/imgs/cards/'+id+(is_small ? '-small' : '')+'.'+Params.imgExt;
	}
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
		'get_doors_card': 'get_doors_card',
		'get_treasures_card': 'get_treasures_card',
		'discard_from_hand': 'discard_from_hand',
		'discard_from_play': 'discard_from_play',
		'discard_from_field': 'discard_from_field',
		'sell_cards': 'sell_cards',
		'turn_card': 'turn_card'
	};
	this.socketTypes = {
		'not_all_users': 'not_all_users',
		'start_game': 'start_game'
	};
	this.framesColors = {
		'sell': 'rgb(31, 122, 31)',
		'select': 'red'
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
}

CardActions.prototype.init = function() {
	var self = this, restoreGameFlag = false;
	self.html = new HtmlBuilder(self);
	self.defActions = new DefaultActions(self);

	WS.setParams({
		'topic': self.gameId
	}).init(function(resp) {
		var startUsersLen = $('.'+self.classes.player_block).length;
		self.placeUserBlocks(resp.users);
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
			self.setSubscribe();
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
					action = acts['turn_card'];
					break;
				case 'sell':
					var col = self.framesColors,
						overallPrice = 0;
					if (card.css('border-right-color') == col['sell']) card.css({'border': '5px outset '+col['select']});
					else card.css({'border': '5px outset '+col['sell']});

					$('.'+self.classes.play_card+', .'+self.classes.hand_card).filter(function() {
						return $(this).css('border-right-color') == col['sell'];
					}).each(function() {
						if ($(this).attr('price')) overallPrice += parseInt($(this).attr('price'));
					});
					console.log(overallPrice)
					if (overallPrice >= 1000) {
						if (confirm('Хотите продать карт на '+overallPrice+' и получить '+(Math.floor(overallPrice/1000))+' уровень(ня)')) {
							var cardIds = [];
							$('.'+self.classes.play_card+', .'+self.classes.hand_card).filter(function() {
								return $(this).css('border-right-color') == col['sell'];
							}).each(function() {
								action = acts['sell_cards'];
								if (parseInt($(this).attr('price')) >= 0) cardIds.push($(this).attr('id'));
								else $(this).css({'border': ''});
							});
						}
					}
					break;
			}
			if (action) {
				WS.publish({
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
				WS.publish({
		    		card_id: $(this).attr('id'), 
		    		card_coords: self.getPercentOffset($(this)), 
		    		user_id: self.userId,
		    		action: self.actionTypes['discard_from_field']
				});
			});
		});

		// get card from deck
		$(document).on('click', '#doors, #treasures', function() {
			WS.publish({
	    		card_type: $(this).attr('id'),
	    		user_id: self.userId,
	    		action: 'get_'+$(this).attr('id')+'_card'
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
			var data = {
				id: info['_id'] ? info['_id'] : info,
				type: type,
				pic_id: info['id']
			};
			if (info['price']) data['price'] = info['price'];
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
		callback;
	switch (resp.action) {
		case acts['from_hand_to_play']:
			var anotherCallback = function() { card.attr('class', self.defClasses.play_card+' '+self.classes.enlarge_card+' '+self.classes.play_card); };
			callback = function() {
				self.defActions.moveCard(card, $('#'+resp.user_id+'.'+self.classes.player_block+' .'+self.classes.play_block), function() {
					if (resp.user_id != self.userId) {
						self.defActions.turnOneCard(card, {src: Params.cardPath(resp.pic_id, true)}, false, anotherCallback);
					} else anotherCallback();
				});
			};
			break;
		case acts['from_hand_to_field']:
			var anotherCallback = function() { card.attr('class', self.defClasses.field_card+' '+self.classes.enlarge_card+' '+self.classes.field_card); };
			callback = function() {
				self.defActions.moveCard(card, $('#'+self.fieldId), function() {
					if (resp.user_id != self.userId) {
						self.defActions.turnOneCard(card, {src: Params.cardPath(resp.pic_id, true)}, false, anotherCallback);
					} else anotherCallback();
				});
			};
			break;
		case acts['from_play_to_field']:
			callback = function() {
				self.defActions.moveCard(card, $('#'+self.fieldId), function() {
					card.attr('class', self.defClasses.field_card+' '+self.classes.enlarge_card+' '+self.classes.field_card);
				});
			};
			break;
		case acts['discard_from_hand']:
		case acts['discard_from_play']:
			var anotherCallback = function() { self.defActions.discard(card); };
			if (resp.user_id != self.userId && resp.action == acts['discard_from_hand']) {
				callback = function() { 
					self.defActions.turnOneCard(card, {src: Params.cardPath(resp.pic_id, true)}, 0, anotherCallback); 
				};
			} else callback = anotherCallback;
			break;
		case acts['sell_cards']:
			callback = function() {
				var anotherCallback;
				for (var el in resp.card_id) {
					anotherCallback = function() { self.defActions.discard($('#'+resp.card_id[el])); };
					if (resp.user_id != self.userId) self.defActions.turnOneCard($('#'+resp.card_id[el]), {src: Params.cardPath(''/*resp.pic_id*/, true)}, false, anotherCallback);
					else anotherCallback();
				}
				$('#'+resp.user_id).find('#lvl').html(resp.user_lvl+' lvl');
			};
			break;
		case acts['discard_from_field']:
			self.defActions.discard(card);
			return;
		case acts['get_doors_card']:
			var cardId = resp.card_id;
			self.defActions.getOneCard({id: cardId, type: resp.card_type, price: resp.price}, $('#'+self.fieldId), function(newCard) {
				newCard.css({'z-index': 99999});
				self.defActions.turnOneCard(newCard, {src: Params.cardPath(resp.pic_id, true)}, false, function() {
					newCard.attr('class', self.defClasses.field_card+' '+self.classes.enlarge_card+' '+self.classes.field_card);
					newCard.removeAttr('style');
					newCard.css({'position': 'relative'});
				});
			});
			return;
		case acts['get_treasures_card']:
			var cardId = resp.card_id;
			if (resp.user_id == self.userId) {
				callback = function(newCard) {
					newCard.css({'z-index': 99999});
					self.defActions.turnOneCard(newCard, {src: Params.cardPath(resp.pic_id, true)}, false, function() {
						newCard.attr('class', self.defClasses.hand_card+' '+self.classes.enlarge_card+' '+self.classes.hand_card);
						newCard.removeAttr('style');
						newCard.css({'position': 'relative'});
						//if (self.userId = resp.user_id) eventsOn($('#'+self.userId));
					});
				}
			}
			self.defActions.getOneCard({id: cardId, type: resp.card_type, price: resp.price}, $('#'+resp.user_id+' .'+self.classes.hand_block), callback);
			return;
		case acts['turn_card']:
			card.toggleClass('decks');
			return;
	}

	if (resp.user_id == self.userId) {
		self.onOffActions(card, 'off', callback);
	} else if (typeof callback == 'function') callback();
}

CardActions.prototype.placeUserBlocks = function(users) {
	var self = this;
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
		//if (resp.user_id == self.userId && !resp.to_all) return false;
		if (resp.count() > 0) self.actions(resp);
	});
}
