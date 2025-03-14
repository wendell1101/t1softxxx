<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 *
 * OGP-27130
 * 總存款滿100即可申請
 * 本週 (n) 檢查存款需大於上週 (n-1) 的 並照上週的存款做獎金基數
 * 週的起迄時間以date_from, date_to為主
 * 每個週期僅限申請一次
 * 每個週期最高獎金1000
 *
 * week1 2022-10-01 ~ 2022-10-07
 * week2 2022-10-08 ~ 2022-10-15
 * week3 2022-10-16 ~ 2022-10-23
 * week4 2022-10-24 ~ 2022-10-31
 * week5 2022-11-01 ~ 2022-11-08
 *

condition:
{
    "class": "promo_rule_ole777th_deposit_weekly_bonus",
    //"total_min_deposit": 100,
    //"max_bonus_of_week": 1000,
    "max_deposit_to_apply": 1000,
    //"deposit_from_reg_to_date": "2022-09-30",
    "bonus_settings": {
		"1": {"week": 1, "date_from": "2022-10-01", "date_to": "2022-10-07", "bonus_percentage": 150, "bet_condition_times": 10, "total_min_deposit": 100},
		"2": {"week": 2, "date_from": "2022-10-08", "date_to": "2022-10-15", "bonus_percentage": 170, "bet_condition_times": 10},
		"3": {"week": 3, "date_from": "2022-10-16", "date_to": "2022-10-23", "bonus_percentage": 200, "bet_condition_times":  9},
		"4": {"week": 4, "date_from": "2022-10-24", "date_to": "2022-10-31", "bonus_percentage": 250, "bet_condition_times":  8},
		"5": {"week": 5, "date_from": "2022-11-01", "date_to": "2022-11-08", "bonus_percentage":   0, "bet_condition_times":  0}
    }
}
 *
 *
 */
