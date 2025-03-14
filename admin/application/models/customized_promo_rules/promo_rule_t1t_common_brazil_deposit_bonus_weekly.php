<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';
/**
 *
 * Deposit Bonus Weekly
 *
 * OGP-30955
 *
 * Bonus condition && Bonus release:
    {
        "class": "promo_rule_t1t_common_brazil_deposit_bonus_weekly",
        "bonus_settings": [
            {"min_deposit": 100,     "bonus_amount": 5},
            {"min_deposit": 500,     "bonus_amount": 13},
            {"min_deposit": 1000,    "bonus_amount": 18},
            {"min_deposit": 3000,    "bonus_amount": 25},
            {"min_deposit": 5000,    "bonus_amount": 38},
            {"min_deposit": 10000,   "bonus_amount": 88},
            {"min_deposit": 30000,   "bonus_amount": 188},
            {"min_deposit": 50000,   "bonus_amount": 588},
            {"min_deposit": 80000,   "bonus_amount": 988},
            {"min_deposit": 100000,  "bonus_amount": 2999},
            {"min_deposit": 300000,  "bonus_amount": 8888},
            {"min_deposit": 500000,  "bonus_amount": 28888},
            {"min_deposit": 1000000, "bonus_amount": 58888}
        ]
    }
}
 */
class promo_rule_t1t_common_brazil_deposit_bonus_weekly extends Abstract_promo_rule{

    public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
        parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
    }

    public function getClassName(){
        return 'promo_rule_t1t_common_brazil_deposit_bonus_weekly';
    }

    /**
     * run bonus condition checker
     * @param  array $description original description in rule
     * @param  array $extra_info
     * @param  boolean $dry_run
     * @return  array ['success'=> success, 'message'=> errorMessageLang]
     * condition
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

    private function getTotalDepositAmount($from_date, $to_date){
        $total_deposit_amount = 0;
        $total_deposit_amount = $this->callHelper('sum_deposit_amount',[$from_date, $to_date, 0]);
        return $total_deposit_amount;
    }


    private function getTotalReleaseBonus($from_date, $to_date){
        $total_release_bonus  = 0;
        $_extra_info['start'] = $from_date;
        $_extra_info['end']   = $to_date;
        $apply_record = $this->callHelper('get_all_released_player_promo', [$this->promorulesId, self::DATE_TYPE_CUSTOMIZE, $_extra_info]);

        if(!empty($apply_record)){
            foreach ($apply_record as $record){
                $total_release_bonus += $record['bonusAmount'];
            }
        }

        return $total_release_bonus;
    }

    private function checkCustomizeBounsCondition($description, &$extra_info, &$errorMessageLang){
        $success           = false;
        $bonus_amount      = 0;
        $total_met_bonus   = 0;
        $accumulation_date = isset($description['accumulation_date'])?$description['accumulation_date'] : null;
        $bonus_settings    = $description['bonus_settings'];

        $_extra_info['week_start'] = isset($description['week_start'])? $description['week_start'] : 'monday';
        $from_date       = $this->get_date_type(self::DATE_THIS_WEEK_START, $_extra_info);
        $to_date         = $this->get_date_type(self::DATE_THIS_WEEK_END, $_extra_info);

        if(!empty($accumulation_date)){
            $from_date = $accumulation_date['start'];
            $to_date = $accumulation_date['end'];
        }

        $total_deposit_amount = $this->getTotalDepositAmount($from_date, $to_date);
        $total_release_bonus  = $this->getTotalReleaseBonus($from_date, $to_date);

        $this->appendToDebugLog('runBonusConditionCheckerCheck', [ 'from_date'=>$from_date, 'to_date'=>$to_date, 'total_deposit_amount'=>$total_deposit_amount, 'total_release_bonus'=> $total_release_bonus ]);

        if (!empty($bonus_settings)) {
            foreach ($bonus_settings as $list) {
                if($list['min_deposit'] <= $total_deposit_amount){
                    $total_met_bonus += $list['bonus_amount'];
                }
            }

            if(!empty($total_met_bonus)){
                $bonus_amount = $total_met_bonus - $total_release_bonus;
                if($bonus_amount > 0){
                    $success = true;
                    $this->appendToDebugLog('final release bonus amount', [ 'bonus_amount'=>$bonus_amount]);
                }else{
                    $errorMessageLang = lang('promo_custom.deposit_donot_match_the_requirement');
                }
            }else{
                $errorMessageLang = lang('notify.121');
            }
        }else {
            $errorMessageLang = lang('promo_rule.common.error');
        }


        return ['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonus_amount];
    }

}
