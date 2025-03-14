<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once __DIR__.'/Brand_router.php';
/**
 * router of db, for brand
 * depends: domain, cookies, env, request ...
 */
class DB_router{
	/**
	 *
	 * @var DB_router
	 */
    private static $instance;
	private $active_target_db;
	private $active_brand;
	private $active_level_brand;
	private $CI;
	private $db_list=[];
	/**
	 * @var Brand_router $_brand_router
	 */
	private $_brand_router;

	private function __construct(){
		$this->CI= & get_instance();
		$this->_brand_router= Brand_router::getSingletonInstance();
		$this->fallback_target_db=config_item('fallback_target_db');
		$config=[];
		foreach(Brand_router::REQUIRED_CONFIG as $itemName){
			$config[$itemName]=config_item($itemName);
		}
		// get first active brand
		$firstBrand=$this->_brand_router->active_brand;
		// reinit brand
		$this->_brand_router->init($config);
		//init var
		$this->copyBrandVar();
		if($this->active_brand!=$firstBrand){
			throw new RuntimeException('some wrong config');
		}
		// $this->initConfig($this->_multiple_level_tree, null);
		// log_message('debug', 'after initConfig', ['fullbrand_currency_list'=>$this->fullbrand_currency_list]);
		$this->init();
		// log_message('debug', $this->getIpCountry('119.9.106.90'));
	}

	public function copyBrandVar(){
		// 'enabled_multiple_level', 'disabled_multiple_database', 'multiple_level_tree', 'fallback_brand',
		// 'multiple_databases_default_setting', 'brand_auto_domain_switcher_group_list', 'brand_domain_to_brand_key'
		$this->disabled_multiple_database=$this->_brand_router->disabled_multiple_database;
		$this->_multiple_level_tree=$this->_brand_router->multiple_level_tree;
		// $this->fallback_brand=$this->_brand_router->fallback_brand;
		$this->multiple_databases_default_setting=$this->_brand_router->multiple_databases_default_setting;
		$this->fullbrand_currency_list=$this->_brand_router->fullbrand_currency_list;
		$this->setActiveBrand($this->_brand_router->active_brand);
		$this->active_level_brand=$this->_brand_router->active_level_brand;
		$this->fullbrand_mainurl=$this->_brand_router->fullbrand_mainurl;
		$this->currency_config_list=$this->_brand_router->currency_config_list;
	}

	/**
	 *
	 * @return \DB_router
	 */
    public static function getSingletonInstance(){
        if(!isset(self::$instance)){
            // Creates sets object to instance
            self::$instance = new DB_router();
        }
        // Returns the instance
        return self::$instance;
    }

	const __OG_TARGET_DB='__OG_TARGET_DB';
	const __OG_BRAND='__OG_BRAND';
	const SUPER_TARGET_DB='super';
	const DEFAULT_BRAND='top';
	const READONLY_DB='readonly';
	const DEFAULT_DB='super';
	private $fallback_target_db=null;
	// private $fallback_brand=null;
	private $_multiple_level_tree=null;
	private $disabled_multiple_database=false;
	private $multiple_databases_default_setting;
	private $fullbrand_mainurl;
	private $currency_config_list;
	// private $fullbrand_list=[];
	/**
	 * 'top'=>[],
	 * 'top-mdbstaging'=>[
	 *		'super'=>,
	 *		'usdt'=>,
	 * 	]
	 *
	 * @var array
	 */
	private $fullbrand_currency_list=[];

	public function getMainUrlByBrand($brand=null){
		if(empty($brand)){
			$brand=$this->active_brand;
		}
		if(array_key_exists($brand, $this->fullbrand_mainurl)){
			return $this->fullbrand_mainurl[$brand];
		}
		return null;
	}

	public function getBrandMainUrlList(){
		return $this->fullbrand_mainurl;
	}

	public function getDBKeyList($brand=null){
		$list=[];
		if(empty($brand)){
			$brand=$this->active_brand;
		}
		if($this->isValidBrand($brand)){
			$list=array_keys($this->fullbrand_currency_list[$brand]);
		}else{
			log_message('error', 'wrong brand '.$brand);
		}

		return $list;
	}

