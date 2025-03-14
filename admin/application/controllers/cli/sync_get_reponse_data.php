<?php
require_once dirname(__FILE__) . "/base_cli.php";

require_once dirname(__FILE__) . '/../modules/sync_3rdparty_command_module.php';

class Sync_get_reponse_data extends Base_cli {

	use sync_3rdparty_command_module;

	const LOG_TITLE = '[sync_get_reponse_data_controller]';

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
	public function start_sync_get_response($dateTimeFromStr = '-10 minutes') {
		//never stop
		set_time_limit(0);
		$dateTimeFromStr = (new \DateTime($dateTimeFromStr))->format('Y-m-d H:i:00');
		$dateTimeToStr = date('Y-m-d H:i:59');
		$this->utils->debug_log('runing... started from:' . $dateTimeFromStr . 'to:' . $dateTimeToStr);

		$mark = 'benchSyncGetResponseData';
		//run sync
	    while (true) {
	    	# reset db
	        $this->resetDb($this->db);
	        
			$this->utils->markProfilerStart($mark);
			$this->run_sync_shell($dateTimeFromStr, $dateTimeToStr);
			$this->utils->markProfilerEndAndPrint($mark);

			$syncSleepTime = $this->config->item('sync_get_response_sleep_seconds');
			$this->utils->debug_log('sleep...', $syncSleepTime, 'from', $dateTimeFromStr, 'to', $dateTimeToStr);
			sleep($syncSleepTime);

			//set next
			$dateTimeFromStr = $dateTimeToStr;
			$dateTimeToStr = date('Y-m-d H:i:s');

			$this->utils->debug_log('start_sync_get_response from', $dateTimeFromStr, 'to', $dateTimeToStr);

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

		$this->utils->debug_log("Sync get reponse datarun_sync_shell : ", $dateTimeFromStr, $dateTimeToStr);

		$cmd = 'bash '.$og_home . '/shell/sync_get_response_data.sh "' . $dateTimeFromStr . '" "' . $dateTimeToStr . '"';
		
		//run merge
		$this->utils->debug_log('start sync', $cmd);

		// $cmd = $this->getCommandLine('merge_game_logs_and_total_stats', array($dateTimeFromStr, $dateTimeToStr, $playerName));
		// $PHP . ' ' . $og_home . '/shell/ci_cli.php cli/sync_game_records/merge_game_logs_and_total_stats "' . $dateTimeFromStr . '" "' . $dateTimeToStr . '" "' . $playerName . '" ';

		$return = shell_exec($cmd);
		$this->utils->debug_log("bermar sync_get_response_data", 'return', $return);

		$this->utils->debug_log("sync_getreponse_data done");

		//all done
		// $this->returnText($str);
	}

}

/// END OF FILE//////////////
