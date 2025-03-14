<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/BaseController.php';

class Isb_seamless_service_api extends BaseController {

	private $api;
	private $player_id;
	private $currencyCode;
	private $gamePlatformId=ISB_SEAMLESS_API;

	const INIT='init';

	const ACTION_VOID='void';

	const ERROR_CODE = array(
		"ER01" => "Internal Error",
		"R_02" => "Invalid request.",
		"R_03" => "Invalid HMAC.",
		"R_09" => "Player not found.",
		"R_10" => "Game is not configured for this licensee.",
		"R_11" => "Invalid Session Id or (can occur only on init call) Session not unique",
		"R_13" => "Invalid Currency",
		"I_03" => "Invalid Token.",
		"I_04" => "Token already used.",
		"B_03" => "Insufficient Funds.",
		"B_04" => "Duplicate Transaction Id.",
		"B_05" => "Transaction has been cancelled.",
		"B_06" => "Round is closed.",
		"B_07" => "There is an active Round in current Session.",
		"W_03" => "Invalid Round.",
		"W_06" => "Duplicate Transaction Id.",
		"W_07" => "Operator should remain the same during the round.",
		"C_03" => "Invalid cancel, Transaction does not exist.",
		"C_04" => "Trying to cancel a bet from an already closed round.",
		"C_05" => "Transaction details do not match.",
	);

	const ACTION_CODE = array(
		"ER01" => "void",
		"R_02" => "void",
		"R_03" => "void",
		"R_09" => "void",
		"R_10" => "void",
		"R_11" => "void",
		"R_13" => "void",
		"I_03" => "void",
		"I_04" => "void",
		"B_03" => "continue",
		"B_04" => "void",
		"B_05" => "void",
		"B_06" => "void",
		"B_07" => "void",
		"W_03" => "void",
		"W_06" => "void",
		"W_07" => "void",
		"C_03" => "void",
		"C_04" => "void",
		"C_05" => "void",
	);

	function __construct() {
		parent::__construct();
	}

	/**
	 *
	 * @param  int $gamePlatformId @deprecated
	 * @return
	 */
	public function index($gamePlatformId=null) {
		$this->load->model(array('wallet_model','game_provider_auth','common_token','player_model',
			'isbseamless_wallet_transactions', 'agency_model', 'external_common_tokens', 'external_system'));
		$params = file_get_contents('php://input');
		$hash = $_GET["hash"];
		$this->utils->debug_log('ISB_SEAMLESS (Raw Input)', $params);
		$reqParams = json_decode($params,true);
		$is_valid=$this->getCurrencyAndValidateDB($reqParams);

		if($is_valid && !empty($this->gamePlatformId)) {
			if (!$this->external_system->isGameApiActive($this->gamePlatformId)
				|| $this->external_system->isGameApiMaintenance($this->gamePlatformId)) {
				$response = $this->returnError('R_02',self::ACTION_VOID);
				$this->utils->debug_log('ISB_SEAMLESS is inactive/maintenance (Error Response Result)', $response);
				return $this->returnJsonResult($response);
			}

			//$this->gamePlatformId is from
			$this->api = $this->utils->loadExternalSystemLibObject($this->gamePlatformId);
			//return error
			if(!empty($this->api)){
				$this->isbseamless_wallet_transactions->tableName=$this->api->transaction_table_name;
				$HMAC = $this->api->generateHMAC($params);
				$this->utils->debug_log('ISB_SEAMLESS (HMAC & Hash)', 'HMAC', $HMAC, 'Hash', $hash);

				$response=$this->processRequest($reqParams, $HMAC, $hash);
				$this->utils->debug_log('ISB_SEAMLESS (Response Result)', $response);
			}else{
				$response = $this->returnError('R_02',self::ACTION_VOID);
				$this->utils->error_log('ISB_SEAMLESS (Error Response Result)', $response);
			}
		} else {
			$response = $this->returnError('R_13',self::ACTION_VOID);
			$this->utils->error_log('ISB_SEAMLESS (Error Response Result)', $response);
		}

		return $this->returnJsonResult($response);

	}

