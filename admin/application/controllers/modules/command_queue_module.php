<?php

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

trait command_queue_module {

    protected function initJobData($token) {
        //search queue result
        $this->load->model(['queue_result']);
        $this->load->library(['language_function', 'session']);
        // $this->queue_result->reconnectDB();

        //load from token
        if(!empty($token)){

            $row=$this->queue_result->getResult($token);
            $row['params']=$this->utils->decodeJson($row['full_params']);

            $this->session->set_userdata('login_lan', $row['lang']);
            $this->utils->debug_log('setup lang', $row['lang']);
            $this->utils->initiateLang();

            return $row;
        }

        return null;

        // $data = null;
        // if ($job) {
        //  $json = $job->workload();
        //  if (!empty($json)) {
        //      // log_message('debug', 'start do_halt_job: ' . $json);
        //      $data = json_decode($json, true);
        //  }
        // }
        // $CI = &get_instance();
        // $CI->load->library(array('lib_gearman', 'utils'));
        // return array($data, $CI);
    }

    public function do_export_csv_job($token) {
        // list($data, $CI) = Queue::initJobData($job);

        $this->utils->debug_log('start memory_get_usage:'. (memory_get_usage()/1024) );

        $data=$this->initJobData($token);

        // $CI->utils->debug_log('workload data', $data);
        // $CI->load->model(array('queue_result'));
        // $CI->queue_result->reconnectDB();

        $token = $data['token'];
        $params = $data['params'];
        //$params[0]['extra_search']['export_token'] = $token;
        $no_of_tries = 5;
        //find extra_search and inject token
        for ($i=0; $i < $no_of_tries; $i++) {
            if(isset($params[$i]['extra_search'])){
                $params[$i]['extra_search']['export_token']=$token;
                $params[$i]['extra_search']['is_remote_export']=false;
                $params[$i]['extra_search']['caller'] = $data['caller'];
            }
        }
        $funcName = $data['func_name'];
        $rlt=['success'=>false, 'filename'=>null];
        $this->utils->debug_log('paramms',$params);
        if(!empty($funcName)){
            try{

                // $this->utils->debug_log('params', $params, $this->utils->decodeJson($params));

                // $params=$this->utils->decodeJson($params);

                $this->utils->debug_log('call '.$funcName, 'params', $params);

                $this->load->model(['report_model']);

                // $CI->load->model(['report_model']);
                $result = call_user_func_array(array($this->report_model, $funcName), $params);
                // $result = $this->report_model->traffic_statistics_aff($affId, $request, $is_export);

                $filename=$result;

                if(is_array($result)){
                    //old export
                    $filename=$this->utils->create_csv_filename($funcName);
                    // $isCsv=true;
                    //don't use link
                    $this->utils->create_csv($result, $filename);
                }

                $rlt=['success'=>true, 'filename'=>$filename.'.csv'];
                $this->queue_result->updateResult($token, $rlt);

                $this->utils->debug_log('result '.$funcName, $rlt);

            }catch(Exception $e){
                $this->utils->error_log('[ERROR] do_export_csv_job error', $e);
                // $CI->utils->writeQueueErrorLog($e->getTraceAsString());
            }
        }else{
            $this->utils->error_log('[ERROR] do_export_csv_job error: canot call empty function');
        }

        // if (!$this->queue_result->updateResult($token, $rlt)) {
        //     $this->utils->error_log("[ERROR] [do_export_csv_job] to " , $to , "token", $token, 'funcName', $funcName,
        //         "failed", $params);
        // }

        $this->utils->debug_log('end memory_get_usage:'. (memory_get_usage()/1024));

        return $rlt;
    }

    public function do_remote_export_csv_job($token) {

        $this->utils->debug_log('start memory_get_usage:'. (memory_get_usage()/1024) );

        $data=$this->initJobData($token);
      //  $token = $data['token'];
        $params = $data['params'];

        $funcName =null;
        $no_of_tries = 5;
         //find extra_search and inject token
        for ($i=0; $i < $no_of_tries ; $i++) {
            if(isset($params[$i]['extra_search'])){
                $params[$i]['extra_search']['export_token']=$token;
                $params[$i]['extra_search']['is_remote_export']=true;
                $params[$i]['extra_search']['caller'] = $data['caller'];
                $funcName = $params[$i]['extra_search']['target_func_name'];
            }
        }
        $this->utils->info_log('call '.$funcName, 'params', $params, 'token',$token);
      //exit;
        $rlt=['success'=>false, 'filename'=>null];
        if(!empty($funcName)){
            try{
                $this->utils->debug_log('call '.$funcName, 'params', $params);
                $this->load->model(['report_model']);
                $export_pid = getmypid();
                $this->report_model->export_token = $token;
                $this->report_model->export_pid = $export_pid;
                $rlt=['success'=>false, 'is_export'=>true, 'processMsg'=> lang('Getting data').'...',  'written' => 0, 'total_count' => 0, 'progress' => 0];
                $this->queue_result->updateResultRunning($token, $rlt, array('processId'=>$export_pid));
                $result = call_user_func_array(array($this->report_model, $funcName), $params);
                $this->utils->debug_log('result '.$result);
                $filename=$result;

                if(is_array($result)){
                    //old export
                    $filename=$this->utils->create_csv_filename($funcName);

                    $this->utils->create_csv($result, $filename,false, $token);
                }
                $rlt=['success'=>true, 'filename'=>$filename.'.csv'];
                //$this->queue_result->updateResult($token, $rlt);

                $this->utils->debug_log('result '.$funcName, $rlt);

            }catch(Exception $e){
                $this->utils->error_log('[ERROR] do_export_csv_job error', $e);

            }
        }else{
            $this->utils->error_log('[ERROR] do_export_csv_job error: canot call empty function');
        }

        $this->utils->debug_log('end memory_get_usage:'. (memory_get_usage()/1024));

        return $rlt;
    }

    public function test_export_big_csv(){

        $this->utils->debug_log('start memory_get_usage:'. (memory_get_usage()/1024) );

        $sql='select * from game_logs';

        $dataResult=array(
            "draw" => '-1',
            "recordsFiltered" => null,
            "recordsTotal" => null,
            "data" => $sql,
            "header_data" => ['col1'],
        );

        $filename='game_logs_'.random_string('md5');

        $this->utils->debug_log('create csv by '.$sql, $this->utils->create_csv($dataResult, $filename));

        $this->utils->debug_log('end memory_get_usage:'. (memory_get_usage()/1024));

    }

    public function do_broadcast_message_job($token,$dbKey=null){

        $data=$this->initJobData($token);

        $token = $data['token'];
        $params = $data['params'];
        $funcName = $data['func_name'];

        $subject = $params['message_subject'];
        $message = $params['message_body'];
        $userId=$params['userId'];
        $sender=$params['sender'];
        $search_query=$params['search_query'];

        $this->load->model(array('internal_message','queue_result'));
        $sql="select playerId from player where deleted_at is null";

        if(!empty($search_query)){

        }

        $is_multi_db = $this->utils->isEnabledMDB();
        $conn=null;

        if($is_multi_db === true){

            if( empty($dbKey)){
                return $this->utils->error_log('dbKey is empty in mdb setting');
            }

            $db_settings_map = $this->utils->getConfig('multiple_databases');

            if(isset($db_settings_map[$dbKey])){

                if($this->utils->getConfig('enable_readonly_db')){
                    $db_settings=$db_settings_map[$dbKey]['readonly'];
                    $conn=mysqli_connect($db_settings['hostname'],
                        $db_settings['username'],
                        $db_settings['password'],
                        $db_settings['database'],
                        $db_settings['port']);
                }else{
                    $db_settings=$db_settings_map[$dbKey]['default'];
                    $conn=mysqli_connect($db_settings['hostname'],
                        $db_settings['username'],
                        $db_settings['password'],
                        $db_settings['database'],
                        $db_settings['port']);
                }
            }
        }else{

            if($this->utils->getConfig('enable_readonly_db')){
                $conn=mysqli_connect($this->utils->getConfig('db.readonly.hostname'),
                    $this->utils->getConfig('db.readonly.username'),
                    $this->utils->getConfig('db.readonly.password'),
                    $this->utils->getConfig('db.readonly.database'),
                    $this->utils->getConfig('db.readonly.port'));

            }else{
                $conn=mysqli_connect($this->utils->getConfig('db.default.hostname'),
                    $this->utils->getConfig('db.default.username'),
                    $this->utils->getConfig('db.default.password'),
                    $this->utils->getConfig('db.default.database'),
                    $this->utils->getConfig('db.default.port'));
            }
        }


        $charset=$this->utils->getConfig('db.default.char_set');
        mysqli_set_charset($conn, $charset);
        // $this->ci->utils->debug_log('try get sql', $sql);
        //get sql then run, large mode
        $qry = mysqli_query($conn, $sql, MYSQLI_USE_RESULT);


        $today = date('Y-m-d H:i:s');
        // $userId = $this->authentication->getUserId();
        // $sender = $this->authentication->getUsername();
        // $ticket_number = '';

        // $this->startTrans();

        $cnt=0;
        while ($row = mysqli_fetch_array($qry, MYSQLI_ASSOC)) {
        // if ($playerIds) {
        //  foreach ($playerIds as $playerId) {

            $send_message_result[$cnt]['Message Id']    = $this->internal_message->addNewMessageAdmin($userId // #1
                                                        , $row['playerId'] // #2
                                                        , $sender // #3
                                                        , $subject // #4
                                                        , $message);  // #5
            $send_message_result[$cnt]['Player Id']     =$row['playerId'];

            $cnt++;
        //  }
        // }
        }

        $cntMessageSuccess  = 0;
        $cntMessageFailed  = 0;
        foreach ($send_message_result as $key => $value) {
            if(!empty($value['Message Id'])){
                $cntMessageSuccess++;
            }else{
                $cntMessageFailed++;
            }
        }

        $result = [
            "Total count of sent messages"=> $cntMessageSuccess,
            "Total count of failed sent messages"=> $cntMessageFailed
        ];

        if ($cntMessageSuccess == $cnt) {
            if (!$this->queue_result->updateResult($token, $result)) {
                raw_debug_log("[ERROR] [do_boardcast_message_job] token:" . $token . " failed: " . var_export($params, true));
            }
        } else {
            $this->utils->debug_log('do_boardcast_message_job count ======================================>',$cntMessageSuccess,$cntAllMessage,$cnt);
            $this->queue_result->failedResult($token, $result);
            raw_debug_log("[ERROR] [do_boardcast_message_job] token:" . $token . " failed: " . var_export($params, true));
        }

        return $result;
    }

    public function do_email_job($token) {
        $data=$this->initJobData($token);

        $CI=$this;

        // $CI->utils->debug_log('workload data', $data);
        $CI->load->model(array('queue_result'));
        // $CI->queue_result->reconnectDB();

        $token = $data['token'];
        $params = $data['params'];
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

        raw_debug_log('debug', $token. ' send mail: '.$to.' subject:'.$emailData['subject']);

        if($CI->utils->isSmtpApiEnabled() && $CI->utils->getOperatorSetting('use_smtp_api') == 'true')
        {
            $smtp_api = $CI->utils->getConfig('current_smtp_api');
            $CI->load->library('smtp/'.$smtp_api);
            $CI->load->model('operatorglobalsettings');

            $smtp_api = strtolower($smtp_api);
            $api = $CI->$smtp_api;

            $from_email = isset($data['from']) && !empty($data['from']) ? $data['from'] : ($this->operatorglobalsettings->getSettingValue('smtp_api_mail_from_email') ?: $this->operatorglobalsettings->getSettingValue('mail_from_email'));
            $from_name = isset($data['from_name']) && !empty($data['from_name']) ? $data['from_name'] : ($this->operatorglobalsettings->getSettingValue('smtp_api_mail_from_name') ?: $this->operatorglobalsettings->getSettingValue('mail_from'));

            $SMTP_API_RESULT = $api->sendEmail($to, $from_email, $from_name, $subject, $body);

            $rlt = $api->isSuccess($SMTP_API_RESULT);

            $CI->utils->debug_log("SMTP API RESPONSE: " . var_export($rlt, true));

            if(!$rlt) $CI->utils->debug_log("SMTP API ERROR RESPONSE: " . var_export($api->getErrorMessages($SMTP_API_RESULT), true));

        }
        else
        {
            $CI->load->library(['email_setting', 'email_manager']);
            if (isset($params['new_email']) && $params['new_email'] ) {
                $rlt = $CI->email_manager->sendEmail($to, $params);
            } else {
                $rlt = $CI->email_setting->sendEmail($to, $emailData);
            }
        }

        if ($rlt === true) {
            if (!$CI->queue_result->updateResult($token, $rlt)) {
                raw_debug_log("[ERROR] [do_email_job] to " . $to . ", token:" . $token . " failed: " . var_export($params, true));
            }
        } else {
            $CI->queue_result->failedResult($token, $rlt);
            raw_debug_log("[ERROR] [do_email_job] to " . $to . ", token:" . $token . " failed: " . var_export($params, true));
        }

        raw_debug_log('debug', "[do_email_job] is done: ".$rlt);
        return $rlt;
    }

