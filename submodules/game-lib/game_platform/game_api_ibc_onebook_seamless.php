<?php
require_once dirname(__FILE__) . '/game_api_ibc_onebook.php';

class Game_api_ibc_onebook_seamless extends Game_api_ibc_onebook {

    const IBC_ONEBOOK_GAMELOGS_TABLE = "ibc_onebook_game_logs";
    const ORIGINAL_TRANSACTION_TABLE = 'common_seamless_wallet_transactions';

    public function getPlatformCode(){
        return IBC_ONEBOOK_SEAMLESS_API;
    }

    public function isSeamLessGame(){
        return true;
    }

    public function __construct(){
        parent::__construct();
        $this->currency_type = $this->getSystemInfo('currency_type');
        $this->original_gamelogs_table = self::IBC_ONEBOOK_GAMELOGS_TABLE;
        $this->wallet_type = $this->getSystemInfo('wallet_type');
        $this->language = $this->getSystemInfo('language');
        $this->key = $this->getSystemInfo('key');

        $this->original_transactions_table = self::ORIGINAL_TRANSACTION_TABLE;


        $this->allow_launch_demo_without_authentication=$this->getSystemInfo('allow_launch_demo_without_authentication', true);
        $this->is_get_login_url =$this->getSystemInfo('is_get_login_url', false);
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

    public function depositToGame($userName, $amount, $transfer_secure_id=null){
        $external_transaction_id = $transfer_secure_id;
        return array(
            'success' => true,
            'external_transaction_id' => $external_transaction_id,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_APPROVED,
            'response_result_id ' => NULL,
            'didnot_insert_game_logs'=> true,
        );
    }

    public function withdrawFromGame($userName, $amount, $transfer_secure_id=null){
        $external_transaction_id = $transfer_secure_id;
        return array(
            'success' => true,
            'external_transaction_id' => $external_transaction_id,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_APPROVED,
            'response_result_id ' => NULL,
            'didnot_insert_game_logs'=> true,
        );
    }

    public function queryForwardGame($playerName, $extraParams = null, $gameToken = null)
    {
        return $this->buildGameLauncher($playerName,$gameToken,$extraParams);
    }

    # generates newGameUrl for login&gamelaunch
    public function getSabaUrl($playerName, $extraParams){
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $is_mobile = $extraParams['is_mobile'];

        if($is_mobile)
        {
            $platform = 2;
        }
        else
        {
            $platform = 1;
        }

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForGetSabaUrl',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername,
        );

        $params = array(
            "vendor_id" => $this->vendor_id,
            // "vendor_member_id" => $gameUsername,
            "platform" => $platform,
        );

        if (!empty($gameUsername)) {
            $params["vendor_member_id"] = $gameUsername;
        }

        $apiName = $this->is_get_login_url ? self::API_getLoginUrl : self::API_getSabaUrl;
        $this->CI->utils->debug_log('GetSabaUrl params: ' . http_build_query($params));
        return $this->callApi($apiName, $params, $context);
    }

