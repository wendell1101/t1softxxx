<?php
/**
 * Champion Sports game integration
 * OGP-22819
 *
 * @author  Jerbey Capoquian
 *
 *
 *
 * API DOC: https://app.swaggerhub.com/apis/caletagaming/caleta-gaming_system_api_operators_guide/1.3
 * API USER: caletaclient
 * API PASS: client@caleta2020
 *
 * By function:
    <site>/caletagaming_service_api/wallet/balance
    <site>/caletagaming_service_api/wallet/bet
    <site>/caletagaming_service_api/wallet/win
    <site>/caletagaming_service_api/wallet/rollback
 *
 *
 * Related File
     - caletagaming_service_api.php
 */
require_once dirname(__FILE__) . '/abstract_game_api.php';

class Game_api_caleta_seamless extends Abstract_game_api {


    const ORIGINAL_LOGS_TABLE_NAME = 'caleta_seamless_game_logs';
    const ORIGINAL_TRANSACTIONS =  'common_seamless_wallet_transactions';
    const MD5_FIELDS_FOR_MERGE=['start_at', 'end_at', 'bet_at', 'status_db', 'status'];
    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE=['bet_amount','result_amount'];

    const FLAG_UPDATED = 1;
    const FlAG_NOT_UPDATED = 0;

    public function __construct() {
        parent::__construct();
        $this->apiUrl = $this->getSystemInfo('url','https://staging.the-rgs.com');
        $this->lang = $this->getSystemInfo('lang','en');
        $this->force_lang = $this->getSystemInfo('force_lang', false);
        $this->lobby_url = $this->getSystemInfo('lobby_url');
        $this->deposit_url = $this->getSystemInfo('deposit_url');
        $this->currency = $this->getSystemInfo('currency');
        $this->operator_id = $this->getSystemInfo('operator_id');

        #service system info
        $this->allowed_invalid_token_on_request = $this->getSystemInfo('allowed_invalid_token_on_request', false);
        $this->force_bet_failed_response = $this->getSystemInfo('force_bet_failed_response', false);
        $this->force_rollback_failed_response = $this->getSystemInfo('force_rollback_failed_response', false);
        $this->force_win_failed_response = $this->getSystemInfo('force_win_failed_response', false);
        $this->allowed_invalid_sign = $this->getSystemInfo('allowed_invalid_sign', false);#for testing only
        $this->original_transactions_table = self::ORIGINAL_TRANSACTIONS;
        $this->enable_merging_rows = $this->getSystemInfo('enable_merging_rows', true);
    }

    const URI_MAP = array(
        self::API_queryForwardGame => '/api/game/url',
        self::API_queryGameListFromGameProvider => '/api/game/list',
        self::API_queryBetDetailLink => '/api/game/round',
    );

    public function isSeamLessGame()
    {
        return true;
    }

    public function getPlatformCode() {
        return CALETA_SEAMLESS_API;
    }

