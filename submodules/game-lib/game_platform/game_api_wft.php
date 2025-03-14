<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

/*
WFT_API = 36

Configuration Sample:
url: http://<domain>/api.aspx
key: <agent>
secret: <secret>
extra_info:
{
"prefix_for_username": "",
"balance_in_game_log": true,
"adjust_datetime_minutes": 0,
"wft_currency" : "RMB",
"wft_login_host" : "<host>",
"max1": "1500",
"max2": "1500",
"max3": "500",
"max4": "500",
"lim1": "1500",
"lim2": "5000",
"lim3": "1000",
"comtype" : "A",
"com1": "0",
"com2": "0",
"com3": "0",
"wft_login_lang" : "ZH-CN"
}

comtype=<Choice of A,B,C,D,E,F,4 for HDP/OU/OE>

 */
class Game_api_wft extends Abstract_game_api {
	public function getPlatformCode() {
		return WFT_API;
	}

	public function __construct() {
		parent::__construct();
		// $this->merchantname = $this->getSystemInfo('key');
		// $this->merchantcode = $this->getSystemInfo('secret');
		// $this->api_url = $this->getSystemInfo('url');
		// $this->game_url = $this->getSystemInfo('game_url');
		// $this->currency = $this->getSystemInfo('currency');
		// $this->method = self::METHOD_POST;

   	}

	# -- Implementation of API functions --
	# Reference: WFT API - 2013-04-29.pdf, Sportsbook Interation Methods.doc
	## 2.1 Create Member
	public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
		$createPlayerSuccess = parent::createPlayer($playerName, $playerId, $password, $email, $extra);
		if (!$createPlayerSuccess) {
			return array(
				'success' => false,
				'message' => 'System create user failed.',
			);
		}

		$gameUserName = $this->getGameUsernameByPlayerUsername($playerName);

		$params=array(
			'username' => $gameUserName,
			'currency' => $this->getSystemInfo('wft_currency'),
		);

