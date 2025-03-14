<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
/**
 * Yeebet Single Wallet API Document
 * OGP-30980
 *
 * @author  Jerbey Capoquian
 *
 *
 * 
 *
 * By function:
    
 *
 * 
 * Related File
     - yeebet_service_api.php
 */


class Game_api_yeebet_seamless extends Abstract_game_api {

    const ORIGINAL_TRANSACTION_TABLE = 'yeebet_seamless_wallet_transactions';
    const ORIGINAL_LOGS_TABLE_NAME = 'yeebet_seamless_game_logs';
    const POST = 'POST';
    const GET = 'GET';
    
    public function __construct() {
        parent::__construct();
        $this->original_transaction_table = self::ORIGINAL_TRANSACTION_TABLE;

        $this->api_url = $this->getSystemInfo('url');
        $this->lobby_url = $this->getSystemInfo('lobby_url');
        $this->currency = $this->getSystemInfo('currency', "PHP");
        $this->redirect = $this->getSystemInfo('redirect', false);
        $this->force_lang = $this->getSystemInfo('force_lang', false);
        $this->language_code = $this->getSystemInfo('language_code', 2);
        #for launching
        $this->app_id = $this->getSystemInfo('app_id', 'xtdDA1UWYVNH');
        $this->secret_key = $this->getSystemInfo('secret_key', 'A0C0CB27404DCC05624B3B6EBC6311DA');
        #for seamless
        $this->seamless_app_id = $this->getSystemInfo('seamless_app_id', 'T1SoftPHPSTG');
        $this->seamless_secret_key = $this->getSystemInfo('seamless_secret_key', '9f8c2eee3f4338d5667445df91e0f0fc');

        $this->commratio = $this->getSystemInfo('commratio');
        $this->quotas = $this->getSystemInfo('quotas');
        $this->portrait = $this->getSystemInfo('portrait');
        $this->state = $this->getSystemInfo('state');
        $this->list_of_method_for_force_error = $this->getSystemInfo('list_of_method_for_force_error', []);
        $this->allow_multiple_settlement = $this->getSystemInfo('allow_multiple_settlement', false);
        $this->gameid_prefix = $this->getSystemInfo('gameid_prefix');# "stg" for staging only,
        $this->use_monthly_transactions_table = $this->getSystemInfo('use_monthly_transactions_table', false);
        $this->enable_merging_rows = $this->getSystemInfo('enable_merging_rows', true);
        $this->enable_mock_failed_transaction = $this->getSystemInfo('enable_mock_failed_transaction', false);
        $this->enable_mock_failed_transaction_player_list = $this->getSystemInfo('enable_mock_failed_transaction_player_list', []);
        $this->prefix_for_username = $this->getSystemInfo('prefix_for_username');
    }

    const URI_MAP = array(
        self::API_queryForwardGame => '/api/login',
        self::API_queryGameResult => '/api/record/bets/detail',
    );

    public function isSeamLessGame()
    {
        return true;
    }

    public function getPlatformCode() {
        return YEEBET_SEAMLESS_GAME_API;
    }

