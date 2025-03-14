<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

/**
 * Defines general behavior of game API classes.
 *
 * General behaviors include:
 * * Getting platform code
 * * Generate URL
 * * Generate Soap Method
 * * Prepares Data below
 * * * Currency for Create Account
 * * * Profile List Id
 * * * Currency for Deposit
 * * * My Balance
 * * Create Player
 * * Login/Logout
 * * Deposit To game
 * * Withdraw from Game
 * * Change Password
 * * Check Player Balance
 * * Check Transaction
 * * Check Game records
 * * Check Forward Game
 * * Synchronize Original Game Logs
 * * Authenticate Soap
 * * Make Soap Options
 * * Check if Player Exist
 * * Check Player Information
 * * Block/Unblock Player
 * * Check Player Daily Balance
 * * Check login Status
 * * Check Total Betting Amount
 *
 *
 * @see Redirect redirect to game page
 * @document name PURSE ADVANCED INTEGRATION
 * @api version 5.9
 * @category Game API
 * @version 1.8.10
 * @copyright 2013-2022 tot
 */

class Game_api_ls_casino extends Abstract_game_api {
	public function __construct() {
		parent::__construct();		
		$this->currency = $this->getSystemInfo('currency','USD');
		$this->country_code = $this->getSystemInfo('country_code','US');
		$this->game_url = $this->getSystemInfo('game_url');
		$this->demo_url = $this->getSystemInfo('demo_url', $this->game_url.'/?guest=true&gameType=AllGames');
		$this->api_url = $this->getSystemInfo('url');
		$this->secret_key = $this->getSystemInfo('secret');
		$this->api_license_id = $this->getSystemInfo('api_license_id');
		$this->operator_name = $this->getSystemInfo('operator_name');
		$this->bet_limits = $this->getSystemInfo('bet_limits');
	}

	public function getPlatformCode() {
		return LS_CASINO_GAME_API;
	}

	public function getHashSecretKey(){
		return hash('SHA256', $this->secret_key);
	}

	public function getCurrency() {
		return $this->currency;
	}

	public function getBetLimits() {
		return $this->bet_limits;
	}

	public function getLicenseId() {
		return $this->api_license_id;
	}

	public function generateUrl($apiName, $params) {
		return $this->api_url;
	}

	public function getHttpHeaders($params){
		$headers = array(
			"API" => $params['method'],
			"DataType" => "JSON"
		);

		return $headers;
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
		$message = "Unable to create Account for LIVE SOLUTIONS SEAMLESS";
		if($return){
			$success = true;
			$message = "Successfull create account for LIVE SOLUTIONS SEAMLESS";
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

	public function depositToGame($playerName, $amount, $transfer_secure_id=null){
		// $playerId 			= $this->getPlayerIdFromUsername($playerName);
		// $gameUsername 		= $this->getGameUsernameByPlayerUsername($playerName);
		// $playerBalance 		= $this->queryPlayerBalance($playerName);
		// $afterBalance 		= @$playerBalance['balance'];
		// $responseResultId 	= null;
		
		// $this->CI->utils->debug_log('>>>>>>>>>>>>>>>>>>> ls casino api depositToGame playerId:', $playerId, ">>>>>> playerName", $playerName, " >>> gameUsername : ", $gameUsername, " >>> afterBalance : ", $afterBalance);
		// $this->insertTransactionToGameLogs($playerId, $gameUsername, $afterBalance, $amount, $responseResultId, $this->transTypeMainWalletToSubWallet());
		
		// return array('success' => true);

		$external_transaction_id = $transfer_secure_id;
	    return array(
	        'success' => true,
	        'external_transaction_id' => $external_transaction_id,
	        'response_result_id ' => NULL,
	        'didnot_insert_game_logs'=>true,
	    );
	}

    public function withdrawFromGame($playerName, $amount, $transfer_secure_id = null) {
    	// $playerId 			= $this->getPlayerIdFromUsername($playerName);
		// $gameUsername 		= $this->getGameUsernameByPlayerUsername($playerName);
		// $playerBalance 		= $this->queryPlayerBalance($playerName);
		// $afterBalance 		= @$playerBalance['balance'];
		// $responseResultId 	= null;

		// $this->CI->utils->debug_log('>>>>>>>>>>>>>>>>>>> ls casino api withdrawFromGame playerId:', $playerId, ">>>>>> playerName", $playerName, " >>> gameUsername : ", $gameUsername, " >>> afterBalance : ", $afterBalance);

		// $this->insertTransactionToGameLogs($playerId, $gameUsername, $afterBalance, $amount, $responseResultId, $this->transTypeSubWalletToMainWallet());
		
		// return array('success' => true);
    
    	$external_transaction_id = $transfer_secure_id;
	    return array(
	        'success' => true,
	        'external_transaction_id' => $external_transaction_id,
	        'response_result_id ' => NULL,
	        'didnot_insert_game_logs'=>true,
	    );
    }

    public function queryForwardGame($playerName, $param) {
    	$playerId = $this->getPlayerIdInPlayer($playerName);
        $token = $this->getPlayerToken($playerId);
        $url = null;
        $this->CI->utils->debug_log('>>>>>>>>>>>>>>>>>>> ls casino api playerID', $playerId, ">>>>>> playerName", $playerName, ">>>>>> Token", $token);

        if (!empty($token)) {
            $url = $this->game_url . "?gameType=" . $param['game_type'] . '&session=' . $token;
        } else {
        	$url = $this->demo_url;
        }

        return array(
            'success' => true,
            'url' => $url,
            'iframeName' => "Live Solutions Casino",
        );
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

	public function getUsernameById($playerId) {
        $this->CI->load->model(array('player_model'));
        $username = $this->CI->player_model->getUsernameById($playerId);
        if (!empty($username)) {
            return $username;
        }
        return null;
    }

    public function isSeamLessGame(){
        return true;
    }	
}

/*end of file*/