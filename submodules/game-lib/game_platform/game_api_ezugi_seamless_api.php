<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
require_once dirname(__FILE__) . '/year_month_table_module.php';

/**
 * EZUGI Integration
 * OGP-23012
 *
 * @author  Sony
 */

class Game_api_ezugi_seamless_api extends Abstract_game_api {
    use Year_month_table_module;

    const ORIGINAL_TRANSACTION_TABLE = 'common_seamless_wallet_transactions';
    const EZUGI_WALLET_TRANSACTION_TABLE = 'ezugi_seamless_wallet_transactions'; //OGP-28820


    public $key;
    public $currency;
    public $operator_id;
    public $use_ezugi_seamless_wallet_transactions;
    public $original_transaction_table;
    private $url;
    public $force_language;
    public $launcher_mode;

    protected $home_link;
    public $force_disable_home_link;
    private $gameLogsStatus;

    // monthly transactions table
    public $initialize_monthly_transactions_table = true;
    public $use_monthly_transactions_table = false;
    public $force_check_previous_transactions_table = false;
    public $force_check_other_transactions_table = false;
    public $previous_table = null;

    public function __construct() {
        parent::__construct();
        

        $this->key = $this->getSystemInfo('key', null);
        $this->currency = $this->getSystemInfo('currency', 'BRL');
        $this->operator_id = $this->getSystemInfo('operator_id', null);
        $this->url = $this->getSystemInfo('url');
        $this->use_ezugi_seamless_wallet_transactions = $this->getSystemInfo('use_ezugi_seamless_wallet_transactions', false);

        if($this->use_ezugi_seamless_wallet_transactions){
            $this->original_transaction_table = self::EZUGI_WALLET_TRANSACTION_TABLE;
            $this->ymt_init();
        }else{
            $this->original_transaction_table = self::ORIGINAL_TRANSACTION_TABLE;
        }

        $this->enable_home_link = $this->getSystemInfo('enable_home_link', true);
        $this->home_link = $this->getSystemInfo('home_link', '');

        $this->enable_delete_token = $this->getSystemInfo('enable_delete_token', true);
        $this->enable_merging_rows = $this->getSystemInfo('enable_merging_rows', false);
        $this->force_language = $this->getSystemInfo('force_language', false);
        $this->force_disable_home_link = $this->getSystemInfo('force_disable_home_link', false);

        
		$this->gameLogsStatus = [
            'ok' => GAME_LOGS::STATUS_PENDING,
            'processed' => GAME_LOGS::STATUS_SETTLED,
            'rollback' => GAME_LOGS::STATUS_REFUND,
        ];

    }

    const TRANSACTION_CREDIT = 'credit';
    const TRANSACTION_DEBIT = 'debit';
    const TRANSACTION_ROLLBACK = 'rollback';

    const API_triggerInternalPayoutRound = 'triggerInternalPayoutRound';
    const URI_MAP = [
        self::API_triggerInternalPayoutRound => 'ezugi_seamless_service_api/{game_platform_id}/credit',
    ];

    const MD5_FIELDS_FOR_MERGE = [        
        'after_balance',
        'round_id',
        'game_id',
        'start_at',
        'end_at',
        'updated_at',
        'status'
    ];

    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE = [
        'amount',
    ];

    public function ymt_init() {
        // start monthly tables
        $this->initialize_monthly_transactions_table = $this->getSystemInfo('initialize_monthly_transactions_table', true);
        $this->use_monthly_transactions_table = $this->getSystemInfo('use_monthly_transactions_table', false);
        $this->force_check_previous_transactions_table = $this->getSystemInfo('force_check_previous_transactions_table', false);
        $this->force_check_other_transactions_table = $this->getSystemInfo('force_check_other_transactions_table', false);

        $this->ymt_initialize($this->original_transaction_table, $this->use_monthly_transactions_table ? $this->use_monthly_transactions_table : $this->initialize_monthly_transactions_table);

        if ($this->use_monthly_transactions_table) {
            $this->original_transaction_table = $this->ymt_get_current_year_month_table();
            $this->previous_table = $this->ymt_get_previous_year_month_table();
        }
        // end monthly tables
    }

    public function isSeamLessGame(){
        return true;
    }

    public function getPlatformCode() {
        return EZUGI_SEAMLESS_API;
    }

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
        $return = $this->createPlayerInDB($playerName, $playerId, $password, $email, $extra);
        $success = false;
        $message = "Unable to create account";
        if($return){
            $success = true;
            $this->setGameAccountRegistered($playerId);
            $message = "Successfully created account";
        }