	/**
	 * getCurrencyAndValidateDB
	 * @param  array $reqParams
	 * @return [type]            [description]
	 */
	private function getCurrencyAndValidateDB($reqParams) {
		if(isset($reqParams['state']) && $reqParams['state'] == "single") {
			if($reqParams['action']['command'] == self::INIT){
				$token=$reqParams['action']['parameters']['token'];
				$token=base64_decode($token);
				$decodeToken=json_decode($token,true);
				$this->currencyCode=strtolower($decodeToken['currency']);
				$this->gamePlatformId=$decodeToken['api'];

				# Get Currency Code for switching of currency and db forMDB
				$is_valid=$this->validateCurrencyAndSwitchDB();

				return $is_valid;
			} else {
				$this->validateCurrencyAndPlayerId($reqParams);

				# Get Currency Code for switching of currency and db forMDB
				$is_valid=$this->validateCurrencyAndSwitchDB();

				return $is_valid;
			}
		} else {
			$this->validateCurrencyAndPlayerId($reqParams);

			# Get Currency Code for switching of currency and db forMDB
			$is_valid=$this->validateCurrencyAndSwitchDB();

			return $is_valid;
		}
	}

	private function validateCurrencyAndPlayerId($reqParams) {
		$this->currencyCode=strtolower($reqParams['currency']);
		$playerId=$reqParams['playerid'];
		$playerInfo=$this->player_model->getPlayerByUsername($playerId);
		$agentId=$playerInfo->agent_id;
		$player_prefix=$this->agency_model->getPlayerPrefixByAgentId($agentId);
		// $getPlayerPrefix=substr($playerId, 0, 4);
		$multiple_currency_api_mapping = (array)@$this->utils->getConfig('isb_multiple_currency_api_mapping');
		if (array_key_exists($player_prefix,$multiple_currency_api_mapping) && !empty($multiple_currency_api_mapping)) {
		    $this->gamePlatformId  = $multiple_currency_api_mapping[$player_prefix];
		}
	}


	private function processRequest($reqParams, $HMAC, $hash) {
		$response = [];
		$success = true;
		$callMethod = null;
		# "R_10" => "Game is not configured for this licensee.",

		if($HMAC === $hash){
			try {
				$fullRequest = $reqParams;
				$username = isset($fullRequest['playerid'])?$fullRequest['playerid']:null;
				$playerId = $this->player_model->getPlayerIdByUsername($username);
				$gameUsername = $this->api->getGameUsernameByPlayerUsername($username);

				# check currency
				if(isset($reqParams['currency']) && strtolower($reqParams['currency'])!=$this->currencyCode){
					throw new Exception('R_13');
				}

				# check player if exists
				if (empty($playerId)){
					throw new Exception('R_09');
				}

				if($reqParams['state'] == 'multi'){
					foreach ($reqParams['actions'] as $req) {
						$callMethod = $req['command'];
						$response = $this->callMethod($gameUsername,$playerId,$callMethod,$req,$fullRequest);
					}
				}else{
					$callMethod = $reqParams['action']['command'];
					$req = $reqParams['action'];
					$response = $this->callMethod($gameUsername,$playerId,$callMethod,$req,$fullRequest);
				}
			}catch (Exception $e) {
				$errCode=$e->getMessage();
				if(array_key_exists($errCode, self::ERROR_CODE)){
					$this->utils->error_log('found error', $errCode, self::ERROR_CODE[$errCode]);
					$action_code = self::ACTION_CODE[$errCode];
					$response = $this->returnError($errCode,$action_code);
				}else{
					//internal error
					$this->utils->error_log('found internal exception', $e);
					$response = $this->returnError('ER01',self::ACTION_VOID);
				}
			}
		}else{
			$success = false;
			$response = $this->returnError('R_03',self::ACTION_VOID);
		}

		# save our response result and API request on our system
		$this->saveResponseResult($success,$callMethod,$reqParams,$response);

		return $response;
	}


