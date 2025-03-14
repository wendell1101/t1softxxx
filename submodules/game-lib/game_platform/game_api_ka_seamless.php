<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

class Game_api_ka_seamless extends Abstract_game_api {

    const ORIGINAL_GAMELOGS_TABLE = '';
    const ORIGINAL_TRANSACTION_TABLE = 'common_seamless_wallet_transactions';

    const MODE_FUN = 0;
    const MODE_REAL = 1;

    const REVOKE = "revoke";
    const PLAY = "play";
    const CREDIT = "credit";

    const STATUS_OK = 'ok';
    const STATUS_REVOKED = 'revoked';

    public function getPlatformCode(){
        return KA_SEAMLESS_API;
    }

    public function isSeamLessGame()
    {
       return true;
    }

    // Fields in game_logs we want to detect changes for merge, and only available when original md5_sum is empty
    const MD5_FIELDS_FOR_MERGE = [
        'external_uniqueid',
        'bet_amount',
        'real_betting_amount',
        'result_amount',
        'round_number',
        'game_code',
        'game_name',
        'start_at',
        'end_at',
        'bet_at',
    ];

    // Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE = [
        'bet_amount',
        'real_betting_amount',
        'result_amount',
        'after_balance',
        'before_balance'
    ];

    public function __construct(){
        parent::__construct();
        $this->original_gamelogs_table = self::ORIGINAL_GAMELOGS_TABLE;
        $this->original_transaction_table = self::ORIGINAL_TRANSACTION_TABLE;

        $this->api_url = $this->getSystemInfo('url');
        $this->api_key = $this->getSystemInfo('key');
        $this->api_secret = $this->getSystemInfo('secret');
        $this->player_mode = $this->getSystemInfo('player_mode', self::MODE_REAL);
        $this->game_launch_url = $this->getSystemInfo('game_launch_url');
        $this->partner_name = $this->getSystemInfo('partner_name');
        $this->currency = $this->getSystemInfo('currency','BRL');

        $this->encryption_key = $this->getSystemInfo('encryption_key', 'yrdSg4BWkYuZPK8p');
        $this->secret_encription_iv = $this->getSystemInfo('secret_encription_iv', 'XuZDCW4ReWDhdNau');
        $this->encrypt_method = $this->getSystemInfo('encrypt_method', 'AES-256-CBC');
    }

    public function getSecretKey() {

        return $this->api_secret;

    }

    public function getTransactionsTable(){
        return $this->original_transactions_table;
    }

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
        $return = $this->createPlayerInDB($playerName, $playerId, $password, $email, $extra);
        $success = false;
        $message = "Unable to create account for KA GAMING";
        if($return){
            $success = true;
            // $this->setGameAccountRegistered($playerId);
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
            $message = "Successfully created account for KA GAMING";
        }

        return array("success" => $success, "message" => $message);
    }

    public function queryForwardGame($playerName, $extra) {

        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $game_code = isset($extra['game_code']) ? $extra['game_code'] : null;
        $language = $this->getSystemInfo('language', $extra['language']);

        $token = $this->getPlayerTokenByGameUsername($gameUsername);


        $params = array(
            "g" => $game_code,
            "p" => $this->partner_name,
            "u" => $gameUsername,
            "t" => $token,
            "ak" => $this->api_key,
            "loc" => $this->getLauncherLanguage($language),
            "cr" => $this->currency,
            "if" => 1,
            "l" => $this->getHomeLink()
        );

        $url = $this->game_launch_url . '?' . http_build_query($params);

        $this->CI->utils->debug_log('KAGAMINGSeamless: (' . __FUNCTION__ . ')', 'PARAMS:', $params, 'URL:', $url);

        return array('success'=>true,'url' => $url);

    }

    // public function queryPlayerBalance($playerName) {

    //     $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
    //     $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
    //     $balance = $this->CI->player_model->getPlayerSubWalletBalance($playerId, $this->getPlatformCode());

    //     $result = array(
    //         'success' => true,
    //         'balance' => $balance // no conversion if seamless. should be convert via service api (OGP-24712)
    //         // 'balance' => $this->dBtoGameAmount($balance)
    //     );

    //     $this->utils->debug_log(__FUNCTION__,'KA Gaming (Query Player Balance): ', $result);

    //     return $result;

    // }

    public function getLauncherLanguage($language){
        $lang='';
        switch ($language) {
        	case Language_function::INT_LANG_ENGLISH:
            case 'en':
            case 'en-us':
                $lang = 'en'; // english
                break;
            case Language_function::INT_LANG_CHINESE:
            case 'cn':
            case 'zh-cn':
                $lang = 'zh'; // chinese
                break;
            case Language_function::INT_LANG_INDONESIAN:
            case 'id':
            case 'id-id':
                $lang = 'id'; // indonesia
                break;
            case Language_function::INT_LANG_VIETNAMESE:
            case 'vi':
            case 'vi-vn':
                $lang = 'vi'; // vietnamese
                break;
            case Language_function::INT_LANG_KOREAN:
            case 'ko-kr':
                $lang = 'en'; // korean
                break;
            case Language_function::INT_LANG_THAI:
            case 'th-th':
            case 'th':
                $lang = 'th'; // thai
                break;
            default:
                $lang = 'en'; // default as english
                break;
        }
        return $lang;
    }

    public function depositToGame($playerName, $amount, $transfer_secure_id = null) {
        $this->utils->debug_log("KA SEAMLESS: (depositToGame)");

        $external_transaction_id = $transfer_secure_id;
        return array(
            'success' => true,
            'external_transaction_id' => $external_transaction_id,
            'response_result_id ' => NULL,
            'didnot_insert_game_logs'=>true,
            'transfer_status'=>self::COMMON_TRANSACTION_STATUS_APPROVED,
            'reason_id'=>self::REASON_UNKNOWN,
        );

    }

    public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null) {
        $this->utils->debug_log("KA SEAMLESS: (withdrawFromGame)");

        $external_transaction_id = $transfer_secure_id;
        return array(
            'success' => true,
            'external_transaction_id' => $external_transaction_id,
            'response_result_id ' => NULL,
            'didnot_insert_game_logs'=>true,
            'transfer_status'=>self::COMMON_TRANSACTION_STATUS_APPROVED,
            'reason_id'=>self::REASON_UNKNOWN,
        );
    }

    public function queryTransaction($transactionId, $extra)
    {
        return $this->returnUnimplemented();
    }

    public function syncOriginalGameLogs($token)
    {
        return $this->returnUnimplemented();
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

    /* queryOriginalGameLogsFromTrans
     * @param  string $dateFrom
     * @param  string $dateTo
     * @param  bool   $use_bet_time
     * @return array
     */
    public function queryOriginalGameLogsFromTrans($dateFrom, $dateTo, $use_bet_time){

        $gameRecords = $this->getDataFromTrans($dateFrom, $dateTo, $use_bet_time);

        $rebuildGameRecords = array();

        $this->processGameRecordsFromTrans($gameRecords, $rebuildGameRecords);

        return $rebuildGameRecords;

    }

    public function getDataFromTrans($dateFrom, $dateTo) {
        #query bet only
        $sqlTime="ka.end_at >= ? AND ka.end_at <= ? AND ka.game_platform_id = ? AND ka.transaction_type != 'revoke'";
        $md5Fields = implode(", ", array('ka.bet_amount', 'ka.result_amount', 'ka.after_balance', 'ka.before_balance', 'ka.round_id', 'ka.game_id', 'ka.start_at', 'ka.end_at'));

        $sql = <<<EOD
SELECT
ka.id as sync_index,
ka.response_result_id,
ka.external_unique_id as external_uniqueid,
MD5(CONCAT({$md5Fields})) as md5_sum,
game_provider_auth.login_name as player_username,
ka.player_id,
ka.game_platform_id,
ka.bet_amount as bet_amount,
ka.bet_amount as real_betting_amount,
ka.amount,
ka.game_id as game_code,
ka.result_amount as result_amount,
ka.transaction_type,
ka.status,
ka.round_id as round_number,
ka.extra_info,
ka.start_at,
ka.start_at as bet_at,
ka.end_at,
ka.before_balance,
ka.after_balance,
ka.transaction_type,
ka.transaction_id,

gd.id as game_description_id,
gd.game_type_id

FROM common_seamless_wallet_transactions as ka
LEFT JOIN game_description as gd ON ka.game_id = gd.external_game_id AND gd.game_platform_id = ?
LEFT JOIN game_provider_auth
ON
	ka.player_id = game_provider_auth.player_id AND
	game_provider_auth.game_provider_id = ?
WHERE
{$sqlTime}
EOD;

        $params=[
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo,
            $this->getPlatformCode()
        ];

        $this->CI->utils->debug_log('merge sql', $sql, $params);

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $result;

    }

    public function processGameRecordsFromTrans(&$gameRecords, &$rebuildGameRecords) {

        $transaction_ids = array();

        if(!empty($gameRecords)) {
            foreach ($gameRecords as $index => $record) {

                if(!in_array($record['transaction_id'], $transaction_ids)) {

                    $transaction_type = $record['transaction_type'];

                    $temp_game_records = $record;
                    $temp_game_records['player_id'] = isset($record['player_id']) ? $record['player_id'] : null;
                    $temp_game_records['sync_index'] = isset($record['sync_index']) ? $record['sync_index'] : null;
                    $temp_game_records['player_username'] = isset($record['player_username']) ? $record['player_username'] : null;

                    $temp_game_records['game_code'] = isset($record['game_code']) ? $record['game_code'] : null;
                    $temp_game_records['game_name'] = isset($record['game_code']) ? $record['game_code'] : null;
                    $temp_game_records['round_number'] = isset($record['round_number']) ? $record['round_number'] : null;
                    $temp_game_records['external_uniqueid'] = isset($record['transaction_id']) ? $record['transaction_id'] : null;
                    $temp_game_records['response_result_id'] = isset($record['response_result_id']) ? $record['response_result_id'] : null;
                    $temp_game_records['md5_sum'] = isset($record['md5_sum']) ? $record['md5_sum'] : null;
                    $temp_game_records['start_at'] = isset($record['start_at']) ? $record['start_at'] : null;
                    $temp_game_records['bet_at'] = isset($record['bet_at']) ? $record['bet_at'] : null;
                    $temp_game_records['end_at'] = isset($record['end_at']) ? $record['end_at'] : null;
                    $temp_game_records['before_balance'] = isset($record['before_balance']) ? $record['before_balance'] : null;
                    $temp_game_records['after_balance'] = isset($record['after_balance']) ? $record['after_balance'] : null;
                    $temp_game_records['transaction_type'] = isset($record['transaction_type']) ? $record['transaction_type'] : null;
                    $temp_game_records['result_amount'] = $temp_game_records['amount'];
                    $temp_game_records['bet_amount'] = $record["bet_amount"] !== null ? $record["bet_amount"] : 0;
                    $temp_game_records['real_betting_amount'] = $record["bet_amount"] !== null ? $record["bet_amount"] : 0;

                    $temp_game_records['status'] = $this->getGameRecordsStatus($record['status']);
                    // $gameRecords[$index] = $temp_game_records;
                    $rebuildGameRecords[] = $temp_game_records;
                    $transaction_ids[] = $record['transaction_id'];
                    unset($data);

                }

            }

        }

    }

    private function queryBetAndResultAmount($round_id, $transaction_id) {

        $sqlTime='ka.round_id = ? and ka.transaction_id = ? and ka.game_platform_id = ? and ( ka.transaction_type="play" or  ka.transaction_type="revoke" )';

        $sql = <<<EOD
SELECT
ka.id as sync_index,
ka.bet_amount,
ka.result_amount,
ka.end_at,
ka.after_balance,
ka.transaction_type,
ka.extra_info,
ka.game_platform_id
FROM common_seamless_wallet_transactions as ka
WHERE
{$sqlTime}
EOD;

        $params=[
            $round_id,
            $transaction_id,
            $this->getPlatformCode()
        ];
        $this->CI->utils->debug_log('merge sql', $sql, $params);

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);


        return $this->processBetAndResultAmount($result);

    }

    private function processBetAndResultAmount($datas) {

        $result = array("bet_amount" => 0,
                        "win_amount" => 0,
                        "after_balance" => 0,
                        "transaction_type" => '',
                        "revoked" => false);

        if(!empty($datas)){


            $total_bet_amount = 0;

            $total_result_amount = 0;

            $after_balance = 0;

            $transaction_type = "";

            $revoked = false;


            foreach($datas as $data) {

                $extra_info_arr = json_decode($data["extra_info"]);

                $free_games = isset($extra_info_arr->FreeGames) ? $extra_info_arr->FreeGames : false;

                $transaction_type = $data["transaction_type"];

                if($transaction_type == self::PLAY) {

                    if(!$free_games) {

                        $total_bet_amount += $data["bet_amount"];

                    }

                    $total_result_amount += $data["result_amount"];

                } else if ($transaction_type == self::REVOKE) {

                    if(!$free_games) {

                        $total_bet_amount -= $data["bet_amount"];

                    }

                    $total_result_amount -= $data["result_amount"];

                    $revoked = true;

                }

                $after_balance = $data["after_balance"];
                $transaction_type = $data["transaction_type"];

            }

            $result = array(
                                "bet_amount" => $total_bet_amount,
                                "win_amount" => $total_result_amount,
                                "after_balance" => $after_balance,
                                "transaction_type" => $transaction_type,
                                "revoked" => $revoked
                            );



        }
        return $result;
    }

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
                'player_username' => $row['player_username'],
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

    /**
     * overview : get game record status
     *
     * @param $status
     * @return int
     */
    private function getGameRecordsStatus($status) {

        switch (strtolower($status)) {
            case self::STATUS_OK:
                $status = Game_logs::STATUS_SETTLED;
                break;
            case self::STATUS_REVOKED:
                $status = Game_logs::STATUS_REFUND;
                break;
            default:
                $status = Game_logs::STATUS_PENDING;
                break;
        }
        return $status;
    }

    public function generateUrl($apiName, $params)
    {
        return $this->returnUnimplemented();
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
                $extra_info = @json_decode($transaction['extra_info'], true);
                $extra=[];
                $extra['trans_type'] = $transaction['trans_type'];
                $extra['extra'] = $extra_info;
                $temp_game_record['extra_info'] = json_encode($extra);
                $temp_game_record['external_uniqueid'] = $transaction['external_uniqueid'];

                $temp_game_record['transaction_type'] = Transactions::GAME_API_ADD_SEAMLESS_BALANCE;
                if($transaction['amount']<0){
                    $temp_game_record['transaction_type'] = Transactions::GAME_API_SUBTRACT_SEAMLESS_BALANCE;
                }

                $temp_game_records[] = $temp_game_record;
                unset($temp_game_record);
            }
        }

        $transactions = $temp_game_records;
    }
}
/*end of file*/