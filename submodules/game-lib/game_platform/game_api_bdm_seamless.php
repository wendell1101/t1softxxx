<?php
/**
 * BDM JOKER game integration
 * OGP-23068
 *
 * @author  Jerbey Capoquian
 *
 *
 * 
 *
 * By function:
    <site>/bdmjoker_service_api/authenticate-token
    <site>/bdmjoker_service_api/authenticate
    <site>/bdmjoker_service_api/balance
    <site>/bdmjoker_service_api/bet
    <site>/bdmjoker_service_api/settle-bet
    <site>/bdmjoker_service_api/cancel-bet
    <site>/bdmjoker_service_api/bonus-win
    <site>/bdmjoker_service_api/jackpot-win
    <site>/bdmjoker_service_api/transaction
    <site>/bdmjoker_service_api/withdraw
    <site>/bdmjoker_service_api/deposit
 *
 * 
 * Related File
     - bdmjoker_service_api.php
 */
require_once dirname(__FILE__) . '/abstract_game_api.php';

class Game_api_bdm_seamless extends Abstract_game_api {


    const ORIGINAL_LOGS_TABLE_NAME = 'bdm_seamless_game_logs';
    const ORIGINAL_TRANSACTION_TABLE = 'common_seamless_wallet_transactions';
    
    const MD5_FIELDS_FOR_MERGE=['start_at', 'end_at', 'status_db', 'status'];
    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE=['bet_amount','result_amount'];

    const FLAG_UPDATED = 1;
    const FlAG_NOT_UPDATED = 0;
    const ERROR_CODE_SUCCESS = 0;

    public function __construct() {
        parent::__construct();
        $this->original_transaction_table = self::ORIGINAL_TRANSACTION_TABLE;

        $this->apiUrl = $this->getSystemInfo('url','http://api688.net/seamless');
        $this->lang = $this->getSystemInfo('lang','en');
        $this->lobby_url = $this->getSystemInfo('lobby_url');
        $this->app_id = $this->getSystemInfo('app_id');
        $this->secret_key = $this->getSystemInfo('secret_key');
        $this->forward_url = $this->getSystemInfo('forward_url','http://gwc788.net/playGame');
        $this->redirect = $this->getSystemInfo('redirect', false);
        $this->force_lang = $this->getSystemInfo('force_lang', false);
        $this->hash_precision = $this->getSystemInfo('hash_precision', 2);

        #service system info
        $this->allowed_invalid_token_on_request = $this->getSystemInfo('allowed_invalid_token_on_request', false);
        $this->force_bet_failed_response = $this->getSystemInfo('force_bet_failed_response', false);
        $this->force_rollback_failed_response = $this->getSystemInfo('force_rollback_failed_response', false);
        $this->force_win_failed_response = $this->getSystemInfo('force_win_failed_response', false);

        $this->seamless_debit_transaction_type = $this->getSystemInfo('seamless_debit_transaction_type', ['withdraw', 'bet']);
    }

    const URI_MAP = array(
        self::API_queryGameListFromGameProvider => '/list-games',
        self::API_checkTicketStatus => '/game-round-status'
    );

    public function isSeamLessGame()
    {
        return true;
    }

    public function getPlatformCode() {
        return BDM_SEAMLESS_API;
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
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json')); 
        
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
        if(@$statusCode == 200 && isset($resultArr['Error']) && $resultArr['Error'] == self::ERROR_CODE_SUCCESS){
            $success = true;
        }

        if (!$success) {
            $this->setResponseResultToError($responseResultId);
            $this->CI->utils->debug_log('bdm Seamless got error ', $responseResultId,'result', $resultArr);
        }
        return $success;     
    }

