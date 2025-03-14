<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/BaseController.php';
/*
 Aggragator main controller is qt_seamless_service_api.php a copy of this controller
*/

class qt_hacksaw_seamless_service_api extends BaseController {

	protected $gamePlatformId;
	protected $currencyCode;
	private $api;
	private $requestParams;
	private $player_id;
    private $currentPlayer;
    private $gameUsername;
    private $headers;

    const WHITELIST_METHOD_FOR_EXPIRED_TOKEN = [
        'qt_hacksaw_seamless_service_api::balance',
		'qt_hacksaw_seamless_service_api::credit',
		'qt_hacksaw_seamless_service_api::rollback',
    ];

	const WHITELIST_METHOD_POSSIBLE_NO_WALLET_SESSION = [
		'qt_hacksaw_seamless_service_api::rewards',
		'qt_hacksaw_seamless_service_api::balance',
	];

	const RETURN_OK = [
		'status' => 200,
		'code' => '',
		'message' => 'Transaction is successful'
	];

	const ERROR_INVALID_TOKEN = [
		'status' => 400,
        'code'  => 'INVALID_TOKEN',
		'message' => 'Missing, invalid or expired player (wallet) session token'
	];

	const ERROR_PASS_KEY_INCORRECT = [
		'status' => 401,
        'code'  => 'LOGIN_FAILED',
		'message' => 'The given pass-key is incorrect.'
	];

	const ERROR_UNKNOWN = [
		'status' => 500,
        'code'  => 'UNKNOWN_ERROR',
		'message' => 'Unknown Error.'
	];

	const ERROR_ACCOUNT_BLOCKED = [
		'status' => 403,
        'code'  => 'ACCOUNT_BLOCKED',
		'message' => 'The player account is blocked.'
	];

	const ERROR_REQUEST_DECLINED = [
		'status' => 400,
        'code'  => 'REQUEST_DECLINED',
		'message' => 'General error. If request could not be processed.'
	];

	const ERROR_INSUFFICIENT_FUNDS = [
		'status' => 400,
        'code'  => 'INSUFFICIENT_FUNDS',
		'message' => "If the requested DEBIT amount is higher than the player's balance within the Operator system"
	];

	const ERROR_LIMIT_EXCEEDED = [
		'status' => 400,
        'code'  => 'LIMIT_EXCEEDED',
		'message' => "The game limit for the player has been exceeded. No bets allowed."
	];

    const ERROR_IP_NOT_ALLOWED = [
		'status' => 401,
        'code'  => 'IP_NOT_ALLOWED',
		'message' => "IP Address is not allowed."
	];

	const RETURN_OK_ALREADY_EXIST = [
		'error' => 0,
		'message' => 'Transaction already exist.'
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

    const ERROR_GAME_MAINTENANCE = [
		'status' => 500,
        'code'  => 'UNKNOWN_ERROR',
		'message' => 'Game is under maintenance or disabled.'
	];

	const STATUS_SUCCESS = 200;

    const UPDATE_INSERT_MESSAGE = 'no changes, data is already inserted';
	const DEBIT = 'debit';
	const CREDIT = 'credit';
	const ROLLBACK = 'rollback';
	const BONUS_REWARDS = 'bonus-rewards';

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
		$this->gamePlatformId = QT_HACKSAW_SEAMLESS_API;
        $this->processRequest();

        $this->game_api = $this->utils->loadExternalSystemLibObject(QT_HACKSAW_SEAMLESS_API);

        $this->shared_pass_key = $this->game_api->getSystemInfo('shared_pass_key');
	}

    private function validateHeaders($functionName) {

		$this->headers = $headers = getallheaders();

        if (!$this->game_api->validateWhiteIP()) {
            $this->transactionType = 'validateWhiteIP';
            return $this->setResponse(self::ERROR_IP_NOT_ALLOWED['status'],self::ERROR_IP_NOT_ALLOWED['message'], array('code' => self::ERROR_IP_NOT_ALLOWED['code']));
        }

		if ($headers) {
			$wallet_session = (isset($headers['Wallet-Session'])) ? $headers['Wallet-Session'] : "";
			$pass_key = (isset($headers['Pass-Key'])) ? $headers['Pass-Key'] : "";


            if(empty($wallet_session) && !in_array($functionName, self::WHITELIST_METHOD_POSSIBLE_NO_WALLET_SESSION)) {
                return $this->setResponse(self::ERROR_INVALID_TOKEN['status'],self::ERROR_INVALID_TOKEN['message'], array('code' => self::ERROR_INVALID_TOKEN['code']));
            }

            if(empty($pass_key) || $pass_key != $this->shared_pass_key) {
                return $this->setResponse(self::ERROR_PASS_KEY_INCORRECT['status'],self::ERROR_PASS_KEY_INCORRECT['message'], array('code' => self::ERROR_PASS_KEY_INCORRECT['code']));
            }

			$playerId = $this->game_api->getPlayerIdByGameUsername($this->gameUsername);

			if(empty($playerId)) {
				return $this->setResponse(self::ERROR_ACCOUNT_BLOCKED['status'],"Player cannot be found", array('code' => self::ERROR_ACCOUNT_BLOCKED['code']));
			}

            if(!empty($wallet_session)) {
                $this->currentPlayer = (array) $this->common_token->getPlayerCompleteDetailsByToken($wallet_session, $this->game_api->getPlatformCode(), true, 10, 30);
				if(empty($this->currentPlayer) && !in_array($functionName, self::WHITELIST_METHOD_FOR_EXPIRED_TOKEN)) {
                    $this->utils->debug_log('QT HACKSAW ' . __METHOD__ , 'expired token');
                    return $this->setResponse(self::ERROR_INVALID_TOKEN['status'],self::ERROR_INVALID_TOKEN['message'], array('code' => self::ERROR_INVALID_TOKEN['code']));
                }
                else if(!empty($this->currentPlayer)) {

                    if(isset($this->requestParams->params->playerId) && $this->currentPlayer['game_username'] != $this->gameUsername) {
                        $this->utils->debug_log('QT HACKSAW ' . __METHOD__ , 'expired token');
                        return $this->setResponse(self::ERROR_INVALID_TOKEN['status'],self::ERROR_INVALID_TOKEN['message'], array('code' => self::ERROR_INVALID_TOKEN['code']));
                    }
                    $this->currentPlayer['playerId'] = $this->currentPlayer['player_id'];
                }
                else {
                    $user_name = $this->game_api->getPlayerUsernameByGameUsername($wallet_session);
                    $this->currentPlayer = (array) $this->game_api->getPlayerInfoByUsername($user_name);
                    if(empty($this->currentPlayer) && !in_array($functionName, self::WHITELIST_METHOD_FOR_EXPIRED_TOKEN)) {
                        $this->utils->debug_log('QT HACKSAW ' . __METHOD__ , 'expired token');
                        return $this->setResponse(self::ERROR_INVALID_TOKEN['status'],self::ERROR_INVALID_TOKEN['message'], array('code' => self::ERROR_INVALID_TOKEN['code']));
                    }
                    $this->utils->debug_log('QT HACKSAW ' . __METHOD__ , 'player found but expired token', $functionName);
                }
            }
		}
	}