    /**
     * overview : custom http call
     *
     * @param $ch
     * @param $params
     */
    protected function customHttpCall($ch, $params) {
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));

    }

    protected function getHttpHeaders($params)
    {
        $headers['X-Auth-Signature'] = $this->signature;
        return $headers;
    }

    protected function isErrorCode($apiName, $params, $statusCode, $errCode, $error) {
        return $errCode || intval($statusCode, 10) >= 503;
    }

    public function generateUrl($apiName, $params) {
        $apiUri = self::URI_MAP[$apiName];
        $url = $this->apiUrl . $apiUri;
        return $url;
    }

    public function processResultBoolean($responseResultId, $resultArr, $statusCode) {
        $success = false;
        if(@$statusCode == 200){
            $success = true;
            if(isset($resultArr['code']) && isset($resultArr['message'])){ #possible error
                $success = false;
            }
        }

        if (!$success) {
            $this->setResponseResultToError($responseResultId);
            $this->CI->utils->debug_log('Caleta Seamless got error ', $responseResultId,'result', $resultArr);
        }
        return $success;
    }

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
        $return = parent::createPlayer($playerName, $playerId, $password, $email, $extra);
        $success = false;
        $message = "Unable to create Account for Caleta Game";
        if($return){
            $success = true;
            $message = "Successfull create account for Caleta Game.";
        }

        return array("success" => $success, "message" => $message);
    }

    public function queryPlayerBalance($playerName) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
        $balance = $this->CI->player_model->getPlayerSubWalletBalance($playerId, $this->getPlatformCode());

        $result = array(
            'success' => true,
            'balance' => $balance
        );

        $this->CI->utils->debug_log('Caleta: (' . __FUNCTION__ . ')', 'PARAMS:', $playerName, 'RESULT:', $result);

        return $result;
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

    # Returns the public key generated by provider(Caleta)
    private function getPublicKey() {
        $publicKey = $this->getSystemInfo('caleta_pub_key'); #provide by provider

        $publicKey = '-----BEGIN PUBLIC KEY-----' . PHP_EOL . chunk_split($publicKey, 64, PHP_EOL) . '-----END PUBLIC KEY-----' . PHP_EOL;
        return openssl_get_publickey($publicKey);
    }

   # Returns the private key generated by merchant(Tripleone)
    private function getPrivateKey() {
        $privateKey = $this->getSystemInfo('caleta_priv_key');

        $privateKey = '-----BEGIN RSA PRIVATE KEY-----' . PHP_EOL . chunk_split($privateKey, 64, PHP_EOL) . '-----END RSA PRIVATE KEY-----' . PHP_EOL;
        return openssl_get_privatekey($privateKey);
    }

    # Generate sha256WithRSA signature
    function getSign($params){
        $privateKey = $this->getPrivateKey();
        $key = openssl_get_privatekey($privateKey);
        $content = json_encode($params);
        openssl_sign($content, $signature, $key, "SHA256");
        openssl_free_key($key);
        $sign = base64_encode($signature);
        return $sign;
    }

    # Signature verification sha256WithRSA using provider public key
    function verify($requestBody, $sign){
        $publicKey = $this->getPublicKey();

        $valid = openssl_verify($requestBody,base64_decode($sign), $publicKey, 'SHA256');
        if(!$valid && $this->allowed_invalid_sign){
            $request = json_decode($requestBody, true);
            #check if index is set and strict check
            if(isset($request['is_testing']) && $request['is_testing'] === true){
                $valid = true;
            }
        }
        return $valid;
    }

    public function getLauncherLanguage($language) {
        if($this->force_lang){
            return $this->lang;
        }
        switch ($language) {
            case LANGUAGE_FUNCTION::INT_LANG_ENGLISH:
            case 'en-us':
            case 'en-US':
                $lang = 'en';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_CHINESE:
                $lang = 'zh-cn';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_VIETNAMESE:
                $lang = 'vi';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_KOREAN:
                $lang = 'ko';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_THAI:
                $lang = 'th';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_JAPANESE:
                $lang = 'ja';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_PORTUGUESE:
            case 'pt-br':
            case 'pt-BR':
                $lang = 'pt';
                break;
            default:
                $lang = $this->lang;
                break;
        }
        return $lang;
    }


    public function queryForwardGame($playerName, $extra = null) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $playerId = $this->getPlayerIdFromUsername($playerName);
        $token = $this->getPlayerToken($playerId);
        $lang = $this->getLauncherLanguage($extra['language']);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryForwardGame',
            'playerName' => $playerName
        );

        if(empty($this->lobby_url)){
            $this->lobby_url = $this->utils->getSystemUrl('player');
            $this->appendCurrentDbOnUrl($this->lobby_url);
        }
        if (!empty($extra['home_link'])) {
            $this->lobby_url = $extra['home_link'];
        }
        if (!empty($extra['extra']['home_link'])){
            $this->lobby_url = $extra['extra']['home_link'];
        }

        if(empty($this->deposit_url)){
            $this->deposit_url = $this->utils->getSystemUrl('player','/player_center/dashboard/cashier#memberCenter');
            $this->appendCurrentDbOnUrl($this->deposit_url);
        }
        if (!empty($extra['cashier_link'])) {
            $this->deposit_url = $extra['cashier_link'];
        }
        if (!empty($extra['extra']['cashier_link'])){
            $this->deposit_url = $extra['extra']['cashier_link'];
        }

        if (isset($extra['extra']['home_link'])) {
            $this->lobby_url = $extra['extra']['home_link'];
        }

		$gameMode = isset($extra['game_mode']) ? $extra['game_mode'] : null;
        $currency = in_array($gameMode, $this->demo_game_identifier) ? 'fun' : $this->currency; // will change to real currency if game is real
        
        /* if($currency == 'fun'){
            $gameUsername = 'testdemoplayer';
            // $token = 'testdemotoken2';
            $token = '71501649493f05b43e98d3ae99a9052a';
        } */

        $params = array(
            'user' => $gameUsername,
            'token' => isset($extra['game_code']) ? $token."-".md5($extra['game_code']) : $token,
            'operator_id' => $this->operator_id,
            'lobby_url' => $this->lobby_url,
            'lang' => $lang,
            'game_code' => $extra['game_code'],
            'deposit_url' => $this->deposit_url,
            'currency' => $currency,
        );

        if ($currency == 'fun') {
            unset($params['token']);
            unset($params['lobby_url']);
        }

        if(isset($extra['extra']['disable_home_link']) && $extra['extra']['disable_home_link']) {
            unset($params['lobby_url']);
        }

        $this->signature = $this->getSign($params);

        $this->CI->utils->debug_log('-----------------------caleta queryForwardGame params ----------------------------',$params);
        return $this->callApi(self::API_queryForwardGame, $params, $context);
    }

    public function processResultForQueryForwardGame($params) {
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr,$statusCode);

        $result['url'] = ( $success && isset($resultArr['url']) ) ? $resultArr['url'] : null;
        if(!$success && isset($resultArr['code']) && isset($resultArr['message'])){ #possible error
            $result['message'] = $resultArr['message'];
        }
        return array($success, $result);
    }

    public function queryGameListFromGameProvider($extra=null){

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryGameListFromGameProvider'
        );

        $params = array(
            'operator_id' => $this->operator_id
        );

        $this->signature = $this->getSign($params);

        $this->CI->utils->debug_log('-----------------------caleta queryGameListFromGameProvider params ----------------------------',$params);
        return $this->callApi(self::API_queryGameListFromGameProvider, $params, $context);
    }

    public function processResultForQueryGameListFromGameProvider($params) {
        $resultArr = $this->getResultJsonFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr,$statusCode);
        $result['games'] = [];
        if(!$success && isset($resultArr['code']) && isset($resultArr['message'])){ #possible error
            $result['message'] = $resultArr['message'];
        } else {
            $result['games'] = $resultArr;
            // $this->updateGameList($resultArr);
        }
        return array($success, $result);
    }

    public function updateGameList($games) {

        $this->CI->load->model(array('original_game_logs_model'));
        $this->preProccessGames($games);

        list($insertRows, $updateRows)=$this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
            'caleta_gamelist',
            $games,
            'external_uniqueid',
            'external_uniqueid',
            ['url_thumb','url_background','name','enabled','blocked_countries','freebet_support'],
            'md5_sum',
            'id',
            []
        );

        $dataResult = [
            'data_count' => count($games),
            'data_count_insert' => 0,
            'data_count_update' => 0
        ];

        if (!empty($insertRows)) {
            $dataResult['data_count_insert'] += $this->updateOrInsertGameList($insertRows, 'insert');
        }
        unset($insertRows);

        if (!empty($updateRows)) {
            $dataResult['data_count_update'] += $this->updateOrInsertGameList($updateRows, 'update');
        }
        unset($updateRows);

        return $dataResult;
    }

    private function updateOrInsertGameList($data, $queryType){
        $dataCount=0;
        if(!empty($data)){
            $caption = [];
            if ($queryType == 'update') {
                $caption = "## UPDATE Caleta GAME LIST\n";
            }
            else {
                $caption = "## ADD NEW Caleta GAME LIST\n";
            }

            $body = "| English Name  | Chinese Name  | Game Code | Game Category | Enabled |\n";
            $body .= "| :--- | :--- | :--- |\n";
            foreach ($data as $record) {
                if ($queryType == 'update') {
                    $record['updated_at'] = $this->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->updateRowsToOriginal('caleta_gamelist', $record);
                    $body .= "| {$record['name']} | N/A | {$record['game_code']} | {$record['category']} | {$record['enabled']} |\n";
                } else {
                    unset($record['id']);
                    $record['created_at'] = $this->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->insertRowsToOriginal('caleta_gamelist', $record);
                    $body .= "| {$record['name']} | N/A | {$record['game_code']} | {$record['category']} | {$record['enabled']} |\n";
                }
                $dataCount++;
                unset($record);
            }
            $this->sendMatterMostMessage($caption, $body);
        }
        return $dataCount;
    }

    public function sendMatterMostMessage($caption, $body){
        $message = [
            $caption,
            $body,
            "#CALETA Game"
        ];

        $channel = $this->utils->getConfig('game_list_notification_channel');
        $this->CI->load->helper('mattermost_notification_helper');
        $channel = $channel;
        $user = 'Caleta Game List';

        sendNotificationToMattermost($user, $channel, [], $message);

    }


    public function preProccessGames(&$games) {
        if(!empty($games)){
            foreach ($games as $key => $game) {
                $games[$key]['url_thumb'] = $game['url_thumb'];
                $games[$key]['url_background'] = $game['url_background'];
                $games[$key]['product'] = $game['product'];
                $games[$key]['platforms'] = json_encode($game['platforms']);
                $games[$key]['name'] = $game['name'];
                $games[$key]['game_id'] = $game['game_id'];
                $games[$key]['game_code'] = $game['game_code'];
                $games[$key]['enabled'] = $game['enabled'];
                $games[$key]['category'] = $game['category'];
                $games[$key]['blocked_countries'] = json_encode($game['blocked_countries']);
                $games[$key]['freebet_support'] = $game['freebet_support'];
                $games[$key]['external_uniqueid'] = $game['game_code'];

                $data = $games[$key];
                $games[$key]['md5_sum'] = $this->CI->original_game_logs_model->generateMD5SumOneRow($data, ['url_thumb','url_background','name','enabled','blocked_countries','freebet_support']);
            }
        }
    }

    public function syncOriginalGameLogs($token = false) {
        $startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
        $startDateTime = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
        $startDateTime->modify($this->getDatetimeAdjust());
        $endDateTime = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));
        $queryDateTimeStart = $startDateTime->format("Y-m-d H:i:s");
        $queryDateTimeEnd = $endDateTime->format('Y-m-d H:i:s');

        $winTransactions = $this->querySettledTransactionsForUpdate($queryDateTimeStart, $queryDateTimeEnd);
        if(!empty($winTransactions)){
            foreach ($winTransactions as $key => $winTransaction) {
                $totalBet = $this->queryAmountByRoundIdAndType($winTransaction['round_id'], "bet")['total_amount'];
                $totalRollbackBet = $this->queryAmountByRoundIdAndType($winTransaction['round_id'], "rollback-bet")['total_amount'];
                $totalActualBet = $totalBet - $totalRollbackBet;
                $totalPayout = $winTransaction['amount'];

                $winTransaction['bet_amount'] = $totalActualBet;
                $winTransaction['result_amount'] = $totalPayout - $totalActualBet;
                $winTransaction['flag_of_updated_result'] = isset($winTransaction['status']) ? $winTransaction['status'] : null;
                $winTransaction['md5_sum'] = $this->CI->original_game_logs_model->generateMD5SumOneRow($winTransaction, ['flag_of_updated_result'], ['result_amount','bet_amount']);
                $this->CI->original_game_logs_model->updateRowsToOriginal('common_seamless_wallet_transactions', $winTransaction);
                if(!$this->enable_merging_rows){
                    $where = array(
                        "transaction_type" => 'bet',
                        "transaction_id" => $winTransaction['transaction_id'],
                        "round_id" => $winTransaction['round_id']
                    );

                    $betData = array(
                        "flag_of_updated_result" => $winTransaction['flag_of_updated_result'],
                        "md5_sum" => $winTransaction['md5_sum'],
                    );
                    $this->CI->original_game_logs_model->updateRowsToOriginalFromMultipleConditions('common_seamless_wallet_transactions', $betData, $where);
                }
            }
        }

        $rollbackTransactions = []; 
        if(!$this->enable_merging_rows){
            $rollbackTransactions = $this->querySettledTransactionsForUpdate($queryDateTimeStart, $queryDateTimeEnd, 'rollback-bet');
            if(!empty($rollbackTransactions)){
                foreach ($rollbackTransactions as $key => $rollbackTransaction) {
                    $rollbackTransaction['flag_of_updated_result'] = isset($rollbackTransaction['status']) ? $rollbackTransaction['status'] : null;
                    $rollbackTransaction['md5_sum'] = $this->CI->original_game_logs_model->generateMD5SumOneRow($rollbackTransaction, ['flag_of_updated_result', 'status']);
                    $where = array(
                        "transaction_type" => 'bet',
                        "transaction_id" => $rollbackTransaction['transaction_id'],
                        "round_id" => $rollbackTransaction['round_id']
                    );

                    $betData = array(
                        "flag_of_updated_result" => $rollbackTransaction['flag_of_updated_result'],
                        "md5_sum" => $rollbackTransaction['md5_sum'],
                    );
                    $this->CI->original_game_logs_model->updateRowsToOriginalFromMultipleConditions('common_seamless_wallet_transactions', $betData, $where);
                    
                }
            }
        }
        
        return array("success"=> true, array("total_trans_updated" => count($winTransactions), "total_rolback_trans_updated" => count($rollbackTransactions)));
    }

    public function queryAmountByRoundIdAndType($roundId, $type){
        $sql = <<<EOD
SELECT
sum(amount) as total_amount
FROM common_seamless_wallet_transactions
WHERE game_platform_id = ? and round_id = ? and transaction_type = ?
EOD;
        $params=[
            $this->getPlatformCode(),
            $roundId,
            $type
        ];

        $this->CI->utils->debug_log('queryAmountByRoundIdAndType sql', $sql, $params);
        $result = $this->CI->original_game_logs_model->commonGetOneOriginalGameLogs($sql, $params);
        return $result;
    }

    public function querySettledTransactionsForUpdate($dateFrom, $dateTo, $type = 'win') {
        $this->CI->load->model('original_game_logs_model');
        $sqlTime="caleta.start_at >= ? AND caleta.end_at <= ? AND caleta.game_platform_id = ? AND caleta.flag_of_updated_result != ? AND caleta.transaction_type = ?";

        $sql = <<<EOD
SELECT
caleta.id,
caleta.external_unique_id,
caleta.transaction_id,
caleta.game_platform_id, round_id, amount, status,
caleta.transaction_type,
(
    SELECT start_at
    FROM common_seamless_wallet_transactions
    WHERE round_id = caleta.round_id
    AND transaction_type = 'bet'
    LIMIT 1
) as start_at

FROM common_seamless_wallet_transactions as caleta
WHERE
{$sqlTime}
EOD;
        $params=[
            $dateFrom,
            $dateTo,
            $this->getPlatformCode(),
            self::FLAG_UPDATED,
            $type
        ];

        $this->CI->utils->debug_log('queryTransactionsForUpdate sql', $sql, $params);
        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $result;
    }

    public function syncMergeToGameLogs($token) {
        $enabled_game_logs_unsettle=true;
        return $this->commonSyncMergeToGameLogs($token,
                $this,
                [$this, 'queryOriginalGameLogsFromTrans'],
                [$this, 'makeParamsForInsertOrUpdateGameLogsRowFromTrans'],
                [$this, 'preprocessOriginalRowForGameLogs'],
                $enabled_game_logs_unsettle);
    }

        /**
     * queryOriginalGameLogsFromTrans
     * @param  string $dateFrom
     * @param  string $dateTo
     * @param  bool   $use_bet_time
     * @return array
     */
    public function queryOriginalGameLogsFromTrans($dateFrom, $dateTo, $use_bet_time){

        $sqlTime="caleta.end_at >= ? AND caleta.end_at <= ? AND caleta.game_platform_id = ? AND caleta.flag_of_updated_result != ?";

        if(!$this->enable_merging_rows){
            $sqlTime="caleta.end_at >= ? AND caleta.end_at <= ? AND caleta.game_platform_id = ? AND caleta.transaction_type in ('win','bet')";
        }

        $statusPending = GAME_LOGS::STATUS_PENDING;
        $flagFalse = self::FLAG_FALSE;
        $sql = <<<EOD
SELECT
caleta.id as sync_index,
caleta.response_result_id,
caleta.external_unique_id as external_uniqueid,
caleta.md5_sum,

caleta.player_id,
caleta.game_platform_id,
caleta.amount,
caleta.bet_amount,
caleta.bet_amount as real_betting_amount,
caleta.result_amount,
caleta.game_id as game_code,
caleta.game_id as game,
caleta.game_id as game_name,
caleta.transaction_type,
if(caleta.flag_of_updated_result = {$flagFalse}, {$statusPending}, caleta.flag_of_updated_result) as status,
caleta.round_id as round_number,
caleta.response_result_id,
caleta.extra_info,
caleta.start_at,
caleta.start_at as bet_at,
caleta.end_at,
caleta.before_balance,
caleta.after_balance,
caleta.transaction_id,
caleta.flag_of_updated_result as status_db,

gd.id as game_description_id,
gd.game_type_id,
gd.game_name as game_name,
gd.game_name as game_description_name,
gd.english_name as game_english_name

FROM common_seamless_wallet_transactions as caleta
LEFT JOIN game_description as gd ON caleta.game_id = gd.external_game_id AND gd.game_platform_id = ?
WHERE
{$sqlTime}
EOD;


        $params=[
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo,
            $this->getPlatformCode(),
            self::FlAG_NOT_UPDATED
        ];

        $this->CI->utils->debug_log('merge sql', $sql, $params);

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
        if(empty($row['md5_sum'])){
            $this->CI->utils->error_log('no md5 on ', $row['external_uniqueid']);
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
        }

        if($this->enable_merging_rows){
            $row['after_balance'] = $row['before_balance'];
            if(($row['result_amount'] + $row['bet_amount']) > 0 ) {
                $row['after_balance'] = $row['before_balance'] + $row['result_amount'] + $row['bet_amount'];
            }
        }else{
            if($row['transaction_type']=='bet'){
                $win_amount = 0;
                $row['bet_amount'] = $row['amount']; #amount as bet_amount
                $row['real_betting_amount'] = $row['amount'];
            }else{
                $win_amount = $row['amount']; #amount as win_amount
                $row['bet_amount'] = 0;
                $row['real_betting_amount'] = 0;
            }
            $row['result_amount'] = $win_amount - $row['bet_amount'];
            $row['status_db'] = $row['status'];
        }

        return [
            'game_info' => [
                'game_type_id' => $row['game_type_id'],
                'game_description_id' => $row['game_description_id'],
                'game_code' => $row['game_name'],
                'game_type' => null,
                'game' => $row['game_name'],
            ],
            'player_info' => [
                'player_id' => $row['player_id'],
                'player_username' => null,
            ],
            'amount_info' => [
                'bet_amount' => $row['bet_amount'],
                'result_amount' => $row['result_amount'],
                'bet_for_cashback' => $row['bet_amount'],
                'real_betting_amount' => $row['real_betting_amount'],
                'win_amount' => null,
                'loss_amount' => null,
                'after_balance' => $row['after_balance'],
            ],
            'date_info' => [
                'start_at' => $row['start_at'],
                'end_at' => $row['end_at'],
                'bet_at' => $row['bet_at'],
                'updated_at' => $this->CI->utils->getNowForMysql(),
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => $row['status_db'],
            'additional_info' => [
                'has_both_side' => 0,
                'external_uniqueid' => $row['external_uniqueid'],
                'round_number' => $row['round_number'],
                'md5_sum' => $row['md5_sum'],
                'response_result_id' => $row['response_result_id'],
                'sync_index' => $row['sync_index'],
                'bet_type' => null
            ],
            /* 'bet_details' => $this->formatBetDetails($row), */
            'bet_details' => $row['bet_details'],
            'extra' => [],
            //from exists game logs
            'game_logs_id'=>isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];
    }

    private function formatBetDetails($row){
        // $rawData = json_encode($row['raw_data']);

       return  [
            'game_name' => $row['game_english_name'],
            'bet_amount' => $row['bet_amount'],
            'win_amount' => $row['amount'],
            'round_id' => $row['round_number'],
            'bet_type' => $row['transaction_type'],
            'betting_time' => $row['bet_at'],
        ];
    }


     /**
     *
     * perpare original rows, include process unknown game, pack bet details, convert game status
     *
     * @param  array &$row
     */
    public function preprocessOriginalRowForGameLogs(array &$row)
    {
        if (empty($row['game_description_id']))
        {
            $unknownGame = $this->getUnknownGame($this->getPlatformCode());
            list($game_description_id,$game_type_id) = $this->getGameDescriptionInfo($row,$unknownGame);
            $row['game_description_id']= $game_description_id;
            $row['game_type_id'] = $game_type_id;
        }

        // if(!$this->enable_merging_rows){
        //     $row['status'] = Game_logs::STATUS_SETTLED;
        // }

        #additional checking for bet status
        if($this->getSystemInfo('get_status_by_win_round', false) && $row['status'] == Game_logs::STATUS_PENDING){
            $queryStatusByWinRound = $this->queryStatusByWinRound($row);
            if(!empty($queryStatusByWinRound['status'])){
                $row['status'] = $queryStatusByWinRound['status'];
            }
        }

        $row['bet_details'] = $this->preprocessBetDetails($row);
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

    public function queryTransaction($transactionId, $extra) {
        return $this->returnUnimplemented();
    }

    public function queryBetDetailLink($playerUsername, $betId = null, $extra = null) {

        $gameUsername = $this->getGameUsernameByPlayerUsername($playerUsername);
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryBetDetailLink'
        );

        $params = array(
            'operator_id' => $this->operator_id,
            'transaction_uuid' => $betId,
            'user' => $gameUsername,
            "round" => $extra
        );

        $this->signature = $this->getSign($params);

        $this->CI->utils->debug_log('-----------------------caleta queryBetDetailLink params ----------------------------',$params);

        if ($this->force_bet_detail_default_format) {
            return parent::queryBetDetailLink($playerUsername, $betId, $extra);
        }

        return $this->callApi(self::API_queryBetDetailLink, $params, $context);
    }

    public function processResultForQueryBetDetailLink($params){
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);

        $success = $this->processResultBoolean($responseResultId, $resultArr,$statusCode);
        $result['url'] = ( $success && isset($resultArr['url']) ) ? $resultArr['url'] : null;
        if(!$success && isset($resultArr['code']) && isset($resultArr['message'])){ #possible error
            $result['message'] = $resultArr['message'];
        }
        return array($success, $result);
    }

    public function getTransactionsTable(){
        return $this->original_transactions_table;
    }

    public function queryStatusByWinRound($row){
        $sqlRound = "original.round_id=? AND original.player_id=? AND original.game_platform_id=? AND original.transaction_type=?";

        $sql = <<<EOD
SELECT
original.status

FROM common_seamless_wallet_transactions as original
WHERE
{$sqlRound};
EOD;
        $params=[
            $row['round_number'],
            $row['player_id'],
            $this->getPlatformCode(),
            'win'
        ];

        $this->CI->utils->debug_log('queryBetStatus sql', $sql, $params);
        $result = $this->CI->original_game_logs_model->commonGetOneOriginalGameLogs($sql, $params);
        return $result;
    }

    public function getUnsettledRounds($dateFrom, $dateTo){
        $sqlTime='original.created_at >= ? AND original.created_at <= ?';

        $this->CI->load->model(array('original_game_logs_model'));
        $pendingStatus = self::FlAG_NOT_UPDATED;
        $transType = 'bet';

        $sql = <<<EOD
SELECT 
original.round_id as round_id, original.transaction_id, game_platform_id
from common_seamless_wallet_transactions as original
where
original.status=?
and original.transaction_type=?
and {$sqlTime}
EOD;

        $params=[
            $pendingStatus,
            $transType,
            $dateFrom,
            $dateTo
		];

        $platformCode = $this->getPlatformCode();
	    $this->CI->utils->debug_log('CALETA SEAMLESS-' .$platformCode.' (getUnsettledRounds)', 'params',$params,'sql',$sql);
        return  $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    }

    public function checkBetStatus($data){
        $this->CI->load->model(['seamless_missing_payout']);

        $roundId = $data['round_id'];
        $transactionId = $data['transaction_id'];
        $baseAmount = 0;
     
        $sql = <<<EOD
SELECT 
original.created_at as transaction_date,
original.transaction_type as transaction_type,
original.status as status,
original.game_platform_id,
original.player_id,
original.round_id as round_id,
original.transaction_id,
ABS(SUM(original.bet_amount)) as amount,
ABS(SUM(original.bet_amount)) as deducted_amount,
gd.id as game_description_id,
gd.game_type_id,
original.external_unique_id as external_uniqueid
from common_seamless_wallet_transactions as original
left JOIN game_description as gd ON original.game_id = gd.external_game_id and gd.game_platform_id=?
where
round_id=? and transaction_id=? and original.game_platform_id=?
EOD;
        
        $params=[$this->getPlatformCode(), $roundId, $transactionId, $this->getPlatformCode()];

        $transactions  = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);

        foreach($transactions as $transaction){
            if($transaction['game_platform_id']){
                $transaction['transaction_status'] = Game_logs::STATUS_PENDING;
                $status = $this->checkIfAlreadySettled($this->getPlatformCode(), $roundId);
                if(isset($status['flag_of_updated_result']) && $status['flag_of_updated_result'] == self::FLAG_UPDATED){
                    continue;
                }
                $transaction['added_amount'] = $baseAmount;
                $transaction['status'] = Seamless_missing_payout::NOT_FIXED;

                $result = $this->CI->original_game_logs_model->insertIgnoreRowsToOriginal('seamless_missing_payout_report',$transaction);
                if($result===false){
                    $this->CI->utils->error_log('CALETA SEAMLESS-' .$this->getPlatformCode().'(checkBetStatus) Error insert missing payout', $transaction);
                }
            }
        }
        
        if(empty($trans)){
            return array('success'=>false, 'exists'=>false);
        }
    }

    public function checkIfAlreadySettled($game_platform_id, $round_id){
        $this->CI->load->model(['original_game_logs_model']);
        $transaction_type = 'win';

        $sql = <<<EOD
SELECT 
original.flag_of_updated_result as flag_of_updated_result
from common_seamless_wallet_transactions as original
WHERE
original.game_platform_id=? AND original.round_id=? AND original.transaction_type=?
EOD;
     
        $params=[$game_platform_id, $round_id, $transaction_type];

        return $this->CI->original_game_logs_model->commonGetOneOriginalGameLogs($sql, $params);
    }
    
    
    public function queryBetTransactionStatus($game_platform_id, $external_uniqueid){
        $this->CI->load->model(['original_game_logs_model']);
        $this->CI->load->model(['seamless_missing_payout']);

        $transaction_id = explode('bet-',$external_uniqueid)[1];
        $transaction_type_win = 'win';
        $transaction_type_rollback = 'rollback-bet';

        $sql = <<<EOD
SELECT 
original.status as status
from common_seamless_wallet_transactions as original
WHERE
original.game_platform_id=? AND original.transaction_id=? AND (original.transaction_type=? OR original.transaction_type =?)
EOD;
     
        $params=[$game_platform_id, $transaction_id, $transaction_type_win, $transaction_type_rollback];

        $trans = $this->CI->original_game_logs_model->commonGetOneOriginalGameLogs($sql, $params);

        if(!empty($trans)){
            $status = Game_logs::STATUS_PENDING;
            if($trans['status'] == Game_logs::STATUS_SETTLED || $trans['status'] == Game_logs::STATUS_CANCELLED){
                $status = $trans['status'];
            }
            return array('success'=>true, 'status'=>$status);
        }
        return array('success'=>false, 'status'=>Game_logs::STATUS_PENDING);
    }
    
    public function preprocessOriginalRowForBetDetails($row, $extra = []) {
        // print_r($row);
        $bet_details = $row;

        $this->CI->load->model(['original_seamless_wallet_transactions']);

        // win transaction have complete details
        $transaction = $this->CI->original_seamless_wallet_transactions->querySingleTransactionCustom($this->original_transactions_table, [
            'transaction_type' => 'win',
            'player_id' => $row['player_id'],
            'round_id' => $row['round_number'],
        ]);

        if (isset($row['transaction_id'])) {
            $bet_details['bet_id'] = $row['transaction_id'];
        }

        if (isset($transaction['bet_amount'])) {
            $bet_details['bet_amount'] = $transaction['bet_amount'];
        }

        if (isset($transaction['amount'])) {
            $bet_details['win_amount'] = $transaction['amount'];
        }

        if (isset($row['game_code'])) {
            $bet_details['game_name'] = $row['game_code'];
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

        // print_r($bet_details);exit;
        return $bet_details;
    }
}
