<?php
require_once dirname(__FILE__) . '/abstract_roulette_api.php';

/**
 * r26755_API
 *
 * Required Fields:
 *
 * * api_key - api key given by system
 * * token - Effective token for player
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Roulette_api_r26755 extends Abstract_roulette_api {

	const DAILY_SPIN_TIMES = 5;
	const CONSUMPTION = 499;
	const MAX_AMOUNT_LIMIT = 2998;
	const API_PREFIX_R26755 = 'r26755';
	const PRE_CHECH_CMS_ID = '19';
	const TYPE_MANULLY_ADD = self::API_PREFIX_R26755.'_manully_add';

	public function getModuleType() {
		return self::MODULE_TYPE_PROMO;
	}
	
	public function getDailySpinTimes($description = null) {
		return (! is_null($description) && isset($description['daily_spin_limits'])) ? $description['daily_spin_limits'] : self::DAILY_SPIN_TIMES;
	}

	public function getPreChechCmsId($description = null) {
		return (! is_null($description) && isset($description['pre_chech_cms_id'])) ? $description['pre_chech_cms_id'] : self::PRE_CHECH_CMS_ID;
	}

	public function getMaxAmountLimit($description = null) {
		return (! is_null($description) && isset($description['max_amount_limit'])) ? $description['max_amount_limit'] : self::MAX_AMOUNT_LIMIT;
	}

	public function getConsumption($description = null) {
		$default_per_amount = self::CONSUMPTION;
		$spin_conditions = isset($description['spin_conditions'])? $description['spin_conditions'] : false;
		if ($spin_conditions) {
			$per_amount = isset($spin_conditions['per_amount'])? $spin_conditions['per_amount'] : $default_per_amount;
		}
		return $default_per_amount;
	}

	public function getSpinCondition() {
		return self::SPIN_CONDITION_DEPOSIT;
	}

	public function getPlatformCode(){
		return self::R26755_API;
	}

	public function getPrefix() {
		return self::API_PREFIX_R26755;
	}

	public function getRouletteType($roulette_name){
		$type = '';
		switch ($roulette_name) {
			case self::API_PREFIX_R26755:
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

	public function generatePlayerRoulette($start_date, $end_date, $player_id, $promo_cms_id){
		$description = $this->getPromoRuleDescription($promo_cms_id);
		$pre_chech_cms_id = $this->getPreChechCmsId($description);
		$consumption = $this->getConsumption($description);
		$max_amount_limit = $this->getMaxAmountLimit($description);
		$daily_times = $this->getDailySpinTimes($description);
		$description['promo_cms_id'] = $promo_cms_id;
		$make_up_spins_list = isset($description['make_up_spins_list']) ? $description['make_up_spins_list'] : [];
        $make_up_limit = isset($description['make_up_limit']) ? $description['make_up_limit'] : 5;
        $username = $this->CI->player_model->getUsernameById($player_id);

		$this->utils->debug_log(__METHOD__." Found cms id {$promo_cms_id}",[
			'description'=> $description,
			'pre_chech_cms_id' => $pre_chech_cms_id,
			'consumption'=> $consumption,
			'max_amount_limit'=> $max_amount_limit,
			'daily_times'=> $daily_times
		]);

		$availableAdditionalSpin = 0;
		$usedAdditionalSpin = 0;
		list($availableAdditionalSpin, $usedAdditionalSpin) = $this->getAdditionalSpin($player_id, $start_date, $end_date); // 拿外加次數

		$deposit_list = $this->listDepositTransactions($player_id, $start_date, $end_date, $consumption, $max_amount_limit);
		$player_used_times = $this->countPlayerPromoByDate($player_id, $promo_cms_id, $start_date, $end_date);
		$player_used_times = $player_used_times - $usedAdditionalSpin;
		$player_used_times_by_pid = $this->countPlayerPromoByDate($player_id, $pre_chech_cms_id, $start_date, $end_date);
		$spin_times_res = $this->calculateSpinTimes($this->getSpinCondition(), $daily_times, $player_used_times, $deposit_list, $consumption, $player_used_times_by_pid, $description);

		$this->utils->debug_log(__METHOD__, 'getAdditionalSpin', [
			'availableAdditionalSpin' => $availableAdditionalSpin,
			'usedAdditionalSpin' => $usedAdditionalSpin,
			'spin_times_res' => $spin_times_res
		]);

		$daily_spin_limits = $description['daily_spin_limits'];

        if (in_array($username, $make_up_spins_list)) {
            $daily_spin_limits = $make_up_limit;

			$spin_times_res['total_times']  = $spin_times_res['total_times']  + $availableAdditionalSpin + $usedAdditionalSpin; //補上總次數
			if ($spin_times_res['total_times'] >= $daily_times) {
				$spin_times_res['total_times'] = $daily_spin_limits;
			}

			$spin_times_res['used_times'] = $spin_times_res['used_times'] + $usedAdditionalSpin;
			$remain = $spin_times_res['total_times'] - $spin_times_res['used_times'];
			$spin_times_res['remain_times'] = $remain > 0 ? $remain : 0;

			$this->utils->debug_log(__METHOD__, 'is in spins_list', [
				'daily_spin_limits' => $daily_spin_limits,
				'make_up_spins_list' => $make_up_spins_list,
				'remain' => $remain,
				'spin_times_res' => $spin_times_res
			]);
        }

		$this->utils->debug_log(__METHOD__, 'player deposit betting and spin_times_res', [
			'spin_times_res' => $spin_times_res,
			'player_used_times_by_pid' => $player_used_times_by_pid,
			'player_used_times' => $player_used_times
		]);

		return $spin_times_res;
	}

	public function calculateSpinTimes($type, $daily_times, $player_used_times, $deposit_list, $consumption, $player_used_times_by_pid = null, $description = null){
		$this->utils->debug_log(__METHOD__, 'start', [ 'type' => $type, 'daily_times' => $daily_times, 'player_used_times' => $player_used_times, 'deposit_list' => $deposit_list]);

		$times_by_amount = 0;
		$total_times = 0;
		$used_times = 0;
		$remain_times = 0;
		$available_list = [];
		$fields = [];
		$total_player_used_times = $player_used_times + $player_used_times_by_pid;
		// list($promorule, $promoCmsSettingId) = $this->CI->promorules->getByCmsPromoCodeOrId($description['promo_cms_id']);
		$count = 0;

		list($promorule, $promoCmsSettingId) = $this->getByCmsPromoCodeOrIdCache($description['promo_cms_id']);

		$this->utils->debug_log(__METHOD__, '----------- getByCmsPromoCodeOrIdCache -----------' ,$promorule, $promoCmsSettingId);

		$this->utils->debug_log(__METHOD__, '----------- start calculateSpinTimes -----------' ,$count, $deposit_list);
		if (count($deposit_list) > 0) {
			foreach ($deposit_list as $deposit) {
				$count += 1;
				$used = false;

				$this->utils->debug_log(__METHOD__, '----------- count start----------- :' . $count, $used);
				if (!empty($deposit->player_promo_id) || !is_null($deposit->player_promo_id)) {

					$data = $this->CI->promorules->getPromoRulesId($deposit->player_promo_id);
					$deposit_ruleid = $data['promorulesId'];

					if ($promorule['promorulesId'] != $deposit_ruleid) {
						$this->utils->debug_log(__METHOD__,'continue promo', ['player_promo_id'=>$deposit->player_promo_id, 'deposit'=>$deposit, 'deposit_ruleid'=>$deposit_ruleid, 'promorulesId' => $promorule['promorulesId']]);
						continue;
					}else{
						$used = true;
					}
				}

				$this->utils->debug_log(__METHOD__, '----------- count end----------- :' . $count, $used);

				$single_times = floor(($deposit->amount / $consumption));
				$times_by_amount += $single_times;
				$fields['used_roulette'] = $used;
				$fields['transid'] = $deposit->id;
				$fields['deposit_amount'] = $deposit->amount;
				$fields['player_promo_id'] = $deposit->player_promo_id;
				$fields['single_times'] = $single_times;
				$available_list[] = $fields;
			}
		}
		$this->utils->debug_log(__METHOD__, '----------- end calculateSpinTimes -----------', ['available_list'=> $available_list]);

		if ($total_player_used_times >= $daily_times) {
			$used_times = $daily_times;
		}else{
			$used_times = $player_used_times;
		}

		if ($type == self::SPIN_CONDITION_BET) {
			$total_times = $times_by_amount > $daily_times ? $daily_times : $times_by_amount;
			$remain = $total_times - $used_times;
			$remain_times = $remain > 0 ? $remain : $remain_times;
		}elseif ($type == self::SPIN_CONDITION_DEPOSIT) {
			$total_times = $times_by_amount > $daily_times ? $daily_times : $times_by_amount;
			$remain = $total_times - $used_times;
			$remain_times = $remain > 0 ? $remain : $remain_times;
		}
		return [
			"total_times" => floor($total_times),
			"used_times" => floor($used_times),
			"remain_times" => floor($remain_times),
			"available_list" => $available_list
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
