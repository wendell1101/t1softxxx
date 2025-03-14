<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
set_include_path(dirname(__FILE__) . '/../unencrypt/phpseclib');
include_once 'Crypt/RSA.php';
/**
 * Defines general behavior of game API classes.
 *
 * General behaviors include:
 * * Getting platform code
 * * Empty Bet Limit
 * * Check the player's IP if white-listed
 * * If White-listed Register or Login
 * * Verify Player's signature
 * * Encrypts player's signature
 * * Check Player's Info
 * * Deposit To game
 * * Withdraw from Game
 * * Create Player
 * * Login/Logout
 * * Check if the player is existing
 * * Check Player Balance
 * * Synchronize Original Game Logs
 * * Synchronize Old Game logs to Current Game Logs
 * * Check all Players Balance
 * * Check all Players bet limit
 * * Gets Bet Limit
 * * Update Bet Limit
 * * Block/Unblock Player

 * All below behaviors are not yet implemented
 * * Logout
 * * Update Player information
 * * Check Player Daily Balance
 * * Check Game Records
 * * Check Login Status
 * * Check Login Token
 * * Check Total Betting Amount
 * * Check Transaction
 * * Check List Players
 * * Reset Player
 * * Revert Broken Game
 *
 * The functions implemented by child class:
 * * Populating game form parameters
 * * Handling callbacks
 *
 *
 *
 * @see Redirect redirect to game page
 *
 * @category Game_platform
 * @version 1.8.10
 * @copyright 2013-2022 tot
 */

class Game_api_ebet extends Abstract_game_api {

	public function getPlatformCode() {
		return EBET_API;
	}

	private $api_url;
	private $channel_id;
	private $public_key;
	private $private_key;
	private $forward_sites = null;
	private $wait_seconds;
	private $recharge_status_retry_times;
	private $sub_channel_id;
	private $html5_enabled;
	private $ebet_game_logs_model;
    public $redirect_url;

	const ALWAYS_LOWERCASE=TRUE;

	const STATUS_NOT_FOUND_USER='4037';
	const STATUS_USER_EXISTS='401';

	const GAME_TYPE_SLOTS = [5];
	const GAME_TYPE_LIVE_DEALER = [1,2,3,4];

	public function __construct() {
		parent::__construct();
		$this->api_url 		= $this->getSystemInfo('url');
		$this->channel_id 	= $this->getSystemInfo('account');
		$this->public_key 	= $this->getSystemInfo('public_key');
		$this->private_key 	= $this->getSystemInfo('private_key');
		$this->sub_channel_id = $this->getSystemInfo('sub_channel_id') ? : 0;
		$this->forward_sites = $this->getSystemInfo('forward_sites');
		$this->auto_tranfer_deposit = $this->getSystemInfo('auto_tranfer_deposit');
		$this->default_lang = $this->getSystemInfo('default_lang');
		$this->html5_enabled = $this->getSystemInfo('html5_enabled');
		$this->sync_after_balance = $this->getSystemInfo('sync_after_balance',false);
		$this->sync_sleep_time = $this->getSystemInfo('sync_sleep_time',3);
		$this->bet_status = $this->getSystemInfo('bet_status',1);
		$this->always_lowercase= $this->getSystemInfo('always_lowercase', self::ALWAYS_LOWERCASE);
		$this->redirect = $this->getSystemInfo('redirect', false);
		$this->wait_seconds = $this->getSystemInfo('wait_seconds', 5);
		$this->recharge_status_retry_times = $this->getSystemInfo('recharge_status_retry_times', 2);
		$this->currency = $this->getSystemInfo('currency');
		$this->enableQueryGameLaunchUrl = $this->getSystemInfo('enableQueryGameLaunchUrl', false);
		$this->redirect_url = $this->getSystemInfo('redirect_url');
		$this->enablePlayerLaunch = $this->getSystemInfo('enablePlayerLaunch', true);
		$this->uiHide = $this->getSystemInfo('uiHide');#example: logo,nav,banner


		$this->game_type_slots 	= $this->getSystemInfo('game_type_slots', self::GAME_TYPE_SLOTS);
		$this->game_type_live_dealer 	= $this->getSystemInfo('game_type_live_dealer', self::GAME_TYPE_LIVE_DEALER);

		if (empty($this->default_lang)) {
			$this->default_lang = '1';
		}

		if (empty($this->html5_enabled)) {
			$this->html5_enabled = FALSE;
		}

		$this->rsa = new Crypt_RSA();
		$this->rsa->setSignatureMode(CRYPT_RSA_SIGNATURE_PKCS1);
		$this->rsa->setHash('md5');

		$this->CI->load->model('ebet_game_logs');
		$this->ebet_game_logs_model=$this->CI->ebet_game_logs;
	}

	const API_getBetLimit = 'getBetLimit';
	const API_updateBetLimit = 'updateBetLimit';
	const API_getUserTransaction = 'getUserTransaction';

	const URI_MAP = array(
		self::API_depositToGame 	   		=> '/api/recharge',
		self::API_withdrawFromGame 	   		=> '/api/recharge',
		self::API_queryPlayerInfo 	   		=> '/api/userinfo',
		self::API_isPlayerExist 	   		=> '/api/userinfo',
		self::API_queryPlayerBalance   		=> '/api/userinfo',
		self::API_queryGameRecords 			=> '/api/userbethistory',
		self::API_createPlayer 				=> '/api/syncuser',
		self::API_batchQueryPlayerBalance 	=> '/api/getusermoney',
		self::API_getBetLimit 				=> '/api/getbetlimit',
		self::API_updateBetLimit 			=> '/api/updatebetlimit',
		self::API_queryTransaction 			=> '/api/rechargestatus',
		self::API_logout 					=> '/api/logout',
		self::API_getUserTransaction => '/api/usertransaction',
		self::API_queryForwardGame => '/api/launchUrl',
		self::API_queryDemoGame => '/api/demo',
		self::API_queryGameListFromGameProvider => '/api/gamelist',
		self::API_queryForwardGameV2 => '/api/playerlaunch'
	);

	const EVENT_TYPE = array(
		'normal' 	=> 1,
		'applink' 	=> 3,
		'token' 	=> 4,
	);

	const LIVE = 1;
	const DEMO = 0;

	const STATUS_CODE = array(
		'Success' 							=> 200,  # DONE
		'Invalid access token' 				=> 410,  # DONE
		'Wrong signature' 					=> 4026, # DONE
		'IP is not authorized' 				=> 4027, # DONE
		'Wrong username and password' 		=> 401,  # DONE
		'Channel system under maintenance' 	=> 505,  # DONE
	);

	const ORIGINAL_LOGS_TABLE_NAME = 'ebet_game_logs';
	const MD5_FIELDS_FOR_ORIGINAL=['gameType', 'roundNo', 'payout', 'createTime', 'payoutTime', 'validBet', 'userId', 'username','realBet','niuniuWithHoldingTotal','niuniuWithHoldingDetail','niuniuResult'];
	const MD5_FLOAT_AMOUNT_FIELDS=['payout','validBet','realBet','niuniuWithHoldingTotal'];
	const GAME_TYPES=['Baccarat', 'DragonTiger', 'Sicbo', 'RouletteWheel'];

	public function getEmptyBetLimit(){
		return [
			'Baccarat'=>[
				'gameId'=>'B',
				'bankerMax'=>null,
				'bankerMin'=>null,
				'bankerPairMax'=>null,
				'bankerPairMin'=>null,
				'playerMax'=>null,
				'playerMin'=>null,
				'playerPairMax'=>null,
				'playerPairMin'=>null,
				'tieMax'=>null,
				'tieMin'=>null,
				'bankerLucky6Min'=>null,
				'bankerLucky6Max'=>null,
				'bankerDragonBonusMin'=>null,
				'bankerDragonBonusMax'=>null,
				'playerDragonBonusMin'=>null,
				'playerDragonBonusMax'=>null,
				'bigMin'=>null,
				'bigMax'=>null,
				'smallMin'=>null,
				'smallMax'=>null,
				'bankerOddMin'=>null,
				'bankerOddMax'=>null,
				'bankerEvenMin'=>null,
				'bankerEvenMax'=>null,
				'playerOddMin'=>null,
				'playerOddMax'=>null,
				'playerEvenMin'=>null,
				'playerEvenMax'=>null,
			],
			'DragonTiger'=>[
				'gameId'=>'D',
				'dragonMin'=>null,
				'dragonMax'=>null,
				'tigerMin'=>null,
				'tigerMax'=>null,
				'tieMin'=>null,
				'tieMax'=>null,
				'dragonOddMin'=>null,
				'dragonOddMax'=>null,
				'dragonEvenMin'=>null,
				'dragonEvenMax'=>null,
				'tigerOddMin'=>null,
				'tigerOddMax'=>null,
				'tigerEvenMin'=>null,
				'tigerEvenMax'=>null,
				'dragonBlackMin'=>null,
				'dragonBlackMax'=>null,
				'dragonRedMin'=>null,
				'dragonRedMax'=>null,
				'tigerBlackMin'=>null,
				'tigerBlackMax'=>null,
				'tigerRedMin'=>null,
				'tigerRedMax'=>null
			],
			'Sicbo'=>[
				'gameId'=>'S',
				'oddMin'=>null,
				'oddMax'=>null,
				'evenMin'=>null,
				'evenMax'=>null,
				'bigMin'=>null,
				'bigMax'=>null,
				'smallMin'=>null,
				'smallMax'=>null,
				'pairMin'=>null,
				'pairMax'=>null,
				'tripleMin'=>null,
				'tripleMax'=>null,
				'allTripleMin'=>null,
				'allTripleMax'=>null,
				'fourMin'=>null,
				'fourMax'=>null,
				'fiveMin'=>null,
				'fiveMax'=>null,
				'sixMin'=>null,
				'sixMax'=>null,
				'sevenMin'=>null,
				'sevenMax'=>null,
				'eightMin'=>null,
				'eightMax'=>null,
				'nineMin'=>null,
				'nineMax'=>null,
				'tenMin'=>null,
				'tenMax'=>null,
				'elevenMin'=>null,
				'elevenMax'=>null,
				'twelveMin'=>null,
				'twelveMax'=>null,
				'thirteenMin'=>null,
				'thirteenMax'=>null,
				'fourteenMin'=>null,
				'fourteenMax'=>null,
				'fifteenMin'=>null,
				'fifteenMax'=>null,
				'sixteenMin'=>null,
				'sixteenMax'=>null,
				'seventeenMin'=>null,
				'seventeenMax'=>null,
				'singleDiceMin'=>null,
				'singleDiceMax'=>null,
				'combinationMin'=>null,
				'combinationMax'=>null,
				'moreLeftMin'=>null,
				'moreLeftMax'=>null,
				'moreRightMin'=>null,
				'moreRightMax'=>null,
			],
			'RouletteWheel'=>[
				'gameId'=>'R',
				'oddMin'=>null,
				'oddMax'=>null,
				'evenMin'=>null,
				'evenMax'=>null,
				'bigMin'=>null,
				'bigMax'=>null,
				'smallMin'=>null,
				'smallMax'=>null,
				'redMin'=>null,
				'redMax'=>null,
				'blackMin'=>null,
				'blackMax'=>null,
				'dozenMin'=>null,
				'dozenMax'=>null,
				'columnMin'=>null,
				'columnMax'=>null,
				'lineMin'=>null,
				'lineMax'=>null,
				'basketMin'=>null,
				'basketMax'=>null,
				'cornerMin'=>null,
				'cornerMax'=>null,
				'trioMin'=>null,
				'trioMax'=>null,
				'streetMin'=>null,
				'streetMax'=>null,
				'splitMin'=>null,
				'splitMax'=>null,
				'straightMin'=>null,
				'straightMax'=>null,
			],
		];
	}

    const BACCARAT = 1;
    const DRAGON_TIGER = 2;
    const SICBO = 3;
    const ROUTERY = 4;
    const NIUNIU = 8;
    const SLOTS = 5;
    const SPORTS = 6;

