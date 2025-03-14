<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

/**
 * Defines general behavior of game API classes.
 *
 * General behaviors include:
 * * Gets platform code
 * * Get Accounts Info
 * * Check Bonus Release
 * * Release Bet Result
 * * Get Refund
 * * Get player's balance
 * * Checks if the user got a bonus win
 * * Transfer money to sub-wallet BS
 * * Shows Transfer Status
 * * Check Forward Game
 * * Block/Unblock Player
 * * Get BBIN Records
 * * Extract xml record
 * * Synchronize Game Records
 * * Check Player's Balance
 *
 * The functions implemented by child class:
 * * Populating game form parameters
 * * Handling callbacks
 *
 *
 *
 * @see Redirect redirect to game page
 * @deprecated no lock on update wallet
 *
 * @category Game_platform
 * @version 1.8.10
 * @copyright 2013-2022 tot
 */

class Game_api_bs extends Abstract_game_api {

	private $bank_id;
	private $url;
	private $pass_key;
	private $currency;
	private $default_language;
	private $token_timeout;

	const CONVERSION_RATE 			= 100;
	const ERROR_INTERNALERROR 		= 399;
	const ERROR_INVALIDHASH 		= 500;
	const ERROR_INVALIDTOKEN 		= 400;
	const ERROR_INSUFFICIENTFUNDS 	= 300;
	const ERROR_UNKNOWNUSERID 		= 310;
	const SUCCESS 					= 'OK';
	const ERROR 					= 'ERROR';
	const REAL_MODE 				= 'real';
	const FREE_MODE 				= 'free';

	const URI_MAP 					= array(
		self::API_queryForwardGame  => 'ctenter.do',
	);

	public function __construct() {
		parent::__construct();
		$this->url 				= $this->getSystemInfo('url');
		$this->bank_id 			= $this->getSystemInfo('account');
		$this->pass_key 		= $this->getSystemInfo('key');
		$this->currency 		= $this->getSystemInfo('currency');
		$this->default_language = $this->getSystemInfo('language');
		$this->token_timeout 	= $this->getSystemInfo('token_timeout');
	}

	public function getPlatformCode() {
		return BS_API;
	}

	# COMMON CALLBACK #############################################################################################################################
	public function authenticate($request) {

		$this->CI->load->model('common_token');
		$this->CI->load->helper('string');

		$hash = $this->hash(array(
			$request['token'],
			$request['userId'],
		));

		if ($request['hash'] == $hash) {

			$playerId = $this->CI->common_token->getPlayerIdByToken($request['token']);

			if ( ! $playerId) {
				return $this->generateReturn($request, array(
					'RESULT' => self::ERROR,
					'CODE' 	 => self::ERROR_INVALIDTOKEN,
				));
			}

			$playerDetails 	= $this->getPlayerDetails($playerId);
			$playerInfo 	= $this->getPlayerInfo($playerId);
			$playerName 	= $this->getGameUsernameByPlayerUsername($playerInfo->username);
			$balance 		= $this->convertDBAmountToGame($this->CI->player_model->getMainWalletBalance($playerId));

			$response = array(
				'RESULT' 		=> self::SUCCESS,
				'USERID' 		=> $playerId,								# REQUIRED
				'USERNAME' 		=> $playerName,								# OPTIONAL
				'FIRSTNAME' 	=> $playerDetails->firstName,				# OPTIONAL
				'LASTNAME' 		=> $playerDetails->lastName,				# OPTIONAL
				'EMAIL' 		=> $playerInfo->email,						# OPTIONAL
				'CURRENCY' 		=> $this->currency,							# OPTIONAL
				'BALANCE' 		=> $balance, 								# current balance of player in cents on EC system side
			);

			$this->CI->common_token->disableToken($request['token']);

		} else {

			$response = array(
				'RESULT' => self::ERROR,
				'CODE' 	 => self::ERROR_INVALIDHASH,
			);

		}

		return $this->generateReturn($request, $response);
	}

