/*
1. переделать структуру игрального стола и после обновить метод actions
2. возможно обернуть все картинки в дивки
3. перебор приходящего массива, в restoreGame, должен быть универсальный
4. создать настройки дефолтных классов для разных позиций карт (на руке, в игре, в поле)
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
		'get_treasures_card': 'get_treasures_card'
	};
	this.socketTypes = {
		'not_all_users': 'not_all_users',
		'start_game': 'start_game'
	};
	this.gameId = settings['gameId'];
	this.userId = settings['userId'];
	this.ajaxUrl = settings['ajaxUrl'];
}

CardActions.prototype.init = function() {
	var self = this, restoreGameFlag = false;

	WS.setParams({
		'topic': self.gameId
	}).init(function(resp) {
		self.placeUserBlocks(resp.users);
		if (resp.type == self.socketTypes['not_all_users']) {
			alert('Осталось '+resp.count+' игрок(ов)');
			return false;
		} else if (resp.type == self.socketTypes['start_game']) {
			self.setSubscribe();
			var count = resp.decks.length,
				func = function() {
					setTimeout(function() {
						var block = $('#'+self.userId);
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

	$(function() {
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
				if ($('#card_actions').length == 0) $(this).css({'position': '', 'z-index': ''});
				$('.js_temp_pic').remove();
			}
		}, '.js_enlarge_card');

		$(document).on('click', '#'+self.userId+' img', function() {
			var nextId = $(this).next().attr('id');

			self.onOffActions($(this));
			if (nextId == 'card_actions') return false;
			self.onOffActions($(this), 'on');
		});

		$(document).on('click', '#card_actions span', function() {
			switch($(this).attr('action')) {
				case 'discard':
					var card = $('#'+$(this).parent().attr('card_id'));
					self.moveCard(card, $('#'+card.attr('type')+'_discard div'), function() {
						card.attr('class', 'decks');
						self.onOffActions(card);
					});
					break;
				case 'sell':
					
					break;
			}
			/*WS.publish({
	    		card_id: $(this).attr('id'), 
	    		card_coords: self.getPercentOffset($(this)), 
	    		user_id: self.userId,
	    		action: action,
	    		to_all: true
			});*/
		});

		$(document).on('click', '.js_card_new_place', function() {

		});

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
	var play_block = $('#'+this.userId+'.'+this.classes.player_block+' .'+this.classes.play_block),
		field_block = $('#'+this.fieldId);

	if (action == 'on') {
		var pos = this.getPosition(elem), height = elem.height();
		elem.after($('<div>', {
			css: $.extend({'z-index': 999, 'position': 'absolute'}, pos),
			id: 'card_actions',
			card_id: elem.attr('id'),
			html: '<span class="glyphicon glyphicon-remove" action="discard" aria-hidden="true"></span>\
				<span class="glyphicon glyphicon-usd" action="sell" aria-hidden="true"></span>\
				<span class="glyphicon glyphicon-refresh" action="change" aria-hidden="true"></span>\
				<span class="glyphicon glyphicon-retweet" action="turn" aria-hidden="true"></span>'
		}));
		play_block.addClass('js_card_new_place').css({'border': '1px solid red'});
		field_block.addClass('js_card_new_place').css({'border': '1px solid red'});
		elem.css({'position': 'relative', 'z-index': 999});
	} else {
		$('#card_actions').remove();
		elem.css({'position': '', 'z-index': ''});
		play_block.removeClass('js_card_new_place').css({'border': ''});
		field_block.removeClass('js_card_new_place').css({'border': ''});
	}
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
						switch (attr) {
							case 'hand_cards':
								$('#'+user+'.'+self.classes.player_block).find('.'+self.classes.hand_block).append(self.createCard(data, 'hand'));
								break;
							case 'play_cards':
								$('#'+user+'.'+self.classes.player_block).find('.'+self.classes.play_block).append(self.createCard(data, 'play'));
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
							$('#'+self.fieldId).append(self.createCard(data, 'field'));
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
			var target = $('#'+resp.user_id+' .'+self.classes.play_block),
				callback = function() {
					self.turnOneCard(card, Params.cardPath(resp.pic_id, true), false, function() {
						card.attr('class', self.defClasses.play_card+' '+self.classes.enlarge_card+' '+self.classes.play_card);
					});
				};
			break;
		case acts['from_hand_to_field']:
			var target = $('#main_field'),
				callback = function() {
					self.turnOneCard(card, Params.cardPath(resp.pic_id, true), false, function() {
						card.attr('class', self.defClasses.field_card+' '+self.classes.enlarge_card+' '+self.classes.field_card);
					});
				};
			break;
		case acts['from_play_to_field']:
			var target = $('#main_field'),
				callback = function() {
					card.attr('class', self.defClasses.field_card+' '+self.classes.enlarge_card+' '+self.classes.field_card);
				};
			break;
		case acts['get_doors_card']:
		case acts['get_treasures_card']:
			if (resp.user_id == self.userId) {
				var cardId = resp.pic_id,
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
			self.getOneCard(cardId, resp.card_type, $('#'+resp.user_id).find('.'+self.classes.hand_block), callback);
			return false;
			break;
	}
	if (!target) return false;
	self.moveCard(card, target, callback);
}

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
		self.getOneCard(players[curUserId][deckId].objShift(), deckId, $('#'+curUserId).find('.'+self.classes.hand_block));
		curUserId = players.nextKey(curUserId);
	}, 50);
}

