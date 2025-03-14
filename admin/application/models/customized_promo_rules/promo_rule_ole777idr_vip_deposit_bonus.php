<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 * Custom promo rule: vip deposit bonus
 * OGP-19167
 *
 */
class promo_rule_ole777idr_vip_deposit_bonus extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'promo_rule_ole777idr_vip_deposit_bonus';
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

			$valid_from	= $description['valid_from'];
			$valid_to	= $description['valid_to'];


			// 1: Check time of application
			$dstr_now = date('dHis', strtotime($now));
			$dstr_valid_from	= date('dHis', strtotime($valid_from));
			$dstr_valid_to		= date('dHis', strtotime($valid_to));

			$this->appendToDebugLog([ 'dstr_now' => $dstr_now, 'dstr_valid' => [ $dstr_valid_from, $dstr_valid_to ] ]);

			if ($dstr_now < $dstr_valid_from || $dstr_now > $dstr_valid_to) {
				throw new Exception('promo_custom.not_in_application_period', 11);
			}

			// 2: Determine deposit sum
			$deposit_interval_to = date('Y-m-d 23:59:59');
			$wday = date('N');
			$wday_diff_to_mon = $wday - 1;
			$deposit_interval_from = date('Y-m-d 00:00:00', strtotime("-{$wday_diff_to_mon} days"));



			$deposit_amount = $this->callHelper('sum_deposit_amount_by_sale_order', [ $deposit_interval_from, $deposit_interval_to, 0 ]);

			$this->appendToDebugLog([ 'deposit_interval' => [ $deposit_interval_from, $deposit_interval_to ], 'deposit_amount' => $deposit_amount ]);

			// 3: Determine total bonus
			$bonus_settings = $description['bonus_settings'];

			$bs_matched = null;
			foreach ($bonus_settings as $bs) {
				if ($deposit_amount >= $bs['deposit_sum_from'] && (empty($bs['deposit_sum_to']) || $deposit_amount < $bs['deposit_sum_to'])) {
					$bs_matched = $bs;
					break;
				}
			}

			if (empty($bs_matched)) {
				throw new Exception('promo_custom.deposit_sum_insufficient', 12);
			}

			$this->appendToDebugLog([ 'bonus_setting_matched' => $bs_matched ]);

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

		$result = [
			'success'		=> false ,
			'message'		=> null ,
			'withdrawal_condition_amount'	=> 0
		];

		try {
			// 1: Determine deposit sum
			$deposit_interval_to = date('Y-m-d 23:59:59');
			$wday = date('N');
			$wday_diff_to_mon = $wday - 1;
			$deposit_interval_from = date('Y-m-d 00:00:00', strtotime("-{$wday_diff_to_mon} days"));

			$deposit_amount = $this->callHelper('sum_deposit_amount_by_sale_order', [ $deposit_interval_from, $deposit_interval_to, 0 ]);

			$this->appendToDebugLog([ 'deposit_interval' => [ $deposit_interval_from, $deposit_interval_to ], 'deposit_amount' => $deposit_amount ]);

			// 2: Determine total bonus
			$bonus_settings = $description['bonus_settings'];

			$bs_matched = null;
			foreach ($bonus_settings as $bs) {
				if ($deposit_amount >= $bs['deposit_sum_from'] && (empty($bs['deposit_sum_to']) || $deposit_amount < $bs['deposit_sum_to'])) {
					$bs_matched = $bs;
					break;
				}
			}

			if (empty($bs_matched)) {
				throw new Exception('promo_custom.deposit_sum_insufficient', 32);
			}

			$this->appendToDebugLog([ 'bonus_setting_matched' => $bs_matched ]);

			$total_bonus = $deposit_amount / 100 * $bs_matched['bonus'];
			$total_bonus = $total_bonus > $bs_matched['max_bonus'] ? $bs_matched['max_bonus'] : $total_bonus;

			$wd_cond_amount = $total_bonus * $bs_matched['turnover'];

			$this->appendToDebugLog([ "total_bonus" => $wd_cond_amount ]);

			$result = [
				'success'	=> true ,
				'message'	=> null ,
				'withdrawal_condition_amount' => $wd_cond_amount
			];
		}
		catch (Exception $ex) {
			$this->appendToDebugLog('releaseBonus exception', [ 'code' => $ex->getCode(), 'mesg' => $ex->getMessage() ]);
			$result = [
				'success'	=> false ,
				'message'	=> $ex->getMessage() ,
				'withdrawal_condition_amount' => 0
			];
		}
		finally {
			return $result;
		}
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
			// 1: Determine deposit sum
			$deposit_interval_to = date('Y-m-d 23:59:59');
			$wday = date('N');
			$wday_diff_to_mon = $wday - 1;
			$deposit_interval_from = date('Y-m-d 00:00:00', strtotime("-{$wday_diff_to_mon} days"));

			$deposit_amount = $this->callHelper('sum_deposit_amount_by_sale_order', [ $deposit_interval_from, $deposit_interval_to, 0 ]);

			$this->appendToDebugLog([ 'deposit_interval' => [ $deposit_interval_from, $deposit_interval_to ], 'deposit_amount' => $deposit_amount ]);

			// 2: Determine total bonus
			$bonus_settings = $description['bonus_settings'];

			$bs_matched = null;
			foreach ($bonus_settings as $bs) {
				if ($deposit_amount >= $bs['deposit_sum_from'] && (empty($bs['deposit_sum_to']) || $deposit_amount < $bs['deposit_sum_to'])) {
					$bs_matched = $bs;
					break;
				}
			}

			if (empty($bs_matched)) {
				throw new Exception('promo_custom.deposit_sum_insufficient', 22);
			}

			$this->appendToDebugLog([ 'bonus_setting_matched' => $bs_matched ]);

			$total_bonus = $deposit_amount / 100 * $bs_matched['bonus'];
			$total_bonus = $total_bonus > $bs_matched['max_bonus'] ? $bs_matched['max_bonus'] : $total_bonus;

			$this->appendToDebugLog([ "total_bonus" => $total_bonus ]);

			$result = [
				'success'	=> true ,
				'message'	=> "Total deposit: {$deposit_amount} Check interval: {$deposit_interval_from} - {$deposit_interval_to}" ,
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
