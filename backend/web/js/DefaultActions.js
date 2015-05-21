function DefaultActions(mainObj) {
	this.copyAttrs(mainObj);
}

/**
 * TODO: totalCards вынести
 */
DefaultActions.prototype.dealCards = function(deckId, players, callback) {
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
		self.getOneCard({id: players[curUserId][deckId].objShift(), type: deckId}, $('#'+curUserId+' .'+self.classes.hand_block));
		curUserId = players.nextKey(curUserId);
	}, 50);
}

DefaultActions.prototype.turnCards = function(block, callback) {
	var cards = [], self = this;
	block.find('.'+self.classes.hand_card).each(function() {
		cards.push($(this).attr('id'));
	});

	self.ajaxRequest(self.ajaxUrl, {cards: cards, type: 'get_cards'}, function(resp) {
		var count = resp.results.length;
		for (var el in resp.results) {
			var card = $('#'+resp.results[el]['_id']),
				attrs = {};

			attrs['src'] = Params.cardPath(resp.results[el]['id'], true);
			if (resp.results[el]['price']) attrs['price'] = resp.results[el]['price'];
			self.turnOneCard(card, attrs, --count, callback);
		}
	});
}

DefaultActions.prototype.getOneCard = function(data, parent, callback) {
	$('#'+data['type']).parent().append(this.html.createCard(data));
	var newCard = $('#'+data['id']);
	newCard.attr('class', this.defClasses.hand_card+' '+this.classes.hand_card);
	this.moveCard(newCard, parent, function() {
		if (typeof callback == 'function') callback(newCard); 
	});
}

DefaultActions.prototype.turnOneCard = function(card, attrs, count, callback) {
	var self = this;
	card.addClass('turn_card_effect');
	card.toggleClass('turn_card_down').delay(1000).queue(function() {
		for (var el in attrs) {
			$(this).attr(el, attrs[el]);
		}
		$(this).removeClass('turn_card_down');
		$(this).toggleClass('turn_card_up').delay(1000).queue(function() {
			$(this).removeClass('turn_card_effect turn_card_up').addClass(self.classes.enlarge_card);
			if (!count && typeof callback == 'function') callback();
			$(this).dequeue();
		});
		$(this).dequeue();
	});
}

/**
 * TODO: переделать независимо от верстки
 */
DefaultActions.prototype.discard = function(card, discardType, callback) {
	if (!discardType) discardType = card.attr('type');
	this.moveCard(card, $('#'+discardType+'_discard div'), function() {
		$('#'+discardType+'_discard div img:not(#'+card.attr('id')+')').remove();
		card.attr('class', 'decks');
		if (typeof callback == 'function') callback();
	});
}

/**
 * TODO: универсальные позиции
 */
DefaultActions.prototype.moveCard = function(card, target, callback) {
	var self = this,
		pos = target.offset(),
		cardPos = card.offset();
	//console.log(pos, cardPos);
	//console.log(pos.left-cardPos.left+target.width()/2, pos.top-cardPos.top+target.height()/2)

	if (card.hasClass(self.classes.enlarge_card)) var hasEnlarge = true;
	card.removeClass(self.classes.enlarge_card).removeAttr('style').css({'position':'absolute', 'z-index': 9999});
	card.animate({
		"left": pos.left-cardPos.left+target.width()/2,
		"top": pos.top-cardPos.top+target.height()/2
	}, 'slow', function() {
		if (hasEnlarge) card.addClass(self.classes.enlarge_card)
		card.removeAttr('style').detach().appendTo(target);
		if (typeof callback == 'function') callback();
	});
}

DefaultActions.prototype.ajaxRequest = function(url, data, successFunc, beforeSendFunc, errorFunc) {
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