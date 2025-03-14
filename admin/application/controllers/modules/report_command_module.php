<?php

/**
 * Class report_command_module
 *
 * General behaviors include :
 *
 * * Generate report
 *
 * @category Command Line
 * @version 1.8.10
 * @copyright 2013-2022 tot
 */
trait report_command_module {

    public function generate_all_report_daily(){
        //yesterday and today
        $from=$this->utils->getYesterdayForMysql();
        $to=$this->utils->getTodayForMysql();
        $this->generate_all_report_from_to($from, $to);
    }


	/**
	 * generate_all_report_from_to
	 * @param  string $from  Y-m-d
	 * @param  string $to   Y-m-d
	 */
    public function generate_all_report_from_to($from, $to, $token=_COMMAND_LINE_NULL){
        if(empty($from) || empty($to)){
            $this->utils->error_log('please use format Y-m-d');
            return;
        }

        if($from > $to){
            $this->utils->error_log('from should <= to', $from, $to);
            return;
        }

        $lock_rebuild_reports_range= $this->utils->getConfig('lock_rebuild_reports_range');
        //convert to day to be sure
        $from_day = $this->utils->formatDateForMysql(new DateTime($from));
        $to_day = $this->utils->formatDateForMysql(new DateTime($to));
        if(!empty($lock_rebuild_reports_range)){
            if($this->utils->isRebuildReportDateLocked($lock_rebuild_reports_range,$from_day,$to_day,$rlt)){
                $this->utils->error_log(sprintf(lang("Regenarate All Report has lock - should not be equal or older than  %s  "),$rlt['cutoff_day']));
                return;
            }
        }

        $this->load->model(['multiple_db_model']);

        $this->utils->foreachDaily($from, $to, 1, function($from, $to) use($token){
            $success=true;

            $from=$this->utils->formatDateTimeForMysql($from);
            $to=$this->utils->formatDateTimeForMysql($to);

            $this->utils->debug_log('generate all report', $from, $to);

            $process_start_time = $this->utils->getNowForMysql();

            $this->rebuild_total_only_minute_hours_day($from, $to);
            $this->generate_game_report_hourly($from, $to);
            $this->generate_player_report_hourly($from, $to);
            $this->generate_payment_report_daily($from, $to);
            $this->generate_cashback_report_daily($from, $to);
            $this->generate_promotion_report_daily($from, $to);
            $this->generate_summary2_report_daily($from, $to);

            if($token !=_COMMAND_LINE_NULL){
                $done = false;
                $result = ['from'=>$from, 'to'=>$to, 'process_start_time'=>$process_start_time, 'process_end_time'=> $this->utils->getNowForMysql()];
                $this->queue_result->appendResult($token, ['request_id'=>_REQUEST_ID, 'result'=> $result ], $done, false);
            }
            return $success;
        });

        if($token != _COMMAND_LINE_NULL){
            $done = true;
            $result = ['done_all_at'=> $this->utils->getNowForMysql()];
            $this->queue_result->appendResult($token, ['request_id'=>_REQUEST_ID, 'result'=> $result ], $done, false);
        }
    }

    public function generate_game_report_hourly($from, $to){

        $lock_rebuild_reports_range= $this->utils->getConfig('lock_rebuild_reports_range');
        $from_day = $this->utils->formatDateForMysql(new DateTime($from));
        $to_day = $this->utils->formatDateForMysql(new DateTime($to));
        if(!empty($lock_rebuild_reports_range)){
            if($this->utils->isRebuildReportDateLocked($lock_rebuild_reports_range,$from_day,$to_day,$rlt)){
                $this->utils->error_log(sprintf(lang("Regenarate All Report has lock - should not be equal or older than  %s  "),$rlt['cutoff_day']));
                return;
            }
        }

        $from = date('Y-m-d H:', strtotime($from)).'00:00';
        if(empty($to)){
            //end of current hour
            $to = date('Y-m-d H:').'59:59';
        }else{
            $to = date('Y-m-d ', strtotime($to)).'23:59:59';
        }

        if($this->utils->isSuperModeOnMDB()){
            $this->utils->debug_log('is super site');
            return $this->merge_reports_to_super('game_report_hourly', $from, $to);
        }

        //sync from total hour
        if(strtotime($from) <= strtotime($to)){
            $this->load->model(['report_model']);
            $success=$this->report_model->generate_game_report_houry($from, $to);
            $this->utils->debug_log('generate_game_report_houry', $from, $to, $success);
        }
    }

    public function generate_payment_report_daily($from, $to){

        $lock_rebuild_reports_range= $this->utils->getConfig('lock_rebuild_reports_range');
        $from_day = $this->utils->formatDateForMysql(new DateTime($from));
        $to_day = $this->utils->formatDateForMysql(new DateTime($to));
        if(!empty($lock_rebuild_reports_range)){
            if($this->utils->isRebuildReportDateLocked($lock_rebuild_reports_range,$from_day,$to_day,$rlt)){
                $this->utils->error_log(sprintf(lang("Regenarate All Report has lock - should not be equal or older than  %s  "),$rlt['cutoff_day']));
                return;
            }
        }

        $from = date('Y-m-d H:', strtotime($from)).'00:00';
        if(empty($to)){
            //end of current hour
            $to = date('Y-m-d H:').'59:59';
        }else{
            $to = date('Y-m-d ', strtotime($to)).'23:59:59';
        }

        //if is super
        if($this->utils->isSuperModeOnMDB()){
            $this->utils->debug_log('is super site');
            return $this->merge_reports_to_super('payment_report_daily', $from, $to);
        }
        //update date from transactions
        if(strtotime($from) <= strtotime($to)){
            $this->load->model(['report_model']);
            $success=$this->report_model->generate_payment_report_daily($from, $to);
            $this->utils->debug_log('generate_payment_report_daily', $from, $to, $success);
        }

    }