    public function accounts($gameUsername, $type) {

        $this->gameUsername = $gameUsername;


        $method = $type;

        if(!method_exists($this, $method)) {
            $this->utils->debug_log('QT HACKSAW ' . __METHOD__ , $method , 'method not exists');
            return $this->setResponse(self::ERROR_UNKNOWN['status'],self::ERROR_UNKNOWN['message'], array('code' => self::ERROR_UNKNOWN['code']));
        }

        $response = $this->$method();

		return $response;


    }

	public function transactions($type = null) {

		if(empty($type)) {

			$type = strtolower($this->requestParams->params->txnType);

		}

		$this->gameUsername = isset($this->requestParams->params->playerId) ?  $this->requestParams->params->playerId : null;

		$method = $type;

        if(!method_exists($this, $method)) {
            $this->utils->debug_log('QT HACKSAW ' . __METHOD__ , $method , 'method not exists');
            return $this->setResponse(self::ERROR_UNKNOWN['status'],self::ERROR_UNKNOWN['message'], array('code' => self::ERROR_UNKNOWN['code']));
        }

        $response = $this->$method();

		return $response;

	}

	private function debit() {

		if (!$this->external_system->isGameApiActive($this->gamePlatformId) || $this->external_system->isGameApiMaintenance($this->gamePlatformId)) {
            return $this->setResponse(self::ERROR_GAME_MAINTENANCE['status'], self::ERROR_GAME_MAINTENANCE['message'], array('code' => self::ERROR_GAME_MAINTENANCE['code']));
        }

		$this->validateHeaders(__METHOD__);

		$params = $this->requestParams->params;

		$rules = [
			"txnType" => "required",
			"txnId" => "required",
			"playerId" => "required",
			"roundId" => "required",
			"amount" => "required",
			"currency" => "required",
			'gameId' => "required",
			"created" => "required",
			"completed" => 'required'
		];

		if(!$this->isValidParams((array)$params , $rules )){
			return $this->setResponse(self::ERROR_REQUEST_DECLINED['status'],self::ERROR_REQUEST_DECLINED['message'], array('code' => self::ERROR_REQUEST_DECLINED['code']));
		}

        $this->CI->utils->debug_log('QT HACKSAW Request Parameters', $params);

        $gameUsername = $params->playerId;


		$playerId = $this->game_api->getPlayerIdByGameUsername($gameUsername);

		if(!empty($playerId)) {

			$this->player_id = $playerId;

			$player_info['username'] = $this->player_model->getUsernameById($playerId);
			$player_info['playerId'] = $playerId;

			$response_code = array(
				"statusCode"	=> self::RETURN_OK["code"],
				"message"		=> self::RETURN_OK["message"],
				"status" 		=> self::RETURN_OK['status']
			);

			$self = $this;

			$trans_success = $this->lockAndTransForPlayerBalance($playerId, function() use($self,$playerId,$player_info,&$response_code, $params) {
				$player_balance = $self->game_api->queryPlayerBalance($player_info['username'])['balance'];
				$player_balance = $self->game_api->dBtoGameAmount($player_balance);
				$transaction_id = $params->txnId;
				$round_id = $params->roundId;

				$new_trans_id = $transaction_id . "-" . $round_id;
				$platform_id = $self->game_api->getPlatformCode();
				$existingRow = $self->common_seamless_wallet_transactions->getTransactionObjectByField($platform_id,$transaction_id,"transaction_id",SELF::DEBIT);

				if(!empty($existingRow)){
					$response_code['referenceId'] = $existingRow->external_unique_id;
					$response_code['after_balance'] = $player_balance;
					return true;

				}

				$betAmount = isset($params->amount) ? $params->amount : null;

				if (($player_balance - $betAmount) < 0) {
					$response_code['statusCode'] 	= self::ERROR_INSUFFICIENT_FUNDS["code"];
					$response_code['status'] 		= self::ERROR_INSUFFICIENT_FUNDS["status"];
					$response_code['message'] 		= self::ERROR_INSUFFICIENT_FUNDS["message"];
					return false;
				}

				$response_code = $self->adjustWallet(self::DEBIT, $player_info, $params);

				if($response_code['status'] != self::STATUS_SUCCESS) {
					return false;
				}

				if($response_code['status'] != self::STATUS_SUCCESS && $response_code['message'] == self::UPDATE_INSERT_MESSAGE) {
					return false;
				}

				return true;
			});


			if($trans_success) {


				if(!array_key_exists('after_balance', $response_code)) {
					$balance = $this->game_api->queryPlayerBalance($player_info['username'])['balance'];
					$balance = $this->game_api->dBtoGameAmount($balance);
				} else {
					$balance = $response_code["after_balance"];
				}

				$response_data = array(
					"balance" => $balance,
				);

				if(isset($response_code['referenceId']) && !empty($response_code['referenceId'])) {
					$response_data['referenceId'] = $response_code['referenceId'];
				}

				return $this->setResponse($response_code['status'],$response_code['message'], $response_data);


			} else {

				if($response_code["status"] == self::STATUS_SUCCESS) { // this scenario is for if player is locked and as you can see the default value for response code is success

					// it should not be success if failed. lockAndTransForPlayerBalance
					return $this->setResponse(self::ERROR_REQUEST_DECLINED['status'],self::ERROR_REQUEST_DECLINED['message'], array('code' => self::ERROR_REQUEST_DECLINED['code']));

				} else {

					return $this->setResponse($response_code['status'],$response_code['message'], array('code' => $response_code["statusCode"]));


				}

			}

		} else {
			return $this->setResponse(self::ERROR_ACCOUNT_BLOCKED['status'],"Player cannot be found", array('code' => self::ERROR_ACCOUNT_BLOCKED['code']));
		}


	}

