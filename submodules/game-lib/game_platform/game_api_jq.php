<?php
/**
 * JQ Integration
 * OGP-24937
 *
 * @author  Sony
 */
require_once dirname(__FILE__) . '/abstract_game_api.php';

class Game_api_jq extends Abstract_game_api {


    const TRANSACTION_DEPOSIT = 1;
    const TRANSACTION_WITHDRAW = 2;


    const MD5_FIELDS_FOR_ORIGINAL = [
        'bet_id',
        'game_name',
        'game_code',
        'account',
        'bet_time',
        'bet_level',
        'bet_amount',
        'total_bet_amount',
        'total_valid_bet',
        'net_profit',
        'serial_number',
        'status',
        'game_type',
        'start_balance',
        'end_balance',
        'reel_type',
    ];

    const MD5_FLOAT_AMOUNT_FIELDS = [
        'total_bet_amount',
        'total_valid_bet',
    ];

    const MD5_FIELDS_FOR_MERGE = [
        'round_id',
        'game_code',
        'username',
        'bet_time',
        'real_betting_amount',
        'valid_bet',
        'result_amount',
        'after_balance',
    ];

    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE = [
        'real_betting_amount',
        'valid_bet',
        'result_amount',
    ];

    const JQ_REASON_SUCCESS = 200;
    const JQ_REASON_USER_ALREADY_EXISTS = 4022;
    const JQ_REASON_NO_ENOUGH_BALANCE = 4006;
    const JQ_REASON_AGENT_NO_BALANCE = 5006;
    const JQ_REASON_GAMELOG_SYNC_OLDER_NO_DATA = 4052;
    const API_syncGameRecordsOlderData = "API_syncGameRecordsOlderData";

    public $original_logs_table_name;
    public $api_url;
    public $uppername;
    public $subagent;
    public $key;
    public $sync_interval;
    public $sync_interval_older;
    public $sync_sleep_time;

    public function __construct() {
        parent::__construct();
        $this->original_logs_table_name = 'jq_game_logs';
        $this->api_url = $this->getSystemInfo('url');
        $this->uppername = $this->getSystemInfo('uppername');
        $this->subagent = $this->getSystemInfo('subagent');
        $this->key = $this->getSystemInfo('key');
        $this->sync_sleep_time = $this->getSystemInfo('sync_sleep_time', 60);
        $this->sync_interval = $this->getSystemInfo('sync_interval', '+10 minutes');
        $this->sync_interval_older = $this->getSystemInfo('sync_interval_older', '+2 hours');


        $this->URI_MAP = array(
            self::API_createPlayer => '/member/member/register',
            self::API_queryPlayerBalance => '/finance/finance/balance/get',
            self::API_depositToGame => '/finance/finance/transfer',
            self::API_withdrawFromGame => '/finance/finance/transfer',
            self::API_queryForwardGame => '/box/game/play',
            self::API_syncGameRecords => '/bet-center/thirdParty/betHistory',
            self::API_queryBetDetailLink => '/GetTxnHistoryInHTML',
            self::API_syncGameRecordsOlderData => '/bet-center/thirdParty/weekToThreeMonth/queryBetHistory',
        );

    }

    public function getPlatformCode(){
        return JQ_GAME_API;
    }

    protected function customHttpCall($ch, $params) {
        $currentDate = new DateTime($this->serverTimeToGameTime((new DateTime)));
        $account = !empty($params['account']) ? $params['account'] : $this->subagent;
        $params['token'] = strtoupper(md5($this->uppername . $this->key . $account . $currentDate->format('Ymd')));

        $headers = [
            'Content-Type: application/json'
        ];

        if(array_key_exists('language', $params)) {
            $headers[] = 'Locale: ' . $params['language'];
            unset($params['language']);
        }

        $this->CI->utils->debug_log('---------- JQ params before callapi ----------', $params);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
    }

    public function generateUrl($api_name, $params) {
        $url = $this->api_url . $this->URI_MAP[$api_name];
        return $url;
    }