	private function callMethod($username,$playerId,$callMethod,$actions,$fullRequest=null){
		switch ($callMethod){
			case 'init':
/*
{
    "allow_open_rounds": false,
    "sessionid": "1606577429X40ag1dUW5aVjqh0oBrMFR",
    "skinid": "909094",
    "playerid": "lmct2008474Eo",
    "operator": "LemacauIDR",
    "action": {
        "command": "init",
        "parameters": {
            "token": "eyJhcGkiOjI0MDIsImN1cnJlbmN5IjoiSURSIiwidG9rZW4iOiJiYTBhMjQ5OTBmM2ZiNGIzNWIxNDkyZGY3ZDUwZDZhZiIsInNpZ25hdHVyZSI6IjU3NGM5YTcwMzJhMzJmMDkxNDE1N2ZlN2NhYzEwZjQ3In0=",
            "country": "CN",
            "game_type": "flash"
        }
    },
    "state": "single"
}
*/
				return $this->playerInit($username,$playerId,$actions,$fullRequest);
/*
{
    "status": "success",
    "playerid": "lmct2008474Eo",
    "sessionid": "1606624419dCgPcGpb2MTSwj3FIz5Q7o",
    "currency": "IDR",
    "balance": 150000000
}
 */
				break;
			case 'token':
/*
{
    "allow_open_rounds": false,
    "currency": "IDR",
    "sessionid": "1606624419dCgPcGpb2MTSwj3FIz5Q7o",
    "skinid": "909094",
    "playerid": "lmct2008474Eo",
    "operator": "LemacauIDR",
    "action": {
        "command": "token"
    },
    "state": "single"
}
 */
				return $this->getToken($username,$playerId,$actions,$fullRequest);
/*
{
    "status": "success",
    "token": "eyJhcGkiOjI0MDIsImN1cnJlbmN5IjoiaWRyIiwidG9rZW4iOiJhYjJkYjVmODlhNjdkNGMxMzQyYzFkZjViZjA1MTUxNiIsInNpZ25hdHVyZSI6ImFmNjU2OWQ0YmYyYWY3YTRhZDVmYjU2ODg2NzM2ZWMwIn0="
}
 */
				break;
			case 'balance':
				return $this->getbalance($username,$playerId,$actions,$fullRequest);
				break;
			case 'bet':
/*
{
    "allow_open_rounds": false,
    "currency": "IDR",
    "sessionid": "1606630357ofLPIh49pa1xjDEiqfwvi7",
    "skinid": "909094",
    "playerid": "lmct2008474Eo",
    "operator": "LemacauIDR",
    "action": {
        "command": "bet",
        "parameters": {
            "transactionid": "1606630405992358079897295513392",
            "roundid": "8379094lmct2008474Eo1606630405165464001357503465",
            "amount": 100000,
            "jpc": 0
        }
    },
    "state": "single"
}
 */
				return $this->playerBet($username,$playerId,$actions,$fullRequest);
/*
{
    "status": "success",
    "balance": 1400000,
    "currency": "idr"
}
 */
				break;
			case 'win':
/*
loss:
{
    "allow_open_rounds": false,
    "currency": "IDR",
    "sessionid": "1606630357ofLPIh49pa1xjDEiqfwvi7",
    "skinid": "909094",
    "playerid": "lmct2008474Eo",
    "operator": "LemacauIDR",
    "action": {
        "command": "win",
        "parameters": {
            "transactionid": "1606630406670390209587589730531",
            "roundid": "8379094lmct2008474Eo1606630405165464001357503465",
            "amount": 0,
            "jpw": 0,
            "jpw_from_jpc": 0,
            "closeround": true,
            "froundid": 0
        }
    },
    "state": "single"
}
win:
{
    "allow_open_rounds": false,
    "currency": "IDR",
    "sessionid": "1606630357ofLPIh49pa1xjDEiqfwvi7",
    "skinid": "909094",
    "playerid": "lmct2008474Eo",
    "operator": "LemacauIDR",
    "action": {
        "command": "win",
        "parameters": {
            "transactionid": "1606630414775202301957017959152",
            "roundid": "8379094lmct2008474Eo1606630414506925543323774277",
            "amount": 50000,
            "jpw": 0,
            "jpw_from_jpc": 0,
            "closeround": true,
            "froundid": 0
        }
    },
    "state": "single"
}
 */
				return $this->playerWin($username,$playerId,$actions,$fullRequest);
/*
{
    "status": "success",
    "balance": 1400000,
    "currency": "IDR"
}
 */
				break;
			case 'transactiondetails':
				# code...
				break;
			case 'rounddetails':
				# code...
				break;
			case 'depositmoney':
				return $this->depositMoney($username,$playerId,$actions,$fullRequest);
				break;
			case 'cancel':
				return $this->canceTransaction($username,$playerId,$actions,$fullRequest);
			case 'end':
/*
{
    "allow_open_rounds": false,
    "currency": "IDR",
    "sessionid": "1606624419dCgPcGpb2MTSwj3FIz5Q7o",
    "skinid": "909094",
    "playerid": "lmct2008474Eo",
    "operator": "LemacauIDR",
    "action": {
        "command": "end",
        "parameters": {
            "sessionstatus": "INACTIVE"
        }
    },
    "state": "single"
}
 */
				return $this->endPlayerSession($username,$playerId,$actions,$fullRequest);
/*
{
    "status": "success",
    "balance": 1500000,
    "currency": "IDR"
}
 */
				break;
			case 'dialog':
				# code...
				break;

			default:
				throw new Exception('R_02');
				break;
		}
	}

