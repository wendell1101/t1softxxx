<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 *
 * OGP-27697
 * OGP-27698
 * OGP-27699
 * OGP-27700
 *
 * 只允許註冊日30天內申請次優惠
 *

condition:
{
    "class": "promo_rule_ole777vn_deposit_bouns_within_ndays",
    "with_n_days": 30 (optional)
}

 *
 *
 *
 */
class Promo_rule_ole777vn_deposit_bouns_within_ndays extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_ole777vn_deposit_bouns_within_ndays';
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

        $with_n_days = 30;
        if(!empty($description['with_n_days'])){
            $with_n_days = $description['with_n_days'];
        }

        $datetime = new DateTime($this->get_date_type(self::REGISTER_DATE));
        $datetime->modify("+{$with_n_days} days");
        $maxDate = $datetime->format('Y-m-d H:i:s');
        $now = $this->get_date_type(self::TO_TYPE_NOW);

        if($now > $maxDate){
            $errorMessageLang =  lang('promo_rule.common.error');
            return ['success' => $success, 'message' => $errorMessageLang];
        }

        $success = true;

		return ['success' => $success, 'message' => $errorMessageLang, 'continue_process_after_script' => TRUE];
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