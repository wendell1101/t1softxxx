<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 *
 * OGP-20270
 * 回归簽到優惠
 * 從註冊日到上次登入至少需要有一筆存款
 * 距離上次登入到今天需要大於三十天(只在簽到第一天判斷)
 * 檢查第幾次申請此優惠(最多五次)
 * 需連續簽到,斷簽需要再次有30天為登入紀錄才能參加此優惠
 * 一天只能申請一次
 * 
 *
condition:
{
    "class": "promo_rule_kinggaming_signin_bonus",
    "allowed_date": {
        "start": "",
        "end": ""
    },
    "release_date": {
        "start": "",
        "end": ""
    },
    "betConditionTimes" : 8,
    "bonus_settings": {
		"1" : { "maxBouns": 8},
        "2" : { "maxBouns": 8},
        "3" : { "maxBouns": 18},
        "4" : { "maxBouns": 18},
        "5" : { "maxBouns": 28}
    }
}
*
*
*/
class Promo_rule_kinggaming_signin_bonus extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_kinggaming_signin_bonus';
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
		$fromDate = !empty($allowed_date['start']) ? $allowed_date['start'] : $this->get_date_type(self::REGISTER_DATE);
		$toDate = !empty($allowed_date['end']) ? $allowed_date['end'] : $this->get_date_type(self::BEFORE_LAST_LOGIN_TIME);
		$today = $this->utils->getTodayForMysql();

		if($this->process_mock('today', $today)){
			//use mock data
			$this->appendToDebugLog('use mock today', ['today'=>$today]);
		}

		$result = $this->checkCustomizeBounsCondition($bonus_settings, $fromDate, $toDate, $extra_info, $description, $errorMessageLang);

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
        // $usdt_deposit_amount = $result['usdt_deposit_amount'];
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
		$allowed_date = $description['allowed_date'];
		$bonus_settings = $description['bonus_settings'];
		$fromDate = !empty($allowed_date['start']) ? $allowed_date['start'] : $this->get_date_type(self::REGISTER_DATE);
		$toDate = !empty($allowed_date['end']) ? $allowed_date['end'] : $this->get_date_type(self::BEFORE_LAST_LOGIN_TIME);
        $today = $this->utils->getTodayForMysql();

        if($this->process_mock('today', $today)){
            //use mock data
            $this->appendToDebugLog('use mock today', ['today'=>$today]);
        }

		$request = $this->checkCustomizeBounsCondition($bonus_settings, $fromDate, $toDate, $extra_info, $description, $errorMessageLang);

        if($request['success']){
        	return $request;
        }
		return $result;
	}

	private function checkCustomizeBounsCondition($bonus_settings, $fromDate, $toDate, &$extra_info, $description, &$errorMessageLang){
        $success = false;
	    $bonus_amount = 0;
	    $apply_times = 0;
	    $currentVipLevelId = $this->levelId;
	    $promorule = $this->promorule;
	    $promoRuleId = $promorule['promorulesId'];
	    $release_date = $description['release_date'];
	    $nowDate = $this->get_date_type(self::TO_TYPE_NOW);

	    #At least one deposit is required from the registration date to the last login
	    $countDepositByPlayerId = $this->callHelper('countDepositByPlayerId',[$fromDate, $toDate,0]);

	    #getPlayerInfoById
	    $getPlayerInfoById = $this->callHelper('getPlayerInfoById',[$this->playerId]);
	    $beforeLastLoginTime =  $getPlayerInfoById['before_last_login_time'];
	    $notLoginDaybyBeforeTime = (strtotime($nowDate) - strtotime($beforeLastLoginTime))/ (60*60*24);

	    #Check how many times to apply for this offer (up to five times)
	    $count_approved_promo = $this->callHelper('count_approved_promo',[$promoRuleId, self::DATE_TYPE_CUSTOMIZE]);
	    $count_approved_promo_yesterday = $this->callHelper('count_approved_promo',[$promoRuleId, self::DATE_TYPE_YESTERDAY]);
	    

	    if(!empty($release_date['start']) && !empty($release_date['end'])){
			$count_approved_promo_yesterday = $this->callHelper('count_approved_promo',[$promoRuleId,self::DATE_TYPE_CUSTOMIZE,$release_date]);
	    }

	    $this->appendToDebugLog('checkCustomizeBounsCondition get_last_released_player_promo',
	    	['release_date' => $release_date, 'countDepositByPlayerId' => $countDepositByPlayerId, 'getPlayerInfoById' => $getPlayerInfoById, 'beforeLastLoginTime' => $beforeLastLoginTime, 'notLoginDaybyBeforeTime' => $notLoginDaybyBeforeTime, 'count_approved_promo' => $count_approved_promo, 'count_approved_promo_yesterday' => $count_approved_promo_yesterday]);

	    if($countDepositByPlayerId < 1){
	    	$success=false;
			$errorMessageLang =  lang('notify.80');
	    	return $result = ['success' => $success, 'message' => $errorMessageLang, 'continue_process_after_script' => FALSE];
	    }

	    if($count_approved_promo == 0){
	    	if ($notLoginDaybyBeforeTime <= 30) {
	    		$success=false;
				$errorMessageLang =  lang('promo_rule.common.error');
	    		return $result = ['success' => $success, 'message' => $errorMessageLang, 'continue_process_after_script' => FALSE];
	    	}else{
	    		$apply_times = $count_approved_promo + 1;
	    	}
	    }else{
	    	if ($count_approved_promo >= 5) {
	    		$success=false;
				$errorMessageLang =  lang('notify.82');
	    		return $result = ['success' => $success, 'message' => $errorMessageLang, 'continue_process_after_script' => FALSE];
	    	} else {
	    		if ($count_approved_promo_yesterday < 1) {
		    		if ($notLoginDaybyBeforeTime <= 30) {
			    		$success=false;
						$errorMessageLang =  lang('promo_rule.common.error');
			    		return $result = ['success' => $success, 'message' => $errorMessageLang, 'continue_process_after_script' => FALSE];
			    	}else{
			    		$apply_times = $count_approved_promo + 1;
			    	}
		    	}else{
		    		$apply_times = $count_approved_promo + 1;
		    	}
	    	}
	    }
	    
		$betSetting = empty($bonus_settings[$apply_times]) ? null : $bonus_settings[$apply_times];

		$this->appendToDebugLog('check betSetting and apply_times', ['betSetting' => $betSetting, 'apply_times' => $apply_times]);

		if (!empty($betSetting)) {
			$success = true;
			$bonus_amount = $betSetting['maxBouns'];

			$this->appendToDebugLog('check bets amount release Bonus success', ['success' => $success,'playerId' => $this->playerId, 'fromDate' => $fromDate, 'toDate' => $toDate,'bonus_amount' => $bonus_amount
			]);

		} else {
			$errorMessageLang = 'Not exist Setting';
			$this->appendToDebugLog('Not exist Setting',['betSetting' => $betSetting]);
		}

		return $result=['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonus_amount];
	}
}
