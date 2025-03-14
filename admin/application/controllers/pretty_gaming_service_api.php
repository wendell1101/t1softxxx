<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/BaseController.php';

class Pretty_gaming_service_api extends BaseController {

	const RAW_REQUEST = "RAW_REQUEST";
	const ACTION_BALANCE = "GetUserBalance";
	const ACTION_BET 	 = "UserPlaceBet";
	const ACTION_CANCEL  = "UserPlaceBetCancel";
	const ACTION_PAYOUT  = "UserPlacePayout";

    const RESPONSE_IP_ADDRESS_NOT_ALLOWED = [
        'CODE' => 61001,
        'DESCRIPTION' => 'IP address is not allowed.'
    ];

	const RESPONSE_CODE  = [
		"SUCCESS"                         => 0,
		"INSUFFICIENT_BALANCE"            => 911001,
		"ACCOUNT_SUSPENDED"               => 911002,
		"SESSION_EXPIRED"                 => 911004,
		"BET_FAILED"            		  => 911005,
		"GAME_UNDER_AINTENANCE" 		  => 911006,
		"PLAYER_NOT_ALLOWED_TO_PLAY_GAME" => 911007,
		"PLAYER_NOT_FOUND" 				  => 911008,
		"BAD_PARAMETERS"                  => 911009,
		"ACCOUNT_LOCKED" 				  => 911010,
		"DUPLICATE_TRANSACTION" 		  => 51101,
		"TRANSACTION_NOT_FOUND" 		  => 51102,
        "IP_ADDRESS_NOT_ALLOWED"          => self::RESPONSE_IP_ADDRESS_NOT_ALLOWED['CODE'],
	];

	const TRANSACTION_STATUS = [
		"PENDING"	 => 'Pending',
		"CANCELED" 	 => 'Canceled',
		"SUCCESSFUL" => 'SuccessfulPayment'
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

    private $transaction_for_fast_track = null;

	public function __construct() {
		parent::__construct();
		$this->current_transaction = '';
		$this->load->model(array('wallet_model','game_provider_auth','common_token','player_model','ruby_play_transactions'));
		$this->gamePlatformId = '';
		$this->current_balance = 0;
	}

	private function init($game_platform_id) {
		$this->game_api = $this->utils->loadExternalSystemLibObject($game_platform_id);
		$this->gamePlatformId = $game_platform_id;
		$this->original_game_logs_table = $this->game_api->original_gamelogs_table;
		$this->transaction_game_logs_table = $this->game_api->original_transaction_table;
		$this->status_code = 200;
	}

	public function index($game_platform_id) {
		$this->init($game_platform_id);
		if ($this->external_system->isGameApiActive($game_platform_id)) {

            if (!$this->game_api->validateWhiteIP()) {
                $this->status_code = 401;
    
                $response = [
                    'code' => self::RESPONSE_IP_ADDRESS_NOT_ALLOWED['CODE'],
                    'msg' => self::RESPONSE_IP_ADDRESS_NOT_ALLOWED['DESCRIPTION']
                ];
    
                $this->game_api->saveToResponseResult(self::ERROR, 'validateWhiteIP', [], $response, $this->status_code);
                return $this->output->set_status_header($this->status_code)->set_content_type('application/json')->set_output(json_encode($response));
            }

			$postJson = file_get_contents("php://input");

			$this->utils->debug_log("PRETTY GAMING SEAMLESS RAW REQUEST ====> ", $postJson);
			$this->game_api->saveToResponseResult(self::SUCCESS, self::API_REQUEST . self::RAW_REQUEST, $postJson, self::API_REQUEST . self::RAW_REQUEST);

			$postData = !empty($postJson) ? json_decode($postJson,true) : array();

			if (!empty($postData['service'])) {
				switch ($postData['service']) {
					case self::ACTION_BALANCE:
						$result = $this->processGetUserBalance($postData);
						return $this->output->set_status_header($this->status_code)->set_content_type('application/json')->set_output(json_encode($result));
						break;
					case self::ACTION_BET:
						$result = $this->processUserPlaceBet($postData);
                        // if($this->transaction_for_fast_track != null && $this->utils->getConfig('enable_fast_track_integration') && $result['code'] == self::RESPONSE_CODE['SUCCESS']) {
                        //     $this->sendToFastTrack();
                        // }
						return $this->output->set_status_header($this->status_code)->set_content_type('application/json')->set_output(json_encode($result));
						break;
					case self::ACTION_CANCEL:
						$result = $this->processUserPlaceBetCancel($postData);
                        // if($this->transaction_for_fast_track != null && $this->utils->getConfig('enable_fast_track_integration') && $result['code'] == self::RESPONSE_CODE['SUCCESS']) {
                        //     $this->sendToFastTrack();
                        // }
						return $this->output->set_status_header($this->status_code)->set_content_type('application/json')->set_output(json_encode($result));
						break;
					case self::ACTION_PAYOUT:
						$result = $this->processUserPlacePayout($postData);
                        if($this->transaction_for_fast_track != null && $this->utils->getConfig('enable_fast_track_integration') && $result['code'] == self::RESPONSE_CODE['SUCCESS']) {
                            $this->sendToFastTrack();
                        }
						return $this->output->set_status_header($this->status_code)->set_content_type('application/json')->set_output(json_encode($result));
						break;

					default:
						$this->utils->debug_log("PRETTY GAMING SEAMLESS ENDPOINT ERROR ====> ", $postData['service']);
						break;
				}
			}

		}

	}

	private function queryExistingTransaction($ticketId, $status = false) {
        $sql = <<<EOD
            SELECT id, ticketId, status
            FROM {$this->transaction_game_logs_table}
            WHERE ticketId = ?
EOD;
        $params = [
            $ticketId
        ];

		if ($status != false) {
			$sql .= ' AND status = ?';
			array_push($params, $status);
		}

    	$queryResult = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql,$params);
    	if (!empty($queryResult)) {
    		return ['exists' => true, 'data' => $queryResult];
    	}
		return ['exists' => false, 'data' => $queryResult];
	}

