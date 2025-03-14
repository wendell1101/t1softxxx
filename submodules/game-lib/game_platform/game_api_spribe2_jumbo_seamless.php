<?php
// require_once dirname(__FILE__) . '/game_api_jumbo_seamless.php';

class Game_api_spribe2_jumbo_seamless extends Game_api_jumbo_seamless {
	
	public function getPlatformCode(){
        return SPRIBE2_JUMBO_SEAMLESS_GAME_API;
    }

    public function getAccessToken($playerName = null, $extra = null) {

        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $balance = $this->queryPlayerBalance($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForGetAccessToken',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername,
        );


        $api_action = (isset($extra['game_mode']) && $extra['game_mode'] == 'real') ? self::URI_MAP[self::API_checkLoginToken] : self::URI_MAP[self::API_queryDemoGame];

        // check if game type is empty or isset

        $attributes = $this->CI->game_description_model->queryAttributeByGameCode($this->getPlatformCode(), $extra['game_code']);
        $attributes = json_decode($attributes, true);
        $gType = isset($attributes['gType']) ? $attributes['gType'] : null;
        
        $jumb_params = array(
            'action'    => $api_action,    //  
            'ts'        => $this->jumb_now() ,
            'parent'    => $this->agent,
            'uid'       => $gameUsername ,
            'balance'   => $balance['balance'],
            'lang'      => $this->getLauncherLanguage($extra['language']) ,
            'gType'     => $gType, #'0' , # 0 slot , 7 Fishing machine
            'mType'     => $extra['game_code'],
            'windowMode'=> 2 # 1 - Include game hall, 2 - does not contain the game hall, hide the close button in the game gType and mType are required fields.
        );

        $encrypted = $this->encrypt(json_encode($jumb_params), $this->key, $this->iv);

        $params = array(
            'dc'    => $this->dc ,
            'x'     => $encrypted
        );

        $this->utils->debug_log("JUMBO SEAMLESS jumb_params ============================>", $jumb_params);
        $this->utils->debug_log("JUMBo SEAMLESS ecrypted params ============================>", $params);
        
        if($extra['game_mode'] != 'real'){
            return $this->callApi(self::API_queryDemoGame, $params, $context);
        } else {
            return $this->callApi(self::API_queryForwardGame, $params, $context);
        }

    }

        /**
     * queryOriginalGameLogs
     * @param  string $dateFrom
     * @param  string $dateTo
     * @param  bool   $use_bet_time
     * @return array
     */
    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time = false){
        $sqlTime="spribe.updated_at >= ? AND spribe.updated_at <= ? AND spribe.game_platform_id = ? AND spribe.transaction_type != ?";

        $sql = <<<EOD
SELECT
spribe.id as sync_index,
spribe.response_result_id,
spribe.external_unique_id as external_uniqueid,
spribe.md5_sum,

spribe.player_id,
spribe.game_platform_id,
spribe.valid_bet as bet_amount,
spribe.bet_amount as real_betting_amount,
spribe.result_amount,
spribe.amount,
spribe.transaction_type,
spribe.game_id as game_code,
spribe.game_id as game,
spribe.game_id as game_name,
spribe.round_id,
spribe.response_result_id,
spribe.extra_info,
spribe.start_at,
spribe.start_at as bet_at,
spribe.end_at,
spribe.before_balance,
spribe.after_balance,
spribe.transaction_id,
spribe.game_status as status,
spribe.game_status,
MD5(CONCAT(spribe.valid_bet, spribe.result_amount, spribe.game_status)) as md5_sum,
spribe.updated_at,

gd.id as game_description_id,
gd.game_type_id

FROM {$this->original_transaction_table_name} as spribe
LEFT JOIN game_description as gd ON spribe.game_id = gd.external_game_id AND gd.game_platform_id = ?
WHERE
{$sqlTime}
EOD;

        
        $params=[
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo,
            $this->getPlatformCode(),
            "debit"
        ];

        $this->CI->utils->debug_log('merge sql', $sql, $params);

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);

        return $result;
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

    public function queryTransactionByDateTime($startDate, $endDate){

        $sql = <<<EOD
SELECT
t.player_id as player_id,
t.created_at transaction_date,
if(t.transaction_type = "debit", -t.amount, t.amount) as amount,
t.after_balance as after_balance,
t.before_balance as before_balance,
t.round_id as round_no,
t.external_unique_id as external_uniqueid,
t.transaction_type trans_type,
t.extra_info extra_info
FROM {$this->original_transaction_table_name} as t
WHERE t.game_platform_id = ? and `t`.`updated_at` >= ? AND `t`.`updated_at` <= ?
ORDER BY t.updated_at asc;

EOD;

        $params=[$this->getPlatformCode(),$startDate, $endDate];

                $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
                return $result;
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

    const REFUND_CODE_SUCCESS = 0000;
    public function triggerInternalRefundRound($params = '{"test":true}'){
        $this->post_json = true;
        $params = json_decode($params, true);
        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultFortriggerInternalRefundRound',
        ];
        
        $apiName = self::API_triggerInternalRefundRound;
        return $this->callApi($apiName, $params, $context);
    }

    public function processResultFortriggerInternalRefundRound($params){
        $resultArr = $this->getResultJsonFromParams($params);
        $success = isset($resultArr['status']) &&  $resultArr['status'] == self::REFUND_CODE_SUCCESS ? true : false; 
        $result = ["message" => json_encode($resultArr)];
        return [$success, $result];
    }
}