<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

/**
 *
 *
 * rng(slots) flash launcher
 *
 * mg_rng_game_url_prefix
 * mg_rng_params
 *
 * https://redirect.contdelivery.com/Casino/Default.aspx
 * ?applicationid=1023
 * &serverid=2635
 * &csid=2635
 * &theme=igamingA
 * &usertype=0
 * &gameid=[GAMEID]
 * &sEXT1=[LOGINNAME]
 * &sEXT2=[PASSWORD]
 * &ul=zh
 *
 * flash live launcher
 *
 * mg_live_game_url_prefix
 * mg_live_params
 *
 * https://webservice.basestatic.net/ETILandingPage/
 * ?CasinoID=2635
 * &LoginName=LOGINNAME
 * &Password=PASSWORD
 * &ClientID=4
 * &UL=en
 * &VideoQuality=AutoHD
 * &BetProfileID=DesignStyleA
 * &CustomLDParam=MultiTableMode^^1||LobbyMode^^C
 * &StartingTab=SPCasinoHoldem||CDNselection^^1
 * &ClientType=1
 * &ModuleID=70004
 * &UserType=0
 * &ProductID=2
 * &ActiveCurrency=Credits
 * &altproxy=TNG
 *
 * mobile launcher
 *
 * mg_mobile_rng_game_url
 * mg_mobile_rng_game_params
 *
 * https://mobile22.gameassists.co.uk/mobilewebservices_40/casino/game/launch/GoldenTree/{game_code}/{lang}/?
 *
 * lobbyURL=<Operator's URL>
 * &bankingURL=<Operator's URL>
 * &username=xxxxxx
 * &password=xxxxxx
 * &currencyFormat=%23%2C%23%23%23.%23%23
 * &logintype=fullUPE
 * &xmanEndPoints=https://xplay22.gameassists.co.uk/xman/x.x
 *
 *
 * mobile live launcher
 *
 * mg_mobile_live_game_url
 * mg_mobile_live_game_params
 *
 * https://webservice.basestatic.net/ETILandingPage/index.aspx
 * ?LoginName=LOGINNAME
 * &Password=PASSWORD
 * &UL=zh-cn
 * &CasinoID=2635
 * &ClientID=6
 * &BetProfileID=MobilePostLogin
 * &StartingTab=SPSicbo
 * &BrandID=igaming
 * &altProxy=TNG
 *
 * live mobile apk
 *
 * mg_live_mobile_apk_url
 * https://livegames.gameassists.co.uk/MobileClient/MobileRedirector/index.aspx?CasinoID=2635&AppID=iGamingLiveDiamondA&ClientID=5&UL=ja-jp&URLType=AndroidDownload
 *
 * rng(slots) mobile apk
 *
 * mg_rng_mobile_apk_url
 * http://resigner.qfcontent.com/goldentree.apk
 *
 * bet url
 * mg_bet_detail_url
 * mg_bet_detail_params
 *
 * https://redirector3.valueactive.eu/casino/default.aspx?
 * applicationid=1001
 * &password=<PASSWORD>
 * &serverid=2635
 * &username=<USERNAME>
 * &lang=zh-cn
 * &transactionID=<TRANSACTIONID>
 * &SessionID=<SESSION NUMBER>
 * &timezone=+8.0
 *
 */
class Game_api_mg extends Abstract_game_api {

	const API_getCurrencyForCreateAccount = "getCurrencyForCreateAccount";
	const API_getBettingProfileList = "getBettingProfileList";
	const API_getCurrencyForDeposit = "getCurrencyForDeposit";
	const API_getMyBalance = "getMyBalance";

	const ERROR_CODE_NOT_FOUND_PLAYER = 24;

	const MG_LIVEGAME = 1;
	const MG_RNGGAME = 2;

	const ORIGINAL_LOGS_TABLE_NAME = 'mg_game_logs';

	const MD5_FIELDS_FOR_ORIGINAL=[
		"row_id",
		"account_number",
		"display_name",
		"display_game_category",
		"session_id",
		"game_end_time",
		"total_wager",
		"total_payout",
		"progressive_wage",
		"iso_code",
		"module_id",
		"client_id",
		"transaction_id"
	];

	const MD5_FLOAT_AMOUNT_FIELDS=[
		'total_wager',
		'total_payout',
	];

	const MD5_FIELDS_FOR_MERGE = [
		"row_id",
		"account_number",
		"display_name",
		"display_game_category",
		"session_id",
		"game_end_time",
		"total_wager",
		"total_payout",
		"progressive_wage",
		"iso_code",
		"module_id",
		"client_id",
		"transaction_id"
	];

	const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE = [
		'total_wager',
		'total_payout',
	];

	public function __construct() {
		parent::__construct();

		$this->default_start_row_id=$this->getSystemInfo('default_start_row_id', 0);
		$this->default_language=$this->getSystemInfo('default_language', 'zh');

		$this->transaction_delay_max_minutes=$this->getSystemInfo('transaction_delay_max_minutes', 60);
		$this->new_rng_redirection=$this->getSystemInfo('new_rng_redirection', false);
		$this->rng_lobby_name=$this->getSystemInfo('rng_lobby_name', 'goldentree');
		$this->new_sync_merge=$this->getSystemInfo('new_sync_merge', false);
		$this->set_player_language=$this->getSystemInfo('set_player_language', false);
	}

	public function getPlatformCode() {
		return MG_API;
	}

	protected function getCallType($apiName, $params) {
		//overwrite in sub-class
		return self::CALL_TYPE_SOAP;
	}

	public function generateUrl($apiName, $params) {
       	// return realpath(dirname(__FILE__)).'/'.$this->getSystemInfo('wsdl_filename', 'entservices200.totalegame.net.wsdl');
   		return $this->getSystemInfo('url');
	}

	protected function generateSoapMethod($apiName, $params) {

		switch ($apiName) {
		case self::API_batchQueryPlayerBalance:
			return array('GetAccountBalance', $params);
			break;

		case self::API_queryPlayerBalance:
			return array('GetAccountBalance', $params);
			break;

		case self::API_getCurrencyForCreateAccount:
			return array('GetCurrenciesForAddAccount', $params);
			break;

		case self::API_getBettingProfileList:
			return array('GetBettingProfileList', $params);
			break;

		case self::API_createPlayer:
			return array('AddAccount', $params);
			break;

		case self::API_isPlayerExist:
			return array('IsAccountAvailable', $params);
			break;

		case self::API_queryPlayerInfo:
			return array('GetAccountDetails', $params);
			break;

		case self::API_depositToGame:
			return array('Deposit', $params);
			break;

		case self::API_getCurrencyForDeposit:
			return array('GetCurrenciesForDeposit', $params);
			break;

		case self::API_withdrawFromGame:
			return array('Withdrawal', $params);
			break;

		case self::API_syncGameRecords:
			return array('GetSpinBySpinData', $params);
			break;

		case self::API_changePassword:
			return array('EditAccount', $params);
			break;
		case self::API_blockPlayer:
			return array('LockAccount', $params);
			break;
		case self::API_unblockPlayer:
			return array('LockAccount', $params);
			break;
		case self::API_resetPlayer:
			return array('ResetLoginAttempts', $params);
			break;

		case self::API_queryTransaction:
			return ['GetTransactionDetail', $params];
			break;
		default:
			# code...
			break;
		}

		return parent::generateSoapMethod($apiName, $params);
	}

	protected function authSoap($client) {

		//use cache for session
		// $result = false;
		if ($this->soapAuthResult) {
			//don't call again
			return true;
		}
		//overwrite in sub-class
		$this->soapAuthResult = $client->IsAuthenticate(
			array(
				'loginName' => $this->getSystemInfo('mg_login_name'),
				'pinCode' => $this->getSystemInfo('mg_pin_code'),
			)
		);

		$success = isset($this->soapAuthResult);
		if ($success) {
			$success = !is_soap_fault($this->soapAuthResult);
			if ($success && isset($this->soapAuthResult->IsAuthenticateResult)) {
				//not soap fault
				$success = $this->soapAuthResult->IsAuthenticateResult->IsSucceed;
			}
		}
		return $success;
	}

	protected function makeHttpOptions($options) {
		return $options;
	}

	protected function makeSoapOptions($options) {
		return $options;
	}

