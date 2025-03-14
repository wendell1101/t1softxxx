<?php

trait sync_3rdparty_command_module {

    /*** sample service
     * ---------
        command=/usr/bin/php /home/vagrant/Code/og/admin/public/index.php cli/sync_get_reponse_data/start_sync_get_response
        numprocs=1
        process_name=%(program_name)s_%(process_num)03d
        directory=/home/vagrant/Code/og/admin
        autostart=true
        autorestart=true
        stdout_logfile=/home/vagrant/Code/og/admin/application/logs/monitor_sync_get_response_stdout.log.supervisor
        stdout_logfile_maxbytes=2MB
        stderr_logfile=/home/vagrant/Code/og/admin/application/logs/monitor_sync_get_response_logs_brl_stderr.log.supervisor
        stderr_logfile_maxbytes=2MB
     * ---------
    */
	public function sync_getreponse_data($dateTimeFromStr = null, $dateTimeToStr = null,$queue_token=null){
		$default_sync_game_logs_max_time_second = $this->config->item('default_sync_game_logs_max_time_second');
		set_time_limit($default_sync_game_logs_max_time_second);
        
		$this->utils->debug_log('========= bermar start sync_getreponse_data ============================ date', $dateTimeFromStr ,'-' ,$dateTimeToStr);

		$this->load->model(array('sync_status_model', 'queue_result'));

		if(!empty($queue_token)){
			$this->utils->debug_log('append result ', _REQUEST_ID, $queue_token);
			//update queue_results
			$this->queue_result->appendResult($queue_token, ['request_id'=>_REQUEST_ID, 'func'=>'sync_getreponse_data']);
		}

        ## rebuild seamless latest game records
        # get data from game logs

		$this->load->model(array('player_get_response_contact'));
        $dateTimeTo = $dateTimeFrom = new DateTime();
        if(empty($dateTimeFromStr)){
            $dateTimeFromStr = $dateTimeFrom->modify('-15 minutes')->format('Y-m-d H:i:00');

        }
        if(empty($dateTimeToStr)){
            $dateTimeToStr = $dateTimeTo->format('Y-m-d H:i:59');
        }
        $resp = $this->player_get_response_contact->sync(new DateTime($dateTimeFromStr), new DateTime($dateTimeToStr));
		

		$this->utils->debug_log('========= end syncSeamlessGameBatchPayoutOnOnePlatform ============================',
        'response', $resp);
		return;
	}
	
    public function send_data_to_getreponse_hourly($dateTimeFromStr = null, $dateTimeToStr = null, $playerId = null){
        $this->send_data_to_getreponse($dateTimeFromStr, $dateTimeToStr, 60, $playerId = null);
    }

