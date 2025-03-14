<?php
require_once dirname(__FILE__) . '/abstract_roulette_api.php';

/**
 * R29620_API
 *
 * Required Fields:
 *
 * * api_key - api key given by system
 * * token - Effective token for player
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Roulette_api_r29620 extends Abstract_roulette_api
{

	const DAILY_SPIN_TIMES = PHP_INT_MAX;
	const CONSUMPTION = 500;
	const API_PREFIX = 'r29620';
	const TYPE_LOGIN = self::API_PREFIX . '_login';
	const TYPE_ANY_DEPOSIT = self::API_PREFIX . '_any_deposit';
	const TYPE_DEPOSIT = self::API_PREFIX . '_deposit';

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
		return self::R29620_API;
	}

	public function getPrefix()
	{
		return self::API_PREFIX;
	}

	public function getDesc()
	{
		return lang('r29620_desc');
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

	public function getConsumption($description = null)
	{
		$per_amount = self::CONSUMPTION;
		$spin_conditions = $this->utils->safeGetArray($description, 'spin_conditions', false);
		if ($spin_conditions) {
			$per_amount = $this->utils->safeGetArray($spin_conditions, 'per_amount', $per_amount);
		}
		return $per_amount;
	}

	public function getCondiction($description = null)
	{
		$spin_conditions = $this->utils->safeGetArray($description, 'spin_conditions', array());
		$login = 0;
		$deposit = 0;
		$threshold = 1;
		if ($spin_conditions) {
			$login = $this->utils->safeGetArray($spin_conditions, 'login', 0);
			$deposit = $this->utils->safeGetArray($spin_conditions, 'any_deposit', 0);
			$threshold = $this->utils->safeGetArray($spin_conditions, 'threshold', 1);
		}
		return [$login, $deposit, $threshold];
	}

	public function getCondictionItem($description, $key)
	{
		$spin_conditions = $this->utils->safeGetArray($description, 'spin_conditions', array());
		return $this->utils->safeGetArray($spin_conditions, $key);
	}

	public function generatePlayerRoulette($start_date, $end_date, $player_id, $promo_cms_id)
	{
		$this->CI->load->model(array('roulette_api_record', 'transactions'));
		$this->utils->debug_log(__METHOD__ . "generatePlayerRoulette params [$player_id]", ['start_date' => $start_date, 'end_date' => $end_date, 'promo_cms_id' => $promo_cms_id]);
		// $valid_date = $this->utils->formatDateForMysql(new \DateTime($start_date));
		$promoRuleDescription = $this->getPromoRuleDescription($promo_cms_id);
		$usePlayerReportRecords = !empty($promoRuleDescription) && array_key_exists('usePlayerReportRecords', $promoRuleDescription) && ($promoRuleDescription['usePlayerReportRecords'] == true);
		// hasAnyDeposit
		$hasAnyDeposit = $this->CI->transactions->hasAnyDeposit($player_id);

		$this->utils->debug_log(__METHOD__ . "generatePlayerRoulette 666 params [$player_id]", ['promoRuleDescription' => $promoRuleDescription, 'hasAnyDeposit' => $hasAnyDeposit, 'promo_cms_id' => $promo_cms_id]);

		$spin_times_res = [
			"total_times" => 0,
			"used_times" => 0,
			"remain_times" => 0,
			"base" => 0,
		];

		$threshold_amount = $this->getCondictionItem($promoRuleDescription, 'threshold');
		$spin_times_res['roulette_api_id']  = $this->getPlatformCode();
		$spin_times_res['threshold_type']   = $this->getCondictionItem($promoRuleDescription, 'type');
		$spin_times_res['threshold_amount'] = $threshold_amount;
		$spin_times_res['accumulateAmount'] = 0;

		if (!$promoRuleDescription || !$hasAnyDeposit) {

			return $spin_times_res;
		}
		if ($usePlayerReportRecords) {

			list($bets, $deposit) = $this->getBetsAndDepositByDate($player_id, $start_date, $end_date);
		} else {

			$deposit = $this->CI->transactions->totalDepositByPlayerAndDateTime($player_id, $start_date, $end_date);
		}

		$this->CI->load->model(['player_model']);
		$msg = '';
		$error_code = 0;
		$controller = $this;
		$succ = $this->CI->player_model->lockAndGenerateRoulette($player_id, function ()
		use ($controller, $player_id, &$msg, &$error_code, $promoRuleDescription, $hasAnyDeposit, $usePlayerReportRecords, $start_date, $end_date, $deposit) {
			//check_login_additional_spin
			list($dt_login, $dt_any_deposit, $dt_threshold) = $this->getCondiction($promoRuleDescription);
			$loginSpin = $this->getSpinByGenerateBy($player_id, self::TYPE_LOGIN, null, $start_date, $end_date, $start_date);
			if ($dt_login > $loginSpin) {

				$this->generateAdditionalSpin($dt_login - $loginSpin, $player_id, null, null, self::TYPE_LOGIN, $start_date);
			}

			$anyDepositSpin = $this->getSpinByGenerateBy($player_id, self::TYPE_ANY_DEPOSIT, null, $start_date, $end_date, $start_date);
			if ($deposit >= $dt_threshold && $dt_any_deposit > $anyDepositSpin) {

				$this->generateAdditionalSpin($dt_any_deposit - $anyDepositSpin, $player_id, null, null, self::TYPE_ANY_DEPOSIT, $start_date);
			}
			$this->utils->debug_log(__METHOD__, 'generatePlayerRoulette-' .
				self::API_PREFIX, [
				'total_deposit' => $deposit,
				'dt_threshold' => $dt_threshold,
				'dt_any_deposit' => $dt_any_deposit,
				'anyDepositSpin' => $anyDepositSpin,
				'dt_login' => $dt_login,
				'loginSpin' => $loginSpin
			]);
			return true;
		});

		// if(!$promoRuleDescription || !$hasAnyDeposit) {

		// 	return [
		// 		"total_times" => 0,
		// 		"used_times" => 0,
		// 		"remain_times" => 0,
		// 		"base" => 0,
		// 	];

		// }
		// if($usePlayerReportRecords) {

		// 	list($bets, $deposit) = $this->getBetsAndDepositByDate($player_id, $start_date, $end_date);
		// } else {

		// 	$deposit = $this->CI->transactions->totalDepositByPlayerAndDateTime($player_id, $start_date, $end_date);
		// }

		// check_login_additional_spin
		// list($dt_login, $dt_any_deposit, $dt_threshold) = $this->getCondiction($promoRuleDescription);
		// $loginSpin = $this->getSpinByGenerateBy($player_id, self::TYPE_LOGIN, null, $start_date, $end_date, $start_date);
		// if($dt_login > $loginSpin){

		//     $this->generateAdditionalSpin($dt_login - $loginSpin, $player_id, null, null, self::TYPE_LOGIN, $start_date);
		// }

		// $anyDepositSpin = $this->getSpinByGenerateBy($player_id, self::TYPE_ANY_DEPOSIT, null, $start_date, $end_date, $start_date);
		// if($deposit >= $dt_threshold && $dt_any_deposit > $anyDepositSpin){

		//     $this->generateAdditionalSpin($dt_any_deposit - $anyDepositSpin, $player_id, null, null, self::TYPE_ANY_DEPOSIT, $start_date);
		// }

		// $player_used_times = $this->countPlayerPromoByDate($player_id, $promo_cms_id, $start_date, $end_date);
		$countPlayerPromoRecord = $this->CI->roulette_api_record->countPlayerPromoRecordByCurrentDate($player_id, $promo_cms_id, $start_date);
		// $this->CI->utils->printLastSQL();
		$per_amount = $this->getConsumption($promoRuleDescription);
		$this->utils->debug_log(__METHOD__, 'generatePlayerRoulette-' .
			self::API_PREFIX, [
			'total_deposit' => $deposit,
			'countPlayerPromoRecord' => $countPlayerPromoRecord,
			'per_amount' => $per_amount
		]);
		$spin_times_res = $this->calculateSpinTimes(
			$this->getSpinCondition(),
			$this->getDailySpinTimes(),
			$countPlayerPromoRecord,
			(float)$deposit,
			$per_amount,
			null,
			$promoRuleDescription
		);

		// $currentDesposit = $this->getSpinByGenerateBy($player_id, self::TYPE_DEPOSIT, null, $start_date, $end_date, $start_date);
		// if($currentDesposit != $spin_times_res['total_times']) {
		// 	$generateTimes = $spin_times_res['total_times'] - $currentDesposit;
		// 	if($generateTimes > 0) {
		// 		$this->generateAdditionalSpin($generateTimes, $player_id, null, null, self::TYPE_DEPOSIT, $start_date);
		// 	}
		// }

		$this->utils->debug_log(__METHOD__, 'generatePlayerRoulette ' . self::API_PREFIX, [
			'total_times' => $spin_times_res['total_times'],
			'used_times' => $spin_times_res['used_times'],
			'countPlayerPromoRecord' => $countPlayerPromoRecord
		]);
		$this->utils->debug_log(__METHOD__, self::API_PREFIX . ' player deposit betting and spin_times_res', ['deposit' => $deposit, 'spin_times_res' => $spin_times_res]);

		$availableAdditionalSpin = 0;
		$usedAdditionalSpin = 0;
		list($availableAdditionalSpin, $usedAdditionalSpin) = $this->getAdditionalSpin($player_id, $start_date, $end_date);
		$this->utils->debug_log(__METHOD__, 'getAdditionalSpin', ['availableAdditionalSpin' => $availableAdditionalSpin, 'usedAdditionalSpin' => $usedAdditionalSpin]);
		$spin_times_res['total_times']  = $spin_times_res['total_times']  + $availableAdditionalSpin + $usedAdditionalSpin;
		// $spin_times_res['total_times'] = $availableAdditionalSpin + $usedAdditionalSpin;
		// $spin_times_res['used_times']   = $spin_times_res['used_times'] + $usedAdditionalSpin;
		$spin_times_res['remain_times'] = $spin_times_res['total_times'] - $spin_times_res['used_times']; //$spin_times_res['remain_times'] + $availableAdditionalSpin;
		$spin_times_res['base'] = (float)$deposit;

		//region for new player center api
		$threshold_amount = $this->getCondictionItem($promoRuleDescription, 'threshold');
		if($deposit >= $threshold_amount){
			$threshold_amount = $per_amount;
		}

		$spin_times_res['roulette_api_id']  = $this->getPlatformCode();
		$spin_times_res['threshold_type']   = $this->getCondictionItem($promoRuleDescription, 'type');
		$spin_times_res['threshold_amount'] = $threshold_amount;
		$spin_times_res['accumulateAmount'] = (float)$deposit;
		//region end
		//
		return $spin_times_res;
	}

	public function calculateSpinTimes($type, $daily_times, $player_used_times, $amount, $consumption, $player_used_times_by_pid = null, $description = null)
	{
		$total_times = 0;
		$used_times = 0;
		$remain_times = 0;

		$per_earn = $this->getCondictionItem($description, 'earn');
		$times_by_amount = floor(($amount / $consumption)) * $per_earn;
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

	// public function countPlayerRouletteUsedTimes($start_date, $end_date, $player_id = null, $roulette_type = null){
	// 	$used_times = $this->CI->roulette_api_record->rouletteList($start_date, $end_date, $player_id, $roulette_type);
	// 	$this->utils->debug_log(__METHOD__, 'result', [ 'used_times' => $used_times]);
	// 	return count($used_times);
	// }

}
