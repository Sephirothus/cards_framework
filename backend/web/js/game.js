var ajaxUrl = $('input[name="ajax_url"]').val(),
	gameId = $('input[name="game_id"]').val(),
	userId = $('input[name="user_id"]').val();

var obj = new CardActions({'gameId': gameId, 'userId': userId, 'ajaxUrl': ajaxUrl});
obj.init();

/*
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
*/
