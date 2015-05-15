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
	};
	this.socketTypes = {
		'not_all_users': 'not_all_users',
		'start_game': 'start_game'
	};
	this.gameId = settings['gameId'];
	this.userId = settings['userId'];
	this.ajaxUrl = settings['ajaxUrl'];
	this.html;
}

CardActions.prototype.init = function() {
	var self = this, restoreGameFlag = false;
	self.html = new HtmlBuilder(self);

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
						self.turnCards(block.find('.'+self.classes.hand_block), function() {
							//eventsOn(block, self.userId);
						});
					}, 1000);
				};
			for (var el in resp.decks) {
				self.dealCards(resp.decks[el], resp.cards, !--count ? func : false);
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
	var self = this,
		overallPrice = 0;

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
			var nextId = $(this).next().attr('id');

			self.onOffActions($(this));
			if ($('#card_actions').attr('card_id') == $(this).attr('id') && $('#card_actions').is(':visible')) return false;
			self.onOffActions($(this), 'on');
		});

		// card actions click
		$(document).on('click', '#card_actions button', function() {
			var card = $('#'+$(this).parent().attr('card_id')),
				action = false,
				acts = self.actionTypes;
			switch($(this).attr('action')) {
				case 'discard':
					self.onOffActions(card);
					self.discard(card);
					if (card.hasClass(self.classes.hand_card)) action = acts['discard_from_hand'];
					else if (card.hasClass(self.classes.play_card)) action = acts['discard_from_play'];
					else if (card.hasClass(self.classes.field_card)) action = acts['discard_from_field'];
					break;
				case 'to_play':
					action = acts['from_hand_to_play'];
					self.onOffActions(card);
					self.moveCard(card, $('#'+self.userId+'.'+self.classes.player_block+' .'+self.classes.play_block), function() {
						card.attr('class', self.classes.enlarge_card+' '+self.defClasses.play_card+' '+self.classes.play_card);
					});
					break;
				case 'to_field':
					action = card.hasClass(self.classes.hand_card) ? acts['from_hand_to_field'] : acts['from_play_to_field'];
					self.onOffActions(card);
					self.moveCard(card, $('#'+self.fieldId), function() {
						card.attr('class', self.classes.enlarge_card+' '+self.defClasses.field_card+' '+self.classes.field_card);
					});
					break;
				case 'turn':
					card.toggleClass('decks');
					break;
				case 'sell':
					overallPrice += parseInt(card.attr('price'));
					if (overallPrice >= 1000) {
						if (confirm('Хотите продать карт на '+overallPrice+' и получить '+(Math.floor(overallPrice/1000))+' уровень(ня)')) {
							overallPrice = 0;

						}
					}
					console.log(overallPrice/1000)
					break;
			}
			if (action) {
				WS.publish({
		    		card_id: card.attr('id'), 
		    		card_coords: self.getPercentOffset(card), 
		    		user_id: self.userId,
		    		action: action
				});
			}
		});
	
		// discard all field cards
		$(document).on('click', '#discard_all', function() {
			$('#'+self.fieldId+' img').each(function() {
				self.discard($(this));
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

CardActions.prototype.onOffActions = function(elem, action) {
	if ($('#card_actions').length) $('#card_actions').slideUp("slow");
	this.focusCard(elem);

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
		$('#card_actions').slideDown("slow");
		this.focusCard(elem);
	}
}

CardActions.prototype.focusCard = function(card) {
	card.parent().find('img').css({'position': '', 'z-index': ''});
	if ($('#card_actions').is(':visible')) $('#'+$('#card_actions').attr('card_id')).css({'position': 'relative', 'z-index': 999});
}

CardActions.prototype.restoreGame = function() {
	var self = this;
	self.ajaxRequest(self.ajaxUrl, {type: 'restore_game'}, function(resp) {
		resp = resp.results;
		for (var attr in resp) {
			for (var user in resp[attr]) {
				for (var type in resp[attr][user]) {
					for (var el in resp[attr][user][type]) {
						var info = resp[attr][user][type][el];
						var data = {
							id: info['_id'] ? info['_id'] : info,
							type: type,
							pic_id: info['id']
						};
						if (info['price']) data['price'] = info['price'];
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
		for (var attr in resp) {
			for (var type in resp[attr]) {
				for (var el in resp[attr][type]) {
					var info = resp[attr][type][el];
					var data = {
						id: info['id'] ? info['id'] : info,
						type: type,
						pic_id: info['id']
					};
					switch (attr) {
						case 'field_cards':
							$('#'+self.fieldId).append(self.html.createCard(data, 'field'));
							break;
						case 'discards':
							var card = self.html.createCard(data, 'discard');
							$('#'+type+'_discard div').append(card);
							break;
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
		acts = self.actionTypes;
	switch (resp.action) {
		case acts['from_hand_to_play']:
			var target = $('#'+resp.user_id+'.'+self.classes.player_block+' .'+self.classes.play_block),
				callback = function() {
					self.turnOneCard(card, Params.cardPath(resp.pic_id, true), false, function() {
						card.attr('class', self.defClasses.play_card+' '+self.classes.enlarge_card+' '+self.classes.play_card);
					});
				};
			break;
		case acts['from_hand_to_field']:
			var target = $('#'+self.fieldId),
				callback = function() {
					self.turnOneCard(card, Params.cardPath(resp.pic_id, true), false, function() {
						card.attr('class', self.defClasses.field_card+' '+self.classes.enlarge_card+' '+self.classes.field_card);
					});
				};
			break;
		case acts['from_play_to_field']:
			var target = $('#'+self.fieldId),
				callback = function() {
					card.attr('class', self.defClasses.field_card+' '+self.classes.enlarge_card+' '+self.classes.field_card);
				};
			break;
		case acts['get_doors_card']:
		case acts['get_treasures_card']:
			if (resp.user_id == self.userId) {
				var cardId = resp.card_id,
					callback = function(newCard) {
						newCard.css({'z-index': 99999});
						self.turnOneCard(newCard, Params.cardPath(resp.pic_id, true), false, function() {
							newCard.attr('class', self.defClasses.hand_card+' '+self.classes.enlarge_card+' '+self.classes.hand_card);
							newCard.removeAttr('style');
							newCard.css({'position': 'relative'});
							//if (self.userId = resp.user_id) eventsOn($('#'+self.userId));
						});
					}
			} else {
				var callback, cardId = resp.card_id;
			}
			self.getOneCard(cardId, resp.card_type, $('#'+resp.user_id+'.'+self.classes.player_block+' .'+self.classes.hand_block), callback);
			return;
		case acts['discard_from_hand']:
			self.turnOneCard(card, Params.cardPath(resp.pic_id, true), 0, function() {
				self.discard(card);
			});
			return;
		case acts['discard_from_play']:
		case acts['discard_from_field']:
			self.discard(card);
			return;
	}
	if (!target) return false;
	self.moveCard(card, target, callback);
}

/**
 * TODO: totalCards вынести
 */
CardActions.prototype.dealCards = function(deckId, players, callback) {
	var self = this,
		totalCards = 0,
		i = 0,
		curUserId = players.firstKey();

	var timer = setInterval(function() {
		if (++i > players.count()) {
			i = 1;
			if (++totalCards >= 4) {
				clearInterval(timer);
				if (typeof callback == 'function') callback();
				return true;
			}
			curUserId = players.firstKey();
		}
		self.getOneCard(players[curUserId][deckId].objShift(), deckId, $('#'+curUserId+'.'+self.classes.player_block+' .'+self.classes.hand_block));
		curUserId = players.nextKey(curUserId);
	}, 50);
}

CardActions.prototype.turnCards = function(block, callback) {
	var cards = [], self = this;
	block.find('.'+self.classes.hand_card).each(function() {
		cards.push($(this).attr('id'));
	});

	self.ajaxRequest(self.ajaxUrl, {cards: cards, type: 'get_cards'}, function(resp) {
		var count = resp.results.length;
		for (var el in resp.results) {
			var card = $('#'+resp.results[el]['_id']),
				url = Params.cardPath(resp.results[el]['id'], true);

			self.turnOneCard(card, url, --count, callback);
		}
	});
}

CardActions.prototype.getOneCard = function(cardId, deckId, parent, callback) {
	var newCard = $('#'+deckId).clone().appendTo($('#'+deckId).parent()).attr('id', cardId).attr('type', deckId);
	newCard.attr('class', this.defClasses.hand_card+' '+this.classes.hand_card);
	this.moveCard(newCard, parent, function() {
		if (typeof callback == 'function') callback(newCard); 
	});
}

CardActions.prototype.turnOneCard = function(card, url, count, callback) {
	var self = this;
	card.addClass('turn_card_effect');
	card.toggleClass('turn_card_down').delay(1000).queue(function() {
		$(this).attr('src', url).removeClass('turn_card_down');
		$(this).toggleClass('turn_card_up').delay(1000).queue(function() {
			$(this).removeClass('turn_card_effect turn_card_up').addClass(self.classes.enlarge_card);
			if (!count && typeof callback == 'function') callback();
			$(this).dequeue();
		});
		$(this).dequeue();
	});
}

CardActions.prototype.discard = function(card, discardType, callback) {
	var self = this;
	if (!discardType) discardType = card.attr('type');
	this.moveCard(card, $('#'+discardType+'_discard div'), function() {
		$('#'+discardType+'_discard div img:not(#'+card.attr('id')+')').remove();
		card.attr('class', 'decks');
		if (typeof callback == 'function') callback();
	});
}

/**
 * 
 */
CardActions.prototype.moveCard = function(card, target, callback) {
	var self = this,
		pos = target.offset(),
		cardPos = card.offset();
	console.log(pos, cardPos);
	console.log(pos.left-cardPos.left+target.width()/2, pos.top-cardPos.top+target.height()/2)

	card.removeClass(self.classes.enlarge_card)
	card.css({'position':'absolute', 'z-index': 9999});
	card.animate({
		"left": pos.left-cardPos.left+target.width()/2,
		"top": pos.top-cardPos.top+target.height()/2
	}, 'slow', function() {
		card.addClass(self.classes.enlarge_card)
		card.removeAttr('style').detach().appendTo(target);
		if (typeof callback == 'function') callback();
	});
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
		if (resp.user_id == self.userId && !resp.to_all) return false;
		if (resp.count() > 0) self.actions(resp);
	});
}

CardActions.prototype.ajaxRequest = function(url, data, successFunc, beforeSendFunc, errorFunc) {
	data['game_id'] = this.gameId;
    $.ajax({
        url: url,
        type: 'POST',
        data: data,
        dataType: 'json',
        beforeSend: function() {
            if (typeof beforeSendFunc == 'function') beforeSendFunc();
        },
        success: function(resp) {
            if (typeof successFunc == 'function') successFunc(resp);
        },
        error: function() {
            if (typeof errorFunc == 'function') errorFunc();
        }
    });
}

/**
 * HtmlBuilder
 *
 *
 */ 
function HtmlBuilder(mainObj) {
	this.copyAttrs(mainObj);
}

HtmlBuilder.prototype.createCard = function(data, where) {
	var picId = data['pic_id'];
	delete data['pic_id'];

	var img = $('<img>', $.extend({
		src: picId ? Params.cardPath(picId, true) : $('#'+data['type']).attr('src')
	}, data));
	switch (where) {
		case 'hand':
			img.addClass(this.classes.hand_card+' '+this.defClasses.hand_card);
			if (picId) img.addClass(this.classes.enlarge_card);
			break;
		case 'field':
			img.addClass(this.classes.field_card+' '+this.defClasses.field_card);
			if (picId) img.addClass(this.classes.enlarge_card);
			break;
		case 'play':
			img.addClass(this.classes.play_card+' '+this.defClasses.play_card);
			if (picId) img.addClass(this.classes.enlarge_card);
			break;
		case 'discard':
			img.addClass('decks');
			break;
	}
	return img;
}

HtmlBuilder.prototype.createUserBlock = function(user, width) {
	return '<div id="'+user.id+'" class="col-md-'+width+' text-center '+this.classes.player_block+'">\
		<div class="row">\
			<div class="col-md-4 '+this.classes.hand_block+'"></div>\
			<div class="col-md-8 '+this.classes.play_block+'" style="height:100px;"></div>\
		</div>\
		<div class="row">\
			<div class="col-md-12 text-left">\
				<span class="label label-primary">\
					'+user.name+'\
					<span id="lvl">1 lvl</span>\
					<span id="sex">('+user.sex+')</span>\
				</span>\
			</div>\
		</div>\
	</div>';
}

HtmlBuilder.prototype.glyph = function(type, action, title) {
	return '<button type="button" class="btn btn-default btn-lg" action="'+action+'" title="'+title+'">\
		<span class="glyphicon glyphicon-'+type+'" aria-hidden="true"></span>\
	</button>';
}

HtmlBuilder.prototype.drawDice = function(parId) {
	$('#'+parId).append('<canvas id="dice" width="100" height="100"></canvas>');
	var ctx = document.getElementById('dice').getContext("2d");
	//ctx.fillStyle = 'blue';
	//ctx.fillRect(0,0,150,75);

	var dicex = 50;
	var dicey = 50;
	var dicewidth = 100;
	var diceheight = 100;
	var dotrad = 6;
	var dotx;
	var doty;
	ctx.beginPath();
	dotx = dicex + 3*dotrad;
	doty = dicey + 3*dotrad;
	ctx.arc(dotx,doty,dotrad,0,Math.PI*2,true);
	dotx = dicex+dicewidth-3*dotrad;
	doty = dicey+diceheight-3*dotrad;
	ctx.arc(dotx,doty,dotrad,0,Math.PI*2,true);
	ctx.closePath();
	ctx.fill();
	ctx.beginPath();
	dotx = dicex + 3*dotrad;
	doty = dicey + diceheight-3*dotrad;  //no change
	ctx.arc(dotx,doty,dotrad,0,Math.PI*2,true);
	dotx = dicex+dicewidth-3*dotrad;
	doty = dicey+ 3*dotrad;
	ctx.arc(dotx,doty,dotrad,0,Math.PI*2,true);
	ctx.closePath();
	ctx.fill();	
}