	private function endPlayerSession($username,$playerId,$reqParams,$fullRequest){
		# load external_common_tokens model
		$this->load->model(array('external_common_tokens'));
		$sessionId = $fullRequest['sessionid'];
		$sessionStatus = $reqParams['parameters']['sessionstatus'];

		# Update status base on GP status
		$this->external_common_tokens->updatePlayerExternalTokenStatus($playerId, $sessionId, $sessionStatus);
		# get player balance
		$playerBalance = $this->getbalance($username,$playerId,$reqParams,$fullRequest);

		$response = array(
			"status" => 'success',
			"balance" => $playerBalance['balance'],
			"currency" => strtoupper($this->currencyCode),
		);

		return $response;
	}

	private function canceTransaction($username,$playerId,$reqParams,$fullRequest){

		$trans_code = [
			'trans_code' => 'success'
		];
		$controller = $this;
		$success = $this->lockAndTransForPlayerBalance($playerId, function() use($controller, $username, &$trans_code, $reqParams, $fullRequest, $playerId){
			$betAmount = @$reqParams['parameters']['amount'];
			$transactionid = @$reqParams['parameters']['transactionid'];
			$roundid = @$reqParams['parameters']['roundid'];
			$froundid = isset($reqParams['parameters']['froundid'])?$reqParams['parameters']['froundid']:0;
			$sessionid = isset($fullRequest['sessionid'])?$fullRequest['sessionid']:null;
			$skinid = isset($fullRequest['skinid'])?$fullRequest['skinid']:null;
			$operator = isset($fullRequest['operator'])?$fullRequest['operator']:null;
			$currency = isset($fullRequest['currency'])?$fullRequest['currency']:null;

			$insertData[] = array(
				"username" => $username,
				"sessionid" => $sessionid,
				"skinid" => $skinid,
				"transactionid" => $transactionid,
				"roundid" => $roundid,
				"amount" => $this->api->convertAmountToDB($betAmount),
				"jpc" => @$reqParams['parameters']['jpc'],
				"command" => @$reqParams['command'],
				"created_at" => date('Y-m-d H:i:s'),
				"updated_at" => date('Y-m-d H:i:s'),
				"external_uniqueid" => @$reqParams['command'] . $transactionid,
				"transaction_status" => "cancelled",
				"currency" => $currency,
				"operator" => $operator
			);

			$existingRow = $this->isbseamless_wallet_transactions->isRowByTransactionId($transactionid);

			# throw error if transaction doesnt exists.
			if(empty($existingRow)){
				$this->utils->debug_log('ISB_SEAMLESS (Player Bet Data)', 'Bet Data', $insertData);
				$this->isbseamless_wallet_transactions->insertGameLogs($insertData[0]);
				$trans_code['trans_code'] = 'C_03';
				// throw new Exception('C_03');
				return false;
			}

			# check if transaction params is same on existing one.
			foreach($reqParams['parameters'] as $key => $val){
				if($key == "amount") {
					$val=$this->api->convertAmountToDB($val);
				}
				if($existingRow[$key] != $val){
					if($key == "timestamp"){
						continue;
					}
					# transaction details do not match.
					$trans_code['trans_code'] = 'C_05';
					// throw new Exception('C_05');
					return false;
				}
			}

			$dataUpate = array(
				"transaction_status" => "cancelled"
			);

			# check if bet has already a successful win
			# if already has win dont cancel return C_04
			$existingRowWin = $this->isbseamless_wallet_transactions->isRowByRoundIdAndWin($roundid);
			if(!empty($existingRowWin)){
				$trans_code['trans_code'] = 'C_04';
				// throw new Exception('C_04');
				return false;
			}

			# refund bet amount due to cancelation
			# if already canceled don't refund balance
			if($existingRow['transaction_status']!="cancelled"){
				$betAmount=$this->api->convertAmountToDB($betAmount);
				$this->add_amount($playerId, $betAmount);
				$playerBalance = $this->getPlayerBalance($username,$playerId);
				$dataUpate['after_balance'] = $this->api->convertAmountToDB($playerBalance);
				# update transaction to cancel
				$this->isbseamless_wallet_transactions->updateGameLog($existingRow['id'],$dataUpate);
			}else{
				# check if round is already closed.
				$trans_code['trans_code'] = 'success';
				$trans_code['balance'] = $this->api->dBtoGameAmount($existingRow['after_balance']);
				// $response = array(
				// 	"status" => 'success',
				// 	"balance" => $this->api->dBtoGameAmount($existingRow['after_balance']),
				// 	"currency" => strtoupper($this->currencyCode),
				// );
				return true;
			}

			$playerBalance = $this->getbalance($username,$playerId,$reqParams,$fullRequest);

			$trans_code['balance'] = $playerBalance['balance'];

			return true;
		});


		$response = array(
			"status" => $trans_code['trans_code'],
			"balance" => $trans_code['balance'],
			"currency" => strtoupper($this->currencyCode),
		);

		return $response;
	}

