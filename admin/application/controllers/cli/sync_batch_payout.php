<?php
require_once dirname(__FILE__) . "/base_cli.php";

require_once dirname(__FILE__) . '/../modules/sync_batch_payout_command_module.php';

class Sync_batch_payout extends Base_cli {

	use sync_batch_payout_command_module;

	const LOG_TITLE = '[sync_game_records_controller]';

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
	public function start_sync_batch_payout($dateTimeFromStr = '-10 minutes') {
		//never stop
		set_time_limit(0);
		$dateTimeFromStr = (new \DateTime($dateTimeFromStr))->format('Y-m-d H:i:00');
		$dateTimeToStr = date('Y-m-d H:i:59');
		$this->utils->debug_log('runing... started from:' . $dateTimeFromStr . 'to:' . $dateTimeToStr);

		$mark = 'benchSyncSeamlessGameBatchPayout';
		//run sync
	    while (true) {
	    	# reset db
	        $this->resetDb($this->db);
	        
			$this->utils->markProfilerStart($mark);
			$this->run_sync_shell($dateTimeFromStr, $dateTimeToStr);
			$this->utils->markProfilerEndAndPrint($mark);

			$syncSleepTime = $this->config->item('sync_batch_payout_sleep_seconds');
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
	 * overview : sync service start
	 *
	 * @param string $dateTimeFromStr
	 */
	public function start_sync_batch_payout_redis($dateTimeFromStr = '-10 minutes') {
		//never stop
		set_time_limit(0);
		$dateTimeFromStr = (new \DateTime($dateTimeFromStr))->format('Y-m-d H:i:00');
		$dateTimeToStr = date('Y-m-d H:i:59');
		$this->utils->debug_log('runing... started from:' . $dateTimeFromStr . 'to:' . $dateTimeToStr);

		$mark = 'benchSyncSeamlessGameBatchPayout';
		//run sync
	    while (true) {
	    	# reset db
	        $this->resetDb($this->db);
	        
			$this->utils->markProfilerStart($mark);
			$this->run_sync_shell($dateTimeFromStr, $dateTimeToStr);
			$this->utils->markProfilerEndAndPrint($mark);

			$syncSleepTime = $this->config->item('sync_batch_payout_sleep_seconds');
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
		//create pool by all game apis
		$apis = $this->config->item('enable_sync_batch_payout_for_gameplatform');

		// $ignore_apis = $this->config->item('og_sync_ignore_api');
		// if(!empty($ignore_apis)) {
		// 	$this->utils->debug_log("Sync game logs ignoring the following APIs: ", $ignore_apis);
		// 	$apis = array_diff($apis, $ignore_apis);
		// }

        $this->load->model('external_system');
        $maintenance_apis = array_column($this->external_system->getGameApiMaintenanceOrPauseSyncing('maintenance_mode'), 'id');
        if(!empty($maintenance_apis)){
            $this->utils->debug_log("Sync game logs ignoring the following under maintenance APIs: ", $maintenance_apis);
            $apis = array_diff($apis, $maintenance_apis);
        }
        $pauseSync_apis = array_column($this->external_system->getGameApiMaintenanceOrPauseSyncing('pause_sync'), 'id');
        if(!empty($pauseSync_apis)){
            $this->utils->debug_log("Sync game logs ignoring the following under pause syncing APIs: ", $pauseSync_apis);
            $apis = array_diff($apis, $pauseSync_apis);
        }

		$og_home = $this->oghome;

		$this->utils->debug_log("Sync game logs apis syncing batch payout: ", $apis);

		$cmd = 'bash '.$og_home . '/shell/sync_batch_payout.sh "' . implode(' ', $apis) . '" "' . $dateTimeFromStr . '" "' . $dateTimeToStr . '"';
		
		//run merge
		$this->utils->debug_log('start sync', $cmd);

		// $cmd = $this->getCommandLine('merge_game_logs_and_total_stats', array($dateTimeFromStr, $dateTimeToStr, $playerName));
		// $PHP . ' ' . $og_home . '/shell/ci_cli.php cli/sync_game_records/merge_game_logs_and_total_stats "' . $dateTimeFromStr . '" "' . $dateTimeToStr . '" "' . $playerName . '" ';

		$return = shell_exec($cmd);
		$this->utils->debug_log("sync_batch_payout", 'return', $return);

		$this->utils->debug_log("sync_batch_payout done");

		//all done
		// $this->returnText($str);
	}

}

/// END OF FILE//////////////
