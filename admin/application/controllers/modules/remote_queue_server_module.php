<?php

/**
 * Class remote_queue_server_module
 *
 * General behaviors include :
 * exec remote queue job
 *
 * @category Command Line
 * @version 1.8.10
 * @copyright 2013-2022 tot
 */
trait remote_queue_server_module {

    public function remote_sync_game_logs($data, $params, $db=null){

        if($this->utils->getConfig('disabled_sync_game_logs_on_sbe')){
            $this->utils->error_log('!!!donot allow sync game logs!!!');
            return false;
        }

        $success=true;

        // $params = json_decode($data['full_params'], true);
        $token = $data['token'];

        //convert bool to string

        $toDateTimeStr = $params['toDateTimeStr'];
        $fromDateTimeStr = $params['fromDateTimeStr'];
        $game_api_id = $params['game_api_id'];
        $timelimit = $params['timelimit'];
        $playerName = $params['playerName'];
        $dry_run = $params['dry_run'];
        $merge_only = $params['merge_only'];
        $only_original = $params['only_original'];

        if(empty($game_api_id)){
            $game_api_id=_COMMAND_LINE_NULL;
        }

        $is_blocked=false;
        $writeLogToSharing=true;
        if(!empty($db)){
            $dbName=$db->getOgTargetDB();
        }
        $file_list=[];
        $cmd=$this->utils->generateCommandLine('sync_game_logs', [$dry_run, $fromDateTimeStr, $toDateTimeStr,
            $game_api_id, $timelimit, $playerName, $merge_only, $only_original, $token],
            $is_blocked, $file_list, $dbName, $writeLogToSharing);
        $this->writeLogFileToQueueResult($token, $file_list);
        $success=$this->runCmd($cmd)==0;

        return $success;
    }

    public function remote_email($data, $params, $db=null){

        $this->load->library(array('email_setting'));

        $token = $data['token'];
        // $params = $data['params'];
        $to = $params['to'];
        $from = $params['from'];
        $fromName = $params['fromName'];
        $subject = $params['subject'];
        $body = $params['body'];
        $emailData = array(
            'from' => $from,
            'from_name' => $fromName,
            'subject' => $subject,
            'body' => $body,
        );

        $success = $this->email_setting->sendEmail($to, $emailData);

        if ($success === true) {
            if (!$this->queue_result->updateResult($token, $rlt)) {
                $this->utils->error_log("[ERROR] [do_email_job] to " . $to . ", token:" . $token . " failed: ", $params);
            }
        } else {
            $this->queue_result->failedResult($token, $rlt);
            $this->utils->error_log("[ERROR] [do_email_job] to " . $to . ", token:" . $token . " failed: " , $params);
        }

        return $success;
    }

    public function remote_sms($data, $params, $db=null){
        $this->load->library('sms/sms_sender');

        // $params = json_decode($data['full_params'], true);
        $token = $data['token'];
        $mobileList  = $params['mobileNum'];
        $smsContent = $params['content'];
        $isSuccess = true;
        $smsError  = [];
        $smsSuccess = [];

        foreach ($mobileList as $mobileNum)  {

            $rlt = $this->sms_sender->send($mobileNum, $smsContent);

            if ($rlt != $isSuccess) {
                $isSuccess = false;
            }

            if ($rlt) {
                $smsSuccess[] = [
                    'mobile' => $mobileNum
                ];
            } else {
                $smsError[] = [
                    'mobile' => $mobileNum,
                    'errorMsg' => $this->sms_sender->getLastError()
                ];
            }
        }

        if ($isSuccess) {
            $this->queue_result->appendResult($token, ['send sms result' => $isSuccess], true);
            $this->utils->debug_log('Sms api response success', $mobileList, $smsContent);
        } else {
            $this->queue_result->failedResult($token, ['send sms result' => $smsError, 'success' => $smsSuccess]);
            $this->utils->debug_log('Sms api response error', $mobileList, $smsContent, $smsError);
        }
    }

    public function remote_debug_queue($data, $params, $db=null){

        $token = $data['token'];
        // $params = $data['full_params'];

        $success = !empty($params['trigger_time']);
        $rlt=['trigger_time'=>$params['trigger_time'], 'run_time'=>$this->utils->getNowForMysql()];

        if ($success) {
            if (!$this->queue_result->appendResult($token, $rlt, true)) {
                $this->utils->error_log("[ERROR] [remote_debug_queue], append result failed token:" . $token . " failed: ", $params);
            }
        } else {
            $this->queue_result->appendResult($token, $rlt, false, true);
            $this->utils->error_log("[ERROR] [remote_debug_queue], token:" . $token . " failed: " , $params);
        }

        return $success;
    }

    public function remote_import_players($data, $params, $db=null){

        $token = $data['token'];
        // $params = $data['params'];

        // $success=true;

        // $rlt=[];

        // $files=$params['files'];
        // $importer_formatter=$params['importer_formatter'];
        // $summary=[];
        // $message=null;


        $is_blocked=false;

        if(empty($db)){
             $cmd=$this->utils->generateCommandLine('run_importer_by_queue_token', [$token], $is_blocked);
        }else{
            $dbName=$db->getOgTargetDB();
            $file_list=[];
            $cmd=$this->utils->generateCommandLine('run_importer_by_queue_token', [$token], $is_blocked, $file_list, $dbName);
        }
        $success=$this->runCmd($cmd)==0;

        $rlt=['running'=>$success, 'cmd'=>$cmd];
        $this->queue_result->appendResult($token, $rlt);

        //try load importer
        // $success=$this->player_model->importFromCSV($importer_formatter, $files, $summary, $message);
        // $rlt['summary']=$summary;
        // $rlt['message']=$message;
        // $rlt['importer_formatter']=$importer_formatter;

        // if ($success) {
        //     if (!$this->queue_result->appendResult($token, $rlt, true)) {
        //         $this->utils->error_log("[ERROR] [remote_import_players], append result failed token:" . $token . " failed: ", $params);
        //     }
        // } else {
        //     $this->queue_result->appendResult($token, $rlt, false, true);
        //     $this->utils->error_log("[ERROR] [remote_import_players], token:" . $token . " failed: " , $params);
        // }

        return $success;

    }