    public function processResultForGetSabaUrl($params){
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJsonArr = $this->getResultJsonFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$statusCode = $this->getStatusCodeFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultJsonArr, $statusCode);
        $result = array();
        $result['gameUrl'] = NULL;
        if($success){
            $result['gameUrl'] = isset($resultJsonArr['Data']) ? $resultJsonArr['Data'] : NULL;
        }
        return array($success, $result);
    }

    protected function buildGameLauncher($playerName,$gameToken,$extraParams)
    {
        #GET LANG FROM PLAYER DETAILS
        $playerId = $this->getPlayerIdFromUsername($playerName);
        // $language = $this->getLauncherLanguage($this->getPlayerDetails($playerId)->language);
        $language = $this->getLauncherLanguage($this->language);

        #IDENTIFY IF LANGUAGE IS INVOKED IN GAME URL, ELSE USE PLAYER LANG
        if(!empty($this->language)) {
            $language = $this->language;
        } else {
            if(isset($extraParams['language'])){
                $language = $this->getLauncherLanguage($extraParams['language']);
            }
        }

        #IDENTIFY IF SKINCOLOR IS INVOKED IN GAME URL, ELSE USE DEFAULT SKIN
        if(isset($extraParams['extra']['skincolor'])){
            $skincolorVal = $extraParams['extra']['skincolor'];
            if(in_array($skincolorVal,self::SKINCOLOR_TYPES)){
                $skincolor = $skincolorVal;
            }else{
                $skincolor = self::SKINCOLOR_TYPES['blue1'];
            }
        }else{
            $skincolor = self::SKINCOLOR_TYPES['blue1'];
        }

        #IDENTIFY IF ODDSTYPE IS INVOKED IN GAME URL, ELSE USE EXTRA INFO DATA
        if(isset($extraParams['extra']['oddstype'])){
            $oddstypeVal = $extraParams['extra']['oddstype'];
            if(in_array($oddstypeVal,self::ODDSTYPE)){
                $oddstype = $oddstypeVal;
            }else{
                $oddstype = $this->odds_type;
            }
        }else{
            $oddstype = $this->odds_type;
        }

        $urlParams = [
                        "token" => $gameToken,
                        "lang" => $language,
                        "Otype" => $oddstype,
                        "skincolor" => $skincolor,
                     ];

        if(isset($extraParams['game_type']) && $extraParams['game_type']==self::ESPORTS_GAME){
            if(isset($extraParams['is_mobile']) && $extraParams['is_mobile']) {
                $urlParams['types'] = $extraParams['game_type'];
            }
            else {
                $urlParams['game'] = $extraParams['game_type'];
            }
        }

        if($this->home_url){
            $urlParams['homeUrl'] = $this->home_url;
        }

        if($this->extend_session_url){
            $urlParams['extendSessionUrl'] = $this->extend_session_url;
        }

        #IDENTIFY IF LAUNCH IN MOBILE MODE
        if(isset($extraParams['is_mobile'])){
            $isMobile = $extraParams['is_mobile'] ? true : false;
            if($isMobile){
                $this->gamelaunch_url = $this->getSystemInfo('mobile_gamelaunch_url');
                if($this->mobile_skin){
                    $urlParams['skin'] = $this->mobile_skin;
                }
            }
        }
        // return $this->gamelaunch_url.'?'.http_build_query($urlParams);

        // uses URL from getSabaUrl for game launch
        $sabaUrl = $this->getSabaUrl($playerName, $extraParams);
        $newGameUrl = isset($sabaUrl['gameUrl']) ? $sabaUrl['gameUrl'] : NULL;
        $url = null;
        $success = false;
        if($newGameUrl != null)
        {
            if (!empty($playerName)) {
                $others = array(
                    "lang" => $language,
                    "webskintype" => $this->web_skin_type,
                );

                $url = $newGameUrl ."&". http_build_query($others);
    
                if($this->mobile_skin && isset($is_mobile)){
                    $url .= "&skin=".$this->mobile_skin;
                }
            } else {
                $url = $newGameUrl;
            }

            $success = true;
        }

        $this->CI->utils->debug_log('oneworks queryForwardGame login URL--------->: ', $url);

        return ['success' => $success, 'url' => $url];
    }

    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time)
    {
        $sqlTime='onebook.last_sync_time >= ? and onebook.last_sync_time <= ?';
        if($use_bet_time){
            $sqlTime='`onebook`.`transaction_time` >= ?
          AND `onebook`.`transaction_time` <= ?';
        }

        $sql = <<<EOD
            SELECT
                onebook.id as sync_index,
                onebook.response_result_id,
                onebook.match_id,
                onebook.vendor_member_id as username,
                onebook.stake as bet_amount,
                onebook.stake as valid_bet,
                onebook.winlost_amount as result_amount,
                onebook.ref_code,
                onebook.odds,
                onebook.odds_type,
                onebook.hdp,
                onebook.league_id,
                onebook.home_id,
                onebook.away_id,
                onebook.bet_team,
                onebook.parlaydata,
                onebook.islive,
                onebook.trans_id,
                onebook.sport_type,
                onebook.parlay_ref_no,
                onebook.parlay_type,
                onebook.combo_type,
                onebook.cashoutdata,
                onebook.home_hdp,
                onebook.away_hdp,
                onebook.original_stake,
                onebook.betchoice as bet_choice,
                onebook.after_amount as after_balance,
                onebook.last_sync_time as updated_at,
                onebook.bet_type as bet_type_oneworks,
                onebook.ticket_status as status_in_db,
                onebook.transaction_time as bet_at,
                onebook.settlement_time as end_at,
                onebook.sport_type as game_code,
                onebook.sport_type as game,
                onebook.external_uniqueid,
                onebook.md5_sum,
                onebook.winlost_datetime,
                onebook.version_key,
                onebook.percentage,
                game_provider_auth.player_id,
                gd.id as game_description_id,
                gd.game_name as game_description_name,
                gd.game_type_id


            FROM $this->original_gamelogs_table as onebook
            LEFT JOIN game_description as gd ON onebook.sport_type = gd.external_game_id AND gd.game_platform_id = ?
            LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
            JOIN game_provider_auth ON onebook.vendor_member_id = game_provider_auth.login_name AND game_provider_auth.game_provider_id=?
            WHERE
            {$sqlTime}
EOD;

        $params=[
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            // $this->getPlatformCode(),
            $dateFrom,
            $dateTo
        ];

        $rows = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $rows;
    }

    public function syncOriginalGameLogs($token = false) {
        $startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
        $startDateTime = new DateTime($startDate->format('Y-m-d H:i:s'));
        $startDateTime->modify($this->getDatetimeAdjust());
        $endDateTime = new DateTime($endDate->format('Y-m-d H:i:s'));
        $queryDateTimeStart = $startDateTime->format("Y-m-d H:i:s");
        $queryDateTimeEnd = $endDateTime->format('Y-m-d H:i:s');

        $refIds = $this->queryRefIdsForUpdate($queryDateTimeStart, $queryDateTimeEnd);
        if(!empty($refIds)){
            $refIds = array_column($refIds, 'transaction_id');
            foreach ($refIds as $key => $refId) {
                $refIdDetails = $this->queryRefIdDetails($refId);
                $bet_info = min($refIdDetails);
                $uniqueid = $bet_info['id'];
                $settle_info = max($refIdDetails);
                if($bet_info['transaction_type'] == "placebet" && count($refIdDetails) == 1){
                    $data['result_amount'] = -$bet_info['bet_amount'];
                }
                if($bet_info['transaction_type'] != "placebet"){
                    $placebetkey = array_search('placebet', array_column($refIdDetails, 'transaction_type'));
                    if($placebetkey !== false){
                        if(isset($refIdDetails[$placebetkey])){
                            $bet_info = $refIdDetails[$placebetkey];
                            $data['bet_amount'] = $bet_info['bet_amount'];
                            $data['result_amount'] = $bet_info['result_amount'];
                            $uniqueid = $bet_info['id'];
                        }
                    }
                }

                $data['bet_amount'] = $bet_info['bet_amount'];
                $confirmbetkey = array_search('confirmbet', array_column($refIdDetails, 'transaction_type'));
                if($confirmbetkey !== false){
                    if(isset($refIdDetails[$confirmbetkey])){
                        $confirm_info = $refIdDetails[$confirmbetkey];
                        $data['bet_amount'] = $confirm_info['bet_amount'];
                        $data['result_amount'] = $confirm_info['result_amount'];
                    }
                }

                if($settle_info['transaction_type'] == "resettle"){
                    if($settle_info['result_amount'] == 0){
                        $settleKey = array_search('settle', array_column($refIdDetails, 'transaction_type'));
                        if($settleKey !== false){
                            if(isset($refIdDetails[$settleKey])){
                                $settle_info = $refIdDetails[$settleKey];
                            }
                        }
                    }
                }
                $data['flag_of_updated_result'] = $settle_info['status'];
                $data['id'] = $uniqueid;
                $data['updated_at'] = $this->CI->utils->getNowForMysql();
                if(!isset($data['result_amount'])){
                    $data['result_amount'] =  $settle_info['result_amount'] - $data['bet_amount'];
                } else {
                    if($settle_info['transaction_type'] != "placebet" && count($refIdDetails) > 1){
                        $data['result_amount'] = $settle_info['result_amount'] - $data['bet_amount'];
                    }
                }
                if($this->is_get_login_url){
                    $extra_info = json_decode($settle_info['extra_info'], true);
                    $txns = isset($extra_info['message']['txns']) ? $extra_info['message']['txns'] : [];
                    if(!empty($txns)){
                        $tx_key = array_search($refId, array_column($txns, 'refId'));
                        if(isset($txns[$tx_key])){
                            $tx_row = $txns[$tx_key];
                            if(isset($tx_row['txId'])){
                                $data['round_id'] = $tx_row['txId'];
                            }
                        }
                    }
                }
                $data['end_at'] = $settle_info['end_at'];
                $data['md5_sum'] = $this->CI->original_game_logs_model->generateMD5SumOneRow($data, ['flag_of_updated_result', 'result_amount', 'end_at']);
                $this->CI->original_game_logs_model->updateRowsToOriginal('common_seamless_wallet_transactions', $data);
            }
        }
        return array("success" => true, array("total_refIds_updated" => count($refIds)));
    }

    public function queryRefIdsForUpdate($dateFrom, $dateTo) {
        $this->CI->load->model('original_game_logs_model');
        $sqlTime="cswt.end_at >= ? AND cswt.end_at <= ? AND cswt.game_platform_id = ?";

        $sql = <<<EOD
SELECT
DISTINCT(transaction_id)
FROM common_seamless_wallet_transactions as cswt
WHERE
{$sqlTime}
EOD;
        $params=[
            $dateFrom,
            $dateTo,
            $this->getPlatformCode()
        ];

        $this->CI->utils->debug_log('queryTransactionsForUpdate sql', $sql, $params);
        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $result;  
    }

    public function queryRefIdDetails($refId) {
        $this->CI->load->model('original_game_logs_model');
        $sqlRefId="cswt.transaction_id = ? AND cswt.game_platform_id = ?";

        $sql = <<<EOD
SELECT
    cswt.id,
    cswt.transaction_type,
    cswt.bet_amount,
    cswt.status,
    cswt.result_amount,
    cswt.start_at,
    cswt.end_at,
    cswt.extra_info

FROM common_seamless_wallet_transactions as cswt
WHERE
{$sqlRefId}
EOD;
        $params=[
            $refId,
            $this->getPlatformCode()
        ];

        $this->CI->utils->debug_log('queryTransactionsForUpdate sql', $sql, $params);
        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $result;  
    }

    public function syncMergeToGameLogs($token) {
        $this->syncOriginalGameLogs($token);
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
        $sqlTime="ibc.end_at >= ? AND ibc.end_at <= ? AND ibc.game_platform_id = ? AND ibc.flag_of_updated_result >= ?";

        $sql = <<<EOD
SELECT
ibc.id as sync_index,
ibc.response_result_id,
ibc.external_unique_id as external_uniqueid,
ibc.md5_sum,

ibc.player_id,
ibc.game_platform_id,
ibc.bet_amount as bet_amount,
ibc.bet_amount as real_betting_amount,
ibc.result_amount,
ibc.amount,
ibc.transaction_type,
ibc.game_id as game_code,
ibc.game_id as game,
ibc.game_id as game_name,
ibc.transaction_id as round_number,
ibc.round_id,
ibc.response_result_id,
ibc.extra_info,
ibc.start_at,
ibc.start_at as bet_at,
ibc.end_at,
ibc.before_balance,
ibc.after_balance,
ibc.transaction_id,
ibc.flag_of_updated_result as status,

gd.id as game_description_id,
gd.game_type_id

FROM common_seamless_wallet_transactions as ibc
LEFT JOIN game_description as gd ON ibc.game_id = gd.external_game_id AND gd.game_platform_id = ?
WHERE
{$sqlTime}
EOD;

        
        $params=[
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo,
            $this->getPlatformCode(),
            Game_logs::STATUS_SETTLED
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
        if($this->is_get_login_url){
            $row['round_number'] = $row['round_id'];
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
                'after_balance' => null,
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
            'bet_details' => $this->preprocessBetDetails($row,null,true),
            'extra' => $row['extra'],
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
        $row['extra'] = [];
        $row['bet_details'] = [];
        $extra_info  = json_decode($row['extra_info'], true)['message'];
        if($extra_info['action'] != "PlaceBetParlay"){
            if(isset($extra_info['betAmount'])){
                $row['real_betting_amount'] = $this->gameAmountToDB($extra_info['betAmount']);
            }
            $row['extra'] = array(
                'match_type' => isset($extra_info['betType'])  ? $this->getBetType($extra_info['betType']) : null,
                'handicap' => isset($extra_info['point']) ? $extra_info['point'] : null,
                'odds' => isset($extra_info['odds']) ? $extra_info['odds'] : null,
                'odds_type' => isset($extra_info['oddsType'])  ? $this->getOddsType($extra_info['oddsType']) : null,
                'bet_type' => Game_logs::BET_TYPE_SINGLE_BET,
            );

            $row['bet_details'] = array(json_encode(
                    array(
                        'bet_details' => array_filter(
                            array(
                                'refNo' => $row['external_uniqueid'],
                                'betTime' => isset($extra_info['betTime']) ? $extra_info['betTime'] : null,
                                'bet' => isset($extra_info['betChoice_en']) ? $extra_info['betChoice_en'] : null,
                                'betType' => isset($extra_info['betTypeName_en']) ? $extra_info['betTypeName_en'] : null,
                                'vs' => isset($extra_info['homeName_en'],$extra_info['awayName_en']) ? $extra_info['homeName_en'] .'-'. $extra_info['awayName_en']: null,
                                'League' => isset($extra_info['leagueName_en']) ? $extra_info['leagueName_en'] : null,
                            )
                        )
                    ))
                
            );
        } else {
            if(isset($extra_info['totalBetAmount'])){
                $row['real_betting_amount'] = $this->gameAmountToDB($extra_info['totalBetAmount']);
            }
            $tickets = isset($extra_info['ticketDetail']) ? $extra_info['ticketDetail'] : [];
            $parlayData = [];
            if(!empty($tickets)){
                foreach ($tickets as $key => $ticket) {
                    $parlayData[]=array(
                        'bet' => isset($ticket['betChoice_en']) ? $ticket['betChoice_en'] : null,
                        'betType' => isset($ticket['betTypeName_en']) ? $ticket['betTypeName_en'] : null,
                        'vs' => isset($ticket['homeName_en'],$ticket['awayName_en']) ? $ticket['homeName_en'] .'-'. $ticket['awayName_en']: null,
                        'League' => isset($ticket['leagueName_en']) ? $ticket['leagueName_en'] : null,
                    );
                }
            }
            if(!empty($parlayData)){
                $row['bet_details'] = array(json_encode(array('bet_details' => array_filter($parlayData))));
            }
            $row['extra'] = array(
                'bet_type' => Game_logs::BET_TYPE_MULTI_BET,
            );
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
    
    public function defaultBetDetailsFormat($row) {
        $bet_details = [];
        
        if(isset($row['extra_info'])){
            $extra_info = isset($row['extra_info']) ? $row['extra_info'] : null;
            $extra_info = json_decode($extra_info);
            $message = isset($extra_info->message) ? $extra_info->message : null;
         
            $ticketDetail = isset($message->ticketDetail) ? $message->ticketDetail : null;
            if(isset($message->odds)){
                $bet_details['odds'] = $message->odds;
            }
            if(isset($message->leagueName)){
                $bet_details['league_name'] = $message->leagueName;
            }
            if(isset($message->sportTypeName_en)){
                $bet_details['sports_name'] = $message->sportTypeName_en;
            }

            if(is_array($ticketDetail)){
                foreach($ticketDetail as $detail){
                    if(isset($detail->odds)){
                        $bet_details['odds'] = $detail->odds;
                    }
                    if(isset($detail->leagueName)){
                        $bet_details['league_name'] = $detail->leagueName;
                    }
                    if(isset($detail->sportTypeName_en)){
                        $bet_details['sports_name'] = $detail->sportTypeName_en;
                    }
                }
            }
        }

        if (isset($row['transaction_id'])) {
            $bet_details['bet_id'] = $row['transaction_id'];
        }

        if (isset($row['round_id'])) {
            $bet_details['round_id'] = $row['round_id'];
        }


        if (isset($row['bet_amount'])) {
            $bet_details['bet_amount'] = $row['bet_amount'];
        }

        if (isset($row['bet_at'])) {
            $bet_details['betting_datetime'] = $row['bet_at'];
        }
        return $bet_details;
     }
}
/*end of file*/