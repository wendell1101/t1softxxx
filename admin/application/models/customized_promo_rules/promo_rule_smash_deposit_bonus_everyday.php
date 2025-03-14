<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 *
 * deposit 200%
 *
 * OGP-24286
 * 每日存款獎金, 一天可領一次
 *

condition:
{
    "class": "promo_rule_smash_deposit_bonus_everyday",
    "allowed_date": {
        "start": "2022-10-24",
        "end": "2022-10-31"
    },
    "min_deposit_today": 200,
    "bonus_settings": [
        {"min_deposit": 500, "betConditionTimes": 40, "percentage": 200},
        {"min_deposit": 300, "betConditionTimes": 45, "percentage": 100},
        {"min_deposit": 200, "betConditionTimes": 50, "percentage":  50}
    ]
}

 *
 *
 */
class Promo_rule_smash_deposit_bonus_everyday extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_smash_deposit_bonus_everyday';
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
        $withdrawal_condition_amount = 0;
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
        $allowed_date = $description['allowed_date'];
        $this->appendToDebugLog('check allowed_date', ['allowed_date' => $allowed_date, 'today' => $currentDate]);

        $minDate = $maxDate = null;
        if(!empty($allowed_date['start']) && !empty($allowed_date['end'])){
            $minDate = $this->utils->formatDateForMysql(new DateTime($allowed_date['start']));
            $maxDate = $this->utils->formatDateForMysql(new DateTime($allowed_date['end']));
        }

        if($currentDate>=$minDate && $currentDate<=$maxDate){
            // check today deposit record
            $min_deposit_today = $description['min_deposit_today'];
            $startDate = $this->callHelper('get_date_type', [self::DATE_TODAY_START]);
            $endDate = $this->callHelper('get_date_type', [self::DATE_TODAY_END]);
            $playerDepositByDate = $this->callHelper('getAnyDepositByDate', [$startDate, $endDate, -1, $min_deposit_today, null, false, 'desc']);
            $this->appendToDebugLog('latest deposit', ['record' => $playerDepositByDate]);
            $playerDeposit = !empty($playerDepositByDate['amount']) ? $playerDepositByDate['amount'] : 0;
            $depositDateTime = !empty($playerDepositByDate['created_at']) ? $playerDepositByDate['created_at'] : 0;

            if(!empty($playerDeposit)){
                $existRequestWithdrawal = $this->callHelper('isRequestWithdrawalAfterDeposit', [$depositDateTime]);
                if(!$existRequestWithdrawal){
                    if(!empty($bonus_settings)){
                        foreach ($bonus_settings as $setting){
                            if($playerDeposit >= $setting['min_deposit']){
                                $success = true;
                                $bonus_amount = $playerDeposit * ($setting['percentage'] / 100);
                                $bet_condition_times = $setting['betConditionTimes'];
                                break;
                            }
                        }
                    }else{
                        $errorMessageLang = 'Not exist bet Setting';
                    }
                }else{
                    $errorMessageLang = 'promo_custom.exist_withdrawal_request_after_depsoti';
                }
            }else{
                $errorMessageLang = 'promo_custom.deposit_donot_match_the_requirement';
            }
        }else{
            $errorMessageLang = 'Not right date';
        }

        return ['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonus_amount, 'bet_condition_times' => $bet_condition_times];
    }
}

