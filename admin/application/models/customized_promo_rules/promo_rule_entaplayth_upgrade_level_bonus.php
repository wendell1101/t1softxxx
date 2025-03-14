<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 * Custom promo rule: vip level upgrade bonus for Entaplay-th
 * OGP-18799
 *
 * Rules:
 * 	1 Application period: 18:00-20:00, 5th day of each month
 * 	2 Limit: once (1) per month
 * 	3 Search for upgrade record in previous month (5 last month - 4 this month)
 * 		if available, issue bonus by bonus config below
 * 	4 Issue bonus for every upgrade in the interval; not limited to once per month
 *  5 Withdrawal condition: turnover (sum bet) >= bonus * 10.0
 *  6 Turnover calculation will exclude specific games: check 'allowed game types'
 *
 * Bonus config:
 * {
 * 	"class":"Promo_rule_entaplayth_upgrade_level_bonus",
 * 	"bonus_settings":{
 *      "1":{"bonus_amount":"0","level_name":"Star"},
 * 		"2":{"bonus_amount":"500","level_name":"Bronze"},
 * 		"3":{"bonus_amount":"1200","level_name":"Emerald"},
 * 		"4":{"bonus_amount":"3600","level_name":"Ruby"},
 * 		"5":{"bonus_amount":"8000","level_name":"Silver"},
 * 		"6":{"bonus_amount":"15000","level_name":"Gold"},
 * 		"7":{"bonus_amount":"30000","level_name":"Platinum"},
 * 		"8":{"bonus_amount":"60000","level_name":"Diamond"}
 * 	},
 * 	"allowed_vip_group":"VIP Player Group",
 * 	"withdraw_condition_bonus_times":10.0,
 * 	"apply_interval_from":"15 18:00",
 * 	"apply_interval_to":"20 20:00",
 * 	"check_interval_from":"05 18:00:00",
 * 	"check_interval_to":"05 17:59:59"
 * }
 */
