<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

class Game_api_kenogame extends Abstract_game_api {

	private $api_url;
	//private $api_key;
	private $transfer_url;
	private $game_info_url;
	private $game_info_token;
	private $VendorSite;
	private $FundLink;
	private $VendorId;
	private $PlayerAllowStake;
	private $VendorRef;
	private $RebateLevel;
	private $Language;
	private $PlayerCurrency;

	const URI_MAP = array(
		self::API_createPlayer => 'player_enter_keno.php',
		self::API_queryPlayerBalance => 'player_get_credit.php',
		self::API_depositToGame => 'player_fund_in_out_first.php',
		self::API_withdrawFromGame => 'player_fund_in_out_first.php',
		self::API_operatorLogin => 'player_enter_keno.php',
		self::API_transfer => 'player_fund_in_out_first.php',
		self::API_checkFundTransfer => 'player_fund_in_out_confirm.php',
		self::API_syncGameRecords => 'GameInfo.php',
		self::API_isPlayerExist => 'player_get_credit.php',

		//self::API_isPlayerExist => 'DisplayPlayerInfo',
		//self::API_queryPlayerInfo => 'DisplayPlayerInfo',
		// self::API_logout => 'LogOutPlayer',
		// self::API_syncGameRecords => 'PlayerGameReport',
		// self::API_changePassword => 'UpdatePlayerPassword',
		// self::API_checkLoginStatus => 'IsPlayerOnline',

	);

	const FUND_IN_OUT_CONFIRM = 'FundInOutConfirm';
	// const FUND_IN_OUT_CONFIRM_PAGE = 'player_fund_in_out_confirm.php';

	public function __construct() {
		parent::__construct();

		$this->api_url = $this->getSystemInfo('url');

		//this is for getting  game history
		$this->game_info_url = $this->getSystemInfo('game_info_url');
		$this->transfer_url = $this->getSystemInfo('transfer_url');
		$this->game_info_token = $this->getSystemInfo('Token');

		$this->VendorSite = $this->getSystemInfo('VendorSite');
		$this->VendorId = $this->getSystemInfo('VendorId');
		$this->PlayerAllowStake = $this->getSystemInfo('PlayerAllowStake');
		$this->VendorRef = $this->getSystemInfo('VendorRef');
		$this->RebateLevel = $this->getSystemInfo('RebateLevel');
		$this->Language = $this->getSystemInfo('Language');
		$this->PlayerCurrency = $this->getSystemInfo('PlayerCurrency');
		$this->Trial = $this->getSystemInfo('Trial');

	}

	public function getPlatformCode() {
		return KENOGAME_API;
	}

	protected function getCallType($apiName, $params) {
		//overwrite in sub-class
		return self::CALL_TYPE_XMLRPC;
	}

	function getApiAdminName() {
		return array("success" => true);
	}

	function getApiKioskName() {
		return array("success" => true);
	}

	function createVarMap($params) {
		return array("success" => true);
	}

	public function generateUrl($apiName, $params) {

		$file_url = self::URI_MAP[$apiName];

		if ($apiName == self::API_syncGameRecords) {

			return $url = rtrim($this->game_info_url, '/') . '/' . $file_url;

		} else {

			return $url = rtrim($this->api_url, '/') . '/' . $file_url;
		}

	}

	function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null) {

		return array(false, null);
	}

	function processResultBoolean($responseResultId, $resultArr, $playerName = null) {
		$success = !empty($resultArr) && !isset($resultArr['Error']);

		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('KENOGAME got error', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
		}
		return $success;
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
					$this->updatePlayerSubwalletBalance($playerId, $balance);
				}
			}

		}
		return array('success' => $success, 'balance' => $balance);
	}

	function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
		parent::createPlayer($playerName, $playerId, $password, $email, $extra);

        $this->CI->utils->debug_log('===================================== KENOGAME Create Player =====================================',array("playerName" => $playerName, "password" => $password,"extra" => $extra));

		return $this->login($playerName, $password);

		// $playerName = $this->getGameUsernameByPlayerUsername($playerName);

		// // $reference_no = random_string('numeric');

		// $context = array(
		// 	'callback_obj' => $this,
		// 	'callback_method' => 'processResultForCreatePlayer',
		// 	'playerName' => $playerName,
		// 	'playerId' => $playerId,
		// );

		// $params = array(
		// 	'VendorSite' => $this->VendorSite,
		// 	'FundLink' => site_url('iframe_module/iframe_viewCashier'),
		// 	'VendorId' => $this->VendorId,
		// 	'PlayerId' => $playerName,
		// 	'PlayerRealName' => $playerName,
		// 	'PlayerCurrency' => $this->PlayerCurrency,
		// 	'PlayerCredit' => '0',
		// 	'PlayerAllowStake' => $this->PlayerAllowStake,
		// 	'Trial' => '1',
		// 	'PlayerIP' => $this->CI->utils->getIP(),
		// 	'VendorRef' => $this->VendorRef,
		// 	'Language' => $this->Language,
		// 	'RebateLevel' => $this->RebateLevel,
		// 	// 'Remarks' => $reference_no,
		// );

		// // var_dump($params);
		// return $this->callApi(self::API_createPlayer, $params, $context);
	}