    public function processResultBoolean($response_result_id, $result_array, $player_name = null, $api_name = null) {
        $success = false;

        if(!empty($result_array['code']) && $result_array['code'] == self::JQ_REASON_SUCCESS){
            $success = true;
        }

        if($api_name == self::API_createPlayer && !empty($result_array['code']) && $result_array['code'] == self::JQ_REASON_USER_ALREADY_EXISTS) {
            $success = true;
        }

        if($api_name == self::API_syncGameRecordsOlderData && !empty($result_array['code']) && $result_array['code'] == self::JQ_REASON_GAMELOG_SYNC_OLDER_NO_DATA) {
            $success = true;
        }

        if(!$success){
            $this->setResponseResultToError($response_result_id);
            $this->CI->utils->debug_log('---------- JQ Process Result Boolean False ----------', $response_result_id, 'player_name', $player_name, 'result', $result_array);
        }

        return $success;
    }

    public function createPlayer($player_name, $playerId, $password, $email = null, $extra = null) {
        parent::createPlayer($player_name, $playerId, $password, $email, $extra);

        $game_username = $this->getGameUsernameByPlayerUsername($player_name);
        $player_id = $this->CI->player_model->getPlayerIdByUsername($player_name);
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForCreatePlayer',
            'player_name' => $player_name,
            'player_id' => $player_id,
        );

        $data = [
            'account' => $game_username,
            'upperName' => $this->uppername,
            'agentAccount' => $this->subagent,
            'userIp' => $this->CI->utils->getIP()
        ];
        $this->CI->utils->debug_log('---------- JQ createPlayer ----------', $data);

