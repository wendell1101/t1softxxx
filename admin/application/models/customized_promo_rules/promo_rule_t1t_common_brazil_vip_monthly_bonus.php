<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 * OGP-30949
 * VIP Monthly Bonus
 *
 * 每個月可領取一次
 * 須符合VIP等級的最低存款條件
 * 計算方式為上個月的1號到月底
 * 取款條件1倍
 *
condition:
{
    "class": "promo_rule_t1t_common_brazil_vip_monthly_bonus",
    "allow_release_day": [01],
    "levelStart": "VIP2",
    "levelEnd": "VIP13",
    "bonus_settings": {
        "VIP2":   { "deposit_amount": 100 ,"bonus_amount": 2},
        "VIP3":   { "deposit_amount": 300 ,"bonus_amount": 6},
        "VIP4":   { "deposit_amount": 1000 ,"bonus_amount": 12},
        "VIP5":   { "deposit_amount": 5000 ,"bonus_amount": 15},
        "VIP6":   { "deposit_amount": 20000 ,"bonus_amount": 30},
        "VIP7":   { "deposit_amount": 100000 ,"bonus_amount": 50},
        "VIP8":   { "deposit_amount": 300000 ,"bonus_amount": 100},
        "VIP9":   { "deposit_amount": 500000 ,"bonus_amount": 200},
        "VIP10":  { "deposit_amount": 1500000 ,"bonus_amount": 300},
        "VIP11":  { "deposit_amount": 5000000 ,"bonus_amount": 500},
        "VIP12":  { "deposit_amount": 20000000 ,"bonus_amount": 1000},
        "VIP13":  { "deposit_amount": 50000000 ,"bonus_amount": 3000}     
   }
}
 *
 *
 */
class Promo_rule_t1t_common_brazil_vip_monthly_bonus extends Abstract_promo_rule{

    public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
        parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
    }

    public function getClassName(){
        
        return 'promo_rule_t1t_common_brazil_vip_monthly_bonus';
    }

    /**
     * run bonus condition checker
     * @param  array $description original description in rule
     * @param  array $extra_info
     * @param  boolean $dry_run
     * @return  array ['success'=> success, 'message'=> errorMessageLang, 'continue_process_after_script' => FALSE]
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
        $errorMessageLang = null;
        $result = $this->checkCustomizeBonusCondition($description, $errorMessageLang);
        return $result;
    }

    private function checkCustomizeBonusCondition($description, &$errorMessageLang){
        $success = false;
        $bonus_amount = 0;
        $errorMessageLang = null;
        $bonus_setting = $description['bonus_settings'];
        $allow_release_day = $description['allow_release_day'];
        $levelId = $this->levelId;
        $fromDate = $this->get_date_type(self::DATE_LAST_MONTH_START);
        $toDate = $this->get_date_type(self::DATE_LAST_MONTH_END);
        $today = date('d');

        #get last month deposit
        $sum_deposit_amount = $this->callHelper('sum_deposit_amount',[$fromDate, $toDate, 0]);

        $this->appendToDebugLog('player details', ['fromDate' => $fromDate, 'toDate' => $toDate, 'today'=> $today , 'sum_deposit_amount' => $sum_deposit_amount, 'levelId' => $levelId]);

        if (in_array($today, $allow_release_day)) {
            if (array_key_exists($levelId, $bonus_setting)) {
                if ($sum_deposit_amount >= $bonus_setting[$levelId]['deposit_amount']) {
                    $success = true;
                    $bonus_amount = $bonus_setting[$levelId]['bonus_amount'];
                } else {
                    $errorMessageLang = 'promo_custom.deposit_donot_match_the_requirement';
                    return ['success'=>$success, 'message'=>$errorMessageLang, 'bonus_amount'=>$bonus_amount];
                }
            } else {
                $errorMessageLang = 'notify.35';
                return ['success'=>$success, 'message'=>$errorMessageLang, 'bonus_amount'=>$bonus_amount];
            }
        } else {
            $errorMessageLang = 'promo_custom.not_in_the_allow_release_day';
            return ['success'=>$success, 'message'=>$errorMessageLang, 'bonus_amount'=>$bonus_amount];
        }
        return ['success'=>$success, 'message'=>$errorMessageLang, 'bonus_amount'=>$bonus_amount];
    }
}