	public function send_data_to_getreponse($fromDateTime = null, $toDateTime = null, $offset = null, $playerId = null) {
		
        $this->utils->debug_log('========= send_data_to_getreponse ============================', $fromDateTime, $toDateTime, $playerId);
        $this->CI->load->model(array('player_get_response_contact'));
        $this->CI->load->library(array('getresponse/getresponse_lib'));

        $config = $this->utils->getConfig('third_party_get_response');
        if(is_null($offset)){
            $offset = isset($config['send_data_to_getreponse_offset'])?$config['send_data_to_getreponse_offset']:60;
        }
        $step = isset($config['send_data_to_getreponse_step'])?$config['send_data_to_getreponse_step']:30;
        $campaignId = isset($config['campaign_id'])?$config['campaign_id']:'C';

		//if null from and to default range is 5 minutes, start=now-7minutes,to=now-2minutes
		$endDateTime = $startDateTime = new DateTime();				
		$startDateTime->modify("-$offset minutes");
		
		if(!empty($fromDateTime)){
			$startDateTime = new DateTime($fromDateTime);				
		}
		if(!empty($toDateTime)){			
            $endDateTime = new DateTime($toDateTime);
		}

		$queryDateTimeStart = $startDateTime->format("Y-m-d H:i:00");
        $queryDateTimeEnd = $startDateTime->modify("+$step minutes")->format('Y-m-d H:i:59');
    	$queryDateTimeMax = $endDateTime->format("Y-m-d H:i:59");

		# Query Exact end
		if($queryDateTimeEnd > $queryDateTimeMax){
			$queryDateTimeEnd = $endDateTime->format("Y-m-d H:i:59");
        }

		while ($queryDateTimeMax  > $queryDateTimeStart) {
		
			$from = new DateTime($queryDateTimeStart);
            $to = new DateTime($queryDateTimeEnd);

            //get data from getresponse table by update at
            $data = $this->player_get_response_contact->getData($from->format('Y-m-d H:i:00'), $to->format('Y-m-d H:i:59'));
            //var_dump($data);
            foreach($data as $row){
                if(empty($row['email'])){
                    continue;
                }
                
                $playerId = $row['player_id'];
                $contact = [];
                unset($contact['id']);
                unset($contact['md5_sum']);
                unset($contact['game_data']);
                unset($contact['balance_limit']);
                $contact['email'] = $row['email'];
                $contact['campaign'] = ['campaignId'=>$campaignId];
                $contact['name'] = $row['player_username'];
                $customFieldValues = [];
                $customFieldValues[] = ['customFieldId'=>'n', 'value'=>[$row['wallet_balance']]];//balancee - value number


                $balanceLimitData = json_decode($row['balance_limit'], true);
                if(isset($balanceLimitData['balance_limit_1'])&&!empty($balanceLimitData['balance_limit_1'])){
                    $customFieldValues[] = ['customFieldId'=>'e', 'value'=>[$balanceLimitData['balance_limit_1']]];//balance_limit_1
                }
                if(isset($balanceLimitData['balance_limit_2'])&&!empty($balanceLimitData['balance_limit_2'])){
                    $customFieldValues[] = ['customFieldId'=>'C', 'value'=>[$balanceLimitData['balance_limit_2']]];//balance_limit_2
                }

                //$customFieldValues[] = ['customFieldId'=>'T', 'value'=>[$row['wallet_balance']]];//birthdate - value date
                //$customFieldValues[] = ['customFieldId'=>'Y', 'value'=>['']];//city - value string
                //$customFieldValues[] = ['customFieldId'=>'G', 'value'=>['']];//comment - value string
                //$customFieldValues[] = ['customFieldId'=>'6', 'value'=>['']];//company - value string
                $confirm_email_status = ($row['confirm_email_status']==0?"false":"true");
                $customFieldValues[] = ['customFieldId'=>'1', 'value'=>[$confirm_email_status]];//confirm_email_status - value string true/false

                //$customFieldValues[] = ['customFieldId'=>'F', 'value'=>['']];//confirm_url - value string
                //$customFieldValues[] = ['customFieldId'=>'B', 'value'=>['']];//country - value country
                if(!empty($row['date_first_deposit'])){
                    $customFieldValues[] = ['customFieldId'=>'S', 'value'=>[date('Y-m-d H:i:s', strtotime($row['date_first_deposit']))]];//date_first_deposit - value date
                }
                if(!empty($row['date_last_deposit'])&&!empty($row['date_last_deposit'])){
                    $customFieldValues[] = ['customFieldId'=>'H', 'value'=>[date('Y-m-d H:i:s', strtotime($row['date_last_deposit']))]];//date_last_deposit - value date
                }

                $gameData = json_decode($row['game_data'], true);

                if(isset($gameData['date_last_casino'])&&!empty($gameData['date_last_casino'])){
                    $customFieldValues[] = ['customFieldId'=>'E', 'value'=>[date('Y-m-d H:i:s', strtotime($gameData['date_last_casino']))]];//date_last_casino - value date
                }

                if(isset($gameData['date_last_livecasino'])&&!empty($gameData['date_last_livecasino'])){
                    $customFieldValues[] = ['customFieldId'=>'y', 'value'=>[date('Y-m-d H:i:s', strtotime($gameData['date_last_livecasino']))]];//date_last_livecasino - value date
                }

                if(isset($gameData['date_last_virtual'])&&!empty($gameData['date_last_virtual'])){
                    $customFieldValues[] = ['customFieldId'=>'A', 'value'=>[date('Y-m-d H:i:s', strtotime($gameData['date_last_virtual']))]];//date_last_virtual - value date
                }

                if(isset($gameData['date_last_esports'])&&!empty($gameData['date_last_esports'])){
                    $customFieldValues[] = ['customFieldId'=>'p', 'value'=>[date('Y-m-d H:i:s', strtotime($gameData['date_last_esports']))]];//date_last_esports - value date
                }

                if(isset($gameData['date_last_sports'])&&!empty($gameData['date_last_sports'])){
                    $customFieldValues[] = ['customFieldId'=>'7', 'value'=>[date('Y-m-d H:i:s', strtotime($gameData['date_last_sports']))]];//date_last_sports - value date
                }

                if(!empty($row['date_last_login'])&&!empty($row['date_last_login'])){
                    $customFieldValues[] = ['customFieldId'=>'x', 'value'=>[date('Y-m-d H:i:s', strtotime($row['date_last_login']))]];//date_last_login - value date
                }
                
                //$customFieldValues[] = ['customFieldId'=>'r', 'value'=>['']];//fax - value phone
                //$customFieldValues[] = ['customFieldId'=>'a', 'value'=>['']];//gender - value "Male"/"Female"/"Prefer not to say"
                //$customFieldValues[] = ['customFieldId'=>'D', 'value'=>['']];//home_phone - value phone
                //$customFieldValues[] = ['customFieldId'=>'3', 'value'=>['']];//http_referer - value url
                //$customFieldValues[] = ['customFieldId'=>'b', 'value'=>['']];//language - value string
                //$customFieldValues[] = ['customFieldId'=>'2', 'value'=>['']];//level_number - value number
                //$customFieldValues[] = ['customFieldId'=>'c', 'value'=>['']];//mobile_phone - value phone
                $customFieldValues[] = ['customFieldId'=>'o', 'value'=>[$row['deposit_count']]];//number_of_deposits - value number
                //$customFieldValues[] = ['customFieldId'=>'K', 'value'=>['']];//phone - value phone
                $customFieldValues[] = ['customFieldId'=>'v', 'value'=>[$row['player_id']]];//player_id - value number
                $customFieldValues[] = ['customFieldId'=>'W', 'value'=>[$row['player_username']]];//player_username - value string
                //$customFieldValues[] = ['customFieldId'=>'t', 'value'=>['']];//postal_code - value string
                //$customFieldValues[] = ['customFieldId'=>'9', 'value'=>['']];//ref - value string

                if(!empty($row['date_registered'])&&!empty($row['date_registered'])){
                    $customFieldValues[] = ['customFieldId'=>'i', 'value'=>[date('Y-m-d H:i:s', strtotime($row['date_registered']))]];//reg_date - value date
                }

                //$customFieldValues[] = ['customFieldId'=>'Z', 'value'=>['']];//state - value string
                //$customFieldValues[] = ['customFieldId'=>'j', 'value'=>['']];//street - value string
                if(isset($row['player_token'])&&!empty($row['player_token'])){
                    $customFieldValues[] = ['customFieldId'=>'d', 'value'=>[$row['player_token']]];//token - value string
                }
                //$customFieldValues[] = ['customFieldId'=>'P', 'value'=>['']];//url - value url
                //$customFieldValues[] = ['customFieldId'=>'u', 'value'=>['']];//work_phone - value phone

                $wdData = json_decode($row['withdrawal_data'], true);
                if(isset($wdData['withdrawal_amount'])&&!empty($wdData['withdrawal_amount'])){
                    $customFieldValues[] = ['customFieldId'=>'s', 'value'=>[$wdData['withdrawal_amount']]];//withdrawal_amount
                }
                if(isset($wdData['withdrawal_count'])&&!empty($wdData['withdrawal_count'])){
                    $customFieldValues[] = ['customFieldId'=>'5', 'value'=>[$wdData['withdrawal_count']]];//withdrawal_count
                }
                if(isset($wdData['withdrawal_currency'])&&!empty($wdData['withdrawal_currency'])){
                    $customFieldValues[] = ['customFieldId'=>'M', 'value'=>[$wdData['withdrawal_currency']]];//withdrawal_currency
                }
                if(isset($wdData['withdrawal_date'])&&!empty($wdData['withdrawal_date'])){
                    $customFieldValues[] = ['customFieldId'=>'I', 'value'=>[$wdData['withdrawal_date']]];//withdrawal_date
                }

                

                $contact['customFieldValues'] = $customFieldValues;


                if(empty($row['contact_id'])){
                    $reponse = $this->CI->getresponse_lib->addContact($playerId, $contact);
                    $this->utils->info_log('========= send_data_to_getreponse addContact ============================', 'reponse', $reponse,
                    'row', $row);
                
                    //call api to get the contact id
                    $contactId = null;
                    $getContactParams = [];
                    $getContactParams["query[email]"]=$contact['email'];
                    $getContactParams["campaignId"]=$campaignId;
                    $getContactResponse = $this->CI->getresponse_lib->getContactByEmail($getContactParams);
                    //var_dump($getContactResponse);
                    if(isset($getContactResponse['result']) && !empty($getContactResponse['result']) && is_array($getContactResponse['result'])){
                        foreach($getContactResponse['result'] as $contactRow){
                            if($contactRow['email']==$contact['email']){
                                $contactId = $contactRow['contactId'];
                            }
                        }
                    }

                    if(!empty($contactId)){
                        $success = $this->player_get_response_contact->updateContactId($contact['email'], $contactId);
                    }
                    $this->utils->info_log('========= send_data_to_getreponse updateContactId ============================', 'reponse', $reponse,
                    'row', $row);
                }else{
                    $contact['contactId'] = $row['contact_id'];
                    $reponse = $this->CI->getresponse_lib->updateContact($playerId, $contact);
                    $this->utils->info_log('========= send_data_to_getreponse updateContact ============================', 'reponse', $reponse,
                    'row', $row);
                }

                if(!$reponse){
                    $this->utils->error_log('========= send_data_to_getreponse ============================', 'reponse', $reponse,
                    'row', $row);
                }
                
            }

			$queryDateTimeStart = $queryDateTimeEnd;
			$queryDateTimeEnd  = (new DateTime($queryDateTimeStart))->modify("+$step minutes")->format('Y-m-d H:i:59');
            
    		# Query Exact end
    		if($queryDateTimeEnd > $queryDateTimeMax){
	    		$queryDateTimeEnd = $queryDateTimeMax;
	    	}
		}
	
		$this->utils->debug_log('=========end check_seamless_api_bet_status ============================');
	}


}