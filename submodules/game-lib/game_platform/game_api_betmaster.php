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
class Game_api_betmaster extends Abstract_game_api {

	protected $game_api_key;
	protected $game_api_secret;
	protected $suffix_for_username;
	protected $language;
	protected $country;
	protected $currency;
	protected $api_url;
	protected $game_url;
	protected $method;
	protected $status_map;
	protected $game_platform_id;
	protected $max_allow_sync_time_interval;



	const URI_MAP = array(
		self::API_createPlayer			=>	'/api/t1agency/create_player_account',
		self::API_isPlayerExist			=>	'/api/t1agency/is_player_account_exist',
		self::API_queryPlayerInfo		=>	'/api/t1agency/query_player_account',
		self::API_updatePlayerInfo		=>	'/api/t1agency/update_player_account',
		self::API_changePassword		=>	'/api/t1agency/change_player_password',
		self::API_blockPlayer			=>	'/api/t1agency/block_player_account',
		self::API_unblockPlayer			=>	'/api/t1agency/unblock_player_account',
		self::API_isPlayerBlocked		=>	'/api/t1agency/query_player_block_status',
		self::API_kickOutGame			=>	'/api/t1agency/kick_out_game',
//		self::API_checkLoginStatus		=>	'/api/t1agency/query_player_online_status',
//		'Optional'						=>	'/api/t1agency/query_game_launcher',
//		'Optional'						=>	'/api/t1agency/query_player_game_settings',
//		'Optional'						=>	'/api/t1agency/update_player_game_settings',
//		'Optional'						=>	'/api/t1agency/query_player_bet_limit',
//		'Optional'						=>	'/api/t1agency/update_player_bet_limit',
		self::API_depositToGame			=>	'/api/t1agency/transfer_player_fund',
		self::API_withdrawFromGame		=>	'/api/t1agency/transfer_player_fund',
		self::API_queryPlayerBalance	=>	'/api/t1agency/query_player_balance',
		self::API_queryGameRecords		=>	'/api/t1agency/query_game_history',
//		'Optional'						=>	'/api/t1agency/query_game_result',
	);

	const SUCCESS_CODE = 0;

	const GAME_TIMEZONE = 'Etc/Greenwich';
	const SYSTEM_TIMEZONE = 'Asia/Taipei';

	const TEST_AGEND_ID = '0';

	const METHOD_GET = 'GET';
	const METHOD_POST = 'POST';

	const ACTION_TYPE_DEPOSIT = 'deposit';
	const ACTION_TYPE_WITHDRAW = 'withdraw';

	const API_isPlayerBlocked = 'isPlayerBlocked';
	const API_kickOutGame = 'kickOutGame';

	public function __construct()
	{
		parent::__construct();
		$this->game_api_key = $this->getSystemInfo('key');
		$this->game_api_secret = $this->getSystemInfo('secret');
		$this->api_url = $this->getSystemInfo('url');
		$this->game_url = $this->getSystemInfo('game_url');

		$this->language = $this->getSystemInfo('language');
		$this->country = $this->getSystemInfo('country');
		$this->currency = $this->getSystemInfo('currency');
		$this->suffix_for_username = $this->getSystemInfo('suffix_for_username');

		$this->game_platform_id = $this->getSystemInfo('game_platform_id');
		$this->max_allow_sync_time_interval = $this->getSystemInfo('max_allow_sync_time_interval', '+5 minutes');

		$this->method = self::METHOD_POST;

	}


	/**
	 * Getting platform code
	 *
	 * @return int
	 */
	public function getPlatformCode()
	{
		return BETMASTER_API;
	}

	/**
	 * Generate URL
	 *
	 * @param $apiName
	 * @param $params
	 * @return string
	 */
	public function generateUrl($apiName, $params) {

		$apiUri = self::URI_MAP[$apiName];

		if (self::METHOD_POST == $this->method) {
			$url = $this->api_url . $apiUri;
		}else{
			$url = $this->api_url . $apiUri . '?' . http_build_query($params);
		}

		$this->CI->utils->debug_log('apiName', $apiName);
		$this->CI->utils->debug_log('url', $url);
		$this->CI->utils->debug_log('====================params', $params);
//		$this->CI->utils->debug_log('sign', $params['sign']);
//		$this->CI->utils->debug_log('url', $url);
//		die($url);

		return $url;
	}

