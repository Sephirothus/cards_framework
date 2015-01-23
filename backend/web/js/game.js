$(function() {

	ajaxRequest($('input[name="ajax_url"]').val(), {'type': 'deal_cards'}, function(resp) {
		dealCards('doors', resp.players);
		dealCards('treasures', resp.players, function() {
			setTimeout(function() {
				alert('Пусть победит истинный Манчкин!');

				/*$('#0').find('.js_hand_cards').find('img').each(function() {
					$(this).css({
						'-webkit-transform' : 'rotate('+ degrees +'deg)',
	                	'-moz-transform' : 'rotate('+ degrees +'deg)',
	                	'-ms-transform' : 'rotate('+ degrees +'deg)',
	                	'transform' : 'rotate('+ degrees +'deg)'
	                });
				});*/

				$('.js_hand_card').draggable({
					containment: '#player',
					stack: '.js_hand_cards',
					//axis: "x",
					cursor: 'move',
					revert: true
			    });

			    $('.js_first_row').droppable({
					accept: '.js_hand_card',
					drop: function(event, ui) {
						ui.draggable.detach().appendTo($(this));
						//ui.draggable.addClass('correct');
					    ui.draggable.draggable('disable');
					    //$(this).droppable('disable');
					    //ui.draggable.position( { of: $(this), my: 'left top', at: 'left top' } );
					    ui.draggable.draggable('option', 'revert', false);
					    ui.draggable.attr('style', '');
					    ui.draggable.removeClass('on_hand js_hand_card');
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
		id = Object.keys(players)[0];

	var timer = setInterval(function() {
		var deck = $('#'+deckId).offset(),
			parent = $('#'+id).find('.js_hand_cards'),
			parPos = parent.offset(),
			additionalClass = 'on_hand',
			newCard = $('#'+deckId).clone().appendTo('body').attr('id', Math.random());

		newCard.addClass('js_hand_card card '+additionalClass).removeClass('decks').css({'position':'absolute', 'left': deck.left+'px', 'top': deck.top+'px'});
		newCard.animate({
			"left": parPos.left+'px',
			"top": parPos.top+'px'
		}, 'slow', function() {
			newCard.attr('style', '').detach().appendTo(parent);
		});
		if (++i >= players.length) {
			i = 0;
			if (++totalCards >= 4) {
				clearInterval(timer);
				if (typeof callback == 'function') callback();
			}
		}
		id = next(players, id);
	}, 500);
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

function next(obj, key) {
	var keys = Object.keys(obj),
		i = keys.indexOf(key);
	return i !== -1 && keys[i + 1] && obj[keys[i + 1]];
}