    /**
     * overview : custom http call
     *
     * @param $ch
     * @param $params
     */
    protected function customHttpCall($ch, $params) {
        if($this->method == self::POST){
            curl_setopt($ch, CURLOPT_POST, TRUE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        }
        $this->utils->debug_log('YEEBET Request Field: ',http_build_query($params));
    }

    protected function isErrorCode($apiName, $params, $statusCode, $errCode, $error) {
        return $errCode || intval($statusCode, 10) >= 503;
    }

    public function generateUrl($apiName, $params) {
        $apiUri = self::URI_MAP[$apiName];
        $url = $this->api_url . $apiUri;
        if($this->method == self::GET){
            $url = $url . '?' . http_build_query($params);
        }
        return $url;
    }

    public function processResultBoolean($responseResultId, $resultArr, $statusCode) { 
        $success = false;

        if(!empty($resultArr) && ($statusCode == 201 || $statusCode == 200) && $resultArr['result'] == 0){
            $success=true;
        }

        if (!$success) {
            $this->setResponseResultToError($responseResultId);
            $this->CI->utils->debug_log('YEEBET GAME API got error: ', $responseResultId,'result', $resultArr, $statusCode);
        }
        return $success;   
    }

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
        $extra = array_merge([
            'prefix' => $this->prefix_for_username,
            'fix_username_limit' => $this->fix_username_limit,
            'minimum_user_length' => $this->minimum_user_length,
            'maximum_user_length' => $this->maximum_user_length,
            'default_fix_name_length' => $this->default_fix_name_length,
            'check_username_only' => true
        ], is_null($extra) ? [] : $extra);

        $return = parent::createPlayer($playerName, $playerId, $password, $email, $extra);
        $success = false;
        $message = "Unable to create Account for YEEBET Game";
        if($return){
            $success = true;
            $message = "Successfull create account for YEEBET Game.";
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
        }

        return array("success" => $success, "message" => $message);
    }

    public function depositToGame($playerName, $amount, $transfer_secure_id=null){
        $external_transaction_id = $transfer_secure_id;

        return [
            "success" => true,
            "external_transaction_id" => $external_transaction_id,
            "response_result_id" => null,
            "didnot_insert_game_logs" => true
        ];
    }

