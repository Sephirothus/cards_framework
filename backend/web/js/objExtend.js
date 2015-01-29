/*function nextKey(obj, key) {
	var keys = Object.keys(obj),
		i = keys.indexOf(key);
	return i !== -1 && keys[i + 1];
}

function firstKey(obj) {
	return Object.keys(obj)[0];
}

function objShift(obj) {
	var el = firstKey(obj),
		val = obj[el];
	delete obj[el];
	return val;
}

function count(obj) {
	return Object.keys(obj).length;
}*/

Object.prototype.nextKey = function(key) {
	//console.log(this);
	//throw 111
	var keys = Object.keys(this),
		i = keys.indexOf(key);
	return i !== -1 && keys[i + 1];
}