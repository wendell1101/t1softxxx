<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
require_once dirname(__FILE__).'/isb_freerounds_module.php';

/**
 * Defines general behavior of game API classes.
 *
 * General behaviors include:
 * * Getting platform code
 * * Custom Http Call
 * * Generate URL
 * * Create Player
 * * Block/Unblock Player
 * * Deposit To game
 * * Withdraw from Game
 * * Get Player Token
 * * Check Player Balance
 * * Check Player Daily Balance
 * * Check Game records
 * * Check Forward Game
 * * Synchronize Original Game Logs
 * * Synchronize and Merge ISB to Game Logs
 * * Get Game Description Information
 * *
 * Behaviors not implemented
 * * Login/Logout player
 * * Update Player's information
 * * Check Player's information
 * * Change Password
 * * Check player's login status
 * * Check total betting amount
 * * Check Transaction
 *
 * The functions implemented by child class:
 * * Populating game form parameters
 * * Handling callbacks
 *
 *
 *
 * @see Redirect redirect to game page
 *
 * @category Game_platform
 * @version 1.8.10
 * @copyright 2013-2022 tot
 */
class Game_api_isb extends Abstract_game_api {
	use isb_freerounds_module;
	private $isb_api_version;
	private $isb_api_license_id;
	private $isb_api_url;
	private $isb_operator_name;
	private $isb_currency;
	private $isb_api_hashcode_secret_key;

	const DEFAULT_DELAY_TIME = 10;

	public function __construct() {
		parent::__construct();
		$this->isb_api_version = $this->getSystemInfo('isb_api_version');
		$this->isb_api_license_id = $this->getSystemInfo('isb_api_license_id');
		$this->isb_api_url = $this->getSystemInfo('url');
		$this->isb_operator_name = $this->getSystemInfo('isb_operator_name');
		$this->isb_currency = $this->getSystemInfo('isb_currency');
		$this->isb_api_hashcode_secret_key = $this->getSystemInfo('secret');
		$this->isb_game_url = $this->getSystemInfo('isb_game_url');
		$this->is_redirect = $this->getSystemInfo('is_redirect',true);
		$this->isb_freerounds_url = $this->getSystemInfo('isb_freerounds_url','https://stage-sg-mbo-api.isoftbet.com');//staging
		$this->isb_fr_api_version = $this->getSystemInfo('isb_frisb_fr_api_versioneerounds_url','v1');
		$this->enabled_fr_feature = $this->getSystemInfo('enabled_fr_feature',false);
		$this->use_gzip_on_wagersfeed = $this->getSystemInfo('use_gzip_on_wagersfeed',false);

		$this->enabled_504_deposit_eq_success = $this->getSystemInfo('enabled_504_deposit_eq_success',true);
	}

	const API_FR_getAvailableGames = "getAvailableGames";
	const API_FR_getPlayerFreeRounds = "getPlayerFreeRounds";
	const API_FR_getCampaigns = "getCampaigns";
	const API_FR_acceptFreeRound = "acceptFreeRound";
	const API_FR_cancelFreeRound = "cancelFreeRound";
	const GET_METHOD = "GET";
	const POST_METHOD = "POST";
	const FR_SUCCESS = "ok";
	const FR_PENDING = "pending";

	const ORIGINAL_LOGS_TABLE_NAME = 'isb_raw_game_logs';
	const MD5_FIELDS_FOR_ORIGINAL=['playerid', 'operator', 'currency', 'sessionid', 'gameid', 'roundid', 'status', 'type','transactionid','amount'];
	const MD5_FLOAT_AMOUNT_FIELDS=['amount'];
	# Fields in game_logs we want to detect changes for merge, and when pragmaticplay_game_logs.md5_sum is empty
    const MD5_FIELDS_FOR_MERGE=['player_id', 'currency', 'roundid','sessionid','status','type','result_amount','bet_amount'];
    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE=['result_amount','bet_amount'];
    const type_bet = "BET";
    const type_result = "WIN";
    const COMMAND_WAGERSFEED = "wagersFeed";
	public function getPlatformCode() {
		return ISB_API;
	}

	protected function customHttpCall($ch, $params) {
		if(isset($params['isFreeround']) && $params['isFreeround']){
			$header_params = array(
				'Content-Type: application/json',
				'X-LICENSEE-ID:'.$this->isb_api_license_id
			);

			curl_setopt($ch, CURLOPT_HTTPHEADER, $header_params);
			if($params['method'] == self::POST_METHOD){
				unset($params['isFreeround'],$params['method'],$params['uri']);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
			}
		} else {
			$header_params = array(
				'Content-Type: application/x-www-form-urlencoded',
				'Content-Type: application/json',
			);
			if(isset($params['action']) && (isset($params['action']['command']) && $params['action']['command'] == self::COMMAND_WAGERSFEED) && $this->use_gzip_on_wagersfeed){
				curl_setopt ($ch, CURLOPT_HTTPHEADER, Array("Content-Type: text/plain","Accept-Encoding: gzip"));
				curl_setopt($ch, CURLOPT_ENCODING,'gzip');
			} else{
				curl_setopt($ch, CURLOPT_HTTPHEADER, $header_params);
			}
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
		}
	}

	public function generateUrl($apiName, $params) {
		$key = $this->isb_api_hashcode_secret_key;
		$hashCode = hash_hmac('SHA256', json_encode($params), $key);
		$url = $this->isb_api_url . '/' . $this->isb_api_version . '/' . $this->isb_api_license_id . '?hash=' . $hashCode;
		if(isset($params['isFreeround']) && $params['isFreeround']){
			$uri = $params['uri'];
			return $uri;
		}
		return $url;
	}

