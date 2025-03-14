<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 *
 * OGP-31152
 * 生涯累積存款,從註冊日到allowed_date end
 * 1倍取款條件
 * 玩家需到玩家中心申請優惠, 自動發放獎金
 *
condition:
{
    "class": "promo_rule_sssbet_career_deposit_bonus",
    "allowed_date": {
        "start": "",
        "end": "2023-10-20 00:00:00"
    },
    "release_date": {
        "start": "",
        "end": ""
    },
    "bonus_settings": [
        {"min_deposit": 5, "max_deposit": 100, "bonus_amount": 3},
        {"min_deposit": 100, "max_deposit": 1000, "bonus_amount": 5},
        {"min_deposit": 1000, "max_deposit": -1, "bonus_amount": 50}
    ]
}
*
*
*/
class Promo_rule_sssbet_career_deposit_bonus extends Abstract_promo_rule{

    public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
        parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
    }

    public function getClassName(){
        return 'Promo_rule_sssbet_career_deposit_bonus';
    }

    /**
     * run bonus condition checker
     * @param  array $description original description in rule
     * @param  array $extra_info
     * @param  boolean $dry_run
     * @return  array ['success'=> success, 'message'=> errorMessageLang]
     */
    protected function runBonusConditionChecker($description, &$extra_info, $dry_run){
        $errorMessageLang = null;

        $allowed_date = $description['allowed_date'];
        $start_date = $this->get_date_type(self::REGISTER_DATE);
        $now_date = $this->get_date_type(self::TO_TYPE_NOW);

        $this->appendToDebugLog('runBonusConditionChecker date params start', ['start_date'=>$start_date, 'now_date'=>$now_date, 'description'=>$description]);

        $fromDate = !empty($allowed_date['start']) ? $allowed_date['start'] : $start_date;
        $toDate = !empty($allowed_date['end']) ? $allowed_date['end'] : $now_date;

        $this->appendToDebugLog('runBonusConditionChecker date params end', ['fromDate' => $fromDate, 'toDate' => $toDate]);

        $result = $this->checkCustomizeBonusCondition($fromDate, $toDate, $description, $extra_info, $errorMessageLang);

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

        $allowed_date = $description['allowed_date'];
        $start_date = $this->get_date_type(self::REGISTER_DATE);
        $now_date = $this->get_date_type(self::TO_TYPE_NOW);

        $this->appendToDebugLog('runBonusConditionChecker date params start', ['start_date'=>$start_date, 'now_date'=>$now_date, 'description'=>$description]);

        $fromDate = !empty($allowed_date['start']) ? $allowed_date['start'] : $start_date;
        $toDate = !empty($allowed_date['end']) ? $allowed_date['end'] : $now_date;

        $this->appendToDebugLog('runBonusConditionChecker date params end', ['fromDate' => $fromDate, 'toDate' => $toDate]);

        $request = $this->checkCustomizeBonusCondition($fromDate, $toDate, $description, $extra_info, $errorMessageLang);

        return $request;
    }

    private function checkCustomizeBonusCondition($fromDate, $toDate, $description, &$extra_info, &$errorMessageLang){
        $success = false;
        $bonus_amount = 0;
        $bonus_settings = $description['bonus_settings'];
        $player_id = $this->playerId;

        $today = $this->utils->getTodayForMysql();
        if($this->process_mock('today', $today)){
            //use mock data
            $this->appendToDebugLog('use mock today', ['today'=>$today]);
        }

        $sum_deposit_amount = $this->callHelper('sum_deposit_amount',[$fromDate, $toDate, 0]);

        $this->appendToDebugLog(__METHOD__ . " promo params total deposit [$player_id]", [
            'sum_deposit_amount' => $sum_deposit_amount,
            'today' => $today,
        ]);

        if (!empty($bonus_settings)) {
            foreach ($bonus_settings as $list) {
                if(($list['min_deposit'] <= $sum_deposit_amount) &&
                    ($sum_deposit_amount < $list['max_deposit'] || $list['max_deposit']<0)){
                    //max_max<0 means no limit
                    $success = true;
                    $bonus_amount = $list['bonus_amount'];
                    break;
                } else {
                    $errorMessageLang = 'promo_custom.deposit_sum_insufficient';
                }
            }
        } else {
            $errorMessageLang = 'promo_rule.common.error';
        }

        $this->appendToDebugLog(__METHOD__ . " promo params bonus result [$player_id]", [
            'bonus_amount' => $bonus_amount,
            'success' => $success,
            'errorMessageLang' => $errorMessageLang,
        ]);

        return ['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonus_amount];
    }
}