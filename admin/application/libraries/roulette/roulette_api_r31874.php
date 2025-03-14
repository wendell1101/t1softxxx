<?php
require_once dirname(__FILE__) . '/abstract_roulette_api.php';

/**
 * R31874_API
 *
 * Required Fields:
 *
 * * api_key - api key given by system
 * * token - Effective token for player
 *
 * @category Payment
 * 
 */

//  {
//     "class": "promo_rule_roulette_r31874",
//     "allowed_date": {
//         "start": "",
//         "end": ""
//     },
//     "release_date": {
//         "start": "",
//         "end": ""
//     },
//     "spin_conditions":{
//         "type"  : "deposit",
//         "threshold": 20,
//         "earn": 1
//     },
//     "rouletteName": "r31874",
//     "cmsId":3
// }

class Roulette_api_r31874 extends Abstract_roulette_api {
    const SPIN_CONDITION_DEPOSIT = 1;
    const DAILY_SPIN_TIMES = 5;
    const CONSUMPTION = 100;
    const API_PREFIX = 'r31874';
	public function getModuleType()
	{
		return self::MODULE_TYPE_PROMO;
	}

	public function getDailySpinTimes()
	{
		return self::DAILY_SPIN_TIMES;
	}

	public function getSpinCondition()
	{
		return self::SPIN_CONDITION_DEPOSIT;
	}

	public function getPlatformCode()
	{
		return self::R31874_API;
	}

	public function getPrefix()
	{
		return self::API_PREFIX;
	}

	public function getDesc()
	{
		return lang('r31874_desc');
	}

	public function getRouletteType($roulette_name)
	{
		return $this->getPlatformCode();
	}

	public function getConsumption($description = null)
	{
		$per_amount = self::CONSUMPTION;
		$spin_conditions = $this->utils->safeGetArray($description, 'spin_conditions', false);
		if ($spin_conditions) {
			$per_amount = $this->utils->safeGetArray($spin_conditions, 'per_amount', $per_amount);
		}
		return $per_amount;
	}

	public function getCondictionItem($description, $key)
	{
		$spin_conditions = $this->utils->safeGetArray($description, 'spin_conditions', array());
		return $this->utils->safeGetArray($spin_conditions, $key);
	}

	public function createRoulette($player_id, $rt_data, $sub_wallet_id = null, $group_level_id = null, $roulette_time = null, $promo_info = null)
	{
		$this->CI->load->model(array('roulette_api_record'));
		$this->utils->debug_log(__METHOD__, 'start', ['player_id' => $player_id, 'rt_data' => $rt_data]);

		try {
			//create roulette record
			$roulette_id = $this->CI->roulette_api_record->add($rt_data);
			if ($roulette_id) {
				$this->CI->load->model(array('player_additional_roulette'));
				// check add spin
				$additionalSpin = $this->CI->player_additional_roulette->getFirstAvailableSpin($player_id, $this->getPlatformCode());
				if ($additionalSpin) {
					$additionalSpinId = $additionalSpin->id;
					// update add spin detail
					$dataToUpdate = [
						'apply_at' => $this->utils->getNowForMysql(),
						'player_promo_id' => $rt_data['player_promo_id'],
						'status' => player_additional_roulette::STATUS_USED,
					];
					$success = $this->CI->player_additional_roulette->updateById($additionalSpinId, $dataToUpdate);
					if (!$success) {
						throw new Exception('create record fail');
					}
				}
			} else {
				throw new Exception('create record fail');
			}

			$this->utils->debug_log(__METHOD__, 'roulette result', ['roulette_id' => $roulette_id]);

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
		$this->CI->load->model(array('roulette_api_record', 'transactions'));
		$this->utils->debug_log(__METHOD__ . "generatePlayerRoulette params [$player_id]", ['start_date' => $start_date, 'end_date' => $end_date, 'promo_cms_id' => $promo_cms_id]);
		
		$promoRuleDescription = $this->getPromoRuleDescription($promo_cms_id);
		$firstDeposit = $this->CI->transactions->getFirstDepositAmount($player_id);

		$this->utils->debug_log(__METHOD__ . "generatePlayerRoulette params [$player_id]", ['promoRuleDescription' => $promoRuleDescription, 'firstDeposit' => $firstDeposit, 'promo_cms_id' => $promo_cms_id]);

		if (!$promoRuleDescription || !$firstDeposit) {
			return [
				"total_times" => 0,
				"used_times" => 0,
				"remain_times" => 0,
				"base" => 0,
			];
		}

		$this->CI->load->model(['player_model','player_promo']);
		$start_date = $this->CI->player_model->getPlayerRegisterDate($player_id);
		$countPlayerPromoRecord = $this->CI->roulette_api_record->countPlayerPromoRecordByDateRange($player_id, $promo_cms_id, $start_date);
		$per_amount = $this->getConsumption($promoRuleDescription);
		$this->utils->debug_log(__METHOD__, 'generatePlayerRoulette-' .
			self::API_PREFIX, [
			'first_deposit' => $firstDeposit,
			'countPlayerPromoRecord' => $countPlayerPromoRecord,
			'per_amount' => $per_amount
		]);

		$spin_times_res = $this->calculateSpinTimes(
			$this->getSpinCondition(),
			$this->getDailySpinTimes(),
			$countPlayerPromoRecord,
			(float)$firstDeposit,
			$per_amount,
			null,
			$promoRuleDescription
		);

		$this->utils->debug_log(__METHOD__, 'generatePlayerRoulette ' . self::API_PREFIX, [
			'total_times' => $spin_times_res['total_times'],
			'used_times' => $spin_times_res['used_times'],
			'countPlayerPromoRecord' => $countPlayerPromoRecord
		]);

		$availableAdditionalSpin = 0;
		$usedAdditionalSpin = 0;
		list($availableAdditionalSpin, $usedAdditionalSpin) = $this->getAdditionalSpin($player_id, $start_date, $end_date);
		$this->utils->debug_log(__METHOD__, 'getAdditionalSpin', ['availableAdditionalSpin' => $availableAdditionalSpin, 'usedAdditionalSpin' => $usedAdditionalSpin]);
		$spin_times_res['total_times']  = $spin_times_res['total_times']  + $availableAdditionalSpin + $usedAdditionalSpin;
		$spin_times_res['remain_times'] = $spin_times_res['total_times'] - $spin_times_res['used_times']; 
		$spin_times_res['base'] = (float)$firstDeposit;
		return $spin_times_res;
	}

	public function calculateSpinTimes($type, $daily_times, $player_used_times, $amount, $consumption, $player_used_times_by_pid = null, $description = null)
	{
		$total_times = 0;
		$used_times = 0;
		$remain_times = 0;

		$per_earn = $this->getCondictionItem($description, 'earn');
		$threshold = $this->getCondictionItem($description, 'threshold');
		$this->utils->debug_log(__METHOD__, ['per_earn' => $per_earn, 'threshold' => $threshold]);
		if ($amount >= $threshold) {
			$total_times = $per_earn;
		}

		// $total_times = $times_by_amount > $daily_times ? $daily_times : $times_by_amount;

		$this->utils->debug_log(__METHOD__, ['31874_total_times' => $total_times]);

		$used_times = $player_used_times;
		$remain = $total_times - $used_times;
		$remain_times = $remain > 0 ? $remain : $remain_times;

		return [
			"total_times" => floor($total_times),
			"used_times" => floor($used_times),
			"remain_times" => floor($remain_times)
		];
	}


}