class Promo_rule_ole777th_deposit_weekly_bonus extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_ole777th_deposit_weekly_bonus';
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
		$result = $this->checkCustomizeBonusCondition($description, $extra_info, $errorMessageLang);

		if(array_key_exists('bonus_amount',$result)){
			unset($result['bonus_amount']);
		}

        if(array_key_exists('withdrawal_condition_amount',$result)){
            unset($result['withdrawal_condition_amount']);
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
        $result = $this->checkCustomizeBonusCondition($description, $extra_info, $errorMessageLang);

        if(array_key_exists('bonus_amount',$result)){
            unset($result['bonus_amount']);
        }

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
		$errorMessageLang = null;
		$result = $this->checkCustomizeBonusCondition($description, $extra_info, $errorMessageLang);

        return $result;
	}

	protected function checkTotalDeposit($total_min_deposit, $deposit_from_reg_to_date){
        $result = false;

        if(!empty($deposit_from_reg_to_date)){
            $fromDate = $this->callHelper('get_date_type', [self::REGISTER_DATE]);
            $toDate = $deposit_from_reg_to_date . ' ' . Utils::LAST_TIME;
            $totalDepositAmount = $this->callHelper('sum_deposit_amount',[$fromDate, $toDate, 0]);
            $this->appendToDebugLog('total deposit from registration', [
                'fromDate' => $fromDate, 'toDate' => $toDate, 'sum_deposit_amount' => $totalDepositAmount
            ]);

            if($totalDepositAmount >= $total_min_deposit){
                $result = true;
            }
            $this->appendToDebugLog('met deposit from reg to date', ['result' => $result]);
        }

        return $result;
    }

    protected function getWeekSettings($bonus_settings, $now){
        $currentWeek = 0;
        $currentWeekSettings = null;
        $lastWeekSettings = null;
        $currentTime = strtotime($now);

        if (!empty($bonus_settings)){
            foreach ($bonus_settings as $settings){
                if (!empty($settings['datetime_from']) && !empty($settings['datetime_to'])){
                    // for testing
                    $date_from = strtotime($settings['datetime_from']);
                    $date_to = strtotime($settings['datetime_to']);
                }else{
                    $date_from = strtotime($settings['date_from'] . ' ' . Utils::FIRST_TIME);
                    $date_to = strtotime($settings['date_to'] . ' ' . Utils::LAST_TIME);
                }

                if( ($currentTime >= $date_from) && ($currentTime <= $date_to) ){
                    $currentWeek = $settings['week'];
                    $currentWeekSettings = $settings;
                    break;
                }
            }

            $lastWeekIndex = $currentWeek - 1;
            if(!empty($bonus_settings[$lastWeekIndex])){
                $lastWeekSettings = $bonus_settings[$lastWeekIndex];
            }
        }

        $this->appendToDebugLog('get week settings', [
            'current datetime' => $now, 'current Week Settings' => $currentWeekSettings, 'last Week Settings' => $lastWeekSettings
        ]);

	    return [$currentWeekSettings, $lastWeekSettings];
    }

    protected function isAllowToApplyThisWeek($bonus_settings, $currentWeekSettings){
        if(!empty($bonus_settings[1]['datetime_from']) && !empty($currentWeekSettings['datetime_to'])){
            // for testing
            $firstWeekStart = $bonus_settings[1]['datetime_from'];
            $lastWeekEnd = $currentWeekSettings['datetime_to'];
        }else{
            $firstWeekStart = $bonus_settings[1]['date_from'] . ' ' . Utils::FIRST_TIME;
            $lastWeekEnd = $currentWeekSettings['date_to'] . ' ' . Utils::LAST_TIME;
        }

        $approved_promo_range = ['start' => $firstWeekStart, 'end' => $lastWeekEnd];
        $count_approved_promo = $this->callHelper('count_approved_promo', [$this->promorulesId, self::DATE_TYPE_CUSTOMIZE, $approved_promo_range]);

        $applyWeek = $currentWeekSettings['week'];
        $canApplyTime = ($applyWeek - 1);
        $this->appendToDebugLog('is allow to apply this week', ['can apply time' => $canApplyTime, 'count_approved_promo' => $count_approved_promo]);

        if($canApplyTime == $count_approved_promo){
            $allowToApply = false; // current week already apply promo for last week bonus
            $this->appendToDebugLog('ErrMsg: player already apply last week bonus on this week', ['allowToApply' => $allowToApply]);
        }else{
            $allowToApplyLastWeekTimes = $canApplyTime-1;
            if($allowToApplyLastWeekTimes == $count_approved_promo){
                $allowToApply = true;
            }else{
                // if player missing one time to apply promo, then can not apply forever.
                $allowToApply = false;
                $this->appendToDebugLog('ErrMsg: player had ever missing one time to apply promo, then can not apply forever', ['allowToApply' => $allowToApply]);
            }
        }

        return $allowToApply;
    }

    protected function isMetDepositCondition($currentWeekSettings, $lastWeekSettings){
	    $result = ['success' => false, 'lastWeekDeposit' => 0];

	    if(!empty($lastWeekSettings['datetime_from']) && !empty($lastWeekSettings['datetime_to'])){
            // for testing
            $lastWeekFrom = $lastWeekSettings['datetime_from'];
            $lastWeekTo = $lastWeekSettings['datetime_to'];
        }else{
            $lastWeekFrom = $lastWeekSettings['date_from'] . ' ' . Utils::FIRST_TIME;
            $lastWeekTo = $lastWeekSettings['date_to'] . ' ' . Utils::LAST_TIME;
        }

	    if(!empty($currentWeekSettings['datetime_from']) && !empty($currentWeekSettings['datetime_to'])){
            // for testing
            $currentWeekFrom = $currentWeekSettings['datetime_from'];
            $currentWeekTo = $currentWeekSettings['datetime_to'];
        }else{
            $currentWeekFrom = $currentWeekSettings['date_from'] . ' ' . Utils::FIRST_TIME;
            $currentWeekTo = $currentWeekSettings['date_to'] . ' ' . Utils::LAST_TIME;
        }

	    $lastWeekRequiredMinDeposit = 0;
	    if(!empty($lastWeekSettings['total_min_deposit'])){
            $lastWeekRequiredMinDeposit = $lastWeekSettings['total_min_deposit'];
        }

        $currentWeekRequiredMinDeposit = 0;
        if(!empty($currentWeekSettings['total_min_deposit'])){
            $currentWeekRequiredMinDeposit = $currentWeekSettings['total_min_deposit'];
        }

        $lastWeekDeposit = $this->callHelper('sum_deposit_amount',[$lastWeekFrom, $lastWeekTo, 0]);
        $currentWeekDeposit = $this->callHelper('sum_deposit_amount',[$currentWeekFrom, $currentWeekTo, 0]);
        $this->appendToDebugLog('compare current week and last week deposit amount', [
            'current week total deposit' => $currentWeekDeposit, 'current week from to' => $currentWeekFrom . ' ~ ' . $currentWeekTo,
            'current week required total min deposit' => $currentWeekRequiredMinDeposit,
            'last week total deposit' => $lastWeekDeposit, 'last week from to' => $lastWeekFrom . ' ~ ' . $lastWeekTo,
            'last week required total min deposit' => $lastWeekRequiredMinDeposit,
        ]);

        if(!empty($lastWeekDeposit) && !empty($currentWeekDeposit)){
            if($currentWeekDeposit >= $lastWeekDeposit){
                $lastWeekDepositMetCondition = true;
                $currentWeekDepositMetCondition = true;

                // for check [total min deposit] >= [$lastWeekRequiredMinDeposit] on last week
                if(!empty($lastWeekRequiredMinDeposit)){
                    if(($lastWeekDeposit < $lastWeekRequiredMinDeposit)){
                        $lastWeekDepositMetCondition = false;
                    }
                }

                // for check [total min deposit] >= [$currentWeekRequiredMinDeposit] on current week
                if(!empty($currentWeekRequiredMinDeposit)){
                    if(($currentWeekDeposit < $currentWeekRequiredMinDeposit)){
                        $currentWeekDepositMetCondition = false;
                    }
                }

                if($lastWeekDepositMetCondition && $currentWeekDepositMetCondition){
                    $result['success'] = true;
                    $result['lastWeekDeposit'] = $lastWeekDeposit;
                }
            }
        }

	    return $result;
    }

	private function checkCustomizeBonusCondition($description, &$extra_info, &$errorMessageLang){
        $success = false;
	    $bonus_amount = 0;
        $withdrawal_condition_amount = 0;
        $bonus_settings = $description['bonus_settings'];
        $max_deposit_to_apply = $description['max_deposit_to_apply'];
        //$max_bonus_of_week = $description['max_bonus_of_week'];
        //$total_min_deposit = $description['total_min_deposit'];
        //$deposit_from_reg_to_date = $description['deposit_from_reg_to_date'];

        $now = $this->callHelper('get_date_type', [self::TO_TYPE_NOW]);
        if(!empty($description['now'])){
            $now = $description['now'];
        }

        //$met_min_deposit = $this->checkTotalDeposit($total_min_deposit, $deposit_from_reg_to_date);

        //if($met_min_deposit){
            list($currentWeekSettings, $lastWeekSettings) = $this->getWeekSettings($bonus_settings, $now);

            if(!empty($currentWeekSettings) && !empty($lastWeekSettings)){
                $isAllowToApplyThisWeek = $this->isAllowToApplyThisWeek($bonus_settings, $currentWeekSettings);

                if($isAllowToApplyThisWeek){
                    $isMetDepositCondition = $this->isMetDepositCondition($currentWeekSettings, $lastWeekSettings);

                    if($isMetDepositCondition['success']){
                        $success = true;
                        $lastWeekDeposit = $isMetDepositCondition['lastWeekDeposit'];
                        $bonus_percentage = $lastWeekSettings['bonus_percentage'];

                        if($lastWeekDeposit >= $max_deposit_to_apply){
                            $lastWeekDeposit = $max_deposit_to_apply;
                        }

                        $bonus_amount = $lastWeekDeposit * ($bonus_percentage / 100);

//                        if($bonus_amount > $max_bonus_of_week){
//                            $bonus_amount = $max_bonus_of_week;
//                        }

                        $withdrawal_condition_amount = $bonus_amount * $lastWeekSettings['bet_condition_times'];
                    }else{
                        $errorMessageLang = 'promo_custom.deposit_donot_match_the_requirement';
                        $this->appendToDebugLog('ErrMsg: not enough deposit');
                    }
                }else{
                    $errorMessageLang = 'You are not valid in this promo';
                    $this->appendToDebugLog('ErrMsg: not allow to apply promo');
                }
            }else{
                $errorMessageLang = 'promo_rule.common.error';
                $this->appendToDebugLog('ErrMsg: not met week settings');
            }
        //}else{
            //$errorMessageLang =  'You are not valid in this promo';
            //$this->appendToDebugLog('ErrMsg: not met minimum total deposit from reg to date');
        //}

        return ['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonus_amount, 'withdrawal_condition_amount'=>round($withdrawal_condition_amount, 2)];
	}
}
