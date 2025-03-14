<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

class Game_api_betsoft extends Abstract_game_api {

    private $bank_id;
    private $url;
    private $pass_key;
    private $currency;
    private $default_language;
    private $token_timeout;

    const SUCCESS = 'OK';
    const REAL_MODE = 'real';
    const FREE_MODE = 'free';
    const FUN_GAME = ['trial', 'fun', 'demo'];

    const NO_REFUND_TRANSACTION = 0;
    const LOSS_FLAG = 0;

    const CALLBACK_BONUS_RELEASE = 'bonus_release';     // unique id is bonus_id
    const CALLBACK_BONUS_WIN = 'bonus_win';             // unique id is transaction_id

    const FREE_ROUND = 'free_round';

    const URI_MAP                   = array(
        self::API_queryForwardGame  => 'cwstartgamev2.do',
    );

    public function __construct() {
        parent::__construct();
        $this->url              = $this->getSystemInfo('url');
        $this->bank_id          = $this->getSystemInfo('bank_id','3046');
        $this->pass_key         = $this->getSystemInfo('passkey' ,'12345');
        $this->currency         = $this->getSystemInfo('currency', 'CNY');
        $this->default_language = $this->getSystemInfo('language', 'en');
        $this->token_timeout    = $this->getSystemInfo('token_timeout');

        $this->demo_url         = $this->getSystemInfo('demo_url', 'https://ole777-gp3.discreetgaming.com/cwguestlogin.do');
        $this->default_game_mode= self::REAL_MODE;

        $this->forward_sites = $this->getSystemInfo('forward_sites');
        $this->token_prefix = $this->getSystemInfo('token_prefix', '');
    }

    const MD5_FIELDS_FOR_MERGE=['user_id', 'transaction_id', 'is_round_finished', 'bet_amount','result_amount'];
    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE=['bet_amount', 'result_amount'];

    public function getPlatformCode() {
        return BETSOFT_API;
    }

    public function passKey() {
        return $this->pass_key;
    }

    public function getCurrency() {
        return $this->currency;
    }

    public function conversionRate() {
        return $this->getSystemInfo('conversion_rate', 1);
    }

    public function lossFlag() {
        return self::LOSS_FLAG;
    }

    public function forwardSites() {
        return $this->forward_sites;
    }

    public function tokenPrefix() {
        return $this->token_prefix;
    }

    public function processResultBoolean($responseResultId, $resultJson, $playerName) {

        # to recheck error code
        $success = ( ! empty($resultJson)) && $resultJson['error_code'] == self::SUCCESS;

        if ( ! $success) {
            $this->setResponseResultToError($responseResultId);
            $this->CI->utils->debug_log('AB got error', $responseResultId, 'playerName', $playerName, 'result', $resultJson);
        }

        return $success;
    }

