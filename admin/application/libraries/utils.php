<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

/**
 * @property \BaseController $CI
 * @property static $utils
 */
class Utils {

	private $_app_prefix;
	private $_multiple_databases;

	function __construct() {
		$this->CI = &get_instance();
		//default load model
		$this->CI->load->model(['player_model']);

        $this->_app_prefix=try_get_prefix();

        $this->debug_log('load app prefix: '.$this->_app_prefix);

		$this->utils = $this;
	}

	private $climate;
	private $_redis = null;

	private $last_locked_key = [];
	private $last_global_locked_key = null;

	const FIRST_TIME = '00:00:00';
	const LAST_TIME = '23:59:59';

	const LOCK_ACTION_BALANCE = 'balance';
	const LOCK_ACTION_AFF_BALANCE = 'aff_balance';
	//const LOCK_ACTION_WITHDRAW = 'withdraw';
	const LOCK_ACTION_AFFILIATE_SETTINGS = 'affiliate_settings';
	const LOCK_ACTION_AGENCY_BALANCE = 'agency_balance';
	const LOCK_ACTION_PLAYER_PROMO = 'playerpromo';
	const LOCK_ACTION_PLAYER_QUEST = 'playerquest';
	const LOCK_ACTION_WITHDRAW_CONDITION = 'withdraw_condition';
	const LOCK_ACTION_AGENCY_STATUS = 'agency_status';
	const LOCK_ACTION_MANUALLY_PAY_CASHBACK = 'manually_pay_cashback';
	const LOCK_ACTION_CASHBACK_REQUEST = 'cashback_request';
	const LOCK_ACTION_DEPOSIT_LOCK = 'deposit_lock';
	const LOCK_ACTION_WITHDRAW_LOCK = 'withdraw_lock';
	const LOCK_ACTION_GENERATE_SIMPLE_PLAYER_GAME_REPORT = 'generate_simple_player_game_report';
	const LOCK_ACTION_SYSTEM_FEATURE = 'system_feature';
	const LOCK_ACTION_GAME_LIST_SYNCING = 'game_list_syncing';
	const LOCK_ACTION_MANUALLY_ADJUST_POINTS_BALANCE = 'manually_adjust_points_balance';
	const LOCK_ACTION_SEAMLESS_WALLET_TRANSACTION_PROCESS = 'seamless_wallet_transaction_process';
	const LOCK_ACTION_MANUALLY_ADJUST_PLAYER_SCORE = 'manually_adjust_player_score';
	const LOCK_ACTION_AUTOMATICALLY_ADJUST_PLAYER_SCORE = 'automatically_adjust_player_score';
	const LOCK_ACTION_CLEAR_COOLDOWN_EXPIRED = 'clear_cooldown_expired';
	const LOCK_ACTION_REDEMPTION_CODE = 'redemption_code';
	const LOCK_ACTION_APPLY_ROULETTE = 'apply_roulette';
	const LOCK_ACTION_GENERATE_ROULETTE = 'generate_roulette';
	const LOCK_ACTION_STATIC_REDEMPTION_CODE = 'static_redemption_code';
	const LOCK_ACTION_UPDATE_TOURNAMENT_PLAYER_SCORE = 'update_tournament_player_score';
    const LOCK_SESSION_FILE = 'sess_file';
    const LOCK_SESSION_FILE_READ = 'sess_file_read';
    const LOCK_SESSION_FILE_WRITE = 'sess_file_write';

	const LOCAL_LOCK_ACTION_PLAYER_LOGIN = 'player_login';
	const LOCK_ACTION_POINTS = 'points_balance';

	const GLOBAL_LOCK_ACTION_REGISTRATION = 'REGISTRATION';
	const GLOBAL_LOCK_ACTION_VIP_CASHBACK = 'vip_cashback';

	const GLOBAL_LOCK_ACTION_PLAYER_REGISTRATION = 'player_registration';
	const GLOBAL_LOCK_ACTION_USER_REGISTRATION = 'user_registration';
	const GLOBAL_LOCK_ACTION_AFFILIATE_REGISTRATION = 'affiliate_registration';
	const GLOBAL_LOCK_ACTION_AGENCY_REGISTRATION = 'agency_registration';
	const GLOBAL_LOCK_ACTION_ROLE_REGISTRATION = 'role_registration';
	const GLOBAL_LOCK_ACTION_REGISTRATION_SETTINGS = 'registration_settings';
	const GLOBAL_LOCK_ACTION_SYSTEM_FEATURE='global_system_feature';
	const LOCK_ACTION_BATCH_ADD_BONUS = 'batch_add_bonus';
    const GLOBAL_LOCK_ACTION_VIP_GROUP='global_vip_group';
    const GLOBAL_LOCK_ACTION_PLAYER_LEVEL = 'player_level';
    const GLOBAL_LOCK_ACTION_VIPSETTING_ID = 'vipsetting_id';



	const CHINESE_LANG_INT = '2';
	const INDONESIAN_LANG_INT = '3';
	const VIETNAMESE_LANG_INT = '4';

	CONST PLAYER_CENTER_LANGUAGE_OPERATORSETTING_NAME = 'player_center_language';

	const FIRST_CHILD_INDEX = 0;

	const VERWALLETXFER_NOT_TO_AGENCY       	= 0x111;
	const VERWALLETXFER_INVALID_AMOUNT          = 0x112;
	const VERWALLETXFER_GAME_NOT_ACTIVE         = 0x113;
	const VERWALLETXFER_GAME_MAINTENANCE		= 0x114;
	const VERWALLETXFER_PLAYER_XFER_DISABLED	= 0x115;
	const VERWALLETXFER_TEST_PLAYERS_ONLY       = 0x116;
	const VERWALLETXFER_WAGERING_LIMITS_ACTIVE	= 0x117;

	const XFERWALLET_PLAYER_ID_EMPTY			= 0x121;
	const XFERWALLET_XFER_CONDS_ACTIVE			= 0x122;
	const XFERWALLET_PREXFER_FAILED				= 0x123;
	const XFERWALLET_PLAYER_BLOCKED_IN_GAME		= 0x124;
	const XFERWALLET_SUCCESS_AMOUNT_LE_ZERO		= 0x125;
	const XFERWALLET_GENERAL_FAILURE			= 0x126;

	const CHKTGTBAL_WITHDRAW_COND_NOT_FINISHED	= 0x131;
	const CHKTGTBAL_BALANCE_NOT_ENOUGH			= 0x132;

	const RESULT_CASE_THE_PLAYER_ALREADY_IN_THE_LEVEL = 0x140; // 320
	const RESULT_CASE_THE_ERROR_IN_TRANS = 0x141; // 321
	const RESULT_CASE_DONE_IN_TRANS = 0x142; // 322
	const RESULT_CASE_TARGET_LEVEL_NOT_EXIST = 0x143; // 323
	const RESULT_CASE_THE_PLAYER_NOT_IN_ANY_LEVEL = 0x145; // 325
    const RESULT_CASE_THE_PLAYER_NOT_EXISTS = 0x146; // 326

	public $default_upload_sub_dir_for_logo = 'uploaded_logo/';
	public $default_logo_dir_url = '/upload';

    public function getDatetimeTimezone(\DateTime $d) {
        if ($d) {
            return $d->format('T');
        }
        return null;
    }

    public function getTimezoneOffset(\DateTime $d) {
        if ($d) {
        	if($this->getConfig('current_php_timezone') == 'UTC'){
        		return '0';
        	}
            return $d->format('Z')/3600;
        }
        return null;
    }

    public function formatDatetimeForDisplay(\DateTime $d, $formatDatetime = 'Y/m/d H:i:s') {
        if ($d) {
            return $d->format($formatDatetime);
        }
        return null;
    }

	public function formatDateTimeForMysql(\DateTime $d) {
		if ($d) {
			return $d->format('Y-m-d H:i:s');
		}
		return null;
	}

     /**
     * Date and Time for Mysql format with no space and special character
     *
     * @param datetime $d
     *
     * @return mixed
     */
    public function formatDateTimeNoSpaceForMysql(\DateTime $d)
    {
        if($d){
           return $d->format('YmdHis');
        }
        return null;
     }

	public function formatYearForMysql(\DateTime $d) {
		if ($d) {
			return $d->format('Y');
		}
		return null;
	}

	public function formatHourForMysql(\DateTime $d) {
		if ($d) {
			return $d->format('H:i:s');
		}
		return null;
	}

	public function formatHourOnlyForMysql(\DateTime $d) {
		if ($d) {
			return $d->format('H');
		}
		return null;
	}

	public function formatYearMonthForMysql(\DateTime $d) {
		if ($d) {
			return $d->format('Ym');
		}
		return null;
	}

	public function formatDateForMysql(\DateTime $d) {
		if ($d) {
			return $d->format('Y-m-d');
		}
		return null;
	}

	public function formatDateHourForMysql(\DateTime $d) {
		if ($d) {
			return $d->format('YmdH');
		}
		return null;
	}

	public function formatDateMinuteForMysql(\DateTime $d) {
		if ($d) {
			return $d->format('YmdHi');
		}
		return null;
	}

	public function getTheDayBeforeYesterdayForMysql() {
		$d = new \DateTime();
		$d->modify('-2 day');
		return $this->formatDateForMysql($d);
	}

	public function getYesterdayForMysql() {
		$d = new \DateTime();
		$d->modify('-1 day');
		return $this->formatDateForMysql($d);
	}

    public function getLast24HoursForMysql() {
        $d = new \DateTime();
        $d->modify('-24 hours');
        return $this->formatDateTimeForMysql($d);
    }
    
	public function getMinusDaysForMysql($noOfDays, $format = 'Y-m-d H:i') {
		$d = new \DateTime();
		$d->modify('-' . $noOfDays . ' day');
		return $d->format($format);
	}

	public function getCurrentDatetime() {
		$d = new \DateTime();
		return $d->format('Y-m-d H:i');
	}

	public function getCurrentDatetimeWithSeconds($format) {
		$d = new \DateTime();
		$result = array();
		$prefixArr = ['Y/m/d H:i:s', 'Y-m-d H:i:s', 'Y/m/d', 'Y-m-d', 'H:i:s'];

		if(!in_array($format, $prefixArr)) {
			return lang("Date not supported format.");
		}

		foreach ($prefixArr as $p) {
			$result[$p] = $d->format($p);
		}

		return $result[$format];
	}

	public function getTimeAgo($date) {
		if (empty($date)) {
			return "No date provided";
		}

		$periods = array("second", "minute", "hour", "day", "week", "month", "year", "decade");

		$lengths = array("60", "60", "24", "7", "4.35", "12", "10");

		$now = time();

		$unix_date = strtotime($date);

		// check validity of date

		if (empty($unix_date)) {
			return "Bad date";
		}

		// is it future date or past date

		if ($now > $unix_date) {
			$difference = $now - $unix_date;
			$tense = "ago";
		} else {
			$difference = $unix_date - $now;
			$tense = "from now";
		}

		for ($j = 0; $difference >= $lengths[$j] && $j < count($lengths) - 1; $j++) {
			$difference /= $lengths[$j];
		}

		$difference = round($difference);

		if ($difference != 1) {
			$periods[$j] .= "s";
		}

		return "$difference $periods[$j] {$tense}";

	}

    /**
	 * Check if date parameter is empty and format the date return
	 *
	 * @param string $str
	 * @param string| $dateFormat The method that will return the format of date
	 */
	public function checkDateWithMethodFormat($str,$dateFormat='formatDateTimeForMysql')
	{
		$result = null;
		if(! empty($str)){
			try{
				$d = new \DateTime($str);
				$result = $this->$dateFormat($d);
			}catch(Exception $e){
				$result = null;
			}
		}
		return $result;
	}

    /**
	 * Check if from and to date is greater than the given number of days
	 *
	 * @param string $fromDate
	 * @param string $toDate
	 * @param int $numDays
	 *
	 * @return mixed
	 */
	public function isDatesBeyondGap($fromDate,$toDate,$numDays=31)
	{
		$result = null;

		if(!empty($fromDate) && !empty($toDate)){
			$dfrom = new \DateTime($fromDate);
			$dto = new \Datetime($toDate);
			$days = ($numDays > 31 || $numDays < 1) ? 31 : $numDays;
			$dfrom->modify('+'.$days.'days');
			$maxToDate = $dfrom->format('Y-m-d H:i:s');
			$tdd = $dto->format('Y-m-d H:i:s');

			if($tdd > $maxToDate){
				$result = true;
			}else{
				$result = false;
			}
		}

		return $result;
	}

	public function getNowForMysql() {
		return $this->formatDateTimeForMysql(new \DateTime);
	}

	public function getTodayForMysql() {
		return $this->formatDateForMysql(new \DateTime);
	}

	public function getTimeForMysql($time) {
		return $this->formatDateForMysql($time);
	}

	public function getThisYearMonthForMysql() {
		return $this->formatYearMonthForMysql(new \DateTime);
	}

	public function getThisYearForMysql() {
		return $this->formatYearForMysql(new \DateTime);
	}

	public function getThisHourForMysql() {
		return $this->formatHourForMysql(new \DateTime);
	}

	public function getHourOnlyForMysql() {
		return $this->formatHourOnlyForMysql(new \DateTime);
	}

	public function get7DaysAgoForMysql() {
		return $this->formatDateForMysql(new \DateTime('-7 days'));
	}

	public function getNowAdd($seconds) {
		$d = new \DateTime();
		$d = $d->add(new \DateInterval('PT' . $seconds . 'S'));
		return $this->formatDateTimeForMysql($d);
	}

	public function getNowSub($seconds) {
		$d = new \DateTime();
		$d = $d->sub(new \DateInterval('PT' . $seconds . 'S'));
		return $this->formatDateTimeForMysql($d);
	}

	public function getTimestampNow() {
		return time();
	}

	public function getDatetimeNow() {
		// $d = new \DateTime();
		return date('YmdHis');
	}

	public function getDatetimeDiffByDays($start_datetime, $interval_rule){
        $target_datetime = clone $start_datetime;
        $target_datetime->add(new DateInterval($interval_rule));

        $interval = $start_datetime->diff($target_datetime);
        return $interval->format('%R%a');
    }

    public function getFirstDateOfCurrentMonth(){
        return date('Y-m-01');
    }

    public function getLastDateOfCurrentMonth() {
        return date('Y-m-t');
    }

    public function isFirstDateOfCurrentMonth() {
        return date('Y-m-d') == $this->getFirstDateOfCurrentMonth();
    }

    public function isLastDateOfCurrentMonth() {
        return date('Y-m-d') == $this->getLastDateOfCurrentMonth();
    }

    public function generateRandomDateTime($start_date_str, $end_date_str) {
    	$min = strtotime($start_date_str);
    	$max = strtotime($end_date_str);
    	$val = rand($min, $max);
    	return  date('Y-m-d H:i:s', $val);
    }

	public function saveToResponseFile($systemTypeId, $content) {
		$dateDir = "/" . date('Y-m-d');
		$dir = $dateDir . "/" . $systemTypeId . "/";
		//create dir
		if (!file_exists(RESPONSE_RESULT_PATH . $dir)) {
			mkdir(RESPONSE_RESULT_PATH . $dir, 0777, true);
			//chmod
			@chmod(RESPONSE_RESULT_PATH . $dateDir, 0777);
			@chmod(RESPONSE_RESULT_PATH . $dir, 0777);
		}
		$filename = $this->getDatetimeNow() . "_" . random_string('alnum', 8) . ".txt";
		$f = RESPONSE_RESULT_PATH . $dir . $filename;
		file_put_contents($f, $content);
		return $dir . $filename;
	}

	public function saveErrorToResponseFile($systemTypeId, $id, $result) {
		$dateDir = "/" . date('Y-m-d');
		$dir = $dateDir . "/" . $systemTypeId . "/";
		//create dir
		if (!file_exists(RESPONSE_RESULT_PATH . $dir)) {
			mkdir(RESPONSE_RESULT_PATH . $dir, 0777, true);
			//chmod
			@chmod(RESPONSE_RESULT_PATH . $dateDir, 0777);
			@chmod(RESPONSE_RESULT_PATH . $dir, 0777);
		}
		$filename = 'error-' . $this->getDatetimeNow() . "-" . $id . ".json";
		$f = RESPONSE_RESULT_PATH . $dir . $filename;
		file_put_contents($f, json_encode($result));
		return $dir . $filename;
	}

	public function isEmptyInArray($key, $arr) {
		return !$this->notEmptyInArray($key, $arr);
	}

	public function notEmptyInArray($key, $arr) {
		return !empty($arr) && !empty($key) && array_key_exists($key, $arr) && !empty($arr[$key]);
	}

	public function notEmptyValuesInArray($keys, $arr) {
		$result = true;
		foreach ($keys as $key) {
			if (!$this->notEmptyInArray($key, $arr)) {
				$result = false;
				break;
			}
		}

		return $result;
	}

	public function cloneArrayWithForeach($arr, callable $skipCondiCB = null, callable $handCurrCB = null, &$new_arr = []){

		if( empty($skipCondiCB) ){
			$skipCondiCB = function ( $_curr, $arr ){
				return true; // for skip this round
			};
		}
		if( empty($handCurrCB) ){
			// handle after detect skip this round condition
			$handCurrCB = function ( $_curr, $_key, &$new_arr, $arr ){
				$new_arr[$_key] = $_curr;
			};
		}

		foreach($arr as $_key => $_curr){
			$do_skip = $skipCondiCB( $_curr, $arr );
			if ( $do_skip ) {
				continue;
			}
			$handCurrCB($_curr, $_key, $new_arr, $arr );
		}
		return $new_arr;
	}

	public function isPaymentSystem($type) {
		return $type == SYSTEM_PAYMENT;
	}

	public function isGameApiSystem($type) {
		return $type == SYSTEM_GAME_API;
	}

	public function loadAnyGameApiObject($params = null) {
		$gameSystemList = $this->getAllCurrentGameSystemList();

		if($gameSystemList){
			return $this->loadExternalSystemLibObject($gameSystemList[0], $params);

		}else{
			return null;
		}
	}

	public function loadExternalSystemLib($systemId, $params = null) {
		$this->CI->load->model('external_system');

		$sys = $this->CI->external_system->getSystemById($systemId);
		if ($sys) {
			$clsName = $sys->class_name;
			if(!empty($sys->class_key)){
				$enabled_cache_dynamic_class=$this->getConfig('enabled_cache_dynamic_class');
				if($sys->system_type==External_system::SYSTEM_GAME_API){
					$obj=$this->CI->external_system->loadDynamicClassAsGameAPI($sys->class_key,
						$enabled_cache_dynamic_class);
					$this->CI->$clsName=$obj;
					return array(true, $clsName);
				}else if($sys->system_type==External_system::SYSTEM_PAYMENT){
					$obj=$this->CI->external_system->loadDynamicClassAsPaymentAPI($sys->class_key,
						$enabled_cache_dynamic_class);
					$this->CI->$clsName=$obj;
					return array(true, $clsName);
				}
				return array(false, null);
			}else{

				if(empty($sys)){
					$this->error_log('load class failed', $systemId);
					return [false, null];
				}

				if (empty($sys->local_path) || empty($sys->class_name)) {
					$this->debug_log('failed', $systemId, $sys);
					throw new Exception('load system lib failed, ' . $systemId);
				}

				# if not exists just return dummy API
				$classExists = file_exists(strtolower(APPPATH.'libraries/'.$sys->local_path . '/' . ucfirst($sys->class_name).".php"));
				if (!$classExists) {
					$this->error_log('class file not exist, systemId:', $systemId, 'sys info: ', $sys);
					return array(false, null);
				}

				$this->CI->load->library(strtolower($sys->local_path . '/' . $sys->class_name), array("platform_code" => $systemId, "params" => $params));
				return array(true, $sys->class_name);
			}
		}

		return array(false, null);
	}

	public function loadExternalSystemLibObject($systemId, $params = null) {
		list($success, $clsName) = $this->loadExternalSystemLib($systemId, $params);
		if ($success) {
			return $this->CI->$clsName;
		} else {
			return null;
		}
	}

	/**
	 *
	 *
	 * @return Game_platform_manager manager instance
	 */
	public function loadGameManager($params = null) {
		$managerName = "game_platform_manager";
		$this->CI->load->library('game_platform/' . $managerName, $params);
		return $this->CI->$managerName;
	}

	/**
	 * convert ip to city name
	 *
	 * @param string ip ip address
	 *
	 * @return string city name, if don't find it or get error, return empty string
	 */
	public function getIpCity($ip) {
		if ($this->guessLocalIp($ip)) {
			return 'Local';
		}

		// require_once dirname(__FILE__) . '/geoip2.phar';
		$city = '';
		$cityDB = $this->CI->config->item('ip_city_db_path');
		try {
			$reader = new \GeoIp2\Database\Reader($cityDB);
			$record = $reader->city($ip);
			// log_message('debug', $record->country->isoCode); // 'US'
			// log_message('debug', $record->country->name); // 'United States'
			// log_message('debug', $record->country->names['zh-CN']); // '美国'

			// log_message('debug', $record->mostSpecificSubdivision->name); // 'Minnesota'
			// log_message('debug', $record->mostSpecificSubdivision->isoCode); // 'MN'

			// log_message('debug', 'city:' . $record->city->name); // 'Minneapolis'

			// log_message('debug', $record->postal->code); // '55455'

			// log_message('debug', $record->location->latitude); // 44.9733
			// log_message('debug', $record->location->longitude); // -93.2323

			$city = $record->city->name;
			$country = $record->country->name;
			if (empty($city)) {
				$city = $country;
				if (empty($city)) {
					$city = '';
				}
			}

			$reader->close();
		} catch (Exception $e) {
			if (substr($ip, 0, 4) == '172.') {
				return 'Local';
			} else {
				log_message('error', $e->getTraceAsString());
			}
		}
		return $city;
	}

	/**
	 * Wrapper method for old and new ip-location lookup methods (OGP-2199)
	 * 	Uses new lookup for China IPs, and old lookup for everywhere instead
	 *
	 * @param	string	$ip				IP address string
	 * @param	bool	$use_old_only	retro mode, just like using old getIpCityAndCountry().  Default: false.
	 * @return	array  	ordered array [ (city), (country) ]
	 *
	 * @see		getIpCityAndCountry_old()	Old lookup method, uses GeoIP2, renamed from getIpCityAndCountry()
	 * @see		getChinaCityCountryIp()		New lookup method, uses zhuzhichao/ip-location-zh (See github)
	 */
	public function getIpCityAndCountry($ip, $use_old_only = false) {
		if(empty($ip)){
			return ['', ''];
		}

		// Run old query first
		$query = $this->getIpCityAndCountry_old($ip);

		// Run new query if country is China
		$country = $query[1];
		//if it's china or it's empty, try second
		if ((strtolower($country) == 'china' || empty($country)) && !$use_old_only) {
			$old_query = $query;
			$query = $this->getChinaCityCountryIp($ip);

			// If new query returns nothing, use results from old query instead
			$city = $query[0];
			if (empty($city)) {
				$query = $old_query;
			}
			if(!empty($country)){
				//keep $country
				$query[1]=$country;
			}
		}

		return $query;
	}

	/**
	 * IP-location lookup method using GeoIP2 database.  Set protected to prevent accidental access.
	 * 	NOTE: please use getIpCityAndCountry(), it combines this one and getChinaCityCountryIp().
	 *
	 * @param	string	$ip		IP address string
	 * @return	array  	ordered array [ (city), (country) ]
	 */
	protected function getIpCityAndCountry_old($ip) {
		if ($this->guessLocalIp($ip)) {
			return array('Local', 'Local');
		}

		// require_once dirname(__FILE__) . '/geoip2.phar';
		$city = '';
		$country = '';
		$cityDB = $this->CI->config->item('ip_city_db_path');
		try {
			$reader = new \GeoIp2\Database\Reader($cityDB);
			$record = $reader->city($ip);

			$city = $record->city->name;
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
			if (substr($ip, 0, 4) == '172.') {
				return ['Local', 'Local'];
			} else {
				log_message('error', $e->getTraceAsString());
			}
		}
		return array($city, $country);
	}

	public function getChinaCityIp($ip) {
		if ($this->guessLocalIp($ip)) {
			return array(lang('Local'), lang('Local'));
		}

		list($city, $country) = $this->getChinaCityCountryIp($ip);
		return $city;
	}

	/**
	 * get city from ip , only for china
	 * @param  string $ip
	 * @return array [$city, $country]
	 */
	public function getChinaCityCountryIp($ip) {
		if ($this->guessLocalIp($ip)) {
			return array(lang('Local'), lang('Local'));
		}

		if(!$this->getConfig('use_new_china_ip_lib')){
			return $this->getChinaCityCountryIpOld($ip);
		}

		$lang='CN';

		if(empty($this->cityObj)){
			$this->cityObj = new ipip\db\City(dirname(__FILE__).'/third_party/ipipfree.ipdb');
		}

		$city = '';
		$country = '';
		try{
			list($country, $province, $city)=$this->cityObj->find($ip, $lang);
			$this->info_log('ip city', $country, $city, $province);
		} catch (Exception $e) {
			$this->error_log($e);
		}
		return [$city, $country];
	}

	/**
	 * IP-location lookup method using zhuzhichao/ip-location-zh.
	 *
	 * @param	string	$ip		IP address string
	 * @return	array  	ordered array [ (city), (country) ]
	 */
	public function getChinaCityCountryIpOld($ip) {

		if ($this->guessLocalIp($ip)) {
			return array(lang('Local'), lang('Local'));
		}

		$city = '';
		$country = '';
		try {
			$info = \Zhuzhichao\IpLocationZh\Ip::find($ip);
			// $info = Ip::find($ip);
			// $info = \Zhuzhichao\IpLocationZh\Ip::find($ip);
			if (count($info) > 0) {
				$country = $info[0];
			}

			if (count($info) > 1) {
				$city = $info[1];
			}

			if (count($info) > 2) {
				//overwrite by detail
				$city = $info[2];
			}

		} catch (Exception $e) {
			$this->error_log($e);
		}
		return array($city, $country);
	}

	public function guessLocalIp($ip) {
		return substr($ip, 0, 8) == '192.168.' || substr($ip, 0, 6) == '127.0.' || $ip == '0.0.0.0';
	}

	public function getGeoplugin($ip) {
		list($city, $country) = $this->getIpCityAndCountry($ip);
		return array(
			'geoplugin_city' => $city,
			'geoplugin_countryName' => $country,
		);
	}

	public function getCountry($ip) {
		list($city, $country) = $this->getIpCityAndCountry($ip);
		return $country;
	}

	public function writePaymentErrorLog($msg) {
		$args = array($msg);

		$functions = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
		$msg = $this->buildDebugMessage($args, $functions, 'PAYMENT_ERROR', true);

		$this->writeServiceErrorLog('payment_error_log', $msg);

		if ($this->getConfig('log_server_enabled')) {
			$this->CI->load->library(array('lib_queue'));
			$callerType = Queue_result::CALLER_TYPE_SYSTEM;
			$caller = 'payment_error_log';
			$token = $this->CI->lib_queue->addLogJob(\Psr\Log\LogLevel::ERROR, $msg, $this->getCallHost(),
				$callerType, $caller, null);
		}

		// $this->debug_log($msg);
	}

	public function writeQueueErrorLog($msg) {
		$args = array($msg);

		$functions = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
		$msg = $this->buildDebugMessage($args, $functions, 'QUEUE_ERROR', true);

		$this->writeServiceErrorLog('queue_error_log', $msg);
	}

	public function writeServiceErrorLog($logName, $msg) {
		$logFile = $this->CI->config->item($logName);
		if (!empty($logFile)) {
			// log_message('error', 'logFile:' . $logFile);
			//only for payment error
			if (!@error_log('[' . date('Y-m-d H:i:s') . '] ' . $msg . "\n", 3, $logFile)) {
				log_message('error', 'write failed:' . $logFile . ', got error: ' . $msg);
			}
		} else {
			log_message('error', $logName . ' is wrong ' . $logFile . ', got error: ' . $msg);
		}
	}

	public function isDebugMode() {
		return $this->CI->config->item('log_threshold') > 1;
	}

	public function getNowDateTime() {
		return new \DateTime;
	}

	public function getTodayDateTimeRange() {
		$from = new \DateTime();
		$from->setTime(0, 0, 0);

		$to = new \DateTime();
		$to->setTime(23, 59, 59);

		return array($from, $to);
	}

	public function getTodayStringRange() {
		list($from, $to) = $this->getTodayDateTimeRange();

		return array($this->formatDateTimeForMysql($from), $this->formatDateTimeForMysql($to));
	}

	public function getPaymentUrl($prefix, $systemId, $amount, $playerId = null, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null, $orderId = null) {
		$url = $prefix . '/redirect/payment/' . $systemId . '/' . $amount . '/' . $playerId . '/' .
			($playerPromoId ? $playerPromoId : '0') . '/' . ($enabledSecondUrl ? 'true' : 'false') . '/' .
			($bankId ? $bankId : '0');
		if (!empty($orderId)) {
			$url .= '/' . $orderId;
		}
		return $url;
	}

	public function loadComposerLib() {
		// require_once APPPATH . "/libraries/vendor/autoload.php";
	}

	public function getDefaultCurrency() {
		//from currencies
		$this->CI->load->model(['currencies']);
		return $this->CI->currencies->getActiveCurrencyCode();
		// return $this->getConfig('default_currency');
	}

	public function useSiteUrlForResource() {
		return $this->getConfig('use_site_url_for_resource');
	}

	public function jsUrl($uri) {
		return $this->processAnyUrl($uri, '/resources/js');
	}

	public function playerResUrl($uri) {
		return $this->processAnyUrl($uri, '/resources/player');
	}

	public function cssUrl($uri) {
		return $this->processAnyUrl($uri, '/resources/css');
	}

    public function thirdpartyUrl($uri) {
        return $this->processAnyUrl($uri, '/resources/third_party');
    }

    public function imageUrl($uri) {
        return $this->processAnyUrl($uri, '/resources/images');
    }

	public function appendVersionToUri($uri) {
		if (!empty($uri)) {

			if (substr($uri, strlen($uri) - 1, 1) == '?') {
				//don't append
			} else {
				$uri .= strpos($uri, '?') !== FALSE ? '&' : '?';
			}
			$uri .= 'v=' . PRODUCTION_VERSION;
		}

		return $uri;
	}

	/**
	 * Appends a GET variable/value pair to existing relative URL
	 * @param	string	$url
	 * @param	string	$var
	 * @param	string	$value
	 * @return	string	The URL with the GET pair appended
	 */
	public function appendGetVarToRelativeUrl($url, $var, $value) {
		$useg0 = explode('#', $url);
		$uanchor = isset($useg0[1]) ? "#{$useg0[1]}" : null;

		$useg1 = explode('?', $useg0[0]);
		$ubase = $useg1[0];
		$uquery = isset($useg1[1]) ? $useg1[1] : null;

		$var_pair = "{$var}={$value}";
		if (empty($uquery)) {
			$uquery = $var_pair;
		}
		else {
			$uquery .= "&{$var_pair}";
		}

		$ufull = "{$ubase}?{$uquery}{$uanchor}";

		return $ufull;
	}

	/**
	 * for admin/player
	 * @param  string $uri
	 * @param  string $prefix
	 * @return string
	 */
	public function processAnyUrl($uri, $prefix) {
		$uri = $this->appendVersionToUri($uri);

		if ($this->isFullUrl($uri)) {
			return $uri;
		} else {
			if (substr($uri, 0, 1) == '/') {
				$uri = substr($uri, 1);
			}
			$uri = $prefix . '/' . $uri;
			if ($this->useSiteUrlForResource()) {
				return site_url($uri);
			} else {
				return $uri;
			}
		}
	}

	public function isFullUrl($uri) {
		$result = false;
		if (!empty($uri)) {
			$result = preg_match('/^https?:\/\//', $uri);
		}

		return $result;
	}

	public function convertTimezone($dateTimeStr, $fromTimezone, $toTimezone) {
		$d = new \DateTime($dateTimeStr, new \DateTimeZone($fromTimezone));
		$d->setTimezone(new \DateTimeZone($toTimezone));
		return $this->formatDateTimeForMysql($d);
	}

	public function safeGetArray($arr, $key, $defaultVal = null, $callback = null) {
		if ($arr && $key) {
			if (array_key_exists($key, $arr)) {
				$val = $arr[$key];
				if (is_null($val)) {
					return $defaultVal;
				} else {
					if ($callback) {
						return call_user_func_array(array($this, $callback), array($val));
					} else {
						return $val;
					}

				}
			}
		}
		return $defaultVal;
	}

	public function nocache() {
		// $this->CI->output->set_header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
		$this->CI->output->set_header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
		$this->CI->output->set_header('Cache-Control: post-check=0, pre-check=0', FALSE);
		$this->CI->output->set_header('Pragma: no-cache');
		$this->CI->output->set_header('X-Request-Id: '.$this->getRequestId());
	}

	/**
	 * print last_query into debug_log file.
	 *
	 * @param CI_DB_driver $db
	 * @return string $last_query The last query sql of $db.
	 */
	public function printLastSQL($db=null) {
		if(empty($db)){
			$db=$this->CI->db;
		}
		$last_query = $db->last_query();
		$this->debug_log('-----------------printLastSQL-----------------', $last_query, $db->getOgTargetDB());
		return $last_query;
	}// EOF printLastSQL

	public function getPaymentAccountAllFlagsKV() {
		$payment_account_types = $this->getPaymentAccountsAll(); // $this->CI->config->item("payment_account_types");
		foreach ($payment_account_types as $key => $value) {
			$payment_account_types[$key] = lang($value['lang_key']);
		}
		return $payment_account_types;
	}

	public function getPaymentAccountSecondCategoryAllFlagsKV() {
		$payment_account_second_category_types = $this->getPaymentAccountsSecondCategoryAll();
		foreach ($payment_account_second_category_types as $key => $value) {
			$payment_account_second_category_types[$key] = lang($value['lang_key']);
		}
		return $payment_account_second_category_types;
	}

	public function getPaymentAccountsAll() {
		$payment_account_types = $this->CI->config->item("payment_account_types_all");
		return $payment_account_types;
	}

	public function getPaymentAccountsSecondCategoryAll() {
		$payment_account_second_category_types = $this->CI->config->item("payment_account_second_category_types_all");
		return $payment_account_second_category_types;
	}

	public function getPaymentAccounts() {
		$this->CI->load->model('operatorglobalsettings');
		$payment_account_types = $this->CI->operatorglobalsettings->getPaymentAccountTypes();
		return $payment_account_types;
	}

	public function getPaymentAccountFlagsKV() {
		$payment_account_types = $this->getPaymentAccounts(); // $this->CI->config->item("payment_account_types");
		foreach ($payment_account_types as $key => $value) {
			$payment_account_types[$key] = lang($value['lang_key']);
		}
		return $payment_account_types;
	}

	public function insertEmptyToHeader($kv, $emptyValue, $emptyLabel) {
		if (!empty($kv)) {

			$data = array($emptyValue => $emptyLabel);
			foreach ($kv as $key => $value) {
				$data[$key] = $value;
			}

			return $data;

			// return array_merge(array($emptyValue => $emptyLabel), $kv);
		}
		return $kv;
	}

	public function getKeyForFilePath($filepath) {
		return base64_encode($filepath) . PRODUCTION_VERSION;
	}

	public function saveFileToCache($filepath) {
		$data = null;
		// $this->debug_log('saveFileToCache: ' . $filepath);
		if (file_exists($filepath)) {
			$key = $this->getKeyForFilePath($filepath);
			// $this->CI->load->driver('cache', array('adapter' => 'memcached', 'backup' => 'file'));
			$data = file_get_contents($filepath);

			$this->saveTextToCache($key, $data);
			// $this->CI->cache->save($key, $data, 0);
		}
		return $data;
	}

	public function getFileFromCache($filepath) {
		$key = $this->getKeyForFilePath($filepath);
		// $this->CI->load->driver('cache', array('adapter' => 'memcached', 'backup' => 'file'));
		// $data = $this->CI->cache->get($key);
		// $this->debug_log('getFileFromCache: ' . $filepath);
		$data = $this->getTextFromCache($key);
		if (empty($data) || ENVIRONMENT == 'development') {
			$data = $this->saveFileToCache($filepath);
		}
		return $data;
	}

	const MAX_CACHE_ITEM_SIZE = 1073741824;
	const MAX_CACHE_KEY_LENGTH = 250;

	const REDIS_DB_FOR_ID_GENERATOR=2;
	const REDIS_DB_SESSION=1;
	const REDIS_DB_CACHE=0;
	const SESSION_MAP_KEY='SESSION_MAP';

	public function getDefaultRedisServer($db=self::REDIS_DB_CACHE){
		if(empty($this->_redis)){
			$this->_redis=try_load_redis($this->CI);
		}
		if(!empty($this->_redis)){
	        try{
	        	//default db of redis
	        	$this->_redis->select($db);
			}catch(Exception $e){
				$this->error_log('connect redis failed', $e);
				$this->_redis=null;
			}
		}

		return $this->_redis;
	}

	public function getSessionRedisServer(){
		return $this->getDefaultRedisServer(self::REDIS_DB_SESSION);
	}

	public function getQuerySessionKeyForRedis(){
		$sess_table_name=$this->getConfig('sess_table_name');
		return $this->getAppPrefix().'-'.$sess_table_name.'-*';
	}

	public function getSessionIdKeyForRedis($sessionId, $specialSessionTable=null){
		if(empty($specialSessionTable)){
			$specialSessionTable=$this->getConfig('sess_table_name');
		}
		return $this->getAppPrefix().'-'.$specialSessionTable.'-'.$sessionId;
	}

	public function getSessionHashMapKeyForRedis($specialSessionTable){
		return $this->getAppPrefix().'-'.$specialSessionTable.'-'.self::SESSION_MAP_KEY;
	}

	/**
	 * save text (< 1mb) to cache
	 * @param string $key <250
	 * @param string $text < 1mb
	 * @param int $ttl default is 0, seconds
	 */
	public function saveTextToCache($key, $text, $ttl = 0) {
		if ($this->getConfig('disable_cache')) {
			return $text;
		}

		if (is_array($text) || is_object($text)) {
			$text = $this->encodeJson($text);
		}
		$success=false;
		$reason=null;
		if (!empty($key) && strlen($key) < self::MAX_CACHE_KEY_LENGTH && strlen($text) < self::MAX_CACHE_ITEM_SIZE) {

			$cache_driver_type=$this->getConfig('cache_driver_type');
			if($cache_driver_type=='redis'){
				$redis=$this->getDefaultRedisServer();
				if(!empty($redis)){
			        $default_redis_expire_time=$this->getConfig('default_redis_expire_time');
			        try{
			        	if($ttl==0){
			        		$ttl=$default_redis_expire_time;
			        	}
						$success=$redis->setEx($this->getAppPrefix().'-'.$key, $ttl, $text);
					}catch(Exception $e){
						$this->error_log('try get cache failed', $this->getAppPrefix(), $key, $text, $ttl);
						$reason='get exception when call redis';
				        $success=false;
					}
					if(!$success){
						$reason='save to redis failed';
					}
				}else{
					$reason='no redis';
				}
			}else{
				$this->CI->load->driver('cache', array('adapter' => 'memcached', 'backup' => 'file'));
				$success=$this->CI->cache->save($this->getAppPrefix().'-'.$key, $text, $ttl);
				if(!$success){
					$reason='save to memcached failed';
				}
			}
		}

		$monitor_cache_key=$this->getConfig('monitor_cache_key');
        if($monitor_cache_key=='*' || (!empty($monitor_cache_key) && $key==$monitor_cache_key)){
            $this->debug_log('cache save', ['_app_prefix'=>$this->getAppPrefix(), 'key'=>$key,
            	'text'=>$text, 'ttl'=>$ttl, 'success'=>$success]);
        }
        if(!$success){
	        $this->utils->error_log('save cache failed', $success, $reason);
        }

		return $text;
	}

	public function notEmptyTextFromCache($key) {
		if ($this->getConfig('disable_cache')) {
			return false;
		}
		return !empty($this->getTextFromCache($key));
	}

	public function getTextFromCache($key) {
		if ($this->getConfig('disable_cache')) {
			return false;
		}

		$monitor_cache_key=$this->getConfig('monitor_cache_key');
		$cache_driver_type=$this->getConfig('cache_driver_type');
		if($cache_driver_type=='redis'){
			$redis=$this->getDefaultRedisServer();
			if(!empty($redis)){
		        try{
					$data=$redis->get($this->getAppPrefix().'-'.$key);
				}catch(Exception $e){
					$this->error_log('try get cache failed', $this->getAppPrefix(), $key, $data);
					$data=null;
				}
			}else{
				//load redis failed
				$data=false;
			}
		}else{
			$this->CI->load->driver('cache', array('adapter' => 'memcached', 'backup' => 'file'));
			$data = $this->CI->cache->get($this->getAppPrefix().'-'.$key);
		}
        if($monitor_cache_key=='*' || (!empty($monitor_cache_key) && $key==$monitor_cache_key)){
            $this->debug_log('cache get', ['_app_prefix'=>$this->getAppPrefix(),
            	'key'=>$key, 'data'=>$data]);
        }

		return $data;
	}

	public function saveJsonToCache($key, $jsonArr, $ttl = 0) {
		return $this->saveTextToCache($key, $this->encodeJson($jsonArr), $ttl);
	}

	public function getJsonFromCache($key) {
		$data = $this->getTextFromCache($key);
		if ($data === false) {
			return false;
		}
		if (!empty($data)) {
			return $this->decodeJson($data);
		}
		return null;
	}

	public function deleteCache($key = null) {
		if ($this->getConfig('disable_cache')) {
			return false;
		}

		$success=false;
		$monitor_cache_key=$this->getConfig('monitor_cache_key');
		$cache_driver_type=$this->getConfig('cache_driver_type');
		if($cache_driver_type=='redis'){
			$redis=$this->getDefaultRedisServer();
			if(!empty($redis)){
				if(is_null($key)) {
					//delete all
					$success=$redis->flushDb();
					$this->utils->debug_log('delete all cache from redis', $success);
				}else{
					//$redis->unlink($this->getAppPrefix().'-'.$key);
					if(method_exists($redis, 'unlink')){
						$redis->unlink($this->getAppPrefix().'-'.$key);
					}else{
						$this->error_log('error using unlink in redis');
						$redis->del($this->getAppPrefix().'-'.$key);
					}

					$success=true;
				}
			}else{
				$this->error_log('no redis');
			}
		}else{
			$this->CI->load->driver('cache', array('adapter' => 'memcached', 'backup' => 'file'));
			if(is_null($key)) {
				# delete the entire cache
				$success=$this->CI->cache->clean();
				$this->utils->debug_log('delete all cache from memcached', $success);
			} else {
				$success=$this->CI->cache->delete($this->getAppPrefix().'-'.$key);
			}

		}
        if($monitor_cache_key=='*' || (!empty($monitor_cache_key) && $key==$monitor_cache_key)){
            $this->debug_log('delete cache', ['_app_prefix'=>$this->getAppPrefix(),
            	'key'=>$key, 'success'=>$success]);
        }
		return $success;
	}

	public function getDefaultItemsPerPage() {
		return $this->CI->config->item('default_items_pre_page');
	}

	const TYPE_STRING = 'string';
	const TYPE_CURRENCY = 'currency';
	const TYPE_DATE = 'date';
	const TYPE_DATETIME = 'datetime';
	const TYPE_LANG = 'lang';

	public function showField($row, $fldName, $type = self::TYPE_STRING) {
		if ($row && !empty($fldName)) {
			$val = null;
			//TODO format
			if (is_object($row) && isset($row->$fldName)) {
				$val = $row->$fldName;
			} else if (is_array($row) && isset($row[$fldName])) {
				$val = $row[$fldName];
			}
			if (!empty($val)) {
				if ($type == self::TYPE_LANG) {
					return lang($val);
				} else {
					return $val;
				}
			}
		}

		return '<i class="help-block">' . lang("lang.norecyet") . '<i/>';
	}

	public function generateLangArray($langNameArr, $langVarName = 'lang') {
		if (!empty($langNameArr)) {
			$lang = array();
			foreach ($langNameArr as $name) {
				$lang[$name] = lang($name);
			}
			return 'var ' . $langVarName . '=' . json_encode($lang) . ";\n";
		}

		return "\n";
	}

    public function convertCryptoCurrency($amount, $base, $target, $paymentType = '', $isPCFApi = false){
    	if(!empty($this->CI->config->item('custom_cryptorate_api'))){
    		$this->CI->load->library(['cryptorate/cryptorate_get']);
    		$this->CI->load->model(array('payment_account'));
    		$this->utils->debug_log('===================get convertCryptoCurrency check', $amount, $base, $target, $paymentType);
    		if($this->CI->operatorglobalsettings->getSettingValue('cronjob_auto_get_usdt_crypto_currency_rate') && ($target == 'USDT' || $base == 'USDT')){
    			$cryptoCurrencyRateData = $this->CI->payment_account->getCryptoCurrencyRateByCurrencyAndTransaction('USDT');
    			$this->utils->debug_log('===================getCryptoCurrencyRateByCurrencyAndTransaction data', $cryptoCurrencyRateData);
				if(!empty($cryptoCurrencyRateData)){
					$crypto = $this->CI->cryptorate_get->getDecimalPlaceSetting($target, 1/$cryptoCurrencyRateData->rate);
					$this->utils->debug_log('===================convertCryptoCurrency amount and rate', $crypto ,$cryptoCurrencyRateData->rate);
		    		return array($crypto, $cryptoCurrencyRateData->rate);
		    	}else{
		    		return false;
		    	}
    		}else{
				$convertCryptoCurrencyOfCustomApi = $this->CI->cryptorate_get->getConvertCryptoCurrency($amount, $base, $target, $paymentType, $isPCFApi);
    		}
    		return $convertCryptoCurrencyOfCustomApi;
    	}

    	$this->utils->debug_log('=======================convertCryptoCurrency', $amount, $base, $target);
    	switch ($target) {
    		case 'BTC':
    		case 'TBTC':
    		case 'btc':
    		case 'tbtc':
		        $table = json_decode(file_get_contents("https://api.binance.com/api/v3/ticker/price?symbol=T".$base."BTC"), true);
		        $rate = $table['price'];
		        $target_amount = number_format($amount * $rate, 8, '.', '');

		        return array($target_amount, $rate);
    			break;
    		case 'ETH':
    		case 'TETH':
    		case 'eth':
    		case 'teth':
		        $table = json_decode(file_get_contents("https://api.binance.com/api/v3/ticker/price?symbol=T".$base."ETH"), true);
		        $rate = $table['price'];
		        $target_amount = number_format($amount * $rate, 8, '.', '');

		        return array($target_amount, $rate);
    			break;
    		case 'USDT':
    		case 'usdt':
    			$table = json_decode(file_get_contents("https://api.huobi.pro/general/exchange_rate/list"), true);
		        $rate_list = $table['data'];
		        foreach ($rate_list as $key => $value) {
		        	if($rate_list[$key]['name'] == 'usdt_cny'){
		        		$rate = $rate_list[$key]['rate'];
		        	}
		        }
		        $target_amount = number_format($amount / $rate, 4, '.', '');

		        return array($target_amount, $rate);
    			break;
    		default:
    			break;
    	}

    	switch ($base) {
    		case 'BTC':
    		case 'TBTC':
    		case 'btc':
    		case 'tbtc':
    			$table = json_decode(file_get_contents("https://api.binance.com/api/v3/ticker/price?symbol=BTC".$target."T"), true);
    			$rate = $table['price'];
    			$target_amount = number_format($amount * $rate, 2, '.', '');

    			return array($target_amount, $rate);
    			break;
    		case 'ETH':
    		case 'TETH':
    		case 'eth':
    		case 'teth':
		        $table = json_decode(file_get_contents("https://api.binance.com/api/v3/ticker/price?symbol=ETH".$target."T"), true);
		        $rate = $table['price'];
		        $target_amount = number_format($amount * $rate, 2, '.', '');

		        return array($target_amount, $rate);
    			break;
    		case 'USDT':
    		case 'usdt':
    			$table = json_decode(file_get_contents("https://api.huobi.pro/general/exchange_rate/list"), true);
		        $rate_list = $table['data'];
		        foreach ($rate_list as $key => $value) {
		        	if($rate_list[$key]['name'] == 'usdt_cny'){
		        		$rate = $rate_list[$key]['rate'];
		        	}
		        }
		        $target_amount = number_format($amount * $rate, 4, '.', '');

		        return array($target_amount, $rate);
    			break;
    		default:
    			break;
    	}
    }

    public function getCryptoToCurrecnyExchangeRate($defaultCurrency = ''){
    	$crypto_to_currecny_exchange_rate = 1;
    	if(!empty($defaultCurrency)){
    		$exchangeSetting = $this->CI->config->item('crypto_to_currecny_exchange_rate');
    		if(is_array($exchangeSetting)){
    			foreach ($exchangeSetting as $currency => $settings) {
    				if(strpos(strtoupper($defaultCurrency),$currency) !== false){
    					$exchange = $exchangeSetting[$currency]['exchange'];
    					$per 	  = $exchangeSetting[$currency]['per'];
    					$crypto_to_currecny_exchange_rate = $exchange/$per;
    					$this->utils->debug_log('=====getCryptoToCurrecnyExchangeRate', $exchange, $per);
    				}
    			}
    		}
    	}
    	return $crypto_to_currecny_exchange_rate;
    }

    /**
     * check isCryptoCurrency
     * @param  object  $banktype [$this->banktype->getBankTypeById($id);]
     * @return boolean           [description]
     */
	public function isCryptoCurrency($banktype) {
		if($this->CI->config->item('cryptocurrencies')) {
	        if(is_object($banktype)){
				$bank_code = $banktype->bank_code;
			}else if(is_array($banktype)){
				$bank_code = $banktype['bank_code'];
			}else{
				$bank_code = $banktype; //already get bank code
			}
			$cryptocurrencies = $this->CI->config->item('cryptocurrencies');
			foreach($cryptocurrencies as $cryptocurrency) {
				if(strpos(strtoupper($bank_code), $cryptocurrency) !== false){
					return true;
				}
			}
		}elseif($this->CI->config->item('enable_withdrawal_crypto_currency')){
			$withdrawal_crypto_currency = $this->CI->config->item('enable_withdrawal_crypto_currency');
			$cryptocurrencies = $withdrawal_crypto_currency['withdraw_cryptocurrencies'];
			$bank_code = $banktype->bank_code;
			foreach($cryptocurrencies as $cryptocurrency) {
				if(strpos(strtoupper($bank_code), $cryptocurrency) !== false){
					return true;
				}
			}
		}
		return false;
	}

    /**
     * getCryptoCurrency code
     * @param  object  $banktype [$this->banktype->getBankTypeById($id);]
     * @return boolean           [description]
     */
	public function getCryptoCurrency($banktype) {
		if($this->CI->config->item('cryptocurrencies')) {
			if(is_object($banktype)){
				$bank_code = $banktype->bank_code;
			}else if(is_array($banktype)){
				$bank_code = $banktype['bank_code'];
			}else{
				$bank_code = $banktype; //already get bank code
			}

			$cryptocurrencies = $this->CI->config->item('cryptocurrencies');

			foreach($cryptocurrencies as $cryptocurrency) {
				if(strpos(strtoupper($bank_code), $cryptocurrency) !== false){
					return $cryptocurrency;
				}
			}
		}elseif($this->CI->config->item('enable_withdrawal_crypto_currency')){
			$withdrawal_crypto_currency = $this->CI->config->item('enable_withdrawal_crypto_currency');
			$cryptocurrencies = $withdrawal_crypto_currency['withdraw_cryptocurrencies'];
			$bank_code = $banktype->bank_code;
			foreach($cryptocurrencies as $cryptocurrency) {
				if(strpos(strtoupper($bank_code), $cryptocurrency) !== false){
					return $cryptocurrency;
				}
			}
		}
		return false;
	}


	/**
	 * Determine crypto currency used by 3rd party payment by gateway name
	 * @param	string	$pay_gateway_name
	 * @see  	comapi_lib::crypto_currency_xchg_rate_3rdparty_payment()
	 * @see		t1t_comapi_module_third_party_deposit::thirdPartyDepositForm()
	 * @return	mixed	string if a cryptocurrency can be determined; otherwise false
	 */
	public function getCryptoCurrencyBy3rdPartyPayName($pay_gateway_name) {
		if ($this->CI->config->item('cryptocurrencies')) {
	        // $bank_code = $banktype->bank_code;
			$cryptocurrencies = $this->CI->config->item('cryptocurrencies');
			$pg_name_clean = strtoupper($pay_gateway_name);

			foreach($cryptocurrencies as $cryptocurrency) {
				if(strpos($pg_name_clean, $cryptocurrency) !== false){
					return $cryptocurrency;
				}
			}
		} elseif ($this->CI->config->item('enable_withdrawal_crypto_currency')) {
			$withdrawal_crypto_currency = $this->CI->config->item('enable_withdrawal_crypto_currency');
			$cryptocurrencies = $withdrawal_crypto_currency['withdraw_cryptocurrencies'];
			// $bank_code = $banktype->bank_code;
			foreach ($cryptocurrencies as $cryptocurrency) {
				if (strpos($pg_name_clean, $cryptocurrency) !== false) {
					return $cryptocurrency;
				}
			}
		}
		return false;
	}

	public function getCustCryptoUpdateTiming($cryptoCurrency) {
		if(!empty($this->CI->config->item('custom_cryptorate_api'))){
    		$this->CI->load->library(['cryptorate/cryptorate_get']);
    		$custCryptoUpdateTiming = $this->CI->cryptorate_get->getCustCryptoUpdateTiming($cryptoCurrency);
    		return $custCryptoUpdateTiming;
    	}else{
    		//default
    		return 30*60;
    	}
	}

	public function getCustCryptoAllowCompareDigital($cryptoCurrency) {
		if(!empty($this->CI->config->item('custom_cryptorate_api'))){
    		$this->CI->load->library(['cryptorate/cryptorate_get']);
    		$custCryptoAllowCompareDigital = $this->CI->cryptorate_get->getCustCryptoAllowCompareDigital($cryptoCurrency);
    		return $custCryptoAllowCompareDigital;
    	}else{
    		//default
    		return 0;
    	}
	}

	public function getCustCryptoInputDecimalPlaceSetting($cryptoCurrency, $reciprocal = true) {
		if(!empty($this->CI->config->item('custom_cryptorate_api'))){
    		$this->CI->load->library(['cryptorate/cryptorate_get']);
    		$custCryptoInputDecimalPlaceSetting = $this->CI->cryptorate_get->getCustCryptoInputDecimalPlaceSetting($cryptoCurrency, $reciprocal);
    		return $custCryptoInputDecimalPlaceSetting;
    	}else{
    		return false;
    	}
	}

	public function isAlipay($banktype) {
		$bank_code = $banktype->bank_code;
		if ($banktype->bankTypeId == $this->CI->playerbankdetails->BANK_TYPE_ALIPAY) {
			return true;
		}
		elseif (substr($bank_code, 0, 6) == 'ALIPAY') {
			return true;
		}
		return false;
	}

	public function isUnionpay($banktype) {
		$bank_code = $banktype->bank_code;
		if ($banktype->bankTypeId == $this->CI->playerbankdetails->BANK_TYPE_ALIPAY) {
			return true;
		}
		elseif (substr($bank_code, 0, 8) == 'UNIONPAY') {
			return true;
		}
		return false;
	}

	public function isWechat($banktype) {
		$bank_code = $banktype->bank_code;
		if ($banktype->bankTypeId == $this->CI->playerbankdetails->BANK_TYPE_ALIPAY) {
			return true;
		}
		elseif (substr($bank_code, 0, 6) == 'WECHAT') {
			return true;
		}
		return false;
	}

	/**
	 *
	 * for js variable
	 *
	 *
	 */
	public function toCurrencyNumber($number) {
		return $this->formatCurrency($number, false, false);
	}

	public function formatCurrencyNumber($number) {
		return $this->formatCurrency($number, false, false);
	}

	public function formatCurrencyNoSym($number) {
		return $this->formatCurrency($number, false);
	}

	public function formatCurrencyWithSymNoDecimal($number) {
		return $this->formatCurrency($number, true, true ,false);
	}

	public function formatCurrencyNoSymwithDecimal($number, $precision = 2) {
		return $this->formatCurrency($number, false, true ,true, $precision);
	}

    public function formatCurrencyWithSpecificApisDecimal($number, $game_platform_id = null) {
        list($is_exist, $number) = $this->customSpecificApisDecimals($game_platform_id, $number);

        if ($is_exist) {
            return $number;
        }

        return $this->formatCurrencyNoSym($number);
    }

	public function formatCurrency($number, $addCurrencySym = true, $enabled_thousands=true , $enabled_decimal = true, $precision = 2) {
		$this->initCurrencyConfig();
        $number = round(doubleval($number), $precision);

        if($enabled_thousands){
			if($precision != 2) {
				$str = number_format($number, $precision, $this->currency_dec_point, $this->currency_thousands_sep);
			} else {
				$str = number_format($number, ($enabled_decimal) ? $this->currency_decimals : 0, $this->currency_dec_point, $this->currency_thousands_sep);
			}
        }else{
			$str = number_format($number, ($enabled_decimal) ? $this->currency_decimals : 0, $this->currency_dec_point, '');
        }
		if ($addCurrencySym) {
			$str = $this->currency_symbol . ' ' . $str;
		}
		return $str;
	}

	public function displayCurrency($number, $options = []){
        $currency = $this->getCurrencyLabel($options);
        $display_currency_order = $this->getConfig('display_currency_order');

        $number = round(doubleval($number), $this->currency_decimals);
	    $number_format_str = number_format($number, $this->currency_decimals, $this->currency_dec_point, $this->currency_thousands_sep);
        $currency['currency_number'] = '<span class="currency_number">' . $number_format_str . '</span>';

        $html = '<span class="t1t_currency">';
        foreach($display_currency_order as $field_type){
            if(isset($currency[$field_type])){
                $html .= $currency[$field_type];
            }
        }
        $html .= '</span>';

	    return $html;
    }

    public function displayCurrencyLabel($options = []){
        $currency = $this->getCurrencyLabel($options);
        $display_currency_order = $this->getConfig('display_currency_order');

        $html = '<span class="t1t_currency">';
        foreach($display_currency_order as $field_type){
            if(isset($currency[$field_type])){
                $html .= $currency[$field_type];
            }
        }
        $html .= '</span>';

        return $html;
    }

    public function getCurrencyLabel(&$options = []){
        $this->initCurrencyConfig();

        // $this->debug_log('getCurrencyLabel', $this->getDefaultPlayerCenterCurrencyDisplayOptions(), $options);

        $this->getDefaultPlayerCenterCurrencyDisplayOptions();

        $display_currency_options = array_merge($this->getDefaultPlayerCenterCurrencyDisplayOptions(), $options);

        $currency = [];
        $currency['currency_name'] = ($display_currency_options['display_currency_name']) ? '<span class="currency_name">' . $this->currency_name . '</span>' : '';
        $currency['currency_short_name'] = ($display_currency_options['display_currency_short_name']) ? '<span class="currency_short_name">' . $this->currency_short_name . '</span>' : '';
        $currency['currency_code'] = ($display_currency_options['display_currency_code']) ? '<span class="currency_code">' . $this->currency_code . '</span>' : '';
        $currency['currency_symbol'] = ($display_currency_options['display_currency_symbol']) ? '<span class="currency_symbol">' . $this->currency_symbol . '</span>' : '';

        return $currency;
    }

    /**
     * Fixes for issue display on currency with 2 decimal
     */
    public function formatCurrencyWithTwoDecimal($number, $addCurrencySym = true) {
		if ($this->utils->getConfig('aff_earnings_report_display_zero_when_amount_is_negative')) {
			if ($number < 0) {
				$number = 0;
			}
		}

        $formatted = round($number, 2);
        $formatted = number_format((float)$formatted, 2, '.', '');
        $formatted = number_format($formatted, 2);
        if ($addCurrencySym) {
            $formatted = $this->currency_symbol . ' ' . $formatted;
        }
        return $formatted;
    }

	public function formatColorCurrency($number, $addCurrencySym = false) {
		$str='';
		if($number<0){
			$str.='<span style="color: #ff0000">';
		}
		$str.=$this->formatCurrency($number, $addCurrencySym);
		if($number<0){
			$str.='</span>';
		}

		return $str;
	}

	public function roundCurrency($doubleVal, $precision = 4) {
		return round(doubleval($doubleVal), $precision);
	}

	public function roundCurrencyForShow($doubleVal, $precision = 2) {
        $set_default_round_currency_precision_for_show = $this->utils->getConfig('set_default_round_currency_precision_for_show');

        if (!empty($set_default_round_currency_precision_for_show) && $precision == 2) {
            $precision = $set_default_round_currency_precision_for_show;
        }

		return round(doubleval($doubleVal), $precision);
	}

	public function floorCurrencyForShow($doubleVal) {
		return round(intval(($doubleVal * 100)) / 100, 2);
	}

	private $currency_init = FALSE;
	private $currency_name = null;
	private $currency_short_name = null;
	private $currency_code = null;
	private $currency_symbol = null;
	private $currency_decimals = null;
	private $currency_dec_point = null;
	private $currency_thousands_sep = null;
	private $player_center_currency_display_options = NULL;

	public function resetCurrency(){
		$this->currency_init=false;
		$this->initCurrencyConfig();
	}

	public function initCurrencyConfig() {
	    if($this->currency_init){
	        return;
        }

        if($this->initCurrencyByActiveCurrencyOnMDB()){
        	return;
        }

		$this->CI->load->model(array('currencies'));
		$currencyInfo = $this->CI->currencies->getActiveCurrencyInfo();

        if(!empty($currencyInfo)) {
            $this->currency_name = $currencyInfo['currency_name'];
            $this->currency_short_name = $currencyInfo['currency_short_name'];
            $this->currency_code = $currencyInfo['currency_code'];
            $this->currency_symbol = $currencyInfo['symbol'];
            $this->currency_decimals = $currencyInfo['currency_decimals'];
            $this->currency_dec_point = $currencyInfo['currency_dec_point'];
            $this->currency_thousands_sep = $currencyInfo['currency_thousands_sep'];
        }

        $this->currency_name = (!empty($this->currency_name)) ? $this->currency_name : $this->CI->config->item('default_currency_name');
        $this->currency_short_name = (!empty($this->currency_short_name)) ? $this->currency_short_name : $this->CI->config->item('default_currency_short_name');
        $this->currency_code = (!empty($this->currency_code)) ? $this->currency_code : $this->CI->config->item('default_currency');
        $this->currency_symbol = (!empty($this->currency_symbol)) ? $this->currency_symbol : $this->CI->config->item('default_currency_symbol');
        $this->currency_decimals = (!empty($this->currency_decimals)) ? $this->currency_decimals : $this->CI->config->item('default_currency_decimals');
        $this->currency_dec_point = (!empty($this->currency_dec_point)) ? $this->currency_dec_point : $this->CI->config->item('default_currency_dec_point');
        $this->currency_thousands_sep = (!empty($this->currency_thousands_sep)) ? $this->currency_thousands_sep : $this->CI->config->item('default_currency_thousands_sep');

        $this->initPlayerCenterCurrencyDisplayOptions();
        $this->currency_init = TRUE;
    }

    public function initPlayerCenterCurrencyDisplayOptions(){
        $player_center_currency_display_format = $this->CI->operatorglobalsettings->getSettingJson('player_center_currency_display_format');
        $player_center_currency_display_format = (empty($player_center_currency_display_format)) ? ['currency_symbol'] : $player_center_currency_display_format;

        $this->player_center_currency_display_options = [
            'display_currency_name' => in_array('currency_name', $player_center_currency_display_format),
            'display_currency_short_name' => in_array('currency_short_name', $player_center_currency_display_format),
            'display_currency_code' => in_array('currency_code', $player_center_currency_display_format),
            'display_currency_symbol' => in_array('currency_symbol', $player_center_currency_display_format)
        ];
    }

    public function initCurrencyByActiveCurrencyOnMDB(){
    	if(!$this->isEnabledMDB()){
    		return false;
    	}

    	$activeCurrency=$this->getActiveCurrencyInfoOnMDB();

    	$this->currency_name = $activeCurrency['name'];
        $this->currency_short_name = $activeCurrency['short_name'];
        $this->currency_code = $activeCurrency['code'];
        $this->currency_symbol = $activeCurrency['symbol'];
        $this->currency_decimals = $activeCurrency['decimals'];
        $this->currency_dec_point = $activeCurrency['dec_point'];
        $this->currency_thousands_sep = $activeCurrency['thousands_sep'];

        $this->initPlayerCenterCurrencyDisplayOptions();
        $this->currency_init = TRUE;

        return true;
    }

	public function getCurrentCurrency() {
		$this->initCurrencyConfig();
        $currency = [
            "currency_name" => $this->currency_name,
            "currency_short_name" => $this->currency_short_name,
            "currency_code" => $this->currency_code,
            "symbol" => $this->currency_symbol,
            'currency_decimals' => $this->currency_decimals,
            'currency_dec_point' => $this->currency_dec_point,
            'currency_thousands_sep' => $this->currency_thousands_sep,
        ];
		return $currency;
	}

	public function getActiveCurrencyKey() {
		$this->initCurrencyConfig();
		return $this->currency_code;
	}

    public function getDefaultPlayerCenterCurrencyDisplayOptions(){
	    return $this->player_center_currency_display_options;
    }

	public function isLocalIP($ip) {
		return is_local_ip($ip);
	}

	public function getIP() {
		if($this->getConfig('use_real_ip_on_player')){
			return $this->tryGetRealIPWithoutWhiteIP();
		}else{
			return $this->CI->input->ip_address();
		}
	}

    public function convertToBoolean($value) {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }
    
	public function is_private_ip($ip){
        return !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }

	public function getUrlContent($url) {
		//use curl
		return file_get_contents($url);
	}

	public function recordAction($management, $action, $description, $db = null) {
		$this->CI->load->model(array('roles'));
		$this->CI->load->library(array('authentication'));

        if ($db == null) {
            $db = $this->CI->db;
        }

		$user_role = 'NOT FOUND';
		if (method_exists($this->CI->authentication, 'getUserId')) {
			$role = $this->CI->roles->getRoleByUserId($this->CI->authentication->getUserId(), $db);
			if (!empty($role)) {
				$user_role = $role['roleName'];
			}
		}

        $get = $this->CI->input->get() ?: array();
        $post = $this->CI->input->post() ?: array();

		$http_referer = '';
		if (isset($_SERVER['HTTP_REFERER'])) {
			$http_referer = @$_SERVER['HTTP_REFERER'];
			if (empty($http_referer)) {
				$http_referer = '';
			}
		}

		$extra = [];
		// remove passwords data
		$get_data = $this->filterPasswordLogs($get);
		if (!empty($get_data)) {
			$extra['get'] = $get_data;
		}

		$post_data = $this->filterPasswordLogs($post);
		if(!empty($post_data)) {
			$extra['post'] = $post_data;
		}

		$data = array(
			'username' => $this->CI->authentication->getUsername(),
			'management' => $management,
			'userRole' => $user_role,
			'action' => $action,
			'description' => $description,
			'logDate' => $this->getNowForMysql(),
			'status' => '0',
			'ip' => $this->getIP(),
			'referrer' => $http_referer,
			'uri' =>current_url(),
			'extra' => $extra ? json_encode($extra, JSON_PRETTY_PRINT) : NULL,
			'params' => $extra ? json_encode($extra, JSON_PRETTY_PRINT) : NULL,
		);

		$tableName=$this->getAdminLogsMonthlyTable(null, $db);
		$db->insert($tableName, $data);
	}


	private $log_description = null;
	private $log_old_value = array();
	private $log_new_value = array();

	public function set_log_description($log_description) {
		$this->log_description = $log_description;
	}

	public function set_log_old_value($log_old_value) {
		$this->log_old_value = $log_old_value;
	}

	public function set_log_new_value($log_new_value) {
		$this->log_new_value = $log_new_value;
	}

	public function logAction() {
		$class = $this->CI->router->class;

		if ($this->isAdminSubProject() && (strpos($class, 'management') || strpos($class, 'api'))) {
			$this->CI->load->model(array('roles'));
			$this->CI->load->library(array('authentication', 'report_functions', 'user_agent'));

			if ($this->CI->authentication->getUserId()) {

				$method = $this->CI->router->method;
				$get = $this->CI->input->get() ?: array();
				$post = $this->CI->input->post() ?: array();
				# $data = array_filter(array_merge($get, $post));
				# $data = $this->filterPasswordLogs(array_filter(array_merge($get, $post)));

				$http_referer = '';
				if (isset($_SERVER['HTTP_REFERER'])) {
					$http_referer = @$_SERVER['HTTP_REFERER'];
					if (empty($http_referer)) {
						$http_referer = '';
					}
				}

				$extra = [];
				// remove passwords data
				$get_data = $this->filterPasswordLogs($get);
				if (!empty($get_data)) {
					$extra['get'] = $get_data;
				}

				$post_data = $this->filterPasswordLogs($post);
				if(!empty($post_data)) {
					$extra['post'] = $post_data;
				}

				$role = $this->CI->roles->getRoleByUserId($this->CI->authentication->getUserId());
				$user_role = 'NOT FOUND';
				if (!empty($role)) {
					$user_role = $role['roleName'];
				}

				$this->CI->report_functions->recordAction(array(
					'username' => $this->CI->authentication->getUsername(),
					'userRole' => $user_role,
					'uri' => current_url(),
					'management' => $class,
					'action' => $method,
					'referrer' => $http_referer,
					'logDate' => date("Y-m-d H:i:s"),
					'ip' => $this->getIP(),
					'status' => 0,
					'extra' => $extra ? json_encode($extra, JSON_PRETTY_PRINT) : NULL,
					'params' => $extra ? json_encode($extra, JSON_PRETTY_PRINT) : NULL,
				));

			}
		}
	}

	public function filterPasswordLogs($data) {
		// remove passwords data
		if (isset($data['password'])) {
			unset($data['password']);
		}
		if (isset($data['hiddenPassword'])) {
			unset($data['hiddenPassword']);
		}
		if (isset($data['cpassword'])) {
			unset($data['cpassword']);
		}
		if (isset($data['opassword'])) {
            unset($data['opassword']);
        }
		if (isset($data['npassword'])) {
            unset($data['npassword']);
        }
        if (isset($data['ncpassword'])) {
            unset($data['ncpassword']);
        }
		return $data;
	}

	public function logAffAction() {

		// $class = $this->CI->router->class;
		if ($this->isAffSubProject()) {

			// $this->CI->load->library(array('authentication', 'rolesfunctions', 'report_functions', 'user_agent'));

			$this->CI->load->model(['log_model']);

			$get = $this->CI->input->get() ?: array();
			$post = $this->CI->input->post() ?: array();
			$data = array_filter(array_merge($get, $post));

			if (isset($data['password'])) {
				unset($data['password']);
			}

			$this->CI->log_model->recordAffLog($data);
		}

	}

	# FROM player_manager and player_function
	public function generateRandomCode($length = 5, $prefix = '', $suffix = '') {
		return $prefix . random_string('alnum', $length) . $suffix;
	}

	public function getApiListByBalanceInGameLog() {

		$apiArray = [];

		if ($this->getConfig('enabled_sync_in_balance')) {

			$this->CI->load->model('external_system');
			$allSys = $this->CI->external_system->getAllSytemGameApi();
			$debug_sync_balance_in_game_logs_include = $this->getConfig('debug_sync_balance_in_game_logs_include');

			foreach ($allSys as $aSys) {
				if ($this->CI->external_system->getExtraInfo($aSys, 'balance_in_game_log', false) ||
					in_array($aSys['id'], $debug_sync_balance_in_game_logs_include)) {
					$apiArray[] = $aSys['id'];
				}
			}

		}

		return $apiArray;
	}

	public function getAllCurrentApiList() {
		$this->CI->load->model('external_system');
		$allSys = $this->CI->external_system->getAllGameApis();
		$apiArray = array();
		foreach ($allSys as $aSys) {
			$apiArray[] = $aSys['id'];
		}
		return $apiArray;
	}

	public function getAllCurrentApiListByType($systemTypeId) {
		$this->CI->load->model('external_system');
		$allSys = $this->CI->external_system->getAllActiveSystemApiByType($systemTypeId);
		$apiArray = array();
		foreach ($allSys as $aSys) {
			$apiArray[] = $aSys['id'];
		}
		return $apiArray;
	}

	public $gameApiList = null;
	public $paymentApiList = null;
	public $teleApiList = null;

	public function getAllCurrentPaymentSystemList($skip_cache = false) {
		if (empty($this->paymentApiList) || $skip_cache) {
			$this->paymentApiList = $this->getAllCurrentApiListByType(SYSTEM_PAYMENT);
		}
		return $this->paymentApiList;
	}

	public function getAllCurrentGameSystemList($skip_cache = false) {
		if (empty($this->gameApiList) || $skip_cache) {
			$this->gameApiList = $this->getAllCurrentApiListByType(SYSTEM_GAME_API);
		}
		return $this->gameApiList;
	}

	public function getAllCurrentTeleSystemList() {
		if (empty($this->teleApiList)) {
			$this->teleApiList = $this->getAllCurrentApiListByType(SYSTEM_TELEPHONE);
		}
		return $this->teleApiList;
	}

	public function getApiListByType($systemTypeId, $get_all = false) {
		$this->CI->load->model('external_system');
		$allSys = $this->CI->external_system->getAllActiveSystemApiByType($systemTypeId, $get_all);
		return $allSys;
	}

	public function getActivePaymentSystemList() {
		return $this->getApiListByType(SYSTEM_PAYMENT);
	}

	public function getActiveGameSystemList() {
		return $this->getApiListByType(SYSTEM_GAME_API);
	}

	public function getAllGameSystemList() {
		return $this->getApiListByType(SYSTEM_GAME_API, true);
	}

	public function getPaymentSystemMap() {
		$this->CI->load->model('external_system');
		$allSys = $this->CI->external_system->getAllActiveSystemApiByType(SYSTEM_PAYMENT);
		$apiArray = array();
		foreach ($allSys as $aSys) {
			$apiArray[$aSys['id']] = $aSys['system_code'];
		}
		return $apiArray;
	}

	public function getGameTagMap($offset = null, $amountPerPage = null, &$total_rows = 0) {
		$this->CI->load->model([ 'game_tags']);
        $data = [];
        $game_tags = $this->CI->game_tags->getAllGameTagsWithPagination($offset, $amountPerPage, $total_rows);
		$apiArray = array();
		foreach ($game_tags as $game_tag) {
			$apiArray[$game_tag['id']] = $game_tag['tag_code'];
		}
		return $apiArray;
	}// EOF getGameTagMap

	public function getGameSystemMap($active_only = true, $offset = null, $amountPerPage = null, &$total_rows = 0) {

		$total_rows = null;
		$this->CI->load->model('external_system');
		if( $active_only == true ){
			// $allSys = $this->CI->external_system->getAllActiveSystemApiByType(SYSTEM_GAME_API);
			$all = false;
			$allSys = $this->CI->external_system->getAllActiveSystemApiByTypeWithPagination(SYSTEM_GAME_API, $all, $offset, $amountPerPage, $total_rows);
		}else{
			$allSys = $this->CI->external_system->getAllSytemGameApi($offset, $amountPerPage, $total_rows);
		}

		$apiArray = array();
		foreach ($allSys as $aSys) {
			$apiArray[$aSys['id']] = $aSys['system_code'];
		}
		return $apiArray;
	} // EOF getGameSystemMap

	public function getNonSeamlessGameSystemMap($active_only = true) {
		$this->CI->load->model('external_system');
		if($active_only == true)
			$allSys = $this->CI->external_system->getAllActiveNonSeamlessGameApi();
		else
			$allSys = $this->CI->external_system->getAllNonSeamlessGameApi();
		$apiArray = array();
		foreach ($allSys as $aSys) {
			$apiArray[$aSys['id']] = $aSys['system_code'];
		}
		return $apiArray;
	}

    public function getGameTransferLimit(){
        $gameMap = $this->getGameSystemMap(true);

        $transfer_limit_list = [];
        foreach($gameMap as $game_platform_id => $game_name){
            /* @var $api Abstract_game_api */
            $api = $this->loadExternalSystemLibObject($game_platform_id);
            if(!empty($api)){
	            $transfer_limit = [
	                'transfer_min_limit' => $api->getTransferMinAmount(),
	                'transfer_max_limit' => $api->getTransferMaxAmount(),
	                'amount_step' => (1 / pow(10, $api->getTransferAmountFloat()))
	            ];

	            $transfer_limit_list[$game_platform_id] = $transfer_limit;
            }
        }

        return $transfer_limit_list;
    }

	public function getApiExistsPrefix() {
		$this->CI->load->model('external_system');
		$allSys = $this->CI->external_system->getAllSytemGameApi();
		$apiArray = array();
		foreach ($allSys as $aSys) {
			$platformCode = $aSys['id'];
			$anApi = $this->loadExternalSystemLibObject($platformCode);
			if (isset($anApi) && !empty($anApi->getSystemInfo('prefix_for_username'))) {
				$apiArray[$platformCode] = $anApi;
			}
		}
		return $apiArray;
	}

    /**
     * @deprecated Not recommended. because the Max Daily deposit rule is not applied.
     * @return type
     */
	public function getDepositMenuList() {
		$this->CI->load->model(array('banktype'));
		return $this->is_mobile() ?
		$this->CI->banktype->getSpecialPaymentTypeListMobile() :
		$this->CI->banktype->getSpecialPaymentTypeList();
	}

	public function randomString($len = 12) {
		$this->CI->load->helper('string');
		return random_string('numeric', $len);
	}

	public function convertArrayToHeaders($headers) {
		$result = array();
		if (!empty($headers)) {
			foreach ($headers as $key => $value) {
				$result[] = $key . ": " . $value;
			}
		}
		return $result;
	}

	public function safe_mkdir($dir) {
		if (!file_exists($dir)) {
			@mkdir($dir, 0777, true);
		}
	}

	public function saveHttpRequest($player_id, $type, $extra = []) {

		if ($this->getConfig('og_load_testing')) {
			return true;
		}

		// OGP-23286: Use ip from $extra if available
        $ip = !empty($extra['ip']) ? $extra['ip'] : $this->CI->input->ip_address();

        $this->CI->load->library(array('user_agent'));
		$this->CI->load->model(array('http_request'));

		// $this->CI->http_request->startTrans();

		$headers = $this->CI->input->request_headers();

		$this->debug_log(' ----- headers ------', $headers);

		$device = ($this->CI->agent->is_mobile() == TRUE) ? $this->CI->agent->mobile() : $this->CI->agent->browser() . " " . $this->CI->agent->version();
		$now = $this->getNowForMysql();
		// OGP-23286: Use user_agent from $extra if available
		$ua = !empty($extra['user_agent']) ? $extra['user_agent'] : (!empty($this->CI->agent->agent) ? $this->CI->agent->agent : '');
		// OGP-23286: Use referrer from $extra if available
		$referrer = !empty($extra['referrer']) ? $extra['referrer'] : (($this->CI->agent->is_referral() == TRUE) ? $this->CI->agent->referrer() : ' ');

		$browser_type = 0;
		switch (true) {
			case isset($_SERVER['HTTP_X_APP_IOS']):
				$browser_type = Http_request::HTTP_BROWSER_TYPE_IOS;
				break;
			case isset($_SERVER['HTTP_X_APP_ANDROID']):
				$browser_type = Http_request::HTTP_BROWSER_TYPE_ANDROID;
				break;
			default:
				if ($this->CI->agent->is_mobile() == FALSE) {
					$this->debug_log('pc');
					$browser_type = Http_request::HTTP_BROWSER_TYPE_PC; //pc
				} else {
					$this->debug_log('mobile');
					$browser_type = Http_request::HTTP_BROWSER_TYPE_MOBILE; //mobile
				}
				break;
		}

		$this->debug_log(' ----- browser_type -----', $browser_type);
		//record city and country
		list($cityFromIP, $countryFromIP)=$this->getIpCityAndCountry($ip);

		$data = array(
			"playerId" => $player_id,
			"ip" => $ip,
			"cookie" => isset($headers['Cookie']) ? $headers['Cookie'] : null,
			"referrer" => $referrer,
			"user_agent" => $ua,
			"os" => $this->CI->agent->platform(),
			"device" => $device,
			"is_mobile" => ($this->CI->agent->is_mobile() == TRUE) ? 1 : 0,
			"type" => $type,
			"createdat" => $now,
			"browser_type" => $browser_type,
			'city'=>$cityFromIP,
			'country'=>$countryFromIP,
		);


		// -- OGP-10993 | send Mattermost notifications for detected private IP addresses used by players
        if($this->is_private_ip($ip)){

        	$this->CI->utils->info_log('Private IP Detected!');

        	$this->CI->load->library(['lib_queue', 'language_function']);
			$this->CI->load->model(['queue_result']);

			$app_prefix = str_replace("-", "_", $this->CI->utils->getAppPrefix());

			// -- Do not send MM alert for private IP if local setup
			if($app_prefix != 'default_og'){
				$request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
				$current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://".$_SERVER['HTTP_HOST'].$request_uri;

				// -- prepare browser type in text
				$browser_type_in_text = "N/A";
				switch ($browser_type) {
					case '1':
						$browser_type_in_text = 'PC';
						break;
					case '2':
						$browser_type_in_text = 'Mobile';
						break;
					case '3':
						$browser_type_in_text = 'Security PC';
						break;
					case '4':
						$browser_type_in_text = 'Security Mobile - IOS';
						break;
					case '5':
						$browser_type_in_text = 'Security Mobile - Android';
						break;
					default:
						$browser_type_in_text = 'N/A';
						break;
				}

				$is_mobile_in_text = 'No';

				if($data['is_mobile']) $is_mobile_in_text = 'Yes';

				// -- prepare full params
				$params = array(
	        		'player_id' 		=> $player_id,
	        		'http_request_type' => lang('http.type.' . $type),
	        		'ip_address' 		=> $ip,
	        		'referrer' 			=> !empty($data['referrer']) ? $data['referrer'] : 'N/A',
	        		'current_url' 		=> !empty($current_url) ? $current_url : 'N/A',
	        		'device'			=> !empty($data['device']) ? $data['device'] : 'N/A',
	        		'user_agent'		=> !empty($data['user_agent']) ? $data['user_agent'] : 'N/A',
	        		'is_mobile'			=> $is_mobile_in_text ?: 'N/A',
	        		'browser_type'		=> $browser_type_in_text ?: 'N/A',
	        		'datetime'			=> $now ?: 'N/A',
	        		'timezone'			=> $now ? $this->getDatetimeTimezone(new DateTime($now)) ?: 'N/A' : 'N/A',
	        		'app_prefix'		=> $app_prefix,
	        	);

        		$this->CI->utils->info_log('PID: Params = ', $params);

				$systemId 	= Queue_result::SYSTEM_UNKNOWN;
				$funcName 	= 'send_player_private_ip_mm_alert';
				$callerType = Queue_result::CALLER_TYPE_ADMIN;
				$caller = $player_id;
				$state 	= null;
				$lang 	= $this->CI->language_function->getCurrentLanguage();

				$token = $this->CI->lib_queue->addSendPlayerPrivateIpMmAlertJob($systemId, $funcName, $params, $callerType, $caller, $state, $lang);
			}

			return true;

        }

		$requestId = $this->CI->http_request->insertHttpRequest($data);

		$this->CI->http_request->syncLastDevice($player_id, $device, $now, $requestId);
		$this->CI->http_request->syncLastIP($player_id, $ip, $now, $requestId);

		// return $this->CI->http_request->endTransWithSucc();
	}

	public function getHttpOnRequest() {
		$this->CI->load->library(array('user_agent'));
		$headers = $this->CI->input->request_headers();
		$ua=!empty($this->CI->agent->agent) ? $this->CI->agent->agent : '';
		$data = array(
			"ip" => $this->CI->input->ip_address(),
			"cookie" => isset($headers['Cookie']) ? $headers['Cookie'] : null,
			"referrer" => ($this->CI->agent->is_referral() == TRUE) ? $this->CI->agent->referrer() : ' ',
			"user_agent" => $ua,
			"os" => $this->CI->agent->platform(),
			"device" => ($this->CI->agent->is_mobile() == TRUE) ? $this->CI->agent->mobile() : $this->CI->agent->browser() . " " . $this->CI->agent->version(),
			"is_mobile" => ($this->CI->agent->is_mobile() == TRUE) ? 1 : 0,
		);
		return $data;
	}

	public function decodePassword($password) {
		if (!empty($password)) {
			$this->CI->load->library(array('salt'));
			return $this->CI->salt->decrypt($password, $this->CI->config->item('DESKEY_OG'));
		}
		return $password;
	}

	public function encodePassword($password) {
		if (!empty($password)) {
			$this->CI->load->library(array('salt'));
			return $this->CI->salt->encrypt($password, $this->CI->config->item('DESKEY_OG'));
		}
		return $password;
	}

	/**
	 *
	 * encode password by md5
	 *
	 * @param  string  $password   unencrypted password
	 * @param  boolean $lower_case
	 * @return string
	 */
	public function encodePasswordMD5($password, $lower_case = true) {
		if (!empty($password)) {
			if ($lower_case) {
				return strtolower(md5($password));
			} else {
				return strtoupper(md5($password));
			}
		}
		return $password;
	}

	public function convertToDateString($dateTimeStr) {
		if (!empty($dateTimeStr)) {
			$d = new \DateTime($dateTimeStr);
			return $this->formatDateForMysql($d);
		}
		return $dateTimeStr;
	}

	public function getJSNumberVariableDefineList($arr, $nameList) {
		$rlt = '';
		if (!empty($nameList)) {
			foreach ($nameList as $name) {
				$rlt = $rlt . $this->getJSNumberVariableDefine($arr, $name);
			}
		}
		return $rlt;
	}

	public function getJSNumberVariableDefine($arr, $name) {
		if ($arr && $name) {
			$val = $this->safeGetArray($arr, $name);
			if (empty($val)) {
				$val = "0";
			}
			return "var " . $name . "=" . $val . ";\n";
		}

		return "var " . $name . ";\n";
	}

	public function getMaxUploadSizeByte() {
		return $this->CI->config->item('max_upload_size_byte');
	}

    public function getUploadMaxWidth() {
		return $this->CI->config->item('upload_image_max_width');
	}

    public function getUploadMaxHeight() {
		return $this->CI->config->item('upload_image_max_height');
	}

	public function getUploadConfig($path){
        $config = array(
            'allowed_types' => $this->CI->config->item("allowed_upload_file"),
            'max_size'      => $this->getMaxUploadSizeByte(),
            'overwrite'     => true,
            'remove_spaces' => true,
            'upload_path'   => $path,
        );
        return $config;
    }

	public function getPromoConstJS() {
		$this->CI->load->model(array('promorules'));

		return "var PROMO_CONST=" . json_encode($this->CI->promorules->getConst()) . ";\n";
	}

    /**
     * Get Promotion Mock array via POST and Config
     *
     * @param array $post_value If its need ,assign via POST.
     * @param array $mock The keys of mock, its usually in Config.
     * @return array The mock array, the values are Not be null, but maybe value will be 0.
     */
    public function getPromotionMock($_post = [], $mock = null){
        if( $mock === null ){
            $mock=$this->utils->getConfig('promotion_mock');
        }

        $notnull_mock=[];
        foreach ($mock as $key => &$value) {

            if( isset($_post[$key]) ){
                //maybe value will be 0
                if( $_post[$key] !== FALSE
                    && $_post[$key]!== null
                    && $_post[$key]!== ''
                ){
                    $value = $post_value;
                }
            } // EOF if( isset($_post[$key]) ){...
            if($value!==FALSE && $value!==null && $value!==''){
                $notnull_mock[$key]=$value;
            }
        } // EOF foreach ($mock as $key => &$value) {...
        return $notnull_mock;
    } // EOF getPromotionMock()

	/**
	 * date -1 day
	 * @param string date
	 * @return string
	 */
	public function getLastDay($date) {
		$d = new \DateTime($date);
		$d->modify('-1 day');
		return $d->format('Y-m-d');
	}

	public function getGamesTree($showGameTree = true) {
		$this->CI->load->model(array('game_description_model'));
		return $this->CI->game_description_model->getGamesTree($showGameTree);
	}

	public function getPlayerLvlTree($showGameTree = true) {
		$this->CI->load->model(array('group_level'));
		return $this->CI->group_level->getGroupPlayerLevels($showGameTree);
	}

	public function getPlayerLevel($showGameTree = true) {
		$this->CI->load->model(array('game_description_model'));
		return $this->CI->game_description_model->getPlayerLevel($showGameTree);
	}

	public function dateDiff($time1, $time2, $precision = 6) {
		// If not numeric then convert texts to unix timestamps
		if (!is_int($time1)) {
			$time1 = strtotime($time1);
		}
		if (!is_int($time2)) {
			$time2 = strtotime($time2);
		}

		// If time1 is bigger than time2
		// Then swap time1 and time2
		if ($time1 > $time2) {
			$ttime = $time1;
			$time1 = $time2;
			$time2 = $ttime;
		}

		// Set up intervals and diffs arrays
		$intervals = array('year', 'month', 'day', 'hour', 'minute', 'second');
		$diffs = array();

		// Loop thru all intervals
		foreach ($intervals as $interval) {
			// Create temp time from time1 and interval
			$ttime = strtotime('+1 ' . $interval, $time1);
			// Set initial values
			$add = 1;
			$looped = 0;
			// Loop until temp time is smaller than time2
			while ($time2 >= $ttime) {
				// Create new temp time from time1 and interval
				$add++;
				$ttime = strtotime("+" . $add . " " . $interval, $time1);
				$looped++;
			}

			$time1 = strtotime("+" . $looped . " " . $interval, $time1);
			$diffs[$interval] = $looped;
		}

		$count = 0;
		$times = array();
		// Loop thru all diffs
		foreach ($diffs as $interval => $value) {
			// Break if we have needed precission
			if ($count >= $precision) {
				break;
			}
			// Add value and interval
			// if value is bigger than 0
			if ($value > 0) {
				// Add s if value is not 1
				if ($value != 1) {
					$interval .= "s";
				}
				// Add value and interval to times array
				$times[] = $value . " " . $interval;
				$count++;
			}
		}

		// Return string with times
		return implode(", ", $times);
	}

	public function openProfiler() {
		if ($this->CI->config->item('enable_profiler')) {
			$this->CI->output->enable_profiler(TRUE);
		}
	}

	public function markProfilerStart($mark) {
		if ($this->CI->config->item('enable_profiler')) {
			$this->CI->benchmark->mark($mark . '_start');
		}
		$this->startEvent($mark, $mark . ' start');
	}

	public function markProfilerEnd($mark) {
		if ($this->CI->config->item('enable_profiler')) {
			$this->CI->benchmark->mark($mark . '_end');
		}
		$this->endEvent($mark);
	}

	/**
	 * Mark XXX_start to XXX_end for elapsed time
	 *
	 * @param string $mark The mark name, MUST BE have _start and _end marks for one mark. Ex: "list_query_start" at line 123 and "list_query_end" at line 126 While $mark="list_query".
	 * @param string &$elapsed_time Spend time.
	 * @return void
	 */
	public function markProfilerEndAndPrint($mark, &$elapsed_time = '') {
		$this->markProfilerEnd($mark);
		if ($this->CI->config->item('enable_profiler')) {
			$this->CI->benchmark->mark($mark . '_end');
		}

		return $this->printProfilerLog($mark, $elapsed_time);
	}

	/**
	 * Mark XXX_start to XXX_end for elapsed time
	 *
	 * @param string $mark The mark name, MUST BE have _start and _end marks for one mark. Ex: "list_query_start" at line 123 and "list_query_end" at line 126 While $mark="list_query".
	 * @return NULL|float Spend time.
	 */
	public function getMarkProfiler($mark){
		$elapsed_time = NULL;
		if ($this->CI->config->item('enable_profiler')) {
			$elapsed_time = $this->CI->benchmark->elapsed_time($mark . '_start', $mark . '_end');
		}
		return $elapsed_time;
	}

	/**
	 * Log Mark in log file.
	 *
	 * @param string $mark The mark name, MUST BE have _start and _end marks for one mark. Ex: "list_query_start" at line 123 and "list_query_end" at line 126 While $mark="list_query".
	 * @param string &$elapsed_time Spend time.
	 * @return void
	 */
	public function printProfilerLog($mark, &$elapsed_time = '') {
		if ($this->CI->config->item('enable_profiler')) {
			$elapsed_time = $this->CI->benchmark->elapsed_time($mark . '_start', $mark . '_end');
			return $this->debug_log($mark, $elapsed_time);
		}
		return '';
	}

	public function getPing($ping_time, $refresh_session_url) {
		$enable_ping = $this->getConfig('enable_ping');

		if ($enable_ping) {

			return <<<EOD

window['_ping']=function(){

	var PING_TIME=$ping_time;

    var img=document.getElementById('_img_refresh_session');
    if(!img){
        img=document.createElement("IMG");
        img.id='_img_refresh_session';
        img.style='position: absolute;';
        img.border='0';
        img.width='0';
        img.height='0';
        // img.style="display:none;";
        document.body.appendChild(img);
    }

    img.src='$refresh_session_url?'+Math.random();

    setTimeout('_ping()',PING_TIME);
}

_ping();

EOD;

		} else {
			return "";
		}

	}

	/**
	 *
	 * DO NOT change this function, should discuss first
	 *
	 * @param  mix $xml
	 * @return result
	 */
	public function xmlToArray($xml) {

		$return = null;
		if ($xml instanceof SimpleXMLElement) {
			$children = $xml->children();
			$return = null;
		}

		foreach ($children as $element => $value) {
			if ($value instanceof SimpleXMLElement) {
				$values = (array) $value->children();

				if (count($values) > 0) {
					$return[$element] = $this->xmlToArray($value);
				} else {
					if (!isset($return[$element])) {
						$return[$element] = (string) $value;
					} else {
						if (!is_array($return[$element])) {
							$return[$element] = array($return[$element], (string) $value);
						} else {
							$return[$element][] = (string) $value;
						}
					}
				}
			}
		}
		if (is_array($return)) {
			return $return;
		} else {
			return false;
		}
	}

	public function arrayToXml($array, $xml = false) {

		if ($xml === false) {
			$xml = new SimpleXMLElement('<?xml version=\'1.0\' encoding=\'utf-8\'?><' . key($array) . '/>');
			$array = $array[key($array)];
		}

		foreach ($array as $key => $value) {
			if (is_array($value)) {
				if (array_values($value) !== $value) {
					$this->arrayToxml($value, $xml->addChild($key));
				} else {
					foreach ($value as $value_2) {
						$this->arrayToxml($value_2, $xml->addChild($key));
					}
				}
			} else {
				if (substr($key, -5) == "_attr") {
					$xml->addAttribute(substr($key, 0, -5), $value);
				} elseif ($key == '_value') {
					$xml[0] = $value;
				} else {
					$xml->addChild($key, $value);
				}
			}
		}

		return $xml->asXML();
	}

		/**
		 * Convert Array to Xml with stand alone attribure
		 *
		 * @param array $array
		 * @param boolean $xml
		 * @param boolean $standAlone
		 *
		 * @return xml
		 */
		public function arrayToXmlStandAlone($array,$xml=false,$standAlone=true)
		{
			if($xml === false){

				$xml = new SimpleXMLElement('<?xml version=\'1.0\' encoding=\'utf-8\' ?><' . key($array) . '/>');

				if($standAlone){
					$xml = new SimpleXMLElement('<?xml version=\'1.0\' encoding=\'utf-8\' standalone=\'yes\'?><' . key($array) . '/>');
				}

				$array = $array[key($array)];
			}

			foreach($array as $key => $value){
				if(is_array($value)){
					if(array_values($value) != $value){
						$this->arrayToXmlStandAlone($value,$xml->addChild($key));
					}else{
						foreach($value as $value_2){
							$this->arrayToXmlStandAlone($value_2,$xml->addChild($key));
						}
					}
				}else{
					if(substr($key, -5) == "_attr"){
						$xml->addAttribute(substr($key,0, -5),$value);
					}
					elseif($key == '_value'){
						$xml[0] = $value;
					}else{
						$xml->addChild($key,$value);
					}
				}
			}

			return $xml->asXML();
		}

	public function arrayToPlainXml($array, $xml = false) {
		if ($xml === false) {
			$xml = new SimpleXMLElement('<' . key($array) . '/>');
			$array = $array[key($array)];
		}
		foreach ($array as $key => $value) {
			if (is_array($value)) {
				$this->arrayToPlainXml($value, $xml->addChild($key));
			} else {
				$xml->addChild($key, $value);
			}
		}
		return $xml->asXML();
	}

	public function getIV1() {
		return @mcrypt_create_iv(16, MCRYPT_DEV_URANDOM);
	}

	public function getIV2() {
		$size = @mcrypt_get_iv_size(MCRYPT_CAST_256, MCRYPT_MODE_CFB);
		return @mcrypt_create_iv($size, MCRYPT_DEV_RANDOM);
	}

	public function aes256_cbc_encrypt($key, $data, $iv) {
		if (32 !== strlen($key)) {
			$key = hash('SHA256', $key, true);
		}

		if (16 !== strlen($iv)) {
			$iv = hash('MD5', $iv, true);
		}

		$padding = 16 - (strlen($data) % 16);
		$data .= str_repeat(chr($padding), $padding);
		return @mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $data, MCRYPT_MODE_CBC, $iv);
	}

	public function aes256_cbc_decrypt($key, $data, $iv) {
		if (32 !== strlen($key)) {
			$key = hash('SHA256', $key, true);
		}

		if (16 !== strlen($iv)) {
			$iv = hash('MD5', $iv, true);
		}

		$data = @mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, $data, MCRYPT_MODE_CBC, $iv);
		$padding = ord($data[strlen($data) - 1]);
		return substr($data, 0, -$padding);
	}

	public function aes128_cbc_encrypt($key, $data, $iv) {
		if (16 !== strlen($key)) {
			$key = hash('MD5', $key, true);
		}

		if (16 !== strlen($iv)) {
			$iv = hash('MD5', $iv, true);
		}

		$padding = 16 - (strlen($data) % 16);
		$data .= str_repeat(chr($padding), $padding);
		return base64_encode(@mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $data, MCRYPT_MODE_CBC, $iv));
	}

	public function aes128_cbc_decrypt($key, $data, $iv) {
		if (16 !== strlen($key)) {
			$key = hash('MD5', $key, true);
		}

		if (16 !== strlen($iv)) {
			$iv = hash('MD5', $iv, true);
		}

		$data = @mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, $data, MCRYPT_MODE_CBC, $iv);
		$padding = ord($data[strlen($data) - 1]);
		return substr($data, 0, -$padding);
	}
	/**
	 *
	 *
	 * @return int
	 *
	 */
	public function getThisYearThisMonth() {
		$today = new DateTime();
		return array(intval($today->format('Y')), intval($today->format('m')));
	}

	public function getStringYearMonth($year, $month) {
		return $year . str_pad($month, 2, '0', STR_PAD_LEFT);
	}

	public function dateRange($first, $last, $dashstyle = false) {
		$dates = array();
		$current = strtotime($first);
		$last = strtotime($last);
		while ($current <= $last) {
			$dates[] = $dashstyle ? date('Y-m-d', $current) : date('Y/m/d', $current);
			$current = strtotime('+1 day', $current);
		}
		return $dates;
	}

	/**
	 * Get all range by period with $first and $last.
	 *
	 * The test code,
	 * http://sandbox.onlinephpfunctions.com/code/50411b755f07d0ac901c76df432ed592b366f7e9
	 * <code>
	 * /// within one period
	 * $first = '2021-12-21 12:00:23';
	 * $last = '2021-12-21 12:22:23';
	 * $periodType = 'daily';
	 * $dateRange = dateRangeByPeriod($first, $last, $periodType, true);
	 * // Expected Result: One range and include $first, $last in the ranges of the return.
	 *
	 * $first = '2021-12-21 12:00:23';
	 * $last = '2021-12-25 12:22:23';
	 * $periodType = 'daily';
	 * $dateRange = dateRangeByPeriod($first, $last, $periodType, true);
	 * // Expected Result: The 5 ranges with $first and $last.
	 *
	 * /// within one period
	 * $first = '2021-11-23 12:00:23';
	 * $last = '2021-11-25 12:22:23';
	 * $periodType = 'weekly';
	 *
	 * $first = '2021-11-21 12:00:23';
	 * $last = '2021-12-25 12:22:23';
	 * $periodType = 'weekly';
	 *
	 * /// within one period
	 * $first = '2021-11-21 12:00:23';
	 * $last = '2021-11-25 12:22:23';
	 * $periodType = 'monthly';
	 *
	 * $first = '2021-11-21 12:00:23';
	 * $last = '2021-12-25 12:22:23';
	 * $periodType = 'monthly';
	 *
	 * $first = '2021-11-21 12:00:23';
	 * $last = '2022-01-25 12:22:23';
	 * $periodType = 'monthly';
	 *
	 * /// within one period
	 * $first = '2021-11-21 12:00:23';
	 * $last = '2021-12-25 12:22:23';
	 * $periodType = 'yearly';
	 *
	 * $first = '2021-11-21 12:00:23';
	 * $last = '2022-01-25 12:22:23';
	 * $periodType = 'yearly';
	 *
	 * $dateRange = dateRangeByPeriod($first, $last, $periodType, true);
	 * </code>
	 *
	 * @param string $first The begin datetime, format, "Y-m-d H:i:s".
	 * @param string $last The end datetime, format, "Y-m-d H:i:s".
	 * @param string $periodType The period type, ex: daily, weekly, monthly and yearly.
	 * @param boolean $isIncludeLatestRange If its true, the Range will include the $first and the $last datetime, that maybe exceed the $last datetime.
	 * If its false, the Range only include the $first into the range of the return array.
	 * @return array The Range Array by Period. A Range format,
	 * - $rangeByPeriod[n]["from"] => string The datetime, ex: "2021-12-21 00:00:00".
	 * - $rangeByPeriod[n]["to"] => string The datetime, ex: "2021-12-21 23:59:59".
	 * - $rangeByPeriod[n]["first"] => string (optional) The datetime, ex: "2021-12-21 12:00:23"
	 * - $rangeByPeriod[n]["last"] => string (optional) The datetime, ex: "2021-12-21 12:22:23"
	 */
	public function dateRangeByPeriod($first, $last, $periodType, $isIncludeLatestRange = false) {
		$rangeByPeriod = array();
		$currentTimestamp = strtotime($first);
		$lastTimestamp = strtotime($last);
		$_firstTimestamp = strtotime($first);
		$_lastTimestamp = strtotime($last);
		while ($currentTimestamp <= $lastTimestamp) {
			$aRangeByPeriod = []; // reset
			switch ($periodType) {
				case 'daily':
					$fromDatetime = date('Y-m-d 00:00:00', $currentTimestamp);
					$toDatetime = date('Y-m-d 23:59:59', $currentTimestamp);
					$aRangeByPeriod = ['from' => $fromDatetime, 'to' => $toDatetime];
					$_fromDatetimeTimestamp = strtotime($fromDatetime);
					$_toDatetimeTimestamp = strtotime($toDatetime);
					if($_fromDatetimeTimestamp <= $_firstTimestamp && $_firstTimestamp <= $_toDatetimeTimestamp){
						$aRangeByPeriod['first'] = $first;
					}
					if($_fromDatetimeTimestamp <= $_lastTimestamp && $_lastTimestamp <= $_toDatetimeTimestamp){
						$aRangeByPeriod['last'] = $last;
					}
					$currentTimestamp = strtotime('+1 day', $currentTimestamp);
					break;

				case 'weekly':
					$fromDatetime = date("Y-m-d 00:00:00", strtotime("this week monday", $currentTimestamp));
					$toDatetime = date("Y-m-d 23:59:59", strtotime("this week sunday", $currentTimestamp));
					$aRangeByPeriod = ['from' => $fromDatetime, 'to' => $toDatetime];
					$_fromDatetimeTimestamp = strtotime($fromDatetime);
					$_toDatetimeTimestamp = strtotime($toDatetime);
					if($_fromDatetimeTimestamp <= $_firstTimestamp && $_firstTimestamp <= $_toDatetimeTimestamp){
						$aRangeByPeriod['first'] = $first;
					}
					if($_fromDatetimeTimestamp <= $_lastTimestamp && $_lastTimestamp <= $_toDatetimeTimestamp){
						$aRangeByPeriod['last'] = $last;
					}
					$currentTimestamp = strtotime('+1 week', $currentTimestamp);
					break;

				case 'monthly':
					$fromDatetime = date("Y-m-d 00:00:00", strtotime("first day of this month", $currentTimestamp));
					$toDatetime = date("Y-m-d 23:59:59", strtotime("last day of this month", $currentTimestamp));
					$aRangeByPeriod = ['from' => $fromDatetime, 'to' => $toDatetime];
					$_fromDatetimeTimestamp = strtotime($fromDatetime);
					$_toDatetimeTimestamp = strtotime($toDatetime);
					if($_fromDatetimeTimestamp <= $_firstTimestamp && $_firstTimestamp <= $_toDatetimeTimestamp){
						$aRangeByPeriod['first'] = $first;
					}
					if($_fromDatetimeTimestamp <= $_lastTimestamp && $_lastTimestamp <= $_toDatetimeTimestamp){
						$aRangeByPeriod['last'] = $last;
					}
					$currentTimestamp = strtotime('+1 month', $currentTimestamp);
					break;

				case 'yearly':
					$fromDatetime = date("Y-m-d 00:00:00", strtotime("first day of January this year", $currentTimestamp));  // -1 year", $currentTimestamp));
					$toDatetime = date("Y-m-d 23:59:59", strtotime("last day of December this year", $currentTimestamp)); // -1 year", $currentTimestamp));
					$aRangeByPeriod = ['from' => $fromDatetime, 'to' => $toDatetime];
					$_fromDatetimeTimestamp = strtotime($fromDatetime);
					$_toDatetimeTimestamp = strtotime($toDatetime);
					if($_fromDatetimeTimestamp <= $_firstTimestamp && $_firstTimestamp <= $_toDatetimeTimestamp){
						$aRangeByPeriod['first'] = $first;
					}
					if($_fromDatetimeTimestamp <= $_lastTimestamp && $_lastTimestamp <= $_toDatetimeTimestamp){
						$aRangeByPeriod['last'] = $last;
					}
					$currentTimestamp = strtotime('+1 year', $currentTimestamp);
					break;
			}

			if(! empty($aRangeByPeriod)){
				$rangeByPeriod[] = $aRangeByPeriod;
			}
		} // EOF while ($currentTimestamp <= $lastTimestamp) {...

		// assign the latest range
		if( ! empty($aRangeByPeriod) && $isIncludeLatestRange){
			switch ($periodType) {
				case 'daily':
					$last_plusOnePeriod = date('Y-m-d H:i:s', strtotime('+1 day', $_lastTimestamp) );
					break;
				case 'weekly':
					$last_plusOnePeriod = date('Y-m-d H:i:s', strtotime('+1 week', $_lastTimestamp) );
					break;
				case 'monthly':
					$last_plusOnePeriod = date('Y-m-d H:i:s', strtotime('+1 month', $_lastTimestamp) );
					break;
				case 'yearly':
					$last_plusOnePeriod = date('Y-m-d H:i:s', strtotime('+1 year', $_lastTimestamp) );
					break;
			}

			$rangeByPeriod_plusOnePeriod = $this->dateRangeByPeriod($first, $last_plusOnePeriod, $periodType, false);
			if( ! empty($rangeByPeriod_plusOnePeriod) ){
				$aRangeByPeriod = $rangeByPeriod_plusOnePeriod[count($rangeByPeriod_plusOnePeriod)-1]; // get latest
				if( isset($aRangeByPeriod['last']) ){
					unset($aRangeByPeriod['last']); // to clear, the last always has in latest one under daily.
				}
				$_fromDatetimeTimestamp = strtotime($aRangeByPeriod['from']);
				$_toDatetimeTimestamp = strtotime($aRangeByPeriod['to']);
				if($_fromDatetimeTimestamp <= $_lastTimestamp && $_lastTimestamp <= $_toDatetimeTimestamp){
					$aRangeByPeriod['last'] = $last;
				}
				if( ! empty($aRangeByPeriod['last']) ){
					$rangeByPeriod[] = $aRangeByPeriod;
				}
			} // EOF if( ! empty($rangeByPeriod_plusOnePeriod) ){

		} // EOF if( ! empty($aRangeByPeriod) && $isIncludeLatestRange){...

		return $rangeByPeriod;
	} // EOF dateRangeByPeriod

	/**
	 * To query with Total_player_game_day::getPlayerTotalBetWinLoss() By Schedule
	 *
	 * @param integer $playerId
	 * @param string $fromDatetime The begin date time, format: YYYY-mm-dd HH:ii:ss, ex: "2012-01-02 12:23:34".
	 * @param string $toDatetime The end date time, format: YYYY-mm-dd HH:ii:ss, ex: "2012-01-02 12:23:34".
	 * @param array $schedule The field,"vipsettingcashbackrule.period_up_down_2" while upgrade checking, The field,"vipsettingcashbackrule.period_down" while downgrade checking.
     * @param bool $enable_multi_currencies_totals
	 * @return array $gameLogData The return array of the total_player_game_day::getPlayerTotalBetWinLoss().
	 */
	public function getPlayerTotalBetWinLossBySchedule( $playerId // #1
                                                    , $fromDatetime // #2
                                                    , $toDatetime // #3
                                                    , $schedule = [] // #4
                                                    , $enable_multi_currencies_totals = false // #5
    ){
		$this->CI->load->model(['total_player_game_day']);
        if( $this->isEnabledMDB() ){
            $this->CI->load->library(array('group_level_lib'));
        }
		$gameLogData = [];

		if( ! empty($schedule) ){
			switch( true ){
				case isset($schedule['daily']):
					$total_player_game_table = 'total_player_game_hour';
					$where_date_field = 'date_hour';
					break;
				default:
				case isset($schedule['weekly']):
					$total_player_game_table = 'total_player_game_day';
					$where_date_field = 'date';
					break;
				case isset($schedule['monthly']):
					$total_player_game_table = 'total_player_game_day';
					$where_date_field = 'date';
					break;
				case isset($schedule['yearly']):
					$total_player_game_table = 'total_player_game_month';
					$where_date_field = 'month';
					break;
			}

			$_fromDatetime = $this->formatDateMinuteForMysql(new DateTime($fromDatetime));
			$_toDatetime = $this->formatDateMinuteForMysql(new DateTime($toDatetime));

            if( $this->isEnabledMDB() && $enable_multi_currencies_totals ){
                $gameLogData = $this->CI->group_level_lib->getPlayerTotalBetWinLossWithForeachMultipleDBWithoutSuper($playerId, $_fromDatetime, $_toDatetime, $total_player_game_table, $where_date_field);
            }else{
                $gameLogData = $this->CI->total_player_game_day->getPlayerTotalBetWinLoss($playerId, $_fromDatetime, $_toDatetime, $total_player_game_table, $where_date_field);
            }
		}

		return $gameLogData;
	}// EOF getPlayerTotalBetWinLossBySchedule

	/**
	 * Get the Formula Info By vip_upgrade_id / vip_downgrade_id
	 *
     * @return array $theFormulaInfo The Formula Information. the format as following,
	 * - $theFormulaInfo['formula'] string The json string for upgrade/downgrade condition.
	 * That is the field, "vip_upgrade_setting.formula" binded the data-table,"vipsettingcashbackrule".
	 * - $theFormulaInfo['formula_json_decoded'] array After json_decode().
	 * - $theFormulaInfo['formula_is_empty'] boolean The result after detected empty.
	 *
	 * - $theFormulaInfo['separate_accumulation_settings'] string The json string for upgrade/downgrade condition.
	 * That is the field, "vip_upgrade_setting.separate_accumulation_settings" binded the data-table,"vipsettingcashbackrule".
	 * - $theFormulaInfo['separate_accumulation_settings_json_decoded'] array After json_decode().
	 * - $theFormulaInfo['separate_accumulation_settings_is_empty'] boolean The result after detected empty.
	 *
	 * - $theFormulaInfo['bet_amount_settings'] string The json string for upgrade/downgrade condition.
	 * That is the field, "vip_upgrade_setting.bet_amount_settings" binded the data-table,"vipsettingcashbackrule".
	 * - $theFormulaInfo['bet_amount_settings_json_decoded'] array After json_decode().
	 * - $theFormulaInfo['bet_amount_settings_is_empty'] boolean The result after detected empty.
	 *
	 * - $theFormulaInfo['period'] string The json string for upgrade/downgrade period.
	 * That is the field, "vipsettingcashbackrule.period_up_down_2"/"vipsettingcashbackrule.period_down" binded the data-table,"vipsettingcashbackrule".
	 * - $theFormulaInfo['period_json_decoded'] array After json_decode().
	 * - $theFormulaInfo['period_is_empty'] boolean The result after detected empty.
	 *
	 */
	public function getFormulaInfoByVipUpgradeId($vip_upgrade_id){
		$this->CI->load->model(['group_level']);
		$theFormulaInfo = [];// defaults
		// vip_upgrade_setting.formula

		/// defaults
		$theFormulaInfo['is_empty'] = null;
		// about formula
		$theFormulaInfo['formula'] = '{}'; // json string
		$theFormulaInfo['formula_json_decoded'] = [];
		$theFormulaInfo['formula_is_empty'] = null; // for prefix, detect is_empty
		// about accumulation
		$theFormulaInfo['accumulation'] = Group_level::ACCUMULATION_MODE_DISABLE;
		// about accumulation, separate or common
		// depend by separate_accumulation_settings_is_empty
		$theFormulaInfo['is_separate_accumulation'] = null;
		$theFormulaInfo['is_common_accumulation'] = null;
		// vip_upgrade_setting.separate_accumulation_settings
		$theFormulaInfo['separate_accumulation_settings'] = '{}'; // json string
		$theFormulaInfo['separate_accumulation_settings_json_decoded'] = [];
		$theFormulaInfo['separate_accumulation_settings_is_empty'] = null;  // for prefix, detect is_empty
		// vip_upgrade_setting.bet_amount_settings
		$theFormulaInfo['bet_amount_settings'] = '{}'; // json string
		$theFormulaInfo['bet_amount_settings_json_decoded'] = [];
		$theFormulaInfo['bet_amount_settings_is_empty'] = null;  // for prefix, detect is_empty

		$vipUpgradeDetails = $this->CI->group_level->getVIPGroupUpgradeDetails($vip_upgrade_id);

		if( empty($vipUpgradeDetails) ){
			// confirm the vip_upgrade_setting data is empty.
			$theFormulaInfo['is_empty'] = true;
		}else{
			$theFormulaInfo['is_empty'] = false;
		}

		if( ! empty($vipUpgradeDetails['formula'])){
			// {"deposit_amount":[">=","1000000"],"operator_2":"and","bet_amount":[">=","100000000"]}
			$theFormulaInfo['formula'] = $vipUpgradeDetails['formula'];
			$json_decoded = $this->json_decode_handleErr($vipUpgradeDetails['formula'], true);
			if( ! empty($json_decoded) ){
				$theFormulaInfo['formula_json_decoded'] = $json_decoded;
				$theFormulaInfo['formula_is_empty'] = false;
			}else{
				$theFormulaInfo['formula_is_empty'] = true;
			}
		}else{
			$theFormulaInfo['formula_is_empty'] = true;
		}
		if( ! empty($vipUpgradeDetails['separate_accumulation_settings'])){
			// {"bet_amount": {"accumulation": "4"}, "deposit_amount": {"accumulation": "0"}}
			$theFormulaInfo['separate_accumulation_settings'] = $vipUpgradeDetails['separate_accumulation_settings'];
			$json_decoded = $this->json_decode_handleErr($vipUpgradeDetails['separate_accumulation_settings'], true);
			if( ! empty($json_decoded) ){
				$theFormulaInfo['separate_accumulation_settings_json_decoded'] = $json_decoded;
				$theFormulaInfo['separate_accumulation_settings_is_empty'] = false;
			}else{
				$theFormulaInfo['separate_accumulation_settings_is_empty'] = true;
			}
		}else{
			$theFormulaInfo['separate_accumulation_settings_is_empty'] = true;
		}

		// about accumulation, separate or common
		// depend by separate_accumulation_settings_is_empty
		$is_separate_accumulation = null;
		$is_common_accumulation = null;
		// for common accumulation Or the default accumulation of the separate accumulation.
		if( isset($vipUpgradeDetails['accumulation']) ){
			$theFormulaInfo['accumulation'] = $vipUpgradeDetails['accumulation'];
		}else{
			// define in defaults
		}
		if($theFormulaInfo['separate_accumulation_settings_is_empty'] == true) {
			$is_separate_accumulation = false;
		}else{
			$is_separate_accumulation = true;
		}
		if($is_separate_accumulation == true){
			$is_common_accumulation = false;
		}else{
			$is_common_accumulation = true;
		}
		$theFormulaInfo['is_separate_accumulation'] = $is_separate_accumulation;
		$theFormulaInfo['is_common_accumulation'] = $is_common_accumulation;

		if( ! empty($vipUpgradeDetails['bet_amount_settings'])){
			// {"itemList": [{"type": "game_type", "value": "6666", "math_sign": ">=", "game_type_id": "1091"}, {"type": "game_platform", "value": "2222", "math_sign": ">=", "game_platform_id": "2134", "precon_logic_flag": "and"}], "defaultItem": {"value": "2222", "math_sign": ">="}}
			$theFormulaInfo['bet_amount_settings'] = $vipUpgradeDetails['bet_amount_settings'];
			$json_decoded = $this->json_decode_handleErr($vipUpgradeDetails['bet_amount_settings'], true);
			if( ! empty($json_decoded) ){
				$theFormulaInfo['bet_amount_settings_json_decoded'] = $json_decoded;
				$theFormulaInfo['bet_amount_settings_is_empty'] = false;
			}else{
				$theFormulaInfo['bet_amount_settings_is_empty'] = true;
			}
		}else{
			$theFormulaInfo['bet_amount_settings_is_empty'] = true;
		}

		if( ! empty($vipUpgradeDetails['period_up_down_2'])){

		}
		if( ! empty($vipUpgradeDetails['period_down'])){

		}


		return $theFormulaInfo;
	}// EOF getFormulaInfoByVipUpgradeId

	/**
	 * Get the Formula information By vipsettingcashbackrule.vipsettingcashbackruleId
	 *
	 * @param integer $vipsettingcashbackruleId The field, "vipsettingcashbackrule.vipsettingcashbackruleId".
	 * @return array $theFormulaInfo The Formula Information. the format as following,
	 * - $theFormulaInfo['upgrade'/'downgrade']['formula'] string The json string for upgrade/downgrade condition.
	 * That is the field, "vip_upgrade_setting.formula" binded the data-table,"vipsettingcashbackrule".
	 * - $theFormulaInfo['upgrade'/'downgrade']['formula_json_decoded'] array After json_decode().
	 * - $theFormulaInfo['upgrade'/'downgrade']['formula_is_empty'] boolean The result after detected empty.
	 *
	 * - $theFormulaInfo['upgrade'/'downgrade']['separate_accumulation_settings'] string The json string for upgrade/downgrade condition.
	 * That is the field, "vip_upgrade_setting.separate_accumulation_settings" binded the data-table,"vipsettingcashbackrule".
	 * - $theFormulaInfo['upgrade'/'downgrade']['separate_accumulation_settings_json_decoded'] array After json_decode().
	 * - $theFormulaInfo['upgrade'/'downgrade']['separate_accumulation_settings_is_empty'] boolean The result after detected empty.
	 *
	 * - $theFormulaInfo['upgrade'/'downgrade']['bet_amount_settings'] string The json string for upgrade/downgrade condition.
	 * That is the field, "vip_upgrade_setting.bet_amount_settings" binded the data-table,"vipsettingcashbackrule".
	 * - $theFormulaInfo['upgrade'/'downgrade']['bet_amount_settings_json_decoded'] array After json_decode().
	 * - $theFormulaInfo['upgrade'/'downgrade']['bet_amount_settings_is_empty'] boolean The result after detected empty.
	 *
	 * - $theFormulaInfo['upgrade'/'downgrade']['period'] string The json string for upgrade/downgrade period.
	 * That is the field, "vipsettingcashbackrule.period_up_down_2"/"vipsettingcashbackrule.period_down" binded the data-table,"vipsettingcashbackrule".
	 * - $theFormulaInfo['upgrade'/'downgrade']['period_json_decoded'] array After json_decode().
	 * - $theFormulaInfo['upgrade'/'downgrade']['period_is_empty'] boolean The result after detected empty.
	 *
	 */
	public function getFormulaInfoByVipsettingcashbackruleId($vipsettingcashbackruleId){
		$this->CI->load->model(['group_level']);
		$theFormulaInfo = [];
		$theFormulaInfo['upgrade'] = []; // for upgrade
		$theFormulaInfo['downgrade'] = []; // for downgrade

		// defaults
		$theFormulaDetail = [];
		// vipsettingcashbackrule.period_up_down_2 Or vipsettingcashbackrule.period_down
		$theFormulaDetail['period'] = '{}'; // json string
		$theFormulaDetail['period_json_decoded'] = [];
		$theFormulaDetail['period_is_empty'] = null; // for prefix, detect is_empty
		// vip_upgrade_setting.formula
		$theFormulaDetail['formula'] = '{}'; // json string
		$theFormulaDetail['formula_json_decoded'] = [];
		$theFormulaDetail['formula_is_empty'] = null; // for prefix, detect is_empty
		// vip_upgrade_setting.separate_accumulation_settings
		$theFormulaDetail['separate_accumulation_settings'] = '{}'; // json string
		$theFormulaDetail['separate_accumulation_settings_json_decoded'] = [];
		$theFormulaDetail['separate_accumulation_settings_is_empty'] = null;  // for prefix, detect is_empty
		// vip_upgrade_setting.bet_amount_settings
		$theFormulaDetail['bet_amount_settings'] = '{}'; // json string
		$theFormulaDetail['bet_amount_settings_json_decoded'] = [];
		$theFormulaDetail['bet_amount_settings_is_empty'] = null;  // for prefix, detect is_empty

		// assign to upgrade/downgrade for defaults
		$theFormulaInfo['upgrade'] = $theFormulaDetail;
		$theFormulaInfo['downgrade'] = $theFormulaDetail;

		$getPlayerCurrentLevelDetails = $this->CI->group_level->getVipGroupLevelDetails($vipsettingcashbackruleId); // vipsettingcashbackrule
		if( ! empty($getPlayerCurrentLevelDetails['period_up_down_2']) ){ // for downgrade
			$theFormulaInfo['upgrade']['period'] = $getPlayerCurrentLevelDetails['period_up_down_2'];
			$json_decoded = $this->json_decode_handleErr($getPlayerCurrentLevelDetails['period_up_down_2'], true);
			if( ! empty($json_decoded) ){
				$theFormulaInfo['upgrade']['period_json_decoded'] = $json_decoded;
			}
			$theFormulaInfo['upgrade']['period_is_empty'] = false;
		}else{
			$theFormulaInfo['upgrade']['period_is_empty'] = true;
		} // EOF if( ! empty($getPlayerCurrentLevelDetails['period_up_down_2']) ){...
		if( ! empty($getPlayerCurrentLevelDetails['period_down']) ){ // for downgrade
			$theFormulaInfo['downgrade']['period'] = $getPlayerCurrentLevelDetails['period_down'];
			$json_decoded = $this->json_decode_handleErr($getPlayerCurrentLevelDetails['period_down'], true);
			if( ! empty($json_decoded) ){
				$theFormulaInfo['downgrade']['period_json_decoded'] = $json_decoded;
			}
			$theFormulaInfo['downgrade']['period_is_empty'] = false;
		} // EOF if( ! empty($getPlayerCurrentLevelDetails['period_down']) ){...

		// for upgrade part
		$theFormulaInfoInUpgrade = [];
		if( ! empty($getPlayerCurrentLevelDetails['vip_upgrade_id']) ){
			$theFormulaInfoInUpgrade = $this->getFormulaInfoByVipUpgradeId($getPlayerCurrentLevelDetails['vip_upgrade_id']);
		}
		if( ! empty($theFormulaInfoInUpgrade) ){
			$theFormulaInfo['upgrade'] = array_merge($theFormulaInfo['upgrade'], $theFormulaInfoInUpgrade);
		}

		// for downgrade part
		$theFormulaInfoInDowngrade= [];
		if( ! empty($getPlayerCurrentLevelDetails['vip_downgrade_id']) ){
			$theFormulaInfoInDowngrade = $this->getFormulaInfoByVipUpgradeId($getPlayerCurrentLevelDetails['vip_downgrade_id']);
		}
		if( ! empty($theFormulaInfoInDowngrade) ){
			$theFormulaInfo['downgrade'] = array_merge($theFormulaInfo['downgrade'], $theFormulaInfoInDowngrade);
		}

		return $theFormulaInfo;
	} // EOF getFormulaInfoByVipsettingcashbackruleId

	public function isValidDateTimeStr($dateTimeStr) {
		return !empty($dateTimeStr) && $dateTimeStr == '0000-00-00 00:00:00';
	}

	const EPSILON = 0.00001;

	/**
	 *
	 * @return int a>b then 1, a=b then 0, a<b then -1
	 *
	 */
	public function compareFloat($a, $b) {
		$a = floatval($a);
		$b = floatval($b);
		if (abs($a - $b) < self::EPSILON) {
			return 0;
		} elseif ($a - $b < 0) {
			return -1;
		} else {
			return 1;
		}
	}

	/**
	 *	Compare size $a and $b .
	 *
	 * @return boolean If true mean met else false.
	 * <code>
	 * $a=1;$sym='<';$b=2; return true;
	 * $a=1;$sym='<';$b=2; return false;
	 * $a=1.0;$sym='=';$b=1; return true;
	 * </code>
	 */
	public function compareResultFloat($a, $sym, $b) {
		$rlt = $this->compareFloat($a, $b);
		if ($sym == '>') {
			return $rlt > 0;
		} elseif ($sym == '<') {
			return $rlt < 0;
		} elseif ($sym == '=') {
			return $rlt == 0;
		} elseif ($sym == '==') {
			return $rlt == 0;
		} elseif ($sym == '>=') {
			return $rlt >= 0;
		} elseif ($sym == '<=') {
			return $rlt <= 0;
		}
	}// EOF compareResultFloat

	public function isZeroFloat($a) {
		return $this->compareResultFloat($a, '=', 0);
	}

	public function compareCurrency($a, $b) {
		$a = $this->roundCurrencyForShow(floatval($a));
		$b = $this->roundCurrencyForShow(floatval($b));
		if (abs($a - $b) < self::EPSILON) {
			return 0;
		} elseif ($a - $b < 0) {
			return -1;
		} else {
			return 1;
		}
	}

	public function compareResultCurrency($a, $sym, $b) {
		$rlt = $this->compareCurrency($a, $b);
		if ($sym == '>') {
			return $rlt > 0;
		} elseif ($sym == '<') {
			return $rlt < 0;
		} elseif ($sym == '=') {
			return $rlt == 0;
		} elseif ($sym == '>=') {
			return $rlt >= 0;
		} elseif ($sym == '<=') {
			return $rlt <= 0;
		}
	}

	public function isAdminSubProject() {
		return $this->CI->config->item('sub_project') == 'admin';
	}

	public function isPlayerSubProject() {
		return $this->CI->config->item('sub_project') == 'player';
	}

	public function isAffSubProject() {
		return $this->CI->config->item('sub_project') == 'aff';
	}

	public function isAgencySubProject() {
		return $this->CI->config->item('sub_project') == 'agency';
	}

	public function safelang($lang_key, $defVal = null) {
		$val = lang($lang_key);
		if (empty($val)) {
			if (!empty($defVal)) {
				$val = $defVal;
			} else {
				$val = $lang_key;
			}
		}

		return $val;
	}

	public function getLockServer() {
		$servers = $this->getConfig('lock_servers');
		$this->CI->load->library('third_party/lock_server', $servers, 'lock_server');

		$this->CI->lock_server->setRetryDelay($this->getConfig('lock_retry_delay'));
		return $this->CI->lock_server;
	}
    public function lockSessFileResource($sessionId, &$lockedKey, $usePrefixMode='common') {
        $usePrefix = Utils::LOCK_SESSION_FILE;
        switch($usePrefixMode){
            default:
            case 'common':
                $usePrefix = Utils::LOCK_SESSION_FILE;
                break;
            case 'read':
                $usePrefix = Utils::LOCK_SESSION_FILE_READ;
                break;
            case 'write':
                $usePrefix = Utils::LOCK_SESSION_FILE_WRITE;
                break;
        }
		return $this->lockResourceBy($sessionId, $usePrefix, $lockedKey);
	}
    //
    public function releaseSessFileResource($sessionId, &$lockedKey, $usePrefixMode='common') {
        $usePrefix = Utils::LOCK_SESSION_FILE;
        switch($usePrefixMode){
            default:
            case 'common':
                $usePrefix = Utils::LOCK_SESSION_FILE;
                break;
            case 'read':
                $usePrefix = Utils::LOCK_SESSION_FILE_READ;
                break;
            case 'write':
                $usePrefix = Utils::LOCK_SESSION_FILE_WRITE;
                break;
        }
		return $this->releaseResourceBy($sessionId, $usePrefix, $lockedKey);
	}

	public function lockPlayerBalanceResource($playerId, &$lockedKey) {
		return $this->lockResourceBy($playerId, Utils::LOCK_ACTION_BALANCE, $lockedKey);
	}

	public function releasePlayerBalanceResource($playerId, &$lockedKey) {
		return $this->releaseResourceBy($playerId, Utils::LOCK_ACTION_BALANCE, $lockedKey);
	}

	public function lockResourceBy($anyId, $action, &$lockedKey, $add_prefix=true) {
		$resourceKey = $this->createLockKey(array($anyId, $action));

		return $this->lockResource($resourceKey, $lockedKey, $add_prefix);
	}

	public function lockResource($resourceKey, &$lockedKey, $add_prefix=true) {
		$this->verbose_log('try lock resource', $resourceKey, $lockedKey);
		//ms
		$timeout = $this->getConfig('app_lock_timeout') * 1000;

		$lock_server = $this->getLockServer();
		$lockedKey = $lock_server->lock($resourceKey, $timeout, $add_prefix);
		$locked = $lockedKey != false;

		if($locked){
			//setup last locked key
			$this->last_locked_key[$resourceKey] = $lockedKey;
		}

		$this->debug_log('resourceKey', $resourceKey, 'locked', $locked, ($locked ? 'success' : 'failed'));
		return $locked;
	}

	public function releaseResourceBy($anyId, $action, &$lockedKey) {
		$resourceKey = $this->createLockKey(array($anyId, $action));

		return $this->releaseResource($resourceKey, $lockedKey);
	}

	public function releaseResource($resourceKey, &$lockedKey) {
		$this->debug_log('try release resource', $resourceKey, $lockedKey);

		$lock_server = $this->getLockServer();

		$released = $lock_server->unlock($lockedKey);

		if($released){
			if(!empty($this->last_locked_key[$resourceKey])){
				$this->last_locked_key[$resourceKey]=null;
				//unset it
				unset($this->last_locked_key[$resourceKey]);
			}
		} else {
			$this->error_log('releaseResource release lock failed', $resourceKey, $lockedKey, $this->last_locked_key);
		}

		return $released;
	}

	public function tryReleaseLastResource(){

		$this->debug_log('tryReleaseLastResource', $this->last_locked_key);

		$all_success = true;
		if(is_array($this->last_locked_key)){
			foreach ($this->last_locked_key as $resourceKey => $lockedKey) {
				$released = $this->releaseResource($resourceKey, $lockedKey);
				if(!$released){
					$all_success = false;
				}
			}
			$this->last_locked_key = [];
		}
		return $all_success;
	}

	public function createLockKey($arr) {
		return implode('-', $arr);
	}

	public function lockActionById($anyId, $action) {
		$lock_it = false;
		if (empty($anyId)) {
			$anyId = '0';
		}
		// if (!empty($anyId)) {
		if (!property_exists($this->CI, 'player_model')) {
			$this->CI->load->model('player_model');
		}
		$trans_key = $this->createLockKey(array($anyId, $action));
		// lock it
		$lock_it = $this->CI->player_model->transGetLock($trans_key);

		$this->debug_log('id', $anyId, 'action', $action, 'lock_it', $lock_it, ($lock_it ? 'success' : 'failed'));
		// }
		return $lock_it;
	}

	public function releaseActionById($anyId, $action) {
		$rlt = false;
		if (empty($anyId)) {
			$anyId = '0';
		}
		// if (!empty($anyId)) {
		if (!property_exists($this->CI, 'player_model')) {
			$this->CI->load->model('player_model');
		}
		$trans_key = $this->createLockKey(array($anyId, $action));
		$rlt = $this->CI->player_model->transReleaseLock($trans_key);

		$this->debug_log('id', $anyId, 'action', $action, 'rlt', $rlt, ($rlt ? 'success' : 'failed'));
		// }
		return $rlt;
	}

	public function checkTargetBalance($player_id, $transfer_from, $amount, &$message = null, &$msg = null, &$chkBalCode = null) {
		$this->CI->load->model(array('wallet_model', 'withdraw_condition'));

		// $transfer_from = $this->input->post('transfer_from');
		// $amount = $this->input->post('amount');
		// $player_id = $this->authentication->getPlayerId();

		if ($this->isEnabledFeature('enabled_auto_check_withdraw_condition')) {
			//check withdraw condition
			$msg = null;
			$unfinished_is_false = true;
			$success = $this->CI->withdraw_condition->autoCheckWithdrawConditionAndMoveBigWallet($player_id, $msg, $transfer_from, $unfinished_is_false);
			$this->debug_log('check withdraw condition result', $msg);

			if (!$success) {
				//failed
				$this->error_log('clearWithdrawConditionAndMoveBigWallet failed', $msg);
				$message = lang('Locked Balance because withdraw condition is not finished');
				$chkBalCode = self::CHKTGTBAL_WITHDRAW_COND_NOT_FINISHED;
				return $success;
			}
		}

		//if main to sub , check total without frozen
		$targetBalance = $this->CI->wallet_model->getTargetTransferBalanceOnMainWallet($player_id, $transfer_from);
		$this->debug_log('check target balance', $player_id, $transfer_from, $targetBalance);

		// $player_account = $this->CI->wallet_model->getPlayerAccountBySubWallet($player_id, $transfer_from);

		$targetTotalBalance = $this->CI->wallet_model->getTargetTotalBalanceOnMainWallet($player_id, $transfer_from);

		if ($this->compareResultCurrency($amount, '>', $targetBalance)) {

			if ($this->compareResultCurrency($amount, '>', $targetTotalBalance)) {
				$message = lang('Do not have enough available balance');
				$chkBalCode = self::CHKTGTBAL_BALANCE_NOT_ENOUGH;

				if($this->isEnabledFeature('cashier_custom_error_message')){

					$this->CI->load->model(['operatorglobalsettings']);

					$errorMsgType = Operatorglobalsettings::NOTIF_NO_ENOUGH_BALANCE;

					#this handles specific err msg if enabled, else show default msg
					if($this->getCashierCustomErrorMessage('transfer_fund_notif',$errorMsgType)){
						$message = $this->composeCustomErrMsg($errorMsgType);
					}

					if ($this->getConfig('enable_customized_cashier_notification_messages')) {
						$message = lang('insufficient_balance_error_message');
					}
				}

				return false;
			} else {
				$message = lang('Do not have enough available balance, please finish required betting amount');
				$chkBalCode = self::CHKTGTBAL_BALANCE_NOT_ENOUGH;
				return false;
			}

		}

		return true;
	}

	public function transferWalletWithoutLock($player_id, $playerName, $gamePlatformId, $transfer_from, $transfer_to,
		$amount, $user_id = null, $walletType = null, $originTransferAmount = null,
		$transfer_secure_id = null, $ignore_promotion_check = false, $reason = null, $is_manual_adjustment = null,
		$withdraw_all_amount=false) {

		$result = array('success' => false);

		$message = null;
		$err_code = null;
		//first DB
		$tranId = $this->CI->wallet_model->transferWalletAmount($gamePlatformId, $player_id, $transfer_from, $transfer_to,
			$amount, null, $walletType, $message, $originTransferAmount, $ignore_promotion_check, $reason , $is_manual_adjustment, $user_id, $err_code);

		if ($tranId) {

			//game API
			$api = $this->loadExternalSystemLibObject($gamePlatformId);
			if ($api) {
				//check account first
				$api->quickCheckAccount($player_id);
				if ($transfer_to == Wallet_model::MAIN_WALLET_ID) {
					if($withdraw_all_amount){
						$result = $api->withdrawAllFromGame($playerName, $amount, $transfer_secure_id);
					}else{
						$result = $api->withdrawFromGame($playerName, $amount, $transfer_secure_id);
					}
				} else if ($transfer_from == Wallet_model::MAIN_WALLET_ID) {
					$result = $api->depositToGame($playerName, $amount, $transfer_secure_id);
				}
			} else {
				$this->utils->debug_log('lost api', $gamePlatformId);
			}

			if($this->isEnabledFeature("cashier_custom_error_message")){
				if(!$result["success"] && isset($result['transfer_status'])){
					if($result['transfer_status'] != 'approved') {
						$message = $this->composeCustomErrMsg($result['reason_id']);
					}
				}
			}

			if ($this->getConfig('set_success_when_transfer_timeout')) {
				//check if timeout
				if (!$result['success'] && @$result['is_timeout']) {
					$this->utils->debug_log('force to success on timeout player_id:' . $player_id, 'result', $result);
					$result['success'] = true;
				}
			}

			$this->debug_log('player_id', $player_id, 'playerName', $playerName, 'result of transfer api', $result, $transfer_from, $transfer_to, $amount, $gamePlatformId);
			if(!$this->utils->getConfig('disable_write_transaction')){
				if (isset($result['external_transaction_id'])) {
					$this->CI->transactions->updateExternalTransactionId($tranId, $result['external_transaction_id']);
				}
			}
			$this->CI->transactions->updateRequestSecureId($tranId, $transfer_secure_id);
			$result['message'] = lang($message);
			$result['transferTransId'] = $tranId;
		} else {
			$this->error_log('transfer failed on db', $gamePlatformId, $playerName, $player_id, $transfer_from, $transfer_to, $amount);
			$api = $this->loadExternalSystemLibObject($gamePlatformId);
			if($api->onlyTransferPositiveInteger() && $amount <= 0){
				$result['success'] = false;
				$result['message'] = lang('notify.110');
			} else{
				$result['success'] = false;
				if ($err_code == Wallet_model::WALLET_NO_ENOUGH_BALANCE) {
					$result['reason_id'] = Abstract_game_api::REASON_NO_ENOUGH_BALANCE;
					$result['message'] = lang('No enough balance');
				} elseif ($err_code == Wallet_model::WALLET_INVALID_TRANSFER_AMOUNT) {
					$result['reason_id'] = Abstract_game_api::REASON_INVALID_TRANSFER_AMOUNT;
					$result['message'] = lang('wallet_invalid_amount');
				} elseif ($err_code == Wallet_model::WALLET_FAILED_TRANSFER) {
					$result['reason_id'] = Abstract_game_api::REASON_NO_ENOUGH_BALANCE;
					$result['message'] = lang('No enough balance');
				} else {
					$result['message'] = lang('notify.51');
					$result['reason_id'] = Abstract_game_api::REASON_UNKNOWN;
				}

			}
		}
		return $result;
	}

	protected function checkIsPlayerExist($player_name, $player_id, $game_platform_id) {

		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);
		$success = false;
		if ($api && $api->isActive()) {

			# CHECK PLAYER IF EXIST
			$player = $api->isPlayerExist($player_name);
			// $this->utils->debug_log('PREPAREGOTOGAME PLAYER: ',$player);
			# IF NOT CREATE PLAYER
			if (isset($player['exists']) && !$player['exists']) {
				//TODO return result
				$player = $this->get_player_info($player_id);
				$this->CI->load->library('salt');
				$decryptedPwd = $this->CI->salt->decrypt($player['password'], $this->getConfig('DESKEY_OG'));
				$api->createPlayer($player['username'], $player['playerId'], $decryptedPwd);
			}
		}
		return $success;
	}

	protected function quickCheckAccount($player_id,$game_platform_id,$api) {
		//check register flag
		$this->CI->load->model(array('game_provider_auth'));
		$rlt = array('success' => true, 'done_create_player' => false);
		if (!$this->CI->game_provider_auth->isRegisterd($player_id,$game_platform_id)) {
			$this->CI->load->library('salt');
			$player = $this->get_player_info($player_id);
			$decryptedPwd = $this->CI->salt->decrypt($player['password'], $this->getConfig('DESKEY_OG'));
			$rlt = $api->createPlayer($player['username'], $player['playerId'], $decryptedPwd);
			if(!isset($rlt['done_create_player'])){
				$rlt['done_create_player'] = false;
			}
			if ($rlt && $rlt['success']) {
				$rlt['done_create_player'] = true;
				$api->updateRegisterFlag($player_id, Abstract_game_api::FLAG_TRUE);
			}
		}

		return $rlt;
	}

	public function transferWallet($player_id, $playerName, $transfer_from, $transfer_to, $amount,
		$user_id = null, $walletType = null, $originTransferAmount = null, $ignore_promotion_check = false,
		$reason = null, $is_manual_adjustment = null, $withdraw_all_amount=false) {
		$this->CI->load->model(array('wallet_model', 'http_request', 'common_token', 'game_logs'));

		// $result = [];

		if (empty($player_id)) {
			$this->error_log('player id is empty', $player_id);
			$result['success'] = false;
			$result['message'] = lang('notify.61');
			$result['code']    = self::XFERWALLET_PLAYER_ID_EMPTY;

			# consider as player not found if player id is empty
			if($this->isEnabledFeature('cashier_custom_error_message')){
				$this->CI->load->model(['operatorglobalsettings']);
				$errorMsgType = Operatorglobalsettings::NOTIF_NOT_FOUND_PLAYER;
				if($this->getCashierCustomErrorMessage('transfer_fund_notif',$errorMsgType)){
					$result['message'] = $this->composeCustomErrMsg($errorMsgType);
				}
			}
			return $result;
		}

		if (empty($playerName)) {
			$playerName = $this->CI->player_model->getUsernameById($player_id);
		}

		// $this->CI->load->model(array('common_token'));
		$token = $this->CI->common_token->getPlayerToken($player_id);
		$this->debug_log('create token first for callback', $player_id);

		$transactionType = null;
		$gamePlatformId = null;
		// $lock_type = null;
		if ($transfer_to != Wallet_model::MAIN_WALLET_ID) {
			$gamePlatformId = $transfer_to;
			$transactionType = Transactions::TRANSFER_TO_SUB_WALLET_FROM_MAIN_WALLET;
			// 	$lock_type = 'main_to_sub';
		} else {
			$gamePlatformId = $transfer_from;
			$transactionType = Transactions::TRANSFER_FROM_SUB_WALLET_TO_MAIN_WALLET;
			// 	$lock_type = 'sub_to_main';
		}

        if($this->utils->isEnabledFeature('enabled_transfer_condition')) {
            if(FALSE !== $playerBlockedInTransferCondition = $this->isPlayerBlockedInTransferCondition($player_id, $transfer_from, $transfer_to)){
                $result['success'] = false;
                $result['message'] = lang('notify.player_donot_allow_transfer_wallet_with_transfer_condition');
                $result['code']    = self::XFERWALLET_XFER_CONDS_ACTIVE;
                $result['details'] = $playerBlockedInTransferCondition;
                return $result;
            }
        }

		// pre-transfer event handler
		$api = $this->loadExternalSystemLibObject($gamePlatformId);
		if ($api) {
			$result = $api->preTransfer($transactionType, $player_id, $playerName, $transfer_from, $transfer_to, $amount, $user_id, $walletType, $originTransferAmount, $ignore_promotion_check, $reason);
			# if not success return error
			if(!@$result['unimplemented']){
				if(!$result['success']){
					$result['message'] = empty($result['message']) ? lang('notify.61') : $result['message'];
					$result['code']    = self::XFERWALLET_PREXFER_FAILED;
					return $result;
				}
			}
		}

		#OGP-3271 (start) - sub wallet function to prevent transferring of funds
		$isPlayerBlockedInGame = $this->isPlayerBlockedInGame($player_id,$gamePlatformId);
		if($isPlayerBlockedInGame){
			$result['success'] = false;
			$result['message'] = lang('notify.107');
			$result['code']    = self::XFERWALLET_PLAYER_BLOCKED_IN_GAME;
			if($this->isEnabledFeature('cashier_custom_error_message')){
				$this->CI->load->model(['operatorglobalsettings']);
				$errorMsgType = Operatorglobalsettings::NOTIF_GAME_ACCOUNT_IS_LOCKED;
				#this handles specific err msg if enabled, else show default msg
				if($this->getCashierCustomErrorMessage('transfer_fund_notif',$errorMsgType)){
					$result['message'] = $this->composeCustomErrMsg($errorMsgType);
				}
			}
			return $result;
		}
		#OGP-3271 (end)

		// $lock_type = self::LOCK_ACTION_BALANCE;

		$result = array('success' => false);

		// $api = $this->loadExternalSystemLibObject($gamePlatformId);

		$checkAcc = $this->quickCheckAccount($player_id,$gamePlatformId,$api);
		if(!$checkAcc['done_create_player']){
			$this->checkIsPlayerExist($playerName, $player_id,$gamePlatformId);
		}

		if($api->onlyTransferPositiveInteger()){
            $this->utils->debug_log('try print positive int on transfer to', $api->onlyTransferPositiveInteger(), $amount);

			$amount=intval($amount);
			# can't withdraw through bank if less than 0. because can't transfer cent
			# set success if amount is zero OGP-7151
		} else {
			// control by extra info round_transfer_amount. value(true|false)
			$amount = $api->formatAmountBeforeTransfer($amount);
		}

		#OGP-4898
        $amount = $api->convertTransactionAmount($amount);

        // check max deposit on auto transfer when launching the game
        if(isset($api->deposit_max_balance_on_auto_transfer) && $api->deposit_max_balance_on_auto_transfer && $transfer_to != Wallet_model::MAIN_WALLET_ID) {
	        $maxTransfer = $api->getTransferMaxAmount();
	        $gameBalance = $api->queryPlayerBalance($playerName);
	        $checkAmount = $amount + $gameBalance['balance'];
	        if($checkAmount > $maxTransfer) {
	        	$depositAmount = $maxTransfer - $gameBalance['balance'];
	        	if($amount > $depositAmount) {
                    $amount = $depositAmount;
                }
            }
        }

        if($amount <= 0) {
            return [
                'success' => true,
                'set_success_if_zero_amount' => true,
                'message' => !empty($result['message']) ? lang('notify.50') . ' ' . $result['message'] : lang('notify.50') ,
                'code' => self::XFERWALLET_SUCCESS_AMOUNT_LE_ZERO
            ];
        }

        $transfer_secure_id = null;
        $requestId = $this->CI->wallet_model->addTransferRequest($player_id, $transfer_from, $transfer_to,
            $amount, $user_id, $transfer_secure_id, $gamePlatformId);
        $respRlt = null;
        $respFileRlt=null;

		$withdrawCheckMsg = null;
		$message = null;
		$self = $this;

		$is_checkTargetBalance = false;
		$disabled_response_results_table_only=$this->utils->getConfig('disabled_response_results_table_only');

		$this->CI->wallet_model->lockAndTransForPlayerBalance($player_id, function () use (
			$self, $player_id, $gamePlatformId, $transfer_from, $transfer_to, $amount, $playerName, $user_id, $disabled_response_results_table_only,
			$walletType, $originTransferAmount, $transfer_secure_id, $transactionType, $ignore_promotion_check, $withdraw_all_amount,
			&$result, &$message, &$withdrawCheckMsg, &$respRlt, &$respFileRlt, $reason, $is_manual_adjustment, &$is_checkTargetBalance) {

			//round main/big wallet first, pause
			// $self->CI->wallet_model->roundMainWallet($player_id);

			// $lock_it = $this->lockActionById($player_id, $lock_type);
			// try {
			$self->debug_log('lockTransferSubwallet', $player_id, $gamePlatformId);

			if ($self->checkTargetBalance($player_id, $transfer_from, $amount, $message, $withdrawCheckMsg, $chkBalCode)) {

				// $this->CI->wallet_model->startTrans();

				$is_checkTargetBalance = true;

				$result = $self->transferWalletWithoutLock($player_id, $playerName, $gamePlatformId,
					$transfer_from, $transfer_to, $amount, $user_id, $walletType, $originTransferAmount,
					$transfer_secure_id, $ignore_promotion_check, $reason, $is_manual_adjustment,
					$withdraw_all_amount);

				if ($result['success']) {
					$self->debug_log('transfer success', 'player_id', $player_id, 'playerName', $playerName, $transfer_from, $transfer_to, $amount);
					// $success = $this->CI->wallet_model->endTransWithSucc();
                    $transfer_successful_msg_with_currency = !empty($this->getConfig('transfer_successful_msg_with_currency')) ? $this->getConfig('transfer_successful_msg_with_currency') : '';
                    if( $this->isEnabledMDB() && !empty($transfer_successful_msg_with_currency) ){
                        $transfer_successful_msg_with_currency = $transfer_successful_msg_with_currency . ' ' .  $amount . ' ';
                    }

					if ($result['success']) {
						$result['success'] = true;

						if(!empty($result['message'])){
                            $result['message'] = $transfer_successful_msg_with_currency . lang('notify.50') . ' ' . $result['message'];
                        }else{
                            $result['message'] = $transfer_successful_msg_with_currency . lang('notify.50');
                        }

					} else {
						$result['success'] = false;
						$result['message'] = lang('notify.61');
						$result['code']	= self::XFERWALLET_GENERAL_FAILURE;
					}
				} else {
					$self->error_log('transfer failed', $result, 'player_id', $player_id, 'playerName', $playerName, $transfer_from, $transfer_to, $amount);

					if (isset($result['response_result_id']) && !empty($result['response_result_id'])) {
						if($disabled_response_results_table_only){
							$respRlt = $self->CI->response_result->readNewResponseById($result['response_result_id']);
							$self->debug_log('load failed response with file', $respRlt);
						}else{
							//read response results
							$respRlt = $self->CI->response_result->getResponseResultById($result['response_result_id']);
							$respFileRlt = $self->CI->response_result->getResponseResultFileByResultId($result['response_result_id']);
							$self->debug_log('load failed response with file', $respRlt);
						}
					}
					//should rollback but keep response result
					// $this->CI->wallet_model->rollbackTrans();

					$result['success'] = false;
					$result['message'] = empty($result['message']) ? lang('notify.61') : $result['message'];
					$result['code']	= self::XFERWALLET_GENERAL_FAILURE;
				}
			} else {
				$result['success'] = false;
				$result['message'] = empty($message) ? lang('notify.61') : $message;
                $result['reason_id'] = Abstract_game_api::REASON_NO_ENOUGH_BALANCE;
                $result['code']	= $chkBalCode;
				// $message = lang('notify.61'); //"transaction failed"
				// $this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);
			}

			return $result['success'];

			// } finally {
			// 	// $rlt = $this->player_model->transReleaseLock($trans_key);
			// 	// release it
			// 	$rlt = $this->releaseActionById($player_id, $lock_type);
			// 	$this->debug_log('releaseTransferSubwallet', $player_id, $gamePlatformId, $lock_type, $rlt);
			// }

		});

		//print wallet to log again
		$this->utils->debug_log('after transfer', $this->CI->wallet_model->readBigWalletFromDB($player_id));

		if($is_checkTargetBalance){

			if ($transactionType == Transactions::TRANSFER_TO_SUB_WALLET_FROM_MAIN_WALLET) {
				// save http_request (cookies, referer, user-agent)
				$self->saveHttpRequest($player_id, Http_request::TYPE_MAIN_WALLET_TO_SUB_WALLET);
			} else if ($transactionType == Transactions::TRANSFER_FROM_SUB_WALLET_TO_MAIN_WALLET) {
				// save http_request (cookies, referer, user-agent)
				$self->saveHttpRequest($player_id, Http_request::TYPE_SUB_WALLET_TO_MAIN_WALLET);
			}
		}

		if (!empty($respRlt)) {
			if($disabled_response_results_table_only){
				//create new response results again
				$result['response_result_id'] = $this->CI->response_result->copyNewResponse($respRlt);
				$this->debug_log('write back new resp', $respRlt);
			}else{
				//create response results again
				$result['response_result_id'] = $this->CI->response_result->copyResult($respRlt);
				$this->CI->response_result->copyResultFile($respFileRlt);
				$this->debug_log('write back result file', $respFileRlt);
			}
		} elseif (isset($result['success']) && !$result['success']) {
			$this->error_log('lost response result', $player_id, $playerName, $transfer_from, $transfer_to, $amount, $user_id);
		}

		$result['extra_message'] = $withdrawCheckMsg;

		$responseResultId = isset($result['response_result_id']) ? $result['response_result_id'] : null;
		$external_transaction_id = isset($result['external_transaction_id']) ? $result['external_transaction_id'] : null;
		$transfer_status = isset($result['transfer_status']) ? $result['transfer_status'] : Abstract_game_api::COMMON_TRANSACTION_STATUS_UNKNOWN;
		$reason_id = isset($result['reason_id']) ? $result['reason_id'] : Abstract_game_api::REASON_UNKNOWN;

		if (@$result['success']) {
			$this->CI->wallet_model->setSuccessToTransferReqeust($requestId, $responseResultId, $external_transaction_id, $transfer_status, $reason_id);
		} else {
			$this->CI->wallet_model->setFailedToTransferReqeust($requestId, $responseResultId, $external_transaction_id, $transfer_status, $reason_id);
		}

		// post-transfer event handler
		// $api = $this->loadExternalSystemLibObject($gamePlatformId);
		if ($api) {
			$api->postTransfer($transactionType, $result, $player_id, $playerName, $transfer_from, $transfer_to, $amount, $user_id, $walletType, $originTransferAmount, $ignore_promotion_check, $reason);
		}

		return $result;
	}

	public function transferWallets($player_id, $playerName, $transfer_from_list, $transfer_to,
		$user_id = null, $walletType = null, $originTransferAmount = null, $ignore_promotion_check = false, $from_transferAllWallet = false) {
		$this->CI->load->model(array('player_model', 'external_system', 'wallet_model'));

		if (!$this->CI->player_model->isEnabledTransfer($player_id)) {
			return;
		}

		$this->debug_log("util transferWallets to wallet [$transfer_to]");

		$transfer_subwallet_results = [];
		$total_transfer_balance = 0;
		$last_error_message = NULL;
		foreach ($transfer_from_list as $gamePlatformId) {
			$transfer_result = [
				'success' => true,
				'message' => NULL,
			];

			//check if source wallet is active
			if (!$this->CI->external_system->isGameApiActive($gamePlatformId) || $this->CI->external_system->isGameApiMaintenance($gamePlatformId) ) {
				$transfer_result = [
					'success' => false,
					'message' => 'The game api is inactive',
				];
				$transfer_subwallet_results[$gamePlatformId] = $transfer_result;

				continue;
			}

			if ($gamePlatformId == $transfer_to) {
				continue;
			}

			#OGP-1760 -- Transfer Issue --
			//check db subwallet amount
			$subwallet = $this->CI->wallet_model->getSubWalletBy($player_id, $gamePlatformId);
			if (empty($subwallet) || $subwallet->totalBalanceAmount <= 0){
				continue;
			}
			#OGP-1760 -- end --

			$api = $this->utils->loadExternalSystemLibObject($gamePlatformId);

			$balance = 0;
			$controller = $this->CI;
			# Lock here, because the balance may have changed after queryPlayerBalance
			# This lock prevents other thread from changing the wallet balance
			$updateBalanceSuccess = $this->CI->wallet_model->lockAndTransForPlayerBalance($player_id, function()
				use ($controller, $api, $playerName, $player_id, &$balance) {

				$result = $api->queryPlayerBalance($playerName);
				if($result['success'] && isset($result['balance'])){
					$balance = $result['balance'];
					$api->updatePlayerSubwalletBalanceWithoutLock($player_id, $balance);
					$controller->utils->debug_log("Query and update player [$player_id] subwallet, balance [$balance]");
					return true;
				}

				$controller->utils->debug_log("Fail: Query and update player [$player_id] subwallet, balance [$balance]");
				return false;
			});

			if (!$updateBalanceSuccess) {
				$transfer_result = [
					'success' => false,
					'message' => 'query balance failed',
				];
				$transfer_subwallet_results[$gamePlatformId] = $transfer_result;

				continue;
			}

			if($api->onlyTransferPositiveInteger()){
				$balance=intval($balance);
			}

			$this->utils->debug_log('try print positive int', $api->onlyTransferPositiveInteger(), $balance);

			//only transfer balance >0
			if ($balance > 0) {
				$balance = $api->convertTransactionAmount($balance);
				// - Round 1 of transferWallet() calls: transfer balances from subwallets to main

				// OGP-17506: Use withdraw_all_amount if called from transferAllWallet()
				// if ($from_transferAllWallet) {
				// 	$this->debug_log(__METHOD__, 'from_transferAllWallet');
				// 	$result = $this->utils->transferWallet($player_id, $playerName, $gamePlatformId, Wallet_model::MAIN_WALLET_ID, $balance, null, null, null, null, null, null, true);
				// }
				// else {
					$result = $this->utils->transferWallet($player_id, $playerName, $gamePlatformId, Wallet_model::MAIN_WALLET_ID, $balance);
				// }

				if (isset($result['success']) && $result['success']) {
					$total_transfer_balance += $balance;
					$transfer_result = [
						'success' => true,
						'message' => 'transfer ' . $playerName . ' from ' . $api->getPlatformCode() . ' balance:' . $balance . ' success',
					];

                    if (!empty($result['transferTransId'])) {
                        $transfer_result['transferTransId'] = $result['transferTransId'];
                    }

					$transfer_subwallet_results[$gamePlatformId] = $transfer_result;
				} else {
					$transfer_result = [
						'success' => false,
						'message' => 'transfer ' . $playerName . ' from ' . $api->getPlatformCode() . ' balance:' . $balance . ' failed',
					];
                    $last_error_message = ((isset($result['message'])) ? $result['message'] :'');
					$transfer_subwallet_results[$gamePlatformId] = $transfer_result;
				}
			} else {
				$transfer_result = [
					'success' => true,
					'message' => 'No balance',
				];
				$transfer_subwallet_results[$gamePlatformId] = $transfer_result;
			}
		}

		$subw_all  = [];
		$subw_fail = [];
		foreach ($transfer_subwallet_results as $subwallet => $subw_res) {
			$subw_all[] = $subwallet;
			if (!$subw_res['success']) {
				$subw_fail[] = $subwallet;
			}
		}

		$summary = [
			'amount_transferred' => $total_transfer_balance ,
			'subwallets'         => $subw_all ,
			'subwallets_fail'    => $subw_fail
		];

		$total_transfer_balance_formatted = $this->utils->formatCurrencyNoSym($total_transfer_balance);

		if ($transfer_to == Wallet_model::MAIN_WALLET_ID) {
			$message = (empty($last_error_message)) ? sprintf(lang('transfer_all_to_main_wallet'), $playerName, $total_transfer_balance_formatted) : $last_error_message;

			$result = [
				'success' => true,
				'message' => $message,
				'wallets' => $transfer_subwallet_results,
				'summary' => $summary
			];

			return $result;
		}

		// $player = $this->get_player_info($player_id);
		// $balance = $player['totalBalanceAmount'];
		$mainWallet=$this->CI->wallet_model->getMainWalletBy($player_id);
		$balance=!empty($mainWallet) ? $mainWallet->totalBalanceAmount : 0;
		if ($balance <= 0) {
			/*$result = [
				'success' => false,
				'message' => lang('Invalid transfer amount'),
				'wallets' => $transfer_subwallet_results,
			];*/
			$this->debug_log('main wallet is empty');
			$result = [
				'success' => true,
				'wallets' => $transfer_subwallet_results,
			];

			return $result;
		}

		if (!$this->CI->external_system->isGameApiActive($transfer_to)) {
			$result = [
				'success' => false,
				'message' => 'The game api is inactive',
				'wallets' => $transfer_subwallet_results,
			];

			return $result;
		}

		$api = $this->utils->loadExternalSystemLibObject($transfer_to);
		$balance = $api->convertTransactionAmount($balance);

		// - Round 2 of transferWallet: transfer balance from main to destination wallet
		// OGP-17506: Use withdraw_all_amount if called from transferAllWallet()
		// if ($from_transferAllWallet) {
		// 	$result = $this->utils->transferWallet($player_id, $playerName, Wallet_model::MAIN_WALLET_ID, $transfer_to, $balance, null, null, null, null, null, null, true);
		// }
		// else {
			$result = $this->utils->transferWallet($player_id, $playerName, Wallet_model::MAIN_WALLET_ID, $transfer_to, $balance);
		// }

		$result['wallets'] = $transfer_subwallet_results;
		$result['summary'] = $summary;

		return $result;
	}

	public function transferAllWallet($player_id, $playerName, $transfer_to,
		$user_id = null, $walletType = null, $originTransferAmount = null, $ignore_promotion_check = false) {

        if($this->utils->getConfig('seamless_main_wallet_reference_enabled')) {
            $game_api = $this->loadExternalSystemLibObject($transfer_to);
            if($game_api->isSeamLessGame()) {
                return ['success' => $this->transferAllSubWalletToMain($player_id, $playerName, $user_id, $walletType, $originTransferAmount, $ignore_promotion_check)];
            }
        }
		$game_system_list = $this->getActiveGameSystemList();
		$game_system_list_exclude_transfer_to = array_filter($game_system_list, function ($game_system_data) use ($transfer_to) {
			return ($game_system_data['id'] == $transfer_to) ? FALSE : TRUE;
		});

		$transfer_from_list = [];
		foreach ($game_system_list_exclude_transfer_to as $game_system_data) {
			$transfer_from_list[] = $game_system_data['id'];
		}

		// if (count($transfer_from_list) <= 0) {
		// 	return [
		// 		'success' => false,
		// 		'message' => 'No sub wallets',
		// 		'wallets' => [],
		// 	];
		// }

		return $this->transferWallets($player_id, $playerName, $transfer_from_list, $transfer_to, $user_id, $walletType, $originTransferAmount, $ignore_promotion_check, 'from_transferAllWallet');
	}

	/**
	 * Detect the Warning Amount, -0.01 in sub-wallet and notify into MM.
	 * @param float|integer $beforeAmount The previous amount.
	 * @param float|integer $afterAmount The amount afterwards.
	 * @param array $specifiedParams The params for notify in MM.
	 * - $specifiedParams['level']
	 * - $specifiedParams['pretext']
	 * - $specifiedParams['title']
	 * - $specifiedParams['message']
	 * @param float|integer $warningAmount The Warning Amount
	 * @param boolean $isDryRun If  it is true,  it means that the notice will send into MM.
	 */
	function detectSmallestNegativeBalanceAndNotifyIntoMM($beforeAmount, $afterAmount, $specifiedParams = [], $warningAmount = -0.01, $isDryRun = false){
		$mmResult = null;
		if(	$afterAmount == $warningAmount || $isDryRun ){
			$isEnable = false;

			$detectSmallestNegativeBalanceAndNotifyIntoMM = $this->getConfig('detectSmallestNegativeBalanceAndNotifyIntoMM');
			if( ! empty($detectSmallestNegativeBalanceAndNotifyIntoMM['enable']) ){
				$isEnable = true;
			}

			$channel = 'PPN002'; /// PPN002, PHP Personal Notification 002
			$level = 'info';
			$pretext = "The sub-wallet has `$warningAmount`.";
			$title = 'theCallTrace';
			if( ! empty($specifiedParams['level']) ){
				$level = $specifiedParams['level'];
			}
			if( ! empty($specifiedParams['pretext']) ){
				$pretext = $specifiedParams['pretext'];
			}
			if( ! empty($specifiedParams['title']) ){
				$title = $specifiedParams['title'];
			}
			if( ! empty($specifiedParams['message']) ){
				$message = $specifiedParams['message'];
			}else{ // default
				$theCallTrace = $this->generateCallTrace();
				$message = '';
				$message .= '```'. PHP_EOL;
				$message .= $theCallTrace. PHP_EOL;
				$message .= '```'. PHP_EOL;
			}

			if($isEnable){
				if( $isDryRun){
					$pretext .= ' #DRY_RUN ';
				}
				$mmResult = $this->utils->sendMessageToMattermostChannel($channel, $level, $title, $message, $pretext);
			}
		}
		return $mmResult;
	} // EOF detectMinimumNegativeBalance

	public function transferAllSubWalletToMain($player_id, $playerName,
			$user_id=null, $walletType=null, $originTransferAmount = null, $ignore_promotion_check = false){

		$success=true;
		$this->CI->load->model(['wallet_model']);

		$bigWallet=$this->CI->wallet_model->getBigWalletByPlayerId($player_id);
		$game_system_list = $this->getActiveGameSystemList();

		$transfer_from_list = [];
		foreach ($game_system_list as $game_system_data) {
			$gamePlatformId=$game_system_data['id'];

            // if(!$this->CI->external_system->isGameApiMaintenance($gamePlatformId)){
            if($this->CI->external_system->isGameApiActive($gamePlatformId) && !$this->CI->external_system->isGameApiMaintenance($gamePlatformId)) {

			if(isset($bigWallet['sub'][$gamePlatformId]) &&
					$bigWallet['sub'][$gamePlatformId]['total_nofrozen']>0){

				$api = $this->utils->loadExternalSystemLibObject($gamePlatformId);

                if($api->isSeamLessGame()) {
                    continue;
                }

				$balance = 0;
				# Lock here, because the balance may have changed after queryPlayerBalance
				# This lock prevents other thread from changing the wallet balance
				$updateBalanceSuccess = $this->CI->wallet_model->lockAndTransForPlayerBalance($player_id, function()
					use ($api, $playerName, $player_id, $bigWallet, $gamePlatformId, &$balance) {

					$result = $api->queryPlayerBalance($playerName);
					if($result['success'] && isset($result['balance'])){
						$balance = $result['balance']; // @todo before
						$api->updatePlayerSubwalletBalanceWithoutLock($player_id, $balance);
					}else{
						$balance = $bigWallet['sub'][$gamePlatformId]['total_nofrozen'];
					}

					return true;
				});

				$this->utils->debug_log("Query and update player balance. UpdateBalanceSuccess: [$updateBalanceSuccess], Balance: [$balance]");

				if($balance>0){
					$result = $this->transferWallet($player_id, $playerName, $gamePlatformId, Wallet_model::MAIN_WALLET_ID, $balance,
						$user_id, $walletType, $originTransferAmount, $ignore_promotion_check);

					if(!$result['success']){
						$success=false;
					}
				}
				// $transfer_from_list[] = $game_system_data['id'];
            }
			}
		}
		// $transfer_to=Wallet_model::MAIN_WALLET_ID;

		// return $this->transferWallets($player_id, $playerName, $transfer_from_list, $transfer_to, $user_id, $walletType, $originTransferAmount, $ignore_promotion_check);

		return $success;
	}

	public function verifyWalletTransfer($player_id, $playerName, $transfer_from, $transfer_to, $amount) {
		$this->CI->load->model(array('player_model', 'external_system', 'transactions', 'wallet_model', 'responsible_gaming'));

        $gamePlatformId = null;
        $lock_type = null;
        if ($transfer_to != Wallet_model::MAIN_WALLET_ID) {
            $gamePlatformId = $transfer_to;
            $lock_type = 'main_to_sub';
        } else {
            $gamePlatformId = $transfer_from;
            $lock_type = 'sub_to_main';
        }

        if ($transfer_to == AGENCY_API) {
            return [
                'status'  => 'error',
                'message' => lang('Transaction Failed! Main Wallet can\'t transfer to Agency Wallet.') ,
                'code'    => Utils::VERWALLETXFER_NOT_TO_AGENCY
            ];
        }

        if(empty($amount) || $amount <= 0){
            return [
                'status'  => 'error',
                'message' => lang('Invalid transfer amount') ,
                'code'    => Utils::VERWALLETXFER_INVALID_AMOUNT
            ];
        }

        if (!$this->CI->external_system->isGameApiActive($gamePlatformId)) {
			if($this->isEnabledFeature('cashier_custom_error_message')){
				$this->CI->load->model(['operatorglobalsettings']);
				$errorMsgType = Operatorglobalsettings::NOTIF_API_MAINTAINING;
				if($this->getCashierCustomErrorMessage('transfer_fund_notif',$errorMsgType)){
					$message = $this->composeCustomErrMsg($errorMsgType);
					return [
							'status'  => 'error',
							'message' => $message ,
							'code'    => Utils::VERWALLETXFER_GAME_NOT_ACTIVE
					];
				}
			}
            return [
                'status'  => 'error',
                'message' => lang('notify.51') , //"transaction failed"
                'code'    => Utils::VERWALLETXFER_GAME_NOT_ACTIVE
            ];
        }

        if($this->CI->external_system->isGameApiMaintenance($gamePlatformId)){
            return [
                'status' => 'error',
                'message' => lang('goto_game.sysMaintenance') ,
                'code'    => Utils::VERWALLETXFER_GAME_MAINTENANCE
            ];
        }

        if ($lock_type == 'sub_to_main' && !$this->CI->player_model->isEnabledTransfer($player_id)) {
            return [
                'status' => 'error',
                'message' => lang('notify.51') , //"transaction failed"
                'code'    => Utils::VERWALLETXFER_PLAYER_XFER_DISABLED
            ];
        }

        $api = $this->loadExternalSystemLibObject($gamePlatformId);

		# testing notification error message
		# applicable for test player only
		if (!empty($api->test_notif_player) && !empty($api->test_notif_error_code)) {
			if($api->test_notif_player == $playerName) {
				if($this->isEnabledFeature('cashier_custom_error_message')){
					$this->CI->load->model(['operatorglobalsettings']);
					if($this->getCashierCustomErrorMessage('transfer_fund_notif',$api->test_notif_error_code)){
						$message = $this->composeCustomErrMsg($api->test_notif_error_code);
						return [
							'status' => 'error',
							'message' => $message ,
							'code'    => Utils::VERWALLETXFER_TEST_PLAYERS_ONLY
						];
					}
				}
			}
		}

        if($this->isEnabledFeature('responsible_gaming')){
            $this->CI->load->library(array('player_responsible_gaming_library'));
            if($transfer_to != Wallet_model::MAIN_WALLET_ID && $this->CI->player_responsible_gaming_library->inWageringLimits($player_id, $amount)){
                return [
                    'status' => 'error',
                    'message' => lang('Wagering Limits Effect, cannot tranfer to game wallet') ,
                    'code'    => Utils::VERWALLETXFER_WAGERING_LIMITS_ACTIVE
                ];
            }
        }


        if ($transfer_from != Wallet_model::MAIN_WALLET_ID && $transfer_to != Wallet_model::MAIN_WALLET_ID) {
            $result = $this->utils->transferWallet($player_id, $playerName, $transfer_from, Wallet_model::MAIN_WALLET_ID, $amount);
            if ($result['success']) {
                $result = $this->utils->transferWallet($player_id, $playerName, Wallet_model::MAIN_WALLET_ID, $transfer_to, $amount);
				if ( !$result['success']) {
					$result['message'] = lang('Failed transfer from subwallet to subwallet');
				}
            }
        } else {
            $result = $this->utils->transferWallet($player_id, $playerName, $transfer_from, $transfer_to, $amount);
        }

        $transferTransId = isset($result['transferTransId']) ? $result['transferTransId'] : null;
        if (!$result['success'] || empty($transferTransId)) {
			$status = !empty($result['set_success_if_zero_amount']) ? 'success' : 'error';
			// $code = isset($result['code']) ? $result['code'] : self::XFERWALLET_GENERAL_FAILURE;
            return [
                'status'  => $status,
                'message' => $result['message'] ,
                'code'    => $result['code']
            ];
        }

        return [
            'status' => 'success',
            'message' => $result['message'],
            'transferTransId' => $transferTransId ,
            'code'    => 0
        ];
	}

	public function existsSubWallet($subwalletId) {
		$list = $this->getAllCurrentGameSystemList();
		return !empty($subwalletId) && in_array($subwalletId, $list);
	}

	public function resUrl($uri) {
		//TODO move to cdn
		$uri = 'resources/' . $uri;
		$uri .= strpos($uri, '?') ? '&' : '?';
		$uri .= 'v=' . PRODUCTION_VERSION;
		return site_url($uri);
	}

	public function loadDatatables($tmpl) {
		// if ($this->isDebugMode()) {

		//jszip
		$tmpl->add_js2($this->resUrl('datatables/JSZip-2.5.0/jszip.min.js'));
		$tmpl->add_js2($this->resUrl('datatables/pdfmake-0.1.18/build/pdfmake.min.js'));
		$tmpl->add_js2($this->resUrl('datatables/pdfmake-0.1.18/build/vfs_fonts.js'));

		//bootstrap
		$tmpl->add_js2($this->resUrl('datatables/DataTables-1.10.10/js/jquery.dataTables.min.js'));
		$tmpl->add_js2($this->resUrl('datatables/DataTables-1.10.10/js/dataTables.bootstrap.min.js'));
		$tmpl->add_css2($this->resUrl('datatables/DataTables-1.10.10/css/dataTables.bootstrap.min.css'));

		//buttons
		$tmpl->add_js2($this->resUrl('datatables/Buttons-1.1.0/js/dataTables.buttons.min.js'));
		$tmpl->add_js2($this->resUrl('datatables/Buttons-1.1.0/js/buttons.bootstrap.min.js'));
		$tmpl->add_js2($this->resUrl('datatables/Buttons-1.1.0/js/buttons.colVis.min.js'));
		$tmpl->add_js2($this->resUrl('datatables/Buttons-1.1.0/js/buttons.flash.min.js'));
		$tmpl->add_js2($this->resUrl('datatables/Buttons-1.1.0/js/buttons.html5.min.js'));
		$tmpl->add_js2($this->resUrl('datatables/Buttons-1.1.0/js/buttons.print.min.js'));
		$tmpl->add_css2($this->resUrl('datatables/Buttons-1.1.0/css/buttons.bootstrap.min.css'));

		//responsive
		$tmpl->add_js2($this->resUrl('datatables/Responsive-2.0.0/js/dataTables.responsive.min.js'));
		$tmpl->add_js2($this->resUrl('datatables/Responsive-2.0.0/js/responsive.bootstrap.min.js'));
		$tmpl->add_css2($this->resUrl('datatables/Responsive-2.0.0/css/responsive.bootstrap.min.css'));

		//scroller
		$tmpl->add_js2($this->resUrl('datatables/Scroller-1.4.0/js/dataTables.scroller.min.js'));
		$tmpl->add_css2($this->resUrl('datatables/Scroller-1.4.0/css/scroller.bootstrap.min.css'));
		// } else {

		// 	$tmpl->add_js2($this->resUrl('datatables/datatables.min.js'));
		// 	$tmpl->add_css2($this->resUrl('datatables/datatables.min.css'));
		// }
	}

    /**
     * @param array $array
     * @param string $filename
     * @param bool $mkdir
     * @return string link
     */
    public function create_csv($array, $filename, $mkdir = FALSE, $token = null, $managementName = null, $actionName = null) {
		$current_url = &get_instance();
		if(is_null($managementName)){
			$managementName = $current_url->router->fetch_class();
		}

		if(is_null($actionName)){
			$actionName = $current_url->router->fetch_method();
		}

    	if(!empty($token)){
    		$this->CI->load->model(['queue_result']);
    		$state = array('processId'=> getmypid());
    	}

    	$BOM = "\xEF\xBB\xBF";

    	$header_data = @$array['header_data'];
    	$data = @$array['data'];
    	$filepath ='';
    	$folder_name = @$array['folder_name'];

    	if($mkdir){
    		$dir=realpath(dirname(__FILE__) . '/../../public/reports').'/' .$folder_name;
    		if (is_dir($dir) == false) {
    			mkdir($dir, 0777);
    		}

    		$filepath = $dir.'/'.$filename . '.csv';
    	} else {
    		$filepath = realpath(dirname(__FILE__) . '/../../public/reports').'/' . $filename . '.csv';
    	}

    	$this->debug_log('create csv file', $filepath);



    	$fp = fopen($filepath, 'w');
    	if ($fp) {

			fwrite($fp, $BOM); // NEW LINE

			if (!empty($header_data)) {
				fputcsv($fp, $header_data, ',', '"');
			}

			$totalCount =  count($data);
			$count_loop=1;
			$percentage_steps = [];
			for ($i=.01; $i <= 10 ; $i +=.01) {
				array_push($percentage_steps, ceil($i/10 * $totalCount));
			}

			if (!empty($data)) {
				foreach ($data as $fields) {

					fputcsv($fp, $fields, ',', '"');
					$this->utils->info_log('utils csv export ',"token", @$token, "loopcount: ", $count_loop, "total rows: ",$totalCount);
					if(!empty($token)){
						if(in_array($count_loop, $percentage_steps)){
							$done = true;
							$rlt=['success'=>false, 'is_export'=>true, 'processMsg'=> lang('Writing').'...',  'written' => $count_loop, 'total_count' => $totalCount, 'progress' => ceil($count_loop/$totalCount * 100)];
							$this->CI->queue_result->updateResultRunning($token, $rlt, $state);
						}
						if(($count_loop) == $totalCount){
							$rlt=['success'=>true, 'is_export'=>true, 'filename'=>$filename.'.csv', 'processMsg'=> lang('Done'),  'written' => $count_loop, 'total_count' => $totalCount, 'progress' => 100];
							$this->CI->queue_result->updateResult($token, $rlt);
						}
					}
					unset($row);
					$count_loop++;
				}
			}
			fclose($fp);
		} else {
			//create report failed
			$this->error_log('create report failed', $filepath);
		}

		$download_link = site_url('/reports/' . $filename . '.csv');

		$this->utils->recordAction($managementName, $actionName, $download_link);

		return $download_link;
	}

    /**
     * @param $funcName
     * @return string
     */
    public function create_csv_filename($funcName){
        $d = new DateTime();
        $filename=$funcName.'_' . $d->format('Y_m_d_H_i_s') . '_' . rand(1, 9999);

        return $filename;
    }

	public function create_excel_multi_sheets($array_multi, $filename, $from_DT_ajax = FALSE, $sheet_titles) {
		require_once dirname(__FILE__) . '/phpexcel/PHPExcel.php';

		$objPHPExcel = new PHPExcel();
		$objPHPExcel->getProperties()->setCreator("Smartbackend");

		$headings = "A,B,C,D,E,F,G,H,I,J,K,L,M,N,O,P,Q,R,S,T,U,V,W,X,Y,Z,AA,AB,AC,AD,AE,AF,AG,AH,AI,AJ,AK,AL,AM,AN,AO,AP,AQ,AR,AS,AT,AU,AV,AW,AX,AY,AZ,BA,BB,BC,BD,BE,BF,BG,BH,BI,BJ,BK,BL,BM,BN,BO,BP,BQ,BR,BS,BT,BU,BV,BW,BX,BY,BZ";
		$excelHeadings = explode(',', $headings);

		$i_sheet = 0;
		foreach ($array_multi as $array) {
			if ($i_sheet > 0) {
				$title = $sheet_titles[$i_sheet];
				$newWorkSheet = new PHPExcel_Worksheet($objPHPExcel, $title);
				$objPHPExcel->addSheet($newWorkSheet);
			}
			$objPHPExcel->setActiveSheetIndex($i_sheet);
			$objPHPExcel->getActiveSheet()->setTitle($sheet_titles[$i_sheet]);
			//$this->debug_log('multi array', $array);

			#IF NOT DATATABLE AJAX
			if ($from_DT_ajax == FALSE) {
				$h = array();
				foreach ($array->result_array() as $row) {
					foreach ($row as $key => $val) {
						if (!in_array($key, $h)) {
							$h[] = $key;
						}
					}
				}

				$headCount = 0;
				foreach ($h as $key) {
					$key = ucwords(str_replace('_', ' ', $key));
					// Example: $objPHPExcel->getActiveSheet()->setCellValue('A1', "Heading1");
					// Example: $objPHPExcel->getActiveSheet()->setCellValue('B1', "Heading2");
					$objPHPExcel->getActiveSheet()->setCellValue($excelHeadings[$headCount] . '1', $key);
					$headCount++;
				}

				$rowCount = 2;
				foreach ($array->result_array() as $row) {
					$colCount = 0;
					foreach ($row as $val) {
						// Example: $objPHPExcel->getActiveSheet()->setCellValue('A'.$count, "col content1");
						// Example: $objPHPExcel->getActiveSheet()->setCellValue('B'.$count, "col content2");
						$objPHPExcel->getActiveSheet()->setCellValue($excelHeadings[$colCount] . $rowCount, $val);
						// Example: $objPHPExcel->getActiveSheet()->setCellValueExplicit('A1', '1234', PHPExcel_Cell_DataType::TYPE_STRING);
						//REf URL: http://stackoverflow.com/questions/32325676/number-stored-as-text-using-phpexcel
						$objPHPExcel->getActiveSheet()->setCellValueExplicit($excelHeadings[$colCount] . $rowCount, $val, PHPExcel_Cell_DataType::TYPE_STRING);
						//will count per column
						$colCount++;
					}
					//will count per row
					$rowCount++;
				}
			} else {
				#FROM DATATABLE AJAX
				$h = $array['header_data'];
				$headCount = 0;
				foreach ($h as $key) {
					$objPHPExcel->getActiveSheet()->setCellValue($excelHeadings[$headCount] . '1', $key);
					$headCount++;
				}

				$rowCount = 2;
				foreach ($array['data'] as $row) {
					$colCount = 0;
					foreach ($row as $val) {
						$objPHPExcel->getActiveSheet()->setCellValue($excelHeadings[$colCount] . $rowCount, $val);
						$objPHPExcel->getActiveSheet()->setCellValueExplicit($excelHeadings[$colCount] . $rowCount, $val, PHPExcel_Cell_DataType::TYPE_STRING);
						$colCount++;
					}
					$rowCount++;
				}
				//$this->debug_log('rowCount colCount', $rowCount, $colCount);
			}

			//Fits the content in cell :Reference url:http://stackoverflow.com/questions/16761897/phpexcel-auto-size-column-width
			$sheet = $objPHPExcel->getActiveSheet();
			$cellIterator = $sheet->getRowIterator()->current()->getCellIterator();
			$cellIterator->setIterateOnlyExistingCells(true);
			/** @var PHPExcel_Cell $cell */
			foreach ($cellIterator as $cell) {
				$sheet->getColumnDimension($cell->getColumn())->setAutoSize(true);
			}

			$i_sheet++;
		}

		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
		$objWriter->setPreCalculateFormulas(false);
		$filepath = APPPATH . '../public/reports/' . $filename;
		$objWriter->save($filepath . '.xls');
		$download_link = site_url('/reports/' . $filename . '.xls');

		if ($from_DT_ajax == FALSE) {
			//header('Content-type: application/vnd.ms-excel');
			header("Content-type:   application/x-msexcel; charset=utf-8");
			header('Content-Disposition: attachment; filename=' . $filename . '.xls');
			$objWriter->save('php://output');
		}
		return $download_link;
	}

	public function create_excel($array, $filename, $from_DT_ajax = FALSE, $callback = null, $isCsv = false) {

		require_once dirname(__FILE__) . '/phpexcel/PHPExcel.php';

		$objPHPExcel = new PHPExcel();

		$objPHPExcel->getProperties()->setCreator("Smartbackend");
		$objPHPExcel->setActiveSheetIndex(0);

		$headings = "A,B,C,D,E,F,G,H,I,J,K,L,M,N,O,P,Q,R,S,T,U,V,W,X,Y,Z,AA,AB,AC,AD,AE,AF,AG,AH,AI,AJ,AK,AL,AM,AN,AO,AP,AQ,AR,AS,AT,AU,AV,AW,AX,AY,AZ,BA,BB,BC,BD,BE,BF,BG,BH,BI,BJ,BK,BL,BM,BN,BO,BP,BQ,BR,BS,BT,BU,BV,BW,BX,BY,BZ";

		$excelHeadings = explode(',', $headings);

		$result_array = $array; //->result_array();
		if ($callback) {
			array_walk($result_array, $callback);
		}

		#IF NOT DATATABLE AJAX
		if ($from_DT_ajax == FALSE) {

			$h = array();

			foreach ($result_array as $row) {
				foreach ($row as $key => $val) {
					if (!in_array($key, $h)) {
						$h[] = $key;
					}
				}
			}

			$headCount = 0;
			foreach ($h as $key) {
				$key = ucwords(str_replace('_', ' ', $key));
				// Example: $objPHPExcel->getActiveSheet()->setCellValue('A1', "Heading1");
				// Example: $objPHPExcel->getActiveSheet()->setCellValue('B1', "Heading2");
				$objPHPExcel->getActiveSheet()->setCellValue($excelHeadings[$headCount] . '1', $key);
				$headCount++;
			}

			$rowCount = 2;
			foreach ($result_array as $row) {
				$colCount = 0;
				// var_dump($row);exit();
				foreach ($row as $val) {
					// var_dump($excelHeadings[$colCount],$colCount,$val);exit();
					// Example: $objPHPExcel->getActiveSheet()->setCellValue('A'.$count, "col content1");
					// Example: $objPHPExcel->getActiveSheet()->setCellValue('B'.$count, "col content2");
					$objPHPExcel->getActiveSheet()->setCellValue($excelHeadings[$colCount] . $rowCount, $val);
					// Example: $objPHPExcel->getActiveSheet()->setCellValueExplicit('A1', '1234', PHPExcel_Cell_DataType::TYPE_STRING);
					//REf URL: http://stackoverflow.com/questions/32325676/number-stored-as-text-using-phpexcel
					$objPHPExcel->getActiveSheet()->setCellValueExplicit($excelHeadings[$colCount] . $rowCount, $val, PHPExcel_Cell_DataType::TYPE_STRING);
					//will count per column
					$colCount++;
				}
				//will count per row
				$rowCount++;
			}

		} else {
			#FROM DATATABLE AJAX
			$h = $array['header_data'];
			$headCount = 0;
			foreach ($h as $key) {
				$objPHPExcel->getActiveSheet()->setCellValue($excelHeadings[$headCount] . '1', $key);
				$headCount++;
			}

			$rowCount = 2;
			foreach ($array['data'] as $row) {
				$colCount = 0;
				foreach ($row as $val) {
					$objPHPExcel->getActiveSheet()->setCellValue($excelHeadings[$colCount] . $rowCount, $val);
					$objPHPExcel->getActiveSheet()->setCellValueExplicit($excelHeadings[$colCount] . $rowCount, $val, PHPExcel_Cell_DataType::TYPE_STRING);
					$colCount++;
				}
				$rowCount++;
			}
		}

		//Fits the content in cell :Reference url:http://stackoverflow.com/questions/16761897/phpexcel-auto-size-column-width
		$sheet = $objPHPExcel->getActiveSheet();
		$cellIterator = $sheet->getRowIterator()->current()->getCellIterator();
		$cellIterator->setIterateOnlyExistingCells(true);
		/** @var PHPExcel_Cell $cell */
		foreach ($cellIterator as $cell) {
			$sheet->getColumnDimension($cell->getColumn())->setAutoSize(true);
		}

		$fileExtension = $isCsv ? '.csv' : '.xls';
		$writer = $isCsv ? 'csv' : 'Excel5';

		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, $writer);

		if ($isCsv) {
			#note this solves the problem in chinese character good in csv but not good when excel program reading csv
			#link: http://stackoverflow.com/questions/32221000/phpexcel-convert-xls-to-csv-with-special-characters
			$objWriter->setUseBOM(true);
		}

		$objWriter->setPreCalculateFormulas(false);
		$filepath = APPPATH . '../public/reports/' . $filename;

		$objWriter->save($filepath . $fileExtension);
		$download_link = site_url('/reports/' . $filename . $fileExtension);
		if ($from_DT_ajax == FALSE && !$isCsv) {
			//header('Content-type: application/vnd.ms-excel');
			header("Content-type:   application/x-msexcel; charset=utf-8 ");
			header('Content-Disposition: attachment; filename=' . $filename . $fileExtension);
			$objWriter->save('php://output');
		}
		return $download_link;
	}

	public function getThisYearMonth() {
		return date('Ym');
	}

    public function getNextYearMonth() {
		$d = new DateTime();
		$d->modify('first day of +1 month');
		return $d->format('Ym');
	}

	public function getLastYearMonth() {
		$d = new DateTime();
		$d->modify('first day of last month');
		return $d->format('Ym');
	}

    public function getYearMonthByDate($date) {
        return date('Ym', strtotime($date));
    }

    public function getPreviousYearMonthByDate($date) {
        return date('Ym', strtotime($date .' first day of last month'));
    }

    public function getNextYearMonthByDate($date) {
        return date('Ym', strtotime($date . 'first day of +1 month'));
    }

	public function getThisMonthRange() {
		return $this->getMonthRange($this->getThisYearMonth());
	}

	public function getThisMonthRangeWithoutTime() {
		return $this->getMonthRange($this->getThisYearMonth(), false);
	}

	/**
	 * @param string yearmonth format: YYYYMM
	 *
	 */
	public function getMonthRange($yearmonth, $withTime = true) {
		$year = substr($yearmonth, 0, 4);
		$month = substr($yearmonth, 4, 2);
		$firstDate = new DateTime();
		$firstDate->setDate($year, $month, 1);
		$firstDate->setTime(0, 0, 0);
		$endDate = new DateTime();
		$endDate->setDate($year, $month, 1);
		$endDate->setTime(23, 59, 59);

		if($withTime){
			return array($firstDate->format('Y-m-d') . ' 00:00:00',
				$endDate->format('Y-m-t') . ' 23:59:59'
			);
		}else{
			return array($firstDate->format('Y-m-d'),
				$endDate->format('Y-m-t'),
			);
		}
	}

	public function getEveryDayBetweenTwoDate($startDate, $endDate) {
		$array = array();
		$interval = new DateInterval('P1D');

		$realEnd = new DateTime($endDate);
		$realEnd->add($interval);

		$period = new DatePeriod(new DateTime($startDate), $interval, $realEnd);

		foreach($period as $date) {
			$array[] = $date->format('Y-m-d');
		}

		return $array;
	}

	public function getLastWeekRange($week_start = null) {
		$week_start = !empty($week_start) ? $week_start : 'sunday';
		$previous_week = strtotime('-1 week +1 day', strtotime($this->utils->getNowForMysql()));

		$startDt = new DateTime();
		$startDt->setTimestamp(strtotime('last ' . $week_start . ' midnight', $previous_week));
		$from_date = $this->utils->formatDateForMysql($startDt).' '.Utils::FIRST_TIME;

		$endDt = new DateTime();
		$endDt->setTimestamp(strtotime('last ' . $week_start . ' +6 day', $previous_week));
		$to_date = $this->utils->formatDateForMysql($endDt).' '.Utils::LAST_TIME;

		return [$from_date, $to_date];
	}

    /**
     *
     * get config item from config file
     *
     * @param $key
     * @return mixed
     */
	public function getConfig($key) {
		/**
		 * override withdrawal verification configuration (use operatorsetting instead of config file)
		 * Note : this only affect withdraw_verification configuration		 *
		 */
		if ($key == 'withdraw_verification') {
			if(!$this->CI->operatorglobalsettings->existsSetting($key)) {
				/** initialize default withdrawal_verification value */
				$this->CI->operatorglobalsettings->insertSetting($key, 'off');
				return 'off';
			} else {
				return $this->getOperatorSetting($key);
			}
		}
		return $this->CI->config->item($key);
	}

	public function debug_sql($sql) {
		if ($this->getConfig('debug_print_sql')) {
			$this->debug_log($sql);
		}
	}

	public function decryptBase64DES($str, $key, $iv, $onlyPrintable = true) {
		$s = $this->decryptDES(base64_decode($str), $key, $iv);
		if ($onlyPrintable) {
			$s = preg_replace('/[^[:print:]]/', '', $s);
		}
		return $s;
	}

	public function decryptDES($strBin, $key, $iv) {
		return @mcrypt_decrypt(MCRYPT_DES, $key, $strBin, MCRYPT_MODE_CBC, $iv);
	}

	public function get_player_info($playerId) {
		$this->CI->load->model(array('player'));
		return $this->CI->player->getPlayerById($playerId);
	}

	public function get_affiliate_info($affId) {
		$this->CI->load->model(array('affiliatemodel'));
		return $this->CI->affiliatemodel->getAffiliateById($affId);
	}

	public function get_main_wallet($playerId) {
		$this->CI->load->model(array('wallet_model'));
		return $this->CI->wallet_model->getMainWalletBalance($playerId);
	}

	public function get_sub_wallet($playerId) {
		$this->CI->load->model(array('wallet_model'));
		return $this->CI->wallet_model->getGroupedSubWalletBy($playerId);
	}

	public function get_player_frozen_amount($playerId) {
		$this->CI->load->model(array('wallet_model'));
		return $this->CI->wallet_model->getPlayerFrozenAmount($playerId);
	}

	public function get_players_betinfo($type, $dateTimeFrom, $dateTimeTo, $gamePlatformId, $gameDescriptionId) {
		$this->CI->load->model(array('game_logs'));
		return $this->CI->game_logs->getGameBetInfo($type, $dateTimeFrom, $dateTimeTo, $gamePlatformId, $gameDescriptionId);
	}

	public function check_if_valid_game_desc_id($gameDescriptionId) {
		$this->CI->load->model(array('game_description_model'));
		return $this->CI->game_description_model->checkIfValidGameDescId($gameDescriptionId);
	}

	public function check_if_valid_game_code($gameCode) {
		$this->CI->load->model(array('game_description_model'));
		return $this->CI->game_description_model->checkIfValidGameCode($gameCode);
	}

	public function add_favorite_game_to_player($gameDescriptionId, $playerId) {
		$this->CI->load->model(array('player_model', 'game_description_model'));
		$gameDesc = $this->CI->game_description_model->getGameDescription($gameDescriptionId);
		if ($gameDesc) {
			// $date = new DateTime();
			$data = array(
				"game_description_id" => $gameDesc->id,
				"game_code" => $gameDesc->game_code,
				"game_platform_id" => $gameDesc->game_platform_id,
				"game_type_id" => $gameDesc->game_type_id,
				"player_id" => $playerId,
				"created_at" => $this->getNowForMysql(),
			);
			return $this->CI->player_model->addFavoriteGameToPlayer($data);
		}
		return false;
	}

	public function remove_favorite_game_from_player($gameDescriptionId, $playerId) {
		$this->CI->load->model(array('game_description_model'));
		return $this->CI->game_description_model->removeFavoriteGameFromPlayer($gameDescriptionId, $playerId);
	}

	public function remove_favorite_game_code_from_player($gameCode, $playerId) {
		$this->CI->load->model(array('game_description_model'));
		return $this->CI->game_description_model->removeFavoriteGameCodeFromPlayer($gameCode, $playerId);
	}

	public function add_favorite_game_code_to_player($gameCode, $playerId) {
		$this->CI->load->model(array('player_model', 'game_description_model'));
		$gameDesc = $this->CI->game_description_model->getGameDescriptionByCode($gameCode);
		if ($gameDesc) {
			// $date = new DateTime();
			$data = array(
				"game_description_id" => $gameDesc->id,
				"game_code" => $gameCode,
				"game_platform_id" => $gameDesc->game_platform_id,
				"game_type_id" => $gameDesc->game_type_id,
				"player_id" => $playerId,
				"created_at" => $this->getNowForMysql(),
			);
			return $this->CI->player_model->addFavoriteGameToPlayer($data);
		}

		return false;
	}

	public function get_player_favorite_games($playerId) {
		$this->CI->load->model(array('game_description_model'));
		return $this->CI->game_description_model->getPlayerFavoriteGames($playerId);
	}

	public function getDateHourSet(\DateTime $d) {
		$start = $d->format('Y-m-d H') . ':00:00';
		$end = $d->format('Y-m-d H') . ':59:59';

		return array($start, $end, $this->formatDateHourForMysql($d));
	}

	public function getNextHour(\DateTime $d) {
		$d = $d->modify('+1 hour');

		return $this->getDateHourSet($d);
	}

	public function getNextTime(\DateTime $d, $change) {
		$dt = clone $d;
		$dt = $dt->modify($change);

		return $dt;
	}

	/**
	 * Get Next time by seconds, always add seconds
	 *
	 * @param datetime $date
	 * @param int $seconds
	 *
	 * @return string $datetime
	 */
	public function getNextTimeBySeconds(\DateTime $date,$seconds){
		$dt = clone $date;

		$datetime = $dt->modify("+".$seconds."seconds");

		return $datetime;
	}

	public function gtAndEqEndTime(\DateTime $d, \DateTime $end) {
		return $this->formatDateHourForMysql($d) >= $this->formatDateHourForMysql($end);
	}

	public function gtEndTime(\DateTime $d, \DateTime $end) {
		return $this->formatDateHourForMysql($d) > $this->formatDateHourForMysql($end);
	}

	public function gtEndHour(\DateTime $d, \DateTime $end) {
		return $this->gtEndTime($d, $end);
		// return $this->formatDateHourForMysql($d) > $this->formatDateHourForMysql($end);
	}

	/**
	 *
	 * loop by start/end
	 *
	 * @param  string/DateTime $start
	 * @param  string/DateTime $end
	 * @param  string $step     format '+1 day' or '+1 hour'
	 * @param  callable $callback
	 * @param  array $lock_info ['lock_type', 'lock_id']
	 * @return bool
	 */
	public function loopDateStartEnd($start, $end, $step, $lock_info, $callback) {

		if (is_string($start)) {
			$start = new \DateTime($start);
		}

		if (is_string($end)) {
			$end = new \DateTime($end);
		}

		$this->CI->load->model(['player_model']);

		$success = false;
		$startDate = $start;
		while (!$this->gtAndEqEndTime($startDate, $end)) {
			$endDate = $this->getNextTime($startDate, $step);

			$from = clone $startDate;
			$to = clone $endDate;

			if ($lock_info) {

				//convert start/end to from/to by step
				$success = $this->CI->player_model->lockAndTrans(
					$lock_info['lock_type'], $lock_info['lock_id'], function ()
					 use ($from, $to, $callback) {
						return $callback($from, $to);
					});
			} else {

				$success = $callback($from, $to);

			}

			if (!$success) {
				$this->utils->error_log('loop date time failed', $from, $to, 'lock_info', $lock_info);
				break;
			}

			$startDate = $endDate;
		}

		return $success;
	}

	/**
	 * format YYYYMMDD
	 *
	 */
	public function convertDayToStartEnd($day) {
		$d = new DateTime();
		$d->setDate(substr($day, 0, 4), substr($day, 4, 2), substr($day, 6, 2));
		$d->setTime(0, 0, 0);
		$start = $this->formatDateTimeForMysql($d);
		$d->setTime(23, 59, 59);
		$end = $this->formatDateTimeForMysql($d);
		return array($start, $end);
	}
	/**
	 * format YYYYMM
	 *
	 */
	public function convertMonthToStartEnd($month) {
		$d = new DateTime();
		$d->setDate(substr($month, 0, 4), substr($month, 4, 2), 1);
		$d->setTime(0, 0, 0);
		$start = $this->formatDateTimeForMysql($d);
		$d->setDate(substr($month, 0, 4), substr($month, 4, 2), $d->format('t'));
		$d->setTime(23, 59, 59);
		$end = $this->formatDateTimeForMysql($d);
		return array($start, $end);
	}
	/**
	 * format YYYY
	 *
	 */
	public function convertYearToStartEnd($year) {
		$d = new DateTime();
		$d->setDate($year, 1, 1);
		$d->setTime(0, 0, 0);
		$start = $this->formatDateTimeForMysql($d);
		$d->setDate($year, 12, 31);
		$d->setTime(23, 59, 59);
		$end = $this->formatDateTimeForMysql($d);
		return array($start, $end);
	}

	/**
	 *
	 * how to use in JS
	 *
	 * _smartbackend_lib.getRegisterPostUrl();
	 * _smartbackend_lib.getCaptchaUrl();
	 *
	 * @param array/object playerInfo
	 *
	 *
	 */
	public function getSmartbackendLib($playerInfo) {
		$player = json_encode($playerInfo);
		// create base url from HOST;
		// $host = @$_SERVER['HTTP_HOST'];
		// $base_url = 'http://' . @$_SERVER['HTTP_HOST'];
		$base_url = rtrim($this->site_url_with_host(''), '/');
		$urls = json_encode(array(
			'base_url' => $base_url,
			'home' => '/iframe_module/iframe_viewCashier',
			'register' => '/iframe_module/iframe_register',
			'registerPost' => '/iframe_module/postRegisterPlayer',
			'captcha' => '/iframe/auth/captcha',
			'login' => '/iframe_module/iframe_login',
			'logout' => '/iframe_module/iframe_logout',
			'player_settings' => '/iframe_module/iframe_playerSettings',
			'make_deposit' => '/iframe_module/iframe_makeDeposit',
			'make_withdraw' => '/iframe_module/iframe_viewWithdraw',
			'bank_details' => '/iframe_module/iframe_bankDetails',
			'player_promo' => '/iframe_module/iframe_myPromo',
			'promos' => '/iframe_module/iframe_promos',
			'change_password' => '/iframe_module/iframe_changePassword',
			'view_report' => '/player_center2/report',
			'player_balance' => '/smartbackend/get_player_balance',
			'top_players' => '/smartbackend/get_top_players',
			'newest_ten_win_players' => '/smartbackend/get_newest_ten_win_players',
			'monthly_top_players' => '/smartbackend/get_monthly_top_players',
		));
		return <<<EOD
    var _smartbackend_lib={
    player: {$player},
    urls: {$urls},

    getPlayerHomeUrl: function(){
    return this.urls.home;
    },
    getRegisterUrl: function(){
    return this.urls.register;
    },
    getRegisterPostUrl: function(){
    return this.urls.registerPost;
    },
    getCaptchaUrl: function(){
    return this.urls.captcha + "?"+ Math.floor((Math.random() * 10000) + 1);
    },
    getLoginUrl: function(){
    return this.urls.login;
    },
    getLogoutUrl: function(){
    return this.urls.logout;
    },
    getPlayerSettingsUrl: function(){
    return this.urls.player_settings;
    },
    getMakeDepositUrl: function(){
    return this.urls.make_deposit;
    },
    getMakeWithdrawUrl: function(){
    return this.urls.make_withdraw;
    },
    getBankDetailsUrl: function(){
    return this.urls.bank_details;
    },
    getPlayerPromoUrl: function(){
    return this.urls.player_promo;
    },
    getPromosUrl: function(){
    return this.urls.promos;
    },
    getChangePasswordUrl: function(){
    return this.urls.change_password;
    },
    getViewReportUrl: function(){
    return this.urls.view_report;
    },
    getTopTenWinPlayersUrl: function(){
    return this.urls.base_url+"/smartbackend/get_top_ten_win_players";
    },
    getNewestTenWinPlayersUrl: function(){
    return this.urls.base_url+"/smartbackend/get_newest_ten_win_players";
    },
    getJsonpUrl: function(){
    return this.urls.base_url+"/smartbackend/get_jsonp";
    },
    getAddFavoriteGameToPlayerUrl: function(){
    return this.urls.base_url+"/smartbackend/add_favorite_game_to_player";
    },
    getAddFavoriteGameCodeToPlayerUrl: function(){
    return this.urls.base_url+"/smartbackend/add_favorite_game_code_to_player";
    },
    getRemoveFavoriteGameToPlayerUrl: function(){
    return this.urls.base_url+"/smartbackend/remove_favorite_game_from_player";
    },
    getRemoveFavoriteGameCodeToPlayerUrl: function(){
    return this.urls.base_url+"/smartbackend/remove_favorite_game_code_from_player";
    },
    getPlayerFavoriteGamesUrl: function(){
    return this.urls.base_url+"/smartbackend/get_player_favorite_games";
    },
    getIsReceivedBonusUrl: function(){
    return this.urls.base_url+"/smartbackend/is_received_bonus";
    },
    getForgotPassword: function(){
    return this.urls.base_url+"/iframe_module/forgot_password";
    },
    getMonthlyTopWinPlayersUrl: function(){
    return this.urls.base_url+"/smartbackend/get_monthly_top_win_players";
    },

    popupIframe: function(url,width){
    require(['render-ui'],function(renderUI){
    renderUI.popupIframe(url,width);
    });
    },
    getGameUrl: function(gamePlatform, gameCode , gameMode, language){

    if(gamePlatform=='pt'){
    gamePlatformUrl = '/goto_ptgame';
    return this.urls.base_url + '/iframe_module' + gamePlatformUrl + '/default' + '/' +gameCode + '/' + gameMode;

    }else if (gamePlatform =='ag') {
    gamePlatformUrl = '/goto_aggame';
    return this.urls.base_url + '/iframe_module' + gamePlatformUrl + '/' +gameCode;

    }else if (gamePlatform =='mg') {
    gamePlatformUrl = '/goto_mggame';
    return this.urls.base_url + '/iframe_module' + gamePlatformUrl + '/' + gameMode + '/' +gameCode;

    }else if (gamePlatform =='nt') {
    gamePlatformUrl = '/goto_ntgame';
    return this.urls.base_url + '/iframe_module' + gamePlatformUrl + '/' +gameCode;

    }else if (gamePlatform =='bbin') {
    gamePlatformUrl = '/goto_bbingame';
    return this.urls.base_url + '/iframe_module' + gamePlatformUrl + '/' +gameCode;

    }else if (gamePlatform =='lb') {
    gamePlatformUrl = '/goto_lbgame';
    return this.urls.base_url + '/iframe_module' + gamePlatformUrl + '/' +gameCode;

    }else if (gamePlatform =='one88') {
    gamePlatformUrl = '/goto_one88game';
    return this.urls.base_url + '/iframe_module' + gamePlatformUrl + '/' +gameCode;

    }else if (gamePlatform =='inteplay') {
    gamePlatformUrl = '/goto_inteplaygame';
    return this.urls.base_url + '/iframe_module' + gamePlatformUrl + '/' +gameCode;

    }else if (gamePlatform =='opus') {
    gamePlatformUrl = '/goto_opus';
    return this.urls.base_url + '/iframe_module' + gamePlatformUrl + '/' +gameCode;

    }else if (gamePlatform =='onesgame') {
    gamePlatformUrl = '/goto_onesgame';
    return this.urls.base_url + '/iframe_module' + gamePlatformUrl + '/' +gameCode;

    }else if (gamePlatform =='gspt') {
    gamePlatformUrl = '/goto_gspt';
    return this.urls.base_url + '/iframe_module' + gamePlatformUrl + '/' +gameCode;

    }else if(gamePlatform=="gp"){
    gamePlatformUrl = '/goto_gameplay';
    return this.urls.base_url + '/iframe_module' + gamePlatformUrl + '/' +gameCode;
    }else{
    return '';
    }

    },
    getBalanceUrl: function(){
    return this.urls.base_url+"/smartbackend/get_player_balance";
    },
    getLogInfoUrl: function(){
    return this.urls.base_url+"/smartbackend/get_log_info";
    },
    getCountRandomBonusUrl: function(){
    return this.urls.base_url+"/smartbackend/count_random_bonus";
    },
    getBalance: function(callback){
    var self=this;
    require(['utils'],function(utils){
    utils.getJSONP(self.getBalanceUrl(),null,function(data){
    utils.safelog(data);
    callback(data);
    });
    })
    },
    getLogInfo: function(callback){
    var self=this;
    require(['utils'],function(utils){
    utils.getJSONP(self.getLogInfoUrl(),null,function(data){
    utils.safelog(data);
    callback(data);
    });
    })
    },
    getNewestTenWinPlayers: function(gameDescriptionId,gameCode,gamePlatformId,callback){
    var self=this;
    require(['utils'],function(utils){
    utils.getJSONP(self.getNewestTenWinPlayersUrl()+'/'+gameDescriptionId+'/'+gameCode+'/'+gamePlatformId,null,function(data){
    utils.safelog(data);
    callback(data);
    });
    })
    },
    getTopTenWinPlayers: function(gameDescriptionId,gameCode,gamePlatformId,callback){
    var self=this;
    require(['utils'],function(utils){
    utils.getJSONP(self.getTopTenWinPlayersUrl()+'/'+gameDescriptionId+'/'+gameCode+'/'+gamePlatformId,null,function(data){
    utils.safelog(data);
    callback(data);
    });
    })
    },
    getMonthlyTopWinPlayers: function(gamePlatformId, resultLimit, yearMonth, callback) {
    var self=this;
    require(['utils'],function(utils){
    utils.getJSONP(self.getMonthlyTopWinPlayersUrl()+'/'+gamePlatformId+'/'+resultLimit+'/'+yearMonth,null,function(data){
    utils.safelog(data);
    callback(data);
    });
    });
    },
    getPlayerFavoriteGames: function(callback){
    var self=this;
    require(['utils'],function(utils){
    utils.getJSONP(self.getPlayerFavoriteGamesUrl(),null,function(data){
    utils.safelog(data);
    callback(data);
    });
    })
    },
    addFavoriteGameToPlayer: function(game_description_id,callback){
    var self=this;
    require(['utils'],function(utils){
    utils.getJSONP(self.getAddFavoriteGameToPlayerUrl()+'/'+game_description_id,null,function(data){
    utils.safelog(data);
    callback(data);
    });
    })
    },
    removeFavoriteGameToPlayer: function(game_description_id,callback){
    var self=this;
    require(['utils'],function(utils){
    utils.getJSONP(self.getRemoveFavoriteGameToPlayerUrl()+'/'+game_description_id,null,function(data){
    utils.safelog(data);
    callback(data);
    });
    })
    },
    addFavoriteGameCodeToPlayer: function(game_code,callback){
    var self=this;
    require(['utils'],function(utils){
    utils.getJSONP(self.getAddFavoriteGameCodeToPlayerUrl()+'/'+game_code,null,function(data){
    utils.safelog(data);
    callback(data);
    });
    })
    },
    removeFavoriteGameCodeToPlayer: function(game_code,callback){
    var self=this;
    require(['utils'],function(utils){
    utils.getJSONP(self.getRemoveFavoriteGameCodeToPlayerUrl()+'/'+game_code,null,function(data){
    utils.safelog(data);
    callback(data);
    });
    });
    },
    isReceivedBonus: function(callback){
    var self=this;
    require(['utils'],function(utils){
    utils.getJSONP(self.getIsReceivedBonusUrl(),null,function(data){
    utils.safelog(data);
    callback(data);
    });
    });
    },
    countRandomBonus: function (promoCategoryId,callback){
    var self=this;
    require(['utils'],function(utils){
    var url=self.getCountRandomBonusUrl();
    if(promoCategoryId){
    url=url+'/'+promoCategoryId;
    }
    utils.getJSONP(url,null,function(data){
    utils.safelog(data);
    callback(data);
    });
    });

    },

    init: function(){
    //init, load success
    if(typeof(callback_after_smartbackend_init)!='undefined'){
    callback_after_smartbackend_init();
    }
    },
    };

    _smartbackend_lib.init();

EOD;

	}

	public function adjustDateTimeStr($dateTimeStr, $adjustStr) {
		$d = new \DateTime($dateTimeStr);
		$d = $this->adjustDateTime($d, $adjustStr);
		return $this->formatDateTimeForMysql($d);
		// $d = $d->modify($adjustStr);
		// return $this->formatDateTimeForMysql($d);
	}

	public function adjustDateTime(DateTime $dateTime, $adjustStr) {
		// $d = new \DateTime($dateTimeStr);
		return $dateTime->modify($adjustStr);
	}

	public function sendToSlack($msg) {
		$url = $this->getConfig('slack_url');
		$user = $this->getConfig('slack_user');
		$channel = $this->getConfig('slack_channel');

		if (!empty($url)) {
			$data = array('payload' => json_encode(array("channel" => $channel, 'username' => $user,
				'text' => '```' . trim(print_r($msg, true)) . '```')));
			$ch = curl_init($url);

			if ($data) {
				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			}

			$result = curl_exec($ch);
			curl_close($ch);
		}
	}

	public function getDisabledGameApi() {
		return $this->getConfig('temp_disabled_game_api');
	}

	public function isDisabledGameApi($systemId) {
		$temp_disabled_game_api = $this->getDisabledGameApi();
		return in_array($systemId, $temp_disabled_game_api);
	}

	public function isEnabledGameApi($systemId) {
		return !$this->isDisabledGameApi($systemId);
	}

	public function getHttpReferer() {
		$http_referer = '';
		if (isset($_SERVER['HTTP_REFERER'])) {
			$http_referer = @$_SERVER['HTTP_REFERER'];
			if (empty($http_referer)) {
				$http_referer = '';
			}
		}

		return $http_referer;
	}

	public function modifyDateTime($dateTimeStr, $modify) {
		if (!empty($modify) && !empty($dateTimeStr)) {
			$d = new DateTime($dateTimeStr);
			$d->modify($modify);
			$dateTimeStr = $this->formatDateTimeForMysql($d);
		}

		return $dateTimeStr;
	}

	public function formatInt($number) {
		$this->initCurrencyConfig();
		$number = intval($number);

		$str = number_format($number, 0, $this->currency_dec_point, $this->currency_thousands_sep);

		return $str;
	}

	public function isEnabledLiveChat() {
		$chat_options = $this->getConfig('live_chat');
		$secret = $chat_options['admin_login_secret'];
		$url = $chat_options['admin_login_url'];

		return !empty($url) && !empty($secret);
	}

	public function generateLiveChatAutoLoginLink($adminUsername, $adminUserId) {
		$this->CI->load->model(array('common_token'));
		// $url = $this->getConfig('live_chat_url');
		// $secret = $this->getConfig('live_chat_secret');
		require_once __DIR__ . '/../libraries/lib_livechat.php';
		$chat_options = $this->getConfig('live_chat');
		$secret = $chat_options['admin_login_secret'];
		$url = $chat_options['admin_login_url'];

		$token = $this->CI->common_token->getAdminUserToken($adminUserId);
		$params = array(
			'l' => $adminUsername,
			't' => time() + 60,
			'a' => 'true',
			'e' => $token,
			'secret_hash' => $secret,
		);

		$this->debug_log('url', $url, 'params', $params);

		return Lib_livechat::getAutoLoginUrl($url, $params, $chat_options['encrypt_key1'], $chat_options['encrypt_key2']);

		// return $url . $this->generateAutoLoginLink($params);
	}

	public function getCurrentLiveChatUsed() {

		return $this->getConfig('live_chat_used');
	}

	public function concatUrl($first, $next) {
		return rtrim($first, '/') . '/' . ltrim($next, '/');
	}

	public function concatArrayToUrl($segmentArr) {
		$url = '';
		foreach ($segmentArr as $item) {
			$url .= '/' . ltrim(rtrim($item, '/'), '/');
		}
		return $url;
	}

	public function isLoadedMonitor() {
		return false;// extension_loaded('newrelic');
	}

	public function setAppNameToMonitor() {
		// if ($this->isLoadedMonitor()) {
		// 	//set domain as app name
		// 	// $url = site_url();
		// 	$url = $this->site_url_with_host();
		// 	if (!empty($url)) {
		// 		$host = parse_url($url, PHP_URL_HOST);
		// 		if (!empty($host)) {
		// 			return newrelic_set_appname($host);
		// 		}
		// 	}
		// }
	}

	public function sendMail($to, $from, $fromName, $subject, $body, $callerType, $caller) {
		try {
			# Detect frequency of email from this caller
			$cacheKey = "$callerType;$caller:LastEmailTime";
			$lastEmailTime = $this->readRedis($cacheKey);
			$emailCooldownTime = $this->CI->config->item('email_cooldown_time');

			if(!empty($emailCooldownTime) && $emailCooldownTime > 0)
			{
				if($lastEmailTime && time() - $lastEmailTime <= $emailCooldownTime) {
					$this->error_log("[$cacheKey] This user is sending email too frequently. Drop email request to [$to] titled [$subject].");
					return null;
				}
			}

			$this->CI->load->library(array('lib_queue'));
			$this->writeRedis($cacheKey, time());
			return $this->CI->lib_queue->addEmailJob($to, $from, $fromName, $subject, $body, $callerType, $caller, null);
		} catch (Exception $e) {
			$this->error_log('send mail error', $e);
		}
	}

	public function getAnalyticCode($type) {
		if ($type == 'player') {
			return $this->getConfig('player_analytic_code');
		} else if ($type == 'admin') {
			return $this->getConfig('admin_analytic_code');
		} else if ($type == 'aff') {
			return $this->getConfig('aff_analytic_code');
		} else if ($type == 'agency') {
			return $this->getConfig('agency_analytic_code');
		}
		return "";
	}

	const FROM_TYPE_PLAYER_REG_DATE = 'player_reg_date';
	const FROM_TYPE_LAST_WITHDRAW = 'last_withdraw';
	const FROM_TYPE_LAST_SAME_PROMO = 'last_same_promo';
	const FROM_TYPE_LAST_TRANSFER = 'last_transfer';

	public function getLastFromDatetime($from_type_arr, $playerId, $promorulesId) {
		//search
		$from_datetime = null;
		if (!empty($from_type_arr)) {
			$this->CI->load->model(array('player_model', 'transactions', 'player_promo'));
			foreach ($from_type_arr as $from_type) {
				switch ($from_type) {
				case self::FROM_TYPE_PLAYER_REG_DATE:
					$d = $this->CI->player_model->getPlayerRegisterDate($playerId);
					$this->debug_log('from_type', $from_type, 'd', $d);
					if (empty($from_datetime) || ($d && $d > $from_datetime)) {
						$from_datetime = $d;
					}
					break;
				case self::FROM_TYPE_LAST_WITHDRAW:
					$d = $this->CI->transactions->getLastWithdrawDatetime($playerId);
					$this->debug_log('from_type', $from_type, 'd', $d);
					if (empty($from_datetime) || ($d && $d > $from_datetime)) {
						$from_datetime = $d;
					}
					break;
				case self::FROM_TYPE_LAST_SAME_PROMO:
					$d = $this->CI->player_promo->getLastApprovedPromoDatetime($playerId, $promorulesId);
					$this->debug_log('from_type', $from_type, 'd', $d);
					if (empty($from_datetime) || ($d && $d > $from_datetime)) {
						$from_datetime = $d;
					}
					break;
				case self::FROM_TYPE_LAST_TRANSFER:
					$d = $this->CI->transactions->getLastTransferDatetime($playerId);
					$this->debug_log('from_type', $from_type, 'd', $d);
					if (empty($from_datetime) || ($d && $d > $from_datetime)) {
						$from_datetime = $d;
					}
					break;
				}

				// if ($from_datetime) {
				// 	break;
				// }
			}
		}
		return $from_datetime;
	}

	public function getHttpHost() {
		$host = null;
		if (isset($_SERVER['HTTP_HOST'])) {
			$host = @$_SERVER['HTTP_HOST'];
		}
		if (empty($host)) {
			$host = 'localhost';
		}
		return $host;
	}

	public function notExistHttp($url) {
		return substr($url, 0, 4) != 'http';
	}

	public function paddingHost($url) {
		if ($this->notExistHttp($url)) {
			$url = '//' . $this->getHttpHost() . '/' . ltrim($url, '/');
		}
		return $url;
	}

	public function getBaseUrlWithHost() {
		$baseUrl = $this->getConfig('base_url');
		if (substr($baseUrl, 0, 4) != 'http') {
			$baseUrl = 'http://' . @$_SERVER['HTTP_HOST'] . $baseUrl;
		}
		$this->debug_log('base url', $baseUrl);
		return $baseUrl;
	}

	public function site_url_with_host($uri = '') {
		return $this->paddingHost(site_url($uri));
	}

	public function paddingHostHttp($url, $host = '') {
		if ($this->notExistHttp($url)) {
			if ($this->getConfig('always_https')) {
				$http = 'https';
			} else {
				$http = 'http';
			}
			$url = $http . '://' . ($host ?: $this->getHttpHost()) . '/' . ltrim($url, '/');
		}
		return $url;
	}

	public function site_url_with_http($uri = '', $host = '') {
		return $this->paddingHostHttp(
			$host ? $uri : site_url($uri), # if a host is specified, do not use site_url to add host
			$host
		);
	}

	public function createPromoDetailButton($promoRuleId, $promoName, $removeHyperlink = false) {
		$showPromoRuleDetailsText = lang("cms.showPromoRuleDetails");
		$html = <<<EOD
    <a href='###' data-toggle='modal' data-target='#promoDetails' class="hide_close_btn" onclick='return viewPromoRuleDetails($promoRuleId)'>
        <span data-toggle='tooltip' data-original-title='$showPromoRuleDetailsText' data-placement="right">
            $promoName
        </span>
    </a>
EOD;
		$html4NoHyperlink = $promoName;

		if($removeHyperlink){
			$html = $html4NoHyperlink;
		}
		return $html;
	}

	public function checkBlockCountry() {
		$blocked_country_for_admin = $this->getConfig('blocked_country_for_admin');
		if (!empty($blocked_country_for_admin)) {
			$ip = $this->getIP();
			list($city, $country) = $this->getIpCityAndCountry($ip);
			$this->debug_log('ip', $ip, 'country', $country);
			if (in_array($country, $blocked_country_for_admin)) {
				show_error('Not Found', 404);
				return false;
			}
		}
		return true;
	}

	public function checkAffBlockIp() {
		$blocked_ip_for_aff = $this->getConfig('blocked_ip_for_aff');
		if (!empty($blocked_ip_for_aff)) {
			$ip = $this->getIP();
			list($city, $country) = $this->getIpCityAndCountry($ip);
			$this->debug_log('ip', $ip, 'country', $country);
			if (in_array($ip, $blocked_ip_for_aff)) {
				show_error('No permission', 403);
				return false;
			}
		}
		return true;
	}

	public function isEnabledDebugBar() {
		return false;
		// return $this->getConfig('enable_debugbar');
	}

	public function initDebugBarData() {
		// if ($this->isEnabledDebugBar()) {

		// $this->loadComposerLib();
		// $debugbar = new \DebugBar\StandardDebugBar();
		// $debugbarRenderer = $debugbar->getJavascriptRenderer();
		// $pid = strval(getmypid());
		// $this->debugbarMap[$pid] = $debugbar;
		// return $debugbar;
		// }
		return null;
	}

	public function getDebugBar() {
		// if ($this->isEnabledDebugBar()) {

		// $pid = strval(getmypid());
		// $debugbar = isset($this->debugbarMap[$pid]) ? @$this->debugbarMap[$pid] : null;
		// if (empty($debugbar)) {
		// 	$debugbar = $this->initDebugBarData();
		// }
		// return $debugbar;
		// }
		return null;
	}

	public function getDebugBarRender() {
		// if ($this->isEnabledDebugBar()) {
		// $debugbar = $this->getDebugBar();
		// if ($debugbar) {
		// 	$debugbarRenderer = $debugbar->getJavascriptRenderer();
		// 	return $debugbarRenderer;
		// }
		// }
		return null;
	}

	public function printDebugBarHead($debugbarRenderer) {
		// if ($this->isEnabledDebugBar()) {
		// $debugbar = $this->getDebugBar();
		// if ($debugbar) {
		// $debugbarRenderer = $debugbar->getJavascriptRenderer();
		// return $debugbarRenderer->renderHead();
		// }
		// }
		return null;
	}

	public function printDebugBar($debugbarRenderer) {
		// if ($this->isEnabledDebugBar()) {
		// $debugbar = $this->getDebugBar();
		// if ($debugbar) {
		// $debugbarRenderer = $debugbar->getJavascriptRenderer();
		//print session
		// $this->CI->load->library('session');
		// $this->debug_log($this->CI->session->all_userdata());
		// return $debugbarRenderer->render();
		// }
		// }
		return null;
	}

	public function addToDebugBar($msg) {
		// if ($this->isEnabledDebugBar()) {
		// 	$debugbar = $this->getDebugBar();
		// 	$debugbar["messages"]->addMessage($msg);
		// }
		// if ($this->isEnabledClockwork()) {
			// $this->CI->clockwork->startEvent('debug', 'print message');
			// $this->eventDebug($msg);
			// $this->CI->clockwork->endEvent('debug');
		// }
	}

	public function sendDebugbarDataInHeaders() {
		// if ($this->isEnabledDebugBar()) {
		// $debugbar = $this->getDebugBar();
		// $debugbar->sendDataInHeaders();
		// }
	}

	public function recordFullIP() {
		// $this->debug_log('recordFullIP', $this->getConfig('record_full_ip'));
		if ($this->getConfig('record_full_ip')) {
			$ips = '';
			if (isset($_SERVER['REMOTE_ADDR'])) {
				$ips .= 'REMOTE_ADDR:' . $_SERVER['REMOTE_ADDR'];
			}

			$this->CI->load->library(array('session'));

			$user_id = $this->CI->session->userdata('user_id');
			$player_id = $this->CI->session->userdata('player_id');
			$affiliateId = $this->CI->session->userdata('affiliateId');
			// $ips .= ' , user_id:' . $user_id . ' , player_id:' . $player_id . ' , affiliateId:' . $affiliateId;

			$headers = CI_Input::DEFAULT_IP_HEADERS;
			foreach ($headers as $header) {
				if (isset($_SERVER[$header]) && !empty($_SERVER[$header])) {
					$ips .= ' , ' . $header . ':' . $_SERVER[$header];
				}
			}

			// $this->debug_log('domain', $this->getHttpHost(), 'url', current_url(), 'fullip', $ips);
			raw_debug_log('domain', $this->getHttpHost(), 'url', current_url(),
				'user_id', $user_id, 'player_id', $player_id, 'affiliateId', $affiliateId, 'fullip', $ips);
		}
	}

	public function isSecurityBrowser() {
		$headers = $this->CI->input->request_headers();
		$this->debug_log('headers', $headers);

		if (isset($headers['HTTP_X_SS_PC']) || isset($headers['HTTP_X_SS_IOS']) || isset($headers['HTTP_X_SS_ANDROID'])) {
			return true;
		}

		return false;
	}

	public function unserializeSession($data) {

		return $this->decodeJson($data);

		// $data = @unserialize(strip_slashes($data));
		// array_walk_recursive($data, 'returnSlashes');
		// return $data;
	}

	public function isHidePlayerContactOnAgency() {
		// return !$this->isEnabledFeature('show_player_contact_on_agency');
	}

	public function isHidePlayerContactOnAff() {
		return !$this->isEnabledFeature('show_player_contact_on_aff');
	}

	public function isHidePlayerInfoOnAff() {
		return !$this->isEnabledFeature('show_player_info_on_affiliate');
	}

	public function formatDebugMessage($value) {

		return formatDebugMessage($value);

		// if (is_object($value)) {
		// 	if ($value instanceof \DateTime) {
		// 		//print date time
		// 		$str = $value->format(\DateTime::ISO8601);
		// 	} else if ($value instanceof \CI_DB_result) {
		// 		$str = $this->CI->db->last_query();
		// 	} else if ($value instanceof \SimpleXMLElement) {
		// 		$str = $value->asXML();
		// 	} else if (method_exists($value, '__toString')) {
		// 		$str = $value->__toString();
		// 	} else if (method_exists($value, 'toString')) {
		// 		$str = $value->toString();
		// 	} else {
		// 		$str = json_encode((array) $value, JSON_PRETTY_PRINT);
		// 	}
		// } else if (is_array($value)) {
		// 	$str = json_encode($value, JSON_PRETTY_PRINT);
		// } else if (is_null($value)) {
		// 	$str = '(NULL)';
		// } else if (is_bool($value)) {
		// 	$str = $value ? 'true' : 'false';
		// } else {
		// 	$str = $value;
		// }

		// return $str;
	}

	public function buildDebugMessage($args, $functions, $title = 'APP', $fullStack = false, $addHeader = true) {

		// $functions = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
		// $functions = array_reverse($functions);
		if (empty($functions)) {
			$functions = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
		}

		$msg = '';

		if ($addHeader) {
			$subtitle = getSubTitleFromBacktrace($functions);

			$env = $this->getConfig('RUNTIME_ENVIRONMENT');
			$host = $this->getCallHost();

			// $functions = debug_backtrace();
			// if (count($functions) > 1) {
			// 	$last = $functions[1];
			// 	$subtitle = @$last['class'] . '.' . @$last['function'];
			// 	if (isset($last['line'])) {
			// 		$subtitle .= ':' . @$last['line'];
			// 	}
			// }
			$msg .= "[" . $env . "] [" . $host . "] [" . $title . "] [" . getmypid() . "] [";
			if (!empty($subtitle)) {
				$msg = $msg . $subtitle . '] [';
			}
		}
		foreach ($args as $key => $value) {
			$str = $this->formatDebugMessage($value);

			$msg .= $key . ": " . $str . ", ";
		}

		if ($addHeader) {
			$msg .= ' ]';
		}

		if ($fullStack) {
			$msg .= "\nStack:\n";
			foreach ($functions as $call) {
				if (empty($call['file']) && !empty($call['class'])) {
					$msg .= $call['class'] . "->" . $call['function'] . "\n";
				} else if (!empty($call['file'])) {
					$funcName = "";
					if (isset($call['function'])) {
						$funcName = '@' . @$call['function'];
					}
					$msg .= str_replace(array('/home/vagrant/Code/og', 'admin/application', 'player/application', 'aff/application'), array('..', '..', '..', '..'), $call['file']) . ":" . $call['line'] . $funcName . "\n";
				}
			}
		}

		return $msg;
	}

	public function getLogPublisher() {
	}

	public function getCallHost() {
		$host = 'CMD';

		$url = $this->site_url_with_host();
		if (!empty($url)) {
			$host = parse_url($url, PHP_URL_HOST);
			if (empty($host)) {
				$host = 'CMD';
			}
		}

		return $host;
	}

	public function error_log() {
		if ($this->getConfig('log_threshold') == 0) {
			//ignore log by setting
			return '';
		}

		$args = func_get_args();
		if (count($args) <= 0) {
			return '';
		}

		$functions = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
		$returnMsg = $this->buildDebugMessage($args, $functions, 'APP', true, true);

		$msg = $this->formatDebugMessage(array_shift($args));

		$args['stack'] = $functions;

		$level = 'error';

		static $_log;

		$_log = &load_class('Log');
		$_log->write_log($level, $msg, $args);

        unset($functions);
        unset($args);
		return $msg;
    }

    /**
     *  send error message to elasticsearch
     *
     *  @param  string error level
     *  @return void
     */
    private function sendToElasticsearch($level, $msg, $returnMsg, $req_id) {
    }

    /**
     *  using a separate index for network connection errors
     *
     *  @param  string level
     *  @param  string message
     *  @param  string return message including call stack
     *  @return bool true if the error is network connection error
     */
    private function logConnectionError($level, $msg, $returnMsg) {
        if(strpos($msg, 'call api error') !== false
            && strpos($returnMsg, "Can't complete SOCKS5 connection") !== false){
            return true;
        }
        if(strpos($msg, 'call api error') !== false
            && strpos($returnMsg, 'Operation timed out after') !== false) {
            return true;
        }
    }

    /**
     *  filter error log message sent to elasticsearch
     *
     *  @param  string error level
     *  @param  string error message
     *  @param  string error detailed error message include call stack
     *  @return bool true if filtered and not to be sent to elasticsearch
     */
    private function esLogFiltered($level, $msg, $returnMsg) {
        return true;
    }

	public function info_log() {
		if ($this->getConfig('log_threshold') == 0) {
			//ignore log by setting
			return '';
		}

		$args = func_get_args();

		if (count($args) <= 0) {
			return '';
		}

		$msg = $this->formatDebugMessage(array_shift($args));

		$level = 'info';

		static $_log;

		$_log = &load_class('Log');
		$_log->write_log($level, $msg, $args);

		// log_message('info', $msg, $args);
		if ($this->getConfig('print_log_to_console') && $this->CI->input->is_cli_request()) {
			$this->initCliOutput();
			$cliMsg=json_encode([
				'message'=>$msg,
				'context'=>$args,
				'level'=>Monolog\Logger::INFO,
				'level_name'=>'INFO',
				'channel'=>$_log->getHostname(),
				'datetime'=>date('c'),
				'trace'=>getSubTitleFromBacktrace(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)),
				'extra'=>[
					'tags'=>[
						'request_id' =>$_log->request_id,
						'env' => $this->getConfig('RUNTIME_ENVIRONMENT'),
						'version'=>PRODUCTION_VERSION,
						'hostname'=>$_log->getHostname(),
					],
					'process_id'=>getmypid(),
					'memory_peak_usage'=>$this->formatBytes(memory_get_peak_usage(true)),
					'memory_usage'=>$this->formatBytes(memory_get_usage(true)),
				]
			], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
			$this->climate->lightYellow($cliMsg);
			unset($cliMsg);
		}

		unset($args);

		return $msg;
	}

	public function initCliOutput(){
		if(empty($this->climate)){
			$this->climate = new League\CLImate\CLImate;
		}
	}

    /**
     * Formats bytes into a human readable string
     *
     * @param  int        $bytes
     * @return string Formatted string
     */
    public function formatBytes($bytes){
        $bytes = (int) $bytes;

        if ($bytes > 1024 * 1024) {
            return round($bytes / 1024 / 1024, 2).' MB';
        } elseif ($bytes > 1024) {
            return round($bytes / 1024, 2).' KB';
        }

        return $bytes . ' B';
    }

	/**
	 * Validate Datetime string
	 * Reference to https://stackoverflow.com/a/47151635
	 *
	 * @param string $date The datetime string
	 * @param string $format the datetime format string.
	 * @return boolean Return true for valided date string else invalided.
	 */
	public function validateDate($date, $format = 'Y-m-d H:i:s')
	{
		$d = DateTime::createFromFormat($format, $date);
		return $d && $d->format($format) == $date;
	}

	/**
	 * Let system idle for a few seconds
	 *
	 * @param integer $idleTotalSec
	 * @return void
	 */
	public function idleSec($idleTotalSec = 180){
		set_time_limit(0);
		$loopIndex = 0;
		while($loopIndex < $idleTotalSec){
			$loopIndex++;
			sleep(1);
		}
	} // EOF idleSec

	/**
	 * Check the tasks is running in list?
	 * for example, "batch_player_level_upgrade" and "checkReferral" cron jobs:
	 * cli, ps aux|grep -E "\bbatch_player_level_upgrade\b|\bcheckReferral\b"
	 *
	 * Reference to https://www.thegeekstuff.com/2011/01/advanced-regular-expressions-in-grep-command-with-10-examples-%E2%80%93-part-ii/
	 *
	 * @param array $grepStrList The functions of Command class in file,"admin/application/controllers/cli/command.php". for detect ps.
	 * @param string $preStr The pe-string for make sure running a cronjob per client. usually with oghome. ex, "/home/vagrant/Code/{CLIENT_STRING}/og_sync"
	 * @param callable  $isExecingCB {
	 * 	@param array $match
	 *  @return boolean will be return of isExecingListWithPS().
	 * } The rule for check isExecing value.
	 * @param string $currPS To catch for debug and confirm work.
	 * @param array $match To catch for debug and confirm work.
	 * @return boolean true means running in bg else false.
	 */
	function isExecingListWithPS($grepStrList=[], $preStr = '', $isExecingCB = null, &$currPS = null, &$match = null){
		$isExecing = false;

		$grepList = [];
		if( ! empty($grepStrList) ){
			foreach($grepStrList as $indexNumber => $grepStr ){
				$grepList[] = sprintf('\b%s\b',$grepStr);
			}
			$grepImploded = implode('|', $grepList);
			$cmd ='ps aux|grep -E "'. $grepImploded.'" ';
			$cmd .= '|grep -v -e "chmod a" '; // filted "chmod a+w /home/vagr..."
		}else{
			$cmd ='ps aux';
		}

		// Example #2 popen() example, Ref. to https://www.php.net/popen
		$handle = popen($cmd.' 2>&1', 'r');
		$read = '';
		// Example #3 Remote fread() examples in the url,"https://www.php.net/manual/en/function.fread.php".
		while (!feof($handle)) {
			$read .= fread($handle, 1024);
		}
		pclose($handle);
		$currPS = $read; // catch for debug
		$grepStr = '.*';
		$pattern = '~'. $preStr. '.*cli/command/'. $grepStr. '~';
		preg_match_all($pattern, $read, $match);
// $theCallTrace = $this->generateCallTrace();
// $this->debug_log(__METHOD__, '$theCallTrace', $theCallTrace);
// $this->debug_log(__METHOD__, '$cmd', $cmd, '$currPS', $currPS, '$match', $match);
		if( !empty($match) ){
			if( is_null($isExecingCB) ){
				$isExecingCB = function ($match) {
					$isExecing = false;
					if(count($match[0]) > 0){ // if has data ,should be same func bg ps.
						$isExecing = true;
					}
					return $isExecing;
				}; // EOF $isExecingCB
			}

// $theCallTrace = $this->generateCallTrace();
// $this->debug_log(__METHOD__, '$theCallTrace', $theCallTrace);
			$isExecing = call_user_func_array($isExecingCB, [$match]);
		} //EOF if( !empty($match) ){...
// $theCallTrace = $this->generateCallTrace();
// $this->debug_log(__METHOD__, '$theCallTrace', $theCallTrace);
// $this->debug_log(__METHOD__, '$currPS', $currPS, '$match', $match);

		return $isExecing;
	} // EOF isExecingListWithPS()

	/**
	 * Detect the related tasks in PS and waiting for the related tasks done.
	 *
	 * @param array $funcList The related tasks
	 * @param integer $maxWaitingTimes The Max waiting round.
	 * @param integer $waitingSec The waiting time as a round, unit:"sec".
	 * @return boolean $isOverWaitingTime If Over waiting time, return true. recomend give up to execute.
	 */
	public function isOverWaitingTimeWithWaitingByPS($funcList = [], $isExecingCB = null, $maxWaitingTimes = 35, $waitingSec = 60, $oghome = 'og'){
		$waitingTimeCounter = 0;
		$isOverWaitingTime = false;

		// detect the related tasks is executing?
		$currPS = null;
		$match = null;
		$is_execing4once_only = $this->isExecingListWithPS($funcList, $oghome, $isExecingCB, $currPS, $match);
// $this->debug_log(__METHOD__, 'is_execing4once_only:', $is_execing4once_only, '$maxWaitingTimes:', $maxWaitingTimes, $currPS, $match);
		while( !!$is_execing4once_only
			&& $waitingTimeCounter < $maxWaitingTimes
		){
			$this->idleSec($waitingSec);
			$waitingTimeCounter++;
			// overide
			$is_execing4once_only = $this->isExecingListWithPS($funcList, $oghome, $isExecingCB, $currPS, $match);
		} // EOF while
// $this->debug_log(__METHOD__, 'waitingTimeCounter:', $waitingTimeCounter, '$maxWaitingTimes:', $maxWaitingTimes);
		if( $waitingTimeCounter >= $maxWaitingTimes ){
			$isOverWaitingTime = true; // for give up
			$this->debug_log(__METHOD__, 'Over Max Waiting Times,  $currPS:', $currPS, '$match:', $match, '$funcList:', $funcList);
		}
		return $isOverWaitingTime;
	} // EOF isOverWaitingTimeWithWaitingByPS

	public function debug_log() {
		if ($this->getConfig('log_threshold') == 0) {
			//ignore log by setting
			return '';
		}

		$args = func_get_args();

		if (count($args) <= 0) {
			return '';
		}

		$level = 'debug';

		static $_log;

		$_log = &load_class('Log');

		$isCli = $this->getConfig('print_log_to_console') && $this->CI->input->is_cli_request();

		$msg = $this->formatDebugMessage(array_shift($args));
		$_log->write_log($level, $msg, $args);

		if ( $isCli) {
			// $msg = $this->formatDebugMessage(array_shift($args));

			$this->initCliOutput();

			if(is_array($args)){
				foreach ($args as $key => &$value) {
			        $value=formatObjectForJson($value);
				}
			}

			$cliMsg=json_encode([
				'message'=>$msg,
				'context'=>$args,
				'level'=>Monolog\Logger::DEBUG,
				'level_name'=>'DEBUG',
				'channel'=>$_log->getHostname(),
				'datetime'=>date('c'),
				'trace'=>getSubTitleFromBacktrace(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)),
				'extra'=>[
					'tags'=>[
						'request_id' =>$_log->request_id,
						'env' => $this->getConfig('RUNTIME_ENVIRONMENT'),
						'version'=>PRODUCTION_VERSION,
						'hostname'=>$_log->getHostname(),
					],
					'process_id'=>getmypid(),
					'memory_peak_usage'=>$this->formatBytes(memory_get_peak_usage(true)),
					'memory_usage'=>$this->formatBytes(memory_get_usage(true)),
				]
			], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
			$this->climate->darkGray($cliMsg);
			unset($cliMsg);
		}

		// $msg = $this->formatDebugMessage(array_shift($args));
		// $_log->write_log($level, $msg, $args);

		$jsonErr = json_last_error(); // for previous line, $_log->write_log($level, $msg, $args);
		if ( $jsonErr !== JSON_ERROR_NONE ) {
			$level = 'error';
			$jsonLastError = $this->handleJsonLastError( $jsonErr );
			$_log->write_log($level, $msg, $jsonLastError);
		}

		unset($args);

		return $msg;
	}

	/**
	 * Get the full processlist of mysql.
	 *
	 * @param CI_DB_driver $db
	 * @return array The processlist array.
	 */
	public function getFullProcesslist($db=null, $limit = 10){
        if( ! empty($limit) ){
            return $this->getFullProcesslistWithSchema($db, $limit);
        }

		if(empty($db)){
			$db=$this->CI->db;
		}

		$currDB = $db->getOgTargetDB(); // for mdb else null

		$query = $db->query("show full processlist"); // too much data to quickly check top 10
		$rows = $query->result_array();
		$this->debug_log('full processlist,$rows counter:', count($rows) , 'currDB:', $currDB);
		$query->free_result();
		return $rows;
	}// EOF getFullProcesslist

    public function getFullProcesslistWithSchema($db=null, $limit = 10){

        if(empty($db)){
			$db=$this->CI->db;
		}
        $currDB = $db->getOgTargetDB(); // for mdb else null

        $sql = <<<EOF
SELECT ID as Id
, USER as User
, HOST as Host
, DB as db
, COMMAND as Command
, TIME as Time
, STATE as State
, INFO as Info
FROM INFORMATION_SCHEMA.PROCESSLIST
WHERE COMMAND != "Sleep" /* filter idle */
AND INFO not like "%filter self%" /* filter self */
ORDER BY TIME ASC
LIMIT $limit
;
EOF;
        $query = $db->query($sql);
		$rows = $query->result_array();
        $this->debug_log("The time-consuming top $limit of processlist, rows:" , $rows,'currDB:', $currDB);
		$query->free_result();
		return $rows;
    } // EOF getFullProcesslistWithSchema

	public function verbose_log() {
		if ($this->getConfig('log_threshold') == 0 || !$this->getConfig('verbose_log')) {
			//ignore log by setting
			return '';
		}

		$args = func_get_args();

		if (count($args) <= 0) {
			return '';
		}

		$msg = $this->formatDebugMessage(array_shift($args));

		$level = 'verbose';

		static $_log;

		$_log = &load_class('Log');
		$_log->write_log($level, $msg, $args);

		if ($this->getConfig('print_log_to_console') && $this->CI->input->is_cli_request()) {
			$this->initCliOutput();
			$cliMsg=json_encode([
				'message'=>$msg,
				'context'=>$args,
				'level'=>Monolog\Logger::DEBUG,
				'level_name'=>'DEBUG',
				'channel'=>$_log->getHostname(),
				'datetime'=>date('c'),
				'trace'=>getSubTitleFromBacktrace(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)),
				'extra'=>[
					'tags'=>[
						'request_id' =>$_log->request_id,
						'env' => $this->getConfig('RUNTIME_ENVIRONMENT'),
						'version'=>PRODUCTION_VERSION,
						'hostname'=>$_log->getHostname(),
					],
					'process_id'=>getmypid(),
					'memory_peak_usage'=>$this->formatBytes(memory_get_peak_usage(true)),
					'memory_usage'=>$this->formatBytes(memory_get_usage(true)),
				]
			]);
			$this->climate->darkGray($cliMsg);
			unset($cliMsg);
		}
		unset($args);

		return $msg;
	}

	public function publishToLogServer($msg) {
	}

	public function runtime_debug_log() {
	}

	public function getFromToByWeek($today) {
		$d = new DateTime($today);
		//from monday to sunday
		$dayOfWeek = $d->format('w');
		if ($dayOfWeek == 0) {
			//sunday
			$dayOfWeek = 7;
		}

		$back = $dayOfWeek - 1;
		if ($back > 0) {
			$monday = $d->modify('-' . $back . ' days');
		} else {
			$monday = $d;
		}
		$sunday = clone $monday;
		$sunday->modify('+6 days');
		return array($monday->format('Y-m-d'), $sunday->format('Y-m-d'));
	}

	public function getFromToByMonth($today) {
		$d = new DateTime($today);
		$firstDay = clone $d;
		$lastDay = clone $d;

		$firstDay->setDate($firstDay->format('Y'), $firstDay->format('m'), 1);
		$lastDay->setDate($firstDay->format('Y'), $firstDay->format('m'), $firstDay->format('t'));

		return array($firstDay->format('Y-m-d'), $lastDay->format('Y-m-d'));
	}

	public function isDisabledApi($apiId) {
		$disabled_api_list = $this->getConfig('disabled_api_list');
		return in_array($apiId, $disabled_api_list);
	}

	public function compareDateTime($d1, $d2) {
		return strcmp($this->formatDateTimeForMysql($d1), $this->formatDateTimeForMysql($d2));
	}

    public function compareDateTimeWithTimestamp($dt1, $dt2) {
        // $dt1 = new DateTime( $d1);
        // $dt2 = new DateTime( $d2);
        // $this->debug_log('OGP-33165.6869.generateCallTrace:', $this->generateCallTrace());
		return $dt1->getTimestamp() - $dt2->getTimestamp();
	}

	public function getHoursForSelect() {
		$map = array();
		for ($i = 0; $i < 24; $i++) {
			$map[str_pad($i, 2, '0', STR_PAD_LEFT)] = str_pad($i, 2, '0', STR_PAD_LEFT);
		}
		return $map;
	}

	public function isEnabledClockwork() {
		return false;
		// return $this->getConfig('enable_clockwork');
	}

	public function eventDebug($msg) {
		// if ($this->isEnabledClockwork() && isset($this->CI->clockwork)) {
		// 	$this->CI->clockwork->debug($msg);
		// }
	}

	public function startEvent($title, $desc = '') {
		// if ($this->isEnabledClockwork() && isset($this->CI->clockwork)) {
		// 	if(empty($desc)) {
		// 		$desc = $title;
		// 	}
		// 	$this->CI->clockwork->startEvent($title, $desc);
		// 	// $this->CI->clockwork->info($msg);
		// 	// $this->CI->clockwork->endEvent('debug');
		// }
	}

	public function endEvent($title) {
		// if ($this->isEnabledClockwork() && isset($this->CI->clockwork)) {
		// 	$this->CI->clockwork->endEvent($title);
		// 	// $this->CI->clockwork->info($msg);
		// 	// $this->CI->clockwork->endEvent('debug');
		// }
	}

	public function isEnabledReadonlyDB() {
		return $this->CI->config->item('enable_readonly_db');
	}

	public function getAllAdminMessage() {
		$this->CI->load->model(['users']);
		return $this->CI->users->getAllAdminMessage();
	}

	public function countUnreadAdminMessage() {
		$this->CI->load->model(['users']);
		return $this->CI->users->countUnreadAdminMessage();
	}

	public function truncateWith($str, $len, $tail = '...') {
		$rlt = substr($str, 0, $len);
		if (strlen($str) > $len) {
			$rlt .= $tail;
		}
		return $rlt;
	}

	public function playerLiveChatUrl() {
		$url = $this->getPlayerMessageUrl();
		$player_livechat_url = $this->getConfig('player_livechat_url');
		if (!empty($player_livechat_url)) {
			$url = $player_livechat_url;
		}
		return $url;
	}

	public function blockAndKickPlayerInGameAndWebsite($player_id, $isKick = false, $isBlockInGame = false, $isBlockInWebsite = false) {
		$this->CI->load->model(array('player_model', 'game_provider_auth','external_system'));
        $this->CI->load->library(array('player_library'));
        //get player data
        $player = $this->CI->player_model->getPlayerById($player_id);
        $playerName = $player->username;
        $gameApis = $this->CI->external_system->getAllActiveSytemGameApi();
        if ($isKick) {
			//kick out from site
			$rlt = $this->CI->player_library->kickPlayer($player_id);
			//kick out from game
            foreach ($gameApis as $key) {
                $api = $this->loadExternalSystemLibObject($key['id']);
                if(empty($api)){
                    continue;
                }
                try{
                    $api->logout($playerName);
                }catch(Exception $e){
                    $this->debug_log(__CLASS__ . '::' . __METHOD__ . '(): ', $e->getMessage());
                }
            }
			$this->debug_log('kickout web and game', $playerName);
		}

		if ($isBlockInGame) {
            //get game apis
			//$gameApis = $this->getAllCurrentGameSystemList();
			foreach ($gameApis as $key) {
				$api = $this->loadExternalSystemLibObject($key['id']);
                if(empty($api)){
                    continue;
                }

                $api->blockPlayer($playerName);

				//set player gameapi status to blocked
				$this->CI->game_provider_auth->updateBlockStatusInDB($player_id, $key['id'], Game_provider_auth::IS_BLOCKED);
			}
		}

		if ($isBlockInWebsite) {
			//block player in website
			$this->CI->player_model->updatePlayer($player_id, array('blocked' => Player_model::IS_BLOCKED, 'blocked_status_last_update' => $this->getNowForMysql()));
		}
	}

	public function unblockPlayerInGameAndWebsite($player_id) {
		$this->CI->load->model(array('player_model', 'game_provider_auth'));

		//get player data
		$player = $this->CI->player_model->getPlayerById($player_id);
		$playerName = $player->username;

		//get game apis
        $gameApis = $this->CI->external_system->getAllActiveSytemGameApi();
		foreach ($gameApis as $game) {
			$api = $this->loadExternalSystemLibObject($game['id']);
            if(empty($api)){
                continue;
            }
			$api->unblockPlayer($playerName);

			//set player gameapi status to unblocked
			$this->CI->game_provider_auth->updateBlockStatusInDB($player_id, $game['id'], Game_provider_auth::IS_UNBLOCKED);
		}

		//unblock player in website
		$this->CI->player_model->updatePlayer($player_id, array('blocked' => Game_provider_auth::IS_UNBLOCKED, 'blocked_status_last_update' => $this->getNowForMysql()));
	}

	public function isEnabledFeature($feature) {
		if ($this->CI->db->table_exists('system_features')) {

			$this->CI->load->model(array('system_feature'));

			return $this->CI->system_feature->isEnabledFeature($feature);
		} else {

			$enabled_features = $this->getConfig('enabled_features');
			if (!empty($enabled_features)) {
				return in_array($feature, $enabled_features);
			}
			return false;

		}
	}

	public function startsWith($str, $start) {
		return strpos($str, $start) === 0;
	}

	public function getSystemApplicationPath($prefix){
        $basename = basename(FCPATH);

        return realpath(FCPATH . '../../' . $prefix . '/'. $basename . '/' . APPPATH);
    }

	public function getSystemUrls() {
		$result = array();
		$prefixArr = $this->getConfig('prefix_website_list');
		// $prefixArr = ['player', 'www', 'admin', 'aff', 'agency', 'm', 'pay', 'pay2'];
		foreach ($prefixArr as $p) {
			$result[$p] = $this->getSystemUrl($p);
		}
		return $result;
	}

	public function removePrefixFromHost($host, $prefixArr) {
		$last_host = '.' . $host;
		foreach ($prefixArr as $p) {
			if ($this->startsWith($host, $p)) {
				$last_host = substr($host, strlen($p));
				break;
			}
		}
		return $last_host;
	}

	public function getSystemHost($prefix) {
		$prefixArr = $this->getConfig('prefix_website_list');
		// $prefixArr = ['player', 'www', 'admin', 'aff', 'agency', 'm', 'pay', 'pay2'];

		$url = $this->getConfig($prefix . '_site_url');
		if (empty($url) && isset($_SERVER['HTTP_HOST']) && !empty($_SERVER['HTTP_HOST'])) {
			$host = @$_SERVER['HTTP_HOST'];

			if (!empty($host)) {
				$last_host = $this->removePrefixFromHost($host, $prefixArr);
				$url = $prefix . $last_host;
			}
		}

		return $url;
	}

	public function getSystemUrl($prefix, $uri = null, $force_add_domain=true, $extra_info = []) {
		// $prefixArr = ['player', 'www', 'admin', 'aff', 'agency', 'm', 'pay', 'pay2'];
		$prefixArr = $this->getConfig('prefix_website_list');

		$url = $this->getConfig($prefix . '_site_url');

		// $this->utils->debug_log('getSystemUrl http host', $_SERVER['HTTP_HOST'], $prefix, $uri);

		if (empty($url) && isset($_SERVER['HTTP_HOST']) && !empty($_SERVER['HTTP_HOST'])) {
			$host = @$_SERVER['HTTP_HOST'];

			if (!empty($host)) {

				if(!$force_add_domain && $this->startsWith($host, $prefix)){
					$url='';
				}else{
					$last_host = $this->removePrefixFromHost($host, $prefixArr);
					// $this->debug_log('last_host', $last_host);
					if ($this->getConfig('always_https') || $this->isHttps()) {
						$url = 'https://' . $prefix . $last_host;

						if( $this->getConfig('always_https') && !empty($extra_info) ){
                            if(!empty($extra_info['getPlayerInternalUrl'])){
                                $url = 'http://' . $prefix . $last_host;
                            }
                        }
					} else {
						$url = 'http://' . $prefix . $last_host;
					}
				}
			}
		}

		if (!empty($uri)) {
			$uri = '/' . ltrim($uri, '/');
		}

		return $url . $uri;
	}

	public function getAsyncApiUrl($uri = NULL){
	    return $this->getSystemUrl('player', '/async' . ((empty($uri)) ? '' : '/' . ltrim($uri, '/')));
    }

	public function convertLangToJqueryValidateLang($session_lang) {
		$this->CI->load->library(['language_function']);
		$lang = null;
		switch ($session_lang) {
			case Language_function::INT_LANG_CHINESE:
				$lang = 'zh';
				break;
			case Language_function::INT_LANG_VIETNAMESE:
				$lang = 'vi';
				break;
			case Language_function::INT_LANG_INDONESIAN:
				$lang = 'id';
				break;
			case Language_function::INT_LANG_KOREAN:
				$lang = 'ko';
				break;
			case Language_function::INT_LANG_THAI:
				$lang = 'th';
				break;
			case Language_function::INT_LANG_INDIA:
				$lang = 'hi';
				break;
			case Language_function::INT_LANG_PORTUGUESE:
				$lang = 'pt_BR';
				break;
			case Language_function::INT_LANG_SPANISH:
				$lang = 'es';
				break;
			case Language_function::INT_LANG_KAZAKH:
				$lang = 'kk';
				break;
		}
		return $lang;
	}

	public function getRuntimeEnv() {
		return $this->getConfig('RUNTIME_ENVIRONMENT');
	}

	public function currentUrl() {
		$protocol = strpos(strtolower($_SERVER['SERVER_PROTOCOL']), 'https')
		=== FALSE ? 'http' : 'https';
		$host = $_SERVER['HTTP_HOST'];
		$uri = @$_SERVER['REQUEST_URI'];

		$currentUrl = $protocol . '://' . $host . $uri;

		return $currentUrl;
	}

	public function decodeJson($mixin) {
		return json_decode($mixin, true);
	}

	public function encodeJson($str, $pretty = false) {
		if ($pretty) {
			return json_encode($str, JSON_PRETTY_PRINT);
		} else {
			return json_encode($str);
		}
	}

	public function loadSimpleAjaxUploader() {
		require_once dirname(__FILE__) . '/Uploader.php';

		$uploader = new FileUpload('uploadfile');

		return $uploader;
	}

	public function compareMinMaxFloat($a, $min, $max, $includedMinMax = false) {
		if ($max === null) {
			$max = PHP_INT_MAX;
		}
		if ($min === null) {
			$min = 0;
		}
		if ($includedMinMax) {

			return $this->compareResultFloat($a, '>=', $min) && $this->compareResultFloat($a, '<=', $max);

		} else {

			return $this->compareResultFloat($a, '>', $min) && $this->compareResultFloat($a, '<', $max);

		}
	}

	public function appendAdminSupportLiveChat() {
		if ($this->isEnabledFeature('show_admin_support_live_chat')) {
			return '<script src="' . site_url('/async/admin_support_live_chat') . '"></script>';
		}
		return '';
	}

	function create_random_password($length = 12) {
		$this->CI->load->helper(['string']);
		return random_string('alnum', $length);
		// char set
		// $chars = array('a', 'b', 'c', 'd', 'e', 'f', 'g', 'h',
		// 	'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's',
		// 	't', 'u', 'v', 'w', 'x', 'y', 'z', 'A', 'B', 'C', 'D',
		// 	'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O',
		// 	'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
		// 	'0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '!',
		// 	'@', '#', '$', '%', '^', '&', '*', '(', ')', '-', '_',
		// 	'[', ']', '{', '}', '<', '>', '~', '`', '+', '=', ',',
		// 	'.', ';', ':', '/', '?', '|');

		// // get random keys of array chars
		// $keys = array_rand($chars, $length);

		// // concatenate chars together to build password
		// $password = '';
		// for ($i = 0; $i < $length; $i++) {
		// 	$password .= $chars[$keys[$i]];
		// }
		// return $password;
	}

	public function generate_password_no_special_char($length = 12) {
		$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
		$count = strlen($chars);

		for ($i = 0, $result = ''; $i < $length; $i++) {
			$index = rand(0, $count - 1);
			$result .= substr($chars, $index, 1);
		}
		return $result;
	}

	public function getLimitDateRangeForPromo($bonusApplicationLimitDateType, $date = null) {
		$this->CI->load->model(['promorules']);
		$dateTimeFrom = null;
		$dateTimeTo = null;
		if (empty($date)) {
			$date = new DateTime();
		}
		switch ($bonusApplicationLimitDateType) {
		case Promorules::BONUS_APPLICATION_LIMIT_DATE_TYPE_DAILY:
			$dateTimeFrom = $date->format('Y-m-d') . ' ' . Utils::FIRST_TIME;
			$dateTimeTo = $date->format('Y-m-d') . ' ' . Utils::LAST_TIME;
			break;
		case Promorules::BONUS_APPLICATION_LIMIT_DATE_TYPE_WEEKLY:
			list($dateFrom, $dateTo) = $this->getFromToByWeek($date->format('Y-m-d'));

			$dateTimeFrom = $dateFrom . ' ' . Utils::FIRST_TIME;
			$dateTimeTo = $dateTo . ' ' . Utils::LAST_TIME;
			break;
		case Promorules::BONUS_APPLICATION_LIMIT_DATE_TYPE_MONTHLY:
			$dateTimeFrom = $date->format('Y-m-01') . ' ' . Utils::FIRST_TIME;
			$dateTimeTo = $date->format('Y-m-t') . ' ' . Utils::LAST_TIME;
			break;
		case Promorules::BONUS_APPLICATION_LIMIT_DATE_TYPE_YEARLY:
			$dateTimeFrom = $date->format('Y-01-01') . ' ' . Utils::FIRST_TIME;
			$dateTimeTo = $date->format('Y-12-31') . ' ' . Utils::LAST_TIME;
			break;
		}
		return [$dateTimeFrom, $dateTimeTo];
	}

	public function minDateTime($d1, $d2) {
		if ($this->compareDateTime($d1, $d2) > 0) {
			return $d2;
		}

		return $d1;
	}

	public function maxDateTime($d1, $d2) {
		if ($this->compareDateTime($d1, $d2) < 0) {
			return $d2;
		}

		return $d1;
	}

	public function getDefaultSortColumn($name) {
		$default_sort_columns = $this->getConfig('default_sort_columns');
		if (isset($default_sort_columns[$name])) {
			return $default_sort_columns[$name];
		}
		//default sort is 1 if don't find settings
		return 1;
	}

	public function getConditionSortColumn($name, $conditions) {
		$conditional_sort_columns = $this->getConfig('conditional_sort_columns');
		if (isset($conditional_sort_columns[$name], $conditional_sort_columns[$name]['condition'])){
			$is_match_conditions = ($conditional_sort_columns[$name]['condition'] == $conditions);
			if($is_match_conditions && isset($conditional_sort_columns[$name]['column'])){
				return $conditional_sort_columns[$name];
			}
		}
		return null;
	}

	public function getPlayerMainWalletBalance($playerId) {
		$this->CI->load->model(['wallet_model']);
		return $this->formatCurrencyNoSym($this->CI->wallet_model->getMainWalletBalance($playerId));
	}

    public function getBigWallet($playerId) {
        $this->CI->load->model(['wallet_model']);
        return $this->CI->wallet_model->getBigWalletByPlayerId($playerId);
    }

	/**
	 * Get the Bonus By Tier
	 *
	 * @param integer|float $min The min of the setting.
	 * @param integer|float $max The max of the setting.
	 * @param integer|float $percentage The percentage of the setting.
	 * @param integer|float $amount The betting amount.
	 * @param point $calculated_amount The calculated betting amount.
	 * @param integer|float $bonus_max_limit
	 * @return void
	 */
	public function getBonusByTier($min, $max, $percentage, $amount, &$calculated_amount, $bonus_max_limit = -1){
		if( $max == 0){ // Unlimited
			$max = PHP_INT_MAX;
		}
		if( (float)$min > (float)$max){
			$_min = $max;
			$_max = $min;
		}else{
			$_min = $min;
			$_max = $max;
		}
		$diff = (float)$_max - (float)$_min;
		$diff++;
		if( (float)$amount >= $diff){
			$bonous = $diff * (float)$percentage;
			$calculated_amount = $diff;
		}else if($amount < $diff){
			$bonous = (float)$amount * (float)$percentage;
			$calculated_amount = (float)$amount;
		}

		if($bonus_max_limit > -1){
			if($bonous > $bonus_max_limit){
				$bonous = $bonus_max_limit;
				$calculated_amount = $bonous / (float)$percentage;
			}
		}

		return $bonous;
	} // EOF getBonusByTier

    /**
     *
     * Move From pub.php
     *
     * @param $playerId
     *
     * @return mixed
     */
    public function getSimpleBigWallet($playerId) {
        $this->CI->load->model([ 'wallet_model', 'external_system' ]);

        $big_wallet = $this->CI->wallet_model->getOrderBigWallet($playerId);
        //$apiMap = $this->utils->getGameSystemMap();

        $subwallets = array();

        $game_balance_total = 0;

        $game_mt = $this->CI->external_system->getAllGameApiMaintenanceStatusKV();

        foreach($big_wallet['sub'] as $sub_wallet_id => $sub_wallet){
            if($this->is_mobile()){
                if(!$sub_wallet['enabled_on_mobile']) continue;
            }else{
                if(!$sub_wallet['enabled_on_desktop']) continue;
            }

            $arr = [
                'sub_wallet_id' => $sub_wallet_id,
                'sub_wallet' => isset($sub_wallet['game']) ? lang($sub_wallet['game']) : '',
                'balance' => $sub_wallet['total_nofrozen'] ,
                // 'maintenance' => $this->CI->external_system->isGameApiMaintenance($sub_wallet_id)
                'maintenance' => intval(isset($game_mt[$sub_wallet_id]['maintenance_mode']) ? $game_mt[$sub_wallet_id]['maintenance_mode'] : 0)
            ];

            $game_balance_total = $game_balance_total + $sub_wallet['total_nofrozen'];
            array_push($subwallets, $arr);
        }

        $walletInfo = [
            'total_balance' => [
                'balance' => $big_wallet['total_nofrozen'],
                'language' => lang('Total Balance')
            ],
            'main_wallet' => [
                'balance' => $big_wallet['main']['total_nofrozen'],
                'frozen' => $big_wallet['main']['frozen'],
                'language' => lang('Main Wallet')
            ],
            'sub_wallets' => $subwallets,
            'game_total' => $game_balance_total,
            'total_withfrozen' => $big_wallet['total_nofrozen'] + $big_wallet['main']['frozen']
        ];

        return $walletInfo;
    }

	public function isEnabledPromotionRule($name) {
		$promoRulesSettings = $this->getConfig('promotion_rules');
		return @$promoRulesSettings[$name];
	}

	public function getPromotionRuleSetting($name, $default = null) {
		$promoRulesSettings = $this->getConfig('promotion_rules');
		return isset($promoRulesSettings[$name]) ? $promoRulesSettings[$name] : $default;
	}

	/**
	 * Returns url of logo image set in CMS/static sites, with fail-safe
	 * @return	string		URL of logo image. defaults to og-login-logo.png if file in static sites doesn't exist.
	 */
	public function getDefaultLogoUrl() {
		$url_static_site = '/resources/images/static_sites';
		$logo_file_failsafe = 'og-login-logo.png';

		$this->CI->load->model(array('static_site'));
		$url_logo = "{$url_static_site}/{$this->CI->static_site->getDefaultLogoUrl()}";
		$realpath_logo = $this->getRealPathForPublicFile($url_logo);
		if (!file_exists($realpath_logo)) {
			$url_logo = "{$url_static_site}/{$logo_file_failsafe}";
		}

		return $url_logo;

	}

	public function isEnabledOnLiveChat($key) {
		$live_chat = $this->getConfig('live_chat');
		return @$live_chat[$key];
	}

	#Ex. 1470713376387 = 2016-08-09 03:29:36
	public function convertTimestampToDateTime($timestamp) {
		$newTimestamp = $timestamp / 1000;
		return gmdate("Y-m-d H:i:s", $newTimestamp);
	}

	public function getLoggedPlayerId() {
		$playerId = null;

		// if(!isset($this->CI->authentication)){
		$this->CI->load->library(array('authentication'));
		// }

		if (method_exists($this->CI->authentication, 'getPlayerId')) {
			$playerId = $this->CI->authentication->getPlayerId();
		}

		return $playerId;
	}

	public function getBigWalletByPlayerId($playerId) {
		$this->CI->load->model(['wallet_model']);

		return $this->CI->wallet_model->getBigWalletByPlayerId($playerId);
	}

	public function getPromoApplication() {
		$this->CI->load->model(['player_promo']);
		$result = $this->CI->player_promo->countAllStatusOfPromoApplication();
		$this->utils->debug_log('countAllStatusOfPromoApplication', $result);
		return $result;
	}

	public function getSBENotificationCount(){
        $result = [];
        $this->CI->load->model(['player','player_promo','internal_message','sale_order', 'player_attached_proof_file_model', 'player_dw_achieve_threshold','shopper_list','player_login_report','duplicate_contactnumber_model', 'player_in_priority']);
        $this->CI->load->library(['payment_manager']);
        $start_today = date('Y-m-d 00:00:00');
		$end_today = date('Y-m-d 23:59:59');
        $sum_notif_count = 0;

        if($this->CI->permissions->checkPermissions('promoapp_list')){
            $promo = $this->getPromoApplication();
            $promo_count = intval($promo[Player_promo::TRANS_STATUS_REQUEST]);
            $result['notificatons']['promo'] = number_format($promo_count);
            $sum_notif_count += $promo_count;
        }

        if($this->CI->permissions->checkPermissions('chat')){
            $messages_count = $this->CI->internal_message->countAdminTotalUnreadMessages();
            $result['notificatons']['messages'] = number_format($messages_count);
            $sum_notif_count += $messages_count;
        }

        if($this->CI->permissions->checkPermissions('deposit_list')){
            $bank_deposit_count = $this->CI->sale_order->countSaleOrders(Sale_order::PAYMENT_KIND_DEPOSIT, Sale_order::VIEW_STATUS_REQUEST,$start_today,$end_today, LOCAL_BANK_OFFLINE,'', Sale_order::COLUMN_CREATED_AT);
            $result['notificatons']['deposit_list']['bank_deposit'] = number_format($bank_deposit_count);
            $sum_notif_count += $bank_deposit_count;

            $thirdparty_count = $this->CI->sale_order->countSaleOrders(Sale_order::PAYMENT_KIND_DEPOSIT, Sale_order::VIEW_STATUS_REQUEST,$start_today ,$end_today, AUTO_ONLINE_PAYMENT, '', Sale_order::COLUMN_CREATED_AT);
            $result['notificatons']['deposit_list']['thirdparty'] = number_format($thirdparty_count);
            $sum_notif_count += $thirdparty_count;
        }

        if($this->CI->permissions->checkPermissions('payment_withdrawal_list')){
            $withdrawal_request_count = $this->CI->payment_manager->getDWCount('withdrawal', 'request');
            $result['notificatons']['withdrawal_request'] = number_format($withdrawal_request_count);
            $sum_notif_count += $withdrawal_request_count;
        }

        if($this->CI->permissions->checkPermissions('agent_withdraw') && $this->isEnabledFeature('notify_agent_withdraw')){
            $agent_withdraw_request_count = $this->CI->payment_manager->getAgentRequestCount('request',  date("Y-m-d") . ' 00:00:00', date("Y-m-d") . ' 23:59:59');
            $result['notificatons']['agent_withdraw_request'] = number_format($agent_withdraw_request_count);
            $sum_notif_count += $agent_withdraw_request_count;
        }

        if($this->CI->permissions->checkPermissions('affiliate_withdraw') && $this->isEnabledFeature('notify_affiliate_withdraw')){
            $affiliate_withdraw_request_count = $this->CI->payment_manager->getAffiliateRequestCount('request',  date("Y-m-d") . ' 00:00:00', date("Y-m-d") . ' 23:59:59');
            $result['notificatons']['affiliate_withdraw_request'] = number_format($affiliate_withdraw_request_count);
            $sum_notif_count += $affiliate_withdraw_request_count;
        }

        if ($this->CI->permissions->checkPermissions('player_list') && $this->isEnabledFeature('notification_new_player')) {
            $new_player_count = $this->CI->player->countNewPlayer($this->getLastViewedNewPlayerDateTime());
            $result['notificatons']['new_player'] = number_format($new_player_count);
            $sum_notif_count += $new_player_count;
        }

        if($this->CI->permissions->checkPermissions('responsible_gaming_setting') && $this->isEnabledFeature('responsible_gaming')){
            $self_exclusion_request_count = $this->CI->payment_manager->getRbSelfExAccount();
            $result['notificatons']['self_exclusion_request'] = number_format($self_exclusion_request_count);
            $sum_notif_count += $self_exclusion_request_count;
        }

        if ($this->CI->permissions->checkPermissions('game_description') && $this->isEnabledFeature('show_new_games_on_top_bar')){
            $new_games_count = $this->countNewGames();
            $result['notificatons']['new_games'] = number_format($new_games_count);
            $sum_notif_count += $new_games_count;
        }

        if($this->CI->permissions->checkPermissions('attached_file_list')){
            $new_player_attachment_count = $this->CI->player_attached_proof_file_model->getTodayPlayerAttachmentCount();
            $result['notificatons']['new_player_attachment'] = number_format($new_player_attachment_count);
            $sum_notif_count += $new_player_attachment_count;
		}

		if ($this->CI->permissions->checkPermissions('shopping_center_manager') && $this->utils->isEnabledFeature('enable_shop')) {
            $new_point_request_count = count($this->CI->shopper_list->getShopperList(null, Shopper_list::REQUEST));
            $result['notificatons']['new_point_request'] = number_format($new_point_request_count);
            $sum_notif_count += $new_point_request_count;
        }


        if($this->CI->permissions->checkPermissions('show_player_deposit_withdrawal_achieve_threshold') && $this->isEnabledFeature('show_player_deposit_withdrawal_achieve_threshold')){
            $count_dw_achieve_threshold = $this->CI->player_dw_achieve_threshold->getPlayerDwAchieveThresholdCount();
            $result['notificatons']['player_dw_achieve_threshold'] = number_format($count_dw_achieve_threshold);
            $sum_notif_count += $count_dw_achieve_threshold;
        }

		if($this->CI->permissions->checkPermissions('notification_duplicate_contactnumber') && $this->CI->config->item('notification_duplicate_contactnumber')){
            $count_duplicate_contactnumber = $this->CI->duplicate_contactnumber_model->countDuplicateContactNumber($start_today,$end_today);
            $result['notificatons']['duplicate_contactnumber'] = number_format($count_duplicate_contactnumber);
            $sum_notif_count += $count_duplicate_contactnumber;
        }

        if($this->CI->permissions->checkPermissions('show_last_login_date_notification') && $this->CI->config->item('show_last_login_date_notification')){
        	$time_on_notify_last_login = $this->CI->operatorglobalsettings->getSystemSetting('time_on_notify_last_login');
        	if(!empty($time_on_notify_last_login) && isset($time_on_notify_last_login['value'])){
        		$clear_time = $this->utils->getConfig('clear_time_on_notify_last_login');
	    		if(strtotime($clear_time) <= strtotime($time_on_notify_last_login['value'])){
	    			$date = $time_on_notify_last_login['value'];
	    		}else{
	    			$date = $clear_time;
	    		}
        	}else{
        		$date = null;
        	}
            $new_player_login_count = $this->CI->player_login_report->getPlayerLoginCountByDate($date);
            $result['notificatons']['new_player_login'] = number_format($new_player_login_count);
            $sum_notif_count += $new_player_login_count;
		}

        $this->CI->load->model(['users']);
        $adminId = $this->CI->authentication->getUserId();
        $_adminDetail = $this->CI->users->selectUsersById($adminId);
        if( $this->CI->operatorglobalsettings->getSettingBooleanValue('player_login_failed_attempt_blocked')
            && $this->CI->config->item('enabled_notifi_failed_login_attempt_features')
        ){

            $_start = $_adminDetail['notified_at'];
            $_end = $end_today;
            $count_failed_login_attempt = $this->CI->player_model->getPlayerIdsByFailedLoginAttemptTimeoutUntil($_start, $_end);
            $result['notificatons']['failed_login_attempt'] = number_format($count_failed_login_attempt);
            $sum_notif_count += $count_failed_login_attempt;
        }
        if( $this->CI->config->item('enabled_priority_player_features')){

            $_start = $_adminDetail['notified_at'];
            $_end = $end_today;
            $count_priority_player = $this->CI->player_in_priority->countPriority($_start, $_end);
            $result['notificatons']['priority_player'] = number_format($count_priority_player);
            $sum_notif_count += $count_priority_player;
        }

        $result['sum_notif'] = $sum_notif_count;

        return $result;
    }

	/**
	 * $d1+$record_activity_timeout_seconds < $d2
	 *
	 * @param  [type] $d1                              [description]
	 * @param  [type] $d2                              [description]
	 * @param  [type] $record_activity_timeout_seconds [description]
	 * @return [type]                                  [description]
	 */
	public function moreThanTimeout($d1, $d2, $record_activity_timeout_seconds) {
		$obj1 = new DateTime($d1);
		$obj2 = new DateTime($d2);
		$obj1->modify('+' . $record_activity_timeout_seconds . ' seconds');

		// $this->debug_log('moreThanTimeout compare',$d1, $obj1, $obj2, 'record_activity_timeout_seconds', $record_activity_timeout_seconds);

		return $this->compareDateTime($obj1, $obj2) < 0;
	}

	public function getIncludeView($name) {
		$apppath = realpath(APPPATH);
		return $apppath . '/views/includes/' . $name;
	}

	public function loadExcel($file) {
		require_once dirname(__FILE__) . '/phpexcel/PHPExcel.php';
		$sheetData = null;
		try {
			$objPHPExcel = PHPExcel_IOFactory::load($file);
			$sheetData = $objPHPExcel->getActiveSheet()->toArray(null, true, true, true);
		} catch (Exception $e) {
			$this->error_log($e);
		}
		return $sheetData;
	}

	public function getWalletLang() {

		# MAKE IT CONFIGURABLE
		# CONFIG SAMPLE IN SECRET KEYS/ MAKE SURE YOU HAVE TRANSLATIONS

		#CONFIG
		$walletOrder = $this->getConfig('wallet_type');

		#DEFAULT
		$apis = $this->getGameSystemMap();

		if (!empty($walletOrder)) {

			$apis = $walletOrder;
		}

		return $apis;
	}

	// $types=['gif','jpg','png','jpeg'];
	const MIMES = [
		'gif' => 'image/gif',
		'jpeg' => 'image/jpeg',
		'jpg' => 'image/jpeg',
		'jpe' => 'image/jpeg',
		'png' => 'image/png',
	];

	public function sendFilesHeader($bannerUrl, $mimeType = null) {
		if ($mimeType == null) {
			$type = pathinfo($bannerUrl, PATHINFO_EXTENSION);
			$mimeType = self::MIMES[$type];
		}
		if ($mimeType == null) {
			$mimeType = 'application/octet-stream';
		}

		$this->CI->output->set_content_type($mimeType);
		$this->CI->output->set_header('X-Accel-Redirect: ' . $bannerUrl);
	}

	public function getFileMimeType($ext){
		$ext = strtolower($ext);
        $mime_types = self::MIMES;
	    return (isset($mime_types[$ext])) ? self::MIMES[$ext] : 'application/octet-stream';
    }

	public function clearIdArray($idArr) {
		$rlt = [];
		if (!empty($idArr)) {
			foreach ($idArr as $id) {
				if (!empty($id) && $id != '0' && $id > 0) {
					$rlt[] = $id;
				}
			}
		}
		return $rlt;
	}

	public function filterActiveGameApi($gameApiArr) {
		$this->CI->load->model(['external_system']);
		return $this->CI->external_system->filterActiveGameApi($gameApiArr);
	}

	public function appendFeedback() {
		$script = '';

		return $script;
	}

	public function loadResponseFile($filepath) {
		// $dateDir = "/" . date('Y-m-d');
		// $dir = $dateDir . "/" . $systemTypeId . "/";
		// //create dir
		// if (!file_exists(RESPONSE_RESULT_PATH . $dir)) {
		// 	mkdir(RESPONSE_RESULT_PATH . $dir, 0777, true);
		// 	//chmod
		// 	@chmod(RESPONSE_RESULT_PATH . $dateDir, 0777);
		// 	@chmod(RESPONSE_RESULT_PATH . $dir, 0777);
		// }
		// $filename = $this->getDatetimeNow() . "_" . random_string('alnum', 8) . ".txt";
		// $f = RESPONSE_RESULT_PATH . $dir . $filename;
		// file_put_contents($f, $content);
		$f = RESPONSE_RESULT_PATH . $filepath;

		return file_get_contents($f);
	}

	public function getPlayerSessionTimeout($player_id, $session_id) {
		if (empty($player_id)) {
			return '';
		}

		$check_player_session_timeout = $this->isEnabledFeature('check_player_session_timeout');

		$run_player_session_timeout = $check_player_session_timeout ? '_check_player_session_timeout();' : '';
		$check_timeout = $this->getConfig('check_session_timeout_ms');
		$url = site_url('/pub/check_player_session_timeout/' . $player_id . '/' . $session_id);
		$logout_url = site_url('/iframe/auth/logout');
		$text_session_timeout = lang('Sorry, session timeout. please login again');

		return <<<EOD

window['_check_player_session_timeout']=function(){

	$.ajax({
		url: '{$url}',
		xhrFields: {
      		withCredentials: true
   		},
		dataType: 'json',
		success: function(data){
			//console.log(data);
			if(data && data['success']){
				if(data['is_timeout']){
					//logout and go back
					alert('{$text_session_timeout}');
					window.location.href='{$logout_url}';
				}
			}
		}
	});

    setTimeout('_check_player_session_timeout()',$check_timeout);
}

{$run_player_session_timeout}


EOD;

	}

	/**
	 * convert year month to start end
	 * @param  string $yearmonth format YYYYmm
	 * @return array   $start, $end
	 */
	public function getStartEndDateTime($yearmonth) {

		$year = substr($yearmonth, 0, 4);
		$month = substr($yearmonth, 4);

		$start = new DateTime($year . '-' . $month . '-01 ' . self::FIRST_TIME);

		$lastDay = $start->format('t');
		$end = new DateTime($year . '-' . $month . '-' . $lastDay . ' ' . self::LAST_TIME);

		return array($start, $end);
	}

	/**
	 * check if mobile
	 * @return boolean
	 */
	public function is_mobile() {
		$this->CI->load->library(['user_agent']);

		return !!$this->CI->agent->is_mobile();
	}

	public function is_safetey_browser(){
        $headers = [];
        foreach($_SERVER as $name => $value){
            if(substr($name, 0, 5) == 'HTTP_'){
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        $safetyBrowserFlg = FALSE;
        if(array_key_exists("User-Agent", $headers)){
            /*User-Agent from safety browser   "Mozilla/5.0 (iPhone; CPU iPhone OS 11_0_2 like Mac OS X) AppleWebKit/604.1.38 (KHTML, like Gecko) TripleoneWebKit 1.0";*/
            if(preg_match("/TripleoneWebKit/", $headers['User-Agent'])){
                $safetyBrowserFlg = TRUE;
            }
        }

        return $safetyBrowserFlg;
    }

	/**
	 * strip the subdomain ex. player.test.com = test.com
	 * @param  string $domain
	 * @return string
	 */
	public function stripSubdomain($domain) {
		$domain_array = explode('.', $domain);
		$domain_array = array_reverse($domain_array);
		return $domain_array[1] . '.' . $domain_array[0];
	}

	/** validate password
	 *
	 * @param  string $encrypted_password  encrypted password
	 * @param  string $password
	 * @return boolean
	 */
	public function validate_password($encrypted_password, $password) {
		return $encrypted_password == $this->encodePassword($password);
	}

	/**
	 * validate password , use md5
	 *
	 * @param  string $encrypted_password_md5
	 * @param  string $password
	 * @return boolean
	 */
	public function validate_password_md5($encrypted_password_md5, $password) {
		return $encrypted_password_md5 == $this->encodePasswordMD5($password);
	}

	public function getOrderBigWalletByPlayerId($playerId) {
		$this->CI->load->model(['wallet_model']);
		return $this->CI->wallet_model->getOrderBigWallet($playerId);
	}

	public function resetBalance($player_id, $messageArr = null) {
		$success = true;
		$manager = $this->utils->loadGameManager();
		$rlt = $manager->queryBalanceOnAllPlatformsByPlayerId($player_id);

		$messageArr = [];
		if (!empty($rlt)) {

			$this->CI->load->model(array('wallet_model', 'daily_balance', 'game_logs', 'game_provider_auth', 'player_model'));

			$controller = $this;
			$this->CI->player_model->lockAndTrans(Utils::LOCK_ACTION_BALANCE, $player_id, function () use ($controller, $player_id, $rlt) {

				$apiArray = $controller->utils->getApiListByBalanceInGameLog();

				foreach ($rlt as $systemId => $val) {
					if ($val['success']) {
						$balance = $val['balance'];

						$api = $controller->utils->loadExternalSystemLibObject($systemId);

						//$api->updatePlayerSubwalletBalance($player_id, $balance);
						$api->updatePlayerSubwalletBalanceWithoutLock($player_id, $balance);
						//update register falg
						// $api->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);

						//only for balance_in_game_logs
						if (in_array($systemId, $apiArray)) {
							$afterBalance = $balance;
							$amount = 0;
							$gameUsername = $controller->game_provider_auth->getGameUsernameByPlayerId($player_id, $systemId);
							$respResultId = null;
							$transType = Game_logs::TRANS_TYPE_SUB_WALLET_TO_MAIN_WALLET;
							$created_at = null;

							//insert to game logs
							$id = $controller->game_logs->insertGameTransaction($systemId, $player_id, $gameUsername,
								$afterBalance, $amount, $respResultId, $transType, $created_at);

							$controller->utils->debug_log('insert game transaction because reset balance',
								$systemId, $player_id, 'balance', $afterBalance, $amount, $transType, 'id', $id);

						}

					} else {
						$success = false;
					}
				}
				//only record one
				$controller->wallet_model->recordPlayerAfterActionWalletBalanceHistory(Wallet_model::BALANCE_ACTION_REFRESH,
					$player_id, null, -1, 0, null, null, null, null, null);

				return true;
			});
		}

		return $success;
	}

	public function sendMessageService($message, $url='', $user='', $channel='') {

		$success = false;

		$url = empty($url) ? $this->getConfig('slack_url') : $url;
		$user = empty($user) ? $this->getConfig('slack_user') : $user;
		$channel = empty($channel) ? $this->getConfig('slack_notify_channel') : $channel;

		$this->debug_log('url', $url, 'user', $user, 'channel', $channel, 'message', $message);

		if (!empty($url) && !empty($user) && !empty($channel) && !empty($message)) {
			$format_message = var_export($message, true);

			$data = array('payload' => json_encode(
				["channel" => $channel, 'username' => $user,
					'attachments' => [['text' => "```php\n" . $format_message . "```\n",
						"color" => "#7CD197"],
					],
				]
			));
			$ch = curl_init($url);

			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

			$result = curl_exec($ch);

			$err_code = curl_errno($ch);
			$success = $err_code == 0;

			if (!$success) {
				$err = curl_error($ch);
				$this->error_log('send message failed: ' . $err_code . ':' . $err, $message);
			}

			curl_close($ch);

		} else {
			$this->debug_log('ignore empty message service');
		}

		return $success;
	}

    public function isHttps(){
        $is_https = isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off';
        if(!$is_https){
            $auto_redirect_to_https_list = $this->getConfig('auto_redirect_to_https_list');

            if(!empty($auto_redirect_to_https_list)){
                $current_www = $this->getSystemHost('www');
                $current_player = $this->getSystemHost('player');

                $is_https = in_array($current_www, $auto_redirect_to_https_list) || in_array($current_player, $auto_redirect_to_https_list);
            }
        }

        return $is_https;
    }

	public function getPlayerLastLogInTime() {

		$this->CI->load->model(['player_model']);
		return $this->CI->player_model->getPlayerLogInTime();
	}

	public function unreadMessages($player_id = NULL) {
        if(NULL === $player_id){
            $playerId = $this->CI->authentication->getPlayerId();
        }

		$this->CI->load->model('internal_message');

		return $this->CI->internal_message->countPlayerUnreadMessages($player_id);
	}

	public function getTrackingCodeFromSession() {

		if($this->getConfig('enable_tracking_all_pages_by_aff_code')) {
			$this->clearTrackingCode();
			$tracking_code = get_cookie('_og_tracking_code');
		} else {
			$tracking_code = $this->CI->session->userdata('tracking_code');
		}
		if (empty($tracking_code)) {
			$this->CI->load->helper('cookie');
			$tracking_code = get_cookie('_og_tracking_code');
		}

		if (empty($tracking_code)) {
			$tracking_code = null;
		}

		return $tracking_code;
	}

    public function clearTrackingCode() {
        $this->CI->load->helper('cookie');
        $this->CI->session->unset_userdata('tracking_code');
        delete_cookie('_og_tracking_code');
    }

	public function clearTrackingToken() {
        $this->CI->load->helper('cookie');
        $this->CI->session->unset_userdata('tracking_token');
        delete_cookie('_og_tracking_token');
    }

	public function callHttpWithProxy($url, $method, $params, array $options, $curlOptions = [], $headers = null) {
        $settle_proxy=false;
        // set proxy
        if (isset($options['call_socks5_proxy']) && !empty($options['call_socks5_proxy'])) {
            $this->utils->debug_log('http call with proxy', $options['call_socks5_proxy']);
            $curlOptions[CURLOPT_PROXYTYPE]=CURLPROXY_SOCKS5_HOSTNAME;
            $curlOptions[CURLOPT_PROXY]=$options['call_socks5_proxy'];
            if (!empty($options['call_socks5_proxy_login']) && !empty($options['call_socks5_proxy_password'])) {
                $curlOptions[CURLOPT_PROXYAUTH]=CURLAUTH_BASIC;
                $curlOptions[CURLOPT_PROXYUSERPWD]=$options['call_socks5_proxy_login'] . ':' . $options['call_socks5_proxy_password'];
            }
            $settle_proxy=true;
        }

        if(!$settle_proxy){
            //http proxy
            if (isset($options['call_http_proxy_host']) && !empty($options['call_http_proxy_host'])) {
                $this->utils->debug_log('http call with http proxy', $options['call_http_proxy_host']);
                $curlOptions[CURLOPT_PROXYTYPE]=CURLPROXY_HTTP;
                $curlOptions[CURLOPT_PROXY]=$options['call_http_proxy_host'];
                $curlOptions[CURLOPT_PROXYPORT]=$options['call_http_proxy_port'];
                if (!empty($options['call_http_proxy_login']) && !empty($options['call_http_proxy_password'])) {
                    $curlOptions[CURLOPT_PROXYAUTH]=CURLAUTH_BASIC;
                    $curlOptions[CURLOPT_PROXYUSERPWD]=$options['call_http_proxy_login'] . ':' . $options['call_http_proxy_password'];
                }
            }
        }

        return $this->callHttp($url, $method, $params, $curlOptions, $headers);
	}

    /**
     *
     * @param  string $url
     * @param  string $method
     * @param  array $params
     * @param  array $curlOptions
     * @param  array $headers
     * @return ($header, $content, $statusCode, $statusText, $errCode, $error, null)
     */
	public function callHttp($url, $method, $params, $curlOptions = null, $headers = null) {
		//call http
		$content = null;
		$header = null;
		$statusCode = null;
		$statusText = '';
		$last_url=null;
		$ch = null;
		$default_http_timeout = $this->getConfig('default_http_timeout');
		$default_connect_timeout = $this->getConfig('default_connect_timeout');
		$method = empty($method) ? 'GET' : $method;

		try {
			$ch = curl_init();

			if (!empty($params)) {

				if ($method == 'GET') {
					if (strpos($url, '?') !== FALSE) {
						//found ?
						$url = rtrim($url, '&') . '&' . http_build_query($params);
					} else {
						//no ?
						$url = $url . '?' . http_build_query($params);
					}
				} elseif ($method == 'POST') {
					if (is_array($params)) {
						curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
					} else {
						curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
					}
				}
                else {
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
                    if (is_array($params)) {
                        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
                    } else {
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
                    }
                }
			}

			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_HEADER, true);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

			//set timeout
			curl_setopt($ch, CURLOPT_TIMEOUT, $default_http_timeout);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $default_connect_timeout);

			$headers = $this->convertArrayToHeaders($headers);
			if (!empty($headers)) {
				curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			}

			if (!empty($curlOptions)) {
				curl_setopt_array($ch, $curlOptions);
			}

			$response = curl_exec($ch);
			$errCode = curl_errno($ch);
			$error = curl_error($ch);
			$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
			$header = substr($response, 0, $header_size);
			$content = substr($response, $header_size);

			$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			$last_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
			// var_dump($url);
			// var_dump($response);

			$statusText = $errCode . ':' . $error;
			// var_dump($statusText);
			curl_close($ch);

		} catch (Exception $e) {
			$this->error_log('http call url:'.$url.', method:' . $method, 'params', $params, 'curlOptions', $curlOptions, 'last_url', $last_url, $e);
        }

		return array($header, $content, $statusCode, $statusText, $errCode, $error, null);
	}

	public function call_tele_api($playerTelephone, &$error = null, $callerId = null, $apiId = null) {
		$error = '';
		$apiName = '';
		if ($apiId === null) {
			# load the first available teleSystem
			$apiIds = $this->getAllCurrentTeleSystemList();
			$default_tele_api = $this->getConfig('default_tele_api');
			$this->utils->debug_log("==============call_tele_api default_tele_api", $default_tele_api);
			$this->utils->debug_log("==============call_tele_api apiIds", $apiIds);
			if(!empty($this->getConfig('telephone_api'))){
				$settings = $this->CI->operatorglobalsettings->getSystemSetting('telephone_api_list');
                $value = $this->CI->operatorglobalsettings->getSettingValueWithoutCache('telephone_api_list');
                $this->CI->utils->debug_log("telephone api list value", $value);
                if(!is_null($value) && !empty($settings)){
                    $this->CI->utils->debug_log("telephone api list", $settings);
                    $apis = array_values($settings['params']['list']);
                    $apiName = isset($settings['params']['list'][$value]) ? $settings['params']['list'][$value] : array_shift($apis);
                    $this->CI->utils->debug_log("System Setting telephone API", $apiName);
                }
			}
			if(!empty($default_tele_api) && in_array($default_tele_api, $apiIds)){
				$apiId = $default_tele_api;
			}
			else{//default is first
				if(!empty($apiName) && in_array($apiName, $apiIds)){
					$apiId = $apiName;
				}else{
					$apiId = $apiIds[0];
				}
			}
		}
		$this->CI->utils->debug_log("==============telephone  apiId", $apiId);
		if ($callerId === null || empty($callerId)) {
			$callerId = $this->getConfig('default_tele_id');
		}

		if (empty($apiId)) {
			$error = 'No any telephone marketing system';
			return null;
		}

		if (empty($callerId)) {
			$error = 'No any caller id';
			return null;
		}

		$api = $this->loadExternalSystemLibObject($apiId);
		//caller Id default is A class
		$result = $api->getCallUrl($playerTelephone, $callerId);

		return $result;
	}

	function compareIP($target_ip, $ip_mask_or_ip) {

		return \Symfony\Component\HttpFoundation\IpUtils::checkIp($target_ip, $ip_mask_or_ip);

		// if (strpos($ip_mask_or_ip, "/") > 0) {
		// 	$mark_len = 32;
		// 	list($ip_str, $mark_len) = explode("/", $ip_mask_or_ip);
		// 	$right_len = 32 - $mark_len;
		// 	return ip2long($target_ip) >> $right_len == ip2long($ip_str) >> $right_len;
		// }else{
		// 	return $target_ip==$ip_mask_or_ip;
		// }
	}

	public function isSMSEnabled() {
		$smsApi = $this->getConfig('sms_api');

		if(TRUE === $this->getConfig('disabled_sms') || empty($smsApi)){
		    return FALSE;
        }

        return TRUE;
	}

	public function isSmtpApiEnabled() {
		$smtpApi = $this->getConfig('current_smtp_api');
		$smtpAPiCredentials = $this->getConfig('smtp_api_info');

		if($this->getConfig('enable_smtp_api') == false || empty($smtpApi) || empty($smtpAPiCredentials))
		    return FALSE;

        return TRUE;
	}

	public function isEmailVerifyEnabled($playerId) {
        #sending email
        $this->CI->load->library(['email_manager']);
        $template = $this->CI->email_manager->template('player', 'player_verify_email');
        $template_enabled = $template->getIsEnableByTemplateName();
        return $template_enabled['enable'];
	}

	public function generateSmsTemplate($searchArr = null, $replaceArr = null, $sms_registration_template = null) {
		if (empty($sms_registration_template)) {
			$sms_registration_template = $this->getOperatorSetting('sms_registration_template');
			if (empty($sms_registration_template)) {
				$sms_registration_template = $this->getConfig('sms_registration_template');
			}
		}

		if (!empty($replaceArr)) {
			$result = str_replace($searchArr, $replaceArr, $sms_registration_template);
		} else {
			$result = $sms_registration_template;
		}

		return $result;
	}

	public function sendSmsByApi($mobileNumber, $msg, $label=null, $playerId = null) {
		$this->CI->load->library(array('sms/sms_sender'));
		if ($this->isEnabledFeature('enabled_send_sms_use_queue_server')) {
			$this->CI->load->model(['queue_result','sms_verification']);
			$this->CI->load->library('lib_queue');

			$mobileNum = $mobileNumber;
			$content = $msg;
			$callerType = Queue_result::CALLER_TYPE_PLAYER;
			$caller = null;
			$state = null;

			$this->CI->lib_queue->addRemoteSMSJob($mobileNum, $content, $callerType, $caller, $state);

			return true;
		} else {
			$this->CI->load->model(['queue_result','sms_verification']);
			$apiName = null;

			$sendSmsData = [];
	        $use_new_sms_api_setting = $this->getConfig('use_new_sms_api_setting');
	        if (!empty($use_new_sms_api_setting)) {
				if (preg_match("/\|/", $mobileNumber)) {
					$convertRecipientNumber = explode('|',$mobileNumber);
					$dialingCode = $convertRecipientNumber[0];
					$mobileNumber = $convertRecipientNumber[1];
				}

				#restrictArea = action type
				$restrictArea = sms_verification::USAGE_SMSAPI_SENDMESSAGE;
				list($apiName, $sms_setting_msg) = $this->CI->sms_sender->getSmsApiNameNew($mobileNumber, $restrictArea);

				$sendSmsData = array(
					'contactNumber' => $mobileNumber,
					'sessionId' => '',
					'code' => '',
					'smsApiUsage' => $restrictArea,
					'smsApiName' => $apiName,
					'ip' => $this->getIP(),
					'playerId' => $playerId,
					'createTime' => $this->getNowForMysql()
				);

				if (!empty($apiName)) {
					$this->CI->sms_verification->addSendSmsRecord($sendSmsData);
				} else {
					$this->error_log('send sms failed to mobileNumber sms_setting_msg' . $mobileNumber, $sms_setting_msg);
				}
			}else{
				if(!is_null($label)){
					$apiName = $this->getSMSApiNameByLabel($label);
				}
			}

			$success = $this->CI->sms_sender->send($mobileNumber, $msg, $apiName);
			if (!$success) {
				$this->error_log('send sms failed to ' . $mobileNumber, $msg);
			}
			return $success;

		}
	}

	public function getSMSApiNameByLabel($label) {
		$apiName = $this->CI->config->item($label, 'sms_api_label');
		$this->CI->utils->debug_log("==============getSMSApiByLabel", "label", $label, "apiName", $apiName);
		return $apiName;
	}

	public function getSmsApiNameByNewSetting($playerId, $mobileNum, $restrictArea, $sessionId){
		$useSmsApi = null;
		$this->CI->load->library(["sms/sms_sender"]);
		$this->CI->load->model(['sms_verification']);
		list($useSmsApi, $sms_setting_msg) = $this->CI->sms_sender->getSmsApiNameNew($mobileNum, $restrictArea);

		$sendSmsData = array(
			'contactNumber' => $mobileNum,
			'sessionId' => $sessionId,
			'code' => '',
			'smsApiUsage' => $restrictArea,
			'smsApiName' => $useSmsApi,
			'ip' => $this->getIP(),
			'playerId' => $playerId,
			'createTime' => $this->getNowForMysql()
		);

		if (!empty($useSmsApi)) {
			$this->CI->sms_verification->addSendSmsRecord($sendSmsData);
		}
		return array($useSmsApi, $sms_setting_msg);
	}

	/**
	 * Get setting from operator_settings (from cache first)
	 *
	 * @param string $name The setting name.
	 * @param string|integer|float $defaultValue The default value while can't get.
	 * @return string|integer|float The setting value.
	 */
	public function getOperatorSetting($name, $defaultValue = null) {
		$this->CI->load->model(['operatorglobalsettings']);
		return $this->CI->operatorglobalsettings->getSettingValue($name, $defaultValue);
	}

	public function getOperatorSettingJson($name, $defaultValue = null) {
		$this->CI->load->model(['operatorglobalsettings']);
		return $this->CI->operatorglobalsettings->getSettingJson($name, 'value', $defaultValue);
	}

	public function putOperatorSetting($name, $value, $field = 'value') {
		$this->CI->load->model(['operatorglobalsettings']);
		return $this->CI->operatorglobalsettings->putSetting($name, $value, $field);
	}

	public function putOperatorSettingJson($name, $value, $field = 'value') {
		$this->CI->load->model(['operatorglobalsettings']);
		return $this->CI->operatorglobalsettings->putSettingJson($name, $value, $field);
	}

	public function isOperatorSettingItemEnabled($name, $item) {
		$this->CI->load->model(['operatorglobalsettings']);

		$jsonArray = $this->CI->operatorglobalsettings->getSettingJson($name, 'value');
		if(empty($jsonArray)){
			$jsonArray =[];
		}
		return in_array($item, $jsonArray);
	}

	/**
	 * Keep head of string and other will replaced with mask_char.
	 *
	 * @param [string] $str The origin string.
	 * @param [integer] $keep_len The string length of head.
	 * @param string $mask_char The char for mask.
	 * @return string The masked string.
	 */
	public function keepHeadString($str, $keep_len, $mask_char = '*'){
		$reStr = '';
		$len = strlen($str);
		if($keep_len >= $len){
			$reStr = $this->keepOnlyString($str, 0, $mask_char, $len);
		}else{
			$reStr = $this->keepOnlyString($str, $keep_len, $mask_char, $len - $keep_len);
		}
		return $reStr;
	}

	/**
	 * Keep tail of string and other will replaced with mask_char.
	 *
	 * @param [string] $str The origin string.
	 * @param [integer] $keep_len The string length of tail.
	 * @param string $mask_char The char for mask.
	 * @return string The masked string.
	 */
	public function keepTailString($str, $keep_len, $mask_char = '*'){
		$reStr = '';
		$len = strlen($str);
		if($keep_len >= $len){
			$reStr = $this->keepOnlyString($str, 0, $mask_char, $len);
		}else{
			$reStr = $this->keepOnlyString($str, -1* $keep_len, $mask_char, $len - $keep_len);
		}
		return $reStr;
	}

	/**
	 * keep string
	 * @param  string $str
	 * @param  int $keep_len <0 means keep only last, >0 means keep first, 0 means all
	 * @return string
	 */
	public function keepOnlyString($str, $keep_len, $mask_char = '*', $fixed_len = 6) {

		if (empty($str)) {
			return str_repeat($mask_char, $fixed_len);
		}

		if ($keep_len > 0) {
			$str = substr($str, 0, $keep_len);
			$result = $str . str_repeat($mask_char, $fixed_len);
		} elseif ($keep_len < 0) {
			$str = substr($str, $keep_len);
			$result = str_repeat($mask_char, $fixed_len) . $str;
		} else {
			$result = str_repeat($mask_char, $fixed_len);
		}

		return $result;
	}

	/**
	 * Mask middle string
	 *
	 * @param string $str The orig string for mask middle string.
	 * @param integer $unmask_len The length NO MASK of prefix and postfix String.
	 * @return string $maskedStr The string after mask.
	 */
	public function maskMiddleStringLite($str, $unmask_len = 4){
		$maskedStr = '';
		$mbLen = mb_strlen($str, 'utf-8');

		if($unmask_len* 2  >=  $mbLen ){
			// full mask string
			$maskedStr = str_repeat('*', $mbLen);
		}else{
			$preStr  = mb_substr ($str, 0, $unmask_len, 'UTF-8');
			$maskedStr .= $preStr;

			$repeatInt = $mbLen - $unmask_len* 2;
			$middleMaskStr = str_repeat('*', $repeatInt);
			$maskedStr .= $middleMaskStr;

			$postfixStr  = mb_substr ($str, mb_strlen($maskedStr, 'utf-8') , $unmask_len, 'UTF-8');
			$maskedStr .= $postfixStr;
		}
		return $maskedStr;
	}

    /**
     * keep string
     * @param  string $str
     * @param  int $start where the string should start
     * @param  int $length the length of mask(*)
     * @param  int $unmask_len the length of first and last words that display unmask
     * @return string
     */
    public function maskMiddleString($str, $start = 0, $length = null, $unmask_len = 4){
        if(!empty($str)){
            $mask = preg_replace ( "/\S/", "*", $str );
            if( is_null ( $length )) {
                $mask = substr ( $mask, $start );
                $res = substr_replace ( $str, $mask, $start );
            }else{
                $mask = substr ( $mask, $start, $length );
                $unmaskLastChars = substr_replace ( $str, $mask, $start, $length );
                $firstChars = substr($str, $start, $unmask_len);
                $res = $firstChars . substr($unmaskLastChars, $unmask_len);
            }
            $str = $res;
        }
        return $str;
    }

	public function generateStatCode($playerUsername) {

		$code = '';

		$player_center_stat_script = $this->getConfig('player_center_stat_script');
		$player_center_stat_server = $this->getConfig('player_center_stat_server');
		$player_center_stat_site_id = $this->getConfig('player_center_stat_site_id');

		if (!empty($player_center_stat_script) && !empty($player_center_stat_server) && !empty($player_center_stat_site_id)) {

			if (empty($playerUsername)) {
				$playerUsername = '';
			}

			$search = ['{player_center_stat_server}', '{player_center_stat_site_id}', '{player_center_stat_user_id}'];
			$replace = [$player_center_stat_server, $player_center_stat_site_id, $playerUsername];
			$code = str_replace($search, $replace, $player_center_stat_script);

		}

		return $code;
	}

	public function getLiveChatLink() {
		$liveChatConfig = $this->utils->getConfig('live_chat');

		# If external live chat url defined, go to external url
		if(array_key_exists('external_url', $liveChatConfig) && !empty($liveChatConfig['external_url'])) {
			if(!is_array($liveChatConfig['external_url'])){
			    return $liveChatConfig['external_url'];
            }

            switch($liveChatConfig['external_url']['system']){
                case 'www':
                case 'm':
                    return $this->getSystemUrl(($this->is_mobile()) ? 'm' : 'www', $liveChatConfig['external_url']['url']);
                    break;
                default:
                    return $this->getSystemUrl($liveChatConfig['external_url']['system'], $liveChatConfig['external_url']['url']);
            }
		}

		//For Demo Purposes Only
		//@Added by Melmark Panugao

		//Original code but not working
		return $this->getSystemUrl('player') . '/pub/live_chat_link/' . $liveChatConfig['www_chat_options']['lang'];

		// return $live_chat_demo_only;
	}

	public function getDevVersionInfo() {
		$targetDB = $this->getActiveTargetDB();
		//hide request id
		$str  = '<span style="display:none;" class="__unique_request_id">'.$this->getRequestId().'</span>';
		$time = date('H:i:s e');
		if ($this->getConfig('debug_version_info')) {
			$branch = '';
			$version_info = PRODUCTION_VERSION . '-' . $this->getRuntimeEnv();

			$str .= <<<EOD
<style type="text/css">
    .version_info{
        display: block;
        position: fixed;
        top: 2px;
        left: 2px;
        background-color: #ff0000;
        z-index: 10000;
        color: #eeeeee;
        padding: 2px
    }
</style>
<span class="version_info"> $version_info on {$targetDB} {elapsed_time} and {memory_usage} at $time </span>
EOD;
		}else{
			$str .= '<span style="display:none;" class="__performance_info"> on '.$targetDB.' {elapsed_time} and {memory_usage} at '.$time.' '.PRODUCTION_VERSION.'</span>';
		}
		return $str;
	}

	private $player_center_template = '';
	private $player_center_theme = '';
	private $player_center_header = '';
	private $player_center_logo = '';
	private $player_center_footer = '';
	private $cms_version = '';
	private $player_center_favicon = '';
	private $player_center_title = '';
	private $player_center_registration = '';
	private $player_center_mobile_login ='';

	public function getPlayerCenterTemplate($check_mobile = true) {

	    if (empty($this->player_center_template)) {

	        //locked template
	        if(!empty($this->getConfig('locked_player_template'))){
	            $locked_player_template=$this->getConfig('locked_player_template');
	            $this->player_center_template = $locked_player_template;
	        }else{
	            $this->CI->load->model(['operatorglobalsettings']);
	            //load from operator settings
	            $this->player_center_template = $this->CI->operatorglobalsettings->getSettingValue('player_center_template', 'stable_center2');
	        }
	    }

		if ($check_mobile) {

			$targetMobileDir = realpath(dirname(__FILE__) . '/../../../player/application/views/' . $this->player_center_template . '/mobile');

			if ($this->is_mobile() && is_dir($targetMobileDir)) {

				return $this->player_center_template . '/mobile';

			}

		}

		return $this->player_center_template;
	}

	private $agency_center_template = NULL;

    public function getAgencyCenterTemplate($check_mobile = true) {

        if (empty($this->agency_center_template)) {

            //locked template
            if(!empty($this->getConfig('locked_agency_template'))){
                $locked_player_template=$this->getConfig('locked_agency_template');
                $this->agency_center_template = $locked_player_template;
            }else{
                $this->agency_center_template = 'default';
            }
        }

        if ($check_mobile) {

            $targetMobileDir = realpath(dirname(__FILE__) . '/../../../player/application/views/' . $this->agency_center_template . '/mobile');

            if ($this->is_mobile() && is_dir($targetMobileDir)) {

                return $this->agency_center_template . '/mobile';

            }

        }

        return $this->agency_center_template;
    }

	public function getPlayerCenterTheme($check_cookie = true) {

		if ($check_cookie && ($preview_theme = $this->CI->input->cookie('preview_theme'))) {
			return $preview_theme;
		}

		if (empty($this->player_center_theme)) {
			$this->CI->load->model(['operatorglobalsettings']);
			//load from operator settings
			$this->player_center_theme = $this->CI->operatorglobalsettings->getSettingJson('player_center_theme', 'value', 'blue');
		}

		return $this->player_center_theme;
	}

    public function setFormValidationLang() {
		$this->CI->load->library('language_function');
		$cur_lang = $this->CI->language_function->getCurrentLanguage();

		switch ($cur_lang) {
		case self::CHINESE_LANG_INT:
			$this->CI->config->set_item('language', 'chinese');
			break;

		case self::INDONESIAN_LANG_INT:
			$this->CI->config->set_item('language', 'indonesian');
			break;

		case self::VIETNAMESE_LANG_INT:
			$this->CI->config->set_item('language', 'veitnamese');
			break;

		default:
			$this->CI->config->set_item('language', 'english');
			break;
		}
	}

	public function isEnabledRollingCommByAgentInSession() {
		$this->CI->load->library('session');
		$agent_id = $this->CI->session->userdata('agent_id');

		return $this->CI->agency_model->isEnabledRollingComm($agent_id);
	}

	public function getPlayerBankDetails($playerId) {
		$this->CI->load->model(array('playerbankdetails'));
		return $this->CI->playerbankdetails->getBankDetails($playerId);
	}

	public function getPlayerPromo($type, $playerId, $promoCmsSettingId = null, $promoCategory = null, $pagination = []) {
		$this->CI->load->model(['player_promo', 'promorules']);

		/** @var \Player_promo $player_promo */
		$player_promo = $this->CI->{"player_promo"};

		/** @var \Promorules $promorules */
		$promorules = $this->CI->{"promorules"};

		switch ($type) {
		case 'mypromo':
			$mypromo = $player_promo->getPlayerActivePromoDetails($playerId);
			return $mypromo;
			break;

		case 'allpromo':
			$promoList = $this->getPlayerAvailablePromoList($playerId, $promoCmsSettingId, $promoCategory, $pagination);
			return $promoList;
			break;

		case 'promojoint' :
            $extra_info = [];

			$promo_list = $this->getPlayerAvailablePromoList($playerId, $promoCmsSettingId);
			$my_promo_raw = $player_promo->getPlayerActivePromoDetails($playerId);
			$first_promo=isset($promo_list['promo_list'][0]) ? $promo_list['promo_list'][0] : null;
			if(!empty($first_promo)){
				$promo_check_stat = $promorules->checkOnlyPromotion($playerId, $first_promo['promorule'], $promoCmsSettingId, 1 != (int)$first_promo['promorule']['disabled_pre_application'], NULL, $extra_info);

				$this->debug_log(__METHOD__, [ 'promo_check_stat' => $promo_check_stat, 'extra_info' => $extra_info ]);

				if(!empty($extra_info['error_message'])){
	                $promo_check_stat = [FALSE, lang($extra_info['error_message'])];
	            }
	            if(!empty($extra_info['error_redirect_url'])){
				    array_push($promo_check_stat, $extra_info['error_redirect_url']);
                }
                if(!empty($extra_info['contact_live_chat_to_apply'])){
                    array_push($promo_check_stat, $extra_info['contact_live_chat_to_apply']);
				}
				if(!empty($extra_info['inform'])){ // # 4, maybe access failure by number index, so access by string key.
					$promo_check_stat['inform'] = $extra_info['inform'];
					$first_promo['inform'] = $extra_info['inform'];
					$promo_list['promo_list'][0]['inform'] = $extra_info['inform'];
	            }
                if($this->getConfig('enabled_friend_referral_promoapp_list')){
                    if(!empty($extra_info['referral_success_count'])){
                        $promo_list['promo_list'][0]['referral_success_count'] = $extra_info['referral_success_count'];
                    }
                }

	            $promo_extra = null;
	            if ($this->getConfig('promo_auto_redirect_to_deposit_page')) {
		            if (!empty($extra_info['redirect_to_deposit'])) {
		            	$promo_extra['redirect_to_deposit'] = $extra_info['redirect_to_deposit'];
		            }
		        }

				// $promo_list = $this->merge_promo_info($promo_list, $my_promo_raw, $promo_check_stat);
				$promo_list = $this->merge_promo_info($promo_list, $my_promo_raw, $promo_check_stat, $promo_extra);

				if ($this->getConfig('enabled_fixed_promo_err_msg')) {
					$promo_list['promo_list'][0]['original_player_allowed_for_promo_mesg'] = $promo_list['promo_list'][0]['player_allowed_for_promo_mesg'];
					$promo_list['promo_list'][0]['player_allowed_for_promo_mesg'] = lang('enabled_fixed_promo_err_msg');
				}

				if($this->getConfig('enabled_promo_countdown_icon')){
					$promo_list['promo_list'][0]['enabled_promo_countdown_icon'] = true;
				}

				return $promo_list;
			}
			return null;
			break;

		case 'firstLogin' :
			$_promoList = $this->utils->getPlayerPromo('promojoint', $playerId, $promoCmsSettingId = null);

			$firstDepositList = [];
			if (isset($_promoList['promo_list'])) {
				foreach ($_promoList['promo_list'] as $_list) {
					if (isset($_list['promorule']) && $_list['promorule']['promoType'] == 0 && $_list['promorule']['depositSuccesionType'] == 0) {
						$firstDepositList[] = $_list;
					}
				}
			}

			return $firstDepositList;
			break;

		default:
			# code...
			break;
		}
	}

    public function getTableAllFieldsName($tableName){
        $fieldName = [];
        $fields = $this->CI->db->field_data($tableName);

        foreach ($fields as $field){
            $fieldName[$field->name] = '';
        }

        return $fieldName;
    }

    /**
     * Used by Utils::getPlayerPromo() output type 'promojoint'
     * @param  array 	$promo_list			output of Utils::getPlayerAvailablePromoList(), all promos available to player (generally one element only)
     * @param  array 	$my_promo_raw		output of Player_promo::getPlayerActivePromoDetails(), player's active promos
     * @param  array 	$promo_check_stat	promo check status generated in Utils::getPlayerPromo() from results of Promorules::checkOnlyPromotion()
     * @param  array 	$promo_extra		extra fields of promo check status
     * @return	array
     */
	protected function merge_promo_info($promo_list, $my_promo_raw, $promo_check_stat, $promo_extra = null) {
		$this->debug_log(__METHOD__, [ 'promo_list' => $promo_list, 'my_promo_raw' => $my_promo_raw, 'promo_check_stat' => $promo_check_stat, 'promo_extra' => $promo_extra ]);
		if (is_array($my_promo_raw)) {
			$my_promo = [];
			// Sort my promos in ruleid-cmsid groups
			foreach ($my_promo_raw as $pr) {
				$pr_ident = "{$pr['promorulesId']}-{$pr['promoCmsSettingId']}";
				if (isset($my_promo[$pr_ident])) {
					$my_promo[$pr_ident][] = $pr;
				}
				else {
					$my_promo[$pr_ident] = [ $pr ];
				}
			}
			// Keep only latest promo in each group
			foreach ($my_promo as $pgkey => $pg) {
				$pdate_latest = null;
				$promo_latest = null;
				foreach ($pg as $key => $p) {
					$pdate = $p['dateApply'];
					if (empty($pdate_latest) || $pdate_latest <= $pdate) {
						$pdate_latest = $pdate;
						$promo_latest = $p;
					}
				}
				$my_promo[$pgkey] = $promo_latest;
			}
		}

		// Merge figures from my_promo into promo_list
		// Merge promo_check_stat, promo_extra into promo_list
		// (This looks illogical, for promo_list may contain multiple rows, while promo_check_stat has only one row)
		// (However, promo_list always contains one element in current usage)
		foreach ($promo_list['promo_list'] as & $lp) {
			$lp['player_allowed_for_promo'] = $promo_check_stat[0];
			$lp['player_allowed_for_promo_mesg'] = lang($promo_check_stat[1]);
            if(isset($promo_check_stat[2])){
                $lp['error_redirect_url'] = $promo_check_stat[2];
                $lp['contact_live_chat_to_apply'] = $promo_check_stat[2];
            }

            if (!empty($promo_extra) && is_array($promo_extra)) {
            	$lp = array_merge($lp, $promo_extra);
            }

			$lp_id = "{$lp['promorule']['promorulesId']}-{$lp['promoCmsSettingId']}";
			$this->utils->debug_log('lp_id', $lp_id);
			if (isset($my_promo[$lp_id])) {
				$lp['player_promo'] = $my_promo[$lp_id];
			}
		}

		return $promo_list;
	}

	public function getPlayerAvailablePromoList($playerId, $promoCmsSettingId = null, $promoCategory = null, $pagination = []) {
        $enabled_log_OGP29899_performance_trace = $this->utils->getConfig('enabled_log_OGP29899_performance_trace');
        global $BM;
		$this->CI->load->model(array('player_model', 'promorules', 'cms_model'));
		$player = $this->CI->player_model->getPlayerById($playerId);

        $data = [
            'promo_list' => []
        ];

        if($player->disabled_promotion){
            return $data;
        }
        if($enabled_log_OGP29899_performance_trace){
            $BM->mark('performance_trace_time_8697');
        } // EOF if($enabled_log_OGP29899_performance_trace){...

		$force_show_deposit_promo = false;
        if(!empty($pagination)){
        	$promo_pagination = $this->CI->promorules->getAllPromoPagination($promoCmsSettingId, $promoCategory, $pagination);
        	$promo_list = $promo_pagination['list'];
        	$data['pagination'] = array('totalRecordCount' => $promo_pagination['totalRecordCount'],
											'totalPages' => $promo_pagination['totalPages'],
											'totalRowsCurrentPage' => $promo_pagination['totalRowsCurrentPage'],
											'currentPage' => $promo_pagination['currentPage']);
            if($enabled_log_OGP29899_performance_trace){
                $BM->mark('performance_trace_time_8701');
            }

			$force_show_deposit_promo = !empty($pagination['force_show_deposit_promo']);
        }else{
        	$promo_list = $this->CI->promorules->getAllPromo(null, null, $promoCmsSettingId, $promoCategory);
            if($enabled_log_OGP29899_performance_trace){
                $BM->mark('performance_trace_time_8704');
            }
        }

        if(empty($promo_list)){
            return $data;
        }
        $cache_condition_promo_on_available_list=$this->utils->getConfig('cache_condition_promo_on_available_list');
        $cache_condition_promo_on_available_list_expired_seconds=$this->utils->getConfig('cache_condition_promo_on_available_list_expired_seconds');
        $available_list = [];
        $startForTime=microtime(true)*1000;
        if($enabled_log_OGP29899_performance_trace){
            $BM->mark('performance_trace_time_8714');
            $performance_trace_time_8726 = [];
            $performance_trace_time_8738 = [];
            $performance_trace_time_8772 = [];
            $performance_trace_time_8779 = [];
            //
            $performance_trace_time_8805 = [];
            $performance_trace_time_8813 = [];
            //
            $performance_trace_time_8840 = [];
            $performance_trace_time_8861 = [];
            //
            $performance_trace_time_8734 = [];
            $performance_trace_time_8747 = [];
            $performance_trace_time_8807 = [];
            $performance_trace_time_8854 = [];
            $performance_trace_time_8887 = [];
        } // EOF if($enabled_log_OGP29899_performance_trace){...


        foreach ($promo_list as &$promo_item) {
            if (empty($promoCmsSettingId) && $promo_item['hide_on_player'] == 2 && !$force_show_deposit_promo || $promo_item['hide_on_player'] == null) {
                //ignore
                continue;
            }

            $promorulesId = $promo_item['promoId'];
                if($enabled_log_OGP29899_performance_trace){
                    $markStr = 'performance_trace_time_8734_promoId'. $promo_item['promoId'];
                    $performance_trace_time_8734[$promo_item['promoId']] = $markStr;
                    $BM->mark($markStr);
                }
            #OGP-1632
            $promorule = $this->CI->promorules->getPromorule($promorulesId);
            $promorule['promoTypeName'] = lang($promorule['promoTypeName']);
            if($enabled_log_OGP29899_performance_trace){
                $markStr = 'performance_trace_time_8726_promoId'. $promo_item['promoId'];
                $performance_trace_time_8726[$promo_item['promoId']] = $markStr;
                $BM->mark($markStr);
            }
            #25515,25516
            if($enabled_log_OGP29899_performance_trace){
                $markStr = 'performance_trace_time_8747_promoId'. $promo_item['promoId'];
                $performance_trace_time_8747[$promo_item['promoId']] = $markStr;
                $BM->mark($markStr);
            }
            $promo_item['enabled_remaining_available'] = false;
            if ($this->utils->getConfig('enabled_promorules_remaining_available')) {
				$requestCount=$this->CI->player_promo->getTotalPromoApproved($promorulesId);
				$remaining_available = $promorule['total_approved_limit'] - $requestCount;
				$promorule['promo_remaining_available'] = $remaining_available <= 0 ? 0 : $remaining_available;
				$promo_item['enabled_remaining_available'] = true;
            }
            if($enabled_log_OGP29899_performance_trace){
                $markStr = 'performance_trace_time_8738_promoId'. $promo_item['promoId'];
                $performance_trace_time_8738[$promo_item['promoId']] = $markStr;
                $BM->mark($markStr);
            }
            #OGP-25722
            $promo_item['enabled_progression_btn'] = false;
			if (!empty($this->utils->getConfig('enabled_progression_btn'))) {
				$promo_item['enabled_progression_btn'] = $this->utils->getConfig('enabled_progression_btn');
			}

			$promo_item['custom_info'] = null;
			$custom_promo_info = $this->getConfig('enabled_custom_promo_info');
			if(!empty($custom_promo_info)){
			 	if(!empty($custom_promo_info[$promo_item['promoCmsSettingId']])){
					$info = $custom_promo_info[$promo_item['promoCmsSettingId']];
					$custom_info = $this->CI->promorules->getCustomPromoInfoByExtraInfo($playerId, $promo_item['promoCmsSettingId'], $info);
					$promo_item['custom_info'] = $custom_info;
				}
			}


			#OGP-25827
			$promo_item['enabled_multiple_tags'] = false;
			if ($this->utils->getConfig('enabled_multiple_type_tags_in_promotions')) {
				$promo_item['enabled_multiple_tags'] = [
					'1' => ['lang_key' => strtoupper(lang("New")), 'cs_class' => 'multiple-tag-new'],
					'2' => ['lang_key' => strtoupper(lang("Favourite")), 'cs_class' => 'multiple-tag-favourite'],
					'3' => ['lang_key' => strtoupper(lang("End Soon")), 'cs_class' => 'multiple-tag-endsoon'],
					'4' => ['lang_key' => strtoupper(lang("Hot")), 'cs_class' => 'multiple-tag-hot'],
				];
			}
			$startTime=microtime(true)*1000;
			$gotAllowedOfPromo=false;
            $hide = false;
            $playerIsAllowed = true;
			if($cache_condition_promo_on_available_list){
				$keyCache='promo_allowed_'.$promorulesId.'-'.$player->playerId.'-'.$player->levelId.'-'.$player->affiliateId.'-'.$player->agent_id;
				// try load from cache
				$jsonPromo=$this->utils->getJsonFromCache($keyCache);
				$this->utils->debug_log('get promo condition from cache', $keyCache, $jsonPromo);
				if(!empty($jsonPromo) && array_key_exists('hide', $jsonPromo) && array_key_exists('playerIsAllowed', $jsonPromo)){
					$hide=$jsonPromo['hide'];
					$playerIsAllowed=$jsonPromo['playerIsAllowed'];
					$gotAllowedOfPromo=true;
				}
			}
            if($enabled_log_OGP29899_performance_trace){
                $markStr = 'performance_trace_time_8772_promoId'. $promo_item['promoId'];
                $performance_trace_time_8772[$promo_item['promoId']] = $markStr;
                $BM->mark($markStr);
            }
			if(!$gotAllowedOfPromo){
	            $hide = false;
	            $playerIsAllowed = $this->CI->promorules->isAllowedPlayerBy($promorulesId, $promorule, $player->levelId, $player->playerId, $player->affiliateId, $hide, $player->agent_id);
                if($enabled_log_OGP29899_performance_trace){
                    $markStr = 'performance_trace_time_8779_promoId'. $promo_item['promoId'];
                    $performance_trace_time_8779[$promo_item['promoId']] = $markStr;
                    $BM->mark($markStr);
                }
	            if($cache_condition_promo_on_available_list){
					// save to cache
					$keyCache='promo_allowed_'.$promorulesId.'-'.$player->playerId.'-'.$player->levelId.'-'.$player->affiliateId.'-'.$player->agent_id;
					$this->utils->saveJsonToCache($keyCache, ['hide'=>$hide, 'playerIsAllowed'=>$playerIsAllowed], $cache_condition_promo_on_available_list_expired_seconds);
					$this->utils->debug_log('save promo condition to cache', $keyCache, $hide, $playerIsAllowed);
	            }
			}
            if($enabled_log_OGP29899_performance_trace){
                $markStr = 'performance_trace_time_8807_promoId'. $promo_item['promoId'];
                $performance_trace_time_8807[$promo_item['promoId']] = $markStr;
                $BM->mark($markStr);
            }

			$this->utils->debug_log('cost of isAllowedPlayerBy', microtime(true)*1000-$startTime);
            if ($hide) {
                $this->utils->debug_log('ingore promotion', $promorulesId, 'player id', $player->playerId);
                continue;
            }

            $promo_item['promorule'] = $promorule;
            $promo_item['promoTypeName'] = $promorule['promoTypeName'];
            $promo_item['hide_date_time'] = $promorule['hide_date'];
            $promo_item['promoThumbnailPath'] = $this->getPromoThumbnailRelativePath();
			$startTime=microtime(true)*1000;
            $promo_item['promoDetails'] = $this->CI->cms_model->decodePromoDetailItem($promo_item['promoDetails']);
			$this->utils->debug_log('cost of decodePromoDetailItem', microtime(true)*1000-$startTime);

                $markStr = 'performance_trace_time_8805_promoId'. $promo_item['promoId'];
                $performance_trace_time_8805[$promo_item['promoId']] = $markStr;
                $BM->mark($markStr);
			//OGP-19313
			if(!$this->CI->promorules->isAllowedByClaimPeriod($promorulesId)){
				//$promo_item['disabled'] = true;
				$playerIsAllowed = false;
			}
            if($enabled_log_OGP29899_performance_trace){
                $markStr = 'performance_trace_time_8813_promoId'. $promo_item['promoId'];
                $performance_trace_time_8813[$promo_item['promoId']] = $markStr;
                $BM->mark($markStr);
            }
            if ($playerIsAllowed) {
                // add resend
                #OGP-1632
                // $promorule = $this->CI->promorules->getPromorule($promorulesId);
                // $promo_item['promorule'] = $promorule;
                $status['enable_resend'] = false; // ! $isVerifiedEmail && $this->promorules->isEmailPromo($promorule);
                $promo_item['status'] = $promorule['status'];
                $promo_item['disabled_pre_application'] = $promorule['disabled_pre_application'] == '1';
            } else {
                $promo_item['disabled_pre_application'] = true;
            }
			$promo_item['disabled'] = !$playerIsAllowed;

            // $currentPlayerCenterLang = $this->CI->language_function->getCurrentLangForPromo(true);
            if($enabled_log_OGP29899_performance_trace){
                $markStr = 'performance_trace_time_8854_promoId'. $promo_item['promoId'];
                $performance_trace_time_8854[$promo_item['promoId']] = $markStr;
                $BM->mark($markStr);
            }
            if($this->isEnabledFeature("enable_multi_lang_promo_manager")){
            	$promo_multi_lang = $this->promoItemMultiLangFields($promo_item);
            	$promo_item = array_merge($promo_item, $promo_multi_lang);
            }
            if($enabled_log_OGP29899_performance_trace){
                $markStr = 'performance_trace_time_8840_promoId'. $promo_item['promoId'];
                $performance_trace_time_8840[$promo_item['promoId']] = $markStr;
                $BM->mark($markStr);
            }
            switch($promo_item['claim_button_link']){
                case 'deposit':
                    $claim_button_url = $this->getPlayerDepositUrl();
                    break;
                case 'referral':
                    $claim_button_url = $this->getPlayerReferralUrl();
                    break;
                case 'custom':
                default:
                    $claim_button_url = '';
                    if(!empty($promo_item['claim_button_url'])){
                        $claim_button_url = $promo_item['claim_button_url'];
                    }
            }
            $promo_item['claim_button_url'] = $claim_button_url;
            // Check promo Can not apply
			if ($this->isEnabledFeature("hide_promotion_if_player_doesnt_meet_the_conditions")) {
				$startTime=microtime(true)*1000;
				$gotCheckAvailablePromo=false;
				if($cache_condition_promo_on_available_list){
					$keyCache='promo_check_available_'.$promorulesId.'-'.$playerId.'-'.$promoCmsSettingId;
					// try load from cache
					$jsonPromo=$this->utils->getJsonFromCache($keyCache);
					$this->utils->debug_log('get promo condition from cache', $keyCache, $jsonPromo);
					if(!empty($jsonPromo) && array_key_exists('promo_check_stat', $jsonPromo)){
						$promo_check_stat=$jsonPromo['promo_check_stat'];
						$promo_item['promo_check_player_allowed'] = $promo_check_stat[0];
						$promo_item['promo_check_mesg'] = $promo_check_stat[0] ? null : lang($promo_check_stat[1]);
						$gotCheckAvailablePromo=true;
					}
				}

				if(!$gotCheckAvailablePromo){
                    if($enabled_log_OGP29899_performance_trace){
                        $markStr = 'performance_trace_time_8861_promoId'. $promo_item['promoId'];
                        $performance_trace_time_8861[$promo_item['promoId']] = $markStr;
                        $BM->mark($markStr);
                    }
					$extra = [];
					$promo_check_stat = $this->CI->promorules->checkOnlyPromotion($playerId, $promo_item['promorule'], $promoCmsSettingId, false, null, $extra);
					$promo_item['promo_check_player_allowed'] = $promo_check_stat[0];
					$promo_item['promo_check_mesg'] = $promo_check_stat[0] ? null : lang($promo_check_stat[1]);
                    if($enabled_log_OGP29899_performance_trace){
                        $markStr = 'performance_trace_time_8887_promoId'. $promo_item['promoId'];
                        $performance_trace_time_8887[$promo_item['promoId']] = $markStr;
                        $BM->mark($markStr);
                    }
		            if($cache_condition_promo_on_available_list){
						$keyCache='promo_check_available_'.$promorulesId.'-'.$playerId.'-'.$promoCmsSettingId;
						$this->utils->saveJsonToCache($keyCache, ['promo_check_stat'=>$promo_check_stat], $cache_condition_promo_on_available_list_expired_seconds);
						$this->utils->debug_log('save checkOnlyPromotion to cache', $keyCache, $promo_check_stat);
					}
				}

				$this->utils->debug_log('cost of checkOnlyPromotion', microtime(true)*1000-$startTime);

				if(!$promo_item['promo_check_player_allowed']){
					continue;
				}
			}

			$this->utils->debug_log('isAllowedByClaimPeriod $playerIsAllowed', $promo_item);
            $available_list[] = $promo_item;
        } // EOF foreach ($promo_list as &$promo_item) {...
		$this->utils->debug_log('cost of foreach promo', microtime(true)*1000-$startForTime);
        if($enabled_log_OGP29899_performance_trace){
            $BM->mark('performance_trace_time_8908');
        }

		$data['promo_list'] = $available_list;

        if( $enabled_log_OGP29899_performance_trace ){
            $elapsed_time = [];
            /// for promorules::getAllPromoPagination() ,cast = 8701 - 8697
            // performance_trace_time_8701
            // performance_trace_time_8697
            if(! empty($performance_trace_time_8701) ){
                $elapsed_time['8701_8697'] = $BM->elapsed_time('performance_trace_time_8701', 'performance_trace_time_8697');
            }
            /// for promorules::getAllPromo()
            // performance_trace_time_8704
            // performance_trace_time_8697
            if(! empty($performance_trace_time_8704) ){
                $elapsed_time['8704_8697'] = $BM->elapsed_time('performance_trace_time_8704', 'performance_trace_time_8697');
            }
            /// for foreach ($promo_list as &$promo_item)
            // performance_trace_time_8908
            // performance_trace_time_8714
            $elapsed_time['8908_8714'] = $BM->elapsed_time('performance_trace_time_8714', 'performance_trace_time_8908');
            /// for promorules->getPromorule() of foreach
            // performance_trace_time_8726[promoId]
            // performance_trace_time_8734[promoId]
            $_elapsed_time_list = $this->_script_elapsed_time_list( $performance_trace_time_8734, '8734', $performance_trace_time_8726,'8726' ); // 8734_8726
            $elapsed_time = array_merge($elapsed_time , $_elapsed_time_list);
            // foreach($performance_trace_time_8726 as $_promoId => $_trace_time_8726){
            //     if( ! empty($performance_trace_time_8734[$_promoId]) ){
            //         $elapsed_time['8726_8734'][$_promoId] = $BM->elapsed_time($performance_trace_time_8726[$_promoId], $performance_trace_time_8734[$_promoId]);
            //     }else{
            //         $elapsed_time['8726_8734'][$_promoId] = 'performance_trace_time_8734 is empty';
            //     }
            // }
            //
            /// for player_promo->getTotalPromoApproved() of foreach
            // performance_trace_time_8738[promoId]
            // performance_trace_time_8747[promoId]
            $_elapsed_time_list = $this->_script_elapsed_time_list($performance_trace_time_8747, '8747', $performance_trace_time_8738, '8738' ); // 8747_8738
            $elapsed_time = array_merge($elapsed_time , $_elapsed_time_list);
            /// for promorules->isAllowedPlayerBy() of foreach
            // performance_trace_time_8807[promoId]
            // performance_trace_time_8772[promoId]
            $_elapsed_time_list = $this->_script_elapsed_time_list($performance_trace_time_8772, '8772', $performance_trace_time_8807, '8807'); // 8772_8807
            $elapsed_time = array_merge($elapsed_time , $_elapsed_time_list);
            /// for promorules->isAllowedByClaimPeriod() of foreach
            // performance_trace_time_8813[promoId]
            // performance_trace_time_8805[promoId]
            $_elapsed_time_list = $this->_script_elapsed_time_list( $performance_trace_time_8805, '8805', $performance_trace_time_8813, '8813'); // 8805_8813
            $elapsed_time = array_merge($elapsed_time , $_elapsed_time_list);
            /// for promoItemMultiLangFields() of foreach
            // performance_trace_time_8854[promoId]
            // performance_trace_time_8840[promoId]
            $_elapsed_time_list = $this->_script_elapsed_time_list($performance_trace_time_8854,'8854', $performance_trace_time_8840, '8840' );// 8854_8840
            $elapsed_time = array_merge($elapsed_time , $_elapsed_time_list);
            /// for promorules->checkOnlyPromotion() of foreach
            // performance_trace_time_8887[promoId]?
            // performance_trace_time_8861[promoId]?
            $_elapsed_time_list = $this->_script_elapsed_time_list($performance_trace_time_8887,'8887', $performance_trace_time_8861, '8861' );// 8887_8861
            $elapsed_time = array_merge($elapsed_time , $_elapsed_time_list);
            //
            /// DONOT apped any element,
            // That will affects the method, Api::getPromoCmsItemDetails().
            // in the line, $promo_list = array_pop($allpromo);
            // $data['elapsed_time'] = $elapsed_time;

            $this->debug_log('getPlayerAvailablePromoList elapsed_time:', $elapsed_time
                , 'playerId:', $playerId
                , 'promoCmsSettingId:', $promoCmsSettingId
                , 'promoCategory:', $promoCategory
                , 'enabled_isAllowedPlayerBy_with_lite:', $this->CI->config->item('enabled_isAllowedPlayerBy_with_lite')
            );
            $elapsed_time = [];
            unset($elapsed_time);
        }

		// $this->debug_log('---------------------------getPlayerAvailablePromoList data', $data);
		return $data;
	}

	/*
	 * validate if the input is base64_encode string
	 * OGP-16208
	 *
	 * @param  string  $input  the input string
	 * @return boolean
	 */
    public function isBase64Encode($input){
        return (base64_encode(base64_decode($input)) === $input);
	}

	/**
	 * Return multi-lang promo fields in promocmssettings.promo_multi_lang
	 * Modified from elvis code in Utils::getPlayerAvailablePromoList()
	 * OGP-15994
	 *
	 * @see		Utils::getPlayerAvailablePromoList()
	 * @see		Comapi_lib::get_promos_bare()
	 * @param	array 	$promo_item		promo row array from Promorules::getAllPromo()
	 * @return	assoc array of [ 'promoName', 'promoDescription', 'promoDetails', 'promoThumbnail' ]
	 */
	public function promoItemMultiLangFields($promo_item) {
	    $this->CI->load->model(['cms_model']);
		$multiPromoItems = @json_decode($promo_item['promo_multi_lang'], true);

		$ret = [
			'promoName'			=> $promo_item['promoName'] ,
			'promoDescription'	=> $promo_item['promoDescription'] ,
			'promoDetails'		=> $promo_item['promoDetails'] ,
			'promoThumbnail'	=> $promo_item['promoThumbnail']
		];

		if (empty($multiPromoItems)){
			return $ret;
		}

		$promo_lang = $this->CI->language_function->getCurrentLangForPromo(true);

		// $this->utils->debug_log(__METHOD__, 'promo_lang', $promo_lang, 'multiPromoItems', $multiPromoItems);

		$default4promo_lang = 'en';
		if( empty($multiPromoItems['multi_lang'][$promo_lang]) ){
			$promo_lang = $default4promo_lang;
		}
	    //OGP-9767 If the selected language has no data, it will set to english(default)
	    // NOTE: All 4 fields must be ready (not empty) for current language, or will drop back to en
	    if($multiPromoItems['multi_lang'][$promo_lang]['promo_title_'.$promo_lang] == null ||
	        $multiPromoItems['multi_lang'][$promo_lang]['short_desc_'.$promo_lang] == null ||
	        $multiPromoItems['multi_lang'][$promo_lang]['details_'.$promo_lang] == null ||
	        $multiPromoItems['multi_lang'][$promo_lang]['banner_'.$promo_lang] == null)
	        $promo_lang = $default4promo_lang;

	    // multi-lang name
	    $newPromoName = $multiPromoItems['multi_lang'][$promo_lang]['promo_title_'.$promo_lang];
	    $ret['promoName'] = $newPromoName ?: $promo_item['promoName'];

	    // multi-lang desc
	    $newPromoDesc = $multiPromoItems['multi_lang'][$promo_lang]['short_desc_'.$promo_lang];
	    $ret['promoDescription'] = $newPromoDesc ?: $promo_item['promoDescription'];

	    // multi-lang details
	    $newPromoDetails = $multiPromoItems['multi_lang'][$promo_lang]['details_'.$promo_lang];
	    $ret['promoDetails'] = $newPromoDetails ?: $promo_item['promoDetails'];
        $ret['promoDetails'] = $this->CI->cms_model->decodePromoDetailItem($ret['promoDetails']);

	    // multi-lang thumbnail
	    $newPromothumbnail = $multiPromoItems['multi_lang'][$promo_lang]['banner_'.$promo_lang];
	    $ret['promoThumbnail'] = $newPromothumbnail;

	    return $ret;
	}

	/**
	 *
	 * overview : View of User setting page
	 *
	 * @param  none
	 * @return String 	SBE Current Logo
	 */
	public function setSBELogo() {
		// $this->CI->load->model('operatorglobalsettings');
		// $logoSettings = $this->CI->operatorglobalsettings->getSettingJson('sys_default_logo');
		// return $this->appendCmsVersionToUri('/resources/images/'.$logoSettings['path'] . $logoSettings['filename']);
		return $this->getUploadedSysLogoUrl();
	}

	public function useSystemDefaultLogo() {
		// $this->CI->load->model('operatorglobalsettings');
		// $logoSettings = $this->CI->operatorglobalsettings->getSettingJson('sys_default_logo');
		// return $logoSettings['use_sys_default'];
		$sys_logo = $this->getSysLogoSettingFromOperatorSettings();
		return $sys_logo['use_sys_default'];
	}

	public function getAvailableShoppingList() {
		$this->CI->load->model(array('shopping_center', 'shopper_list'));
		$allRecords = $this->CI->shopping_center->getData(null, Shopping_center::STATUS_NORMAL, false);
		$availableItems = array();
		if (!empty($allRecords)) {
			foreach ($allRecords as $key => $value) {
				$usedItem = count($this->CI->shopper_list->getShopperList($value['id'], Shopper_list::APPROVED));
				$totalItem = $value['how_many_available'];
				$available = (int) $totalItem - (int) $usedItem;
				if ($available) {
					$availableItems[] = $value;
				}
			}
		}
		return $availableItems;
	}

	public function isUploadedLogoExist() {
		// $this->CI->load->model('operatorglobalsettings');
		// $logoSettings = $this->CI->operatorglobalsettings->getSettingJson($this->getDefaultLogoSettingName());
		// $file_loc = $_SERVER['DOCUMENT_ROOT'] . '/resources/images/' . $logoSettings['path'] . $logoSettings['filename'];
		$file_loc = $this->getUploadedSysLogoRealPath();

		if (!file_exists($file_loc)) {
			return false;
		}

		return true;
	}

	public function isLogoOperatorSettingsExist() {
		$this->CI->load->model('operatorglobalsettings');
		return $this->CI->operatorglobalsettings->existsSetting($this->getDefaultLogoSettingName());
	}

	public function isLogoSetOnDB() {
		// $this->CI->load->model('operatorglobalsettings');
		// $settingName = $this->getDefaultLogoSettingName();
		// $sysLogoValue = $this->CI->operatorglobalsettings->getSettingJson($settingName);

		// if (trim($sysLogoValue['filename']) != "") {
		// 	return true;
		// }
		// return false;
		$sys_logo = $this->getSysLogoSettingFromOperatorSettings();
		return !empty(trim($sys_logo['filename']));
	}

	public function getDefaultLogoSettingName() {
		return 'sys_default_logo';
	}

	public function createSmsContent($code, $useSmsApi = null) {
		if(is_null($useSmsApi)){
			$useSmsApi = $this->CI->sms_sender->getSmsApiName();
		}
		$this->utils->debug_log("=============createSmsContent: ", "code", $code, "useSmsApi", $useSmsApi);

		$msg = null;
		$sms_content = $this->getConfig('sms_content_template');
		$sms_content_custom = $this->getConfig('sms_content_custom');
		if(isset($sms_content_custom[$useSmsApi])){
			$sms_content = $sms_content_custom[$useSmsApi];
		}

		if (!empty($sms_content)) {
			if (strpos($sms_content, '{minutes}') === FALSE) {
				$msg = str_replace(['{code}'],[$code],$sms_content);
			} else {
				$msg = str_replace(['{code}', '{minutes}'],
					[$code, round($this->utils->getConfig('sms_valid_time') / 60)],
					$sms_content);
			}
		} else {
			# This config is used to force sms content language
			if (!empty($this->config->item('sms_lang'))) {
				$this->utils->debug_log("Sending SMS with configured lang (1=en, 2=zh-cn):", $this->config->item('sms_lang'));
				$msg = sprintf(lang('Your verification code is [%s]', $this->config->item('sms_lang')), $code);
			} else {
				$msg = sprintf(lang('Your verification code is [%s]'), $code);
			}
		}
		$this->utils->debug_log("=============createSmsContent msg: ", $msg);
		return $msg;
	}

	public function getTotalDepositWithdrawalBonusCashbackByPlayers($playerId, $dateFrom = null, $dateTo = null) {
		$this->CI->load->model(array('transactions'));

		return $this->CI->transactions->getTotalDepositWithdrawalBonusCashbackByPlayers($playerId, $dateFrom, $dateTo);
	}

	public function getAllBankTypes() {
		$this->CI->load->library(array('player_functions'));

		return $this->CI->player_functions->getAllBankType();
	}

    public function getWithdrawMinMax($playerId) {
        $this->CI->load->model(array('group_level', 'operatorglobalsettings', 'transactions'));

        $playerWithdrawRule = $this->CI->group_level->getPlayerWithdrawRule($playerId);
        if(!isset($playerWithdrawRule[0])){
            $this->utils->error_log("=====================cannot get playerWithdrawRule", $playerWithdrawRule);
            return false;
        }
        $rule = $playerWithdrawRule[0];
        $data['deposit_times'] = $this->CI->transactions->getTransactionCount(array(
            'to_id' => $playerId,
            'to_type' => Transactions::PLAYER,
            'transaction_type' => Transactions::DEPOSIT,
            'status' => Transactions::APPROVED,
        ));

        $data['daily_max_withdraw_amount']    = $rule['dailyMaxWithdrawal'];
        $data['withdraw_times_limit']         = $rule['withdraw_times_limit'];
        $data['max_withdraw_per_transaction'] = $rule['max_withdraw_per_transaction'];
        $data['min_withdraw_per_transaction'] = $rule['min_withdrawal_per_transaction'];
        $data['max_monthly_withdrawal'] 	  = $rule['max_monthly_withdrawal'];

        if(empty($data['min_withdraw_per_transaction'])){
            $min_withdraw = $this->CI->operatorglobalsettings->getSettingDoubleValue('min_withdraw');
            $data['min_withdraw_per_transaction'] = $min_withdraw;
        }

        if(($data['deposit_times'] == 0) && isset($rule['max_withdrawal_non_deposit_player'])  ){
        	$data['max_withdrawal_non_deposit_player'] = $rule['max_withdrawal_non_deposit_player'];
            $data['max_withdraw_per_transaction'] = $rule['max_withdrawal_non_deposit_player'];
        }

        return $data;
    }

	public function renderLang($lang_key, $argv = null) {
		if (empty($argv)) {

			return lang($lang_key);
		} else {
			if (!is_array($argv)) {
				$argv = [$argv];
			}
			return vsprintf(lang($lang_key), $argv);
		}
	}

    /**
     * For to replace the args position changed by another language/wording.
     *
     * @param string $lang_key
     * @param array $replace_list
     * @return void
     */
    public function renderLangWithReplaceList($lang_key, $replace_list = []){

        $_langStr = $this->renderLang($lang_key, array_values($replace_list));
        if( $_langStr != $lang_key ){
            // utils::renderLang() had working in the $lang_key.
            $langStr = $_langStr;
        }

        $healthy = array_keys($replace_list);
        $yummy = array_values($replace_list);
        /// reference to Example #1 in https://www.php.net/manual/en/function.str-replace.php
        // $phrase  = "You should eat fruits, vegetables, and fiber every day.";
        // $healthy = array("fruits", "vegetables", "fiber");
        // $yummy   = array("pizza", "beer", "ice cream");
        $newphrase = str_replace($healthy, $yummy, $langStr);
        return $newphrase;
    }// EOF renderLangWithReplaceList()
    /**
     * Check the lang_key has exists in lang array.
     *
     * @param string $lang_key
     * @return boolean
     */
    public function isExistsInLang($lang_key){
        $isExists = false;
        if ($lang_key == lang($lang_key)){
            // non-defined
        }else{
            $isExists = true;
        }
        return $isExists;
    } // EOF isExistsInLang
    /**
     *
     * @return mixed
     */
	public function get_favorites($player_id = null) {
		$this->CI->load->library(array('authentication'));
		$player_id = empty($player_id) ? $this->CI->authentication->getPlayerId() : $player_id;
		$games = $this->CI->db
			->distinct()
			->from('favorite_game')
			->where('player_id', $player_id)
			->order_by('created_at', 'desc')
			->get()
			->result_array();

		return $games;
	}

	public function getPlayerMyFavoriteGames($player_id){
        $this->CI->load->model(['player_preference', 'game_description_model']);

        $player_myfavorites = $this->CI->player_preference->getPlayerMyFavorites($player_id);

        if(empty($player_myfavorites)){
            return FALSE;
        }

        $games = $this->CI->game_description_model->getGameDescriptionByIdList(array_keys($player_myfavorites));

        $options = [

        ];

        foreach ($games as &$game) {
            $game = $this->_process_game($game, $options);

            $game['image_url'] = $this->getPlayerCmsUrl('/images/game_icon' . ((empty($game['image_location'])) ? '' : '/' . $game['image_location']) . '/' . $game['game_code'] . '.png' );
        }

        return $games;
    }

	protected function _process_game($game, $options){
	    extract($options);

        if(empty($prefix_url)){
            $prefix_url = '/iframe_module';
        }

        if(empty($mode)){
            $mode = 'real';
        }

        if(empty($language)){
            $language = 2;
        }

        if(empty($is_mobile)){
            $is_mobile = $this->is_mobile();
        }

        switch ($game['game_platform_id']) {
            case HB_API:
                $game['url_without_prefix'] = implode('/', ['/goto_habagame', $mode, $game['game_code'], $language, $is_mobile]);
                $game['url'] = $prefix_url . $game['url_without_prefix'];
                $game['image_location'] = 'habanero';
                break;
            case UC_API:
                $game['url_without_prefix'] = implode('/', ['/goto_ucgame', $mode, $game['game_code']]);
                $game['url'] = $prefix_url . $game['url_without_prefix'];
                $game['image_location'] = 'uc8';
                break;
            case ISB_API:
                $game['url_without_prefix'] = implode('/', ['/goto_isb_game', $game['game_code'], $mode, $is_mobile, $language]);
                $game['url'] = $prefix_url . $game['url_without_prefix'];
                $game['image_location'] = 'isb';
                break;
            case OG_API:
                $game['url_without_prefix'] = implode('/', ['/goto_oggame']);
                $game['url'] = $prefix_url . $game['url_without_prefix'];
                break;
            case AGIN_API:
                # http://w_without_prefixww.demo2.tripleoneteu/rl, m/goto_agingame/default/6
                $siteName = 'default';
                $gameType = 0;
                $is_mobile = null;
                $mode = null;
                $game['url_without_prefix'] = implode('/', ['/goto_agingame/'.$siteName.'/'.$gameType, $is_mobile, $mode]);
                $game['url'] = $prefix_url . $game['url_without_prefix'];
                break;
            case BETEAST_API:
                $game['url_without_prefix'] = implode('/', ['/goto_beteastgame']);
                $game['url'] = $prefix_url . $game['url_without_prefix'];
                break;
            case EBET_BBIN_API:
                $game['url_without_prefix'] = implode('/', ['/goto_ebetbbingame']);
                $game['url'] = $prefix_url . $game['url_without_prefix'];
                break;
            case SPORTSBOOK_API:
                $game['url_without_prefix'] = implode('/', ['/goto_ipm', $is_mobile]);
                $game['url'] = $prefix_url . $game['url_without_prefix'];
                break;
            case MG_API:
                $game['url_without_prefix'] = implode('/', ['/goto_mggame', 2, $game['game_code'], $mode, $is_mobile]);
                $game['url'] = $prefix_url . $game['url_without_prefix'];
                $game['image_location'] = 'microgame';
                break;
            case IMPT_API:
                $game['url_without_prefix'] = implode('/', ['/goto_imptgame/default', $game['game_code'], $mode]);
                $game['url'] = $prefix_url . $game['url_without_prefix'];
                $game['image_location'] = 'playtech';
                break;
            case PRAGMATICPLAY_API:
                $game['url_without_prefix'] = implode('/', ['/goto_pragmaticplaygame', $game['game_code'], $mode]);
                $game['url'] = $prefix_url . $game['url_without_prefix'];
                $game['image_location'] = 'pragmatic';
                break;
            case PNG_API:
                $game['url_without_prefix'] = implode('/', ['/goto_pnggame', $game['attributes'], $mode]);
                $game['url'] = $prefix_url . $game['url_without_prefix'];
                $game['image_location'] = 'png';
                break;
            case SPADE_GAMING_API:
                $game['url_without_prefix'] = implode('/', ['/goto_spadegame', $game['game_code'], $mode]);
                $game['url'] = $prefix_url . $game['url_without_prefix'];
                $game['image_location'] = 'spadegaming';
                break;
            case JUMB_GAMING_API:
                $game['url_without_prefix'] = implode('/', ['/goto_jumbogame/slots', $game['game_code']]);
                $game['url'] = $prefix_url . $game['url_without_prefix'];
                $game['image_location'] = 'jumbo';
                break;
            case QT_API:
                $game['url_without_prefix'] = implode('/', ['/gotogame', QT_API, $game['game_code'], $mode]);
                $game['url'] = $prefix_url . $game['url_without_prefix'];
                $game['image_location'] = 'qt';
                break;
            case TTG_API:
                $gameAttr = json_decode($game['attributes'], true);

                if (!empty($gameAttr)) {
                    $game['url_without_prefix'] = implode('/', ['/goto_ttggame', $gameAttr['gameId'], $game['game_code'], '0', $mode]);
                    $game['url'] = $prefix_url . $game['url_without_prefix'];
                } else {
                    $game['url_without_prefix'] = implode('/', ['/goto_ttggame', $game['game_code'], '0', $mode]);
                    $game['url'] = $prefix_url . $game['url_without_prefix'];
                }

                $game['image_location'] = 'ttg';

                break;
            case YOPLAY_API:
                $game['url_without_prefix'] = implode('/', ['/goto_yoplaygame', $game['game_code']]);
                $game['url'] = $prefix_url . $game['url_without_prefix'];
                $game['image_location'] = 'yoplay';
                break;
            case DT_API:
                $game['url_without_prefix'] = implode('/', ['/goto_dtgame', $game['game_code'], $mode, $is_mobile]);
                $game['url'] = $prefix_url . $game['url_without_prefix'];
                $game['image_location'] = 'dt';
                break;
            case EBET_BBTECH_API:
                $game['url_without_prefix'] = implode('/', ['/goto_ebetbbtech/', $game['game_code'], $mode]);
                $game['url'] = $prefix_url . $game['url_without_prefix'];
                $game['image_location'] = 'bbtech';
                break;
            case PT_API:
                $game['url_without_prefix'] = implode('/', ['/goto_ptgame/default', $game['game_code'], $mode]);
                $game['url'] = $prefix_url . $game['url_without_prefix'];
                $game['image_location'] = 'playtech';
                break;
            case OPUS_API:
                $game['url_without_prefix'] = implode('/', ['/goto_opus/slots/pp', $game['game_code'], $mode]);
                $game['url'] = $prefix_url . $game['url_without_prefix'];
                $game['image_location'] = 'pragmatic';
                break;
            case FG_API:
                $game['url_without_prefix'] = implode('/', ['/goto_fg', $game['sub_game_provider'], $game['game_code'], $mode, $is_mobile]);
                $game['url'] = $prefix_url . $game['url_without_prefix'];
                $game['image_location'] = 'flow_gaming';
                break;
            case RTG_API:
                $game['url_without_prefix'] = implode('/', ['/goto_rtg', $game['sub_game_provider'], $game['game_code'], $mode]);
                $game['url'] = $prefix_url . $game['url_without_prefix'];
                $game['image_location'] = 'rtg';
                break;
            case T1LOTTERY_API:
                $game['url_without_prefix'] = implode('/', ['/goto_t1lottery', 'official', $game['game_code'], $mode]);
                $game['url'] = $prefix_url . $game['url_without_prefix'];
                $game['image_location'] = 't1lottery';
                break;
            case IBC_API:
                $game['url_without_prefix'] = implode('/', ['/goto_ibc', $game['game_code'], $mode]);
                $game['url'] = $prefix_url . $game['url_without_prefix'];
                $game['image_location'] = 'ibc';
                break;
            case KYCARD_API:
                $attributes = json_decode($game['attributes']);
                $game['url_without_prefix'] = implode('/', ['/goto_kycard', $attributes->game_launch_code]);
                $game['url'] = $prefix_url . $game['url_without_prefix'];
                $game['image_location'] = 'kycard';
                break;
            default:
                $game['url'] = '';
                break;
        }

        $game['game_name'] = lang($game['game_name']);
        $game['image_url'] = $this->getPlayerCmsUrl('/images/game_icon' . ((empty($game['image_location'])) ? '' : '/' . $game['image_location']) . '/' . $game['game_code'] . '.png' );

        return $game;
    }

    public function get_recently_played() {
		$this->CI->load->library(array('authentication','game_list_lib'));
		$this->CI->load->model(array('favorite_game_model'));

		$player_id = $this->CI->authentication->getPlayerId();
		$game_platform = $this->getAllCurrentGameSystemList();

		$games = $this->CI->db
			->distinct()
			// ->select('game_description.game_platform_id, game_description.game_type_id,  game_description.external_game_id, game_description.game_code')
			->select('game_description.*, game_type.game_type_code')
			->from('total_player_game_hour')
			->join('game_description', 'game_description.id = total_player_game_hour.game_description_id')
			->join('game_type', 'game_type.id = game_description.game_type_id')
			->where('total_player_game_hour.player_id', $player_id)
			->where_in('total_player_game_hour.game_platform_id', $game_platform)
			->order_by('total_player_game_hour.id', 'desc')
			// ->limit($this->getRecentlyGameDisplayLimit())
			->limit(10)
			->get()
			->result_array();

		$favorite_games = $this->CI->favorite_game_model->get_favorites($player_id);
		$favorite_games = array_column($favorite_games, 'url');
        $language = $this->CI->language_function->convertGameLauncherLanguage($this->CI->language_function->getCurrentLanguage());
		$options = [
			'prefix' => 'iframe_module',
			'mode' => 'real',
			'language' => $language,
			'is_mobile' => $this->is_mobile(),
            'favorite_games' => $favorite_games,
		];


        $games = $this->CI->game_list_lib->getGameUrl($games, $options);

		return $games;
	}

	public function getActiveGameList($game_platform = NULL){
        $this->CI->load->library(array('authentication'));
        $this->CI->load->model(array('favorite_game_model'));

        if(empty($game_platform)){
            $game_platform = $this->getAllCurrentGameSystemList();
        }else{
            $game_platform = (is_array($game_platform)) ? $game_platform : [$game_platform];
        }

        $games = $this->CI->db
            ->distinct()
            ->select('*')
            ->from('game_description')
            ->where_in('game_description.game_platform_id', $game_platform)
            ->where('flag_show_in_site', 1)
            ->get()
            ->result_array();

        $mode = 'real';

        $language = $this->CI->language_function->convertGameLauncherLanguage($this->CI->language_function->getCurrentLanguage());

        $is_mobile = $this->is_mobile();

        $options = [
            'prefix_url' => '/iframe_module',
            'mode' => $mode,
            'language' => $language,
            'is_mobile' => $is_mobile
        ];

        foreach ($games as &$game) {
            $game = $this->_process_game($game, $options);
        }

        return $games;
    }

	/**
	 *
	 *	Check if using http or https
	 *
	 */
	public function getServerProtocol() {
		$protocol = isset($_SERVER["HTTPS"]) ? 'https' : 'http';
		return $protocol;
	}

    public function loopCSV($csv_file, $ignore_first_row, &$cnt, &$message, $callback){
		$cnt=0;
    	$success=false;

    	if(!file_exists($csv_file)){
    		return false;
    	}

		$file = fopen($csv_file, "r");
		if ($file !== false) {

			try{
				while (!feof($file)) {
					$tmpData = fgetcsv($file);

					// $this->debug_log('debug every row', $tmpData);

					if($cnt==0 && $ignore_first_row){
						//ignore header
						$this->debug_log('ignore first row', $tmpData);
						$cnt++;
						continue;
					}

					if (empty($tmpData)) {
						$this->debug_log('ignore empty row');
						$cnt++;
						continue;
					}

					$stop_flag=false;

					$success=$callback($cnt, $tmpData, $stop_flag);
					$cnt++;

					if($stop_flag){
						break;
					}
				}
			} finally {
				fclose($file);
			}

		} else {
			$this->error_log('open csv failed');
			$message=lang('Open CSV File Failed');
		}

		return $success;
    }


    /**
     * The function array_combine()
     * If they are not of same size
     *
     * ref. to https://www.php.net/manual/en/function.array-combine.php#85323
     * the use age, https://onlinephp.io/c/808b5
     *
     * @param array $a
     * @param array $b
     * @return array The combined array of slices with shorter length.
     */
    public function combine_arr($a, $b) {
        $acount = count($a);
        $bcount = count($b);
        $size = ($acount > $bcount) ? $bcount : $acount;
        $a = array_slice($a, 0, $size);
        $b = array_slice($b, 0, $size);
        return array_combine($a, $b);
    }

    public function _extract_row($csv_row){
        if( ! empty($csv_row) ){
            $_csv_row = [];
            foreach($csv_row as $_index => $col){
                $col = trim($col);
                // https://regex101.com/r/9wNaka/1
                preg_match('/[\'\"]{1}(?P<val>.+)[\'\"]{1}/', $col, $matches);
                if( ! empty($matches['val'])){
                    $_col = $matches['val'];
                }else{
                    $_col = $col;
                }
                $_col = trim($_col);
                $_csv_row[$_index] = $_col;
            }
        }else{
            $_csv_row = $csv_row;
        }
        return $_csv_row;
    } // EOF _extract_row

   	public function getPlayerCenterFooter($check_cookie = true) {

		if ($check_cookie && ($preview_footer = $this->CI->input->cookie('preview_footer'))) {
			return $preview_footer;
		}

		if (empty($this->player_center_footer)) {
			$this->CI->load->model(['operatorglobalsettings']);
			//load from operator settings
			$this->player_center_footer = $this->CI->operatorglobalsettings->getSettingJson('player_center_footer', 'value', 'footer');
		}

		return $this->player_center_footer;
	}

	public function getPlayerCenterHeader($check_cookie = true) {

		if ($check_cookie && ($preview_header = $this->CI->input->cookie('preview_header'))) {
			return $preview_header;
		}

		if (empty($this->player_center_header)) {
			$this->CI->load->model(['operatorglobalsettings']);
			//load from operator settings
			$this->player_center_header = $this->CI->operatorglobalsettings->getSettingJson('player_center_header', 'value', 'nav');
		}

		return $this->player_center_header;
	}

	public function getPlayerCenterLogo($check_cookie = true) {

		if ($check_cookie && ($preview_header = $this->CI->input->cookie('preview_logo'))) {
			return $preview_header;
		}

		if (empty($this->player_center_logo)) {
			$this->CI->load->model(['operatorglobalsettings']);
			//load from operator settings
			$this->player_center_logo = $this->CI->operatorglobalsettings->getSettingJson('player_center_logo', 'value', 'logo');
		}

		return $this->player_center_logo;
	}

	public function getPlayerCenterFavicon($check_cookie = true) {

		if ($check_cookie && ($preview_header = $this->CI->input->cookie('preview_favicon'))) {
			return $preview_header;
		}

		if (empty($this->player_center_favicon)) {
			$this->CI->load->model(['operatorglobalsettings']);
			//load from operator settings
			$this->player_center_favicon = $this->CI->operatorglobalsettings->getSettingJson('player_center_favicon', 'value', 'favicon');

			// $this->debug_log('load player center favicon from player_center_favicon', $this->player_center_favicon);
		}

		return $this->player_center_favicon;
	}

	public function getPlayerCenterRegistration($check_cookie = true) {

		if ($check_cookie && ($preview_registration = $this->CI->input->cookie('preview_registration'))) {
			return $preview_registration;
		}
		$deprecated_registeration_template = $this->utils->getConfig('deprecated_registeration_template');

		if (empty($this->player_center_registration)) {
			$this->CI->load->model(['operatorglobalsettings']);
			//load from operator settings
			$this->player_center_registration = $this->CI->operatorglobalsettings->getSettingJson('player_center_registration', 'value', 'recommended');
		}

		if (in_array($this->player_center_registration, $deprecated_registeration_template, true)){
			$this->player_center_registration = 'recommended';
		}

		return $this->player_center_registration;
	}

	public function initialiazeDefaultPlayerCenterLanguage() {
		$this->CI->load->model(array('operatorglobalsettings'));
		$langSetup = $this->CI->operatorglobalsettings->getSettingJson(self::PLAYER_CENTER_LANGUAGE_OPERATORSETTING_NAME);

		if (!isset($langSetup) ||  empty($langSetup)) {
			$arrLangSetup = array('language' => '0', "hide_lang" => false);
			$this->CI->operatorglobalsettings->insertSettingJson(self::PLAYER_CENTER_LANGUAGE_OPERATORSETTING_NAME, $arrLangSetup);
		}
	}

	public function convertDateTimeFormat($dateTimeStr, $fmt, $toFormat='Y-m-d H:i:s'){
		$dt = DateTime::createFromFormat($fmt, $dateTimeStr);
		return $dt->format($toFormat);
	}

	public function loadExternalLoginApi(){

		// $this->debug_log('APPPATH:'.APPPATH);

		$external_login_api_class=$this->getConfig('external_login_api_class');
		if(!empty($external_login_api_class) && file_exists(dirname(__FILE__).'/external_login/'.$external_login_api_class.'.php')){

			// $this->debug_log('try load external_login/'.$external_login_api_class);

			$this->CI->load->library('external_login/'.$external_login_api_class);
			$api=$this->CI->$external_login_api_class;
			if(!empty($api)){
				return $api;
				// $success=$api->validateUsernamePassword($playerId, $username, $password, $message);
			}
		}

		return null;
	}

	public function login_external($playerId, $username, $password, &$message=''){
		$success=false;

		if(!empty($username) && !empty($password)){
			//try load class
			$api=$this->loadExternalLoginApi();
			if(!empty($api)){
				$success=$api->validateUsernamePassword($playerId, $username, $password, $message);
				$this->debug_log('login_external validateUsernamePassword', $success, 'message', $message);
			}
		}

		return $success;
	}

	/**
	 *
	 * @deprecated
	 * DEPRECATED; use Utils::callHttp() instead
	 */
	public function httpCall($url, $params, $config, $initSSL=null) {
		//call http
		$content = null;
		$header = null;
		$statusCode = null;
		$statusText = '';
		$resultObj=null;
		$ch = null;

		try {
			$ch = curl_init();

			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_HEADER, true);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

			//set timeout
			curl_setopt($ch, CURLOPT_TIMEOUT, $config['timeout_second']);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $config['connect_timeout']);

			// set proxy
			if (isset($config['call_socks5_proxy']) && !empty($config['call_socks5_proxy'])) {
				$this->debug_log('http call with proxy', $config['call_socks5_proxy']);
				curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
				curl_setopt($ch, CURLOPT_PROXY, $config['call_socks5_proxy']);
				if (!empty($config['call_socks5_proxy_login']) && !empty($config['call_socks5_proxy_password'])) {
					curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC);
					curl_setopt($ch, CURLOPT_PROXYUSERPWD, $config['call_socks5_proxy_login'] . ':' . $options['call_socks5_proxy_password']);
				}
			// }else{
			// 	//check variables
			// 	if (!empty($this->call_socks5_proxy)) {
			// 		$this->CI->utils->debug_log('http call with proxy by variable', $this->call_socks5_proxy);
			// 		curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
			// 		curl_setopt($ch, CURLOPT_PROXY, $this->call_socks5_proxy);
			// 		if (!empty($this->call_socks5_proxy_login) && !empty($this->call_socks5_proxy_password)) {
			// 			curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC);
			// 			curl_setopt($ch, CURLOPT_PROXYUSERPWD, $this->call_socks5_proxy_login . ':' . $this->call_socks5_proxy_password);
			// 		}
			// 	}

			}

			if(!empty($initSSL)){

				$initSSL($ch);

			}
			//build header
			$header_array=@$config['header_array'];
			$headers=[];
			if(!empty($header_array)){

				foreach ($header_array as $key => $value) {
					$headers[] = $key . ": " . $value;
				}

			}

			if (!empty($headers)) {
				curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			}
			//process post
			if(@$config['is_post']){
				curl_setopt($ch, CURLOPT_POST, true);
				if(@$config['post_json']){
					curl_setopt($ch, CURLOPT_POSTFIELDS, $this->encodeJson($params) );
				}else{
					curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params) );
				}
			}

			if(isset($config['skip_ssl_verify']) && $config['skip_ssl_verify']){
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            }

			$response = curl_exec($ch);
			$errCode = curl_errno($ch);
			$error = curl_error($ch);
			$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
			$header = substr($response, 0, $header_size);
			$content = substr($response, $header_size);

			$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			$last_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
			// var_dump($url);
			// var_dump($response);

			$statusText = $errCode . ':' . $error;
			// var_dump($statusText);
			curl_close($ch);

			if(!$errCode && @$config['is_result_json']){
				$resultObj=$this->decodeJson($content);
			}

		} catch (Exception $e) {
			$this->processError($e);
		}
		return array($header, $content, $statusCode, $statusText, $errCode, $error, $resultObj);

	}

	public function onlyGetFirstIP($ipStr){
		if(!empty($ipStr)){

			$arr=explode(',',$ipStr);
			if(!empty($arr)){
				return trim($arr[0]);
			}
		}

		return '';
	}

	/**
	 * timeout
	 *
	 * @param  string  $start
	 * @param  int  $timeoutMinutes
	 * @return boolean
	 */
	public function isTimeoutNow($start, $timeoutMinutes, $modify = '+'){

		$d=new DateTime($start);

		$d->modify($modify . $timeoutMinutes . ' minutes');

		//already timeout
		$rlt=$this->formatDateTimeForMysql($d) < $this->getNowForMysql();

		$this->debug_log('rlt', $rlt ? 'true' : 'false');

		return $rlt;
	}

	public function getMinuteBetweenTwoTime($start,$timeoutMinutes){

		$time = (strtotime($this->getNowForMysql()) - strtotime($start)) / (60);

		$this->debug_log('----time',$time);
		return $timeoutMinutes - intval($time);
	}

	public function getHostname(){
		return gethostname();
	}

    public function getUploadPath($related_path = NULL) {
        $filepath= (empty($related_path)) ? $this->getConfig('UPLOAD_PATH') : rtrim($this->getConfig('UPLOAD_PATH'), '/') . $related_path;
		if(!file_exists($filepath)){
			@mkdir($filepath, 0777, true);
			@chmod($filepath, 0777);
		}

        return $filepath;
    }

    public function countRowFromCSV($csv_file, &$message){
    	$cnt=0;

    	if(!is_readable($csv_file)){
    		return false;
    	}
    	try{
    		$file = new SplFileObject($csv_file, 'r');
    		try{
    			$file->seek(PHP_INT_MAX);
				//without header
    			$cnt=$file->key();
    		} finally {
    			unset($file);
    		}
    	}catch(Exception $e) {
    		$this->CI->utils->error_log('countRowFromCSV open csv failed', $e);
    		$message=lang('Open CSV File Failed');
    	}

    	return $cnt;
    }

    public function getUploadPublicUrl($prefix = 'player', $uri = null){
    	$path=$this->getConfig('PUBLIC_UPLOAD_PATH');
		$this->utils->addSuffixOnMDB($path);

        return $this->getSystemUrl($prefix, $path. $uri);
    }

    public function getPlayerInternalPath($related_path = NULL) {
    	$path=PLAYER_INTERNAL_PATH;
		$this->utils->addSuffixOnMDB($path);

        return (empty($related_path)) ? $path : $path . $related_path;
    }

    public function getPlayerInternalUrl($prefix = 'player', $uri = NULL) {
    	$path=$this->getConfig('PLAYER_INTERNAL_BASE_URL');
		$this->utils->addSuffixOnMDB($path);
        $extra_info['getPlayerInternalUrl'] = true;

        return $this->getSystemUrl($prefix, $path . $uri, true, $extra_info);
    }

    public function getPlayerInternalXAccelRedirectUrl($uri = NULL) {
    	$path=PLAYER_INTERNAL_BASE_PATH;
		$this->utils->addSuffixOnMDB($path);

        return $path . $uri;
    }

	public function getHeaderTemplatePath() {

		$path=$this->getUploadPath().'/themes';
		$this->utils->addSuffixOnMDB($path);
		return $path.'/'.$this->getPlayerCenterTemplate().'/includes/dynamic_top_nav/';
	}

	public function getHeaderTemplateBuiltInPath(){
        return $this->getSystemApplicationPath('player') . '/views/' . $this->getPlayerCenterTemplate(FALSE) . '/includes/header_template/';
	}

	public function getCustomHostCss(){
		$customHostCss = $this->getCustomThemeHost('custom_css_file');
		return ($customHostCss) ? : "";
	}

	public function processHeaderTemplate(){
        $this->startEvent('Load header template');

		$header_template_name = $this->getPlayerCenterHeader();
		$header_template_host_setting_name = $this->getCustomThemeHost('header_template');

		$header_template_name = ($header_template_host_setting_name) ? $header_template_host_setting_name :  $header_template_name;

        $dynamic_header_path = realpath($this->getHeaderTemplatePath() . 'dynamic_top_' . $header_template_name . '.php');

        if ($this->utils->isEnabledFeature('enable_dynamic_header') && file_exists($dynamic_header_path)) {
            $html = $this->utils->renderHeaderTemplate($dynamic_header_path);
        } else {
            $builtin_header_path = realpath($this->getHeaderTemplateBuiltInPath() . $header_template_name . '.tmpl');
            if($header_template_name === NULL || !file_exists($builtin_header_path)){
                $html = $this->CI->load->view($this->utils->getPlayerCenterTemplate(FALSE) . '/includes/top_nav', [], TRUE);
            }else{
                $html = $this->utils->renderHeaderTemplate($this->getHeaderTemplateBuiltInPath() . $header_template_name . '.tmpl');
            }
        }
        $this->endEvent('Load header template');

        return $html;
    }

	public function getFooterTemplatePath() {
		$path=$this->getUploadPath().'/themes';
		$this->utils->addSuffixOnMDB($path);
		return $path.'/'.$this->getPlayerCenterTemplate().'/includes/dynamic_main_footer/';
	}

    public function getFooterTemplateBuiltInPath(){
        return $this->getSystemApplicationPath('player') . '/views/' . $this->getPlayerCenterTemplate(FALSE) . '/includes/footer_template/';
    }

    public function processFooterTemplate(){
        $this->startEvent('Load footer template');

        $footer_template_name = $this->getPlayerCenterFooter();
		$footer_template_host_setting_name = $this->getCustomThemeHost('footer_template');

		$footer_template_name = ($footer_template_host_setting_name) ? $footer_template_host_setting_name :  $footer_template_name;

        $dynamic_footer_path = realpath($this->getFooterTemplatePath() . 'dynamic_footer_' . $footer_template_name . '.php');

        if ($this->utils->isEnabledFeature('enable_dynamic_footer') && file_exists($dynamic_footer_path)) {
            $html = $this->utils->renderFooterTemplate($dynamic_footer_path);
        } else {
            $builtin_footer_path = realpath($this->getFooterTemplateBuiltInPath() . $footer_template_name . '.tmpl');
            if($footer_template_name === NULL || !file_exists($builtin_footer_path)){
                $html = $this->CI->load->view($this->utils->getPlayerCenterTemplate(FALSE) . '/includes/footer', [], TRUE);
            }else{
                $html = $this->utils->renderFooterTemplate($this->getFooterTemplateBuiltInPath() . $footer_template_name . '.tmpl');
            }
        }
        $this->endEvent('Load footer template');

        return $html;
	}

	public function processMobileCustomFooterTemplate(){
        $this->startEvent('Load custom footer template');

		$builtin_footer_path = $this->getMobilePlayerCenterCustomDynamicFooter();
		if($builtin_footer_path){
			$footer=$builtin_footer_path;
			if (!empty($footer)) {
				$data=$this->initTemplateVariables(false);
				$m = new Mustache_Engine;
				$footer=preg_replace('/<script.+id="tmpl_footer">/', "", $footer, 1);
				$footer=$m->render($footer, $data);
				return $footer;
			}
		}
        $this->endEvent('Load custom footer template');
		return false;
	}

	private function getCustomThemeHost($locate=null) {

		if (!$this->isEnabledFeature('enable_dynamic_theme_host_template')) {
			return false;
		}

		$this->CI->load->model(['operatorglobalsettings']);
		$themeSetting = $this->CI->operatorglobalsettings->getSetting("player_center_theme_host_template");
		if (!$themeSetting) {
			return false;
		}

		$themeList = json_decode($themeSetting->template, true);
		if (!$themeList) {
			return false;
		}

		$mainHostName = $this->getMainHostName();
		foreach ($themeList as $list) {
			if (isset($list[$locate]) && $list['hostname'] == $mainHostName) {
				return $list[$locate];
			}
		}

		return false;
	}

	public function getMainHostName() {
		$host = $_SERVER['HTTP_HOST'];
		$hostExplode = explode(".", $host);
		array_shift($hostExplode);
		return join('.', $hostExplode);
	}

	public function getLogoTemplatePath() {
		$path=$this->getUploadPath().'/themes';
		$this->utils->addSuffixOnMDB($path);
		return $path.'/'.$this->getPlayerCenterTemplate(FALSE).'/img_logo/';
	}

	public function getFaviconTemplatePath() {
		$path=$this->getUploadPath().'/themes';
		$this->utils->addSuffixOnMDB($path);
		return $path.'/' . $this->getPlayerCenterTemplate(FALSE) . '/img_favicon/';
	}

	public function getThemesTemplatePath() {
		$path=$this->getUploadPath().'/themes';
		$this->utils->addSuffixOnMDB($path);
		return $path.'/'.$this->getPlayerCenterTemplate();
	}

	public function getJsTemplatePath() {
		$path=$this->getUploadPath().'/themes';
		$this->utils->addSuffixOnMDB($path);
		return $path.'/'.$this->getPlayerCenterTemplate().'/js/';
	}

	public function getActivePlayerCenterTheme() {

		$theme_template_name = $this->getPlayerCenterTheme();
		$theme_template_host_setting_name = $this->getCustomThemeHost('theme_template');

		$theme_template_name = ($theme_template_host_setting_name) ? $theme_template_host_setting_name :  $theme_template_name;

		$base_path = base_url() . $this->getPlayerCenterTemplate();
		if(file_exists($this->getThemesTemplatePath().'/styles/base-theme-'.$theme_template_name.'.css')) {
			$themes = $this->getUploadThemeUri().'/'.$this->utils->getPlayerCenterTemplate().'/styles/base-theme-'.$theme_template_name.'.css';
		} else if (file_exists($this->getPlayerCenterTemplate().'/styles/base-theme-'.$theme_template_name.'.css')) {
			$themes = $base_path.'/styles/base-theme-'.$theme_template_name.'.css';
		} else {
			$themes = $base_path.'/styles/base-theme-blue.css';
		}

		return $themes;
	}

	public function simpleSubmitPostForm($url, $params, $postJson=false, $headers=null) {
		try {
			$ch = curl_init();

			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_HEADER, true);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

			if($postJson){
				$paramsContent=$this->encodeJson($params);
			}else{
				$paramsContent=http_build_query($params);
			}

			if(empty($headers)){
				//default header
				$headers=['Content-type', 'application/json'];
			}
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

			curl_setopt($ch, CURLOPT_POSTFIELDS,  $paramsContent);
			curl_setopt($ch, CURLOPT_TIMEOUT, $this->getConfig('default_http_timeout'));
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->getConfig('default_connect_timeout'));

			$response = curl_exec($ch);
			$errCode = curl_errno($ch);
			$error = curl_error($ch);
			$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
			$header = substr($response, 0, $header_size);
			$content = substr($response, $header_size);

			$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			$last_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

			$statusText = $errCode . ':' . $error;
			curl_close($ch);

			$this->debug_log('simpleSubmitPostForm url', $url, 'params', $params , 'paramsContent', $paramsContent, 'response', $response, 'errCode', $errCode, 'error', $error, 'statusCode', $statusCode);

			return $content;
		} catch (Exception $e) {
			$this->error_log('POST failed', $e);
		}

		return null;
	}

	public function getRequestId(){
		// static $_log;

		// $_log = &load_class('Log');
		return _REQUEST_ID;
	}

	public function printSQLToJson(){
		if(!$this->getConfig('debug_print_json_sql')){
			return;
		}

		static $_log;

		$_log = &load_class('Log');

		$_log->debug_sql_log('------------print sql start------------');

		$queries = array_combine($this->CI->db->query_times, $this->CI->db->queries);

		foreach ($queries as $time => $sql) {
			$_log->debug_sql_log($sql, $time);
		}

		$readOnlyQueries = [];
		if(isset($this->CI->readOnlyDB)){
			$readOnlyQueries = array_combine($this->CI->readOnlyDB->query_times, $this->CI->readOnlyDB->queries);
			foreach ($readOnlyQueries as $time => $sql) {
				$_log->debug_sql_log($sql, $time);
			}
		}

		$reportQueryies=[];
		if(isset($this->CI->report_sql)){
			$reportQueryies=$this->CI->report_sql;
			foreach ($reportQueryies as $time => $sql) {
				$_log->debug_sql_log($sql, $time);
			}
		}

		$_log->debug_sql_log('------------print sql end------------');
	}

	public function setupReadOnlyDB($readOnlyDB){
		$this->CI->readOnlyDB=$readOnlyDB;
	}

	private $redis_lock_server=null;

	/**
	 * global redis, sharing with og_sync and og pod
	 * @return object $redis
	 */
	public function getRedisLockServer($db=self::REDIS_DB_CACHE){
        if (empty($this->redis_lock_server)) {
        	//try load redis from lock_servers
        	$lock_servers=$this->getConfig('lock_servers');
        	$server=$lock_servers[0];
            list($host, $port, $timeout) = $server;
            $redis = new \Redis();
            try{
            	$redis->connect($host, $port, $timeout);
            	// $redis->select($db);
            }catch(Exception $e){
            	$this->error_log('cannot connect to redis', $e, $host, $port);
            	return null;
            }

            $this->redis_lock_server = $redis;
        }
        if(!empty($this->redis_lock_server)){
	        try{
	        	$this->redis_lock_server->select($db);
	        }catch(Exception $e){
	        	$this->error_log('cannot select db in redis', $e);
	        	$this->redis_lock_server=null;
	        	return null;
	        }
        }
		return $this->redis_lock_server;
	}

	public function getRedisServer($db=self::REDIS_DB_CACHE){
        $redis=$this->getDefaultRedisServer($db);
        if(!empty($redis)){
        	return $redis;
        }else{
        	//fallback to lock server
        	return $this->getRedisLockServer($db);
        }
	}

	/**
	 * Read from REDIS lock_servers
	 * @param	string	$key	The key
	 * @return	string	Value stored, null if key not present
	 */
	public function readRedis($key){
		$redis=$this->getRedisServer(self::REDIS_DB_CACHE);
		if(empty($redis)){
			return null;
		}
		$key_full = $this->addAppPrefixForKey($key);

		return $redis->get($key_full);
	}

	/**
	 * Write to REDIS lock_servers
	 * @param	string	$key	The key
	 * @param	string	$val	The value
	 * @param	int		$ttl	Time to live, NOTE: IN SECONDS
	 * @return	bool	true if successful, otherwise false
	 */
	public function writeRedis($key, $val, $ttl = 3600){
		$redis=$this->getRedisServer(self::REDIS_DB_CACHE);
		if(empty($redis)){
			return false;
		}

		$key_full = $this->addAppPrefixForKey($key);
        return $redis->set($key_full, $val, $ttl);
	}

	/**
	 * delete key REDIS lock_servers
	 * @param	string	$key	The key
	 * @return	bool	true if successful, otherwise false
	 */
	public function deleteKeyRedis($key){
		$redis=$this->getRedisServer(self::REDIS_DB_CACHE);
		if(empty($redis)){
			return false;
		}

		$key_full = $this->addAppPrefixForKey($key);
		//async delete
		//$redis->unlink($key_full);
		if(method_exists($redis, 'unlink')){
			$redis->unlink($key_full);
		}else{
			$redis->del($key_full);
			$this->error_log('error using unlink in redis');
		}
        return true;
	}

	/**
	 * readJsonFromRedis
	 * @param  string $key
	 * @return mixin null or decoded array
	 */
	public function readJsonFromRedis($key){
		$val=$this->readRedis($key);
		if(!empty($val)){
			return $this->decodeJson($val);
		}
		return null;
	}

	public function writeJsonToRedis($key, $json, $ttl = 3600){
		if(empty($json)){
			return false;
		}
		$val=$this->encodeJson($json);
		return $this->writeRedis($key, $val, $ttl);
	}

	public function getGUID() {
	    if (function_exists('com_create_guid')) {
	        return com_create_guid();
	    } else {
	        mt_srand((double)microtime()*10000);
	        $charid = strtoupper(md5(uniqid(rand(), true)));
	        $hyphen = chr(45);
	        $uuid = chr(123)
	            .substr($charid, 0, 8).$hyphen
	            .substr($charid, 8, 4).$hyphen
	            .substr($charid,12, 4).$hyphen
	            .substr($charid,16, 4).$hyphen
	            .substr($charid,20,12)
	            .chr(125);
	        return $uuid;
	    }
	}

	const LOGO_CACHE_TTL = 3600; # 1 HOUR

	public function getRecentlyGameDisplayLimit() {
		return $this->getConfig('recently_played_item_display_limit');
	}

	public function getVipBadgePath($check_mobile = true) {
		$path=$this->getUploadPath().'/';
		$this->utils->addSuffixOnMDB($path);
		return $path . '/vip/' . $this->getPlayerCenterTemplate($check_mobile) . '/img_badge/';
	}

	public function getVipBadgeUri($check_mobile = true){
		$path='/upload';
		$this->utils->addSuffixOnMDB($path);
		return $path.'/vip/' . $this->getPlayerCenterTemplate($check_mobile) . '/img_badge';
	}

	public function getPromoThumbnails() {
		$path=$this->getUploadPath().'/';
		$this->utils->addSuffixOnMDB($path);
		return $path . '/promothumbnails/' . $this->getPlayerCenterTemplate(FALSE).'/';
	}

	public function getQuestThumbnails() {
		$path=$this->getUploadPath().'/';
		$this->utils->addSuffixOnMDB($path);
		return $path . '/questthumbnails/' . $this->getPlayerCenterTemplate(FALSE).'/';
	}

    public function getPromoThumbnailRelativePath($check_mobile = true) {
        $path=$this->getConfig('PUBLIC_UPLOAD_PATH');
        $this->utils->addSuffixOnMDB($path);
        return $path . '/promothumbnails/' . $this->getPlayerCenterTemplate($check_mobile).'/';
    }

	public function getQuestThumbnailRelativePath($check_mobile = true){
		$path=$this->getConfig('PUBLIC_UPLOAD_PATH');
		$this->utils->addSuffixOnMDB($path);
		return $path . '/questthumbnails/' . $this->getPlayerCenterTemplate($check_mobile).'/';
	}

    public function getAgencyFavIconRelativePath(){
        $path=$this->getConfig('PUBLIC_UPLOAD_PATH');
        $this->utils->addSuffixOnMDB($path);
        return $path;
    }

    public function getAffFavIconRelativePath(){
        $path=$this->getConfig('PUBLIC_UPLOAD_PATH');
        $this->utils->addSuffixOnMDB($path);
        return $path;
    }

	public function getPromoThumbnailsUrl($imageName, $check_mobile = true) {
		if(empty($imageName)) {
			return $this->utils->imageUrl('promothumbnails/default_promo_cms_1.jpg');
		}

		$imageFolderPath = $this->getPromoThumbnails();
		$imagePath = $imageFolderPath.$imageName;
		$cacheKey = "getPromoThumbnailsUrl|".$imagePath;

		$promoThumbnailUrl = $this->getTextFromCache($cacheKey);

		if($promoThumbnailUrl === false) {
			if(file_exists($imagePath)){
				$promoThumbnailUrl = $this->getUploadUri().'/promothumbnails/'.$this->utils->getPlayerCenterTemplate($check_mobile).'/'.$imageName;
			} else {
				$promoThumbnailUrl = $this->utils->imageUrl('promothumbnails/'.$imageName);
			}

			$this->saveTextToCache($cacheKey, $promoThumbnailUrl, self::LOGO_CACHE_TTL);
		}

		return $promoThumbnailUrl;
	}

	public function getShopThumbnailsPath() {
		$path=$this->getUploadPath().'/';
		$this->utils->addSuffixOnMDB($path);
		return $path . '/shopthumbnails';
	}

	public function getCacheKeyForPlayerCenterLogoURL(){
        return "getPlayerCenterLogoURL:".$this->utils->getPlayerCenterTemplate(FALSE) . '|' . $this->getPlayerCenterLogo() . '|' . $this->getCmsVersion();
	}

	public function getPlayerCenterLogoURL(){
        $playercenter_logo = NULL;
        $prefer_player_center_logo = (int)$this->CI->operatorglobalsettings->getSettingIntValue('prefer_player_center_logo', PLAYER_CENTER_LOGO_PREFER_UPLOAD);

        switch($prefer_player_center_logo){
            case PLAYER_CENTER_LOGO_PREFER_WWW:
                $playercenter_logo = $this->getAnyCmsUrl('/includes/images/logo.png');
                break;
            case PLAYER_CENTER_LOGO_PREFER_DEFAULT:
                $playercenter_logo = $this->getSystemUrl("player", '/'. $this->getPlayerCenterTemplate(FALSE) . '/img/logo.png', false);
                break;
            case PLAYER_CENTER_LOGO_PREFER_UPLOAD:
                $cacheKey = $this->getCacheKeyForPlayerCenterLogoURL();
                $playercenter_logo = $this->getTextFromCache($cacheKey);

                if(!empty($playercenter_logo)){
                    return $playercenter_logo;
                }

                $fileExt = array("jpg","jpeg","png","gif", "PNG");
                foreach ($fileExt as $key => $value) {
                    $dir = $this->getLogoTemplatePath(). $this->getPlayerCenterLogo() . '.' . $value;

                    if (file_exists($dir)) {
                        $playercenter_logo = $this->getSystemUrl("player", $this->getUploadThemeUri().'/' . $this->utils->getPlayerCenterTemplate(FALSE) . '/img_logo/' . $this->getPlayerCenterLogo() . '.' . $value .'?v=' . $this->getCmsVersion(), false);

                        $this->saveTextToCache($cacheKey, $playercenter_logo, self::LOGO_CACHE_TTL);
                        return $playercenter_logo;
                    }
                }
                if(empty($playercenter_logo)){
                    $playercenter_logo = $this->getSystemUrl("player", '/'. $this->getPlayerCenterTemplate(FALSE) . '/img/logo.png', false);
                }
                break;
        }

		return $playercenter_logo;
	}

	public function getPlayerCenterFaviconURL(){
        $dir = $this->getFaviconTemplatePath(). $this->getPlayerCenterFavicon() . '.ico';

        $this->debug_log('getPlayerCenterFaviconURL: '.$dir);

        if (file_exists($dir)) {
            $playercenter_favicon = $this->getSystemUrl("player", $this->getUploadThemeUri().'/' . $this->utils->getPlayerCenterTemplate(FALSE) . '/img_favicon/' . $this->getPlayerCenterFavicon() . '.ico' . '?v=' . $this->getCmsVersion(), false);
        } else {
            $playercenter_favicon = $this->getSystemUrl("player", '/favicon.ico', false);
        }

		return $playercenter_favicon;
	}

	public function getCmsVersion() {
		if(empty($this->cms_version)) {
			$this->CI->load->model(['operatorglobalsettings']);
			$cms_version = $this->CI->operatorglobalsettings->getSettingJson('cms_version');
			if(empty($cms_version)) {
				$cms_version = '1.000.000.001';
				$this->CI->operatorglobalsettings->syncSettingJson("cms_version", $cms_version, 'value');
			}
			//add production version
			$this->cms_version = PRODUCTION_VERSION.'-'.$cms_version;
		}

		return $this->cms_version;
	}

	public function incCmsVersion(){

		$this->CI->load->model(['operatorglobalsettings']);
		$cms_version = $this->CI->operatorglobalsettings->getSettingJson('cms_version');
		if(empty($cms_version)) {
			$cms_version = '1.000.000.001';
			$this->CI->operatorglobalsettings->syncSettingJson("cms_version", $cms_version, 'value');
		}
		$arr=explode('.', $cms_version);
		$last_version=$arr[count($arr)-1]+1;
		$arr[count($arr)-1]= $last_version;
		$cms_version=implode('.', $arr);
		$this->debug_log('update cms version:'.$cms_version);

		if($this->isEnabledMDB()){
			//update mdb first
        	$sourceDB=$this->getActiveTargetDB();
			$rlt=$this->CI->operatorglobalsettings->foreachMultipleDBWithoutSourceDB($sourceDB,
				function($db, &$result) use($cms_version){
				$result=$cms_version;
				return $this->CI->operatorglobalsettings->syncSettingJson("cms_version", $cms_version, 'value', $db);
			});
			$this->debug_log('update cms version on mdb', $rlt);
		}

		$this->CI->operatorglobalsettings->syncSettingJson("cms_version", $cms_version, 'value');

		$this->cms_version=PRODUCTION_VERSION.'-'.$cms_version;
		return $cms_version;
	}

	//OGP-5207
	//you can you this format as well for dynamic language web title.
	//_json:{"1":"Demo","2":"Demo","3":"Demo","4":"Demo","5":"Demo"}
	public function getPlayertitle() {
		$this->CI->load->model(['operatorglobalsettings']);
		$this->player_center_title = $this->CI->operatorglobalsettings->getSettingJson('player_center_title');
		if(empty($this->player_center_title)) {
			$this->CI->operatorglobalsettings->syncSettingJson("player_center_title", 'Demo', 'value');
			$this->player_center_title = $this->CI->operatorglobalsettings->getSettingJson('player_center_title');
		}

		return $this->player_center_title;
	}

	public function appendCmsVersionToUri($uri) {
		if (!empty($uri)) {

			if (substr($uri, strlen($uri) - 1, 1) == '?') {
				//don't append
			} else {
				$uri .= strpos($uri, '?') !== FALSE ? '&' : '?';
			}
			$uri .= 'v=' . $this->getCmsVersion();
		}

		return $uri;
	}

    public function getPlayerCmsUrl($uri, $prefix = ''){
        $uri = $this->appendCmsVersionToUri($uri);

        if ($this->isFullUrl($uri)) {
            return $uri;
        } else {
            //remove starts '/'
            if (substr($uri, 0, 1) == '/') {
                $uri = substr($uri, 1);
            }
            if (substr($prefix, 0, 1) != '/' && $prefix != '') {
                $prefix = '/' . $prefix;
            }

            $uri = $this->getSystemUrl('player', $prefix . '/' . $uri, false);

            return $uri;
        }
    }

    public function getAgencyCmsUrl($uri, $prefix = ''){
        $uri = $this->appendCmsVersionToUri($uri);

        if ($this->isFullUrl($uri)) {
            return $uri;
        } else {
            //remove starts '/'
            if (substr($uri, 0, 1) == '/') {
                $uri = substr($uri, 1);
            }
            if (substr($prefix, 0, 1) != '/' && $prefix != '') {
                $prefix = '/' . $prefix;
            }

            $uri = $this->getSystemUrl('agency', $prefix . '/' . $uri, false);

            return $uri;
        }
    }

	public function getAnyCmsUrl($uri, $prefix = '', $website_prefix = null) {
		$uri = $this->appendCmsVersionToUri($uri);

		if ($this->isFullUrl($uri)) {
			return $uri;
		} else {
			//remove starts '/'
			if (substr($uri, 0, 1) == '/') {
				$uri = substr($uri, 1);
			}
			if (substr($prefix, 0, 1) != '/' && $prefix != '') {
				$prefix = '/' . $prefix;
			}
			if(!!empty($website_prefix)) {
				//change to www url
				if($this->is_mobile()){
					$uri = $this->getSystemUrl('m',$prefix . '/' . $uri);
				} else {
					$uri = $this->getSystemUrl('www',$prefix . '/' . $uri);
				}
			} else {
				$uri = $this->getSystemUrl($website_prefix, $prefix . '/' . $uri);
			}

			return $uri;
		}
	}

	public function getAddJs() {
		$prefix_js = "js-";
		$js_path = rtrim($this->getJsTemplatePath(), '/');

		$files = glob(realpath($js_path).'/'.$prefix_js.'*');

		$response = [] ;

		foreach ($files as $file => $value) {
			//var_dump(basename($value));die();

			$dir = $this->getSystemUrl("player", $this->getUploadThemeUri().'/'.$this->getPlayerCenterTemplate().'/js/'.basename($value).'?v='.$this->getCmsVersion(), false);
			array_push($response, $dir);
		}
		//var_dump($response);die();
		return $response;
	}

    public function catchOutputWithShellCmd($cmd){
        $handle = popen($cmd.' 2>&1', 'r');
		$read = '';
		// Example #3 Remote fread() examples in the url,"https://www.php.net/manual/en/function.fread.php".
		while (!feof($handle)) {
			$read .= fread($handle, 1024);
		}
		pclose($handle);
        return $read;
    } // EOF catchOutputWithShellCmd

	public function runCmd($cmd){
        $return_var=pclose(popen($cmd, 'r'));
        $this->utils->debug_log('runCmd: '.$cmd, $return_var);
		return $return_var;
	}

    public function find_out_php(){
    	$php_path_arr=['/usr/bin/php5.6', '/usr/bin/php5', '/usr/local/bin/php', '/usr/bin/php'];

    	foreach ($php_path_arr as $path) {
    		if(file_exists($path)){
    			return $path;
    		}
    	}

    	return 'php';
    }

	public function generateCommandLine($func, $params, $is_blocked=false, &$file_list=[], $dbName=null, $isWritingLogToSharingDir=false){

		$php_str=$this->find_out_php();

		$og_admin_home = realpath(dirname(__FILE__) . "/../../");

		$this->debug_log('func:'.$func, $params);
		$params_str='';
		if(!empty($params)){

			foreach ($params as &$param) {
				if($param===true){
					$param='true';
				}
				if($param===false){
					$param='false';
				}
				if(is_string($param) && $param===''){
					$param=_COMMAND_LINE_NULL;
				}
				$param='"'.$param.'"';
			}

			$params_str=implode(' ', $params);
		}

		$main_cmd=$php_str." ".$og_admin_home."/shell/ci_cli.php cli/command/".$func." $params_str";
		return $this->generateCommonLine($main_cmd, $is_blocked, $func,
			$file_list, $dbName, $isWritingLogToSharingDir);
	}

	public function getLogDirFromSharing(){
		$log_dir=$this->utils->getSharingUploadPath('/remote_logs');
		if(!file_exists($log_dir)){
			@mkdir($log_dir, 0777 , true);
		}
		return $log_dir;
	}

	public function generateCommonLine($main_cmd, $is_blocked=false, $func=null, &$file_list=[], $dbName=null, $isWritingLogToSharingDir=false){

		// $params_str=implode(' ', $params);

//		$og_admin_home = realpath(dirname(__FILE__) . "/../../");

		$uniqueid=random_string('md5');

		//app log
		$log_dir=$tmp_shell_dir=BASEPATH.'/../application/logs/tmp_shell';
		if(!file_exists($tmp_shell_dir)){
			@mkdir($tmp_shell_dir, 0777 , true);
		}
		if($isWritingLogToSharingDir){
			// write to sharing private log
			$log_dir=$this->getLogDirFromSharing();
		}
		$dbEnv='';
		$appPrefix=$this->_app_prefix;
		if(empty($dbName)){
			$dbName=$this->CI->db->getOgTargetDB();
		}
		$appPrefix=$dbName;
		if($this->isEnabledMDB()) {
			$dbEnv='__OG_TARGET_DB='.$dbName;
		}

		$log_dir=realpath($log_dir);
		if(empty($log_dir) || $log_dir=='/'){
			$log_dir='/tmp/'.$appPrefix;
		}

		$title=$func;
		if(empty($title)){
			$title=$uniqueid;
		}

		$noroot_command_shell=<<<EOD
#!/bin/bash

echo "start {$title} `date`"

start_time=`date "+%s"`
{$dbEnv} {$main_cmd}
end_time=`date "+%s"`

echo "Total run time: `expr \$end_time - \$start_time` (s)"
echo "done {$title} `date`"
EOD;

		$tmp_shell=$tmp_shell_dir.'/'.$func.'_'.$uniqueid.'.sh';
		file_put_contents($tmp_shell, $noroot_command_shell);

		$this->debug_log('write shell', $noroot_command_shell, $tmp_shell);

		#for windows
		#shell_exec("sed -i -e 's/\r$//' ". $tmp_shell);
		$file_list[]=$tmp_shell;

		//convert to realpath
		// $cmd=realpath($cmd);
		if($is_blocked){
			$cmd='bash '.$tmp_shell;
		}else{
			$log_file=$log_dir.'/job_'.$func.'_'.$uniqueid.'.log';
			$file_list[]=$log_file;
			$cmd='nohup bash '.$tmp_shell.' 2>&1 > '.$log_file.' &';
		}
		$this->debug_log('full cmd', $cmd);
		return $cmd;
	}

	public function loopDateTimeStartEnd($start, $end, $step, $callback) {
		if (is_string($start)) {
			$start = new \DateTime($start);
		}

		if (is_string($end)) {
			$end = new \DateTime($end);
		}

		$now=new DateTime();
		if($end > $now){
			$end = $now;
		}


		$success = false;
		$startDate = clone $start;
		while ($startDate < $end) {
			$endDate = $this->getNextTime($startDate, $step);

			if($endDate>$end){
				$endDate=$end;
			}

			$from = clone $startDate;
			$to = clone $endDate;

			$success = $callback($from, $to, $step);

			if (!$success) {
				$this->utils->error_log('loop date time failed', $from, $to);
				break;
			}

			$startDate = $endDate;
		}

		return $success;
	}

	/**
	 * Check the cronjob name is exists in the list.
	 *
	 * @param string $curr_job_func The cronjob name.
	 * @param array $target_list The cronjob name list in the setting,"cronjob_list_func_name_with_mdb_suffix_at_tail".
	 * @return boolean If its true , thats means exists in the list.
	 */
	public function isPOSInList($curr_job_func, $target_list = []){
		// list pos to curr
		// strpos(curr,list[n])
		if( empty( $target_list ) ){
			$target_list =  $this->getConfig('cronjob_list_func_name_with_mdb_suffix_at_tail');
		}

		$matched_list = array_filter($target_list, function($v, $k) use ($curr_job_func){
			$mystring = $curr_job_func;
			$findme = $v;
			$pos = strpos($mystring, $findme);
			if($pos === false){
				// not found
				return false;
			}else{
				// found
				return true;
			}
		}, ARRAY_FILTER_USE_BOTH);
		$this->utils->debug_log('OGP-25378.matched_list:', $matched_list
		, 'target_list:', $target_list
		, 'curr_job_func:', $curr_job_func );
		return empty($matched_list)? false : true;
	} // EOF isPOSInList

    /**
     * @deprecated marked by elvis
     */
	public function logoutToGames() {

		$this->CI->load->model(array('player_model','game_provider_auth'));
		$apis = $this->getAllCurrentGameSystemList();
		$logoutToGames =  $this->getConfig('logout_to_games');
		$this->debug_log('logoutToGames =============================================>', $logoutToGames, 'apis' , $apis);

		if(!empty($logoutToGames) && !empty($apis)){
			foreach ($apis as $gamePlatformId) {
				if(in_array($gamePlatformId, $logoutToGames)){

					$playerId = $this->getLoggedPlayerId();
					#sometimes player not exist
					$existInGameProviderAuth = $this->CI->game_provider_auth->getByPlayerIdGamePlatformId($playerId, $gamePlatformId);

					if(!empty($existInGameProviderAuth)){
						$player = (array)$this->CI->player_model->getPlayerById($playerId);
						$api = $this->loadExternalSystemLibObject($gamePlatformId);
						$playerName = $api->getGameUsernameByPlayerUsername($player['username']);
						$password= $api->getPassword($playerName);

						$rlt = $api->logout($player['username'], $password) ;

						if($rlt['success']){
							$this->CI->game_provider_auth->setPlayerStatusLoggedOff($gamePlatformId, $playerId);
						}
						$this->debug_log('playerName', $playerName, 'gamePlatformId', $gamePlatformId, "logoutToGames result", $rlt);
					}
				}
			}
		}

		return;
	}

    public function getUniversalSaveState($saveStateName = null) {
        $this->CI->load->model(array('player_model'));
        $saveStateData = $this->CI->player_model->getSaveStateData($saveStateName);

        // $data['columnshownumber'] = $saveStateData['columnshownumber'];
        $data['columnhidenumber'] = $saveStateData['columnhidenumber'];
        $data['isdatatableExist'] = $saveStateData['isdatatableExist'];

        return $data;
	}

	public function getPlayerCenterCustomScript() {
		$this->CI->load->model(['operatorglobalsettings']);

        $js = $this->CI->operatorglobalsettings->getSettingJson('player_center_custom_script', 'template');
        $js = (empty($js)) ? $this->CI->operatorglobalsettings->getSettingJson('player_center_custom_script', 'description_json') : $js;
		return $js;
	}

	public function getTrackingScriptWithDoamin($system = 'player', $type = 'gtm', $extra_type = NULL){
        $tracking_script_with_domain = $this->getConfig('tracking_script_with_domain');

        if(!isset($tracking_script_with_domain)){
            return NULL;
        }

	    $mainHostName = $this->getMainHostName();
        $mainHostName = strtolower($mainHostName);

        $domain_setting = NULL;
        if(isset($tracking_script_with_domain[$mainHostName])){
            $domain_setting = $tracking_script_with_domain[$mainHostName];
        }else{
            if(!isset($tracking_script_with_domain['default'])){
                return NULL;
            }
            $domain_setting = $tracking_script_with_domain['default'];
        }

        $system_setting = NULL;
        if(isset($domain_setting[$system])){
            $system_setting = $domain_setting[$system];
        }else{
            if(!isset($domain_setting['player'])){
                return NULL;
            }
            $system_setting = $domain_setting['player'];
        }

        $type_setting = NULL;
        if(isset($system_setting[$type])){
            $type_setting = $system_setting[$type];
        }

        $extra_type_setting = NULL;
        if(is_array($type_setting)){
            if(isset($type_setting[$extra_type])){
                $extra_type_setting = $type_setting[$extra_type];
                return $extra_type_setting;
            }else{
                return NULL;
            }
        }else{
            return $type_setting;
        }
    }

	public function getBanIconPath() {
		$path=$this->getUploadPath();
		$this->utils->addSuffixOnMDB($path);
		return $path . '/system/bank-icon';
	}

	public function getBankIcon($bankIcon, $addDomain = false) {
		return !empty($bankIcon) ? $this->getSystemUrl("player", $this->getUploadUri().'/system/bank-icon/'.$bankIcon, $addDomain) : $this->imageUrl('no.png');
	}

	/**
	 * Checks db for table_name existence without using table list cache.
	 * Db::table_exists() uses cache, and causes some very cunning side effects in
	 * ::initRespTableByDate() (see below).
	 * @param	string	$table_name		Name of table
	 * @return	bool	true if table exists, otherwise false
	 */
	public function table_really_exists($table_name, $db=null) {
		if(empty($db)){
			$db=$this->CI->db;
		}
		$query = $db->query('SHOW TABLES');

		$table_list = $query->result_array();
		foreach ($table_list as $row) {
			// $this->debug_log(__METHOD__, 'table_list-row', reset($row));
			$table_list_item = reset($row);
			if ($table_name == $table_list_item) {
				$query->free_result();
				return true;
			}
		}
		$query->free_result();
		return false;
	}

	/**
	 * Get the table status in MySql.
	 *
	 * @param string $tablename The table name.
	 * @return array  $row The row for information of the table.
	 *
	 */
	public function showTableStatus($tablename, $db=null){
        if(empty($db)){
			$db=$this->CI->db;
		}
		$sql = 'SHOW TABLE STATUS LIKE "'. $tablename. '";';
		$query = $db->query($sql);
		$row = $query->row_array();
		$query->free_result(); // $query 物件將不再使用了
		unset($query);
		return $row;
	} // EOF showTableStatus

    /**
     * Get Auto Increment Id via SHOW TABLE query
     * There has Non match issue when the table exists the Auto Increment id
     *
     * When ignore performance, please use the method, self::getMaxPrimaryIdByTable().
     *
     * @param string $tablename The table name
     * @param CI_DB_driver $db
     * @return integer The auto increment id.
     */
    public function getAutoIncrementByTable($tablename, $db=null){
        $auto_increment = -1;
        $row = $this->showTableStatus($tablename, $db);
        if( ! empty($row) ){
            $auto_increment = $row['Auto_increment'];
        }
        return $auto_increment;
    }
    /**
     * Get All ColumnName
     *
     * @param string $tablename The table name
     * @param CI_DB_driver $db
     * @return array
     */
    public function getAllColumnName($tablename, $db=null){
        if(empty($db)){
			$db=$this->CI->db;
		}
        $dbName = $db->getDBName();
        $allColumnList = [];

        $sql_formater = '';
        $sql_formater .= "SELECT COLUMN_NAME ";
        $sql_formater .= "FROM INFORMATION_SCHEMA.COLUMNS ";
        $sql_formater .= "WHERE TABLE_SCHEMA = '$dbName' "; // param, db name
        $sql_formater .= "AND TABLE_NAME= '$tablename' ";  // param, table name
        $sql = sprintf($sql_formater, $dbName, $tablename);

		$query = $db->query($sql);
        if ($query && $query->num_rows() > 0) {
		    $rows = $query->result_array();
            $query->free_result();
            unset($query);
            $allColumnList = array_column($rows, 'COLUMN_NAME');
        }
        return $allColumnList;
    }
    /**
     * Get the Primary Column Name
     *
     * @param string $tablename The table name
     * @param CI_DB_driver $db
     * @return string The Primary Column Name
     */
    public function getPrimaryColumnName($tablename, $db=null){
        if(empty($db)){
			$db=$this->CI->db;
		}
        $dbName = $db->getDBName();

        $columnName = '';

        $sql_formater = '';
        $sql_formater .= 'SELECT COLUMN_NAME ';
        $sql_formater .= 'FROM INFORMATION_SCHEMA.`KEY_COLUMN_USAGE` ';
        $sql_formater .= 'WHERE TABLE_SCHEMA = \'%s\' '; // param, db name
        $sql_formater .= 'AND TABLE_NAME=\'%s\' ';  // param, table name
        $sql_formater .= 'AND CONSTRAINT_NAME=\'PRIMARY\';';

        $sql = sprintf($sql_formater, $dbName, $tablename);
		$query = $db->query($sql);
		$row = $query->row_array();
		$query->free_result(); // $query 物件將不再使用了
		unset($query);

        if(! empty($row) ){
            $columnName = $row['COLUMN_NAME'];
        }

        // free
        $row = [];
        unset($row);

		return $columnName;
    } // EOF getPrimaryColumnName
    /**
     * Get the max Primary Id of the table
     *
     * @param string $tablename The table name
     * @param boolean $doAutoIncrement When its true then return the Primary Id will incremented by one.
     * @param CI_DB_driver $db
     * @return integer The Max Primary Id of the table.
     * If its be -1, that means failed
     */
    public function getMaxPrimaryIdByTable($tablename, $doAutoIncrement = true, $db=null){
        if(empty($db)){
			$db=$this->CI->db;
		}

        $maxPrimaryId = -1;

        $pkColumnName = $this->getPrimaryColumnName($tablename, $db);
        $sql_formater = '';
        $sql_formater .= 'SELECT %s '; // $pkColumnName
        $sql_formater .= 'FROM %s '; // $tablename
        $sql_formater .= 'ORDER BY %s DESC '; // $pkColumnName
        $sql_formater .= 'LIMIT 1 ';

        $row = [];
        if( ! empty($pkColumnName) ){
            $sql = sprintf($sql_formater,  $pkColumnName, $tablename, $pkColumnName);
            $query = $db->query($sql);
            $row = $query->row_array();
            $query->free_result(); // $query 物件將不再使用了
            unset($query);
        }else{
            $dbName = $db->getOgTargetDB();
            $this->error_log('Not found the Primary ColumnName, the table:', $tablename, ' the DB:', $dbName );
        }

        if( empty($row) ){
            $maxPrimaryId = 0;
        }else{
            $maxPrimaryId = $row[$pkColumnName];

            if($doAutoIncrement){
                $maxPrimaryId++;
            }
        }

        $row = []; // free
        unset($query);

		return $maxPrimaryId;
    } // EOF getMaxPrimaryIdByTable

    /**
     * Add the Suffix String to lang field
     *
     * @param string $langFieldContent The language string or the json encoded format in multi-language.
     * @param string $suffixStr The suffix string
     * @return void
     */
    public function appendSuffix2langField($langFieldContent, $suffixStr = ''){
        $jsonFormatPrefix = '_json:';
        $_returnLangFieldContent = '';
        if(substr($langFieldContent, 0, 6) === $jsonFormatPrefix){
            $jsonStr = substr($langFieldContent, 6);
			$jsonArr = json_decode($jsonStr, true);
            if( !empty($jsonArr) ){
                foreach($jsonArr as $langId => $langStr){
                    $jsonArr[$langId] .= $suffixStr;
                }
                $jsonStr = json_encode($jsonArr); // wrip
            }
            $_returnLangFieldContent = $jsonFormatPrefix;
            $_returnLangFieldContent .= $jsonStr;
        }else{
            $_returnLangFieldContent = $langFieldContent;
            $_returnLangFieldContent .= $suffixStr;
        }
        return $_returnLangFieldContent;
    } // EOF appendSuffix2langField



	public function initAllRespTablesByDate($dateStr){
		$t1=$this->initRespTableByDate($dateStr);
		$t2=$this->initRespSyncTableByDate($dateStr);
		return [$t1, $t2];
	}

	public function initRespTableByDate($dateStr){
		$tableName=$this->getRespTableFullName($dateStr);

		// OGP-9829 workaround: Db::table_exists() was used as a guard condition.
		// However, it caches table list, and causes some side effects.  If the resp
		// table of the day is not created yet, and more than one API accesses need
		// storing result - like when changing player's password on game platforms -
		// the method will repeatedly create the table and cause an error.  This
		// doesn't happen on live host, for the resp table is created by cronjob;
		// the workaround is to prevent errors on staging.  Ported from xcyl.
		$this->CI->load->model(['response_result']);
		$respDB=$this->CI->response_result->getRespDB();
		$exists=$this->tableExistsOnMysql($tableName, $respDB);
		if (!$exists) {
			try{

			$this->CI->load->dbforge();

			$fields=[
				'id' => [
					'type' => 'BIGINT',
					'null' => false,
					'auto_increment' => TRUE,
					'unsigned' => true,
				],
				'external_system_id' => [
					'type' => 'INT',
					'null' => true,
				],
				'response_result_id' => [
					'type' => 'BIGINT',
					'null' => true,
					'unsigned' => true,
				],
				'content' => [
					'type' => 'JSON',
					'null' => true,
				],
				'request_api' => [
	                'type' => 'VARCHAR',
	                'constraint' => '200',
	                'null' => true,
				],
				'request_params' => [
					'type' => 'JSON',
					'null' => true,
				],
				'status_code' => [
	                'type' => 'VARCHAR',
	                'constraint' => '50',
	                'null' => true,
				],
				'player_id' => [
					'type' => 'INT',
					'null' => true,
				],
				'related_id1' => [
					'type' => 'INT',
					'null' => true,
				],
				'related_id2' => [
					'type' => 'INT',
					'null' => true,
				],
				'related_id3' => [
					'type' => 'INT',
					'null' => true,
				],
				'error_code' => [
					'type' => 'INT',
					'null' => true,
				],
				'cost_ms' => [
					'type' => 'INT',
					'null' => true,
				],
				'decode_result' => [
					'type' => 'JSON',
					'null' => true,
				],
				'external_transaction_id' => [
	                'type' => 'VARCHAR',
	                'constraint' => '200',
					'null' => true,
				],
				'full_url' => [
	                'type' => 'VARCHAR',
	                'constraint' => '500',
					'null' => true,
				],
				'request_id' => [
	                'type' => 'VARCHAR',
	                'constraint' => '64',
	                'null' => true,
				],
				'external_request_id' => [
	                'type' => 'VARCHAR',
	                'constraint' => '64',
					'null' => true,
				],
				'status' => [
					'type' => 'INT',
					'null' => false,
					'default' => 1, //1 is normal, 2, is error
				],
				'created_at' => [
					'type' => 'DATETIME',
					'null' => true,
				],
			];
			$this->CI->dbforge->add_field($fields);
			$this->CI->dbforge->add_key('id', TRUE);
			$this->CI->dbforge->add_key('external_system_id');
			$this->CI->dbforge->add_key('response_result_id');
			$this->CI->dbforge->add_key('request_id');
			$this->CI->dbforge->add_key('created_at');
			$this->CI->dbforge->add_key('player_id');
			$this->CI->dbforge->add_key('request_api');
			$this->CI->dbforge->add_key('error_code');
			$this->CI->dbforge->create_table($tableName);

			}catch(Exception $e){
				$this->error_log('create table failed: '.$tableName, $e);
			}
		}

		return $tableName;
	}

	public function initRespSyncTableByDate($dateStr){
		$tableName='resp_sync_'.$dateStr;

		if (!$this->table_really_exists($tableName)) {
			try{

			$this->CI->load->dbforge();

			$fields=[
				'id' => [
					'type' => 'INT',
					'null' => false,
					'auto_increment' => TRUE,
				],
				'external_system_id' => [
					'type' => 'INT',
					'null' => true,
				],
				'response_result_id' => [
					'type' => 'INT',
					'null' => true,
				],
				'filepath' => [
	                'type' => 'VARCHAR',
	                'constraint' => '500',
					'null' => true,
				],
				'request_api' => [
	                'type' => 'VARCHAR',
	                'constraint' => '200',
	                'null' => true,
				],
				'request_params' => [
					'type' => 'JSON',
					'null' => true,
				],
				'status_code' => [
	                'type' => 'VARCHAR',
	                'constraint' => '50',
	                'null' => true,
				],
				'player_id' => [
					'type' => 'INT',
					'null' => true,
				],
				'related_id1' => [
					'type' => 'INT',
					'null' => true,
				],
				'related_id2' => [
					'type' => 'INT',
					'null' => true,
				],
				'related_id3' => [
					'type' => 'INT',
					'null' => true,
				],
				'error_code' => [
					'type' => 'INT',
					'null' => true,
				],
				'cost_ms' => [
					'type' => 'INT',
					'null' => true,
				],
				'external_transaction_id' => [
	                'type' => 'VARCHAR',
	                'constraint' => '200',
					'null' => true,
				],
				'full_url' => [
	                'type' => 'VARCHAR',
	                'constraint' => '500',
					'null' => true,
				],
				'request_id' => [
	                'type' => 'VARCHAR',
	                'constraint' => '64',
	                'null' => true,
				],
				'external_request_id' => [
	                'type' => 'VARCHAR',
	                'constraint' => '64',
					'null' => true,
				],
				'status' => [
					'type' => 'INT',
					'null' => false,
					'default' => 1, //1 is normal, 2, is error
				],
				'created_at' => [
					'type' => 'DATETIME',
					'null' => true,
				],
			];
			$this->CI->dbforge->add_field($fields);
			$this->CI->dbforge->add_key('id', TRUE);
			$this->CI->dbforge->add_key('external_system_id');
			$this->CI->dbforge->add_key('response_result_id');
			$this->CI->dbforge->add_key('request_id');
			$this->CI->dbforge->add_key('created_at');
			$this->CI->dbforge->add_key('player_id');
			$this->CI->dbforge->add_key('request_api');
			$this->CI->dbforge->add_key('error_code');
			$this->CI->dbforge->create_table($tableName);

			}catch(Exception $e){
				$this->error_log('create table failed: '.$tableName, $e);
			}
		}

		return $tableName;
	}

	public function initRespCashierTableByMonth($monthStr){
		$tableName='resp_cashier_'.$monthStr;

		if (!$this->table_really_exists($tableName)) {
			try{

			$this->CI->load->dbforge();


			$fields=[
				'id' => [
					'type' => 'BIGINT',
					'null' => false,
					'auto_increment' => TRUE,
					'unsigned' => true,
				],
				'system_type_id' => [
					'type' => 'INT',
					'null' => true,
				],
				'content' => [
					'type' => 'JSON',
					'null' => true,
				],
				'filepath' => [
	                'type' => 'VARCHAR',
	                'constraint' => '500',
					'null' => true,
				],
				'note' => [
	                'type' => 'TEXT',
					'null' => true,
				],
				'created_at' => [
					'type' => 'DATETIME',
					'null' => true,
				],
				'request_api' => [
	                'type' => 'VARCHAR',
	                'constraint' => '200',
	                'null' => true,
				],
				'request_params' => [
					'type' => 'JSON',
					'null' => true,
				],
				'status_code' => [
	                'type' => 'VARCHAR',
	                'constraint' => '50',
	                'null' => true,
				],
				'status_text' => [
	                'type' => 'VARCHAR',
	                'constraint' => '200',
	                'null' => true,
				],
				'extra' => [
					'type' => 'TEXT',
					'null' => true,
				],
				'flag' => [
					'type' => 'INT',
					'null' => true,
				],
				'player_id' => [
					'type' => 'INT',
					'null' => true,
				],
				'related_id1' => [
					'type' => 'INT',
					'null' => true,
				],
				'related_id2' => [
					'type' => 'VARCHAR',
	                'constraint' => '200',
					'null' => true,
				],
				'related_id3' => [
					'type' => 'VARCHAR',
	                'constraint' => '200',
					'null' => true,
				],
				'sync_id' => [
					'type' => 'INT',
					'null' => true,
				],
				'external_transaction_id' => [
	                'type' => 'VARCHAR',
	                'constraint' => '200',
					'null' => true,
				],
				'full_url' => [
	                'type' => 'VARCHAR',
	                'constraint' => '500',
					'null' => true,
				],
				'request_id' => [
	                'type' => 'VARCHAR',
	                'constraint' => '64',
	                'null' => true,
				],
				'cost_ms' => [
					'type' => 'INT',
					'null' => true,
				],
				'external_request_id' => [
	                'type' => 'VARCHAR',
	                'constraint' => '64',
					'null' => true,
				],
				'status' => [
					'type' => 'INT',
					'null' => false,
					'default' => 1, //1 is normal, 2, is error
				],
				'error_code' => [
					'type' => 'INT',
					'null' => true,
				],
				'decode_result' => [
					'type' => 'JSON',
					'null' => true,
				],
			];

			$this->CI->dbforge->add_field($fields);
			$this->CI->dbforge->add_key('id', TRUE);
			$this->CI->dbforge->add_key('request_id');
			$this->CI->dbforge->add_key('created_at');
			$this->CI->dbforge->add_key('player_id');
			$this->CI->dbforge->add_key('system_type_id');
			$this->CI->dbforge->add_key('sync_id');
			$this->CI->dbforge->add_key('request_api');
			$this->CI->dbforge->add_key('error_code');
			$this->CI->dbforge->create_table($tableName);

			}catch(Exception $e){
				$this->error_log('create table failed: '.$tableName, $e);
			}
		}

		return $tableName;
	}

	public function initArchiveTableByYearMonth($sourceTableName, $targetTableName){
		if (!$this->table_really_exists($targetTableName)) {
			try{
				$this->CI->db->query("CREATE TABLE $targetTableName LIKE $sourceTableName");
			}catch(Exception $e){
				$this->error_log('create table failed: '.$targetTableName, $e);
			}
		}
	}

	public function getPromoCategoryIconPath($fileName = null) {
		$path=$this->getUploadPath();
		$this->utils->addSuffixOnMDB($path);

		if(isset($fileName)){
            return $path . '/system/promo-category-icon/' . $fileName;
        }

		return $path . '/system/promo-category-icon';
	}

	public function getQuestCategoryIconPath($fileName = null) {
		$path=$this->getUploadPath();
		$this->utils->addSuffixOnMDB($path);

		if(isset($fileName)){
            return $path . '/system/quest-category-icon/' . $fileName;
        }

		return $path . '/system/quest-category-icon';
	}

	public function getPromoCategoryIcon($icon) {
		return !empty($icon) ? $this->getUploadUri().'/system/promo-category-icon/' . $icon : $this->imageUrl('no.png');
	}

	public function getQuestCategoryIcon($icon) {
		return !empty($icon) ? $this->getUploadUri().'/questthumbnails/stable_center2/' . $icon : $this->imageUrl('no.png');
	}

	/**
	 * Wraps Player_promo::getAllPromoType.  Returns only promo categories enabled for promo manager.
	 * @return	array
	 */
	public function getAllPromoType() {
		$this->CI->load->model('player_promo');
		$types = $this->CI->player_promo->getAllPromoType();

		foreach ($types as & $t) {
			$t['name'] = lang($t['name']);
		}

		return $types;
	}

	public function getPasswordReg() {
		return $this->getConfig('default_regex_password');
	}

	/**
	 * get Username regex
	 *
	 *
	 * @param array $details For collect the related settings.
	 * @return string The regex string, restrict_regex_username.
	 */
	public function getUsernameReg(&$details=[]) {
		// return !empty($this->isRestrictUsernameEnabled()) ? $this->getConfig('restrict_regex_username') : $this->getConfig('default_regex_username');
		$details = $this->getUsernameRegWithDetails();
		return $details['restrict_regex_username'];
	}
	public function getUsernameRegWithDetails() {
		$this->CI->load->model(['operatorglobalsettings']);
		$player_validator = $this->getConfig('player_validator');
        $enable_restrict_username_more_options = !empty($this->getConfig('enable_restrict_username_more_options'));
		$usernameValidator = $player_validator['username'];
		$result = [];
		$result['restrict_username_enabled'] = $this->CI->operatorglobalsettings->getSettingJson('restrict_username_enabled');
        $result['username_case_insensitive'] = $this->getSettingJsonInOperatorglobalsettings('username_case_insensitive', Operatorglobalsettings::USERNAME_CASE_INSENSITIVE_DEFAULT);
		$result['username_requirement_mode'] = $this->getSettingJsonInOperatorglobalsettings('username_requirement_mode', Operatorglobalsettings::USERNAME_REQUIREMENT_MODE_DEFAULT);
        $result['restrict_regex_username'] = !empty($this->isRestrictUsernameEnabled()) ? $this->getConfig('restrict_regex_username') : $this->getConfig('default_regex_username');

		if(!$enable_restrict_username_more_options){
            $result['username_requirement_mode'] = Operatorglobalsettings::USERNAME_REQUIREMENT_MODE_USE_RESTRICT_REGEX;
            $result['username_case_insensitive'] = Operatorglobalsettings::USERNAME_CASE_INSENSITIVE_ENABLE;
        }else if( empty($result['restrict_username_enabled']) ){
			// If the Restrict Username switch is OFF,
			// Username Requirement default is Numbers and Letters only
			// and
			// Case Insensitive default is ON
			$result['username_requirement_mode'] = Operatorglobalsettings::USERNAME_REQUIREMENT_MODE_NUMBERS_AND_LETTERS_ONLY;
			$result['username_case_insensitive'] = Operatorglobalsettings::USERNAME_CASE_INSENSITIVE_ENABLE;
		}
		switch($result['username_requirement_mode']){
            default:
            case Operatorglobalsettings::USERNAME_REQUIREMENT_MODE_USE_RESTRICT_REGEX:
                $result['restrict_regex_username'] = !empty($this->isRestrictUsernameEnabled()) ? $this->getConfig('restrict_regex_username') : $this->getConfig('default_regex_username');
                break;
			case Operatorglobalsettings::USERNAME_REQUIREMENT_MODE_NUMBER_ONLY:
				$result['restrict_regex_username'] = $usernameValidator['restrict_regex_number_only'];
				break;
			case Operatorglobalsettings::USERNAME_REQUIREMENT_MODE_LETTERS_ONLY:
				$result['restrict_regex_username'] = $usernameValidator['restrict_regex_letters_only'];
				break;
			case Operatorglobalsettings::USERNAME_REQUIREMENT_MODE_NUMBERS_AND_LETTERS_ONLY:
				$result['restrict_regex_username'] = $usernameValidator['restrict_regex_number_letters_only'];
				break;
		}
		return $result;
	}

	public function getUsernameRegForJS(&$details) {
		// return !empty($this->isRestrictUsernameEnabled()) ? $this->getConfig('restrict_regex_username_js') : $this->getConfig('default_regex_username_js');
		$details = $this->getUsernameRegForJSWithDetails();
		return $details['restrict_regex_username_js'];
	}
	public function getUsernameRegForJSWithDetails() {
		$this->CI->load->model(['operatorglobalsettings']);
		$player_validator = $this->getConfig('player_validator');
        $enable_restrict_username_more_options = !empty($this->getConfig('enable_restrict_username_more_options'));

		$usernameValidator = $player_validator['username'];
		$result = [];
		$result['restrict_username_enabled'] = $this->CI->operatorglobalsettings->getSettingJson('restrict_username_enabled');
		$result['username_case_insensitive'] = $this->getSettingJsonInOperatorglobalsettings('username_case_insensitive', Operatorglobalsettings::USERNAME_CASE_INSENSITIVE_DEFAULT);
		$result['username_requirement_mode'] = $this->getSettingJsonInOperatorglobalsettings('username_requirement_mode', Operatorglobalsettings::USERNAME_REQUIREMENT_MODE_DEFAULT);
		$result['restrict_regex_username_js'] = !empty($this->isRestrictUsernameEnabled()) ? $this->getConfig('restrict_regex_username_js') : $this->getConfig('default_regex_username_js');
        if(!$enable_restrict_username_more_options){
            $result['username_requirement_mode'] = Operatorglobalsettings::USERNAME_REQUIREMENT_MODE_USE_RESTRICT_REGEX;
            $result['username_case_insensitive'] = Operatorglobalsettings::USERNAME_CASE_INSENSITIVE_ENABLE;
        }else if( empty($result['restrict_username_enabled']) ){
			// If the Restrict Username switch is OFF,
			// Username Requirement default is Numbers and Letters only
			// and
			// Case Insensitive default is ON
			$result['username_requirement_mode'] = Operatorglobalsettings::USERNAME_REQUIREMENT_MODE_NUMBERS_AND_LETTERS_ONLY;
			$result['username_case_insensitive'] = Operatorglobalsettings::USERNAME_CASE_INSENSITIVE_ENABLE;
		}
		switch($result['username_requirement_mode']){
            default:
            case Operatorglobalsettings::USERNAME_REQUIREMENT_MODE_USE_RESTRICT_REGEX:
                $result['restrict_regex_username_js'] = !empty($this->isRestrictUsernameEnabled()) ? $this->getConfig('restrict_regex_username_js') : $this->getConfig('default_regex_username_js');
                break;
			case Operatorglobalsettings::USERNAME_REQUIREMENT_MODE_NUMBER_ONLY:
				$result['restrict_regex_username_js'] = $usernameValidator['restrict_regex_js_number_only'];
				break;
			case Operatorglobalsettings::USERNAME_REQUIREMENT_MODE_LETTERS_ONLY:
				$result['restrict_regex_username_js'] = $usernameValidator['restrict_regex_js_letters_only'];
				break;
			case Operatorglobalsettings::USERNAME_REQUIREMENT_MODE_NUMBERS_AND_LETTERS_ONLY:
				$result['restrict_regex_username_js'] = $usernameValidator['restrict_regex_js_number_letters_only'];
				break;
		}
		return $result;
	}

	public function isRestrictUsernameEnabled() {
		$this->CI->load->model(['operatorglobalsettings']);
		$result = $this->CI->operatorglobalsettings->getSettingJson('restrict_username_enabled');
		if(is_null($result)) {
			$this->CI->operatorglobalsettings->syncSettingJson("restrict_username_enabled", '1', 'value');
			return $this->CI->operatorglobalsettings->getSettingJson('restrict_username_enabled');
		}

		return $result;
	}

	public function getSettingJsonInOperatorglobalsettings($setting_name, $defaultStr = '0') {
		$this->CI->load->model(['operatorglobalsettings']);
		$result = $this->CI->operatorglobalsettings->getSettingJson($setting_name);
		if(is_null($result)) {
			$this->CI->operatorglobalsettings->syncSettingJson($setting_name, $defaultStr, 'value');
			$result = $this->CI->operatorglobalsettings->getSettingJson($setting_name);
		}
		return $result;
	} // EOF getSettingJsonInOperatorglobalsettings

	public function isPasswordMinMaxEnabled() {
		$this->CI->load->model(['operatorglobalsettings']);
		$result = $this->CI->operatorglobalsettings->getSettingJson('set_password_min_max');
		$default_password_data = $this->utils->getConfig('player_validator');
       	$default_password_data = array(
                                    "min" => $default_password_data['password']['min'],
                                    "max" => $default_password_data['password']['max'],
                                );
		if(empty($result)){
			$this->CI->operatorglobalsettings->syncSettingJson("set_password_min_max", $default_password_data, 'value');
			$result = $this->CI->operatorglobalsettings->getSettingJson('set_password_min_max');
		}
		$result['password_min_max_enabled'] = ($default_password_data === $result) ? false : true;
		return $result;
	}

	/**
	 * Formula copied from postResetMainPassword()
	 * @see 	player_password_module::postResetMainPassword()
	 * @see		player_center2/Security::index()
	 * @return	array
	 */
	public function passwordLenLimits() {
		/**
		 * enabled: { min:int, max:int, password_min_max_enabled:bool }
		 * See method definition above
		 */
		$enabled = $this->isPasswordMinMaxEnabled();

		// $enabled always not empty => min/max always use values from $enabled!?
		$min = !empty($enabled) ? $enabled['min'] : $this->utils->getConfig('default_min_size_password');
		$max = !empty($enabled) ? $enabled['max'] : $this->utils->getConfig('default_max_size_password');

		return [ 'min' => $min, 'max' => $max ];
	}

	public function getPlayerCenterLanguage() {
		$this->CI->load->model(array('operatorglobalsettings'));
		$langSetup = $this->CI->operatorglobalsettings->getSettingJson('player_center_language');
		$default_player_language = $this->getConfig('default_player_language');

		if(empty($default_player_language)){
			$default_player_language = self::CHINESE_LANG_INT;
		} else {
			$this->CI->load->library('language_function');
			$default_player_language = $this->CI->language_function->langStrToInt($default_player_language);
		}

		$domain_prefer_language = $this->getConfig('domain_prefer_language');
		if(!empty($domain_prefer_language)){
		    $main_host = $this->getMainHostName();
		    if(isset($domain_prefer_language[$main_host])){
		        return $domain_prefer_language[$main_host];
            }
        }

		if (!isset($langSetup) || is_array($langSetup)) {
			$this->CI->operatorglobalsettings->syncSettingJson("player_center_language", $default_player_language, 'value');
			return $this->CI->operatorglobalsettings->getSettingJson('player_center_language');
		}

		return $langSetup;
	}

	public function isForcePlayerCenterToDefaultLanguage() {
		$this->CI->load->model(array('operatorglobalsettings'));
		$isForceToDefault = $this->CI->operatorglobalsettings->getSettingJson('force_to_default_language');
		$default_player_language = $this->getConfig('default_player_language');

		if(empty($default_player_language)){
			$default_player_language = self::CHINESE_LANG_INT;
		} else {
			$this->CI->load->library('language_function');
			$default_player_language = $this->CI->language_function->langStrToInt($default_player_language);
		}

		if (!isset($isForceToDefault)) {
			$this->CI->operatorglobalsettings->syncSettingJson("force_to_default_language", $default_player_language, 'value');
			return $this->CI->operatorglobalsettings->getSettingJson('force_to_default_language');
		}

		return $isForceToDefault;
	}

	public function isRetainCurrentLanguage() {
		$this->CI->load->model(array('operatorglobalsettings'));
		$isRetainCurrentLanguage = $this->CI->operatorglobalsettings->getSettingJson('retain_player_current_language');
		return $isRetainCurrentLanguage;
	}

	public function formatTimeForMysql(\DateTime $d) {
		if ($d) {
			return $d->format('H:i:s');
		}
		return null;
	}

	public function isAllEnabledApi($arr) {
		$this->CI->load->model(['external_system']);
		return $this->CI->external_system->isAllEnabledApi($arr);
	}

	public function isAnyEnabledApi($arr) {
		$this->CI->load->model(['external_system']);
		return $this->CI->external_system->isAnyEnabledApi($arr);
	}

	public function getAllActiveGameApiId(){
		$this->CI->load->model(['external_system']);

		$rows= $this->CI->external_system->getAllActiveSytemGameApi();
		$idList=[];

		foreach ($rows as $row) {
			$idList[]=$row['id'];
		}

		return $idList;
	}

	public function isGameApiIdActive($id){
		$list = $this->getAllActiveGameApiId();
		if(in_array($id, $list)){
			return true;
		}
		return false;
	}

	/**
	 * Reads settings about uploaded sys logo from operatorsettings.  Uses key name returned by Utils::getDefaultLogoSettingName() ('sys_default_logo' now).
	 * @return	array 	[ path, filename, use_sys_default ]
	 */
	public function getSysLogoSettingFromOperatorSettings() {
		$operator_settings_key = $this->getDefaultLogoSettingName();
	    $sys_logo_settings = $this->getOperatorSettingJson($operator_settings_key);
	    return $sys_logo_settings;
	}

	/**
	 * Returns default logo upload dir
	 * @return 	string
	 */
	public function getSysLogoUploadPath() {
		$sys_logo = $this->getSysLogoSettingFromOperatorSettings();
		$sub_path = $this->default_upload_sub_dir_for_logo;
		if (isset($sys_logo['path']) && !empty($sys_logo['path'])) {
			$sub_path = $sys_logo['path'];
		}
		$path=$this->getUploadPath();
		$this->addSuffixOnMDB($path);
		$upload_path = "{$path}/{$sub_path}";
		return $upload_path;
	}

	/**
	 * Returns URL for uploaded system logo.
	 * @return	string
	 */
	public function getUploadedSysLogoUrl() {
		$sys_logo = $this->getSysLogoSettingFromOperatorSettings();
		$path=$this->default_logo_dir_url;
		$this->addSuffixOnMDB($path);
		$www_path = "{$path}/{$sys_logo['path']}{$sys_logo['filename']}";
		return $www_path;
	}

	/**
	 * Returns real path (filesystem path) for uploaded system logo.
	 * @return	string
	 */
	public function getUploadedSysLogoRealPath() {
		$logo_upload_path = $this->getSysLogoUploadPath();
		$sys_logo = $this->getSysLogoSettingFromOperatorSettings();
		$real_path = "{$logo_upload_path}/{$sys_logo['filename']}";
		return $real_path;
	}

	/**
	 * Maps subpath in public directory to physical path
	 * @param 	string	$public_file_url
	 * @return 	string	physical path of file
	 */
	public function getRealPathForPublicFile($public_file_url) {
		$dir_public = APPPATH . '../public';
		return $dir_public . $public_file_url;
	}

	public function getAgentTrackingCodeFromSession() {
		$tracking_code = $this->CI->session->userdata('ag_tracking_code');
		if (empty($tracking_code)) {
			$this->CI->load->helper('cookie');
			$tracking_code = get_cookie('_og_ag_tracking_code');
		}

		if (empty($tracking_code)) {
			$tracking_code = null;
		}

		return $tracking_code;
	}

    public function setAgentTrackingCodeToSession($tracking_code) {
        if (!empty($tracking_code)) {
            $this->CI->session->set_userdata('ag_tracking_code', $tracking_code);
            $this->CI->load->helper('cookie');
            //30 days
            set_cookie('_og_ag_tracking_code', $tracking_code, 86400 * 30);

            return true;
        }

        return false;
    }

    public function clearAgentTrackingCodeFromSession() {
        $this->CI->load->helper('cookie');
        $this->CI->session->unset_userdata('ag_tracking_code');
        delete_cookie('_og_ag_tracking_code');
    }

    public function setAgentTrackingSourceCodeToSession($tracking_code) {
        if (!empty($tracking_code)) {
            $this->CI->session->set_userdata('agent_tracking_source_code', $tracking_code);
            $this->CI->load->helper('cookie');
            //30 days
            set_cookie('_og_agent_tracking_source_code', $tracking_code, 86400 * 30);

            return true;
        }

        return false;
    }

    public function clearAgentTrackingSourceCodeFromSession() {
        $this->CI->load->helper('cookie');
        $this->CI->session->unset_userdata('agent_tracking_source_code');
        delete_cookie('_og_agent_tracking_source_code');
    }

	public function getAllSystemMap() {
		$this->CI->load->model('external_system');
		$allSys = $this->CI->external_system->getAllSystemApis();
		$apiArray = array();
		foreach ($allSys as $aSys) {
			$apiArray[$aSys['id']] = $aSys['system_code'];
		}
		return $apiArray;
	}

	public function getLiveChatOnClick(){
		$liveChatConfig = $this->utils->getConfig('live_chat');

		if(!empty($liveChatConfig['external_onclick'])){
			return $liveChatConfig['external_onclick'];
		}

		return 'window.open(\''.$this->getLiveChatLink().'\')';
	}

    public function getPlayerCenterCustomHeader(){
        return $this->getConfig('player_center_custom_header');
    }

    public function getPlayerCenterCustomFooter(){
        return $this->getConfig('player_center_custom_footer');
    }

    public function getMobilePlayerCenterCustomHeader(){
        return $this->getConfig('mobile_player_center_custom_header');
    }

    public function getMobilePlayerCenterCustomFooter(){
        return $this->getConfig('mobile_player_center_custom_footer');
    }

	public function getMobilePlayerCenterCustomDynamicFooter(){
        return $this->getConfig('mobile_player_center_custom_dynamic_footer');
    }

    public function getAffiliateCustomHeader(){
        return $this->getConfig('affiliate_custom_header');
    }

    public function getAffiliateCustomFooter(){
        return $this->getConfig('affiliate_custom_footer');
    }

	public function onlyGetLastIP($ipStr){
		if(!empty($ipStr)){

			$arr=explode(',',$ipStr);
			if(!empty($arr)){
				return trim($arr[count($arr)-1]);
			}
		}

		return '';
	}

	/**
	 * send message from admin to player when some condition enable
	 * @param int $user_id messages adminId
	 * @param int $playerId messages player id
	 * @param string $subject messages subject
	 * @param string $sender messagedetails sender
	 * @param string $message messagedetails message
	 *
	 * @return int
	 */
	public function adminSendMsg($userId,$playerId,$sender,$subject,$message){
        $this->CI->load->model('internal_message');
        return $this->CI->internal_message->addNewMessageAdmin($userId, $playerId, $sender, $subject, $message);
    }

	private function getLang($langKey) {
		return lang($langKey);
	}

    public function generate_lang_text_array(){
		return ['button_login' => $this->getLang("lang.logIn"),
			'form_field_username' => $this->getLang('form.field.username'),
			'form_field_password' => $this->getLang('form.field.password'),
			'text_loading' => lang('text.loading'),
			'label_captcha' => lang('label.captcha'),
			'form_register' => $this->getLang('lang.register'),
			'form_register_games' => $this->getLang('lang.aregister'),
			'header_trial_game' => $this->getLang('header.trial_game'),
			'button_logout' => $this->getLang('header.logout'),
			'button_membership' => $this->getLang('sidemenu.membership'),
			'player_center' => $this->getLang('player_center'),
			'header_memcashier' => $this->getLang('Cashier'),
            'header_transfermoney' => $this->getLang('Transfer Money'),
            'header_transferfund' => $this->getLang('Transfer Fund'),
            'header_deposit' => $this->getLang('Deposit'),
            'header_depositnow' => $this->getLang('Deposit Now'),
            'header_deposit_short_name' => $this->getLang('deposit'),
			'header_memcenter' => $this->getLang('header.memcenter'),
            'header_playerinfo' => $this->getLang('Player Information'),
			'header_withdrawal' => $this->getLang('Withdrawal'),
			'header_mainwallet' => $this->getLang('header.mainwallet'),
			'header_information' => $this->getLang('header.information'),
			'header_report' => $this->getLang('Search'),
			'header_messages' => $this->getLang('site.Messages'),
			'header_cashier_center' => $this->getLang('site.CashierCenter'),
			'header_fund_management' => $this->getLang('Fund Management'),
			'header_acct_info' => $this->getLang('Account Information'),
			'header_refer_a_friend' => $this->getLang('Refer a friend'),
			'header_help_and_support' => $this->getLang('Help & Support'),
			'header_security' => $this->getLang('Security'),
			'header_acct_history' => $this->getLang('Account History'),
			'header_vip_group' => $this->getLang('VIP Group'),
			'header_promotions' => $this->getLang('Promotions'),
			'header_fav_games' => $this->getLang('Favorite Games'),
			'header_shop' => $this->getLang('Shop'),
			'header_sportsbook' => $this->getLang('Sportsbook'),
			'header_live_casino' => $this->getLang('Live Casino'),
			'header_poker' => $this->getLang('wc.04'),
			'header_poker_games' => $this->getLang('Poker Games'),
			'header_affiliate' => $this->getLang('a_header.affiliate'),
			'header_sports' => $this->getLang('Sports'),
			'header_casino' => $this->getLang('Casino'),
			'header_slot' => $this->getLang('header.slots'),
			'header_slots' => $this->getLang('Slots'),
			'header_esports' => $this->getLang('Esports'),
			'header_slots_games' => $this->getLang('Slot Games'),
			'header_lottery' => $this->getLang('Lottery'),
			'header_slots_featured' => $this->getLang('Featured games'),
			'header_live_dealer' => $this->getLang('Live dealer'),
			'header_cardgames' => $this->getLang('Table games'),
			'header_lottery_scratch_cards' => $this->getLang('Lottery scratch card'),
			'header_bingo_keno' => $this->getLang('Bingo keno'),
			'header_video_sport' => $this->getLang('Video sport'),
			'header_video_poker' => $this->getLang('Video poker'),
			'header_2by2_games' => $this->getLang('2by2 games'),
			'header_ainsworth_games' => $this->getLang('Ainsworth games'),
			'header_pragmatic_play' => $this->getLang('Pragmatic play'),
			'header_genesis_gaming' => $this->getLang('Genesis gaming'),
			'header_coming_soon' => $this->getLang('Coming Soon'),
            'header_Announcements' => $this->getLang('Announcements'),
            'header_5_percent_deposit_bonus' => $this->getLang('header.5_percent_deposit_bonus'),
            'header_crash' => $this->getLang('Crash'),
            'header_double' => $this->getLang('Double'),
            'header_dice' => $this->getLang('Dice'),
            'header_coins' => $this->getLang('Hi-Lo'),
            'header_vip_club' => $this->getLang('VIP Club'),
			'message_success_login' => $this->getLang('gen.success.login'),
			'message_success_logout' => $this->getLang('gen.success.logout'),
			'forgotpassword' => $this->getLang('lang.forgotpasswd'),
			'welcomeback' => $this->getLang('Welcome Back'),
			'livehelp' => $this->getLang('cashier.40'),
			'livechat' => $this->getLang('Live Chat'),
			'join_now' => $this->getLang('join.now'),
            'login' => $this->getLang('lang.logIn'),
            'player.first_name' => $this->getLang('First Name'),
            'player.username' => $this->getLang('Username'),
            'player.contact_number' => $this->getLang('Contact Number'),
            'player.email_address' => $this->getLang('Email Address'),
			'captcha_required' => $this->getLang('captcha.required'),
			'confirm_go_to_mobile' => $this->getLang('confirm_go_to_mobile'),
			'my_account' => $this->getLang('My Account'),
			'app_download' => $this->getLang('app download'),
			'browser' => $this->getLang('Browser'),
			'gambling_license' => $this->getLang('Gambling license'),
			'footer_responsible_gambling' => $this->getLang('footer_responsible_gambling'),
			'footer_terms_and_conditions' => $this->getLang('footer_terms_and_conditions'),
			'footer_general_disclaimer' => $this->getLang('footer_general_disclaimer'),
			'footer_privacy_policy' => $this->getLang('footer.02'),
			'footer_games_rules' => $this->getLang('footer_games_rules'),
			'footer_help_centre' => $this->getLang('footer_help_centre'),
            'footer_help' => $this->getLang('footer.help'),
            'footer_provability_explained' => $this->getLang('footer.provability_explained'),
            'footer_bonus_term_and_conditions' => $this->getLang('footer.bonus_term_and_conditions'),
            'footer_pay_safe' => $this->getLang('footer.pay_safe'),
            'footer_affiliate_program' => $this->getLang('footer.05'),
			'footer_payment_methods' => $this->getLang('footer_payment_methods'),
			'footer_contact_us' => $this->getLang('footer_contact_us'),
			'footer_banking_option' => $this->getLang('footer_banking_option'),
			'footer_guidelines' => $this->getLang('footer.guidelines'),
			'footer_network_hub' => $this->getLang('footer.network_hub'),
			'kgvip_content_001' => $this->getLang('kgvip_content_001'),
			'kgvip_disclaimer' => $this->getLang('kgvip_disclaimer'),
			'kgvip_copyright' => $this->getLang('kgvip_copyright'),
			'kgvipen_disclamimer' => $this->getLang('kgvipen_disclamimer'),
			'liwei_content_001' => $this->getLang('liwei_content_001'),
			'liwei_disclaimer' => $this->getLang('liwei_disclaimer'),
			'liwei_copyright' => $this->getLang('liwei_copyright'),
			'lequ_content_001' => $this->getLang('lequ_content_001'),
			'lequ_disclaimer' => $this->getLang('lequ_disclaimer'),
			'lequ_copyright' => $this->getLang('lequ_copyright'),
			'new' => $this->getLang('New'),
			'header_mobile' => $this->getLang('Mobile'),
			'header_home' => $this->getLang('header.home'),
			'header_notification' => $this->getLang('Notifications'),
			'header_game' => $this->getLang('Game'),
			'header_games' => $this->getLang('lang.games'),
			'header_live_game' => $this->getLang('cms.livegames'),
			'header_blog' => $this->getLang('Blog'),
			'header_fishing' => $this->getLang('header.fishing'),
			'play_now' => $this->getLang('wc.11'),
			'play_now_games' => $this->getLang('Play'),
			'football' => $this->getLang('Football'),
			'basketball' => $this->getLang('Basketball'),
			'tennis' => $this->getLang('Tennis'),
			'horse_racing' => $this->getLang('Horse Racing'),
			'dog_racing' => $this->getLang('Dog Racing'),
			'club_massimo' => $this->getLang('Club Massimo'),
			'club_palazzo' => $this->getLang('Club Palazzo'),
			'club_gallardo' => $this->getLang('Club Gallardo'),
			'club_nouvo' => $this->getLang('Club Nouvo'),
			'club_apollo' => $this->getLang('Club Apollo'),
			'club_divino' => $this->getLang('Club Divino'),
			'quick_transfer_mainwallet' => $this->getLang('Main Wallet'),
			'quick_transfer_subwallet' => $this->getLang('Sub Wallet'),
			'subwallet' => $this->getLang('Sub Wallet'),
			'quick_transfer_qt' => $this->getLang('Quick Transfer'),
			'quick_transfer_transfer' => $this->getLang('Transfer'),
			'transfer' => $this->getLang('Transfer'),
			'quick_deposit_qd' => $this->getLang('Quick Deposit'),
			'quick_deposit_min_dep_amt' => $this->getLang('Minimum Deposit Amount'),
			'quick_deposit_max_dep_amt' => $this->getLang('Maximum Deposit Amount'),
			'quick_deposit_use_for_dep_amt' => $this->getLang('Use for Deposit Amount'),
			'quick_deposit_dep' => $this->getLang('Deposit'),
			'quick_deposit_change_acct' => $this->getLang('Change Account'),
			'set_language' => $this->getLang('system.word2'),
			'live_dealers' => $this->getLang('Live Dealers'),
			'view_games' => $this->getLang('View Games'),
			'sports_book' => $this->getLang('Sports Book'),
			'header_affiliates' => $this->getLang('Affiliates'),
			'go_to_casino_page' => $this->getLang('Go To Casino Page'),
			'go_to_live_dealers_page' => $this->getLang('Go To Live Dealers Page'),
			'go_to_sports_book_page' => $this->getLang('Go To Sports Book Page'),
			'go_to_poker_page' => $this->getLang('Go To Poker Page'),
			'live_dealer' => $this->getLang('tags.LiveDealer'),
			'player_center_copyright' => $this->getLang('player_center_copyright'),
			'player_center_about' => $this->getLang('player_center_about'),
			'learn_more' => $this->getLang('Learn More'),
			'info_centre' => $this->getLang('Info Centre'),
			'connect_with_us' => $this->getLang('Connect With Us'),
			'demo' => $this->getLang('Demo'),
			'footer_news' => $this->getLang('News'),
			'player_center_about_content' => $this->getLang('player_center_about_content'),
			'create_your_account_now' => $this->getLang('Create your account now'),
			'and_get_a_signup_bonus' => $this->getLang('and get a signup bonus'),
			'banking_options' =>  $this->getLang('banking_options'),
			'poker_description' =>  $this->getLang('poker.description'),
			'footer_faq' =>  $this->getLang('FAQ'),
			'sina_weibo' =>  $this->getLang('Sina Weibo'),
			'tencent_weibo' =>  $this->getLang('Tencent Weibo'),
			'baccarat' =>  $this->getLang('Baccarat'),
			'roulette' =>  $this->getLang('Roulette'),
			'sic_bo' =>  $this->getLang('Sic Bo'),
			'dragon_tiger' =>  $this->getLang('Dragon Tiger'),
			'bull_bull' =>  $this->getLang('Bull Bull'),
			'black_jack' =>  $this->getLang('Black Jack'),
			'win_three_cards' =>  $this->getLang('Win Three Cards'),
			'more' =>  $this->getLang('more'), // OGLANG-18
			'sportsbook_desc' =>  $this->getLang('sportsbook_desc'), // OGLANG-61
			'bullfight' =>  $this->getLang('Bullfight'),
            'Wallet_Total' => $this->getLang('Wallet Total'),
            'Total_Balance' => $this->getLang('Total Balance'),
            'Main_Wallet_Total' => $this->getLang('Main Wallet Total'),
            'Game_Wallet_Total' => $this->getLang('Game Wallet Total'),
            'Pending_Balance' => $this->getLang('cashier.pendingBalance.playerWallet'),
            'alert-success' => $this->getLang('alert-success'),
            'alert-info' => $this->getLang('alert-info'),
            'alert-warning' => $this->getLang('alert-warning'),
            'alert-danger' => $this->getLang('alert-danger'),
            'close_button_text' => $this->getLang('lang.close'),
            'confirm_button_text' => lang('Confirm'),
            'submit_button_text' => lang('lang.submit'),
            'form.validation.invalid_minlength' => sprintf(lang('form.validation.invalid_minlength'), '{0}', '{1}'),
            'form.validation.invalid_maxlength' => sprintf(lang('form.validation.invalid_maxlength'), '{0}', '{1}'),
            'form.validation.invalid_regex' => sprintf(lang('form.validation.invalid_regex'), '{0}'),
            'Invalid transfer amount' => $this->getLang('Invalid transfer amount'),
            'Invalid product wallet' => $this->getLang('Invalid product wallet'),
            "Select Transfer From" => $this->getLang('cashier.18'),
            "Select Transfer To" => $this->getLang('cashier.19'),
            "Transfer" => $this->getLang('Transfer'),
            "Transfer All" => $this->getLang('Transfer All'),
            "Transfer Back All" => $this->getLang('Transfer Back All'),
            "lang.refreshbalance" => $this->getLang('lang.refreshbalance'),
            "Transfer Processing" => $this->getLang('Transfer Processing'),
            "No enough balance" => $this->getLang('No enough balance'),
            "Reset" => $this->getLang('Reset'),
            "Unlimited" => $this->getLang('Unlimited'),
            "Transfer In" => $this->getLang('Transfer In'),
            "Transfer Out" => $this->getLang('Transfer Out'),
            "minimal_transfer_amount_label" => $this->getLang('minimal_transfer_amount_label'),
            "maxmal_transfer_amount_label" => $this->getLang('maxmal_transfer_amount_label'),
            "whether_to_allow_decimals_to_transfer_label" => $this->getLang('whether_to_allow_decimals_to_transfer_label'),
            "Quick Transfer Mode" => $this->getLang('Quick Transfer Mode'),
            "Pro Transfer Mode" => $this->getLang('Pro Transfer Mode'),
            "Select game to transfer money from" => $this->getLang('Select game  to transfer money from'),
            "Select game to transfer money to" => $this->getLang('Select game  to transfer money to'),
            "Change Currency Failed" => $this->getLang('Change Currency Failed'),
            "Changing Currency" => $this->getLang('Changing Currency'),
            "The request should wait %d seconds before replaying" => $this->getLang('The request should wait %d seconds before replaying.'),
            "The page will wait %d seconds before reloading" => $this->getLang('The page will wait %d seconds before reloading.'),
            "System is busy, please wait {0} seconds before trying again" => $this->getLang('System is busy, please wait {0} seconds before trying again.'),
            "All" => $this->getLang('All'),
            "wallet_center" => $this->getLang('Cashier Center'),
            "header_partner" => $this->getLang('header.affiliate2'),
            "header_faq" => $this->getLang('hxpj.footer_bar.links.common_problems'),
            "language" => $this->getLang('player.62'),
            "fundaccount" => $this->getLang('Fund Account'),
            "emailaddress" => $this->getLang('player.06'),
            "wechatid" => $this->getLang('wechat'),
            "license" => $this->getLang('License'),
            "qqid" => $this->getLang('player.23'),
            "copyright" => $this->getLang('Copy Right'),
            "allrightreserved" => $this->getLang('All rights reserved'),
            "devicesupport" => $this->getLang('Device Support'),
            "header_slot_games" => $this->getLang('header_slot_games'),
            "Transfer Amount Exceeding the max limit" => $this->getLang('Transfer Amount Exceeding the max limit'),
            "Transfer Amount Below the min limit" => $this->getLang('Transfer Amount Below the min limit'),
            "header_contact_us_form" => $this->getLang('contact_us_form'),
            "change_password" => $this->getLang('Change Password'),
            "cs.reply" => $this->getLang('cs.reply'),
            "game_providers" => $this->getLang('Game Providers'),
            "keno_lotto" => $this->getLang('keno_lotto'),
            "q_and_a" => $this->getLang('q_and_a'),
            "footer_responsibility" => $this->getLang('footer_responsibility'),
            "footer_condition" => $this->getLang('footer_conditions'),
            "Confirm" => $this->getLang('Confirm'),
            "Cancel" => $this->getLang('Cancel'),
            "The Deposits Channel of Money" => $this->getLang('The Deposits Channel of Money'),
            "CHAT NOW" => $this->getLang('CHAT NOW'),
            "CHAT" => $this->getLang('CHAT'),
            "LINE" => $this->getLang('LINE'),
            "CALL" => $this->getLang('CALL'),
            "header_welcome" => $this->getLang('Welcome'),
            "header_balance" => $this->getLang('Balance'),
            "footer_gambling" => $this->getLang('Gambling Theraphy'),
            "header_deposit_withdraw" => $this->getLang('header_deposit_withdraw'),
            "manage_player_information" => $this->getLang('Manage Player Information'),
			"header_vip" => $this->getLang('VIP'),
			'player_greetings_hi' => $this->getLang('Hi'),
			"header_app" => $this->getLang('header_app'),
			"footer_banking" => $this->getLang('footer_banking'),
			"footer_faq" => $this->getLang('footer_faq'),
			"footer_content1" => $this->getLang('footer_content1'),
			"footer_content2" => $this->getLang('footer_content2'),
			"footer_content3" => $this->getLang('footer_content3'),
			"footer_content4" => $this->getLang('footer_content4'),
			"header_entaplay" => $this->getLang('header_entaplay'),
			"service_advantages" => $this->getLang('service_advantages'),
			"average_time" => $this->getLang('average_time'),
			"hot_promotion" => $this->getLang('hot_promotion'),
			"daily_mini" => $this->getLang('daily_mini'),
			"join_now" => $this->getLang('join_now'),
			"license" => $this->getLang('License'),
			"banks_transfer" => $this->getLang('banks_transfer'),
			"internet_banking" => $this->getLang('internet_banking'),
			"footer_content5" => $this->getLang('footer_content5'),
			"footer_content6" => $this->getLang('footer_content6'),
			"footer_slots" => $this->getLang('footer_slots'),
			"footer_deposit" => $this->getLang('footer_deposit'),
			"footer_withdrawal" => $this->getLang('footer_withdrawal'),
			"header_nav" => $this->getLang('nav'),
			"official_partners" => $this->getLang('official_partners'),
			"national_club" => $this->getLang('national_club'),
			"toll_free_call" => $this->getLang('toll_free_call'),
			"novice_school" => $this->getLang('novice_school'),
			"five_minutes_to_help_you_enjoy" => $this->getLang('five_minutes_to_help_you_enjoy'),
			"view_novice_school" => $this->getLang('view_novice_school'),
			"convenient_functions" => $this->getLang('convenient_functions'),
			"fast_game_never_lost" => $this->getLang('fast_game_never_lost'),
			"client" => $this->getLang('client'),
			"add_to_favorites" => $this->getLang('add_to_favorites'),
			"setup_as_front_page" => $this->getLang('setup_as_front_page'),
			"hold_a_pagcor_legal_license" => $this->getLang('hold_a_pagcor_legal_license'),
			"view_gaming_license" => $this->getLang('view_gaming_license'),
			"cooperation"  => $this->getLang('cooperation'),
			"game_introduction" => $this->getLang('game_introduction'),
			"deposit_method" => $this->getLang('deposit_method'),
			'footer_additional'					=> $this->getLang('footer.additional') ,
			'footer_about_us_title'				=> $this->getLang('footer.about_us.title') ,
			'footer_about_us_contents'			=> $this->getLang('footer.about_us.contents') ,
			'footer_why_choose_title'			=> $this->getLang('footer.why_choose.title') ,
			'footer_why_choose_contents'		=> $this->getLang('footer.why_choose.contents') ,
			'footer_member_and_aff_title'		=> $this->getLang('footer.member_and_aff.title') ,
			'footer_member_and_aff_contents'	=> $this->getLang('footer.member_and_aff.contents') ,
			'footer_online_betting_title'		=> $this->getLang('footer.online_betting.title') ,
			'footer_online_betting_contents'	=> $this->getLang('footer.online_betting.contents') ,
			'footer_suggestion_title'			=> $this->getLang('footer.suggestion.title') ,
			'footer_suggestion_contents'		=> $this->getLang('footer.suggestion.contents') ,
			'footer_aff_title'					=> $this->getLang('footer.aff.title') ,
			'footer_aff_contents'				=> $this->getLang('footer.aff.contents') ,
			'footer_game_providers'			=> $this->getLang('footer.game_providers') ,
			'footer_money_deposit_channel'	=> $this->getLang('footer.money_deposit_channel') ,
			'footer_monitor_control_by'	=> $this->getLang('footer.monitor_control_by') ,
			'footer_transfer_through'	=> $this->getLang('footer.transfer_through') ,
			'footer_local_bank'		=> $this->getLang('footer.local_bank') ,
			'footer_call_us'		=> $this->getLang('footer.call_us') ,
			'footer_line'			=> $this->getLang('footer.line') ,
			'footer_live_chat'		=> $this->getLang('footer.live_chat') ,
			'footer_click_here'		=> $this->getLang('footer.click_here') ,
			'footer_faq'			=> $this->getLang('footer.faq') ,
			'footer_responsibility'	=> $this->getLang('footer.responsibility') ,
			'footer_terms_conds'	=> $this->getLang('footer.terms_conds') ,
			'footer_privacy_policy'	=> $this->getLang('footer.privacy_policy') ,
			'footer_security'		=> $this->getLang('footer.security') ,
			'header_1_home'			=> $this->getLang('header.1.home') ,
			'header_1_sports'		=> $this->getLang('header.1.sports') ,
			'header_1_live_casino'	=> $this->getLang('header.1.live_casino') ,
			'header_1_slots'		=> $this->getLang('header.1.slots') ,
			'header_1_fishing'		=> $this->getLang('header.1.fishing') ,
			'header_1_keno_lotto'	=> $this->getLang('header.1.keno_lotto') ,
			'header_1_promotions'	=> $this->getLang('header.1.promotions') ,
			'header_1_vip'			=> $this->getLang('header.1.vip') ,
			'header_1_blog' 		=> $this->getLang('header.1.blog') ,
			'header_1_esports' 		=> $this->getLang('header.1.esports') ,
			'header_1_card_games' 	=> $this->getLang('header.1.card_games') ,
			'header_1_lottery' 		=> $this->getLang('header.1.lottery') ,
			'header_1_cock_fight' 	=> $this->getLang('header.1.cock_fight') ,
			'Virtual Sports' 		=> $this->getLang('Virtual Sports') ,
			'header_virtual_sports' => $this->getLang('header.1.virtual_sports') ,
			'sidebar_login'				=> $this->getLang('sidebar_login'	) ,
			'sidebar_register'			=> $this->getLang('sidebar_register'	) ,
			'sidebar_the_product'		=> $this->getLang('sidebar_the_product'	) ,
			'sidebar_home'				=> $this->getLang('sidebar_home'	) ,
			'sidebar_slots'				=> $this->getLang('sidebar_slots'	) ,
			'sidebar_live_casino'		=> $this->getLang('sidebar_live_casino'		) ,
			'sidebar_sports_betting'	=> $this->getLang('sidebar_sports_betting'	) ,
			'sidebar_shoot_fish'		=> $this->getLang('sidebar_shoot_fish'		) ,
			'sidebar_benefits'			=> $this->getLang('sidebar_benefits') ,
			'sidebar_all_promotions'	=> $this->getLang('sidebar_all_promotions'	) ,
			'sidebar_vip'				=> $this->getLang('sidebar_vip'		) ,
			'sidebar_alliance'			=> $this->getLang('sidebar_alliance') ,
			'sidebar_set_up'			=> $this->getLang('sidebar_set_up'	) ,
			'sidebar_account_mgmt'		=> $this->getLang('sidebar_account_mgmt'	) ,
			'sidebar_resp_gaming'		=> $this->getLang('sidebar_resp_gaming'	) ,
			'sidebar_help'				=> $this->getLang('sidebar_help'	) ,
			'sidebar_contact_us'		=> $this->getLang('sidebar_contact_us'	) ,
			'sidebar_terms_and_conds'	=> $this->getLang('sidebar_terms_and_conds'	) ,
			'sidebar_faq'				=> $this->getLang('sidebar_faq'		) ,
			'sidebar_about_us'			=> $this->getLang('sidebar_about_us') ,
			'24_hour_service'			=> $this->getLang('24_hour_service') ,
			'footer_livechat'			=> $this->getLang('livechat') ,
			'footer_beginners_guide'	=> $this->getLang('footer_beginners_guide') ,
			'footer_easy_to_understand'	=> $this->getLang('footer_easy_to_understand') ,
			'footer_learn_more'			=> $this->getLang('footer_learn_more') ,
			'footer_property'			=> $this->getLang('footer_property') ,
			'footer_fast_game_always'	=> $this->getLang('footer_fast_game_always') ,
			'footer_login_to_member_dashboard'	=>  $this->getLang('footer_login_to_member_dashboard') ,
			'footer_gambling_license'	=> $this->getLang('footer_gambling_license') ,
			'footer_legal_license_from'	=> $this->getLang('footer_legal_license_from') ,
			'footer_check'				=> $this->getLang('footer_check') ,
			'footer_about_us'			=> $this->getLang('footer_about_us') ,
			'footer_chelsea'			=> $this->getLang('footer_chelsea'),
			'footer_official_partner'	=> $this->getLang('footer_official_partner'),
			'all_rights_reserved'		=> $this->getLang('All rights reserved'),
            'home' => $this->getLang('Home'),
            'in_house' => $this->getLang('In-House'),
            'slots' => $this->getLang('Slots'),
            'fishing' => $this->getLang('Fishing'),
            'live_casino' => $this->getLang('Live Casino'),
            'sports' => $this->getLang('Sports'),
            'reference' => $this->getLang('Reference'),
            'agent' => $this->getLang('Agent'),
            'vip_club' => $this->getLang('VIP Club'),
            'lang_vip_club_button' => $this->getLang('lang.vipclubbutton'),
            'app_download' => $this->getLang('App Download'),
            'portuguese' => $this->getLang('Portuguese'),
            'english' => $this->getLang('English'),
            'enter' => $this->getLang('Enter'),
            'register' => $this->getLang('Register'),
            'promotions' => $this->getLang('Promotions'),
            'deposit' => $this->getLang('Deposit'),
            'withdraw' => $this->getLang('Withdraw'),
            'logout' => $this->getLang('Logout'),
            'providers' => $this->getLang('Providers'),
            'games' => $this->getLang('Games'),
            'in_house_games' => $this->getLang('In-House Games'),
            'help' => $this->getLang('Help'),
            'privacy_polices' => $this->getLang('Privacy Polices'),
            'responsible_gaming' => $this->getLang('Responsible Gaming'),
            'terms_and_conditions' => $this->getLang('terms_and_conditions'),
            'previous_rolls' => $this->getLang('Previous Rolls'),
            'footer_info1' => $this->getLang('footer.info.1'),
            'footer_info2' => $this->getLang('footer.info.2'),
            'notice_withdrawal_bank1' => $this->getLang('notice.withrawal_bank.1'),
            'contact_us' => $this->getLang('Contact Us'),
            'kgvipen_disclamimer_p1' => $this->getLang('kgvipen_disclamimer_p1'),
            'kgvipen_disclamimer_p2' => $this->getLang('kgvipen_disclamimer_p2'),
            'kgvipen_disclamimer_p3' => $this->getLang('kgvipen_disclamimer_p3'),
            'kgvipen_disclamimer_p4' => $this->getLang('kgvipen_disclamimer_p4'),
            'kgvipen_disclamimer_p5' => $this->getLang('kgvipen_disclamimer_p5'),
            'kgvipen_disclamimer_p6' => $this->getLang('kgvipen_disclamimer_p6'),
            'footer_affiliates' => $this->getLang('footer_affiliates'),
            'footer_privacy_policy' => $this->getLang('footer_privacy_policy'),
            'footer_help_and_contacts' => $this->getLang('footer_help_and_contacts'),
            'alphabook_disclaimer' => $this->getLang('alphabook_disclaimer'),
            'footer_cookie_policy' => $this->getLang('footer_cookie_policy'),
            'footer_kyc_policy' => $this->getLang('footer_kyc_policy'),
            'footer_follow_us' => $this->getLang('footer_follow_us'),
            'name' => $this->getLang('Name'),
            'time' => $this->getLang('Time'),
            'player' => $this->getLang('Player'),
            'bet_amount' => $this->getLang('Bet Amount'),
            'multiplier' => $this->getLang('Multiplier'),
            'payout' => $this->getLang('Payout'),
            'header_join' => $this->getLang('header.join'),
		];
    }

    public function generate_date_time_format(){
		return [
			'datetime_1' => $this->utils->getCurrentDatetimeWithSeconds('Y/m/d H:i:s'),
			'datetime_2' => $this->utils->getCurrentDatetimeWithSeconds('Y-m-d H:i:s'),
			'date_1' => $this->utils->getCurrentDatetimeWithSeconds('Y/m/d'),
			'date_2' => $this->utils->getCurrentDatetimeWithSeconds('Y-m-d'),
			'time_1' => $this->utils->getCurrentDatetimeWithSeconds('H:i:s'),
		];
    }

    public function initTemplateVariables($for_html){

		$www_url=$for_html ? '' : $this->getSystemUrl('www');
		$player_url=$for_html ? '' : $this->getSystemUrl('player');
        $mobile_url=$for_html ? '' : $this->getSystemUrl('m');
		// $www_url_with_slash=$for_html ? '/' : $this->getSystemUrl('www').'/';
		$logo=$this->getPlayerCenterLogoURL();
		$textLang=$this->generate_lang_text_array();
		$imgProvider='/'. $this->getPlayerCenterTemplate().'/img/gameProviders';
		$dateFormat=$this->generate_date_time_format();

		$data=[
			'url' => $www_url,
			'player_url' => $player_url,
			'mobile_url' => $mobile_url,
			'url_with_slash' => $www_url.'/',
			'logo' => $logo,
			'logo_path' => $this->getPlayerCenterLogoPath(),
			'textLang' => $textLang,
			'imgProvider' => $imgProvider,
			'dateFormat' => $dateFormat,
			'currentLang' => $this->CI->language_function->getCurrentLanguage(),
			'currentLangForPromo' => $this->CI->language_function->getCurrentLangForPromo(),
		];

		return $data;
    }

    public function renderHeaderTemplate($headerTemplateFile, $for_html=false){
    	$header=file_get_contents($headerTemplateFile);

    	if(!empty($header)){
			$data=$this->initTemplateVariables($for_html);
    		$m = new Mustache_Engine;
    		$header=preg_replace('/<script.+id="tmpl">/', "", $header, 1);
			$header=$m->render($header, $data);
    	}

    	return $header;
    }

    public function renderFooterTemplate($footerTemplateFile, $for_html=false){
    	$footer=file_get_contents($footerTemplateFile);

    	if(!empty($footer)){
			$data=$this->initTemplateVariables($for_html);
    		$m = new Mustache_Engine;
    		$footer=preg_replace('/<script.+id="tmpl_footer">/', "", $footer, 1);
			$footer=$m->render($footer, $data);
    	}

    	return $footer;
    }


    public function btn_case_str_to_html($btn_case_str, $wrapper_class = null, $btn_type = null, $btn_class= null, $btn_lang = null, $extra_attr = ''){
        $html = '';
        $btn_link = '';
        // defaults as for REGISTERING_BTN
        if(is_null($wrapper_class)){
            $wrapper_class = 'col-md-12 col-lg-12';
        }
        if(is_null($btn_class)){
            $btn_class = 'btn btn-primary';
        }
        switch($btn_case_str){
            case 'HOME_BTN':
                if(is_null($btn_type)){
                    $btn_type = 'button';
                }
                $btn_link = $this->utils->is_mobile()? $this->utils->getSystemUrl('m'): $this->utils->getSystemUrl('www');
                if(is_null($btn_lang)){
                    $btn_lang = lang('lang.registration_mod_lang_in_home');
                }
                break;
            case 'SIGN_IN_BTN':
                if(is_null($btn_type)){
                    $btn_type = 'button';
                }
                $btn_link = site_url('iframe/auth/login');
                if(is_null($btn_lang)){
                    $btn_lang = lang('lang.registration_mod_lang_in_sign_in');
                }
                break;
            case 'REGISTERING_BTN':
                if(is_null($btn_type)){
                    $btn_type = 'submit';
                }
                $btn_link = site_url('player_center/iframe_register');
                if(is_null($btn_lang)){
                    $btn_lang = lang('lang.registration_mod_lang_in_register_now');
                }
                break;
            default:
                if(empty($btn_lang)){
                    $btn_type = '';
                    $btn_lang = '';
                }else{
                    // replace to the return of lang().
                    $btn_lang = lang($btn_lang);
                }

                break;
        }

        /// 6 params: wrapper_class, btn_type, btn_class, btn_link, btn_lang and extra_attr
        // col-md-12 col-lg-12
        // submit
        // btn btn-primary
        // site_url('iframe/auth/login')
        // lang('Register Now');
        // $extra_attr
        $html_formater = '';
        $html_formater .= '<div class="col %s">';
        $html_formater .= '<button type="%s" class="%s" data-link="%s" data-btn_case="'. $btn_case_str. '" '. $extra_attr. ' >%s</button>';
        $html_formater .= '</div>';

        if( ! empty($btn_lang) ){
            $html = sprintf($html_formater, $wrapper_class, $btn_type, $btn_class, $btn_link, $btn_lang);
        }
        return $html;
    }

	public function getPlayerCenterLogoPath(){
		$cacheKey = "getPlayerCenterLogoPath:".$this->utils->getPlayerCenterTemplate() . '|' . $this->getPlayerCenterLogo() . '|' . $this->getCmsVersion();
		$playercenter_logo = $this->getTextFromCache($cacheKey);

		if($playercenter_logo === false) {
			$fileExt = array("jpg","jpeg","png","gif", "PNG");
			foreach ($fileExt as $key => $value) {
				$dir = $this->getLogoTemplatePath(). $this->getPlayerCenterLogo() . '.' . $value;

				if (file_exists($dir)) {
					$playercenter_logo = $this->utils->getUploadThemeUri().'/' . $this->utils->getPlayerCenterTemplate() . '/img_logo/' . $this->getPlayerCenterLogo() . '.' . $value .'?v=' . $this->getCmsVersion();
					$this->saveTextToCache($cacheKey, $playercenter_logo, self::LOGO_CACHE_TTL);
					return $playercenter_logo;
				} else {
					$playercenter_logo = '/'. $this->getPlayerCenterTemplate() . '/img/logo.png?v=' . $this->getCmsVersion();
				}
			}

			$this->saveTextToCache($cacheKey, $playercenter_logo, self::LOGO_CACHE_TTL);
		}

		return $playercenter_logo;
	}

    public function getLastViewedNewPlayerDateTime() {
		$this->CI->load->model(['operatorglobalsettings']);
		$last_viewed_new_player_datetime = $this->CI->operatorglobalsettings->getSettingJson('last_viewed_new_player_datetime');

		if(empty($last_viewed_new_player_datetime)) {
			$this->CI->operatorglobalsettings->syncSettingJson("last_viewed_new_player_datetime", $this->getNowForMysql(), 'value');
			return $this->CI->operatorglobalsettings->getSettingJson('last_viewed_new_player_datetime');
		}

		return $last_viewed_new_player_datetime;
	}

	public function setLastViewedNewPlayerDateTime($last_viewed_new_player_datetime = '') {
		if (empty($last_viewed_new_player_datetime)) {
			$last_viewed_new_player_datetime = $this->getNowForMysql();
		}

		$this->CI->operatorglobalsettings->syncSettingJson("last_viewed_new_player_datetime", $last_viewed_new_player_datetime, 'value');
	}

    public function stopLoginGame($playerId){
        $status_suspend = Player_model::SUSPENDED_STATUS;
        $status_selfExclusion = Player_model::SELFEXCLUSION_STATUS;
        $block =  $this->getPlayerStatus($playerId);

        switch($block){
            case $status_suspend:
            case $status_selfExclusion:
                return false;
            break;
            default:
             	return true;
            break;
        }
    }

    public function getDefaultCurrencyDecPoint() {
        return $this->currency_dec_point;
    }

    /**
     * Get Player Status, active,blocked,suspended,self-Exclusion
     * Flow
     * @param playerId  int To select someone player.
	 * @param formatter int Applied colors(html) and wordings while return?
	 * @param block int|null  if null for resturn form getBlockStatus().
	 * @param is_export int|null Is for export?
	 * @param lang array|null The wordings mapping to status.
     * @return int|string The player status maybe applied colors(html) and wordings by param."$formatter".
     */
    public function getPlayerStatus($playerId,$formatter=0,$block=null,$is_export=null, $lang = null){

		/// OGP-15172
		if( empty($lang['lang.active']) ){
			$lang['lang.active'] = lang('lang.active');
		}
		if( empty($lang['Blocked']) ){
			$lang['Blocked'] = lang('Blocked');
		}
		if( empty($lang['Suspended']) ){
			$lang['Suspended'] = lang('Suspended');
		}
		if( empty($lang['Self Exclusion']) ){
			$lang['Self Exclusion'] = lang('Self Exclusion');
		}
		if( empty($lang['Failed Login Attempt']) ){
			$lang['Failed Login Attempt'] = lang('Failed Login Attempt');
		}

        //define status
        if($formatter){
        	if($is_export){
        		$status_active = $lang['lang.active'];
            	$status_blocked = $lang['Blocked'];
            	$status_suspend = $lang['Suspended'];
           		$status_selfExclusion = $lang['Self Exclusion'];
           		$status_failed_login_attempt = $lang['Failed Login Attempt'];
        	}else{
	            $status_active = '<i><p style="color:#5cb85c">' . $lang['lang.active'] . '</i>';
	            $status_blocked = '<i><p style="color:#ce4844">' . $lang['Blocked'] . '</p></i>';
	            $status_suspend = '<i> <p style="color:#EC971F">' . $lang['Suspended'] . '</p></i>';
	            $status_selfExclusion ='<i><p style="color:#AA6708">' . $lang['Self Exclusion'] . '</p></i>';
	            $status_failed_login_attempt = '<i> <p style="color:#EC971F">' . $lang['Failed Login Attempt'] . '</p></i>';
	        }
        }else {
            $status_active=0;
            $status_blocked=Player_model::BLOCK_STATUS;
            $status_suspend=Player_model::SUSPENDED_STATUS;
            $status_selfExclusion=Player_model::SELFEXCLUSION_STATUS;
            $status_failed_login_attempt=Player_model::STATUS_BLOCKED_FAILED_LOGIN_ATTEMPT;
        }
        $rfStatus =0;

        if(intval($playerId)==0){
            return $rfStatus;
        }

        $this->CI->load->model(array('player','responsible_gaming'));
        $selfExclusion = $this->CI->responsible_gaming->chkSelfExclusion($playerId);
        $coolOff = $this->CI->responsible_gaming->chkCoolOff($playerId);

        if($selfExclusion || $coolOff){
            return $status_selfExclusion;
        }else{
            if(is_null($block)){
                $block =  $this->CI->player->getBlockStatus($playerId);
            }

            switch ($block){
                case Player_model::BLOCK_STATUS:
                    return  $status_blocked;
                    break;
                case Player_model::SUSPENDED_STATUS:
                    return  $status_suspend;
                    break;
                case Player_model::STATUS_BLOCKED_FAILED_LOGIN_ATTEMPT:
                    return  $status_failed_login_attempt;
                    break;
                default:
                    return  $status_active;
                    break;
            }
        }
    }

    public function blockLoginGame($playerId){
        $status = $this->getPlayerStatus($playerId);
        if($status>0){
            return true;
        }else{
            return false;
        }
    }

	public function simpleProcessFloat($val){
		return floatval(str_replace(',', '', $val));
	}

	public function simpleProcessInt($val){
		return intval(str_replace(',', '', $val));
	}

	public function getProvinceCityFromAddress($address){
		$province='';
		$city='';
		if(!empty($address)){
			$arr=explode('省', trim($address));
			if(count($arr)>=2){
				$province=trim($arr[0]);
				//try city
				$cityArr=explode('市', $arr[1]);
				if(count($cityArr)>=1){
					$city=trim($cityArr[0]);
				}
			}
		}

		return [$province, $city];
	}

	//===raw db conn=====================================================
	private $db_connection=null;
	private $read_db_connection=null;

	public function getConn(){

		if(empty($this->db_connection)){

			$conn=mysqli_connect($this->getConfig('db.default.hostname'),
				$this->getConfig('db.default.username'),
				$this->getConfig('db.default.password'),
				$this->getConfig('db.default.database'),
				$this->getConfig('db.default.port'));
			$charset=$this->getConfig('db.default.char_set');
			if($conn){
				mysqli_set_charset($conn, $charset);
				$this->db_connection=$conn;
			}else{
				$this->error_log('connect mysql failed', mysqli_connect_errno(), mysqli_connect_error());
			}
		}

		return $this->db_connection;

	}

	public function getReadConn(){
		if(empty($this->read_db_connection)){
			$conn=mysqli_connect($this->getConfig('db.readonly.hostname'),
				$this->getConfig('db.readonly.username'),
				$this->getConfig('db.readonly.password'),
				$this->getConfig('db.readonly.database'),
				$this->getConfig('db.readonly.port'));
			$charset=$this->getConfig('db.readonly.char_set');
			if($conn){
				mysqli_set_charset($conn, $charset);
				$this->read_db_connection=$conn;
				$this->debug_log('set read db connection', $this->read_db_connection);
			}else{
				$this->error_log('connect readonly mysql failed', mysqli_connect_errno(), mysqli_connect_error());
			}
		}
		return $this->read_db_connection;
	}

	public function closeAllConn(){
		$success=true;

		// $this->debug_log('try to close db connection', $this->db_connection);
		if(!empty($this->db_connection)){
			$rlt=mysqli_close($this->db_connection);
			$this->debug_log('close db connection', $rlt);
			$this->db_connection=null;
		}
		// $this->debug_log('try to close read db connection', $this->read_db_connection);
		if(!empty($this->read_db_connection)){
			$rlt=mysqli_close($this->read_db_connection);
			$this->debug_log('close read db connection', $rlt);
			$this->read_db_connection=null;
		}

		if(!empty($this->_redis)){
        	$rlt=false;
			try{
				// $this->debug_log('call close redis');
				$rlt=$this->_redis->close();
				$this->_redis=null;
			}catch(Exception $e){
				$this->error_log('close redis failed', $e);
			}
			$this->debug_log('try close redis connection', $rlt);
		}
        if (!empty($this->redis_of_id_generator)) {
        	$rlt=false;
			try{
				// $this->debug_log('call close redis');
				$rlt=$this->redis_of_id_generator->close();
				$this->redis_of_id_generator=null;
			}catch(Exception $e){
				$this->error_log('close id generator redis failed', $e);
			}
			$this->debug_log('try close id generator redis connection', $rlt);
		}
        if (!empty($this->redis_lock_server)) {
        	$rlt=false;
			try{
				// $this->debug_log('call close redis');
				$rlt=$this->redis_lock_server->close();
				$this->redis_lock_server=null;
			}catch(Exception $e){
				$this->error_log('close global redis failed', $e);
			}
			$this->debug_log('try close global redis connection', $rlt);
		}

		//use same redis
		// if(isset($this->CI->session)){
		// 	$rlt=$this->CI->session->tryCloseRedis();
		// 	$this->debug_log('call close session redis', $rlt);
		// }

		return $success;
	}

    public function isPlayerBlockedInTransferCondition($player_id, $transfer_from, $transfer_to){
        $this->CI->load->model('transfer_condition');
        $transfer_condittion = $this->CI->transfer_condition->isPlayerTransferConditionExist($player_id, $transfer_from, $transfer_to);
        return $transfer_condittion;
    }

	/*
	 * Check if player is blocked in game
	 *
	 * USED IN:
	 * OGP-3271 (start) - sub wallet function to prevent transferring of funds
	 *
	 * params $playerId int
	 * params $gamePlatformId int
	 */
	public function isPlayerBlockedInGame($playerId,$gamePlatformId){
		$this->CI->load->model('game_provider_auth');
		$blockInGameFlag = $this->CI->game_provider_auth->isBlockedUsernameInDB($playerId,$gamePlatformId);
		return $blockInGameFlag;
	}

	/*
	 * Check if country is blocked in country rules
	 *
	 *
	 * params $playerId int
	 * params $gamePlatformId int
	 */
	public function isCountryBlocked($countryName){
		$this->CI->load->model('country_rules');
		$blockCountry = $this->CI->country_rules->isCountryBlocked($countryName);
		return $blockCountry;
	}

	public function getCountryList(){
		$countryList = array();
		$countryList = unserialize(COUNTRY_LIST);
		if(!empty($countryList)){
			if($this->utils->isEnabledFeature('enable_remove_country_in_list_if_blocked_country_rules')){
				foreach ($countryList as $key => $value) {
					$this->debug_log('getCountryList', $key);
					if($this->isCountryBlocked($key)){
						$this->debug_log('getCountryList blocked', $key);
						unset($countryList[$key]);
					}
				}
			}
		}

		return $countryList;
	}

	public function getCountryIso2List(){
		$countryList = array();
		$countryList = unserialize(COUNTRY_ISO2);
		if(!empty($countryList)){
			if($this->utils->isEnabledFeature('enable_remove_country_in_list_if_blocked_country_rules')){
				foreach ($countryList as $key => $value) {
					$this->debug_log('getCountryIso2List', $key);
					if($this->isCountryBlocked($key)){
						$this->debug_log('getCountryIso2List blocked', $key);
						unset($countryList[$key]);
					}

					$unsetCountryList = $this->getConfig('unset_iso2_country_list');
					if (!empty($unsetCountryList) && in_array($key, $unsetCountryList)) {
						$this->debug_log('getCountryIso2List unset', $key);
						unset($countryList[$key]);
					}
				}
			}
		}

		return $countryList;
	}

	public function getCommonCountryList(){
		$commonCountryListConfig = $this->getConfig('common_country_list');
		if(!empty($commonCountryListConfig)){
			$commonCountryList = $commonCountryListConfig;
		} else {
			$commonCountryList = unserialize(COMMON_COUNTRY_LIST);
		}

		if(!empty($commonCountryList)){
			if($this->utils->isEnabledFeature('enable_remove_country_in_list_if_blocked_country_rules')){
				array_walk($commonCountryList, function(&$item,&$key) use (&$commonCountryList){
					if($this->isCountryBlocked($key)){
						unset($commonCountryList[$key]);
					}
				});
			}
		}

		return $commonCountryList;
	}

	public function getLocalLockServer() {
		$servers = $this->getConfig('local_lock_servers');
		$this->CI->load->library('third_party/local_lock_server', $servers);

		return $this->CI->local_lock_server;
	}

	public function localLockResourceBy($anyId, $action, &$lockedKey) {
		$resourceKey = $this->createLockKey(array($anyId, $action));

		return $this->localLockResource($resourceKey, $lockedKey);
	}

	public function localLockResource($resourceKey, &$lockedKey) {
		$this->verbose_log('try lock resource', $resourceKey, $lockedKey);
		//ms
		$timeout = $this->getConfig('app_lock_timeout') * 1000;

		$lock_server = $this->getLocalLockServer();
		$lockedKey = $lock_server->lock($resourceKey, $timeout);
		$locked = $lockedKey != false;

		$this->debug_log('resourceKey', $resourceKey, 'locked', $locked, ($locked ? 'success' : 'failed'));
		// }
		return $locked;
	}

	public function localReleaseResourceBy($anyId, $action, &$lockedKey) {
		$resourceKey = $this->createLockKey(array($anyId, $action));

		return $this->localReleaseResource($resourceKey, $lockedKey);
	}

	public function localReleaseResource($resourceKey, $lockedKey) {
		$this->verbose_log('try release resource', $resourceKey, $lockedKey);

		$lock_server = $this->getLocalLockServer();

		$released = $lock_server->unlock($lockedKey);
		return $released;
	}

	public function show_message($type, $title, $message, $redirect_url = NULL){
        redirect('/player_center2/show_message?' . http_build_query([
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'redirect_url' => $redirect_url
        ]));

        return;
	}

    public function flash_message($type, $message) {
        switch ($type) {
            case FLASH_MESSAGE_TYPE_SUCCESS:
                $show_message = array(
                    'result' => FLASH_MESSAGE_TYPE_SUCCESS,
                    'message' => $message,
                );
                $this->CI->session->set_userdata($show_message);
                break;

            case FLASH_MESSAGE_TYPE_DANGER:
                $show_message = array(
                    'result' => FLASH_MESSAGE_TYPE_DANGER,
                    'message' => $message,
                );
                $this->CI->session->set_userdata($show_message);
                break;

            case FLASH_MESSAGE_TYPE_WARNING:
                $show_message = array(
                    'result' => FLASH_MESSAGE_TYPE_WARNING,
                    'message' => $message,
                );
                $this->CI->session->set_userdata($show_message);
                break;
        }
    }

    public function resetAppPrefix(){
    	$this->_app_prefix=try_get_prefix();
    }

	public function getAppPrefix(){
		if($this->getConfig('cache_app_prefix_on_utils')){
			return $this->_app_prefix;
		}else{
			return try_get_prefix();
		}
	}

	public function addAppPrefixForKey($key) {
		if($this->getConfig('cache_app_prefix_on_utils')){
			return "{$this->_app_prefix}-{$key}";
		}else{
			return try_get_prefix().'-'.$key;
		}
	}

	public function isAvailableT1LotteryBO(){

		$available=false;

		$api=$this->utils->loadExternalSystemLibObject(T1LOTTERY_API);
		if(!empty($api)){
			$boInfo=$api->getBackOfficeInfo();

			if(!empty($boInfo['backoffice_url']) && !empty($boInfo['backoffice_username']) &&
	        		!empty($boInfo['backoffice_password'])){
				$available=true;
			}
		}

		return $available;
	}

	public function isAllowedAutoTransferOnFeature(){
		return $this->isEnabledFeature('always_auto_transfer_if_only_one_game') || $this->isEnabledFeature('enabled_single_wallet_switch');
	}

    public function countNewGames(){
        $this->CI->load->model("game_description_model");
        $count = $this->CI->game_description_model->getNewGamesCount();
        return $count;
    }

	public function isFromHost($host){
		return  (strpos($_SERVER['HTTP_HOST'], $host ) === 0);
	}

	public function isFromAdminHost(){
		$prefix_admin_domains=$this->getConfig('prefix_admin_domains');
		if(!empty($prefix_admin_domains)){
			foreach ($prefix_admin_domains as $domain) {
				if($this->isFromHost($domain)){
					return true;
				}
			}
		}

		return false;
	}

	public function idCardType(){
		$type_of_id_card = $this->getConfig('type_of_id_card');
		$response = array();
		if(!empty($type_of_id_card)){
			foreach ($type_of_id_card as $key => $value) {
				$response[] = array(
					"code_type" => $key,
					"type_name" => lang($value)
				);
			}
		}

		return $response;
	}

	public function id_card_type_to_text($type) {
		$id_card_types = $this->getConfig('type_of_id_card');
		if (!is_array($id_card_types) || !isset($id_card_types[$type])) {
			return $type;
		}

		return lang($id_card_types[$type]);
	}

	public function getPlayerActiveProfilePicture($player_id){
		$this->CI->load->model('player_attached_proof_file_model');
		$response = $this->CI->player_attached_proof_file_model->getAttachementRecordInfo($player_id,null,'profile');
		if(!empty($response)){
			foreach ($response as $key => $value) {
				if(isset($value['visible_to_player'])){
					if($value['visible_to_player']){
						if(isset($value['file_name'])) {
							return $this->getSystemUrl("player", '/'.str_replace('/mobile', '',$this->getProfilePictureUploadPath()) . '/' . $value['file_name'], false);
						}
					}
				}
			}
		}

		return null;
	}

	public function hasUploadedProfilePicture() {
		$profile_filename = $this->getActiveProfilePicture();
		$file_loc = $this->getProfilePictureFullPath() . '/'. $profile_filename;
		if($this->CI->agent->is_mobile()){
			$file_loc = str_replace('/mobile', '', $file_loc);
		}

		if (!isset($profile_filename) || empty($profile_filename) || !file_exists($file_loc)) { return false; }

		return true;
	}

	public function setProfilePicture() {
		if ($this->hasUploadedProfilePicture()) {
			$sdvsd = base_url().str_replace('/mobile', '',$this->getProfilePictureUploadPath()) . '/' . $this->getActiveProfilePicture();
			return $sdvsd;
		} else {
			if($this->CI->agent->is_mobile()){
				return site_url($this->utils->getPlayerCenterTemplate().'/images/user_icon.svg');
			} else {
				return base_url().$this->utils->getPlayerCenterTemplate() . '/img/default-profile.png';

			}
		}
	}

	public function getProfilePictureFullPath() {
		//NEW Function jhunel.php 1-9-2018
		$path=$this->utils->getUploadPath() . '/'. $this->CI->config->item("player_upload_folder");
		$this->utils->addSuffixOnMDB($path);
		return $path;
		/*old
		return $this->utils->getUploadPath() . '/player/profile_picture/'. $this->utils->getPlayerCenterTemplate();*/
	}

	public function getProfilePictureUploadPath() {
		//new function, jhunel.php 1-9-2018
		$path='upload/' . $this->CI->config->item("player_upload_folder");
		$this->utils->addSuffixOnMDB($path);
		return $path;
		/*OLD function
		return 'upload/player/profile_picture/' . $this->utils->getPlayerCenterTemplate();
		*/
	}

	/**
	 * @author Hayme.php 2017-05-09
	 * Overview : Get player current profile picture setup on DB
	 * @param 	String      playerId
	 * @return	void
	 */
	public function getActiveProfilePicture() {
		$player_id = $this->CI->authentication->getPlayerId();
		// Check player proof_filename
		$this->CI->load->model(array('player_model','player_attached_proof_file_model'));
		//NEW Function jhunel.php.ph 1-9-2018
		$response = $this->CI->player_attached_proof_file_model->getAttachementRecordInfo($player_id,null,player_attached_proof_file_model::PROFILE_PICTURE,null,false,null,false);
		if(!empty($response)){
			foreach ($response as $key => $value) {
				if(isset($value['visible_to_player'])){
					if($value['visible_to_player']){
						if(isset($value['file_name'])) {
							return $value['file_name'];
						}
					}
				}
			}
		}

		return false;

		/*OLD Function
		$result = json_decode($this->player_model->getPlayerInfoDetailById($player_id)['proof_filename'], true);

		if (!$result) {
			return false;
		}

		if (empty($result['profile_image'])) {
			$this->set_default_value_proof_filename($player_id);
			return false;
		}

		// Get active profile picture file name
		foreach ($result['profile_image'] as $key => $values) {
			if ($result['profile_image'][$key]['active']) {
				return $key;
			}
		}*/
	}

	// public function getProfilePictureUploadPath() {
	// 	return 'upload/' .  $this->CI->config->item("player_upload_folder");
	// }

	public function getPlayerCenterMobileLogin($check_cookie = true) {

		if ($check_cookie && ($preview_mobile_login = $this->CI->input->cookie('preview_mobile_login'))) {
			return $preview_mobile_login;
		}

		if (empty($this->player_center_mobile_login)) {
			$this->CI->load->model(['operatorglobalsettings']);
			//load from operator settings
			$this->player_center_mobile_login = $this->CI->operatorglobalsettings->getSettingJson('player_center_mobile_login', 'value', 'recommended');
		}

		return $this->player_center_mobile_login;
	}

	public function getPlayerCenterMobileLoginFile(){

		if ($this->is_mobile()) {
			$path = '/auth/login';
			$view = $this->getPlayerCenterTemplate() . $path;
		} else {
			$custom_login_path_pc = $this->utils->getConfig('custom_login_path_pc') ? $this->utils->getConfig('custom_login_path_pc') : '';
			$path = '/auth' . $custom_login_path_pc . '/login';
			$view = $this->getPlayerCenterTemplate(false) . $path;
		}

		if ($this->is_mobile() && $this->isEnabledFeature('enable_dynamic_mobile_login')) {
			$getPlayerCenterMobileLogin = $this->getPlayerCenterMobileLogin();

			if (!empty($getPlayerCenterMobileLogin)) {
				$loginPathDir = rtrim(dirname(__FILE__).'/../../../player/application/views/'.$this->getPlayerCenterTemplate().'/auth/', '/');
				if(file_exists($loginPathDir.'/login_'.$getPlayerCenterMobileLogin.'.php')){
					$view = $this->getPlayerCenterTemplate() .'/auth/login_'.$getPlayerCenterMobileLogin;
				}
			}
		}

		return $view;
	}

	public function isTestAccountByPlayerTags($player_id){
		$this->CI->load->model('player_model');
		return $this->CI->player_model->isTestAccountByPlayerTags($player_id);
	}

	public function makeDateHourList($from, $to){
		$list=[];
		$start=new DateTime($from);
		$end=new DateTime($to);

		$step='+1 hour';

		$startDate = clone $start;

		while ($this->utils->formatDateHourForMysql($startDate) <= $this->utils->formatDateHourForMysql($end)) {
			$list[]=$this->utils->formatDateHourForMysql($startDate);

			$endDate = $this->getNextTime($startDate, $step);
			$startDate = $endDate;
		}

		return $list;
	}

	public function isUserListedInLotteryExtra() {
		if (!$this->isAnyEnabledApi([ T1LOTTERY_API ])) {
			return false;
		}
		else {
			$this->CI->load->library(['authentication', 'lottery_bo_roles']);
			$username = $this->CI->authentication->getUsername();
			$res = $this->CI->lottery_bo_roles->roles_user_exists($username);

			return $res;
		}
	}

	public function getPlayerMinifyCssPath($data) {
		return $this->getMinifyFilePath($data, 'player', 'css');
	}

	public function getPlayerMinifyJsPath($data) {
		return $this->getMinifyFilePath($data, 'player', 'js');
	}

	/**
	 *
	 * @param	string|array  $data file path
	 * @param	string        $project is 'admin' or 'player'
	 * @param	string        $type is 'css' or 'js'
	 * @return	string|array  tmp file path or origin path
	 */
	private function getMinifyFilePath($data, $project, $type) {
		if (!$this->isEnabledFeature('enable_player_center_minify_file')) {
			return $data;
		}

		if ($this->isFullUrl($data)){
		    return $data;
        }

		$minifySetting = $this->CI->config->item('minify_setting');

		if(!isset($minifySetting[$project]) || !isset($minifySetting[$project][$type])){
		    return $data;
        }

		$minifyList = $minifySetting[$project][$type];

		$is_string = false;
		if (is_string($data)) {
			$data = [$data];
			$is_string = true;
		}

		$minifyData = [];
		foreach ($data as $path) {
            $path = ltrim($path, '/');

			if(in_array($path, $minifyList)) {
				$UPLOAD_PATH = $this->getUploadPath();
				$PUBLIC_UPLOAD_PATH = ltrim($this->CI->config->item('PUBLIC_UPLOAD_PATH'), '/');
				$pathDirName = dirname($path);
				$ext   = pathinfo($path, PATHINFO_EXTENSION);
				$fileName = basename($path, ".$ext") . '.min.' . $ext;
				$tmpPath = '/tmp/' . $project . '/' . $type . '/' . $pathDirName . '/' .$fileName;
				$tmpUploadPath = $UPLOAD_PATH . $tmpPath;
				if(file_exists($tmpUploadPath)) {
					$path = $PUBLIC_UPLOAD_PATH . $tmpPath;
                }
            }

			$minifyData[] = '/' . $path;
		}

		if ($is_string) {
			return $minifyData[0];
		} else {
			return $minifyData;
		}
	}

	public function setReferralCodeCookie($referral_code){
		set_cookie('referralcode', $referral_code, 300);
	}

	public function getReferralCodeCookie(){
		return get_cookie('referralcode');
	}

	public function removeReferralCodeCookie(){
		delete_cookie('referralcode');
	}

	public function setBtagCookie($btag){
		set_cookie('btag', $btag, 3600);
	}

	public function getBtagCookie(){
		return get_cookie('btag');
	}

	public function removeBtagCookie(){
		delete_cookie('btag');
	}

	public function setupSecondReadDB($secondReadDB){
		$this->CI->secondReadDB=$secondReadDB;
	}

	public function fixTransferRequest($transferRequest){
		$success=true;
		$error_message=null;
		$status=null;
		$message=null;

		$this->CI->load->model(['wallet_model', 'external_system', 'transactions']);

		$external_system_id=$transferRequest['external_system_id'];

		$api=$this->loadExternalSystemLibObject($external_system_id);

		if(empty($api)){
			$success=false;
			$error_message=lang('Load API failed');
		}

		if(empty($transferRequest['external_transaction_id']) && !$api->isAllowedQueryTransactionWithoutId()){
			$success=false;
			$error_message=lang('This game does not support query transaction');
		}

		if($transferRequest['fix_flag']==Wallet_model::DB_TRUE){
			//already fixed
			$success=false;
			$error_message=lang('Cannot double fix');
		}

		if($success){
			// $api=$this->utils->loadExternalSystemLibObject($external_system_id);
			if(!empty($api)){
				$external_transaction_id=$transferRequest['external_transaction_id'];
				$transfer_from=$transferRequest['from_wallet_type_id'];
				$transfer_to=$transferRequest['to_wallet_type_id'];

				$playerId=$transferRequest['player_id'];
				$playerName=$this->CI->player_model->getUsernameById($playerId);
				$transfer_method=$transferRequest['from_wallet_type_id']==0 ? 'deposit' : 'withdrawal';
				$extra=['playerName'=>$playerName, 'playerId'=>$playerId, 'transfer_method'=>$transfer_method,
					'transfer_time'=>$transferRequest['created_at'], 'secure_id'=>$transferRequest['secure_id']];
				$rlt=$api->queryTransaction($transferRequest['external_transaction_id'], $extra);

				$this->debug_log($transferRequest['external_transaction_id'].' query transaction result', $rlt);
				if(!empty($rlt) && $rlt['success']){

					$amount=$transferRequest['amount'];
					$originTransferAmount=$transferRequest['amount'];
					// $player_id=$transferRequest['player_id'];

					$sys = $this->CI->external_system->getSystemById($external_system_id);
					$systemName = $sys->system_code;

					$reason='auto fix';
					//main to sub or sub to main
					$trans_type=null;
					// $note=null;
					// $note_reason = sprintf('<i>Reason:</i> %s <br>',$reason);
					if($transferRequest['from_wallet_type_id']==Wallet_model::MAIN_WALLET_ID){
						//main to sub
						$trans_type=Transactions::TRANSFER_TO_SUB_WALLET_FROM_MAIN_WALLET;
						// $note = 'transfer from main wallet to subwallet (' . $systemName . '), amount is ' . $amount . ' , playerId is ' . $player_id;
						// $note = (trim($reason) != '')  ? ($note_reason . sprintf('<i>Normal Note:</i> %s <br>',$note)) : $note;
					}

					if($transferRequest['to_wallet_type_id']==Wallet_model::MAIN_WALLET_ID){
						//sub to main
						$trans_type=Transactions::TRANSFER_FROM_SUB_WALLET_TO_MAIN_WALLET;
						// $note = 'transfer from subwallet (' . $systemName . ') to main wallet , amount is ' . $amount . ' , playerId is ' . $player_id;
						// $note = (trim($reason) != '')  ? ($note_reason . sprintf('<i>Normal Note:</i> %s <br>',$note)) : $note;
					}

					$status_from_success=$rlt['success'] ? Abstract_game_api::COMMON_TRANSACTION_STATUS_APPROVED : Abstract_game_api::COMMON_TRANSACTION_STATUS_PROCESSING;
					$status_of_api= !empty($rlt['status']) ? $rlt['status'] : $status_from_success;

					$new_trans_type=null;
					//check deposit
					if( ($trans_type==Transactions::DEPOSIT_TO_SUB_WALLET  || $trans_type==Transactions::MANUALLY_DEPOSIT_TO_SUB_WALLET)
						&& $status_of_api==Abstract_game_api::COMMON_TRANSACTION_STATUS_DECLINED
						// $transferRequest['status']  is readonly
						&& $transferRequest['status']==Wallet_model::STATUS_TRANSFER_SUCCESS
					){
						//deposit: status is success but real is declined
						//add balance to main
						$new_trans_type=Transactions::TRANSFER_FROM_SUB_WALLET_TO_MAIN_WALLET;

					}elseif( ($trans_type==Transactions::WITHDRAW_FROM_SUB_WALLET || $trans_type==Transactions::MANUALLY_WITHDRAW_FROM_SUB_WALLET)
						&& $status_of_api==Abstract_game_api::COMMON_TRANSACTION_STATUS_APPROVED
						// $transferRequest['status']  is readonly
						&& $transferRequest['status']==Wallet_model::STATUS_TRANSFER_FAILED){
						//withdraw: status is failed but real is approved

						//add balance to main
						$new_trans_type=Transactions::TRANSFER_FROM_SUB_WALLET_TO_MAIN_WALLET;
					}else{
						$message=lang('Sorry, Only Allow that deposit to subwallet and declined status(API Query) or withdraw from subwallet and successful status(API Query), so nothing change');
					}

					//deposit to sub and api status is declined, but transfer status is success, that's why add balance back to main wallet
					if($new_trans_type==Transactions::TRANSFER_FROM_SUB_WALLET_TO_MAIN_WALLET){

						$reason='auto fix';
						$success = $this->CI->wallet_model->lockAndTransForPlayerBalance($playerId, function ()
								use ($playerId, $playerName, $amount, $new_trans_type, $external_transaction_id, $status_of_api, $reason,
									$transfer_from, $transfer_to, $originTransferAmount, $transferRequest, &$message) {
							// $success = $this->wallet_model->incMainWallet($player_id, $amount);

							$transferRequestId=$transferRequest['id'];
							//check fix flag in lock and trans
							$transferFixFlag=$this->CI->wallet_model->getFixFlagFromTransferRequest($transferRequestId);

							if($transferFixFlag==Wallet_model::DB_FALSE){

								$external_transaction_id=$transferRequest['external_transaction_id'];
								$gamePlatformId=$transferRequest['external_system_id'];

								$note = 'make up transfer record on main wallet, amount:' . $amount . ', player:' . $playerName . '. reason:' . $reason;
								$really_fix_balance=true;
								$transferTransId = $this->CI->transactions->makeUpTransferTransaction($playerId, $new_trans_type, $gamePlatformId, $gamePlatformId,
									$amount, $note, $really_fix_balance, $this->getNowForMysql(), $external_transaction_id,null, $transferRequestId);

								$success=!empty($transferTransId);

								$this->debug_log('add to main wallet when auto fix transfer', 'playerId', $playerId, 'amount', $amount, 'transferTransId', $transferTransId, 'success', $success);

								if($success){
									$success=$this->CI->wallet_model->updateTransferQueryStatusAndFixFlag(
										$transferRequestId, $status_of_api, Wallet_model::DB_TRUE);
									if(!$success){
										$this->error_log('update transfer query status failed');
										$message=lang('Update transfer status failed');
									}
								}else{
									$message=lang('Auto Fix Failed');
								}

								return $success;

							}else{
								$this->debug_log('Cannot double fix:'.$transferFixFlag);
								$message=lang('Cannot double fix');
								return false;
							}
						});

						if($success){
							$message=lang('Auto add balance to player').' '.$playerName.', '.lang('Amount').': '.$amount;
						}
					// }else{
					// 	$message=lang('Sorry, Only Allow that deposit to subwallet and declined status(API Query), so nothing change');
					}

				}else{

					$success=false;
					$error_message=lang('Call API failed');
					if(!empty($rlt['error_message'])){
						$error_message=$error_message.': '.$rlt['error_message'];
					}

				}
			}else{
				$success=false;
				$error_message=lang('This game is not available');
			}
		}

		return [$success, $status, $error_message, $message];
	}

	public function queryAndUpdateTransferRequestStatus($transferRequest){
		$success=true;
		$error_message=null;
		$status=null;
		$status_message=null;

		$external_system_id=$transferRequest['external_system_id'];
		//call api
		$api=$this->loadExternalSystemLibObject($external_system_id);

		if(empty($transferRequest['external_transaction_id']) && !$api->isAllowedQueryTransactionWithoutId()){
			$success=false;
			$error_message=lang('This game does not support query transaction');
		}

		if($success){

			if(!empty($api)){
				$playerId=$transferRequest['player_id'];
				$playerName=$this->CI->player_model->getUsernameById($playerId);
				$transfer_method=$transferRequest['from_wallet_type_id']==0 ? 'deposit' : 'withdrawal';
				$extra = [
                    'playerName'=>$playerName,
                    'playerId'=>$playerId,
                    'transfer_method'=>$transfer_method,
                    'transfer_time'=>$transferRequest['created_at'],
                    'secure_id'=>$transferRequest['secure_id'],
                    'amount' => $transferRequest['amount'],
                    'transfer_updated_at' => $transferRequest['updated_at'],
                    'status' => $transferRequest['status'],
                ];
				$rlt=$api->queryTransaction($transferRequest['external_transaction_id'], $extra);

				$this->debug_log($transferRequest['external_transaction_id'].' query transaction result', $rlt);
				if(!empty($rlt) && !@$rlt['unimplemented'] && $rlt['success']){

					$status_from_success=$rlt['success'] ? Abstract_game_api::COMMON_TRANSACTION_STATUS_APPROVED : Abstract_game_api::COMMON_TRANSACTION_STATUS_PROCESSING;
					$status= !empty($rlt['status']) ? $rlt['status'] : $status_from_success;

					$this->debug_log('query transaction', $rlt);

					//will ignore unknown
					if(isset($rlt['status']) && $api->isValidTransferStatus($rlt['status'])
							&& $rlt['status']!=Abstract_game_api::COMMON_TRANSACTION_STATUS_UNKNOWN){
						//try update status
						$this->CI->wallet_model->updateTransferQueryStatus($transferRequest['id'], $rlt['status']);
					}

					$show_id=empty($transferRequest['external_transaction_id']) ? $transferRequest['secure_id'] : $transferRequest['external_transaction_id'];

					$status_message=sprintf(lang('query_status_status_message'),
						'<b>'.$show_id.'</b>',
						'<b>'.$api->translateTransferStatus($status).'</b>',
						'<b>'.$playerName.'</b>',
						'<b>'.$transferRequest['amount'].'</b>');
					//old
					// $status_message=sprintf(lang('Status of %s is %s, amount is %s belongs to %s'),
					// 	'<b>'.$show_id.'</b>',
					// 	'<b>'.$api->translateTransferStatus($status).'</b>', '<b>'.$transferRequest['amount'].'</b>',
					// 	'<b>'.$playerName.'</b>');

					// if(isset($rlt['status']) && $api->isValidTransferStatus($rlt['status'])
					// 		&& $rlt['status']!=Abstract_game_api::COMMON_TRANSACTION_STATUS_APPROVED){

					// 	$status_message=sprintf(lang('Status of %s is %s and belongs to %s'),
					// 	'<b>'.$show_id.'</b>',
					// 	'<b>'.$api->translateTransferStatus($status).'</b>',
					// 	'<b>'.$playerName.'</b>');
					// }
					// }

				}else{

					$success=false;
					$error_message=lang('Call API failed');
					if(!empty($rlt['error_message'])){
						$error_message=$error_message.': '.$rlt['error_message'];
					}

				}
			}else{
				$success=false;
				$error_message=lang('This game is not available');
			}
		}

		return [$success, $status, $error_message, $status_message];
	}

	public function getMetaDataInfo() {
		$this->CI->load->model('cms');
		$uri_string = $this->CI->uri->uri_string();
		return $this->CI->cms->getMetaDataInfo($uri_string);
	}

	public function is_readonly() {
		$this->CI->load->library(array('session'));
		return $this->CI->session->userdata('readonly');
	}

	/**
	 * Filter an array, or each array in a 2-d array, keeping only selected fields
	 *
	 * @param	$ar		array	2-D array of db rows
	 * @param	$fields	array 	simple array of field names to keep
	 *
	 * @return	array
	 */
	public function array_select_fields($ar, $fields) {
		if (!is_array($ar) || !is_array($fields)) {
			return $ar;
		}

		$fields_flip = array_flip($fields);
		// For 2-D array
		if (is_array(reset($ar))) {
			foreach ($ar as & $row) {
				$row = array_intersect_key($row, $fields_flip);
			}
		}
		// For plain array
		else {
			$ar = array_intersect_key($ar, $fields_flip);
		}

		return $ar;
	}

	public function getOperatorSettingInJson($name, $field){
		$this->CI->load->model(['operatorglobalsettings']);
		//load from operator settings
		$result = $this->CI->operatorglobalsettings->getSettingJson($name, $field);
		return $result;
	}

	public function getCurrentLanguageCode($isFromPlayerSettings=false){
		$this->CI->load->library(['language_function','authentication']);

		if($isFromPlayerSettings){
			$this->CI->load->model('player_model');
			$playerId = $this->CI->authentication->getPlayerId();
			$playerId ? $currentLang = $this->CI->player_model->getLanguageFromPlayer($playerId) : $currentLang = Language_function::INT_LANG_CHINESE;
		}else{
			$currentLang = $this->CI->language_function->getCurrentLanguage();
		}

		switch ($currentLang) {

			case Language_function::INT_LANG_ENGLISH: return "en"; break;
			case Language_function::INT_LANG_CHINESE: return "cn"; break;
			case Language_function::INT_LANG_INDONESIAN: return "ind"; break;
			case Language_function::INT_LANG_VIETNAMESE: return "viet"; break;
			case Language_function::INT_LANG_KOREAN: return "kor"; break;
			case Language_function::INT_LANG_THAI: return "th"; break;
			default: return "en"; break;

		}
	}

	public function getCashierCustomErrorMessage($category,$type){
		$this->CI->load->model(['operatorglobalsettings']);
		$result = $this->CI->operatorglobalsettings->getSettingJson("cashier_notification_settings","template");

		if(isset($result[$category][$type]['is_enabled'])) {
			if($result[$category][$type]['is_enabled'] == 'false') return false;
		}
		if(!isset($result[$category]) || empty($result[$category])){
			return false;
		}

		if($category != "customer_support"){
			$isFromPlayerSettings = false;
			if (in_array($this->getCurrentLanguageCode($isFromPlayerSettings), $result[$category][$type]['multi_lang_messages'])) {
                $resultArr = $result[$category][$type]['multi_lang_messages'][$this->getCurrentLanguageCode($isFromPlayerSettings)];
            } else {
                $resultArr = $result[$category][$type]['multi_lang_messages']['en'];
            }
			// $resultArr = $result[$category][$type]['multi_lang_messages'][$this->getCurrentLanguageCode($isFromPlayerSettings)];
			$resultArr['custom_error_code'] = $result[$category][$type]['custom_error_code'] ?: lang("N/A");
			$resultArr['label'] = $result[$category][$type]['label'] ?: lang("N/A");
		}else{
			$resultArr = $result[$category][$type];
		}
		return $resultArr;
	}

	public function composeCustomErrMsg($errorCode){
		$this->CI->load->model(['operatorglobalsettings']);

		$customErrorMsg = "<p class='custom_err_msg'>".$this->getCashierCustomErrorMessage('transfer_fund_notif',$errorCode)['custom_error_msg']."</p>";

		$csButtonLink = "";
		if($this->getCashierCustomErrorMessage('customer_support',0)){
			$customerSupportUrl = $this->getCashierCustomErrorMessage('customer_support',Operatorglobalsettings::NOTIF_CS_URL)['url'];

			$csButtonLink = "<a href='".$customerSupportUrl."' target='_blank'>".lang("Click here to contactCS")."</a>";
		}

		$playerOptionMsg1 = "<p class='custom_player_option_msg1'>".$this->getCashierCustomErrorMessage('transfer_fund_notif',$errorCode)['player_option_msg1']."</p>".$csButtonLink;
		$playerOptionMsg2 = "<p class='custom_player_option_msg2'>".$this->getCashierCustomErrorMessage('transfer_fund_notif',$errorCode)['player_option_msg2']."</p>";
		$errorCode = "<p class='error-notif-code'>(".lang('Error Code Reference:').$this->getCashierCustomErrorMessage('transfer_fund_notif',$errorCode)['custom_error_code'].")</p>";

		//$message = $customErrorMsg."<br/>".$playerOptionMsg1."<br/>".$playerOptionMsg2."<br/>".$errorCode;
		$message = $customErrorMsg.$playerOptionMsg1.$playerOptionMsg2.$errorCode;
		return $message;
	}

    public function setNotActiveOrMaintenance($gamePlatformID){
        $this->CI->load->model('external_system');
        $status = (!$this->CI->external_system->isGameApiActive($gamePlatformID) || $this->CI->external_system->isGameApiMaintenance($gamePlatformID));

        if ($status) {
            return true;
        }else{
            return false;
        }
    }

  	public function array_column_concat_multi(array $input, array $column_keys) {
		$result = array();
		$column_keys = array_flip($column_keys);
		foreach($input as $key => $el) {
		  $result[$key] = implode("",array_intersect_key($el, $column_keys));
		}

		return $result;
  	}

    public function checkPlayerCanDirectlyChangePassword() {
        $this->CI->load->model(array('player_model'));
        $player_id = $this->CI->authentication->getPlayerId();
        $player = $this->CI->player_model->getPlayerById($player_id);
        if (isset($player->is_phone_registered)) {
            return ($player->is_phone_registered == PHONE_REGISTERED_YET) ? true : false;
        } else {
            return false;
        }
    }

    public function getPlayerHomeUrl($target = false){
		switch ($target) {
			case 'www':
				return $this->getSystemUrl('www');
				break;
            case 'm':
                return $this->getSystemUrl('m');
                break;
			case 'home':
				return $this->getSystemUrl('player', '/player_center/home');
				break;

			default:
				if ($this->utils->getPlayerCenterTemplate() == 'iframe') {
					return $this->getSystemUrl('player', '/iframe_module/iframe_viewCashier');
				} else {
					return $this->getSystemUrl('player', '/player_center/dashboard');
				}
				break;
		}
    }

    public function getPlayerDepositUrl(){
        return $this->getSystemUrl('player','player_center2/deposit');
	}

    public function getPlayerReferralOnClick() {
        $referralUrlConfig = $this->utils->getConfig('player_center_referral_onclick');

        $playerReferralUrl = $this->utils->getSystemUrl('player', '/player_center2/referral', false);
        if(!empty($referralUrlConfig['player'])){
            $host = $referralUrlConfig['player']['host'];
            $url = $referralUrlConfig['player']['url'];
            $playerReferralUrl = $this->utils->getSystemUrl($host, $url);
        }

        $playerMobileReferralUrl = site_url('player_center2/referral');
        if(!empty($referralUrlConfig['player_mobile'])){
            $host = $referralUrlConfig['player_mobile']['host'];
            $url = $referralUrlConfig['player_mobile']['url'];
            $playerMobileReferralUrl = $this->utils->getSystemUrl($host, $url);
        }

        if($this->is_mobile()){
            return $playerMobileReferralUrl;
        }else{
            return $playerReferralUrl;
        }
    }

    public function getPlayerReferralUrl(){
        return $this->getSystemUrl('player','player_center2/referral');
    }

	public function getPlayerBankAccountUrl($hash = ''){
		$url = $this->getSystemUrl('player','player_center2/bank_account');
		if( ! empty($hash) ){
			$url .=  '#'. $hash;
		}
		return $url;
    }

    public function getPlayerMessageUrl(){
        return $this->getSystemUrl('player','player_center2/messages');
    }

    public function getPlayerSecurityUrl(){
        return $this->getSystemUrl('player','player_center2/security');
    }

    public function getPlayerHistoryUrl($history_type = NULL){
        return (empty($history_type)) ? $this->getSystemUrl('player','player_center2/report') : $this->getSystemUrl('player','player_center2/report/index/' . $history_type);
    }

    public function getPlayerLoginUrl() {
        return $this->getSystemUrl('player','/iframe/auth/login');
    }

    public function getPlayerLogoutUrl() {
        return $this->getSystemUrl('player','/iframe/auth/logout');
    }

    public function getPlayerProfileSetupUrl() {
		return $this->getSystemUrl('player','/player_center/dashboard/index#accountInformation');
	}

    public function getPlayerProfileSetupUrlFromGameLaunch() {
		return $this->getSystemUrl('player','/player_center/dashboard/index?is_game_launch=true#accountInformation');
	}

	public function getPlayerProfileUrl() {
		return $this->getSystemUrl('player','/player_center/profile');
	}


	public function getPlayerShopUrl() {
        return $this->getSystemUrl('player','/player_center/dashboard/index#shop');
    }

    public function getPlayerForgotPassword() {
        return $this->getSystemUrl('player','/iframe_module/forget_password_select');
    }

    public function checkForgetPasswordEnabled() {
	    $result = FALSE;
	    $this->CI->load->model('operatorglobalsettings');
        $forget_password_enabled = $this->CI->operatorglobalsettings->getSettingJson('forget_password_enabled');
        $forget_password_enabled = isset($forget_password_enabled) ? (bool)$forget_password_enabled : FALSE ;

        if($forget_password_enabled){
            $result['enabled'] = $forget_password_enabled;
            $passwordRecoverySetting = $this->CI->operatorglobalsettings->getSettingIntValue('password_recovery_options');
            $security_question_enabled = (bool)($passwordRecoverySetting & 1);
            $sms_enabled = (bool)($passwordRecoverySetting & 2);
            $email_enabled = (bool)($passwordRecoverySetting & 4);

            $sms_api = $this->getConfig('sms_api');
            $sms_api_exist = !empty($sms_api);
            $sms_enabled = $sms_enabled && $sms_api_exist;

            //security question
            if($security_question_enabled){
                $result['recovery_options']['security_question'] = [
                    'enabled' => true,
                    'url' => $this->getSystemUrl('player', 'iframe_module/forgot_password')
                ];
            }

            //sms
            if($sms_enabled){
                $result['recovery_options']['sms'] = [
                    'enabled' => true,
                    'sms_api' => $sms_api,
                    'url' => $this->getSystemUrl('player', 'iframe_module/password_recovery_sms')
                ];
            }

            //email
            if($email_enabled){
                $result['recovery_options']['email'] = [
                    'enabled' => true,
                    'url' => $this->getSystemUrl('player', 'iframe_module/password_recovery_email')
                ];
            }

            if($forget_password_enabled && !$security_question_enabled && !$sms_enabled && !$email_enabled){
                $result = FALSE;
            }
        }

	    return $result;
    }

    /**
     * minus seconds from date time
     * @param  string $datetime
     * @param  integer $seconds
     * @return string
     */
	public function getMinusSecondsForMysql($datetime, $seconds) {
		if(empty($datetime)){
			return null;
		}
		$d = new \DateTime($datetime);
		$d->modify('-' . $seconds . ' minutes');
		return $this->formatDateTimeForMysql($d);
	}

    public function loadLanguage($lang, $file = 'main', $focus = false) {
        if ($focus) {
            $this->CI->lang->focus_load = $focus;
        }
        $this->CI->lang->load($file, $lang);

        $custom_lang = config_item('custom_lang');
        if((FALSE !== $custom_lang) && (file_exists(APPPATH . 'language/custom/' . $custom_lang . '/' . $lang . '/custom_lang.php'))){
            $this->CI->lang->load('custom', 'custom/' . $custom_lang . '/' . $lang);
        }
        //get back
        $this->CI->lang->focus_load = false;
    }

    public function initiateLang() {
        $this->CI->load->library(array('language_function'));

		$xLang=$this->CI->input->get_request_header('X-Lang');

		if(empty($xLang)){
			$lang=intval($this->CI->language_function->getCurrentLanguage());
		}else{
			$lang=intval($this->CI->language_function->isoLangCountryToIndex($xLang));
		}

		$this->CI->language_function->setCurrentLanguage($lang);

        // $lang = $this->CI->language_function->getCurrentLanguage();
        $langCode = $this->CI->language_function->getLanguageCode($lang);
        $language = $this->CI->language_function->getLanguage($lang);
        $this->CI->lang->load($langCode, $language);

        $custom_lang = config_item('custom_lang');
        if((FALSE !== $custom_lang) && (file_exists(APPPATH . 'language/custom/' . $custom_lang . '/' . $language . '/custom_lang.php'))){
            $this->CI->lang->load('custom', 'custom/' . $custom_lang . '/' . $language);
        }
    }

    /**
     * removed duplicate values by $key
     * @return object
     */
    public function unique_multidim_array($array, $key) {
        $result = array();
        $i = 0;
        $key_array = array();

        foreach($array as $val) {
            if (!in_array($val[$key], $key_array)) {
                $key_array[$i] = $val[$key];
                $result[$i] = $val;
            }
            $i++;
        }
        return $result;
    }

    public function isAvailableUrl($url){
        return (FALSE === strpos($url, 'http://') && FALSE === strpos($url, 'https://')) ? FALSE : TRUE;
    }

    public function getSharingUploadPath($related_path = NULL) {
        $filepath= (empty($related_path)) ? $this->getConfig('SHARING_UPLOAD_PATH') : rtrim($this->getConfig('SHARING_UPLOAD_PATH'), '/') . $related_path;
		if(!file_exists($filepath)){
			@mkdir($filepath, 0777, true);
			@chmod($filepath, 0777);
		}

        return $filepath;
    }

    //=====currency and mdb=====================================================
    private $_available_currency_list=null;
    /**
     * load from config
     * @return list of currency
     */
	public function getAvailableCurrencyList(){
		if(!$this->isEnabledMDB()){
			return null;
		}

		if($this->_available_currency_list===null){
			$this->_available_currency_list=[];
			// 'cny'=>['code'=>'CNY', 'name'=>'CN Yuan', 'short_name'=>'元', 'symbol'=>'¥', 'decimals'=>2, 'dec_point'=>'.', 'thousands_sep'=>','],
			$multiple_currency_list=$this->getConfig('multiple_currency_list');
			if(!empty($multiple_currency_list)){
				$this->_available_currency_list=$multiple_currency_list;
			}
			unset($multiple_currency_list);
		}

		return $this->_available_currency_list;
	}
    /**
     * To filer the Available Currency List for enable selection defined
     *
     * @param array $availableCurrencyList The return array from utils::getAvailableCurrencyList().
     * @param string $enableSelectionFor The usaged target String, ex: "old_player_center" and "new_player_center_api".
     * @return array The filered array
     */
    public function filterAvailableCurrencyList4enableSelection($availableCurrencyList, $enableSelectionFor = 'old_player_center'){
        $filteredCurrencyList = [];
        $self = $this; // utils
        if( !empty($availableCurrencyList) ){
            // $client_ip = $this->utils->getConfig('try_real_ip_on_acl_api') ? $this->utils->tryGetRealIPWithoutWhiteIP() : $this->utils->getIp();
            $ip_address = $this->utils->getIp();
            // $referrer = ($this->CI->agent->is_referral() == TRUE) ? $this->CI->agent->referrer() : ' ';
            $referrer = $this->utils->getHttpRefererDomain();

            $enableSelectionFor = strtolower($enableSelectionFor);
            $filteredCurrencyList = array_filter($availableCurrencyList
                , function($_value, $_key) use ( $ip_address, $referrer, $enableSelectionFor, $self ) {
                    $rlt = null;
                    switch( $enableSelectionFor ){
                        default:
                        case 'old_player_center':
                            $rlt = $self->_filterAvailableCurrencyListByEnableSelectionKey($ip_address, $referrer, $_value, $_key, 'enable_selection_for_old_player_center');
                            break;
                        case 'new_player_center_api':
                            $rlt = $self->_filterAvailableCurrencyListByEnableSelectionKey($ip_address, $referrer, $_value, $_key, 'enable_selection_for_new_player_center_api');
                            break;
                    }
                    $self->utils->debug_log('OGP31625.13999.rlt', $rlt
                                                , '_key:', $_key
                                                , 'enableSelectionFor:', $enableSelectionFor
                                                , 'ip_address:', $ip_address
                                                , 'referrer:', $referrer
                                             );
                    return $rlt;
                    // return true; // stay
                    // return false; // filter out
            }, ARRAY_FILTER_USE_BOTH);
        } // EOF if( !empty($availableCurrencyList) ){...
        return $filteredCurrencyList;
    } // EOF filterAvailableCurrencyList4enableSelection
    //
    public function _filterAvailableCurrencyListByEnableSelectionKeyAndIp ( $ip_address, $currencyValue, $currencyKey, $enable_selection_key){
        $rlt = null; // true: allow, false: disallow.

        ///
        // enable_selection_for_XXX_player_center: noSet, true, false
        // white_list_ip: noSet, inArray, notInArray
        //
        //
        $enableSelectionStr = 'noSet';
        if( isset($currencyValue[$enable_selection_key]) ){
            $enableSelectionStr = !empty($currencyValue[$enable_selection_key])? 'enable': 'disabled';
        }
        $isInList = 'noSet';
        if( isset($currencyValue['white_list_ip']) ){
            $isInList = in_array($ip_address, $currencyValue['white_list_ip'])? 'inArray': 'notInArray';
        }

        $switchStr = '';
        $switchStr .= $enableSelectionStr;
        $switchStr .= '.';
        $switchStr .= $isInList;
        switch($switchStr){ // enableSelectionStr. isInList
            case 'noSet.noSet':
                $rlt = true; // allow by noSet in enableSelectionStr
                break;
            case 'noSet.inArray':
                $rlt = true; // allow by noSet in enableSelectionStr
                break;
            case 'noSet.notInArray':
                $rlt = true; // allow by noSet in enableSelectionStr
                break;

            case 'enable.noSet':
                $rlt = true; // allow by enable in enableSelectionStr
                break;
            case 'enable.inArray':
                $rlt = true; // allow by enable in enableSelectionStr
                break;
            case 'enable.notInArray':
                $rlt = true; // allow by enable in enableSelectionStr
                break;

            case 'disabled.noSet':
                $rlt = false; // disallow by disabled in enableSelectionStr
                break;
            case 'disabled.inArray':
                $rlt = true; // allow by in_array of white_list_ip
                break;
            case 'disabled.notInArray':
                $rlt = false; // disallow by Not in_array of white_list_ip
                break;
        }

        $this->utils->debug_log('OGP31625.13999.14046InIp.rlt', $rlt
                                                , 'switchStr:', $switchStr
                                                , 'currencyKey:', $currencyKey
                                                , 'ip_address:', $ip_address
                                                , 'white_list_ip:', empty($currencyValue['white_list_ip'])? null: $currencyValue['white_list_ip']
                                             );
        return [$rlt, $switchStr];
    }
    public function _filterAvailableCurrencyListByEnableSelectionKeyAndReferrer ( $referrer, $currencyValue, $currencyKey, $enable_selection_key){
        $rlt = null; // true: allow, false: disallow.

        ///
        // enable_selection_for_XXX_player_center: noSet, true, false
        // white_list_domain: noSet, inArray, notInArray
        //
        //
        $enableSelectionStr = 'noSet';
        if( isset($currencyValue[$enable_selection_key]) ){
            $enableSelectionStr = !empty($currencyValue[$enable_selection_key])? 'enable': 'disabled';
        }
        $isInList = 'noSet';
        if( isset($currencyValue['white_list_domain']) ){
            $isInList = in_array($referrer, $currencyValue['white_list_domain'])? 'inArray': 'notInArray';
        }

        $switchStr = '';
        $switchStr .= $enableSelectionStr;
        $switchStr .= '.';
        $switchStr .= $isInList;
        switch($switchStr){ // enableSelectionStr. isInList
            case 'noSet.noSet':
                $rlt = true; // allow by noSet in enableSelectionStr
                break;
            case 'noSet.inArray':
                $rlt = true; // allow by noSet in enableSelectionStr
                break;
            case 'noSet.notInArray':
                $rlt = true; // allow by noSet in enableSelectionStr
                break;

            case 'enable.noSet':
                $rlt = true; // allow by enable in enableSelectionStr
                break;
            case 'enable.inArray':
                $rlt = true; // allow by enable in enableSelectionStr
                break;
            case 'enable.notInArray':
                $rlt = true; // allow by enable in enableSelectionStr
                break;

            case 'disabled.noSet':
                $rlt = false; // disallow by disabled in enableSelectionStr
                break;
            case 'disabled.inArray':
                $rlt = true; // allow by in_array of white_list_domain
                break;
            case 'disabled.notInArray':
                $rlt = false; // disallow by Not in_array of white_list_domain
                break;
        }


        $this->utils->debug_log('OGP31625.13999.14088InReferrer.rlt', $rlt
                                            , 'switchStr:', $switchStr
                                            , 'currencyKey:', $currencyKey
                                            , 'referrer:', $referrer
                                            , 'white_list_domain:', empty($currencyValue['white_list_domain'])? null: $currencyValue['white_list_domain']
                                        );
        return [$rlt, $switchStr];
    }
    //
    public function _filterAvailableCurrencyListByEnableSelectionKey( $ip_address, $referrer, $currencyValue, $currencyKey, $enable_selection_key = 'enable_selection_for_old_player_center' ){
        $rlt = true; // stay
        list($_rlt4Ip, $switchStr4Ip) = $this->_filterAvailableCurrencyListByEnableSelectionKeyAndIp($ip_address, $currencyValue, $currencyKey, $enable_selection_key);
        list($_rlt4Referrer, $switchStr4Referrer ) = $this->_filterAvailableCurrencyListByEnableSelectionKeyAndReferrer($referrer, $currencyValue, $currencyKey, $enable_selection_key);


        $ipNoSet = false;
        if( strpos($switchStr4Ip, '.noSet') !== false){
            $ipNoSet = true;
        }
        $referrerNoSet = false;
        if( strpos($switchStr4Referrer, '.noSet') !== false){
            $referrerNoSet = true;
        }
        if(!$ipNoSet && $referrerNoSet){
            $rlt = $_rlt4Ip; // as by Ip
        }elseif($ipNoSet && !$referrerNoSet){
            $rlt = $_rlt4Referrer; // as by Referrer
        }else{
            $rlt = $_rlt4Ip && $_rlt4Referrer;
        }
        $this->utils->debug_log('OGP31625.13999.14157InEnableSelection.rlt', $rlt
                                            , '_rlt4Ip:', $_rlt4Ip
                                            , 'switchStr4Ip:', $switchStr4Ip
                                            , '_rlt4Referrer:', $_rlt4Referrer
                                            , 'switchStr4Referrer:', $switchStr4Referrer
                                            , 'currencyKey:', $currencyKey
                                            , 'white_list_ip:', empty($currencyValue['white_list_ip'])? null: $currencyValue['white_list_ip']
                                            , 'white_list_domain:', empty($currencyValue['white_list_domain'])? null: $currencyValue['white_list_domain']
                                        );
        return $rlt;
    } // EOF _filterAvailableCurrencyList4enableSelectionInOldPlayerCenter

	public function getActiveCurrencyKeyOnMDB(){
		if($this->isEnabledMDB()){
			$_multiple_db=Multiple_db::getSingletonInstance();
			return $_multiple_db->getActiveTargetDB();
		}

		return null;
	}

	public function getActiveCurrencyInfoOnMDB(){

		$currencyKey=$this->getActiveCurrencyKeyOnMDB();
		if(!empty($currencyKey)){
			if($currencyKey==Multiple_db::SUPER_TARGET_DB){
				//get default_currency_on_super
				return $this->getConfig('default_currency_on_super');
			}else{
				//get currency info from
				$list=$this->getAvailableCurrencyList();
				return isset($list[$currencyKey]) ? $list[$currencyKey] : null;
			}
		}

		return null;
	}

	public function getActiveCurrencyDBFormatOnMDB(){
		$result=['currencyCode'=>null, 'currencyName'=>null, 'currencySymbol'=>null, 'currencyShortName'=>null];
		$info=$this->getActiveCurrencyInfoOnMDB();
		if(!empty($info)){
			$result['currencyCode']=$info['code'];
			$result['currencyName']=$info['name'];
			$result['currencySymbol']=$info['symbol'];
			$result['currencyShortName']=$info['short_name'];
		}

		return $result;
	}

	public function isAvailableCurrencyKey($key, $case_sensative = true){
		$list=$this->getAvailableCurrencyList();
		if($case_sensative) {
			return array_key_exists($key, $list);
		}
		else {
			return array_key_exists(strtolower($key), array_change_key_case($list, CASE_LOWER));
		}
	}

	public function getActiveTargetDB(){
		if($this->isEnabledMDB()){
			$_multiple_db=Multiple_db::getSingletonInstance();
			return $_multiple_db->getActiveTargetDB();
		}
		return 'default';
	}

	/**
	 * enabled mdb
	 * @return boolean
	 */
	public function isEnabledMDB(){
		$_multiple_db=Multiple_db::getSingletonInstance();
		return $_multiple_db->isEnabledMDB();
	}

	/**
	 * is super mode on mdb, only when enabled mdb
	 * @return boolean
	 */
	public function isSuperModeOnMDB(){
		$_multiple_db=Multiple_db::getSingletonInstance();
		return $this->isEnabledMDB() && $_multiple_db->isSuperModeOnMDB();
	}

	/**
	 * is super or no mdb mode
	 * if single db, back to normal.
	 * some function only allow on super site if mdb mode
	 * @return boolean
	 */
	public function isSuperSiteOrNoMDB(){
		return $this->isSuperModeOnMDB() || !$this->isEnabledMDB();
	}

	/**
	 * enable super site
	 * @return boolean
	 */
	public function isEnabledSuperSite(){
		return $this->isEnabledMDB() && !$this->getConfig('disabled_super_site');
	}

	public function getMDBList(){
		$_multiple_db=Multiple_db::getSingletonInstance();
		return $_multiple_db->getMDBList();
	}

	public function getActiveUploadSuffix(){
		$suffix=null;
		$_multiple_db=Multiple_db::getSingletonInstance();
		if($_multiple_db->isEnabledMDB()){
			$suffix=$this->getActiveTargetDB();
		}
		return $suffix;
	}

	public function addSuffixOnMDB(&$path){
		$suffix=$this->getActiveUploadSuffix();
		if(!empty($suffix)){
			$path=rtrim($path).'/'.$suffix;
		}
	}

	public function getUploadThemeUri(){
		$path='/upload/themes';
		$this->addSuffixOnMDB($path);
		return $path;
	}

	public function getUploadUri(){
		$path='/upload';
		$this->addSuffixOnMDB($path);
		return $path;
	}

	public function getInstanceMDB(){
		return Multiple_db::getSingletonInstance();
	}

    public function foreachMultipleDBToCIDB(callable $callback, $readonly=false, $excludeList=null){

    	$readonly=$readonly && $this->utils->isEnabledReadonlyDB();

    	$result=[];
    	$multiple_databases=$this->utils->getConfig('multiple_databases');
    	if(!empty($multiple_databases)){
    		$this->CI->load->model(['player_model']);
    		$keys=array_keys($multiple_databases);
			//replace ci db and ci dbforge
			$this->CI->load->dbforge();
			$lastForgeDB=$this->CI->dbforge->getDB();
    		$lastDB=$this->CI->db;
    		foreach ($keys as $db_name) {
    			if(!empty($excludeList) && in_array($db_name, $excludeList)){
	    			$this->utils->debug_log('ignore db : '.$db_name);
    				continue;
    			}
				if($readonly){
					$db_name=$db_name.'_readonly';
				}else{
					$db_name=$db_name;
				}
    			$this->CI->db=$this->CI->player_model->getAnyDBFromMDBByKey($db_name);
	    		$this->CI->dbforge->changeDB($this->CI->db);
    			//run key
    			// $this->utils->debug_log('run db : '.$db_name);
    			$result[$db_name]=$callback($this->CI->db,$db_name);
    		}
    		//restore
    		$this->CI->db=$lastDB;
    		$this->CI->dbforge->changeDB($lastForgeDB);
    	}else{
    		$lastDB=$this->CI->db;
    		if($readonly){
				$this->CI->db=$this->CI->load->database(READONLY_DATABASE, TRUE);
    		}
    		//just current db
    		$result['default']=$callback($this->CI->db);
    		if($readonly){
	    		$this->CI->db=$lastDB;
    		}
    	}

    	return $result;
    }

    public function appendActiveDBToUrl(&$url){
    	return $this->appendDBToUrl($url, $this->getActiveTargetDB());
    }

    public function appendDBToUrl(&$url, $db){
    	if($this->isEnabledMDB() && !empty($url)){
			if (substr($url, strlen($url) - 1, 1) == '?') {
				//don't append
			} else {
				$url .= strpos($url, '?') !== FALSE ? '&' : '?';
			}
    		$url.=Multiple_db::__OG_TARGET_DB.'='.$db;
    	}
    }

    //=====currency and mdb=====================================================

    public function text_from_json($_json_text, $lang_code = '') {
    	if (preg_match('/^_json:(.+)$/', $_json_text, $match) == 0) {
    		return $_json_text;
    	}
    	$json_real = $match[1];
    	$lang_ar = json_decode($json_real, 'as array');

    	if (empty($lang_code)) {
    		return $lang_ar;
    	}

    	if (isset($lang_ar[$lang_code])) {
    		return $lang_ar[$lang_code];
    	}

    	$default_lang = reset($lang_ar);

    	return $default_lang;
    }

	public function gtEndDate(\DateTime $d, \DateTime $end) {
		return $this->formatDateForMysql($d) > $this->formatDateForMysql($end);
	}

	public function foreachDaily($from, $to, $step_days, $callback) {
		if (is_string($from)) {
			$start = new \DateTime($from.' '.self::FIRST_TIME);
		}

		if (is_string($to)) {
			$end = new \DateTime($to.' '.self::LAST_TIME);
		}

		$success = false;
		$startDate = $start;
		$step='+'.$step_days.' days';

		$this->utils->info_log('start foreach', $start, $end, $step);
		while (!$this->gtEndDate($startDate, $end)) {

			//always one day
			$from = clone $startDate;
			$to = new DateTime($from->format('Y-m-d').' 23:59:59');

			$this->utils->debug_log('run callback', $from, $to);
			$success=$callback($from, $to);

			if (!$success) {
				$this->utils->error_log('loop date time failed', $from, $to);
				break;
			}

			$startDate = $this->getNextTime($startDate, $step);

			unset($from);
			unset($to);
		}

		return $success;
	}

	public function extractFloatNumber($str) {
		/*
		 ex.
		 afdfd1000.23 -> 1000.23
		 126,564,789.33 m² -> 126564789.33
		*/
	 	return (double)filter_var($str, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
	}

	public function loadAffiliateExternalLoginApi(){

		// $this->debug_log('APPPATH:'.APPPATH);

		$external_login_api_class=$this->getConfig('affiliate_external_login_api_class');
		if(!empty($external_login_api_class) && file_exists(dirname(__FILE__).'/external_login/'.$external_login_api_class.'.php')){

			// $this->debug_log('try load external_login/'.$external_login_api_class);

			$this->CI->load->library('external_login/'.$external_login_api_class);
			$api=$this->CI->$external_login_api_class;
			if(!empty($api)){
				return $api;
				// $success=$api->validateUsernamePassword($playerId, $username, $password, $message);
			}
		}

		return null;
	}

	public function login_external_for_affiliate($affiliateId, $username, $password, &$message=''){
		$success=false;

		if(!empty($username) && !empty($password)){
			//try load class
			$api=$this->loadAffiliateExternalLoginApi();
			if(!empty($api)){
				$success=$api->validateUsernamePassword($affiliateId, $username, $password, $message);
			}
		}

		return $success;
	}

	public function buildCurrencySelectHtml($logged, $addItemAll, $addClass='form-control input-sm', $ignoreFilterByEnableSelection = true){
        $html='';
        if($this->CI->utils->isEnabledMDB()){
            $list=$this->CI->utils->getAvailableCurrencyList();
            $active_currency_key=$this->CI->utils->getActiveCurrencyKeyOnMDB();
            if(!$ignoreFilterByEnableSelection){
                $list = $this->CI->utils->filterAvailableCurrencyList4enableSelection($list, 'enable_selection_for_old_player_center');
            }
            $logged_class = $this->getConfig('hide_currency_list_in_player_logged_page') ? '_select_currecny_on_logged hide' : '_select_currecny_on_logged';
            $login_class = $this->getConfig('hide_currency_list_in_player_login_page') ? '_select_currecny_on_login hide' : '_select_currecny_on_login';
            if(!empty($list) && !$this->isCurrencyDomain()){
                $class=$logged ? $logged_class : $login_class;
                $html='<!-- active: '.$active_currency_key.' --><select class="'.$class.' '.$addClass.'">';
                if($addItemAll){
                	$html.='<option value="'.Multiple_db::SUPER_TARGET_DB.'" >'.lang('All').'</option>';
                }
                foreach ($list as $currencyKey => $currencyInfo) {
                    $selected=$currencyKey==$active_currency_key ? 'selected' : '' ;
                    $html.='<option value="'.$currencyKey.'" '.$selected.' >'.$currencyInfo['symbol'].' '.$currencyInfo['code'].'</option>';
                }
                $html.='</select>';
            }
        }

        return $html;
	}

	//====global lock for mdb============================================================================================
	public function getGlobalLockServer() {
		if(!isset($this->global_lock_server)){
			$servers = $this->getConfig('lock_servers');
			$this->CI->load->library('third_party/lock_server', $servers, 'global_lock_server');
			if($this->isEnabledMDB()){
				$this->CI->load->model('multiple_db_model');
				$superDB=$this->CI->multiple_db_model->getSuperDBFromMDB();
				$this->debug_log('change prefix for global lock: '.$superDB->getDBName());
				$this->CI->global_lock_server->setPrefix($superDB->getDBName());
			}
			$this->CI->global_lock_server->setRetryDelay($this->getConfig('lock_retry_delay'));
			$this->global_lock_server=$this->CI->global_lock_server;
		}
		return $this->global_lock_server;
	}

	public function globalLockAny($anyId, $type, callable $callback) {
		$success = false;
		$lockedKey=null;
		$lock_it = $this->globalLockResourceBy($anyId, $type, $lockedKey);
		try {
			if ($lock_it) {

				$success = $callback();

			} else {
				$retryTime=$this->getConfig('lock_retry_delay')*20;
				$timeout = $this->getConfig('app_lock_timeout') * 1000;
				$this->error_log('global lock failed, timeout: '.$timeout, 'lock retry time: '.$retryTime, $type, $anyId);
			}
		} finally {
			if(!empty($lockedKey)){
				$rlt = $this->globalReleaseResourceBy($anyId, $type, $lockedKey);
				if(!$rlt){
					$this->error_log('global release failed', $type, $anyId, $lockedKey);
				}
			}elseif($lock_it){
				$this->error_log('cannot global release empty key', $type, $anyId, $lockedKey);
			}
		}
		return $success;
	}

	public function globalLockResourceBy($anyId, $type, &$lockedKey) {
		$resourceKey = $this->createLockKey(array($anyId, $type));

		return $this->globalLockResource($resourceKey, $lockedKey);
	}

	public function globalLockResource($resourceKey, &$lockedKey) {
		$this->verbose_log('try lock resource', $resourceKey, $lockedKey);
		//ms
		$timeout = $this->getConfig('app_lock_timeout') * 1000;

		$lock_server = $this->getGlobalLockServer();
		$add_prefix=true;
		$lockedKey = $lock_server->lock($resourceKey, $timeout, $add_prefix);
		$locked = $lockedKey != false;

		if($locked){
			//setup last locked key
			$this->last_global_locked_key=$lockedKey;
		}

		$this->debug_log('global resourceKey', $resourceKey, 'locked', $locked, ($locked ? 'success' : 'failed'));
		// }
		return $locked;
	}

	public function globalReleaseResourceBy($anyId, $type, &$lockedKey) {
		$resourceKey = $this->createLockKey(array($anyId, $type));

		return $this->globalReleaseResource($resourceKey, $lockedKey);
	}

	public function globalReleaseResource($resourceKey, &$lockedKey) {
		$this->verbose_log('try release resource', $resourceKey, $lockedKey);

		$lock_server = $this->getGlobalLockServer();

		$released = $lock_server->unlock($lockedKey);

		if($released){
			$this->last_global_locked_key=null;
		}

		return $released;
	}

	public function globalTryReleaseLastResource(){

		$this->debug_log('globalTryReleaseLastResource', $this->last_global_locked_key);

		if(!empty($this->last_global_locked_key)){
			$lockedKey=$this->last_global_locked_key;
			return $this->globalReleaseResource('', $lockedKey);
		}
		return true;
	}

	public function globalLockPlayerRegistration($username, callable $callback) {
		return $this->globalLockAny($username, self::GLOBAL_LOCK_ACTION_PLAYER_REGISTRATION, $callback);
	}

    public function globalLockPlayerLevel($username, callable $callback) {
		return $this->globalLockAny($username, self::GLOBAL_LOCK_ACTION_PLAYER_LEVEL, $callback);
	}
    public function globalLockVipsettingId($vipsettingId, callable $callback) {
		return $this->globalLockAny($vipsettingId, self::GLOBAL_LOCK_ACTION_VIPSETTING_ID, $callback);
	}

	public function globalLockUserRegistration($username, callable $callback) {
		return $this->globalLockAny($username, self::GLOBAL_LOCK_ACTION_USER_REGISTRATION, $callback);
	}

	public function globalLockAffiliateRegistration($username, callable $callback) {
		return $this->globalLockAny($username, self::GLOBAL_LOCK_ACTION_AFFILIATE_REGISTRATION, $callback);
	}

	public function globalLockAgencyRegistration($username, callable $callback) {
		return $this->globalLockAny($username, self::GLOBAL_LOCK_ACTION_AGENCY_REGISTRATION, $callback);
	}

	public function globalLockRoleRegistration($rolename, callable $callback) {
		return $this->globalLockAny($rolename, self::GLOBAL_LOCK_ACTION_ROLE_REGISTRATION, $callback);
	}

	public function globalLockRegistrationSettings($type, callable $callback) {
		return $this->globalLockAny($type, self::GLOBAL_LOCK_ACTION_REGISTRATION_SETTINGS, $callback);
	}

	public function globalLockSyncPlayer($username, callable $callback) {
		return $this->globalLockAny($username, self::GLOBAL_LOCK_ACTION_PLAYER_REGISTRATION, $callback);
	}

	public function globalLockSyncUser($username, callable $callback) {
		return $this->globalLockAny($username, self::GLOBAL_LOCK_ACTION_USER_REGISTRATION, $callback);
	}

	public function globalLockSyncAffiliate($username, callable $callback) {
		return $this->globalLockAny($username, self::GLOBAL_LOCK_ACTION_AFFILIATE_REGISTRATION, $callback);
	}

	public function globalLockSyncAgency($username, callable $callback) {
		return $this->globalLockAny($username, self::GLOBAL_LOCK_ACTION_AGENCY_REGISTRATION, $callback);
	}

	public function globalLockSyncRole($rolename, callable $callback) {
		return $this->globalLockAny($rolename, self::GLOBAL_LOCK_ACTION_ROLE_REGISTRATION, $callback);
	}

	public function globalLockSyncRegistrationSettings($type, callable $callback) {
		return $this->globalLockAny($type, self::GLOBAL_LOCK_ACTION_REGISTRATION_SETTINGS, $callback);
	}

	//====global lock for mdb============================================================================================

	public function getSyncDateRule(){
		$billingDay=$this->getConfig('billing_day');
		$minDatetime=new DateTime();
		$maxDatetime=new DateTime();

		$today=new DateTime();
		if($today->format('j')>$billingDay){
			//only sync this month
			$minDatetime=$minDatetime->format('Y-m-01 00:00:00');
			$maxDatetime=$maxDatetime->format('Y-m-t 23:59:59');
		}else{
			//only sync last month to this month
			$minDatetime->modify('-1 month');
			$minDatetime=$minDatetime->format('Y-m-01 00:00:00');
			$maxDatetime=$maxDatetime->format('Y-m-t 23:59:59');
		}

		return [$minDatetime, $maxDatetime];
	}

	/**
	 * Check if permission is deprecated
	 * @param  string  $permission_code [description]
	 * @return boolean
	 *
	 * Created by Frans Eric Dela Cruz (frans.php.ph) 11-27-2018
	 */
	public function isDeprecatedPermission($permission_code){
		$deprecated_permissions = $this->getConfig('deprecated_permissions');

		if(in_array($permission_code, $deprecated_permissions)) {
			return true;
		}

		return false;
	}

	/**
	 * Display either Deprecated or Not
	 * @param  string $permission_code
	 * @param  string $permission_name
	 * @return string
	 *
	 * Created by Frans Eric Dela Cruz (frans.php.ph) 11-27-2018
	 */
	public function displayPermission($permission_code, $permission_name){
		if($this->isDeprecatedPermission($permission_code)){

			$permission_name = '<del>' . $permission_name . '</del>  <span class="text-danger">' . lang('role.deprecated') . '</span>';
		}

		return $permission_name;
	}

	/*
	 *
	 * @param  string $hourFormat YYYYmmddHH, sample: 2018113008
	 * @return string
	 */
	public function convertHourFormatToDateTime($hourFormat, $isFirstTime){
		$y=substr($hourFormat, 0, 4);
		$m=substr($hourFormat, 4, 2);
		$d=substr($hourFormat, 6, 2);
		$h=substr($hourFormat, 8, 2);
		return $y.'-'.$m.'-'.$d.' '.$h.($isFirstTime ? ':00:00' : ':59:59' );
	}

	/**
	 * masking IM Account
	 * Not used anywhere after patched OGP-15079.
	 * @param  string $value [description]
	 * @return string
	 */
	public function maskingIMAccount($value){
		$length = mb_strlen($value);
    	$default_length = 8;

    	if($length > $default_length){

			$first_two_letters 	= mb_substr($value, 0, 2);

			$mask = $first_two_letters . '******';

    	}
    	else
    	{
    		$mask = preg_replace ( "/\S/", "*", $value );
    	}

    	return $mask;
	}

	/**
	 * Saves detailed result to a log file
	 * -
	 * There are cases when we want to save large amount
	 * of result messages but the size won't fit in the database.
	 * We can use this method for saving large messages from results
	 * of a remote command to a log file instead of saving it in the database.
	 *
	 * @param  string $token Task ID
	 * @param  string $method_name Name of method called for remote job
	 * @param  string $content
	 * @return string link to view remote log
	 */
	public function _saveDetailedResultToRemoteLog($token, $method_name, $message){
		if(empty($token)) return;

		$success = true;
		$remote_log_file_path = $this->utils->getSharingUploadPath('/remote_logs');

		// -- sample filename: methodName_20181214_abcDeFgHiJk12345678.log
		$file_name = $method_name.'_'.date('YmdHis').'_'.$token;
		$file_path = rtrim($remote_log_file_path, '/').'/'.$file_name.'.log';

		$store_file = @file_put_contents($file_path, $message);

		if($store_file === FALSE) {
			$success = false;
			$this->utils->error_log('FAILED TO OPEN FILE: '.$file_path);
		}
		else
			$this->utils->info_log('LINK TO REMOTE LOG FILE: '. site_url("/system_management/view_remote_log/{$token}/{$file_name}"));

		return !$success ? FALSE : site_url("/system_management/view_remote_log/{$token}/{$file_name}");
	}

	public function _appendSaveDetailedResultToRemoteLog($token, $method_name, $message, &$filepath, $writeToCsv=false, $csvHeader=[]){
		if(empty($token)) {
			$this->error_log('No token supplied');
			return;
		}
		$remote_log_file_path = $this->utils->getSharingUploadPath('/remote_logs');
		$file_name = $method_name.'_'.$token;

	     if($writeToCsv){
            $filepath = rtrim($remote_log_file_path, '/').'/'.$file_name.'_log.csv';
    		$fp = fopen($filepath, (file_exists($filepath)) ? 'a' : 'w');
    		if ($fp) {
    			$BOM = "\xEF\xBB\xBF";
                fwrite($fp, $BOM); //
            } else {
                //create report failed
            	$this->utils->error_log('create csv file failed', $filepath);
            	return;
            }
            if(!empty($csvHeader)){
           		fputcsv($fp, $csvHeader, ',', '"');
            }else{
            	fputcsv($fp, $message, ',', '"');
            }
            fclose($fp);
		} else {
			$filepath = rtrim($remote_log_file_path, '/').'/'.$file_name.'.log';
    		$fp = fopen($filepath, (file_exists($filepath)) ? 'a' : 'w');
			fwrite($fp, $message."\n");
            fclose($fp);
		}
	}

	/**
	 * Used for redirection in sidebar if permission used is disabled
	 *
	 * Created by Mark Andrew Mendoza (andrew.php.ph)
	 */
	public function activeAffiliateSidebar($exitLoop = false){
		$affurl = '/home';
		$permissions = $this->CI->permissions->getPermissions();
		if ($permissions != null) {
			foreach ($permissions as $perm) {
			    switch($perm){
			    case 'view_affiliates' :
			            $affurl = '/affiliate_management/aff_list';
			            $exitLoop = true;
			        break;
			    case 'aff_domain_setting' :
			            $affurl = '/affiliate_management/viewDomain';
			            $exitLoop = true;
			        break;
			    case 'affiliate_earnings' :
			            $affurl = '/affiliate_management/viewAffiliateEarnings';
			            $exitLoop = true;
			        break;
			    case 'affiliate_payments' :
			            $affurl = '/affiliate_management/viewAffiliatePayment';
			            $exitLoop = true;
			        break;
			    case 'banner_settings' :
			            $affurl = '/affiliate_management/viewAffiliateBanner';
			            $exitLoop = true;
			        break;
			    case 'affiliate_tag' :
			            $affurl = '/affiliate_management/viewAffiliateTag';
			            $exitLoop = true;
			        break;
			    case 'affiliate_statistics' :
			        	if($this->CI->utils->isEnabledFeature('switch_old_aff_stats_report_to_new'))
			        		$affurl = '/affiliate_management/affiliate_statistics2?enable_date=true';

			        	else
			            $affurl = '/affiliate_management/affiliate_statistics?enable_date=true';

			            $exitLoop = true;
			        break;
			    case 'affiliate_terms' :
			        	if($this->CI->utils->isEnabledFeature('switch_to_ibetg_commission'))
			            	$affurl = '/affiliate_management/viewAffilliateLevelSetup';

			            else
			            	$affurl = '/affiliate_management/viewTermsSetup';

			            $exitLoop = true;
			        break;
			    case 'affiliate_deposit' :
			            $affurl = '/affiliate_management/affiliate_deposit';
			            $exitLoop = true;
			        break;
			    case 'affiliate_withdraw' :
			            $affurl = '/affiliate_management/affiliate_withdraw';
			            $exitLoop = true;
			        break;
                case 'view_affiliate_login_report':
			            $affurl = '/affiliate_management/viewAffiliateLoginReport';
			            $exitLoop = true;
			        break;
			    default: $affurl = '/home';
			    }
			    if($exitLoop) break;
			}
		}
		return $affurl;
	}

	public function activePlayerSidebar($exitLoop = false){
		$playerurl = '/home';
		$permissions = $this->CI->permissions->getPermissions();
		if ($permissions != null) {
			foreach ($permissions as $perm) {
			    switch($perm){
			    case 'player_list' :
			            $playerurl = '/player_management/viewAllPlayer';
			            $exitLoop = true;
			        break;
			    case 'online_player_list' :
			            $playerurl = '/player_management/viewOnlinePlayerList';
			            $exitLoop = true;
			        break;
			    case 'vip_group_setting' :
			            $playerurl = '/vipsetting_management/vipGroupSettingList';
			            $exitLoop = true;
			        break;
			    case 'tag_player' :
			    	if($this->CI->permissions->checkPermissions('taggedlist')){
			            $playerurl = '/player_management/taggedlist';
			            $exitLoop = true;
			        }
			        break;
			    case 'linked_account' :
			    	if($this->CI->utils->isEnabledFeature('linked_account')){
			            $playerurl = '/player_management/linkedAccount';
			            $exitLoop = true;
			        }
			        break;
			    case 'taggedlist' :
			            $playerurl = '/player_management/playerTagManagement';
			            $exitLoop = true;
			        break;
				case 'iptaglist' :
			            $playerurl = '/player_management/iptaglist';
			            $exitLoop = true;
			        break;
			    case 'manual_subtract_balance_tag' :
			        	$playerurl = '/player_management/ManualSubtractBalanceTagManagement';
			            $exitLoop = true;
			        break;
			    case 'account_process' :
			            $playerurl = '/player_management/accountProcess';
			            $exitLoop = true;
			        break;
			    case 'upload_batch_player' :
			    	if ($this->CI->utils->isEnabledFeature('enabled_batch_upload_player')){
			            $playerurl = '/player_management/accountAutoProcess';
			            $exitLoop = true;
			        }
			        break;
			    case 'friend_referral_player' :
			            $playerurl = '/player_management/friendReferral';
			            $exitLoop = true;
			        break;
			    case 'responsible_gaming_setting' :
			    	if($this->CI->utils->isEnabledFeature('responsible_gaming')){
			            $playerurl = '/player_management/responsibleGamingSetting';
			            $exitLoop = true;
			        }
			        break;
		        default : $playerurl = '/home';
			    }
			    if($exitLoop) break;
			}
		}
		return $playerurl;
	}

	public function activeCSSidebar($exitLoop = false){
		$csurl = '/home';
		$permissions = $this->CI->permissions->getPermissions();
		if ($permissions != null) {
			foreach ($permissions as $perm) {
			    switch($perm){
			    case 'chat' :
			            $csurl = '/cs_management/messages';
			            $exitLoop = true;
			        break;
			    case 'live_chat' :
			    	if($this->CI->utils->isEnabledLiveChat()){
			            $csurl = '/redirect/gotolivechat';
			            $exitLoop = true;
			        }
			        break;
			    case 'player_live_chat_link' :
			    	if($this->CI->utils->isEnabledLiveChat()){
			            $csurl = '/cs_management/livechat_link';
			            $exitLoop = true;
			        }
			        break;
			    case 'view_abnormal_payment_report' :
					if($this->utils->getConfig('enabled_abnormal_payment_notification')){
			            $csurl = '/cs_management/view_abnormal_payment_report';
			            $exitLoop = true;
			        }
			        break;
			    default : $csurl = '/home';
		        	break;
			    }
			    if($exitLoop) break;
			}
		}
		return $csurl;
	}

	public function activePaymentSidebar($exitLoop = false){
		$paymenturl = '/home';
		$permissions = $this->CI->permissions->getPermissions();
		if ($permissions != null) {
			foreach ($permissions as $perm) {
			    switch($perm){
			    case 'deposit_list' :
			            $paymenturl = '/home/nav/deposit_today';
			            $exitLoop = true;
			        break;
			    case 'payment_withdrawal_list' :
			            $paymenturl = '/home/nav/withdrawal/true';
			            $exitLoop = true;
			        break;
			    case 'transfer_request' :
			            $paymenturl = '/payment_management/transfer_request';
			            $exitLoop = true;
			        break;
			    case 'report_transactions' :
			            $paymenturl = '/payment_management/viewtransactionList';
			            $exitLoop = true;
			        break;
			    case 'new_deposit' :
			            $paymenturl = '/payment_management/newDeposit';
			            $exitLoop = true;
			        break;
			    case 'new_internal_withdrawal' :
			            $paymenturl = '/payment_management/newInternalWithdrawal';
			            $exitLoop = true;
			        break;
			    case 'new_withdrawal' :
			            $paymenturl = '/payment_management/newWithdrawal';
			            $exitLoop = true;
			        break;
			    case 'exception_order_list' :
			            $paymenturl = '/payment_management/exception_order_list';
			            $exitLoop = true;
			        break;
			    case 'lock_deposit_list' :
			            $paymenturl = '/payment_management/lockedDepositList';
			            $exitLoop = true;
			        break;
			    case 'lock_withdrawal_list' :
			            $paymenturl = '/payment_management/lockedWithdrawalList';
			            $exitLoop = true;
			        break;
			    case 'batch_deposit' :
			            $paymenturl = '/payment_management/batchDeposit';
			            $exitLoop = true;
			        break;
			    default : $paymenturl = '/home';
		        	break;
			    }
			    if($exitLoop) break;
			}
		}
		return $paymenturl;
	}

	public function activeMarketingSidebar($exitLoop = false){
		$marketingurl = '/home';
		$permissions = $this->CI->permissions->getPermissions();
		if ($permissions != null) {
			foreach ($permissions as $perm) {
			    switch($perm){
			    case 'promo_category_setting' :
			            $marketingurl = '/marketing_management/promoTypeManager';
			            $exitLoop = true;
			        break;
			    case 'promo_rules_setting' :
			            $marketingurl = '/marketing_management/promoRuleManager';
			            $exitLoop = true;
			        break;
			    case 'promocms' :
			            $marketingurl = '/marketing_management/promoSettingList';
			            $exitLoop = true;
			        break;
			    case 'shopping_center_manager' :
			    	if ($this->utils->isEnabledFeature('enable_shop')) {
			            $marketingurl = '/marketing_management/shoppingCenterItemList';
			            $exitLoop = true;
			        }
			        break;
			    case 'shopper_list' :
			    	if ($this->utils->isEnabledFeature('enable_shop')) {
			            $marketingurl = '/marketing_management/shoppingClaimRequestList';
			            $exitLoop = true;
			        }
					break;
				case 'shop_point_expiration' :
			    	if ($this->utils->isEnabledFeature('enable_shop')) {
			            $marketingurl = '/marketing_management/shoppingClaimRequestList';
			            $exitLoop = true;
			        }
			        break;
			    case 'promoapp_list' :
			            $marketingurl = '/marketing_management/promoApplicationList';
			            $exitLoop = true;
			        break;
			    case 'report_gamelogs' :
			            $marketingurl = '/marketing_management/viewGameLogs';
			            $exitLoop = true;
			        break;
			    case 'cashback_setting' :
			    	if(!$this->utils->isEnabledFeature('close_cashback')){
			            $marketingurl = '/marketing_management/cashbackPayoutSetting';
			            $exitLoop = true;
			        }
			        break;
			    case 'friend_referral_setting' :
			            $marketingurl = '/marketing_management/friend_referral_settings';
			            $exitLoop = true;
			        break;
			    case 'batch_adjust_balance' :
			            $marketingurl = '/marketing_management/batchBalanceAdjustment';
			            $exitLoop = true;
			        break;
			    case 'cashback_request' :
			    	if(!$this->utils->isEnabledFeature('close_cashback')){
			            $marketingurl = '/marketing_management/manage_cashback_request';
			            $exitLoop = true;
			        }
			        break;
			    case 'bonus_game_settings' :
			    	if ($this->utils->isEnabledFeature('bonus_games__enable_bonus_game_settings')) {
			            $marketingurl = '/marketing_management/bonusGameSettings';
			            $exitLoop = true;
			        }
			        break;
			    case 'ole777_wager_sync' :
			    	if ($this->utils->isEnabledFeature('ole777_wager_sync')){
			            $marketingurl = '/marketing_management/ole777_wager_sync';
			            $exitLoop = true;
			        }
			        break;
			    default : $marketingurl = '/home';
		        	break;
			    }
			    if($exitLoop) break;
			}
		}
		return $marketingurl;
	}

	public function activeCMSSidebar($exitLoop = false){
		$cmsurl = '/home';
		$permissions = $this->CI->permissions->getPermissions();
		if ($permissions != null) {
			foreach ($permissions as $perm) {
			    switch($perm){
			    case 'view_news_category' :
			            $cmsurl = '/cms_management/viewNewsCategory';
			            $exitLoop = true;
			        break;
			    case 'view_news' :
			            $cmsurl = '/cms_management/viewNews';
			            $exitLoop = true;
			        break;
			    case 'bannercms' :
			            $cmsurl = '/cmsbanner_management/viewBannerManager';
			            $exitLoop = true;
			        break;
			    case 'emailcms' :
			    	if ($this->utils->isEnabledFeature('trigger_deposit_list_send_message'))
			            $cmsurl = '/cms_management/viewMsgtpl';

			            $cmsurl = '/cms_management/viewEmailTemplateManager';
			            $exitLoop = true;
			        break;
			    case 'smtp_setting' :
			            $cmsurl = '/cms_management/smtp_setting';
			            $exitLoop = true;
			        break;
			    case 'staticSites' :
			            $cmsurl = '/cms_management/staticSites';
			            $exitLoop = true;
			        break;
			    case 'player_center_settings' :
			            $cmsurl = '/cms_management/player_center_settings';
			            $exitLoop = true;
			        break;
			    case 'sms_manager' :
			            $cmsurl = '/cms_management/sms_manager_views';
			            $exitLoop = true;
			        break;
			    case 'playercenter_notif_mngmt' :
			    	if ($this->utils->isEnabledFeature('cashier_custom_error_message')){
			            $cmsurl = '/cms_management/notificationManagementSettings';
			            $exitLoop = true;
			        }
			        break;
			    case 'metadata_manager' :
			            $cmsurl = '/cms_management/viewMetaData';
			            $exitLoop = true;
			        break;
                case 'navigation_manager' :
                        $cmsurl = '/cms_management/viewNavigationManager';
                        $exitLoop = true;
                    break;
			    default : $cmsurl = '/home';
		        	break;
			    }
			    if($exitLoop) break;
			}
		}
		return $cmsurl;
	}

	public function activeReportSidebar($exitLoop = false){
		$reporturl = '/home';
		$permissions = $this->CI->permissions->getPermissions();
		if ($permissions != null) {
			foreach ($permissions as $perm) {
				if(false){ #$this->CI->permissions->checkPermissions('summary_report')
					$reporturl = '/report_management/summary_report';
			        $exitLoop = true;
				}
				elseif($this->CI->permissions->checkPermissions('summary_report')){
					$reporturl = '/report_management/summary_report_2';
			        $exitLoop = true;
				}
				else{
				    switch($perm){
				    case 'report_transactions' :
				            $reporturl = '/payment_management/viewTransactionList/report';
				            $exitLoop = true;
				        break;
				    case 'report_transfer_request' :
				            $reporturl = '/payment_management/transfer_request/report';
				            $exitLoop = true;
				        break;
				    case 'report_gamelogs' :
				            $reporturl = '/marketing_management/viewGameLogs/report';
				            $exitLoop = true;
				        break;
				    case 'promotion_report' :
				            $reporturl = '/report_management/viewReport/4';
				            $exitLoop = true;
				        break;
				    case 'player_report' :
				            $reporturl = 'report_management/viewPlayerReport2';
				            $exitLoop = true;
				        break;
				    case 'player_balance_report' :
				            $reporturl = '/report_management/viewReport/5';
				            $exitLoop = true;
				        break;
				    case 'payment_report' :
				            $reporturl = '/report_management/viewPaymentReport?enable_date=true';
				            $exitLoop = true;
				        break;
				    case 'payment_status_history_report' :
				    	if($this->utils->isEnabledFeature('enable_payment_status_history_report')){
				            $reporturl = '/report_management/viewPaymentStatusHistoryReport?enable_date=true';
				            $exitLoop = true;
				        }
				        break;
				    case 'summary_report' :
				            $reporturl = '/report_management/summary_report';
				            $exitLoop = true;
				        break;
				    case 'game_report' :
				            $reporturl = '/report_management/viewGamesReport';
				            $exitLoop = true;
				        break;
				    case 'cashback_report' :
				            $reporturl = '/report_management/cashback_report?enable_date=true';
				            $exitLoop = true;
				        break;
				    case 'duplicate_account_report' :
				            $reporturl = '/report_management/duplicate_account_report';
				            $exitLoop = true;
				        break;
				    case 'sms_report' :
				            $reporturl = '/report_management/viewSmsReport';
				            $exitLoop = true;
				        break;
					case 'view_email_verification_report' :
				            $reporturl = '/report_management/viewEmailVerificationReport';
				            $exitLoop = true;
				        break;
				    case 'active_player_report' :
				            $reporturl = '/report_management/viewActivePlayers';
				            $exitLoop = true;
				        break;
				    case 'daily_player_balance_report' :
				            $reporturl = '/report_management/daily_player_balance_report';
				            $exitLoop = true;
				        break;
				    case 'responsible_gaming_report' :
				    	if($this->utils->isEnabledFeature('responsible_gaming')){
				            $reporturl = '/report_management/responsibleGamingReport';
				            $exitLoop = true;
				        }
				        break;
				    case 'bonus_games_report' :
				            $reporturl = '/report_management/bonusGamesReport';
				            $exitLoop = true;
				        break;
				    case 'player_analysis_report' :
				            $reporturl = '/report_management/player_analysis_report';
				            $exitLoop = true;
				        break;
				    case 'grade_report' :
				            $reporturl = '/report_management/viewGradeReport';
				            $exitLoop = true;
				        break;
				    case 'view_communication_preference_report' :
				    	if ($this->utils->isEnabledFeature('enable_communication_preferences')){
				            $reporturl = '/report_management/viewCommunicationPreferenceReport';
				            $exitLoop = true;
				        }
						break;
					case 'show_player_deposit_withdrawal_achieve_threshold' :
						if ($this->utils->isEnabledFeature('show_player_deposit_withdrawal_achieve_threshold')){
				            $reporturl = '/report_management/view_player_achieve_threshold_report';
				            $exitLoop = true;
				        }
						break;
					case 'view_player_login_report' :
						if ($this->utils->getConfig('enable_player_login_report')){
				            $reporturl = '/report_management/viewPlayerLoginReport';
				            $exitLoop = true;
				        }
						break;
					case 'view_adjustment_score_report' :
						if ($this->utils->getConfig('enabled_player_score')){
				            $reporturl = '/report_management/viewAdjustmentScoreReport';
				            $exitLoop = true;
				        }
						break;
					case 'view_roulette_report' :
						if ($this->utils->getConfig('enabled_roulette_report')){
				            $reporturl = '/report_management/viewRouletteReport';
				            $exitLoop = true;
				        }
						break;
					case 'view_duplicate_contactnumber' :
						if ($this->utils->getConfig('notification_duplicate_contactnumber')){
							$reporturl = '/report_management/viewDuplicateContactNumberReport';
							$exitLoop = true;
						}
						break;
					case 'shopping_center_manager' :
						if ($this->utils->isEnabledFeature('enable_shop')){
				            $reporturl = 'report_management/viewShoppingPointReport';
				            $exitLoop = true;
				        }
				        break;

					case 'attached_file_list' :
						$reporturl = '/report_management/viewAttachedFileList';
						$exitLoop = true;
						break;
					case 'conversion_rate_report' :
						$reporturl = '/report_management/conversion_rate_report';
						$exitLoop = true;
						break;
					case 'transactions_daily_summary_report' :
						$reporturl = '/report_management/transactionsSummaryReport';
						$exitLoop = true;
						break;
					case 'view_and_operate_iovation_report' :
						$reporturl = '/report_management/viewIovationReport';
						$exitLoop = true;
						break;
					case 'view_and_operate_iovation_report' :
						$reporturl = '/report_management/viewIovationReport';
						$exitLoop = true;
						break;

				    case ($perm == 'view_income_access_signup_report' || $perm == 'view_income_access_sales_report') :
				    	if ($this->utils->isEnabledFeature('enable_income_access')){
				            $reporturl = '/report_management/viewIncomeAccessReport';
				            $exitLoop = true;
				        }
				        break;
				    default : $reporturl = '/home';
			        	break;
				    }
				}
			    if($exitLoop) break;
			}
		}
		return $reporturl;
	}

	public function activeSystemSidebar($exitLoop = false){
		$systemurl = '/home';
		$permissions = $this->CI->permissions->getPermissions();
		if ($permissions != null) {
			foreach ($permissions as $perm) {
				if($this->CI->permissions->checkPermissions('view_admin_users')){
					$systemurl = '/user_management/viewUsers';
			        $exitLoop = true;
				}
				else{
				    switch($perm){
				    case 'game_description' :
				            $systemurl = '/game_description/viewGameDescription';
				            $exitLoop = true;
				        break;
				    case 'game_type' :
				            $systemurl = '/game_type/viewGameType';
				            $exitLoop = true;
				        break;
				    case 'payment_api' :
				            $systemurl = '/payment_api/viewPaymentApi';
				            $exitLoop = true;
				        break;
				    case 'ip' :
				            $systemurl = '/ip_management/viewList';
				            $exitLoop = true;
				        break;
				    case 'country_rules' :
				            $systemurl = '/country_rules_management/viewList';
				            $exitLoop = true;
				        break;
				    case ($perm == 'admin_manage_user_roles' || $perm == 'role') :
				            $systemurl = '/role_management/viewRoles';
				            $exitLoop = true;
				        break;
				    case 'currency_setting' :
				            $systemurl = '/user_management/viewCurrency';
				            $exitLoop = true;
				        break;
				    case 'user_logs_report' :
				            $systemurl = '/user_management/viewLogs';
				            $exitLoop = true;
				        break;
				    case 'duplicate_account_setting' :
				            $systemurl = '/user_management/viewDuplicateAccount';
				            $exitLoop = true;
				        break;
				    case 'registration_setting' :
				            $systemurl = '/marketing_management/viewRegistrationSettings';
				            $exitLoop = true;
				        break;
				    case 'notification' :
				            $systemurl = '/notification_management';
				            $exitLoop = true;
				        break;
				    case 'system_features' :
				            $systemurl = '/system_management/system_features';
				            $exitLoop = true;
				        break;
				    case 'bank/3rd_payment_list' :
				            $systemurl = '/payment_management/bank3rdPaymentList';
				            $exitLoop = true;
				        break;
				    case 'collection_account' :
				            $systemurl = '/payment_account_management/view_payment_account';
				            $exitLoop = true;
				        break;
                    case 'view_previous_balances_checking_setting' :
                    case 'edit_previous_balances_checking_setting' :
				            $systemurl = '/payment_management/previousBalanceSetting';
				            $exitLoop = true;
				        break;
				    case 'nonpromo_withdraw_setting' :
				            $systemurl = '/payment_management/nonPromoWithdrawSetting';
				            $exitLoop = true;
				        break;
				    case 'default_collection_account' :
				            $systemurl = '/payment_management/defaultCollectionAccount';
				            $exitLoop = true;
				        break;
				    case 'withdrawal_workflow' :
				            $systemurl = '/payment_management/customWithdrawalProcessingStageSetting';
				            $exitLoop = true;
				        break;
				    case 'payment_setting' :
				            $systemurl = '/payment_management/depositCountSetting';
				            $exitLoop = true;
				        break;
				    case 'dev_functions' :
				    	if($this->CI->users->isT1User($this->CI->authentication->getUsername())){
				            $systemurl = '/system_management/other_functions';
				            $exitLoop = true;
				        }
				        break;
				    case 'view_task_list' :
				    	if($this->CI->users->isT1User($this->CI->authentication->getUsername())){
				            $systemurl = '/system_management/view_task_list';
				            $exitLoop = true;
				        }
				        break;
				    case 'view_resp_result' :
				    	if($this->CI->users->isT1User($this->CI->authentication->getUsername())){
				            $systemurl = '/system_management/view_resp_result';
				            $exitLoop = true;
				        }
				        break;
				    case 'view_sms_api_settings' :
						if (!empty($this->getConfig('use_new_sms_api_setting'))) {
							$systemurl = '/system_management/view_sms_api_settings';
				            $exitLoop = true;
						}
				        break;
				    case 'view_sms_report' :
				            $systemurl = '/system_management/view_sms_report';
				            $exitLoop = true;
				        break;
				    case 'view_smtp_api_report' :
				            $systemurl = '/system_management/view_smtp_api_report';
				            $exitLoop = true;
				        break;
				    case 'view_withdrawal_declined_category' :
				            $systemurl = '/system_management/view_withdrawal_declined_category';
				            $exitLoop = true;
				        break;
				    case 'view_adjustment_category' :
				            $systemurl = '/system_management/view_adjustment_category';
				            $exitLoop = true;
				        break;
				    case 'game_wallet_settings' :
				            $systemurl = '/system_management/game_wallet_settings';
				            $exitLoop = true;
				        break;
				    case 'lottery_bo_role_binding' :
				            $systemurl = '/user_management/lottery_bo_role_binding';
				            $exitLoop = true;
				        break;
				    case 'game_api' :
				            $systemurl = '/game_api/viewGameApi';
				            $exitLoop = true;
				        break;
				    case 'game_maintenance_schedule' :
				            $systemurl = '/game_api/viewGameMaintenanceSchedule';
				            $exitLoop = true;
				        break;
				    case 'risk_score_settings' :
				    	if($this->utils->isEnabledFeature('show_risk_score')){
				            $systemurl = '/system_management/risk_score_setting';
				            $exitLoop = true;
				        }
				        break;
			         case 'kyc_settings' :
				    	if($this->utils->isEnabledFeature('show_kyc_status')){
				            $systemurl = '/system_management/kyc_setting';
				            $exitLoop = true;
				        }
				        break;
				    default : $systemurl = '/home';
			        	break;
				    }
				}
			    if($exitLoop) break;
			}
		}
		return $systemurl;
	}

	public function activeAgencySidebar($exitLoop = false){
		$agencyurl = '/home';
		$permissions = $this->CI->permissions->getPermissions();
		if ($permissions != null) {
			foreach ($permissions as $perm) {
			    switch($perm){
			    case 'structure_list' :
			            $agencyurl = '/agency_management/structure_list';
			            $exitLoop = true;
			        break;
			    case 'agent_list' :
			            $agencyurl = '/agency_management/agent_list';
			            $exitLoop = true;
			        break;
			    case 'credit_transactions' :
			    	if (!$this->utils->isEnabledFeature('agent_settlement_to_wallet')) {
			            $agencyurl = '/agency_management/credit_transactions';
			            $exitLoop = true;
			        }
			        break;
			    case 'agency_payment' :
			            $agencyurl = '/agency_management/agency_payment';
			            $exitLoop = true;
			        break;
			    case 'tier_comm_patterns' :
			    	if ($this->utils->isEnabledFeature('agent_tier_comm_pattern')) {
			            $agencyurl = '/agency_management/tier_comm_patterns';
			            $exitLoop = true;
			        }
			        break;
			    case 'agency_logs' :
			            $agencyurl = '/agency_management/agency_logs';
			            $exitLoop = true;
			        break;
			    case 'agent_domain_list' :
			            $agencyurl = '/agency_management/agent_domain_list';
			            $exitLoop = true;
			        break;
			    default : $agencyurl = '/home';
		        	break;
			    }
			    if($exitLoop) break;
			}
		}
		return $agencyurl;
	}

	public function activeCustomReportSidebar($exitLoop = false){
		$customReportUrl = '/home';
		$enabledReport = $this->getConfig('custom_report_tab_sidebar_item');
		if ($enabledReport != null) {
			foreach ($enabledReport as $report) {
			    switch($report){
			    case 'player_additional_roulette_report' :
			            $customReportUrl = '/report_custom_additional_management/viewPlayerAdditionalRouletteReport';
			            $exitLoop = true;
			        break;
                case 'player_additional_report' :
                        $customReportUrl = '/report_custom_additional_management/viewPlayerAdditionalReport';
                        $exitLoop = true;
                    break;
			    default : $customReportUrl = '/home';
		        	break;
			    }
			    if($exitLoop) break;
			}
		}
		return $customReportUrl;
	}

	public function activeTabs(){
		$playerUrl = $this->activePlayerSidebar();
		$csUrl = $this->activeCSSidebar();
		$paymentUrl = $this->activePaymentSidebar();
		$marketingUrl = $this->activeMarketingSidebar();
		$cmsUrl = $this->activeCMSSidebar();
		$affUrl = $this->activeAffiliateSidebar();
		$reportUrl = $this->activeReportSidebar();
		$systemUrl = $this->activeSystemSidebar();
		$agencyUrl = $this->activeAgencySidebar();
		$customReportUrl = $this->activeCustomReportSidebar();

		if($playerUrl != '/home')
			redirect($playerUrl);

		elseif($csUrl != '/home')
			redirect($csUrl);

		elseif($paymentUrl != '/home')
			redirect($paymentUrl);

		elseif($marketingUrl != '/home')
			redirect($marketingUrl);

		elseif($cmsUrl != '/home')
			redirect($cmsUrl);

		elseif($affUrl != '/home')
			redirect($affUrl);

		elseif($reportUrl != '/home')
			redirect($reportUrl);

		elseif($systemUrl != '/home')
			redirect($systemUrl);

		elseif($agencyUrl != '/home')
			redirect($agencyUrl);

		elseif($customReportUrl != '/home')
			redirect($customReportUrl);
		else
			redirect('/theme_management');
	}

	/*
     * retrieves datetime ranges based on given interval
     *
     * @param  string $first  Start of datetime range
     * @param  string $last   End of datetime range
     * @param  string $step   Interval
     * @param  string $format Date format
     * @return array
     */
    public function dateTimeRangePeriods($first, $last, $step = '+1 hour', $format = 'Y-m-d H:i:s') {

        $dates = array();

        $current = date($format, strtotime($first));
        $last = date($format, strtotime($last));

        $maxTimes = 99999999;
        $runTimes = 0;
        // -- loop until current date meets the end date
        while ($current < $last) {
            if($maxTimes < $runTimes){
                $this->utils->error_log('Exceeded maximum number of executions, please check current:', $current, 'last:', $last);
                break;
            }
            $tmp_from = $current;

            // -- get next datetime based on interval provided
            $tmp_to = date($format, strtotime($tmp_from . ' ' .$step));

            // -- If next interval exceeds the limit, use the limit.
            $final_to = $tmp_to <= $last ? $tmp_to : $last;


            $dates[] = array(
                'from'     => $tmp_from,
                'to'    => $final_to
            );

            // -- Set new current date
            $current = date($format, strtotime($final_to));
            $runTimes++;
        } // EOF while ($current < $last) {...

        return $dates;
    }

    public function getRemoteReportPath(){
    	$path=realpath(dirname(__FILE__) . '/../../public/reports');
		$this->utils->addSuffixOnMDB($path);

    	return $path;
    }

    public function getRemoteReportDownloadPath(){
    	$path='/reports';
		$this->utils->addSuffixOnMDB($path);

    	return $path;
    }

    /**
     * send alert back to admin
     * @param  string $level   'warning' or 'error'
     * @param  string $message
     * @return boolean $success
     */
    public function sendAlertBack($level, $title, $message){
    	//add prefix
    	$title=$this->getAppPrefix().': '.$title;

    	$success=$this->sendMessageToMattermost($level, $title, $message);

    	if($level=='error'){
    		//send it to pushover
    		$success=$this->sendErrorAlertToPushover($title, $message);
    	}

    	return $success;
    }

    /**
     * send message to pushover
     * @param  string $title
     * @param  string $message
     * @return boolean $success
     */
    public function sendErrorAlertToPushover($title, $message){
        $success=false;
    	//pushover url
    	$url=$this->getConfig('pushover_send_api_url');
    	$pushover_default_priority=$this->getConfig('pushover_default_priority');
    	$pushover_user=$this->getConfig('pushover_user');
    	$pushover_token=$this->getConfig('pushover_token');
    	if(empty($pushover_user) || empty($pushover_token)){
    		$success=false;
    		$this->error_log('empty pushover user or token', $pushover_user, $pushover_token);
    		return $success;
    	}
    	$method='POST';
    	$params=[
    		'title'=>$title,
    		'message'=>$message,
    		'token'=>$pushover_token,
    		'user'=>$pushover_user,
    		'priority'=>$pushover_default_priority,
    	];
    	list($header, $content, $statusCode, $statusText, $errCode, $error, $obj)=
    		$this->callHttp($url, $method, $params);

        if($errCode!=0 || $statusCode>=400){
            $this->error_log('error code', $errCode, $error, $statusCode, $header, $content);
            $success=false;
        }else{
            $this->debug_log('return result', $content, $errCode, $error, $statusCode, $header);
            $success=true;
        }

        return $success;
    }
    /**
     * sendMessageToMattermost
     * @param  string $level   'warning' or 'error'
     * @param  string $title
     * @param  string $message
     * @return boolean
     */
    public function sendMessageToMattermost($level, $title, $message, $pretext=null){
    	$username='system alert';
    	$channel='system_alert';
	    $this->CI->load->helper('mattermost_notification_helper');
	    if($level=='error'){
	    	$level='danger';
	    }
        $messages = [
            [
                'text' => $message,
                'type' => $level,
                'title' => $title,
                'pretext' => $pretext,
            ]
        ];

	    return sendNotificationToMattermost($username, $channel, $messages);
    }

	public function loadOTPApi(){

		// $this->debug_log('APPPATH:'.APPPATH);

		$external_otp_api_class=$this->getConfig('external_otp_api_class');
		if(!empty($external_otp_api_class) && file_exists(dirname(__FILE__).'/otp_api/'.$external_otp_api_class.'.php')){

			$this->CI->load->library('otp_api/'.$external_otp_api_class);
			$api=$this->CI->$external_otp_api_class;
			if(!empty($api)){
				return $api;
			}
		}

		return null;
	}

    public function isActiveCurrency($defaultCurrency){
        if($this->isEnabledMDB()){
        	$success=true;
            //if currency is not same with current currency
            if(!empty($defaultCurrency)){
                $this->debug_log('defaultCurrency', $defaultCurrency, 'getActiveCurrencyKeyOnMDB',
                    $this->getActiveCurrencyKeyOnMDB());
                $success=strtolower($defaultCurrency)==strtolower($this->getActiveCurrencyKeyOnMDB());
            }
            return $success;
        }else{
        	//it's not mdb , so always true
        	return true;
        }
    }

    public function isCurrencyDomain(){
    	$isCurrencyDomain=false;
		if($this->isEnabledMDB()){
		    $_multiple_db=Multiple_db::getSingletonInstance();
		    $domain_target_db=$_multiple_db->checkCurrencyDomain();
		    if(!empty($domain_target_db)){
		        $isCurrencyDomain=true;
		    }
		}
		return $isCurrencyDomain;
    }

    public function createTempDirPath(){
		//app log
		$tmpDir=BASEPATH.'/../application/logs/tmp_shell';
		if(!file_exists($tmpDir)){
			@mkdir($tmpDir, 0777 , true);
		}

		$tmpDir=realpath($tmpDir);
		if(empty($tmpDir) || $tmpDir=='/'){
			$tmpDir='/tmp/'.$this->getAppPrefix();
		}

		return $tmpDir;
    }

    public function createTempFileName(){
		$uniqueid=time().'-'.random_string('md5');
		return $uniqueid;
    }

    public function createTempFile(){
		$uniqueid=$this->createTempFileName();
		$tmpDir=$this->createTempDirPath();
		return $tmpDir.'/'.$uniqueid;
	}

    public function checkAndFormatDateTime($str){
    	$result=null;
    	if(!empty($str)){
	    	try{
		       	// $this->debug_log('checkAndFormatDateTime', $str);
		        $d=new DateTime($str);
		       	// $this->debug_log('checkAndFormatDateTime new DateTime', $d);
		        $result=$this->formatDateTimeForMysql($d);
		       	// $this->debug_log('checkAndFormatDateTime after format', $str);
	    	}catch(Exception $e){
	    		$result=null;
	    		// $this->error_log('format failed', $e);
	    	}
    	}

        return $result;
    }

    public function checkAndFormatDate($str){
    	$result=null;
    	if(!empty($str)){
	    	try{
		       	// $this->debug_log('checkAndFormatDateTime', $str);
		        $d=new DateTime($str);
		       	// $this->debug_log('checkAndFormatDateTime new DateTime', $d);
		        $result=$this->formatDateForMysql($d);
		       	// $this->debug_log('checkAndFormatDateTime after format', $str);
	    	}catch(Exception $e){
	    		$result=null;
	    		// $this->error_log('format failed', $e);
	    	}
    	}

        return $result;
    }

    /**
     * extract lang json format
     * _json: {}
     * @param  string $langJson
     * @return array
     */
    public function extractLangJson($langJson){
    	$this->CI->load->library(['language_function']);
    	$result=[];
		if(substr($langJson, 0, 6) === '_json:'){
			$jsonStr = substr($langJson, 6);
			$jsonArr = json_decode($jsonStr, true);
			if(!empty($jsonArr) && json_last_error() == JSON_ERROR_NONE) {
				$langMap=Language_function::ISO2_LANG;
				foreach ($jsonArr as $key => $value) {
					if(array_key_exists($key, $langMap)){
						$result[$langMap[$key]]=$value;
					}
				}
			}
		}else{
			$result[Language_function::ISO2_LANG[Language_function::INT_LANG_ENGLISH]]=$langJson;
		}

		return $result;
    }

	public function replaceKeyToIsoLang($langJson){
		$this->CI->load->library(['language_function']);
		$result=[];
		$jsonStr = substr($langJson , strpos($langJson , '_json:') + 6 );
		$jsonArr = json_decode($jsonStr, true);
		if (!empty($jsonArr) && json_last_error() == JSON_ERROR_NONE) {
			$langMap = Language_function::ISO_LANG_COUNTRY;
			foreach ($jsonArr as $key => $value) {
				if (array_key_exists($key, $langMap)) {
					$result[$langMap[$key]] = $value;
				}
			}
		}
		return $result;
	}

    public function isRebuildReportDateLocked($lock_rebuild_reports_range,$from,$to,&$rlt,$getCutOffDay=false){
    	$delete_greater_than = $lock_rebuild_reports_range['no'];
    	$start_day = null;
    	$max_day = null;
    	switch ($lock_rebuild_reports_range['time_unit'] ) {
    		case 'days':
    		$last_n_days =  date("Y-m-d", strtotime('-'.$delete_greater_than.'  days'));
            $max_day = date("Y-m-d", strtotime($last_n_days.' -1 day'));//subtract 1 day
            $start_day = date("Y-m-d", strtotime($max_day.'-'.$delete_greater_than.' days'));
            break;

            default://months
            $last_n_months =  date("Y-m-d", strtotime('-'.$delete_greater_than.'  months'));
            $max_day = date("Y-m-d", strtotime($last_n_months.' -1 day'));//subtract 1 day
            $start_day = date("Y-m-d", strtotime($max_day.'-'.$delete_greater_than.' months'));
            break;
        }

        if($getCutOffDay===true){
        	return array('cutoff_day'=>$max_day);
        }
        if($from <= $max_day || $to <= $max_day ){
        	$rlt=array('is_locked'=>true, 'cutoff_day'=>$max_day);
        	return true;
        }
        $rlt=array('is_locked'=>false, 'cutoff_day'=>$max_day);
        return false;
    }

    /**
     * Translate payment_account.second_category_flag to language text
     * Built in OGP-13250
     * @param	int		$second_category_flag	== payment_account.second_category_flag
     * @return	string
     * Note: if second_category_flag is not mapped, returns lang("pay.second_category_bank_transfer") by default
     */
    public function second_category_flag_to_text($second_category_flag, $default_text = null) {
    	$cat2_texts = [
			SECOND_CATEGORY_ONLINE_BANK		=> lang("pay.second_category_online_bank") ,
            SECOND_CATEGORY_ALIPAY			=> lang("pay.second_category_alipay") ,
            SECOND_CATEGORY_WEIXIN			=> lang("pay.second_category_weixin") ,
            SECOND_CATEGORY_QQPAY			=> lang("pay.second_category_qqpay") ,
            SECOND_CATEGORY_UNIONPAY		=> lang("pay.second_category_unionpay") ,
            SECOND_CATEGORY_QUICKPAY		=> lang("pay.second_category_quickpay") ,
            SECOND_CATEGORY_PIXPAY			=> lang("pay.second_category_pixpay") ,
            SECOND_CATEGORY_BANK_TRANSFER	=> lang("pay.second_category_bank_transfer") ,
            SECOND_CATEGORY_ATM_TRANSFER	=> lang("pay.second_category_atm_transfer") ,
            SECOND_CATEGORY_CRYPTOCURRENCY	=> lang("pay.second_category_cryptocurrency") ,
            '_DEFAULT'						=> lang("pay.second_category_bank_transfer") ,
    	];

    	if (isset($cat2_texts[$second_category_flag])) {
    		return $cat2_texts[$second_category_flag];
    	}
    	else {
    		return empty($default_text) ? $cat2_texts['_DEFAULT'] : $default_text;
    	}
    }

    const ENCRYPT_METHOD='aes-256-ctr';
    const ENCRYPT_TAG_LENGTH=16;

	public function encryptPassword($password, &$error=null) {
		if (!empty($password)) {
			$key=$this->getConfig('COMMON_KEY_FOR_PASSWORD');
	        if(mb_strlen($key, '8bit') !== 32) {
	        	$error='wrong key';
	        	return false;
	        }
			$ivLength = openssl_cipher_iv_length(self::ENCRYPT_METHOD);
			$iv = openssl_random_pseudo_bytes($ivLength, $isStrong);
			if (false === $iv && false === $isStrong) {
			    //error
				$error='encrypt failed';
				return false;
			}
			$ciphertext = openssl_encrypt($password, self::ENCRYPT_METHOD, $key, OPENSSL_RAW_DATA, $iv);
			return base64_encode($iv.$ciphertext);
		}
		return false;
	}

	public function decryptPassword($password, &$error=null) {
		if (!empty($password)) {
			$key=$this->getConfig('COMMON_KEY_FOR_PASSWORD');
	        if(mb_strlen($key, '8bit') !== 32) {
				$error='wrong key';
	        	return false;
			}
			//decode base64
			$password=base64_decode($password);
			$ivLength = openssl_cipher_iv_length(self::ENCRYPT_METHOD);
			$iv = mb_substr($password, 0, $ivLength, '8bit');
	        $ciphertext = mb_substr($password, $ivLength, null, '8bit');
			return openssl_decrypt($ciphertext, self::ENCRYPT_METHOD, $key, OPENSSL_RAW_DATA, $iv);
		}
		return false;
	}

	public function safeRandomString($length=16){
		return bin2hex(openssl_random_pseudo_bytes($length));
	}


	public function json_decode_handleErr($jsonStr, $assoc = false){
		$decoded = json_decode($jsonStr, $assoc);
		$jsonErr = json_last_error(); // for previous line, $_log->write_log($level, $msg, $args);
		if ( $jsonErr !== JSON_ERROR_NONE ) {
			$level = 'error';
			$jsonLastError = $this->handleJsonLastError( $jsonErr );
			$msg = $jsonStr;
			$msg .= ' !!! '. $jsonErr;
			$_log = &load_class('Log');
			$_log->write_log($level, $msg, $jsonLastError);
			// clear for Allowed memory size exhausted
			$jsonStr = null;
			$msg = null;
			$decoded = null;
		}else{
			$jsonStr = null;
		}
		return $decoded ;
	}

	/**
	 * Readable return of json_last_error() after json_decode().
	 * @param integer $jsonLastError The return of json_last_error().
	 * @return string JSON_ERROR_XXX intro Text.
	 */
	public function handleJsonLastError($jsonLastError = NULL){

		if($jsonLastError === NULL){
			$jsonLastError = json_last_error();
		}
		$echo = 'DEFAULT';
		switch ($jsonLastError) {
			case JSON_ERROR_NONE:
			$echo = ' - No errors';
			break;
			case JSON_ERROR_DEPTH:
			$echo = ' - Maximum stack depth exceeded';
			break;
			case JSON_ERROR_STATE_MISMATCH:
			$echo = ' - Underflow or the modes mismatch';
			break;
			case JSON_ERROR_CTRL_CHAR:
			$echo = ' - Unexpected control character found';
			break;
			case JSON_ERROR_SYNTAX:
			$echo = ' - Syntax error, malformed JSON';
			break;
			case JSON_ERROR_UTF8:
			$echo = ' - Malformed UTF-8 characters, possibly incorrectly encoded';
			break;
			default:
			$echo = ' - Unknown error';
			break;
		}
		return $echo;
	}

	public function setConfig($key, $val) {
		return $this->CI->config->set_item($key, $val);
	}

	private $redis_of_id_generator=null;

	public function getRedisServerForIdGenerator($db=self::REDIS_DB_FOR_ID_GENERATOR){
        if (empty($this->redis_of_id_generator)) {
        	//try load redis from lock_servers
        	$default_redis_for_id_generator=$this->getConfig('default_redis_for_id_generator');
            if(!empty($default_redis_for_id_generator)){
	            $redis = new \Redis();
	            try{
	            	$redis->connect($default_redis_for_id_generator['host'], $default_redis_for_id_generator['port'], $default_redis_for_id_generator['timeout']);
	            }catch(Exception $e){
	            	$this->error_log('cannot connect to redis', $e, $host, $port);
	            	return null;
	            }

	            $this->redis_of_id_generator = $redis;
            }
        }
        //try select db
        if(!empty($this->redis_of_id_generator)){
	        try{
	        	$this->redis_of_id_generator->select($db);
	        }catch(Exception $e){
	        	$this->error_log('cannot select db in redis', $e);
	        	$this->redis_of_id_generator=null;
	        	return null;
	        }
	    }

		return $this->redis_of_id_generator;
	}

	/**
	 * generateUniqueId on global redis server
	 * @param  string $key
	 * @param  int $maxId unsigned 32-bit int
	 * @param  int $initId
	 * @return int $id
	 */
	public function generateUniqueIdFromRedis($key, $maxId=4294967295, $initId=1){
		$redis=$this->getRedisServerForIdGenerator(self::REDIS_DB_FOR_ID_GENERATOR);
		if(empty($redis)){
			$this->error_log('cannot generate unique id, no redis server');
			return null;
		}

		$fullKey=$this->addAppPrefixForKey($key);
		try{
			$id=$redis->incr($fullKey);
			if($id<=0){
				//no 0
				$id=$redis->incr($fullKey);
			}else if($id>=$maxId){
				//reset if more than max id
				$redis->set($fullKey, $initId);
				$id=$initId;
			}
		}catch(Exception $e){
			$this->error_log('generate id failed', $e);
			return null;
		}

		return $id;
	}

	/**
	 * resetUniqueId  on global redis server
	 * @param  string  $key
	 * @param  integer $initId
	 * @return boolean
	 */
	public function resetUniqueIdOnRedis($key, $initId=1){
		$redis=$this->getRedisServerForIdGenerator(self::REDIS_DB_FOR_ID_GENERATOR);
		if(empty($redis)){
			$this->error_log('cannot reset unique id, no redis server');
			return false;
		}
		$success=false;
		$fullKey=$this->addAppPrefixForKey($key);
		try{
			$success=$redis->set($fullKey, $initId);
		}catch(Exception $e){
			$this->error_log('generate id failed', $e);
			return false;
		}

		return $success;
	}

	public function printToConsole($msg, $isError=false){
		$this->initCliOutput();
		if($isError){
			$this->climate->bold()->blink()->backgroundRed($msg);
		}else{
			$this->climate->lightGreen($msg);
		}
	}

	public function isOptionsRequest(){
		if(isset($_SERVER['REQUEST_METHOD'])){
			$method=strtoupper($_SERVER['REQUEST_METHOD']);
			return $method=='OPTIONS';
		}

		return false;
	}

    /**
	 * adjust domain for www site
	 * if referer is www and domain is player, change player to www
	 *
	 * @param string $domain
	 * @return
	 */
	public function adjustDomainForWWW($domain){
		$result=$domain;
		if($this->startsWith($domain, 'player.')){
			//get domain from referer
			$refererDomain=$this->getHttpRefererDomain();
			$isWWWOrM=$this->startsWith($refererDomain, 'www.') || $this->startsWith($refererDomain, 'm.');
			if(!empty($refererDomain) && $isWWWOrM){
				//replace it
				$result=$refererDomain;
			}
		}

		return $result;
	}

    public function getHttpRefererDomain(){
		$domain=null;
		$referer=$this->getHttpReferer();
		if(!empty($referer)){
			$domain=parse_url($referer, PHP_URL_HOST);
		}
		return $domain;
	}

	public function getHttpRefererUri(){
		$uri=null;
		$referer=$this->getHttpReferer();
		if(!empty($referer)){
			$uri=parse_url($referer, PHP_URL_PATH);
		}
		return $uri;
	}

	public function stripHTMLtags($str, $remove_html=true, $doHtmlentities=false)
	{
		if(isset($str)) {
			if($remove_html) {
				$str = preg_replace('/<[^<|>]+?>/', '', htmlspecialchars_decode($str));
			}
			if ($doHtmlentities) {
				$str = htmlentities($str, ENT_QUOTES, "UTF-8");
			}
		}
		return $str;
	}

	/**
	 * Strip html tags of array
	 *
	 * @access	public
	 * @param	array $data
	 * @return	array
	 */
	public function stripHtmlTagsOfArray($data){
		return is_array($data) ? array_map(array($this,'stripHtmlTagsOfArray'), $data) : $this->stripHTMLtags($data);
	}

	public function convertEndingCharInFile($file_path){

		$str=file_get_contents($file_path);

		if(!empty($str)){

			$str=str_replace(["\n\r", "\r\n"], "\n", $str);

			file_put_contents($file_path, $str);
		}
	}

	/**
	 * copyFileToSharingPrivate
	 *
	 * @param  string $file_path
	 * @param  string $target_file_path
	 * @param  int $charset_code      1=gbk, 2=utf-8
	 * @return bool copy result
	 */
	public function copyFileToSharingPrivate($file_path, &$target_file_path, $charset_code){
		//copy file
		$sharingPrivatePath=$this->getConfig('SHARING_PRIVATE_PATH');
		$target_file_path=$sharingPrivatePath.'/'.random_string('md5');

		$this->convertEndingCharInFile($file_path);

		if($charset_code==2){
			//$sharingPrivatePath
			return copy($file_path, $target_file_path);
		}else{
			return file_put_contents($target_file_path, iconv('GBK', 'UTF-8//IGNORE', file_get_contents($file_path)))!==false;
		}
	}

	/**
	 * isResourceInsideLock
	 * @param  string $anyId
	 * @param  string $action
	 * @return boolean
	 */
	public function isResourceInsideLock($anyId, $action){
		$success=false;
		//found locked key
		$resource = $this->createLockKey([$anyId, $action]);
		if(!empty($this->last_locked_key[$resource])){
			$last_locked_key = $this->last_locked_key[$resource];
			if($last_locked_key['add_prefix']){
				$resource=$this->getAppPrefix().'-'.$resource;
			}
			//same key or not
			$success=$last_locked_key['resource']===$resource;
			if($success){
				$this->debug_log('found last_locked_key', $success, $anyId, $action, $resource, $this->last_locked_key);
			} else {
				$this->error_log('last_locked_key not match', $success, $anyId, $action, $resource, $this->last_locked_key);
			}
		}else{
			$this->error_log('not found last_locked_key',$success, $anyId, $action, $resource, $this->last_locked_key);
		}
		return $success;
	}

	public function isValidJson($data){
		if (!empty($data)) {
			@json_decode($data);
			return (json_last_error() === JSON_ERROR_NONE);
		}
		return false;
	}

	public function getExecutionTimeToNow(){
		global $BM;

		$elapsed = $BM->elapsed_time('total_execution_time_start', 'total_execution_time_end');
		//reset end
		unset($BM->marker['total_execution_time_end']);
		// $this->debug_log('total_execution_time_start', $BM->marker['total_execution_time_start'], @$BM->marker['total_execution_time_end']);
		return $elapsed;
	}

	/**
     * Check content if a valid xml
     * @param string content
     *
     * @return bolean
    */
    public function isValidXml($content)
    {
        #check if empty
        $content = trim($content);
        if (empty($content)) {
            return false;
        }

        #check if html
        if (stripos($content, '<!DOCTYPE html>') !== false) {
            return false;
        }

        libxml_use_internal_errors(true);
        simplexml_load_string($content);
        $errors = libxml_get_errors();
        libxml_clear_errors();

        return empty($errors);
	}

	function generateCallTrace(){ // @todo remove before go live.
        $e = new Exception();
        $trace = explode("\n", $e->getTraceAsString());
        // reverse array to make steps line up chronologically
        $trace = array_reverse($trace);
        array_shift($trace); // remove {main}
        array_pop($trace); // remove call to this method
        $length = count($trace);
        $result = array();

        for ($i = 0; $i < $length; $i++)
        {
            $result[] = ($i + 1)  . ')' . substr($trace[$i], strpos($trace[$i], ' ')); // replace '#someNum' with '$i)', set the right ordering
        }

        return "\t" . implode("\n\t", $result);
    }

    public function get_hour_minute_elapsed_time($timestamp1,$timestamp2=null){

        //$diff = 1580259547 - 1580255827;
    	if(empty($timestamp2)){
    		$timestamp2 = time();
    	}
    	$diff = $timestamp2 - $timestamp1;
    	$tmins = $diff/60;
    	$hours = floor($tmins/60);
    	$minutes = $tmins%60;
    	return [$hours,$minutes];

    }

    /**
     * seconds Convert To Time
     * ref. to https://stackoverflow.com/a/43956977
     * @param integer $inputSeconds
     * @param array $sections for collection the sections of elapsed time.
     * @return string the sentence of the elapsed time.
     */
    public function secondsToTime($inputSeconds, &$sections = null) {
        $secondsInAMinute = 60;
        $secondsInAnHour = 60 * $secondsInAMinute;
        $secondsInADay = 24 * $secondsInAnHour;

        // Extract days
        $days = floor($inputSeconds / $secondsInADay);

        // Extract hours
        $hourSeconds = $inputSeconds % $secondsInADay;
        $hours = floor($hourSeconds / $secondsInAnHour);

        // Extract minutes
        $minuteSeconds = $hourSeconds % $secondsInAnHour;
        $minutes = floor($minuteSeconds / $secondsInAMinute);

        // Extract the remaining seconds
        $remainingSeconds = $minuteSeconds % $secondsInAMinute;
        $seconds = ceil($remainingSeconds);

        // Format and return
        $timeParts = [];
        $sections = [
            'day' => (int)$days,
            'hour' => (int)$hours,
            'minute' => (int)$minutes,
            'second' => (int)$seconds,
        ];

        foreach ($sections as $name => $value){
            if ($value > 0){
                $timeParts[] = $value. ' '.$name.($value == 1 ? '' : 's');
            }
        }

        return implode(', ', $timeParts);
    }

    /**
     * Redirects to login page, retaining current path in GET variable redirect
     * @param	boolean	$from_timeout	True if called from a login timeout event.  Will append a
     									GET variable 'timeout_resume' (defined in config) to specific
     									this redirect is from a timeout event.
     * @see		Permissions::checkSettings()
     * @return
     */
    public function redirectToLogin($from_timeout = true) {
        $return_path = current_url();
        if($return_path != '/') {
        	if ($from_timeout) {
	        	$timeout_resume = $this->getConfig('get_var_resuming_from_token_timeout');
	            return redirect("auth/login?{$timeout_resume}=1&redirect=" . urlencode($return_path));
	        }
	        else {
	        	return redirect("auth/login?redirect=" . urlencode($return_path));
	        }
        }

        return redirect("auth/login");
    }

	/**
	 * overview: check if force admin enabled 2FA and if admin has enabled 2FA
	 * @return array [$force2FaForAllAdmin, $adminEnabled2Fa, $forceRedirect]
	 */
	public function checkIfAdminForceEnabled2FA(){
		$this->CI->load->model(['users']);
		$force2FaForAllAdmin = $this->getConfig('force_2fa_for_all_adminusers');
		$username = $this->CI->authentication->getUsername();
		$adminEnabled2Fa = $this->CI->users->isEnabledOTPByUsername($username);
		$forceRedirect = false;

		if(!$force2FaForAllAdmin){
			return [$force2FaForAllAdmin, $adminEnabled2Fa, $forceRedirect];
		}

		$isT1Admin = $this->CI->users->isT1Admin($username);
		if($adminEnabled2Fa || $isT1Admin){
			return [$force2FaForAllAdmin, TRUE, $forceRedirect];
		}

		$url = $this->getUrl(true);
		$parseUrl = parse_url($url, PHP_URL_PATH);
		$trimPath = trim($parseUrl, '/');
		$allowedPath = ['user_management/otp_settings', 'user_management/init_otp_secret', 'user_management/validate_and_enable_otp'];

		if(!in_array($trimPath, $allowedPath)){
			$forceRedirect = true;
			return [$force2FaForAllAdmin, $adminEnabled2Fa, $forceRedirect];
		}

		return [$force2FaForAllAdmin, $adminEnabled2Fa, $forceRedirect];
	}

	public function redirectOtpSettings(){
		return redirect("user_management/otp_settings");
	}

    /**
     * Generate random color
     *
     * @return array
    */
    public function generateRandomColor(){
	    $result = array('rgb' => '', 'hex' => '#');
	    foreach(array('r', 'b', 'g') as $col){
	        $rand = mt_rand(0, 255);
	        $result['rgb'][$col] = $rand;
	        $dechex = dechex($rand);
	        if(strlen($dechex) < 2){
	            $dechex = '0' . $dechex;
	        }
	        $result['hex'] .= $dechex;
	    }
	    return $result;
	}

	public function truncateAmountDecimal($amount, $decimals = 2){
		return round($amount, $decimals);
	}

	public function loopDateTimeStartEndDaily($start, $end, $callback) {
		if (is_string($start)) {
			$start = new \DateTime($start);
		}

		if (is_string($end)) {
			$end = new \DateTime($end);
		}

		$now=new DateTime();
		if($end > $now){
			$end = $now;
		}


		$success = false;
		$startDate = clone $start;
		while ($startDate < $end) {
			$endDate = new DateTime($startDate->format('Y-m-d').' 23:59:59');

			if($endDate>$end){
				$endDate=$end;
			}

			$from = clone $startDate;
			$to = clone $endDate;

			$success = $callback($from, $to);

			if (!$success) {
				$this->utils->error_log('loop date time failed', $from, $to);
				break;
			}

			$startDate = $this->getNextTime($endDate, "+1 seconds");
		}

		return $success;
	}

	public function generateDateTimeRange( $first, $last, $step = '+30 minutes', $format = 'Y-m-d H:i:s', $splitDates = true ) {

        $dates = array();
        $current = strtotime( $first );
        $last = strtotime( $last );


        while( $current <= $last ) {
            $temp = [];
            $temp['from'] = date( $format, $current );
            $current = strtotime( $step, $current );
            $temp['to'] = date($format, $current );

            if($splitDates &&
                date('Ymd', strtotime($temp['from'])) <> date('Ymd', strtotime($temp['to']))){
                $temp2 = [];
                $temp2['from'] = $temp['from'];
                $temp2['to'] = date('Y-m-d', strtotime($temp['from'])).' 23:59:59';
                $dates[] = $temp2;

                $temp2 = [];
                $temp2['from'] = date('Y-m-d', strtotime($temp['to'])).' 00:00:00';
                $temp2['to'] = $temp['to'];
                $dates[] = $temp2;

            }else{
                $dates[] = $temp;
            }


        }

        return $dates;
    }

    public function tableExistsOnMysql($tableName, $db=null) {

		$_multiple_db=Multiple_db::getSingletonInstance();
		$dbName=$_multiple_db->getDBNameFromTargetDB();
		$arr=explode('.', $tableName);
		if(count($arr)==2){
			$dbName=$arr[0];
			$tableName=$arr[1];
		}
		$sql=<<<EOD
select TABLE_NAME from information_schema.TABLES
where TABLE_SCHEMA=?
and TABLE_NAME=?
EOD;
		$params=[$dbName, $tableName];
		$this->debug_log('check table on mysql', $sql, $params);
		$exists=false;
		$this->CI->load->model(['player_model']);
		$row=$this->CI->player_model->runOneRawSelectSQLArray($sql, $params, $db);
		if(!empty($row)){
			$exists=$row['TABLE_NAME']==$tableName;
		}
		return $exists;
	}

	public function getRespTableFullName($dateStr){
		$tableName='resp_'.$dateStr;
		$new_resp_table_append_db_name=$this->getConfig('new_resp_table_append_db_name');
		$new_resp_table_append_db_name_on_currency_mdb=$this->getConfig('new_resp_table_append_db_name_on_currency_mdb');
		if(!empty($new_resp_table_append_db_name) && $new_resp_table_append_db_name_on_currency_mdb==$this->getActiveTargetDB()){
			$tableName=$new_resp_table_append_db_name.'.'.$tableName;
		}
		$this->debug_log('try get resp table name', $new_resp_table_append_db_name, $new_resp_table_append_db_name_on_currency_mdb, $tableName);
		return $tableName;
	}

	public function initFailedRemoteCommonSeamlessTransactionTableByDate($yearMonthStr){
		$main_table = 'failed_remote_common_seamless_transactions';
		$tableName = $main_table.'_'.$yearMonthStr;

		if (!$this->table_really_exists($tableName)) {
			try{
				$this->CI->db->query("CREATE TABLE $tableName LIKE $main_table");
			}catch(Exception $e){
				$this->error_log('create table failed: '.$tableName, $e);
			}
		}

	}

	/**
	 * initBalanceMonthlyTableByDate
	 * @param  string $yearMonthStr
	 * @return string table name
	 */
	public function initBalanceMonthlyTableByDate($yearMonthStr){
		$tableName='balance_history_'.$yearMonthStr;
		if (!$this->table_really_exists($tableName)) {
			try{

			$this->CI->load->dbforge();

			$fields=[
				'id' => [
					'type' => 'BIGINT',
					'null' => false,
					'auto_increment' => TRUE,
					'unsigned' => true,
				],
				'player_id' => [
					'type' => 'INT',
					'null' => false,
				],
				'aff_id' => [
					'type' => 'INT',
					'null' => true,
				],
				'user_type' => [
					'type' => 'INT',
					'null' => true,
				],
				'record_type' => [
					'type' => 'INT',
					'null' => true,
				],
				'action_type' => [
					'type' => 'INT',
					'null' => true,
				],
				'main_wallet' => [
					'type' => 'double',
					'null' => true,
				],
				'sub_wallet' => [
					'type' => 'json',
					'null' => true,
				],
				'transaction_id' => [
					'type' => 'INT',
					'null' => true,
				],
				'playerpromo_id' => [
					'type' => 'INT',
					'null' => true,
				],
				'sale_order_id' => [
					'type' => 'INT',
					'null' => true,
				],
				'walletaccount_id' => [
					'type' => 'INT',
					'null' => true,
				],
				'sub_wallet_id' => [
					'type' => 'INT',
					'null' => true,
				],
				'amount' => [
					'type' => 'double',
					'null' => true,
				],
				'game_platform_id' => [
					'type' => 'INT',
					'null' => true,
				],
				'total_balance' => [
					'type' => 'double',
					'null' => true,
				],
				'big_wallet' => [
					'type' => 'json',
					'null' => true,
				],
				'agent_id' => [
					'type' => 'INT',
					'null' => true,
				],
				'created_at' => [
					'type' => 'DATETIME',
					'null' => false,
				],
				'updated_at' => [
					'type' => 'DATETIME',
					'null' => true,
				],
			];
			$this->CI->dbforge->add_field($fields);
			$this->CI->dbforge->add_key('id', TRUE);
			$this->CI->dbforge->add_key('player_id');
			$this->CI->dbforge->add_key('transaction_id');
			$this->CI->dbforge->add_key('aff_id');
			$this->CI->dbforge->add_key('agent_id');
			$this->CI->dbforge->add_key('created_at');
			$this->CI->dbforge->create_table($tableName);

			}catch(Exception $e){
				$this->error_log('create table failed: '.$tableName, $e);
			}
		}

		return $tableName;
	}

	public function initSeamlessBalanceMonthlyTableByDate($yearMonthStr){
		$tableName='seamless_wallet_balance_history_'.$yearMonthStr;
		if (!$this->table_really_exists($tableName)) {
			try{

			$this->CI->load->dbforge();

			$fields = array(
				"id" => array(
					"type" => "BIGINT",
					"null" => false,
					"auto_increment" => true
				),
				"player_id" => array(
					"type" => "BIGINT",
					"null" => false,
				),
				"game_platform_id" => array(
					"type" => "INT",
					"null" => false
				),
				'transaction_date' => array(
					'type' => 'DATETIME',
					'null' => true
				),
				'transaction_type' => array(
					'type' => 'INT',
					'null' => true,
				),
				'amount' => array(
					'type' => 'DOUBLE',
					'null' => true,
				),
				'before_balance' => array(
					'type' => 'DOUBLE',
					'null' => true,
				),
				'after_balance' => array(
					'type' => 'DOUBLE',
					'null' => true,
				),
				'round_no' => array(
					'type' => 'VARCHAR',
					'constraint' => '100',
					'null' => true,
				),
				'extra_info' => array(
					'type' => 'TEXT',
					'null' => true,
				),

				# SBE additional info
				"external_uniqueid" => array(
					"type" => "VARCHAR",
					"constraint" => "100",
					'unique' => true,
				),
				"created_at DATETIME DEFAULT CURRENT_TIMESTAMP" => array(
					"null" => false
				),
				"updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP" => array(
					"null" => false
				),
				'md5_sum' => array(
					'type' => 'VARCHAR',
					'constraint' => '32',
					'null' => true,
				)
			);

			$this->CI->dbforge->add_field($fields);
			$this->CI->dbforge->add_key('id', TRUE);
			$this->CI->dbforge->add_key('player_id');
			$this->CI->dbforge->add_key('game_platform_id');
			$this->CI->dbforge->add_key('transaction_date');
			$this->CI->dbforge->add_key('round_no');
			$this->CI->dbforge->add_key('transaction_type');
			$this->CI->dbforge->create_table($tableName);
			$this->CI->load->model('player_model');
			$this->CI->player_model->addUniqueIndex($tableName, 'idx_external_uniqueid_game_platform_id', 'external_uniqueid,game_platform_id');

			}catch(Exception $e){
				$this->error_log('create table failed: '.$tableName, $e);
			}
		}

		return $tableName;
	}

	public function initT1lotteryTransactionsMonthlyTableByDate($yearMonthStr){
		$tableName='t1lottery_transactions_'.$yearMonthStr;
		if (!$this->CI->utils->table_really_exists($tableName)) {
			try{

                $this->CI->load->dbforge();

                $fields = array(
                    'id' => array(
                        'type' => 'BIGINT',
                        'null' => false,
                        'auto_increment' => TRUE,
                    ),
                    'username' => array(
                        'type' => 'VARCHAR',
                        'constraint' => '50',
                        'null' => true,
                    ),
                    'timestamp' => array(
                        'type' => 'VARCHAR',
                        'constraint' => '50',
                        'null' => true,
                    ),
                    'timestamp_parsed' => array(
                        'type' => 'DATETIME',
                        'null' => true,
                    ),
                    'merchant_code' => array(
                        'type' => 'VARCHAR',
                        'constraint' => '50',
                        'null' => true,
                    ),
                    'amount' => array(
                        'type' => 'double',
                        'null' => true,
                    ),
                    'currency' => array(
                        'type' => 'VARCHAR',
                        'constraint' => '10',
                        'null' => true,
                    ),
                    'round_id' => array(
                        'type' => 'VARCHAR',
                        'constraint' => '50',
                        'null' => true,
                    ),
                    'bet_id' => array(
                        'type' => 'VARCHAR',
                        'constraint' => '50',
                        'null' => true,
                    ),
                    'player_id' => array(
                        'type' => 'BIGINT',
                        'null' => false,
                    ),
                    'trans_type' => array(
                        'type' => 'VARCHAR',
                        'constraint' => '50',
                        'null' => true,
                    ),
                    'before_balance' => array(
                        'type' => 'double',
                        'null' => true,
                    ),
                    'after_balance' => array(
                        'type' => 'double',
                        'null' => true,
                    ),
                    'game_platform_id' => array(
                        'type' => 'INT',
                        'constraint' => '11',
                        'null' => false,
                    ),
                    'status' => array(
                        'type' => 'TINYINT',
                        'null' => true,
                        'default' => 0,
                    ),
                    'raw_data' => array(
                        'type' => 'TEXT',
                        'null' => true
                    ),
                    'game_code' => array(
                        'type' => 'varchar',
                        'constraint' => 20,
                        'null' => true
                    ),
                    'number' => array(
                        'type' => 'varchar',
                        'constraint' => 20,
                        'null' => true
                    ),
                    'opencode' => array(
                        'type' => 'varchar',
                        'constraint' => 20,
                        'null' => true
                    ),

                    # SBE additional info
                    'response_result_id' => array(
                        'type' => 'INT',
                        'constraint' => '11',
                        'null' => true,
                    ),
                    'external_uniqueid' => array(
                        'type' => 'VARCHAR',
                        'constraint' => '100',
                        'null' => true,
                    ),
                    'elapsed_time' => array(
                        'type' => 'INT',
                        'null' => true,
                    ),
                    'created_at DATETIME DEFAULT CURRENT_TIMESTAMP' => array(
                        'null' => false,
                    ),
                    'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' => array(
                        'null' => false,
                    ),
                );

                $this->CI->dbforge->add_field($fields);
                $this->CI->dbforge->add_key('id', TRUE);
                $this->CI->dbforge->create_table($tableName);
                # Add Index
                $this->CI->load->model('player_model');
                $this->CI->player_model->addIndex($tableName, 'idx_round_id', 'round_id');
                $this->CI->player_model->addIndex($tableName, 'idx_bet_id', 'bet_id');
                $this->CI->player_model->addIndex($tableName, 'idx_trans_type', 'trans_type');
                $this->CI->player_model->addIndex($tableName, 'idx_timestamp_parsed', 'timestamp_parsed');
                $this->CI->player_model->addIndex($tableName, 'idx_player_id', 'player_id');
                $this->CI->player_model->addIndex($tableName, 'idx_updated_at', 'updated_at');
                $this->CI->player_model->addIndex($tableName, 'idx_status', 'status');
                $this->CI->player_model->addIndex($tableName, 'idx_game_platform_id', 'game_platform_id');
                $this->CI->player_model->addIndex($tableName, 'idx_game_code', 'game_code');
                $this->CI->player_model->addUniqueIndex($tableName, 'idx_external_uniqueid', 'external_uniqueid');

			}catch(Exception $e){
				$this->CI->utils->error_log('create table failed: '.$tableName, $e);
			}
		}

		return $tableName;
	}

	public function getSeamlessBalanceHistoryTable($dateStr=null){
		$d=new DateTime($dateStr);
		$monthStr=$d->format('Ym');
		return $this->initSeamlessBalanceMonthlyTableByDate($monthStr);
	}

	public function getBalanceHistoryTable($dateStr=null){
		if($this->getConfig('enabled_balance_history_monthly_table')){
			$d=new DateTime($dateStr);
			$monthStr=$d->format('Ym');
			return $this->initBalanceMonthlyTableByDate($monthStr);
		}else{
			return 'balance_history';
		}
	}
	/**
	 * Filter the array for get the element that's key contains the $findwe string.
	 *
	 * @param array $findwe The key contains anyone of the elements of array.
	 * @param array $sourceArray The source key-value array.
	 * @return array $resultArray The result array.
	 */
	public function filterKeyContainStringsOfList($findwe=[], $sourceArray=[]){
		$resultArray = [];
		$findweResult = array_filter( array_keys($sourceArray), function ($keyStr) use ($findwe){
            $returnBool = false;
            foreach($findwe as $findme){
                if( strpos($keyStr, $findme) !== false ){
                    $returnBool = $returnBool || true;
                }
            }
            return $returnBool;
		});
		if( !empty( $findweResult ) ){
			foreach($findweResult as $findweKeyStr){
				$resultArray[$findweKeyStr] = $sourceArray[$findweKeyStr];
			}
		}
		return $resultArray;
	} // EOF filterKeyContainStringsOfList

	/**
	 * Returns data range from date by type daily|weekly|monthly
	 */
	public function getRangeFromType($type, $date){
		$limitFrom = $limitTo = null;
		if(!$type||!$date){
			return [$limitFrom, $limitTo];
		}
		$new_date = new DateTime($date);
		switch ($type) {
			case 'daily':
				$limitFrom = $limitTo = date('Y-m-d', strtotime($date));
				break;
			case 'weekly':
				$dayNum = $new_date->format('N');
				$limitFrom = $new_date->format('Y-m-d');
				if($dayNum>0){
				    $dayNum--;
				    $limitFrom = $new_date->modify('-'.$dayNum.'day')->format('Y-m-d');
				}
				$limitFromObj = new DateTime($limitFrom);
				$limitTo = $limitFromObj->modify('+6 days')->format('Y-m-d');
				break;
			case 'monthly':
				$limitFrom = $new_date->modify('first day of this month')->format('Y-m-d');
				$limitTo = $new_date->modify('last day of this month')->format('Y-m-d');
				break;
			default:
				return [$limitFrom, $limitTo];
		}

		return [$limitFrom, $limitTo];
	}

	public function isCharLengthValid($input) {
        $length = strlen($input);
        $min_prefix=3;
        $max_prefix=5;
        if ($length <= $max_prefix && $length >= $min_prefix) {
            return true;
		}
		return false;
    }

    /**
     * minus minutes from date time
     * @param  string $datetime
     * @param  integer $minutes
     * @return string
     */
	public function getMinusMinutesForMysql($datetime, $minutes) {
		if(empty($datetime)){
			return null;
		}
		$d = new \DateTime($datetime);
		$d->modify('-' . $minutes . ' minutes');
		return $this->formatDateTimeForMysql($d);
	}

	/**
	 * tryGetRealIPWithoutWhiteIP
	 * @return string ip
	 */
	public function tryGetRealIPWithoutWhiteIP() {
		$this->CI->load->model(['ip']);
		//get remote addr but not cdn/proxy ip
		$remoteAddr=$this->CI->input->getRemoteAddr();
		if(!empty($remoteAddr)){
			if($this->CI->ip->isCDNOrProxy($remoteAddr) || $this->isLocalIP($remoteAddr)){
				//ignore cdn/proxy or private ip
				$this->debug_log('ignore cdn/proxy or private ip', $remoteAddr);
				$remoteAddr=null;
			}
		}
		$foundCDNOrProxy=false;
		$list=$this->CI->input->getIpListFromXForwardedFor();
		//default ip
		$realIp=$this->CI->input->ip_address();
		$this->debug_log('default real ip', $realIp, 'list of ip', $list);
		$countOfIP=count($list);
		//get ip from x forwarded for
		foreach ($list as $ip) {
			if(empty($ip)){
				continue;
			}
			if($this->CI->ip->isCDNOrProxy($ip)){
				$foundCDNOrProxy=true;
				$this->debug_log('found cdn or proxy', $ip, 'so real ip is', $realIp);
				break;
			}else if(!$this->isLocalIP($ip)){
				$realIp=$ip;
				$this->debug_log('try set real ip', $realIp);
			}else{
				$this->debug_log('ignore private ip', $ip);
			}
		}
		// if count of ip>1
		if(!$foundCDNOrProxy && $countOfIP>1){
			// if not found cdn/proxy on x forwarded for, will try remote addr
			if(!empty($remoteAddr)){
				$realIp=$remoteAddr;
			}else{
				$this->debug_log('no cdn/proxy, no remote addr, use last ip as real ip', $realIp);
			}
		}
		$xforwarded=null;
		if(array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER)){
			$xforwarded=@$_SERVER['HTTP_X_FORWARDED_FOR'];
		}
		$this->debug_log('final real ip', $realIp, 'HTTP_X_FORWARDED_FOR', $xforwarded);
		return $realIp;
	}

	public function extractLangFromData($data, $key){
    	$this->CI->load->library(['language_function']);
		$langId = $this->CI->language_function->getCurrentLanguage();
    	$result=[];
		$langJson = isset($data[$key])?$data[$key]:null;
		if(!empty($langJson) && substr($langJson, 0, 6) === '_json:'){
			$jsonStr = substr($langJson, 6);
			$jsonArr = json_decode($jsonStr, true);
			$result = isset($jsonArr[$langId])?$jsonArr[$langId]:$langJson;
			return trim($result,' ,');
		}else{
			return $langJson;
		}
    }

	/**
	 * Find the day, the date will be the Weekday and the Day-th of the month.
	 *
	 *
	 * @param integer $findDth the day-th of the month
	 * @param integer $findWeekday the weekday
	 * @param string $timeExecBeginStr Source by the date begin to find.
	 * @param null|boolean $isFoundIt To assign the param for check Found It or Not.
	 * @return DateTime
	 */
	function findWeekdayAndDth($findDth = 5, $findWeekday = 1, $timeExecBeginStr = 'now', &$isFoundIt = null){
		$TEB_DateTime = new DateTime($timeExecBeginStr);
		$theDth = $TEB_DateTime->format('d');
		$theWeekday = $TEB_DateTime->format( 'N' );
		// $findDth = 5;// moved to the param
		// $findWeekday = 1; // moved to the param
		$maxLimit = 999;
		$i=0;
		while( ($findWeekday != $theWeekday || $findDth != $theDth)
			&& $i < $maxLimit
		){

			$TEB_DateTime->modify('first day of next month');
			$TEB_DateTime->modify('+'. ($findDth- 1). ' day');

			$theDth = $TEB_DateTime->format('d');
			$theWeekday = $TEB_DateTime->format( 'N' );

			$i++;
		}
		if($maxLimit == $i){
			// over search range
			$isFoundIt = false;
		}else{
			$isFoundIt = true;
		}
		return $TEB_DateTime;
	}

	public function savePlayerLoginDetails($playerId, $username, $result, $login_from, $extra = []){

        $this->CI->load->library(array('user_agent'));
		$this->CI->load->model(array('player_login_report','player_model'));

		$this->utils->debug_log(__METHOD__.'() result', $result);

		if (empty($playerId)) {
			$this->utils->debug_log(__METHOD__.'() playerId', $playerId, 'username', $username);
			return false;
		}

		$ip = !empty($extra['ip']) ? $extra['ip'] : ($this->utils->getConfig('try_real_ip_on_acl_api') ? $this->tryGetRealIPWithoutWhiteIP() : $this->getIP());
		$ua = !empty($extra['user_agent']) ? $extra['user_agent'] : (!empty($this->CI->agent->agent) ? $this->CI->agent->agent : '');
		$referrer = !empty($extra['referrer']) ? $extra['referrer'] : (($this->CI->agent->is_referral() == TRUE) ? $this->CI->agent->referrer() : ' ');

		$this->utils->debug_log(__METHOD__.'() extra',$extra, $ip, $ua, $referrer);

		$login_result = $result['success'] ? Player_login_report::LOGIN_SUCCESS : Player_login_report::LOGIN_FAILED;
		$notes = !empty($result['errors']) ? json_encode($result['errors']) : '';
		$headers = $this->CI->input->request_headers();
		$device = ($this->CI->agent->is_mobile() == TRUE) ? $this->CI->agent->mobile() : $this->CI->agent->browser() . " " . $this->CI->agent->version();
		$now = $this->utils->getNowForMysql();
		$player_status = $this->utils->getPlayerStatus($playerId);

		$browser_type = 0;
		switch (true) {
			case isset($_SERVER['HTTP_X_APP_IOS']):
				$browser_type = Http_request::HTTP_BROWSER_TYPE_IOS;
				break;
			case isset($_SERVER['HTTP_X_APP_ANDROID']):
				$browser_type = Http_request::HTTP_BROWSER_TYPE_ANDROID;
				break;
			default:
				if ($this->CI->agent->is_mobile() == FALSE) {
					$this->debug_log('pc');
					$browser_type = Http_request::HTTP_BROWSER_TYPE_PC; //pc
				} else {
					$this->debug_log('mobile');
					$browser_type = Http_request::HTTP_BROWSER_TYPE_MOBILE; //mobile
				}
				break;
		}

		$data = array(
			"player_id" => $playerId,
			"ip" => $ip,
			"cookie" => isset($headers['Cookie']) ? $headers['Cookie'] : null,
			"referrer" => $referrer,
			"user_agent" => $ua,
			"os" => $this->CI->agent->platform(),
			"device" => $device,
			"is_mobile" => ($this->CI->agent->is_mobile() == TRUE) ? 1 : 0,
			"create_at" => $now,
			"login_result" => $login_result,
			"player_status" => $player_status,
			"login_from" => $login_from,
			"browser_type" => $browser_type,
			"content" => $notes
		);

		$this->utils->debug_log(__METHOD__.'() data', json_encode($data));

		$insertData = $this->CI->player_login_report->insertPlayerLoginDetails($data);

		$this->utils->debug_log(__METHOD__.'() insertData', $insertData);
	}

	public function isMultiDimentionalArray($a) {
		$rv = array_filter($a,'is_array');
		if(count($rv)>0) return true;
		return false;
	}

    public function arrayToHtmlList($array, $level = 0)
    {
        $level++;
        $return = "<ul>";

        foreach($array as $k => $v) {
            if (is_array($v)) {
                $return .= "<li class='bet_details_level_{$level}'>" . $k . "</li>";
                $return .= $this->arrayToHtmlList($v, $level);
                continue;
            }

            if(is_numeric($k)){
                $return .= "<li class='bet_details_level_{$level}'>" . $v . "</li>";
            }else{
                $return .= "<li class='bet_details_level_{$level}'>" . $k . ' : ' . $v . "</li>";
            }


        }

        $return .= "</ul>";
        return $return;
    }

    public function formatResursiveJsonToHtmlListBetDetails($data, $title, $isKey = false){
        if($isKey){
            $string = "<li><b>$title</b>";
        }else{
            #$string = "<b>$title</b><br>";
            $string = '';
        }
        $string .= "<ul>";
        if($this->isMultiDimentionalArray($data)){
            foreach($data as $key => $value){
                if(!is_array($value)){
                    $string .= "<li><b>$key:</b> " . $value ."</li>";
                }else{
                    $string2 = '';
                    if(is_numeric($key)){
                        if(!empty($value)){
                            $string .= "<li>";
                            $string .= implode(', ', $value);
                            $string .= "</li>";
                        }
                    }else{
                        $string .= @$this->formatResursiveJsonToHtmlListBetDetails($value, $key, true);
                    }
                }
            }

        }else{
            if(!empty($data)){
                $string .= "<li>" . implode(', ', $data) . "</li>";
            }
        }
        $string .= "</ul>";
        $string .= "</li>";
        return $string;
    }

    /**
     * Format small float numbers to fixed point, with expected effective digits and at least min_digits
     * OGP-23116
     * @param	float	$v
     * @param	int		$effective_digits
     * @param	int		$min_digits
     * @return	string
     */
    public function smallNumberToFixed($v, $effective_digits = 4, $min_digits = 8) {
    	// Numbers > 1: simply apply %f formatter
    	if ($v > 1) {
    		return sprintf('%f', $v);
    	}

    	$u = abs($v);

    	$lgu = abs(log10($u));

    	$prec = floor($lgu) + $effective_digits;
    	$prec = $prec < $min_digits ? $min_digits : $prec;

    	return sprintf("%.{$prec}f", $v);
    }

	/**
	 * getActivePopupBanner
	 * @param string $site 'www' or 'player'
	 * @return array
	 */
	public function getActivePopupBanner( $player_id)
	{
		$this->CI->load->model(['cms_model']);
		$this->CI->load->library(array('cmsbanner_library'));

		$availableBanner = [];
		$access_time = $this->getNowForMysql();
		$popupbanner = $this->CI->cms_model->getVisiblePopupBanner();
		try{

			if(empty($popupbanner)){
				throw new Exception("no popup banner found", 1);
			}

			if($popupbanner['is_daterange'] == 1){

				$start_date = new DateTime($popupbanner['start_date']);
                $end_date = new DateTime($popupbanner['end_date']);

				$start_date = $this->formatDateTimeForMysql($start_date);
                $end_date = $this->formatDateTimeForMysql($end_date);

				if($access_time < $start_date || $end_date < $access_time){
					throw new Exception("not in the date range expect start[$start_date] end[$end_date]", 2);
				}
			}

			$display_in = json_decode($popupbanner['display_in'],true);
			if(is_array($display_in)){


                if ($this->is_mobile()) {
                    if(!in_array( 2, $display_in) && !(in_array(4, $display_in))){
                    	throw new Exception("not enable in mobile", 2);
                    }
                } else {
                    if (!in_array(1, $display_in) && !(in_array(3, $display_in))) {
                        throw new Exception("not enable in desktop", 2);
                    }
				}

			}

			$display_freq = json_decode($popupbanner['display_freq'], true);
			if (!is_array($display_freq)) {
				throw new Exception("not enable freq", 2);
			} else {
				$player = $this->CI->player_model->getPlayerInfoById($player_id, null);
				$before_last_login_time = $player['before_last_login_time'];
				if (empty($display_freq)) {
					throw new Exception("not enable freq", 2);
				}
				if( !in_array(2, $display_freq) && in_array(1, $display_freq)){
					if(!empty($before_last_login_time)){
						throw new Exception("not enable freq", 2);
					}

				} else if( !in_array(2, $display_freq)){

					throw new Exception("not enable freq", 2);
				}
			}

			$redriectLink = '';
			$redriectBtnName = (!empty(trim($popupbanner['redirect_btn_name']))) ? $popupbanner['redirect_btn_name'] : false;
			if($popupbanner['redirect_to'] == 'enable'){

				switch ($popupbanner['redirect_type']) {
					case '1':
						//'onclick="document.location.href='/player_center2/deposit'"
						$redriectLink = '/player_center2/deposit';
						$redriectBtnName = $redriectBtnName?:lang('Deposit');
						break;
					case '2':
						//onclick="document.location.href='/player_center2/referral'"
						$redriectLink = '/player_center2/referral';
						$redriectBtnName = $redriectBtnName?:lang('Refer a Friend');
						break;
					case '3':
						//document.location.href='/player_center2/promotion'
						$redriectLink = '/player_center2/promotion';
						$redriectBtnName = $redriectBtnName?:lang('Promotions');
						break;
				}
			}

			$banner_src = '';
			$background_color = '';
			if(!empty($popupbanner['banner_url'])){
				if($popupbanner['is_default_banner'] == 1) {
					$bannerBackgroundElement = '<div style="background-color:'.$popupbanner['banner_url'].'" class="banner" id="bannerColor"></div>';
					$background_color = $popupbanner['banner_url'];

				} else {

                    if (!empty($popupbanner['banner_url']) && file_exists($this->CI->cmsbanner_library->getUploadPath($popupbanner['banner_url']))) {
                        $popupbanner['banner_url'] = $this->utils->getSystemUrl('player') . $this->CI->cmsbanner_library->getPublicPath($popupbanner['banner_url']);
                    }

					$banner_src = $popupbanner['banner_url'];
					$bannerBackgroundElement = '<img src="'.$banner_src.'" class="banner">';
				}
			}

			$availableBanner = array(
				'title' => ($popupbanner['title']?:''),
				'content' => $this->CI->cms_model->decodePromoDetailItem(($popupbanner['content']?:'')),
				'popupBannerId' => isset($popupbanner['id']) ? $popupbanner['id'] : $popupbanner['popup_id'],
				'bannerBackgroundElement' => $bannerBackgroundElement,
				'backgroundImg' => $banner_src,
				'backgroundColor' => $background_color,
				'displayInPlayerDesktop' => (in_array(1, $display_in)) ? 1 : 0,
				'displayInPlayerMobile' => (in_array(2, $display_in)) ? 1 : 0,
				'displayInWSiteFontDesktop' => (in_array(3, $display_in)) ? 1 : 0,
				'displayInWsiteFontMobile' => (in_array(4, $display_in)) ? 1 : 0,
			);

			$redriectLink = '';
            $redriectBtnName = (!empty(trim($popupbanner['redirect_btn_name']))) ? $popupbanner['redirect_btn_name'] : false;
            if ($popupbanner['redirect_to'] == 'enable') {

                switch ($popupbanner['redirect_type']) {
                    case '1':
                        //'onclick="document.location.href='/player_center2/deposit'"
                        $redriectLink = '/player_center2/deposit';
                        $redriectBtnName = $redriectBtnName?:lang('Deposit');
                        break;
                    case '2':
                        //onclick="document.location.href='/player_center2/referral'"
                        $redriectLink = '/player_center2/referral';
                        $redriectBtnName = $redriectBtnName?:lang('Refer a Friend');
                        break;
                    case '3':
                        //document.location.href='/player_center2/promotion'
                        $redriectLink = '/player_center2/promotion';
                        $redriectBtnName = $redriectBtnName?:lang('Promotions');
                        break;
                }
				$availableBanner['redriectLink'] = $redriectLink;
				$availableBanner['redriectBtnName'] = $redriectBtnName;
            }


		} catch (Exception $e) {
			$error_message = $e->getMessage();
			$this->debug_log('no avalible popup banner', $error_message);
			return false;
		}

		return json_encode($availableBanner);
        // return $availableBanner;
	}

    /**
     * sendMessageToMattermostChannel
     * @param  string $level   'warning' or 'error'
     * @param  string $title
     * @param  string $message
     * @return boolean
     */
    public function sendMessageToMattermostChannel($channel, $level, $title, $message, $pretext=null){
		$username = $this->CI->db->getDBName();
    	// $username='system alert';
    	// $channel='system_alert';
	    $this->CI->load->helper('mattermost_notification_helper');
	    if($level=='error'){
	    	$level='danger';
	    }
        $messages = [
            [
                'text' => $message,
                'type' => $level,
                'title' => $title,
                'pretext' => $pretext,
            ]
        ];

	    return sendNotificationToMattermost($username, $channel, $messages);
    }

	/**
	 * Get the enable_timezone_query setting on the current page.
	 *
	 * TODO: the result from function, _getIsEnableWithMethodAndList().
	 *
	 * @param string $theMethod
	 * @return bool If true, should display the timezone input for query.
	 */
	public function _getEnableTimezoneQueryWithMethod($theMethod){
		$enable_timezone_query = false; // default
		$enable_timezone_query_method_list = $this->CI->config->item('enable_timezone_query_method_list');
		if( !empty($enable_timezone_query_method_list) ){
			if( in_array($theMethod, $enable_timezone_query_method_list) ){ // __METHOD__, "Payment_management::deposit_list"
				$enable_timezone_query = true;
			}
		}
		return $enable_timezone_query;
	} // EOF _getEnableTimezoneQueryWithMethod


	//
	/**
	 * Get the daily_balance_in_extra_db_method_list setting on the current method.
	 *
	 * @param string $theMethod
	 * @param string $currency for mdb, ex: cny, thb and idr,...etc. Its depended by isEnabledMDB()
	 * @param string $extrasuffix_database For caller to get the extrasuffix database name. Return empty string while $is_enable was false.
	 * @return bool If true, the balance_history should add extra_db in sql.
	 */
	public function _getDailyBalanceInExtraDbWithMethod($theMethod, $currencyStr = null, &$extrasuffix_database = ''){
		$daily_balance_in_extra_db_method_list = $this->CI->config->item('daily_balance_in_extra_db_method_list');
		$is_enable = $this->_getIsEnableWithMethodAndList($theMethod, $daily_balance_in_extra_db_method_list);
		if($is_enable){
			$extrasuffix_database_formater = $this->CI->config->item('extrasuffix_database_formater');
			$_currencyAppendStr = ''; // default, ignore
			if( $this->isEnabledMDB() ){ // for mdb
				if( ! empty($currencyStr) ){
					$_currencyAppendStr = '_'. $currencyStr;
				}
			}
			$extrasuffix_database = sprintf($extrasuffix_database_formater, $_currencyAppendStr);
		}

		return $is_enable;
	}

	/**
	 * For balance_history table
	 * log the CallTrace while currect ActionType has matched detect ActionType
	 *
	 * @param string $curr_database
	 * @param integer $curr_balanceHistoryId
	 * @param integer $curr_ActionType
	 * @param string|integer $detectActionType So far, its supported 6, 1001 and "any".
	 * @param integer $entraceLineNo please assign __LINE__. the keyword, "26371.16387"
	 * @param string $entraceCallTrace The string form return of utils::generateCallTrace()
	 * @return void
	 */
	public function scriptOGP26371_catch_action_type_source( $curr_database
															, $curr_balanceHistoryId = 0
															, $curr_ActionType = 0
															, $detectActionType = 6
															, $entraceLineNo = 0
															, &$entraceCallTrace = ''
	){
		if( strtolower($detectActionType) == 'any'){
			$curr_balanceHistoryId = 0;
			$curr_ActionType = 0;
			$detectActionType = 0;
		}

		if( ! empty($this->getConfig("enabled_log_OGP26371_catch_{$detectActionType}_source") ) ){ // "enabled_log_OGP26371_catch_6_source", "enabled_log_OGP26371_catch_1001_source"
			if( ! empty($curr_balanceHistoryId) ){
				$sql = "SELECT action_type FROM {$curr_database}balance_history WHERE `id` = $curr_balanceHistoryId";
				$_query = $this->CI->db->query($sql, []);
				$_rows = $_query->result_array();
				$_query->free_result(); // free
				if( ! empty($_rows) ){
					$curr_ActionType = $_rows[0]['action_type'];
				}
				// free
				unset($_rows);
				unset($_query);
			}

			if( empty($entraceCallTrace) ){
				$entraceCallTrace = $this->generateCallTrace();
			}

			if( $curr_ActionType == $detectActionType ){
				$this->debug_log("OGP-26371.16387.entraceLineNo:", $entraceLineNo
					, 'detectActionType:', $detectActionType
					, 'curr_database:', $curr_database
					, 'curr_ActionType:', $curr_ActionType
				);
				$this->debug_log("OGP-26371.16387.entraceLineNo:", $entraceLineNo, 'entraceCallTrace:', $entraceCallTrace );
				$entraceCallTrace = '';// clear
			}
		}
	}// EOF scriptOGP26371_catch_action_type_source

	/**
	 * Get the balance_history_in_extra_db_method_list setting on the current method.
	 *
	 * @param string $theMethod
	 * @param string $currency for mdb, ex: cny, thb and idr,...etc. Its depended by isEnabledMDB()
	 * @param string $extrasuffix_database For caller to get the extrasuffix database name. Return empty string while $is_enable was false.
	 * @return bool If true, the balance_history should add extra_db in sql.
	 */
	public function _getBalanceHistoryInExtraDbWithMethod($theMethod, $currencyStr = null, &$extrasuffix_database = ''){
		$total_balance_in_extra_db_method_list = $this->CI->config->item('balance_history_in_extra_db_method_list');
		$is_enable = $this->_getIsEnableWithMethodAndList($theMethod, $total_balance_in_extra_db_method_list);
		if($is_enable){
			$extrasuffix_database_formater = $this->CI->config->item('extrasuffix_database_formater');
			$_currencyAppendStr = ''; // default, ignore
			if( $this->isEnabledMDB() ){ // for mdb
				if( ! empty($currencyStr) ){
					$_currencyAppendStr = '_'. $currencyStr;
				}
			}
			$extrasuffix_database = sprintf($extrasuffix_database_formater, $_currencyAppendStr);
		}

		if( ! empty( $this->utils->getConfig('enabled_log_OGP26371_display_db') ) ){
			$this->debug_log('OGP-26371.16386.is_enable:', $is_enable, 'extrasuffix_database:', $extrasuffix_database);
			$this->debug_log('OGP-26371.16386.generateCallTrace', $this->generateCallTrace() );
		}
		return $is_enable;
	} // EOF _getEnableFreezeTopWithMethod


	/**
	 * Get the enable_freeze_top_method_list setting on the current page.
	 *
	 * @param string $theMethod
	 * @return bool If true, should freeze top of list for this report.
	 */
	public function _getEnableFreezeTopWithMethod($theMethod){
		$enable_freeze_top_method_list = $this->CI->config->item('enable_freeze_top_method_list');
		$is_enable = $this->_getIsEnableWithMethodAndList($theMethod, $enable_freeze_top_method_list);
		return $is_enable;
	} // EOF _getEnableFreezeTopWithMethod
    //
	public function _getEnableGo1stPageAnotherSearchWithMethod($theMethod){
		$enable_method_list = $this->CI->config->item('enable_go_1st_page_another_search_method_list');
		$is_enable = $this->_getIsEnableWithMethodAndList($theMethod, $enable_method_list);
		return $is_enable;
	} // EOF _getEnableGo1stPageAnotherSearchWithMethod

    public function _getAdjustPlayerLevel2othersWithMethod($theMethod){
        $_method_list = $this->CI->config->item('adjust_player_level2others_method_list');
        $is_enable = $this->_getIsEnableWithMethodAndList($theMethod, $_method_list);
        return $is_enable;
    } // EOF _getAdjustPlayerLevel2othersWithMethod

    public function _getSyncVipGroup2othersWithMethod($theMethod){
        $_method_list = $this->CI->config->item('sync_vip_group2others_method_list');
        $is_enable = $this->_getIsEnableWithMethodAndList($theMethod, $_method_list);
        return $is_enable;
    } // EOF _getSyncVipGroup2othersWithMethod

	public function _getIsEnableWithMethodAndList($theMethod, $theMethodListInConfig){
		$is_enable = false; // default
		$_method_list = $theMethodListInConfig;
		if( !empty($_method_list) ){
			if( in_array($theMethod, $_method_list) ){ // __METHOD__, "Payment_management::deposit_list"
				$is_enable = true;
			}
		}
		return $is_enable;
	} // EOF _getIsEnableWithMethodAndList

	/**
	 * Get the Datetime Range for calc Cashback
	 *
	 * @param string $date The result of utils::formatDateForMysql().
	 * @param integer $startHour The attr. group_level::getCashbackSettings()->fromHour;
	 * @param integer $endHour The attr. group_level::getCashbackSettings()->toHour;
	 * @param string $start_date The begin day, the format is referenced to utils::formatDateForMysql().
	 * @param string $end_date The end day, the format is referenced to utils::formatDateForMysql().
	 * @return array The format as the followings,
	 * - $return['startDate'] = $lastDate;
	 * - $return['startHour'] = $startHour;
	 * - $return['endDate'] = $date;
	 * - $return['endHour'] = $endHour;
	 * - $return['startDateHour'] for tpgh.end_at ( game_logs/total_player_game_hour AS tpgh) in group_level::getPlayerBetByDate().
	 * - $return['endDateHour'] for tpgh.end_at ( game_logs/total_player_game_hour AS tpgh) in group_level::getPlayerBetByDate().
	 * - $return['startDateTime'] for tpgh.updated_at ( game_logs AS tpgh) in the function, group_level::getPlayerBetBySettledDate().
	 * - $return['endDateTime'] for tpgh.updated_at ( game_logs AS tpgh) in the function, group_level::getPlayerBetBySettledDate().
	 */
	function getDateTimesToCalcCashback($date, $startHour, $endHour, $start_date = null, $end_date = null){
		if (!empty($start_date) && !empty($end_date)) {
			$lastDate_date = $start_date;
			$date_date = $end_date;

            $lastDate = str_replace('-', '', $start_date);
			$date = str_replace('-', '', $end_date); // override $date with $end_date
        } else {
            $lastDate = $this->utils->getLastDay($date); // yesterday

            if (intval($endHour) == 23) {
                //all yesterday
                $date = $lastDate;
			}
			// get for date format, 'Y-m-d'
			$lastDate_date = $lastDate;
			$date_date = $date;

            $date = str_replace('-', '', $date);
			$lastDate = str_replace('-', '', $lastDate);
        }

		$return = [];
		$return['startDate'] = $lastDate;
		$return['startHour'] = $startHour;
		$return['endDate'] = $date;
		$return['endHour'] = $endHour;
		// for tpgh.end_at ( game_logs/total_player_game_hour AS tpgh)
		$return['startDateHour'] = $lastDate.$startHour;
		$return['endDateHour'] = $date.$endHour;
		// for tpgh.updated_at ( game_logs AS tpgh)
		$return['startDateTime'] = sprintf('%s %s:00:00', $lastDate_date , $startHour);
		$return['endDateTime'] = sprintf('%s %s:59:59', $date_date, $endHour);

		return $return;
	} // EOF getDateTimesToCalcCashback


	/**
	 * Save the Update Log of the player
	 *
	 * @param integer $player_id
	 * @param string $changes The changes note.
	 * @param string $updatedBy The username of the adminuser.
	 * @param string $today The datatime of the change action, format: "Y-m-d H:i:s".
	 * @return void
	 */
	public function _savePlayerUpdateLog($player_id // #1
                                        , $changes // #2
                                        , $updatedBy // #3
                                        , $today = null // #4
                                        , $db = null // #5
    ) {
        $this->CI->load->model(['player_model']);
		$data = array(
			'playerId' => $player_id,
			'changes' => $changes,
			'createdOn' => empty($today) ? date('Y-m-d H:i:s') : $today,
			'operator' => $updatedBy,
		);

		$this->CI->player_model->addPlayerInfoUpdates($player_id, $data, $db);
	}

	public function initAdminLogsTableByDate($yearMonthStr, $db = null){
		$tableName='logs_'.$yearMonthStr;
		if (!$this->table_really_exists($tableName, $db)) {
			try{

			$this->CI->load->dbforge();

            if ($db !== null) {
                $this->CI->dbforge->changeDB($db);
            }


			$fields=[
				'logId' => [
					'type' => 'INT',
					'null' => false,
					'auto_increment' => TRUE,
					'unsigned' => true,
				],
				'username' => [
					'type' => 'VARCHAR',
					'constraint'=>32,
					'null'=> false,
				],
				'management' => [
					'type' => 'VARCHAR',
					'constraint'=>255,
					'null'=> false,
				],
				'userRole' => [
					'type' => 'VARCHAR',
					'constraint'=>255,
					'null'=> true,
				],
				'action' => [
					'type' => 'VARCHAR',
					'constraint'=>500,
					'null'=> false,
				],
				'description' => [
					'type' => 'VARCHAR',
					'constraint'=>255,
					'null'=> true,
				],
				'logDate' => [
					'type' => 'DATETIME',
					'null'=> false,
				],
				'status' => [
					'type' => "enum('0','1')",
					'null'=> false,
					'default'=>'0',
				],
				'ip' => [
					'type' => 'VARCHAR',
					'constraint'=>64,
					'null'=> true,
				],
				'referrer' => [
					'type' => 'VARCHAR',
					'constraint'=>255,
					'null'=> true,
				],
				'uri' => [
					'type' => 'VARCHAR',
					'constraint'=>255,
					'null'=> true,
				],
				'data' => [
					'type' => 'VARCHAR',
					'constraint'=>2000,
					'null'=> true,
				],
				'extra' => [
					'type' => 'TEXT',
					'null'=> true,
				],
				'params' => [
					'type' => 'JSON',
					'null'=> true,
				],
			];
			$this->CI->dbforge->add_field($fields);
			$this->CI->dbforge->add_key('logId', TRUE);
			$this->CI->dbforge->add_key('logDate');
			$this->CI->dbforge->add_key('username');
			$this->CI->dbforge->add_key('ip');
			$this->CI->dbforge->add_key('management');
			$this->CI->dbforge->add_key('action');
			$this->CI->dbforge->create_table($tableName);

			}catch(Exception $e){
				$this->error_log('create table failed: '.$tableName, $e);
			}
		}

		return $tableName;
	}

	public function getAdminLogsMonthlyTable($dateStr=null, $db = null){
		if($this->getConfig('enabled_admin_logs_monthly_table')){
			$d=new DateTime($dateStr);
			$monthStr=$d->format('Ym');
			return $this->initAdminLogsTableByDate($monthStr, $db);
		}else{
			return 'logs';
		}
	}

	public function displaySmtpApiConfigKey($key, $value){
		if(!$this->getConfig('display_smtp_api_config_detail') && in_array($key, ['api_key', 'api_key_id'])){
			$value = '*****************'. substr($value, -3);
		}

		return $value;
	}

	public function convertArrayItemsToString($arr){
		$result = [];
		foreach($arr as $item){
			$result[] = (string)$item;
		}
		return $result;
	}

    public function getBatchpayoutSharingUploadPath($apiId, $path = null) {
		$fullPath = '/batch_payout/'.$apiId.'/';
		if(!empty($path)){
			$fullPath.=$path;
		}
		$baseDir=$this->getSharingUploadPath($fullPath);
		return $baseDir;
    }

	public function readRedisKeys($key = '*'){
		$redis=$this->getRedisServer(self::REDIS_DB_CACHE);
		if(empty($redis)){
			return null;
		}
		$key_full = $this->CI->utils->addAppPrefixForKey($key);
		$this->debug_log('========= readRedisKeys ============================',
		'key_full',$key_full,
		'key', $key);

		$allKeys = $redis->keys($key_full);	// all keys will match this.
		return $allKeys;
	}

	public function readRedisKeysNoAppPrefix($key = '*', $prefix = ''){
		$redis=$this->getRedisServer(self::REDIS_DB_CACHE);
		if(empty($redis)){
			return null;
		}
		$result = [];
		$fullKey = $key;
		if(empty($prefix)){
			$prefix = $this->getAppPrefix();
		}
		if (strpos($fullKey, $prefix) === false) {
			$fullKey = $prefix.'-'.$key;
		}

		//get all keys
		$keys=$redis->keys('*');
		foreach($keys as $i => $value){
			if (strpos($value, $fullKey) !== false) {
				$temp = [];
				$temp['key'] = $value;
				$temp['prefix'] = $prefix;
				$temp['no_prefix'] = $value;
				if(!empty($prefix)){
					$temp['no_prefix'] = str_replace($prefix.'-','',$value);
				}
				$result[] = $temp;
			}
		}

		$this->debug_log('readRedisKeysNoAppPrefix',
		'key', $key,
		'prefix', $prefix,
		'fullKey', $fullKey,
		'result', $result,
		'keys', $keys);

		return $result;
	}

	public function deleteRedisKey($key){
		$redis=$this->getRedisServer(self::REDIS_DB_CACHE);
		if(empty($redis)){
			return null;
		}
		if(method_exists($redis, 'unlink')){
			$rtl = $redis->unlink($key);
		}else{
			$rtl = $redis->del($key);
			$this->error_log('error using unlink in redis');
		}

		$this->CI->utils->info_log("deleteRedisKey key", $rtl);

		return true;
	}

	//https://github.com/phpredis/phpredis#del-delete-unlink
	public function removeRedisKey($key){
		$redis=$this->getRedisServer(self::REDIS_DB_CACHE);
		if(empty($redis)){
			return null;
		}
		$rtl = $redis->delete($key);
		$this->CI->utils->info_log("removeRedisKey key", $rtl);

		return true;
	}


	// source - https://stackoverflow.com/a/34280440
	function emoji_mb_ord($char, $encoding = 'UTF-8') {
		if ($encoding === 'UCS-4BE') {
			list(, $ord) = (strlen($char) === 4) ? @unpack('N', $char) : @unpack('n', $char);
			return $ord;
		} else {
			return $this->emoji_mb_ord(mb_convert_encoding($char, 'UCS-4BE', $encoding), 'UCS-4BE');
		}
	}
	// source - https://stackoverflow.com/a/34280440
	function emoji_mb_htmlentities($string, $hex = false, $encoding = 'UTF-8') {
		return preg_replace_callback('/[\x{80}-\x{10FFFF}]/u', function ($match) use ($hex) {
			return sprintf($hex ? '&#x%X;' : '&#%d;', $this->emoji_mb_ord($match[0]));
		}, $string);
	}

	public function remove_http($url) {
		$disallowed = array('http://', 'https://');
		foreach($disallowed as $d) {
		   if(strpos($url, $d) === 0) {
			  return str_replace($d, '', $url);
		   }
		}
		return $url;
	 }

	public function buildDeadsimplechatHtml($viplevel='', $playerUsername){
		$html = '';
		$vipCondition = $this->CI->config->item('deadsimplechat_condition_of_vip_level');
		$deadsimplechat_room_id = $this->CI->config->item('deadsimplechat_room_id');
		if(!empty($viplevel) && ($viplevel >= $vipCondition)){
			$html = '<iframe id="bubble-frame" class="deadsimplechat" src="https://deadsimplechat.com/'.$deadsimplechat_room_id.'?username='.$this->maskMiddleStringLite($playerUsername,1).'"> </iframe>';
		}else{
			$html = '<span class="deadsimplechat_vip_message">'.lang('deadsimplechat warning message').'</span>';
		}
		return $html;
	}

	public function getChatAsyncUrl(){
		$library = $this->getConfig('p2p_chat_api_default');
        $chat_config = $this->CI->utils->getConfig('p2p_chat_api');

        if(!empty($library) && isset($chat_config[$library])){
			return $this->getSystemUrl('player', '/async/getChatHTML');
        } else {
            return false;
		}
	}

    public function getChatHTML($vip_level = '', $player_username) {
        $library = $this->getConfig('p2p_chat_api_default');
		$this->utils->debug_log(__METHOD__ . ' get setting', [
			"library" => $library,
			"player_username" => $player_username,
			"vip_level" => $vip_level
		 ]);
		$html = '';
        if(empty($library)){
            // return $this->buildDeadsimplechatHtml($vip_level, $player_username); // for compatibility of old chat on smash
            // $html = '<span class="deadsimplechat_vip_message">'.lang('deadsimplechat warning message').'</span>';
			$this->utils->debug_log(__METHOD__ . 'no setting found', [ "library" => $library ]);
			return  $html;
        }

        $chat_config = $this->CI->utils->getConfig('p2p_chat_api')[$library];
        $vip_condition = $chat_config['vip_level'];
		$this->utils->debug_log(__METHOD__ . ' get chat_config', $chat_config);

        if(!empty($vip_level) && ($vip_level >= $vip_condition)) {
            $this->CI->load->model('player_model');
            $player_id = $this->CI->player_model->getPlayerIdByUsername($player_username);
            $this->CI->load->library('chat/'. $library);
            $chat_url = $this->CI->$library->getChatUrl($player_id);
			if(!empty($chat_url)){
				$html = '<iframe id="bubble-frame" class="deadsimplechat" src="' . $chat_url . '"> </iframe>';
			} else {
				$html = '<span class="deadsimplechat_vip_message">'.lang('unimplemented').'</span>';
			}
        } else {
            $html = '<span class="deadsimplechat_vip_message">'.lang('deadsimplechat warning message').'</span>';
        }
        return $html;
    }

	public function generateDateRangeSplitMonth( $from, $to, $minutes = 10) {
		$result = [];
		$interval = new DateInterval('PT'.$minutes.'M');
		$realEnd = new DateTime($to);
		$realEnd->add($interval);
		$periods = new DatePeriod(new DateTime($from), $interval, $realEnd);
		$count = iterator_count($periods);

		foreach($periods as $key => $period){
			if($key<$count){
				$temp = [];
				$temp['from'] = $period->format('Y-m-d H:i:s');
				$tempTo = $period;
				$tempTo->modify('+'.$minutes.' minutes');
				$temp['to'] = $tempTo->format('Y-m-d H:i:s');
				$result[] = $temp;
			}
		}

		return $result;
    }

    public function groupTransactionsByDate($transactions, $key='transaction_date'){
        $result = [];

        foreach($transactions as $transaction){
            if(isset($transaction[$key])){
                $dateStr = date('Y-m-d', strtotime($transaction[$key]));
                if(!isset($result[$dateStr])){
                    $result[$dateStr] = [];
                }
                $result[$dateStr][] = $transaction;

            }
        }

        return $result;
    }

	/**
	 * Summary of enableRedemptionCodeInPlayerCenter
	 * config enable_redemption_code_system bool
	 * config enable_static_redemption_code_system bool
	 * config enable_redemption_code_system_in_playercenter bool
	 * config redemption_code_promo_cms_id int from promo cms id
	 * @return bool
	 */
	public function enableRedemptionCodeInPlayerCenter() {
		return (($this->utils->getConfig('enable_redemption_code_system') || $this->utils->getConfig('enable_static_redemption_code_system')) && $this->utils->getConfig('enable_redemption_code_system_in_playercenter') && !empty($this->utils->getConfig('redemption_code_promo_cms_id')));
	}

	public function playerTrackingEvent($playerId, $sourceType, $params = array()) {
		if($this->CI->config->item('enable_player_action_trackingevent_system')) {

			$this->debug_log('========= playerTrackingEvent ========= value', [$playerId, $sourceType, $params]);

			$this->CI->load->library(['player_trackingevent_library']);
			switch($sourceType) {
				case 'approveDeposit':
					$this->CI->player_trackingevent_library->approveSaleOrder($playerId, $params);
					break;
				case 'delineDeposit':
					$this->CI->player_trackingevent_library->delineSaleOrder($playerId, $params);
					break;
				case 'common':
				default:
					if(!empty($params) && !is_array($params)){
						$this->debug_log('========= playerTrackingEvent ========= params not array');
						return FALSE;
					}
					$this->CI->player_trackingevent_library->createNotify($playerId, $sourceType, $params);
					break;
			}
		}
		return TRUE;
	}

	public function playerTrackingEventForS2S($playerId, $sourceType, $params = array()) {
		if($this->CI->config->item('enable_player_action_trackingevent_system_by_s2s')) {

			$this->debug_log('========= playerTrackingEvent ========= value', [$playerId, $sourceType, $params]);

			$this->CI->load->library(['player_trackingevent_library']);
			switch($sourceType) {
				case 'approveDeposit':
					$this->CI->player_trackingevent_library->approveSaleOrder($playerId, $params);
					break;
				case 'delineDeposit':
					$this->CI->player_trackingevent_library->delineSaleOrder($playerId, $params);
					break;
				case 'common':
				default:
					if(!empty($params) && !is_array($params)){
						$this->debug_log('========= playerTrackingEvent ========= params not array');
						return FALSE;
					}
					$this->CI->player_trackingevent_library->createNotify($playerId, $sourceType, $params);
					break;
			}
		}
		return TRUE;
	}

    public function pluralize($singular, $plural, $count) {
        if($count > 1) {
            return $plural;
        }else{
            return $singular;
        }
    }

    /**
    * Returns a GUIDv4 string
    *
    * Uses the best cryptographically secure method
    * for all supported pltforms with fallback to an older,
    * less secure version.
    *
    * @param bool $trim
    * @return string
    */
    public function getGUIDv4($trim = true) {
        // Windows
        if(function_exists('com_create_guid') === true) {
            if($trim === true) {
                return trim(com_create_guid(), '{}');
            }else{
                return com_create_guid();
            }
        }

        // OSX/Linux
        if(function_exists('openssl_random_pseudo_bytes') === true) {
            $data = openssl_random_pseudo_bytes(16);
            $data[6] = chr(ord($data[6]) & 0x0f | 0x40);    // set version to 0100
            $data[8] = chr(ord($data[8]) & 0x3f | 0x80);    // set bits 6-7 to 10

            return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
        }

        // Fallback (PHP 4.2+)
        mt_srand((double)microtime() * 10000);
        $charid = strtolower(md5(uniqid(rand(), true)));
        $hyphen = chr(45);                  // "-"
        $lbrace = $trim ? "" : chr(123);    // "{"
        $rbrace = $trim ? "" : chr(125);    // "}"
        $guidv4 = $lbrace.
                  substr($charid,  0,  8).$hyphen.
                  substr($charid,  8,  4).$hyphen.
                  substr($charid, 12,  4).$hyphen.
                  substr($charid, 16,  4).$hyphen.
                  substr($charid, 20, 12).
                  $rbrace;
        return $guidv4;
    }

    public function inverseDateTimeModification($modifier) {
        $get_arithmetic_operator = substr($modifier, 0, 1);
        $get_time = substr($modifier, 1);

        if($get_arithmetic_operator == '+') {
            $inverse = '-' . $get_time;
        }else{
            $inverse = '+' . $get_time;
        }

        return $inverse;
    }

	public function getCapchaSetting($namespace = null) {

		if($namespace) {
			$captcha_by_namespace = $this->utils->getConfig('current_captcha_by_namespace'); // array
			$current_setting = $this->safeGetArray($captcha_by_namespace, $namespace, false);
			if($current_setting) {return $current_setting;}
		}
		return $this->check3rdCapchaSetting();
    }

	public function check3rdCapchaSetting() {
		$c_setting = $this->utils->getConfig('enabled_captcha_of_3rdparty');
		if(empty($c_setting) || !is_array($c_setting)){
			return 'default';
		}
		return $this->safeGetArray($c_setting, "3rdparty_label", "default");
	}

	public function checkThirdPartyCapchaCode($captcha_label, $captcha_code){
		if($captcha_label == 'hcaptcha'){
			return $this->postVerifyhcaptchaCode($captcha_code);
		}
	}

	public function postVerifyhcaptchaCode($captchaCode) {
		$post_url = $this->utils->getConfig('enabled_captcha_of_3rdparty')['verify_url'];
		$socks5_proxy = !empty($this->utils->getConfig('call_socks5_proxy')) ? $this->utils->getConfig('call_socks5_proxy') : null;
		$options = [
			'call_socks5_proxy' => $socks5_proxy,
			'http_timeout' => $this->utils->getConfig('enabled_captcha_of_3rdparty')['hcaptcha_timeout_seconds'],
			'connect_timeout' => $this->utils->getConfig('enabled_captcha_of_3rdparty')['hcaptcha_timeout_seconds']
		];
		$method	= 'POST';
		$params = [
			'secret' => $this->utils->getConfig('enabled_captcha_of_3rdparty')['secret'],
			'response' => $captchaCode
		];

		$response_result = $this->utils->callHttpWithProxy($post_url, $method, $params, $options);
		$json_result = json_decode($response_result[1],true);
		$this->utils->debug_log(__METHOD__,'========register validationHcaptchaToken', $json_result);

		if($json_result['success']){
			return true;
		}else{
			return false;
		}
	}

	/**
	 * To encrypt string with public key
	 * decrypt with private_decrypt()
	 */
	function public_encrypt($plain_text, $pub_key){
		$_pub_key = '-----BEGIN PUBLIC KEY-----' . PHP_EOL . chunk_split($pub_key, 64, PHP_EOL) . '-----END PUBLIC KEY-----' . PHP_EOL;
		$pub_key_res = openssl_get_publickey($_pub_key);
		if(!$pub_key_res) {
			throw new Exception('Public Key invalid');
		}
		openssl_public_encrypt($plain_text, $crypt_text, $pub_key_res, OPENSSL_PKCS1_PADDING);
		openssl_free_key($pub_key_res);
		return base64_encode($crypt_text); // 加密後的內容為 binary 透過 base64_encode() 轉換為 string 方便傳輸
	}
	/**
	 * To decrypt string with public key
	 * encrypt with public_encrypt()
	 */
	function private_decrypt($encrypted_text, $priv_key){
		$_priv_key = '-----BEGIN RSA PRIVATE KEY-----' . PHP_EOL . chunk_split($priv_key, 64, PHP_EOL) . '-----END RSA PRIVATE KEY-----' . PHP_EOL;
		$private_key_res = openssl_get_privatekey($_priv_key);
		// $private_key_res = openssl_get_privatekey($priv_key, PASSPHRASE); // 如果使用密碼
		if(!$private_key_res) {
			throw new Exception('Private Key invalid');
		}

		// 先將密文做 base64_decode() 解釋
		openssl_private_decrypt(base64_decode($encrypted_text), $decrypted, $private_key_res, OPENSSL_PKCS1_PADDING);
		openssl_free_key($private_key_res);
		return $decrypted;
	}

	function number_format_short( $n, $precision = 1 ) {
		if ($n < 1000) {
			// 0 - 999
			$n_format = number_format($n, $precision);
			$suffix = '';
		} else if ($n < 1000000) {
			$n_format = number_format($n / 1000, $precision);
			$suffix = 'K';
		} else if ($n < 1000000000) {
			$n_format = number_format($n / 1000000, $precision);
			$suffix = 'M';
		} else if ($n < 1000000000000) {
			$n_format = number_format($n / 1000000000, $precision);
			$suffix = 'B';
		} else {
			// 1t+
			$n_format = number_format($n / 1000000000000, $precision);
			$suffix = 'T';
		}

		if ( $precision > 0 ) {
			$dotzero = '.' . str_repeat( '0', $precision );
			$n_format = str_replace( $dotzero, '', $n_format );
		}

		return $n_format . $suffix;
	}

    public function trim_multi_array($arr) {
        foreach ($arr as $key => $value) {
            if (is_array($value)) {
                $arr[$key] = trim_multi_array($value);
            } else {
                $arr[$key] = trim($value);
            }
        }
        return $arr;
    }

	public function getRemoteWalletBalanceHistoryTable($dateStr=null){
		$d=new DateTime($dateStr);
		$monthStr=$d->format('Ym');
		return $this->initRemoteWalletBalanceMonthlyTableByDate($monthStr);
	}

	public function initRemoteWalletBalanceMonthlyTableByDate($yearMonthStr){
		$tableName='remote_wallet_balance_history_'.$yearMonthStr;
		return $tableName;
	}

    public function truncateAmount($amount, $precision = 2) {
        $amount = floatval($amount);

        if ($amount == 0) {
            return $amount;
        }

        return floatval(bcdiv($amount, 1, $precision));
    }

    public function customSpecificApisDecimals($game_platform_id, $number, $with_currency_symbol = false) {
        $is_exist = false;
        $custom_specific_apis_decimals = $this->getConfig('main_custom_specific_apis_decimals') + $this->getConfig('add_custom_specific_apis_decimals');

        if (!empty($custom_specific_apis_decimals) && array_key_exists($game_platform_id, $custom_specific_apis_decimals)) {
            $is_exist = true;
            $precision = !empty($custom_specific_apis_decimals[$game_platform_id]['precision']) ? $custom_specific_apis_decimals[$game_platform_id]['precision'] : 2;
            $number = $this->formatCurrency($number, $with_currency_symbol, true ,true, $precision);
        }

        return [$is_exist, $number];
    }

    public function getCostMs() {
        return intval($this->getExecutionTimeToNow() * 1000);
    }

    public function isDemoMode($mode) {
        if(in_array($mode, ['demo', 'trial', 'no-login'])){
            return true;
        }

        return false;
    }
    public function validation_errors_array($prefix = '', $suffix = '', $do_strip_tags = true) {
		$errors_array = [];
		if (FALSE === ($OBJ =& _get_validation_object()))
		{
			return $errors_array;
		}

		$_error_array = $OBJ->error_array();
		foreach ($_error_array as $key => $val) {
            $str = '';
			if ($val != '') {
                $str .= $prefix;
                if($do_strip_tags){
                    $str .= trim(strip_tags($val));
                }else{
                    $str .= trim($val);
                }

				$str .= $suffix;
				$errors_array[$key] = $str;
			}
		}

		return $errors_array;
	}

    /**
     * Collect the a series of list, for the string, that has combined prefix and Line numbers.
     *
     * @param string $_trace_time_list_start The prefix string of Benchmark for begin.
     * @param integer $_line_number_start The line number of Benchmark for begin.
     * @param string $_trace_time_list_end The prefix string of Benchmark for end.
     * @param integer $_line_number_end The line number of Benchmark for begin.
     * @return array The list of key-array,
     * the format as line numbers(, begin and end) and the array of the begin prefix string.
     */
    public function _script_elapsed_time_list(  $_trace_time_list_start
                                , $_line_number_start
                                , $_trace_time_list_end
                                , $_line_number_end
    ){
        global $BM;
        $elapsed_time = [];
        $elapsed_time[$_line_number_start.'_'. $_line_number_end] = [];
        foreach($_trace_time_list_start as $_promoId => $_trace_time_start){
            if( ! empty($_trace_time_list_end[$_promoId]) ){
                $elapsed_time[$_line_number_start.'_'. $_line_number_end][$_promoId] = $BM->elapsed_time($_trace_time_list_start[$_promoId], $_trace_time_list_end[$_promoId]);
            }else{
                $elapsed_time[$_line_number_start.'_'. $_line_number_end][$_promoId] = 'performance_trace_time_'. $_line_number_end. ' is empty';
            }
        }
        return $elapsed_time;
    }

    public function isCustomizedPromoExists($class_name) {
        $models_path = realpath(dirname(__FILE__) . '/../models/');
		$customized_promo_rules_calss_file = 'customized_promo_rules/' . $class_name;

		return file_exists($models_path . '/' . $customized_promo_rules_calss_file . '.php');
	}

	public function loadCustomizedPromoRuleObject($class_name) {
        $models_path = realpath(dirname(__FILE__) . '/../models/');
		$customized_promo_rules_calss_file = 'customized_promo_rules/' . $class_name;

		$this->CI->load->model($customized_promo_rules_calss_file);

		$obj = $this->CI->$class_name;

		if(empty($obj)) return null;

		if(!is_subclass_of($obj, 'Abstract_promo_rule')) return null;

		return $obj;
	}

    public function formatCurrencyStyle($number, $with_symbol = false) {
        $currency_style = $this->utils->getConfig('get_pos_bet_details_currency_style');

        $str = number_format($number, $currency_style['decimals'], $currency_style['decimal_separator'], $currency_style['thousands_separator']);

        if ($with_symbol) {
            $str = $currency_style['symbol'] . ' ' . $str;
        }

        return $str;
    }

	public function getLogoImageUrl($basePath, $path){
		$return = [];
		$return['512x512'] = [];
		$return['512x512'][] = $basePath.$path.'512x512/icon-colored.png';
		$return['512x512'][] = $basePath.$path.'512x512/icon-white.png';
		$return['512x512'][] = $basePath.$path.'512x512/icon-black.png';
		$return['256x256'] = [];
		$return['256x256'][] = $basePath.$path.'256x256/icon-colored.png';
		$return['256x256'][] = $basePath.$path.'256x256/icon-white.png';
		$return['256x256'][] = $basePath.$path.'256x256/icon-black.png';
		$return['128x128'] = [];
		$return['128x128'][] = $basePath.$path.'128x128/icon-colored.png';
		$return['128x128'][] = $basePath.$path.'128x128/icon-white.png';
		$return['128x128'][] = $basePath.$path.'128x128/icon-black.png';

        return $return;
    }

    public function mergeArrayValues($array, $separator = '-', $remove_empty_value = true) {
        if ($remove_empty_value) {
            $array = array_values(array_filter($array));
        }

        $result = array_reduce($array, function ($value1, $value2) use ($separator) {
            return $value1 . $separator . $value2;
        });

        return ltrim($result, $separator);
    }

    public function getCacheSettingOnConfig($item) {
    	$cache_setting = $this->getConfig('cache_setting');
        if(array_key_exists($item, $cache_setting)){
        	return $cache_setting[$item];
        }else{
        	$cache_setting['enable'] = false;
        }
        return $cache_setting;
    }

    /**
     * ref. to https://stackoverflow.com/a/35207936
     *
     * @param array $parts
     * @return void
     */
    public function build_url(array $parts) {

        $builded = '';
        $builded .= ( empty($parts['scheme']) )?'': $parts['scheme']. ':';
        $builded .= ( empty($parts['host'])
                    && empty($parts['user']))?  '': '//';
        $builded .= ( empty($parts['user']))?   '': $parts['user'];
        $builded .= ( empty($parts['pass']))?   '': ':'.$parts['pass'];
        $builded .= ( empty($parts['user']))?   '': '@';
        $builded .= ( empty($parts['host']))?   '': $parts['host'];
        $builded .= ( empty($parts['port']))?   '': ':'.$parts['port'];
        $builded .= ( empty($parts['path']))?   '': $parts['path'];
        $builded .= ( empty($parts['query']))?   '': '?'.$parts['query'];
        $builded .= ( empty($parts['fragment']))?   '': '#'.$parts['fragment'];
        return $builded;

    }

    /**
	 * IP-location lookup method using GeoIP2 database.
	 *
	 * @param	string	$ip		 IP address string
	 * @return	string  $isoCode iso code
	 */
	public function getIpIsoCode($ip) {
		if($this->guessLocalIp($ip)) {
			return '';
		}

		$isoCode = '';
		$cityDB = $this->CI->config->item('ip_city_db_path');
		try {
			$reader = new \GeoIp2\Database\Reader($cityDB);
			$record = $reader->city($ip);
			$isoCode = $record->country->isoCode;
			if (empty($isoCode)) {
				$isoCode = '';
			}

			$reader->close();
		} catch (Exception $e) {
			if (substr($ip, 0, 4) == '172.') {
				return '';
			} else {
				log_message('error', $e->getTraceAsString());
			}
		}
		return $isoCode;
	}

	public function queryAndUpdateRemoteWalletRequestStatus($request){
		$this->CI->load->model(['external_system']);
		$original_id = $request['game_platform_id'];
		$unique_id = $request['external_uniqueid'];
		$success = true;
		$error_message = null;
		$status = null;
		$status_message = null;

		$external_system_id = $this->CI->external_system->getIdByOriginalPlatformId($original_id);
		// //call api
		$api = $this->loadExternalSystemLibObject($external_system_id);
		if(!empty($api)){
			if(!empty($unique_id)){
				$table = $this->getRemoteWalletBalanceHistoryTable($request['transaction_date']);
				$rlt = $api->queryRemoteWalletTransaction($unique_id);
				$this->debug_log('queryAndUpdateRemoteWalletRequestStatus', $rlt);
				if(!empty($rlt)){
					$code_success = 0;
					$code_not_found = 35;
					if(isset($rlt['code']) && $rlt['code'] == $code_success){
						$status_from_success=$rlt['success'] ? Abstract_game_api::COMMON_TRANSACTION_STATUS_APPROVED : Abstract_game_api::COMMON_TRANSACTION_STATUS_UNKNOWN;
						$status= !empty($rlt['detail']['status']) ? $rlt['detail']['status'] : $status_from_success;

						//will ignore unknown
						if(isset($rlt['detail']['status']) && $api->isValidTransferStatus($rlt['detail']['status'])
								&& $rlt['detail']['status']!=Abstract_game_api::COMMON_TRANSACTION_STATUS_UNKNOWN){
							//try update status
							$this->CI->wallet_model->updateRemoteWalletTransferQueryStatus($request['id'], $rlt['detail']['status'], true, $table);
						}


						$status_message= lang("Transaction is exist.");
					} else {
						$success=false;
						if(isset($rlt['code']) && $rlt['code'] == $code_not_found){
							$status = Abstract_game_api::COMMON_TRANSACTION_STATUS_UNKNOWN;
							$this->CI->wallet_model->updateRemoteWalletTransferQueryStatus($request['id'], $status, false, $table);

							$error_message =lang("Transaction not exist.");
						} else {
							$error_message=lang('Call API failed.');
						}
					}
				} else {
					$success=false;
					$error_message=lang('Call API failed.');
				}
			} else{
				$success=false;
				$error_message=lang('Invalid Id');
			}
		}else{
			$success=false;
			$error_message=lang('This game is not available');
		}

		return [$success, $status, $error_message, $status_message];
	}

	public function toCamelCase($string){
		return lcfirst(preg_replace_callback('/_([a-z])/', function($matches) {
			return strtoupper($matches[1]);
		}, $string));
	}

	public function toSnakeCase($string) {
		return strtolower(preg_replace('/[A-Z]/', '_$0', $string));
	}

	public function boolToStringBool($bool){
		return $bool ? 'true' : 'false';
	}

	public function fixRemoteWalletRequest($request){
		$this->CI->load->model(['external_system']);
		$original_id = $request['game_platform_id'];
		$unique_id = $request['external_uniqueid'];
		$success = true;
		$error_message = null;
		$status = null;
		$status_message = null;

		if($request['fix_flag'] == Wallet_model::DB_TRUE){
			//already fixed
			$success=false;
			$error_message=lang('Cannot double fix');
			return [$success, $status, $error_message, $status_message];
		}

		$external_system_id = $this->CI->external_system->getIdByOriginalPlatformId($original_id);
		$request['main_platform_id'] = $external_system_id;
		// //call api
		$api = $this->loadExternalSystemLibObject($external_system_id);
		if(!empty($api)){
			if(!empty($unique_id)){
				$table = $this->getRemoteWalletBalanceHistoryTable($request['transaction_date']);
				$rlt = $api->queryRemoteWalletTransaction($unique_id);
				$this->debug_log('queryAndUpdateRemoteWalletRequestStatus', $rlt);
				if(!empty($rlt)){
					$code_success = 0;
					$code_not_found = 35;
					if(isset($rlt['code']) && $rlt['code'] == $code_success){
						$status_from_success=$rlt['success'] ? Abstract_game_api::COMMON_TRANSACTION_STATUS_APPROVED : Abstract_game_api::COMMON_TRANSACTION_STATUS_UNKNOWN;
						$status= !empty($rlt['detail']['status']) ? $rlt['detail']['status'] : $status_from_success;

						//will ignore unknown
						if(isset($rlt['detail']['status']) && $api->isValidTransferStatus($rlt['detail']['status'])
								&& $rlt['detail']['status']!=Abstract_game_api::COMMON_TRANSACTION_STATUS_UNKNOWN){
							//try update status
							$this->CI->wallet_model->updateRemoteWalletTransferQueryStatus($request['id'], $rlt['detail']['status'], true, $table);
						}


						$status_message= lang("Transaction is exist.");
					} else {
						$success=false;
						if(isset($rlt['code']) && $rlt['code'] == $code_not_found){
							$status = Abstract_game_api::COMMON_TRANSACTION_STATUS_UNKNOWN;
							// $this->CI->wallet_model->updateRemoteWalletTransferQueryStatus($request['id'], $status, false, $table);
							if($request['transaction_type'] == "decrease_balance" || $request['transaction_type'] == "increase_balance"){
								$reason='missing request, auto fix remote wallet';
								$player_id = $request['player_id'];
								$player_username = $this->CI->player_model->getPlayerUsername($player_id)['username'];
								$amount = $request['amount'];
								$new_trans_type = $request['transaction_type'] == "decrease_balance" ? Transactions::MANUAL_ADD_BALANCE : Transactions::MANUAL_SUBTRACT_BALANCE;
								$external_transaction_id = $request['external_uniqueid'];
								$status_of_api = $status;

								$success = $this->CI->wallet_model->lockAndTransForPlayerBalance($player_id, function ()
										use ($player_id, $player_username, $amount, $new_trans_type, $external_transaction_id, $status_of_api, $reason, $request, $rlt, $table, &$error_message) {
									// $success = $this->wallet_model->incMainWallet($player_id, $amount);

									$uniqueid=$request['external_uniqueid'];

									if($request['fix_flag'] == Wallet_model::DB_FALSE){

										$external_transaction_id = $request['external_uniqueid'];
										$game_plaform_id = $request['main_platform_id'];

										$note = 'make up transfer record on main wallet, amount:' . $amount . ', player:' . $player_username . '. reason:' . $reason;
										$really_fix_balance=true;
										$trans_id = $this->CI->transactions->makeUpRemoteWalletTransaction($player_id, $new_trans_type, $game_plaform_id, $game_plaform_id,
											$amount, $note, $really_fix_balance, $this->getNowForMysql(), $external_transaction_id,null, $uniqueid);

										$success=!empty($trans_id);

										$this->debug_log('add to main wallet when auto fix remote request', 'player_id', $player_id, 'amount', $amount, 'trans_id', $trans_id, 'success', $success);

										if($success){
											$exist = false;
											$success = $this->CI->wallet_model->updateRemoteWalletTransferQueryStatus($request['id'], $status_of_api, $exist, $table, Wallet_model::DB_TRUE, $reason);
											if(!$success){
												$this->error_log('update transfer query status failed');
												$error_message=lang('Update transfer status failed');
											}
										}else{
											$error_message=lang('Auto Fix Failed');
										}

										return $success;

									}else{
										$this->debug_log('Cannot double fix:'.$transferFixFlag);
										$error_message=lang('Cannot double fix');
										return false;
									}
								});

								if($success){
									if($new_trans_type == Transactions::MANUAL_ADD_BALANCE){
										$status_message=lang('Auto add balance to player').' '.$player_username.', '.lang('Amount').': '.$amount;
									} else {
										$status_message=lang('Auto deduct balance to player').' '.$player_username.', '.lang('Amount').': '.$amount;
									}
								}
							} else {
								$error_message =lang("Invalid type.");
							}
						} else {
							$error_message=lang('Call API failed.');
						}
					}
				} else {
					$success=false;
					$error_message=lang('Call API failed.');
				}
			} else{
				$success=false;
				$error_message=lang('Invalid Id');
			}
		}else{
			$success=false;
			$error_message=lang('This game is not available');
		}

		return [$success, $status, $error_message, $status_message];
	}

    public function getCmsIdByRouletteName($roulette_name) {
		$roulette_to_cmsid_pair = $this->getConfig('roulette_to_cmsid_pair');
		$roulette_name  = $roulette_name ?: $this->CI->input->post('roulette_name', true);
		$promo_cms_id = $roulette_name && is_array($roulette_to_cmsid_pair) && array_key_exists($roulette_name, $roulette_to_cmsid_pair) ? $roulette_to_cmsid_pair[$roulette_name]: false;
		return $promo_cms_id;
	}

    public function getUrl($use_request_uri = false) {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https://' : 'http://';
        $url = $protocol . $_SERVER['HTTP_HOST'];

        if ($use_request_uri) {
            $url .= $_SERVER['REQUEST_URI'];
        }

        return $url;
    }

    public function randomUsername($prefix = null, $show_player = true, $is_uppercase = false, $usernames = [], $ramdom_type = 'alnum', $length = 8, $random_length = ['use' => true, 'min' => 6, 'max' => 8]) {
        $is_random_length = $random_length['use'] ? $random_length['use'] : true;
        $min_random_length = $random_length['min'] ? $random_length['min'] : 6;
        $max_random_length = $random_length['max'] ? $random_length['max'] : 8;

        if ($is_random_length) {
            $length = rand($min_random_length, $max_random_length);
        }

        if (!empty($usernames)) {
            $name = $usernames[rand (0 , count($usernames) -1)];
        } else {
            $name = random_string($ramdom_type, $length);
        }

        if (is_numeric($name[0])) {
            $name[0] = random_string('alpha', 1);
        }

        if ($is_uppercase) {
            $name = strtoupper($name);
        } else {
            $name = strtolower($name);
        }

        if (!$show_player) {
            $name = substr_replace($name, '***', 1, -2);
        }

        return $prefix . $name;
    }

    // to sort a multi-dimensional array asc
    public function usortAscByArrayKeyValues(&$array, $key) {
        usort($array, function ($a, $b) use ($key) {
            return strnatcmp($a[$key], $b[$key]);
        });
    }

    // to sort a multi-dimensional array desc
    public function usortDescByArraykeyValues(&$array, $key) {
        usort($array, function ($a, $b) use ($key) {
            return strnatcmp($b[$key], $a[$key]);
        });
    }

    public function isTimeout($timeout_at = null) {
        if ($timeout_at >= $this->getNowForMysql()) {
            return true;
        } else {
            return false;
        }
    }

    public function convertSecondsToMinutes($seconds) {
        $minutes = intval(gmdate('i', $seconds));

        if (empty($minutes)) {
            $minutes = 60;
        }

        return $minutes;
    }

    public function microtime_int() {
        return intval(microtime(true) * 1000);
    }

    public function isUrlencoded($string, $use_rawurldecode = true) {
        if ($use_rawurldecode) {
            $urldecoded = rawurldecode($string);
        } else {
            $urldecoded = urldecode($string);
        }

        return $string !== $urldecoded;
    }

    public function unparse_url($parsed_url) {
        $scheme   = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
        $host     = isset($parsed_url['host']) ? $parsed_url['host'] : '';
        $port     = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
        $user     = isset($parsed_url['user']) ? $parsed_url['user'] : '';
        $pass     = isset($parsed_url['pass']) ? ':' . $parsed_url['pass']  : '';
        $pass     = ($user || $pass) ? "$pass@" : '';
        $path     = isset($parsed_url['path']) ? $parsed_url['path'] : '';
        $query    = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
        $fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : '';

        return "{$scheme}{$user}{$pass}{$host}{$port}{$path}{$query}{$fragment}";
    }

	public function getValueOrDefault($key, $array, $default)
	{
		return array_key_exists($key, $array) ? $array[$key] : $default;
	}

    public function getT1GamePlatformIdByOriginalGamePlatformId($game_platform_id) {
        $t1_game_platform_id = null;
        $t1_games_mapping = $this->getConfig('t1_games_mapping');

        if (!empty($t1_games_mapping[$game_platform_id])) {
            $t1_game_platform_id = $t1_games_mapping[$game_platform_id];
        }

        $this->debug_log('OGP-34898', 'game_whitelist', __METHOD__, [
            'game_platform_id' => $game_platform_id,
            't1_game_platform_id' => $t1_game_platform_id,
        ], 't1_games_mapping', $t1_games_mapping);

        return $t1_game_platform_id;
    }

    public function isGameWhitelisted($game_platform_id, $game_code) {
        $is_whitelisted = true;

        // get active games from cache -> command.php -> cache_active_games
        $cache_key = "cache_active_games_{$game_platform_id}";
        $game_codes = $this->getJsonFromCache($cache_key);
        $total_games = 0;

        if (!empty($game_codes) && is_array($game_codes)) {
            $total_games = count($game_codes);

            if (!in_array($game_code, $game_codes)) {
                $is_whitelisted = false;
            }
        }

        $params = [
            'cache_key' => $cache_key,
            'total_games' => $total_games,
            'game_codes' => $game_codes,
        ];

        $this->utils->debug_log('OGP-34898', 'game_whitelist', __METHOD__, 'Data from cache', $params);

        return $is_whitelisted;
    }

    public function isDotNotationValid($dotNotation) {
        return is_string($dotNotation) && strpos($dotNotation, '.') !== false;
    }

    public function getValueFromDotNotation($array, $dotNotation) {
        $keys = explode('.', $dotNotation);

        foreach ($keys as $key) {
            if (!isset($array[$key])) {
                $array = null;
                break;
            }

            $array = $array[$key];
        }

        return $array;
    }

    public function containsColon($param) {
        // Check if the parameter contains a colon (:) using strpos()
        if (strpos($param, ':') !== false) {
            return true; // Colon found
        } else {
            return false; // Colon not found
        }
    }

    public function isMultidimensionalArray($array, $any_array_key_type = false) {
        // default, will check array key numeric
        if (isset($array[0])) {
            return true;
        }

        // will check array key numeric and string
        if ($any_array_key_type) {
            foreach ($array as $value) {
                if (isset($value) && is_array($value)) {
                    return true;
                } else {
                    break;
                }
            }
        }

        return false;
    }

    public function validateRequestParams($request_params, $rule_sets, $strict = true) {
        $is_valid = true;
        $message = 'valid';
        $param = null;

        foreach ($rule_sets as $param => $rules) {
            $is_dot_notation_valid = $this->isDotNotationValid($param);
    
            if ($is_dot_notation_valid) {
                $request_param = $this->getValueFromDotNotation($request_params, $param);
            } else {
                $request_param = isset($request_params[$param]) ? $request_params[$param] : null;
            }
    
            foreach ($rules as $key => $rule) {
                if (is_string($rule) && $this->containsColon($rule)) {
                    $rule = !empty(explode(':', $rule)[0]) ? explode(':', $rule)[0] : $rule;
                    $value = !empty(explode(':', $rule)[1]) ? explode(':', $rule)[1] : 0;
                }
    
                if (is_string($key)) {
                    if (is_array($rule)) {
                        $rule = $key;
                        $value = $rules[$key];
                    } else {
                        $value = $rule;
                        $rule = $key;
                    }
                }
    
                switch ($rule) {
                    case 'optional':
                        $is_valid = true;
                        break;
                    case 'array':
                        if (!is_null($request_param) && !is_array($request_param)) {
                            $is_valid = false;
                            $message = 'Parameter ' . $param . ' must be an ' . $rule;
                        }
                        break;
                    case 'multidimensional_array':
                        if (!is_null($request_param) && !$this->isMultidimensionalArray($request_param, true)) {
                            $is_valid = false;
                            $message = 'Parameter ' . $param . ' must be ' . $rule;
                        }
                        break;
                    case 'nullable':
                        continue 2;
                    case 'required':
                        if ($is_dot_notation_valid) {
                            if (!in_array('nullable', $rules)) {
                                if (empty($request_param)) {
                                    $is_valid = false;
                                    $message = 'Parameter ' . $param . ' is ' . $rule;
                                }
                            }
                        } else {
                            if (in_array('nullable', $rules) || in_array('boolean', $rules)) {
                                if (!array_key_exists($param, $request_params)) {
                                    $is_valid = false;
                                    $message = 'Parameter ' . $param . ' is ' . $rule;
                                }
                            } else {
                                if (!array_key_exists($param, $request_params) || empty($request_param)) {
                                    $is_valid = false;
                                    $message = 'Parameter ' . $param . ' is ' . $rule;
                                }
                            } 
                        }
                        break;
                    case 'string':
                        if (!is_null($request_param) && !is_string($request_param)) {
                            $is_valid = false;
                            $message = 'Parameter ' . $param . ' must be ' . $rule;
                        }
                        break;
                    case 'integer':
                        if (!is_null($request_param) && !is_integer($request_param)) {
                            $is_valid = false;
                            $message = 'Parameter ' . $param . ' must be ' . $rule;
                        }
                        break;
                    case 'float':
                        if (!is_null($request_param) && !is_float($request_param)) {
                            $is_valid = false;
                            $message = 'Parameter ' . $param . ' must be ' . $rule;
                        }
                        break;
                    case 'double':
                        if (!is_null($request_param) && !is_double($request_param)) {
                            $is_valid = false;
                            $message = 'Parameter ' . $param . ' must be ' . $rule;
                        }
                        break;
                    case 'numeric':
                        if (!is_null($request_param) && !is_numeric($request_param)) {
                            $is_valid = false;
                            $message = 'Parameter ' . $param . ' must be ' . $rule;
                        }
                        break;
                    case 'positive':
                        if (!is_null($request_param) && $request_param < 0) {
                            $is_valid = false;
                            $message = 'Parameter ' . $param . ' must be ' . $rule;
                        }
                        break;
                    case 'negative':
                        if (!is_null($request_param) && $request_param > 0) {
                            $is_valid = false;
                            $message = 'Parameter ' . $param . ' must be ' . $rule;
                        }
                        break;
                    case 'greater_than':
                        if (!is_null($request_param) && intval($request_param) <= $value) {
                            $is_valid = false;
                            $message = 'Parameter ' . $param . ' is expected to be ' . str_replace('_', ' ', $rule) . ' ' . $value;
                        }
                        break;
                    case 'less_than':
                        if (!is_null($request_param) && intval($request_param) >= $value) {
                            $is_valid = false;
                            $message = 'Parameter ' . $param . ' must be ' . str_replace('_', ' ', $rule) . ' ' . $value;
                        }
                        break;
                    case 'minimum_size':
                        if (!is_null($request_param) && ($value !== null) && (strlen($request_param) < $value)) {
                            $is_valid = false;
                            $message = 'Parameter ' . $param . ' must be ' . str_replace('_', ' ', $rule) . ' ' . $value;
                        }
                        break;
                    case 'maximum_size':
                        if (!is_null($request_param) && ($value !== null) && (strlen($request_param) > $value)) {
                            $is_valid = false;
                            $message = 'Parameter ' . $param . ' must be ' . str_replace('_', ' ', $rule) . ' ' . $value;
                        }
                        break;
                    case 'boolean':
                        if (!is_null($request_param) && !is_bool($request_param)) {
                            if (in_array($request_param, [0, 1], true)) {
                                $is_valid = true;
                            } else {
                                $is_valid = false;
                                $message = 'Parameter ' . $param . ' must be ' . $rule . ' type';
                            }
                        }
                        break;
                    case 'expected_value':
                        if ($strict) {
                            if (!is_null($request_param) && $request_param != $value) {
                                $is_valid = false;
                                $message = "Invalid parameter {$param}";
                            }
                        } else {
                            if (!is_null($request_param) && strtolower($request_param) != strtolower($value)) {
                                $is_valid = false;
                                $message = "Invalid parameter {$param}";
                            }
                        }
                        break;
                    case 'expected_value_in':
                        if (!is_array($value)) {
                            $is_valid = false;
                            $message = "Rule ({$rule}): must be an array";
                            break;
                        }
    
                        if ($strict) {
                            if (!is_null($request_param) && is_array($value) && !in_array($request_param, $value)) {
                                $is_valid = false;
                                $message = "Invalid parameter {$param}";
                            }
                        } else {
                            if (!is_null($request_param) && is_array($value) && !in_array(strtolower($request_param), $value)) {
                                $is_valid = false;
                                $message = "Invalid parameter {$param}";
                            }
                        }
                        break;
                    case 'ip_address':
                        if (!is_null($request_param) && !filter_var($request_param, FILTER_VALIDATE_IP, [FILTER_FLAG_IPV4, FILTER_FLAG_IPV6])) {
                            $is_valid = false;
                            $message = "Invalid parameter {$param}, must be a valid IP address";
                        }
                        break;
                    case 'min':
                        if (!is_null($request_param)) {
                            if (is_string($request_param) && strlen($request_param) < $value) {
                                $is_valid = false;
                                $message = 'Parameter ' . $param . ' ' . str_replace('_', ' ', $rule) . ' length ' . $value;
                                break;
                            }
    
                            if (is_numeric($request_param) && $request_param < $value) {
                                $is_valid = false;
                                $message = "Parameter {$param} {$rule} {$value}";
                                break;
                            }
                        }
                        break;
                    case 'max':
                        if (!is_null($request_param)) {
                            if (is_string($request_param) && strlen($request_param) > $value) {
                                $is_valid = false;
                                $message = 'Parameter ' . $param . ' ' . str_replace('_', ' ', $rule) . ' length ' . $value;
                                break;
                            }
    
                            if (is_numeric($request_param) && $request_param > $value) {
                                $is_valid = false;
                                $message = "Parameter {$param} {$rule} {$value}";
                                break;
                            }
                        }
                        break;
                    case 'min_length':
                        if (!is_null($request_param) && strlen($request_param) < $value) {
                            $is_valid = false;
                            $message = 'Parameter ' . $param . ' ' . str_replace('_', ' ', $rule) . ' length ' . $value;
                            break;
                        }
                        break;
                    case 'max_length':
                        if (!is_null($request_param) && strlen($request_param) > $value) {
                            $is_valid = false;
                            $message = 'Parameter ' . $param . ' ' . str_replace('_', ' ', $rule) . ' length ' . $value;
                            break;
                        }
                        break;
                    default:
                        $is_valid = false;
                        $message = "Invalid rule '" . $rule . "' on parameter '" . $param . "'";
                        break;
                }
            }

            if (!$is_valid) {
                break;
            }
        }

        $result = [
            'is_valid' => $is_valid,
            'message' => $message,
        ];

        if (!$is_valid) {
            $result['key'] = $param;
        }
    
        return (object) $result;
    }

	public function gameAmountToDBTruncateNumber($amount, $custom_precision = null, $custom_conversion_rate = null) {
        if($amount==0){
            return $amount;
        }
		$conversion_rate = floatval($this->getConfig('utils_conversion_rate'));
		$precision = floatval($this->getConfig('utils_precision'));

		if(!$conversion_rate){
			$conversion_rate = 1;
		}
		if(!$precision){
			$precision = 2;
		}
		if(!empty($custom_conversion_rate)){
			$conversion_rate = $custom_conversion_rate;
		}
		if(!empty($custom_precision)){
			$precision = $custom_precision;
		}
        //compute amount with conversion rate
        $value = floatval($amount / $conversion_rate);

        return bcdiv($value, 1, $precision);
    }

	public function dBtoGameAmount($amount,$custom_precision = null, $custom_conversion_rate = null) {
		$conversion_rate = floatval($this->getConfig('utils_conversion_rate'));
		$precision = floatval($this->getConfig('utils_precision'));
		if(!$conversion_rate){
			$conversion_rate = 1;
		}
		if(!$precision){
			$precision = 2;
		}

		if (!empty($custom_conversion_rate)) {
			$conversion_rate = $custom_conversion_rate;
		}

		if (!empty($custom_precision)) {
			$precision = $custom_precision;
		}
        $value = floatval($amount * $conversion_rate);
        return round($value,$precision);
    }

    public function isTableExists($table_name, $db = null) {
        if (empty($db)) {
            $db = $this->CI->db;
        }
    
        $query = $db->query("SELECT 1 FROM information_schema.tables WHERE table_name = " . $db->escape($table_name) . " AND table_schema = " . $db->escape($db->database));
        return $query->num_rows() > 0;
    }

    public function createTableLike($createTableName, $likeTableName) {
        $success = false;

        if (!is_string($createTableName) || !is_string($likeTableName)) {
            throw new \InvalidArgumentException("Table names must be valid strings.");
        }

        if (!$this->isTableExists($createTableName)) {
            try {
                $this->debug_log(__METHOD__, "Attempting to create table '{$createTableName}' like '{$likeTableName}'");
                $this->CI->load->model(['external_system']);
                $this->CI->external_system->runRawUpdateInsertSQL("CREATE TABLE {$createTableName} LIKE {$likeTableName}");
                
                $this->debug_log(__METHOD__, "Table created successfully: {$createTableName}");
                $success = true;
            } catch (\Exception $e) {
                $this->debug_log(__METHOD__, "Failed to create table '{$createTableName}' like '{$likeTableName}'");
                $this->debug_log(__METHOD__, "Error: " . $e->getMessage());
            }
        } else {
            // $this->debug_log(__METHOD__, "Table '{$createTableName}' already exists. No action taken.");
            $success = true;
        }

        return $success;
    }

    public function isIncludedInGamesReportSearch($key) {
        $excludedInGamesReportSearch = $this->utils->getConfig('excluded_in_games_report_search');

        if (!in_array($key, $excludedInGamesReportSearch)) {
            return true;
        }

        return false;
    }

    public function isNotEmptyArray($array) {
        return !empty($array) && is_array($array);
    }
}

////END OF FILE