	public function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null)
	{
		return $this->returnUnimplemented();
	}

	public function processResultBoolean($responseResultId, $resultJson, $playerName) {
		$success = self::SUCCESS_CODE == $resultJson['code'];
		if (!$success) {
			$this->utils->error_log('return error', $resultJson, 'playerName', $playerName, 'responseResultId', $responseResultId);
		}
		return $success;
	}

	/**
	 * Concat all non-empty values to one string ,
	 * append secret key to last, then use SHA1 to get signature
	 * The order should be sorted by parameter name in alphabetical order
	 * Ignore any json field and sign field
	 * Always use utf-8
	 *
	 * @param $params
	 * @return string
	 */
	protected function generateSignatureByParams($params){
		$excludes = array('extra', 'sign', 'game_platform_id');

		//The order should be sorted by parameter name in alphabetical order
		ksort($params);
		$message = '';

		foreach ($params as $key => $value){

			//Ignore any json field and sign field
			if(!in_array($key, $excludes)){
				$message .= $params[$key];
			}
		}

		$message .= $this->game_api_secret;

		return hash('sha256', $message);
	}

	protected function customHttpCall($ch, $params) {

		if (self::METHOD_POST == $this->method) {
			$data_json = json_encode($params);

			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS,$data_json);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		}
	}

	/**
	 * get player agent id by game user name
	 * 
	 * @param $playerName
	 * @return string
	 */
	protected function getPlayerByName($playerName){
		$this->CI->load->model('player_model');

		$player_id = $this->getPlayerIdInGameProviderAuth($playerName);
		$player = $this->CI->player_model->getPlayerById($player_id);

		return $player;
	}

	/**
	 * get player agent id by game user name
	 *
	 * @param $playerName
	 * @return string
	 */
	protected function getPlayerAgentIdByName($playerName){
		$player = $this->getPlayerByName($playerName);

		if($player){
			$agent_id = $player->agent_id ? $player->agent_id : self::TEST_AGEND_ID;
		}

		return $agent_id;
	}

	/**
	 * get player agent id by game user name
	 *
	 * @param $playerName
	 * @return string
	 */
	protected function getPlayerAgentIdAndRootAgentIdByName($playerName){
		$player = $this->getPlayerByName($playerName);

		if($player){
			$agent_id = $player->agent_id ? $player->agent_id : self::TEST_AGEND_ID;
			$root_agent_id = $player->root_agent_id ? $player->root_agent_id : self::TEST_AGEND_ID;
		}

		return [$agent_id, $root_agent_id];
	}

	public function getGameTimeToServerTime() {
		return '+8 hours';
	}

	public function getServerTimeToGameTime() {
		return '-8 hours';
	}

	public function convertGameTime($dateTimeStr) {
		//from UTC TO UTC+8
		if ($dateTimeStr) {
			$dateTimeStr = $this->CI->utils->convertTimezone($dateTimeStr, self::GAME_TIMEZONE, self::SYSTEM_TIMEZONE);
		}

		return $dateTimeStr;
	}

	public function convertUsernameToGame($username) {

		if (!empty($this->suffix_for_username)) {
			$username = $username . $this->suffix_for_username;
		}

		return $username;
	}

	/**
	 * Create Player
	 *
	 * @param $playerName
	 * @param $playerId
	 * @param $password
	 * @param null $email
	 * @param null $extra
	 * @return array
	 */
	public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {

//		$playerName .= '@t1agency.com';

//		$this->CI->utils->debug_log('============createPlayer->playerId', $playerId);

		parent::createPlayer($playerName, $playerId, $password, $email, $extra);

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		list($agent_id, $root_agent_id) = $this->getPlayerAgentIdAndRootAgentIdByName($gameUsername);

//		$this->CI->utils->debug_log('============createPlayer->playerName', $playerName);
//		$this->CI->utils->debug_log('============createPlayer->gameUsername', $gameUsername);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForCreatePlayer',
			'playerName' => $playerName,
		);

		$extra = array(
			'language'	=>	isset($extra['language']) ? $extra['language'] : $this->language,
			'country'	=>	isset($extra['country']) ? $extra['country'] : $this->country,
			'currency'	=>	isset($extra['currency']) ? $extra['currency'] : $this->currency,
			'email'		=>	isset($extra['email']) ? $extra['email'] : $email,
		);

		if(empty($extra['email'])) $extra['email'] = $playerName . '@t1agency.com';

		$params = array(
			'username'	=>	$gameUsername,
			'password'	=>	$password,
			'realname'	=>	$playerName,
			'agent_id'	=>	$agent_id,
			'sma_id'	=>	$root_agent_id,
			'extra'		=>	$extra,
		);

		$params['sign'] = $this->generateSignatureByParams($params);

		return $this->callApi(self::API_createPlayer, $params, $context);

	}

	public function processResultForCreatePlayer($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);

		$this->CI->utils->debug_log('============processResultForCreatePlayer', $resultJson);

		return array($success, $resultJson);

	}

	/**
	 * Check Player if exist
	 *
	 * @param $playerName
	 * @return array
	 */
	public function isPlayerExist($playerName) {

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$agent_id = $this->getPlayerAgentIdByName($gameUsername);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForIsPlayerExist',
			'playerName' => $playerName,
		);

		$params = array(
			'agent_id' =>	$agent_id,
			'username' =>	$gameUsername,
		);

		$params['sign'] = $this->generateSignatureByParams($params);

		$this->method = self::METHOD_GET;

		return $this->callApi(self::API_isPlayerExist, $params, $context);

	}

	public function processResultForIsPlayerExist($params){

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);

		if ($success) {
			$result = array('exists' => $resultJson['result']['exists']);
		}else{
			$result = array('exists' => false);
		}

