<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 *
 * Songkran bonus 3777 THB
 *
 * OGP-21709
 *
 * 非首次笔笔存，至少存款200
 * 玩家每日可申请一次
 * 举例：玩家A用A IP申请过此优惠，那么在当天，仅限玩家A再次申请这个优惠，其他玩家不可以申请。

* Bonus condition && Bonus release:
{
    "class": "Promo_rule_ole777_deposit_bonus_everyday_v2",
    "amount_bonus_lists": [
        {"amount":   77, "min_deposit":   200, "max_deposit":  499},
        {"amount":  177, "min_deposit":   500, "max_deposit":  999},
        {"amount":  377, "min_deposit":  1000, "max_deposit": 4999},
        {"amount": 1377, "min_deposit":  5000, "max_deposit": 9999},
        {"amount": 3777, "min_deposit": 10000, "max_deposit": 10000}
    ]
}

 *
 *
 */
class Promo_rule_ole777_deposit_bonus_everyday_v2 extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_ole777_deposit_bonus_everyday_v2';
	}

	/**
	 * run bonus condition checker
	 * @param  array $description original description in rule
	 * @param  array $extra_info
	 * @param  boolean $dry_run
	 * @return  array ['success'=> success, 'message'=> errorMessageLang, 'continue_process_after_script' => FALSE]
	 */
	protected function runBonusConditionChecker($description, &$extra_info, $dry_run){
		$success=false;
		$errorMessageLang=null;

        $promoRuleId = $this->promorulesId;
        $fromDate = $this->callHelper('get_date_type', [self::DATE_TODAY_START]);
        $toDate = $this->callHelper('get_date_type', [self::TO_TYPE_NOW]);

        $default_count_promo_date['start'] = $fromDate;
        $default_count_promo_date['end'] = $toDate;

        $count_approved_promo = $this->callHelper('count_approved_promo',[$promoRuleId, self::DATE_TYPE_CUSTOMIZE, $default_count_promo_date]);
        $this->appendToDebugLog('count_approved_promo today', ['count_approved_promo' => $count_approved_promo]);

        $countDeposit = $this->callHelper('countDepositByPlayerId',[$fromDate, $toDate]);
        $met_deposit_cnt = $countDeposit > 1; // not first deposit

        if(!$met_deposit_cnt){
            $errorMessageLang = 'notify.43';
        }elseif (!empty($count_approved_promo)){
            $errorMessageLang = 'notify.83';
        }else{
            $success = true;
        }

        $result=['success'=>$success, 'message'=>$errorMessageLang, 'continue_process_after_script' => FALSE];

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
        $depositAmount = 0;

        $promoRuleId = $this->promorulesId;
        $fromDate = $this->callHelper('get_date_type', [self::DATE_TODAY_START]);
        $toDate = $this->callHelper('get_date_type', [self::TO_TYPE_NOW]);

        $default_count_promo_date['start'] = $fromDate;
        $default_count_promo_date['end'] = $toDate;
        $count_approved_promo = $this->callHelper('count_approved_promo',[$promoRuleId, self::DATE_TYPE_CUSTOMIZE,$default_count_promo_date]);

        $amount_bonus_list = $description['amount_bonus_lists'];
        $max_amount_bonus = end($description['amount_bonus_lists']);

        $max_deposit = $max_amount_bonus['max_deposit'];
        $max_bonus = $max_amount_bonus['amount'];

        $getLastDepositByDate = $this->callHelper('getLastDepositByDate',[$fromDate, $toDate]);
        if(!empty($getLastDepositByDate)){
            $depositAmount = intval($getLastDepositByDate['amount']);
            $this->appendToDebugLog('getLastDepositByDate today', ['depositAmount today' => $depositAmount]);
        }

        if(empty($depositAmount)){
            $result['success'] = false;
            $result['errorMessageLang'] = 'notify.39';
            return $result;
        }

        if(empty($count_approved_promo)){
            if($depositAmount > $max_deposit){
                $bonus_amount = $max_bonus;
            }else{
                foreach ($amount_bonus_list as $list){
                    if(($depositAmount >= $list['min_deposit']) && ($depositAmount <= $list['max_deposit'])){
                        $bonus_amount = $list['amount'];
                        $success = true;
                        break;
                    }
                }
            }
        }else{
            $errorMessageLang = 'notify.83';
        }

        $result=['success'=>$success, 'message'=>$errorMessageLang, 'bonus_amount'=>$bonus_amount];
        return $result;
    }
}