    /**
     * Process Pre-Checker For withdrawal (Risk Automation Process)
     *
     * @param [type] $data
     * @param [type] $params
     * @param [type] $db
     * @return void
     */
    public function remote_processPreChecker($data, $params, $db=null){
        $success=true;
        $token = $data['token'];
        // $walletAccountId = $params['walletAccountId'];

        // $dateList=[];
        // $success= true;
        // $error='';
        $is_blocked=false;

        if(empty($db)){
            $cmd=$this->utils->generateCommandLine('processPreCheckerWithToken', [$token], $is_blocked);
        }else{
            $dbName=$db->getOgTargetDB();
            $file_list=[];
            $cmd=$this->utils->generateCommandLine('processPreCheckerWithToken', [$token], $is_blocked, $file_list, $dbName);
        }
       $success=$this->runCmd($cmd)==0;

       return $success;
    }

    /**
     * generate Command Line for send2Insvr4CreateAndApplyBonusMulti function
     *
     * @param array $data
     * @param [type] $params
     * @param [type] $db
     * @return void
     */
    public function remote_send2Insvr4CreateAndApplyBonusMulti($data, $params, $db=null){
        $success=true;
        $token = $data['token'];
        $is_blocked=false;

        if(empty($db)){
            $cmd=$this->utils->generateCommandLine('send2Insvr4CreateAndApplyBonusMultiWithToken', [$token], $is_blocked);
        }else{
            $dbName=$db->getOgTargetDB();
            $file_list=[];
            $cmd=$this->utils->generateCommandLine('send2Insvr4CreateAndApplyBonusMultiWithToken', [$token], $is_blocked, $file_list, $dbName);
        }
       $success=$this->runCmd($cmd)==0;

       return $success;
    } // EOF remote_send2Insvr4CreateAndApplyBonusMulti

    public function remote_rebuild_games_total($data, $params, $db=null){

        $success=true;
        $token = $data['token'];
        $rebuild_hour = $params['rebuild_hour'];
        $rebuild_minute = $params['rebuild_minute'];
        $endDateTimeStr = $params['endDateTimeStr'];
        $fromDateTimeStr = $params['fromDateTimeStr'];
        // $dry_run = $params['dry_run'];

        // $dateList=[];
        $success= true;
        // $error='';
        $is_blocked=false;
        $file_list=[];

        $dbName=null;
        $writeLogToSharing=true;
        if(!empty($db)){
            $dbName=$db->getOgTargetDB();
        }
        $cmd=$this->utils->generateCommandLine('rebuild_totals', [$fromDateTimeStr, $endDateTimeStr,$rebuild_hour,$rebuild_minute,$token], $is_blocked, $file_list, $dbName, $writeLogToSharing);
        $this->writeLogFileToQueueResult($token, $file_list);
        $success=$this->runCmd($cmd)==0;

        return $success;
    }

    public function writeLogFileToQueueResult($token, $file_list){
        // process log file
        foreach($file_list as $filepath){
            // if it's log file
            if(substr($filepath, -4)==='.log'){
                // write log file to queue result
                $this->utils->debug_log('rebuild_games_total log file', $filepath);
                // only keep filename
                $filename=basename($filepath);
                $this->queue_result->updateLogFileByToken($token, $filename);
                break;
            }
        }
    }

    public function remote_rebuild_seamless_balance_history($data, $params, $db=null){

        $success=true;
        $token = $data['token'];
        $rebuild_game_transactions = $params['rebuild_game_transactions'];
        $rebuild_game_balance_transfers = $params['rebuild_game_balance_transfers'];
        $endDateTimeStr = $params['endDateTimeStr'];
        $fromDateTimeStr = $params['fromDateTimeStr'];
        $by_game_platform_id = $params['by_game_platform_id'];

        $dry_run = $params['dry_run'];

        $dateList=[];
        $success= true;
        $error='';
        $is_blocked=false;
        $file_list=[];
        $minutes = 30;

        $dbName=null;
        $writeLogToSharing=true;
        if(!empty($db)){
            $dbName=$db->getOgTargetDB();
        }
        $cmd=$this->utils->generateCommandLine('rebuild_seamless_balance_history', [$fromDateTimeStr, $endDateTimeStr, $by_game_platform_id, $minutes, $rebuild_game_transactions,$rebuild_game_balance_transfers,$token],
            $is_blocked, $file_list, $dbName, $writeLogToSharing);
        $this->writeLogFileToQueueResult($token, $file_list);
        $success=$this->runCmd($cmd)==0;

        return $success;
    }

    public function remote_calculate_selected_aff_monthly_earnings($data, $params, $db=null){
        $this->load->model(['affiliatemodel']);

        $token = $data['token'];
        $update_data = $params['update_data'];
        $fee_type = $params['type'];
        $dateList=[];
        $success= true;
        $error='';
        $is_blocked=false;

        switch($fee_type) {
            case 'addon_platform_fee':
                //platform_fee_update_type
                $batch_update_type = $update_data['platform_fee_update_type'];
                unset($update_data['platform_fee_update_type']);
                break;
            case 'player_benefit_fee':
                $batch_update_type = $update_data['benefit_fee_update_type'];
                unset($update_data['benefit_fee_update_type']);
                break;
        }

        $fee_for_all = $update_data['set_for_all'];
        $yearmonth = $update_data['yearmonth'];
        unset($update_data['set_for_all'], $update_data['yearmonth']);

        if(empty($db)){
            foreach ($update_data as $affiliate_username => $fee) {
                if($batch_update_type == 'ALL') {
                    $new_fee = $fee_for_all;
                } else {
                    $new_fee = $fee;
                }

                if($success) {
                    $success = false;
                    $cmd=$this->utils->generateCommandLine('calculate_selected_aff_monthly_earnings', [$affiliate_username, $yearmonth, $fee_type, $new_fee, $token], $is_blocked);
                    $success=$this->runCmd($cmd)==0;
                }
                if ($success) {
                    $this->queue_result->reconnectDB();
                    $done = true;
                    if (! $this->queue_result->appendResult($token, ['affiliate_username'=> $affiliate_username, 'new_fee' => $new_fee, 'success' => $success], $done)) {
                        $this->utils->error_Log("calculate_selected_aff_monthly_earnings token:" . $token . " failed: " . var_export($params, true));
                        break;
                    } else {
                        continue;
                    }
                } else {
                    $this->queue_result->failedResult($token, ['affiliate_username'=> $affiliate_username, 'new_fee' => $new_fee, 'success' => $success]);
                    $this->utils->error_Log("calculate_selected_aff_monthly_earnings token:" . $token . " failed: " . var_export($params, true));
                    break;
                }

            }
        }else{
            $dbName=$db->getOgTargetDB();
            $this->utils->debug_log('===mdb===remote_calculate_selected_aff_monthly_earnings', $dbName);

            $file_list=[];
            foreach ($update_data as $affiliate_username => $fee) {
                if ($batch_update_type == 'ALL') {
                    $new_fee = $fee_for_all;
                } else {
                    $new_fee = $fee;
                }

                if ($success) {
                    $success = false;
                    $cmd=$this->utils->generateCommandLine('calculate_selected_aff_monthly_earnings', [$affiliate_username, $yearmonth, $fee_type, $new_fee, $token], $is_blocked, $file_list, $dbName);

                    $success=$this->runCmd($cmd)==0;
                }
                if ($success) {
                    $this->queue_result->reconnectDB();
                    $done = true;
                    if (! $this->queue_result->appendResult($token, ['affiliate_username'=> $affiliate_username, 'new_fee' => $new_fee, 'success' => $success], $done)) {
                        $this->utils->error_Log("calculate_selected_aff_monthly_earnings token:" . $token . " failed: " . var_export($params, true));
                        break;
                    } else {
                        continue;
                    }
                } else {
                    $this->queue_result->failedResult($token, ['affiliate_username'=> $affiliate_username, 'new_fee' => $new_fee, 'success' => $success]);
                    $this->utils->error_Log("calculate_selected_aff_monthly_earnings token:" . $token . " failed: " . var_export($params, true));
                    break;
                }
            }

        }
        $this->utils->debug_log("calculate_selected_aff_monthly_earnings is done: ", $success, $dateList);
        return $success;
    }