//		$this->CI->utils->debug_log('============processResultForIsPlayerExist', $resultJson);
//		$this->CI->utils->debug_log('============processResultForIsPlayerExist->$resultJson[exists]', $resultJson['result']['exists']);

		return array($success, $result);
	}

	public function queryPlayerInfo($playerName) {

//		$rlt = $this->checkLoginStatus($playerName);
//		if ( ! $rlt['success']) {
//			$rlt = $this->login($playerName, $this->getPasswordString($playerName));
//			if ( ! $rlt['success']) {
//				return array(
//					'success' => false,
//				);
//			}
//		}

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$agent_id = $this->getPlayerAgentIdByName($gameUsername);

		$context = array(
			'callback_obj' 		=> $this,
			'callback_method' 	=> 'processResultForQueryPlayerInfo',
			'playerName' 		=> $playerName,
		);

		$params = array(
			'username'		=>	$gameUsername,
			'agent_id'		=>	$agent_id,
		);

		$params['sign'] = $this->generateSignatureByParams($params);

		$this->method = self::METHOD_GET;

		return $this->callApi(self::API_queryPlayerInfo, $params, $context);

	}

	public function processResultForQueryPlayerInfo($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');

		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);

		return array($success, $resultJson);
	}

	public function updatePlayerInfo($playerName, $infos) {

//		$rlt=parent::isPlayerExist($playerName);
//		if($rlt['exists']){
			$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
			$agent_id = $this->getPlayerAgentIdByName($gameUsername);

			$context = array(
				'callback_obj' => $this,
				'callback_method' => 'processUpdatePlayerInfo',
				'playerName' => $playerName,
			);

			$params = array(
				"username"	=> $playerName,
				"realname"	=> $playerName,
				"extra"		=> $infos,
				"agent_id"	=> $agent_id,
			);

			$params['sign'] = $this->generateSignatureByParams($params);

			return $this->callApi(self::API_updatePlayerInfo, $params, $context);
//		}else{
//			return ['success' => true, 'exists'=>$rlt['exists']];
//		}
	}

	/**
	 * overview : process update for user info
	 *
	 * @param $params
	 * @return array
	 */
	public function processUpdatePlayerInfo($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);

		if ($success) {
			//I think the api result key 'udpated' should be 'updated'
			$result = array('exists' => $resultJson['result']['udpated']);
		}else{
			$result = array('exists' => false);
		}

//		$this->CI->utils->debug_log('============processUpdatePlayerInfo', $resultJson);
//		$this->CI->utils->debug_log('============processUpdatePlayerInfo->$resultJson[exists]', $resultJson['result']['udpated']);

		return array($success, $result);
	}

	public function changePassword($playerName, $oldPassword, $newPassword) {

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$agent_id = $this->getPlayerAgentIdByName($gameUsername);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForChangePassword',
			'playerName' => $playerName,
			'password' => $newPassword,
		);

		$params = array(
			'username' => $gameUsername,
			'password' => $newPassword,
			"agent_id" => $agent_id,
		);

		$params['sign'] = $this->generateSignatureByParams($params);

		return $this->callApi(self::API_changePassword, $params, $context);

	}

	public function processResultForChangePassword($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->convertResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);

		$this->CI->utils->debug_log('============processResultForChangePassword', $resultJson);

		if ($success) {

			$playerName = $this->getVariableFromContext($params, 'playerName');
			$playerId = $this->getPlayerIdInGameProviderAuth($playerName);

			if ($playerId) {
				$password = $this->getVariableFromContext($params, 'password');
				$this->updatePasswordForPlayer($playerId, $password);
				$this->CI->utils->debug_log('============processResultForChangePassword->OK');
			} else {
				$this->CI->utils->debug_log('cannot find player', $playerName);
				$this->CI->utils->debug_log('============processResultForChangePassword->fail');
			}

		}

		return array($success, $resultJson);

	}

	function blockPlayer($playerName) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$agent_id = $this->getPlayerAgentIdByName($gameUsername);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForBlockPlayer',
			'playerName' => $playerName,
		);

		$params = array(
			'username' => $gameUsername,
			'agent_id' => $agent_id,
		);

		$params['sign'] = $this->generateSignatureByParams($params);

		return $this->callApi(self::API_blockPlayer, $params, $context);
	}

	function processResultForBlockPlayer($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');

		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);

		$this->CI->utils->debug_log('============processResultForBlockPlayer', $resultJson);

		if ($success) {
			$result = array('blocked' => $resultJson['result']['blocked']);

			if($resultJson['result']['blocked']){
				$this->blockUsernameInDB($playerName);
				$this->CI->utils->debug_log('============processResultForBlockPlayer->block ok', $resultJson);
			}
		}

		return array($success, $result);
	}

	function unblockPlayer($playerName) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$agent_id = $this->getPlayerAgentIdByName($gameUsername);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForUnblockPlayer',
			'playerName' => $playerName,
		);

		$params = array(
			'username' => $gameUsername,
			'agent_id' => $agent_id,
		);

		$params['sign'] = $this->generateSignatureByParams($params);

		return $this->callApi(self::API_unblockPlayer, $params, $context);
	}
	function processResultForUnblockPlayer($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');

		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);

		$this->CI->utils->debug_log('============processResultForUnblockPlayer', $resultJson);

		if ($success) {

			$result = array('blocked' => $resultJson['result']['blocked']);

			if(!$resultJson['result']['blocked']){
				$this->unblockUsernameInDB($playerName);
				$this->CI->utils->debug_log('============processResultForUnblockPlayer->unblock ok', $resultJson);
			}
		}

		return array($success, $result);
	}

	function isPlayerBlocked($playerName) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$agent_id = $this->getPlayerAgentIdByName($gameUsername);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForIsPlayerBlocked',
			'playerName' => $playerName,
		);

		$params = array(
			'username' => $gameUsername,
			'agent_id' => $agent_id,
		);

		$params['sign'] = $this->generateSignatureByParams($params);

		$this->method = self::METHOD_GET;

		return $this->callApi(self::API_isPlayerBlocked, $params, $context);
	}

	function processResultForIsPlayerBlocked($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');

		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);

		$this->CI->utils->debug_log('============processResultForIsPlayerBlocked', $resultJson);

		return array($success, $resultJson);
	}

	function kickOutGame($playerName, $game_platform_id) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$agent_id = $this->getPlayerAgentIdByName($gameUsername);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForKickOutGame',
			'playerName' => $playerName,
		);

		$params = array(
			'username'			=>	$gameUsername,
			'agent_id'			=>	$agent_id,
			'game_platform_id'	=>	$game_platform_id,
		);

		$params['sign'] = $this->generateSignatureByParams($params);

		return $this->callApi(self::API_kickOutGame, $params, $context);
	}

	function processResultForKickOutGame($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');

		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);

		$this->CI->utils->debug_log('============processResultForKickOutGame', $resultJson);

		return array($success, $resultJson);
	}

	function checkLoginStatus($playerName)
	{
		return $this->returnUnimplemented();
	}