	/**
	 * playerInit
	 *
	 * @param  string $username
	 * @param  int $playerId
	 * @param  array $reqParams
	 * @param  array $fullRequest
	 * @return array
	 */
	private function playerInit($username,$playerId,$reqParams,$fullRequest){
		$token = isset($reqParams['parameters']['token'])?$reqParams['parameters']['token']:null;
		$decodeBaseToken=base64_decode($token);
		$decodeToken=json_decode($decodeBaseToken,true);
		$token=$decodeToken['token'];
		$playerIdByToken = $this->api->getPlayerIdByToken($token);
		$playerName = $this->player_model->getUsernameById($playerId);
		$this->utils->debug_log('ISB_SEAMLESS (PlayerInit token)', 'Decode Base', $decodeBaseToken, 'Json Decode', $decodeToken, 'Player Token', $token, 'playerId', $playerIdByToken);
		if(!empty($playerIdByToken)){
			# load external_common_tokens model
			$this->load->model(array('external_common_tokens'));

			$activeSession = $this->external_common_tokens->checkExpiredExternalToken($playerId,$this->gamePlatformId,$fullRequest['sessionid']);
			# get sessionid, check if token is expired, if yes return error R_11
			if(!empty($activeSession)){
				throw new Exception('R_11');
			}

			# add sessionid on external_common_tokens table
			$this->external_common_tokens->addPlayerToken($playerId,$fullRequest['sessionid'],$this->gamePlatformId,$this->currencyCode);

			$playerBalance = $this->getbalance($username,$playerId,$reqParams,$fullRequest);
			$response = array(
				"status" => 'success',
				"playerid" => $playerName,
				"sessionid" => $fullRequest['sessionid'],
				"currency" => strtoupper($this->currencyCode),
				"balance" => $playerBalance['balance'],
			);
			return $response;
		}else{
			throw new Exception('I_03');
		}

	}

	private function getToken($username,$playerId,$reqParams,$fullRequest){
		$newToken = $this->common_token->createTokenWithSignKeyBy($playerId, 'player_id');

		$hashToken=array(
    		'currency'=>$this->currencyCode,
    		'api'=>$this->gamePlatformId,
    		'token'=>$newToken[0]
    	);

    	ksort($hashToken);
    	$hashToken['signature']=md5(json_encode($hashToken).$this->api->key);
    	$token=base64_encode(json_encode($hashToken));

		$response = array(
			"status" => 'success',
			"token" => $token
		);

		return $response;
	}

	private function getbalance($username,$playerId,$reqParams,$fullRequest){
		$balance = 0;
		$trans_success = $this->lockAndTransForPlayerBalance($playerId, function() use($playerId,
			$username,&$balance) {

				$balance = $this->getPlayerBalance($username, $playerId);
				if($balance===false){
					return false;
				}

				return true;
			});

		$response = array(
			"status" => 'success',
			"balance" => $balance,
			"currency" => strtoupper($this->currencyCode),
		);

		return $response;
	}

	private function getPlayerBalance($username, $playerId){
		if($this->utils->getConfig('enable_seamless_single_wallet')){
			$seamless_balance = 0;
			$seamless_reason_id = null;
			$seamless_wallet = $this->wallet_model->querySeamlessSingleWallet($playerId, $seamless_balance, $seamless_reason_id);
            if(!$seamless_wallet){
				return false;
            }else{
				return $this->api->dBtoGameAmount($seamless_balance);
			}
		} else {
			$playerBalance = $this->api->queryPlayerBalance($username);

			return $this->api->dBtoGameAmount($playerBalance['balance']);
		}
	}

