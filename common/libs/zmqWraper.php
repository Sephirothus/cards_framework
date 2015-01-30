<?php
namespace common\libs;

//use React\ZMQ\Context;

require dirname(dirname(__DIR__)) . '/vendor/autoload.php';

class zmqWraper extends ZMQContext {

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	public function push($data) {
		$context = new ZMQContext();
        $socket = $context->getSocket(\ZMQ::SOCKET_PUSH, 'my pusher');
        $socket->connect("tcp://localhost:5555");
        $socket->send(Json::encode($data));
	}
}