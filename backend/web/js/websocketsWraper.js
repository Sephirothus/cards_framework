var WS = {
	conn: false,
	url: 'ws://localhost:8080',
	topic: false,
	setParams: function(params) {
		for (var i in params) {
			eval('this.'+i+' = "'+params[i]+'"');
		}
		return this;
	},
	init: function(func) {
		if (!this.topic) return console.warn('Topic not set!');
		if (!this.conn) {
			var $this = this;
			$this.conn = new ab.Session(this.url,
				function() {
		        	$this.onSubscribe(func);
		        },
		        function() {
		            console.warn('WebSocket connection closed');
		        },
		        {'skipSubprotocolCheck': true}
		    );
		}
		return this;
	},
	onSubscribe: function(func) {
		this.unSubscribe();
		this.conn.subscribe(this.topic, function(topic, data) {
        	if (typeof func == 'function') func(data);
        });
	},
	unSubscribe: function() {
		if (this.checkIsSubscribed(this.topic)) this.conn.unsubscribe(this.topic);
	},
	checkIsSubscribed: function(topic) {
		return topic in this.conn._subscriptions ? true : false;
	},
	publish: function(data) {
		this.conn.publish(this.topic, data);
	},
	getConn: function() {
		return this.conn;
	}
};