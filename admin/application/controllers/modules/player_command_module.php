<?php
/**
 * @property \Utils $utils
 * @property \Player_model $player_model
 * @property \Player_recent_game_model $player_recent_game_model
 */
trait player_command_module {
	public function cronjob_clear_recent_game($dateTimeFromStr = null, $dateTimeToStr = null)
	{
		$this->load->model(['player_model', 'player_recent_game_model']);

		$login_time_min = $this->utils->formatDateTimeForMysql(new DateTime($dateTimeFromStr ?: "-7 days"));
		$login_time_max = $this->utils->formatDateTimeForMysql(new DateTime($dateTimeToStr ?: "now"));

		$this->utils->debug_log("# clear_player_recent_game: From: {$login_time_min}, To: {$login_time_max}");

		$playerIds = $this->player_model->getAllEnabledPlayersByActivityTime($login_time_min, $login_time_max);

		$total_count = count($playerIds);
		$success_count = 0;
		foreach($playerIds as $player){
			$result = $this->player_recent_game_model->clearRecentGame($player->playerId);
			if($result) $success_count++;
		}

		$this->utils->debug_log("# clear_player_recent_game resoult: {$success_count} / {$total_count}");
	}

    /**
     * gen_player_session_file_list
     *
     * sudo /bin/bash admin/shell/command.sh cronjob_sync_player_session_file_into_relay > ./logs/command_cronjob_sync_player_session_file_into_relay.log 2>&1 &
     *
     * @return void
     */
    public function cronjob_sync_player_session_file_into_relay(){
        $this->load->model(['player_session_files_relay']);
        $this->load->library(array('Lib_session_of_player'));
        // $this->player_session_files_relay->cron4genSessionFileList($fixFilename);
        $results = [];

        $session_of_player = $this->utils->getConfig('session_of_player');
        $is_scan_with_relay = ($session_of_player['scan_session_with'] === Lib_session_of_player::SCAN_SESSION_WITH_RELAY_TABLE )? true: false;
        if( ! $is_scan_with_relay ){
            $this->utils->debug_log('is_scan_with_relay:', $is_scan_with_relay, ' scan_session_with:', $session_of_player['scan_session_with']);
            return false;
        }

        $fixFilename = null;
        $this->benchmark->mark('cron4genSessionFileList_start');
        $results['cron4genSessionFileList'] = $this->player_session_files_relay->cron4genSessionFileList($fixFilename);
        $this->benchmark->mark('cron4genSessionFileList_stop');
        $this->utils->debug_log('results.cron4genSessionFileList', $results['cron4genSessionFileList']
                                , 'elapsed_time:', $this->benchmark->elapsed_time('cron4genSessionFileList_start', 'cron4genSessionFileList_stop' ) );

        $this->benchmark->mark('cron4syncTableFromFiles_start');
        $results['cron4syncTableFromFiles'] = $this->player_session_files_relay->cron4syncTableFromFiles();
        $this->benchmark->mark('cron4syncTableFromFiles_stop');
        $this->utils->debug_log('results.cron4syncTableFromFiles', $results['cron4syncTableFromFiles']
                                , 'elapsed_time:', $this->benchmark->elapsed_time('cron4syncTableFromFiles_start', 'cron4syncTableFromFiles_stop' ) );

        $this->benchmark->mark('cron4syncTableByfileExists_start');
        $results['cron4syncTableByfileExists'] = $this->player_session_files_relay->cron4syncTableByfileExists();
        $this->benchmark->mark('cron4syncTableByfileExists_stop');
        $this->utils->debug_log('results.cron4syncTableByfileExists', $results['cron4syncTableByfileExists']
                                , 'elapsed_time:', $this->benchmark->elapsed_time('cron4syncTableByfileExists_start', 'cron4syncTableByfileExists_stop' ) );

    } // EOF cronjob_sync_player_session_file_into_relay()