	public function getAvailableCurrencyList($brand=null){
		$list=[];
		if(empty($brand)){
			$brand=$this->active_brand;
		}
		if($this->isValidBrand($brand)){
			$l=array_keys($this->fullbrand_currency_list[$brand]);
			foreach($l as $currencyKey){
				if($currencyKey==self::SUPER_TARGET_DB){
					continue;
				}
				if(array_key_exists($currencyKey, $this->currency_config_list)){
					$list[$currencyKey]=$this->currency_config_list[$currencyKey];
				}else{
					log_message('error', 'lost config of currency');
				}
			}
		}else{
			log_message('error', 'wrong brand '.$brand);
		}

		return $list;
	}

	public function isEnabledMDB(){
		if($this->disabled_multiple_database){
			return false;
		}
		return !empty($this->_multiple_level_tree);
	}

	public function isEnabledBrand(){
		return $this->isEnabledMDB();
	}

	public function isEnabledMultipleLevel(){
		return $this->isEnabledMDB();
	}

	public function isSuperModeOnMDB(){
		// ignore
		return $this->active_target_db==self::SUPER_TARGET_DB;
	}

	public function makeUniqueDBKey($brand, $currency){
		return $brand.'-'.$currency;
	}

	public function getUniqueDBKey(){
		return $this->makeUniqueDBKey($this->active_brand, $this->active_target_db);
	}

	public function getActiveLevelBrand(){
		return $this->active_level_brand;
	}

	public function getActiveBrand(){
		return $this->active_brand;
	}

	private function setActiveBrand($active_brand){
		$this->active_brand=$active_brand;
		$this->rememberActiveBrand();
		return $this->active_brand;
	}

	public function rememberActiveBrand(){
		$this->saveActiveBrandToCookies();
	}

	public function rememberActiveTargetDB(){
		$this->saveActiveTargetDBToCookies();
	}

	public function getActiveTargetDB(){
		return $this->active_target_db;
	}

	private function setActiveTargetDB($active_target_db){
		$this->active_target_db=$active_target_db;
		$this->rememberActiveTargetDB();
		return $this->active_target_db;
	}

	public function discoveryConfigBy($fullbrand=null, $currencyKey=null, $readonly=false){
		if(empty($fullbrand)){
			$fullbrand=$this->active_brand;
		}
		if(empty($currencyKey)){
			$currencyKey=$this->active_target_db;
		}
		$configDB=null;
		// discovery in $this->_multiple_level_tree
		if(array_key_exists($fullbrand, $this->fullbrand_currency_list)){
			if(array_key_exists($currencyKey ,$this->fullbrand_currency_list[$fullbrand])){
				// found
				$config=$this->fullbrand_currency_list[$fullbrand][$currencyKey];
				$configDB= $config['default'];
				// readonly and exists
				if($readonly && array_key_exists('readonly', $config)){
					$configDB= $config['readonly'];
				}
			}
		}
		if(!empty($configDB)){
			// merge multiple_databases_default_setting
			$configDB=array_merge($this->multiple_databases_default_setting, $configDB);
			$configDB['brand']=$fullbrand;
			$configDB['active_group']=$currencyKey;
			$configDB[self::__OG_TARGET_DB]=$currencyKey;
			$configDB[self::__OG_BRAND]=$fullbrand;
		}else{
			log_message('error', 'cannot find db config', ['fullbrand'=>$fullbrand, 'currency'=>$currencyKey, 'readonly'=>$readonly]);
		}
		return $configDB;
	}

	public function checkCurrencyDomain(){
		//get host first
		$host=$this->getHttpHost();
		//check domain settings
		$auto_domain_switcher_group_list=config_item('auto_domain_switcher_group_list');
		if(!empty($auto_domain_switcher_group_list)){
			//check
			foreach ($auto_domain_switcher_group_list as $groupKey => $list) {
				foreach ($list as $domain_target_db => $bindDomain) {
					if($host==$bindDomain){
						if($this->isValidTargetDB($domain_target_db)){
							log_message('debug', 'try get target db from auto_domain_switcher_group_list',
								['domain_target_db'=>$domain_target_db]);
							//found
							return $domain_target_db;
						}
					}
				}
			}
		}
		$domain_to_currency_key=config_item('domain_to_currency_key');
		if(!empty($domain_to_currency_key)){
			if($host!='localhost'){
				$domain_target_db=null;
				if(array_key_exists($host, $domain_to_currency_key)){
					$domain_target_db=$domain_to_currency_key[$host];
				}else{
					// try fnmatch
					foreach($domain_to_currency_key as $domainPat=>$target_db){
						if(fnmatch($domainPat, $host, FNM_CASEFOLD)){
							// matched
							$domain_target_db=$target_db;
							break;
						}
					}
				}
				if(!empty($domain_target_db)){
					if($this->isValidTargetDB($domain_target_db)){
						log_message('debug', 'try get target db from domain',
							['domain_target_db'=>$domain_target_db]);
						return $domain_target_db;
					}
				}
			}
		}
		return null;
	}

