<?php
require_once dirname(__FILE__) . '/roulette_api_interface.php';

/**
 * Defines general behavior of roulette API classes.
 *
 * General behaviors include:
 * * Creating Roulette bonus
 * * Generating Roulette Spin Times
 * * Reading Roulette Winning List
 *
 * @category roulette
 * @version 1.0
 * @copyright 2013-2022 tot
 * 
 * @property Utila $utils
 */
 abstract class Abstract_roulette_api implements Roulette_api_interface {

	function __construct($params = null) {
		$this->CI = &get_instance();
		$this->DAILY_SPIN_TIMES = $this->getDailySpinTimes();
		$this->SPIN_CONDITION = $this->getSpinCondition();
		$this->PLATFORM_CODE = $this->getPlatformCode();
		$this->CI->load->model(array('sale_order', 'wallet_model','playerbankdetails','roulette_api_record', 'player_additional_roulette', 'player_model'));
		$this->CI->load->helper('string');
		$this->utils = $this->CI->utils;
	}

	protected $DAILY_SPIN_TIMES;
	protected $SPIN_CONDITION;
	protected $PLATFORM_CODE;

	const NORMAL_API_1 = 1;
	const SUPER_API_1 = 2;
	const NORMAL_API_2 = 3;
    const SUPER_API_2 = 4;
	const R25318_API = 5;
	const R26255_API = 6;
	const R26256_API = 7;
	const R26755_API = 8;
	const R26756_API = 9;
	const R26871_API = 10;
	const R26872_API = 11;
	const R27831N_API = 12;
	const R27831S_API = 13;
	const R28024_API = 14;
	const R28025_API = 15;
	const R28561_API = 16;
	const R28683_API = 17;
	const R29620_API = 18;
	const R29758_API = 19;
	const R29757_API = 20;
	const R30774_API = 21;
	const R30970_API = 22;
	const R31492_API = 23;
	const R31682_API = 24;
	const R31874_API = 25;
	const R32439_API = 26;
    const R33827_USD_API = 27;
    const R33827_PHP_API = 28;
    const R33827_JPY_API = 29;

	
	const SPIN_CONDITION_DEPOSIT = 'deposit';
	const SPIN_CONDITION_BET = 'bet';
	const SPIN_CONDITION_TIMEDIFF = 'timediff';

	const ALLOW_DEPOSIT = 1;
    const ALLOW_WITHDRAW = 2;

    const NOT_ALLOWED_JOIN = 0x384;

    const TO_TYPE_NOW = 'now';
	const DATE_YESTERDAY_START = 'yesterday_start';
	const DATE_YESTERDAY_END = 'yesterday_end';
	const DATE_TODAY_START = 'today_start';
	const DATE_TODAY_END = 'today_end';
	const DATE_LAST_WEEK_START = 'last_week_start';
	const DATE_LAST_WEEK_END = 'last_week_end';
	const DATE_THIS_WEEK_START = 'this_week_start';
	const DATE_THIS_WEEK_END = 'this_week_end';
	const LAST_RELEASE_BONUS_TIME = 'last_release_bonus_time';
	const DATE_LAST_MONTH_START = 'last_month_start';
	const DATE_LAST_MONTH_END = 'last_month_end';
	const DATE_THIS_MONTH_START = 'this_month_start';
	const DATE_THIS_MONTH_END = 'this_month_end';
	const DATE_THIS_WEEK_CUSTOM = 'this_week_custom';
	const REGISTER_DATE = 'register_date';
	const BEFORE_LAST_LOGIN_TIME = 'before_last_login_time';

	const MODULE_TYPE_PROMO = 'promo';
	const MODULE_TYPE_TRANS = 'trans';

	abstract public function getDailySpinTimes();
	abstract public function getSpinCondition();
	abstract public function getPlatformCode();
	abstract public function getPrefix();
	abstract public function generatePlayerRoulette($start_date, $end_date, $player_id, $promo_cms_id);
	
	public function getModuleType() {
		return self::MODULE_TYPE_TRANS;
	}
	//====implements roulette_api_interface start===================================
	public function generateRouletteSpinTimes($player_id, $promo_cms_id, $refreshCache = false) {
		$this->utils->debug_log(__METHOD__, 'start', [ 'player_id' => $player_id, 'promo_cms_id' => $promo_cms_id]);

		$cache_key="RouletteSpinTimes-$player_id-$promo_cms_id";

		$cachedResult = $this->utils->getJsonFromCache($cache_key);
		if(!empty($cachedResult) && !$refreshCache && !$this->preventCacheData($promo_cms_id)) {
			$cachedResult['ch'] = true;
			$this->utils->debug_log(__METHOD__, 'spin_times_res', ['spin_times_res' => $cachedResult]);
			return $cachedResult;
		}

		try {
			$spin_times_res = $this->verifyRouletteSpinTimes($player_id, $promo_cms_id);
			$this->utils->debug_log(__METHOD__, 'spin_times_res', ['spin_times_res' => $spin_times_res]);

			$res = [
				"success" => true,
				"type" => $this->getSpinCondition(),
				"spin_times_data" => $spin_times_res,
				"mesg" => lang('Get daily spin times success'),
			];

			// $ttl = 20 * 60; // 20 minutes
			$ttl = 1 * 60; // 1 minutes
			$this->utils->saveJsonToCache($cache_key, $res, $ttl);

			return $res;
			
		} catch (Exception $ex) {
			return [
				'success' => false, 'code' => $ex->getCode(), 'mesg' => $ex->getMessage()
			];
		}
	}
	
	public function createRoulette($player_id, $rt_data, $sub_wallet_id, $group_level_id = null,$roulette_time = null, $promo_info=null) {}

	public function processAfteraApply($player_id, $promo_cms_id, $roulette_name = null, $is_pcf_api = false){
		$this->generateRouletteSpinTimes($player_id, $promo_cms_id, true);
		if ($is_pcf_api) {
			$this->getRouletteRecords(null, null, 'DESC', 20, null, $player_id, $is_pcf_api);
		}else{
			$this->getRouletteWinningList(null, null, null, null, $player_id, true);
		}
	}
	/**
	 * detail: get Roulette Winning List
	 *
	 * @return array
	 */
	public function getRouletteWinningList($start_date, $end_date, $offset, $limit, $player_id = null, $refreshCache= false){

		if (empty($start_date)) {
			$start_date = $this->utils->getTodayForMysql() .' '.Utils::FIRST_TIME;
		}
		if (empty($end_date)) {
			$end_date = $this->utils->getNowForMysql();
		}
		$rouletteprefix = $this->getPrefix();
		$cache_key="getRouletteWinningList-$player_id-$rouletteprefix";

		$roulette_name  = $rouletteprefix;
		$promo_cms_id = $this->getCmsIdByRouletteName($roulette_name);
		$cachedResult = $this->utils->getJsonFromCache($cache_key);
		if(!empty($cachedResult) && !$refreshCache && !$this->preventCacheData($promo_cms_id)) {
			$this->utils->debug_log(__METHOD__, 'WinningList get from cache', [ 'cachedResult' => $cachedResult]);
			return $cachedResult;
		}

		$roulette_list = $this->CI->roulette_api_record->rouletteList($start_date, $end_date, $player_id, $this->getPlatformCode());
		$roulette_list = array_slice($roulette_list, $offset, $limit);
		if($this->getDesc()) {

			foreach ($roulette_list as $key => $record) {
				$roulette_list[$key]['type'] =  $this->getDesc();
			}
		}

		$ttl = 5 * 60; // 5 minutes
		$this->utils->saveJsonToCache($cache_key, $roulette_list, $ttl);

		$this->CI->utils->printLastSQL();
		$this->utils->debug_log(__METHOD__, 'WinningList', [ 'roulette_list' => $roulette_list]);
		return $roulette_list;
	}

	public function getDesc() {
		return false;
	}

	public function getRouletteRecords($start_date, $end_date, $sort, $limit, $page, $player_id = null, $refreshCache= false){
		if (empty($start_date)) {
			$start_date = $this->utils->getTodayForMysql() .' '.Utils::FIRST_TIME;
		}
		if (empty($end_date)) {
			$end_date = $this->utils->getNowForMysql();
		}
		$this->utils->debug_log(__METHOD__, ['start_date' => $start_date, 'end_date' => $end_date]);
		$rouletteprefix = $this->getPrefix();
		$cache_key="getRouletteRecords-$player_id-$rouletteprefix-$start_date-$end_date-$limit-$page";

		$roulette_name  = $rouletteprefix;
		$promo_cms_id = $this->getCmsIdByRouletteName($roulette_name);
		$cachedResult = $this->utils->getJsonFromCache($cache_key);
		if(!empty($cachedResult) && !$refreshCache && !$this->preventCacheData($promo_cms_id)) {
			$this->utils->debug_log(__METHOD__, 'roulette records from cache', [ 'cachedResult' => $cachedResult]);
			return $cachedResult;
		}
		$roulette_list = $this->CI->roulette_api_record->getPlayerRoulettePagination($player_id, $this->getPlatformCode(), $start_date, $end_date, $limit, $page, $sort);

		$ttl = 1 * 5; // 2 minutes
		$this->utils->saveJsonToCache($cache_key, $roulette_list, $ttl);

		$this->CI->utils->printLastSQL();
		$this->utils->debug_log(__METHOD__, ['roulette_list' => $roulette_list]);
		return $roulette_list;
	}

	public function getRouletteLatest($start_date, $end_date, $offset, $limit, $player_id = null, $refreshCache= false){
		if (empty($start_date)) {
			$start_date = $this->utils->getTodayForMysql() .' '.Utils::FIRST_TIME;
		}
		if (empty($end_date)) {
			$end_date = $this->utils->getNowForMysql();
		}
		$rouletteprefix = $this->getPrefix();
		$cache_key="getRouletteLatest-$player_id-$rouletteprefix-$start_date-$end_date-$limit-$offset";

		$roulette_name  = $rouletteprefix;
		$promo_cms_id = $this->getCmsIdByRouletteName($roulette_name);
		$cachedResult = $this->utils->getJsonFromCache($cache_key);
		if(!empty($cachedResult) && !$refreshCache && !$this->preventCacheData($promo_cms_id)) {
			$this->utils->debug_log(__METHOD__, 'roulette latest get from cache', [ 'cachedResult' => $cachedResult]);
			return $cachedResult;
		}

		$roulette_list = $this->CI->roulette_api_record->rouletteList($start_date, $end_date, $player_id, $this->getPlatformCode());
		$roulette_list = array_slice($roulette_list, $offset, $limit);

		$ttl = 1 * 5; // 5s
		$this->utils->saveJsonToCache($cache_key, $roulette_list, $ttl);

		$this->CI->utils->printLastSQL();
		$this->utils->debug_log(__METHOD__, 'latest', [ 'roulette_list' => $roulette_list]);
		return $roulette_list;
	}

	/**
	 * detail:verify Roulette Spin Times
	 * 檢查當下是否還有可用次數
	 * @return array
	 */
	public function verifyRouletteSpinTimes($player_id, $promo_cms_id){
		$start_date = $this->utils->getTodayForMysql() .' '.Utils::FIRST_TIME;

		$retentionTime = $this->getRetentionTime($this->getPrefix(), $promo_cms_id);
		$retention_spin_times_res = [
			"total_times" => 0,
			"used_times" => 0,
			"remain_times" => 0,
		];
		if(!$this->utils->isTimeoutNow($start_date, $retentionTime)){
			$retention_start = $this->utils->getYesterdayForMysql() .' '.Utils::FIRST_TIME;
			$retention_end = $this->utils->getYesterdayForMysql() .' '.Utils::LAST_TIME;
			$retention_spin_times_res = $this->generatePlayerRoulette($retention_start, $retention_end, $player_id, $promo_cms_id);
		}
		$end_date = $this->utils->getNowForMysql();

		// list($bets, $deposit) = $this->getBetsAndDepositByDate($player_id, $start_date, $end_date);
		// $player_used_times = $this->countPlayerPromoByDate($player_id, $promo_cms_id, $start_date, $end_date);
		// $this->CI->utils->printLastSQL();
		// $spin_times_res = $this->calculateSpinTimes($this->getSpinCondition(), $this->getDailySpinTimes(), $player_used_times, (int)$deposit, self::CONSUMPTION);

		$spin_times_res = $this->generatePlayerRoulette($start_date, $end_date, $player_id, $promo_cms_id);
		$spin_times_res['total_times'] = $spin_times_res['total_times'] + $retention_spin_times_res['total_times'];
		$spin_times_res['used_times'] = $spin_times_res['used_times'] + $retention_spin_times_res['used_times'];
		$spin_times_res["remain_times"] = $spin_times_res["remain_times"] + $retention_spin_times_res["remain_times"];
		$spin_times_res['getRetention'] = $retention_spin_times_res;
		$spin_times_res['valid_date'] = (!empty($retention_start) && ($retention_spin_times_res["remain_times"]!=0))? $this->utils->formatDateForMysql(new \DateTime($retention_start)): $this->utils->formatDateForMysql(new \DateTime($start_date));
		
		$spin_times_res['base'] = $this->utils->safeGetArray($spin_times_res, 'base', 0);

		return $spin_times_res;
	}

	/**
	 * detail:get Player Bet And Deposit Amount by Date
	 * 
	 * @return array
	 */
	public function getPlayerBetAndDepositAmount($player_id, $start_date = null, $end_date = null){
		if (empty($start_date)) {
			$start_date = $this->utils->getTodayForMysql() .' '.Utils::FIRST_TIME;
		}
		if (empty($end_date)) {
			$end_date = $this->utils->getNowForMysql();
		}
		$rouletteprefix = $this->getPrefix();
		$cache_key="getPlayerBetAndDepositAmount-$player_id";

		$roulette_name  = $rouletteprefix;
		$promo_cms_id = $this->getCmsIdByRouletteName($roulette_name);
		$cachedResult = $this->utils->getJsonFromCache($cache_key);
		if(!empty($cachedResult) && !$this->preventCacheData($promo_cms_id)) {
			$this->utils->debug_log(__METHOD__, 'WinningList get from cache', [ 'roulette_list' => $cachedResult]);
		return $cachedResult;
		}
		list($bets, $deposit) = $this->getBetsAndDepositByDate($player_id, $start_date, $end_date);
		
		$roulette_name  = $this->CI->input->post('roulette_name', true);
		$promo_cms_id = $this->getCmsIdByRouletteName($roulette_name);
		$promoRuleDescription = $this->getPromoRuleDescription($promo_cms_id);
		$usePlayerReportRecords = !empty($promoRuleDescription) && array_key_exists('usePlayerReportRecords', $promoRuleDescription) && ($promoRuleDescription['usePlayerReportRecords'] == true);
		if(!$usePlayerReportRecords) {
			$deposit = $this->CI->transactions->totalDepositByPlayerAndDateTime($player_id, $start_date, $end_date);
		}
		$ttl = 5 * 60; // 5 minutes
		$this->utils->saveJsonToCache($cache_key, [$bets, $deposit], $ttl);
		return [$bets, $deposit];
	}

	//====implements roulette_api_interface end===================================

	public function calculateSpinTimes($type, $daily_times, $player_used_times, $amount, $consumption, $player_used_times_by_pid = null, $description = null){
		$this->utils->debug_log(__METHOD__, 'start', [ 'type' => $type, 'daily_times' => $daily_times, 'player_used_times' => $player_used_times, 'amount' => $amount]);

		$total_times = 0;
		$used_times = 0;
		$remain_times = 0;

		if ($type == self::SPIN_CONDITION_BET) {
			$times_by_amount = floor(($amount / $consumption));
			$total_times = $times_by_amount > $daily_times ? $daily_times : $times_by_amount;
			$used_times = $player_used_times;
			$remain = $total_times - $used_times;
			$remain_times = $remain > 0 ? $remain : $remain_times;
		}elseif ($type == self::SPIN_CONDITION_DEPOSIT) {
			$times_by_amount = floor(($amount / $consumption));
			$total_times = $times_by_amount > $daily_times ? $daily_times : $times_by_amount;
			$used_times = $player_used_times;
			$remain = $total_times - $used_times;
			$remain_times = $remain > 0 ? $remain : $remain_times;
		}
		return [
			"total_times" => floor($total_times),
			"used_times" => floor($used_times),
			"remain_times" => floor($remain_times)
		];
	}

	/**
	 * getBetsAndDepositByDate
	 * @param  string $start_date
	 * @param  string $end_date
	 * @return array [$bets, $deposit]
	 */
	public function getBetsAndDepositByDate($player_id, $start_date, $end_date){
		$this->CI->load->model(['player_model']);
		$this->utils->debug_log(__METHOD__, 'bet and deposit amount date time', ['player_id' => $player_id, 'start_date' => $start_date , 'end_date' => $end_date]);
		return $this->CI->player_model->getBetsAndDepositByDate($player_id, $start_date, $end_date);
	}

	/**
	 * count player promo
	 * @param  int $player_id
	 * @param  string $start_date
	 * @param  string $end_date
	 * @return int $result
	 */
	public function countPlayerPromoByDate($player_id, $promo_cms_id, $start_date, $end_date){
		$this->CI->load->model(array('roulette_api_record','promorules'));
		$result = 0;
		if(!empty($player_id) && !empty($promo_cms_id)){
			$this->utils->debug_log(__METHOD__,['start_date'=> $start_date, 'end_date' => $end_date, 'player_id' => $player_id, 'promo_cms_id' => $promo_cms_id]);
			$cnt = $this->CI->roulette_api_record->countPlayerPromoRecordByCurrentDate($player_id, $promo_cms_id, $start_date);
			if(empty($cnt)){
				$result= 0;
			}else{
				$result= $cnt;
			}
		}
		return $result;
	}

	public function getRetentionTime($config_key = 'default', $promo_cms_id = null)
	{
		$found_promo_setting = false;
		$retention_time = 0;
		$bonus_release = null;
		if($promo_cms_id) {
			$promoRuleDescription = $this->getPromoRuleDescription($promo_cms_id);
			if(empty($promoRuleDescription)) {

				// list($promorule, $promoCmsSettingId) = $this->CI->promorules->getByCmsPromoCodeOrId($promo_cms_id);
				list($promorule, $promoCmsSettingId) = $this->getByCmsPromoCodeOrIdCache($promo_cms_id);
				$formula = json_decode($promorule['formula'], true);
				// $this->utils->debug_log('527.974.formula:', $formula);
				if( ! empty($formula['bonus_release']) ){
					$bonus_release = $this->utils->json_decode_handleErr($formula['bonus_release'], true);
					if( ! is_null($bonus_release) && isset($bonus_release['retention_time'])) {
						// $formula['bonus_release'] = $bonus_release;
						$retention_time = $bonus_release['retention_time'];
						$found_promo_setting = true;
					}
				}
			} else {
				$bonus_release = $promoRuleDescription;
				if( ! is_null($bonus_release) && isset($bonus_release['retention_time'])) {
					// $formula['bonus_release'] = $bonus_release;
					$retention_time = $bonus_release['retention_time'];
					$found_promo_setting = true;
				}
			}

			$this->utils->debug_log(__METHOD__." Found cms id {$promo_cms_id}",['bonus_release'=> $bonus_release, 'retention_time'=>$retention_time]);
		}

		if(!$found_promo_setting) {

			$retention_time_config = $this->CI->utils->getConfig('roulette_retention_time');
			if($retention_time_config) {

				$retention_time = isset($retention_time_config[$config_key]) ? $retention_time_config[$config_key] : ( isset($retention_time_config['default'])? $retention_time_config['default']: 0);
			}
			$this->utils->debug_log(__METHOD__." Not Found cms id",['config'=> $retention_time_config, 'retention_time'=>$retention_time]);
		}
		return $retention_time;
	}

	protected function getPromoRuleDescription($promo_cms_id){
		$description = null;
		$cache_key="PromoRuleDescription-$promo_cms_id";

		$cachedResult = $this->utils->getJsonFromCache($cache_key);
		if(!empty($cachedResult)) {
			$this->utils->debug_log(__METHOD__, 'PromoRuleDescription from cache', ['cachedResult' => $cachedResult]);
			return $cachedResult;
		}
		if($promo_cms_id) {
			// list($promorule, $promoCmsSettingId) = $this->CI->promorules->getByCmsPromoCodeOrId($promo_cms_id);
			list($promorule, $promoCmsSettingId) = $this->getByCmsPromoCodeOrIdCache($promo_cms_id);
			$formula = json_decode($promorule['formula'], true);
			if( ! empty($formula['bonus_release']) ){
				$description = $this->CI->utils->json_decode_handleErr($formula['bonus_release'], true);

				$ttl = 10 * 60; // 20 minutes
				$this->utils->saveJsonToCache($cache_key, $description, $ttl);
			}
		}
		return $description;
	}

	protected function getByCmsPromoCodeOrIdCache($promo_cms_id, $refreshCache = false){
		$cache_key="getByCmsPromoCodeOrIdCache-$promo_cms_id";

		$res = [];
		$cachedResult = $this->utils->getJsonFromCache($cache_key);
		if(!empty($cachedResult) && !$refreshCache) {
			$cachedResult['ch'] = true;
			$res = $cachedResult;
			$this->utils->debug_log(__METHOD__, 'getByCmsPromoCodeOrIdCache get from cache', ['res' => $res]);
		}else{
			// ist($promorule, $promoCmsSettingId) = $this->CI->promorules->getByCmsPromoCodeOrId($promo_cms_id);
			list($promorule, $promoCmsSettingId) = $this->CI->promorules->getByPromoCmsId($promo_cms_id);
			// $ttl = 20 * 60; // 20 minutes
			$ttl = 10 * 60; // 1 minutes
			$res = [$promorule, $promoCmsSettingId];
			$this->utils->saveJsonToCache($cache_key, $res, $ttl);
		}

		return $res;
	}

	protected function preventCacheData($promo_cms_id) {
		$PromoRuleDescription = $this->getPromoRuleDescription($promo_cms_id);
		return $PromoRuleDescription && !empty($PromoRuleDescription['preventCacheData']);
	}

	public function getCmsIdByRouletteName($roulette_name) {
        return $this->utils->getCmsIdByRouletteName($roulette_name);
		// $roulette_to_cmsid_pair = $this->CI->utils->getConfig('roulette_to_cmsid_pair');
		// $roulette_name  = $roulette_name ?: $this->CI->input->post('roulette_name', true);
		// $promo_cms_id = $roulette_name && is_array($roulette_to_cmsid_pair) && array_key_exists($roulette_name, $roulette_to_cmsid_pair) ? $roulette_to_cmsid_pair[$roulette_name]: false;
		// return $promo_cms_id;
	}

	public function generateAdditionalSpin($quantity, $player_id, $source_promo_rule_id, $source_player_promo_id, $generate_by, $exp_at = null)
	{
		for ($i = 0; $i < $quantity; $i++) {
			$succ = $this->createAdditionalSpin($player_id, $source_promo_rule_id, $source_player_promo_id, $generate_by, $exp_at);
			if (!$succ) {
				$i--;
			}
		}
		// return $this->createAdditionalSpin($player_id, $source_promo_rule_id, $source_player_promo_id, $generate_by, $exp_at);
		return ($i == $quantity);
	}
	public function createAdditionalSpin($player_id, $source_promo_rule_id, $source_player_promo_id, $generate_by, $exp_at = null) {
		$this->CI->load->model(array('player_additional_roulette'));
		$newSpin = [
			'roulette_type' => $this->getPlatformCode(),
			'source_promo_rule_id' => $source_promo_rule_id,
			'source_player_promo_id' => $source_player_promo_id,
			'player_id' => $player_id,
			'generate_by' => $generate_by,
			'expired_at' => $exp_at,
		];
		$result = $this->CI->player_additional_roulette->add($newSpin);
		if($result) {
			return true;
		}
		return false;
	}

	public function getAdditionalSpin($player_id, $start_date, $end_date, $force_get_available_spin = false) {

		$roulette_type = $this->getPlatformCode();
		$this->utils->debug_log(__METHOD__." params {$player_id}", [ 'start_date' => $start_date, 'end_date' => $end_date, 'roulette_type' => $roulette_type]);

		$format_start_date = $this->utils->formatDateForMysql(new \DateTime($start_date));
		$current_date =  $this->utils->getTodayForMysql();
		$this->CI->load->model(array('player_additional_roulette'));
		$availableSpin = 0;
		$usedSpin = 0;
		if($format_start_date == $current_date || $force_get_available_spin) {
			
			$availableSpin = $this->CI->player_additional_roulette->getAvailableSpin($player_id, $roulette_type, $current_date);
		}
		$usedSpin = $this->CI->player_additional_roulette->getUsedSpinByDate($player_id, $roulette_type, $start_date, $end_date);

		$targetAdditionalSpin = $this->CI->player_additional_roulette->getFirstAvailableSpin($player_id, $this->getPlatformCode(), $start_date);

		return [$availableSpin, $usedSpin, $targetAdditionalSpin];
	}

	public function listDepositTransactions($player_id, $from_datetime, $to_datetime, $min_amount = null, $max_amount = null) {
		$this->CI->load->model(['transactions']);
		return $this->CI->transactions->getDepositListBy($player_id, $from_datetime, $to_datetime, $min_amount, $max_amount);
	}

	public function countRouletteTransactions($player_id, $from_datetime, $to_datetime) {
		$this->CI->load->model(['transactions']);
		return  $this->CI->transactions->countRouletteByPlayerId($player_id, $from_datetime, $to_datetime);
	}

	public function sumDepositAmount($player_id, $from_datetime, $to_datetime, $min_amount = 0) {
		$this->CI->load->model(['transactions']);
		return  $this->CI->transactions->sumDepositAmount($player_id, $from_datetime, $to_datetime, $min_amount);
	}

	public function countRouletteRecord($player_id, $platform_code, $from_datetime, $to_datetime) {
		$this->CI->load->model(['roulette_api_record']);
		return  $this->CI->roulette_api_record->countRouletteById($player_id, $platform_code, $from_datetime, $to_datetime);
	}

	/**
	 * function getLastRecord to get the last record from table roulette_api_record
	 *
	 * @param integer $playerId
	 * @param integer $roulette_type roulette_api_record::ROULETTE_NAME_TYPES
	 * @param string $periodFrom '2020-01-01 00:00:00'
	 * @param string $periodTo '2020-01-01 00:00:00'
	 * @param integer $limit
	 * @return void
	 */
	public function getLastRecord($playerId, $roulette_type, $periodFrom = null, $periodTo = null, $limit = 1 ) {
		return  $this->CI->roulette_api_record->getLastRecord($playerId, $roulette_type, $limit, $periodFrom, $periodTo);
	}

	public function getDateType($type, $extra_info = NULL){

		$val=null;

		switch ($type){
			case self::DATE_YESTERDAY_START:
				$d=$this->utils->getYesterdayForMysql().' '.Utils::FIRST_TIME;
				break;
			case self::DATE_YESTERDAY_END:
				$d=$this->utils->getYesterdayForMysql().' '.Utils::LAST_TIME;
				break;
			case self::DATE_TODAY_START:
				$d=$this->utils->getTodayForMysql().' '.Utils::FIRST_TIME;
				break;
			case self::DATE_TODAY_END:
				$d=$this->utils->getTodayForMysql().' '.Utils::LAST_TIME;
				break;
			case self::TO_TYPE_NOW:
				$d=$this->utils->getNowForMysql();
				break;
			case self::DATE_LAST_WEEK_START:
				$week_start = (isset($extra_info['week_start'])) ? $extra_info['week_start'] : 'sunday';
				$previous_week = strtotime('-1 week +1 day', strtotime($this->utils->getNowForMysql()));
				$dt = new DateTime();
				$dt->setTimestamp(strtotime('last ' . $week_start . ' midnight', $previous_week));

				$d=$this->utils->formatDateForMysql($dt).' '.Utils::FIRST_TIME;
				break;
		    case self::DATE_LAST_WEEK_END:
				$week_start = (isset($extra_info['week_start'])) ? $extra_info['week_start'] : 'sunday';
				$previous_week = strtotime("-1 week +1 day", strtotime($this->utils->getNowForMysql()));
				$dt = new DateTime();
				$dt->setTimestamp(strtotime('last ' . $week_start . ' +6 day', $previous_week));

				$d=$this->utils->formatDateForMysql($dt).' '.Utils::LAST_TIME;
				break;
			case self::DATE_THIS_WEEK_START:
			//always monday
				$d=date('Y-m-d H:i:s', strtotime('midnight monday this week'));
				break;
			case self::DATE_THIS_WEEK_END:
				//to now
				$d=$this->utils->getNowForMysql();
				break;
			case self::DATE_THIS_WEEK_CUSTOM:
				$d=date('Y-m-d H:i:s', strtotime('midnight'. $extra_info .'this week'));
				break;
			case self::DATE_LAST_MONTH_START:
				$d=date('Y-m-d', strtotime('first day of last month')).' '.Utils::FIRST_TIME;
				break;
			case self::DATE_LAST_MONTH_END:
				$d=date('Y-m-d', strtotime('last day of last month')).' '.Utils::LAST_TIME;
				break;
			case self::DATE_THIS_MONTH_START:
				//always 01 of current month
				$d=date('Y-m-d H:i:s', strtotime('midnight first day of this month'));
				break;
			case self::DATE_THIS_MONTH_END:
				//always 30, 31 or 28,29
				$d=date('Y-m-d H:i:s', strtotime('midnight first day of next month -1 second'));
				break;
			case self::REGISTER_DATE:
				$getPlayerInfoById = $this->getPlayerInfoById($this->playerId);
				$d=$getPlayerInfoById['playerCreatedOn'];
				break;
			case self::BEFORE_LAST_LOGIN_TIME:
				$getPlayerInfoById = $this->getPlayerInfoById($this->playerId);
				$d=$getPlayerInfoById['before_last_login_time'];
				break;
			case self::LAST_RELEASE_BONUS_TIME:
				$this->load->model(['player_promo']);
				$row=$this->player_promo->getLastReleasedPlayerPromo($this->playerId, $this->promorule['promorulesId']);
				if(!empty($row)){
					$d=$row['dateProcessed'];
				}else{
					$d=self::EMPTY_DATETIME;
				}

				break;
			default:
				$d=new DateTime($type);
				$d=$this->utils->formatDateTimeForMysql($d);
				break;
		}

		return $d;
	}

	public function checkPrizeLimit($product_id, $month_limit){
		$this->CI->load->model(array('roulette_api_record'));

		if ($month_limit < 1) {
			$this->utils->debug_log(__METHOD__,[ 'checkPrizeLimit month_limit' => $month_limit]);
			return true;
		}

		$month_start = date('Y-m-d H:i:s', strtotime('midnight first day of this month'));
		$now = $this->utils->getNowForMysql();
		$result = $this->CI->roulette_api_record->checkPrizeLimit($product_id, $month_start, $now, $month_limit);

		$this->utils->debug_log(__METHOD__,[ 'checkPrizeLimit result' => $result]);

		return $result;
	}
    public function get_rand($proArr) {
		$result = '';//選中的值
		//概率數組的總概率精度
		if(is_array($proArr)){
			$proSum = array_sum($proArr);//計算陣列值的總數
			// print_r($proSum);
		 	//print_r($proArr);exit;
			//概率數組循環 
			foreach ($proArr as $key => $proCur) {
				//$proCur中獎率
				$randNum = mt_rand(1, $proSum);//1~10000挑1 
				if ($randNum <= $proCur) { 
					$result = $key;//小於設定的中獎率就成立
					break; 
				} else { 
				//不中同時把該項目中獎率從總中獎率減掉
					$proSum -= $proCur;//中獎率總和減該項目中獎率
				} 
			}
			unset ($proArr);
		}
		return $result; 
	}

    public function playerRouletteRewardOdds() {
		$this->utils->debug_log(__METHOD__, 'start');

		try {
			$prize_arr = $this->CI->utils->getConfig('roulette_reward_odds_settings');
			$prize_arr = empty($prize_arr[$this->getPrefix()]) ? false : $prize_arr[$this->getPrefix()];

			if (!$prize_arr) {
				return [
					'success' => false, 'code' => 0, 'mesg' => lang("Can't not found Roulette Reward Odds setting")
				];
			}

			foreach ($prize_arr as $key => $val) { 
				$arr[$val['id']] = $val['v'];//為了算總和
			}

			$rid = $this->get_rand($arr);//根據概率獲取獎項id
			$prize_item = '';
			$bonus = 0;
			foreach ($prize_arr as $key => $val) {
				if ($val['id'] == $rid) {
                    $chance_res = $val;
					$prize_item = $val['prize'];
					$bonus = $val['bonus'];
					break;
				}
			}
            
            $chance_res['rid'] = $rid;
            $chance_res['prize'] = $prize_item;
            $chance_res['bonus'] = $bonus;
			$this->utils->debug_log(__METHOD__, 'chance_res', ['prize_arr' => $prize_arr, 'chance_res' => $chance_res, 'arr' => $arr]);

			$res = [
				"success" => true,
				"type" => $this->getSpinCondition(),
				"chance_res" => $chance_res,
				"mesg" => lang('Get player roulette Chance success'),
			];
			return $res;
			
		} catch (Exception $ex) {
			return [
				'success' => false, 'code' => $ex->getCode(), 'mesg' => $ex->getMessage()
			];
		}
	}

    public function checkDailyPrizeLimit($product_id, $daily_limit, $player_id){
		$this->CI->load->model(array('roulette_api_record'));

		if ($daily_limit < 1) {
			$this->utils->debug_log(__METHOD__,[ 'checkDailyPrizeLimit daily_limit' => $daily_limit]);
			return true;
		}

		$daily_start = $this->getDateType(self::DATE_TODAY_START);
		$now = $this->utils->getNowForMysql();
		$result = $this->CI->roulette_api_record->checkPrizeLimit($product_id, $daily_start, $now, $daily_limit, $player_id);

		$this->utils->debug_log(__METHOD__,[ 'checkDailyPrizeLimit result' => $result]);

		return $result;
	}

	public function returnUnimplemented() {
		return array('success' => true, 'unimplemented' => true);
	}

	protected function getTimeoutSecond() {
		return $this->CI->utils->getConfig('default_http_timeout');
	}

	protected function getConnectTimeout() {
		return $this->CI->utils->getConfig('default_connect_timeout');
	}

	public function getClientIP() {
		return $this->CI->utils->getIP();
	}

	public function validateWhiteIp($ip){
		return empty($this->white_ip_list) || in_array($ip, $this->white_ip_list);
	}

	public function getCurrentTimeStamp() {
		return time();
	}

	public function lockResourceBy($anyId, $action, &$lockedKey) {
		return $this->CI->utils->lockResourceBy($anyId, $action, $lockedKey);
	}

	public function releaseResourceBy($anyId, $action, &$lockedKey) {
		return $this->CI->utils->releaseResourceBy($anyId, $action, $lockedKey);
	}

	public function lockPlayerBalanceResource($player_id, &$lockedKey){
		return $this->CI->utils->lockResourceBy($player_id, Utils::LOCK_ACTION_BALANCE, $lockedKey);
	}

	public function releasePlayerBalanceResource($player_id, &$lockedKey){
		return $this->CI->utils->releaseResourceBy($player_id, Utils::LOCK_ACTION_BALANCE, $lockedKey);
	}

	public function isAllowDeposit(){
		return $this->allowDepositWithdraw && ($this->allowDepositWithdraw & self::ALLOW_DEPOSIT) > 0;
	}

	public function isAllowWithdraw(){
		return $this->allowDepositWithdraw && ($this->allowDepositWithdraw & self::ALLOW_WITHDRAW) > 0;
	}

	public function getSpinByGenerateBy($playerId, $generate_by, $status = null, $start_date = null, $end_date = null, $exp_at = null){
		return $this->CI->player_additional_roulette->getSpinByGenerateBy($playerId, $generate_by, $this->getPlatformCode(), $status, $start_date, $end_date,  $exp_at);
	}
}
///END OF FILE///////////
