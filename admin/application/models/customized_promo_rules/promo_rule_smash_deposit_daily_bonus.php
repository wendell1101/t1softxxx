<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 *
 * Bônus de Mistério de Carnaval
 *
 * OGP-28316
 * 每位玩家仅限每天领取1次
 * 领奖前，如有发起提款申请或审核中的提款记录则无法领取奖金
 *

condition:
{
    "class": "promo_rule_smash_deposit_daily_bonus",
    "allowed_date": {
        "start": "2023-02-16",
        "end": "2023-02-25"
    },
    "bonus_settings": {
        "2023-02-16": {"min_deposit":   20, "betConditionTimes":  0, "bonus_amount":     1},
        "2023-02-17": {"min_deposit":   20, "betConditionTimes":  0, "bonus_amount":     2},
        "2023-02-18": {"min_deposit":   20, "betConditionTimes":  0, "bonus_amount":     3},
        "2023-02-19": {"min_deposit":   50, "betConditionTimes": 10, "bonus_amount":     4},
        "2023-02-20": {"min_deposit":   50, "betConditionTimes": 10, "bonus_amount":     5},
        "2023-02-21": {"min_deposit":  200, "betConditionTimes": 30, "bonus_amount":    18},
        "2023-02-22": {"min_deposit":  300, "betConditionTimes": 30, "bonus_amount":    28},
        "2023-02-23": {"min_deposit":  500, "betConditionTimes": 30, "bonus_amount":    58},
        "2023-02-24": {"min_deposit": 1000, "betConditionTimes": 35, "bonus_amount":   128},
        "2023-02-25": {"min_deposit": 5000, "betConditionTimes": 35, "bonus_amount":  1028}
    }
}

 *
 *
 */
class Promo_rule_smash_deposit_daily_bonus extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'promo_rule_smash_deposit_daily_bonus';
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
        $errorMessageLang = null;
        $result = $this->releaseBonus($description, $extra_info, $dry_run);

        $bonus_amount = $result['bonus_amount'];
        $times = $result['bet_condition_times'];
        $this->appendToDebugLog('get bonus_amount and bet_condition_times', ['bonus_amount'=>$bonus_amount, 'times'=>$times]);

        if($times > 0){
            $withdrawal_condition_amount = $bonus_amount * $times;
            $success = $withdrawal_condition_amount > 0;
        }else{
            // this promo allow zero bet condition times
            $withdrawal_condition_amount = 0;
            $success = true;
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
        $allowed_date = $description['allowed_date'];
        $this->appendToDebugLog('check allowed_date', ['allowed_date' => $allowed_date, 'today' => $currentDate]);

        $minDate = $maxDate = null;
        if(!empty($allowed_date['start']) && !empty($allowed_date['end'])){
            $minDate = $this->utils->formatDateForMysql(new DateTime($allowed_date['start']));
            $maxDate = $this->utils->formatDateForMysql(new DateTime($allowed_date['end']));
        }

        if($currentDate>=$minDate && $currentDate<=$maxDate){
            // check today deposit record
            if(!empty($bonus_settings[$currentDate])){
                $setting = $bonus_settings[$currentDate];
                $min_deposit_today = $setting['min_deposit'];
                $startDate = $currentDate . ' ' . Utils::FIRST_TIME;
                $endDate = $currentDate . ' ' . Utils::LAST_TIME;

                $playerDepositByDate = $this->callHelper('getAnyDepositByDate', [$startDate, $endDate, -1, $min_deposit_today, null, false]);
                $this->appendToDebugLog('player deposit record', ['result' => $playerDepositByDate]);

                $playerDeposit = !empty($playerDepositByDate['amount']) ? $playerDepositByDate['amount'] : 0;
                $depositDateTime = !empty($playerDepositByDate['created_at']) ? $playerDepositByDate['created_at'] : 0;

                if(!empty($playerDeposit)){
                    $existRequestWithdrawal = $this->callHelper('isRequestWithdrawalAfterDeposit', [$depositDateTime]);
                    if(!$existRequestWithdrawal){
                        $success = true;
                        $bonus_amount = $setting['bonus_amount'];
                        $bet_condition_times = $setting['betConditionTimes'];
                    }else{
                        $errorMessageLang = 'promo_custom.exist_withdrawal_request_after_depsoti';
                    }
                }else{
                    $errorMessageLang = 'promo_custom.deposit_donot_match_the_requirement';
                }
            }else{
                $errorMessageLang = 'Not exist Setting';
            }
        }else{
            $errorMessageLang = 'Not right date';
        }

        return ['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonus_amount, 'bet_condition_times' => $bet_condition_times];
    }
}

