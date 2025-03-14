<?php
require_once dirname(__FILE__) . '/abstract_roulette_api.php';

/**
 * R30774_API
 *
 * Required Fields:
 *
 * * api_key - api key given by system
 * * token - Effective token for player
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Roulette_api_r30774 extends Abstract_roulette_api
{

	const DAILY_SPIN_TIMES = PHP_INT_MAX;
	const CONSUMPTION = 20;
	const API_PREFIX = 'r30774';
	const TYPE_REFER_FRIEND = self::API_PREFIX . '_refer_friend';

	public function getModuleType()
	{
		return self::MODULE_TYPE_PROMO;
	}

	public function getDailySpinTimes($description = []) {
		return $this->CI->utils->safeGetArray($description, 'daily_spin_limits', self::DAILY_SPIN_TIMES);
	}

	public function getSpinCondition()
	{
		return self::SPIN_CONDITION_DEPOSIT;
	}

	public function getPlatformCode()
	{
		return self::R30774_API;
	}

	public function getPrefix()
	{
		return self::API_PREFIX;
	}

	public function getDesc()
	{
		return lang('r30774_desc');
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
		$this->CI->load->model(array('roulette_api_record', 'transactions', 'player_friend_referral', 'player_model'));
		$this->utils->debug_log(__METHOD__ . " generatePlayerRoulette params [$player_id]", ['start_date' => $start_date, 'end_date' => $end_date, 'promo_cms_id' => $promo_cms_id]);
		$promoRuleDescription = $this->getPromoRuleDescription($promo_cms_id);
		$threshold = $this->getCondictionItem($promoRuleDescription, 'threshold');

        $spin_times_res = [
            "total_times" => 0,
            "used_times" => 0,
            "remain_times" => 0,
            "base" => 0,
        ];

        $this->utils->debug_log(__METHOD__ . " promoRuleDescription params [$player_id]", ['promoRuleDescription' => $promoRuleDescription, 'threshold' => $threshold, 'spin_times_res' => $spin_times_res]);

        list($promorule, $promoCmsSettingId) = $this->getByCmsPromoCodeOrIdCache($promo_cms_id);
        $appStartDate = $promorule['applicationPeriodStart'];

        $this->utils->debug_log(__METHOD__ . " getByCmsPromoCodeOrIdCache params [$player_id]", ['promorule' => $promorule, 'promoCmsSettingId' => $promoCmsSettingId, 'promo_cms_id' => $promo_cms_id]);

		$referredList = $this->CI->player_friend_referral->getReferredByPlayerIdWithDateRange($player_id, $appStartDate, $end_date);
		$referredPlayerIds = array_column($referredList, 'invitedPlayerId');
		$referredPlayerIds = array_unique($referredPlayerIds);
		$referredPlayerIds = array_values($referredPlayerIds);

		$countHasAnyDeposit = 0;
		$filteredPlayerIds = [];
		$countRef = 0;

		foreach ($referredPlayerIds as $playerId) {
            $deposit = $this->CI->transactions->totalDepositByPlayerAndDateTime($playerId, $appStartDate, $end_date);

			if ($deposit >= $threshold) {
				$countHasAnyDeposit++;
				$filteredPlayerIds[] = $playerId;
			}
		}

		$countRef = count($filteredPlayerIds);

		$this->utils->debug_log(__METHOD__ . " referredList params [$player_id]", [
            'referredList' => $referredList,
			'countHasAnyDeposit' => $countHasAnyDeposit,
			'filteredPlayerIds' => $filteredPlayerIds,
			'countRef' => $countRef
		]);

		if (!$promoRuleDescription || $countRef < 1 || $countHasAnyDeposit < 1) {
			return $spin_times_res;
		}

		$countPlayerPromoRecord = $this->CI->roulette_api_record->countPlayerPromoRecordByCurrentDate($player_id, $promo_cms_id, $start_date);
        $spin_times_res['used_times'] = $countPlayerPromoRecord;

		$this->utils->debug_log(__METHOD__, ' countPlayerPromoRecordByCurrentDate-' .self::API_PREFIX, [
			'countPlayerPromoRecord' => $countPlayerPromoRecord,
			'spin_times_res' => $spin_times_res
		]);

		$availableAdditionalSpin = 0;
		$usedAdditionalSpin = 0;
		list($availableAdditionalSpin, $usedAdditionalSpin) = $this->getAdditionalSpin($player_id, $start_date, $end_date);
		$this->utils->debug_log(__METHOD__, ' getAdditionalSpin' .self::API_PREFIX, ['availableAdditionalSpin' => $availableAdditionalSpin, 'usedAdditionalSpin' => $usedAdditionalSpin]);
		$spin_times_res['total_times']  = $availableAdditionalSpin + $usedAdditionalSpin;
		// $spin_times_res['total_times'] = $availableAdditionalSpin + $usedAdditionalSpin;
		// $spin_times_res['used_times']   = $spin_times_res['used_times'] + $usedAdditionalSpin;
		$spin_times_res['remain_times'] = $spin_times_res['total_times'] - $spin_times_res['used_times'];

		$this->utils->debug_log(__METHOD__,' player spin_times_res-' .self::API_PREFIX, ['spin_times_res' => $spin_times_res]);

		return $spin_times_res;
	}

    //not use
	public function calculateSpinTimes($type, $daily_times, $player_used_times, $count_hasany_deposit, $consumption, $player_used_times_by_pid = null, $description = null)
	{
		$total_times = 0;
		$used_times = 0;
		$remain_times = 0;

		$per_earn = $this->getCondictionItem($description, 'earn');
		$times_by_amount = $count_hasany_deposit * $per_earn;
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


}
