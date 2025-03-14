<?php
require_once dirname(__FILE__) . '/abstract_roulette_api.php';

/**
 * R28025_API
 *
 * Required Fields:
 *
 * * api_key - api key given by system
 * * token - Effective token for player
 *
 * 1. 不論存款綁過優惠或轉完再綁都可計入
 * 2. 單筆存款 >= 10000 給三次大輪, 每日有2筆transaction可得到,當日最多可轉6輪
 * 3. 每日優惠,跨日重計
 * 4. 1/20 - 1/22三日
 * 5. 取款條件 bonus X5
 * 6. 每日只能存在一次大獎
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Roulette_api_r28025 extends Abstract_roulette_api {

	const DAILY_SPIN_TIMES = 6;
	const CONSUMPTION = 10000;
	const MAX_AMOUNT_LIMIT = 999999999999;
	const API_PREFIX_R28025 = 'r28025';

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
		return self::R28025_API;
	}

	public function getPrefix() {
		return self::API_PREFIX_R28025;
	}

	public function getRouletteType($roulette_name){
		$type = '';
		switch ($roulette_name) {
			case self::API_PREFIX_R28025:
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
	public function generateRouletteSpinTimes($player_id, $roulette_name, $refreshCache = true) {
		$this->utils->debug_log(__METHOD__, 'start', [ 'player_id' => $player_id, 'roulette_name' => $roulette_name]);

        $cache_key="RouletteSpinTimes-$player_id-$roulette_name";

        $cachedResult = $this->utils->getJsonFromCache($cache_key);
        if(!empty($cachedResult) && !$refreshCache && !$this->preventCacheData($roulette_name)) {
			$cachedResult['ch'] = true;
			$this->utils->debug_log(__METHOD__, 'spin_times_res', ['cachedResult' => $cachedResult]);
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
		$today_date = $this->utils->getTodayForMysql();
		$description = $this->rouletteDescription($roulette_name);
		$allowed_day = isset($description['allowed_day']) ? $description['allowed_day'] : [];//20,21,22
		$start_date = $this->getDateType(self::DATE_TODAY_START);
		$end_date = $this->getDateType(self::TO_TYPE_NOW);

		$this->utils->debug_log(__METHOD__. " {$player_id}", [ 'today_date' => $today_date, 'description' => $description, 'allowed_day' => $allowed_day, 'start_date' => $start_date, 'end_date' => $end_date]);

		//check today is allowed day
		if (!in_array(date('d', strtotime($today_date)), $allowed_day)) {
			return false;
		}

		$retention_spin_times_res = [
			"total_times" => 0,
			"used_times" => 0,
			"remain_times" => 0,
		];

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

		$deposit_list = $this->listDepositTransactions($player_id, $start_date, $end_date, $consumption, $max_amount_limit);
		// $player_used_times = $this->countRouletteTransactions($player_id, $date_today_start, $date_now);// count player roulette transaction
		$player_used_times = $this->countRouletteRecord($player_id, $this->getPlatformCode(), $date_today_start, $date_now);
		$spin_times_res = $this->calculateSpinTimes($this->getSpinCondition(), $daily_times, $player_used_times, $deposit_list, $consumption, $max_amount_limit, $description);

		$this->utils->debug_log(__METHOD__, 'player deposit betting and spin_times_res', [
			'spin_times_res' => $spin_times_res,
			'start_date' => $start_date,
			'end_date' => $end_date,
			'date_today_start' => $date_today_start,
			'date_now' => $date_now
		]);

		return $spin_times_res;
	}

	public function calculateSpinTimes($type, $daily_times, $player_used_times, $deposit_list, $consumption, $max_amount_limit = null, $description = null){
		$this->utils->debug_log(__METHOD__, 'start', [ 'type' => $type, 'daily_times' => $daily_times, 'player_used_times' => $player_used_times, 'deposit_list' => $deposit_list]);

		$times_by_amount = 0;
		$total_times = 0;
		$used_times = 0;
		$remain_times = 0;
		$per_transaction = $description['per_transaction'];

		if (!empty($deposit_list)) {
			$count = count($deposit_list);

			if ($count > 1) {
				$times_by_amount = $count * $per_transaction;
			}elseif($count == 1){
				$times_by_amount = $per_transaction;
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
		$this->CI->load->library(array('authentication'));
		$player_id = $this->CI->authentication->getPlayerId();
		$this->utils->debug_log(__METHOD__, 'start player_id',$player_id);

		try {
			$prize_arr = $this->CI->utils->getConfig('roulette_reward_odds_settings');
			$prize_arr = empty($prize_arr[$this->getPrefix()]) ? false : $prize_arr[$this->getPrefix()];

			$description = $this->rouletteDescription($this->getPrefix());

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
						if (isset($val['daily_limit'])) {
							if (!$this->checkDailyPrizeLimit($val['product_id'], $val['daily_limit'], $player_id)) {
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
		$description = empty($description[$roulette_name]) ? null : $description[$roulette_name];
		$this->utils->debug_log(__METHOD__,[ 'description' => $description]);

		return $description;
	}

}
