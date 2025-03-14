<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

/**
 * Game Provider: CMD
 * Game Type: Sports
 * Wallet Type: Seamless
 *
 * @category Game_platform
 * @version not specified
 * @copyright 2013-2022 tot
 * @integrator @melvin.php.ph

    Related File
    -routes.php
    -cmd_seamless_service_api.php
 **/

class Game_api_cmd_seamless extends Abstract_game_api {
    // default
    public $http_method;
    public $api_url;
    public $language;
    public $force_language;
    public $currency;
    public $prefix_for_username;
    public $conversion;
    public $precision;
    public $arithmetic_name;
    public $adjustment_precision;
    public $adjustment_conversion;
    public $adjustment_arithmetic_name;
    public $game_api_player_blocked_validate_api_methods;
    public $original_seamless_game_logs_table;
    public $original_seamless_wallet_transactions_table;
    public $game_seamless_service_logs_table;
    public $save_game_seamless_service_logs;
    public $sync_time_interval;
    public $sleep_time;
    public $enable_sync_original_game_logs;
    public $use_transaction_data;
    public $game_provider_gmt;
    public $game_provider_date_time_format;
    public $get_usec;
    public $use_transaction_bet_time;
    public $enable_merging_rows;

    // additional
    public $partner_code;
    public $partner_key;
    public $web_game_launch_url;
    public $mobile_game_launch_url;
    public $new_mobile_game_launch_url;
    public $use_new_mobile_game_launch_url;
    public $template_name;
    public $view;
    public $cipher_algo;
    public $is_login_entry;
    public $fix_username_limit;
    public $minimum_user_length;
    public $maximum_user_length;
    public $default_fix_name_length;
    public $cmd_game_code;
    public $time_type;
    public $query_bet_record_by;
    public $update_bet_round_id;
    public $allowed_negative_balance;

    const SEAMLESS_GAME_API_NAME = 'CMD_SEAMLESS_GAME_API';

    const HTTP_METHOD_GET = 'GET';
    const HTTP_METHOD_POST = 'POST';

    const RESPONSE_CODE_SUCCESS = 0;
    const RESPONSE_CODE_USER_ALREADY_EXISTS = -98;

    const API_METHOD_DEDUCT_BALANCE = 'DeductBalance';
    const API_METHOD_UPDATE_BALANCE = 'UpdateBalance';

    const FLAG_NOT_UPDATED = 0;
    const FLAG_UPDATED_FOR_GAME_LOGS = 1;
    const FLAG_UPDATED = 2;
    const FLAG_RETAIN = 3;

    const QUERY_BET_RECORD_BY_DATE = 'date';
    const QUERY_BET_RECORD_BY_VERSION = 'version';

    const API_syncGameRecordsByVersion = 'betrecord';
    const API_syncGameRecordsByDate = 'betrecordbydate';

    const URI_MAP = [
        self::API_createPlayer => 'createmember',
        self::API_isPlayerExist => 'exist',
        self::API_queryForwardGame => '/auth.aspx',
        self::API_syncGameRecordsByVersion => 'betrecord',
        self::API_syncGameRecordsByDate => 'betrecordbydate',
    ];

    const MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS = [
        'status',
        'bet_amount',
        'win_amount',
        'result_amount',
        'flag_of_updated_result',
        
        // additional
        'game_code',
        'round_id',
    ];

    const MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS = [
        'bet_amount',
        'win_amount',
        'result_amount',
    ];

    const MD5_FIELDS_FOR_MERGE_FROM_TRANS = [
        'player_id',
        'before_balance',
        'after_balance',
        'game_code',
        'transaction_type',
        'status',
        'response_result_id',
        'external_uniqueid',
        'start_at',
        'bet_at',
        'end_at',
        'created_at',
        'updated_at',
        'transaction_id',
        'round_id',
        'bet_amount',
        'real_betting_amount',
        'win_amount',
        'result_amount',
        'flag_of_updated_result',
        'wallet_adjustment_status',
    ];

    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE_FROM_TRANS = [
        'amount',
        'before_balance',
        'after_balance',
        'bet_amount',
        'real_betting_amount',
        'result_amount',
    ];

    public function __construct() {
        parent::__construct();
        $this->CI->load->model(['original_game_logs_model']);

        // default
        $this->http_method = self::HTTP_METHOD_GET;
        $this->api_url = $this->getSystemInfo('url');
        $this->language = $this->getSystemInfo('language');
        $this->force_language = $this->getSystemInfo('force_language', '');
        $this->currency = $this->getSystemInfo('currency');
        $this->prefix_for_username = $this->getSystemInfo('prefix_for_username');
        $this->conversion = $this->getSystemInfo('conversion', 1);
        $this->precision = $this->getSystemInfo('precision', 4);
        $this->arithmetic_name = $this->getSystemInfo('arithmetic_name', 'multiplication');
        $this->adjustment_precision = $this->getSystemInfo('adjustment_precision', $this->precision);
        $this->adjustment_conversion = $this->getSystemInfo('adjustment_conversion', $this->conversion);
        $this->adjustment_arithmetic_name = $this->getSystemInfo('adjustment_arithmetic_name', 'division');
        $this->game_api_player_blocked_validate_api_methods = $this->getSystemInfo('game_api_player_blocked_validate_api_methods', []);
        $this->original_seamless_game_logs_table = $this->getSystemInfo('original_seamless_game_logs_table', 'cmd_seamless_game_logs');
        $this->original_seamless_wallet_transactions_table = $this->getSystemInfo('original_seamless_wallet_transactions_table', 'cmd_seamless_wallet_transactions');
        $this->game_seamless_service_logs_table = $this->getSystemInfo('game_seamless_service_logs_table', 'cmd_seamless_service_logs');
        $this->save_game_seamless_service_logs = $this->getSystemInfo('save_game_seamless_service_logs', true);
        $this->sync_time_interval = $this->getSystemInfo('sync_time_interval', '+0 hours'); //minutes/hours/days
        $this->sleep_time = $this->getSystemInfo('sleep_time', '20'); //seconds
        $this->enable_sync_original_game_logs = $this->getSystemInfo('enable_sync_original_game_logs', false);
        $this->use_transaction_data = $this->getSystemInfo('use_transaction_data', true);
        $this->game_provider_gmt = $this->getSystemInfo('game_provider_gmt', '+0 hours');
        $this->game_provider_date_time_format = $this->getSystemInfo('game_provider_date_time_format', 'Y-m-d H:i:s');
        $this->get_usec = $this->getSystemInfo('get_usec', true);
        $this->use_transaction_bet_time = $this->getSystemInfo('use_transaction_bet_time', false);
        $this->enable_merging_rows = $this->getSystemInfo('enable_merging_rows', true);

        // additional
        $this->partner_code = $this->getSystemInfo('partner_code');
        $this->partner_key = $this->getSystemInfo('partner_key');
        $this->web_game_launch_url = $this->getSystemInfo('web_game_launch_url');
        $this->mobile_game_launch_url = $this->getSystemInfo('mobile_game_launch_url');
        $this->new_mobile_game_launch_url = $this->getSystemInfo('new_mobile_game_launch_url');
        $this->use_new_mobile_game_launch_url = $this->getSystemInfo('use_new_mobile_game_launch_url', true);
        $this->template_name = $this->getSystemInfo('template_name', 'aliceblue');
        $this->view = $this->getSystemInfo('view', 'v1');
        $this->cipher_algo = $this->getSystemInfo('cipher_algo', 'AES-128-CBC');
        $this->is_login_entry = $this->getSystemInfo('is_login_entry', true);
        $this->cmd_game_code = $this->getSystemInfo('cmd_game_code', 'cmd_sports');
        $this->time_type = $this->getSystemInfo('time_type', 1);
        $this->query_bet_record_by = $this->getSystemInfo('query_bet_record_by', self::QUERY_BET_RECORD_BY_VERSION);
        $this->update_bet_round_id = $this->getSystemInfo('update_bet_round_id', false);
        $this->allowed_negative_balance = $this->getSystemInfo('allowed_negative_balance', false);

        // fix exceed game username length
        $this->fix_username_limit = $this->getSystemInfo('fix_username_limit', true);
        $this->minimum_user_length = $this->getSystemInfo('minimum_user_length', 4);
        $this->maximum_user_length = $this->getSystemInfo('maximum_user_length', 20);
        $this->default_fix_name_length = $this->getSystemInfo('default_fix_name_length', 8);
        $this->append_agent_prefix = $this->getSystemInfo('append_agent_prefix', false);
    }

