<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
set_include_path(dirname(__FILE__) . '/../unencrypt/phpseclib');
include_once 'Crypt/RSA.php';

class Game_api_ebet_bbtech extends Abstract_game_api {

	private $api_url;
	private $channelId;
	private $islive;
	private $tag;
	private $public_key;
	private $private_key;
	private $thirdParty;
	private $currency;
	private $logout_before_launch_game;
	private $game_key;
	private $config;
	private $game_url;

	const CODE_PLAYER_EXISTS=422;

	public function __construct() {
		parent::__construct();
		$this->api_url = $this->getSystemInfo('url');
		$this->channelId = $this->getSystemInfo('channelId');
		$this->islive = $this->getSystemInfo('live');
		$this->thirdParty = $this->getSystemInfo('thirdParty');
		$this->tag = $this->getSystemInfo('tag');
		$this->public_key = $this->getSystemInfo('public_key');
		$this->private_key = $this->getSystemInfo('private_key');
		$this->currency = $this->getSystemInfo('currency');
		//for game launch
		$this->game_url = $this->getSystemInfo('game_url');
		$this->game_key = $this->getSystemInfo('game_key');
		$this->config = $this->getSystemInfo('config');

		$this->logout_before_launch_game=$this->getSystemInfo('logout_before_launch_game', true);

		# init RSA
		$this->rsa = new Crypt_RSA();
		$this->rsa->setSignatureMode(CRYPT_RSA_SIGNATURE_PKCS1);
		$this->rsa->setHash('md5');
	}

	public function getPlatformCode() {
		return EBET_BBTECH_API;
	}

	public function generateUrl($apiName, $params) {
		$url = $this->api_url;
		return $url;
	}

	public function getHttpHeaders($params){
		return array("Content-Type" => "application/json");
		return array("Accept" => "application/json");
	}