	protected function prepareSoap($client) {
		if ($this->soapAuthResult && isset($this->soapAuthResult->IsAuthenticateResult)) {
			$sessionGUID = $this->soapAuthResult->IsAuthenticateResult->SessionGUID;
			$mg_header_url = $this->getSystemInfo('mg_header_url');
			$mg_server_ip = $this->getSystemInfo('mg_server_ip');

			/**
			 *  Setup for header
			 */
			$xml = <<<EOD
<AgentSession xmlns="{$mg_header_url}">
    <SessionGUID>{$sessionGUID}</SessionGUID>
    <IPAddress>{$mg_server_ip}</IPAddress>
</AgentSession>
EOD;

			$xmlvar = new SoapVar($xml, XSD_ANYXML);
			$header = new SoapHeader($mg_header_url, 'AgentSession', $xmlvar);
			$client->__setSoapHeaders($header);
		}
		return $client;
	}

	public function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null) {
		return array(false, null);
	}

	protected function processResultBoolean($responseResultId, $result, $playerName = null) {
		$success = !is_soap_fault($result);
		if ($success) {
			if (isset($result->IsSucceed) && $result->IsSucceed) {
				$success = true;
			} elseif (isset($result->Currency) || isset($result->Profile)) {
				$success = true;
			} else {
				$success = false;
			}
		}

		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->error_log('MG got error', $responseResultId, 'playerName', $playerName, 'result', $result);
		}

		return $success;
	}

	public function getPrepareData() {
		$currencyList = $this->getCurrencyForCreateAccount();
		$profileList = $this->getProfileListId();
		$currencyForDepositList = $this->getCurrencyForDeposit();
		$myBalanceList = $this->getMyBalance();
		return array('currencyList' => $currencyList, 'profileList' => $profileList,
			'currencyForDepositList' => $currencyForDepositList,
			'myBalanceList' => $myBalanceList);
	}

	private function getCurrencyForCreateAccount() {
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForGetCurrencyForCreateAccount',
		);

		return $this->callApi(self::API_getCurrencyForCreateAccount, array(), $context);
	}

	public function processResultForGetCurrencyForCreateAccount($params) {
		$responseResultId = $params['responseResultId'];
		$resultObj = $params['resultObj'];
		$success = false;
		if(isset($resultObj->GetCurrenciesForAddAccountResult)){
			$success = $this->processResultBoolean($responseResultId, $resultObj->GetCurrenciesForAddAccountResult);
		}
		$this->CI->utils->debug_log('processResultForGetCurrencyForCreateAccount', $resultObj);
		$result = array();
		if ($success) {
			$result['currencyList'] = (array) $resultObj->GetCurrenciesForAddAccountResult;
		}

		return array($success, $result);
	}

	private function getProfileListId() {
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForGetBettingProfileList',
		);

		return $this->callApi(self::API_getBettingProfileList, array(), $context);
	}

	public function processResultForGetBettingProfileList($params) {
		$responseResultId = $params['responseResultId'];
		$resultObj = $params['resultObj'];
		$success = false;
		if(isset($resultObj->GetBettingProfileListResult)){
			$success = $this->processResultBoolean($responseResultId, $resultObj->GetBettingProfileListResult);
		}
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
	private function getCurrencyForDeposit() {
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForGetCurrencyForDeposit',
		);

		return $this->callApi(self::API_getCurrencyForDeposit, array(), $context);
	}

	public function processResultForGetCurrencyForDeposit($params) {
		$responseResultId = $params['responseResultId'];
		$resultObj = $params['resultObj'];
		$success = false;
		if(isset($resultObj->GetCurrenciesForDepositResult)){
			$success = $this->processResultBoolean($responseResultId, $resultObj->GetCurrenciesForDepositResult);
		}
		$result = array();
		if ($success) {
			$result['currencyList'] = (array) $resultObj->GetCurrenciesForDepositResult;
		}

		return array($success, $result);
	}

	private function getMyBalance() {
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForGetMyBalance',
		);

		return $this->callApi(self::API_getMyBalance, array(), $context);

	}
	public function processResultForGetMyBalance($params) {
		$responseResultId = $params['responseResultId'];
		$resultObj = $params['resultObj'];
		$success = false;
		if(isset($resultObj->GetMyBalanceResult)){
			$success = $this->processResultBoolean($responseResultId, $resultObj->GetMyBalanceResult);
		}
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

	public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
		parent::createPlayer($playerName, $playerId, $password, $email, $extra);
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$data = $this->getPlayerDetails($playerId);

		$firstName = $playerName;
		$lastName = $playerName;

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForCreatePlayer',
			'playerId' => $playerId,
			'playerName' => $playerName,
		);

		return $this->callApi(self::API_createPlayer, array(
			"accountNumber" => $playerName,
			"password" => $password,
			"firstName" => $firstName,
			"lastName" => $lastName,
			"currency" => $this->getSystemInfo('mg_currency_for_add_account'),
			"BettingProfileId" => $this->getSystemInfo('mg_betting_profile_id_for_add_account'),
			"isProgressive" => true,
		), $context
		);
	}

	public function processResultForCreatePlayer($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultObj = $this->getResultObjFromParams($params);
		$playerId = $this->getVariableFromContext($params, 'playerId');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = false;
		if(isset($resultObj->AddAccountResult)){
			$success = $this->processResultBoolean($responseResultId, $resultObj->AddAccountResult, $playerName);
		}
		$this->updateExternalAccountIdForPlayer($playerId, $resultObj->AddAccountResult->AccountNumber);

		return array($success, null);
	}

	public function isPlayerExist($playerName) {
		$accountNumber = $this->getGameUsernameByPlayerUsername($playerName);
		if (!empty($accountNumber)) {

			$context = array(
				'callback_obj' => $this,
				'callback_method' => 'processResultForIsPlayerExist',
				'playerName' => $playerName,
				'gameUsername' => $accountNumber,
				// 'playerId' => $playerId,
			);
			return $this->callApi(self::API_isPlayerExist, array('accountNumber' => $accountNumber), $context);
		}
		return $this->returnFailed('Not found ' . $playerName);
	}

	protected function processResultForIsPlayerExist($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultObj = $this->getResultObjFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = false;
		if(isset($resultObj->IsAccountAvailableResult)){
			$success = $this->processResultBoolean($responseResultId, $resultObj->IsAccountAvailableResult, $playerName);
		}
		$exists = false;
		if ($success) {
			$exists = !$resultObj->IsAccountAvailableResult->IsAccountAvailable;
		}
		$result = array('exists' => $exists);
		return array($success, $result);
	}

	public function queryPlayerInfo($playerName) {
		$accountNumber = $this->getGameUsernameByPlayerUsername($playerName);
		if (!empty($accountNumber)) {
			$context = array(
				'callback_obj' => $this,
				'callback_method' => 'processResultForQueryPlayerInfo',
				'playerName' => $playerName,
				'gameUsername' => $accountNumber,
			);

			return $this->callApi(self::API_queryPlayerInfo, array('accountNumber' => $accountNumber), $context);
		}
		return $this->returnFailed('Not found ' . $playerName);
	}

	public function processResultForQueryPlayerInfo($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultObj = $this->getResultObjFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = false;
		if(isset($resultObj->GetAccountDetailsResult)){
			$success = $this->processResultBoolean($responseResultId, $resultObj->GetAccountDetailsResult, $playerName);
		}
		$result = array();
		if ($success) {
			$mgrlt = $resultObj->GetAccountDetailsResult;
			$playerInfo = array(
				'playerName' => $mgrlt->AccountNumber,
				'languageCode' => null,
				'blocked' => ($mgrlt->AccountStatus != 'Open'),
			);
			$result["playerInfo"] = $playerInfo;
		}

		return array($success, $result);
	}

	public function changePassword($playerName, $oldPassword, $newPassword) {
		$accountNumber = $this->getGameUsernameByPlayerUsername($playerName);
		if (!empty($accountNumber)) {
			//EditAccount
			$context = array(
				'callback_obj' => $this,
				'callback_method' => 'processResultForChangePassword',
				'playerName' => $playerName,
				'newPassword' => $newPassword,
			);

			return $this->callApi(self::API_changePassword,
				array('accountNumber' => $accountNumber, 'password' => $newPassword, 'bettingProfileId' => $this->getSystemInfo('mg_betting_profile_id_for_add_account')),
				$context);
		}
		return $this->returnFailed('Not found ' . $playerName);
	}
	public function processResultForChangePassword($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultObj = $this->getResultObjFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$newPassword = $this->getVariableFromContext($params, 'newPassword');
		$success = false;
		if(isset($resultObj->EditAccountResult)){
			$success = $this->processResultBoolean($responseResultId, $resultObj->EditAccountResult, $playerName);
		}
		if ($success) {
			$playerId = $this->getPlayerIdInPlayer($playerName);
			//sync password to game_provider_auth
			$this->updatePasswordForPlayer($playerId, $newPassword);
		}
		return array($success, null);
	}

	public function blockPlayer($playerName) {
		$accountNumber = $this->getGameUsernameByPlayerUsername($playerName);
		if (!empty($accountNumber)) {
			//LockAccount
			$context = array(
				'callback_obj' => $this,
				'callback_method' => 'processResultForBlockPlayer',
				'playerName' => $playerName,
				'gameUsername' => $accountNumber,
			);

			return $this->callApi(self::API_blockPlayer,
				array('strAccounts' => array($accountNumber), 'bLock' => true),
				$context);
		}
		return $this->returnFailed('Not found ' . $playerName);
	}
	public function processResultForBlockPlayer($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultObj = $this->getResultObjFromParams($params);
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');

		$success = !is_soap_fault($resultObj);
		if ($success) {
			$this->blockUsernameInDB($gameUsername);
		}
		return array($success, null);
	}

	public function unblockPlayer($playerName) {
		$accountNumber = $this->getGameUsernameByPlayerUsername($playerName);
		if (!empty($accountNumber)) {
			//LockAccount
			$context = array(
				'callback_obj' => $this,
				'callback_method' => 'processResultForUnblockPlayer',
				'playerName' => $playerName,
				'gameUsername' => $accountNumber,
			);

			return $this->callApi(self::API_blockPlayer,
				array('strAccounts' => array($accountNumber), 'bLock' => false),
				$context);
		}
		return $this->returnFailed('Not found ' . $playerName);
	}
	public function processResultForUnblockPlayer($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultObj = $this->getResultObjFromParams($params);
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');

		$success = !is_soap_fault($resultObj);
		if ($success) {
			$this->unblockUsernameInDB($gameUsername);
		}
		return array($success, null);
	}

	public function resetPlayer($playerName) {
		$accountNumber = $this->getGameUsernameByPlayerUsername($playerName);
		if (!empty($accountNumber)) {
			//EditAccount
			$context = array(
				'callback_obj' => $this,
				'callback_method' => 'processResultForResetPlayer',
				'playerName' => $playerName,
				'gameUsername' => $accountNumber,
			);

			return $this->callApi(self::API_resetPlayer,
				array('accountNumbers' => array($accountNumber)),
				$context);
		}
		return $this->returnFailed('Not found ' . $playerName);
	}
	public function processResultForResetPlayer($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultObj = $this->getResultObjFromParams($params);

		$success = !is_soap_fault($resultObj);
		return array($success, null);
	}

	public function depositToGame($playerName, $amount, $transfer_secure_id=null) {
		$playerUsername = $playerName;
		$transactionReferenceNumber = !empty($transfer_secure_id) ? $transfer_secure_id : 'T' . random_string('alnum');
		$accountNumber = $this->getGameUsernameByPlayerUsername($playerName);
		$currency = $this->getSystemInfo('mg_currency_for_deposit'); // $this->getCurrencyForDeposit();
		if (!empty($accountNumber)) {

			$context = array(
				'callback_obj' => $this,
				'callback_method' => 'processResultForDepositToGame',
				'playerName' => $playerName,
				'gameUsername' => $accountNumber,
				'amount' => $amount,
				//because we can't use this number to query transaction on mg api
				//don't use external_transaction_id, because it will be recorded which is wrong id
				'transactionReferenceNumber' => $transactionReferenceNumber,
			);
            // $context['enabled_guess_success_for_curl_errno_on_this_api']=$this->enabled_guess_success_for_curl_errno_on_this_api;
            // $context['is_timeout_mock']=$this->getSystemInfo('is_timeout_mock', false);

			$rlt = $this->callApi(self::API_depositToGame, array(
				'accountNumber' => $accountNumber,
				'amount' => $amount,
				'transactionReferenceNumber' => $transactionReferenceNumber,
				'currency' => $currency,
			), $context);

			return $rlt;
		}
		return $this->returnFailed('Not found ' . $playerName);
	}

	public function processResultForDepositToGame($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultObj = $this->getResultObjFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$amount = $this->getVariableFromContext($params, 'amount');

		$rlt = $this->getFirstResultFromObject($resultObj);
		$success = $this->processResultBoolean($responseResultId, $rlt, $playerName);

		$result = array('response_result_id' => $responseResultId, 'reason_id'=>self::REASON_UNKNOWN);

		if ($success) {
			$afterBalance = floatval($rlt->Balance);
			$result['external_transaction_id'] = $rlt->TransactionId;
			$result["currentplayerbalance"] = $afterBalance; //The account’s balance in the player’s currency
            $result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
            $result['didnot_insert_game_logs']=true;
		} else {
			if(!empty($rlt->ErrorCode)){
				//result is right
	            $result['transfer_status']=self::COMMON_TRANSACTION_STATUS_DECLINED;
	            switch ($rlt->ErrorCode) {
	            	case '2':
	            	case '24':
	            	case '33':
			            $result['reason_id']=self::REASON_NOT_FOUND_PLAYER;
	            		break;
	            	case '35':
			            $result['reason_id']=self::REASON_LOGIN_PROBLEM;
	            		break;
	            	case '39':
			            $result['reason_id']=self::REASON_TRANSFER_AMOUNT_IS_TOO_HIGH;
	            		break;
	            	case '100004':
			            $result['reason_id']=self::REASON_TRANSFER_AMOUNT_IS_TOO_LOW;
	            		break;
	            	case '43':
			            $result['reason_id']=self::REASON_NO_ENOUGH_CREDIT_IN_SYSTEM;
	            		break;
	            	case '45':
			            $result['reason_id']=self::REASON_GAME_ACCOUNT_LOCKED;
	            		break;
	            	case '61':
	            	case '62':
	            	case '71':
			            $result['reason_id']=self::REASON_SESSION_TIMEOUT;
	            		break;
	            	case '7':
			            $result['reason_id']=self::REASON_GAME_PROVIDER_ACCOUNT_PROBLEM;
	            		break;
	            	default:
	            		$result['reason_id']=self::REASON_FAILED_FROM_API;
	            		break;
	            }
			}else{
	            $result['transfer_status']=self::COMMON_TRANSACTION_STATUS_UNKNOWN;
			}
		}

		return array($success, $result);
	}

	public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null) {
		$playerUsername = $playerName;
		$transactionReferenceNumber = !empty($transfer_secure_id) ? $transfer_secure_id : 'T' . random_string('alnum');
		$accountNumber = $this->getGameUsernameByPlayerUsername($playerName);
		if (!empty($accountNumber)) {

			$context = array(
				'callback_obj' => $this,
				'callback_method' => 'processResultForDepositToGame',
				'playerName' => $playerName,
				'gameUsername' => $accountNumber,
				'amount' => -$amount,
				//because we can't use this number to query transaction on mg api
				//don't use external_transaction_id, because it will be recorded which is wrong id
				'transactionReferenceNumber' => $transactionReferenceNumber,
			);

			$rlt = $this->callApi(self::API_withdrawFromGame, array(
				'accountNumber' => $accountNumber,
				'amount' => $amount,
				'transactionReferenceNumber' => $transactionReferenceNumber,
			), $context);
			return $rlt;

		}
		return $this->returnFailed('Not found ' . $playerName);
	}

	public function processResultForWithdrawFromGame($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultObj = $this->getResultObjFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$amount = $this->getVariableFromContext($params, 'amount');

		$rlt = $this->getFirstResultFromObject($resultObj);
		// $rlt = $resultObj->WithdrawalResult;
		$success = $this->processResultBoolean($responseResultId, $rlt, $playerName);

		$result = array('response_result_id' => $responseResultId, 'reason_id'=>self::REASON_UNKNOWN);
		if ($success) {
			$afterBalance = floatval($rlt->Balance);
			$result['external_transaction_id'] = $rlt->TransactionId;
			$result["currentplayerbalance"] = $afterBalance; //The account’s balance in the player’s currency
            $result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
            $result['didnot_insert_game_logs']=true;
		} else {
			if(!empty($rlt->ErrorCode)){
				//result is right
	            $result['transfer_status']=self::COMMON_TRANSACTION_STATUS_DECLINED;
	            switch ($rlt->ErrorCode) {
	            	case '2':
	            	case '24':
	            	case '33':
			            $result['reason_id']=self::REASON_NOT_FOUND_PLAYER;
	            		break;
	            	case '35':
			            $result['reason_id']=self::REASON_LOGIN_PROBLEM;
	            		break;
	            	case '39':
			            $result['reason_id']=self::REASON_TRANSFER_AMOUNT_IS_TOO_HIGH;
	            		break;
	            	case '84':
	            	case '100004':
			            $result['reason_id']=self::REASON_TRANSFER_AMOUNT_IS_TOO_LOW;
	            		break;
	            	case '100003':
			            $result['reason_id']=self::REASON_NO_ENOUGH_BALANCE;
	            		break;
	            	case '43':
			            $result['reason_id']=self::REASON_NO_ENOUGH_CREDIT_IN_SYSTEM;
	            		break;
	            	case '45':
			            $result['reason_id']=self::REASON_GAME_ACCOUNT_LOCKED;
	            		break;
	            	case '61':
	            	case '62':
	            	case '71':
			            $result['reason_id']=self::REASON_SESSION_TIMEOUT;
	            		break;
	            	case '7':
			            $result['reason_id']=self::REASON_GAME_PROVIDER_ACCOUNT_PROBLEM;
	            		break;
	            	default:
	            		$result['reason_id']=self::REASON_FAILED_FROM_API;
	            		break;
	            }
			}else{
	            $result['transfer_status']=self::COMMON_TRANSACTION_STATUS_UNKNOWN;
			}
		}

		return array($success, $result);
	}

	public function login($playerName, $password = null) {
		return $this->returnUnimplemented();
	}

	public function logout($playerName, $password = null) {
		return $this->returnUnimplemented();
	}

	public function updatePlayerInfo($playerName, $infos) {
		return $this->returnUnimplemented();
	}

	public function queryPlayerBalance($playerName) {
		// $password = $this->getPasswordString($playerName);
		$accountNumber = $this->getGameUsernameByPlayerUsername($playerName);
		$playerId = $this->getPlayerIdByExternalAccountId($accountNumber);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryPlayerBalance',
			'playerId' => $playerId,
			'accountNumber' => $accountNumber,
		);

		return $this->callApi(self::API_queryPlayerBalance,
			array("delimitedAccountNumbers" => $accountNumber), $context);
	}

	public function processResultForQueryPlayerBalance($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultObj = $this->getResultObjFromParams($params);
		$playerId = $this->getVariableFromContext($params, 'playerId');
		$accountNumber = $this->getVariableFromContext($params, 'accountNumber');
		$playerName = $accountNumber;

		if ($resultObj == null) {
			$this->CI->utils->debug_log('processResultForQueryPlayerBalance returned null');
			return array(false, null);
		}

		$rlt = $resultObj->GetAccountBalanceResult;

		$success = false;
		$result = array();

		$balResult = $this->getFirstResultFromObject($rlt);
		//search account number
		if (isset($balResult) && strtolower($balResult->AccountNumber) == strtolower($accountNumber)) {
			$result["balance"] = floatval($balResult->Balance);
			$success = $balResult->IsSucceed;
		}

		if ($success && isset($result["balance"]) && $result["balance"] !== null) {
			if ($playerId) {
				$this->CI->utils->debug_log('query balance playerId', $playerId, 'playerName',
					$playerName, 'balance', @$result['balance']);
			}
		} else {
			$success = false;
		}

		if (!$success) {
			$this->CI->utils->debug_log('MG got error', $responseResultId, 'playerName', $accountNumber, 'result', $result);
		}

		return array($success, $result);
	}

	public function checkLoginStatus($playerName) {
		return $this->returnUnimplemented();
	}

	private function getFromToTransferTime($transfer_time){
		$fromTime=(new DateTime($transfer_time))->modify('-10 minutes')->format('Y-m-d\TH:i:s');
		$toTime=(new DateTime($transfer_time))->modify('+10 minutes')->format('Y-m-d\TH:i:s');

		return [$fromTime, $toTime];
	}

	public function queryTransaction($external_transaction_id, $extra) {
		//get time first
		$transfer_time=isset($extra['transfer_time']) ? $extra['transfer_time'] : null;
		$secure_id=isset($extra['secure_id']) ? $extra['secure_id'] : null;

		if(empty($transfer_time) && empty($external_transaction_id)){
			return $this->returnFailed('please support transaction id or transfer time');
		}

		if(!empty($external_transaction_id)){
			//GetTransactionDetail
			$context = array(
				'callback_obj' => $this,
				'callback_method' => 'processResultForQueryTransaction',
				'playerName'=>$extra['playerName'],
				'playerId'=>$extra['playerId'],
				'external_transaction_id' => $external_transaction_id,
			);

			$params=array(
				'transactionId' => $external_transaction_id,
			);

			return $this->callApi(self::API_queryTransaction, $params, $context);

		}else if(!empty($transfer_time) && !empty($secure_id)){

			//assume delay deadline
			$delay_deadline=$this->CI->utils->formatDateTimeForMysql((new DateTime())->modify('-'.$this->transaction_delay_max_minutes.' minutes'));
			$is_not_delay=$transfer_time<$delay_deadline;

			$playerName=$extra['playerName'];
			$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
			$gameUsername=null;

			list($fromTime, $toTime)=$this->getFromToTransferTime($transfer_time);

			//GetFinancialTransactionStatus
			$apiName='GetFinancialTransactionStatus';
			$params=['AccountNumber'=>$gameUsername, 'FromDate'=>$fromTime, 'ToDate'=>$toTime];
			$url = $this->getSystemInfo('mg_web_api_url') . '/' . $apiName;
			$this->CI->utils->debug_log('apiName', $apiName, $params);

			list($header, $resultText, $statusCode, $statusText, $errCode, $error, $resultObj) = $this->httpCallApi($url, $params);
			$this->CI->utils->debug_log("http call", "header", count($header), "resultText", $resultText, "statusCode", $statusCode, "statusText", $statusText, 'callerrCode', $errCode, 'callerr', $error);

			// $rltArr = null;
			$success = !$this->isErrorCode($apiName, $params, $statusCode, $errCode, $error);
			// $this->CI->utils->debug_log('success', $success);
			$dont_save_response_in_api = $this->getConfig('dont_save_response_in_api');
			$field=[];
			$extra = $header;
			$responseResultId = $this->saveResponseResult($success, $apiName, $params,
				$resultText, $statusCode, $statusText, $extra, $field, $dont_save_response_in_api);

			$status=self::COMMON_TRANSACTION_STATUS_UNKNOWN;
			if($success){
				$rltArr=$this->CI->utils->decodeJson($resultText);

				$this->CI->utils->debug_log('result of GetFinancialTransactionStatus', $rltArr);
				if(!empty($rltArr)){
					if(@$rltArr['Status']['ErrorCode']==0){
						//success
						$transResultList=@$rltArr['Result'];
						$found=false;
						if(!empty($transResultList)){
							//search secure_id
							foreach ($transResultList as $transInfo) {
								if($transInfo['TransactionReferenceNumber']==$secure_id){
									$found=true;
									switch ($transInfo['Status']) {
										case 'Succeed':
											$status=self::COMMON_TRANSACTION_STATUS_APPROVED;
											break;

										case 'Pending':
										case 'In Progress':
											$status=self::COMMON_TRANSACTION_STATUS_PROCESSING;
											break;

										case 'Rejected':
										case 'Cancelled':
											$status=self::COMMON_TRANSACTION_STATUS_DECLINED;
											break;

									}
									break;
								}
							}
						}

						if(!$found && $is_not_delay){
							//not found and not delay
							$status=self::COMMON_TRANSACTION_STATUS_DECLINED;
						}

						$this->CI->utils->debug_log('check missing', $found, $is_not_delay, $status);
					}
				}
			}

			return ['success'=>$success, 'response_result_id'=>$responseResultId, 'external_transaction_id'=>$external_transaction_id,
				'status'=>$status];

		}

		return $this->returnFailed('Bad Request');
	}
	/**
	 * for transactionId
	 * @param  array $params params package
	 * @return array [$success, $result]
	 */
	public function processResultForQueryTransaction($params) {
		// $success = $this->processResultBoolean($responseResultId, $resultXml, array('key_error', 'network_error', 'account_not_exist', 'error'));
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultObj = $this->getResultObjFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');

		$rlt = $this->getFirstResultFromObject($resultObj);

		$success = !is_soap_fault($rlt);
		if ($success) {
			if (isset($result->IsSucceed) && $result->IsSucceed) {
				$success = true;
			}else{
				//check error code
				$success = true;
			}
		}
		//default is unknown
		$status=self::COMMON_TRANSACTION_STATUS_UNKNOWN;
		$result=['response_result_id'=>$responseResultId, 'external_transaction_id'=>$external_transaction_id];

		if($success){

			$this->CI->utils->debug_log('result of processResultForQueryTransaction', $rlt);

			switch ($rlt->Status) {
				case 'Succeed':
					$status=self::COMMON_TRANSACTION_STATUS_APPROVED;
					break;

				case 'Pending':
				case 'In Progress':
					$status=self::COMMON_TRANSACTION_STATUS_PROCESSING;
					break;

				case 'Rejected':
				case 'Cancelled':
					$status=self::COMMON_TRANSACTION_STATUS_DECLINED;
					break;

			}
		}

		$result['status']=$status;

		return array($success, $result);
	}

	public function getLauncherLanguage($language,$is_mobile){
        $lang='';
        $this->CI->load->library("language_function");
        switch ($language) {
            case 'en-us':
                	$lang = 'en'; # mobile and flash same code
                break;
            case 'zh-cn':
            case Language_function::INT_LANG_CHINESE:
            	if($is_mobile){
                	$lang = 'zh-cn'; // chinese
            	}else{
            		$lang = 'zh';
            	}
                break;
            case 'id-id':
            case Language_function::INT_LANG_INDONESIAN:
                $lang = 'en'; // indonesia
                break;
            case 'vi-vn':
            case Language_function::INT_LANG_VIETNAMESE:
                $lang = 'en'; // vietnamese
                break;
            case 'ko-kr':
            case Language_function::INT_LANG_KOREAN:
                $lang = 'ko-kr'; // korean
                break;
            default:
                $lang = 'en'; // default as english
                break;
        }
        return $lang;
    }

	function queryForwardGame($playerName, $extra) {

		$result = array("success" => false, "blocked" => false, 'url' => null, 'message' => null);

		$player_id = $this->getPlayerIdInPlayer($playerName);
		# CHECK IF LOGGED-IN PLAYER IS BLOCKED
		$blocked = $this->isBlocked($playerName);

		if ($blocked) {
			$result['success'] = false;
			$result['blocked'] = true;
			$result['message'] = 'goto_game.blocked';
		} else {
			$this->CI->load->model(array('game_provider_auth'));
			$loginInfo = $this->CI->game_provider_auth->getOrCreateLoginInfoByPlayerId($player_id, $this->getPlatformCode());
			if ($loginInfo) {
				$gameType =  $extra['game_type'] = "_null"?($extra['game_code']=="_mglivecasino"?(self::MG_LIVEGAME):(self::MG_RNGGAME)):$extra['game_type'];
				$gameCode = $extra['game_code'];
				$mode = isset($extra['game_mode']) ? $extra['game_mode'] : null;
                // $gameName = $extra['gameName'];
				$is_mobile = $extra['is_mobile'];

				$language = $this->getLauncherLanguage($extra['language'],$is_mobile);
				if(empty($language)){
					$language=$this->default_language;
				}

				$params = array();
				if($is_mobile){

					if ($gameType == Game_api_mg::MG_RNGGAME) {
						if(!$this->new_rng_redirection){
							$url = $this->getSystemInfo('mg_mobile_rng_game_url');
							//slots
							$params = $this->getSystemInfo('mg_mobile_rng_game_params');
							$params['lobbyURL'] = isset($extra['extra']['t1_lobby_url']) ? $extra['extra']['t1_lobby_url'] : $this->CI->utils->getSystemUrl('player');
							$params['bankingURL']= $this->CI->utils->getSystemUrl('player');
							$params['username']= $loginInfo->login_name;
							$params['password']= $loginInfo->password;
							// $qry = http_build_query($params);
							// $url .= $qry;

							foreach ($params as $key => $value) {
								$url .= $key.'='.$value.'&';
							}

							$url=rtrim($url, '&');

							$params=[];
							// $params['username']= $loginInfo->login_name;
							// $params['password']= $loginInfo->password;

							//replace game coe and lang
							$url=str_replace('{lang}', $language, $url);
							$url=str_replace('{game_code}', $gameCode, $url);

							$urlForm=['post'=>true, 'url'=>$url, 'params'=> $params];
							list($form_html, $form_id)=$this->createHtmlForm($urlForm);
							$result['form_html']=$form_html;
							$result['form_id']=$form_id;
						} else {
							$mobile_params = $this->getSystemInfo('mg_mobile_rng_game_params');
							$url = $this->getSystemInfo('mg_mobile_rng_game_url');
							$params['applicationid'] = $mobile_params['applicationid'];
							$params['serverid'] = $mobile_params['serverid'];
							$params['gamename'] = $gameCode;
							$params['lobbyname'] = $this->rng_lobby_name;
							if($mode == 'fun'){
								$params['ispracticeplay'] = "true";
							}
							else{
								$params['username']= $loginInfo->login_name;
								$params['password']= $loginInfo->password;
							}
							$params['UL'] = $language;
							//https://redirect.contdelivery.com/Casino/Default.aspx?applicationid=163&serverid=3336&gamename=[GAMECODE]&lobbyname=goldentree&username=[USERNAME]&password=[PASSWORD]&ul=[LANGUAGECODE]
							//
							//https://redirect.contdelivery.com/Casino/Default.aspx?applicationid=163&serverid=3336&gamename=[GAMECODE]&lobbyname=goldentree&ispracticeplay=true&ul=[LANGUAGECODE]
						}


					} else {
						$url = $this->getSystemInfo('mg_mobile_live_game_url');
						//live launcher
						$params = $this->getSystemInfo('mg_mobile_live_game_params');
						$params['LoginName']= $loginInfo->login_name;
						$params['Password']= $loginInfo->password;
						$params['BetProfileID'] = $this->getSystemInfo('mg_betting_profile_id_for_add_account');
						$params['UL'] = $language;

						$urlForm=['post'=>true, 'url'=>$url, 'params'=> $params];
						list($form_html, $form_id)=$this->createHtmlForm($urlForm);
						$result['form_html']=$form_html;
						$result['form_id']=$form_id;
					}
				}else{

					if ($gameType == Game_api_mg::MG_RNGGAME) {
                        //slots
                        $params = $this->getSystemInfo('mg_rng_params');

                        $params["ul"] = $language;
                        $params["gameid"] = $gameCode;
                        $params['sEXT1'] = $loginInfo->login_name;
                        $params['sEXT2'] = $loginInfo->password;
                        if($mode == 'fun'){
                            $params['sEXT1'] = 'demo';
                            $params['sEXT2'] = 'demo';
                        }
                        $url = $this->getSystemInfo('mg_rng_game_url_prefix'); // 'https://igaminga.gameassists.co.uk/aurora/?';
                    }else {
						//live launcher
						//load info from config or api
						$params = $this->getSystemInfo('mg_live_params');
						$params['BetProfileID'] = $this->getSystemInfo('mg_betting_profile_id_for_add_account');
						$params['LoginName'] = $loginInfo->login_name;
						$params['Password'] = $loginInfo->password;
						$params['UL'] = $language;
						$url = $this->getSystemInfo('mg_live_game_url_prefix'); //'https://livegames.gameassists.co.uk/ETILandingPage/?';
					}
				}

				$qry = http_build_query($params);
				$url .= $qry;

				$result['url'] = $url;
				$result['success'] = true;
			} else {
				$result['success'] = false;
				$result['message'] = 'goto_game.error';
			}
		}

		return $result;
	}

	//for web api
	protected function getHttpHeaders($params) {
		$loginName = $this->getSystemInfo('mg_login_name');
		$pinCode = $this->getSystemInfo('mg_pin_code');

		$postJson = json_encode($params);

		return array(
			'Content-Type' => 'application/json',
			'Authorization' => 'Basic ' . base64_encode($loginName . ':' . $pinCode),
			'Content-Length' => strlen($postJson),
		);
	}

	protected function customHttpCall($ch, $params) {

		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);

		$postJson = json_encode($params);

		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postJson);
	}

	public function syncOriginalGameLogs($token) {
		$syncId = $this->getValueFromSyncInfo($token, 'syncId');
		$lastRowId = $this->getValueFromSyncInfo($token, 'lastRowId');

		$ignore_public_sync = $this->getValueFromSyncInfo($token, 'ignore_public_sync');

		if ($ignore_public_sync == true) {
			$this->CI->utils->debug_log('ignore public sync'); // ignore public sync
			return array('success' => true);
		}
		$result = $this->convertGameRecordsToFile($lastRowId, $syncId);

		return $result;
	}

	//TODO move to config_secret_local
	private $conversion_rate = 100;

	public function convertDBAmountToGame($amount) {
		return round($amount * $this->conversion_rate);
	}

	public function convertGameAmountToDB($amount) {
		return round(floatval(floatval($amount) / $this->conversion_rate), 2);
	}

	private function copyRowToDB($row, $responseResultId) {
		$display_name = $row->DisplayName;
		if (empty($display_name)) {
			$display_name = self::UNKNOWN_GAME_ID;
		}
		$result = array(
			'row_id' => $row->RowId,
			'account_number' => $row->AccountNumber,
			'display_name' => $display_name,
			'display_game_category' => $row->DisplayGameCategory,
			'session_id' => $row->SessionId,
			// 'game_end_time' => $this->convertGameTime($row->GameEndTime),
			'game_end_time' => $this->gameTimeToServerTime($row->GameEndTime),
			'total_wager' => $this->convertGameAmountToDB($row->TotalWager),
			'total_payout' => $this->convertGameAmountToDB($row->TotalPayout),
			'progressive_wage' => $row->ProgressiveWage,
			'iso_code' => $row->ISOCode,
			'game_platform' => $row->GamePlatform,
			'external_uniqueid' => $row->RowId,
			'response_result_id' => $responseResultId,
			'created_at' => $this->CI->utils->getNowForMysql(),
			'module_id' => $row->ModuleId,
			'client_id' => $row->ClientId,
			'transaction_id' => $row->TransactionId,
			'external_game_id' => $row->ModuleId."-".$row->ClientId,
		);
		$this->CI->mg_game_logs->syncToMGGameLogs($result);

	}

	public function getGameTimeToServerTime() {
		return '+8 hours';
	}

	public function getServerTimeToGameTime() {
		return '-8 hours';
	}

	private function syncToMGGameLogs($data) {
		$this->CI->mg_game_logs->syncToMGGameLogs($data);
	}

	private function getGameRecordPath() {
		return $this->getSystemInfo('mg_game_records_path');
	}

	public function syncMergeToGameLogs($token) {
		$dateTimeFrom = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$dateTimeTo = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
		$dateTimeFrom->modify($this->getDatetimeAdjust());

		$this->CI->utils->debug_log('dateTimeFrom', $dateTimeFrom, 'dateTimeTo', $dateTimeTo);
		$rlt = array('success' => true);
		$this->CI->load->model(['mg_game_logs']);

		$this->CI->utils->debug_log('==============> start merge', round(memory_get_usage()/(1024*1024), 2));

		if($this->new_sync_merge){
			return $this->newMGSyncMerge($token);
		} else {
			$qry = $this->CI->mg_game_logs->getQueryOfMGGameLogStatistics($dateTimeFrom->format('Y-m-d H:i:s'), $dateTimeTo->format('Y-m-d H:i:s'), true);
		}

		$this->CI->utils->debug_log('==============> after getQueryOfMGGameLogStatistics', round(memory_get_usage()/(1024*1024), 2));

		$cnt = 0;
		$cntOfInsert=0;
		$cntOfUpdate=0;
		$cntOfIgnore=0;
		if ($qry) {

			$this->CI->load->model(array('game_logs', 'game_description_model'));

			$unknownGame = $this->getUnknownGame();

			while($key=$qry->nextRowObject()){
				list($game_description_id, $game_type_id) = $this->getGameDescriptionInfo($key, $unknownGame);
				//search game name
				$username = strtolower($key->playername);
				$gameDate = new DateTime($key->game_end_time);
				$gameDateStr = $this->CI->utils->formatDateTimeForMysql($gameDate);

                $bet_amount     = $this->dBtoGameAmount($key->bet_amount);
                $result_amount  = $this->dBtoGameAmount($key->result_amount);

                //table is bet, trans_amount is real bet
				$extra = array('table' => $key->external_uniqueid,'trans_amount' => $bet_amount, 'sync_index' => $key->id); //add round

				$execType='insert';

				$this->syncGameLogs(
					$game_type_id,
					$game_description_id,
					$key->external_game_id,
					$key->game_type,
					$key->game,
					$key->player_id,
					$username,
					$bet_amount,
					$result_amount,
					null,
					null,
					0,
					0,
					$key->external_uniqueid,
					$gameDateStr,
					$gameDateStr,
					$key->response_result_id,
					1,
                	$extra,
                	null,
                	$execType
				);

				if($cnt % 1000 == 0){
					$this->CI->utils->debug_log('==============> cnt:'.$cnt, round(memory_get_usage()/(1024*1024), 2));
				}
				if($execType=='insert'){
					$cntOfInsert++;
				}else if($execType=='update'){
					$cntOfUpdate++;
				}else{
					$cntOfIgnore++;
				}

				unset($extra, $result_amount, $bet_amount, $gameDateStr, $gameDate, $username, $game_type_id, $game_description_id, $key);
			}
			unset($unknownGame);

			$qry->free_result();
		}

		unset($qry);

		$this->CI->utils->debug_log('==============> end merge', round(memory_get_usage()/(1024*1024), 2));

		$this->CI->utils->debug_log('syncMergeToGameLogs monitor :'.$cnt.' insert:'.$cntOfInsert.', update:'.$cntOfUpdate.', ignore:'.$cntOfIgnore);
		return $rlt;
	}

	private function getGameDescriptionInfo($row, $unknownGame) {

        if(empty($row->game_description_id)){
            $this->CI->load->model('game_type_model');

            if(strpos(strtolower($row->game_type), 'slot')){
                $query = "(game_platform_id = " . $this->getPlatformCode() . " and game_type like '%slot%')";
            }

            if(in_array($row->game_type, array('Baccarat', 'BlackJack', 'Roulette'))){
                $query = "(game_platform_id = " . $this->getPlatformCode() . " and game_type like '%live%')";
            }

            if(in_array($row->game_type, array('Video Poker Games'))){
                $query = "(game_platform_id = " . $this->getPlatformCode() . " and game_type like '%video poker%')";
            }

            if(in_array($row->game_type, array('Bingo','Soft game','Interactive Games'))){
                $query = "(game_platform_id = " . $this->getPlatformCode() . " and game_type like '%other%')";
            }

            if(in_array($row->game_type, array('Table Games','Table Poker', 'Power Poker Games', ))){
                $query = "(game_platform_id = " . $this->getPlatformCode() . " and game_type like '%table game%')";
            }

            if(!empty($query)){
                $game_type_details = $this->CI->game_type_model->getGameTypeList($query);
            }

            if(!empty($game_type_details[0])){
                $game_type_id = $game_type_details[0]['id'];
                $row->game_type = $game_type_details[0]['game_type'];
            }else{
                $row->game_type_id = $unknownGame->game_type_id;
                $row->game_type = $unknownGame->game_name;
            }
        }

		$externalGameId = $row->game;
		$extra = array('game_code' => $row->game,
			'moduleid' => $row->module_id, 'clientid' => $row->client_id);
		return $this->processUnknownGame(
			$row->game_description_id, $row->game_type_id,
			$row->game, $row->game_type, $externalGameId, $extra,
			$unknownGame);
	}

	private function getMGGameLogStatistics($dateTimeFrom, $dateTimeTo) {
		$this->CI->load->model('mg_game_logs');
		return $this->CI->mg_game_logs->getMGGameLogStatistics($dateTimeFrom, $dateTimeTo);
	}

	// protected function getTimeoutSecond() {
	// 	return 3600;
	// }

	public function convertGameRecordsToFile($lastRowId = null, $syncId = null) {
		$this->CI->benchmark->mark('mg_game_logs_start');
		$this->CI->load->model(array('external_system', 'mg_game_logs','original_game_logs_model'));

		$sys = $this->CI->external_system->getSystemById($this->getPlatformCode());

		if (empty($lastRowId)) {
			$lastRowId = $sys->last_sync_id;
		}
		if (empty($lastRowId)) {
			$lastRowId = $this->default_start_row_id;
		}

		$apiName = 'GetSpinBySpinData';
		$url = $this->getSystemInfo('mg_web_api_url') . '/' . $apiName;

		$success = true;
		$stop = false;

		$result = array('data_count'=> 0);
		while ($success && !$stop) {
			$params = array('LastRowId' => $lastRowId);

			$this->CI->utils->debug_log('apiName', $apiName, $params);
			list($header, $resultText, $statusCode, $statusText, $errCode, $error, $resultObj) = $this->httpCallApi($url, $params);

			$this->CI->utils->debug_log("http call", "header", count($header), "resultText", strlen($resultText), "statusCode",
					$statusCode, "statusText", $statusText, 'callerrCode', $errCode, 'callerr', $error);

			$success = !$this->isErrorCode($apiName, $params, $statusCode, $errCode, $error);

			$extra = $header;
			$responseResultId = $this->saveResponseResult($success, $apiName, $params,
				$resultText, $statusCode, $statusText, $extra, array('sync_id' => $syncId));

			if ($success) {
				$resultObj = json_decode($resultText);

				$statusOfResult = $resultObj->Status;
				$success = !$statusOfResult->ErrorCode;

				if (!$success) {
					$this->setResponseResultToError($responseResultId);
				} else {
					$gameRecords =   json_decode(json_encode($resultObj->Result), true); // convert multi dimentional array to object
					$countResult = count($gameRecords);

					$this->CI->utils->debug_log('get mg result', $countResult, 'responseResultId', $responseResultId);
					if ($countResult <= 0) {
						break;
					}
					// filter duplicate RowId with one api request
					$this->removeDuplicateUniqueID($gameRecords, 'RowId');

					// reprocess game records base on database field in mg_game_logs
					$this->processGameRecords($gameRecords,$responseResultId);

					// get last array (fetch last record for max row id
					$last_record = end(array_keys($gameRecords));
					$maxRowId = $gameRecords[$last_record]['row_id'];

					list($insertRows, $updateRows)=$this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
						self::ORIGINAL_LOGS_TABLE_NAME,		# original table logs
						$gameRecords,						# api record (format array)
						'external_uniqueid',				# unique field in api
						'external_uniqueid',				# unique field in mg_game_logs table
						self::MD5_FIELDS_FOR_ORIGINAL,
						'md5_sum',
						'id',
						self::MD5_FLOAT_AMOUNT_FIELDS
					);

					$this->CI->utils->debug_log('MG game apiafter process available rows', 'gamerecords ->',count($gameRecords),
							'insertrows->',count($insertRows), 'updaterows->',count($updateRows));

					if (!empty($insertRows)) {
						$result['data_count'] += $this->updateOrInsertOriginalGameLogs($insertRows, 'insert');
					}
					unset($insertRows);

					if (!empty($updateRows)) {
						$result['data_count'] += $this->updateOrInsertOriginalGameLogs($updateRows, 'update');
					}
					unset($updateRows);

					//$resultRows = $resultObj->Result;
					//list($availableRows, $maxRowId) = $this->CI->mg_game_logs->getAvailableRows($resultRows);
					//$this->CI->utils->debug_log('availableRows', count($availableRows), 'maxRowId', $maxRowId);
					//unset($resultRows);
					//foreach ($availableRows as $row) {
					//	$this->copyRowToDB($row, $responseResultId);
					//}

					//unset($availableRows);
					if ($maxRowId) {
						$this->CI->external_system->setLastSyncId($this->getPlatformCode(), $maxRowId);
						$lastRowId = $maxRowId;
					} else {
						break;
					}
				}
			}
		}
		$this->CI->benchmark->mark('mg_game_logs_stop');
		$this->CI->utils->debug_log('mg_logs_bench', $this->CI->benchmark->elapsed_time('mg_game_logs_start', 'mg_game_logs_stop'));

		$result['success'] = $success;
		return $result;
	}

	// remove duplicate unique id in one response
	public function removeDuplicateUniqueID(&$gameRecords, $rowId) {
		$tempArr = array_unique(array_column($gameRecords,$rowId));
		$gameRecords =  array_intersect_key($gameRecords, $tempArr);
	}

	public function processGameRecords(&$gameRecords,$responseResultId) {
		$preResult = array();
		foreach ($gameRecords as $index => $record) {
			$display_name = $record['DisplayName'];
			if (empty($display_name)) {
				$display_name = self::UNKNOWN_GAME_ID;
			}
			$preResult[$index]['row_id'] = $record['RowId'];
			$preResult[$index]['account_number'] = $record['AccountNumber'];
			$preResult[$index]['display_name'] = $display_name;
			$preResult[$index]['display_game_category'] = $record['DisplayGameCategory'];
			$preResult[$index]['session_id'] = $record['SessionId'];
			$preResult[$index]['game_end_time'] =  $this->gameTimeToServerTime($record['GameEndTime']);
			$preResult[$index]['total_wager'] =  $this->convertGameAmountToDB($record['TotalWager']);
			$preResult[$index]['total_payout'] =  $this->convertGameAmountToDB($record['TotalPayout']);
			$preResult[$index]['progressive_wage'] = $record['ProgressiveWage'];
			$preResult[$index]['iso_code'] = $record['ISOCode'];
			$preResult[$index]['game_platform'] = $record['GamePlatform'];
			$preResult[$index]['external_uniqueid'] = $record['RowId'];
			$preResult[$index]['response_result_id'] = $responseResultId;
			$preResult[$index]['created_at'] =  $this->CI->utils->getNowForMysql();
			$preResult[$index]['module_id'] = $record['ModuleId'];
			$preResult[$index]['client_id'] = $record['ClientId'];
			$preResult[$index]['transaction_id'] = $record['TransactionId'];
			$preResult[$index]['external_game_id'] = $record['ModuleId']."-".$record['ClientId'];
		}
		$gameRecords = $preResult;
	}

	private function updateOrInsertOriginalGameLogs($data, $queryType){
		$dataCount=0;
		if(!empty($data)){
			foreach ($data as $record) {
				if ($queryType == 'update') {
					$this->CI->original_game_logs_model->updateRowsToOriginal(self::ORIGINAL_LOGS_TABLE_NAME, $record);
				} else {
					unset($record['id']);
					$this->CI->original_game_logs_model->insertRowsToOriginal(self::ORIGINAL_LOGS_TABLE_NAME, $record);
				}
				$dataCount++;
				unset($record);
			}
		}
		return $dataCount;
	}

	public function batchQueryPlayerBalance($playerNames, $syncId = null) {
		$this->CI->benchmark->mark('mg_sync_balance_start');

		$success = false;
		$result = array();
		try {

			$this->CI->load->model(array('game_provider_auth', 'player_model'));
			if (empty($playerNames)) {
				$playerNames = $this->getAllGameUsernames();
			} else {
				//convert to game username
				foreach ($playerNames as &$username) {
					$username = $this->getGameUsernameByPlayerUsername($username);
				}
			}
			if (!empty($playerNames)) {

				$this->CI->utils->debug_log('params', count($playerNames));

				$split = $this->getSystemInfo('mg_batch_query_balance_split');
				$names = array_chunk($playerNames, $split);
				foreach ($names as $nameArr) {
					if (!empty($nameArr)) {
						//don't save response for balance
						$context = array(
							'callback_obj' => $this,
							'callback_method' => 'processResultForBatchQueryPlayerBalance',
							'dont_save_response_in_api' => $this->getConfig('dont_save_response_in_api'),
							'syncId' => $syncId,
						);

						$rlt = $this->callApi(self::API_batchQueryPlayerBalance,
							array("delimitedAccountNumbers" => implode(',', $nameArr)), $context);

						if ($rlt && $rlt['success']) {
							$success = true;
						}
					}
				}
			}
		} catch (\Exception $e) {
			$this->processError($e);
			$success = false;
		}
		$this->CI->benchmark->mark('mg_sync_balance_stop');
		$this->CI->utils->debug_log('mg_sync_balance_bench', $this->CI->benchmark->elapsed_time('mg_sync_balance_start', 'mg_sync_balance_stop'));

		return $this->returnResult($success, "balances", $result);

	}

	public function processResultForBatchQueryPlayerBalance($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultObj = $this->getResultObjFromParams($params);
		$success = isset($resultObj->GetAccountBalanceResult) && isset($resultObj->GetAccountBalanceResult->BalanceResult);
		$result = array(); // array('balances' => null);

		$cnt = 0;
		if ($success) {
			$rlt = $resultObj->GetAccountBalanceResult;
			$balRlt = $rlt->BalanceResult;
			$this->CI->utils->debug_log('type of balRlt', gettype($balRlt), 'cnt', count($balRlt), 'is succeed', isset($balRlt->IsSucceed));

			$isOneRow = isset($balRlt->IsSucceed) && isset($balRlt->AccountNumber) && isset($balRlt->Balance);

			if ($isOneRow) {
				$balRlt = array($balRlt);
			}
			foreach ($balRlt as $balResult) {
				if ($balResult && isset($balResult->IsSucceed)) {
					$success = $balResult->IsSucceed;
					if ($success) {
						//search account number
						// if ($balResult->AccountNumber == $accountNumber) {
						$gameUsername = $balResult->AccountNumber;
						$playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);

						if ($playerId) {
							$bal = floatval($balResult->Balance);
							$result["balances"][$playerId] = $bal;
							$cnt++;
						}
					}
				}
			}

		}
		$this->CI->utils->debug_log('sync balance', $cnt, 'success', $success);
		return array($success, $result);
	}

	public function useQueryAvailableBalance() {
		return true;
	}

	public function queryBetDetailLink($playerUsername, $betId = null, $extra = null) {
		$url = $this->getSystemInfo('mg_bet_detail_url');
		$params = $this->getSystemInfo('mg_bet_detail_params');
		$url .= 'applicationid=' . $params['applicationid'] . '&password=' . $extra['password'] . '&serverid=' . $params['serverid'] . '&username=' . $playerUsername . '&lang=' . $params['lang'] . '&timezone=' . $params['timezone'];
		return Array('success' => true, 'url' => $url);
	}

    public function isAllowedQueryTransactionWithoutId(){
        return true;
    }

    public function generatePromoLink($playerName,$extra = null) {
    	$is_mobile = $extra['is_mobile'];

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$gamePassword = $this->getPasswordByGameUsername($gameUsername);
		$url = $this->getSystemInfo('mg_promo_link');
		if($is_mobile){
			$serverid = $this->getSystemInfo('desktop_mg_promo_server_id');
		} else {
			$serverid = $this->getSystemInfo('mobile_mg_promo_server_id');
		}
		$url .= 'networkid=1&username='.$gameUsername.'&password='.$gamePassword.'&serverid='.$serverid;

        switch (@$extra['language']) {
            case 'en-us':
                	$lang = 'en'; # mobile and flash same code
                break;
            case 'zh-cn':
            	if($is_mobile){
                	$lang = 'zh-cn'; // chinese
            	}else{
            		$lang = 'zh';
            	}
                break;
            case 'id-id':
                $lang = 'en'; // indonesia
                break;
            case 'vi-vn':
                $lang = 'en'; // vietnamese
                break;
            case 'ko-kr':
                $lang = 'ko-kr'; // korean
                break;
            default:
                $lang = 'en'; // default as english
                break;
        }

 		return $url.'&lang='.$lang;
	}

	public function newMGSyncMerge($token){
    	$enabled_game_logs_unsettle=false;
        return $this->commonSyncMergeToGameLogs($token,
            $this,
            [$this, 'queryOriginalGameLogs'],
            [$this, 'makeParamsForInsertOrUpdateGameLogsRow'],
            [$this, 'preprocessOriginalRowForGameLogs'],
            $enabled_game_logs_unsettle);
    }

    public function makeParamsForInsertOrUpdateGameLogsRow(array $row){

    	//fix md5_sum
        if(empty($row['md5_sum'])){
        	//genereate md5 sum
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE,
            	self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
        }
        $extra = array('table' => $row['external_uniqueid'],'trans_amount' => $row['bet_amount'], 'sync_index' => $row['sync_index']); //add round
        return [
            'game_info' => [
                'game_type_id' => $row['game_type_id'],
                'game_description_id' => $row['game_description_id'],
                'game_code' => $row['game_code'],
                'game_type' => null,
                'game' => $row['game']
            ],
            'player_info' => [
                'player_id' => $row['player_id'],
                'player_username' => $row['player_username']
            ],
            'amount_info' => [
                'bet_amount' => $row['bet_amount'],
                'result_amount' => $row['result_amount'],
                'bet_for_cashback' => $row['bet_amount'],
                'real_betting_amount' => $row['bet_amount'],
                'win_amount' => null,
                'loss_amount' => null,
                'after_balance' => null
            ],
            'date_info' => [
                'start_at' => $row['start_at'],
                'end_at' => $row['end_at'],
                'bet_at' => $row['bet_at'],
                'updated_at' => $row['end_at']
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => Game_logs::STATUS_SETTLED,
            'additional_info' => [
                'has_both_side' => 0,
                'external_uniqueid' => $row['external_uniqueid'],
                'round_number' => $row['external_uniqueid'],
                'md5_sum' => $row['md5_sum'],
                'response_result_id' => $row['response_result_id'],
                'sync_index' => isset($row['id']) ? $row['id'] : null,
                'bet_type' => null
            ],
            'bet_details' => [""=>null],
            'extra' => $extra,
            //from exists game logs
            'game_logs_id'=>isset($row['game_logs_id']) ? $row['game_logs_id'] : null
        ];
    }

    public function preprocessOriginalRowForGameLogs(array &$row){

    	$this->CI->load->model(array('game_logs'));
    	$game_description_id = $row['game_description_id'];
        $game_type_id = $row['game_type_id'];

        if (empty($game_description_id)) {
            list($game_description_id, $game_type_id) = $this->newGetGameDescriptionInfo($row, $this->getUnknownGame());
        }
        $row['game_description_id']=$game_description_id;
        $row['game_type_id']=$game_type_id;
        $row['status']=Game_logs::STATUS_SETTLED;
        $row['flag']=Game_logs::FLAG_GAME;
        $row['updated_at']=$this->CI->utils->getTodayForMysql();
    }

    /**
     * queryOriginalGameLogs
     * @param  string $dateFrom
     * @param  string $dateTo
     * @param  bool   $use_bet_time
     * @return array
     */
    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time){

        $sqlTime='mg.game_end_time >= ? and mg.game_end_time <= ?';

		$sql = <<<EOD
SELECT mg.id as sync_index,
mg.row_id,
mg.game_end_time,
mg.account_number,
mg.account_number as player_username,
mg.total_payout-mg.total_wager as result_amount,
mg.total_wager as bet_amount,
mg.total_wager as real_bet_amount,
mg.game_end_time as start_at,
mg.game_end_time as end_at,
mg.game_end_time as bet_at,
mg.total_wager,
mg.total_payout,
mg.display_name,
mg.display_game_category,
mg.external_uniqueid,
mg.response_result_id,
mg.module_id,
mg.client_id,
mg.transaction_id,
mg.progressive_wage,
mg.iso_code,
mg.session_id,
mg.md5_sum,
concat(mg.module_id,"-",mg.client_id)  as game_code,
mg.display_name as game,
mg.external_game_id,

game_provider_auth.player_id,

gd.id as game_description_id,
gd.game_name as game_description_name,
gd.game_type_id

FROM mg_game_logs as mg
LEFT JOIN game_description as gd ON mg.external_game_id = gd.external_game_id AND gd.game_platform_id = ?
LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
JOIN game_provider_auth ON mg.account_number = game_provider_auth.login_name and game_provider_auth.game_provider_id=?
WHERE

{$sqlTime}

EOD;

        $params=[$this->getPlatformCode(), $this->getPlatformCode(),
        $dateFrom,$dateTo];

        $row = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);

    	return  $row;
    }

    private function newGetGameDescriptionInfo($row, $unknownGame) {
		$game_description_id = null;
		$game_type_id = null;
		if (isset($row['game_description_id'])) {
			$game_description_id = $row['game_description_id'];
			$game_type_id = $row['game_type_id'];
		}

		if(empty($game_description_id)){
			$gameDescId=$this->CI->game_description_model->processUnknownGame($this->getPlatformCode(),
				$unknownGame->game_type_id, $row['game'], $row['game_code']);
		}

		return [$game_description_id, $game_type_id];
	}

}

/*end of file*/