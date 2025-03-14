<?php
if(! defined("BASEPATH")){
    exit("No direct script access allowed");
}

require_once dirname(__FILE__) . '/abstract_game_api.php';

/**
 * Common Class for Transfer and Seamless Wallet of Evolution Game
 *
 * API Name: EVOLUTION_SEAMLESS_THB1_API ; EVOLUTION_GAMING_API ; T1EVOLUTION_API
 *
 * @see evolution_seamless_service_api.php ; game_api_evolutiion_gaming.php ; game_api_evolution_gaming_t1.php
 *
 */
abstract class Abstract_game_api_common_evolution_gaming extends Abstract_game_api {

    protected $api_url;
    protected $casino_key;
    protected $api_token;
    protected $country_code;
    protected $currency_code;
    protected $language_code;
    protected $original_gamelogs_table;
    public $sleep_time;
    public $api_name = null;
    public $game_vertical, $game_provider;
    public $original_seamless_wallet_transactions_table = 'evolution_seamless_wallet_transactions';
    public $use_monthly_transactions_table=false;

    const RETRIEVE_BALANCE = 'RWA';
    const CREDIT_REQUEST = 'ECR';
    const DEBIT_REQUEST = 'EDB';
    const QUERY_STATUS_REQUEST = 'TRI';# TRI = retrieve transaction info

    const OUTPUT_STRING = 0;
    const OUTPUT_XML = 1;

    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';

    const TRANSFER_SUCCESS = 'Y';
    const TRANSFER_ERROR = 'N';
    const STATUS_RESOLVED = 'Resolved';

    const DEFAULT_GAME_TYPE = 'baccarat';
    const DEFAULT_GAME_CODE = 'zixzea8nrf1675oh';

    const GAME_TYPE_ROULLETTE = 'roulette';
    const GAME_TYPE_MONEYWHEEL = 'moneywheel';
    const GAME_TYPE_BLACKJACK = 'blackjack';
    const GAME_TYPE_BACCARAT = 'baccarat';

    const URI_MAP = array(
        self::API_createPlayer => 'ua/v1/',
        self::API_login => 'ua/v1/',
        self::API_depositToGame => 'api/ecashier',
        self::API_withdrawFromGame => 'api/ecashier',
        self::API_queryPlayerBalance => 'api/ecashier',
        self::API_queryTransaction => 'api/ecashier',
        self::API_isPlayerExist => 'api/ecashier',
        self::API_syncGameRecords => 'api/gamehistory/v1/casino/games',
        self::API_queryBetDetailLink => 'api/render/v1/details',
        self::API_queryGameListFromGameProvider => 'api/lobby/v1',
    );

    const MD5_FIELDS_FOR_ORIGINAL=array(
        'started_at',
        'settled_at',
        'status',
        'player_bet_amount',
        'player_payout',
        'casino_id',
        'game_round_id',
        'bets',
        'player_id'
    );

    const MD5_FLOAT_AMOUNT_FIELDS=array(
        'player_bet_amount',
        'player_payout'
    );

    const MD5_FIELDS_FOR_MERGE=array(
        'status',
        'real_bet_amount',
        'result_amount',
        'game_code',
        'game',
        'game_description_id',
        'game_type_id'
    );

    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE=array(
        'player_bet_amount',
        'player_payout'
    );

    public function __construct() {

        parent::__construct();

        $this->api_url = $this->getSystemInfo('url');
        $this->game_launch_url = $this->getSystemInfo('game_launch_url', $this->api_url);
        $this->casino_key = $this->getSystemInfo('casino_key');
        $this->ecid = $this->getSystemInfo('ecid');
        $this->api_token = $this->getSystemInfo('api_token');
        $this->ectoken = $this->getSystemInfo('ectoken');
        $this->country_code = $this->getSystemInfo('country_code');
        $this->language_code = $this->getSystemInfo('language_code');
        $this->enter_default_game= $this->getSystemInfo('enter_default_game', true);
        $this->sync_time_interval = $this->getSystemInfo('sync_time_interval', '+30 minutes');
        $this->game_logs_api_password = $this->getSystemInfo('game_logs_api_password');
        $this->external_lobby_api_token = $this->getSystemInfo('external_lobby_api_token');
        $this->original_gamelogs_table = $this->getOriginalTable();
        $this->use_admin_url_for_game_history = $this->getSystemInfo('use_admin_url_for_game_history', false);
        $this->admin_url = $this->getSystemInfo('admin_url');
        $this->use_insert_ignore = $this->getSystemInfo('use_insert_ignore',false);
        $this->use_new_uniqueid = $this->getSystemInfo('use_new_uniqueid',false);

        $this->enable_sync_lost_and_found = $this->getSystemInfo('enable_sync_lost_and_found',false);

        $this->enable_mm_channel_nofifications = $this->getSystemInfo('enable_mm_channel_nofifications', false);
        $this->mm_channel = $this->getSystemInfo('mm_channel', 'test_mattermost_notif');

        $this->player_info = $this->getSystemInfo('player_info', array(
            'CNY' => ['cn', 'CN'],  # lang and country
            'KRW' => [ 'ko', 'KR'],
            'THB' => [ 'th', 'TH'],
            'IDR' => [ 'id', 'ID'],
            'VND' => [ 'vi', 'VN'],
            'INR' => [ 'hi', 'IN'],
            'VN2' => ['vi', 'VN']
        ));

        $this->method = self::METHOD_POST;

        $this->vip_group_id = $this->getSystemInfo('vip_group_id');
        $this->sleep_time = $this->getSystemInfo('sleep_time', '1'); //seconds
        $this->game_vertical = $this->getSystemInfo('game_vertical', 'slots,live,rng');
        $this->game_provider = $this->getSystemInfo('game_provider', 'evolution');
        $this->use_monthly_transactions_table = $this->getSystemInfo('use_monthly_transactions_table', false);
        $this->is_support_lobby = $this->getSystemInfo('is_support_lobby', false);
        $this->game_list_api_url = $this->getSystemInfo('game_list_api_url', $this->api_url);
        $this->fix_username_limit = $this->getSystemInfo('fix_username_limit', true);
        $this->minimum_user_length = $this->getSystemInfo('minimum_user_length', 7);
        $this->maximum_user_length = $this->getSystemInfo('maximum_user_length', 50);
        $this->default_fix_name_length = $this->getSystemInfo('default_fix_name_length', 7);
        $this->truncate_converted_transaction_amount = $this->getSystemInfo('truncate_converted_transaction_amount', false);
    }