	private function credit() {

		$this->validateHeaders(__METHOD__);

		$params = $this->requestParams->params;

		$rules = [
			"txnType" => "required",
			"txnId" => "required",
			"playerId" => "required",
			"roundId" => "required",
			"amount" => "required",
			"currency" => "required",
			'gameId' => "required",
			"created" => "required",
			'completed' => "required"
		];

		if(!$this->isValidParams((array)$params , $rules )){
			return $this->setResponse(self::ERROR_REQUEST_DECLINED['status'],self::ERROR_REQUEST_DECLINED['message'], array('code' => self::ERROR_REQUEST_DECLINED['code']));
		}

        $this->CI->utils->debug_log('QT HACKSAW Request Parameters', $params);

        $gameUsername = $params->playerId;


		$playerId = $this->game_api->getPlayerIdByGameUsername($gameUsername);

		if(!empty($playerId)) {

			$this->player_id = $playerId;

			$player_info['username'] = $this->player_model->getUsernameById($playerId);
			$player_info['playerId'] = $playerId;

			$response_code = array(
				"statusCode"	=> self::RETURN_OK["code"],
				"message"		=> self::RETURN_OK["message"],
				"status" 		=> self::RETURN_OK['status']
			);

			$self = $this;

			$trans_success = $this->lockAndTransForPlayerBalance($playerId, function() use($self,$playerId,$player_info,&$response_code, $params) {
				$player_balance = $self->game_api->queryPlayerBalance($player_info['username'])['balance'];
				$player_balance = $self->game_api->dBtoGameAmount($player_balance);
				$transaction_id = $params->txnId;
				$round_id = $params->roundId;

				$new_trans_id = $transaction_id . "-" . $round_id;
				$platform_id = $self->game_api->getPlatformCode();
				$existingRow = $self->common_seamless_wallet_transactions->getTransactionObjectByField($platform_id,$transaction_id,"transaction_id",SELF::CREDIT);

				if(!empty($existingRow)){
					$response_code['referenceId'] = $existingRow->external_unique_id;
					$response_code['after_balance'] = $player_balance;
					return true;

				}

				$creditAmount = isset($params->amount) ? $params->amount : null;

				// if (($player_balance - $creditAmount) < 0) {
				// 	$response_code['statusCode'] 	= self::ERROR_INSUFFICIENT_FUNDS["code"];
				// 	$response_code['status'] 		= self::ERROR_INSUFFICIENT_FUNDS["status"];
				// 	$response_code['message'] 		= self::ERROR_INSUFFICIENT_FUNDS["message"];
				// 	return false;
				// }

				$response_code = $self->adjustWallet(self::CREDIT, $player_info, $params);

				if($response_code['status'] != self::STATUS_SUCCESS) {
					return false;
				}

				if($response_code['status'] != self::STATUS_SUCCESS && $response_code['message'] == self::UPDATE_INSERT_MESSAGE) {
					return false;
				}

				return true;
			});


			if($trans_success) {


				if(!array_key_exists('after_balance', $response_code)) {
					$balance = $this->game_api->queryPlayerBalance($player_info['username'])['balance'];
					$balance = $this->game_api->dBtoGameAmount($balance);
				} else {
					$balance = $response_code["after_balance"];
				}

				$response_data = array(
					"balance" => $balance,
				);

				if(isset($response_code['referenceId']) && !empty($response_code['referenceId'])) {
					$response_data['referenceId'] = $response_code['referenceId'];
				}

				return $this->setResponse($response_code['status'],$response_code['message'], $response_data);


			} else {

				if($response_code["status"] == self::STATUS_SUCCESS) { // this scenario is for if player is locked and as you can see the default value for response code is success

					// it should not be success if failed. lockAndTransForPlayerBalance
					return $this->setResponse(self::ERROR_REQUEST_DECLINED['status'],self::ERROR_REQUEST_DECLINED['message'], array('code' => self::ERROR_REQUEST_DECLINED['code']));

				} else {

					return $this->setResponse($response_code['status'],$response_code['message'], array('code' => $response_code["statusCode"]));


				}

			}

		} else {
			return $this->setResponse(self::ERROR_ACCOUNT_BLOCKED['status'],"Player cannot be found", array('code' => self::ERROR_ACCOUNT_BLOCKED['code']));
		}

	}

