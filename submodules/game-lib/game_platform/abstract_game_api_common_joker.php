<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

/**
    * API NAME: JOKER_API - 5676
    * Hash: HMAC-SHA1
    * Ticket No: OGP-16252
    *
    * @category Game_platform
    * @version not specified
    * @copyright 2013-2022 tot
    * @author   Pedro P. Vitor Jr.
 */

abstract class Abstract_game_api_common_joker extends Abstract_game_api {
    const METHOD_POST = 'POST';
    const SUCCESS_CODE = "RS_OK";
    const SUCCESS_TRANSFER = "CTS_SUCCESS";
    const DECLINE_TRANSFER = "CTS_DECLINED";

    const GAME_ENABLED = 1;
    const GAME_DISABLED = 0;

    const MD5_FIELDS_FOR_ORIGINAL = [
        'ocode',
        'username',
        'gamecode',
        'description',
        'round_id',
        'time',
        'details',
        'app_id',
        'currency_code',
        'type',
        'transaction_code',
        'before_balance',
        'after_balance',
    ];
    const MD5_FLOAT_AMOUNT_FIELDS = [
        'amount',
        'free_amount',
        'result_amount',
        'before_balance',
        'after_balance',
    ];
    const MD5_FIELDS_FOR_MERGE=[
        'ocode',
        'username',
        'gamecode',
        'description',
        'round_id',
        'time',
        'details',
        'app_id',
        'currency_code',
        'type',
        'transaction_code',
        'external_uniqueid',
    ];
    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE=[
        'amount',
        'free_amount',
        'result_amount',
    ];

    protected $sync_step_in_seconds;
    protected $nextId = null;

    public function __construct() {
        parent::__construct();

        $this->method = self::METHOD_POST;

        $this->api_url = $this->getSystemInfo('url', 'http://api688.net/');
        $this->game_url = $this->getSystemInfo('game_url', 'http://www.gwc688.net/');
        $this->lobby_url = $this->getSystemInfo('lobby_url', 'www.google.com');
        $this->mobile_app_url = $this->getSystemInfo('mobile_app_url', 'joker://www.joker123.net/mobile');

        $this->appID = $this->getSystemInfo('app_id', 'TFD8');
        $this->secretKey = $this->getSystemInfo('secretkey', 'khtbhn8jpjbiq');
        $this->redirectURL = $this->getSystemInfo('redirectURL', 'www.google.com');
        $this->sync_step_in_seconds = $this->getSystemInfo("sync_step_in_seconds",3600);
        $this->lang = $this->getSystemInfo('lang', 'en');
        $this->key_for_app = $this->getSystemInfo('key_for_app','EciWDXpWxyDbfC/QvS61qajkkKy5HZWSCEExJfnosP8=');
        $this->iv_key_for_app = $this->getSystemInfo('iv_key_for_app','o0MgGUB6UvNiYUODWXVYdg==');
        $this->language = $this->getSystemInfo('language','en');
    }

    public function getPlatformCode() {
        return $this->returnUnimplemented();
    }

    public function generateUrl($apiName, $params) {
        ksort($params);
        $http = array(
            "AppID" => $this->appID,
            "Signature" => $this->getSignCode($params)
        );

        $url = $this->api_url."?".urldecode(utf8_encode(http_build_query($http)));

        $this->utils->debug_log("JOKER: Generated URL:", $url, "Params: ", $params, "HTTP: ", $http);

        return $url;
    }

    public function getHttpHeaders($params){
        return array("Accept" => "application/json", "Content-Type" => "application/json");
    }

