<?php
require_once dirname(__FILE__) . '/game_api_impt.php';

/**
 * Defines general behavior of game API classes.
 *
 * General behaviors include:
 * * Check Player Balance
 * * Logout
 * * Check Login Toketn
 * * Deposit To Game
 * * Withdraw from Game
 * * Check Fund Transfer
 * * Check Forward Game
 * * Synchronize Original Game Logs
 * * Synchronize and Merge Imslots to Game Logs
 * * Generate URL
 * Behaviors not implemented:
 * * Update Player's information
 * * Check Player Daily Balance
 * * Check Game Records
 * * Check player's login status
 * * Check Transaction
 * * Check Player's informati on
 *
 * The functions implemented by child class:
 * * Populating game form parameters
 * * Handling callbacks
 *
 * @deprecated 2.0
 *
 *
 * @see Redirect redirect to game page
 *
 * @category Game_platform
 * @version 1.8.10
 * @copyright 2013-2022 tot
 */
class Game_api_imslots extends Abstract_game_api {

	protected $merchantname;
	protected $merchantcode;
	protected $currency;
	protected $api_url;
	protected $game_url;
	protected $method;

	const SUCCESS_CODE = 0;
	const PLAYER_DOES_NOT_EXISTS = 53;
	const PLAYER_EXISTS_BUT_NOT_ACTIVE = 250;
	const PLAYER_ALREADY_EXISTS = 54;
	const Playtech = 0;
	const Games_OS = 1;
	const IM_Wallet = 2;
	const METHOD_GET = 'GET';
	const METHOD_POST = 'POST';
	const METHOD_PUT = 'PUT';

	const URI_MAP = array(
		self::API_createPlayer => 'player/createplayer',
		self::API_isPlayerExist => 'player/checkplayerexists/membercode/{membercode}',
		self::API_queryPlayerBalance => 'account/getbalance/membercode/{membercode}/producttype/{producttype}',
		self::API_changePassword => 'player/resetpassword',
		self::API_logout => 'player/killsession',
		self::API_login => 'player/authenticateplayer',
		self::API_checkLoginToken => 'player/checkplayertoken/membercode/{membercode}/token/{token}/producttype/{producttype}',
		self::API_depositToGame => 'chip/createtransaction',
		self::API_withdrawFromGame => 'chip/createtransaction',
		self::API_checkFundTransfer => 'chip/checktransaction/membercode/{membercode}/externaltransactionid/{externaltransactionid}/producttype/{producttype}',
		self::API_syncGameRecords => 'report/getbetlog/startdate/{startdate}/enddate/{enddate}/page/{page}/pagesize/{pagesize}/producttype/{producttype}/currency/{currencycode}',
		self::API_queryForwardGame => 'game/launchgame',
		'launchfreegame' => 'game/launchfreegame',
	);

	public function __construct() {
		parent::__construct();
		$this->merchantname = $this->getSystemInfo('key');
		$this->merchantcode = $this->getSystemInfo('secret');
		$this->api_url = $this->getSystemInfo('url');
		$this->game_url = $this->getSystemInfo('game_url');
		$this->currency = $this->getSystemInfo('currency');
		$this->method = self::METHOD_POST;
	}

	public function getPlatformCode() {
		return IMSLOTS_API;
	}

	public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {

		parent::createPlayer($playerName, $playerId, $password, $email, $extra);

		$playerName = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForCreatePlayer',
			'playerName' => $playerName,
		);

		$params = array(
			'membercode' => $playerName,
			'password' => $password,
			'currency' => $this->currency,
		);

