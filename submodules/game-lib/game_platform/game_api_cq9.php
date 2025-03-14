<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
/**
	* API DOCS NAME: Transfer Wallet GameBoy
	* Document Number: none
	* API Doc: https://hackmd.io/s/ryWBwyI4M#Header
	*
	* @category Game_platform
	* @version 1.8.10
	* @copyright 2013-2022 tot
**/

class game_api_cq9 extends Abstract_game_api {
    public $original_table;

	const POST = "POST";
	const GET = "GET";
	const API_getGameProviderList = "getGameProviderList";
	const API_getCq9GameList = "API_getCq9GameList";
	const API_queryLaunchLobby = "API_queryLaunchLobby";
	const PAGE_SIZE = 20000;
	const FISHING_GAME = 'fishing_game';
	const API_SUCCESS_RESPONSE = '0';
    const API_ACCOUNT_ALREADY_EXISTS = '6';

    const MD5_FIELDS_FOR_ORIGINAL = [
        'gamehall',
        'gametype',
        'gameplat',
        'gamecode',
        'account',
        'round',
        'balance',
        'win',
        'bet',
        'jackpot',
        'status',
        'endroundtime',
        'createtime',
        'bettime',
        'rake',
        'roomfee',
        'validbet',
    ];

    const MD5_FLOAT_AMOUNT_FIELDS = [
        'balance',
        'win',
        'bet',
        'validbet',
        'jackpot',
        'rake',
        'roomfee',
    ];

    const MD5_FIELDS_FOR_MERGE = [
        'gamehall',
        'game_type',
        'gameplat',
        'game_code',
        'username',
        'round_number',
        'after_balance',
        'win_amount',
        'bet_amount',
        'jackpot',
        'cq9_status',
        'end_at',
        'start_at',
        'bet_at',
        'jackpot',
        'rake',
        'roomfee',
        'valid_bet',
    ];

    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE = [
        'after_balance',
        'win_amount',
        'bet_amount',
        'valid_bet',
        'jackpot',
        'rake',
        'roomfee',
    ];

	public function __construct() {
		parent::__construct();

        $this->original_table = 'cq9_game_logs';
		$this->api_url = $this->getSystemInfo('url');
		$this->api_token = $this->getSystemInfo('api_token');
		$this->gamehall = $this->getSystemInfo('gamehall', 'cq9');

		$this->method = "POST";
		$this->URI_MAP = array(
			self::API_createPlayer => '/gameboy/player',
			self::API_login => '/gameboy/player/login',
			self::API_logout => '/gameboy/player/logout',
			self::API_queryPlayerBalance => '/gameboy/player/balance',
			self::API_isPlayerExist => '/gameboy/player/check',
			self::API_depositToGame => '/gameboy/player/deposit',
			self::API_withdrawFromGame => '/gameboy/player/withdraw',
			self::API_queryTransaction => '/gameboy/transaction/record',
			self::API_changePassword => '/gameboy/player/pwd',
			self::API_getGameProviderList => '/gameboy/game/halls',
			self::API_getCq9GameList => '/gameboy/game/list',
			self::API_queryForwardGame => '/gameboy/player/gamelink',
			self::API_syncGameRecords => '/gameboy/order/view',
			self::API_queryLaunchLobby => '/gameboy/player/lobbylink',
            self::API_queryBetDetailLink => '/gameboy/order/detail/v2',
			self::API_queryGameListFromGameProvider => '/gameboy/game/list/',

		);
	}

	public function getPlatformCode() {
		return CQ9_API;
	}

