function Dice() {
	this.maxNum = 6;
	this.curNum = 5;
	this.intervals = [{'count': 20, 'time': 100}, {'count': 2, 'time': 500}];
}

Dice.prototype.randomNum = function() {
	return Math.floor((Math.random() * this.maxNum) + 1);
}

Dice.prototype.draw = function(block) {
	var html = '<div class="dice_face-'+this.curNum+'">',
		col = function(content) { return '<div class="dice_column">'+content+'</div>'; },
		pip = function(count) { var ret = ''; for (var i = 0; i < count; i++) ret += '<span class="dice_pip"></span>'; return ret; };

	switch (this.curNum) {
		case 1:
		case 2:
		case 3:
			html += pip(this.curNum);
			break;
		case 4:
			html += col(pip(2))+col(pip(2));
			break;
		case 5:
			html += col(pip(2))+col(pip(1))+col(pip(2));
			break;
		case 6:
			html += col(pip(3))+col(pip(3));
			break;
	}
	html += '</div>';
	if (block) block.html(html);
	return html;
}


Dice.prototype.throwDice = function(block, finalNum, mainCallback) {
	if (!finalNum) finalNum = this.randomNum();
	var self = this,
		intervals = self.intervals,
		val = intervals.shift(),
		intervalFunc = function(count, time, callback) {
			var curCount = 0;
			interval = setInterval(function() {
				self.curNum = self.randomNum();
				self.draw(block);
				curCount++;
				if (curCount >= count) {
					clearInterval(interval);
					callback();
				}
			}, time);
		},
		callback = function() {
			val = intervals.shift();
			if (val) intervalFunc(val['count'], val['time'], callback);
			else {
				self.curNum = finalNum;
				self.draw(block);
				if (typeof mainCallback == 'function') mainCallback();
			}
		};

	intervalFunc(val['count'], val['time'], callback);
}