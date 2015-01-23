$(function() {
	var players = [];

	$('.js_players').each(function() {
		players.push($(this).attr('id'));
	});
	ajaxRequest($('input[name="ajax_url"]').val(), {'type': 'deal_cards', 'players': players}, function(resp) {
		dealCards('doors', resp.results);
		dealCards('treasures', resp.results, function() {
			setTimeout(function() {
				//alert('Пусть победит истинный Манчкин!');
				turnCards();

				$('.js_hand_card').draggable({
					connectToSortable: ".js_first_row",
					containment: '#player',
					stack: '.js_hand_cards',
					//axis: "x",
					cursor: 'move',
					revert: true,
					//zIndex: 99999,
					stop: function(event, ui) {
						$(this).attr('style', 'position: relative;');
					}
			    });

			    $('.js_first_row').droppable({
					accept: '.js_hand_card',
					drop: function(event, ui) {
						ui.draggable.detach().appendTo($(this));
						//ui.draggable.addClass('correct');
					    ui.draggable.draggable('disable');
					    //$(this).droppable('disable');
					    ui.draggable.draggable('option', 'revert', false);
					    ui.draggable.attr('style', '');
					    ui.draggable.removeClass('on_hand js_hand_card');
					    $('.js_first_row').sortable({
					    	revert: true
					    });
					}
			    });
			}, 1000);
		});
	});

    $(document).on({
		mouseenter: function(e) {
        	if (e.pageX > ($(window).width()/2)) var pos = 'left:0';
        	else var pos = 'right:0';
			$('body').prepend($('<img>', {
				src: '/imgs/cards/'+$(this).attr('id')+'.jpg', 
				class: 'js_temp_pic', 
				style: 'z-index:9999;position:fixed;top:0;'+pos+';height:500px;'
			}));
		},
		mouseleave: function() {
			$('.js_temp_pic').remove();
		}
	}, '.js_enlarge_card');
});

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
			newCard = $('#'+deckId).clone().appendTo('body').attr('id', cardId);

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

function turnCards() {
	$('.js_hand_cards').first().find('.js_hand_card').each(function() {
		var id = $(this).attr('id');

		var block = '<div id="'+id+'" class="flip-container">\
			<div class="flipper">\
				<div class="front">\
					<img class="js_hand_card card on_hand ui-draggable ui-draggable-handle" src="'+$(this).attr('src')+'">\
				</div>\
				<div class="back">\
					<img class="js_hand_card card on_hand ui-draggable ui-draggable-handle" src="/imgs/cards/'+id+'.jpg">\
				</div>\
			</div>\
		</div>';
		$(this).before(block);
		$(this).remove();
		//$(this).addClass('turn_card_effect');
		$('.flip-container').toggleClass('turn_card');
		throw 111;
	});

	//$('.js_hand_cards').first().find('.js_hand_card').removeClass('turn_card_effect');
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
	var el = firstKey(obj);
	delete obj[el];
	return el;
}

function count(obj) {
	return Object.keys(obj).length;
}