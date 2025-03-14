<?php
require_once dirname(__FILE__) . '/abstract_roulette_api.php';

/**
 * R26871_API
 *
 * Required Fields:
 *
 * * api_key - api key given by system
 * * token - Effective token for player
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Roulette_api_r26871 extends Abstract_roulette_api {

	const DAILY_SPIN_TIMES = 10;
	const CONSUMPTION = 10000;
	const MAX_AMOUNT_LIMIT = 49999;
	const API_PREFIX_R26871 = 'r26871';

	public function getDailySpinTimes($description = null) {
		return (! is_null($description) && isset($description['daily_spin_limits'])) ? $description['daily_spin_limits'] : self::DAILY_SPIN_TIMES;
	}

	public function getConsumption($description = null) {
		return (! is_null($description) && isset($description['per_amount'])) ? $description['per_amount'] : self::CONSUMPTION;
	}

	public function getMaxAmountLimit($description = null) {
		return (! is_null($description) && isset($description['max_amount_limit'])) ? $description['max_amount_limit'] : self::MAX_AMOUNT_LIMIT;
	}

	public function getSpinCondition() {
		return self::SPIN_CONDITION_DEPOSIT;
	}

	public function getPlatformCode(){
		return self::R26871_API;
	}

	public function getPrefix() {
		return self::API_PREFIX_R26871;
	}

	public function getRouletteType($roulette_name){
		$type = '';
		switch ($roulette_name) {
			case self::API_PREFIX_R26871:
				$type = $this->getPlatformCode();
                break;
		}
		$this->utils->debug_log(__METHOD__, 'type res', ['type' => $type]);
		return $type;
	}

	public function processAfteraApply($player_id, $promo_cms_id, $roulette_name = null, $is_pcf_api = false){
		$this->generateRouletteSpinTimes($player_id, $roulette_name, true);
		if ($is_pcf_api) {
			$this->getRouletteRecords(null, null, 'DESC', 20, null, $player_id, $is_pcf_api);
		}else{
			$this->getRouletteWinningList(null, null, null, null, $player_id, true);
		}
	}

	//====implements roulette_api_interface start===================================
	public function generateRouletteSpinTimes($player_id, $roulette_name, $refreshCache = false) {
		$this->utils->debug_log(__METHOD__, 'start', [ 'player_id' => $player_id, 'roulette_name' => $roulette_name]);

        $cache_key="RouletteSpinTimes-$player_id-$roulette_name";

        $cachedResult = $this->utils->getJsonFromCache($cache_key);
        if(!empty($cachedResult) && !$refreshCache && !$this->preventCacheData($roulette_name)) {
			$cachedResult['ch'] = true;
			$this->utils->debug_log(__METHOD__, 'spin_times_res', ['spin_times_res' => $cachedResult]);
         	return $cachedResult;
        }

		try {
			$spin_times_res = $this->verifyRouletteSpinTimes($player_id, $roulette_name);
			$this->utils->debug_log(__METHOD__, 'spin_times_res', ['spin_times_res' => $spin_times_res]);

			if (!$spin_times_res) {
				 throw new Exception(lang('You are not allowed to join due to invalid applicaton period!'), self::NOT_ALLOWED_JOIN);
			}

			$res = [
				"success" => true,
				"type" => $this->getSpinCondition(),
				"spin_times_data" => $spin_times_res,
				"mesg" => lang('Get daily spin times success'),
			];

			$description = $this->rouletteDescription($roulette_name);
			$cache_ttl = isset($description['ttl']) ? $description['ttl'] : 20;
			// $ttl = 20 * 60; // 20 minutes
			$ttl = $cache_ttl * 60; // 60 minutes
			$this->utils->saveJsonToCache($cache_key, $res, $ttl);

			return $res;

		} catch (Exception $ex) {
			return [
				'success' => false, 'code' => $ex->getCode(), 'mesg' => $ex->getMessage()
			];
		}
	}

	/**
	 * detail:verify Roulette Spin Times
	 * 檢查當下是否還有可用次數
	 * @return array
	 */
	public function verifyRouletteSpinTimes($player_id, $roulette_name){
		// $start_date = $this->utils->getTodayForMysql() .' '.Utils::FIRST_TIME;
		$today_date = $this->utils->getTodayForMysql();
		$description = $this->rouletteDescription($roulette_name);
		$allowed_day = isset($description['allowed_day']) ? $description['allowed_day'] : 1;
		$week_start = isset($description['week_start']) ? $description['week_start'] : 'monday';

		$this->utils->debug_log(__METHOD__. " {$player_id}", [ 'today_date' => $today_date, 'description' => $description, 'allowed_day' => $allowed_day, 'week_start' => $week_start]);

		//check today is monday
		if (date('w', strtotime($today_date)) != $allowed_day) {
			return false;
		}

		$extra_info['week_start'] = $week_start;
		$start_date = $this->getDateType(self::DATE_LAST_WEEK_START, $extra_info);

		// $retentionTime = $this->getRetentionTime($this->getPrefix(), $roulette_name);
		$retention_spin_times_res = [
			"total_times" => 0,
			"used_times" => 0,
			"remain_times" => 0,
		];
		// if(!$this->utils->isTimeoutNow($start_date, $retentionTime)){
		// 	$retention_start = $this->utils->getYesterdayForMysql() .' '.Utils::FIRST_TIME;
		// 	$retention_end = $this->utils->getYesterdayForMysql() .' '.Utils::LAST_TIME;
		// 	$retention_spin_times_res = $this->generatePlayerRoulette($retention_start, $retention_end, $player_id, $roulette_name);
		// }
		// $end_date = $this->utils->getNowForMysql();
		$end_date = $this->getDateType(self::DATE_LAST_WEEK_END, $extra_info);

		$spin_times_res = $this->generatePlayerRoulette($start_date, $end_date, $player_id, $roulette_name);
		$spin_times_res['total_times'] = $spin_times_res['total_times'] + $retention_spin_times_res['total_times'];
		$spin_times_res['used_times'] = $spin_times_res['used_times'] + $retention_spin_times_res['used_times'];
		$spin_times_res["remain_times"] = $spin_times_res["remain_times"] + $retention_spin_times_res["remain_times"];
		$spin_times_res['getRetention'] = $retention_spin_times_res;
		$spin_times_res['valid_date'] = (!empty($retention_start) && ($retention_spin_times_res["remain_times"]!=0))? $this->utils->formatDateForMysql(new \DateTime($retention_start)): $this->utils->formatDateForMysql(new \DateTime($start_date));

		return $spin_times_res;
	}
	
	public function createRoulette($player_id, $rt_data, $sub_wallet_id = null, $group_level_id = null, $roulette_time = null, $promo_info=null) {
		$this->CI->load->model(array('roulette_api_record'));
		$this->utils->debug_log(__METHOD__, 'start', [ 'player_id' => $player_id, 'rt_data' => $rt_data]);

		try {
			//create roulette record
			$roulette_id = $this->CI->roulette_api_record->add($rt_data);
			
			$this->utils->debug_log(__METHOD__, 'roulette result', [ 'roulette_id' => $roulette_id]);

			$res = [
				"success" => true,
				"type" => $this->getSpinCondition(),
				"roulette_id" => $roulette_id,
				"mesg" => lang('Apply roulette bonus success'),
			];

			return $res;
			
		} catch (Exception $ex) {
			return [
				'success' => false, 'code' => $ex->getCode(), 'mesg' => $ex->getMessage()
			];
		}
	}

	public function generatePlayerRoulette($start_date, $end_date, $player_id, $roulette_name){
		$description = $this->rouletteDescription($roulette_name);
		$consumption = $this->getConsumption($description);
		$max_amount_limit = $this->getMaxAmountLimit($description);
		$daily_times = $this->getDailySpinTimes($description);

		$additional_spin_player_list = isset($description['additional_spin_player_list']) ? $description['additional_spin_player_list'] : [];
		$additional_spin_times = isset($description['additional_spin_times']) ? $description['additional_spin_times'] : 0;

		if (in_array($player_id, $additional_spin_player_list)) {
			$daily_times = $daily_times + $additional_spin_times;
		}

		$this->utils->debug_log(__METHOD__." Found player_id {$player_id}",[
			'description' => $description,
			'roulette_name' => $roulette_name,
			'consumption'=> $consumption,
			'max_amount_limit'=> $max_amount_limit,
			'daily_times'=> $daily_times
		]);

		$start_date = !empty($description['allowed_date_start']) ? $description['allowed_date_start'] : $start_date;
		$end_date = !empty($description['allowed_date_end']) ? $description['allowed_date_end'] : $end_date;

		$date_today_start = !empty($description['release_date_start']) ? $description['release_date_start'] : $this->getDateType(self::DATE_TODAY_START);
		$date_now = !empty($description['release_date_end']) ? $description['release_date_end'] : $this->getDateType(self::TO_TYPE_NOW);

		$deposit = $this->sumDepositAmount($player_id, $start_date, $end_date);
		$player_used_times = $this->countRouletteRecord($player_id, $this->getPlatformCode(), $date_today_start, $date_now);
		$spin_times_res = $this->calculateSpinTimes($this->getSpinCondition(), $daily_times, $player_used_times, $deposit, $consumption, $max_amount_limit, $description);

		$this->utils->debug_log(__METHOD__, 'player deposit betting and spin_times_res', [
			'spin_times_res' => $spin_times_res,
			'start_date' => $start_date,
			'end_date' => $end_date,
			'date_today_start' => $date_today_start,
			'date_now' => $date_now
		]);

		return $spin_times_res;
	}
	public function calculateSpinTimes($type, $daily_times, $player_used_times, $deposit_amount, $consumption, $max_amount_limit = null, $description = null){
		$this->utils->debug_log(__METHOD__, 'start', [ 'type' => $type, 'daily_times' => $daily_times, 'player_used_times' => $player_used_times, 'deposit_amount' => $deposit_amount]);

		$times_by_amount = 0;
		$total_times = 0;
		$used_times = 0;
		$remain_times = 0;

		if ($type == self::SPIN_CONDITION_BET) {
			// $times_by_amount = floor(($deposit_amount / $consumption));
			// $total_times = $times_by_amount > $daily_times ? $daily_times : $times_by_amount;
			// $used_times = $player_used_times;
			// $remain = $total_times - $used_times;
			// $remain_times = $remain > 0 ? $remain : $remain_times;
		}elseif ($type == self::SPIN_CONDITION_DEPOSIT) {
			if ($deposit_amount >= $consumption && $deposit_amount <= $max_amount_limit) {
				$times_by_amount = $daily_times;
			}
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

	public function getPlayerBetAndDepositAmount($player_id, $start_date = null, $end_date = null){
		if (empty($start_date)) {
			$start_date = $this->utils->getTodayForMysql() .' '.Utils::FIRST_TIME;
		}
		if (empty($end_date)) {
			$end_date = $this->utils->getNowForMysql();
		}
		return $this->getBetsAndDepositByDate($player_id, $start_date, $end_date);
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

			$award = 1;
			while ($award <= 1) {
				$rid = $this->get_rand($arr);//根據概率獲取獎項id
				$chance_res =[];
				foreach ($prize_arr as $key => $val) {
					if ($val['id'] == $rid) {
						$chance_res = $val;
						$this->utils->debug_log(__METHOD__, 'start',$rid, $val);
						if (isset($val['month_limit'])) {
							if (!$this->checkPrizeLimit($val['product_id'], $val['month_limit'])) {
								$award++;
								break;
							}else{
								$this->utils->debug_log(__METHOD__, 'roulette again',$rid);
							}
						}else if (isset($val['daily_limit'])) {
							if (!$this->checkDailyPrizeLimit($val['product_id'], $val['daily_limit'], null)) {
								$award++;
								break;
							}else{
								$this->utils->debug_log(__METHOD__, 'roulette again',$rid);
							}
						}else{
							$award++;
							break;
						}
					}
				}
			}

			$chance_res['rid'] = $rid;
			$this->utils->debug_log(__METHOD__, 'chance_res', ['chance_res' => $chance_res, 'arr' => $arr, 'rid' => $rid]);

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

	public function rouletteDescription($roulette_name){
		$description = $this->CI->utils->getConfig('roulette_description');
		$this->utils->debug_log(__METHOD__,[ 'description' => $description]);
		$description = empty($description[$roulette_name]) ? null : $description[$roulette_name];

		return $description;
	}

}
