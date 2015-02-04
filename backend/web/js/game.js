var ajaxUrl = $('input[name="ajax_url"]').val()
	conn = WS.setParams({
		'topic': $('input[name="game_id"]').val()
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
							moveStart(resp.first_move);
							$(this).remove();
						}).appendTo('#main_field');
					}, 1000);
				};
			for (var el in resp.decks) {
				dealCards(resp.decks[el], resp.cards, !--count ? func : false);
			}
		} else {
			console.log('game in progress');
		}
	}).getConn();

$(function() {
    $(document).on({
		mouseenter: function(e) {
        	if (e.pageX > ($(window).width()/2)) var pos = 'left:0';
        	else var pos = 'right:0';
			$('body').prepend($('<img>', {
				src: Params.cardPath($(this).attr('id')), 
				class: 'js_temp_pic', 
				style: 'z-index:9999;position:fixed;top:0;'+pos+';height:500px;'
			}));
		},
		mouseleave: function() {
			$('.js_temp_pic').remove();
		}
	}, '.js_enlarge_card');
});

function moveStart(userId) {
	var block = $('#'+userId);
	turnCards(block.find('.js_hand_cards'), function() {
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
			    conn.publish({
			    	type: 'ping_pong',
			    	data: {
			    		card_id: ui.draggable.attr('id'), 
			    		coords: $('#'+ui.draggable.attr('id')).offset(), 
			    		user_id: userId
			    	}
				});

			    block.find('.js_first_row').sortable({
			    	revert: true
			    });
			}
	    });
	});
}

function dealCards(deckId, players, callback) {
	var totalCards = 0,
		i = 0,
		userId = firstKey(players);

	var timer = setInterval(function() {
		if (++i > count(players)) {
			i = 1;
			if (++totalCards >= 4) {
				clearInterval(timer);
				if (typeof callback == 'function') callback();
				return true;
			}
			userId = firstKey(players);
		}
		var deck = $('#'+deckId).offset(),
			parent = $('#'+userId).find('.js_hand_cards'),
			parPos = parent.offset(),
			cardId = objShift(players[userId][deckId]),
			newCard = $('#'+deckId).clone().appendTo('body').attr('id', cardId).attr('type', deckId);

		newCard.addClass('js_hand_card card on_hand').removeClass('decks').css({'position':'absolute', 'left': deck.left+'px', 'top': deck.top+'px'});
		newCard.animate({
			"left": parPos.left+'px',
			"top": parPos.top+'px'
		}, 'slow', function() {
			newCard.attr('style', '').detach().appendTo(parent);
		});
		userId = nextKey(players, userId);
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

			card.attr('id', resp.results[el]['id']);
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

function ajaxRequest(url, data, successFunc, beforeSendFunc, errorFunc) {
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

function nextKey(obj, key) {
	var keys = Object.keys(obj),
		i = keys.indexOf(key);
	return i !== -1 && keys[i + 1];
}

function firstKey(obj) {
	return Object.keys(obj)[0];
}

function objShift(obj) {
	var el = firstKey(obj),
		val = obj[el];
	delete obj[el];
	return val;
}

function count(obj) {
	return Object.keys(obj).length;
}
