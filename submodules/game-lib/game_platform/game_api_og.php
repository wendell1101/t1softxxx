<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

class Game_api_og extends Abstract_game_api {

	private $api_url;
	private $currency;
	private $agent;
	private $limit;
	private $limitvideo;
	private $limitroulette;
	private $UserKey;
	private $client_domain;
	private $settingsStartVendorId;

	private $vendor_id;
	private $add_time;

	const TRANSFER_IN = 'IN';
	const TRANSFER_OUT = 'OUT';
	const API_prepareTransferCredit = 'prepareTransferCredit';
	const API_transferCreditConfirm = 'transferCreditConfirm';
	const API_getVendorId = 'getVendorId';
	const ERROR_AGENT_NO_EXIST = "agent no exist";
    const ERROR_NO_DATA = "No_Data";
	const IFRAME = [
        "default" => 0,
        "iframe" => 1,
        "https" => 2,
        "iframe_https" => 3,
    ];

	const EXIST_PLAYER_FLAG = 1;

	public function __construct() {
		parent::__construct();
		$this->api_url = $this->getSystemInfo('url');
		$this->gamelogs_url = $this->getSystemInfo('gamelogs_url');
		$this->transfer_url = $this->getSystemInfo('transfer_url');
		$this->currency = $this->getSystemInfo('currency');
		$this->agent = $this->getSystemInfo('agent');
		$this->limit = $this->getSystemInfo('limit');
		$this->limitvideo = $this->getSystemInfo('limitvideo');
		$this->limitroulette = $this->getSystemInfo('limitroulette');
		$this->UserKey = $this->getSystemInfo('UserKey');
		$this->client_domain = $this->getSystemInfo('client_domain');
		$this->settingsStartVendorId = $this->getSystemInfo('settings_start_vendor_id');

		//0 means http without iframe, 1 means with iframe
		//2 = https without iframe, 3 means https with iframe
		$this->launcher_param_iframe = $this->getSystemInfo('launcher_param_iframe', 3);
		$this->max_call_attempt = $this->getSystemInfo('max_call_attempt', 10);
		$this->max_data_set = $this->getSystemInfo('max_data_set', 300);//default from og game is 300

		$this->platformname=$this->getSystemInfo('platformname', 'OG');
	}

	public function getPlatformCode() {
		return OG_API;
	}

    public function shouldGuessSuccessStatusCode($statusCode){
        return $statusCode==502 || $statusCode==500;
    }

	public function generateUrl($apiName, $params) {
		$params['agent'] = $this->agent;

		if(isset($params['report'])){
			$this->api_url = $this->gamelogs_url; # update api url to gamelogs
			unset($params['report']);
		}

		if(isset($params['method']) && ($params['method'] == 'ptc' || $params['method'] == 'ctc')){
			if(!empty($this->transfer_url)){
				$this->api_url = $this->transfer_url; # api url for transfer
			}
		}
		$params_string = '';
		$ctr =1;
		foreach ($params as $key => $value) {
			$params_string .=$key.'='.$value;
			if(count($params) != $ctr){
				$params_string .= "$";
			}
			$ctr++;
		}

		$newParams = base64_encode($params_string);
		$key = MD5($newParams.$this->UserKey);
		$url = $this->api_url.'?params='.$newParams.'&key='.$key;
		return $url;

	}