    public function getPlatformCode() {
        return CMD_SEAMLESS_GAME_API;
    }

    public function isSeamLessGame() {
        return true;
    }

    public function getSeamlessGameLogsTable() {
        return $this->original_seamless_game_logs_table;
    }

    public function getTransactionsTable(){
        return $this->getSeamlessTransactionTable();
    }

    public function getSeamlessTransactionTable() {
        return $this->original_seamless_wallet_transactions_table;
    }

    public function getGameSeamlessServiceLogsTable() {
        return $this->game_seamless_service_logs_table;
    }

    public function aesEncrypt($data) {
        if (is_array($data)) {
            $data = json_encode($data);
        }

        $output = false;
        $iv = strrev($this->partner_key);
        $output = openssl_encrypt($data, $this->cipher_algo, $this->partner_key, OPENSSL_RAW_DATA, $iv);
        $output = base64_encode($output);
        return $output;
    }

    public function aesDecrypt($data) {
        $output = false;
        $iv = strrev($this->partner_key);
        $output = openssl_decrypt(base64_decode($data), $this->cipher_algo, $this->partner_key, OPENSSL_RAW_DATA, $iv);
        return $output;
    }

    public function getHttpHeaders($params) {
        $headers = [
            'Content-type' => 'application/json',
        ];

        return $headers;
    }

