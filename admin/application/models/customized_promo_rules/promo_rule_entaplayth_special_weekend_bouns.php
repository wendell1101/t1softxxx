<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 *
 * OGP-19149
 *
 * 每週五六日,每日只能申請一次
 * 須將存款轉入指定的子錢包 金額需要大於當日累積存款,完成轉取款條件才能轉出子錢包(需設定轉帳條件)
 * 拆分成三個優惠規則設定
 * 以申請累積存款計算(當日)
 * withdrawal condition = (deposit amount + bonus) * turnove
 * transfer condition = (deposit amount + bonus) * turnove
 *
condition:
{
    "class": "promo_rule_entaplayth_special_weekend_bouns",
    "allowed_date": {
        "start": "",
        "end": ""
    },
    "release_date": {
        "start": "",
        "end": ""
    },
    "special_date_1":"monday",
    "special_date_2":"wednesday",
    "special_date_3":"thursday",
    //for Sbobet or BTi
    "bonus_settings": [
        {"min_deposit": 1000, "max_deposit": 9999999999999, "bonus": 500, "turnover": 5}
    ],
    //for PP Live Casino or OG+ Live Casino
    "bonus_settings": [
		{"min_deposit": 1000, "max_deposit": 2999, "bonus": 300, "turnover": 10},
		{"min_deposit": 3000, "max_deposit": 4999, "bonus": 1500, "turnover": 12},
		{"min_deposit": 5000, "max_deposit": PHP_INT_MAX, "bonus": 3000, "turnover": 15}
    ],
	for PlaynGo Slot or Hydako Slot
    "bonus_settings": [
		{"min_deposit": 1000, "max_deposit": 2999, "bonus": 300, "turnover": 5},
		{"min_deposit": 3000, "max_deposit": 4999, "bonus": 1500, "turnover": 8},
		{"min_deposit": 5000, "max_deposit": PHP_INT_MAX, "bonus": 3000, "turnover": 10}
    ]
}

