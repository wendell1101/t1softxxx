<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

/**
 * get config before config init
 * don't use any CI func or class
 * keep brand info
 * finish brand db config
 *
 * CANNOT USE $this->CI or $this->utils or config_item or log_message
 */
class Brand_router{

	/**
	 *
	 * @var Brand_router
	 */
	private static $instance;
    public $multiple_level_tree;
	public $disabled_multiple_database;
	public $enabled_multiple_level;
	public $multiple_databases_default_setting;
	public $currency_config_list;
	public $cookie_for_brand;
	public $fallback_brand;
	// get from config
	public $brand_domain_to_brand_key=[];
	// full brand => [currency=>]
	// full brand is like top-mdbstaging-sub1
	public $fullbrand_currency_list;
	// active brand is full brand
	public $active_brand;
	// if full brand is top-mdbstaging, level brand is mdbstaging
	public $active_level_brand;
	public $fullbrand_mainurl=[];

	const __OG_BRAND='__OG_BRAND';
	// const __OG_TARGET_DB='super';
	const SUPER_TARGET_DB='super';
	const DEFAULT_BRAND='top';

	private function __construct(){
	}

	/**
	 *
	 * @return Brand_router
	 */
    public static function getSingletonInstance(){
        if(!isset(self::$instance)){
            // Creates sets object to instance
            self::$instance = new Brand_router();
        }
        // Returns the instance
        return self::$instance;
    }

	const REQUIRED_CONFIG=['enabled_multiple_level', 'disabled_multiple_database', 'multiple_level_tree', 'fallback_brand',
		'multiple_databases_default_setting', 'cookie_for_brand', 'currency_config_list'];

	/**
	 * initConfig and discovery brand
	 *
	 * @param array $config
	 * @return string
	 */
    public function init($config){
		$this->assignConfig($config);
		$this->initConfig($this->multiple_level_tree, null);
		raw_debug_log('after initConfig', ['fullbrand_currency_list'=>$this->fullbrand_currency_list]);
        return $this->discoveryBrand();
    }

	private function assignConfig($config){
		foreach(self::REQUIRED_CONFIG as $itemName){
			if(array_key_exists($itemName, $config)){
				$this->$itemName=$config[$itemName];
			}else{
				throw new RuntimeException('lost config item '.$itemName);
			}
		}
	}

	public function initConfig($parentNode, $parentBrandKey){
		if(array_key_exists('brand', $parentNode) && !empty($parentNode['brand'])){
			// sub brand
			$brand=$parentNode['brand'];
			foreach($brand as $brandKey=>$brandInfo){
				$fullbrandKey=$brandKey;
				if(!empty($parentBrandKey)){
					$fullbrandKey=$parentBrandKey.'-'.$brandKey;
				}
				// add current brand key
				$this->fullbrand_currency_list[$fullbrandKey]=[];
				// init brand
				$this->initConfig($brandInfo, $fullbrandKey);
			}
		}
		// domain config
		if(!empty($parentBrandKey) && array_key_exists('domain', $parentNode) && !empty($parentNode['domain'])){
			$domainList=$parentNode['domain'];
			foreach($domainList as $domainName){
				$this->brand_domain_to_brand_key[$domainName]=$parentBrandKey;
			}
		}
		// no currency on 0 level
		if(!empty($parentBrandKey) && array_key_exists('currency', $parentNode) && !empty($parentNode['currency'])){
			$currencyList=$parentNode['currency'];
			// this brand includes currency
			$this->fullbrand_currency_list[$parentBrandKey]=$currencyList;
			$keys=array_keys($currencyList);
			foreach($keys as $key){
				if($key==self::SUPER_TARGET_DB){
					continue;
				}
				if(!array_key_exists($key, $this->currency_config_list)){
					throw new RuntimeException('lost currency config '.$key);
				}
			}
		}
		// main_url config
		if(!empty($parentBrandKey) && array_key_exists('main_url', $parentNode) && !empty($parentNode['main_url'])){
			$this->fullbrand_mainurl[$parentBrandKey]=$parentNode['main_url'];
		}
	}

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

	public function checkBrandDomain(){
		//get host first
		$host=$this->getHttpHost();
		//check domain settings
		$brand_domain_to_brand_key=$this->brand_domain_to_brand_key;
		if(!empty($brand_domain_to_brand_key)){
			if($host!='localhost'){
				$domain_brand=null;
				if(array_key_exists($host, $brand_domain_to_brand_key)){
					$domain_brand=$brand_domain_to_brand_key[$host];
				}else{
					// try fnmatch
					foreach($brand_domain_to_brand_key as $domainPat=>$brand){
						if(fnmatch($domainPat, $host, FNM_CASEFOLD)){
							// matched
							$domain_brand=$brand;
							break;
						}
					}
				}
				if(!empty($domain_brand)){
					if($this->isValidBrand($domain_brand)){
						raw_debug_log('try get brand from brand_domain_to_brand_key',
							['domain_brand'=>$domain_brand]);
						return $domain_brand;
					}
				}
			}
		}
		return null;
	}

