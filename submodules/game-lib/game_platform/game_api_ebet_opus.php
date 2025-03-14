<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
set_include_path(dirname(__FILE__) . '/../unencrypt/phpseclib');
include_once 'Crypt/RSA.php';
 /*********
 API DOCS: FROM EBET
 API VER: Unkown
 Live URL : http://mainapi.ebet2017.com:8070/GlobalSystemAPI/api/request

 sample extra info 
 {
    "channelId": "29",
    "live": "1",
    "game_url": "http://live.opus-office.space:3001/",
    "thirdParty": "opus",
    "tag": "opus_sjy",
    "public_key": "MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCAOhQyLvLtsIedhkr892M2B5wt3iX/PH3yjs3oOICo4sQP2chL3g+Bt6hJFPX3VXbcrnz2vXPDJ3amaateY8a5LqgK2N8cfAE0jqUZe2iS3tun6rVDJufK47eLJlX9S/Ywx2XW2kH5Xgh5C2AAY8sjfKjEZF8PIQtoiKHz2woOVwIDAQAB",
    "private_key": "MIICdgIBADANBgkqhkiG9w0BAQEFAASCAmAwggJcAgEAAoGBAIA6FDIu8u2wh52GSvz3YzYHnC3eJf88ffKOzeg4gKjixA/ZyEveD4G3qEkU9fdVdtyufPa9c8MndqZpq15jxrkuqArY3xx8ATSOpRl7aJLe26fqtUMm58rjt4smVf1L9jDHZdbaQfleCHkLYABjyyN8qMRkXw8hC2iIofPbCg5XAgMBAAECgYB/uVpkol9YY36yahJH6CPqrankBmPS2W6bLaPsrFt73mVtZIEfERJhI499PGPE+lxrdWMEY7HrsmN9X8RTQQ+v9wUiz1bfqvZ0tn2cDl88wIeXfQFeJDcpWICjd9Afjoo6PNTtKYUBuCgF23dOKdRT42jPN1iHBX3kuCQukccQAQJBANjHDsstdw6TcZ53ev+NJqPOyM47bE4bVGQ3qVE1PhVD9s+Tv+yencxCSMbJZo4ozULwRCPyFwtw2ylg6HusVt8CQQCXbW6r1hrhC0DBDCp74CXU4lyVWdu4FnLsQqije4KbbSwi7gjhJLGqtWFyRyC7ZZvHiZ6+w5BPz9h0TTDO14+JAkAKuT4oGuWq2Oxj9HEnNypULCSO3y2qZ3uzQXWkyMd7cdNBzYNPB0GzGwxSmR/zpF0TFKOqS42MSVbuIxcdFxdtAkB6g++m1/OnYJNjnZRB5Xi2ZO7DZ5B9wKv6u3P10Vg6qHmtSSml/ypAE8Bj1WiGNg9zwcTOUyvPZzqZ3lo+/+kBAkEAp3teXeyQcyk1bel+iormaWP8GYONk2utPHGpiNVPv9/tfHBJz0+d6NHus9dyNpGwCmcRJkM+f+umpvUkpU6CQQ==",
    "currency": "RMB",
    "prefix_for_username": "ab",
    "odd_type": "33",
    "game_language": "en-US",
    "adjust_datetime_minutes": 20,
    "gameTimeToServerTime": "+0 hour",
    "serverTimeToGameTime": "-0 hour"
 }
 ******/
class Game_api_ebet_opus extends Abstract_game_api {

	private $api_url;
	private $channelId;
	private $islive;
	private $tag;
	private $public_key;
	private $private_key;
	private $thirdParty;
	private $currency;

	const ProductWallet = 2;
	const TRANSFER_IN = 1;
	const TRANSFER_OUT = 0;
	const TRANSFER_DESC = array( 
		self::TRANSFER_IN => "Transfer In",
		self::TRANSFER_OUT => "Transfer Out"
	);

	const API_performTransfer = 'performTransfer';
	

