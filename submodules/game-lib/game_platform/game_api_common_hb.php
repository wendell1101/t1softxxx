<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
require_once dirname(__FILE__) . '/habanero_game_syncing_utils.php';

/**
 * Defines general behavior of game API classes.
 *
 * General behaviors include:
 * * Getting platform code
 * * Generate URL
 * * Generate Soap Method
 * * Prepares Data below
 * * * Currency for Create Account
 * * * Profile List Id
 * * * Currency for Deposit
 * * * My Balance
 * * Create Player
 * * Login/Logout
 * * Deposit To game
 * * Withdraw from Game
 * * Change Password
 * * Check Player Balance
 * * Check Transaction
 * * Check Game records
 * * Check Forward Game
 * * Synchronize Original Game Logs
 * * Authenticate Soap
 * * Make Soap Options
 * * Check if Player Exist
 * * Check Player Information
 * * Block/Unblock Player
 * * Check Player Daily Balance
 * * Check login Status
 * * Check Total Betting Amount

 * Behaviors not implemented
 * * Check Fund Transfer
 * * Reset Player
 * * Update Player Information
 * * Batch Check Player Balance
 * * Check Player's Available Balance
 *
 * The functions implemented by child class:
 * * Populating game form parameters
 * * Handling callbacks
 *
 *
 *
 *
 * @see Redirect redirect to game page
 *
 * @category Game API
 * @version 1.8.10
 * @copyright 2013-2022 tot
 */
class Game_api_common_hb extends Abstract_game_api
{
	use habanero_game_syncing_utils;

	private $api_domain;
	private $brandId;
	private $APIKey;
	private $PlayerHostAddress;
	private $CurrencyCode;
	private $userAgent;
	protected $currentApi = null;

	const API_getJackpots = 'GetJackpots';
	const ERROR_CODE_TO0_MANY_REQUEST = 429;

    const ReportPlayerStakePayout = 'ReportPlayerStakePayout';

	public function __construct() {
		parent::__construct();
		$this->PlayerHostAddress = ''; //$_SERVER['REMOTE_ADDR'];
		$this->userAgent = ''; //$_SERVER['HTTP_USER_AGENT'];
		$this->api_domain = $this->getSystemInfo('url');
		$this->APIKey = $this->getSystemInfo('live_secret');
		$this->brandId = $this->getSystemInfo('live_key');
        $this->CurrencyCode = $this->getSystemInfo('currency', "CNY");
        $this->lobby_url = $this->getSystemInfo('lobby_url');
		$this->use_local_wsdl = $this->getSystemInfo('use_local_wsdl',false);
		$this->allow_fullscreen = $this->getSystemInfo('allow_fullscreen', true);

        //don't support
        $this->is_enabled_direct_launcher_url=$this->getSystemInfo('is_enabled_direct_launcher_url', false);

		$this->json_api_url=$this->getSystemInfo('json_api_url', 'https://ws-a.insvr.com/jsonapi');
		$this->check_player_info = $this->getSystemInfo('check_player_info', true);
		$this->sync_sleep = $this->getSystemInfo('sync_sleep', 30);
		$this->enable_freespin_in_merging = $this->getSystemInfo('enable_freespin_in_merging',true);

		$this->lobby_key = $this->getSystemInfo('lobby_key', '');
		$this->max_retry_sync_original = $this->getSystemInfo('max_retry_sync_original', 3);
		$this->allow_sync_BrandCCWinners = $this->getSystemInfo('allow_sync_BrandCCWinners', true);


		$this->history_url = $this->getSystemInfo('history_url', 'http://app-test.insvr.com/games/history');
		$this->history_view_type = $this->getSystemInfo('history_view_type', 'game');
		$this->unique_game_id_used = $this->getSystemInfo('unique_game_id_used', 'FriendlyId');
		
	}

	/**
	 * overview : get platform code
	 * @return int
	 */
	public function getPlatformCode() {
		return $this->returnUnimplemented();
	}

	const JSON_API=[
		self::API_syncIncompleteGames,
		self::API_revertBrokenGame,
		self::API_queryIncompleteGames,
		self::ReportPlayerStakePayout,
		self::API_changePassword,
		self::API_syncGameRecords,
		'GetBrandCCWinners',
	];

	const URI_MAP = [
		self::API_syncIncompleteGames=>'GetBrandIncompleteGames',
		self::API_revertBrokenGame=>'ExpirePlayerGames',
		self::API_queryIncompleteGames=>'GetPlayerResumeGames',
		self::ReportPlayerStakePayout => 'ReportPlayerStakePayout',
		self::API_changePassword => 'UpdatePlayerPassword',
		self::API_syncGameRecords => 'GetBrandCompletedGameResultsV2',
		'GetBrandCCWinners' => 'GetBrandCCWinners',
	];

	/**
	 * overview : get call type
	 *
	 * @param $apiName
	 * @param $params
	 * @return int
	 */
	protected function getCallType($apiName, $params) {
		if(in_array($apiName, self::JSON_API)){
			return self::CALL_TYPE_HTTP;
		}
		//overwrite in sub-class
		return self::CALL_TYPE_SOAP;
	}

	/**
	 * overview : generate url
	 *
	 * @param $apiName
	 * @param $params
	 * @return string
	 */
	public function generateUrl($apiName, $params) {
		if(in_array($apiName, self::JSON_API)){
			$methodName=self::URI_MAP[$apiName];
			return rtrim($this->json_api_url,'/').'/'.$methodName;
		}

		if($this->use_local_wsdl){
			return realpath(dirname(__FILE__)).'/wsdl/38/'.$this->getSystemInfo('wsdl_filename', 'hb_live_server.wsdl');
		}else{
			return $this->getSystemInfo('url');
		}
	}

    protected function getHttpHeaders($params) {
    	return ['Content-Type'=>'application/json'];
    }

	public function customHttpCall($ch, $params){
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
	}

	protected function isErrorCode($apiName, $params, $statusCode, $errCode, $error) {
        $statusCode = intval($statusCode, 10);
        return $errCode || intval($statusCode, 10) >= 501;
    }

	/**
	 * overview : generate soap method
	 *
	 * @param $apiName
	 * @param $params
	 * @return array
	 */
	protected function generateSoapMethod($apiName, $params) {
		switch ($apiName) {
		case self::API_getJackpots:
			return array('GetJackpots', $params);
			break;
		case self::API_createPlayer:
			return array('LoginOrCreatePlayer', $params);
			break;

		case self::API_queryPlayerInfo:
		case self::API_isPlayerExist:
		case self::API_queryPlayerCurrency:
			return array('QueryPlayer', $params);
			break;

		case self::API_depositToGame:
			return array('DepositPlayerMoney', $params);
			break;

		case self::API_withdrawFromGame:
			return array('WithdrawPlayerMoney', $params);
			break;

		case self::API_queryPlayerBalance:
			return array('QueryPlayer', $params);
			break;

		case self::API_logout:
			return array('LogOutPlayer', $params);
			break;

		case self::API_queryGameRecords:
			return array('GetPlayerGameTransactions', $params);
			break;

		case self::API_queryTransaction:
			return array('QueryTransfer', $params);
			break;

		}
		return parent::generateSoapMethod($apiName, $params);
	}

