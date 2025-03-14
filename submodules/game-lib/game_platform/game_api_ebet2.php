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
 * @deprecated
 * @copyright 2013-2022 tot
 */

class Game_api_ebet2 extends Abstract_game_api {

	public function getPlatformCode() {
		return EBET2_API;
	}

	private $api_url;
	private $channel_id;
	private $public_key;
	private $private_key;
	private $forward_sites = null;

	private $sub_channel_id;
	private $html5_enabled;

	const ALWAYS_LOWERCASE=TRUE;

	const STATUS_NOT_FOUND_USER='4037';
	const STATUS_USER_EXISTS='401';

	private $ebet_game_logs_model;

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

		$this->always_lowercase= $this->getSystemInfo('always_lowercase', self::ALWAYS_LOWERCASE);

		if (empty($this->default_lang)) {
			$this->default_lang = '1';
		}

		if (empty($this->html5_enabled)) {
			$this->html5_enabled = FALSE;
		}

		$this->rsa = new Crypt_RSA();
		$this->rsa->setSignatureMode(CRYPT_RSA_SIGNATURE_PKCS1);
		$this->rsa->setHash('md5');

		$this->CI->load->model('ebet2_game_logs');
		$this->ebet_game_logs_model=$this->CI->ebet2_game_logs;
	}

	const API_getBetLimit = 'getBetLimit';
	const API_updateBetLimit = 'updateBetLimit';

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
			],
			'DragonTiger'=>[
				'gameId'=>'D',
				'dragonMin'=>null,
				'dragonMax'=>null,
				'tigerMin'=>null,
				'tigerMax'=>null,
				'tieMin'=>null,
				'tieMax'=>null,
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

		$rechargeReqId = $this->generateTransferId($transfer_secure_id);

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

		$amount = - $amount;

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		if($this->always_lowercase){
			$gameUsername=strtolower($gameUsername);
		}

		$rechargeReqId = $this->generateTransferId($transfer_secure_id); //'WIT' . date('YmdHis') . random_string('numeric', 8);

		$context = array(
				'callback_obj' 		=> $this,
				'callback_method' 	=> 'processResultForRecharge',
				'game_username' 	=> $gameUsername,
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
				'money' 		=> $amount,
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

			$playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
			if ($playerId) {
				if(isset($resultJson['money'])){
					$afterBalance = floatval( $resultJson['money'] );
					$this->insertTransactionToGameLogs($playerId, $gameUsername, $afterBalance, $amount, $responseResultId, (($type == 'IN') ? $this->transTypeMainWalletToSubWallet() : $this->transTypeSubWalletToMainWallet()));

				}else{
					$this->CI->utils->error_log('lost money field in result', $params['params']);
				}
			} else {
				$this->CI->utils->error_log('cannot get player id from ' . $gameUsername . ' getPlayerIdInGameProviderAuth');
			}
			$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
		} else {
			$error_code = @$resultJson['status'];
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

		//load from result
		$external_transaction_id=$rechargeReqId;
		//if empty, use our transfer id
		if(empty($external_transaction_id)){
			#	$external_transaction_id=$this->getVariableFromContext($params, 'transfer_secure_id');
		}

		$result['after_balance']=$afterBalance;
		#$result['external_transaction_id']=$external_transaction_id;
		#$result['response_result_id'] = $responseResultId;

		return array($success, $result);
	}

	# INCOMING API CALL #############################################################################################################################























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
			'lang' 			=> $this->default_lang,
			'signature' 	=> $signature,
			'timestamp' 	=> $timestamp,
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

		$game_username = $this->getGameUsernameByPlayerUsername($playerName);

		if($this->always_lowercase){
			$game_username=strtolower($game_username);
		}

		$context = array(
			'callback_obj' 		=> $this,
			'callback_method' 	=> 'processResultForIsPlayerExist',
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

		return $this->callApi(self::API_isPlayerExist, $params, $context);
	}

	public function processResultForIsPlayerExist($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$game_username = $this->getVariableFromContext($params, 'game_username');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);

		$exist=true;

		if(isset($resultJson['status']) && $resultJson['status']==self::STATUS_NOT_FOUND_USER){
			$success=true;
			$exist=false;
		}

		return array( ! empty($resultJson), array('exist' => $exist));
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

			$params = array(
				'startTimeStr' 	=> $startDate->format('Y-m-d H:i:s'),
				'endTimeStr' 	=> $endDate->format('Y-m-d H:i:s'),
				'channelId' 	=> $this->channel_id,
				'subChannelId' 	=> $this->sub_channel_id,
				'pageNum' 		=> $currentPage++,
				'pageSize' 		=> $pageSize,
				'signature' 	=> $signature,
				'timestamp' 	=> $timestamp,
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

	public function processResultForSyncOriginalGameLogs($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultJson);
		$result = null;

		if ($success) {


			$gameRecords = isset($resultJson['betHistories']) ? $resultJson['betHistories'] : array();

			if ($gameRecords) {

				array_walk($gameRecords, function(&$gameRecord) use ($responseResultId) {

					$gameRecord['realBet'] = array_sum(array_column($gameRecord['betMap'], 'betMoney'));

					foreach ($gameRecord as &$value) {
						if (is_array($value)) {
							$value = json_encode($value);
						}
					}

					$gameshortcode 		= $gameRecord['gameType'];
					$uniqueId 			= $gameRecord['betHistoryId'];
					$external_uniqueid 	= $uniqueId;

					$gameRecord['origCreateTime'] 	= $gameRecord['createTime'];
					$gameRecord['origPayoutTime'] 	= $gameRecord['payoutTime'];

					$gameRecord['createTime'] = date('Y-m-d H:i:s', $gameRecord['createTime']);
					$gameRecord['payoutTime'] = date('Y-m-d H:i:s', $gameRecord['payoutTime']);

					$gameRecord['response_result_id'] 	= $responseResultId;
					$gameRecord['uniqueid'] 			= $uniqueId;
					$gameRecord['external_uniqueid'] 	= $external_uniqueid;
					$gameRecord['gameshortcode'] 		= $gameshortcode;

				});

				if($this->getSystemInfo('always_sync_game_records')){

					$this->CI->utils->debug_log('gameRecords', count($gameRecords));
					foreach ($gameRecords as $availableRow) {
						if ( ! $this->isInvalidRow($availableRow)) {
							$this->ebet_game_logs_model->syncGameLogs($availableRow);
						}
					}

				}else{

					$availableRows = $this->ebet_game_logs_model->getAvailableRows($gameRecords);
					$this->CI->utils->debug_log('availableRows', count($availableRows), 'gameRecords', count($gameRecords));

					foreach ($availableRows as $availableRow) {
						if ( ! $this->isInvalidRow($availableRow)) {
							// $this->CI->ebet_game_logs->syncGameLogs($availableRow);
							$this->ebet_game_logs_model->insertEbetGameLogs($availableRow);
						}
					}

				}

			}

			$result['count'] = $resultJson['count'];

		}

		return array($success, $result);

	}

	public function syncMergeToGameLogs($token) {

		$this->CI->load->model(array('game_logs', 'player_model', 'game_description_model'));

		$dateTimeFrom = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$dateTimeFrom->modify($this->getDatetimeAdjust());
		$dateTimeFrom = $dateTimeFrom->format('Y-m-d H:i:s');

		$dateTimeTo = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
		$dateTimeTo = $dateTimeTo->format('Y-m-d H:i:s');

		$this->CI->utils->debug_log('dateTimeFrom', $dateTimeFrom, 'dateTimeTo', $dateTimeTo);

		$cnt 	= 0;
		$rlt 	= array('success' => true);
		$result = $this->ebet_game_logs_model->getebetGameLogStatistics($dateTimeFrom, $dateTimeTo);

		foreach ($result as $ebet_data) {
			if ($player_id = $this->getPlayerIdInGameProviderAuth($ebet_data->username)) {

				$cnt++;

				$player 		= $this->CI->player_model->getPlayerById($player_id);
				$bet_amount 	= $this->gameAmountToDB($this->getBetAmount($ebet_data));
				$real_bet_amount= $this->gameAmountToDB($this->getRealBetAmount($ebet_data));
				$result_amount 	= $this->gameAmountToDB($this->getResultAmount($ebet_data));
				$has_both_side 	= $bet_amount >= $result_amount && $result_amount > 0 ? 1 : 0;

				// $this->CI->utils->debug_log('time: '.$ebet_data->end_at, $ebet_data->roundNo, 'bet_amount', $bet_amount,
				// 	'real_bet_amount', $real_bet_amount, 'result_amount', $result_amount);

				$this->syncGameLogs(
					$ebet_data->game_type_id,  			# game_type_id
					$ebet_data->game_description_id,	# game_description_id
					$ebet_data->gameshortcode, 			# game_code
					$ebet_data->game_type_id, 			# game_type
					$ebet_data->game, 					# game
					$player_id, 						# player_id
					$ebet_data->username, 				# player_username
					$bet_amount, 						# bet_amount
					$result_amount, 					# result_amount
					null,								# win_amount
					null,								# loss_amount
					null,								# after_balance
					$has_both_side, 					# has_both_side
					$ebet_data->external_uniqueid, 		# external_uniqueid
					$ebet_data->start_at,				# start_at
					$ebet_data->end_at,					# end_at
					$ebet_data->response_result_id,		# response_result_id
					Game_logs::FLAG_GAME,
					['table'=>$ebet_data->roundNo, 'trans_amount'=>$real_bet_amount]
				);

			}
		}

		$this->CI->utils->debug_log('syncMergeToGameLogs monitor', 'count', $cnt);

		return array('success' => true);
	}

	public function queryForwardGame($playerName = NULL, $extra = array()) {

		$url = $this->getSystemInfo('flash_url');

		if ( ! $playerName) return array('success' => true, 'url' => $url . '?' . http_build_query(array('channelId' => $this->channel_id)));

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		if($this->always_lowercase){
			$game_username=strtolower($game_username);
		}

		$token = $this->getPlayerTokenByUsername($playerName);
		$timestamp = time();

		return array('success' => true, 'url' => $url . '?' . http_build_query(array(
			'ts' 			=> $timestamp,
			'username' 		=> $gameUsername,
			'accessToken' 	=> $token,
		)));

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
//						$self->updatePlayerSubwalletBalance($playerId, floatval($balance));
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

	public function getBetLimit($playerName) {

		$game_username = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' 		=> $this,
			'callback_method' 	=> 'processResultForGetBetLimit',
			'playerName' 		=> $playerName,
		);

		$signature_key 	= $game_username;
		$signature 		= $this->encrypt($signature_key);

		$params = array(
			'username' 		=> $game_username,
			'channelId' 	=> $this->channel_id,
			'signature' 	=> $signature,
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

		$game_id = $params['gameId'];
		$signature_key 	= $game_username . $game_id;
		$signature 		= $this->encrypt($signature_key);

		$params = array_merge(array(
			'username' 		=> $game_username,
			'channelId' 	=> $this->channel_id,
			'signature' 	=> $signature,
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
	public function blockPlayer($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$success = $this->blockUsernameInDB($playerName);
		return array("success" => true);
	}
	//===end blockPlayer=====================================================================================

	//===start unblockPlayer=====================================================================================
	public function unblockPlayer($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$success = $this->unblockUsernameInDB($playerName);
		return array("success" => true);
	}
	//===end unblockPlayer=====================================================================================

	public function logout($playerName, $password = null) {
		return $this->returnUnimplemented();
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

}

/*end of file*/