	public function __construct() {
        parent::__construct();
        $this->CI->load->library("language_function");

		$this->api_url        = $this->getSystemInfo('url');
        $this->game_url       = $this->getSystemInfo('game_url');
        $this->game_mobile_url = $this->getSystemInfo('game_mobile_url');
		$this->channelId      = $this->getSystemInfo('channelId');
		$this->islive         = $this->getSystemInfo('live');
		$this->thirdParty     = $this->getSystemInfo('thirdParty');
		$this->tag            = $this->getSystemInfo('tag');
		$this->public_key     = $this->getSystemInfo('public_key');
		$this->private_key    = $this->getSystemInfo('private_key');
		$this->currency       = $this->getSystemInfo('currency','RMB');
		$this->odd_type       = $this->getSystemInfo('odd_type','33');
		$this->game_language  = $this->getSystemInfo('game_language','en-US');

		# init RSA
		$this->rsa = new Crypt_RSA();
		$this->rsa->setSignatureMode(CRYPT_RSA_SIGNATURE_PKCS1);
		$this->rsa->setHash('md5');

	}

    const SUCCESS_CODE = 200;
    const FAIL_CODE = 401;

	public function getPlatformCode() {
		return EBET_OPUS_API;
	}

	public function onlyTransferPositiveInteger(){
        return true;
    }
    
	public function generateUrl($apiName, $params) {
        return $this->api_url;
	}

	public function getHttpHeaders($params){
		return array("Content-Type" => "application/json");
	}

