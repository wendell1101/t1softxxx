<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

/***************
	#Extra Info
	{
	    "CTGent": "CS0001", => this is from credential docs
	    "vendorKey": "4247a396b1bb56afb32f7efcb0b19f01", => this is from credential docs
	    "kgAESKey": "3f203ac36b4262ba28afa0366f4a63b9", => this is from credential docs
	    "homeURL": "www.og.local", => main home url of client
	    "adjust_datetime_minutes":  => 10 adjust time of start range of gamelogs
	}

***************/

class Game_api_kuma extends Abstract_game_api {

	private $api_url;
	private $CTGent;
	private $currency;
	private $language;
	private $live_key;
	private $homeURL;

	const DEFAULT_ODD_TYPE= 'A';
	const DEFAULT_CURRENCY = 'CNY';
	const API_prepareTransferCredit = 'prepareTransferCredit';
	const API_transferCreditConfirm = 'transferCreditConfirm';
	const TRANSFER_IN = '100';
	const TRANSFER_OUT = '200';
	const MAX_GAMELOGS_PER_REQUEST = 100;

	const URI_MAP = array(
		self::API_createPlayer => '/kg_api/action/doValidate.php',
		self::API_prepareTransferCredit => '/kg_api/action/doValidate.php',
		self::API_transferCreditConfirm => '/kg_api/action/doValidate.php',
		self::API_queryPlayerBalance => '/kg_api/action/doValidate.php',
		self::API_syncGameRecords => '/kg_api/action/doValidate.php',
		self::API_login => '/kg_api/action/doValidate.php',
		self::API_queryForwardGame => '/kg_api/action/fwValidate.php'
	);

	public function __construct() {

		parent::__construct();
		$this->api_url = $this->getSystemInfo('url');
		$this->CTGent = $this->getSystemInfo('CTGent');
		$this->live_key = $this->getSystemInfo('live_key');
		$this->vendorKey = $this->getSystemInfo('vendorKey');
		$this->kgAESKey = $this->getSystemInfo('kgAESKey');
		$this->homeURL = $this->getSystemInfo('homeURL');

		$this->oddtype = $this->getSystemInfo('oddtype');
        if ($this->oddtype == '') {
            $this->oddtype = self::DEFAULT_ODD_TYPE;
        }

		$this->currency = $this->getSystemInfo('currency');
        if (empty($this->currency)) {
            $this->currency = self::DEFAULT_CURRENCY;
        }

	}

	public function getPlatformCode() {

		return KUMA_API;

	}

	public function generateUrl($apiName, $params) {

		$apiUri = self::URI_MAP[$apiName];
		$postArray['params'] = $this->get_aes_string($this->get_format_string($params));
		$postArray['key'] = md5($this->get_format_string($params).$this->vendorKey);
		$url = $this->api_url.$apiUri."?params=".$postArray['params']."&key=".$postArray['key'];
		return $url;

	}

	private function get_format_string($dtlParms){

		$parmsString='';
		foreach ($dtlParms as $key => $value) {
		  	$parmsString = $parmsString.$key."=".$value.",";
		}
		return $parmsString;

	}

