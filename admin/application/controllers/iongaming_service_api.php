<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/BaseController.php';

class Iongaming_service_api extends BaseController {

	const RAW_REQUEST 		      = "RAW_REQUEST";
	const ACTION_BALANCE 	      = "GetAvailableCredit";
	const ACTION_DEDUCT_BALANCE   = "DeductBalance";
	const ACTION_ROLLBACK_BALANCE = "RollbackBalance";
	const ACTION_INSERT 	 	  = "Insert";
	const ACTION_SETTLE 	 	  = "Settle";

	const ACTION_PAYOUT  		  = "Settle";
	const ACTION_CANCEL  		  = "UserPlaceBetCancel";
	const DB_TRANS_ROLLBACK  	  = "Rollback";

	const RESPONSE_CODE  = [
		"SUCCESS"               => "SUCCESS",
		"MEMBER_NOT_FOUND"      => "MEMBER_NOT_FOUND",
		"GENERAL_ERROR"         => "GENERAL_ERROR",
		"MEMBER_NOT_FOUND"      => "MEMBER_NOT_FOUND",
		"INSUFFICIENT_BALANCE"  => "INSUFFICIENT_BALANCE",
		"SYSTEM_IN_MAINTENANCE" => "SYSTEM_IN_MAINTENANCE",
		"MEMBER_BLOCK"          => "MEMBER_BLOCK",
		"INVALID_ARG"           => "INVALID_ARG",
		"SYSTEM_CHECK"          => "SYSTEM_CHECK",
		"DATE_FORMAT_ERROR"     => "DATE_FORMAT_ERROR",
		"BET_REJECTED"          => "BET_REJECTED",
		"SETTLEMENT_ERROR"      => "SETTLEMENT_ERROR",
	];

	const API_REQUEST = 'api_request_';
	const ENDPOINT_RESPONSE = 'endpoint_response_';
	const ENDPOINT_ADD_SUBWALLET = 'endpoint_add_subwallet';
	const ENDPOINT_SUBTRACT_SUBWALLET = 'endpoint_subtract_subwallet';
	const SUCCESS = true;
	const ERROR = false;
	const FAIL = false;

	const GAME_LOGS = 'game';
	const TRANSACTION_LOGS = 'transaction';

	public function __construct() {
		parent::__construct();
		$this->current_transaction = '';
		$this->load->model(array('wallet_model','game_provider_auth','common_token','player_model','original_game_logs_model'));
		$this->gamePlatformId = '';
		$this->current_balance = 0;
		$this->responseData = '';
	}

	private function init($game_platform_id) {
		$this->game_api = $this->utils->loadExternalSystemLibObject($game_platform_id);
		$this->gamePlatformId = $game_platform_id;
		$this->transaction_game_logs_table = $this->game_api->original_transaction_table;
		$this->currency = $this->game_api->currency;
		$this->status_code = 200;
	}

