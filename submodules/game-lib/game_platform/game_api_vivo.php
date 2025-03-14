<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

class Game_api_vivo extends Abstract_game_api {

	private $api_url;
	private $casino_id;
	private $operator_id;
	private $operator_key;
	private $server_id;
	private $hash_passkey;
	private $account_number;
	private $account_pin;
	private $currentProcess;

	const URI_MAP = array(
		self::API_depositToGame => '/integrationrequest.aspx',
		self::API_withdrawFromGame => '/integrationrequest.aspx',
		self::API_queryPlayerBalance => '/integrationrequest.aspx',
		self::API_isPlayerExist => '/integrationrequest.aspx',
		self::API_syncGameRecords => '/integrationTwoWallet/GetHistoryApi.aspx',
		self::API_login => '/flash/loginplayer.aspx',
		self::API_logout => '/flash/loginplayer.aspx',
	);

	public function __construct() {
		parent::__construct();
		$this->api_url = $this->getSystemInfo('url');
		$this->casino_id = $this->getSystemInfo('casino_id');
		$this->operator_id = $this->getSystemInfo('operator_id');
		$this->operator_key = $this->getSystemInfo('operator_key');
		$this->server_id = $this->getSystemInfo('server_id');
		$this->hash_passkey = $this->getSystemInfo('hash_passkey');
		$this->account_number = $this->getSystemInfo('account_number');
		$this->account_pin = $this->getSystemInfo('account_pin');
		$this->game_launcher_url = $this->getSystemInfo('game_launcher_url');
		$this->mobile_game_launcher_url = $this->getSystemInfo('mobile_game_launcher_url');
	}

	public function getPlatformCode() {
		return VIVO_API;
	}

	public function generateUrl($apiName, $params) {
		switch($this->currentProcess){
			case "/integrationrequest.aspx":
				$url = $this->api_url.self::URI_MAP[$apiName];
			break;

			case "/flash/loginplayer.aspx":
				$params_string = http_build_query($params);

				$url = $this->api_url.self::URI_MAP[$apiName]. "?" . $params_string;
			break;
			case "/integrationTwoWallet/GetHistoryApi.aspx":
				$params_string = http_build_query($params);

				$url = $this->api_url.self::URI_MAP[$apiName]. "?" . $params_string;
			break;

			default:
				$url = $this->api_url.self::URI_MAP[$apiName];
			break;
		}

		return $url;

	}

	protected function customHttpCall($ch, $params) {
		switch($this->currentProcess){
			case "/integrationrequest.aspx":
				$this->setUpCurrl($ch,$params);
			break;
			default:
				//code
			break;
		}
	}

