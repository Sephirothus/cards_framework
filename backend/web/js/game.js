$(function() {

	dealCards('doors');
	dealCards('treasures', function() {
		setTimeout(function() {
			alert('Пусть победит истинный Манчкин!');

			$('#0').find('.js_hand_cards').find('img').each(function() {
				$(this).css({
					'-webkit-transform' : 'rotate('+ degrees +'deg)',
                	'-moz-transform' : 'rotate('+ degrees +'deg)',
                	'-ms-transform' : 'rotate('+ degrees +'deg)',
                	'transform' : 'rotate('+ degrees +'deg)'
                });
			});

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

function dealCards(deckId, callback) {
	var totalCards = 0,
		i = 0;

	var timer = setInterval(function() {
		var deck = $('#'+deckId).offset(),
			parent = $('#'+i).find('.js_hand_cards'),
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
		if (++i >= 5) {
			i = 0;
			if (++totalCards >= 4) {
				clearInterval(timer);
				if (typeof callback == 'function') callback();
			}
		}
	}, 500);
}