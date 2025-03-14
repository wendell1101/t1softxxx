<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

/**
 * Defines general behavior of game API classes.
 *
 * General behaviors include:
 * * Getting platform code
 * * Generate URL
 * * Get Player's Session ID
 * * Create Player
 * * Check Player if exist
 * * Check Player Balance
 * * Change Password
 * * Login/Logout player
 * * Check Player login token
 * * Deposit To game
 * * Withdraw from Game
 * * Check Last  Fund Transfer Request
 * * Check Fund Transfer
 * * Check Forward Game
 * * Synchronize Original Game Logs
 * * Get Game Description Information
 * Behaviors not implemented
 * * Update Player's information
 * * Check Player Daily Balance
 * * Check Game records
 * * Check player's login status
 * * Check Transaction
 *
 * The functions implemented by child class:
 * * Populating game form parameters
 * * Handling callbacks
 *
 * @see Redirect redirect to game page
 *
 * @category Game_platform
 * @version 1.8.10
 * @copyright 2013-2022 tot
 * @since 3.1.00.1 add block/unblock
 */
class Game_api_impt extends Abstract_game_api {

	protected $merchantname;
	protected $merchantcode;
	protected $currency;
	protected $api_url;
	protected $game_url;
	protected $method;
	protected $transaction_status_declined;
	protected $transaction_status_approved;
	public $transfer_retry_times;
	protected $check_current_transfer_request;
	protected $launch_game_on_player;
	protected $sync_time_interval;

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

	const START_PAGE = 1;
	const ITEM_PER_PAGE = 3000;