    public function generate_cashback_report_daily($from, $to){

        $lock_rebuild_reports_range= $this->utils->getConfig('lock_rebuild_reports_range');
        $from_day = $this->utils->formatDateForMysql(new DateTime($from));
        $to_day = $this->utils->formatDateForMysql(new DateTime($to));
        if(!empty($lock_rebuild_reports_range)){
            if($this->utils->isRebuildReportDateLocked($lock_rebuild_reports_range,$from_day,$to_day,$rlt)){
                $this->utils->error_log(sprintf(lang("Regenarate All Report has lock - should not be equal or older than  %s  "),$rlt['cutoff_day']));
                return;
            }
        }

        $from = date('Y-m-d H:', strtotime($from)).'00:00';
        if(empty($to)){
            //end of current hour
            $to = date('Y-m-d H:').'59:59';
        }else{
            $to = date('Y-m-d ', strtotime($to)).'23:59:59';
        }

        //if is super
        if($this->utils->isSuperModeOnMDB()){
            $this->utils->debug_log('is super site');
            return $this->merge_reports_to_super('cashback_report_daily', $from, $to);
        }
        //update date from transactions
        if(strtotime($from) <= strtotime($to)){
            $this->load->model(['report_model']);
            $success=$this->report_model->generate_cashback_report_daily($from, $to);
            $this->utils->debug_log('generate_cashback_report_daily', $from, $to, $success);
        }

    }

    public function generate_promotion_report_daily($from, $to){

        $lock_rebuild_reports_range= $this->utils->getConfig('lock_rebuild_reports_range');
        $from_day = $this->utils->formatDateForMysql(new DateTime($from));
        $to_day = $this->utils->formatDateForMysql(new DateTime($to));
        if(!empty($lock_rebuild_reports_range)){
            if($this->utils->isRebuildReportDateLocked($lock_rebuild_reports_range,$from_day,$to_day,$rlt)){
                $this->utils->error_log(sprintf(lang("Regenarate All Report has lock - should not be equal or older than  %s  "),$rlt['cutoff_day']));
                return;
            }
        }

        $from = date('Y-m-d H:', strtotime($from)).'00:00';
        if(empty($to)){
            //end of current hour
            $to = date('Y-m-d H:').'59:59';
        }else{
            $to = date('Y-m-d ', strtotime($to)).'23:59:59';
        }

        //if is super
        if($this->utils->isSuperModeOnMDB()){
            $this->utils->debug_log('is super site');
            return $this->merge_reports_to_super('promotion_report_details', $from, $to);
        }
        //update date from transactions
        if(strtotime($from) <= strtotime($to)){
            $this->load->model(['report_model']);
            $success=$this->report_model->generate_promotion_report_details($from, $to);
            $this->utils->debug_log('generate_promotion_report_details', $from, $to, $success);
        }

    }

	public function generate_player_report_hourly($start='last_2_hours', $end=null, $username = '_null'){

        $lock_rebuild_reports_range= $this->utils->getConfig('lock_rebuild_reports_range');

        if(!empty($lock_rebuild_reports_range)){
            if($start =='last_2_hours'){
                $start = date('Y-m-d H:', strtotime('-2 hours')).'00:00';
            }
            $from_day = $this->utils->formatDateForMysql(new DateTime($start));
            $to_day = $this->utils->formatDateForMysql(new DateTime($end));
            if($this->utils->isRebuildReportDateLocked($lock_rebuild_reports_range,$from_day,$to_day,$rlt)){
                $this->utils->error_log(sprintf(lang("Regenarate All Report has lock - should not be equal or older than  %s  "),$rlt['cutoff_day']));
                return;
            }
        }

        $from = $to = date('Y-m-d');
		if($start =='last_6_hours'){
			$from = date('Y-m-d H:', strtotime('-6 hours')).'00:00';
            $to = date('Y-m-d H:').'00:00';
		}else if($start =='last_2_hours'){
            $from = date('Y-m-d H:', strtotime('-2 hours')).'00:00';
            $to = date('Y-m-d H:').'00:00';
        }else{
            $from = date('Y-m-d H:', strtotime($start)).'00:00';
            if(empty($end)){
                $to = date('Y-m-d H:').'00:00';
            }else{
                $to = date('Y-m-d ', strtotime($end)).'23:00:00';
            }
        }

        $this->utils->debug_log('generate_player_report_hourly from to', $from, $to);
		//if is super
        if($this->utils->isSuperModeOnMDB()){
        	$this->utils->debug_log('is super site');
			return $this->merge_reports_to_super('player_report_hourly', $from, $to);
		}

        if(strtotime($from) < strtotime('2015-01-01') /*---minimum date---*/){
            $this->utils->debug_log('Invalid, minimum date 2015-01-01');
        }
        elseif(strtotime($from) <= strtotime($to)){
            if($username == '_null'){
                $player_id = null;
            }else{
                $player_id = $this->player_model->getPlayerIdByUsername($username);
            }

            $this->load->model(['report_model']);
            $success=$this->report_model->generate_player_report_hourly($from, $to, $player_id);

            if(!$success){
            	$this->utils->error_log('generate_player_report_hourly failed', $from, $to);
			}else{
	            $this->utils->debug_log('generate_player_report_hourly', $from, $to, $success);
			}
        }
        else{
            $this->utils->debug_log('Invalid dates!');
        }

    }

	public function generate_summary2_report_daily($start='last_2_days', $end=null){

        $lock_rebuild_reports_range= $this->utils->getConfig('lock_rebuild_reports_range');

        if(!empty($lock_rebuild_reports_range)){
            if($start =='last_2_days'){
                $start = date('Y-m-d', strtotime('yesterday'));
            }
            $from_day = $this->utils->formatDateForMysql(new DateTime($start));
            $to_day = $this->utils->formatDateForMysql(new DateTime($end));
            if($this->utils->isRebuildReportDateLocked($lock_rebuild_reports_range,$from_day,$to_day,$rlt)){
                $this->utils->error_log(sprintf(lang("Regenarate All Report has lock - should not be equal or older than  %s  "),$rlt['cutoff_day']));
                return;
            }

        }

        $from = $to = date('Y-m-d');
        if($start =='last_2_days'){
            $from = date('Y-m-d', strtotime('yesterday'));
            $to = date('Y-m-d');
        }
        else{
            $from = date('Y-m-d', strtotime($start));
            if(empty($end)){
                $to = date('Y-m-d');
            }else{
                $to = date('Y-m-d', strtotime($end));
            }
        }

		if(!empty($start) && !empty($end)){
			$from = date('Y-m-d', strtotime($start));
			$to = date('Y-m-d', strtotime($end));
		}

		$this->load->model(['report_model']);

        $this->utils->debug_log('date from to', $from, $to);


		//if is super
		if($this->utils->isSuperModeOnMDB()){
			$this->utils->debug_log('is super site');
			return $this->merge_reports_to_super('summary2_report', $from, $to);
		}

        if(strtotime($from) < strtotime('2015-01-01') /*---minimum date---*/){
            $this->utils->debug_log('Invalid, minimum date 2015-01-01');
        }
        elseif(strtotime($from) <= strtotime($to)){

            $success=$this->report_model->generate_summary2_report_daily($from, $to);
            $this->utils->debug_log('generate_summary2_report_daily', $from, $to, $success);
        }
        else{
            $this->utils->debug_log('Invalid dates!');
        }
	}