    public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null) {
        $external_transaction_id = $transfer_secure_id;

        return [
            "success" => true,
            "external_transaction_id" => $external_transaction_id,
            "response_result_id" => null,
            "didnot_insert_game_logs" => true
        ];
    }

    public function queryForwardGame($playerName, $extra = null) {
        $this->CI->load->model('common_token');
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $language = $this->getLanguage($extra['language']);
        $isDemo = false;
        if(isset($extra['game_mode']) && $extra['game_mode'] != 'real'){
            $isDemo = true;
        }

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryForwardGame',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername,
        );
        $home_link = $this->getHomeLink();
        if(isset($extra['extra']['home_link'])) {
            $home_link = $extra['extra']['home_link'];
        }

        $pcCode = 1;
        $mobileCode = 2;
        $params = array(
            'appid' => $this->app_id,
            'username' => $gameUsername,
            // 'nickname' => $gameUsername, #not required
            'iscreate' =>  self::FLAG_TRUE,
            'clienttype' => $extra['is_mobile'] ? $mobileCode : $pcCode,
            'language' => $language,
            'currency' => $this->currency,
            'returnurl' =>  $home_link,
            'username' =>  $gameUsername,
            'token' => $this->CI->common_token->getPlayerCommonTokenByGameUsername($gameUsername)
        );

        if($this->commratio){
            /*The commission ratio field (defined by the
                company, avoid temporarily modified the
                proportion of stolen commission)
                The anchor ID can pass according to this
                (ID10001_0.5)
            */
            $params['commratio'] = $this->commratio;
        }
        if($this->state){
            // Status (1:Open,0:Prohibit,-1:Lock,-2:Prohibit bet,-3:Max win,-4:Max lost),-3 and -4 have no 
            $params['state'] = $this->state;
        }
        if($this->quotas){
            //Bet limited field ("1,2,3" comma segmentation),
            $params['quotas'] = $this->quotas;
        }
        if($this->portrait){
            //User portrait
            $params['portrait'] = $this->portrait;
        }

        if(isset($extra['game_code']) && !empty($extra['game_code'])){
            $game_code = preg_replace("/[^0-9]/", '', $extra['game_code']); #get number only
            $params['gid'] = $game_code;
        }

        if($isDemo){
            $params['ist'] = $isDemo;
        }

        $params['sign'] = $this->generateSign($params);

        $this->method = self::POST;

        return $this->callApi(self::API_queryForwardGame, $params, $context);
    }

    public function processResultForQueryForwardGame($params){
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        $result = array();

        if($success){
            if(isset($resultArr['openurl']))
            {
                $result['url'] = $resultArr['openurl'];
            }
        }

        return array($success, $result);
    }

    private function generateSign($params){
        $secretKey = $this->secret_key;
        $data = array_change_key_case($params, CASE_LOWER);
        $data = array_filter($data, 'strlen');

        ksort($data);
        $dataStr = '';
        foreach($data AS $key => $value){
            if($key <> 'sign'){
                $dataStr .= $key . '=' . $value . '&';
            }
        }
        $dataStr = substr_replace($dataStr, "", -1);
        $encryptedKey = md5($dataStr . "&key=" . $secretKey);
        return $encryptedKey;
    }

    public function getLanguage($currentLang) {

        if($this->force_lang && $this->language_code){
            return $this->language_code;
        }

        switch ($currentLang) {
            case LANGUAGE_FUNCTION::INT_LANG_CHINESE:
            case LANGUAGE_FUNCTION::PLAYER_LANG_CHINESE :
                $language = 1;
                break;
            case LANGUAGE_FUNCTION::INT_LANG_INDONESIAN:
            case LANGUAGE_FUNCTION::PLAYER_LANG_INDONESIAN :
                $language = 11;
                break;
            case LANGUAGE_FUNCTION::INT_LANG_VIETNAMESE:
            case LANGUAGE_FUNCTION::PLAYER_LANG_VIETNAMESE :
                $language = 10;
                break;
            case LANGUAGE_FUNCTION::INT_LANG_KOREAN:
            case LANGUAGE_FUNCTION::PLAYER_LANG_KOREAN :
                $language = 3;
                break;
            case LANGUAGE_FUNCTION::INT_LANG_THAI:
            case LANGUAGE_FUNCTION::PLAYER_LANG_THAI :
                $language = 5;
                break;
            case LANGUAGE_FUNCTION::INT_LANG_PORTUGUESE:
            case LANGUAGE_FUNCTION::PLAYER_LANG_PORTUGUESE :
                $language = 13;
                break;
            case LANGUAGE_FUNCTION::INT_LANG_SPANISH:
            case LANGUAGE_FUNCTION::PLAYER_LANG_SPANISH :
                $language = 12;
                break;
            default:
                $language = 2;
                break;
        }
        return $language;
    }

    public function syncOriginalGameLogs($token = false) {
        return $this->returnUnimplemented();
    }

    public function queryTransaction($transactionId, $extra) {
        return $this->returnUnimplemented();
    }

    public function syncMergeToGameLogs($token) {
        $enabled_game_logs_unsettle=true;
        if ($this->enable_merging_rows) {
            return $this->commonSyncMergeToGameLogs(
                $token,
                $this,
                [$this, 'queryOriginalGameLogsMerge'],
                [$this, 'makeParamsForInsertOrUpdateGameLogsRowFromTransMerge'],
                [$this, 'preprocessOriginalRowForGameLogsMerge'],
                $enabled_game_logs_unsettle
            );
        } else {
            return $this->commonSyncMergeToGameLogs(
                $token,
                $this,
                [$this, 'queryOriginalGameLogsFromTrans'],
                [$this, 'makeParamsForInsertOrUpdateGameLogsRowFromTrans'],
                [$this, 'preprocessOriginalRowForGameLogsFromTrans'],
                $enabled_game_logs_unsettle
            );
        }
    }

        /**
     * queryOriginalGameLogsMerge
     * @param  string $dateFrom
     * @param  string $dateTo
     * @param  bool   $use_bet_time
     * @return array
     */
    public function queryOriginalGameLogsMerge($dateFrom, $dateTo, $use_bet_time = false){
        $table = $this->getTransactionsTable();
        $sqlTime = '`yeebet`.`updated_at` >= ? AND `yeebet`.`updated_at` <= ?';

        if($use_bet_time)
        {
            $sqlTime = '`yeebet`.`created_at` >= ? AND `yeebet`.`created_at` <= ?';
        }

        $this->CI->utils->debug_log('YEEBET sqlTime ===>', $sqlTime);

        $sql = <<<EOD
        SELECT
yeebet.id as sync_index,
yeebet.response_result_id,
yeebet.serial_number as external_uniqueid,
yeebet.md5_sum,

yeebet.player_id,
yeebet.game_platform_id,
yeebet.amount,
yeebet.user_name,
yeebet.external_game_id as game_code,
yeebet.external_game_id as game,
yeebet.external_game_id as game_name,
yeebet.bets,
yeebet.sbe_status as status,
yeebet.after_balance,
yeebet.game_create_time,
yeebet.created_at,
null as round_number,

gd.id as game_description_id,
gd.game_type_id

FROM {$table} as yeebet
LEFT JOIN game_description as gd ON yeebet.external_game_id = gd.external_game_id AND gd.game_platform_id = ?
WHERE
        {$sqlTime}

EOD;

        $params = [
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo
        ];

        $results =  $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        $this->CI->original_game_logs_model->removeDuplicateUniqueid($results, 'external_uniqueid', function(){ return 2;});
        $results = array_values($results);
        return $results;
    }

    /**
     * it will be used on processUnsettleGameLogs and commonUpdateOrInsertGameLogs
     *
     * @param  array $row
     * @return array $params
     */
    public function makeParamsForInsertOrUpdateGameLogsRowFromTransMerge(array $row) {
        if(empty($row['md5_sum'])){
            $this->CI->utils->error_log('no md5 on ', $row['external_uniqueid']);
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, ['status', 'bets', 'game_create_time'],
                ['amount']);
        }

        return [
            'game_info' => [
                'game_type_id' => $row['game_type_id'],
                'game_description_id' => $row['game_description_id'],
                'game_code' => $row['game_code'],
                'game_type' => null,
                'game' => $row['game_code'],
            ],
            'player_info' => [
                'player_id' => $row['player_id'],
                'player_username' => null,
            ],
            'amount_info' => [
                'bet_amount' => isset($row['bet_amount']) ? $row['bet_amount'] : 0,
                'result_amount' => isset($row['result_amount']) ? $row['result_amount'] : 0,
                'bet_for_cashback' => isset($row['bet_amount']) ? $row['bet_amount'] : 0,
                'real_betting_amount' => isset($row['real_betting_amount']) ? $row['real_betting_amount'] : 0,
                'win_amount' => null,
                'loss_amount' => null,
                'after_balance' => $row['after_balance'],
            ],
            'date_info' => [
                'start_at' => $row['game_create_time'],
                'end_at' => $row['game_create_time'],
                'bet_at' => $row['game_create_time'],
                'updated_at' => $this->CI->utils->getNowForMysql(),
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => $row['status'],
            'additional_info' => [
                'has_both_side' => 0,
                'external_uniqueid' => $row['external_uniqueid'],
                'round_number' => $row['round_number'],
                'md5_sum' => $row['md5_sum'],
                'response_result_id' => $row['response_result_id'],
                'sync_index' => $row['sync_index'],
                // 'bet_type' => $this->getBetTypeIdString($row['bet_type_id']),
                'bet_type' => null
            ],
            'bet_details' => $row['bets'],
            'extra' => [],
            //from exists game logs
            'game_logs_id'=>isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];
    }

     /**
     *
     * perpare original rows, include process unknown game, pack bet details, convert game status
     *
     * @param  array &$row
     */
    public function preprocessOriginalRowForGameLogsMerge(array &$row)
    {
        // if (empty($row['game_description_id']))
        // {
        //     $unknownGame = $this->getUnknownGame($this->getPlatformCode());
        //     list($game_description_id,$game_type_id) = $this->getGameDescriptionInfo($row,$unknownGame);
        //     $row['game_description_id']= $game_description_id;
        //     $row['game_type_id'] = $game_type_id;
        // }

        $betDetails = [];
        if(!empty($row['bets'])){
            $betDetails = json_decode($row['bets'], true);
        }
        
        if(!empty($betDetails)){
            $row['bet_amount'] = isset($betDetails['commamount']) ? $betDetails['commamount'] : 0;
            $row['real_betting_amount'] = isset($betDetails['betamount']) ? $betDetails['betamount'] : 0;
            $row['result_amount'] = isset($betDetails['winlost']) ? $betDetails['winlost'] : 0;
            $row['round_number'] = isset($betDetails['gameno']) ? $betDetails['gameno'] : null;
            if(empty($row['round_number'])){ #try get by gameroundno
                $row['round_number'] = isset($betDetails['gameroundno']) ? $betDetails['gameroundno'] : null;
            }
            $row['game_name'] = isset($betDetails['gameid']) ? $betDetails['gameid'] : null;
            $row['game_code'] = isset($betDetails['gameid']) ? $betDetails['gameid'] : null;
            if(!empty($this->gameid_prefix)){
                $row['game_name'] = $this->gameid_prefix . "-" .  $row['game_name']; 
                $row['game_code'] = $this->gameid_prefix . "-" .  $row['game_code']; 
            }
        } else {
            $result = $this->getBetDetails($row['external_uniqueid']);
            $betDetails = isset($result['details']) ? $result['details'] : [];
            if(!empty($betDetails)){
                $row['bet_amount'] = isset($betDetails['commamount']) ? $betDetails['commamount'] : 0;
                $row['real_betting_amount'] = isset($betDetails['betamount']) ? $betDetails['betamount'] : 0;
                $row['result_amount'] = isset($betDetails['winlost']) ? $betDetails['winlost'] : 0;
                $row['round_number'] = isset($betDetails['gameno']) ? $betDetails['gameno'] : null;
                if(empty($row['round_number'])){ #try get by gameroundno
                    $row['round_number'] = isset($betDetails['gameroundno']) ? $betDetails['gameroundno'] : null;
                }
                $row['bets'] = json_encode($betDetails);
                $row['game_name'] = isset($betDetails['gameid']) ? $betDetails['gameid'] : null;
                $row['game_code'] = isset($betDetails['gameid']) ? $betDetails['gameid'] : null;
                if(!empty($this->gameid_prefix)){
                    $row['game_name'] = $this->gameid_prefix . "-" .  $row['game_name']; 
                    $row['game_code'] = $this->gameid_prefix . "-" .  $row['game_code']; 
                }
            } else {
                $row['bet_amount'] =  0;
                $row['real_betting_amount'] =  0;
                $row['result_amount'] =  0;
            } 
        }

        if(empty($row['game_create_time'])){
            $row['game_create_time'] = $row['created_at'];
        }

        if (empty($row['game_description_id']))
        {
            $unknownGame = $this->getUnknownGame($this->getPlatformCode());
            list($game_description_id,$game_type_id) = $this->getGameDescriptionInfo($row,$unknownGame);
            $row['game_description_id']= $game_description_id;
            $row['game_type_id'] = $game_type_id;
        }
    }

    public function getGameDescriptionInfo($row, $unknownGame) {

        $game_description_id = null;
        $game_name = $row['game_name'];
        $external_game_id = $row['game_code'];

        $game_type_id = $unknownGame->game_type_id ? $unknownGame->game_type_id : null;
        $game_type = $unknownGame->game_name ? $unknownGame->game_name : self::TAG_CODE_UNKNOWN_GAME;

        return $this->processUnknownGame(
            $game_description_id, $game_type_id,
            $external_game_id, $game_type, $external_game_id);
    }

    public function queryTransactionByDateTime($startDate, $endDate){
        $this->CI->load->model(array('original_game_logs_model'));

        $original_transactions_table = $this->getTransactionsTable();

        $sql = <<<EOD
SELECT
db.player_id as player_id,
db.created_at transaction_date,
ABS(db.amount) as amount,
db.after_balance as after_balance,
db.before_balance as before_balance,
COALESCE(
    JSON_UNQUOTE(JSON_EXTRACT(db.bets, '$.gameno')),
    JSON_UNQUOTE(JSON_EXTRACT(db.bets, '$.gameroundno'))
) AS round_no,
db.external_unique_id as external_uniqueid,
db.type as trans_type,
IF(db.amount < 0, 1002, 1001) as transaction_type
FROM {$original_transactions_table} as db
WHERE `db`.`created_at` >= ? AND `db`.`created_at` <= ?  AND `db`.player_id is not null
ORDER BY db.created_at asc;
EOD;
        
        $params=[$startDate, $endDate];
        
        $results = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        array_walk($results, function($rows, $key) use(&$results){
            $typeCode = $rows['trans_type'];
            switch ($typeCode) {
                case 1:
                    $results[$key]['trans_type'] = "Bet";
                    break;
                case 5:
                    $results[$key]['trans_type'] = "Deposit";
                    break;
                case 6:
                    $results[$key]['trans_type'] = "Return deposit";
                    break;
                case 7:
                    $results[$key]['trans_type'] = "Bet Cancel";
                    break;
                case 9:
                    $results[$key]['trans_type'] = "Settle";
                    break;
                case 10:
                    $results[$key]['trans_type'] = "Re Settle";
                    break;
                
                default:
                    //do nothing
                    break;
            }
        });
        return $results;
    }

    public function getTransactionsTable(){
        if(!$this->use_monthly_transactions_table){            
            return $this->original_transaction_table;
        }

        $date=new DateTime();
        $monthStr=$date->format('Ym');
        
        return $this->initGameTransactionsMonthlyTableByDate($monthStr); 
    }

    public function initGameTransactionsMonthlyTableByDate($yearMonthStr){
        if(!$this->use_monthly_transactions_table){            
            return $this->original_transaction_table;
        }

        $tableName=$this->original_transaction_table.'_'.$yearMonthStr;
        if (!$this->CI->utils->table_really_exists($tableName)) {
            try{
                $this->CI->load->model(['player_model']);
                $this->CI->player_model->runRawUpdateInsertSQL('create table '.$tableName.' like yeebet_seamless_wallet_transactions');

            }catch(Exception $e){
                $this->CI->utils->error_log('create table failed: '.$tableName, $e);
                return null;
            }
        }

        return $tableName;
    }

    public function getBetDetails($serialNumber = 918977){

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForGetBetDetails',
        );

        $params = array(
            'appid' => $this->app_id,
            'ids' => $serialNumber,
            'index' => 0,
            'size' => 1
        );

        $params['sign'] = $this->generateSign($params);

        $this->method = self::GET;

        return $this->callApi(self::API_queryGameResult, $params, $context);
    }

    public function processResultForGetBetDetails($params){
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        $result = array();
        if($success){
            $result['details'] = isset($resultArr['array'][0]) ? $resultArr['array'][0] : [];
        }
        return array($success, $result);
    }

    public function queryOriginalGameLogsFromTrans($dateFrom, $dateTo, $use_bet_time = false)
    {
        $table = $this->getTransactionsTable();

        $sqlTime = 'transaction.updated_at BETWEEN ? AND ?';

        if ($use_bet_time) {
            $sqlTime = 'transaction.game_create_time BETWEEN ? AND ?';
        }

        $md5Fields = implode(", ", array('amount', 'transaction.after_balance', 'transaction.updated_at'));

        $sql = <<<EOD

SELECT
    gd.game_type_id,
    gd.id AS game_description_id,
    transaction.external_game_id,
    gd.english_name AS game,

    transaction.player_id,
    transaction.user_name AS player_username,

    transaction.amount,
    transaction.after_balance,

    transaction.game_create_time AS start_at,
    transaction.updated_at,

    transaction.sbe_status AS status,
    transaction.external_unique_id AS external_uniqueid,
    MD5(CONCAT({$md5Fields})) AS md5_sum,
    transaction.response_result_id,
    transaction.id AS sync_index,

    transaction.type,
    transaction.bets

FROM {$table} AS transaction
LEFT JOIN game_description AS gd ON transaction.external_game_id = gd.external_game_id AND gd.game_platform_id = ?
WHERE
    transaction.game_platform_id = ? AND
    {$sqlTime}
EOD;

        $params = [
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo,
        ];

        $this->CI->utils->debug_log(__METHOD__ . ' ===========================> sql and params - ' . __LINE__, $sql, $params);
        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);

        return $result;
    }

    public function makeParamsForInsertOrUpdateGameLogsRowFromTrans(array $row)
    {
        $data = [
            'game_info' => [
                'game_type_id'          => $row['game_type_id'],
                'game_description_id'   => $row['game_description_id'],
                'game_code'             => $row['external_game_id'],
                'game_type'             => null,
                'game'                  => $row['game']
            ],
            'player_info' => [
                'player_id'             => $row['player_id'],
                'player_username'       => $row['player_username']
            ],
            'amount_info' => [
                'bet_amount'            => $row['bet_amount'],
                'result_amount'         => $row['result_amount'],
                'bet_for_cashback'      => $row['bet_amount'],
                'real_betting_amount'   => $row['bet_amount'],
                'win_amount'            => null,
                'loss_amount'           => null,
                'after_balance'         => $row['after_balance'],
            ],
            'date_info' => [
                'start_at'              => $row['start_at'],
                'end_at'                => $row['updated_at'],
                'bet_at'                => $row['start_at'],
                'updated_at'            => $row['updated_at']
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => Game_logs::STATUS_SETTLED,
            'additional_info' => [
                'has_both_side'         => 0,
                'external_uniqueid'     => $row['external_uniqueid'],
                'round_number'          => $row['round_id'],
                'md5_sum'               => $row['md5_sum'],
                'response_result_id'    => $row['response_result_id'],
                'sync_index'            => $row['sync_index'],
                'bet_type'              => null
            ],
            'bet_details' => $this->preprocessBetDetails($row, null, true),
            'extra' => [],

            'game_logs_id' => isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id' => isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];

        return $data;
    }

    public function preprocessOriginalRowForGameLogsFromTrans(array &$row)
    {
        if (empty($row['game_type_id'])) {
            list($row['game_description_id'], $row['game_type_id']) = $this->getGameDescriptionInfo($row, $this->getUnknownGame());
        }

        #set bet and result amount
        if ($row['type'] == 1) { #bet
            $row['bet_amount'] = abs($row['amount']);
            $row['result_amount'] = $row['amount'];
        } elseif ($row['type'] == 9) { #settle
            $row['bet_amount'] = 0;
            $row['result_amount'] = $row['amount'];
        }

        #set round number
        $bets = json_decode($row['bets'], true);
        if(isset($bets['gameroundno'])){
            $row['round_id'] = $bets['gameroundno'];
        }
    }

    public function getUnsettledRounds($dateFrom, $dateTo){
        $sqlTime='T.created_at >= ? AND T.created_at <= ?';

        $this->CI->load->model(array('original_game_logs_model'));
        $this->original_transactions_table = $this->getTransactionsTable();
        $settledStatus = Game_logs::STATUS_SETTLED;
        $transType = 1; //BET CODE

        $sql = <<<EOD

SELECT 
T.serial_number AS round_id, 
T.serial_number, 
T.external_unique_id,
T.type,
T.sbe_status as status,
GD.game_platform_id
FROM {$this->original_transactions_table} AS T
LEFT JOIN game_description as GD ON GD.game_name = T.external_game_id
WHERE NOT EXISTS (
    SELECT 'exists'
    FROM {$this->original_transactions_table} AS T2
    WHERE T2.serial_number = T.serial_number
    AND T2.sbe_status=? 
)
AND T.type=?
AND {$sqlTime}
EOD;


        $params=[
            $settledStatus,
            $transType,
            $dateFrom,
            $dateTo
		];
        $platformCode = $this->getPlatformCode();
	    $this->CI->utils->debug_log('YEEBET SEAMLESS-' .$platformCode.' (getUnsettledRounds)', 'params',$params,'sql',$sql);
        $result =  $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
	    $this->CI->utils->debug_log('YEEBET SEAMLESS-' .$platformCode.' (getUnsettledRounds)', [
            '$result' => $result 
        ]);
        return $result;
    }

    public function checkBetStatus($data){
        $this->CI->load->model(['seamless_missing_payout']);
        $this->original_transactions_table = $this->getTransactionsTable();

        $serial_number = $data['serial_number'];
        $external_unique_id = $data['external_unique_id'];
        $transStatus = Game_logs::STATUS_PENDING;
        $baseAmount = 0;
        $platformCode = $this->getPlatformCode();
     
        $sql = <<<EOD

SELECT 
T.serial_number AS round_id, 
T.external_unique_id as external_uniqueid,
T.created_at as transaction_date,
T.type as transaction_type,
T.notify_id as transaction_id,
GD.game_platform_id,
T.player_id,
T.amount as amount,
ABS(SUM(T.amount)) as amount,
ABS(SUM(T.amount)) as deducted_amount,
GD.id as game_description_id,
GD.game_type_id,
GD.game_platform_id
FROM {$this->original_transactions_table} AS T
LEFT JOIN game_description as GD ON GD.game_name = T.external_game_id AND GD.game_platform_id=?
WHERE T.serial_number=?
AND T.external_unique_id=? 
AND GD.game_platform_id=?
EOD;
        
        $params=[$this->getPlatformCode(), $serial_number, $external_unique_id, $this->getPlatformCode()];

        $transactions  = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        
        foreach($transactions as $transaction){
            if($transaction['game_platform_id']){
                $transaction['transaction_status'] = $transStatus;
                $transaction['added_amount'] = $baseAmount;
                $transaction['status'] = Seamless_missing_payout::NOT_FIXED;

                $result = $this->CI->original_game_logs_model->insertIgnoreRowsToOriginal('seamless_missing_payout_report',$transaction);
                $this->CI->utils->debug_log('YEEBET SEAMLESS-' .$platformCode.' (getUnsettledRounds)', [
                    '$transactions' => $transactions , 
                    '$result' => $result , 
                ]);
                if($result===false){
                    $this->CI->utils->error_log('YEEBET SEAMLESS-' .$this->getPlatformCode().'(checkBetStatus) Error insert missing payout', $transaction);
                }
            }
        }
        
        if(empty($trans)){
            return array('success'=>false, 'exists'=>false);
        }
    }
    
    public function queryBetTransactionStatus($game_platform_id, $external_unique_id){
        $this->CI->load->model(['original_game_logs_model']);
        $this->original_transactions_table = $this->getTransactionsTable();
        $this->CI->load->model(['seamless_missing_payout']);
	    $this->CI->utils->debug_log('YEEBET SEAMLESS-' .$this->getPlatformCode().' (queryBetTransactionStatus)', [
            'game_platform_id' => $game_platform_id,
            'external_unique_id' => $external_unique_id,
        ]);

        $sql = <<<EOD
SELECT 
sbe_status
FROM {$this->original_transactions_table} as T
WHERE NOT EXISTS (
    SELECT 'exists'
    FROM {$this->original_transactions_table} AS T2
    WHERE T2.serial_number = T.serial_number
    AND T2.sbe_status=1
)
AND game_platform_id=? AND external_unique_id=? 
EOD;
     
        $params=[$game_platform_id, $external_unique_id];

        $trans = $this->CI->original_game_logs_model->commonGetOneOriginalGameLogs($sql, $params);

        if(!empty($trans)){
            return array('success'=>false, 'status'=>Game_logs::STATUS_PENDING);
        }
        return array('success'=>true, 'status'=>Game_logs::STATUS_SETTLED);
    }
}