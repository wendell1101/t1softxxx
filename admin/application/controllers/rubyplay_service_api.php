<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/BaseController.php';

class Rubyplay_service_api extends BaseController {

	protected $gamePlatformId = RUBYPLAY_SEAMLESS_THB1_API;
	private $api;

	const ACTION_PLAYERINFO = "playerInformation";
	const ACTION_BALANCE = "balance";
	const ACTION_DEBIT = "debit";
	const ACTION_CREDIT = "credit";
	const ACTION_CANCEL = "cancel";
	const CODE_SUCCESS = "0";

	const ERROR_CODE = array(
		"0" => "Success",
		"100" => "Player Account does not exist",
		"101" => "Invalid currency",
		"102" => "Player account blocked/inactive",
		"103" => "Insufficient funds",
		"104" => "Invalid player sessionToken",
		"105" => "Invalid Credentials",
		"106" => "Duplicate transaction Id",
		"107" => "Game round closed",
		"500" => "Internal server error"
	);


	public function __construct() {
		parent::__construct();
		$this->load->model(array('wallet_model','game_provider_auth','common_token','player_model','ruby_play_transactions'));
		$this->api = $this->utils->loadExternalSystemLibObject($this->gamePlatformId);
		$this->currencyCode = $this->api->getCurrency();
		// $this->country = $this->api->getCountryCode();
	}

	public function playerInformation() {
		if ($this->external_system->isGameApiActive($this->gamePlatformId)) {
			$reqParams = file_get_contents('php://input');
			$fullRequest = json_decode($reqParams,true);
			$callMethod = self::ACTION_PLAYERINFO;
			$this->CI->utils->debug_log("Ruby Play (Request params)", $reqParams);

			if(!empty($fullRequest['sessionToken'])) {
				$sessionToken = isset($fullRequest['sessionToken']) ? $fullRequest['sessionToken'] : null;
				$response = [];
				$success = true;

				$responseData=array(
					"playerId" => null,
					"playerName" => null,
					"currencyCode" => null
				);
				try {
					$playerInfo = $this->api->getPlayerInfoByToken($sessionToken);
					$gameUsername = $this->api->getGameUsernameByPlayerId($playerInfo['playerId']);
					$isTokenValid = $this->common_token->isTokenValid($playerInfo['playerId'], $sessionToken);
					$isBlocked = $this->api->isBlocked($playerInfo['username']);
					$isPlayerExist = $this->api->isPlayerExist($playerInfo['username']);

					if(empty($playerInfo)) {
						throw new Exception('104');
					}
					if(!$isTokenValid) {
						throw new Exception('104');
					}
					if($isBlocked) {
						throw new Exception('102');
					}
					if(!$isPlayerExist) {
						throw new Exception('100');
					}

					$responseData = array(
						"playerId" => $playerInfo['playerId'],
						"playerName" => $gameUsername,
						"currencyCode" => "THB"
					);

					$response = $this->returnSuccess($responseData);

				} catch (Exception $e) {
					log_message('error', self::ERROR_CODE[$e->getMessage()]);
					$response = $this->returnError($e->getMessage(), $responseData);
				}
			} else {
				$success = false;
				$response = $this->returnError('105');
			}
		} else {
			$success = false;
			$response = $this->returnError('500');
		}
			
		# save response result and API request
		$this->saveResponseResult($success,$callMethod,$reqParams,$response);

		return $this->returnJsonResult($response);

	}

