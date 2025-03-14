<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 *
 * PAYDAY FREEBET OLE777
 *
 * OGP-21675
 *
 * 优惠期间，玩家仅能申请一次
 * 促销期间，玩家只要至少有300笔存款（累计）和500笔提款金额（累计）

* Bonus condition && Bonus release:
{
    "class": "promo_rule_ole777_deposit_withdrawal_bonus",
    "deposit": 300,
    "withdrawal": 500
}

 *
 *
 */
class Promo_rule_ole777_deposit_withdrawal_bonus extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_ole777_deposit_withdrawal_bonus';
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

        $min_deposit = $description['deposit'];
        $min_withdrawal = $description['withdrawal'];

        $applicationPeriodStart = $this->promorule['applicationPeriodStart'];
        $promoRuleId = $this->promorulesId;
        $now = $this->utils->getNowForMysql();

        //total deposit
        $deposit = $this->callHelper('sum_deposit_amount',[$applicationPeriodStart, $now, 0]);
        $deposit = empty($deposit) ? 0 : $deposit;

        //total withdrawal
        $withdrawal = $this->callHelper('sum_withdrawal_amount',[$applicationPeriodStart, $now, 0]);
        $withdrawal = empty($withdrawal) ? 0 : $withdrawal;

        //total applied count
        $release_date = ['start'=> $applicationPeriodStart, 'end'=> $now];
        $applied_promo = $this->callHelper('count_approved_promo',[$promoRuleId, self::DATE_TYPE_CUSTOMIZE, $release_date]);

        $this->appendToDebugLog('deposit and withdrawal and applied_promo_count', ['deposit' => $deposit, 'withdrawal' => $withdrawal, 'applied_promo_count' => $applied_promo]);

        if(!$applied_promo){
            if($deposit >= $min_deposit){
                if($withdrawal >= $min_withdrawal){
                    $success=true;
                }else{
                    $errorMessageLang = 'No enough withdrawal';
                }
            }else{
                $errorMessageLang = 'No enough deposit';
            }

        }else{
            $success = false;
            $errorMessageLang = 'notify.83';
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
        return $this->returnUnimplemented();
    }
}
