<?php
require_once dirname(__FILE__) . '/abstract_roulette_api.php';

/**
 * R32439_API
 *
 * Required Fields:
 *
 * * api_key - api key given by system
 * * token - Effective token for player
 *
 * @category Payment
 * @copyright 2013-2024 tot
 */
class Roulette_api_r32439 extends Abstract_roulette_api
{
	const DAILY_SPIN_TIMES = 5;
	const CONSUMPTION = 10;
	const API_PREFIX = 'r32439';

	public function getModuleType()
	{
		return self::MODULE_TYPE_PROMO;
	}

	public function getDailySpinTimes($description = null) {
		return (! is_null($description) && isset($description['daily_spin_limits'])) ? $description['daily_spin_limits'] : self::DAILY_SPIN_TIMES;
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

	public function getSpinCondition()
	{
		return self::SPIN_CONDITION_DEPOSIT;
	}

	public function getPlatformCode()
	{
		return self::R32439_API;
	}

	public function getPrefix()
	{
		return self::API_PREFIX;
	}

	public function getDesc()
	{
		return lang('r32439_desc');
	}

	public function getRouletteType($roulette_name)
	{
		return $this->getPlatformCode();
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

	public function getCondictionItem($description, $key)
	{
		$spin_conditions = $this->utils->safeGetArray($description, 'spin_conditions', array());
		return $this->utils->safeGetArray($spin_conditions, $key);
	}

	public function generatePlayerRoulette($start_date, $end_date, $player_id, $promo_cms_id)
	{
		$this->CI->load->model(array('roulette_api_record', 'transactions'));

		$rname = self::API_PREFIX;
		$this->utils->debug_log(__METHOD__ . "generatePlayerRoulette-$rname params.1 [$player_id]", [
			'start_date' => $start_date,
			'end_date' => $end_date,
			'promo_cms_id' => $promo_cms_id
		]);

		$promoRuleDescription = $this->getPromoRuleDescription($promo_cms_id);
		$usePlayerReportRecords = !empty($promoRuleDescription) && array_key_exists('usePlayerReportRecords', $promoRuleDescription) && ($promoRuleDescription['usePlayerReportRecords'] == true);

		$hasAnyDeposit = $this->CI->transactions->hasAnyDeposit($player_id);
		$spin_times_res = [
			"total_times" => 0,
			"used_times" => 0,
			"remain_times" => 0,
			"base" => 0,
		];

		$this->utils->debug_log(__METHOD__ . "generatePlayerRoulette-$rname params.2 [$player_id]", [
			'promoRuleDescription' => $promoRuleDescription,
			'hasAnyDeposit' => $hasAnyDeposit,
			'promo_cms_id' => $promo_cms_id,
			'spin_times_res' => $spin_times_res
		]);

		if (!$promoRuleDescription || !$hasAnyDeposit) {
			return $spin_times_res;
		}

		if ($usePlayerReportRecords) {
			list($bets, $deposit) = $this->getBetsAndDepositByDate($player_id, $start_date, $end_date);
		} else {
			$deposit = $this->CI->transactions->totalDepositByPlayerAndDateTime($player_id, $start_date, $end_date);
		}

		// $player_used_times = $this->countPlayerPromoByDate($player_id, $promo_cms_id, $start_date, $end_date);
		$countPlayerPromoRecord = $this->CI->roulette_api_record->countPlayerPromoRecordByCurrentDate($player_id, $promo_cms_id, $start_date);
		$this->CI->utils->printLastSQL();

		$per_amount = $this->getConsumption($promoRuleDescription);

		$spin_times_res = $this->calculateSpinTimes(
			$this->getSpinCondition(),
			$this->getDailySpinTimes(),
			$countPlayerPromoRecord,
			(float)$deposit,
			$per_amount,
			null,
			$promoRuleDescription
		);

		$this->utils->debug_log(__METHOD__, "generatePlayerRoulette-$rname params.3 [$player_id]", [
			'total_deposit' => $deposit,
			'countPlayerPromoRecord' => $countPlayerPromoRecord,
			'per_amount' => $per_amount,
			'spin_times_res' => $spin_times_res
		]);

		$availableAdditionalSpin = 0;
		$usedAdditionalSpin = 0;

		list($availableAdditionalSpin, $usedAdditionalSpin) = $this->getAdditionalSpin($player_id, $start_date, $end_date);

		$this->utils->debug_log(__METHOD__, "generatePlayerRoulette-$rname params.4 [$player_id]", [
			'availableAdditionalSpin' => $availableAdditionalSpin,
			'usedAdditionalSpin' => $usedAdditionalSpin
		]);

		$spin_times_res['total_times']  = $spin_times_res['total_times']  + $availableAdditionalSpin + $usedAdditionalSpin;
		$spin_times_res['remain_times'] = $spin_times_res['total_times'] - $spin_times_res['used_times']; 
		$spin_times_res['base'] = (float)$deposit;
		$spin_times_res['roulette_api_id']  = $this->getPlatformCode();
		$spin_times_res['threshold_type']   = $this->getCondictionItem($promoRuleDescription, 'type');
		$spin_times_res['accumulateAmount'] = (float)$deposit;

		$this->utils->debug_log(__METHOD__, "generatePlayerRoulette-$rname params.5 [$player_id]", [
			'spin_times_res' => $spin_times_res,
		]);

		return $spin_times_res;
	}

	public function calculateSpinTimes($type, $daily_times, $player_used_times, $amount, $consumption, $player_used_times_by_pid = null, $description = null)
	{
		$total_times = 0;
		$used_times = 0;
		$remain_times = 0;
		$times_by_amount = 0;
		$threshold_amount = $this->getCondictionItem($description, 'threshold');
		$has_threshold = false;
		$threshold = 0;

		$deposit_amount = $this->getCondictionItem($description, 'deposit_amount');
		end($deposit_amount);
		$lastKey = key($deposit_amount);

		foreach ($deposit_amount as $key => $val) {
			if ($amount >= $val['min_deposit'] && $amount <= $val['max_deposit']) {
				$times_by_amount = $val['earn'];
				$has_threshold = true;
				if ($key == $lastKey){
  					$threshold = $key;
				}else{
					$threshold = $key + 1;
				}
				break;
			}
		}

		if ($has_threshold) {
			$threshold_amount = isset($deposit_amount[$threshold]['min_deposit']) ? $deposit_amount[$threshold]['min_deposit'] : $threshold_amount;
		}

		$total_times = $times_by_amount > $daily_times ? $daily_times : $times_by_amount;
		$used_times = $player_used_times;
		$remain = $total_times - $used_times;
		$remain_times = $remain > 0 ? $remain : $remain_times;

		return [
			"total_times" => floor($total_times),
			"used_times" => floor($used_times),
			"remain_times" => floor($remain_times),
			"threshold_amount" => $threshold_amount,
		];
	}
}
