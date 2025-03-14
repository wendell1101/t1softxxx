<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

/**
* Game Provider: BGSOFT
* Game Type: Mini Games
* Wallet Type: Transfer
*
* @category Game_platform
* @version not specified
* @copyright 2013-2022 tot
* @interecordator @emil.php.ph

**/

class Game_api_bgsoft extends Abstract_game_api
{
    public $api_url;
    public $merchant_code;
    public $secure_key;
    public $currency;
    public $home_link;
    public $force_disable_home_link;

    const SUCCESS_CODE = 0;
    const DUPLICATE_USERNAME = 8;
    const INVALID_EXTERNAL_TRANSACTION_ID = 18;
    const CODE_INVALID_AUTH_TOKEN=4;

    const START_PAGE = 1;

    //**Game API URI */
    const GAME_API_URI = '/gameapi/v2';

    //**Action Type For Transfer */
    const ACTION_TYPE_DEPOSIT = 'deposit';
    const ACTION_TYPE_WITHDRAW = 'withdraw';

    //**Methods */
    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';

    const API_queryForwardGameDemo = "queryForwardGameDemo";


    const URI_MAP = [
        self::API_generateToken      => 'generate_token',
        self::API_createPlayer       => 'create_player',
        self::API_depositToGame      => 'transfer_player_fund',
        self::API_withdrawFromGame   => 'transfer_player_fund',
        self::API_queryForwardGame   => 'chain/query_game_launcher',
        self::API_queryTransaction   => 'query_transaction',
        self::API_queryPlayerBalance => 'query_player_balance',
        self::API_syncGameRecords => 'chain/query_game_history',
        self::API_isPlayerExist => 'query_player_balance',
        self::API_queryForwardGameDemo   => 'chain/query_game_launcher',
    ];

    const MD5_FLOAT_AMOUNT_FIELDS = [
        'bet_amount',
        'payout_amount'
    ];

    const MD5_FIELDS_FOR_ORIGINAL = [
        'uniqueid',
        'username',
        'game_code',
        'bet_time',
        'payout_time',
        'game_finish_time',
        'bet_amount',
        'payout_amount',
        'period',
        'bet_status',
        'bet_details',
        'result_details'
    ];

    const MD5_FIELDS_FOR_MERGE = [
        'game_type_id',
        'game_description_id',
        'game_code',
        'player_id',
        'bet_amount',
        'result_amount',
        'start_at',
        'bet_at',
        'end_at',
        'updated_at',
        'external_uniqueid'
    ];

    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE = [
        'bet_amount',
        'result_amount',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->api_url = $this->getSystemInfo('url');
        $this->merchant_code = $this->getSystemInfo('merchant_code', 'dd35aPO0bd186dc6ace6We2e0fb48s70');
        $this->secure_key = $this->getSystemInfo('secure_key', 'dd35aPO0bd186dc6ace6We2e0fb48s70');
        $this->sign_key = $this->getSystemInfo('sign_key', 'dd35aPO0bd186dc6ace6We2e0fb48s70');
        $this->game_api_uri = $this->getSystemInfo('game_api_uri', self::GAME_API_URI);
        $this->method = self::METHOD_POST;
        $this->originalTable = 'bgsoft_game_logs';
        $this->language = $this->getSystemInfo('language','');
        $this->force_language = $this->getSystemInfo('force_language', '');
        $this->currency = $this->getSystemInfo('currency','BRL');
        $this->home_link = $this->getSystemInfo('home_link','');
        $this->force_disable_home_link = $this->getSystemInfo('force_disable_home_link', false);

        $this->game_launch_map = $this->getSystemInfo('game_launch_map', ['chain'=>['3001','3002','3003']]);
    }

    public function getPlatformCode()
    {
        return BGSOFT_GAME_API;
    }

    public function getSignString($fields, $except=['sign'])
    {
        $params=[];
        foreach ($fields as $key => $value) {
            if( in_array($key, $except) || is_array($value)){
                continue;
            }
            $params[$key]=$value;
        }

        if(empty($params)){
            return '';
        }

        ksort($params);

        return implode('', array_values($params));

    }

