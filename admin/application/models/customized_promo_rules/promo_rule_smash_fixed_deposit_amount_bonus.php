<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 *
 * OGP-24742
 *
 * 固定日期固定金額
 * 会员每日一次
 * Withdrawal Condition(獎金) * 7倍
 *
condition:
{
    "class": "promo_rule_smash_fixed_deposit_amount_bonus",
    "allowed_date": {
        "start": "",
        "end": ""
    },
    "release_date": {
        "start": "",
        "end": ""
    },
    "bonus_settings": {
    	"2022-01-25" : { "fixed_deposit": 222, "bonus":4 },
        "2022-01-26" : { "fixed_deposit": 444, "bonus":14 },
        "2022-01-27" : { "fixed_deposit": 444, "bonus":24 },
        "2022-01-28" : { "fixed_deposit": 555, "bonus":34 },
		"2022-02-25" : { "fixed_deposit": 222, "bonus":4 },
        "2022-02-26" : { "fixed_deposit": 444, "bonus":14 },
        "2022-02-27" : { "fixed_deposit": 444, "bonus":24 },
        "2022-02-28" : { "fixed_deposit": 555, "bonus":34 },
        "2022-03-01" : { "fixed_deposit": 555, "bonus":44 },
        "2022-03-02" : { "fixed_deposit": 777, "bonus":54 },
        "2022-03-03" : { "fixed_deposit": 777, "bonus":64 }
    },
    "bet_condition_times": 7
}
*
*/
class Promo_rule_smash_fixed_deposit_amount_bonus extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_smash_fixed_deposit_amount_bonus';
	}

	/**
	 * run bonus condition checker
	 * @param  array $description original description in rule
	 * @param  array $extra_info
	 * @param  boolean $dry_run
	 * @return  array ['success'=> success, 'message'=> errorMessageLang]
	 */
	protected function runBonusConditionChecker($description, &$extra_info, $dry_run){
		$success = false;
		$errorMessageLang = null;
		$allowed_date = $description['allowed_date'];
		$bonus_settings = $description['bonus_settings'];
		$fromDate = !empty($allowed_date['start']) ? $allowed_date['start'] : $this->get_date_type(self::DATE_TODAY_START);
		$toDate = !empty($allowed_date['end']) ? $allowed_date['end'] : $this->get_date_type(self::TO_TYPE_NOW);

		$result = $this->checkCustomizeBonusCondition($bonus_settings, $fromDate, $toDate, $extra_info, $description, $errorMessageLang);

		if(array_key_exists('bonus_amount',$result)){
			unset($result['bonus_amount']);
		}

		if(array_key_exists('deposit_amount',$result)){
			unset($result['deposit_amount']);
		}

		return $result;
	}

	/**
	 * generate withdrawal condition
	 * @param  array $description original description in rule
	 * @param  array $extra_info
	 * @param  boolean $dry_run
	 * @return  array ['success'=> success, 'message'=> errorMessageLang, 'withdrawal_condition_amount'=> withdrawal condition amount]
	 */
	protected function generateWithdrawalCondition($description, &$extra_info, $dry_run){
		$success = false;
        $errorMessageLang = null;
        $withdrawal_condition_amount = 0;

        $result = $this->releaseBonus($description, $extra_info, $dry_run);

        $times = $description['bet_condition_times'];
        $bonus_amount = $result['bonus_amount'];
        $deposit_amount = $result['deposit_amount'];
        $this->appendToDebugLog('get bonus_amount and deposit_amount and times', ['bonus_amount'=>$bonus_amount, 'deposit_amount'=>$deposit_amount, 'times'=>$times]);

        if($times > 0){
            $withdrawal_condition_amount = $bonus_amount * $times;
            $success = $withdrawal_condition_amount > 0;
        }else{
            $errorMessageLang='Lost bet_condition_times in settings';
        }

        $result=['success'=>$success, 'message'=>$errorMessageLang, 'withdrawal_condition_amount'=>round($withdrawal_condition_amount, 2)];
        return $result;
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
		$success = false;
		$errorMessageLang = null;
		$result = [];
		$allowed_date = $description['allowed_date'];
		$bonus_settings = $description['bonus_settings'];
		$fromDate = !empty($allowed_date['start']) ? $allowed_date['start'] : $this->get_date_type(self::DATE_TODAY_START);
		$toDate = !empty($allowed_date['end']) ? $allowed_date['end'] : $this->get_date_type(self::TO_TYPE_NOW);

		$request = $this->checkCustomizeBonusCondition($bonus_settings, $fromDate, $toDate, $extra_info, $description, $errorMessageLang);

        if($request['success']){
        	return $request;
        }
		return $result;
	}

	private function checkCustomizeBonusCondition($bonus_settings, $fromDate, $toDate, &$extra_info, $description, &$errorMessageLang){
        $success = false;
	    $bonus_amount = 0;
	    $deposit_amount = 0;
	    $currentVipLevelId = $this->levelId;
	    $promoRuleId = $this->promorule['promorulesId'];
	    $release_date = $description['release_date'];
		$today = $this->utils->getTodayForMysql();

	    $count_approved_promo = $this->callHelper('count_approved_promo',[$promoRuleId, self::DATE_TYPE_THIS_MONTH]);

	    if(!empty($release_date['start']) && !empty($release_date['end'])){
	    	$count_approved_promo = $this->callHelper('count_approved_promo',[$promoRuleId, self::DATE_TYPE_CUSTOMIZE, $release_date]);
	    }

	    $this->appendToDebugLog('release result', ['count_approved_promo' => $count_approved_promo, 'promoRuleId' => $promoRuleId]);

	    if($count_approved_promo > 1){
	    	$success=false;
				$errorMessageLang =  lang('notify.82');
	    	return $result=['success' => $success, 'message' => $errorMessageLang, 'continue_process_after_script' => FALSE];
	    }

	    if (array_key_exists($today, $bonus_settings)) {
			$betSetting = $bonus_settings[$today];
	    }else{
	    	$success = false;
			$errorMessageLang =  lang('notify.78');
	    	return $result = ['success' => $success, 'message' => $errorMessageLang, 'continue_process_after_script' => FALSE];
	    }

	    $this->appendToDebugLog('params detail', ['betSetting' => $betSetting, 'promoRuleId' => $promoRuleId]);

		if (!empty($betSetting)) {
			$fixed_deposit = $betSetting['fixed_deposit'];
			$bonus = $betSetting['bonus'];
			$playerFixedDeposit = $this->callHelper('getPlayerFixedDeposit',[$fromDate, $toDate, $fixed_deposit]);

			$this->appendToDebugLog('player fixed deposit amount', ['betSetting' => $betSetting, 'fixed_deposit' => $fixed_deposit, 'bonus' => $bonus, 'promoRuleId' => $promoRuleId]);

			if($playerFixedDeposit == $fixed_deposit){
	    		$deposit_amount = $playerFixedDeposit;
				$bonus_amount = $bonus;
	    		$success=true;
	    	}else{
	    		$success=false;
				$errorMessageLang = lang('promo_rule.common.error');
		    	return $result=['success' => $success, 'message' => $errorMessageLang, 'continue_process_after_script' => FALSE];
	    	}

			$this->appendToDebugLog('bets amount release bonus success', ['success' => $success,'playerId' => $this->playerId, 'fromDate' => $fromDate, 'toDate' => $toDate,'bonus_amount' => $bonus_amount, 'deposit_amount' => $deposit_amount
			]);
		} else {
			$errorMessageLang = 'Not exist bet Setting';
			$this->appendToDebugLog('Not exist bet Setting',['betSetting' => $betSetting]);
		}
		return $result=['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonus_amount, 'deposit_amount' => $deposit_amount];
	}
}