    public function generateUrl($apiName, $params) {
        $apiUri = self::URI_MAP[$apiName];
        $params_string = http_build_query($params);
        $url = $this->url . $apiUri . "?" . $params_string;
        return $url;
    }

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
        $createPlayer = parent::createPlayer($playerName, $playerId, $password, $email, $extra);
        $success = false;
        $message = "Unable to create account for betsoft game";
        if($createPlayer){
            $success = true;
            $message = "Successfull create account for betsoft game";
        }
        return array("success" => $success, "message" => $message);
    }

    public function queryPlayerBalance($playerName) {
        $this->CI->load->model(array('player_model'));

        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
        $balance = $this->CI->player_model->getPlayerSubWalletBalance($playerId, $this->getPlatformCode());

        $result = array(
            'success' => true,
            'balance' => $balance
        );

        return $result;
    }

    public function depositToGame($playerName, $amount, $transfer_secure_id=null) {
        $playerId           = $this->getPlayerIdFromUsername($playerName);
        $gameUsername       = $this->getGameUsernameByPlayerUsername($playerName);
        $playerBalance      = $this->queryPlayerBalance($playerName);

        $afterBalance       = @$playerBalance['balance'];
        $responseResultId   = null;

        $this->CI->utils->debug_log('BETSOFT deposit playerId:', $playerId, " playerName", $playerName, " gameUsername : ", $gameUsername, " afterBalance : ", $afterBalance);

        $this->insertTransactionToGameLogs($playerId, $gameUsername, $afterBalance, $amount, $responseResultId, $this->transTypeMainWalletToSubWallet());

        return array('success' => true);
    }

    public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null) {
        $playerId           = $this->getPlayerIdFromUsername($playerName);
        $gameUsername       = $this->getGameUsernameByPlayerUsername($playerName);
        $playerBalance      = $this->queryPlayerBalance($playerName);
        $afterBalance       = @$playerBalance['balance'];
        $responseResultId   = null;

        $this->CI->utils->debug_log('BETSOFT withdraw playerId:', $playerId, " playerName", $playerName, " gameUsername : ", $gameUsername, " afterBalance : ", $afterBalance);

        $this->insertTransactionToGameLogs($playerId, $gameUsername, $afterBalance, $amount, $responseResultId, $this->transTypeSubWalletToMainWallet());

        return array('success' => true);
    }

    public function isPlayerExist($userName) {
        $playerName = $this->getGameUsernameByPlayerUsername($userName);
        $playerId = $this->getPlayerIdInGameProviderAuth($playerName);
        $result['exists'] = true;
        $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
        return array(true, $result);
    }

    public function queryForwardGame($playerName, $extra = null) {
        $playerId = $this->getPlayerIdFromUsername($playerName);
        $token = $token = $this->getPlayerToken($playerId);
        $language = isset($extra['lang']) ? : $this->default_language;

        # token_prefix (should same in forward_site key
        if(!empty($this->token_prefix)){
            $token = $this->token_prefix.$token;
        }

        $params = array_filter(array(
            'bankId'        => $this->bank_id,
            'mode'          => $extra['mode'],
            'gameId'        => $extra['game_id'],
            'lang'          => $language,
        ));

        if (in_array($extra['mode'], self::FUN_GAME)) {
            unset($params['mode']);
            $params_string = http_build_query($params);
            $url = $this->demo_url . "?" . $params_string;
        } else {
            $params['token'] = $token;
            $apiUri = self::URI_MAP[self::API_queryForwardGame];
            $params_string = http_build_query($params);
            $url = $this->url . $apiUri . "?" . $params_string;
        }

        return ['success'=> true, 'url' => $url];
    }

    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time){
        $sqlTime='betsoft.last_sync_time >= ? and betsoft.last_sync_time <= ?';
        //if($use_bet_time){
        //   $sqlTime='betsoft.last_sync_time >= ? and betsoft.last_sync_time <= ?';  # only last sync is available
        //}
        $sqlRefund=' and betsoft.is_refunded = ? ';

        // game logs has more than 1 record. need to get one then process bet and result amount in merge
        // checking multiple record by round_id
        $sqlBetTransID=' and betsoft.bet_transid!=0 and bet_transid!=""';

        $sql = <<<EOD
SELECT betsoft.id as sync_index,
betsoft.id,
betsoft.user_id as username,
betsoft.bet_amount,
betsoft.bet_amount as real_bet_amount,
betsoft.result_amount,
betsoft.external_uniqueid,
betsoft.last_sync_time,
betsoft.last_sync_time as bet_time,
betsoft.md5_sum,
betsoft.game_id as game_code,
betsoft.game_id as game,
betsoft.is_round_finished,

betsoft.response_result_id,
betsoft.round_id,

game_provider_auth.player_id,

gd.id as game_description_id,
gd.game_name as game_description_name,
gd.game_type_id

FROM betsoft_game_logs as betsoft
LEFT JOIN game_description as gd ON betsoft.game_id = gd.game_code AND gd.game_platform_id = ?
LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
JOIN game_provider_auth ON betsoft.user_id = game_provider_auth.login_name and game_provider_auth.game_provider_id=?

WHERE
{$sqlTime}
{$sqlRefund}
{$sqlBetTransID}
EOD;

        $params=[$this->getPlatformCode(), $this->getPlatformCode(), $dateFrom,$dateTo, self::NO_REFUND_TRANSACTION];

        return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    }


    public function makeParamsForInsertOrUpdateGameLogsRow(array $row){

        // echo "<pre>";
        // print_r($row);exit();

        $extra_info=[];

        if(empty($row['md5_sum'])){
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
        }

        $logs_info = [
            'game_info'=>['game_type_id'=>$row['game_type_id'], 'game_description_id'=>$row['game_description_id'],
                'game_code'=>$row['game_code'], 'game_type'=>null, 'game'=>$row['game']],
            'player_info'=>['player_id'=>$row['player_id'], 'player_username'=>$row['username']],
            'amount_info'=>['bet_amount'=>$row['bet_amount'], 'result_amount'=>$row['result_amount'],
                'bet_for_cashback'=>$row['bet_amount'], 'real_betting_amount'=>$row['real_bet_amount'],
                'win_amount'=>null, 'loss_amount'=>null, 'after_balance'=>null],
            'date_info'=>['start_at'=>$row['bet_time'], 'end_at'=>$row['bet_time'], 'bet_at'=>$row['bet_time'],
                'updated_at'=>$row['last_sync_time']],
            'flag'=>Game_logs::FLAG_GAME,
            'additional_info'=>['has_both_side'=>0, 'external_uniqueid'=>$row['external_uniqueid'], 'round_number'=>$row['external_uniqueid'],
                'md5_sum'=>$row['md5_sum'], 'response_result_id'=>$row['response_result_id'], 'sync_index'=>$row['sync_index'],
                'bet_type'=>null ],
            'bet_details'=>null,
            'status'=>Game_logs::STATUS_SETTLED,
            'extra'=>$extra_info,
            //from exists game logs
            'game_logs_id'=>isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];

        return $logs_info;
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
            $gameDescId=$this->CI->game_description_model->processUnknownGame($this->getPlatformCode(),
                $unknownGame->game_type_id, $row['game'], $row['game_code']);
        }

        return [$game_description_id, $game_type_id];
    }

    public function preprocessOriginalRowForGameLogs(array &$row){
        $game_description_id = $row['game_description_id'];
        $game_type_id = $row['game_type_id'];

        if (empty($game_description_id)) {
            list($game_description_id, $game_type_id) = $this->getGameDescriptionInfo($row, $this->getUnknownGame());
        }
        $row['game_description_id']=$game_description_id;
        $row['game_type_id']=$game_type_id;

        if (isset($row['frb'])) {
            // set bet to 0 for free round bonus
            $row['bet_amount'] = 0;
            $row['real_bet_amount'] = 0;
            $row['response_result_id'] = null;
            $row['result_amount'] = $this->gameAmountToDB($row['result_amount']);
        } else {
            list($bet_amount, $result_amount) = $this->processGameResultByRoundId($row['round_id']);
            $row['bet_amount'] = $this->gameAmountToDB($bet_amount);
            $row['real_bet_amount']=$this->gameAmountToDB($bet_amount);
            $row['result_amount'] = $this->gameAmountToDB($result_amount);
        }
        $row['status'] = Game_logs::STATUS_SETTLED;

        return $row;
    }

    public function processGameResultByRoundId($round_id) {
        $game_record = $this->gameResultByRoundId($round_id);
        $bet_amount = $result_amount = 0;
        if (!empty($game_record)) {
            foreach ( $game_record as $record) {
                $bet_trans =  $record['bet_transid'];
                $win_trans =  $record['win_transid'];   # win_amount|trans_id ( if 0 loss

                if ( !empty($bet_trans) || $bet_trans!= 0) {
                    $bet_and_trans_id = explode("|",$bet_trans);
                    $bet_amount = $bet_and_trans_id['0'];
                } else {
                    $win_and_trans_id = explode("|",$win_trans);

                    // if value of win is 0 = means loss. if greater than it's win
                    $win_amount = $win_and_trans_id['0'];
                    if ($win_amount == self::LOSS_FLAG) {
                        $result_amount = -$bet_amount;
                    } else {
                        $result_amount = $win_amount;
                    }

                }
            }
        }

        return array($bet_amount, $result_amount);
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

    public function queryFreeRoundLogs($dateFrom, $dateTo){
        $sqlTime='frb.last_sync_time >= ? and frb.last_sync_time <= ?';

        // add virtual column for bet amount ( no bet in free round bonus )
        // "free" as frb ==> add static

        $bonus_win = self::CALLBACK_BONUS_WIN;
        $bonus_release = self::CALLBACK_BONUS_RELEASE;

        $sql = <<<EOD
SELECT frb.id as sync_index,
frb.id,
frb.bonus_id,
frb.amount as result_amount,
frb.game_username as username,
frb.bonus_id,
frb.last_sync_time,
frb.last_sync_time as bet_time,
frb.md5_sum,
frb.game_id as game_code,
frb.game_id as game,
frb.is_bonus_release,
frb.transaction_id,
frb.is_trans_success,
frb.player_id,
frb.callback,
(case
   when (frb.callback = "{$bonus_win}") then frb.transaction_id
   when (frb.callback = "{$bonus_release}") then frb.bonus_id
end) as external_uniqueid,
"0" as bet_amount,
"free_round" as frb,
game_provider_auth.player_id,

gd.id as game_description_id,
gd.game_name as game_description_name,
gd.game_type_id

FROM betsoft_free_round_bonus as frb
LEFT JOIN game_description as gd ON frb.game_id = gd.game_code AND gd.game_platform_id = ?
LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
JOIN game_provider_auth ON frb.game_username = game_provider_auth.login_name and game_provider_auth.game_provider_id=?

WHERE
{$sqlTime}
EOD;

        $params=[$this->getPlatformCode(), $this->getPlatformCode(), $dateFrom,$dateTo];
        $result =  $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $result;
    }

    public function syncMergeFreeBonusToGameLogs($token) {
        $enabled_game_logs_unsettle=false;
        return $this->commonSyncMergeToGameLogs($token,
            $this,
            [$this, 'queryFreeRoundLogs'],
            [$this, 'makeParamsForInsertOrUpdateGameLogsRow'],
            [$this, 'preprocessOriginalRowForGameLogs'],
            $enabled_game_logs_unsettle);
    }

    public function blockPlayer($playerName) {
        $playerName = $this->getGameUsernameByPlayerUsername($playerName);
        $result = $this->blockUsernameInDB($playerName);
        return array("success" => $result);
    }

    public function unblockPlayer($playerName) {
        $playerName = $this->getGameUsernameByPlayerUsername($playerName);
        $result = $this->unblockUsernameInDB($playerName);
        return array("success" => $result);
    }

    public function gameResultByRoundId($round_id) {
        $this->CI->db->select('*');
        $this->CI->db->where('round_id', $round_id);
        $this->CI->db->from('betsoft_game_logs');
        $query = $this->CI->db->get();
        return $query->result_array();
    }

    public function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null) {
        return array(false, null);
    }

    public function updatePlayerInfo($playerName, $infos) {
        return $this->returnUnimplemented();
    }

    public function queryPlayerDailyBalance($playerName, $playerId, $dateFrom = null, $dateTo = null) {
        return $this->returnUnimplemented();
    }

    public function queryGameRecords($dateFrom, $dateTo, $playerName = null) {
        return $this->returnUnimplemented();
    }

    public function checkLoginToken($playerName, $token) {
        return $this->returnUnimplemented();
    }

    public function totalBettingAmount($playerName, $dateFrom, $dateTo) {
        return $this->returnUnimplemented();
    }

    public function queryTransaction($transactionId, $extra) {
        return $this->returnUnimplemented();
    }

    public function login($playerName, $password = null) {
        return $this->returnUnimplemented();
    }

    public function checkLoginStatus($playerName) {
        return $this->returnUnimplemented();
    }

    public function queryPlayerInfo($playerName) {
        return $this->returnUnimplemented();
    }

    public function syncOriginalGameLogs($token) {
        return $this->returnUnimplemented();
    }

    public function changePassword($playerName, $oldPassword, $newPassword) {
        return $this->returnUnimplemented();
    }

    public function logout($playerName, $password = null) {
        return $this->returnUnimplemented();
    }

    public function processResultForLogout($params) {
        return $this->returnUnimplemented();
    }
}