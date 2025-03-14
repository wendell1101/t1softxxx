<?php

trait points_command_module {

	public function calculate_bet_to_points_hourly($fromDateTime = null, $toDateTime = null, $playerId = null, $step = 30) {

		//cehck if feature is enabled
		$isEnabled = $this->utils->getConfig('enable_beting_amount_to_point');		
		if(!$isEnabled){
			return;
		}

		$this->utils->debug_log('========= calculate_bet_to_points_hourly start ============================');
		$this->load->library(array('authentication'));
		$this->load->model(array('total_player_game_hour', 'player_model', 'group_level', 'points_transaction_report_hour', 'point_transactions'));

		$startDateTime = new DateTime();
		//$startDateTime->modify("-$step minutes");
		$endDateTime = new DateTime();
		
		$stepback = 0;
		if(!empty($fromDateTime)){
			$startDateTime = new DateTime($fromDateTime);				
			$startDateTime->modify("-60 minutes");
		}
		if(!empty($toDateTime)){			
			$endDateTime = new DateTime($toDateTime);
		}

		$queryDateTimeStart = $startDateTime->format("Y-m-d H:i:s");
		$queryDateTimeEnd = $startDateTime->modify("+$step minutes")->format('Y-m-d H:i:s');
    	$queryDateTimeMax = $endDateTime->format("Y-m-d H:i:s");

		# Query Exact end
		if($queryDateTimeEnd > $queryDateTimeMax){
			$queryDateTimeEnd = $endDateTime->format("Y-m-d H:i:s");
		}

		while ($queryDateTimeMax  > $queryDateTimeStart) {
		
			$from = new DateTime($queryDateTimeStart);
			$to = new DateTime($queryDateTimeEnd);
			//$fromDateHourStr = $this->utils->formatDateHourForMysql($from);
			//$toDateHourStr = $this->utils->formatDateHourForMysql($to);
			$this->utils->debug_log('========= loop calculate_bet_to_points_hourly============================',$from->format("Y-m-d H:i:s"),$to->format("Y-m-d H:i:s"));
			
			$data = $this->points_transaction_report_hour->getPointsTransactionReportByUpdatedAt($from, $to);

			if(!empty($data)){
				//sync add and deduct points								
				$this->point_transactions->syncPlayerPoints($from, $to, $data, $playerId);
			}

			//check for possible deleted data
			$this->point_transactions->syncDeletedPlayerPoints($from, $to, $data, $playerId);

			$queryDateTimeStart = $queryDateTimeEnd;
			$queryDateTimeEnd  = (new DateTime($queryDateTimeStart))
			->modify("+$step minutes")->format('Y-m-d H:i:s');
    		# Query Exact end
    		if($queryDateTimeEnd > $queryDateTimeMax){
	    		$queryDateTimeEnd = $queryDateTimeMax;
	    	}
		}
	
		$this->utils->debug_log('=========end calculate_bet_to_points_hourly============================');
	}

