<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

class Game_api_lapis extends Abstract_game_api {
	private $lapis_api_url;
	private $lapis_api_username;
	private $lapis_api_password;
	private $hor_username;
	private $hor_password;
	private $lapis_currency;
	private $lapis_language;

	private $lapis_api_crtype;
	private $lapis_api_tartype;
	private $lapis_api_partnerid;
	private $lapis_banking_url;
	private $lapis_lobby_url;
	private $lapis_logout_redirect_url;
	private $lapis_api_member_username;
	private $lapis_api_member_password;
	private $lapis_api_product;
	private $lapis_api_agenttx;

	private $api_login_token;
	private $api_network_id;
	private $apiCall;

	const STATUS_SUCCESS = '0';
	const ORIGINAL_LOGS_TABLE_NAME = 'mglapis_game_logs';
	const MD5_FIELDS_FOR_ORIGINAL=['key','col_id','mbr_id','mbr_code','trans_type','trans_id','mgs_game_id','mgs_action_id','clearing_amount','balance_after_bet','trans_time','bet_amount','result_amount'];
	const MD5_FLOAT_AMOUNT_FIELDS=['bet_amount','result_amount','clearing_amount'];
	const MD5_FIELDS_FOR_MERGE=['trans_type','bet_amount','real_bet_amount','result_amount','after_balance','start_at','bet_at','status'];
	const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE=['bet_amount','real_bet_amount','result_amount','after_balance'];
	public function __construct() {
		parent::__construct();
		$this->CI->load->model(array('mglapis_game_logs'));
		$this->lapis_api_url = $this->getSystemInfo('url');
		$this->lapis_api_username = $this->getSystemInfo('key');
		$this->lapis_api_password = $this->getSystemInfo('secret');
		$this->hor_username = $this->getSystemInfo('hor_username');
		$this->hor_password = $this->getSystemInfo('hor_password');
		$this->lapis_currency = $this->getSystemInfo('lapis_currency');
		$this->lapis_language = $this->getSystemInfo('lapis_language');
		$this->lapis_api_crtype = $this->getSystemInfo('lapis_api_crtype');
		$this->lapis_api_tartype = $this->getSystemInfo('lapis_api_tartype');
		$this->lapis_api_partnerid = $this->getSystemInfo('lapis_api_partnerid');
		$this->lapis_banking_url = $this->getSystemInfo('lapis_banking_url');
		$this->lapis_lobby_url = $this->getSystemInfo('lapis_lobby_url');
		$this->lapis_logout_redirect_url = $this->getSystemInfo('lapis_logout_redirect_url');
		$this->lapis_api_member_username = $this->getSystemInfo('lapis_api_member_username');
		$this->lapis_api_member_password = $this->getSystemInfo('lapis_api_member_password');
		$this->lapis_api_product = $this->getSystemInfo('lapis_api_product');
		$this->lapis_api_agenttx = $this->getSystemInfo('lapis_api_agenttx');
		$this->lapis_api_agenttype = $this->getSystemInfo('lapis_api_agenttype');
		$this->system_prefix = $this->getSystemInfo('system_prefix','995');//default system prefix for xcbet
		$this->syncSleepTime = $this->getSystemInfo('syncSleepTime',60); 
		//$this->is_update_original_row = $this->getSystemInfo('is_update_original_row');
	}

	public function getPlatformCode() {
		return LAPIS_API;
	}

