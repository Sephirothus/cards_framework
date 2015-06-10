/**
 * CChain class
 */
function CChain() {
	this.chain = [];
	this.objs = [];
}

CChain.prototype.registerObjs = function(objs) {
	this.objs = objs;
}

CChain.prototype.searchMethod = function(method) {
	for (var obj in this.objs) {
		if (typeof this.objs[obj][method] == 'function') return this.objs[obj];
	}
	return {};
}

CChain.prototype.registerCall = function(func, args, obj) {
	this.chain.push({'obj': obj ? obj : (typeof func == 'string' ? this.searchMethod(func): {}), 'function': func, 'args': args ? args : []});
	return this;
}

CChain.prototype.runWithCallback = function() {
	var self = this, func = self.chain.shift();
	if (func) {
		if (typeof func['function'] == 'string') func['function'] = func['obj'][func['function']];
		for (var i = func['args'].length; i < self.getCountFuncArgs(func['function']); i++) {
			func['args'].push(false);
		}
		func['args'].push(function() {
			self.runWithCallback();
		});
		func['function'].apply(func['obj'], func['args']);
	}
}

CChain.prototype.getCountFuncArgs = function(func) {
	return func.toString().split('\n')[0].match(/\(.*\)/g)[0].replace(/\(|\)/g, '').split(',').length-1;
}

CChain.prototype.run = function() {
	this.runWithCallback();
}
