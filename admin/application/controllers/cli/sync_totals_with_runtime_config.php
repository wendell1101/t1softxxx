<?php
require_once dirname(__FILE__) . "/base_cli.php";

/**
 * Class Sync_totals_with_runtime_config
 *
 * General behaviors include :
 *
 * * Sync totals for game api
 *
 * init start time from runtime config file, trace last game logs
 * when sync failed and retry failed too, not write to runtime config, next loop will start same time again
 *
 * @category Command Line
 * @version 7.78
 * @copyright 2013-2022 tot
 */
class Sync_totals_with_runtime_config extends Base_cli {

	public $oghome = null;

	public function __construct() {
		parent::__construct();

		$this->config->set_item('print_log_to_console', true);

		$this->oghome = realpath(dirname(__FILE__) . "/../../../");
	}

	/**
	 * overview : sync service start
	 *
	 * @param string $gamePlatformId
	 */
	public function sync_totals_start($gamePlatformId) {
		//never stop
		set_time_limit(0);
		$db=$this->db;
		//convert to int
		$gamePlatformId=intval($gamePlatformId);

		$dbName=$db->getOgTargetDB();
		if(!$this->utils->isEnabledMDB()){
			$dbName=null;
		}
		$this->utils->debug_log('==========runing...', $gamePlatformId);

		$mark = 'benchSyncTotalsWithRuntimeConfig';
		//run sync
		while (true) {
			$syncSleepTime = $this->config->item('sync_totals_sleep_seconds');
			$this->utils->markProfilerStart($mark);
			$this->utils->debug_log("try start", $gamePlatformId);
			$file_list=[];
			//run
			$cmd = $this->getCommandLine($gamePlatformId, 'sync', [], $file_list, $dbName);
			$this->utils->debug_log('start sync '.$mark, $cmd);
			shell_exec($cmd);
			$this->utils->debug_log('end sync '.$mark);
			if(!empty($file_list)){
				foreach ($file_list as $f) {
					// $this->utils->debug_log('delete file: '.$f);
					if($this->utils->getConfig('always_delete_total_tmp_shell')){
						unlink($f);
					}
				}
			}

			$this->utils->markProfilerEndAndPrint($mark);
			$this->utils->debug_log('sleep...', $syncSleepTime);
			for ($i = 0; $i < $syncSleepTime; $i++) {
				sleep(1);
			}
			gc_collect_cycles();
		}

		$this->utils->debug_log('============stopped', $gamePlatformId);
	}

	/**
	 * overview : get command line
	 *
	 * @param string $func
	 * @param array $args
	 * @param array $file_list
	 * @param string $dbName
	 * @return string
	 */
	protected function getCommandLine($gamePlatformId, $func, $args, &$file_list, $dbName) {
		$og_home = $this->oghome;
		$php_str=$this->utils->find_out_php();

		$argStr = '';
		if (!empty($args)) {
			foreach ($args as $val) {
				$argStr .= ' "' . $val . '"';
			}
		}

		$cmd = $php_str . ' ' . $og_home . '/shell/ci_cli.php cli/sync_totals_with_runtime_config/'.$func.'/'.$gamePlatformId.$argStr;
		if(empty($dbName)){
			return $this->utils->generateCommonLine($cmd, true, $func, $file_list);
		}else{
			return $this->utils->generateCommonLine($cmd, true, $func, $file_list, $dbName);
		}
	}

