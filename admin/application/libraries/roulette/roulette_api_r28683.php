<?php
require_once dirname(__FILE__) . '/abstract_roulette_api.php';

/**
 * R28683_API
 *
 * Required Fields:
 *
 * * api_key - api key given by system
 * * token - Effective token for player
 *
 *
 * @category roulette
 * @copyright 2013-2023 tot
 */
class Roulette_api_r28683 extends Abstract_roulette_api {

	const DAILY_SPIN_TIMES = 4;
	const CONSUMPTION = 0;
	const MAX_AMOUNT_LIMIT = 0;
	const DEFAULT_CYCLE_HOUR = 6;
	const API_PREFIX_R28683 = 'r28683';

	public function getDailySpinTimes($description = []) {
		// return (! is_null($description) && isset($description['daily_spin_limits'])) ? $description['daily_spin_limits'] : self::DAILY_SPIN_TIMES;
		return $this->CI->utils->safeGetArray($description, 'daily_spin_limits', self::DAILY_SPIN_TIMES);
	}

	public function getConsumption($description = []) {
		// return (! is_null($description) && isset($description['per_amount'])) ? $description['per_amount'] : self::CONSUMPTION;
		return $this->CI->utils->safeGetArray($description, 'per_amount', self::CONSUMPTION);
	}

	public function getMaxAmountLimit($description = []) {
		// return (! is_null($description) && isset($description['max_amount_limit'])) ? $description['max_amount_limit'] : self::MAX_AMOUNT_LIMIT;
		return $this->CI->utils->safeGetArray($description, 'max_amount_limit', self::MAX_AMOUNT_LIMIT);
	}

	public function getSpinCondition() {
		return self::SPIN_CONDITION_TIMEDIFF;
	}

	public function getPlatformCode(){
		return self::R28683_API;
	}

	public function getPrefix() {
		return self::API_PREFIX_R28683;
	}

	public function getRouletteType(){
		return $this->getPlatformCode();
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

		$spin_times_res = $this->generatePlayerRoulette($start_date, $end_date, $player_id, $roulette_name);
		$spin_times_res['valid_date'] = $this->utils->formatDateForMysql(new \DateTime($start_date));
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
		$daily_times = $this->getDailySpinTimes($description);

		$this->utils->debug_log(__METHOD__." Found player_id {$player_id}",[
			'roulette_name' => $roulette_name,
			'description' => $description,
			'daily_times'=> $daily_times
		]);

		$start_date = !empty($description['allowed_date_start']) ? $description['allowed_date_start'] : $start_date;
		$end_date = !empty($description['allowed_date_end']) ? $description['allowed_date_end'] : $end_date;

		$date_today_start = !empty($description['release_date_start']) ? $description['release_date_start'] : $this->getDateType(self::DATE_TODAY_START);
		$date_now = !empty($description['release_date_end']) ? $description['release_date_end'] : $this->getDateType(self::TO_TYPE_NOW);

		// $player_used_times = $this->countRouletteRecord($player_id, $this->getPlatformCode(), $date_today_start, $date_now);
		
		// $lastRecord = $this->getLastRecord( $player_id, 13, null, '2022-12-06 21:08:25');
		$lastRecord = $this->getLastRecord( $player_id, $this->getPlatformCode());
		$lastApplyAt = null;
		// $lastApplyAt = '2023-03-29 23:08:25';
		$used_times = $this->countRouletteRecord($player_id, $this->getPlatformCode(), $date_today_start, $date_now);
		$remain_times = 0;
		$total_times = $used_times;

		if($this->utils->safeGetArray($lastRecord, 'created_at', false)) {

			$lastApplyAt = $lastRecord['created_at'];
		}

		if($lastApplyAt != null) {
			$cycle_hour = self::DEFAULT_CYCLE_HOUR;
			if(isset($description['cycle'])) {

				$cycle_hour = $this->utils->safeGetArray($description['cycle'], 'hour');
			}
			$lastT = new DateTime($lastApplyAt);
			$currentT = new DateTime($date_now);
			$interval = $currentT->diff($lastT);

			$diff_in_hours = $interval->h; // hours
			$diff_in_hours += $interval->days * 24;
			if((int)$diff_in_hours >= (int)$cycle_hour && (int)$used_times < $daily_times) {
				$remain_times = 1;
			}
		}else {
			$remain_times = 1;
		}
		$total_times += $remain_times;
		
		$spin_times_res = array(
			"total_times" => (int)$total_times,
			"used_times" => (int)$used_times,
			"remain_times" => (int)$remain_times,
			"last_apply_at" => $lastApplyAt
		);	
		
		$this->utils->debug_log(__METHOD__, 'player deposit betting and spin_times_res', [
			'spin_times_res' => $spin_times_res,
			'start_date' => $start_date,
			'end_date' => $end_date,
			'date_today_start' => $date_today_start,
			'date_now' => $date_now
		]);
		// $spin_times_res = $this->calculateSpinTimes($this->getSpinCondition(), $daily_times, $player_used_times, $date_now, $consumption, $description);
		return $spin_times_res;

	}

	public function calculateSpinTimes($type, $daily_times, $player_used_times, $date_now, $consumption, $player_used_times_by_pid = null, $description = []){
		$this->utils->debug_log(__METHOD__, 'start', [ 'type' => $type, 'daily_times' => $daily_times, 'player_used_times' => $player_used_times]);

		$times_by_amount = 0;
		$total_times = 0;
		$used_times = 0;
		$remain_times = 0;
		$per_transaction = $description['per_transaction'];

		$count = count($deposit_list);

		if ($count > 1) {
			$times_by_amount = $count * $per_transaction;
		}elseif($count == 1){
			$times_by_amount = $per_transaction;
		}

		if ($p_total_times > 0) {
			$times_by_amount = $times_by_amount + $p_total_times;
		}

		$total_times = $times_by_amount > $daily_times ? $daily_times : $times_by_amount;
		$used_times = $player_used_times;
		$remain = $total_times - $used_times;
		$remain_times = $remain > 0 ? $remain : $remain_times;

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
			if($chance_res['bonus'] == 0 ){
				$chance_res['skip_tran'] = true;
			}
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

		if(empty($description)) {
			return array();
		}

		// $description = empty($description[$roulette_name]) ? null : $description[$roulette_name];
		$description = $this->utils->safeGetArray($description, $roulette_name, []);
		$this->utils->debug_log(__METHOD__,[ 'description' => $description]);
		return $description;
	}

}
