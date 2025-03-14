<?php
require_once dirname(__FILE__) . '/game_api_common_onebook.php';

class Game_api_ibc_onebook extends Game_api_common_onebook {

	const IBC_ONEBOOK_GAMELOGS_TABLE = "ibc_onebook_game_logs";

	public function getPlatformCode(){
		return IBC_ONEBOOK_API;
    }

    public function __construct(){
        parent::__construct();
        $this->currency_type = $this->getSystemInfo('currency_type');
        $this->original_gamelogs_table = self::IBC_ONEBOOK_GAMELOGS_TABLE;
        $this->wallet_type = $this->getSystemInfo('wallet_type');
        $this->language = $this->getSystemInfo('language');
        $this->oneworks_bet_setting = $this->getSystemInfo('oneworks_bet_setting');
        $this->seamless_debit_transaction_type = $this->getSystemInfo('seamless_debit_transaction_type', ['debit', 'placebetparlay', 'placebet']);
    }

    public function setMemberBetSetting($playerName,$newBetSetting=null) {
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForMemberSettings',
            'playerName' => $playerName,
        );
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $sportsTypeArr = $this->oneworks_bet_setting;
        $betSettingArr = array();

        if (!empty($sportsTypeArr)) {
            foreach ($sportsTypeArr as $key) {
                if (!empty($key['sport_type'])) {
                    foreach ($key['sport_type'] as $type) {
                        $betSettingArr[$type] = array(
                            "sport_type" => (string)$type,
                            "min_bet" => $key['min_bet'],
                            "max_bet" => $key['max_bet'],
                            "max_bet_per_match" => $key['max_bet_per_match'],
                            // "max_bet_per_ball" => isset($key['max_bet_per_ball']) ? $key['max_bet_per_ball'] : $key['max_bet_per_match'],
                        );

                        $multiplier = isset($this->max_payout_per_match_multiplier)?$this->max_payout_per_match_multiplier:8;
						$betSettingArr[$type]['max_payout_per_match'] = isset($key['max_payout_per_match'])?$key['max_payout_per_match']:$betSettingArr[$type]['max_bet_per_match']*$multiplier;

                    }
                }
            }
        }

        # Update new settings
        if(!empty($newBetSetting)){
            foreach ($newBetSetting as $setting) {
                $betSettingArr[$setting['sport_type']] = $setting;
            }
        }

        # RE-KEY ARRAY
        $betSettingArr = array_values($betSettingArr);

        $params = array(
            "vendor_id" => $this->vendor_id,
            "vendor_member_id" => $gameUsername,
            "bet_setting" => json_encode($betSettingArr),
            "operatorid" => $this->operator_id
        );