	public function bonus($type) {

		$this->gameUsername = isset($this->requestParams->params->playerId) ?  $this->requestParams->params->playerId : null;

		$method = $type;

        if(!method_exists($this, $method)) {
            $this->utils->debug_log('QT HACKSAW ' . __METHOD__ , $method , 'method not exists');
            return $this->setResponse(self::ERROR_UNKNOWN['status'],self::ERROR_UNKNOWN['message'], array('code' => self::ERROR_UNKNOWN['code']));
        }

        $response = $this->$method();

		return $response;

	}

	private function rewards() {

		$this->transactionType = "bonus-rewards";

		$this->validateHeaders(__METHOD__);

		$params = $this->requestParams->params;

		$rules = [
			"rewardType" => "required",
			"rewardTitle" => "required",
			"txnId" => "required",
			"playerId" => "required",
			"amount" => "required",
			"created" => "required"
		];

		if(!$this->isValidParams((array)$params , $rules )){
			return $this->setResponse(self::ERROR_REQUEST_DECLINED['status'],self::ERROR_REQUEST_DECLINED['message'], array('code' => self::ERROR_REQUEST_DECLINED['code']));
		}

        $this->CI->utils->debug_log('QT HACKSAW Request Parameters', $params);

        $gameUsername = $params->playerId;


		$playerId = $this->game_api->getPlayerIdByGameUsername($gameUsername);

		if(!empty($playerId)) {

			$this->player_id = $playerId;

			$player_info['username'] = $this->player_model->getUsernameById($playerId);
			$player_info['playerId'] = $playerId;

			$response_code = array(
				"statusCode"	=> self::RETURN_OK["code"],
				"message"		=> self::RETURN_OK["message"],
				"status" 		=> self::RETURN_OK['status']
			);

			$self = $this;

			$trans_success = $this->lockAndTransForPlayerBalance($playerId, function() use($self,$playerId,$player_info,&$response_code, $params) {
				$player_balance = $self->game_api->queryPlayerBalance($player_info['username'])['balance'];
				$player_balance = $self->game_api->dBtoGameAmount($player_balance);
				$transaction_id = $params->txnId;
				$platform_id = $self->game_api->getPlatformCode();
				$existingRow = $self->common_seamless_wallet_transactions->getTransactionObjectByField($platform_id,$transaction_id,"transaction_id",SELF::BONUS_REWARDS);

				if(!empty($existingRow)){
					$response_code['referenceId'] = $existingRow->external_unique_id;
					$response_code['after_balance'] = $player_balance;
					return true;

				}

				$amount = isset($params->amount) ? $params->amount : null;

				// if (($player_balance - $amount) < 0) {
				// 	$response_code['statusCode'] 	= self::ERROR_INSUFFICIENT_FUNDS["code"];
				// 	$response_code['status'] 		= self::ERROR_INSUFFICIENT_FUNDS["status"];
				// 	$response_code['message'] 		= self::ERROR_INSUFFICIENT_FUNDS["message"];
				// 	return false;
				// }

				$response_code = $self->adjustWallet(self::BONUS_REWARDS, $player_info, $params);

				if($response_code['status'] != self::STATUS_SUCCESS) {
					return false;
				}

				if($response_code['status'] != self::STATUS_SUCCESS && $response_code['message'] == self::UPDATE_INSERT_MESSAGE) {
					return false;
				}

				return true;
			});


			if($trans_success) {


				if(!array_key_exists('after_balance', $response_code)) {
					$balance = $this->game_api->queryPlayerBalance($player_info['username'])['balance'];
					$balance = $this->game_api->dBtoGameAmount($balance);
				} else {
					$balance = $response_code["after_balance"];
				}

				$response_data = array(
					"balance" => $balance,
				);

				if(isset($response_code['referenceId']) && !empty($response_code['referenceId'])) {
					$response_data['referenceId'] = $response_code['referenceId'];
				}

				return $this->setResponse($response_code['status'],$response_code['message'], $response_data);


			} else {

				if($response_code["status"] == self::STATUS_SUCCESS) { // this scenario is for if player is locked and as you can see the default value for response code is success

					// it should not be success if failed. lockAndTransForPlayerBalance
					return $this->setResponse(self::ERROR_REQUEST_DECLINED['status'],self::ERROR_REQUEST_DECLINED['message'], array('code' => self::ERROR_REQUEST_DECLINED['code']));

				} else {

					return $this->setResponse($response_code['status'],$response_code['message'], array('code' => $response_code["statusCode"]));


				}

			}

		} else {
			return $this->setResponse(self::ERROR_ACCOUNT_BLOCKED['status'],"Player cannot be found", array('code' => self::ERROR_ACCOUNT_BLOCKED['code']));
		}

	}