	protected function customHttpCall($ch, $params) {
		$header_params = array(
			'X-Requested-With: X-Api-Client',
			'X-Api-Call: X-Api-Client',
		);
		if ($this->apiCall == self::API_operatorLogin) {
			$header_params[] = "Content-Type: application/x-www-form-urlencoded";
			$header_params[] = "Content-Type: application/json";
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
		} elseif ($this->apiCall == self::API_createPlayer) {
			$header_params[] = "X-Api-Auth: " . $this->api_login_token;
			$header_params[] = "Content-Type: application/json";
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
		} elseif ($this->apiCall == self::API_syncGameRecords) {
			$header_params[] = "X-Api-Auth: " . $this->api_login_token;
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		} else {
			$header_params[] = "Content-Type: text/xml";
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
		}
		$this->utils->debug_log('headers', $header_params, $this->apiCall, $params);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header_params);
	}

	public function generateNowUTC(){
		$d=new DateTime();
		$modify='-8 hours';
		return $this->utils->modifyDateTime($d->format('Y-m-d H:i:s'), $modify).' UTC';
		// return $d->format('Y-m-d H:i:s').' UTC';
	}

	public function generateUrl($apiName, $params) {
		$url = null;
		if ($apiName == self::API_operatorLogin) {
			$url = $this->lapis_api_url . '/lps/j_spring_security_check';
		} elseif ($apiName == self::API_createPlayer) {
			$url = $this->lapis_api_url . '/lps/secure/network/' . $this->api_network_id . '/downline';
		} elseif ($apiName == self::API_syncGameRecords) {
			$agent_type = "hortx";
			if(!empty($this->lapis_api_agenttype)){
				$agent_type = $this->lapis_api_agenttype;
			}
			$url = $this->lapis_api_url . '/lps/secure/'.$agent_type.'/' . $this->api_network_id . '?' . urldecode(http_build_query($params));
		} else {
			$url = $this->lapis_api_url . '/member-api-web/member-api';
		}
		return $url;
	}

	public function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null) {
		return array(false, null);
	}

	public function processResultBoolean($responseResultId, $params, $resultArr, $playerName = null, $isXmlResultType = false) {
		$success = !empty($resultArr);
		if (!$success || ($isXmlResultType && $resultArr['status'] != self::STATUS_SUCCESS)) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('mg lapis got error', $responseResultId, 'playerName', $playerName, 'result', $params['resultText']);
			$success = false;
		}
		return $success;
	}

	public function processGameList($game) {
		$game = parent::processGameList($game);
		$this->CI->load->model(array('game_description_model'));
		$extra = $this->CI->game_description_model->getGameTypeById($game['g']);
		$game['gp'] = "iframe_module/goto_mglapis_game/" . $game['c']; //game param
		return $game;
	}

	public function getToken($mode=null) {
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForGetToken',
		);

		$params = array(
			"j_username" => $this->lapis_api_username,
			"j_password" => $this->lapis_api_password,
		);
		if($mode=="gamelogs"){
			$params = array(
				"j_username" => $this->hor_username,
				"j_password" => $this->hor_password,
			);
		}
		$this->apiCall = self::API_operatorLogin;
		return $this->callApi(self::API_operatorLogin, $params, $context);
	}

	public function processResultForGetToken($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$success = false;
		$success = $this->processResultBoolean($responseResultId, $params, $resultJson);
		$result = array();
		if ($success) {
			$this->api_login_token = $resultJson['token'];
			$this->api_network_id = $resultJson['id'];
		}
		return array($success, $resultJson);
	}

	//===start createPlayer=====================================================================================
	public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
		$tokenResult = $this->getToken();
		if (@$tokenResult['success']) {
			parent::createPlayer($playerName, $playerId, $password, $email, $extra);
			$playerName = $this->getGameUsernameByPlayerUsername($playerName);
			$context = array(
				'callback_obj' => $this,
				'callback_method' => 'processResultForCreatePlayer',
				'playerName' => $playerName,
				'playerId' => $playerId,
			);

			$params = array(
				"crId" => $this->api_network_id,
				"crType" => $this->lapis_api_crtype,
				"neId" => $this->api_network_id,
				"neType" => $this->lapis_api_crtype,
				"tarType" => $this->lapis_api_tartype,
				"username" => $playerName,
				"name" => $playerName,
				"password" => $password,
				"confirmPassword" => $password,
				"currency" => $this->lapis_currency,
				"language" => $this->lapis_language,
				"casino" => array("enable" => true),
				"poker" => array("enable" => false),
			);
			$this->apiCall = self::API_createPlayer;
			return $this->callApi(self::API_createPlayer, $params, $context);
		}

		return ['success'=>false];
	}

	public function processResultForCreatePlayer($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$playerId = $this->getVariableFromContext($params, 'playerId');
		$success = $this->processResultBoolean($responseResultId, $params, $resultJson, $playerName);

		$result=['response_result_id'=>$responseResultId];

		if(isset($resultJson['success'])){
			$success=$resultJson['success'];
			unset($resultJson['success']);
			if(!$success){
				if(strpos(@$resultJson['message'] , 'existed')!==FALSE){
					//user exists
					$success=true;
				}
			}
		}

		//update register
		if ($success) {
			$this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
		}

		return array($success, $result);
	}

	//===end createPlayer=====================================================================================

	public function isPlayerExist($playerName) {

		// $rlt=$this->queryPlayerBalance($playerName);

		// $result=['response_result_id'=>$rlt['response_result_id']];
		// if($rlt['success']){
		// 	$result['success']=true;
		// 	if(isset($rlt["balance"])){
		// 		$result['exists']=true;
		// 	}else{
		// 		$result['exists']=false;
		// 	}
		// }else{
		// 	$result['success']=false;
		// 	$result['exists']=null;
		// }

		// return $result;

		$playerId=$this->getPlayerIdFromUsername($playerName);

		$playerName = $this->getGameUsernameByPlayerUsername($playerName);

		$password = $this->getPasswordByGameUsername($playerName);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForIsPlayerExists',
			'playerName' => $playerName,
			'playerId'=>$playerId,
		);

		$xmlData = "<mbrapi-login-call timestamp=\"" . $this->generateNowUTC() . "\" apiusername=\"" . $this->lapis_api_member_username . "\" apipassword=\"" . $this->lapis_api_member_password . "\" username=\"" . $playerName . "\" password=\"" . $password . "\" ipaddress=\"" . $this->utils->getIP() . "\" partnerId=\"" . $this->lapis_api_partnerid . "\" currencyCode=\"" . $this->lapis_currency . "\"/>";
		$this->apiCall=self::API_checkLoginToken;
		return $this->callApi(self::API_checkLoginToken, $xmlData, $context);
	}

	public function processResultForIsPlayerExists($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultXmlArr = $this->getResultXmlFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
        $playerId = $this->getVariableFromContext($params, 'playerId');
		$success = $this->processResultBoolean($responseResultId, $params, $resultXmlArr, $playerName, true);

		$result=['response_result_id'=>$responseResultId];
		$result['exists']=null;

		if ($success) {

			$result['exists']=true;

	        $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);

		}else{

			if(!empty($resultXmlArr)){

				if(isset($resultXmlArr['status'])){
					$success=true;
					//check status
					$result['exists']=false;
				}

			}

		}

		return [$success, $result];
	}

	//===start queryPlayerInfo=====================================================================================
	public function queryPlayerInfo($playerName) {
		return $this->returnUnimplemented();
	}
	//===end queryPlayerInfo=====================================================================================

	//===start changePassword=====================================================================================
	public function changePassword($playerName, $oldPassword, $newPassword) {
		// return $this->returnUnimplemented();

		return ['success'=>false];
	}
	//===end changePassword=====================================================================================

	//===start blockPlayer=====================================================================================
	public function blockPlayer($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$success = $this->blockUsernameInDB($playerName);
		return array("success" => true);
	}
	//===end blockPlayer=====================================================================================

	//===start unblockPlayer=====================================================================================
	public function unblockPlayer($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$success = $this->unblockUsernameInDB($playerName);
		return array("success" => true);
	}
	//===end unblockPlayer=====================================================================================

	//===start depositToGame=====================================================================================
	public function depositToGame($playerName, $amount, $transfer_secure_id = null) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$result = $this->getGamePlayerToken($playerName);
		if (!empty($result) && $result['success']) {
			$transId = "tx-" . random_string('numeric');
			$context = array(
				'callback_obj' => $this,
				'callback_method' => 'processResultForDepositToGame',
				'playerName' => $playerName,
				'amount' => $amount,
				'transId' => $transId,
			);

			$xmlData = "<mbrapi-changecredit-call timestamp=\"" . $this->generateNowUTC() . "\" apiusername=\"" . $this->lapis_api_member_username . "\" apipassword=\"" . $this->lapis_api_member_password . "\" token=\"" . @$result['success']['token'] . "\" product=\"" . $this->lapis_api_product . "\" operation=\"topup\" amount=\"" . $amount . "\"  tx-id=\"" . $transId . "\"/>";
			$this->apiCall=self::API_depositToGame;
			return $this->callApi(self::API_depositToGame, $xmlData, $context);
		}

		return ['success'=>false];
	}

	public function processResultForDepositToGame($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultXmlArr = $this->getResultXmlFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$amount = $this->getVariableFromContext($params, 'amount');
		$transId = $this->getVariableFromContext($params, 'transId');
		$success = $this->processResultBoolean($responseResultId, $params, $resultXmlArr, $playerName, true);
		$result = array('response_result_id' => $responseResultId);
		$afterBalance = null;
		if ($success) {
			//get current sub wallet balance
			$playerBalance = $this->queryPlayerBalance($playerName);
			//for sub wallet
			$afterBalance = isset($playerBalance['balance']) ? $playerBalance['balance'] : null;

			//update
			$playerId = $this->getPlayerIdInGameProviderAuth($playerName);
			if ($playerId) {
				//deposit
				$this->insertTransactionToGameLogs($playerId, $playerName, $afterBalance, $amount, $responseResultId,
					$this->transTypeMainWalletToSubWallet());
			} else {
				$this->CI->utils->error_log('cannot get player id from ' . $playerName . ' getPlayerIdInGameProviderAuth');
			}

		}

		$result['after_balance'] = $afterBalance;
		$result['external_transaction_id'] = $transId;

		return array($success, $result);
	}

	//===end depositToGame=====================================================================================

	//===start withdrawFromGame=====================================================================================
	public function withdrawFromGame($playerName, $amount, $transfer_secure_id = null) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$result = $this->getGamePlayerToken($playerName);
		if (!empty($result) && $result['success']) {
			$transId = "tx-" . random_string('numeric');
			$context = array(
				'callback_obj' => $this,
				'callback_method' => 'processResultForWithdrawToGame',
				'playerName' => $playerName,
				'amount' => $amount,
				'transId' => $transId,
			);

			$xmlData = "<mbrapi-changecredit-call timestamp=\"" . $this->generateNowUTC() . "\" apiusername=\"" . $this->lapis_api_member_username . "\" apipassword=\"" . $this->lapis_api_member_password . "\" token=\"" . @$result['success']['token'] . "\" product=\"" . $this->lapis_api_product . "\" operation=\"withdraw\" amount=\"" . $amount . "\" tx-id=\"" . $transId . "\"/>";

			$this->apiCall=self::API_withdrawFromGame;
			return $this->callApi(self::API_withdrawFromGame, $xmlData, $context);
		}

		return ['success'=>false];
	}

	public function processResultForWithdrawToGame($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultXmlArr = $this->getResultXmlFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$amount = $this->getVariableFromContext($params, 'amount');
		$transId = $this->getVariableFromContext($params, 'transId');
		$success = $this->processResultBoolean($responseResultId, $params, $resultXmlArr, $playerName, true);

		$result = array('response_result_id' => $responseResultId);
		if ($success) {
			//get current sub wallet balance
			$playerBalance = $this->queryPlayerBalance($playerName);

			//for sub wallet
			$afterBalance = isset($playerBalance['balance']) ? $playerBalance['balance'] : null;

			//update
			$playerId = $this->getPlayerIdInGameProviderAuth($playerName);
			if ($playerId) {
				//withdrawal
				$this->insertTransactionToGameLogs($playerId, $playerName, $afterBalance, $amount, $responseResultId,
					$this->transTypeSubWalletToMainWallet());
			} else {
				$this->CI->utils->error_log('cannot get player id from ' . $playerName . ' getPlayerIdInGameProviderAuth');
			}
		}

		$result['after_balance'] = $afterBalance;
		$result['external_transaction_id'] = $transId;

		return array($success, $result);
	}

	//===end withdrawFromGame=====================================================================================

	//===start login=====================================================================================
	public function login($playerName, $password = null) {
		return $this->returnUnimplemented();
	}

	public function getGamePlayerToken($playerName) {
		// $playerId = $this->getPlayerIdInPlayer($playerName);
		// $password = $this->decryptPassword($this->getPlayerInfo($playerId)->password);
		$password = $this->getPasswordByGameUsername($playerName);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForGetPlayerToken',
			'playerName' => $playerName,
		);

		$xmlData = "<mbrapi-login-call timestamp=\"" . $this->generateNowUTC() . "\" apiusername=\"" . $this->lapis_api_member_username . "\" apipassword=\"" . $this->lapis_api_member_password . "\" username=\"" . $playerName . "\" password=\"" . $password . "\" ipaddress=\"" . $this->utils->getIP() . "\" partnerId=\"" . $this->lapis_api_partnerid . "\" currencyCode=\"" . $this->lapis_currency . "\"/>";

		return $this->callApi(self::API_checkLoginToken, $xmlData, $context);
	}

	public function processResultForGetPlayerToken($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultXmlArr = $this->getResultXmlFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $params, $resultXmlArr, $playerName, true);
		if ($success) {
			return $resultXmlArr;
		}

		return null;
	}
	//===end login=====================================================================================

	//===start logout=====================================================================================
	public function logout($playerName, $password = null) {
		return $this->returnUnimplemented();
	}

	//===end logout=====================================================================================

	//===start updatePlayerInfo=====================================================================================
	public function updatePlayerInfo($playerName, $infos) {
		return $this->returnUnimplemented();
	}

	//===end updatePlayerInfo=====================================================================================

	//===start queryPlayerBalance=====================================================================================
	public function queryPlayerBalance($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$result = $this->getGamePlayerToken($playerName);
		if ($result) {
			$context = array(
				'callback_obj' => $this,
				'callback_method' => 'processResultForQueryPlayerBalance',
				'playerName' => $playerName,
			);

			$xmlData = "<mbrapi-account-call timestamp=\"" . $this->generateNowUTC() . "\" apiusername=\"" . $this->lapis_api_member_username . "\" apipassword=\"" . $this->lapis_api_member_password . "\" token=\"" . @$result['success']['token'] . "\" />";

			$this->apiCall=self::API_queryPlayerBalance;
			return $this->callApi(self::API_queryPlayerBalance, $xmlData, $context);
		}

		return ['success'=>false];
	}

	public function processResultForQueryPlayerBalance($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultXmlArr = $this->getResultXmlFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $params, $resultXmlArr, $playerName, true);
		$result = array();
		$wallet = $resultXmlArr->wallets->{'account-wallet'}['credit-balance'];

		if ($success && isset($resultXmlArr->wallets)) {
			$result["balance"] = floatval(@$resultXmlArr->wallets->{'account-wallet'}['credit-balance']);
			$playerId = $this->getPlayerIdInGameProviderAuth($playerName);
			$this->CI->utils->debug_log('query balance playerId', $playerId, 'playerName', $playerName,
				'balance', @$result["balance"]);
			if ($playerId) {
				//should update database
				// $this->updatePlayerSubwalletBalance($playerId, $result["balance"]);
			} else {
				log_message('error', 'cannot get player id from ' . $playerName . ' getPlayerIdInGameProviderAuth');
			}
		} else {
			$success = false;
		}
		return array($success, $result);
	}
	//===end queryPlayerBalance=====================================================================================

	//===start queryPlayerDailyBalance=====================================================================================
	public function queryPlayerDailyBalance($playerName, $playerId, $dateFrom = null, $dateTo = null) {
		$daily_balance = parent::getPlayerDailyBalance($playerName, $playerId, $dateFrom = null, $dateTo = null);

		$result = array();

		if ($daily_balance != null) {
			foreach ($daily_balance as $key => $value) {
				$result[$value['updated_at']] = $value['balance'];
			}
		}

		return array_merge(array('success' => true, "balanceList" => $result));
	}
	//===end queryPlayerDailyBalance=====================================================================================

	//===start queryGameRecords=====================================================================================
	public function queryGameRecords($dateFrom, $dateTo, $playerName = null) {
		$gameRecords = parent::getGameRecords($dateFrom, $dateTo, $playerName, $this->getPlatformCode());
		return array('success' => true, 'gameRecords' => $gameRecords);
	}
	//===end queryGameRecords=====================================================================================

	//===start checkLoginStatus=====================================================================================
	public function checkLoginStatus($playerName) {
		return $this->returnUnimplemented();
	}
	//===end checkLoginStatus=====================================================================================

	//===start totalBettingAmount=====================================================================================
	public function totalBettingAmount($playerName, $dateFrom, $dateTo) {
		return $this->returnUnimplemented();
	}
	//===end totalBettingAmount=====================================================================================

	//===start queryTransaction=====================================================================================
	public function queryTransaction($transactionId, $extra) {
		return $this->returnUnimplemented();
	}
	public function processResultForQueryTransaction($apiName, $params, $responseResultId, $resultXml) {
		return $this->returnUnimplemented();
	}
	//===end queryTransaction=====================================================================================

	//===start queryForwardGame=====================================================================================
	public function queryForwardGame($playerName, $extra) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$result = $this->getGamePlayerToken($gameUsername);
		if (!empty($result) && $result['success']) {
			$context = array(
				'callback_obj' => $this,
				'callback_method' => 'processResultForQueryForward',
				'playerName' => $playerName,
			);

			$demoMode = $extra['game_mode'] == 'real' ? 'false' : 'true';
			$this->lapis_lobby_url = $this->CI->utils->getSystemUrl('player');
			$this->lapis_logout_redirect_url = $this->CI->utils->getSystemUrl('player');
			$this->lapis_banking_url = $this->utils->getSystemUrl('player','/iframe_module/iframe_viewMiniCashier/'. $this->getPlatformCode());
			//convert timezone
			$xmlData = "<mbrapi-launchurl-call timestamp=\"" . $this->generateNowUTC() . "\" apiusername=\"" . $this->lapis_api_member_username . "\" apipassword=\"" . $this->lapis_api_member_password . "\" token=\"" . @$result['success']['token'] . "\" language=\"" . $this->lapis_language . "\" gameId=\"" . $extra['game_code'] . "\" bankingUrl=\"" . site_url($this->lapis_banking_url) . "\" lobbyUrl=\"" . site_url($this->lapis_lobby_url) . "\" logoutRedirectUrl=\"" . site_url($this->lapis_logout_redirect_url) . "\" demoMode=\"" . $demoMode . "\" />";

			$this->apiCall=self::API_queryForwardGame;
			$resultArr = $this->callApi(self::API_queryForwardGame, $xmlData, $context);
			if ($resultArr) {
				return array(
					'success' => true,
					'url' => (string) @$resultArr['success']['launchUrl'],
					'iframeName' => "MG",
				);
			}
		}
		return [
			'success' => false,
			'iframeName' => "MG",
		];
	}

	public function processResultForQueryForward($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultXmlArr = $this->getResultXmlFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $params, $resultXmlArr, $playerName);
		if ($success) {
			return $resultXmlArr;
		}
	}
	//===end queryForwardGame=====================================================================================

	//===start syncGameRecords=====================================================================================
	/**
	 *
	 */
	public function syncOriginalGameLogs($token) {
		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

		$startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
		$endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));

		$startDate->modify($this->getDatetimeAdjust());

		//$endDate->modify('+ 30 minutes');
		$this->CI->utils->debug_log('----------------------------------------------------------------------startDate', $startDate, 'endDate', $endDate);
		
		$mode = 'gamelogs';
		$tokenResult = $this->getToken($mode);
		$result = array();

		if (@$tokenResult['success']) {
			$result[] = $this->CI->utils->loopDateTimeStartEnd($startDate, $endDate, '+30 minutes', function($startDate, $endDate) {
				$startDate = new DateTime($startDate->format('Y-m-d H:i:s'));
				$startDate->modify("-30 minutes");
				$context = array(
					'callback_obj' => $this,
					'callback_method' => 'processResultForSyncGameRecords',
					'api_method' => self::API_syncGameRecords,
				);
				$data = array(
					"start" => $startDate->format('Y:m:d:H:i:s'),
					"end" => $endDate->format('Y:m:d:H:i:s'),
					"timezone" => 'UTC',
				);

				$this->apiCall = self::API_syncGameRecords;
				return $this->callApi(self::API_syncGameRecords, $data, $context);
				sleep($this->syncSleepTime);
			});
			return array(true, $result);	
		}else{
			return ['success'=>false];
		}
	}

	// const GAME_TIMEZONE = 'UTC';
	// const SYSTEM_TIMEZONE = 'Asia/Hong_Kong';

	// private function convertServerTimeToGameTime($dateTimeStr) {
	// 	//from UTC TO UTC+8
	// 	if ($dateTimeStr) {
	// 		$dateTimeStr = $this->CI->utils->convertTimezone($dateTimeStr, self::SYSTEM_TIMEZONE, self::GAME_TIMEZONE);
	// 	}

	// 	return $dateTimeStr;
	// }

	public function processGameRecords($rows,$responseResultId){
		$results = array();
		if(!empty($rows)){
			foreach ($rows as $key => $row) {
				if($row['key'] == "Unable finish the request. HOR has other HorTx request on processing, please let it finish before a new call."){
					$this->CI->utils->debug_log('[---------MG LAPIS SYNC ORIGINAL LOGS  ---------]', 'Unable finish the request. HOR has other HorTx request on processing, please let it finish before a new call.');
					sleep($this->syncSleepTime);
					continue;
				}
				$trans_time = null;
            	if(isset($row["transTime"])){
					$trans_time = $this->utils->convertTimestampToDateTime($row["transTime"]);
            	}else{
					$trans_time = $this->utils->convertTimestampToDateTime($row["transactionTimestampDate"]);
            	}
				$trans_time_converted = $this->gameTimeToServerTime($trans_time);
				$type = isset($row["type"]) ? $row["type"] : $row["type"];
				$betTransKey = isset($row["betTransKey"]) ? $row["betTransKey"] : $row["betTransKey"];
				$mbrCode = isset($row["mbrCode"]) ? $row["mbrCode"] : $row["mbrCode"];
				$gameKey = isset($row["gameKey"]) ? $row["gameKey"] : $row["gameKey"];
				$data=[
					"key" => isset($row["key"]) ? $row["key"] : $row["key"],
					"col_id" => isset($row["colId"]) ? $row["colId"] : $row["colId"],
					"mbr_id" => isset($row["mbrId"]) ? $row["mbrId"] : $row["mbrNeKey"] ,
					"mbr_code" => isset($row["mbrCode"]) ? $row["mbrCode"] : $row["mbrCode"],
					"trans_type" => ltrim($type,"mgsaspi"),
					"trans_id" => ltrim($betTransKey,"BetTransaction:"),
					"mgs_game_id" => isset($row["mgsGameId"]) ? $row["mgsGameId"] : $row["mgsGameId"],
					"mgs_action_id" => isset($row["mgsActionId"]) ? $row["mgsActionId"] : $row["mgsActionId"],
					"clearing_amount" => isset($row["clrngAmnt"]) ? $row["clrngAmnt"] : $row["clrngAmnt"],
					"balance_after_bet" => isset($row["afterTxWalletAmount"]) ? $row["afterTxWalletAmount"] : $row["afterTxWalletAmount"],
					"ref_trans_id" => isset($row["refTransId"]) ? $row["refTransId"] : $row["refKey"] ,
					"ref_trans_type" => isset($row["refTransType"]) ? $row["refTransType"] : $row["refType"],
					"sync_datetime" => $this->utils->getNowForMysql(),

                	"player_name" => ltrim($mbrCode,$this->system_prefix.":"),
                	"game_id" => ltrim($gameKey,"Game:"),
					"trans_time" => $trans_time_converted,
					"external_uniqueid" => ltrim($betTransKey,"BetTransaction:"),
					"response_result_id" => $responseResultId,
				];
				$amount = (float) isset($row["amount"]) ? $row["amount"] : $row["amount"];
				if($data['trans_type']=='bet'){
					$data['bet_amount']=$amount;
					// $data['result_amount']=-$amount;
					$data['result_amount']= -$amount;
				}elseif($data['trans_type']=='win'){
					$data['bet_amount']=0;
					$data['result_amount']=$amount;
				}elseif($data['trans_type']=='refund'){
					$data['bet_amount'] = $amount;
					$data['result_amount'] = 0;					
				}
				$results[] = $data;
			}
		}
		return $results;
	}

	private function updateOrInsertOriginalGameLogs($data, $queryType){
        $dataCount=0;
        if(!empty($data)){
            foreach ($data as $record) {
                if ($queryType == 'update') {
                	// $record['updated_at'] = $this->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->updateRowsToOriginal(self::ORIGINAL_LOGS_TABLE_NAME, $record);
                } else {
                    unset($record['id']);
                    // $record['created_at'] = $this->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->insertRowsToOriginal(self::ORIGINAL_LOGS_TABLE_NAME, $record);
                }
                $dataCount++;
                unset($record);
            }
        }
        return $dataCount;
    }

	public function processResultForSyncGameRecords($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJsonArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $params, $resultJsonArr);
		$this->CI->utils->debug_log('[---------MG LAPIS SYNC ORIGINAL LOGS RESULT COUNT ---------]', count($resultJsonArr));
		$this->CI->load->model(array('mglapis_game_logs', 'external_system','original_game_logs_model'));
		$result = array(
			'data_count' => 0,
			'data_count_insert'=> 0,
			'data_count_update'=> 0
		);
		if ($success) {
			$gameRecords = $resultJsonArr;
			if (!empty($gameRecords)) {
				$gameRecords = $this->processGameRecords($gameRecords, $responseResultId);

				list($insertRows, $updateRows)=$this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
	                self::ORIGINAL_LOGS_TABLE_NAME,
	                $gameRecords,
	                'external_uniqueid',
	                'external_uniqueid',
	                self::MD5_FIELDS_FOR_ORIGINAL,
	                'md5_sum',
	                'id',
	                self::MD5_FLOAT_AMOUNT_FIELDS
	            );

	            $this->CI->utils->debug_log('after process available rows', 'gamerecords ->',count($gameRecords), 'insertrows->',count($insertRows), 'updaterows->',count($updateRows));

	            $result['data_count'] = count($gameRecords);
				if (!empty($insertRows)) {
					$result['data_count_insert'] += $this->updateOrInsertOriginalGameLogs($insertRows, 'insert');
				}
				unset($insertRows);

				if (!empty($updateRows)) {
					$result['data_count_update'] += $this->updateOrInsertOriginalGameLogs($updateRows, 'update');
				}
				unset($updateRows);

				//old
				// $this->CI->mglapis_game_logs->syncRecords($gameRecords, $responseResultId);
			}
		}
		return array($success, $result);
	}

	private function invertSign($amount, $toPositive = false) {
		$amount = $toPositive ? abs($amount) : -$amount;
		return $amount;
	}

	public function getGameTimeToServerTime() {
		return '+8 hours';
	}

	public function getServerTimeToGameTime() {
		return '-8 hours';
	}

   // private $lastDateTimeSync = null;
    private $syncCount = 0;

    public function syncMergeToGameLogs($token) {
		$enabled_game_logs_unsettle=true;
        return $this->commonSyncMergeToGameLogs($token,
            $this,
            [$this, 'queryOriginalGameLogs'],
            [$this, 'makeParamsForInsertOrUpdateGameLogsRow'],
            [$this, 'preprocessOriginalRowForGameLogs'],
            $enabled_game_logs_unsettle);
	}

	/**
     * queryOriginalGameLogs
     * @param  string $dateFrom
     * @param  string $dateTo
     * @param  bool   $use_bet_time
     * @return array
     */
    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time){
    	$sql = <<<EOD
SELECT mglapis.id as sync_index,
mglapis.player_name player_username,
mglapis.game_id,
mglapis.trans_id,
mglapis.trans_type,
mglapis.bet_amount,
mglapis.bet_amount real_bet_amount,
mglapis.result_amount,
mglapis.balance_after_bet after_balance,
mglapis.game_id as gameshortcode,
mglapis.external_uniqueid ,
mglapis.response_result_id,
mglapis.trans_time end_at,
mglapis.trans_time start_at,
mglapis.trans_time bet_at,
mglapis.sync_datetime as updated_at,
mglapis.mgs_action_id as round_number,
mglapis.md5_sum,
gd.id as game_description_id,
gd.game_name as game,
gd.game_code as game_code,
gd.game_type_id,
gp.player_id
FROM mglapis_game_logs as mglapis
LEFT JOIN game_description as gd ON mglapis.game_id = gd.external_game_id and gd.game_platform_id=?
LEFT JOIN game_provider_auth as gp ON mglapis.player_name = gp.login_name and game_provider_id=?
WHERE
mglapis.trans_type != "refund" and 
mglapis.trans_time >= ? AND mglapis.trans_time <= ?
EOD;
		$params=[$this->getPlatformCode(), $this->getPlatformCode(),$dateFrom,$dateTo];
		$this->CI->utils->debug_log('[---------MG LAPIS merge sql---------]: '.$sql);
        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);

        return $result;
    }

    /**
     *
     * perpare original rows, include process unknown game, pack bet details, convert game status
     *
     * @param  array &$row
     */
    public function preprocessOriginalRowForGameLogs(array &$row){
    	$this->CI->load->model(array('game_logs'));
        $game_description_id = $row['game_description_id'];
        $game_type_id = $row['game_type_id'];
        $isRefund = $this->CI->mglapis_game_logs->isRefundIdExist($row['trans_id'],null,null);
        if (empty($game_description_id)) {
            list($game_description_id, $game_type_id) = $this->getGameDescriptionInfo($row, $this->getUnknownGame());
        }
        $row['game_description_id']=$game_description_id;
        $row['game_type_id']=$game_type_id;
        $row['bet_details']= [];
        $row['status'] = $isRefund ? Game_logs::STATUS_REFUND : Game_logs::STATUS_SETTLED;
    }

	/**
     * it will be used on processUnsettleGameLogs and commonUpdateOrInsertGameLogs
     *
     * @param  array $row
     * @return array $params
     */
    public function makeParamsForInsertOrUpdateGameLogsRow(array $row){
    	$extra_info=[
    		'trans_amount'=>$row['bet_amount'], 
    		'table'=>$row['external_uniqueid'], 
    		'bet_type'=>$row['trans_type']
    	];
    	$has_both_side=0;
    	if(empty($row['md5_sum'])){
        	//genereate md5 sum
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE,
            	self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
        }

        return [
        	//set game_type to null unless we know exactly game type name from original game logs
            'game_info'=>['game_type_id'=>$row['game_type_id'], 'game_description_id'=>$row['game_description_id'],
                'game_code'=>$row['game_code'], 'game_type'=>null, 'game'=>$row['game']],
            'player_info'=>['player_id'=>$row['player_id'], 'player_username'=>$row['player_username']],
            'amount_info'=>['bet_amount'=>$row['bet_amount'], 'result_amount'=>$row['result_amount'],
                'bet_for_cashback'=>$row['bet_amount'], 'real_betting_amount'=>$row['real_bet_amount'],
                'win_amount'=>null, 'loss_amount'=>null, 'after_balance'=>$row['after_balance']],
            'date_info'=>['start_at'=>$row['start_at'], 'end_at'=>$row['end_at'], 'bet_at'=>$row['bet_at'],
                'updated_at'=>$row['updated_at']],
            'flag'=>Game_logs::FLAG_GAME,
            'status'=>$row['status'],
            'additional_info'=>['has_both_side'=>$has_both_side, 'external_uniqueid'=>$row['external_uniqueid'], 'round_number'=>$row['round_number'],
                'md5_sum'=>$row['md5_sum'], 'response_result_id'=>$row['response_result_id'], 'sync_index'=>$row['sync_index'],
                'bet_type'=>null ],
            'bet_details'=>$row['bet_details'],
            'extra'=>$extra_info,
            //from exists game logs
            'game_logs_id'=>isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];

    }

    public function syncMergeToGameLogsOld($token) {
    	$dateTimeFrom = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
    	$dateFromRef = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
    	$dateTimeTo = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

		$dateTimeFrom->modify($this->getDatetimeAdjust());

        #adjust synctim
        // $dateTimeFrom = $dateTimeFrom->modify('+8 hours');
        // $dateTimeTo = 	$dateTimeTo->modify('+8 hours');
        // $dateFromRef = $dateFromRef->modify('+8 hours');

    // 	$timeLimit = $dateTimeFrom->diff($dateTimeTo)->i;
	  	// $start = $dateTimeFrom->setTime(8,0,0);

    // 	$startStr = $start->format('Y-m-d H:i:s');
    // 	if($dateFromRef < $start ){
    // 		$startStr = $start->modify('-1 day')->format('Y-m-d H:i:s');
    // 	}

    // 	$endStr = (new \DateTime($startStr))->modify('+1 day')->setTime(07, 59, 59)->format('Y-m-d H:i:s');

    //     $this->CI->utils->debug_log('MG LAPIS DATE INPUT','dateTimeFrom', $dateTimeFrom, 'dateTimeTo', $dateTimeTo);
    //     $this->syncCount++;
    // 	if($this->syncCount > 1 && $timeLimit <= 30){
    // 		return array('success' => true);
    // 	}

		$startStr=$dateTimeFrom->format('Y-m-d H:i:s');
		$endStr=$dateTimeTo->format('Y-m-d H:i:s');

    	$this->CI->load->model(array('game_logs', 'player_model', 'mglapis_game_logs', 'game_description_model'));
		//$result = $this->CI->mglapis_game_logs->getMGLapisGameLogStatistics($dateTimeFrom->format('Y-m-d H:i:s'), $dateTimeTo->format('Y-m-d H:i:s'));
    	$result = $this->CI->mglapis_game_logs->getMGLapisGameLogStatistics($startStr,$endStr);
        //$this->CI->utils->debug_log('[---------MG LAPIS SYNCMERGETOGAMELOGS RESULT---------]', $result);
    	$this->CI->utils->debug_log('[---------MG LAPIS SYNCMERGETOGAMELOGS---------]: ', count($result), $startStr, $endStr);
    	$rlt = array('success' => true);

    	if ($result) {
    		$unknownGame = $this->getUnknownGame();
    		$gameDescIdMap = $this->CI->game_description_model->getGameCodeMap($this->getPlatformCode());

    		foreach ($result as $mgLapisData) {
    			$isExist = $this->CI->mglapis_game_logs->isRefundIdExist($mgLapisData->trans_id,$startStr,$endStr);

    			$player_id = $this->getPlayerIdInGameProviderAuth($mgLapisData->player_name);
    			if (!$player_id) {
    				continue;
    			}

    			$player = $this->CI->player_model->getPlayerById($player_id);

    			$gameDate = new \DateTime($mgLapisData->trans_time);
    			$gameDateStr = $this->CI->utils->formatDateTimeForMysql($gameDate);
				//$bet_amount = $this->gameAmountToDB($mgLapisData->bet_amount);
    			$bet_amount = (float)$mgLapisData->bet_amount;
    			list($game_description_id, $game_type_id) = $this->getGameDescriptionInfo($mgLapisData, $unknownGame, $gameDescIdMap);

    			$result_amount = $mgLapisData->result_amount - $mgLapisData->bet_amount  ;

    			$after_balance= (float)$mgLapisData->balance_after_bet;
				// if only bet means bet loss

				// if($mgLapisData->trans_type == "mgsaspibet"||$mgLapisData->trans_type == "bet"){
				// 	$result_amount = -$mgLapisData->bet_amount;
				// }
				//real bet
    			if ($isExist || $mgLapisData->trans_type=='refund') {
    			 	$extra=['trans_amount'=>$bet_amount, 'table'=>$mgLapisData->external_uniqueid, 'bet_type'=>$mgLapisData->trans_type, 'status'=> Game_logs::STATUS_REFUND];
    			}else {
    				$extra=['trans_amount'=>$bet_amount, 'table'=>$mgLapisData->external_uniqueid, 'bet_type'=>$mgLapisData->trans_type];
    			}

    			$this->syncGameLogs(
    				$game_type_id,
    				$game_description_id,
    				$mgLapisData->game_code,
    				$mgLapisData->game_type,
					$mgLapisData->game, # game_name
					$player_id,
					$player->username,
					$bet_amount,
					$result_amount,
					null, # win_amount
					null, # loss_amount
					$after_balance, # after_balance
					0, # has_both_side
					$mgLapisData->external_uniqueid,
					$gameDateStr,
					$gameDateStr,
					$mgLapisData->response_result_id,
					Game_logs::FLAG_GAME,
					$extra
					);
    		}
    	} else {
    		$rlt = array('success' => false);
    	}

    	return $rlt;
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
		$extra = array('game_code' => $externalGameId);
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
			$row->game, $row->game_type, $externalGameId, $extra,
			$unknownGame);
	}

	public function gameAmountToDB($amount) {
		//only need 2
		return round(floatval($amount), 2);
	}

	public function batchQueryPlayerBalance($playerNames, $syncId = null) {

        if (empty($playerNames)) {
            $playerNames = $this->getAllGameUsernames();
        }

        return $this->batchQueryPlayerBalanceOneByOne($playerNames, $syncId);

    }

	public function processBatchQueryPlayerBalance($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultXmlArr = $this->getResultXmlFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $params, $resultXmlArr, $playerName, true);
		$result = array();
		$wallet = $resultXmlArr->wallets->{'account-wallet'}['credit-balance'];

		if ($success && isset($resultXmlArr->wallets)) {
			$result["balance"] = floatval(@$resultXmlArr->wallets->{'account-wallet'}['credit-balance']);
			$playerId = $this->getPlayerIdInGameProviderAuth($playerName);
			$this->CI->utils->debug_log('query balance playerId', $playerId, 'playerName', $playerName,
				'balance', @$result["balance"]);
			if ($playerId) {
				//should update database
//				$this->updatePlayerSubwalletBalance($playerId, $result["balance"]);
			} else {
				log_message('error', 'cannot get player id from ' . $playerName . ' getPlayerIdInGameProviderAuth');
			}
		} else {
			$success = false;
		}
		return array($success, $result);
	}

	//===end syncGameRecords=====================================================================================

	//===start syncBalance=====================================================================================
	//===end syncBalance=====================================================================================
}

/*end of file*/