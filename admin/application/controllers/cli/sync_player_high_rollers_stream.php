<?php
require_once dirname(__FILE__) . "/base_cli.php";

class Sync_player_high_rollers_stream extends Base_cli {

	const LOG_TITLE = '[sync_player_high_rollers_stream_controller]';

	public $oghome = null;

	/**
	 * overview : Sync_player_high_rollers_stream constructor.
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
	public function sync_service_start($dateTimeFromStr = '-10 minutes') {
		//never stop
		set_time_limit(0);
		$dateTimeFromStr = (new \DateTime($dateTimeFromStr))->format('Y-m-d H:i:00');
		$dateTimeToStr = date('Y-m-d H:i:59');
		$this->utils->debug_log('runing... started from:' . $dateTimeFromStr . 'to:' . $dateTimeToStr);

		$mark = 'benchSyncPlayerHighRollersStream';
		//run sync
	    while (true) {
	    	# reset db
	        $this->resetDb($this->db);
	        
			$this->utils->markProfilerStart($mark);
			$this->run_sync_shell($dateTimeFromStr, $dateTimeToStr);
			$this->utils->markProfilerEndAndPrint($mark);

			$syncSleepTime = $this->config->item('sync_sleep_seconds');
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

		$cmd = 'bash '.$og_home . '/shell/sync_player_high_rollers_stream.sh "' . $dateTimeFromStr . '" "' . $dateTimeToStr . '"';
		
		//run sync
		$this->utils->debug_log('start sync', $cmd);
		$return = shell_exec($cmd);
		$this->utils->debug_log("sync_player_high_rollers_stream", 'return', $return);
		$this->utils->debug_log("sync_player_high_rollers_stream done");
	}

	public function sync($dateTimeFromStr = null, $dateTimeToStr = null){
		$default_sync_game_logs_max_time_second = $this->config->item('default_sync_game_logs_max_time_second');
		set_time_limit($default_sync_game_logs_max_time_second);
        $queue_token = null;


		$this->utils->debug_log('>> start sync_player_high_rollers_stream >> date', $dateTimeFromStr ,'-' ,$dateTimeToStr);

		$this->load->model(array('sync_status_model', 'queue_result'));

		if(!empty($queue_token)){
			$this->utils->debug_log('append result ', _REQUEST_ID, $queue_token);
			//update queue_results
			$this->queue_result->appendResult($queue_token, ['request_id'=>_REQUEST_ID, 'func'=>'sync_player_high_rollers_stream']);
		}

		$this->load->model(array('player_high_rollers_stream'));
        $dateTimeTo = $dateTimeFrom = new DateTime();
        if(empty($dateTimeFromStr)){
            $dateTimeFromStr = $dateTimeFrom->modify('-15 minutes')->format('Y-m-d H:i:00');

        }
        if(empty($dateTimeToStr)){
            $dateTimeToStr = $dateTimeTo->format('Y-m-d H:i:s');
        }
        $this->utils->debug_log('>> end sync_player_high_rollers_stream >> date', $dateTimeFromStr . ' to ' . $dateTimeToStr);
        $resp = $this->player_high_rollers_stream->sync(new DateTime($dateTimeFromStr), new DateTime($dateTimeToStr));
		$this->utils->debug_log('>> end sync_player_high_rollers_stream >>','response', $resp);
		return;
	}

}

/// END OF FILE//////////////