	public function getAccountInfo($request) {

		$hash = $this->hash(array(
			$request['userId'],
		));

		if ($request['hash'] == $hash) {

			$playerId 		  = $request['userId'];
			$playerDetails 	  = $this->getPlayerDetails($playerId);
			$playerInfo 	  = $this->getPlayerInfo($playerId);
			$playerName 	  = $this->getGameUsernameByPlayerUsername($playerInfo->username);

			$response = array(
				'RESULT' 	  => self::SUCCESS,
				'USERNAME' 	  => $playerName,					# OPTIONAL
				'FIRSTNAME'   => $playerDetails->firstName,		# OPTIONAL
				'LASTNAME' 	  => $playerDetails->lastName,		# OPTIONAL
				'EMAIL' 	  => $playerInfo->email,			# OPTIONAL
				'CURRENCY' 	  => $this->currency,
			);
		} else {
			$response = array(
				'RESULT' 	  => self::ERROR,
				'CODE' 	 	  => self::ERROR_INVALIDHASH,
			);
		}

		return $this->generateReturn($request, $response);
	}

	public function bonusRelease($request) {

		$hash = $this->hash(array(
			$request['userId'],
			$request['bonusId'],
			$request['amount'],
		));

		if ($request['hash'] == $hash) {

			$balance = 1;

			$response = array(
				'RESULT' 		=> self::SUCCESS,
			);
		} else {
			$response = array(
				'RESULT' => self::ERROR,
				'CODE' 	 => self::ERROR_INVALIDHASH,
			);
		}

		return $this->generateReturn($request, $response);
	}

	# TTT CALLBACK ################################################################################################################################
	public function betResult($request) {

		$this->CI->load->model(array('player_model', 'bs_game_logs'));

		$hash = $this->hash(array(
			$request['userId'],
			$request['bet'],
			$request['win'],
			$request['isRoundFinished'],
			$request['roundId'],
			$request['gameId'],
		));

		# Check the request parameters and validate the hash.
		if ($request['hash'] == $hash) {

			$playerId = $request['userId'];

			if ( ! $this->getGameUsernameByPlayerId($playerId)) {
				return $this->generateReturn($request, array(
					'RESULT' => self::ERROR,
					'CODE' 	 => self::ERROR_UNKNOWNUSERID,
				));
			}

			# Check if the transaction was already processed. If yes – just return success response (See details below).
			if ( ! $this->CI->bs_game_logs->isUniqueIdAlreadyExists($request['transactionId'])) {

				$amount  = $this->convertGameAmountToDB($request['amount']);
				$balance = $this->CI->player_model->getMainWalletBalance($playerId);

				# check balance
				if ($balance < $amount) {
					return $this->generateReturn($request, array(
						'RESULT' => self::ERROR,
						'CODE' 	 => self::ERROR_INSUFFICIENTFUNDS,
					));
				}

				# update player balance
				$this->CI->wallet_model->decMainWallet($playerId, $balance);

				# save BSG transaction if processing was successful
				$this->CI->bs_game_logs->insertBsGameLogs($request);

			}

			$balance = $this->CI->player_model->getMainWalletBalance($playerId);
			$balance = $this->convertDBAmountToGame($balance);

			$extSystemTransactionId = uniqid(); # TODO:

			# Return response to BSG.
			$response = array(
				'RESULT' => self::SUCCESS,
				'EXTSYSTEMTRANSACTIONID' => $extSystemTransactionId,
				'BALANCE' => $balance, # current balance of player in cents on EC system side
			);

		} else {

			$response = array(
				'RESULT' => self::ERROR,
				'CODE' 	 => self::ERROR_INVALIDHASH,
			);

		}

		return $this->generateReturn($request, $response);
	}

	public function refundBet($request) {

		$this->CI->load->model(array('player_model', 'bs_game_logs'));

		$hash = $this->hash(array(
			$request['userId'],
			$request['casinoTransactionId'],
		));

		# Check the request parameters and validate the hash.
		if ($request['hash'] == $hash) {

			$playerId = $request['userId'];

			if ( ! $this->getGameUsernameByPlayerId($playerId)) {
				return $this->generateReturn($request, array(
					'RESULT' => self::ERROR,
					'CODE' 	 => self::ERROR_UNKNOWNUSERID,
				));
			}

			$extSystemTransactionId = uniqid(); # TODO:

			# Return response to BSG.
			$response = array(
				'RESULT' => self::SUCCESS,
				'EXTSYSTEMTRANSACTIONID' => $extSystemTransactionId,
				'BALANCE' => $balance, # current balance of player in cents on EC system side
			);

		} else {

			$response = array(
				'RESULT' => self::ERROR,
				'CODE' 	 => self::ERROR_INVALIDHASH,
			);

		}

		return $this->generateReturn($request, $response);
	}

