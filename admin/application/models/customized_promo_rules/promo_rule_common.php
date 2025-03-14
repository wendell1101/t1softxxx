<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 *
 * Default probability bonus
 *
{
    "class": "promo_rule_common",
    "not_allow_promo_on_other_days": {
        "promorule_ids": ["id_1", "id_2", "id_3"]
    },
    "not_allow_promo_on_the_same_day": {
        "promorule_ids": ["17414", "17412", "17458","17031"]
    }
}
 *
 *
 */
class Promo_rule_common extends Abstract_promo_rule {

    public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
        parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
    }

    public function getClassName(){
        return 'Promo_rule_common';
    }

    /**
     * run bonus condition checker
     * @param  array $description original description in rule
     * @param  array $extra_info
     * @param  boolean $dry_run
     * @return  array ['success'=> success, 'message'=> errorMessageLang]
     */
    protected function runBonusConditionChecker($description, &$extra_info, $dry_run){
        $success=true;
        $errorMessageLang = null;

        $isReleasedBonusToday = false;
        if(!empty($description['not_allow_promo_on_the_same_day']['promorule_ids'])){
            $_promorule_ids = $description['not_allow_promo_on_the_same_day']['promorule_ids'];
            $this->_checkNotAllowOtherPromoOnTheSameDay($_promorule_ids, $isReleasedBonusToday);
        }
        if(!empty($isReleasedBonusToday)){
            $success=false;
            $errorMessageLang = 'notify.134';
            // continue_process_after_script
        }

        //check if player apply the same series of promo on other days
        $isReleasedBonusOtherDays = false;
        if(!empty($description['not_allow_promo_on_other_days']['promorule_ids'])){
            $promorule_ids = $description['not_allow_promo_on_other_days']['promorule_ids'];
            $this->_checkNotAllowOtherPromoRecords($promorule_ids, $isReleasedBonusOtherDays);
        }
        if(!empty($isReleasedBonusOtherDays)){
            $success = false;
            $errorMessageLang = 'notify.134';
            return ['success' => $success, 'message' => $errorMessageLang];
        }

        $result = ['success' => $success, 'message' => $errorMessageLang, 'continue_process_after_script' => true];
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
        // $withdrawal_condition_amount = 0;
        // $success = $withdrawal_condition_amount > 0;
        // $result = ['success' => $success, 'withdrawal_condition_amount' => round($withdrawal_condition_amount, 2)];
        // return $result;
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
        // $transfer_condition_amount = 0;
        // $success = $transfer_condition_amount > 0;
        // $result=['success' => $success, 'message' => $errorMessageLang, 'transfer_condition_amount' => round($transfer_condition_amount, 2)];
        // return $result;
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
        // $bonus_amount = 0;
        // $success = !empty($bonus_amount);
        // $result=['success'=> $success, 'message'=> $errorMessageLang, 'bonus_amount'=> $bonus_amount];
        // return $result;
    }
}
