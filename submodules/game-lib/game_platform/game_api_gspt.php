<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

/**
 * Defines general behavior of game API classes.
 *
 * General behaviors include:
 * * Getting platform code
 * * Get Api Admin Name
 * * Get Api Kiosk Name
 * * Create Var Map
 * * Generate URL
 * * Create Player
 * * Check if Player exist
 * * Check Player Information
 * * Change Player Password
 * * Block/Unblock Player
 * * Deposit To game
 * * Withdraw from Game
 * * Login/Logout player
 * * Update Player's Information
 * * Check Player Balance
 * * Check Player Daily Balance
 * * Check Game records
 * * Check Player's Login Status
 * * Check Login Token
 * * Check total betting amount
 * * Check Transaction
 * * Check if the Player's id already exist
 * Behaviors not implemented
 * * Login/Logout player
 * * Update Player's information
 * * Check player's login status
 * * Get Username by Player Username
 * * Get game Description Info
 * * Batch Check Player Balance
 * * List Player
 * * Reset Player
 * * Revert Broken Game
 *
 * The functions implemented by child class:
 * * Populating game form parameters
 * * Handling callbacks
 *
 * @deprecated 2.0
 *
 *
 * @see Redirect redirect to game page
 *
 * @category Game_platform
 * @version 1.8.10
 * @copyright 2013-2022 tot
 */
class Game_api_gspt extends Abstract_game_api {

	private $api_url;
	private $api_key;
	private $api_secret;
	private $api_suffix;

	const URI_MAP = array(
		self::API_createPlayer => 'CreatePlayer',
		self::API_isPlayerExist => 'DisplayPlayerInfo',
		self::API_queryPlayerInfo => 'DisplayPlayerInfo',
		self::API_queryPlayerBalance => 'CheckBalance',
		self::API_depositToGame => 'Deposit',
		self::API_withdrawFromGame => 'Withdraw',
		self::API_logout => 'LogOutPlayer',
		self::API_syncGameRecords => 'PlayerGameReport',
		self::API_changePassword => 'UpdatePlayerPassword',
		self::API_checkLoginStatus => 'IsPlayerOnline',
		//self::API_operatorLogin => 'LoadGame',
		self::API_queryForwardGame => 'LoadGame',
		self::API_queryTransaction=> 'CheckTransaction',
	);

	const START_PAGE = 1;
	const ITEM_PER_PAGE = 3000;
	const SET_TO_MOBILE = 1;

	//const GSPT_PLAYER_NAME_SUFFIX = '@RTT365';

	const DEFAULT_TRANSACTION_STATUS_APPROVED='approved';
	const DEFAULT_TRANSACTION_STATUS_DECLINED='declined';

	public function __construct() {
		parent::__construct();

		$this->api_url = $this->getSystemInfo('url');
		$this->api_key = $this->getSystemInfo('key');
		$this->api_secret = $this->getSystemInfo('secret');
		$this->api_suffix = $this->getSystemInfo('gspt_suffix');

		$this->add_progressive_bet = $this->getSystemInfo('add_progressive_bet', false);
		$this->add_progressive_win = $this->getSystemInfo('add_progressive_win', true);

		$this->perPageSize=$this->getSystemInfo('per_page_size', self::ITEM_PER_PAGE);

		$this->sync_time_interval = $this->getSystemInfo('sync_time_interval', '+10 minutes');

		$this->transaction_status_approved= $this->getSystemInfo('transaction_status_approved', self::DEFAULT_TRANSACTION_STATUS_APPROVED);
		$this->transaction_status_declined= $this->getSystemInfo('transaction_status_declined', self::DEFAULT_TRANSACTION_STATUS_DECLINED);

		$this->status_map=[
			$this->transaction_status_approved => self::COMMON_TRANSACTION_STATUS_APPROVED,
			$this->transaction_status_declined => self::COMMON_TRANSACTION_STATUS_DECLINED,
		];

	}

	// protected function convertStatus($status){
	// 	$this->CI->utils->debug_log('GSPT Status map', $this->status_map, 'Status', $status);
	// 	if(isset($this->status_map[$status])){
	// 		return $this->status_map[$status];
	// 	}else{
	// 		return self::COMMON_TRANSACTION_STATUS_UNKNOWNs;
	// 	}

	// }

	public function getPlatformCode() {
		return GSPT_API;
	}

	public function generateUrl($apiName, $params) {

		//return $url = $this->api_url . "?" . $params_string;

		$params_string = http_build_query($params);

		if ($apiName == self::API_queryForwardGame) {

			$gameUrl = $this->getSystemInfo('login_url');
			$url = $this->getSystemInfo('login_url') . "?" . $params_string;
			// var_dump($url);
			return $url;
		} else {

			return $url = $this->api_url . "?" . $params_string;
		}

	}

	// function getHttpHeaders($params) {
	// 	return array("success" => true);
	// }

	// function initSSL($ch) {
	// 	return array("success" => true);
	// }