	# OUTGOING API CALL #############################################################################################################################
	public function callback($params) {

		$gameUsername = $params['username'];
		if ($this->forward_sites && preg_match("#^(?P<prefix>" . implode('|', array_keys($this->forward_sites)) . ")#", $gameUsername, $matches)) {
			if (isset($this->forward_sites[$matches['prefix']])) {
				$url = $this->forward_sites[$matches['prefix']];
				return $this->forwardCallback($url, $params);
			}
		}

		if ($this->getSystemInfo('ip_whitelist')) {
			$this->CI->load->library('whitelist_library');
			$ip_address = $this->CI->input->ip_address();
			$ip_whitelisted = $this->CI->whitelist_library->ip_whitelisted($this->getPlatformCode(), $ip_address);

			if ( ! $ip_whitelisted) {
				return array('status' => self::STATUS_CODE['IP is not authorized']);
			}
		}

		$cmd = @$params['cmd'];

		switch ($cmd) {

			case 'RegisterOrLoginReq':
				$response = $this->registerOrLoginReq($params);
				break;

			case 'UserInfo':
				if($this->getSystemInfo('enabled_update_user_info_on_callback')){
					$response = $this->userInfo($params);
				}else{
					$status= self::STATUS_CODE['Success'];
					$response=array('status'=>$status);
				}
				break;

			default:
				show_error('Bad Parameters [method]', 400);
				return;

		}

		$this->CI->utils->debug_log('callback_response', $response);
		return $response;

	}

