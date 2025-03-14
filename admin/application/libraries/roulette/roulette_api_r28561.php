<?php
require_once dirname(__FILE__) . '/abstract_roulette_api.php';

/**
 * R28561_API
 *
 * Required Fields:
 *
 * * api_key - api key given by system
 * * token - Effective token for player
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Roulette_api_r28561 extends Abstract_roulette_api {

	const API_PREFIX_R28561 = 'r28561';

	public function getModuleType() {
		return self::MODULE_TYPE_PROMO;
	}
	
	public function getSpinCondition() {
		return '';
	}

	public function getPlatformCode(){
		return self::R28561_API;
	}

	public function getPrefix() {
		return self::API_PREFIX_R28561;
	}

	public function getRouletteType($roulette_name){
		return $this->getPlatformCode();
	}

	public function getDailySpinTimes($description = null) {
		return 0;
	}

	public function verifyRouletteSpinTimes($player_id, $promo_cms_id){

		$spin_times_res = $this->generatePlayerRoulette(null, null, $player_id, $promo_cms_id);
		$spin_times_res['total_times'] = $spin_times_res['total_times'];
		$spin_times_res['used_times'] = $spin_times_res['used_times'];
		$spin_times_res["remain_times"] = $spin_times_res["remain_times"];
		$spin_times_res['valid_date'] = $this->utils->formatDateForMysql(new \DateTime());
		
		$spin_times_res['base'] = $this->utils->safeGetArray($spin_times_res, 'base', 0);

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
	
	public function generatePlayerRoulette($start_date, $end_date, $player_id, $promo_cms_id){
		$description = $this->getPromoRuleDescription($promo_cms_id);
		list($promorule, $promoCmsSettingId) = $this->getByCmsPromoCodeOrIdCache($promo_cms_id);
		$this->utils->debug_log(__METHOD__." Found player_id {$player_id}",[
			'description' => $description,
			'promo_cms_id' => $promo_cms_id
		]);

		if(empty($promorule)){
			throw new Exception(lang('Rule Not Found'), Api_common::CODE_PMO_PROMO_CMS_ID_INVALID);
		}

		$limit_times = $this->utils->safeGetArray($description, 'times_limit', 1);
		$player_used_times = count($this->CI->player_promo->getReleasedPlayerPromoByTimes($promorule['promorulesId'], $player_id));
		$spin_times_res = $this->calculateSpinTimes($this->getSpinCondition(), $limit_times, $player_used_times, null, null, 0, $description);

		$this->utils->debug_log(__METHOD__, 'player deposit betting and spin_times_res', [
			'spin_times_res' => $spin_times_res,
		]);

		return $spin_times_res;
	}

	public function calculateSpinTimes($type, $limit_times, $player_used_times, $deposit_list, $consumption, $p_total_times = null, $description = null){
		$this->utils->debug_log(__METHOD__, 'start', [ 'type' => $type, 'limit_times' => $limit_times, 'player_used_times' => $player_used_times , 'p_total_times' => $p_total_times, 'deposit_list' => $deposit_list]);

		$total_times = 0;
		$used_times = 0;
		$remain_times = 0;

		$total_times = $limit_times;
		$used_times = $player_used_times;
		$remain = $total_times - $used_times;
		$remain_times = $remain > 0 ? $remain : $remain_times;

		return [
			"total_times" => floor($total_times),
			"used_times" => floor($used_times),
			"remain_times" => floor($remain_times)
		];
	}

	public function playerRouletteRewardOdds() {
		$this->CI->load->library(array('authentication'));
		$player_id = $this->CI->authentication->getPlayerId();
		$this->utils->debug_log(__METHOD__, 'start player_id',$player_id);

		try {
			$prize_arr = $this->utils->getConfig('roulette_reward_odds_settings');
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
