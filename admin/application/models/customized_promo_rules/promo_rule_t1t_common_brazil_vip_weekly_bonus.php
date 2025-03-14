<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 * OGP-30948
 * VIP Weekly Bonus
 *
 * 每週可領取一次
 * 須符合VIP等級的最低存款條件
 * 計算方式為上週累計存款
 * 取款條件1倍
 *
condition:
{
    "class": "promo_rule_t1t_common_brazil_vip_weekly_bonus",
    "allowed_date": {
        "start": "",
        "end": ""
    },
    "allowed_release_day": "",
    "week_start": "",
    "period_min_deposit": "",
    "bonus_settings": {
        "VIP2":   { "deposit_amount": 10 ,"bonus_amount": 1},
        "VIP3":   { "deposit_amount": 20 ,"bonus_amount": 3},
        "VIP4":   { "deposit_amount": 30 ,"bonus_amount": 5},
        "VIP5":   { "deposit_amount": 50 ,"bonus_amount": 6},
        "VIP6":   { "deposit_amount": 100 ,"bonus_amount": 15},
        "VIP7":   { "deposit_amount": 200 ,"bonus_amount": 20},
        "VIP8":   { "deposit_amount": 300 ,"bonus_amount": 30},
        "VIP9":   { "deposit_amount": 500 ,"bonus_amount": 50},
        "VIP10":  { "deposit_amount": 1000 ,"bonus_amount": 100},
        "VIP11":  { "deposit_amount": 1000 ,"bonus_amount": 200},
        "VIP12":  { "deposit_amount": 1000 ,"bonus_amount": 300},
        "VIP13":  { "deposit_amount": 1000 ,"bonus_amount": 1000}
    }
}
 *
 *
 */
class Promo_rule_t1t_common_brazil_vip_weekly_bonus extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_t1t_common_brazil_vip_weekly_bonus';
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
        $levelId = $this->levelId;
        $allowed_date = isset($description['allowed_date']) ? $description['allowed_date'] : null;
        $allowed_release_days = isset($description['allowed_release_days']) ? $description['allowed_release_days'] : null;
        $week_start['week_start'] = isset($description['week_start']) ? $description['week_start'] : 'mondays';

        $fromDate = !empty($allowed_date['start']) ? $allowed_date['start'] : $this->get_date_type(self::DATE_LAST_WEEK_START, $week_start);
		$toDate = !empty($allowed_date['end']) ? $allowed_date['end'] : $this->get_date_type(self::DATE_LAST_WEEK_END, $week_start);
        
        $release_day = !empty($allowed_release_days) ? $allowed_release_days : 'Mon';
        $now_day =  date("D", strtotime($this->get_date_type(self::TO_TYPE_NOW)));
        
        //add appendToDebugLog
        $this->appendToDebugLog(__METHOD__ . 'date details', [
            'allowed_date' => $allowed_date,
            'allowed_release_days' => $allowed_release_days,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'release_day' => $release_day,
            'now_day' => $now_day
        ]);

        if(is_array($allowed_release_days)){
            if(!in_array($now_day, $allowed_release_days)){
                $errorMessageLang = 'notify.78';
                return ['success'=>$success, 'message'=>$errorMessageLang];
            }
        }else{
            if($now_day != $release_day){
                $errorMessageLang = 'notify.78';
                return ['success'=>$success, 'message'=>$errorMessageLang];
            }
        }

        #get last week deposit
        $sum_deposit_amount = $this->callHelper('sum_deposit_amount',[$fromDate, $toDate, 0]);

        $this->appendToDebugLog('player details', ['sum_deposit_amount' => $sum_deposit_amount, 'levelId' => $levelId]);

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

        return ['success'=>$success, 'message'=>$errorMessageLang, 'bonus_amount'=>$bonus_amount];
    }
}