	protected function customHttpCall($ch, $params) {
		if($this->method == self::POST){
			curl_setopt($ch, CURLOPT_POST, TRUE);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
		}

		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);  	# fix for 301 moved permanently.
		curl_setopt($ch, CURLOPT_MAXREDIRS, 10);  			# prevent calling recursively.
	}

	protected function getHttpHeaders($params){
		switch ($this->method) {
			case self::GET:
				$contentType = 'application/json';
				break;

			default:
				$contentType = 'application/x-www-form-urlencoded';
				break;
		}

		$headers = array(
			'Content-type' => $contentType,
			'Authorization' => $this->api_token
		);


		return $headers;
	}

	public function generateUrl($apiName, $params) {


		$apiUri = $this->URI_MAP[$apiName];
		$url = $this->api_url.$apiUri;
		if($this->method == self::GET){
			if($apiName == self::API_queryPlayerBalance){
				$url = $this->api_url.$apiUri."/".$params["account"];
			}
			if($apiName == self::API_isPlayerExist){
				$url = $this->api_url.$apiUri."/".$params["account"];
			}
			if($apiName == self::API_queryTransaction){
				$url = $this->api_url.$apiUri."/".$params["mtcode"];
			}
			if($apiName == self::API_getCq9GameList){
				$url = $this->api_url.$apiUri."/".$params["gamehall"];
			}
			if($apiName == self::API_syncGameRecords){
				$url = $this->api_url.$apiUri."?".http_build_query($params);
			}
			if($apiName == self::API_queryBetDetailLink){
                $url = $this->api_url.$apiUri."?".http_build_query($params);
            }
			if($apiName == self::API_queryGameListFromGameProvider){
				$url = $this->api_url.$apiUri."/".$params["gamehall"];
            }
		}

		if($apiName == self::API_queryGameListFromGameProvider && $this->method == self::POST){
			$this->method = self::GET;
			$url = $this->api_url.$apiUri."/".$params["gamehall"];
		}

		return $url;
	}

	public function processResultBoolean($responseResultId, $resultArr, $playerName = null) {
		$success = false;

		if(isset($resultArr['status']['code']) && ($resultArr['status']['code'] === self::API_SUCCESS_RESPONSE || $resultArr['status']['code'] === self::API_ACCOUNT_ALREADY_EXISTS)) {
			$success = true;
		}

		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('CQ9_API API got error ', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
		}

		return $success;
	}

	public function getGameProviderList(){
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForGetGameProviderList'
		);

		$this->method = self::GET;

		return $this->callApi(self::API_getGameProviderList, array(), $context);
	}

	public function processResultForGetGameProviderList($params){
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr);
		$result = [];
		if($success){
			$result["providers"] = $resultArr["data"];
		}

		return array($success, $result);
	}

	public function getCq9GameList($gamehall){
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForGetGameList'
		);

		$params = array(
			'gamehall' => $gamehall
		);

		$this->method = self::GET;

		return $this->callApi(self::API_getCq9GameList, $params, $context);
	}

	public function processResultForGetGameList($params){
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr);
		$result = [];
		if($success){
			$result["gamelist"] = $resultArr["data"];
		}

		return array($success, $result);
	}

	public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
		parent::createPlayer($playerName, $playerId, $password, $email, $extra);
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForCreatePlayer',
			'playerName' => $playerName,
			'playerId' => $playerId,
			'gameUsername' => $gameUsername
		);

		$params = array(
			'account' => $gameUsername,
			'password' => $password,
			'nickname' => $gameUsername,
		);

		$this->method = self::POST;

		return $this->callApi(self::API_createPlayer, $params, $context);
	}

	public function processResultForCreatePlayer($params){
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerId = $this->getVariableFromContext($params, 'playerId');
		$success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);

		$result = array(
			"player" => $gameUsername,
			"exists" => false
		);

		if($success){
			# update flag to registered = truer
	        $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
	        $result["exists"] = true;
		}else{
			$result["message"] = @$resultArr["status"]["message"];
		}

		return array($success, $result);
	}

	public function changePassword($playerName, $oldPassword = null, $newPassword) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForChangePassword',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername,
			'newPassword' => $newPassword
		);

		$params = array(
			'account' => $gameUsername,
			'password' => $newPassword,
		);

		$this->method = self::POST;

		return $this->callApi(self::API_changePassword, $params, $context);

	}

	public function processResultForChangePassword($params){
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$newPassword = $this->getVariableFromContext($params, 'newPassword');

		$success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);
		if ($success) {
			$playerId = $this->getPlayerIdInPlayer($playerName);
			//sync password to game_provider_auth
			$this->updatePasswordForPlayer($playerId, $newPassword);
		}

		return array($success, $resultArr);
	}

	public function queryPlayerBalance($playerName) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryPlayerBalance',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername,
		);

		$params = array(
			'account' => $gameUsername
		);

		$this->method = self::GET;

		return $this->callApi(self::API_queryPlayerBalance, $params, $context);
	}

	public function processResultForQueryPlayerBalance($params) {
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);
		$result = array();
		if($success){
            if(isset($resultArr['data']['balance'])) {
                $result['balance'] = $this->gameAmountToDB(floatval($resultArr['data']['balance']));
            }
            else {
                $success = false;
            }
		}

		return array($success, $result);

	}

	public function isPlayerExist($playerName){
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForIsPlayerExist',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername,
		);

		$params = array(
			'account' => $gameUsername
		);

		$this->method = self::GET;

		return $this->callApi(self::API_isPlayerExist, $params, $context);
    }

    public function processResultForIsPlayerExist($params){
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);
		$result = array();

		if($success){
			if($resultArr["data"]){
				$result['exists'] = true;
				$playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
				$this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
			}else{
				$result['exists'] = false;
			}
		}else{
			$result['exists'] = null;
		}

		return array($success, $result);
    }


	public function batchQueryPlayerBalance($playerNames, $syncId = null) {
        if (empty($playerNames)) {
            $playerNames = $this->getAllGameUsernames();
        }

        return $this->batchQueryPlayerBalanceOneByOne($playerNames, $syncId);
    }

	private function getReasons($error_code){
        switch ($error_code) {
            case 1:
            	return self::REASON_NO_ENOUGH_BALANCE;
                break;
            case 2:
            	return self::REASON_NOT_FOUND_PLAYER;
                break;
            case 3:
            case 4:
            	return self::REASON_INVALID_KEY;
                break;
            case 5:
            	return self::REASON_INCOMPLETE_INFORMATION;
                break;
            case 9:
            	return self::REASON_DUPLICATE_TRANSFER;
                break;
            case 100:
            	return self::REASON_FAILED_FROM_API;
                break;
            case 101:
            	return self::REASON_IP_NOT_AUTHORIZED;
                break;
            case 105:
            	return self::REASON_FAILED_FROM_API;
                break;
            case 200:
            case 201:
            	return self::REASON_LOCKED_GAME_MERCHANT;
                break;
            default:
                return self::REASON_UNKNOWN;
                break;
        }
	}

	public function depositToGame($playerName, $amount, $transfer_secure_id=null){
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$eventTime = new DateTime($this->serverTimeToGameTime(date("Y-m-d\TH:i:s-04:00")));
    	$formatedEventTime = $eventTime->format("Y-m-d\TH:i:s-04:00");
   	 	$external_transaction_id = $gameUsername.date("YmdHis").uniqid();//$transfer_secure_id;
        $amount = $this->dBtoGameAmount($amount);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForDepositToGame',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'amount' => $amount,
            'external_transaction_id' => $external_transaction_id,
        );

		$params = array(
			'account' => $gameUsername,
			'mtcode' => $external_transaction_id,
			'amount' => $amount,
			'eventTime' => $formatedEventTime
		);

		$this->method = self::POST;

		return $this->callApi(self::API_depositToGame, $params, $context);
	}

	public function processResultForDepositToGame($params) {
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$amount = $this->getVariableFromContext($params, 'amount');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);
		$statusCode = $this->getStatusCodeFromParams($params);

		$result = array(
			'response_result_id' => $responseResultId,
			'external_transaction_id' => $external_transaction_id,
			'transfer_status' => self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id' => self::REASON_UNKNOWN
		);

		if ($success) {
    //         $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
    //         if ($playerId) {
    //             $this->insertTransactionToGameLogs($playerId, $gameUsername, null, $amount, $responseResultId,$this->transTypeMainWalletToSubWallet());
    //         } else {
    //             $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
    //             $result['reason_id']=self::REASON_NOT_FOUND_PLAYER;
    //         }
			$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
			$result['didnot_insert_game_logs']=true;
        }else{
			$error_code = @$resultArr['status']['code'];

			if(((in_array($statusCode, $this->other_status_code_treat_as_success)) || in_array($error_code, $this->other_status_code_treat_as_success)) && $this->treat_500_as_success_on_deposit){
                $result['reason_id']=self::REASON_GAME_PROVIDER_INTERNAL_PROBLEM;
				$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_UNKNOWN;
                $success=true;
            }else{
				$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
				$result['reason_id']=$this->getReasons($error_code);
			}
            
        }

        return array($success, $result);
	}

	public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null){
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$eventTime = new DateTime($this->serverTimeToGameTime(date("Y-m-d\TH:i:s-04:00")));
    	$formatedEventTime = $eventTime->format("Y-m-d\TH:i:s-04:00");
   	 	$external_transaction_id = $gameUsername.date("YmdHis").uniqid();
        $amount = $this->dBtoGameAmount($amount);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForWithdrawFromGame',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername,
            'amount' => $amount,
            'external_transaction_id' => $external_transaction_id
        );

		$params = array(
			'account' => $gameUsername,
			'mtcode' => $external_transaction_id,
			'amount' => $amount,
			'eventTime' => $formatedEventTime
		);


		$this->method = self::POST;

		return $this->callApi(self::API_withdrawFromGame, $params, $context);
	}

	public function processResultForWithdrawFromGame($params){
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$amount = $this->getVariableFromContext($params, 'amount');
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);
		$result = array(
			'response_result_id' => $responseResultId,
			'external_transaction_id'=>$external_transaction_id,
			'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id'=>self::REASON_UNKNOWN
		);

		if ($success) {
    //         $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
    //         if ($playerId) {
	   //          $this->insertTransactionToGameLogs($playerId, $gameUsername, null, $amount, $responseResultId,$this->transTypeSubWalletToMainWallet());
    //         } else {
    //             $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
    //             $result['reason_id']=self::REASON_NOT_FOUND_PLAYER;
    //         }
			$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
			$result['didnot_insert_game_logs']=true;
        }else{
			$error_code = @$resultArr['status']['code'];
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
            $result['reason_id']=$this->getReasons($error_code);
        }

        return array($success, $result);
	}

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
			'mtcode' => $transactionId
		);

		$this->method = self::GET;

		return $this->callApi(self::API_queryTransaction, $params, $context);
	}

	public function processResultForQueryTransaction($params){
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);

		$result = array(
			'response_result_id' => $responseResultId,
			'status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id'=>self::REASON_UNKNOWN,
			'external_transaction_id'=>$external_transaction_id
		);

		if($success){
			switch ($resultArr['data']['status']) {
				case 'success':
					$result['status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
					break;
				case 'failed':
					$result['status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
					break;
				case 'pending':
					$result['status'] = self::COMMON_TRANSACTION_STATUS_PROCESSING;
					break;
			}
		}else{
			$error_code = @$resultArr['status']['code'];
            $result['reason_id']=$this->getReasons($error_code);
			$result['status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
		}

		return array($success, $result);
	}

	public function blockPlayer($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$success = $this->blockUsernameInDB($playerName);
		return array("success" => true);
    }

    public function unblockPlayer($playerName) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$this->unblockUsernameInDB($gameUsername);
		return array("success" => true);
    }

    public function login($playerName, $password = null, $extra = null) {
    	$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForLogin',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'password' => $password,
        );

		$params = array(
			'account' => $gameUsername,
			'password' => $password
		);

		$this->method = self::POST;

    	return $this->callApi(self::API_login, $params, $context);
	}

	public function processResultForLogin($params){
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);
		$result = array();

		if($success){
			$result['usertoken'] = $resultArr["data"]["usertoken"];
		}

		return array($success, $result);
	}

	public function logout($playerName, $password = null) {
    	$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForLogout',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName
        );

		$params = array(
			'account' => $gameUsername,
		);

		$this->method = self::POST;

    	return $this->callApi(self::API_logout, $params, $context);
	}

	public function processResultForLogout($params){
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);

		return array($success, $resultArr);
	}

	# available code: “zh-cn”, “en”
	# ※ some games also support “th” (thai)
	public function getLauncherLanguage($language){
		switch (strtolower($language)) {
            case Language_function::INT_LANG_ENGLISH:
            case "en-us":
                return "en";
                break;
            case Language_function::INT_LANG_CHINESE:
             case "zh-cn":
                return "zh-cn";
                break;

            case Language_function::INT_LANG_INDONESIAN:
            case "id-id":
                return "id";
                break;
            case Language_function::INT_LANG_VIETNAMESE:
			case "vi-vn":
            case "vi-vi":
                return "vn";
                break;
            case Language_function::INT_LANG_KOREAN:
            case "ko-kr":
                return "kr";
                break;
            case Language_function::INT_LANG_THAI:
            case "th-th":
                return "th";
                break;
            default:
                return "en";
                break;
        }
    }

	public function queryForwardGame($playerName, $extra = null) {
		$game_mode = $extra['game_mode'];
		if ($game_mode == 'demo' || $game_mode == 'trial') {
			$demo_url = $this->getSystemInfo('demo_url', 'https://demo.cqgame.games/');
    		return array("success"=>true, "url" => $demo_url);
    	}

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$password = $this->getPasswordByGameUsername($gameUsername);
		$loginData = $this->login($playerName,$password);

		if($loginData["success"]){
			$context = array(
	            'callback_obj' => $this,
	            'callback_method' => 'processResultForQueryForwardGame',
	            'playerName' => $playerName,
	            'gameUsername' => $gameUsername,
	        );

			$this->method = self::POST;

			$language = $this->getSystemInfo('language', $extra['language']);

			if ($extra['game_code'] === self::FISHING_GAME) {
				$params = array(
					'usertoken' => $loginData['usertoken'],
					'lang' => $language,
				);
				return $this->callApi(self::API_queryLaunchLobby, $params, $context);
			} else {
				$params = array(
					'usertoken' => $loginData['usertoken'],
					'gamehall' => $this->gamehall,
					'gamecode' => $extra['game_code'],
					'gameplat' => $extra['is_mobile']?"MOBILE":"WEB",
					'lang' => $language,
				);
                $api_result = $this->callApi(self::API_queryForwardGame, $params, $context);

                $lobby_url = $this->getHomeLink();
                if (array_key_exists("extra", $extra)) {
                    if(isset($extra['extra']['t1_lobby_url'])) {
                        $lobby_url = $extra['extra']['t1_lobby_url'];
                    }
                    if(isset($extra['extra']['home_link'])) {
                        $lobby_url = $extra['extra']['home_link'];
                    }
                }
                if (is_numeric($params['gamecode'])){
                    $api_result['url'] .= '&leaveUrl=' . $lobby_url;
                }

				return $api_result;
			}
		}else{
			return $loginData;
		}
	}

	public function processResultForQueryForwardGame($params){
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr,$gameUsername);
        $result = array();
        $result['url'] = '';
       	if($success){
			$result['url'] =  $resultArr['data']['url'];
       	}

        return array($success, $result);
	}

	# notes: the bet order is based on the time of game award sending; pull the data 3 minutes before the current time; recommending interval is 1-5 minutes, maximum of 60 minutes
	public function syncOriginalGameLogs($token = false) {
		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

		$startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
    	$endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));
    	$startDate->modify($this->getDatetimeAdjust());

    	#timestamp format
    	$queryDateTimeStart = $startDate->format("Y-m-d\TH:i:s-04:00");
		$queryDateTimeEnd = $endDate->format("Y-m-d\TH:i:s-04:00");

    	$result = array();
    	$continueSync = true;
    	$page = 1;
    	while ($continueSync) {
			$context = array(
				'callback_obj' => $this,
				'callback_method' => 'processResultForSyncOriginalGameLogs',
				'startDate' => $queryDateTimeStart,
				'endDate' => $queryDateTimeEnd
			);

			$params = array(
				'starttime' => $queryDateTimeStart,
				'endtime' => $queryDateTimeEnd,
				'page' => $page,
				'pagesize' => self::PAGE_SIZE,
			);
			$this->method = self::GET;

			$resultArr[] = $result = $this->callApi(self::API_syncGameRecords, $params, $context);
			$continueSync = @$result['continueSync'];

            $this->CI->utils->debug_log(__METHOD__ . ' info ---------->', $params, 'continueSync', $continueSync);

			$page++;
    	}

    	return array("success"=>true, $resultArr);
	}

    public function processResultForSyncOriginalGameLogs_oldVersion($params) {
		$this->CI->load->model(array('cq9_game_logs'));
		$startDate = $this->getVariableFromContext($params, 'startDate');
		$endDate = $this->getVariableFromContext($params, 'endDate');
        $resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr);
		$result = [];
		$dataCount = 0;
		$result['continueSync'] = false;
		if($success){
			$gameRecords = isset($resultArr['data']['Data'])?$resultArr['data']['Data']:array();
			$availableRows = !empty($gameRecords)?$this->CI->cq9_game_logs->getAvailableRows($gameRecords):array();
			foreach ($availableRows as $record) {
				# don't record if in progress bet
				if($record['status']!="complete"){
					continue;
				}

				$insertRecord = array();
				$insertRecord['gamehall'] = isset($record['gamehall'])?$record['gamehall']:null;
				$insertRecord['gametype'] = isset($record['gametype'])?$record['gametype']:null;
				$insertRecord['gameplat'] = isset($record['gameplat'])?$record['gameplat']:null;
				$insertRecord['gamecode'] = isset($record['gamecode'])?$record['gamecode']:null;
				$insertRecord['account'] = isset($record['account'])?$record['account']:null;
				$insertRecord['round'] = isset($record['round'])?$record['round']:null;
				$insertRecord['balance'] = isset($record['balance'])?$record['balance']:null;
				$insertRecord['win'] = isset($record['win'])?$record['win']:null;
				$insertRecord['bet'] = isset($record['bet'])?$record['bet']:null;
				$insertRecord['validbet'] = isset($record['validbet'])?$record['validbet']:null;
				$insertRecord['jackpot'] = isset($record['jackpot'])?$record['jackpot']:null;
				$insertRecord['status'] = isset($record['status'])?$record['status']:null;
				$insertRecord['endroundtime'] = isset($record['endroundtime'])?date('Y-m-d H:i:s', strtotime($record['endroundtime'])):null;
				$insertRecord['createtime'] = isset($record['createtime'])?date('Y-m-d H:i:s', strtotime($record['createtime'])):null;
				$insertRecord['bettime'] = isset($record['bettime'])?date('Y-m-d H:i:s', strtotime($record['bettime'])):null;
				$insertRecord['detail'] = isset($record['detail'])?json_encode($record['detail'],true):null;
				$insertRecord['gamerole'] = isset($record['gameRole'])?$record['gameRole']:null;
				$insertRecord['bankertype'] = isset($record['bankerType'])?$record['bankerType']:null;
				$insertRecord['rake'] = isset($record['rake'])?$record['rake']:null;
				$insertRecord['roomfee'] = isset($record['roomfee'])?$record['roomfee']:null;
				$insertRecord['bettype'] = isset($record['bettype'])?json_encode($record['bettype']):null;
				$insertRecord['gameresult'] = isset($record['gameresult'])?json_encode($record['gameresult']):null;
				$insertRecord['tabletype'] = isset($record['tabletype'])?$record['tabletype']:null;
				$insertRecord['tableid'] = isset($record['tableid'])?$record['tableid']:null;
				$insertRecord['roundnumber'] = isset($record['roundnumber'])?$record['roundnumber']:null;

				//extra info from SBE
				$insertRecord['external_uniqueid'] = isset($record['gamehall']) ? $record['gamehall'].$record['round'] : NULL;
				$insertRecord['response_result_id'] = $responseResultId;
				$insertRecord['created_at'] = $this->utils->getNowDateTime()->format('Y-m-d H:i:s');
				$insertRecord['updated_at'] = $this->utils->getNowDateTime()->format('Y-m-d H:i:s');

				//insert data to rtg gamelogs table database
				$this->CI->cq9_game_logs->insertGameLogs($insertRecord);
				$dataCount++;
			}

			$result['dataCount'] = $dataCount;
			$result['continueSync'] = $resultArr['data']['TotalSize'] == self::PAGE_SIZE;
		}

		return array($success, $result);
	}

	public function processResultForSyncOriginalGameLogs($params) {
		$this->CI->load->model(array('original_game_logs_model'));
		$startDate = $this->getVariableFromContext($params, 'startDate');
		$endDate = $this->getVariableFromContext($params, 'endDate');
        $resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr);

		$result = [
            'data_count' => 0,
            'data_count_insert' => 0,
			'data_count_update' => 0,
            'continueSync' => false,
        ];

		if($success && isset($resultArr['data']['Data']) && !empty($resultArr['data']['Data'])) {
            $extra['response_result_id'] = $responseResultId;
            $totalSize = isset($resultArr['data']['TotalSize']) ? $resultArr['data']['TotalSize'] : 0;
            $result['continueSync'] = $totalSize >= self::PAGE_SIZE;
			$gameRecords = $this->rebuildGameRecords($resultArr['data']['Data'], $extra);

            list($insertRows, $updateRows) = $this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                $this->original_table,
                $gameRecords,
                'external_uniqueid',
                'external_uniqueid',
                self::MD5_FIELDS_FOR_ORIGINAL,
                'md5_sum',
                'id',
                self::MD5_FLOAT_AMOUNT_FIELDS
            );

            $this->CI->utils->debug_log(__METHOD__ . ' after process available rows ---------->', 'gamerecords', count($gameRecords), 'insertrows', count($insertRows), 'updaterows', count($updateRows));

            $result['data_count'] += is_array($gameRecords) ? count($gameRecords): 0;

            if(!empty($insertRows)) {
                $result['data_count_insert'] += $this->updateOrInsertOriginalGameLogs($insertRows, 'insert');
            }

            unset($insertRows);

            if(!empty($updateRows)) {
                $result['data_count_update'] += $this->updateOrInsertOriginalGameLogs($updateRows, 'update');
            }

            unset($updateRows);
		}

		return array($success, $result);
	}

    public function rebuildGameRecords($gameRecords, $extra) {
        foreach($gameRecords as $record) {

            # don't record if in progress bet
            if($record['status'] != "complete") {
                continue;
            }

            $insertRecord = [];
            $insertRecord['gamehall'] = isset($record['gamehall']) ? $record['gamehall'] : null;
            $insertRecord['gametype'] = isset($record['gametype']) ? $record['gametype'] : null;
            $insertRecord['gameplat'] = isset($record['gameplat']) ? $record['gameplat'] : null;
            $insertRecord['gamecode'] = isset($record['gamecode']) ? $record['gamecode'] : null;
            $insertRecord['account'] = isset($record['account']) ? $record['account'] : null;
            $insertRecord['round'] = isset($record['round']) ? $record['round'] : null;
            $insertRecord['balance'] = isset($record['balance']) && !empty($record['balance']) ? $record['balance'] : 0;
            $insertRecord['win'] = isset($record['win']) && !empty($record['win']) ? $record['win'] : 0;
            $insertRecord['bet'] = isset($record['bet']) && !empty($record['bet']) ? $record['bet'] : 0;
            $insertRecord['validbet'] = isset($record['validbet']) && !empty($record['validbet']) ? $record['validbet'] : 0;
            $insertRecord['jackpot'] = isset($record['jackpot']) && !empty($record['jackpot']) ? $record['jackpot'] : 0;
            $insertRecord['status'] = isset($record['status']) ? $record['status'] : null;
            $insertRecord['endroundtime'] = isset($record['endroundtime']) ? date('Y-m-d H:i:s', strtotime($record['endroundtime'])) : null;
            $insertRecord['createtime'] = isset($record['createtime']) ? date('Y-m-d H:i:s', strtotime($record['createtime'])) : null;
            $insertRecord['bettime'] = isset($record['bettime']) ? date('Y-m-d H:i:s', strtotime($record['bettime'])) : null;
            $insertRecord['detail'] = isset($record['detail']) ? json_encode($record['detail'], true) : null;
            $insertRecord['gamerole'] = isset($record['gameRole']) ? $record['gameRole'] : null;
            $insertRecord['bankertype'] = isset($record['bankerType']) ? $record['bankerType'] : null;
            $insertRecord['rake'] = isset($record['rake']) && !empty($record['rake']) ? $record['rake'] : 0;
            $insertRecord['roomfee'] = isset($record['roomfee']) && !empty($record['roomfee']) ? $record['roomfee'] : 0;
            $insertRecord['bettype'] = isset($record['bettype']) ? json_encode($record['bettype']) : null;
            $insertRecord['gameresult'] = isset($record['gameresult']) ? json_encode($record['gameresult']) : null;
            $insertRecord['tabletype'] = isset($record['tabletype']) ? $record['tabletype'] : null;
            $insertRecord['tableid'] = isset($record['tableid']) ? $record['tableid'] : null;
            $insertRecord['roundnumber'] = isset($record['roundnumber']) ? $record['roundnumber'] : null;

            //extra info from SBE
            $insertRecord['external_uniqueid'] = isset($record['gamehall']) && isset($record['round']) ? $record['gamehall'].$record['round'] : null;
            $insertRecord['response_result_id'] = $extra['response_result_id'];
            $insertRecord['created_at'] = $this->utils->getNowForMysql();
            $insertRecord['updated_at'] = $this->utils->getNowForMysql();

            $dataRecords[] = $insertRecord;
        }

        return $dataRecords;
    }

    private function updateOrInsertOriginalGameLogs($data, $queryType) {
        $dataCount = 0;

        if(!empty($data)) {
            foreach($data as $record) {
                if($queryType == 'update') {
                    $record['updated_at'] = $this->CI->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->updateRowsToOriginal($this->original_table, $record);
                }else{
                    unset($record['id']);
                    $record['created_at'] = $this->CI->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->insertRowsToOriginal($this->original_table, $record);
                }

                $dataCount++;
                unset($record);
            }
        }

        return $dataCount;
    }

	public function syncMergeToGameLogs_oldVersion($token) {

		$this->CI->load->model(array('game_logs', 'player_model', 'cq9_game_logs'));

		$dateTimeFrom = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$dateTimeFrom->modify($this->getDatetimeAdjust());
		$dateTimeTo = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

		//observer the date format
		$startDate = $dateTimeFrom->format('Y-m-d H:i:s');
		$endDate = $dateTimeTo->format('Y-m-d H:i:s');

		$rlt = array('success' => true);

		$result = $this->CI->cq9_game_logs->getGameLogStatistics($startDate, $endDate);

		$cnt = 0;
		if (!empty($result)) {

			$unknownGame = $this->getUnknownGame();

			foreach ($result as $row) {
				$cnt++;

				$game_description_id = $row->game_description_id;
				$game_type_id = $row->game_type_id;
				$game_type = $row->game_type;

                //OGP-23147 validBet column is used for table and sports game
                if($game_type=='table' || $game_type=='sports game'){
                    $valid_bet = $this->gameAmountToDBGameLogsTruncateNumber($row->valid_bet);
                }else{
                    $valid_bet = $this->gameAmountToDBGameLogsTruncateNumber($row->bet_amount);
                }

				if(empty($row->game_type_id)&&empty($row->game_description_id)){
					list($game_description_id, $game_type_id) = $this->getGameDescriptionInfo($row, $unknownGame);
				}

				// OGP-19066 Update computation
				if($game_type == "table"){
					//$bet_amount = $row->valid_bet;
					$result_amount = ($row->win_amount - $row->rake) - $row->roomfee; //win-rake-roomfee
				}else{
					$result_amount = $row->win_amount-$row->bet_amount; //win-bet
				}

                $result_amount = $this->gameAmountToDBGameLogsTruncateNumber($result_amount);

                $after_balance = $this->gameAmountToDBGameLogsTruncateNumber($row->after_balance);

	            $extra = array(
                    'table'	=> $row->round_id,
                    'real_betting_amount' => $this->gameAmountToDBGameLogsTruncateNumber($row->bet_amount)
                );

				$this->syncGameLogs(
					$game_type_id,
					$game_description_id,
					$row->game_code,
					$row->game_type,
					$row->game,
					$row->player_id,
					$row->username,
					$valid_bet,
					$result_amount,
					null, # win_amount
					null, # loss_amount
					$after_balance, # after_balance
					0, # has_both_side
					$row->external_uniqueid,
					$row->end_datetime, //start
					$row->end_datetime, //end
					$row->response_result_id,
					Game_logs::FLAG_GAME,
                    $extra
				);

			}
		}

		$this->CI->utils->debug_log('CQ9_API PLAY API =========================>', 'startDate: ', $startDate,'EndDate: ', $endDate);
		$this->CI->utils->debug_log('syncMergeToGameLogs monitor', 'count', $cnt);
		return $rlt;
	}

    public function syncMergeToGameLogs($token) {
        $enabled_game_logs_unsettle = true;

        return $this->commonSyncMergeToGameLogs($token,
            $this,
            [$this, 'queryOriginalGameLogs'],
            [$this, 'makeParamsForInsertOrUpdateGameLogsRow'],
            [$this, 'preprocessOriginalRowForGameLogs'],
            $enabled_game_logs_unsettle);
    }

    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time) {
        $game_logs_table = $this->original_table;
        $sqlTime = "{$game_logs_table}.updated_at >= ? AND {$game_logs_table}.updated_at <= ?";

        if($use_bet_time) {
            $sqlTime = "{$game_logs_table}.bettime >= ? AND {$game_logs_table}.bettime <= ?";
        }

        $sql = <<<EOD
SELECT
    {$game_logs_table}.id AS sync_index,
    {$game_logs_table}.gamehall,
    {$game_logs_table}.gametype AS game_type,
    {$game_logs_table}.gameplat,
    {$game_logs_table}.gamecode AS game_code,
    {$game_logs_table}.account AS username,
    {$game_logs_table}.round AS round_number,
    {$game_logs_table}.balance AS after_balance,
    {$game_logs_table}.win AS win_amount,
    {$game_logs_table}.bet AS bet_amount,
    {$game_logs_table}.validbet AS valid_bet,
    {$game_logs_table}.jackpot,
    {$game_logs_table}.status AS cq9_status,
    {$game_logs_table}.endroundtime AS end_at,
    {$game_logs_table}.bettime AS start_at,
    {$game_logs_table}.bettime AS bet_at,
    {$game_logs_table}.jackpot,
    {$game_logs_table}.rake,
    {$game_logs_table}.roomfee,
    {$game_logs_table}.response_result_id,
    {$game_logs_table}.external_uniqueid,
    {$game_logs_table}.created_at,
    {$game_logs_table}.updated_at,
    {$game_logs_table}.md5_sum,
    game_provider_auth.login_name AS player_username,
    game_provider_auth.player_id,
    game_description.id AS game_description_id,
    game_description.game_name AS game_description_name,
    game_description.game_type_id,
    game_description.english_name AS game
FROM
    {$game_logs_table}
    LEFT JOIN game_description ON {$game_logs_table}.gamecode = game_description.game_code AND game_description.game_platform_id = ?
    LEFT JOIN game_type ON game_description.game_type_id = game_type.id
    JOIN game_provider_auth ON {$game_logs_table}.account = game_provider_auth.login_name and game_provider_auth.game_provider_id = ?
WHERE {$sqlTime}

EOD;

        $params = [
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo
        ];

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);

        return $result;
    }

    public function makeParamsForInsertOrUpdateGameLogsRow(array $row) {
        if(empty($row['md5_sum'])) {
            $row['md5_sum'] = $this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE, self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
		}

        return [
            'game_info' => [
                'game_type_id'          => isset($row['game_type_id']) ? $row['game_type_id'] : null,
                'game_description_id'   => isset($row['game_description_id']) ? $row['game_description_id'] : null,
                'game_code'             => isset($row['game_code']) ? $row['game_code'] : null,
                'game_type'             => null,
                'game'                  => isset($row['game']) ? $row['game'] : null
            ],
            'player_info' => [
                'player_id'             => isset($row['player_id']) ? $row['player_id'] : null,
                'player_username'       => isset($row['player_username']) ? $row['player_username'] : null
            ],
            'amount_info' => [
                'bet_amount'            => !empty($row['bet_amount']) ? $this->gameAmountToDB($row['bet_amount']) : 0,
                'result_amount'         => !empty($row['result_amount']) ? $this->gameAmountToDB($row['result_amount']) : 0,
                'bet_for_cashback'      => !empty($row['bet_amount']) ? $this->gameAmountToDB($row['bet_amount']) : 0,
                'real_betting_amount'   => !empty($row['bet_amount']) ? $this->gameAmountToDB($row['bet_amount']) : 0,
                'win_amount'            => 0,
                'loss_amount'           => 0,
                'after_balance'         => !empty($row['after_balance']) ? $this->gameAmountToDB($row['after_balance']) : 0
            ],
            'date_info' => [
                'start_at'              => isset($row['start_at']) ? $row['start_at'] : '0000-00-00 00:00:00',
                'end_at'                => isset($row['end_at']) ? $row['end_at'] : '0000-00-00 00:00:00',
                'bet_at'                => isset($row['bet_at']) ? $row['bet_at'] : '0000-00-00 00:00:00',
                'updated_at'            => isset($row['updated_at']) ? $row['updated_at'] : '0000-00-00 00:00:00'
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => $row['status'],
            'additional_info' => [
                'has_both_side'         => 0,
                'external_uniqueid'     => isset($row['external_uniqueid']) ? $row['external_uniqueid'] : null,
                'round_number'          => isset($row['round_number']) ? $row['round_number'] : null,
                'md5_sum'               => isset($row['md5_sum']) ? $row['md5_sum'] : null,
                'response_result_id'    => isset($row['response_result_id']) ? $row['response_result_id'] : null,
                'sync_index'            => isset($row['sync_index']) ? $row['sync_index'] : null,
                'bet_type'              => null
            ],
            'bet_details' => [
                'Game Hall' => $row['gamehall'],
                'Game Type' => $row['game_type'],
                'Game Plat' => $row['gameplat'],
                'Game Code' => $row['game_code'],
                'Account' => $row['username'],
                'Round' => $row['round_number'],
                'Balance' => $row['after_balance'],
                'Win' => $row['win_amount'],
                'Bet' => $row['bet_amount'],
                'Valid Bet' => $row['valid_bet'],
                'Jackpot' => $row['jackpot'],
            ],
            'extra' => [
                'note' => $row['note'],
            ],
            'game_logs_id' => isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];
    }
    const NUM_DECIMAL_KEEP = 4;
    public function preprocessOriginalRowForGameLogs(array &$row) {
        if(empty($row['game_type_id'])) {
            list($row['game_description_id'], $row['game_type_id']) = $this->getGameDescriptionInfo($row, $this->getUnknownGame());
        }

        $row['game_type'] = isset($row['game_type']) ? $row['game_type'] : null;
        $row['win_amount'] = isset($row['win_amount']) && !empty($row['win_amount']) ? $row['win_amount'] : 0;
        $row['bet_amount'] = isset($row['bet_amount']) && !empty($row['bet_amount']) ? $row['bet_amount'] : 0;
        $row['valid_bet'] = isset($row['valid_bet']) && !empty($row['valid_bet']) ? $row['valid_bet'] : 0;
        $row['rake'] = isset($row['rake']) && !empty($row['rake']) ? $row['rake'] : 0;
        $row['roomfee'] = isset($row['roomfee']) && !empty($row['roomfee']) ? $row['roomfee'] : 0;
        $row['status'] = isset($row['cq9_status']) && $row['cq9_status'] == 'complete' ? Game_logs::STATUS_SETTLED : Game_logs::STATUS_PENDING;

        //OGP-23147 validBet column is used for table and sports game
        if($row['game_type'] == 'table' || $row['game_type'] == 'sports game') {
            $row['bet_amount'] = $row['valid_bet'];
        }

        // OGP-19066 Update computation
        if($row['game_type'] == "table") {
            // $row['result_amount'] = ($row['win_amount'] - $row['rake']) - $row['roomfee']; // win - rake - roomfee
            $result_amount = bcsub($row['win_amount'], $row['rake'], self::NUM_DECIMAL_KEEP);
            $row['result_amount'] = bcsub($result_amount, $row['roomfee'], self::NUM_DECIMAL_KEEP);
        }else{
            // $row['result_amount'] = $row['win_amount'] - $row['bet_amount']; // win - bet
            $row['result_amount'] = bcsub($row['win_amount'], $row['bet_amount'], self::NUM_DECIMAL_KEEP);
        }

        $row['note'] = $this->getNote($row['result_amount']);
    }

	private function getGameDescriptionInfo_oldversion($row, $unknownGame) {
		$game_description_id = null;

		$external_game_id = $row->game_id;
        $extra = array('game_code' => $external_game_id,'game_name' => $row->game_id);

        $game_type_id = $unknownGame->game_type_id;
        $game_type = $unknownGame->game_name;

		return $this->processUnknownGame(
			$game_description_id, $game_type_id,
			$external_game_id, $game_type, $external_game_id, $extra,
			$unknownGame);
	}

    private function getGameDescriptionInfo($row, $unknownGame) {
        $game_type_id = null;
        $game_description_id = null;

        if(isset($row['game_description_id']) && !empty($row['game_description_id']) && isset($row['game_type_id']) && !empty($row['game_type_id'])) {
            $game_type_id = $row['game_type_id'];
            $game_description_id = $row['game_description_id'];
        }else{
            $game_type_id = $unknownGame->game_type_id;
            $game_description_id = $this->CI->game_description_model->processUnknownGame($this->getPlatformCode(), $game_type_id, $row['game_code'], $row['game_code']);
        }

        return [$game_description_id, $game_type_id];
    }

    public function getNote($result_amount) {
        $note = '';

        if($result_amount > 0) {
            $note = 'Win';
        }elseif($result_amount < 0) {
            $note = 'Lose';
        }elseif($result_amount == 0) {
            $note = 'Draw';
        }else{
            $note = '';
        }

        return $note;
    }

	public function queryPlayerInfo($playerName) {
		return $this->returnUnimplemented();
	}

	public function updatePlayerInfo($playerName, $infos) {
		return $this->returnUnimplemented();
	}


    public function queryBetDetailLink($player_username, $external_uniqueid = null, $round_id= null) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($player_username);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryBetDetailLink',
            'gameUsername' => $gameUsername
        );

        $params = array(
            'roundid' => $round_id,
            'account' => $gameUsername,
        );

        $this->method = self::GET;

        return $this->callApi(self::API_queryBetDetailLink, $params, $context);
    }

    public function processResultForQueryBetDetailLink($params){
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);

        $result = array(
            'url' => null
        );

        if($success){
            $result["url"] = $resultArr['data'];
        }

        return array($success, $result);
    }

	public function queryGameListFromGameProvider($extra = null)
    {
        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryGameListFromGameProvider',
        ];

        $params = [
            'gamehall' => $this->gamehall,
        ];

        return $this->callApi(self::API_queryGameListFromGameProvider, $params, $context);
    }

    public function processResultForQueryGameListFromGameProvider($params)
    {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        $result['games'] = [];

        if ($success) {
            $result['games'] = isset($resultArr['data']) ? $resultArr['data'] : [];
        }

        return [$success, $result];
    }
}

/*end of file*/