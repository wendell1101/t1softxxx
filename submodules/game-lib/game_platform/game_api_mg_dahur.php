<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

class Game_api_mg_dahur extends Abstract_game_api {

    private $url;
    private $content_type;
    private $method;

    const MAXIMUM_PASSWORD_LENGTH = 15;
    const FLASH_APP_ID = 1001;
    const HTML5__APP_ID = 1002;

    const GAME_TYPE_LIVE = 'live';
    const GAME_TYPE_SLOTS = 'slots';
    const GAME_TYPE_PROGRESSIVE = 'progressive';

    const DEFAULT_LIVE_DEALER = 1930;
    const DEFAULT_SLOTS = 1954;
    const DEFAULT_PROGRESSIVE = 1227;

    const CATEGORY_PAYOUT = 'PAYOUT';
    const CATEGORY_WAGER = 'WAGER';

    const GAME_MODE_TEST = array('demo', 'fun', 'trial');
    const GAME_WIN = 2;

    const METHOD_POST = 'POST';
    const METHOD_GET = 'GET';

    const DEFAULT_PAGE_SIZE = 100000;

    const RETRY_OPERATION_TIMEOUT_COUNT = 3;

    const API_queryExternalAccountIdFromProvider = "queryExternalAccountIdFromProvider";

    const URI_MAP = [
        self::API_generateToken => '/oauth/token',
        self::API_createPlayer => '/v1/account/member',
        self::API_depositToGame => '/v1/transaction',
        self::API_withdrawFromGame => '/v1/transaction',
        self::API_queryPlayerBalance => '/v1/wallet',//no postdata
        self::API_queryForwardGame => '/v1/launcher/item',
        self::API_syncGameRecords => '/v1/feed/transaction',//no postdata
        self::API_isPlayerExist => '/v1/account',//no postdata
        self::API_queryTransaction => '/v1/transaction',//no postdata
        //self::API_queryForwardGame => '/v1/launcher/item',
        //self::API_syncGameRecords => '/v1/feed/transaction',
        self::API_queryExternalAccountIdFromProvider => '/v1/account'
    ];    

    CONST METHOD_MAP = array(
        self::API_generateToken => self::METHOD_POST,//
        self::API_createPlayer => self::METHOD_POST,//
        self::API_depositToGame => self::METHOD_POST,
        self::API_withdrawFromGame => self::METHOD_POST,
        self::API_queryPlayerBalance => self::METHOD_GET,
        self::API_isPlayerExist => self::METHOD_GET,//
        self::API_queryTransaction => self::METHOD_GET,        
        self::API_queryForwardGame => self::METHOD_POST,
        self::API_syncGameRecords => self::METHOD_GET,
        self::API_queryExternalAccountIdFromProvider => self::METHOD_GET,
    );

    CONST CONTENTTYPE_MAP = array(
        self::API_generateToken => 'query_string',//
        self::API_createPlayer => 'json',//
        self::API_queryPlayerBalance => 'query_string_no_postsdata',
        self::API_isPlayerExist => 'query_string_no_postsdata',//
        self::API_depositToGame => 'json',
        self::API_withdrawFromGame => 'json',
        self::API_queryTransaction => 'query_string_no_postsdata',        
        self::API_queryForwardGame => 'json',
        self::API_syncGameRecords => 'query_string_no_postsdata',
        self::API_queryExternalAccountIdFromProvider => 'query_string',
    );

    public function __construct() {
        parent::__construct();

        $this->url = $this->getSystemInfo('url','test');
        $this->auth_username = $this->getSystemInfo('auth_username');
        $this->auth_password = $this->getSystemInfo('auth_password');
        $this->parent_id = $this->getSystemInfo('parent_id');

        $this->username = $this->getSystemInfo('username');
        $this->password = $this->getSystemInfo('password');

        $this->content_type = $this->getSystemInfo('content_type', 'query_string');
        $this->timezone = $this->getSystemInfo('timezone', 'UTC+8');
        $this->currency = $this->getSystemInfo('currency', 'CNY');
        $this->text_id = $this->getSystemInfo('text_id', 'TEXT-TX-ID');
        $this->language = $this->getSystemInfo('language', 'en');
        $this->lobby_url = $this->getSystemInfo('lobby_url', '');

        # LIVE DEALER TITANIUM
        $this->titanium = $this->getSystemInfo('titanium', 'default');

        #$this->sync_time_interval = $this->getSystemInfo('sync_time_interval', '+59 minutes');
        $this->sync_time_interval = $this->getSystemInfo('sync_time_interval', '+1 hours');

        $this->retry_api_operation_timeout_count = $this->getSystemInfo('retry_api_operation_timeout_count', self::RETRY_OPERATION_TIMEOUT_COUNT);

        $this->auth = '';
        $this->is_token_api = false;
        $this->method = self::METHOD_POST;

        $this->token_timeout_minutes= $this->getSystemInfo('token_timeout_minutes', 45);
        $this->force_get_token= $this->getSystemInfo('force_get_token', false);
    }

