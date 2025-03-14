<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/BaseController.php';

class Pgsoft_seamless_service_api extends BaseController {

	protected $gamePlatformId;
	protected $currencyCode;
	private $api;
	private $requestParams;
	private $player_id;

	const RETURN_OK = [
		'error' => 0,
		'message' => 'Transaction is successful'
	];

	const RETURN_OK_ALREADY_EXIST = [
		'error' => 0,
		'message' => 'Transaction already exist.'
	];

	const ERROR_GET_BALANCE = [
		'error' => 1,
		'message' => 'Error encountered while getting balance of player'
	];
	const ERROR_PLAYER_LOCKED = [
		'error' => 1,
		'message' => 'Player is locked'
	];
	const ERROR_PLAYER_NOT_FOUND = [
		'error' => 1,
		'message' => 'Player not found'
	];
	const ERROR_NOT_ENOUGH_BALANCE = [
        'error' => 10,
        'message' => 'Player not enough balance'
    ];
    const SYSTEM_ERROR = [
    	'error' => 1038,
        'message' => 'System Error'
    ];
    const SYSTEM_ERROR_INVALID_PARAMETERS = [
    	'error' => 1038,
        'message' => 'Invalid parameters.'
    ];
    const ERROR_METHOD_NOT_EXIST = [
    	'error' => 1,
        'message' => 'Method not exists'
    ];
    const ERROR_IP_NOT_ALLOWED = [
    	'error' => 1038,
        'message' => 'IP Not Allowed',
		'http_code' => 401
    ];

    const UPDATE_INSERT_MESSAGE = 'no changes, data is already inserted';
	const DEBIT = 'bet';
	const SETTLE = 'settle';
	const CANCEL = 'cancelBet';
	const REDTIGER = 10;
	const PGSOFT = 11;
	const SPADEGAMING = 14;
	const EVOLUTION = 16;

    const MD5_FIELDS_FOR_ORIGINAL = [
    	'UserId','UserName','OrderTime','TransGuid','Stake','Winlost','TurnOver','Currency','ProviderId',
    	'ParentId','GameId','ProductType','TableName','PlayType','ExtraData','ModifyDate','WinloseDate','Status','ProviderStatus'
    ];
    const MD5_FLOAT_AMOUNT_FIELDS = [
    	'Stake','Winlost','TurnOver','CancelledStake'
    ];

	public function __construct() {

		parent::__construct();
		$this->load->model(array('wallet_model','game_provider_auth','common_token','player_model','game_description_model','external_system',
			'common_seamless_wallet_transactions','original_game_logs_model','pgsoft_seamless_game_logs'));
		// $this->game_api = $this->utils->loadExternalSystemLibObject($this->gamePlatformId);
		$this->requestParams = new stdClass();

	}

	public function index($currency,$api) {
		$this->processRequest();
		$this->retrieveHeaders();

		$this->currencyCode = $currency;
		$this->gamePlatformId = $api;
		$is_valid=$this->getCurrencyAndValidateDB();
		if($is_valid) {
			if (!$this->external_system->isGameApiActive($this->gamePlatformId) || $this->external_system->isGameApiMaintenance($this->gamePlatformId)) {
				$this->utils->debug_log('PGSoft is inactive/maintenance (Error Response Result)');
				return $this->setResponse(self::ERROR_METHOD_NOT_EXIST['error'],self::ERROR_METHOD_NOT_EXIST['message']);
			}
			$this->api = $this->utils->loadExternalSystemLibObject($this->gamePlatformId);

			//check if IP whitelisted
			if(!$this->api->validateWhiteIP()){
				$extra = ['http_code'=>self::ERROR_IP_NOT_ALLOWED['http_code']];
				return $this->setResponse(self::ERROR_IP_NOT_ALLOWED['error'],self::ERROR_IP_NOT_ALLOWED['message'], $extra);
			} 

			$method = $this->requestParams->params->action;

			if(!method_exists($this, $method)) {
				return $this->setResponse(self::ERROR_METHOD_NOT_EXIST['error'],self::ERROR_METHOD_NOT_EXIST['message']);
			}

			$response = $this->$method();
			return $response;
		}
	}

	protected function processRequest(){
		$request = file_get_contents('php://input');
		$this->CI->utils->debug_log(__FUNCTION__, 'PGSoft (Raw Input): ', $request);
		$decoded_params=json_decode($request);
		$this->CI->utils->debug_log(__FUNCTION__, 'PGSoft (Raw array input): ', $decoded_params);
		$this->requestParams->params = $decoded_params;

		return $this->requestParams;
	}