	public function index($game_platform_id, $casino_integration = null, $s = null, $transaction = null, $method = null) {
		$this->init($game_platform_id);
		if ($this->external_system->isGameApiActive($game_platform_id)) {
			$postJson = $this->input->get();
			$api_call = $_SERVER['REQUEST_URI'];

			$postJson['url'] = $api_call;

			$this->utils->debug_log("ION GAMING SEAMLESS RAW REQUEST ====> ", $postJson);
			$this->game_api->saveToResponseResult(self::SUCCESS, self::API_REQUEST . self::RAW_REQUEST, $postJson, self::API_REQUEST . self::RAW_REQUEST);

			unset($postJson['url']);
			$postData = !empty($postJson) ? $postJson : array();

			if (!empty($method) && !empty($transaction) && ($transaction === 'bet' || $transaction === 'wallet')) {
				switch ($method) {
					case self::ACTION_BALANCE:
						$result = $this->GetAvailableCredit($postData);
						return $this->output->set_status_header($this->status_code)->set_content_type('application/json')->set_output(json_encode($result));
						break;
					case self::ACTION_DEDUCT_BALANCE:
						$result = $this->DeductBalance($postData);
						return $this->output->set_status_header($this->status_code)->set_content_type('application/json')->set_output(json_encode($result));
						break;
					case self::ACTION_ROLLBACK_BALANCE:
						$result = $this->RollbackBalance($postData);
						return $this->output->set_status_header($this->status_code)->set_content_type('application/json')->set_output(json_encode($result));
						break;
					case self::ACTION_INSERT:
						$result = $this->Insert($postData);
						return $this->output->set_status_header($this->status_code)->set_content_type('application/json')->set_output(json_encode($result));
						break;
					case self::ACTION_SETTLE:
						$result = $this->Settle($postData);
						return $this->output->set_status_header($this->status_code)->set_content_type('application/json')->set_output(json_encode($result));
						break;
					case self::ACTION_PAYOUT:
						$result = $this->processUserPlacePayout($postData);
						return $this->output->set_status_header($this->status_code)->set_content_type('application/json')->set_output(json_encode($result));
						break;
					
					default:
						$this->utils->debug_log("ION GAMING SEAMLESS ENDPOINT ERROR ====> ", $postData['service']);
						break;
				}
			}

		}

	}

