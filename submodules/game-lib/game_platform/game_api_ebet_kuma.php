<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
set_include_path(dirname(__FILE__) . '/../unencrypt/phpseclib');
include_once 'Crypt/RSA.php';

class Game_api_ebet_kuma extends Abstract_game_api {

	private $api_url;
	private $channelId;
	private $islive;
	private $tag;
	private $public_key;
	private $private_key;
	private $thirdParty;
	private $currency;
	private $CTGent;

	const TRANSFER_IN = '100';
	const TRANSFER_OUT = '200';
	const API_prepareTransferCredit = 'prepareTransferCredit';
    const API_transferCreditConfirm = 'transferCreditConfirm';

	public function __construct() {
        parent::__construct();
		$this->api_url        = $this->getSystemInfo('url');
        $this->game_url       = $this->getSystemInfo('game_url');
		$this->channelId      = $this->getSystemInfo('channelId');
		$this->islive         = $this->getSystemInfo('live');
		$this->thirdParty     = $this->getSystemInfo('thirdParty');
		$this->tag            = $this->getSystemInfo('tag');
		$this->public_key     = $this->getSystemInfo('public_key');
		$this->private_key    = $this->getSystemInfo('private_key');
		$this->currency       = $this->getSystemInfo('currency','CNY');
		$this->CTGent=$this->getSystemInfo('CTGent','CS0020');

		# init RSA
		$this->rsa = new Crypt_RSA();
		$this->rsa->setSignatureMode(CRYPT_RSA_SIGNATURE_PKCS1);
		$this->rsa->setHash('md5');

	}

    const SUCCESS_CODE = 200;

	public function getPlatformCode() {
		return EBET_KUMA_API;
	}

	public function generateUrl($apiName, $params) {
        $url = $this->api_url;
        return $url;
	}

	public function getHttpHeaders($params){
		return array("Content-Type" => "application/json");
	}