//	function checkLoginStatus($playerName) {
//
//		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
//		$agent_id = $this->getPlayerAgentIdByName($playerName);
//
//		$context = array(
//			'callback_obj' => $this,
//			'callback_method' => 'processResultForCheckLoginStatus',
//			'playerName' => $playerName,
//		);
//
//		$params = array(
//			'username'			=>	$playerName,
//			'agent_id'			=>	$agent_id,
//		);
//
//		$params['sign'] = $this->generateSignatureByParams($params);
//		$this->method = self::METHOD_GET;
//
//		return $this->callApi(self::API_checkLoginStatus, $params, $context);
//	}
//
//	function processResultForCheckLoginStatus($params) {
//		$responseResultId = $this->getResponseResultIdFromParams($params);
//		$resultJson = $this->getResultJsonFromParams($params);
//		$playerName = $this->getVariableFromContext($params, 'playerName');
//
//		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);
//
//		$this->CI->utils->debug_log('============processResultForCheckLoginStatus', $resultJson);
//
//		return array($success, $resultJson);
//	}

	function depositToGameWithPlatformId($playerName, $amount, $transfer_secure_id=null, $game_platform_id) {
		// $rlt = $this->logout($playerName);

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$agent_id = $this->getPlayerAgentIdByName($gameUsername);

		$externaltranid = !empty($transfer_secure_id) ? $transfer_secure_id : random_string('unique');

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForDepositToGame',
			'playerName' => $playerName,
			'externaltranid' => $externaltranid,
			'amount' => $amount,
		);

		$params = array(
			'action_type'			=>	self::ACTION_TYPE_DEPOSIT,
			'agent_id'				=>	$agent_id,
			'amount'				=>	$amount,
			'external_trans_id'		=>	$externaltranid,
			'game_platform_id'		=>	$game_platform_id,
			'username'				=>	$gameUsername,
		);

		$params['sign'] = $this->generateSignatureByParams($params);

		return $this->callApi(self::API_depositToGame, $params, $context);
	}

	function depositToGame($playerName, $amount, $transfer_secure_id=null) {
		return $this->depositToGameWithPlatformId($playerName, $amount, $transfer_secure_id, $this->game_platform_id);
	}

	function processResultForDepositToGame($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$amount = $this->getVariableFromContext($params, 'amount');

		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);

		$transaction_id = $resultJson['result']['transaction_id'];
		$updated = $resultJson['result']['updated'];

		$result = array(
			'response_result_id' => $transaction_id,
			'status' => $updated,
		);

		$this->CI->utils->debug_log('============processResultForDepositToGame', $resultJson);
		$this->CI->utils->debug_log('============processResultForDepositToGame->amount', $amount);

		if ($success) {

			$result['success']=true;

			//update
			if ($updated) {

				$playerId = $this->getPlayerIdInGameProviderAuth($playerName);

				if($playerId){
					//get current sub wallet balance
					$playerBalance = $this->queryPlayerBalance($playerName);
					//for sub wallet
					$afterBalance = $playerBalance['balance'];

					$this->CI->utils->debug_log('============processResultForDepositToGame->playerBalance', $playerBalance);
					$this->CI->utils->debug_log('============processResultForDepositToGame->afterBalance', $afterBalance);

					//deposit
					$this->insertTransactionToGameLogs($playerId, $playerName, $afterBalance, $amount, $transaction_id,
						$this->transTypeMainWalletToSubWallet());
				}

			} else {
				$this->CI->utils->debug_log('error', 'cannot get player id from ' . $playerName . ' getPlayerIdInGameProviderAuth');
			}
		}

		$this->CI->utils->debug_log('============processResultForDepositToGame->result', $result);

		return array($success, $result);
	}

	public function withdrawFromGameWithPlatformId($playerName, $amount, $transfer_secure_id=null, $game_platform_id) {
		// $rlt = $this->logout($playerName);

		$this->CI->utils->debug_log('============start->withdrawFromGameWithPlatformId', $playerName);

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$agent_id = $this->getPlayerAgentIdByName($gameUsername);

		$externaltranid = !empty($transfer_secure_id) ? $transfer_secure_id : random_string('unique');

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForWithdrawFromGame',
			'playerName' => $playerName,
			'externaltranid' => $externaltranid,
			'amount' => $amount,
		);

		$params = array(
			'action_type'			=>	self::ACTION_TYPE_WITHDRAW,
			'agent_id'				=>	$agent_id,
			'amount'				=>	$amount,
			'external_trans_id'		=>	$externaltranid,
			'game_platform_id'		=>	$game_platform_id,
			'username'				=>	$gameUsername,
		);

		$params['sign'] = $this->generateSignatureByParams($params);

		$this->CI->utils->debug_log('============withdrawFromGameWithPlatformId', $params);

		return $this->callApi(self::API_depositToGame, $params, $context);
	}

	public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null) {
		return $this->withdrawFromGameWithPlatformId($playerName, $amount, $transfer_secure_id, $this->game_platform_id);
	}

	public function processResultForWithdrawFromGame($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$amount = $this->getVariableFromContext($params, 'amount');

		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);

		$transaction_id = $resultJson['result']['transaction_id'];
		$updated = $resultJson['result']['updated'];

		$result = array(
			'response_result_id' => $transaction_id,
			'status' => $updated,
		);

		$this->CI->utils->debug_log('============processResultForWithdrawFromGame', $resultJson);

		if ($success) {

			$result['success']=true;

			//update
			if ($updated) {

				$playerId = $this->getPlayerIdInGameProviderAuth($playerName);

				if($playerId){
					//get current sub wallet balance
					$playerBalance = $this->queryPlayerBalance($playerName);
					//for sub wallet
					$afterBalance = $playerBalance['balance'];

					$this->CI->utils->debug_log('============processResultForWithdrawFromGame->playerBalance', $playerBalance);
					$this->CI->utils->debug_log('============processResultForWithdrawFromGame->afterBalance', $afterBalance);

					//deposit
					$this->insertTransactionToGameLogs($playerId, $playerName, $afterBalance, $amount, $transaction_id,
						$this->transTypeSubWalletToMainWallet());
				}

			} else {
				$this->CI->utils->debug_log('error', 'cannot get player id from ' . $playerName . ' getPlayerIdInGameProviderAuth');
			}
		}

		return array($success, $result);
	}

	public function login($playerName, $password = null)
	{
		return $this->returnUnimplemented();
	}

	public function logout($playerName, $password = null)
	{
		return $this->returnUnimplemented();
	}



	/**
	 * Check Player Balance
	 *
	 * @param $playerName
	 * @return array
	 */
	public function queryPlayerBalance($playerName) {

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$agent_id = $this->getPlayerAgentIdByName($gameUsername);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryPlayerBalance',
			'playerName' => $playerName,
		);

		$params = array(
			'username'		=>	$gameUsername,
			'agent_id'		=>	$agent_id,
		);

		$params['sign'] = $this->generateSignatureByParams($params);

		$this->method = self::METHOD_GET;


		return $this->callApi(self::API_queryPlayerBalance, $params, $context);

	}

	public function processResultForQueryPlayerBalance($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->convertResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);

		$this->CI->utils->debug_log('============processResultForQueryPlayerBalance', $resultJson);

		if ($success) {
			$result['success'] = true;
			$result["balance"] = isset($resultJson['result']['main_balance']) ? floatval($resultJson['result']['main_balance']) : 0;
			$result["game_platform_balance"] = isset($resultJson['result']['game_platform_balance']) ? $resultJson['result']['game_platform_balance'] : null;
		} else {
			$result['success'] = false;
		}

		return array($success, $result);

	}

	public function queryPlayerDailyBalance($playerName, $playerId, $dateFrom = null, $dateTo = null)
	{
		return $this->returnUnimplemented();
	}