	public function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null) {
		return array(false, null);
	}

	public function processResultXml($responseResultId, $resultXml, $errArr, $info_must_be_1=false)
	{
		$success = true;
        $info = $resultXml; //already converted to array
		// $info = $this->getAttrValueromXml($resultXml, 'info');

		if (in_array($info, $errArr)) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('OG got error', $responseResultId, 'result', $resultXml);
			$success = false;
		}else if($info_must_be_1){
            if(is_array($info)){
                $success= $info[0]=='1';
            }else{
                $success= $info=='1';
            }
		}

		return $success;
	}

	public function processResultBoolean($responseResultId, $resultArr, $playerName = null) {
		$success = false;
		if(isset($resultArr['0'])&&$resultArr['0']=='1'){
			$success = true;
		}
		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('OG API got error ', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
		}
		return $success;
	}

	public function isPlayerExist($playerName){
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForIsPlayerExist',
			'playerId' => $playerId,
			'playerName' => $playerName,
			'gameUsername' => $gameUsername,
		);

		$params = array(
			'agent'	=>	$this->agent,
			'username' => $gameUsername,
			'method' => 'caie', #Value =“caie” is representing method CheckAccountIsExist method
		);

		return $this->callApi(self::API_isPlayerExist, $params, $context);
	}

	public function processResultForIsPlayerExist($params){

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultXml = (array)$this->getResultXmlFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
//		$success = $this->processResultBoolean($responseResultId, $resultXml, $playerName);
		$success = $this->processResultXml($responseResultId, $resultXml,
			array('key_error', 'network_error', '10'),true);

		$resultArr = $resultXml[0];  // exist if value is 1
		if ($resultArr == self::EXIST_PLAYER_FLAG ) {
			$success = false;
		} else {
			$success = true;
		}
		$result['exists'] = $resultArr == self::EXIST_PLAYER_FLAG ? true : false;
		// if ($success) {
		// 	$result = array('exists' => isset($resultXml[0]) ? $resultXml[0]=='1':false);
		// }else{
		// 	$result = array('exists' => null);
		// }

		return array($success, $result);
	}

	public function testTarget($methodName) {
		$this->init();
		$this->$methodName();
	}


	public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
		parent::createPlayer($playerName, $playerId, $password, $email, $extra);

		$password = $this->getPasswordString($playerName);
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForCreatePlayer',
			'playerId' => $playerId,
			'playerName' => $playerName,
			'gameUsername' => $gameUsername,
		);

		$params = array(
			'agent'	=>	$this->agent,
			'username' => $gameUsername,
			'moneysort' => $this->currency,
			'password' => $password,
			'limit' => $this->limit,
			'limitvideo' => $this->limitvideo,
			'limitroulette' => $this->limitroulette,
			'method' => 'caca' #Value =“caca” CheckAndCreateAccount
		);

		$this->CI->utils->debug_log('og params ======>', $params);
		return $this->callApi(self::API_createPlayer, $params, $context);
	}

	public function processResultForCreatePlayer($params){
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultXml = (array)$this->getResultXmlFromParams($params);

		$playerName = $this->getVariableFromContext($params, 'playerName');

//		$success = $this->processResultBoolean($responseResultId, $resultXml, $playerName);
		$success = $this->processResultXml($responseResultId, $resultXml,
			array('key_error', 'network_error', '0', '2', '3', '10'), true);

		return array($success, $resultXml);
	}

	public function changePassword($playerName, $oldPassword = null, $newPassword) {
		if (!empty($playerName)) {
			$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
			$playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
			//EditAccount
			$context = array(
				'callback_obj' => $this,
				'callback_method' => 'processResultForChangePassword',
				'playerName' => $playerName,
				'gameUsername' => $gameUsername,
				'playerId' => $playerId,
				'newPassword' => $newPassword,
			);

			$params = array(
				'agent'	=>	$this->agent,
				'username' => $gameUsername,
				'password' => $newPassword,
				'method' => 'ua' #Value ="ua" UpdateAccount method
			);

			return $this->callApi(self::API_changePassword, $params, $context);
		}
		return $this->returnFailed('empty player name');
	}

	public function processResultForChangePassword($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$playerName = $this->getVariableFromContext($params,'playerName');
		$newPassword = $this->getVariableFromContext($params,'newPassword');
		$playerId = $this->getVariableFromContext($params,'playerId');
		$resultXml = (array)$this->getResultXmlFromParams($params);
//		$success = $this->processResultBoolean($responseResultId, $resultXml, $playerName);
		$success = $this->processResultXml($responseResultId, $resultXml,
			array('key_error', 'network_error', '0', '10'), true);

		if ($success) {
			//sync password to game_provider_auth
			$this->updatePasswordForPlayer($playerId, $newPassword);
		}

		return array($success, $resultXml);
	}

	public function queryPlayerBalance($playerName) {
		if (!empty($playerName)) {
			$gameUserName = $this->getGameUsernameByPlayerUsername($playerName);
			$password = $this->getPasswordByGameUsername($gameUserName);

			$context = array(

				'callback_obj' => $this,
				'callback_method' => 'processResultForQueryPlayerBalance',
				'playerName' => $playerName,
				'gameUserName' => $gameUserName,
			);

			$params = array(
				'agent'	=>	$this->agent,
				'username' => $gameUserName,
				'password' => $password,
				'method' => 'gb', #Value="gb" is representing getbalance method
			);

			return $this->callApi(self::API_queryPlayerBalance, $params, $context);
		}
		return $this->returnFailed('empty player name');
	}

	public function processResultForQueryPlayerBalance($params) {

		$playerName = $this->getVariableFromContext($params, 'playerName');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultXml = (array)$this->getResultXmlFromParams($params);

		$this->CI->utils->debug_log('processResultForQueryPlayerBalance', $params['resultText']);

		$success = $this->processResultXml($responseResultId, $resultXml,
			array('key_error', 'network_error', 'account_not_exist', 'account_no_exist', 'error', '10','0'));

		$result = ['response_result_id'=>$responseResultId];

		## If API returns success the resultXml[0] will contains string of player's balance that contains decimal values
		if ($success && strpos($resultXml[0], '.') !== false && isset($resultXml[0])) {
			$result['balance'] = floatval($resultXml[0]);
			$result['exists'] = true;
		} else {
			$success = false;
		}

//		if($playerId = $this->getPlayerIdInGameProviderAuth($playerName)) {
//			$this->CI->utils->debug_log('OG GAME API query balance playerId', $playerId, 'playerName', $playerName, 'balance', $result['balance']);
//		}else{
//			$this->CI->utils->debug_log('OG GAME API cannot get player id from ' . $playerName . ' getPlayerIdInGameProviderAuth');
//		}
//		$result['exists'] = true;
		return array($success, $result);

	}

	public function depositToGame($playerName, $amount, $transfer_secure_id=null){
		$type = self::TRANSFER_IN;
		$transfer_type = self::API_depositToGame;
		$playerId = $this->getPlayerIdInPlayer($playerName);
		return $this->transfer($type, $transfer_type, $playerId, $playerName, $amount, $transfer_secure_id);
	}

	public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null){
		$type = self::TRANSFER_OUT;
		$transfer_type = self::API_withdrawFromGame;
		$playerId = $this->getPlayerIdInPlayer($playerName);
		return $this->transfer($type, $transfer_type, $playerId, $playerName, $amount, $transfer_secure_id);
	}

	public function transfer($type, $transfer_type, $playerId, $playerName, $amount, $transfer_secure_id = null){
		$this->CI->load->model(array('wallet_model'));
		$usernameWithoutPrefix = $playerName;

		$password = $this->getPasswordString($playerName);
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		if(empty($transfer_secure_id)){
			$transfer_secure_id=random_string('numeric', 13);
		}

		$billno=$this->agent.$transfer_secure_id;

		//always query balance first
		$result=$this->queryPlayerBalance($usernameWithoutPrefix);

		//$result['external_transaction_id']=$billno;

		$this->utils->debug_log('============= query player balance for '.$type.' billno', $billno, 'result', $result);

		if(!$result['success']){
			return $result;
		}

		$result = $this->transferCredit($playerId, $gameUsername, $password, $amount, $billno, $type, $transfer_type);

		$this->utils->debug_log('============= first transferCredit '.$type.' billno:'. $billno, 'result', $result);

		if ($result['success']) {
			$result = $this->transferCreditConfirm($playerId, $gameUsername, $password, $amount, $result['billno'], $type, $transfer_type);
			$this->CI->utils->debug_log('============= OG transfer '.$type.' result ######### ', $result);
		}else{
			//return failed , don't try
			$error_code = @$result['error_code'];
			switch($error_code) {
				case 'account_no_exist' :
					$result['reason_id'] = self::REASON_NOT_FOUND_PLAYER;
					break;
				case 'no_enough_credit' :
					$result['reason_id'] = self::REASON_NO_ENOUGH_BALANCE;
					break;
				case '0' :
					$result['reason_id'] = self::REASON_FAILED_FROM_API;
					break;
				case '10' :
					$result['reason_id'] = self::REASON_AGENT_NOT_EXISTED;
					break;
			}
			$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_DECLINED;

			return $result;
		}

		//only if transferCreditConfirm is failed
		if(!$result['success']){

			//try query order status
			$result = $this->transferCreditConfirm($playerId, $gameUsername, $password, $amount, $result['external_transaction_id'], $type, $transfer_type);
			$this->CI->utils->debug_log('============= get error when '.$type.' try transferCreditConfirm', $playerName, $amount, $billno, $result);

			//only for transfer to sub
			if(!$result['success'] && $type == self::TRANSFER_IN){

				$this->CI->utils->debug_log('============= convert success to true if still network error when '.$type, $playerName, $amount, $billno, $result);

				//convert to success
				$result['success']=true;
			}
		}

		if ($result['success']) {
			//update
//			$playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
//			if ($playerId) {
//				$playerBalance = $this->queryPlayerBalance($usernameWithoutPrefix);
//				$this->CI->utils->debug_log('============= OG QUERY_PLAYER_BALANCE '.$type.' ######### ', $playerBalance);
//
				$afterBalance = 0;
//				if ($playerBalance && $playerBalance['success']) {
//					$afterBalance = $playerBalance['balance'];
//					$this->CI->utils->debug_log('============= OG AFTER BALANCE FROM API '.$type.' ######### ', $afterBalance);
//				} else {
//					//IF GET PLAYER BALANCE FAILED
//					$rlt = $this->CI->wallet_model->getSubWalletBy($playerId, $this->getPlatformCode());
//					$afterBalance = $rlt->totalBalanceAmount;
//					$this->CI->utils->debug_log('============= OG AFTER BALANCE FROM WALLET '.$type.' ######### ', $afterBalance);
//				}

				$responseResultId = $result['response_result_id'];
				//insert into game logs
				// $this->insertTransactionToGameLogs($playerId, $gameUsername, $afterBalance, $amount, $responseResultId,$type == self::TRANSFER_IN ? $this->transTypeMainWalletToSubWallet() : $this->transTypeSubWalletToMainWallet());

				//should update database
				// $this->updatePlayerSubwalletBalance($playerId, $afterBalance);
//			} else {
//				$this->CI->utils->debug_log('error', '=============== cannot get player id from '.$playerName.' getPlayerIdInGameProviderAuth');
//			}
			$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
			$result['didnot_insert_game_logs']=true;
		}

		return $result;
	}


	public function transferCredit($playerId, $gameUsername, $password, $amount, $external_transaction_id, $transaction_type, $transfer_type)
	{
		$this->CI->load->helper('string');
		// $randStr = random_string('alnum', 16);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForTransferCredit',
			'playerId' => $playerId,
			'gameUsername' => $gameUsername,
			'billno' => $external_transaction_id,
			'transfer_type' => $transfer_type,
		);

		if($transaction_type==self::TRANSFER_IN){
			$context['enabled_guess_success_for_curl_errno_on_this_api']=$this->enabled_guess_success_for_curl_errno_on_this_api;
			// $context['is_timeout_mock']=$this->getSystemInfo('is_timeout_mock', false);
		}

		$params = array(
			'agent'	=>	$this->agent,
			'username'=> $gameUsername,
			'password' => $password,
			'billno' => $external_transaction_id,
			'type' => $transaction_type,
			'credit' => $amount,
			'method' => 'ptc', #Value="ptc" is representing PrepareTransferCredit

//			'cagent' => $this->cagent,
//			'method' => 'tc',
//			'loginname' => $playerName,
//			'billno' => $external_transaction_id,
//			'type' => $transaction,
//			'credit' => $amount,
//			'actype' => $this->actype,
//			'password' => $password,
//			'cur' => $this->currency,
		);

		$this->CI->utils->debug_log('>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>trans-params>>> ', $params);

		return $this->callApi(self::API_prepareTransferCredit, $params, $context);
	}

	public function processResultForTransferCredit($params)
	{
		$this->CI->utils->debug_log('####### OG PREPARE TRANSFER ######### ', $params['params']);

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$result = array('response_result_id' => $responseResultId);
		$result['billno'] = $this->getVariableFromContext($params, 'billno');
		$success=false;
		//for timeout or other error
		if(parent::processGuessSuccess($params, $success, $result)){
			return [$success, $result];
		}

        $resultXml = (array)$this->getResultXmlFromParams($params);

		$success = $this->processResultXml($responseResultId, $resultXml,
			array('key_error', 'network_error', 'account_not_exist', 'error', 'no_enough_credit', 'account_no_exist', '10', '0'), true);

		$result['error_code']=isset($resultXml[0]) ? $resultXml[0]:null;

		return array($success, $result);
	}

	public function transferCreditConfirm($playerId, $gameUsername, $password, $amount, $external_transaction_id, $transaction_type)
	{
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForTransferCreditConfirm',
			'playerId' => $playerId,
			'gameUsername' => $gameUsername,
			//'external_transaction_id'=>$external_transaction_id,
			//mock testing
			// 'is_timeout_mock' => $this->getSystemInfo('is_timeout_mock', false),
			//for this api
			// 'enabled_guess_success_for_curl_errno_on_this_api' => $this->enabled_guess_success_for_curl_errno_on_this_api,
		);

		if($transaction_type==self::TRANSFER_IN){
			$context['enabled_guess_success_for_curl_errno_on_this_api']=$this->enabled_guess_success_for_curl_errno_on_this_api;
			// $context['is_timeout_mock']=$this->getSystemInfo('is_timeout_mock', false);
		}

		$params = array(
			'agent'	=>	$this->agent,
			'username'=> $gameUsername,
			'password' => $password,
			'billno' => $external_transaction_id,
			'type' => $transaction_type,
			'credit' => $amount,
			'method' => 'ctc', #Value="ptc" is representing PrepareTransferCredit

		);
		return $this->callApi(self::API_transferCreditConfirm, $params, $context);
	}

	public function processResultForTransferCreditConfirm($params)
	{

		$this->CI->utils->debug_log('####### OG processResultForTransferCreditConfirm ######### ', $params['params']);

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$result = array('response_result_id' => $responseResultId);
		$external_transaction_id=$this->getVariableFromContext($params, 'external_transaction_id');
		//$result['external_transaction_id'] = $external_transaction_id;
		$success=false;
		//for timeout or other error
		if(parent::processGuessSuccess($params, $success, $result)){
			return [$success, $result];
		}

		$resultXml = (array) $this->getResultXmlFromParams($params);

		$this->CI->utils->debug_log('=========resultXml', $resultXml);

		$success = $this->processResultXml($responseResultId, $resultXml,
			array('key_error', 'network_error', 'account_not_exist', 'error', 'account_no_exist', '10', '0'), true);

		$result = array(
				'response_result_id' => $responseResultId,
				'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
				'reason_id'=>self::REASON_UNKNOWN
		);
		if(!$success) {
			$error_code = @$result['error_code'];
			switch($error_code) {
				case 'account_no_exist' :
					$result['reason_id'] = self::REASON_NOT_FOUND_PLAYER;
					break;
				case 'no_enough_credit' :
					$result['reason_id'] = self::REASON_NO_ENOUGH_BALANCE;
					break;
				case '0' :
					$result['reason_id'] = self::REASON_FAILED_FROM_API;
					break;
				case '10' :
					$result['reason_id'] = self::REASON_AGENT_NOT_EXISTED;
					break;
			}
			$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_DECLINED;
		}

		$result['error_code']=isset($resultXml[0]) ? $resultXml[0]: null;

		return array($success, $result);
	}

