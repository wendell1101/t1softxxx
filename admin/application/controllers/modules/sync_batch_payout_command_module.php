<?php

trait sync_batch_payout_command_module {

	public function sync_seamless_game_batch_payout($gamePlatformId, $dateTimeFromStr = null, $dateTimeToStr = null,$queue_token=null){
		$default_sync_game_logs_max_time_second = $this->config->item('default_sync_game_logs_max_time_second');
		set_time_limit($default_sync_game_logs_max_time_second);

		$this->utils->debug_log('========= start sync_seamless_game_batch_payout ============================', $gamePlatformId);
		$this->utils->debug_log('========= start sync_seamless_game_batch_payout ============================ date', $dateTimeFromStr ,'-' ,$dateTimeToStr);

		$this->load->model(array('sync_status_model', 'queue_result'));

		if(!empty($queue_token)){
			$this->utils->debug_log('append result ', _REQUEST_ID, $queue_token);
			//update queue_results
			$this->queue_result->appendResult($queue_token, ['game_api'=>$gamePlatformId,
				'request_id'=>_REQUEST_ID, 'func'=>'sync_seamless_game_batch_payout']);
		}

		$dateTimeFrom = new \DateTime($dateTimeFromStr);
		$dateTimeTo = new \DateTime($dateTimeToStr);
		list($todayFrom, $todayTo) = $this->utils->getTodayDateTimeRange();
		//set default datetime from to
		if (empty($dateTimeFromStr)) {
			$dateTimeFrom = $todayFrom;
		}
		if (empty($dateTimeToStr)) {
			$dateTimeTo = $todayTo;
		}

		$note = 'pid:' . getmypid();
		$func = 'sync_seamless_game_batch_payout ';

		$this->load->library('game_platform/game_platform_manager');
		//$manager = $this->CI->utils->loadGameManager();

		$rlt = $this->game_platform_manager->syncSeamlessGameBatchPayoutOnOnePlatform($gamePlatformId, $dateTimeFrom, $dateTimeTo,null, null, null);

		$this->utils->debug_log('========= end syncSeamlessGameBatchPayoutOnOnePlatform ============================', $gamePlatformId);
		return;
	}
	
	//using redis
	public function sync_seamless_game_batch_payout_redis($gamePlatformId, $dateTimeFromStr = null, $dateTimeToStr = null,$queue_token=null){
		$default_sync_game_logs_max_time_second = $this->config->item('default_sync_game_logs_max_time_second');
		set_time_limit($default_sync_game_logs_max_time_second);

		//get redis version
		//$this->utils->debug_log('========= start sync_seamless_game_batch_payout_redis redisVersion============================', phpversion('redis'));

		$this->utils->debug_log('========= start sync_seamless_game_batch_payout_redis ============================', $gamePlatformId);
		$this->utils->debug_log('========= start sync_seamless_game_batch_payout_redis ============================ date', $dateTimeFromStr ,'-' ,$dateTimeToStr);

		$this->load->model(array('sync_status_model', 'queue_result'));

		if(!empty($queue_token)){
			$this->utils->debug_log('append result ', _REQUEST_ID, $queue_token);
			//update queue_results
			$this->queue_result->appendResult($queue_token, ['game_api'=>$gamePlatformId,
				'request_id'=>_REQUEST_ID, 'func'=>'sync_seamless_game_batch_payout_redis']);
		}

		$dateTimeFrom = new \DateTime($dateTimeFromStr);
		$dateTimeTo = new \DateTime($dateTimeToStr);
		list($todayFrom, $todayTo) = $this->utils->getTodayDateTimeRange();
		//set default datetime from to
		if (empty($dateTimeFromStr)) {
			$dateTimeFrom = $todayFrom;
		}
		if (empty($dateTimeToStr)) {
			$dateTimeTo = $todayTo;
		}

		$note = 'pid:' . getmypid();
		$func = 'sync_seamless_game_batch_payout_redis ';

		$this->load->library('game_platform/game_platform_manager');
		//$manager = $this->CI->utils->loadGameManager();

		$rlt = $this->game_platform_manager->syncSeamlessGameBatchPayoutRedisOnOnePlatform($gamePlatformId, $dateTimeFrom, $dateTimeTo,null, null, null);

		$this->utils->debug_log('========= end syncSeamlessGameBatchPayoutRedisOnOnePlatform ============================', $gamePlatformId);
		return;
	}


}