//	public function queryGameRecords($dateFrom, $dateTo, $playerName = null) {
//		return $this->returnUnimplemented();
//	}

//	public function queryGameRecords($dateFrom, $dateTo, $playerName = null) {
//		$gameRecords = parent::getGameRecords($dateFrom, $dateTo, $playerName, $this->getPlatformCode());
//		return array('success' => true, 'gameRecords' => $gameRecords);
//	}

	/**
	 * overview : query game records
	 *
	 * @param $dateFrom
	 * @param $dateTo
	 * @param null $playerName
	 * @return array
	 */
	public function queryGameRecords($dateFrom, $dateTo, $playerName = null) {

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$agent_id = $this->getPlayerAgentIdByName($gameUsername);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processQueryGameRecords',
			'playerName' => $playerName,
			'gameUsername'=>$gameUsername,
		);
		$params = array(
			'from' => $this->serverTimeToGameTime($dateFrom->format('Y-m-d H:i:s')),
			'to' => $this->serverTimeToGameTime($dateTo->format('Y-m-d H:i:s')),
			'game_platform_id' => $this->game_platform_id,
			'agent_id' => $agent_id,
			'username' => $gameUsername,
		);

		$params['sign'] = $this->generateSignatureByParams($params);

		$this->method = self::METHOD_GET;

		$result = $this->callApi(self::API_queryGameRecords, $params, $context);
		return $result;
	}

	/**
	 * overview : process query game records
	 *
	 * @param $params
	 * @return array
	 */
	public function processQueryGameRecords($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');

		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);

		$this->CI->utils->debug_log('============processQueryGameRecords', $resultJson);

		if($success){
			$result = $resultJson['result']['game_history'];
		}



		return array($success, $result);
	}

	public function queryTransaction($transactionId, $extra)
	{
		return $this->returnUnimplemented();
	}

	public function queryForwardGame($playerName, $extra)
	{
		return $this->returnUnimplemented();
	}

	public function syncOriginalGameLogs($token) {

		$playerName = parent::getValueFromSyncInfo($token, 'playerName');
		$syncId = parent::getValueFromSyncInfo($token, 'syncId');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$startDate->modify($this->getDatetimeAdjust());


		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForSyncGameRecords',
		);

		$queryDateTimeStart = $this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s'));
		$queryDateTimeEnd = $this->serverTimeToGameTime($startDate->modify($this->max_allow_sync_time_interval)->format('Y-m-d H:i:s'));
		$queryDateTimeMax = $this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s'));

		$result = null;

		while ($queryDateTimeMax  > $queryDateTimeStart) {

			if($queryDateTimeEnd > $queryDateTimeMax){
				$queryDateTimeEnd = $queryDateTimeMax;
			}

			$params = array(
				"from"				=> $queryDateTimeStart,
				"to"				=> $queryDateTimeEnd,
				"game_platform_id"	=> $this->game_platform_id,
			);

			$this->method = self::METHOD_GET;

			if (empty($playerName)) {

			}else{
				$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
				$params['username'] = $gameUsername;
			}

			$params['sign'] = $this->generateSignatureByParams($params);

			$this->CI->utils->debug_log('============syncOriginalGameLogs->from', $queryDateTimeStart);
			$this->CI->utils->debug_log('============syncOriginalGameLogs->to', $queryDateTimeEnd);

			$result = $this->callApi(self::API_queryGameRecords, $params, $context);

			$startDate = new DateTime($queryDateTimeEnd);
			$queryDateTimeStart = $startDate->format('Y-m-d H:i:s');
			$queryDateTimeEnd  = $startDate->modify($this->max_allow_sync_time_interval)->format('Y-m-d H:i:s');

//			$this->CI->utils->debug_log('============syncOriginalGameLogs->queryDateTimeStart', $queryDateTimeStart);
//			$this->CI->utils->debug_log('============syncOriginalGameLogs->queryDateTimeEnd', $queryDateTimeEnd);
//			$this->CI->utils->debug_log('============syncOriginalGameLogs->queryDateTimeMax', $queryDateTimeMax);
//
//			if($queryDateTimeMax  > $queryDateTimeStart){
//				$this->CI->utils->debug_log('============AAAAAAAAAAAAAAAAA', $queryDateTimeMax);
//			}else{
//				$this->CI->utils->debug_log('============BBBBBBBBBBBBBBBBB', $queryDateTimeMax);
//			}

		}

		return $result;
	}

