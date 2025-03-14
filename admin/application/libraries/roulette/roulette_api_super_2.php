<?php
require_once dirname(__FILE__) . '/abstract_roulette_api.php';

/**
 * SUPER_API_2
 *
 * Required Fields:
 *
 * * api_key - api key given by system
 * * token - Effective token for player
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Roulette_api_super_2 extends Abstract_roulette_api {

	const DAILY_SPIN_TIMES = PHP_INT_MAX;
	const CONSUMPTION = 1000;
    const API_PREFIX_SUPER_2 = 'super_2';

	public function getModuleType() {
		return self::MODULE_TYPE_PROMO;
	}
	public function getDailySpinTimes() {
		return self::DAILY_SPIN_TIMES;
	}

	public function getSpinCondition() {
		return self::SPIN_CONDITION_DEPOSIT;
	}

	public function getPlatformCode(){
		return self::SUPER_API_2;
	}

	public function getPrefix() {
		return self::API_PREFIX_SUPER_2;
	}

	public function getRouletteType($roulette_name){
		$type = '';
		switch ($roulette_name) {
			case self::API_PREFIX_SUPER_2:
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

		return $spin_times_res;
	}

	public function generatePlayerRoulette($start_date, $end_date, $player_id, $promo_cms_id)
	{
		$this->CI->load->model(array('roulette_api_record', 'transactions'));
		// $valid_date = $this->utils->formatDateForMysql(new \DateTime($start_date));
		// list($bets, $deposit) = $this->getBetsAndDepositByDate($player_id, $start_date, $end_date);
		$deposit = $this->CI->transactions->totalDepositByPlayerAndDateTime($player_id, $start_date, $end_date);

		// $player_used_times = $this->countPlayerPromoByDate($player_id, $promo_cms_id, $start_date, $end_date);
		$countPlayerPromoRecord = $this->CI->roulette_api_record->countPlayerPromoRecordByCurrentDate($player_id, $promo_cms_id, $start_date);
		// $this->CI->utils->printLastSQL();
		$spin_times_res = $this->calculateSpinTimes($this->getSpinCondition(), $this->getDailySpinTimes(), $countPlayerPromoRecord, (float)$deposit, self::CONSUMPTION);
		$this->utils->debug_log(__METHOD__, 'generatePlayerRoulette super_1', [ 
			'total_times' => $spin_times_res['total_times'], 
			'used_times' => $spin_times_res['used_times'],
			'countPlayerPromoRecord' => $countPlayerPromoRecord
		]);
		$this->utils->debug_log(__METHOD__, 'Roulette_api_super_1 player deposit betting and spin_times_res', [ 'deposit' => $deposit, 'spin_times_res' => $spin_times_res]);

		return $spin_times_res;
	}

	// public function countPlayerRouletteUsedTimes($start_date, $end_date, $player_id = null, $roulette_type = null){
	// 	$used_times = $this->CI->roulette_api_record->rouletteList($start_date, $end_date, $player_id, $roulette_type);
	// 	$this->utils->debug_log(__METHOD__, 'result', [ 'used_times' => $used_times]);
	// 	return count($used_times);
	// }

	public function getPlayerBetAndDepositAmount($player_id, $start_date = null, $end_date = null){
		if (empty($start_date)) {
			$start_date = $this->utils->getTodayForMysql() .' '.Utils::FIRST_TIME;
		}
		if (empty($end_date)) {
			$end_date = $this->utils->getNowForMysql();
		}
		return $this->getBetsAndDepositByDate($player_id, $start_date, $end_date);
	}
}
