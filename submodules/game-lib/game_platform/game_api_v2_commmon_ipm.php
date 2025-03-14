<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

/**
 * document: IMOne API Web Services Specification Document Version <2.1.4>
 *
 */
class Game_api_v2_commmon_ipm extends Abstract_game_api {
	private $api_url;
	public $merchant_code;
	public $currency;
	public $game_url;
	public $sync_time_interval;
	public $sync_days_interval;
	public $sleep_time;
    public $languange;
    public $report_domain;

	const API_checkMobileLoginToken = "checkMobileLoginToken";
	const URI_MAP = array(
		self::API_createPlayer 			=> '/Player/Register',
		self::API_isPlayerExist 		=> '/Player/CheckExists',
		self::API_changePassword 		=> '/Player/ResetPassword',
		self::API_queryPlayerBalance 	=> '/Player/GetBalance',
		self::API_depositToGame 		=> '/Transaction/PerformTransfer',
		self::API_withdrawFromGame      => '/Transaction/PerformTransfer',
		self::API_checkLoginToken		=> '/Game/NewLaunchGame',
		self::API_checkMobileLoginToken => '/Game/NewLaunchMobileGame',
		self::API_queryGameRecords		=> '/Report/GetBetLog',
		self::API_queryTransaction		=> '/Transaction/CheckTransferStatus'
	);

	const SUCCESS = 0;
	const PLAYER_NOT_EXIST = 504;

	//Product Wallet Code
	const PRODUCT_CODE_MWG_FISHING = 2;
	const PRODUCT_CODE_GG_FISHING = 4;
	const PRODUCT_CODE_IM_SLOT = 101;
	const PRODUCT_CODE_PT = 102;
	const PRODUCT_CODE_IM_LIVE = 201;
	const PRODUCT_CODE_IM_SPORTSBOOK = 301;
	const PRODUCT_CODE_IM_ESPORTS = 401;
	const DEPOSIT_TRANSACTION = "DEP";
	const WITHDRAWAL_TRANSACTION = "WIT";

	//DateFilterType
	const BET_DATE = 1;
	const EVENT_DATE = 2;

	//BetStatus
	const NOT_SETTLED = 0;
	const SETTLED = 1;
	const ALL_SETTLED = null;
	const Pending = 0;
	const Confirmed = 1;
	const Cancelled  = 2;
	const NOT_CANCEL = 0;
	const CANCEL = 1;

	const IM_SPORTSBOOK = "IMSB";

	public function __construct() {
		parent::__construct();
		$this->api_url = $this->getSystemInfo('url');
		$this->merchant_code = $this->getSystemInfo('merchant_code');
		$this->currency = $this->getSystemInfo('currency');
		$this->sync_time_interval = $this->getSystemInfo('sync_time_interval', '+10 minutes');
		$this->sync_days_interval = $this->getSystemInfo('sync_days_interval', '-7 day');
		$this->sleep_time = $this->getSystemInfo('sleep_time', '60');
        $this->language = $this->getSystemInfo('language', 'en-us');
        $this->report_domain = $this->getSystemInfo('report_domain');
	}

	# ABSTRACT HELPER ########################################################################################################################################
	function getPlatformCode() {

    }

	function generateUrl($apiName, $params) {

		$apiUri = self::URI_MAP[$apiName];
		$url = $this->api_url . $apiUri;

        if ($apiName == self::API_queryGameRecords) {
                $url = $this->report_domain . $apiUri;
        }

		//echo $url;
		return $url;
	}

	public function getHttpHeaders($params){
		return array('Content-type' => 'application/json');
	}