	private function get_aes_string($params){

		$key = pack('H*', $this->kgAESKey);
		$iv_size = @mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
		$iv = @mcrypt_create_iv($iv_size, MCRYPT_RAND);
		$cipher_params = @mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $params, MCRYPT_MODE_CBC, $iv);
		$cipher_params_iv = $iv . $cipher_params;
		return base64_encode($cipher_params_iv);

	}

	function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null) {

		return array(false, null);

	}

	function processResultBoolean($responseResultId, $resultArr, $playerName = null) {

		$success = false;
		if($resultArr['Status']==1){
			$success = true;
		}
		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('KUMA API got error', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
		}
		return $success;

	}

	function createPlayer($playerName, $playerId=null, $password=null, $email = null, $extra = null) {

		if($password==null){
			$password = $this->getPassword($playerName);
		}

		if(isset($password['password'])){
			$password = $password['password'];
		}

		if($playerId==null){
			$playerId = $this->CI->player_model->getPlayerIdByUsername($playerName);
		}
		parent::createPlayer($playerName, $playerId, $password, $email, $extra);

		$userName = $this->getGameUsernameByPlayerUsername($playerName);

		$token = date("YmdHis").rand(1,99);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForCreatePlayer',
			'playerName' => $userName
		);

		$params = array(
			'CTGent' => $this->CTGent,
			'Loginname' => $userName,
			'Method' => 'lg',
			'Oddtype' => $this->oddtype,
			'Cur' => $this->currency,
			'NickName' => $userName,
			'SecureToken' => $token
		);

		return $this->callApi(self::API_createPlayer, $params, $context);

	}

	function processResultForCreatePlayer($params){

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultText = $this->getResultTextFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$resultArray = $this->resultTextToArray($resultText);
		$success = $this->processResultBoolean($responseResultId, $resultArray, $playerName);
		return array($success, $resultArray);

	}

	protected function resultTextToArray($resultTxt){

		$resultArr = array();
		foreach(explode(",",$resultTxt) as $exVal){
			$val = explode("=",$exVal);
			$resultArr[$val[0]] = isset($val[1])?$val[1]:'';
		}
		return $resultArr;

	}

	function login($userName, $password = null) {

		if($password==null){
			$password = $this->getPassword($userName);
		}

		$userName = $this->getGameUsernameByPlayerUsername($userName);
		$token = date("YmdHis").rand(1,99);

		if(isset($password['password'])){
			$password = $password['password'];
		}

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForLogin',
			'playerName' => $userName,
			'token' => $token,
		);

		$params = array(
			'CTGent' => $this->CTGent,
			'Loginname' => $userName,
			'Method' => 'lg',
			'Oddtype' => $this->oddtype,
			'Cur' => $this->currency,
			'NickName' => $userName,
			'SecureToken' => $token
		);

		return $this->callApi(self::API_login, $params, $context);

	}

	function processResultForLogin($params){

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultText = $this->getResultTextFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$resultArray = $this->resultTextToArray($resultText);
		$resultArray['token'] = $this->getVariableFromContext($params, 'token');
		$success = $this->processResultBoolean($responseResultId, $resultArray, $playerName);
		return array($success, $resultArray);

	}

	function queryPlayerBalance($userName) {
		$isPlayerExist = $this->login($userName);
		if(!$isPlayerExist['success']&&$isPlayerExist['ErrorCode']=='M1143'){
			$this->createPlayer($userName);
		}

		$playerName = $this->getGameUsernameByPlayerUsername($userName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryPlayerBalance',
			'playerName' => $playerName
		);

		$params = array(
			'CTGent' => $this->CTGent,
			'Loginname' => $playerName,
			'Method' => 'gb',
			'Cur' => $this->currency
		);

		return $this->callApi(self::API_queryPlayerBalance, $params, $context);

	}

	function processResultForQueryPlayerBalance($params) {

		$playerName = $this->getVariableFromContext($params, 'playerName');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultText = $this->getResultTextFromParams($params);
		$resultArray = $this->resultTextToArray($resultText);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $resultArray,$playerName);
		$result = array();

		if ($success) {
			$result['balance'] = @floatval($resultArray['Data']);

			if ($playerId = $this->getPlayerIdInGameProviderAuth($playerName)) {
				$this->CI->utils->debug_log('query balance playerId', $playerId, 'playerName', $playerName, 'balance', $result['balance']);
			} else {
				$this->CI->utils->debug_log('cannot get player id from ' . $playerName . ' getPlayerIdInGameProviderAuth');
			}

		} else {
			if (@$resultArr['error'] == 'PLAYER NOT FOUND') {
				$result['exists'] = false;
			} else {
				$result['exists'] = true;
			}
		}

		return array($success, $result);

	}

	function prepareTransferCredit($userName, $amount,$type, $transfer_type, $transfer_secure_id=null){
		$isPlayerExist = $this->login($userName);
		if(!$isPlayerExist['success']&&$isPlayerExist['ErrorCode']=='M1143'){
			$isPlayerExist = $this->createPlayer($userName);
		}

		$playerName = $this->getGameUsernameByPlayerUsername($userName);
		$billNo = 'TF'.date("ymdHis").rand(1,99);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForPrepareTransferCredit',
			'playerName' => $playerName,
			'sbe_playerName' => $userName,
			'amount' => $amount,
			'Type' => $type,
			'transfer_type'=>$transfer_type,
		);

		$params = array(
			'CTGent' => $this->CTGent,
			'Loginname'=> $playerName,
			'Method' => 'tc',
			'Billno' => $billNo,
			'Credit' => $amount,
			'Cur' => $this->currency,
			'Type' => $type
		);

		return $this->callApi(self::API_prepareTransferCredit, $params, $context);

	}

	function processResultForPrepareTransferCredit($params) {

		$playerName = $this->getVariableFromContext($params, 'playerName');
		$sbe_playerName = $this->getVariableFromContext($params, 'sbe_playerName');
		$amount = $this->getVariableFromContext($params, 'amount');
		$type = $this->getVariableFromContext($params, 'Type');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultText = $this->getResultTextFromParams($params);
		$resultArray = $this->resultTextToArray($resultText);
		$success = $this->processResultBoolean($responseResultId, $resultArray,$playerName);
		$result = array('response_result_id' => $responseResultId);
		if($success){
			$TGSno = $resultArray["Data"];
			$result = $this->transferCreditConfirm($sbe_playerName,$playerName,$params['params'],$TGSno);
		}

		return array($success, $result);

	}

	function transferCreditConfirm($sbe_playerName,$playerName, $params, $TGSno){

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultTransferCreditConfirm',
			'playerName' => $playerName,
			'sbe_playerName' => $sbe_playerName,
			'external_transaction_id' => $TGSno,
		);

		$params['Method'] = "tcc";
		$params['TGSno'] = $TGSno;
		return $this->callApi(self::API_transferCreditConfirm, $params, $context);

	}

	function processResultTransferCreditConfirm($params){

		$playerName = $this->getVariableFromContext($params, 'playerName');
		$sbe_playerName = $this->getVariableFromContext($params, 'sbe_playerName');
		$type = $params['params']["Type"];
		$amount = $params['params']["Credit"];
		$resultText = $this->getResultTextFromParams($params);
		$resultArray = $this->resultTextToArray($resultText);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$success = $this->processResultBoolean($responseResultId, $resultArray,$playerName);

        $result['external_transaction_id']=$external_transaction_id;

		if ($success) {

            $playerId = $this->getPlayerIdInGameProviderAuth($playerName);
            if ($playerId) {
                $playerBalance = $this->queryPlayerBalance($sbe_playerName);
                $afterBalance = 0;

                if($type == self::TRANSFER_IN){ // Deposit
                	if ($playerBalance && $playerBalance['success']) {
	                    $afterBalance = $playerBalance['balance'];
	                } else {
	                    //IF GET PLAYER BALANCE FAILED
	                    $rlt = $this->CI->wallet_model->getSubWalletBy($playerId, $this->getPlatformCode());
	                    $afterBalance = $rlt->totalBalanceAmount;
	                    $this->CI->utils->debug_log('============= KUMA_API AFTER BALANCE FROM WALLET '.$type.' ######### ', $afterBalance);
	                }
	                // $responseResultId = $result['response_result_id'];
	                // Deposit
	                $this->insertTransactionToGameLogs($playerId, $playerName, $afterBalance, $amount, $responseResultId,
	                    $this->transTypeMainWalletToSubWallet());
                }else{ // Withdraw
                	if ($playerBalance && $playerBalance['success']) {
	                    $afterBalance = $playerBalance['balance'];
	                    $this->CI->utils->debug_log('============= KUMA_API AFTER BALANCE FROM API '.$type.' ######### ', $afterBalance);
	                } else {
	                    //IF GET PLAYER BALANCE FAILED
	                    $rlt = $this->CI->wallet_model->getSubWalletBy($playerId, $this->getPlatformCode());
	                    $afterBalance = $rlt->totalBalanceAmount;
	                    $this->CI->utils->debug_log('============= KUMA_API AFTER BALANCE FROM WALLET '.$type.' ######### ', $afterBalance);
	                }
	                // $responseResultId = $result['response_result_id'];
	                // Withdraw
	                $this->insertTransactionToGameLogs($playerId, $playerName, $afterBalance, $amount, $responseResultId,
	                    $this->transTypeSubWalletToMainWallet());
                }

            } else {
                $this->CI->utils->debug_log('error', '=============== cannot get player id from '.$playerName.' getPlayerIdInGameProviderAuth');
            }
        }

        return array($success, $result);

	}

	function depositToGame($userName, $amount, $transfer_secure_id=null){

		$type = self::TRANSFER_IN;
		$transfer_type = self::API_depositToGame;
		return $this->prepareTransferCredit($userName, $amount, $type, $transfer_type, $transfer_secure_id);

	}

	function withdrawFromGame($userName, $amount, $transfer_secure_id=null){

		$type = self::TRANSFER_OUT;
		$transfer_type = self::API_withdrawFromGame;
		return $this->prepareTransferCredit($userName, $amount, $type, $transfer_type, $transfer_secure_id);

	}

	function queryForwardGame($playerName, $gameId, $extra=null) {

		if ($extra['trial']) {

			$trial_games = array(
				'1011' => 'Z2VjUmdQdHBJd0JJdkZxRGV4bld2QT09',
				'1012' => 'Z2VjUmdQdHBJd0JJdkZxRGV4bld2QT09',
				'1013' => 'cEVYNjhvMTJNdzZBV041Rms4YWVXZz09',
				'1014' => 'NHNtaVdQUlRVNytlMDFxcW5GT1VPUT09',
				'1015' => 'RjBxZTF2VzdFaGR6YnNJdlE4N1ZnUT09',
				'1016' => 'UEFVbmh1MEJXTm5JaGdhaW9QZGRCdz09',
				'1017' => 'REpQaWZXSEpTd0UrSlZtblJRN3dUdz09',
				'1018' => 'cXlaNElKTUNSSlVKbVRxb2lOZE9qQT09',
				'1019' => 'dWVXK2tuMDBWUm04K1lCY0o5WnNpdz09',
				'1020' => 'V3p4djVCVTVLbGxHenBjaittK2gxZz09',
				'1021' => 'aWFaQWw4RGtnWmhiVXcyakxVVFFuQT09',
				'1022' => 'YXVHUlRzYmREUXlKdStWZXFNa3RLUT09',
				'1023' => 'Z28xRG1UbDJod2xQSkpQSHpYMlNBdz09',
				'1024' => 'clJvdkxGUHdRV2lyMmlMemZpM21sQT09',
				'1025' => 'MWtIaTkxb1FpeHAzMS8yZFlxNFYydz09',
				'1026' => 'dWswQkZYbGt3K3hCSFJtUGZ4RExyUT09'
			);

			return array(
				'success' => true,
				'url' => 'http://www.kgslots.com/freeplay/?game=' . $trial_games[$gameId]
			);
		}

		$isPlayerExist = $this->login($playerName);
		if(!$isPlayerExist['success']&&$isPlayerExist['ErrorCode']=='M1143'){
			$this->createPlayer($playerName);
		}

		$returnArr = $this->login($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryForwardGame',
			'playerName' => $playerName
		);

		$params = array(
			'CTGent' => $this->CTGent,
			'Loginname' => $playerName,
			'Method' => 'fwgame_opt',
			'Oddtype' => $this->oddtype,
			'Cur' => $this->currency,
			'HomeURL' => $this->homeURL,
			'GameId' => $gameId,
			'SecureToken' => $returnArr['token'],
			'Lang' => $extra['lang']
		);

		return $this->callApi(self::API_queryForwardGame, $params, $context);

	}

	function processResultForQueryForwardGame($params){

		$playerName = $this->getVariableFromContext($params, 'playerName');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultText = $this->getResultTextFromParams($params);
		$resultArray = $this->resultTextToArray($resultText);
		$success = $this->processResultBoolean($responseResultId, $resultArray,$playerName);

		$explodedText = explode(',',$resultText);
		$url = str_replace("Data=", "", $explodedText[1]);
		$result = array('url' => $url);
		return array($success, $result);

	}

	function syncOriginalGameLogs($token = false) {

		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$startDate->modify($this->getDatetimeAdjust());
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
		$last_sync_id = $this->CI->external_system->getLastSyncId($this->getPlatformCode());
		$startBillNo = $last_sync_id==null?0:((int)$last_sync_id+1);

		//observer the date format
		$startDate = $startDate->format('Y-m-d H:i:s');
		$endDate = $endDate->format('Y-m-d H:i:s');
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForSyncGameRecords',
			'startDate' => $startDate,
			'endDate' => $endDate,
			'token' => $token
		);

		$params = array(
			'CTGent' => $this->CTGent,
			'Start' => $startDate,
			'End' => $endDate,
			'Method' => 'gbrd',
			'BillNumber' => $startBillNo,
			'Count' => self::MAX_GAMELOGS_PER_REQUEST
		);

		return $this->callApi(self::API_syncGameRecords, $params, $context);

	}

	function processResultForSyncGameRecords($params) {

		$this->CI->load->model(array('kuma_game_logs', 'player_model'));
		$resultText = $this->getResultTextFromParams($params);
		$resultArray = json_decode($resultText,TRUE);
		$token = $this->getVariableFromContext($params, 'token');

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArray);
		$result = array();

		if ($success) {
			$gameRecords = json_decode($resultArray['Data'],TRUE);
			if ($gameRecords) {
				$availableRows = $this->CI->kuma_game_logs->getAvailableRows($gameRecords);
				if (isset($availableRows)) {
					foreach ($availableRows as $record) {
						$arrayToSave = array(
							"Username" => $record['Account'],
							"PlayerId" => $this->getPlayerIdInGameProviderAuth($record['Account']),
							"BillNo" => $record['BillNo'],
							"GameID" => str_replace("SLOT", "", $record['GameID']),
							"BetValue" => $record['BetValue'],
							"NetAmount" => $record['NetAmount'],
							"SettleTime" => $record['SettleTime'],
							"AgentsCode" => $record['AgentsCode'],
							"Account" => $record['Account'],
							"uniqueid" =>  $record['BillNo'],
							"external_uniqueid" => $record['BillNo'],
							"response_result_id" => $responseResultId,
						);

						$this->CI->kuma_game_logs->insertGameLogs($arrayToSave);
						$lastSyncId = $record['BillNo']; // update last sync ID
					}
					$result['data'] = $availableRows;
				}
			}
			//update last sync id
			if(isset($lastSyncId)){
				$this->CI->external_system->setLastSyncId($this->getPlatformCode(), $lastSyncId);
			}
			//100 is maximum data per request if meet condition, means there is remaining data
			if(count($gameRecords)==self::MAX_GAMELOGS_PER_REQUEST){
				$this->syncOriginalGameLogs($token);
			}
		}

		return array($success, $result);
	}

	function syncMergeToGameLogs($token) {

		$this->CI->load->model(array('game_logs', 'player_model', 'kuma_game_logs'));

		$dateTimeFrom = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$dateTimeFrom->modify($this->getDatetimeAdjust());
		$dateTimeTo = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

		//observer the date format
		$startDate = $dateTimeFrom->format('Y-m-d H:i:s');
		$endDate = $dateTimeTo->format('Y-m-d H:i:s');

		$this->CI->utils->debug_log('dateTimeFrom', $startDate, 'dateTimeTo', $endDate);


		$rlt = array('success' => true);
		$result = $this->CI->kuma_game_logs->getGameLogStatistics($startDate, $endDate);

		$cnt = 0;

		if ($result) {

			$unknownGame = $this->getUnknownGame();
			foreach ($result as $kuma_data) {
				$player_id = $kuma_data->PlayerId;

				if (!$player_id) {
					continue;
				}

				$cnt++;

				$bet_amount = $kuma_data->bet_amount;
				$result_amount = $kuma_data->result_amount - $bet_amount;

				$game_description_id = $kuma_data->game_description_id;
				$game_type_id = $kuma_data->game_type_id;

				if (empty($game_description_id)) {
					$game_description_id = $unknownGame->id;
					$game_type_id = $unknownGame->game_type_id;
				}

				$this->syncGameLogs(
					$game_type_id,
					$game_description_id,
					$kuma_data->game_code,
					$kuma_data->game_type,
					$kuma_data->game,
					$player_id,
					$kuma_data->Username,
					$bet_amount,
					$result_amount,
					null, # win_amount
					null, # loss_amount
					null, # after_balance
					0, # has_both_side
					$kuma_data->external_uniqueid,
					$kuma_data->date_created, //start
					$kuma_data->date_created, //end
					$kuma_data->response_result_id
				);

			}
		}

		$this->CI->utils->debug_log('syncMergeToGameLogs monitor', 'count', $cnt);
		return $rlt;
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

	function changePassword($playerName, $oldPassword = null, $newPassword) {
		return $this->returnUnimplemented();
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
		return $this->returnUnimplemented();
	}

}

/*end of file*/