    public function do_sync_game_logs_job($token) {
        return false;

        // $data=$this->initJobData($token);

        // $CI=$this;

        // // $CI->utils->debug_log('workload data', $data);
        // $CI->load->model(array('queue_result'));
        // // $CI->queue_result->reconnectDB();

        // $token = $data['token'];
        // $params = $data['params'];

        // $endDateTimeStr = $params['endDateTimeStr'];
        // $fromDateTimeStr = $params['fromDateTimeStr'];
        // $game_api_id = $params['game_api_id'];
        // $timelimit = $params['timelimit'];
        // $playerName = $params['playerName'];
        // $dry_run = $params['dry_run'];
        // $merge_only = $params['merge_only'];

        // $this->utils->debug_log('do_sync_game_logs_job', $token, $params);

        // $game_api_id=intval($game_api_id);

        // // $default_sync_game_logs_max_time_second = $this->config->item('default_sync_game_logs_max_time_second');
        // set_time_limit(0);

        // $this->utils->debug_log('=========start sync_game_logs============================',
        //     'fromDateTimeStr', $fromDateTimeStr, 'endDateTimeStr', $endDateTimeStr, 'playerName', $playerName, 'timelimit', $timelimit, 'dry_run', $dry_run);
        // // $this->returnText($msg);
        // $mark = 'sync_game_logs';
        // $this->utils->markProfilerStart($mark);

        // if($endDateTimeStr>$this->utils->getNowForMysql()){
        //     $endDateTimeStr=$this->utils->getNowForMysql();
        // }

        // $step='+'.$timelimit.' minutes';

        // $this->queue_result->appendResult($token, ['step'=>$step]);

        // $dateList=[];
        // $manager = $this->utils->loadGameManager();
        // $success=$this->utils->loopDateTimeStartEnd($fromDateTimeStr, $endDateTimeStr, $step, function($from, $to, $step)
        //         use(&$dateList, $manager, $playerName, $game_api_id, $dry_run, $token, $merge_only){

        //     $this->utils->debug_log($from, $to, $game_api_id, $step);

        //     $fromStr=$this->utils->formatDateTimeForMysql($from);
        //     $toStr=$this->utils->formatDateTimeForMysql($to);

        //     // $ignore_public_sync = true;
        //     $return_var=$this->run_cmd_sync_all_game($dry_run, $fromStr, $toStr,
        //         $game_api_id, $playerName, $token, $merge_only);

        //     $success= $return_var==0;

        //     $dateList[]=['from'=>$fromStr,
        //         'to'=>$toStr,
        //         'game api'=>$game_api_id,
        //         'player'=>$playerName,
        //         'return_var'=>$return_var,
        //         'success'=>$success];

        //     return $success;
        // });

        // $this->utils->markProfilerEndAndPrint($mark);
        // // $this->returnText($msg);

        // $this->utils->debug_log('=========end sync_game_logs============================', $dateList);
        // // $this->returnText($msg);

        // if ($success) {
        //     $this->queue_result->reconnectDB();
        //     $done=true;
        //     if (!$this->queue_result->appendResult($token, $dateList, $done)) {
        //         $this->utils->error_Log("do_sync_game_logs_job to " . $to . ", token:" . $token . " failed: " . var_export($params, true));
        //     }
        // } else {
        //     $this->queue_result->failedResult($token, $dateList);
        //     $this->utils->error_Log("do_sync_game_logs_job to " . $to . ", token:" . $token . " failed: " . var_export($params, true));
        // }

        // $this->utils->debug_log("do_sync_game_logs_job is done: ", $success, $dateList);
        // return $success;
    }

    public function do_rebuild_games_total_job($token) {

         return false;
        // $data=$this->initJobData($token);

        // $CI=$this;
        // $CI->load->model(array('queue_result'));

        // $token = $data['token'];
        // $params = $data['params'];
        // $rebuild_hour = $params['rebuild_hour'];
        // $rebuild_minute = $params['rebuild_minute'];
        // $endDateTimeStr = $params['endDateTimeStr'];
        // $fromDateTimeStr = $params['fromDateTimeStr'];
        // $dry_run = $params['dry_run'];

        // $this->utils->debug_log('do_rebuild_games_total_job', $token, $params);

        // set_time_limit(0);

        // $this->utils->debug_log('=========start do_rebuild_games_total============================',
        //     'fromDateTimeSt', $fromDateTimeStr, 'endDateTimeStr', $endDateTimeStr, 'dry_run', $dry_run);

        // $mark = 'do_rebuild_games_total';
        // $this->utils->markProfilerStart($mark);

        // $dateList=[];
        // $success= true;
        // $error='';

        // try {
        //  $this->rebuild_totals($fromDateTimeStr, $endDateTimeStr, $rebuild_hour, $rebuild_minute) ;
        //  }
        //  catch(Exception $e) {
        //     $error=$e->getMessage();
        //     $success = false;
        // }

        // $dateList[]=['from'=>$fromDateTimeStr,
        // 'to'=> $endDateTimeStr,
        // 'rebuild_hour' => $rebuild_hour,
        // 'rebuild_minute' => $rebuild_minute,
        // 'return_var'=> true,
        // 'success'=>$success];

        // $this->utils->markProfilerEndAndPrint($mark);

        // $this->utils->debug_log('=========end do_rebuild_games_total============================', $dateList);
        // if ($success) {
        //     $this->queue_result->reconnectDB();
        //     $done=true;
        //     if (!$this->queue_result->appendResult($token, $dateList, $done)) {
        //         $this->utils->error_Log("do_rebuild_games_total_job to " . $to . ", token:" . $token . " failed: " . var_export($params, true));
        //     }
        // } else {
        //     $this->queue_result->failedResult($token, $dateList);
        //     $this->utils->error_Log("do_rebuild_games_total_job to " . $to . ", token:" . $token . " failed: " . var_export($params, true));
        // }
        // $this->utils->debug_log("do_rebuild_games_total_job is done: ", $success, $dateList);
        // return $success;
    }

    public function do_calculate_aff_earnings_job($token) {

        $data = $this->initJobData($token);

        $CI = $this;

        $CI->load->model(array('queue_result'));

        $token = $data['token'];
        $params = $data['params'];

        $startDate = $params['startDate'];
        $endDate = $params['endDate'];
        $username = $params['username'];
        $dry_run = $params['dry_run'];

        $this->utils->debug_log('do_calculate_aff_earnings_job', $token, $params);

        set_time_limit(0);

        $this->utils->debug_log('=========start calculate_aff_earnings ============================',
            'startDate', $startDate,
            'endDate', $endDate,
            'username', $username,
            'dry_run', $dry_run
        );

        $mark = 'calculate_aff_earnings';
        $this->utils->markProfilerStart($mark);

        ################################################################

        // $success = $this->calculate_daily_earnings($startDate, $endDate);
        $success = $this->test_generate_affiliate_earnings($startDate, $endDate, $username);

        $dateList = array(array(
            'from' => $startDate,
            'to' => $endDate,
            'username' => $username,
            'success' => $success
        ));

        ################################################################

        $this->utils->markProfilerEndAndPrint($mark);

        $this->utils->debug_log('=========end calculate_aff_earnings ============================', $dateList);

        if ($success) {
            $this->queue_result->reconnectDB();
            $done = TRUE;
            if ( ! $this->queue_result->appendResult($token, $dateList, $done)) {
                $this->utils->error_Log("do_calculate_aff_earnings_job token:" . $token . " failed: " . var_export($params, true));
            }else{

            }
        } else {
            $this->queue_result->failedResult($token, $dateList);
            $this->utils->error_Log("do_calculate_aff_earnings_job token:" . $token . " failed: " . var_export($params, true));
        }

        $this->utils->debug_log("do_calculate_aff_earnings_job is done: ", $success, $dateList);

        return $success;
    }

    public function do_call_cf_job($token){
        $data=$this->initJobData($token);

        $this->load->model(array('queue_result'));

        $token = $data['token'];
        $params = $data['params'];
        $domainList = $params['domainList'];

        $this->load->library(['lib_cloudflare']);
        $this->lib_cloudflare->init();
        $success=$this->lib_cloudflare->addDomainList($domainList);

        $result=$this->lib_cloudflare->last_result;
        $this->utils->debug_log($result);

        if ($success === true) {
            if (!$this->queue_result->updateResult($token, $result)) {
                $this->utils->error_log("[ERROR] [do_call_cf_job] to " . $to . ", token:" . $token . " failed: " , $params);
            }
        } else {
            $this->queue_result->failedResult($token, $result);
            $this->utils->error_log("[ERROR] [do_call_cf_job] to " . $to . ", token:" . $token . " failed: ", $params);
        }

        $this->utils->debug_log("[do_call_cf_job] is done",$success);
        return $success;
    }

    /**
     * add queue
     *
     * @param  string $importer_formatter
     * @param  string $import_player_csv_file
     * @param  string $import_aff_csv_file
     * @param  string $import_aff_contact_csv_file
     * @param  string $import_player_contact_csv_file
     * @param  string $import_player_bank_csv_file
     *
     */
    public function run_remote_import_players($importer_formatter, $import_player_csv_file,
        $import_aff_csv_file, $import_aff_contact_csv_file, $import_player_contact_csv_file, $import_player_bank_csv_file){

        if($import_player_csv_file=='_null'){
            $import_player_csv_file='';
        }
        if($import_aff_csv_file=='_null'){
            $import_aff_csv_file='';
        }
        if($import_aff_contact_csv_file=='_null'){
            $import_aff_contact_csv_file='';
        }
        if($import_player_contact_csv_file=='_null'){
            $import_player_contact_csv_file='';
        }
        if($import_player_bank_csv_file=='_null'){
            $import_player_bank_csv_file='';
        }

        $files=[
            'import_player_csv_file'=>$import_player_csv_file,
            'import_aff_csv_file'=>$import_aff_csv_file,
            'import_aff_contact_csv_file'=>$import_aff_contact_csv_file,
            'import_player_contact_csv_file'=>$import_player_contact_csv_file,
            'import_player_bank_csv_file'=>$import_player_bank_csv_file,
        ];

        $callerType=Queue_result::CALLER_TYPE_ADMIN;
        $caller=null;
        $state=null;
        $lang=null;

        $this->load->library(['lib_queue']);
        $token=$this->lib_queue->addRemoteImportPlayers($files, $importer_formatter, $callerType, $caller, $state, $lang);

        $this->utils->debug_log('create token', $token, $files, $importer_formatter, $callerType, $caller, $state, $lang);
        $this->utils->info_log('/system_management/common_queue/'.$token);
    }