    public function remote_pay_cashback_daily($data, $params, $db=null){

        $success=true;
        $token = $data['token'];
        $date = $params['date'];
        $forceToPay = $params['forceToPay'];
        $playerId=0;
        $debug_mode='false';

        $result = ['start'=>'try run pay cashback by '.$date];
        $done=false;
        $this->queue_result->appendResult($token, ['request_id'=>_REQUEST_ID, 'result'=> $result ], $done);

        $success= true;
        $is_blocked=false;

        if(empty($db)){
             $cmd=$this->utils->generateCommandLine('onlyPayCashback', [$date, $playerId, $debug_mode, $token, $forceToPay], $is_blocked);
        }else{
            $dbName=$db->getOgTargetDB();
            $file_list=[];
            $cmd=$this->utils->generateCommandLine('onlyPayCashback', [$date, $playerId, $debug_mode, $token, $forceToPay], $is_blocked, $file_list, $dbName);
        }
        $success=$this->runCmd($cmd)==0;

        return $success;

    }

    public function remote_pay_tournament_event($data, $params, $db=null) {
        $success=true;
        $token = $data['token'];
        $eventId = $params['eventId'];
        $forceToPay = $params['forceToPay'];
        $playerId=0;
        $debug_mode='false';

        $result = ['start'=>'try run pay tournament event by '. $eventId];
        $done=false;
        $this->queue_result->appendResult($token, ['request_id'=>_REQUEST_ID, 'result'=> $result ], $done);

        $success= true;
        $is_blocked=false;

        if(empty($db)){
             $cmd=$this->utils->generateCommandLine('onlyPayTouramentBonus', [$eventId, $playerId, $debug_mode, $token, $forceToPay], $is_blocked);
        }else{
            $dbName=$db->getOgTargetDB();
            $file_list=[];
            $cmd=$this->utils->generateCommandLine('onlyPayTouramentBonus', [$eventId, $playerId, $debug_mode, $token, $forceToPay], $is_blocked, $file_list, $dbName);
        }
        $success=$this->runCmd($cmd)==0;

        return $success;
    }

    public function remote_recalculate_tournament_event($data, $params, $db=null) {
        $success=true;
        $token = $data['token'];
        $eventId = $params['eventId'];
        $playerId=0;
        $debug_mode='false';

        $result = ['start'=>'try run pay tournament event by '. $eventId];
        $done=false;
        $this->queue_result->appendResult($token, ['request_id'=>_REQUEST_ID, 'result'=> $result ], $done);

        $success= true;
        $is_blocked=false;

        if(empty($db)){
             $cmd=$this->utils->generateCommandLine('onlyPayTouramentBonus', [$eventId, $playerId, $debug_mode, $token], $is_blocked);
        }else{
            $dbName=$db->getOgTargetDB();
            $file_list=[];
            $cmd=$this->utils->generateCommandLine('onlyPayTouramentBonus', [$eventId, $playerId, $debug_mode, $token], $is_blocked, $file_list, $dbName);
        }
        $success=$this->runCmd($cmd)==0;

        return $success;
    }

    public function remote_sync_t1_gamegateway($data, $params, $db=null){

        $success=true;
        $token = $data['token'];
        $date_to = $params['date_to'];
        $date_from = $params['date_from'];
        $playerName=$params['playerName'];
        $timelimit=30;

        $dateList=[];
        $success= true;
        $error='';
        $is_blocked=false;
        $dbName=null;
        $writeLogToSharing=true;
        if(!empty($db)){
            $dbName=$db->getOgTargetDB();
        }
        $file_list=[];
        $cmd=$this->utils->generateCommandLine('manually_sync_t1_gamegateway', [$date_from, $date_to, $playerName, $timelimit, $token],
            $is_blocked, $file_list, $dbName, $writeLogToSharing);
        $success=$this->runCmd($cmd)==0;
        $this->writeLogFileToQueueResult($token, $file_list);

        return $success;

    }

    public function remote_export_csv($data, $params, $db=null){

        $success=true;
        $token = $data['token'];
        $dateList=[];
        $success= true;
        $is_blocked = false;

        if(empty($db)){
            $cmd=$this->utils->generateCommandLine('do_remote_export_csv_job', [$token], $is_blocked);
        }else{
            $dbName=$db->getOgTargetDB();
            $file_list=[];
            $cmd=$this->utils->generateCommandLine('do_remote_export_csv_job', [$token], $is_blocked, $file_list, $dbName);
        }

        $success=$this->runCmd($cmd)==0;

        return $success;
    }

    public function remote_stop_queue($data, $params, $db=null){
        $this->load->model(['queue_result']);
        $success=true;
        $params = $data['params'];
        $executed_stop_token = $data['token'];
        $token = $params['token_to_stop'];
        //temp call to get the process on dbs
        $result = $this->initJobData($token);
        $state =  json_decode($result['state']);
        $pid = $state->processId;
        // $cmd = "sudo kill -9 $pid";
        // $success=$this->runCmd($cmd)==0;
        $success = posix_kill($pid, SIGKILL);
        if($success){
           $this->utils->debug_log('Killed PID',$pid ,'success', $success);
           $this->queue_result->updateResultStopped($token);
       }else{
         $this->utils->debug_log('Attempt to kill',$pid, 'status', 'success', $success);
         $this->queue_result->updateResultStopped($token);
     }
     $this->queue_result->updateResult($executed_stop_token, ['success' => true]);
     return $success;

    }