    public function generateSignatureByParams($params, $except=['sign'])
    {
        $signString=$this->getSignString($params, $except);

        if(empty($signString)){
            return '';
        }

        $sign=strtolower(sha1($signString.$this->sign_key));

        return $sign;
    }

    public function generateUrl($apiName, $params) {
        $params['sign'] = $this->generateSignatureByParams($params);

        $apiUri = self::URI_MAP[$apiName];
        if (self::METHOD_POST == $this->method) {
            $url = $this->api_url .$this->game_api_uri.'/'. $apiUri;
        }else{
            if($apiName==self::API_queryForwardGame || $apiName==self::API_queryForwardGameDemo){
                if(isset($params['game_code'])){
                    foreach($this->game_launch_map as $key => $value){
                        if(is_array($value) && in_array($params['game_code'], $value)){
                            $apiUri = $key.'/query_game_launcher';
                        }
                    }
                }
            }

            $url = $this->api_url .$this->game_api_uri.'/'. $apiUri . '?' . http_build_query($params);
            
        }

        $this->CI->utils->debug_log('apiName', $apiName, 'url', $url);
        $this->CI->utils->debug_log('BGSOFT params', $params);
        return $url;
    }

    protected function customHttpCall($ch, $params) {
        $params['sign'] = $this->generateSignatureByParams($params);

        if (self::METHOD_POST == $this->method) {

            $data_json = json_encode($params);

            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS,$data_json);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        }
    }

    public function processResultBoolean($responseResultId, $resultArr, $playerName = null)
    {
        $success = false;

        if(isset($resultArr['code']) && $resultArr['code'] === self::SUCCESS_CODE)
        {
            $success = true;
        }

        $fake_code_invalid_auth_token=$this->getValueFromApiConfig('fake_code_invalid_auth_token', false);

        if ($fake_code_invalid_auth_token || !$success)
        {
            // if error is 4 InvalidAuthToken
            if( $fake_code_invalid_auth_token || (isset($resultArr['code']) && $resultArr['code']==self::CODE_INVALID_AUTH_TOKEN) ){
                // clear token cache, so next time will generate new token
                $this->CI->utils->debug_log('BGSOFT API clear token cache');
                $this->cancelTokenCache();
            }
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('BGSOFT API got error ', $responseResultId, 'result', $resultArr);
		}

		return $success;
	}

    public function getAvailableApiToken(){
        return $this->getCommonAvailableApiToken(function(){
           return $this->generateToken();
        });
    }

    public function generateToken(){

        $context = array(
            'callback_obj'    => $this,
            'callback_method' => 'processResultForGenerateToken',
        );

        $params = array(
            'merchant_code' =>  $this->merchant_code,
            'secure_key'    =>  $this->secure_key,
        );

        $this->method = self::METHOD_POST;

        return $this->callApi(self::API_generateToken, $params, $context);
    }

    public function processResultForGenerateToken($params)
    {
        $resultArr = $this->getResultJsonFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr);

        if($success){
            if(isset($resultArr['detail']['auth_token'])){
                $api_token = $resultArr['detail']['auth_token'];
                $timeout = isset($resultArr['detail']['timeout']) ? intval($resultArr['detail']['timeout'])-30 : 3600;
                $api_token_timeout_datetime = $this->CI->utils->formatDateTimeForMysql(
                    new DateTime('+'.$timeout.' seconds'));

                $result['api_token'] = $api_token;
                $result['api_token_timeout_datetime'] = $api_token_timeout_datetime;

            }else{

                $success=false;
            }
        }

        return [$success, $result];
    }

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null)
    {
        parent::createPlayer($playerName, $playerId, $password, $email, $extra);
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $token = $this->getAvailableApiToken();

        if(empty($token)){
            return ['success'=>false, 'error_message'=>'no auth token'];
        }


        $context = array(
            'callback_obj'    => $this,
            'callback_method' => 'processResultForCreatePlayer',
            'playerName'      => $playerName,
            'gameUsername'    => $gameUsername,
            'playerId'        => $playerId,
        );

        $params = array(
            'auth_token'    => $token,
            'merchant_code' => $this->merchant_code,
            'username'      => $gameUsername
        );

        return $this->callApi(self::API_createPlayer, $params, $context);
    }

    public function processResultForCreatePlayer($params)
    {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $playerId = $this->getVariableFromContext($params, 'playerId');
        $success = $this->processResultBoolean($responseResultId, $resultArr);
        $result=null;

        if($success){
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
        }else{
            //if player exist
            if(isset($resultArr['code']) && $resultArr['code']==self::DUPLICATE_USERNAME){
                $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
                $success=true;
                $result=['user_exists'=>true];
            }
        }

        return array($success,$result);
    }

    public function depositToGame($playerName, $amount, $transfer_secure_id=null)
    {
        $type = self::ACTION_TYPE_DEPOSIT;
        return $this->transferCredit($playerName, $amount, $type, $transfer_secure_id);
    }

    public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null)
    {
        $type = self::ACTION_TYPE_WITHDRAW;
        return $this->transferCredit($playerName, $amount, $type, $transfer_secure_id);
    }

    public function transferCredit($playerName, $amount, $type, $transfer_secure_id)
    {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $playerId = $this->getPlayerIdFromUsername($playerName);
        $amount = $this->dBtoGameAmount($amount);
        $external_transaction_id = $transfer_secure_id;
        $token = $this->getAvailableApiToken();

        $context = array(
            'callback_obj'            => $this,
            'callback_method'         => 'processResultForTransferCredit',
            'playerName'              => $playerName,
            'gameUsername'            => $gameUsername,
            'playerId'                => $playerId,
            'amount'                  => $amount,
            'type'                    => $type,
            'external_transaction_id' => $external_transaction_id,
        );

        $params = array(
            'auth_token'        => $token,
            'merchant_code'     => $this->merchant_code,
            'username'          => $gameUsername,
            'action_type'       => $type,
            'amount'            => $amount,
            'external_trans_id' => $external_transaction_id
        );

        $this->method = self::METHOD_POST;

        return $this->callApi( $type == self::ACTION_TYPE_DEPOSIT ? self::API_depositToGame : self::API_withdrawFromGame , $params, $context);
    }

    public function processResultForTransferCredit($params){
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $playerId = $this->getVariableFromContext($params, 'playerId');
        $type = $this->getVariableFromContext($params, 'type');
        $amount = $this->getVariableFromContext($params, 'amount');
        $resultArr = $this->getResultJsonFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr,$gameUsername);
        $statusCode = $this->getStatusCodeFromParams($params);
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');

        $result = [
            'response_result_id' => $responseResultId,
            'external_transaction_id'=>$external_transaction_id,
            'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id'=>self::REASON_UNKNOWN
        ];

        if ($success) {

            $result['didnot_insert_game_logs']=true;
            $result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
        }else{
            $error_code = isset($resultArr['code']) ? $resultArr['code'] : '';
            if((in_array($statusCode, $this->other_status_code_treat_as_success) || in_array($error_code, $this->other_status_code_treat_as_success)) && $this->treat_500_as_success_on_deposit){
                $result['reason_id']=self::REASON_GAME_PROVIDER_INTERNAL_PROBLEM;
                $result['transfer_status']=self::COMMON_TRANSACTION_STATUS_UNKNOWN;
                $success=true;
            }
            if($error_code && is_int($resultArr['code'])){
                $result['transfer_status']=self::COMMON_TRANSACTION_STATUS_DECLINED;
            }
        }

        return array($success, $result);
    }

    public function queryPlayerBalance($playerName)
    {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $token = $this->getAvailableApiToken();

        $context = array(
            'callback_obj'    => $this,
            'callback_method' => 'processResultForQueryPlayerBalance',
            'playerName'      => $playerName,
            'gameUsername'    => $gameUsername,
        );

        $params = array(
            'auth_token'    => $token,
            'merchant_code' => $this->merchant_code,
            'username'      => $gameUsername
        );

        $this->method = self::METHOD_GET;

        return $this->callApi(self::API_queryPlayerBalance, $params, $context);
    }

    public function processResultForQueryPlayerBalance($params)
    {
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);
        $result = array();

        if($success){
            $result['balance'] = $this->convertAmountToDB($resultArr['detail']['game_platform_balance']);
        }

        return array($success, $result);
	}

    public function queryForwardGame($playerName, $extra)
    {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $game_code = isset($extra['game_code']) ? $extra['game_code'] : null;

        $gameMode = isset($extra['game_mode'])?$extra['game_mode']:null;

        $token = $this->getAvailableApiToken();

        $context = array(
            'callback_obj'    => $this,
            'callback_method' => 'processResultForQueryForwardGame',
            'playerName'      => $playerName,
            'gameUsername'    => $gameUsername
        );

        if(isset($extra['home_link']) && !empty($extra['home_link'])) {
            $this->home_link = $extra['home_link'];
        }

        $language = $this->language;
        if(isset($extra['language'])){
            $language = $extra['language'];
        }

        if($this->force_language && !empty($this->force_language)){
            $language = $this->force_language;
        }

        $language = $this->getLauncherLanguage($language);

        $params = array(
            'auth_token'    => $token,
            'merchant_code' => $this->merchant_code,
            'game_code'     => $game_code,
            'username'      => $gameUsername,
            'language'      => $language
        );


        if(!empty($this->home_link)){
            $params['home_link'] = $this->home_link;
        }

        if(isset($extra['extra']['disable_home_link']) && $extra['extra']['disable_home_link']) {
            unset($params['home_link']);
        }

        if($this->force_disable_home_link){
            unset($params['home_link']);
        }

		if(in_array($gameMode, $this->demo_game_identifier)){
            $params['trial'] = 'true';
            $params['username'] = $this->getSystemInfo('demo_username',uniqid());
        }

        $this->method = self::METHOD_GET;

        return $this->callApi(self::API_queryForwardGame, $params, $context);
    }

    public function processResultForQueryForwardGame($params)
    {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr);
        $result = array();

        if($success && isset($resultArr['detail']['game_url'])){

            $result['url'] = $resultArr['detail']['game_url'];
        }

        return array($success, $result);
    }

    public function getLauncherLanguage($language) {
        $lang = '';

        $language = strtolower($language);
        switch($language)
        {
            case Language_function::INT_LANG_ENGLISH:
            case 'en-us':
                    $lang = 'en-US';
                    break;
            case Language_function::INT_LANG_CHINESE:
            case 'cn':
            case 'zh-cn':
                    $lang = 'zh-CN';
                    break;
            case Language_function::INT_LANG_VIETNAMESE:
            case 'vi-vi':
            case 'vi-vn':
                    $lang = 'vi-VN';
                    break;
            case Language_function::INT_LANG_PORTUGUESE:
            case 'pt':
            case 'pt-br':
            case 'pt-pt':
                $lang = 'pt-BR';
                break;
            case Language_function::INT_LANG_INDIA:
                case 'hi-in':
                $lang = 'hi-IN';
                break;
            case Language_function::INT_LANG_THAI:
                case 'th-th':
                $lang = 'th-TH';
                break;
            case Language_function::INT_LANG_SPANISH:
                case 'es-us':
                $lang = 'es-US';
                break;
            case "jp":
            case "ja-JP":
            case "ja-en":
            case "jp-jp":
            case "ja-jp":
                $lang = 'ja-JP';
                break;
            case "kr":
            case "ko-KR":
            case "ko-kr":
                $lang = 'ko-KR';
                break;
            default:
                $lang = 'zh-CN';
                break;
        }

        return $lang;
	}

    public function isPlayerExist($playerName){
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $playerId  = $this->getPlayerIdInGameProviderAuth($gameUsername);
        $token = $this->getAvailableApiToken();
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForIsPlayerExist',
            'playerName' => $playerName,
            'playerId' => $playerId
        );

        $params = array(
            'auth_token'    => $token,
            'merchant_code' => $this->merchant_code,
            'username'      => $gameUsername
        );

        $this->method = self::METHOD_GET;

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

    public function queryTransaction($transactionId, $extra)
    {
        $token = $this->getAvailableApiToken();
        $playerId = $extra['playerId'];

        $context = array(
            'callback_obj'            => $this,
            'callback_method'         => 'processResultForQueryTransaction',
            'external_transaction_id' => $transactionId,
            'playerId'                => $playerId
        );

        $params = array(
            'auth_token'        => $token,
            'merchant_code'     => $this->merchant_code,
            'external_trans_id' => $transactionId
        );

        $this->method = self::METHOD_GET;

        return $this->callApi(self::API_queryTransaction, $params, $context);
    }

    public function processResultForQueryTransaction($params)
    {
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr);

        $result = array(
			'response_result_id'     => $responseResultId,
			'external_transaction_id'=> $external_transaction_id,
			'status'                 => self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id'              => self::REASON_UNKNOWN
		);

        if($success){
            $result['status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
        }else{
            if(isset($resultArr['code']) && $resultArr['code'] == self::INVALID_EXTERNAL_TRANSACTION_ID){
                $result['status']=self::COMMON_TRANSACTION_STATUS_DECLINED;
            }
        }

        return array($success, $result);
    }

    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time)
    {
        $sqlTime='bgsoft_game_logs.updated_at >= ? and bgsoft_game_logs.updated_at <= ?';
        if($use_bet_time){
          $sqlTime='bgsoft_game_logs.bet_time >= ? and bgsoft_game_logs.bet_time <= ?';
        }

        $this->CI->utils->debug_log('BGSOFT sqlTime ===>', $sqlTime);

        $sql = <<<EOD
    SELECT
      bgsoft_game_logs.id as sync_index,
      bgsoft_game_logs.uniqueid,
      bgsoft_game_logs.username as player_username,
      bgsoft_game_logs.game_code,
      bgsoft_game_logs.bet_time as start_at,
      bgsoft_game_logs.bet_time as bet_at,
      bgsoft_game_logs.game_finish_time as end_at,
      bgsoft_game_logs.bet_amount AS bet_amount,
      bgsoft_game_logs.payout_amount as result_amount,
      bgsoft_game_logs.period,
      bgsoft_game_logs.bet_status,
      bgsoft_game_logs.bet_details,
      bgsoft_game_logs.result_details,

      bgsoft_game_logs.external_uniqueid,
      bgsoft_game_logs.response_result_id,
      bgsoft_game_logs.created_at,
      bgsoft_game_logs.updated_at,
      bgsoft_game_logs.md5_sum,
      game_provider_auth.player_id,
      game_description.id AS game_description_id,
      game_description.game_type_id
    FROM
      bgsoft_game_logs
      join game_provider_auth on game_provider_auth.login_name=bgsoft_game_logs.username and game_provider_auth.game_provider_id=?
      LEFT JOIN game_description
        ON (
          bgsoft_game_logs.game_code = game_description.external_game_id
          AND game_description.game_platform_id = ?
        )
    WHERE

      {$sqlTime}

EOD;
        $params=[$this->getPlatformCode(), $this->getPlatformCode(),
          $dateFrom,$dateTo];

        return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    }

    public function syncBgsoft($startDate, $endDate, $page, $extra)
    {
        $token = $this->getAvailableApiToken();
        $game_code = isset($extra['game_code']) ? $extra['game_code'] : null;

        if(empty($token)){
            return ['success'=>false, 'error_message'=>'no auth token'];
        }

        $context = array(
            'callback_obj'    => $this,
            'callback_method' => 'processResultForSyncBgsoft',
            'startDate'       => $startDate,
            'endDate'         => $endDate
        );

        $params = array(
            'auth_token'    => $token,
            'merchant_code' => $this->merchant_code,
            'game_code'     => $game_code,
            'from'          => $startDate,
            'to'            => $endDate,
            'page_number'   => $page
        );

        $this->method = self::METHOD_GET;

        return $this->callApi(self::API_syncGameRecords, $params, $context);
    }

    public function processResultForSyncBgsoft($params)
    {
        $this->CI->load->model(array('original_game_logs_model'));

        $responseResultId = $this->getResponseResultIdFromParams($params);
        $from = $this->getVariableFromContext($params, 'from');
        $to = $this->getVariableFromContext($params, 'to');
        $resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr);
        $data = !empty($resultArr['detail']['game_history']) ? $resultArr['detail']['game_history'] : null;
        $extra['response_result_id'] = $responseResultId;

        $result = array('data_count'=>0);

        if($success && !empty($data)){
            $gameRecords = $this->rebuildGameRecords($data, $extra);

            if(!empty($gameRecords)){

                list($insertRows, $updateRows)=$this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                    'bgsoft_game_logs',
                    $gameRecords,
                    'uniqueid',
                    'external_uniqueid',
                    self::MD5_FIELDS_FOR_ORIGINAL,
                    'md5_sum',
                    'id',
                    self::MD5_FLOAT_AMOUNT_FIELDS);

                    unset($gameRecords);

                if (!empty($insertRows)) {
                    $result['data_count'] += $this->updateOrInsertOriginalGameLogs($insertRows, 'insert');
                }
                unset($insertRows);

                if (!empty($updateRows)) {
                    $result['data_count'] += $this->updateOrInsertOriginalGameLogs($updateRows, 'update');
                }

                unset($updateRows);

            }
            $result['total_pages']=isset($detail['total_pages']) ? $detail['total_pages'] : null;
            $result['current_page']=isset($detail['current_page']) ? $detail['current_page'] : null;
            $result['total_rows_current_page']=isset($detail['total_rows_current_page']) ? $detail['total_rows_current_page'] : null;
            unset($detail);
        }else{
            $success=false;
        }
            unset($resultArr);

        return array($success, $result);
    }

    public function rebuildGameRecords($gameRecords, $extra)
    {
        if(!empty($gameRecords)){
            foreach($gameRecords as $index => $record){
                $data["uniqueid"] = isset($record["uniqueid"]) ? $record["uniqueid"] : null;
                $data['username'] = isset($record['username']) ? $record['username'] : null;
                $data['game_code'] = isset($record['game_code']) ? $record['game_code'] : null;
                $data['bet_time'] = isset($record['bet_time']) ? $this->gameTimeToServerTime(date("Y-m-d H:i:s", $record['bet_time'])) : null;
                $data['payout_time'] = isset($record['payout_time']) ? $this->gameTimeToServerTime(date("Y-m-d H:i:s", $record['payout_time'])) : null;
                $data['game_finish_time'] = isset($record['game_finish_time']) ? $this->gameTimeToServerTime(date("Y-m-d H:i:s", $record['game_finish_time'])) : null;
                $data['bet_amount'] = isset($record['bet_amount']) ? $record['bet_amount'] : null;
                $data['payout_amount'] = isset($record['payout_amount']) ? $record['payout_amount'] : null;
                $data['period'] = isset($record['period']) ? $record['period'] : null;
                $data['bet_status'] = isset($record['status']) ? $record['status'] : null;
                $data['bet_details'] = isset($record['bet_details']) ? json_encode($record['bet_details']) : null;
                $data['result_details'] = isset($record['result_details']) ? json_encode($record['result_details']) : null;

                //extra info from SBE
                $data["external_uniqueid"] = isset($record["uniqueid"]) ? $record["uniqueid"] : null;
                $data['response_result_id'] = isset($extra['response_result_id']) ? $extra['response_result_id'] : null;
                $dataRecords[] = $data;
            }

            return $dataRecords;
        }
    }

    private function updateOrInsertOriginalGameLogs($data, $queryType,  $additionalInfo=[])
    {
        $dataCount = 0;
        if(!empty($data))
        {
            foreach ($data as $record)
            {
                if ($queryType == 'update')
                {
                    $record['updated_at'] = $this->CI->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->updateRowsToOriginal($this->originalTable, $record);
                }else{
                    unset($record['id']);
                    $record['created_at'] = $record['updated_at'] = $this->CI->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->insertRowsToOriginal($this->originalTable, $record);
                }
                $dataCount++;
                unset($record);
            }
        }

        return $dataCount;
    }

    public function syncPaginate($startDate, $endDate, $page, &$rows_count=0)
    {
        $this->CI->utils->debug_log('start syncPaginate================',$startDate, $endDate, $page);

        $data_count = 0;
        $success=true;
        $done=false;

        while(!$done) {

            $sync_results = [
                'syncBgsoft' => $this->syncBgsoft($startDate, $endDate, $page, $extra=null),
            ];

            foreach($sync_results as $sync_method_key => $sync_result) {
                $rlt = $sync_result;
                if($rlt['success']) {

                    if(isset($rlt['total_rows_current_page'])) {
                        $rows_count += $rlt['total_rows_current_page'];
                    }

                    if(isset($rlt['data_count'])) {
                        $data_count += $rlt['data_count'];
                    }

                    $this->CI->utils->debug_log("sync game logs api result for {$sync_method_key} ------------------>", $rlt);

                    if($rlt['total_pages'] > $rlt['current_page']) {
                        $page = $rlt['current_page'] + 1;
                        $this->CI->utils->debug_log($sync_method_key . ' not done ================', $rlt['total_pages'], $rlt['current_page']);
                    }else{
                        $done = true;
                        $this->CI->utils->debug_log($sync_method_key . ' done ===================', $rlt['total_pages'], $rlt['current_page']);
                    }

                    $success=true;
                }else{
                    $success=false;
                    $done=true;
                    $this->CI->utils->error_log($sync_method_key . 'sync game logs api error', $rlt);
                }
            }

            $result = [
                'success' => $success,
                'data_count' => $data_count,
                'page' => $page,
                'rows_count' => $rows_count
            ];

            $this->CI->utils->debug_log('Overall sync game logs api result ------------------>', $result);
        }

        return $success;
    }

    public function syncOriginalGameLogs($token = false)
    {
        $startDate = clone $this->getValueFromSyncInfo($token, 'dateTimeFrom');
        $endDate = clone $this->getValueFromSyncInfo($token, 'dateTimeTo');
        $startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
        $endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));
        $startDate->modify($this->getDatetimeAdjust());

        $startDateStr=$startDate->format('Y-m-d H:i:s');
        $endDateStr=$endDate->format('Y-m-d H:i:s');
        $page = self::START_PAGE;

        $rows_count=0;
        $success= $this->syncPaginate( $startDateStr, $endDateStr, $page, $rows_count );

        $this->CI->utils->debug_log('result rows_count', $rows_count);

        return array('success'=>$success, 'rows_count'=>$rows_count);

    }

    public function syncMergeToGameLogs($token)
    {
        $enabled_game_logs_unsettle = true;
        return $this->commonSyncMergeToGameLogs($token,
            $this,
            [$this, 'queryOriginalGameLogs'],
            [$this, 'makeParamsForInsertOrUpdateGameLogsRow'],
            [$this, 'preprocessOriginalRowForGameLogs'],
            $enabled_game_logs_unsettle);
	}

    public function makeParamsForInsertOrUpdateGameLogsRow(array $row)
    {


        if(empty($row['md5_sum'])){
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
        }

        $row['result_amount'] = $row['result_amount'] - $row['bet_amount'];

        if(isset($row['end_at']))
        {
            if($row['end_at'] == "0000-00-00 00:00:00")
            {
                $end_at = $row['start_at'];
            }else{
                $end_at = $row['end_at'];
            }
        }else{
            $end_at = $row['start_at'];
        }

        $bet_details = null;
        if(isset($row['bet_details'])){
            $bet_details = json_decode($row['bet_details'], TRUE);
        }

        $bet_amount = isset($row['bet_amount']) ? $this->gameAmountToDB($row['bet_amount']) : 0;

        return [
            'game_info' => [
                'game_type_id'          => isset($row['game_type_id']) ? $row['game_type_id'] : null,
                'game_description_id'   => isset($row['game_description_id']) ? $row['game_description_id'] : null,
                'game_code'             => isset($row['game_code']) ? $row['game_code'] : null,
                'game_type'             => isset($row['game_code']) ? $row['game_code'] : null,
                'game'                  => isset($row['game_code']) ? $row['game_code'] : null
            ],
            'player_info' => [
                'player_id'             => isset($row['player_id']) ? $row['player_id'] : null,
                'player_username'       => isset($row['username']) ? $row['username'] : null
            ],
            'amount_info' => [
                'bet_amount'            => $bet_amount,
                'result_amount'         => isset($row['result_amount']) ? $this->gameAmountToDB($row['result_amount']) : 0,
                'bet_for_cashback'      => $bet_amount,
                'real_betting_amount'   => $bet_amount,
                'win_amount'            => null,
                'loss_amount'           => null,
                'after_balance'         => null, // not implemented
            ],
            'date_info' => [
                'start_at'              => isset($row['bet_at']) ? $row['bet_at'] : null,
                'end_at'                => $end_at,
                'bet_at'                => isset($row['bet_at']) ? $row['bet_at'] : null,
                'updated_at'            => isset($row['updated_at']) ? $row['updated_at'] : null
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => Game_logs::STATUS_SETTLED,
            'additional_info' => [
                'has_both_side'         => 0,
                'external_uniqueid'     => isset($row['external_uniqueid']) ? $row['external_uniqueid'] : null,
                'round_number'          => isset($row['external_uniqueid']) ? $row['external_uniqueid'] : null,
                'md5_sum'               => isset($row['md5_sum']) ? $row['md5_sum'] : null,
                'response_result_id'    => isset($row['response_result_id']) ? $row['response_result_id'] : null,
                'sync_index'            => isset($row['sync_index']) ? $row['sync_index'] : null,
                'bet_type'              => null
            ],
            'bet_details' => $bet_details,
            'extra' => null,
            // existing game logs
            'game_logs_id' => isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id' => isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];
    }

    public function preprocessOriginalRowForGameLogs(array &$row)
    {
        if (empty($row['game_description_id'])) {
            $unknownGame = $this->getUnknownGame($this->getPlatformCode());
            list($game_description_id,$game_type_id) = $this->getGameDescriptionInfo($row,$unknownGame);
            $row['game_description_id']= $game_description_id;
            $row['game_type_id'] = $game_type_id;

        }

        $row['status'] = Game_logs::STATUS_SETTLED;
    }

    private function getGameDescriptionInfo($row, $unknownGame)
    {
        $game_description_id = null;
		$external_game_id = $row['game_code'];
        $extra = array('game_code' => $external_game_id,'game_name' => $row['game_name']);

        $game_type_id = $unknownGame->game_type_id ? $unknownGame->game_type_id : null;
        $game_type = $unknownGame->game_name ? $unknownGame->game_name : self::TAG_CODE_UNKNOWN_GAME;

		return $this->processUnknownGame(
			$game_description_id, $game_type_id,
			$external_game_id, $game_type, $external_game_id, $extra,
			$unknownGame);
    }

    public function getApiSignKey(){
        return $this->sign_key;
    }

    public function getCurrency(){
        return $this->currency;
    }

    public function relatedActionsMap($trans_type) {
        $actions_map = [
            'bet' => Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET,
            'payout' => Wallet_model::REMOTE_WALLET_ACTION_TYPE_PAYOUT,
            'refund' => Wallet_model::REMOTE_WALLET_ACTION_TYPE_REFUND,
        ];

        return !empty($actions_map[$trans_type]) ? $actions_map[$trans_type] : null;
    }
}
//end of class