	public function generate_oneworks_report_daily($date = null){
		if(!$this->utils->isEnabledFeature('enabled_oneworks_game_report')){
			$this->utils->debug_log('feature enabled_oneworks_game_report disabled');
			return;
		}
		if(!$date){
			$date = date('Y-m-d', strtotime('today'));
		}
		$this->load->model(['report_model']);
		$success=$this->report_model->generate_oneworks_report_daily($date);
		$this->utils->debug_log('generate_oneworks_report_daily', $date, $success);
	}

    public function generate_oneworks_report_by_date_range($date_from, $date_to){
        $date = $date_from;
        $end_date = $date_to;
        while (strtotime($date) <= strtotime($end_date)) {
            $this->generate_oneworks_report_daily($date);
            $date = date ("Y-m-d", strtotime("+1 day", strtotime($date)));
        }
        $this->utils->debug_log('generate_oneworks_report_by_date_range', $date_from ,$date_to);
    }

    public function generate_afb88_report_daily($date = null){
        if(!$this->utils->isEnabledFeature('enabled_afb88_sports_game_report')){
            $this->utils->debug_log('feature enabled_afb88_sports_game_report disabled');
            return;
        }
        if(!$date){
            $date = date('Y-m-d', strtotime('today'));
        }
        $this->load->model(['report_model']);
        $success=$this->report_model->generate_afb88_report_daily($date);
        $this->utils->debug_log('generate_afb88_report_daily', $date, $success);
    }

    public function generate_afb88_report_by_date_range($date_from, $date_to){
        $date = $date_from;
        $end_date = $date_to;
        while (strtotime($date) <= strtotime($end_date)) {
            $this->generate_afb88_report_daily($date);
            $date = date ("Y-m-d", strtotime("+1 day", strtotime($date)));
        }
        $this->utils->debug_log('generate_afb88_report_by_date_range', $date_from ,$date_to);
    }

    public function generate_kingrich_summary_report_hourly($date_from=null, $date_to=null){
        $this->load->model(['report_model']);
        $success = $this->report_model->generate_kingrich_summary_report_hourly($date_from, $date_to);
        //$success = true;
        $this->utils->debug_log('generate_kingrich_summary_report_hourly logs success',$success);
    }

    //===super report==========================================
    public function merge_reports_to_super($report_type, $from, $to){
        if(!$this->utils->isEnabledMDB()){
            return false;
        }
        $result=null;
		$this->utils->debug_log('merge_reports_to_super:'.$report_type, $from, $to);
		$this->load->model(['multiple_db_model']);
		switch ($report_type){
			case 'summary2_report':
				$result=$this->multiple_db_model->syncSummary2Report($from, $to);
                $this->utils->info_log('syncSummary2Report result', $result);
				break;
			case 'player_report_hourly':
				$result=$this->multiple_db_model->syncPlayerReportHourly($from, $to);
                $this->utils->info_log('syncPlayerReportHourly result', $result);
				break;
            case 'game_report_hourly':
                $result=$this->multiple_db_model->syncGameReportHourly($from, $to);
                $this->utils->info_log('syncGameReportHourly result', $result);
                break;
            case 'payment_report_daily':
                $result=$this->multiple_db_model->syncPaymentReportDaily($from, $to);
                $this->utils->info_log('syncPaymentReportDaily result', $result);
                break;
            case 'cashback_report_daily':
                $result=$this->multiple_db_model->syncCashbackReportDaily($from, $to);
                $this->utils->info_log('syncCashbackReportDaily result', $result);
                break;
            case 'promotion_report_details':
                $result=$this->multiple_db_model->syncPromotionReportDetails($from, $to);
                $this->utils->info_log('syncPromotionReportDetails result', $result);
                break;
            case 'total_player_game_day':
                $result=$this->multiple_db_model->syncTotalPlayerGameDay($from, $to);
                $this->utils->info_log('syncTotalPlayerGameDay result', $result);
                break;
            case 'summary_game_total_bet':
                $result=$this->multiple_db_model->syncSummaryGameTotalBet($from, $to);
                $this->utils->info_log('syncSummaryGameTotalBet result', $result);
                break;
            case 'summary_game_total_bet_daily':
                $result=$this->multiple_db_model->syncSummary_game_total_bet_daily($from, $to);
                $this->utils->info_log('summary_game_total_bet_daily result', $result);
                break;
		}

        return $result;
    }
    //===super report==========================================

    //This is for Kingrich PagCor Compliance.
    public function generate_kingrich_send_data_schedule(){
        $this->load->model(['report_model']);
        $success = $this->report_model->generate_kingrich_send_data_schedule();
        //$success = true;
        $this->utils->debug_log('generate_kingrich_send_data_schedule logs success',$success);
    }

    public function generate_player_simple_report($fromDateStr, $toDateStr, $playerId='_null'){
        $from=new DateTime($fromDateStr);
        $to=new DateTime($toDateStr);

        if($playerId=='_null'){
            $playerId=null;
        }
        $this->load->model(['total_player_game_day']);
        $cntPlayerReport=0;
        //lock and trans
        $this->lockAndTrans(Utils::LOCK_ACTION_GENERATE_SIMPLE_PLAYER_GAME_REPORT, 0,
                function() use(&$cntPlayerReport, $from, $to, $playerId){
            $cntPlayerReport=$this->total_player_game_day->syncSimplePlayerGameReportDaily($from, $to, $playerId);
            return true;
        });
        // $this->utils->printLastSQL();
        $this->utils->debug_log('cntPlayerReport', $cntPlayerReport, $this->db->last_query(), $this->db->getOgTargetDB());

    }

