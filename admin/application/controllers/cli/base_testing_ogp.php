<?php

require_once dirname(__FILE__) . '/base_testing.php';

class BaseTestingOGP extends BaseTesting {

	public function __construct() {
		parent::__construct();
		$this->oghome = realpath(dirname(__FILE__) . "/../../../");
	}

	# overload parent functions
	public function init() {


		if( isset($this->input) ){
			$this->isCliRequest = $this->input->is_cli_request();
		}
		$this->className = join('', array_slice(explode('\\', __CLASS__), -1));
		return true;
	}

	public function index($target = null) {
		set_time_limit(0);
		$this->load->library(['utils','utils4testogp']);
		$this->init();

		//set to testing_debug_log
		$this->config->set_item('app_debug_log', $this->config->item('testing_debug_log'));
		if (!empty($target)) {
			$args = func_get_args();
			array_shift($args);
			$slicedArgs = $args; /// pass params after removed first
			call_user_func_array([$this, $target], $slicedArgs);
		} else {
			$this->testAll();
		}
		$this->load->view('test_result');
	}

	/**
	 * Specify function name to test.
	 * Support the args of $methodName.
	 * The following cli will pass args,"2019-12-16" "2019-12-23" into catchTestSampling() :
	 * <code>
	 * vagrant@default_og_xcyl:~/Code/og$ php admin/public/index.php cli/Testing_ogp15716/testTarget/catchTestSampling "2019-12-16" "2019-12-23"
	 * </code>
	 *
	 * @param array $methodName The method Name
	 * @return mixed The return of $methodName .
	 */
	//should overwrite testAll
	// URI, http://admin.og.local/cli/Testing_ogp00000/index/testAll
	public function testAll() {
		$this->init();
		$classMethods = get_class_methods($this);
		$excludeMethods = array('test', 'testTarget', 'testAll');
		foreach($classMethods as $method){
			if(strpos($method, 'test') !== 0
				|| in_array($method, $excludeMethods)
			) {
				continue;
			}

			// # Clear loaded language before each test call
			// $this->lang->is_loaded = array();
			// $this->lang->language = array();
			if($this->isCliRequest){
				echo sprintf('%s::will start...'.PHP_EOL, $method);
			}
			$this->testAll['results'][$method] = $this->$method();
			// $this->_flash();
			//sleep(1);
		}

		if($this->isCliRequest){
			return $this->testAll['results'];
		}

		// ob_end_flush();
	}





}