	function registerOrLoginReq($params) {

		$this->CI->load->model('player_model');

		$status 		= self::STATUS_CODE['Success'];
		$subChannelId 	= $this->sub_channel_id;

		$eventType 			= @$params['eventType'];
		$channelId 			= @$params['channelId'];
		$gameUsername 		= @$params['username'];
		$password 			= @$params['password'];
		$signature 			= @$params['signature'];
		$timestamp 			= @$params['timestamp'];
		$ip 				= @$params['ip'];
		$accessToken 		= @$params['accessToken'];

		try {

			if ($eventType == 4 && $this->html5_enabled && isset($gameUsername, $accessToken)) {
				$signature_key = $timestamp . $accessToken;
				if ($this->verify($signature_key, $signature)) {

					//check block flag
					if($this->isBlockedUsernameInDB($gameUsername)){
						$this->utils->debug_log('blocked username', $gameUsername);
						throw new Exception('IP is not authorized');
					}

					//sync password first, copy player password to game password
					$this->syncGamePassword($gameUsername);

					//get password from game provider auth
					$playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);

					$token = $this->getPlayerToken($playerId);
					if ($accessToken != $token) {
						$this->CI->utils->debug_log('gameUsername', $gameUsername, 'playerId', $playerId, 'token', $token, 'accessToken', $accessToken);
						throw new Exception('Invalid access token');
					}

				} else {
					throw new Exception('Wrong signature', self::STATUS_CODE['Wrong signature']);
				}

			}

			# VALIDATE BY USERNAME AND PASSWORD
			elseif (isset($gameUsername, $password) && ! empty($gameUsername) && ! empty($password)) {

				// $prefix = $this->getSystemInfo('prefix_for_username');

				# PREFIX OF THIS SITE MATCHED
				// if (substr($gameUsername, 0, strlen($prefix)) == $prefix) {

					$signature_key = $gameUsername . $timestamp;
					if ($this->verify($signature_key, $signature)) {
						//check block flag
						if($this->isBlockedUsernameInDB($gameUsername)){
							$this->utils->debug_log('blocked username', $gameUsername);
							throw new Exception('IP is not authorized');
						}

						//sync password first, copy player password to game password
						$this->syncGamePassword($gameUsername);

						//get password from game provider auth
						$pass=$this->getPasswordByGameUsername($gameUsername);
						$playerId=$this->getPlayerIdInGameProviderAuth($gameUsername);

						if ($password == $pass){

							$accessToken = $this->getPlayerToken($playerId);

						} else {
							$this->CI->utils->debug_log('gameUsername', $gameUsername, 'playerId', $playerId, 'pass', $pass, 'password', $password);

							throw new Exception('Wrong username and password');

						}

					} else {

						throw new Exception('Wrong signature', self::STATUS_CODE['Wrong signature']);

					}


				// }

				# PREFIX OF OTHER SITES MATCHED
				// elseif (preg_match("#^(?P<prefix>" . implode('|', array_keys($this->forward_sites)) . ")#", $gameUsername, $matches)) {

				// 	if (isset($this->forward_sites[$matches['prefix']])) {

				// 		$url = $this->forward_sites[$matches['prefix']];

				// 		return $this->forwardCallback($url, $params);

				// 	} else {

				// 		throw new Exception('Wrong username and password');

				// 	}

				// }

				# DOES NOT MATCH ANY PREFIX
				// else {

					// throw new Exception('Wrong username and password');

				// }

			}

			# VALIDATE BY ACCESS TOKEN
			elseif (isset($accessToken) && ! empty($accessToken)) {

				$signature_key = $timestamp . $accessToken;

				$this->utils->debug_log('compare','signature_key', $signature_key,'signature',$signature);

				$player = $this->verify($signature_key, $signature) ? $this->getPlayerInfoByToken($accessToken) : false;

				$this->utils->debug_log('verify','player',$player);

				if (isset($player['gameUsername'])) {

					if($this->isBlockedUsernameInDB($player['gameUsername'])){
						$this->utils->debug_log('blocked username', $player['gameUsername']);
						throw new Exception('Wrong username and password');
					}

					$gameUsername = $player['gameUsername'];

				} else {

					throw new Exception('Invalid access token');

				}

			} else {

				show_error('Bad Parameters [username, password, accessToken]', 400);
				return;

			}

			return array(
				'status' 		=> $status,
				'subChannelId' 	=> $subChannelId,
				'accessToken' 	=> $accessToken,
				'username' 		=> $gameUsername,
				'nickname' 		=> $gameUsername,
				'currency' 		=> $this->currency,
			);

		} catch (Exception $e) {

			$error_message 	= $e->getMessage();
			$error_code 	= self::STATUS_CODE[$error_message] ? : self::STATUS_CODE['Channel system under maintenance'];

			$this->CI->utils->debug_log('callback_error', $error_message);

			return array('status' => $error_code);

		}

	}

	function userInfo($params) {

		$status 		= self::STATUS_CODE['Success'];

		$gameUsername 	= isset($params['username']) ? @$params['username'] : null;
		$channelId 		= isset($params['channelId']) ? @$params['channelId'] : null;
		$subChannelId 	= isset($params['subChannelId']) ? @$params['subChannelId'] : null;
		$userId 		= isset($params['userId']) ? @$params['userId'] : null;
		$money 			= isset($params['money']) ? @$params['money'] : 0;
		$timestamp 		= isset($params['timestamp']) ? @$params['timestamp'] : null;
		$ip 			= isset($params['ip']) ? @$params['ip'] : null;
		$signature 		= isset($params['signature']) ? @$params['signature'] : null;

		try {

			$signature_key = $gameUsername.$channelId.$timestamp;

			if ($this->verify($signature_key, $signature)) {

				$playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);

				if ( ! empty($playerId)) {
					$self = $this;
					// $this->CI->lockAndTransForPlayerBalance($playerId, function () use ($self, $playerId, $money) {
						$self->updatePlayerSubwalletBalance($playerId, $money);
					// });
					$status 		= self::STATUS_CODE['Success'];

				}

			}else{
				$status=self::STATUS_CODE['Wrong signature'];
			}

			return array('status'=>$status);

		} catch (Exception $e) {

			$error_message 	= $e->getMessage();
			$error_code 	= self::STATUS_CODE[$error_message] ? : self::STATUS_CODE['Channel system under maintenance'];

			$this->CI->utils->debug_log('callback_error', $error_message);

			return array('status' => $error_code);

		}

	}

	# OUTGOING API CALL #############################################################################################################################



	# HELPER ########################################################################################################################################

	function verify($str, $signature) {
		$signature = base64_decode($signature);
		$this->rsa->loadKey($this->public_key);
		return $this->rsa->verify($str, $signature);
	}

	function encrypt($str) {
		$this->rsa->loadKey($this->private_key);
		$signature = $this->rsa->sign($str);
		$signature = base64_encode($signature);
		return $signature;
	}

	function forwardCallback($url, $params) {
		list($header, $resultText) = $this->httpCallApi($url, $params);
		$this->CI->utils->debug_log('forwardCallback', $url, $header, $resultText);
		return json_decode($resultText);
	}

	# HELPER ########################################################################################################################################



	# ABSTRACT HELPER ########################################################################################################################################

	public function generateUrl($apiName, $params) {
		$apiUri = self::URI_MAP[$apiName];
		$url = $this->api_url . $apiUri;
		return $url;
	}

	protected function customHttpCall($ch, $params) {

		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));

	}

	public function processResultBoolean($responseResultId, $resultJson, $playerName = null) {

		$success = ! empty($resultJson) && $resultJson['status'] == self::STATUS_CODE['Success'];

		if ( ! $success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('EBET got error', $responseResultId, 'playerName', $playerName, 'result', $resultJson);
		}

		return $success;

	}

	private function isInvalidRow($row) {
		return FALSE;
	}

	public function gameAmountToDB($amount) {
		$amount = floatval($amount);
		return round($amount, 2);
	}

	private function getRealBetAmount($row) {
		$bet = $row->realBet;
		return $bet;
	}

	private function getBetAmount($row) {
		$bet = $row->bet;
		return $bet;
	}

	private function getResultAmountNew($row) {
		$bet = $row['realBet'];
		$result = $row['result'];
		return $result - $bet;
	}

	private function getResultAmount($row) {
		$bet = $row->realBet;
		$result = $row->result;
		return $result - $bet;
	}

	# ABSTRACT HELPER ########################################################################################################################################



	# INCOMING API CALL #############################################################################################################################

	public function queryPlayerInfo($playerName) {

		$game_username = $this->getGameUsernameByPlayerUsername($playerName);

		if($this->always_lowercase){
			$game_username=strtolower($game_username);
		}

		$context = array(
			'callback_obj' 		=> $this,
			'callback_method' 	=> 'processResultForQueryPlayerInfo',
			'game_username' 	=> $game_username,
			'playerName' 		=> $playerName,
		);

		$timestamp 		= time();
		$signature_key 	= $game_username . $timestamp;
		$signature 		= $this->encrypt($signature_key);

		$params = array(
			'username' 		=> $game_username,
			'channelId' 	=> $this->channel_id,
			'signature' 	=> $signature,
			'timestamp' 	=> $timestamp,
		);

		return $this->callApi(self::API_queryPlayerInfo, $params, $context);
	}

	public function processResultForQueryPlayerInfo($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$game_username = $this->getVariableFromContext($params, 'game_username');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);

		return array($success, $resultJson);
	}

	public function depositToGame($playerName, $amount, $transfer_secure_id=null) {

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		//convert to lower
		if($this->always_lowercase){
			$gameUsername=strtolower($gameUsername);
		}

		$rechargeReqId = $this->generateTransferId($transfer_secure_id); // 'DEP' . date('YmdHis') . random_string('numeric', 8);

		$context = array(
			'callback_obj' 		=> $this,
			'callback_method' 	=> 'processResultForRecharge',
			'gameUsername' 	=> $gameUsername,
			'playerName' 		=> $playerName,
			'rechargeReqId'=>$rechargeReqId,
			'amount' 			=> $amount,
			'transfer_secure_id'=>$transfer_secure_id,
			'external_transaction_id' => $rechargeReqId
		);

		$timestamp 		= time();
		$signature_key 	= $gameUsername . $timestamp;
		$signature 		= $this->encrypt($signature_key);

		$this->CI->utils->debug_log('game_username', $gameUsername, 'timestamp', $timestamp, 'key', $this->private_key);

		$params = array(
			'username' 		=> $gameUsername,
			'money' 		=> $amount,
			'channelId' 	=> $this->channel_id,
			'rechargeReqId' => $rechargeReqId,
			'signature' 	=> $signature,
			'timestamp' 	=> $timestamp,
		);

		return $this->callApi(self::API_depositToGame, $params, $context);
	}

	public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		if($this->always_lowercase){
			$gameUsername=strtolower($gameUsername);
		}

		$rechargeReqId = $this->generateTransferId($transfer_secure_id); //'WIT' . date('YmdHis') . random_string('numeric', 8);

		$context = array(
			'callback_obj' 		=> $this,
			'callback_method' 	=> 'processResultForRecharge',
			'gameUsername' 	=> $gameUsername,
			'playerName' 		=> $playerName,
			'amount' 			=> $amount,
			'rechargeReqId'     => $rechargeReqId,
			'transfer_secure_id'=>$transfer_secure_id,
			'external_transaction_id' => $rechargeReqId
		);

		$timestamp 		= time();
		$signature_key 	= $gameUsername . $timestamp;
		$signature 		= $this->encrypt($signature_key);

		$params = array(
			'username' 		=> $gameUsername,
			'money' 		=> -$amount,
			'channelId' 	=> $this->channel_id,
			'rechargeReqId' => $rechargeReqId,
			'signature' 	=> $signature,
			'timestamp' 	=> $timestamp,
		);

		return $this->callApi(self::API_withdrawFromGame, $params, $context);
	}

	public function processResultForRecharge($params) {

		$this->CI->utils->debug_log('params of recharge', $params['params']);

		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$amount = $this->getVariableFromContext($params, 'amount');
		$rechargeReqId = $this->getVariableFromContext($params, 'rechargeReqId');
		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);
		$statusCode = $this->getStatusCodeFromParams($params);

		$type = $amount>=0 ? 'IN' : 'OUT';

		$afterBalance=null;

		$result = array(
			'response_result_id' => $responseResultId,
			'external_transaction_id'=>$external_transaction_id,
			'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id'=>self::REASON_UNKNOWN
		);

		if ($success) {

			//call query transaction

//            //try query order status
//            $result=$this->queryTransaction($rechargeReqId, null);
//            $this->CI->utils->debug_log('============= ebet query trans when '.$type.' try queryTransaction', $rechargeReqId, $result);
//
//            $cnt=1;
//            //3 times
//            while((!$result['success'] || @$result['status']=='0') && $cnt<=$this->transfer_retry_times){
//                $result=$this->queryTransaction($rechargeReqId, null);
//
//                $cnt++;
//
//                sleep($this->common_wait_seconds);
//
//                $this->CI->utils->debug_log('============= ebet get error when '.$type.' try queryTransaction', $rechargeReqId, $result);
//            }
//            //only deposit to sub
//            if($result['success'] && $type == 'IN' && @$result['status']=='0'){
//
//                $this->CI->utils->debug_log('============= ebet convert success to true if still network error when '.$type, $rechargeReqId, $result);
//
//                //convert to success
//                $result['success']=true;
//                $result['status']=@$result['status'];
//            }else{
//                $this->CI->utils->debug_log('============= ebet transfer status when '.$type, $rechargeReqId, $result);
//	           	//200 = success
//	           	$result['success']=@$result['status']=='200';
//	           	$result['status']=@$result['status'];
//            }

			// $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
			// if ($playerId) {
			// 	if(isset($resultJson['money'])){
			// 		$afterBalance = floatval( $resultJson['money'] );
			// 		$this->insertTransactionToGameLogs($playerId, $gameUsername, $afterBalance, $amount, $responseResultId, (($type == 'IN') ? $this->transTypeMainWalletToSubWallet() : $this->transTypeSubWalletToMainWallet()));

			// 	}else{
			// 		$this->CI->utils->error_log('lost money field in result', $params['params']);
			// 	}
			// } else {
			// 	$this->CI->utils->error_log('cannot get player id from ' . $gameUsername . ' getPlayerIdInGameProviderAuth');
			// }
			$result['didnot_insert_game_logs']=true;
			$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
		} else {

			$error_code = @$resultJson['status'];

			// if it's 500 , convert it to success
            if(((in_array($statusCode, $this->other_status_code_treat_as_success)) ||  (in_array($error_code, $this->other_status_code_treat_as_success))) && $this->treat_500_as_success_on_deposit){
				$result['reason_id']=self::REASON_GAME_PROVIDER_INTERNAL_PROBLEM;
				$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_UNKNOWN;
                $success=true;
            }else{
				switch($error_code) {
					case '201' :
						$result['reason_id'] = self::REASON_DUPLICATE_TRANSFER;
						break;
					case '202' :
						$result['reason_id'] = self::REASON_AGENT_NOT_EXISTED;
						break;
					case '203' :
					case '204' :
						$result['reason_id'] = self::REASON_INVALID_TRANSACTION_ID;
						break;
					case '4003' :
						$result['reason_id'] = self::REASON_GAME_PROVIDER_INTERNAL_PROBLEM;
						break;
					case '4026' :
						$result['reason_id'] = self::REASON_INVALID_KEY;
						break;
					case '4027' :
						$result['reason_id'] = self::REASON_IP_NOT_AUTHORIZED;
						break;
					case '4028' :
					case '4037' :
						$result['reason_id'] = self::REASON_NOT_FOUND_PLAYER;
						break;
				}
				$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_DECLINED;
			}
			
		}

		//load from result
		$external_transaction_id=$rechargeReqId;
		//if empty, use our transfer id
		if(empty($external_transaction_id)){
		#	$external_transaction_id=$this->getVariableFromContext($params, 'transfer_secure_id');
		}

		// $result['after_balance']=$afterBalance;
		#$result['external_transaction_id']=$external_transaction_id;
		#$result['response_result_id'] = $responseResultId;

		return array($success, $result);
	}

	# INCOMING API CALL #############################################################################################################################

	public function queryGameListFromGameProvider($extra=null){ 
        $this->utils->debug_log("EBET TRANSFER WALLET: (queryGameList)");   

		$timestamp 		= time();
		$signature_key 	= $this->channel_id . $timestamp;
		$signature 		= $this->encrypt($signature_key);

        $params = [
			'channelId' => $this->channel_id,
			'timestamp' => $timestamp,
			'signature' => $signature
		];

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryGameListFromGameProvider',
        );

        return $this->callApi(self::API_queryGameListFromGameProvider, $params, $context);
    } 

    public function processResultForQueryGameListFromGameProvider($params){
		$this->CI->utils->debug_log('EBET SEAMLESS SEAMLESS (processResultForQueryForwardGame)', $params);

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultJson);

		return array($success, $resultJson);
	}



	public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
		parent::createPlayer($playerName, $playerId, $password, $email, $extra);
		//call syncuser
		$game_username = $this->getGameUsernameByPlayerUsername($playerName);

		if($this->always_lowercase){
			$game_username=strtolower($game_username);
		}

		$context = array(
			'callback_obj' 		=> $this,
			'callback_method' 	=> 'processResultForCreatePlayer',
			'game_username' 	=> $game_username,
			'playerName' 		=> $playerName,
			'playerId' 			=> $playerId,
		);

		//FIXME
		$timestamp 		= time();
		$signature_key 	= $game_username;
		$signature 		= $this->encrypt($signature_key);

		$params = array(
			'username' 		=> $game_username,
			'channelId' 	=> $this->channel_id,
			// 'lang' 			=> $this->default_lang,
			'signature' 	=> $signature,
			// 'timestamp' 	=> $timestamp,
            'subChannelId'  => $this->sub_channel_id,
		);

		return $this->callApi(self::API_createPlayer, $params, $context);

		// return array('success' => true);
	}

	public function processResultForCreatePlayer($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$game_username = $this->getVariableFromContext($params, 'game_username');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$playerId = $this->getVariableFromContext($params, 'playerId');
		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);

		if(isset($resultJson['status']) && $resultJson['status']==self::STATUS_USER_EXISTS){
			$success=true;
		}

		if($success){
			$this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
		}else{
			$this->CI->utils->error_log('ebet transfer failed', $game_username);
		}

		return array( $success, null);
	}

	public function login($playerName, $password = NULL) {
		return $this->returnUnimplemented();
	}

	public function isPlayerExist($playerName) {

		$playerId=$this->getPlayerIdFromUsername($playerName);

		$game_username = $this->getGameUsernameByPlayerUsername($playerName);

		if($this->always_lowercase){
			$game_username=strtolower($game_username);
		}

		$context = array(
			'callback_obj' 		=> $this,
			'callback_method' 	=> 'processResultForIsPlayerExist',
			'game_username' 	=> $game_username,
			'playerName' 		=> $playerName,
			'playerId' => $playerId,
		);

		$timestamp 		= time();
		$signature_key 	= $game_username . $timestamp;
		$signature 		= $this->encrypt($signature_key);

		$params = array(
			'username' 		=> $game_username,
			'channelId' 	=> $this->channel_id,
			'signature' 	=> $signature,
			'timestamp' 	=> $timestamp,
		);

		return $this->callApi(self::API_isPlayerExist, $params, $context);
	}

	public function processResultForIsPlayerExist($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$game_username = $this->getVariableFromContext($params, 'game_username');
		$playerId = $this->getVariableFromContext($params, 'playerId');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);

		$exist=null;

		if(isset($resultJson['status']) && $resultJson['status']==self::STATUS_NOT_FOUND_USER){
			$success=true;
			$exist=false;
		}else if($success){
			$exist=true;
			$this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
		}

		return array( ! empty($resultJson), array('exists' => $exist));
	}

	public function queryPlayerBalance($playerName) {

		$game_username = $this->getGameUsernameByPlayerUsername($playerName);

		if($this->always_lowercase){
			$game_username=strtolower($game_username);
		}

		$context = array(
			'callback_obj' 		=> $this,
			'callback_method' 	=> 'processResultForQueryPlayerBalance',
			'game_username' 	=> $game_username,
			'playerName' 		=> $playerName,
		);

		$timestamp 		= time();
		$signature_key 	= $game_username . $timestamp;
		$signature 		= $this->encrypt($signature_key);

		$params = array(
			'username' 		=> $game_username,
			'channelId' 	=> $this->channel_id,
			'signature' 	=> $signature,
			'timestamp' 	=> $timestamp,
		);

		return $this->callApi(self::API_queryPlayerBalance, $params, $context);
	}

	public function processResultForQueryPlayerBalance($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$game_username = $this->getVariableFromContext($params, 'game_username');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);

		$result = array('balance' => 0);

		if (isset($resultJson['money'])) {
			$result['balance'] = $resultJson['money'];
		}

		return array($success, $result);
	}

	const DEFAULT_PAGE_SIZE=100;

	public function syncOriginalGameLogs($token) {

		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$startDate->modify($this->getDatetimeAdjust());

		// $startDate = $this->serverTimeToGameTime($startDate);
		// $endDate = $this->serverTimeToGameTime($endDate);

		$context = array(
			'callback_obj' 		=> $this,
			'callback_method' 	=> 'processResultForSyncOriginalGameLogs',
		);

		$currentPage = 1;
		$pageSize 	 = $this->getSystemInfo('page_size');
		if(empty($pageSize)){
			$pageSize=self::DEFAULT_PAGE_SIZE;
		}

		$cnt = 0;
		$sum = 0;

		do {

			$timestamp 		= time();
			$signature_key 	= $timestamp;
			$signature 		= $this->encrypt($signature_key);

            $this->CI->utils->debug_log('############# EBET startDate', $startDate->format('Y-m-d H:i:s'), 'endDate', $endDate->format('Y-m-d H:i:s'));

			$params = array(
				'startTimeStr' 	=> $startDate->format('Y-m-d H:i:s'),
				'endTimeStr' 	=> $endDate->format('Y-m-d H:i:s'),
				'channelId' 	=> $this->channel_id,
				'subChannelId' 	=> $this->sub_channel_id,
				'pageNum' 		=> $currentPage++,
				'pageSize' 		=> $pageSize,
				'signature' 	=> $signature,
				'timestamp' 	=> $timestamp,
				'betStatus' 	=> $this->bet_status,
			);

			$this->utils->debug_log('syncOriginalGameLogs params', $params);

			$result = $this->callApi(self::API_queryGameRecords, $params, $context);
			$success = $result['success'];
			if ($success) {
				$total_pages = ceil($result['count'] / $pageSize);
				$goNext = $currentPage <= $total_pages;
			} else {
				$goNext = false;
			}
		} while ($success && $goNext);

		return array('success' => $success);

	}

	public function processGameRecords($gameRecords, $response_result_id) {
		$newArray = array();
		if ($gameRecords) {
			foreach ($gameRecords as $gameRecord) {
				$insertArray = array();

					$insertArray['realBet'] = array_sum(array_column($gameRecord['betMap'], 'betMoney'));

					foreach ($gameRecord as &$value) {
						if (is_array($value)) {
							$value = json_encode($value);
						}
					}

					$gameshortcode 		= isset($gameRecord['gameType'])?$gameRecord['gameType']:null;
					if($gameRecord['gameType'] == self::SLOTS){
						// $gameshortcode 		= isset($gameRecord['gameType'])?$gameRecord['gameType']."-".$gameRecord['gameName']:null;
						$gameshortcode = isset($gameRecord['tableCode'])?$gameRecord['tableCode']:"unknown";
					}

					if($gameRecord['gameType'] == self::SPORTS){
						$betMap = json_decode($gameRecord['betMap'], true);
						$parlay_bettype = 0;
						$gameshortcode = "unknown";
						$betType = isset($betMap[0]['betType']) ? $betMap[0]['betType'] : "unknown_bettype";
						$selectionList = isset($gameRecord['selectionList']) ? $gameRecord['selectionList'] : [];

						if(!empty($selectionList)){
							$selectionList = json_decode($selectionList, true);
							$gameshortcode = isset($selectionList[0]['gameCode']) ? $selectionList[0]['gameCode'] : "unknown";
						}
						
						if($betType == $parlay_bettype){
							$gameshortcode = "parlay";
						}
					}
					$uniqueId 			= isset($gameRecord['betHistoryId'])?$gameRecord['betHistoryId']:null;
					$external_uniqueid 	= $uniqueId;

					unset($gameRecord['bet']);

					$insertArray["gameType"] = isset($gameRecord['gameType'])?$gameRecord['gameType']:null;
					$insertArray["betMap"] = isset($gameRecord['betMap'])?$gameRecord['betMap']:null;
					$insertArray["judgeResult"] = isset($gameRecord['judgeResult'])?$gameRecord['judgeResult']:null;
					$insertArray["roundNo"] = isset($gameRecord['roundNo'])?$gameRecord['roundNo']:null;
					$insertArray["payout"] = isset($gameRecord['payout'])?$gameRecord['payout']:null;
					$insertArray["bankerCards"] = isset($gameRecord['bankerCards'])?$gameRecord['bankerCards']:null;
					$insertArray["playerCards"] = isset($gameRecord['playerCards'])?$gameRecord['playerCards']:null;
					$insertArray["allDices"] = isset($gameRecord['allDices'])?$gameRecord['allDices']:null;
					$insertArray["dragonCard"] = isset($gameRecord['dragonCard'])?$gameRecord['dragonCard']:null;
					$insertArray["tigerCard"] = isset($gameRecord['tigerCard'])?$gameRecord['tigerCard']:null;
					$insertArray["number"] = isset($gameRecord['number'])?$gameRecord['number']:null;
					$insertArray['origCreateTime'] 	= isset($gameRecord['createTime'])?$gameRecord['createTime']:null;
					$insertArray['origPayoutTime'] 	= isset($gameRecord['payoutTime'])?$gameRecord['payoutTime']:null;
					$insertArray["createTime"] = isset($gameRecord['createTime'])?date('Y-m-d H:i:s', $gameRecord['createTime']):null;
					$insertArray["payoutTime"] = isset($gameRecord['payoutTime'])?date('Y-m-d H:i:s', $gameRecord['payoutTime']):null;
					$insertArray["cancelTime"] = isset($gameRecord['cancelTime'])?date('Y-m-d H:i:s', $gameRecord['cancelTime']):null;
					$insertArray["betHistoryId"] = isset($gameRecord['betHistoryId'])?$gameRecord['betHistoryId']:null;
					$insertArray["validBet"] = isset($gameRecord['validBet'])?$gameRecord['validBet']:null;
					$insertArray["userId"] = isset($gameRecord['userId'])?$gameRecord['userId']:null;
					$insertArray["username"] = isset($gameRecord['username'])?$gameRecord['username']:null;
					$insertArray["subChannelId"] = isset($gameRecord['subChannelId'])?$gameRecord['subChannelId']:null;
					$insertArray["niuniuWithHoldingTotal"] = isset($gameRecord['niuniuWithHoldingTotal'])?$gameRecord['niuniuWithHoldingTotal']:null;
					$insertArray["niuniuWithHoldingDetail"] = isset($gameRecord['niuniuWithHoldingDetail'])?$gameRecord['niuniuWithHoldingDetail']:null;
					$insertArray["niuniuResult"] = isset($gameRecord['niuniuResult'])?$gameRecord['niuniuResult']:null;

					if(isset($gameRecord['withHoldingTotal'])){
						$insertArray["niuniuWithHoldingTotal"] = isset($gameRecord['withHoldingTotal'])?$gameRecord['withHoldingTotal']:null;
					}
					if(isset($gameRecord['withHoldingDetail'])){
						$insertArray["niuniuWithHoldingDetail"] = isset($gameRecord['withHoldingDetail'])?$gameRecord['withHoldingDetail']:null;
					}

					$insertArray['response_result_id'] 	= $response_result_id;
					$insertArray['uniqueid'] 			= $uniqueId;
					$insertArray['external_uniqueid'] 	= $external_uniqueid;
					$insertArray['gameshortcode'] 		= $gameshortcode;
					if($insertArray["roundNo"]=='ROUNOXCB01020024022000080011'){
						var_dump($gameRecord);
					}
					array_push($newArray,$insertArray);
			}
		}
		return $newArray;
	}

	private function updateOrInsertOriginalGameLogs($data, $queryType, $additionalInfo=[]){
        $dataCount=0;
        if(!empty($data)){

            foreach ($data as $record) {
                if ($queryType == 'update') {
                    $this->CI->original_game_logs_model->updateRowsToOriginal(self::ORIGINAL_LOGS_TABLE_NAME, $record);
                } else {
                    unset($record['id']);
                    $this->CI->original_game_logs_model->insertRowsToOriginal(self::ORIGINAL_LOGS_TABLE_NAME, $record);
                }
                $dataCount++;
                unset($record);
            }
        }

        return $dataCount;
    }

	public function processResultForSyncOriginalGameLogs($params) {
		$this->CI->load->model(array('external_system','original_game_logs_model'));
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultJson);
		$result = array(
			'data_count'=> 0
		);
		if ($success) {
			$gameRecords = $this->processGameRecords($resultJson['betHistories'], $responseResultId);
			list($insertRows, $updateRows)=$this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                self::ORIGINAL_LOGS_TABLE_NAME,
                $gameRecords,
                'uniqueid',
                'uniqueid',
                self::MD5_FIELDS_FOR_ORIGINAL,
                'md5_sum',
                'id',
                self::MD5_FLOAT_AMOUNT_FIELDS
            ); 
            $this->CI->utils->debug_log('after process available rows', 'gamerecords ->',count($gameRecords), 'insertrows->',count($insertRows), 'updaterows->',count($updateRows));
            if (!empty($insertRows)) {
				$result['data_count'] += $this->updateOrInsertOriginalGameLogs($insertRows, 'insert');
			}
			unset($insertRows);

			if (!empty($updateRows)) {
				$result['data_count'] += $this->updateOrInsertOriginalGameLogs($updateRows, 'update');
			}
			unset($updateRows);
			$result['count'] = $resultJson['count'];
		}


		// if ($success) {


		// 	$gameRecords = isset($resultJson['betHistories']) ? $resultJson['betHistories'] : array();

		// 	if ($gameRecords) {
		// 		$newArray = array();
		// 		foreach ($gameRecords as $gameRecord) {
		// 			$insertArray = array();

		// 			$insertArray['realBet'] = array_sum(array_column($gameRecord['betMap'], 'betMoney'));

		// 			foreach ($gameRecord as &$value) {
		// 				if (is_array($value)) {
		// 					$value = json_encode($value);
		// 				}
		// 			}

		// 			$gameshortcode 		= isset($gameRecord['gameType'])?$gameRecord['gameType']:null;
		// 			if($gameRecord['gameType'] == self::SLOTS){
		// 				$gameshortcode 		= isset($gameRecord['gameType'])?$gameRecord['gameType']."-".$gameRecord['gameName']:null;
		// 			}
		// 			$uniqueId 			= isset($gameRecord['betHistoryId'])?$gameRecord['betHistoryId']:null;
		// 			$external_uniqueid 	= $uniqueId;

		// 			unset($gameRecord['bet']);

		// 			$insertArray["gameType"] = isset($gameRecord['gameType'])?$gameRecord['gameType']:null;
		// 			$insertArray["betMap"] = isset($gameRecord['betMap'])?$gameRecord['betMap']:null;
		// 			$insertArray["judgeResult"] = isset($gameRecord['judgeResult'])?$gameRecord['judgeResult']:null;
		// 			$insertArray["roundNo"] = isset($gameRecord['roundNo'])?$gameRecord['roundNo']:null;
		// 			$insertArray["payout"] = isset($gameRecord['payout'])?$gameRecord['payout']:null;
		// 			$insertArray["bankerCards"] = isset($gameRecord['bankerCards'])?$gameRecord['bankerCards']:null;
		// 			$insertArray["playerCards"] = isset($gameRecord['playerCards'])?$gameRecord['playerCards']:null;
		// 			$insertArray["allDices"] = isset($gameRecord['allDices'])?$gameRecord['allDices']:null;
		// 			$insertArray["dragonCard"] = isset($gameRecord['dragonCard'])?$gameRecord['dragonCard']:null;
		// 			$insertArray["tigerCard"] = isset($gameRecord['tigerCard'])?$gameRecord['tigerCard']:null;
		// 			$insertArray["number"] = isset($gameRecord['number'])?$gameRecord['number']:null;
		// 			$insertArray['origCreateTime'] 	= isset($gameRecord['createTime'])?$gameRecord['createTime']:null;
		// 			$insertArray['origPayoutTime'] 	= isset($gameRecord['payoutTime'])?$gameRecord['payoutTime']:null;
		// 			$insertArray["createTime"] = isset($gameRecord['createTime'])?date('Y-m-d H:i:s', $gameRecord['createTime']):null;
		// 			$insertArray["payoutTime"] = isset($gameRecord['payoutTime'])?date('Y-m-d H:i:s', $gameRecord['payoutTime']):null;
		// 			$insertArray["betHistoryId"] = isset($gameRecord['betHistoryId'])?$gameRecord['betHistoryId']:null;
		// 			$insertArray["validBet"] = isset($gameRecord['validBet'])?$gameRecord['validBet']:null;
		// 			$insertArray["userId"] = isset($gameRecord['userId'])?$gameRecord['userId']:null;
		// 			$insertArray["username"] = isset($gameRecord['username'])?$gameRecord['username']:null;
		// 			$insertArray["subChannelId"] = isset($gameRecord['subChannelId'])?$gameRecord['subChannelId']:null;
		// 			$insertArray["niuniuWithHoldingTotal"] = isset($gameRecord['niuniuWithHoldingTotal'])?$gameRecord['niuniuWithHoldingTotal']:null;
		// 			$insertArray["niuniuWithHoldingDetail"] = isset($gameRecord['niuniuWithHoldingDetail'])?$gameRecord['niuniuWithHoldingDetail']:null;
		// 			$insertArray["niuniuResult"] = isset($gameRecord['niuniuResult'])?$gameRecord['niuniuResult']:null;

		// 			$insertArray['response_result_id'] 	= $responseResultId;
		// 			$insertArray['uniqueid'] 			= $uniqueId;
		// 			$insertArray['external_uniqueid'] 	= $external_uniqueid;
		// 			$insertArray['gameshortcode'] 		= $gameshortcode;
		// 			array_push($newArray,$insertArray);
		// 		}

		// 		unset($gameRecords); // unset all data on gameRecords used newArray

		// 		if($this->getSystemInfo('always_sync_game_records')){

		// 			$this->CI->utils->debug_log('newArray', count($newArray));
		// 			foreach ($newArray as $availableRow) {
		// 				if ( ! $this->isInvalidRow($availableRow)) {
		// 					$this->ebet_game_logs_model->syncGameLogs($availableRow);
		// 				}
		// 			}

		// 		}else{

		// 			$availableRows = $this->ebet_game_logs_model->getAvailableRows($newArray);
		// 			$this->CI->utils->debug_log('availableRows', count($availableRows), 'newArray', count($newArray));

		// 			foreach ($availableRows as $availableRow) {
		// 				if ( ! $this->isInvalidRow($availableRow)) {
		// 					// $this->CI->ebet_game_logs->syncGameLogs($availableRow);
		// 					$this->ebet_game_logs_model->insertEbetGameLogs($availableRow);
		// 				}
		// 			}

		// 		}

		// 	}

		// 	$result['count'] = $resultJson['count'];

		// }

		return array($success, $result);

	}

	   /**
     * queryOriginalGameLogs
     * @param  string $dateFrom
     * @param  string $dateTo
     * @param  bool   $use_bet_time
     * @return array
     */
    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time){
        $sql = <<<EOD
SELECT
	ebet.id as sync_index,
    ebet.id as id,
	ebet.external_uniqueid,
	ebet.username,
	ebet.gameshortcode,
	ebet.response_result_id,
	ebet.createTime as start_at,
	ebet.payoutTime as end_at,
	ebet.cancelTime as cancel_time,
	ebet.validBet as bet,
	ebet.realBet,
	ebet.payout as result,
	ebet.roundNo,
	ebet.betHistoryId,
    ebet.gameType,
    ebet.judgeResult,
	ebet.betMap,
	ebet.balance as after_balance,
	ebet.niuniuWithHoldingTotal,
	ebet.niuniuWithHoldingDetail,
	ebet.niuniuResult,
	game_provider_auth.player_id,
	gd.id as game_description_id,
	gd.game_name as game,
	gd.game_code as game_code,
	gd.game_type_id,
	gd.void_bet as void_bet,
	ebet.md5_sum
FROM
	ebet_game_logs as ebet
LEFT JOIN
	game_description as gd ON gd.external_game_id = ebet.gameshortcode and gd.void_bet!=1 and gd.game_platform_id = ?
JOIN
	game_provider_auth ON ebet.username = game_provider_auth.login_name AND game_provider_auth.game_provider_id = ?
WHERE
	ebet.payoutTime >= ? AND ebet.payoutTime <= ?
EOD;
        
        $params=[
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo,
        ];

        $this->CI->utils->debug_log('merge sql', $sql, $params);

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $result;
    }

    /**
     * it will be used on processUnsettleGameLogs and commonUpdateOrInsertGameLogs
     *
     * @param  array $row
     * @return array $params
     */
    public function makeParamsForInsertOrUpdateGameLogsRow(array $row) {
        if(empty($row['md5_sum'])){
            $this->CI->utils->error_log('no md5 on ', $row['external_uniqueid']);
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, ['end_at'],['bet', 'result']);
        }
        $bet_amount = $this->gameAmountToDB($row['bet']);
        $result_amount = $this->gameAmountToDB($row['result']-$row['realBet']);

		if(!empty($row['niuniuWithHoldingTotal'])){
			$result_amount 	= $this->gameAmountToDB($this->getResultAmountNew($row));
			$result_amount = $result_amount - $row['niuniuWithHoldingTotal'];
		}

        $real_betting_amount = $this->gameAmountToDB($row['realBet']);
        $bet_details = $this->processGameBetDetail((object)$row, $bet_amount, $result_amount);
		$betHistoryId = !empty($row['betHistoryId']) ? $row['betHistoryId'] : null;
		$roundNo = !empty($row['roundNo']) ? $row['roundNo'] : $betHistoryId;
		
        return [
            'game_info' => [
                'game_type_id' => $row['game_type_id'],
                'game_description_id' => $row['game_description_id'],
                'game_code' => $row['game_code'],
                'game_type' => null,
                'game' => $row['game'],
            ],
            'player_info' => [
                'player_id' => $row['player_id'],
                'player_username' => null,
            ],
            'amount_info' => [
                'bet_amount' => $bet_amount,
                'result_amount' => $result_amount ,
                'bet_for_cashback' => $bet_amount,
                'real_betting_amount' => $real_betting_amount,
                'win_amount' => null,
                'loss_amount' => null,
                'after_balance' => $row['after_balance'],
            ],
            'date_info' => [
                'start_at' => $row['start_at'],
                'end_at' => $row['end_at'],
                'bet_at' => $row['start_at'],
                'updated_at' => $this->CI->utils->getNowForMysql(),
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => is_null($row['cancel_time']) ? Game_logs::STATUS_SETTLED : Game_logs::STATUS_CANCELLED,
            'additional_info' => [
                'has_both_side' => 0,
                'external_uniqueid' => $row['external_uniqueid'],
                'round_number' => $roundNo,
                'md5_sum' => $row['md5_sum'],
                'response_result_id' => $row['response_result_id'],
                'sync_index' => $row['sync_index'],
                'bet_type' => null
            ],
            'bet_details' => $bet_details,
            'extra' => [],
            //from exists game logs
            'game_logs_id'=>isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];
    }

    /**
     *
     * perpare original rows, include process unknown game, pack bet details, convert game status
     *
     * @param  array &$row
     */
    public function preprocessOriginalRowForGameLogs(array &$row)
    {
        if (empty($row['game_description_id']))
        {
            $unknownGame = $this->getUnknownGame($this->getPlatformCode());
            list($game_description_id,$game_type_id) = $this->getGameDescriptionInfo($row,$unknownGame);
            $row['game_description_id']= $game_description_id;
            $row['game_type_id'] = $game_type_id;
        }
    }

	public function syncMergeToGameLogs($token) {
		$enabled_game_logs_unsettle=false;
        return $this->commonSyncMergeToGameLogs($token,
                $this,
                [$this, 'queryOriginalGameLogs'],
                [$this, 'makeParamsForInsertOrUpdateGameLogsRow'],
                [$this, 'preprocessOriginalRowForGameLogs'],
                $enabled_game_logs_unsettle);


		// $this->CI->load->model(array('game_logs', 'player_model', 'game_description_model'));

		// $dateTimeFrom = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		// $dateTimeFrom->modify($this->getDatetimeAdjust());
		// $dateTimeFrom = $dateTimeFrom->format('Y-m-d H:i:s');

		// $dateTimeTo = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
		// $dateTimeTo = $dateTimeTo->format('Y-m-d H:i:s');

		// $this->CI->utils->debug_log('dateTimeFrom', $dateTimeFrom, 'dateTimeTo', $dateTimeTo);

		// $cnt 	= 0;
		// $rlt 	= array('success' => true);
		// $result = $this->ebet_game_logs_model->getebetGameLogStatistics($dateTimeFrom, $dateTimeTo);
		// $unknownGame = $this->getUnknownGame();
		// foreach ($result as $ebet_data) {
		// 	if ($player_id = $this->getPlayerIdInGameProviderAuth($ebet_data->username)) {

		// 		$cnt++;

		// 		$player 		= $this->CI->player_model->getPlayerById($player_id);
		// 		$bet_amount 	= $this->gameAmountToDB($this->getBetAmount($ebet_data));
		// 		$real_bet_amount= $this->gameAmountToDB($this->getRealBetAmount($ebet_data));
		// 		$result_amount 	= $this->gameAmountToDB($this->getResultAmount($ebet_data));
		// 		$has_both_side 	= $bet_amount >= $result_amount && $result_amount > 0 ? 1 : 0;
		// 		$afterBalance = $ebet_data->after_balance;
		// 		// if($this->sync_after_balance){
		// 		// 	$afterBalance = $this->processAndGetAfterBalanceAfterBet($ebet_data->roundNo,$ebet_data->end_at,$ebet_data->username,$ebet_data->start_at,$ebet_data->end_at);
		// 		// }

		// 		if(!empty($ebet_data->niuniuWithHoldingTotal)){
		// 			$result_amount = $result_amount - $ebet_data->niuniuWithHoldingTotal;
		// 		}

        //         $extra = [
        //             'table'=>$ebet_data->roundNo,
        //             'trans_amount'=>$real_bet_amount,
		// 			//'bet_type'=> lang("Bet Detail Link"),
		// 			'bet_type'=> null,
        //             'bet_details' => $this->processGameBetDetail($ebet_data,$bet_amount,$result_amount),
        //             'sync_index' => $ebet_data->id,
        //         ];
        //         if (empty($ebet_data->game_description_id)) {
		// 			// $ebet_data->game_type_id = $unknownGame->game_type_id;
		// 			// $ebet_data->game_description_id = $unknownGame->id;
		// 			$unknownGame = $this->getUnknownGame($this->getPlatformCode());
        //     		list($game_description_id,$game_type_id) = $this->getGameDescriptionInfo($ebet_data,$unknownGame);
        //     		$ebet_data->game_description_id = $game_description_id;
        //     		$ebet_data->game_type_id = $game_type_id;

		// 		}
		// 		// $this->CI->utils->debug_log('time: '.$ebet_data->end_at, $ebet_data->roundNo, 'bet_amount', $bet_amount,
		// 		// 	'real_bet_amount', $real_bet_amount, 'result_amount', $result_amount);

		// 		$this->syncGameLogs(
		// 			$ebet_data->game_type_id,  			# game_type_id
		// 			$ebet_data->game_description_id,	# game_description_id
		// 			$ebet_data->gameshortcode, 			# game_code
		// 			$ebet_data->game_type_id, 			# game_type
		// 			$ebet_data->game, 					# game
		// 			$player_id, 						# player_id
		// 			$ebet_data->username, 				# player_username
		// 			$bet_amount, 						# bet_amount
		// 			$result_amount, 					# result_amount
		// 			null,								# win_amount
		// 			null,								# loss_amount
		// 			$afterBalance,		    # after_balance
		// 			$has_both_side, 					# has_both_side
		// 			$ebet_data->external_uniqueid, 		# external_uniqueid
		// 			$ebet_data->start_at,				# start_at
		// 			$ebet_data->end_at,					# end_at
		// 			$ebet_data->response_result_id,		# response_result_id
		// 			Game_logs::FLAG_GAME,
        //             $extra
		// 		);

		// 	}
		// }

		// $this->CI->utils->debug_log('syncMergeToGameLogs monitor', 'count', $cnt);

		// return array('success' => true);
	}

	public function getGameDescriptionInfo($row, $unknownGame) {

        $game_description_id = null;
        $game_name = $row['gameshortcode'];
        $external_game_id = $row['gameshortcode'];

        $game_type_id = $unknownGame->game_type_id ? $unknownGame->game_type_id : null;
        $game_type = $unknownGame->game_name ? $unknownGame->game_name : self::TAG_CODE_UNKNOWN_GAME;

        return $this->processUnknownGame(
            $game_description_id, $game_type_id,
            $external_game_id, $game_type, $external_game_id);
    }

    public function processGameBetDetail($game_records,$bet_amount,$result_amount){
    	if($game_records->gameType == self::SLOTS){
        	return null;
        }
        $place_of_bet = "N/A";
        $judge_result = "N/A";
        switch ($game_records->gameType) {
            case self::BACCARAT: # Baccarat
                $bet_map = json_decode($game_records->betMap,true);
                $place_of_bet = $this->getBetTypeAndJudgeResult($bet_map, self::BACCARAT, true);
                $judge_result = $this->getBetTypeAndJudgeResult($game_records->judgeResult,self::BACCARAT);
                break;

            case self::DRAGON_TIGER: # Dragon Tiger
                $bet_map = json_decode($game_records->betMap,true);
                $place_of_bet = $this->getBetTypeAndJudgeResult($bet_map, self::DRAGON_TIGER, true);
                $judge_result = $this->getBetTypeAndJudgeResult($game_records->judgeResult,self::DRAGON_TIGER);
                break;

            case self::SICBO: # Sicbo
                $bet_map = json_decode($game_records->betMap,true);
                $place_of_bet = $this->getBetTypeAndJudgeResult($bet_map, self::SICBO, true);
                $judge_result = $this->getBetTypeAndJudgeResult($game_records->judgeResult,self::SICBO);
                break;

            case self::ROUTERY: # Routery
                $bet_map = json_decode($game_records->betMap,true);
                $place_of_bet = $this->getBetTypeAndJudgeResult($bet_map, self::ROUTERY, true);
                $judge_result = $this->getBetTypeAndJudgeResult($game_records->judgeResult,self::ROUTERY);
                break;
        }
        if($game_records->gameType == self::NIUNIU){
        	$bet_details["Withholding Total"] = $game_records->niuniuWithHoldingTotal;
        } else {
        	$bet_details = [
            // "place_of_bet" => $place_of_bet,
            // "bet_amount" => $this->getBetAmount($game_records),
            // "bet_result" => $this->getResultAmount($game_records),
	            "Bet Details" => lang("Bet amount") . ": " . $this->getBetAmount($game_records) . ", " . lang("Place of Bet") . ": " . $place_of_bet . ", " . lang(" Bet result") . ": " .  $this->getResultAmount($game_records) . ", ".lang("Won Side").": " . $judge_result,
	        ];

	        $bet_details["bet_details"][$game_records->external_uniqueid] = [
	            "odds" => null,
	            "win_amount" => ($result_amount > 0) ? $result_amount:0,
	            "bet_amount" =>  $bet_amount,
	            "bet_placed" => $place_of_bet,
	            "won_side" => $judge_result,
	            "winloss_amount" => $result_amount,
	        ];
        } 
        return json_encode($bet_details);
    }

    public function getBetTypeAndJudgeResult($ids,$game_type_id,$is_bet_type = null){
        $list_of_bet_type_and_judge_result = [
            self::BACCARAT => [
                    '60' => 'Player',
                    '66' => 'Player pair',
                    '68' => 'Tie',
                    '80' => 'Banker',
                    '88' => 'Banker pair',
                    '86' => 'bankerLucky6',
                    '81' => 'bankerDragonBonus',
                    '61' => 'playerDragonBonus',
                    '70' => 'Big',
                    '71' => 'Small',
                    '82' => 'BankerOdd',
                    '83' => 'BankerEven',
                    '62' => 'playerOdd',
                    '63' => 'playerEven',
                    '64' => 'super6Player',
                    '65' => 'super6PlayerPair',
                    '67' => 'super6Tie',
                    '69' => 'playerNatural',
                    '72' => 'perfectPair',
                    '73' => 'anyPair',
                    '74' => 'dragonSeven',
                    '75' => 'pandaEight',
                    '84' => 'super6',
                    '85' => 'super6Banker',
                    '87' => 'super6BankerPair',
                    '89' => 'bankerNatural',
                    '90' => 'superTie0',
                    '91' => 'superTie1',
                    '92' => 'superTie2',
                    '93' => 'superTie3',
                    '94' => 'superTie4',
                    '95' => 'superTie5',
                    '96' => 'superTie6',
                    '97' => 'superTie7',
                    '98' => 'superTie8',
                    '99' => 'superTie9',
                ],
            self::DRAGON_TIGER => [
                    '10' => 'Dragon',
                    '11' => 'Tiger',
                    '68' => 'Tie',
                    '12' => 'dragonOdd',
                    '13' => 'dragonEven',
                    '14' => 'tigerOdd',
                    '15' => 'tigerEven',
                    '16' => 'dragonBlack',
                    '17' => 'dragonRed',
                    '18' => 'tigerBlack',
                    '19' => 'tigerRed'
                ],
            self::SICBO => [
                    '100' => 'odd', # 单
                    '101' => 'even', # 双
                    '102' => 'big', # 大
                    '103' => 'small', # 小
                    '104' => "Two Dice Pair 1", # 对子
                    '105' => "Two Dice Pair 2",
                    '106' => "Two Dice Pair 3",
                    '107' => "Two Dice Pair 4",
                    '108' => "Two Dice Pair 5",
                    '109' => "Two Dice Pair 6",
                    '110' => 'Only Triple 1', #围骰
                    '111' => 'Only Triple 2', #围骰
                    '112' => 'Only Triple 3', #围骰
                    '113' => 'Only Triple 4', #围骰
                    '114' => 'Only Triple 5', #围骰
                    '115' => 'Only Triple 6', #围骰
                    '116' => 'Triple', #全围
                    '117' => 'Single Point 4', # 点
                    '118' => 'Single Point 5', # 点
                    '119' => 'Single Point 6', # 点
                    '120' => 'Single Point 7', # 点
                    '121' => 'Single Point 8', # 点
                    '125' => 'Single Point 9', # 点
                    '126' => 'Single Point 10', # 点
                    '127' => 'Single Point 11', # 点
                    '128' => 'Single Point 12', # 点
                    '129' => 'Single Point 13', # 点
                    '130' => 'Single Point 14', # 点
                    '131' => 'Single Point 15', # 点
                    '132' => 'Single Point 16', # 点
                    '133' => 'Single Point 17', # 点
                    '134' => 'Single Number', # 单点数
                    '135' => 'Single Number 2', # 单点数
                    '136' => 'Single Number 3', # 单点数
                    '137' => 'Single Number 4', # 单点数
                    '138' => 'Single Number 5', # 单点数
                    '139' => 'Single Number 6', # 单点数
                    '140' => 'Two dice combinations 1-2', # 组合
                    '141' => 'Two dice combinations 1-3', # 组合
                    '142' => 'Two dice combinations 1-4', # 组合
                    '143' => 'Two dice combinations 1-5', # 组合
                    '144' => 'Two dice combinations 1-6', # 组合
                    '145' => 'Two dice combinations 2-3', # 组合
                    '146' => 'Two dice combinations 2-4', # 组合
                    '147' => 'Two dice combinations 2-5', # 组合
                    '148' => 'Two dice combinations 2-6', # 组合
                    '149' => 'Two dice combinations 3-4', # 组合
                    '150' => 'Two dice combinations 3-5', # 组合
                    '151' => 'Two dice combinations 3-6', # 组合
                    '152' => 'Two dice combinations 4-5', # 组合
                    '153' => 'Two dice combinations 4-6', # 组合
                    '154' => 'Two dice combinations 5-6', # 组合
                    '155' => 'Two same number', # 二同号
                    '156' => 'Three different number', # 三同号
                    '157' => 'HIGHER',
                    '158' => 'LOWER',
                    '159' => 'SNAP',
                    '160' => '3、4、5、6',
                    '161' => '7、8、9、10',
                    '162' => '11、12、13、14',
                    '163' => '15、16、17、18',
                ],
            self::ROUTERY => [
                    '200' => 'Straight up',
                    '201' => 'Split bet',
                    '202' => 'Street bet',
                    '203' => 'Corner bet',
                    '204' => '3 numbers bet',
                    '205' => '4 numbers',
                    '206' => 'Line bet',
                    '207' => 'Column',
                    '208' => 'Dozen',
                    '209' => 'Red ',
                    '210' => 'Black ',
                    '211' => 'Odd ',
                    '212' => 'Even ',
                    '213' => 'Big ',
                    '214' => 'Small',
                ],
        ];

        if(isset($is_bet_type)){
        	$result = [];
        	if(!empty($ids)){
        		foreach ($ids as $key => $id) {
        			$betType = $id['betType'];
        			$amount = $id['betMoney'];
        			$betPlace = $list_of_bet_type_and_judge_result[$game_type_id][$betType];
					//$result[] = $betPlace . ":" . $amount;

					# change the bet detail format for ticker OGP-14174

					$imploded_bet_number = isset($id['betNumber']) ? implode(',',$id['betNumber']) : null;

					$result[] = $amount . " " . $betPlace . "[".$imploded_bet_number."]";
        		}
			}
			
			return implode(", ",$result);

        }else{
            $ids = json_decode($ids);
            if(count($ids) > 1){
                $result = [];
                foreach ($ids as $id) {
                    $result[$id] = $list_of_bet_type_and_judge_result[$game_type_id][$id];
                }
                return implode(', ', $result);
            }else{
                return $list_of_bet_type_and_judge_result[$game_type_id][$ids[0]];
            }
        }

    }

	public function queryForwardGame($playerName = NULL, $extra = array()) {
        $game_type = !empty($extra['game_type']) ? $extra['game_type'] : null;
        $game_code = !empty($extra['game_code']) ? $extra['game_code'] : null;
        $game_mode = !empty($extra['game_mode']) ? $extra['game_mode'] : null;
        $is_demo_mode = $this->utils->isDemoMode($game_mode);

        switch ($game_type) {
            case parent::GAME_TYPE_LIVE_DEALER:
                $category = 'Live';
                break;
            case parent::GAME_TYPE_LOTTERY:
                $category = 'Lottery';
                break;
            case parent::GAME_TYPE_FISHING_GAME:
                $category = 'Fishing';
                break;
            case parent::GAME_TYPE_SLOTS:
                $category = 'Slot';
                break;
            case parent::GAME_TYPE_SPORTS:
            case parent::GAME_TYPE_E_SPORTS:
                $category = 'Sportbook';
                break;
            default:
                $category = 'Live';
                break;
        }

		$url = $this->getSystemInfo('flash_url');
		if($this->enableQueryGameLaunchUrl){ #The following API Scheduled for removal on October 28, 2024  OGP-34195
			$resultQueryGameLaunchUrl = $this->queryGameLaunchUrl();
			if($resultQueryGameLaunchUrl['success'] && isset($resultQueryGameLaunchUrl['launchUrl'])){
				$url = $resultQueryGameLaunchUrl['launchUrl'];
			}
		}

		if ( ! $playerName) return array('success' => true, 'url' => $url . '?' . http_build_query(array('channelId' => $this->channel_id)));

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		if($this->always_lowercase){
			$gameUsername=strtolower($gameUsername);
		}

		$token = $this->getPlayerTokenByUsername($playerName);
		$timestamp = time();

        $language = $this->getSystemInfo('language', $extra['language']);

		# commenting this code to overide language if language in extra info is set
		// if(isset($extra['language']) && !empty($extra['language'])){
        //     $language=$this->getLauncherLanguage($extra['language']);
        // }else{
        //     $language=$this->getLauncherLanguage($language);
        // }
		$language=$this->getLauncherLanguage($language);

		$params = array(
			'ts' 			=> $timestamp,
			'username' 		=> $gameUsername,
			'accessToken' 	=> $token,
            'language'      => $language,
		);

		/* if(isset($extra['gameType'])){
			$params['gameType'] = $extra['gameType'];
		} */

		/* if(isset($extra['game_mode']) && strtolower($extra['game_mode']) != 'real'){
			$params['mode'] = 'trial';
		} */

        if ($is_demo_mode) {
            $resultQueryGameDemoUrl = $this->queryGameDemohUrl();
            $url = $resultQueryGameDemoUrl['launchUrl'];

            $params = [
                'language' => $language,
            ];
        }

        if (!empty($game_code) && $game_code != '_null') {
            if (($game_type == self::GAME_TYPE_SPORTS || $game_type == self::GAME_TYPE_E_SPORTS) && $game_code != 'WEB_GAME') {
                $params['gameCode'] = $extra['game_code'];
            }

            $params['tableCode'] = $extra['game_code'];
        }

        if (!empty($game_type)) {
            $params['category'] = $category;
        }

		//this is for gamegateway api
		/* if(isset($extra['game_type']) && !empty($extra['game_type'])){			
			$url .= '?' . http_build_query($params);
			$gameType = $this->getGameType($extra['game_type']);

			if(!empty($gameType)) {
				$url .= '&gameType='.implode(',', $gameType); 
			}
			
			return array('success' => true, 'url' => $url);
		} */

        $redirect_url_params = $params;
        unset($redirect_url_params['gameCode'], $redirect_url_params['tableCode']);
        $redirect_url = $url . '?' . http_build_query($redirect_url_params);
        $params['redirecturl'] = !empty($this->redirect_url) ? $this->redirect_url : $redirect_url;

        $game_launch_url = $url . '?' . http_build_query($params);

        if(!$is_demo_mode && $this->enablePlayerLaunch){
			$resultQueryGameLaunchUrl = $this->playerLaunch($params, $extra);
			if($resultQueryGameLaunchUrl['success'] && isset($resultQueryGameLaunchUrl['launchUrl'])){
				$game_launch_url = $resultQueryGameLaunchUrl['launchUrl'];
			}
		}

		return array('success' => true, 'url' => $game_launch_url);

	}

	public function playerLaunch($params, $extra){
		$context = array(
			'callback_obj' 		=> $this,
			'callback_method' 	=> 'processResultForQueryGameLaunchUrl',
			'game_username' 	=> $params['username'],
		);

		$timestamp 		= time();
		$signature_key 	= $params['username'].$timestamp;
		$signature 		= $this->encrypt($signature_key);

		$this->CI->utils->debug_log('signature_key', $signature_key, 'key', $this->private_key);
		$request_params = array(
			'channelId' 	=> $this->channel_id,
			'signature' 	=> $signature,
			'timestamp' 	=> $timestamp,
			'clientIP'      => $this->CI->input->ip_address(),
			'username'      => $params['username'],
			'subChannelId'  => $this->sub_channel_id,
			'language'      => $params['language'],
			'uiMode'        => "auto"
		);
		if(isset($params['tableCode'])){
			$request_params['tableCode'] = $params['tableCode'];
		}

		if(isset($params['category'])){
			$request_params['category'] = $params['category'];
		}

		if(isset($params['redirecturl'])){
			$request_params['redirecturl'] = $params['redirecturl'];
		}

		if(!empty($this->uiHide)){
			$request_params['uiHide'] = $this->uiHide;
		}
		return $this->callApi(self::API_queryForwardGameV2, $request_params, $context);
	}

	public function queryGameLaunchUrl(){
		$context = array(
			'callback_obj' 		=> $this,
			'callback_method' 	=> 'processResultForQueryGameLaunchUrl',
		);

		$timestamp 		= time();
		$signature_key 	= $this->channel_id.$timestamp;
		$signature 		= $this->encrypt($signature_key);

		$this->CI->utils->debug_log('timestamp', $timestamp, 'key', $this->private_key);

		$params = array(
			'channelId' 	=> $this->channel_id,
			'signature' 	=> $signature,
			'timestamp' 	=> $timestamp,
			'currency'      => $this->currency
		);

		return $this->callApi(self::API_queryForwardGame, $params, $context);
	}

	public function processResultForQueryGameLaunchUrl($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $success = false;
        $result = [];
        if(isset($resultArr['launchUrl'])){
        	$success = true;
        	$result['launchUrl'] = $resultArr['launchUrl'];
        }
        return array($success, $result);
    }

    public function queryGameDemohUrl() {
		$context = array(
			'callback_obj' 		=> $this,
			'callback_method' 	=> 'processResultForQueryGameDemohUrl',
		);

		$timestamp 		= time();
		$signature_key 	= $this->channel_id.$timestamp;
		$signature 		= $this->encrypt($signature_key);

		$this->CI->utils->debug_log('timestamp', $timestamp, 'key', $this->private_key);

		$params = array(
			'channelId' 	=> $this->channel_id,
			'signature' 	=> $signature,
			'timestamp' 	=> $timestamp,
			'currency'      => $this->currency,
		);

		return $this->callApi(self::API_queryDemoGame, $params, $context);
	}

	public function processResultForQueryGameDemohUrl($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $success = false;
        $result = [];
        if(isset($resultArr['launchUrl'])){
        	$success = true;
        	$result['launchUrl'] = $resultArr['launchUrl'];
        }
        return array($success, $result);
    }

	public function getGameType($gameType){
		$type = '';
		switch ($gameType) {		
			case 'live_dealer':
				$type =$this->game_type_live_dealer; 
                break;
            case 'slots':            
				$type =$this->game_type_slots;
                break;       
            default:
				$type = null; // this must be null, because once you put invalid game type, it shows error in the game                
                break;
        }
		return (array)$type;
	}

    public function getLauncherLanguage($lang){
        $this->CI->load->library("language_function");
        switch ($lang) {
            case 'cn':
			case 'zh-cn':
            case LANGUAGE_FUNCTION::INT_LANG_CHINESE:
                $lang = 'zh_cn';
                break;
            case 'en':
			case 'en-us':
            case LANGUAGE_FUNCTION::INT_LANG_ENGLISH:
                $lang = 'en_us';
                break;
            case 'vi':
			case 'vi-vn':
            case LANGUAGE_FUNCTION::INT_LANG_VIETNAMESE:
                $lang = 'vi_vn';
                break;
            case 'th':
			case 'th-th':
            case LANGUAGE_FUNCTION::INT_LANG_THAI:
                $lang = 'th_th';
                break;
			case 'id':
			case 'id-id':
			case LANGUAGE_FUNCTION::INT_LANG_INDONESIAN:
				$lang = 'in_id';
				break;
			case 'tr':
			case 'tr-tr':
				$lang = 'tr_tr';
				break;
			case 'es':
			case 'es-es':
				$lang = 'es_es';
				break;
            default:
                $lang = 'en_us';
                break;
        }

        return $lang;
    }

	// public function batchQueryPlayerBalance($playerNames, $syncId = null) {

	// 	if( empty($playerNames)){

	// 		// load available names then call batchQueryPlayerBalance
	// 		return $this->batchQueryPlayerBalanceOnlyAvailable($syncId);

	// 	} else {

	// 		$result = $this->batchQueryPlayerBalanceOneByOne($playerNames, $syncId);

	// 		if (isset($result['balances']) && ! empty($result['balances'])) {

	// 			$players = $result['balances'];

	// 			$self = $this;
	// 			foreach ($players as $playerName => $balance) {

	// 				$playerId = $this->getPlayerIdFromUsername($playerName);

	// 				if ( ! empty($playerId)) {
	// 					$this->CI->lockAndTrans(Utils::LOCK_ACTION_BALANCE, $balance . $playerId, function () use ($self, $playerId, $balance) {
	// 						$self->updatePlayerSubwalletBalance($playerId, floatval($balance));
	// 					});
	// 				}

	// 			}
	// 		}

	// 		return array('success' => true);
	// 	}

	// }

	public function batchQueryPlayerBalance($playerNames, $syncId = null) {

		$result = array('success' => false);

		if(empty($playerNames)){
			$result['success']=true;
			return $result;
		}

		if(!is_array($playerNames)){
			$playerNames=[$playerNames];
		}

		$game_usernames = array_filter(array_map(function($playerName) {
			return $this->getGameUsernameByPlayerUsername($playerName);
		}, $playerNames));

		$batches = array_chunk($game_usernames, 150);
		foreach ($batches as $batch) {

			$game_usernames_str = implode(',', $batch);

			$context = array(
				'callback_obj' 		=> $this,
				'callback_method' 	=> 'processResultForbatchQueryPlayerBalance',
			);

			$signature_key 	= $game_usernames_str;
			$signature 		= $this->encrypt($signature_key);

			$params = array(
				'username' 		=> $game_usernames_str,
				'channelId' 	=> $this->channel_id,
				'signature' 	=> $signature,
			);

			$result = $this->callApi(self::API_batchQueryPlayerBalance, $params, $context);

		}

		return $result;
	}

	public function processResultForbatchQueryPlayerBalance($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultJson);

		if ($success && isset($resultJson['results']) && ! empty($resultJson['results'])) {
			$result = $resultJson['results'];
			$self = $this;
			foreach ($result as $record) {
				$playerId = $this->getPlayerIdInGameProviderAuth($record['username']);
				$balance = $record['money'];
				if ($playerId) {
					// $this->CI->lockAndTransForPlayerBalance($playerId, function () use ($self, $playerId, $balance) {
						$self->updatePlayerSubwalletBalance($playerId, floatval($balance));
					// });
				}
			}
		}

		return array($success);
	}

	public function batchQueryPlayerBetLimit($playerNames, $gameType = '', $gameId = '') {

		$game_usernames = array_filter(array_map(function($playerName) {
			return $this->getGameUsernameByPlayerUsername($playerName);
		}, $playerNames));

		foreach ($playerNames as $playerName) {

			$resultObj = $this->getBetLimit($playerName, ''); # TODO: GAME ID WHEN NEO ALREADY FIXED IT

			if ($resultObj['success'] && isset($resultObj['limit'])) {
				$games = array_column($resultObj['limit'], null, 'gameId');

				if ($gameType != '') {
					$games = array_filter($games, function($game) use ($gameType) {
						return substr($game['gameId'], 0, 1) == substr($gameType, 0, 1);
					});
				}

				$gameIds = array_keys($games);
				// sort($gameIds);

				$result['gameIds'] = $gameIds;

				$result['limit'] = $gameId ? (isset($games[$gameId]) ? $games[$gameId] : null) : reset($games);

				$result['success'] = true;
				break;
			}
		}

		return $result;
	}

	// public function batchUpdatePlayerBetLimit($playerNames, $gameType = '', $gameId = '') {

	// 	$game_usernames = array_filter(array_map(function($playerName) {
	// 		return $this->getGameUsernameByPlayerUsername($playerName);
	// 	}, $playerNames));

	// 	foreach ($playerNames as $playerName) {

	// 		$resultObj = $this->getBetLimit($playerName, ''); # TODO: GAME ID WHEN NEO ALREADY FIXED IT

	// 		if ($resultObj['success'] && isset($resultObj['limit'])) {
	// 			$games = array_column($resultObj['limit'], null, 'gameId');

	// 			if ($gameType != '') {
	// 				$games = array_filter($games, function($game) use ($gameType) {
	// 					return substr($game['gameId'], 0, 1) == substr($gameType, 0, 1);
	// 				});
	// 			}

	// 			$gameIds = array_keys($games);
	// 			// sort($gameIds);

	// 			$result['gameIds'] = $gameIds;

	// 			$result['limit'] = $gameId ? (isset($games[$gameId]) ? $games[$gameId] : null) : reset($games);

	// 			$result['success'] = true;
	// 			break;
	// 		}
	// 	}

	// 	return $result;
	// }

	/** 
	 * Process Signature Key
	 * 
	 * @return string $signature_key
	*/
	private function processSignatureKey($game_username,$channel_id=null,$timestamp=null){
		$signature_key = "";

		if(! empty($game_username)){
			$signature_key .= $game_username;
		}
		
		if(! empty($channel_id)){
			$signature_key .= $channel_id;
		}

		if(! empty($timestamp)){
			$signature_key .= $timestamp;
		}

		return $signature_key;
	}

	public function getBetLimit($playerName) {

		$game_username = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' 		=> $this,
			'callback_method' 	=> 'processResultForGetBetLimit',
			'playerName' 		=> $playerName,
		);

		$channel_id     = $this->channel_id;
		$timestamp = time();

		$signature_key = $this->processSignatureKey($game_username,$channel_id,$timestamp);
		$signature 		= $this->encrypt($signature_key);

		$params = array(
			'username' 		=> $game_username,
			'channelId' 	=> $channel_id,
			'signature' 	=> $signature,
			'timestamp' 	=> $timestamp,
		);

		return $this->callApi(self::API_getBetLimit, $params, $context);
	}

	public function processResultForGetBetLimit($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);

		$result = array('status' => $resultJson['status']);
		if ($success && isset($resultJson['limit'])) {
			$games = array_column($resultJson['limit'], null, 'gameId');
			$result['gameIds'] = array_keys($games);
			foreach ($games as $game_id => $game) {
				foreach ($game as $key => $value) {

					$initial = substr($game_id, 0, 1);

					switch ($initial) {
						case 'B':
							$game_name = 'baccarat';
							break;
						case 'D':
							$game_name = 'dragonTiger';
							break;
						case 'S':
							$game_name = 'sicbo';
							break;
						case 'R':
							$game_name = 'rouletteWheel';
							break;
						default:
							$game_name = null;
							break;
					}

					if ($game_name) {
						$result['limit'][$game_name][$game_id][$game_name . 'BetLimit.' . $key] = $value;
					}

				}
			}
			$result['success'] = true;
		}

		return array($success, $result);
	}

	public function updateBetLimit($playerName, $params) {

		$game_username = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' 		=> $this,
			'callback_method' 	=> 'processResultForUpdateBetLimit',
			'playerName' 		=> $playerName,
		);

		$channel_id = $this->channel_id;
		$timestamp = time();

		$signature_key = $this->processSignatureKey($game_username,$channel_id,$timestamp);
		$signature 		= $this->encrypt($signature_key);

		$params = array_merge(array(
			'username' 		=> $game_username,
			'channelId' 	=> $channel_id,
			'signature' 	=> $signature,
			'timestamp' 	=> $timestamp,
		), $params);

		return $this->callApi(self::API_updateBetLimit, $params, $context);
	}

	public function processResultForUpdateBetLimit($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);

		return array($success, $resultJson);
	}

	public function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null) {
		return array(false, null);
	}

	public function syncPlayerAccount($username, $password, $playerId) {
		return $this->returnUnimplemented();
	}

	public function changePassword($playerName, $oldPassword, $newPassword) {
		//change password
		$success=true;
		$playerId = $this->getPlayerIdInPlayer($playerName);
		if(!empty($playerId)){
			$this->updatePasswordForPlayer($playerId, $newPassword);
		}

		return array('success' => $success);
		// return $this->returnUnimplemented();
	}

	//===start blockPlayer=====================================================================================
	// public function blockPlayer($playerName) {
	// 	$playerName = $this->getGameUsernameByPlayerUsername($playerName);
	// 	$success = $this->blockUsernameInDB($playerName);
	// 	return array("success" => true);
	// }
	//===end blockPlayer=====================================================================================

	//===start unblockPlayer=====================================================================================
	// public function unblockPlayer($playerName) {
	// 	$playerName = $this->getGameUsernameByPlayerUsername($playerName);
	// 	$success = $this->unblockUsernameInDB($playerName);
	// 	return array("success" => true);
	// }
	//===end unblockPlayer=====================================================================================

	public function logout($playerName, $password = null) {

        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		if($this->always_lowercase){
			$gameUsername=strtolower($gameUsername);
		}

		$channel_id     = $this->channel_id;
		$timestamp = time();

		$signature_key = $this->processSignatureKey($gameUsername,$channel_id,$timestamp);
		$signature 		= $this->encrypt($signature_key);

			$params = array(
                'channelId' 	=> $channel_id,
				'username' 		=> $gameUsername,
                'timestamp'     => $timestamp,
				'signature' 	=> $signature
			);

            return $this->callApi(self::API_logout, $params);

		//return $this->returnUnimplemented();
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

	public function checkLoginToken($playerName, $token) {
		return $this->returnUnimplemented();
	}

	public function totalBettingAmount($playerName, $dateTimeFrom, $dateTimeTo) {
		return $this->returnUnimplemented();
	}

	public function queryTransaction($transactionId, $extra) {
		//call transaction

		$context = array(
			'callback_obj' 		=> $this,
			'callback_method' 	=> 'processResultForQueryTransaction',
			'external_transaction_id'     => $transactionId,
			'call_qt_iteration' => isset($extra["call_qt_iteration"]) ? $extra["call_qt_iteration"] : 0 // use for calling the rechargestatus api, if the api response got -1 based on this ticket OGP-22424
		);

		// $timestamp 		= time();
		$signature_key 	= $transactionId;
		$signature 		= $this->encrypt($signature_key);

		$params = array(
			'channelId' 	=> $this->channel_id,
			'rechargeReqId' => $transactionId,
			'signature' 	=> $signature,
		);

		return $this->callApi(self::API_queryTransaction, $params, $context);
	}

	public function processResultForQueryTransaction($params) {

		

		$this->CI->utils->debug_log('params of recharge', $params['params']);

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		// $game_username = $this->getVariableFromContext($params, 'game_username');
		// $playerName = $this->getVariableFromContext($params, 'playerName');
		// $amount = $this->getVariableFromContext($params, 'amount');
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		//call successfully, don't check status
		//$success = !empty($resultJson);// $this->processResultBoolean($responseResultId, $resultJson, $playerName);

		$success = @$resultJson['status'] ==  self::STATUS_CODE['Success'] ? true : false;

		$status=null;

		$result = array(
			'response_result_id' => $responseResultId,
			'external_transaction_id'=>$external_transaction_id,
			'status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id'=>self::REASON_UNKNOWN
		);

		if ($success) {
			$status=$resultJson['status'];
			if($status=='200'){
				$result['status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
			}elseif($status=='0'){
				$result['status']=self::COMMON_TRANSACTION_STATUS_PROCESSING;
			}
		} else {
			$error_code = @$resultJson['status'];
			switch($error_code) {
				case '202' :
					$result['reason_id'] = self::REASON_AGENT_NOT_EXISTED;
					break;
				case '203' :
				case '-1' :

					$call_qt_iteration = $this->getVariableFromContext($params, 'call_qt_iteration'); 

					if($call_qt_iteration < $this->recharge_status_retry_times) { // request 2 more times for final confirmation. based on ticket (OGP-22424)

						// add sleep time for 5 seconds (it is recommended that you make the request after 5 seconds)
						sleep($this->wait_seconds);

						$extra["call_qt_iteration"] = $call_qt_iteration++; // increment

						$this->queryTransaction($external_transaction_id, $extra);

					} else {

						$result['reason_id'] = self::REASON_INVALID_TRANSACTION_ID;

					}

					break;
				case '204' :
					$result['reason_id'] = self::REASON_INVALID_TRANSACTION_ID;
					break;
				case '4003' :
					$result['reason_id'] = self::REASON_GAME_PROVIDER_INTERNAL_PROBLEM;
					break;
				case '4026' :
					$result['reason_id'] = self::REASON_INVALID_KEY;
					break;
				case '4027' :
					$result['reason_id'] = self::REASON_IP_NOT_AUTHORIZED;
					break;
				case '4028' :
				case '4037' :
					$result['reason_id'] = self::REASON_NOT_FOUND_PLAYER;
					break;
			}
			$result['status']=self::COMMON_TRANSACTION_STATUS_DECLINED;
		}

		//load from result
		// $external_transaction_id=$rechargeReqId;
		// //if empty, use our transfer id
		// if(empty($external_transaction_id)){
		// 	$external_transaction_id=$this->getVariableFromContext($params, 'transfer_secure_id');
		// }

		// $result['after_balance']=$afterBalance;
		#$result['external_transaction_id']=$external_transaction_id;
		#$result['response_result_id'] = $responseResultId;
		#$result['status']=$status;

		return array($success, $result);
	}
	public function listPlayers($playerNames) {
		return $this->returnUnimplemented();
	}

	public function resetPlayer($playerName) {
		return $this->returnUnimplemented();
	}

	public function revertBrokenGame($playerName) {
		return $this->returnUnimplemented();
	}


	/** 
	 * This api is to query the current amount of money for users who have logged in within a period of time.
	* Note: Because the amount of data may be too large, it is recommended to bring the following parameters when requesting to reduce the query time.
	* (startTimeStr, endTimeStr, pageNum, pageSize)
	*
	* @param datetime $timestamp
	* @param string $playerName
	* @param datetime $start
	* @param datetime $end
	* 
	* @return mixed
	*/
	public function getUserTransaction($timestamp,$playerName,$start=null,$end=null)
	{
		$timestamp  = time();
		//$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$gameUsername = $playerName;
		$signature_key 	= $gameUsername . $timestamp;
		$signature 		= $this->encrypt($signature_key);
		$now = new DateTime();
		$start = $this->CI->utils->formatDateForMysql($now)." 00:00:00";
		$end = $this->CI->utils->formatDateForMysql($now)." 23:59:59";

		$context = [
			'callback_obj' => $this,
			'callback_method' => 'processResultGetUserTransaction',
			'playerName' => $playerName
		];

		$params = [
			'channelId' => $this->channel_id,
			'timestamp' => $timestamp,
			'signature' => $signature,
			'username' => $gameUsername,
			'startTimeStr' => $start,
			'endTimeStr' => $end,
			'pageNum' => 1,
			'pageSize' => 5000 // max
		];

		if(! empty($start)){
			$params['startTimeStr'] = $start;
		}

		if(! empty($end)){
			$params['endTimeStr'] = $end;
		}

		if(! empty($timestamp)){
			$params['timestamp'] = $timestamp;
		}

		$this->CI->utils->debug_log(__METHOD__. ' params: ',$params,'and sleeping in seconds',$this->sync_sleep_time);

		sleep($this->sync_sleep_time);

		# we sleep for 5 seconds, so we need to reconnect to database
		$this->CI->db->_reset_select();
		$this->CI->db->reconnect();
		$this->CI->db->initialize();

		return $this->callApi(self::API_getUserTransaction,$params,$context);
	}

	/**
	 * processResultGetUserTransaction
	 * 
	 * @param array $params
	 * 
	 * @return mixed
	 */
	public function processResultGetUserTransaction($params)
	{
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);

		if($success){
			$this->CI->utils->debug_log(__METHOD__. ' resultJson: ',$resultJson,'playerName: ',$playerName);

			return [$success,$resultJson];
		}else{
			$this->CI->utils->error_log(__METHOD__. ' ERROR with info resultJson: ',$resultJson,'playerName: ',$playerName);

			return false;
		}
	}

	/**
	 * Process After Balance
	 * 
	 * @param int $roundno
	 * @param datetime $timestamp
	 * @param string $playerName
	 * @param datetime $start
	 * @param datetime $end
	 * @param int $index
	 * @param boolean $returnAll
	 * @param boolean $useIndex
	 * 
	 * @return array
	 */
	public function processAndGetAfterBalanceAfterBet($roundno,$timestamp,$playerName,$start=null,$end=null,$index=0,$returnAll=false,$useIndex=false)
	{
		$datetime = new DateTime($timestamp);
		$timepoint = strtotime($datetime->format('Y-m-d H:i:s'))*1000;

		if(! empty($start)){
			$startObject = new DateTime($start);

			$start = $this->CI->utils->formatDateForMysql($startObject)." 00:00:00";
		}

		if(! empty($end)){
			$endObject = new DateTime($end);

			$end = $this->CI->utils->formatDateForMysql($endObject)." 23:59:59";
		}

		$result = $this->getUserTransaction($timepoint,$playerName,$start,$end);

		$this->CI->utils->debug_log(__METHOD__. ' result: ',$result);

		if($result){
			if(isset($result['transactions'])){
				if($returnAll){
					return $result['transactions'];
				}else{
					if($index == 0 && $useIndex){
						if(isset($result['transactions'][$index]['transAfterBalance'])){
							return $result['transactions'][$index]['transAfterBalance'];
						}
					}else{
						$afterBalance = null;
						if(isset($result['transactions'])){
							foreach($result['transactions'] as $transaction){
								if(isset($transaction['roundNo']) && $transaction['roundNo'] == $roundno){
									$afterBalance =  $transaction['transAfterBalance'];
								}
							}
						}
						return ['success'=>true,'after_balance'=>$afterBalance];
					}
				}
			}
		}

		return ['success'=>false];
	}

	/**
	 * Sync After Balance via service
	 * @see sync_after_balance.sh
	 * @see Sync_after_balance@start_sync_after_balance
	 */
	public function syncAfterBalance($token)
	{
		# check the extra info first
		if($this->sync_after_balance){
			$dateTimeFrom = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
			$dateTimeTo = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
			$dateTimeFrom->modify($this->getDatetimeAdjust());
			//observer the date format
			$startDate = $dateTimeFrom->format('Y-m-d H:i:s');
			$endDate = $dateTimeTo->format('Y-m-d H:i:s');
	
			$originalRecords = $this->queryRealOriginalGameLogsAfterBalanceIsNull($startDate,$endDate);
	
			$data_count = 0;
			$mergeToGamelogs = [];
	
			foreach($originalRecords as $key => $record){
	
				$createTime = $record['createTime'];
				$payoutDate = $record['payoutTime'];
				$playerName = $record['username'];
				$roundNo = $record['roundNo'];
				$external_uniqueid = $record['external_uniqueid'];
	
				$result = $this->processAndGetAfterBalanceAfterBet($roundNo,$payoutDate,$playerName,$createTime,$payoutDate);

				if(isset($result['success']) && $result['success'] && isset($result['after_balance'])){
					# update after balance
					$record['balance'] = $result['after_balance'];
					$this->CI->original_game_logs_model->updateRowsToOriginal(self::ORIGINAL_LOGS_TABLE_NAME, $record);

					# consolidate all gamelogs to update in gamelogs
					$mergeToGamelogs[$key]['after_balance'] = $result['after_balance'];
					$mergeToGamelogs[$key]['external_uniqueid'] = $external_uniqueid;
					$data_count++;
				}
			}

			# update gamelogs
			$this->updateAfterBalanceOnGamelogs($mergeToGamelogs);

			unset($originalRecords);
			unset($data);
			# add logs
			$this->CI->utils->debug_log("EBET after balance updated count: ",$data_count,"start_date: ",$startDate,"end_date: ",$endDate);

			return array("success" => true,"data_count"=>$data_count);
		}
	}

    public function queryRealOriginalGameLogsAfterBalanceIsNull($dateFrom, $dateTo){
        $this->CI->load->model(array('original_game_logs_model'));
        $table = self::ORIGINAL_LOGS_TABLE_NAME;
        $sql = <<<EOD
            SELECT
                id,
                roundNo,
                username,
                payoutTime,
                createTime,
                external_uniqueid
            FROM
             {$table} USE INDEX (idx_game_date)
              WHERE payoutTime >= ?
            AND payoutTime <= ? and balance IS NULL
EOD;

        $params=[
            $dateFrom,
            $dateTo
        ];

        return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    }

}

/*end of file*/