		return $this->callApi(self::API_createPlayer, $params, $context);

	}

	public function processResultForCreatePlayer($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);

		return array($success, $resultJson);

	}

	public function isPlayerExist($playerName) {

		$playerName = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForIsPlayerExist',
			'playerName' => $playerName,
		);

		$params = array(
			'membercode' => $playerName,
		);

		return $this->callApi(self::API_isPlayerExist, $params, $context);

	}

	public function processResultForIsPlayerExist($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');

		$result['exists'] = $resultJson['Code'] != self::PLAYER_DOES_NOT_EXISTS && $resultJson['Code'] != self::PLAYER_EXISTS_BUT_NOT_ACTIVE;

		return array(true, $result);

	}

	public function queryPlayerBalance($playerName) {

		$playerName = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryPlayerBalance',
			'playerName' => $playerName,
		);

		$params = array(
			'membercode' => $playerName,
			'producttype' => self::IM_Wallet,
		);

		return $this->callApi(self::API_queryPlayerBalance, $params, $context);

	}

	public function processResultForQueryPlayerBalance($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->convertResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);

		return array($success, array(
			'balance' => $success && isset($resultJson['Balance']) ? @floatval($resultJson['Balance']) : 0,
		));

	}

	public function changePassword($playerName, $oldPassword, $newPassword) {

		$playerName = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForChangePassword',
			'playerName' => $playerName,
			'password' => $newPassword,
		);

		$params = array(
			'membercode' => $playerName,
			'password' => $newPassword,
		);

		$this->method = self::METHOD_PUT;
		return $this->callApi(self::API_changePassword, $params, $context);

	}

	public function processResultForChangePassword($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->convertResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);

		if ($success) {

			$playerName = $this->getVariableFromContext($params, 'playerName');
			$playerId = $this->getPlayerIdInGameProviderAuth($playerName);

			if ($playerId) {
				$password = $this->getVariableFromContext($params, 'password');
				$this->updatePasswordForPlayer($playerId, $password);
			} else {
				$this->CI->utils->debug_log('cannot find player', $playerName);
			}

		}

		return array($success, $resultJson);

	}

	public function logout($playerName, $password = null) {

		$playerName = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForLogout',
			'playerName' => $playerName,
		);

		$params = array(
			'membercode' => $playerName,
			'producttype' => self::IM_Wallet,
		);

		$this->method = self::METHOD_PUT;
		return $this->callApi(self::API_logout, $params, $context);

	}

	public function processResultForLogout($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);

		return array($success, $resultJson);

	}

	public function login($playerName, $password = null) {

		$playerName = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForLogin',
			'playerName' => $playerName,
		);

		$params = array(
			'membercode' => $playerName,
			'password' => $password,
		);

		$this->method = self::METHOD_PUT;
		return $this->callApi(self::API_login, $params, $context);

	}

	public function processResultForLogin($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);

		return array($success, $resultJson);

	}

	public function checkLoginToken($playerName, $token) {

		$playerName = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForCheckLoginToken',
			'playerName' => $playerName,
		);

		$params = array(
			'membercode' => $playerName,
			'token' => $token,
			'producttype' => self::IM_Wallet,
		);

		return $this->callApi(self::API_checkLoginToken, $params, $context);

	}

	public function processResultForCheckLoginToken($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);

		return array($success, $resultJson);

	}

	public function depositToGame($playerName, $amount, $transfer_secure_id=null) {

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$externaltransactionid = random_string('unique');

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForDepositToGame',
			'playerName' => $playerName,
			'externaltransactionid' => $externaltransactionid,
		);

		$params = array(
			'membercode' => $gameUsername,
			'amount' => $this->CI->utils->roundCurrencyForShow($amount),
			'externaltransactionid' => $externaltransactionid,
			'producttype' => self::IM_Wallet,
		);

		$this->CI->utils->debug_log('deposit to game', $params);

		if ($this->getSystemInfo('check_last_transfer_request')) {
			$playerId = $this->getPlayerIdInPlayer($playerName);
			$this->checkLastFundTransferRequest($playerId, Transactions::TRANSFER_TO_SUB_WALLET_FROM_MAIN_WALLET, $amount);
		}

		return $this->callApi(self::API_depositToGame, $params, $context);

	}

	public function processResultForDepositToGame($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$externaltransactionid = $this->getVariableFromContext($params, 'externaltransactionid');
		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);

		if ( ! $success && $this->getSystemInfo('check_last_transfer_request')) {

			# WAIT FOR X SECONDS BEFORE TRYING AGAIN
			sleep(1);

			$result = $this->checkFundTransfer($playerName, $externaltransactionid);

			$success = $result['success'];
			unset($result['success']);

			return array($success, $result);
		}

		$resultJson['response_result_id'] = $responseResultId;

		return array($success, $resultJson);

	}

	public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null) {

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$externaltransactionid = 'WIT' . random_string('unique');

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForWithdrawFromGame',
			'playerName' => $playerName,
			'externaltransactionid' => $externaltransactionid,
		);

		$params = array(
			'membercode' => $gameUsername,
			'amount' => $this->CI->utils->roundCurrencyForShow(-$amount),
			'externaltransactionid' => $externaltransactionid,
			'producttype' => self::IM_Wallet,
		);

		if ($this->getSystemInfo('check_last_transfer_request')) {
			$playerId = $this->getPlayerIdInPlayer($playerName);
			$this->checkLastFundTransferRequest($playerId, Transactions::TRANSFER_FROM_SUB_WALLET_TO_MAIN_WALLET, - $amount);
		}

		return $this->callApi(self::API_withdrawFromGame, $params, $context);

	}

	public function processResultForWithdrawFromGame($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$externaltransactionid = $this->getVariableFromContext($params, 'externaltransactionid');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);

		if ( ! $success && $this->getSystemInfo('check_last_transfer_request')) {

			# WAIT FOR X SECONDS BEFORE TRYING AGAIN
			sleep(1);

			$result = $this->checkFundTransfer($playerName, $externaltransactionid);

			$success = $result['success'];
			unset($result['success']);

			return array($success, $result);
		}

		$resultJson['response_result_id'] = $responseResultId;

		return array($success, $resultJson);

	}

	# check last failed transaction before current transfer
	# only for past 6 hours
	# before send transfer
	# first check last failed one
	# we should add/minus main wallet if success
	# but still need check balance first, if no enough balance , record error
	# don't use lock , because already locked on transfer
	#
	# RETURN:
	# 	TRUE  - LAST FAILED TRANSACTION HAS BEEN CHANGED TO SUCCESSFUL
	# 	FALSE - NO FAILED TRANSACTION OR NO CHANGE ON LAST FAILED TRANSACTION
	public function checkLastFundTransferRequest($player_id, $transferRequestType, $current_amount) {

		$lastFailedTransferRequest = $this->CI->wallet_model->lastFailedTransferRequest($this->getPlatformCode(), $player_id, $transferRequestType);

		if ($lastFailedTransferRequest && isset($lastFailedTransferRequest['request_params']['externaltransactionid'])) {

			$player = $this->getPlayerInfo($player_id);
			$playerName = $player->username;

			$checkFundTransferResult = $this->checkFundTransfer($playerName, $lastFailedTransferRequest['request_params']['externaltransactionid']);

			# IF CHECK FUND TRANSFER RESULT IS SUCCESSFUL
			if ($checkFundTransferResult['success'] && $checkFundTransferResult['Code'] == self::SUCCESS_CODE) {

				$transfer_request_id		= $lastFailedTransferRequest['id'];
				$transfer_from 				= $lastFailedTransferRequest['from_wallet_type_id'];
				$transfer_to 				= $lastFailedTransferRequest['to_wallet_type_id'];
				$amount 					= $lastFailedTransferRequest['amount'];
				$external_transaction_id 	= isset($checkFundTransferResult['external_transaction_id']) ? $checkFundTransferResult['external_transaction_id'] : null;

				# IF FUND TRANSFER IS FROM MAIN WALLET THEN CHECK IF BALANCE IS SUFFICIENT
				if ($lastFailedTransferRequest['from_wallet_type_id'] == Wallet_model::MAIN_WALLET_ID) {

					# TODO: CHECK BALANCE + CURRENT_AMOUNT
					$is_balance_sufficient = $this->CI->utils->checkTargetBalance($player_id, Wallet_model::MAIN_WALLET_ID, $amount);

				} else {

					# ALWAYS TRUE IF FUND TRANSFER IS FROM SUBWALLET BECAUSE IT WILL NOT BECOME SUCCESSFUL IF WE DON'T HAVE SUFFICIENT BALANCE IN THEIR SYSTEM.
					$is_balance_sufficient = true;

				}

				if ($is_balance_sufficient) {

					# UPDATE SUBWALLET BALANCE FOR CORRECT BEFORE BALANCE
					$queryPlayerBalanceResult = $this->queryPlayerBalance($playerName);

					# ADD / SUBTRACT AMOUNT TO CURRENT BALANCE TO GET THE CORRECT BEFORE / AFTER BALANCE IN transferWalletAmount METHOD
					if ($transfer_from == Wallet_model::MAIN_WALLET_ID) {
						$balance = $queryPlayerBalanceResult['balance'] - $amount + $current_amount;
					} else {
						$balance = $queryPlayerBalanceResult['balance'] + $amount + $current_amount;
					}

					$this->updatePlayerSubwalletBalance($player_id, $balance);

					# TRANSACTION IS ROLLED BACK ON FAILURE SO WE NEED TO INSERT TRANSACTION AGAIN
					# THIS METHOD ALSO ADJUSTS THE BALANCE OF BOTH MAIN WALLET AND SUB WALLET
					$tranId = $this->CI->wallet_model->transferWalletAmount($this->getPlatformCode(), $player_id, $transfer_from, $transfer_to, $amount, $external_transaction_id);

					# UPDATE TRANSFER REQUEST FLAG
					$this->CI->wallet_model->setSuccessToTransferReqeust($transfer_request_id, $result['response_result_id']);

					$this->utils->debug_log('checkLastFundTransferRequest - transfer has been updated: transfer_request_id(' . $transfer_request_id . ')');

				} else {

					$this->utils->error_log('checkLastFundTransferRequest - insufficient balance: transfer_request_id(' . $transfer_request_id . ')');

				}

				return true;

			}

		}

		return false;
	}

	public function checkFundTransfer($playerName, $externaltransactionid) {

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForCheckFundTransfer',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername,
		);

		$params = array(
			'membercode' => $gameUsername,
			'externaltransactionid' => $externaltransactionid,
			'producttype' => self::IM_Wallet,
		);

		return $this->callApi(self::API_checkFundTransfer, $params, $context);

	}

	public function processResultForCheckFundTransfer($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);

		$resultJson['response_result_id'] = $responseResultId;

		return array($success, $resultJson);

	}

	public function queryForwardGame($playerName, $extra) {

		//load password
		$password = $this->getPasswordString($playerName);
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryForwardGame',
			'playerName' => $playerName,
		);

		$params = array(
			'gamecode' => $extra['gamecode'],
			'language' => $extra['language'],
			'ipaddress' => $this->CI->input->ip_address(),
			'producttype' => self::IM_Wallet,
		);

		if ($playerName) {
			$params['membercode'] = $playerName;
		}

		return $this->callApi((isset($extra['gameMode']) && $extra['gameMode'] == 'trial' ? 'launchfreegame' : self::API_queryForwardGame), $params, $context);

	}

	public function processResultForQueryForwardGame($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);

		return array($success, $resultJson);

	}

	public function importGameLogs($token) {

		$this->CI->load->model(array('imslots_game_logs', 'player_model'));

		$playerName = parent::getValueFromSyncInfo($token, 'playerName');
		$syncId = parent::getValueFromSyncInfo($token, 'syncId');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');

		$gamelogs_prefix = $this->getSystemInfo('prefix_game_log_file'); //sample hxhprod_
		//echo $prefix."prod_<pre>";print_r($startDate->format('Ymd_'));exit;
		$game_records_path = $this->getSystemInfo('game_records_path');
		$scanned_directory = array_diff(scandir($game_records_path), array('..', '.'));

		$success = true;
		foreach($scanned_directory as $key){
			if(is_dir($key)){
				continue; // if not file
			}
			$filename = current(explode(".", $key));//no extension
			// if (strpos($filename,$startDate->format('Ymd_')) == false) {
			//     continue; // continue if not in range in specific date
			// }
			$exploded = explode("_",str_replace($gamelogs_prefix,"",$filename)); // to get time format
			$startDate->format('YmdHis');
			if(!isset($exploded[1])){
				continue;
			}
			$toCompareDate = $exploded[0].$exploded[1]; //date of file
			$dateCompareStart = $startDate->format('YmdHis'); //date of request
			$dateCompareTo = $endDate->format('YmdHis'); //date of request
			if($toCompareDate >= $dateCompareStart &&$toCompareDate <= $dateCompareTo){ // if in range
				$fileFullDir = $game_records_path.$key; // get full directory of the file
				$file = file($fileFullDir); // get csv file
				$gameRecords = array_map('str_getcsv', $file);
				//echo "<pre>";print_r($gameRecords[0]);
				unset($gameRecords[0]); //unset first array element "header of csv"
				if ($gameRecords) {
					$availableRows = $this->CI->imslots_game_logs->getAvailableRows($gameRecords);
					$this->CI->utils->debug_log('availableRows', count($availableRows), 'gameRecords', count($gameRecords));
					if (isset($availableRows)) {
						foreach ($availableRows as $record) {
							$external_uniqueid = $record[6];
							$insertData = array();
							$insertData['Username'] = $record[2];
							$insertData['PlayerId'] = $this->getPlayerIdInGameProviderAuth($insertData['Username']);
							$insertData['StartTime'] = date("Y-m-d H:i:s", strtotime($record[0]));
							$insertData['EndTime'] = date("Y-m-d H:i:s", strtotime($record[1]));
							$insertData['MemberCode'] = $record[2];
							$insertData['MerchantName'] = $record[3];
							$insertData['ProviderName'] = $record[4];
							$insertData['GameCode'] = $record[5];
							$insertData['RoundId'] = $external_uniqueid;
							$insertData['CurrencyCode'] = $record[7];
							$insertData['BetAmount'] = $record[8];
							$insertData['WinAmount'] = $record[9];
							$insertData['GambleAmount'] = $record[10];
							$insertData['GambleWinAmount'] = $record[11];
							$insertData['ProgressiveShare'] = $record[12];
							$insertData['ProgressiveWin'] = $record[13];
							$insertData['GameName'] = $record[14];
							$insertData['IMGameCode'] = $record[15];
							$insertData['external_uniqueid'] = $external_uniqueid;
							$insertData['response_result_id'] = '';
							//if(!empty($insertData['PlayerId'])){
								$this->CI->imslots_game_logs->insertImslotsGameLogs($insertData);
							//}else{
								//$this->CI->utils->debug_log('ignore empty player id', $record['Username'], $insertData);
							//}
						}
					}
				}
				fclose($file);
			}
		}

		return array('success' => $success);

	}

	public function syncOriginalGameLogs($token) {

		$this->CI->load->model(array('imslots_game_logs', 'player_model'));

		$playerName = parent::getValueFromSyncInfo($token, 'playerName');
		$syncId = parent::getValueFromSyncInfo($token, 'syncId');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$gamelogs_prefix = $this->getSystemInfo('prefix_game_log_file'); // sample hxhprod_
		$game_records_path = $this->getSystemInfo('game_records_path');
		$scanned_directory = array_diff(scandir($game_records_path), array('..', '.'));
		$success = true;
		foreach($scanned_directory as $key){
			if(is_dir($key)){
				continue; // if not file or not in grange
			}
			$filename = current(explode(".", $key));//no extension
			$filenameDate = str_replace($gamelogs_prefix,"",$filename); // to get time format
			$dateCompareStart = $startDate->format('YmdHi'); //date of request
			$dateCompareTo = $endDate->format('YmdHi'); //date of request
			if($filenameDate >= $dateCompareStart &&$filenameDate <= $dateCompareTo){ // if in range
				$fileFullDir = $game_records_path.$key; // get full directory of the file
				$file = file($fileFullDir); // get csv file.
				$gameRecords = array_map('str_getcsv', $file);
				//echo "<pre>";print_r($gameRecords[0]);
				unset($gameRecords[0]); //unset first array element "header of csv"

				if ($gameRecords) {
					$availableRows = $this->CI->imslots_game_logs->getAvailableRows($gameRecords);
					$this->CI->utils->debug_log('availableRows', count($availableRows), 'gameRecords', count($gameRecords));
					if (isset($availableRows)) {
						foreach ($availableRows as $record) {
							$external_uniqueid = $record[6];
							$insertData = array();
							$insertData['external_uniqueid'] = $external_uniqueid;
							//$insertData['response_result_id'] = '';
							if($record[7]=="Bet"){ // BET
								$insertData['Username'] = $record[10];
								$insertData['PlayerId'] = $this->getPlayerIdInGameProviderAuth($insertData['Username']);
								$insertData['StartTime'] = date("Y-m-d H:i:s", strtotime($record[13]));
								$insertData['EndTime'] = date("Y-m-d H:i:s", strtotime($record[13]));
								$insertData['MemberCode'] = $record[10];
								$insertData['ProviderName'] = $record[2];
								$insertData['GameCode'] = $record[3];
								$insertData['RoundId'] = $external_uniqueid;
								$insertData['CurrencyCode'] = $this->currency;
								//$insertData['CurrencyCode'] = $record[7];
								$insertData['BetAmount'] = $record[11];
								$insertData['WinAmount'] = 0; // if bet only means loss all bet
								$insertData['GameName'] = $record[4];
								$insertData['IMGameCode'] = $record[3];
								$this->CI->imslots_game_logs->insertImslotsGameLogs($insertData);
							}else{ // RESULT
								$insertData['WinAmount'] = $record[11];
								$this->CI->imslots_game_logs->updateImslotsGameLogs($insertData);
							}

						}
					}
				}
				//is array, not file handler
				// fclose($file);
			}
		}

		return array('success' => $success);

	}

	public function syncMergeToGameLogs($token) {

		//return $this->returnUnimplemented();

		$this->CI->load->model(array('game_logs', 'player_model', 'imslots_game_logs'));

		$dateTimeFrom = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$dateTimeTo = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

		$result = $this->CI->imslots_game_logs->getImslotsGameLogStatistics($dateTimeFrom->format('Y-m-d H:i:s'), $dateTimeTo->format('Y-m-d H:i:s'));
		$cnt = 0;
		if ($result) {

			$unknownGame = $this->getUnknownGame();

			foreach ($result as $imslots_data) {
				$player_id = $imslots_data->PlayerId;

				$cnt++;

				$bet_amount = $imslots_data->bet_amount;
				$result_amount = $imslots_data->result_amount - $bet_amount;

				$game_description_id = $imslots_data->game_description_id;
				$game_type_id = $imslots_data->game_type_id;

				if (empty($game_description_id)) {
					$game_description_id = $unknownGame->id;
					$game_type_id = $unknownGame->game_type_id;
				}
				$this->syncGameLogs(
					$game_type_id,
					$game_description_id,
					$imslots_data->game_code,
					$imslots_data->game_type,
					$imslots_data->game,
					$player_id,
					$imslots_data->Username,
					$bet_amount,
					$result_amount,
					null, # win_amount
					null, # loss_amount
					null, # after_balance
					0, # has_both_side
					$imslots_data->external_uniqueid,
					$imslots_data->date_created,
					$imslots_data->date_created,
					null# response_result_id
				);

			}
		}
		$this->CI->utils->debug_log('syncMergeToGameLogs monitor', 'count', $cnt);
		return array('success' => true);

	}

	protected function getResultAmount($row) {
		$bet = $row->Bet;
		$win = $row->Win;
		$progWin = $row->ProgressiveWin;
		return $win + $progWin - $bet;
	}

	protected function getAfterBalance($row) {
		$startBal = $row->Balance;
		// $bet = $row->Bet;
		// $win = $row->Win;
		// return $startBal - $bet + $win;
		return $startBal;
	}

	protected function getGameDescriptionInfo($row, $unknownGame, $gameDescIdMap) {
		$game_description_id = null;
		if (isset($row->game_description_id)) {
			$game_description_id = $row->game_description_id;
		}
		$game_type_id = null;
		if (isset($row->game_type_id)) {
			$game_type_id = $row->game_type_id;
		}

		$externalGameId = $row->gameshortcode;
		$extra = array('game_code' => $row->gameshortcode);
		// if (empty($game_description_id)) {
		// 	//search game_description_id by code
		// 	if (isset($gameDescIdMap[$externalGameId]) && !empty($gameDescIdMap[$externalGameId])) {
		// 		$game_description_id = $gameDescIdMap[$externalGameId]['game_description_id'];
		// 		$game_type_id = $gameDescIdMap[$externalGameId]['game_type_id'];
		// 		if ($gameDescIdMap[$externalGameId]['void_bet'] == 1) {
		// 			return array(null, null);
		// 		}
		// 	}
		// }

		return $this->processUnknownGame(
			$game_description_id, $game_type_id,
			$row->GameName, $row->GameType, $externalGameId, $extra,
			$unknownGame);
	}

	public function generateUrl($apiName, $params) {

		$apiUri = self::URI_MAP[$apiName];
		$url = $this->api_url . $apiUri;

		preg_match_all("#\{([^\}]+)\}#", $url, $matches, PREG_SET_ORDER);
		foreach ($matches as $match) {
			$url = str_replace($match[0], $params[$match[1]], $url);
			unset($params[$match[1]]);
		}

		if (!$params) {
			$this->method = self::METHOD_GET;
		}

		$this->CI->utils->debug_log('apiName', $apiName, 'url', $url, 'params', $params);

		return $url;
	}

	function getHttpHeaders($params) {
		return array(
			"merchantname" => $this->merchantname,
			"merchantcode" => $this->merchantcode,
		);
	}

	protected function customHttpCall($ch, $params) {

		if ($this->method == self::METHOD_POST || $this->method == self::METHOD_PUT) {
			if ($this->method == self::METHOD_PUT) {
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, self::METHOD_PUT);
			} else {
				curl_setopt($ch, CURLOPT_POST, TRUE);
			}
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params, JSON_PRETTY_PRINT));
		}

		$this->method = self::METHOD_POST;

	}

	public function updatePlayerInfo($playerName, $infos) {
		return $this->returnUnimplemented();
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

	// public function totalBettingAmount($playerName, $dateFrom, $dateTo) {
	// 	return $this->returnUnimplemented();
	// }

	public function queryTransaction($transactionId, $extra) {
		return $this->returnUnimplemented();
	}

	public function queryPlayerInfo($playerName) {
		return $this->returnUnimplemented();
	}

	public function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null) {
		return $this->returnUnimplemented();
	}

	public function processResultBoolean($responseResultId, $resultJson, $playerName) {
		$success = $resultJson['Code'] == self::SUCCESS_CODE;
		if (!$success) {
			$this->utils->error_log('return error', $resultJson, 'playerName', $playerName, 'responseResultId', $responseResultId);
		}
		return $success;
	}

	public function gameAmountToDB($amount) {
		$amount = floatval($amount);
		return round($amount, 2);
	}

	public function serverTimeToGameTime($dateTimeStr) {
		if (is_object($dateTimeStr) && $dateTimeStr instanceof DateTime) {
			return rawurlencode($dateTimeStr->format('Y-m-d H.i.s'));
		} else {
			$modify = $this->getServerTimeToGameTime();
			return date('Y-m-d H.i.s', strtotime($this->utils->modifyDateTime($dateTimeStr, $modify)));
		}
	}

}