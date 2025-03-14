<?php
require_once dirname(__FILE__) . '/abstract_roulette_api.php';

/**
 * R33827_PHP_API
 *
 * Required Fields:
 *
 * * api_key - api key given by system
 * * token - Effective token for player
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Roulette_api_r33827_php extends Abstract_roulette_api {

	const DAILY_SPIN_TIMES = PHP_INT_MAX;
	const CONSUMPTION = 100;
	const API_PREFIX = 'r33827_php';

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
		return self::R33827_PHP_API;
	}

	public function getPrefix() {
		return self::API_PREFIX;
	}

	public function getDesc() {
		return lang('r33827_php_desc');
	}

	public function getRouletteType($roulette_name){
		return $this->getPlatformCode();
	}
	
	public function createRoulette($player_id, $rt_data, $sub_wallet_id = null, $group_level_id = null, $roulette_time = null, $promo_info=null) {
		$this->CI->load->model(array('roulette_api_record'));
		$this->utils->debug_log(__METHOD__, 'start', [ 'player_id' => $player_id, 'rt_data' => $rt_data]);

		try {
			//create roulette record
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

	public function getConsumption($description = null) {
		$per_amount = self::CONSUMPTION;
		$spin_conditions = $this->utils->safeGetArray($description, 'spin_conditions', false);
		if ($spin_conditions) {
			$per_amount = $this->utils->safeGetArray($spin_conditions, 'per_amount', $per_amount);
		}
		return $per_amount;
	}

	public function generatePlayerRoulette($start_date, $end_date, $player_id, $promo_cms_id)
	{
		$this->CI->load->model(array('roulette_api_record', 'transactions'));
		$this->utils->debug_log(__METHOD__."generatePlayerRoulette params [$player_id]", [ 'start_date' => $start_date, 'end_date' => $end_date, 'promo_cms_id' => $promo_cms_id]);
		// $valid_date = $this->utils->formatDateForMysql(new \DateTime($start_date));
		$promoRuleDescription = $this->getPromoRuleDescription($promo_cms_id);
		$usePlayerReportRecords = !empty($promoRuleDescription) && array_key_exists('usePlayerReportRecords', $promoRuleDescription) && ($promoRuleDescription['usePlayerReportRecords'] == true);
		if($usePlayerReportRecords) {

			list($bets, $deposit) = $this->getBetsAndDepositByDate($player_id, $start_date, $end_date);
		} else {
			
			$deposit = $this->CI->transactions->totalDepositByPlayerAndDateTime($player_id, $start_date, $end_date);
		}

		// $player_used_times = $this->countPlayerPromoByDate($player_id, $promo_cms_id, $start_date, $end_date);
		$countPlayerPromoRecord = $this->CI->roulette_api_record->countPlayerPromoRecordByCurrentDate($player_id, $promo_cms_id, $start_date);
		// $this->CI->utils->printLastSQL();
		$per_amount = $this->getConsumption($promoRuleDescription);
		$spin_times_res = $this->calculateSpinTimes($this->getSpinCondition(), $this->getDailySpinTimes(), $countPlayerPromoRecord, (float)$deposit, $per_amount);
		$this->utils->debug_log(__METHOD__, 'generatePlayerRoulette '. self::API_PREFIX, [ 
			'total_times' => $spin_times_res['total_times'], 
			'used_times' => $spin_times_res['used_times'],
			'countPlayerPromoRecord' => $countPlayerPromoRecord, 'per_amount' => $per_amount, 'promoRuleDescription' => $promoRuleDescription
		]);
		$this->utils->debug_log(__METHOD__, self::API_PREFIX . ' player deposit betting and spin_times_res', [ 'deposit' => $deposit, 'spin_times_res' => $spin_times_res]);

		$availableAdditionalSpin = 0;
		$usedAdditionalSpin = 0;
		list($availableAdditionalSpin, $usedAdditionalSpin) = $this->getAdditionalSpin($player_id, $start_date, $end_date);
		$this->utils->debug_log(__METHOD__, 'getAdditionalSpin', [ 'availableAdditionalSpin' => $availableAdditionalSpin, 'usedAdditionalSpin' => $usedAdditionalSpin]);
		$spin_times_res['total_times']  = $spin_times_res['total_times']  + $availableAdditionalSpin + $usedAdditionalSpin;
		// $spin_times_res['used_times']   = $spin_times_res['used_times']   + $usedAdditionalSpin;
		$spin_times_res['remain_times'] = $spin_times_res['total_times'] - $spin_times_res['used_times']; //$spin_times_res['remain_times'] + $availableAdditionalSpin;
		$spin_times_res['base'] = (float)$deposit;
		return $spin_times_res;
	}

	// public function countPlayerRouletteUsedTimes($start_date, $end_date, $player_id = null, $roulette_type = null){
	// 	$used_times = $this->CI->roulette_api_record->rouletteList($start_date, $end_date, $player_id, $roulette_type);
	// 	$this->utils->debug_log(__METHOD__, 'result', [ 'used_times' => $used_times]);
	// 	return count($used_times);
	// }
    
}
