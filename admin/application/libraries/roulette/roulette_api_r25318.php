<?php
require_once dirname(__FILE__) . '/abstract_roulette_api.php';

/**
 * R25318_API
 *
 * Required Fields:
 *
 * * api_key - api key given by system
 * * token - Effective token for player
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Roulette_api_r25318 extends Abstract_roulette_api {

	const DAILY_SPIN_TIMES = 5;
	const CONSUMPTION = 1000;
	const API_PREFIX_R25318 = 'r25318';

	public function getDailySpinTimes() {
		return self::DAILY_SPIN_TIMES;
	}

	public function getSpinCondition() {
		return self::SPIN_CONDITION_DEPOSIT;
	}

	public function getPlatformCode(){
		return self::R25318_API;
	}

	public function getPrefix() {
		return self::API_PREFIX_R25318;
	}

	public function getRouletteType($roulette_name){
		$type = '';
		switch ($roulette_name) {
			case self::API_PREFIX_R25318:
				$type = $this->getPlatformCode();
                break;
		}
		$this->utils->debug_log(__METHOD__, 'type res', ['type' => $type]);
		return $type;
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

	public function generatePlayerRoulette($start_date, $end_date, $player_id, $promo_cms_id){
		list($bets, $deposit) = $this->getBetsAndDepositByDate($player_id, $start_date, $end_date);
		$player_used_times = $this->countPlayerPromoByDate($player_id, $promo_cms_id, $start_date, $end_date);
		$this->CI->utils->printLastSQL();
		$spin_times_res = $this->calculateSpinTimes($this->getSpinCondition(), $this->getDailySpinTimes(), $player_used_times, (int)$deposit, self::CONSUMPTION);
		// $spin_times_res = $this->calculateSpinTimes($this->getSpinCondition(), $this->getDailySpinTimes(), $player_used_times, '100000', self::CONSUMPTION);

		$spin_times_res['deposit'] = $deposit;

		$this->utils->debug_log(__METHOD__, 'player deposit betting and spin_times_res', [ 'bets' => $bets, 'deposit' => $deposit, 'spin_times_res' => $spin_times_res]);

		return $spin_times_res;
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
}
