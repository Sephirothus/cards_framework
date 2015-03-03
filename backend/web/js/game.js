var ajaxUrl = $('input[name="ajax_url"]').val(),
	gameId = $('input[name="game_id"]').val(),
	userId = $('input[name="user_id"]').val(),
	flag = false;

WS.setParams({
	'topic': gameId
}).init(function(resp) {
	if (resp.type == 'not_all_users') {
		alert('Осталось '+resp.count+' игрок(ов)');
		return false;
	} else if (resp.type == 'start_game') {
		var count = resp.decks.length,
			func = function() {
				setTimeout(function() {
					$('<input/>', {
						type: 'button',
						class: 'btn btn-success',
						value: 'Начнем!'
					}).on('click', function() {
						var block = $('#'+userId);
						setSubscribe();
						turnCards(block.find('.js_hand_cards'), function() {
							eventsOn(block, userId);
						});
						$(this).remove();
					}).appendTo('#main_field');
				}, 1000);
			};
		for (var el in resp.decks) {
			dealCards(resp.decks[el], resp.cards, !--count ? func : false);
		}
	} else {
		if (!flag) restoreGame();
		setSubscribe();
		flag = true;
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
		},
		mouseleave: function() {
			$('.js_temp_pic').remove();
		}
	}, '.js_enlarge_card');
});

function setSubscribe() {
	WS.onSubscribe(function(resp) {
		console.log(resp)
		if (resp.user_id == userId) return false;
		if (resp.count() > 0) cardActions(resp);
	});
}

function cardActions(resp) {
	var card = $('#'+resp.card_id).length ? $('#'+resp.card_id) : $('#'+resp.pic_id);
	switch (resp.action) {
		case 'from_hand_to_play':
			var target = $('#'+resp.user_id+' .js_first_row'),
				callback = function() {
					turnOneCard($('#'+resp.card_id), Params.cardPath(resp.pic_id, true), false, function() {
						card.attr('class', 'card js_enlarge_card');
						card.removeAttr('style').detach().appendTo(target);
					});
				};
			break;
		case 'from_hand_to_field':
			var target = $('#main_field'),
				callback = function() {
					turnOneCard($('#'+resp.card_id), Params.cardPath(resp.pic_id, true), false, function() {
						card.attr('class', 'card js_enlarge_card');
						card.removeAttr('style').detach().appendTo(target);
					});
				};
			break;
		case 'from_play_to_field':
			var target = $('#main_field'),
				callback = function() {
					card.attr('class', 'card js_enlarge_card');
					card.removeAttr('style').detach().appendTo(target);
				};
			break;
	}
	var pos = target.offset(),
		cardPos = card.offset();
	card.css({'position':'absolute', 'z-index': 9999});
	card.animate({
		"left": cardPos.left > (pos.left+target.width()/2) ? cardPos.left-(pos.left+target.width()/2) : (pos.left+target.width()/2)-cardPos.left,
		"top": pos.top-cardPos.top+target.height()/2
	}, 'slow', function() {
		callback();
	});
}

function eventsOn(block) {
	block.find('.js_hand_card, .js_play_card').draggable({
		//connectToSortable: '#'+userId+' .js_first_row',
		cursor: 'move',
		revert: true,
		stop: function(event, ui) {
			$(this).attr('style', 'position: relative;');
		}
    });

	block.find('.js_first_row').droppable({
		accept: '#'+userId+' .js_hand_card',
		drop: function(event, ui) {
		    ui.draggable.removeAttr('style');
		    ui.draggable.removeClass('on_hand js_hand_card');
		    ui.draggable.addClass('js_play_card');
		    ui.draggable.detach().appendTo($(this));
		    var el = $('#'+ui.draggable.attr('id')),
		    	offset = getPercentOffset(el);
		    WS.publish({
	    		card_id: ui.draggable.attr('id'), 
	    		card_coords: offset, 
	    		user_id: userId,
	    		action: 'from_hand_to_play'
			});
			block.find('.js_first_row').sortable();
		}
    });

	block.find('.js_first_row').sortable();

    $('#main_field').droppable({
		accept: '.js_hand_card, .js_play_card',
		drop: function(event, ui) {
			var action = ui.draggable.hasClass('js_hand_card') ? 'from_hand_to_field' : 'from_play_to_field';
			console.log(action)
			ui.draggable.detach().appendTo($(this));
		    ui.draggable.draggable('destroy');
		    ui.draggable.removeAttr('style');
		    ui.draggable.removeClass('on_hand js_hand_card js_play_card');
		    var el = $('#'+ui.draggable.attr('id')),
		    	offset = getPercentOffset(el);
		    WS.publish({
	    		card_id: ui.draggable.attr('id'), 
	    		card_coords: offset, 
	    		user_id: userId,
	    		action: action
			});
		}
	});
}

