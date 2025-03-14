<?php

trait player_activity_command_module {

	public function sync_player_activity_logs($fromDateTime = null, $toDateTime = null) {
		//check if feature is enabled
		$isEnabled = $this->utils->getConfig('enable_player_activity_logs');
		if(!$isEnabled){
			return;
		}
		$offset = $this->utils->getConfig('player_activity_logs_offset');		       
		$step = $this->utils->getConfig('player_activity_logs_step');

		if(empty($offset)){
            $offset = 10;//minutes
        }
		if(empty($step)){
            $step = 5;//minutes
        }

		$startDateTime = new DateTime();				
		$startDateTime->modify("-$offset minutes");
		$endDateTime = $startDateTime;
		$endDateTime->modify("+$step minutes");
		
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

		$this->load->model(['player_activity_stream']);
		$player_activity_logs_allowed_days = $this->utils->getConfig('player_activity_logs_allowed_days');
		$tableNames =  $this->getTransactionTables($player_activity_logs_allowed_days);

		while ($queryDateTimeMax  > $queryDateTimeStart) {
			$from = new DateTime($queryDateTimeStart);
            $to = new DateTime($queryDateTimeEnd);
			$mergedData = [];

			foreach ($tableNames as $table){
				//check if table exists
				if ($this->CI->utils->table_really_exists($table)){
					$currentTableData = $this->player_activity_stream->queryPlayerActivityLogs($table, $from->format("Y-m-d H:i:00"), $to->format("Y-m-d H:i:59"));
					if(!empty($currentTableData)){
						foreach ($currentTableData as $row) {
							$mergedData[] = $row;
						}
					}
				}
			
			}

			$respResultData=[];
			$depositData=[];
			$withdrawData=[];
			if(!empty($mergedData)){
				$respResultData = $this->rebuildDataFromResponseResult($mergedData);
			}
			$depositTransactions = $this->player_activity_stream->queryDepositTransactions('sale_orders', $from->format("Y-m-d H:i:00"), $to->format("Y-m-d H:i:59"));
			$withdrawTransactions = $this->player_activity_stream->queryWithdrawTransactions('walletaccount', $from->format("Y-m-d H:i:00"), $to->format("Y-m-d H:i:59"));

			if(!empty($depositTransactions)){
				$tempDepositData = $this->rebuildDepositData($depositTransactions);
				$depositData = array_merge(
					!empty($tempDepositData) ? call_user_func_array('array_merge', $tempDepositData) : []
				);
	
			}

			if(!empty($withdrawTransactions)){
				$tempWithdrawData = $this->rebuildWithdrawData($withdrawTransactions);	
				$withdrawData = array_merge(
					!empty($tempDepositData) ? call_user_func_array('array_merge', $tempWithdrawData) : []
				);
			}

			$respResultData = is_array($respResultData) && !empty($respResultData) ? $respResultData : [];
			$depositData = is_array($depositData) && !empty($depositData) ? $depositData : [];
			$withdrawData = is_array($withdrawData) && !empty($withdrawData) ? $withdrawData : [];

			$respResultData = array_filter($respResultData, function($item){
				return is_array($item) && isset($item['request_id']) && !empty($item['request_id']);
			});
			
		
			if(!empty($respResultData)){
				$this->player_activity_stream->savePlayerActivityLogs($respResultData);
			}
			if(!empty($depositData)){
				$this->player_activity_stream->savePlayerActivityLogs($depositData);
			}
			if(!empty($withdrawData)){
				$this->player_activity_stream->savePlayerActivityLogs($withdrawData);
			}

			$queryDateTimeStart = $queryDateTimeEnd;
			$queryDateTimeEnd  = (new DateTime($queryDateTimeStart))->modify("+$step minutes")->format('Y-m-d H:i:59');
            
    		# Query Exact end
    		if($queryDateTimeEnd > $queryDateTimeMax){
	    		$queryDateTimeEnd = $queryDateTimeMax;
	    	}
		}
	}

	private function getOriginalTablePrefix(){
		return 'resp';
	}


