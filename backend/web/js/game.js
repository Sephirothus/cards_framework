$(function() {

	$('#doors').click(function() {
		var parPos = $('#2').find('.js_hand_cards').offset();
		console.log(parPos)
		$(this).css({'position': 'absolute'});
		$(this).animate({
			"left": parPos.left+'px',
			"top": parPos.top+'px'
		}, 'fast');
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