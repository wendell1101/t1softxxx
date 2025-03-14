<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 *
 * Cumulative Deposits
 *
 * OGP-16680
 *
 * Accumulation deposit cycle: three weeks as a cycle

condition:
{
    "class": "promo_rule_dj002_3_weeks_cumulative",
    "inform": "时限内已累积存款额度 : %d",
    "first_cycle_start_date": "2020-01-01 00:00:00",
    "cycle_by_day" : 21,
    "amount_bonus_lists": [
        {"amount": 500, "bonus":  8},
        {"amount": 1000, "bonus": 18},
        {"amount": 3000, "bonus": 28},
        {"amount": 10000, "bonus": 108},
        {"amount": 50000, "bonus": 388},
        {"amount": 200000, "bonus": 1288},
        {"amount": 500000, "bonus": 2888},
        {"amount": 2000000, "bonus": 11888},
        {"amount": 50000000, "bonus": 28888},
        {"amount": 200000000, "bonus": 111888}
    ]
}

 *
 *
 */
class Promo_rule_dj002_3_weeks_cumulative extends Abstract_promo_rule{

    public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
        parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
    }

    public function getClassName(){
        return 'Promo_rule_dj002_3_weeks_cumulative';
    }

    /**
     * run bonus condition checker
     * @param  array $description original description in rule
     * @return  array ['is_released'=> is_released, 'deposit_amount'=> deposit_amount]
     */
    private function checkPromoRequirement($description){
        $fromDate = $this->get_cycle_startdate($description['first_cycle_start_date'], $description['cycle_by_day']);
        $toDate   = $this->utils->getCurrentDatetimeWithSeconds('Y-m-d H:i:s');

        $extra_info['start'] = $fromDate;
        $extra_info['end'] = $toDate;
        $promorulesId = $this->promorule['promorulesId'];
        $is_released  = $this->get_last_released_player_promo($promorulesId, self::DATE_TYPE_CUSTOMIZE, $extra_info);

        $deposit = $this->callHelper('sum_deposit_amount', [$fromDate, $toDate, 0]);
        $this->appendToDebugLog('check player deposit amount ', ['deposit amount'=>$deposit]);

        $requirement['is_released'] = $is_released;
        $requirement['deposit_amount'] = $deposit;
        return $requirement;
    }

    /**
     * run bonus condition checker
     * @param  array $description original description in rule
     * @param  array $extra_info
     * @param  boolean $dry_run
     * @return  array ['success'=> success, 'inform'=> informShowBeforeApply,'message'=> errorMessageLang]
     */
    protected function runBonusConditionChecker($description, &$extra_info, $dry_run){
        $success          = false;
        $errorMessageLang = null;
        $requirement = $this->checkPromoRequirement($description);
        $deposit = $requirement['deposit_amount'];

        if($requirement['is_released']){
            $errorMessageLang = lang('notify.83');
        } else {
            $minimum_deposit_requirement = $description['amount_bonus_lists'][0]['amount'];
            $this->appendToDebugLog('check minimum_deposit_requirement', ['minimum_deposit_requirement'=>$minimum_deposit_requirement]);
            if($deposit >= $minimum_deposit_requirement){
                $success = true;
            }
        }

        $inform = sprintf(lang($description['inform']), $deposit);
        $result = ['success'=>$success, 'inform' => $inform, 'message'=>$errorMessageLang, 'continue_process_after_script' => FALSE];
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
        $success=false;
        $errorMessageLang=null;
        $bonus_amount=0;

        $requirement = $this->checkPromoRequirement($description);
        $deposit = $requirement['deposit_amount'];

        if($requirement['is_released']){
            $errorMessageLang = lang('notify.83');
        } else {
            $amount_bonus_lists = $description['amount_bonus_lists'];
            if(is_array($amount_bonus_lists)){
                foreach ($amount_bonus_lists as $list) {
                    if($deposit >= $list['amount']){
                        $success = true;
                        $bonus_amount = $list['bonus'];
                    } else {
                        break;
                    }
                }
            }
        }

        $result=['success'=>$success, 'message'=>$errorMessageLang, 'bonus_amount'=>$bonus_amount];
        return $result;
    }

    /**
     * get start date of a specific repeating cycle (count in days)
     * @param string [$first_cycle_start_date] datetime of the first cycle
     * @param int [$cycle_by_day] how many days are there in a cycle, eg. 21 days for 3 weeks
     *
     * @return string first datetime of the closest cycle
     */
    private function get_cycle_startdate($first_cycle_start_date, $cycle_by_day){
        $start_date = new DateTime($first_cycle_start_date);
        $today = new DateTime("today");

        $difference = $start_date->diff($today);
        $cycles = floor($difference->days / $cycle_by_day);

        $start = $start_date->modify('+'. $cycle_by_day*$cycles .'day');
        return $start->format('Y-m-d H:i:s');
    }
}