    public function generateHash($params){
        $secretKey = $this->secret_key;
        $array = array_filter($params);
        $array = array_change_key_case($array, CASE_LOWER);
        ksort($array);  

        $rawData = '';
        $precision = $this->hash_precision;
        $decimalKeys = ["amount", "endbalance", "result", "startbalance"];
        foreach ($array as $Key => $Value){
            if(in_array($Key, $decimalKeys)){
                // $Value = number_format($Value, 2, '.','');
                $Value = bcdiv($Value, 1, $precision);
            }
            $rawData .=  $Key . '=' . $Value . '&' ;
        }
        $rawData = substr($rawData,0, -1);
        $rawData .= $secretKey;
        $hash = md5($rawData);
        return $hash;
    }

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
        $return = parent::createPlayer($playerName, $playerId, $password, $email, $extra);
        $success = false;
        $message = "Unable to create Account for bdm Game";
        if($return){
            $success = true;
            $message = "Successfull create account for bdm Game.";
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

        $this->CI->utils->debug_log('bdm: (' . __FUNCTION__ . ')', 'PARAMS:', $playerName, 'RESULT:', $result);

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



    public function getLauncherLanguage($language) {
        if($this->force_lang){
            return $this->lang;
        }
        switch ($language) {
            case LANGUAGE_FUNCTION::INT_LANG_ENGLISH:
                $lang = 'en'; 
                break;
            case LANGUAGE_FUNCTION::INT_LANG_CHINESE:
                $lang = 'zh';
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
    
        if(empty($this->lobby_url)){
            $this->lobby_url = $this->utils->getSystemUrl('player');
            $this->appendCurrentDbOnUrl($this->lobby_url);
        }
        
        if (isset($extra['home_link'])&&!empty($extra['home_link'])) {
            $this->lobby_url = $extra['home_link']; //? Game exit URL
        }
        
        if (isset($extra['extra']['home_link'])&&!empty($extra['extra']['home_link'])) {
            $this->lobby_url = $extra['extra']['home_link']; //? Game exit URL
        }

        $params = array(
            'appID' => $this->app_id,
            'token' => $token,
            'gameCode' => $extra['game_code'],
            'language' => $lang,
            'mobile' => $extra['is_mobile'],
            'redirectUrl' => $this->lobby_url
        );

        $this->utils->debug_log(__METHOD__ . ' params', $params, 'extra', $extra);
        
        $forward_url = $this->forward_url;
        $url = $forward_url . "?" . http_build_query($params);
        $data = array(
            "success" => true,
            "url" => $url,
            "redirect" => $this->redirect
        );
        return $data;
    }

    public function queryGameListFromGameProvider($extra=null){

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryGameListFromGameProvider'
        );

        $params = array(
            'AppID' => $this->app_id,
            'Timestamp' => round( (microtime(true)* 1000) )
        );
        $params['hash'] = $this->generateHash($params);

        $this->CI->utils->debug_log('-----------------------bdm queryGameListFromGameProvider params ----------------------------',$params);
        return $this->callApi(self::API_queryGameListFromGameProvider, $params, $context);
    }

    public function processResultForQueryGameListFromGameProvider($params) {
        $resultArr = $this->getResultJsonFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr,$statusCode);
        $result['games'] = [];
        if($success){
            if(isset($resultArr['ListGames'])){
                $result['games'] = $resultArr['ListGames'];
                if(!empty($result['games'])){
                    $this->updateGameList($result['games']);
                }
            }
        } else {
            if(isset($resultArr['Message'])){
                $result['message'] = $resultArr['Message'];
            }
        }
        return array($success, $result);
    }

    public function updateGameList($games) {

        $this->CI->load->model(array('original_game_logs_model'));
        $this->preProccessGames($games);

        list($insertRows, $updateRows)=$this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
            'bdm_gamelist',
            $games,
            'external_uniqueid',
            'external_uniqueid',
            ['game_name','game_alias','supported_platforms','image1'],
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
                $caption = "## UPDATE BDM GAME LIST\n";
            }
            else {
                $caption = "## ADD NEW BDM GAME LIST\n";
            }