	public function isEnabledMDB(){
		if($this->disabled_multiple_database){
			return false;
		}
		return $this->enabled_multiple_level && !empty($this->multiple_level_tree);
	}

	private function setActiveBrand($active_brand){
		$this->active_brand=$active_brand;
		$this->extractToLevelBrand();
		return $this->active_brand;
	}

	private function extractToLevelBrand(){
		$brand=$this->active_brand;
		$arr=explode('-', $brand);
		// get last one
		$this->active_level_brand=$arr[count($arr)-1];
		return $this->active_level_brand;
	}

	public function getActiveLevelBrand(){
		return $this->active_level_brand;
	}

	/**
	 * discovery brand
	 * Param/Env(command line) > SERVER variable > Domain > Input Get (__OG_BRAND) > Cookies (__OG_BRAND)
	 *
	 * @param string $force_to_brand
	 * @return string
	 */
	public function discoveryBrand($force_to_brand=null){
		if(!$this->isEnabledMDB()){
			$active_brand=self::DEFAULT_BRAND;
			//no mdb
			raw_debug_log('disabled mdb');
			return $this->setActiveBrand($active_brand);
		}

		//default db rule
		//set fallback
		$active_brand=$this->fallback_brand;
		if(empty($this->fallback_brand)){
			$active_brand=self::DEFAULT_BRAND;
		}
		if(!empty($force_to_brand) && $this->isValidBrand($force_to_brand)){
			raw_debug_log('try get target db from force_to_target_db and force_to_brand',
				['force_to_brand'=>$force_to_brand]);
			return $this->setActiveBrand($force_to_brand);
		}

		// Param/Env(command line) > SERVER variable > Domain > Input Get (__OG_BRAND) >
		// Cookies (__OG_BRAND) > IP Country
		$env_brand=getenv(self::__OG_BRAND);
		raw_debug_log('brand from getenv', ['env_brand'=>$env_brand]);
		if($this->isValidBrand($env_brand)){
			raw_debug_log('try get brand from getenv', ['env_brand'=>$env_brand]);
			return $this->setActiveBrand($env_brand);
		}else{
			raw_error_log('wrong brand from env', ['env_brand'=>$env_brand]);
		}

		if(array_key_exists(self::__OG_BRAND, $_SERVER) && !empty($_SERVER[self::__OG_BRAND])){
			$server_brand=$_SERVER[self::__OG_BRAND];
			raw_debug_log('try get brand from _SERVER', $server_brand);
			if($this->isValidBrand($server_brand)){
				return $this->setActiveBrand($server_brand);
			}
		}

		//check domain settings
		$domain_brand=$this->checkBrandDomain();
		if(!empty($domain_brand)){
			return $this->setActiveBrand($domain_brand);
		}

		$input_brand=array_key_exists(self::__OG_BRAND, $_REQUEST) ? $_REQUEST[self::__OG_BRAND] : null;
		if(empty($input_brand)){
			$input_brand=array_key_exists(self::__OG_BRAND, $_POST) ? $_POST[self::__OG_BRAND] : null;
		}
		//try get from input get
		if($this->isValidBrand($input_brand)){
			raw_debug_log('try get brand from input get',
				['input_brand'=>$input_brand]);
			return $this->setActiveBrand($input_brand);
		}

		//try cookies
		// $cookie_name=self::__OG_TARGET_DB.'_'.config_item('sub_project');
		$cookie_name=$this->cookie_for_brand;
		if(array_key_exists($cookie_name, $_COOKIE) && !empty($_COOKIE[$cookie_name])){
			$cookie_brand=$_COOKIE[$cookie_name];
			raw_debug_log('try get brand from cookies: '.$cookie_name,
				['cookie_brand'=>$cookie_brand]);
			if($this->isValidBrand($cookie_brand)){
				return $this->setActiveBrand($cookie_brand);
			}
		}

		raw_debug_log('brand choose default:'.$active_brand);
		return $active_brand;
	}

	public function isValidBrand($brand){
		if(empty($brand)){
			return false;
		}
		return array_key_exists($brand, $this->fullbrand_currency_list);
	}

}