	const DEFAULT_TRANSACTION_STATUS_APPROVED='Approved';
	const DEFAULT_TRANSACTION_STATUS_DECLINED='Declined';

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
		self::API_queryForwardGame => 'game/launchgame',
		self::API_syncGameRecords => 'report/getbetlog/startdate/{startdate}/enddate/{enddate}/page/{page}/pagesize/{pagesize}/producttype/{producttype}/currency/{currencycode}',
		self::API_blockPlayer=>'player/freezeplayer/membercode/{membercode}/frozen/{frozenstatus}',
		self::API_unblockPlayer=>'player/freezeplayer/membercode/{membercode}/frozen/{frozenstatus}',
	);

	public function __construct() {
		parent::__construct();
		$this->merchantname = $this->getSystemInfo('key');
		$this->merchantcode = $this->getSystemInfo('secret');
		$this->api_url = $this->getSystemInfo('url');
		$this->game_url = $this->getSystemInfo('game_url');
		$this->currency = $this->getSystemInfo('currency');
		$this->method = self::METHOD_POST;
		$this->add_progressive_bet = $this->getSystemInfo('add_progressive_bet', false);
		$this->add_progressive_win = $this->getSystemInfo('add_progressive_win', true);
		$this->perPageSize=$this->getSystemInfo('per_page_size', self::ITEM_PER_PAGE);

		$this->sync_time_interval = $this->getSystemInfo('sync_time_interval', '+10 minutes');

		$this->launch_game_on_player = $this->getSystemInfo('launch_game_on_player', true);
		$this->check_current_transfer_request = $this->getSystemInfo('check_current_transfer_request', true);
		$this->transfer_retry_times = $this->getSystemInfo('transfer_retry_times', 3);

		$this->transaction_status_approved= $this->getSystemInfo('transaction_status_approved', self::DEFAULT_TRANSACTION_STATUS_APPROVED);
		$this->transaction_status_declined= $this->getSystemInfo('transaction_status_declined', self::DEFAULT_TRANSACTION_STATUS_DECLINED);

		$this->status_map=[
			$this->transaction_status_approved => self::COMMON_TRANSACTION_STATUS_APPROVED,
			$this->transaction_status_declined => self::COMMON_TRANSACTION_STATUS_DECLINED,
		];

        //don't support
        $this->is_enabled_direct_launcher_url=$this->getSystemInfo('is_enabled_direct_launcher_url', false);
	}

	protected function convertStatus($status){

		if(isset($this->status_map[$status])){
			return $this->status_map[$status];
		}else{
			return self::COMMON_TRANSACTION_STATUS_PROCESSING;
		}

	}

	public function getPlatformCode() {
		return IMPT_API;
	}

	/**
	 * overview : get game time to server time
	 *
	 * @return string
	 */
	// public function getGameTimeToServerTime() {
	// 	return '+8 hours';
	// }

	/**
	 * overview : get server time to game time
	 *
	 * @return string
	 */
	// public function getServerTimeToGameTime() {
	// 	return '-8 hours';
	// }

	public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {

		parent::createPlayer($playerName, $playerId, $password, $email, $extra);

		$usernameWithoutPrefix=$playerName;
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForCreatePlayer',
			'playerName' => $playerName,
			'usernameWithoutPrefix'=>$usernameWithoutPrefix,
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
			'producttype' => self::Playtech,
		);

		return $this->callApi(self::API_queryPlayerBalance, $params, $context);

	}

	public function processResultForQueryPlayerBalance($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->convertResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);

		return array($success, array(
			'balance' => $success && isset($resultJson['Balance']) ? $this->gameAmountToDB($resultJson['Balance']) : 0,
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
			'producttype' => self::Playtech,
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
			'producttype' => self::Playtech,
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
		$amount = $this->CI->utils->roundCurrencyForShow($amount);

		// $externaltransactionid = random_string('unique');
		$externaltransactionid = empty($transfer_secure_id) ? 'D' . random_string('unique') : $transfer_secure_id ;

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForDepositToGame',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername,
			'amount' => $amount,
			'externaltransactionid' => $externaltransactionid,
            //mock testing
            // 'is_timeout_mock' => $this->getSystemInfo('is_timeout_mock', false),
            //for this api
            // 'enabled_guess_success_for_curl_errno_on_this_api' => $this->enabled_guess_success_for_curl_errno_on_this_api,
		);

		$params = array(
			'membercode' => $gameUsername,
			'amount' => $this->dBtoGameAmount($amount),
			'externaltransactionid' => $externaltransactionid,
			'producttype' => self::Playtech,
		);

		$this->CI->utils->debug_log('deposit to game', $params);

		// if ($this->check_last_transfer_request) {
		// 	$playerId = $this->getPlayerIdInPlayer($playerName);
		// 	$this->checkLastFundTransferRequest($playerId, Transactions::TRANSFER_TO_SUB_WALLET_FROM_MAIN_WALLET,
		// 		$amount, 'externaltransactionid');
		// }

		return $this->callApi(self::API_depositToGame, $params, $context);

	}

	public function processResultForDepositToGame($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$amount = $this->getVariableFromContext($params, 'amount');
		$externaltransactionid = $this->getVariableFromContext($params, 'externaltransactionid');
		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);

		$success= $success && @$resultJson['Status']==$this->transaction_status_approved;

		if ( ! $success && $this->check_current_transfer_request) {

			$this->CI->utils->debug_log($playerName , 'status of result', $resultJson);

			$cnt = 0;

			do {

				$result = $this->queryTransaction($externaltransactionid, ['playerName' => $playerName]);

				if ($result['success'] && @$result['transaction_success'] ) {
			    	$this->CI->utils->debug_log('============= success deposit try queryTransaction', $playerName, $amount, $externaltransactionid, $result);
			    	$success = true;
			    	break;
				} else {
			    	$this->CI->utils->error_log('============= error deposit try queryTransaction', $playerName, $amount, $externaltransactionid, $result);
				}

				sleep(1);

			} while($this->transfer_retry_times > ++$cnt);


			if ( ! $result['success']) {
			    $this->CI->utils->debug_log('============= convert success to true if still error when deposit', $playerName, $amount, $externaltransactionid, $result);
			    $result['success'] = true;
			    $success = true;
			}

			$resultJson = $result;

		}

		$result=['response_result_id' => $responseResultId, 'external_transaction_id'=> $externaltransactionid];
		$result['status']=$this->convertStatus(@$resultJson['Status']);

		if ($success) {
		    $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
		    if ($playerId) {
		        $playerBalance = $this->queryPlayerBalance($playerName);
		        $this->CI->utils->debug_log('============= IMPT QUERY_PLAYER_BALANCE deposit ######### ', $playerBalance);

		        $afterBalance = 0;
		        if ($playerBalance && $playerBalance['success']) {
		            $afterBalance = $playerBalance['balance'];
		            $this->CI->utils->debug_log('============= IMPT AFTER BALANCE FROM API deposit ######### ', $afterBalance);
		        } else {
		            $rlt = $this->CI->wallet_model->getSubWalletBy($playerId, $this->getPlatformCode());
		            $afterBalance = $rlt->totalBalanceAmount + $amount;
		            $this->CI->utils->debug_log('============= IMPT AFTER BALANCE FROM WALLET deposit ######### ', $afterBalance);
		        }

		        $this->insertTransactionToGameLogs($playerId, $gameUsername, $afterBalance, $amount, $responseResultId, $this->transTypeMainWalletToSubWallet());

		    } else {
		        $this->CI->utils->debug_log('error', '=============== cannot get player id from '.$gameUsername.' getPlayerIdInGameProviderAuth');
		    }
		}

		unset($resultJson['success']);

		return array($success, $result);

	}

	public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null) {

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$amount = $this->CI->utils->roundCurrencyForShow(-$amount);

		$externaltransactionid = empty($transfer_secure_id) ? 'W' . random_string('unique') : $transfer_secure_id ;

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForWithdrawFromGame',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername,
			'amount' => $amount,
			'externaltransactionid' => $externaltransactionid,
			//only deposit
            //mock testing
            // 'is_timeout_mock' => $this->getSystemInfo('is_timeout_mock', false),
            //for this api
            // 'enabled_guess_success_for_curl_errno_on_this_api' => $this->enabled_guess_success_for_curl_errno_on_this_api,
		);

		$params = array(
			'membercode' => $gameUsername,
			'amount' => $this->dBtoGameAmount($amount),
			'externaltransactionid' => $externaltransactionid,
			'producttype' => self::Playtech,
		);

		$this->CI->utils->debug_log('withdraw to game', $params);

		// if ($this->check_last_transfer_request) {
		// 	$playerId = $this->getPlayerIdInPlayer($playerName);
		// 	$this->checkLastFundTransferRequest($playerId, Transactions::TRANSFER_FROM_SUB_WALLET_TO_MAIN_WALLET,
		// 	 - $amount, 'externaltransactionid');
		// }

		return $this->callApi(self::API_withdrawFromGame, $params, $context);

	}

	public function processResultForWithdrawFromGame($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$amount = $this->getVariableFromContext($params, 'amount');
		$externaltransactionid = $this->getVariableFromContext($params, 'externaltransactionid');
		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);

		$success= $success && @$resultJson['Status']==$this->transaction_status_approved;

		if ( ! $success && $this->check_current_transfer_request) {

			$this->CI->utils->debug_log($playerName , 'status of result', $resultJson);

			$cnt = 0;

			do {

				$result = $this->queryTransaction($externaltransactionid, ['playerName' => $playerName]);

				if ($result['success'] && @$result['transaction_success']) {
			    	$this->CI->utils->debug_log('============= success withdraw try queryTransaction', $playerName, $amount, $externaltransactionid, $result);
			    	$success = true;
			    	break;
				} else {
			    	$this->CI->utils->error_log('============= error withdraw try queryTransaction', $playerName, $amount, $externaltransactionid, $result);
				}

				sleep(1);

			} while($this->transfer_retry_times > ++$cnt);


			if ( ! $result['success']) {
			    $this->CI->utils->debug_log('============= convert success to true if still error when withdraw', $playerName, $amount, $externaltransactionid, $result);
			    $result['success'] = true;
			    $success = true;
			}

			$resultJson = $result;

		}

		$result=['response_result_id' => $responseResultId];
		$result['status']=$this->convertStatus(@$resultJson['Status']);

		if ($success) {
		    $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
		    if ($playerId) {
		        $playerBalance = $this->queryPlayerBalance($playerName);
		        $this->CI->utils->debug_log('============= IMPT QUERY_PLAYER_BALANCE withdraw ######### ', $playerBalance);

		        $afterBalance = 0;
		        if ($playerBalance && $playerBalance['success']) {
		            $afterBalance = $playerBalance['balance'];
		            $this->CI->utils->debug_log('============= IMPT AFTER BALANCE FROM API withdraw ######### ', $afterBalance);
		        } else {
		            $rlt = $this->CI->wallet_model->getSubWalletBy($playerId, $this->getPlatformCode());
		            $afterBalance = $rlt->totalBalanceAmount;
		            $this->CI->utils->debug_log('============= IMPT AFTER BALANCE FROM WALLET withdraw ######### ', $afterBalance);
		        }

		        $this->insertTransactionToGameLogs($playerId, $gameUsername, $afterBalance, $amount, $responseResultId, $this->transTypeSubWalletToMainWallet());

		    } else {
		        $this->CI->utils->debug_log('error', '=============== cannot get player id from '.$gameUsername.' getPlayerIdInGameProviderAuth');
		    }
		}

		unset($resultJson['success']);

		return array($success, $result);

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
			if ($checkFundTransferResult['success'] && @$checkFundTransferResult['transaction_success'] ) { // && $checkFundTransferResult['Code'] == self::SUCCESS_CODE) {

				$transfer_request_id		= $lastFailedTransferRequest['id'];
				$transfer_from 				= $lastFailedTransferRequest['from_wallet_type_id'];
				$transfer_to 				= $lastFailedTransferRequest['to_wallet_type_id'];
				$amount 					= $this->gameAmountToDB($lastFailedTransferRequest['amount']);
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

		$this->utils->debug_log('checkFundTransfer', $playerName, $externaltransactionid);

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
			'producttype' => self::Playtech,
		);

		return $this->callApi(self::API_checkFundTransfer, $params, $context);

	}

	public function processResultForCheckFundTransfer($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);

		if(isset($resultJson['success'])){
			unset($resultJson['success']);
		}

		// $success= $success && @$resultJson['Status']==$this->transaction_status_approved;

		// $resultJson['response_result_id'] = $responseResultId;
		$result=['response_result_id' => $responseResultId];
		$result['status']=$this->convertStatus(@$resultJson['Status']);
		$result['transaction_success']=$result['status']==self::COMMON_TRANSACTION_STATUS_APPROVED;

		return array($success, $result);

	}

	public function generateGotoUri($playerName, $extra){

		return '/iframe_module/goto_imptgame/default/'.$extra['gamecode'].'/'.$extra['gamemode'].'/'.$extra['mobile'];

	}

	public function queryForwardGame($playerName, $extra) {

		$nextUrl=$this->generateGotoUri($playerName, $extra);
		$result=$this->forwardToWhiteDomain($playerName, $nextUrl);
		if($result['success']){
			return $result;
		}

		//load password
		// $password = $this->getPasswordString($playerName);
		// $playerName = $this->getGameUsernameByPlayerUsername($playerName);

		// $resultJson['language'] = $extra['language'];
		// $resultJson['host'] = $this->game_url;
		// $resultJson['url'] = $this->game_url . '/casinoclient.html?' . http_build_query(array(
		// 	'game' => $params['params']['gamecode'],
		// 	'language' => $params['params']['language'],
		// 	'nolobby' => 1,
		// ));

		$this->CI->load->model(['external_system']);

		$success=true;
		$platformName = $this->CI->external_system->getNameById($this->getPlatformCode());
		$gameCode=$extra['gamecode'];
		$ptLang=$extra['language'];
		$api_play_pt = $this->getSystemInfo('game_url');
		$api_play_js = $this->getSystemInfo('api_play_js','https://login.longsnake88.com/jswrapper/integration.js.php?casino=longsnake88');
		$player_url = $this->CI->utils->getSystemUrl('player');

		$result = [
			'success'=>$success,
			'launch_game_on_player'=>$this->launch_game_on_player,
			'platformName' => $platformName,
			'game_code' => $gameCode,
			'lang' => $ptLang,
			'api_play_pt' => $api_play_pt,
			'api_play_js' => $api_play_js,
			'player_url' => $player_url,
			'mobile'=>$extra['mobile'],
			'mobile_systemId'=>$this->getSystemInfo('mobile_systemId'),
			'mobile_launcher'=>rtrim($this->getSystemInfo('mobile_launcher'), '/').'/',
			'v'=>PRODUCTION_VERSION,
		];

		return $result;

		// return array('success' => true, 'url' => $this->game_url . '/casinoclient.html?' . http_build_query(array(
		// 	'game' => $extra['gamecode'],
		// 	'language' => $extra['language'],
		// 	'nolobby' => 1,
		// )), 'host' => $this->game_url, 'language' => $extra['language'],
		// 	'PlaytechUserName' => $playerName, 'PlaytechPassword' => $password);

		// $context = array(
		// 	'callback_obj' => $this,
		// 	'callback_method' => 'processResultForQueryForwardGame',
		// 	'playerName' => $playerName,
		// );

		// $params = array(
		// 	'membercode' => $playerName,
		// 	'gamecode' => $extra['game_code'],
		// 	'language' => $extra['language'],
		// 	'ipaddress' => $extra['ip_address'],
		// 	'producttype' => self::Playtech,
		// );

		// return $this->callApi(self::API_queryForwardGame, $params, $context);

	}

	// public function processResultForQueryForwardGame($params) {

	// 	$responseResultId = $this->getResponseResultIdFromParams($params);
	// 	$resultJson = $this->getResultJsonFromParams($params);
	// 	$playerName = $this->getVariableFromContext($params, 'playerName');
	// 	$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);

	// 	if ($success) {
	// 		$resultJson['language'] = $params['params']['language'];
	// 		$resultJson['host'] = $this->game_url;
	// 		$resultJson['url'] = $this->game_url . '/casinoclient.html?' . http_build_query(array(
	// 			'game' => $params['params']['gamecode'],
	// 			'language' => $params['params']['language'],
	// 			'nolobby' => 1,
	// 		));
	// 	}

	// 	return array($success, $resultJson);

	// }

	// function getSyncTimeInterval(){

	// 	$interval = $this->getSystemInfo('sync_time_interval');

	// 	if($interval){
	// 		return $interval;
	// 	}
	// 	return '+30 minutes';
	// }

	public function syncOriginalGameLogs($token) {

		// $this->CI->utils->debug_log('-----------IMPT INTERVAL-------', $this->sync_time_interval);

		$playerName = parent::getValueFromSyncInfo($token, 'playerName');
		$syncId = parent::getValueFromSyncInfo($token, 'syncId');
		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

		$startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
    	$endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));
    	$startDate->modify($this->getDatetimeAdjust());
		//observer the date format
		// $startDate = $startDate->format('Y-m-d H:i:s');
		// $endDate = $endDate->format('Y-m-d H:i:s');


		// $startDate = new DateTime($startDate);
		// $endDate = new DateTime($endDate);

		// $this->CI->utils->debug_log('-----------IMPT INTERVAL-------', $this->sync_time_interval);

		$this->CI->utils->debug_log('syncOriginalGameLogs: '.$this->getPlatformCode() , 'from', $startDate, 'to', $endDate, 'sync_id', $syncId, 'playerName', $playerName, 'sync_time_interval' ,$this->sync_time_interval);

		// $queryDateTimeStart = $startDate->format('Y-m-d H:i:s');
		// $queryDateTimeEnd = $startDate->modify($this->sync_time_interval)->format('Y-m-d H:i:s');
	 //    $queryDateTimeMax = $endDate->format('Y-m-d H:i:s');

		$count = 0;
		$real_count = 0;
		$sum = 0;
		$success = true;

		$queryDateTimeStart = $startDate->format('Y-m-d H:i:s');
		$queryDateTimeEnd = $startDate->modify($this->sync_time_interval)->format('Y-m-d H:i:s');
	    $queryDateTimeMax = $endDate->format('Y-m-d H:i:s');

		while ($queryDateTimeMax  > $queryDateTimeStart) {

			$done = false;
			$currentPage = self::START_PAGE;

			while (!$done) {

				$dont_save_response_in_api = $this->getConfig('dont_save_response_in_api');

				$rlt = null;

				$context = array(
					'callback_obj' => $this,
					'callback_method' => 'processResultForSyncOriginalGameLogs',
					'playerName' => $playerName,
					'syncId' => $syncId,
					// 'dont_save_response_in_api' => $dont_save_response_in_api,
				);

				$startDateParam=new DateTime($queryDateTimeStart);
				if($queryDateTimeEnd>$queryDateTimeMax){
					$endDateParam=new DateTime($queryDateTimeMax);
				}else{
					$endDateParam=new DateTime($queryDateTimeEnd);
				}

				if (!empty($playerName)) {

					//API_syncGameRecordsByPlayer
					$params = array(
						'startdate' => rawurlencode($startDateParam->format('Y-m-d H.i.s')),
						'enddate' => rawurlencode($endDateParam->format('Y-m-d H.i.s')),
						'currencycode' => $this->currency,
						'page' => $currentPage,
						'pagesize' => $this->perPageSize,
						'membercode' => $playerName,
						'producttype' => self::Playtech,
					);

					$rlt = $this->callApi(self::API_syncGameRecordsByPlayer, $params, $context);

				} else {

					$params = array(
						'startdate' => rawurlencode($startDateParam->format('Y-m-d H.i.s')),
						'enddate' => rawurlencode($endDateParam->format('Y-m-d H.i.s')),
						'currencycode' => $this->currency,
						'page' => $currentPage,
						'pagesize' => $this->perPageSize,
						'producttype' => self::Playtech,
					);

					$rlt = $this->callApi(self::API_syncGameRecords, $params, $context);

				}

				$done = true;
				if ($rlt) {
					$success = $rlt['success'];
				}

				if ($rlt && $rlt['success']) {
					$currentPage = $rlt['currentPage'];
					$total_pages = $rlt['totalPages'];
					//next page
					$currentPage += 1;

					$done = $currentPage > $total_pages;
					$count += $rlt['count'];
					$sum += $rlt['sum'];
					$real_count += $rlt['real_count'];
					$this->CI->utils->debug_log($params, 'currentPage', $currentPage, 'total_pages', $total_pages, 'done', $done, 'result', $rlt);
				}
			}

			$queryDateTimeStart = $endDateParam->format('Y-m-d H:i:s');
	    	$queryDateTimeEnd  = (new DateTime($queryDateTimeStart))->modify($this->sync_time_interval)->format('Y-m-d H:i:s');

		}//end while outer
		$this->CI->utils->debug_log('queryDateTimeStart', $queryDateTimeStart, 'queryDateTimeEnd', $queryDateTimeEnd,
			'queryDateTimeMax', $queryDateTimeMax);

		// $context = array(
		// 	'callback_obj' => $this,
		// 	'callback_method' => 'processResultForSyncOriginalGameLogs',
		// 	'playerName' => $playerName,
		// );

		// $currentPage = self::START_PAGE;
		// $total_pages = 0;
		// do {
			// $dont_save_response_in_api = $this->getConfig('dont_save_response_in_api');

			// $params = array(
			// 	'startdate' => $startDate,
			// 	'enddate' => $endDate,
			// 	'currencycode' => 'CNY',
			// 	'page' => $currentPage,
			// 	'pagesize' => self::ITEM_PER_PAGE,
			// 	'producttype' => self::Playtech,
			// );

			// if (!empty($playerName)) {
			// 	$params['membercode'] = $playerName;
			// }

			//try post
			// $this->method = self::METHOD_POST;

			// $result = $this->callApi(self::API_syncGameRecords, $params, $context);
			// $success = $result['success'];
			// if ($success) {

			// 	$total_pages = $result['totalPages'];
			// 	$currentPage++;
			// 	$cnt += $result['totalCount'];
			// 	$sum += $result['sum'];

			// 	$goNext = $currentPage <= $total_pages;

			// 	$this->CI->utils->debug_log('page', $currentPage, 'total_pages', $total_pages,
			// 		'done', $goNext, 'cnt', $cnt, 'sum', $sum, 'result', $result);

			// } else {
			// 	$goNext = false;
			// }
		// } while ($success && $goNext);

		return array("success" => $success, "count"=>$count, "sum"=> $sum, "real_count"=> $real_count);

	}

	protected function isInvalidRow($row) {
		//remove ProgressiveBet
		return $row['Bet'] == '0' && $row['Win'] == '0' && $row['ProgressiveWin'] == '0'; //$row['PROGRESSIVEBET'] == '0' &&
	}

	public function processResultForSyncOriginalGameLogs($params) {
		$this->CI->load->model('impt_game_logs');

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);

		$result = null;
		$count=0;
		$real_count=0;
		$sum = 0;

		# TODO: TEST
		# $resultJson['Result'] = json_decode('[{"PlayerName": "test2235180991","WindowCode": "0","GameId": "1","GameCode": "18556515376","GameType": "Fixed Odds","GameName": "Monkey Thunderbolt (mnkt)","SessionId": "89292371","Bet": "0","Win": "0","ProgressiveBet": "0","ProgressiveWin": "0","Balance": "5","CurrentBet": "0","GameDate": "2016-04-20 09:58:12","LiveNetwork": "","RNum": "1"}]', true);
		if ($success) {

			$gameRecords = $resultJson['Result'];
			if ($gameRecords) {
				$real_count=count($gameRecords);

				$availableRows = $this->CI->impt_game_logs->getAvailableRows($gameRecords);
				$this->CI->utils->debug_log('availableRows', count($availableRows), 'gameRecords', count($gameRecords));

				foreach ($availableRows as $record) {
					if (!$this->isInvalidRow($record)) {

						$count++;

						preg_match_all("/\(([^()]+)\)/", $record['GameName'], $matches);
						$gameshortcode = $matches[1][0];
						$uniqueId = $record['GameCode'];
						// $external_uniqueid = $gameshortcode . '-' . $record['SessionId'] . '-' . $record['GameDate'] . '-' . $record['WindowCode'];
						$external_uniqueid = $uniqueId;

						$record['GameDate'] = $this->gameTimeToServerTime($record['GameDate']);
						$record['response_result_id'] = $responseResultId;
						$record['uniqueid'] = $uniqueId;
						$record['external_uniqueid'] = $external_uniqueid;
						$record['gameshortcode'] = $gameshortcode;

						$sum += floatval($record['Bet']);
						$this->CI->impt_game_logs->insertImptGameLogs($record);
					}
				}
			}

			$this->utils->debug_log($resultJson['Pagination']);

			$result['totalPages'] = @$resultJson['Pagination']['TotalPage'];
			$result['currentPage'] = @$resultJson['Pagination']['CurrentPage'];
			$result['itemsPerPage'] = @$resultJson['Pagination']['ItemsPerPage'];
			$result['totalCount'] = @$resultJson['Pagination']['TotalCount'];
		}

		$result['count'] = $count;
		$result['sum'] = $sum;
		$result['real_count']= $real_count;

		return array($success, $result);

	}

	public function syncMergeToGameLogs($token) {

		$this->CI->load->model(array('game_logs', 'player_model', 'impt_game_logs'));

		$dateTimeFrom = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$dateTimeTo = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
		$dateTimeFrom->modify($this->getDatetimeAdjust());

		$this->CI->utils->debug_log('dateTimeFrom', $dateTimeFrom, 'dateTimeTo', $dateTimeTo);

		$rlt = array('success' => true);
		$result = $this->CI->impt_game_logs->getImptGameLogStatistics($dateTimeFrom->format('Y-m-d H:i:s'), $dateTimeTo->format('Y-m-d H:i:s'));

		$cnt = 0;
		$sum=0;
		if ($result) {

			$unknownGame = $this->getUnknownGame();
			$gameDescIdMap = $this->CI->game_description_model->getGameCodeMap($this->getPlatformCode());

			foreach ($result as $impt_data) {

				$player_id=$impt_data->player_id;
				if (empty($player_id)) {
					continue;
				}

				$cnt++;

				// $player = $this->CI->player_model->getPlayerById($player_id);

				$has_both_side = $impt_data->Bet >= $impt_data->Win && $impt_data->Win > 0 ? 1 : 0;

				$bet_amount = $this->gameAmountToDB($impt_data->Bet);
				$result_amount = $this->gameAmountToDB($impt_data->Win);
				// $result_amount = $this->gameAmountToDB($this->getResultAmount($impt_data));
				$after_balance = $this->gameAmountToDB($this->getAfterBalance($impt_data));

				$sum+=$bet_amount;

				if($this->add_progressive_bet){
					//add progressive part
					$bet_amount+=$this->gameAmountToDB($impt_data->ProgressiveBet);
				}
				if($this->add_progressive_win){
					$result_amount+=$this->gameAmountToDB($impt_data->ProgressiveWin);
				}
				//remove bet from result
				$result_amount=$result_amount-$bet_amount;
				// if (empty($game_description_id)) {
				// 	$game_description_id = $unknownGame->id;
				// 	$game_type_id = $unknownGame->game_type_id;
				// }
				//it's for round number and game result
				//search game code on game strats report , PT BO
				$extra_info=['table' => $impt_data->GameCode];

				// $game_description_id = $impt_data->game_description_id;
				// $game_type_id = $impt_data->game_type_id;

				// if (empty($game_description_id)) {
				// 	$game_description_id = $unknownGame->id;
				// 	$game_type_id = $unknownGame->game_type_id;
				// }

				list($game_description_id, $game_type_id) = $this->getGameDescriptionInfo($impt_data, $unknownGame, $gameDescIdMap);

				$this->syncGameLogs(
					$game_type_id, $game_description_id,
					$impt_data->gameshortcode, $impt_data->GameType, $impt_data->GameName,
					$player_id, $impt_data->PlayerName,
					$bet_amount, $result_amount,
					null, # win_amount
					null, # loss_amount
					$after_balance,
					$has_both_side,
					$impt_data->external_uniqueid,
					$impt_data->GameDate,
					$impt_data->GameDate,
					$impt_data->response_result_id,
					Game_logs::FLAG_GAME, $extra_info
				);

			}
		}

		$this->CI->utils->debug_log('syncMergeToGameLogs monitor', 'count', $cnt);

		$rlt['count']=$cnt;
		$rlt['sum']=$sum;

		return $rlt;

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
		$this->utils->debug_log('queryTransaction', $transactionId, $extra);
		return $this->checkFundTransfer($extra['playerName'], $transactionId);
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

	// public function serverTimeToGameTime($dateTimeStr) {
	// 	if (is_object($dateTimeStr) && $dateTimeStr instanceof DateTime) {
	// 		return rawurlencode($dateTimeStr->format('Y-m-d H.i.s'));
	// 	} else {
	// 		$modify = $this->getServerTimeToGameTime();
	// 		return date('Y-m-d H.i.s', strtotime($this->utils->modifyDateTime($dateTimeStr, $modify)));
	// 	}
	// }

	public function blockPlayer($playerName) {

		parent::blockPlayer($playerName);

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForBlockPlayer',
			'playerName' => $playerName,
			'gameUsername'=>$gameUsername,
		);

		$params = array(
			'membercode' => $gameUsername,
			'frozenstatus' => '1',
		);

		return $this->callApi(self::API_blockPlayer, $params, $context);

	}

	public function processResultForBlockPlayer($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');

		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);
		$result=['code'=>@$resultJson['Code']];

		return array($success, $result);
	}

	public function unblockPlayer($playerName) {
		parent::unblockPlayer($playerName);

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForUnblockPlayer',
			'playerName' => $playerName,
			'gameUsername'=>$gameUsername,
		);

		$params = array(
			'membercode' => $gameUsername,
			'frozenstatus' => '0',
		);

		return $this->callApi(self::API_unblockPlayer, $params, $context);

	}

	public function processResultForUnblockPlayer($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');

		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);
		$result=['code'=>@$resultJson['Code']];

		return array($success, $result);
	}

}