	public function balance() {
		if ($this->external_system->isGameApiActive($this->gamePlatformId)) {
			$reqParams = file_get_contents('php://input');
			$fullRequest = json_decode($reqParams,true);
			$callMethod = self::ACTION_BALANCE;
			$this->CI->utils->debug_log("Ruby Play (Request params)", $reqParams);

			if(!empty($fullRequest)) {
				$sessionToken 	= isset($fullRequest['sessionToken']) ? $fullRequest['sessionToken'] : null;
				$playerId 		= isset($fullRequest['playerId']) ? $fullRequest['playerId'] : null;
				$currencyCode 	= isset($fullRequest['currencyCode']) ? $fullRequest['currencyCode'] : null;
				$gameUsername 	= isset($fullRequest['gameId']) ? $fullRequest['gameId'] : null;

				$response = [];
				$success = true;

				$responseData=array(
					"balance" => 0
				);
				try {
					$playerInfo = $this->api->getPlayerInfoByToken($sessionToken);
					$isTokenValid = $this->common_token->isTokenValid($playerInfo['playerId'], $sessionToken);
					$isBlocked = $this->api->isBlocked($playerInfo['username']);
					$isPlayerExist = $this->api->isPlayerExist($playerInfo['username']);
					$playerBalance = $this->api->queryPlayerBalance($playerInfo['username']);

					if(empty($playerInfo)) {
						throw new Exception('104');
					}
					if(!$isTokenValid) {
						throw new Exception('104');
					}
					if($isBlocked) {
						throw new Exception('102');
					}
					if(!$isPlayerExist) {
						throw new Exception('100');
					}
					if($currencyCode != $this->currencyCode) {
						throw new Exception('101');
					}

					$responseData = array(
						"balance" => $playerBalance['balance'],
					);

					$response = $this->returnSuccess($responseData);

				} catch (Exception $e) {
					log_message('error', self::ERROR_CODE[$e->getMessage()]);
					$response = $this->returnError($e->getMessage(), $responseData);
				}
			} else {
				$success = false;
				$response = $this->returnError('105');
			}
		} else {
			$success = false;
			$response = $this->returnError('500');
		}
			
		# save response result and API request
		$this->saveResponseResult($success,$callMethod,$reqParams,$response);

		return $this->returnJsonResult($response);

	}

	public function debit() {
		$reqParams = file_get_contents('php://input');
		$fullRequest = json_decode($reqParams,true);
		$this->CI->utils->debug_log("Ruby Play (Request params)", $reqParams);
		return $this->processTransaction($fullRequest, self::ACTION_DEBIT);
	}

	public function credit() {
		$reqParams = file_get_contents('php://input');
		$fullRequest = json_decode($reqParams,true);
		$this->CI->utils->debug_log("Ruby Play (Request params)", $reqParams);
		return $this->processTransaction($fullRequest, self::ACTION_CREDIT);
	}

	public function cancel() {
		$reqParams = file_get_contents('php://input');
		$fullRequest = json_decode($reqParams,true);
		$this->CI->utils->debug_log("Ruby Play (Request params)", $reqParams);
		return $this->processTransaction($fullRequest, self::ACTION_CANCEL);
	}

