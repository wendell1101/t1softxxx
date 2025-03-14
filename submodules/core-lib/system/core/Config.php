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
 * CodeIgniter Config Class
 *
 * This class contains functions that enable config files to be managed
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @category	Libraries
 * @author		ExpressionEngine Dev Team
 * @link		http://codeigniter.com/user_guide/libraries/config.html
 */
class CI_Config {

	/**
	 * List of all loaded config values
	 *
	 * @var array
	 */
	var $config = array();
	/**
	 * List of all loaded config files
	 *
	 * @var array
	 */
	var $is_loaded = array();
	/**
	 * List of paths to search when trying to load a config file
	 *
	 * @var array
	 */
	var $_config_paths = array(APPPATH);

	/**
	 * Constructor
	 *
	 * Sets the $config data from the primary config.php file as a class variable
	 *
	 * @access   public
	 * @param   string	the config file name
	 * @param   boolean  if configuration values should be loaded into their own section
	 * @param   boolean  true if errors should just return false, false if an error message should be displayed
	 * @return  boolean  if the file was successfully loaded or not
	 */
	function __construct() {
		$this->config = &get_config();
		// log_message('debug', "Config Class Initialized");

		$hide_host_on_url = $this->item('hide_host_on_url');
		// log_message('debug', '===================base_url==============' . $base_url . ' hide_host_on_url:' . $hide_host_on_url);

		// Set the base_url automatically if none was provided
		if ($this->config['base_url'] == '') {
			if ($hide_host_on_url) {
				$base_url = '/';
				$this->set_item('base_url', $base_url);
			} else {
				if (isset($_SERVER['HTTP_HOST'])) {
					$is_https=$this->config['always_https'] || (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off');
					$base_url = $is_https ? 'https' : 'http';
					$base_url .= '://' . $_SERVER['HTTP_HOST'];
					$base_url .= str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']);
				} else {
					$base_url = 'http://localhost/';
				}

				$this->set_item('base_url', $base_url);
			}
		}

		if(!empty($this->config['current_php_timezone'])){

			date_default_timezone_set($this->config['current_php_timezone']);

		}
	}

	// --------------------------------------------------------------------

	/**
	 * Load Config File
	 *
	 * @access	public
	 * @param	string	the config file name
	 * @param   boolean  if configuration values should be loaded into their own section
	 * @param   boolean  true if errors should just return false, false if an error message should be displayed
	 * @return	boolean	if the file was loaded correctly
	 */
	function load($file = '', $use_sections = FALSE, $fail_gracefully = FALSE) {
		$file = ($file == '') ? 'config' : str_replace('.php', '', $file);
		$found = FALSE;
		$loaded = FALSE;

		$check_locations = defined('ENVIRONMENT')
		? array(ENVIRONMENT . '/' . $file, $file)
		: array($file);

		foreach ($this->_config_paths as $path) {
			foreach ($check_locations as $location) {
				$file_path = $path . 'config/' . $location . '.php';

				if (in_array($file_path, $this->is_loaded, TRUE)) {
					$loaded = TRUE;
					continue 2;
				}

				if (file_exists($file_path)) {
					$found = TRUE;
					break;
				}
			}

			if ($found === FALSE) {
				continue;
			}

			include $file_path;

			if (!isset($config) OR !is_array($config)) {
				if ($fail_gracefully === TRUE) {
					return FALSE;
				}
				show_error('Your ' . $file_path . ' file does not appear to contain a valid configuration array.');
			}

			if ($use_sections === TRUE) {
				if (isset($this->config[$file])) {
					$this->config[$file] = array_merge($this->config[$file], $config);
				} else {
					$this->config[$file] = $config;
				}
			} else {
				$this->config = array_merge($this->config, $config);
			}

			$this->is_loaded[] = $file_path;
			unset($config);

			$loaded = TRUE;
			// log_message('debug', 'Config file loaded: ' . $file_path);
			break;
		}

		if ($loaded === FALSE) {
			if ($fail_gracefully === TRUE) {
				return FALSE;
			}
			show_error('The configuration file ' . $file . '.php does not exist.');
		}

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Fetch a config file item
	 *
	 *
	 * @access	public
	 * @param	string	the config item name
	 * @param	string	the index name
	 * @param	bool
	 * @return	string
	 */
	function item($item, $index = '') {
		if ($index == '') {
			if (!isset($this->config[$item])) {
				return FALSE;
			}

			$pref = $this->config[$item];
		} else {
			if (!isset($this->config[$index])) {
				return FALSE;
			}

			if (!isset($this->config[$index][$item])) {
				return FALSE;
			}

			$pref = $this->config[$index][$item];
		}

		return $pref;
	}

	// --------------------------------------------------------------------

	/**
	 * Fetch a config file item - adds slash after item (if item is not empty)
	 *
	 * @access	public
	 * @param	string	the config item name
	 * @param	bool
	 * @return	string
	 */
	function slash_item($item) {
		if (!isset($this->config[$item])) {
			return FALSE;
		}
		if (trim($this->config[$item]) == '') {
			return '';
		}

		return rtrim($this->config[$item], '/') . '/';
	}

	// --------------------------------------------------------------------

	/**
	 * Site URL
	 * Returns base_url . index_page [. uri_string]
	 *
	 * @access	public
	 * @param	string	the URI string
	 * @return	string
	 */
	function site_url($uri = '') {
		// log_message('debug', '===================site_url==============');

		if (substr($uri, 0, 8)=='https://' || substr($uri, 0, 7)=='http://') {
			return $uri;
		}

		$base_url = $this->slash_item('base_url');

		// $hide_host_on_url = $this->item('hide_host_on_url');
		// log_message('debug', '===================base_url==============' . $base_url . ' hide_host_on_url:' . $hide_host_on_url);

		// if (empty($base_url)) {
		// 	//get host
		// 	$host = @$_SERVER['HTTP_HOST'];
		// 	if (!empty($host)) {
		// 		//use host
		// 	}
		// }

		if ($uri == '') {
			return $base_url . $this->item('index_page');
		}

		if ($this->item('enable_query_strings') == FALSE) {
			$suffix = ($this->item('url_suffix') == FALSE) ? '' : $this->item('url_suffix');
			return $base_url . $this->slash_item('index_page') . $this->_uri_string($uri) . $suffix;
		} else {
			return $base_url . $this->item('index_page') . '?' . $this->_uri_string($uri);
		}
	}

	// -------------------------------------------------------------

	/**
	 * Base URL
	 * Returns base_url [. uri_string]
	 *
	 * @access public
	 * @param string $uri
	 * @return string
	 */
	function base_url($uri = '') {
		return $this->slash_item('base_url') . ltrim($this->_uri_string($uri), '/');
	}

	// -------------------------------------------------------------

	/**
	 * Build URI string for use in Config::site_url() and Config::base_url()
	 *
	 * @access protected
	 * @param  $uri
	 * @return string
	 */
	protected function _uri_string($uri) {
		if ($this->item('enable_query_strings') == FALSE) {
			if (is_array($uri)) {
				$uri = implode('/', $uri);
			}
			$uri = trim($uri, '/');
		} else {
			if (is_array($uri)) {
				$i = 0;
				$str = '';
				foreach ($uri as $key => $val) {
					$prefix = ($i == 0) ? '' : '&';
					$str .= $prefix . $key . '=' . $val;
					$i++;
				}
				$uri = $str;
			}
		}
		return $uri;
	}

	// --------------------------------------------------------------------

	/**
	 * System URL
	 *
	 * @access	public
	 * @return	string
	 */
	function system_url() {
		$x = explode("/", preg_replace("|/*(.+?)/*$|", "\\1", BASEPATH));
		return $this->slash_item('base_url') . end($x) . '/';
	}

	// --------------------------------------------------------------------

	/**
	 * Set a config file item
	 *
	 * @access	public
	 * @param	string	the config item key
	 * @param	string	the config item value
	 * @return	void
	 */
	function set_item($item, $value) {
		$this->config[$item] = $value;
	}

	// --------------------------------------------------------------------

	/**
	 * Assign to Config
	 *
	 * This function is called by the front controller (CodeIgniter.php)
	 * after the Config class is instantiated.  It permits config items
	 * to be assigned or overriden by variables contained in the index.php file
	 *
	 * @access	private
	 * @param	array
	 * @return	void
	 */
	function _assign_to_config($items = array()) {
		if (is_array($items)) {
			foreach ($items as $key => $val) {
				$this->set_item($key, $val);
			}
		}
	}

	//===runtime config file================================================
	private $defaultRuntimeItemName='runtime_file_config';
	/**
	 * getRuntimeFile
	 * @param  string $uniqueId
	 * @param  string $defaultRuntimeFilename
	 * @return
	 */
	public function getRuntimeFile($uniqueId, $defaultRuntimeFilename='/tmp/local_dev_runtime_config.json'){
		$filename=$defaultRuntimeFilename;
		$configItem=$this->item($this->defaultRuntimeItemName);
		if(!empty($configItem) && array_key_exists($uniqueId, $configItem)){
			$filename=$configItem[$uniqueId];
		}
		log_message('debug', 'use runtime file', ['filename'=>$filename, 'uniqueId'=>$uniqueId]);
		return $filename;
	}

	/**
	 * getRuntimeConfig
	 * @param  string $uniqueId
	 * @param  callable $callbackRuntime
	 * @param  string $maxLastConfigTime
	 * @return
	 */
	public function getRuntimeConfig($uniqueId, $lastRuntimeConfig, $maxLastConfigTime='+1 hour'){
		$fromRuntimeConfig=null;
		$filename=$this->getRuntimeFile($uniqueId);
		if(file_exists($filename)){
			//load from file first
			$fromRuntimeConfig=json_decode(file_get_contents($filename), true);
		}
		if(empty($lastRuntimeConfig)){
			$lastRuntimeConfig=$fromRuntimeConfig;
		}
		if(empty($fromRuntimeConfig)){
			$fromRuntimeConfig=$lastRuntimeConfig;
			//-1s when write to runtime file
			$minDate=new DateTime($lastRuntimeConfig['minute']);
			$minDate->modify('-1 minute');
			$fromRuntimeConfig['minute']=$minDate->format('Y-m-d H:i:s');
			log_message('debug', 'init runtime config', ['fromRuntimeConfig'=>$fromRuntimeConfig, 'lastRuntimeConfig'=>$lastRuntimeConfig]);
			//write it to file
			$this->writeRuntimeConfig($fromRuntimeConfig, $uniqueId);
		}else{
			//compare with last runtime info
			if($lastRuntimeConfig['minute'] <= $fromRuntimeConfig['minute']){
				$fromRuntimeConfig=$lastRuntimeConfig;
			}
		}
		if(!empty($maxLastConfigTime)){
			//check max
			$from=new DateTime($fromRuntimeConfig['minute']);
			$to=new DateTime($lastRuntimeConfig['minute']);
			$from->modify($maxLastConfigTime);
			if($to>$from){
				//if >1 hour, reset it
				$lastRuntimeConfig=$this->makeRuntimeConfig($from);
			}
		}
		return [$fromRuntimeConfig, $lastRuntimeConfig];
	}
	/**
	 * writeRuntimeConfig
	 * @param  array $runtimeConfig
	 * @param  string $uniqueId
	 */
	public function writeRuntimeConfig($runtimeConfig, $uniqueId){
		$filename=$this->getRuntimeFile($uniqueId);
		$d=new \DateTime();
		$runtimeConfig['updated_at']=$d->format('Y-m-d H:i:s');
		$success=file_put_contents($filename, json_encode($runtimeConfig))!==false;
		return $success;
	}
	/**
	 * makeRuntimeConfig
	 * @param  DateTime $d
	 * @return array
	 */
	public function makeRuntimeConfig($d){
		return [
			'minute'=>$d->format('Y-m-d H:i').':00',
			'hour'=>$d->format('Y-m-d H').':00:00',
			'day'=>$d->format('Y-m-d').' 00:00:00',
		];
	}

	/**
	 * generateRuntimeConfig
	 * @param  string $uniqueId
	 * @param  DateTime $dateTime
	 */
	public function generateRuntimeConfig($uniqueId, $dateTime){
		if(empty($uniqueId)){
			log_message('error', 'uniqueId is empty');
			return false;
		}
		if(empty($dateTime)){
			log_message('error', 'date time is empty');
			return false;
		}
		$runtimeConfig=$this->makeRuntimeConfig($dateTime);
		$this->writeRuntimeConfig($runtimeConfig, $uniqueId);
		$filename=$this->getRuntimeFile($uniqueId);
		log_message('info', 'write to file', ['filename'=>$filename, 'runtimeConfig'=>$runtimeConfig, 'uniqueId'=>$uniqueId]);

		return true;
	}
	//===runtime config file================================================

}

// END CI_Config class

/* End of file Config.php */
/* Location: ./system/core/Config.php */