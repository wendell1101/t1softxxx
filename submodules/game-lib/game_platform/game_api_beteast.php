<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

class Game_api_beteast extends Abstract_game_api {

	private $api_url;
	private $access_key;
	private $iv_key;
	private $db_key;
	private $currency;
	private $language;
	private $agent_code;

	const URI_MAP = array(
		self::API_createPlayer => 'createplayer',
		self::API_depositToGame => 'deposit',
		self::API_withdrawFromGame => 'withdraw',
		self::API_queryPlayerBalance => 'querybalance',
		self::API_syncGameRecords => 'syncgamerecords',
		self::API_changePassword => 'changepassword',
		self::API_blockPlayer => 'banplayer',
		self::API_unblockPlayer => 'unbanplayer',
		self::API_login => 'login',
		self::API_logout => 'logout'
	);

	public function __construct() {
		parent::__construct();
		$this->api_url = $this->getSystemInfo('url');
		$this->access_key = $this->getSystemInfo('ACCESS_KEY');
		$this->iv_key = $this->getSystemInfo('IV_KEY');
		$this->db_key = $this->getSystemInfo('db_key');
		$this->currency = $this->getSystemInfo('currency');
		$this->agent_code = $this->getSystemInfo('agent_code');
	}

	public function getPlatformCode() {
		return BETEAST_API;
	}

	public function encryption($encrypt, $key, $iv){
		$td = @mcrypt_module_open(MCRYPT_RIJNDAEL_256, '', MCRYPT_MODE_ECB, '');
		@mcrypt_generic_init($td, $key, $iv);
		$encrypted = @mcrypt_generic($td, $encrypt);
		$encode = base64_encode($encrypted);
		@mcrypt_generic_deinit($td);
		@mcrypt_module_close($td);
		return $encode;
	}

	protected function customHttpCall($ch, $params) {
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    }

	public function generateUrl($apiName, $params) {
		//$apiUri = self::URI_MAP[$apiName];
		$rtn = $this->encryption(json_encode($params), $this->access_key, $this->iv_key);
		$url = $this->api_url."?data={$rtn}";
		return $url;
	}