	public function processTransaction($fullRequest, $callMethod) {

		$sessionToken 	= isset($fullRequest['sessionToken']) ? $fullRequest['sessionToken'] : null;
		$playerId 		= isset($fullRequest['playerId']) ? $fullRequest['playerId'] : null;
		$currencyCode 	= isset($fullRequest['currencyCode']) ? $fullRequest['currencyCode'] : null;
		$gameId 		= isset($fullRequest['gameId']) ? $fullRequest['gameId'] : null;
		$trans_amount 	= isset($fullRequest['amount']) ? $fullRequest['amount'] : null;
		$roundId 		= isset($fullRequest['roundId']) ? $fullRequest['roundId'] : null;
		$transactionId 	= isset($fullRequest['transactionId']) ? $fullRequest['transactionId'] : null;
		$deviceType 	= isset($fullRequest['deviceType']) ? $fullRequest['deviceType'] : null;
		$gameRoundEnd 	= isset($fullRequest['gameRoundEnd']) ? $fullRequest['gameRoundEnd'] : null;
		$refTransacId	= isset($fullRequest['referenceTransactionId']) ? $fullRequest['referenceTransactionId'] : null;

		if(!empty($fullRequest)) {

			$playerInfo = $this->api->getPlayerInfoByToken($sessionToken);
			$isTokenValid = $this->common_token->isTokenValid($playerInfo['playerId'], $sessionToken);
			$playerBalance = $this->api->queryPlayerBalance($playerInfo['username']);
			$response = [];
			$success = true;
			$responseData=['balance'=>0];

			try {

				if(!$isTokenValid) {
					throw new Exception('104');
				}
				if(isset($transactionId)) {
					if($callMethod == self::ACTION_DEBIT || $callMethod == self::ACTION_CREDIT) {
						$existingRow = $this->ruby_play_transactions->isTransactionIdAlreadyExists($transactionId,'debit');
						if($existingRow) {
							throw new Exception('106');
						}
					}
				}
				if($trans_amount > $playerBalance['balance'] && $callMethod == self::ACTION_DEBIT) {
					throw new Exception('103');
				}
				if($currencyCode != $this->currencyCode && $callMethod != 'cancel') {
					throw new Exception('101');
				}
				if($callMethod == self::ACTION_CANCEL || $callMethod == self::ACTION_CREDIT) {
					$transId = isset($transactionId)?$transactionId:$refTransacId;
					$rowRoundId = $this->ruby_play_transactions->isRowByRoundId($roundId);
					$rowRoundId = end($rowRoundId);
					// print_r($rowRoundId);exit;
					$isExist = $this->ruby_play_transactions->isTransactionIdAlreadyExists($transId,$callMethod);
					if(!empty($rowRoundId)){
						if($rowRoundId['gameRoundEnd'] == true && $callMethod != $rowRoundId['action']) {
							throw new Exception('107');
						} else if ($rowRoundId['gameRoundEnd'] && $callMethod == $rowRoundId['action'] && $isExist){
							$responseData = ['balance'=>intval($rowRoundId['after_balance'])];
							$response = $this->returnSuccess($responseData);
							return $this->returnJsonResult($response);
						}
					}
				}

				$trans_params = [
					"transactionId" => $transactionId,
					"playerId" => $playerId,
					"amount" => $trans_amount,
					"action" => $callMethod,
					"referenceTransactionId" => $refTransacId
				];

				list($success, $before_balance, $after_balance) = $this->debitCreditBalance($trans_params);

				$this->CI->utils->debug_log(__FUNCTION__,'Success: ', $success, 'before_balance: ',$before_balance, 'after_balance: ', $after_balance);

				if(!$success) {
					$response = $this->returnError('500',['balance'=>$before_balance]);
				}

				$data = [
					'sessionToken' => $sessionToken,
					'currencyCode' => $currencyCode,
					'gameId' => $gameId,
					'roundId' => $roundId,
					'deviceType' => $deviceType,
					'gameRoundEnd' => $gameRoundEnd,
					'before_balance' => $before_balance,
					'after_balance' => $after_balance
				];

				if($success) {
					$responseData = array(
						'balance' => $after_balance
					);
					$response = $this->returnSuccess($responseData);
					$response_result_id['response_result_id'] = $this->saveResponseResult($success,$callMethod,$fullRequest,$response);
					$trans_record = array_merge($trans_params, $data, $response_result_id);
					$this->processTransactionRecord($trans_record);
				}
				
			} catch (Exception $e) {
				log_message('error', self::ERROR_CODE[$e->getMessage()]);
				if($e->getMessage() == 103 || $e->getMessage() == 106 || $e->getMessage() == 101 || $e->getMessage() == 107) {
					$response = $this->returnError($e->getMessage(),$responseData);
				} else {
					$response = $this->returnError($e->getMessage());
				}

			}
		} else {
			$success = false;
			$response = $this->returnError('500');
		}

		# save response result and API request

		return $this->returnJsonResult($response);

	}

