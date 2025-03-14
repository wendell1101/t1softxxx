<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 *
 * Ativar 100% de bônus de depósito
 *
 * OGP-28648
 * 完成前5日的存款任务，则可参与100%奖金优惠。
 * 每日领取1次
 * 领奖前，如有发起提款申请或审核中的提款记录则无法领取奖金
 *

condition:
{
    "class": "promo_rule_smash_deposit_daily_bonus_v2",
    "allowed_date": {
        "start": "2023-03-06",
        "end": "2023-03-11"
    },
    "bonus_settings": {
        "2023-03-06": {"min_deposit":   20, "betConditionTimes":  1, "bonus_amount":     1},
        "2023-03-07": {"min_deposit":   20, "betConditionTimes":  1, "bonus_amount":     1},
        "2023-03-08": {"min_deposit":   20, "betConditionTimes":  1, "bonus_amount":     1},
        "2023-03-09": {"min_deposit":   20, "betConditionTimes":  1, "bonus_amount":     2},
        "2023-03-10": {"min_deposit":   20, "betConditionTimes":  1, "bonus_amount":     2}
    },
    "final_bonus_settings" {
        "2023-03-11": {"min_deposit":    1, "percentage": 100, "betConditionTimes":  35, "max_bonus":  1888}
    }
}

 *
 *
 */
class Promo_rule_smash_deposit_daily_bonus_v2 extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'promo_rule_smash_deposit_daily_bonus_v2';
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

        if(array_key_exists('bet_condition_times',$result)){
            unset($result['bet_condition_times']);
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
        $success = false;
        $errorMessageLang = null;
        $result = $this->releaseBonus($description, $extra_info, $dry_run);

        $bonus_amount = $result['bonus_amount'];
        $times = $result['bet_condition_times'];
        $this->appendToDebugLog('get bonus_amount and bet_condition_times', ['bonus_amount'=>$bonus_amount, 'times'=>$times]);

        if($times > 0){
            $withdrawal_condition_amount = $bonus_amount * $times;
            $success = $withdrawal_condition_amount > 0;
        }else{
            $errorMessageLang='Lost bet_condition_times in settings';
        }

        $result = ['success'=>$success, 'message'=>$errorMessageLang, 'withdrawal_condition_amount'=>round($withdrawal_condition_amount, 2)];

        return $result;
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
        $bet_condition_times = 0;

        $today = $this->utils->getTodayForMysql();
        if($this->process_mock('today', $today)){
            //use mock data
            $this->appendToDebugLog('use mock today', ['today' => $today]);
        }

        $d = new DateTime($today);
        $currentDate = $d->format('Y-m-d');

        $bonus_settings = $description['bonus_settings'];
        $final_bonus_settings = $description['final_bonus_settings'];
        $allowed_date = $description['allowed_date'];
        $this->appendToDebugLog('check allowed_date', ['allowed_date' => $allowed_date, 'today' => $currentDate]);

        $minDate = $maxDate = null;
        if(!empty($allowed_date['start']) && !empty($allowed_date['end'])){
            $minDate = $this->utils->formatDateForMysql(new DateTime($allowed_date['start']));
            $maxDate = $this->utils->formatDateForMysql(new DateTime($allowed_date['end']));
        }

        if($currentDate>=$minDate && $currentDate<=$maxDate){
            //check accumulate promo date
            $count_approved_promo = 0;
            $allow_apply_finial_bonus = false;

            $total_day = count($bonus_settings);
            $bonus_date = array_keys($bonus_settings);
            $release_date['start'] = $bonus_date[0] . ' ' . Utils::FIRST_TIME;
            $release_date['end'] = $bonus_date[$total_day-1] . ' ' . Utils::LAST_TIME;

            if(!empty($release_date['start']) && !empty($release_date['end'])){
                $count_approved_promo = $this->callHelper('count_approved_promo',[$this->promorulesId, self::DATE_TYPE_CUSTOMIZE, $release_date]);
                $allow_apply_finial_bonus = ($count_approved_promo == $total_day);
            }
            $this->appendToDebugLog('allow apply final bonus', ['result' => $allow_apply_finial_bonus, 'total release promo count' => $count_approved_promo]);

            // check today deposit record
            $setting = [];
            if($allow_apply_finial_bonus && !empty($final_bonus_settings[$currentDate])){
                $setting = $final_bonus_settings[$currentDate];
            }else if (!empty($bonus_settings[$currentDate])){
                $setting = $bonus_settings[$currentDate];
            }

            if(!empty($setting)){
                $min_deposit_today = $setting['min_deposit'];
                $startDate = $currentDate . ' ' . Utils::FIRST_TIME;
                $endDate = $currentDate . ' ' . Utils::LAST_TIME;

                $playerDepositByDate = $this->callHelper('getAnyDepositByDate', [$startDate, $endDate, -1, $min_deposit_today, null, false, 'desc']);
                $this->appendToDebugLog('player deposit record', ['result' => $playerDepositByDate]);

                $playerDeposit = !empty($playerDepositByDate['amount']) ? $playerDepositByDate['amount'] : 0;
                $depositDateTime = !empty($playerDepositByDate['created_at']) ? $playerDepositByDate['created_at'] : 0;

                if(!empty($playerDeposit)){
                    $existRequestWithdrawal = $this->callHelper('isRequestWithdrawalAfterDeposit', [$depositDateTime]);
                    if(!$existRequestWithdrawal){
                        $success = true;
                        $bet_condition_times = $setting['betConditionTimes'];
                        if(!empty($setting['percentage']) && !empty($setting['max_bonus'])){
                            $bonus_amount = $playerDeposit * ( $setting['percentage'] / 100 );
                            if($bonus_amount > $setting['max_bonus']){
                                $bonus_amount = $setting['max_bonus'];
                            }
                        }else{
                            $bonus_amount = $setting['bonus_amount'];
                        }
                    }else{
                        $errorMessageLang = 'promo_custom.exist_withdrawal_request_after_depsoti';
                    }
                }else{
                    $errorMessageLang = 'promo_custom.deposit_donot_match_the_requirement';
                }
            }else{
                $errorMessageLang = 'promo_rule.common.error';
            }
        }else{
            $errorMessageLang = 'Not right date';
        }

        return ['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonus_amount, 'bet_condition_times' => $bet_condition_times];
    }
}

