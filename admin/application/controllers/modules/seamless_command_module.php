<?php

trait seamless_command_module {

	public function check_seamless_round_status($fromDateTime = null, $toDateTime = null, $playerId = null) {
		
		//cehck if feature is enabled
        $isEnabled = $this->utils->getConfig('enable_check_seamless_api_bet_status');
        $apiIds = $this->utils->getConfig('check_seamless_api_bet_status_api_ids');
        $offset = $this->utils->getConfig('check_seamless_api_bet_status_offset');		       
		$step = $this->utils->getConfig('check_seamless_api_bet_status_step');
		if(!$isEnabled && empty($apiIds)){
			$this->utils->debug_log('========= check_seamless_api_bet_status disabled ============================', 'isEnabled', $isEnabled, 'apiIds', $apiIds);
			return;
        }
        
        if(empty($offset)){
            $offset = 10;//minutes
        }
		if(empty($step)){
            $step = 5;//minutes
        }

        $this->utils->debug_log('========= check_seamless_api_bet_status start ============================');

		$this->load->library(array('authentication'));
		$this->load->model(array('amb_transactions', 'external_system'));

		//if null from and to default range is 5 minutes, start=now-7minutes,to=now-2minutes
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

		$missingRounds = [];
		$gameMaintenanceNotices = [];
		$apiIds2 = $this->CI->utils->getConfig('check_seamless_api_bet_status_auto_maintenance_apis'); 	//Avoid putting sports game on this config; settlements is expected to be long
		$threshold = $this->CI->utils->getConfig('check_seamless_api_bet_status_auto_maintenance_threshold');
		$mm_channel = $this->CI->utils->getConfig('check_seamless_api_bet_status_auto_maintenance_mm_channel');
		$client = $this->CI->utils->getConfig('check_seamless_api_bet_status_auto_maintenance_client');
		$enabled_game_auto_maintenance = $this->CI->utils->getConfig('check_seamless_api_bet_status_enable_auto_maintenance_game');
		while ($queryDateTimeMax  > $queryDateTimeStart) {
			$from = new DateTime($queryDateTimeStart);
            $to = new DateTime($queryDateTimeEnd);
            
			$this->utils->debug_log('========= loop check_seamless_api_bet_status ============================',$from->format("Y-m-d H:i:s"),$to->format("Y-m-d H:i:s"));
			
            foreach($apiIds as $gamePlatformId){
                $api = $this->utils->loadExternalSystemLibObject($gamePlatformId);
                if(!$api){
                	continue;
                }
                //get pending bet status
				$rounds = $api->getUnsettledRounds($from->format("Y-m-d H:i:00"),$to->format("Y-m-d H:i:59"));
				if(empty($rounds)){
					continue;
				}

				$missingRounds[] = ['game_platform_id' => $gamePlatformId, 'rounds' => $rounds];

				if(in_array($gamePlatformId,$apiIds2)){
					if(count($rounds)>=$threshold && $enabled_game_auto_maintenance){
						$gameMaintenanceNotices [] = [
							'game_platform_id' => $gamePlatformId,
							'count' => count($rounds),
							'threshold' => $threshold
						];
						
					}
				}

                foreach($rounds as $key=>$round){
                    //check bet status, depends on api what will do with result
					$checkBetStatusResult = $api->checkBetStatus($round);
					$this->utils->debug_log('check_seamless_api_bet_status', 'round', $round, 'checkBetStatusResult',$checkBetStatusResult);
					//exit;
                }
            }

			$queryDateTimeStart = $queryDateTimeEnd;
			$queryDateTimeEnd  = (new DateTime($queryDateTimeStart))->modify("+$step minutes")->format('Y-m-d H:i:59');
            
    		# Query Exact end
    		if($queryDateTimeEnd > $queryDateTimeMax){
	    		$queryDateTimeEnd = $queryDateTimeMax;
	    	}
		}


		//Send alert once after all games has been checked
		if(!empty($missingRounds)){
			$message = "@all SEAMLESS GAMES MISSING SETTLEMENT NOTICE!!!"."\n";            
			$message .= 'Client: '.$client."\n";       			
			$message .= 'Date: '.$from->format("Y-m-d H:i:00").' - '.$to->format("Y-m-d H:i:59")."\n";       
			$message .= 'Missing Settlements: ```'.json_encode($missingRounds). '```' ."\n";       						

			$this->CI->load->helper('mattermost_notification_helper');

			$notif_message = array(
				array(
					'text' => $message,
					'type' => 'warning'
				)
			);
			sendNotificationToMattermost("API ({$gamePlatformId})", $mm_channel, $notif_message, null);
		}

		// maintenance game and send alert if enabled
		if (!empty($gameMaintenanceNotices)) {
			$processedPlatformIds = []; // Track already processed game_platform_id values
		
			foreach ($gameMaintenanceNotices as $item) {
				$gamePlatformId = isset($item['game_platform_id']) ? $item['game_platform_id'] : null;
		
				// Skip duplicate game_platform_id
				if (in_array($gamePlatformId, $processedPlatformIds)) {
					continue;
				}
		
				// Add to the processed list
				$processedPlatformIds[] = $gamePlatformId;
		
				$missingPayoutCount = isset($item['count']) ? $item['count'] : null;
				$missingPayoutThresholdCount = isset($item['threshold']) ? $item['threshold'] : null;
		
				$this->db->where('id', $gamePlatformId)
						 ->set(['maintenance_mode' => External_system::DB_TRUE, 'updated_at' => $this->CI->utils->getNowForMysql()]);
				$this->external_system->runAnyUpdate('external_system');
		
				$this->utils->debug_log('check_seamless_api_bet_status put game to maintenance', 'gamePlatformId', $gamePlatformId);
		
				// Prepare the notification message
				$message = "@all Seamless Game ({$gamePlatformId}) AUTO MAINTENANCE NOTICE!!!; Missing settlement count already: $missingPayoutThresholdCount\n";
				$message .= 'Client: ' . $client . "\n";
				$message .= 'Date: ' . $from->format("Y-m-d H:i:00") . ' - ' . $to->format("Y-m-d H:i:59") . "\n";
				$message .= 'Missing Settlements Count: ' . $missingPayoutCount . "\n";
		
				$this->CI->load->helper('mattermost_notification_helper');
		
				$notif_message = [
					[
						'text' => $message,
						'type' => 'warning'
					]
				];
				sendNotificationToMattermost("API ({$gamePlatformId})", $mm_channel, $notif_message, null);
			}
		}
		

	
		$this->utils->debug_log('=========end check_seamless_api_bet_status ============================');
	}
}