	public function init($force_to_target_db=null, $force_to_brand=null){
		$this->discoveryBrand($force_to_brand);
		$this->discoveryCurrency($force_to_target_db);
		log_message('debug', 'after init', ['active_brand'=>$this->active_brand, 'active_target_db'=>$this->active_target_db]);
	}

	public function discoveryCurrency($force_to_target_db=null){
		if(!$this->isEnabledMDB()){
			$active_target_db=self::DEFAULT_DB;
			//no mdb
			log_message('debug', 'disabled mdb');
			return $this->setActiveTargetDB($active_target_db);
		}

		//default db rule
		//set fallback
		$active_target_db=$this->fallback_target_db;
		if(empty($this->fallback_target_db)){
			$active_target_db=self::DEFAULT_DB;
		}
		if(!empty($force_to_target_db) && $this->isValidTargetDB($force_to_target_db)){
			log_message('debug', 'try get target db from force_to_target_db', $force_to_target_db);
			return $this->setActiveTargetDB($force_to_target_db);
		}

		// Param/Env(command line) > SERVER variable > Domain > Input Get (__OG_TARGET_DB) >
		// Cookies (__OG_TARGET_DB) > IP Country
		$env_target_db=getenv(self::__OG_TARGET_DB);
		if($this->isValidTargetDB($env_target_db)){
			log_message('debug', 'try get target db from getenv', $env_target_db);
			return $this->setActiveTargetDB($env_target_db);
		}

		if(array_key_exists(self::__OG_TARGET_DB, $_SERVER) && !empty($_SERVER[self::__OG_TARGET_DB])){
			$server_target_db=$_SERVER[self::__OG_TARGET_DB];
			log_message('debug', 'try get target db from _SERVER', $server_target_db);
			if($this->isValidTargetDB($server_target_db)){
				return $this->setActiveTargetDB($server_target_db);
			}
		}

		//check domain settings
		$domain_target_db=$this->checkCurrencyDomain();
		if(!empty($domain_target_db)){
			return $this->setActiveTargetDB($domain_target_db);
		}

		$input_target_db=$this->CI->input->get(self::__OG_TARGET_DB);
		if(empty($input_target_db)){
			$input_target_db=$this->CI->input->post(self::__OG_TARGET_DB);
		}
		//try get from input get
		if($this->isValidTargetDB($input_target_db)){
			log_message('debug', 'try get target db from input get',
				['input_target_db'=>$input_target_db]);
			return $this->setActiveTargetDB($input_target_db);
		}

		//try cookies
		// $cookie_name=self::__OG_TARGET_DB.'_'.config_item('sub_project');
		$cookie_name=config_item('cookie_for_target_db');
		if(array_key_exists($cookie_name, $_COOKIE) && !empty($_COOKIE[$cookie_name])){
			$cookie_target_db=$_COOKIE[$cookie_name];
			log_message('debug', 'try get target db from cookies: '.$cookie_name,
				['cookie_target_db'=>$cookie_target_db]);
			if($this->isValidTargetDB($cookie_target_db)){
				return $this->setActiveTargetDB($cookie_target_db);
			}
		}
		//ip country to currency key
		$ip_country_to_currency_key=config_item('ip_country_to_currency_key');
		if(!empty($ip_country_to_currency_key)){
			$ip=$this->getIP();
			$ipCountry=$this->getIpCountry($ip);
			log_message('debug', 'ip:'.$ip.', ipCountry:'.$ipCountry);

			if($ipCountry!='Local' && !empty($ipCountry)){
				if(array_key_exists($ipCountry, $ip_country_to_currency_key)){
					$ip_country_target_db=$ip_country_to_currency_key[$ipCountry];
					if($this->isValidTargetDB($ip_country_target_db)){
						log_message('debug', 'try get target db from ip country',
							['ip_country_target_db'=>$ip_country_target_db]);
						return $this->setActiveTargetDB($ip_country_target_db);
					}
				}
			}
		}

		log_message('debug', 'mdb choose default:'.$active_target_db);
		return $this->setActiveTargetDB($active_target_db);
	}