	private function debitCreditBalance($params) {

		$playerId = @$params['playerId'];
		$gameUsername = $this->api->getGameUsernameByPlayerId($playerId);
		$playerName = $this->api->getPlayerUsernameByGameUsername($gameUsername);
		$amount = @$params['amount'];
		$action = @$params['action'];
		$transactionId = isset($params['transactionId'])?$params['transactionId']:$params['referenceTransactionId'];

		$success = false;
		$controller = $this;
		$before_balance = $this->api->queryPlayerBalance($playerName)['balance'];
		if($action == self::ACTION_DEBIT){
			$success = $this->lockAndTransForPlayerBalance($playerId, function() use($controller,$playerId,$amount) {
				$isDeduct = $controller->wallet_model->decSubWallet($playerId, $controller->gamePlatformId, $amount);
				return $isDeduct;
			});
		} else if ($action == self::ACTION_CANCEL){
			$existingRow = $this->ruby_play_transactions->isRowByTransactionId($transactionId);
			$amount = $existingRow['amount'];
			if(!empty($existingRow)) {
				$success = $this->lockAndTransForPlayerBalance($playerId, function() use($controller,$playerId,$amount) {
					return $isAdded = $controller->wallet_model->incSubWallet($playerId, $controller->gamePlatformId, $amount);
				});
			} else {
				$success = false;
			}
		} else {
			$success = $this->lockAndTransForPlayerBalance($playerId, function() use($controller,$playerId,$amount) {
				$isAdded = $controller->wallet_model->incSubWallet($playerId, $controller->gamePlatformId,$amount);
				if($isAdded == false && $amount == 0) {
	            	$isAdded = true;
	            }
				return $isAdded;
			});
		}
		$after_balance = $this->api->queryPlayerBalance($playerName)['balance'];

		return array($success,$before_balance,$after_balance);

	}

	public function processTransactionRecord(&$trans_record){

		$data['action'] = isset($trans_record['action']) ? $trans_record['action'] : null;
		$data['sessionToken'] = isset($trans_record['sessionToken']) ? $trans_record['sessionToken'] : null;
		$data['playerId'] = isset($trans_record['playerId']) ? $trans_record['playerId'] : null;
		$data['currencyCode'] = isset($trans_record['currencyCode']) ? $trans_record['currencyCode'] : null;
		$data['gameId'] = isset($trans_record['gameId']) ? $trans_record['gameId'] : null;
		$data['amount'] = isset($trans_record['amount']) ? $trans_record['amount'] : null;
		$data['roundId'] = isset($trans_record['roundId']) ? $trans_record['roundId'] : null;
		$data['transactionId'] = isset($trans_record['transactionId']) ? $trans_record['transactionId'] : $trans_record['referenceTransactionId'];
		$data['deviceType'] = isset($trans_record['deviceType']) ? $trans_record['deviceType'] : null;
		$data['gameRoundEnd'] = isset($trans_record['gameRoundEnd']) ? $trans_record['gameRoundEnd'] : null;
		$data['referenceTransactionId'] = isset($trans_record['referenceTransactionId']) ? $trans_record['referenceTransactionId'] : null;
		$data['before_balance'] = isset($trans_record['before_balance']) ? $trans_record['before_balance'] : null;
		$data['after_balance'] = isset($trans_record['after_balance']) ? $trans_record['after_balance'] : null;
		$data['response_result_id'] = isset($trans_record['response_result_id']) ? $trans_record['response_result_id'] : null;
		$data['external_uniqueid'] = isset($trans_record['transactionId'])?$data['action']."-".$data['transactionId']:$data['action']."-".$trans_record['referenceTransactionId'];
		$data['start_at'] = $this->utils->getNowForMysql();
		$data['end_at'] = $this->utils->getNowForMysql();

		$trans_record = $data;

		$this->insertTransactionWithLog($trans_record);
	}

	private function returnError($errorCode, $responseData=null) {
		$returnData = array(
			"code" => $errorCode,
			"description" => self::ERROR_CODE[$errorCode],
		);

		if(!empty($responseData)) {
			$response = array_merge($responseData, $returnData);
		} else {
			$response = $returnData;
		}
		
		return $response;
	}

	private function returnSuccess($responseData){
		$code=self::CODE_SUCCESS;
		$data = array(
			"code" => $code,
			"description" => self::ERROR_CODE[$code],
		);
		$response = array_merge($responseData,$data);

		return $response;
	}

	private function saveResponseResult($success, $callMethod, $params, $response){
        //save to db
        $this->CI->load->model("response_result");
        $flag = $success ? Response_result::FLAG_NORMAL : Response_result::FLAG_ERROR;
        return $this->CI->response_result->saveResponseResult(
        	$this->gamePlatformId,
        	$flag,
        	$callMethod,
        	json_encode($params),
        	$response,
        	200,
        	null,
        	null
        );

    }

    private function insertTransactionWithLog($data) {

        $affected_rows = $this->ruby_play_transactions->insertTransaction($data);

        return $affected_rows;
    }


}