    public function do_stop_queue($token){

        $this->load->model(['queue_result']);
        $data = $this->initJobData($token);
      //  $this->utils->info_log('data', $data, $token_to_stop);
        $params = $data['params'];

        $token_to_stop = $params['token_to_stop'];
        $token_to_stop_data = $this->initJobData($token_to_stop);

        $state =  json_decode($token_to_stop_data['state']);
        $pid = $state->processId;
        //last resort to kill process
        $success = posix_kill($pid, SIGKILL);
        if($success){
            $this->queue_result->updateResultStopped($token_to_stop);
            $this->utils->debug_log('Killed PID',$pid ,'success', $success);
        }else{
            $this->utils->debug_log('Attempted to kill',$pid, 'status', 'success', $success);
        }
        $this->queue_result->updateResult($token, ['success' => true]);
        return $success;

    }

    public function remote_manually_batch_add_cashback_bonus($token){

        $this->load->model(['queue_result','player_model','wallet_model','transactions','withdraw_condition','transaction_notes','operatorglobalsettings']);

        $queue_result_model = $this->queue_result;
        $player_model = $this->player_model;
        $wallet_model = $this->wallet_model;
        $transactions = $this->transactions;
        $payment_manager = $this->payment_manager; //already loaded , dont load
        $withdraw_condition = $this->withdraw_condition;
        $transaction_notes = $this->transaction_notes;
        $operatorglobalsettings = $this->operatorglobalsettings;
        $controller = $this;

        $settingsStr = $operatorglobalsettings->getSettingValueWithoutCache(Group_level::CASHBACK_SETTINGS_NAME);

        $cashbackSettings = (object) array();
        if (!empty($settingsStr)) {
            $cashbackSettings = json_decode($settingsStr);
            if (empty($cashbackSettings)) {
                $cashbackSettings = (object) array();
            }
        }

        if(!isset($cashbackSettings->min_cashback_amount)){
            $cashbackSettings->min_cashback_amount=$this->getMiniCashbackAmount();
        }

        $data = $controller->initJobData($token);
        $params = $data['params'];
        $uploadCsvFilepath=$controller->utils->getSharingUploadPath('/upload_temp_csv');
        $csv_file = rtrim($uploadCsvFilepath, '/').'/'.$params['file'];
        //$totalCount = ($this->utils->countRowFromCSV($csv_file,$message));//note: this util func should always be  $file->key() + 1 if all  -not working properly of theres last blank lines
        $fp = file($csv_file);// this one works
        $totalCount =  count($fp) - 1;

        if(!file_exists($csv_file)){
            $rlt=['success'=>false, 'failCount'=>0, 'errorDetail'=>'CSV file is not exist', 'failedList' =>0,  'successCount'=>0,  'processedRows' => 0, 'totalCount' => $totalCount, 'progress' => 0];
            $queue_result_model->failedResult($token, $rlt);
            return $controller->utils->error_log("File not exist!");
        }
        $csv_headers = [lang('username'), lang('cashback_amount'), lang('withdrawal_condition_amount')];

        //prepare logs
        $message_log = '';
        $csv_logs_header = ['username','reason','cashback_amount'];
        $funcName = __FUNCTION__;
        $controller->utils-> _appendSaveDetailedResultToRemoteLog($token, $funcName.'_failed_results', $message_log, $failed_log_filepath, true, $csv_logs_header);

        // start process
        $state = array('processId'=>getmypid());
        $rlt=['success'=>false, 'failCount'=>0,/*'failedList' =>0,*/ 'failed_log_filepath' => site_url().'remote_logs/'.basename($failed_log_filepath),   'successCount'=>0,  'processedRows' => 0, 'totalCount' => $totalCount, 'progress' => 0];
        $queue_result_model->updateResultRunning($token, [], $state);

        $adminUserId = $params['adminUserId'];
        $adminUsername = $params['adminUsername'];
        $reason = $params['reason'];
        $adjustment_type = Transactions::AUTO_ADD_CASHBACK_TO_BALANCE;
        $action_name = 'Add manually cashback';
        $playerMap = $player_model->getPlayerUsernameIdMap();
        $max_error_to_stop = $controller->utils->getConfig('remote_batch_add_cashback_bonus_max_error_stop');//100

        $count_loop = 0;
        $failCount = 0;
        $successCount = 0;
        $failedList = [] ;
        $percentage_steps = [];

        for ($i=.1; $i <= 10 ; $i +=.1) {
            array_push($percentage_steps, ceil($i/10 * $totalCount));
        };
        $ignore_first_row = true;

        $controller->utils->loopCSV($csv_file, $ignore_first_row, $cnt, $message,
            function($cnt, $csv_row, $stop_flag)
            use(
                $controller, $queue_result_model, $player_model, $wallet_model,$transactions,$payment_manager,$withdraw_condition,
                $transaction_notes, $token,$state,$percentage_steps,$playerMap,&$count_loop, &$failCount,&$successCount,&$totalCount,&$failedList,
                $csv_headers, $action_name, $adjustment_type, $adminUserId, $adminUsername,$reason,$cashbackSettings,$max_error_to_stop,$funcName,$failed_log_filepath
            ) {
                // if(count($csv_row) == 0){
                //     $totalCount--;
                // }
                print_r($csv_row);
                 print_r($cnt);               // $count_loop++;
                $row = null;
                $success = false;

                if(count($csv_headers) == count($csv_row)){
                    $row = array_combine($csv_headers, $csv_row);
                }else{
                    $rlt=['success'=>false, 'errorDetail'=>'CSV number of columns not tallied with the uploaded file', 'failCount'=>0,/*'failedList' =>0, */ 'failed_log_filepath' => site_url().'remote_logs/'.basename($failed_log_filepath), 'successCount'=>0,  'processedRows' => $count_loop, 'totalCount' => $totalCount, 'progress' => 0];
                    $queue_result_model->failedResult($token, $rlt);
              // $stop_flag = true;
              // exit;
                    return  $controller->utils->error_log("Columns not Matched!", 'csv_headers',$csv_headers,'csv_row',$csv_row);
                }

                $playerId = null;
                $username = null;
                if(isset($playerMap[$row['username']])){
                    $playerId = $playerMap[$row['username']];
                    $username = $row['username'];
                }

                $controller->utils->info_log("csv_row", $csv_row,'count_loop', $count_loop,'totalCount', $totalCount);

                if(empty($playerId)){
                   $failCount++;
                   $controller->utils->error_log("PLAYER NOT EXIST", $row);
                  // array_push($failedList, ['username'=> isset($row['username']) ? $row['username'] : '','reason'=>'Player not exist']);
                   $message_log = ['username'=> isset($row['username']) ? $row['username'] : '','reason'=>'Player not exist'];
                   $controller->utils-> _appendSaveDetailedResultToRemoteLog($token, $funcName.'_failed_results', $message_log, $failed_log_filepath, true, []);
               }
               $cashback_amount = isset($row['cashback_amount']) ? $row['cashback_amount'] : 0;
               $withdrawal_condition_amount = isset($row['withdrawal_condition_amount']) ? $row['withdrawal_condition_amount'] : 0;

           //start adjustnment--------------------------------------------------------------------

               if(!empty($playerId)){

                  $is_cashback_in_min_and_max = false;

                 if($cashback_amount >= $cashbackSettings->min_cashback_amount && $cashback_amount <= $cashbackSettings->max_cashback_amount){
                   $is_cashback_in_min_and_max = true;
                  }

                  if(!$is_cashback_in_min_and_max){
                    $failCount++;
                    $controller->utils->error_log("Cashback amount not in range", $row);
                      // array_push($failedList, ['username'=> isset($row['username']) ? $row['username'] : '','reason'=>'Cashback amount of '.$cashback_amount.' not in range (min:'.$cashbackSettings->min_cashback_amount.' max:'.$cashbackSettings->max_cashback_amount.')', 'cashback_amount' => $cashback_amount]);
                    $message_log = ['username'=> isset($row['username']) ? $row['username'] : '','reason'=>'Cashback amount of '.$cashback_amount.' not in range (min:'.$cashbackSettings->min_cashback_amount.' max:'.$cashbackSettings->max_cashback_amount.')', 'cashback_amount' => $cashback_amount];
                    $controller->utils-> _appendSaveDetailedResultToRemoteLog($token, $funcName.'_failed_results', $message_log, $failed_log_filepath, true, []);
                 }

                 if($is_cashback_in_min_and_max){

                    $lockedKey=null;
                    $lock_it = $controller->lockPlayerBalanceResource($playerId, $lockedKey);
                    try {
                        if ($lock_it) {

                            $controller->startTrans();
                            // DONT INCLUDE PROMO
                            $betTimes = null;
                            $deposit_amount = null;
                            $promo_category = null;
                            $show_in_front_end = null;
                            $promoRuleId = null;
                            $adjustment_category_id = null;
                            $promorule = null;
                            // DONT INCLUDE PROMO
                            $totalBeforeBalance = $wallet_model->getTotalBalance($playerId);
                            $before_adjustment = $player_model->getMainWalletBalance($playerId);
                            $after_adjustment = $before_adjustment + $cashback_amount;
                            $controller->utils->debug_log('player_id', $playerId, 'totalBeforeBalance', $totalBeforeBalance, 'after_adjustment',$after_adjustment);
                            $wallet_name =  'Main Wallet';
                            $note = sprintf('%s <b>%s</b> balance to <b>%s</b>\'s <b>%s</b>(<b>%s</b> to <b>%s</b>) by <b>%s</b>, <b>%s</b> ',
                                $action_name, number_format($cashback_amount, 2), $username, $wallet_name,
                                number_format($before_adjustment, 2), number_format($after_adjustment, 2),
                                $username, '');
                            $transaction = $transactions->createAdjustmentTransaction($adjustment_type,
                                $adminUserId, $playerId, $cashback_amount, $before_adjustment, $note, $totalBeforeBalance,
                                $promo_category, $show_in_front_end, $reason, $promoRuleId, $adjustment_category_id, Transactions::MANUALLY_ADJUSTED);
                            if (!$transaction) {
                                $failCount++;
                                //array_push($failedList, ['username'=> $username,'reason'=>'Transaction not created']);
                                $message_log = ['username'=> $username,'reason'=>'Transaction not created'];
                                $controller->utils-> _appendSaveDetailedResultToRemoteLog($token, $funcName.'_failed_results', $message_log, $failed_log_filepath, true, []);
                                $this->rollbackTrans();  //quit
                            }else{
                                $adjust_data = [
                                    'playerId' => $transaction['to_id'],
                                    'adjustmentType' => $transaction['transaction_type'],
                                    'walletType' => 0, # 0 - MAIN WALLET
                                    'amountChanged' => $transaction['amount'],
                                    'oldBalance' => $transaction['before_balance'],
                                    'newBalance' => $transaction['after_balance'],
                                    'reason' => $reason,
                                    'adjustedOn' => $transaction['created_at'],
                                    'adjustedBy' => $transaction['from_id'],
                                    'show_flag' => $show_in_front_end == '1',
                                ];
                                $payment_manager->addPlayerBalAdjustmentHistory($adjust_data);
                                if (!empty($transaction['id'])) {
                                    $transaction_notes->add($reason, $adminUserId, $adjustment_type, $transaction['id']);
                                }
                                $bonusTransId=$transaction['id'];
                                $withdrawalConditionId = $controller->withdraw_condition->createWithdrawConditionForManual($playerId, $bonusTransId,
                                    $withdrawal_condition_amount, $deposit_amount, $cashback_amount, $betTimes, $promorule,$reason);
                            }
                            $success = $controller->endTransWithSucc();
                             //success = if  theres any error during promo request and settled transaction
                            if($success){
                                $successCount++;
                            }else{
                                $failCount++;
                               // array_push($failedList, ['username'=> $row['username'],'reason'=>'Trans error']);
                                $message_log = ['username'=> $row['username'],'reason'=>'Trans error'];
                                $controller->utils-> _appendSaveDetailedResultToRemoteLog($token, $funcName.'_failed_results', $message_log, $failed_log_filepath, true, []);
                            }
                        }//lockit end
                    } finally {
                       $controller->releasePlayerBalanceResource($playerId, $lockedKey);
                   }

                }


            }//end check empty playerId
            $count_loop++;
            //update front end progress
            $rlt=['success'=>false, 'failCount'=> $failCount,/*'failedList' => $failedList,*/ 'failed_log_filepath' => site_url().'remote_logs/'.basename($failed_log_filepath), 'successCount'=> $successCount,  'processedRows' => $count_loop, 'totalCount' => $totalCount, 'progress' => ceil($count_loop/$totalCount * 100)];
            $queue_result_model->updateResultRunning($token, $rlt, $state);

           //  if($failCount >= $max_error_to_stop){
           //    $rlt=['success'=>false, 'failCount'=> $failCount,'failedList' => $failedList, 'errorDetail'=>'To much error! Please inspect your CSV first','successCount'=> $successCount,  'processedRows' => $count_loop, 'totalCount' => $totalCount, 'progress' => ceil($count_loop/$totalCount * 100)];
           //    $queue_result_model->failedResult($token, $rlt);
           //    $controller->utils->error_log("Too much error! Please inspect your CSV first");
           //    exit; // return not working, it continues
           // }

            if($count_loop == $totalCount){
                $controller->utils->info_log('count_loop == totalCount',$count_loop == $totalCount);
            //update last - Done
                $rlt=['success'=>true, 'failCount'=>$failCount,/*'failedList' => $failedList,*/ 'failed_log_filepath' => site_url().'remote_logs/'.basename($failed_log_filepath), 'successCount'=> $successCount,  'processedRows' => $count_loop, 'totalCount' => $totalCount, 'progress' => 100];
                $queue_result_model->updateResult($token, $rlt);
            //end adjustnment-------------------------------------------------------------------
            }

        });//loop csv;
        $successCount = $totalCount - $failCount;
        $controller->utils->debug_log("Import manually_batch_add_cashback_bonus, [$successCount] out of [$totalCount] succeed.  failed_log_filepath: ". $failed_log_filepath);
    }