    public function remote_manually_batch_add_cashback_bonus($data, $params, $db=null){

        $token = $data['token'];
        $is_blocked=false;
        if(empty($db)){
            $cmd=$this->utils->generateCommandLine('remote_manually_batch_add_cashback_bonus', [$token], $is_blocked);
        }else{
            $dbName=$db->getOgTargetDB();
            $file_list=[];
            $cmd=$this->utils->generateCommandLine('remote_manually_batch_add_cashback_bonus', [$token], $is_blocked, $file_list, $dbName);
        }
        $success=$this->runCmd($cmd)==0;

        $rlt=['running'=>$success, 'cmd'=>$cmd];
        $this->queue_result->appendResult($token, $rlt);

        return $success;

    }

    public function remote_update_player_benefit_fee($data, $params, $db=null){
        $token = $data['token'];
        $is_blocked=false;
        if(empty($db)){
            $cmd=$this->utils->generateCommandLine('update_player_benefit_fee_job', [$token], $is_blocked);
        }else{
            $dbName=$db->getOgTargetDB();
            $file_list=[];
            $cmd=$this->utils->generateCommandLine('update_player_benefit_fee_job', [$token], $is_blocked, $file_list, $dbName);
        }
        $success=$this->runCmd($cmd)==0;

        $rlt=['running'=>$success, 'cmd'=>$cmd];
        $this->queue_result->appendResult($token, $rlt);

        return $success;
    }
    public function remote_batch_benefit_fee_adjustment($data, $params, $db=null){
        $token = $data['token'];
        $is_blocked=false;
        if (empty($db)) {
            $cmd=$this->utils->generateCommandLine('remote_batch_benefit_fee_adjustment', [$token], $is_blocked);
        } else {
            $dbName=$db->getOgTargetDB();
            $file_list=[];
            $cmd=$this->utils->generateCommandLine('remote_batch_benefit_fee_adjustment', [$token], $is_blocked, $file_list, $dbName);
        }
        $success=$this->runCmd($cmd)==0;

        $rlt=['running'=>$success, 'cmd'=>$cmd];
        $this->queue_result->appendResult($token, $rlt);

        return $success;
    }

    public function remote_batch_addon_platform_fee_adjustment($data, $params, $db=null){
        $token = $data['token'];
        $is_blocked=false;
        if (empty($db)) {
            $cmd=$this->utils->generateCommandLine('remote_batch_addon_platform_fee_adjustment', [$token], $is_blocked);
        } else {
            $dbName=$db->getOgTargetDB();
            $file_list=[];
            $cmd=$this->utils->generateCommandLine('remote_batch_addon_platform_fee_adjustment', [$token], $is_blocked, $file_list, $dbName);
        }
        $success=$this->runCmd($cmd)==0;

        $rlt=['running'=>$success, 'cmd'=>$cmd];
        $this->queue_result->appendResult($token, $rlt);

        return $success;
    }

    public function remote_batch_sync_balance_by($data, $params, $db=null){

        $success    = true;
        $token      = $data['token'];
        $mode       = $params['mode'];
        $dry_run    = $params['dry_run'];
        $max_number = $params['max_number'];
        $apiId      = $params['apiId'];

        $success = true;
        $is_blocked = false;

        if(empty($db)){
            $cmd = $this->utils->generateCommandLine('batch_sync_balance_by', [$mode, $dry_run, $max_number, $apiId, $token], $is_blocked);
        }else{
            $dbName=$db->getOgTargetDB();
            $file_list=[];
            $cmd = $this->utils->generateCommandLine('batch_sync_balance_by', [$mode, $dry_run, $max_number, $apiId, $token], $is_blocked, $file_list, $dbName);
        }
        $success = $this->runCmd($cmd) == 0;

        return $success;
    }

    public function remote_send_player_private_ip_mm_alert($data, $params, $db=null){

        $success    = true;
        $token      = $data['token'];
        $success    = true;
        $is_blocked = false;

        if(empty($db)){
            $cmd = $this->utils->generateCommandLine('send_player_private_ip_mm_alert', [$token], $is_blocked);
        }else{
            $dbName=$db->getOgTargetDB();
            $file_list=[];
            $cmd = $this->utils->generateCommandLine('send_player_private_ip_mm_alert', [$token], $is_blocked, $file_list, $dbName);
        }
        $success = $this->runCmd($cmd) == 0;

        return $success;
    }

    public function remote_regenerate_all_report($data, $params, $db=null){

        $success    = true;
        $token      = $data['token'];
        $start_date_obj = new DateTime($params['fromDateTimeStr']);
        $fromDateTimeStr = $start_date_obj->format('Y-m-d');
        $end_date_obj = new DateTime($params['endDateTimeStr']);
        $endDateTimeStr = $end_date_obj->format('Y-m-d');
        $success    = true;
        $is_blocked = false;
        $dbName=null;
        $writeLogToSharing=true;
        if(!empty($db)){
            $dbName=$db->getOgTargetDB();
        }
        $file_list=[];
        $cmd = $this->utils->generateCommandLine('generate_all_report_from_to', [$endDateTimeStr,$fromDateTimeStr, $token],
            $is_blocked, $file_list, $dbName, $writeLogToSharing);

        $success = $this->runCmd($cmd) == 0;
        $this->writeLogFileToQueueResult($token, $file_list);

        return $success;
    }

    public function remote_broadcast_message($data, $params, $db=null){

        $success=true;
        $token = $data['token'];
        $dateList=[];
        $success= true;
        $is_blocked = false;

        if(empty($db)){
            $cmd=$this->utils->generateCommandLine('do_broadcast_message_job', [$token], $is_blocked);
        }else{
            $dbName=$db->getOgTargetDB();
            $file_list=[];
            $cmd=$this->utils->generateCommandLine('do_broadcast_message_job', [$token,$dbName], $is_blocked, $file_list, $dbName);
        }

        $success=$this->runCmd($cmd)==0;

        return $success;
    }

    public function remote_batch_add_bonus($data, $params, $db=null){

        $success=true;
        $is_blocked=false;
        $token = $data['token'];

        if(empty($db)){
            $cmd=$this->utils->generateCommandLine('import_csv_bonus_by_queue', [$token], $is_blocked);
        }else{
            $dbName=$db->getOgTargetDB();
            $file_list=[];
            $cmd=$this->utils->generateCommandLine('import_csv_bonus_by_queue', [$token,$dbName], $is_blocked, $file_list, $dbName);
        }

        $success=$this->runCmd($cmd)==0;

        return $success;

    }