//	public function syncOriginalGameLogs($token) {
//
//		$this->CI->utils->debug_log('============processResultForSyncGameRecords->token', $token);
//
//		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
//		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
//
//		$this->CI->utils->debug_log('============processResultForSyncGameRecords->syncInfo', $this->syncInfo);
//		$this->CI->utils->debug_log('============processResultForSyncGameRecords->startDate', $startDate);
//		$this->CI->utils->debug_log('============processResultForSyncGameRecords->endDate', $endDate);
//
//		$startDate = new DateTime($startDate->format('Y-m-d H:i:s'));
//		$endDate = new DateTime($endDate->format('Y-m-d H:i:s'));
//		$startDate->modify($this->getDatetimeAdjust());
//		$this->CI->utils->debug_log('startDate', $startDate, 'endDate', $endDate);
//
//		$context = array(
//			'callback_obj' => $this,
//			'callback_method' => 'processResultForSyncGameRecords',
//		);
//
//		$params = array(
//			"from"				=> $startDate->format('Y-m-d H:i:s'),
//			"to"				=> $endDate->format('Y-m-d H:i:s'),
//			"game_platform_id"	=> $this->game_platform_id,
//		);
//
//		$params['sign'] = $this->generateSignatureByParams($params);
//
//		$this->method = self::METHOD_GET;
//
//		return $this->callApi(self::API_queryGameRecords, $params, $context);
//	}

	public function processResultForSyncGameRecords($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');

		// $this->CI->utils->debug_log('original records', $params);
		// load models
		$this->CI->load->model(array('betmaster_game_logs', 'external_system'));
		$result = array();
		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);


		$this->CI->utils->debug_log('============processResultForSyncGameRecords', $resultJson);

		if ($success) {
			$gameRecords = $resultJson['result']['game_history'];

			if (empty($gameRecords) || !is_array($gameRecords)) {
				$this->CI->utils->debug_log('wrong game records', $gameRecords);
			}

			if (!empty($gameRecords)) {

				$this->CI->utils->debug_log('gameRecords', count($gameRecords));

				$availableRows = $this->CI->betmaster_game_logs->getAvailableRows($gameRecords);
				$this->CI->utils->debug_log('availableRows', count($availableRows), 'responseResultId', $responseResultId);

				foreach ($availableRows as $record) {
					$data = array(
						'game_name' => $record['game_name'],
						'result_amount' => $record['result_amount'],
						'round_number' => $record['round_number'],
						'real_bet_amount' => $record['real_bet_amount'],
						'outcome_id' => $record['bet_details']['outcome_id'],
						'special_odds' => $record['bet_details']['special_odds'],
						'subtype_id' => $record['bet_details']['market']['subtype_id'],
						'is_live' => 'true' == $record['bet_details']['market']['is_live'] ? 'true' : 'false',
						'type_id' => $record['bet_details']['market']['type_id'],
						'game_details_date_start' => $record['game_details']['date_start'],
						'game_details_id' => $record['game_details']['id'],
						'team_home_id' => $record['game_details']['team_home']['id'],
						'team_away_id' => $record['game_details']['team_away']['id'],
						'username' => $record['username'],
						'agent_id' => $record['agent_id'],
						'uniqueid' => $record['uniqueid'],
						'payout_amount' => $record['payout_amount'],
						'after_balance' => $record['after_balance'],
						'bet_time' => $this->gameTimeToServerTime($record['bet_time']),
						'payout_time' => $this->gameTimeToServerTime($record['payout_time']),
						'effective_bet_amount' => $record['effective_bet_amount'],
						'game_platform_id' => $record['game_platform_id'],
						'odds' => $record['odds'],
						'game_code' => $record['game_code'],
						'game_finish_time' => $this->gameTimeToServerTime($record['game_finish_time']),
						'external_uniqueid' => $record['uniqueid'],
						'response_result_id' => $responseResultId,
					);


//					$isUniqueIdAlreadyExists = $this->isUniqueIdAlreadyExists($record['uniqueid']);

					$isUniqueIdAlreadyExists = $record['isUniqueIdAlreadyExists'];

					if(!$isUniqueIdAlreadyExists){
						$this->CI->utils->debug_log('============processResultForSyncGameRecords->insertBetmasterGameLogs', $resultJson);
						$this->CI->betmaster_game_logs->insertBetmasterGameLogs($data);
					}else{
						$this->CI->utils->debug_log('============processResultForSyncGameRecords->updateBetmasterGameLogs', $resultJson);
						$this->CI->betmaster_game_logs->updateBetmasterGameLogs($data);
					}

				}
			}
		}

		return array($success, $result);
	}

	private function isUniqueIdAlreadyExists($uniqueId) {
		$this->CI->load->model('betmaster_game_logs');
		return $this->CI->betmaster_game_logs->isUniqueIdAlreadyExists($uniqueId);
	}

	public function syncMergeToGameLogs($token) {

		$this->CI->load->model(array('game_logs', 'player_model', 'game_description_model', 'betmaster_game_logs'));

		$dateTimeFrom = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$dateTimeFrom->modify($this->getDatetimeAdjust());
		$dateTimeFrom = $dateTimeFrom->format('Y-m-d H:i:s');

		$dateTimeTo = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
		$dateTimeTo = $dateTimeTo->format('Y-m-d H:i:s');

		$this->CI->utils->debug_log('dateTimeFrom', $dateTimeFrom, 'dateTimeTo', $dateTimeTo);

		$this->CI->utils->debug_log('=====================syncMergeToGameLogs->token', $token);

		$cnt 	= 0;
		$rlt 	= array('success' => true);
		$result = $this->CI->betmaster_game_logs->getBetmasterGameLogStatistics($dateTimeFrom, $dateTimeTo);

		$this->CI->utils->debug_log('=====================syncMergeToGameLogs', $result);

		foreach ($result as $betmaster_data) {
			if ($player_id = $this->getPlayerIdInGameProviderAuth($betmaster_data->username)) {

				$cnt++;

//				$player 		= $this->CI->player_model->getPlayerById($player_id);
				$bet_amount 	= $this->gameAmountToDB($betmaster_data->bet_amount);
//				$real_bet_amount= $this->gameAmountToDB($betmaster_data->real_bet_amount);
				$result_amount 	= $this->gameAmountToDB($betmaster_data->result_amount);
//				$has_both_side 	= $bet_amount >= $result_amount && $result_amount > 0 ? 1 : 0;

				$has_both_side = 0;

				if( $betmaster_data->result_amount == 0 &&
					$betmaster_data->payout_amount == $betmaster_data->bet_amount &&
					$betmaster_data->result_amount == ($betmaster_data->payout_amount - $betmaster_data->bet_amount)
				){
					$has_both_side = 1;
				}

				// $this->CI->utils->debug_log('time: '.$betmaster_data->end_at, $betmaster_data->roundNo, 'bet_amount', $bet_amount,
				// 	'real_bet_amount', $real_bet_amount, 'result_amount', $result_amount);

				$this->syncGameLogs(
					$betmaster_data->game_type_id,  			# game_type_id
					$betmaster_data->game_description_id,	# game_description_id
					$betmaster_data->game_code, 			# game_code
					$betmaster_data->game_code, 			# game_type
					$betmaster_data->game, 					# game
					$player_id, 						# player_id
					$betmaster_data->username, 				# player_username
					$bet_amount, 						# bet_amount
					$result_amount, 					# result_amount
					null,								# win_amount
					null,								# loss_amount
					$betmaster_data->after_balance, 								# after_balance
					$has_both_side, 					# has_both_side
					$betmaster_data->external_uniqueid, 		# external_uniqueid
					$betmaster_data->start_at,				# start_at
					$betmaster_data->end_at,					# end_at
					$betmaster_data->response_result_id,		# response_result_id
					Game_logs::FLAG_GAME
//					[
//						'outcome_id'		=>	$betmaster_data->outcome_id,
//						'special_odds'		=>	$betmaster_data->special_odds,
//						'is_live'			=>	$betmaster_data->is_live,
//						'type_id'			=>	$betmaster_data->type_id,
//						'team_home_id'		=>	$betmaster_data->team_home_id,
//						'team_away_id'		=>	$betmaster_data->team_away_id,
//						'game_platform_id'	=>	$betmaster_data->game_platform_id,
//						'odds'				=>	$betmaster_data->odds
//					]
				);

			}
		}

		$this->CI->utils->debug_log('syncMergeToGameLogs monitor', 'count', $cnt);

		return array('success' => true);
	}

	public function gameAmountToDB($amount) {
		$amount = floatval($amount);
		return round($amount, 2);
	}

	public function batchQueryPlayerBalance($playerNames, $syncId = null) {

		$result = $this->batchQueryPlayerBalanceOneByOne($playerNames, $syncId);

		$success = isset($result['success']) ?  $result['success'] : false;
		$returnResult = array();

		if($success){

			if ($this->is_array_and_not_empty($result['balances'])) {

				$returnResult['balances'] = $result['balances'];

				foreach ($result['balances'] as $playerName => $balance){
					$playerId = $this->getPlayerIdInPlayer($playerName);
//					$this->updatePlayerSubwalletBalance($playerId, $balance);
				}
			}

		}

		return array($success, $returnResult);
	}

//	public function processResultForbatchQueryPlayerBalance($params) {
//
//		$responseResultId = $this->getResponseResultIdFromParams($params);
//		$resultJson = $this->getResultJsonFromParams($params);
//		$success = $this->processResultBoolean($responseResultId, $resultJson);
//
//		if ($success && isset($resultJson['results']) && ! empty($resultJson['results'])) {
//			$result = $resultJson['results'];
//			$self = $this;
//			foreach ($result as $record) {
//				$playerId = $this->getPlayerIdInGameProviderAuth($record['username']);
//				$balance = $record['money'];
//				if ($playerId) {
//					// $this->CI->lockAndTransForPlayerBalance($playerId, function () use ($self, $playerId, $balance) {
//					$self->updatePlayerSubwalletBalance($playerId, floatval($balance));
//					// });
//				}
//			}
//		}
//
//		return array($success);
//	}

}