	private function queryExistingGameID($gameId, $status = false) {
        $sql = <<<EOD
            SELECT id, ticketId, status
            FROM {$this->transaction_game_logs_table}
            WHERE gameId = ?
EOD;
        $params = [
            $gameId
        ];

		if ($status != false) {
			$sql .= ' AND status = ?';
			array_push($params, $status);
		}

    	$queryResult = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql,$params);
    	if (!empty($queryResult)) {
    		return ['exists' => true, 'data' => $queryResult];
    	}
		return ['exists' => false, 'data' => $queryResult];
	}

	private function processGetUserBalance($request_data) {
		$data = $request_data['data'];
		$this->utils->debug_log("PRETTY GAMING SEAMLESS REQUEST GetUserBalance ====> ", $request_data);

		$player_id = $this->game_api->getPlayerIdInGameProviderAuth($data['playerUsername']);

        $success = $this->lockAndTransForPlayerBalance($player_id,function() use($data) {
			$apiResult = $this->game_api->queryPlayerBalanceByGameUsername($data['playerUsername']);
			if ($apiResult['success']) {
				$this->current_balance = $this->game_api->dBtoGameAmount($apiResult['balance']);
				return true;
			}
			return false;
        });

		$responseData = [
			'code'    => self::RESPONSE_CODE['BAD_PARAMETERS'],
			'balance' => 0
		];

        if ($success) {
			$responseData = [
				'code'    => self::RESPONSE_CODE['SUCCESS'],
				'balance' => $this->current_balance
			];
        }

		$responseResultData = ['Request' => $request_data, 'Response' => $responseData];

		$this->game_api->saveToResponseResult(self::SUCCESS, self::ACTION_BALANCE, $responseResultData, self::ACTION_BALANCE);
		return $responseData;
	}

	private function processUserPlaceBet($request_data) {
		$data = $request_data['data'];

		$new_data = $this->rebuildTransaction($data);
		$new_data['extra'] = json_encode($request_data);
		$new_data['api_request'] = $request_data['service'];

		$this->current_transaction = self::ACTION_BET;
		$this->utils->debug_log("PRETTY GAMING SEAMLESS REQUEST UserPlaceBet ====> ", $request_data);

		$responseData = [
			'code'    => self::RESPONSE_CODE['GAME_UNDER_AINTENANCE']
		];

		// Check if bet/pending already exist
		$bExist = $this->queryExistingTransaction($data['ticketId'], $data['status']);

		$apiBalance = $this->game_api->queryPlayerBalanceByGameUsername($data['playerUsername']);
		$apiBalance['balance'] = $this->game_api->dBtoGameAmount($apiBalance['balance']);
		$player_id = $this->game_api->getPlayerIdInGameProviderAuth($data['playerUsername']);
		if (floatval(abs($data['totalBetAmt'])) > $apiBalance['balance']) {
			$responseData['code'] = self::RESPONSE_CODE['INSUFFICIENT_BALANCE'];
			$responseResultData = [
				'Message'  => 'INSUFFICIENT_BALANCE',
				'Request'  => $request_data,
				'Data' 	   => [
					'Balance'    => $apiBalance['balance'],
					'Bet_Amount' => $data['totalBetAmt']
				],
				'Response' => $responseData
			];

			$this->game_api->saveToResponseResult(self::ERROR, self::ACTION_BET . '_' . $new_data['ticketId'], $responseResultData, self::ACTION_BET . '_' . $new_data['ticketId']);
			return $responseData;
		}

		if ($bExist['exists']) {
			$responseData['code'] = self::RESPONSE_CODE['DUPLICATE_TRANSACTION'];
			$responseResultData = [
				'Message'  => 'DUPLICATE_TRANSACTION',
				'Request'  => $request_data,
				'Data' 	   => [
					'ticketId' => $data['ticketId'],
					'status'   => $data['status']

				],
				'Response' => $responseData
			];

			$this->game_api->saveToResponseResult(self::ERROR, self::ACTION_BET . '_' . $new_data['ticketId'], $responseResultData, self::ACTION_BET . '_' . $new_data['ticketId']);
			return $responseData;
		}

		if ($this->game_api->do_payout_on_bet) {
			$success = $this->add_amount($player_id, $data['totalPayOutAmt'], $new_data);
			if (!$success) {
				$responseData['code'] = self::RESPONSE_CODE['BET_FAILED'];
				$responseResultData = [
					'Message'  => 'INTERNAL_ERROR(ADD AMOUNT LOCKED ERROR)',
					'Request'  => $request_data,
					'Data' 	   => [
						'ticketId' => $data['ticketId'],
						'status'   => $data['status']
					],
					'Response' => $responseData
				];

				$this->game_api->saveToResponseResult(self::ERROR, self::ACTION_BET . '_' . $new_data['ticketId'], $responseResultData, self::ACTION_BET . '_' . $new_data['ticketId']);
				return $responseData;
			}
		}

		$success = $this->subtract_amount($player_id, abs($data['totalBetAmt']), $new_data);
		if (!$success) {
			$responseData['code'] = self::RESPONSE_CODE['BET_FAILED'];
			$responseResultData = [
				'Message'  => 'INTERNAL_ERROR(SUBTRACT AMOUNT LOCKED ERROR)',
				'Request'  => $request_data,
				'Data' 	   => [
					'ticketId' => $data['ticketId'],
					'status'   => $data['status']
				],
				'Response' => $responseData
			];

			$this->game_api->saveToResponseResult(self::ERROR, self::ACTION_BET . '_' . $new_data['ticketId'], $responseResultData, self::ACTION_BET . '_' . $new_data['ticketId']);
			return $responseData;
		}

		$responseData['code'] = self::RESPONSE_CODE['SUCCESS'];

		$responseResultData = [
			'Message'  => 'SUCCESS',
			'Request'  => $request_data,
			'Response' => $responseData
		];

		$this->game_api->saveToResponseResult(self::SUCCESS, self::ACTION_BET . '_' . $new_data['ticketId'], $responseResultData, self::ACTION_BET . '_' . $new_data['ticketId']);
		return $responseData;
	}

	private function processUserPlacePayout($request_data) {
		$data = $request_data['data'];

		$new_data = $this->rebuildTransaction($data);
		$new_data['extra'] = json_encode($request_data);
		$new_data['api_request'] = $request_data['service'];

		$this->current_transaction = self::ACTION_PAYOUT;
		$this->utils->debug_log("PRETTY GAMING SEAMLESS REQUEST UserPlacePayout ====> ", $request_data);

		$responseData = [
			'code'    => self::RESPONSE_CODE['GAME_UNDER_AINTENANCE']
		];

		// Check if payout already exists
		$bExist = $this->queryExistingTransaction($data['ticketId'], $data['status']);

		// Check if there's a bet/pending transaction for this payout
		$bExistBet = $this->queryExistingTransaction($data['ticketId'], self::TRANSACTION_STATUS['PENDING']);
		$player_id = $this->game_api->getPlayerIdInGameProviderAuth($data['playerUsername']);

		if ($bExist['exists']) {
			$responseData['code'] = self::RESPONSE_CODE['DUPLICATE_TRANSACTION'];
			$responseResultData = [
				'Message'  => 'DUPLICATE_TRANSACTION',
				'Request'  => $request_data,
				'Data' 	   => [
					'ticketId' => $data['ticketId'],
					'status'   => $data['status']
				],
				'Response' => $responseData
			];

			$this->game_api->saveToResponseResult(self::ERROR, self::ACTION_PAYOUT . '_' . $new_data['ticketId'], $responseResultData, self::ACTION_PAYOUT . '_' . $new_data['ticketId']);
			return $responseData;
		}

		if (!$bExistBet['exists']) {
			$responseData['code'] = self::RESPONSE_CODE['TRANSACTION_NOT_FOUND'];
			$responseResultData = [
				'Message'  => 'TRANSACTION_NOT_FOUND',
				'Request'  => $request_data,
				'Data' 	   => [
					'ticketId' => $data['ticketId'],
					'status'   => $data['status']
				],
				'Response' => $responseData
			];

			$this->game_api->saveToResponseResult(self::ERROR, self::ACTION_PAYOUT . '_' . $new_data['ticketId'], $responseResultData, self::ACTION_PAYOUT . '_' . $new_data['ticketId']);
			return $responseData;
		}

		$success = $this->add_amount($player_id, $data['totalPayOutAmt'], $new_data);
		if (!$success) {
			$responseData['code'] = self::RESPONSE_CODE['BET_FAILED'];
			$responseResultData = [
				'Message'  => 'INTERNAL_ERROR(ADD AMOUNT LOCKED ERROR)',
				'Request'  => $request_data,
				'Data' 	   => [
					'ticketId' => $data['ticketId'],
					'status'   => $data['status']
				],
				'Response' => $responseData
			];

			$this->game_api->saveToResponseResult(self::ERROR, self::ACTION_PAYOUT . '_' . $new_data['ticketId'], $responseResultData, self::ACTION_PAYOUT . '_' . $new_data['ticketId']);
			return $responseData;
		}

		$responseData['code'] = self::RESPONSE_CODE['SUCCESS'];

		$responseResultData = [
			'Message'  => 'SUCCESS',
			'Request'  => $request_data,
			'Response' => $responseData
		];

		$this->game_api->saveToResponseResult(self::SUCCESS, self::ACTION_PAYOUT . '_' . $new_data['ticketId'], $responseResultData, self::ACTION_PAYOUT . '_' . $new_data['ticketId']);
		return $responseData;
	}



	private function processUserPlaceBetCancel($request_data) {
		$data = $request_data['data'];

		$new_data = $this->rebuildTransaction($data);
		$new_data['extra'] = json_encode($request_data);
		$new_data['api_request'] = $request_data['service'];

		$this->current_transaction = self::ACTION_CANCEL;
		$this->utils->debug_log("PRETTY GAMING SEAMLESS REQUEST UserPlaceBetCancel ====> ", $request_data);

		$responseData = [
			'code'    => self::RESPONSE_CODE['GAME_UNDER_AINTENANCE']
		];

		// Check if refund/cancel already exists
		$bExist = $this->queryExistingTransaction($data['ticketId'], $data['status']);

		// Check if there's a bet/pending request for this refund/cancel
		$bExistBet = $this->queryExistingTransaction($data['ticketId'], self::TRANSACTION_STATUS['PENDING']);

		$apiBalance = $this->game_api->queryPlayerBalanceByGameUsername($data['playerUsername']);
		$apiBalance["balance"] = $this->game_api->dBtoGameAmount($apiBalance["balance"]); // it seems $apiBalance is not using on this method.
		$player_id = $this->game_api->getPlayerIdInGameProviderAuth($data['playerUsername']);

		if ($bExist['exists']) {
			$responseData['code'] = self::RESPONSE_CODE['DUPLICATE_TRANSACTION'];
			$responseResultData = [
				'Message'  => 'DUPLICATE_TRANSACTION',
				'Request'  => $request_data,
				'Data' 	   => [
					'ticketId' => $data['ticketId'],
					'status'   => $data['status']

				],
				'Response' => $responseData
			];

			$this->game_api->saveToResponseResult(self::ERROR, self::ACTION_CANCEL . '_' . $new_data['ticketId'], $responseResultData, self::ACTION_CANCEL . '_' . $new_data['ticketId']);
			return $responseData;
		}

		if (!$bExistBet['exists']) {
			$responseData['code'] = self::RESPONSE_CODE['TRANSACTION_NOT_FOUND'];
			$responseResultData = [
				'Message'  => 'TRANSACTION_NOT_FOUND',
				'Request'  => $request_data,
				'Data' 	   => [
					'ticketId' => $data['ticketId'],
					'status'   => $data['status']

				],
				'Response' => $responseData
			];

			$this->game_api->saveToResponseResult(self::ERROR, self::ACTION_CANCEL . '_' . $new_data['ticketId'], $responseResultData, self::ACTION_CANCEL . '_' . $new_data['ticketId']);
			return $responseData;
		}

		$success = $this->add_amount($player_id, abs($data['totalBetAmt']), $new_data);
		if (!$success) {
			$responseData['code'] = self::RESPONSE_CODE['BET_FAILED'];
			$responseResultData = [
				'Message'  => 'INTERNAL_ERROR(SUBTRACT AMOUNT LOCKED ERROR)',
				'Request'  => $request_data,
				'Data' 	   => [
					'ticketId' => $data['ticketId'],
					'status'   => $data['status']
				],
				'Response' => $responseData
			];

			$this->game_api->saveToResponseResult(self::ERROR, self::ACTION_CANCEL . '_' . $new_data['ticketId'], $responseResultData, self::ACTION_CANCEL . '_' . $new_data['ticketId']);
			return $responseData;
		}


		$responseData['code'] = self::RESPONSE_CODE['SUCCESS'];

		$responseResultData = [
			'Message'  => 'SUCCESS',
			'Request'  => $request_data,
			'Response' => $responseData
		];
		$this->game_api->saveToResponseResult(self::SUCCESS, self::ACTION_CANCEL . '_' . $new_data['ticketId'], $responseResultData, self::ACTION_CANCEL . '_' . $new_data['ticketId']);
		return $responseData;
	}


	private function rebuildTransaction($data) {
		return [
			'playerUsername'    => isset($data['playerUsername']) ? $data['playerUsername'] : null,
			'ticketId' 		 	=> isset($data['ticketId']) ? $data['ticketId'] : null,
			'type'              => isset($data['type']) ? $data['type'] : null,
			'currency' 	 		=> isset($data['currency']) ? $data['currency'] : null,
			'gameId' 			=> isset($data['gameId']) ? $data['gameId'] : null,
			'totalBetAmt' 		=> isset($data['totalBetAmt']) ? $this->game_api->gameAmountToDB(floatval($data['totalBetAmt'])) : null,
			'totalPayOutAmt' 	=> isset($data['totalPayOutAmt']) ? $this->game_api->gameAmountToDB(floatval($data['totalPayOutAmt'])) : null,
			'winLoseTurnOver'   => isset($data['winLoseTurnOver']) ? $this->game_api->gameAmountToDB(floatval($data['winLoseTurnOver'])) : null,
			'txtList' 		 	=> isset($data['txtList']) ? json_encode($data['txtList']) : null,
			'status' 	 		=> isset($data['status']) ? $data['status'] : null,
			'result' 	 		=> isset($data['result']) ? json_encode($data['result']) : null,
			'createDate' 		=> isset($data['createDate']) ? $this->game_api->gameTimeToServerTime($data['createDate']) : null,
			'requestDate'   	=> isset($data['requestDate']) ? $this->game_api->gameTimeToServerTime($data['requestDate']) : null,
			'external_uniqueid' => isset($data['ticketId']) ? $data['ticketId'] . '-' . $data['status'] : null,
		];
	}

	private function subtract_amount($playerId, $amount, $transaction = null) {
        $success=$this->lockAndTransForPlayerBalance($playerId,function() use($playerId, $amount, $transaction) {

			$amount = $this->game_api->gameAmountToDB(floatval($amount));
			$before_balance = $this->game_api->queryPlayerBalanceByGameUsername($transaction['playerUsername']);
			$before_balance['balance'] = $this->game_api->dBtoGameAmount($before_balance["balance"]);
			$after_balance = floatval(abs($before_balance['balance'])) - $amount;

			$transaction['before_balance'] = $this->game_api->gameAmountToDB(floatval($before_balance['balance']));
			$transaction['after_balance'] = $this->game_api->gameAmountToDB(floatval($after_balance));
			$insert_succ = $this->game_api->doSyncOriginal(array($transaction), $this->transaction_game_logs_table, self::TRANSACTION_LOGS);

			if ($insert_succ['success']) {
                if($this->utils->getConfig('enable_fast_track_integration')) {
                    $this->CI->load->model(['common_seamless_wallet_transactions']);
                    $this->transaction_for_fast_track = null;
                    $this->transaction_for_fast_track = $transaction;
                    $this->transaction_for_fast_track['id'] = $this->CI->common_seamless_wallet_transactions->getLastInsertedId();
                }
				$succ = true;
				if ($amount > 0) {
					$succ = $this->wallet_model->decSubWallet($playerId, $this->gamePlatformId, $amount);
				}
				if ($succ) {
			$this->game_api->saveToResponseResult(self::SUCCESS, self::ENDPOINT_SUBTRACT_SUBWALLET . '_' . $this->current_transaction . '_' . $transaction['ticketId'],['player_id' => $playerId, 'amount' => $amount], self::ENDPOINT_SUBTRACT_SUBWALLET . '_' . $this->current_transaction . '_' . $transaction['ticketId']);
					return true;
				}
				return false;
			} else {
				return false;
			}
        });
		$this->game_api->saveToResponseResult(self::SUCCESS, self::ENDPOINT_SUBTRACT_SUBWALLET . '_' . $this->current_transaction . '_' . $transaction['ticketId'],
			['player_id' => $playerId, 'amount' => $amount], self::ENDPOINT_SUBTRACT_SUBWALLET . '_' . $this->current_transaction . '_' . $transaction['ticketId']);
        return $success;
	}

	private function add_amount($playerId, $amount, $transaction = null) {
        $success=$this->lockAndTransForPlayerBalance($playerId,function() use($playerId, $amount, $transaction) {

			$amount = $this->game_api->gameAmountToDB(floatval($amount));
			$before_balance = $this->game_api->queryPlayerBalanceByGameUsername($transaction['playerUsername']);
			$before_balance['balance'] = $this->game_api->dBtoGameAmount($before_balance["balance"]);
			$after_balance = floatval(abs($before_balance['balance'])) + $amount;

			$transaction['before_balance'] = $this->game_api->gameAmountToDB(floatval($before_balance['balance']));
			$transaction['after_balance'] = $this->game_api->gameAmountToDB(floatval($after_balance));

			$insert_succ = $this->game_api->doSyncOriginal(array($transaction), $this->transaction_game_logs_table, self::TRANSACTION_LOGS);

			if ($insert_succ['success']) {
                if($this->utils->getConfig('enable_fast_track_integration')) {
                    $this->CI->load->model(['common_seamless_wallet_transactions']);
                    $this->transaction_for_fast_track = null;
                    $this->transaction_for_fast_track = $transaction;
                    $this->transaction_for_fast_track['id'] = $this->CI->common_seamless_wallet_transactions->getLastInsertedId();
                }
				$succ = true;
				if ($amount > 0) {
					$succ = $this->wallet_model->incSubWallet($playerId, $this->gamePlatformId, $amount);
				}
				if ($succ) {
					$this->game_api->saveToResponseResult(self::SUCCESS, self::ENDPOINT_ADD_SUBWALLET . '_' . $this->current_transaction . '_' . $transaction['ticketId'],['player_id' => $playerId, 'amount' => $amount], self::ENDPOINT_ADD_SUBWALLET . '_' . $this->current_transaction . '_' . $transaction['ticketId']);
					return true;
				}
				return false;
			} else {
				return false;
			}
        });

		return $success;
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

    private function sendToFastTrack() {
        $this->CI->load->model(['game_description_model']);
        $game_description = $this->game_description_model->getGameDetailsByExternalGameIdAndGamePlatform($this->api->getPlatformCode(), $this->transaction_for_fast_track['type']);
        $betType = null;
        switch($this->transaction_for_fast_track['trans_type']) {
            case 'UserPlaceBet':
                $betType = 'Bet';
                break;
            case 'UserPlacePayout':
                $betType = 'Win';
                break;
            default:
                $betType = null;
                break;
        }

        if ($betType == null) {
            return;
        }

        $data = [
            "activity_id" =>  strval($this->transaction_for_fast_track['id']),
            "amount" => $betType == 'Win' ? (float) abs($this->transaction_for_fast_track['totalPayOutAmt']) : (float) abs($this->transaction_for_fast_track['totalBetAmt']),
            "balance_after" =>  $this->transaction_for_fast_track['after_balance'],
            "balance_before" =>  $this->transaction_for_fast_track['before_balance'],
            "bonus_wager_amount" =>  0.00,
            "currency" =>  $this->api->currency,
            "exchange_rate" =>  1,
            "game_id" => $game_description->game_description_id,
            "game_name" => $this->utils->extractLangJson($game_description->game_name)['en'],
            "game_type" => $this->utils->extractLangJson($game_description->game_type)['en'],
            "is_round_end" =>  $betType == 'Win' ? true : false,
            "locked_wager_amount" =>  0.00,
            "origin" =>  $_SERVER['HTTP_HOST'],
            "round_id" =>  $this->transaction_for_fast_track['ticketId'],
            "timestamp" =>  str_replace('+00:00', 'Z', gmdate('c', strtotime('now'))),
            "type" =>  $betType,
            "user_id" =>  $this->game_api->getPlayerIdInGameProviderAuth($this->transaction_for_fast_track['playerUsername']),
            "vendor_id" =>  strval($this->api->getPlatformCode()),
            "vendor_name" =>  $this->external_system->getSystemName($this->api->getPlatformCode()),
            "wager_amount" => $betType == 'Bet' ? (float) abs($this->$game_description->game_description_id['totalPayOutAmt']) : 0,
        ];

        $this->load->library('fast_track');
        $this->fast_track->addToQueue('sendGameLogs', $data);
    }
}