    protected function initEventFromParams($token, array $params){
        $eventInfo=$params['event'];
        $eventClass=$eventInfo['class'];
        require_once dirname(__FILE__) . "/../../models/events/".$eventClass.".php";
        $event=new $eventClass($token, $eventInfo['name'], $eventInfo['data']);
        return $event;
    }

    public function exporting_csv_from_queue($token) {
        $this->utils->info_log('start exporting');

        $this->load->model(['queue_result']);
        $data=$this->initJobData($token);

        // $CI->utils->debug_log('workload data', $data);
        // $CI->load->model(array('queue_result'));
        // $CI->queue_result->reconnectDB();

        $token = $data['token'];
        $params = $data['params'];
        $event=$this->initEventFromParams($token, $params);
        $exitCode=0;

        //find extra_search and inject token
        $rlt=['success'=>false, 'filename'=>null];
        $this->utils->debug_log('paramms',$params);
        if(!empty($event)){
            try{

                // $this->utils->debug_log('params', $params, $this->utils->decodeJson($params));

                // $params=$this->utils->decodeJson($params);

                $modelName=$event->getExportingModel();
                $funcName=$event->getExportingFunc();
                //only pass event
                $this->utils->debug_log('call '.$modelName.'->'.$funcName, 'event', $event);
                $this->load->model([$modelName]);

                // $CI->load->model(['report_model']);
                $result = call_user_func([$this->$modelName, $funcName], $event);
                $this->utils->info_log('result of exporting function', $result);
                $success=$result['success'];

                // $rlt=['success'=>$success, 'csv_download_link'=>$result['csv_download_link']];
                // if(!$success){
                //     $this->queue_result->appendResult($token, $rlt, true, true);
                // }else{
                //     $this->queue_result->appendResult($token, $rlt, true);
                // }
                // $token, $success, $message,$progress, $total, $done, array $extra=[]
                $message='';
                $progress=100;
                $total=100;
                $done=true;
                $this->queue_result->updateFinalResult($token, $success, $message, $progress, $total, $done,
                    $result['csv_download_link']);

                $this->utils->debug_log('result '.$funcName, $result);

            }catch(Exception $e){
                $this->utils->error_log('[ERROR] do_export_csv_job error', $e);
                $exitCode=1;
                // $CI->utils->writeQueueErrorLog($e->getTraceAsString());
            }
        }else{
            $this->utils->error_log('[ERROR] do_export_csv_job error: canot call empty function');
            $exitCode=2;
        }

        $this->utils->info_log('end exporting');

        exit($exitCode);
    }

    public function service_status_checker(){

        //5 minutes
        set_time_limit(300);
        $startTime=time();
        $this->load->library(['lib_queue']);
        $this->load->model(['queue_result']);
        //check queue server
        $rabbitmq_server=$this->utils->getConfig('rabbitmq_server');
        //missing config
        if(empty($rabbitmq_server)){
            $this->utils->sendAlertBack('warning', 'empty config of rabbitmq', 'no any config for rabbitmq server');
            return false;
        }
        $this->utils->info_log('pass config rabbitmq_server');
        //try connect rabbitmq
        $rabbitmq_host=isset($rabbitmq_server['host']) ? $rabbitmq_server['host'] : null;
        $rabbitmq_port=isset($rabbitmq_server['port']) ? $rabbitmq_server['port'] : null;
        $rabbitmq_username=isset($rabbitmq_server['username']) ? $rabbitmq_server['username'] : null;
        $rabbitmq_password=isset($rabbitmq_server['password']) ? $rabbitmq_server['password'] : null;
        $online=false;
        try{
            $rabbitmq_connection = new AMQPStreamConnection($rabbitmq_host, $rabbitmq_port, $rabbitmq_username, $rabbitmq_password);
            $rabbitmq_channel = $rabbitmq_connection->channel();
            $online=!empty($rabbitmq_connection) && !empty($rabbitmq_channel);
        }catch(Exception $e){
            $online=false;
            $this->utils->error_log('connect rabbitmq failed', $rabbitmq_server, $e);
        }

        if(!$online){
            $this->utils->sendAlertBack('error', 'cannot connect to rabbitmq',
                'cannot connect to rabbitmq. settings: '.var_export($rabbitmq_server, true));
            return false;
        }
        try{
            //close rabbitmq
            $rabbitmq_channel->close();
            $rabbitmq_channel=null;
            $rabbitmq_connection->close();
            $rabbitmq_connection=null;
        }catch(Exception $e){
            $this->utils->error_log('close rabbitmq failed', $rabbitmq_server, $e);
        }
        $this->utils->info_log('pass connect rabbitmq_server', $rabbitmq_server);

        $this->run_service_status_checker($this->db, $startTime, $rabbitmq_server);
    }

    protected function run_service_status_checker($db, $startTime, $rabbitmq_server){

        if(!$this->utils->getConfig('enabled_service_status_checker')){
            return;
        }

        //check event
        $callerType=Queue_result::CALLER_TYPE_SYSTEM;
        $caller=Queue_result::SYSTEM_UNKNOWN;
        $state=null;
        $token=$this->lib_queue->triggerAsyncRemoteMonitorHeartBeatEvent($callerType, $caller, $state);
        //waiting 2 minutes ?
        if(empty($token)){
            $this->utils->sendAlertBack('error', 'cannot trigger monitor event',
                'cannot trigger monitor event. settings: '.var_export($rabbitmq_server, true));
            return false;
        }
        $this->utils->info_log('pass sending event', $token);
        $id=$this->queue_result->getIdByToken($token);
        $monitor_event_timeout_seconds=$this->utils->getConfig('monitor_event_timeout_seconds');
        $foundResult=false;
        $finalResult=null;
        for ($i=0; $i < $monitor_event_timeout_seconds; $i++) {
            sleep(1);
            //check response
            $finalResult=$this->queue_result->getFinalResultById($id);
            if(!empty($finalResult)){
                $foundResult=true;
                $this->utils->debug_log('found final result', $id, $finalResult);
                break;
            }
            $this->utils->debug_log('not found final result', $id);
        }
        if(!$foundResult){
            $this->utils->sendAlertBack('error', 'cannot get monitor event result',
                'cannot get monitor event result. settings: '.var_export($rabbitmq_server, true).', token:'.$token.', request id: '.$this->utils->getRequestId().' , id:'.$id);
            return false;
        }
        $this->utils->info_log('pass testing monitor event', $id, $finalResult);
        //summary
        $lastTime=$finalResult['extra']['header_beat_time'];
        $last=strtotime($lastTime);
        $d=$last-$startTime;
        $this->utils->info_log('cost time: '.$d, $startTime, $last);

        return true;
    }

    public function update_player_benefit_fee_job($token)
    {
        $this->load->library(array('authentication'));
        $this->load->model(array('queue_result'));
        $this->load->library(array('lib_queue','affiliate_commission'));
        try{
            $queue_result_model = $this->queue_result;
            $data = $this->initJobData($token);
            $this->utils->debug_log('run update_player_benefit_fee_job', $data);
    
            $queue_result_model->appendResult($token, [
                'message' => 'start job',
                'params' => $data['params']
            ]);
            
            $params = [];
            if (isset($data['params']) && !empty($data['params'])) {
                $params = $data['params'];
            }
    
            //params refer to updatePlayerBenefitFeeForOneByQueue
            $yearmonth = $params['yearmonth'];
            $affiliate_username = $params['affiliate_username'];
            $operator = $params['operator'];
    
            $queue_result_model->appendResult($token, [
                'message' => 'processing generate_monthly_earnings',
            ]);
            $success = $this->affiliate_commission->generate_monthly_earnings_for_all($yearmonth, $affiliate_username, null, null, 0, true);
            if (!$success) {
                throw new Exception('generate_monthly_earnings_for_all failed');
            }

            $queue_result_model->appendResult($token, [
                'success'=>true,
                'message' => lang('Update Successfully')
            ],true, false);

        } catch (Exception $e) {
            $queue_result_model->appendResult($token, [
                'success'=>true,
                'message' => lang('Update Failed')
            ],true, true);
            $this->utils->error_log('update_player_benefit_fee_job', $e);
        }
    }

