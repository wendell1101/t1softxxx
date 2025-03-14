<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 5.1.6 or newer
 *
 * @package		CodeIgniter
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2008 - 2014, EllisLab, Inc.
 * @license		http://codeigniter.com/user_guide/license.html
 * @link		http://codeigniter.com
 * @since		Version 1.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * Exceptions Class
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @category	Exceptions
 * @author		ExpressionEngine Dev Team
 * @link		http://codeigniter.com/user_guide/libraries/exceptions.html
 */
class CI_Exceptions {
	var $action;
	var $severity;
	var $message;
	var $filename;
	var $line;

	/**
	 * Nesting level of the output buffering mechanism
	 *
	 * @var int
	 * @access public
	 */
	var $ob_level;

	/**
	 * List if available error levels
	 *
	 * @var array
	 * @access public
	 */
	var $levels = array(
		E_ERROR => 'Error',
		E_WARNING => 'Warning',
		E_PARSE => 'Parsing Error',
		E_NOTICE => 'Notice',
		E_CORE_ERROR => 'Core Error',
		E_CORE_WARNING => 'Core Warning',
		E_COMPILE_ERROR => 'Compile Error',
		E_COMPILE_WARNING => 'Compile Warning',
		E_USER_ERROR => 'User Error',
		E_USER_WARNING => 'User Warning',
		E_USER_NOTICE => 'User Notice',
		E_STRICT => 'Runtime Notice',
	);

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->ob_level = ob_get_level();
		// Note:  Do not log messages from this constructor.
	}

	// --------------------------------------------------------------------

	/**
	 * Exception Logger
	 *
	 * This function logs PHP generated error messages
	 *
	 * @access	private
	 * @param	string	the error severity
	 * @param	string	the error string
	 * @param	string	the error filepath
	 * @param	string	the error line number
	 * @return	string
	 */
	function log_exception($severity, $message, $filepath, $line) {
		$severity = (!isset($this->levels[$severity])) ? $severity : $this->levels[$severity];
		// $functions = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
		// $msg = "\nStack:\n";
		// if(!empty($functions)){			
		// 	foreach ($functions as $call) {
		// 		if (empty($call['file']) && !empty($call['class'])) {
		// 			$msg .= $call['class'] . "->" . $call['function'] . "\n";
		// 		} else if (!empty($call['file'])) {
		// 			$funcName = "";
		// 			if (isset($call['function'])) {
		// 				$funcName = '@' . @$call['function'];
		// 			}
		// 			$msg .= str_replace(array('/home/vagrant/Code/og', 'admin/application', 'player/application', 'aff/application', 'agency/application'), array('..', '..', '..', '..', '..'), $call['file']) . ":" . $call['line'] . $funcName . "\n";
		// 		}
		// 	}
		// }

		log_message('error', 'Severity: ' . $severity . '  --> ' . $message . ' ' . $filepath . ' ' . $line, TRUE);
	}

	// --------------------------------------------------------------------

	/**
	 * 404 Page Not Found Handler
	 *
	 * @access	private
	 * @param	string	the page
	 * @param 	bool	log error yes/no
	 * @return	string
	 */
	function show_404($page = '', $log_error = TRUE) {
		$heading = "404 Page Not Found";
		$message = "The page you requested was not found.";

		// By default we log this, but allow a dev to skip it
		// if ($log_error)
		// {
		// 	log_message('error', '404 Page Not Found --> '.$page);
		// }

		echo $this->show_error($heading, $message, 'error_404', 404);
		exit;
	}

	// --------------------------------------------------------------------

	/**
	 * General Error Page
	 *
	 * This function takes an error message as input
	 * (either as a string or an array) and displays
	 * it using the specified template.
	 *
	 * @access	private
	 * @param	string	the heading
	 * @param	string	the message
	 * @param	string	the template name
	 * @param 	int		the status code
	 * @return	string
	 */
	function show_error($heading, $message, $template = 'error_general', $status_code = 500) {

		log_message('error', $message);

		set_status_header($status_code);
		header('X-Request-Id: '._REQUEST_ID);

		$message = '<p>' . implode('</p><p>', (!is_array($message)) ? array($message) : $message) . '</p>';

		if (ob_get_level() > $this->ob_level + 1) {
			ob_end_flush();
		}
		ob_start();
		include APPPATH . 'errors/' . $template . '.php';
		$buffer = ob_get_contents();
		ob_end_clean();
		return $buffer;
	}

	// --------------------------------------------------------------------

	/**
	 * Native PHP error handler
	 *
	 * @access	private
	 * @param	string	the error severity
	 * @param	string	the error string
	 * @param	string	the error filepath
	 * @param	string	the error line number
	 * @return	string
	 */
	function show_php_error($severity, $message, $filepath, $line) {

		$show_php_error=true;
		//ignore soap fault
		if (strpos($message, 'SoapClient') !== FALSE) {
			$show_php_error=false;
			return;
		}

		if(strpos($message, 'Redis::connect') !== false ){
			//ignore
			$show_php_error=false;
			return;
		}

		if(strpos($message, 'Cannot modify header information') !== false ){
			//ignore
			$show_php_error=false;
			return;
		}

		if(config_item('ignore_undefined_php')==true){
			// log_message('info', 'severity:'.$severity.', message:'.$message);
			//ignore undefined php
			if($severity==E_NOTICE && strpos($message, 'Undefined') !== false ){
				$show_php_error=false;
				return;
			}
		}

		// raw_debug_log('ignore_unexpect_warning_php', $message, $severity, E_WARNING);

		if(config_item('ignore_unexpect_warning_php')==true){
			if($severity==E_WARNING && strpos($message, 'expects parameter') !== false ){
				$show_php_error=false;
				return;
			}
		}

		if(config_item('ignore_count_warning')==true){
			if($severity==E_WARNING && strpos($message, 'count()') !== false ){
				$show_php_error=false;
				return;
			}
		}

		if(config_item('ignore_php_warning_reference_variable_on_param')==true){
			if(strpos($message, 'Only variables should be passed by reference') !== false ){
				$show_php_error=false;
				return;
			}
		}

		if(config_item('disabled_all_php_page_error')==true){
			$show_php_error=false;
			return;
		}

		if(config_item('ignore_all_notice_error')==true && $severity==E_NOTICE){
			$show_php_error=false;
			return;
		}

		$severity = (!isset($this->levels[$severity])) ? $severity : $this->levels[$severity];

		$filepath = str_replace("\\", "/", $filepath);

		// For safety reasons we do not show the full file path
		if (FALSE !== strpos($filepath, '/')) {
			$x = explode('/', $filepath);
			$filepath = $x[count($x) - 2] . '/' . end($x);
		}

		if (ob_get_level() > $this->ob_level + 1) {
			ob_end_flush();
		}
		ob_start();
		include APPPATH . 'errors/error_php.php';
		$buffer = ob_get_contents();
		ob_end_clean();
		echo $buffer;
	}

}
// END Exceptions Class

/* End of file Exceptions.php */
/* Location: ./system/core/Exceptions.php */