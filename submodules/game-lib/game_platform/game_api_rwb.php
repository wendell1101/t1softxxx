<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
// require_once('/admin/application/controllers/rwb_service_api.php');

class Game_api_rwb extends Abstract_game_api {

	const URI_MAP = array(
		self::API_syncGameRecords	 => '/api/qnx/bets/history?',
		self::API_queryPlayerBalance => '/api/qnx/balanceupdate?',
		self::API_queryTransaction  => '/api/qnx/transactions/failed?',
		self::API_login 			 => '/api/qnx/authenticate?',
		self::API_syncBalance 		 => '/api/qnx/balanceupdate?'
	);

	const BetTime = 1;
	const BetSettleTime = 2;
	const COMBO_PARLAY = 0;
	const MAXREFUNDCOUNTLIMIT = 10;
	//reason
	const BetPlace = 1;
	const BetDecline = 2;
	const BetSettle = 3;
	const BetPartialSettle = 4;
	const BetResettle = 5;
	const BetUnsettle = 6;
	//status
	const Error_System = 1;
	const Error_ConnectionFailed = 30;
	const Error_StatusCode = 31;
	const Error_EmptyResponse = 32;
	const Error_ApiError = 33;
	const Error_ReplayDetected = 11;

	const SETTLED_DEBIT = 1;
	const SETTLED_CREDIT = 2;
	const DUPLICATE_TRANSACTION = 3;

	# Don't ignore on refresh 
	const DEFAULT_IGNORED_0_ON_REFRESH = FALSE;
	
	public function __construct() {
		parent::__construct();
		$this->api_url = $this->getSystemInfo('url');
		$this->integration_key = $this->getSystemInfo('key');
		$this->secret_key = $this->getSystemInfo('secret');
		$this->currency = $this->getSystemInfo('currency');
		$this->language = $this->getSystemInfo('language');
		$this->auto_debit_credit_on_failed_transaction = $this->getSystemInfo('auto_debit_credit_on_failed_transaction',true);
	}

	public function getPlatformCode() {
		return RWB_API;
	}

	public function generateUrl($apiName, $params) {
		$url = $this->api_url.$params["method"];
		unset($params["method"]);
		$string = json_encode($params,true);
		// echo "string = ". $string;echo"<br>";
		$secret = $this->secret_key;
		// echo "secret = ". $secret;echo"<br>";
		$hash = hash_hmac('sha256', $string, $secret);
		// echo "hash = ". $hash;echo"<br>";
		$data = array(
			"hash" => $hash,
		);
		$generateUrl = $url. http_build_query($data);
		// echo"<br>";
		// echo $generateUrl;exit();
		return $generateUrl;
	}


	public function getHttpHeaders($params){
		return array("Content-Type" => 'application/json; charset=utf-8');
	}

