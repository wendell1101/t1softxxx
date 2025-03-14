<?php
namespace WebPushApp;
use Illuminate\Database\Capsule\Manager as Capsule;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;

class LiveMessage implements MessageComponentInterface {

	// protected $subscribedTopics = array();

	// public function onSubscribe(ConnectionInterface $conn, $topic) {
	// 	$this->subscribedTopics[$topic->getId()] = $topic;
	// }

	// /**
	//  * @param string JSON'ified string we'll receive from ZeroMQ
	//  */
	// public function onLiveMessage($entry) {
	// 	$entryData = json_decode($entry, true);

	// 	// If the lookup topic object isn't set there is no one to publish to
	// 	// if (!array_key_exists($entryData['category'], $this->subscribedTopics)) {
	// 	// 	return;
	// 	// }

	// 	$topic = $this->subscribedTopics[$entryData['category']];

	// 	// re-send the data to all the clients subscribed to that category
	// 	$topic->broadcast($entryData);
	// }

	// protected $clients;

	protected $callerIdMapClients = array();
	// protected $adminIdMapClients = array();

	const STATUS_NEW_JOB = 2;
	const STATUS_DONE = 3;
	const STATUS_READ = 4;

	//1=admin, 2=player, 3=system
	const CALLER_TYPE_ADMIN = 1;
	const CALLER_TYPE_PLAYER = 2;
	const CALLER_TYPE_SYSTEM = 3;

	// public $test = 'TEST from live message';

	protected function logdebug($msg) {
		echo $msg . "\n";
	}

	protected function startsWith($haystack, $needle) {
		$length = strlen($needle);
		return (substr($haystack, 0, $length) === $needle);
	}

	protected function endsWith($haystack, $needle) {
		$length = strlen($needle);
		if ($length == 0) {
			return true;
		}

		return (substr($haystack, -$length) === $needle);
	}

	protected function nowStr() {
		$d = new \DateTime;
		return $d->format('Y-m-d H:i:s');
	}

	protected function nowSub($interval) {
		$d = new \DateTime;
		$d->sub(new \DateInterval($interval));
		return $d->format('Y-m-d H:i:s');
	}

	protected function nowAdd($interval) {
		$d = new \DateTime;
		$d->add(new \DateInterval($interval));
		return $d->format('Y-m-d H:i:s');
	}

	protected function getPlayerIdFromToken($token) {
		//LOAD FROM DB
		//$users = Capsule::table('player')->where('votes', '>', 100)->get();
		//before 6 hours
		$loginToken = Capsule::table('player_login_token')->select('player_id')->where('token', '=', $token)->first();
		// where('created_at', '>=', $this->nowSub('PT6H'))->first();
		if ($loginToken) {
			return $loginToken->player_id;
		}
		return null;
	}

	protected function getAdminIdFromToken($token) {
		$loginToken = Capsule::table('admin_login_token')->select('admin_id')->where('token', '=', $token)->first();
		// where('created_at', '>=', $this->nowSub('PT6H'))->first();
		if ($loginToken) {
			return $loginToken->admin_id;
		}
		return null;
	}

	protected function getMessageListFromPlayer($playerId) {
		return array();
	}

	protected function getMessageListFromAdmin($adminId) {
		return array();
	}

	protected function getQueueResultFromToken($jobToken) {
		$job = Capsule::table('queue_results')->where('token', '=', $jobToken)->where('status', '!=', self::STATUS_NEW_JOB)->first();
		if ($job) {
			$jsonStr = $job->result;
			$result = null;
			if (!empty($jsonStr)) {
				$result = json_decode($jsonStr, true);
			}
			//remove result from job
			$job->result = null;
			return array($result, $job);
		}
		return array(null, null);
	}

	protected function setReadToQueueResult($jobToken) {
		return Capsule::table('queue_results')->where('token', '=', $jobToken)->where('status', '!=', self::STATUS_NEW_JOB)->update(array('status' => self::STATUS_READ, 'updated_at' => $this->nowStr()));
	}

	protected function setUnreadToQueueResult($jobToken) {
		return Capsule::table('queue_results')->where('token', '=', $jobToken)->where('status', '!=', self::STATUS_NEW_JOB)->update(array('status' => self::STATUS_DONE, 'updated_at' => $this->nowStr()));
	}

	// protected function getQueueResultFromToken($jobToken) {
	// 	//query from queue_results
	// 	$job = Capsule::table('queue_results')->select('result')->where('token', '=', $jobToken)->where('status', self::STATUS_DONE)->first();
	// 	if ($job) {
	// 		$jsonStr = $job->result;
	// 		if (!empty($jsonStr)) {
	// 			return json_decode($jsonStr, true);
	// 		}
	// 	}
	// 	return null;
	// }