     /**
     * for cronjob_update_players_deposit_count
     *
     * @param string $startData 
     * @param string $enddate
     * @return void
     */
    public function updatePlayerDepositCount($startDate = '', $endDate = '')
    {        
        $this->load->model(['sale_order','operatorglobalsettings']);

        $now = time();

		$startTime = !empty($startDate)? strtotime($startDate): $now - 660;
		$start = date('Y-m-d H:i:s', $startTime);
        $end   = date('Y-m-d H:i:s', !empty($endDate)? strtotime($endDate):  $startTime + 600);

        $orders = $this->sale_order->getPlayerIdWithApprovedDeclined($start, $end);
        if (!empty($orders)) {
            $success = $fail = [];

            foreach ($orders as $v) {
                if ($v['status'] == Sale_order::STATUS_SETTLED) {
                    $success[] = $v['player_id'];
                } else {
                    $fail[] = $v['player_id'];
                }
            }

            if (!empty($success)) {
                $this->updatePlayersDepositCountWithStatus(
                    implode('_',array_unique($success)),
                    Sale_order::STATUS_SETTLED,
                    function () {}
                );
            }
		
            if (!empty($fail)) {
                $this->updatePlayersDepositCountWithStatus(
                    implode('_',array_unique($fail)),
                    Sale_order::STATUS_DECLINED,
                    function () {}
                );
            }
        }
    }


    /**
     * for cronjob_update_players_approved_deposit_count
     *
     * @param string|integer $playerIds The field,"player_id". The separator:_ (under line).
     * @return void
     */
    public function updatePlayersApprovedDepositCount($playerIds = '0', $status = null){
        $this->load->model(['sale_order','operatorglobalsettings']);
        $this->load->library(array('payment_library'));
        if( is_null($status) ){
            $status = Sale_order::STATUS_SETTLED;
        }
        $cachekey = $this->payment_library->getCachekeyWithStatusOfSaleOrder($status);

        if( empty($playerIds) ){
            $_playerIds = $this->payment_library->cronGetData2OperatorSettings($cachekey);
            if( !empty($_playerIds)){
                $playerIds = implode('_', $_playerIds);
            }
        }
        $_this = $this;
        return $this->updatePlayersDepositCountWithStatus( $playerIds // #1
                                                    , $status // #2
                                                    , function($_player_id) use ($status, $_this) { // #3, callBack4updatedCount4Player
            $_this->payment_library->removePlayerId2refreshPlayersDepositCountWithStatus($_player_id, $status);
        });
    } // EOF updatePlayersApprovedDepositCount

    public function cronjob_update_promorule_release_bonus_count($override = "OFF", $promo_rule_id = _COMMAND_LINE_NULL){
        $this->load->model(['promorules']);
        if(!$this->db->field_exists('bonusReleaseCount', 'promorules')){
            $this->utils->debug_log('cronjob_update_promorule_release_bonus_count bonusReleaseCount field not exists');
            return;
        }
        $search = [];
        if( $promo_rule_id !== _COMMAND_LINE_NULL ){
            $search['promorulesId'] = $promo_rule_id;
        }
        $promorules = $this->promorules->getAllPromoRule(true, false, $search);
        if( empty($promorules) ){
            $this->utils->debug_log('cronjob_update_promorule_release_bonus_count promorules is empty');
            return;
        }
        foreach($promorules as $promorule){

            $promoruleId = $promorule['promorulesId'];
            $lastSyncFrom = $this->utils->safeGetArray($promorule, 'syncReleaseCountAt', $promorule['createdOn']);
            
            $isFirstDateOfCurrentMonth = $this->utils->isFirstDateOfCurrentMonth();
            if( $isFirstDateOfCurrentMonth) {
                $runOverride = empty($lastSyncFrom) || (date('Y-m-d', $lastSyncFrom) !== $this->getFirstDateOfCurrentMonth());
                $this->utils->debug_log('isFirstDateOfCurrentMonth:', $isFirstDateOfCurrentMonth);
                if( $runOverride ){
                    $override = "ON";
                    $this->utils->debug_log('runOverride due to firstdate lastSyncFrom:', $lastSyncFrom);
                }
            }

            $currentReleaseCount = $promorule['bonusReleaseCount'];
            if( $override === "ON" ){
                $this->utils->debug_log('override:', $override);
                $lastSyncFrom = $promorule['createdOn'];
                $currentReleaseCount = 0;
            }
            $count = $this->promorules->getPromoRuleReleaseCount($lastSyncFrom, $promoruleId);
            $this->utils->debug_log('promoruleId:', $promoruleId, 'count:', $count, 'currentReleaseCount:', $currentReleaseCount);
            if( $count > 0 ){
                $count = $currentReleaseCount + $count;
                $rlt = $this->promorules->updatePromoRuleReleaseCount($count, $promoruleId);
                $this->utils->debug_log('updatePromoRuleReleaseCount rlt:', $rlt, 'promoruleId:', $promoruleId, 'count:', $count);
            }
        }
        $this->utils->debug_log('cronjob_update_promorule_release_bonus_count done');
    } // EOF cronjob_update_promorule_release_bonus_count