            $body = "| English Name  | Chinese Name  | Game Code | Game Type | Supported Platforms |\n";
            $body .= "| :--- | :--- | :--- |\n";
            foreach ($data as $record) {
                if ($queryType == 'update') {
                    $record['updated_at'] = $this->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->updateRowsToOriginal('bdm_gamelist', $record);
                    $body .= "| {$record['game_name']} | N/A | {$record['game_code']} | {$record['game_type']} | {$record['supported_platforms']} |\n";
                } else {
                    unset($record['id']);
                    $record['created_at'] = $this->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->insertRowsToOriginal('bdm_gamelist', $record);
                    $body .= "| {$record['game_name']} | N/A | {$record['game_code']} | {$record['game_type']} | {$record['supported_platforms']} |\n";
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
            "#bdm Game"
        ];

        $channel = $this->utils->getConfig('game_list_notification_channel');
        $this->CI->load->helper('mattermost_notification_helper');
        $channel = $channel;
        $user = 'bdm Game List';

        sendNotificationToMattermost($user, $channel, [], $message);

    }


    public function preProccessGames(&$games) {
        if(!empty($games)){
            foreach ($games as $key => $game) { 
                $data['game_type'] = $game['GameType'];
                $data['game_code'] = $game['GameCode'];
                $data['game_name'] = $game['GameName'];
                $data['game_alias'] = $game['GameAlias'];
                $data['specials'] = $game['Specials'];
                $data['supported_platforms'] = $game['SupportedPlatForms'];
                $data['order'] = $game['Order'];
                $data['default_width'] = $game['DefaultWidth'];
                $data['default_height'] = $game['DefaultHeight'];
                $data['image1'] = $game['Image1'];
                $data['external_uniqueid'] = $game['GameCode'];
                $data['md5_sum'] = $this->CI->original_game_logs_model->generateMD5SumOneRow($data, ['game_name','game_alias','supported_platforms','image1']);
                $games[$key] = $data;
                unset($data);
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

        $settledTrans = $this->queryBetTransactionsForUpdate($queryDateTimeStart, $queryDateTimeEnd);
        if(!empty($settledTrans)){
            foreach ($settledTrans as $key => $trans) {
                if($trans['transaction_type'] == "settle_bet"){ #slots and others
                    $betInfo = $this->queryBetInfo($trans['round_id'], 'bet', $trans['player_id']);
                    $cancelBetInfo = $this->queryBetInfo($trans['round_id'], 'cancel_bet', $trans['player_id']);
                    $totalBetAmount = (float)$betInfo['total_amount'] -(float)$cancelBetInfo['total_amount'];
                    $totalPayout = $trans['amount'];
                    $trans['bet_amount'] = $totalBetAmount;
                    $trans['result_amount'] = $totalPayout - $totalBetAmount;
                } else if($trans['transaction_type'] == "jackpot_win" || $trans['transaction_type'] == "bonus_win"){
                    $trans['bet_amount'] = 0;
                    $trans['result_amount'] = $trans['amount'];  
                }

                $trans['flag_of_updated_result'] = self::FLAG_UPDATED;
                $this->CI->original_game_logs_model->updateRowsToOriginal('common_seamless_wallet_transactions', $trans);
            }
        }
        return array("success"=> true, array("total_trans_updated" => count($settledTrans)));
    }

    public function queryBetTransactionsForUpdate($dateFrom, $dateTo) {
        $this->CI->load->model('original_game_logs_model');
        $sqlTime="bdm.start_at >= ? AND bdm.end_at <= ? AND bdm.game_platform_id = ? AND bdm.flag_of_updated_result != ? AND bdm.transaction_type in('settle_bet','transaction','jackpot_win','bonus_win')";

        $sql = <<<EOD
SELECT
bdm.id,
bdm.external_unique_id,
bdm.player_id,
bdm.game_platform_id, round_id,
bdm.transaction_type,
bdm.amount

FROM common_seamless_wallet_transactions as bdm
WHERE
{$sqlTime}
EOD;
        $params=[
            $dateFrom,
            $dateTo,
            $this->getPlatformCode(),
            self::FLAG_UPDATED
        ];

        $this->CI->utils->debug_log('queryTransactionsForUpdate sql', $sql, $params);
        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $result;  
    }

    public function queryBetInfo($roundId, $type, $playerId){
        $sql = <<<EOD
SELECT
sum(amount) as total_amount,
min(start_at) as bet_time
FROM common_seamless_wallet_transactions
WHERE game_platform_id = ? and round_id = ? and transaction_type = ? and player_id = ?
EOD;
        $params=[
            $this->getPlatformCode(),
            $roundId,
            $type,
            $playerId
        ];

        $this->CI->utils->debug_log('queryAmountByTransactionIdAndType sql', $sql, $params);
        $result = $this->CI->original_game_logs_model->commonGetOneOriginalGameLogs($sql, $params);
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
        $sqlTime="bdm.end_at >= ? AND bdm.end_at <= ? AND bdm.game_platform_id = ? AND bdm.flag_of_updated_result != ?";

        $sql = <<<EOD
SELECT
bdm.id as sync_index,
bdm.response_result_id,
bdm.external_unique_id as external_uniqueid,
bdm.md5_sum,

bdm.player_id,
bdm.game_platform_id,
bdm.bet_amount,
bdm.bet_amount as real_betting_amount,
bdm.result_amount,
bdm.game_id as game_code,
bdm.game_id as game,
bdm.game_id as game_name,
bdm.transaction_type,
bdm.flag_of_updated_result as status,
bdm.round_id as round_number,
bdm.response_result_id,
bdm.extra_info,
bdm.start_at,
bdm.start_at as bet_at,
bdm.end_at,
bdm.before_balance,
bdm.after_balance,
bdm.transaction_id,

gd.id as game_description_id,
gd.game_type_id

FROM common_seamless_wallet_transactions as bdm
LEFT JOIN game_description as gd ON bdm.game_id = gd.external_game_id AND gd.game_platform_id = ?
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
            'status' => $row['status'],
            'additional_info' => [
                'has_both_side' => 0,
                'external_uniqueid' => $row['external_uniqueid'],
                'round_number' => $row['round_number'],
                'md5_sum' => $row['md5_sum'],
                'response_result_id' => $row['response_result_id'],
                'sync_index' => $row['sync_index'],
                'bet_type' => null
            ],
            'bet_details' => [],
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
    public function preprocessOriginalRowForGameLogs(array &$row)
    {
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

    public function queryTransaction($transactionId, $extra) {
        return $this->returnUnimplemented();
    }

    public function queryRoundStatus($round = '', $username = ''){

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryRoundStatus'
        );

        $params = array(
            'AppID' => $this->app_id,
            'RoundID' => $round,
            'Timestamp' => round( (microtime(true)* 1000) )
        );
        $params['hash'] = $this->generateHash($params);
        $params['Username'] = $username;

        $this->CI->utils->debug_log('-----------------------bdm queryRoundStatus params ----------------------------',$params);
        return $this->callApi(self::API_checkTicketStatus, $params, $context);
    }

    public function processResultForQueryRoundStatus($params) {
        $resultArr = $this->getResultJsonFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr,$statusCode);
        return array($success, $resultArr);
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
original.flag_of_updated_result=?
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
	    $this->CI->utils->debug_log('BDM SEAMLESS-' .$platformCode.' (getUnsettledRounds)', 'params',$params,'sql',$sql);
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
                    $this->CI->utils->error_log('BDM SEAMLESS-' .$this->getPlatformCode().'(checkBetStatus) Error insert missing payout', $transaction);
                }
            }
        }
        
        if(empty($trans)){
            return array('success'=>false, 'exists'=>false);
        }
    }

    public function checkIfAlreadySettled($game_platform_id, $round_id){
        $this->CI->load->model(['original_game_logs_model']);
        $transaction_type = 'settle_bet';

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

        $round_id = explode('bet-B-',$external_uniqueid)[1];
        $transaction_type = 'settle_bet';

        $sql = <<<EOD
SELECT 
original.flag_of_updated_result as status
from common_seamless_wallet_transactions as original
WHERE
original.game_platform_id=? AND original.round_id=? AND original.transaction_type=?
EOD;
     
        $params=[$game_platform_id, $round_id, $transaction_type];

        $trans = $this->CI->original_game_logs_model->commonGetOneOriginalGameLogs($sql, $params);

        if(!empty($trans)){
            $status = Game_logs::STATUS_PENDING;
            if($trans['status'] == self::FLAG_UPDATED){
                $status = Game_logs::STATUS_SETTLED;
            }
            return array('success'=>true, 'status'=>$status);
        }
        return array('success'=>false, 'status'=>Game_logs::STATUS_PENDING);
    }

}