	private function playerBet($username,$playerId,$reqParams,$fullRequest){
		# "B_06" => "Round is closed.",
		# "B_07" => "There is an active Round in current Session.",
		$trans_code = [
			'trans_code' => 'success'
		];
		//pre check amount
		$betAmount = @$reqParams['parameters']['amount'];
		if($betAmount<0){
			//return error
			throw new Exception('R_02');
		}

		$controller = $this;
		$success = $this->lockAndTransForPlayerBalance($playerId, function() use($controller, $username, &$trans_code, $reqParams, $fullRequest, $playerId) {
			$transactionid = @$reqParams['parameters']['transactionid'];
			$roundid = @$reqParams['parameters']['roundid'];
			$betAmount = @$reqParams['parameters']['amount'];
			$froundid = isset($reqParams['parameters']['froundid'])?$reqParams['parameters']['froundid']:0;
			$sessionid = isset($fullRequest['sessionid'])?$fullRequest['sessionid']:null;
			$skinid = isset($fullRequest['skinid'])?$fullRequest['skinid']:null;
			$operator = isset($fullRequest['operator'])?$fullRequest['operator']:null;
			$currency = isset($fullRequest['currency'])?$fullRequest['currency']:null;

			$insertData[] = array(
				"username" => $username,
				"sessionid" => $sessionid,
				"skinid" => $skinid,
				"transactionid" => $transactionid,
				"roundid" => $roundid,
				"amount" => $this->api->convertAmountToDB($betAmount),
				"jpc" => @$reqParams['parameters']['jpc'],
				"froundid" => $froundid,
				"fround_coin_value" => @$reqParams['parameters']['fround_coin_value'],
				"fround_lines" => @$reqParams['parameters']['fround_lines'],
				"fround_line_bet" => @$reqParams['parameters']['fround_line_bet'],
				"timestamp" => @$reqParams['parameters']['timestamp'],
				"command" => @$reqParams['command'],
				"created_at" => date('Y-m-d H:i:s'),
				"updated_at" => date('Y-m-d H:i:s'),
				"external_uniqueid" => @$reqParams['command'] . $transactionid,
				"currency" => $currency,
				"operator" => $operator
			);
			$activeSession = $this->external_common_tokens->checkActiveExternalToken($playerId,$this->gamePlatformId,$sessionid);
			# get sessionid, check if already exist , if yes return error R_11
			if(empty($activeSession)){
				$trans_code['trans_code'] = 'R_11';
				return false;
				// throw new Exception('R_11');
			}

			$existingRow = $this->isbseamless_wallet_transactions->isRowByTransactionId($transactionid);
			// print_r($existingRow);exit;
			if(!empty($existingRow)){
				# check if transaction params is same on existing one.
				foreach($reqParams['parameters'] as $key => $val){
					if($key == "amount") {
						$val=$this->api->convertAmountToDB($val);
					}
					if($existingRow[$key] != $val){
						if($key == "timestamp"){
							continue;
						}
						$trans_code['trans_code'] = 'B_04';
						return false;
						// throw new Exception('B_04');
					}
				}

				# check if transaction is cancelled
				if($existingRow['transaction_status'] == "cancelled"){
					$trans_code['trans_code'] = 'B_05';
					return false;
					// throw new Exception('B_05');
				}

				# if same transaction return same value as berfore.
				$trans_code['trans_code'] = 'success';
				$trans_code['balance'] = $this->api->dBtoGameAmount($existingRow['after_balance']);
				// $response = array(
				// 	"status" => 'success',
				// 	"balance" => $this->api->dBtoGameAmount($existingRow['after_balance']),
				// 	"currency" => strtoupper($this->currencyCode),
				// );

				return true;

			}

			# check if Round is Closed.
			# CODE
			# if yes, return error B_06 Round is closed.
			#

			# Check if there is another open round in current session
			# CODE
			# if yes, return error B_07 There is an active Round in current Session.
			#

			$queryPlayerBalanceResult = $this->getPlayerBalance($username,$playerId);

			if (($queryPlayerBalanceResult - $betAmount) < 0) {
				$trans_code['trans_code'] = 'B_03';
				return false;
				// throw new Exception('B_03');
			}

			# include before balance on gamelogs
			$insertData[0]['before_balance'] = $this->api->convertAmountToDB($queryPlayerBalanceResult);

			# if froundid > 0 , this is free spin don't deduct money from player balance.
			if($froundid == 0){
				$betAmount= $this->api->convertAmountToDB($betAmount);
				$this->subtract_amount($playerId, $betAmount);
			}

			$playerBalance = $this->getPlayerBalance($username,$playerId,$reqParams,$fullRequest);

			# include after balance on gamelogs
			$insertData[0]['after_balance'] = $this->api->convertAmountToDB($playerBalance);

			$trans_code['balance'] = $playerBalance;

			# insert gamelogs to isbseamless_wallet_transactions table
			$this->utils->debug_log('ISB_SEAMLESS (Player Bet Data)', 'Bet Data', $insertData);
			$this->isbseamless_wallet_transactions->insertGameLogs($insertData[0]);
			return true;

		});

		if($trans_code['trans_code'] != 'success') {
			throw new Exception($trans_code['trans_code']);
		}


		$response = array(
			"status" => $trans_code['trans_code'],
			"balance" => $trans_code['balance'],
			"currency" => $this->currencyCode,
		);

		return $response;

	}