	/**
	 * getCurrencyAndValidateDB
	 * @param  array $reqParams
	 * @return [type]            [description]
	 */
	private function getCurrencyAndValidateDB() {
		if(isset($this->currencyCode) && !empty($this->currencyCode)) {
			# Get Currency Code for switching of currency and db forMDB
			$is_valid=$this->validateCurrencyAndSwitchDB($this->currencyCode);

			return $is_valid;
		} else {
			return false;
		}
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

	protected function getBalance() {
		$gameUsername = $this->requestParams->params->userName;
		list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByUsername($gameUsername);
		$this->player_id = $playerId = $player->player_id;
		//$player_info = (array) $this->api->getPlayerInfoByUsername($user_name);

        if($playerStatus && !empty($playerId)) {
			
			$player_info = [];
			$player_info['username']=$user_name=$player_username;
			$player_info['playerId']=$playerId;	        

			$balance = 0;
			$trans_success = $this->lockAndTransForPlayerBalance($playerId, function() use($user_name,&$balance) {
				$balance = $this->api->queryPlayerBalance($user_name)['balance'];
				if($balance===false){
					return false;
				}

				return true;
			});

			$data = array(
				"balance" => $balance,
			);

			if($trans_success) {
				return $this->setResponse(self::RETURN_OK['error'], self::RETURN_OK['message'], $data);
			} else {
				return $this->setResponse(self::ERROR_GET_BALANCE['error'], self::ERROR_GET_BALANCE['message']);
			}
        } else {
        	return $this->setResponse(self::ERROR_PLAYER_NOT_FOUND['error'], self::ERROR_PLAYER_NOT_FOUND['message']);
        }
	}

	protected function bet() {

		$encode_trans = json_encode($this->requestParams->params->data[0]);
		$trans_record = json_decode($encode_trans,true);
		$gameUsername = $trans_record['UserName'];
		$playerId = $this->api->getPlayerIdByGameUsername($gameUsername);
		$player_username = $player = null;
		list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByUsername($gameUsername);
		$this->player_id = $playerId = $player->player_id;
		
		$player_info = [];
		$player_info['username']=$player_username;
		$player_info['playerId']=$playerId;

		$isBetExistsAlready = false;

		if($playerStatus && !empty($playerId)) {
			$this->player_id = $playerId;
			
			$controller = $this;
			$trans_code = [
				'error' => self::RETURN_OK['error'],
				'message' => self::RETURN_OK['message']
			];
			$trans_success = $this->lockAndTransForPlayerBalance($playerId, function() use($controller,$playerId,$player_info,&$trans_code,&$isBetExistsAlready,$trans_record) {
				$player_balance = $controller->api->queryPlayerBalance($player_info['username'])['balance'];
				$transaction_id = $trans_record['TransGuid'];
				$provider_id = $trans_record['ProviderId'];
				$api_id = $this->switchApiIdBySubPlatform($provider_id);
				$existingRow = $this->common_seamless_wallet_transactions->getTransactionObjectByField($api_id,$transaction_id,"transaction_id",SELF::DEBIT);

				if(!empty($existingRow)){
					$isBetExistsAlready = true;
					$trans_code['after_balance'] = $player_balance;
					return true;
					
				}
				
				$betAmount = isset($trans_record['Stake']) ? $trans_record['Stake'] : null;

				if(!is_numeric($betAmount)){
					$trans_code['error'] = self::SYSTEM_ERROR_INVALID_PARAMETERS['error'];
					$trans_code['message'] = self::SYSTEM_ERROR_INVALID_PARAMETERS['message'];
					return false;
				}

				if (($player_balance - $betAmount) < 0) {
					$trans_code['error'] = self::ERROR_NOT_ENOUGH_BALANCE['error'];
					$trans_code['message'] = self::ERROR_NOT_ENOUGH_BALANCE['message'];
					return false;
				}

				$trans_code = $controller->adjustWallet(self::DEBIT, $betAmount, $player_info, $trans_record, $player_balance);

				if($trans_code['error'] != self::RETURN_OK['error']) {
					return false;
				}

				if($trans_code['error'] != self::RETURN_OK['error'] && $trans_code['message'] == self::UPDATE_INSERT_MESSAGE) {
					return false;
				}

				return true;
			});

			if(!array_key_exists('after_balance', $trans_code)) {
                $this->api->queryPlayerBalance($player_info['username'])['balance'];
            }

			if($trans_success) {
				
                $data = [
					'balance' => (float)$trans_code['after_balance'],
					'data' => [[
						'error' => $trans_code['error'],
						'transId' => $trans_record['TransGuid'],
						'userId' => $trans_record['UserId'],
						'balance' => (float)$trans_code['after_balance']
					]]
	
				];

				if($isBetExistsAlready){
					$trans_code['message'] = self::RETURN_OK_ALREADY_EXIST['message'];
				}

                return $this->setResponse($trans_code['error'], $trans_code['message'], $data);
            }
            else {

                if($trans_code['error'] != self::RETURN_OK['error']) {
					return $this->setResponse($trans_code['error'], $trans_code['message']);
				}

                return $this->setResponse(self::SYSTEM_ERROR["error"], self::SYSTEM_ERROR["message"]);
            }

			if($trans_code['error'] != self::RETURN_OK['error']) {
				return $this->setResponse($trans_code['error'], $trans_code['message']);
			}

		}

		return $this->setResponse(self::ERROR_PLAYER_NOT_FOUND['error'], self::ERROR_PLAYER_NOT_FOUND['message']);
	}

	protected function settle() {

		$trans_data = $this->requestParams->params->data;

		$encode_trans = json_encode($trans_data[0]);
		$trans_record = json_decode($encode_trans,true);
		//$gameUsername = $trans_record['UserName'];
		//$playerId = $this->api->getPlayerIdByGameUsername($gameUsername);

		// if(!empty($playerId)) {

			$response_data = array();

			$is_success_all = true;

			$err_message = "";

			foreach($trans_data as $data) {

				$encode_trans = json_encode($data);
				$trans_record = json_decode($encode_trans,true);

				$gameUsername = $trans_record['UserName'];
				$this->player_id = $playerId = null;
				list($playerStatus, $player, $gameUsername, $player_username) = $this->getPlayerByUsername($gameUsername);
				$this->player_id = $playerId = $player->player_id;

				$player_info = [];
				$player_info['username']=$player_username;
				$player_info['playerId']=$playerId;

				if($playerStatus || !empty($playerId)) {

					$trans_code = [
						'error' => self::RETURN_OK['error'],
						'message' => self::RETURN_OK['message']
					];

					$controller = $this;

					$resp_data = array();

					$resp_data["user_id"] = $trans_record["UserId"];

					$trans_success = $this->lockAndTransForPlayerBalance($playerId, function() use($controller,$playerId,$player_info,&$trans_code, $trans_data,&$response_data, $trans_record, &$resp_data) {
						
						$player_balance = $controller->api->queryPlayerBalance($player_info['username'])['balance'];
				
						$transaction_id = $trans_record['TransGuid'];

						$resp_data["transaction_id"] = $transaction_id; // need this on response

						

						$provider_id = $trans_record['ProviderId'];

						$api_id = $controller->switchApiIdBySubPlatform($provider_id);

						$existingRow = $controller->common_seamless_wallet_transactions->getTransactionObjectByField($api_id,$transaction_id,"transaction_id",self::SETTLE);

						if(!empty($existingRow)){

							$trans_code['after_balance'] = $player_balance;
	
							$resp_data["error"] = self::RETURN_OK['error'];
	
							//$response_data[] = $resp_data;
	
							return true;
							
						}

						$WinlostAmount = isset($trans_record['Winlost']) ? $trans_record['Winlost'] : null;

						if(!is_numeric($WinlostAmount)){
							$trans_code['error'] = self::SYSTEM_ERROR_INVALID_PARAMETERS['error'];
							$trans_code['message'] = self::SYSTEM_ERROR_INVALID_PARAMETERS['message'];
							return false;
						}

						$trans_code = $controller->adjustWallet(self::SETTLE, $WinlostAmount, $player_info, $trans_record, $player_balance);

						$resp_data["error"] = self::RETURN_OK['error'];
						
						if($trans_code['error'] != self::RETURN_OK['error']) {
							$resp_data["error"] = self::SYSTEM_ERROR['error'];
							return false;
						}

						// $response_data[] = $resp_data;

						return true;

					});


					if(!$trans_success) {

						// this condition is to check if locked because by default the the response code is success
						if($trans_code['error'] == self::RETURN_OK['error']) {
							$transaction_id = $trans_record['TransGuid'];
							$resp_data["transaction_id"] = $transaction_id;
							$resp_data["error"] = self::ERROR_PLAYER_LOCKED['error'];
							$resp_data["error_message"] = self::ERROR_PLAYER_LOCKED['message']; // for player locked only
							$err_message = self::ERROR_PLAYER_LOCKED['message'];
							$resp_data["after_balance"] = isset($trans_code["after_balance"]) ? $trans_code["after_balance"] : $this->api->queryPlayerBalance($player_info['username'])['balance'];
							$is_success_all = false;

							$response_data[] = $resp_data;

						} else {

							$resp_data["after_balance"] = isset($trans_code["after_balance"]) ? $trans_code["after_balance"] : $this->api->queryPlayerBalance($player_info['username'])['balance'];

							$response_data[] = $resp_data;

							$is_success_all = false;

							$err_message = isset($trans_code["message"]) ? $trans_code["message"] : self::SYSTEM_ERROR["message"];

						}

					} else {
						$resp_data["after_balance"] = isset($trans_code["after_balance"]) ? $trans_code["after_balance"] : $this->api->queryPlayerBalance($player_info['username'])['balance'];
						$response_data[] = $resp_data;
					}


				} else {
					return $this->setResponse(self::ERROR_PLAYER_NOT_FOUND['error'], self::ERROR_PLAYER_NOT_FOUND['message']);
				}

			}

			$transaction_data = array();
			
			$balance = 0;

			foreach($response_data as $data) {

				$transaction_id = $data["transaction_id"];

				$transaction_data[] = array(
					'error' => isset($data["error"])?$data["error"]:null,
					'transId' => $transaction_id,
					'userId' => isset($data["user_id"])?$data["user_id"]:null,
					'balance' => (float)$data['after_balance']
				);

				$balance = (float)$data['after_balance'];

			}

			if($is_success_all) {

				$data = [
					'balance' => $balance,
					'data' => $transaction_data
	
				];

				return $this->setResponse(self::RETURN_OK['error'], self::RETURN_OK['error'], $data);

			} else {

				return $this->setResponse(self::SYSTEM_ERROR["error"], self::SYSTEM_ERROR["message"]);

			}

			

			// $this->player_id = $playerId;
			// $player_info = $this->player_model->getPlayerInfoById($playerId);

			// $controller = $this;
			// $trans_code = [
			// 	'error' => self::RETURN_OK['error'],
			// 	'message' => self::RETURN_OK['message']
			// ];

			// $response_data = array();

			// $trans_success = $this->lockAndTransForPlayerBalance($playerId, function() use($controller,$playerId,$player_info,&$trans_code, $trans_data,&$response_data) {

			// 	$iteration = 1;
			// 	foreach($trans_data as $data) {

			// 		$resp_data = array();

				
			// 		$encode_trans = json_encode($data);
			// 		$trans_record = json_decode($encode_trans,true);

			// 		$transaction_id = $trans_record['TransGuid'];

			// 		$resp_data["transaction_id"] = $transaction_id; // need this on response

			// 		$provider_id = $trans_record['ProviderId'];

			// 		$api_id = $this->switchApiIdBySubPlatform($provider_id);

			// 		$existingRow = $this->common_seamless_wallet_transactions->getTransactionObjectByField($api_id,$transaction_id,"transaction_id",self::SETTLE);

			// 		if(!empty($existingRow)){

			// 			$trans_code['after_balance'] = $this->api->queryPlayerBalance($player_info['username'])['balance'];

			// 			$resp_data["error"] = self::RETURN_OK['error'];

			// 			$response_data[] = $resp_data;

			// 			if(count($trans_data) > $iteration) {
			// 				$iteration++;
			// 				continue;
			// 			} else {
			// 				return true;
			// 			}
						
			// 		}

			// 		$WinlostAmount = isset($trans_record['Winlost']) ? $trans_record['Winlost'] : null;

			// 		$trans_code = $controller->adjustWallet(self::SETTLE, $WinlostAmount, $player_info, $trans_record);

			// 		$resp_data["error"] = self::RETURN_OK['error'];
					
			// 		if($trans_code['error'] != self::RETURN_OK['error']) {
			// 			$resp_data["error"] = self::SYSTEM_ERROR['error'];
			// 			//return false;
			// 		}

			// 		// if($trans_code['error'] != self::RETURN_OK['error'] && $trans_code['message'] == self::UPDATE_INSERT_MESSAGE) {
			// 		// 	return false;
			// 		// } // need to remove this. it will return false if one of the transaction is already inserted.

			// 		$response_data[] = $resp_data;
					
			// 		$iteration++;

			// 	}

			// 	return true;
			// });

			// if($trans_success) {

			// 	$transaction_data = array();
			// 	// loop transactionIds
			// 	foreach($response_data as $resp_data) {

			// 		$this->CI->utils->debug_log('REYNARD RESP DATA', $resp_data);

			// 		$transaction_id = $resp_data["transaction_id"];

			// 		if($resp_data["error"] != self::RETURN_OK['error']) {
			// 			$trans_code['error'] = SELF::SYSTEM_ERROR['error'];
			// 			$trans_code['message'] = SELF::SYSTEM_ERROR['message'];
			// 		}
			// 		$transaction_data[] = array(
			// 			'error' => $resp_data["error"],
			// 			'transId' => $transaction_id,
			// 			'userId' => $trans_record['UserId'],
			// 			'balance' => (float)$trans_code['after_balance']
			// 		);
					
			// 	}
				
            //     $data = [
			// 		'balance' => (float)$trans_code['after_balance'],
			// 		'data' => $transaction_data
	
			// 	];

            //     return $this->setResponse($trans_code['error'], $trans_code['message'], $data);
            // }
            // else {

            //     if($trans_code['error'] != self::RETURN_OK['error']) {
			// 		return $this->setResponse($trans_code['error'], $trans_code['message']);
			// 	}

            //     return $this->setResponse(self::SYSTEM_ERROR["error"], self::SYSTEM_ERROR["message"]);
            // }
			
		// }

		// return $this->setResponse(self::ERROR_PLAYER_NOT_FOUND['error'], self::ERROR_PLAYER_NOT_FOUND['message']);

	}

	protected function cancelBet() {

		$encode_trans = json_encode($this->requestParams->params->data[0]);
		$trans_record = json_decode($encode_trans,true);
		$gameUsername = $trans_record['UserName'];
		$playerId = $this->api->getPlayerIdByGameUsername($gameUsername);

		if(!empty($playerId)) {
			$this->player_id = $playerId;
			$player_info = $this->player_model->getPlayerInfoById($playerId);

			$controller = $this;
			$trans_code = [
				'error' => self::RETURN_OK['error'],
				'message' => self::RETURN_OK['message']
			];
			$trans_success = $this->lockAndTransForPlayerBalance($playerId, function() use($controller,$playerId,$player_info,&$trans_code,$trans_record) {

				$transaction_id = $trans_record['TransGuid'];
				$provider_id = $trans_record['ProviderId'];
				$api_id = $this->switchApiIdBySubPlatform($provider_id);
				$existingRow = $this->common_seamless_wallet_transactions->getTransactionObjectByField($api_id,$transaction_id,"transaction_id",SELF::CANCEL);

				if(!empty($existingRow)){

					$trans_code['after_balance'] = $this->api->queryPlayerBalance($player_info['username'])['balance'];
					return true;
					
				}

				$cancelledAmount = isset($trans_record['Stake']) ? $trans_record['Stake'] : null;

				$trans_code = $controller->adjustWallet(self::CANCEL, $cancelledAmount, $player_info, $trans_record);
				
				if($trans_code['error'] != self::RETURN_OK['error']) {
					return false;
				}

				if($trans_code['error'] != self::RETURN_OK['error'] && $trans_code['message'] == self::UPDATE_INSERT_MESSAGE) {
					return false;
				}

				return true;
			});

			if($trans_success) {
				
                $data = [
					'balance' => (float)$trans_code['after_balance'],
					'data' => [[
						'transId' => $trans_record['TransGuid'],
						'userId' => $trans_record['UserId'],
						'balance' => (float)$trans_code['after_balance']
					]]
	
				];
	
				return $this->setResponse($trans_code['error'], $trans_code['message'], $data);
            }
            else {

                if($trans_code['error'] != self::RETURN_OK['error']) {
					return $this->setResponse($trans_code['error'], $trans_code['message']);
				}

                return $this->setResponse(self::SYSTEM_ERROR["error"], self::SYSTEM_ERROR["message"]);
            }
			
		} else {
			return $this->setResponse(self::ERROR_PLAYER_NOT_FOUND['error'], self::ERROR_PLAYER_NOT_FOUND['message']);
		}
	}

	private function adjustWallet($action, $amount, $player_info, $trans_record, $before_balance=null) {

		$trans_code = [
			'error' => self::RETURN_OK['error'],
			'message' => self::RETURN_OK['message']
		];


		$trans_code['before_balance'] = $after_balance = $before_balance;
		if($before_balance===null){
			$trans_code['before_balance'] =  $before_balance = $this->api->queryPlayerBalance($player_info['username'])['balance'];	
		}
        
        if($amount == 0) {
            $trans_code['error'] = self::RETURN_OK['error'];
            $trans_code['message'] = self::RETURN_OK['message'];
            $trans_code['after_balance'] = $before_balance;
        }
        else {
            if($action == self::DEBIT) {
                $deduct_balance = $this->wallet_model->decSubWallet($player_info['playerId'], $this->api->getPlatformCode(), $amount);
				$after_balance = $before_balance - abs($amount);
                if(!$deduct_balance) {
					$after_balance = $before_balance;
                    $trans_code['error'] = self::SYSTEM_ERROR['error'];
                    $trans_code['message'] = self::SYSTEM_ERROR['message'];
                }
                //$trans_code['after_balance'] = $this->api->queryPlayerBalance($player_info['username'])['balance'];
				
            } elseif ($action == self::SETTLE) {
                $add_balance = $this->wallet_model->incSubWallet($player_info['playerId'], $this->api->getPlatformCode(), $amount);
				$after_balance = $before_balance + abs($amount);
                if(!$add_balance) {
					$after_balance = $before_balance;
                    $trans_code['error'] = self::SYSTEM_ERROR['error'];
                    $trans_code['message'] = self::SYSTEM_ERROR['message'];
                }
                //$trans_code['after_balance'] = $this->api->queryPlayerBalance($player_info['username'])['balance'];
				
            } elseif ($action == self::CANCEL) {
                $add_balance = $this->wallet_model->incSubWallet($player_info['playerId'], $this->api->getPlatformCode(), $amount);
				$after_balance = $before_balance + abs($amount);
                if(!$add_balance) {
					$after_balance = $before_balance;
                    $trans_code['error'] = self::SYSTEM_ERROR['error'];
                    $trans_code['message'] = self::SYSTEM_ERROR['message'];
                }
                //$trans_code['after_balance'] = $this->api->queryPlayerBalance($player_info['username'])['balance'];
				
            } else {
                $trans_code['error'] = self::ERROR_METHOD_NOT_EXIST['error'];
                $trans_code['message'] = self::ERROR_METHOD_NOT_EXIST['message'];
                return $trans_code;
            }
        }

		$trans_code['after_balance'] = $after_balance;

		$insertOnTrans = $this->processTransaction($trans_code['before_balance'],$trans_code['after_balance'],$trans_record,$player_info);
		// print_r($insertOnTrans);exit;
		// if($insertOnTrans) {
		// 	$trans_record = $this->processOriginalGameLogs($trans_code['before_balance'],$trans_code['after_balance'],$trans_record,$player_info);
		// 	$isInsertOrUpdate = $this->insertDataOnOriginal($trans_record);
			
		// 	if(!$isInsertOrUpdate['success'] && $isInsertOrUpdate['result']['message'] == self::UPDATE_INSERT_MESSAGE) {
		// 		$existingRow = $this->pgsoft_seamless_game_logs->isRowByTransactionId($trans_record[0]['TransGuid']);

		// 		$trans_code = [
		// 			'error' => self::RETURN_OK['error'],
		// 			'message' => self::UPDATE_INSERT_MESSAGE,
		// 			'after_balance' => $existingRow['after_balance']
		// 		];
		// 	} 
		// }

		if(!$insertOnTrans['success']) {
			$trans_code = [
				'error' => self::RETURN_OK['error'],
				'message' => self::UPDATE_INSERT_MESSAGE,
				'after_balance' => $insertOnTrans['result']['after_balance']
			];
		}

		return $trans_code;

	}
    
    private function processTransaction($before_balance,$after_balance,$trans_record,$player_info) {

		if(!isset($player_info['playerId']) || empty($player_info['playerId'])){
			$playerId = $this->player_model->getPlayerIdByUsername($player_info['username']);
		}else{
			$playerId = $player_info['playerId'];
		}

    	$apiId = $this->gamePlatformId;
    	$transaction_type = $this->requestParams->params->action;
    	
    	$UserId = isset($trans_record['UserId']) ? $trans_record['UserId'] : null;
		$UserName = isset($trans_record['UserName']) ? $trans_record['UserName'] : null;
		$OrderTime = isset($trans_record['OrderTime']) ? $this->api->gameTimeToServerTime($trans_record['OrderTime']) : null;
		$TransGuid = isset($trans_record['TransGuid']) ? $trans_record['TransGuid'] : null;
		$Stake = isset($trans_record['Stake']) ? $trans_record['Stake'] : null;
		$Winlost = isset($trans_record['Winlost']) ? $trans_record['Winlost'] : null;
		$TurnOver = isset($trans_record['TurnOver']) ? $trans_record['TurnOver'] : null;
		$Currency = isset($trans_record['Currency']) ? $trans_record['Currency'] : null;
		$ProviderId = isset($trans_record['ProviderId']) ? $trans_record['ProviderId'] : null;
		$ParentId = isset($trans_record['ParentId']) ? $trans_record['ParentId'] : null;
		$GameId = isset($trans_record['GameId']) ? $trans_record['GameId'] : null;
		$ProductType = isset($trans_record['ProductType']) ? $trans_record['ProductType'] : null;
		$TableName = isset($trans_record['TableName']) ? $trans_record['TableName'] : null;
		$PlayType = isset($trans_record['PlayType']) ? $trans_record['PlayType'] : null;
		$ExtraData = isset($trans_record['ExtraData']) ? $trans_record['ExtraData'] : null;
		$ModifyDate = isset($trans_record['ModifyDate']) ? $this->api->gameTimeToServerTime($trans_record['ModifyDate']) : null;
		$WinloseDate = isset($trans_record['WinloseDate']) ? $this->api->gameTimeToServerTime($trans_record['WinloseDate']) : null;
		$Status = isset($trans_record['Status']) ? $trans_record['Status'] : null;
		$ProviderStatus = isset($trans_record['ProviderStatus']) ? $trans_record['ProviderStatus'] : null;
		$externalUniqueId = $TransGuid;
        $now = (new DateTime())->format('Y-m-d H:i:s');
        $startTime = isset($OrderTime) ? $OrderTime : $now;
        $endTime = isset($WinloseDate) ? $WinloseDate : (isset($ModifyDate) ? $ModifyDate : $now);

    	$extra_info = [
    		'playerId' => $playerId,
            'UserId' => $UserId,
            'UserName' => $UserName,
            'OrderTime' => $OrderTime,
            'TransGuid' => $TransGuid,
            'Stake' => $Stake,
            'Winlost' => $Winlost,
            'TurnOver' => $TurnOver,
            'Currency' => $Currency,
            'ProviderId' => $ProviderId,
            'ParentId' => $ParentId,
            'GameId' => $GameId,
            'ProductType' => $ProductType,
            'TableName' => $TableName,
            'PlayType' => $PlayType,
            'ExtraData' => $ExtraData,
            'ModifyDate' => $ModifyDate,
            'WinloseDate' => $WinloseDate,
            'Status' => $Status,
            'ProviderStatus' => $ProviderStatus,
            'transaction_type' => $transaction_type
        ];

        $extraInfo = json_encode($extra_info);

        $apiId = $this->switchApiIdBySubPlatform($ProviderId);
        if($apiId == LIVE12_EVOLUTION_SEAMLESS_API){
        	$ProductType = $TableName;
        }

        $gameRecords = [
            [
                'game_platform_id' => $apiId,
                'amount' => $transaction_type == self::SETTLE ? $Winlost : $Stake,
                'game_id' => $ProductType,
                'transaction_type' => $transaction_type,
                'status' => $Status,
                'response_result_id' => $this->utils->getRequestId(),
                'external_unique_id' => $transaction_type.'-'.$externalUniqueId,
                'extra_info' => $extraInfo,
                'start_at' => $startTime,
                'end_at' => $endTime,
                'transaction_id' => $externalUniqueId,
                'before_balance' => $before_balance,
                'after_balance' => $after_balance,
                'player_id' => $playerId,
                'round_id' => $GameId
            ]
        ];

        $this->processGameRecords($gameRecords);

        $success=true;
        $result=[];
        $existingRow = $this->common_seamless_wallet_transactions->getRoundRowArray($this->gamePlatformId,$GameId,$transaction_type);
        $getLastExistingRow = end($existingRow);
        if($getLastExistingRow) {
        	$dataRecord = $gameRecords[0];
        	foreach($dataRecord as $key => $val){
	    		if($key == 'amount') {
					if($val != $getLastExistingRow[$key]){
						$external_uniqueid = 'update-'.$transaction_type.'-'.$externalUniqueId.'-'.$before_balance.$after_balance;
						$gameRecords[0]['external_unique_id'] = $external_uniqueid;
						$this->common_seamless_wallet_transactions->insertRow($gameRecords[0]);
					} else {
						$result['after_balance'] = $getLastExistingRow['after_balance'];
						$success=false;
					}
	    		}
			}
        } else {
        	$this->common_seamless_wallet_transactions->insertRow($gameRecords[0]);
        }

        if(!array_key_exists('after_balance', $result)) {
            $result['after_balance'] = $this->api->queryPlayerBalance($player_info['username'])['balance'];
        }

        return ['success' => $success, 'result' => $result];
    }

    private function switchApiIdBySubPlatform($sub_provider_id) {
    	switch ($sub_provider_id) {
    		case self::REDTIGER:
    			$game_platform_id = LIVE12_REDTIGER_SEAMLESS_API;
    			break;
    		case self::PGSOFT:
    			$game_platform_id = LIVE12_PGSOFT_SEAMLESS_API;
    			break;
    		case self::SPADEGAMING:
    			$game_platform_id = LIVE12_SPADEGAMING_SEAMLESS_API;
    			break;
    		case self::EVOLUTION:
    			$game_platform_id = LIVE12_EVOLUTION_SEAMLESS_API;
    			break;
    		default:
    			$game_platform_id = LIVE12_SEAMLESS_GAME_API;
    			break;
    	}

    	return $game_platform_id;
    }

    /**
     * Process Game Records Array
     * 
     * @param array $gameRecords
     * @return void
     */
    public function processGameRecords(&$gameRecords){
        $elapsed=intval($this->utils->getExecutionTimeToNow()*1000);
        if(! empty($gameRecords)){
            foreach($gameRecords as $index => $record){
                $data['game_platform_id'] = isset($record['game_platform_id']) ? $record['game_platform_id'] : null;
                $data['amount'] = isset($record['amount']) ? $record['amount'] : null;
                $data['before_balance'] = isset($record['before_balance']) ? $record['before_balance'] : null;
                $data['after_balance'] = isset($record['after_balance']) ? $record['after_balance'] : null;
                $data['player_id'] = isset($record['player_id']) ? $record['player_id'] : null;
                $data['game_id'] = isset($record['game_id']) ? $record['game_id'] : null;
                $data['transaction_type'] = isset($record['transaction_type']) ? $record['transaction_type'] : null;
                $data['status'] = isset($record['status']) ? $record['status'] : null;
                $data['response_result_id'] = isset($record['response_result_id']) ? $record['response_result_id'] : null;
                $data['external_unique_id'] = isset($record['external_unique_id']) ? $record['external_unique_id'] : null;
                $data['extra_info'] = isset($record['extra_info']) ? $record['extra_info'] : null;
                $data['start_at'] = isset($record['start_at']) ? $record['start_at'] : null;
                $data['end_at'] = isset($record['end_at']) ? $record['end_at'] : null;
                $data['transaction_id'] = isset($record['transaction_id']) ? $record['transaction_id'] : null;
                $data['elapsed_time'] = isset($record['elapsed_time']) ? $record['elapsed_time'] : $elapsed;
                $data['round_id'] = isset($record['round_id']) ? $record['round_id'] : null;

                $gameRecords[$index] = $data;
                unset($data);
            }
        }
    }

 //    private function processOriginalGameLogs($before_balance,$after_balance,$trans_record,$player_info) {

 //    	$transaction_type = $this->requestParams->params->action;
 //    	$data['UserId'] = isset($trans_record['UserId']) ? $trans_record['UserId'] : null;
	// 	$data['UserName'] = isset($trans_record['UserName']) ? $trans_record['UserName'] : null;
	// 	$data['OrderTime'] = isset($trans_record['OrderTime']) ? $this->api->gameTimeToServerTime($trans_record['OrderTime']) : null;
	// 	$data['TransGuid'] = isset($trans_record['TransGuid']) ? $trans_record['TransGuid'] : null;
	// 	$data['Stake'] = isset($trans_record['Stake']) ? $trans_record['Stake'] : null;
	// 	$data['Winlost'] = isset($trans_record['Winlost']) ? $trans_record['Winlost'] : null;
	// 	$data['TurnOver'] = isset($trans_record['TurnOver']) ? $trans_record['TurnOver'] : $trans_record['Stake'];
	// 	$data['Currency'] = isset($trans_record['Currency']) ? $trans_record['Currency'] : null;
	// 	$data['ProviderId'] = isset($trans_record['ProviderId']) ? $trans_record['ProviderId'] : null;
	// 	$data['ParentId'] = isset($trans_record['ParentId']) ? $trans_record['ParentId'] : null;
	// 	$data['GameId'] = isset($trans_record['GameId']) ? $trans_record['GameId'] : null;
	// 	$data['ProductType'] = isset($trans_record['ProductType']) ? $trans_record['ProductType'] : null;
	// 	$data['TableName'] = isset($trans_record['TableName']) ? $trans_record['TableName'] : null;
	// 	$data['PlayType'] = isset($trans_record['PlayType']) ? $trans_record['PlayType'] : null;
	// 	$data['ExtraData'] = isset($trans_record['ExtraData']) ? $trans_record['ExtraData'] : null;
	// 	$data['ModifyDate'] = isset($trans_record['ModifyDate']) ? $this->api->gameTimeToServerTime($trans_record['ModifyDate']) : null;
	// 	$data['WinloseDate'] = isset($trans_record['WinloseDate']) ? $this->api->gameTimeToServerTime($trans_record['WinloseDate']) : null;
	// 	$data['Status'] = isset($trans_record['Status']) ? $trans_record['Status'] : null;
	// 	$data['ProviderStatus'] = isset($trans_record['ProviderStatus']) ? $trans_record['ProviderStatus'] : null;
	// 	$data['transaction_type'] = isset($transaction_type) ? $transaction_type : null;
	// 	$data['transaction_status'] = $transaction_type == 'cancelBet' ? $transaction_type : null;
	// 	$data['CancelledStake'] = $transaction_type == 'cancelBet' ? $data['Stake'] : null;

 //        $now = (new DateTime())->format('Y-m-d H:i:s');
	// 	$data['start_at'] = isset($data['OrderTime']) ? $data['OrderTime'] : $now;
	// 	$data['end_at'] = isset($data['WinloseDate']) ? $data['WinloseDate'] : (isset($data['ModifyDate']) ? $data['WinloseDate'] : $now);

	// 	$data['response_result_id'] = isset($trans_record['response_result_id']) ? $trans_record['response_result_id'] : null;
	// 	$data['external_uniqueid'] = $data['TransGuid'];
	// 	$data['before_balance'] = $before_balance;
	// 	$data['after_balance'] = $after_balance;

	// 	$gameRecords[]=$data;

	// 	return $gameRecords;
 //    }

 //    private function insertDataOnOriginal($gameRecords) {
 //    	$dataResult = [
 //    		'data_count' => 0,
 //    		'data_count_insert' => 0,
 //    		'data_count_update' => 0
 //    	];
 //    	$success = true;
 //    	if(!empty($gameRecords)){
 //            list($insertRows, $updateRows)=$this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
 //                $this->api->getOriginalTable(),
 //                $gameRecords,
 //                'GameId',
 //                'GameId',
 //                self::MD5_FIELDS_FOR_ORIGINAL,
 //                'md5_sum',
 //                'id',
 //                self::MD5_FLOAT_AMOUNT_FIELDS
 //            );
 //            $this->CI->utils->debug_log('after process available rows', 'gamerecords ->',count($gameRecords), 'insertrows->',count($insertRows), 'updaterows->',count($updateRows));

 //            $dataResult['data_count'] = count($gameRecords);
 //            if(empty($updateRows) && empty($insertRows)) {
 //            	$success = false;
 //            	$dataResult['message'] = self::UPDATE_INSERT_MESSAGE;
 //            }
 //            if (!empty($insertRows)) {
 //                $dataResult['data_count_insert'] += $this->updateOrInsertOriginalGameLogs($insertRows, 'insert');
 //            }

 //            unset($insertRows);
 //            if (!empty($updateRows)) {
 //            	$game_id = $updateRows[0]['GameId'];
 //            	$checkUpdates = $this->updateOriginalLogs($game_id,$updateRows[0]);
 //            	$updateRows[0] = $checkUpdates;
            	
 //            	if(!empty($updateRows[0])) {
 //                	$dataResult['data_count_update'] += $this->updateOrInsertOriginalGameLogs($updateRows, 'update');
 //            	}
 //            }
 //            unset($updateRows);

 //        }

 //        return array('success'=>$success, 'result'=>$dataResult);
	// }

	// private function updateOrInsertOriginalGameLogs($data, $queryType){
 //        $dataCount=0;
 //        if(!empty($data)){
 //            foreach ($data as $record) {
 //                if ($queryType == 'update') {
 //                    $record['updated_at'] = $this->utils->getNowForMysql();
 //                    $this->CI->original_game_logs_model->updateRowsToOriginal($this->api->getOriginalTable(), $record);
 //                } else {
 //                    unset($record['id']);
 //                    $record['created_at'] = $this->utils->getNowForMysql();
 //                    $this->CI->original_game_logs_model->insertRowsToOriginal($this->api->getOriginalTable(), $record);
 //                }
 //                $dataCount++;
 //                unset($record);
 //            }
 //        }
 //        return $dataCount;
 //    }

 //    private function updateOriginalLogs($game_id, $updateRows){
 //    	$existingRow = $this->pgsoft_seamless_game_logs->isRowByRoundId($game_id);
 //    	$transaction_type = $this->requestParams->params->action;

 //    	foreach($updateRows as $key => $val){
 //    		if(in_array($key, self::MD5_FLOAT_AMOUNT_FIELDS)) {
 //    			$floatAmount = number_format((float)$existingRow[$key], 2, '.', '');
	// 			if($transaction_type != self::CANCEL){
	// 				if($transaction_type == 'settle') {
	// 					if($key == "Stake" || $key == "TurnOver"){
	// 						$updateRows['Stake'] = $existingRow['Stake'];
	// 						$updateRows['TurnOver'] = $existingRow['TurnOver'];
	// 						continue;
	// 					}
	// 				}
	// 				$updateRows[$key] = $floatAmount + $val;
	// 				$updateRows['before_balance'] = $existingRow['before_balance'];
	// 			} elseif($transaction_type == self::CANCEL) {
	// 				if($key == "Stake" || $key == "TurnOver"){
	// 					$updateRows['Stake'] = $existingRow['Stake'];
	// 					$updateRows['TurnOver'] = $existingRow['TurnOver'];
	// 					continue;
	// 				}
	// 				$updateRows[$key] = $floatAmount + $val;
	// 				$updateRows['before_balance'] = $existingRow['before_balance'];
	// 			} else {
	// 				return false;
	// 			}
 //    		}
	// 	}

	// 	return $updateRows;
 //    }

    private function setResponse($returnCode, $message, $data = []) {
    	$code = ['error' => $returnCode, 'message' => $message];
        $data = array_merge($code,$data);
        return $this->setOutput($data);
    }

    private function setOutput($data = []) {
        $flag = $data['error'] == 0 ? Response_result::FLAG_NORMAL : Response_result::FLAG_ERROR;
		$httpCode = (isset($data['http_code']))?$data['http_code']:200;
		unset($data['http_code']);

        $data = json_encode($data);
        $fields = ['player_id' => $this->player_id];

		$cost = intval($this->utils->getExecutionTimeToNow()*1000);

		
		
        if($this->api) {
            $this->CI->response_result->saveResponseResult(
                $this->api->getPlatformCode(),
                $flag,
                $this->requestParams->params->action,
                json_encode($this->requestParams->params),
                $data,
                $httpCode,
                null,
                is_array($this->headers)?json_encode($this->headers):$this->headers,
                $fields,
				false,
				null,
				$cost
            );
        }
		$this->output->set_status_header($httpCode);
        $this->output->set_content_type('application/json')->set_output($data);
        $this->output->_display();
        exit();
    }

	public function getPlayerByUsername($gameUsername){
		$player = $this->common_token->getPlayerCompleteDetailsByGameUsername($gameUsername, $this->gamePlatformId);		 
		
		if(!$player){		
			return [false, null, null, null];
		}
		$this->player = $player;
		return [true, $player, $player->game_username, $player->username];
	}

	public function retrieveHeaders() {
		$this->headers = getallheaders();
	}


}