    public function remote_batch_benefit_fee_adjustment($token){

        $this->load->model(['queue_result','affiliatemodel','affiliate_earnings']);
        $this->load->library(array('affiliate_manager', 'affiliate_commission'));

        $queue_result_model = $this->queue_result;
        $affiliatemodel = $this->affiliatemodel;
        $affiliate_manager = $this->affiliate_manager;
        $affiliate_commission = $this->affiliate_commission;
        $affiliate_earnings = $this->affiliate_earnings;
        $controller = $this;

        $data = $controller->initJobData($token);
        $params = $data['params'];
        $uploadCsvFilepath=$controller->utils->getSharingUploadPath('/upload_temp_csv');
        $csv_file = rtrim($uploadCsvFilepath, '/').'/'.$params['file'];
        //$totalCount = ($this->utils->countRowFromCSV($csv_file,$message));//note: this util func should always be  $file->key() + 1 if all  -not working properly of theres last blank lines
        $fp = file($csv_file);// this one works
        $totalCount =  count($fp) - 1;

        if(!file_exists($csv_file)){
            $rlt=['success'=>false, 'failCount'=>0, 'errorDetail'=>'CSV file is not exist', 'failedList' =>0,  'successCount'=>0,  'processedRows' => 0, 'totalCount' => $totalCount, 'progress' => 0];
            $queue_result_model->failedResult($token, $rlt);
            return $controller->utils->error_log("File not exist!");
        }
        $csv_headers = [lang('username'), lang('benefit_fee')];

        //prepare logs
        $message_log = '';
        $csv_logs_header = ['username','reason', $params['yearmonth']];
        $funcName = __FUNCTION__;
        $controller->utils-> _appendSaveDetailedResultToRemoteLog($token, $funcName.'_failed_results', $message_log, $failed_log_filepath, true, $csv_logs_header);

        // start process
        $state = array('processId'=>getmypid());
        $rlt=['success'=>false, 'failCount'=>0,/*'failedList' =>0,*/ 'failed_log_filepath' => site_url().'remote_logs/'.basename($failed_log_filepath),   'successCount'=>0,  'processedRows' => 0, 'totalCount' => $totalCount, 'progress' => 0];
        $queue_result_model->updateResultRunning($token, [], $state);

        $affiliateMap = $affiliatemodel->getAffiliateMap();
        $adminUserId = $params['adminUserId'];
        $adminUsername = $params['adminUsername'];
        $reason = $params['reason'];
        $yearmonth = $params['yearmonth'];
        $action_name = 'Batch Benefit Fee Adjustment';

        $count_loop = 0;
        $failCount = 0;
        $successCount = 0;
        $failedList = [] ;
        $percentage_steps = [];

        for ($i=.1; $i <= 10 ; $i +=.1) {
            array_push($percentage_steps, ceil($i/10 * $totalCount));
        };
        $ignore_first_row = true;

        $controller->utils->loopCSV($csv_file, $ignore_first_row, $cnt, $message,
            function($cnt, $csv_row, $stop_flag)
            use(
                $controller, $queue_result_model, $affiliatemodel, $affiliate_manager, $affiliate_commission,  $affiliate_earnings, $token,$state,$percentage_steps,&$count_loop, &$failCount,
                &$successCount,&$totalCount,&$failedList,
                $csv_headers, $action_name, $affiliateMap, $adminUserId, $adminUsername, $reason, $yearmonth, $funcName, $failed_log_filepath
            ) {
                // if(count($csv_row) == 0){
                //     $totalCount--;
                // }
                print_r($csv_row);
                 print_r($cnt);               // $count_loop++;
                $row = null;
                $success = false;

                if(count($csv_headers) == count($csv_row)){
                    $row = array_combine($csv_headers, $csv_row);
                }else{
                    $rlt=['success'=>false, 'errorDetail'=>'CSV number of columns not tallied with the uploaded file', 'failCount'=>0,/*'failedList' =>0, */ 'failed_log_filepath' => site_url().'remote_logs/'.basename($failed_log_filepath), 'successCount'=>0,  'processedRows' => $count_loop, 'totalCount' => $totalCount, 'progress' => 0];
                    $queue_result_model->failedResult($token, $rlt);
              // $stop_flag = true;
              // exit;
                    return  $controller->utils->error_log("Columns not Matched!", 'csv_headers',$csv_headers,'csv_row',$csv_row);
                }

                $affiliate_id = null;
                $username = null;
                if (isset($affiliateMap[$row['username']])) {
                    $affiliate_id = $affiliateMap[$row['username']];
                    $username = $row['username'];
                }

                $controller->utils->info_log("csv_row", $csv_row,'count_loop', $count_loop,'totalCount', $totalCount);

                if(empty($affiliate_id)){
                   $failCount++;
                   $controller->utils->error_log("AFFILIATE NOT EXIST", $row);
                  // array_push($failedList, ['username'=> isset($row['username']) ? $row['username'] : '','reason'=>'Player not exist']);
                   $message_log = ['username'=> isset($row['username']) ? $row['username'] : '','reason'=>'Affiliate not exist'];
                   $controller->utils-> _appendSaveDetailedResultToRemoteLog($token, $funcName.'_failed_results', $message_log, $failed_log_filepath, true, []);
               }

           //start adjustnment--------------------------------------------------------------------

               if(!empty($affiliate_id)){
                $skip = false;
                $affiliate_commission_record = $affiliate_earnings->getAffiliateMonthlyCommissionByYearmonthAndAffid($affiliate_id, $yearmonth);
                if(empty($affiliate_commission_record)) {
                    $skip = true;
                    $failCount++;
                    $message_log = ['username'=> $row['username'],'reason'=>'commission record not found'];
                    $controller->utils-> _appendSaveDetailedResultToRemoteLog($token, $funcName.'_failed_results', $message_log, $failed_log_filepath, true, []);

                } else {
                    if($affiliate_commission_record['paid_flag'] == 1){
                        $skip = true;
                        $failCount++;
                        $message_log = ['username'=> $row['username'],'reason'=>'status paid'];
                        $controller->utils-> _appendSaveDetailedResultToRemoteLog($token, $funcName.'_failed_results', $message_log, $failed_log_filepath, true, []);
                    }
                }
                if(!$skip) {

                    $lockedKey=null;
                    $lock_it = $controller->lockPlayerBalanceResource($affiliate_id, $lockedKey);
                    try {
                        if ($lock_it) {

                            $controller->startTrans();
                            $success = $affiliatemodel->updatePlayerBenefitFee($affiliate_id, $yearmonth, $row['benefit_fee'], true, 'import from CSV'. $reason);
                            if(!$success){
                                $failCount++;
                                $message_log = ['username'=> $username,'reason'=>'run updatePlayerBenefitFee fail'];
                                $controller->utils-> _appendSaveDetailedResultToRemoteLog($token, $funcName.'_failed_results', $message_log, $failed_log_filepath, true, []);

                                $this->rollbackTrans();  //quit
                            }
                            $success = $controller->endTransWithSucc();
                            if (!$success) {
                                $failCount++;
                                $message_log = ['username'=> $row['username'],'reason'=>'Trans error'];
                                $controller->utils-> _appendSaveDetailedResultToRemoteLog($token, $funcName.'_failed_results', $message_log, $failed_log_filepath, true, []);
                            }

                            if($success) {
                                $success = $affiliate_commission->generate_monthly_earnings_for_all($yearmonth, $username);
                                if ($success) {
                                    $successCount++;
                                } else {
                                    $failCount++;
                                    $message_log = ['username'=> $username,'reason'=>'regenerate monthly earnings fail'];
                                    $controller->utils-> _appendSaveDetailedResultToRemoteLog($token, $funcName.'_failed_results', $message_log, $failed_log_filepath, true, []);
                                }
                            }
                        }//lockit end
                    } finally {
                        $controller->releasePlayerBalanceResource($affiliate_id, $lockedKey);
                    }
                }
            }//end check empty playerId
            $count_loop++;
            //update front end progress
            $rlt=['success'=>false, 'failCount'=> $failCount,/*'failedList' => $failedList,*/ 'failed_log_filepath' => site_url().'remote_logs/'.basename($failed_log_filepath), 'successCount'=> $successCount,  'processedRows' => $count_loop, 'totalCount' => $totalCount, 'progress' => ceil($count_loop/$totalCount * 100)];
            $queue_result_model->updateResultRunning($token, $rlt, $state);

            if($count_loop == $totalCount){
                $controller->utils->info_log('count_loop == totalCount',$count_loop == $totalCount);
            //update last - Done
                $rlt=['success'=>true, 'failCount'=>$failCount,/*'failedList' => $failedList,*/ 'failed_log_filepath' => site_url().'remote_logs/'.basename($failed_log_filepath), 'successCount'=> $successCount,  'processedRows' => $count_loop, 'totalCount' => $totalCount, 'progress' => 100];
                $queue_result_model->updateResult($token, $rlt);
            //end adjustnment-------------------------------------------------------------------
            }

        });//loop csv;
        $successCount = $totalCount - $failCount;
        $controller->utils->debug_log("Import batch_benefit_fee_adjustment, [$successCount] out of [$totalCount] succeed.  failed_log_filepath: ". $failed_log_filepath);
    }

    public function remote_batch_subtract_bonus_by_queue($token){
        $this->load->model(['queue_result', 'users', 'player_model', 'transactions']);
        $cnt = 0;
        $playerCnt=0;
        $success_amount = 0;
        $successCnt =0;
        $failedCnt =0;
        $count_loop=0;
        $controller=$this;
        $queue_result_model = $this->queue_result;

        //load from token
        $data = $this->initJobData($token);
        $token = $data['token'];
        $params = $data['params'];
        $this->utils->debug_log('load from queue:', $token, $params, 'JobData:', $data);

        $reason=$params['reason'];
        $adminUserId=$data['caller'];

        $uploadCsvFilepath = $this->utils->getSharingUploadPath('/upload_temp_csv');
        $csv_file = rtrim($uploadCsvFilepath, '/').'/'.$params['file'];
        $fp = file($csv_file);
        $totalCount =  count($fp) - 1;

        if(!file_exists($csv_file)){
            $rlt = ['success'=>false, 'failedCnt'=>0, 'errorDetail'=>'CSV file is not exist', 'failedList'=>0, 'successCount'=>0, 'processedRows'=> 0, 'totalCount'=> $totalCount, 'progress'=> 0];
            $this->queue_result->failedResult($token, $rlt);
            return $this->utils->error_log("File not exist!");
        }

        $funcName = __FUNCTION__;
        $message_log ='';
        $failed_log_filepath = '';
        $csv_logs_header = [lang('username'), lang('pay.amt'), lang('status'), lang('message')];
        $controller->utils->_appendSaveDetailedResultToRemoteLog($token, $funcName . '_failed_results', $message_log, $failed_log_filepath, true, $csv_logs_header);

        $adminUsername=$this->users->getUsernameById($adminUserId);
        $wallet_name = 'Main Wallet';
        $show_in_front_end = null;
        $ignore_first_row = true;
        $message = '';

        $this->utils->loopCSV($csv_file, $ignore_first_row, $cnt, $message,
            function($cnt, $csv_row, $stop_flag) use($controller, $queue_result_model, $show_in_front_end, $adminUserId,
                $adminUsername, $reason, $token, $wallet_name, $funcName, &$message, &$success_amount, &$failed_log_filepath,
                &$successCnt, &$failedCnt, &$playerCnt, &$totalCount, &$count_loop){

                //process one row
                //print_r($csv_row);
                //print_r($cnt);
                $playerCnt++;

                $player_name    = strval($csv_row[0]);
                $amount         = floatval($csv_row[1]);

                $player_id=$this->player_model->getPlayerIdByUsername($player_name);

                $controller->utils->info_log("csv_row", $csv_row, "count_loop", $count_loop, "totalCount", $totalCount);

                if(empty($player_id)){
                    $this->utils->debug_log('==============player not exist', $player_name);
                    $message_log = [$player_name, $amount, lang('Failed'), lang('Player does not exist')];
                    $controller->utils->_appendSaveDetailedResultToRemoteLog($token, $funcName . '_failed_results', $message_log, $failed_log_filepath, true, []);
                    $failedCnt++;
                    $count_loop++;

                    if(!empty($token)){
                        $rlt=['success'=>false, 'failedCnt'=>$failedCnt, 'successCnt'=>$successCnt, 'successAmt'=>$success_amount, 'playerCnt'=>$playerCnt,
                            'processedRows'=>$count_loop, 'totalCount'=>$totalCount, 'progress'=>ceil($count_loop/$totalCount * 100), 'failed_log_filepath'=>site_url().'remote_logs/'.basename($failed_log_filepath)];
                        $queue_result_model->updateResultRunning($token, $rlt);
                    }
                }else{
                    $success = $this->lockAndTransForPlayerBalance($player_id, function()
                    use($controller, $queue_result_model, $show_in_front_end, $adminUserId,
                        $adminUsername, $player_id, $player_name, $wallet_name, $reason, $amount,
                        $token, $funcName, &$message, &$failed_log_filepath, &$count_loop) {

                            $rlt = false;
                            $totalBeforeBalance = $this->wallet_model->getTotalBalance($player_id);
                            $this->utils->debug_log('player_id', $player_id, 'totalBeforeBalance', $totalBeforeBalance);

                            $before_adjustment = $this->player_model->getMainWalletBalance($player_id);
                            $after_adjustment = $before_adjustment - $amount;
                            $action_name = 'Subtract';

                            if ($after_adjustment < 0) {
                                $this->utils->debug_log('==============player balance is not enough ', $player_name);
                                $message_log = [$player_name, $amount, lang('Failed'), lang('No enough balance')];
                                $controller->utils->_appendSaveDetailedResultToRemoteLog($token, $funcName . '_failed_results', $message_log, $failed_log_filepath, true, []);
                                return $rlt;
                            }

                            $note = sprintf('%s <b>%s</b> balance to <b>%s</b>\'s <b>%s</b>(<b>%s</b> to <b>%s</b>) by <b>%s</b>',
                                $action_name, number_format($amount, 2), $player_name, $wallet_name,
                                number_format($before_adjustment, 2), number_format($after_adjustment, 2),
                                $adminUsername);
                            $note_reason = sprintf('<i>Reason:</i> %s <br>',$reason);
                            $note = (trim($reason) != '')  ? ($note_reason . sprintf('<i>Normal Note:</i> %s <br>',$note)) : $note;


                            $transaction = $this->transactions->createAdjustmentTransaction(Transactions::SUBTRACT_BONUS,
                                $adminUserId, $player_id, $amount, null, $note, null,
                                null, $show_in_front_end, $reason, null, null, Transactions::MANUALLY_ADJUSTED);

                            if(!!$transaction){
                                $this->transactions->addPlayerBalAdjustmentHistory(array(
                                    'playerId' => $transaction['to_id'],
                                    'adjustmentType' => $transaction['transaction_type'],
                                    'walletType' => 0, # 0 - MAIN WALLET
                                    'amountChanged' => $transaction['amount'],
                                    'oldBalance' => $transaction['before_balance'],
                                    'newBalance' => $transaction['after_balance'],
                                    'reason' => $reason,
                                    'adjustedOn' => $transaction['created_at'],
                                    'adjustedBy' => $transaction['from_id'],
                                    'show_flag' => $show_in_front_end == '1',
                                ));

                                $rlt = true;
                            }

                            if(!$rlt){
                                $message_log = [$player_name, $amount, lang('Failed'), lang('Trans error')];
                                $controller->utils->_appendSaveDetailedResultToRemoteLog($token, $funcName . '_failed_results', $message_log, $failed_log_filepath, true, []);
                            }

                            return $rlt;
                        }
                    );//lockAndTransForPlayerBalance

                    if($success){
                        $success_amount += $amount;
                        $successCnt++;
                    }else{
                        $failedCnt++;
                    }

                    $count_loop++;

                    //update front end progress
                    $rlt=['success'=>false, 'failedCnt'=>$failedCnt, 'successCnt'=>$successCnt, 'successAmt'=>$success_amount, 'playerCnt'=>$playerCnt,
                        'processedRows'=>$count_loop, 'totalCount'=>$totalCount, 'progress'=>ceil($count_loop/$totalCount * 100), 'failed_log_filepath'=>site_url().'remote_logs/'.basename($failed_log_filepath)];
                    $queue_result_model->updateResultRunning($token, $rlt);
                } // check empty player id

                if($count_loop == $totalCount){
                    $this->utils->info_log('count_loop == totalCount',$count_loop == $totalCount);
                    //update last - Done
                    $rlt=['success'=>true, 'failedCnt'=>$failedCnt, 'successCnt'=> $successCnt, 'playerCnt'=>$playerCnt, 'successAmt'=>$success_amount,
                        'processedRows'=>$count_loop, 'totalCount'=>$totalCount, 'progress'=>100, 'failed_log_filepath'=>site_url().'remote_logs/'.basename($failed_log_filepath)];
                    $queue_result_model->updateResult($token, $rlt);
                }

            } // end loop callback
        ); //end loop csv

        $this->utils->debug_log("import_csv_subtract_bonus_anyfile, total->[$totalCount], succeed->[$successCnt], failed->[$failedCnt].  failed_log_filepath: " . $failed_log_filepath);
    }