	//to setup Curl
	protected function setUpCurrl($ch,$params){
		$data = array(
			'Param' => $params,
		);
		$xml_object = new SimpleXMLElement("<DATA></DATA>");
		$xmlData = $this->CI->utils->arrayToXml($params, $xml_object);

		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlData);
	}

	function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null) {

		return array(false, null);
	}

	function processResultBoolean($responseResultId, $resultArr, $playerName = null) {
		$success = !empty($resultArr);
		if(isset($resultArr['Status'])){
			$success = !empty($resultArr)&&$resultArr['Status'] != 'E';
		}
		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('VIVO got error', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
		}
		return $success;
	}

	public function queryTransaction($transactionId, $extra) {
		return $this->returnUnimplemented();
	}

	public function syncPlayerAccount($username, $password, $playerId) {
		$this->CI->utils->debug_log('username', $username, 'playerId', $playerId);
		// $success = false;
		$balance = null;
		$rlt = $this->isPlayerExist($username);
		$success = $rlt['success'];
		if ($rlt['success']) {
			if ($rlt['exists']) {
				//update register flag
				$this->updateRegisterFlag($playerId, true);
			} else {
				$rlt = $this->createPlayer($username, $password, $playerId);
				$success = $rlt['success'];
				if ($rlt['success']) {
					$this->updateRegisterFlag($playerId, true);
				}
			}
		}
		if ($success) {
			//update balance
			$rlt = $this->queryPlayerBalance($username);
			$success = $rlt['success'];
			if ($success) {
				//for sub wallet
				$balance = isset($rlt['balance']) ? floatval($rlt['balance']) : null;
				if ($balance !== null) {
					//update
//					$this->updatePlayerSubwalletBalance($playerId, $balance);
				}
			}

		}
		return array('success' => $success, 'balance' => $balance);
	}

	function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
		//return $this->returnUnimplemented();
		parent::createPlayer($playerName, $playerId, $password, $email, $extra);
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$this->depositToGame($userName, 0);
		return array("success" => true, array("playerName" => $playerName));

	}

	function login($userName, $password = null) {
		$this->currentProcess = self::URI_MAP[self::API_login];
		if($password==null){
			$password = $this->getPassword($userName);
		}
		$playerIp = $this->utils->getIP();

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForLogin',
			'playerName' => $userName
		);

		$params = array(
			'LoginName' => $userName,
			'PlayerPassword' => $password['password'],
			'OperatorID' => $this->operator_id,
			'PlayerIP' => $playerIp
		);

		return $this->callApi(self::API_login, $params, $context);
	}

	protected function vivoResultTextToArray($resultTxt){
		$resultArr = array();
		$resultTxt = str_replace(array('{','}'),array('',''),$resultTxt);
		foreach(explode(",",$resultTxt) as $exVal){
			$val = explode("=",$exVal);
			$resultArr[$val[0]] = isset($val[1])?$val[1]:'';
		}
		return $resultArr;
	}

	function processResultForLogin($params) {

		$playerName = $this->getVariableFromContext($params, 'playerName');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultTxt = $this->getValueFromParams($params,'resultText');
		$resultArr =$this->vivoResultTextToArray($resultTxt);
		$success = false;
		if (isset($resultArr['Token'])) {
			$result = $resultArr;
			$success = true;
			$this->CI->utils->debug_log('processResultForLoginPlayer ', $resultTxt);
		} else {
			$this->CI->utils->debug_log('error', 'cannot login player ' . $playerName . ' VIVO');
			$result = array();
		}

		return array($success, $result);
	}

	function depositToGame($userName, $amount, $transfer_secure_id=null) {

		$this->currentProcess = self::URI_MAP[self::API_depositToGame];

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForDepositToGame',
			'playerName' => $userName,
			'amount' => $amount
		);

		$uniqueid = $int = (integer) (substr(hexdec(md5(date('Y-m-d H:i:s').$userName)),0,9)*100000000); //get a 9 digit random
		$hash = MD5($userName.$amount.$uniqueid.$this->hash_passkey);
		$password = $this->getPassword($userName);

		$params = array(
			'CasinoID' => $this->casino_id,
			'OperatorID' => $this->operator_id,
			'UserName' => $userName,
			'UserPWD' => $password['password'],
			'UserID'=> $userName,
			'AccountNumber'=> $this->account_number,
			'AccountPin'=> $this->account_pin,
			'Amount' => $amount,
			'TransactionType'=> 'DEPOSIT',
			'TransactionID'=> $uniqueid,
			'Hash'=> $hash
		);

		return $this->callApi(self::API_depositToGame, $params, $context);

	}

	function processResultForDepositToGame($params) {

		$playerName = $this->getVariableFromContext($params, 'playerName');
		$amount = $this->getVariableFromContext($params, 'amount');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultXml = $this->getResultXmlFromParams($params);
		$resultArr = json_decode(json_encode($resultXml), true);
		// $result = array();
		$result = array('response_result_id' => $responseResultId);
		$success = false;

		if ($this->processResultBoolean($responseResultId, $resultArr,$playerName)) {
			//get current sub wallet balance
			$playerBalance = $this->queryPlayerBalance($playerName);
			//for sub wallet
			$afterBalance = $playerBalance['balance'];
			$result["external_transaction_id"] = $resultArr['TransactionID'];
			$result["currentplayerbalance"] = $afterBalance;
			$result["userNotFound"] = false;
			$success = true;
			//update
			$playerId = $this->getPlayerIdInGameProviderAuth($playerName);
			if ($playerId) {
				//deposit
				$this->insertTransactionToGameLogs($playerId, $playerName, $afterBalance, $amount, $responseResultId,$this->transTypeMainWalletToSubWallet());

			} else {
				$this->CI->utils->debug_log('error', 'cannot get player id from ' . $playerName . ' getPlayerIdInGameProviderAuth');
			}

		} else {
			$result["userNotFound"] = true;
		}
		return array($success, $result);

	}

	function queryPlayerBalance($userName) {

		$this->currentProcess = self::URI_MAP[self::API_queryPlayerBalance];
		$amount = 0;

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryPlayerBalance',
			'playerName' => $userName
		);

		$uniqueid = $int = (integer) (substr(hexdec(md5(date('Y-m-d H:i:s').$userName)),0,9)*100000000); //get a 9 digit random
		$hash = MD5($userName.$amount.$uniqueid.$this->hash_passkey);
		$password = $this->getPassword($userName);

		$params = array(
			'CasinoID' => $this->casino_id,
			'OperatorID' => $this->operator_id,
			'UserName' => $userName,
			'UserPWD' => $password['password'],
			'UserID'=> $userName,
			'AccountNumber'=> $this->account_number,
			'AccountPin'=> $this->account_pin,
			'Amount' => $amount,
			'TransactionType'=> 'CHECK',
			'TransactionID'=> $uniqueid,
			'Hash'=> $hash
		);

		return $this->callApi(self::API_queryPlayerBalance, $params, $context);

	}

	function processResultForQueryPlayerBalance($params) {
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultXml = $this->getResultXmlFromParams($params);
		$resultArr = json_decode(json_encode($resultXml), true);

		$playerName = $this->getVariableFromContext($params, 'playerName');

		$success = false;
		$result = array();
		if ($this->processResultBoolean($responseResultId, $resultArr,$playerName)) {

			$success = true;
			$result['balance'] = @floatval($resultArr['Amount']);

			if ($playerId = $this->getPlayerIdInGameProviderAuth($playerName)) {
				$this->CI->utils->debug_log('query balance playerId', $playerId, 'playerName', $playerName, 'balance', $result['balance']);
			} else {
				$this->CI->utils->debug_log('cannot get player id from ' . $playerName . ' getPlayerIdInGameProviderAuth');
			}

		} else {
			$success = false;
			if (@$resultArr['error'] == 'PLAYER NOT FOUND') {
				$result['exists'] = false;
			} else {
				$result['exists'] = true;
			}
		}
		return array($success, $result);

	}


	function withdrawFromGame($userName, $amount, $transfer_secure_id=null) {

		$this->currentProcess = self::URI_MAP[self::API_withdrawFromGame];

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForWithdrawFromGame',
			'playerName' => $userName,
			'amount' => $amount
		);

		$uniqueid = $int = (integer) (substr(hexdec(md5(date('Y-m-d H:i:s').$userName)),0,9)*100000000); //get a 9 digit random
		$hash = MD5($userName.$amount.$uniqueid.$this->hash_passkey);
		$password = $this->getPassword($userName);

		$params = array(
			'CasinoID' => $this->casino_id,
			'OperatorID' => $this->operator_id,
			'UserName' => $userName,
			'UserPWD' => $password['password'],
			'UserID'=> $userName,
			'AccountNumber'=> $this->account_number,
			'AccountPin'=> $this->account_pin,
			'Amount' => $amount,
			'TransactionType'=> 'WITHDRAW',
			'TransactionID'=> $uniqueid,
			'Hash'=> $hash
		);

		return $this->callApi(self::API_withdrawFromGame, $params, $context);
	}

	function processResultForWithdrawFromGame($params) {

		$playerName = $this->getVariableFromContext($params, 'playerName');
		$amount = $this->getVariableFromContext($params, 'amount');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultXml = $this->getResultXmlFromParams($params);
		$resultArr = json_decode(json_encode($resultXml), true);
		$result = array('response_result_id' => $responseResultId);
		$success = false;
		if ($this->processResultBoolean($responseResultId, $resultArr,$playerName)) {
			//get current sub wallet balance
			$playerBalance = $this->queryPlayerBalance($playerName);

			//for sub wallet
			$afterBalance = $playerBalance['balance'];
			$result["external_transaction_id"] = $resultArr['TransactionID'];
			$result["currentplayerbalance"] = $afterBalance;
			$result["userNotFound"] = false;
			$success = true;
			//update
			$playerId = $this->getPlayerIdInGameProviderAuth($playerName);
			if ($playerId) {
				//withdraw
				$this->insertTransactionToGameLogs($playerId, $playerName, $afterBalance, $amount, $responseResultId,
					$this->transTypeMainWalletToSubWallet());

			} else {
				$this->CI->utils->debug_log('error', 'cannot get player id from ' . $playerName . ' getPlayerIdInGameProviderAuth');
			}

		} else {
			$result["userNotFound"] = true;
		}
		return array($success, $result);
	}

	function queryForwardGame($playerName, $extra=null) {
		$returnArr = $this->login($playerName);

		if (!isset($returnArr['Token'])) {
			$this->depositToGame($playerName,0); // just to create player amount zero deposit
			$this->queryForwardGame($playerName, $extra);
			$returnArr = $this->login($playerName);
		}
		$params = array(
			'token' => $returnArr['Token'],
			'operatorID' => $returnArr['OperatorID'],
			'logoSetup' => 'VIVO_LOGO',
			'serverID' => $this->server_id,
			'isPlaceBetCTA' => true,
			'language'=>$extra['language']
		);
		$params_string = http_build_query($params);
		$launcher =$this->game_launcher_url;
		if(isset($extra['platform'])){
			$launcher = $extra['platform']=="mobile"?$this->mobile_game_launcher_url:$this->game_launcher_url;
		}
		$link = $launcher . "?" . $params_string;
		$params['url'] = $link;
		$params['success'] = true;
		return $params;

	}

	function isPlayerExist($userName) {
		$this->currentProcess = self::URI_MAP[self::API_isPlayerExist];

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForIsPlayerExist',
			'playerName' => $userName,
		);

		$amount = 0;
		$uniqueid = $int = (integer) (substr(hexdec(md5(date('Y-m-d H:i:s').$userName)),0,9)*100000000); //get a 9 digit random
		$hash = MD5($userName.$amount.$uniqueid.$this->hash_passkey);
		$password = $this->getPassword($userName);

		$params = array(
			'CasinoID' => $this->casino_id,
			'OperatorID' => $this->operator_id,
			'UserName' => $userName,
			'UserPWD' => $password['password'],
			'UserID'=> $userName,
			'AccountNumber'=> $this->account_number,
			'AccountPin'=> $this->account_pin,
			'Amount' => $amount,
			'TransactionType'=> 'CHECK',
			'TransactionID'=> $uniqueid,
			'Hash'=> $hash
		);

		return $this->callApi(self::API_isPlayerExist, $params, $context);

	}

	function processResultForIsPlayerExist($params) {

		//var_dump($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultXml = $this->getResultXmlFromParams($params);
		$resultArr = json_decode(json_encode($resultXml), true);

		$result = array();
		$success = false;
		if ($resultArr['Status']=="E"||$resultArr['Status']=="S") {
			if($resultArr['Status']=="E"&&$resultArr['StatusCode']=="102"){
				$success = true;
				$result['exists'] = false;
			}else{
				$success = true;
				$result['exists'] = true;
			}
		} else {
			$result['exists'] = true;
			$result['errorcode'] = @$resultArr['Header']['ErrorCode'];
			$result['error'] = @$resultArr['Param']['ErrorDesc'];

		}

		return array($success, $result);

	}

	function syncOriginalGameLogs($token = false) {

		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
		//observer the date format
		$startDate = $startDate->format('Y-m-d H:i:s');
		$endDate = $endDate->format('Y-m-d H:i:s');

		$result = $this->syncOriginalGameLogsOnLive($startDate, $endDate); // sync original logs for live casino

		return $result;

	}

	function syncOriginalGameLogsOnLive($startDate, $endDate) {

		$this->currentProcess = self::URI_MAP[self::API_syncGameRecords];

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForSyncGameRecords',
			//'playerName' => $playerName,
			//'playerId' => $playerId,
		);

		$params = array(
			'OperatorKey' => $this->operator_key,
			'FromDate' => $startDate,
			'ToDate' => $endDate,
			'ReportType' => 'ACCOUNT_TRANSACTIONS'
		);
		return $this->callApi(self::API_syncGameRecords, $params, $context);

	}

	private function syncOriginalGameLogsOnSlots($startDate, $endDate) {
		return $this->returnUnimplemented();//not yet setup slots
	}

	protected function vivoLogsResultTextToArray($resultText){

		$resultArr = array();
		$resultText = explode("}{",$resultText);
		if(isset($resultText[1])){
			$resultText = str_replace(array('{','}'),array('',''),$resultText[1]);
			$fields = array('TransactionID','Playerloginname','TransactionDate','TransactionType','TransactionTypeID','BalanceBefore','DebitAmount','CreditAmount', 'BalanceAfter','TableRoundID','TableID','CardProviderID','CardNumber','GameName','LiveDealerGameID','RNGgameID','Currency');
			if($resultText){
				foreach(explode("[NL]",$resultText) as $index => $exVal){
					$inerval = explode(";",$exVal);
					foreach($fields as $key => $val){
						$resultArr[$index][$val] = $inerval[$key];
					}
				}
			}
		}


		return $resultArr;
	}

	function processResultForSyncGameRecords($params) {

		$this->CI->load->model(array('vivo_live_game_logs', 'player_model'));

		$resultArr = $this->vivoLogsResultTextToArray($params['resultText']);
		$responseResultId = $this->getResponseResultIdFromParams($params);

		$result = array();
		$success = true;
		if ($success) {
			$gameRecords = $resultArr;
			if ($gameRecords) {

				$availableRows = $this->CI->vivo_live_game_logs->getAvailableRows($gameRecords);
				if (isset($availableRows[0])) {
					foreach ($availableRows[0] as $record) {
						$record['Username'] = str_replace("T1T","",$record['Playerloginname']);
						$player = $this->CI->player_model->getPlayerByUsername($record['Username']);
						$record['PlayerId'] = $player->playerId;
						$record['TransactionDate'] = date('Y-m-d H:i:s',strtotime($record['TransactionDate'])); //add external_uniueid for og purposes
						$record['external_uniqueid'] = $record['TransactionID']; //add external_uniueid for og purposes
						$record['response_result_id'] = $responseResultId;

						//update BET transaction, merge BET and WIN for sync merge
						if($record['TransactionType']=='WIN:'.$record['GameName']){

							$WHERE = array(
								'TableRoundID' => $record['TableRoundID'],
								'TransactionType' => 'BET:'.$record['GameName']
							);
							$UPDATE = array(
								'CreditAmount' => $record['CreditAmount'],
								'BalanceAfter' => $record['BalanceAfter']
							);

							$this->CI->vivo_live_game_logs->updateViVoLiveGameLogs($UPDATE,$WHERE);
						}
						//End of update BET transaction

						$this->CI->vivo_live_game_logs->insertViVoLiveGameLogs($record);
					}
					$result['data'] = $availableRows[0];
				}

			}
		}
		return array($success, $result);
	}

	function syncMergeToGameLogs($token) {

		$this->CI->load->model(array('game_logs', 'player_model', 'vivo_live_game_logs'));

		$dateTimeFrom = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$dateTimeTo = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

		//observer the date format
		$startDate = $dateTimeFrom->format('Y-m-d H:i:s');
		$endDate = $dateTimeTo->format('Y-m-d H:i:s');

		$this->CI->utils->debug_log('dateTimeFrom', $startDate, 'dateTimeTo', $endDate);


		$rlt = array('success' => true);
		$result = $this->CI->vivo_live_game_logs->getViVoLiveGameLogStatistics($startDate, $endDate);

		$cnt = 0;

		if ($result) {

			foreach ($result as $vivo_data) {
				// echo "<pre>";print_r($vivo_data);exit;
				$player_id = $vivo_data->PlayerId;

				if (!$player_id) {
					continue;
				}

				$cnt++;

				$bet_amount = $vivo_data->bet_amount;
				$result_amount = $vivo_data->result_amount - $bet_amount;

				$game_description_id = $vivo_data->game_description_id;
				$game_type_id = $vivo_data->game_type_id;

				$has_both_side 	= $bet_amount >= $result_amount && $result_amount > 0 ? 1 : 0;

				$this->syncGameLogs(
					$game_type_id,
					$game_description_id,
					$vivo_data->game_code,
					$vivo_data->game_type,
					$vivo_data->game,
					$player_id,
					$vivo_data->Username,
					$bet_amount,
					$result_amount,
					null, # win_amount
					null, # loss_amount
					$vivo_data->BalanceAfter, # after_balance
					$has_both_side, # has_both_side
					$vivo_data->external_uniqueid,
					$vivo_data->date_created,
					$vivo_data->date_created,
					$vivo_data->response_result_id
				);

			}
		}

		$this->CI->utils->debug_log('syncMergeToGameLogs monitor', 'count', $cnt);
		return $rlt;
	}

	function queryPlayerInfo($playerName) {
		return $this->returnUnimplemented();
	}

	function changePassword($playerName, $oldPassword = null, $newPassword) {
		return $this->returnUnimplemented();
	}

	function blockPlayer($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$success = $this->blockUsernameInDB($playerName);
		return array("success" => true);
	}

	function unblockPlayer($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$success = $this->unblockUsernameInDB($playerName);
		return array("success" => true);
	}

	function logout($playerName, $password = null) {
		return $this->returnUnimplemented();
	}

	function updatePlayerInfo($playerName, $infos) {
		return $this->returnUnimplemented();
		// return array("success" => true);
	}

	function queryPlayerDailyBalance($playerName, $playerId, $dateFrom = null, $dateTo = null) {
		return $this->returnUnimplemented();
	}

	function queryGameRecords($dateFrom, $dateTo, $playerName = null) {
		return $this->returnUnimplemented();
	}

	function checkLoginStatus($playerName) {
		return $this->returnUnimplemented();
	}

	public function checkLoginToken($playerName, $token) {
		return $this->returnUnimplemented();

	}

	function totalBettingAmount($playerName, $dateTimeFrom, $dateTimeTo) {
		return $this->returnUnimplemented();
	}

	private function convertToDateTime($datetime) {

		if ($datetime == null) {
			return null;
		}

		// 05/05/2016 14:20:57
		$dateArr = explode('/', $datetime);
		$yearAndTime = explode(' ', $dateArr[2]);
		$year = $yearAndTime[0];
		$time = $yearAndTime[1];
		$mysqldatetime = $year . '-' . $dateArr[0] . '-' . $dateArr[1] . ' ' . $time;

		return $mysqldatetime;

	}

	// public function processGameList($game) {

	// 	$game_image = substr($game['game_code'], 3) . '.' . $this->getGameImageExtension();
	// 	$game_image = $this->checkIfGameImageExist($game_image) ? $game_image : $this->getDefaultGameImage();

	// 	return array(
	// 		'c' => $game['game_code'], # C - GAME CODE
	// 		'n' => lang($game['game_name']), # N - GAME NAME
	// 		'i' => $game_image, # I - GAME IMAGE
	// 		'g' => $game['game_type_id'], # G - GAME TYPE ID
	// 		'r' => $game['offline_enabled'] == 1, # R - TRIAL
	// 	);
	// }

}

/*end of file*/