    public function remote_batch_subtract_bonus($data, $params, $db=null){

        $success=true;
        $is_blocked=false;
        $token = $data['token'];

        if(empty($db)){
            $cmd=$this->utils->generateCommandLine('remote_batch_subtract_bonus_by_queue', [$token], $is_blocked);
        }else{
            $dbName=$db->getOgTargetDB();
            $file_list=[];
            $cmd=$this->utils->generateCommandLine('remote_batch_subtract_bonus_by_queue', [$token,$dbName], $is_blocked, $file_list, $dbName);
        }

        $success=$this->runCmd($cmd)==0;

        return $success;

    }

    public function remote_generate_recalculate_cashback_report($data, $params, $db=null){
        $success=true;
        $is_blocked=false;
        $token = $data['token'];
        $fromDate = $params['fromDate'];
        $toDate = $params['toDate'];
        $tempRecalculateCashbackReportTable = $params['tempRecalculateCashbackReportTable'];

        if(empty($db)){
            $cmd=$this->utils->generateCommandLine('generate_recalculate_cashback_report', [$fromDate, $toDate, $tempRecalculateCashbackReportTable, $token], $is_blocked);
        }else{
            $dbName=$db->getOgTargetDB();
            $file_list=[];
            $cmd=$this->utils->generateCommandLine('generate_recalculate_cashback_report', [$fromDate, $toDate, $tempRecalculateCashbackReportTable, $token], $is_blocked, $file_list, $dbName);
        }

        $success=$this->runCmd($cmd)==0;

        return $success;
    }

    public function remote_generate_recalculate_wcdp_report($data, $params, $db=null){
        // wcdp = withdraw condition deduction process
        $success=true;
        $is_blocked=false;
        $token = $data['token'];
        $fromDate = $params['fromDate'];
        $toDate = $params['toDate'];
        $tempRecalculateWCDPReportTable = $params['tempRecalculateWCDPReportTable'];

        if(empty($db)){
            $cmd=$this->utils->generateCommandLine('generate_recalculte_wcdp_report', [$fromDate, $toDate, $tempRecalculateWCDPReportTable, $token], $is_blocked);
        }else{
            $dbName=$db->getOgTargetDB();
            $file_list=[];
            $cmd=$this->utils->generateCommandLine('generate_recalculte_wcdp_report', [$fromDate, $toDate, $tempRecalculateWCDPReportTable, $token], $is_blocked, $file_list, $dbName);
        }

        $success=$this->runCmd($cmd)==0;

        return $success;
    }

    public function remote_calcReportDailyBalance($data, $params, $db = null) {
        $success = true;
        $is_blocked = false;
        $this->utils->debug_log(__METHOD__, $params);
        $arg_date = $params['arg_date'];

        $cmd = $this->utils->generateCommandLine('calcReportDailyBalance', [ $arg_date ], $is_blocked);

        $success = $this->runCmd($cmd) == 0;

        return $success;
    }

    public function remote_batch_update_player_sales_agent($data, $params, $db = null) {
        $success = true;
        $token      = $data['token'];
        $is_blocked = false;
        $this->utils->debug_log(__METHOD__, $token);

        if(empty($db)){
            $cmd = $this->utils->generateCommandLine('batch_update_player_sales_agent', [$token], $is_blocked);
        }else{
            $dbName=$db->getOgTargetDB();
            $file_list=[];
            $cmd = $this->utils->generateCommandLine('batch_update_player_sales_agent', [$token], $is_blocked, $file_list, $dbName);
        }

        $success = $this->runCmd($cmd) == 0;
        return $success;
    }

    public function remote_bulk_import_playertag($data, $params, $db=null){

        $success=true;
        $is_blocked=false;
        $token = $data['token'];

        if(empty($db)){
            $cmd=$this->utils->generateCommandLine('import_csv_playertag_by_queue', [$token], $is_blocked);
        }else{
            $dbName=$db->getOgTargetDB();
            $file_list=[];
            $cmd=$this->utils->generateCommandLine('import_csv_playertag_by_queue', [$token,$dbName], $is_blocked, $file_list, $dbName);
        }

        $success=$this->runCmd($cmd)==0;

        return $success;
    }

    public function remote_bulk_import_affiliatetag($data, $params, $db=null){

        $success=true;
        $is_blocked=false;
        $token = $data['token'];

        if(empty($db)){
            $cmd=$this->utils->generateCommandLine('import_csv_affiliatetag_by_queue', [$token], $is_blocked);
        }else{
            $dbName=$db->getOgTargetDB();
            $file_list=[];
            $cmd=$this->utils->generateCommandLine('import_csv_affiliatetag_by_queue', [$token,$dbName], $is_blocked, $file_list, $dbName);
        }

        $success=$this->runCmd($cmd)==0;

        return $success;
    }

    public function remote_batch_remove_playertag($data, $params, $db=null){

        $this->utils->debug_log('bermar running remote_batch_remove_playertag');

        $success=true;
        $is_blocked=false;
        $token = $data['token'];
        if(empty($db)){
            $cmd=$this->utils->generateCommandLine('batch_remove_playertag_by_queue', [$token], $is_blocked);
        }else{
            $dbName=$db->getOgTargetDB();
            $file_list=[];
            $cmd=$this->utils->generateCommandLine('batch_remove_playertag_by_queue', [$token,$dbName], $is_blocked, $file_list, $dbName);
        }

        $success=$this->runCmd($cmd)==0;

        return $success;
    }

    public function remote_batch_remove_playertag_ids($data, $params, $db=null){

        $this->utils->debug_log('bermar running batch_remove_playertag_ids');

        $success=true;
        $is_blocked=false;
        $token = $data['token'];
        if(empty($db)){
            $cmd=$this->utils->generateCommandLine('batch_remove_playertag_ids_by_queue', [$token], $is_blocked);
        }else{
            $dbName=$db->getOgTargetDB();
            $file_list=[];
            $cmd=$this->utils->generateCommandLine('batch_remove_playertag_ids_by_queue', [$token,$dbName], $is_blocked, $file_list, $dbName);
        }

        $success=$this->runCmd($cmd)==0;

        return $success;
    }

