<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 *
 * OGP-16679
 *
 * 申請區間：每月
 * 非首存優惠 (即優惠期間第一筆存款不能申請優惠 第二筆開始才可以)
 * 用上一單筆存款金額需要 > 200
 * 会员每月可获得两次奖金
 * Withdrawal Condition(存款+獎金) * 9倍
 * vip level 最高發放獎金 bonus_settings
 *
condition:
{
    "class": "promo_rule_bigbet999_vip_deposit_bouns",
    "allowed_date": {
        "start": "",
        "end": ""
    },
    "release_date": {
        "start": "",
        "end": ""
    },
    "bonus_settings": {
		"1" : { "releaseMaxBonus": 2000},
        "39" : { "releaseMaxBonus": 6000},
        "41" : { "releaseMaxBonus": 8000},
        "32" : { "releaseMaxBonus": 12000},
        "33" : { "releaseMaxBonus": 20000}
    }
}

*
*
*/
class Promo_rule_bigbet999_vip_deposit_bouns extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_bigbet999_vip_deposit_bouns';
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
		$fromDate = !empty($allowed_date['start']) ? $allowed_date['start'] : $this->get_date_type(self::DATE_THIS_MONTH_START);
		$toDate = !empty($allowed_date['end']) ? $allowed_date['end'] : $this->get_date_type(self::TO_TYPE_NOW);
		$today = $this->utils->getTodayForMysql();

		if($this->process_mock('today', $today)){
			//use mock data
			$this->appendToDebugLog('use mock today', ['today'=>$today]);
		}

		$result = $this->checkCustomizeBounsCondition($bonus_settings, $fromDate, $toDate, $extra_info, $description, $errorMessageLang);

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
            $withdrawal_condition_amount = ($bonus_amount + $deposit_amount) * $times;
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
		$fromDate = !empty($allowed_date['start']) ? $allowed_date['start'] : $this->get_date_type(self::DATE_THIS_MONTH_START);
		$toDate = !empty($allowed_date['end']) ? $allowed_date['end'] : $this->get_date_type(self::TO_TYPE_NOW);
        $today = $this->utils->getTodayForMysql();

        if($this->process_mock('today', $today)){
            //use mock data
            $this->appendToDebugLog('use mock today', ['today'=>$today]);
        }

		$request = $this->checkCustomizeBounsCondition($bonus_settings, $fromDate, $toDate, $extra_info, $description, $errorMessageLang);

        if($request['success']){
        	return $request;
        }
		return $result;
	}

	private function checkCustomizeBounsCondition($bonus_settings, $fromDate, $toDate, &$extra_info, $description, &$errorMessageLang){

        $success = false;
	    $bonus_amount = 0;
	    $maxBetAmount = 0;
	    $deposit_amount = 0;
	    $currentVipLevelId = $this->levelId;
	    $promoRuleId = $this->promorule['promorulesId'];
	    $release_date = $description['release_date'];
	    $countDepositByPlayerId = $this->callHelper('countDepositByPlayerId',[$fromDate, $toDate,0]);
	    $count_approved_promo = $this->callHelper('count_approved_promo',[$promoRuleId, self::DATE_TYPE_THIS_MONTH]);
	    $getLastDepositByDate = $this->callHelper('getLastDepositByDate',[$fromDate, $toDate]);
	    $lastDepositAmount = intval($getLastDepositByDate['amount']);

	    if(!empty($release_date['start']) && !empty($release_date['end'])){
	    	$count_approved_promo = $this->callHelper('count_approved_promo',[$promoRuleId, self::DATE_TYPE_CUSTOMIZE, $release_date]);
	    }

	    $this->appendToDebugLog('check params detail', ['countDepositByPlayerId' => $countDepositByPlayerId, 'count_approved_promo' => $count_approved_promo, 'getLastDepositByDate' => $getLastDepositByDate, 'lastDepositAmount' => $lastDepositAmount, 'promoRuleId' => $promoRuleId]);

	    if($count_approved_promo >= 2){
	    	$success=false;
				$errorMessageLang =  lang('notify.82');
	    	return $result=['success' => $success, 'message' => $errorMessageLang, 'continue_process_after_script' => FALSE];
	    }else{
			if($countDepositByPlayerId < 2){#At least one deposit
		    	$success=false;
				$errorMessageLang = lang('promo_rule.common.error');
		    	return $result=['success' => $success, 'message' => $errorMessageLang, 'continue_process_after_script' => FALSE];
		    }else{
				if($lastDepositAmount < 200){#A single deposit needs to be greater than 200 thb
		    		$success=false;
					$errorMessageLang = lang('promo_rule.common.error');
			    	return $result=['success' => $success, 'message' => $errorMessageLang, 'continue_process_after_script' => FALSE];
		    	}else{
		    		$deposit_amount = $lastDepositAmount;
		    	}
		    }
	    }

		$betSetting = empty($bonus_settings[$currentVipLevelId]) ? null : $bonus_settings[$currentVipLevelId];

		if (!empty($betSetting)) {
			if(array_key_exists($currentVipLevelId, $bonus_settings)){
				$success=true;
				$maxBetAmount = $betSetting['releaseMaxBonus'];

				if($deposit_amount > $maxBetAmount){
					$bonus_amount = $maxBetAmount;
				}else{
					$bonus_amount = $deposit_amount;
				}
				$this->appendToDebugLog('check bets amount release Bonus success', ['success' => $success,'playerId' => $this->playerId, 'fromDate' => $fromDate, 'toDate' => $toDate, 'maxBetAmount' => $maxBetAmount,'bonus_amount' => $bonus_amount, 'deposit_amount' => $deposit_amount
				]);
			}else{
				$errorMessageLang = 'Not exist this level in setting';
				$this->appendToDebugLog('Not exist this level in setting',['currentVipLevelId' => $currentVipLevelId]);
			}
		} else {
			$errorMessageLang = 'Not exist bet Setting';
			$this->appendToDebugLog('Not exist bet Setting',['betSetting' => $betSetting]);
		}

		return $result=['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonus_amount, 'deposit_amount' => $deposit_amount];
	}
}
