<?php
require_once dirname(__FILE__) . '/abstract_roulette_api.php';

/**
 * NORMAL_API_1
 *
 * Required Fields:
 *
 * * api_key - api key given by system
 * * token - Effective token for player
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Roulette_api_normal_1 extends Abstract_roulette_api {

	const DAILY_SPIN_TIMES = 50;
	const CONSUMPTION = 5000;
	const API_PREFIX_NORMAL = 'normal_1';

	public function getModuleType() {
		return self::MODULE_TYPE_PROMO;
	}
	public function getDailySpinTimes() {
		return self::DAILY_SPIN_TIMES;
	}

	public function getSpinCondition() {
		return self::SPIN_CONDITION_BET;
	}

	public function getPlatformCode(){
		return self::NORMAL_API_1;
	}

	public function getPrefix() {
		return self::API_PREFIX_NORMAL;
	}

	public function getDesc() {
		return lang('Normal Roulette');
	}

	public function getRouletteType($roulette_name){
		$type = '';
		switch ($roulette_name) {
			case self::API_PREFIX_NORMAL:
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
			$roulette_id = $this->CI->roulette_api_record->add($rt_data);
			if($roulette_id) {
				$this->CI->load->model(array('player_additional_roulette'));
				// check add spin
				$additionalSpin = $this->CI->player_additional_roulette->getFirstAvailableSpin($player_id, $this->getPlatformCode());
				if($additionalSpin) {
					$additionalSpinId = $additionalSpin->id;
					// update add spin detail
					$dataToUpdate = [
						'apply_at' => $this->utils->getNowForMysql(),
						'player_promo_id' => $rt_data['player_promo_id'],
						'status' => player_additional_roulette::STATUS_USED,
					];
					$success = $this->CI->player_additional_roulette->updateById($additionalSpinId, $dataToUpdate);
					if(!$success) {
	
						throw new Exception('create record fail');
					}
				}
			} else {
				throw new Exception('create record fail');
			}
			
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

	public function generatePlayerRoulette($start_date, $end_date, $player_id, $promo_cms_id)
	{
		$this->CI->load->model(array('roulette_api_record'));
		$this->utils->debug_log(__METHOD__."generatePlayerRoulette params [$player_id]", [ 'start_date' => $start_date, 'end_date' => $end_date, 'promo_cms_id' => $promo_cms_id]);
		// $valid_date = $this->utils->formatDateForMysql(new \DateTime($start_date));
		list($bets, $deposit) = $this->getBetsAndDepositByDate($player_id, $start_date, $end_date);
		// $player_used_times = $this->countPlayerPromoByDate($player_id, $promo_cms_id, $start_date, $end_date);
		$countPlayerPromoRecord = $this->CI->roulette_api_record->countPlayerPromoRecordByCurrentDate($player_id, $promo_cms_id, $start_date);
		
		// $this->CI->utils->printLastSQL();
		$spin_times_res = $this->calculateSpinTimes($this->getSpinCondition(), $this->getDailySpinTimes(), $countPlayerPromoRecord, (int)$bets, self::CONSUMPTION);
		$this->utils->debug_log(__METHOD__, 'generatePlayerRoulette normal_1', [ 
			'total_times' => $spin_times_res['total_times'], 
			'used_times' => $spin_times_res['used_times'],
			'countPlayerPromoRecord' => $countPlayerPromoRecord
		]);
		$this->utils->debug_log(__METHOD__, 'player deposit betting and spin_times_res', [ 'bets' => $bets, 'deposit' => $deposit, 'spin_times_res' => $spin_times_res]);

		$availableAdditionalSpin = 0;
		$usedAdditionalSpin = 0;
		list($availableAdditionalSpin, $usedAdditionalSpin) = $this->getAdditionalSpin($player_id, $start_date, $end_date);
		$this->utils->debug_log(__METHOD__, 'getAdditionalSpin', [ 'availableAdditionalSpin' => $availableAdditionalSpin, 'usedAdditionalSpin' => $usedAdditionalSpin]);
		$spin_times_res['total_times']  = $spin_times_res['total_times']  + $availableAdditionalSpin + $usedAdditionalSpin;
		// $spin_times_res['used_times']   = $spin_times_res['used_times']   + $usedAdditionalSpin;
		$spin_times_res['remain_times'] = $spin_times_res['total_times'] - $spin_times_res['used_times'] ;//$spin_times_res['remain_times'] + $availableAdditionalSpin;

		return $spin_times_res;
	}

	// public function countPlayerRouletteUsedTimes($start_date, $end_date, $player_id = null, $roulette_type = null){
	// 	$used_times = $this->CI->roulette_api_record->rouletteList($start_date, $end_date, $player_id, $roulette_type);
	// 	$this->utils->debug_log(__METHOD__, 'result', [ 'used_times' => $used_times]);
	// 	return count($used_times);
	// }

}
