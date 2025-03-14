<?php
/**
 * Sexy Baccarat - SV388 Seamless Game Integration
 * OGP-29112
 */
require_once dirname(__FILE__) . '/abstract_game_api_common_sexy_baccarat.php';

class Game_api_sv388_seamless extends Abstract_game_api_common_sexy_baccarat {

    const GAME_TYPE = 'LIVE';
    const GAME_PLATFORM = 'SV388';
    const ORIGINAL_TRANSACTION_TABLE = 'sv388_seamless_wallet_transactions';
  
    public function getPlatformCode(){
        return SV388_SEAMLESS_GAME_API;
    }

    public function __construct(){
        parent::__construct();
        $this->original_transactions_table = self::ORIGINAL_TRANSACTION_TABLE;    
    }

    public function getTransactionsTable(){
        return $this->original_transactions_table;
    }

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {

        $extra = [
            'prefix' => $this->prefix_for_username,

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
                'gameUsername' => $gameUsername,
                'playerName' => $playerName,
            ];

        $betLimit = json_encode(array(
        	self::GAME_PLATFORM => [
        		self::GAME_TYPE => [
                    "maxbet" => $this->sv388_maxbet,
                    "minbet" => $this->sv388_minbet,
                    "mindraw" => $this->sv388_mindraw,
                    "matchlimit" => $this->sv388_matchlimit,
                    "maxdraw" => $this->sv388_maxdraw
        		]
        	]
        ));

        $params = [
            'cert' => $this->cert,
            'agentId' => $this->agentId,
            'userId' => $gameUsername,
            'currency' => $this->getCurrency(),
            'betLimit' => $betLimit,
            'language' => $this->language
        ];

        $this->CI->utils->debug_log('<-------------------------PARAMSTAL------------------------->', $params);

        return $this->callApi(self::API_createPlayer, $params, $context); 
    }

    public function processResultForCreatePlayer($params) {
    	$playerName = $this->getVariableFromContext($params, 'playerName');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);
        // print_r($resultArr);exit;
        $result = array();
        $result['response_result_id'] = $responseResultId;
        if($success){
            $result['status'] = $resultArr['status'];
            $result['msg'] = $resultArr['desc'];
        }
        return array($success, $result);
    }

    public function queryPlayerBalance($playerName) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
        $balance = $this->CI->player_model->getPlayerSubWalletBalance($playerId, $this->getPlatformCode());

        $result = array(
            'success' => true,
            'balance' => $balance
        );

        return $result;
    }


    /** queryOriginalGameLogs
     * @param  string $dateFrom
     * @param  string $dateTo
     * @param  bool   $use_bet_time
     * @return array
     */
    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time){
        //only one time field
        $sqlTime='sv.updated_at >= ? AND sv.updated_at <= ?';
        if($use_bet_time){
            $sqlTime='sv.betTime >= ? AND sv.betTime <= ?';
        }

        $sql = <<<EOD
SELECT
sv.id as sync_index,
sv.response_result_id,
sv.external_uniqueid,
sv.md5_sum,

sv.userId as player_username,
sv.action,
sv.platformTxId,
sv.platform,
sv.gameType as game_type,
sv.gameCode as game_code,
sv.gameName as game_name,
sv.betType as bet_type,
sv.betAmount as bet_amount,
sv.betAmount as real_betting_amount,
sv.winAmount as win_amount,
sv.betTime as bet_at,
sv.betTime as start_at,
sv.betTime as end_at,
sv.roundId as round_number,
sv.gameInfo,
sv.before_balance,
sv.after_balance,
sv.response_result_id,
sv.created_at,
sv.updated_at,
sv.tip_amount,
sv.bet_status,

game_provider_auth.player_id,
gd.id as game_description_id,
gd.game_type_id

FROM {$this->original_transactions_table} as sv
LEFT JOIN game_description as gd ON sv.gameCode = gd.external_game_id AND gd.game_platform_id = ?
LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
JOIN game_provider_auth ON sv.userId = game_provider_auth.login_name
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

        $this->debug_log('merge sql', $sql, $params);

        return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    }

    /** queryOriginalGameLogsFromTrans
     * @param  string $dateFrom
     * @param  string $dateTo
     * @param  bool   $use_bet_time
     * @return array
     */
    public function queryOriginalGameLogsFromTrans($dateFrom, $dateTo, $use_bet_time){
        //only one time field
        $this->action_for_merge = $this->getSystemInfo('action_for_merge', ['bet', 'settle', 'tip', 'give','resettle']);
        $sqlTime='sv.updated_at >= ? AND sv.updated_at <= ?';

        if(!empty($this->action_for_merge) && is_array($this->action_for_merge)){
            $sqlTime.= ' and action in ("' . implode('","',$this->action_for_merge) .'")';
        }
        
        if($use_bet_time){
            //$sqlTime='sv.createdAt >= ? AND sv.createdAt <= ?';
        }

        $sql = <<<EOD
SELECT
sv.id as sync_index,
sv.response_result_id,
sv.external_uniqueid,
sv.betTime,
sv.betTime as bet_at,
sv.betTime as start_at,
sv.betTime as end_at,
sv.betType as bet_type,
sv.created_at,
sv.updated_at,
game_provider_auth.player_id,
sv.platformTxId as platformTxId,
sv.roundId as round_number,
sv.md5_sum,
sv.userId as player_username,
sv.action trans_type,
sv.action_status action_status,
sv.platformTxId,
sv.platform,
sv.after_balance,
sv.before_balance,
sv.action,
sv.action_status,
IF(sv.action='bet',sv.betAmount,0) bet_amount,
IF(sv.action='bet',sv.betAmount,0) real_betting_amount,
IF(sv.action='adjustBet',sv.betAmount,0) adjust_bet_amount,
IF(sv.action='settle' OR sv.action='give' OR sv.action='resettle',sv.winAmount,0) win_amount,
IF(sv.action='tip' AND sv.action_status<>?,sv.tip_amount,0) tip_amount,
IF(sv.action='give',sv.winAmount,0) give_amount,
sv.created_at,
sv.updated_at,
sv.gameType as game_type,
sv.gameCode as game_code,
sv.gameName as game_name,
gd.id as game_description_id,
gd.game_type_id,

sv.gameInfo

FROM {$this->original_transactions_table} as sv
LEFT JOIN game_description as gd ON sv.gameCode = gd.external_game_id AND gd.game_platform_id = ?
LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
JOIN game_provider_auth ON sv.userId = game_provider_auth.login_name
AND game_provider_auth.game_provider_id=?
WHERE
{$sqlTime}
EOD;

        $params=[
            Game_logs::STATUS_CANCELLED,
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo
        ];

        $this->debug_log('merge sql', $sql, $params);

        return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    }
}