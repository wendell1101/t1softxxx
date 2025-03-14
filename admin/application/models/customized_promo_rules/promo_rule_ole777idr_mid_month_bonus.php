<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 *
 * Mid-Month Bonus
 * OGP-22225
 *
 * 最低存款额为100
 * 每月m-n号内，允许玩家申请一次免费奖金，
 * ㄧ個月一次，一個等級一次

condition:
{
    "class": "promo_rule_ole777idr_mid_month_bonus",
    "allowed_date":{
        "start": "13",
        "end": "17"
    }
    "bet_condition_times": 10,
    "bonus_settings":{
        "112" : {"percentage": '15', "max_bonus": 300},
        "113" : {"percentage": '15', "max_bonus": 300},
        "114" : {"percentage": '20', "max_bonus": 500},
        "115" : {"percentage": '25', "max_bonus": 1000},
        "116" : {"percentage": '30', "max_bonus": 1500},
        "117" : {"percentage": '35', "max_bonus": 2000},
        "118" : {"percentage": '40', "max_bonus": 2500},
        "119" : {"percentage": '45', "max_bonus": 3000},
        "120" : {"percentage": '50', "max_bonus": 10000}
    }
}
 *
 */
class Promo_rule_ole777idr_mid_month_bonus extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_ole777idr_mid_month_bonus';
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

        $bonus_settings = $description['bonus_settings'];
        $allowed_date = $description['allowed_date'];

        $today = $this->utils->getTodayForMysql();
        if($this->process_mock('today', $today)){
            $this->appendToDebugLog('use mock today', ['today' => $today]);
        }

        # check date
        $d = new DateTime($today);
        $currentDate = $d->format('Y-m-d');
        $minDate = $d->format('Y-m-') . $allowed_date['start'];
        $maxDate = $d->format('Y-m-') . $allowed_date['end'];
        $fromDate = $minDate.' '.Utils::FIRST_TIME;
        $toDate = $maxDate.' '.Utils::LAST_TIME;

        #check first apply this month
        //$count_approved_promo = $this->callHelper('count_approved_promo',[$this->promorulesId, self::DATE_TYPE_THIS_MONTH]);

        $met_deposit = false;
        $getLastDepositByDate = $this->callHelper('getLastDepositByDate',[$fromDate, $toDate]);
        if(!empty($getLastDepositByDate)){
            $depositAmount = intval($getLastDepositByDate['amount']);
            $met_deposit = $depositAmount >= 100;
            $this->appendToDebugLog('getLastDepositByDate today', ['last deposit amount' => $depositAmount]);
        }

        $isReleasedBonusToday = false;
        if(!empty($description['not_allow_promo_on_the_same_day']['promorule_ids'])){
            $_promorule_ids = $description['not_allow_promo_on_the_same_day']['promorule_ids'];
            $this->_checkNotAllowOtherPromoOnTheSameDay($_promorule_ids, $isReleasedBonusToday);
        }
        $this->appendToDebugLog('OGP31638.91', ['isReleasedBonusToday' => $isReleasedBonusToday]);
        if(!empty($isReleasedBonusToday)){
            $success=false;
            $errorMessageLang = 'notify.134';
        }else if(($currentDate >= $minDate) && ($currentDate <= $maxDate)){
            if(array_key_exists($this->levelId, $bonus_settings)){
                if($met_deposit){
                    $success = true;
                }else{
                    $errorMessageLang = 'No enough deposit';
                }
            }else{
                $errorMessageLang = 'Not right group level';
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
        $success = false;
        $errorMessageLang = null;
        $withdrawal_condition_amount = 0;

        $levelId = $this->levelId;
        $bonus_settings = $description['bonus_settings'];
        $times = $description['bet_condition_times'];

        if($times > 0){
            if(array_key_exists($levelId, $bonus_settings)){
                $setting = $bonus_settings[$levelId];
                $withdrawal_condition_amount = $this->callHelper('calcWithdrawConditionAndCheckMaxBonus',
                    [$setting['percentage']/100, $times]);

                $success = $withdrawal_condition_amount > 0;
                if(!$success){
                    if($this->playerBonusAmount <= 0){
                        $errorMessageLang='Bonus amount is not correct';
                    }else if($this->depositAmount <= 0){
                        $errorMessageLang='Deposit amount is not correct';
                    }else{
                        $errorMessageLang='Withdraw condition is not correct';
                    }
                }
            }else{
                $errorMessageLang='Not right group level';
            }
        }else{
            $errorMessageLang='Lost bet_condition_times in settings';
        }

        $result=['success'=>$success, 'message'=>$errorMessageLang, 'withdrawal_condition_amount'=>round($withdrawal_condition_amount, 2)];
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
		$success = false;
		$errorMessageLang = null;
		$bonus_amount = 0;

        $levelId = $this->levelId;
        $bonus_settings = $description['bonus_settings'];
        $lastDepositAmount = $this->depositAmount;

        if(array_key_exists($levelId, $bonus_settings)){
            $setting = $bonus_settings[$levelId];
            $this->appendToDebugLog('get bonus setting', ['bonus_settings'=>$setting, 'levelId'=>$levelId]);
            if(!empty($lastDepositAmount)){
                $bonus_amount = ($setting['percentage'] / 100) * $lastDepositAmount;
                if($bonus_amount > $setting['max_bonus']){
                    $bonus_amount = $setting['max_bonus'];
                }
                $success=true;
            }
        }

		$result=['success'=>$success, 'message'=>$errorMessageLang, 'bonus_amount'=>$bonus_amount];
		return $result;
	}
}