	protected function isPlayerType($type) {
		return $this->startsWith($type, 'player:');
	}

	protected function isAdminType($type) {
		return $this->startsWith($type, 'admin:');
	}

	protected function isSystemType($type) {
		return $this->startsWith($type, 'system:');
	}

	protected function getCallerUniqueId($callerType, $caller) {
		return $callerType . ":" . $caller;
	}

	/**
	 *
	 * @return \SplObjectStorage
	 */
	protected function getTargetClients($jobInfo) {
		// $targetType = $queueResult->caller_type;
		// $targetClients = null;
		// if ($targetType == self::CALLER_TYPE_ADMIN) {
		// 	$adminId = $queueResult->caller;
		// 	$targetClients = $this->callerIdMapClients[$adminId];
		// } else if ($targetType == self::CALLER_TYPE_PLAYER) {
		// 	$playerId = $queueResult->caller;
		// 	$targetClients = $this->callerIdMapClients[$playerId];
		// }
		$id = $this->getCallerUniqueId($jobInfo->caller_type, $jobInfo->caller);
		$this->logdebug('getTargetClients id:' . $id);
		return @$this->callerIdMapClients[$id];
	}

	protected function addConnByPlayerId($playerId, ConnectionInterface $conn) {
		return $this->addConnById(self::CALLER_TYPE_PLAYER, $playerId, $conn);
	}

	protected function addConnByAdminId($adminId, ConnectionInterface $conn) {
		return $this->addConnById(self::CALLER_TYPE_ADMIN, $adminId, $conn);
	}

	protected function addConnById($callerType, $caller, ConnectionInterface $conn) {
		$map = &$this->callerIdMapClients;
		$id = $this->getCallerUniqueId($callerType, $caller);
		if (!array_key_exists($id, $map)) {
			$map[$id] = new \SplObjectStorage;
		}
		$this->logdebug("add to " . $id);
		$map[$id]->attach($conn);
		return true;
	}

	protected function removeConn(ConnectionInterface $conn, $callerType = null, $caller = null) {
		if ($callerType && $caller) {
			$id = $this->getCallerUniqueId($callerType, $caller);
			if (array_key_exists($id, $this->callerIdMapClients)) {
				$this->callerIdMapClients[$id]->detach($conn);
			}
		} else {
			foreach ($this->callerIdMapClients as $id => $clients) {
				$clients->detach($conn);
			}

		}
		return true;
	}

	protected function getConnMapInfo() {
		return count($this->callerIdMapClients);
		// $str = "";
		// foreach ($this->callerIdMapClients as $id => $clients) {
		// 	$str .= $id . "=>";
		// 	if ($clients) {
		// 		$str .= $clients->count() . "\n";
		// 	} else {
		// 		$str .= "null\n";
		// 	}
		// }
		// return $str;
	}

	public function __construct() {
		// $this->clients = new \SplObjectStorage;
		$this->capsule = new Capsule;

		//load config
		define('BASEPATH', realpath(dirname(__FILE__) . '/../../../'));
		define('APPPATH', BASEPATH . '/application');
		require dirname(__FILE__) . '/../../../application/config/constants.php';
		$file_path = dirname(__FILE__) . '/../../../application/config/config.php';
		require $file_path;
		if (!isset($config) OR !is_array($config)) {
			exit('Your config file does not appear to be formatted correctly.');
		}
		$this->config = $config;

		$this->capsule->addConnection(array(
			'driver' => 'mysql',
			'host' => $config['db.default.hostname'],
			'database' => $config['db.default.database'],
			'username' => $config['db.default.username'],
			'password' => $config['db.default.password'],
			'charset' => $config['db.default.char_set'],
			'collation' => $config['db.default.dbcollat'],
			'prefix' => $config['db.default.dbprefix'],
		));

		$this->capsule->setAsGlobal();

		$this->capsule->bootEloquent();
	}

	public function onOpen(ConnectionInterface $conn) {
		// Store the new connection to send messages to later
		// $this->clients->attach($conn);
		// $player_id = $conn->Session->get('player_id');

		// $this->logdebug("player_id: $player_id");

		$this->logdebug("New connection! ({$conn->resourceId}), map conn:" . $this->getConnMapInfo());
	}