// 	function processResultForCreatePlayer($params) {
	// 		$responseResultId = $this->getResponseResultIdFromParams($params);
	// 		$resultArr = $this->getResultObjFromParams($params);
	// // var_dump($resultArr);

// 		$result = array();
	// 		$success = false;
	// 		if ($this->processResultBoolean($responseResultId, $resultArr)) {
	// 			$success = true;
	// 		} else {
	// 			$success = false;
	// 			$result['response'] = $resultArr['Error'];
	// 		}

// 		return array($success, $resultArr);

// 	}

	protected function generateXmlRpcMethod($apiName, $params) {

		switch ($apiName) {

		case self::API_syncGameRecords:
			return array('GetGameInfo', $params, 'json');
			break;

		case self::API_queryPlayerBalance:
			return array('GetCredit', $params, 'xml');
			break;

		case self::API_isPlayerExist:
			return array('GetCredit', $params, 'xml');
			break;

		case self::API_createPlayer:
			return array('PlayerLanding', $params, 'xml');
			break;

		case self::API_depositToGame:
		case self::API_withdrawFromGame:
		case self::API_transfer:
			return array('FundInOutFirst', $params, 'xml');
			break;

		case self::API_operatorLogin:
			return array('PlayerLanding', $params, 'xml');
			break;

		case self::API_checkFundTransfer:
			return array('FundInOutConfirm', $params, 'xml');
			break;

		default:
			# code...
			break;
		}

		//return parent::generateSoapMethod($apiName, $params);
	}

	function isPlayerExist($playerName) {

		$playerId=$this->getPlayerIdFromUsername($playerName);
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		// $reference_no = random_string('numeric');

		//Get Player id
		//$playerId = '23';
		// $playerId = $this->getPlayerIdInGameProviderAuth($playerName);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForIsPlayerExist',
			'playerName' => $playerName,
			'playerId'=>$playerId,
		);

		$params = array(
			'VendorId' => $this->VendorId,
			'PlayerId' => $playerName,
		);

        $this->CI->utils->debug_log('===================================== KENOGAME isPlayerExist request =====================================',$params);

		return $this->callApi(self::API_isPlayerExist, $params, $context);
		//return array("success" => true);

	}

	function processResultForIsPlayerExist($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		// $amount = $this->getVariableFromContext($params, 'amount');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$playerId = $this->getVariableFromContext($params, 'playerId');

		$resultArr = $this->getResultObjFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);
		$result = array('response_result_id'=>$responseResultId);

        $result['exists'] = null;

		if ($success) {
			$result['exists'] = true;
			$this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
		} else {
			$success = false;
			if (@$resultArr['Error'] == 'PLAYER NOT FOUND') {
				$result['exists'] = false;
				$success = true;
			}
		}

		return array($success, $result);

	}

	// public function isPlayerExist($playerName) {
	// 	$this->CI->utils->debug_log('isPlayerExist=queryPlayerBalance');
	// 	$rlt = $this->queryPlayerBalance($playerName);
	// 	if ($rlt['success']) {
	// 		$rlt['exists'] = true;
	// 	} else {
	// 		// $rlt['success']=true;
	// 		$rlt['exists'] = $rlt['exists'];

	// 	}
	// 	return $rlt;
	// }

	function queryPlayerInfo($playerName) {

		// $rlt = $this->checkLoginStatus($playerName);
		// if (!$rlt['success']) {
		// 	$rlt = $this->login($playerName, $this->getPasswordString($playerName));
		// 	if (!$rlt['success']) {
		// 		return array(
		// 			'success' => false,
		// 		);
		// 	}
		// }

		// $playerName = $this->getGameUsernameByPlayerUsername($playerName);
		// $context = array(
		// 	'callback_obj' => $this,
		// 	'callback_method' => 'processResultForQueryPlayerInfo',
		// 	'playerName' => $playerName,
		// );

		// $params = array(
		// 	'LoginID' => $this->api_key,
		// 	'LoginPass' => $this->api_secret,
		// 	'Function' => self::URI_MAP[self::API_queryPlayerInfo],
		// 	'PlayerName' => $playerName,
		// );

		// return $this->callApi(self::API_queryPlayerInfo, $params, $context);
		// return array("success" => true);
		return $this->returnUnimplemented();
	}

	function changePassword($playerName, $oldPassword = null, $newPassword) {

		// $playerName = $this->getGameUsernameByPlayerUsername($playerName);
		// $context = array(
		// 	'callback_obj' => $this,
		// 	'callback_method' => 'processResultForChangePassword',
		// 	'playerName' => $playerName,
		// 	'newPassword' => $newPassword,
		// );

		// $params = array(
		// 	'LoginID' => $this->api_key,
		// 	'LoginPass' => $this->api_secret,
		// 	'Function' => self::URI_MAP[self::API_changePassword],
		// 	'PlayerName' => $playerName,
		// 	'PlayerPass' => $newPassword,
		// );

		// return $this->callApi(self::API_changePassword, $params, $context);
		// return array("success" => true);
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

	/**
	 * $amount +/-
	 */
	protected function transferAmount($playerName, $amount, $transfer_secure_id=null, $transferType=null) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		//$reference_no = random_string('numeric');
		//Get Player id
		//$playerId = '23';
		$playerId = $this->getPlayerIdInGameProviderAuth($playerName);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForTransferAmount',
			'playerName' => $playerName,
			'amount' => $amount,
			'playerId' => $playerId,
			'transfer_type' => $transferType,
		);

		$params = array(
			'VendorId' => $this->VendorId,
			'PlayerId' => $playerName,
			'Amount' => $this->gameAmountToDB($amount),
		);

		$this->CI->utils->debug_log('playerName', $playerName, 'params', $params);

		return $this->callApi(self::API_transfer, $params, $context);

	}

	public function processResultForTransferAmount($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$amount = $this->getVariableFromContext($params, 'amount');
		$playerId = $this->getVariableFromContext($params, 'playerId');
		$resultArr = $this->getResultObjFromParams($params);

		// $result = array();
		$success = false;

		$result = array(
			'response_result_id' => $responseResultId,
			'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id'=>self::REASON_UNKNOWN
		);
		if ($this->processResultBoolean($responseResultId, $resultArr, $playerName) && isset($resultArr['FundIntegrationId'])) {

			#PlayerId - vendor assigned player id (should be unique to each vendor)
			#Amount - fund transfer amount, allow negative
			#FundIntegrationId - passing the unique FundIntegrationId
			#VendorRef - vendor generate reference number for checking(NOTE: ICHANGED THIS TO VendorId/ Maybe wrong Docs )
			$this->CI->utils->debug_log('playerName', $playerName, 'id', @$resultArr['FundIntegrationId']);

			// $forConfirmation = array(
			// 	'PlayerId' => $playerName,
			// 	'FundIntegrationId' => @$resultArr['FundIntegrationId'],
			// 	'PlayerIP' => $this->CI->utils->getIP(),
			// 	'VendorId' => $this->VendorId,
			// 	'Amount' => $amount,
			// );
			$fundIntegrationId = @$resultArr['FundIntegrationId'];

			$rlt = $this->confirmTransaction($playerName, $fundIntegrationId, $amount);

			if ($rlt && $rlt['success']) {

				//var_dump($resultArr['FundIntegrationId'], $confirm_response);
				// $result["currentplayerbalance"] = $afterBalance;
				//get current sub wallet balance
				$playerBalance = $this->queryPlayerBalance($playerName);
				//for sub wallet
				$afterBalance = @$playerBalance['balance'];
				//$result["external_transaction_id"] = $fundIntegrationId;
				$result["currentplayerbalance"] = $afterBalance;
				$result["userNotFound"] = false;
				$success = true;
				//update
				// $playerId = $this->getPlayerIdInGameProviderAuth($playerName);

				$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
				// if ($playerId) {
				// 	//deposit
				// 	$this->insertTransactionToGameLogs($playerId, $playerName, $afterBalance, $amount, $responseResultId,
				// 		$this->transTypeMainWalletToSubWallet());

				// } else {
				// 	$this->CI->utils->debug_log('error', 'cannot get player id from ' . $playerName . ' getPlayerIdInGameProviderAuth');
				// 	$result["userNotFound"] = true;
				// }

				$result['didnot_insert_game_logs']=true;
			} else {
				$result["error_message"] = $resultArr['Error'];

				$error_code = @$resultArr['Error'];
				$result['reason_id'] = $this->getReason($error_code);
				$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
			}

		} else {
			if (isset($resultArr['Error'])) {
				$error_code = @$resultArr['Error'];
				$result['reason_id'] = $this->getReason($error_code);
				$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
			} else {
				$result["error_message"] = 'lost FundIntegrationId';
				$this->CI->utils->debug_log('playerName', $playerName, $result["error"]);
			}

		}

		return array($success, $result);

	}

	public function getReason($error_code) {
		switch($error_code) {
			case 'VENDOR ACCOUNT SUSPENDED' :
			case 'VENDOR NOT AUTHORIZED' :
				$reason_id = self::REASON_LOCKED_GAME_MERCHANT;
				break;
			case 'GAME SERVER MAINTENANCE' :
			case 'GAME SERVER NOT APPROPRIATE' :
			$reason_id = self::REASON_API_MAINTAINING;
				break;
			case 'PLAYER ACCOUNT SUSPENDED' :
				$reason_id = self::REASON_NOT_FOUND_PLAYER;
				break;
			case 'PLAYER CURRENCY NOT SUPPORT' :
			case 'PLAYER CURRENCY NOT MATCH BEFORE' :
			$reason_id = self::REASON_CURRENCY_ERROR;
				break;
			case 'INCORRECT API KEY' :
				$reason_id = self::REASON_INVALID_KEY;
				break;
			default :
				$reason_id = self::REASON_UNKNOWN;
		}
		return $reason_id;
	}


	public function confirmTransaction($playerName, $fundIntegrationId, $amount) {

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processConfrirmTransaction',
			'playerName' => $playerName,
			'amount' => $amount,
		);

		$params = array(
			'PlayerId' => $playerName,
			'FundIntegrationId' => $fundIntegrationId,
			'PlayerIP' => $this->CI->utils->getIP(),
			'VendorId' => $this->VendorId,
			'Amount' => $this->gameAmountToDB($amount),
		);

		$this->CI->utils->debug_log('playerName', $playerName, 'params', $params);

		return $this->callApi(self::API_checkFundTransfer, $params, $context);

		// $xmlrpcServer = $this->generateUrl(self::API_checkFundTransfer, null);
		// // $xmlrpcServer = $this->api_url . self::FUND_IN_OUT_CONFIRM_PAGE;

		// $this->CI->utils->debug_log('xml server', $xmlrpcServer, $forConfirmation);

		// list($header, $resultText, $statusCode, $statusText, $errCode, $error, $resultObj) =
		// $this->xmlrpcCallApi($xmlrpcServer, self::FUND_IN_OUT_CONFIRM, $forConfirmation);

		// $this->CI->utils->debug_log('result', $resultText, $forConfirmation);

		// return $resultObj;

	}

	public function processConfrirmTransaction($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		// $amount = $this->getVariableFromContext($params, 'amount');
		// $playerId = $this->getVariableFromContext($params, 'playerId');
		$resultArr = $this->getResultObjFromParams($params);

		$result = array();
		$success = false;

		if ($this->processResultBoolean($responseResultId, $resultArr, $playerName)) {
			$success = true;
			$result["external_game_balance"] = @$resultArr['Remain'];
		}

		return array($success, $result);
	}

	function queryTransaction($transactionId, $extra) {
		return $this->returnUnimplemented();
	}

	function depositToGame($playerName, $amount, $transfer_secure_id=null) {
		return $this->transferAmount($playerName, $amount, $transfer_secure_id, self::API_depositToGame);
	}

	function withdrawFromGame($playerName, $amount, $transfer_secure_id=null) {
		return $this->transferAmount($playerName, -$amount, $transfer_secure_id, self::API_withdrawFromGame);
	}

	const DEFAULT_PLAYER_CREDIT = 0;

	function login($playerName, $password = null) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);

		// $reference_no = random_string('numeric');

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForLogin',
			'playerName' => $playerName,
			// 'playerId' => $playerId,
		);

		$params = array(
			'VendorSite' => $this->VendorSite,
			'FundLink' => $this->utils->site_url_with_http('iframe_module/iframe_viewCashier'),
			'VendorId' => $this->VendorId,
			'PlayerId' => $playerName,
			'PlayerRealName' => $playerName,
			'PlayerCurrency' => $this->PlayerCurrency,
			'PlayerCredit' => self::DEFAULT_PLAYER_CREDIT,
			'PlayerAllowStake' => $this->PlayerAllowStake,
			'Trial' => $this->Trial,
			'PlayerIP' => $this->CI->utils->getIP(),
			'VendorRef' => $this->VendorRef,
			'Language' => $this->Language,
			'RebateLevel' => $this->RebateLevel,
			// 'Remarks' => $reference_no,
		);

        $this->CI->utils->debug_log('===================================== KENOGAME login request =====================================',$params);

		return $this->callApi(self::API_operatorLogin, $params, $context);
	}
	public function processResultForLogin($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		// $amount = $this->getVariableFromContext($params, 'amount');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$resultArr = $this->getResultObjFromParams($params);

        $this->CI->utils->debug_log('===================================== KENOGAME login response =====================================',$resultArr);

		$result = array();
		$success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);
		if ($success) {
			$result['url'] = $resultArr['Link'];
			$playerId = $this->getPlayerIdInGameProviderAuth($playerName);
			$this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
		} else {
			// $success = false;
			// $result['response'] = $resultArr['Error'];
		}

		return array($success, $result);
	}

	function logout($playerName, $password = null) {
		return $this->returnUnimplemented();

		// return array("success" => true);
	}

	function updatePlayerInfo($playerName, $infos) {
		return $this->returnUnimplemented();
		// return array("success" => true);
	}

	function queryPlayerBalance($playerName) {

		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		// $reference_no = random_string('numeric');

		//Get Player id
		//$playerId = '23';
		// $playerId = $this->getPlayerIdInGameProviderAuth($playerName);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryPlayerBalance',
			'playerName' => $playerName,
		);

		$params = array(
			'VendorId' => $this->VendorId,
			'PlayerId' => $playerName,
		);

		return $this->callApi(self::API_queryPlayerBalance, $params, $context);
		//return array("success" => true);

	}

	function processResultForQueryPlayerBalance($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		// $amount = $this->getVariableFromContext($params, 'amount');
		$playerName = $this->getVariableFromContext($params, 'playerName');

		// array (size=4)
		//'Credit' => string '13.00' (length=5)
		//'Jackpot' => string '0.00' (length=4)
		//'Status' => string '1' (length=1)
		//'LastLoginTime' => string '0000-00-00 00:00:00' (length=19)

		$resultArr = $this->getResultObjFromParams($params);
		$success = false;
		$result = array();

		if ($this->processResultBoolean($responseResultId, $resultArr, $playerName) && isset($resultArr['Credit'])) {
			$success = true;
			$result['balance'] = @floatval($resultArr['Credit']);

			if ($playerId = $this->getPlayerIdInGameProviderAuth($playerName)) {
				$this->CI->utils->debug_log('query balance playerId', $playerId, 'playerName', $playerName, 'balance', $result['balance']);
			} else {
				$this->CI->utils->debug_log('cannot get player id from ' . $playerName . ' getPlayerIdInGameProviderAuth');
			}

		} else {
			$success = false;
			if (@$resultArr['Error'] == 'PLAYER NOT FOUND') {
				$result['exists'] = false;
			} else {
				$result['exists'] = true;
			}
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

		// $playerName = $this->getGameUsernameByPlayerUsername($playerName);
		// $context = array(
		// 	'callback_obj' => $this,
		// 	'callback_method' => 'processResultForCheckLoginStatus',
		// 	'playerName' => $playerName,
		// );
		// $params = array(
		// 	'LoginID' => $this->api_key,
		// 	'LoginPass' => $this->api_secret,
		// 	'Function' => self::URI_MAP[self::API_checkLoginStatus],
		// 	'PlayerName' => $playerName,
		// );

		// return $this->callApi(self::API_checkLoginStatus, $params, $context);
		return array("success" => true);
	}

	public function checkLoginToken($playerName, $token) {
		return array("success" => true);

	}

	function totalBettingAmount($playerName, $dateTimeFrom, $dateTimeTo) {
		return array("success" => true);
	}

	function queryForwardGame($playerName, $extra) {

		$this->CI->utils->debug_log('queryForwardGame=login');

		$rlt = $this->login($playerName);

		return $rlt;
// 		$rlt = $this->checkLoginStatus($playerName);
		// 		$password = $this->getPasswordString($playerName);
		// 		if (!$rlt['success']) {
		// 			$rlt = $this->login($playerName, $password);
		// 			if (!$rlt['online']) {
		// 				return array(
		// 					'success' => false,
		// 				);
		// 			}
		// 		}

// 		//$reference_no = random_string('numeric');
		// 		$remarks = 'loadgame';
		// 		$playerId = $this->getPlayerIdInPlayer($playerName);

// 		$context = array(
		// 			'callback_obj' => $this,
		// 			'callback_method' => 'processLoadingGame',
		// 			'playerName' => $playerName,
		// 			'playerId' => $playerId,
		// 		);

// 		$params = array(
		// 			'VendorSite' => $this->VendorSite,
		// 			'FundLink' => $extra['FundLink'],
		// 			'VendorId' => $this->VendorId,
		// 			'PlayerId' => $playerName,
		// 			'PlayerRealName' => $playerName,
		// 			'PlayerCurrency' => $this->PlayerCurrency,
		// 			'PlayerCredit' => $extra['PlayerCredit'],
		// 			'PlayerAllowStake' => $this->PlayerAllowStake,
		// 			'Trial' => $extra['Trial'],
		// 			'PlayerIP' => $extra['PlayerIP'],
		// 			'VendorRef' => $this->VendorRef,
		// 			'Language' => $extra['Language'],
		// 			'RebateLevel' => $this->RebateLevel,
		// 			'Remarks' => $remarks,
		// 		);
		// //var_dump($params);

// 		return $this->callApi(self::API_operatorLogin, $params, $context);
	}

	// protected function processLoadingGame($params) {
	// 	$responseResultId = $this->getResponseResultIdFromParams($params);
	// 	$resultArr = $this->getValueFromParams($params, 'resultText');

	// 	$result = array();
	// 	$success = false;
	// 	if ($this->processResultBoolean($responseResultId, $resultArr)) {
	// 		$success = true;
	// 	} else {
	// 		$success = false;
	// 		$result['response'] = $resultArr['Error'];
	// 	}

	// 	return array($success, $resultArr);

	// }

	public function gameAmountToDB($amount) {
		$amount = floatval($amount);
		return round($amount, 2);
	}

	const DEFAULT_MINUTES_FOR_GAME_LOGS = 10;

	public function nextTime(DateTime $dateTime) {
		$min = $this->getSystemInfo('max_minutes_game_logs');
		if (empty($min)) {
			$min = self::DEFAULT_MINUTES_FOR_GAME_LOGS;
		}

		return $dateTime->modify('+' . $min . ' minutes');
	}

	function syncOriginalGameLogs($token = false) {

		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
		// $startDateStr = $startDate->format('Y-m-d H:i:s');
		// $endDateStr = $endDate->format('Y-m-d H:i:s');

		$startDate->modify($this->getDatetimeAdjust());

		//$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$reference_no = random_string('numeric');

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForSyncGameRecords',
		);

		$currentDateTime = clone $startDate;

		while ($this->CI->utils->compareDateTime($currentDateTime, $endDate) <= 0) {
			$this->CI->utils->debug_log('currentDateTime', $currentDateTime, 'endDate', $endDate);
			$datenow = new DateTime();
			$datenow->modify('- 10 minutes');

			if($datenow->format('YmdHis') <= $currentDateTime->format('YmdHis')){
				break; # the time you search need to be 10min before the current time (provider rule)
			}

			$params = array(
				'VendorId' => $this->VendorId,
				'Time' => $this->CI->utils->formatDateTimeForMysql($currentDateTime),
				'Token' => $this->game_info_token,
			);

			$rlt = $this->callApi(self::API_syncGameRecords, $params, $context);

			if (!@$rlt['success']) {
				$this->CI->utils->error_log('sync game logs failed', $rlt, 'currentDateTime', $currentDateTime, 'endDate', $endDate);
				break;
			}

			//start from +10
			$currentDateTime = $this->nextTime($currentDateTime);
		}

		return $rlt;
	}

	function processResultForSyncGameRecords($params) {

		// $resultJson = $this->getValueFromParams($params, 'resultText');

// $usersample = 'siopao42';
		// $resultJson = <<<EOD
		// 		 {
		// 		 	"STATUSCODE":"1",
		// 		 	"STATUSDESC":"SUCCESS",
		// 		 	"DATA":[
		// 		 			{
		// 		 				"BetId":"0023819721",
		// 		 				"GameCode":"KENO",
		// 		 				"PlayerId":"{$usersample}",
		// 		 				"BetType":"BIG_SMALL+ODD_EVEN",
		// 		 				"BetSlip":"BIG_EVEN",
		// 		 				"Odds":"3.8",
		// 		 				"RegionId":"SK",
		// 		 				"GameId":"201507290920",
		// 		 				"Amount":18,
		// 		 				"StakeAccurate":"18.00",
		// 		 				"Credit":"40994.75",
		// 		 				"Payout":-18,
		// 		 				"PJackpot":"0.09",
		// 		 				"CreateTime":"2015-07-29 15:17:32",
		// 		 				"UpdateTime":"2015-07-29 15:22:12"
		// 		 			},
		// 			 		{
		// 		 				"BetId":"0000008879",
		// 		 				"GameCode":"PK10",
		// 		 				"PlayerId":"{$usersample}",
		// 		 				"GameId":"2201507290925",
		// 		 				"RegionId":"SUP",
		// 		 				"BetType":"BET",
		// 		 				"Amount":10,
		// 		 				"StakeAccurate":"10.00",
		// 		 				"Payout":-10,
		// 		 				"BetSlip":"BIG",
		// 		 				"Odds":"1.95",
		// 		 				"Credit":"41735.02",
		// 		 				"PJackpot":"0.20",
		// 		 				"CreateTime":"2015-07-29 15:24:30",
		// 		 				"UpdateTime":"2015-07-29 15:25:00"
		// 					},
		// 					{
		// 		 				"BetId":"0009393765",
		// 		 				"GameCode":"KENO",
		// 		 				"PlayerId":"{$usersample}",
		// 		 				"BetType":"PARLAY_BET",
		// 		 				"BetSlip":[
		// 		 							{
		// 		 								"GameId":"762749",
		// 		 								"RegionId":"SUP",
		// 		 								"ParlayBetType":"BIG_SMALL",
		// 		 								"ParlaySubSlip":"SMALL",
		// 		 								"ParlaySubOdds":"1.95"
		// 		 							},
		// 		 							{
		// 		 								"GameId":"762749",
		// 		 								"RegionId":"SUP",
		// 		 								"ParlayBetType":"ODD_EVEN",
		// 		 								"ParlaySubSlip":"ODD",
		// 		 								"ParlaySubOdds":"1.95"}
		// 		 						     ],
		// 		 				"Amount":"50.00",
		// 		 				"StakeAccurate":"50.00",
		// 		 				"Credit":"7823.00",
		// 		 				"Payout":-50,
		// 		 				"PJackpot":"0.50",
		// 		 				"CreateTime":"2014-04-11 14:52:30",
		// 		 				"UpdateTime":"2014-04-11 14:53:00"
		// 		 			},
		// 					{
		// 		 				"BetId":"0000652738",
		// 		 				"GameCode":"SSC",
		// 		 				"PlayerId":"{$usersample}",
		// 		 				"BetType":"HUNDRED_BIG",
		// 		 				"RegionId":"SUP 3",
		// 		 				"GameId":"08201507290613",
		// 		 				"BetSlip":null,
		// 		 				"Odds":"1.95",
		// 		 				"Amount":25,
		// 		 				"StakeAccurate":"25.00",
		// 		 				"Credit":"42365.82",
		// 		 				"Payout":23.75,
		// 		 				"PJackpot":"0.25",
		// 		 				"CreateTime":"2015-07-29 15:19:51",
		// 		 				"UpdateTime":"2015-07-29 15:20:18"
		// 		 			}
		// 		 			]
		// 		 }

// EOD;

		$resultJson = $this->getValueFromParams($params, 'resultText');
		// $this->CI->utils->debug_log('resultJson =>>>>', $resultJson);

		$resultArr = json_decode($resultJson);

		/// $resultArr = $this->getResultJsonFromParams($params);
		$statusCode = @$resultArr->STATUSCODE;
		$statusText = @$resultArr->STATUSDESC;
		$keno_game_logs = @$resultArr->DATA;

		$success = false;
		$result = array();

		if ($statusCode == '0') {

			$result['error'] = $statusText;
			echo "<pre>";print_r($resultArr);exit;
		} else if ($statusCode == '1') {

			$responseResultId = $this->getResponseResultIdFromParams($params);

			$gameRecords = $keno_game_logs;
			//$this->CI->utils->debug_log('GAMERECORDS insert =>>>>', $gameRecords);

			$this->CI->load->model(array('kenogame_game_logs', 'external_system'));

			if ($gameRecords) {

				list($availableRows, $maxRowId) = $this->CI->kenogame_game_logs->getAvailableRows($gameRecords);

				//var_dump($availableRows, $maxRowId);exit();

				$this->CI->utils->debug_log('availableRows', count($availableRows), 'gameRecords', count($gameRecords), 'maxRowId', $maxRowId);

				if ($availableRows) {
					foreach ($availableRows as $record) {

						$kenogameData = array(
							"BetId" => $record->BetId,
							"GameCode" => $record->GameCode,
							"PlayerId" => $record->PlayerId,
							"BetType" => $record->BetType,
							"RegionId" => isset($record->RegionId) ? $record->RegionId : null,
							"GameId" => $record->GameId,
							"BetSlip" => isset($record->BetSlip) ? $record->BetSlip : 0,
							"Odds" => isset($record->Odds) ? @$record->Odds : 0,
							"Amount" => $record->Amount,
							"StakeAccurate" => $record->StakeAccurate,
							"Credit" => $record->Credit,
							"Payout" => $record->Payout,
							"PJackpot" => isset($record->PJackpot) ? $record->PJackpot : 0,
							"CreateTime" => $record->CreateTime,
							"UpdateTime" => $record->UpdateTime,
							'external_uniqueid' => $record->BetId,
							'response_result_id' => $responseResultId,
						);
						//$this->CI->utils->debug_log('KENOGAMEROW insert =>>>>', $kenogameData);

						$this->CI->kenogame_game_logs->insertKenoGameLogs($kenogameData);
						//var_dump($kenogameData);
					}

					if ($maxRowId) {
						$this->CI->external_system->setLastSyncId($this->getPlatformCode(), $maxRowId);
						$lastRowId = $maxRowId;
					}
				}

				$success = true;

			} else {

				$success = true;
			}

		}

		return array($success, $result);

	}

	private function getGameInfoFromOriginal($row) {

		$game_type = $row->game_name;
		if (isset($row->RegionId)) {
			$game = $row->game_name . ' ' . $row->RegionId;
		} else {
			$game = $row->game_name;
		}
		$game_code = $game;
		$externalGameId = $game_code;

		return array($game_type, $game, $game_code, $externalGameId);
	}

	private function getGameDescriptionInfo($row, $unknownGame) {
		// $this->CI->utils->debug_log('GetGameDescriptionInfo Row =>>>>>>>', $row);
		list($game_type, $game, $game_code, $externalGameId) = $this->getGameInfoFromOriginal($row);

		$extra = array('game_code' => $game_code);
		return $this->processUnknownGame(
			null, $game_code,
			$game, $game_type, $externalGameId, $extra,
			$unknownGame);
	}

	function syncMergeToGameLogs($token) {

		$this->CI->load->model(array('game_logs', 'player_model', 'kenogame_game_logs'));

		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
		$startDate = $startDate->format('Y-m-d H:i:s');
		$endDate = $endDate->format('Y-m-d H:i:s');

		$this->CI->utils->debug_log('dateTimeFrom', $startDate, 'dateTimeTo', $endDate);

		$rlt = array('success' => true);
		$result = $this->CI->kenogame_game_logs->getKenogameGameLogStatistics($startDate, $endDate);
		//	var_dump($result); exit();

		$cnt = 0;
		if ($result) {

			$unknownGame = $this->getUnknownGame();
			// var_dump($unknownGame);

			foreach ($result as $kenogame_data) {

				// if (!$player_id = $this->getPlayerIdInGameProviderAuth($kenogame_data->player_name)) {
				// 	continue;
				// }

				$cnt++;

				// $player = $this->CI->player_model->getPlayerById($player_id);

				$bet_amount = $this->gameAmountToDB($kenogame_data->StakeAccurate);
				$result_amount = $this->gameAmountToDB($kenogame_data->result_amount);
				// $game_description_id = $kenogame_data->game_description_id;
				// $game_type_id = $kenogame_data->game_type_id;

				list($game_description_id, $game_type_id) = $this->getGameDescriptionInfo($kenogame_data, $unknownGame);

				$this->syncGameLogs(
					//$r =array(
					$game_type_id, # $game_type_id
					$game_description_id, # $game_description_id
					$kenogame_data->game_name, # $game_code
					$game_type_id, #$game_type
					$kenogame_data->game_name, # $game
					$kenogame_data->player_id, # $player_id
					$kenogame_data->player_name, #$player_username
					$bet_amount,
					$result_amount,
					null, # win_amount
					null, # loss_amount
					$kenogame_data->after_balance, # after_balance
					0, # has_both_side
					$kenogame_data->external_uniqueid,
					$kenogame_data->game_date,
					$kenogame_data->game_date,
					$kenogame_data->response_result_id# response_result_id
				);

				//   var_dump($r);

			}
		}
		$this->CI->utils->debug_log('syncMergeToGameLogs monitor', 'count', $cnt);

		return $rlt;
	}

}

/*end of file*/