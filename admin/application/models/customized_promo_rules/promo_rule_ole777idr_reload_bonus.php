<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 *
 * Mid-Month Bonus
 * OGP-31906 New reload sports
 * OGP-31907 New reload Casino
 * OGP-31908 New reload Slots & fishing
 *
 * 最低存款额为100
 * 第二筆存款才可以開始申請
 * 每日可申請一次獎金
 * 只要當日申請過Mid-month bonus, 就不可申請reload bonus

condition:
{
    "class": "promo_rule_ole777idr_reload_bonus",
    "bonus_settings":{
        "112" : {"percentage": '15', "max_bonus": 300},
        "113" : {"percentage": '15', "max_bonus": 300},
        "114" : {"percentage": '20', "max_bonus": 500},
        "115" : {"percentage": '25', "max_bonus": 1000},
        "116" : {"percentage": '30', "max_bonus": 1500},
        "117" : {"percentage": '35', "max_bonus": 2000},
        "118" : {"percentage": '40', "max_bonus": 2500},
        "119" : {"percentage": '45', "max_bonus": 3000},
        "120" : {"percentage": '50', "max_bonus": 10000}
    },
    "not_allow_promo_on_other_days": {
        "promorule_ids": ["id_1", "id_2", "id_3"]
    },
    "not_allow_promo_on_the_same_day": {
        "promorule_ids": ["17031"]
    }
}
 *
 */
class Promo_rule_ole777idr_reload_bonus extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_ole777idr_reload_bonus';
	}

	/**
	 * run bonus condition checker
	 * @param  array $description original description in rule
	 * @param  array $extra_info
	 * @param  boolean $dry_run
	 * @return  array ['success'=> success, 'message'=> errorMessageLang, 'continue_process_after_script' => TRUE]
	 */
	protected function runBonusConditionChecker($description, &$extra_info, $dry_run){
        $success = false;
        $errorMessageLang = null;

        $bonus_settings = $description['bonus_settings'];
        $fromDate = $this->get_date_type(self::REGISTER_DATE);
        $toDate = $this->get_date_type(self::TO_TYPE_NOW);
        $this->appendToDebugLog('check date', ['fromDate' => $fromDate, 'toDate' => $toDate]);

        $isReleasedBonusToday = false;
        if(!empty($description['not_allow_promo_on_the_same_day']['promorule_ids'])){
            $_promorule_ids = $description['not_allow_promo_on_the_same_day']['promorule_ids'];
            $this->_checkNotAllowOtherPromoOnTheSameDay($_promorule_ids, $isReleasedBonusToday);
        }
        $this->appendToDebugLog('OGP31638.91', ['isReleasedBonusToday' => $isReleasedBonusToday]);
        if(!empty($isReleasedBonusToday)){
            $errorMessageLang = 'notify.134';
            return ['success' => $success, 'message' => $errorMessageLang];
        }

        //check if player apply the same series of promo on other days
        $isReleasedBonusOtherDays = false;
        if(!empty($description['not_allow_promo_on_other_days']['promorule_ids'])){
            $promorule_ids = $description['not_allow_promo_on_other_days']['promorule_ids'];
            $this->_checkNotAllowOtherPromoRecords($promorule_ids, $isReleasedBonusOtherDays);
        }
        if(!empty($isReleasedBonusOtherDays)){
            $errorMessageLang = 'notify.134';
            return ['success' => $success, 'message' => $errorMessageLang];
        }
        
        $deposit_cnt = $this->callHelper('countDepositByPlayerId', [$fromDate, $toDate]);
        if($deposit_cnt<1){
            $errorMessageLang = 'notify.80';
            return ['success' => $success, 'message' => $errorMessageLang];
        }

        if(!array_key_exists($this->levelId, $bonus_settings)){
            $errorMessageLang = 'Not right group level';
            return ['success' => $success, 'message' => $errorMessageLang];
        }

        $success = true;

        $result=['success'=>$success, 'message'=>$errorMessageLang, 'continue_process_after_script' => TRUE];
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
		$bonus_amount = 0;

        $levelId = $this->levelId;
        $bonus_settings = $description['bonus_settings'];
        $lastDepositAmount = $this->depositAmount;

        if(array_key_exists($levelId, $bonus_settings)){
            $setting = $bonus_settings[$levelId];
            $bonus_amount = ($setting['percentage'] / 100) * $lastDepositAmount;
            if($bonus_amount > $setting['max_bonus']){
                $bonus_amount = $setting['max_bonus'];
            }
            if(!empty($bonus_amount)){
                $success=true;
            }
            $this->appendToDebugLog('get bonus setting', ['bonus_settings'=>$setting, 'levelId'=>$levelId, 'lastDepositAmount'=>$lastDepositAmount]);
        }

		$result=['success'=>$success, 'message'=>$errorMessageLang, 'bonus_amount'=>$bonus_amount];
		return $result;
	}
}