	private function getTransactionTables($allowedDays)
	{
		$tables = [];
		$date = new DateTime();
	
		// Generate the current day and the last allowed days
		for ($i = 1	; $i <=$allowedDays; $i++) {
			$dayMonthString = $date->format('Ymd');
			$tables[] = $this->initGameTransactionsDailyTableByDate($dayMonthString);
			$date->modify('-1 day');
		}
		return $tables;
	}
	
	private function initGameTransactionsDailyTableByDate($dayMonthString)
	{
		return $this->getOriginalTablePrefix() . '_' . $dayMonthString;
	}


	private function rebuildDataFromResponseResult($rawData){
		$this->load->model(array('player_activity_stream','common_token'));
		$data = [];
		if(!empty($rawData)){
			foreach($rawData as $item){
				$game_platform_id = null;
				$content = isset($item['content']) ? json_decode($item['content']) : [];
				$request_params = isset($content->request_params) ? $content->request_params: null;
				$request_api = isset($item['request_api']) ? $item['request_api'] : null;
				$player_id=isset($content->player_id) ? $content->player_id: null;
				$player_activity_action_type=$this->player_activity_stream->getPlayerActionType($request_api);
				$extra=[];
	
				$game_platform_id = isset($request_params->virtualGameId) ? $request_params->virtualGameId : null;
				$extra = !empty($content->extra) ? json_decode($content->extra) : [];
				
				$device_type = null;
	
				foreach ($extra as $i) {
					if (isset($i->device)) {
						$device_type = $i->device;
						break;
					}
				}

				$extra[]['browser_user_agent']=$device_type;
	
				if($game_platform_id !== null){
					$extra[]['virtualGameId'] = $game_platform_id;
				}
	
				$data[] = [
					'status' => $item['status'],
					'http_status_code' => isset($content->status_code) ? $content->status_code: null,
					'domain' => isset($content->domain) ? $content->domain: null,
					'cost_ms' => isset($item['cost_ms']) ? $item['cost_ms']: null,
					'response_result_id' => isset($item['response_result_id']) ? $item['response_result_id']: null,
					'client_ip' => isset($content->client_ip) ? $content->client_ip: null,
					'device_type' => $device_type,
					'player_id' => $player_id,
					'date_time' => isset($content->created_at) ? $content->created_at: null,
					'request_id' => isset($content->request_id) ? $content->request_id: null,
					'request_params' => isset($content->request_params) ? json_encode($content->request_params): null,
					'response_params' => isset($content->resultText) ? json_encode($content->resultText) : null,
					'player_activity_action_type' => $player_activity_action_type,
					'extra_info' => json_encode($extra),
					'raw_data' => json_encode($item)
				];
			}
		}

		return $data;
	}

	private function rebuildDepositData($rawData){
		$data = [];
		$this->load->model(array('player_activity_stream', 'sale_order'));
		if(!empty($rawData)){
			foreach($rawData as $item){
				$statusText = $this->sale_order->getStatusText($item['status']);
				$paymentAccountName = $item['payment_account_name'];
	
				$extra = [
					'transaction_type' => 'deposit',
					'status_text' => isset($statusText) ? $statusText : null,
					'amount' => isset($item['amount']) ? $item['amount'] : null,
					'transaction_id' => isset($item['secure_id']) ? $item['secure_id'] : null,
					'notes' => isset($item['notes']) ? $item['notes'] : null,
					'payment_account_name' => isset($paymentAccountName) ? $paymentAccountName : null,
					'browser_user_agent' => isset($item['browser_user_agent']) ? $item['browser_user_agent'] : null,
					'geo_location' => isset($item['geo_location']) ? $item['geo_location'] : null,
				];
	
				$data[] = [
					[
						'request_id' => isset($item['secure_id']) ? $item['secure_id'] : null,
						'status' => 1,
						'client_ip' => isset($item['ip']) ? $item['ip'] : null,
						'device_type' => isset($item['browser_user_agent']) ? $item['browser_user_agent'] : null,
						'player_activity_action_type' => $this->player_activity_stream->getPlayerActionType('deposit'),
						'player_id' => isset($item['player_id']) ? $item['player_id'] : null,
						'date_time' => isset($item['process_time']) ? $item['process_time'] : null,
						'extra_info' => isset($extra) ? json_encode($extra) : null,
						'raw_data' => isset($raw_data) ? json_encode($raw_data) : null,
					]
				];
			}
		}
		
		return $data;
	}