	public function callback($result = null, $platform = 'web') {		
		$this->CI->utils->debug_log('Request Params ====================================>', $result);
		$this->CI->utils->debug_log('Request Token ====================================>', $result['session_token']);
        $this->CI->utils->debug_log('Request Membername ====================================>', $result['memberName']);

        $success = false;
        $token_prefix       = $this->thirdParty. "-" .$this->tag . "-";
        $result['session_token']    = str_replace($token_prefix, "", $result['session_token']);

        $player_id          = $this->getPlayerIdByToken($result['session_token']);
        $game_username      = $this->getGameUsernameByPlayerId($player_id);

        $params = array(                	
				"message" => "Account not found.",                   
				"status" => self::FAIL_CODE
			);

        $this->CI->utils->debug_log('Check player id ====================================>', $player_id);
        $this->CI->utils->debug_log('Check system token ====================================>', $player_id);        
        
        if (!empty($player_id)) {
            $success = true;
            $playerInfo = $this->getPlayerInfoByToken($result['session_token']);
            $this->CI->utils->debug_log('Check info ====================================>', $playerInfo);
        }

        if ($platform == 'web') {
            if ($success) {
            	$sub_wallet = json_decode($playerInfo["big_wallet"], true)['sub'];
                $balance = $sub_wallet[$this->getPlatformCode()]['total_nofrozen'];

                $params = array(                	
                    "currency"  => $this->currency,
                    "language"  => $this->game_language,
                    "balance"  => $balance,
                    "status"    => self::SUCCESS_CODE
                );
                
            }
        }

        $this->CI->utils->debug_log('Check EBET_OPUS_API RESPONSE (Callback) ====================================>', $params);
        return json_encode($params);
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
		$this->CI->utils->debug_log('EBET OPUS PARAMS POST ',json_encode($postParams,true));
		// echo json_encode($postParams,true);exit;
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postParams,true));
	}

	public function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null) {
		return array(false, null);
	}

	public function processResultBoolean($responseResultId, $resultArr, $playerName = null) {
        if ($resultArr['status'] == self::SUCCESS_CODE) {
			$result = json_decode($resultArr['result'], true);

			if(isset($resultArr['isgamelogs'])&&$resultArr['isgamelogs']){
				$success = true;
			}else{
				$result = $this->xmlToArray($resultArr['result']);
				if($result['status_code'] == 0){
	           		$success = true;
				}else{
	        		$success = false;
				}
			}
        }else{
        	$success = false;
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->error_log('EBET OPUS got error', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
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
            "user_name"     => $playerName,
            "method_action" => "checkuserbalance"
        );

        return $this->callApi(self::API_isPlayerExist, $params, $context);
	}

	public function processResultForIsPlayerExists($params) {
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$userName = $this->getVariableFromContext($params, 'userName');
		$success = false;
		$result = array();

		if ($resultArr['status'] == self::SUCCESS_CODE) {
			$resultArr = $this->xmlToArray($resultArr["result"]);

			if(isset($resultArr['status_code']) && $resultArr['status_code'] == 0) {
				$success = true;
				$result['exists'] = true;
			}else{
				$success = true;
				$result['exists'] = false;
			}
		}else{
			$success = false;
			$result['exists'] = null;
		}

		return array($success, $result);
	}

	public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {	
		parent::createPlayer($playerName, $playerId, $password, $email, $extra);
		$userName = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj'    => $this,
			'callback_method' => 'processResultForCreatePlayer',
			'userName'        => $userName,
			'sbe_userName'    => $playerName
		);

		$params = array(
			'first_name' 	=> $userName,
			'user_name' 	=> $userName,
			'Language' 		=> $this->game_language,
			'odds_type' 	=> $this->odd_type,
			'currency' 		=> $this->currency,
			'method_action' => 'createmember',
		);

		return $this->callApi(self::API_createPlayer, $params, $context);
	}

	public function processResultForCreatePlayer($params){
		$userName = $this->getVariableFromContext($params, 'userName');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJsonArr = $this->getResultJsonFromParams($params);
		$this->CI->utils->debug_log('EBET OPUS CREATE PLAYER resultArr ======>', $resultJsonArr);
		$success = $this->processResultBoolean($responseResultId, $resultJsonArr, $userName);

		$reuslt = array(
			"userName" => $userName
		);

		return array($success, $reuslt);

	}

	public function batchQueryPlayerBalance($playerNames, $syncId = null) {

        if (empty($playerNames)) {
            $playerNames = $this->getAllGameUsernames();
        }

        return $this->batchQueryPlayerBalanceOneByOne($playerNames, $syncId);

    }

	public function queryPlayerBalance($userName) {
		$playerName = $this->getGameUsernameByPlayerUsername($userName);

        $context = array(
			'callback_obj'    => $this,
			'callback_method' => 'processResultForQueryPlayerBalance', 
			'playerName'	  => $playerName
		);

        $params = array(
            "user_name"      => empty($playerName) ? $userName : $playerName,            
            "method_action" => "checkuserbalance"
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
			$resultArr = $this->xmlToArray($resultArr["result"]);
			$result['balance'] = @floatval($resultArr['user_balance']);

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
		return $this->performTransfer($userName, $amount, $type, $transfer_secure_id);

	}

	public function withdrawFromGame($userName, $amount, $transfer_secure_id=null){
		$type = self::TRANSFER_OUT;
		return $this->performTransfer($userName, $amount, $type, $transfer_secure_id);
	}
	
	public function performTransfer($userName, $amount,$type, $transfer_secure_id=null){
		$playerName = $this->getGameUsernameByPlayerUsername($userName);
		$transactionId = $this->tag.$playerName.date("YmdHis");

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForPerformTransfer',
			'playerName' => $playerName,
			'sbe_playerName' => $userName,
			'amount' => $amount,
			'Type' => $type,
			'transactionId' => $transactionId
		);

		$params = array(
			'user_name' => $playerName,			
			'trans_id' => $transactionId,
			'amount' => $amount,
			'currency' => $this->currency,
			'direction' => $type,
			"method_action" => "fundtransfer"
		);
	
		return $this->callApi(self::API_performTransfer, $params, $context);
	}

	public function processResultForPerformTransfer($params){
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$sbe_playerName = $this->getVariableFromContext($params, 'sbe_playerName');
		$external_transaction_id = $this->getVariableFromContext($params, 'transactionId');
		$type = $this->getVariableFromContext($params, 'Type');
		$amount = $this->getVariableFromContext($params, 'amount');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
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
	                    $this->CI->utils->debug_log('============= EBET OPUS AFTER BALANCE FROM WALLET '.self::TRANSFER_DESC[$type].' ######### ', $afterBalance);
	                }
	                // Deposit
	                $this->insertTransactionToGameLogs($playerId, $playerName, $afterBalance, $amount, $responseResultId,
	                    $this->transTypeMainWalletToSubWallet());
                }else{ // Withdraw
                	if ($playerBalance && $playerBalance['success']) {
	                    $afterBalance = $playerBalance['balance'];
	                    $this->CI->utils->debug_log('============= EBET OPUS AFTER BALANCE FROM API '.self::TRANSFER_DESC[$type].' ######### ', $afterBalance);
	                } else {
	                    //IF GET PLAYER BALANCE FAILED
	                    $rlt = $this->CI->wallet_model->getSubWalletBy($playerId, $this->getPlatformCode());
	                    $afterBalance = $rlt->totalBalanceAmount;
	                    $this->CI->utils->debug_log('============= EBET OPUS AFTER BALANCE FROM WALLET '.self::TRANSFER_DESC[$type].' ######### ', $afterBalance);
	                }
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

    public function getLanguage($lang){        
        switch ($lang) {
            case LANGUAGE_FUNCTION::INT_LANG_CHINESE:
                $lang = 'ZH-CN';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_ENGLISH:
                $lang = 'EN-US';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_INDONESIAN:
                $lang = 'EN-US';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_VIETNAMESE:
                $lang = 'EN-US';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_KOREAN:
                $lang = 'EN-US';
                break;
            default:
                $lang = 'EN-US';
                break;
        }
        return $lang;
    }

    public function logout($playerName, $password = null) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForLogout',
			'playerName' => $playerName
		);

		$params = array(
			'PlayerId' => $playerName,
			'method_action' => 'kickuser'
		);

		return $this->callApi(self::API_logout, $params, $context);
	}

	public function processResultForLogout($params){
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);
		$reuslt = array(
			'playerName' => $playerName
		);

		return array($success, $result);
	}

	public function queryForwardGame($playerName, $extra=null) {
        $gameUsername   = $this->getGameUsernameByPlayerUsername($playerName);
        $token        	= $this->getPlayerTokenByUsername($playerName);               
        $token 			= $this->thirdParty . "-" . $this->tag . "-" . $token;
        $language 		= $this->game_language;
        $url 			= null;

        $params =  "token=". $this->thirdParty . "@" . $this->tag . "@" . $gameUsername . "@" . $token . "&language=" . $language;
        $url = $this->game_url . "?" . $params;
        
        if ($extra['is_mobile']) {
        	$url = $this->game_mobile_url . "?" . $params;
        }
		     
        $this->CI->utils->debug_log('queryForwardGame [OPUS] =======================================>' . $url);

        $data = [
            "url"       => $url,
            "success"   => true
            ];

        return $data;
    }

	public function processResultForQueryForwardGame($params){
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);

		$result = array();
		if($success){		
			$data = json_decode($resultArr['result'],true);
			$result = array('url' => $data['GameUrl']);
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
		$return = $this->syncEbetOpusGameLogs($startDate,$endDate,$take,$page);
		if(isset($return['count'])){
			if( $return['count'] == $take ){
				$page++;
				return $this->_continueSync( $startDate, $endDate, $take, $page );
			}
		}
		return $return;
	}


	function syncEbetOpusGameLogs($startDate,$endDate,$take,$page){
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
				"method_action" => "gettransactionmemberdetail"
		);

		$this->utils->debug_log('=====================> OPUS syncOriginalGameLogs params', $params);

		return $this->callApi(self::API_syncGameRecords, $params, $context);
	}

	function processResultForSyncGameRecords($params) {
		$this->CI->load->model(array('ebet_opus_game_logs'));

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);

		$resultArr['isgamelogs'] = true;
		$success = $this->processResultBoolean($responseResultId, $resultArr);
		$count = 0;
		$this->utils->debug_log('=====================> EBET OPUS syncOriginalGameLogs result', json_decode($resultArr['result'],true)['totalCount']);
		
        $rarr = json_decode($resultArr['result'],true);
        $gameRecords = isset($rarr["betHistories"])?$rarr["betHistories"]:array();

		if ($success) {

            if (!empty($gameRecords)) {
                $availableRows = $this->CI->ebet_opus_game_logs->getAvailableRows($gameRecords);

                if (!empty($availableRows)) {
                    $gameRecordsPush = array();
                    foreach ($availableRows as $record) {
                    	// print_r($record);

                        $record['match_datetime'] = $this->gameTimeToServerTime(date("Y-m-d H:i:s", ($record['match_datetime']/1000)));
                        $record['transaction_time'] = $this->gameTimeToServerTime(date("Y-m-d H:i:s", ($record['transaction_time']/1000)));
                        $record['winlost_datetime'] = $this->gameTimeToServerTime(date("Y-m-d H:i:s", ($record['winlost_datetime']/1000)));
                        $record['last_update'] = $this->gameTimeToServerTime(date("Y-m-d H:i:s", ($record['last_update']/1000)));
                        $recordPush = array();
                        
                        $recordPush['ebet_opus_id'] = isset($record['id'])?$record['id']:null;
                        $recordPush['third_party'] = isset($record['thirdParty'])?$record['thirdParty']:null;
                        $recordPush['tag'] = isset($record['tag'])?$record['tag']:null;
                        $recordPush['game_category'] = isset($record['gameCategory'])?$record['gameCategory']:null;
                        $recordPush['trans_id'] = isset($record['trans_id'])?$record['trans_id']:null;
                        $recordPush['member_id'] = isset($record['member_id'])?$record['member_id']:null;
                        $recordPush['league_id'] = isset($record['league_id'])?$record['league_id']:null;
                        $recordPush['home_id'] = isset($record['home_id'])?$record['home_id']:null;
                        $recordPush['away_id'] = isset($record['away_id'])?$record['away_id']:null;
                        $recordPush['match_datetime'] = isset($record['match_datetime'])?$record['match_datetime']:null;
                        $recordPush['bet_type'] = isset($record['bet_type'])?$record['bet_type']:null;
                        $recordPush['parlay_ref_no'] = isset($record['parlay_ref_no'])?$record['parlay_ref_no']:null;
                        $recordPush['odds'] = isset($record['odds'])?$record['odds']:null;
                        $recordPush['currency'] = isset($record['currency'])?$record['currency']:null;
                        $recordPush['stake'] = isset($record['stake'])?$record['stake']:null;
                        $recordPush['winlost_amount'] = isset($record['winlost_amount'])?$record['winlost_amount']:null;
                        $recordPush['transaction_time'] = isset($record['transaction_time'])?$record['transaction_time']:null;
                        $recordPush['ticket_status'] = isset($record['ticket_status'])?$record['ticket_status']:null;
                        $recordPush['version_key'] = isset($record['version_key'])?$record['version_key']:null;
                        $recordPush['odds_type'] = isset($record['odds_type'])?$record['odds_type']:null;
                        $recordPush['sports_type'] = isset($record['sports_type'])?$record['sports_type']:null;
                        $recordPush['bet_team'] = isset($record['bet_team'])?$record['bet_team']:null;
                        $recordPush['home_hdp'] = isset($record['home_hdp'])?$record['home_hdp']:null;
                        $recordPush['away_hdp'] = isset($record['away_hdp'])?$record['away_hdp']:null;
                        $recordPush['match_id'] = isset($record['match_id'])?$record['match_id']:null;
                        $recordPush['is_live'] = isset($record['is_live'])?$record['is_live']:null;
                        $recordPush['home_score'] = isset($record['home_score'])?$record['home_score']:null;
                        $recordPush['away_score'] = isset($record['away_score'])?$record['away_score']:null;
                        $recordPush['choice_code'] = isset($record['choicecode'])?$record['choicecode']:null;
                        $recordPush['choice_name'] = isset($record['choicename'])?$record['choicename']:null;
                        $recordPush['txn_type'] = isset($record['txn_type'])?$record['txn_type']:null;
                        $recordPush['last_update'] = isset($record['last_update'])?$record['last_update']:null;
                        $recordPush['league_name'] = isset($record['leaguename'])?$record['leaguename']:null;
                        $recordPush['home_name'] = isset($record['homename'])?$record['homename']:null;
                        $recordPush['away_name'] = isset($record['awayname'])?$record['awayname']:null;
                        $recordPush['sport_name'] = isset($record['sportname'])?$record['sportname']:null;
                        $recordPush['odds_name'] = isset($record['oddsname'])?$record['oddsname']:null;
                        $recordPush['bet_type_name'] = isset($record['bettypename'])?$record['bettypename']:null;
                        $recordPush['winlost_status'] = isset($record['winlost_status'])?$record['winlost_status']:null;

                        //SBE use                        
                        $playerID = $this->getPlayerIdInGameProviderAuth(strtolower($record['member_id']));
                    	$playerUsername = $this->getGameUsernameByPlayerId($playerID);

                        $recordPush['player_id']  = $playerID;
                        $recordPush['username']  = $playerUsername;
                        $recordPush['external_uniqueid']   = $record['id']; //add external_uniueid for og purposes
                        $recordPush['response_result_id']  = $responseResultId;

                        // print_r($recordPush);
                        // die();
                        $this->CI->ebet_opus_game_logs->insertGameLogs($recordPush);
                        $count++; # add count inserted data
                    }
                }
            }
		}

		return array($success,array('count'=>count($gameRecords)));
	}

	function syncMergeToGameLogs($token) {
		$this->CI->load->model(array('game_logs', 'player_model', 'ebet_opus_game_logs'));

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
		$result = $this->CI->ebet_opus_game_logs->getGameLogStatistics($startDate, $endDate);
		$cnt = 0;
	
		if ($result) {
			foreach ($result as $data) {
				$playerId = $this->getPlayerIdInGameProviderAuth($data->member_id);
				$username = $data->member_id;

				$cnt++;
				$bet_amount = $data->bet_amount;
				$realbet = $data->bet_amount;
				$result_amount = (float)$data->result_amount;

				$game_description_id = $data->game_description_id;
				$game_type_id = $data->game_type_id;

				//should use processGameDesction function
				if (empty($game_description_id)) {
					$unknownGame = $this->getUnknownGame();
					$game_description_id = $unknownGame->id;
					$game_type_id = $unknownGame->game_type_id;
				}

				$betDetails = $this->CI->utils->encodeJson(array('bet' => $bet_amount, 'rate' => $data->odds, 'bet_detail' => ""));

				$extra = array(
					'trans_amount'=> $realbet,
					'odds'	=> 	$data->odds,
					'note' => $betDetails,
				);

				$this->syncGameLogs(
					$game_type_id,
					$game_description_id,
					$data->game_code,
					$data->game_type,
					$data->game,
					$playerId,
					$username,
					$bet_amount,
					$result_amount,
					null, # win_amount
					null, # loss_amount
					null, # after_balance
					0, # has_both_side
					$data->external_uniqueid,
					$data->start_datetime, //start
					$data->end_datetime, //end
					$data->response_result_id,
					1,
					$extra
				);
			}
		}

		$this->CI->utils->debug_log('syncMergeToGameLogs monitor', 'count', $cnt);
		return $rlt;
	}

	public function changePassword($playerName, $oldPassword = null, $newPassword) {
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


	public function login($playerName, $password = null) {
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

	public function xmlToArray($xml_data) {		
		$xml = new SimpleXMLElement($xml_data);
		return json_decode(json_encode((array)$xml), TRUE);
	}
}

/*end of file*/