	public function getBalance($request) {

		$this->CI->load->model(array('player_model', 'bs_game_logs'));

		$hash = $this->hash(array(
			$request['userId'],
		));

		# Check the request parameters and validate the hash.
		if ($request['hash'] == $hash) { # TODO: CHECK IF HAS HASH, NOT INDICATED IN THE DOCUMENT

			$playerId = $request['userId'];

			if ( ! $this->getGameUsernameByPlayerId($playerId)) {
				return $this->generateReturn($request, array(
					'RESULT' => self::ERROR,
					'CODE' 	 => self::ERROR_UNKNOWNUSERID,
				));
			}

			$balance = $this->CI->player_model->getMainWalletBalance($playerId);
			$balance = $this->convertDBAmountToGame($balance);

			# Return response to BSG.
			$response = array(
				'RESULT' 	=> self::SUCCESS,
				'BALANCE' 	=> $balance, # current balance of player in cents on EC system side
			);

		} else {

			$response = array(
				'RESULT' => self::ERROR,
				'CODE' 	 => self::ERROR_INVALIDHASH,
			);

		}

		return $this->generateReturn($request, $response);
	}

	public function bonusWin($request) {

		$hash = $this->hash(array(
			$request['userId'],
			$request['bonusId'],
			$request['amount'],
		));

		if ($request['hash'] == $hash) {

			$balance = 1;

			$response = array(
				'RESULT' 		=> self::SUCCESS,
			);
		} else {
			$response = array(
				'RESULT' => self::ERROR,
				'CODE' 	 => self::ERROR_INVALIDHASH,
			);
		}

		return $this->generateReturn($request, $response);
	}

	# LBBET CALLBACK ##############################################################################################################################
	public function transfer($request) {

		$this->CI->load->model(array('player_model', 'bs_game_logs'));

		$hash = $this->hash(array(
			$request['userId'],
			$request['transactionId'],
			$request['gameSessionId'],
			$request['gameId'],
			$request['amount'], # TODO: IF ADD OR SUBTRACT
		));

		# Check the request parameters and validate the hash.
		if ($request['hash'] == $hash) {

			$playerId = $request['userId'];

			if ( ! $this->getGameUsernameByPlayerId($playerId)) {
				return $this->generateReturn($request, array(
					'RESULT' => self::ERROR,
					'CODE' 	 => self::ERROR_UNKNOWNUSERID,
				));
			}

			# Check if the transaction was already processed. If yes – just return success response (See details below).
			if ( ! $this->CI->bs_game_logs->isUniqueIdAlreadyExists($request['transactionId'])) {

				$amount  = $this->convertGameAmountToDB($request['amount']);
				$balance = $this->CI->player_model->getMainWalletBalance($playerId);

				# check balance
				if ($balance < $amount) {
					return $this->generateReturn($request, array(
						'RESULT' => self::ERROR,
						'CODE' 	 => self::ERROR_INSUFFICIENTFUNDS,
					));
				}

				# update player balance
				$this->CI->wallet_model->decMainWallet($playerId, $balance);

				# save BSG transaction if processing was successful
				$this->CI->bs_game_logs->insertBsGameLogs($request);

			}

			$balance = $this->CI->player_model->getMainWalletBalance($playerId);
			$balance = $this->convertDBAmountToGame($balance);

			# Return response to BSG.
			$response = array(
				'RESULT' => self::SUCCESS,
				'BALANCE' => $balance, # current balance of player in cents on EC system side
			);

		} else {

			$response = array(
				'RESULT' => self::ERROR,
				'CODE' 	 => self::ERROR_INVALIDHASH,
			);

		}

		return $this->generateReturn($request, $response);
	}

	public function transferStatus($request) {

		$hash = $this->hash(array(
			$request['userId'],
			$request['transactionId'],
		));

		if ($request['hash'] == $hash) {

			$balance = 1;

			$response = array(
				'RESULT' 		=> self::SUCCESS,
				'BALANCE' 		=> $this->convertDBAmountToGame($balance), # current balance of player in cents on EC system side
			);
		} else {
			$response = array(
				'RESULT' => self::ERROR,
				'CODE' 	 => self::ERROR_INVALIDHASH,
			);
		}

		return $this->generateReturn($request, $response);
	}