        return $this->callApi(self::API_createPlayer, $data, $context);
    }

    public function processResultForCreatePlayer($params){
        $player_name = $this->getVariableFromContext($params, 'player_name');
        $player_id = $this->getVariableFromContext($params, 'player_id');
        $response_result_id = $this->getResponseResultIdFromParams($params);
        $array_result = $this->getResultJsonFromParams($params);
        $result = [];
        $success = $this->processResultBoolean($response_result_id, $array_result, $player_name, self::API_createPlayer);

        if($success){
            $this->updateRegisterFlag($player_id, Abstract_game_api::FLAG_TRUE);
        }

        $this->CI->utils->debug_log('---------- JQ result for createPlayer ----------', $array_result);
        return array($success, $result);
    }

    public function queryPlayerBalance($player_name) {
        $game_username = $this->getGameUsernameByPlayerUsername($player_name);
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryPlayerBalance',
            'playerName' => $player_name,
        );

        $data = [
            'account' => $game_username,
            'upperName' => $this->uppername,
        ];

        $this->CI->utils->debug_log('---------- JQ queryPlayerBalance ----------', $data);

        return $this->callApi(self::API_queryPlayerBalance, $data, $context);

    }

    public function processResultForQueryPlayerBalance($params) {
        $player_name = $this->getVariableFromContext($params, 'playerName');
        $response_result_id = $this->getResponseResultIdFromParams($params);
        $array_result = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($response_result_id, $array_result, $player_name);
        $result = [];

        if($success) {
            $result['balance'] = $array_result['data'];
        }
        $this->CI->utils->debug_log('---------- JQ result for queryPlayerBalance ----------', $array_result);
        return array($success, $result);
    }

    public function depositOrWithdraw($player_name, $amount, $transfer_secure_id, $transaction_type) {
        $game_username = $this->getGameUsernameByPlayerUsername($player_name);
        $order_id = $transfer_secure_id;
        if($order_id == null) {
            $order_id = $this->getSecureId('transfer_request', 'secure_id', true, 'T');
        }
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForDepositOrWithdraw',
            'playerName' => $player_name,
            'external_transaction_id'=> $order_id
        );

        $data = [
            'account' => $game_username,
            'upperName' => $this->uppername,
            'amount' => $amount,
            'orderNumber' => $order_id,

        ];
        $this->CI->utils->debug_log('---------- JQ params for depositOrWithdraw ----------', $data);

        if($transaction_type == self::TRANSACTION_DEPOSIT) {
            $data['action'] = 'IN';
            return $this->callApi(self::API_depositToGame, $data, $context);
        }
        else {
            $data['action'] = 'OUT';
            return $this->callApi(self::API_withdrawFromGame, $data, $context);
        }
    }

    public function processResultForDepositOrWithdraw($params) {
        $player_name = $this->getVariableFromContext($params, 'playerName');
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
        $response_result_id = $this->getResponseResultIdFromParams($params);
        $array_result = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($response_result_id, $array_result, $player_name);

        $result = array(
            'response_result_id' => $response_result_id,
            'external_transaction_id' => $external_transaction_id,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id' => self::REASON_UNKNOWN
        );

        if($success){
            $result['didnot_insert_game_logs'] = true;
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
        }  else {
            $status = $array_result['status'];
            $result['reason_id'] = $this->getTransferErrorReasonCode($status);
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_UNKNOWN;
        }

        $this->CI->utils->debug_log('---------- JQ result for depositOrWithdraw ----------', $array_result);
        return array($success, $result);
    }

    public function depositToGame($player_name, $amount, $transfer_secure_id=null) {
        return $this->depositOrWithdraw($player_name, $this->dBtoGameAmount($amount), $transfer_secure_id, self::TRANSACTION_DEPOSIT);
    }


    public function withdrawFromGame($player_name, $amount, $transfer_secure_id=null,$notRecordTransaction=false) {
        return $this->depositOrWithdraw($player_name, $this->dBtoGameAmount($amount), $transfer_secure_id, self::TRANSACTION_WITHDRAW);
    }

    public function getTransferErrorReasonCode($errorCode) {
        switch ($errorCode) {
            case self::JQ_REASON_AGENT_NO_BALANCE:
                $reasonCode = parent::REASON_NO_ENOUGH_CREDIT_IN_SYSTEM;
                break;
            case self::JQ_REASON_NO_ENOUGH_BALANCE:
                $reasonCode = parent::REASON_NO_ENOUGH_BALANCE;
                break;
            default:
                $reasonCode = self::REASON_UNKNOWN;
        }

        return $reasonCode;
    }


    public function queryTransaction($transfer_id, $extra) {
        return $this->returnUnimplemented();
    }

    public function queryForwardGame($player_name, $extra) {

        $game_username = $this->getGameUsernameByPlayerUsername($player_name);
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryForwardGame',
            'player_name' => $player_name,
        );

        $language = $this->getSystemInfo('language', $extra['language']);
        $language = $this->getLauncherLanguage($language);

        $data = [
            'account' => $game_username,
            'upperName' => $this->uppername,
            'agentAccount' => $this->subagent,
            'gameCode' => $extra['game_code'],
            'device' => $extra['is_mobile'] ? 'mobile' : 'pc',
            'userIp' => $this->CI->utils->getIP(),
            'language' => $language
        ];
        $this->CI->utils->debug_log('---------- JQ queryForwardGame ----------', $data);

        return $this->callApi(self::API_queryForwardGame, $data, $context);
    }

    public function processResultForQueryForwardGame($params){
        $player_name = $this->getVariableFromContext($params, 'player_name');
        $response_result_id = $this->getResponseResultIdFromParams($params);
        $array_result = $this->getResultJsonFromParams($params);
        $result = [];
        $success = $this->processResultBoolean($response_result_id, $array_result, $player_name);

        if($success){
            $result['url'] = $array_result['data'];
        }

        $this->CI->utils->debug_log('---------- JQ result for queryForwardGame ----------', $array_result);
        return array($success, $result);
    }

    public function getLauncherLanguage($currentLang) {
        switch ($currentLang) {
            case 'zh-cn':
            case LANGUAGE_FUNCTION::INT_LANG_CHINESE:
                $language = 'zh_CN';
                break;
            case 'vi-vn':
            case LANGUAGE_FUNCTION::INT_LANG_VIETNAMESE:
                $language = 'vi_VN';
                break;
            case 'th-th':
            case LANGUAGE_FUNCTION::INT_LANG_THAI:
                $language = 'th_TH';
                break;
            default:
                $language = 'zh_CN';
                break;
        }
        return $language;
    }

    public function syncOriginalGameLogs($token) {
        $start_date_time = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $end_date_time = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

        $start_date_time = new DateTime($this->serverTimeToGameTime($start_date_time->format('Y-m-d H:i:s')));
        $end_date_time = new DateTime($this->serverTimeToGameTime($end_date_time->format('Y-m-d H:i:s')));

        $start_date_time->modify($this->getDatetimeAdjust());

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForSyncOriginalGameLogs'
        );

        $start_at = clone $start_date_time;
        $query_key = null;
        $api_result = [];
        $now = new DateTime();
        do {

            do {
                sleep($this->sync_sleep_time);
                $end_at = clone $start_at;
                $end_at = $end_at->modify($this->sync_interval);
                if($end_at > $end_date_time) {
                    $end_at = clone $end_date_time;
                }

                $data = [
                    'agentAccount' => $this->subagent,
                    'upperName' => $this->uppername,
                    'start' => $start_at->format('Y/m/d H:i:s'),
                ];

                $diff = date_diff($start_at, $now);

                if($diff->format('%a') < 30) {

                    $context = array(
                        'callback_obj' => $this,
                        'callback_method' => 'processResultForSyncGameRecords',
                    );
                    $data['end'] = $end_at->format('Y/m/d H:i:s');
                    if(!empty($query_key)) {
                        $data['direction'] = 'next';
                        $data['quertKey'] = $query_key;
                    }
                    $api_result = $this->callApi(self::API_syncGameRecords, $data, $context);
                    $query_key = $api_result['last_key'];
                }
                else {
                    $context = array(
                        'callback_obj' => $this,
                        'callback_method' => 'processResultForSyncGameRecordsOlderData',
                    );
                    if($start_at->format('H') % 2 != 0) {
                        $start_at->modify('-1 hour');
                    }
                    $data['start'] = $start_at->format('Y/m/d_H');
                    $api_result = $this->callApi(self::API_syncGameRecordsOlderData, $data, $context);
                }

            }
            while($api_result['has_next']);

            $diff = date_diff($start_at, $now);

            if($diff->format('%a') < 30) {
                $start_at = $start_at->modify($this->sync_interval);
                $query_key = null;
            }
            else {
                $start_at = $start_at->modify($this->sync_interval_older);
                $query_key = null;
            }
        }
        while($start_at <= $end_date_time);

        return array('success' => true, 'sync_details' => $api_result);
    }

    public function processResultForSyncGameRecordsOlderData($params) {
        $this->CI->load->model(array('original_game_logs_model'));
        $response_result_id = $this->getResponseResultIdFromParams($params);
        $array_result = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($response_result_id, $array_result, null, self::API_syncGameRecords);



        $game_records_url = !empty($array_result['data']) && gettype($array_result['data']) === 'string' ? $array_result['data'] : null;
        $game_records = [];
        if($game_records_url !== null) {
            $game_records = json_decode(file_get_contents($game_records_url), true);
        }

        $result = array(
            'data_count' => 0,
            'data_count_insert'=> 0,
            'data_count_update'=> 0,
            'has_next' => false,
            'last_key' => null,
        );

        if($success && !empty($game_records)) {

            $this->CI->utils->debug_log('---------- JQ arrayResult ----------', $array_result);

            $game_records = $this->preProcessGameRecords($game_records, $response_result_id);

            list($insert_rows, $update_rows)=$this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                $this->original_logs_table_name,
                $game_records,
                'external_uniqueid',
                'external_uniqueid',
                self::MD5_FIELDS_FOR_ORIGINAL,
                'md5_sum',
                'id',
                self::MD5_FLOAT_AMOUNT_FIELDS
            );
            $this->CI->utils->debug_log('after process available rows', 'gamerecords ->',count($game_records), 'insertrows->',count($insert_rows), 'updaterows->',count($update_rows));
            if (!empty($insert_rows)) {
                $result['data_count_insert'] = $this->updateOrInsertOriginalGameLogs($insert_rows, 'insert');
                $result['data_count'] += $result['data_count_insert'];
            }
            unset($insert_rows);

            if (!empty($update_rows)) {
                $result['data_count_update'] += $this->updateOrInsertOriginalGameLogs($update_rows, 'update');
                $result['data_count'] += $result['data_count_update'];
            }
            unset($update_rows);

        }

        return array($success, $result);
    }

    public function processResultForSyncGameRecords($params) {
        $this->CI->load->model(array('original_game_logs_model'));
        $response_result_id = $this->getResponseResultIdFromParams($params);
        $array_result = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($response_result_id, $array_result, null, self::API_syncGameRecords);
        $game_records = !empty($array_result['data']) ? $array_result['data'] : [];
        $result = array(
            'data_count' => 0,
            'data_count_insert'=> 0,
            'data_count_update'=> 0,
            'has_next' => false,
            'last_key' => null,
        );

        if($success && !empty($game_records['betHistory'])) {

            $this->CI->utils->debug_log('---------- JQ arrayResult ----------', $array_result);

            $game_records = $this->preProcessGameRecords($game_records['betHistory'], $response_result_id);

            list($insert_rows, $update_rows)=$this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                $this->original_logs_table_name,
                $game_records,
                'external_uniqueid',
                'external_uniqueid',
                self::MD5_FIELDS_FOR_ORIGINAL,
                'md5_sum',
                'id',
                self::MD5_FLOAT_AMOUNT_FIELDS
            );
            $this->CI->utils->debug_log('after process available rows', 'gamerecords ->',count($game_records), 'insertrows->',count($insert_rows), 'updaterows->',count($update_rows));
            if (!empty($insert_rows)) {
                $result['data_count_insert'] = $this->updateOrInsertOriginalGameLogs($insert_rows, 'insert');
                $result['data_count'] += $result['data_count_insert'];
            }
            unset($insert_rows);

            if (!empty($update_rows)) {
                $result['data_count_update'] += $this->updateOrInsertOriginalGameLogs($update_rows, 'update');
                $result['data_count'] += $result['data_count_update'];
            }
            unset($update_rows);

            $result['has_next'] = $array_result['data']['pageable']['hasNext'];
            $result['last_key'] = $array_result['data']['pageable']['lastKey'];

        } else{
            $result['has_next'] = false;
            $result['last_key'] = null;
        }

        return array($success, $result);
    }

    public function preProcessGameRecords(&$game_records, $response_result_id) {
        $new_game_records = [];
        if(!empty($game_records)){
            foreach($game_records as $index => $record) {
                $data['bet_id'] = isset($record['betId']) ? $record['betId'] : null;
                $data['game_name'] = isset($record['gameName']) ? $record['gameName'] : null;
                $data['game_code'] = isset($record['gameCode']) ? $record['gameCode'] : null;
                $data['account'] = isset($record['account']) ? $record['account'] : null;
                $data['bet_time'] = isset($record['betTime']) ? date('Y-m-d H:i:s', $record['betTime'] / 1000) : null;
                $data['bet_level'] = isset($record['betLevel']) ? $record['betLevel'] : null;
                $data['bet_amount'] = isset($record['betAmount']) ? $record['betAmount'] : null;
                $data['total_bet_amount'] = isset($record['totalBetAmount']) ? $record['totalBetAmount'] : null;
                $data['total_valid_bet'] = isset($record['totalValidBetAmount']) ? $record['totalValidBetAmount'] : null;
                $data['net_profit'] = isset($record['netProfit']) ? $record['netProfit'] : null;
                $data['serial_number'] = isset($record['serialNumber']) ? $record['serialNumber'] : null;
                $data['status'] = isset($record['status']) ? $record['status'] : null;
                $data['game_type'] = isset($record['gameType']) ? $record['gameType'] : null;
                $data['start_balance'] = isset($record['startBalance']) ? $record['startBalance'] : null;
                $data['end_balance'] = isset($record['endBalance']) ? $record['endBalance'] : null;
                $data['reel_type'] = isset($record['reelType']) ? $record['reelType'] : null;

                $data['external_uniqueid'] = $data['bet_id'] . '-' . $data['serial_number'];
                $data['response_result_id'] = $response_result_id;
                $new_game_records[] = $data;
                unset($data);

            }
        }
        return $new_game_records;
    }

    private function updateOrInsertOriginalGameLogs($data, $queryType){
        $dataCount=0;
        if(!empty($data)){
            foreach ($data as $record) {
                if ($queryType == 'update') {
                    $record['updated_at'] = $this->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->updateRowsToOriginal($this->original_logs_table_name, $record);
                } else {
                    unset($record['id']);
                    $record['created_at'] = $this->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->insertRowsToOriginal($this->original_logs_table_name, $record);
                }
                $dataCount++;
                unset($record);
            }
        }
        return $dataCount;
    }

    public function syncMergeToGameLogs($token) {
        $enabled_game_logs_unsettle = false;
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
        $table = $this->original_logs_table_name;
        $sqlTime='original.updated_at >= ? and original.updated_at <= ?';

        if($use_bet_time) {
            $sqlTime='original.bet_time >= ? and original.bet_time <= ?';
        }

        $sql = <<<EOD
SELECT
    original.id as sync_index,
    original.bet_id as round_id,
    original.game_code,
    original.account as username,
    original.bet_time,
    original.total_bet_amount as real_betting_amount,
    original.total_valid_bet as valid_bet,
    original.net_profit as result_amount,
    original.end_balance as after_balance,

    original.external_uniqueid,
    original.updated_at,
    original.md5_sum,
    original.response_result_id,

    game_provider_auth.login_name as player_username,
    game_provider_auth.player_id,

    game_description.id as game_description_id,
    game_description.game_name as game_description_name,
    game_description.game_type_id

FROM
    {$table} as original
    LEFT JOIN game_description ON original.game_code = game_description.external_game_id AND game_description.game_platform_id = ?
    LEFT JOIN game_type ON game_description.game_type_id = game_type.id
    JOIN game_provider_auth ON original.account = game_provider_auth.login_name and game_provider_auth.game_provider_id = ?
WHERE

{$sqlTime}

EOD;

        $params = [
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo
        ];

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $result;
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

        $data = [
            'game_info' => [
                'game_type_id'          => $row['game_type_id'],
                'game_description_id'   => $row['game_description_id'],
                'game_code'             => $row['game_code'],
                'game_type'             => null,
                'game'                  => $row['game_description_name']
            ],
            'player_info' => [
                'player_id'             => $row['player_id'],
                'player_username'       => $row['player_username']
            ],
            'amount_info' => [
                'bet_amount'            => $row['valid_bet'],
                'result_amount'         => $row['result_amount'],
                'bet_for_cashback'      => $row['valid_bet'],
                'real_betting_amount'   => $row['real_betting_amount'],
                'win_amount'            => null,
                'loss_amount'           => null,
                'after_balance'         => $row['after_balance'],
            ],
            'date_info' => [
                'start_at'              => $row['bet_time'],
                'end_at'                => $row['bet_time'],
                'bet_at'                => $row['bet_time'],
                'updated_at'            => $row['updated_at']
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' =>  Game_logs::STATUS_SETTLED,
            'additional_info' => [
                'has_both_side'         => 0,
                'external_uniqueid'     => $row['external_uniqueid'],
                'round_number'          => $row['round_id'],
                'md5_sum'               => $row['md5_sum'],
                'response_result_id'    => $row['response_result_id'],
                'sync_index'            => $row['sync_index'],
                'bet_type'              => null
            ],
            'bet_details' => ['Created At' => $this->CI->utils->getNowForMysql()],
            'extra' => [],

            'game_logs_id' => isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];

        return $data;

    }

    /**
     *
     * perpare original rows, include process unknown game, pack bet details, convert game status
     *
     * @param  array &$row
     */
    public function preprocessOriginalRowForGameLogs(array &$row){
        if (empty($row['game_type_id'])) {
            list($row['game_description_id'], $row['game_type_id']) = $this->getGameDescriptionInfo($row, $this->getUnknownGame());
        }
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
        $game_type_id = $unknownGame->game_type_id;
        if (isset($row['game_description_id'])) {
            $game_description_id = $row['game_description_id'];
            $game_type_id = $row['game_type_id'];
        }

        if(empty($game_description_id)){
            $game_description_id = $this->CI->game_description_model->processUnknownGame($this->getPlatformCode(),
                $unknownGame->game_type_id, $row['game_code'], $row['game_code']);
        }

        return [$game_description_id, $game_type_id];
    }

}