    protected function customHttpCall($ch, $params) {

        $this->CI->utils->debug_log('EVOLUTION GAME API PASS =============>' . $this->game_logs_api_password);

        if(isset($params['startDate'])) {
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC );
            curl_setopt($ch, CURLOPT_USERPWD, $this->casino_key.':'.$this->game_logs_api_password);

            $this->utils->debug_log("customHttpCall", 'authentication', $this->casino_key.':'.$this->game_logs_api_password);
        }elseif($this->method == self::METHOD_GET){
            if($this->api_name == self::API_queryPlayerBalance || 
            $this->api_name == self::API_queryTransaction || 
            $this->api_name == self::API_isPlayerExist){
                $headers = [
                    'Content-Type: application/x-www-form-urlencoded',
                    'Authorization: Basic ' . base64_encode($this->casino_key.":".$this->ectoken)
                ];

                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

                $this->utils->debug_log("customHttpCall", 'headers', $headers, 'params', $params, $this->casino_key.":".$this->ectoken);
            }else{
                curl_setopt($ch, CURLOPT_USERPWD, $this->casino_key.':'.$this->api_token);
            }
        }elseif ($this->method == self::METHOD_POST) {
            // curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $this->method);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, 1);
            if ($this->api_name == self::API_depositToGame || $this->api_name == self::API_withdrawFromGame) {

                $headers = [
                    'Content-Type: application/x-www-form-urlencoded',
                    'Authorization: Basic ' . base64_encode($this->casino_key.":".$this->ectoken)
                ];

                $this->utils->debug_log("customHttpCall", 'headers', $headers, 'params', $params, $this->casino_key.":".$this->ectoken);

                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));

                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            } else {
                curl_setopt($ch, CURLOPT_USERPWD, $this->casino_key.':'.$this->api_token);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
                $headers = ['Content-Type: application/json'];

                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            }
        }
    }

    public function generateUrl($apiName, $params) {
        $this->api_name = $apiName;
        $api_uri = self::URI_MAP[$apiName];

        if ($apiName == self::API_createPlayer || $apiName == self::API_login) {
            $url = $this->api_url.$api_uri.$this->casino_key.'/'.$this->api_token;
        } elseif ($apiName == self::API_syncGameRecords) {
            $url = $this->api_url . $api_uri . '?startDate=' . $params['startDate'];
            if(isset($params['endDate'])){
                $url .= '&endDate='. $params['endDate'];
            }
            if ($this->use_admin_url_for_game_history) {
                $url = $this->admin_url . $api_uri . '?startDate=' . $params['startDate'];
            }
        } elseif ($apiName == self::API_depositToGame || $apiName == self::API_withdrawFromGame) {
            $url = $this->api_url . $api_uri;
        } elseif ($apiName == self::API_queryGameListFromGameProvider) {
            $url = $this->api_url . $api_uri . '/' . $this->casino_key . '/state?' . http_build_query($params);
        } else {
            $url = $this->api_url.$api_uri.'?'. http_build_query($params);
        }

        $this->CI->utils->debug_log('EVOLUTION GAME URL =============>' . $url);

        return $url;
    }

    private function getPlayerCurrency($gameUsername){
        # use correct currency code
        $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);

        if($playerId){

            $this->CI->load->model(array('player_model'));

            $currencyCode = $this->CI->player_model->getPlayerCurrencyByPlayerId($playerId);
            if(($currencyCode)){
                if($currencyCode == 'CNY'){
                //    $currencyCode = 'RMB';
                }
                return $currencyCode;
            }else{
                return $this->currency_code;
            }
        } else {
            return $this->currency_code;
        }
    }

    public function getCountryLangByCurrency($currencyCode) {
        $playerInfo = $this->player_info;
        $language = $playerInfo[$currencyCode][0];
        $country = $playerInfo[$currencyCode][1];

        return array($language, $country);
    }

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {

        parent::createPlayer($playerName, $playerId, $password, $email, $extra);

        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $ip_address = $this->CI->input->ip_address();

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForCreatePlayer',
            'playerName' => $gameUsername,
            'playerId' => $playerId
        );

        if ($this->language_code && !empty($this->language_code)) {
            $currencyCode = $this->language_code;
        } else {
            $currencyCode = $this->getPlayerCurrency($gameUsername);
        }

        list($language, $country) = $this->getCountryLangByCurrency($currencyCode);

        $params = array(
            'uuid' => uniqid(),
            'player' => array(
                'id' => $gameUsername,
                'update' => true,
                'firstName' => $gameUsername,
                'lastName' => $gameUsername,
                'nickname' => $gameUsername,
                'country' => $country,
                'language' => $language,
                'currency' => $currencyCode,
                'session' => array('id' => $gameUsername, 'ip' => $ip_address)
            ),
            'config' => array(
                'brand' => array('id' => '1', 'skin' => '1'),
                'channel' => array('wrapped' => false)
            )
        );

        $this->method = self::METHOD_POST;

        $this->utils->debug_log("create player params ============================>", $params, 'currency code ==> ', $currencyCode);

        return $this->callApi(self::API_createPlayer, $params, $context);
    }

    public function processResultForCreatePlayer($params){
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultText = $this->getResultTextFromParams($params);

        $resultJsonArr = json_decode($resultText,TRUE);

        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');

        $success = $this->processResultBoolean($responseResultId, $resultJsonArr, $gameUsername);

        $playerId = $this->getVariableFromContext($params, 'playerId');

        if($success){
	        $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
		}

        return array($success, $resultJsonArr);
    }


    public function login($playerName, $extra = null)
    {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $ip_address = $this->CI->input->ip_address();

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForLogin',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername,
        );

        if ($this->language_code && !empty($this->language_code)) {
            $currencyCode = $this->language_code;
        } else {
            $currencyCode = $this->getPlayerCurrency($gameUsername);
        }

        list($language, $country) = $this->getCountryLangByCurrency($currencyCode);

        // override language for game launching
        $language = !empty($extra['language']) ? $this->getGameLanguage($extra['language']) :  $language;

        $params = array(
            'uuid' => uniqid(),
            'player' => array(
                'id' => $gameUsername,
                'update' => true,
                'firstName' => $gameUsername,
                'lastName' => $gameUsername,
                'nickname' => $gameUsername,
                'country' => $country,
                'language' => $language, # !empty($extra['language']) ? $this->getGameLanguage($extra['language']) :  $this->language_code,
                // 'language' => $language, # !empty($extra['language']) ? $this->getGameLanguage($extra['language']) :  $this->language_code,
                'currency' => $currencyCode,
                'session' => array('id' => $gameUsername, 'ip' => $ip_address),
                'group'   => array('action' => 'clear')
            ),
            'config' => array(
                'brand' => array('id' => '1', 'skin' => '1'),
            )
        );

        if ($this->vip_group_id && !empty($this->vip_group_id)) {
            $params['player']['group'] = [
                    'id'     => $this->vip_group_id,
                    'action' => 'assign'
            ];
        }


        if (!empty($extra['game_type']) && !empty($extra['game_code'])) {
            $params['config']['game']['category'] = $extra['game_type'];
            $params['config']['game']['table']['id'] = $extra['game_code'];
        } elseif(!empty($extra['game_type'])) {
            $params['config']['game']['category'] = $extra['game_type'];
        } else if(!$this->enter_default_game){
            $params['config']['game']['category'] = self::DEFAULT_GAME_TYPE;
            $params['config']['game']['table']['id'] = self::DEFAULT_GAME_CODE;
        }

        $params['config']['channel']['wrapped'] = false;
        if(!empty($extra['is_mobile'])) {
            $params['config']['channel']['mobile'] = $extra['is_mobile'];
        }

        $this->method = self::METHOD_POST;

        return $this->callApi(self::API_login, $params, $context);
    }

    public function getGameLanguage($language) {
        switch (strtolower($language)) {
            case Language_function::INT_LANG_ENGLISH:
            case "en-us":
                return "en";
                break;
            case Language_function::INT_LANG_CHINESE:
            case "zh-cn":
                return "cn";
                break;

            case Language_function::INT_LANG_INDONESIAN:
            case "id-id":
                return "id";
                break;
            case Language_function::INT_LANG_VIETNAMESE:
            case "vi-vn":
                return "vi";
                break;
            case Language_function::INT_LANG_KOREAN:
            case "ko-kr":
                return "ko";
                break;
            case Language_function::INT_LANG_THAI:
            case "th":
            case "th-th":
                return "th";
                break;
            default:
                return "en";
                break;
        }
    }

    public function processResultForLogin($params){
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultText = $this->getResultTextFromParams($params);

        $resultJsonArr = json_decode($resultText,TRUE);

        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');

        $success = $this->processResultBoolean($responseResultId, $resultJsonArr, $gameUsername);

        return array($success, $resultJsonArr);
    }

    public function queryPlayerBalance($playerName) {

        $result = $this->login($playerName);

        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        if ($result['success']) {
            $login_session = substr($result['entry'], -16);

            $context = array(
                'callback_obj' => $this,
                'callback_method' => 'processResultForQueryPlayerBalance',
                'playerName' => $playerName,
                "gameUsername" => $gameUsername,
            );

            $params = array(
                'cCode' => self::RETRIEVE_BALANCE,
                'output' => self::OUTPUT_XML,
                // 'ecID' => $this->ecid, // $this->casino_key.$login_session,
                'euID' => $gameUsername,
            );

            $this->method = self::METHOD_GET;

            $this->utils->debug_log("query balance params ============================>", $params);

            return $this->callApi(self::API_queryPlayerBalance, $params, $context);
        }
    }

    public function processResultForQueryPlayerBalance($params) {

        $resultXml = $this->getResultXmlFromParams($params);

        $resultArr = json_decode(json_encode($resultXml), true);

        $this->utils->debug_log("query balance result ============================>", $resultArr);

        $success = $resultArr['result'] == 'N' ? false : true;

        if(!empty($resultArr['abalance'])) {
            $resultArr['balance'] = $this->gameAmountToDB(floatval($resultArr['abalance']));
        }

        return array($success, $resultArr);
    }

    public function depositToGame($playerName, $amount, $transfer_secure_id=null) {

        $result = $this->login($playerName);

        $playerName = $this->getGameUsernameByPlayerUsername($playerName);

        if ($result['success']) {

            $secure_id = $this->getSecureId('transfer_request', 'secure_id', false, 'T');

            $login_session = substr($result['entry'], -16);

            $externaltranid = $transfer_secure_id ? $transfer_secure_id : $secure_id;

            $amount = $this->dBtoGameAmount($amount);

            $context = array(
                'callback_obj' => $this,
                'callback_method' => 'processResultForDepositToGame',
                'playerName' => $playerName,
                'eTransID' => $externaltranid,
                'gameUsername' => $playerName
            );

            $params = array(
                'cCode' => self::CREDIT_REQUEST,
                'output' => self::OUTPUT_XML,
                'amount' => $amount,
                // 'ecID' => $this->ecid, // $this->casino_key.$login_session,
                'euID' => $playerName,
                'eTransID' => $externaltranid
            );

            $this->method = self::METHOD_POST;

            $this->utils->debug_log("deposit to game params ============================>", $params);

            return $this->callApi(self::API_depositToGame, $params, $context);
        }
    }

    public function processResultForDepositToGame($params) {

        $playerName = $this->getVariableFromContext($params, 'playerName');
        $amount = $this->getParamValueFromParams($params, 'amount');
        $responseResultId = $this->getResponseResultIdFromParams($params);

        $resultXml = $this->getResultXmlFromParams($params);
        $resultArr = json_decode(json_encode($resultXml), true);
        $statusCode = $this->getStatusCodeFromParams($params);

        // $success = false;
        // if ($resultArr['result'] == self::TRANSFER_SUCCESS ) {
        //     $afterBalance = floatval($resultArr['balance']);
        //     $playerId = $this->getPlayerIdInGameProviderAuth($playerName);
        //     if ($playerId) {
        //         $this->insertTransactionToGameLogs($playerId, $playerName, $afterBalance, $amount, $responseResultId, $this->transTypeMainWalletToSubWallet());
        //     } else {
        //         $this->CI->utils->debug_log('error', 'cannot get player id from ' . $playerName . ' getPlayerIdInGameProviderAuth');
        //     }
        //     $success = true;
        // }

        $success = ($resultArr['result'] == self::TRANSFER_SUCCESS) ? true : false;

        $this->utils->debug_log("processResultForDepositToGame", 'resultArr', $resultArr);

        $external_transaction_id = $this->getVariableFromContext($params, 'eTransID');
        $result = array(
            'response_result_id' => $responseResultId,
            'external_transaction_id' => $external_transaction_id,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id' => self::REASON_UNKNOWN
        );

        if($success){
            $result['didnot_insert_game_logs'] = true;
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
        }  else {
            if((in_array($statusCode, $this->other_status_code_treat_as_success)) && $this->treat_500_as_success_on_deposit){
                $result['reason_id']=self::REASON_GAME_PROVIDER_INTERNAL_PROBLEM;
                $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_UNKNOWN;
                $success=true;
            }else{
                $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
            }
        }

        return array($success, $result);
    }


    public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null) {

        $result = $this->login($playerName);

        $playerName = $this->getGameUsernameByPlayerUsername($playerName);

        if ($result['success']) {

            $secure_id = $this->getSecureId('transfer_request', 'secure_id', false, 'T');

            $login_session = substr($result['entry'], -16);

            $externaltranid = $transfer_secure_id ? $transfer_secure_id : $secure_id;

            $amount = $this->dBtoGameAmount($amount);

            $context = array(
                'callback_obj' => $this,
                'callback_method' => 'processResultForWithdrawFromGame',
                'playerName' => $playerName,
                'eTransID' => $externaltranid,
                'gameUsername' => $playerName
            );

            $params = array(
                'cCode' => self::DEBIT_REQUEST,
                'output' => self::OUTPUT_XML,
                'amount' => $amount,
                // 'ecID' => $this->ecid, // $this->casino_key.$login_session
                'euID' => $playerName,
                'eTransID' => $externaltranid
            );

            $this->method = self::METHOD_POST;

            $this->utils->debug_log("withdraw to game params ============================>", $params);

            return $this->callApi(self::API_withdrawFromGame, $params, $context);
        }
    }

    public function processResultForWithdrawFromGame($params) {
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $amount = $this->getParamValueFromParams($params, 'amount');
        $responseResultId = $this->getResponseResultIdFromParams($params);

        $resultXml = $this->getResultXmlFromParams($params);
        $resultArr = json_decode(json_encode($resultXml), true);

        // $success = false;
        // if ($resultArr['result'] == self::TRANSFER_SUCCESS ) {
        //     $afterBalance = floatval($resultArr['balance']);
        //     $playerId = $this->getPlayerIdInGameProviderAuth($playerName);
        //     if ($playerId) {
        //         $this->insertTransactionToGameLogs($playerId, $playerName, $afterBalance, $amount, $responseResultId, $this->transTypeSubWalletToMainWallet());
        //     } else {
        //         $this->CI->utils->debug_log('error', 'cannot get player id from ' . $playerName . ' getPlayerIdInGameProviderAuth');
        //     }
        //     $success = true;
        // }

        $this->utils->debug_log("processResultForWithdrawFromGame", 'resultArr', $resultArr);

        $success = ($resultArr['result'] == self::TRANSFER_SUCCESS) ? true : false;
        $external_transaction_id = $this->getVariableFromContext($params, 'eTransID');
        $result = array(
            'response_result_id' => $responseResultId,
            'external_transaction_id' => $external_transaction_id,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id' => self::REASON_UNKNOWN
        );

        if($success){
            $result['didnot_insert_game_logs'] = true;
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
        }  else {
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
        }

        return array($success, $result);
    }

    public function isPlayerExist($playerName) {

        $playerId = $this->getPlayerIdFromUsername($playerName);
        $playerName = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForIsPlayerExist',
            'playerName' => $playerName,
            'playerId' => $playerId
        );

        $params = array(
            'cCode' => self::RETRIEVE_BALANCE,
            'output' => self::OUTPUT_XML,
            // 'ecID' => $this->ecid,
            'euID' => $playerName,
        );

        $this->method = self::METHOD_GET;

        return $this->callApi(self::API_isPlayerExist, $params, $context);

    }

    public function processResultForIsPlayerExist($params){

        $resultXml = $this->getResultXmlFromParams($params);

        $resultArr = json_decode(json_encode($resultXml), true);

        $playerId = $this->getVariableFromContext($params, 'playerId');

        $this->utils->debug_log("processResultForIsPlayerExist", 'resultArr', $resultArr);

        // if not exist set success to true
        $success = $resultArr['result'] == 'N' ? true : false;

        if(isset($resultArr['abalance']) != null) {
            $success = true;
            $result['exists'] = true;
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
        } else {
            $success = true;
            $result['exists'] = false;
        }

        return array($success, $result);
    }

    public function syncOriginalGameLogs($token = false) {

        $startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

        $startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
        $endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));
        $startDate->modify($this->getDatetimeAdjust());

        //observer the date format
        $queryDateTimeStart = $startDate->format('Y-m-d H:i:s');
        $queryDateTimeEnd = $startDate->modify($this->sync_time_interval)->format('Y-m-d H:i:s');
        $queryDateTimeMax = $endDate->format('Y-m-d H:i:s');

        $rtn = array();

        $t = microtime(true);
        $micro = sprintf("%03d",($t - floor($t)) * 1000);

        while ($queryDateTimeMax  > $queryDateTimeStart) {

            $startDateParam=new DateTime($queryDateTimeStart);
            if($queryDateTimeEnd>$queryDateTimeMax){
                $endDateParam=new DateTime($queryDateTimeMax);
            }else{
                $endDateParam=new DateTime($queryDateTimeEnd);
            }
            $startDateParam = $startDateParam->format('Y-m-d\TH:i:s');
            $endDateParam = $endDateParam->format('Y-m-d\TH:i:s');

            $startDateParam = $startDateParam.'Z';
            $endDateParam = $endDateParam.'Z';

            $context = array(
                'callback_obj' => $this,
                'callback_method' => 'processResultForSyncGameRecords',
                'startDate' => $startDateParam,
                'endDate' => $endDateParam,
            );

            $params = array(
                'startDate' => $startDateParam,  #   '2018-10-22T01:00:00.117Z',
                'endDate' => $endDateParam,      #   '2018-10-22T01:00:00.117Z'
            );

            $rtn[] = $this->callApi(self::API_syncGameRecords, $params, $context);

            sleep($this->sleep_time);

            $queryDateTimeStart = (new DateTime($endDateParam))->format('Y-m-d H:i:s');
            $queryDateTimeEnd  = (new DateTime($queryDateTimeStart))->modify($this->sync_time_interval)->format('Y-m-d H:i:s');

        }
        return array("success"=>true,"sync_details" => $rtn);
    }

    public function updateOrInsertOriginalGameLogs($rows, $type){
        $dataCount=0;
        if(!empty($rows)) {
            foreach ($rows as $row) {
                if ($type=='update') {
                    $data['id']=$row['id'];
                    $this->CI->original_game_logs_model->updateRowsToOriginal($this->original_gamelogs_table, $row);
                } else {
                    if($this->use_insert_ignore){
                        $this->CI->original_game_logs_model->insertIgnoreRowsToOriginal($this->original_gamelogs_table, $row);
                    }else{
                        $this->CI->original_game_logs_model->insertRowsToOriginal($this->original_gamelogs_table, $row);
                    }
                }
                $dataCount++;
                unset($data);
			}
        }
        return $dataCount;
    }

    public function preProcessGameRecords(&$gameRecords,$extra){
        $preProcessResults = array();

        $dataIndex = -1;

        if (!empty($gameRecords['data'])) {
            foreach($gameRecords['data'] as $key => $record) {
                $gameData = $record['games'];

                if (!empty($gameData)) {  # make use participants(player) is not empty
                    //$dataKey=0;
                    foreach ($gameData as $key2 => $data) {
                       $playerData = $data['participants'];
                        foreach ($playerData as $key3 => $record2) {
                            $temp = [];
                            $casino_id = isset($record2['casinoId']) ? $record2['casinoId'] : null;

                            $temp['game_round_id'] = isset($data['id']) ? $data['id'] : null;
                            $temp['started_at'] = isset($data['startedAt']) ? $this->gameTimeToServerTime($data['startedAt']) : null;
                            $temp['settled_at'] = isset($data['settledAt']) ? $this->gameTimeToServerTime($data['settledAt']) : null;
                            $temp['payout'] = isset($data['payout']) ? $data['payout'] : null;
                            $temp['dealer'] = isset($data['dealer']) ? json_encode($data['dealer']) : null;
                            $temp['result'] = isset($data['result']) ? json_encode($data['result']) : null;
                            $temp['game_type'] = isset($data['gameType']) ? $data['gameType'] : null;
                            $temp['status'] = isset($data['status']) ? $data['status'] : null;
                            $temp['currency'] = isset($data['currency']) ? $data['currency'] : null;
                            $temp['wager'] = isset($data['wager']) ? $data['wager'] : null;
                            $temp['table'] = isset($data['table']) ? $data['table']['id'] : null;
                            $temp['participants'] = isset($data['participants']) ? json_encode($data['participants']) : null;
                            $temp['decisions'] = isset($data['decisions']) ? $data['decisions'] : null;

                            # participants(player data)
                            $temp['city'] = isset($record2['city']) ? $record2['city'] : null;
                            $temp['screen_name'] = isset($record2['screenName']) ? $record2['screenName'] : null;
                            $temp['casino_session_id'] = isset($record2['casinoSessionId']) ? $record2['casinoSessionId'] : null;
                            $temp['casino_id'] = $casino_id;
                            $temp['country'] = isset($record2['country']) ? $record2['country'] : null;
                            $temp['bet_coverage'] = isset($record2['betCoverage']) ? json_encode($record2['betCoverage']) : null;
                            $temp['bets'] = isset($record2['bets']) ? json_encode($record2['bets']) : null;
                            $temp['session_id'] = isset($record2['sessionId']) ? $record2['sessionId'] : null;
                            $temp['config_overlays'] = isset($record2['configOverlays']) ? json_encode($record2['configOverlays']) : null;
                            $temp['player_id'] = isset($record2['playerId']) ? $record2['playerId'] : null;
                            if(isset($record2['status'])){ #override main status, get participants round status
                                $temp['status'] = $record2['status'];
                            }

                            list($betAmount, $resultAmount, $betPlacedOn) = $this->processBetResultAndBetPlaceFromBetInfo($record2['bets']);

                            # create unique id
                            $external_uniqueid = $record2['playerId'].'-'.$data['table']['id'].'-'.$betPlacedOn;
                            if($this->use_new_uniqueid){
                                $external_uniqueid = $record2['playerId'].'_'.$temp['game_round_id'];
                            }

                            # reprocess base on participants data
                            $temp['player_bet_amount'] = $betAmount;
                            $temp['player_payout'] = $resultAmount;

                            if(array_key_exists($external_uniqueid, $preProcessResults)) {
                                $temp['player_bet_amount'] += $preProcessResults[$external_uniqueid]['player_bet_amount'];
                                $temp['player_payout'] += $preProcessResults[$external_uniqueid]['player_payout'];
                            }

                            $temp['response_result_id'] = $extra['responseResultId'];
                            $temp['external_uniqueid'] = $external_uniqueid;
                            $temp['last_sync_time'] = $this->CI->utils->getNowForMysql();

                            $temp['screen_name'] = isset($record2['screenName']) ? $record2['screenName'] : null;


                            //if($data['id']=='1703af0f658739e5fa05043a'){
                            //    $this->CI->utils->debug_log('preProcessResult 1703af0f658739e5fa05043a', $preProcessResult[$dataKey], 'dataKey', $dataKey);
                            //}
                            //$dataKey++;
                            $preProcessResults[$external_uniqueid] = $temp;
                        }
                    }
                }
            }
        }



        /*foreach($preProcessResult as $key => $preProcessResultRow){
            if($preProcessResultRow['game_round_id']=='1703af0f658739e5fa05043a'){
                $this->CI->utils->debug_log('preProcessResultRow', $preProcessResultRow, 'key', $key);
            }
        }*/
        $gameRecords = $preProcessResults;
    }

    public function processBetResultAndBetPlaceFromBetInfo($betInfo) {
        $betAmount = $resultAmount = 0;
        $betPlacedOn = '';
        foreach($betInfo as $info) {
            $betAmount+= $info['stake'];
            $resultAmount+= $info['payout'];
            if(empty($betPlaceOn)) {
                $betPlacedOn = $info['placedOn'];
            }
        }
        $resultAmount = $resultAmount - $betAmount;   # to confirm
        return array($betAmount, $resultAmount, $betPlacedOn);
    }

    public function processResultForSyncGameRecords($params)
    {
        $this->CI->load->model(array('external_system','original_game_logs_model'));

        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultText = $this->getResultTextFromParams($params);

        $gameRecords = json_decode($resultText, TRUE);

        $result = array('data_count'=>0);
        $success = false;
        if (!empty($gameRecords)) {
            $extra = ['responseResultId'=>$responseResultId];


            $this->preProcessGameRecords($gameRecords,$extra);
            list($insertRows, $updateRows)=$this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                $this->original_gamelogs_table,			# original table logs
                $gameRecords,						# api record (format array)
                'external_uniqueid',				# unique field in api
                'external_uniqueid',				# unique field in evolution_2_game_logs table
                self::MD5_FIELDS_FOR_ORIGINAL,
                'md5_sum',
                'id',
                self::MD5_FLOAT_AMOUNT_FIELDS
            );

            $gameRecords_cnt = is_array($gameRecords) ? count($gameRecords) : 0;
            $insertRows__cnt = is_array($insertRows) ? count($insertRows) : 0;
            $updateRows_cnt = is_array($updateRows) ? count($updateRows) : 0;

            $this->CI->utils->debug_log('after process available rows', $gameRecords_cnt,$insertRows__cnt,$updateRows_cnt);

            unset($gameRecords);

            if (!empty($insertRows)) {
                $result['data_count'] += $this->updateOrInsertOriginalGameLogs($insertRows, 'insert');
            }
            unset($insertRows);

            if (!empty($updateRows)) {
                $result['data_count'] += $this->updateOrInsertOriginalGameLogs($updateRows, 'update');
            }

            unset($updateRows);
            $success = true;
        }
        return array($success, $result);
    }

    public function processResultBoolean($responseResultId, $resultJson, $gameUsername) {

        if(empty($resultJson)) {

            $this->setResponseResultToError($responseResultId);

            $this->CI->utils->error_log('Evolution Gaming', $responseResultId, 'gameUsername', $gameUsername, 'result', $resultJson);
        }

        return true;
    }

    public function queryForwardGame($playerName, $extra=null) {

        $result = $this->login($playerName, $extra);

        $success = $result['success'] ? true : false;
        if($result['success']) {
            $stringToSearch = 'http';
            if (stripos($result['entry'],$stringToSearch) !== false) {
                $url = preg_replace('/\\\\/', '', $result['entry']);
            } else {
                $url = $this->api_url.substr($result['entry'], 1);
            }
            return array('success' => $success, 'url' => $url);
        }
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

    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time){
        $gidQuery = "";
        if($this->isSeamLessGame()){
            $gidQuery = "and gd.id IS NOT NULL";
        }

        $sqlTime='original_table.settled_at >= ? and original_table.settled_at <= ? ';
        if($use_bet_time){
            $sqlTime='original_table.started_at >= ? and original_table.started_at <= ? ';
        }
        $sqlStatus=" and original_table.status = ?  {$gidQuery}";

        $sql = <<<EOD
SELECT original_table.id as sync_index,
original_table.id,
original_table.external_uniqueid,
original_table.game_round_id,
original_table.started_at as bet_time,
original_table.settled_at as end_time,
original_table.player_id as username,
original_table.response_result_id,
original_table.player_bet_amount as bet_amount,
original_table.player_bet_amount as real_bet_amount,
original_table.player_payout as result_amount,

original_table.last_sync_time,
original_table.md5_sum,
original_table.table as game_code,
original_table.table as game,
original_table.participants as participants,
original_table.decisions as decisions,
original_table.currency as currency,

original_table.result as original_result,
original_table.participants as original_participants,
original_table.game_type as original_game_type,

game_provider_auth.player_id,
gd.id as game_description_id,
gd.game_type_id

FROM {$this->original_gamelogs_table} as original_table

left JOIN game_description as gd ON original_table.table = gd.external_game_id and gd.game_platform_id=?
JOIN game_provider_auth ON original_table.player_id = game_provider_auth.login_name and game_provider_auth.game_provider_id=?

WHERE

{$sqlTime}
{$sqlStatus}
EOD;

        $params=[$this->getPlatformCode(), $this->getPlatformCode(), $dateFrom,$dateTo, self::STATUS_RESOLVED];

        return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    }

    public function makeParamsForInsertOrUpdateGameLogsRow(array $row){

        $extra_info=[];

        if(empty($row['md5_sum'])){
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row,
                self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
        }

        $betDetails = $this->generateBetDetails($row);
        $resultAmount = isset($row['result_amount']) ? $row['result_amount'] : null;
        if(empty($row['participants'])&&empty($row['decisions']) && empty($row['currency']) ){
            $tmpResultAmount = isset($row['result_amount']) ? $row['result_amount'] : 0;
            $tmpBetAmount = isset($row['bet_amount']) ? $row['bet_amount'] : 0;
            $resultAmount = $tmpResultAmount-$tmpBetAmount;
        }

        $logs_info = [
            'game_info'=>array(
                'game_type_id'=> isset($row['game_type_id']) ? $row['game_type_id'] : null,
                'game_description_id'=>isset($row['game_description_id']) ? $row['game_description_id'] : null,
                'game_code'=> isset($row['game_code']) ? $row['game_code'] : null,
                'game_type'=>null,
                'game'=> isset($row['game']) ? $row['game'] : null
            ),
            'player_info'=>array(
                'player_id'=> isset($row['player_id']) ? $row['player_id'] : null,
                'player_username'=> isset($row['username']) ? $row['username'] : null
            ),
            'amount_info'=>array(
                'bet_amount'            => isset($row['bet_amount']) ? $this->gameAmountToDBTruncateNumber($row['bet_amount']) : 0,
                'result_amount'         => isset($resultAmount) ? $this->gameAmountToDBTruncateNumber($resultAmount) : 0,
                'bet_for_cashback'      => isset($row['bet_amount']) ? $this->gameAmountToDBTruncateNumber($row['bet_amount']) : 0,
                'real_betting_amount'   => isset($row['real_bet_amount']) ? $this->gameAmountToDBTruncateNumber($row['real_bet_amount']) : 0,
                'win_amount'            => null,
                'loss_amount'           => null,
                'after_balance'         => isset($row['after_balance']) ? $row['after_balance'] : 0
            ),
            'date_info'=>array(
                'start_at'=> isset($row['bet_time']) ? $row['bet_time'] : null,
                'end_at'=> isset($row['end_time']) ? $row['end_time'] : null,
                'bet_at'=> isset($row['bet_time']) ? $row['bet_time'] : null,
                'updated_at'=> isset($row['last_sync_time']) ? $row['last_sync_time'] : null
            ),
            'flag'=>Game_logs::FLAG_GAME,
            'status'=>$row['status'],
            'additional_info'=>array(
                'has_both_side'=>0,
                'external_uniqueid'=> isset($row['external_uniqueid']) ? $row['external_uniqueid'] : null,
                'round_number'=> isset($row['game_round_id']) ? $row['game_round_id'] : null,
                'md5_sum'=> isset($row['md5_sum']) ? $row['md5_sum'] : null,
                'response_result_id'=> isset($row['response_result_id']) ? $row['response_result_id'] : null,
                'sync_index'=> isset($row['sync_index']) ? $row['sync_index'] : null,
                'bet_type'=>null
            ),
            'bet_details'=>$betDetails,
            'extra'=>$extra_info,
            //from exists game logs
            'game_logs_id'=>isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];

        return $logs_info;
    }

    public function formatDetails($params, $key){
        if(empty($params) || empty($key)){
            return '';
        }
        $data = isset($params[$key])?(array)$params[$key]:[];

        if(is_array($data)){
            return implode(', ',$data);
        }
        return $data;
    }

    public function generateBetDetails($data){
        $originalResult = isset($data['original_result'])?$data['original_result']:'{}';
        $originalResult = json_decode($originalResult,true);
        return $originalResult;/*
        $gameType = isset($data['original_game_type'])?$data['original_game_type']:null;

        $originalParticipants = isset($data['original_participants'])?$data['original_participants']:[];

        if(!is_array($originalResult)){
            $originalResult = json_decode($originalResult,true);
        }

        $this->CI->utils->debug_log("bermar generateBetDetails",$originalResult);

        $result = [];

        if($gameType==self::GAME_TYPE_MONEYWHEEL || $gameType==self::GAME_TYPE_ROULLETTE){
            $result['outcome'] = $this->formatDetails($originalResult, 'outcome');
            $result['banker_score'] = $this->formatDetails(@$originalResult['banker'], 'score');
            $result['banker_cards'] = $this->formatDetails(@$originalResult['banker'], 'cards');
            $result['player_score'] = $this->formatDetails(@$originalResult['player'], 'score');
            $result['player_cards'] = $this->formatDetails(@$originalResult['player'], 'cards');
        }else{
            $result['outcome'] = $this->formatDetails($originalResult, 'outcome');
            $result['banker_score'] = $this->formatDetails(@$originalResult['banker'], 'score');
            $result['banker_cards'] = $this->formatDetails(@$originalResult['banker'], 'cards');
            $result['player_score'] = $this->formatDetails(@$originalResult['player'], 'score');
            $result['player_cards'] = $this->formatDetails(@$originalResult['player'], 'cards');
        }

        return $result;*/
    }

    public function generateBetDetails_($data){
        $originalResult = isset($data['original_result'])?$data['original_result']:[];
        $originalParticipants = isset($data['original_participants'])?$data['original_participants']:[];

        if(!is_array($originalResult)){
            $originalResult = json_decode($originalResult,true);
        }
        $this->CI->utils->debug_log("bermar generateBetDetails",$originalResult);

        $result = [];
        $result['outcome'] = $this->formatDetails($originalResult, 'outcome');
        $result['banker_score'] = $this->formatDetails($originalResult, 'banker_score');
        $result['banker_cards'] = $this->formatDetails($originalResult, 'banker_cards');
        $result['player_score'] = $this->formatDetails($originalResult, 'player_score');
        $result['player_cards'] = $this->formatDetails($originalResult, 'banker_cards');


        $result['banker_score'] = null;
        if(isset($originalResult['banker']['score'])){
            $result['banker_score'] = $originalResult['banker']['score'];
        }

        $result['banker_cards'] = '';
        if(isset($originalResult['banker']['cards'])){
            $result['banker_cards'] = implode(',',$originalResult['banker']['cards']);
        }

        $result['player_score'] = null;
        if(isset($originalResult['player']['score'])){
            $result['player_score'] = $originalResult['player']['score'];
        }

        $result['player_cards'] = '';
        if(isset($originalResult['player']['cards'])){
            $result['player_cards'] = implode(',',$originalResult['player']['cards']);
        }
        return $result;
    }

    private function getGameDescriptionInfo($row, $unknownGame) {
        $game_description_id = null;

        $external_game_id = $row['game_code'];
        $extra = array('game_code' => $row['game_code']);

        $game_type_id = $unknownGame->game_type_id;
        $game_type = $unknownGame->game_name;

        return $this->processUnknownGame(
            $game_description_id, $game_type_id,
            $external_game_id, $game_type, $external_game_id, $extra,
            $unknownGame);
    }

    public function preprocessOriginalRowForGameLogs(array &$row){
        $this->CI->load->model(['evolution_seamless_thb1_wallet_transactions_model']);
        $game_description_id = $row['game_description_id'];
        $game_type_id = $row['game_type_id'];
        if (empty($game_description_id)) {
            list($game_description_id, $game_type_id) = $this->getGameDescriptionInfo($row, $this->getUnknownGame());
        }
        $row['game_description_id']=$game_description_id;
        $row['game_type_id']=$game_type_id;
        $gameRoundId = isset($row['game_round_id']) ? $row['game_round_id'] : null;

        $afterBalanceResult = $this->CI->evolution_seamless_thb1_wallet_transactions_model->getAfterBalance($gameRoundId,$row['username']);

        if(! empty($afterBalanceResult)){
            # update after balance
            $row['after_balance'] = $afterBalanceResult;

            $this->CI->utils->debug_log("after balance updated: ",$row['after_balance'],'game_round_id',$gameRoundId);
        }else{
            $this->CI->utils->debug_log("after balance not updated: ",'game_round_id',$gameRoundId);
        }

        $row['status'] = Game_logs::STATUS_SETTLED;
    }

    public function queryPlayerInfo($playerName) {
        return $this->returnUnimplemented();
    }

    public function changePassword($playerName, $oldPassword, $newPassword) {
        return $this->returnUnimplemented();
    }

    public function updatePlayerInfo($playerName, $infos) {
        return $this->returnUnimplemented();
    }

    public function logout($playerName, $password = null) {
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
        
        $playerName=$extra['playerName'];
		$playerId=$extra['playerId'];
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryTransaction',
            'eTransID' => $transactionId,
            'playerName' => $playerName,
            'gameUsername' => $gameUsername
        );

        $params = array(
            'cCode' => self::QUERY_STATUS_REQUEST,
            'output' => self::OUTPUT_XML,
            // 'ecID' => $this->ecid,
            'eTransID' => $transactionId,
            'euID' => $gameUsername,
        );

        $this->method = self::METHOD_GET;

        $this->utils->debug_log("queryTransaction to game params ============================>", $params);

        return $this->callApi(self::API_queryTransaction, $params, $context);
    }

    public function processResultForQueryTransaction($params){
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $external_transaction_id = $this->getVariableFromContext($params, 'eTransID');
        $resultXml = $this->getResultXmlFromParams($params);
        $resultArr = json_decode(json_encode($resultXml), true);

        $result = array(
            'response_result_id' => $responseResultId,
            'external_transaction_id'=>$external_transaction_id,
            'status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id'=>self::REASON_UNKNOWN
        );

        $success = isset($resultArr['result']) ? true : false;

        $this->utils->debug_log("processResultForQueryTransaction", 'resultArr', $resultArr);

        if($success){
            if($resultArr['result'] == self::TRANSFER_SUCCESS){
                $result['status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
            } else if($resultArr['result'] == self::TRANSFER_ERROR){
                $result['status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
                if(isset($resultArr['errormsg'])){
                    if(strpos($resultArr['errormsg'], 'Transaction is not found for id:') !== false) {
                        $result['reason_id']=self::REASON_TRANSACTION_NOT_FOUND;
                    }
                }
            } else {
                $success = false;
            }
        }
        return array($success, $result);
    }

    public function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null) {
        return array(false, null);
    }

    public function queryTransactionByDateTime($startDate, $endDate){
        $this->CI->load->model(array('original_game_logs_model'));
        $this->original_transactions_table = $this->getTransactionsTable();

$sql = <<<EOD
SELECT
gpa.player_id as player_id,
t.created_at transaction_date,
t.transactionAmount as amount,
t.afterBalance as after_balance,
t.beforeBalance as before_balance,
t.gameId as round_no,
t.external_uniqueid as external_uniqueid,
t.action trans_type
FROM {$this->original_transactions_table} as t
JOIN game_provider_auth gpa on gpa.login_name = t.userId and gpa.game_provider_id = ?
WHERE `t`.`updated_at` >= ? AND `t`.`updated_at` <= ?
ORDER BY t.updated_at asc;

EOD;

$params=[$this->getPlatformCode(),$startDate, $endDate];

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $result;
    }

    public function processTransactions(&$transactions){
        $temp_game_records = [];

        if(!empty($transactions)){
            foreach($transactions as $transaction){

                $temp_game_record = [];
                $temp_game_record['player_id'] = $transaction['player_id'];
                $temp_game_record['game_platform_id'] = $this->getPlatformCode();
                $temp_game_record['transaction_date'] = $transaction['transaction_date'];
                $temp_game_record['amount'] = abs($transaction['amount']);
                $temp_game_record['before_balance'] = $transaction['before_balance'];
                $temp_game_record['after_balance'] = $transaction['after_balance'];
                $temp_game_record['round_no'] = $transaction['round_no'];


                $temp_game_record['transaction_type'] = Transactions::GAME_API_ADD_SEAMLESS_BALANCE;
                if(in_array($transaction['trans_type'], ['debit'])){
                    $temp_game_record['transaction_type'] = Transactions::GAME_API_SUBTRACT_SEAMLESS_BALANCE;
                }

                $extra_info = [];
                $extra=[];
                $extra['trans_type'] = $transaction['trans_type'];
                $extra['extra'] = $extra_info;
                $temp_game_record['extra_info'] = json_encode($extra);
                $temp_game_record['external_uniqueid'] = $transaction['trans_type'].'-'.$transaction['external_uniqueid'];

                $temp_game_records[] = $temp_game_record;
                unset($temp_game_record);
            }
        }

        $transactions = $temp_game_records;
    }

    public function getUnsettledRounds($dateFrom, $dateTo){
        $sqlTime='`created_at` >= ? AND `created_at` <= ?';

        $this->CI->load->model(array('original_game_logs_model'));
        $finalResult = [];
        $this->original_transactions_table = $this->getTransactionsTable();
        $status = Game_logs::STATUS_PENDING;

        $sql = <<<EOD
select group_concat(`action`) as concat_action,  gameId, userId,
SUM(IF(`action` = 'credit', transactionAmount, 0)) as sumCredit,
SUM(IF(`action` = 'debit', transactionAmount, 0)) as sumDebit,
SUM(IF(`action` = 'cancel', transactionAmount, 0)) as sumCancel
from {$this->original_transactions_table}
where {$sqlTime}
group by gameId, userId
having concat_action not like '%credit%' AND concat_action not like '%cancel%';
EOD;

/*old query
 SELECT
    trans.*, group_concat(trans.`action`) all_action
FROM {$this->original_transactions_table} as trans
WHERE {$sqlTime}
GROUP BY trans.gameId, trans.userId
HAVING all_action not like '%credit%';
 */

        $params=[
            $dateFrom,
            $dateTo
		];

	    $this->CI->utils->debug_log('EVOLUTION SEAMLESS (getUnsettledRounds)', 'params',$params,'sql',$sql);
        return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    }

	public function checkBetStatus($data) {

        if(!isset($data['gameId']) || !isset($data['userId'])){
            return array('success'=>false, 'exists'=>false);
        }

        $gamePlatformId = $this->getPlatformCode();
        $this->CI->load->model(array('original_game_logs_model', 'seamless_missing_payout'));
        $this->original_transactions_table = $this->getTransactionsTable();
        $ispayoutexist = true;

        //check round if no refund
        $this->CI->db->from($this->original_transactions_table)
            ->where("gameId",$data['gameId'])
            ->where("action !=", 'debit')
            ->where("userId",$data['userId']);
        $ispayoutexist = $this->CI->original_game_logs_model->runExistsResult();

        if($ispayoutexist){
            return array('success'=>true, 'exists'=>$ispayoutexist);
        }

        $transTable=$this->getTransactionsTable();

        //save record to missing payout report
$sql = <<<EOD
SELECT
t.created_at transaction_date,
t.`action` transaction_type,
game_provider_auth.player_id,
t.gameId round_id,
t.id transaction_id,
t.transactionAmount amount,
t.transactionAmount deducted_amount,
gd.id as game_description_id,
gd.game_type_id,
t.external_uniqueid
FROM {$transTable} as t
left JOIN game_description as gd ON t.gameDetailsTableVid = gd.external_game_id and gd.game_platform_id=?
JOIN game_provider_auth ON t.userId = game_provider_auth.login_name and game_provider_auth.game_provider_id=?
WHERE
t.gameId = ? and t.`action` = 'debit' and t.userId=?
EOD;

        $params=[$this->getPlatformCode(), $this->getPlatformCode(), $data['gameId'], $data['userId']];

        $trans = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);

        if(empty($trans)){
            return array('success'=>false, 'exists'=>false);
        }

        foreach($trans as $insertData){
            $insertData['transaction_status'] = Game_logs::STATUS_PENDING;
            $insertData['game_platform_id'] = $this->getPlatformCode();
            $insertData['added_amount'] = 0;
            $insertData['status'] = Seamless_missing_payout::NOT_FIXED;
            $notes = [];
            $insertData['note'] = json_encode($notes);
            $result = $this->CI->original_game_logs_model->insertIgnoreRowsToOriginal('seamless_missing_payout_report',$insertData);
            if($result===false){
                $this->CI->utils->error_log('EVOLUTION SEAMLESS (checkBetStatus) Error insert missing payout', $insertData);
            }
        }

        if($this->enable_mm_channel_nofifications){

            //save data to seamless_missing_payout

            //check if transaction has no payout
            $adminUrl = $this->CI->utils->getConfig('admin_url');
            $message = "@all EVOLUTION Seamless to check missing Payout"."\n";
            $message = "Client: ".$adminUrl."\n";
            $message .= json_encode($data);

            $this->CI->load->helper('mattermost_notification_helper');

            $notif_message = array(
                array(
                    'text' => $message,
                    'type' => 'warning'
                )
            );
            sendNotificationToMattermost("EVOLUTION SEAMLESS SERVICE ($gamePlatformId)", $this->mm_channel, $notif_message, null);
        }

		return array('success'=>true, 'exists'=>$ispayoutexist);
	}

    function getFileExtension($filename)
    {
        $path_info = pathinfo($filename);
        return $path_info['extension'];
    }

	public function syncLostAndFound($token) {
        if(!$this->enable_sync_lost_and_found){
            return $this->returnUnimplemented();
        }

		$this->CI->load->model(array('original_game_logs_model', 'player_model'));

		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');

		$game_records_path = $this->getSystemInfo('game_records_path');


        $period = new DatePeriod(
            new DateTime( $startDate->format('Y-m-d') ),
            new DateInterval('P1D'),
            new DateTime( $endDate->format('Y-m-d') )
        );

        $periods = [];

        $success = true;
        foreach($period as $dateFolder){
            $periods[] = $dateFolder->format('Ymd');
        }
        if(empty($periods)){
            $periods[] =$startDate->format('Ymd');
        }

        foreach($periods as $dateFolder){
            $game_records_path_with_date = $game_records_path.$dateFolder;

            //check if folder exist
            if(!is_dir($game_records_path_with_date) || !file_exists($game_records_path_with_date)){
				continue; // if not file
			}
            $scanned_date_directory = array_diff(scandir($game_records_path_with_date), array('..', '.'));
            foreach($scanned_date_directory as $key){
                if(is_dir($key)){
                    continue; // if not file
                }

                $filename = $key;//no extension

                $fileFullDir = $game_records_path_with_date.'/'.$key; // get full directory of the file

				$file = file($fileFullDir); // get csv file
				$gameRecords = array_map('str_getcsv', $file);
				//echo "<pre>";print_r($gameRecords[0]);
				unset($gameRecords[0]); //unset first array element "header of csv"

				if ($gameRecords) {
                    foreach($gameRecords as $record){
                        $insertData = array();
                        $insertData['casino_id'] = $record[8];
                        $insertData['game_round_id'] = $record[9];
                        $insertData['started_at'] = $this->gameTimeToServerTime($record[14]);
                        $insertData['settled_at'] = $this->gameTimeToServerTime($record[13]);

                        $insertData['payout'] = $record[6];
                        $insertData['dealer'] = $record[11];
                        $insertData['result'] = null;
                        $insertData['game_type'] = $record[2];
                        $insertData['status'] = 'Resolved';
                        $insertData['currency'] = null;
                        $insertData['wager'] = $record[7];
                        $insertData['table'] = $record[4];
                        $insertData['participants'] = null;
                        $insertData['decisions'] = null;
                        $insertData['player_id'] = $record[3];

                        $insertData['player_bet_amount'] = $insertData['wager'];
                        $insertData['player_payout'] = $insertData['payout'];

                        $insertData['city'] = null;
                        $insertData['screen_name'] = $insertData['player_id'];
                        $insertData['casino_session_id'] = null;

                        $insertData['response_result_id'] = null;

                        $external_uniqueid=$insertData['player_id'].'-'.$insertData['table'].'_'.$insertData['game_round_id'];
                        $insertData['external_uniqueid'] = $external_uniqueid;

                        //check if data exist
                        $isExist = true;

                        $this->CI->db->select('*')->from($this->original_gamelogs_table)
                            ->where('external_uniqueid', $external_uniqueid);
                        $tmpRows = $this->CI->original_game_logs_model->runMultipleRowArray();

                        if(empty($tmpRows)){
                            $isExist = false;
                        }

                        if(!$isExist){
                            $this->CI->original_game_logs_model->insertIgnoreRowsToOriginal($this->original_gamelogs_table,$insertData);
                        }

                    }


				}
				//fclose($file);

            }


        }

		$success = true;
		return array('success' => $success);

	}

    public function fetchBetDetailLink($round_id) {
        $params = [
            'gameId' => $round_id,
            'gameProvider' => $this->game_provider,
            'casinoId' => $this->casino_key,
        ];

        $url = $this->generateUrl(self::API_queryBetDetailLink, $params);

        $result = [
            'success' => true,
            'url' => $url,
            'username' => $this->casino_key,
            'password' => $this->external_lobby_api_token,
        ];

        return $result;
    }

    public function queryBetDetailLink($player_username, $unique_id = null, $extra = []) {
        if ($this->force_bet_detail_default_format) {
            return parent::queryBetDetailLink($player_username, $unique_id, $extra);
        }

        if (!empty($extra['round_id'])) {
            $unique_id = $extra['round_id'];
        }

        $baseUrl = $this->utils->getBaseUrlWithHost();
        $path = site_url('/async/get_bet_detail_link_of_game_api/' . $this->getPlatformCode() . '/' . $player_username . '/' . $unique_id);
        $url = rtrim($baseUrl, '/') . $path;

        $result = [
            'success' => true,
            'url' => $url,
        ];

        return $result;
    }

    public function getTransactionsTable(){
        if(!$this->use_monthly_transactions_table){            
            return $this->original_seamless_wallet_transactions_table;
        }

        $date=new DateTime();
        $monthStr=$date->format('Ym');
        
        return $this->initGameTransactionsMonthlyTableByDate($monthStr);        
    }

	public function initGameTransactionsMonthlyTableByDate($yearMonthStr){
        if(!$this->use_monthly_transactions_table){            
            return $this->original_seamless_wallet_transactions_table;
        }

		$tableName=$this->original_seamless_wallet_transactions_table.'_'.$yearMonthStr;

		if (!$this->CI->utils->table_really_exists($tableName)) {
			try{
                $this->CI->load->model(['player_model']);
                $this->CI->player_model->runRawUpdateInsertSQL('create table '.$tableName.' like ' . $this->original_seamless_wallet_transactions_table);

			}catch(Exception $e){
				$this->CI->utils->error_log('create table failed: '.$tableName, $e);
                return null;
			}
		}
		return $tableName;
	}

    public function queryGameListFromGameProvider($extra = null) {
        $params = [
            'gameVertical' => $this->game_vertical,
            'gameProvider' => $this->game_provider,
        ];
    
        $this->method = self::METHOD_GET;
        $currentMethod = self::API_queryGameListFromGameProvider;
        $url = $this->generateUrl($currentMethod, $params);
    
        // list($response, $httpCode) = $this->customHttpCall2($url, "{$this->casino_key}:{$this->api_token}");
        list($response, $httpCode) = $this->customHttpCall2($url, "{$this->casino_key}:{$this->external_lobby_api_token}");

    
        $resultArr = json_decode($response, true);
        $success = ($httpCode >= 200 && $httpCode < 300 && !empty($resultArr));
    
        if ($success) {
            $result['games'] = $this->rebuildGameList($resultArr);
        } else {
            $result['error'] = 'Failed to retrieve game list or invalid response';
        }
    
        return [$success, $result];
    }

    private function customHttpCall2($url, $auth) {
    
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, $auth);
    
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \Exception("cURL error: {$error}");
        }
    
        curl_close($ch);
    
        return [$response, $httpCode];
    }

    protected function rebuildGameList($game=[])
    {
        $data = [];
        if (isset($game['players'])) {
            unset($game['players']);
        }
        if (isset($game['tables'])) {
            foreach ($game['tables'] as $key => $table) {
                if (isset($table['descriptions'])) {
                    unset($table['descriptions']);
                }
                if (isset($table['videoSnapshot'])) {
                    unset($table['videoSnapshot']);
                }
                if (isset($table['dealerHand'])) {
                    unset($table['dealerHand']);
                }
                if (isset($table['seatsTaken'])) {
                    unset($table['seatsTaken']);
                }
                if (isset($table['dealer'])) {
                    unset($table['dealer']);
                }
                if (isset($table['privateTableConfig'])) {
                    unset($table['privateTableConfig']);
                }
                if (isset($table['betLimits'])) {
                    unset($table['betLimits']);
                }
                if (isset($table['operationSchedules'])) {
                    unset($table['operationSchedules']);
                }
                if (isset($table['operationHours'])) {
                    unset($table['operationHours']);
                }
                if (isset($table['sitesAssigned'])) {
                    unset($table['sitesAssigned']);
                }
                if (isset($table['players'])) {
                    unset($table['players']);
                }
                if (isset($table['seats'])) {
                    unset($table['seats']);
                }
                if (isset($table['betBehind'])) {
                    unset($table['betBehind']);
                }
                if (isset($table['seatsLimit'])) {
                    unset($table['seatsLimit']);
                }
                if (isset($table['sitesBlocked'])) {
                    unset($table['sitesBlocked']);
                }
                if (isset($table['results'])) {
                    unset($table['results']);
                }
                if (isset($table['history'])) {
                    unset($table['history']);
                }
                if (isset($table['road'])) {
                    unset($table['road']);
                }
                $game['tables'][$key] = $table; // update the game tables after unsetting
            }
        }
    
        $data[] = $game;
  
        return $data;
    }

    public function convertTransactionAmount($amount) {
        if(!$this->truncate_converted_transaction_amount){
            return $amount;
        }

        // $currency = $this->CI->utils->getCurrentCurrency();
        // $precision = isset($currency['currency_decimals']) ? $currency['currency_decimals'] : 2;
        $precision = 2;// set static, seems the sub-wallet has fixed decimal places which are 2
        return bcdiv($amount, 1, $precision);
    }
}
/*end of file*/