	public function onMessage(ConnectionInterface $from, $msg) {
		$this->logdebug($msg);

		$cmd = json_decode($msg, true);
		$type = $cmd['type'];
		$playerId = null;
		$adminId = null;
		$valided = false;
		if ($this->isPlayerType($type)) {
			$playerId = $this->getPlayerIdFromToken($cmd['token']);
			$valided = !empty($playerId);
		} else if ($this->isAdminType($type)) {
			$adminId = $this->getAdminIdFromToken($cmd['token']);
			$valided = !empty($adminId);
		} else if ($this->isSystemType($type)) {
			$valided = true;
		}
		if ($valided) {
			// $this->logdebug('callerIdMapClients:' . var_export($this->callerIdMapClients, true));
			// if ($type == 'login') {
			// 	//login by token
			// 	$playerId = $this->getPlayerIdFromToken($cmd['token']);
			// 	// $from->Session->set('player_id', $playerId);
			// 	$from->send(json_encode(array("success" => !empty($playerId), "type" => $type, "player_id" => $playerId)));
			// } else
			$success = false;
			$result = null;
			$msgId = null;
			if ($type == 'system:push_queue_result') {
				$jobToken = @$cmd['job_token'];
				//search clients for player/admin and send back
				list($queueResult, $jobInfo) = $this->getQueueResultFromToken($jobToken);
				$this->logdebug('get result from token:' . $cmd['job_token'] . ' info:' . var_export($jobInfo, true));
				if ($jobInfo && $queueResult) {
					$targetClients = $this->getTargetClients($jobInfo);
					// $this->logdebug('get target clients:' . $targetClients);
					if ($targetClients && $targetClients->count() > 0) {
						$result = array("job_result" => $queueResult, "job_info" => $jobInfo);
						foreach ($targetClients as $targetClient) {
							$targetClient->send(json_encode(array("success" => true, "type" => "system:push_queue_result", "result" => $result)));
						}
						//set read to queue result
						$this->setReadToQueueResult($jobToken);
					} else {
						$this->logdebug('no more clients');
					}
					$success = true;
				}

				// $result = null;
			} else if ($type == 'player:login') {
				//map player_id to client conn
				$success = $this->addConnByPlayerId($playerId, $from);
				$this->logdebug("login, conn map: " . $this->getConnMapInfo());
			} else if ($type == 'player:logout') {
				$success = $this->removeConn($from, self::CALLER_TYPE_PLAYER, $playerId);
				$this->logdebug("logout, conn map: " . $this->getConnMapInfo());
			} else if ($type == 'player:query_job_result') {
				$jobToken = @$cmd['job_token'];
				list($queueResult, $jobInfo) = $this->getQueueResultFromToken($jobToken);
				if ($queueResult) {
					$this->setReadToQueueResult($jobToken);
					$result = array("job_result" => $queueResult, "job_info" => $jobInfo);
				}
				$success = !empty($result);
			} else if ($type == 'player:query_message') {
				$result = $this->getMessageListFromPlayer($playerId);
				// $this->logdebug('result:' . var_export($result, true));
				$success = $result !== false;
				// $this->logdebug('success:' . $success);
			} else if ($type == 'admin:login') {
				//map player_id to client conn
				$success = $this->addConnByAdminId($adminId, $from);
			} else if ($type == 'admin:logout') {
				$success = $this->removeConn($from, self::CALLER_TYPE_ADMIN, $adminId);
			} else if ($type == 'admin:query_job_result') {
				$jobToken = @$cmd['job_token'];
				list($queueResult, $jobInfo) = $this->getQueueResultFromToken($jobToken);
				if ($queueResult) {
					$this->setReadToQueueResult($jobToken);
					$result = array("job_result" => $queueResult, "job_info" => $jobInfo);
				}
				$success = !empty($result);
			} else if ($type == 'admin:query_message') {
				$result = $this->getMessageListFromAdmin($adminId);
				$success = $result !== false;
			}

			$returnMsg = array("success" => $success, "type" => $type, "result" => $result, "msg_id" => $msgId);
			// $this->logdebug('return msg:' . var_export($returnMsg, true));
			$from->send(json_encode($returnMsg));

			// $player_id = $from->Session->get('player_id');

			// $this->logdebug("player_id: $player_id");

			// $numRecv = count($this->clients) - 1;
			// echo sprintf('Connection %d sending message "%s" to %d other connection%s' . "\n"
			// 	, $from->resourceId, $msg, $numRecv, $numRecv == 1 ? '' : 's');

			// foreach ($this->clients as $client) {
			// 	if ($from !== $client) {
			// 		// The sender is not the receiver, send to each client connected
			// 		$client->send($msg);
			// 	}
			// }
		} else {
			$msgId = null; //auth failed
			$this->logdebug('return failed');
			$from->send(json_encode(array("success" => false, "type" => $type, "msg_id" => $msgId)));
		}
	}

	public function onClose(ConnectionInterface $conn) {
		// The connection is closed, remove it, as we can no longer send it messages
		// $this->clients->detach($conn);

		$this->removeConn($conn);

		$this->logdebug("Connection {$conn->resourceId} has disconnected, conn map: " . count($this->getConnMapInfo()));
	}

	public function onError(ConnectionInterface $conn, \Exception $e) {
		$this->logdebug("An error has occurred: {$e->getMessage()}");

		$conn->close();
	}

}
///END OF FILE//////////