	private function playerWin($username,$playerId,$reqParams,$fullRequest){
		#"W_03" => "Invalid Round.",
		#"W_07" => "Operator should remain the same during the round.",

		$trans_code = [
			'trans_code' => 'success'
		];
		//pre check amount
		$betAmount = @$reqParams['parameters']['amount'];
		if($betAmount<0){
			//return error
			throw new Exception('R_02');
		}

		$controller = $this;
		$success = $this->lockAndTransForPlayerBalance($playerId, function() use($controller, $username, &$trans_code, $reqParams, $fullRequest, $playerId){
			$winAmount = @$reqParams['parameters']['amount'];
			$transactionid = @$reqParams['parameters']['transactionid'];
			$roundid = @$reqParams['parameters']['roundid'];
			$froundid = isset($reqParams['parameters']['froundid'])?$reqParams['parameters']['froundid']:0;
			$sessionid = isset($fullRequest['sessionid'])?$fullRequest['sessionid']:null;
			$skinid = isset($fullRequest['skinid'])?$fullRequest['skinid']:null;
			$operator = isset($fullRequest['operator'])?$fullRequest['operator']:null;
			$currency = isset($fullRequest['currency'])?$fullRequest['currency']:null;

			$insertData[] = array(
				"username" => $username,
				"sessionid" => $sessionid,
				"skinid" => $skinid,
				"transactionid" => $transactionid,
				"roundid" => $roundid,
				"amount" => $this->api->convertAmountToDB($winAmount),
				"jpw" => @$reqParams['parameters']['jpw'],
				"closeround" => @$reqParams['parameters']['closeround'],
				"jpw_from_jpc" => @$reqParams['parameters']['jpw_from_jpc'],
				"froundid" => @$reqParams['parameters']['froundid'],
				"timestamp" => @$reqParams['parameters']['timestamp'],
				"command" => @$reqParams['command'],
				"created_at" => date('Y-m-d H:i:s'),
				"updated_at" => date('Y-m-d H:i:s'),
				"external_uniqueid" => @$reqParams['command'] . $transactionid,
				"currency" => $currency,
				"operator" => $operator
			);


			$existingRow = $this->isbseamless_wallet_transactions->isRowByTransactionId($transactionid);
			if(!empty($existingRow)){
				# check if transaction params is same on existing one.
				foreach($reqParams['parameters'] as $key => $val){
					if($key == "amount") {
						$val=$this->api->convertAmountToDB($val);
					}
					if($existingRow[$key] != $val){
						if($key == "timestamp"){
							continue;
						}
						$trans_code['trans_code'] = 'W_06';
						return false;
						// throw new Exception('W_06'); # dublicate transactionid
					}
				}

				# if same transaction return same value as berfore.
				$trans_code['trans_code'] = 'success';
				$trans_code['balance'] = $this->api->dBtoGameAmount($existingRow['after_balance']);
				// $response = array(
				// 	"status" => 'success',
				// 	"balance" => $this->api->dBtoGameAmount($existingRow['after_balance']),
				// 	"currency" => strtoupper($this->currencyCode),
				// );

				return true;
			}

			$getExistingBet = $this->isbseamless_wallet_transactions->isRowByRoundId($roundid);
			# get sessionid currency, check if session's currency is same with currencyCode
			if(!empty($getExistingBet)) {
				if(strtolower($getExistingBet['currency']) != strtolower($this->currencyCode)){
					$trans_code['trans_code'] = 'R_13';
					return false;
					// throw new Exception('R_13');
				}
				if($getExistingBet['operator'] != $operator) {
					$trans_code['trans_code'] = 'W_07';
					return false;
					// throw new Exception('W_07');
				}
			}

			# check if round exists and is not inactive and has got a bet with transaction status OK
			#
			# CODE
			# if no, return error W_03
			#

			# check if round already belongs to current session
			# check if operator is the same as for the previous session
			# if no, return error W_07
			#

			# check if closeround parameters is true
			# if yes, set round status to INACTIVE

			$playerBalance = $this->getPlayerBalance($username,$playerId);

			# include before balance on gamelogs
			$insertData[0]['before_balance'] = $this->api->convertAmountToDB($playerBalance);

			# add balance to Wallet
			$winAmount=$this->api->convertAmountToDB($winAmount);
			$this->add_amount($playerId, $winAmount);

			$playerBalance = $this->getPlayerBalance($username,$playerId);

			# include after balance on gamelogs
			$insertData[0]['after_balance'] = $this->api->convertAmountToDB($playerBalance);

			$trans_code['balance'] = $playerBalance;

			# insert gamelogs to isbseamless_wallet_transactions table
			$this->utils->debug_log('ISB_SEAMLESS (Player Bet Data)', 'Bet Data', $insertData);
			$this->isbseamless_wallet_transactions->insertGameLogs($insertData[0]);
			return true;
		});

		if($trans_code['trans_code'] != 'success') {
			throw new Exception($trans_code['trans_code']);
		}

		$response = array(
			"status" => $trans_code['trans_code'],
			"balance" => $trans_code['balance'],
			"currency" => strtoupper($this->currencyCode),
		);

		return $response;
	}

