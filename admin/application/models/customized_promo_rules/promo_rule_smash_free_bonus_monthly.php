<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 *
 * Special bonus on the 5th of each month
 * OGP-24453
 *
 * 每个月的5号0点~24点开启，每个月領一次
 * 从上个月5号0点开始计算，系统会根据您上月本月存款和投注的累计天数决定您的现金返还比例。
 * 当玩家在一天内有存款记录并进行投注，即算作一个有效的天数。
 * 每月m-n号内，允许玩家申请一次免费奖金，
 *

condition:
{
    "class": "promo_rule_smash_free_bonus_monthly",
    "allowed_day": "05",
    "bonus_settings":[
        {"accumulated_days": 30, "percentage": 15.55},
        {"accumulated_days": 25, "percentage": 5.55},
        {"accumulated_days": 20, "percentage": 3.55},
        {"accumulated_days": 15, "percentage": 2.55},
        {"accumulated_days": 10, "percentage": 1.55}
    ]
}
 *
 */
class Promo_rule_smash_free_bonus_monthly extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_smash_free_bonus_monthly';
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

        $d = new DateTime($today);
        $allowed_day = $description['allowed_day'];
        $currentDate = $d->format('Y-m-d');
        $allowDate = $d->format('Y-m-') . $allowed_day;
        $this->appendToDebugLog('check allowed_date', ['allowed_date' => $allowed_day]);

        $applyDateRange = ['start' => $allowDate.' '.Utils::FIRST_TIME, 'end' => $allowDate.' '.Utils::LAST_TIME];
        $applied_in_this_month = $this->callHelper('count_approved_promo', [$this->promorulesId, self::DATE_TYPE_CUSTOMIZE, $applyDateRange]);
        $this->appendToDebugLog('check apply record this month', ['result' => $applied_in_this_month]);

        $from = date('Y-m-d', strtotime("$allowDate -1 month"));
        $to = date('Y-m-d', strtotime("$allowDate -1 day"));
        $this->appendToDebugLog('check deposit / bet date range', ['from' => $from, 'to' => $to]);

        list($bet_date, $deposit_date) = $this->callHelper('getBetAndDepositDateByDate', [$from, $to]);
        $this->appendToDebugLog('date have deposit and bet', ['bet_date' => $bet_date, 'deposit_date' => $deposit_date]);

        $player_accumulated_days = array_intersect($bet_date, $deposit_date);
        $accumulated_days_count = count($player_accumulated_days);
        $this->appendToDebugLog('the same date have deposit and bet', ['date' => $player_accumulated_days, 'count' => $accumulated_days_count]);

        if(empty($applied_in_this_month)){
            if($currentDate == $allowDate){
                if(!empty($accumulated_days_count)){
                    $success = true;
                }else{
                    $errorMessageLang = 'promo_rule.common.error';
                }
            }else{
                $errorMessageLang = 'Not right date';
            }
        }else{
            $errorMessageLang = 'notify.83';
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

        $d = new DateTime($today);
        $allowed_day = $description['allowed_day'];
        $currentDate = $d->format('Y-m-d');
        $allowDate = $d->format('Y-m-') . $allowed_day;
        $this->appendToDebugLog('check allowed_date', ['allowed_date' => $allowed_day]);

        $applyDateRange = ['start' => $allowDate.' '.Utils::FIRST_TIME, 'end' => $allowDate.' '.Utils::LAST_TIME];
        $applied_in_this_month = $this->callHelper('count_approved_promo', [$this->promorulesId, self::DATE_TYPE_CUSTOMIZE, $applyDateRange]);
        $this->appendToDebugLog('check apply record this month', ['result' => $applied_in_this_month]);

        $from = date('Y-m-d', strtotime("$allowDate -1 month"));
        $to = date('Y-m-d', strtotime("$allowDate -1 day"));
        $this->appendToDebugLog('check deposit / bet date range', ['from' => $from, 'to' => $to]);

        list($bet_date, $deposit_date) = $this->callHelper('getBetAndDepositDateByDate', [$from, $to]);
        $this->appendToDebugLog('date have deposit and bet', ['bet_date' => $bet_date, 'deposit_date' => $deposit_date]);

        $player_accumulated_days = array_intersect($bet_date, $deposit_date);
        $accumulated_days_count = count($player_accumulated_days);
        $this->appendToDebugLog('the same date have deposit and bet', ['date' => $player_accumulated_days, 'count' => $accumulated_days_count]);

        $percentage = 0;
        $settings = $description['bonus_settings'];

        if(!empty($accumulated_days_count)){
            $fromDatetime = $from . ' ' . Utils::FIRST_TIME;
            $toDatetime = $to.' '.Utils::LAST_TIME;

            //total deposit
            $deposit = $this->callHelper('sum_deposit_amount',[$fromDatetime, $toDatetime, 0]);
            $deposit = empty($deposit) ? 0 : $deposit;

            //total withdrawal
            $withdrawal = $this->callHelper('sum_withdrawal_amount',[$fromDatetime, $toDatetime, 0]);
            $withdrawal = empty($withdrawal) ? 0 : $withdrawal;

            $this->appendToDebugLog('bonus result', ['deposit' => $deposit, 'withdrawal' => $withdrawal]);
        }

        if(empty($applied_in_this_month)){
            if($currentDate == $allowDate){
                if(!empty($accumulated_days_count)){
                    foreach ($settings as $setting){
                        if($accumulated_days_count >= $setting['accumulated_days']){
                            $percentage = $setting['percentage'] / 100;
                            $success = true;
                            $this->appendToDebugLog('percentage', $percentage);
                            break;
                        }
                    }

                    if($success && !empty($percentage)){
                        $bonus_amount = ($deposit - $withdrawal) * $percentage;
                        $this->appendToDebugLog('bonus result', ['deposit' => $deposit, 'withdrawal' => $withdrawal, 'percentage' => $percentage]);
                    }else{
                        $errorMessageLang = 'Sorry, you cannot apply this promotion yet';
                    }
                }
            }
        }

		$result=['success'=>$success, 'message'=>$errorMessageLang, 'bonus_amount'=>$bonus_amount];
		return $result;
	}
}

