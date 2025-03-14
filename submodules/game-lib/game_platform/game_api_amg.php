<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

/**
 * AMG Integration
 * OGP-17035
 *
 * @author  Erickson Qua
 */
class Game_api_amg extends Abstract_game_api
{
    const REQUEST_METHOD_POST = 'POST';
    const REQUEST_METHOD_GET = 'GET';
    const REQUEST_METHOD_PUT = 'PUT';
    const REQUEST_SUCCESS = 200;

    const TRANSACTION_TYPE_BET = 'Bet';
    const TRANSACTION_TYPE_PAYOUT = 'Payout';

    const SOURCE_PROVIDER = 'TPG';
    const SOURCE_ID = 'payment-processor';

    const HTTP_ERROR_INCORRECT_CREDENTIALS = 401;
    const HTTP_ERROR_INCORRECT_QUERY_PARAMETER = 400;
    const HTTP_ERROR_INCORRECT_PARAMETER_VALUE = 404;
    const HTTP_ERROR_SERVER_ERROR = 500;

    public $error_messages = array(
        self::HTTP_ERROR_INCORRECT_CREDENTIALS => 'Incorrect credentials/IP not whitelisted or Insufficient permissions',
        self::HTTP_ERROR_INCORRECT_QUERY_PARAMETER => 'Incorrect query parameter passed',
        self::HTTP_ERROR_INCORRECT_PARAMETER_VALUE => 'Incorrect value passed to query parameter',
        self::HTTP_ERROR_SERVER_ERROR => 'Server error'
    );

    public $uri_map = array(
        self::API_createPlayer => '/gameserver/rest/api/v5/players',
        self::API_login => '/gameserver/rest/api/v5/sessions',
        self::API_logout => '/gameserver/rest/api/v5/sessions',
        self::API_queryPlayerBalance => '/gameserver/wallet/payment-gateway/balance',
        self::API_depositToGame => '/gameserver/wallet/payment-gateway/deposit',
        self::API_withdrawFromGame => '/gameserver/wallet/payment-gateway/withdraw',
        self::API_queryForwardGame => '/gameserver/rest/api/v5/game',
        self::API_syncGameRecords => "/gameserver/rest/api/v5/{player_name}/rounds"
    );

    # Fields in original game_logs we want to detect changes for update
    const MD5_FIELDS_FOR_ORIGINAL=[
        'timestamp',
        'external_id',
        'transaction_type',
        'currency',
        'base_currency',
        'amount',
        'base_currency_amount',
        'last_cash_balance',
        'last_bonus_balance',
        'round_id',
        'player_id',
        'player_name',
        'round_ended',
        'round_start_time',
        'round_end_time',
        'game_id',
        'game_name',
        'response_result_id',
        'external_uniqueid',
    ];

    # Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS = [
        'amount',
    ];

    # Fields in game_logs we want to detect changes for merge, and when original game_logs.md5_sum is empty
    const MD5_FIELDS_FOR_MERGE=[
        'round',
        'username',
        'bet_amount',
        'start_at',
        'end_at',
        'game_code',
        'round_ended',
    ];

    # Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE=[
        'bet_amount',
        'valid_bet',
        'result_amount',
    ];

    public $original_gamelogs_table;
    private $currency;
    private $language;
    private $api_url;
    private $country;
    public $api_username;
    public $api_password;
    private $fraction_digits;
    private $scale;
    private $affiliate_tag;
    private $game_info_provider;

    private $request_method;

    public function __construct($data)
    {
        parent::__construct();
        $this->original_gamelogs_table = 'amg_game_logs';

        $this->currency = $this->getSystemInfo('currency', 'USD');
        $this->language = $this->getSystemInfo('language');
        $this->api_url = $this->getSystemInfo('url', 'https://kinggamingcasino-stage.tain.com');

        $this->lobby_url = $this->getSystemInfo('lobby_url', 'https://kinggamingcasino-stage.tain.com/lobby/cwl/html5/dev/init');
        $this->country = strtoupper($this->getSystemInfo('default_country', 'CN'));
        $this->api_username = $this->getSystemInfo('api_username', 'kinggaming');
        $this->api_password = $this->getSystemInfo('api_password', 'test');
        $this->fraction_digits = $this->getSystemInfo('fraction_digits', 2);
        $this->scale = $this->getSystemInfo('scale', 0);
        $this->affiliate_tag = $this->getSystemInfo('affiliate_tag', '');
        $this->game_info_provider = $this->getSystemInfo('game_info_provider', 'TAIN');
        $this->callback_credentials = $this->getSystemInfo('callback_credentials', []);
        $this->enabled_direct_merged = $this->getSystemInfo('enabled_direct_merged', true);
        $this->allow_manual_merged = $this->getSystemInfo('allow_manual_merged', false);
    }

    public function getPlatformCode()
    {
        return AMG_API;
    }

    protected function customHttpCall($ch, $params)
    {
        switch ($this->request_method) {
            case self::REQUEST_METHOD_POST:
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
                break;
            case self::REQUEST_METHOD_PUT:
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, self::REQUEST_METHOD_PUT);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
                break;
            case self::REQUEST_METHOD_GET:
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, self::REQUEST_METHOD_GET);
                break;
            default:
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
                break;
        }

        curl_setopt($ch, CURLOPT_USERPWD, $this->api_username . ":" . $this->api_password);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $this->utils->debug_log("AMG_API: (customHttpCall) Params:", $params);
    }

    public function generateUrl($apiName, $params)
    {
        $url = $this->api_url . $this->uri_map[$apiName];

        if($this->request_method == self::REQUEST_METHOD_GET) {
            $url .= '?' . http_build_query($params);
        }

        $this->debug_log('AMG_API GeneratedUrl: ', $apiName, $url, $params);

        return $url;
    }

    public function processResultBoolean($responseResultId, $resultArr, $statusCode)
    {
        $success = false;

        if($statusCode == self::REQUEST_SUCCESS) {
            $success = true;
        }
        else if(array_key_exists($statusCode, $this->error_messages)) {
            $this->utils->debug_log("============= AMG_API GOT ERROR =============", "RESPONSE RESULT ID: {$responseResultId}", "MESSAGE: {$this->error_messages[$statusCode]}", 'RESULT', $resultArr);
        }
        else {
            $this->utils->debug_log("============= AMG_API GOT ERROR =============", "RESPONSE RESULT ID: {$responseResultId}", "MESSAGE: Unknown http error code.", 'RESULT', $resultArr);
        }

        if (!$success) {
            $this->setResponseResultToError($responseResultId);
        }

        return $success;
    }

    public function login($playerName, $password = null)
    {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForLogin',
            'gameUsername' => $gameUsername,
        );

        $params = array(
            'playerId' => $gameUsername,
            'displayName' => $gameUsername,
            'currency' => array(
                'code' => $this->currency,
                'fractionDigits' => $this->fraction_digits,
                'scale' => $this->scale
            ),
            'country' => $this->country,
            'affiliateTag' => $this->affiliate_tag
        );

        $params = json_encode($params);

        $this->request_method = self::REQUEST_METHOD_POST;

        return $this->callApi(self::API_login, $params, $context);
    }

    public function processResultForLogin($params)
    {
        $statusCode = $this->getStatusCodeFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        $result = array();

        if ($success) {
            if (isset($resultArr['sessionKey'])) {
                $result['session_key'] = $resultArr['sessionKey'];
            }
        }

        return array($success, $result);
    }

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null)
    {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $playerId = $this->getPlayerIdFromUsername($playerName);
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForCreatePlayer',
            'gameUsername' => $gameUsername,
            'playerId' => $playerId
        );

        $params = array(
            'playerId' => $gameUsername,
            'displayName' => $gameUsername,
            'currency' => array(
                'code' => $this->currency,
                'fractionDigits' => $this->fraction_digits,
                'scale' => $this->scale
            ),
            'country' => $this->country,
            'affiliateTag' => $this->affiliate_tag
        );

        $params = json_encode($params);

        $this->request_method = self::REQUEST_METHOD_PUT;

        $this->debug_log('AMG_API Create: ', $params);
        return $this->callApi(self::API_createPlayer, $params, $context);
    }

    public function processResultForCreatePlayer($params)
    {
        $statusCode = $this->getStatusCodeFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $playerId = $this->getVariableFromContext($params, 'playerId');
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);

        $this->debug_log('AMG_API Create: ', $resultArr);
        $result = ['player' => $gameUsername, 'exists' => false];
        if($success){
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
            $result['exists'] = true;
        }

        return array($success, $result);
    }

    public function queryPlayerBalance($playerName)
    {
        $result_array = $this->login($playerName);
        $session_key = '';

        if (isset($result_array['session_key'])) {
            $session_key = $result_array['session_key'];
        } else {
            return ["success" => false];
        }

        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryPlayerBalance',
            'gameUsername' => $gameUsername
        );

        $params = array(
            'sessionKey' => $session_key,
            'validateSessionKey' => true,
            "playerId" => $gameUsername,
            'currency' => array(
                'code' => $this->currency,
                'fractionDigits' => $this->fraction_digits,
                'scale' => $this->scale
            )
        );

        $params = json_encode($params);

        $this->request_method = self::REQUEST_METHOD_POST;

        return $this->callApi(self::API_queryPlayerBalance, $params, $context);
    }

    public function processResultForQueryPlayerBalance($params)
    {
        $statusCode = $this->getStatusCodeFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        $result = [];

        if ($success) {
            $result['balance'] = $this->convertAmountToDB($resultArr['cashBalanceInMinorUnits']);
        }

        return array($success, $result);
    }

    private function formatDate($timestamp)
    {
        $timestamp = new DateTime($timestamp);
        return $timestamp->format('Y-m-d\TH:i:s.000\Z');
    }


    public function depositToGame($playerName, $amount, $transfer_secure_id = null)
    {
        $result_array = $this->login($playerName);
        $session_key = '';

        if (isset($result_array['session_key'])) {
            $session_key = $result_array['session_key'];
        } else {
            return ["success" => false];
        }

        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $external_transaction_id = empty($transfer_secure_id) ? 'AMG' . uniqid() : $transfer_secure_id;

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForDepositToGame',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'external_transaction_id' => $external_transaction_id,
            'amount' => $this->dBtoGameAmount($amount)
        );

        $datetime_utc = $this->formatDate($this->gameTimeToServerTime($this->utils->getNowForMysql()));
        $params = array(
            'sessionKey' => $session_key,
            'validateSessionKey' => true,
            'playerId' => $gameUsername,
            'source' => array(
                'provider' => self::SOURCE_PROVIDER,
                'id' => self::SOURCE_ID
            ),
            'items' => array(
                array(
                    'transactionId' => $external_transaction_id,
                    'transactionTime' => $datetime_utc,
                    'currency' => array(
                        'code' => $this->currency,
                        'fractionDigits' => $this->fraction_digits,
                        'scale' => $this->scale
                    ),
                    'amountInMinorUnits' => $this->dBtoGameAmount($amount)
                )
            )
        );

        $params = json_encode($params);

        $this->request_method = self::REQUEST_METHOD_POST;

        return $this->callApi(self::API_depositToGame, $params, $context);
    }

    public function processResultForDepositToGame($params)
    {
        $statusCode = $this->getStatusCodeFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $amount = $this->getVariableFromContext($params, 'amount');
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
        $resultArr = $this->getResultJsonFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);

        $result = array(
            'response_result_id' => $responseResultId,
            'external_transaction_id' => $external_transaction_id,
            'transfer_status' => parent::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id' => parent::REASON_UNKNOWN
        );

        if ($success) {
            $result['transfer_status'] = parent::COMMON_TRANSACTION_STATUS_APPROVED;
            $result['didnot_insert_game_logs'] = true;
        } else {
            $result['transfer_status'] = parent::COMMON_TRANSACTION_STATUS_DECLINED;
            $result['reason_id'] = $this->getReasons($statusCode);
        }

        return array($success, $result);
    }

    private function getReasons($statusCode)
    {
        switch ($statusCode) {
            case 400:
                return self::REASON_INCOMPLETE_INFORMATION;
                break;
            case 401:
                return self::REASON_INVALID_KEY;
                break;
            case 404:
                return self::REASON_INVALID_TRANSACTION_ID;
                break;
            case 409:
                return self::REASON_INVALID_TRANSFER_AMOUNT;
                break;
            case 500:
                return self::REASON_GAME_PROVIDER_INTERNAL_PROBLEM;
                break;

            default:
                return self::REASON_UNKNOWN;
                break;
        }
    }

    public function withdrawFromGame($playerName, $amount, $transfer_secure_id = null)
    {
        $result_array = $this->login($playerName);
        $session_key = '';

        if (isset($result_array['session_key'])) {
            $session_key = $result_array['session_key'];
        } else {
            return ["success" => false];
        }

        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $external_transaction_id = empty($transfer_secure_id) ? 'AMG' . uniqid() : $transfer_secure_id;

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForWithdrawFromGame',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'external_transaction_id' => $external_transaction_id,
            'amount' => $amount
        );

        $datetime_utc = $this->formatDate($this->gameTimeToServerTime($this->utils->getNowForMysql()));
        $params = array(
            'sessionKey' => $session_key,
            'validateSessionKey' => true,
            'playerId' => $gameUsername,
            'source' => array(
                'provider' => self::SOURCE_PROVIDER,
                'id' => self::SOURCE_ID
            ),
            'items' => array(
                array(
                    'transactionId' => $external_transaction_id,
                    'transactionTime' => $datetime_utc,
                    'currency' => array(
                        'code' => $this->currency,
                        'fractionDigits' => $this->fraction_digits,
                        'scale' => $this->scale
                    ),
                    'amountInMinorUnits' => $this->dBtoGameAmount($amount)
                )
            )
        );

        $params = json_encode($params);

        $this->request_method = self::REQUEST_METHOD_POST;

        return $this->callApi(self::API_withdrawFromGame, $params, $context);
    }

    public function processResultForWithdrawFromGame($params)
    {
        $statusCode = $this->getStatusCodeFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $amount = $this->getVariableFromContext($params, 'amount');
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
        $resultArr = $this->getResultJsonFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);

        $result = array(
            'response_result_id' => $responseResultId,
            'external_transaction_id' => $external_transaction_id,
            'transfer_status' => parent::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id' => parent::REASON_UNKNOWN
        );

        if ($success) {
            $result['transfer_status'] = parent::COMMON_TRANSACTION_STATUS_APPROVED;
            $result['didnot_insert_game_logs'] = true;
        } else {
            $result['transfer_status'] = parent::COMMON_TRANSACTION_STATUS_DECLINED;
            $result['reason_id'] = $this->getReasons($statusCode);
        }

        return array($success, $result);
    }

    private function _getGameLaunchUrl($game_id)
    {
        $context = array(
            'callback_obj' => $this,
            'callback_method' => '_processResultForGetGameLaunchUrl'
        );

        $params['provider'] = $this->game_info_provider;
        $params['gameId'] = $game_id;

        $this->request_method = self::REQUEST_METHOD_GET;

        return $this->callApi(self::API_queryForwardGame, $params, $context);
    }

    protected function _processResultForGetGameLaunchUrl($params)
    {
        $statusCode = $this->getStatusCodeFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        $result = array();
        $result['use_default_language'] = false;

        if ($success) {

            if (isset($resultArr['result'][0])) {
                $resultArr = $resultArr['result'][0];
                $result['game_launch_url'] = $resultArr['launchUrlTemplate'];
            }
        }

        return array($success, $result);
    }

    private function getLauncherLanguage($lang)
    {
        $lang = strtolower($lang);
        switch ($lang) {
            case "chinese":
            case "zh-cn":
            case "cn-zh":
            case "zh":
            case "cn":
            case "ch":
            case LANGUAGE_FUNCTION::INT_LANG_CHINESE:
                return "zh";
            case "thai":
            case "th-th":
            case "th":
            case "tha":
            case LANGUAGE_FUNCTION::INT_LANG_THAI:
                return "th";
            case "korean":
            case "ko":
            case "kr":
            case "kor":
            case LANGUAGE_FUNCTION::INT_LANG_KOREAN:
                return "ko";
            default:
                return "en";
        }
    }

    public function queryForwardGame($playerName, $extra = null)
    {

        $cache_key = 'AMG_LAUNCH_URL-' . $extra['game_code'];
        
        $game_launch_array = array();
        $game_launch_url = '';
        $language = $this->language ?: $extra['language'];

        $game_launch_url = $this->utils->getTextFromCache($cache_key);
        if($game_launch_url === false) {
            $game_launch_array = $this->_getGameLaunchUrl($extra['game_code']);
            if($game_launch_array['success']) {
                $this->utils->saveTextToCache($cache_key, $game_launch_array['game_launch_url']);
                $game_launch_url = $game_launch_array['game_launch_url'];
            }
            else {
                return ["success" => false];
            }
        }

        $language = $this->getLauncherLanguage($language);

        if ($extra['game_mode'] == 'real') {

            $result_array = $this->login($playerName);
            $session_key = '';
            if ($result_array['success']) {
                $session_key = $result_array['session_key'];
            } else {
                return ["success" => false];
            }

            $game_launch_url = str_replace('{sessionKey}', $session_key, $game_launch_url);
        }
        else {
            $game_launch_url = str_replace('{sessionKey}', '', $game_launch_url);
        }

        $game_launch_url = str_replace('{lang}', $language, $game_launch_url);

        return ['success' => true,'url' => $game_launch_url];
    }

    public function callback($method, $params) {
        if($method == 'game') {

            $responseResultId = $extra['response_result_id'] = $this->CI->response_result->saveResponseResult(
                $this->getPlatformCode(),
                Response_result::FLAG_NORMAL,
                self::API_syncGameRecords,
                json_encode([]),
                json_encode($params),
                200,
                null,
                null
            );

            $gameRecords = [];
            if(array_key_exists('events', $params)) {
                
                $gameRecords = $params['events'];
            }

            $this->rebuildGameRecords($gameRecords, $extra);

            $this->CI->load->model(array('original_game_logs_model'));
            list($insertRows, $updateRows) = $this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                $this->original_gamelogs_table,
                $gameRecords,
                'external_uniqueid',
                'external_uniqueid',
                self::MD5_FIELDS_FOR_ORIGINAL,
                'md5_sum',
                'id',
                self::MD5_FLOAT_AMOUNT_FIELDS
            );

            $this->CI->utils->debug_log('after process available rows', count($gameRecords), count($insertRows), count($updateRows));

            unset($gameRecords);

            $result = array(
                'data_count' => 0,
                'data_count_insert'=> 0,
                'data_count_update'=> 0
            );

            $rounds = array();
            #for testing rounds
            if(array_key_exists('rounds', $params)) {
                $rounds = (array)$params['rounds'];
            }
            
            if (!empty($insertRows)) {
                #get unique rounds on insert
                $rounds = array_unique(array_merge(array_column($insertRows, 'round_id'),$rounds));
                $result['data_count_insert'] += $result['data_count'] += $this->updateOrInsertOriginalGameLogs(
                    $insertRows,
                    'insert',
                    ['responseResultId'=>$responseResultId]
                );
            }
            unset($insertRows);

            if (!empty($updateRows)) {
                #get unique rounds on update
                $rounds = array_unique(array_merge(array_column($updateRows, 'round_id'),$rounds));
                $result['data_count_update'] += $result['data_count'] += $this->updateOrInsertOriginalGameLogs(
                    $updateRows,
                    'update',
                    ['responseResultId'=>$responseResultId]
                );
            }
            unset($updateRows);

            #check config
            if($this->enabled_direct_merged){
                if(!empty($rounds)){
                    $rows = $this->queryByRounds($rounds);
                    $enabled_game_logs_unsettle=false;
                    return $this->roundSyncMergeToGameLogs(
                        $this,
                        $rows,
                        [$this, 'makeParamsForInsertOrUpdateGameLogsRow'],
                        [$this, 'preprocessOriginalRowForGameLogs'],
                        $enabled_game_logs_unsettle
                    );
                }
            }
            return $result;
        }

    }

    public function roundSyncMergeToGameLogs(
            $api,
            $rows,
            callable $makeParamsForInsertOrUpdateGameLogsRow,
            callable $preprocessOriginalRowForGameLogs,
            $enabled_game_logs_unsettle){

        #default values
        $rlt = array('success' => true);
        $cnt = 0;
        $insertRows=null;
        $updateRows=null;

        if(!empty($rows)){
            #enable update, always update possible changes.
            $reUpdate = true;
            list($insertRows, $updateRows)=$this->CI->original_game_logs_model->generateInsertAndUpdateForGameLogs($rows, 'external_uniqueid', $reUpdate, $this->getPlatformCode());
        }
        unset($rows);

        if(!empty($insertRows)){
            $this->CI->utils->debug_log('preprocessOriginalRowForGameLogs insertRows', count($insertRows));
            foreach ($insertRows as &$row) {
                $preprocessOriginalRowForGameLogs($row);
            }
        }

        if(!empty($updateRows)){
            $this->CI->utils->debug_log('preprocessOriginalRowForGameLogs updateRows', count($updateRows));
            foreach ($updateRows as &$row) {
                $preprocessOriginalRowForGameLogs($row);
            }
        }

        if($enabled_game_logs_unsettle && (!empty($insertRows) || !empty($updateRows)) ){
            $this->CI->game_logs->processUnsettleGameLogs($api, $makeParamsForInsertOrUpdateGameLogsRow,
                $insertRows, $updateRows);
        }

        if(!empty($insertRows)){
            $rlt['success']=$this->commonUpdateOrInsertGameLogs($api, $makeParamsForInsertOrUpdateGameLogsRow,
                'insert', $insertRows, $cnt);
        }
        unset($insertRows);

        if($rlt['success'] && !empty($updateRows)){
            $rlt['success']=$this->commonUpdateOrInsertGameLogs($api, $makeParamsForInsertOrUpdateGameLogsRow,
                'update', $updateRows, $cnt);
        }
        unset($updateRows);

        $this->CI->utils->debug_log('syncMergeToGameLogs monitor', 'count', $cnt);
        $rlt['count']=$cnt;
        return $rlt;
    }

    public function queryByRounds($roundIds)
    {
        $roundIds = implode(",",$roundIds);
        $sqlWhere="amg.round_id in ({$roundIds})";
        $sql = <<<EOD
            SELECT
                amg.id as sync_index,
                min(amg.response_result_id) as response_result_id,
                amg.round_id as round_id ,
                amg.player_name as username,
                sum(IF(transaction_type = "Bet",amount,0)) as valid_bet,
                sum(IF(transaction_type = "Bet",amount,0)) as bet_amount,
                sum(IF(transaction_type = "Payout",amount,0)) as payout_amount,
                (sum(IF(transaction_type = "Payout",amount,0))) -  (sum(IF(transaction_type = "Bet",amount,0))) as result_amount,
                min(amg.round_start_time) as start_at,
                max(amg.round_end_time) as end_at,
                min(amg.round_start_time) as bet_at,
                amg.transaction_type as transaction_type, 
                max(amg.last_cash_balance) as after_balance,
                amg.game_id as game_code,
                max(amg.round_ended) as round_ended,
                amg.md5_sum,
                CONCAT(amg.round_id, "-", amg.game_id, "-", amg.player_name) as external_uniqueid ,
                game_provider_auth.player_id,
                gd.id as game_description_id,
                gd.game_name as game_description_name,
                gd.game_type_id
            FROM amg_game_logs as amg
            LEFT JOIN game_description as gd ON amg.game_id = gd.external_game_id AND gd.game_platform_id = ?
            LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
            JOIN game_provider_auth ON amg.player_name = game_provider_auth.login_name
            AND game_provider_auth.game_provider_id= ?
            WHERE {$sqlWhere}
            GROUP BY amg.round_id
            
EOD;

        $params=[
            $this->getPlatformCode(),
            $this->getPlatformCode(),
        ];
        return  $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);

    }

    // /*
    //     Game provider provided Game List API
    //  */
    // public function getGameList(
    //     $game_type_id = null,
    //     $where = null,
    //     $limit = null,
    //     $offset = null,
    //     $full_game_list = null
    // ) {

    //     $lang = $this->processPlayerLanguageForParams("English");
    //     $context = array(
    //         'callback_obj' => $this,
    //         'callback_method' => 'processResultForGetGameList',
    //     );

    //     $platformtype = [
    //                      self::DESKTOP_PLATFORM_TYPE,
    //                      self::MOBILE_PLATFORM_TYPE,
    //                      self::MINIGAME_PLATFORM_TYPE
    //                     ];
    //     $gameListArr = [];

    //     $result = $this->login($this->gamelist_testplayer);
    //     if ($result['success']) {
    //         $params = array(
    //                 'lang' => $lang,
    //                 'authtoken' => $result['authtoken'],
    //                 'iconres' => self::ICON_RESOLUTION_343,
    //             );
    //         $this->request_method = self::REQUEST_METHOD_GET;

    //         foreach ($platformtype as $value) {
    //             $params['platformtype'] = $value;
    //             $result = $this->callApi(self::API_getGameProviderGamelist, $params, $context);
    //             if ($result['success']) {
    //                 array_push($gameListArr, $result['games']);
    //             }
    //         }
    //     }
    //     return $gameListArr;
    // }

    // public function processResultForGetGameList($params)
    // {
    //     $statusCode = $this->getStatusCodeFromParams($params);
    //     $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
    //     $resultArr = $this->getResultJsonFromParams($params);
    //     $responseResultId = $this->getResponseResultIdFromParams($params);
    //     $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
    //     return array($success, ['games'=>$resultArr['games']]);
    // }


    public function syncOriginalGameLogs($token)
    {
        return $this->returnUnimplemented();
    }

    private function rebuildGameRecords(&$gameRecords)
    {
        $newGR =[];
        foreach ($gameRecords as $i => $gr) {
            $newGR[$i]['timestamp'] = isset($gr['timestamp']) ? $this->gameTimeToServerTime($gr['timestamp']) : null;
            $newGR[$i]['external_id'] = isset($gr['id']) ? $gr['id'] : null;
            $newGR[$i]['transaction_type'] = isset($gr['transactionType']) ? $gr['transactionType'] : null;
            $newGR[$i]['currency'] = isset($gr['currency']) ? $gr['currency'] : null;
            $newGR[$i]['base_currency'] = isset($gr['baseCurrency']) ? $gr['baseCurrency'] : null;
            $newGR[$i]['amount'] = isset($gr['amount']) ? $gr['amount'] : null;
            $newGR[$i]['base_currency_amount'] = isset($gr['baseCurrencyAmount']) ? $gr['baseCurrencyAmount'] : null;
            $newGR[$i]['last_cash_balance'] = isset($gr['lastCashBalance']) ? $gr['lastCashBalance'] : null;
            $newGR[$i]['last_bonus_balance'] = isset($gr['lastBonusBalance']) ? $gr['lastBonusBalance'] : null;

            $newGR[$i]['round_id'] = isset($gr['roundId']) ? $gr['roundId'] : null;
            $newGR[$i]['player_id'] = isset($gr['playerId']) ? $gr['playerId'] : null;
            $newGR[$i]['player_name'] = isset($gr['playerName']) ? $gr['playerName'] : null;
            $newGR[$i]['round_ended'] = isset($gr['roundEnded']) ? $gr['roundEnded'] : null;
            $newGR[$i]['round_start_time'] = isset($gr['roundStartTime']) ? $this->gameTimeToServerTime(date('Y-m-d H:i:s', $gr['roundStartTime'] / 1000)) : null;
            $newGR[$i]['round_end_time'] = isset($gr['roundEndTime']) ? $this->gameTimeToServerTime(date('Y-m-d H:i:s', $gr['roundEndTime'] / 1000)) : null;
            $newGR[$i]['game_id'] = isset($gr['gameId']) ? $gr['gameId'] : null;
            $newGR[$i]['game_name'] = isset($gr['gameName']) ? $gr['gameName'] : null;
            $newGR[$i]['response_result_id'] = isset($gr['response_result_id']) ? $gr['response_result_id'] : null;

            #OGP-18910 :modify unique id for the chance of multiple payout or bet request 
            $newGR[$i]['external_uniqueid'] = isset($gr['roundId']) && isset($gr['transactionType']) ? $gr['transactionType'] . '-' . $gr['roundId'] . '-' . $gr['id']: null;
        }

        $gameRecords = $newGR;
    }

    private function updateOrInsertOriginalGameLogs($rows, $update_type, $additionalInfo = [])
    {
        $dataCount = 0;
        if (!empty($rows)) {
            foreach ($rows as $key => $record) {
                if ($update_type=='update') {
                    $this->CI->original_game_logs_model->updateRowsToOriginal($this->original_gamelogs_table, $record);
                } else {
                    unset($record['id']);
                    $this->CI->original_game_logs_model->insertRowsToOriginal($this->original_gamelogs_table, $record);
                }
                $dataCount++;
                unset($record);
            }
        }
        return $dataCount;
    }

    public function syncMergeToGameLogs($token)
    {
        if($this->enabled_direct_merged && !$this->allow_manual_merged){
            return $this->returnUnimplemented();
        }
        
        $enabled_game_logs_unsettle=true;

        return $this->commonSyncMergeToGameLogs(
            $token,
            $this,
            [$this, 'queryOriginalGameLogs'],
            [$this, 'makeParamsForInsertOrUpdateGameLogsRow'],
            [$this, 'preprocessOriginalRowForGameLogs'],
            $enabled_game_logs_unsettle
        );
    }

    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time = false)
    {
        $sqlTime='`amg`.`round_end_time` >= ?
        AND `amg`.`round_end_time` <= ?';

        if ($use_bet_time) {
            $sqlTime='`amg`.`round_start_time` >= ?
        AND `amg`.`round_start_time` <= ?';
        }

        $sql = <<<EOD
            SELECT
                amg.id as sync_index,
                amg.response_result_id,
                amg.round_id as round_id ,
                amg.player_name as username,
                amg.amount,
                amg.round_start_time as start_at,
                amg.round_end_time as end_at,
                amg.round_start_time as bet_at,
                amg.transaction_type as transaction_type,
                amg.last_cash_balance as after_balance,
                amg.game_id as game_code,
                amg.round_ended,
                amg.md5_sum,
                game_provider_auth.player_id,
                gd.id as game_description_id,
                gd.game_name as game_description_name,
                gd.game_type_id
            FROM $this->original_gamelogs_table as amg
            LEFT JOIN game_description as gd ON amg.game_id = gd.external_game_id AND gd.game_platform_id = ?
            LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
            JOIN game_provider_auth ON amg.player_name = game_provider_auth.login_name
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
        $game_logs = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $this->rebuildOriginalForMerge($game_logs);
    }

    private function rebuildOriginalForMerge($game_logs) {
        $new_game_logs = [];
        foreach($game_logs as $game_log) {
            if($game_log['transaction_type'] == self::TRANSACTION_TYPE_BET) {
                if(!array_key_exists($game_log['round_id'], $new_game_logs)) {
                $new_game_logs[$game_log['round_id']] = $game_log;
                $new_game_logs[$game_log['round_id']]['bet_amount'] = $game_log['amount'];
                $new_game_logs[$game_log['round_id']]['valid_bet'] = $game_log['amount'];
                $new_game_logs[$game_log['round_id']]['result_amount'] = 0 - $game_log['amount'];
                $new_game_logs[$game_log['round_id']]['after_balance'] = $game_log['after_balance'];
                } 
            }
            else {
                if(array_key_exists($game_log['round_id'], $new_game_logs)) {
                    // $new_game_logs[$game_log['round_id']]['result_amount'] = $game_log['after_balance'] - $new_game_logs[$game_log['round_id']]['after_balance'];

                    #OGP-18910 : modify result, add payout on existing record array
                    $new_game_logs[$game_log['round_id']]['result_amount'] +=  $game_log['amount'];

                    $new_game_logs[$game_log['round_id']]['round_ended'] = $game_log['round_ended'];
                    $new_game_logs[$game_log['round_id']]['end_at'] = $game_log['end_at'];
                    $new_game_logs[$game_log['round_id']]['after_balance'] = $game_log['after_balance'];
                }
                else {
                    $bet = $this->getBetRecord($game_log['round_id']);
                    if(!empty($bet)) {
                        $bet = $bet[0];
                        $new_game_logs[$game_log['round_id']] = $bet;
                        $new_game_logs[$game_log['round_id']]['bet_amount'] = $bet['amount'];
                        $new_game_logs[$game_log['round_id']]['valid_bet'] = $bet['amount'];

                        // $new_game_logs[$game_log['round_id']]['result_amount'] = $game_log['after_balance'] - $new_game_logs[$game_log['round_id']]['after_balance'];

                        #OGP-18910 : modify result, net = payout - bet
                        $new_game_logs[$game_log['round_id']]['result_amount'] = $game_log['amount'] - $bet['amount'];

                        $new_game_logs[$game_log['round_id']]['round_ended'] = $game_log['round_ended'];
                        $new_game_logs[$game_log['round_id']]['end_at'] = $game_log['end_at'];
                        $new_game_logs[$game_log['round_id']]['after_balance'] = $game_log['after_balance'];
                    }
                    else {
                        $new_game_logs[$game_log['round_id']] = $game_log;
                        $new_game_logs[$game_log['round_id']]['bet_amount'] = 0;
                        $new_game_logs[$game_log['round_id']]['valid_bet'] = 0;
                        $new_game_logs[$game_log['round_id']]['result_amount'] = $game_log['amount'];

                        $new_game_logs[$game_log['round_id']]['round_ended'] = 1;
                        $new_game_logs[$game_log['round_id']]['end_at'] = $game_log['end_at'];
                        $new_game_logs[$game_log['round_id']]['after_balance'] = $game_log['after_balance'];
                    }
                }
            }
            $new_game_logs[$game_log['round_id']]['external_uniqueid'] = $new_game_logs[$game_log['round_id']]['round_id'] . '-' . $new_game_logs[$game_log['round_id']]['game_code'] . '-' . $new_game_logs[$game_log['round_id']]['username'];
        }


        $this->utils->debug_log("AMG_API: (TOTAL ROWS)", count($new_game_logs));
        return $new_game_logs;
    }



    public function makeParamsForInsertOrUpdateGameLogsRow(array $row)
    {
        $bet_details = [];

        $extra = [
            'table' =>  $row['round_id'],
        ];

        if (empty($row['md5_sum'])) {
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow(
                $row,
                self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE
            );
        }

        $this->utils->debug_log("AMG_API: (makeParamsForInsertOrUpdateGameLogsRow)", $row);
        return [
            'game_info' => [
                'game_type_id' => $row['game_type_id'],
                'game_description_id' => $row['game_description_id'],
                'game_code' => $row['game_code'],
                'game_type' => $row['game_type_id'],
                'game' => $row['game_description_name']
            ],
            'player_info' => [
                'player_id' => $row['player_id'],
                'player_username' => $row['username']
            ],
            'amount_info' => [
                'bet_amount' => $row['valid_bet'],
                'result_amount' => $row['result_amount'],
                'bet_for_cashback' => $row['valid_bet'],
                'real_betting_amount' => $row['bet_amount'],
                'win_amount' => null,
                'loss_amount' => null,
                'after_balance' => $row['after_balance'],
            ],
            'date_info' => [
                'start_at' => $row['bet_at'],
                'end_at' => $row['end_at'] ?: $row['bet_at'],
                'bet_at' => $row['bet_at'],
                'updated_at' => $this->CI->utils->getNowForMysql(),
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => $row['status'],
            'additional_info' => [
                'has_both_side' => 0,
                'external_uniqueid' => $row['external_uniqueid'],
                'round_number' => $row['round_id'],
                'md5_sum' => $row['md5_sum'],
                'response_result_id' => $row['response_result_id'],
                'sync_index' => $row['sync_index'],
                'bet_type' => null
            ],
            'bet_details' => $bet_details,
            'extra' => $extra,
            //from exists game logs
            'game_logs_id'=>isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];
    }

    public function preprocessOriginalRowForGameLogs(array &$row)
    {
        if (empty($row['game_description_id'])) {
            $unknownGame = $this->getUnknownGame($this->getPlatformCode());
            list($game_description_id,$game_type_id) = $this->getGameDescriptionInfo($row, $unknownGame);
            $row['game_description_id']= $game_description_id;
            $row['game_type_id'] = $game_type_id;
        }
        // $row['bet_amount'] = $this->dBtoGameAmount($row['bet_amount']);
        // $row['valid_bet'] = $this->dBtoGameAmount($row['valid_bet']);
        // $row['result_amount'] = $this->dBtoGameAmount($row['result_amount']);
        // $row['after_balance'] = $this->dBtoGameAmount($row['after_balance']);
        $row['status'] = $row['round_ended'] ? Game_logs::STATUS_SETTLED : Game_logs::STATUS_PENDING;

    }



    public function getBetRecord($round_id)
    {

        $sql = <<<EOD
            SELECT
                amg.id as sync_index,
                amg.response_result_id,
                amg.round_id as round_id ,
                amg.player_name as username,
                amg.amount,
                amg.round_start_time as start_at,
                amg.round_end_time as end_at,
                amg.round_start_time as bet_at,
                amg.transaction_type as transaction_type,
                amg.last_cash_balance as after_balance,
                amg.game_id as game_code,
                amg.round_ended,
                amg.external_uniqueid,
                amg.md5_sum,
                game_provider_auth.player_id,
                gd.id as game_description_id,
                gd.game_name as game_description_name,
                gd.game_type_id
            FROM $this->original_gamelogs_table as amg
            LEFT JOIN game_description as gd ON amg.game_id = gd.external_game_id AND gd.game_platform_id = ?
            LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
            JOIN game_provider_auth ON amg.player_name = game_provider_auth.login_name
            AND game_provider_auth.game_provider_id=?
            WHERE
            round_id = ? and transaction_type = 'Bet'
EOD;

        $params=[
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            $round_id
        ];
        return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    }

    private function getGameDescriptionInfo($row, $unknownGame)
    {
        $game_description_id = null;
        $game_name = str_replace(
            "알수없음",
            $row['game_code'],
            str_replace(
                "不明",
                $row['game_code'],
                str_replace("Unknown", $row['game_code'], $unknownGame->game_name)
            )
        );
        $external_game_id = $row['game_code'];
        $extra = array('game_code' => $external_game_id,'game_name' => $game_name);

        $game_type_id = $unknownGame->game_type_id ? $unknownGame->game_type_id : null;
        $game_type = $unknownGame->game_name ? $unknownGame->game_name : self::TAG_CODE_UNKNOWN_GAME;

        return $this->processUnknownGame(
            $game_description_id,
            $game_type_id,
            $external_game_id,
            $game_type,
            $external_game_id,
            $extra,
            $unknownGame
        );
    }

    public function queryTransaction($transactionId, $extra) {
        return $this->returnUnimplemented();
    }
}