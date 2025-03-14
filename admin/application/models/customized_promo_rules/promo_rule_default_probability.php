<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 *
 * Default probability bonus
 *
{
    "class": "promo_rule_default_probability",
    "bonus_settings":[
        {"odds": 81.2, "bonus": 2, "count": 100000, "is_default": true},
        {"odds": 12.3, "bonus": 10, "count": 100},
        {"odds": 5.5, "bonus": 40, "count": 20},
        {"odds": 1, "bonus": 100, "count": 2}
    ]
}
 *
 *
 */
class Promo_rule_default_probability extends Abstract_promo_rule{

    public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
        parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
    }

    public function getClassName(){
        return 'Promo_rule_default_probability';
    }

    /**
     * run bonus condition checker
     * @param  array $description original description in rule
     * @param  array $extra_info
     * @param  boolean $dry_run
     * @return  array ['success'=> success, 'message'=> errorMessageLang]
     */
    protected function runBonusConditionChecker($description, &$extra_info, $dry_run){
        //always return true unless have others
        $success=true;
        return $result=['success' => $success, 'message' => null];
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
        $bonus_settings=$description['bonus_settings'];
        $item=$this->getRandomItemByOdds($bonus_settings);
        $success=!empty($item);
        $errorMessageLang=null;
        $bonusAmount=0;
        $depositAmount=null;
        if($success){
            $bonusAmount=$item['bonus'];
        }
        $result=['success'=> $success, 'message'=> $errorMessageLang, 'bonus_amount'=> $bonusAmount, 'deposit_amount'=> $depositAmount];
        return $result;
    }
}