*
*
*/
class Promo_rule_entaplayth_special_weekend_bouns extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_entaplayth_special_weekend_bouns';
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
		$allowed_date = $description['allowed_date'];
		$bonus_settings = $description['bonus_settings'];
		$special_date_fri = $this->get_date_type(self::DATE_THIS_WEEK_CUSTOM, $description['special_date_1']);
	    $special_date_sat = $this->get_date_type(self::DATE_THIS_WEEK_CUSTOM, $description['special_date_2']);
	    $special_date_sun = $this->get_date_type(self::DATE_THIS_WEEK_CUSTOM, $description['special_date_3']);
	    $today_start      = $this->get_date_type(self::DATE_TODAY_START);
	    $now_date 		  = $this->get_date_type(self::TO_TYPE_NOW);

	    $this->appendToDebugLog('runBonusConditionChecker date params start', ['special_date_fri' => $special_date_fri, 'special_date_sat' => $special_date_sat, 'special_date_sun' => $special_date_sun, 'today_start'=>$today_start, 'now_date'=>$now_date, 'description'=>$description]);

	    if ($today_start == $special_date_fri || $today_start == $special_date_sat || $today_start == $special_date_sun) {
	    	switch ($today_start) {
		    	case $special_date_fri:
		    		$description['special_date']['star'] = $special_date_fri;
		    		break;
		    	case $special_date_sat:
		    		$description['special_date']['star'] = $special_date_sat;
		    		break;
		    	case $special_date_sun:
		    		$description['special_date']['star'] = $special_date_sun;
		    		break;
		    }
	    }else{
	    	$success = false;
			$errorMessageLang =  lang('notify.78');
	    	return $result = ['success' => $success, 'message' => $errorMessageLang, 'continue_process_after_script' => FALSE];
	    }

	    $description['special_date']['end'] = $now_date;

		$fromDate = !empty($allowed_date['start']) ? $allowed_date['start'] : $description['special_date']['star'];
		$toDate = !empty($allowed_date['end']) ? $allowed_date['end'] : $now_date;
		$today = $this->utils->getTodayForMysql();

		$this->appendToDebugLog('runBonusConditionChecker date params end', ['description' => $description, 'fromDate' => $fromDate, 'toDate' => $toDate]);

		if($this->process_mock('today', $today)){
			//use mock data
			$this->appendToDebugLog('use mock today', ['today'=>$today]);
		}

		$result = $this->checkCustomizeBounsCondition($bonus_settings, $fromDate, $toDate, $extra_info, $description, $errorMessageLang);

		if(array_key_exists('bonus_amount',$result)){
			unset($result['bonus_amount']);
		}

		if(array_key_exists('deposit_amount',$result)){
			unset($result['deposit_amount']);
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

        $times = $result['times'];
        $bonus_amount = $result['bonus_amount'];
        $deposit_amount = $result['deposit_amount'];
        $this->appendToDebugLog('get bonus_amount and deposit_amount and times', ['bonus_amount'=>$bonus_amount, 'deposit_amount'=>$deposit_amount, 'times'=>$times]);

        if($times > 0){
            $withdrawal_condition_amount = ($bonus_amount + $deposit_amount) * $times;
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
        $success = false;
        $errorMessageLang = null;
        $transfer_condition_amount = 0;

        $result = $this->releaseBonus($description, $extra_info, $dry_run);

        $times = $result['times'];
        $bonus_amount = $result['bonus_amount'];
        $deposit_amount = $result['deposit_amount'];
        $this->appendToDebugLog('get bonus_amount and deposit_amount and times', ['bonus_amount'=>$bonus_amount, 'deposit_amount'=>$deposit_amount, 'times'=>$times]);

        if($times > 0){
        	$transfer_condition_amount = ($bonus_amount + $deposit_amount) * $times;
        	$success = $transfer_condition_amount > 0;
        }else{
            $errorMessageLang = 'Lost bet_condition_times in settings';
        }

        $result=['success' => $success, 'message' => $errorMessageLang, 'transfer_condition_amount' => round($transfer_condition_amount, 2)];
        return $result;
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
		$allowed_date = $description['allowed_date'];
		$bonus_settings = $description['bonus_settings'];
		$special_date_fri = $this->get_date_type(self::DATE_THIS_WEEK_CUSTOM, $description['special_date_1']);
	    $special_date_sat = $this->get_date_type(self::DATE_THIS_WEEK_CUSTOM, $description['special_date_2']);
	    $special_date_sun = $this->get_date_type(self::DATE_THIS_WEEK_CUSTOM, $description['special_date_3']);
	    $today_start      = $this->get_date_type(self::DATE_TODAY_START);
	    $now_date 		  = $this->get_date_type(self::TO_TYPE_NOW);

	    if ($today_start == $special_date_fri || $today_start == $special_date_sat || $today_start == $special_date_sun) {
	    	switch ($today_start) {
		    	case $special_date_fri:
		    		$description['special_date']['star'] = $special_date_fri;
		    		break;
		    	case $special_date_sat:
		    		$description['special_date']['star'] = $special_date_sat;
		    		break;
		    	case $special_date_sun:
		    		$description['special_date']['star'] = $special_date_sun;
		    		break;
		    }
	    }else{
	    	$success = false;
			$errorMessageLang =  lang('notify.78');
	    	return $result = ['success' => $success, 'message' => $errorMessageLang, 'continue_process_after_script' => FALSE];
	    }

	    $description['special_date']['end'] = $now_date;
	    
		$fromDate = !empty($allowed_date['start']) ? $allowed_date['start'] : $description['special_date']['star'];
		$toDate = !empty($allowed_date['end']) ? $allowed_date['end'] : $now_date;
        $today = $this->utils->getTodayForMysql();

        if($this->process_mock('today', $today)){
            //use mock data
            $this->appendToDebugLog('use mock today', ['today'=>$today]);
        }

		$request = $this->checkCustomizeBounsCondition($bonus_settings, $fromDate, $toDate, $extra_info, $description, $errorMessageLang);

        if($request['success']){
        	return $request;
        }
		$result =['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonus_amount];
		return $result;
	}

	private function checkCustomizeBounsCondition($bonus_settings, $fromDate, $toDate, &$extra_info, $description, &$errorMessageLang){

        $success = false;
	    $bonus_amount = 0;
	    $maxBetAmount = 0;
	    $deposit_amount = 0;
	    $transfer_amount = 0;
	    $times = 0;
	    $currentVipLevelId = $this->levelId;
	    $promoRuleId = $this->promorule['promorulesId'];
	    $release_date = $description['release_date'];
	    $special_date = $description['special_date'];

	    #check Released Bonus from today start to nowx
	    $checkReleasedBonusToday = $this->callHelper('get_last_released_player_promo',[$promoRuleId,self::DATE_TYPE_CUSTOMIZE], $special_date);

	    if(!empty($release_date['start']) && !empty($release_date['end'])){
			$checkReleasedBonusToday = $this->callHelper('get_last_released_player_promo',[$promoRuleId,self::DATE_TYPE_CUSTOMIZE,$release_date]);
	    }

	    $total_deposit = $this->callHelper('sum_deposit_amount', [$fromDate, $toDate, 0]);
	    $getLastTransfer = $this->callHelper('getLastTransfer', [$fromDate, $toDate]);

	    $this->appendToDebugLog('checkCustomizeBounsCondition check params detail', ['release_date' => $release_date, 'special_date' => $special_date, 'checkReleasedBonusToday' => $checkReleasedBonusToday, 'total_deposit' => $total_deposit, 'promoRuleId' => $promoRuleId, 'getLastTransfer' => $getLastTransfer]);

	    if($checkReleasedBonusToday){
	    	$success=false;
			$errorMessageLang =  lang('notify.83');
	    	return $result = ['success' => $success, 'message' => $errorMessageLang, 'continue_process_after_script' => FALSE];
	    }else{
			if($total_deposit < 1000){#total deposit need to >= 1000
	    		$success=false;
				$errorMessageLang = lang('promo_rule.common.error');
		    	return $result=['success' => $success, 'message' => $errorMessageLang, 'continue_process_after_script' => FALSE];
	    	}else{
	    		if ($getLastTransfer['amount'] >= $total_deposit) {
	    				$deposit_amount = $total_deposit;
	    				$transfer_amount = $getLastTransfer['amount'];
	    		} else {
	    			$success=false;
					$errorMessageLang = lang('Please deposit to target sub-wallet first');
		    		return $result=['success' => $success, 'message' => $errorMessageLang, 'continue_process_after_script' => FALSE];
	    		}
	    	}
	    }
			
		if (!empty($bonus_settings)) {
			if(is_array($bonus_settings)){
                foreach ($bonus_settings as $list) {
                    if($deposit_amount >= $list['min_deposit'] && $deposit_amount <= $list['max_deposit']){
                        $success = true;
                        $bonus_amount = $list['bonus'];
                        $times = $list['turnover'];
                    } else {
                        continue;
                    }
                }
            }

			$this->appendToDebugLog('check bets amount release Bonus success', ['success' => $success,'playerId' => $this->playerId, 'fromDate' => $fromDate, 'toDate' => $toDate, 'bonus_settings' => $bonus_settings,'bonus_amount' => $bonus_amount, 'deposit_amount' => $deposit_amount, 'times' => $times
			, 'transfer_amount' => $transfer_amount]);
			
		} else {
			$errorMessageLang = 'Not exist bet Setting';
			$this->appendToDebugLog('Not exist bet Setting',['bonus_settings' => $bonus_settings]);
		}

		return $result=['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonus_amount, 'deposit_amount' => $deposit_amount, 'times' => $times];
	}
}