	public function endGameSession($request) {

		// $this->CI->load->model('wallet_model');

		// $hash = $this->hash(array(
		// 	$request['userId'],
		// 	$request['transactionId'],
		// 	$request['gameSessionId'],
		// 	$request['totalBet'],
		// 	$request['totalWin'],
		// 	$request['balance'],
		// 	# $request['mode'], TODO: CHECK, NOT INCLUDED IN HASH ORDER
		// ));

		// # Check the request parameters and validate the hash.
		// if ($request['hash'] == $hash) {

		// 	$playerId = $request['userId'];

		// 	if ( ! $this->getGameUsernameByPlayerId($playerId)) {
		// 		return $this->generateReturn($request, array(
		// 			'RESULT' => self::ERROR,
		// 			'CODE' 	 => self::ERROR_UNKNOWNUSERID,
		// 		));
		// 	}

		// 	# Check if the transaction was already processed. If yes – just return success response (See details below).
		// 	if ( ! $this->CI->bs_game_logs->isUniqueIdAlreadyExists($request['transactionId'])) {

		// 		$amount  = $this->convertGameAmountToDB($request['balance']);
		// 		$balance = $this->CI->player_model->getMainWalletBalance($playerId);

		// 		# TODO: check balance
		// 		// if ($balance < $amount) {
		// 		// 	return $this->generateReturn($request, array(
		// 		// 		'RESULT' => self::ERROR,
		// 		// 		'CODE' 	 => self::ERROR_INSUFFICIENTFUNDS,
		// 		// 	));
		// 		// }

		// 		# update player balance
		// 		$this->CI->wallet_model->incMainWallet($playerId, $amount);

		// 		# save BSG transaction if processing was successful
		// 		$this->CI->bs_game_logs->insertBsGameLogs($request);

		// 	}

		// 	$balance = $this->CI->player_model->getMainWalletBalance($playerId);
		// 	$balance = $this->convertDBAmountToGame($balance);

		// 	# Return response to BSG.
		// 	$response = array(
		// 		'RESULT' => self::SUCCESS,
		// 		'BALANCE' => $balance, # current balance of player in cents on EC system side
		// 	);

		// } else {

		// 	$response = array(
		// 		'RESULT' => self::ERROR,
		// 		'CODE' 	 => self::ERROR_INVALIDHASH,
		// 	);

		// }

		// return $this->generateReturn($request, $response);
	}

	# START QUERY FORWARD GAME #################################################################################################################################
		public function queryForwardGame($playerName, $extra = null) {

			$this->CI->load->model(array('common_token','wallet_model'));
			$this->CI->load->helper('string');

			$playerId 	= $this->getPlayerIdFromUsername($playerName);
			$playerName = $this->getGameUsernameByPlayerUsername($playerName);
			$token 		= $this->CI->common_token->createTokenBy($playerId, 'player_id', $this->token_timeout);
			$currency 	= $this->currency;
			$balance 	= $this->convertDBAmountToGame(isset($extra['balance']) ? $extra['balance'] : $this->CI->player_model->getMainWalletBalance($playerId));
			$language 	= isset($extra['lang']) ? : $this->default_language;

			$context = array(
				'callback_obj' 		=> $this,
				'callback_method' 	=> 'processResultForQueryForwardGame',
				'playerId' 			=> $playerId,
				'playerName' 		=> $playerName,
				'balance' 			=> $balance,
				'token' 			=> $token,
			);

			$params = array_filter(array(
				'token' 		=> $token,
				'bankId' 		=> $this->bank_id,
				'mode' 			=> $extra['mode'],
				'gameId' 		=> $extra['game_id'],
				'currency' 		=> $currency,
				'balance' 		=> $balance, 						# Specifies balance in cents that should be transferred from EC to BSG into game session
				'lang' 			=> $language,
				// 'homeUrl' 	=> $this->homeUrl,					# OPTIONAL
				// 'cashier' 	=> $this->cashier,					# OPTIONAL
				// 'CDN' 		=> $this->CDN,						# OPTIONAL
			));

			return $this->callApi(self::API_queryForwardGame, $params, $context);
		}

		public function processResultForQueryForwardGame($params) {

			$responseResultId 	= $this->getResponseResultIdFromParams($params);
			$resultJson 		= $this->getResultJsonFromParams($params);
			$playerId 			= $this->getVariableFromContext($params, 'playerId');
			$playerName 		= $this->getVariableFromContext($params, 'playerName');
			$balance 			= $this->getVariableFromContext($params, 'balance');
			$token 				= $this->getVariableFromContext($params, 'token');
			$success 			= $this->processResultBoolean($responseResultId, $resultJson, $playerName);

			return array($success, array(
				'url' 			=> $resultJson['gameLoginUrl'],
				'playerId' 		=> $playerId,
				'playerName' 	=> $playerName,
				'balance' 		=> $balance,
				'token' 		=> $token,
			));

		}
	# END QUERY FORWARD GAME #################################################################################################################################