     public function generate_vr_report_daily($date = null){
        if(!$this->utils->isEnabledFeature('enabled_vr_game_report')){
            $this->utils->debug_log('feature enabled_vr_game_report disabled');
            return;
        }
        if(!$date){
            $date = date('Y-m-d', strtotime('today'));
        }
        $this->load->model(['report_model']);
        $success=$this->report_model->generate_vr_report_daily($date);
        $this->utils->debug_log('getVRDataForReport', $date, $success);
    }

    public function generate_vr_report_by_date_range($date_from, $date_to){
        $date = $date_from;
        $end_date = $date_to;
        while (strtotime($date) <= strtotime($end_date)) {
            $this->generate_vr_report_daily($date);
            $date = date ("Y-m-d", strtotime("+1 day", strtotime($date)));
        }
        $this->utils->debug_log('generate_vr_report_by_date_range', $date_from ,$date_to);
    }

    /*
     * Command Format:
     *
     *     MDB:
     *         bash ./command_mdb_noroot.sh <db name> <function name> [datetime_from] [datetime_to]
     *         bash ./command_mdb_noroot.sh idr generate_agency_agent_report '2019-09-24 23:00:00' '2019-09-24 23:59:59'
     *
     *     Single DB:
     *         bash ./command.sh generate_agency_agent_report [datetime_from] [datetime_to]
     *         bash ./command.sh generate_agency_agent_report '2019-08-27 15:00:00' '2019-08-27 15:59:59'
     */
    public function generate_agency_agent_report($datetime_from = null, $datetime_to = null)
    {
        $use_agency_agent_reports = $this->utils->getConfig('use_agency_agent_reports');
        if(!$use_agency_agent_reports){
            $this->utils->debug_log('use_agency_agent_reports was set to false');
            return;
        }
        $interval = new DateInterval('PT1H');
        if(!$datetime_from || !$datetime_to){
            $dateTimeFrom = new DateTime($this->utils->getNowForMysql());
            $dateTimeFrom->sub($interval);
            $dateTimeTo = new DateTime($this->utils->getNowForMysql());
            $dateTimeTo->sub($interval);
        }else{
            $dateTimeFrom = new DateTime($datetime_from);
            $dateTimeTo = new DateTime($datetime_to);
        }

        $this->load->model(['agency_agent_report']);
        $success=$this->agency_agent_report->sync($dateTimeFrom,$dateTimeTo);
        $this->utils->debug_log('Sync DateRange:', $dateTimeFrom->format("Y-m-d H:i:s")."-".$dateTimeTo->format("Y-m-d H:i:s"), $success);
    }

    /*
     * Command Format:
     *
     *     MDB:
     *         bash ./command_mdb_noroot.sh <db name> <function name> [datetime_from] [datetime_to]
     *         bash ./command_mdb_noroot.sh idr generate_transactions_daily_summary_report '2019-04-01' '2019-04-30'
     *
     *     Single DB:
     *         bash ./command.sh generate_transactions_daily_summary_report [datetime_from] [datetime_to]
     *         bash ./command.sh generate_transactions_daily_summary_report '2019-04-01' '2019-04-30'
     */
    public function generate_transactions_daily_summary_report()
    {
        if(!$this->utils->isEnabledFeature('enabled_transactions_daily_summary_report')){
            $this->utils->debug_log('feature transactions_daily_summary_report disabled');
            return;
        }

        $this->load->model(['player_model','transactions','wallet_model','total_player_game_day','operatorglobalsettings']);

        $todayDateYmdHis = $this->utils->getNowForMysql();
        $todayDateYmd = new DateTime($todayDateYmdHis);
        $summaryRecordDate = $todayDateYmd->format('Y-m-d');

        $initialBalanceTodayDate = $todayDateYmd->modify("-1 day");
        $initialBalanceTodayDateYmd = $initialBalanceTodayDate->format('Y-m-d');

        $from_time = $this->operatorglobalsettings->getSettingValue('transactions_daily_summary_report_day_starttime')?:"00";

        $dateFrom = $summaryRecordDate." ".$from_time.":00:00";
        $dateToDT = new DateTime($dateFrom);
        $dateToDT->add(new DateInterval('PT23H'));
        $dateTo = $dateToDT->format('Y-m-d H:')."59:59";

        $this->utils->debug_log('[Initiate] transactions Daily Summary Report, Sync DateRange:', $dateFrom."-".$dateTo);

        $tempPlayerTable = $this->createTempTable("player");
        try{
            $playerQueryRowsCnt = 500;
            $loopCnt = $this->getPageLoopCntFromTotalPlayerCnt($tempPlayerTable,$playerQueryRowsCnt);

            $transSummaryReportTable = "transactions_daily_summary_report";
            $this->db->where('sync_date', $dateFrom);
            $this->transactions->runRealDelete($transSummaryReportTable, $this->db);

            $pageCnt = 0;
            while ($pageCnt < $loopCnt)
            {
                $offset = $pageCnt * $playerQueryRowsCnt;
                $this->db->select("playerId,username")->from($tempPlayerTable)->where('deleted_at IS NULL');
                $this->db->limit($playerQueryRowsCnt,$offset);
                $rows=$this->player_model->runMultipleRowArray();

                if(!empty($rows))
                {
                    foreach ($rows as $val)
                    {
                        $playerId = $val['playerId'];
                        $data = $this->transactions->getPlayerTotalSummaryReport($playerId,$dateFrom,$dateTo);

                        $playerGameTotal = $this->total_player_game_day->getPlayerTotalBetWinLoss($playerId,$dateFrom,$dateTo);

                        $bigwalletDetails = $this->wallet_model->getBalanceDetails($playerId)['big_wallet'];
                        $latestTotalBalance = $this->wallet_model->getBalanceDetails($playerId)['total_balance'];

                        # Get latest balance record of the player
                        $latestBalanceRecords = json_encode(['total_balance'=>$latestTotalBalance,'big_wallet'=>$bigwalletDetails]);

                        $totalDeposit = $data['total_deposit'];
                        $totalAddBonus = $data['total_add_bonus'];
                        $totalAddCashback = $data['total_add_cashback'];
                        $totalManualAddBalance = $data['total_manual_add_balance'];
                        $totalManualSubtractBalance = $data['total_manual_subtract_balance'];
                        $totalWithdrawal = $data['total_withdrawal'];
                        $totalPlayerReferralBonus = $data['total_player_refer_bonus'];
                        $totalSubtractBonus = $data['total_subtract_bonus'];
                        $totalVipBonus = $data['total_vip_bonus'];
                        $totalWin = $playerGameTotal['total_win'];
                        $totalLoss = $playerGameTotal['total_loss'];

                        $totalInitialBalance = 0;
                        $initialBalanceRecord = $this->getPlayerTodayInitialBalanceFromSummaryReport($playerId,$initialBalanceTodayDateYmd);

                        if($initialBalanceRecord){
                            $totalInitialBalance = json_decode($initialBalanceRecord,true)['total_balance'];
                        }

                        $endBalance = ($totalInitialBalance + $totalDeposit + $totalAddBonus + $totalAddCashback + $totalPlayerReferralBonus + $totalVipBonus + $totalManualAddBalance) - ($totalWithdrawal + $totalSubtractBonus + $totalManualSubtractBalance + ($totalWin - $totalLoss));

                        $dailySummaryReportData = [
                            "player_id" => $playerId,
                            "username" => $val['username'],
                            "sync_date" => $summaryRecordDate,
                            "total_initial_balance" => $initialBalanceRecord,
                            "total_deposit" => $totalDeposit,
                            "total_manual_add_balance" => $totalManualAddBalance,
                            "total_withdrawal" => $totalWithdrawal,
                            "total_manual_subtract_balance" => $totalManualSubtractBalance,
                            "total_add_bonus" => $totalAddBonus,
                            "total_referral_bonus" => $totalPlayerReferralBonus,
                            "total_subtract_bonus" => $totalSubtractBonus,
                            "total_vip_bonus" => $totalVipBonus,
                            "total_add_cashback" => $totalAddCashback,
                            "total_win" => $totalWin,
                            "total_loss" => $totalLoss,
                            "end_balance" => $endBalance,
                            "latest_balance_record" => $latestBalanceRecords,
                            "balance_validation" => $endBalance == $latestTotalBalance ? "Tallied" : "Not Tallied",
                            "updated_at"=>$this->utils->getNowForMysql()
                          ];

                        $result = $this->transactions->insertData($transSummaryReportTable,$dailySummaryReportData);
                        $this->utils->debug_log('[Insert] transactions Daily Summary Report, Sync DateRange:', $dateFrom."-".$dateTo, $result);
                    }
                }

                $pageCnt++;
            }
        }
        finally{
            $this->db->query("DROP TABLE IF EXISTS $tempPlayerTable;");
        }
    }