		return $this->callApi('create', $params);
	}

	## 2.2 Update
	## max1=<Max bet for HDP/OU/OE>
	## max2=<Max bet for 1X2>
	## max3=<Max bet for PAR>
	## max4=<Max bet for ORT/CS/TG/HFT/FLG>
	## lim1=<Per match limit for all except PAR/ORT>
	## lim2=<Per match payout limit for PAR (payout = bet * odds)>
	## lim3=<Per match limit for ORT>
	## comtype=<Choice of A,B,C,D,E,F,4 for HDP/OU/OE>
	## com1=<Commission for HDP/OU/OE>
	## com2=<Commisson for 1X2/ORT>
	## com3=<Commission for PAR/CS/TG/HFT/FLG>
	## suspend=<0: no suspend, 1:suspend>
	## action = update
	//	http://hapi.bm.1sgames.com/api.aspx?agent=AGENT&secret=SECRET&action=update&username=USERNAME&action=update&comtype=C&com1=0&com2=0&com3=0&max1=10000&max2=10000&max3=10000&max4=10000&lim1=20000&lim2=50000&lim3=20000&suspend=0
	public function updatePlayerInfo($playerName, $infos) {
		$defaultValues = array(
			'action' => 'update',
			'max1' => $this->getSystemInfo('max1'),
			'max2' => $this->getSystemInfo('max2'),
			'max3' => $this->getSystemInfo('max3'),
			'max4' => $this->getSystemInfo('max4'),
			'lim1' => $this->getSystemInfo('lim1'),
			'lim2' => $this->getSystemInfo('lim2'),
			'lim3' => $this->getSystemInfo('lim3'),
			'comtype' => $this->getSystemInfo('comtype'), // F
			'com1' => $this->getSystemInfo('com1'),
			'com2' => $this->getSystemInfo('com2'),
			'com3' => $this->getSystemInfo('com3'),
			'suspend' => 0,
		);

		# Fill in default values
		foreach ($defaultValues as $key => $val) {
			if (!array_key_exists($key, $infos)) {
				$infos[$key] = $val;
			}
		}

		$gameUserName = $this->getGameUsernameByPlayerUsername($playerName);
		return $this->callApi('update', $infos);
	}

	## 2.3 Check Balance
	public function queryPlayerInfo($playerName) {
		# there is no other implementation of queryPlayerInfo in WFT API
		return $this->queryPlayerBalance($playerName);
	}

	public function queryPlayerBalance($playerName) {
		$gameUserName = $this->getGameUsernameByPlayerUsername($playerName);
		$result = $this->callApi('balance', array(
			'username' => $gameUserName,
		));

		return array(
			'success' => $result['success'],
			'balance' => $result['result'],
			'exists' => $result['error_code'] != '1',
		);
	}

	## 2.4 Deposit
	public function depositToGame($playerName, $amount, $transfer_secure_id=null) {
		# according to documentation, call "Check Balance" with no error before deposit
		$balanceResult = $this->queryPlayerBalance($playerName);
		if (!$balanceResult['success']) {
			return array('success' => false, 'message' => 'Check balance API call before deposit failed.');
		}

		$this->utils->debug_log("Balance Result", $balanceResult);

		$gameUserName = $this->getGameUsernameByPlayerUsername($playerName);
		$depositResult = $this->callApi('deposit', array(
			'username' => $gameUserName,
			'amount' => $amount,
			'serial' => $this->getSerial(),
		));

		# query balance again after deposit to get the current balance
		$balanceResult = $this->queryPlayerBalance($playerName);
		if (!$balanceResult['success']) {
			return array('success' => false, 'message' => 'Check balance API call after deposit failed.');
		}
		$depositResult['currentplayerbalance'] = $balanceResult['balance'];
		return $depositResult;
	}

	## 2.5 Withdraw
	public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null) {
		# according to documentation, call "Check Balance" with no error before deposit
		$balanceResult = $this->queryPlayerBalance($playerName);
		if (!$balanceResult['success']) {
			return array('success' => false, 'message' => 'Check balance API call before deposit failed.');
		}

		$gameUserName = $this->getGameUsernameByPlayerUsername($playerName);
		$withdrawResult = $this->callApi('withdraw', array(
			'username' => $gameUserName,
			'amount' => $amount,
			'serial' => $this->getSerial(),
		));

		# query balance again after deposit to get the current balance
		$balanceResult = $this->queryPlayerBalance($playerName);
		if (!$balanceResult['success']) {
			return array('success' => false, 'message' => 'Check balance API call after deposit failed.');
		}
		$withdrawResult['currentplayerbalance'] = $balanceResult['balance'];
		return $withdrawResult;
	}

	## 2.6 Login
	## Note: We return an additional parameter loginUrl that's to be used for actual login
	public function login($playerName, $password = null) {
		$gameUserName = $this->getGameUsernameByPlayerUsername($playerName);
		$result = $this->callApi('login', array(
			'username' => $gameUserName,
			'host' => $this->getSystemInfo('wft_login_host'),
			'lang' => $this->getSystemInfo('wft_login_lang'),
		));
		return array(
			'success' => $result['success'],
			'loginUrl' => $result['result'],
		);
	}

	## returns URL to enter game. Uses login API to achieve this.
	public function queryForwardGame($playerName, $extra = array()) {
		$this->utils->debug_log("WFT Game Player Name: ", $playerName);
		if (!$playerName) {
			$gameUserName = $this->getSystemInfo('wft_demo_account');
			$playerName = $this->getSystemInfo('wft_demo_account');
		} else {
			$gameUserName = $this->getGameUsernameByPlayerUsername($playerName);
		}
		$this->utils->debug_log("WFT Game User Name: ", $gameUserName);
		$result = array("success" => false, "blocked" => false, 'url' => null, 'message' => null);

		$playerId = $this->getPlayerIdInPlayer($playerName);
		// $blocked = $this->isBlocked($playerName);

		// if ($blocked) {
		// 	$result['success'] = false;
		// 	$result['blocked'] = true;
		// 	$result['message'] = 'goto_game.blocked';
		// } else {
		$this->CI->load->model(array('game_provider_auth'));
		$loginInfo = $this->CI->game_provider_auth->getOrCreateLoginInfoByPlayerId($playerId, $this->getPlatformCode());
		$this->utils->debug_log("WFT Player ID: ", $playerId);
		$this->utils->debug_log("WFT Login Info: ", $loginInfo);
		if (!$loginInfo) {
			$result['success'] = false;
			$result['message'] = 'goto_game.error';
		} else {
			//update first
			$this->updatePlayerInfo($playerName, array("username" => $gameUserName));
			$host_url = $this->getSystemInfo('wft_login_host');
			if($extra['is_mobile']){
				$host_url = $this->getSystemInfo('wft_mobile_login_host');
			}
			$loginResult = $this->callApi('login', array(
				'username' => $gameUserName,
				'host' => array_key_exists('host', $extra) ? $extra['host'] : $host_url,
				'lang' => array_key_exists('lang', $extra) ? $extra['lang'] : $this->getSystemInfo('wft_login_lang'),
			));
			$this->utils->debug_log("WFT Login Result: ", $loginResult);
			$result['success'] = $loginResult['success'];
			$result['url'] = str_replace( 'http://', 'https://', $loginResult['result'] );
			$result['iframeName'] = array_key_exists('iframeName', $extra) ? $extra['iframeName'] : 'sportFrame';
		}
		// }

		return $result;
	}

	## 2.7 Logout
	public function logout($playerName, $password = null) {
		$gameUserName = $this->getGameUsernameByPlayerUsername($playerName);
		return $this->callApi('logout', array(
			'username' => $gameUserName,
		));
	}

	## 2.8 Check Payment
	## Not part of abstract_game_api, not used at the moment
	private function checkPayment($playerName) {
		$gameUserName = $this->getGameUsernameByPlayerUsername($playerName);
		if (!$gameUserName) {
			return array('success' => false, 'message' => "Username [$playerName] not found.");
		}
		return $this->callApi('check_payment', array(
			'username' => $gameUserName,
			'serial' => $this->getSerial(),
		));
	}

	## 2.9 Check Online Users
	## if username is empty, API will return all online users, delimited by ','
	public function checkLoginStatus($playerName) {
		$gameUserName = $this->getGameUsernameByPlayerUsername($playerName);
		if (!$gameUserName) {
			return array('success' => false, 'message' => "Username [$playerName] not found.");
		}
		$result = $this->callApi('check_online', array(
			'username' => $gameUserName,
		));
		return array(
			'success' => $result['success'],
			'loginStatus' => (strpos($result['result'], $playerName) !== false),
		);
	}

	## 2.10 Ticket; 2.14 Fetch Tickets
	## Note: This sync uses 2.14 - Fetch, always syncs all tickets available.
	## I have no luck calling 2.10 - ticket (always gave error)
	public function syncOriginalGameLogs($token = null) {
		$success = true;
		do {
			# fetches max number of tickets available
			$result = $this->callApi('fetch', array());
			$numTickets = 0;
			if ($result['success']) {
				$ticketFetchIds = $this->processTickets($result['result']);
				$numTickets = count($ticketFetchIds);
				if ($numTickets > 0) {
					$this->callApi('mark_fetched', array('fetch_ids' => join(',', $ticketFetchIds)));
				}
			} else {
				$success = false;
				break;
			}
		} while ($numTickets > 0);

		return array('success' => $success);
	}

	## Process tickets, converting them to game logs
	## Returns number of tickets processed
	private function processTickets($ticketXml) {
		if (empty($ticketXml)) {
			return array();
		}

		$this->CI->load->model('wft_game_logs');

		$ticketXmlArr = explode('</ticket>', trim($ticketXml));
		$ticketFetchIds = array();
		foreach ($ticketXmlArr as $aTicketXml) {
			if (empty($aTicketXml)) {
				continue;
			}
			$aTicketXml .= '</ticket>'; # put back the delimiter
			$aTicketXmlObj = simplexml_load_string($aTicketXml);
			$ticket = $this->xml2array($aTicketXmlObj);
			$this->utils->debug_log($ticket);

			# convert match date (e.g. 07/04 00:45) to MySQL format, append with current year
			$matchDate = DateTime::createFromFormat('Y/d/m H:i', date("Y") . '/' . $ticket['match_date']);
			$ticket['match_date'] = $matchDate->format('Y-m-d H:i:s');

			# Only mark ticket as fetched if the insert is successful,
			# or the fetch id (unique) already exists in DB
			unset($ticket[""]); #unset null field from ticket
			if ($this->CI->wft_game_logs->add($ticket) ||
				$this->CI->wft_game_logs->hasFetchId($ticket['fetch_id'])
			) {
				$ticketFetchIds[] = $ticket['fetch_id'];
			}
		}

		return $ticketFetchIds;
	}

	public function syncMergeToGameLogs($token = null) {
		if ($token) {
			# get time range from sync token
			$dateTimeFrom = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
			$dateTimeTo = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
		} else { # for testing purpose, if token is not given, sync all
		$dateTimeFrom = new DateTime('2000-01-01'); # sufficiently long start time
		$dateTimeTo = new DateTime(); # default end time is today
	}

		$dateTimeFrom->modify($this->getDatetimeAdjust());
		$this->CI->utils->debug_log('dateTimeFrom', $dateTimeFrom, 'dateTimeTo', $dateTimeTo);

		# query game log
		$this->CI->load->model(array('game_logs', 'wft_game_logs'));
		$wftGameLogs = $this->CI->wft_game_logs->getGameLogs($dateTimeFrom->format('Y-m-d H:i:s'), $dateTimeTo->format('Y-m-d H:i:s'));

		$count = 0;
		$playerIdsMap = array();

		if ($wftGameLogs) {
			foreach ($wftGameLogs as $aGameLog) {
				$gameLogs = array();
				$count++;

				# get game description
				list($game_description_id, $game_type_id)
				= $this->processUnknownGame('', '', $aGameLog->game, $aGameLog->sports_type, $aGameLog->game);

				# prepare username - the username we get from tickets is appended with the agent code
				$agentCode = $this->getSystemInfo('key');
				if (preg_match('/^' . $agentCode . '(.*)/', $aGameLog->user_name, $captureGroup)) {
					$username = strtolower($captureGroup[1]);
				} else {
					$username = strtolower($aGameLog->user_name);
				}

				# prepare playerId
				if (!array_key_exists($username, $playerIdsMap)) {
					$playerIdsMap[$username] = $this->getPlayerIdInGameProviderAuth($username);
				}
				$playerId = $playerIdsMap[$username];

				# prepare other parameters
				$gameDate = $aGameLog->trans_date; # already in mysql format
				$winAmount = $aGameLog->win_amount > 0 ? $aGameLog->win_amount : 0;
				$lossAmount = $aGameLog->win_amount < 0 ? $aGameLog->win_amount : 0;

				$this->syncGameLogs($game_type_id, $game_description_id, $aGameLog->game,
					$aGameLog->sports_type,
					$aGameLog->game, $playerId, $username, $aGameLog->bet_amount,
					$aGameLog->win_amount, $winAmount, $lossAmount, 0, 0,
					$aGameLog->fetch_id, $gameDate, $gameDate, '');
			}
		}

		$this->CI->utils->debug_log("[$count] records synced successfully.");
		return array('success' => true);
	}

	private function getGameDescriptionInfo($row, $unknownGame) {
		$externalGameId = $row->game;
		return $this->processUnknownGame('', '', $row->game, '', $externalGameId, array(), $unknownGame);
	}

	# -- API functions for testing purpose --
	public function testListTickets() {
		return $this->callApi('fetch', array());
	}

	public function testGetTeamName($teamId) {
		return $this->callApi('team', array('team_id' => $teamId));
	}

	public function testGetLeagueName($leagueId) {
		return $this->callApi('league', array('league_id' => $leagueId));
	}

	# -- Implementation of functions not directly provided in API --
	public function isPlayerExist($playerName) {
		$gameUserName = $this->getGameUsernameByPlayerUsername($playerName);
		$result = $this->callApi('balance', array(
			'username' => $gameUserName,
		));

		return array(
			'success' => $result['error_code'] == '0' || $result['error_code'] == '1',
			'exists' => $result['error_code'] != '1',
		);
	}

	public function blockPlayer($playerName) {
		$gameUserName = $this->getGameUsernameByPlayerUsername($playerName);

		# update the player's suspend state, 1 = suspend i.e. block
		$result = $this->updatePlayerInfo($playerName,
			array(
				'username' => $gameUserName,
				'suspend' => 1,
			)
		);

		# update local data
		if ($result['success']) {
			$this->blockUsernameInDB($gameUserName);
		}

		return $result;
	}

	public function unblockPlayer($playerName) {
		$gameUserName = $this->getGameUsernameByPlayerUsername($playerName);

		$result = $this->updatePlayerInfo($playerName,
			array(
				'username' => $gameUserName,
				'suspend' => 0,
			)
		);

		# update local data
		if ($result['success']) {
			$this->unblockUsernameInDB($gameUserName);
		}

		return $result;
	}

	# -- Helper Functions --
	## Constructs the URL used by function callApi
	## Reference: WFT API - 2013-04-29.pdf, Sportsbook Interation Methods.doc
	public function generateUrl($apiName, $params) {
		$apiUrl = $this->getSystemInfo('url');

		# use the action value as apiName
		$params['action'] = $apiName;

		# add credentials to query params
		$params['agent'] = $this->getSystemInfo('key');
		$params['secret'] = $this->getSystemInfo('secret');

		# construct the full url
		return $apiUrl . '?' . http_build_query($params);
	}

	## Deals with the result of API call, it should return array($success, $resultArr)
	## The $resultArr will in turn be used as return value of callApi
	## Reference: WFT API - 2013-04-29.pdf
	public function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null) {
		$success = false;
		$resultArr = array('response_result_id' => $responseResultId);
		list($responseErrCode, $responseErrText, $responseResult) = $this->parseResponse($resultText);
		$success = ($responseErrCode === 0); # ErrCode = 0 is no error
		if (!empty($responseErrText)) {
			$resultArr['message'] = $responseErrText;
		}
		if (isset($responseResult)) {
			$resultArr['result'] = $responseResult;
		}
		$resultArr['error_code'] = $responseErrCode;

		$this->utils->debug_log("API call [$apiName], params and result: ", $params, array($success, $resultArr));
		return array($success, $resultArr);
	}

	## Parses the API's response XML text, and returns an array
	## with the following fields: errcode, errtext, result
	private function parseResponse($responseText) {
		$this->utils->debug_log("Response Text", $responseText);

		# Sometimes data under <result> tag needs escaping. Use CDATA to avoid parser fail.
		$responseText = str_replace("<result>", "<result><![CDATA[", $responseText);
		$responseText = str_replace("</result>", "]]></result>", $responseText);

		$xml = simplexml_load_string($responseText);
		if ($xml === false) {
			$this->utils->debug_log("ERROR: Failed parsing XML response [$responseText]");
			foreach (libxml_get_errors() as $error) {
				$this->utils->debug_log($error->message);
			}
		} else {
			return array(intval($xml->errcode), (string) $xml->errtext, (string) $xml->result);
		}
	}

	## Converts xml object into array, with index translated that of DB fields
	private $indexMapping = array(
		'fid' => 'fetch_id', 'id' => 'ticket_id', 't' => 'last_modified', 'u' => 'user_name', 'b' => 'bet_amount', 'w' => 'win_amount', 'a' => 'commission_amount', 'c' => 'commission_rate', 'ip' => 'bet_ip', 'league' => 'league_id', 'home' => 'home_id', 'away' => 'away_id', 'status' => 'danger_status', 'game' => 'game', 'odds' => 'odds', 'side' => 'side', 'info' => 'info', 'half' => 'half', 'trandate' => 'trans_date', 'workdate' => 'work_date', 'matchdate' => 'match_date', 'runscore' => 'run_score', 'score' => 'score', 'htscore' => 'ht_score', 'flg' => 'first_last_goal', 'res' => 'result', 'edesc' => 'game_desc', 'eres' => 'game_result', 'exrate' => 'exchange_rate', 'jp' => 'jp', 'oddstype' => 'odds_type', 'sportstype' => 'sports_type',
	);
	private function xml2array($xmlObject, $out = array()) {
		foreach ((array) $xmlObject as $index => $node) {
			if (empty((string) $node)) {
				$out[$this->indexMapping[$index]] = '';
			} else {
				$out[$this->indexMapping[$index]] = (is_object($node)) ? $this->xml2array($node) : $node;
			}
		}
		return $out;
	}

	## Returns a serial number used as a unique number in some API calls
	private function getSerial() {
		return round(microtime(true) * 1000);
	}

	# -- Unimplemented interface functions --
	public function changePassword($playerName, $oldPassword, $newPassword) {
		return $this->returnUnimplemented();
	}
	public function queryPlayerDailyBalance($playerName, $playerId, $dateFrom = null, $dateTo = null) {
		return $this->returnUnimplemented();
	}
	public function queryGameRecords($dateFrom, $dateTo, $playerName = null) {
		return $this->returnUnimplemented();
	}
	public function totalBettingAmount($playerName, $dateFrom, $dateTo) {
		return $this->returnUnimplemented();
	}
	public function queryTransaction($transactionId, $extra) {
		return $this->returnUnimplemented();
	}
}