    /**
     * for cronjob_update_players_declined_deposit_count
     *
     * @param string|integer $playerIds The field,"player_id". The separator:_ (under line).
     * @return void
     */
    public function updatePlayersDeclinedDepositCount($playerIds = '0', $status = null){
        $this->load->model(['sale_order']);
        $this->load->library(array('payment_library'));
        if( is_null($status) ){
            $status = Sale_order::STATUS_DECLINED;
        }
        $cachekey = $this->payment_library->getCachekeyWithStatusOfSaleOrder($status);

        if( empty($playerIds) ){
            $_playerIds = $this->payment_library->cronGetData2OperatorSettings($cachekey);
            if( !empty($_playerIds)){
                $playerIds = implode('_', $_playerIds);
            }
        }
        $_this = $this;
        return $this->updatePlayersDepositCountWithStatus( $playerIds // #1
                                                    , $status // #2
                                                    , function($_player_id) use ($status, $_this) { // #3, callBack4updatedCount4Player
            $_this->payment_library->removePlayerId2refreshPlayersDepositCountWithStatus($_player_id, $status);
        });
    } // EOF updatePlayersDeclinedDepositCount
    /**
     * for refrash count of approved/declined deposit in some players
     *
     * @param string|integer $playerIds The field,"player_id". The separator:_ (under line).
     * @param integer $status sale_orders.status Field
     * @param callable $callBack4updatedCount4Player (void)$callBack4updatedCount4Player($player_id)
     * @return void
     */
    public function updatePlayersDepositCountWithStatus($playerIds = '', $status = null, callable $callBack4updatedCount4Player){
        $this->load->model(['player_model', 'sale_order',]);
        $this->load->library(['payment_library']);

        $updatedCounter = [];
        $updatedCounter['success'] = 0;
        $updatedCounter['success_playerId_list'] = [];
        $updatedCounter['failed_playerId_list'] = [];
        $updatedCounter['notFound_playerId_list'] = [];
        $playerId_list = explode('_', $playerIds);
        // playerId, username
        $list = $this->player_model->getEnabledPlayers($playerId_list);

        /// convert to array row.
        $listArray = [];
        foreach($list as $index => $row){
            $listArray[$index] = (array)$row;
        }
        $list = $listArray;
        unset($listArray);

        //should split by 500
        $msg = PHP_EOL. $this->utils->debug_log('total count', count($list), 'playerId_list:', $playerId_list);
        $updateList = [];
        if( ! empty(count($list))){
            $updateList = array_chunk($list, 500);
        }


        if( is_null($status) ){
            $_STATUS = Sale_order::STATUS_DECLINED;
        }else{
            $_STATUS = $status;
        }
        switch($_STATUS){
            case Sale_order::STATUS_SETTLED:
                $_UpdateFiled = 'approved_deposit_count';
                break;
            case Sale_order::STATUS_DECLINED:
                $_UpdateFiled = 'declined_deposit_count';
                break;
        }

        // params, $playerId, $_STATUS
        $sql = <<<EOF
            SELECT COUNT(sale_orders.id) as counter
            , sale_orders.player_id as playerId
            FROM sale_orders
            WHERE sale_orders.player_id = ?
            AND sale_orders.status = ?
EOF;

        foreach ($updateList as $updateSet) {

            foreach ($updateSet as $key => $updateRow) {
                $_updateRow = [];
                $playerId = $updateRow['playerId'];

                try{
                    $this->sale_order->startTrans();
                    $row = $this->sale_order->runOneRawSelectSQLArray($sql, [$playerId, $_STATUS]);

                    $_updateRow[$_UpdateFiled] = $row['counter'];
                    $result = false;
                    $success = false;
					if( isset($_updateRow[$_UpdateFiled]) ){
						$this->utils->debug_log("updatePlayerDepositCount PlayerId:{$playerId} {$_UpdateFiled}:{$_updateRow[$_UpdateFiled]}");
                        $this->sale_order->db->where('playerId', $playerId);
                        $this->sale_order->db->update('player', $_updateRow);
                        $result = $this->sale_order->db->affected_rows();

                        $ingoreKeys = $this->utils->getConfig('ignore_updatePlayersDepositCountWithStatus_keys') ? $this->utils->getConfig('ignore_updatePlayersDepositCountWithStatus_keys'): [];
                        if (in_array($key, $ingoreKeys)){
                            $this->utils->debug_log('ignore_updatePlayersDepositCountWithStatus_keys', $key, $ingoreKeys);
                            throw new Exception("ignore_updatePlayersDepositCountWithStatus_keys");
                        }

                        $callBack4updatedCount4Player($playerId);
                    }
                    if(empty($result)){
                        $currRow = $this->player_model->getPlayerById($playerId);
                        $deposit_count = $_UpdateFiled == 'approved_deposit_count' ? $currRow->approved_deposit_count : $currRow->declined_deposit_count;
                        if($row['counter'] == $deposit_count){
                            $this->utils->info_log('PlayerId:'.$playerId.' same count', $row['counter'], $deposit_count);
                            $success =  $this->sale_order->endTransWithSucc();
                        }else{
                            throw new Exception("empty result");
                        }
                    }else{
                        $success = $this->sale_order->endTransWithSucc();
                    }
                    // $success = $this->sale_order->endTransWithSucc() && !empty($result);
                    if($success){
                        $updatedCounter['success']++;
                        $updatedCounter['success_playerId_list'][] = $playerId;
                    }
                }catch(Exception $e){
                    $this->sale_order->rollbackTrans();
                    $updatedCounter['failed_playerId_list'][] = $playerId;
                    $this->utils->debug_log('Exception', $e->getMessage(), 'playerId:', $playerId);
                }
            } // EOF foreach ($updateSet as $updateRow) {...
            $msg .= PHP_EOL. $this->utils->debug_log('list count', count($list), $updatedCounter['success']. " player(s) has been updated");
        }

        // Check the $playerId_list Not in the data table, "player".
        array_walk($playerId_list, function($_playerId, $key) use ( $list, &$updatedCounter) {
            if( !empty($list)){
                if(!in_array($_playerId, array_column($list, 'playerId') )){
                    $updatedCounter['notFound_playerId_list'][] = $_playerId;
                }
            }else{
                $updatedCounter['notFound_playerId_list'][] = $_playerId;
            }
        });

        $this->utils->debug_log('failed_playerId_list:', $updatedCounter['failed_playerId_list']);
        $this->utils->debug_log('notFound_playerId_list:', $updatedCounter['notFound_playerId_list']);
        // add the fail event
        if ( !empty($updatedCounter['failed_playerId_list'])) {
            $status = 0;
            if ($_UpdateFiled == 'approved_deposit_count') {
                $status = Sale_order::STATUS_SETTLED;
            } 
            if ($_UpdateFiled == 'declined_deposit_count') {
                $status = Sale_order::STATUS_DECLINED;
            }

            if ($status > 0) {
                foreach ($updatedCounter['failed_playerId_list'] as $failed_playerId) {
                    $this->payment_library->addPlayerId2refreshPlayersDepositCountWithStatus($failed_playerId, $status);
                }
            }
        }

        $notFound_playerId_list = $updatedCounter['notFound_playerId_list'];
        if( ! empty($notFound_playerId_list) ){
            foreach($notFound_playerId_list as $index => $notFound_playerId){
                $_player_id = $notFound_playerId;
                $this->payment_library->removePlayerId2refreshPlayersDepositCountWithStatus($_player_id, $_STATUS);
            }
        }
        $this->returnText($msg);
    } // EOF updatePlayersDepositCountWithStatus

} // EOF player_command_module