	protected function customHttpCall($ch, $params) {
		unset($params["method"]); //unset action not need on params
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params, true));
		curl_setopt( $ch, CURLOPT_AUTOREFERER, true ); 
  		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 ); 
  		//curl_setopt( $ch, CURLOPT_TIMEOUT, 60 ); 
	}


	public function callback($result = null, $platform = 'web') {
		return $this->returnUnimplemented();
	}

	public function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null) {
		return array(false, null);
	}

	public function processResultBoolean($responseResultId, $resultArr, $playerName = null) {
		$success = false;
        $success = ($resultArr['Code']==0) ? true : false;
        if (!$success) {
            $this->setResponseResultToError($responseResultId);
            $this->CI->utils->debug_log('RWB got error ======================================>', $responseResultId, 'result', $resultArr);
            $success = false;
        }
        return $success;
	}

	public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
		// create player on game provider auth
		$return = parent::createPlayer($playerName, $playerId, $password, $email, $extra); 
		$success = false;
		$message = "Unable to create account for RWB api";
		if($return){
			$success = true;
			$message = "Successfull create account for RWB api";
		}
		
		return array("success" => $success, "message" => $message);   
	}

	public function changePassword($playerName, $oldPassword = null, $newPassword) {
		return $this->returnUnimplemented();
	}

	public function isPlayerExist($playerName) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
        $result['exists'] = true;
        $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
		return array(true, $result);
	}

	public function queryPlayerBalance($playerName) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
		$balance = $this->CI->player_model->getPlayerSubWalletBalance($playerId, $this->getPlatformCode());
		// $this->CI->load->model(array('wallet_model'));
		// $balance = $this->CI->wallet_model->getSubWalletTotalNofrozenOnBigWalletByPlayer($playerId, $this->getPlatformCode());

		$result = array(
			'success' => true, 
			'balance' => $balance
		);

		return $result;
	}

	public function depositToGame($playerName, $amount, $transfer_secure_id=null){
		$external_transaction_id = $transfer_secure_id;
	    return array(
	        'success' => true,
	        'external_transaction_id' => $external_transaction_id,
	        'response_result_id ' => NULL,
	        'didnot_insert_game_logs'=>true,
	    );
	}

    public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null) {
    	$external_transaction_id = $transfer_secure_id;
	    return array(
	        'success' => true,
	        'external_transaction_id' => $external_transaction_id,
	        'response_result_id ' => NULL,
	        'didnot_insert_game_logs'=>true,
	    );
    }

	public function queryForwardGame($playerName, $extra = null) {
		// return $this->returnUnimplemented();
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$login_response = $this->login($playerName,null);
		$language = $this->getLauncherLanguage($extra['language']);
		return $data = array(
			"integration_key" => $this->integration_key,
			"userId" => $gameUsername,
			"authToken" => isset($login_response['Token']) ? $login_response['Token'] : null,
			"language" => $language,
			"success" => isset($login_response['success']) ? $login_response['success'] : null
		);
	}

	public function getLauncherLanguage($language){
        if(!empty($language)){
        	switch ($language) {
	            case 2:
	                $lang = 'zh-CN'; // chinese
	                break;
	            case 3:
	                $lang = 'id-ID '; // indonesia
	                break;
	            case 4:
	                $lang = 'vi-VN '; // vietnam
	                break;
	            case 5:
	                $lang = 'ko-KR '; // korean
	                break;
	            case 6:
	                $lang = 'th-TH '; // thailand
	                break;
	            default:
	                $lang = 'en_US'; // default as english
	                break;
	        }
        } else{
        	$lang = 'en_US';
        }  
        return $lang;
    }

	public function syncOriginalGameLogs($token = false,$filterBy = self::BetTime) {
		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

		$startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
		$endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));
		$startDate->modify($this->getDatetimeAdjust());
		//observer the date format
		$startDate = $startDate->format('Y-m-d\TH:i:s\Z');
		$endDate = $endDate->format('Y-m-d\TH:i:s\Z');

		$context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForSyncGameRecords'
        );

        $params = array(
            "IntegrationKey" => $this->integration_key,
			"FilterBy" 		 => $filterBy,
			"DateFrom"		 => $startDate,
			"DateTo" 		 => $endDate,
			"Language" 		 => $this->language,
			"method" 		 => self::URI_MAP[self::API_syncGameRecords]
        );

        $this->utils->debug_log("syncing params ============================>", $params);
        return $this->callApi(self::URI_MAP[self::API_syncGameRecords], $params, $context);
	}

	public function processResultForSyncGameRecords($params) {
		$this->CI->load->model(array('rwb_game_logs','player_model'));
		$responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr);
        $result = array();
        $count = 0;

        if($success){
        	$gameRecords = $resultArr['Bets'];
        	$dateTimeNow = date('Y-m-d H:i:s');
        	if (!empty($gameRecords)) {
        		$this->utils->debug_log(' RWB record response - =================================================> ' .json_encode($gameRecords));
        		foreach ($gameRecords as $record) {
        			$insertRecord = array();
					$insertRecord['user_id'] 			= isset($record['UserId']) ? $record['UserId'] : NULL;
					$insertRecord['bet_id'] 			= isset($record['BetId']) ? $record['BetId'] : NULL;
					$insertRecord['currency'] 			= isset($record['Currency']) ? $record['Currency'] : NULL;
					$insertRecord['description'] 		= isset($record['purchaseId']) ? $record['purchaseId'] : NULL;
        			$insertRecord['status'] 			= isset($record['Status']) ? $record['Status'] : NULL;
					$insertRecord['settle_status'] 		= isset($record['SettleStatus']) ? $record['SettleStatus'] : NULL;
					$insertRecord['stake'] 				= isset($record['Stake']) ? $record['Stake'] : NULL;
					$insertRecord['payout'] 			= isset($record['Payout']) ? $record['Payout'] : NULL;
					$insertRecord['potential_payout'] 	= isset($record['PotentialPayout']) ? $record['PotentialPayout'] : NULL;
					$insertRecord['is_mobile'] 			= isset($record['IsMobile']) ? $record['IsMobile'] : NULL;
					$insertRecord['ip_address'] 		= isset($record['IpAddress']) ? $record['IpAddress'] : NULL;
					$insertRecord['price_format'] 		= isset($record['PriceFormat']) ? $record['PriceFormat'] : NULL;
					$insertRecord['bet_time'] 			= isset($record['BetTime']) ?  $this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($record['BetTime']))) : NULL; 
					$insertRecord['settle_time'] 		= isset($record['SettleTime']) ? $this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($record['SettleTime']))) : NULL;
					$insertRecord['selections'] 		= isset($record['Selections']) ? json_encode($record['Selections']) : NULL;

					$insertRecord['external_uniqueid'] 	= isset($record['BetId']) ? $record['BetId'] : NULL;
					$insertRecord['response_result_id'] = $responseResultId;

					$isExists = $this->CI->rwb_game_logs->isRowIdAlreadyExists($insertRecord['bet_id']);
					if ($isExists) {
						$insertRecord['updated_at'] = $dateTimeNow;
						$this->CI->rwb_game_logs->updateGameLogs($insertRecord);
					} else {
						$insertRecord['created_at'] = $dateTimeNow;
						$this->CI->rwb_game_logs->insertGameLogs($insertRecord);
					}
					$count++;
        		}
        		$result['data_count'] = $count;
        	}
        }
        return array($success, $result);
	}

	public function syncMergeToGameLogs($token) {
		$this->CI->load->model(array('rwb_game_logs','player_model','game_description_model','game_logs','rwb_game_transactions'));
		$dateTimeFrom = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$dateTimeFrom->modify($this->getDatetimeAdjust());
		$dateTimeTo = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

		//observer the date format
		$startDate = $dateTimeFrom->format('Y-m-d H:i:s');
		$endDate = $dateTimeTo->format('Y-m-d H:i:s');

		$rlt = array('success' => true);

		$result = $this->CI->rwb_game_logs->getGameLogStatistics($startDate, $endDate);
		$cnt = 0;
		if ($result) {
			$unknownGame = $this->getUnknownGame();
			foreach ($result as $rwb_game_logs) {
				$chk_settled_date = date('Y-m-d', strtotime($rwb_game_logs['settle_time']));
				$is_failed = $this->CI->rwb_game_transactions->getFailedTransaction($rwb_game_logs['bet_id']);
				if($is_failed){
					$this->utils->debug_log(' RWB bet- '.$rwb_game_logs['bet_id'].'======isfailed==============>');
					$status = $this->getFailedGameRecordStatus($is_failed->reason);
				} else {
					$status = $this->getGameRecordsStatus($rwb_game_logs['status'],$rwb_game_logs['settle_status']);
				}
				$this->utils->debug_log(' RWB bet- '.$rwb_game_logs['bet_id'].'======status==============> ' .$status);
				$player_username = $this->getPlayerUsernameByGameUsername($rwb_game_logs['user_id']);
				$player_id = $this->getPlayerIdFromUsername($player_username);
				if (!$player_id) {
					continue;
				}
				$selections = json_decode($rwb_game_logs['selections'],true);
				if(count($selections) > 1 ){
					$gameDetails = $this->CI->game_description_model->getGameDescriptionByGamePlatformIdAndGameCode(RWB_API,(string)self::COMBO_PARLAY);
				} else {
					if(!empty($selections)){
						$gameDetails = $this->CI->game_description_model->getGameDescriptionByGamePlatformIdAndGameCode(RWB_API,$selections[0]['SportId']);
					} else {
						$gameDetails = $this->CI->game_description_model->getGameDescriptionByGamePlatformIdAndGameCode(RWB_API,null);
					}
				}
				$betDetails = $this->processGameBetDetail($selections);	
				$extra = array(
					'trans_amount'	=> 	$rwb_game_logs['bet_amount'],
					'status'		=> 	($status == Game_logs::STATUS_REFUND) ? Game_logs::STATUS_SETTLED : $status ,
					'bet_details'	=> 	$this->CI->utils->encodeJson(array('bet_details' => $betDetails)),
					'note'			=> ($status == Game_logs::STATUS_REFUND) ? "Refund ". $this->gameAmountToDB($rwb_game_logs['bet_amount']) : NULL
				);
				$cnt++;
				// echo"<pre>";
				// print_r($betDetails);exit();
				$this->syncGameLogs(
					(!empty($gameDetails)) ? $gameDetails[0]->game_type_id : $unknownGame->game_type_id,
					(!empty($gameDetails)) ? $gameDetails[0]->game_description_id : $unknownGame->id,
					(!empty($gameDetails)) ? $gameDetails[0]->game_code : $unknownGame->game_code,
					(!empty($gameDetails)) ? $gameDetails[0]->game_type_id : $unknownGame->game_type_id,
					(!empty($gameDetails)) ?	$gameDetails[0]->game_name : $unknownGame->game_name,
					$player_id,
					$player_username,
					$rwb_game_logs['bet_amount'],
					$rwb_game_logs['result_amount'],
					null, # win_amount
					null, # loss_amount
					null,//$data['after_balance'], # after_balance
					0, # has_both_side
					$rwb_game_logs['bet_id'],
					$rwb_game_logs['bet_time'], //start
					($chk_settled_date == '0001-01-01') ? $rwb_game_logs['bet_time'] : $rwb_game_logs['settle_time'], //end
					null,
					Game_logs::FLAG_GAME,
					$extra
				);
			}
		}
		$this->CI->utils->debug_log('RWB syncMergeToGameLogs monitor', 'count', $cnt);
		return $rlt;
	}

	public function processGameBetDetail($rowArray){
		$set = array();
		if(!empty($rowArray)){
            foreach ($rowArray as $key => $game) {
                $set[$game['Id']] = array(
                	'HomeTeam'=>$game['HomeTeam'],
                	'AwayTeam'=>$game['AwayTeam'],
                    'isLive' => $game['IsLive'],
                    'league' => $game['League'],
                );
            }
        }
        return json_encode($set,true);
	}
	/**
	 * overview : get game record status
	 *
	 * @param $status
	 * @return int
	 */
	private function getGameRecordsStatus($bet_status,$settle_status) {
		$this->CI->load->model(array('game_logs'));
		switch ((int)$bet_status) {
			case 4:
				$status = Game_logs::STATUS_ACCEPTED;
				break;
			case 2:
				$status = Game_logs::STATUS_CANCELLED;
				break;
			case 1:
				switch ((int)$settle_status) {
					case 0:
						$status = Game_logs::STATUS_ACCEPTED;
						break;
					case 1:
						$status = Game_logs::STATUS_VOID;
						break;
					case 2:
					case 3:
					case 4:
					case 5:
					case 6:
						$status = Game_logs::STATUS_SETTLED;
						break;
				}
				break;
			default:
				$status = Game_logs::STATUS_ACCEPTED;
				break;
		}
		return $status;
	}

	private function getFailedGameRecordStatus($status) {
		$this->CI->load->model(array('game_logs'));
		switch ((int)$status) {
			case 1:
			case 6:
				$status = Game_logs::STATUS_ACCEPTED;
				break;
			case 2:
				$status = Game_logs::STATUS_CANCELLED;
				break;
			case 3:
			case 4:
			case 5:
				$status = Game_logs::STATUS_SETTLED;
				break;
			default:
				$status = Game_logs::STATUS_ACCEPTED;
				break;
		}
		return $status;
	}

	public function syncLostAndFound($token) {
		//get failed transctions
		$this->syncFailedTransactions($token);
		//sync gamelogs by settle time
		return $this->syncOriginalGameLogs($token, self::BetSettleTime);
	}

	public function syncFailedTransactions($token)
	{
		$this->CI->load->model(array('external_system'));
		$lastSyncIde = $this->CI->external_system->getLastSyncId(RWB_API);
		$context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForSyncFailedTransaction'
        );

        $params = array(
            "IntegrationKey" => $this->integration_key,
			"Timestamp" 		 => $lastSyncIde,
			"method" 		 => self::URI_MAP[self::API_queryTransaction]
        );
        $this->utils->debug_log("syncing params ============================>", $params);
        return $this->callApi(self::URI_MAP[self::API_queryTransaction], $params, $context);
	}

	public function processResultForSyncFailedTransaction($params) {
		$this->CI->load->model(array('rwb_game_logs','wallet_model','external_system','rwb_game_transactions'));
		$responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr);
        if($success){
        	$failed_transactions = $resultArr['Transactions'];
        	// will update last sync id
        	$last_sync_id = $resultArr['Timestamp'];
        	$this->CI->external_system->updateLastSyncId(RWB_API, array("last_sync_id" =>$last_sync_id));

        	if(!empty($failed_transactions)){
        		foreach ($failed_transactions as $failed_transactionsi) {
        			$player_username = $this->getPlayerUsernameByGameUsername($failed_transactionsi['UserId']);
					$playerId = $this->getPlayerIdFromUsername($player_username);
					$credit = $failed_transactionsi['Amount'] > 0;
					$amount = abs($failed_transactionsi['Amount']);
					$controller = $this;
					$transactionExist = $this->CI->rwb_game_transactions->isTransactionIdAlreadyExists($failed_transactionsi['Id']);
					$is_settled = 0;
					if($this->auto_debit_credit_on_failed_transaction){
						if(!$transactionExist){
							$trans_success = true;
							if($amount > 0){//check amount
								$trans_success = $this->CI->wallet_model->lockAndTransForPlayerBalance($playerId, function() use($controller, $playerId, $amount,$credit) {
									if($credit){
										return $controller->CI->wallet_model->wallet_model->incSubWallet($playerId, $controller->getPlatformCode(), $amount);
									} else{
										return $controller->CI->wallet_model->wallet_model->decSubWallet($playerId, $controller->getPlatformCode(), $amount);
									}
								});
							}
							if($trans_success){
								$is_settled = $credit ? self::SETTLED_CREDIT : self::SETTLED_DEBIT;
							}
						} else {
							$is_settled = self::DUPLICATE_TRANSACTION;
						}
					}

					$playerName = $this->getPlayerUsernameByGameUsername($failed_transactionsi['UserId']);
					//check balance after
					$afterBalanceResult = $this->queryPlayerBalance($playerName);
					$afterBalance = isset($afterBalanceResult['success']) && $afterBalanceResult['success'] ? $afterBalanceResult['balance'] : 0;

					//insert data in transactions
					$requestId = "F".$failed_transactionsi['Id'];
					$exist = $this->CI->rwb_game_transactions->isRowIdAlreadyExists($requestId);
					$transaction = array(
						"request_id" => isset($failed_transactionsi['Id']) ? $requestId : NULL,
						"transaction_id" => isset($failed_transactionsi['Id']) ? $failed_transactionsi['Id'] : NULL,
						"user_id" => isset($failed_transactionsi['UserId']) ? $failed_transactionsi['UserId'] : NULL,
						"bet_id" => isset($failed_transactionsi['BetId']) ? $failed_transactionsi['BetId'] : NULL,
						"reason" => isset($failed_transactionsi['Reason']) ? $failed_transactionsi['Reason'] : NULL,
						"amount" => isset($failed_transactionsi['Amount']) ? $failed_transactionsi['Amount'] : NULL,
						"currency" => isset($failed_transactionsi['Currency']) ? $failed_transactionsi['Currency'] : NULL,
						"status" => isset($failed_transactionsi['Status']) ? $failed_transactionsi['Status'] : NULL,
						"balance" => isset($failed_transactionsi['Balance']) ? $failed_transactionsi['Balance'] : NULL,
						"is_failed" => TRUE,
						"created_at" => date('Y-m-d H:i:s'),
						"response_result_id" => $responseResultId,
						"external_uniqueid" => $failed_transactionsi['Id'],
						"is_settled" => $is_settled,
						"after_balance" => $afterBalance
					);
					if(!$exist) {
						$this->CI->rwb_game_transactions->insertRow($transaction);
					}
        		}
        	}
        }
        $result = array(
        	"code" => isset($resultArr['Code']) ? $resultArr['Code'] : null,
        	"timestamp" => isset($resultArr['Timestamp']) ? $resultArr['Timestamp'] : null,
        	"transactions" => isset($resultArr['Transactions']) ? $resultArr['Transactions'] : null,
        );
        return array($success, $result);
    }

	public function blockPlayer($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$success = $this->blockUsernameInDB($playerName);
		return array("success" => true);
	}

	public function unblockPlayer($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$success = $this->unblockUsernameInDB($playerName);
		return array("success" => true);
	}


	public function login($playerName, $password = null) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
		$balance = $this->CI->player_model->getPlayerSubWalletBalance($playerId, $this->getPlatformCode());

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForLogin',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername,
        );
        
        $params = array(
            "IntegrationKey" => $this->integration_key,
			"UserId" 		 => $gameUsername,
			"Username"		 => $gameUsername,
			"Balance" 		 => $balance,
			"Currency"		 =>	$this->currency,
			"method" 		 => self::URI_MAP[self::API_login]
        );
        $this->utils->debug_log("login params ============================>", $params);
        return $this->callApi(self::URI_MAP[self::API_login], $params, $context);
	}

	public function processResultForLogin($params){
		$playerName = $this->getVariableFromContext($params, 'playerName');
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $this->utils->debug_log("login response ============================>", $resultArr);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);
        $result['Token'] = isset($resultArr['Token']) ? $resultArr['Token'] : null;
        return array($success, $result);
	}

	public function balanceUpdate($playerName){
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
		$this->CI->load->model(array('wallet_model'));
		$controller = $this;
		$balance = null;

		$success = $this->CI->wallet_model->lockAndTransForPlayerBalance($playerId, function () use ($gameUsername, $playerId, $playerName, &$balance, $controller) {
			$controller->CI->load->model(array('wallet_model'));
			$balance =  $controller->CI->wallet_model->getSubWalletTotalNofrozenOnBigWalletByPlayer($playerId, $this->getPlatformCode());
			return true;
		});
		if($success){
			$context = array(
			    'callback_obj' => $this,
			    'callback_method' => 'processResultForBalanceUpdate',
			    'playerName' => $playerName,
			    'gameUsername' => $gameUsername,
			);

			$params = array(
			    "IntegrationKey" => $this->integration_key,
				"UserId" 		 => $gameUsername,
				"Balance" 		 => $balance,
				"method" 		 => self::URI_MAP[self::API_syncBalance]
			);
			$this->utils->debug_log("balance update params ============================>", $params);
			return $this->callApi(self::URI_MAP[self::API_syncBalance], $params, $context);
		}
		return $success;
	}

	public function processResultForBalanceUpdate($params){
		$playerName = $this->getVariableFromContext($params, 'playerName');
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $this->utils->debug_log("balance update response ============================>", $resultArr);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);
        $result['result'] = isset($resultArr['Message']) ? $resultArr['Message'] : $success;
        return array($success, $result);
	}

	public function postDepositToGame($result, $player_id, $playerName, $transfer_from, $transfer_to, $amount, $extra_details = []){
		return  $this->balanceUpdate($playerName);
	}

	public function postWithdrawFromGame($result, $player_id, $playerName, $transfer_from, $transfer_to, $amount, $extra_details = []) {
        return  $this->balanceUpdate($playerName);
    }

	public function processResultForgetVendorId($params) {
		return $this->returnUnimplemented();
	}

	/**
	 * overview : get game time to server time
	 *
	 * @return string
	 */
	/*public function getGameTimeToServerTime() {
		//return '+8 hours';
	}*/

	/**
	 * overview : get server time to game time
	 *
	 * @return string
	 */
	/*public function getServerTimeToGameTime() {
		//return '-8 hours';
	}*/

	public function queryTransaction($transactionId, $extra) {
		return $this->returnUnimplemented();
	}

	public function syncPlayerAccount($username, $password, $playerId) {
		return $this->returnUnimplemented();
	}

	public function queryPlayerInfo($playerName) {
		return $this->returnUnimplemented();
	}

	public function logout($playerName, $password = null) {
		return $this->returnUnimplemented();
	}

	public function updatePlayerInfo($playerName, $infos) {
		return $this->returnUnimplemented();
		// return array("success" => true);
	}

	public function queryPlayerDailyBalance($playerName, $playerId, $dateFrom = null, $dateTo = null) {
		return $this->returnUnimplemented();
	}

	public function queryGameRecords($dateFrom, $dateTo, $playerName = null) {
		return $this->returnUnimplemented();
	}

	public function checkLoginStatus($playerName) {
		return $this->returnUnimplemented();
	}

	public function checkLoginToken($playerName, $token) {
		return $this->returnUnimplemented();
	}

	public function totalBettingAmount($playerName, $dateTimeFrom, $dateTimeTo) {
		return $this->returnUnimplemented();
	}

	public function isSeamLessGame(){
	    return true;
	}
	
}

/*end of file*/