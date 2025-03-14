<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

/**
 * Defines general behavior of game API classes.
 *
 * General behaviors include:
 * * Getting platform code
 * * Generate URL
 * * Generate Security Token
 * * Create Player
 * * Check Player Information
 * * Change Password
 * * Block/Unblock Player
 * * Check Fund Transfer
 * * Deposit To game
 * * Withdraw from Game
 * * Login/Logout player
 * * Update Player's information
 * * Check Player Balance
 * * Check Player Daily Balance
 * * Check player's login status
 * * Check Game records
 * * Check Forward Game
 * * Synchronize Original Game Logs
 * * Get Ibc Game log statistics
 * * Get Game Description Information
 * Behaviors not implemented
 * * Login/Logout player
 * * Check total betting amount
 * * Check Transaction
 * * Batch check player balance
 *
 *
about IBC of lebo , I found they have another way to launch game:
To access Sportsbook with HTTPS (gsoft-ib.com) use below our primary domain.
https://mkt.gsoft-ib.com/Deposit_ProcessLogin.aspx?lang=en&g=sessiontoken
so just use g=<session token> to launch game, very easy.
one more thing , don't only use http for player domain , should think about what if https
 *
 * The functions implemented by child class:
 * * Populating game form parameters
 * * Handling callbacks
 *
 *
 * @see Redirect redirect to game page
 *
 * @category Game_platform
 * @version 1.8.10
 * @copyright 2013-2022 tot
 */
class Game_api_ibc extends Abstract_game_api {
	private $ibc_operator_code;
	private $ibc_private_key;
	private $ibc_operator_currency;
	private $ibc_api_url;
	private $ibc_odds_type;
	private $ibc_max_transfer;
	private $ibc_min_transfer;
	private $ibc_game_url;

	const LOST_AND_FOUND_MAX_DAYS_BACK = 7; # Query lostAndFound for records max 7 days back
	const START_PAGE = 0;
	const ITEM_PER_PAGE = 500;
	const STATUS_SUCCESS = '0';
	const DEPOSIT_TRANSACTION = 1;
	const WITHDRAW_TRANSACTION = 0;

	const FUND_TRANSFER_STATUS_CODE_APPROVED = 0;
	const FUND_TRANSFER_STATUS_CODE_FAILED = 1;
	const FUND_TRANSFER_STATUS_CODE_PENDING = 2;

	const SPORTSBOOK_GAME = 1;
	const LIVECASINO_GAME = 2;
	const CASINO_GAME = 3;
	const MOBILE_WEB = 4;

	const DEFAULT_ODDS_TYPE='2';
	const DEFAULT_O_TYPE='2';

	const ODDS_FORMAT = [1 => 'eu', 2 => 'hk'];
	const API_confirmMemberBetSetting = "ConfirmMemberBetSetting";
	const API_getMemberBetSetting = "GetMemberBetSetting";

	const URI_MAP = array(
		self::API_createPlayer => 'CreateMember',
		self::API_login => 'Login',
		self::API_logout => 'Logout',
		self::API_queryPlayerBalance => 'CheckUserBalance',
		self::API_depositToGame => 'FundTransfer',
		self::API_withdrawFromGame => 'FundTransfer',
		self::API_checkFundTransfer => 'CheckFundTransfer',
		self::API_syncGameRecords => 'GetSportBettingDetail',
		self::API_batchQueryPlayerBalance => 'CheckUserBalance',
		self::API_updatePlayerInfo => 'UpdateMember',
		self::API_syncLostAndFound => 'GetSportBettingDetail',
		self::API_isPlayerExist => 'CheckUserBalance',
		self::API_setMemberBetSetting => "PrepareMemberBetSetting",
		self::API_confirmMemberBetSetting => "ConfirmMemberBetSetting",
		self::API_getMemberBetSetting => "GetMemberBetSetting",
	);

	const CLIENT_TYPE = array("web" => 0, "ios" => 1, "android" => 2);

	# Don't ignore on refresh
	const DEFAULT_IGNORED_0_ON_REFRESH = FALSE;

	public function __construct() {
		parent::__construct();
		$this->ibc_operator_code = $this->getSystemInfo('ibc_operator_code');
		$this->ibc_private_key = $this->getSystemInfo('ibc_private_key');
		$this->ibc_api_url = $this->getSystemInfo('ibc_api_url');
		$this->ibc_odds_type = $this->getSystemInfo('ibc_odds_type', self::DEFAULT_ODDS_TYPE);
		$this->ibc_max_transfer = $this->getSystemInfo('ibc_max_transfer', 99999999);
		$this->ibc_min_transfer = $this->getSystemInfo('ibc_min_transfer');
		$this->ibc_game_url = $this->getSystemInfo('ibc_game_url');
		$this->ibc_mobile_demo_game_url = $this->getSystemInfo('ibc_mobile_demo_game_url',$this->ibc_game_url);
		$this->enable_mobile_demo_game = $this->getSystemInfo('enable_mobile_demo_game',false);
		$this->ibc_demo_account = $this->getSystemInfo('ibc_demo_account');
		$this->ibc_mobile_game_url = $this->getSystemInfo('ibc_mobile_game_url');
		$this->ibc_web_skin_type = $this->getSystemInfo('ibc_web_skin_type','3');
		$this->ibc_bet_setting = $this->getSystemInfo('ibc_bet_setting');

		$this->o_type=$this->getSystemInfo('o_type', self::DEFAULT_O_TYPE);

		$this->locked_ibc_game_url=$this->CI->utils->getConfig('locked_ibc_game_url');
		if(!empty($this->locked_ibc_game_url)){
			$this->ibc_game_url=$this->locked_ibc_game_url;
		}

		$this->ibc_use_g_param_to_replace_cookies=$this->getSystemInfo('ibc_use_g_param_to_replace_cookies', true);

		$this->max_payout_per_match_multiplier = $this->getSystemInfo('max_payout_per_match_multiplier', 8);		
	}

	public function getPlatformCode() {
		return IBC_API;
	}

	public function generateUrl($apiName, $params) {
		$apiUri = self::URI_MAP[$apiName];
		return $url = $this->ibc_api_url . "/" . $apiUri . "?" . http_build_query($params);
	}

	private function generateSecurityToken($str) {
		return strtoupper(md5($str));
	}