	public function sync($gamePlatformId, $playerName = null){

		$default_sync_game_logs_max_time_second = $this->config->item('default_sync_game_logs_max_time_second');
		set_time_limit($default_sync_game_logs_max_time_second);

		if($playerName=='_null'){
			$playerName=null;
		}
		$gamePlatformId=intval($gamePlatformId);
		$this->load->model(array('total_player_game_minute', 'game_logs',
			'player_model', 'total_player_game_hour', 'total_player_game_day'));

		$lastRuntimeConfig=$this->game_logs->queryRuntimeConfigFromGameLogs($gamePlatformId);
		list($fromRuntime, $toRuntime)=$this->config->getRuntimeConfig($gamePlatformId, $lastRuntimeConfig);
		$this->utils->debug_log('getRuntimeConfig', $fromRuntime, $toRuntime);
		$dateTimeFromStr=$fromRuntime['minute'];
		$dateTimeToStr=$toRuntime['minute'];
		$isSyncMinute=false;
		if($dateTimeToStr > $dateTimeFromStr){
			$isSyncMinute=true;
		}else{
			$this->utils->debug_log('isSyncMinute is false', $dateTimeToStr, $dateTimeFromStr);
		}
		//compare minute and hour
		$isSyncHour=false;
		$isSyncDay=false;
		$minuteTo=new DateTime($toRuntime['minute']);
		$hourFrom=new DateTime($fromRuntime['hour']);
		if($minuteTo->format('Y-m-d H') > $hourFrom->format('Y-m-d H')){
			//if minute move to next hour
			$isSyncHour=true;
			$isSyncDay=true;
		}
		$runtimeConfig=$fromRuntime;

		$this->utils->debug_log('fromRuntime', $fromRuntime, 'toRuntime', $toRuntime, 'isSyncMinute', $isSyncMinute, 'isSyncHour', $isSyncHour, 'isSyncDay', $isSyncDay);
		$this->utils->debug_log('=========start Sync_totals_with_runtime_config============================', $gamePlatformId, $dateTimeFromStr, $dateTimeToStr, $playerName);

		$dateTimeFrom = new \DateTime($dateTimeFromStr);
		$dateTimeTo = new \DateTime($dateTimeToStr);
		$playerId = null;
		if (!empty($playerName)) {
			$playerId = $this->player_model->getPlayerIdByUsername($playerName);
		}

		$count=0;
		if($isSyncMinute){
			$this->utils->info_log('start sync minute', $dateTimeFrom, $dateTimeTo, $gamePlatformId, $playerId);
			$success=$this->dbtransOnlyWithDeadlockRetry(function() use(&$count, $dateTimeFrom, $dateTimeTo, $playerId, $gamePlatformId){
				$count += $this->total_player_game_minute->sync($dateTimeFrom, $dateTimeTo, $playerId, $gamePlatformId);
				return true;
			});
			$this->utils->debug_log('total_player_game_minute sync count', $count, $success);
			if(!$success){
				$this->utils->error_log('total_player_game_minute sync failed');
				return $success;
			}else{
				//if success, set minute field
				$runtimeConfig['minute']=$dateTimeTo->format('Y-m-d H:i:s');
			}
		}else{
			$this->utils->debug_log('skip isSyncMinute', $isSyncMinute);
		}
		if($isSyncHour){
			$dateTimeFrom = new \DateTime($fromRuntime['hour']);
			$dateTimeTo = new \DateTime($toRuntime['minute']);
			// step 1 hour
			$success=$this->utils->loopDateTimeStartEnd($dateTimeFrom->format('Y-m-d H').':00:00', $dateTimeTo->format('Y-m-d H').':59:59',
				'+1 hour', function($from, $to) use(&$count, $gamePlatformId, $playerId){
				//00:00:00 to 01:00:00 => 00:59:59
				if($to->format('s')=='00'){
					$to->modify('-1 second');
				}
				$this->utils->info_log('start sync hour', $from, $to, $gamePlatformId, $playerId);
				$success=$this->dbtransOnlyWithDeadlockRetry(function() use(&$count, $from, $to, $playerId, $gamePlatformId){
					//minute to hour
					$count += $this->total_player_game_hour->sync($from, $to, $playerId, $gamePlatformId);
					return true;
				});
				$this->utils->debug_log('total_player_game_hour sync count', $count, $success);
				return $success;
			});
			if(!$success){
				$this->utils->error_log('total_player_game_hour sync failed');
				return $success;
			}else{
				//if success, set hour field
				$runtimeConfig['hour']=$dateTimeTo->format('Y-m-d H').':00:00';
			}
		}else{
			$this->utils->debug_log('skip isSyncHour', $isSyncHour);
		}
		if($isSyncDay){
			$dateTimeFrom = new \DateTime($fromRuntime['day']);
			$dateTimeTo = new \DateTime($toRuntime['minute']);
			// step 1 day
			$success=$this->utils->loopDateTimeStartEnd($dateTimeFrom->format('Y-m-d').' 00:00:00', $dateTimeTo->format('Y-m-d').' 23:59:59',
				'+1 day', function($from, $to) use(&$count, $gamePlatformId, $playerId){
				//00:00:00 to 01:00:00 => 00:59:59
				if($to->format('s')=='00'){
					$to->modify('-1 second');
				}
				$this->utils->info_log('start sync day', $from, $to, $gamePlatformId, $playerId);
				$success=$this->dbtransOnlyWithDeadlockRetry(function() use(&$count, $from, $to, $playerId, $gamePlatformId){
					//hour to day
					$count += $this->total_player_game_day->sync($from, $to, $playerId, $gamePlatformId);
					return true;
				});
				$this->utils->debug_log('total_player_game_day sync count', $count, $success);
				return $success;
			});
			if(!$success){
				$this->utils->error_log('total_player_game_day sync failed');
				return $success;
			}else{
				//if success, set hour field
				$runtimeConfig['day']=$dateTimeTo->format('Y-m-d').' 00:00:00';
			}
		}else{
			$this->utils->debug_log('skip isSyncDay', $isSyncDay);
		}
		//write to runtime config
		$this->config->writeRuntimeConfig($runtimeConfig, $gamePlatformId);
		$this->utils->debug_log('=========end Sync_totals_with_runtime_config============================', $dateTimeFrom, $dateTimeTo, $count);
		return true;
	}

}

/// END OF FILE//////////////
