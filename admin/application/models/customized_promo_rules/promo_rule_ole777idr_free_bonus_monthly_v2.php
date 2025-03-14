<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 * 每月m-n号内，允许玩家申请一次免费奖金，
 * ㄧ個月一次，一個等級一次
 * 不考虑vip级别, 考虑上月累积存款, 发放对应的奖金

condition:
{
    "class": "promo_rule_ole777idr_free_bonus_monthly_v2",
    "allowed_date":{
        "start": "01",
        "end": "03"
    },
    "bonus_settings":[
        {"lm_min_deposit": 1000, "lm_max_deposit": 2000, "bonus_amount": 17},
        {"lm_min_deposit": 2000, "lm_max_deposit": 3000, "bonus_amount": 27},
        {"lm_min_deposit": 3000, "lm_max_deposit": 4000, "bonus_amount": 37},
        {"lm_min_deposit": 4000, "lm_max_deposit": 15000, "bonus_amount": 57},
        {"lm_min_deposit": 15000, "lm_max_deposit": 30000, "bonus_amount": 377},
        {"lm_min_deposit": 30000, "lm_max_deposit": 50000, "bonus_amount": 577},
        {"lm_min_deposit": 50000, "lm_max_deposit": 100000, "bonus_amount": 777},
        {"lm_min_deposit": 100000, "lm_max_deposit": 200000, "bonus_amount": 1077},
        {"lm_min_deposit": 200000, "lm_max_deposit": 10000000, "bonus_amount": 1777}
    ]
}
 *
 */