	public function discoveryBrand($force_to_brand=null){
		$active_brand=$this->_brand_router->discoveryBrand($force_to_brand);
		$this->copyBrandVar();
		return $this->setActiveBrand($active_brand);

/*
		if(!$this->isEnabledMDB()){
			$active_brand=self::DEFAULT_BRAND;
			//no mdb
			log_message('debug', 'disabled mdb');
			return $this->setActiveBrand($active_brand);
		}

		//default db rule
		//set fallback
		$active_brand=$this->fallback_brand;
		if(empty($this->fallback_brand)){
			$active_brand=self::DEFAULT_BRAND;
		}
		if(!empty($force_to_brand) && $this->isValidBrand($force_to_brand)){
			log_message('debug', 'try get target db from force_to_target_db and force_to_brand',
				['force_to_brand'=>$force_to_brand]);
			return $this->setActiveBrand($force_to_brand);
		}

		// Param/Env(command line) > SERVER variable > Domain > Input Get (__OG_BRAND) >
		// Cookies (__OG_BRAND) > IP Country
		$env_brand=getenv(self::__OG_BRAND);
		log_message('debug', 'brand from getenv', ['env_brand'=>$env_brand]);
		if($this->isValidBrand($env_brand)){
			log_message('debug', 'try get brand from getenv', ['env_brand'=>$env_brand]);
			return $this->setActiveBrand($env_brand);
		}else{
			log_message('error', 'wrong brand from env', ['env_brand'=>$env_brand]);
		}

		if(array_key_exists(self::__OG_BRAND, $_SERVER) && !empty($_SERVER[self::__OG_BRAND])){
			$server_brand=$_SERVER[self::__OG_BRAND];
			log_message('debug', 'try get brand from _SERVER', $server_brand);
			if($this->isValidBrand($server_brand)){
				return $this->setActiveBrand($server_brand);
			}
		}

		//check domain settings
		$domain_brand=$this->checkBrandDomain();
		if(!empty($domain_brand)){
			return $this->setActiveBrand($domain_brand);
		}

		$input_brand=$this->CI->input->get(self::__OG_BRAND);
		if(empty($input_brand)){
			$input_brand=$this->CI->input->post(self::__OG_BRAND);
		}
		//try get from input get
		if($this->isValidBrand($input_brand)){
			log_message('debug', 'try get brand from input get',
				['input_brand'=>$input_brand]);
			return $this->setActiveBrand($input_brand);
		}

		//try cookies
		// $cookie_name=self::__OG_TARGET_DB.'_'.config_item('sub_project');
		$cookie_name=config_item('cookie_for_brand');
		if(array_key_exists($cookie_name, $_COOKIE) && !empty($_COOKIE[$cookie_name])){
			$cookie_brand=$_COOKIE[$cookie_name];
			log_message('debug', 'try get brand from cookies: '.$cookie_name,
				['cookie_brand'=>$cookie_brand]);
			if($this->isValidBrand($cookie_brand)){
				return $this->setActiveBrand($cookie_brand);
			}
		}
		//ip country to currency key
		$ip_country_to_brand=config_item('ip_country_to_brand');
		if(!empty($ip_country_to_brand)){
			$ip=$this->getIP();
			$ipCountry=$this->getIpCountry($ip);
			log_message('debug', 'ip:'.$ip.', ipCountry:'.$ipCountry);

			if($ipCountry!='Local' && !empty($ipCountry)){
				if(array_key_exists($ipCountry, $ip_country_to_brand)){
					$ip_country_brand=$ip_country_to_brand[$ipCountry];
					if($this->isValidBrand($ip_country_brand)){
						log_message('debug', 'try get brand from ip country',
							['ip_country_brand'=>$ip_country_brand]);
						return $this->setActiveBrand($ip_country_brand);
					}
				}
			}
		}

		log_message('debug', 'brand choose default:'.$active_brand);
		return $this->setActiveBrand($active_brand);
*/
	}