        return array("success" => $success, "message" => $message);
    }

    public function isPlayerExist($playerName) {
        return ['success'=>true, 'exists'=>$this->isPlayerExistInDB($playerName)];
    }

    public function generateUrl($apiName, $params) {
        if($apiName == self::API_triggerInternalPayoutRound) {
            $path = str_replace('{game_platform_id}', $this->getPlatformCode(), self::URI_MAP[self::API_triggerInternalPayoutRound]);
            $url = $this->CI->utils->getSystemUrl('admin', $path);
            return $url;
        }
        return $this->returnUnimplemented();
    }

    protected function customHttpCall($ch, $params) {
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt( $ch, CURLOPT_AUTOREFERER, true );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
    }

    public function getHttpHeaders($params) {
        $headers = array(
            'Hash' => $this->generateHash($params)
        );
        return $headers;
    }


    private function generateHash($params) {
        $key = $this->key;
        $hash = base64_encode(hash_hmac('sha256', json_encode($params), $key, true));
        return $hash;
    }

    public function queryPlayerBalance($playerName) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
        $useReadonly = true;
        $balance = $this->CI->player_model->getPlayerSubWalletBalance($playerId, $this->getPlatformCode(), $useReadonly);

        $result = array(
            'success' => true,
            'balance' => $balance
        );

        return $result;
    }

    public function depositToGame($userName, $amount, $transfer_secure_id = null){
        return array(
            'success' => true,
            'external_transaction_id' => $transfer_secure_id,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_APPROVED,
            'response_result_id ' => NULL,
            'didnot_insert_game_logs'=> true,
        );
    }

    public function withdrawFromGame($userName, $amount, $transfer_secure_id = null){
        return array(
            'success' => true,
            'external_transaction_id' => $transfer_secure_id,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_APPROVED,
            'response_result_id ' => NULL,
            'didnot_insert_game_logs'=> true,
        );
    }

    public function getLauncherLanguage($language){
        $lang='';

        $language = strtolower($language);
        switch ($language) {
            case Language_function::INT_LANG_ENGLISH:
            case 'en':
            case 'en-us':
            case 'en-US':
                $lang = 'en'; // english
                break;
            case Language_function::INT_LANG_CHINESE:
            case 'zh-cn':
                $lang = 'zh'; // chinese
                break;
            case Language_function::INT_LANG_INDONESIAN:
            case 'id-id':
                $lang = 'in';
                break;
            case Language_function::INT_LANG_VIETNAMESE:
            case 'vi-vn':
                $lang = 'vi';
                break;
            case Language_function::INT_LANG_KOREAN:
            case 'ko-kr':
                $lang = 'ko';
                break;
            case Language_function::INT_LANG_INDIA:
            case 'hi-in':
                $lang = 'hi';
                    break;
            case Language_function::INT_LANG_PORTUGUESE:
            case 'pt':
            case 'pt-br':
            case 'pt-BR':
            case 'pt-pt':
                $lang = 'pt';
                break;
            case Language_function::INT_LANG_SPANISH:
            case 'es':
            case 'es-es':
                $lang = 'es';
                break;
            case Language_function::INT_LANG_JAPANESE:
            case 'ja':
            case 'ja-JP':
            case 'ja-jp':
                $lang = 'ja';
                break;
            default:
                $lang = 'en'; // default as english
                break;
        }
        return $lang;
    }

    public function queryForwardGame($playerName,$extra=[]) {
        $this->CI->load->model('common_token');
        $player_id = $this->getPlayerIdFromUsername($playerName);
        // list($token, $sign_key) = $this->CI->common_token->createTokenWithSignKeyBy($player_id, 'player_id', 60); // 1 minute expiration
        $token = $this->getPlayerTokenByUsername($playerName);
        // $playerName = $this->getGameUsernameByPlayerUsername($playerName);
        $language = $this->getLauncherLanguage($this->getSystemInfo('language', $extra['language']));

        if ($this->force_language !== false) {
            $language = $this->force_language;
        }

        $context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryForwardGame',
			'playerName' => $playerName,
		);

        $params = [
            'operatorId' => $this->operator_id,
            'token' => $token,
            'language' => $language,
        ];

        if(!empty($extra['game_code'])) {
            $params['openTable'] = $extra['game_code'];
        }

        if($this->enable_home_link){
            $params['homeUrl'] = $this->getHomeLinkBy($extra['is_mobile']);

            if (isset($extra['extra']['home_link'])) {
                $params['homeUrl'] = $extra['extra']['home_link'];
            }

            if (isset($extra['extra']['cashier_link'])) {
                $params['cashierUrl'] = $extra['extra']['cashier_link'];
            }
        }

        #removes homeUrl if disable_home_link is set to TRUE
        if(isset($extra['extra']['disable_home_link']) && $extra['extra']['disable_home_link']) {
            unset($params['homeUrl']);
        }
        
        if($this->force_disable_home_link){
            unset($params['homeUrl']);
        }

        if(empty($token)){
            $url = $this->home_link;
            $success = false;
        }else{
            $url = $this->url . '?' . http_build_query($params);
            $success = true;
        }

        $this->CI->utils->debug_log('Ezugi queryForwardGame url: ', $url, $params);

        return [
            'success' => $success,
            'url' => $url
        ];

        return $this->callApi(self::API_queryForwardGame, $params, $context);

    }

    public function queryTransaction($transactionId, $extra) {
        return $this->returnUnimplemented();
    }

    public function syncOriginalGameLogs($token = false) {
        return $this->returnUnimplemented();
    }

    public function syncMergeToGameLogs($token) {
        $enabled_game_logs_unsettle=true;
        if($this->enable_merging_rows){
            return $this->commonSyncMergeToGameLogs($token,
                $this,
                [$this, 'queryDistinctRounds'],
                [$this, 'makeParamsForInsertOrUpdateGameLogsRow'],
                [$this, 'preprocessOriginalRowForGameLogs'],
                $enabled_game_logs_unsettle
            );
        }
        return $this->commonSyncMergeToGameLogs($token,
            $this,
            [$this, 'queryOriginalGameLogs'],
            [$this, 'makeParamsForInsertOrUpdateGameLogsRow'],
            [$this, 'preprocessOriginalRowForGameLogs'],
            $enabled_game_logs_unsettle);
    }

    public function queryDistinctRounds($dateFrom, $dateTo, $use_bet_time){
        if ($this->use_ezugi_seamless_wallet_transactions && $this->use_monthly_transactions_table) {
            $this->original_transaction_table = $this->ymt_get_year_month_table_by_date(null, $dateFrom);
            $this->previous_table = $this->ymt_get_previous_year_month_table(null, $dateFrom);
        }

        $query = function ($table_name) use ($dateFrom, $dateTo, $use_bet_time) {
            $sqlTime="ezugi.updated_at >= ? AND ezugi.updated_at <= ? AND ezugi.game_platform_id = ? and ezugi.round_id is not null";

$sql = <<<EOD
SELECT
DISTINCT(ezugi.round_id),
ezugi.player_id
FROM {$table_name} as ezugi
WHERE
{$sqlTime}
EOD;
            $params=[
                $dateFrom,
                $dateTo,
                $this->getPlatformCode()
            ];
            $this->CI->utils->debug_log('query distinct bet transacstion ezugi merge sql', $sql, $params);
            /* $round_ids = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);

            $results = array_column($round_ids, 'round_id'); */

            $results = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);

            return $results;
        };

        $results = $query($this->original_transaction_table);

        if ($this->use_ezugi_seamless_wallet_transactions && $this->use_monthly_transactions_table) {
            if ($this->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
                if (empty($results)) {
                    $results = $query($this->previous_table);
                }
            }
        }

        if(!empty($results)){
            $this->preProcessResults($results);
            return $results;
        }

        return [];
    }

    private function preProcessResults( array &$results){
        if(!empty($results)){
            foreach ($results as $key => $result) {
                $player_id = $result['player_id'];
                $round_id = $result['round_id'];
                $rows = $this->queryTransactions($player_id, $round_id);
                $last_row = end($rows);
                $bet_amount = 0;
                $result_amount = 0;
                array_walk($rows, function($data, $key) use(&$bet_amount, &$result_amount) {
                    if($data['transaction_type'] == "debit"){
                        $bet_amount += abs($data['amount']);
                    }
                    $result_amount += $data['amount'];
                });

                $bet_details = [
                    'bet' => $rows[0]['extra_info'],
                    'credit' => $last_row['extra_info'],
                ];

                $round_data = array(
                    "sync_index" => $last_row['sync_index'],
                    "start_at" => $rows[0]['start_at'],
                    "end_at" => $last_row['end_at'],
                    "after_balance" => $last_row['after_balance'],
                    "status" => $last_row['status'],
                    "player_id" => $last_row['player_id'],
                    "round_id" => $last_row['round_number'],
                    // "external_uniqueid" => $last_row['external_unique_id'],
                    /*last external_unique_id might change time to time during sync
                    instead used roundid and player id as unique*/
                    "external_uniqueid" => $player_id."-".$round_id,
                    "bet_amount" => $bet_amount,
                    "result_amount" => $result_amount,
                    "game_description_id" => null,
                    "game_description_name" => null,
                    "game_code" => null,
                    "game_type_id" => null,
                    "transaction_type" => null,
                    "response_result_id" => null,
                    "updated_at" => $last_row['updated_at'],
                    "bet_details" => $bet_details,
                );
                $round_data['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($round_data, ['external_uniqueid', 'end_at', 'status'], ['bet_amount', 'result_amount']);

                $extra_info = $last_row['extra_info'];
                if(!is_array($extra_info)){
                    $extra_info = json_decode($extra_info, true);
                }
                $round_data['table_id'] = $extra_info['tableId'];
                $this->CI->load->model('game_description_model');
                $game_details = $this->CI->game_description_model->getGameDetailsByExternalGameIdAndGamePlatform($this->getPlatformCode(), $round_data['table_id'], true);
                if(!empty($game_details)){
                    $round_data['game_description_id'] = $game_details['game_description_id'];
                    $round_data['game_description_name'] = $game_details['game_name'];
                    $round_data['game_code'] = $game_details['game_code'];
                    $round_data['game_type_id'] = $game_details['game_type_id'];
                    $round_data['game_english_name'] = isset($game_details['english_name']) ? $game_details['english_name'] : null;
                }
                $results[$key] = $round_data;
            }
        }
    }

    private function queryTransactions($player_id, $round_id){
        $this->CI->load->model('original_game_logs_model');

        $query = function ($table_name) use ($player_id, $round_id) {
            $sql = <<<EOD
SELECT
ezugi.id as sync_index,
ezugi.transaction_type,
ezugi.start_at as start_at,
ezugi.end_at as end_at,
ezugi.external_unique_id,
ezugi.round_id as round_number,
ezugi.after_balance, 
ezugi.status,
ezugi.amount,
ezugi.game_id as game_id,
ezugi.game_id as game_name,
ezugi.game_id as game_code,
ezugi.player_id,
ezugi.extra_info,
ezugi.updated_at

FROM {$table_name} as ezugi
WHERE
ezugi.player_id = ? and ezugi.round_id = ? and ezugi.game_platform_id = ?
EOD;

            $params=[
                $player_id,
                $round_id,
                $this->getPlatformCode()
            ];

            $this->CI->utils->debug_log('ezugi queryBet sql', $sql, $params);

            $rows = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);

            return $rows;
        };

        $rows = $query($this->original_transaction_table);

        if ($this->use_ezugi_seamless_wallet_transactions && $this->use_monthly_transactions_table) {
            if ($this->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
                if (empty($rows)) {
                    $rows = $query($this->previous_table);
                }
            }
        }

        return $rows;
    }

    private function preProcessRoundIds( array &$round_ids){
        if(!empty($round_ids)){
            foreach ($round_ids as $key => $round_id) {
                $rows = $this->queryAllRows($round_id);
                $last_row = end($rows);
                $bet_amount = 0;
                $result_amount = 0;
                array_walk($rows, function($data, $key) use(&$bet_amount, &$result_amount) {
                    if($data['transaction_type'] == "debit"){
                        $bet_amount += abs($data['amount']);
                    }
                    $result_amount += $data['amount'];
                });

                $bet_details = [
                    'bet' => $rows[0]['extra_info'],
                    'credit' => $last_row['extra_info'],
                ];

                $round_data = array(
                    "sync_index" => $last_row['sync_index'],
                    "start_at" => $rows[0]['start_at'],
                    "end_at" => $last_row['end_at'],
                    "after_balance" => $last_row['after_balance'],
                    "status" => $last_row['status'],
                    "player_id" => $last_row['player_id'],
                    "round_id" => $last_row['round_number'],
                    "external_uniqueid" => $last_row['round_number'],
                    "bet_amount" => $bet_amount,
                    "result_amount" => $result_amount,
                    "game_description_id" => null,
                    "game_description_name" => null,
                    "game_code" => null,
                    "game_type_id" => null,
                    "transaction_type" => null,
                    "response_result_id" => null,
                    "updated_at" => $last_row['updated_at'],
                    "bet_details" => $bet_details,
                );
                $round_data['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($round_data, ['external_uniqueid', 'end_at', 'status'], ['bet_amount', 'result_amount']);

                $extra_info = $last_row['extra_info'];
                if(!is_array($extra_info)){
                    $extra_info = json_decode($extra_info, true);
                }
                $round_data['table_id'] = $extra_info['tableId'];
                $this->CI->load->model('game_description_model');
                $game_details = $this->CI->game_description_model->getGameDetailsByExternalGameIdAndGamePlatform($this->getPlatformCode(), $round_data['table_id'], true);
                if(!empty($game_details)){
                    $round_data['game_description_id'] = $game_details['game_description_id'];
                    $round_data['game_description_name'] = $game_details['game_name'];
                    $round_data['game_code'] = $game_details['game_code'];
                    $round_data['game_type_id'] = $game_details['game_type_id'];
                    $round_data['game_english_name'] = isset($game_details['english_name']) ? $game_details['english_name'] : null;
                }
                $round_ids[$key] = $round_data;
            }
        }
    }


    private function queryAllRows($round_id){
        $this->CI->load->model('original_game_logs_model');
        $sql = <<<EOD
SELECT
ezugi.id as sync_index,
ezugi.transaction_type,
ezugi.start_at as start_at,
ezugi.end_at as end_at,
ezugi.round_id as external_uniqueid,
ezugi.round_id as round_number,
ezugi.after_balance, 
ezugi.status,
ezugi.amount,
ezugi.game_id as game_id,
ezugi.game_id as game_name,
ezugi.game_id as game_code,
ezugi.player_id,
ezugi.extra_info,
ezugi.updated_at

FROM {$this->original_transaction_table} as ezugi
WHERE
ezugi.round_id = ?
and ezugi.game_platform_id = ?
EOD;

        $params=[
            $round_id,
            $this->getPlatformCode()
        ];

        $this->CI->utils->debug_log('ezugi queryBet sql', $sql, $params);
        return $rows = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    }

    /**
     * queryOriginalGameLogs
     * @param  string $dateFrom
     * @param  string $dateTo
     * @param  bool   $use_bet_time
     * @return array
     */
    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time) {
        // $table = $this->original_transaction_table; //'common_seamless_wallet_transactions';

        if ($this->use_ezugi_seamless_wallet_transactions && $this->use_monthly_transactions_table) {
            $this->original_transaction_table = $this->ymt_get_year_month_table_by_date(null, $dateFrom);
            $this->previous_table = $this->ymt_get_previous_year_month_table(null, $dateFrom);
        }
        
        $query = function($table) use ($dateFrom, $dateTo, $use_bet_time) {
            $sqlTime='transaction.updated_at >= ? and transaction.updated_at <= ?';

            if($use_bet_time) {
                $sqlTime='transaction.start_at >= ? and transaction.start_at <= ?';
            }
            $md5Fields = implode(", ", array('transaction.amount', 'transaction.after_balance', 'transaction.round_id', 'transaction.game_id', 'transaction.start_at', 'transaction.end_at', 'transaction.updated_at', 'transaction.status'));
            $sql = <<<EOD
SELECT
    transaction.id as sync_index,
    transaction.amount as bet_amount,
    transaction.amount as result_amount,
    transaction.after_balance,
    transaction.status,
    transaction.start_at,
    transaction.end_at,
    transaction.transaction_type,
    transaction.game_id,
    transaction.game_id as game_code,

    transaction.external_unique_id as external_uniqueid,
    transaction.updated_at,
    transaction.response_result_id,
    transaction.round_id,
    MD5(CONCAT({$md5Fields})) as md5_sum,
    CONCAT({$md5Fields}) as md5_string,
    transaction.extra_info,

    transaction.player_id


FROM
    {$table} as transaction
WHERE
transaction.transaction_type != 'rollback' and {$sqlTime} and transaction.game_platform_id = ?

EOD;

            $params = [
                $dateFrom,
                $dateTo,
                $this->getPlatformCode(),
            ];

            $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
            $this->CI->utils->debug_log("EZUGI SEAMLESS @queryOriginalGameLogs last_query: " . $this->CI->db->last_query());

            return $result;
        };

        $result = $query($this->original_transaction_table);

        if ($this->use_ezugi_seamless_wallet_transactions && $this->use_monthly_transactions_table) {
            if ($this->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
                if (empty($result)) {
                    $result = $query($this->previous_table);
                }
            }
        }

        //process game description
        $gameList = [];

        $this->CI->load->model('game_description_model');
        foreach($result as &$row){
            $extraInfo = $row['extra_info'];
            if(!is_array($extraInfo)){
                $extraInfo = json_decode($extraInfo, true);
            }
            $tableId = $extraInfo['tableId'];

            $row['game_description_id'] = null;
            $row['game_description_name'] = null;
            // $row['game_code'] = null;
            $row['game_type_id'] = null;

            if(!empty($tableId)){
                if(isset($gameList[$tableId])){
                    $row['game_description_id'] = $gameList[$tableId]['game_description_id'];
                    $row['game_description_name'] = $gameList[$tableId]['game_description_name'];
                    $row['game_code'] = $gameList[$tableId]['game_code'];
                    $row['game_type_id'] = $gameList[$tableId]['game_type_id'];
                }else{
                    //get gameBy external game id
                    $game = $this->CI->game_description_model->getGameDetailsByExternalGameIdAndGamePlatform($this->getPlatformCode(),$tableId, true);
                    if(!empty($game)){
                        $gameList[$tableId] = [];
                        $gameList[$tableId]['game_description_id'] = $game['game_description_id'];
                        $gameList[$tableId]['game_description_name'] = $game['game_name'];
                        $gameList[$tableId]['game_code'] = $game['game_code'];
                        $gameList[$tableId]['game_type_id'] = $game['game_type_id'];

                        $row['game_description_id'] = $game['game_description_id'];
                        $row['game_description_name'] = $game['game_name'];
                        $row['game_code'] = $game['game_code'];
                        $row['game_type_id'] = $game['game_type_id'];
                    }else{
                        $row['game_code'] = $tableId; //if game not found, use table id as external_game_id and  game_code
                    }

                }
            }
            $row['md5_string'] .= $row['game_type_id'];#include game type id on md5 generating
            $row['md5_sum'] = md5($row['md5_string']);
        }
        
        $this->CI->utils->debug_log("EZUGI SEAMLESS @queryOriginalGameLogs", [
            '$result' => $result,
        ]);

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
        } else {
            if(empty($row['md5_sum'])){
                $this->CI->utils->error_log('no md5 on ', $row['external_uniqueid']);
                $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE,
                    self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
            }
        }
        $betDetails = $row;
        $bet_amount = ($row['transaction_type'] == self::TRANSACTION_DEBIT) ? abs($row['bet_amount']) : 0;
        if($this->enable_merging_rows){
            $bet_amount = $row['bet_amount'];
            $betDetails = $row['bet_details'];
        }

        $this->CI->utils->debug_log("EZUGI SEAMLESS @makeParamsForInsertOrUpdateGameLogsRow", [
            '$row' => $row,
        ]);
        
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
                'player_username'       => null
            ],
            'amount_info' => [
                'bet_amount'            => $bet_amount,
                'result_amount'         => $row['result_amount'],
                'bet_for_cashback'      => $bet_amount,
                'real_betting_amount'   => $bet_amount,
                'win_amount'            => null,
                'loss_amount'           => null,
                'after_balance'         => $row['after_balance'],
            ],
            'date_info' => [
                'start_at'              => $row['start_at'],
                'end_at'                => $row['end_at'],
                'bet_at'                => $row['start_at'],
                'updated_at'            => $row['updated_at']
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => $row['status'],
            'additional_info' => [
                'has_both_side'         => 0,
                'external_uniqueid'     => $row['external_uniqueid'],
                'round_number'          => $row['round_id'],
                'md5_sum'               => $row['md5_sum'],
                'response_result_id'    => $row['response_result_id'],
                'sync_index'            => $row['sync_index'],
                'bet_type'              => null
            ],
            'bet_details' => $this->formatBetDetails($row),
            'extra' => [],

            'game_logs_id' => isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];

       
        $this->CI->utils->debug_log("EZUGI SEAMLESS @makeParamsForInsertOrUpdateGameLogsRow", [
            '$data' => $data,
        ]);
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

        $status = $row['status'];
        switch($status) {
            case 'ok':
            case 'processed':
                $row['status'] = Game_logs::STATUS_SETTLED;
                if($this->enable_merging_rows){
                    if($status == "processed"){
                        $row['status'] = Game_logs::STATUS_PENDING;
                    }
                }
                break;
            case 'rollback':
                $row['status'] = Game_logs::STATUS_CANCELLED;
                break;
            default:
                $row['status'] = Game_logs::STATUS_PENDING;
                break;
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
        $game_type_id = null;
        if (isset($row['game_description_id'])) {
            $game_description_id = $row['game_description_id'];
            $game_type_id = $row['game_type_id'];
        }

        if(empty($game_description_id)){
            $game_description_id=$this->CI->game_description_model->processUnknownGame($this->getPlatformCode(),
            $unknownGame->game_type_id, $row['game_code'], $row['game_code']);
            $game_type_id = $unknownGame->game_type_id;
        }

        return [$game_description_id, $game_type_id];
    }


    public function triggerInternalPayoutRound($transaction) {
        $this->CI->load->model('common_token');
        $this->CI->utils->debug_log('EZUGI SEAMLESS (triggerInternalPayoutRound)', 'transaction', $transaction);

        $transaction = json_decode($transaction);
        $transaction->token = $this->CI->common_token->getPlayerCommonTokenByGameUsername($transaction->uid);
        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForTriggerInternalPayoutRound',
            'gameUsername' => $transaction->uid,
            'transaction' => $transaction
        ];

        return $this->callApi(self::API_triggerInternalPayoutRound, $transaction, $context);
    }

    public function processResultForTriggerInternalPayoutRound($params){
        $resultArr = $this->getResultJsonFromParams($params);

        $this->CI->utils->debug_log('EZUGI SEAMLESS (processResultForTriggerInternalPayoutRound)', $params, $resultArr);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $transaction = $this->getVariableFromContext($params, 'transaction');
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $apiName = $this->getVariableFromContext($params, 'apiName');
        $success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername, $apiName);
        $result = array(
            'gameUsername' => $gameUsername,
            'transaction' => $transaction,
            'status' => null,
            'exists' => null,
            'triggered_payout' => false
        );
        if($success){
            $result['status'] = true;
            $result['triggered_payout'] = true;
            $success = true;
        }
        $result['message'] = isset($resultArr['errorDescription']) ? $resultArr['errorDescription'] : '';

        return [$success, $result];
    }

    public function processResultBoolean($responseResultId, $resultArr, $playerName = null, $apiName = '') {
        $success = false;
        if(isset($resultArr['errorCode']) && $resultArr['errorCode'] == 0){
            $success = true;
        }
        if (!$success) {
            $this->setResponseResultToError($responseResultId);

            $this->CI->utils->debug_log('EZUGI has error', $apiName, $responseResultId, 'playerName', $playerName, 'result', $resultArr);
        }
        return $success;
    }


    private function formatBetDetails($row){

        if($row['transaction_type'] == self::TRANSACTION_DEBIT){
            return  [
                'round_id' => $row['round_id'],
                'bet_type' => $row['transaction_type'],
                'bet_amount' => $row['bet_amount'],
                'betting_time' => $row['start_at'],
            ];
        }

        return  [
             'round_id' => $row['round_id'],
             'bet_type' => $row['transaction_type'],
             'win_amount' => $row['result_amount'],
             'betting_time' => $row['start_at'],
         ];
     }

    public function queryTransactionByDateTime($startDate, $endDate){
        $this->CI->load->model(array('original_game_logs_model'));

        $original_transactions_table = $this->original_transaction_table;

        if ($this->use_ezugi_seamless_wallet_transactions && $this->use_monthly_transactions_table) {
            $original_transactions_table = $this->ymt_get_year_month_table_by_date(null, $startDate);
        }

        if(!$original_transactions_table){
            $this->CI->utils->debug_log("queryTransactionByDateTime cannot get seamless transaction table", $this->getPlatformCode());
            return false;
        }

        $sql = <<<EOD
SELECT
t.player_id as player_id,
t.start_at transaction_date,
t.amount as amount,
t.after_balance as after_balance,
t.before_balance as before_balance,
t.round_id as round_no,
t.transaction_id as transaction_id,
t.external_unique_id as external_uniqueid,
t.transaction_type trans_type,
t.extra_info extra_info
FROM {$original_transactions_table} as t
WHERE t.game_platform_id = ? and `t`.`updated_at` >= ? AND `t`.`updated_at` <= ?
ORDER BY t.updated_at asc, t.id asc;
EOD;

        $params=[$this->getPlatformCode(),$startDate, $endDate];

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $result;
    }

    public function getTransactionsTable(){
        return $this->original_transaction_table;
    }

    /* public function defaultBetDetailsFormat($row) {
        $bet_details = [];
        if($this->enable_merging_rows){
            $bet = json_decode($row['bet'],true);
            $credit = json_decode($row['credit'],true);
            if($row){
                $bet_details = [
                    'bet_amount'    => $bet['debitAmount'],
                    'win_amount'   => $credit['creditAmount'],
                    'round_id'     => $bet['roundId'],
                    'bet_id'        => $bet['transactionId'],
                    'table_id'     => $bet['tableId'],
                    'others'        => $row
                ];
            }
        }else{
            $bet_amount = ($row['transaction_type'] == self::TRANSACTION_DEBIT) ? abs($row['bet_amount']) : 0;
            $bet_details['bet_amount'] = $bet_amount;
            if (isset($row['game_name'])) {
                $bet_details['game_name '] = $row['game_name'];
            }
            if (isset($row['round_number'])) {
                $bet_details['round_id'] = $row['round_number'];
            }
            if (isset($row['external_uniqueid'])) {
                $bet_details['bet_id'] = $row['external_uniqueid'];
            }
            if (isset($row['start_at'])) {
                $bet_details['betting_datetime'] = $row['start_at'];
            }
        }
        return $bet_details;
     } */

    public function getUnsettledRounds($dateFrom, $dateTo){
        $sqlTime='`created_at` >= ? AND `created_at` <= ?';

        $this->CI->load->model(array('original_game_logs_model'));
        $this->original_transactions_table = $this->getTransactionsTable();
        $pendingStatus = 'ok';
        $transType = 'debit';

        $sql = <<<EOD
SELECT 
original.round_id, original.transaction_id, game_platform_id
from {$this->original_transactions_table} as original
where
status=?
and transaction_type=?
and {$sqlTime}
EOD;


        $params=[
            $pendingStatus,
            $transType,
            $dateFrom,
            $dateTo
		];
        $platformCode = $this->getPlatformCode();
	    $this->CI->utils->debug_log('EZUGI SEAMLESS-' .$platformCode.' (getUnsettledRounds)', 'params',$params,'sql',$sql);
        return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    }

    public function checkBetStatus($data){
        $this->CI->load->model(['seamless_missing_payout']);
        $this->original_transactions_table = $this->getTransactionsTable();

        $roundId = $data['round_id'];
        $transactionId = $data['transaction_id'];
        $pendingStatus = 'ok';
        $transStatus = $this->gameLogsStatus[$pendingStatus];
        $baseAmount = 0;
     
        $sql = <<<EOD
SELECT 
original.start_at as transaction_date,
original.transaction_type,
original.status,
original.game_platform_id,
original.player_id,
original.round_id,
original.transaction_id,
ABS(SUM(original.amount)) as amount,
ABS(SUM(original.amount)) as deducted_amount,
gd.id as game_description_id,
gd.game_type_id,
original.external_unique_id as external_uniqueid
from {$this->original_transactions_table} as original
left JOIN game_description as gd ON original.game_id = gd.external_game_id and gd.game_platform_id=?
where
round_id=? and transaction_id=? and original.game_platform_id=?
EOD;
        
        $params=[$this->getPlatformCode(), $roundId, $transactionId, $this->getPlatformCode()];

        $transactions  = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        
        foreach($transactions as $transaction){
            if($transaction['game_platform_id']){
                $transaction['transaction_status'] = $transStatus;
                $transaction['added_amount'] = $baseAmount;
                $transaction['status'] = Seamless_missing_payout::NOT_FIXED;

                $result = $this->CI->original_game_logs_model->insertIgnoreRowsToOriginal('seamless_missing_payout_report',$transaction);
                if($result===false){
                    $this->CI->utils->error_log('EVOLUTION SEAMLESS-' .$this->getPlatformCode().'(checkBetStatus) Error insert missing payout', $transaction);
                }
            }
        }
        
        if(empty($trans)){
            return array('success'=>false, 'exists'=>false);
        }
    }

    public function queryBetTransactionStatus($game_platform_id, $external_uniqueid){
        $this->CI->load->model(['original_game_logs_model']);
        $this->original_transactions_table = $this->getTransactionsTable();
        $this->CI->load->model(['seamless_missing_payout']);

        $sql = <<<EOD
SELECT 
status
FROM {$this->original_transactions_table}
WHERE
game_platform_id=? AND external_unique_id=? 
EOD;
     
        $params=[$game_platform_id, $external_uniqueid];

        $trans = $this->CI->original_game_logs_model->commonGetOneOriginalGameLogs($sql, $params);

        if(!empty($trans)){
            return array('success'=>true, 'status'=>$this->gameLogsStatus[$trans['status']]);
        }
        return array('success'=>false, 'status'=>$this->gameLogsStatus['ok']);
    }

    public function preprocessOriginalRowForBetDetails($row, $extra = []) {
        $this->CI->load->library(['language_function']);
        $amount_data = $this->getAmountsFromGameResults($row['player_id'], $row['round_id']);

        $bet_details = $row;

       /*  $credit_details = !empty($row['bet_details']['credit']) ? json_decode($row['bet_details']['credit'], true) : [];
        $game_data = !empty($credit_details['gameDataString']) ? json_decode($credit_details['gameDataString'], true) : []; */

        if (isset($row['round_id'])) {
            $bet_details['bet_id'] = $row['round_id'];
        }

        if (isset($amount_data['bet_amount'])) {
            $bet_details['bet_amount'] = $amount_data['bet_amount'];
        }

        if (isset($amount_data['win_amount'])) {
            $bet_details['win_amount'] = $amount_data['win_amount'];
        }

        if (isset($row['game_description_name'])) {
            $game_description_name = $this->utils->extractLangJson($row['game_description_name']);
            $bet_details['game_name'] = $game_description_name[$this->utils->getCurrentLanguageCode()];
        }

        if (isset($row['round_id'])) {
            $bet_details['round_id'] = $row['round_id'];
        }

        if (isset($row['start_at'])) {
            $bet_details['betting_datetime'] = $row['start_at'];
        }

        if (isset($row['end_at'])) {
            $bet_details['settlement_datetime'] = $row['end_at'];
        }

        // print_r($bet_details);exit;
        return $bet_details;
    }

    public function getAmountsFromGameResults($player_id, $round_id) {
        $this->CI->load->model(['original_seamless_wallet_transactions']);

        $where = [
            'transaction_type' => self::TRANSACTION_CREDIT,
            'player_id' => $player_id,
            'round_id' => $round_id,
        ];

        $result = $this->CI->original_seamless_wallet_transactions->getSpecificColumn($this->original_transaction_table, 'extra_info', $where);

        if ($this->use_ezugi_seamless_wallet_transactions && $this->use_monthly_transactions_table) {
            if ($this->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
                if (empty($result)) {
                    $result = $this->CI->original_seamless_wallet_transactions->getSpecificColumn($this->previous_table, 'extra_info', $where);
                }
            }
        }

        $extra_info = !empty($result) ? json_decode($result, true) : [];
        $game_data = !empty($extra_info['gameDataString']) ? json_decode($extra_info['gameDataString'], true) : [];

        $data = [
            'bet_amount' => isset($game_data['BetAmount']) ?$game_data['BetAmount'] : 0,
            'win_amount' => isset($game_data['TotalWin']) ? $game_data['TotalWin'] : 0,
        ];

        $data['result_amount'] = $data['win_amount'] - $data['bet_amount'];

        return $data;
    }

    #OGP-34427
    public function getProviderAvailableLanguage() {
        return $this->getSystemInfo('provider_available_langauge', ['en','zh-cn','id-id','vi-vi','ko-kr','th-th','pt']);
    }
}

/*end of file*/