	private function rollback() {

		$this->transactionType = "rollback";

		$this->validateHeaders(__METHOD__);

		$params = $this->requestParams->params;

        $this->CI->utils->debug_log('QT HACKSAW Request Parameters', $params);

        $gameUsername = $params->playerId;


		$playerId = $this->game_api->getPlayerIdByGameUsername($gameUsername);

		if(!empty($playerId)) {

			$this->player_id = $playerId;

			$player_info['username'] = $this->player_model->getUsernameById($playerId);
			$player_info['playerId'] = $playerId;

			$response_code = array(
				"statusCode"	=> self::RETURN_OK["code"],
				"message"		=> self::RETURN_OK["message"],
				"status" 		=> self::RETURN_OK['status']
			);

			$self = $this;

			$trans_success = $this->lockAndTransForPlayerBalance($playerId, function() use($self,$playerId,$player_info,&$response_code, $params) {
				$player_balance = $self->game_api->queryPlayerBalance($player_info['username'])['balance'];
				$player_balance = $self->game_api->dBtoGameAmount($player_balance);
				$transaction_id = $params->txnId;
				$round_id = $params->roundId;

				$new_trans_id = $transaction_id . "-" . $round_id;
				$platform_id = $self->game_api->getPlatformCode();
				$existingRow = $self->common_seamless_wallet_transactions->getTransactionObjectByField($platform_id,$transaction_id,"transaction_id",SELF::ROLLBACK);

				if(!empty($existingRow)){
					$response_code['referenceId'] = $existingRow->external_unique_id;
					$response_code['after_balance'] = $player_balance;
					return true;

				}

				$betAmount = isset($params->amount) ? $params->amount : null;

				if (($player_balance - $betAmount) < 0) {
					$response_code['statusCode'] 	= self::ERROR_INSUFFICIENT_FUNDS["code"];
					$response_code['status'] 		= self::ERROR_INSUFFICIENT_FUNDS["status"];
					$response_code['message'] 		= self::ERROR_INSUFFICIENT_FUNDS["message"];
					return false;
				}

				$response_code = $self->adjustWallet(self::ROLLBACK, $player_info, $params);

				if($response_code['status'] != self::STATUS_SUCCESS) {
					return false;
				}

				if($response_code['status'] != self::STATUS_SUCCESS && $response_code['message'] == self::UPDATE_INSERT_MESSAGE) {
					return false;
				}

				return true;
			});


			if($trans_success) {


				if(!array_key_exists('after_balance', $response_code)) {
					$balance = $this->game_api->queryPlayerBalance($player_info['username'])['balance'];
					$balance = $this->game_api->dBtoGameAmount($balance);
				} else {
					$balance = $response_code["after_balance"];
				}

				$response_data = array(
					"balance" => $balance,
				);

				if(isset($response_code['referenceId']) && !empty($response_code['referenceId'])) {
					$response_data['referenceId'] = $response_code['referenceId'];
				}

				return $this->setResponse($response_code['status'],$response_code['message'], $response_data);


			} else {

				if($response_code["status"] == self::STATUS_SUCCESS) { // this scenario is for if player is locked and as you can see the default value for response code is success

					// it should not be success if failed. lockAndTransForPlayerBalance
					return $this->setResponse(self::ERROR_REQUEST_DECLINED['status'],self::ERROR_REQUEST_DECLINED['message'], array('code' => self::ERROR_REQUEST_DECLINED['code']));

				} else {

					return $this->setResponse($response_code['status'],$response_code['message'], array('code' => $response_code["statusCode"]));


				}

			}

		} else {
			return $this->setResponse(self::ERROR_ACCOUNT_BLOCKED['status'],"Player cannot be found", array('code' => self::ERROR_ACCOUNT_BLOCKED['code']));
		}

	}


    protected function session(){


		$this->transactionType = "session";

        $this->validateHeaders(__METHOD__);


        $game_code = $_GET["gameId"];

		$playerId = $this->game_api->getPlayerIdByGameUsername($this->gameUsername);

		if(!empty($playerId)) {

			$player_info['username'] = $this->player_model->getUsernameById($playerId);
			$player_info['playerId'] = $playerId;

			$this->player_id = $playerId;

			$self = $this;

			$player_balance = 0;

			$currency = "";

			$trans_success = $this->lockAndTransForPlayerBalance($playerId, function() use($self,$player_info,&$player_balance, &$currency) {

				$player_balance = $self->game_api->queryPlayerBalance($player_info['username'])['balance'];

				$currency = $self->game_api->getCurrency();

				$player_balance = $self->game_api->dBtoGameAmount($player_balance);

				return true;
			});


			if($trans_success) {

				$response_data = array(
					"balance" => $player_balance,
					"currency" => $currency
				);

				return $this->setResponse(self::RETURN_OK['status'],self::RETURN_OK['message'], $response_data);

			} else {

				$this->utils->debug_log('QT HACKSAW ' . __METHOD__ , 'trans success is false');
            	return $this->setResponse(self::ERROR_REQUEST_DECLINED['status'],self::ERROR_REQUEST_DECLINED['message'], array('code' => self::ERROR_REQUEST_DECLINED['code']));

			}

		} else {

			return $this->setResponse(self::ERROR_ACCOUNT_BLOCKED['status'],"Player cannot be found", array('code' => self::ERROR_ACCOUNT_BLOCKED['code']));

		}

        return $game_code;

    }


	protected function balance(){

		$this->transactionType = "balance";

        $this->validateHeaders(__METHOD__);

        $game_code = isset($_GET["gameId"]) ? $_GET["gameId"] : null;

		$playerId = $this->game_api->getPlayerIdByGameUsername($this->gameUsername);

		if(!empty($playerId)) {

			$this->player_id = $playerId;

			$player_info['username'] = $this->player_model->getUsernameById($playerId);
			$player_info['playerId'] = $playerId;

			$this->player_id = $playerId;

			$self = $this;

			$player_balance = 0;

			$currency = "";

			$trans_success = $this->lockAndTransForPlayerBalance($playerId, function() use($self,$player_info,&$player_balance, &$currency) {

				$player_balance = $self->game_api->queryPlayerBalance($player_info['username'])['balance'];

				$currency = $self->game_api->getCurrency();

				$player_balance = $self->game_api->dBtoGameAmount($player_balance);

				return true;
			});


			if($trans_success) {

				$response_data = array(
					"balance" => $player_balance,
					"currency" => $currency
				);

				return $this->setResponse(self::RETURN_OK['status'],self::RETURN_OK['message'], $response_data);

			} else {

				$this->utils->debug_log('QT HACKSAW ' . __METHOD__ , 'trans success is false');
            	return $this->setResponse(self::ERROR_UNKNOWN['status'],self::ERROR_UNKNOWN['message'], array('code' => self::ERROR_UNKNOWN['code']));

			}

		} else {

			return $this->setResponse(self::ERROR_ACCOUNT_BLOCKED['status'],"Player cannot be found", array('code' => self::ERROR_ACCOUNT_BLOCKED['code']));

		}

        return $game_code;

    }



