<?php
require_once dirname(__FILE__) . '/../modules/lock_app_module.php';

/**
 * Undocumented class
 * 
 * @property CI_Benchmark $benchmark
 * @property CI_Unit_test $unit
 * @property Mock_php_stream $mock_php_stream
 */
class Unit_test_runner extends CI_Controller {
	use lock_app_module;

	public function __construct()
	{
		parent::__construct();

		$this->load->vars('unit_test_memory_start', [
			'memory_get_usage' => memory_get_usage(),
			'memory_get_peak_usage' => memory_get_peak_usage(),
		]);

		//command only except debug mode
		if (!$this->input->is_cli_request() && !$this->utils->isDebugMode()) {
			show_error('Not allowed', 405);
			exit;
		}

		$this->load->library('unit_test');
		$this->load->file(APPPATH . 'libraries/unit_test_runner_lib.php');
		$this->load->library('mock_php_stream');

		$this->mock_php_stream->register();

		ini_set('xdebug.var_display_max_children', '-1');
		ini_set('xdebug.var_display_max_data', '-1');
		ini_set('xdebug.var_display_max_depth', '-1');
	}

	public function _remap($method)
	{
		global $CI, $URI;

		try {
			$this->benchmark->mark('unit_test_start');

			call_user_func_array(array(&$CI, $method), array_slice($URI->rsegments, 2));

			$this->benchmark->mark('unit_test_end');
		} catch (\Exception $e) {
		} catch (\Throwable $th) {
		}

		$this->_unit_test_report();
	}

	protected function _unit_test_report()
	{
		$results = $this->unit->result();
		echo PHP_EOL . str_repeat('=', 80) . PHP_EOL;
		foreach($results as $entry) {
			echo sprintf("[%-6s][%s] %s\n", strtoupper($entry['Result']), $entry['Test Name'], $entry['Notes']);
		}
		echo str_repeat('=', 80) . PHP_EOL;
		$this->_post_unit_test_report();
	}

	protected function _post_unit_test_report()
	{

		$unit_test_memory_start = $this->load->get_var('unit_test_memory_start');

		$unit_test_memory_start_memory_usage = round($unit_test_memory_start['memory_get_usage'] / 1024 / 1024, 2, PHP_ROUND_HALF_UP);
		$unit_test_memory_start_memory_peak_usage = round($unit_test_memory_start['memory_get_peak_usage'] / 1024 / 1024, 2, PHP_ROUND_HALF_UP);

		$memory_usage = round(memory_get_usage() / 1024 / 1024, 2, PHP_ROUND_HALF_UP);
		$memory_peak_usage = round(memory_get_peak_usage() / 1024 / 1024, 2, PHP_ROUND_HALF_UP);

		$benchmark_elapsed_time = $this->benchmark->elapsed_time('unit_test_start', 'unit_test_end');

		echo <<<REPORT
Memory Usage: {$unit_test_memory_start_memory_usage} => {$memory_usage}
Peak Memory Usage: {$unit_test_memory_start_memory_peak_usage} => {$memory_peak_usage}
Run time: {$benchmark_elapsed_time}

REPORT;
	}

	public function payment($test_runner = null, $testTarget = null)
	{
		$this->load->helper('date');
		$this->load->model('wallet_model');

		if(!file_exists(APPPATH . '../../submodules/payment-lib/tests/' . strtolower($test_runner) . '.php')) {
			show_error('Not found test runner: [' . $test_runner . ']', 404);
			exit;
		}
		
		$this->load->file(APPPATH . '../../submodules/payment-lib/tests/' . strtolower($test_runner) . '.php');

		$class = ucfirst(strtolower($test_runner));
		
		/** @var Unit_test_runner_lib $instance */
		$instance = new $class();
		$instance->init();

		if(method_exists($instance, $testTarget)) {
			$instance->$testTarget();
		} else {
			$testCases = $instance->getTestCases();
			echo 'Test target:' . PHP_EOL;
			foreach($testCases as $method) {
				echo $method . PHP_EOL;
			}
		}
	}
}