function getPercentOffset(el) {
	return {
    	'top': el.offset().top / $(document).height() * 100,
    	'left': el.offset().left / $(document).width() * 100,
    };
}

function restoreGame() {
	ajaxRequest(ajaxUrl, {type: 'restore_game'}, function(resp) {
		resp = resp.results;
		for (var attr in resp) {
			for (var user in resp[attr]) {
				for (var type in resp[attr][user]) {
					for (var el in resp[attr][user][type]) {
						var info = resp[attr][user][type][el];
						var data = {
							id: info['id'] ? info['id'] : info,
							type: type,
							pic_id: info['id']
						};
						switch (attr) {
							case 'hand_cards':
								$('#'+user).find('.js_hand_cards').append(createCard(data, 'hand'));
								break;
							case 'play_cards':
								$('#'+user).find('.js_first_row').append(createCard(data, 'play'));
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
							$('#main_field').append(createCard(data, 'field'));
							break;
					}
				}
			}
		}
		eventsOn($('#'+userId), userId);
	});
}

function dealCards(deckId, players, callback) {
	var totalCards = 0,
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
		var deck = $('#'+deckId).offset(),
			parent = $('#'+curUserId).find('.js_hand_cards'),
			parPos = parent.offset(),
			cardId = players[curUserId][deckId].objShift(),
			newCard = $('#'+deckId).clone().appendTo('body').attr('id', cardId).attr('type', deckId);

		newCard.addClass('js_hand_card card on_hand').removeClass('decks').css({'position':'absolute', 'left': deck.left+'px', 'top': deck.top+'px'});
		newCard.animate({
			"left": parPos.left+'px',
			"top": parPos.top+'px'
		}, 'slow', function() {
			newCard.attr('style', '').detach().appendTo(parent);
		});
		curUserId = players.nextKey(curUserId);
	}, 50);
}

function turnCards(block, callback) {
	var cards = [];
	block.find('.js_hand_card').each(function() {
		cards.push($(this).attr('id'));
	});

	ajaxRequest(ajaxUrl, {cards: cards, type: 'get_cards'}, function(resp) {
		var count = resp.results.length;
		for (var el in resp.results) {
			var card = $('#'+resp.results[el]['_id']),
				url = Params.cardPath(resp.results[el]['id'], true);

			turnOneCard(card, url, --count, callback);
		}
	});
}

function turnOneCard(card, url, count, callback) {
	card.addClass('turn_card_effect');
	card.toggleClass('turn_card_down').delay(1000).queue(function() {
		$(this).attr('src', url).removeClass('turn_card_down');
		$(this).toggleClass('turn_card_up').delay(1000).queue(function() {
			$(this).removeClass('turn_card_effect turn_card_up').addClass('js_enlarge_card');
			if (!count && typeof callback == 'function') callback();
			$(this).dequeue();
		});
		$(this).dequeue();
	});
}

function createCard(data, where) {
	var img = $('<img>', {
		id: data['id'],
		type: data['type'],
		src: data['pic_id'] ? Params.cardPath(data['pic_id'], true) : $('#'+data['type']).attr('src')
	});
	switch (where) {
		case 'hand':
			img.addClass('js_hand_card card on_hand');
			if (data['pic_id']) img.addClass('js_enlarge_card');
			break;
		case 'field':
			img.addClass('js_field_card card');
			if (data['pic_id']) img.addClass('js_enlarge_card');
			break;
		case 'play':
			img.addClass('js_play_card card');
			if (data['pic_id']) img.addClass('js_enlarge_card');
			break;
		case 'discard':
			break;
	}
	return img;
}

function ajaxRequest(url, data, successFunc, beforeSendFunc, errorFunc) {
	data['game_id'] = gameId;
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