    public function remote_batch_remove_iovation_evidence($data, $params, $db=null){

        $this->utils->debug_log(__METHOD__, $data, $params);

        $success=true;
        $is_blocked=false;
        $token = $data['token'];
        if(empty($db)){
            $cmd=$this->utils->generateCommandLine('batch_remove_iovation_evidence_by_queue', [$token], $is_blocked);
        }else{
            $dbName=$db->getOgTargetDB();
            $file_list=[];
            $cmd=$this->utils->generateCommandLine('batch_remove_iovation_evidence_by_queue', [$token,$dbName], $is_blocked, $file_list, $dbName);
        }

        $success=$this->runCmd($cmd)==0;

        return $success;
    }

    public function remote_check_mgquickfire_data($data, $params, $db=null){
        $token = $data['token'];
        $is_blocked=false;

        if(empty($db)){
             $cmd=$this->utils->generateCommandLine('check_mgquickfire_data_by_queue', [$token], $is_blocked);
        }else{
            $dbName=$db->getOgTargetDB();
            $file_list=[];
            $cmd=$this->utils->generateCommandLine('check_mgquickfire_data_queue', [$token,$dbName], $is_blocked, $file_list, $dbName);
        }
        $success=$this->runCmd($cmd)==0;
        return $success;
    }

    public function remote_sync_game_after_balance($data, $params, $db=null){
        $token = $data['token'];
        $is_blocked=false;

        if(empty($db)){
             $cmd=$this->utils->generateCommandLine('sync_game_after_balance_by_queue', [$token], $is_blocked);
        }else{
            $dbName=$db->getOgTargetDB();
            $file_list=[];
            $cmd=$this->utils->generateCommandLine('sync_game_after_balance_by_queue', [$token,$dbName], $is_blocked, $file_list, $dbName);
        }
        $success=$this->runCmd($cmd)==0;
        return $success;
    }

    public function remote_send_data_to_fast_track($data, $params, $db=null) {
        $token = $data['token'];
        $is_blocked=false;

        if(empty($db)){
             $cmd=$this->utils->generateCommandLine('send_data_to_fast_track', [$token], $is_blocked);
        }else{
            $dbName=$db->getOgTargetDB();
            $file_list=[];
            $cmd=$this->utils->generateCommandLine('send_data_to_fast_track', [$token,$dbName], $is_blocked, $file_list, $dbName);
        }
        $success=$this->runCmd($cmd)==0;
        return $success;
    }

    public function remote_t1lottery_settle_round($data, $params, $db=null){

        $this->utils->debug_log('bermar running remote_t1lottery_settle_round');

        $success=true;
        $is_blocked=false;
        $token = $data['token'];
        if(empty($db)){
            $cmd=$this->utils->generateCommandLine('t1lottery_settle_round_by_queue', [$token], $is_blocked);
        }else{
            $dbName=$db->getOgTargetDB();
            $file_list=[];
            $cmd=$this->utils->generateCommandLine('t1lottery_settle_round_by_queue', [$token,$dbName], $is_blocked, $file_list, $dbName);
        }

        $success=$this->runCmd($cmd)==0;

        return $success;
    }

    public function remote_generate_redemption_code_with_internal_message($data, $params, $db=null){

        $success    = true;
        $token      = $data['token'];
        $success    = true;
        $is_blocked = false;

        if(empty($db)){
            $cmd = $this->utils->generateCommandLine('generate_redemption_code_with_internal_message_job', [$token],$is_blocked);
        }else{
            $dbName=$db->getOgTargetDB();
            $file_list=[];
            $cmd = $this->utils->generateCommandLine('generate_redemption_code_with_internal_message_job', [$token],$is_blocked, $file_list, $dbName);
        }

        $success = $this->runCmd($cmd) == 0;

        return $success;
    }

    public function remote_generate_static_redemption_code_with_internal_message($data, $params, $db=null){

        $success    = true;
        $token      = $data['token'];
        $success    = true;
        $is_blocked = false;

        if(empty($db)){
            $cmd = $this->utils->generateCommandLine('generate_static_redemption_code_with_internal_message_job', [$token],$is_blocked);
        }else{
            $dbName=$db->getOgTargetDB();
            $file_list=[];
            $cmd = $this->utils->generateCommandLine('generate_static_redemption_code_with_internal_message_job', [$token],$is_blocked, $file_list, $dbName);
        }

        $success = $this->runCmd($cmd) == 0;

        return $success;
    }

    public function remote_generate_redemption_code($data, $params, $db=null){

        $success    = true;
        $token      = $data['token'];
        $success    = true;
        $is_blocked = false;

        if(empty($db)){
            $cmd = $this->utils->generateCommandLine('generate_redemption_code_job', [$token],$is_blocked);
        }else{
            $dbName=$db->getOgTargetDB();
            $file_list=[];
            $cmd = $this->utils->generateCommandLine('generate_redemption_code_job', [$token],$is_blocked, $file_list, $dbName);
        }

        $success = $this->runCmd($cmd) == 0;

        return $success;
    }

    public function remote_generate_static_redemption_code($data, $params, $db=null){

        $success    = true;
        $token      = $data['token'];
        $success    = true;
        $is_blocked = false;

        if(empty($db)){
            $cmd = $this->utils->generateCommandLine('generate_static_redemption_code_job', [$token],$is_blocked);
        }else{
            $dbName=$db->getOgTargetDB();
            $file_list=[];
            $cmd = $this->utils->generateCommandLine('generate_static_redemption_code_job', [$token],$is_blocked, $file_list, $dbName);
        }

        $success = $this->runCmd($cmd) == 0;

        return $success;
    }

    public function remote_update_player_quest_reward_status($data, $params, $db=null){

        $success    = true;
        $token      = $data['token'];
        $success    = true;
        $is_blocked = false;

        if(empty($db)){
            $cmd = $this->utils->generateCommandLine('update_player_quest_reward_status_job', [$token],$is_blocked);
        }else{
            $dbName=$db->getOgTargetDB();
            $file_list=[];
            $cmd = $this->utils->generateCommandLine('update_player_quest_reward_status_job', [$token],$is_blocked, $file_list, $dbName);
        }

        $success = $this->runCmd($cmd) == 0;

        return $success;
    }

    public function remote_process_queue_approve_to_approved($data, $params, $db=null){

        $success    = true;
        $token      = $data['token'];
        $success    = true;
        $is_blocked = false;

        if(empty($db)){
            $cmd = $this->utils->generateCommandLine('process_queue_approve_to_approved', [$token],$is_blocked);
        }else{
            $dbName=$db->getOgTargetDB();
            $file_list=[];
            $cmd = $this->utils->generateCommandLine('process_queue_approve_to_approved', [$token],$is_blocked, $file_list, $dbName);
        }

        $success = $this->runCmd($cmd) == 0;

        return $success;
    }