	public function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null) {
		return array(false, null);
	}

	public function processResultBoolean($responseResultId, $resultArr, $playerName = null) {
		$success = !empty($resultArr) && $resultArr['error_code'] == self::STATUS_SUCCESS;
		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('IBC got error', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
		}
		return $success;
	}

	private function formatYMDHis($dateTimeStr) {
		$d = new Datetime($dateTimeStr);
		return $d->format('Y-m-d H:i:s');
	}

	private function getYmdHisForRequestDateTime() {
		return $this->formatYMDHis($this->serverTimeToGameTime(new DateTime()));
	}

	//===start createPlayer=====================================================================================
	public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
		parent::createPlayer($playerName, $playerId, $password, $email, $extra);
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForCreatePlayer',
			'playerName' => $playerName,
			'playerId' => $playerId,
		);

		$params = array(
			"OpCode" => $this->ibc_operator_code,
			"PlayerName" => $gameUsername,
			"OddsType" => $this->ibc_odds_type,
			"MaxTransfer" => $this->ibc_max_transfer,
			"MinTransfer" => $this->ibc_min_transfer,
		);
		$securityToken = $this->generateSecurityToken($this->ibc_private_key . "/api/CreateMember?" . http_build_query($params));

		$data = array(
			"SecurityToken" => $securityToken,
			"OpCode" => $this->ibc_operator_code,
			"PlayerName" => $gameUsername,
			"OddsType" => $this->ibc_odds_type,
			"MaxTransfer" => $this->ibc_max_transfer,
			"MinTransfer" => $this->ibc_min_transfer,
		);

		return $this->callApi(self::API_createPlayer, $data, $context);
	}

	public function processResultForCreatePlayer($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);
		if(!$success){
			$this->setMemberBetSetting($playerName);//set bet limit
		}
		return array($success, $resultJson);
	}

	//===end createPlayer=====================================================================================

	//===start queryPlayerInfo=====================================================================================
	public function queryPlayerInfo($playerName) {
		// $playerName = $this->getGameUsernameByPlayerUsername($playerName);
		// return array("success" => true);
		return $this->returnUnimplemented();
	}
	//===end queryPlayerInfo=====================================================================================

	//===start changePassword=====================================================================================
	public function changePassword($playerName, $oldPassword, $newPassword) {
		// $playerName = $this->getGameUsernameByPlayerUsername($playerName);
		// return array("success" => true);
		return $this->returnUnimplemented();
	}
	//===end changePassword=====================================================================================

	//===start depositToGame=====================================================================================
	public function depositToGame($playerName, $amount, $transfer_secure_id=null) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$transId = !empty($transfer_secure_id) ? $transfer_secure_id : uniqid();
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForDepositToGame',
			'playerName' => $playerName,
			'gameUsername'=>$gameUsername,
			'amount' => $amount,
			'transId' => $transId,
			'external_transaction_id' => $transId,
		);

		$params = array(
			"OpCode" => $this->ibc_operator_code,
			"PlayerName" => $gameUsername,
			"OpTransId" => $transId,
			"Amount" => $amount,
			"Direction" => self::DEPOSIT_TRANSACTION,
		);
		$securityToken = $this->generateSecurityToken($this->ibc_private_key . "/api/FundTransfer?" . http_build_query($params));

		$params['SecurityToken']=$securityToken;

		return $this->callApi(self::API_depositToGame, $params, $context);
	}

	public function processResultForDepositToGame($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$amount = $this->getVariableFromContext($params, 'amount');
		$transId = $this->getVariableFromContext($params, 'transId');
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');

		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);
		$result = array('response_result_id' => $responseResultId, 'external_transaction_id'=>$external_transaction_id,
			'reason_id'=>self::REASON_UNKNOWN, 'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN);
		if ($success) {
			switch ($resultJson['Data']['status']) {
				case self::FUND_TRANSFER_STATUS_CODE_FAILED:
					$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_DECLINED;
					$result['reason_id']=self::REASON_FAILED_FROM_API;
					$success=false;
					break;

				case self::FUND_TRANSFER_STATUS_CODE_APPROVED:
					$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
					break;

				case self::FUND_TRANSFER_STATUS_CODE_PENDING:
					$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_PROCESSING;
					$rltQry=$this->queryTransaction($result["external_transaction_id"], ['playerName'=>$playerName]);
					if($rltQry && $rltQry['success']){
						if($rltQry['Data']['status']==self::FUND_TRANSFER_STATUS_CODE_FAILED){
							$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_DECLINED;
							$success=false;
						}elseif($rltQry['Data']['status']==self::FUND_TRANSFER_STATUS_CODE_APPROVED){
							$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
						}else{
							//still pending
							$success=true;
						}
					}else{
						//still pending
						$success=true;
					}
					break;

				default:
					$success=true;
					$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_UNKNOWN;
					break;
			}

			$result['didnot_insert_game_logs']=true;
			$result['after_balance']=floatval(@$resultJson['Data']['after_amount']);
		} else {
			$error_code=@$resultJson['error_code'];
			if($error_code=='11000'){
				$success=true;
				$result['reason_id']=self::REASON_API_MAINTAINING;
				$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_UNKNOWN;

			}else{
				// $result["external_transaction_id"] = $external_transaction_id;
				$result["transfer_status"]=self::COMMON_TRANSACTION_STATUS_UNKNOWN;
                $result['reason_id'] = $this->getReason($error_code);
			}
		}
		return array($success, $result);
	}

    private function getReason($error_code){
        switch ($error_code) {
            case '11000':
            case '12999':
                return self::REASON_API_MAINTAINING;
                break;
            case '12000':
            case '13000':
            case '15000':
                return self::REASON_GAME_PROVIDER_INTERNAL_PROBLEM;
                break;
            case '18005':
                return self::REASON_NOT_FOUND_PLAYER;
                break;
            case '25998':
                return self::REASON_TRANSFER_AMOUNT_IS_TOO_LOW;
                break;
            default:
                return self::REASON_FAILED_FROM_API;
        }
    }

	//===end depositToGame=====================================================================================

	//===start withdrawFromGame=====================================================================================
	public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$transId = !empty($transfer_secure_id) ? $transfer_secure_id : uniqid();
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForWithdrawToGame',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername,
			'amount' => $amount,
			'transId' => $transId,
			'external_transaction_id' => $transId,
		);

		$params = array(
			"OpCode" => $this->ibc_operator_code,
			"PlayerName" => $gameUsername,
			"OpTransId" => $transId,
			"Amount" => $amount,
			"Direction" => self::WITHDRAW_TRANSACTION,
		);
		$securityToken = $this->generateSecurityToken($this->ibc_private_key . "/api/FundTransfer?" . http_build_query($params));

		$params['SecurityToken']=$securityToken;

		return $this->callApi(self::API_withdrawFromGame, $params, $context);
	}

	public function processResultForWithdrawToGame($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$amount = $this->getVariableFromContext($params, 'amount');
		$transId = $this->getVariableFromContext($params, 'transId');
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');

		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);
		$result = array('response_result_id' => $responseResultId, 'reason_id'=>self::REASON_UNKNOWN);
		if ($success) {
			//get current sub wallet balance
			// $playerBalance = $this->queryPlayerBalance($playerName);

			//for sub wallet
			// $afterBalance = $playerBalance['balance'];

			//external_transaction_id means game api system transaction id , not our
			$result["external_transaction_id"] = $external_transaction_id; //$resultJson['Data']['trans_id'];
			// $result["currentplayerbalance"] = $afterBalance;
			// $result["transId"] = $transId;
			// $result["userNotFound"] = false;
			// $result["transfer_status"]=self::COMMON_TRANSACTION_STATUS_APPROVED;

			switch ($resultJson['Data']['status']) {
				case self::FUND_TRANSFER_STATUS_CODE_FAILED:
					$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_DECLINED;
					$result['reason_id']=self::REASON_FAILED_FROM_API;
					$success=false;
					break;

				case self::FUND_TRANSFER_STATUS_CODE_APPROVED:
					$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
					$success=true;
					break;

				case self::FUND_TRANSFER_STATUS_CODE_PENDING:
					$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_PROCESSING;
					//try search again
					$rltQry=$this->queryTransaction($result["external_transaction_id"], ['playerName'=>$playerName]);
					if($rltQry && $rltQry['success']){
						if($rltQry['Data']['status']==self::FUND_TRANSFER_STATUS_CODE_FAILED){
							$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_DECLINED;
							$result['reason_id']=self::REASON_FAILED_FROM_API;
							$success=false;
						}elseif($rltQry['Data']['status']==self::FUND_TRANSFER_STATUS_CODE_APPROVED){
							$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
							$success=true;
						}else{
							$result['reason_id']=self::REASON_FAILED_FROM_API;
							//still pending
							$success=false;
						}
					}else{
						$result['reason_id']=self::REASON_FAILED_FROM_API;
						//still pending
						$success=false;
					}
					break;

				default:
					$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_UNKNOWN;
					$result['reason_id']=self::REASON_FAILED_FROM_API;
					$success=false;
					break;
			}

			$result['after_balance']=floatval(@$resultJson['Data']['after_amount']);
			$result['didnot_insert_game_logs']=true;
		} else {
			// $result["userNotFound"] = true;
            $error_code=@$resultJson['error_code'];
			$result["external_transaction_id"] = $external_transaction_id;
			$result["transfer_status"]=self::COMMON_TRANSACTION_STATUS_UNKNOWN;
            $result['reason_id'] = $this->getReason($error_code);
		}

		return array($success, $result);
	}

	//===end withdrawFromGame=====================================================================================

	//===start login=====================================================================================
	public function login($playerName, $password = null) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$this->CI->utils->debug_log('IBC login account: ', $gameUsername);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForOperatorLogin',
			'playerName' => $playerName,
		);

		$params = array(
			"OpCode" => $this->ibc_operator_code,
			"PlayerName" => $gameUsername,
		);
		$securityToken = $this->generateSecurityToken($this->ibc_private_key . "/api/Login?" . http_build_query($params));

		$data = array(
			"OpCode" => $this->ibc_operator_code,
			"PlayerName" => $gameUsername,
			"SecurityToken" => $securityToken,
		);

		return $this->callApi(self::API_login, $data, $context);
	}

	public function processResultForOperatorLogin($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultJson);
		$this->CI->utils->debug_log('ibc response: ', $success, $resultJson);
		return array($success, $resultJson);
	}

	//===end login=====================================================================================

	//===start logout=====================================================================================
	public function logout($playerName, $password = null) {
		// $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		// $resultJson = $this->getResultJsonFromParams($params);

		// $success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);
		// $result = array();
		// return array(true, null);
		return $this->returnUnimplemented();
	}

	//===end logout=====================================================================================

	//===start updatePlayerInfo=====================================================================================
	public function updatePlayerInfo($playerName, $infos) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForUpdatePlayerInfo',
			'playerName' => $playerName,
		);

		$params = array(
			"OpCode" => $this->ibc_operator_code,
			"PlayerName" => $gameUsername,
			"OddsType" => $this->ibc_odds_type,
			"MaxTransfer" => $this->ibc_max_transfer,
			"MinTransfer" => $this->ibc_min_transfer,
		);
		$securityToken = $this->generateSecurityToken($this->ibc_private_key . "/api/UpdateMember?" . http_build_query($params));

		$data = array(
			"SecurityToken" => $securityToken,
			"OpCode" => $this->ibc_operator_code,
			"PlayerName" => $gameUsername,
			"OddsType" => $this->ibc_odds_type,
			"MaxTransfer" => $this->ibc_max_transfer,
			"MinTransfer" => $this->ibc_min_transfer,
		);

		return $this->callApi(self::API_updatePlayerInfo, $data, $context);
	}

	public function processResultForUpdatePlayerInfo($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);
		return array($success, $resultJson);
	}

	//===end updatePlayerInfo=====================================================================================

	//===start queryPlayerBalance=====================================================================================
	public function queryPlayerBalance($playerName) {
		$playerInfo = $this->getPlayerInfoByUsername($playerName);
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryPlayerBalance',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername,
		);

		$params = array(
			"OpCode" => $this->ibc_operator_code,
			"PlayerName" => $gameUsername,
		);
		$securityToken = $this->generateSecurityToken($this->ibc_private_key . "/api/CheckUserBalance?" . http_build_query($params));

		$data = array(
			"OpCode" => $this->ibc_operator_code,
			"PlayerName" => $gameUsername,
			"SecurityToken" => $securityToken,
		);

		return $this->callApi(self::API_queryPlayerBalance, $data, $context);
	}

	public function processResultForQueryPlayerBalance($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');

		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);
		$result = array();
		$resultArr = !empty($resultJson) && isset($resultJson['Data']) ? reset($resultJson['Data']) : array();
		if ($success && isset($resultArr['balance'])) {
			$result['exists'] = true;
			$result["balance"] = floatval($resultArr['balance']);
			// $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
			// $this->CI->utils->debug_log('query balance playerId', $playerId, 'gameUsername',
			// 	$gameUsername, 'balance');
			// if ($playerId) {
				//should update database
				// $this->updatePlayerSubwalletBalance($playerId, $result["balance"]);
			// } else {
			// 	log_message('error', 'cannot get player id from ' . $gameUsername . ' getPlayerIdInGameProviderAuth');
			// }
		} else {
			$success = false;
		}
		return array($success, $result);
	}
	//===end queryPlayerBalance=====================================================================================

	//===start checkLoginStatus=====================================================================================
	public function checkLoginStatus($playerName) {
		return $this->returnUnimplemented();
	}
	//===end checkLoginStatus=====================================================================================

	//===start queryTransaction=====================================================================================
	public function queryTransaction($transactionId, $extra) {

		$playerName=$extra['playerName'];
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryTransaction',
			'gameUsername' => $gameUsername,
			'playerName'=>$playerName,
			'external_transaction_id' => $transactionId,
		);

		$params = array(
			"OpCode" => $this->ibc_operator_code,
			"PlayerName" => $gameUsername,
			"OpTransId" => $transactionId,
		);
		$securityToken = $this->generateSecurityToken($this->ibc_private_key . "/api/CheckFundTransfer?" . http_build_query($params));

		$params['SecurityToken']=$securityToken;

		return $this->callApi(self::API_checkFundTransfer, $params, $context);
	}

	public function processResultForQueryTransaction($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$transId = $this->getVariableFromContext($params, 'external_transaction_id');

		$success = !empty($resultJson) && isset($resultJson['error_code']); // $this->processResultBoolean($responseResultId, $resultJson, $playerName);
		$result = array('response_result_id'=>$responseResultId);

		if ($success) {

			if($resultJson['error_code']==2){
				//doesn't exist
				$result['status']=self::COMMON_TRANSACTION_STATUS_DECLINED;
			}else{
				switch ($resultJson['Data']['status']) {
					case self::FUND_TRANSFER_STATUS_CODE_FAILED:
						$result['status']=self::COMMON_TRANSACTION_STATUS_DECLINED;
						break;

					case self::FUND_TRANSFER_STATUS_CODE_APPROVED:
						$result['status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
						break;

					case self::FUND_TRANSFER_STATUS_CODE_PENDING:
						$result['status']=self::COMMON_TRANSACTION_STATUS_PROCESSING;
						break;

					default:
						$result['status']=self::COMMON_TRANSACTION_STATUS_UNKNOWN;
						break;
				}
			}

			$result['extra_notes']=@$resultJson['message'];
			$result["external_transaction_id"] = $transId;

		} else {
			$result['status']=self::COMMON_TRANSACTION_STATUS_UNKNOWN;
			$result['external_transaction_id']=$transId;
		}

		return array($success, $result);
	}

	//===end queryTransaction=====================================================================================

	public function generateGotoUri($playerName, $extra){

		return '/player_center/goto_ibc/1';

	}

	//===start queryForwardGame=====================================================================================
	public function queryForwardGame($playerName, $extra) {
		$this->CI->utils->debug_log('IBC queryForwardGame login playerName: ', $playerName);
		$this->CI->utils->debug_log('IBC queryForwardGame login demoAccount: ', $this->ibc_demo_account);
		$is_mobile = isset($extra['is_mobile'])?$extra['is_mobile']:false;
		# mobile demo
		if ($extra['gameType'] == self::SPORTSBOOK_GAME && $is_mobile && !$playerName && $this->enable_mobile_demo_game) {
			// return array('success'=>true,'url' => $this->ibc_mobile_demo_game_url.'?lang='.$extra['language']);
			$mobile_home_url = $this->utils->getSystemUrl('m');
			$mobile_login_url = $this->utils->getPlayerLoginUrl();
			$mobile_registration_url = $this->utils->getSystemUrl('m','/player_center/iframe_register');
			//sample
			//http://i.gsoft888.net/vender.aspx?lang=en&OType=1&homeUrl=http://localhost/1/&singupUrl=http://localhost/2/&LoginUrl=http://localhost/3/
			return array('success'=>true,'url' => $this->ibc_mobile_demo_game_url.'?lang='.$extra['language'].'&Otype=1'.'&homeUrl='.$mobile_home_url.'&singupUrl='.$mobile_registration_url.'&LoginUrl='.$mobile_login_url);
		}

		if(empty($this->locked_ibc_game_url)){
			//if not locked ibc
			$nextUrl=$this->generateGotoUri($playerName, $extra);
			$result=$this->forwardToWhiteDomain($playerName, $nextUrl);
			if($result['success']){
				return $result;
			}
		}else{
			$this->ibc_game_url=$this->locked_ibc_game_url;
		}

		if (!$playerName) {
			return array('success'=>true, 'sessionToken' => null, 'url' => $this->ibc_game_url . "?WebSkinType=" . $this->ibc_web_skin_type , 'is_mobile' => $extra['is_mobile']);
		} else {
			$result = $this->login($playerName);
		}

		$process_login = "/Deposit_ProcessLogin.aspx";
		if($extra['is_mobile']){
			$params = array(
						"lang" 	=> $extra['language'],
						"st"	=> $result['sessionToken'],
						"WebSkinType"	=> $this->ibc_web_skin_type,
					);
			$game_url = $this->ibc_mobile_game_url.$process_login;
		} else {
			$params = array(
						"lang" 	=> $extra['language'],
						"WebSkinType"	=> $this->ibc_web_skin_type,
					);
			if($this->ibc_use_g_param_to_replace_cookies){
				$params['g']=$result['sessionToken'];
			}
			$game_url = $this->ibc_game_url.$process_login;
		}

		if(!empty($this->o_type)){
			$params['OType']=$this->o_type;
		}

		if ($extra['gameType'] == self::SPORTSBOOK_GAME) {
			$params = http_build_query($params);
		} elseif ($extra['gameType'] == self::LIVECASINO_GAME) {
			$params['act'] = (!$extra['is_mobile']) ? "livecasino" : NULL;
			$params = http_build_query($params);
		} elseif ($extra['gameType'] == self::CASINO_GAME) {
			(!$extra['is_mobile']) ? $params['act'] = "casino" : $params['types'] = "CL";
			$params = http_build_query($params);
		}
		$url = $game_url . "?" . $params;
		$result = array('success'=>true, 'sessionToken' => $result['sessionToken'], 'url' => $url);

		// $this->CI->utils->debug_log('IBC queryForwardGame login result: ', $result);
		// if ($extra['gameType'] == self::SPORTSBOOK_GAME) {
		// 	$url = $game_url . '/Deposit_ProcessLogin.aspx?lang=' . $extra['language'] . (($extra['is_mobile']) ? "&st=".$result['sessionToken'] : "");
		// } elseif ($extra['gameType'] == self::LIVECASINO_GAME) {
		// 	$url = $game_url . '/Deposit_ProcessLogin.aspx?lang=' . $extra['language'] . '&act=livecasino';
		// } elseif ($extra['gameType'] == self::CASINO_GAME) {
		// 	$url = $game_url . '/Deposit_ProcessLogin.aspx?lang=' . $extra['language'] . (($extra['is_mobile']) ? "&types=CL"."&st=".$result['sessionToken'] : "&act=casino");
		// }
		// $result = array('sessionToken' => $result['sessionToken'], 'url' => $url);
		return $result;
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
		$this->CI->utils->debug_log('startDate', $startDate, 'endDate', $endDate);
		$startDate = $startDate->format('Y-m-d H:i:s');
		$endDate   = $endDate->format('Y-m-d H:i:s');
		$this->CI->utils->loopDateTimeStartEnd($startDate, $endDate, '+12 hours', function($startDate, $endDate) {
			$startDate = $startDate->format('Y-m-d H:i:s');
			$endDate = $endDate->format('Y-m-d H:i:s');
			$context = array(
				'callback_obj' => $this,
				'callback_method' => 'processResultForSyncGameRecords',
			);
			$encodedStartDate =  strtolower(urlencode($startDate));
			$encodedEndDate   =  strtolower(urlencode($endDate));
			$dateParams = array(
				"StartTime" => $encodedStartDate,
				"EndTime" => $encodedEndDate,
			);
			$params = array(
				"OpCode" => $this->ibc_operator_code,
			);
			$dateTime = '&StartTime='.$dateParams['StartTime'].'&EndTime='.$dateParams['EndTime'];
			$orignalText = $this->ibc_private_key . "/api/GetSportBettingDetail?" . http_build_query($params).$dateTime;
			$securityToken = $this->generateSecurityToken($orignalText);

			$data = array(
				"OpCode" => $this->ibc_operator_code,
				"StartTime" => $startDate,
				"EndTime" => $endDate,
				"SecurityToken" => $securityToken,
			);
			return $this->callApi(self::API_syncGameRecords, $data, $context);
		});

		return ['success'=>true];
	}

	public function processResultForSyncGameRecords($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$this->CI->load->model(array('ibcgame_game_logs', 'external_system', 'game_description_model', 'game_provider_auth'));
		$result = array();
		$success = $this->processResultBoolean($responseResultId, $resultJson);

		if ($success) {
			$gameRecords = $resultJson['Data'];

			if (empty($gameRecords) || !is_array($gameRecords)) {
				$this->CI->utils->debug_log('wrong game records', $gameRecords);
			}
			// echo"<pre>";
			// print_r($gameRecords);exit();
			if (!empty($gameRecords)) {
				$this->CI->utils->debug_log('gameRecords', count($gameRecords));
				$transIdArr=[];
				$sportArr=[];
				$playerNameArr=[];
				foreach ($gameRecords as $record) {
					$transIdArr[]=$record['TransId'];
					$sportArr[]=isset($record['SportType']) ? $record['SportType'] : 0;
					$playerNameArr[]=$record['PlayerName'];
				}
				$sportArr=array_unique($sportArr);

				$gameDetails = $this->CI->game_description_model->getGameDescriptionListByGamePlatformIdAndGameCodeArray($this->getPlatformCode(),$sportArr);
				$unknownGame = $this->getUnknownGame();
				$playerMap= $this->CI->game_provider_auth->getPlayerMapFromLoginNameArr($this->getPlatformCode(), $playerNameArr);

				$mapTransId=$this->CI->ibcgame_game_logs->getExistsTransIdArr($transIdArr);

				$this->CI->utils->debug_log('start processing game records', count($gameRecords));

				foreach ($gameRecords as $record) {

					$ibcGameData = $this->getGameDataFrom($record, $responseResultId);

					// $isExists = $this->CI->ibcgame_game_logs->isRowIdAlreadyExists($ibcGameData['trans_id']);
					if (in_array($ibcGameData['trans_id'], $mapTransId)) {
						$this->CI->ibcgame_game_logs->updateGameLogs($ibcGameData);
					} else {
						$this->CI->ibcgame_game_logs->insertIbcGameLogs($ibcGameData);
					}
					// $this->syncMergeToGameLogsInstantly($ibcGameData, $gameDetails, $unknownGame, $playerMap);
				}

				$this->CI->utils->debug_log('end processing game records', count($gameRecords));
			}
		}

		return array($success, $result);
	}

	public function syncMergeToGameLogsInstantly($ibcData,  $gameDetails, $unknownGame, $playerMap) {
		$this->CI->load->model(array('game_logs'));
		// $this->CI->load->model('game_description_model');
		$gameCode = $ibcData['sport_type'];
		// $gameDetails = $this->CI->game_description_model->getGameDescriptionByGamePlatformIdAndGameCode(IBC_API,$gameCode);
		// $unknownGame = $this->getUnknownGame();
		// $this->CI->utils->debug_log('playerMap', $playerMap);

		$gd= isset($gameDetails[$gameCode]) ? $gameDetails[$gameCode] : $unknownGame;
		$player_id = isset($playerMap[$ibcData['player_name']]) ? $playerMap[$ibcData['player_name']] : null ; //$this->getPlayerIdInGameProviderAuth($ibcData['player_name']);
		if (empty($player_id)) {
			$this->CI->utils->error_log('lost player in ibc', $ibcData);
			return;
		}
		// $player = $this->CI->player_model->getPlayerById($player_id);
		$player_username = $ibcData['player_name']; //$player->username;

		$status = $this->getGameRecordsStatus($ibcData['ticket_status']);
		$bet_amount = ($status == Game_logs::STATUS_REFUND) ? 0 : $this->gameAmountToDB($ibcData['stake']);
		$betDetails = $this->processGameBetDetail($ibcData);
		$extra = array(
			'table'=>$ibcData['trans_id'],
			'trans_amount'	=> 	$bet_amount,
			'status'		=> 	($status == Game_logs::STATUS_REFUND) ? Game_logs::STATUS_SETTLED : $status ,
			'bet_details'	=> $betDetails,
			'odds' 			=>  $ibcData['odds'],
			'note'			=> ($status == Game_logs::STATUS_REFUND) ? "Refund ". $this->gameAmountToDB($ibcData['stake']) : NULL
		);

		if(array_key_exists('odds_type', $ibcData)) {
			$odds_format = array_key_exists($ibcData['odds_type'], self::ODDS_FORMAT) ? self::ODDS_FORMAT[$ibcData['odds_type']] : null;
			if(!empty($odds_format)){
				$extra['odds_type']=$odds_format; # eu / hk
			}
		}

		$this->syncGameLogs(
			$gd->game_type_id,
			$gd->id,
			$gd->game_code,
			$gameCode,
			$gameCode,
			$player_id,
			$player_username,
			$bet_amount,
			$ibcData['winlose_amount'],
			null, # win_amount
			null, # loss_amount
			null,//$gameplay_sbtech_game_logs['after_balance'], # after_balance
			0, # has_both_side
			$ibcData['external_uniqueid'],
			$ibcData['transaction_time'],  //start
			$ibcData['transaction_time'],  //end
			$ibcData['response_result_id'],
			Game_logs::FLAG_GAME,
			$extra
		);
	}

	public function processGameBetDetail($rowArray){
		if(!is_array($rowArray)) {
			$rowArray = json_decode(json_encode($rowArray), true);
		}

		$details = array(
			"Bet ID" => $rowArray['trans_id'],
			"League" => $rowArray['league_name'],
			"Match ID" => @$rowArray['match_id'],
			"Odds"	=> $rowArray['odds'],
			"Status" => $rowArray['ticket_status'],
			"Away Team" =>$rowArray['away_id_name'],
			"Home Team" =>$rowArray['home_id_name'],
			"Bet Team" =>$rowArray['bet_team']
		);
		return json_encode($details);

	}

	public function getGameDataFrom($record, $responseResultId){

		$ibcGameData = array(
			'trans_id' => $record['TransId'],
			'player_name' => $record['PlayerName'],
			'transaction_time' => $this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($record['TransactionTime']))),
			'match_id' => $record['MatchId'],
			'league_id' => $record['LeagueId'],
			'league_name' => $record['LeagueName'],
			'sport_type' => isset($record['SportType']) ? $record['SportType'] : 0,
			'away_id' => $record['AwayId'],
			'away_id_name' => $record['AwayIDName'],
			'home_id' => $record['HomeId'],
			'home_id_name' => $record['HomeIDName'],
			'match_datetime' => $this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($record['MatchDatetime']))),
			'bet_type' => $record['BetType'],
			'parlay_refno' => $record['ParlayRefNo'],
			'bet_team' => $record['BetTeam'],
			'hdp' => $record['HDP'],
			'away_hdp' => $record['AwayHDP'],
			'home_hdp' => $record['HomeHDP'],
			'odds' => $record['Odds'],
			'odds_type' => isset($record['OddsType']) ? $record['OddsType'] : 0,
			'away_score' => $record['AwayScore'],
			'home_score' => $record['HomeScore'],
			'is_live' => $record['IsLive'],
			'parlay_type' => $record['parlay_type'],
			'combo_type' => $record['combo_type'],
			'is_lucky' => $record['IsLucky'],
			'bet_tag' => $record['Bet_Tag'],
			'last_ball_no' => $record['LastBallNo'],
			'ticket_status' => strtolower($record['TicketStatus']),
			'stake' => $record['Stake'],
			'winlose_amount' => $record['WinLoseAmount'],
			'winlost_datetime' => $this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($record['WinLostDateTime']))),
			'game_platform' => $this->getPlatformCode(),
			'external_uniqueid' => strval($record['TransId']),
			'response_result_id' => $responseResultId,
		);

		return $ibcGameData;
	}

	public function syncLostAndFound($token) {
		$this->CI->load->model(array('ibcgame_game_logs'));

		# Limit the scope of getRunningLogs to avoid returning residue records for too long ago
		$runningLogsStartDate = date('Y-m-d H:i:s', strtotime('-'.self::LOST_AND_FOUND_MAX_DAYS_BACK.' days'));
		$runningLogsEndDate = date('Y-m-d H:i:s');
		$result = $this->CI->ibcgame_game_logs->getRunningLogs($runningLogsStartDate, $runningLogsEndDate);
		$this->utils->debug_log("syncLostAndFound for results between [$runningLogsStartDate] and [$runningLogsEndDate], result count", count($result));

		$transaction_time = array();
		if(!empty($result)) {
			foreach ($result as $key => $resulti) {
				$transaction_time[] = $resulti['transaction_time'];
			}
		}
		if(!empty($transaction_time)) {
			$startDate = date('Y-m-d H:i:s',min(array_map('strtotime', $transaction_time)));
			$endDate   = date('Y-m-d H:i:s',max(array_map('strtotime', $transaction_time)));
			if(count($transaction_time) > 1) {
				$this->CI->utils->loopDateTimeStartEnd($startDate, $endDate, '+12 hours', function($startDate, $endDate) {
					$startDate = $startDate->format('Y-m-d H:i:s');
					$endDate = $endDate->format('Y-m-d H:i:s');
					$resultByRange = $this->CI->ibcgame_game_logs->getRunningLogs($startDate,$endDate);
					// $this->utils->debug_log('syncLostAndFound resultByRange - '.json_encode($resultByRange));
					if(!empty($resultByRange)) {
						$this->utils->debug_log('syncLostAndFound startDate - '.$startDate);
						$this->utils->debug_log('syncLostAndFound endDate - '.$endDate);
						return $this->callLostAndFound($startDate,$endDate);
					}else{
						return true;
					}
				});
			}
			else {
				return $this->callLostAndFound($startDate,$endDate);
			}
		}
		return array('success' => true);
	}

	public function callLostAndFound($startDate,$endDate){
		$date = $this->lostAndFoundTimeAdjustment($startDate,$endDate);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForSyncLostAndFound',
		);
		$encodedStartDate =  strtolower(urlencode($date['startDate']));
		$encodedEndDate   =  strtolower(urlencode($date['endDate']));
		$dateParams = array(
			"StartTime" => $encodedStartDate,
			"EndTime" => $encodedEndDate,
		);
		$params = array(
			"OpCode" => $this->ibc_operator_code,
		);
		$dateTime = '&StartTime='.$dateParams['StartTime'].'&EndTime='.$dateParams['EndTime'];
		$orignalText = $this->ibc_private_key . "/api/GetSportBettingDetail?" . http_build_query($params).$dateTime;
		$securityToken = $this->generateSecurityToken($orignalText);
		$data = array(
			"OpCode" => $this->ibc_operator_code,
			"StartTime" => $date['startDate'],
			"EndTime" => $date['endDate'],
			"SecurityToken" => $securityToken,
		);
		return $this->callApi(self::API_syncLostAndFound, $data, $context);
	}

	public function lostAndFoundTimeAdjustment($startDate,$endDate){
		$startDate = $this->serverTimeToGameTime($startDate);
		$endDate = $this->serverTimeToGameTime($endDate);
		$endDate = new DateTime($endDate);
		$endDate->modify('+1 minutes');
		$endDate = $endDate->format('Y-m-d H:i:s');
		return array(
			"startDate" => $startDate,
			"endDate" => $endDate,
		);
	}

	public function processResultForSyncLostAndFound($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$this->CI->load->model(array('ibcgame_game_logs', 'external_system', 'game_description_model', 'game_provider_auth','game_logs'));
		$result = array();
		$success = $this->processResultBoolean($responseResultId, $resultJson);

		if ($success) {
			$gameRecords = $resultJson['Data'];

			if (empty($gameRecords) || !is_array($gameRecords)) {
				$this->CI->utils->debug_log('wrong game records', $gameRecords);
			}
			// print_r(count($gameRecords));exit();
			if (!empty($gameRecords)) {
				$this->CI->utils->debug_log('gameRecords', count($gameRecords));
				$transIdArr=[];
				$sportArr=[];
				$playerNameArr=[];
				foreach ($gameRecords as $record) {
					$transIdArr[]=$record['TransId'];
					$sportArr[]=isset($record['SportType']) ? $record['SportType'] : 0;
					$playerNameArr[]=$record['PlayerName'];
				}
				$sportArr=array_unique($sportArr);

				$gameDetails = $this->CI->game_description_model->getGameDescriptionListByGamePlatformIdAndGameCodeArray($this->getPlatformCode(),$sportArr);
				$unknownGame = $this->getUnknownGame();
				$playerMap= $this->CI->game_provider_auth->getPlayerMapFromLoginNameArr($this->getPlatformCode(), $playerNameArr);

				$mapTransId=$this->CI->ibcgame_game_logs->getExistsNotSettledTransIdArr($transIdArr);

				$this->CI->utils->debug_log('start processing game records', count($gameRecords));

				//api is settled, but not settle in db
				foreach ($gameRecords as $record) {
					$status=$this->getGameRecordsStatus($record['TicketStatus']);
					if($status!=Game_logs::STATUS_SETTLED){
						continue;
					}

					$ibcGameData = $this->getGameDataFrom($record, $responseResultId);

					// $isExists = $this->CI->ibcgame_game_logs->isRowIdAlreadyExists($ibcGameData['trans_id']);
					if (in_array($ibcGameData['trans_id'], $mapTransId)) {
						$this->CI->utils->debug_log('update and merge game data '.$record['TransId'].' '.$record['PlayerName'].' '.$record['SportType'], $record['TicketStatus'] );
						$this->CI->ibcgame_game_logs->updateGameLogs($ibcGameData);
					// } else {
					// 	$this->CI->ibcgame_game_logs->insertIbcGameLogs($ibcGameData);
						$this->syncMergeToGameLogsInstantly($ibcGameData, $gameDetails, $unknownGame, $playerMap);
					}
				}

				$this->CI->utils->debug_log('end processing game records', count($gameRecords));
			}
		}

		return array($success, $result);
	}

	public function syncMergeToGameLogs($token) {

		// return array('success' => true);

		$this->CI->load->model(array('ibcgame_game_logs','game_logs'));
		$dateTimeFrom = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$dateTimeFrom->modify($this->getDatetimeAdjust());
		$dateTimeTo = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
		//observer the date format
		$startDate = $dateTimeFrom->format('Y-m-d H:i:s');
		$endDate = $dateTimeTo->format('Y-m-d H:i:s');
		$rlt = array('success' => true);
		$result = $this->CI->ibcgame_game_logs->getGameLogStatistics($startDate, $endDate);

		// echo"<pre>";print_r($result);exit();
		// echo 1;exit();
		$cnt = 0;
		if ($result) {
			$unknownGame = $this->getUnknownGame();
			foreach ($result as $ibc_data) {
				// $player_id = $this->getPlayerIdInGameProviderAuth($ibc_data['player_name']);
				$player_id=$ibc_data['player_id'];

				// $player = $this->CI->player_model->getPlayerById($player_id);
				// $player_username = $player->username;

				$game_description_id = $ibc_data['game_description_id'];
				$game_type_id = $ibc_data['game_type_id'];
				$status = $this->getGameRecordsStatus($ibc_data['ticket_status']);
				$bet_amount = ($status == Game_logs::STATUS_REFUND) ? 0 : $this->gameAmountToDB($ibc_data['stake']);
				$betDetails = $this->processGameBetDetail($ibc_data);

				$odds_format = array_key_exists($ibc_data['odds_type'], self::ODDS_FORMAT) ? self::ODDS_FORMAT[$ibc_data['odds_type']] : null;

				$extra = array(
					'table' => $ibc_data['trans_id'],
					'trans_amount'	=> 	$bet_amount,
					'status'		=> 	($status == Game_logs::STATUS_REFUND) ? Game_logs::STATUS_SETTLED : $status ,
					'bet_details'	=>  $betDetails,
					'odds' 			=>  $ibc_data['odds'],
					'note'			=> ($status == Game_logs::STATUS_REFUND) ? "Refund ". $this->gameAmountToDB($ibc_data['stake']) : NULL,
					'sync_index' => $ibc_data['id'],
				);

				if(!empty($odds_format)){
					$extra['odds_type']=$odds_format; # eu / hk
				}

				if (empty($game_description_id)) {
					$game_description_id = $unknownGame->id;
					$game_type_id = $unknownGame->game_type_id;
				}

				$this->syncGameLogs(
					$game_type_id,
					$game_description_id,
					$ibc_data['game_code'],
					$ibc_data['game_type'],
					$ibc_data['game'],
					$player_id,
					$ibc_data['player_name'],
					$bet_amount,
					$ibc_data['winlose_amount'],
					null, # win_amount
					null, # loss_amount
					null,// # after_balance
					0, # has_both_side
					$ibc_data['trans_id'],
					$ibc_data['transaction_time'],  //start
					$ibc_data['transaction_time'],  //end
					$ibc_data['response_result_id'],
					Game_logs::FLAG_GAME,
					$extra
				);
			}
		}

		$this->CI->utils->debug_log('syncMergeToGameLogs monitor', 'count', $cnt);
		return $rlt;
	}

	/**
	 * overview : get game record status
	 *
	 * @param $status
	 * @return int
	 */
	private function getGameRecordsStatus($status) {
		$this->CI->load->model(array('game_logs'));
		$status = strtolower($status);

		switch ($status) {
		case 'running':
		case 'waiting':
			$status = Game_logs::STATUS_ACCEPTED;
			break;
		case 'reject':
			$status = Game_logs::STATUS_REJECTED;
			break;
		case 'refund':
			$status = Game_logs::STATUS_REFUND;
			break;
		case 'void':
			$status = Game_logs::STATUS_VOID;
			break;
		case 'won':
		case 'half won':
		case 'draw':
		case 'lose':
		case 'half lose':
			$status = Game_logs::STATUS_SETTLED;
			break;
		}
		return $status;
	}

	// private function getIbcGameLogStatistics($dateTimeFrom, $dateTimeTo) {
	// 	$this->CI->load->model('ibcgame_game_logs');
	// 	return $this->CI->ibcgame_game_logs->getIbcGameLogStatistics($dateTimeFrom, $dateTimeTo);
	// }

	public function gameAmountToDB($amount) {
		//only need 2
		return round(floatval($amount), 2);
	}

	const GAME_CODE_PREFIX = 'ibc.games.';
	private function getGameDescriptionInfo($row, $unknownGame, $gameDescIdMap) {
		$game_description_id = null;
		if (isset($row->game_description_id)) {
			$game_description_id = $row->game_description_id;
		}
		$game_type_id = null;
		if (isset($row->game_type_id)) {
			$game_type_id = $row->game_type_id;
		}

		//$externalGameId = $row->gameshortcode;
		$externalGameId = self::GAME_CODE_PREFIX . $row->league_id;
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
			$row->league_name, null, $externalGameId, $extra,
			$unknownGame);
	}

	//===end syncGameRecords=====================================================================================

	//===start syncBalance=====================================================================================
	//===end syncBalance=====================================================================================

	// ===start isPlayerExist=====================================================================================
	public function isPlayerExist($playerName) {
		$playerInfo = $this->getPlayerInfoByUsername($playerName);
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForIsPlayerExist',
			'playerName' => $playerName,
		);

		$params = array(
			"OpCode" => $this->ibc_operator_code,
			"PlayerName" => $gameUsername,
		);
		$securityToken = $this->generateSecurityToken($this->ibc_private_key . "/api/CheckUserBalance?" . http_build_query($params));

		$data = array(
			"OpCode" => $this->ibc_operator_code,
			"PlayerName" => $gameUsername,
			"SecurityToken" => $securityToken,
		);

		return $this->callApi(self::API_isPlayerExist, $data, $context);
	}

	public function processResultForIsPlayerExist($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$playerId = $this->getPlayerIdInPlayer($playerName);
		if($resultJson['error_code'] == 0){
			$success = true;
			$result['exists'] = $success;
			$this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
		}
		elseif($resultJson['error_code'] == 23005){
			$success = true;
			$result['exists'] = false;
		}
		else{
			$success = false;
			$result['exists'] = null;
		}
		return array($success, $result);
	}
	// ===end isPlayerExist=====================================================================================

	/**
	 * game time + 12 = server time
	 *
	 */
	// public function getGameTimeToServerTime() {
	// 	return '+12 hours';
	// }

	// public function getServerTimeToGameTime() {
	// 	return '-12 hours';
	// }

	//===start batchQueryPlayerBalance=====================================================================================
	public function batchQueryPlayerBalance($playerNames, $syncId = null) {
		return $this->returnUnimplemented();
	}

	//===end batchQueryPlayerBalance=====================================================================================

	public function setMemberBetSetting($playerName,$newBetSetting = null) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForSetMemberBetSetting',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername,
			'confirm' => false
		);

		$sportsTypeArr = empty($newBetSetting) ? $this->ibc_bet_setting : $newBetSetting;
		$betSettingArr = array();
		if (!empty($sportsTypeArr)) {
			foreach ($sportsTypeArr as $key) {
				if (!empty($key['sport_type'])) {
					$key['sport_type'] = empty($newBetSetting) ?  $key['sport_type'] : array($key['sport_type']);
					foreach ($key['sport_type'] as $type) {
						$betSettingArr[] = array(
							"sport_type" => $type,
							"min_bet" => isset($key['min_bet']) ? $key['min_bet'] : 1,
							"max_bet" => isset($key['max_bet']) ? $key['max_bet'] : 1,
							"max_bet_per_match" => isset($key['max_bet_per_match']) ? $key['max_bet_per_match'] : 1,
							"max_bet_per_ball" => isset($key['max_bet_per_ball']) ? $key['max_bet_per_ball'] : 0,
						);

						$betSettingArr['max_payout_per_match'] = (isset($key['max_payout_per_match'])?$key['max_payout_per_match']:$key['max_payout_per_match']*$this->max_payout_per_match_multiplier);
					}
				}
			}
		}
		$result = array();
		if(!empty($betSettingArr)){
			$last_key = end(array_keys($betSettingArr));
			foreach ($betSettingArr as $key => $value) {
				$params = array(
					"OpCode" => $this->ibc_operator_code,
					"PlayerName" => $gameUsername,
					"sportType" => @$value['sport_type'],
					"minBet" => @$value['min_bet'],
					"maxBet" => @$value['max_bet'],
					"maxBetPerMatch" => @$value['max_bet_per_match'],
					"max_bet_per_ball" => @$value['max_bet_per_ball'],
				);
				if($key == $last_key){
					$context['confirm'] = true;
				}
				$params['SecurityToken'] = $this->generateSecurityToken($this->ibc_private_key . "/api/PrepareMemberBetSetting?" . http_build_query($params));
				$result[] = $this->callApi(self::API_setMemberBetSetting, $params, $context);
			}
		}

		return array(
			"success"	=>true,
			"result" 	=>end($result)
		);
	}

	public function processResultForSetMemberBetSetting($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$confirm = $this->getVariableFromContext($params, 'confirm');
		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);
		if($success && $confirm){
			$this->confirmMemberBetSetting($playerName);
		}
		return array($success, $resultJson);
	}
	//to confirm the members setting changes.
	public function confirmMemberBetSetting($playerName) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForConfirmMemberBetSetting',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername,
		);

		$params = array(
			"OpCode" => $this->ibc_operator_code,
			"PlayerName" => $gameUsername,
		);
		$params['SecurityToken']  = $this->generateSecurityToken($this->ibc_private_key . "/api/ConfirmMemberBetSetting?" . http_build_query($params));
		return $this->callApi(self::API_confirmMemberBetSetting, $params, $context);
	}

	public function processResultForConfirmMemberBetSetting($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);
		return array($success, $resultJson);
	}

	//to get members setting of betting limit.
	public function getMemberBetSetting($playerName) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForGetMemberBetSetting',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername,
		);

		$params = array(
			"OpCode" => $this->ibc_operator_code,
			"PlayerName" => $gameUsername,
		);
		$params['SecurityToken'] = $this->generateSecurityToken($this->ibc_private_key . "/api/GetMemberBetSetting?" . http_build_query($params));
		return $this->callApi(self::API_getMemberBetSetting, $params, $context);
	}

	public function processResultForGetMemberBetSetting($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);
		return array($success, $resultJson);
	}

	 public function getSports($sport_id = null){
        $sports = array(
            //necessary fields
            "1" => lang("Soccer"),
            "2" => lang("Basketball"),
            "3" => lang("Football"),
            "5" => lang("Tennis"),
            "8" => lang("Baseball"),
            "10" => lang("Golf"),
            "11" => lang("Motorsports"),
            "99" => lang("Other Sports"),
            "99MP" => lang("Mix Parlay"),
            //others
            "154" => lang("HorseRacing FixedOdds"),
            "161" => lang("Number GameNumber"),
            "180" => lang("Virtual Sports"),
            "190" => lang("Virtual Sports 2"),
        );

        if(empty($sport_id)){
            return $sports;
        } else {
            if(isset($sports[$sport_id])){
                return $sports[$sport_id];
            }
            return $sport_id;
        }
    }

}

/*end of file*/