    public function remote_batch_addon_platform_fee_adjustment($token){

        $this->load->model(['queue_result','affiliatemodel','affiliate_earnings']);
        $this->load->library(array('affiliate_manager', 'affiliate_commission'));

        $queue_result_model = $this->queue_result;
        $affiliatemodel = $this->affiliatemodel;
        $affiliate_manager = $this->affiliate_manager;
        $affiliate_commission = $this->affiliate_commission;
        $affiliate_earnings = $this->affiliate_earnings;
        $controller = $this;

        $data = $controller->initJobData($token);
        $params = $data['params'];
        $uploadCsvFilepath=$controller->utils->getSharingUploadPath('/upload_temp_csv');
        $csv_file = rtrim($uploadCsvFilepath, '/').'/'.$params['file'];
        //$totalCount = ($this->utils->countRowFromCSV($csv_file,$message));//note: this util func should always be  $file->key() + 1 if all  -not working properly of theres last blank lines
        $fp = file($csv_file);// this one works
        $totalCount =  count($fp) - 1;

        if(!file_exists($csv_file)){
            $rlt=['success'=>false, 'failCount'=>0, 'errorDetail'=>'CSV file is not exist', 'failedList' =>0,  'successCount'=>0,  'processedRows' => 0, 'totalCount' => $totalCount, 'progress' => 0];
            $queue_result_model->failedResult($token, $rlt);
            return $controller->utils->error_log("File not exist!");
        }
        $csv_headers = [lang('username'), lang('platform_fee')];

        //prepare logs
        $message_log = '';
        $csv_logs_header = ['username','reason', $params['yearmonth']];
        $funcName = __FUNCTION__;
        $controller->utils-> _appendSaveDetailedResultToRemoteLog($token, $funcName.'_failed_results', $message_log, $failed_log_filepath, true, $csv_logs_header);

        // start process
        $state = array('processId'=>getmypid());
        $rlt=['success'=>false, 'failCount'=>0,/*'failedList' =>0,*/ 'failed_log_filepath' => site_url().'remote_logs/'.basename($failed_log_filepath),   'successCount'=>0,  'processedRows' => 0, 'totalCount' => $totalCount, 'progress' => 0];
        $queue_result_model->updateResultRunning($token, [], $state);

        $affiliateMap = $affiliatemodel->getAffiliateMap();
        $adminUserId = $params['adminUserId'];
        $adminUsername = $params['adminUsername'];
        $reason = $params['reason'];
        $yearmonth = $params['yearmonth'];
        $action_name = 'Batch Addon Platform Fee Adjustment';

        $count_loop = 0;
        $failCount = 0;
        $successCount = 0;
        $failedList = [] ;
        $percentage_steps = [];

        for ($i=.1; $i <= 10 ; $i +=.1) {
            array_push($percentage_steps, ceil($i/10 * $totalCount));
        };
        $ignore_first_row = true;

        $controller->utils->loopCSV($csv_file, $ignore_first_row, $cnt, $message,
            function($cnt, $csv_row, $stop_flag)
            use(
                $controller, $queue_result_model, $affiliatemodel, $affiliate_manager, $affiliate_commission,  $affiliate_earnings, $token,$state,$percentage_steps,&$count_loop, &$failCount,
                &$successCount,&$totalCount,&$failedList,
                $csv_headers, $action_name, $affiliateMap, $adminUserId, $adminUsername, $reason, $yearmonth, $funcName, $failed_log_filepath
            ) {
                // if(count($csv_row) == 0){
                //     $totalCount--;
                // }
                print_r($csv_row);
                 print_r($cnt);               // $count_loop++;
                $row = null;
                $success = false;

                if(count($csv_headers) == count($csv_row)){
                    $row = array_combine($csv_headers, $csv_row);
                }else{
                    $rlt=['success'=>false, 'errorDetail'=>'CSV number of columns not tallied with the uploaded file', 'failCount'=>0,/*'failedList' =>0, */ 'failed_log_filepath' => site_url().'remote_logs/'.basename($failed_log_filepath), 'successCount'=>0,  'processedRows' => $count_loop, 'totalCount' => $totalCount, 'progress' => 0];
                    $queue_result_model->failedResult($token, $rlt);
              // $stop_flag = true;
              // exit;
                    return  $controller->utils->error_log("Columns not Matched!", 'csv_headers',$csv_headers,'csv_row',$csv_row);
                }

                $affiliate_id = null;
                $username = null;
                if (isset($affiliateMap[$row['username']])) {
                    $affiliate_id = $affiliateMap[$row['username']];
                    $username = $row['username'];
                }

                $controller->utils->info_log("csv_row", $csv_row,'count_loop', $count_loop,'totalCount', $totalCount);

                if(empty($affiliate_id)){
                   $failCount++;
                   $controller->utils->error_log("AFFILIATE NOT EXIST", $row);
                  // array_push($failedList, ['username'=> isset($row['username']) ? $row['username'] : '','reason'=>'Player not exist']);
                   $message_log = ['username'=> isset($row['username']) ? $row['username'] : '','reason'=>'Affiliate not exist'];
                   $controller->utils-> _appendSaveDetailedResultToRemoteLog($token, $funcName.'_failed_results', $message_log, $failed_log_filepath, true, []);
               }

           //start adjustnment--------------------------------------------------------------------

               if(!empty($affiliate_id)){
                $skip = false;
                $affiliate_commission_record = $affiliate_earnings->getAffiliateMonthlyCommissionByYearmonthAndAffid($affiliate_id, $yearmonth);
                if(empty($affiliate_commission_record)) {
                    $skip = true;
                    $failCount++;
                    $message_log = ['username'=> $row['username'],'reason'=>'commission record not found'];
                    $controller->utils-> _appendSaveDetailedResultToRemoteLog($token, $funcName.'_failed_results', $message_log, $failed_log_filepath, true, []);

                } else {
                    if($affiliate_commission_record['paid_flag'] == 1){
                        $skip = true;
                        $failCount++;
                        $message_log = ['username'=> $row['username'],'reason'=>'status paid'];
                        $controller->utils-> _appendSaveDetailedResultToRemoteLog($token, $funcName.'_failed_results', $message_log, $failed_log_filepath, true, []);
                    }
                }
                if(!$skip) {

                    $lockedKey=null;
                    $lock_it = $controller->lockPlayerBalanceResource($affiliate_id, $lockedKey);
                    try {
                        if ($lock_it) {

                            $controller->startTrans();
                            $success = $affiliatemodel->updateAddonPlatformFee($affiliate_id, $yearmonth, $row['platform_fee'], true, 'import from CSV'. $reason);
                            if(!$success){
                                $failCount++;
                                $message_log = ['username'=> $username,'reason'=>'run updateAddonPlatformFee fail'];
                                $controller->utils-> _appendSaveDetailedResultToRemoteLog($token, $funcName.'_failed_results', $message_log, $failed_log_filepath, true, []);

                                $this->rollbackTrans();  //quit
                            }
                            $success = $controller->endTransWithSucc();
                            if (!$success) {
                                $failCount++;
                                $message_log = ['username'=> $row['username'],'reason'=>'Trans error'];
                                $controller->utils-> _appendSaveDetailedResultToRemoteLog($token, $funcName.'_failed_results', $message_log, $failed_log_filepath, true, []);
                            }

                            if($success) {
                                $success = $affiliate_commission->generate_monthly_earnings_for_all($yearmonth, $username);
                                if ($success) {
                                    $successCount++;
                                } else {
                                    $failCount++;
                                    $message_log = ['username'=> $username,'reason'=>'regenerate monthly earnings fail'];
                                    $controller->utils-> _appendSaveDetailedResultToRemoteLog($token, $funcName.'_failed_results', $message_log, $failed_log_filepath, true, []);
                                }
                            }
                        }//lockit end
                    } finally {
                        $controller->releasePlayerBalanceResource($affiliate_id, $lockedKey);
                    }
                }
            }//end check empty playerId
            $count_loop++;
            //update front end progress
            $rlt=['success'=>false, 'failCount'=> $failCount,/*'failedList' => $failedList,*/ 'failed_log_filepath' => site_url().'remote_logs/'.basename($failed_log_filepath), 'successCount'=> $successCount,  'processedRows' => $count_loop, 'totalCount' => $totalCount, 'progress' => ceil($count_loop/$totalCount * 100)];
            $queue_result_model->updateResultRunning($token, $rlt, $state);

            if($count_loop == $totalCount){
                $controller->utils->info_log('count_loop == totalCount',$count_loop == $totalCount);
            //update last - Done
                $rlt=['success'=>true, 'failCount'=>$failCount,/*'failedList' => $failedList,*/ 'failed_log_filepath' => site_url().'remote_logs/'.basename($failed_log_filepath), 'successCount'=> $successCount,  'processedRows' => $count_loop, 'totalCount' => $totalCount, 'progress' => 100];
                $queue_result_model->updateResult($token, $rlt);
            //end adjustnment-------------------------------------------------------------------
            }

        });//loop csv;
        $successCount = $totalCount - $failCount;
        $controller->utils->debug_log("Import batch_addon_platform_fee_adjustment, [$successCount] out of [$totalCount] succeed.  failed_log_filepath: ". $failed_log_filepath);
    }

