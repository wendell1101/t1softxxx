<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 *
 * 新手玩家礼包
 *
 * OGP-25122
 * 注册至今只要有一笔存款>=50, 即符合条件
 *

condition:
{
    "class": "promo_rule_smash_check_exist_deposit_bonus",
    "deposit_amount": 50
}

 *
 *
 */
class Promo_rule_smash_check_exist_deposit_bonus extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_smash_check_exist_deposit_bonus';
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

        $min_deposit = $description['deposit_amount'];
        $fromDate = $this->get_date_type(self::REGISTER_DATE);
        $toDate = $this->get_date_type(self::TO_TYPE_NOW);

        $topDepositAmount = $this->callHelper('getTopDepositByDate', [$fromDate, $toDate]);
        $this->appendToDebugLog('getTopDepositByDate',['single_max_deposit'=>$topDepositAmount]);

        if($topDepositAmount >= $min_deposit){
            $success = true;
        }else{
            $errorMessageLang = 'promo_custom.deposit_donot_match_the_requirement';
        }

		return ['success' => $success, 'message' => $errorMessageLang, 'continue_process_after_script' => FALSE];
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