	function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null) {

		return array(false, null);
	}

	function processResultBoolean($responseResultId, $resultJson, $playerName = null) {
		$success = !empty($resultJson) && !array_key_exists('ErrorLog', $resultJson);

		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('GSPT got error', $responseResultId, 'playerName', $playerName, 'result', $resultJson['ErrorLog']['error']);
		}
		return $success;
	}

	function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
		parent::createPlayer($playerName, $playerId, $password, $email, $extra);
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForCreatePlayer',
			'playerName' => $playerName,
			'playerId' => $playerId,
		);

		$params = array(
			'LoginID' => $this->api_key,
			'LoginPass' => $this->api_secret,
			'Function' => self::URI_MAP[self::API_createPlayer],
			'PlayerName' => strtoupper($playerName),
			'PlayerPass' => $password,
		);

		return $this->callApi(self::API_createPlayer, $params, $context);
	}

	function processResultForCreatePlayer($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$resultXml = $this->getResultXmlFromParams($params);
		$resultArr = json_decode(json_encode($resultXml), true);
		$success = $this->processResultBoolean($responseResultId, $resultArr);
		return array($success, $resultArr);

	}

	public function isPlayerExist($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForIsPlayerExist',
			'playerName' => $playerName,
		);
		$params = array(
			'LoginID' => $this->api_key,
			'LoginPass' => $this->api_secret,
			'Function' => self::URI_MAP[self::API_isPlayerExist],
			'PlayerName' => strtoupper($playerName),
		);

		return $this->callApi(self::API_isPlayerExist, $params, $context);
	}

	function processResultForIsPlayerExist($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$resultXml = $this->getResultXmlFromParams($params);

		$success = false;
		$result = array('exists' => true);
		$resultArr = json_decode(json_encode($resultXml), true);
		if ($this->processResultBoolean($responseResultId, $resultArr)) {
			$success = true;
		} else {
			$success = false;
			$result["exists"] = false;
		}

		return array($success, $result);

	}

	function queryPlayerInfo($playerName) {

		$rlt = $this->checkLoginStatus($playerName);
		if (!$rlt['success']) {
			$rlt = $this->login($playerName, $this->getPasswordString($playerName));
			if (!$rlt['success']) {
				return array(
					'success' => false,
				);
			}
		}

		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryPlayerInfo',
			'playerName' => $playerName,
		);

		$params = array(
			'LoginID' => $this->api_key,
			'LoginPass' => $this->api_secret,
			'Function' => self::URI_MAP[self::API_queryPlayerInfo],
			'PlayerName' => strtoupper($playerName),
		);

		return $this->callApi(self::API_queryPlayerInfo, $params, $context);
	}

	function processResultForQueryPlayerInfo($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$resultXml = $this->getResultXmlFromParams($params);

		$success = false;
		$result = array();
		$resultArr = json_decode(json_encode($resultXml), true);
		if ($this->processResultBoolean($responseResultId, $resultArr)) {
			$success = true;
			$result["playerInfo"] = $resultArr;
		} else {
			$success = false;
		}

		return array($success, $result);
	}

	function changePassword($playerName, $oldPassword = null, $newPassword) {

		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForChangePassword',
			'playerName' => $playerName,
			'newPassword' => $newPassword,
		);

		$params = array(
			'LoginID' => $this->api_key,
			'LoginPass' => $this->api_secret,
			'Function' => self::URI_MAP[self::API_changePassword],
			'PlayerName' => strtoupper($playerName),
			'PlayerPass' => $newPassword,
		);

		return $this->callApi(self::API_changePassword, $params, $context);
	}

	function processResultForChangePassword($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultXml = $this->getResultXmlFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$newPassword = $this->getVariableFromContext($params, 'newPassword');

		$success = false;
		$result = array();
		$resultArr = json_decode(json_encode($resultXml), true);
		if ($this->processResultBoolean($responseResultId, $resultArr)) {
			if ($playerId = $this->getPlayerIdInPlayer($playerName)) {
				$this->updatePasswordForPlayer($playerId, $newPassword);

				$success = true;
				$result = $resultArr['UpdatePlayerPassword'];

			} else {

				$this->CI->utils->debug_log('cannot find player', $playerName);
			}

		} else {
			$success = false;
		}

		return array($success, $result);
	}

	function blockPlayer($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$success = $this->blockUsernameInDB($playerName);
		return array("success" => true);
	}
	// function processResultForBlockPlayer($params) {
	// 	$playerName = $this->getGameUsernameByPlayerUsername($playerName);
	// 	$success = $this->blockUsernameInDB($playerName);
	// 	return array("success" => true);
	// }

	function unblockPlayer($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$success = $this->unblockUsernameInDB($playerName);
		return array("success" => true);
	}
	// function processResultForUnblockPlayer($params) {
	// 	return array("success" => true);
	// }

	function depositToGame($playerName, $amount, $transfer_secure_id = null) {

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		// $reference_no = random_string('numeric');

        if(empty($transfer_secure_id)){
            $transfer_secure_id=random_string('numeric', 13);
        }

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForDepositToGame',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername,
			// 'amount' => $amount,
            'transfer_secure_id'=>$transfer_secure_id,
            'external_transaction_id'=>$transfer_secure_id,
            //mock testing
            // 'is_timeout_mock' => $this->getSystemInfo('is_timeout_mock', false),
            //for this api
            // 'enabled_guess_success_for_curl_errno_on_this_api' => $this->enabled_guess_success_for_curl_errno_on_this_api,
		);

		$params = array(
			'LoginID' => $this->api_key,
			'LoginPass' => $this->api_secret,
			'Function' => self::URI_MAP[self::API_depositToGame],
			'PlayerName' => strtoupper($gameUsername),
			'Amount' => $amount,
			'TransacID' => $transfer_secure_id,

		);

		return $this->callApi(self::API_depositToGame, $params, $context);

	}

	function processResultForDepositToGame($params) {
//var_dump($params); exit();
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$resultXml = $this->getResultXmlFromParams($params);
		$resultArr = json_decode(json_encode($resultXml), true);

		$result = array('response_result_id' => $responseResultId,
			'external_transaction_id'=>$external_transaction_id,
			'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id'=>self::REASON_UNKNOWN);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);

        $this->CI->utils->debug_log('processResultForDepositToGame success: '.$success, $resultArr);

        // $result['status'] = $success ? self::COMMON_TRANSACTION_STATUS_APPROVED : self::COMMON_TRANSACTION_STATUS_PROCESSING;

        // $cnt=1;
        // $extra_info=null;
        //debug
        // $success=false;
   //      while(!$success && $cnt<=$this->transfer_retry_times){

   //      	$rlt=$this->queryTransaction($transfer_secure_id, $extra_info);

   //      	$this->CI->utils->debug_log('try query transaction: '.$transfer_secure_id, $rlt);

   //      	$success=$rlt['success'] && $rlt['status']==$this->transaction_status_approved;

			// $result['status']= $rlt['status'];

   //      	$cnt++;
   //      }

		if ($success) {

			$amount = @$resultArr['Deposit']['amount'];
			//get current sub wallet balance
			// $playerBalance = $this->queryPlayerBalance($playerName);

			//for sub wallet
			$afterBalance = null; // $playerBalance['balance'];
			// $result["external_transaction_id"] = @$resultArr['Deposit']['kiosk_transaction_id'];
			// $result["currentplayerbalance"] = $afterBalance;
			// $result["userNotFound"] = false;
			// $success = true;
			//update
			$playerId = $this->getPlayerIdInGameProviderAuth($playerName);
			if ($playerId) {
				//deposit
				$this->insertTransactionToGameLogs($playerId, $playerName, $afterBalance, $amount, $responseResultId,
					$this->transTypeMainWalletToSubWallet());

			} else {
				$this->CI->utils->debug_log('error', 'cannot get player id from ' . $playerName . ' getPlayerIdInGameProviderAuth');
			}

			$apiResult=@$resultArr['Deposit']['result'];
			if(strpos($apiResult, 'OK')!==false){

				$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;

			}


		} else {

			//get error log
			$errorcode=@$resultArr['ErrorLog']['errorcode'];
			switch ($errorcode) {
				case '98':
					$result['reason_id']=self::REASON_NO_ENOUGH_BALANCE;
					$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
					break;
				case '41':
					$result['reason_id']=self::REASON_NOT_FOUND_PLAYER;
					$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
					break;
				case '1005':
					$result['reason_id']=self::REASON_DISABLED_DEPOSIT_BY_GAME_PROVIDER;
					$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
					break;
			}

			// $result["userNotFound"] = true;
		}

		return array($success, $result);
	}

	function withdrawFromGame($playerName, $amount, $transfer_secure_id = null) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		// $reference_no = random_string('numeric');

        if(empty($transfer_secure_id)){
            $transfer_secure_id=random_string('numeric', 13);
        }

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForWithdrawFromGame',
			'playerName' => $playerName,
			// 'amount' => $amount,
			'gameUsername' => $gameUsername,
			// 'amount' => $amount,
            'transfer_secure_id'=>$transfer_secure_id,
            'external_transaction_id'=>$transfer_secure_id,
		);

		$params = array(
			'LoginID' => $this->api_key,
			'LoginPass' => $this->api_secret,
			'Function' => self::URI_MAP[self::API_withdrawFromGame],
			'PlayerName' => strtoupper($gameUsername),
			'Amount' => $amount,
			'TransacID' => $transfer_secure_id,

		);

		return $this->callApi(self::API_withdrawFromGame, $params, $context);
	}
	function processResultForWithdrawFromGame($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$resultXml = $this->getResultXmlFromParams($params);
		$resultArr = json_decode(json_encode($resultXml), true);

		// $result = array();
		$result = array('response_result_id' => $responseResultId,
			'external_transaction_id'=>$external_transaction_id,
			'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id'=>self::REASON_UNKNOWN);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);

        $this->CI->utils->debug_log('processResultForWithdrawFromGame success: '.$success, $resultArr);

        // $result['status'] = $success ? self::COMMON_TRANSACTION_STATUS_APPROVED : self::COMMON_TRANSACTION_STATUS_PROCESSING;

        // $cnt=1;
        // $extra_info=null;
        //debug
        // $success=false;
   //      while(!$success && $cnt<=$this->transfer_retry_times){

   //      	$rlt=$this->queryTransaction($transfer_secure_id, $extra_info);

   //      	$this->CI->utils->debug_log('try query transaction: '.$transfer_secure_id, $rlt);

   //      	$success=$rlt['success'] && $rlt['status']==$this->transaction_status_approved;

			// $result['status']= $rlt['status'];

   //      	$cnt++;
   //      }

		if ($success) {
			//get current sub wallet balance
			$amount = @$resultArr['Withdraw']['amount'];

			// $playerBalance = $this->queryPlayerBalance($playerName);

			//for sub wallet
			$afterBalance = null; //$playerBalance['balance'];
			// $result["external_transaction_id"] = @$resultArr['Withdraw']['kiosk_transaction_id'];
			// $result["currentplayerbalance"] = $afterBalance;
			// $result["userNotFound"] = false;
			// $success = true;
			//update
			$playerId = $this->getPlayerIdInGameProviderAuth($playerName);
			if ($playerId) {
				//withdraw
				$this->insertTransactionToGameLogs($playerId, $playerName, $afterBalance, $amount, $responseResultId,
					$this->transTypeSubWalletToMainWallet());

			} else {
				$this->CI->utils->debug_log('error', 'cannot get player id from ' . $playerName . ' getPlayerIdInGameProviderAuth');
			}

			$apiResult=@$resultArr['Withdraw']['result'];
			if(strpos($apiResult, 'OK')!==false){

				$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;

			}

		} else {

			//get error log
			$errorcode=@$resultArr['ErrorLog']['errorcode'];
			switch ($errorcode) {
				case '98':
					$result['reason_id']=self::REASON_NO_ENOUGH_BALANCE;
					$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
					break;
				case '41':
					$result['reason_id']=self::REASON_NOT_FOUND_PLAYER;
					$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
					break;
			}

			// $result["userNotFound"] = true;
		}
		return array($success, $result);
	}

	function login($playerName, $password = null) {
		return $this->returnUnimplemented();
	}

	function logout($playerName, $password = null) {

		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForLogout',
			'playerName' => $playerName,
		);
		$params = array(
			'LoginID' => $this->api_key,
			'LoginPass' => $this->api_secret,
			'Function' => self::URI_MAP[self::API_logout],
			'PlayerName' => strtoupper($playerName),
		);

		return $this->callApi(self::API_logout, $params, $context);
	}

	function processResultForLogout($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultXml = $this->getResultXmlFromParams($params);
		$resultArr = json_decode(json_encode($resultXml), true);
		//var_dump($resultArr);
		$success = false;
		if ($this->processResultBoolean($responseResultId, $resultArr)) {
			$success = true;
		} else {
			$success = false;
		}
		return array($success, null);
	}

	function updatePlayerInfo($playerName, $infos) {
		return array("success" => true);
	}
	// function processResultForUpdatePlayerInfo($apiName, $params, $responseResultId, $resultJson) {
	// 	return array("success" => true);
	// }

	function queryPlayerBalance($playerName) {

		$playerName = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryPlayerBalance',
			'playerName' => $playerName,
		);

		$params = array(
			'LoginID' => $this->api_key,
			'LoginPass' => $this->api_secret,
			'Function' => self::URI_MAP[self::API_queryPlayerBalance],
			'PlayerName' => strtoupper($playerName),
		);

		return $this->callApi(self::API_queryPlayerBalance, $params, $context);

	}

	function processResultForQueryPlayerBalance($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$resultXml = $this->getResultXmlFromParams($params);

		$success = false;
		$result = array();
		$resultArr = json_decode(json_encode($resultXml), true);
		if ($this->processResultBoolean($responseResultId, $resultArr)) {
			$success = true;
			$result['balance'] = @floatval($resultArr['CheckBalance']['BALANCE']);

			if ($playerId = $this->getPlayerIdInGameProviderAuth($playerName)) {
				$this->CI->utils->debug_log('query balance playerId', $playerId, 'playerName', $playerName, 'balance', $result['balance']);
			} else {
				$this->CI->utils->debug_log('cannot get player id from ' . $playerName . ' getPlayerIdInGameProviderAuth');
			}

		} else {
			$success = false;

		}

		return array($success, $result);
	}

	function queryPlayerDailyBalance($playerName, $playerId, $dateFrom = null, $dateTo = null) {
		return array("success" => true);
	}

	function queryGameRecords($dateFrom, $dateTo, $playerName = null) {
		return array("success" => true);
	}

	function checkLoginStatus($playerName) {

		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForCheckLoginStatus',
			'playerName' => $playerName,
		);
		$params = array(
			'LoginID' => $this->api_key,
			'LoginPass' => $this->api_secret,
			'Function' => self::URI_MAP[self::API_checkLoginStatus],
			'PlayerName' => strtoupper($playerName),
		);

		return $this->callApi(self::API_checkLoginStatus, $params, $context);
	}

	function processResultForCheckLoginStatus($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultXml = $this->getResultXmlFromParams($params);
		$resultArr = json_decode(json_encode($resultXml), true);

		$success = false;
		$result = array();

		if ($this->processResultBoolean($responseResultId, $resultArr)) {
			$success = true;
			$result['online'] = $resultArr['IsPlayerOnline']['result'];

		} else {
			$success = false;

		}
		return array($success, $result);
	}

	public function checkLoginToken($playerName, $token) {
		return array("success" => true);

	}
	// public function processResultForCheckLoginToken($params) {
	// 	return array("success" => true);

	// }

	function totalBettingAmount($playerName, $dateTimeFrom, $dateTimeTo) {
		return array("success" => true);
	}

	function queryTransaction($transfer_secure_id, $extra) {
		// $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$playerId=$extra['playerId'];

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryTransaction',
			'playerId'=>$playerId,
			// 'playerName' => $playerName,
			// 'gameUsername' => $gameUsername,
			'external_transaction_id' => $transfer_secure_id,
		);

		$params = array(
			'LoginID' => $this->api_key,
			'LoginPass' => $this->api_secret,
			'Function' => self::URI_MAP[self::API_queryTransaction],
			'TransacID' => $transfer_secure_id,
		);

		return $this->callApi(self::API_queryTransaction, $params, $context);

	}

	function processResultForQueryTransaction($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		// $playerName = $this->getVariableFromContext($params, 'playerName');
		// $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		// $resultJson = $this->getResultJsonFromParams($params);
		$resultXml = $this->getResultXmlFromParams($params);
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');

		$success=false;
		$result=['response_result_id'=>$responseResultId, 'external_transaction_id'=> $external_transaction_id,
			'status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN];

		if(!empty($resultXml)){

			$resultArr = json_decode(json_encode($resultXml), true);

			$success = !empty($resultArr) && !array_key_exists('ErrorLog', $resultArr);

			if($success){
				// $success = $this->processResultBoolean($responseResultId, $resultArr);
				// $result = ['response_result_id' => $responseResultId, 'status'=>@$resultArr['CheckTransaction']['status']];

				// $success= $success && @$resultArr['CheckTransaction']['status']==$this->transaction_status_approved;

				$result['original_status']=@$resultArr['CheckTransaction']['status'];

				if(strtolower($result['original_status'])==$this->transaction_status_approved){
					$result['status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
				}elseif(strtolower($result['original_status'])==$this->transaction_status_declined){
					$result['status']=self::COMMON_TRANSACTION_STATUS_DECLINED;
				// }elseif(strtolower($result['original_status'])=='missing'){
				// 	$result['status']=self::COMMON_TRANSACTION_STATUS_UNKNOWN;
				}
			}else{

				$errorcode=@$resultArr['ErrorLog']['errorcode'];
				$result['error_code']=$errorcode;

			}
		}

		return [$success, $result];
	}

	function queryForwardGame($playerName, $extra) {

		$rlt = $this->checkLoginStatus($playerName);
		$password = $this->getPasswordString($playerName);
		if (!$rlt['success']) {
			$rlt = $this->login($playerName, $password);
			if (!$rlt['online']) {
				return array(
					'success' => false,
				);
			}
		}

		$playerName = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryForwardGame',
			'playerName' => $playerName,
		);

		$params = array(
			'username' => strtoupper($extra['username']) . $this->api_suffix,
			'password' => $password,
			'langcode' => $extra['language'],
			'gamecode' => $extra['game_code'],

		);

		if(!empty($extra['is_mobile'])) {
			$params['h5'] = self::SET_TO_MOBILE;
		}

		return $this->callApi(self::API_queryForwardGame, $params, $context);

	}

	function processResultForQueryForwardGame($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultText = $this->getValueFromParams($params, 'resultText');
		$result = array();
		$result['url'] = $resultText;

		return array(true, $result);

	}

	public function gameAmountToDB($amount) {
		$amount = floatval($amount);
		return round($amount, 2);
	}

	function syncOriginalGameLogs($token = false) {

		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
		// $startDate = $startDate->format('Y-m-d H:i:s');
		// $endDate = $endDate->format('Y-m-d H:i:s');
		$playerName = parent::getValueFromSyncInfo($token, 'playerName');

    	$count = 0;
    	$sum=0;
    	$real_count=0;
    	$success = true;
    	//$rlt = null;

		$queryDateTimeStart = $startDate->format('Y-m-d H:i:s');
		$queryDateTimeEnd = $startDate->modify($this->sync_time_interval)->format('Y-m-d H:i:s');
	    $queryDateTimeMax = $endDate->format('Y-m-d H:i:s');

		while ($queryDateTimeMax  > $queryDateTimeStart) {

			$done = false;
			$currentPage = self::START_PAGE;

			while (!$done) {

				$context = array(
					'callback_obj' => $this,
					'callback_method' => 'processResultForSyncGameRecords',
					);

				$params = array(
					'LoginID' => $this->api_key,
					'LoginPass' => $this->api_secret,
					'Function' => self::URI_MAP[self::API_syncGameRecords],
					'startdate' => $queryDateTimeStart,
					'enddate' => $queryDateTimeEnd,
					'pageNum' => $currentPage,
					'perPage' => $this->perPageSize,
				);

				if(!empty($playerName)){
					$params['PlayerName']=$playerName;
				}

				$rlt = $this->callApi(self::API_syncGameRecords, $params, $context);

				$done = true;
				if ($rlt) {
					$success = $rlt['success'];
				}

				if ($rlt && $rlt['success']) {
					$currentPage = $rlt['currentPage'];
					$total_pages = $rlt['totalPages'];
					//next page
					$currentPage += 1;

					$done = $currentPage > $total_pages;
					$count += $rlt['count'];
					$sum += $rlt['sum'];
					$real_count += $rlt['real_count'];
					$this->CI->utils->debug_log($params, 'currentPage', $currentPage, 'total_pages', $total_pages, 'done', $done, 'result', $rlt);
				}
			}

			$queryDateTimeStart = $queryDateTimeEnd ;
	    	$queryDateTimeEnd  = (new DateTime($queryDateTimeStart))->modify($this->sync_time_interval)->format('Y-m-d H:i:s');

	    } //end while outer

     	// $this->CI->utils->debug_log('#########GSPT syncOriginalGameLogs','total rows count', $cnt );

		return array("success" => $success, "count"=>$count, "sum"=> $sum, "real_count"=> $real_count);

	}

	function processResultForSyncGameRecords($params) { //var_dump($params); exit();
		//var_dump($params);
		// $this->CI->utils->debug_log('############### GSPT RESULT ###################', $params);

		#NOTE: "error":"Date range more than 30 minutes are not supported for all players games report, use shorter period or provide 'playername'","errorcode":0}'
		// 	$params= array(
		//  	'responseResultId' => 685,
		// 			'resultText' => '
		// 					{
		//     "result": [
		//         {
		//             "PLAYERNAME": "SUPERMAN@BRAND",
		//             "WINDOWCODE": "6",
		//             "GAMEID": "73",
		//             "GAMECODE": "33455254586",
		//             "GAMETYPE": "Live Games",
		//             "GAMENAME": "Roulette Live (rol)",
		//             "SESSIONID": "42143458748",
		//             "BET": "23",
		//             "WIN": "57",
		//             "PROGRESSIVEBET": "0",
		//             "PROGRESSIVEWIN": "0",
		//             "BALANCE": "57.78",
		//             "CURRENTBET": "0",
		//             "GAMEDATE": "2014-09-24 00:37:41",
		//             "INFO": "33:150,400,0;151,1900,5700;",
		//             "LIVENETWORK": "Live Asia Network",
		//             "RNUM": "1"
		//         },
		//         {
		//             "PLAYERNAME": "SUPERMAN@BRAND",
		//             "WINDOWCODE": "6",
		//             "GAMEID": "74",
		//             "GAMECODE": "33455316481",
		//             "GAMETYPE": "Live Games",
		//             "GAMENAME": "Roulette Live (rol)",
		//             "SESSIONID": "42143458748",
		//             "BET": "11",
		//             "WIN": "0",
		//             "PROGRESSIVEBET": "0",
		//             "PROGRESSIVEWIN": "0",
		//             "BALANCE": "46.78",
		//             "CURRENTBET": "0",
		//             "GAMEDATE": "2014-09-24 00:38:40",
		//             "INFO": "7:150,400,0;151,700,0;",
		//             "LIVENETWORK": "Live Asia Network",
		//             "RNUM": "2"
		//         },
		//         {
		//             "PLAYERNAME": "SUPERMAN@BRAND",
		//             "WINDOWCODE": "6",
		//             "GAMEID": "75",
		//             "GAMECODE": "33455370684",
		//             "GAMETYPE": "Live Games",
		//             "GAMENAME": "Roulette Live (rol)",
		//             "SESSIONID": "42143458748",
		//             "BET": "11",
		//             "WIN": "12",
		//             "PROGRESSIVEBET": "0",
		//             "PROGRESSIVEWIN": "0",
		//             "BALANCE": "47.78",
		//             "CURRENTBET": "0",
		//             "GAMEDATE": "2014-09-24 00:39:41",
		//             "INFO": "32:151,400,1200;149,700,0;",
		//             "LIVENETWORK": "Live Asia Network",
		//             "RNUM": "3"
		//         },
		//         {
		//             "PLAYERNAME": "SUPERMAN@BRAND",
		//             "WINDOWCODE": "6",
		//             "GAMEID": "76",
		//             "GAMECODE": "33455424999",
		//             "GAMETYPE": "Live Games",
		//             "GAMENAME": "Roulette Live (rol)",
		//             "SESSIONID": "42143458748",
		//             "BET": "11",
		//             "WIN": "0",
		//             "PROGRESSIVEBET": "0",
		//             "PROGRESSIVEWIN": "0",
		//             "BALANCE": "36.78",
		//             "CURRENTBET": "0",
		//             "GAMEDATE": "2014-09-24 00:40:41",
		//             "INFO": "24:151,400,0;149,700,0;",
		//             "LIVENETWORK": "Live Asia Network",
		//             "RNUM": "4"
		//         },
		//         {
		//             "PLAYERNAME": "SUPERMAN@BRAND",
		//             "WINDOWCODE": "6",
		//             "GAMEID": "77",
		//             "GAMECODE": "33455499922",
		//             "GAMETYPE": "Live Games",
		//             "GAMENAME": "Roulette Live (rol)",
		//             "SESSIONID": "42143458748",
		//             "BET": "11",
		//             "WIN": "12",
		//             "PROGRESSIVEBET": "0",
		//             "PROGRESSIVEWIN": "0",
		//             "BALANCE": "37.78",
		//             "CURRENTBET": "0",
		//             "GAMEDATE": "2014-09-24 00:41:42",
		//             "INFO": "3:150,700,0;149,400,1200;",
		//             "LIVENETWORK": "Live Asia Network",
		//             "RNUM": "5"
		//         }
		//     ],
		//     "pagination": {
		//         "currentPage": "1",
		//         "totalPages": 3,
		//         "itemsPerPage": "5",
		//         "totalCount": 12
		//     }
		// }'

//  	);

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);

		$gameRecords = array();

		if (isset($resultJson['result'])) {
			$gameRecords = $resultJson['result'];
			$pagination_info = $resultJson['pagination'];
		}



		$success = $this->processResultBoolean($responseResultId, $resultJson);

		if(!$success){
 	   		return array($success, @$resultJson);
		}



		$this->CI->load->model(array('gspt_game_logs', 'external_system'));


		$result = array();
		$count=0;
		$sum=0;
		$real_count=0;

		if (!empty($gameRecords)) {

			$real_count=count($gameRecords);

			list($availableRows, $maxRowId) = $this->CI->gspt_game_logs->getAvailableRows($gameRecords);
			$this->CI->utils->debug_log('availableRows', count($availableRows), 'gameRecords', count($gameRecords), 'maxRowId', $maxRowId);

			if ($availableRows) {
				foreach ($availableRows as $record) {

					$count++;
					$sum+=$record['BET'];

					preg_match_all("/\(([^()]+)\)/", $record['GAMENAME'], $matches);
					$gameshortcode = @$matches[1][0];

					$gsptData = array(
						'player_name' => $record['PLAYERNAME'],
						'window_code' => $record['WINDOWCODE'],
						'game_id' => $record['GAMEID'],
						'game_code' => $record['GAMECODE'],
						'game_type' => $record['GAMETYPE'],
						'game_name' => $record['GAMENAME'],
						'gameshortcode' => $gameshortcode,
						'session_id' => $record['SESSIONID'],
						'bet' => $record['BET'],
						'win' => $record['WIN'],
						'progressive_bet' => $record['PROGRESSIVEBET'],
						'progressive_win' => $record['PROGRESSIVEWIN'],
						'balance' => $record['BALANCE'],
						'current_bet' => $record['CURRENTBET'],
						'game_date' => $record['GAMEDATE'],
						'info' => $record['INFO'],
						'live_network' => $record['LIVENETWORK'],
						'r_num' => $record['RNUM'],
						'external_uniqueid' => $record['GAMECODE'],
						'response_result_id' => $responseResultId,
					);

					$this->CI->gspt_game_logs->insertGsptGameLogs($gsptData);
				}

				if ($maxRowId) {
					$this->CI->external_system->setLastSyncId($this->getPlatformCode(), $maxRowId);
					$lastRowId = $maxRowId;
				}
			}

			$result['currentPage'] = $pagination_info['currentPage'];
			$result['totalPages'] = $pagination_info['totalPages'];
			$result['itemsPerPage'] = $pagination_info['itemsPerPage'];
			$result['totalCount'] = $pagination_info['totalCount'];

			$success = true;

		} else {

			$success = true;
			$result['currentPage'] = 0;
			$result['totalPages'] = 0;
			$result['itemsPerPage'] = 0;
			$result['totalCount'] = 0;
		}

		$result['count'] = $count;
		$result['sum'] = $sum;
		$result['real_count']= $real_count;

		return array($success, $result);

	}



	function getSyncTimeInterval(){

		$interval = $this->getSystemInfo('sync_time_interval');

		if($interval){
			return $interval;
		}
		return '+30 minutes';
	}

	private function isUniqueIdAlreadyExists($uniqueId) {
		return array("success" => true);
	}

	private function isInvalidRow($row) {
		return array("success" => true);
	}

	function syncMergeToGameLogs($token) {

		$this->CI->load->model(array('game_logs', 'player_model', 'gspt_game_logs', 'game_description_model'));

		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
		$startDate = $startDate->format('Y-m-d H:i:s');
		$endDate = $endDate->format('Y-m-d H:i:s');

		$this->CI->utils->debug_log('dateTimeFrom', $startDate, 'dateTimeTo', $endDate);

		$rlt = array('success' => true);
		$result = $this->CI->gspt_game_logs->getGsptGameLogStatistics($startDate, $endDate,
			$this->api_suffix);
		//var_dump($result); //exit();

		$cnt = 0;
		$sum=0;
		if ($result) {

			$unknownGame = $this->getUnknownGame();
			// var_dump($unknownGame);
			$gameDescIdMap = $this->CI->game_description_model->getGameCodeMap($this->getPlatformCode());

			foreach ($result as $gspt_data) {

				$slicedPlayerName = strtolower(str_replace($this->api_suffix, '', $gspt_data->player_name));
				//	var_dump($slicedPlayerName);

				if (!$player_id = $this->getPlayerIdInGameProviderAuth($slicedPlayerName)) {
					continue;
				}

				$player_id = $this->getPlayerIdInGameProviderAuth($slicedPlayerName);

				$cnt++;

				// $player = $this->CI->player_model->getPlayerById($player_id);
				// $game_description_id = $gspt_data->game_description_id;
				// $game_type_id = $gspt_data->game_type_id;

				list($game_description_id, $game_type_id) = $this->getGameDescriptionInfo($gspt_data, $unknownGame,
					$gameDescIdMap);
				// $this->CI->utils->debug_log('game_description_id', $game_description_id, 'game_type_id', $game_type_id);

				// if (empty($game_description_id)) {
				// 	$this->CI->utils->debug_log('empty game_description_id , pt_game_logs.id=', $key->id);
				// 	continue;
				// }

				//process bet/win/loss
				$bet_amount = $this->gameAmountToDB($gspt_data->bet);
				$win_amount = $this->gameAmountToDB($gspt_data->win_amount);

				$sum+=$bet_amount;

				if($this->add_progressive_bet){
					//add progressive part
					$bet_amount+=$this->gameAmountToDB($gspt_data->progressive_bet);
				}
				if($this->add_progressive_win){
					$win_amount+=$this->gameAmountToDB($gspt_data->progressive_win);
				}

				// if (empty($game_description_id)) {
				// 	$game_description_id = $unknownGame->id;
				// 	$game_type_id = $unknownGame->game_type_id;
				// }

				$winLose = round(($win_amount - $bet_amount), 2);
				$flag=Game_logs::FLAG_GAME;
				$extra_info=['table'=>$gspt_data->round_number, 'note'=>$gspt_data->info];

				$this->syncGameLogs(
					//$r = array(
					$game_type_id,
					$game_description_id,
					$gspt_data->game_code,
					$game_type_id,
					$gspt_data->game_name,
					$player_id,
					$gspt_data->player_name,
					$bet_amount,
					$winLose,
					null, # win_amount
					null, # loss_amount
					null, # after_balance
					0, # has_both_side
					$gspt_data->external_uniqueid,
					$gspt_data->game_date,
					$gspt_data->game_date,
					$gspt_data->response_result_id, # response_result_id
					$flag,
					$extra_info
				);

			}
		}
		$this->CI->utils->debug_log('syncMergeToGameLogs monitor', 'count', $cnt);

		$rlt['count']=$cnt;
		$rlt['sum']=$sum;
		return $rlt;

	}

	private function processAfterBalance($afterBalance) {
		return array("success" => true);
	}

	private function getGameDescriptionInfo($row, $unknownGame, $gameDescIdMap) {
		$game_description_id = null;
		if (isset($row->game_description_id)) {
			$game_description_id = $row->game_description_id;
		}
		$game_type_id = null;
		if (isset($row->game_type_id)) {
			$game_type_id = $row->game_type_id;
		}

		$externalGameId = $row->gameshortcode;
		$extra = array('game_code' => $row->gameshortcode);
		if (empty($game_description_id)) {
			//search game_description_id by code
			if (isset($gameDescIdMap[$externalGameId]) && !empty($gameDescIdMap[$externalGameId])) {
				$game_description_id = $gameDescIdMap[$externalGameId]['game_description_id'];
				$game_type_id = $gameDescIdMap[$externalGameId]['game_type_id'];
				if ($gameDescIdMap[$externalGameId]['void_bet'] == 1) {
					return array(null, null);
				}
			}
		}

		return $this->processUnknownGame(
			$game_description_id, $game_type_id,
			$row->game_name, $row->game_type, $externalGameId, $extra,
			$unknownGame);
	}

	private function getPTGameLogStatistics($dateTimeFrom, $dateTimeTo, $playerName) {
		return array("success" => true);
	}

	public function batchQueryPlayerBalance($playerNames, $syncId = null) {
		return array("success" => true);
	}

	function processResultForBatchQueryPlayerBalance($params) {

		return array("success" => true);

	}

	public function listPlayers($playerNames) {
		return array("success" => true);
	}

	function processResultForListPlayers($params) {
		return array("success" => true);
	}

	public function resetPlayer($playerName) {
		return array("success" => true);

	}

	public function processResultForResetPlayer($params) {
		return array("success" => true);
	}

	public function revertBrokenGame($playerName) {
		return array("success" => true);

	}

	public function processResultForRevertBrokenGame($params) {
		return array("success" => true);
	}

}

/*end of file*/