    public function customHttpCall($ch, $params) {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params,true));

        $this->utils->debug_log("JOKER: (customHttpCall) Params:", $params);
    }

    private function getSignCode($params) {
        $queryString = urldecode(utf8_encode(http_build_query($params)));
        $sign = base64_encode(hash_hmac("sha1", $queryString, $this->secretKey, true));
        $signature = urlencode($sign);
        $this->utils->debug_log("JOKER Sign Code", $signature, "Params:" , $params, "Query:", $queryString, "Secret:", $this->secretKey);

        return $signature;
    }

    private function getSignCodeApp($params) {
        $key = utf8_encode($this->key_for_app);
        $ivString  = base64_decode($this->iv_key_for_app);
        $data = utf8_encode(urldecode(http_build_query($params, '', '&')));
        $encryptedData = base64_encode(openssl_encrypt($data, 'aes-256-cbc', $key,OPENSSL_RAW_DATA, $ivString));
        // print_r(urlencode($encryptedData));exit;

        $this->utils->debug_log("JOKER (Sign Code for APP)","Params:" , $params, "Data:", $data, "Secret:", $key, 'Iv:', $ivString);

        return urlencode($encryptedData);
    }

    protected function isErrorCode($apiName, $params, $statusCode, $errCode, $error) {
        return $errCode || (intval($statusCode, 10) >= 400 && $statusCode != '422');
    }

    protected function processResultBoolean($responseResultId, $resultArr, $playerName = null, $statusCode) {
        $success = false;

        if($statusCode==200 || $statusCode==201) {
            $success = true;
        }

        $this->CI->utils->debug_log('JOKER Process Result ', $statusCode);

        if (!$success) {
            $this->setResponseResultToError($responseResultId);
            $this->CI->utils->debug_log('JOKER got error ', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
        }

        $this->CI->utils->debug_log('Result Array HERE!!! ','Success: ', $success, 'statusCode: ', $statusCode);
        return $success;
    }

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
        parent::createPlayer($playerName, $playerId, $password, $email, $extra);
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForCreatePlayer',
            'playerName' => $playerName,
            'playerId' => $playerId
        );

        $params = array(
            "Method" => "CU",
            "Timestamp" => time(),
            "Username" => $gameUsername # Note: createPlayer takes in playerName without prefix
        );

        $this->CI->utils->debug_log('********** JOKER createPlayer params **********', $params);
        return $this->callApi(self::API_createPlayer, $params, $context);
    }

    public function processResultForCreatePlayer($params){
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $playerId = $this->getVariableFromContext($params, 'playerId');
        $success = $this->processResultBoolean($responseResultId, $resultArr, $playerName, $statusCode);

        if($success) {
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
        }
        return array($success, $resultArr);
    }

    public function isPlayerExist($playerName){
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $playerId  = $this->getPlayerIdInGameProviderAuth($gameUsername);
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForIsPlayerExist',
            'playerName' => $playerName,
            'playerId' => $playerId
        );

        $params = array(
            "Method" => "CU",
            "Timestamp" => time(),
            "Username" => $gameUsername
        );

        return $this->callApi(self::API_isPlayerExist, $params, $context);
    }

    public function processResultForIsPlayerExist($params){
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $playerId = $this->getVariableFromContext($params, 'playerId');
        $playerName = $this->getVariableFromContext($params, 'playerName');

        $exists = $this->processResultBoolean($responseResultId, $resultArr, $playerName, $statusCode);

        if($exists) {
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
        }
        return array(true, array('exists' => $exists, 'result' => $resultArr));
    }

    public function queryPlayerBalance($playerName) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryPlayerBalance',
            'playerName' => $playerName
        );

        $params = array(
            "Method" => "GC",
            "Timestamp" => time(),
            "Username" => $gameUsername
        );

        return $this->callApi(self::API_queryPlayerBalance, $params, $context);
    }

    public function processResultForQueryPlayerBalance($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $success = $this->processResultBoolean($responseResultId, $resultArr, $playerName, $statusCode);

        $this->CI->utils->debug_log('********** JOKER result for queryPlayerBalance **********', $success);

        if($success) {
            return array($success, array('balance' => floatval($resultArr['Credit']), 'result' => $resultArr));
        } else {
            return array(false);
        }
    }

    /* change Password */
    public function changePassword($playerName, $oldPassword = null, $newPassword) {
        if (!empty($playerName)) {
            $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
            $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);

            $context = array(
                'callback_obj' => $this,
                'callback_method' => 'processResultForChangePassword',
                'playerName' => $playerName,
                'gameUsername' => $gameUsername,
                'playerId' => $playerId,
                'newPassword' => $newPassword,
            );

            $params = array(
                "Method" => "SP",
                "Password" => $newPassword,
                "Timestamp" => time(),
                "Username" => $gameUsername
            );

            return $this->callApi(self::API_changePassword, $params, $context);
        }
        return $this->returnFailed('empty player name');
    }

    public function processResultForChangePassword($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $playerName = $this->getVariableFromContext($params,'playerName');
        $newPassword = $this->getVariableFromContext($params,'newPassword');
        $playerId = $this->getVariableFromContext($params,'playerId');
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $resultArr = $this->getResultJsonFromParams($params);
        $result = ['response_result_id' => $responseResultId];

        $success = $this->processResultBoolean($responseResultId, $resultArr, $playerName, $statusCode);

        if ($success) {
            $this->CI->utils->debug_log('********** JOKER Change Password Success! **********', $success);
            //sync password to game_provider_auth
            $this->updatePasswordForPlayer($playerId, $newPassword);
        }

        return array($success, $result);
    }

    #Amount to be transferred. To transfer credit out put in a minus value
    public function depositToGame($userName, $amount, $transfer_secure_id=null) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($userName);
        $requestID = empty($transfer_secure_id) ? $this->generateSerialNo() : $transfer_secure_id;
        $playerId = $this->getPlayerIdInPlayer($userName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForDepositToGame',
            'playerName' => $userName,
            'gameUsername' => $gameUsername,
            'playerId' => $playerId,
            'external_transaction_id' => $requestID,
            'amount' => number_format($amount, 3, '.', ''),
            'transfer_type' => self::API_depositToGame,
        );

        $params = array(
            "Amount" => number_format($amount, 3, '.', ''), # 3 decimal places, with no thousand separator
            "Method" => "TC",
            "RequestID" => $requestID,
            "Timestamp" => time(),
            "Username" => $gameUsername
        );

        return $this->callApi(self::API_depositToGame, $params, $context);

        //return $this->transferCredit($userName, $amount, $transfer_secure_id, self::API_depositToGame);
    }

    public function processResultForDepositToGame($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $amount = $this->getVariableFromContext($params, 'amount');
		$playerId = $this->getVariableFromContext($params, 'playerId');
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');

        $success = $this->processResultBoolean($responseResultId, $resultArr, $playerName, $statusCode);

        $result = array(
            'response_result_id' => $responseResultId,
            'external_transaction_id'=>$external_transaction_id,
            'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
        );

        if($success){
            $result["amount"] = $amount;
            $result["game_balance"] = floatval($resultArr['Credit']);
            $result['didnot_insert_game_logs']=true;
            $result['result'] = $resultArr;
            $result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
        }  else {
            if((in_array($statusCode, $this->other_status_code_treat_as_success)) && $this->treat_500_as_success_on_deposit){
                $result['reason_id']=self::REASON_GAME_PROVIDER_INTERNAL_PROBLEM;
                $result['transfer_status']=self::COMMON_TRANSACTION_STATUS_UNKNOWN;
                $success=true;
            } else {
                $result['result'] = $resultArr;
                $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_UNKNOWN;
            }
        }

        if ($this->verify_transfer_using_query_transaction) {

            $this->test_fail_transfer = $this->getSystemInfo('test_fail_transfer', false);
            if($this->test_fail_transfer){
                $success=false;
            }

            //check transfer status
            $extraParams = [];
            $extraParams['playerName'] = $playerName;
            $extraParams['playerId'] = $playerId;
            $queryTrans = $this->queryTransaction($external_transaction_id, $extraParams);
            $this->CI->utils->debug_log('processResultForDepositToGame queryTrans', $queryTrans);

            if(!empty($queryTrans) && !@$queryTrans['unimplemented'] && $queryTrans['success']){
                if(isset($queryTrans['status']) && 
                    $queryTrans['status']==self::COMMON_TRANSACTION_STATUS_APPROVED &&
                    $queryTrans['external_transaction_id']==$external_transaction_id){
                    $success = true;
                    $result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
                }
            }

        }

        return array($success, $result);

    }

	public function withdrawFromGame($userName, $amount, $transfer_secure_id=null){
        $gameUsername = $this->getGameUsernameByPlayerUsername($userName);
        $requestID = empty($transfer_secure_id) ? $this->generateSerialNo() : $transfer_secure_id;
        $playerId = $this->getPlayerIdInPlayer($userName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForWithdrawFromGame',
            'playerName' => $userName,
            'gameUsername' => $gameUsername,
            'playerId' => $playerId,
            'external_transaction_id' => $requestID,
            'amount' => -1 * number_format($amount, 3, '.', ''),
            'transfer_type' => self::API_withdrawFromGame,
        );

        $params = array(
            "Amount" => -1 * number_format($amount, 3, '.', ''), # 3 decimal places, with no thousand separator
            "Method" => "TC",
            "RequestID" => $requestID,
            "Timestamp" => time(),
            "Username" => $gameUsername
        );

        return $this->callApi(self::API_withdrawFromGame, $params, $context);
	}

	public function processResultForWithdrawFromGame($params){
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $amount = $this->getVariableFromContext($params, 'amount');
		$playerId = $this->getVariableFromContext($params, 'playerId');
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');

        $success = $this->processResultBoolean($responseResultId, $resultArr, $playerName, $statusCode);

        $result = array(
            'response_result_id' => $responseResultId,
            'external_transaction_id'=>$external_transaction_id,
            'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
        );

        if($success){
            $result["amount"] = $amount;
            $result["game_balance"] = floatval($resultArr['Credit']);
            $result['didnot_insert_game_logs']=true;
            $result['result'] = $resultArr;
            $result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
        }  else {
            $result['result'] = $resultArr;
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_UNKNOWN;
        }

        if ($this->verify_transfer_using_query_transaction) {

            $this->test_fail_transfer = $this->getSystemInfo('test_fail_transfer', false);
            if($this->test_fail_transfer){
                $success=false;
            }

            //check transfer status
            $extraParams = [];
            $extraParams['playerName'] = $playerName;
            $extraParams['playerId'] = $playerId;
            $queryTrans = $this->queryTransaction($external_transaction_id, $extraParams);
            $this->CI->utils->debug_log('processResultForWithdrawFromGame queryTrans', $queryTrans);

            if(!empty($queryTrans) && !@$queryTrans['unimplemented'] && $queryTrans['success']){
                if(isset($queryTrans['status']) && 
                    $queryTrans['status']==self::COMMON_TRANSACTION_STATUS_APPROVED &&
                    $queryTrans['external_transaction_id']==$external_transaction_id){
                    $success = true;
                    $result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
                }
            }
            
        }

        return array($success, $result);
	}


    /*public function withdrawFromGame($userName, $amount, $transfer_secure_id=null) {
        return $this->transferCredit($userName, -$amount, $transfer_secure_id, self::API_withdrawFromGame);
    }*/

    public function transferCredit($userName, $amount, $transfer_secure_id=null, $transferType=null) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($userName);
        $requestID = empty($transfer_secure_id) ? $this->generateSerialNo() : $transfer_secure_id;

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForTransferCredit',
            'playerName' => $userName,
            'external_transaction_id' => $requestID,
            'amount' => number_format($amount, 3, '.', ''),
            'transfer_type' => $transferType,
        );

        $params = array(
            "Amount" => number_format($amount, 3, '.', ''), # 3 decimal places, with no thousand separator
            "Method" => "TC",
            "RequestID" => $requestID,
            "Timestamp" => time(),
            "Username" => $gameUsername
        );

        return $this->callApi(self::API_transfer, $params, $context);
    }

    public function processResultForTransferCredit($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $amount = $this->getVariableFromContext($params, 'amount');
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');

        $success = $this->processResultBoolean($responseResultId, $resultArr, $playerName, $statusCode);

        $result = array(
            'response_result_id' => $responseResultId,
            'external_transaction_id'=>$external_transaction_id,
            'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
        );

        if($success){
            $result["amount"] = $amount;
            $result["game_balance"] = floatval($resultArr['Credit']);
            $result['didnot_insert_game_logs']=true;
            $result['result'] = $resultArr;
            $result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
        }  else {
            if((in_array($statusCode, $this->other_status_code_treat_as_success)) && $this->treat_500_as_success_on_deposit){
                $result['reason_id']=self::REASON_GAME_PROVIDER_INTERNAL_PROBLEM;
                $result['transfer_status']=self::COMMON_TRANSACTION_STATUS_UNKNOWN;
                $success=true;
            } else {
                $result['result'] = $resultArr;
                $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_UNKNOWN;
            }
        }
        return array($success, $result);

    }

    public function queryTransaction($transactionId, $extra) {
        $playerName=$extra['playerName'];
		$playerId=$extra['playerId'];
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryTransaction',
            'external_transaction_id' => $transactionId,
			'playerId'=>$playerId,
        );

        $params = array(
            "Method" => "TCH",
            "RequestID" => $transactionId,
            "Timestamp" => time()
        );

        $this->CI->utils->debug_log('********** JOKER params for queryTransaction **********', $params);
        return $this->callApi(self::API_queryTransaction, $params, $context);
    }

    public function processResultForQueryTransaction($params){
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
        $statusCode = $this->getStatusCodeFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, null, $statusCode);

        $result = array(
            'response_result_id' => $responseResultId,
            'external_transaction_id'=>null,
            'amount'=>-1,
            'game_username'=>null,
        );

        if ($success) {
            $result['status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
            $result['amount']=isset($resultArr['Amount'])?$resultArr['Amount']:-1;
            $result['game_username']=isset($resultArr['Username'])?$resultArr['Username']:null;
            $result['external_transaction_id']=isset($resultArr['RequestID'])?$resultArr['RequestID']:null;
        }else{
            $result['status']=self::COMMON_TRANSACTION_STATUS_UNKNOWN;
            $result['reason_id'] = self::REASON_TRANSACTION_NOT_FOUND;
            $this->CI->utils->debug_log('********** JOKER processResultForQueryTransaction ********** External transaction id not found!');
        }

        $this->CI->utils->debug_log('********** JOKER processResultForQueryTransaction **********', $resultArr, $statusCode, $success);
        return array($success, $result);
    }


    public function generateGotoUri($playerName, $extra) {
        return '/player_center/goto_common_game/' . $this->getPlatformCode() . '/' . $extra['game_code'] . '/' . $extra['game_mode'];
    }

    /* Query Forward Game */
    # Provide game launch URL
    public function queryForwardGame($playerName, $extra = array()){

        if($playerName !== null) {
            $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
            $playerId = $this->getPlayerIdInPlayer($playerName);
            $password = $this->getPassword($playerName);
        }

        if($extra['game_mode'] == 'app') {
            $this->changePassword($playerName,null,$password['password']);
            $params = array(
                "username" => strtoupper($this->appID.".".$gameUsername),
                "password" => $password['password'],
                "time" => time(),
                "auto" => 1 // 1: auto login, 0: fill up login form
            );

            $data = array(
                "data" => $this->getSignCodeApp($params)
            );

            $url = $this->mobile_app_url."?".urldecode(utf8_encode(http_build_query($data)));

            $this->utils->debug_log("JOKER: Generated URL:", $url, "Params: ", $params, "HTTP: ", $data);

            return $result = array(
                'success' => true,
                'url' => $url,
                'is_redirect' => true
            );

        }

        $nextUrl = $this->generateGotoUri($playerName, $extra);
        $result = $this->forwardToWhiteDomain($playerName, $nextUrl);
        if($result['success']){
            return $result;
        }

        $game_mode = $extra['game_mode'];
        $game_code = $extra['game_code'];

        $language = isset($extra['language']) ? $this->getLauncherLanguage($extra['language']) : $this->language;

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryForwardGame',
            'playerName' => $playerName,
            'game_code' => $game_code,
            'language' => $language
        );

        $params = array(
            "Method" => "PLAY",
            "Timestamp" => time(),
            "Username" => $gameUsername
        );

        $this->CI->utils->debug_log('********** JOKER params for Query Forward Game !!! **********', $params, $game_code);
        return $this->callApi(self::API_queryForwardGame, $params, $context);

    }

    public function processResultForQueryForwardGame($params) {
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $game_code = $this->getVariableFromContext($params, 'game_code');
        $language = $this->getVariableFromContext($params, 'language');
        $statusCode = $this->getStatusCodeFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $playerName, $statusCode);

        $result['success'] = false;
        if($success){
            $result['success'] = true;

            $httpGame = array(
                "Token" => $resultArr['Token'],
                "Game" => $game_code,
                "redirectURL" => $this->redirectURL,
                "lang" => $language
            );

            $url = $this->game_url."?".urldecode(utf8_encode(http_build_query($httpGame)));

            $result['url'] = $url;

            $this->CI->utils->debug_log('********** JOKER result url for queryForwardGame **********', $resultArr, $url);
        }

        return array($success, $result);
    }


    /* Request Token when launching Game */
    // public function getPlayerToken($playerName){
    //     $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
    //     $context = array(
    //         'callback_obj' => $this,
    //         'callback_method' => 'processResultForGetPlayerToken',
    //         'playerName' => $playerName
    //     );

    //     $params = array(
    //         "Method" => "RT",
    //         "Timestamp" => time(),
    //         "Username" => $gameUsername
    //     );

    //     return $this->callApi(self::API_isPlayerExist, $params, $context);
    // }

    // public function processResultForGetPlayerToken($params){
    //     $responseResultId = $this->getResponseResultIdFromParams($params);
    //     $statusCode = $this->getStatusCodeFromParams($params);
    //     $resultArr = $this->getResultJsonFromParams($params);
    //     $playerName = $this->getVariableFromContext($params, 'playerName');
    //     $success = $this->processResultBoolean($responseResultId, $resultArr, $playerName, $statusCode);

    //     if($success) {
    //         return array(true, array('token' => $resultArr['Token'], 'result' => $resultArr));
    //     } else {
    //         return array(false);
    //     }
    // }

    private function getReasons($reason_id) {
        switch ($reason_id) {
            case 0:
                return self::COMMON_TRANSACTION_STATUS_APPROVED;
                break;
            case 1:
                return self::COMMON_TRANSACTION_STATUS_UNKNOWN;
                break;
            default:
                return self::COMMON_TRANSACTION_STATUS_DECLINED;
                break;
        }
    }

    public function getTransferErrorReasonCode($errorCode) {
        switch ($errorCode) {
            case 'ACCESS_DENIED':
                $reasonCode = self::REASON_INVALID_KEY;
                break;
            case 'PLAYER_NOT_FOUND':
                $reasonCode = self::REASON_NOT_FOUND_PLAYER;
                break;
            case 'CURRENCY_MISMATCH':
                $reasonCode = self::REASON_CURRENCY_ERROR;
                break;
            case 'BAD_REQUEST':
                $reasonCode = self::REASON_INCOMPLETE_INFORMATION;
                break;
            default:
                $reasonCode = self::REASON_UNKNOWN;
        }

        return $reasonCode;
    }

    private function generateSerialNo() {
        $dt = new DateTime($this->utils->getNowForMysql());
        return $dt->format('Ymd').random_string('numeric', 6);
    }

    #
    public function syncOriginalGameLogs($token = false) {
        $startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

        $startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
        $endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));

        $startDate->modify($this->getDatetimeAdjust());

        $start = clone $startDate;
        $end = clone $endDate;

        $this->CI->utils->debug_log('********** JOKER Date Params Here: **********', $startDate, $endDate);

        $now=new DateTime();

        if($end > $now){
           $end = $now;
        }

        $step = $this->sync_step_in_seconds; # steps in seconds
        $success = false;

        $result = [
            'data_count' => 0,
            'data_count_insert'=> 0,
            'data_count_update'=> 0,
            'is_max_return' => false,
            'row_count' => 0,
            'nextId' => null
        ];

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForSyncGameRecords',
            'sync_method' => self::API_syncGameRecords,
        );

        $this->updateNextId = false;

        while($start < $end){
            $endDate = $this->CI->utils->getNextTimeBySeconds($start,$step);
            if($endDate>$end){
                $endDate=$end;
            }

            $params = array(
                "EndDate" => $endDate->format('Y-m-d H:i'),
                "Method" => 'TS',
                "StartDate" => $start->format('Y-m-d H:i'),
                "Timestamp" => time(),
                'NextId' => ''
            );

            $this->CI->utils->debug_log('********** JOKER params for syncOriginalGameLogs **********', $params);

            $api_result = $this->_callSync($params,$context);

            # we check if API call is success
            if(isset($api_result["success"]) && ! $api_result["success"]){

                $this->CI->utils->debug_log('JOKER ERROR in calling API: ',$api_result);

                break;
            }else{
                $success = true;
            }

            # we check here if nextId is not empty and row count > 0, if so, use it in next call
            if(isset($api_result['is_max_return']) && $api_result['is_max_return'] && isset($api_result['row_count']) && $api_result['row_count'] > 0) {
                $result['row_count'] += isset($api_result['row_count']) && !empty($api_result['row_count']) ? $api_result['row_count'] : 0;
                $result['data_count'] += isset($api_result['data_count']) && !empty($api_result['data_count']) ? $api_result['data_count'] : 0;
                $result['data_count_insert'] += isset($api_result['data_count_insert']) && !empty($api_result['data_count_insert']) ? $api_result['data_count_insert']: 0;
                $result['data_count_update'] += isset($api_result['data_count_update']) && !empty($api_result['data_count_update']) ? $api_result['data_count_update'] : 0;
                $nextId = isset($api_result['nextId']) ? $api_result['nextId'] : null;
                $this->CI->utils->debug_log(__METHOD__. ' is max return of API ',$api_result["is_max_return"],'row_count', $result['row_count']);

                if(!is_null($nextId)) {
                    $this->updateNextId = true;
                    $this->nextId = $nextId;
                    sleep($this->common_wait_seconds);
                    continue;
                }else{
                    continue;
                }
            }else{
                $result['row_count'] += isset($api_result['row_count']) && !empty($api_result['row_count']) ? $api_result['row_count'] : 0;
                $result['data_count'] += isset($api_result['data_count']) && !empty($api_result['data_count']) ? $api_result['data_count'] : 0;
                $result['data_count_insert'] += isset($api_result['data_count_insert']) && !empty($api_result['data_count_insert']) ? $api_result['data_count_insert']: 0;
                $result['data_count_update'] += isset($api_result['data_count_update']) && !empty($api_result['data_count_update']) ? $api_result['data_count_update'] : 0;
                $start = $endDate;
            }
        }

        return [
            'success' => $success,
            $result
            ];
    }

    public function processResultForSyncGameRecords($params) {
        $this->CI->load->model(array('original_game_logs_model'));
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);

        $resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, null, $statusCode);

        $dataResult = array(
            'data_count' => 0,
            'data_count_insert'=> 0,
            'data_count_update'=> 0,
            'is_max_return' => false,
            'row_count' => 0,
            'nextId' => null
        );

        if($success && !empty($resultArr['data']['Game']) || !empty($resultArr['data']['Jackpot']) || !empty($resultArr['data']['Competition'])) {
            $dataGameRecords = !empty($resultArr['data']['Game']) ? $resultArr['data']['Game'] : [];
            $dataJackpotRecords = isset($resultArr['data']['Jackpot']) && !empty($resultArr['data']['Jackpot']) ? $resultArr['data']['Jackpot'] : [];
            $dataCompetitionRecords = isset($resultArr['data']['Competition']) && !empty($resultArr['data']['Competition']) ? $resultArr['data']['Competition'] : [];
            $mergeExtraRecords = array_merge($dataJackpotRecords, $dataCompetitionRecords);
            $gameRecords = array_merge($dataGameRecords, $mergeExtraRecords);
            $gameRecordsCount = !empty($gameRecords) && is_array($gameRecords) ? count($gameRecords) : 0;
            $nextId = isset($resultArr['nextId']) && empty($resultArr['nextId']) ? null : $resultArr['nextId'] ;
            $dataGameResult = $this->insertOgl($gameRecords, $responseResultId);

            $this->CI->utils->debug_log(__METHOD__.' data result >>>>>>>>', $dataGameResult);

            if(!is_null($nextId) && $gameRecordsCount > 0) {
                $dataResult['is_max_return'] = true;
                $dataResult['nextId'] = $nextId;
            }else{
                $dataResult['is_max_return'] = false;
            }

            $dataResult['row_count'] += $gameRecordsCount;
            $dataResult['data_count'] += isset($dataGameResult['data_count']) && !empty($dataGameResult['data_count']) ? $dataGameResult['data_count'] : 0;
            $dataResult['data_count_insert'] += isset($dataGameResult['data_count_insert']) && !empty($dataGameResult['data_count_insert']) ? $dataGameResult['data_count_insert']: 0;
            $dataResult['data_count_update'] += isset($dataGameResult['data_count_update']) && !empty($dataGameResult['data_count_update']) ? $dataGameResult['data_count_update'] : 0;
        }else{
            $dataResult['row_count'] += 0;
            $dataResult['data_count'] += 0;
            $dataResult['data_count_insert'] += 0;
            $dataResult['data_count_update'] += 0;
        }

        return array($success, $dataResult);
    }

    public function syncLostAndFound($token) {
        $startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

        $startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
        $endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));

        $startDate->modify($this->getDatetimeAdjust());

        $start = clone $startDate;
        $end = clone $endDate;

        $this->CI->utils->debug_log('********** JOKER Date Params Here: **********', $startDate, $endDate);

        $now = new DateTime();

        if($end > $now) {
           $end = $now;
        }

        $step = $this->sync_step_in_seconds; # steps in seconds
        $success = false;

        $result = [
            'data_count' => 0,
            'data_count_insert'=> 0,
            'data_count_update'=> 0,
            'is_max_return' => false,
            'row_count' => 0,
            'nextId' => null
        ];

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForSyncGameRecords',
            'sync_method' => self::API_syncLostAndFound,
        );

        $this->updateNextId = false;

        while($start < $end) {
            $endDate = $this->CI->utils->getNextTimeBySeconds($start,$step);
            
            if($endDate>$end) {
                $endDate=$end;
            }

            $params = array(
                "EndDate" => $endDate->format('Y-m-d H:i'),
                "Method" => 'TSM',
                "StartDate" => $start->format('Y-m-d H:i'),
                "Timestamp" => time(),
                'NextId' => ''
            );

            $this->CI->utils->debug_log('********** JOKER params for syncLostAndFound **********', $params);

            $api_result = $this->_callSync($params, $context);

            # we check if API call is success
            if(isset($api_result["success"]) && !$api_result["success"]) {
                $this->CI->utils->debug_log('JOKER ERROR in calling API: ',$api_result);
                break;
            }else{
                $success = true;
            }

            # we check here if nextId is not empty and row count > 0, if so, use it in next call
            if(isset($api_result['is_max_return']) && $api_result['is_max_return'] && isset($api_result['row_count']) && $api_result['row_count'] > 0) {
                $result['row_count'] += isset($api_result['row_count']) && !empty($api_result['row_count']) ? $api_result['row_count'] : 0;
                $result['data_count'] += isset($api_result['data_count']) && !empty($api_result['data_count']) ? $api_result['data_count'] : 0;
                $result['data_count_insert'] += isset($api_result['data_count_insert']) && !empty($api_result['data_count_insert']) ? $api_result['data_count_insert']: 0;
                $result['data_count_update'] += isset($api_result['data_count_update']) && !empty($api_result['data_count_update']) ? $api_result['data_count_update'] : 0;
                $nextId = isset($api_result['nextId']) ? $api_result['nextId'] : null;
                $this->CI->utils->debug_log(__METHOD__. ' is max return of API ',$api_result["is_max_return"],'row_count', $result['row_count']);

                if(!is_null($nextId)) {
                    $this->updateNextId = true;
                    $this->nextId = $nextId;
                    sleep($this->common_wait_seconds);
                    continue;
                }else{
                    continue;
                }
            }else{
                $result['row_count'] += isset($api_result['row_count']) && !empty($api_result['row_count']) ? $api_result['row_count'] : 0;
                $result['data_count'] += isset($api_result['data_count']) && !empty($api_result['data_count']) ? $api_result['data_count'] : 0;
                $result['data_count_insert'] += isset($api_result['data_count_insert']) && !empty($api_result['data_count_insert']) ? $api_result['data_count_insert']: 0;
                $result['data_count_update'] += isset($api_result['data_count_update']) && !empty($api_result['data_count_update']) ? $api_result['data_count_update'] : 0;
                $start = $endDate;
            }
        }

        return [
            'success' => $success,
            $result
            ];
    }

    /**
     * Insert Original Game logs daata
     *
     * @param array $gameRecords
     * @param int $responseResultId
     *
     * @return array
     *
     */
    public function insertOgl(&$gameRecords,$responseResultId)
    {

        $this->processGameRecords($gameRecords,$responseResultId);

        list($insertRows, $updateRows)=$this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
            $this->originalTable,
            $gameRecords,
            'external_uniqueid',
            'external_uniqueid',
            self::MD5_FIELDS_FOR_ORIGINAL,
            'md5_sum',
            'id',
            self::MD5_FLOAT_AMOUNT_FIELDS
        );

        $dataResult = [
            'data_count' => 0,
            'data_count_insert' => 0,
			'data_count_update' => 0
        ];
        
        $this->CI->utils->debug_log('after process available rows ----->', 'gamerecords-> ' . count($gameRecords), 'insertrows-> ' . count($insertRows), 'updaterows-> ' . count($updateRows));
        
        $dataResult['data_count'] += is_array($gameRecords) ? count($gameRecords) : 0;

        if (!empty($insertRows)) {
            $dataResult['data_count_insert'] += $this->updateOrInsertOriginalGameLogs($insertRows, 'insert');
        }
        unset($insertRows);

        if (!empty($updateRows)) {
            $dataResult['data_count_update'] += $this->updateOrInsertOriginalGameLogs($updateRows, 'update');
        }
        unset($updateRows);

        return $dataResult;
    }

    public function processGameRecords(&$gameRecords, $responseResultId) {
        if(!empty($gameRecords)){
            foreach($gameRecords as $index => $record) {
                $data['ocode'] = isset($record['OCode']) ? $record['OCode'] : null;
                $data['username'] = isset($record['Username']) ? $record['Username'] : null;
                $data['gamecode'] = isset($record['GameCode']) ? $record['GameCode'] : null;
                $data['description'] = isset($record['Description']) ? $record['Description'] : null;
                $data['round_id'] = isset($record['RoundID']) ? $record['RoundID'] : null;
                $data['amount'] = isset($record['Amount']) ? $record['Amount'] : null;
                $data['free_amount'] = isset($record['FreeAmount']) ? $record['FreeAmount'] : null;
                $data['result_amount'] = isset($record['Result']) ? $record['Result'] : null;
                $data['time'] = isset($record['Time']) ? $this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($record['Time']))) : null;
                $data['details'] = isset($record['Details']) ? json_encode($record['Details']) : null;
                $data['app_id'] = isset($record['AppID']) ? $record['AppID'] : null;
                $data['currency_code'] = isset($record['CurrencyCode']) ? $record['CurrencyCode'] : null;
                $data['type'] = isset($record['Type']) ? $record['Type'] : null;
                $data['transaction_code'] = isset($record['TransactionOCode']) ? $record['TransactionOCode'] : null;
                $data['before_balance'] = isset($record['StartBalance']) ? $record['StartBalance'] : null;
                $data['after_balance'] = isset($record['EndBalance']) ? $record['EndBalance'] : null;
                $data['extra_info'] = !empty($record) && is_array($record) ? json_encode($record) : null;

                $data['response_result_id'] = $responseResultId;
                $data['external_uniqueid'] = $data['ocode']; //add external_uniueid for og purposes
                $gameRecords[$index] = $data;

                unset($data);
            }
        }
    }

    public function _callSync(&$params,$context)
    {
        if($this->updateNextId){
            $params['NextId'] = $this->nextId;
        }
        $api_result =  $this->callApi($context['sync_method'], $params, $context);

        return $api_result;
    }

    public function processJackpotGameRecords($gameJackpotRecords,$key,$result) {
        if(!empty($gameJackpotRecords)){
            if(isset($gameJackpotRecords['Jackpot'][$key])){
                foreach($gameJackpotRecords['Jackpot'][$key] as $record) {
                    if(isset($record['Result'])){
                        $this->CI->utils->info_log('jason record Result',$record['Result'],'result',$result);
                        return $record['Result'] + $result;
                    }
                    return $result;
                }
            }
            return $result;
        }
        return $result;
    }

    private function updateOrInsertOriginalGameLogs($rows, $update_type, $additionalInfo=[]){
        $dataCount = 0;
        if(!empty($rows))
        {
            foreach ($rows as $key => $record) {
                if ($update_type=='update') {
                    $this->CI->original_game_logs_model->updateRowsToOriginal($this->originalTable, $record);
                } else {
                    unset($record['id']);
                    $this->CI->original_game_logs_model->insertRowsToOriginal($this->originalTable, $record);
                }
                $dataCount++;
                unset($record);
            }
        }
        return $dataCount;
    }

    public function syncMergeToGameLogs($token) {
        $enabled_game_logs_unsettle=false;
        return $this->commonSyncMergeToGameLogs($token,
            $this,
            [$this, 'queryOriginalGameLogs'],
            [$this, 'makeParamsForInsertOrUpdateGameLogsRow'],
            [$this, 'preprocessOriginalRowForGameLogs'],
            $enabled_game_logs_unsettle);
    }

    /**
     * queryOriginalGameLogs
     * @param  string $dateFrom
     * @param  string $dateTo
     * @param  bool   $use_bet_time
     * @return array
     */
    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time) {
        $dateFrom = new DateTime($dateFrom);
        $dateFrom = $dateFrom->modify($this->getDatetimeAdjustSyncMerge());
        $dateFrom = $dateFrom->format('Y-m-d H:i:s');
        $sqlTime='joker.time >= ? and joker.time <= ?';
        $sql = <<<EOD
SELECT
joker.id as sync_index,
joker.response_result_id,
joker.ocode,
joker.username,
joker.gamecode as game_code,
joker.gamecode as game_name,
joker.description,
joker.round_id as round_number,
joker.amount as bet_amt,
joker.free_amount,
joker.result_amount as result_amt,
joker.time as end_at,
joker.time as start_at,
joker.time as bet_at,
joker.details,
joker.app_id,
joker.currency_code,
joker.type,
joker.transaction_code,
joker.external_uniqueid,
joker.md5_sum,
joker.created_at,
joker.updated_at,
joker.before_balance,
joker.after_balance,
joker.extra_info,

game_provider_auth.player_id,
game_provider_auth.login_name as player_username,

gd.id as game_description_id,
gd.game_type_id

FROM $this->originalTable as joker
LEFT JOIN game_description as gd ON joker.gamecode = gd.external_game_id AND gd.game_platform_id = ?
LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
JOIN game_provider_auth ON joker.username = game_provider_auth.login_name
AND game_provider_auth.game_provider_id=?
WHERE 

{$sqlTime}

EOD;

        $params=[
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo
        ];

        return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    }

    /**
     * it will be used on processUnsettleGameLogs and commonUpdateOrInsertGameLogs
     *
     * @param  array $row
     * @return array $params
     */
    public function makeParamsForInsertOrUpdateGameLogsRow(array $row) {
        if(!array_key_exists('md5_sum', $row)){
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow(
                $row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE
            );
        }

        return [
            'game_info' => [
                'game_type_id'          => $row['game_type_id'],
                'game_description_id'   => $row['game_description_id'],
                'game_code'             => $row['game_code'],
                'game_type'             => null,
                'game'                  => $row['game_name']
            ],
            'player_info' => [
                'player_id'             => $row['player_id'],
                'player_username'       => $row['player_username']
            ],
            'amount_info' => [
                'bet_amount'            => $row['bet_amt'],
                'result_amount'         => $row['result_amt'] - $row['bet_amt'],
                'bet_for_cashback'      => $row['bet_amt'],
                'real_betting_amount'   => $row['bet_amt'],
                'win_amount'            => null,
                'loss_amount'           => null,
                'after_balance'         => $row['after_balance']
            ],
            'date_info' => [
                'start_at'              => $row['start_at'],
                'end_at'                => $row['end_at'],
                'bet_at'                => $row['bet_at'],
                'updated_at'            => $row['updated_at']
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => $row['status'],
            'additional_info' => [
                'has_both_side'         => 0,
                'external_uniqueid'     => $row['external_uniqueid'],
                'round_number'          => $row['round_number'],
                'md5_sum'               => $row['md5_sum'],
                'response_result_id'    => $row['response_result_id'],
                'sync_index'            => $row['sync_index'],
                'bet_type'              => $row['type'],
            ],
            'bet_details' => $this->preprocessBetDetails($row),
            'extra' => [
                'note' => lang('Type') . ': ' . $row['type'],
            ],

            'game_logs_id' => isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
        ];

    }

     /**
     *
     * perpare original rows, include process unknown game, pack bet details, convert game status
     *
     * @param  array &$row
     */
    public function preprocessOriginalRowForGameLogs(array &$row){
        if (empty($row['game_description_id'])) {
            $unknownGame = $this->getUnknownGame($this->getPlatformCode());
            list($game_description_id,$game_type_id) = $this->getGameDescriptionInfo($row,$unknownGame);
            $row['game_description_id']= $game_description_id;
            $row['game_type_id'] = $game_type_id;
        }

        $row['status'] = Game_logs::STATUS_SETTLED;
    }


    /**
     * overview : get game description information
     *
     * @param $row
     * @param $unknownGame
     * @param $gameDescIdMap
     * @return array
     */
    private function getGameDescriptionInfo($row, $unknownGame) {

        $game_description_id = null;
        $game_name = str_replace("알수없음",$row['game_code'],
                     str_replace("不明",$row['game_code'],
                     str_replace("Unknown",$row['game_code'],$unknownGame->game_name)));
        $external_game_id = $row['game_code'];
        $extra = array('game_code' => $external_game_id,'game_name' => $game_name);

        $game_type_id = $unknownGame->game_type_id ? $unknownGame->game_type_id : null;
        $game_type = $unknownGame->game_name ? $unknownGame->game_name : self::TAG_CODE_UNKNOWN_GAME;

        return $this->processUnknownGame(
            $game_description_id, $game_type_id,
            $external_game_id, $game_type, $external_game_id, $extra,
            $unknownGame);
    }

       /**
     * Create a game history login token for open game history.
    *  The api will return the game history url included the generated token and other parameters
    */
    public function queryBetDetailLink($playerUsername, $ocode=null, $extra = null) {
        if ($this->force_bet_detail_default_format) {
            return parent::queryBetDetailLink($playerUsername, $ocode, $extra);
        }

        $gameUsername = $this->getGameUsernameByPlayerUsername($playerUsername);
        $playerId = $this->getPlayerIdInPlayer($playerUsername);
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryBetDetailLink',
            'playerUsername' => $playerUsername,
            'playerName' => $gameUsername,
            'playerId' => $playerId
        );

        $params = array(
            "Language" => $this->lang,
            "Method" => "History",
            "OCode" => $ocode,
            "Timestamp" => time(),
            "Type" => !empty($extra) && in_array($extra, ['Game', 'Jackpot', 'Competition']) ? $extra : 'Game',
        );

        $this->CI->utils->debug_log('********** JOKER params for Bet Detail Link !!! **********', $params, $ocode);
        return $this->callApi(self::API_queryBetDetailLink, $params, $context);
    }

    /**
     * Process Result of queryBetDetailLink method
    */
    public function processResultForQueryBetDetailLink($params) {
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $statusCode = $this->getStatusCodeFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $playerName, $statusCode);

        $result['success'] = false;
        if($success){
            $result['success'] = true;
            $url = "http:".$resultArr['Url'];
            $result['url'] = $url;
            //$result['url'] = implode("",explode("//",$url));

            $this->CI->utils->debug_log('********** JOKER result url for BetDetailLink **********', $resultArr, $url);
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

    public function getLauncherLanguage($lang) {
        switch ($lang) {
            case LANGUAGE_FUNCTION::INT_LANG_CHINESE:
            case 'zh-cn':
            case 'zh':
                $language = 'zh';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_INDONESIAN:
            case 'id-id':
            case 'id':
                $language = 'id';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_THAI:
            case 'th-th':
            case 'th':
                $language = 'th';
                break;
            default:
                $language = 'en';
                break;
        }
        return $language;
    }

    public function getTournamentList($status = 'All') {
        $context =[
            'callback_obj' => $this,
            'callback_method' => 'processResultForGetTournamentList',
            'status' => $status,
        ];

        $params = [
            'Method' => 'Tournaments',
            'Timestamp' => time(),
            'Status' => $status,
        ];

        $this->CI->utils->debug_log(__METHOD__, $params);

        return $this->callApi(self::API_getTournamentList, $params, $context);
    }

    public function processResultForGetTournamentList($params) {
        $statusCode = $this->getStatusCodeFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, null, $statusCode);

        $result = [
            'success' => $success,
            'data' => null,
        ];

        if ($success) {
            $result['data'] = $resultArr;

            $this->CI->utils->debug_log(__METHOD__, $resultArr);
        }

        return array($success, $result);
   }

   public function getTournamentInfo($ocode) {
        $context =[
            'callback_obj' => $this,
            'callback_method' => 'processResultForGetTournamentInfo',
            'ocode' => $ocode,
        ];

        $params = [
            'Method' => 'Tournament',
            'Timestamp' => time(),
            'OCode' => $ocode,
        ];

        $this->CI->utils->debug_log(__METHOD__, $params);

        return $this->callApi(self::API_getTournamentInfo, $params, $context);
    }

    public function processResultForGetTournamentInfo($params) {
        $statusCode = $this->getStatusCodeFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, null, $statusCode);

        $result = [
            'success' => $success,
            'data' => null,
        ];

        if ($success) {
            $result['data'] = $resultArr;

            $this->CI->utils->debug_log(__METHOD__, $resultArr);
        }

        return array($success, $result);
    }

    public function getTournamentRank($ocode) {
        $context =[
            'callback_obj' => $this,
            'callback_method' => 'processResultForGetTournamentRank',
            'ocode' => $ocode,
        ];

        $params = [
            'Method' => 'TournamentRank',
            'Timestamp' => time(),
            'OCode' => $ocode,
        ];

        $this->CI->utils->debug_log(__METHOD__, $params);

        return $this->callApi(self::API_getTournamentRank, $params, $context);
    }

    public function processResultForGetTournamentRank($params) {
        $statusCode = $this->getStatusCodeFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, null, $statusCode);

        $result = [
            'success' => $success,
            'data' => null,
        ];

        if ($success) {
            $result['data'] = $resultArr;

            $this->CI->utils->debug_log(__METHOD__, $resultArr);
        }

        return array($success, $result);
    }

    public function getTournamentTickets($playerUsername) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerUsername);

        $context =[
            'callback_obj' => $this,
            'callback_method' => 'processResultForGetTournamentTickets',
            'playerUsername' => $playerUsername,
            'gameUsername' => $gameUsername,
        ];

        $params = [
            'Method' => 'TournamentTicket',
            'Timestamp' => time(),
            'Username' => $gameUsername,
        ];

        $this->CI->utils->debug_log(__METHOD__, $params);

        return $this->callApi(self::API_getTournamentTickets, $params, $context);
    }

    public function processResultForGetTournamentTickets($params) {
        $statusCode = $this->getStatusCodeFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $playerUsername = $this->getVariableFromContext($params, 'playerUsername');
        $success = $this->processResultBoolean($responseResultId, $resultArr, $playerUsername, $statusCode);

        $result = [
            'success' => $success,
            'data' => null,
        ];

        if ($success) {
            $result['data'] = $resultArr;

            $this->CI->utils->debug_log(__METHOD__, $playerUsername, $resultArr);
        }

        return array($success, $result);
    }

    public function preprocessOriginalRowForBetDetails($row, $extra = []) {
        // print_r($row);exit;
        $bet_details = $row;

        if (isset($row['transaction_code'])) {
            $bet_details['bet_id'] = $row['transaction_code'];
        }

        if (isset($row['bet_amt'])) {
            $bet_details['bet_amount'] = $row['bet_amt'];
        }

        if (isset($row['result_amt'])) {
            $bet_details['win_amount'] = $row['result_amt'];
        }

        if (isset($row['game_name'])) {
            $bet_details['game_name'] = $row['game_name'];
        }

        if (isset($row['round_number'])) {
            $bet_details['round_id'] = $row['round_number'];
        }

        if (isset($row['start_at'])) {
            $bet_details['betting_datetime'] = $row['start_at'];
        }

        if (isset($row['end_at'])) {
            $bet_details['settlement_datetime'] = $row['end_at'];
        }

        if (isset($row['type'])) {
            $bet_details['bet_type'] = $row['type'];
        }

        // print_r($bet_details);exit;
        return $bet_details;
    }
}