    private function createTempTable($tableName){
        $dateString = date("YmdHis");
        $tempPlayerTable = "players_temp_".$dateString;
        $this->db->query("CREATE TEMPORARY TABLE $tempPlayerTable LIKE $tableName");
        $this->db->query("INSERT $tempPlayerTable SELECT * FROM $tableName;");
        $this->utils->debug_log('[CreateTempTable] transactions Daily Summary Report, From:', $tableName." To: ".$tempPlayerTable);
        return $tempPlayerTable;
    }

    private function getPageLoopCntFromTotalPlayerCnt($tempPlayerTable,$playerQueryRowsCnt=500){
        $this->load->model('player_model');
        $playerCntSql = <<<EOF
                SELECT COUNT(`playerId`) as player_count FROM `$tempPlayerTable` WHERE `deleted_at` IS NULL
EOF;
        $totalPlayerCnt = $this->player_model->runRawSelectSQL($playerCntSql)[0]->player_count;

        $loopCnt = ceil($totalPlayerCnt/$playerQueryRowsCnt);
        return $loopCnt;
    }


    /*
     * Command Format:
     *
     *     MDB:
     *         bash ./command_mdb_noroot.sh <db name> <function name> [datetime_from] [datetime_to]
     *         bash ./command_mdb_noroot.sh idr update_daily_transaction_summary_report_records '2019-04-01' '2019-04-30'
     *
     *     Single DB:
     *         bash ./command.sh update_daily_transaction_summary_report_records [datetime_from] [datetime_to]
     *         bash ./command.sh update_daily_transaction_summary_report_records '2019-04-01' '2019-04-30'
     */
    public function update_daily_transaction_summary_report_records($dateFrom=null,$toDateRange=null){
        $from_time = $this->operatorglobalsettings->getSettingValue('transactions_daily_summary_report_day_starttime')?:"00";

        if($dateFrom&&$toDateRange)
        {
            $beginDate = new DateTime($dateFrom);
            $endDate = new DateTime($toDateRange);
            $endDate = $endDate->modify('+1 day');

            $interval = new DateInterval('P1D');
            $daterange = new DatePeriod($beginDate, $interval ,$endDate);

            foreach($daterange as $date){
                $dateFromStr = $date->format("Y-m-d ").$from_time.":00:00";
                $dateToDT = new DateTime($dateFromStr);
                $dateToDT->add(new DateInterval('PT23H'));
                $dateToStr = $dateToDT->format('Y-m-d H:')."59:59";
                $this->executeUpdateDailyTransactionSummaryReportRecords($dateFromStr,$dateToStr);
            }
        }else{
            $todayDateYmdHis = $this->utils->getNowForMysql();
            $todayDateYmd = new DateTime($todayDateYmdHis);
            $todayDateStr = $todayDateYmd->format('Y-m-d');

            $dateFrom = $todayDateStr." ".$from_time.":00:00";
            $dateToDT = new DateTime($dateFrom);
            $dateToDT->add(new DateInterval('PT23H'));
            $dateTo = $dateToDT->format('Y-m-d H:')."59:59";

            $this->executeUpdateDailyTransactionSummaryReportRecords($dateFrom,$dateTo);
        }
    }