	protected function customHttpCall($ch, $params) {
		$action = $params["method_action"];
		unset($params["method_action"]); //unset action not need on params

		$postParams = array(
			"channelId" => $this->channelId,
			"thirdParty" => $this->thirdParty,
			"tag" => $this->tag,
			"action" => array(
				"command" => $action,
				"parameters" => $params
			),
			"live" => $this->islive,
			"timestamp" => time()
		);
		$postParams["signature"] = $this->encrypt($this->channelId.$this->thirdParty.$this->tag.$postParams["timestamp"]);
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postParams,true));
	}

	function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null) {
		return array(false, null);
	}

	function processResultBoolean($responseResultId, $resultArr, $playerName = null) {
		$success = true;
		$cnt=count($resultArr['result']);
		if($cnt<1000){
			$this->CI->utils->debug_log('resultArr', $resultArr, $responseResultId, $playerName);
		}else{
			$this->CI->utils->debug_log('resultArr', count($resultArr), $responseResultId, $playerName);
		}
		if(@$resultArr['status']!=200){

			$success = false;

		}else{

			$response = json_decode(@$resultArr['result'],true);
			if(isset($response['result']) && $response['result'] == false ){
				$success = false;
			}
		}

		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->error_log('EBET_BBTECH_API got error', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
		}
		return $success;
	}

	/**
	 * overview : get game time to server time
	 *
	 * @return string
	 */
	/*function getGameTimeToServerTime() {
		return '+12 hours'; #(GMT -4)
	}*/

	/**
	 * overview : get server time to game time
	 *
	 * @return string
	 */
	/*function getServerTimeToGameTime() {
		return '-12 hours';
	}*/

	function callback($result = null, $platform = 'web') {
		if($platform == 'web'){
			$playerId = $this->getPlayerIdByExternalAccountId($result['userId']);
			if(!empty($playerId)){
				$status = array(
					"status" => 200,
				);
			}else{
				$status = array(
					"status" => 0,
					"message"=> "User not found."
				);
			}
			return $status;
		}
	}

	function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
		parent::createPlayer($playerName, $playerId, $password, $email, $extra);
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForCreatePlayer',
			'playerName' => $playerName,
			'playerId' => $playerId
		);

		$params = array(
			'playerName'=> $playerName,
			'method_action'=> "createplayer"
		);

		return $this->callApi(self::API_createPlayer, $params, $context);
	}

	function processResultForCreatePlayer($params){

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJsonArr = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $resultJsonArr, $playerName);
		$result = json_decode($resultJsonArr['result'],true);
		$playerId = $this->getVariableFromContext($params, 'playerId');
		if($success){
			//update external AccountID
			$this->updateExternalAccountIdForPlayer($playerId, $result['playerId']);

		}else{

			if(isset($resultJsonArr['status']) && $resultJsonArr['status']==self::CODE_PLAYER_EXISTS){
				$this->CI->utils->debug_log('player exists', $playerName);
				$success=true;
			}

		}

		return array($success, $resultJsonArr);

	}

	function depositToGame($userName, $amount, $transfer_secure_id=null) {

		$playerName = $this->getGameUsernameByPlayerUsername($userName);
		// $remitno = date("YmdHis").rand(1,1000);
		$remitno = $this->tag.time();
		$externalId = $this->getExternalAccountIdByPlayerUsername($userName);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForDepositToGame',
			'playerName' => $playerName,
			'sbe_playerName' => $userName,
			'amount' => $amount,
			'transaction_id'=>$remitno,
		);

        $context['enabled_guess_success_for_curl_errno_on_this_api']=$this->enabled_guess_success_for_curl_errno_on_this_api;
        // $context['is_timeout_mock']=$this->getSystemInfo('is_timeout_mock', false);

		$params = array(
			"playerId" => $externalId,
			"referenceId" => $remitno,
			"action" => "deposit", // deposit - deposit , withdrawal - withdraw
			"amount" => $amount,
			"method_action" => "transfer"
		);

		return $this->callApi(self::API_depositToGame, $params, $context);

	}

	function processResultForDepositToGame($params) {

		$playerName = $this->getVariableFromContext($params, 'playerName');
		$sbe_playerName = $this->getVariableFromContext($params, 'sbe_playerName');
		$amount = $this->getVariableFromContext($params, 'amount');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$result = array('response_result_id' => $responseResultId);
		$success = false;
		$transaction_id=$this->getVariableFromContext($params, 'transaction_id');

		if ($this->processResultBoolean($responseResultId, $resultArr,$playerName)) {
			//get current sub wallet balance
			$playerBalance = $this->queryPlayerBalance($sbe_playerName);
			//for sub wallet
			$afterBalance = @$playerBalance['balance'];
			$result["external_transaction_id"] = $transaction_id;
			if(!empty($afterBalance)){
				$result["currentplayerbalance"] = $afterBalance;
			}
			$result["userNotFound"] = false;
			$success = true;
			//update
			$playerId = $this->getPlayerIdInGameProviderAuth($playerName);
			if ($playerId) {
				//deposit
				$this->insertTransactionToGameLogs($playerId, $playerName, $afterBalance, $amount, $responseResultId,$this->transTypeMainWalletToSubWallet());

			} else {
				$this->CI->utils->debug_log('error', 'cannot get player id from ' . $playerName . ' getPlayerIdInGameProviderAuth');
			}
		}

		return array($success, $result);

	}

	function withdrawFromGame($userName, $amount, $transfer_secure_id=null) {

		$playerName = $this->getGameUsernameByPlayerUsername($userName);
		$remitno = $this->tag.time();
		$externalId = $this->getExternalAccountIdByPlayerUsername($userName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForWithdrawFromGame',
			'playerName' => $playerName,
			'sbe_playerName' => $userName,
			'transaction_id'=>$remitno,
			'amount' => $amount
		);

		$params = array(
			"playerId" => $externalId,
			"referenceId" => $remitno,
			"action" => "withdrawal", // deposit - deposit , withdrawal - withdraw
			"amount" => $amount,
			"method_action" => "transfer"
		);
		return $this->callApi(self::API_withdrawFromGame, $params, $context);
	}

	function processResultForWithdrawFromGame($params) {

		$playerName = $this->getVariableFromContext($params, 'playerName');
		$sbe_playerName = $this->getVariableFromContext($params, 'sbe_playerName');
		$amount = $this->getVariableFromContext($params, 'amount');

		$transaction_id=$this->getVariableFromContext($params, 'transaction_id');

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$result = array('response_result_id' => $responseResultId);
		$success = false;
		if ($this->processResultBoolean($responseResultId, $resultArr,$playerName)) {
			//get current sub wallet balance
			$playerBalance = $this->queryPlayerBalance($sbe_playerName);

			//for sub wallet
			$afterBalance = @$playerBalance['balance'];
			$result["external_transaction_id"] = $transaction_id;
			if(!empty($afterBalance)){
				$result["currentplayerbalance"] = $afterBalance;
			}
			$result["userNotFound"] = false;
			$success = true;
			//update
			$playerId = $this->getPlayerIdInGameProviderAuth($playerName);
			if ($playerId) {
				//withdraw
				$this->insertTransactionToGameLogs($playerId, $playerName, $afterBalance, $amount, $responseResultId,
					$this->transTypeSubWalletToMainWallet());

			} else {
				$this->CI->utils->debug_log('error', 'cannot get player id from ' . $playerName . ' getPlayerIdInGameProviderAuth');
			}

		} else {
			$result["userNotFound"] = true;
		}
		return array($success, $result);
	}

	function queryPlayerBalance($userName) {

		$playerName = $this->getGameUsernameByPlayerUsername($userName);
		$externalId = $this->getExternalAccountIdByPlayerUsername($userName);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryPlayerBalance',
			'playerName' => $playerName
		);

		$params = array(
			"playerId" => $externalId,
			"method_action" => "getplayerinfo"
		);
		return $this->callApi(self::API_queryPlayerBalance, $params, $context);
	}

	function processResultForQueryPlayerBalance($params) {

		$playerName = $this->getVariableFromContext($params, 'playerName');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);
		$result = array();
		if ($success) {
			$resultArr = json_decode($resultArr["result"],true);
			if(isset($resultArr['balance'])){
				$result['balance'] = @floatval($resultArr['balance']);

				if ($playerId = $this->getPlayerIdInGameProviderAuth($playerName)) {
					$this->CI->utils->debug_log('query balance playerId', $playerId, 'playerName', $playerName, 'balance', $result['balance']);
				} else {
					$this->CI->utils->debug_log('cannot get player id from ' . $playerName . ' getPlayerIdInGameProviderAuth');
				}
			}
		}
		return array($success, $result);
	}

	function queryForwardGame($username, $extra=null) {
		$externalId = $this->getExternalAccountIdByPlayerUsername($username);
		$key 		= $this->game_key;
		$config 	= $this->config;
		$game_url	= $this->game_url;
		$params = array(
			"key" 			=> $key,
			"userid" 		=> $externalId,
			"config"		=> $config,
			"gameconfig"	=> $extra['game_name']
		);
		$url_params = http_build_query($params);
		$generateUrl = $game_url.'?'.$url_params;
		$data = [
            'url' => $generateUrl,
            'success' => true
        ];
        $this->utils->debug_log(' BBTECH generateUrl - =================================================> ' . $generateUrl);
        return $data;
	}

	function blockPlayer($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$success = $this->blockUsernameInDB($playerName);
		return array("success" => true);
	}

	function unblockPlayer($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$success = $this->unblockUsernameInDB($playerName);
		return array("success" => true);
	}

	function syncOriginalGameLogs($token = false) {
		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
		//observer the date format
		$startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
    	$endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));
    	$startDate->modify($this->getDatetimeAdjust());

		$startDate=$startDate->format('Y-m-d H:i:s');
		$endDate=$endDate->format('Y-m-d H:i:s');
		$page = 1;
		$take = 5000; // max data get

		return $this->_continueSync( $startDate, $endDate, $take, $page );

	}

	function _continueSync( $startDate, $endDate, $take = 0, $page = 1){
		$return = $this->syncEbetBbtechGamelogs($startDate,$endDate,$take,$page);
		if(isset($return['count'])){
			if( $return['count'] == $take ){
				$skip += $take;
				return $this->_continueSync( $startDate, $endDate, $take, $page );
			}
		}
		return $return;
	}


	function syncEbetBbtechGamelogs($startDate,$endDate,$take,$page){

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForSyncGameRecords',
			'startDate' => $startDate,
			'endDate' => $endDate,
			'take' => $take,
			'skip' => $page
		);

		$params = array(
			"startDate" => $startDate,
			"endDate" => $endDate,
			// "type" => "casinolive", // game type
			"pageNumber" => $page, // page number
			"pageSize" => $take, //page Size default is 5000
			"method_action" => "getrawbethistory"
		);
		// echo"<pre>";print_r($params);exit();
		$this->utils->debug_log('=====================> EBETBBTECH syncOriginalGameLogs params', $params);

		return $this->callApi(self::API_syncGameRecords, $params, $context);
	}

	function processResultForSyncGameRecords($params) {

		$this->CI->load->model(array('ebetbbtech_game_logs'));
		$resultArr = $this->getResultJsonFromParams($params);
		$resultArr['isgamelogs'] = true; // tag gamelogs for process boolean
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr);
		$count = 0;
		$this->utils->debug_log('=====================> EBETBBTECH syncOriginalGameLogs result', count($resultArr));
		if ($success) {
			$rarr = json_decode($resultArr['result'],true);
			$gameRecords = $rarr["betHistories"];
			if(!empty($gameRecords)) {
				$availableRows = $this->CI->ebetbbtech_game_logs->getAvailableRows($gameRecords);
				if(!empty($availableRows)) {
					foreach ($availableRows as $record) {
						$datetime = isset($record['transactionDate']) ? $record['transactionDate'] / 1000 : NULL;
						$insertRecord = array();
						$playerID = $this->getPlayerIdInGameProviderAuth(strtolower($record['playerName']));
						$playerUsername = $this->getGameUsernameByPlayerId($playerID);
						//Data from BBTECH API
						$insertRecord['providerId'] 	= isset($record['providerId']) ? $record['providerId'] : NULL;
						$insertRecord['userId'] 		= isset($record['userId']) ? $record['userId'] : NULL;
						$insertRecord['playerName'] 	= isset($record['playerName']) ? $record['playerName'] : NULL;
						$insertRecord['amount'] 		= isset($record['amount']) ? $record['amount'] : NULL;
						$insertRecord['remoteTranId'] 	= isset($record['remoteTranId']) ? $record['remoteTranId'] : NULL;
						$insertRecord['gameId'] 		= isset($record['gameId']) ? $record['gameId'] : NULL;
						$insertRecord['gameName'] 		= isset($record['gameName']) ? $record['gameName'] : NULL;
						$insertRecord['roundId'] 		= isset($record['roundId']) ? $record['roundId'] : NULL;
						$insertRecord['trnType'] 		= isset($record['trnType']) ? $record['trnType'] : NULL;
						$insertRecord['thirdParty'] 	= isset($record['thirdParty']) ? $record['thirdParty'] : NULL;
						$insertRecord['tag'] 			= isset($record['tag']) ? $record['tag'] : NULL;
						$insertRecord['transactionDate']= $this->gameTimeToServerTime(date('Y-m-d H:i:s', ($datetime)));
						//extra info from SBE
						$insertRecord['Username'] = $playerUsername;
						$insertRecord['PlayerId'] = $playerID;
						$insertRecord['external_uniqueid'] = $insertRecord['remoteTranId']; //add external_uniueid for og purposes
						$insertRecord['response_result_id'] = $responseResultId;
						//insert data to BBTECH gamelogs table database
						$this->CI->ebetbbtech_game_logs->insertGameLogs($insertRecord);
						$count++;
					}
				}
			}
		}

		return array($success,array('count'=>$count));
	}

	function syncMergeToGameLogs($token) {

		$this->CI->load->model(array('ebetbbtech_game_logs','game_logs'));

		$dateTimeFrom = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$dateTimeFrom->modify($this->getDatetimeAdjust());
		$dateTimeTo = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
		//observer the date format
		$startDate = $dateTimeFrom->format('Y-m-d H:i:s');
		$endDate = $dateTimeTo->format('Y-m-d H:i:s');

		$rlt = array('success' => true);

		$result = $this->CI->ebetbbtech_game_logs->getGameLogStatistics($startDate, $endDate);
		$cnt = 0;
		if ($result) {

			// echo"<pre>";print_r($betAmount);exit();
			$unknownGame = $this->getUnknownGame();
			foreach ($result as $ebetbbtech_data) {

				$payout = $this->CI->ebetbbtech_game_logs->getPayOutByRoundId($ebetbbtech_data['roundId']);

				// $this->CI->utils->debug_log('========total payout==============', $payout['amount'], $ebetbbtech_data['UserName']);

				$betAmount = 0 - $ebetbbtech_data['amount'];
				$resultAmount = ($payout['amount'] == 0) ? $ebetbbtech_data['amount'] : $payout['amount']-$betAmount;
				if (!$ebetbbtech_data['PlayerId']) {
					continue;
				}
				$cnt++;
				$game_description_id = $ebetbbtech_data['game_description_id'];
				$game_type_id = $ebetbbtech_data['game_type_id'];

				//for real bet
				$extra = array('trans_amount'=> $betAmount );
				//end
				if (empty($game_description_id)) {
					$game_description_id = $unknownGame->id;
					$game_type_id = $unknownGame->game_type_id;
				}
				$this->syncGameLogs(
					$game_type_id,
					$game_description_id,
					$ebetbbtech_data['game_code'],
					$ebetbbtech_data['game_type'],
					$ebetbbtech_data['game'],
					$ebetbbtech_data['PlayerId'],
					$ebetbbtech_data['UserName'],
					$betAmount,
					$resultAmount,
					null, # win_amount
					null, # loss_amount
					null, # after_balance
					0, # has_both_side
					$ebetbbtech_data['external_uniqueid'],
					$ebetbbtech_data['game_date'], //start
					$ebetbbtech_data['game_date'], //end
					$ebetbbtech_data['response_result_id'],
					Game_logs::FLAG_GAME,
					$extra
				);

			}
		}

		$this->CI->utils->debug_log('syncMergeToGameLogs monitor', 'count', $cnt);
		return $rlt;
	}

	function changePassword($playerName, $oldPassword = null, $newPassword) {
		return $this->returnUnimplemented();
	}


	function login($userName, $password = null) {
		return $this->queryForwardGame($userName, $password);
	}

	function queryTransaction($transactionId, $extra) {
		return $this->returnUnimplemented();
	}

	function syncPlayerAccount($username, $password, $playerId) {
		return $this->returnUnimplemented();
	}

	function queryPlayerInfo($playerName) {
		return $this->returnUnimplemented();
	}

	function logout($playerName, $password = null) {
		return $this->returnUnimplemented();
	}

	function updatePlayerInfo($playerName, $infos) {
		return $this->returnUnimplemented();
		// return array("success" => true);
	}

	function queryPlayerDailyBalance($playerName, $playerId, $dateFrom = null, $dateTo = null) {
		return $this->returnUnimplemented();
	}

	function queryGameRecords($dateFrom, $dateTo, $playerName = null) {
		return $this->returnUnimplemented();
	}

	function checkLoginStatus($playerName) {
		return $this->returnUnimplemented();
	}

	public function checkLoginToken($playerName, $token) {
		return $this->returnUnimplemented();

	}

	function totalBettingAmount($playerName, $dateTimeFrom, $dateTimeTo) {
		return $this->returnUnimplemented();
	}

	function isPlayerExist($userName) {

		$playerName = $this->getGameUsernameByPlayerUsername($userName);
		$externalId = $this->getExternalAccountIdByPlayerUsername($userName);
		$playerId   = $this->getPlayerIdInGameProviderAuth($playerName);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForIsPlayerExists',
			'playerName' => $playerName,
			'playerId' 			=> $playerId
		);

		$params = array(
			"playerId" => $externalId,
			"method_action" => "getplayerinfo"
		);

		return $this->callApi(self::API_isPlayerExist, $params, $context);

	}

	function processResultForIsPlayerExists($params) {

		$playerId = $this->getVariableFromContext($params, 'playerId');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr);

		if($success){
	       $result['exists'] = true;
	       $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
		}
		else{
			if($resultArr['status'] == 422){
				$success = true;
				$result['exists'] = false;
			}
			else{
				$result['exists'] = null;
			}
		}
		return array($success, $result);
	}

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

	public function convertTransactionAmount($amount){
		return floor($amount);
	}

	public function gameAmountToDB($amount) {
		//only need 2
		return round(floatval($amount), 2);
	}

	public function onlyTransferPositiveInteger(){
		return true;
	}

	# HELPER ########################################################################################################################################

}

/*end of file*/