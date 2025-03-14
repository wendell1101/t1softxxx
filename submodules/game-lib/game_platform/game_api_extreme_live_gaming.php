<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

class Game_api_extreme_live_gaming extends Abstract_game_api {



	const URI_MAP = array(
	);

	public function __construct() {
		parent::__construct();
		$this->currency = $this->getSystemInfo('currency','KRW');
		$this->language = $this->getSystemInfo('language','ko');
		$this->countryCode = $this->getSystemInfo('countryCode','KR');
		$this->cCode = $this->getSystemInfo('cCode');
		$this->caID = $this->getSystemInfo('caID');
		$this->caID_pass = $this->getSystemInfo('caID_pass');
		$this->game_url = $this->getSystemInfo('game_url');
	}

	public function getPlatformCode() {
		return EXTREME_LIVE_GAMING_API;
	}

	const AUTO_REDIRECT = 0;

	public function getLanguage($currentLang = null) {
		if(!empty($currentLang)){
			switch ($currentLang) {
	            case LANGUAGE_FUNCTION::INT_LANG_CHINESE:
	                $language = 'zh';
	                break;
	            case LANGUAGE_FUNCTION::INT_LANG_INDONESIAN:
	                $language = 'id';
	                break;
	            case LANGUAGE_FUNCTION::INT_LANG_VIETNAMESE:
	                $language = 'vi';
	                break;
	            case LANGUAGE_FUNCTION::INT_LANG_KOREAN:
	                $language = 'ko';
	                break;
	            default:
	                $language = 'en';
	                break;
	        }
	        return $language;
		}
        return $this->language;    
	}

	public function getCountryCode() {
		return $this->countryCode;
	}

	public function getCurrency() {
		return $this->currency;
	}

	public function generateUrl($apiName, $params) {
		return $this->api_url;
	}


	public function getHttpHeaders($params){
		return array(	"API" => $params['method'],
						"DataType" => "JSON");
	}

	protected function customHttpCall($ch, $params) {
		unset($params["method"]); //unset action not need on params
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params, true));
		curl_setopt( $ch, CURLOPT_AUTOREFERER, true ); 
  		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 ); 
  		//curl_setopt( $ch, CURLOPT_TIMEOUT, 60 ); 
	}

	public function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null) {
		return array(false, null);
	}

	public function processResultBoolean($responseResultId, $resultArr, $playerName = null) {
		
	}

	public function createPlayer($userName, $playerId, $password, $email = null, $extra = null) {
		// create player on game provider auth
		$return = parent::createPlayer($userName, $playerId, $password, $email, $extra); 
		$success = false;
		$message = "Unable to create Account for Extreme Live Gaming";
		if($return){
			$success = true;
			$message = "Successfull create account for Extreme Live Gaming";
		}
		
		return array("success"=>$success,"message"=>$message);     
	}


	public function changePassword($playerName, $oldPassword = null, $newPassword) {
		return $this->returnUnimplemented();
	}

	public function isPlayerExist($userName) {
		$playerName = $this->getGameUsernameByPlayerUsername($userName);
		$playerId = $this->getPlayerIdInGameProviderAuth($playerName);
        $result['exists'] = true;
        $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
		return array(true, $result);
	}

	public function queryPlayerBalance($userName) {
		$playerName = $this->getGameUsernameByPlayerUsername($userName);
        $playerId = $this->getPlayerIdInGameProviderAuth($playerName);
		$balance = $this->CI->player_model->getPlayerSubWalletBalance($playerId, $this->getPlatformCode());

		$result = array(
			'success' => true, 
			'balance' => $balance
		);

		return $result;
	}

	public function depositToGame($userName, $amount, $transfer_secure_id=null){
		$player_id = $this->getPlayerIdFromUsername($userName);
		$playerBalance = $this->queryPlayerBalance($userName);
		$afterBalance = @$playerBalance['balance'];
		if(empty($transfer_secure_id)){
			$external_transaction_id = $this->utils->getTimestampNow();
		}
		$this->insertTransactionToGameLogs($player_id, $userName, $afterBalance, $amount, NULL,$this->transTypeMainWalletToSubWallet());
		return array(
			'success' => true,
			'external_transaction_id' => $external_transaction_id,
			'response_result_id ' => NULL,
		);
	}

    public function withdrawFromGame($userName, $amount, $transfer_secure_id=null,$notRecordTransaction=false) {
    	$player_id = $this->getPlayerIdFromUsername($userName);
		$playerBalance = $this->queryPlayerBalance($userName);
		$afterBalance = @$playerBalance['balance'];
		if(empty($transfer_secure_id)){
			$external_transaction_id = $this->utils->getTimestampNow();
		}
		$this->insertTransactionToGameLogs($player_id, $playerName, $afterBalance, $amount, $result['response_result_id'],$this->transTypeSubWalletToMainWallet());
		return array(
			'success' => true,
			'external_transaction_id' => $external_transaction_id,
			'response_result_id ' => NULL,
		);
    }

	public function queryForwardGame($userName, $extra = null) {
		$gamename 		= $this->getGameUsernameByPlayerUsername($userName);
		$playerId 		= $this->getPlayerIdFromUsername($userName);
		$token 			= $this->getPlayerToken($playerId);
		$page 			= $this->checkLobby($extra['game'],$extra['is_mobile']);
		$language = $this->getLanguage($extra['language']);
		$game_url = $this->game_url;
		$params = array(
			"cCode" => $this->cCode,
			"caID" => $this->caID,
			"caID_pass" => $this->caID_pass,
			"caUserID" => $gamename,
			"sessionID" => $token,
			"output" => self::AUTO_REDIRECT,
			"clienttype" => ($extra['is_mobile']) ? 'html' : 'flash',
			"page" => $page
		);

		$url_params = http_build_query($params);
		$generateUrl 	= $game_url.'?'.$url_params;
		$data = [
            'url' => $generateUrl,
            'success' => true
        ];
        $this->utils->debug_log(' Extreme generateUrl - =================================================> ' . $generateUrl);
        return $data;
	}

	public function checkLobby($game,$is_mobile){
		$game = strtolower($game);

		switch ($game) {
		case 'baccarat':
			$page = ($is_mobile) ? 106 : 103;
			break;
		case 'roulette':
			$page = ($is_mobile) ? 107 : 104;
			break;
		case 'blackjack':
			$page = ($is_mobile) ? 108 : 105;
			break;
		}
		return $page;

	}

	public function syncOriginalGameLogs($token = false) {
		return $this->returnUnimplemented();
	}

	public function syncMergeToGameLogs($token) {
		return $this->returnUnimplemented();
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


	public function login($username, $password = null) {
		return $this->returnUnimplemented();
	}

	public function processResultForgetVendorId($params) {
		return $this->returnUnimplemented();
	}

	/**
	 * overview : get game time to server time
	 *
	 * @return string
	 */
	/*public function getGameTimeToServerTime() {
		//return '+8 hours';
	}*/

	/**
	 * overview : get server time to game time
	 *
	 * @return string
	 */
	/*public function getServerTimeToGameTime() {
		//return '-8 hours';
	}*/

	public function queryTransaction($transactionId, $extra) {
		return $this->returnUnimplemented();
	}

	public function syncPlayerAccount($username, $password, $playerId) {
		return $this->returnUnimplemented();
	}

	public function queryPlayerInfo($playerName) {
		return $this->returnUnimplemented();
	}

	public function logout($playerName, $password = null) {
		return $this->returnUnimplemented();
	}

	public function updatePlayerInfo($playerName, $infos) {
		return $this->returnUnimplemented();
		// return array("success" => true);
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

	
}

/*end of file*/