    protected function customHttpCall($ch, $params) {
        if ($this->http_method == self::HTTP_METHOD_POST) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        }
    }

    public function processResultBoolean($responseResultId, $resultArr, $statusCode, $playerName = null) {
        $success = false;

        if (isset($resultArr['Code']) && ($resultArr['Code'] == self::RESPONSE_CODE_SUCCESS || $resultArr['Code'] == self::RESPONSE_CODE_USER_ALREADY_EXISTS)) {
            $success = true;
        }

        if (!$success) {
            $this->setResponseResultToError($responseResultId);
            $this->utils->debug_log(self::SEAMLESS_GAME_API_NAME . ' API got error ', $responseResultId, 'statusCode', $statusCode, 'playerName', $playerName, 'result', $resultArr);
        }

        return $success;
    }

    public function generateUrl($api_name, $params) {
        $api_uri = !empty(self::URI_MAP[$api_name]) ? self::URI_MAP[$api_name] : null;
        $url = $this->api_url;

        if ($this->http_method == self::HTTP_METHOD_GET) {
            switch ($api_name) {
                case self::API_createPlayer:
                    $url .= '?' . http_build_query($params);
                    break;
                case self::API_isPlayerExist:
                    $url .= '?' . http_build_query($params);
                    break;
                case self::API_queryForwardGame:
                    if (isset($params['is_mobile']) && $params['is_mobile']) {
                        if ($this->use_new_mobile_game_launch_url) {
                            $url = rtrim($this->new_mobile_game_launch_url, '/');
                        } else {
                            $url = rtrim($this->mobile_game_launch_url, '/');
                        }
                    } else {
                        $url = rtrim($this->web_game_launch_url, '/');
                    }

                    if (isset($params['is_mobile'])) {
                        unset($params['is_mobile']);
                    }

                    $url .= $api_uri . '?' . http_build_query($params);
                    break;
                case self::API_syncGameRecordsByVersion:
                case self::API_syncGameRecordsByDate:
                    $url .= '/?' . http_build_query($params);
                    break;
                default:
                    $url .= $api_uri . '?' . http_build_query($params);
                    break;
            }
        }

        return $url;
    }

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
        $this->CI->load->model(array('agency_model', 'player_model'));

        $agentPrefix = $playerPrefix = '';

        if($this->append_agent_prefix){
            $player = $this->CI->player_model->getPlayerByUsername($playerName);
            if(!empty($player->agent_id)){
                $agentPrefix = $this->CI->agency_model->getPlayerPrefixByAgentId($player->agent_id);
                if(!empty($agentPrefix)){
                    $playerPrefix .= $agentPrefix;
                }
            }
        }

        $playerPrefix .= $this->prefix_for_username;

        $this->CI->utils->debug_log('createPlayer bermar ', 'playerPrefix', $playerPrefix);


        $extra = [
            'prefix' => $playerPrefix,

            # fix exceed game length name
            'fix_username_limit' => $this->fix_username_limit,
            'minimum_user_length' => $this->minimum_user_length,
            'maximum_user_length' => $this->maximum_user_length,
            'default_fix_name_length' => $this->default_fix_name_length,
        ];

        parent::createPlayer($playerName, $playerId, $password, $email, $extra);
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForCreatePlayer',
            'playerId' => $playerId,
            'playerName' => $playerName,
            'gameUsername' => $gameUsername,
        ];

        $params = [
            'Method' => self::URI_MAP[self::API_createPlayer],
            'PartnerKey' => $this->partner_key,
            'UserName' => $gameUsername,
            'Currency' => $this->currency,
        ];

        $this->CI->utils->debug_log('createPlayer', 'playerPrefix', $playerPrefix, 'params', $params, 'agentPrefix', $agentPrefix);
        
        return $this->callApi(self::API_createPlayer, $params, $context);
    }

    public function processResultForCreatePlayer($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $playerId = $this->getVariableFromContext($params, 'playerId');
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        
        $result = [
            'response_result_id' => $responseResultId,
            'player_name' => $playerName,
            'player_game_username' => $gameUsername,
        ];
        
        if ($success) {
            if (!empty($resultArr['result'])) {
                $result['response_result'] = $resultArr['result'];
            }

            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
        }

        $this->CI->utils->debug_log('<---------- processResultForCreatePlayer ---------->', 'processResultForCreatePlayer_result', 'result: ' . json_encode($result));

        return [$success, $result];
    }

    public function isPlayerExist($playerName) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForIsPlayerExist',
            'playerId' => $playerId,
            'playerName' => $playerName,
            'gameUsername' => $gameUsername,
        ];

        $params = [
            'Method' => self::URI_MAP[self::API_isPlayerExist],
            'PartnerKey' => $this->partner_key,
            'UserName' => $gameUsername
        ];

        return $this->callApi(self::API_isPlayerExist, $params, $context);
    }

    public function processResultForIsPlayerExist($params) {
        $playerId = $this->getVariableFromContext($params, 'playerId');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);

        $result = [
            'exists' => false,
        ];

        if ($success) {
            $result['exists'] = true;
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
        } else {
            if (isset($resultArr['Data'])) {
                if (!$resultArr['Data']) {
                    $success = true;
                }
            }
        }

        return [$success, $result];
    }

    public function depositToGame($playerName, $amount, $transfer_secure_id = null) {
        $external_transaction_id = $transfer_secure_id;

        $result = [
            'success' => true,
            'external_transaction_id' => $external_transaction_id,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_APPROVED,
            'response_result_id' => null,
            'didnot_insert_game_logs' => true,
        ];

        return $result;
    }

    public function withdrawFromGame($playerName, $amount, $transfer_secure_id = null) {
        $external_transaction_id = $transfer_secure_id;

        $result = [
            'success' => true,
            'external_transaction_id' => $external_transaction_id,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_APPROVED,
            'response_result_id' => null,
            'didnot_insert_game_logs' => true,
        ];

        return $result;
    }

    public function queryTransaction($transactionId, $extra) {
        return $this->returnUnimplemented();
    }

    public function getLauncherLanguage($language) {
        return $this->getGameLauncherLanguage($language, [
            'en_us' => 'en-US',
            'zh_cn' => 'zh-CN',
            'id_id' => 'id-ID',
            'vi_vn' => 'vi-VN',
            'ko_kr' => 'ko-KR',
            'th_th' => 'th-TH',
            'hi_in' => 'hi-IN',
            'pt_br' => 'pt-PT',
        ]);
    }

    public function queryForwardGame($playerName, $extra = null) {
        $this->http_method = self::HTTP_METHOD_GET;

        $success = true;
        $playerId = $this->getPlayerIdFromUsername($playerName);
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $token = $this->getPlayerToken($playerId);
        $is_mobile = isset($extra['is_mobile']) && $extra['is_mobile'];

        $language = $this->language;
        if(isset($extra['language']) && !empty($extra['language'])){
            $language = $extra['language'];
        }else{
            $language = $this->language;
        }

        if($this->force_language && !empty($this->force_language)){
            $language = $this->force_language;
        }


        $language = $this->getLauncherLanguage($language);

        $params = [
            'lang' => $language,
            'templatename' => $this->template_name,
            'is_mobile' => $is_mobile, // extra info only
        ];

        if ($this->is_login_entry) {
            $params['token'] = $token;
            $params['user'] = $gameUsername;
            $params['currency'] = $this->currency;
            $params['view'] = $this->view;
        }

        $url = $this->generateUrl(self::API_queryForwardGame, $params);

        $result = [
            'success' => $success,
            'url' => $url,
        ];

        $this->utils->debug_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'params', $params, 'result', $result);

        return $result;
    }

    public function syncOriginalGameLogs($token) {
        $datetime_from = clone $this->getValueFromSyncInfo($token, 'dateTimeFrom');
        $datetime_to = clone $this->getValueFromSyncInfo($token, 'dateTimeTo');

        $datetime_from_modified = $datetime_from->modify($this->getDatetimeAdjust());

        $datetime_from = new DateTime($this->serverTimeToGameTime($datetime_from_modified->format('Y-m-d H:i:s')));
        $datetime_to = new DateTime($datetime_to->format('Y-m-d H:i:s'));

        $datetime_from = $datetime_from->format('Y-m-d') . 'T' . $datetime_from->format('H:i:s');
        $datetime_to = $datetime_to->format('Y-m-d') . 'T' . $datetime_to->format('H:i:s');

        $result = [
            'success' => true,
            'data_count' => 0,
            'data_count_insert' => 0,
            'data_count_update' => 0,
            'sync_time_interval' => $this->sync_time_interval,
            'sleep_time' => $this->sleep_time
        ];

        $last_sync_id = $this->getLastSyncIdFromTokenOrDB($token);
        $sync_id = !empty($last_sync_id) ? $last_sync_id : 0;

        if ($this->query_bet_record_by == self::QUERY_BET_RECORD_BY_DATE) {
            $sync_result = $this->queryBetRecordByDate($datetime_from, $datetime_to, $sync_id);
        } else {
            $sync_result = $this->queryBetRecordByVersion($sync_id);
        }

        $result['success'] = isset($sync_result['success']) ? $sync_result['success'] : false;
        $result['data_count'] = isset($sync_result['data_count']) ? $sync_result['data_count'] : 0;
        $result['data_count_insert'] = isset($sync_result['data_count_insert']) ? $sync_result['data_count_insert'] : 0;
        $result['data_count_update'] = isset($sync_result['data_count_update']) ? $sync_result['data_count_update'] : 0;

        $this->utils->info_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'result', $result);

        return $result;
    }

    public function queryBetRecordByVersion($sync_id = 0) {
        $this->http_method = self::HTTP_METHOD_GET;
        $method = self::API_syncGameRecordsByVersion;

        $params = [
            'Method' => $method,
            'PartnerKey' => $this->partner_key,
            'Version' => $sync_id,
        ];

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryBetRecord',
            'request_params' => $params,
        );

        return $this->callApi($method, $params, $context);
    }

    public function queryBetRecordByDate($start_date = null, $end_date = null, $sync_id = 0) {
        $this->http_method = self::HTTP_METHOD_GET;
        $method = self::API_syncGameRecordsByDate;

        $params = [
            'Method' => $method,
            'PartnerKey' => $this->partner_key,
            'TimeType' => $this->time_type,
            'StartDate' => $start_date,
            'EndDate' => $end_date,
            'Version' => $sync_id,
        ];

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryBetRecord',
            'request_params' => $params,
        );

        return $this->callApi($method, $params, $context);
    }

    public function processResultForQueryBetRecord($params) {
        $this->CI->load->model(['original_seamless_wallet_transactions']);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        $request_params = $this->getVariableFromContext($params, 'request_params');

        $result = [
            'data_count' => 0,
            'data_count_insert' => 0,
            'data_count_update' => 0
        ];

        $extra = [
            'response_result_id' => $responseResultId,
            'request_params' => $request_params,
        ];

        if($success) {
            $game_records = !empty($resultArr['Data']) ? $resultArr['Data'] : [];

            if (!empty($game_records)) {
                $result['data_count'] = count($game_records);

                if (is_array($game_records) && isset($game_records[0])) {
                    foreach ($game_records as $game_record) {
                        $data = $this->rebuildGameRecords($game_record, $extra);
                        $external_unique_id = isset($data['external_unique_id']) ? $data['external_unique_id'] : null;
                        $md5_sum = isset($data['md5_sum']) ? $data['md5_sum'] : null;
                        $version_id = isset($data['version_id']) ? $data['version_id'] : null;

                        $this->CI->external_system->setLastSyncId($this->getPlatformCode(), $version_id);
                        $get_record = $this->CI->original_seamless_wallet_transactions->querySingleTransactionCustom($this->original_seamless_game_logs_table, ['external_unique_id' => $external_unique_id]);

                        if (!empty($get_record)) {
                            if (isset($get_record['md5_sum']) && $get_record['md5_sum'] != $md5_sum) {
                                // update
                                $result['data_count_update'] += 1;
                                $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_game_logs_table, $data, 'external_unique_id', $external_unique_id);
                                $this->updateTransaction($data);
                            }
                        } else {
                            // insert
                            $result['data_count_insert'] += 1;
                            $this->CI->original_seamless_wallet_transactions->insertTransactionData($this->original_seamless_game_logs_table, $data);
                            $this->updateTransaction($data);
                        }
                    }
                }
            }
        }

        sleep($this->sleep_time);
        $this->CI->db->_reset_select();
        $this->CI->db->reconnect();
        $this->CI->db->initialize();

        return array($success, $result);
    }

    public function rebuildGameRecords($game_record, $extra) {
        $version_id = !empty($game_record['Id']) ? $game_record['Id'] : null;
        $game_username = !empty($game_record['SourceName']) ? $game_record['SourceName'] : null;
        $player_id = $this->getPlayerIdInGameProviderAuth($game_username);
        $language = $this->language;
        $currency = !empty($game_record['currency']) ? $game_record['currency'] : $this->currency;
        $transaction_id = !empty($game_record['ReferenceNo']) ? $game_record['ReferenceNo'] : null;
        $game_code = !empty($game_record['SportType']) ? $game_record['SportType'] : null;
        $round_id = !empty($game_record['MatchID']) ? $game_record['MatchID'] : null;
        $status = !empty($game_record['WinLoseStatus']) ? $game_record['WinLoseStatus'] : null;
        $external_unique_id = $transaction_id;
        if(isset($game_record['DangerStatus'])){
            #override status if danger status is cancel or rejected
            if($game_record['DangerStatus'] == "C" || $game_record['DangerStatus'] == "R"){
                $status = $game_record['DangerStatus'];
            }
        }

        $data = [
            'version_id' => $version_id,
            'player_id' => $player_id,
            'game_username' => $game_username,
            'language' => $language,
            'currency' => $currency,
            'transaction_id' => $transaction_id,
            'game_code' => $game_code,
            'round_id' => $round_id,
            'status' => $status,
            'request' => isset($extra['request_params']) ? json_encode($extra['request_params']) : null,
            'response' => json_encode($game_record),
            'soc_transaction_id' => !empty($game_record['SocTransId']) ? $game_record['SocTransId'] : null,
            'response_result_id' => isset($extra['response_result_id']) ? $extra['response_result_id'] : null,
            'external_unique_id' => $external_unique_id,
            'md5_sum' => md5(json_encode($game_record)),
        ];

        return $data;
    }

    public function setStatusFromOgl($status) {
        switch ($status) {
            case 'WA':# Win All
            case 'WH':# Win Half
            case 'LA':# Lose All 
            case 'LH':# Lose Half 
            case 'D':# Draw
                $status = Game_logs::STATUS_SETTLED;
                break;
            case 'P':
                $status = Game_logs::STATUS_PENDING;
                break;
            case 'R':
                $status = Game_logs::STATUS_REJECTED;
                break;
            case 'C':
                $status = Game_logs::STATUS_CANCELLED;
                break;
            default:
                $status = Game_logs::STATUS_PENDING;
                break;
        }

        return $status;
    }

    public function updateTransaction($data) {
        $update_data = [
            'status' => !empty($data['status']) ? $this->setStatusFromOgl($data['status']) : null,
            'flag_of_updated_result' => self::FLAG_NOT_UPDATED,
            'md5_sum' => !empty($data['md5_sum']) ? $data['md5_sum'] : null,
        ];

        $where = [
            'game_username' => !empty($data['game_username']) ? $data['game_username'] : null,
            'transaction_id' => !empty($data['transaction_id']) ? $data['transaction_id'] : null,
        ];

        return $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResultCustom($this->original_seamless_wallet_transactions_table, $where, $update_data);
    }

    public function syncOriginalGameLogsFromTrans($token = false) {
        $this->CI->load->model(['original_seamless_wallet_transactions']);
        $startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
        $startDate->modify($this->getDatetimeAdjust());
        $queryDateTimeStart = $startDate->format('Y-m-d H:i:s');
        $queryDateTimeEnd = $endDate->format('Y-m-d H:i:s');
        $transactions = $this->queryTransactionsForUpdate($queryDateTimeStart, $queryDateTimeEnd, null, $this->use_transaction_bet_time);
        $updated = 0;

        if (!empty($transactions) && is_array($transactions)) {
            foreach ($transactions as $transaction) {
                $validated_transaction = [
                    'type' => !empty($transaction['transaction_type']) ? $transaction['transaction_type'] : null,
                    'id' => !empty($transaction['transaction_id']) ? $transaction['transaction_id'] : null,
                    'player_id' => !empty($transaction['player_id']) ? $transaction['player_id'] : null,
                    'game_code' => !empty($transaction['game_code']) ? $transaction['game_code'] : null,
                    'round_id' => !empty($transaction['round_id']) ? $transaction['round_id'] : null,
                    'start_at' => !empty($transaction['start_at']) ? $transaction['start_at'] : null,
                    'status' => !empty($transaction['status']) ? $transaction['status'] : null,
                    'updated_at' => !empty($transaction['updated_at']) ? $transaction['updated_at'] : null,
                    'external_unique_id' => !empty($transaction['external_unique_id']) ? $transaction['external_unique_id'] : null,
                    'amount' => !empty($transaction['amount']) ? $transaction['amount'] : 0,
                    'bet_amount' => !empty($transaction['bet_amount']) ? $transaction['bet_amount'] : 0,
                    'win_amount' => !empty($transaction['win_amount']) ? $transaction['win_amount'] : 0,
                    'result_amount' => !empty($transaction['result_amount']) ? $transaction['result_amount'] : 0
                ];

                extract($validated_transaction, EXTR_PREFIX_ALL, 'transaction');

                $order_by = ['field_name' => 'updated_at', 'is_desc' => true];
                $get_game_logs = $this->CI->original_seamless_wallet_transactions->querySingleTransactionCustom($this->original_seamless_game_logs_table, [
                    'player_id' => $transaction_player_id,
                    'transaction_id' => $transaction_id,
                ], [], $order_by);

                if (!empty($get_game_logs)) {
                    $game_code = isset($get_game_logs['game_code']) ? $get_game_logs['game_code'] : $transaction_game_code;
                    $round_id = isset($get_game_logs['round_id']) ? $get_game_logs['round_id'] : $transaction_round_id;
                    $status = !empty($get_game_logs['status']) ? $this->setStatusFromOgl($get_game_logs['status']) : $transaction_status;
                    $ogl_response = !empty($get_game_logs['response']) ? json_decode($get_game_logs['response'], true) : [];
                    $real_bet_amount = isset($ogl_response['BetAmount']) ? $ogl_response['BetAmount'] : null;
                    $real_result_amount = isset($ogl_response['WinAmount']) ? $ogl_response['WinAmount'] : null;

                    if (empty($transaction_status) && !empty($status)) {
                        $transaction_status = $status;
                    }

                    $deduct_balance_transaction = $this->CI->original_seamless_wallet_transactions->querySingleTransactionCustom($this->original_seamless_wallet_transactions_table, [
                        'transaction_type' => self::API_METHOD_DEDUCT_BALANCE,
                        'player_id' => $transaction_player_id,
                        'transaction_id' => $transaction_id,
                    ]);

                    $update_balance_transaction = $this->CI->original_seamless_wallet_transactions->querySingleTransactionCustom($this->original_seamless_wallet_transactions_table, [
                        'transaction_type' => self::API_METHOD_UPDATE_BALANCE,
                        'player_id' => $transaction_player_id,
                        'transaction_id' => $transaction_id,
                    ]);

                    $validated_deduct_balance_transaction = [
                        'amount' => !empty($deduct_balance_transaction['amount']) ? $deduct_balance_transaction['amount'] : 0,
                        'external_unique_id' => !empty($deduct_balance_transaction['external_unique_id']) ? $deduct_balance_transaction['external_unique_id'] : null,
                        'real_amount' => !empty($deduct_balance_transaction['bet_amount']) ? $deduct_balance_transaction['bet_amount'] : null,
                    ];

                    $validated_update_balance_transaction = [
                        'amount' => !empty($update_balance_transaction['amount']) ? $update_balance_transaction['amount'] : 0,
                        'external_unique_id' => !empty($update_balance_transaction['external_unique_id']) ? $update_balance_transaction['external_unique_id'] : null,
                    ];

                    extract($validated_deduct_balance_transaction, EXTR_PREFIX_ALL, 'bet');
                    extract($validated_update_balance_transaction, EXTR_PREFIX_ALL, 'update_api');

                    if (array_key_exists('transaction_type', $transaction)) {
                        switch ($transaction_type) {
                            case self::API_METHOD_DEDUCT_BALANCE:
                                if (!empty($real_bet_amount)) {
                                    $bet_amount = $real_bet_amount * $this->conversion;
                                } else {
                                    $bet_amount = !empty($transaction_bet_amount) ? $transaction_bet_amount : $transaction_amount;
                                }

                                if (!empty($real_result_amount)) {
                                    $result_amount = $real_result_amount * $this->conversion;

                                    if ($real_result_amount > 0) {
                                        $win_amount = $real_result_amount * $this->conversion;
                                    } else {
                                        $win_amount = $bet_amount - abs($result_amount);
                                    }
                                } else {
                                    $win_amount = 0;
                                    $result_amount = !empty($transaction_result_amount) ? $transaction_result_amount : -$transaction_amount;
                                }

                                $bet_data = [
                                    'status' => $status,
                                    'bet_amount' => $bet_amount,
                                    'win_amount' => $win_amount,
                                    'result_amount' => $result_amount,
                                    'flag_of_updated_result' => self::FLAG_UPDATED_FOR_GAME_LOGS,

                                    // additional
                                    'game_code' => $game_code,
                                    'round_id' => $round_id,
                                ];

                                if(strtoupper($get_game_logs['status']) == "D"){ # if draw set result amount to 0
                                    $bet_data['result_amount'] = 0;
                                }

                                if (!$this->enable_merging_rows) {
                                    $bet_data['bet_amount'] = $bet_amount;
                                    $bet_data['win_amount'] = 0;
                                    $bet_data['result_amount'] = -$bet_amount;
                                }

                                $bet_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($bet_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);
                                $update_result = $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $bet_data, 'external_unique_id', $transaction_external_unique_id);

                                if ($update_result) {
                                    $updated++;
                                }
                                break;
                            case self::API_METHOD_UPDATE_BALANCE:
                                $update_api_data = [
                                    'status' => $status,
                                    'flag_of_updated_result' => self::FLAG_UPDATED,
                                    'game_code' => $game_code,
                                ];

                                if (!$this->enable_merging_rows) {
                                    $update_api_data['bet_amount'] = 0;
                                    $update_api_data['win_amount'] = 0;
                                    $update_api_data['result_amount'] = $update_api_amount;
                                    $update_api_data['flag_of_updated_result'] = self::FLAG_UPDATED_FOR_GAME_LOGS;
                                }

                                /* if (!empty($real_bet_amount)) {
                                    $bet_amount = $real_bet_amount;
                                } else {
                                    if (!empty($bet_real_amount)) {
                                        $bet_amount = $bet_real_amount;
                                    }
                                }

                                $bet_data = [
                                    'status' => $transaction_status,
                                    'bet_amount' => $bet_amount,
                                    'win_amount' => $update_api_amount,
                                    'result_amount' => $update_api_amount - $bet_amount,
                                    'flag_of_updated_result' => self::FLAG_UPDATED_FOR_GAME_LOGS,

                                    // additional
                                    'game_code' => $game_code,
                                    'round_id' => $transaction_round_id,
                                ];

                                switch ($transaction_status) {
                                    case Game_logs::STATUS_SETTLED:
                                        $bet_data['win_amount'] = $update_api_amount;
                                        $bet_data['result_amount'] = $update_api_amount - $bet_amount;
                                        break;
                                    case Game_logs::STATUS_REFUND:
                                    case Game_logs::STATUS_UNSETTLED:
                                    case Game_logs::STATUS_CANCELLED:
                                        $bet_data['win_amount'] = 0;
                                        $bet_data['result_amount'] = $update_api_amount;
                                        break;
                                    case Game_logs::STATUS_PENDING:
                                        $bet_data['win_amount'] = 0;
                                        $bet_data['result_amount'] = $bet_amount;
                                        break;
                                    default:
                                        # code...
                                        break;
                                }

                                $bet_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($bet_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS); */
                                $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $update_api_data, 'external_unique_id', $transaction_external_unique_id);
                                // $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $bet_data, 'external_unique_id', $bet_external_unique_id);
                                break;
                            default:
                                break;
                        }
                    }

                    $this->utils->debug_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'transaction_start_at', $transaction_start_at, 'transaction_updated_at', $transaction_updated_at);
                }
            }
        }

        $total_transactions_updated = $updated;

        $result = [
            $this->utils->pluralize('total_transaction_updated', 'total_transactions_updated', $total_transactions_updated) => $total_transactions_updated,
        ];

        $this->utils->info_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'result', $result);

        return ['success' => true, $result];
    }

    public function queryTransactionsForUpdate($dateFrom, $dateTo, $transaction_type = null, $use_transaction_bet_time = true) {
        $sqlTime = 'updated_at >= ? AND updated_at <= ?';

        if ($use_transaction_bet_time) {
            $sqlTime = 'start_at >= ? AND end_at <= ?';
        }

        if (!empty($transaction_type)) {
            $and_transaction_type = 'AND transaction_type = ?';
        } else {
            $and_transaction_type = '';
        }

        $sql = <<<EOD
SELECT
id,
game_platform_id,
player_id,
transaction_type,
transaction_id,
game_code,
round_id,
amount,
start_at,
status,
request,
external_unique_id,
updated_at,
bet_amount,
win_amount,
result_amount
FROM {$this->original_seamless_wallet_transactions_table}
WHERE game_platform_id = ? AND wallet_adjustment_status NOT IN ('preserved', 'failed') AND {$sqlTime} {$and_transaction_type}
EOD;

        if (!empty($transaction_type)) {
            $params = [
                $this->getPlatformCode(),
                $dateFrom,
                $dateTo,
                $transaction_type,
            ];
        } else {
            $params = [
                $this->getPlatformCode(),
                $dateFrom,
                $dateTo
            ];
        }

        // $this->utils->debug_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'sql', $sql, 'params', $params);
        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);

        return $result;
    }

    public function queryPlayerTransaction($transaction_type, $player_id, $game_code, $round_id, $transaction_id = null) {
        $and_transaction_id = !empty($transaction_id) ? 'AND transaction_id = ?' : '';

        $sql = <<<EOD
SELECT DISTINCT 
player_id,
id,
sum(amount) as amount,
status,
request,
external_unique_id,
sum(bet_amount) as bet_amount,
sum(win_amount) as win_amount,
sum(result_amount) as result_amount
FROM {$this->original_seamless_wallet_transactions_table}
WHERE game_platform_id = ? AND transaction_type = ? AND player_id = ? AND game_code = ? AND round_id = ? AND wallet_adjustment_status NOT IN ('preserved', 'failed') {$and_transaction_id}
EOD;

        $params = [
            $this->getPlatformCode(),
            $transaction_type,
            $player_id,
            $game_code,
            $round_id,
        ];

        if (!empty($transaction_id)) {
            array_push($params, $transaction_id);
        }

        // $this->utils->debug_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'sql', $sql, 'params', $params);
        $results = $this->CI->original_game_logs_model->commonGetOneOriginalGameLogs($sql, $params);

        return $results;
    }

    public function syncMergeToGameLogs($token) {
        $enabled_game_logs_unsettle = true;

        if ($this->use_transaction_data) {
            $this->syncOriginalGameLogsFromTrans($token);
        }

        return $this->commonSyncMergeToGameLogs(
            $token,
            $this,
            [$this, 'queryOriginalGameLogsFromTrans'],
            [$this, 'makeParamsForInsertOrUpdateGameLogsRowFromTrans'],
            [$this, 'preprocessOriginalRowForGameLogsFromTrans'],
            $enabled_game_logs_unsettle
        );
    }

    /**
     * queryOriginalGameLogsFromTrans
     * @param  string $dateFrom
     * @param  string $dateTo
     * @param  bool   $use_bet_time
     * @return array
     */
    public function queryOriginalGameLogsFromTrans($dateFrom, $dateTo, $use_bet_time) {
        $sqlTime = 'transaction.updated_at >= ? AND transaction.updated_at <= ?';

        if ($use_bet_time) {
            $sqlTime = 'transaction.start_at >= ? AND transaction.start_at <= ?';
        }

        $sql = <<<EOD
SELECT
transaction.id AS sync_index,
transaction.player_id,
transaction.game_username AS player_username,
transaction.before_balance,
transaction.after_balance,
transaction.game_code,
transaction.transaction_type,
transaction.status,
transaction.response_result_id,
transaction.external_unique_id as external_uniqueid,
transaction.start_at,
transaction.start_at AS bet_at,
transaction.end_at,
transaction.created_at,
transaction.updated_at,
transaction.md5_sum,
transaction.transaction_id,
transaction.round_id,
transaction.bet_amount,
transaction.bet_amount AS real_betting_amount,
transaction.win_amount,
transaction.result_amount,
transaction.flag_of_updated_result,
transaction.wallet_adjustment_status,
game_description.id AS game_description_id,
game_description.game_type_id,
game_description.english_name AS game
FROM {$this->original_seamless_wallet_transactions_table} AS transaction
LEFT JOIN game_description ON transaction.game_code = game_description.external_game_id AND game_description.game_platform_id = ?
WHERE transaction.game_platform_id = ? AND transaction.flag_of_updated_result = ? AND transaction.wallet_adjustment_status NOT IN ('preserved', 'failed') AND {$sqlTime}
EOD;

        $params = [
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            self::FLAG_UPDATED_FOR_GAME_LOGS,
            $dateFrom,
            $dateTo,
        ];

        $this->utils->debug_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'sql', $sql, 'params', $params);
        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);

        return $result;
    }

    /**
     * it will be used on processUnsettleGameLogs and commonUpdateOrInsertGameLogs
     *
     * @param  array $row
     * @return array $params
     */
    public function makeParamsForInsertOrUpdateGameLogsRowFromTrans(array $row) {
        if (empty($row['md5_sum'])) {
            $row['md5_sum'] = $this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE_FROM_TRANS);
        }

        $data = [
            'game_info' => [
                'game_type_id' => !empty($row['game_type_id']) ? $row['game_type_id'] : null,
                'game_description_id' => !empty($row['game_description_id']) ? $row['game_description_id'] : null,
                'game_code' => !empty($row['game_code']) ? $row['game_code'] : null,
                'game_type' => null,
                'game' => !empty($row['game']) ? $row['game'] : null,
            ],
            'player_info' => [
                'player_id' => !empty($row['player_id']) ? $row['player_id'] : null,
                'player_username' => !empty($row['player_username']) ? $row['player_username'] : null,
            ],
            'amount_info' => [
                'bet_amount' => !empty($row['bet_amount']) ? $row['bet_amount'] : 0,
                'result_amount' => !empty($row['result_amount']) ? $row['result_amount'] : 0,
                'bet_for_cashback' => !empty($row['bet_amount']) ? $row['bet_amount'] : 0,
                'real_betting_amount' => !empty($row['real_betting_amount']) ? $row['real_betting_amount'] : 0,
                'win_amount' => null,
                'loss_amount' => null,
                'after_balance' => !empty($row['after_balance']) ? $row['after_balance'] : 0,
            ],
            'date_info' => [
                'start_at' => !empty($row['start_at']) ? $row['start_at'] : '0000-00-00 00:00:00',
                'end_at' => !empty($row['end_at']) ? $row['end_at'] : '0000-00-00 00:00:00',
                'bet_at' => !empty($row['bet_at']) ? $row['bet_at'] : '0000-00-00 00:00:00',
                'updated_at' => $this->utils->getNowForMysql(),
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => !empty($row['status']) ? $row['status'] : null,
            'additional_info' => [
                'has_both_side' => 0,
                'external_uniqueid' => !empty($row['external_uniqueid']) ? $row['external_uniqueid'] : null,
                'round_number' => !empty($row['round_id']) ? $row['round_id'] : null,
                'md5_sum' => $row['md5_sum'],
                'response_result_id' => !empty($row['response_result_id']) ? $row['response_result_id'] : null,
                'sync_index' => $row['sync_index'],
                'bet_type' => null,
            ],
            'bet_details' => $this->preprocessBetDetails($row, null, true),
            'extra' => [
                'note' => !empty($row['note']) ? $row['note'] : null,
                'odds' =>  isset($row['odds']) ? $row['odds'] : null,
            ],
            'game_logs_id' => isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id' => isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];

        //$this->utils->debug_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'data', $data);

        return $data;
    }

    /**
     *
     * perpare original rows, include process unknown game, pack bet details, convert game status
     *
     * @param  array &$row
     */
    public function preprocessOriginalRowForGameLogsFromTrans(array &$row) {
        if (empty($row['game_type_id'])) {
            list($row['game_description_id'], $row['game_type_id']) = $this->getGameDescriptionInfo($row, $this->getUnknownGame());
        }

        $result_amount = !empty($row['result_amount']) ? $row['result_amount'] : 0;
        $status = !empty($row['status']) ? $row['status'] : null;
        
        if ($this->enable_merging_rows) {
            $is_bet_transaction_type = $row['transaction_type'] == self::API_METHOD_DEDUCT_BALANCE;

            if ($status == Game_logs::STATUS_REFUND) {
                if ($is_bet_transaction_type) {
                    $row['after_balance'] += $row['result_amount'];
                }
            } else {
                if ($is_bet_transaction_type) {
                    $row['after_balance'] += $row['win_amount'];
                }
            }
        }

        $row['note'] = $this->getResult($status, $result_amount, $row);
        $odds_detail = $this->getOdds($row['player_id'], $row['transaction_id']);
        $row['odds'] = $odds = isset($odds_detail['Odds']) ? $odds_detail['Odds'] : null;
        $odds_type = isset($odds_detail['OddsType']) ? $odds_detail['OddsType'] : null;
        if(!$this->enable_merging_rows){
            if($odds < 0){
                $this->utils->debug_log('odds_detail', $odds_detail);
                if($row['transaction_type'] == self::API_METHOD_DEDUCT_BALANCE){
                    if($odds_type == "MY" || $odds_type == "INDO"){
                        $row['real_betting_amount'] = $row['bet_amount'];
                        $row['bet_amount'] = abs($row['bet_amount'] * $odds);
                        $row['result_amount'] = -$row['bet_amount'];
                    } else if($odds_type == "US"){
                        $row['real_betting_amount'] = $row['bet_amount'];
                        $row['bet_amount'] = abs($row['bet_amount'] * ($odds/100));
                        $row['result_amount'] = -$row['bet_amount'];
                    }
                }
            }
        }
        
        $row['bet_details'] = $this->getBetDetails($row['player_id'], $row['transaction_id']);
    }

    private function getGameDescriptionInfo($row, $unknownGame) {
        $game_code = !empty($row['game_code']) ? $row['game_code'] : null;
        $game_type_id = !empty($row['game_type_id']) ? $row['game_type_id'] : $unknownGame->game_type_id;

        if (!empty($row['game_description_id'])) {
            $game_description_id = $row['game_description_id'];
        } else {
            $game_description_id = $this->CI->game_description_model->processUnknownGame($this->getPlatformCode(), $unknownGame->game_type_id, $game_code, $game_code);
        }

        return array($game_description_id, $game_type_id);
    }

    public function getResult($status, $result_amount, $row = []) {
        $result = 'Unknown';

        if ($status == Game_logs::STATUS_SETTLED) {
            if ($this->enable_merging_rows) {
                if ($result_amount > 0) {
                    $result = 'Win';
                } elseif ($result_amount < 0) {
                    $result = 'Lose';
                } elseif ($result_amount == 0) {
                    $result = 'Draw';
                } else {
                    $result = 'Free Game';
                }
            } else {
                $result = $row['wallet_adjustment_status'];
            }
        } elseif ($status == Game_logs::STATUS_CANCELLED) {
            $result = 'Cancelled';
        } elseif ($status == Game_logs::STATUS_REFUND) {
            $result = 'Refund';
        } elseif ($status == Game_logs::STATUS_UNSETTLED) {
            $result = 'Unsettled';
        } elseif ($status == Game_logs::STATUS_PENDING) {
            $result = 'Pending';
        } elseif ($status == Game_logs::STATUS_REJECTED) {
            $result = 'Rejected';
        } else {
            $result = 'Unknown';
        }

        return $result;
    }

    public function defaultBetDetailsFormat($row) {
        $bet_details = [];

        if (isset($row['bet_amount'])) {
            $bet_details['bet_amount'] = $row['bet_amount'];
        }

        if (isset($row['win_amount'])) {
            $bet_details['win_amount'] = $row['win_amount'];
        }

        if (isset($row['transaction_id'])) {
            $bet_details['bet_id'] = $row['transaction_id'];
        }

        if (isset($row['game'])) {
            $bet_details['game_name'] = $row['game'];
        }

        if (isset($row['round_id'])) {
            $bet_details['round_id'] = $row['round_id'];
        }

        if (isset($row['bet_at'])) {
            $bet_details['betting_datetime'] = $row['bet_at'];
        }

        return $bet_details;
     }

    public function queryTransactionByDateTime($startDate, $endDate) {
        if (empty($this->original_seamless_wallet_transactions_table)) {
            $this->utils->debug_log("queryTransactionByDateTime cannot get seamless transaction table", $this->getPlatformCode());
            return false;
        }

        $md5_fields = implode(", ", array('transaction.amount', 'transaction.before_balance', 'transaction.after_balance', 'transaction.result_amount', 'transaction.updated_at'));

        $sql = <<<EOD
SELECT
transaction.player_id as player_id,
transaction.start_at as transaction_date,
transaction.amount as amount,
transaction.after_balance,
transaction.before_balance,
transaction.result_amount,
transaction.round_id as round_no,
transaction.transaction_id,
transaction.external_unique_id as external_uniqueid,
transaction.transaction_type as trans_type,
transaction.updated_at,
MD5(CONCAT({$md5_fields})) as md5_sum,
transaction.request
FROM {$this->original_seamless_wallet_transactions_table} as transaction
WHERE transaction.game_platform_id = ? and transaction.updated_at >= ? AND transaction.updated_at <= ?
ORDER BY transaction.updated_at asc, transaction.id asc;
EOD;

        $params = [
            $this->getPlatformCode(),
            $startDate,
            $endDate,
        ];

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $result;
    }

    public function processTransactions(&$transactions) {
        $temp_game_records = [];

        if (!empty($transactions)) {
            foreach ($transactions as $transaction) {
                $temp_game_record = [];
                $temp_game_record['player_id'] = $transaction['player_id'];
                $temp_game_record['game_platform_id'] = $this->getPlatformCode();
                $temp_game_record['transaction_date'] = $transaction['transaction_date'];
                $temp_game_record['amount'] = abs($transaction['amount']);
                $temp_game_record['before_balance'] = $transaction['before_balance'];
                $temp_game_record['after_balance'] = $transaction['after_balance'];
                $temp_game_record['round_no'] = $transaction['round_no'];
                $temp_game_record['md5_sum'] = $transaction['md5_sum'];

                if (empty($temp_game_record['round_no']) && isset($transaction['transaction_id'])) {
                    $temp_game_record['round_no'] = $transaction['transaction_id'];
                }

                $extra=[];
                $extra['trans_type'] = $transaction['trans_type'];
                $temp_game_record['extra_info'] = json_encode($extra);
                $temp_game_record['external_uniqueid'] = $transaction['external_uniqueid'];
                $temp_game_record['transaction_type'] = Transactions::GAME_API_ADD_SEAMLESS_BALANCE;

                if ($transaction['trans_type'] == self::API_METHOD_DEDUCT_BALANCE) {
                    $temp_game_record['transaction_type'] = Transactions::GAME_API_SUBTRACT_SEAMLESS_BALANCE;
                } else {
                    if ($transaction['result_amount'] < 0) {
                        $temp_game_record['transaction_type'] = Transactions::GAME_API_SUBTRACT_SEAMLESS_BALANCE;
                    }
                }

                $temp_game_records[] = $temp_game_record;
                unset($temp_game_record);
            }
        }

        $transactions = $temp_game_records;
    }

    public function getBetDetails($player_id, $transaction_id) {
        $this->CI->load->model(['original_seamless_wallet_transactions']);

        $transaction = $this->CI->original_seamless_wallet_transactions->querySingleTransactionCustom($this->original_seamless_game_logs_table, [
            'player_id' => $player_id,
            'transaction_id' => $transaction_id,
        ]);

        return isset($transaction['response']) ? json_decode($transaction['response'], true) : [];
    }

    public function getOdds($player_id, $transaction_id) {
        $bet_details = $this->getBetDetails($player_id, $transaction_id);
        return array(
            'Odds' =>  isset($bet_details['Odds']) ? $bet_details['Odds'] : null,
            'OddsType' => isset($bet_details['OddsType']) ? $bet_details['OddsType'] : null,
        );
        // return isset($bet_details['Odds']) ? $bet_details['Odds'] : null;
    }

    public function rebuildBetDetailsFormat($row, $game_type) {
        $bet_details = [];

        switch ($game_type) {
            case self::GAME_TYPE_SPORTS:
            case self::GAME_TYPE_E_SPORTS:
                if (isset($row['bet_details']['Hdp'])) {
                    $bet_details['Hdp'] = $row['bet_details']['Hdp'];
                }

                if (isset($row['bet_details']['Odds'])) {
                    $bet_details['Odds'] = $row['bet_details']['Odds'];
                }

                if (isset($row['bet_details']['Choice'])) {
                    $bet_details['Choice'] = $row['bet_details']['Choice'];
                }

                if (isset($row['bet_details']['MatchID'])) {
                    $bet_details['MatchID'] = $row['bet_details']['MatchID'];
                }

                if (isset($row['bet_details']['Currency'])) {
                    $bet_details['Currency'] = $row['bet_details']['Currency'];
                }

                if (isset($row['bet_details']['LeagueId'])) {
                    $bet_details['LeagueId'] = $row['bet_details']['LeagueId'];
                }

                if (isset($row['bet_details']['OddsType'])) {
                    $bet_details['OddsType'] = $row['bet_details']['OddsType'];
                }

                if (isset($row['bet_details']['AwayScore'])) {
                    $bet_details['AwayScore'] = $row['bet_details']['AwayScore'];
                }

                if (isset($row['bet_details']['BetAmount'])) {
                    $bet_details['BetAmount'] = $row['bet_details']['BetAmount'];
                }

                if (isset($row['bet_details']['BetSource'])) {
                    $bet_details['BetSource'] = $row['bet_details']['BetSource'];
                }

                if (isset($row['bet_details']['HomeScore'])) {
                    $bet_details['HomeScore'] = $row['bet_details']['HomeScore'];
                }

                if (isset($row['bet_details']['MatchDate'])) {
                    $bet_details['MatchDate'] = $row['bet_details']['MatchDate'];
                }

                if (isset($row['bet_details']['SpecialId'])) {
                    $bet_details['SpecialId'] = $row['bet_details']['SpecialId'];
                }

                if (isset($row['bet_details']['SportType'])) {
                    $bet_details['SportType'] = $row['bet_details']['SportType'];
                }

                if (isset($row['bet_details']['TransDate'])) {
                    $bet_details['TransDate'] = $row['bet_details']['TransDate'];
                }

                if (isset($row['bet_details']['TransType'])) {
                    $bet_details['TransType'] = $row['bet_details']['TransType'];
                }

                if (isset($row['bet_details']['WinAmount'])) {
                    $bet_details['WinAmount'] = $row['bet_details']['WinAmount'];
                }

                if (isset($row['bet_details']['AwayTeamId'])) {
                    $bet_details['AwayTeamId'] = $row['bet_details']['AwayTeamId'];
                }

                if (isset($row['bet_details']['BetRemarks'])) {
                    $bet_details['BetRemarks'] = $row['bet_details']['BetRemarks'];
                }

                if (isset($row['bet_details']['HomeTeamId'])) {
                    $bet_details['HomeTeamId'] = $row['bet_details']['HomeTeamId'];
                }

                if (isset($row['bet_details']['MMRPercent'])) {
                    $bet_details['MMRPercent'] = $row['bet_details']['MMRPercent'];
                }

                if (isset($row['bet_details']['SocTransId'])) {
                    $bet_details['SocTransId'] = $row['bet_details']['SocTransId'];
                }

                if (isset($row['bet_details']['Outstanding'])) {
                    $bet_details['Outstanding'] = $row['bet_details']['Outstanding'];
                }

                if (isset($row['bet_details']['ReferenceNo'])) {
                    $bet_details['ReferenceNo'] = $row['bet_details']['ReferenceNo'];
                }

                if (isset($row['bet_details']['WorkingDate'])) {
                    $bet_details['WorkingDate'] = $row['bet_details']['WorkingDate'];
                }

                if (isset($row['bet_details']['CashOutTotal'])) {
                    $bet_details['CashOutTotal'] = $row['bet_details']['CashOutTotal'];
                }

                if (isset($row['bet_details']['DangerStatus'])) {
                    $bet_details['DangerStatus'] = $row['bet_details']['DangerStatus'];
                }

                if (isset($row['bet_details']['ExchangeRate'])) {
                    $bet_details['ExchangeRate'] = $row['bet_details']['ExchangeRate'];
                }

                if (isset($row['bet_details']['RejectReason'])) {
                    $bet_details['RejectReason'] = $row['bet_details']['RejectReason'];
                }

                if (isset($row['bet_details']['RunAwayScore'])) {
                    $bet_details['RunAwayScore'] = $row['bet_details']['RunAwayScore'];
                }

                if (isset($row['bet_details']['RunHomeScore'])) {
                    $bet_details['RunHomeScore'] = $row['bet_details']['RunHomeScore'];
                }

                if (isset($row['bet_details']['StatusChange'])) {
                    $bet_details['StatusChange'] = $row['bet_details']['StatusChange'];
                }

                if (isset($row['bet_details']['MemCommission'])) {
                    $bet_details['MemCommission'] = $row['bet_details']['MemCommission'];
                }

                if (isset($row['bet_details']['StateUpdateTs'])) {
                    $bet_details['StateUpdateTs'] = $row['bet_details']['StateUpdateTs'];
                }

                if (isset($row['bet_details']['WinLoseStatus'])) {
                    $bet_details['WinLoseStatus'] = $row['bet_details']['WinLoseStatus'];
                }

                if (isset($row['bet_details']['CashOutTakeBack'])) {
                    $bet_details['CashOutTakeBack'] = $row['bet_details']['CashOutTakeBack'];
                }

                if (isset($row['bet_details']['MemCommissionSet'])) {
                    $bet_details['MemCommissionSet'] = $row['bet_details']['MemCommissionSet'];
                }

                if (isset($row['bet_details']['CashOutWinLoseAmount'])) {
                    $bet_details['CashOutWinLoseAmount'] = $row['bet_details']['CashOutWinLoseAmount'];
                }
                break;
            default:
                $bet_details = $this->defaultBetDetailsFormat($row);
                break;
        }

        if (empty($bet_details) && !empty($row['bet_details'])) {
            $bet_details = is_array($row['bet_details']) ? $row['bet_details'] : json_decode($row['bet_details'], true);
        }

        if (empty($bet_details)) {
            $bet_details = $this->defaultBetDetailsFormat($row);
        }

        return $bet_details;
    }

    public function getUnsettledRounds($dateFrom, $dateTo){
        $original_transactions_table = $this->getTransactionsTable();
        if(!$original_transactions_table){
            $this->utils->debug_log("getUnsettledRounds cannot get seamless transaction table", $this->getPlatformCode());
            return false;
        }
        $STATUS_PENDING = Game_logs::STATUS_PENDING;
        $STATUS_ACCEPTED = Game_logs::STATUS_ACCEPTED;

        $sqlTime="CMD.created_at >= ? AND CMD.created_at <= ? AND CMD.transaction_type = ? AND CMD.status in ('{$STATUS_PENDING}', '{$STATUS_ACCEPTED}')";
        $this->CI->load->model(array('original_game_logs_model'));
        $sql = <<<EOD
SELECT 
CMD.round_id as round_id, 
CMD.transaction_id as transaction_id, 
CMD.created_at as transaction_date,
CMD.external_unique_id as external_uniqueid,
CMD.player_id,
CMD.transaction_type,
CMD.amount,
CMD.amount as deducted_amount,
0 as added_amount,
CMD.game_platform_id as game_platform_id,
gd.id as game_description_id,
gd.game_type_id

from {$original_transactions_table} as CMD
LEFT JOIN game_description as gd ON CMD.game_code = gd.external_game_id and gd.game_platform_id = ?
where
{$sqlTime}
EOD;

        $transaction_type = "DeductBalance";
        $params=[
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo,
            $transaction_type,

        ];
        $this->CI->utils->debug_log('==> CMD getUnsettledRounds sql', $sql, $params);
        $results = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        // print_r($results);exit();
        return $results;
    }

    public function checkBetStatus($row){
        $original_transactions_table = $this->getTransactionsTable();
        $this->CI->load->model(['seamless_missing_payout', 'original_seamless_wallet_transactions', 'original_game_logs_model']);
        if(!empty($row)){
            $external_uniqueid = $row['external_uniqueid'];
            $bet = $this->CI->original_seamless_wallet_transactions->querySingleTransactionCustom($original_transactions_table, ['external_unique_id'=> $external_uniqueid]);
            if($bet['status'] == Game_logs::STATUS_PENDING || $bet['status'] == Game_logs::STATUS_ACCEPTED){
                $row['transaction_status']  = Game_logs::STATUS_PENDING;
                $row['status'] = Seamless_missing_payout::NOT_FIXED;
                // unset($row['row_count']);
                $result = $this->CI->original_game_logs_model->insertIgnoreRowsToOriginal('seamless_missing_payout_report', $row);
                if($result===false){
                    $this->CI->utils->error_log('CMD SEAMLESS-' .$this->getPlatformCode().'(checkBetStatus) Error insert missing payout', $row);
                }
            }
        } else {
            return array('success'=>false, 'exists'=>false);
        }
    }

    public function queryBetTransactionStatus($game_platform_id, $external_uniqueid){
        $this->CI->load->model(['original_seamless_wallet_transactions', ]);
        $original_transactions_table = $this->getTransactionsTable();
        $row = $this->CI->original_seamless_wallet_transactions->querySingleTransactionCustom($original_transactions_table, ['external_unique_id'=> $external_uniqueid]);
        if(!empty($row)){
            return array('success'=>true, 'status'=> $row['status']);
        }
        
        return array('success'=>false, 'status'=> Game_logs::STATUS_PENDING);
    }
}
//end of class