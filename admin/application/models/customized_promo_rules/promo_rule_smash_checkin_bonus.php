<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 *
 * OGP-26726
 * 每月重置簽到
 * 每日簽到優惠
 * 每日存款>=20BRL
 * 一天只能申請一次(保留沒申請的,可一次領)
 * 需連續簽到,斷簽需重新累積簽到次數領過的需跳過,七天為一週期
 * 
 *
condition:
{
    "class": "promo_rule_smash_checkin_bonus",
    "allowed_date": {
        "start": "",
        "end": ""
    },
    "release_date": {
        "start": "",
        "end": ""
    },
    "betConditionTimes" : 5,
    "bonus_settings": {
		"1" : { "bonus": 2},
        "2" : { "bonus": 3},
        "3" : { "bonus": 4},
        "4" : { "bonus": 5},
        "5" : { "bonus": 6},
        "6" : { "bonus": 7},
        "7" : { "bonus": 8}
    },
    "application_day":"2022-08-01",
    "depositListByDates" : [
        {"trans_date":"2022-08-01"},
        {"trans_date":"2022-08-11"},
        {"trans_date":"2022-08-15"},
        {"trans_date":"2022-08-16"},
        {"trans_date":"2022-08-17"},
        {"trans_date":"2022-08-18"},
        {"trans_date":"2022-08-19"},
        {"trans_date":"2022-08-20"},
        {"trans_date":"2022-08-21"},
        {"trans_date":"2022-08-22"},
        {"trans_date":"2022-08-23"},
        {"trans_date":"2022-08-26"},
        {"trans_date":"2022-08-28"},
        {"trans_date":"2022-08-29"}]
}
*
*
*/
class Promo_rule_smash_checkin_bonus extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_smash_checkin_bonus';
	}

	/**
	 * run bonus condition checker
	 * @param  array $description original description in rule
	 * @param  array $extra_info
	 * @param  boolean $dry_run
	 * @return  array ['success'=> success, 'message'=> errorMessageLang]
	 */
	protected function runBonusConditionChecker($description, &$extra_info, $dry_run){
		$success = false;
		$errorMessageLang = null;
		$application_day = $description['application_day'];
		$allowed_date = $description['allowed_date'];
		$bonus_settings = $description['bonus_settings'];
		$fromDate = !empty($allowed_date['start']) ? $allowed_date['start'] : $this->get_date_type(self::DATE_THIS_MONTH_START);
		$toDate = !empty($allowed_date['end']) ? $allowed_date['end'] : $this->get_date_type(self::DATE_THIS_MONTH_END);
		$today = $this->utils->getTodayForMysql();

		if($this->process_mock('today', $today)){
			//use mock data
			$this->appendToDebugLog('use mock today', ['today'=>$today]);
		}

		if (strtotime($today) <= strtotime($application_day)) {
			$success = false;
			$errorMessageLang = lang('notify.78');
			return $result = ['success' => $success, 'message' => $errorMessageLang, 'continue_process_after_script' => FALSE];
		}

		$result = $this->checkCustomizeBonusCondition($bonus_settings, $fromDate, $toDate, $extra_info, $description, $errorMessageLang);

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
		$success = false;
        $errorMessageLang = null;
        $withdrawal_condition_amount = 0;

        $result = $this->releaseBonus($description, $extra_info, $dry_run);

        $times = $description['betConditionTimes'];
        $bonus_amount = $result['bonus_amount'];
        $this->appendToDebugLog('get bonus_amount and times', ['bonus_amount'=>$bonus_amount, 'times'=>$times]);

        if($times > 0){
            $withdrawal_condition_amount = $bonus_amount * $times;
            $success = $withdrawal_condition_amount > 0;
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
		$result = [];
		$application_day = $description['application_day'];
		$allowed_date = $description['allowed_date'];
		$bonus_settings = $description['bonus_settings'];
		$fromDate = !empty($allowed_date['start']) ? $allowed_date['start'] : $this->get_date_type(self::DATE_THIS_MONTH_START);
		$toDate = !empty($allowed_date['end']) ? $allowed_date['end'] : $this->get_date_type(self::DATE_THIS_MONTH_END);
        $today = $this->utils->getTodayForMysql();

        if($this->process_mock('today', $today)){
            //use mock data
            $this->appendToDebugLog('use mock today', ['today'=>$today]);
        }

        if (strtotime($today) <= strtotime($application_day)) {
			$success = false;
			$errorMessageLang =  lang('notify.78');
			return $result = ['success' => $success, 'message' => $errorMessageLang, 'continue_process_after_script' => FALSE];
		}

		$request = $this->checkCustomizeBonusCondition($bonus_settings, $fromDate, $toDate, $extra_info, $description, $errorMessageLang);

        if($request['success']){
        	return $request;
        }
		return $result;
	}

	private function checkCustomizeBonusCondition($bonus_settings, $fromDate, $toDate, &$extra_info, $description, &$errorMessageLang){
        $success = false;
        $errorMessageLang = lang('notify.79');
	    $bonus_amount = 0;
	    $currentVipLevelId = $this->levelId;
	    $promorule = $this->promorule;
	    $promoRuleId = $promorule['promorulesId'];
	    $release_date = $description['release_date'];
	    $amountLimit = isset($description['amountLimit']) ? $description['amountLimit'] : 20 ;
	    $nowDate = $this->get_date_type(self::TO_TYPE_NOW);

	    $countDepositByPlayerId = $this->callHelper('countDepositByPlayerId',[$fromDate, $toDate]);
	    $this->appendToDebugLog('checkCustomizeBonusCondition',['countDepositByPlayerId' => $countDepositByPlayerId, 'fromDate' => $fromDate, 'toDate' => $toDate]);

	    if($countDepositByPlayerId < 1){
	    	$success=false;
			$errorMessageLang =  lang('notify.80');
	    	return $result = ['success' => $success, 'message' => $errorMessageLang, 'continue_process_after_script' => FALSE];
	    }

	    $depositListByDates = $this->callHelper('getPlayerDepositListByDates',[$this->playerId, $fromDate, $toDate, $amountLimit]);
	    $playerBonusAmount = $this->callHelper('getPlayerBonusAmount',[$this->playerId, $promoRuleId, $fromDate, $toDate]);
	    $depositListByDates = isset($description['depositListByDates']) ? $description['depositListByDates'] : $depositListByDates;

	 //    $depositListByDates = array(
		//     ["trans_date" => "2022-08-01"],
		//     // ["trans_date" => "2022-08-02"],
		//     ["trans_date" => "2022-08-03"],
		//     ["trans_date" => "2022-08-04"],
		//     // ["trans_date" => "2022-08-05"],
		//     ["trans_date" => "2022-08-06"],
		//     ["trans_date" => "2022-08-07"],
		//     ["trans_date" => "2022-08-08"],
		//     // ["trans_date" => "2022-08-09"],
		//     // ["trans_date" => "2022-08-10"],
		//     // ["trans_date" => "2022-08-11"],
		//     // ["trans_date" => "2022-08-12"],
		//     // ["trans_date" => "2022-08-13"],
		//     // ["trans_date" => "2022-08-14"],
		//     // ["trans_date" => "2022-08-15"],
		//     // ["trans_date" => "2022-08-16"],
		//     // ["trans_date" => "2022-08-17"],
		//     // ["trans_date" => "2022-08-18"],
		//     // ["trans_date" => "2022-08-19"],
		//     // ["trans_date" => "2022-08-20"],
		//     // ["trans_date" => "2022-08-21"],
		//     // ["trans_date" => "2022-08-22"],
		// );

	    $this->appendToDebugLog('checkCustomizeBonusCondition getPlayerDepositListByDates', ['playerId' => $this->playerId, 'depositListByDates' => $depositListByDates, 'playerBonusAmount' => $playerBonusAmount,'bonus_settings' => $bonus_settings]);

	    if (!empty($bonus_settings)) {
	    	
	    	$last_date = null;
	    	$current_date = null;
	    	$count_constant = 0;
	    	$count_discontinue = 0;
	    	$total_constant_bonus = 0;
	    	$cycles_count = 0;
	    	
	    	foreach ($depositListByDates as $deposit) {
	    		$current_date = $deposit['trans_date'];
	    		$constant = false;
	    		$this->appendToDebugLog('trace params',['last_date' => $last_date, 'count_constant' => $count_constant]);

	    		if (!is_null($last_date)) {
	    			$last_date_time = date('Y-m-d', strtotime($last_date . ' +1 day'));
	    			$count_constant_time = $current_date;

					$this->appendToDebugLog('trace date time',['last_date_time' =>  $last_date_time, 'count_constant_time' => $count_constant_time, 'count_constant' =>  $count_constant, 'count_discontinue' =>  $count_discontinue,]);

	    			if ($last_date_time == $count_constant_time) {
	    				$constant = true;
					}

					if ($constant) {
						if ($count_discontinue == 0) {
							$count_constant += 1;
						}else{
							$count_discontinue += 1;
						}

						if ($count_discontinue > $count_constant) {
							$count_discontinue = 0;
							$count_constant += 1;
						}
					}else{
						$count_discontinue = 1;
					}
				}else{
					$count_constant = 1;
				}

				$last_date = $deposit['trans_date'];

				$this->appendToDebugLog('algorithm result',['last_date' => $last_date, 'count_constant' => $count_constant, 'count_discontinue' => $count_discontinue, 'current_date' => $current_date, 'cycles_count' => $cycles_count]);

				if ($count_constant == 7) {
					$cycles_count += 1;
					$count_constant = 0;
					$count_discontinue = 0;
					$last_date = null;

					$this->appendToDebugLog('is count_constant == 7result',['last_date' => $last_date, 'count_constant' => $count_constant, 'count_discontinue' => $count_discontinue, 'current_date' => $current_date, 'cycles_count' => $cycles_count]);
				}
			}

			for ($i=1; $i <= $count_constant; $i++) {
				$bonusSetting = empty($bonus_settings[$i]) ? null : $bonus_settings[$i];
				$bonus_amount = $bonusSetting['bonus'];
				$total_constant_bonus += $bonus_amount;
			}

			$this->appendToDebugLog('for result ',['count_constant' => $count_constant, 'bonus_amount' => $bonus_amount, 'total_constant_bonus' => $total_constant_bonus]);

			if ($cycles_count > 0) {
				$sum_arr_bonus = array_sum(array_column($bonus_settings, 'bonus'));
				$constant_bonus = $cycles_count * $sum_arr_bonus;
				$bonus_amount = $total_constant_bonus + $constant_bonus;

				$this->appendToDebugLog('when cycles_count > 0 ,',['sum_arr_bonus' => $sum_arr_bonus, 'constant_bonus' => $constant_bonus, 'bonus_amount' => $bonus_amount]);
			}else{
				$bonus_amount = $total_constant_bonus;
			}

			if ($playerBonusAmount > 0) {
				$bonus_amount = $bonus_amount - $playerBonusAmount;
			}

			if ($bonus_amount > 0) {
				$success = true;
				$errorMessageLang = lang('notify.90');
			}else{
				$errorMessageLang = lang('notify.78');
			}

			$this->appendToDebugLog('check release Bonus success', ['success' => $success, 'playerId' => $this->playerId, 'fromDate' => $fromDate, 'toDate' => $toDate,'bonus_amount' => $bonus_amount, 'cycles_count' => $cycles_count, 'total_constant_bonus' => $total_constant_bonus, 'last_date' => $last_date, 'count_constant' => $count_constant, 'count_discontinue' => $count_discontinue, 'current_date' => $current_date, 'playerBonusAmount' => $playerBonusAmount
			]);

		} else {
			$errorMessageLang = 'Not exist Setting';
			$this->appendToDebugLog('Not exist Setting',['bonus_settings' => $bonus_settings]);
		}
		return $result=['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonus_amount];
	}
}
