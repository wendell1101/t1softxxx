<?php
require dirname(__FILE__) . '/../vendor/autoload.php';

// use Ratchet\Http\HttpServer;
// use Ratchet\Server\IoServer;
// use Ratchet\WebSocket\WsServer;
use WebPushApp\LiveMessage;

// $server = IoServer::factory(
// 	new HttpServer(
// 		new WsServer(
// 			new LiveMessage()
// 		)
// 	),
// 	10080
// );

// $server->run();

$loop = React\EventLoop\Factory::create();
$pusher = new LiveMessage;

// Listen for the web server to make a ZeroMQ push after an ajax request
// $context = new React\ZMQ\Context($loop);
// $pull = $context->getSocket(ZMQ::SOCKET_PULL);
// $pull->bind('tcp://127.0.0.1:5555'); // Binding to 127.0.0.1 means the only client that can connect is itself
// $pull->on('message', array($pusher, 'onLiveMessage'));

// Set up our WebSocket server for clients wanting real-time updates
// $memcache = new Memcache;
// $memcache->connect('localhost', 11211);

$webSock = new React\Socket\Server($loop);
$webSock->listen(10080, '0.0.0.0'); // Binding to 0.0.0.0 means remotes can connect
$webServer = new Ratchet\Server\IoServer(
	new Ratchet\Http\HttpServer(
		new Ratchet\WebSocket\WsServer(
			$pusher
			// new Ratchet\Wamp\WampServer(
			// new Ratchet\Session\SessionProvider(
			// 	$pusher, new Symfony\Component\HttpFoundation\Session\Storage\Handler\MemcacheSessionHandler($memcache)
			// )
			// )
		)
	),
	$webSock
);

//run worker
// $factory = new Zikarsky\React\Gearman\Factory($loop);

// $factory->createWorker("127.0.0.1", 4730)->then(
// 	// on successful creation
// 	function (Zikarsky\React\Gearman\WorkerInterface $worker) {
// 		$worker->setId('Test-Client/' . getmypid());
// 		$worker->register('reverse', function (Zikarsky\React\Gearman\JobInterface $job, $worker) {
// 			echo "Job: ", $job->getHandle(), ": ", $job->getFunction(),
// 			" with ", $job->getWorkload(), "\n";

// 			echo var_export($worker->getRegisteredFunctions(), true) . "\n";
// 			return strrev($job->getWorkload());
// 		});
// 	},
// 	// error-handler
// 	function ($error) {
// 		echo "Error: $error\n";
// 	}
// );

$loop->run();

///END OF FILE//////