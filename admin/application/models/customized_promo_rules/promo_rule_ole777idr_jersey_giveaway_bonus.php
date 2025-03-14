<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 *
 * JERSEY GIVEAWAY
 *
 * OGP-22610
 *
 * 玩家必须在优惠期间内需累积两千万IDR的总存款

 * Bonus condition:
   {
     "class": "promo_rule_ole777idr_jersey_giveaway_bonus",
     "deposit": 20000000
   }

 *
 *
 */
class Promo_rule_ole777idr_jersey_giveaway_bonus extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_ole777idr_jersey_giveaway_bonus';
	}

	/**
	 * run bonus condition checker
	 * @param  array $description original description in rule
	 * @param  array $extra_info
	 * @param  boolean $dry_run
	 * @return  array ['success'=> success, 'message'=> errorMessageLang, 'continue_process_after_script' => FALSE]
	 */
	protected function runBonusConditionChecker($description, &$extra_info, $dry_run){
		$success = false;
		$errorMessageLang = null;

        $min_deposit = $description['deposit'];

        $applicationPeriodStart = $this->promorule['applicationPeriodStart'];
        $now = $this->utils->getNowForMysql();

        //total deposit
        $deposit = $this->callHelper('sum_deposit_amount',[$applicationPeriodStart, $now, 0]);
        $deposit = empty($deposit) ? 0 : $deposit;

        if($deposit >= $min_deposit){
            $success = true;
        }else{
            $errorMessageLang = 'No enough deposit';
        }

        $result = ['success' => $success, 'message' => $errorMessageLang, 'continue_process_after_script' => FALSE];
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
