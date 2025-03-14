<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 *
 * 註冊送體驗金
 *
 * OGP-30277
 * 註冊未存款過的玩家，即可符合資格
 * 發放獎金：R$5
 * 流水：x10 
 * 獎金發放：手動
 *
condition:
{
    "class": "promo_rule_t1bet_registration_bonus",
	"allowed_date": {
        "start": "",
        "end": ""
    },
    "deposit_times" : 0,
    "bonus_amount": 5
}
 *
 *
 */
class promo_rule_t1bet_registration_bonus extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'promo_rule_t1bet_registration_bonus';
	}

	/**
	 * run bonus condition checker
	 * @param  array $description original description in rule
	 * @param  array $extra_info
	 * @param  boolean $dry_run
	 * @return  array ['success'=> success, 'message'=> errorMessageLang, 'continue_process_after_script' => TRUE]
	 */
	protected function runBonusConditionChecker($description, &$extra_info, $dry_run){
	
		$errorMessageLang = null;
		$result = $this->checkCustomizeBounsCondition($description, $extra_info, $errorMessageLang);

		if(array_key_exists('bonus_amount',$result)){
			unset($result['bonus_amount']);
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
		$errorMessageLang = null;
		$result = $this->checkCustomizeBounsCondition($description, $extra_info, $errorMessageLang);
		return $result;
	}

	private function checkCustomizeBounsCondition($description, &$extra_info, &$errorMessageLang){
		$success = false;
		$bonus_amount = 0;
        $depositTimes = $description['deposit_times'];
		$allowed_date = $description['allowed_date'];
		$fromDate = !empty($allowed_date['start']) ? $allowed_date['start'] : $this->get_date_type(self::REGISTER_DATE);
		$toDate = !empty($allowed_date['end']) ? $allowed_date['end'] : $this->get_date_type(self::TO_TYPE_NOW);
		$this->appendToDebugLog('depositPeriodDate',['fromDate' => $fromDate,'toDate' => $toDate]);

		$countDepositByPlayerId = $this->callHelper('countDepositByPlayerId',[$fromDate, $toDate,0]);
        $this->appendToDebugLog('countDepositByPlayerId',['countDepositByPlayerId' => $countDepositByPlayerId]);

        if($countDepositByPlayerId > $depositTimes){
			$errorMessageLang = 'promo_custom.deposit_donot_match_the_requirement';
        }else{
			$success = true;
			$bonus_amount = $description['bonus_amount'];
        }

		return ['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonus_amount];
	}

}