	private function rebuildWithdrawData($rawData){
		$data = [];
		$this->load->model(array('player_activity_stream', 'sale_order'));
		$this->load->library(array('language_function'));
		if(!empty($rawData)){
			foreach($rawData as $item){
				$statusText = $this->sale_order->getStatusText($item['status']);
				$bankName = isset($item['bankName']) ? $item['bankName'] : null;
				$langId = $this->language_function->getCurrentLanguage();
				$langCode = $this->language_function->getCurrentLangForPromo(true,$langId);
				$paymentAccountName = $this->utils->extractLangJson($bankName)[$langCode] ?: null;
	
				$extra = [
					'transaction_type' => 'withdraw',
					'walletType' => isset($item['walletType']) ? $item['walletType'] : null,
					'status_text' => isset($statusText) ? $statusText : null,
					'amount' => isset($item['amount']) ? $item['amount'] : null,
					'transaction_id' => isset($item['transactionCode']) ? $item['transactionCode'] : null,
					'notes' => isset($item['notes']) ? $item['notes'] : null,
					'payment_account_name' => isset($paymentAccountName) ? $paymentAccountName : null,
					'browser_user_agent' => isset($item['browser_user_agent']) ? $item['browser_user_agent'] : null,
					'geo_location' => isset($item['geo_location']) ? $item['geo_location'] : null,
				];
	
				$data[] = [
					[
						'request_id' => isset($item['transactionCode']) ? $item['transactionCode'] : null,
						'status' => 1,
						'client_ip' => isset($item['ip']) ? $item['ip'] : null,
						'device_type' => isset($item['browser_user_agent']) ? $item['browser_user_agent'] : null,
						'player_activity_action_type' => $this->player_activity_stream->getPlayerActionType('withdraw'),
						'player_id' => isset($item['playerId']) ? $item['playerId'] : null,
						'date_time' => isset($item['process_time']) ? $item['process_time'] : null,
						'extra_info' => isset($extra) ? json_encode($extra) : null,
						'raw_data' => isset($raw_data) ? json_encode($raw_data) : null,
					]
				];
			}
		}
		
		return $data;
	}

	public function sync_player_last_played($fromDateTime = null, $toDateTime = null){

		if(empty($toDateTime)){
			$toDateTime = $this->utils->getNowForMysql();
		}

		if(empty($fromDateTime)){
			$minutes = $this->utils->getConfig('get_sync_player_last_played_minutes');
			$fromDateTime = $this->utils->formatDateTimeForMysql((new DateTime($to))->modify('-'.$minutes.' minutes'));
		}

		$this->load->model(['game_logs_stream']);
		$cnt = $this->game_logs_stream->replace_player_last_bet($fromDateTime, $toDateTime);
		$this->utils->debug_log('sync_player_last_played count', $cnt);
	}

	public function sync_player_last_played_cronjob($minutes=-1){

		$get_sync_player_last_played_minutes = $this->utils->getConfig('get_sync_player_last_played_minutes');
		if($minutes <= 0){
			$minutes = $get_sync_player_last_played_minutes;
		}

		set_time_limit($minutes*60);

		$this->utils->debug_log('sync_player_last_played_cronjob get last '.$minutes.' minutes');
		$from = $this->utils->formatDateTimeForMysql(new DateTime('-'.$minutes.' minutes'));

		$this->sync_player_last_played($from);
	}

	private function flattenArray($array) {
		$flattened = [];
	
		foreach ($array as $item) {
			if (is_array($item)) {
				// If it's an array, recurse into it
				$flattened = array_merge($flattened, $this->flattenArray($item));
			} else {
				// Otherwise, just add the item
				$flattened[] = $item;
			}
		}
	
		return $flattened;
	}
}