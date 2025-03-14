<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 *
 * 圣诞专享充送好礼
 *
 * OGP-20523
 *
 * 当日累计充值金额达到相应门槛，即可获得相应礼金。
 * 每天每个用户限制获取1次彩金，如同一人拥有多账号只计算为一个有效用户。

condition:
{
    "class": "promo_rule_whatsbet_christmas_bonus",
    "bonus_condition_list":[
        {"min_deposit": 10000, "bonus_amount": 88},
        {"min_deposit": 50000, "bonus_amount": 588},
        {"min_deposit": 100000, "bonus_amount": 1088},
        {"min_deposit": 200000, "bonus_amount": 2088},
        {"min_deposit": 500000, "bonus_amount": 5088}
    ]
}
 *
 */
class Promo_rule_whatsbet_christmas_bonus extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_whatsbet_christmas_bonus';
	}

    /**
     * run bonus condition checker
     * @param  array $description original description in rule
     * @return  array ['is_released'=> is_released, 'deposit_amount'=> deposit_amount]
     */
    private function checkPromoRequirement(){
        $fromDate = $this->get_date_type(self::DATE_TODAY_START);
        $toDate   = $this->utils->getNowForMysql();

        $extra_info['start'] = $fromDate;
        $extra_info['end'] = $toDate;

        $promorulesId = $this->promorule['promorulesId'];
        $is_released  = $this->get_last_released_player_promo($promorulesId, self::DATE_TYPE_CUSTOMIZE, $extra_info);

        $deposit = $this->callHelper('sum_deposit_amount', [$fromDate, $toDate, 0]);
        $this->appendToDebugLog('check player deposit amount ', ['deposit amount'=>$deposit]);

        $requirement['is_released'] = $is_released;
        $requirement['deposit_amount'] = $deposit;
        return $requirement;
    }

	/**
	 * run bonus condition checker
	 * @param  array $description original description in rule
	 * @param  array $extra_info
	 * @param  boolean $dry_run
	 * @return  array ['success'=> success, 'message'=> errorMessageLang, 'continue_process_after_script' => TRUE]
	 */
	protected function runBonusConditionChecker($description, &$extra_info, $dry_run){
		$success=false;
		$errorMessageLang=null;
        $requirement = $this->checkPromoRequirement();
        $deposit = $requirement['deposit_amount'];

        if($requirement['is_released']){
            $errorMessageLang = 'Reached today request limit';
        } else {
            $minimum_deposit_requirement = $description['bonus_condition_list'][0]['min_deposit'];
            $this->appendToDebugLog('check minimum_deposit_requirement', ['minimum_deposit_requirement' => $minimum_deposit_requirement]);
            if ($deposit >= $minimum_deposit_requirement) {
                $success = true;
            }else{
                $errorMessageLang = 'No enough deposit';
            }
        }

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
        $success=false;
        $errorMessageLang=null;
        $bonus_amount=0;

        $requirement = $this->checkPromoRequirement();
        $deposit = $requirement['deposit_amount'];

        if($requirement['is_released']){
            $errorMessageLang = 'Reached today request limit';
        } else {
            $amount_bonus_lists = $description['bonus_condition_list'];
            if(is_array($amount_bonus_lists)){
                foreach ($amount_bonus_lists as $list) {
                    if($deposit >= $list['min_deposit']){
                        $success = true;
                        $bonus_amount = $list['bonus_amount'];
                    } else {
                        break;
                    }
                }
            }
        }

		$result=['success'=>$success, 'message'=>$errorMessageLang, 'bonus_amount'=>$bonus_amount];
		return $result;
	}
}

