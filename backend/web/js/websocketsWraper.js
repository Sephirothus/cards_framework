var WS = {
	conn: false,
	url: 'ws://localhost:8080',
	topic: false,
	startGameFunc: false,
	setParams: function(params) {
		for (var i in params) {
			switch (typeof params[i]) {
				case 'function':
					var part = params[i];
					break;
				default:
					var part = '"'+params[i]+'"';
					break;
			}
			eval('this.'+i+' = '+part);
		}
		return this;
	},
	init: function() {
		if (!this.topic) return console.warn('Topic not set!');
		if (!this.conn) {
			var $this = this;
			$this.conn = new ab.Session(this.url,
		        function() {
		            $this.conn.subscribe($this.topic, function(topic, data) {
		            	if ($this.startGameFunc && data['type'] == 'start_game') {
		            		$this.startGameFunc(data);
		            	} else if (typeof data['func'] == 'function') data['func'](data['data']);
		            });
		        },
		        function() {
		            console.warn('WebSocket connection closed');
		        },
		        {'skipSubprotocolCheck': true}
		    );
		}
		return this;
	},
	publish: function(data) {
		this.conn.publish(this.topic, data);
	},
	getConn: function() {
		return this.conn;
	}
};