CardActions.prototype.getOneCard = function(cardId, deckId, parent, callback) {
	var deck = $('#'+deckId).offset(),
		parPos = parent.offset(),
		newCard = $('#'+deckId).clone().appendTo('body').attr('id', cardId).attr('type', deckId);
	newCard.attr('class', this.defClasses.hand_card+' '+this.classes.hand_card).css({'position':'absolute', 'left': deck.left+'px', 'top': deck.top+'px'});
	newCard.animate({
		"left": (parPos.left+parent.width()/2)+'px',
		"top": parPos.top+'px'
	}, 'slow', function() {
		newCard.removeAttr('style').detach().appendTo(parent);
		if (typeof callback == 'function') callback(newCard); 
	});
	/*this.moveCard(newCard, parent, function() {
		if (typeof callback == 'function') callback(newCard); 
	});*/
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

/**
 * TODO: universal left, top positions
 */
CardActions.prototype.moveCard = function(card, target, callback) {
	var pos = target.offset(),
		cardPos = card.offset();
	card.css({'position':'absolute', 'z-index': 9999});
	card.animate({
		"left": cardPos.left > (pos.left+target.width()/2) ? cardPos.left-(pos.left+target.width()/2) : (pos.left+target.width()/2)-cardPos.left,
		"top": pos.top-cardPos.top+target.height()/2
	}, 'slow', function() {
		if (typeof callback == 'function') callback();
		card.removeAttr('style').detach().appendTo(target);
	});
}

CardActions.prototype.createUserBlock = function(user, width) {
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

CardActions.prototype.placeUserBlocks = function(users) {
	var self = this;
	for (var el in users) {
		users[el]['id'] = el;
		var curUser = users[el];
		if (!$('#'+el).length) {
			if ($('.js_player_place:empty').length == 0) {
				var block = $('#'+this.exampleBlockId).clone();
				block.find('.js_player_place').html('');
				$('#'+this.exampleBlockId+':last').after(block);
			}
			$('.js_player_place').each(function() {
				var player_block = $(this).find('.'+self.classes.player_block),
					field = $(this).find('#'+self.fieldId);
				if (player_block.length < 2) {
					if (player_block.length > 0 && field.length > 0) return true;
					if (player_block.length > 0) player_block.removeClass('col-md-12').addClass('col-md-6');
					var width = player_block.length > 0 || field.length > 0 ? 6 : 12;
					if (field.length > 0) {
						field.removeClass('col-md-12').addClass('col-md-6');
						$(this).prepend(self.createUserBlock(curUser, width));
					} else $(this).append(self.createUserBlock(curUser, width));
					return false;
				}
			});
		}
	}
}

CardActions.prototype.createCard = function(data, where) {
	var img = $('<img>', {
		id: data['id'],
		type: data['type'],
		src: data['pic_id'] ? Params.cardPath(data['pic_id'], true) : $('#'+data['type']).attr('src')
	});
	switch (where) {
		case 'hand':
			img.addClass(this.classes.hand_card+' '+this.defClasses.hand_card);
			if (data['pic_id']) img.addClass(this.classes.enlarge_card);
			break;
		case 'field':
			img.addClass(this.classes.field_card+' '+this.defClasses.field_card);
			if (data['pic_id']) img.addClass(this.classes.enlarge_card);
			break;
		case 'play':
			img.addClass(this.classes.play_card+' '+this.defClasses.play_card);
			if (data['pic_id']) img.addClass(this.classes.enlarge_card);
			break;
		case 'discard':
			break;
	}
	return img;
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