    public function getPlatformCode() {
        return MG_DASHUR_API;
    }

    public function getMethod($apiName){
        foreach(self::METHOD_MAP as $key => $value){
            if($key==$apiName){
                return $value;
            }
        }
        return self::METHOD_GET;
    }

    public function getContentType($apiName){
        foreach(self::CONTENTTYPE_MAP as $key => $value){
            if($key==$apiName){
                return $value;
            }
        }
        return 'query_string';
    }    

    public function generateUrl($apiName, $params) {
        //$this->method = @$params['method'];
        //unset($params['method']);

        $this->method = $this->getMethod($apiName);

        $uri = self::URI_MAP[$apiName];
        if ($this->method == self::METHOD_GET) {
            $url = $this->url.$uri;

            if($apiName==self::API_isPlayerExist){
                $url = $url.'/'.$params['account'];
            }else{
                if(isset($params['start_time']) && isset($params['end_time'])) {
                    $start_date = $params['start_time'];
                    $end_date = $params['end_time'];
                    unset($params['start_time'],$params['end_time']);
                    $url = $url.'?'.http_build_query($params).'&start_time='.$start_date.'&end_time='.$end_date;
                } else {
                    $url = $url.'?'.http_build_query($params);
                }
            }
        } else {
            $url = $this->url.$uri;
        }
        
        return $url;
    }

    protected function getHttpHeaders($params) {
        $header = array(
            "X-DAS-TZ" => $this->timezone,
            "X-DAS-CURRENCY" => $this->currency,
            "X-DAS-TX-ID" => $this->text_id,
            "X-DAS-LANG" => $this->language
        );

        $token=null;

        if ($this->is_token_api) {
            $header['Content-Type'] = 'application/x-www-form-urlencoded';
            $header['Authorization'] = 'Basic ' . base64_encode($this->auth_username . ':' . $this->auth_password);
        } else {
            $clone = clone $this;
            $token = $clone->getAvailableApiToken();
            $header['Content-Type'] = 'application/json';
            $header['Authorization'] = 'Bearer ' . $token;
        }

        $this->CI->utils->debug_log('getHttpHeaders', $header, 'params', $params, 'is_token_api', $this->is_token_api, 'token', $token);
        return $header;
    }