    private function executeUpdateDailyTransactionSummaryReportRecords($dateFrom,$dateTo)
    {
        $tempPlayerTable = $this->createTempTable("player");
        try{
            $playerQueryRowsCnt = 500;
            $loopCnt = $this->getPageLoopCntFromTotalPlayerCnt($tempPlayerTable,$playerQueryRowsCnt);

            $this->load->model(['transactions','total_player_game_day']);
            $pageCnt = 0;
            while ($pageCnt < $loopCnt)
            {
                $offset = $pageCnt * $playerQueryRowsCnt;
                $this->db->select("playerId,username")->from($tempPlayerTable)->where('deleted_at IS NULL');
                $this->db->limit($playerQueryRowsCnt,$offset);
                $rows = $this->player_model->runMultipleRowArray();

                if(!empty($rows))
                {
                    foreach ($rows as $val)
                    {
                        $playerId = $val['playerId'];
                        $playerGameTotal = $this->total_player_game_day->getPlayerTotalBetWinLoss($playerId,$dateFrom,$dateTo);

                        $data = $this->transactions->getPlayerTotalSummaryReport($playerId,$dateFrom,$dateTo);

                        $totalDeposit = $data['total_deposit'];
                        $totalAddBonus = $data['total_add_bonus'];
                        $totalAddCashback = $data['total_add_cashback'];
                        $totalManualAddBalance = $data['total_manual_add_balance'];
                        $totalManualSubtractBalance = $data['total_manual_subtract_balance'];
                        $totalWithdrawal = $data['total_withdrawal'];
                        $totalPlayerReferralBonus = $data['total_player_refer_bonus'];
                        $totalSubtractBonus = $data['total_subtract_bonus'];
                        $totalVipBonus = $data['total_vip_bonus'];
                        $totalWin = $playerGameTotal['total_win'];
                        $totalLoss = $playerGameTotal['total_loss'];

                        $syncDate = new DateTime($dateFrom);
                        $dailySummaryReportData = [
                            "player_id" => $playerId,
                            "username" => $val['username'],
                            "sync_date" => $syncDate->format("Y-m-d"),
                            "total_deposit" => $totalDeposit,
                            "total_manual_add_balance" => $totalManualAddBalance,
                            "total_withdrawal" => $totalWithdrawal,
                            "total_manual_subtract_balance" => $totalManualSubtractBalance,
                            "total_add_bonus" => $totalAddBonus,
                            "total_referral_bonus" => $totalPlayerReferralBonus,
                            "total_subtract_bonus" => $totalSubtractBonus,
                            "total_vip_bonus" => $totalVipBonus,
                            "total_add_cashback" => $totalAddCashback,
                            "total_win" => $totalWin,
                            "total_loss" => $totalLoss,
                            "updated_at"=>$this->utils->getNowForMysql()
                          ];

                        $isRecordExists = $this->checkRecordExistsInTransactionsDailyReport($playerId,$syncDate->format("Y-m-d"));
                         if($isRecordExists){
                             $this->db->where(['player_id'=>$playerId,'sync_date'=>$syncDate->format("Y-m-d")])->set($dailySummaryReportData);
                            $result=$this->transactions->runAnyUpdate('transactions_daily_summary_report');
                            $this->utils->debug_log('[Update] transactions Daily Summary Report, Sync DateRange:', $dateFrom."-".$dateTo, $result);
                         }
                    }
                }
                $pageCnt++;
            }
        }finally{
            $this->db->query("DROP TABLE IF EXISTS $tempPlayerTable;");
        }
    }

    private function checkRecordExistsInTransactionsDailyReport($playerId,$syncDate)
    {
        $this->load->model('transactions');
        $this->db->where(['player_id'=>$playerId,'sync_date'=>$syncDate])->from('transactions_daily_summary_report');
        return $this->transactions->runExistsResult();
    }

    private function getPlayerTodayInitialBalanceFromSummaryReport($playerId,$syncDate)
    {
        $this->load->model("transactions");
        $this->db->select("latest_balance_record")->from("transactions_daily_summary_report");
        $this->db->where("player_id",$playerId);
        $this->db->where("sync_date",$syncDate);
        return $this->transactions->runOneRowOneField('latest_balance_record')?:0;
    }

    /**
     * generate_quickfire_report
     *
     * @param  string  $dateTimeFromStr
     * @param  string  $dateTimeToStr
     *
     */
    public function generate_quickfire_report($dateTimeFromStr = null, $dateTimeToStr = null){
        if(!$this->utils->isEnabledFeature('enabled_quickfire_game_report')){
            $this->utils->debug_log('feature enabled_quickfire_game_report disabled');
            return;
        }
        if(!$dateTimeFromStr && !$dateTimeToStr){
            $dateTimeFromStr =  $dateTimeToStr = $this->utils->getNowForMysql();
        }

        $dateTimeFrom = new \DateTime($dateTimeFromStr);
        $dateTimeTo = new \DateTime($dateTimeToStr);

        $api = $this->utils->loadExternalSystemLibObject(MG_QUICKFIRE_API);

        $token = random_string('unique');
        $api->saveSyncInfoByToken($token, $dateTimeFrom, $dateTimeTo, null, null, null);
        $result = $api->syncGameRecordsThroughExport($token);
        $this->utils->debug_log('generate_oneworks_report_daily', $dateTimeFromStr, $dateTimeToStr, $result);
    }

    public function generate_games_report_timezone($dateFromStr = null, $dateToStr = null, $platformId = "_null", $playerId = "_null"){

        if(!$dateFromStr && !$dateToStr){
            $dateFromStr =  $dateToStr = $this->utils->getTodayForMysql();
        }
        $dateFrom = new \DateTime($dateFromStr);
        $dateTo = new \DateTime($dateToStr);
        $token = random_string('unique');

        if($playerId == "_null"){
            $playerId = null;
        }

        if($platformId == "_null"){
            $platformId = null;
        }

        $extra = array(
            "playerId" => $playerId
        );
        $allResult = [];

        if($platformId){
            $api = $this->utils->loadExternalSystemLibObject($platformId);
            $api->saveSyncInfoByToken($token, $dateFrom, $dateTo, null, null, null, $extra);
            $result = $api->syncMergeToGameReports($token);
            $this->utils->debug_log('generate_games_report_timezone', $platformId, $dateFromStr, $dateToStr, $result);
            $allResult[$platformId] = $result;
        } else {
            $games_with_report_timezone = $this->utils->getConfig('games_with_report_timezone');
            $this->utils->debug_log('games_with_report_timezone', $games_with_report_timezone);
            if(!empty($games_with_report_timezone)){
                foreach ($games_with_report_timezone as $gamePlatformId) {
                    $api = $this->utils->loadExternalSystemLibObject($gamePlatformId);
                    $api->saveSyncInfoByToken($token, $dateFrom, $dateTo, null, null, null);
                    $result = $api->syncMergeToGameReports($token);
                    $this->utils->debug_log('generate_games_report_timezone', $gamePlatformId, $dateFromStr, $dateToStr, $result);
                    $allResult[$gamePlatformId] = $result;
                }
            }
        }
        return $allResult;

    }