    public function test_lock_balance($username, $seconds = 10, $success = true, $token = null)
    {
        $this->load->model(['queue_result']);

    }

    public function test_player_lock_balance($username, $seconds = 10, $token)
    {
        //check if feature is enabled
        if (!$this->utils->getConfig('enable_test_player_lock_balance')) {
            $this->utils->debug_log('disabled enable_test_player_lock_balance');
            return true;
        }

        $this->load->model(['player_model', 'queue_result']);
        $process_start_time = $this->utils->getNowForMysql();

        if (!empty($token)) {
            $success = $this->testLockBalance($username, $seconds);

            $result = [
                'func' => 'test_player_lock_balance',
                'username' => $username,
                'seconds' => $seconds,
                'process_start_time' => $process_start_time,
                'process_end_time' => $this->utils->getNowForMysql(),
                'message' => ''
            ];

            $final_result = [
                'token' => $token,
                'success' => $success,
                'message' => '',
                'progress' => 100,
                'total' => 100,
                'done' => true
            ];

            if ($success) {
                $result['message'] = lang('Test player lock balance done!');
                $final_result['message'] = $result['message'];
                $this->queue_result->appendResult($final_result['token'], ['request_id' => _REQUEST_ID, 'result' => $result ], $final_result['done'], false);
                $this->queue_result->updateFinalResult($final_result['token'], $final_result['success'], $final_result['message'], $final_result['progress'], $final_result['total'], $final_result['done']);
                $this->utils->debug_Log("============================ rebuild_points_transaction_report_hour_result ============================", "token", $token, "result", $result, "final_result", $final_result);
            } else {
                $result['message'] = lang('Lock failed! Please try again after 2 minutes.');
                $final_result['message'] = $result['message'];
                $this->queue_result->appendResult($final_result['token'], ['request_id' => _REQUEST_ID, 'result' => $result ], $final_result['done'], true);
                $this->queue_result->updateFinalResult($final_result['token'], $final_result['success'], $final_result['message'], $final_result['progress'], $final_result['total'], $final_result['done']);
                $this->utils->debug_Log("============================ rebuild_points_transaction_report_hour_result ============================", "token", $token, "result", $result, "final_result", $final_result);
            }
        }
	}

    public function test_lock_table($table, $seconds = 10, $token)
    {
        //check if feature is enabled
        if (!$this->utils->getConfig('enable_test_lock_table')) {
            $this->utils->debug_log('disabled enable_test_lock_table');
            return true;
        }

        $this->load->model(['player_model', 'queue_result']);
        $process_start_time = $this->utils->getNowForMysql();

        if (!empty($token)) {
            $success = $this->testLockTable($table, $seconds);

            $result = [
                'func' => 'test_lock_table',
                'table' => $table,
                'seconds' => $seconds,
                'process_start_time' => $process_start_time,
                'process_end_time' => $this->utils->getNowForMysql(),
                'message' => ''
            ];

            $final_result = [
                'token' => $token,
                'success' => $success,
                'message' => '',
                'progress' => 100,
                'total' => 100,
                'done' => true
            ];

            if ($success) {
                $result['message'] = lang('Test lock table done!');
                $final_result['message'] = $result['message'];
                $this->queue_result->appendResult($final_result['token'], ['request_id' => _REQUEST_ID, 'result' => $result ], $final_result['done'], false);
                $this->queue_result->updateFinalResult($final_result['token'], $final_result['success'], $final_result['message'], $final_result['progress'], $final_result['total'], $final_result['done']);
                $this->utils->debug_Log("============================ test_lock_table ============================", "token", $token, "result", $result, "final_result", $final_result);
            } else {
                $result['message'] = lang('Lock failed! Please try again after 2 minutes.');
                $final_result['message'] = $result['message'];
                $this->queue_result->appendResult($final_result['token'], ['request_id' => _REQUEST_ID, 'result' => $result ], $final_result['done'], true);
                $this->queue_result->updateFinalResult($final_result['token'], $final_result['success'], $final_result['message'], $final_result['progress'], $final_result['total'], $final_result['done']);
                $this->utils->debug_Log("============================ test_lock_table ============================", "token", $token, "result", $result, "final_result", $final_result);
            }
        }
    }


    public function run_remote_manual_sync_gamelist_from_gamegateway($game_platform_id, $token)
    {

        $this->load->model(['player_model', 'queue_result']);
        $process_start_time = $this->utils->getNowForMysql();

        if (!empty($token)) {
            $success = $this->do_manual_sync_gamelist_from_gamegateway($game_platform_id);

            $result = [
                'func' => 'run_remote_manual_sync_gamelist_from_gamegateway',
                'game_platform_id' => $game_platform_id,
                'process_start_time' => $process_start_time,
                'process_end_time' => $this->utils->getNowForMysql(),
                'message' => ''
            ];

            $final_result = [
                'token' => $token,
                'success' => $success,
                'message' => '',
                'progress' => 100,
                'total' => 100,
                'done' => true
            ];

            if ($success) {
                $result['message'] = lang('run_remote_manual_sync_gamelist_from_gamegateway done!');
                $final_result['message'] = $result['message'];
                $this->queue_result->appendResult($final_result['token'], ['request_id' => _REQUEST_ID, 'result' => $result ], $final_result['done'], false);
                $this->queue_result->updateFinalResult($final_result['token'], $final_result['success'], $final_result['message'], $final_result['progress'], $final_result['total'], $final_result['done']);
                $this->utils->debug_Log("============================ run_remote_manual_sync_gamelist_from_gamegateway ============================", "token", $token, "result", $result, "final_result", $final_result);
            } else {
                $result['message'] = lang('run_remote_manual_sync_gamelist_from_gamegateway failed!');
                $final_result['message'] = $result['message'];
                $this->queue_result->appendResult($final_result['token'], ['request_id' => _REQUEST_ID, 'result' => $result ], $final_result['done'], true);
                $this->queue_result->updateFinalResult($final_result['token'], $final_result['success'], $final_result['message'], $final_result['progress'], $final_result['total'], $final_result['done']);
                $this->utils->debug_Log("============================ run_remote_manual_sync_gamelist_from_gamegateway ============================", "token", $token, "result", $result, "final_result", $final_result);
            }
        }
    }

    public function do_sync_game_tag_from_one_to_other_mdb_by_queue($token)
    {

        $this->load->model(['player_model', 'queue_result']);
        $process_start_time = $this->utils->getNowForMysql();

        if (!empty($token)) {
            $success = false;
            $sync_results = $this->sync_game_tag_from_one_to_other_mdb_by_queue($token);
            if(!empty($sync_results)){
                $success = true;
            }

            $result = [
                'func' => 'do_sync_game_tag_from_one_to_other_mdb_by_queue',
                'process_start_time' => $process_start_time,
                'process_end_time' => $this->utils->getNowForMysql(),
                'message' => ''
            ];

            $final_result = [
                'token' => $token,
                'success' => $success,
                'message' => '',
                'progress' => 100,
                'total' => 100,
                'done' => true
            ];

            if ($success) {
                $result['message'] = lang('do_sync_game_tag_from_one_to_other_mdb_by_queue done!');
                $final_result['message'] = $result['message'];
                $this->queue_result->appendResult($final_result['token'], ['request_id' => _REQUEST_ID, 'result' => $result ], $final_result['done'], false);
                $this->queue_result->updateFinalResult($final_result['token'], $final_result['success'], $final_result['message'], $final_result['progress'], $final_result['total'], $final_result['done'], null, array("results" => $sync_results));
                $this->utils->debug_Log("============================ do_sync_game_tag_from_one_to_other_mdb_by_queue ============================", "token", $token, "result", $result, "final_result", $final_result);
            } else {
                $result['message'] = lang('do_sync_game_tag_from_one_to_other_mdb_by_queue failed!');
                $final_result['message'] = $result['message'];
                $this->queue_result->appendResult($final_result['token'], ['request_id' => _REQUEST_ID, 'result' => $result ], $final_result['done'], true);
                $this->queue_result->updateFinalResult($final_result['token'], $final_result['success'], $final_result['message'], $final_result['progress'], $final_result['total'], $final_result['done']);
                $this->utils->debug_Log("============================ do_sync_game_tag_from_one_to_other_mdb_by_queue ============================", "token", $token, "result", $result, "final_result", $final_result);
            }
        }
    }


    public function sync_game_tag_from_one_to_other_mdb_by_queue($token){
        $this->load->model(['multiple_db_model']);
        $data=$this->initJobData($token);
        $token = $data['token'];
        $params = json_decode($data['full_params'],true);
        $sourceDb = $params['source_db'];
        $gameIds = $params['game_ids'];
        $this->utils->debug_log('load from queue:', $token, $params, 'GameIDS:', $gameIds);
        $rlt = [];
        if(!empty($gameIds)){
            $gameIds = explode(",", $gameIds);
            foreach ($gameIds as $key => $gameId) {
                $rlt[$gameId][] = $this->multiple_db_model->syncGameTagFromOneToOtherMDB($sourceDb, $gameId);
                $this->utils->debug_log('do_sync_game_tag_from_one_to_other_mdb_by_queue result ==:', $rlt);
            }
        }

        return $rlt;
    }

    public function do_batch_refund($token)
    {
        $this->load->model(['multiple_db_model']);
        $data=$this->initJobData($token);
        $token = $data['token'];
        $params = $data['params'];
        $game_platform_id = isset($params['game_platform_id']) ? $params['game_platform_id'] : null;
        $bet_ids = isset($params['bet_ids']) ? $params['bet_ids'] : [];
        $success = true;

        //check if feature is enabled
        if (!$this->utils->getConfig('enable_batch_refund')) {
            $this->utils->debug_log('disabled enable_batch_refund');
            return true;
        }

        $this->load->model(['player_model', 'queue_result']);
        $process_start_time = $this->utils->getNowForMysql();

        if (!empty($token)) {
            $api = $this->utils->loadExternalSystemLibObject($game_platform_id);
            if (!empty($api)) {
                $extra['from_dev_functions'] = true;
                $rlt = $api->batchRefund($bet_ids, $extra);
                $success = true;
            }

            $result = [
                'func' => 'remote_batch_refund',
                'process_start_time' => $process_start_time,
                'process_end_time' => $this->utils->getNowForMysql(),
                'message' => ''
            ];

            $final_result = [
                'token' => $token,
                'message' => '',
                'progress' => 100,
                'total' => 100,
                'success' => true,
                'done' => true
            ];


            if ($success) {
                $result['message'] = lang('Batch refund done!');
                $final_result['message'] = $result['message'];
                $this->queue_result->appendResult($final_result['token'], ['request_id' => _REQUEST_ID, 'result' => $result ], $final_result['done'], false);
                $success = $this->queue_result->updateFinalResult($final_result['token'], $final_result['success'], $final_result['message'], $final_result['progress'], $final_result['total'], $final_result['done']);
                
                $this->utils->debug_Log("============================ batch refund ============================", "token", $token, "result", $result, "final_result", $final_result);
            } else {
                $result['message'] = lang('Batch refund failed! Please try again after 2 minutes.');
                $final_result['message'] = $result['message'];
                $this->queue_result->appendResult($final_result['token'], ['request_id' => _REQUEST_ID, 'result' => $result ], $final_result['done'], true);
                $success = $this->queue_result->updateFinalResult($final_result['token'], $final_result['success'], $final_result['message'], $final_result['progress'], $final_result['total'], $final_result['done']);
                $this->utils->debug_Log("============================ batch refund ============================", "token", $token, "result", $result, "final_result", $final_result);
            }
            return $success;
        }
    }