	protected function customHttpCall($ch, $params) {
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params, true));
		curl_setopt( $ch, CURLOPT_AUTOREFERER, true );
  		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
	}

	function processResultBoolean($responseResultId, $resultJsonArr, $playerName = null, $check_exists=false, &$exists=false) {

		$success = (! empty($resultJsonArr) && $resultJsonArr['Code'] == self::SUCCESS);// || $resultJsonArr['Code'] == self::PLAYER_NOT_EXIST;

		if(isset($resultJsonArr['Code']) && $resultJsonArr['Code']==558 || $resultJsonArr['Code']==579){
			$success=true;
		}

		if ( ! $success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->error_log('IPM got error', $responseResultId, 'playerName', $playerName, 'result', $resultJsonArr);
		}

		return $success;
	}

	function gameAmountToDB($amount) {
		return round(floatval($amount), 2);
	}
	# ABSTRACT HELPER ########################################################################################################################################



	# IMPLEMENTED INTERFACE METHODS #############################################################################################################################
	public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
		parent::createPlayer($playerName, $playerId, $password, $email, $extra);
		$game_username = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForCreatePlayer',
			'playerId' => $playerId,
			'playerName' => $playerName,
		);

		$params = array(
			'MerchantCode' => $this->merchant_code,
			'PlayerId' => $game_username,
			'Currency' => $this->currency,
			'Password' => $password
		);

		return $this->callApi(self::API_createPlayer, $params, $context);

	}

	public function processResultForCreatePlayer($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJsonArr = $this->getResultJsonFromParams($params);
		$this->CI->utils->debug_log('processResultForCreatePlayer ==========================>', $resultJsonArr);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$playerId = $this->getVariableFromContext($params, 'playerId');
		$success = $this->processResultBoolean($responseResultId, $resultJsonArr, $playerName);
		$result = array(
			'playerName' => $playerName
		);

		//update register
		if ($success) {
			$this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
		}

		return array($success, $result);

	}

	public function isPlayerExist($userName) {
		$playerName = $this->getGameUsernameByPlayerUsername($userName);
        $playerId   = $this->getPlayerIdInGameProviderAuth($playerName);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForIsPlayerExist',
			'playerName' => $playerName,
			'playerId' => $playerId
		);

		$params = array(
			'MerchantCode' => $this->merchant_code,
			'PlayerId' => $playerName,
		);

        return $this->callApi(self::API_isPlayerExist, $params, $context);
	}

	public function processResultForIsPlayerExist($params) {
		$playerId = $this->getVariableFromContext($params, 'playerId');
        $responseResultId = $this->getResponseResultIdFromParams($params);
      	$resultJsonArr = $this->getResultJsonFromParams($params);
      	$this->CI->utils->debug_log('processResultForIsPlayerExist ==========================>', $resultJsonArr);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $success = $this->processResultBoolean($responseResultId, $resultJsonArr, $playerName);

        if($success){
        	if ($resultJsonArr['Code']== self::PLAYER_NOT_EXIST) {
	        	$result = array('exists' => false); # Player not found
	        }else{
	        	$result = array('exists' => true);
	        	$this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
	        }
	    }else if($resultJsonArr['Code'] == self::PLAYER_NOT_EXIST){

	    	$success=true;
	        $result = array('exists' => false); # Player not found

        }else{
            $result = array('exists' => null); #api other error

	    }
        return array($success, $result);
	}


	public function changePassword($playerName, $oldPassword = null, $newPassword) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
        $playerId   = $this->getPlayerIdInGameProviderAuth($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForIsChangePassword',
			'playerName' => $playerName,
			'playerId' => $playerId
		);

		$params = array(
			'MerchantCode' 	=> $this->merchant_code,
			'PlayerId' 		=> $playerName,
			"Password"		=> $newPassword
		);

        return $this->callApi(self::API_changePassword, $params, $context);
	}

	public function processResultForIsChangePassword($params) {
		$playerId = $this->getVariableFromContext($params, 'playerId');
        $responseResultId = $this->getResponseResultIdFromParams($params);
      	$resultJsonArr = $this->getResultJsonFromParams($params);
      	$this->CI->utils->debug_log('processResultForIsChangePassword ==========================>', $resultJsonArr);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $success = $this->processResultBoolean($responseResultId, $resultJsonArr, $playerName);

        $result = array(
			'playerName' => $playerName
		);

		return array($success, $result);
	}

	public function queryForwardGame($playerName, $extra) {

		$result = $this->getAccessToken($playerName,$extra);

        if (!empty($result['success'])) {
            $data = [
                'url' => $this->game_url,
                'success' => true
            ];
        }

        return $data;

        //return $this->returnUnimplemented();
	}

	public function queryPlayerBalance($playerName) {
		return $this->returnUnimplemented();
	}

	public function login($playerName, $password = NULL) {
		return $this->returnUnimplemented();
	}

	public function processResultForLogin($params) {
		return $this->returnUnimplemented();
	}

	public function logout($playerName, $password = NULL) {
		return $this->returnUnimplemented();
	}

	public function processResultForLogout($params) {
		return $this->returnUnimplemented();
	}

	public function depositToGame($playerName, $amount, $transfer_secure_id=null) {
		return $this->returnUnimplemented();
	}

	public function processResultForDepositToGame($params) {
		return $this->returnUnimplemented();
	}

	public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null) {
		return $this->returnUnimplemented();
	}

	public function processResultForWithdrawFromGame($params) {
		return $this->returnUnimplemented();
	}

	public function checkFundTransfer($playerName, $externaltransactionid) {
		return $this->returnUnimplemented();
	}

	public function processResultForCheckFundTransfer($params) {
		return $this->returnUnimplemented();
	}

	public function batchQueryPlayerBalance($playerNames, $syncId = null) {
		return $this->returnUnimplemented();
	}

	public function processResultForBatchQueryPlayerBalance($params) {
		return $this->returnUnimplemented();
	}

	function isInvalidRow($row) {
		// return $row['settled'] != 1; # TODO: CHECKE
		return false;
	}

	public function syncOriginalGameLogs($token) {
		return $this->returnUnimplemented();
	}

	const GAME_HISTORY_FIELDS=array(
	    'betId',
	    'betTime',
	    'memberCode',
	    'matchDateTime',
	    'sportsName',
	    'matchID',
	    'leagueName',
	    'homeTeam',
	    'awayTeam',
	    'favouriteTeamFlag',
	    'betType',
	    'selection',
	    'handicap',
	    'oddsType',
	    'odds',
	    'currency',
	    'betAmt',
	    'result',
	    'HTHomeScore',
	    'HTAwayScore',
	    'FTHomeScore',
	    'FTAwayScore',
	    'BetHomeScore',
	    'BetAwayScore',
	    'settled',
	    'betCancelled',
	    'bettingMethod',
	    'BTStatus',
	    'BTComission',
	    'uniqueid',
	    'external_uniqueid',
	    'gameshortcode',
	    'response_result_id',
	    'BTBuyBack',
	    'ParlayBetDetails',
	);

	public function filterFields($row){
		$rlt=[];
		if(!empty($row) && is_array($row)){
			foreach (self::GAME_HISTORY_FIELDS as $fldName) {
				if(array_key_exists($fldName, $row)){
					$rlt[$fldName]=$row[$fldName];
				}
			}
		}

		return $rlt;
	}

	public function isBettradeMode($row){
		$is=false;
		if(!empty($row)){
			if(isset($row->BTBuyBack)){
				$is=true;
			}
		}

		return $is;
	}

	// function isInvalidBetting($row) {
	// 	if($this->process_bettrade=='cancel'){
	// 	}
	// 	return $row->BTBuyBack>0; # TODO: CHECKE
	// }

	public function syncMergeToGameLogs($token) {
		return $this->returnUnimplemented();
	}

	private function getGameDescriptionInfo($row, $unknownGame, $gameDescIdMap) {
		return $this->returnUnimplemented();
	}

	# IMPLEMENTED INTERFACE METHODS #############################################################################################################################



	# UNIMPLEMENTED INTERFACE METHOD #############################################################################################################################

	public function queryPlayerInfo($playerName) {
		return $this->returnUnimplemented();
	}

	public function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null) {
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

	public function queryTransaction($transactionId, $extra) {
		return $this->returnUnimplemented();
	}
	# UNIMPLEMENTED INTERFACE METHOD #############################################################################################################################

	public function syncLostAndFound($token) {
		return $this->returnUnimplemented();
	}

	// protected function ipm_now() {
 //        return round(microtime(true)*1000);
 //    }

	// protected function transaction_id($trans_type) {
 //        $now = $this->ipm_now();
 //        $rnd = sprintf('%06x', mt_rand(0x0, 0xffffff));
 //        $response = $trans_type."_{$now}s{$rnd}";
 //        return $response;
 //    }

    public function init_fund_transaction($data){
    	// $transaction_id = $this->transaction_id($data["transaction_type"]);
    	//var_dump($data["transaction_type"]);die();
    	if($data["transaction_type"] == self::WITHDRAWAL_TRANSACTION){
    		$data["amount"] = -$data["amount"];
    	}
    	// if(isset($data['external_transaction_id'])){
    	$transaction_id=$data['external_transaction_id'];
    	// }

        $context = array(
            'callback_obj' => $this,
            'callback_method' => $data["callback_method"],
            'playerId' => $data["player_id"],
            'amount' => $data["amount"],
            'gameUsername' => $data['game_username'],
            'external_transaction_id' => $transaction_id,
        );

        $params = array(
			"MerchantCode" 	=> $this->merchant_code,
			"PlayerId" 		=> $data["game_username"],
			"ProductWallet"	=> $data["product_code"],
			"TransactionId" => $transaction_id,
			"Amount"		=> $data["amount"]
		);

        return $this->callApi($data["api_url"], $params, $context);
    }

    /**
	 * overview : get game record status
	 *
	 * @param $status
	 * @return int
	 */
	public function getGameRecordsStatus($status) {
		//var_dump($status);die();
		$this->CI->load->model(array('game_logs'));
		$status = strtolower($status);

		switch ($status) {
			case "settled":
				$status = Game_logs::STATUS_SETTLED;
				break;
			case "pending":
				$status = Game_logs::STATUS_PENDING;
				break;
			case "cancel":
				$status = Game_logs::STATUS_CANCELLED;
				break;
		}

		return $status;
	}
}