class Promo_rule_ole777idr_free_bonus_monthly_v2 extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_ole777idr_free_bonus_monthly_v2';
	}

    protected function completed_player_info(){
        $conditionResult = $this->player_model->getPlayerAccountInfoStatus($this->playerId);
        $completed_player_info = $conditionResult['status'];

        if(!$completed_player_info){
            $conditionResultMissingFields = !empty($conditionResult['missing_fields']) ? $conditionResult['missing_fields'] : NULL;
            $this->appendToDebugLog('not complete player info',['missing_fields'=>$conditionResultMissingFields]);
        }

        return $completed_player_info;
    }

    protected function isVerifiedPhone($description){
        $verified_phone = true;

        if(!empty($description['verified_phone']) && $description['verified_phone']){
            $verified_phone = $this->player_model->isVerifiedPhone($this->playerId);
        }

        if(!$verified_phone){
            $this->appendToDebugLog('not verified phone',['verified_phone'=>$verified_phone]);
        }

        return $verified_phone;
    }

    protected function isVerifiedEmail($description){
        $verified_email = true;

        if(!empty($description['verified_email']) && $description['verified_email']){
            $verified_email = $this->player_model->isVerifiedEmail($this->playerId);
        }

        if(!$verified_email){
            $this->appendToDebugLog('not verified email',['verified_email'=>$verified_email]);
        }

        return $verified_email;
    }

    protected function isValidDeposit($description){
        $result = ['success' => false, 'bonus_amount' => 0];
        $bonus_settings = $description['bonus_settings'];

        // total deposit of last month
        $first_datetime_of_last_month = $this->callHelper('get_date_type', [ self::DATE_LAST_MONTH_START ] ); // $this->get_date_type
        $end_datetime_of_last_month = $this->callHelper('get_date_type', [ self::DATE_LAST_MONTH_END ] ); // $this->get_date_type
        $from_datetime = $first_datetime_of_last_month;
        $to_datetime = $end_datetime_of_last_month;

        $min_amount = 0;
        $last_month_deposit = $this->callHelper('sum_deposit_amount',[$from_datetime, $to_datetime, $min_amount]);
        $last_month_deposit = empty($last_month_deposit) ? 0 : $last_month_deposit;
        $this->appendToDebugLog('last_month_deposit with sum_deposit_amount():', ['last_month_deposit'=>$last_month_deposit, 'from_datetime'=>$from_datetime, 'to_datetime'=>$to_datetime]);

        foreach ($bonus_settings as $setting){
            if(($last_month_deposit >= $setting['lm_min_deposit']) && ($last_month_deposit < $setting['lm_max_deposit'])){
                $result['success'] = true;
                $result['bonus_amount'] = $setting['bonus_amount'];
                $this->appendToDebugLog('get bonus setting', ['bonus_settings'=>$setting, 'result'=>$result]);
            }
        }

        return $result;
    }

	/**
	 * run bonus condition checker
	 * @param  array $description original description in rule
	 * @param  array $extra_info
	 * @param  boolean $dry_run
	 * @return  array ['success'=> success, 'message'=> errorMessageLang, 'continue_process_after_script' => TRUE]
	 */
	protected function runBonusConditionChecker($description, &$extra_info, $dry_run){
        $success = false;
        $errorMessageLang = null;

        $today = $this->utils->getTodayForMysql();
        if($this->process_mock('today', $today)){
            $this->appendToDebugLog('use mock today', ['today' => $today]);
        }

        $allowed_date = $description['allowed_date'];
        $this->appendToDebugLog('check allowed_date', ['allowed_date' => $allowed_date]);

        $d = new DateTime($today);
        $currentDate = $d->format('Y-m-d');
        if(!empty($allowed_date['start']) && !empty($allowed_date['end']) && ($allowed_date['end'] == 'end_of_the_month')){
            $minDate = $d->format('Y-m-').$allowed_date['start'];
            $maxDate = $this->callHelper('get_date_type', [self::DATE_THIS_MONTH_END]);
        }else{
            $minDate = $d->format('Y-m-').$allowed_date['start'];
            $maxDate = $d->format('Y-m-').$allowed_date['end'];
        }

        $this->appendToDebugLog('min max date', ['minDate'=>$minDate, 'maxDate'=>$maxDate]);

        $completed_player_info = $this->completed_player_info();
        $verified_phone = $this->isVerifiedPhone($description);
        $verified_email = $this->isVerifiedEmail($description);
        $met_deposit = $this->isValidDeposit($description);

        if(($currentDate >= $minDate) && ($currentDate <= $maxDate)){
            if($completed_player_info){
                if($verified_email){
                    if($verified_phone){
                        if($met_deposit['success']){
                            $success = true;
                        }else{
                            $errorMessageLang = 'No enough deposit';
                        }
                    }else{
                        $errorMessageLang = 'promo.rule_is_player_verified_mobile';
                    }
                }else{
                    $errorMessageLang = 'promo.rule_is_player_verified_email';
                }
            }else{
                $errorMessageLang = 'notify.93';
            }
        }else{
            $errorMessageLang = 'Not right date';
        }

        $result=['success'=>$success, 'message'=>$errorMessageLang, 'continue_process_after_script' => TRUE];
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

        $today = $this->utils->getTodayForMysql();
        if($this->process_mock('today', $today)){
            $this->appendToDebugLog('use mock today', ['today' => $today]);
        }

        $allowed_date = $description['allowed_date'];
        $this->appendToDebugLog('check allowed_date', ['allowed_date' => $allowed_date]);

        $d = new DateTime($today);
        $currentDate = $d->format('Y-m-d');
        if(!empty($allowed_date['start']) && !empty($allowed_date['end']) && ($allowed_date['end'] == 'end_of_the_month')){
            $minDate = $d->format('Y-m-').$allowed_date['start'];
            $maxDate = $this->callHelper('get_date_type', [self::DATE_THIS_MONTH_END]);
        }else{
            $minDate = $d->format('Y-m-').$allowed_date['start'];
            $maxDate = $d->format('Y-m-').$allowed_date['end'];
        }

        $this->appendToDebugLog('min max date', ['minDate'=>$minDate, 'maxDate'=>$maxDate]);

        $completed_player_info = $this->completed_player_info();
        $verified_phone = $this->isVerifiedPhone($description);
        $verified_email = $this->isVerifiedEmail($description);
        $met_deposit = $this->isValidDeposit($description);

        if(($currentDate >= $minDate) && ($currentDate <= $maxDate)){
            if($completed_player_info){
                if($verified_email){
                    if($verified_phone){
                        if($met_deposit['success']){
                            $success = true;
                            $bonus_amount = $met_deposit['bonus_amount'];
                        }else{
                            $errorMessageLang = 'No enough deposit';
                        }
                    }else{
                        $errorMessageLang = 'promo.rule_is_player_verified_mobile';
                    }
                }else{
                    $errorMessageLang = 'promo.rule_is_player_verified_email';
                }
            }else{
                $errorMessageLang = 'notify.93';
            }
        }else{
            $errorMessageLang = 'Not right date';
        }

		$result=['success'=>$success, 'message'=>$errorMessageLang, 'bonus_amount'=>$bonus_amount];
		return $result;
	}


}

