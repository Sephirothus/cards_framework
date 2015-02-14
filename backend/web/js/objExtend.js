Object.defineProperty(Object.prototype, 'nextKey', {
	value: function(key) {
		var keys = Object.keys(this),
			i = keys.indexOf(key);
		return i !== -1 && keys[i + 1];
	}
});

Object.defineProperty(Object.prototype, 'firstKey', {
	value: function() {return Object.keys(this)[0];}
});

Object.defineProperty(Object.prototype, 'objShift', {
	value: function() {
		var el = this.firstKey(),
			val = this[el];
		delete this[el];
		return val;
	}
});

Object.defineProperty(Object.prototype, 'count', {
	value: function() {return Object.keys(this).length;}
});
