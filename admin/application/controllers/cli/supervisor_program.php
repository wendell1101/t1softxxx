<?php
require_once dirname(__FILE__) . "/base_cli.php";

/**
 * Class Supervisor_program
 *
 * General behaviors include :
 *
 * * Restart specific program on supervisor
 *
 * @category Command Line
 * @version 1
 */
class Supervisor_program extends Base_cli {

	const LOG_TITLE = '[supervisor_program_controller]';
	const ACTION_RESTART = "restart";
	const ACTION_START = "start";

	public $oghome = null;

	/**
	 * overview : Supervisor_program constructor.
	 */
	public function __construct() {
		parent::__construct();

		$this->config->set_item('print_log_to_console', true);
		$this->oghome = realpath(dirname(__FILE__) . "/../../../");
		$this->load->model(array('operatorglobalsettings'));
	}

	public function restart($program_name = null, $key = "restart_queue_program"){
		set_time_limit(0);
		$this->utils->debug_log('restart... program:' . $program_name);
		$mark = 'benchRestartProgram';
		while (true) {
			if(!empty($program_name)){
				$value = $this->get_key_value($key);
				if(!empty($value)){
					$this->utils->info_log('restarting......' . $program_name);
					$this->utils->markProfilerStart($mark);
					//create from to
					//run on thread
					$this->run_shell($action = self::ACTION_RESTART, $program_name);
					$this->utils->markProfilerEndAndPrint($mark);
					gc_collect_cycles();
					
					##update key
					$this->delete_key($key);
				} 
			}
			sleep(1);
		}

		$this->utils->debug_log(self::LOG_TITLE, 'stopped');
	}

	public function run_shell($action, $program_name){
		$this->utils->debug_log("try run", $action, $program_name);
		$cmd = 'sudo supervisorctl '. $action. ' '. $program_name. ':';
		$this->utils->info_log('try run', $cmd);
		shell_exec($cmd);

		$this->utils->debug_log("done");
	}

	public function start($program_name = null, $key = "start_queue_program"){
		set_time_limit(0);
		$this->utils->debug_log('start... program:' . $program_name);
		$mark = 'benchStartProgram';
		while (true) {
			if(!empty($program_name)){
				$val = $this->get_key_value($key);
				if($val){
					$this->utils->markProfilerStart($mark);
					//create from to
					//run on thread
					$this->run_shell($action = self::ACTION_START, $program_name);
					$this->utils->markProfilerEndAndPrint($mark);
					gc_collect_cycles();

					#update key
					$this->delete_key($key);
				} 
			}
			sleep(1);
		}

		$this->utils->debug_log(self::LOG_TITLE, 'stopped');
	}

	public function get_key_value($key = "restart_queue_program"){
		$value=$this->utils->readRedis($key);
		$this->utils->info_log('get_key_value... key:' . $key, $value);
		return $value;
	}

	public function delete_key($key){
		$value=$this->utils->deleteKeyRedis($key);
		$this->utils->info_log('delete_key... key:' . $key , $success);
	}
}

/// END OF FILE//////////////
