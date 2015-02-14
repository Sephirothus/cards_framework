var ajaxUrl = $('input[name="ajax_url"]').val(),
	gameId = $('input[name="game_id"]').val(),
	userId = $('input[name="user_id"]').val();

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
		restoreGame();
	}
});

$(function() {
    $(document).on({
		mouseenter: function(e) {
        	if (e.pageX > ($(window).width()/2)) var pos = 'left:0';
        	else var pos = 'right:0';
			$('body').prepend($('<img>', {
				src: Params.cardPath($(this).attr('pic_id')), 
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
		$('#'+resp.card_id).animate({
			"left": resp.card_coords.left+'px',
			"top": resp.card_coords.top+'px'
		}, 'slow', function() {
			//$('#'+resp.card_id).attr('style', '').detach().appendTo(parent);
		});
	});
}

function eventsOn(block) {
	block.find('.js_hand_card').draggable({
		//containment: '#'+userId,
		stack: '#'+userId+' .js_hand_cards',
		//axis: "x",
		cursor: 'move',
		revert: true,
		stop: function(event, ui) {
			$(this).attr('style', 'position: relative;');
		}
    });

	block.find('.js_first_row').droppable({
		accept: '#'+userId+' .js_hand_card',
		drop: function(event, ui) {
			ui.draggable.detach().appendTo($(this));
		    ui.draggable.draggable('disable');
		    ui.draggable.draggable('option', 'revert', false);
		    ui.draggable.attr('style', '');
		    ui.draggable.removeClass('on_hand js_hand_card');
		    WS.publish({
	    		card_id: ui.draggable.attr('id'), 
	    		card_coords: $('#'+ui.draggable.attr('id')).offset(), 
	    		user_id: userId
			});

		    block.find('.js_first_row').sortable({
		    	revert: true
		    });
		}
    });
}

function restoreGame() {
	setSubscribe();
	ajaxRequest(ajaxUrl, {type: 'restore_game'}, function(resp) {
		resp = resp.results;
		for (var user in resp.hand_cards) {
			for (var type in resp.hand_cards[user]) {
				for (var el in resp.hand_cards[user][type]) {
					var info = resp.hand_cards[user][type][el];
					//if (user == userId) info['id']
					var data = {
						id: info['id'] ? info['id'] : info,
						type: type,
						pic_id: info['id']
					};
					$('#'+user).find('.js_hand_cards').append(createCard(data, 'hand'));
				}
			}
			if (user == userId) eventsOn($('#'+user), userId);
		}
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

			card.attr('pic_id', resp.results[el]['id']);
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
			break;
		case 'field':
			break;
		case 'play':
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