    public function sync_total_player_game_minute_additional($from, $to, $playerId = "_null", $gamePlatformId= "_null"){
        if($playerId == "_null"){
            $playerId = null;
        }
        if($gamePlatformId == "_null"){
            $gamePlatformId = null;
        }

        $this->load->model('total_player_game_minute');
        $result = [];
        $this->utils->loopDateTimeStartEnd($from, $to,'+30 minutes', function($from, $to) use (&$result, $playerId, $gamePlatformId) {
            $fromStr=$from->format('Y-m-d H:i:s');
            $toStr=$to->format('Y-m-d H:i:s');
            $result[$fromStr.$toStr] = $this->total_player_game_minute->sync_total_player_game_minute_additional($from, $to, $playerId, $gamePlatformId);
            return true;
        });
        $this->utils->debug_log('sync_total_player_game_minute_additional result', $result);
    }

    public function generate_summary2_report_monthly($startMonth = null, $endMonth = null){

        if(empty($startMonth)){
            $startMonth = date('Y-m', strtotime('-1 month'));
        }

        if(empty($endMonth)){
            $endMonth = date('Y-m');
        }

        $this->load->model(['report_model']);
        $success=$this->report_model->generate_summary2_report_monthly($startMonth, $endMonth);
        $this->utils->debug_log('generate_summary2_report_monthly', $startMonth, $endMonth, $success);
    }

    public function generatate_game_tournament_winners($dateTimeFromStr = "_null", $dateTimeToStr = "_null", $sleepTimeSeconds = 3){
        if($dateTimeFromStr == "_null"){
            $dateTimeFromStr = $this->utils->getNowForMysql();
        }
        if($dateTimeToStr == "_null"){
            $dateTimeToStr = $this->utils->getNowForMysql();
        }

        $dateTimeFrom = new \DateTime($dateTimeFromStr);
        $dateTimeTo = new \DateTime($dateTimeToStr);
        $results = array();

        $games = $this->utils->getConfig('games_with_tournament_feature');
        if(!empty($games)){
            foreach ($games as $key => $gameId) {
                $api = $this->utils->loadExternalSystemLibObject($gameId);
                $token = random_string('unique');
                $api->saveSyncInfoByToken($token, $dateTimeFrom, $dateTimeTo);
                $results[] = $api->syncTournamentsWinners($token);
               sleep($sleepTimeSeconds);
            }
        }
        $this->utils->debug_log('end sync--------------------', $results);
    }

    public function generateSummaryGameTotalBet(){
        $current_date = $this->utils->getTodayForMysql();
        if($this->utils->isSuperModeOnMDB()){
            $this->utils->debug_log('is super site');
            return $this->merge_reports_to_super('summary_game_total_bet', $current_date, $current_date);
        }

        $this->load->model(array('total_player_game_day', 'transactions', 'game_description_model'));
        $rows = $this->CI->total_player_game_day->getTopGamesByPlayerBetAndCount();
        $count = 0;
        if(!empty($rows)){
            $this->db->empty_table('summary_game_total_bet');
            foreach ($rows as $key => $row) {
                $row['virtual_game_id'] = $row['game_platform_id']."-".$row['external_game_id'];
                $percentage = 50;
                $row['total_half_percentage'] = ($row['total_bets'] * ($percentage / 100)) + ($row['total_players'] * ($percentage / 100));
                $row['currency_key'] = strtoupper($this->utils->getActiveTargetDB());
                $row['api_date'] = $current_date;
                $row['unique_id'] = $row['currency_key']. "-".$row['virtual_game_id'];
                $success = $this->transactions->insertData('summary_game_total_bet', $row, $this->db);
                if($success){
                    $count++;
                }
            }
        }
        $this->utils->debug_log('generateSummaryGameTotalBet total insert count', $count);
    }

    public function generateSummaryGameTotalBetDaily($date = null){
        if(empty($date)){
            $date = $this->utils->getTodayForMysql();
        }

        if($this->utils->isEnabledMDB()){
            if($this->utils->isSuperModeOnMDB()){
                $this->utils->debug_log('is super site');
                return $this->merge_reports_to_super('summary_game_total_bet_daily', $date, $date);
            }
        }

        $this->load->model(array('total_player_game_day', 'transactions'));
        $rows = $this->CI->total_player_game_day->queryTopGamesByPlayerBetAndCountDaily($date);

        $count = 0;
        if(!empty($rows)){
            $this ->db->where('api_date', $date);
            $this->db->delete('summary_game_total_bet_daily');

            foreach ($rows as $key => $row) {
                $row['virtual_game_id'] = $row['game_platform_id']."-".$row['external_game_id'];
                $percentage = 50;
                $row['total_half_percentage'] = ($row['total_bets'] * ($percentage / 100)) + ($row['total_players'] * ($percentage / 100));
                $row['currency_key'] = strtoupper($this->utils->getActiveTargetDB());
                $row['unique_id'] = "{$row['currency_key']}-{$row['virtual_game_id']}-{$row['api_date']}";
                $success = $this->transactions->insertData('summary_game_total_bet_daily', $row, $this->db);
                if($success){
                    $count++;
                }
            }
        }
        $this->utils->debug_log('generateSummaryGameTotalBetDaily total insert count', $count);
        return $count;
    }