    public function do_batch_export_player_id($token)
    {
        $this->load->model(['multiple_db_model', 'player_model']);
        $this->utils->debug_log('start memory_get_usage:' . (memory_get_usage() / 1024));
        $data = $this->initJobData($token);
        $token = isset($data['token']) ? $data['token'] : null;
        $params = isset($data['params']) ? $data['params'] : [];
    
        $usernames = isset($params['usernames']) ? $params['usernames'] : [];
    
        // Check if feature is enabled
        if (!$this->utils->getConfig('enable_batch_export_player_id')) {
            $this->utils->debug_log('disabled enable_batch_export_player_id');
            return true;
        }
    
        if (!empty($token)) {
            $results = $this->player_model->getPlayerIdsAndUsernamesByUsernames($usernames);
            if (is_array($results)) {
                $filename = $this->utils->create_csv_filename('batch_export_player_id');
                // Prepare CSV data
                $csv_data = [
                    'header_data' => ['Username', 'Player Id'], // Header
                    'data' => array_map(function ($result) {
                        return [$result['username'], $result['playerId']];
                    }, $results)
                ];
                $this->utils->create_csv($csv_data, $filename);
            }
            $rlt = ['success' => true, 'filename' => $filename . '.csv'];
            $this->queue_result->updateResult($token, $rlt);
            if (!$rlt['success']) {
                $this->utils->error_log('[ERROR] do_batch_export_player_id error: failed to batch export');
            }
            $this->utils->debug_log('end memory_get_usage:' . (memory_get_usage() / 1024));
            return $rlt;
        }
    }

    public function sync_games_report_timezones_by_token($token, $dateFrom, $dateTo, $gameApiId, $playerId)
    {
        if (empty($token)) {
            return false;
        }

        $this->load->model(['player_model', 'queue_result']);
        $process_start_time = $this->utils->getNowForMysql();

        if (!empty($token)) {
            $response = $this->generate_games_report_timezone($dateFrom, $dateTo, $gameApiId, $playerId);

            $result = [
                'func' => 'sync_games_report_timezones_by_token',
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
                'gameApiId' => $gameApiId,
                'playerId' => $playerId,
                'process_start_time' => $process_start_time,
                'process_end_time' => $this->utils->getNowForMysql(),
                'response' => $response
            ];


            $this->queue_result->appendResult($token, ['request_id' => _REQUEST_ID, 'result' => $result ], true, false);
            $this->queue_result->updateFinalResult($token, true, $response, 100, count($response), true);
        }
    }

    public function sync_summary_game_total_bet_daily_token($token, $date)
    {
        if (empty($token)) {
            return false;
        }

        $this->load->model(['player_model', 'queue_result']);
        $processStartTime = $this->utils->getNowForMysql();

        if (!empty($token)) {
            $totalCount = $this->generateSummaryGameTotalBetDaily($date);
            $result = [
                'func' => 'sync_summary_game_total_bet_daily_token',
                'date' => $date,
                'process_start_time' => $processStartTime,
                'process_end_time' => $this->utils->getNowForMysql(),
                'total' => $totalCount
            ];

            $this->queue_result->appendResult($token, ['request_id' => _REQUEST_ID, 'result' => $result ], true, false);
            $this->queue_result->updateFinalResult($token, true, [], 100, $totalCount, true);
        }
    }

    public function do_sync_latest_game_records($date_from, $date_to, $token = null) {
        //check if feature is enabled
        if (!$this->utils->getConfig('enable_sync_latest_game_records')) {
            $this->utils->debug_log('disabled enable_sync_latest_game_records');
            return true;
        }

        $this->load->model(['queue_result']);
        $process_start_time = $this->utils->getNowForMysql();

        if (!empty($token)) {
            $sync_result = $this->sync_game_records_latest($date_from, $date_to);
            $success = isset($sync_result['success']) && $sync_result['success'] ? $sync_result['success'] : false;
            $count = !empty($sync_result['data']['insert_count']) ? $sync_result['data']['insert_count'] : 0;

            $result = [
                'func' => 'sync_latest_game_records',
                'date_from' => $date_from,
                'date_to' => $date_to,
                'process_start_time' => $process_start_time,
                'process_end_time' => $this->utils->getNowForMysql(),
                'message' => ''
            ];

            $final_result = [
                'token' => $token,
                'success' => $success,
                'message' => '',
                'progress' => 100,
                'total' =>  $count,
                'done' => true
            ];

            if ($success) {
                $result['message'] = lang('Sync latest game records done!');
                $final_result['message'] = $result['message'];
                $this->queue_result->appendResult($final_result['token'], ['request_id' => _REQUEST_ID, 'result' => $result ], $final_result['done'], false);
                $this->queue_result->updateFinalResult($final_result['token'], $final_result['success'], $final_result['message'], $final_result['progress'], $final_result['total'], $final_result['done']);
                $this->utils->debug_Log("============================ rebuild_points_transaction_report_hour_result ============================", "token", $token, "result", $result, "final_result", $final_result);
            } else {
                $result['message'] = lang('Sync latest game records failed');
                $final_result['message'] = $result['message'];
                $this->queue_result->appendResult($final_result['token'], ['request_id' => _REQUEST_ID, 'result' => $result ], $final_result['done'], true);
                $this->queue_result->updateFinalResult($final_result['token'], $final_result['success'], $final_result['message'], $final_result['progress'], $final_result['total'], $final_result['done']);
                $this->utils->debug_Log("============================ rebuild_points_transaction_report_hour_result ============================", "token", $token, "result", $result, "final_result", $final_result);
            }
        }
	}

    public function do_check_seamless_round_status($token = null, $date_from, $date_to) {
        $this->load->model(['queue_result']);
        $process_start_time = $this->utils->getNowForMysql();

        if (!empty($token)) {
            $this->check_seamless_round_status($date_from, $date_to);

            $result = [
                'func' => 'check_seamless_round_status',
                'date_from' => $date_from,
                'date_to' => $date_to,
                'process_start_time' => $process_start_time,
                'process_end_time' => $this->utils->getNowForMysql(),
                'message' => ''
            ];

            $final_result = [
                'token' => $token,
                'success' => true,
                'message' => '',
                'progress' => 100,
                'done' => true
            ];

            $result['message'] = lang('Sync missing payout report done!');
            $final_result['message'] = $result['message'];
            $this->queue_result->appendResult($final_result['token'], ['request_id' => _REQUEST_ID, 'result' => $result ], $final_result['done'], false);
        }
	}

    public function do_cancel_game_round($game_platform_id, $game_username, $round_id, $game_code, $token = null) {
        //check if feature is enabled
        if (!$this->utils->getConfig('enable_cancel_game_round')) {
            $this->utils->debug_log('disabled enable_cancel_game_round');
            return true;
        }

        $this->load->model(['queue_result']);
        $process_start_time = $this->utils->getNowForMysql();

        if (!empty($token)) {
            $response = $this->cancelGameRound($game_platform_id, $game_username, $round_id, $game_code);
            $success = isset($response['success']) && $response['success'] ? $response['success'] : false;

            $result = [
                'func' => 'cancelGameRound',
                'game_platform_id' => $game_platform_id,
                'game_username' => $game_username,
                'round_id' => $round_id,
                'game_code' => $game_code,
                'process_start_time' => $process_start_time,
                'process_end_time' => $this->utils->getNowForMysql(),
                'message' => ''
            ];

            $final_result = [
                'token' => $token,
                'success' => $success,
                'message' => '',
                'progress' => 100,
                'done' => true
            ];

            if ($success) {
                $result['message'] = isset($response['message']) ? $response['message'] : lang('Cancel game round done!');
                $final_result['message'] = $result['message'];
                $this->queue_result->appendResult($final_result['token'], ['request_id' => _REQUEST_ID, 'result' => $result ], $final_result['done'], false);
                $this->queue_result->updateFinalResult($final_result['token'], $final_result['success'], $final_result['message'], $final_result['progress'], $final_result['total'], $final_result['done']);
                $this->utils->debug_Log("============================ rebuild_points_transaction_report_hour_result ============================", "token", $token, "result", $result, "final_result", $final_result);
            } else {
                $result['message'] = isset($response['message']) ? $response['message'] : lang('Cancel game round failed');
                $final_result['message'] = $result['message'];
                $this->queue_result->appendResult($final_result['token'], ['request_id' => _REQUEST_ID, 'result' => $result ], $final_result['done'], true);
                $this->queue_result->updateFinalResult($final_result['token'], $final_result['success'], $final_result['message'], $final_result['progress'], $final_result['total'], $final_result['done']);
                $this->utils->debug_Log("============================ cancel_game_round ============================", "token", $token, "result", $result, "final_result", $final_result);
            }
        }
    }

    public function refresh_all_player_balance_in_specific_game_provider_by_token($token, $game_platform_id, $is_only_registered)
    {
        if (empty($token)) {
            return false;
        }

        $this->utils->debug_log('refresh_all_player_balance_in_specific_game_provider game_platform_id', $game_platform_id, 'is_only_registered', $is_only_registered);
        $this->load->model(['player_model', 'queue_result']);
        $processStartTime = $this->utils->getNowForMysql();

        if (!empty($token)) {
            $wallet_count_refreshed = $this->refresh_all_player_balance_in_specific_game_provider($game_platform_id, $is_only_registered);
            $result = [
                'func' => 'refresh_all_player_balance_in_specific_game_provider',
                'game_platform_id' => $game_platform_id,
                'is_only_registered' => $is_only_registered,
                'process_start_time' => $processStartTime,
                'process_end_time' => $this->utils->getNowForMysql(),
                'wallet_count_refreshed' => $wallet_count_refreshed,
            ];

            $this->queue_result->appendResult($token, ['request_id' => _REQUEST_ID, 'result' => $result ], true, false);
            $this->queue_result->updateFinalResult($token, true, [], 100, $wallet_count_refreshed, true);
        }
    }

    public function transfer_all_players_subwallet_to_main_wallet_by_token($token, $game_id, $max_balance, $min_balance)
    {
        if (empty($token)) {
            return false;
        }

        if($game_id == '_null'){
            $game_id = null;
        }
        
        $this->utils->debug_log('transfer_all_players_subwallet_to_main_wallet_by_token game_id', $game_id, 'max_balance', $max_balance, 'min_balance', $min_balance);
        $this->load->model(['player_model', 'queue_result']);
        $processStartTime = $this->utils->getNowForMysql();

        if (!empty($token)) {
            $totalCount = $this->transfer_all_players_subwallet_to_main_wallet($game_id, $max_balance, $min_balance);
            $result = [
                'func' => 'transfer_all_players_subwallet_to_main_wallet_by_token',
                'game_id' => $game_id,
                'max_balance' => $max_balance,
                'min_balance' => $min_balance,
                'process_start_time' => $processStartTime,
                'process_end_time' => $this->utils->getNowForMysql(),
                'total_player' => $totalCount
            ];

            $this->queue_result->appendResult($token, ['request_id' => _REQUEST_ID, 'result' => $result ], true, false);
            $this->queue_result->updateFinalResult($token, true, [], 100, $totalCount, true);
        }
    }
}// end trait