class promo_rule_entaplayth_upgrade_level_bonus extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_entaplayth_upgrade_level_bonus';
	}

	/**
	 * run bonus condition checker
	 * @param  array $description original description in rule
	 * @param  array $extra_info
	 * @param  boolean $dry_run
	 * @return  array ['success'=> success, 'message'=> errorMessageLang, 'continue_process_after_script' => FALSE]
	 */
	protected function runBonusConditionChecker($description, &$extra_info, $dry_run){

		$result = [
			'success'	=> false ,
			'message'	=> null ,
			'continue_process_after_script' => false
		];

		try {
			$this->appendToDebugLog('vip_dataset', [
				'group' => $this->vipGroupName,
				'level' => $this->levelName,
				'groupid' => $this->vipGroupId,
				'levelid' => $this->levelId ,
				'level_value' => $this->vipLevel
			]);

			$now = $this->utils->getNowForMysql();
			if ($this->process_mock('now', $now)){
				$this->appendToDebugLog('use mock now', [ 'now' => $now ]);
			}

			// 1: Check time of application
			$now_dstr = date('dHis', strtotime($now));
			$app_intvl_from	= date('dHis', strtotime("2020-08-{$description['apply_interval_from']}"));
			$app_intvl_to	= date('dHis', strtotime("2020-08-{$description['apply_interval_to']}"));

			$this->appendToDebugLog([ 'now_dstr' => $now_dstr, 'application_interval' => [ $app_intvl_from, $app_intvl_to ] ]);

			if ($now_dstr < $app_intvl_from || $now_dstr > $app_intvl_to) {
				throw new Exception('promo_custom.not_in_application_period', 11);
			}

			// 2: Check VIP level
			$bonus_settings = $description['bonus_settings'];

			$this->appendToDebugLog('mapped_bonus', [
				'setting_row'	=> $bonus_settings[$this->vipLevel] ,
				'bonus_amount'	=> $bonus_settings[$this->vipLevel]['bonus_amount']
			]);

			if (!isset($bonus_settings[$this->vipLevel]) || $bonus_settings[$this->vipLevel]['bonus_amount'] <= 0) {
				throw new Exception('promo_custom.not_in_allowed_vip_level', 12);
			}

			// 3: Check upgrade record in period
			$to_date = date("Y-m-{$description['check_interval_to']}", strtotime($now));
			$from_date = date("Y-m-{$description['check_interval_from']}", strtotime("{$now} -1 month"));
			$upg_recs = $this->callHelper('searchAllUpgradeRecords', [ $from_date, $to_date ]);
			if (empty($upg_recs)) {
				throw new Exception('promo_custom.level_upgrade_record_not_found', 13);
			}

			// Point of success
			$result = [
				'success'	=> true ,
				'message'	=> null ,
				'continue_process_after_script' => false
			];
		}
		catch (Exception $ex) {
			$this->appendToDebugLog('runBonusConditionChecker exception', [ 'code' => $ex->getCode(), 'mesg' => $ex->getMessage() ]);
			$result = [
				'success'	=> false ,
				'message'	=> $ex->getMessage() ,
				'continue_process_after_script' => false
			];
		}
		finally {
			return $result;
		}
	}

	/**
	 * generate withdrawal condition
	 * @param  array $description original description in rule
	 * @param  array $extra_info
	 * @param  boolean $dry_run
	 * @return  array ['success'=> success, 'message'=> errorMessageLang, 'withdrawal_condition_amount'=> withdrawal condition amount]
	 */
	protected function generateWithdrawalCondition($description, &$extra_info, $dry_run){

		return $this->returnUnimplemented();
	}

    /**
     * generate transfer condition
     * @param  array $description original description in rule
     * @param  array $extra_info exchange data
     * @param  boolean $dry_run
     * @return  array ['success'=> success, 'message_lang'=> errorMessageLang, 'withdrawal_condition_amount'=> withdrawal condition amount]
     */
    protected function generateTransferCondition($description, &$extra_info, $dry_run){
        return $this->returnUnimplemented();
    }

	/**
	 * release bonus
	 * @param  array $description original description in rule
	 * @param  array $extra_info
	 * @param  boolean $dry_run
	 * @return  array ['success'=> success, 'message'=> errorMessageLang, 'bonus_amount'=> bonus amount]
	 */
	protected function releaseBonus($description, &$extra_info, $dry_run){

		$result = [
			'success'		=> false ,
			'message'		=> null ,
			'bonus_amount'	=> 0
		];

		try {
			$now = $this->utils->getNowForMysql();
			if ($this->process_mock('now', $now)){
				$this->appendToDebugLog('use mock now', [ 'now' => $now ]);
			}

			$to_date = date("Y-m-{$description['check_interval_to']}", strtotime($now));
			$from_date = date("Y-m-{$description['check_interval_from']}", strtotime("{$now} -1 month"));
			$upg_recs = $this->callHelper('searchAllUpgradeRecords', [ $from_date, $to_date ]);
			if (empty($upg_recs)) {
				throw new Exception('promo_custom.level_upgrade_record_not_found', 13);
			}

			$bonus_settings = $description['bonus_settings'];
			$total_bonus = 0;
			foreach ($upg_recs as $row) {
				$new_level = $row['level_to'];
				$bonus = $bonus_settings[$new_level]['bonus_amount'];
				$this->appendToDebugLog([ "new_level" => $new_level, 'bonus' => $bonus ]);
				$total_bonus += $bonus;
			}

			$this->appendToDebugLog([ "total_bonus" => $total_bonus ]);

			$result = [
				'success'	=> true ,
				'message'	=> null ,
				'bonus_amount' => $total_bonus
			];
		}
		catch (Exception $ex) {
			$this->appendToDebugLog('releaseBonus exception', [ 'code' => $ex->getCode(), 'mesg' => $ex->getMessage() ]);
			$result = [
				'success'	=> false ,
				'message'	=> $ex->getMessage() ,
				'bonus_amount' => 0
			];
		}
		finally {
			return $result;
		}
	}
}