    public function generateGamelogsExportLinks($dateFrom = null, $dateTo = null){
        $this->load->model(['report_model']);
        #basis do_remote_export_csv_job

        if(empty($dateFrom)){
            $dateFrom = new DateTime();
            $dateFrom->modify('-1 hour');
            $dateFrom = $dateFrom->format('Y-m-d H:00:00');
        }

        if(empty($dateTo)){
            $dateTo = new DateTime();
            $dateTo = $dateTo->format('Y-m-d H:59:59');
        }

        $this->utils->loopDateTimeStartEnd($dateFrom, $dateTo,'+60 minutes',function($dateFrom, $dateTo) {
            $from = $dateFrom->format("Y-m-d H:00:00");
            $to = $dateFrom->format('Y-m-d H:59:59');
            $dateHour = $dateFrom->format("YmdH");
            $date = $dateFrom->format("Ymd");

            $query = $this->db->get_where('game_logs_export_hour', ['date_hour' => $dateHour]);
            if ($query->num_rows() > 0) {
                $rows = $query->result();

                // delete
                $this->db->where('date_hour', $dateHour);
                $this->db->delete('game_logs_export_hour');

                if ($this->db->affected_rows() > 0) {
                    $this->utils->info_log('game_logs_export_hour delete success.', $dateHour);
                } else {
                    $this->utils->info_log('game_logs_export_hour delete failed.', $dateHour);
                }

                foreach ($rows as $row) {
                    $filename = $row->file_name;
                    $this->utils->info_log('game_logs_export_hour file_name for delete', $filename);
                    $filepath = realpath(dirname(__FILE__) . "/../../../../../");
                    $filepath = $filepath."/pub/sharing_upload/remote_reports/{$filename}.csv";
                    $this->utils->info_log('game_logs_export_hour filepath for delete', $filepath);
                    unlink($filepath);
                }
            }

            $filename = $this->report_model->queryGameHistoryForExport($from, $to);
            if(empty($filename)){
                $this->utils->info_log('queryGameHistoryForExport is empty');
                return true;
            }

            $rlt=['success'=>true, 'filename'=>$filename.'.csv'];
            $this->utils->info_log('generateGamelogsExportLinks rlt', $from, $to, $rlt);

            $success = $this->transactions->insertData('game_logs_export_hour', ['date' => $date,'date_hour' => $dateHour, 'file_name' => $filename], $this->db);
            $this->utils->info_log('game_logs_export_hour insert rlt', $success);
            return true;
        });
    }

    public function deleteOldGamelogsExportedFiles($date = null){
        $days = $this->utils->getConfig('days_old_for_delete_gamelogs_exported_files');
        if(empty($date)){
            $date = new DateTime();
            $date->modify('-'.$days.' days');

            $date = $date->format('Ymd');
        } else {
            $date = DateTime::createFromFormat('Y-m-d', $date)->format('Ymd');
        }

        $query = $this->db->get_where('game_logs_export_hour', ['date' => $date]);
        if ($query->num_rows() > 0) {
            $rows = $query->result();

            // delete
            $this->db->where('date', $date);
            $this->db->delete('game_logs_export_hour');

            if ($this->db->affected_rows() > 0) {
                $this->utils->info_log('game_logs_export_hour delete success.', $date);
            } else {
                $this->utils->info_log('game_logs_export_hour delete failed.', $date);
            }

            foreach ($rows as $row) {
                $filename = $row->file_name;
                $this->utils->info_log('game_logs_export_hour file_name for delete', $filename);
                $filepath = realpath(dirname(__FILE__) . "/../../../../../");
                $filepath = $filepath."/pub/sharing_upload/remote_reports/{$filename}.csv";
                $this->utils->info_log('game_logs_export_hour filepath for delete', $filepath);
                unlink($filepath);
            }
        }
    }

    public function sync_total_game_transaction_yearly($year = null) {
        // Ensure the year is valid
        if (empty($year) || !is_numeric($year) || $year < 1000 || $year > 9999) {
            $this->utils->debug_log('Invalid year parameter passed', $year);
            return false;
        }

        $currentYear = (int)date('Y');  // Current year
        $currentMonth = (int)date('m'); // Current month (1-12)
        $total_count = 0;

        if ($year == $currentYear) {
            for ($month = 1; $month <= $currentMonth; $month++) {
                $yearMonth = $year . str_pad($month, 2, '0', STR_PAD_LEFT);
                $monthly_count = $this->sync_total_game_transaction_monthly($yearMonth);
                if ($monthly_count !== false) {
                    $total_count += $monthly_count;  // Increment total count by the monthly count
                }
            }
        } else {
            for ($month = 1; $month <= 12; $month++) {
                $yearMonth = $year . str_pad($month, 2, '0', STR_PAD_LEFT);
                $monthly_count = $this->sync_total_game_transaction_monthly($yearMonth);
                if ($monthly_count !== false) {
                    $total_count += $monthly_count;  // Increment total count by the monthly count
                }
            }
        }

        $this->utils->debug_log('sync_total_game_transaction_yearly total count = ', $total_count, $year);
        return $total_count;
    }


    public function sync_total_game_transaction_monthly($yearMonth = null) {
        if (empty($yearMonth)) {
            $yearMonth = $this->utils->getThisYearMonth();
        }

        if (!$this->is_valid_year_month($yearMonth)) {
            $this->utils->debug_log('invalid year month param passed', $yearMonth);
            return false;
        }

        $this->load->model(['transactions', 'total_player_game_month']);
        $player_transactions = $this->transactions->get_player_transaction_summary_monthly($yearMonth);
        $player_bets = $this->total_player_game_month->get_player_bet_summary_monthly($yearMonth);

        $data = $this->merge_player_data($player_transactions, $player_bets);
        $total_count = 0;

        if (!empty($data)) {
            // Chunk the data into batches of 500
            $chunks = array_chunk($data, 500);

            foreach ($chunks as $chunk) {
                // Pass each chunk to the batch processing function
                $success = $this->transactions->replace_or_update_transactions_batch($chunk);
                if ($success) {
                    $total_count += count($chunk);  // Increment count by the number of rows successfully processed
                }
            }
        }

        $this->utils->debug_log('sync_total_game_transaction_monthly total count = ', $total_count, $yearMonth);
        return $total_count;
    }



    private function merge_player_data($player_transactions, $player_bets) {
        // Default values for missing keys
        $default_values = [
            "year_month" => "",
            "unique_id" => "",
            "player_id" => "",
            "player_username" => "",
            "total_amount_deposit" => 0.00,
            "total_amount_withdraw" => 0.00,
            "total_amount_bonus" => 0.00,
            "total_bet_amount" => 0.00,
            "total_net_loss" => 0.00,
        ];

        $merged = [];

        // Combine both arrays
        $all_data = array_merge($player_transactions, $player_bets);
        if(!empty($all_data)){
            foreach ($all_data as $player) {
                $id = $player["unique_id"];

                if (!isset($merged[$id])) {
                    $merged[$id] = $default_values;
                }

                // Merge player data
                $merged[$id] = array_merge($merged[$id], $player);
            }

            return array_values($merged);
        }
        return $merged;
        
    }

    private function is_valid_year_month($yearMonth) {
        if (preg_match('/^\d{6}$/', $yearMonth)) {
            $year = substr($yearMonth, 0, 4);
            $month = substr($yearMonth, 4, 2);

            // Check if the month is valid (01 to 12)
            if ($month >= 1 && $month <= 12) {
                return true;
            }
        }
        return false;
    }
}