    public function remote_flowgaming_process_pushfeed($data, $params, $db=null){

        $this->utils->debug_log('bermar running flowgaming_process_pushfeed');

        $success=true;
        $is_blocked=false;
        $token = $data['token'];
        if(empty($db)){
            $cmd=$this->utils->generateCommandLine('flowgaming_process_pushfeed_by_queue', [$token], $is_blocked);
        }else{
            $dbName=$db->getOgTargetDB();
            $file_list=[];
            $cmd=$this->utils->generateCommandLine('flowgaming_process_pushfeed_by_queue', [$token,$dbName], $is_blocked, $file_list, $dbName);
        }

        $success=$this->runCmd($cmd)==0;

        return $success;
    }

    public function remote_rebuild_points_transaction_report_hour($data, $params, $db = null)
    {
        $from_date_time = $params['from_date_time'];
        $to_date_time = $params['to_date_time'];
        $player_id = $params['player_id'];
        $is_sync_player_points = $params['is_sync_player_points'];
        $token = $data['token'];
        $is_blocked = false;

        if(empty($db)){
            $cmd = $this->utils->generateCommandLine('rebuild_points_transaction_report_hour', [$from_date_time, $to_date_time, $player_id, $is_sync_player_points, $token], $is_blocked);
        }else{
            $dbName = $db->getOgTargetDB();
            $file_list = [];
            $cmd = $this->utils->generateCommandLine('rebuild_points_transaction_report_hour', [$from_date_time, $to_date_time, $player_id, $is_sync_player_points, $token], $is_blocked, $file_list, $dbName);
        }

        $success = $this->runCmd($cmd) == 0;

        return $success;
    }

    public function remote_kick_player_by_game_platform_id($data, $params, $db=null) {
        $token = $data['token'];
        $is_blocked=false;

        if(empty($db)){
             $cmd=$this->utils->generateCommandLine('kick_player_by_game_platform_id_from_queue', [$token], $is_blocked);
        }else{
            $dbName=$db->getOgTargetDB();
            $file_list=[];
            $cmd=$this->utils->generateCommandLine('kick_player_by_game_platform_id_from_queue', [$token,$dbName], $is_blocked, $file_list, $dbName);
        }
        $success=$this->runCmd($cmd)==0;
        return $success;
    }

    public function remote_player_lock_balance($data, $params, $db = null)
    {
        $success=true;
        $is_blocked=false;
        $username = $params['username'];
        $seconds = $params['seconds'];
        $token = $data['token'];

        if (empty($db)) {
            $cmd = $this->utils->generateCommandLine('test_player_lock_balance', [$username, $seconds, $token], $is_blocked);
        } else {
            $dbName = $db->getOgTargetDB();
            $file_list = [];
            $cmd = $this->utils->generateCommandLine('test_player_lock_balance', [$username, $seconds, $token], $is_blocked, $file_list, $dbName);
        }

        $success = $this->runCmd($cmd) == 0;

        return $success;
    }

    public function remote_call_sync_tags_to_3rd_api_with_player_id_list($data, $params, $db=null) {
        $token = $data['token'];
        $is_blocked=false;

        if(empty($db)){
             $cmd=$this->utils->generateCommandLine('call_sync_tags_to_3rd_api_with_player_id_list_from_queue', [$token], $is_blocked);
        }else{
            $dbName=$db->getOgTargetDB();
            $file_list=[];
            $cmd=$this->utils->generateCommandLine('call_sync_tags_to_3rd_api_with_player_id_list_from_queue', [$token,$dbName], $is_blocked, $file_list, $dbName);
        }
        $success=$this->runCmd($cmd)==0;
        return $success;
    }

    public function remote_lock_table($data, $params, $db = null)
    {
        $success=true;
        $is_blocked=false;
        $table = $params['table'];
        $seconds = $params['seconds'];
        $token = $data['token'];

        if (empty($db)) {
            $cmd = $this->utils->generateCommandLine('test_lock_table', [$table, $seconds, $token], $is_blocked);
        } else {
            $dbName = $db->getOgTargetDB();
            $file_list = [];
            $cmd = $this->utils->generateCommandLine('test_lock_table', [$table, $seconds, $token], $is_blocked, $file_list, $dbName);
        }

        $success = $this->runCmd($cmd) == 0;

        return $success;
    }

    public function remote_do_manual_sync_gamelist_from_gamegateway($data, $params, $db = null)
    {
        $success=true;
        $is_blocked=false;
        $game_platform_id = $params['game_platform_id'];
        $token = $data['token'];
        $this->utils->debug_log('remote_do_manual_sync_gamelist_from_gamegateway params:', $params);

        if (empty($db)) {
            $cmd = $this->utils->generateCommandLine('run_remote_manual_sync_gamelist_from_gamegateway', [$game_platform_id, $token], $is_blocked);
        } else {
            $dbName = $db->getOgTargetDB();
            $file_list = [];
            $cmd = $this->utils->generateCommandLine('run_remote_manual_sync_gamelist_from_gamegateway', [$game_platform_id, $token], $is_blocked, $file_list, $dbName);
        }

        $success = $this->runCmd($cmd) == 0;

        return $success;
    }

    public function remote_sync_game_tag_from_one_to_other_mdb($data, $params, $db = null)
    {
        $success=true;
        $is_blocked=false;
        $token = $data['token'];

        if (empty($db)) {
            $cmd = $this->utils->generateCommandLine('do_sync_game_tag_from_one_to_other_mdb_by_queue', [$token], $is_blocked);
        } else {
            $dbName = $db->getOgTargetDB();
            $file_list = [];
            $cmd = $this->utils->generateCommandLine('do_sync_game_tag_from_one_to_other_mdb_by_queue', [$token], $is_blocked, $file_list, $dbName);
        }

        $success = $this->runCmd($cmd) == 0;

        return $success;
    }

    public function remote_batch_refund($data, $params, $db = null)
    {
        if (!$this->utils->getConfig('enable_batch_refund')) {
            $this->utils->debug_log('disabled enable_batch_refund');
            return true;
        }
        $success=true;
        $is_blocked=false;
        $token = $data['token'];

        if (empty($db)) {
            $cmd = $this->utils->generateCommandLine('do_batch_refund', [$token], $is_blocked);
        } else {
            $dbName = $db->getOgTargetDB();
            $file_list = [];
            $cmd = $this->utils->generateCommandLine('do_batch_refund', [$token], $is_blocked, $file_list, $dbName);
        }

        $success = $this->runCmd($cmd) == 0;

        return $success;
    }