	# START IMPROVISED / UTILS ##############################################################################################################################################################################
		public function generateUrl($apiName, $params) {
			$apiUri = self::URI_MAP[$apiName];
			$params_string = http_build_query($params);
			$url = $this->url . $apiUri . "?" . $params_string;
			return $url;
		}

		public function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null) {
			// afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText, $extra, $resultObj);
			return array(false, null);
		}

		public function processResultBoolean($responseResultId, $resultJson, $playerName) {

			$success = ( ! empty($resultJson)) && $resultJson['error_code'] == self::SUCCESS_CODE;

			if ( ! $success) {
				$this->setResponseResultToError($responseResultId);
				$this->CI->utils->debug_log('AB got error', $responseResultId, 'playerName', $playerName, 'result', $resultJson);
			}

			return $success;
		}

		public function blockPlayer($playerName) {
			$playerName = $this->getGameUsernameByPlayerUsername($playerName);
			$result = $this->blockUsernameInDB($playerName);
			return array("success" => $result);
		}

		public function unblockPlayer($playerName) {
			$playerName = $this->getGameUsernameByPlayerUsername($playerName);
			$result = $this->unblockUsernameInDB($playerName);
			return array("success" => $result);
		}

		public function callback($method, $params) {
			try {
				$data = $this->{$method}($params);
			} catch (Exception $e) {
				return $this->generateReturn($params, array(
					'RESULT' => self::ERROR,
					'CODE' 	 => self::ERROR_INTERNALERROR,
				));
			}
			return $data;
		}

		public function hash($parameters) {
			$parameters[] = $this->pass_key;
			return md5(implode('', $parameters));
		}

		public function generateReturn($request, $response) {

			$data = array(
				'REQUEST' 	=> array(),
				'TIME' 		=> date('d M Y H:i:s'),
				'RESPONSE' 	=> $response,
			);

			foreach ($request as $key => $value) {
				$data['REQUEST'][strtoupper($key)] = $value;
			}

			return $data;
		}

		public function convertDBAmountToGame($amount) {
			return round($amount * self::CONVERSION_RATE);
		}

		public function convertGameAmountToDB($amount) {
			return round(floatval(floatval($amount) / self::CONVERSION_RATE), 2);
		}
	# END IMPROVISED / UTILS ##############################################################################################################################################################################



	# START NOT IMPLEMENTED ##############################################################################################################################################################################
		public function updatePlayerInfo($playerName, $infos) {
			return $this->returnUnimplemented();
		}

		public function queryPlayerDailyBalance($playerName, $playerId, $dateFrom = null, $dateTo = null) {
			return $this->returnUnimplemented();
		}

		public function queryGameRecords($dateFrom, $dateTo, $playerName = null) {
			return $this->returnUnimplemented();
		}

		public function checkLoginToken($playerName, $token) {
			return $this->returnUnimplemented();
		}

		public function totalBettingAmount($playerName, $dateFrom, $dateTo) {
			return $this->returnUnimplemented();
		}

		public function queryTransaction($transactionId, $extra) {
			return $this->returnUnimplemented();
		}

		public function login($playerName, $password = null) {
			return $this->returnUnimplemented();
		}

		public function checkLoginStatus($playerName) {
			return $this->returnUnimplemented();
		}

		public function queryPlayerInfo($playerName) {
			return $this->returnUnimplemented();
		}

		public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
			return $this->returnUnimplemented();
		}

		public function queryPlayerBalance($playerName) {
			return $this->returnUnimplemented();
		}

		public function depositToGame($playerName, $amount, $transfer_secure_id=null) {
			return $this->returnUnimplemented();
		}

		public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null) {
			return $this->returnUnimplemented();
		}

		public function transferCredit($playerName, $amount, $operFlag) {
			return $this->returnUnimplemented();
		}

		public function syncOriginalGameLogs($token) {
			return $this->returnUnimplemented();
		}

		public function syncMergeToGameLogs($token) {
			return $this->returnUnimplemented();
		}

		public function changePassword($playerName, $oldPassword, $newPassword) {
			return $this->returnUnimplemented();
		}

		public function logout($playerName, $password = null) {
			return $this->returnUnimplemented();
		}

		public function processResultForLogout($params) {
			return $this->returnUnimplemented();
		}
	# END NOT IMPLEMENTED ##############################################################################################################################################################################

}