//	public function prepareTransferCredit($userName, $amount,$type, $transfer_secure_id=null){
//
// 		$password = $this->getPasswordString($userName);
//		$playerName = $this->getGameUsernameByPlayerUsername($userName);
//		$billNo = 'TF'.date("ymdHis").rand(1,99);
//
//		$context = array(
//			'callback_obj' => $this,
//			'callback_method' => 'processResultForPrepareTransferCredit',
//			'playerName' => $playerName,
//			'sbe_playerName' => $userName,
//			'amount' => $amount,
//			'type' => $type
//		);
//
//		$params = array(
//			'agent'	=>	$this->agent,
//			'username'=> $playerName,
//			'password' => $password,
//			'billno' => $billNo,
//			'type' => $type,
//			'credit' => $amount,
//			'method' => 'ptc' #Value="ptc" is representing PrepareTransferCredit
//		);
//
//		return $this->callApi(self::API_prepareTransferCredit, $params, $context);
//
//	}
//
//	public function processResultForPrepareTransferCredit($params) {
//
//		$playerName = $this->getVariableFromContext($params, 'playerName');
//		$sbe_playerName = $this->getVariableFromContext($params, 'sbe_playerName');
//		$amount = $this->getVariableFromContext($params, 'amount');
//		$type = $this->getVariableFromContext($params, 'type');
//		$responseResultId = $this->getResponseResultIdFromParams($params);
//		$resultXml = $this->getResultXmlFromParams($params);
//
//		$success = $this->processResultBoolean($responseResultId, $resultXml,$playerName);
//		$result = array('response_result_id' => $responseResultId);
//		if($success){
//			$result = $this->transferCreditConfirm($sbe_playerName,$playerName,$params['params']);
//		}
//
//		return array($success, $result);
//
//	}
//
//	public function transferCreditConfirm($sbe_playerName,$playerName, $params){
//
//		$context = array(
//			'callback_obj' => $this,
//			'callback_method' => 'processResultTransferCreditConfirm',
//			'playerName' => $playerName,
//			'sbe_playerName' => $sbe_playerName,
//		);
//
//		$params['method'] = "ctc"; # representing ConfirmTransferCredit
//		return $this->callApi(self::API_transferCreditConfirm, $params, $context);
//
//	}
//
//	public function processResultTransferCreditConfirm($params){
//
//		$playerName = $this->getVariableFromContext($params, 'playerName');
//		$sbe_playerName = $this->getVariableFromContext($params, 'sbe_playerName');
//		$type = $params['params']["type"];
//		$amount = $params['params']["credit"];
//		$billno = $params['params']["billno"];
//		$resultXml = $this->getResultXmlFromParams($params);
//		$responseResultId = $this->getResponseResultIdFromParams($params);
//		$success = $this->processResultBoolean($responseResultId, $resultXml,$playerName);
//
//        $result['external_transaction_id']=$billno;
//
//		if ($success) {
//
//            $playerId = $this->getPlayerIdInGameProviderAuth($playerName);
//
//            if ($playerId) {
//                $playerBalance = $this->queryPlayerBalance($sbe_playerName);
//                $afterBalance = 0;
//
//                if($type == self::TRANSFER_IN){ // Deposit
//                	if ($playerBalance && $playerBalance['success']) {
//	                    $afterBalance = $playerBalance['balance'];
//	                } else {
//	                    //IF GET PLAYER BALANCE FAILED
//	                    $rlt = $this->CI->wallet_model->getSubWalletBy($playerId, $this->getPlatformCode());
//	                    $afterBalance = $rlt->totalBalanceAmount;
//	                    $this->CI->utils->debug_log('============= OG_API AFTER BALANCE FROM WALLET '.$type.' ######### ', $afterBalance);
//	                }
//	                // $responseResultId = $result['response_result_id'];
//	                // Deposit
//	                $this->insertTransactionToGameLogs($playerId, $playerName, $afterBalance, $amount, $responseResultId,
//	                    $this->transTypeMainWalletToSubWallet());
//                }else{ // Withdraw
//                	if ($playerBalance && $playerBalance['success']) {
//	                    $afterBalance = $playerBalance['balance'];
//	                    $this->CI->utils->debug_log('============= OG_API AFTER BALANCE FROM API '.$type.' ######### ', $afterBalance);
//	                } else {
//	                    //IF GET PLAYER BALANCE FAILED
//	                    $rlt = $this->CI->wallet_model->getSubWalletBy($playerId, $this->getPlatformCode());
//	                    $afterBalance = $rlt->totalBalanceAmount;
//	                    $this->CI->utils->debug_log('============= OG_API AFTER BALANCE FROM WALLET '.$type.' ######### ', $afterBalance);
//	                }
//	                // $responseResultId = $result['response_result_id'];
//	                // Withdraw
//	                $this->insertTransactionToGameLogs($playerId, $playerName, $afterBalance, $amount, $responseResultId,
//	                    $this->transTypeSubWalletToMainWallet());
//                }
//
//            } else {
//                $this->CI->utils->debug_log('error', '=============== cannot get player id from '.$playerName.' getPlayerIdInGameProviderAuth');
//            }
//        }
//
//        return array($success, $result);
//
//	}

	public function getLauncherLanguage($lang){
		$this->CI->load->library("language_function");
		switch ($lang) {
			case 'zh':
			case LANGUAGE_FUNCTION::INT_LANG_CHINESE:
				$lang = 'zh';
				break;
			case 'kr':
			case LANGUAGE_FUNCTION::INT_LANG_KOREAN:
				$lang = 'kr';
				break;
			case 'th':
			case LANGUAGE_FUNCTION::INT_LANG_THAI:
				$lang = 'th';
				break;
			default:
				$lang = 'en';
				break;
		}
		return $lang;
	}

	public function queryForwardGame($playerName,$extra=null) {

		$password = $this->getPasswordString($playerName);
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		// $gametype = $extra["is_mobile"]?21:1; // 1 web, 21 mobile

		$params = array(
			'agent'	=>	$this->agent,
			'username' => $playerName,
			'password' => $password,
			'domain' => $this->client_domain,
			'gametype' => 1,//$gametype,
			'gamekind' => 0,
			'iframe' => self::IFRAME['https'], // use https as default
			'platformname' => $this->platformname, // 'OG',
			'lang' => $this->getLauncherLanguage($extra['language']),
			'method' => 'tg', #tg representing TransferGame
		);

		$params['agent'] = $this->agent;

		$params_string = '';
		$ctr =1;
		foreach ($params as $key => $value) {
			$params_string .=$key.'='.$value;
			if(count($params) != $ctr){
				$params_string .= "$";
			}
			$ctr++;
		}

		$newParams = base64_encode($params_string);
		$key = MD5($newParams.$this->UserKey);
		$url = $this->api_url.'?params='.$newParams.'&key='.$key;

		$success=true;

		return ['url'=>$url, 'success'=>$success] ;


		// $context = array(
		// 	'callback_obj' => $this,
		// 	'callback_method' => 'processResultForQueryForwardGame',
		// 	'language' => $extra['language'],
		// 	'playerName' => $playerName
		// );
		// $params = array(
		// 	'agent'	=>	$this->agent,
		// 	'username' => $playerName,
		// 	'password' => $password,
		// 	'domain' => $this->client_domain,
		// 	'gametype' => 1,//$gametype,
		// 	'gamekind' => 0,
		// 	'iframe' => self::IFRAME['https'], // use https as default
		// 	'platformname' => 'OG',
		// 	'lang' => $this->getLauncherLanguage($extra['language']),
		// 	'method' => 'tg', #tg representing TransferGame
		// );

		// return $this->callApi(self::API_queryForwardGame, $params, $context);

	}

	// public function processResultForQueryForwardGame($params){

	// 	$playerName = $this->getVariableFromContext($params, 'playerName');
	// 	$language = $this->getVariableFromContext($params, 'language');
	// 	$responseResultId = $this->getResponseResultIdFromParams($params);
	// 	$resultXml = (array)$this->getResultXmlFromParams($params);
	// 	$url = trim(preg_replace('#.*href="([^"]*)".*#s', '$1', $params['resultText']));
 //        // $url = htmlspecialchars_decode($url);
	// 	$success = true;
 //        // $headerArr = array_map(
 //        //     function($x) { return array_map("trim", explode(":", $x, 2)); },
 //        //     array_filter(
 //        //         array_map("trim", explode("\n", $params['extra']))
 //        //     )
 //        // );

 //        // $this->CI->utils->debug_log('headerArr', $headerArr);
 //        // $url = null;
 //        // if(!empty($headerArr)){
 //        //     $found302=strpos($headerArr[0][0], '302');
 //        //     $realUrl=null;
 //        //     foreach ($headerArr as $headerRow) {
 //        //         if($headerRow[0]=='Location'){
 //        //             $realUrl=$headerRow[1];
 //        //         }
 //        //     }
 //        //     $this->CI->utils->debug_log('found302', $found302, 'realUrl', $realUrl);
 //        //     if($found302 && !empty($realUrl)){
 //        //         $url=$realUrl;
 //        //     }
 //        // }

 //        $result = [ "url" => $url ];
	// 	return array($success, $result);

	// }

	public function getVendorId(){
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForgetVendorId',
			'startDate' => $startDate,
			'endDate' => $endDate
		);

		$params = array(
			'agent'	=>	$this->agent,
			'ordernumber' => 1,
			'method' => 'gvi',
			'report' => true
		);

		return $this->callApi(self::API_getVendorId, $params, $context);
	}

	public function processResultForgetVendorId($params){
		$resultXml = $this->getResultXmlFromParams($params);
		return array($success, $result);
	}


	private $currentGameLogsVendorId=0;
	private $currentGameLogsAddTime = '';
	private $currentResponseStatus = false;
	private $errorCount = 0;
	private $loopCount = 0;

	public function syncOriginalGameLogs($token = false) {

		// $startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		// $endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

		// $startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
		// $endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));
		// $startDate->modify($this->getDatetimeAdjust());
		// //observer the date format
		// $startDate = $startDate->format('Y-m-d H:i:s');
		// $endDate = $endDate->format('Y-m-d H:i:s');

		// $startDate = $startDate->format('Y-m-d');
		// $endDate = $endDate->format('Y-m-d');


		// do {

		// 	$this->loopCount++;

		// 	try
		// 	{

		// 		$last_sync_id = $this->CI->external_system->getLastSyncId($this->getPlatformCode());

		// 		#Get the last loop transaction id
		// 		if($this->currentGameLogsVendorId){

		// 			$StartVendorId = $this->currentGameLogsVendorId;

		// 			#if not get the last  sync id
		// 		}elseif($last_sync_id){

		// 			$StartVendorId =  $last_sync_id;

		// 			#else get the start settings
		// 		}else{
		// 			$StartVendorId = $this->settingsStartVendorId;

		// 		}

		// 		$context = array(
		// 			'callback_obj' => $this,
		// 			'callback_method' => 'processResultForSyncGameRecords',
		// 			// 'startDate' => $startDate,
		// 			// 'endDate' => $endDate
		// 		);

		// 		$params = array(
		// 			'agent'	=>	$this->agent,
		// 			'vendorid' => $StartVendorId,
		// 			'method' => 'gbrbv',
		// 			'report' => true # report boolean static
		// 		);

		// 		$result =  $this->callApi(self::API_syncGameRecords, $params, $context);
		// 		$this->currentResponseStatus = $result['success'];
		// 		# rest a little bit
		// 		sleep(1);
		// 		if(!$this->currentResponseStatus){
		// 			$this->errorCount++;
		// 			break;
		// 		}

		// 	} catch (Exception $e) {

		// 		$this->CI->utils->debug_log('OG API:syncOriginalGameLogs $StartVendorId:  '.@$this->currentGameLogsVendorId, 'loopCount', @$this->loopCount, 'errorCount', @$this->errorCount );
		// 		break;
		// 	}
		// }while($this->currentResponseStatus);
		$ignore_public_sync = $this->getValueFromSyncInfo($token, 'ignore_public_sync');

		if ($ignore_public_sync == true) {
			//ignore public sync
			$this->CI->utils->debug_log('ignore public sync');
			return array('success' => true);
		}
		$attempt = 0;
		$rows_count = 0;
		do {
			$last_sync_id = $this->CI->external_system->getLastSyncId($this->getPlatformCode());
	    	$vendor_id = (!empty($last_sync_id)) ? $last_sync_id : $this->settingsStartVendorId;
	    	$context = array(
				'callback_obj' => $this,
				'callback_method' => 'processResultForSyncGameRecords',
			);

			$params = array(
				'agent'	=>	$this->agent,
				'vendorid' => $vendor_id,
				'method' => 'gbrbv',
				'report' => true # report boolean static
			);

			$result =  $this->callApi(self::API_syncGameRecords, $params, $context);

			$rows_count += $result['data_count'];			

			sleep(1);
			$attempt++;
			$this->CI->utils->debug_log('OG API:syncOriginalGameLogs attempt: ',$attempt);
			$this->CI->utils->debug_log('OG API:syncOriginalGameLogs orginal data: ',$result['original_data_count']);
		} while(($attempt < $this->max_call_attempt) && ($result['original_data_count'] >= $this->max_data_set));

		return array('success' => true, 'rows_count' => $rows_count);
	}

	private function ogXmlparser($string){
	    $parser = xml_parser_create();
	    xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
	    xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE,   1);
	    xml_parse_into_struct($parser, $string, $vals, $index);
	    xml_parser_free($parser);
	    if(isset($vals[0]['value']) && ($vals[0]['value'] == 'No_Data')){
	    	return [];
	    }
	    $i = 0;
	    return $this->xml_get_children($vals, $i);
	}

	private function xml_get_children($vals, &$i) {
	    $children = array();
	    if (isset($vals[$i]['value'])) $children[] = $vals[$i]['value'];
	    while (++$i < count($vals)) {
	        switch ($vals[$i]['type']) {
		        case 'cdata':
		            $children[] = $vals[$i]['value'];
		            break;

		        case 'complete':
		            $children[$vals[$i]['attributes']['name']] = $vals[$i]['value'];
		            break;

		        case 'open':
		        	$children[] = $this->xml_get_children($vals, $i);
		            break;

		        case 'close':
		            return $children;
	        }
	    }
	}

	public function processResultForSyncGameRecords($params) {
		$this->CI->load->model(array('og_game_logs', 'player_model'));
		$resultXml =  $this->ogXmlparser($params['resultText']);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$this->CI->utils->debug_log('[OG api gamelogs responseId]', @$responseResultId);
		$manual_sync = $this->getVariableFromContext($params, 'manual_sync');

		$checkXml = $this->getResultXmlFromParams($params);
		$success = isset($checkXml->Data) ? true : (($checkXml == self::ERROR_AGENT_NO_EXIST) ? false : true);
		$this->CI->utils->debug_log('[OG api gamelogs success]', @$success);

		$result = array();
		if ($success) {
			$gameRecords = $resultXml;

			$dataCount = 0;
			if(!empty($gameRecords)){
				
				$gameRecords = $gameRecords === array_values($gameRecords) ? $gameRecords : array($gameRecords);

				$availableRows = $this->CI->og_game_logs->getAvailableRows($gameRecords);
				$maxVendorId = max(array_column($gameRecords, 'VendorId'));
				$this->CI->utils->debug_log('[OG api gamelogs availableRows]', @$availableRows,'gameRecordscountd', @count($gameRecords));
				if (!empty($availableRows)) {
					foreach ($availableRows as $record) {
						$insertRecord = array();
						$lastVendorId = isset($record['VendorId']) ? $record['VendorId'] : NULL;

						//Data from OG API
						$insertRecord['ProductID'] = isset($record['ProductID']) ? $record['ProductID'] : NULL;
						$insertRecord['UserName'] = isset($record['UserName']) ? $record['UserName'] : NULL;
						$insertRecord['GameRecordID'] = isset($record['GameRecordID']) ? $record['GameRecordID'] : NULL;
						$insertRecord['OrderNumber'] = isset($record['OrderNumber']) ? $record['OrderNumber'] : NULL;
						$insertRecord['TableID'] = isset($record['TableID']) ? $record['TableID'] : NULL;
						$insertRecord['Stage'] = isset($record['Stage']) ? $record['Stage'] : NULL;
						$insertRecord['Inning'] = isset($record['Inning']) ? $record['Inning'] : NULL;
						$insertRecord['GameNameID'] = isset($record['GameNameID']) ? $record['GameNameID'] : NULL;
						$insertRecord['GameBettingKind'] = isset($record['GameBettingKind']) ? $record['GameBettingKind'] : NULL;
						$insertRecord['GameBettingContent'] = isset($record['GameBettingContent']) ? $record['GameBettingContent'] : NULL;
						$insertRecord['ResultType'] = isset($record['ResultType']) ? $record['ResultType'] : NULL;
						$insertRecord['BettingAmount'] = isset($record['BettingAmount']) ? $record['BettingAmount'] : NULL;
						$insertRecord['CompensateRate'] = isset($record['CompensateRate']) ? $record['CompensateRate'] : NULL;
						$insertRecord['WinLoseAmount'] = isset($record['WinLoseAmount']) ? $record['WinLoseAmount'] : NULL;
						$insertRecord['Balance'] = isset($record['Balance']) ? $record['Balance'] : NULL;
						$insertRecord['AddTime'] = isset($record['AddTime']) ? $this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($record['AddTime']))) : NULL;
						$insertRecord['PlatformID'] = isset($record['PlatformID']) ? $record['PlatformID'] : NULL;
						$insertRecord['VendorId'] = $lastVendorId;
						$insertRecord['ValidAmount'] = isset($record['ValidAmount']) ? $record['ValidAmount'] : NULL;
						$insertRecord['GameKind'] = isset($record['GameKind']) ? $record['GameKind'] : NULL;

						//extra info from SBE
						$insertRecord['external_uniqueid'] = $insertRecord['ProductID']; //add external_uniueid for og purposes
						$insertRecord['response_result_id'] = $responseResultId;
						$this->CI->utils->debug_log('[OG api insertRecord]', @$insertRecord,$lastVendorId );
						//insert data to OG gamelogs table database
						$this->CI->og_game_logs->insertGameLogs($insertRecord);
						// $this->CI->external_system->setLastSyncId($this->getPlatformCode(), $lastVendorId);

						// $this->CI->utils->debug_log('OG API lastVendorId '.@$lastVendorId);
						$dataCount++;
					}
				}

				if(!empty($manual_sync)) {
					$lastGameResult = end($gameRecords);
					$this->currentGameLogsVendorId = @$lastGameResult['VendorId'];
					$this->currentGameLogsAddTime = isset($lastGameResult['AddTime']) ? $this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($lastGameResult['AddTime']))) : NULL;
				} else {
					$this->CI->external_system->setLastSyncId($this->getPlatformCode(), $maxVendorId);
				}
			}
			$result['data_count'] = $dataCount;
			$result['original_data_count'] = count($gameRecords);

			$result['current_vendor_id'] = $this->currentGameLogsVendorId;
			$result['current_add_time'] = $this->currentGameLogsAddTime;
		}
		return array($success, $result);
	}

	public function syncMergeToGameLogs($token) {

		$this->CI->load->model(array('game_logs', 'player_model', 'og_game_logs'));

		$dateTimeFrom = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$dateTimeFrom->modify($this->getDatetimeAdjust());
		$dateTimeTo = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

		//observer the date format
		$startDate = $dateTimeFrom->format('Y-m-d H:i:s');
		$endDate = $dateTimeTo->format('Y-m-d H:i:s');

		$result = $this->CI->og_game_logs->getGameLogStatistics($startDate, $endDate);

		$cnt = 0;
		if ($result) {

			$unknownGame = $this->getUnknownGame();
			foreach ($result as $og_data) {

				$note = $this->convertGamedetatilsToJson($og_data->GameBettingContent);
				$realbet = $og_data->BetAmount;
				$GameRecordId = $og_data->GameRecordId;

				if (!$og_data->PlayerId) {
					continue;
				}

				$cnt++;

				$game_description_id = $og_data->game_description_id;
				$game_type_id = $og_data->game_type_id;

				if (empty($game_description_id)) {
                    list($game_description_id, $game_type_id) = $this->getGameDescriptionInfo($og_data, $unknownGame);
				}

				$extra = array('table' => $GameRecordId, 'trans_amount' => $realbet, 'bet_details' => $note);

				$this->syncGameLogs(
					$game_type_id,
					$game_description_id,
					$og_data->game_code,
					$og_data->game_type,
					$og_data->game,
					$og_data->PlayerId,
					$og_data->UserName,
					$og_data->ValidAmount,
					$og_data->result_amount,
					null, # win_amount
					null, # loss_amount
					null, # after_balance
					0, # has_both_side
					$og_data->external_uniqueid,
					$og_data->game_date, //start
					$og_data->game_date, //end
					$og_data->response_result_id,
					null,
					$extra
				);

			}
		}

		$this->CI->utils->debug_log('syncMergeToGameLogs monitor', 'count', $cnt);
		return array('success' => true);
	}


	public function convertGamedetatilsToJson($gameDetails = null){
		$place_of_bet = 0;
		$bet_amount = 1;
		$bet_result = 2;
		$og_game_place_bet = [
			"101" => 'player',
			"102" => 'banker',
			"103" => 'tie',
			"764" => '4 nian2',
			"765" => '4 nian3',
			"766" => 'odd',
			"767" => 'even',
			"768" => '1,2 si tong',
			"769" => '1,2 san tong',
			"770" => '1,3 si tong',
			"771" => '1,3 er tong',
			"772" => '1,4 san tong',
			"773" => '1,4 er tong',
			"774" => '2,3 si tong',
			"775" => '2,3 yi tong',
			"776" => '2,4 san tong',
			"777" => '2,4 yi tong',
			"778" => '3,4 er tong',
			"779" => '3,4 yi tong',
			"780" => 'San men(3,2,1)',
			"781" => 'San men(2,1,4)',
			"782" => 'San men(1,4,3)',
			"783" => 'San men(4,3,2)',
			"401" => 'Point 4',
			"402" => 'Point 5',
			"403" => 'Point 6',
			"404" => 'Point 7',
			"405" => 'Point 8',
			"406" => 'Point 9',
			"407" => 'Point 10',

		];

		$data = explode("^", $gameDetails);
		$data = [
			"place_of_bet" => $data[$place_of_bet],
			"bet_amount" => $data[$bet_amount],
			"bet_result" => $data[$bet_result],
			"bet_details" => lang("Bet amount") . ": " . $data[$bet_amount] . ", " . lang(" Bet result") . ": " .  $data[$bet_result],
		];

		foreach ($og_game_place_bet as $key => $value) {
			if ($key == $data['place_of_bet']) {
				$data['place_of_bet'] = $value;
				$data['bet_details'] = lang("Place of bet") . ": " . $value . ", " . $data['bet_details'];
			}
		}

		return json_encode($data);

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

	/**
	 * overview : get game time to server time
	 *
	 * @return string
	 */
	// public function getGameTimeToServerTime() {
	// 	//return '+8 hours';
	// }

	/**
	 * overview : get server time to game time
	 *
	 * @return string
	 */
	// public function getServerTimeToGameTime() {
	// 	//return '-8 hours';
	// }

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

	//======utils===============================================================

	public function getAttrValueFromXml($resultXml, $attrName)
	{
		$info = null;
		if (!empty($resultXml)) {
			$result = $resultXml->xpath('/result');
			if (isset($result[0])) {
				$attr = $result[0]->attributes();
				if (!empty($attr)) {
					foreach ($attr as $key => $value) {
						if ($key == $attrName) {
							$info = ''.$value;
						}
						$this->CI->utils->debug_log('key', $key, 'value', ''.$value);
					}
				} else {
					$this->CI->utils->debug_log('empty attr');
				}
			} else {
				$this->CI->utils->debug_log('empty /result');
			}
		} else {
			$this->CI->utils->debug_log('empty xml');
		}

		return $info;
	}

    private function getGameDescriptionInfo($row, $unknownGame) {
        $game_description_id = null;
        $game_name = $external_game_id = $row->game_code;
        $extra = array('game_code' => $external_game_id);

        $game_type_id = $unknownGame->game_type_id ? $unknownGame->game_type_id : null;
        $game_type = $unknownGame->game_name ? $unknownGame->game_name : self::TAG_CODE_UNKNOWN_GAME;

        return $this->processUnknownGame(
            $game_description_id, $game_type_id,
            $game_name, $game_type, $external_game_id, $extra,
            $unknownGame);
    }


	/** sample
		Array (
			[success] => 1
			[data_count] => 300
			[original_data_count] => 300
			[current_vendor_id] => 1415372963238
			[current_add_time] => 2018-12-30 15:21:13
			[response_result_id] => 26204
		)
	 */
	public function syncGameByDateTime($startDate=null, $endDate=null) {
		$result = $this->getVendorIdByDateTime($startDate);

		# 2018-12-30 15:26:24
		if($result) {
			$this->vendor_id = @$result['VendorId'];

			$attempt = 0;
			do {
				$continue = true;

				$context = array(
					'callback_obj' => $this,
					'callback_method' => 'processResultForSyncGameRecords',
					'manual_sync' => true
				);

				$params = array(
					'agent'	=>	$this->agent,
					'vendorid' => $this->vendor_id,
					'method' => 'gbrbv',
					'report' => true # report boolean static
				);
				$result =  $this->callApi(self::API_syncGameRecords, $params, $context);
				$this->vendor_id = @$result['current_vendor_id'];
				$this->add_time = @$result['current_add_time'];

				if(empty($this->vendor_id)) {
					$continue = false;
				}

				sleep(1);
				$attempt++;
			} while($this->add_time <= $endDate && $continue);  # ($attempt < $this->max_call_attempt) && ($result['original_data_count'] >= $this->max_data_set)
		} else {
			$this->CI->utils->debug_log('No fetch data for this date ====> ', $startDate);
		}
		return $result;
	}

	// get closest vendor id by date time
	// AddTime < $dateTime
	// note we should based datetime by vendor id. add function to get dateTime by vendor ID
	public function getVendorIdByDateTime($dateTime) {
		// $dateTime = '2018-12-30 17:30:56';
		$this->CI->db->select('AddTime, VendorId');
		$this->CI->db->where('AddTime <', $dateTime);
		$this->CI->db->from('og_game_logs');
		$this->CI->db->order_by("AddTime", "DESC");
		$query = $this->CI->db->get();
		$result =  $query->first_row('array');

		if($result) {
			$this->CI->utils->debug_log('Add time ====> ', $result['AddTime'], ' Vendor ID =====> ', $result['VendorId']);
		}
		return $result;
	}
}