	public function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null) {
		return array(false, null);
	}

	public function processResultBoolean($responseResultId, $resultArr, $playerName = null) {
		$success = !empty($resultArr) && $resultArr['status'] == 'success';
		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('isb got error', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
			$success = false;
		}
		return $success;
	}

	public function processGameList($game) {
		$game = parent::processGameList($game);
		$this->CI->load->model(array('game_description_model'));
		$extra = $this->CI->game_description_model->getGameTypeById($game['g']);
		$game['gp'] = "iframe_module/goto_isb_game/" . $game['c']; //game param
		return $game;
	}

	private function testAccountChecker($playerName) {
		$isb_test_accounts = $this->getSystemInfo('isb_test_accounts');
		if(isset($isb_test_accounts['accounts'])){
			$isbTestAccts = explode(",", $isb_test_accounts['accounts']);

			if (in_array($playerName, $isbTestAccts)) {
				$this->isb_operator_name = $this->getSystemInfo('isb_test_operator_name');
			}
		}

		// $isbTestAccts = explode(",", $this->getSystemInfo('isb_test_accounts')['accounts']);
		// if (in_array($playerName, $isbTestAccts)) {
		// 	$this->isb_operator_name = $this->getSystemInfo('isb_test_operator_name');
		// }
	}

    private function getPlayerISBCurrency($username){
		# use correct currency code
		$playerId = $this->getPlayerIdInGameProviderAuth($username);
		if(!is_null($playerId)){
			$this->CI->load->model(array('player_model'));
			$currencyCode = $this->CI->player_model->getPlayerCurrencyByPlayerId($playerId);
			if(!is_null($currencyCode)){
				if($currencyCode == 'CNY'){
					$currencyCode = 'RMB';
				}
				return $currencyCode;
			}else{
				return $this->isb_currency;
			}
		}else{
			return $this->isb_currency;
		}
	}

	//===start createPlayer=====================================================================================
	public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		parent::createPlayer($playerName, $playerId, $password, $email, $extra);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForCreatePlayer',
			'playerName' => $playerName,
			'playerId' => $playerId,
		);

		$params = array(
			"operator" => $this->isb_operator_name,
			"playerid" => $playerName,
			"username" => $playerName,
			// "value" => "0", #doc says, value will always be 0 for new registered players
			"currency" => $this->getPlayerISBCurrency($playerName),
			"action" => array(
				"command" => "addPlayer",
				"parameters" => array(
					"real" => true, // makes no sense to create demo using extra info as this will affect all users.
				),
			),
		);
		return $this->callApi(self::API_createPlayer, $params, $context);
	}

	public function processResultForCreatePlayer($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$playerId = $this->getVariableFromContext($params, 'playerId');
		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);
		if($success){
			$this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
		}
		return array($success, $resultJson);
	}

	//===end createPlayer=====================================================================================

	//===start queryPlayerInfo=====================================================================================
	public function queryPlayerInfo($playerName) {
		return $this->returnUnimplemented();
	}
	//===end queryPlayerInfo=====================================================================================

	//===start changePassword=====================================================================================
	public function changePassword($playerName, $oldPassword, $newPassword) {
		return $this->returnUnimplemented();
	}
	//===end changePassword=====================================================================================

	//===start blockPlayer=====================================================================================
	// public function blockPlayer($playerName) {
	// 	$playerName = $this->getGameUsernameByPlayerUsername($playerName);
	// 	$success = $this->blockUsernameInDB($playerName);
	// 	return array("success" => true);
	// }
	//===end blockPlayer=====================================================================================

	//===start unblockPlayer=====================================================================================
	// public function unblockPlayer($playerName) {
	// 	$playerName = $this->getGameUsernameByPlayerUsername($playerName);
	// 	$success = $this->unblockUsernameInDB($playerName);
	// 	return array("success" => true);
	// }
	//===end unblockPlayer=====================================================================================

	//===start depositToGame=====================================================================================
	public function depositToGame($playerName, $amount, $transfer_secure_id = null) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $external_transaction_id = empty($transfer_secure_id) ? 'T'.$this->CI->utils->randomString(12) : $transfer_secure_id;

		// For negative amount upon deposit since the API didn't handle this kind of request
		if ($amount < 0) {
			$result = array(
				'external_transaction_id'=>$external_transaction_id,
				'transfer_status'=>self::COMMON_TRANSACTION_STATUS_DECLINED,
				'reason_id'=>self::REASON_UNKNOWN
			);
			return array(false, $result);
		}

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForDepositToGame',
			'gameUsername' => $gameUsername,
			'amount' => $amount,
			'external_transaction_id' => $external_transaction_id,
			'playerName' => $playerName
		);
		$this->testAccountChecker($gameUsername);
		$params = array(
			"operator" =>$this->isb_operator_name,
			"playerid" => $gameUsername,
			"username" => $gameUsername,
			"currency" => $this->getPlayerISBCurrency($gameUsername),
			"action" => array(
				"command" => "depositFunds",
				"parameters" => array(
					"amount" => $this->dBtoGameAmount($this->convertIsbMoney($amount)), #in isb the value is in cents so we need convert to actual value
					"transactionid" => $external_transaction_id,
				),
			),
		);
		return $this->callApi(self::API_depositToGame, $params, $context);
	}

	public function processResultForDepositToGame($params) {
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJsonArr = $this->getResultJsonFromParams($params);
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$amount = $this->getVariableFromContext($params, 'amount');
		$success = $this->processResultBoolean($responseResultId, $resultJsonArr, $gameUsername, true);

		$result = array(
			'response_result_id' => $responseResultId,
			'external_transaction_id'=>$external_transaction_id,
			'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id'=>self::REASON_UNKNOWN
		);

		if($this->enabled_504_deposit_eq_success && !$success){
			$statusCode=$this->getStatusCodeFromParams($params);
			if($statusCode==504){
				$this->CI->utils->debug_log('found 504, set success to true');
				$result['didnot_insert_game_logs']=true;
				//unknown
				$success=true;
			}
		}

		if ($success) {
			//get current sub wallet balance
			// $playerBalance = $this->convertAmountToSbeMoney($resultJsonArr['balance']);
			//for sub wallet
			// $afterBalance = $this->gameAmountToDB($playerBalance) ?: null;
			// //$result["external_transaction_id"] = null;
			// $result["currentplayerbalance"] = $afterBalance;
			// //$result["transId"] = $transId;
			// //$result["userNotFound"] = false;

			// //update
			// $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
			// if ($playerId) {
			// 	//deposit
			// 	$this->insertTransactionToGameLogs($playerId, $gameUsername, $afterBalance, $amount, $responseResultId,
			// 		$this->transTypeMainWalletToSubWallet());
			// } else {
			// 	$this->CI->utils->debug_log('error', 'cannot get player id from ' . $gameUsername . ' getPlayerIdInGameProviderAuth');
			// }
			$result['didnot_insert_game_logs']=true;
			$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
		} else {
			$error_code = @$resultJsonArr['code'];
			switch($error_code) {
				case 'R_01' :
				case 'R_02' :
					$result['reason_id'] = self::REASON_INCOMPLETE_INFORMATION;
					break;
				case 'R_03' :
					$result['reason_id'] = self::REASON_CURRENCY_ERROR;
					break;
				case 'R_04' :
					$result['reason_id'] = self::REASON_AGENT_NOT_EXISTED;
					break;
				case 'R_05' :
					$result['reason_id'] = self::REASON_NOT_FOUND_PLAYER;
					break;
				case 'DF_01' :
					$result['reason_id'] = self::REASON_DUPLICATE_TRANSFER;
					break;
			}

			$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
		}

		return array($success, $result);
	}

	//===end depositToGame=====================================================================================

	//===start withdrawFromGame=====================================================================================
	public function withdrawFromGame($playerName, $amount, $transfer_secure_id = null) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $external_transaction_id = empty($transfer_secure_id) ? 'T'.$this->CI->utils->randomString(12) : $transfer_secure_id;

		// For negative amount upon withdraw since the API didn't handle this kind of request
		if ($amount < 0) {
			$result = array(
				'external_transaction_id'=>$external_transaction_id,
				'transfer_status'=>self::COMMON_TRANSACTION_STATUS_DECLINED,
				'reason_id'=>self::REASON_UNKNOWN
			);
			return array(false, $result);
		}

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForWithdrawToGame',
			'gameUsername' => $gameUsername,
			'amount' => $amount,
			'playerName' => $playerName,
			'external_transaction_id' => $external_transaction_id
		);
		$this->testAccountChecker($playerName);
		$params = array(
			"operator" => $this->isb_operator_name,
			"playerid" => $gameUsername,
			"username" => $gameUsername,
			"currency" => $this->getPlayerISBCurrency($gameUsername),
			"action" => array(
				"command" => "withdrawFunds",
				"parameters" => array(
					"amount" => $this->dBtoGameAmount($this->convertIsbMoney($amount)), #in isb the value is in cents so we need convert to actual value
					"transactionid" => $external_transaction_id,
				),
			),
		);
		return $this->callApi(self::API_withdrawFromGame, $params, $context);
	}

    private function convertIsbMoney($amount){
        // Multiply by 100
        return $amount * 100;
    }

    private function convertAmountToSbeMoney($amount){
        // Divide by 100
        return $amount / 100;
    }

	public function processResultForWithdrawToGame($params) {
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJsonArr = $this->getResultJsonFromParams($params);
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$amount = $this->getVariableFromContext($params, 'amount');
		// $transId = $this->getVariableFromContext($params, 'transId');
		$success = $this->processResultBoolean($responseResultId, $resultJsonArr, $gameUsername);

		$result = array(
			'response_result_id' => $responseResultId,
			'external_transaction_id'=>$external_transaction_id,
			'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id'=>self::REASON_UNKNOWN
		);

		if ($success) {
			//get current sub wallet balance

			// $playerBalance = $this->convertAmountToSbeMoney($resultJsonArr['balance']);
			//for sub wallet
			// $afterBalance = $this->gameAmountToDB($playerBalance) ?: null;
			// $result["external_transaction_id"] = null;
			// $result["currentplayerbalance"] = $afterBalance;
			// $result["transId"] = $transId;
			$result["userNotFound"] = false;

			//update
			// $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
			// if ($playerId) {
			// 	//withdrawal
			// 	$this->insertTransactionToGameLogs($playerId, $gameUsername, $afterBalance, $amount, $responseResultId,
			// 		$this->transTypeSubWalletToMainWallet());
			// } else {
			// 	$this->CI->utils->debug_log('error', 'cannot get player id from ' . $gameUsername . ' getPlayerIdInGameProviderAuth');
			// }
			$result['didnot_insert_game_logs']=true;
			$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
		} else {
			$error_code = @$resultJsonArr['code'];
			switch($error_code) {
				case 'R_01' :
				case 'R_02' :
					$result['reason_id'] = self::REASON_INCOMPLETE_INFORMATION;
					break;
				case 'R_03' :
					$result['reason_id'] = self::REASON_CURRENCY_ERROR;
					break;
				case 'R_04' :
					$result['reason_id'] = self::REASON_AGENT_NOT_EXISTED;
					break;
				case 'R_05' :
					$result['reason_id'] = self::REASON_NOT_FOUND_PLAYER;
					break;
				case 'DF_01' :
				case 'WF_03' :
					$result['reason_id'] = self::REASON_DUPLICATE_TRANSFER;
					break;
				case 'WF_01' :
					$result['reason_id'] = self::REASON_NO_ENOUGH_BALANCE;
					break;
			}
			$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
		}

		return array($success, $result);
	}

	//===end withdrawFromGame=====================================================================================

	//===start login=====================================================================================
	public function login($playerName, $password = null) {
		return $this->returnUnimplemented();
	}

	public function getPlayerISBToken($playerName) {
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForGetPlayerISBToken',
			'playerName' => $playerName,
		);
		$this->testAccountChecker($playerName);
		$params = array(
			"operator" => $this->isb_operator_name,
			"playerid" => $playerName,
			"username" => $playerName,
			"currency" => $this->getPlayerISBCurrency($playerName),
			"action" => array(
				"command" => "getPlayerToken",
			),
		);
		return $this->callApi(self::API_checkLoginToken, $params, $context);
	}

	public function processResultForGetPlayerISBToken($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJsonArr = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $resultJsonArr, $playerName);
		return array($success, array("result" => $resultJsonArr));
	}
	//===end login=====================================================================================

	//===start logout=====================================================================================
	public function logout($playerName, $password = null) {
		// return $this->getPlayerISBToken($playerName);
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForLogout',
			'playerName' => $playerName,
		);
		$this->testAccountChecker($gameUsername);
		$params = array(
			"action" => array(
				"command" => "killPlayerSessions",
				"parameters" => array(
					"players" => array(
						array(
							"playerid" => $gameUsername,
							"operator" => $this->isb_operator_name
					 	),
					),
				),
			),
		);
		return $this->callApi(self::API_logout, $params, $context);
	}

	public function processResultForLogout($params){
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$resultJsonArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultJsonArr,$playerName);
		$result = array();
		if($success){
			$result['message'] = $resultJsonArr['message'];
		}
		return array($success, $result);
	}

	//===end logout=====================================================================================

	//===start updatePlayerInfo=====================================================================================
	public function updatePlayerInfo($playerName, $infos) {
		return $this->returnUnimplemented();
	}

	//===end updatePlayerInfo=====================================================================================

    //===start isPlayerExist=====================================================================================
    public function isPlayerExist($playerName) {
        $playerName = $this->getGameUsernameByPlayerUsername($playerName);
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForIsPlayerExist',
            'playerName' => $playerName,
        );
        $this->testAccountChecker($playerName);
        $params = array(
            "operator" => $this->isb_operator_name,
            "playerid" => $playerName,
            "username" => $playerName,
            "currency" => $this->getPlayerISBCurrency($playerName),
            "action" => array(
                "command" => "getPlayerBalance",
            ),
        );
        return $this->callApi(self::API_queryPlayerBalance, $params, $context);
    }

    public function processResultForIsPlayerExist($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJsonArr = $this->getResultJsonFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $success = $this->processResultBoolean($responseResultId, $resultJsonArr, $playerName);

        $result = array();
        if ($success) {
            $result = array('exists' => true);
        }else{
            if($resultJsonArr['message']=="Invalid Player Details"){
                $result = array('exists' => false,'success'=>true); # Player not found
            }else{
                $result = array('exists' => false); # Player not found
            }
        }

        return array($success, $result);
    }
    //===end queryPlayerBalance=====================================================================================

	//===start queryPlayerBalance=====================================================================================
	public function queryPlayerBalance($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryPlayerBalance',
			'playerName' => $playerName,
		);
		$this->testAccountChecker($playerName);
		$params = array(
			"operator" => $this->isb_operator_name,
			"playerid" => $playerName,
			"username" => $playerName,
			"currency" => $this->getPlayerISBCurrency($playerName),
			"action" => array(
				"command" => "getPlayerBalance",
			),
		);
		return $this->callApi(self::API_queryPlayerBalance, $params, $context);
	}

	public function processResultForQueryPlayerBalance($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJsonArr = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $resultJsonArr, $playerName);

		$result = array();
		if ($success) {
			$result["balance"] = $this->gameAmountToDB(floatval($this->convertAmountToSbeMoney(@$resultJsonArr['balance']))); #handle cents
			$playerId = $this->getPlayerIdInGameProviderAuth($playerName);
			$this->CI->utils->debug_log('query balance playerId', $playerId, 'playerName', $playerName,
				'balance', @$resultJsonArr['balance']);
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

	public function queryTransaction($transactionId, $extra) {
		$playerName=$extra['playerName'];
		$playerId=$extra['playerId'];
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryTransaction',
			'gameUsername' => $gameUsername,
			'external_transaction_id' => $transactionId,
			'playerId'=>$playerId,
		);
		$this->testAccountChecker($gameUsername);

		$params = array(
			"operator" => $this->isb_operator_name,
			"playerid" => $gameUsername,
			"username" => $gameUsername,
			"currency" => $this->getPlayerISBCurrency($gameUsername),
			"action" => array(
				"command" => "checkTransfer",
				"parameters" => array(
					"transactions" => [$transactionId],
				),
			),
		);

		return $this->callApi(self::API_queryTransaction, $params, $context);
	}

	public function processResultForQueryTransaction( $params ){
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJsonArr = $this->getResultJsonFromParams($params);
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$success = $this->processResultBoolean($responseResultId, $resultJsonArr, $gameUsername, true);

		$result = array(
			'response_result_id' => $responseResultId,
			'external_transaction_id'=>$external_transaction_id,
			'status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id'=>self::REASON_UNKNOWN
		);

		if($success){
			$trans_status = @$resultJsonArr['transactions'][0]['transactionstatus'];
			if($trans_status == 'notfound') {
				$result['reason_id'] = self::REASON_INVALID_TRANSACTION_ID;
				$result['status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
			} else {
				$result['status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
			}
		} else {
			$error_code = @$resultJsonArr['code'];
			switch($error_code) {
				case 'R_01' :
				case 'R_02' :
					$result['reason_id'] = self::REASON_INCOMPLETE_INFORMATION;
					break;
				case 'R_03' :
					$result['reason_id'] = self::REASON_CURRENCY_ERROR;
					break;
				case 'R_04' :
					$result['reason_id'] = self::REASON_AGENT_NOT_EXISTED;
					break;
				case 'R_05' :
					$result['reason_id'] = self::REASON_NOT_FOUND_PLAYER;
					break;
			}
			$result['status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
		}

		return array($success, $result);
	}

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

    /*
        Current available language for UC is:
        English, Chinese
     */
    public function getLauncherLanguage($lang){
        $this->CI->load->library("language_function");
        switch ($lang) {
            case LANGUAGE_FUNCTION::INT_LANG_CHINESE:
            case 'zh-cn':
                $lang = 'chs';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_INDONESIAN:
            case 'id-id':
                $lang = 'id';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_VIETNAMESE:
            case 'vi-vn':
                $lang = 'vi';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_KOREAN:
            case 'ko-kr':
                $lang = 'kr';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_THAI:
            case 'th-th':
                $lang = 'th';
                break;
            default:
                $lang = 'en';
                break;
        }
        return $lang;
    }

    public function queryForwardGame($playerName, $param) {
        $url = null;
        $language = $this->getLauncherLanguage($param['language']);
        $game_mode = $param['game_mode'] == 'real' ? 1 : 0;

        if (empty($playerName)) {
        	$url = $this->isb_game_url . "/" . $this->isb_api_license_id . "/" . $param['game_code'] . "?lang=" . $language . '&cur=' . $this->isb_currency . '&mode=' . $game_mode;
        } else {
        	$playerName = $this->getGameUsernameByPlayerUsername($playerName);
	        $data = $this->getPlayerISBToken($playerName);

	        if (!empty($data['result'])) {
	            $url = $this->isb_game_url . "/" . $this->isb_api_license_id . "/" . $param['game_code'] . "?lang=" . $language . '&cur=' . $this->getPlayerISBCurrency($playerName) . '&mode=' . $game_mode;

	            if($game_mode == 1){
	            	$url .= '&user=' . $playerName . '&uid=' . $playerName . '&token=' . $data['result']['token'] . '&operator=' . $this->isb_operator_name;
	            }
	        }
        }

		if(isset($param['extra']['is_mobile_flag'])) {

			$lobby_url = $param['extra']['is_mobile_flag']
				   ? $this->utils->getSystemUrl('m') . $this->getSystemInfo('isb_game_lobby_url')
				   : $this->utils->getSystemUrl('www') . $this->getSystemInfo('isb_game_lobby_url');
		} else {

            $lobby_url = $this->utils->getSystemUrl('www') . $this->getSystemInfo('isb_game_lobby_url');

		}

        if(isset($param['home_link']) && !empty($param['home_link'])){
            $lobby_url = $param['home_link'];
        }
        else if(isset($param['extra']['t1_lobby_url'])) {
            $lobby_url = $param['extra']['t1_lobby_url'];
        }



        // if ($param['extra']['is_mobile_flag']) {
        $url .= "&lobbyURL=" . $lobby_url;
        // }

        return array(
            'success' => true,
            'url' => $url,
            'iframeName' => "ISB API",
            'is_redirect' => @$param['extra']['is_mobile_flag']?true:false
        );
    }

	public function processResultForQueryForward($params) {

	}
	//===end queryForwardGame=====================================================================================

	//===start syncGameRecords=====================================================================================
	/**
	 *
	 */
	const START_PAGE = 1;
	public function syncOriginalGameLogs($token) {
		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

		// $startDate = new DateTime($startDate->format('Y-m-d H:i:s'));
		$startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
		$endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));
		$startDate->modify($this->getDatetimeAdjust());
		$this->CI->utils->debug_log('startDate', $startDate, 'endDate', $endDate);

		$this->CI->load->model(array('external_system'));
		// $lastSyncDateTime = $this->CI->external_system->getLastSyncDatetime(ISB_API);

		// $delayTime = $this->getSystemInfo('sync_delay_time') ?: self::DEFAULT_DELAY_TIME;
		// $interval = new DateInterval('PT' . $delayTime . 'M');

		// $endDate->sub($interval);
		// $startDate->sub($interval);
		$queryDateTime = null;
		$endDateTimeMinute = $endDate->format('Y-m-d H:i') . ":00";

		$this->CI->utils->debug_log('adjust start date', $startDate, 'end date', $endDate);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForSyncGameRecords',
			'call_time' => $startDate->format('Y-m-d H:i'),
		);

		while ($endDateTimeMinute >= $queryDateTime) {

			$queryDateTime = $startDate->format('Y-m-d H:i') . ":00";

			$this->CI->utils->debug_log('ISB query datetime >-----------------------> ' . $queryDateTime);
			// $this->CI->external_system->updateLastSyncDatetime(ISB_API, $queryDateTime);
			$page = self::START_PAGE;
			$done = false;
			while (!$done) {
				$params = array(
					"action" => array(
						"command" => "wagersFeed",
						"parameters" => array(
							"datetime" => $queryDateTime,
							"page" => $page,
						),
					),
				);
				$rlt = $this->callApi(self::API_syncGameRecords, $params, $context);
				$done = true;
				if ($rlt && $rlt['success']) {
					$total_pages = @$rlt['totalPages'];
					//next page
					$page += 1;
					$done = $page >= $total_pages;
					$this->CI->utils->debug_log('page', $page, 'total_pages', $total_pages, 'done', $done, 'result', $rlt);
				}
				if ($done) {
					$success = true;
				}
			}
			$startDate->modify('+1 minutes');
		}
		return array('success' => $success);
	}

	// public function getGameTimeToServerTime() {
	// 	return '+8 hours';
	// }

	// public function getServerTimeToGameTime() {
	// 	return '-8 hours';
	// }

	public function processResultForSyncGameRecords($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJsonArr = $this->getResultJsonFromParams($params);
		$callTime = $this->getVariableFromContext($params, 'call_time');

		// load models
		$this->CI->load->model(array('isb_game_logs', 'external_system','original_game_logs_model'));
		$result = array();
		$success = $this->processResultBoolean($responseResultId, $resultJsonArr);
		$rawGameRecords = array();
		$this->CI->utils->debug_log("ISB GAMELOGS ----------------> ", $callTime, ' flag: ', $success, ' RESULT --------------> ', count($resultJsonArr));

		$result = array(
			'data_count'=> 0
		);
		if ($success && isset($resultJsonArr['report']) && !empty($resultJsonArr['report'])) {

			$gameRecords = $this->processGameRecords($resultJsonArr['report'], $responseResultId);
			list($insertRows, $updateRows)=$this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                self::ORIGINAL_LOGS_TABLE_NAME,
                $gameRecords,
                'uniqueid',
                'uniqueid',
                self::MD5_FIELDS_FOR_ORIGINAL,
                'md5_sum',
                'id',
                self::MD5_FLOAT_AMOUNT_FIELDS
            );
			$this->CI->utils->debug_log('after process available rows', 'gamerecords ->',count($gameRecords), 'insertrows->',count($insertRows), 'updaterows->',count($updateRows));
			if (!empty($insertRows)) {
				$result['data_count'] += $this->updateOrInsertOriginalGameLogs($insertRows, 'insert');
			}
			unset($insertRows);

			if (!empty($updateRows)) {
				$result['data_count'] += $this->updateOrInsertOriginalGameLogs($updateRows, 'update');
			}
			unset($updateRows);
			//old
			// #UPDATE status if type is bet due to sync merge problem(its not updating)  when it is still ACTIVE- this will not merge
			// array_filter($gameRecords, function ($row) {
			// 	if($row['type'] == 'BET'|| $row['type'] == 'FREE_ROUND_BET'){
			// 		$this->CI->isb_game_logs->updateBetStatus($row['uniqueid'],$row['status']);
			// 	}
			// });

			// if (!empty($gameRecords)) {

			// 	$availableRows = $this->CI->isb_game_logs->getAvailableRows($gameRecords);
			// 	$availableRows = $availableRows;

			// 	if (!empty($availableRows)) {

			// 		# INSERT BETS

			// 		# Get bet rows
			// 		$betRows = array_filter($availableRows, function ($row) {
			// 			return ($row['type'] == 'BET'||$row['type'] == 'FREE_ROUND_BET');
			// 		});

			// 		# Insert
			// 		if (!empty($betRows)) {
			// 			$this->CI->isb_game_logs->insertBatchGameLogs($betRows);
			// 		}

			// 		# END INSERT BETS

			// 		# INSERT RESULTS THE UPDATE BETS

			// 		# Get result rows
			// 		$resultRows = array_filter($availableRows, function ($row) {
			// 			return ($row['type'] != 'BET'&&$row['type'] != 'FREE_ROUND_BET');
			// 		});

			// 		# Insert and Update
			// 		if (!empty($resultRows)) {
			// 			$this->CI->isb_game_logs->insertBatchGameLogs($resultRows);
			// 			$round_ids = array_unique(array_column($resultRows, 'roundid'));
			// 			array_walk($round_ids, array($this->CI->isb_game_logs, 'updateRound'));
			// 		}

			// 		# END INSERT RESULTS THE UPDATE BETS

			// 	}
			// }

			$result['currentPage'] = $resultJsonArr['currentpage'];
			$result['totalPages'] = $resultJsonArr['pages'];
			$result['itemsPerPage'] = $resultJsonArr['total'];
		}
		return array($success, $result);
	}

	private function updateOrInsertOriginalGameLogs($data, $queryType, $additionalInfo=[]){
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

	private function processGameRecords($gameRecords, $response_result_id) {
		$mergeGameRecords = array();
		foreach ($gameRecords as $gameRecord) {

			$sessions = $gameRecord['sessions'];

			if (!empty($sessions)) {
				foreach ($sessions as $session) {

					$rounds = $session['rounds'];

					if (!empty($rounds)) {

						foreach ($rounds as $round) {
							$transactions = $round['transactions'];
							if (!empty($transactions)) {
								foreach ($transactions as $transaction) {
									if($this->isb_operator_name != $gameRecord['operator']){
										continue; # ignore other operator
									}
									$mergeGameRecords[] = array(
										'playerid' => @$gameRecord['playerid'],
										'operator' => @$gameRecord['operator'],
										'currency' => @$gameRecord['currency'],
										'sessionid' => @$session['sessionid'],
										'gameid' => @$session['gameid'],
										'roundid' => @$round['roundid'],
										'status' => @$round['status'],
										'type' => @$transaction['type'],
										'transactionid' => @$transaction['transactionid'],
										'time' => @$this->gameTimeToServerTime($transaction['time']),
										'amount' => @$transaction['amount'],
										'balance' => @$transaction['balance'],
										'jpc' => @$transaction['jpc'],
										'jpw' => @$transaction['jpw'],
										'jpw_jpc' => @$transaction['jpw_jpc'],
										'response_result_id' => $response_result_id,
										'uniqueid' => @$transaction['transactionid'],
										'external_uniqueid' => @$transaction['transactionid'],
										'last_sync_time' => $this->utils->getCurrentDatetimeWithSeconds('Y-m-d H:i:s'),
									);
								} # foreach ($transactions as $transaction)
							} # if ( ! empty($transactions))
						} # foreach ($rounds as $round)
					} # if ( ! empty($rounds))
				} # foreach ($sessions as $session)
			} # if ( ! empty($sessions))
		} # foreach ($gameRecords as $gameRecord)

		return $mergeGameRecords;
	}

	public function convertGameAmountToDB($amount) {
		return $amount / 100;
	}

	// public function syncMergeToGameLogs($token) {
	// 	$dateTimeFrom = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
	// 	$dateTimeTo = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
	// 	$dateTimeFrom->modify($this->getDatetimeAdjust());

	// 	$this->CI->utils->debug_log('dateTimeFrom', $dateTimeFrom, 'dateTimeTo', $dateTimeTo);

	// 	$this->CI->load->model(array('game_logs', 'player_model', 'isb_game_logs', 'game_description_model'));
	// 	$result = $this->CI->isb_game_logs->getIsbGameLogStatistics($dateTimeFrom->format('Y-m-d H:i:s'), $dateTimeTo->format('Y-m-d H:i:s'));
	// 	$rlt = array('success' => true);
	// 	if ($result) {
	// 		$unknownGame = $this->getUnknownGame();
	// 		$gameDescIdMap = $this->CI->game_description_model->getGameCodeMap($this->getPlatformCode());

	// 		foreach ($result as $isbdata) {
	// 			$player_id = $this->getPlayerIdInGameProviderAuth($isbdata->player_name);
	// 			if (!$player_id) {
	// 				continue;
	// 			}

	// 			$player = $this->CI->player_model->getPlayerById($player_id);
	// 			$player_username = $player->username;

	// 			$gameDate = new \DateTime($isbdata->transaction_time);
	// 			$gameDateStr = $this->CI->utils->formatDateTimeForMysql($gameDate);
	// 			$bet_amount = $isbdata->bet_amount;
	// 			$result_amount = $isbdata->result_amount;

	// 			list($game_description_id, $game_type_id) = $this->getGameDescriptionInfo($isbdata, $unknownGame, $gameDescIdMap);
	// 			if (empty($game_description_id)) {
	// 				$this->CI->utils->debug_log('empty game_description_id , isb_game_logs_model.id=', $isbdata->id);
	// 				continue;
	// 			}
	// 			if ($bet_amount == 0 && $result_amount == 0) {
	// 				$this->CI->utils->debug_log('bet_amount and result amount is zero');
	// 				continue;
	// 			}

	// 			$this->syncGameLogs($game_type_id,
	// 				$game_description_id,
	// 				$isbdata->game_code,
	// 				$isbdata->game_type,
	// 				$isbdata->game,
	// 				$player_id,
	// 				$player_username,
	// 				$bet_amount,
	// 				$result_amount,
	// 				null, # win_amount
	// 				null, # loss_amount
	// 				null, # after_balance
	// 				0, # has_both_side
	// 				$isbdata->external_uniqueid,
	// 				$gameDateStr,
	// 				$gameDateStr,
	// 				$isbdata->response_result_id);
	// 		}
	// 	} else {
	// 		$rlt = array('success' => false);
	// 	}
	// 	return $rlt;
	// }

	public function syncMergeToGameLogs($token) {
        $this->unknownGame = $this->getUnknownGame($this->getPlatformCode());
        return $this->commonSyncMergeToGameLogs($token,
	        $this,
	        [$this, 'queryOriginalGameLogs'],
	        [$this, 'makeParamsForInsertOrUpdateGameLogsRow'],
	        [$this, 'preprocessOriginalRowForGameLogs'],
	        false
	    );

		// $this->CI->load->model(array('game_logs', 'player_model', 'isb_game_logs', 'game_description_model'));

		// $rlt = array('success' => true);

		// $dateTimeFrom = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		// $dateTimeTo = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

		// // $dateTimeFrom = new DateTime($this->serverTimeToGameTime($dateTimeFrom->format('Y-m-d H:i:s')));
		// // $dateTimeTo = new DateTime($this->serverTimeToGameTime($dateTimeTo->format('Y-m-d H:i:s')));
		// $dateTimeFrom->modify($this->getDatetimeAdjust());

		// $isb_game_logs = $this->CI->isb_game_logs->getGameLogStatistics($dateTimeFrom->format('Y-m-d H:i:s'), $dateTimeTo->format('Y-m-d H:i:s'));

		// if ($isb_game_logs) {
		// 	$count = 0;
		// 	$total = 0;
  //           $unknownGame = $this->getUnknownGame();
		// 	foreach ($isb_game_logs as $row) {

		// 		$bet_amount = $row->bet_amount;
		// 		$result_amount = $row->result_amount;
		// 		$after_balance = $row->after_balance;
				// $has_both_side = $bet_amount >= $result_amount && $result_amount > 0 ? 1 : 0;

		// 		$total += $bet_amount;
		// 		$count++;

		// 		if ($bet_amount == 0 && $result_amount == 0) {
		// 			continue;
		// 		}

		// 		$game_description = $this->CI->game_description_model->getGame(array('external_game_id' => $row->external_game_id,'game_description.game_platform_id' => $this->getPlatformCode()));
		// 		$game_description = isset($game_description[0]) ? $game_description[0] : NULL;

  //               if(empty($game_description)){
  //                   list($game_description_id, $game_type_id) = $this->getGameDescriptionInfo($row, $unknownGame);
  //               } else{
  //                   $game_description_id = $game_description->gameDescriptionId;
  //                   $game_type_id = $game_description->gameTypeId;
  //               }

  //               $extra = array('table' => $row->external_uniqueid, 'trans_amount' => $this->gameAmountToDB($bet_amount));
		// 		$this->syncGameLogs(
		// 			$game_type_id,
		// 			$game_description_id,
		// 			$game_description ? $game_description->gameCode : $unknownGame->game_code,
		// 			$game_description ? $game_description->gameType : $unknownGame->game_type_id,
		// 			$game_description ? $game_description->gameName : $unknownGame->game_name,
		// 			$row->player_id,
		// 			$row->player_username,
		// 			$this->gameAmountToDB($bet_amount),
		// 			$this->gameAmountToDB($result_amount),
		// 			NULL, # $win_amount,
		// 			NULL, # $loss_amount,
		// 			$this->gameAmountToDB($after_balance),
		// 			$has_both_side,
		// 			$row->external_uniqueid,
		// 			$row->start_at,
		// 			$row->end_at,
		// 			$row->response_result_id,
  //                   Game_logs::FLAG_GAME,
  //                   $extra
		// 		);

		// 	}

		// } else {
		// 	$rlt = array('success' => false);
		// }

		// return $rlt;

	}

	public function preprocessOriginalRowForGameLogs(array &$row){
		if (empty($row['game_description_id'])) {
            list($game_description_id,$game_type_id) = $this->getGameDescriptionInfo($row,$this->unknownGame);
            $row['game_description_id']= $game_description_id;
            $row['game_type_id'] = $game_type_id;
        }
        $row['status'] = $this->getGameRecordsStatus($row['type']);
	}

	public function makeParamsForInsertOrUpdateGameLogsRow(array $row){

		if(empty($row['md5_sum'])){
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
        }
        // $bet_amount = $row['bet_amount'];
        // $result_amount = $row['result_amount'];
        $amount = $this->gameAmountToDB($this->convertAmountToSbeMoney($row['amount']));
        $after_balance = $this->gameAmountToDB($this->convertAmountToSbeMoney($row['balance']));
        if($row['type'] == self::type_bet){
			#if type BET
			$bet_amount = $amount;
			$result_amount = -$bet_amount;
		} else {
			#if type WIN
			$bet_amount = 0;
			$result_amount = $amount;
		}
        return [
            'game_info' => [
                'game_type_id' => $row['game_type_id'],
                'game_description_id' => $row['game_description_id'],
                'game_code' => $row['external_game_id'],
                'game_type' => null,
                'game' => $row['external_game_id']
            ],
            'player_info' => [
                'player_id' => $row['player_id'],
                'player_username' => $row['game_name']
            ],
            'amount_info' => [
                'bet_amount' => $bet_amount,
                'result_amount' => $result_amount,
                'bet_for_cashback' => $bet_amount,
                'real_betting_amount' => $bet_amount,
                'win_amount' => null,
                'loss_amount' => null,
                'after_balance' => $after_balance
            ],
            'date_info' => [
                'start_at' => $row['game_date'],
                'end_at' => $row['game_date'],
                'bet_at' => $row['game_date'],
                'updated_at' => $row['game_date']
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => $row['status'],
            'additional_info' => [
                'has_both_side' => 0,
                'external_uniqueid' => $row['external_uniqueid'],
                'round_number' => $row['external_uniqueid'],
                'md5_sum' => $row['md5_sum'],
                'response_result_id' => $row['response_result_id'],
                'sync_index' => $row['id'],
                'bet_type' => null
            ],
            'bet_details' => [],
            'extra' => ['table' => $row['roundid']],
            //from exists game logs
            'game_logs_id'=>isset($row['game_logs_id']) ? $row['game_logs_id'] : null
        ];
	}

	public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time){
		$table = self::ORIGINAL_LOGS_TABLE_NAME;

		$sqlTime='`irgl`.`last_sync_time` >= ?
          AND `irgl`.`last_sync_time` <= ?';

        if($use_bet_time){
            $sqlTime = '`irgl`.`time` >= ? AND `irgl`.`time` <= ?';
        }

        $sql = <<<EOD
SELECT irgl.id as id,
irgl.playerid as game_name,
irgl.currency,
irgl.uniqueid as external_uniqueid,
irgl.transactionid,
irgl.time AS game_date,
irgl.gameid AS external_game_id,
irgl.response_result_id,
irgl.sessionid,
irgl.roundid,
irgl.status,
irgl.type,
irgl.amount,
irgl.time,
irgl.md5_sum,
irgl.balance,
irgl.result_amount,
irgl.result_balance,

game_provider_auth.player_id,
gd.id as game_description_id,
gd.game_type_id

FROM {$table} as irgl

left JOIN game_description as gd ON irgl.gameID = gd.external_game_id and gd.game_platform_id=?
JOIN game_provider_auth ON irgl.playerid = game_provider_auth.login_name and game_provider_auth.game_provider_id=?

WHERE

{$sqlTime}

EOD;
	$params=[
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo
        ];

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        // $this->processedGameRecordsBeforeMerging($result);
        return $result;
    }

    function group_by($key, $array) {
	    $result = array();
	    if(!empty($array)){
	    	foreach($array as $val) {
		        if(array_key_exists($key, $val)){
		            $result[$val[$key]][] = $val;
		        }else{
		            $result[""][] = $val;
		        }
		    }
	    }

	    return $result;
	}

	private function processedGameRecordsBeforeMerging(&$rows)
	{
		$tmpWinRecords	= [];
		$tmpBetRecords	= [];
		$gameRecords 	= [];

		if (empty($rows)) {
			return;
		}

		# collect all win record and bet reccord then put in separate array
		foreach ($rows as $rowKey => $row) {
			// if (empty($row['amount'])) { continue; }

			if (strtoupper($row['type']) == 'BET') {
				$tmpBetRecords[$row['roundid']][$row['transactionid']] = $row;
			}

			if (strtoupper($row['type']) == 'WIN') {
				$tmpWinRecords[$row['roundid']][$row['transactionid']] = $row;
			}
		}

		foreach ($tmpBetRecords as $betRecordsKey => $betRecords) {
			# check if multiple bets
			if (count($betRecords) > 1) {
				$firstKey = key($betRecords);

				foreach ($betRecords as $recordKey => $record) {
					if ($recordKey === $firstKey) { continue;	}

					$betAmount = $this->gameAmountToDB($this->convertAmountToSbeMoney($record['amount']));
					$record['result_amount'] = -$betAmount;
					$record['bet_amount'] 	= $betAmount;
					$record['after_balance'] = $this->gameAmountToDB($this->convertAmountToSbeMoney($record['balance']));
					array_push($gameRecords, $record);
					unset($betRecords[$recordKey]);
				}
			}

			# check if bet record has no corresponding win record
			if (!isset($tmpWinRecords[$betRecordsKey])) {
				foreach ($betRecords as $keyRecord => $record) {
					$betAmount = $this->gameAmountToDB($this->convertAmountToSbeMoney($record['amount']));
					$record['result_amount'] = -$betAmount;
					$record['bet_amount'] 	= $betAmount;
					$record['after_balance'] = $this->gameAmountToDB($this->convertAmountToSbeMoney($record['balance']));
					array_push($gameRecords, $record);
				}
			}
			else {
				$betRecord = array_shift($betRecords);
				$firstKey = key($tmpWinRecords[$betRecordsKey]);
				$betAmount = $this->gameAmountToDB($this->convertAmountToSbeMoney($betRecord['amount']));

				foreach ($tmpWinRecords[$betRecordsKey] as $winRecordKey => $record) {
					$winAmount = $this->gameAmountToDB($this->convertAmountToSbeMoney($record['amount']));
					$afterBalance = $this->gameAmountToDB($this->convertAmountToSbeMoney($record['balance']));

					if ($winRecordKey === $firstKey) {
						$record['bet_amount'] 	= $betAmount;
						$record['result_amount'] = $winAmount - $betAmount;
						$record['after_balance'] = $afterBalance;
						array_push($gameRecords, $record);
					}
					else {	# set as free spin
						$record['bet_amount'] 	= 0;
						$record['result_amount'] = $winAmount;
						$record['after_balance'] = $afterBalance;
						array_push($gameRecords, $record);
					}
				}

			}
		}

		$rows = $gameRecords;

		unset($gameRecords);
		unset($tmpBetRecords);
		unset($tmpWinRecords);
	}


    private function processedGameRecordsBeforeMerging_OLD(&$rows){

    	$arr_roundid = array();
    	if(!empty($rows)) {
			foreach($rows as $key => $roundid_row)
			{
			   $arr_roundid[$roundid_row['roundid']][$key] = $roundid_row; //group by sessionid
			}
		}
		ksort($arr_roundid, SORT_NUMERIC);
		$result = array();
		if(!empty($arr_roundid)){
			foreach ($arr_roundid as $key => $round_data) {
				if(!empty($round_data)){
					$type_bet = array_map(function($element){
					    return $element['type'];
					}, $round_data);
					$count_type_bet = isset(array_count_values($type_bet)['BET']) ? array_count_values($type_bet)['BET'] : 0;
					$round_data  = array_values($round_data);//reset index
					$cnt_round_data = count($round_data);
					$bet_count = 0;
					$bet = array();
					$win = array();

					$sorted_round_data = array();
					foreach ($round_data as $keyi => $value) {
						if($value['type'] == "BET"){//check if data is BET
							$bet_count++;
							$bet[$keyi] = array("bet_amount" => $value['amount'], "bet_external_uniqueid" => $value['external_uniqueid']);//set if have bet
							if($cnt_round_data == 1 || $count_type_bet > 1){//check if recieve one data only on specific round or if have multiple type bet
								$bet_amount = $value['amount'];
								$data['id'] = $value['id'];
								$data['bet_amount'] = $this->gameAmountToDB(floatval($this->convertAmountToSbeMoney(($bet_amount))));
						        $data['game_name'] = $value['game_name'];
						        $data['currency'] = $value['currency'];
						        $data['external_uniqueid'] = isset($win[$keyi-1]) ? $win[$keyi-1]['win_external_uniqueid'] :$value['external_uniqueid'];
						        $data['game_date'] = $value['game_date'];
						        $data['external_game_id'] = $value['external_game_id'];
						        $data['response_result_id'] = $value['response_result_id'];
						        $data['sessionid'] = $value['sessionid'];
						        $data['roundid'] = $value['roundid'];
						        $data['status'] = $value['status'];
						        $data['type'] = $value['type'];
						        $data['amount'] = $this->gameAmountToDB(floatval($this->convertAmountToSbeMoney(($value['amount']))));
						        $data['time'] = $value['time'];
						        $data['md5_sum'] = $value['md5_sum'];
						        $data['player_id'] = $value['player_id'];
						        $data['game_description_id'] = $value['game_description_id'];
						        $data['game_type_id'] = $value['game_type_id'];
						        $result_amount = isset($win[$keyi-1]) ? $win[$keyi-1]['win_amount'] - $bet_amount  : -$bet_amount;
						        $data['result_amount'] = $this->gameAmountToDB(floatval($this->convertAmountToSbeMoney(($result_amount))));
						        $data['after_balance'] = $this->gameAmountToDB(floatval($this->convertAmountToSbeMoney(($value['balance']))));
						        // $result[] = $data;
						        $sorted_round_data[] = $data;
						        unset($data);
							}
						} else {
							$win[$keyi] = array("win_amount" => $value['amount'], "win_external_uniqueid" => $value['external_uniqueid']);//set if  win on first array
							$bet_amount = isset($bet[$keyi-1]) ? $bet[$keyi-1]['bet_amount'] : 0; //get bet by keys mostly format is BET WIN BET WIN( get the first index)
							$data['bet_amount'] = $this->gameAmountToDB(floatval($this->convertAmountToSbeMoney(($bet_amount))));//get bet amount if have type bet
							$data['id'] = $value['id'];
					        $data['game_name'] = $value['game_name'];
					        $data['currency'] = $value['currency'];
					        $data['external_uniqueid'] = isset($bet[$keyi-1]) ? $bet[$keyi-1]['bet_external_uniqueid'] :$value['external_uniqueid'];//get bet transaction id if have type bet
					        $data['game_date'] = $value['game_date'];
					        $data['external_game_id'] = $value['external_game_id'];
					        $data['response_result_id'] = $value['response_result_id'];
					        $data['sessionid'] = $value['sessionid'];
					        $data['roundid'] = $value['roundid'];
					        $data['status'] = $value['status'];
					        $data['type'] = $value['type'];
					        $data['amount'] = $this->gameAmountToDB(floatval($this->convertAmountToSbeMoney(($value['amount']))));
					        $data['time'] = $value['time'];
					        $data['md5_sum'] = $value['md5_sum'];
					        $data['player_id'] = $value['player_id'];
					        $data['game_description_id'] = $value['game_description_id'];
					        $data['game_type_id'] = $value['game_type_id'];
					        
                            //$result_amount = ($data['amount'] == 0) ?(-$bet_amount) : isset($bet[$keyi-1])?$value['amount'] - $bet_amount : $value['amount'];//check if lose or win, lose negate and if win actual payout
                            $result_amount = 0;
                            if($data['amount'] == 0){
                                $result_amount = $bet_amount*-1;
                            }else{
                                if(isset($bet[$keyi-1])){
                                    $result_amount = $value['amount'] - $bet_amount;
                                }else{
                                    $result_amount = $value['amount'];
                                }
                            }


					        $data['result_amount'] = $this->gameAmountToDB(floatval($this->convertAmountToSbeMoney(($result_amount))));
					        $data['after_balance'] = $this->gameAmountToDB(floatval($this->convertAmountToSbeMoney(($value['balance']))));
					        // $result[] = $data;
					        if(array_search($data['external_uniqueid'] , array_column($sorted_round_data, 'external_uniqueid')) !== False) { //check if external unique id is exist and replace the first data for duplicate betting data
							    $sorted_round_data[$keyi-1] = $data;
							} else {
								$sorted_round_data[] = $data;
							}
							unset($data);
						}
					}

					$result = array_merge($result,$sorted_round_data);
					unset($sorted_round_data);
					unset($bet);

				}
			}
		}
		$unique_ids = array_unique(array_column($result, 'external_uniqueid'));
		$unique_arr = array_intersect_key($result, $unique_ids);
		$rows  = array_values($unique_arr);
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
		case 'cancel':
			$status = Game_logs::STATUS_REJECTED;
			break;
		case 'win':
		case 'bet':
			$status = Game_logs::STATUS_SETTLED;
			break;
		}
		return $status;
	}

	private function getGameDescriptionInfo($row, $unknownGame) {
		$game_description_id = null;

		$external_game_id = $row['external_game_id'];
        $extra = array('game_code' => $external_game_id);

        $game_type_id = $unknownGame->game_type_id;
        $game_type = $unknownGame->game_name;

		return $this->processUnknownGame(
			$game_description_id, $game_type_id,
			$external_game_id, $game_type, $external_game_id, $extra,
			$unknownGame);
	}

	// public function gameAmountToDB($amount) {
	// 	//only need 2
	// 	return round(floatval($amount), 2);
	// }

	//===end syncGameRecords=====================================================================================

	//===start syncBalance=====================================================================================
	//===end syncBalance=====================================================================================
}

/*end of file*/