	protected function processRequest(){
		$request = file_get_contents('php://input');
		$this->CI->utils->debug_log(__FUNCTION__, 'QT Hacksaw (Raw Input): ', $request);
		$decoded_params=json_decode($request);
		$this->CI->utils->debug_log(__FUNCTION__, 'QT Hacksaw (Raw array input): ', $decoded_params);
		$this->requestParams->params = $decoded_params;

		return $this->requestParams;
	}

	private function adjustWallet($action, $player_info, $trans_record) {

        $existingTransaction = array();

		$transType = "";

		$response_code = array(
			"statusCode"	=> self::RETURN_OK["code"],
			"message"		=> self::RETURN_OK["message"],
			"status" 		=> self::RETURN_OK['status']
		);

        $before_balance = $this->game_api->queryPlayerBalance($player_info['username'])['balance'];

        $response_code['before_balance'] = $this->game_api->dBtoGameAmount($before_balance);

        $external_game_id = isset($trans_record->gameId) ? $trans_record->gameId : null;

        if($action == self::DEBIT) {

            $betAmount = $trans_record->amount;

            if($betAmount > 0) {


                $will_deduct = isset($trans_record->bonusType) &&  $trans_record->bonusType == "FREE_ROUND" ? false : true;

                if($will_deduct) {
                	$uniqueid =  $action.'-'.$trans_record->txnId;
                    $uniqueIdOfSeamlessService=$this->game_api->getPlatformCode().'-'.$uniqueid;

                    if (method_exists($this->wallet_model, 'setUniqueidOfSeamlessService')) {
                        $this->wallet_model->setUniqueidOfSeamlessService($uniqueIdOfSeamlessService, $external_game_id);
                    }

                    if (method_exists($this->wallet_model, 'setGameProviderActionType')) {
                        $this->wallet_model->setGameProviderActionType('bet');
                    }

                    $betAmount = $this->game_api->gameAmountToDB($betAmount);

                    $this->CI->utils->debug_log('QT HACKSAW BET AMOUNT', $betAmount);
                    $deduct_balance = $this->wallet_model->decSubWallet($player_info['playerId'], $this->game_api->getPlatformCode(), $betAmount);
                    $this->CI->utils->debug_log('QT HACKSAW DEDUCT BALANCE', $deduct_balance);

                }

            }

			$after_balance = $this->game_api->queryPlayerBalance($player_info['username'])['balance'];

            $response_code["after_balance"] = $this->game_api->dBtoGameAmount($after_balance);

        }  elseif ($action == self::CREDIT) {

            $amount = $trans_record->amount;

			if($amount > 0) {
				$uniqueid =  $action.'-'.$trans_record->txnId;
                $uniqueIdOfSeamlessService=$this->game_api->getPlatformCode().'-'.$uniqueid;

                if (method_exists($this->wallet_model, 'setUniqueidOfSeamlessService')) {
                    $this->wallet_model->setUniqueidOfSeamlessService($uniqueIdOfSeamlessService, $external_game_id);
                }

                if (method_exists($this->wallet_model, 'setGameProviderActionType')) {
                    $this->wallet_model->setGameProviderActionType('payout');
                }

				$amount = $this->game_api->gameAmountToDB($amount);

				$add_balance = $this->wallet_model->incSubWallet($player_info['playerId'], $this->game_api->getPlatformCode(), $amount);
				$this->CI->utils->debug_log('QT HACKSAW ADD BALANCE', $add_balance);

			}

            $after_balance = $this->game_api->queryPlayerBalance($player_info['username'])['balance'];

            $response_code["after_balance"] = $this->game_api->dBtoGameAmount($after_balance);


		} elseif ($action == self::BONUS_REWARDS) {

			$transType = self::BONUS_REWARDS;

            $amount = $trans_record->amount;

			if($amount > 0) {
				$uniqueid =  $action.'-'.$trans_record->txnId;
                $uniqueIdOfSeamlessService=$this->game_api->getPlatformCode().'-'.$uniqueid;

                if (method_exists($this->wallet_model, 'setUniqueidOfSeamlessService')) {
                    $this->wallet_model->setUniqueidOfSeamlessService($uniqueIdOfSeamlessService, $external_game_id);
                }

                if (method_exists($this->wallet_model, 'setGameProviderActionType')) {
                    $this->wallet_model->setGameProviderActionType('payout');
                }

				$amount = $this->game_api->gameAmountToDB($amount);

				$add_balance = $this->wallet_model->incSubWallet($player_info['playerId'], $this->game_api->getPlatformCode(), $amount);
				$this->CI->utils->debug_log('QT HACKSAW ADD BALANCE', $add_balance);

			}

            $after_balance = $this->game_api->queryPlayerBalance($player_info['username'])['balance'];

            $response_code["after_balance"] = $this->game_api->dBtoGameAmount($after_balance);


		} else if ($action == self::ROLLBACK) {

			$transType = self::ROLLBACK;

			$amount = $trans_record->amount;

			// check if the bet id is existing if not no deduction will happen
			$betId = $trans_record->betId;

			$existingTransactionDebit = $this->common_seamless_wallet_transactions->getTransactionObjectByField($this->game_api->getPlatformCode(),$betId,"transaction_id",SELF::DEBIT);


			if(!empty($existingTransactionDebit)) {

				if($amount > 0) {
					$uniqueid =  $action.'-'.$trans_record->txnId;
                    $uniqueIdOfSeamlessService=$this->game_api->getPlatformCode().'-'.$uniqueid;

                    if (method_exists($this->wallet_model, 'setUniqueidOfSeamlessService')) {
                        $this->wallet_model->setUniqueidOfSeamlessService($uniqueIdOfSeamlessService, $external_game_id);
                    }

                    if (method_exists($this->wallet_model, 'setGameProviderActionType')) {
                        $this->wallet_model->setGameProviderActionType('refund');
                    }

					$amount = $this->game_api->gameAmountToDB($amount);

					$add_balance = $this->wallet_model->incSubWallet($player_info['playerId'], $this->game_api->getPlatformCode(), $amount);
					$this->CI->utils->debug_log('QT HACKSAW ADD BALANCE', $add_balance);

				}

			}


            $after_balance = $this->game_api->queryPlayerBalance($player_info['username'])['balance'];

            $response_code["after_balance"] = $this->game_api->dBtoGameAmount($after_balance);


        } else {
            $response_code['statusCode'] = self::ERROR_REQUEST_DECLINED['code'];
			$response_code['message'] = self::ERROR_REQUEST_DECLINED['message'];
            $response_code['status'] = self::ERROR_REQUEST_DECLINED['status'];
            return $response_code;
        }
        // }


		$insertOnTrans = $this->processTransaction($response_code['before_balance'],$response_code['after_balance'],$trans_record,$player_info, $transType);

		if(!$insertOnTrans['success']) {
			$response_code = [
				'statusCode' => self::RETURN_OK['code'],
				'message' => self::RETURN_OK['message'],
				'status' => self::RETURN_OK['status'],
				'after_balance' => (int)$insertOnTrans['result']['after_balance']
			];
		}

		$response_code["referenceId"] = isset($insertOnTrans['result']['referenceId']) ? $insertOnTrans['result']['referenceId'] : null;

		return $response_code;

	}

