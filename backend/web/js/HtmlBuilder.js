function HtmlBuilder(mainObj) {
	this.copyAttrs(mainObj);
}

HtmlBuilder.prototype.createCard = function(data, where) {
	var picId = data['pic_id'];
	delete data['pic_id'];

	var img = $('<img>', $.extend({
		src: picId ? Params.cardPath(picId, true) : $('#'+data['type']).attr('src')
	}, data));
	switch (where) {
		case 'hand':
			img.addClass(this.classes.hand_card+' '+this.defClasses.hand_card);
			if (picId) img.addClass(this.classes.enlarge_card);
			break;
		case 'field':
			img.addClass(this.classes.field_card+' '+this.defClasses.field_card);
			if (picId) img.addClass(this.classes.enlarge_card);
			break;
		case 'play':
			img.addClass(this.classes.play_card+' '+this.defClasses.play_card);
			if (picId) img.addClass(this.classes.enlarge_card);
			break;
		case 'discard':
			img.addClass('decks');
			break;
	}
	return img;
}

HtmlBuilder.prototype.createUserBlock = function(user, width) {
	return '<div id="'+user.id+'" class="col-md-'+width+' text-center '+this.classes.player_block+'">\
		<div class="row">\
			<div class="col-md-4 '+this.classes.hand_block+'"></div>\
			<div class="col-md-8 '+this.classes.play_block+'" style="min-height:100px;"></div>\
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

HtmlBuilder.prototype.glyph = function(type, action, title) {
	return '<button type="button" class="btn btn-default btn-lg" action="'+action+'" title="'+title+'">\
		<span class="glyphicon glyphicon-'+type+'" aria-hidden="true"></span>\
	</button>';
}

HtmlBuilder.prototype.drawDice = function(parId) {
	$('#'+parId).append('<canvas id="dice" width="100" height="100"></canvas>');
	var ctx = document.getElementById('dice').getContext("2d");
	//ctx.fillStyle = 'blue';
	//ctx.fillRect(0,0,150,75);

	var dicex = 50;
	var dicey = 50;
	var dicewidth = 100;
	var diceheight = 100;
	var dotrad = 6;
	var dotx;
	var doty;
	ctx.beginPath();
	dotx = dicex + 3*dotrad;
	doty = dicey + 3*dotrad;
	ctx.arc(dotx,doty,dotrad,0,Math.PI*2,true);
	dotx = dicex+dicewidth-3*dotrad;
	doty = dicey+diceheight-3*dotrad;
	ctx.arc(dotx,doty,dotrad,0,Math.PI*2,true);
	ctx.closePath();
	ctx.fill();
	ctx.beginPath();
	dotx = dicex + 3*dotrad;
	doty = dicey + diceheight-3*dotrad;  //no change
	ctx.arc(dotx,doty,dotrad,0,Math.PI*2,true);
	dotx = dicex+dicewidth-3*dotrad;
	doty = dicey+ 3*dotrad;
	ctx.arc(dotx,doty,dotrad,0,Math.PI*2,true);
	ctx.closePath();
	ctx.fill();	
}