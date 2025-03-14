<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 *
 * Up to 280% Welcome Bonus
 *
 * OGP-29630
 * 第一次存款 100%
 * 第二次存款  80%
 * 第三次存款 100%
 * 每筆存款最高可得2888
 * 
 * 
condition:
{
    "class": "promo_rule_r99_deposit_welcome_bonus",
    "max_deposit_bonus": 2888,
    "bonus_settings": {
        "first_deposit_percentage": 100,
        "second_deposit_percentage": 80,
        "third_deposit_percentage": 100 
    }
}
 *
 *
 */
class Promo_rule_r99_deposit_welcome_bonus extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_r99_deposit_welcome_bonus';
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
        $result = $this->checkCustomizeBonusCondition($description, $errorMessageLang);

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
		$errorMessageLang=null;
        $result = $this->checkCustomizeBonusCondition($description, $errorMessageLang);

		return $result;
	}

    private function checkCustomizeBonusCondition($description, &$errorMessageLang){
        $success = false;
        $bonus_amount = 0;
        $max_deposit_bonus = $description['max_deposit_bonus'];
        $first_deposit_percentage  = $description['bonus_settings']['first_deposit_percentage'];
        $second_deposit_percentage = $description['bonus_settings']['second_deposit_percentage'];
        $third_deposit_percentage  = $description['bonus_settings']['third_deposit_percentage'];

        $promorule = $this->promorule;
	    $promoRuleId = $promorule['promorulesId'];

        $fromDate = $this->get_date_type(self::REGISTER_DATE);
		$toDate = $this->get_date_type(self::TO_TYPE_NOW);
        
        // get the deposit times of career
        $countDepositByPlayerId = $this->callHelper('countDepositByPlayerId',[$fromDate, $toDate,0]);
        $this->appendToDebugLog('player deposit count', ['count' => $countDepositByPlayerId]);
        $this->appendToDebugLog('player register date', ['date' => $fromDate]);

        // if already deposit > 3, can't apply this promo 
        if ($countDepositByPlayerId > 3) {
            return ['success' => false, 'message' => lang('notify.80')];
        }

        // get the approved promo count
	    $releasedBonusCnt = $this->callHelper('count_approved_promo',[$promoRuleId, self::DATE_TYPE_CUSTOMIZE]);
        $this->appendToDebugLog('check player approved promo count', ['count' => $releasedBonusCnt]);

        // get first, second and third deposit of career
        $getFirstDepositByDate = $this->callHelper('getAnyDepositByDate',[$fromDate, $toDate, 'first', null, null]);
        $firstDepositAmount = intval($getFirstDepositByDate['amount']);
        $this->appendToDebugLog('first deposit', ['amount' => $firstDepositAmount]);

        $getSecondDepositByDate = $this->callHelper('getAnyDepositByDate',[$fromDate, $toDate, 2, null, null]);
        $secondDepositAmount = intval($getSecondDepositByDate['amount']);
        $this->appendToDebugLog('second deposit', ['amount' => $secondDepositAmount]);

        $getThirdDepositByDate = $this->callHelper('getAnyDepositByDate',[$fromDate, $toDate, 3, null, null]);
        $thirdDepositAmount = intval($getThirdDepositByDate['amount']);
        $this->appendToDebugLog('third deposit', ['amount' => $thirdDepositAmount]);

        // use first deposit apply promo
        if(empty($releasedBonusCnt)){
            if(!empty($firstDepositAmount)){
                $success = true;
                $bonus_amount = $firstDepositAmount * ($first_deposit_percentage / 100);
            }else{
                return ['success' => false, 'message' => lang('notify.79')];
            }
        }

        // use second deposit apply promo
        if($releasedBonusCnt == 1){
            if(!empty($secondDepositAmount)){
                $success = true;
                $bonus_amount = $secondDepositAmount * ($second_deposit_percentage / 100);
            }else{
                return ['success' => false, 'message' => lang('notify.79')];
            }
        }

        // use third deposit apply promo
        if($releasedBonusCnt == 2){
            if(!empty($thirdDepositAmount)){
                $success = true;
                $bonus_amount = $thirdDepositAmount * ($third_deposit_percentage / 100);
            }else{
                return ['success' => false, 'message' => lang('notify.79')];
            }
        }

        // already apply promo > third
        if($releasedBonusCnt > 2){
            return ['success' => false, 'message' => lang('notify.83')];
        }

        if ($bonus_amount > $max_deposit_bonus) {
            $bonus_amount = $max_deposit_bonus;
        }

        return ['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonus_amount];
    }
}