    private function processTransaction($before_balance,$after_balance,$trans_record,$player_info, $transType = "") {

    	$apiId = $this->gamePlatformId;
    	$transaction_type = empty($transType) ? $this->requestParams->params->txnType : $transType;
		$transaction_type = strtolower($transaction_type);
    	$playerId = $this->player_model->getPlayerIdByUsername($player_info['username']);
		$UserName = isset($trans_record->playerId) ? $trans_record->playerId : null;
        $RoundId = isset($trans_record->roundId) ? $trans_record->roundId : null;
		$TransactionId = isset($trans_record->txnId) ? $trans_record->txnId : null;
        $Amount = isset($trans_record->amount) ? $this->game_api->gameAmountToDB($trans_record->amount) : null;
		$BonusBetAmount = isset($trans_record->bonusBetAmount) ? $this->game_api->gameAmountToDB($trans_record->bonusBetAmount) : null;
		$TransactionId = isset($trans_record->txnId) ? $trans_record->txnId : null;
		$BonusType = isset($trans_record->bonusType) ? $trans_record->bonusType : null;
		$BonusPromoCode = isset($trans_record->bonusPromoCode) ? $trans_record->bonusPromoCode : null;
		$JPContribution = isset($trans_record->jpContribution) ? $trans_record->jpContribution : null;
		$GameId = isset($trans_record->gameId) ? $trans_record->gameId : null;
		$Device = isset($trans_record->device) ? $trans_record->device : null;
		$ClientType = isset($trans_record->clientType) ? $trans_record->clientType : null;
		$ClientRoundId = isset($trans_record->clientRoundId) ? $trans_record->clientRoundId : null;
		$Currency = isset($trans_record->currency) ? $trans_record->currency : null;
		$Created = isset($trans_record->created) ? $trans_record->created : null;
		$Completed = isset($trans_record->completed) ? $trans_record->completed : null;
		$Category = isset($trans_record->category) ? $trans_record->category : null;
		$JPPayout = isset($trans_record->jpPayout) ? $trans_record->jpPayout : null;
		$BetId = isset($trans_record->betId) ? $trans_record->betId : null;
		$RewardType = isset($trans_record->rewardType) ? $trans_record->rewardType : null;
		$RewardTitle = isset($trans_record->rewardTitle) ? $trans_record->rewardTitle : null;
		$TableId = isset($trans_record->tableId) ? $trans_record->tableId : null;

		$externalUniqueId = $TransactionId;
        $now = (new DateTime())->format('Y-m-d H:i:s');

        if(!empty($Created)) {

			$time_arr = explode("[",$Created);

			$time = strtotime($time_arr[0]);

            $dateTime = date('Y-m-d H:i:s', $time);

            $dateTime = $this->game_api->gameTimeToServerTime($dateTime);
        }

        $startTime = !empty($Created) ? $dateTime : $now;
        $endTime = !empty($Created) ? $dateTime : $now;


        // if($transaction_type == SELF::DEBIT || $transaction_type == SELF::REVOKE) {
		// if($transaction_type == SELF::DEBIT) {
        //     $Round = $RoundId;
        //     $RoundId = $TransactionId;
        //     $TransactionId = $TransactionId;

        // } else if($transaction_type == SELF::CREDIT) {

        //     $CreditIndex = isset($trans_record->creditIndex) ? $trans_record->creditIndex : null;

        //     $RoundId = $TransactionId;
        //     $TransactionId = $TransactionId . "-credit-" . $CreditIndex;
        // }



    	$extra_info = [
    		'playerId' => $playerId,
            'UserName' => $UserName,
            'TransactionId' => $TransactionId,
            'Amount' => $Amount,
            'Currency' => $Currency,
            'GameId' => $GameId,
            'RoundId' => $RoundId,
            'TransactionType' => $transaction_type,
			'Device' => $Device,
			'ClientType' => $ClientType,
			'ClientRoundId' => $ClientRoundId,
			'Created' => $Created,
			'Category' => $Category,
			'Completed' => $Completed,
			'TableId' => $TableId,
			'GameUserName' => $this->gameUsername
        ];


        if($transaction_type == self::DEBIT) {

			$extra_info["BonusBetAmount"] = $BonusBetAmount;
            $extra_info["BonusType"] = $BonusType;
			$extra_info["BonusPromoCode"] = $BonusPromoCode;
			$extra_info["JPContribution"] = $JPContribution;

        } else if($transaction_type == self::CREDIT) {

            $extra_info["JPPayout"] = $JPPayout;
			$extra_info["BetId"] = $BetId;

		} else if ($transaction_type == self::BONUS_REWARDS) {

			$extra_info["RewardType"] = $RewardType;

			$extra_info["RewardTitle"] = $RewardTitle;

		} else if ($transaction_type == SELF::ROLLBACK) {



            $platform_id = $this->game_api->getPlatformCode();

			$extra_info["BetId"] = $BetId;



            // check first if the revoke is already triggered

            $existingTransactionRollback = $this->common_seamless_wallet_transactions->getTransactionObjectByField($platform_id,$TransactionId,"transaction_id", SELF::ROLLBACK);

            if(!EMPTY($existingTransactionRollback)) {

                $result = [];

                $after_balance = $this->game_api->queryPlayerBalance($player_info['username'])['balance'];

                $result['after_balance'] = $this->game_api->dBtoGameAmount($after_balance);

				$result['referenceId'] = $existingTransactionRollback->external_unique_id;

                return ['success' => true, 'result' => $result];

            }

            $existingTransactionBet = $this->common_seamless_wallet_transactions->getTransactionObjectByField($platform_id,$BetId,"transaction_id", SELF::DEBIT);

            if(!EMPTY($existingTransactionBet)) {

                $existing_transaction_id = $existingTransactionBet->transaction_id;

                $this->common_seamless_wallet_transactions->setTransactionStatus( $this->game_api->getPlatformCode(), $existing_transaction_id, 'transaction_id', 'cancelled', SELF::DEBIT);

			}

        }

        $extraInfo = json_encode($extra_info);


        $gameRecords = [
            [
                'game_platform_id' => $apiId,
                'amount' => $Amount,
                'game_id' => $GameId,
                'transaction_type' => $transaction_type,
                'response_result_id' => $this->utils->getRequestId(),
                'external_unique_id' =>  $transaction_type.'-'.$TransactionId,
                'extra_info' => $extraInfo,
                'start_at' => $startTime,
                'end_at' => $endTime,
                'transaction_id' => $TransactionId,
                'before_balance' => $this->game_api->gameAmountToDB($before_balance),
                'after_balance' => $this->game_api->gameAmountToDB($after_balance),
                'player_id' => $playerId,
                'round_id' => $RoundId,
                'status' => 'ok'
            ]
        ];

        $this->processGameRecords($gameRecords);

        $success=true;
        $result=[];

        $existingRow = $this->common_seamless_wallet_transactions->getTransactionObjectByField($this->gamePlatformId,$TransactionId,"transaction_id",$transaction_type);
        if(!empty($existingRow)) {
        	$dataRecord = $gameRecords[0];
            if($dataRecord["amount"] != $existingRow->amount) {

                $external_uniqueid = 'update-'.$transaction_type.'-'.$TransactionId.'-'.$before_balance.$after_balance.'-'.$RoundId;
                $gameRecords[0]['external_unique_id'] = $external_uniqueid;

                $this->common_seamless_wallet_transactions->insertRow($gameRecords[0]);

            } else {
                $after_balance = $this->game_api->queryPlayerBalance($player_info['username'])['balance'];
                $result['after_balance'] = $this->game_api->dBtoGameAmount($after_balance);
				$success=false;
            }


        } else {
                $this->common_seamless_wallet_transactions->insertRow($gameRecords[0]);
        }

        if(!array_key_exists('after_balance', $result)) {
            $after_balance = $this->game_api->queryPlayerBalance($player_info['username'])['balance'];
            $result['after_balance'] = $this->game_api->dBtoGameAmount($after_balance);
        }

		$result["referenceId"] = $transaction_type.'-'.$TransactionId;

        return ['success' => $success, 'result' => $result];
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

    private function setResponse($statusCode, $message, $data = []) {
    	$code = ['status' => $statusCode, 'message' => $message];
        $data = array_merge($code,$data);
        return $this->setOutput($data);
    }

    private function setOutput($data = []) {
        $flag = $data['status'] == 200 ? Response_result::FLAG_NORMAL : Response_result::FLAG_ERROR;
        $statusHeader = $data['status'];
		if($data["status"] == 200) {
			unset($data['message']);
		}
        unset($data['status']);
        $data = json_encode($data);
        $fields = ['player_id' => $this->player_id];

		$cost = intval($this->utils->getExecutionTimeToNow()*1000);

        if($this->game_api) {
            $this->CI->response_result->saveResponseResult(
                $this->game_api->getPlatformCode(),
                $flag,
                isset($this->requestParams->params->txnType) ? $this->requestParams->params->txnType : $this->transactionType,
                isset($this->requestParams->params) ? json_encode($this->requestParams->params): null,
                $data,
                $statusHeader,
                null,
                is_array($this->headers) ? json_encode($this->headers) : $this->headers,
                $fields,
				false,
				null,
				$cost
            );
        }

        $this->output->set_content_type('application/json')
                ->set_output($data)
                ->set_status_header($statusHeader);
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

    public function generatePlayerToken(){
        $this->utils->debug_log("QT HACKSAW SERVICE API: (bet)");

		$username = $this->requestParams->params->username;
		$result = $this->game_api->getPlayerTokenByUsername($username);
		var_dump($result);
	}

	private function isValidParams($request, $rules){
		//validate params
		foreach($rules as $key => $rule){
			if($rule=='required'&&!isset($request[$key])){
				$this->utils->error_log("AMB SEAMLESS SERVICE: (isValidParams) Missing Parameters: ". $key, $request, $rules);
				return false;
			}


			if($rule=='isNumeric'&&isset($request[$key])&&!$this->isNumeric($request[$key])){
				$this->utils->error_log("AMB SEAMLESS SERVICE: (isValidParams) Parameters isNotNumeric: ". $key . '=' . $request[$key], $request, $rules);
				return false;
			}
		}

		return true;
	}

	public function isNumeric($amount){
		return is_numeric($amount);
	}

}