    public function remote_batch_export_player_id($data, $params, $db = null)
    {
        if (!$this->utils->getConfig('enable_batch_export_player_id')) {
            $this->utils->debug_log('disabled enable_batch_export_player_id');
            return true;
        }
        $success=true;
        $is_blocked=false;
        $token = $data['token'];

        if (empty($db)) {
            $cmd = $this->utils->generateCommandLine('do_batch_export_player_id', [$token], $is_blocked);
        } else {
            $dbName = $db->getOgTargetDB();
            $file_list = [];
            $cmd = $this->utils->generateCommandLine('do_batch_export_player_id', [$token], $is_blocked, $file_list, $dbName);
        }

        $success = $this->runCmd($cmd) == 0;

        return $success;
    }

    public function remote_sync_games_report_timezones($data, $params, $db = null)
    {

        $success=true;
        $is_blocked=false;
        $dateFrom = $params['dateFrom'];
        $dateTo = $params['dateTo'];
        $gameApiId = $params['gameApiId'];
        $playerId = $params['playerId'];
        $token = $data['token'];

        if (empty($db)) {
            $cmd = $this->utils->generateCommandLine('sync_games_report_timezones_by_token', [$token, $dateFrom, $dateTo, $gameApiId, $playerId], $is_blocked);
        } else {
            $dbName = $db->getOgTargetDB();
            $file_list = [];
            $cmd = $this->utils->generateCommandLine('sync_games_report_timezones_by_token', [$token, $dateFrom, $dateTo, $gameApiId, $playerId], $is_blocked, $file_list, $dbName);
        }

        $success = $this->runCmd($cmd) == 0;

        return $success;
    }

    public function remote_sync_summary_game_total_bet_daily($data, $params, $db = null)
    {
        $success=true;
        $is_blocked=false;
        $date = $params['date'];
        $token = $data['token'];

        if (empty($db)) {
            $cmd = $this->utils->generateCommandLine('sync_summary_game_total_bet_daily_token', [$token, $date], $is_blocked);
        } else {
            $dbName = $db->getOgTargetDB();
            $file_list = [];
            $cmd = $this->utils->generateCommandLine('sync_summary_game_total_bet_daily_token', [$token, $date], $is_blocked, $file_list, $dbName);
        }

        $success = $this->runCmd($cmd) == 0;

        return $success;
    }

    public function remote_sync_latest_game_records($data, $params, $db = null) {
        $success=true;
        $is_blocked=false;
        $token = $data['token'];
        $date_from = $params['date_from'];
        $date_to = $params['date_to'];

        if (empty($db)) {
            $cmd = $this->utils->generateCommandLine('do_sync_latest_game_records', [$date_from, $date_to, $token], $is_blocked);
        } else {
            $dbName = $db->getOgTargetDB();
            $file_list = [];
            $cmd = $this->utils->generateCommandLine('do_sync_latest_game_records', [$date_from, $date_to, $token], $is_blocked, $file_list, $dbName);
        }

        $success = $this->runCmd($cmd) == 0;

        return $success;
    }

    public function remote_check_seamless_round_status($data, $params, $db=null){

        $success=true;
        $date_to = $params['date_to'];
        $date_from = $params['date_from'];
        $playerName=$params['playerName'];
        $token = $data['token'];

        $success= true;
        $is_blocked=false;
        $dbName=null;
        $writeLogToSharing=true;
        if(!empty($db)){
            $dbName=$db->getOgTargetDB();
        }
        $file_list=[];
        $cmd=$this->utils->generateCommandLine('do_check_seamless_round_status', [$token, $date_from, $date_to, $playerName],
            $is_blocked, $file_list, $dbName, $writeLogToSharing);
        $success=$this->runCmd($cmd)==0;
        return $success;
    }

    public function remote_cancel_game_round($data, $params, $db = null) {
        $success=true;
        $is_blocked=false;
        $token = $data['token'];
        $game_platform_id = $params['game_platform_id'];
        $game_username = $params['game_username'];
        $round_id = $params['round_id'];
        $game_code = $params['game_code'];

        if (empty($db)) {
            $cmd = $this->utils->generateCommandLine('do_cancel_game_round', [$game_platform_id, $game_username, $round_id, $game_code, $token], $is_blocked);
        } else {
            $dbName = $db->getOgTargetDB();
            $file_list = [];
            $cmd = $this->utils->generateCommandLine('do_cancel_game_round', [$game_platform_id, $game_username, $round_id, $game_code, $token], $is_blocked, $file_list, $dbName);
        }

        $success = $this->runCmd($cmd) == 0;

        return $success;
    }

    public function remote_refresh_all_player_balance_in_specific_game_provider($data, $params, $db = null)
    {

        $success=true;
        $is_blocked=false;
        $game_platform_id = $params['game_platform_id'];
        $is_only_registered = $params['is_only_registered'];
        $token = $data['token'];
        $this->utils->debug_log('remote_refresh_all_player_balance_in_specific_game_provider', $params);
        if (empty($db)) {
            $cmd = $this->utils->generateCommandLine('refresh_all_player_balance_in_specific_game_provider_by_token', [$token, $game_platform_id, $is_only_registered], $is_blocked);
        } else {
            $dbName = $db->getOgTargetDB();
            $file_list = [];
            $cmd = $this->utils->generateCommandLine('refresh_all_player_balance_in_specific_game_provider_by_token', [$token, $game_platform_id, $is_only_registered], $is_blocked, $file_list, $dbName);
        }

        $success = $this->runCmd($cmd) == 0;

        return $success;
    }

    public function remote_transfer_all_players_subwallet_to_main_wallet($data, $params, $db = null)
    {
        return false;

        // $success=true;
        // $is_blocked=false;
        // $game_id = $params['game_id'];
        // $min_balance = $params['min_balance'];
        // $max_balance = $params['max_balance'];
        // $token = $data['token'];
        // $this->utils->debug_log('remote_transfer_all_players_subwallet_to_main_wallet_params', $params);
        // if (empty($db)) {
        //     $cmd = $this->utils->generateCommandLine('transfer_all_players_subwallet_to_main_wallet_by_token', [$token, $game_id, $max_balance, $min_balance], $is_blocked);
        // } else {
        //     $dbName = $db->getOgTargetDB();
        //     $file_list = [];
        //     $cmd = $this->utils->generateCommandLine('transfer_all_players_subwallet_to_main_wallet_by_token', [$token, $game_id, $max_balance, $min_balance], $is_blocked, $file_list, $dbName);
        // }

        // $success = $this->runCmd($cmd) == 0;

        // return $success;
    }
}