	public function switchCIDatabaseToActiveTargetDB($force=true){
		return $this->switchCIDatabase($this->active_target_db, $force, $this->active_brand);
	}

	/**
	 * switch to target db, and remember, replace ci db
	 * @param  string  $targetDB
	 * @param  boolean $force
	 * @return boolean
	 */
	public function switchCIDatabase($targetDB=null, $force=true, $brand=null){

		if(empty($targetDB)){
			$targetDB=$this->active_target_db;
		}
		if(empty($brand)){
			$brand=$this->active_brand;
		}

		if(!$force && isset($this->CI->db) && is_object($this->CI->db) && $this->CI->db->getOgTargetDB()==$targetDB){
			//still old
			return true;
		}

		$this->init($targetDB, $brand);
		$this->rememberActiveTargetDB();
		$this->rememberActiveBrand();

		$uniqueDBKey=$this->makeUniqueDBKey($brand, $targetDB);
		if(!isset($this->db_list[$uniqueDBKey])){
			$this->db_list[$uniqueDBKey]=&DB(['fullbrand'=>$brand, 'currency'=>$targetDB, 'readonly'=>false]);
		}

		$this->CI->db = null;

		// Load the DB class
		$this->CI->db = $this->db_list[$uniqueDBKey];

		return true;
	}

	/**
	 * connect database
	 *
	 * @param string $currency
	 * @param string $brand
	 * @param boolean $readonly
	 * @return CI_DB_driver
	 */
	public function connectDatabase($currency, $brand=null, $readonly=false){
		if(empty($brand)){
			$brand=$this->active_brand;
		}
		$uniqueDBKey=$this->makeUniqueDBKey($brand, $currency);
		if(!isset($this->db_list[$uniqueDBKey])){
			$this->db_list[$uniqueDBKey]=&DB(['fullbrand'=>$brand, 'currency'=>$currency, 'readonly'=>$readonly]);
		}
		return $this->db_list[$uniqueDBKey];
	}

	public function getDBNameFromTargetDB(){
		$config=$this->discoveryConfigBy();
		return $config['database'];
	}

	public function getDatabaseNameByTargetDB($targetDB, $readonly=false, $brand=null){
		$config=$this->discoveryConfigBy($brand, $targetDB, $readonly);
		return !empty($config) ? $config['database'] : null;
	}

	public function isValidBrand($brand){
		if(empty($brand)){
			return false;
		}
		return array_key_exists($brand, $this->fullbrand_currency_list);
	}

	public function isValidTargetDB($currencyKey){
		if(empty($currencyKey)){
			return false;
		}

		// check currency on active brand
		if(array_key_exists($this->active_brand, $this->fullbrand_currency_list)){
			return array_key_exists($currencyKey, $this->fullbrand_currency_list[$this->active_brand]);
		}
		return false;
	}

	public function getMDBList(){
		if($this->isEnabledMDB()){
			// get currency list of active brand
			return array_keys($this->fullbrand_currency_list[$this->active_brand]);
		}

		return null;
	}

	public function getMDBConfig(){
		// for queue_server
		// get config list of active brand
		$list=[];
		foreach($this->fullbrand_currency_list[$this->active_brand] as $currency=>$config){
			$configDB=array_merge($this->multiple_databases_default_setting, $config);
			$list[$currency]= $configDB;
		}
		return ;
	}

	public function loadReadOnlyDB($db=null){
		if(empty($db)){
			$db=$this->CI->db;
		}

		if ($this->CI->utils->isEnabledReadonlyDB() && $this->isEnabledBrand()) {
			// discoveryConfig
			// $config=$this->discoveryConfigBy($this->active_brand, $this->active_target_db, true);
			return $this->connectDatabase($this->active_target_db, $this->active_brand, true);
		}else{
			return $this->CI->db;
		}
	}

	public function rawConnectDB($readonly, $db=null){
		if(empty($db)){
			$db=$this->CI->db;
		}
		if($readonly){
			$db=$this->loadReadOnlyDB($db);
		}

        $conn=mysqli_connect($db->hostname,
            $db->username,
            $db->password,
            $db->database,
            $db->port);
        $charset=$db->char_set;
        mysqli_set_charset($conn, $charset);
        return $conn;
	}

