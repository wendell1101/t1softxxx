<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 *
 * OGP-24460
 * 只能申請一次
 * 会员在提款前必须拥有 Tox3（存款 + 奖金
 *
condition:
{
    "class": "promo_rule_win102_deposit_weekly_bonus",
    "allowed_date": {
        "start": "2022-01-01 00:00:00",
        "end": "2022-02-23 23:59:59"
    },
    "release_date": {
        "start": "",
        "end": ""
    },
    "bonus_settings": [
		{"min_deposit": 1000000, "max_deposit": 349999999, "bonus_percentage": 1},
		{"min_deposit": 350000000 , "max_deposit": 1019999999, "bonus_percentage": 1.5},
		{"min_deposit": 1020000000, "max_deposit": 99999999999, "bonus_percentage": 2}
    ]
}
*
*
*/
class Promo_rule_win102_deposit_weekly_bonus extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_win102_deposit_weekly_bonus';
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
		$fromDate = !empty($allowed_date['start']) ? $allowed_date['start'] : $this->get_date_type(self::DATE_LAST_WEEK_START);
		$toDate = !empty($allowed_date['end']) ? $allowed_date['end'] : $this->get_date_type(self::DATE_LAST_WEEK_END);
		$today = $this->utils->getTodayForMysql();

		if($this->process_mock('today', $today)){
			//use mock data
			$this->appendToDebugLog('use mock today', ['today'=>$today]);
		}

		$this->appendToDebugLog('runBonusConditionChecker check date', ['fromDate'=>$fromDate, 'toDate'=>$toDate, 'today'=>$today]);


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
		$success = false;
		$errorMessageLang = null;
		$allowed_date = $description['allowed_date'];
		$bonus_settings = $description['bonus_settings'];
		$fromDate = !empty($allowed_date['start']) ? $allowed_date['start'] : $this->get_date_type(self::DATE_LAST_WEEK_START);
		$toDate = !empty($allowed_date['end']) ? $allowed_date['end'] : $this->get_date_type(self::DATE_LAST_WEEK_END);
		$today = $this->utils->getTodayForMysql();

        if($this->process_mock('today', $today)){
            //use mock data
            $this->appendToDebugLog('use mock today', ['today'=>$today]);
        }

		$request = $this->checkCustomizeBonusCondition($bonus_settings, $fromDate, $toDate, $extra_info, $description, $errorMessageLang);

        return $request;
	}

	private function checkCustomizeBonusCondition($bonus_settings, $fromDate, $toDate, &$extra_info, $description, &$errorMessageLang){
        $success = false;
	    $bonus_amount = 0;
	    $deposit_amount = 0;
	    $currentVipLevelId = $this->levelId;
	    $promorule = $this->promorule;
	    $promoRuleId = $promorule['promorulesId'];
	    $release_date = $description['release_date'];
	    $errorMessageLang =  lang('notify.79');

	    #get last week deposit
	    $sum_deposit_amount = $this->callHelper('sum_deposit_amount',[$fromDate, $toDate, 0]) * 1000;

	    #check Released Bonus
	    $checkReleasedBonus = $this->callHelper('count_approved_promo',[$promoRuleId,self::DATE_TYPE_THIS_WEEK]);

	    if(!empty($release_date['start']) && !empty($release_date['end'])){
			$checkReleasedBonus = $this->callHelper('count_approved_promo',[$promoRuleId,self::DATE_TYPE_CUSTOMIZE,$release_date]);
	    }

	    $this->appendToDebugLog('checkCustomizeBonusCondition count_approved_promo',
	    	['release_date' => $release_date, 'sum_deposit_amount' => $sum_deposit_amount, 'checkReleasedBonus' => $checkReleasedBonus]);

	    if($checkReleasedBonus){
	    	$success=false;
			$errorMessageLang =  lang('notify.83');
	    	return $result = ['success' => $success, 'message' => $errorMessageLang, 'continue_process_after_script' => FALSE];
	    }else{
	    	if (!empty($bonus_settings)) {
				if(is_array($bonus_settings)){
	                foreach ($bonus_settings as $list) {
	                    if($sum_deposit_amount >= $list['min_deposit'] && $sum_deposit_amount < $list['max_deposit']){
	                        $success = true;
	                        $bonus_amount = $sum_deposit_amount * ($list['bonus_percentage']/100);
	                        $deposit_amount = $sum_deposit_amount;
	                    } else {
	                        continue;
	                    }
	                }
	            }
				$this->appendToDebugLog('check bets amount release Bonus success', ['success' => $success,'playerId' => $this->playerId, 'fromDate' => $fromDate, 'toDate' => $toDate, 'bonus_settings' => $bonus_settings,'bonus_amount' => $bonus_amount, 'sum_deposit_amount' => $sum_deposit_amount]);
			} else {
				$errorMessageLang = 'Not exist bet Setting';
				$this->appendToDebugLog('Not exist bet Setting',['bonus_settings' => $bonus_settings]);
			}
	    }
		return $result=['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonus_amount/1000];
	}
}