	private function depositmoney($username,$playerId,$reqParams,$fullRequest){

		$trans_code = [
			'trans_code' => 'success'
		];
		$controller = $this;
		$success = $this->lockAndTransForPlayerBalance($playerId, function() use($controller, $username, &$trans_code, $reqParams, $fullRequest, $playerId){
			$amount = isset($reqParams['parameters']['amount'])?$reqParams['parameters']['amount']:0;
			$amount = $controller->api->convertAmountToDB($amount);
			$controller->add_amount($playerId,$amount);
			$playerBalance = $controller->getPlayerBalance($username,$playerId);
			$trans_code['balance'] = $playerBalance;
			return true;
		});

		$response = array(
			"status" => $trans_code['trans_code'],
			"balance" => $trans_code['balance'],
			"currency" => strtoupper($this->currencyCode),
		);

		return $response;

	}

	private function subtract_amount($playerId, $amount) {
		// $lockedKey = NULL;
		// $lock_it = $this->utils->lockResourceBy($playerId, Utils::LOCK_ACTION_BALANCE, $lockedKey);
		// $this->utils->debug_log('lock subtract_amount', 'id', $playerId, $lock_it);

		// if ($lock_it) {
		// 	try {
		// 		$this->startTrans();
				if($this->utils->getConfig('enable_seamless_single_wallet')){
					$reason_id=Abstract_game_api::REASON_UNKNOWN;
					$this->wallet_model->transferSeamlessSingleWallet($playerId, Wallet_model::TRANSFER_TYPE_OUT, $amount, $reason_id);
				} else {
					$this->wallet_model->decSubWallet($playerId, $this->gamePlatformId, $amount);
				}
			// 	$this->endTransWithSucc();
			// } finally {
			// 	$this->utils->releaseResourceBy($playerId, Utils::LOCK_ACTION_BALANCE, $lockedKey);
			// 	$this->utils->debug_log('release subtract_amount lock', 'id', $playerId);
			// }
		// }
	}

	private function add_amount($playerId, $amount) {
		// $lockedKey = NULL;
		// $lock_it = $this->utils->lockResourceBy($playerId, Utils::LOCK_ACTION_BALANCE, $lockedKey);
		// $this->utils->debug_log('lock add_amount', 'id', $playerId, $lock_it);

		// if ($lock_it) {
		// 	try {
		// 		$this->startTrans();
				if($this->utils->getConfig('enable_seamless_single_wallet')){
					$reason_id=Abstract_game_api::REASON_UNKNOWN;
					$this->wallet_model->transferSeamlessSingleWallet($playerId, Wallet_model::TRANSFER_TYPE_IN, $amount, $reason_id);
				} else {
					$this->wallet_model->incSubWallet($playerId, $this->gamePlatformId, $amount);
				}
		// 		$this->endTransWithSucc();
		// 	} finally {
		// 		$this->utils->releaseResourceBy($playerId, Utils::LOCK_ACTION_BALANCE, $lockedKey);
		// 		$this->utils->debug_log('release add_amount lock', 'id', $playerId);
		// 	}
		// }
	}


    private function returnError($errorCode,$action=null) {
    	$action=isset($action) ? $action : "continue";
		$response = array(
			"status" => "error",
			"code" => $errorCode,
			"message" => self::ERROR_CODE[$errorCode],
			"action" => $action, //optional
			# "display" => true //optional
		);

		return $response;
	}

	private function saveResponseResult($success, $callMethod, $params, $response){
        //save to db
        $this->CI->load->model("response_result");
        $flag = $success ? Response_result::FLAG_NORMAL : Response_result::FLAG_ERROR;
        return $this->CI->response_result->saveResponseResult($this->gamePlatformId, $flag, $callMethod, json_encode($params), $response, 200, null, null);
    }

    protected function validateCurrencyAndSwitchDB(){
        if(!$this->utils->isEnabledMDB()){
            return true;
        }
        if(empty($this->currencyCode)){
            return false;
        }else{
            //validate currency name
            if(!$this->utils->isAvailableCurrencyKey($this->currencyCode)){
                //invalid currency name
                return false;
            }else{
                //switch to target db
                $_multiple_db=Multiple_db::getSingletonInstance();
                $_multiple_db->switchCIDatabase($this->currencyCode);
                return true;
            }
        }
    }


}

///END OF FILE////////////