	private function saveActiveBrandToCookies($expire=0){

		if (!is_numeric($expire)) {
			$expire = time() - 86500;
		} else {
			$expire = ($expire > 0) ? time() + $expire : 0;
		}
		$path='/';
		$domain='';
		$secure=false;
		$httpOnly=config_item('enabled_http_only_on_mdb_cookies');

		// $cookie_name=self::__OG_TARGET_DB.'_'.config_item('sub_project');
		$cookie_name=config_item('cookie_for_brand');
		setcookie($cookie_name, $this->active_brand, $expire, $path, $domain, $secure, $httpOnly);
	}

	private function saveActiveTargetDBToCookies($expire=0){

		if (!is_numeric($expire)) {
			$expire = time() - 86500;
		} else {
			$expire = ($expire > 0) ? time() + $expire : 0;
		}
		$path='/';
		$domain='';
		$secure=false;
		$httpOnly=config_item('enabled_http_only_on_mdb_cookies');

		// $cookie_name=self::__OG_TARGET_DB.'_'.config_item('sub_project');
		$cookie_name=config_item('cookie_for_target_db');

		setcookie($cookie_name, $this->active_target_db, $expire, $path, $domain, $secure, $httpOnly);

	}

	//===helper===========================
	const DEFAULT_IP_HEADERS=['HTTP_X_SS_CLIENT_ADDR', 'HTTP_X_FORWARDED_FOR', 'HTTP_TRUE_CLIENT_IP', 'HTTP_CLIENT_IP', 'HTTP_X_CLIENT_IP',
		'HTTP_X_CLUSTER_CLIENT_IP'];

	private function getHttpHost() {
		$host = null;
		if (isset($_SERVER['HTTP_HOST'])) {
			$host = @$_SERVER['HTTP_HOST'];
		}
		if (empty($host)) {
			$host = 'localhost';
		}
		return $host;
	}

	private function searchNotLocalIP($spoofArr) {
		if (!empty($spoofArr)) {
			foreach ($spoofArr as $ip) {

				//try ignore 192.168 && 10.
				if(is_local_ip($ip)){
					continue;
				}

				return $ip;
			}
			log_message('debug', 'cannot get right ip', ['spoofArr'=>$spoofArr]);
			return $spoofArr[0];
		}
		return FALSE;
	}

	private function get_value_from_server($key){
		return isset($_SERVER[$key]) ? $_SERVER[$key] : null;
	}

	private function getIP(){

		foreach (self::DEFAULT_IP_HEADERS as $header) {
			if (($spoof = $this->get_value_from_server($header)) !== FALSE) {
				// Some proxies typically list the whole chain of IP
				// addresses through which the client has reached us.
				// e.g. client_ip, proxy_ip1, proxy_ip2, etc.
				if (strpos($spoof, ',') !== FALSE) {
					$spoof = explode(',', $spoof);
					$spoof = $this->searchNotLocalIP($spoof);
					// $spoof = $spoof[0];
				}

				if (!$this->valid_ip($spoof)) {
					$spoof = FALSE;
				} else {
					break;
				}
			}
		}
		$remoteAddr = '';
		if (isset($_SERVER['REMOTE_ADDR'])) {
			$remoteAddr = $_SERVER['REMOTE_ADDR'];
		}
		$ip_address = ($spoof !== FALSE) ? $spoof : $remoteAddr;

		if (!$this->valid_ip($ip_address)) {
			if(!$this->is_cli_request()){
				//real invalid ip
				log_message('debug', 'invalid ip:'.$ip_address, ['spoof'=>$spoof, 'remoteAddr'=>$remoteAddr, '_SERVER'=>$_SERVER]);
				$ip_address = '0.0.0.0';
			}else{
				//means local
				$ip_address = '127.0.0.1';
			}
		}

		return $ip_address;

		// return $this->CI->input->ip_address();
	}

	private function is_cli_request() {
		return (php_sapi_name() === 'cli' OR defined('STDIN'));
	}