	protected function customHttpCall($ch, $params) {
		$action = $params["method_action"];
		unset($params["method_action"]); //unset action not need on params

		$postParams = array(
				"channelId"         => $this->channelId,
				"thirdParty"        => $this->thirdParty,
				"tag"               => $this->tag,
				"action" => array(
						"command"    => $action,
						"parameters" => $params
				),
				"live"              => $this->islive,
				"timestamp"         => time()
		);
		$postParams["signature"] = $this->encrypt($this->channelId.$this->thirdParty.$this->tag.$postParams["timestamp"]);
		$this->CI->utils->debug_log('EBET KUMA PARAMS POST ',json_encode($postParams,true));

		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postParams,true));
	}

	protected function resultTextToArray($resultTxt){

		$resultArr = array();
		foreach(explode(",",$resultTxt) as $exVal){
			$val = explode("=",$exVal);
			$resultArr[$val[0]] = isset($val[1])?$val[1]:'';
		}
		return $resultArr;

	}

	public function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null) {
		return array(false, null);
	}

	public function processResultBoolean($responseResultId, $resultArr, $playerName = null) {
        if ($resultArr['status'] == self::SUCCESS_CODE) {
			$result = $this->resultTextToArray($resultArr['result']);
			if(isset($resultArr['isgamelogs'])&&$resultArr['isgamelogs']){
				$success = true;
			}else{
				if($result['Status']==1){
	           		$success = true;
				}else{
	        		$success = false;
				}
			}
        }else{
        	$success = false;
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->error_log('GAME_KUMA_API got error', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
		}

		return $success;
	}

	public function isPlayerExist($userName) {
		$playerName = $this->getGameUsernameByPlayerUsername($userName);

        $context = array(
			'callback_obj'    => $this,
			'callback_method' => 'processResultForIsPlayerExists', 
			'playerName'	  => $playerName
		);

        $params = array(
            "Loginname"     => empty($playerName)?$userName:$playerName,
            "Cur"         	=> $this->currency,
            "method_action" => "getbalance"
        );

        return $this->callApi(self::API_isPlayerExist, $params, $context);
	}

	public function processResultForIsPlayerExists($params) {

		$playerName = $this->getVariableFromContext($params, 'playerName');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$userName = $this->getVariableFromContext($params, 'userName');
		$success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);
		$result = array();

		if ($success) {
			$resultArr = $this->resultTextToArray($resultArr["result"]);
			if(isset($resultArr['Status'])&&$resultArr['Status']==1){
				$result['exists'] = true;
			}
		}else{
			$result['exists'] = false;
		}

		return array($success, $result);
	}

	public function login($playerName, $password = null) {
		$userName = $this->getGameUsernameByPlayerUsername($playerName);
		$token = date("YmdHis").rand(1,99);

		$context = array(
			'callback_obj'    => $this,
			'callback_method' => 'processResultForLogin',
			'userName'        => $userName,
			'sbe_userName'    => $playerName,
			'token'    => $token
		);

		$params = array(
			'Loginname' 	=> $userName,
			'NickName'      => $userName,
			'SecureToken'   => $token,
			'Cur'      		=> $this->currency,
			'method_action' => 'checkorcreategameaccount'
		);
		return $this->callApi(self::API_login, $params, $context);
	}

	public function processResultForLogin($params){
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);	
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$resultArray['token'] = $this->getVariableFromContext($params, 'token');
		$success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);

		return array($success, $resultArray);
	}

	public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
		parent::createPlayer($playerName, $playerId, $password, $email, $extra);
		$userName = $this->getGameUsernameByPlayerUsername($playerName);
		$token = date("YmdHis").rand(1,99);

		$context = array(
			'callback_obj'    => $this,
			'callback_method' => 'processResultForCreatePlayer',
			'userName'        => $userName,
			'sbe_userName'    => $playerName
		);

		$params = array(
			'Loginname' 	=> $userName,
			'NickName'      => $userName,
			'SecureToken'   => $token,
			'Cur'      		=> $this->currency,
			'method_action' => 'checkorcreategameaccount'
		);

		return $this->callApi(self::API_createPlayer, $params, $context);
	}

	public function processResultForCreatePlayer($params){
		$userName = $this->getVariableFromContext($params, 'userName');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJsonArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultJsonArr, $userName);

		return array($success, $resultJsonArr);

	}

	public function queryPlayerBalance($userName) {
		$playerName = $this->getGameUsernameByPlayerUsername($userName);

        $context = array(
			'callback_obj'    => $this,
			'callback_method' => 'processResultForQueryPlayerBalance', 
			'playerName'	  => $playerName
		);

        $params = array(
            "Loginname"     => empty($playerName)?$userName:$playerName,
            "Cur"         	=> $this->currency,
            "method_action" => "getbalance"
        );

        return $this->callApi(self::API_queryPlayerBalance, $params, $context);
	}

	public function processResultForQueryPlayerBalance($params){
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);		
		$success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);
		$result = array();
		$this->CI->utils->debug_log('query balance result',$resultArr);
		if ($success) {
			$resultArr = $this->resultTextToArray($resultArr["result"]);
			$result['balance'] = @floatval($resultArr['Data']);

			if ($playerId = $this->getPlayerIdInGameProviderAuth($playerName)) {
				$this->CI->utils->debug_log('query balance playerId', $playerId, 'playerName', $playerName, 'balance', $result['balance']);
			} else {
				$this->CI->utils->debug_log('cannot get player id from ' . $playerName . ' getPlayerIdInGameProviderAuth');
			}

		} 

		return array($success, $result);
	}

	public function depositToGame($userName, $amount, $transfer_secure_id=null){

		$type = self::TRANSFER_IN;
		return $this->prepareTransferCredit($userName, $amount, $type, $transfer_secure_id);

	}

	public function withdrawFromGame($userName, $amount, $transfer_secure_id=null){

		$type = self::TRANSFER_OUT;
		return $this->prepareTransferCredit($userName, $amount, $type, $transfer_secure_id);

	}
	
	public function prepareTransferCredit($userName, $amount,$type, $transfer_secure_id=null){
		$playerName = $this->getGameUsernameByPlayerUsername($userName);
		$billNo = strtoupper($this->CTGent.$this->channelId.substr(md5(uniqid(mt_rand(),true)),0,8));

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForPrepareTransferCredit',
			'playerName' => $playerName,
			'sbe_playerName' => $userName,
			'amount' => $amount,
			'Type' => $type
		);

		$params = array(
			'Billno' => $billNo,
			'Loginname' => $playerName,
			'Type' => $type,
			'Cur' => $this->currency,
			'Credit' => $amount,
			"method_action" => "preparetransfercredit"
		);

		return $this->callApi(self::API_prepareTransferCredit, $params, $context);
	}

	public function processResultForPrepareTransferCredit($params) {

		$playerName = $this->getVariableFromContext($params, 'playerName');
		$sbe_playerName = $this->getVariableFromContext($params, 'sbe_playerName');
		$amount = $this->getVariableFromContext($params, 'amount');
		$type = $this->getVariableFromContext($params, 'Type');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);
		$result = array(false,array());
		if($success){
			$resultArr = $this->resultTextToArray($resultArr["result"]);
			$TGSno = $resultArr["Data"];
			$result = $this->transferCreditConfirm($sbe_playerName,$playerName,$params['params'],$TGSno);
			$result = array($result["success"],$result);
		}

		return $result;

	}

	public function transferCreditConfirm($sbe_playerName,$playerName, $params, $TGSno){

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultTransferCreditConfirm',
			'playerName' => $playerName,
			'sbe_playerName' => $sbe_playerName,
			'external_transaction_id' => $TGSno,
		);

		$params['method_action'] = "confirmtransfercredit";
		$params['TGSno'] = $TGSno;
		return $this->callApi(self::API_transferCreditConfirm, $params, $context);

	}

	public function processResultTransferCreditConfirm($params){

		$playerName = $this->getVariableFromContext($params, 'playerName');
		$sbe_playerName = $this->getVariableFromContext($params, 'sbe_playerName');
		$type = $params['params']["Type"];
		$amount = $params['params']["Credit"];
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);

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
	                    $this->CI->utils->debug_log('============= EBET_KUMA_API AFTER BALANCE FROM WALLET '.$type.' ######### ', $afterBalance);
	                }
	                // $responseResultId = $result['response_result_id'];
	                // Deposit
	                $this->insertTransactionToGameLogs($playerId, $playerName, $afterBalance, $amount, $responseResultId,
	                    $this->transTypeMainWalletToSubWallet());
                }else{ // Withdraw
                	if ($playerBalance && $playerBalance['success']) {
	                    $afterBalance = $playerBalance['balance'];
	                    $this->CI->utils->debug_log('============= EBET_KUMA_API AFTER BALANCE FROM API '.$type.' ######### ', $afterBalance);
	                } else {
	                    //IF GET PLAYER BALANCE FAILED
	                    $rlt = $this->CI->wallet_model->getSubWalletBy($playerId, $this->getPlatformCode());
	                    $afterBalance = $rlt->totalBalanceAmount;
	                    $this->CI->utils->debug_log('============= EBET_KUMA_API AFTER BALANCE FROM WALLET '.$type.' ######### ', $afterBalance);
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

    public function getLauncherLanguage($lang){
        $this->CI->load->library("language_function");
        switch ($lang) {
            case LANGUAGE_FUNCTION::INT_LANG_CHINESE:
                $lang = 'zh-cn';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_ENGLISH:
                $lang = 'en';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_INDONESIAN:
                $lang = 'en';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_VIETNAMESE:
                $lang = 'en';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_KOREAN:
                $lang = 'en';
                break;
            default:
                $lang = 'en';
                break;
        }
        return $lang;
    }

	public function queryForwardGame($playerName, $extra=null) {
		$returnArr = $this->login($playerName);

		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryForwardGame',
			'playerName' => $playerName
		);

		$params = array(
			'Loginname' => $playerName,
			'Lang' => $this->getLauncherLanguage($extra['lang']),
			'Cur' => $this->currency,
			'SecureToken' => $returnArr['token'],
			'GameId' => $extra['gameId'],
			'method_action' => 'getforwardgameurl'
		);

		return $this->callApi(self::API_queryForwardGame, $params, $context);
    }

	public function processResultForQueryForwardGame($params){
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);

		$result = array();
		if($success){
			$explodedText = explode(',',$resultArr["result"]);
			$url = str_replace("Data=", "", $explodedText[1]);			
			$result = array('url' => $url);
		}

		return array($success, $result);
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

	public function syncOriginalGameLogs($token = false) {
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
		$return = $this->syncEbetSpadeGamelogs($startDate,$endDate,$take,$page);
		if(isset($return['count'])){
			if( $return['count'] == $take ){
				$page++;
				return $this->_continueSync( $startDate, $endDate, $take, $page );
			}
		}
		return $return;
	}


	function syncEbetSpadeGamelogs($startDate,$endDate,$take,$page){

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
                "pageSize" => $take, //page Size default is 5000
				"pageNumber" => $page, // page number
				"method_action" => "getrawbethistory"
		);

		$this->utils->debug_log('=====================> EBETSPADE syncOriginalGameLogs params', $params);

		return $this->callApi(self::API_syncGameRecords, $params, $context);
	}

	function processResultForSyncGameRecords($params) {
		$this->CI->load->model(array('ebetkuma_game_logs'));
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$resultArr['isgamelogs'] = true;
		$success = $this->processResultBoolean($responseResultId, $resultArr);
		$count = 0;
		$this->utils->debug_log('=====================> EBET KUMA syncOriginalGameLogs result', count($resultArr));

        $rarr = json_decode($resultArr['result'],true);
        $gameRecords = isset($rarr["betHistories"])?$rarr["betHistories"]:array();

		if ($success) {

            if (!empty($gameRecords)) {
                $availableRows = $this->CI->ebetkuma_game_logs->getAvailableRows($gameRecords);

                if (!empty($availableRows)) {
                    $gameRecordsPush = array();
                    foreach ($availableRows as $record) {

                        $record['settleTime'] = $this->gameTimeToServerTime(date("Y-m-d H:i:s", ($record['settleTime']/1000)));

                        $recordPush = array();
                        $gameUsername = strtolower($record['account']);
                        $player_id = $this->getPlayerIdInGameProviderAuth($gameUsername);

                        if(!$player_id){
                            $player_id=0; # if not exist player continue still save it
                        }

                        $recordPush['playerId'] = $player_id;
                        $recordPush['username'] = isset($record['account'])?$record['account']:null;
                        $recordPush['billNo'] = isset($record['billNo'])?$record['billNo']:null;
                        $recordPush['gameId'] = isset($record['gameId'])?$record['gameId']:null;
                        $recordPush['betValue'] = isset($record['betValue'])?$record['betValue']:null;
                        $recordPush['netAmount'] = isset($record['netAmount'])?$record['netAmount']:null;
                        $recordPush['settleTime'] = isset($record['settleTime'])?$record['settleTime']:null;
                        $recordPush['agentsCode'] = isset($record['agentsCode'])?$record['agentsCode']:null;
                        $recordPush['account'] = isset($record['account'])?$record['account']:null;
                        $recordPush['ticketStatus'] = isset($record['ticketStatus'])?$record['ticketStatus']:null;
                        $recordPush['thirdParty'] = isset($record['thirdParty'])?$record['thirdParty']:null;
                        $recordPush['tag'] = isset($record['tag'])?$record['tag']:null;

                        //SBE use
                        $recordPush['external_uniqueid']   = $record['billNo']; //add external_uniueid for og purposes
                        $recordPush['response_result_id']  = $responseResultId;

                        $this->CI->ebetkuma_game_logs->insertGameLogs($recordPush);
                        $count++; # add count inserted data
                    }
                }
            }
		}

		return array($success,array('count'=>count($gameRecords)));
	}

	function syncMergeToGameLogs($token) {

		$this->CI->load->model(array('game_logs', 'player_model', 'ebetkuma_game_logs'));

		$dateTimeFrom = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$dateTimeTo = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

		$startDate = new DateTime($this->serverTimeToGameTime($dateTimeFrom->format('Y-m-d H:i:s')));
		$endDate = new DateTime($this->serverTimeToGameTime($dateTimeTo->format('Y-m-d H:i:s')));
		//observer the date format
		$startDate->modify($this->getDatetimeAdjust());

		$startDate=$startDate->format('Y-m-d H:i:s');
		$endDate = $dateTimeTo->format('Y-m-d H:i:s');
		// $this->gameTimeToServerTime
		$this->CI->utils->debug_log('dateTimeFrom', $startDate, 'dateTimeTo', $endDate);


		$rlt = array('success' => true);
		$result = $this->CI->ebetkuma_game_logs->getGameLogStatistics($startDate, $endDate);
		$cnt = 0;

		if ($result) {

			$unknownGame = $this->getUnknownGame();
			foreach ($result as $data) {
				$player_id = $data->playerId;

				if (!$player_id) {
					continue;
				}

				$cnt++;
				$bet_amount = $data->bet_amount;
				$realbet = $data->bet_amount;
				$result_amount = ((float)$data->result_amount - (float)$realbet);

				$game_description_id = $data->game_description_id;
				$game_type_id = $data->game_type_id;

				//should use processGameDesction function
				if (empty($game_description_id)) {
					$game_description_id = $unknownGame->id;
					$game_type_id = $unknownGame->game_type_id;
				}

				$extra = array('trans_amount'=> $realbet,"table"=>$data->billNo);

				$this->syncGameLogs(
						$game_type_id,
						$game_description_id,
						$data->game_code,
						$data->game_type,
						$data->game,
						$data->playerId,
						$data->username,
						$bet_amount,
						$result_amount,
						null, # win_amount
						null, # loss_amount
						null, # after_balance
						0, # has_both_side
						$data->external_uniqueid,
						$data->settleTime, //start
						$data->settleTime, //end
						$data->response_result_id,
						1,
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
}

/*end of file*/