	private function queryExistingTransaction($data, $method) {
        $sql = <<<EOD
            SELECT id, Stake
            FROM {$this->transaction_game_logs_table}
            WHERE transaction_type = ? AND Guid = ?
EOD;

        $params = [
            $method,
            $data['Guid']
        ];

		if ($method === self::ACTION_DEDUCT_BALANCE) {
	        $sql = <<<EOD
	            SELECT id, Stake
	            FROM {$this->transaction_game_logs_table}
	            WHERE transaction_type = ? AND SeqNo = ? AND Guid = ?
EOD;
	        $params = [
	            $method,
	            $data['SeqNo'],
	            $data['Guid']
	        ];
		}

    	$queryResult = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql,$params);
    	if (!empty($queryResult)) {
    		return ['exists' => true, 'data' => $queryResult];
    	}
		return ['exists' => false, 'data' => $queryResult];
	}

	private function GetAvailableCredit($request_data) {
		$this->utils->debug_log("ION GAMING SEAMLESS REQUEST GetUserBalance ====> ", $request_data);

		$player_id = $this->game_api->getPlayerIdInGameProviderAuth($request_data['AccountId']);

		$responseData = [
			'Result'    	  => self::RESPONSE_CODE['GENERAL_ERROR'],
			'AvailableCredit' => 0,
			'CurrencyCode'    => $this->currency
		];

		if (empty($player_id)) {
			$this->game_api->saveToResponseResult(self::ERROR, self::ACTION_BALANCE, $responseResultData, self::ACTION_BALANCE);
			return $responseData;
		}

        $success = $this->lockAndTransForPlayerBalance($player_id,function() use($request_data, $player_id) {
	        if($this->utils->getConfig('enable_seamless_single_wallet')) {
	            $seamless_balance = 0;
	            $seamless_reason_id = null;
	            $seamless_wallet = $this->wallet_model->querySeamlessSingleWallet($player_id, $seamless_balance, $seamless_reason_id);
	            if (!$seamless_wallet) {
	                return false;
	            } else {
	                $this->current_balance = $seamless_balance;
	                return true;
	            }
	        } else {
	        	$player_name = $this->game_api->getPlayerUsernameByGameUsername($request_data['AccountId']);
				$apiResult = $this->game_api->queryPlayerBalance($player_name);
				if ($apiResult['success']) {
					$this->current_balance = $apiResult['balance'];
					return true;
				}
				return false;
	        }
        });

        if ($success) {
			$responseData = [
				'code'    		  => self::RESPONSE_CODE['SUCCESS'],
				'AvailableCredit' => $this->current_balance,
				'CurrencyCode'    => $this->currency
			];
        }

		$responseResultData = ['Request' => $request_data, 'Response' => $responseData];

		$this->game_api->saveToResponseResult(self::SUCCESS, self::ACTION_BALANCE, $responseResultData, self::ACTION_BALANCE);
		return $responseData;
	}

	private function DeductBalance($request_data) {

		$this->utils->debug_log("ION GAMING SEAMLESS REQUEST DeductBalance ====> ", $request_data);

		$player_id = $this->game_api->getPlayerIdInGameProviderAuth($request_data['AccountId']);
		$OrderId = $this->game_api->getUniqueId();

		$this->responseData = [
			'Result'  => self::RESPONSE_CODE['GENERAL_ERROR'],
			'OrderId' => $OrderId
		];

		$request_data['OrderId'] = $OrderId;

		if (empty($player_id)) {
			$this->game_api->saveToResponseResult(self::ERROR, self::ACTION_DEDUCT_BALANCE . '_' . $request_data['Guid'], $responseResultData, self::ACTION_DEDUCT_BALANCE . '_' . $request_data['Guid']);
			return $this->responseData;
		}

        $success = $this->lockAndTransForPlayerBalance($player_id,function() use($request_data, $OrderId, $player_id) {
			$this->current_transaction = self::ACTION_DEDUCT_BALANCE;

			// Check if there's a Deduct Balance request
			$bExist = $this->queryExistingTransaction($request_data, self::ACTION_DEDUCT_BALANCE);

			if ($bExist['exists']) {
				$responseResultData = [
					'Message'  => 'DUPLICATE_TRANSACTION',
					'Request'  => 'DeductBalance',
					'Data' 	   => $request_data,
					'Response' => $this->responseData
				];

				$this->game_api->saveToResponseResult(self::ERROR, self::ACTION_DEDUCT_BALANCE . '_' . $request_data['Guid'], $responseResultData, self::ACTION_DEDUCT_BALANCE . '_' . $request_data['Guid']);
				return false;
			}

			$request_data['OrderId'] = $OrderId;

			$new_data = $this->rebuildTransaction($request_data, self::ACTION_DEDUCT_BALANCE);

			$success = $this->subtract_amount($player_id, abs($request_data['Stake']), $new_data);

	        if ($success) {
				$this->responseData = [
					'Result'  => self::RESPONSE_CODE['SUCCESS'],
					'OrderId' => $OrderId
				];
	        }

			$responseResultData = [
				'Message'  => 'SUCCESS',
				'Request'  => 'DeductBalance',
				'Data' 	   => $request_data,
				'Response' => $this->responseData
			];

			$this->game_api->saveToResponseResult(self::SUCCESS, self::ACTION_DEDUCT_BALANCE . '_' . $request_data['Guid'], $responseResultData, self::ACTION_DEDUCT_BALANCE . '_' . $request_data['Guid']);
			return true;
        });

        return $this->responseData;
	}

	private function RollbackBalance($request_data) {
		$this->utils->debug_log("ION GAMING SEAMLESS REQUEST RollbackBalance ====> ", $request_data);

		$player_id = $this->game_api->getPlayerIdInGameProviderAuth($request_data['AccountId']);

        $success = $this->lockAndTransForPlayerBalance($player_id,function() use($request_data, $player_id) {
        	$this->current_transaction = self::ACTION_ROLLBACK_BALANCE;

			// Check if there's a Rollback request already
			$bExist = $this->queryExistingTransaction($request_data, self::ACTION_ROLLBACK_BALANCE);

			// Check if there's a Deduct Balance request
			$bExistBet = $this->queryExistingTransaction($request_data, self::ACTION_DEDUCT_BALANCE);

			$this->responseData = [
				'Result'  => self::RESPONSE_CODE['GENERAL_ERROR'],
				'Amount'  => 0
			];

			if ($bExist['exists']) {
				$responseResultData = [
					'Message'  => 'DUPLICATE_TRANSACTION',
					'Request'  => 'RollbackBalance',
					'Data' 	   => $request_data,
					'Response' => $this->responseData
				];

				$this->game_api->saveToResponseResult(self::ERROR, self::ACTION_ROLLBACK_BALANCE . '_' . $request_data['Guid'], $responseResultData, self::ACTION_ROLLBACK_BALANCE . '_' . $request_data['Guid']);
				return false;
			}

			if (!$bExistBet['exists']) {
				$this->responseData = [
					'Result'  => self::RESPONSE_CODE['ORDER_NOT_FOUND'],
					'Amount'  => 0
				];

				$responseResultData = [
					'Message'  => 'ORDER_NOT_FOUND',
					'Request'  => 'RollbackBalance',
					'Data' 	   => $request_data,
					'Response' => $this->responseData
				];

				$this->game_api->saveToResponseResult(self::ERROR, self::ACTION_ROLLBACK_BALANCE . '_' . $request_data['Guid'], $responseResultData, self::ACTION_ROLLBACK_BALANCE . '_' . $request_data['Guid']);
				return false;
			}

			$new_data = $this->rebuildTransaction($request_data, self::ACTION_ROLLBACK_BALANCE);
			$success = $this->add_amount($player_id, abs($bExist['data']['Stake']), $new_data);

	        if ($success) {
				$this->responseData = [
					'Result'  => self::RESPONSE_CODE['SUCCESS'],
					'Amount'  => $bExist['data']['Stake']
				];
	        }

			$responseResultData = [
				'Message'  => 'SUCCESS',
				'Request'  => 'RollbackBalance',
				'Data' 	   => $request_data,
				'Response' => $this->responseData
			];

			$this->game_api->saveToResponseResult(self::SUCCESS, self::ACTION_ROLLBACK_BALANCE . '_' . $request_data['Guid'], $responseResultData, self::ACTION_ROLLBACK_BALANCE . '_' . $request_data['Guid']);
			return true;
        });
		return $this->responseData;
	}

	private function Insert($request_data) {
		$this->utils->debug_log("ION GAMING SEAMLESS REQUEST Insert ====> ", $request_data);
		$this->current_transaction = self::ACTION_INSERT;

		$player_id = $this->game_api->getPlayerIdInGameProviderAuth($request_data['AccountId']);

        $success = $this->lockAndTransForPlayerBalance($player_id,function() use($request_data, $player_id) {
        	$this->responseData = [
				'Result'  => self::RESPONSE_CODE['GENERAL_ERROR']
			];

			$new_data = $this->rebuildTransaction($request_data, self::ACTION_INSERT);

			$success = $this->subtract_amount($player_id, abs($request_data['Stake']), $new_data, false);

	        if ($success) {
				$this->responseData = [
					'Result'  => self::RESPONSE_CODE['SUCCESS']
				];
	        }

			$responseResultData = [
				'Message'  => 'SUCCESS',
				'Request'  => 'Insert',
				'Data' 	   => $request_data,
				'Response' => $this->responseData
			];

			$this->game_api->saveToResponseResult(self::SUCCESS, self::ACTION_INSERT . '_' . $request_data['Guid'], $responseResultData, self::ACTION_INSERT . '_' . $request_data['Guid']);
			return true;
        });
        return $this->responseData;
	}

	private function Settle($request_data) {
		$this->utils->debug_log("ION GAMING SEAMLESS REQUEST Settle ====> ", $request_data);
		$this->current_transaction = self::ACTION_SETTLE;

		$player_id = $this->game_api->getPlayerIdInGameProviderAuth($request_data['AccountId']);

    	$this->responseData = [
			'Result'  => self::RESPONSE_CODE['GENERAL_ERROR']
		];

		if (!$player_id) {
			$this->responseData = [
				'Result'  => self::RESPONSE_CODE['MEMBER_NOT_FOUND']
			];

			$responseResultData = [
				'Message'  => 'MEMBER_NOT_FOUND',
				'Request'  => 'Settle',
				'Data' 	   => $request_data,
				'Response' => $this->responseData
			];

			$this->game_api->saveToResponseResult(self::ERROR, self::SETTLE . '_' . $request_data['Guid'], $responseResultData, self::SETTLE . '_' . $request_data['Guid']);
			return false;
		}

        $success = $this->lockAndTransForPlayerBalance($player_id,function() use($request_data, $player_id) {
			// Check if there's a Settled request
			$bExist = $this->queryExistingTransaction($request_data, self::ACTION_SETTLE);

			if ($bExist['exists']) {
				$this->responseData = [
					'Result'  => self::RESPONSE_CODE['SETTLEMENT_ERROR']
				];

				$responseResultData = [
					'Message'  => 'SETTLEMENT_ERROR',
					'Request'  => 'RollbackBalance',
					'Data' 	   => $request_data,
					'Response' => $this->responseData
				];

				$this->game_api->saveToResponseResult(self::ERROR, self::ACTION_SETTLE . '_' . $request_data['Guid'], $responseResultData, self::ACTION_SETTLE . '_' . $request_data['Guid']);
				return false;
			}

			$new_data = $this->rebuildTransaction($request_data, self::ACTION_SETTLE);
			$success = $this->add_amount($player_id, abs($request_data['PlayerWinLoss']), $new_data);

	        if ($success) {
				$this->responseData = [
					'Result'  => self::RESPONSE_CODE['SUCCESS']
				];
	        }

			$responseResultData = [
				'Message'  => 'SUCCESS',
				'Request'  => 'Settle',
				'Data' 	   => $request_data,
				'Response' => $this->responseData
			];

			$this->game_api->saveToResponseResult(self::SUCCESS, self::ACTION_SETTLE . '_' . $request_data['Guid'], $responseResultData, self::ACTION_SETTLE . '_' . $request_data['Guid']);
			return true;
        });
		return $this->responseData;
	}

	private function rebuildTransaction($data, $method) {
		$new_data = [];

		// if ($method === self::ACTION_DEDUCT_BALANCE) {
		// 	$new_data = [
		// 		'Guid'   					=> isset($data['Guid']) ? $data['Guid'] : null,
		// 		'RefNo'    					=> isset($data['RefNo']) ? $data['RefNo'] : null,
		// 		'SeqNo' 		 			=> isset($data['SeqNo']) ? $data['SeqNo'] : null,
		// 		'OrderId'           		=> isset($data['OrderId']) ? $data['OrderId'] : null,
		// 		'AccountId' 	 			=> isset($data['AccountId']) ? $data['AccountId'] : null,
		// 		'Stake' 					=> isset($data['Stake']) ? $this->game_api->gameAmountToDB(floatval($data['Stake'])) : null,
		// 		'DeductBalance_Timestamp'   => isset($data['Timestamp']) ? $this->game_api->gameTimeToServerTime($data['Timestamp']) : null,
		// 		'external_uniqueid' 		=> isset($data['Guid']) ? $data['Guid'] : null,
		// 		'status' 		            => self::ACTION_DEDUCT_BALANCE,
		// 	];
		// } else if ($method === self::ACTION_INSERT) {
		// 	$new_data = [
		// 		'Guid'   					=> isset($data['Guid']) ? $data['Guid'] : null,
		// 		'RefNo'    					=> isset($data['RefNo']) ? $data['RefNo'] : null,
		// 		'SeqNo' 		 			=> isset($data['SeqNo']) ? $data['SeqNo'] : null,
		// 		'OrderId'           		=> isset($data['OrderId']) ? $data['OrderId'] : null,
		// 		'AccountId' 	 			=> isset($data['AccountId']) ? $data['AccountId'] : null,
		// 		'ProductType' 				=> isset($data['ProductType']) ? $data['ProductType'] : null,
		// 		'OrderTime' 				=> isset($data['OrderTime']) ? $this->game_api->gameTimeToServerTime($data['OrderTime']) : null,
		// 		'Stake' 					=> isset($data['Stake']) ? $this->game_api->gameAmountToDB(floatval($data['Stake'])) : null,
		// 		'GameId'   					=> isset($data['GameId']) ? $data['GameId'] : null,
		// 		'GameStartTime' 			=> isset($data['GameStartTime']) ? $this->game_api->gameTimeToServerTime($data['GameStartTime']) : null,
		// 		'TableName' 	 			=> isset($data['TableName']) ? $data['TableName'] : null,
		// 		'GroupBetOptions' 			=> isset($data['GroupBetOptions']) ? $data['GroupBetOptions'] : null,
		// 		'BetOptions' 				=> isset($data['BetOptions']) ? $data['BetOptions'] : null,
		// 		'Ip'   						=> isset($data['Ip']) ? $data['Ip'] : null,
		// 		'IsCommission'   			=> isset($data['IsCommission']) ? $data['IsCommission'] : null,	
		// 		'Insert_Timestamp'  		=> isset($data['Timestamp']) ? $this->game_api->gameTimeToServerTime($data['Timestamp']) : null,
		// 		'external_uniqueid' 		=> isset($data['Guid']) ? $data['Guid'] : null,
		// 		'status' 		            => self::ACTION_INSERT,
		// 	];
		// } else if ($method === self::ACTION_SETTLE) {
		// 	$new_data = [
		// 		'Guid'   					=> isset($data['Guid']) ? $data['Guid'] : null,
		// 		'RefNo'    					=> isset($data['RefNo']) ? $data['RefNo'] : null,
		// 		'AccountId' 	 			=> isset($data['AccountId']) ? $data['AccountId'] : null,
		// 		'WinningStake' 				=> isset($data['WinningStake']) ? $this->game_api->gameAmountToDB(floatval($data['WinningStake'])) : null,
		// 		'PlayerWinLoss' 			=> isset($data['PlayerWinLoss']) ? $this->game_api->gameAmountToDB(floatval($data['PlayerWinLoss'])) : null,
		// 		'SettlementStatus' 			=> isset($data['SettlementStatus']) ? $data['SettlementStatus'] : null,
		// 		'SettleTime' 				=> isset($data['SettleTime']) ? $this->game_api->gameTimeToServerTime($data['SettleTime']) : null,
		// 		'Stake' 					=> isset($data['Stake']) ? $this->game_api->gameAmountToDB(floatval($data['Stake'])) : null,
		// 		'GroupBetOptions' 			=> isset($data['GroupBetOptions']) ? $data['GroupBetOptions'] : null,
		// 		'BetOptions' 				=> isset($data['BetOptions']) ? $data['BetOptions'] : null,
		// 		'Settle_Timestamp'  		=> isset($data['Timestamp']) ? $this->game_api->gameTimeToServerTime($data['Timestamp']) : null,
		// 		'external_uniqueid' 		=> isset($data['Guid']) ? $data['Guid'] : null,
		// 		'status' 		            => self::ACTION_SETTLE,
		// 	];
		// } else if ($method === self::ACTION_ROLLBACK_BALANCE) {
		// 	$new_data = [
		// 		'Guid'   					=> isset($data['Guid']) ? $data['Guid'] : null,
		// 		'RefNo'    					=> isset($data['RefNo']) ? $data['RefNo'] : null,
		// 		'SeqNo' 		 			=> isset($data['SeqNo']) ? $data['SeqNo'] : null,
		// 		'OrderId'           		=> isset($data['OrderId']) ? $data['OrderId'] : null,
		// 		'AccountId' 	 			=> isset($data['AccountId']) ? $data['AccountId'] : null,
		// 		'RollbackBalance_Timestamp' => isset($data['Timestamp']) ? $this->game_api->gameTimeToServerTime($data['Timestamp']) : null,
		// 		'external_uniqueid' 		=> isset($data['Guid']) ? $data['Guid'] : null,
		// 		'status' 		            => self::ACTION_ROLLBACK_BALANCE,
		// 	];
		// }

		$new_data = [
			'RefNo'    					=> !empty($data['RefNo']) ? $data['RefNo'] : null,
			'SeqNo' 		 			=> !empty($data['SeqNo']) ? $data['SeqNo'] : 0,
			'OrderId'           		=> !empty($data['OrderId']) ? $data['OrderId'] : null,
			'AccountId' 	 			=> !empty($data['AccountId']) ? $data['AccountId'] : null,
			'ProductType' 				=> !empty($data['ProductType']) ? $data['ProductType'] : null,
			'OrderTime' 				=> !empty($data['OrderTime']) ? $this->game_api->gameTimeToServerTime(date('Y-m-d h:i:s', strtotime($data['OrderTime']))) : null,
			'Stake' 					=> !empty($data['Stake']) ? floatval($data['Stake']) : null,
			'WinningStake' 				=> !empty($data['WinningStake']) ? floatval($data['WinningStake']) : null,
			'PlayerWinLoss' 			=> !empty($data['PlayerWinLoss']) ? floatval($data['PlayerWinLoss']) : null,

			// 'Stake' 					=> !empty($data['Stake']) ? $this->game_api->gameAmountToDB(floatval($data['Stake'])) : null,
			// 'WinningStake' 				=> !empty($data['WinningStake']) ? $this->game_api->gameAmountToDB(floatval($data['WinningStake'])) : null,
			// 'PlayerWinLoss' 			=> !empty($data['PlayerWinLoss']) ? $this->game_api->gameAmountToDB(floatval($data['PlayerWinLoss'])) : null,

			'SettlementStatus' 			=> !empty($data['SettlementStatus']) ? $data['SettlementStatus'] : null,
			'GameId'   					=> !empty($data['GameId']) ? $data['GameId'] : null,
			'GameStartTime' 			=> !empty($data['GameStartTime']) ? $this->game_api->gameTimeToServerTime(date('Y-m-d h:i:s', strtotime($data['GameStartTime']))) : null,
			'SettleTime' 				=> !empty($data['SettleTime']) ? $this->game_api->gameTimeToServerTime(date('Y-m-d h:i:s', strtotime($data['SettleTime']))) : null,
			'TableName' 	 			=> !empty($data['TableName']) ? $data['TableName'] : null,
			'GroupBetOptions' 			=> !empty($data['GroupBetOptions']) ? $data['GroupBetOptions'] : null,
			'BetOptions' 				=> !empty($data['BetOptions']) ? $data['BetOptions'] : null,
			'Ip'   						=> !empty($data['Ip']) ? $data['Ip'] : null,
			'IsCommission'   			=> !empty($data['IsCommission']) ? $data['IsCommission'] : null,
			'Timestamp'   				=> !empty($data['Timestamp']) ? $this->game_api->gameTimeToServerTime(date('Y-m-d h:i:s', strtotime($data['Timestamp']))) : null,
			'Guid'   					=> !empty($data['Guid']) ? $data['Guid'] : null,
			'external_uniqueid' 		=> !empty($data['Guid']) ? $data['Guid'] : null,
			'transaction_type' 		    => $method,
			'extra' 		            => json_encode($data),
		];

		return $new_data;
	}

	private function subtract_amount($playerId, $amount, $transaction = null, $is_adjust_wallet = true) {
        // $success = $this->lockAndTransForPlayerBalance($playerId,function() use($playerId, $amount, $transaction, $is_adjust_wallet) {
			$no_db_amount = floatval($amount);

			$amount = floatval($amount);
			// $amount = $this->game_api->gameAmountToDB(floatval($amount));
			// 
        	$player_name = $this->game_api->getPlayerUsernameByGameUsername($transaction['AccountId']);
			$before_balance = $this->getCurrentBalance($transaction['AccountId']);

			$after_balance = floatval(abs($before_balance)) - $no_db_amount;

			$transaction['before_balance'] = floatval($before_balance);
			$transaction['after_balance'] = floatval($after_balance);

			// $transaction['before_balance'] = $this->game_api->gameAmountToDB(floatval($before_balance['balance']));
			// $transaction['after_balance'] = $this->game_api->gameAmountToDB(floatval($after_balance));

			$insert_succ = $this->game_api->doSyncOriginal(array($transaction), $this->transaction_game_logs_table, self::TRANSACTION_LOGS);

			if ($insert_succ['success']) {
				$succ = true;
				if ($amount > 0 && $is_adjust_wallet) {
					if ($this->utils->getConfig('enable_seamless_single_wallet')) {
						$reason_id = Abstract_game_api::REASON_UNKNOWN;
						$succ = $this->wallet_model->transferSeamlessSingleWallet($playerId, Wallet_model::TRANSFER_TYPE_OUT, $amount, $reason_id);
					} else {
						$succ = $this->wallet_model->decSubWallet($playerId, $this->gamePlatformId, $amount);
					}
				}
				if ($succ) {
					$this->game_api->saveToResponseResult(self::SUCCESS, self::ENDPOINT_SUBTRACT_SUBWALLET . '_' . $this->current_transaction . '_' . $transaction['Guid'],['player_id' => $playerId, 'amount' => $amount], self::ENDPOINT_SUBTRACT_SUBWALLET . '_' . $this->current_transaction . '_' . $transaction['Guid']);
					return true;
				}
				return false;
			} else {
				return false;
			}
        // });
        // return $success;
	}

	private function add_amount($playerId, $amount, $transaction = null, $is_adjust_wallet = true) {
        // $success=$this->lockAndTransForPlayerBalance($playerId,function() use($playerId, $amount, $transaction, $is_adjust_wallet) {

			$no_db_amount = floatval($amount);

			$amount = floatval($amount);
			// $amount = $this->game_api->gameAmountToDB(floatval($amount));

        	$player_name = $this->game_api->getPlayerUsernameByGameUsername($transaction['AccountId']);
			$before_balance = $this->getCurrentBalance($transaction['AccountId']);
			
			$after_balance = floatval(abs($before_balance)) + $no_db_amount;

			$transaction['before_balance'] = floatval($before_balance);
			$transaction['after_balance'] = floatval($after_balance);

			// $transaction['before_balance'] = $this->game_api->gameAmountToDB(floatval($before_balance['balance']));
			// $transaction['after_balance'] = $this->game_api->gameAmountToDB(floatval($after_balance));

			$insert_succ = $this->game_api->doSyncOriginal(array($transaction), $this->transaction_game_logs_table, self::TRANSACTION_LOGS);

			if ($insert_succ['success']) {
				$succ = true;
				if ($amount > 0 && $is_adjust_wallet) {
					if ($this->utils->getConfig('enable_seamless_single_wallet')) {
						$reason_id = Abstract_game_api::REASON_UNKNOWN;
						$succ = $this->wallet_model->transferSeamlessSingleWallet($playerId, Wallet_model::TRANSFER_TYPE_IN, $amount, $reason_id);
					} else {
						$succ = $this->wallet_model->incSubWallet($playerId, $this->gamePlatformId, $amount);
					}
				}
				if ($succ) {
					$this->game_api->saveToResponseResult(self::SUCCESS, self::ENDPOINT_ADD_SUBWALLET . '_' . $this->current_transaction . '_' . $transaction['Guid'],['player_id' => $playerId, 'amount' => $amount], self::ENDPOINT_ADD_SUBWALLET . '_' . $this->current_transaction . '_' . $transaction['Guid']);
					return true;
				}
				return false;
			} else {
				return false;
			}
        // });

		// return $success;
	}

	private function getCurrentBalance($gameUsername) {
		$player_id = $this->game_api->getPlayerIdInGameProviderAuth($gameUsername);
        if($this->utils->getConfig('enable_seamless_single_wallet')) {
            $seamless_balance = 0;
            $before_balance = 0;
            $seamless_reason_id = null;
            $seamless_wallet = $this->wallet_model->querySeamlessSingleWallet($player_id, $seamless_balance, $seamless_reason_id);
            if (!$seamless_wallet) {
                return 0;
            } else {
                return $seamless_balance;
            }
        } else {
        	$player_name = $this->game_api->getPlayerUsernameByGameUsername($gameUsername);
			$apiResult = $this->game_api->queryPlayerBalance($player_name);
			if ($apiResult['success']) {
                return $apiResult['balance'];
			}
			return 0;
        }
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
}