	private function valid_ip($ip, $which = '') {
		$which = strtolower($which);

		// First check if filter_var is available
		if (is_callable('filter_var')) {
			switch ($which) {
			case 'ipv4':
				$flag = FILTER_FLAG_IPV4;
				break;
			case 'ipv6':
				$flag = FILTER_FLAG_IPV6;
				break;
			default:
				$flag = FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6;
				break;
			}

			return (bool) filter_var($ip, FILTER_VALIDATE_IP, $flag);
		}

		if ($which !== 'ipv6' && $which !== 'ipv4') {
			if (strpos($ip, ':') !== FALSE) {
				$which = 'ipv6';
			} elseif (strpos($ip, '.') !== FALSE) {
				$which = 'ipv4';
			} else {
				return FALSE;
			}
		}

		$func = '_valid_' . $which;
		return $this->$func($ip);
	}


	/**
	 * Validate IPv4 Address
	 *
	 * Updated version suggested by Geert De Deckere
	 *
	 * @access	protected
	 * @param	string
	 * @return	bool
	 */
	private function _valid_ipv4($ip) {
		$ip_segments = explode('.', $ip);

		// Always 4 segments needed
		if (count($ip_segments) !== 4) {
			return FALSE;
		}
		// IP can not start with 0
		if ($ip_segments[0][0] == '0') {
			return FALSE;
		}

		// Check each segment
		foreach ($ip_segments as $segment) {
			// IP segments must be digits and can not be
			// longer than 3 digits or greater then 255
			if ($segment == '' OR preg_match("/[^0-9]/", $segment) OR $segment > 255 OR strlen($segment) > 3) {
				return FALSE;
			}
		}

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Validate IPv6 Address
	 *
	 * @access	protected
	 * @param	string
	 * @return	bool
	 */
	private function _valid_ipv6($str) {
		// 8 groups, separated by :
		// 0-ffff per group
		// one set of consecutive 0 groups can be collapsed to ::

		$groups = 8;
		$collapsed = FALSE;

		$chunks = array_filter(
			preg_split('/(:{1,2})/', $str, NULL, PREG_SPLIT_DELIM_CAPTURE)
		);

		// Rule out easy nonsense
		if (current($chunks) == ':' OR end($chunks) == ':') {
			return FALSE;
		}

		// PHP supports IPv4-mapped IPv6 addresses, so we'll expect those as well
		if (strpos(end($chunks), '.') !== FALSE) {
			$ipv4 = array_pop($chunks);

			if (!$this->_valid_ipv4($ipv4)) {
				return FALSE;
			}

			$groups--;
		}

		while ($seg = array_pop($chunks)) {
			if ($seg[0] == ':') {
				if (--$groups == 0) {
					return FALSE; // too many groups
				}

				if (strlen($seg) > 2) {
					return FALSE; // long separator
				}

				if ($seg == '::') {
					if ($collapsed) {
						return FALSE; // multiple collapsed
					}

					$collapsed = TRUE;
				}
			} elseif (preg_match("/[^0-9a-f]/i", $seg) OR strlen($seg) > 4) {
				return FALSE; // invalid segment
			}
		}

		return $collapsed OR $groups == 1;
	}

	private function getIpCountry($ip) {
		if (empty($ip) || is_local_ip($ip)) {
			return 'Local';
		}

		$city = '';
		$country = '';
		$cityDB = config_item('ip_county_db_path');
		try {
			$reader = new \GeoIp2\Database\Reader($cityDB);
			$record = $reader->country($ip);

			// $city = $record->city->name;
			$country = $record->country->name;
			if (empty($city)) {
				$city = $country;
				if (empty($city)) {
					$city = '';
				}
			}
			if (empty($country)) {
				$country = '';
			}

			$reader->close();
		} catch (Exception $e) {
			log_message('error', $e->getTraceAsString());
		}
		return $country;
	}

	public function getHostIdFromDB($targetDB=null, $brand=null){
		$hostid=null;
		if($this->isEnabledMDB()){
			if(empty($targetDB)){
				$targetDB=$this->getActiveTargetDB();
			}
			// og_mdb_staging_super or og_mdb_super
			$dbname=$this->getDatabaseNameByTargetDB($targetDB, false, $brand);
			$arr=explode('_', $dbname);
			if(strpos($dbname, 'staging')===false){
				// live , og_mdb_super to mdb-og
				if(count($arr)>=2){
					$hostid=$arr[1].'-'.$arr[0];
				}
			}else{
				// staging, og_mdb_staging_super to mdbstaging-og
				if(count($arr)>=3){
					$hostid=$arr[1].$arr[2].'-'.$arr[0];
				}
			}
		}

		if(empty($hostid)){
			// from hostname, eg. mdbstaging-og-64776d655f-fss82 to mdbstaging-og
			$hostname=gethostname();
			$arr=explode('-', $hostname);
			if(count($arr)>=2){
				$hostid=$arr[0].'-'.$arr[1];
			}else{
				$hostid=$hostname;
			}
		}
		return $hostid;
	}

	public function getBrandList(){
		return array_keys($this->fullbrand_currency_list);
	}

	// router of upload path
	/**
	 * route upload path
	 *
	 * @return string
	 */
	public function routeUploadPath(){
		$hostid=$this->getHostIdFromDB();
		// convert db name to pod id, og_mdb_staging_super to mdbstaging-og
		// Code/og/writable/<full-brand>/pub/<pod id>/upload
		$path=APPPATH.'/../../writable/'.$this->getActiveBrand().'/pub/'.$hostid.'/upload';
		if(!file_exists($path)){
			@mkdir($path, 0777, true);
			@chmod($path, 0777);
		}
		return $path;
	}

    public function routeSharingUploadPath() {
		// Code/og/writable/<full-brand>/pub/sharing_upload
		$path=APPPATH.'/../../writable/'.$this->getActiveBrand().'/pub/sharing_upload';
		if(!file_exists($path)){
			@mkdir($path, 0777, true);
			@chmod($path, 0777);
		}
		return $path;
	}

	public function routeSharingPrivatePath() {
		// Code/og/writable/<full-brand>/pub/sharing_private
		$path=APPPATH.'/../../writable/'.$this->getActiveBrand().'/pub/sharing_private';
		if(!file_exists($path)){
			@mkdir($path, 0777, true);
			@chmod($path, 0777);
		}
		return $path;
	}

	public function routeCombinePath(){
		$hostid=$this->getHostIdFromDB();
		// Code/og/writable/<full-brand>/pub/<pod id>/player_pub
		$path=APPPATH.'/../../writable/'.$this->getActiveBrand().'/pub/'.$hostid.'/player_pub';
		if(!file_exists($path)){
			@mkdir($path, 0777, true);
			@chmod($path, 0777);
		}
		return $path;
	}

	public function routePlayerInternalPath(){
		$hostid=$this->getHostIdFromDB();
		// Code/og/writable/<full-brand>/pub/<pod id>/player_pub
		$path=APPPATH.'/../../writable/'.$this->getActiveBrand().'/pub/'.$hostid.'/player/internal';
		return $path;
	}

	const BRAND_URL=[
		'/upload',
		'/banner',
		'/reports',
		'/player/upload',
		'/player/internal',
		'/resources/player/built_in',
		'/resources/hedge_in_ag',
		'/resources/images/cms_game_types',
		'/resources/images/cms_game_platforms',
		'/resources/images/account',
		'/resources/images/banner',
		'/resources/images/depositslip',
		'/resources/images/promothumbnails',
		'/resources/images/shopping_banner',
		'/resources/images/tutorial',
		'/resources/images/vip_cover',
		'/resources/images/promo_cms',
		'/resources/images/uploaded_logo',
		'/resources/images/themes',
		'/resources/images/static_sites',
	];

	public function isBrandUrl($url){
		// TODO validate url, check prefix
		return true;
	}

	public function getBrandPrefixUrl($brand=null){
		if(empty($brand)){
			$brand=$this->active_brand;
		}

		return '/brand/'.$brand;
	}

	public function routeBrandUrl($url,  $brand=null){
		// insert <full brand>
		if(empty($brand)){
			$brand=$this->active_brand;
		}
		if(!$this->CI->utils->startsWith($url, '/')){
			// insert /
			$url='/'.$url;
		}
		// /upload to /brand/<full brand>/upload
		// /player/upload to /brand/<full brand>/player/upload
		if($this->isBrandUrl($url)){
			return $this->getBrandPrefixUrl($brand).$url;
		}
		return $url;
	}

}