	/**
	 * overview : result after process
	 *
	 * @param $apiName
	 * @param $params
	 * @param $responseResultId
	 * @param $resultText
	 * @param $statusCode
	 * @param null $statusText
	 * @param null $extra
	 * @param null $resultObj
	 * @return array
	 */
	public function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null) {
		return array(false, null);

	}

	/**
	 * overview : get prepare data
	 *
	 * @return array
	 */
	public function getPrepareData() {
		$currencyList = $this->getCurrencyForCreateAccount();
		$profileList = $this->getProfileListId();
		$currencyForDepositList = $this->getCurrencyForDeposit();
		$myBalanceList = $this->getMyBalance();
		return array('currencyList' => $currencyList, 'profileList' => $profileList,
			'currencyForDepositList' => $currencyForDepositList,
			'myBalanceList' => $myBalanceList);
	}

	/**
	 * overview : get currency for creating account
	 *
	 * @return array
	 */
	private function getCurrencyForCreateAccount() {
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForGetCurrencyForCreateAccount',
		);

		return $this->callApi(self::API_getCurrencyForCreateAccount, array(), $context);
	}

	/**
	 * overview : process result for getCurrencyForCreateAccount
	 *
	 * @param $params
	 * @return array
	 */
	public function processResultForGetCurrencyForCreateAccount($params) {
		$responseResultId = $params['responseResultId'];
		$resultObj = $params['resultObj'];

		$success = $this->processResultBoolean($responseResultId, $resultObj->GetCurrenciesForAddAccountResult);
		$this->CI->utils->debug_log('processResultForGetCurrencyForCreateAccount', $resultObj);
		$result = array();
		if ($success) {
			$result['currencyList'] = (array) $resultObj->GetCurrenciesForAddAccountResult;
		}

		return array($success, $result);
	}

	/**
	 * overview : get profile list
	 * @return array
	 */
	private function getProfileListId() {
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForGetBettingProfileList',
		);

		return $this->callApi(self::API_getBettingProfileList, array(), $context);
	}

	/**
	 * overview : processing result for get betting profile list
	 *
	 * @param $params
	 * @return array
	 */
	public function processResultForGetBettingProfileList($params) {
		$responseResultId = $params['responseResultId'];
		$resultObj = $params['resultObj'];
		// $playerId = $params['context']['playerId'];

		$success = $this->processResultBoolean($responseResultId, $resultObj->GetBettingProfileListResult);
		$this->CI->utils->debug_log($resultObj);

		$result = array();
		if ($success) {
			foreach ($resultObj->GetBettingProfileListResult as $rlt) {
				$result["profileList"][] = $rlt->Id;
				break;
			}
		}

		return array($success, $result);
	}

	/**
	 * overview : get currency for deposit
	 *
	 * @return array
	 */
	private function getCurrencyForDeposit() {
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForGetCurrencyForDeposit',
		);

		return $this->callApi(self::API_getCurrencyForDeposit, array(), $context);
	}

	/**
	 * overview : process result for getCurrencyForDeposit
	 *
	 * @param $params
	 * @return array
	 */
	public function processResultForGetCurrencyForDeposit($params) {
		$responseResultId = $params['responseResultId'];
		$resultObj = $params['resultObj'];

		$success = $this->processResultBoolean($responseResultId, $resultObj->GetCurrenciesForDepositResult);
		$result = array();
		if ($success) {
			$result['currencyList'] = (array) $resultObj->GetCurrenciesForDepositResult;
		}

		return array($success, $result);
	}

	/**
	 * overview : get my balance
	 *
	 * @return array
	 */
	private function getMyBalance() {
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForGetMyBalance',
		);

		return $this->callApi(self::API_getMyBalance, array(), $context);

	}

	/**
	 * overview : process result for getMyBalance
	 *
	 * @param $params
	 * @return array
	 */
	public function processResultForGetMyBalance($params) {
		$responseResultId = $params['responseResultId'];
		$resultObj = $params['resultObj'];

		$success = $this->processResultBoolean($responseResultId, $resultObj->GetMyBalanceResult);
		//$this->CI->utils->debug_log($resultObj);
		$result = array();
		if ($success) {
			foreach ($resultObj->GetMyBalanceResult->MemberBalances as $rlt) {
				$result["myBalanceList"][] = (array) $rlt;
				break;
			}
		}

		return array($success, $result);
	}


	public function getJackpots() {
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForGetJackpots',
		);

		$params = array(
			"BrandId" => $this->brandId,
			"APIKey" => $this->APIKey
		);
		$params = array('req' => $params);

		return $this->callApi(self::API_getJackpots, $params, $context);
	}

	public function processResultForGetJackpots($params){
		$responseResultId = $this->getResponseResultIdFromParams((array) $params);
		$resultObj = json_decode(json_encode($params['resultObj']),true);
		$jackpots = isset($resultObj['GetJackpotsResult']['JackpotInfoDTO'])?$resultObj['GetJackpotsResult']['JackpotInfoDTO']:array();
		$success = isset($resultObj['GetJackpotsResult']['JackpotInfoDTO'])?true:false;
		return array($success, array('jackpots'=>$jackpots,'response_result_id'=>$responseResultId));
	}

	/**
	 * overview : create player game
	 *
	 * @param $playerName
	 * @param $playerId
	 * @param $password
	 * @param null $email
	 * @param null $extra
	 * @return array
	 */
	public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
		parent::createPlayer($playerName, $playerId, $password, $email, $extra);
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		if(empty($gameUsername)){
			return ['success'=>false];
		}

		if($this->check_player_info){
			$isPlayerExist = $this->isPlayerExist($playerName);
			$this->CI->utils->debug_log('createPlayer isPlayerExist returned null');
			if($isPlayerExist['success'] && $isPlayerExist['exists']){
				return array('success'=>true, 'exists'=>true);
			}
		}

		$segmentKey = '';
		if(isset($extra['external_category']) && !empty($extra['external_category'])){
			$segmentKey = trim($extra['external_category']);
		}

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForCreatePlayer',
			'playerId' => $playerId,
			'playerName' => $playerName,
			'gameUsername' => $gameUsername,
			'segmentKey' => $segmentKey,
		);

		$params = array(
			"req" => array(
				"Username" => $gameUsername,
				"BrandId" => $this->brandId,
				"PlayerHostAddress" => $this->PlayerHostAddress,
				'UserAgent' => $this->userAgent,
				'KeepExistingToken' => true,
				"APIKey" => $this->APIKey,
				"Password" => $password,
				"CurrencyCode" => $this->CurrencyCode,
				"SegmentKey" => $segmentKey
			)
		);

		return $this->callApi(self::API_createPlayer, $params, $context);
	}

	/**
	 * overview process result for create player
	 *
	 * @param $params
	 * @return array
	 */
	public function processResultForCreatePlayer($params) {
		$responseResultId = $this->getResponseResultIdFromParams((array) $params);
		$resultObj = $params['resultObj']->LoginOrCreatePlayerResult;
		// $resultJsonArr = $this->getResultJsonFromParams($params);
		// $resultJsonArr = $this->getResultJsonFromParams($params);
		$playerId = $this->getVariableFromContext((array) $params, 'playerId');
		$playerName = $this->getVariableFromContext((array) $params, 'playerName');
		$gameUsername = $this->getVariableFromContext((array) $params, 'gameUsername');
		$segmentKey = $this->getVariableFromContext((array) $params, 'segmentKey');
		$success = $this->processResultBoolean($responseResultId, $resultObj, $playerName);
		$this->CI->utils->debug_log('processResultForCreatePlayer', $resultObj);

		$result = [
			'player' => $gameUsername,
			'exists' => false,
		];

		if($success){
			// OGP-19787 PlayerCreated=false player already exists already
			if(isset($resultObj->PlayerCreated) && $resultObj->PlayerCreated==false){
				$success=true;
			}
		}else{
			$result['error']=isset($resultObj->Message)?$resultObj->Message:'';
		}

		if($success){
			//$this->updateExternalAccountIdForPlayer($playerId, $resultObj->PlayerId);
			if(!empty($segmentKey)){
				$successUpdate = $this->updateExternalCategoryForPlayer($playerId, $segmentKey);
				if(!$successUpdate){
					$this->CI->utils->error_log('HB Error update external category.', $playerId, $segmentKey);
				}
			}
		}

		//update register
		if ($success) {
			$this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
			$result['exists'] = true;
		}

		return array($success, $result);
	}

	/**
	 * @param $playerName
	 * @param null $password
	 * @return array
	 */
	public function login($playerName, $password = null) {
		$password = $this->getPassword($playerName);
		$playerId = $this->getPlayerIdInPlayer($playerName);
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$playerInfo = $this->queryPlayerInfo($playerName);
		$this->CurrencyCode = isset($playerInfo[0]['CurrencyCode']) ? $playerInfo[0]['CurrencyCode'] : $this->CurrencyCode;

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForLogin',
			'playerName' => $playerName,
			'playerId'=>$playerId,
			'gameUsername' => $gameUsername,
		);

		$params = array(
			"req" => array(
				"Username" => $gameUsername,
				"BrandId" => $this->brandId,
				"PlayerHostAddress" => $this->PlayerHostAddress,
				"APIKey" => $this->APIKey,
				"Password" => $password['password'],
				"CurrencyCode" => $this->CurrencyCode,
			)
		);

		return $this->callApi(self::API_createPlayer, $params, $context);
	}

	/**
	 * overview : process result for login
	 *
	 * @param $params
	 * @return array
	 */
	public function processResultForLogin($params) {
		$responseResultId = $this->getResponseResultIdFromParams((array) $params);
		$resultObj = $params['resultObj']->LoginOrCreatePlayerResult;
		//$playerId = $this->getVariableFromContext((array) $params, 'playerId');
		$playerName = $this->getVariableFromContext((array) $params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $resultObj, $playerName);
		$this->CI->utils->debug_log('processResultForCreatePlayer', $resultObj);
		//$this->updateExternalAccountIdForPlayer($playerId, $resultObj->PlayerId);
		return array($success, array('token' => $resultObj->Token));
	}

	/**
	 * overview : deposit to game
	 *
	 * @param $playerName
	 * @param $amount
	 * @param null $transfer_secure_id
	 * @return array
	 */
	public function depositToGame($playerName, $amount, $transfer_secure_id=null) {
		$password = $this->getPassword($playerName);
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$transactionReferenceNumber = 'DEP' . random_string('alnum');
		if(!empty($transfer_secure_id)){
			$transactionReferenceNumber = $this->prefix_for_transaction_id . $transfer_secure_id;
		}

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForDepositToGame',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername,
			'amount' => $amount,
			'external_transaction_id' => $transactionReferenceNumber,
            'isRetry'=>false,
		);
		$params = array(
			'req' => array(
				'BrandId' => $this->brandId,
				'APIKey' => $this->APIKey,
				'Username' => $gameUsername,
				'Password' => $password['password'],
				'CurrencyCode' => $this->CurrencyCode,
				'Amount' => $this->dBtoGameAmount($amount),
				'RequestId' => $transactionReferenceNumber,
			)
		);


		return $this->callApi(self::API_depositToGame, $params, $context);
	}

	/**
	 * overview : process result for deposit to game
	 *
	 * @param $params
	 * @return array
	 */
	public function processResultForDepositToGame($params) {
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$responseResultId = $this->getResponseResultIdFromParams((array) $params);
		$resultObj = $this->getResultObjFromParams((array) $params);
		$gameUsername = $this->getVariableFromContext((array) $params, 'gameUsername');
		$amount = $this->getVariableFromContext((array) $params, 'amount');
		$rlt = $this->getFirstResultFromObject($resultObj);
		$rltSuccess = (is_object($rlt) && property_exists($rlt,'Success')) ? $rlt->Success : false;
		$success = $this->processResultBoolean($responseResultId, $rltSuccess, $gameUsername);
		$isRetry = $this->getVariableFromContext($params, 'isRetry');

		if(!$isRetry){
			if(strpos(@$rlt->Message,'NO change to balance') !== false||strpos(@$rlt->Message,'repeated deposit requestid') !== false){
				$success = false;
			}
		}

        $result = array(
            'response_result_id' => $responseResultId,
            'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id'=>self::REASON_UNKNOWN,
            'external_transaction_id'=>$external_transaction_id
        );

		if ($success) {
			$afterBalance = $this->gameAmountToDB(floatval($rlt->RealBalance));
			$result["currentplayerbalance"] = $rlt->RealBalance;
			$result["balance"] = $afterBalance;
			$result['didnot_insert_game_logs']=true;
			$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
			$result["userNotFound"] = false;
		} else {
			$error_msg = $this->getResultTextFromParams($params);
			$result['reason_id'] = $this->getReasons($error_msg);
			$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
		}

		return array($success, $result);
	}

	private function getReasons($error_msg){
        $error_msg = strtolower($error_msg);
        switch ($error_msg) {
            case strpos($error_msg,'username not provided') !== false:
                return self::REASON_NOT_FOUND_PLAYER;
                break;
            case strpos($error_msg,'parameter error') !== false:
                return self::REASON_INCOMPLETE_INFORMATION;
                break;
            case strpos($error_msg,'unrecognized guid format') !== false:
                return self::REASON_GAME_PROVIDER_ACCOUNT_PROBLEM;
                break;
            case strpos($error_msg,'repeated withdrawal requestid') !== false:
            case strpos($error_msg,'repeated deposit requestid') !== false:
                return self::REASON_DUPLICATE_TRANSFER;
                break;
            case strpos($error_msg,'insufficient funds') !== false:
                return self::REASON_NO_ENOUGH_BALANCE;
                break;
            case strpos($error_msg,'maintenance') !== false:
                return self::REASON_API_MAINTAINING;
                break;
            default:
                return self::REASON_UNKNOWN;
                break;
        }
	}

	/**
	 * overview : withdraw from game
	 *
	 * @param $playerName
	 * @param $amount
	 * @param null $transfer_secure_id
	 * @return array
	 */
	public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null) {
		$playerUsername = $playerName;
		$password = $this->getPassword($playerName);
		$transactionReferenceNumber = 'WIT' . random_string('alnum');
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		// $resultPlayerBalance = $this->queryPlayerBalance($playerUsername);
		if(!empty($transfer_secure_id)){
			$transactionReferenceNumber = $this->prefix_for_transaction_id . $transfer_secure_id;
		}

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForWithdrawFromGame',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername,
			'amount' => $amount,
			'external_transaction_id' => $transactionReferenceNumber,
            'isRetry'=>false,
		);

		$params = array(
			'req' => array(
				'BrandId' => $this->brandId,
				'APIKey' => $this->APIKey,
				'Username' => $gameUsername,
				'Password' => $password['password'],
				'CurrencyCode' => $this->CurrencyCode,
				'Amount' => '-' . $this->dBtoGameAmount($amount),
				'WithdrawAll' => '',
				'RequestId' => $transactionReferenceNumber
			)
		);

		return $this->callApi(self::API_withdrawFromGame, $params, $context);
	}

	/**
	 * overview : processing for withdraw from game
	 * @param $params
	 * @return array
	 */
	public function processResultForWithdrawFromGame($params) {
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$responseResultId = $this->getResponseResultIdFromParams((array) $params);
		$resultObj = $this->getResultObjFromParams((array) $params);
		$playerName = $this->getVariableFromContext((array) $params, 'playerName');
		$gameUsername = $this->getVariableFromContext((array) $params, 'gameUsername');
		$amount = $this->getVariableFromContext((array) $params, 'amount');
		$rlt = $this->getFirstResultFromObject($resultObj);
		$rltSuccess = (is_object($rlt) && property_exists($rlt,'Success')) ? $rlt->Success : false;
		$success = $this->processResultBoolean($responseResultId, $rltSuccess, $gameUsername);
		$isRetry = $this->getVariableFromContext($params, 'isRetry');

		if(!$isRetry){
			if(strpos(@$rlt->Message,'NO change to balance') !== false || strpos(@$rlt->Message,'repeated withdrawal requestid') !== false || strpos(@$rlt->Message,'Insufficient funds')!== false){
				$success = false;
			}
		}

        $result = array(
            'response_result_id' => $responseResultId,
            'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id'=>self::REASON_UNKNOWN,
			'external_transaction_id'=>$external_transaction_id
        );

		if ($success) {
			$afterBalance = $this->gameAmountToDB(floatval($rlt->RealBalance));
			$result["currentplayerbalance"] = $afterBalance; //The account’s balance in the player’s currency
			$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
			$result['didnot_insert_game_logs']=true;
			$result["userNotFound"] = false;
		} else {
			$error_msg = $this->getResultTextFromParams($params);
			$result['reason_id'] = $this->getReasons($error_msg);
			$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
		}

		return array($success, $result);
	}

	/**
	 * overview : change player password
	 *
	 * @param $playerName
	 * @param $oldPassword
	 * @param $newPassword
	 * @return array
	 */
	public function changePassword($playerName, $oldPassword, $newPassword) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		if (!empty($gameUsername)) {
			//EditAccount
			$context = array(
				'callback_obj' => $this,
				'callback_method' => 'processResultForChangePassword',
				'playerName' => $playerName,
				'gameUsername' => $gameUsername,
				'newPassword' => $newPassword,
			);

			$params = array(
				'BrandId' => $this->brandId,
				'APIKey' => $this->APIKey,
				'Username' => $gameUsername,
				'NewPassword' => $newPassword,
			);

			return $this->callApi(self::API_changePassword, $params, $context);
		}
		return $this->returnFailed('Not found ' . $gameUsername);
	}

	/**
	 * overview : process result for change password
	 *
	 * @param $params
	 * @return array
	 */
	public function processResultForChangePassword($params) {
        $responseResultId = $this->getResponseResultIdFromParams((array) $params);
        $resultJson = $this->getResultJsonFromParams($params);
        $playerName = $this->getVariableFromContext((array) $params, 'playerName');
        $newPassword = $this->getVariableFromContext((array) $params, 'newPassword');
        $success = false;
        $resultJsonSuccess = isset($resultJson['Success']) ? $resultJson['Success'] : false;

        $result = [
            'player' => $playerName,
            'newPassword' => $newPassword,
            'message' => "Update password Failed!"
        ];

        $success = $this->processResultBoolean($responseResultId, $resultJsonSuccess, $playerName);

        $this->CI->utils->debug_log(__METHOD__.' processResultForChangePassword', $resultJson , 'success', $success);

        if ($success) {
           $playerId = $this->getPlayerIdInPlayer($playerName);
           //sync password to game_provider_auth
		   $this->updatePasswordForPlayer($playerId, $newPassword);
		   $result['message'] = "Update password success!";
        }

        return array($success, $result);
	}

	/**
	 * overview : query player balance
	 *
	 * @param $playerName
	 * @return array
	 */
	public function queryPlayerBalance($playerName) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$password = $this->getPassword($playerName);
		//$playerId = $this->getPlayerIdByExternalAccountId($gameUsername);
		$playerId = $this->CI->player_model->getPlayerIdByUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryPlayerBalance',
			'playerId' => $playerId,
			'gameUsername' => $gameUsername,
		);

		$params = array(
			'req' => array(
				'BrandId' => $this->brandId,
				'APIKey' => $this->APIKey,
				"Username" => $gameUsername,
				"Password" => $password['password']
			)
		);

		return $this->callApi(self::API_queryPlayerBalance, $params, $context);
	}

	/**
	 * overview : processing result for query player balance
	 *
	 * @param $params
	 * @return array
	 */
	public function processResultForQueryPlayerBalance($params) {
		$responseResultId = $this->getResponseResultIdFromParams((array) $params);
		$resultObj = $this->getResultObjFromParams((array) $params);
		$gameUsername = $this->getVariableFromContext((array) $params, 'gameUsername');

		if ($resultObj == null) {
			$this->CI->utils->debug_log('processResultForQueryPlayerBalance returned null');
			return array(false, null);
		}

		$balance = $this->gameAmountToDB($resultObj->QueryPlayerResult->RealBalance);
		$status = $resultObj->QueryPlayerResult->Found ? 1 : 0;
		$success = $this->processResultBoolean($responseResultId, $resultObj, $gameUsername);
		$rltObj = (array) $resultObj->QueryPlayerResult;
		$result = [];

		if ($success) {
			$result["balance"] = $this->gameAmountToDB(floatval($rltObj['RealBalance']));
		}

		return array($success, $result);
	}

	/**
	 * overview : query transaction
	 *
	 * @param $transactionId
	 * @param $extra
	 * @return array
	 */
	public function queryTransaction($transactionId, $extra) {
        $playerName=$extra['playerName'];
        $playerId=$extra['playerId'];
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryTransaction',
			'playerId' => $playerId,
			'playerName' => $playerName,
			'gameUsername' => $gameUsername,
			'external_transaction_id' => $transactionId,
		);

		$params = array(
			'req' => array(
				'BrandId' => $this->brandId,
				'APIKey' => $this->APIKey,
				"RequestId" => $transactionId
			)
		);

		return $this->callApi(self::API_queryTransaction, $params, $context);
	}

	/**
	 * overview : processing result for query transaction
	 *
	 * @param $params
	 * @return array
	 */
	public function processResultForQueryTransaction($params) {
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$responseResultId = $this->getResponseResultIdFromParams((array) $params);
		$resultObj = $this->getResultObjFromParams((array) $params);
		$gameUsername = $this->getVariableFromContext((array) $params, 'gameUsername');
		$rlt = $this->getFirstResultFromObject($resultObj);
		$this->currentApi = self::API_queryTransaction;

		$this->CI->utils->debug_log('result obj ',$resultObj);
		$this->CI->utils->debug_log('result hb ',$rlt);

		$this->CI->utils->error_log('result obj ',$resultObj);
		$this->CI->utils->error_log('result hb ',$rlt);

		$success = $this->processResultBoolean($responseResultId, $rlt->Success, $gameUsername);

		$result = array(
			'response_result_id' => $responseResultId,
			'status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id'=>self::REASON_UNKNOWN,
			'external_transaction_id'=>$external_transaction_id
		);

		if($success) {
			$result['status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
			if (isset($rlt->Success) && $rlt->Success==false) {
				$result['reason_id'] = self::REASON_TRANSACTION_NOT_FOUND;
				$result['status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
				$success = true;
			}
		} else {
			$error_msg = $this->getResultTextFromParams($params);
			$result['reason_id'] = $this->getReasons($error_msg);
			$result['status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
		}

		return array($success, $result);
	}

	/**
	 * overview : logout player
	 *
	 * @param $playerName
	 * @param null $password
	 * @return array
	 */
	public function logout($playerName, $password = null) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $password = $this->getPassword($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForLogout',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername
		);

		$params = array(
			'req' => array(
				'BrandId' => $this->brandId,
				'APIKey' => $this->APIKey,
				'Username' => $gameUsername,
				'Password' => $password['password']
			)
		);

		return $this->callApi(self::API_logout, $params, $context);
	}

	/**
	 * overview : process result for logout
	 *
	 * @param $params
	 * @return array
	 */
	public function processResultForLogout($params) {
		$responseResultId = $this->getResponseResultIdFromParams((array) $params);
		$resultObj = $this->getResultObjFromParams($params);

		if ($resultObj == null) {
			$this->CI->utils->debug_log('processResultForQueryTransaction returned null');
			return array(false, null);
		}

		$result = $return = (array) $resultObj->LogoutPlayerResult;
		$success = true;
		if ($result['Message'] == "Player was not logged in") {
			$success = false;
		}

		return array($success, $result);
	}

	/**
	 * overview : query game records
	 *
	 * @param $dateFrom
	 * @param $dateTo
	 * @param null $playerName
	 * @return array
	 */
	public function queryGameRecords($dateFrom, $dateTo, $playerName = null) {

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processQueryGameRecords',
		//	'playerId' => $playerId,
		//	'accountNumber' => $accountNumber,
		);
		$params = array(
			'BrandId' => $this->brandId,
			'APIKey' => $this->APIKey,
			'Username' => $playerName,
			'DtStartUTC' => $dateFrom,
			'DtEndUTC' => $dateTo,
		);
		$params = array('req' => $params);

		$result = $this->callApi(self::API_queryGameRecords, $params, $context);
		return $result;
	}

	/**
	 * overview : process query game records
	 *
	 * @param $params
	 * @return array
	 */
	public function processQueryGameRecords($params) {

		$responseResultId = $this->getResponseResultIdFromParams((array) $params);

		$resultObj = $this->getResultObjFromParams($params);
		$accountNumber = $params['params']['req']['Username'];
		if (count((array) $resultObj->GetPlayerGameTransactionsResult) == 0) {
			$this->CI->utils->debug_log('processResultForQueryGameRecords returned null');
			return array(false, array());
		}

		$result = $return = $resultObj->GetPlayerGameTransactionsResult->PlayerGameTransactionsDTO;
		$success = count($result) > 0 ? TRUE : FALSE;
		return array($success, (array) $result);
	}


	public function getLauncherLanguage($language){
		$lang='';
        switch ($language) {
            case 1:
            case 'en-us':
                $lang = 'en'; // english
                break;
            case 2:
            case 'zh-cn':
                $lang = 'zh-CN'; // chinese
                break;
            case 3:
            case 'id-id':
                $lang = 'id'; // chinese
                break;
            case 4:
            case 'vi-vn':
                $lang = 'vi'; // chinese
                break;
            case 5:
            case 'ko-kr':
                $lang = 'ko'; // korean
                break;
            case 'th-th':
                $lang = 'th'; // korean
                break;
            default:
                $lang = 'en'; // default as english
                break;
        }
        return $lang;
	}

	/**
	 * overview : query forward game
	 *
	 * @param $playerName
	 * @param null $extra
	 * @return mixed
	 */
	public function queryForwardGame($playerName, $extra = null) {
		$mode = strtolower($extra['game_mode'])!='real'?'fun':'real';

		$keyname = @$extra['game_code'];
		if(!$keyname || $keyname=='_null'){
			$keyname = $this->lobby_key;
		}

		$newParams = array(
			'mode' => $mode,
			'keyname' => $keyname,
			'locale' => $this->getLauncherLanguage(@$extra['language']),
			'brandid' => $this->brandId,
			'mobile' => $extra['is_mobile'],
			'lobbyurl' => @$extra['lobby_url'],
		);

		if(isset($extra['external_category']) && !empty($extra['external_category'])){
			if($this->isSeamLessGame()){
				$newParams['segmentkey']=$extra['external_category'];
			}
			$player_id = $this->getPlayerIdInPlayer($playerName);
			$successUpdate = $this->updateExternalCategoryForPlayer($player_id, $extra['external_category']);
			if(!$successUpdate){
				$this->CI->utils->error_log('HABANERO SEAMLESS: Error update external category.', $player_id, $extra['external_category']);
			}
		}

		if(isset($extra['home_link']) && !empty($extra['home_link'])){
			$newParams['lobbyurl']=$extra['home_link'];
		}

		if(strtolower(@$extra['redirection']) == 'iframe'){
			$newParams['ifrm']=1;
		}
		$success=true;

		if($mode=='real'){
			$playerReturn = $this->login($playerName);
			if(isset($playerReturn['token'])){
				$newParams['token'] = $playerReturn['token'];
			}
			if(empty($newParams['token'])){
				$success=false;
			}
		}

		$result['success'] = $success;
		$result['url'] = $this->getSystemInfo('second_url'). '?' . http_build_query($newParams);

		return $result;
	}

	/**
	 * overview : sync original game logs
	 *
	 * @param $token
	 * @return array
	 */
	// public function syncOriginalGameLogs($token) {
	// 	$this->CI->utils->debug_log('HB sync original dateTimeFrom', parent::getValueFromSyncInfo($token, 'dateTimeFrom'),'dateTimeTo', parent::getValueFromSyncInfo($token, 'dateTimeTo'));

	// 	$dateTimeFrom = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
	// 	$dateTimeTo = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
	// 	$syncId = parent::getValueFromSyncInfo($token, 'syncId');

	// 	$startDate = new DateTime($this->serverTimeToGameTime($dateTimeFrom));
	// 	$endDate = new DateTime($this->serverTimeToGameTime($dateTimeTo));

	// 	$startDate->modify($this->getDatetimeAdjust());

	// 	$startDate = $startDate->format("YmdHis");
	// 	$endDate = $endDate->format("YmdHis");

	// 	$context = array(
	// 		'callback_obj' => $this,
	// 		'callback_method' => 'processResultForSyncOriginalGameLogs',
	// 		'syncId' => $syncId,
	// 	);

	// 	$this->CI->utils->debug_log('real dateTimeFrom', $startDate, 'dateTimeTo', $endDate);

	// 	$params = array(
	// 		'req' => array(
	// 			'BrandId' => $this->brandId,
	// 			'APIKey' => $this->APIKey,
	// 			'DtStartUTC' => $startDate,
	// 			'DtEndUTC' => $endDate
	// 		)
	// 	);

	// 	return $this->callApi(self::API_syncGameRecords, $params, $context);
	// }

	// private function rebuildGameRecords(&$gameRecords,$extra){
	// 	$new_gameRecords =[];

    //     foreach($gameRecords as $index => $record) {
	// 		$new_gameRecords[$index]['playerid'] = $gameRecords[$index]['PlayerId'];
	// 		$new_gameRecords[$index]['brandid'] = $gameRecords[$index]['BrandId'];
	// 		$new_gameRecords[$index]['username'] = $gameRecords[$index]['Username'];
	// 		$new_gameRecords[$index]['brandgameid'] = $gameRecords[$index]['BrandGameId'];
	// 		$new_gameRecords[$index]['gamekeyname'] = $gameRecords[$index]['GameKeyName'];
	// 		$new_gameRecords[$index]['gametypeid'] = $gameRecords[$index]['GameTypeId'];
    //         $new_gameRecords[$index]['dtstarted'] = $this->gameTimeToServerTime($gameRecords[$index]['DtStarted']);
    //         $new_gameRecords[$index]['dtcompleted'] = $this->gameTimeToServerTime($gameRecords[$index]['DtCompleted']);
	// 		$new_gameRecords[$index]['friendlygameinstanceid'] = $gameRecords[$index]['FriendlyGameInstanceId'];
	// 		$new_gameRecords[$index]['gameinstanceid'] = $gameRecords[$index]['GameInstanceId'];
	// 		$new_gameRecords[$index]['stake'] = $gameRecords[$index]['Stake'];
	// 		$new_gameRecords[$index]['payout'] = $gameRecords[$index]['Payout'];
	// 		$new_gameRecords[$index]['jackpotwin'] = $gameRecords[$index]['JackpotWin'];
	// 		$new_gameRecords[$index]['jackpotcontribution'] = $gameRecords[$index]['JackpotContribution'];
	// 		$new_gameRecords[$index]['currencycode'] = $gameRecords[$index]['CurrencyCode'];
	// 		$new_gameRecords[$index]['channeltypeid'] = $gameRecords[$index]['ChannelTypeId'];
	// 		$new_gameRecords[$index]['balanceafter'] = $gameRecords[$index]['BalanceAfter'];
    //         $new_gameRecords[$index]['bonustoreal'] = isset($gameRecords[$index]['BonusToReal']) && !empty($gameRecords[$index]['BonusToReal']) ? $gameRecords[$index]['BonusToReal']:null;
    //         $new_gameRecords[$index]['bonustorealcoupon'] = isset($gameRecords[$index]['BonusToRealCoupon']) && !empty($gameRecords[$index]['BonusToRealCoupon']) ? $gameRecords[$index]['BonusToRealCoupon']:null;
    //         $new_gameRecords[$index]['external_uniqueid'] = $gameRecords[$index]['FriendlyGameInstanceId'];
    //         $new_gameRecords[$index]['response_result_id'] = $extra['response_result_id'];
    //     }
    //     $gameRecords = $new_gameRecords;
	// }

	// public function processResultForSyncOriginalGameLogs($params) {
    //     $this->CI->load->model('original_game_logs_model');
	// 	$startDate = $this->getVariableFromContext($params, 'startDate');
	// 	$endDate = $this->getVariableFromContext($params, 'endDate');
	// 	$responseResultId = $this->getResponseResultIdFromParams($params);
	// 	$result = array('data_count'=>0);
	// 	$success = true;
    //     $resultText = $this->getResultTextFromParams($params);
    //     $clean_xml = str_ireplace(['SOAP-ENV:', 'SOAP:'], '', $resultText);
    //     $resultObj = simplexml_load_string($clean_xml);
    //     $result_game = $resultObj->Body->GetBrandCompletedGameResultsTempResponse->GetBrandCompletedGameResultsTempResult;
    //     $resultRows = json_decode(json_encode($result_game),true);

    //     /*
	// 		** If the bet has only 1 log, a key doesn't exists **

	// 		[PlayerCompletedGamesExtendedDTO] => Array
	// 		(
	// 			[PlayerId] => c5e2717f-5a67-e911-81b3-000d3a805b30
	//             [BrandId] => fb598e7b-fc30-e911-81b3-000d3a805b30
	//             [Username] => noppachat
	//             [BrandGameId] => f6a12372-27eb-4b74-a9c2-7bb846a9f008
	//             [GameKeyName] => SGMysticFortune
	//             [GameTypeId] => 11
	//             [DtStarted] => 2019-09-29T18:39:59.673
	//             [DtCompleted] => 2019-09-29T18:40:00.377
	//             [FriendlyGameInstanceId] => 15035228002
	//             [GameInstanceId] => eedf1aee-f46f-48e1-ab73-322d7e828656
	//             [Stake] => 1.2500
	//             [Payout] => 0.0000
	//             [JackpotWin] => 0
	//             [JackpotContribution] => 0
	//             [CurrencyCode] => THB
	//             [ChannelTypeId] => 4
	//             [BalanceAfter] => 269.0300
	//             [BonusToReal] => Array
	//                 (
	//                 )

	//             [GameStateId] => 3

	// 		)
    //      */

	// 	if (!empty($resultRows['PlayerCompletedGamesExtendedDTO'])) {
	// 		if(array_key_exists(0, $resultRows['PlayerCompletedGamesExtendedDTO'])) {
	// 			$gameRecords = $resultRows['PlayerCompletedGamesExtendedDTO'];
	// 		} else {
	// 			$gameRecords = array($resultRows['PlayerCompletedGamesExtendedDTO']);
	// 		}
	// 	} else {
	// 		$gameRecords = [];
	// 	}

	// 	if($success&&!empty($gameRecords)){
    //         $extra = ['response_result_id'=>$responseResultId];
    //         $this->rebuildGameRecords($gameRecords,$extra);

    //         list($insertRows, $updateRows)=$this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
    //             $this->original_table,
    //             $gameRecords,
    //             'external_uniqueid',
    //             'external_uniqueid',
    //             self::MD5_FIELDS_FOR_ORIGINAL,
    //             'md5_sum',
    //             'id',
    //             self::MD5_FLOAT_AMOUNT_FIELDS
    //         );
	// 		$this->CI->utils->debug_log('after process available rows', count($gameRecords), count($insertRows), count($updateRows));

    //         unset($gameRecords);

    //         if (!empty($insertRows)) {
    //             $result['data_count'] += $this->updateOrInsertOriginalGameLogs($insertRows, 'insert',
    //                 ['responseResultId'=>$responseResultId]);
    //         }
    //         unset($insertRows);

    //         if (!empty($updateRows)) {
    //             $result['data_count'] += $this->updateOrInsertOriginalGameLogs($updateRows, 'update',
    //                 ['responseResultId'=>$responseResultId]);
    //         }
    //         unset($updateRows);
	// 	}

	// 	return array($success, $result);
	// }

	// private function updateOrInsertOriginalGameLogs($rows, $update_type, $additionalInfo=[]){
    //     $dataCount=0;
    //     if(!empty($rows)){
    //         $responseResultId=$additionalInfo['responseResultId'];
    //         foreach ($rows as $record) {
    //             if ($update_type=='update') {
    //                 $this->CI->original_game_logs_model->updateRowsToOriginal($this->original_table, $record);
    //             } else {
    //                 unset($record['id']);
    //                 $this->CI->original_game_logs_model->insertRowsToOriginal($this->original_table, $record);
    //             }
    //             $dataCount++;
    //             unset($data);
    //         }
    //     }

    //     return $dataCount;
    // }

	/**
	 * overview : sync merge to game logs
	 *
	 * @param $token
	 * @return array
	 */
	// public function syncMergeToGameLogs($token) {
    //     $enabled_game_logs_unsettle=false;
    //     return $this->commonSyncMergeToGameLogs($token,
    //         $this,
    //         [$this, 'queryOriginalGameLogs'],
    //         [$this, 'makeParamsForInsertOrUpdateGameLogsRow'],
    //         [$this, 'preprocessOriginalRowForGameLogs'],
    //         $enabled_game_logs_unsettle);
    // }

//     public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time){
//         $sqlTime='hb.dtcompleted >= ?
//           AND hb.dtcompleted <= ?';
//         if($use_bet_time){
//             $sqlTime='hb.dtstarted >= ?
//           AND hb.dtstarted <= ?';
//         }

//         $sql = <<<EOD
// 			SELECT
// 				hb.id as sync_index,
// 				hb.response_result_id,
// 				hb.friendlygameinstanceid as round,
// 				hb.username,
// 				hb.stake as bet_amount,
// 				hb.stake as valid_bet,
// 				hb.balanceafter as after_balance,
// 				hb.payout as result_amount,
// 				IFNULL(hb.bonustoreal,0) AS bonus_amount,
// 				hb.dtstarted as start_at,
// 				hb.dtcompleted as end_at,
// 				hb.dtstarted as bet_at,
// 				hb.gamekeyname as game_code,
// 				hb.gamekeyname as game_name,
// 				hb.updated_at,
// 				hb.external_uniqueid,
// 				hb.md5_sum,
// 				game_provider_auth.player_id,
// 				gd.id as game_description_id,
// 				gd.game_name as game_description_name,
// 				gd.game_type_id
// 			FROM $this->original_table as hb
// 			LEFT JOIN game_description as gd ON hb.gamekeyname = gd.external_game_id AND gd.game_platform_id = ?
// 			LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
// 			JOIN game_provider_auth ON hb.username = game_provider_auth.login_name
// 			AND game_provider_auth.game_provider_id=?
// 			WHERE
//             {$sqlTime}
// EOD;

//         $params=[
//             $this->getPlatformCode(),
//             $this->getPlatformCode(),
//             $dateFrom,
//             $dateTo
//         ];

//         return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
//     }

    // public function makeParamsForInsertOrUpdateGameLogsRow(array $row){
    //     $extra = [
    //         'table' =>  $row['round'],
    //     ];
    //     if(empty($row['md5_sum'])){
    //         $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE,
    //             self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
    //     }
    //     return [
    //         'game_info' => [
    //             'game_type_id' => $row['game_type_id'],
    //             'game_description_id' => $row['game_description_id'],
    //             'game_code' => $row['game_code'],
    //             'game_type' => null,
    //             'game' => $row['game_code']
    //         ],
    //         'player_info' => [
    //             'player_id' => $row['player_id'],
    //             'player_username' => $row['username']
    //         ],
    //         'amount_info' => [
    //             'bet_amount' => $this->gameAmountToDB($row['valid_bet']),
    //             'result_amount' => $this->gameAmountToDB((($row['result_amount']+$row['bonus_amount'])-$row['bet_amount'])),
    //             'bet_for_cashback' => $this->gameAmountToDB($row['valid_bet']),
    //             'real_betting_amount' => $this->gameAmountToDB($row['bet_amount']),
    //             'win_amount' => null,
    //             'loss_amount' => null,
    //             'after_balance' => $this->gameAmountToDB($row['after_balance'])
    //         ],
    //         'date_info' => [
    //             'start_at' => $row['bet_at'],
    //             'end_at' => $row['end_at'],
    //             'bet_at' => $row['bet_at'],
    //             'updated_at' => $this->CI->utils->getNowForMysql(),
    //         ],
    //         'flag' => Game_logs::FLAG_GAME,
    //         'status' => Game_logs::STATUS_SETTLED,
    //         'additional_info' => [
    //             'has_both_side' => 0,
    //             'external_uniqueid' => $row['external_uniqueid'],
    //             'round_number' => $row['round'],
    //             'md5_sum' => $row['md5_sum'],
    //             'response_result_id' => $row['response_result_id'],
    //             'sync_index' => $row['sync_index'],
    //             'bet_type' => null
    //         ],
    //         'bet_details' => [],
    //         'extra' => $extra,
    //         //from exists game logs
    //         'game_logs_id'=>isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
    //         'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
    //     ];
    // }


    // public function preprocessOriginalRowForGameLogs(array &$row){
    //     if (empty($row['game_description_id'])) {
    //         $unknownGame = $this->getUnknownGame($this->getPlatformCode());
    //         list($game_description_id,$game_type_id) = $this->getGameDescriptionInfo($row,$unknownGame);
    //         $row['game_description_id']= $game_description_id;
    //         $row['game_type_id'] = $game_type_id;
    //     }
    //     $row['status'] = Game_logs::STATUS_SETTLED;
    // }

	// private function getGameDescriptionInfo($row, $unknownGame) {
	// 	$game_description_id = null;
	// 	$external_game_id = $row['game_code'];
    //     $extra = array('game_code' => $external_game_id,'game_name' => $row['game_name']);

    //     $game_type_id = $unknownGame->game_type_id ? $unknownGame->game_type_id : null;
    //     $game_type = $unknownGame->game_name ? $unknownGame->game_name : self::TAG_CODE_UNKNOWN_GAME;

	// 	return $this->processUnknownGame(
	// 		$game_description_id, $game_type_id,
	// 		$external_game_id, $game_type, $external_game_id, $extra,
	// 		$unknownGame);
	// }

	/**
	 * overview : processing result
	 *
	 * @param $responseResultId
	 * @param $result
	 * @param null $playerName
	 * @return bool
	 */
	protected function processResultBoolean($responseResultId, $result, $playerName = null,$statusCode=200) {
		$this->CI->utils->debug_log('processResultBoolean result ==>',$result);
		$success = false;
		if ($result) {
			$success = true;
		}

		if ($this->currentApi === self::API_queryTransaction && isset($result)) {
			$success = true;
		}

		if(($this->currentApi === self::API_withdrawFromGame || $this->currentApi === self::API_depositToGame)
			&& isset($result['Success']) && $result['Success']==false){
			$success = false;
		}

		# check if game sync is current API executing
		if($this->currentApi === self::API_syncGameRecords){
			if($statusCode === self::ERROR_CODE_TO0_MANY_REQUEST){
				$success = false;
			}
			if(is_array($result) && $statusCode !== self::ERROR_CODE_TO0_MANY_REQUEST){
				$success = true;
			}
		}

		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('HB got error', $responseResultId, 'playerName', $playerName, 'result', $result, 'apiName', $this->currentApi);
		}

		return $success;
	}

	/**
	 * overview : auth soap
	 *
	 * @param $client
	 * @return bool
	 */
	protected function authSoap($client) {
		return true;
	}

	/**
	 * overview : make soap options
	 *
	 * @param $options
	 * @return mixed
	 */
	protected function makeSoapOptions($options) {
		//overwrite in sub-class
		if($this->use_local_wsdl){
			$options['ignore_ssl_verify'] = $this->getSystemInfo('ignore_ssl_verify', true);
		}
		return $options;
	}

	/**
	 * overview : check if player exist
	 *
	 * @param $playerName
	 * @return array
	 */
	public function isPlayerExist($playerName) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$playerId=$this->getPlayerIdFromUsername($playerName);
		$password = $this->getPassword($playerName);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForIsPlayerExist',
			'playerId' => $playerId,
			'gameUsername' => $gameUsername,
		);
		$params = array(
			'BrandId' => $this->brandId,
			'APIKey' => $this->APIKey,
			"Username" => $gameUsername,
			"Password" => $password['password'],
		);
		$params = array('req' => $params);
		return $this->callApi(self::API_isPlayerExist, $params, $context);
	}

	/**
	 * overview : process result for isPlayerExist
	 *
	 * @param $params
	 * @return array
	 */
	protected function processResultForIsPlayerExist($params) {
		$exists = false;
		$responseResultId = $this->getResponseResultIdFromParams((array) $params);
		$resultObj = $this->getResultObjFromParams((array) $params);
		$gameUsername = $this->getVariableFromContext((array) $params, 'gameUsername');
        $playerId = $this->getVariableFromContext($params, 'playerId');

        $success = false;
        if(!empty($resultObj) && isset($resultObj->QueryPlayerResult)){
        	if(isset($resultObj->QueryPlayerResult->Found)){
        		$exists = $resultObj->QueryPlayerResult->Found ? true : false;
        		$success = true;
        	}
        } else {
			$this->CI->utils->debug_log('processResultForIsPlayerExist returned null');
        }

		if (!$success) {
			$exists=null;
		}

		if($exists){
	        $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
		}

		$result = ['exists' => $exists,'response_result_id'=>$responseResultId];

		return array($success, $result);
	}

	/**
	 * overview : query player currency
	 *
	 * @param $playerName
	 * @return array
	 */
	public function queryPlayerCurrency($playerName) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$password = $this->getPassword($playerName);
		//$playerId = $this->getPlayerIdByExternalAccountId($gameUsername);
		$playerId = $this->CI->player_model->getPlayerIdByUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryPlayerCurrency',
			'playerId' => $playerId,
			'gameUsername' => $gameUsername,
		);
		$params = array(
			'BrandId' => $this->brandId,
			'APIKey' => $this->APIKey,
			"Username" => $gameUsername,
			"Password" => $password['password'],
		);
		$params = array('req' => $params);
		return $this->callApi(self::API_queryPlayerCurrency, $params, $context);
	}

	/**
	 * overview : processing result for queryPlayerCurrency
	 * @param $params
	 * @return array
	 */
	public function processResultForQueryPlayerCurrency($params) {
		$resultObj = $this->getResultObjFromParams((array) $params);
		$success = false;
		$result = array();
		if($resultObj != null){
			if(isset($resultObj->QueryPlayerResult)){
				$exist = $resultObj->QueryPlayerResult->Found ? 1 : 0;
				if($exist){
					$success = true;
					$result['currency_code'] = $resultObj->QueryPlayerResult->CurrencyCode;
					$result['currency_symbol'] = $resultObj->QueryPlayerResult->CurrencySymbol;
				}
			}
		}
		return array($success, $result);
	}

	/**
	 * overview : query player information
	 *
	 * @param $playerName
	 * @return array
	 */
	public function queryPlayerInfo($playerName) {
		$accountNumber = $this->getGameUsernameByPlayerUsername($playerName);
		if(empty($accountNumber)){
			return null;
		}
		$password = $this->getPassword($playerName);

		//$playerId = $this->getPlayerIdByExternalAccountId($accountNumber);
		$playerId = $this->CI->player_model->getPlayerIdByUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryPlayerBalance',
			'playerId' => $playerId,
			'accountNumber' => $accountNumber,
		);
		$params = array(
			'BrandId' => $this->brandId,
			'APIKey' => $this->APIKey,
			"Username" => $accountNumber,
			"Password" => $password['password'],
		);
		$params = array('req' => $params);
		$result = $this->callApi(self::API_queryPlayerInfo, $params, $context);

		return array($result);
	}

	/**
	 * overview : processing result for queryPlayerInfo
	 * @param $params
	 * @return array
	 */
	public function processResultForQueryPlayerInfo($params) {
		$responseResultId = $this->getResponseResultIdFromParams((array) $params);
		$resultObj = $this->getResultObjFromParams((array) $params);
		$accountNumber = $this->getVariableFromContext((array) $params, 'accountNumber');

		$playerName = $accountNumber;

		if ($resultObj == null) {
			$this->CI->utils->debug_log('processResultForQueryPlayerBalance returned null');
			return array(false, null);
		}

		$status = $resultObj->QueryPlayerResult->Found ? 1 : 0;
		$success = $this->processResultBoolean($responseResultId, $status, $playerName);
		// $this->CI->utils->debug_log('processResultForQueryPlayerBalance success', $success);
		$result = array();
		// if ($success) {
		$result = (array) $resultObj->QueryPlayerResult;
		// $playerId = $this->getPlayerIdByExternalAccountId($resultObj->GetAccountBalanceResult->BalanceResult->AccountNumber);
		$playerId = $this->CI->player_model->getPlayerIdByUsername($playerName);

		if ($success) {
			$this->CI->utils->debug_log('query balance playerId', $playerId, 'playerName',
				$playerName, 'balance', @$result['balance']);
		} else {
			$success = false;
		}

		if (!$success) {
			$this->CI->utils->debug_log('HB got error', $responseResultId, 'playerName', $accountNumber, 'result', $result);
		}
		return array('success' => $success, $result);
	}

	// /**
	//  * overview : block player
	//  * @param $playerName
	//  * @return array
	//  */
	// public function blockPlayer($playerName) {
	// 	$playerName = $this->getGameUsernameByPlayerUsername($playerName);
	// 	$success = $this->blockUsernameInDB($playerName);
	// 	return array("success" => true);
	// }

	// /**
	//  * overview : unblock player
	//  *
	//  * @param $playerName
	//  * @return array
	//  */
	// public function unblockPlayer($playerName) {
	// 	$playerName = $this->getGameUsernameByPlayerUsername($playerName);
	// 	$success = $this->unblockUsernameInDB($playerName);
	// 	return array("success" => true);
	// }

	/**
	 * overview : reset player
	 * @param $playerName
	 * @return array
	 */
	// public function resetPlayer($playerName) {
	// 	return $this->returnUnimplemented();
	// }

	/**
	 * overview : update player information
	 *
	 * @param $playerName
	 * @param $infos
	 * @return array
	 */
	// public function updatePlayerInfo($playerName, $infos) {
	// 	return $this->returnUnimplemented();
	// }

	/**
	 * overview : query player daily balance
	 *
	 * @param $playerName
	 * @param $playerId
	 * @param null $dateFrom
	 * @param null $dateTo
	 * @return array
	 */
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

	/**
	 * overview : check login status
	 *
	 * @param $playerName
	 * @return array
	 */
	// public function checkLoginStatus($playerName) {
	// 	return array("success" => true, "loginStatus" => true);
	// }

	/**
	 * overview : get total betting game amount
	 *
	 * @param $playerName
	 * @param $dateFrom
	 * @param $dateTo
	 * @return array
	 */
	public function totalBettingAmount($playerName, $dateFrom, $dateTo) {
		$gameBettingRecord = parent::getGameTotalBettingAmount($playerName, $dateFrom, $dateTo);
		if ($gameBettingRecord != null) {
			$result['bettingAmount'] = $gameBettingRecord['bettingAmount'];
		}
		return array("success" => true, "bettingAmount" => $result['bettingAmount']);
	}

	/**
	 * overview : get timeout second
	 *
	 * @return int
	 */
	// protected function getTimeoutSecond() {
	// 	return 3600;
	// }

	/**
	 * overview : batch query player balance
	 *
	 * @param $playerNames
	 * @param null $syncId
	 * @return array
	 */
	// public function batchQueryPlayerBalance($playerNames, $syncId = null) {
	// 	return $this->returnUnimplemented();
	// }


	/**
	 * overview : query available player balance
	 *
	 * @return array
	 */
	// public function useQueryAvailableBalance() {
	// 	return $this->returnUnimplemented();
	// }

	/**
	 * overview : sync incomplete games
	 *
	 * @param $token
	 * @return array
	 */
	public function syncIncompleteGames($token) {

		$syncId = parent::getValueFromSyncInfo($token, 'syncId');

		$this->CI->utils->debug_log('HB sync incomplete games');

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForSyncIncompleteGames',
			'syncId' => $syncId,
		);

		$params = [
			'BrandId' => $this->brandId,
			'APIKey' => $this->APIKey,
		];

		return $this->callApi(self::API_syncIncompleteGames, $params, $context);
	}

	public function processResultForSyncIncompleteGames($params) {

        $resultJson = $this->getResultJsonFromParams($params);
        $responseResultId=$this->getResponseResultIdFromParams($params);
        $success=$resultJson!==null;
		$result = array('data_count'=>0);

		$this->CI->utils->debug_log('processResultForSyncIncompleteGames resultJson', count($resultJson));

		if($success){
			$this->CI->load->model(['game_logs']);
			$gamePlatformId=$this->getPlatformCode();
			$existsIdArr=$this->CI->game_logs->getExistsIdList('hb_incomplete_games',
				['game_platform_id'=>$gamePlatformId]);
			$this->CI->utils->debug_log('exists id arr', $existsIdArr);
			if(!empty($resultJson)){
				foreach ($resultJson as $row) {
					$externalUniqueId=$gamePlatformId.'-'.$row['Username'].'-'.$row['GameInstanceId'];
					$dtstarted=new DateTime($row['DtStarted']);
					$dtstarted=$this->gameTimeToServerTime($dtstarted->format('Y-m-d H:i:s'));
					//save it to db
					$data=[
						'player_id'=>$row['PlayerId'],
						'username'=>$row['Username'],
						'game_instance_id'=>$row['GameInstanceId'],
						'friendly_id'=>$row['FriendlyId'],
						'game_name'=>$row['GameName'],
						'game_key_name'=>$row['GameKeyName'],
						'provider'=>$row['Provider'],
						'brand_game_id'=>$row['BrandGameId'],
						'dt_started'=>$dtstarted,
						'stake'=>$row['Stake'],
						'payout'=>$row['Payout'],
						'game_state_id'=>$row['GameStateId'],
						'game_state_name'=>$row['GameStateName'],
						'response_result_id'=>$responseResultId,
						'external_uniqueid'=>$externalUniqueId,
						'updated_at'=>$this->utils->getNowForMysql(),
						'username_key'=>$gamePlatformId.'-'.$row['Username'],
						'game_platform_id'=>$gamePlatformId,
					];
					$id=$this->CI->game_logs->updateOrInsertRowByUniqueField('hb_incomplete_games', $data,
						function(&$data, $id){
						if(empty($id)){
							$data['created_at']=$this->utils->getNowForMysql();
						}
					});
					if(empty($id)){
						$this->CI->utils->error_log('update or insert failed', $data, $row);
						break;
					}else{
						if(!empty($existsIdArr)){
							//remove id
							$key=array_search($id, $existsIdArr);
							if(!empty($key)){
								unset($existsIdArr[$key]);
							}
							$this->CI->utils->debug_log('delete by id', $id, count($existsIdArr));
						}
						$result['data_count']++;
					}
				}
			} //if(!empty($resultJson)){
			// still delete if empty
			$deleteArr=$existsIdArr;
			if(!empty($deleteArr)){
				$this->CI->utils->info_log('delete id', $deleteArr);
				//batch delete
				$this->CI->game_logs->runBatchDeleteByIdWithLimit('hb_incomplete_games', $deleteArr);
			}
		} //if($success){

		return array($success, $result);
	}

    public function revertBrokenGame($playerName) {

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForRevertBrokenGame',
			'syncId' => $syncId,
		);

		$params = array(
			'BrandId' => $this->brandId,
			'APIKey' => $this->APIKey,
			'Username'=> $gameUsername,
		);

		return $this->callApi(self::API_revertBrokenGame, $params, $context);
    }

	public function processResultForRevertBrokenGame($params) {
		$result = array('data_count'=>0);
        $resultJsonArr = $this->getResultJsonFromParams($params);
		$success = !empty($resultJsonArr) && $resultJsonArr['Success'];
		if(isset($resultJsonArr['GamesExpired'])){
			$result['data_count']=$resultJsonArr['GamesExpired'];
		}

        return [$success, $result];
	}

    public function queryIncompleteGames($playerName) {
		$this->CI->utils->debug_log('HB query incomplete games');

		$gameUsername=$this->getGameUsernameByPlayerUsername($playerName);
		if(empty($gameUsername)){
			return ['success'=>false, 'error'=>'cannot find player name'];
		}
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryIncompleteGames',
			'gameUsername'=>$gameUsername,
			'playerName'=>$playerName,
		);

		$params = [
			'BrandId' => $this->brandId,
			'APIKey' => $this->APIKey,
			'Username' => $gameUsername,
		];

		return $this->callApi(self::API_queryIncompleteGames, $params, $context);

    }

    public function processResultForQueryIncompleteGames($params){
        $resultJson = $this->getResultJsonFromParams($params);
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $responseResultId=$this->getResponseResultIdFromParams($params);
        $success=$resultJson!==null;
		$result = array('data_count'=>0);

		$this->CI->utils->debug_log('processResultForQueryIncompleteGames resultJson', count($resultJson));
		if($success){
			if(!empty($resultJson)){
				$this->CI->load->model(['game_logs']);
				$gamePlatformId=$this->getPlatformCode();
				foreach ($resultJson as $row) {
					$externalUniqueId=$gamePlatformId.'-'.$gameUsername.'-'.$row['GameInstanceId'];
					$dtstarted=new DateTime($row['DtStarted']);
					$dtstarted=$this->gameTimeToServerTime($dtstarted->format('Y-m-d H:i:s'));
					//save it to db
					$data=[
						'username'=>$gameUsername,
						'game_instance_id'=>$row['GameInstanceId'],
						'friendly_id'=>$row['FriendlyId'],
						'game_name'=>$row['GameName'],
						'game_key_name'=>$row['Keyname'],
						'dt_started'=>$dtstarted,
						'response_result_id'=>$responseResultId,
						'external_uniqueid'=>$externalUniqueId,
						'updated_at'=>$this->utils->getNowForMysql(),
						'game_platform_id'=>$gamePlatformId,
					];
					$id=$this->CI->game_logs->updateOrInsertRowByUniqueField('hb_incomplete_games', $data,
						function(&$data, $id){
						if(empty($id)){
							$data['created_at']=$this->utils->getNowForMysql();
						}
					});
					if(empty($id)){
						$this->CI->utils->error_log('update or insert failed', $data, $row);
						break;
					}else{
						$result['data_count']++;
					}
				}
			}
		}

		return array($success, $result);
    }

    public function testMD5Fields($resultText, $externalUniqueId){
        $clean_xml = str_ireplace(['SOAP-ENV:', 'SOAP:'], '', $resultText);
        $resultObj = simplexml_load_string($clean_xml);
        $result_game = $resultObj->Body->GetBrandCompletedGameResultsTempResponse->GetBrandCompletedGameResultsTempResult;
        $resultRows = json_decode(json_encode($result_game),true);
		$gameRecords = !empty($resultRows['PlayerCompletedGamesExtendedDTO'])?$resultRows['PlayerCompletedGamesExtendedDTO']:[];
        $extra = ['response_result_id'=>null];
        $this->rebuildGameRecords($gameRecords,$extra);
		//only keep one external uniqueid
		$apiRows=[];
		foreach ($gameRecords as $row) {
			if($row['external_uniqueid']==$externalUniqueId){
				$apiRows[]=$row;
				break;
			}
		}
		unset($gameRecords);
		$originalStrArr=[];
        $uniqueidValues=$this->CI->original_game_logs_model->preprocessRows($apiRows, self::MD5_FIELDS_FOR_ORIGINAL,
        	'external_uniqueid', 'md5_sum', self::MD5_FLOAT_AMOUNT_FIELDS, $originalStrArr);

		// $this->CI->utils->debug_log('after process available rows', $gameRecords, $uniqueidValues);

		return ['rows'=>$apiRows, 'uniqueidValues'=>$uniqueidValues, 'originalStrArr'=>$originalStrArr];
    }

    public function getOriginalTable(){
    	return $this->original_table;
    }

	public function getMD5Fields(){
		return [
			'md5_fields_for_original'=>  $this->getMd5FieldsForOriginal(),
			'md5_float_fields_for_original'=> $this->getMd5FloatAmountFields(),
			'md5_fields_for_merge'=>$this->getMd5FieldsForMerge() ,
			'md5_float_fields_for_merge'=>$this->getMd5FloatAmountFieldsForMerge(),
		];
	}

	/**
	 * Query a date range and receive a list of usernames with total stake and total payout for the period. Hour granularity
	 *
	 * HTTP verb: POST
	 * Headers: Content-Type application/json
	 *
	 * @param datetime $startDate
	 * @param datetime $endDate
	 *
	 * @depends processResultForReportPlayerStakePayout
	 * @return
	 */
    public function queryPlayerReport($startDate,$endDate)
    {
		$brandId = $this->brandId;
		$apiKey = $this->APIKey;
		$start = new \DateTime($startDate);
		$end = new \DateTime($endDate);

		$context = [
			'callback_obj' => $this,
			'callback_method' => 'processResultForReportPlayerStakePayout'
		];

		$params = [
			'BrandId' => $brandId,
			'APIKey' => $apiKey,
			'DTStartUTC' => $start->format('YmdHis'),
			'DTEndUTC' => $end->format('YmdHis')
		];
		$this->CI->utils->debug_log(__METHOD__. ' params >>>>>>>>',$params);

		return $this->callApi(self::ReportPlayerStakePayout,$params,$context);
	}

	public function processResultForReportPlayerStakePayout($params)
	{
		$resultJson = $this->getResultJsonFromParams($params);
		$responseResultId=$this->getResponseResultIdFromParams($params);
		$success = $resultJson !== null;
		$resultJsonCnt = is_array($resultJson) ? count($resultJson) : null;
		$formattedData = !is_null($resultJsonCnt) ? $this->generateReturnStakePayout($resultJson) : null;

		$result = [
			'response_result_id' => $responseResultId,
			'data_count' => $resultJsonCnt,
			'data' => $formattedData
		];

		return [
			$success,
			$result
		];
	}

	/**
	 * Process the return of reportPlayerStakePayout for Gamegateway@query_simple_player_report_from_game_provider
	 * @param array $data
	 * @return array
	 */
	public function generateReturnStakePayout($data)
	{
		$result = [];
		$data = is_array($data) ? $data  : null;

		if(! is_null($data)){
			foreach($data as $key => $value){

				if(isset($value['Username'])){
					$result[$key]['username'] = $value['Username'];
					$result[$key]['game_username'] = $value['Username'];
				}

				if(isset($value['Payout'])){
					$strTurnover = 'turnover_' . (!empty($this->getPlatformCode()) ? $this->getPlatformCode() : "");
					$result[$key][$strTurnover] = $value['Payout'];
				}

				if(isset($value['Nett'])){
					$strNett = 'ggr_' . (!empty($this->getPlatformCode()) ? $this->getPlatformCode() : "");
					$result[$key][$strNett] = $value['Nett'];
				}
				continue;
			}
		}

		return $result;
	}

	public function getIdempotentTransferCallApiList(){
		return [self::API_depositToGame, self::API_withdrawFromGame];
	}
    
    public function queryBetDetailLink($playerUsername, $round_id = null, $extra=null)
    {

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerUsername);

		$result['success'] = true;
		
		$params = [
			'brandid'=>$this->brandId,
			'username'=>$gameUsername,	
			//'FriendlyId'=> $round_id,		
			'locale'=>$this->getLauncherLanguage(@$extra['language']),
			'viewtype'=>$this->history_view_type
		];

		if($this->unique_game_id_used=='Gameinstanceid'){
			$params['Gameinstanceid'] = $round_id;
		}else{
			$params['FriendlyId'] = $round_id;
		}

		$hashString = strtolower($round_id.$this->brandId.$this->APIKey);
		$params['hash'] = $this->hashSHA256($hashString);

		$result['url'] = $this->history_url. '?' . http_build_query($params);

		return $result;
    }

    public function hashSHA256($string) {
		return hash('SHA256', $string);
	}
}