        return $this->callApi(self::API_setMemberBetSetting, $params, $context);
    }

    public function processResultForMemberSettings($params) {
        $statusCode = $this->getStatusCodeFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJson = $this->getResultJsonFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $success = $this->processResultBoolean($responseResultId, $resultJson, $statusCode);
        $result = array('response_result_id' => $responseResultId, 'result' => $resultJson);
        return array($success, $result);
    }

    public function queryPlayerBalance($playerName)
    {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryPlayerBalance',
            'gameUsername' => $gameUsername
        );

        $params = array(
            "vendor_id" => $this->vendor_id,
            "vendor_member_ids" => $gameUsername,
            "wallet_id" => self::WALLET_TYPE[$this->wallet_type],
            "operatorid" => $this->operator_id
        );

        return $this->callApi(self::API_queryPlayerBalance, $params, $context);
    }

    public function processResultForQueryPlayerBalance($params)
    {
        $statusCode = $this->getStatusCodeFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        $result = [];
        $result['balance'] = 0;
        if($success){
            $result['balance'] = @$resultArr['Data'][0]['balance'] ?: 0;
        }
        return array($success, $result);
    }

    public function depositToGame($playerName, $amount, $transfer_secure_id=null)
    {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $external_transaction_id = empty($transfer_secure_id) ? 'T'.$this->generateUnique() : $transfer_secure_id;

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForDepositToGame',
            'amount' => $amount,
            'external_transaction_id' => $external_transaction_id
        );

        $params = array(
            "vendor_id" => $this->vendor_id,
            "vendor_member_id" => $gameUsername,
            "vendor_trans_id" => $this->getSystemInfo('prefix_for_username').$external_transaction_id,
            "amount" => $amount,
            "currency" => $this->currency_id,
            "direction" => self::DEPOSIT,
            "wallet_id" => self::WALLET_TYPE[$this->wallet_type],
            "operatorid" => $this->operator_id
        );
        return $this->callApi(self::API_depositToGame, $params, $context);
    }

    public function processResultForDepositToGame($params)
    {
        $statusCode = $this->getStatusCodeFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $success = $this->processResultBoolean($responseResultId,$resultArr,$statusCode);
        $result = array(
            'response_result_id' => $responseResultId,
            'external_transaction_id' => $external_transaction_id,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id' => self::REASON_UNKNOWN
        );

        if ($success) {
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
            $result['didnot_insert_game_logs'] = true;
        }else{
            // if it's 500 , convert it to success
            if((in_array($statusCode, $this->other_status_code_treat_as_success)) && $this->treat_500_as_success_on_deposit){
                $result['reason_id']=self::REASON_GAME_PROVIDER_INTERNAL_PROBLEM;
                $result['transfer_status']=self::COMMON_TRANSACTION_STATUS_UNKNOWN;
                $success=true;
            }else{
                $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
                $result['reason_id'] = $this->getReasons($statusCode);
            }
        }
        return array($success, $result);
    }

    public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null)
    {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $external_transaction_id = empty($transfer_secure_id) ? 'W'.$this->generateUnique() : $transfer_secure_id;

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForWithdrawFromGame',
            'amount' => $amount,
            'external_transaction_id' => $external_transaction_id
        );

        $params = array(
            "vendor_id" => $this->vendor_id,
            "vendor_member_id" => $gameUsername,
            "vendor_trans_id" => $this->getSystemInfo('prefix_for_username').$external_transaction_id,
            "amount" => $amount,
            "currency" => $this->currency_id,
            "direction" => self::WITHDRAWAL,
            "wallet_id" => self::WALLET_TYPE[$this->wallet_type],
            "operatorid" => $this->operator_id
        );
        return $this->callApi(self::API_withdrawFromGame, $params, $context);
    }

    public function processResultForWithdrawFromGame($params)
    {
        $statusCode = $this->getStatusCodeFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr,$statusCode);

        $result = array(
            'response_result_id' => $responseResultId,
            'external_transaction_id' => $external_transaction_id,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id' => self::REASON_UNKNOWN
        );

        if ($success) {
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
            $result['didnot_insert_game_logs'] = true;
        }else{
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
            $result['reason_id'] = $this->getReasons($statusCode);
        }
        return array($success, $result);
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
            "operatorid" => $this->operator_id,
            "vendor_member_id" => $gameUsername,
            "platform" => $platform,
        );

        $this->CI->utils->debug_log('GetSabaUrl params: ' . http_build_query($params));
        return $this->callApi(self::API_getSabaUrl, $params, $context);
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
        $language = $this->getLauncherLanguage($this->getPlayerDetails($playerId)->language);

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
            $others = array(
                "lang" => $language,
                "webskintype" => $this->web_skin_type,
            );
            $url = $newGameUrl ."&". http_build_query($others);

            if($this->mobile_skin && isset($is_mobile)){
                $url .= "&skin=".$this->mobile_skin;
            }

            #check game type if esports
            if(isset($extraParams['game_type']) && ($extraParams['game_type']==self::ESPORTS_GAME || $extraParams['game_type']==self::E_SPORTS_GAME)){
                if($isMobile){
                    $url .= "&types=".self::ESPORTS_GAME;
                }else{
                    $url .= "&game=".self::ESPORTS_GAME;
                }
            }
            
            $success = true;
        }

        $this->CI->utils->debug_log('oneworks queryForwardGame login URL--------->: ', $url);

        return ['success' => $success, 'url' => $url];
    }

    public function syncOriginalGameLogs($token = false)
    {
        $startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
        $startDate = new DateTime($startDate->format('Y-m-d H:i:s'));
        $endDate = new DateTime($endDate->format('Y-m-d H:i:s'));
        $startDate->modify($this->getDatetimeAdjust());
        $this->CI->utils->debug_log('startDate', $startDate, 'endDate', $endDate);

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForSyncOriginalGameLogs',
        ];

        $done = false;
        $result = ["success" => false];

        while (!$done) {
            # get the last version key in db
            # check if last version key exist, if null it means it needs to call the first record which is 0 version key
            $last_version_key = $this->CI->external_system->getLastSyncIdByGamePlatform($this->getPlatformCode()) ?: 0;
            $params = [
                        "vendor_id" => $this->vendor_id,
                        "version_key" => $last_version_key,
                        "operatorid" => $this->operator_id
                      ];
            $resultData = $this->callApi(self::API_syncGameRecords, $params, $context);
            $result = ["success" => $resultData['success']];

            //error or done
            $done = $resultData['success'];
            if(!$resultData['success']){
                $this->CI->utils->error_log('wrong result', $resultData);
                $result['error_message']=@$resultData['error_message'];
            }
        }
        return $result;
    }

    public function processResultForSyncOriginalGameLogs($params)
    {
        $statusCode = $this->getStatusCodeFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId,$resultArr,$statusCode);

        $lastVersionKey = isset($resultArr['Data']['last_version_key'])?$resultArr['Data']['last_version_key']:null;
        $result = ['data_count' => 0,'last_version_key' => $lastVersionKey];

        $betDetailsGameRecords = !empty($resultArr['Data']['BetDetails'])?$resultArr['Data']['BetDetails']:[];
        $betNumberDetailsGameRecords = !empty($resultArr['Data']['BetNumberDetails'])?$resultArr['Data']['BetNumberDetails']:[];
        $betVirtualSportGameRecords = !empty($resultArr['Data']['BetVirtualSportDetails'])?$resultArr['Data']['BetVirtualSportDetails']:[];
        $gameRecords = !empty($resultArr['Data']['BetDetails'])?$resultArr['Data']['BetDetails']:[];

        $gameRecords = array_merge($betDetailsGameRecords,$betNumberDetailsGameRecords,$betVirtualSportGameRecords);

        if($success && !empty($gameRecords)){
            $extra = ['response_result_id' => $responseResultId];
            // $this->CI->utils->debug_log('onebook 1THBGAMERECORDS', $gameRecords);
            $this->rebuildGameRecords($gameRecords,$extra);

            $oldCnt=count($gameRecords);
            $this->CI->load->model(array('original_game_logs_model'));
            $this->CI->original_game_logs_model->removeDuplicateUniqueid($gameRecords, 'trans_id', function($row1st, $row2nd){
                //compare status
                $status1st=strtolower($row1st['ticket_status']);
                $status2nd=strtolower($row2nd['ticket_status']);
                //if same status, keep second
                if($status1st==$status2nd){
                    return 2;
                }else if($status1st=='waiting'){
                    return 2;
                }else if($status2nd=='waiting'){
                    return 1;
                }else if($status1st=='running'){
                    return 2;
                }else if($status2nd=='running'){
                    return 1;
                }
                //default is last
                return 2;
            });
            $cnt=count($gameRecords);

            $this->CI->utils->debug_log('removeDuplicateUniqueid oldCnt:'.$oldCnt.', cnt:'.$cnt);

            $this->CI->load->model('original_game_logs_model');
            list($insertRows, $updateRows) = $this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                    $this->original_gamelogs_table,
                    $gameRecords,
                    'external_uniqueid',
                    'external_uniqueid',
                    self::MD5_FIELDS_FOR_ORIGINAL,
                    'md5_sum',
                    'id',
                    self::MD5_FLOAT_AMOUNT_FIELDS
                );

            $this->CI->utils->debug_log('ONEBOOK after process available rows', 'gamerecords ->',count($gameRecords), 'insertrows->',count($insertRows), 'updaterows->',count($updateRows));
            $insertRows = json_encode($insertRows);
            unset($gameRecords);
            if (!empty($insertRows)) {
                $result['data_count'] += $this->updateOrInsertOriginalGameLogs($insertRows,'insert',$lastVersionKey);
            }
            unset($insertRows);
            if (!empty($updateRows)) {
                $result['data_count'] += $this->updateOrInsertOriginalGameLogs($updateRows,'update',$lastVersionKey);
            }
            unset($updateRows);
        }

        //will update last sync id
        if (!empty($lastVersionKey)) {
            $this->CI->external_system->setLastSyncId($this->getPlatformCode(),$lastVersionKey);
        }
        return array($success, $result);
    }

    public function queryTransaction($transactionId, $extra) {
        $playerName=$extra['playerName'];
        $playerId=$extra['playerId'];
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryTransaction',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'playerId'=>$playerId,
            'external_transaction_id' => $transactionId
        );

        $params = array(
            "vendor_id" => $this->vendor_id,
            "vendor_trans_id" => $this->getSystemInfo('prefix_for_username').$transactionId,
            "wallet_id" => self::WALLET_TYPE[$this->wallet_type],
            "operatorid" => $this->operator_id
        );
        return $this->callApi(self::API_queryTransaction, $params, $context);
    }

    public function processResultForQueryTransaction($params)
    {
        $statusCode = $this->getStatusCodeFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJsonArr = $this->getResultJsonFromParams($params);
        $transId = $this->getVariableFromContext($params, 'external_transaction_id');
        $success = $this->processResultBoolean($responseResultId,$resultJsonArr,$statusCode);

        $this->CI->utils->debug_log('oneworks query response', $resultJsonArr, 'transaction id', $transId);

        $result = array(
            'response_result_id' => $responseResultId,
            'external_transaction_id'=>$transId,
            'status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id'=>self::REASON_UNKNOWN
        );
        if($success) {
            $result['status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
        } else {
            $result['reason_id'] = $this->getTransferErrorReasonCode($resultJsonArr['error_code']);
            $result['status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
        }
        return array($success, $result);
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
                onebook.after_amount as after_balance,
                onebook.last_sync_time as updated_at,
                onebook.bet_type as bet_type_oneworks,
                onebook.ticket_status as status_in_db,
                onebook.transaction_time as bet_at,
                onebook.settlement_time,
                onebook.winlost_datetime,
                onebook.sport_type as game_code,
                onebook.sport_type as game,
                onebook.external_uniqueid,
                onebook.md5_sum,
                onebook.settlement_time as end_at,
                onebook.version_key,
                game_provider_auth.player_id,
                gd.id as game_description_id,
                gd.game_name as game_description_name,
                gd.game_type_id
            FROM $this->original_gamelogs_table as onebook
            LEFT JOIN game_description as gd ON onebook.sport_type = gd.external_game_id AND gd.game_platform_id = ?
            LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
            JOIN game_provider_auth ON onebook.vendor_member_id = game_provider_auth.login_name
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

        return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    }

    public function makeParamsForInsertOrUpdateGameLogsRow(array $row)
    {
        $extra = [
            'match_type' => $row['match_type'],
            'handicap' => $row['hdp'],
            'odds' => $row['odds'],
            'odds_type' => $row['odds_type'],
            'is_parlay' => $row['parlaydata']
        ];

        $has_both_side=0;
        if(!empty($row['cashoutdata']))
        {
            $cash_out_stake = 0;
            $cashoutdata = json_decode($row['cashoutdata']);
            foreach ($cashoutdata as $key => $cashout) {
                $row['result_amount'] += ((float)$cashout->buyback_amount - $cashout->real_stake);
                $cash_out_stake += (float)$cashout->stake;
            }
            $extra['trans_amount'] = (!empty($row['original_stake'])) ? $row['original_stake'] : $cash_out_stake + $row['bet_amount'];
        }

        # no available amount when draw status
        if(strtolower($row['status_in_db']) == self::STATUS_DRAW){
            $row['bet_amount'] = 0;
            $extra['note'] = lang("Draw");
        }

		if(isset($row['note']) && !empty($row['note'])){
			$extra['note'] = lang($row['note']);
		}

        if(empty($row['md5_sum'])){
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row,
                self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
        }

        if(!empty($row['end_at'])){
            $row['end_at'] = $row['end_at'];
        }else{
            $row['end_at'] = $row['bet_at'];
        }

        if(!empty($row['result_amount'])) {
            $row['valid_bet'] = abs($row['result_amount']);
        } else {
            $row['valid_bet'] = $row['bet_amount'];
        }

        return [
            'game_info' => [
                'game_type_id' => $row['game_type_id'],
                'game_description_id' => $row['game_description_id'],
                'game_code' => $row['game_code'],
                'game_type' => $row['game_type_id'],
                'game' => $row['game_code']
            ],
            'player_info' => [
                'player_id' => $row['player_id'],
                'player_username' => $row['username']
            ],
            'amount_info' => [
                'bet_amount' => $row['bet_amount'],
                'result_amount' => floatval($row['result_amount']),
                'bet_for_cashback' => $row['bet_for_cashback'],
                'real_betting_amount' => $row['real_bet_amount'],
                'win_amount' => null,
                'loss_amount' => null,
                'after_balance' =>floatval($row['after_balance']),
            ],
            'date_info' => [
                'start_at' => $row['bet_at'],
                'end_at' => $row['end_at'],
                'bet_at' => $row['bet_at'],
                'updated_at' => $this->CI->utils->getNowForMysql(),
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => $row['status'],
            'additional_info' => [
                'has_both_side' => 0,
                'external_uniqueid' => $row['external_uniqueid'],
                'round_number' => $row['external_uniqueid'],
                'md5_sum' => $row['md5_sum'],
                'response_result_id' => $row['response_result_id'],
                'sync_index' => $row['sync_index'],
                'bet_type' => $row['bet_type']
            ],
            'bet_details' => $row['bet_details'],
            'extra' => $extra,
            //from exists game logs
            'game_logs_id'=>isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];
    }

    public function queryTransactionByDateTime($startDate, $endDate){
        //$this->CI->load->model('original_game_logs_model');

$sql = <<<EOD
SELECT
t.player_id as player_id,
t.created_at transaction_date,
t.amount as amount,
t.after_balance as after_balance,
t.before_balance as before_balance,
t.round_id as round_no,
t.external_unique_id as external_uniqueid,
t.transaction_type trans_type,
t.extra_info extra_info
FROM {$this->original_transactions_table} as t
WHERE t.game_platform_id = ? and `t`.`updated_at` >= ? AND `t`.`updated_at` <= ?
ORDER BY t.updated_at asc;

EOD;

$params=[$this->getPlatformCode(),$startDate, $endDate];
$this->utils->info_log('queryTransactionByDateTime', 'sql', $sql, 'params',$params);

        return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);

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
                $extra_info = [];
                $extra=[];
                $extra['trans_type'] = $transaction['trans_type'];
                $extra['extra'] = $extra_info;
                $temp_game_record['extra_info'] = json_encode($extra);
                $temp_game_record['external_uniqueid'] = $transaction['player_id'].'-'.$transaction['external_uniqueid'];

                $temp_game_record['transaction_type'] = Transactions::GAME_API_ADD_SEAMLESS_BALANCE;
                if($transaction['after_balance']<$transaction['before_balance']){
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