    public function rebuild_points_transaction_report_hour($fromDateTime = null, $toDateTime = null, $playerId = null, $isSyncPlayerPoints = 'false', $token = null, $time_interval = 30)
    {
        $this->load->model(['points_transaction_report_hour', 'queue_result', 'player_model']);

        //check if feature is enabled
		$isEnabled = $this->utils->getConfig('enable_beting_amount_to_point');

		if(!$isEnabled)
        {
			$this->utils->debug_log('feature enable_beting_amount_to_point is disabled');
			return;
		}
        
        //if date time from and to is null or not set, the default date time will be today
        $defaultDateTime = new DateTime();

        if(empty($fromDateTime))
        {
            $fromDateTime = $defaultDateTime->format('Y-m-d') .' '. '00:00:00';
        }else{
            $fromDateTime = new DateTime($fromDateTime);
            $fromDateTime->modify("-$time_interval minutes");
            $fromDateTime = $fromDateTime->format('Y-m-d H:i:s');
        }

        if(empty($toDateTime))
        {
            $toDateTime = $defaultDateTime->format('Y-m-d') .' '. '23:59:59';
        }else{
            $toDateTime = new DateTime($toDateTime);
            $toDateTime->modify("+$time_interval minutes");
            $toDateTime = $toDateTime->format('Y-m-d H:i:s');
        }
        
        $this->utils->debug_log('============================ Check Date time from and to ============================', 'fromDateTime', $fromDateTime, 'toDateTime', $toDateTime);

        if($playerId != '_null')
        {
            $playerId = intval($playerId);
            $rebuild_points_of = $this->player_model->getUsernameById($playerId);
        }else{
            $playerId = null;
            $rebuild_points_of = "All Players";
        }
        
        $this->points_transaction_report_hour->startTrans();
        $pointsTransactionReportHourDataRow = $this->points_transaction_report_hour->sync(new DateTime($fromDateTime), new DateTime($toDateTime), $playerId, $isSyncPlayerPoints);
        $trans_success = $this->points_transaction_report_hour->endTransWithSucc();

        $newDataRow = [];
        foreach($pointsTransactionReportHourDataRow as $key => $dataRow) 
        {
            //add player name before player id
            $keyName = 'player_id';
            $offset = array_search($keyName, array_keys($pointsTransactionReportHourDataRow[$key])) + 1;
            $addPlayerNameToDataRow = array_merge(
                array_slice($pointsTransactionReportHourDataRow[$key], 0, $offset),
                array('player_name' => $this->player_model->getUsernameById($dataRow['player_id'])),
                array_slice($pointsTransactionReportHourDataRow[$key], $offset, null)
            );

            array_push($newDataRow, $addPlayerNameToDataRow);
        }

        $this->utils->debug_log('============================ Check count of points_transaction_report_hour newDataRow ============================', 'total count', count($newDataRow));
        
        $this->utils->debug_log('============================ Check token and endTransWithSucc ============================', 'token', $token, 'endTransWithSucc', $trans_success);

        if(!empty($token))
        {
            $process_start_time = $this->utils->getNowForMysql();

            $result = [
                'func' => 'rebuild_points_transaction_report_hour',
                'from' => $fromDateTime, 
                'to' => $toDateTime,
                'player_id' => $playerId,
                'rebuild_points_of' => $rebuild_points_of,
                'data' => $newDataRow,
                'process_start_time' => $process_start_time, 
                'process_end_time' => $this->utils->getNowForMysql(),
                'message' => ''
            ];

            $final_result = [
                'token' => $token,
                'success' => $trans_success,
                'message' => '',
                'progress' => 100,
                'total' => count($pointsTransactionReportHourDataRow),
                'done' => true
            ];

            if($trans_success)
            {
                $result['message'] = lang('Rebuild successfully!');
                $final_result['message'] = $result['message'];
                $this->queue_result->appendResult($final_result['token'], ['request_id' => _REQUEST_ID, 'result' => $result ], $final_result['done'], false);
                $this->queue_result->updateFinalResult($final_result['token'], $final_result['success'], $final_result['message'], $final_result['progress'], $final_result['total'], $final_result['done']);
                $this->utils->debug_Log("============================ rebuild_points_transaction_report_hour_result ============================", "token", $token, "result", $result, "final_result", $final_result);
            }else{
                $result['message'] = lang('Rebuild failed!');
                $final_result['message'] = $result['message'];
                $this->queue_result->appendResult($final_result['token'], ['request_id' => _REQUEST_ID, 'result' => $result ], $final_result['done'], true);
                $this->queue_result->updateFinalResult($final_result['token'], $final_result['success'], $final_result['message'], $final_result['progress'], $final_result['total'], $final_result['done']);
                $this->utils->debug_Log("============================ rebuild_points_transaction_report_hour_result ============================", "token", $token, "result", $result, "final_result", $final_result);
            }
        }
    }

}