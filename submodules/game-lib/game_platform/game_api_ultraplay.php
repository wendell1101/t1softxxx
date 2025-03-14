<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

class Game_api_ultraplay extends Abstract_game_api {

	const SUCCESS = 'OK';

	public function __construct() {
		parent::__construct();
		$this->api_version = $this->getSystemInfo('api_version');
		$this->odd_format = $this->getSystemInfo('odd_format');
		$this->iframe_url = $this->getSystemInfo('iframe_url');
		$this->default_lang = $this->getSystemInfo('default_lang');
		$this->currency = $this->getSystemInfo('currency');
	}

	public function generateUrl($apiName, $params) {
		return $this->returnUnimplemented();
	}

	public function getPlatformCode() {
		return ULTRAPLAY_API;
	}

	public function get_api_version() {
		return $this->api_version;
	}

	public function get_currency() {
		return $this->currency;
	}

	public function serviceApi($method, $result = null) {

		$this->CI->utils->debug_log('Game_api_mg_quickfire service API: ', $result);

	}

	public function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null) {
		return array(false, null);
	}

	public function processResultBoolean($responseResultId, $resultArr, $playerName = null) {
		$success = !empty($resultArr) && $resultArr['status_code'] == self::STATUS_SUCCESS;
		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('MG_QUICKFIRE_API got error', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
		}
		return $success;
	}

	public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {

		// create player on game provider auth
		$return = parent::createPlayer($playerName, $playerId, $password, $email, $extra); 
		$success = false;
		$message = "Unable to create Account for MG quickfire";
		if($return){
			$success = true;
			$message = "Successfull create account for MG quickfire";
		}
		
		return array("success"=>$success,"message"=>$message);

	}

	public function loginByToken($token = null){
		$playerId = $this->getPlayerIdByToken($token);

		if($playerId){
			$playerName = $this->getGameUsernameByPlayerId($playerId);
			$balance = $this->queryPlayerBalance($playerName);
			$result = array(
				'success' => true,
				'token_attr' => $token,
				'loginname_attr' => $playerName,
				'currency_attr' => $this->currency,
				'country_attr' => $this->country,
				'city_attr' => '',
				'balance_attr' => $balance['balance'],
				'bonusbalance_attr' => 0,
				'wallet_attr' => 'vanguard', #always vanguard
				'extinfo' => ''
			);
		}else{
			$result = array(
				'success' => false,
				'error_code' => '6002'
			);
		}

		return $result;
	}

	public function login($gameUsername, $password = null) {

		if ($password == $this->getPasswordByGameUsername($gameUsername)) {

			$playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
			$playerName = $this->getGameUsernameByPlayerId($playerId);
			$balance = $this->queryPlayerBalance($playerName);
			$token = $this->getPlayerToken($playerId);
			$result = array(
				'success' => true,
				'token_attr' => $token,
				'loginname_attr' => $playerName,
				'currency_attr' => $this->currency,
				'country_attr' => $this->country,
				'city_attr' => '',
				'balance_attr' => $balance['balance'],
				'bonusbalance_attr' => 0,
				'wallet_attr' => 'vanguard', #always vanguard
				'extinfo' => ''
			);

		} else {
			$result = array(
				'success' => false,
				'error_code' => '6101'
			);
		}

		return $result;

	}


	public function queryPlayerBalance($playerName) {
		$this->CI->load->model(array('player_model'));

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
		$balance = $this->CI->player_model->getPlayerSubWalletBalance($playerId, $this->getPlatformCode());

		$result = array(
			'success' => true, 
			'balance' => $balance
		);

		return $result;
	}

	public function depositToGame($playerName, $amount, $transfer_secure_id = null) {
		
		$playerId = $this->getPlayerIdFromUsername($playerName);
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$afterBalance = $this->CI->player_model->getPlayerSubWalletBalance($playerId, $this->getPlatformCode());
		$responseResultId = NULL;

		$this->insertTransactionToGameLogs($playerId, $gameUsername, $afterBalance, $amount, $responseResultId, $this->transTypeMainWalletToSubWallet());

		return array('success' => true);

	}

	public function withdrawFromGame($playerName, $amount, $transfer_secure_id = null) {

		$playerId = $this->getPlayerIdFromUsername($playerName);
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$afterBalance = $this->CI->player_model->getPlayerSubWalletBalance($playerId, $this->getPlatformCode());
		$responseResultId = NULL;

		$this->insertTransactionToGameLogs($playerId, $gameUsername, $afterBalance, $amount, $responseResultId, $this->transTypeSubWalletToMainWallet());

		return array('success' => true);

	}

	public function queryPlayerInfo($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		return array("success" => true);
	}

	public function changePassword($playerName, $oldPassword, $newPassword) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		return array("success" => true);
	}

	public function blockPlayer($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$success = $this->blockUsernameInDB($playerName);
		return array("success" => true);
	}

	public function unblockPlayer($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$success = $this->unblockUsernameInDB($playerName);
		return array("success" => true);
	}
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

	public function totalBettingAmount($playerName, $dateFrom, $dateTo) {
		return $this->returnUnimplemented();
	}

	public function queryTransaction($transactionId, $extra) {
		return $this->returnUnimplemented();
	}
	public function processResultForQueryTransaction($apiName, $params, $responseResultId, $resultXml) {
		return $this->returnUnimplemented();
	}

	public function queryForwardGame($playerName, $extra) {

		$loginToken = $this->getPlayerTokenByUsername($playerName);

		$url = $this->iframe_url . '?' . http_build_query(array(
			'loginToken' => $loginToken,
			'deviceType' => isset($extra['deviceType']) ? $extra['deviceType'] : 'desktop' ,
			'lang' => isset($extra['lang']) ? $extra['lang'] : $this->default_lang,
			'oddformat' => $this->odd_format
		));

		return array('success' => TRUE, 'url' => $url);

	}

	public function syncOriginalGameLogs($token) {
		return $this->returnUnimplemented();
	}

	public function syncMergeToGameLogs($token) {
		return $this->returnUnimplemented();
	}

}

/*end of file*/