	function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null) {
		return array(false, null);
	}

	function processResultBoolean($responseResultId, $resultArr, $playerName = null) {
		$success = false;
		if($resultArr['result']==0){
			$success = true;
		}
		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('BETEAST got error ======================================>', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
		}
		return $success;
	}

	function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
		parent::createPlayer($playerName, $playerId, $password, $email, $extra);

		$userName = $this->getGameUsernameByPlayerUsername($playerName);

		if($password==null){
			$password = $this->getPassword($playerName);
		}
		if(isset($password['password'])){
			$password = $password['password'];
		}

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForCreatePlayer',
			'playerName' => $userName
		);

		$params = array(
			'requestType'=> 'create',
			'id' => $userName,
			'password' => $password,
			'agent_code' => $this->agent_code, // Agent in charge of the user
			'currency' => $this->currency,
			'key' => $this->db_key // DB KEY
		);
		return $this->callApi(self::API_createPlayer, $params, $context);
	}

	function processResultForCreatePlayer($params){
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJsonArr = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $resultJsonArr, $playerName);
		return array($success, $resultJsonArr);

	}

	function login($userName, $password = null) {

		$playerName = $this->getGameUsernameByPlayerUsername($userName);
		$password = $this->getPasswordByGameUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForLogin',
			'playerName' => $playerName
		);

		$params = array(
			'requestType'=> 'create',
			'id' => $playerName,
			'password' => $password,
			'agent_code' => $this->agent_code, // Agent in charge of the user
			'currency' => $this->currency,
			'key' => $this->db_key // DB KEY
		);
		return $this->callApi(self::API_login, $params, $context);
	}

	function processResultForLogin($params){
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJsonArr = $this->getResultJsonFromParams($params);
		// echo "<pre>";print_r($params);exit;

		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $resultJsonArr, $playerName);
		return array($success, $resultJsonArr);

	}

	function depositToGame($userName, $amount, $transfer_secure_id=null) {

		$playerName = $this->getGameUsernameByPlayerUsername($userName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForDepositToGame',
			'playerName' => $playerName,
			'sbe_playerName' => $userName,
			'amount' => $amount
		);

		$params = array(
			'requestType'=> 'trans',
			'id' => $playerName,
			'agent_code' => $this->agent_code, // Agent in charge of the user
			'cash' => $amount,
			'action' => 1,// 1 - credit in 2 - credit out
			'key' => $this->db_key // DB KEY
		);

		return $this->callApi(self::API_depositToGame, $params, $context);

	}

	function processResultForDepositToGame($params) {

		$playerName = $this->getVariableFromContext($params, 'playerName');
		$sbe_playerName = $this->getVariableFromContext($params, 'sbe_playerName');
		$amount = $this->getVariableFromContext($params, 'amount');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$result = array('response_result_id' => $responseResultId);
		$success = false;
		$this->utils->debug_log("Deposit ResultArr ============================>", $resultArr);
		$this->utils->debug_log("Deposit result from response result id ============================>", $resultArr);
		if ($this->processResultBoolean($responseResultId, $resultArr,$playerName)) {
			//get current sub wallet balance
			$playerBalance = $this->queryPlayerBalance($sbe_playerName);
			//for sub wallet
			$afterBalance = $playerBalance['balance'];
			$result["external_transaction_id"] = $resultArr['number'];
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

		$playerName = $this->getGameUsernameByPlayerUsername($userName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryPlayerBalance',
			'playerName' => $playerName
		);

		$params = array(
			'requestType'=> 'Balance',
			'id' => $playerName,
			'agent_code' => $this->agent_code, // Agent in charge of the user
			'key' => $this->db_key // DB KEY
		);

		return $this->callApi(self::API_queryPlayerBalance, $params, $context);

	}

	function processResultForQueryPlayerBalance($params) {

		$playerName = $this->getVariableFromContext($params, 'playerName');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');

		$success = false;
		$result = array();
		if ($this->processResultBoolean($responseResultId, $resultArr,$playerName)) {

			$success = true;
			$result['balance'] = @floatval($resultArr['balance']);

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

		$playerName = $this->getGameUsernameByPlayerUsername($userName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForWithdrawFromGame',
			'playerName' => $playerName,
			'sbe_playerName' => $userName,
			'amount' => $amount
		);

		$params = array(
			'requestType'=> 'trans',
			'id' => $playerName,
			'agent_code' => $this->agent_code, // Agent in charge of the user
			'cash' => $amount,
			'action' => 2,// 1 - credit in 2 - credit out
			'key' => $this->db_key // DB KEY
		);

		return $this->callApi(self::API_withdrawFromGame, $params, $context);
	}

	function processResultForWithdrawFromGame($params) {

		$playerName = $this->getVariableFromContext($params, 'playerName');
		$sbe_playerName = $this->getVariableFromContext($params, 'sbe_playerName');
		$amount = $this->getVariableFromContext($params, 'amount');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$result = array('response_result_id' => $responseResultId);
		$success = false;
		if ($this->processResultBoolean($responseResultId, $resultArr,$playerName)) {
			//get current sub wallet balance
			$playerBalance = $this->queryPlayerBalance($sbe_playerName);

			//for sub wallet
			$afterBalance = $playerBalance['balance'];
			$result["external_transaction_id"] = $resultArr['number'];
			$result["currentplayerbalance"] = $afterBalance;
			$result["userNotFound"] = false;
			$success = true;
			//update
			$playerId = $this->getPlayerIdInGameProviderAuth($playerName);
			if ($playerId) {
				//withdraw
				$this->insertTransactionToGameLogs($playerId, $playerName, $afterBalance, $amount, $responseResultId,$this->transTypeSubWalletToMainWallet());

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

		$params = array();
		$this->CI->utils->debug_log('BETEAST ===============>>', json_encode($returnArr,true));
		if($returnArr['success']&&isset($returnArr['login_token'])){
			$params['url'] = $returnArr['login_token'];
			$params['success'] = true;
		}else{
			$params['success'] = false;
			$this->CI->utils->debug_log('error', $playerName.' cannot get launch game error on login');
		}
		return $params;

	}

	function blockPlayer($playerName) {

		$playerName = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForBlockPlayer',
			'playerName' => $playerName,
		);

		$params = array(
			'requestType'=> 'ban',
			'id' => $playerName,
			'agent_code' => $this->agent_code, // Agent in charge of the user
			'action' => "Y", // Y : suspend(deactivate) , N : activate
			'key' => $this->db_key // DB KEY
		);

		return $this->callApi(self::API_blockPlayer, $params, $context);
	}

	function changePassword($playerName, $oldPassword = null, $newPassword) {

		$username = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForChangePassword',
			'playerName' => $playerName,
			'newPassword' => $newPassword,
		);

		$params = array(
			'requestType'=> 'password_change',
			'id' => $username,
			'password' => $newPassword, // new password
			'agent_code' => $this->agent_code, // Agent in charge of the user
			'key' => $this->db_key // DB KEY
		);

		return $this->callApi(self::API_changePassword, $params, $context);
	}

	function processResultForChangePassword($params){
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$newPassword = $this->getVariableFromContext($params, 'newPassword');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = json_decode($this->getResultTextFromParams($params),true);
		$result = array('response_result_id' => $responseResultId);
		$success = false;
		$message = "Update password Failed!";
		if ($this->processResultBoolean($responseResultId, $resultArr,$playerName)) {
			$playerId = $this->getPlayerIdInPlayer($playerName);
			//sync password to game_provider_auth
			$success = true;
			$this->updatePasswordForPlayer($playerId, $newPassword);
			$message = "Update password success!";
		}

		return array($success, $resultArr);
	}

	function processResultForBlockPlayer($params){
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$result = array('response_result_id' => $responseResultId);
		$success = false;

		if ($this->processResultBoolean($responseResultId, $resultArr,$playerName)) {
			$this->blockUsernameInDB($playerName);//block on OG
			$success = true;
		}
		return array($success, $resultArr);
	}

	function unblockPlayer($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForUnblockPlayer',
			'playerName' => $playerName,
		);

		$params = array(
			'requestType'=> 'ban',
			'id' => $playerName,
			'agent_code' => $this->agent_code, // Agent in charge of the user
			'action' => "N", // Y : suspend(deactivate) , N : activate
			'key' => $this->db_key // DB KEY
		);

		return $this->callApi(self::API_unblockPlayer, $params, $context);
	}

	function processResultForUnblockPlayer($params){
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$result = array('response_result_id' => $responseResultId);
		$success = false;

		if ($this->processResultBoolean($responseResultId, $resultArr,$playerName)) {
			$success = $this->unblockUsernameInDB($playerName);;//unblock on OG
			$success = true;
		}
		return array($success, $resultArr);
	}

	function syncOriginalGameLogs($token = false) {

		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

		$startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
    	$endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));
    	$startDate->modify($this->getDatetimeAdjust());
		//observer the date format
		$startDate = $startDate->format('Y-m-d H:i:s');
		$endDate = $endDate->format('Y-m-d H:i:s');

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForSyncGameRecords',
			'startDate' => $startDate,
			'endDate' => $endDate
		);

		$params = array(
			'requestType'=> 'game_log',
			'id' => '',//userID (when empty, it searches for all the agents)
			'game_nm' => 'all',//baccarat / dnt / sicbo / roulette / all
			'startday' => $startDate,
			'endday' => $endDate,
			'page' => 1, //Current page
			'pagecount' => 99999999, //Current page
			'Flag' => 0, // 0 : search direct users only / 1 : search for all users including sub agents
			'agent_code' => $this->agent_code, // Agent in charge of the user
			'key' => $this->db_key // DB KEY
		);

		return $this->callApi(self::API_syncGameRecords, $params, $context);
	}

	function processResultForSyncGameRecords($params) {
		$this->CI->load->model(array('beteast_game_logs', 'player_model'));

		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr);
		$result = array();
		if ($success) {
			$gameRecords = $resultArr['data'];
			if ($gameRecords) {
				$availableRows = $this->CI->beteast_game_logs->getAvailableRows($gameRecords);
				if (isset($availableRows[0])) {
					foreach ($availableRows[0] as $record) {
						$record['start_dt'] = $this->gameTimeToServerTime($record['start_dt']);
						$record['end_dt'] = $this->gameTimeToServerTime($record['end_dt']);
						$record['Username'] = $record['login_nm'];
						$record['PlayerId'] = $this->getPlayerIdInGameProviderAuth($record['Username']);
						$record['external_uniqueid'] = $record['gameid']; //add external_uniueid for og purposes
						$record['response_result_id'] = $responseResultId;
						$this->CI->beteast_game_logs->insertBeteastGameLogs($record);
					}
					$result['data'] = $availableRows[0];
				}
			}
		}
		return array($success, $result);
	}

	function syncMergeToGameLogs($token) {

		$this->CI->load->model(array('game_logs', 'player_model', 'beteast_game_logs'));

		$dateTimeFrom = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$dateTimeFrom->modify($this->getDatetimeAdjust());
		$dateTimeTo = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

		//observer the date format
		$startDate = $dateTimeFrom->format('Y-m-d H:i:s');
		$endDate = $dateTimeTo->format('Y-m-d H:i:s');

		$this->CI->utils->debug_log('dateTimeFrom', $startDate, 'dateTimeTo', $endDate);


		$rlt = array('success' => true);
		$result = $this->CI->beteast_game_logs->getBeteastLogStatistics($startDate, $endDate);

		$cnt = 0;

		if ($result) {

			$unknownGame = $this->getUnknownGame();

			foreach ($result as $beteast_data) {
				$player_id = $beteast_data->PlayerId;

				if (!$player_id) {
					continue;
				}

				$cnt++;

				$result_amount = $beteast_data->result_amount - $beteast_data->bet_amount;
				$bet_amount = $result_amount==0?0:$beteast_data->bet_amount;
				$real_bet_amount = $beteast_data->bet_amount;

				$extra = array('table' => $beteast_data->gameid, 'trans_amount'=>$real_bet_amount );

				$game_description_id = $beteast_data->game_description_id;
				$game_type_id = $beteast_data->game_type_id;

				if (empty($game_description_id)) {
					$game_description_id = $unknownGame->id;
					$game_type_id = $unknownGame->game_type_id;
				}

				$this->syncGameLogs(
					$game_type_id,
					$game_description_id,
					$beteast_data->game_code,
					$beteast_data->game_type,
					$beteast_data->game,
					$player_id,
					$beteast_data->Username,
					$bet_amount,
					$result_amount,
					null, # win_amount
					null, # loss_amount
					null, # after_balance
					0, # has_both_side
					$beteast_data->external_uniqueid,
					$beteast_data->date_start, //start
					$beteast_data->date_end, //end
					$beteast_data->response_result_id,
					Game_logs::FLAG_GAME,
					$extra
				);

			}
		}

		$this->CI->utils->debug_log('syncMergeToGameLogs monitor', 'count', $cnt);
		return $rlt;
	}

	function queryTransaction($transactionId, $extra) {
		return $this->returnUnimplemented();
	}

	function syncPlayerAccount($username, $password, $playerId) {
		return $this->returnUnimplemented();
	}

	function queryPlayerInfo($playerName) {
		return $this->returnUnimplemented();
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

	function isPlayerExist($userName) {
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
}

/*end of file*/