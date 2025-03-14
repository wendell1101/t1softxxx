<?php
require_once dirname(__FILE__) . "/base_cli.php";

//require_once dirname(__FILE__) . '/../modules/sync_latest_game_records_command_module.php';

class Sync_latest_game_records extends Base_cli {

	//use sync_latest_game_records_command_module;

	const LOG_TITLE = '[sync_latest_game_records_controller]';

	public $oghome = null;

	/**
	 * overview : Sync_game_records constructor.
	 */
	public function __construct() {
		parent::__construct();
		$this->config->set_item('print_log_to_console', true);
		$this->oghome = realpath(dirname(__FILE__) . "/../../../");
	}

	/**
	 * overview : sync service start
	 *
	 * @param string $dateTimeFromStr
	 */
	public function start_sync_latest_game_records($dateTimeFromStr = '-10 minutes') {
		//never stop
		set_time_limit(0);
		$dateTimeFromStr = (new \DateTime($dateTimeFromStr))->format('Y-m-d H:i:00');
		$dateTimeToStr = date('Y-m-d H:i:59');
		$this->utils->debug_log('runing... started from:' . $dateTimeFromStr . 'to:' . $dateTimeToStr);

		$mark = 'benchSyncLatestGameRecords';
		//run sync
	    while (true) {
	    	# reset db
	        $this->resetDb($this->db);
	        
			$this->utils->markProfilerStart($mark);
			$this->run_sync_shell($dateTimeFromStr, $dateTimeToStr);
			$this->utils->markProfilerEndAndPrint($mark);

			$syncSleepTime = $this->config->item('sync_latest_game_records_sleep_seconds');
			$this->utils->debug_log('sleep...', $syncSleepTime, 'from', $dateTimeFromStr, 'to', $dateTimeToStr);
			sleep($syncSleepTime);

			//set next
			$dateTimeFromStr = $dateTimeToStr;
			$dateTimeToStr = date('Y-m-d H:i:s');

			$this->utils->debug_log('from', $dateTimeFromStr, 'to', $dateTimeToStr);

			gc_collect_cycles();
		}

		$this->utils->debug_log(self::LOG_TITLE, 'stopped');
	}

	/**
	 * overview : run sync shell
	 *
	 * @param string 	 $dateTimeFromStr
	 * @param string	 $dateTimeToStr
	 * @param string $playerName
	 */
	public function run_sync_shell($dateTimeFromStr, $dateTimeToStr) {

		$og_home = $this->oghome;

		$this->utils->debug_log("Sync latest game records");

		$cmd = 'bash '.$og_home . '/shell/sync_latest_game_records.sh "' . $dateTimeFromStr . '" "' . $dateTimeToStr . '"';
		
		//run merge
		$this->utils->debug_log('start sync', $cmd);
		$return = shell_exec($cmd);
		$this->utils->debug_log("sync_latest_game_records", 'return', $return);

		$this->utils->debug_log("sync_latest_game_records done");

		//all done
		// $this->returnText($str);
	}

	public function sync_game_records_latest($dateTimeFromStr = null, $dateTimeToStr = null){
		$default_sync_game_logs_max_time_second = $this->config->item('default_sync_game_logs_max_time_second');
		set_time_limit($default_sync_game_logs_max_time_second);
        $queue_token = $gamePlatformId= null;

		$this->utils->debug_log('========= start sync_latest_game_records ============================ api', $gamePlatformId);
		$this->utils->debug_log('========= start sync_latest_game_records ============================ date', $dateTimeFromStr ,'-' ,$dateTimeToStr);

		$this->load->model(array('sync_status_model', 'queue_result'));

		if(!empty($queue_token)){
			$this->utils->debug_log('append result ', _REQUEST_ID, $queue_token);
			//update queue_results
			$this->queue_result->appendResult($queue_token, ['game_api'=>$gamePlatformId,
				'request_id'=>_REQUEST_ID, 'func'=>'sync_latest_game_records']);
		}

        ## rebuild seamless latest game records
        # get data from game logs

		$this->load->model(array('player_latest_game_logs'));
        $dateTimeTo = $dateTimeFrom = new DateTime();
        if(empty($dateTimeFromStr)){
            $dateTimeFromStr = $dateTimeFrom->modify('-15 minutes')->format('Y-m-d H:i:00');

        }
        if(empty($dateTimeToStr)){
            $dateTimeToStr = $dateTimeTo->format('Y-m-d H:i:s');
        }
        $this->utils->debug_log('========= end syncSeamlessGameBatchPayoutOnOnePlatform ============================ date', $dateTimeFromStr . ' to ' . $dateTimeToStr);
        $resp = $this->player_latest_game_logs->sync(new DateTime($dateTimeFromStr), new DateTime($dateTimeToStr));

		//save to cache by game code
		if($this->utils->getConfig('sync_latest_game_records_save_cache_enabled')) {		
			$game_codes = $this->utils->getConfig('latest_bets_game_code');
			$cached = false;
			if(!empty($game_codes)) {				
				$res = $this->player_latest_game_logs->get_latest_bets($game_codes, $cached, true);
			}
			$game_types = $this->utils->getConfig('latest_bets_game_type');
			if(!empty($game_types)) {
				foreach($game_types as $game_type){
					$res = $this->player_latest_game_logs->get_latest_bets_by_game_type($game_type, $cached, true);
				}
			}
		}

		$this->utils->debug_log('========= end syncSeamlessGameBatchPayoutOnOnePlatform ============================', $gamePlatformId,
        'response', $resp);
		return;
	}

}

/// END OF FILE//////////////