    protected function customHttpCall($ch, $params) {
        $this->method = @$params['method'];
        unset($params['method']);

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $this->method);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20 );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if ($this->content_type == 'query_string') {
            $data_string = http_build_query($params);           
            curl_setopt($ch, CURLOPT_POSTFIELDS,  $data_string); 
        } elseif($this->content_type == 'json') {
            $data_string = json_encode($params);        
            curl_setopt($ch, CURLOPT_POSTFIELDS,  $data_string); 
        }else{
            $data_string = '';
        }      

        $this->CI->utils->debug_log('getHttpHeaders', 'method', $this->method, 'params', $params, 'is_token_api', $this->is_token_api, 'data_string', $data_string);
    }

    protected function isErrorCode($apiName, $params, $statusCode, $errCode, $error) {
        return $errCode || intval($statusCode, 10) >= 501;
    }

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
        parent::createPlayer($playerName, $playerId, $password, $email, $extra);
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $password =  random_string('alnum', self::MAXIMUM_PASSWORD_LENGTH);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForCreatePlayer',
            'playerName' => $playerName,
            'playerId' => $playerId,
        );

        $params = array(
            'parent_id' => $this->parent_id,
            'username' =>  $gameUsername,
            'password' => $password,
            'ext_ref' => 'ext_'.$gameUsername,
            'group_id' => '',
            'method' => $this->getMethod(self::API_createPlayer)
        );
        $this->is_token_api = false;

        $this->content_type = $this->getContentType(self::API_createPlayer);
        return $this->callApi(self::API_createPlayer, $params, $context);
    }

    public function processResultForCreatePlayer($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $playerId = $this->getVariableFromContext($params, 'playerId');

        $resultText = $this->getResultTextFromParams($params);
        $resultJsonArr = json_decode($resultText,TRUE);
		$result = array(
			'exists' => false
		);
        $success = $this->processResultBoolean($responseResultId, $resultJsonArr, $playerName);
        if ($success) {
            $result['exists'] = true;
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
        }

        if (isset($resultJsonArr['data'])) {
            // try to save player account id
            $this->updateExternalAccountIdForPlayer($playerId, $resultJsonArr['data']['id']);
        }
        
        $this->CI->utils->debug_log('MGDASHUR: (' . __FUNCTION__ . ')', 'success:', $success, 'RETURN:', $success, $result, 'resultJsonArr', $resultJsonArr, 'params', $params);

        return array($success, $result);
    }

    public function processResultBoolean($responseResultId, $resultJson, $playerName = null) {
        $success = true;
        if (isset($resultJson['error'])) {
            $success = false;
            $this->setResponseResultToError($responseResultId);

            # set to true if player exist
            $message = strtolower($resultJson['error']['message']);
            if (strpos($message, 'account conflicts') !== false) {
                $success = true;
            }
            $this->CI->utils->debug_log('MG DAHUR got error', $responseResultId, 'playerName', $playerName, 'result', $resultJson);
        }
        return $success;
    }

    public function queryPlayerBalance($playerName) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryPlayerBalance',
            'playerName' => $playerName,
        );

        $params = array(
            'account_ext_ref' => 'ext_'.$gameUsername,
            'method' => $this->getMethod(self::API_queryPlayerBalance)
        );

        $this->is_token_api = false;

        $this->content_type = $this->getContentType(self::API_queryPlayerBalance);

        return $this->callApi(self::API_queryPlayerBalance, $params, $context);
    }

    public function processResultForQueryPlayerBalance($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');

        $resultText = $this->getResultTextFromParams($params);
        $resultJsonArr = json_decode($resultText,TRUE);

        $success = $this->processResultBoolean($responseResultId, $resultJsonArr, $playerName);
        $result = array();
        if($success) {
            $result['balance'] = $this->gameAmountToDB($resultJsonArr['data'][0]['credit_balance']);
        }
        return array($success, $result);
    }

    public function depositToGame($playerName, $amount, $transfer_secure_id=null) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        if (empty($transfer_secure_id)) {
            $transfer_secure_id = $this->getSecureId('transfer_request', 'secure_id', false, 'T');
        }
        
        $account_id = $this->getExternalAccountIdByPlayerUsername($playerName);

        # If transfer 50 to game(sub wallet)
        # in casino game it's 50,000 (50x1000)
        # in slots/progressive game it's 50 (0.05x50 )
        $amount = $this->dBtoGameAmount($amount);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForDepositToGame',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'amount' => $amount,
            'external_transaction_id' => $transfer_secure_id
        );

        $params = [
            array(
                'account_id' => $account_id,  # alternative to this account_ext_ref (ext_ref in createPlayer)
                'category' =>  'TRANSFER',
                'type' => 'CREDIT',
                'balance_type' => 'CREDIT_BALANCE',
                'amount' => $amount,
                'external_ref' => $transfer_secure_id,
                'meta_data' => array(
                    'description' => 'credit account to '.$gameUsername,
                    'mypromokey' => 'promo_key_'.$gameUsername
                )
            ),
            'method' => $this->getMethod(self::API_depositToGame)
        ];

        $this->is_token_api = false;

        $this->content_type = $this->getContentType(self::API_depositToGame);

        return $this->callApi(self::API_depositToGame, $params, $context);
    }

    public function processResultForDepositToGame($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $amount = $this->getVariableFromContext($params, 'amount');

        $resultText = $this->getResultTextFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $resultJsonArr = json_decode($resultText,TRUE);

        $success = $this->processResultBoolean($responseResultId, $resultJsonArr, $playerName);

        $result = array(
            'response_result_id' => $responseResultId,
            'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id'=>self::REASON_UNKNOWN
        );
        if ($success) {
            $result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
            $result['didnot_insert_game_logs']=true;
            // $afterBalance = $this->gameAmountToDB($resultJsonArr['data'][0]['balance']);

            // $result["current_player_balance"] = $afterBalance;
            // $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);

            // if ($playerId) {
            //     $this->insertTransactionToGameLogs($playerId, $gameUsername, $afterBalance, $amount, $responseResultId, $this->transTypeMainWalletToSubWallet());
            // } else {
            //     $this->CI->utils->debug_log('error', 'cannot get player id from ' . $playerName . ' getPlayerIdInGameProviderAuth');
            // }
        } else {
            $error_code = @$resultJsonArr['error']['message'];
            // if it's 500 , convert it to success
            if(((in_array($statusCode, $this->other_status_code_treat_as_success)) 
            || ((in_array($error_code, $this->other_status_code_treat_as_success))) 
            && $this->treat_500_as_success_on_deposit)){
                $result['reason_id']=self::REASON_GAME_PROVIDER_INTERNAL_PROBLEM;
                $result['transfer_status']=self::COMMON_TRANSACTION_STATUS_UNKNOWN;
                $success=true;
            }else{
                $message = strtolower($error_code); 
            }
            
            if (strpos($message, 'forbidden') !== false) {
                $result['reason_id']=self::REASON_NOT_FOUND_PLAYER;
            }
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
        }
        return array($success, $result);
    }

    public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null,$notRecordTransaction=false) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        if (empty($transfer_secure_id)) {
            $transfer_secure_id = $this->getSecureId('transfer_request', 'secure_id', false, 'T');
        }

        $account_id = $this->getExternalAccountIdByPlayerUsername($playerName);

        # If transfer 50 to game(sub wallet)
        # in casino game it's 50,000 (50x1000)
        # in slots/progressive game it's 50 (0.05x50 )
        $amount = $this->dBtoGameAmount($amount);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForWithdrawFromGame',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'amount' => $amount,
            'external_transaction_id' => $transfer_secure_id

        );

        $params = [
            array(
                'account_id' => $account_id,  # alternative to this account_ext_ref (ext_ref in createPlayer)
                'category' =>  'TRANSFER',
                'type' => 'DEBIT',
                'balance_type' => 'CREDIT_BALANCE',
                'amount' => $amount,
                'external_ref' => $transfer_secure_id,
                'meta_data' => array(
                    'description' => 'credit account to '.$gameUsername,
                    'mypromokey' => 'promo_key_'.$gameUsername
                )
            ),
            'method' => $this->getMethod(self::API_withdrawFromGame)
        ];

        $this->is_token_api = false;

        $this->content_type = $this->getContentType(self::API_withdrawFromGame);

        return $this->callApi(self::API_withdrawFromGame, $params, $context);
    }

    public function processResultForWithdrawFromGame($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $amount = $this->getVariableFromContext($params, 'amount');

        $resultText = $this->getResultTextFromParams($params);
        $resultJsonArr = json_decode($resultText,TRUE);

        $success = $this->processResultBoolean($responseResultId, $resultJsonArr, $playerName);

        $result = array(
            'response_result_id' => $responseResultId,
            'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id'=>self::REASON_UNKNOWN
        );
        if ($success) {
            $result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
            $result['didnot_insert_game_logs']=true;
            // $afterBalance = $this->gameAmountToDB($resultJsonArr['data'][0]['balance']);

            // $result["current_player_balance"] = $afterBalance;
            // $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);

            // if ($playerId) {
            //     $this->insertTransactionToGameLogs($playerId, $gameUsername, $afterBalance, $amount, $responseResultId, $this->transTypeSubWalletToMainWallet());
            // } else {
            //     $this->CI->utils->debug_log('error', 'cannot get player id from ' . $playerName . ' getPlayerIdInGameProviderAuth');
            // }
        } else {
            $error_code = @$resultJsonArr['error']['message'];
            $message = strtolower($error_code);
            if (strpos($message, 'forbidden') !== false) {
                $result['reason_id']=self::REASON_NOT_FOUND_PLAYER;
            }
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
        }
        return array($success, $result);
    }

    public function queryTransaction($transactionId, $extra) {
        $playerName=$extra['playerName'];
        $playerId=$extra['playerId'];
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $account_id = $this->getExternalAccountIdByPlayerUsername($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryTransaction',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'playerId'=>$playerId,
            'external_transaction_id' => $transactionId
        );

        $params = array(
            'account_id' => $account_id,
            'ext_ref' => $transactionId,
            'method' => $this->getMethod(self::API_queryTransaction)
        );

        $this->is_token_api = false;

        $this->content_type = $this->getContentType(self::API_queryTransaction);

        return $this->callApi(self::API_queryTransaction, $params, $context);
    }

    public function processResultForQueryTransaction($params){
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
        $playerName = $this->getVariableFromContext($params, 'playerName');

        $resultText = $this->getResultTextFromParams($params);
        $resultJsonArr = json_decode($resultText,TRUE);

        $success = $this->processResultBoolean($responseResultId, $resultJsonArr, $playerName);

        $result = array(
            'response_result_id' => $responseResultId,
            'external_transaction_id'=>$external_transaction_id,
            'status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id'=>self::REASON_UNKNOWN
        );

        if ($success) {
            $result['status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
        } else {
            $error_code = @$resultJsonArr['error']['message'];
            $message = strtolower($error_code);
            // they we're using global response
            if (strpos($message, 'forbidden') !== false) {
                $result['reason_id']=self::REASON_INVALID_TRANSACTION_ID;
            }
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
        }

        return array($success, $result);
    }

    public function isPlayerExist($playerName) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForIsPlayerExist',
            'gameUsername' => $gameUsername,
        );

        $account_id = $this->getExternalAccountIdByPlayerUsername($playerName);
        $params = array(
            'account' => $account_id,
            'method' => $this->getMethod(self::API_isPlayerExist)
        );

        $this->content_type = $this->getContentType(self::API_isPlayerExist);

        $this->is_token_api = false;
        
        return $this->callApi(self::API_isPlayerExist, $params, $context);
    }

    public function processResultForIsPlayerExist($params) {
        $resultText = $this->getResultTextFromParams($params);
        $resultJsonArr = json_decode($resultText,TRUE);

        $success = true;
        if (isset($resultJsonArr['data'])) {
            $success = false;
        }
        $result['exists'] = !$success ? true : false;
        return array($success, $result);
    }

    public function queryForwardGame($playerName, $extra = null) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $account_id = $this->getExternalAccountIdByPlayerUsername($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryForwardGame',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername,
        );

        $game_id = isset($extra['item_id']) ? $extra['item_id'] : $extra['game_code'];
        $app_id = (isset($extra['is_mobile']) && $extra['is_mobile']) ? self::HTML5__APP_ID : self::FLASH_APP_ID;
        if(!empty($game_id)) {
            $game_code_pieces = explode("-", $game_id);
            if(count($game_code_pieces) == 2) { // new game_code APP_ID-ITEM_ID
                $app_id = $game_code_pieces[0];
                $game_id = $game_code_pieces[1];
            }
        }

        $params = array(
            'account_id' => $account_id,
            'app_id' => $app_id,
            'login_context' => array(
                'ip' => $this->CI->input->ip_address(),
                'session_key' => $gameUsername,
                'user_agent' => $this->CI->input->user_agent(),
                'lang' => $this->language
            ),
            'method' => $this->getMethod(self::API_queryForwardGame)
        );

        $params['conf_params']['lobby_url'] = $this->lobby_url;

        $game_type = isset($extra['game_type']) ? $extra['game_type'] : null;

        if ($game_type == self::GAME_TYPE_LIVE) {
            $params['item_id'] = is_null($game_id) ? self::DEFAULT_LIVE_DEALER : $game_id;
            $params['conf_params']['titanium'] = $this->titanium;
        } elseif($game_type == self::GAME_TYPE_SLOTS) {
            $params['item_id'] = is_null($game_id) ? self::DEFAULT_SLOTS : $game_id;
        } else {
            $params['item_id'] = is_null($game_id) ? self::DEFAULT_PROGRESSIVE : $game_id;
        }

        // recheck demo
        $mode = isset($extra['game_mode']) ? $extra['game_mode'] : null;
        if (in_array($mode, self::GAME_MODE_TEST)) {
            if($game_type == self::GAME_TYPE_SLOTS){
                unset($params['account_id']);
            }
            $params['demo'] = true;
        }

        $this->content_type = $this->getContentType(self::API_queryForwardGame);

        $this->is_token_api = false;

        return $this->callApi(self::API_queryForwardGame, $params, $context);
    }

    public function processResultForQueryForwardGame($params){
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');

        $resultText = $this->getResultTextFromParams($params);
        $resultJsonArr = json_decode($resultText,TRUE);

        $success = $this->processResultBoolean($responseResultId, $resultJsonArr, $playerName);

        $game_link = '';
        if (isset($resultJsonArr)) {
            $game_link = @$resultJsonArr['data'];
        }
        return array($success, ['url' => $game_link]);
    }

    # NOTE
    # IF PLAYER WIN ( RETURN 2 RECORDS FROM API ) CATEGORY : PAYOUT(amount field is result amount) AND WAGER(amount is player bet)
    # IF PLAYER LOSS ONLY amount field is (loss amount and bet amount )
    public function syncOriginalGameLogs($token = false) {

        $startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

        $startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
        $endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));
        $startDate->modify($this->getDatetimeAdjust());

        // get 1 hour data at a time (api restriction)
        $queryDateTimeStart = $startDate->format('Y-m-d\TH:i:s');
        $queryDateTimeEnd = $startDate->modify($this->sync_time_interval)->format('Y-m-d\TH:i:s');
        $queryDateTimeMax = $endDate->format('Y-m-d\TH:i:s');

        $data_count = 0;
        $success = false;
        while ($queryDateTimeMax  > $queryDateTimeStart) {

            $startDateParam=new DateTime($queryDateTimeStart);
            if($queryDateTimeEnd>$queryDateTimeMax){
                $endDateParam=new DateTime($queryDateTimeMax);
            }else{
                $endDateParam=new DateTime($queryDateTimeEnd);
            }

            $startDateParam = $startDateParam->format('Y-m-d\TH:i:s');
            $endDateParam = $endDateParam->format('Y-m-d\TH:i:s');

            $context = array(
                'callback_obj' => $this,
                'callback_method' => 'processResultForSyncGameRecords',
                'startDate' => $startDateParam,
                'endDate' => $endDateParam,
            );

            $page = 1;
            $retries = array();
            do {
                $continue = true;

                $params = array(
                    'company_id' => $this->parent_id,
                    'start_time' => $startDateParam,
                    'end_time' => $endDateParam,
                    'include_transfers' => 'false',
                    'include_end_round' => 'false',
                    'method' => $this->getMethod(self::API_syncGameRecords),
                    'page_size' => self::DEFAULT_PAGE_SIZE,
                    'page' => $page,  # not in api docs
                );

                $this->content_type = $this->getContentType(self::API_syncGameRecords);

                $this->is_token_api = false;

                $result = $this->callApi(self::API_syncGameRecords, $params, $context);

                if ($result['success']) {
                    $sync_next_page = 'true';

                    # resync next page if api data count is greater than or equal to default page size
                    if ($result['api_data_count'] < self::DEFAULT_PAGE_SIZE) {
                        $data_count+=$result['data_count'];
                        $sync_next_page = 'false';  # for logs
                        $continue = false;
                    }

                    $this->CI->utils->debug_log('MG DAHUR ', ' api data count ===> ', $result['api_data_count'], " inserted data count =====> ", $result['data_count'],
                        ' sync next page ==> ', $sync_next_page, ' datemg_start==> ', $startDateParam, ' datemg_end ', $endDateParam);
                    $page++;
                } else {
                    @$retries[$page]++;
                }

            } while($continue && @$retries[$page] <= $this->retry_api_operation_timeout_count);
            $this->CI->utils->debug_log('MG DAHUR ====> ', $retries);

            $queryDateTimeStart = $endDateParam;
            $queryDateTimeEnd  = (new DateTime($queryDateTimeStart))->modify($this->sync_time_interval)->format('Y-m-d\TH:i:s');

            if($data_count){
                $success = true;
            }
        }
        return array('success' => $success, 'data_count' => $data_count);
    }

    public function processResultForSyncGameRecords($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);

        $resultText = $this->getResultTextFromParams($params);
        $resultJsonArr = json_decode($resultText,TRUE);

        $this->CI->load->model(array('mg_dashur_game_logs', 'player_model'));

        $data_count = 0;
        $success = true;
        $result['api_data_count'] = 0;
        if (!empty($resultJsonArr['data'])) {

            $result['api_data_count'] = count($resultJsonArr['data']);

            $availableRows = $this->CI->mg_dashur_game_logs->getAvailableRows($resultJsonArr['data']);

            foreach($availableRows as $logs) {
                list($game_id, $item_id) = $this->getGameAndItemId($logs['meta_data']);

                $player_id = $this->getPlayerIdByExternalAccountId($logs['account_id']);
                $account_id = isset($logs['account_id']) ? $logs['account_id'] : null;

                $mg_id =  isset($logs['id']) ? $logs['id'] : null;
                $data['test'] = !empty($logs['test']) ? $logs['test'] : 0;
                $data['wallet_code'] = isset($logs['wallet_code']) ? $logs['wallet_code'] : null;
                $data['external_ref'] = isset($logs['external_ref']) ? $logs['external_ref'] : null;
                $data['category'] = isset($logs['category']) ? $logs['category'] : null;
                $data['sub_category'] = isset($logs['sub_category']) ? $logs['sub_category'] : null;
                $data['balance_type'] = isset($logs['balance_type']) ? $logs['balance_type'] : null;
                $data['type'] = isset($logs['type']) ? $logs['type'] : null;
                $data['amount'] = isset($logs['amount']) ?  $this->gameAmountToDB($logs['amount']) : null;
                $data['meta_data'] = isset($logs['meta_data']) ?json_encode($logs['meta_data']) : null;
                $data['mg_id'] = $mg_id;
                $data['parent_transaction_id'] = isset($logs['parent_transaction_id']) ? $logs['parent_transaction_id'] : null;
                $data['account_id'] = $account_id;
                $data['account_ext_ref'] = isset($logs['account_ext_ref']) ? $logs['account_ext_ref'] : null;
                $data['application_id'] = isset($logs['application_id']) ? $logs['application_id'] : null;
                $data['currency_unit'] = isset($logs['currency_unit']) ? $logs['currency_unit'] : null;
                $data['transaction_time'] = isset($logs['transaction_time']) ? $this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($logs['transaction_time']))) : null;
                $data['balance'] = isset($logs['balance']) ? $logs['balance'] : null;
                $data['pool_amount'] = isset($logs['pool_amount']) ? $logs['pool_amount'] : null;
                $data['created_by'] = isset($logs['created_by']) ? $logs['created_by'] : null;
                $data['created'] = isset($logs['created']) ?  $this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($logs['created']))) : null;
                $data['session'] = isset($logs['session']) ? $logs['session'] : null;
                $data['ip'] = isset($logs['ip']) ? $logs['ip'] : null;

                $data['last_updated_time'] = $this->CI->utils->getTodayForMysql();
                $data['md5_sum'] = md5($logs['session'].$logs['amount'].$logs['transaction_time'].$logs['category']);
                $data['round_key'] = $account_id.'-'.$game_id;

                $data['game_id'] = $game_id;
                $data['item_id'] = $item_id;

                //extra info from SBE
                $data['player_id'] = $player_id ? $player_id : 0;
                $data['external_uniqueid'] = $mg_id;
                $data['response_result_id'] = $responseResultId;

                $this->CI->mg_dashur_game_logs->insertGameLogs($data);
                $data_count++;
            }
            $success = true;
        }
        $result['data_count'] = $data_count;

        return array($success, $result);
    }

    public function syncMergeToGameLogs($token) {
        $this->CI->load->model(array('game_logs', 'player_model', 'mg_dashur_game_logs'));

        $dateTimeFrom = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $dateTimeFrom->modify($this->getDatetimeAdjust());
        $dateTimeTo = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

        $startDate = $dateTimeFrom->format('Y-m-d H:i:s');
        $endDate = $dateTimeTo->format('Y-m-d H:i:s');

        $result = $this->CI->mg_dashur_game_logs->getGameLogStatistics($startDate, $endDate);

        $count = 0;
        if($result) {
            $unknownGame = $this->getUnknownGame();

            foreach ($result as $data) {
                $count++;

                $game_description_id = $data['game_description_id'];
                $game_type_id = $data['game_type_id'];

                if (empty($game_description_id)) {
                    $game_description_id = $unknownGame->id;
                    $game_type_id = $unknownGame->game_type_id;
                }

                $trans_date = date('Y-m-d',strtotime($data['transaction_time']));
                list($bet_amount, $result_amount) = $this->processGameResultByRoundKey($data['round_key'], $data['account_id'],$trans_date);

                $bet_time = new DateTime($data['transaction_time']);
                $external_uniqueid = $data['round_key'].'-'.$bet_time->format('Ymd');

                $extra = array(
                    'trans_amount' => $bet_amount,
                    'table' =>  $data['external_ref']
                );

                $this->syncGameLogs(
                    $game_type_id,
                    $game_description_id,
                    $data['game_code'],
                    null,
                    $data['game'],
                    $data['player_id'],
                    $data['player_name'],
                    $bet_amount,
                    $result_amount,
                    null, // win_amount
                    null, // loss_amount
                    null, // after balance
                    0,    // has both side
                    $external_uniqueid,  // use round key for unique id
                    $data['transaction_time'], //start
                    $data['transaction_time'], // end
                    $data['response_result_id'],
                    Game_logs::FLAG_GAME,
                    $extra
                );
            }
        }

        $this->CI->utils->debug_log('syncMergeToGameLogs monitor', 'count', $count);

        return  array('success' => true );
    }


    /***
     * NOTE : v1/feed/transaction return free and real games transaction but can't distinguish if it's a free or real game
     *  - from category "PAYOUT"
     *
     * if multiple payout in same round id just get the last MG ID by round key
     * if multiple wager in same round id get the min(mg_id) first bet
     *
     * NEW UPDATE 11/20/2018 : get all payout including free spin payout
     */
    public function processGameResultByRoundKey($round_key,$accountId,$betTime) {
        $this->CI->load->model(array('mg_dashur_game_logs'));

        // make sure there is a wager data (deprecated)
        //
        # OGP-14477
        # will include freespin in the game log records even without bet amount
        # the reason is, some freespin is used in the next day or any of the following day
        # therefore the bet amount will just set to zero and record the payout amount only
        $wager_record = $this->CI->mg_dashur_game_logs->getWagerByRoundKeyAndCategory($round_key,$accountId,$betTime);

        $bet_amount = $result_amount = 0;
        $username = $external_ref = '';

        // recal multiple wager
        if(!empty($wager_record)){
            foreach ($wager_record as $wager) {
                $username = @$wager['session'];
                $external_ref = @$wager['external_ref'];
                $bet_amount+= $wager['amount'];
            }
        }

        // get all payout including freespin payout
        $payout_records = $this->CI->mg_dashur_game_logs->getPayOutByRoundKeyAndCategory($round_key,$betTime);

        if ($payout_records) {
            foreach ($payout_records as $payout) {
                $result_amount+= $payout['amount'];
            }
            $result_amount = $result_amount-$bet_amount;
        } else {
            // if no PAYOUT category meaning player loss. get result in WAGER category
            $result_amount = -$bet_amount;
        }

        $this->CI->utils->debug_log('MG RECORD ===> BET ', $bet_amount, ' result amount ===> ', $result_amount, '  game name === > ', $username, ' external ref  ==> ', $external_ref);

        return array($bet_amount, $result_amount);
    }

    // if player won (return multiple response in api) with PAYOUT(can be multiple) and WAGER category.
    // if player loss ( return one response ) only WAGER. amount field is total bet and total loss
    public function getGameAndItemId($metaData) {
        $game_id = $item_id = '';

        if (!empty($metaData)) {
            $game_id = $metaData['mg']['game_id'];
            $item_id = $metaData['item_id'];   // game description id
        }
        return array($game_id, $item_id);
    }

    public function changePassword($playerName, $oldPassword = null, $newPassword) {
        return $this->returnUnimplemented();
    }

    public function batchQueryPlayerBalance($playerNames, $syncId = null) {
    }

    public function login($username, $password = null) {
        return $this->returnUnimplemented();
    }

    public function processResultForgetVendorId($params) {
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

    public function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null) {
        return array(false, null);
    }


    public function getExternalAccountIdByPlayerUsername($playerUsername) {
        $external_account_id = parent::getExternalAccountIdByPlayerUsername($playerUsername);
        if(empty($external_account_id)) {
            $external_account_id = $this->queryExternalAccountIdFromProvider($playerUsername)['external_account_id'];
        }
        return $external_account_id;
    }

    public function queryExternalAccountIdFromProvider($playerName) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryExternalAccountIdFromProvider',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername,
        );

        $params = array(
            'ext_ref' => 'ext_'.$gameUsername,
            'method' => $this->getMethod(self::API_queryExternalAccountIdFromProvider)
        );

        $this->is_token_api = false;

        $this->content_type = $this->getContentType(self::API_queryExternalAccountIdFromProvider);

        return $this->callApi(self::API_queryExternalAccountIdFromProvider, $params, $context);
    }

    public function processResultForQueryExternalAccountIdFromProvider($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');

        $resultText = $this->getResultTextFromParams($params);
        $resultJsonArr = json_decode($resultText,TRUE);

        $success = $this->processResultBoolean($responseResultId, $resultJsonArr, $playerName);
        $result = ['external_account_id' => null];
        if($success) {
            if(!empty($resultJsonArr['data'][0]['id'])) {
                $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
                $result['external_account_id'] = $resultJsonArr['data'][0]['id'];
                $this->updateExternalAccountIdForPlayer($playerId, $resultJsonArr['data'][0]['id']);
            }
        }
        return array($success, $result);
    }

    public function getAvailableApiToken(){
        
        $token = null;
        if($this->force_get_token){
            $result = $this->generateToken();
            $this->CI->utils->debug_log("MGDASHUR (getAvailableApiToken)", 'result', $result);
            if(isset($result['api_token'])){
                $token = $result['api_token'];
            }
        }else{
            $token = $this->getCommonAvailableApiToken(function(){
                return $this->generateToken();
             });
        }

        $this->CI->utils->debug_log("MGDASHUR (getAvailableApiToken)",$token);
        return $token;
    }

    private function generateToken() {
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForGenerateToken',
        );

        $params = array(
            'grant_type' => 'password',
            'username' => $this->username,
            'password' => $this->password,
            'method' => $this->getMethod(self::API_generateToken)
        );

        $this->is_token_api = true;

        $this->content_type = $this->getContentType(self::API_generateToken);

        return $this->callApi(self::API_generateToken, $params, $context);
    }

    public function processResultForGenerateToken($params)
    {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultText = $this->getResultTextFromParams($params);
        $resultJsonArr = json_decode($resultText,TRUE);
        $result=['api_token'=>null, 'api_token_timeout_datetime'=>null];
        $success = $this->processResultBoolean($responseResultId, $resultJsonArr);

        //$success = false;
        $result = [];
        if (!empty($resultJsonArr)&&isset($resultJsonArr['access_token'])) {
            $success = true;
            //$result = $resultJsonArr;

            $token_timeout = new DateTime($this->utils->getNowForMysql());
            $minutes = $this->token_timeout_minutes;
            $token_timeout->modify("+".$minutes." minutes");
            $result['api_token']=$resultJsonArr['access_token'];
            $result['api_token_timeout_datetime']=$token_timeout->format('Y-m-d H:i:s');
        }else{
            $success = false;
        }

        $this->CI->utils->debug_log('MGDASHUR: (' . __FUNCTION__ . ')', 'success:', $success, 'RETURN:', $success, $result, 'resultJsonArr', $resultJsonArr, 'params', $params);

        return array($success, $result);
    }


}

/*end of file*/