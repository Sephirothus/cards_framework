function Dungeon(map, settings) {
	this.map = map | this.generate();
	this.width = 400;
	this.height = 400;
	this.tileSize = 10;
	this.objects = {
		// terrain
		'water': 'water',
		'rock': 'rock',
		'wall': 'wall',
		// creatures
		'enemy': 'enemy',
		// passages
		'enter': 'enter',
		'exit': 'exit',
	};
}

Dungeon.prototype.generate = function() {
	for (var i=0; i < this.width; i+=this.tileSize) {
		
	}
}

Dungeon.prototype. = function() {
	
}