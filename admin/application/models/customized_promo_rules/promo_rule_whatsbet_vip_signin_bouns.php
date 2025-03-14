<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 *
 * Whatsbet vip signin bouns bouns
 *
 * 
 *
 * OGP-21434
 *
 * vip签到送彩金
 * 活動有效期每週一到日 
 * 非存款優惠,一天申請一次 手動發放
 * 每週簽到次數 ＝ 一週的申請次數
 * 取款條件1倍流水
 * 連續簽到會獲得額外獎金(day 3,5,7)
 *
 * 第 1 天 充值10000卢比 投注量18888卢比 获得70卢比彩金，第 2 天 充值20000卢比 投注量38888卢比 
 * 获得140卢比彩金，以此类推7天全部签到就可获得1960卢比超级签到彩金哦。
 * 如果中间间断一天，连续签到不会被重置，而是获取当周登入第几天的奖励。连续天数越大，获得的超级彩金越高哦！
 * 
condition:
{
    "class": "promo_rule_whatsbet_vip_signin_bouns",
    "allowed_date": {
        "start": "",
        "end": ""
    },
    "count_promo_date": {
        "start": "",
        "end": ""
    },
    "betConditionTimes" : 1,
    "bonus_settings": {
		"1" : { "maxBouns":70, "deposit":10000, "bet":18888, "extraBouns":0},
        "2" : { "maxBouns":140, "deposit":20000, "bet":38888, "extraBouns":0},
        "3" : { "maxBouns":210, "deposit":30000, "bet":58888, "extraBouns":36},
        "4" : { "maxBouns":280, "deposit":40000, "bet":78888, "extraBouns":0},
        "5" : { "maxBouns":350, "deposit":50000, "bet":98888, "extraBouns":66},
        "6" : { "maxBouns":420, "deposit":60000, "bet":128888, "extraBouns":0},
        "7" : { "maxBouns":490, "deposit":70000, "bet":18888, "extraBouns":88}
    }
}

Promo Manager Mock For Class:
{
    "today":"2020-09-10",
    "getLastUpgradeLevelOrCurrentLevel":"4"
}
 *
 *
 *
 */
class Promo_rule_whatsbet_vip_signin_bouns extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'promo_rule_whatsbet_vip_signin_bouns';
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
		$allowed_date = $description['allowed_date'];
		$bonus_settings = $description['bonus_settings'];
		$fromDate = !empty($allowed_date['start']) ? $allowed_date['start'] : $this->get_date_type(self::DATE_TODAY_START);
		$toDate = !empty($allowed_date['end']) ? $allowed_date['end'] : $this->get_date_type(self::DATE_TODAY_END);
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
		$fromDate = !empty($allowed_date['start']) ? $allowed_date['start'] : $this->get_date_type(self::DATE_TODAY_START);
		$toDate = !empty($allowed_date['end']) ? $allowed_date['end'] : $this->get_date_type(self::DATE_TODAY_END);
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
	    $count_promo_date = $description['count_promo_date'];
	    $nowDate = $this->get_date_type(self::TO_TYPE_NOW);

	    $released_promo_date['start'] = $fromDate;
	    $released_promo_date['end'] = $toDate;

	    #check Released Bonus
	    $checkReleasedBonus = $this->callHelper('get_last_released_player_promo',[$promoRuleId,self::DATE_TYPE_CUSTOMIZE,$released_promo_date]);

	    #sum deposit amount and total bets today
	    list($betsAmount, $deposit) = $this->callHelper('getBetsAndDepositByDate', [$fromDate, $toDate]);

	    #Check how many times to apply for this offer (up to five times)
	    $count_promo_date_from = $this->get_date_type(self::DATE_THIS_WEEK_CUSTOM,'monday');
	    $count_promo_date_to = $this->get_date_type(self::DATE_THIS_WEEK_CUSTOM,'sunday');

	    $default_count_promo_date['start'] = $count_promo_date_from;
	    $default_count_promo_date['end'] = $count_promo_date_to;

	    $count_approved_promo = $this->callHelper('count_approved_promo',[$promoRuleId, self::DATE_TYPE_CUSTOMIZE,$default_count_promo_date]);
	    

	    if(!empty($count_promo_date['start']) && !empty($count_promo_date['end'])){
			$count_approved_promo = $this->callHelper('count_approved_promo',[$promoRuleId,self::DATE_TYPE_CUSTOMIZE,$count_promo_date]);
	    }

	    $this->appendToDebugLog('checkCustomizeBounsCondition params',
			['checkReleasedBonus' => $checkReleasedBonus, 'betsAmount' => $betsAmount, 'deposit' => $deposit, 'count_promo_date_from' => $count_promo_date_from, 'count_promo_date_to' => $count_promo_date_to, 'default_count_promo_date' => $default_count_promo_date, 'count_approved_promo' => $count_approved_promo, 'released_promo_date' => $released_promo_date]);

	    if($checkReleasedBonus){
	    	$success=false;
			$errorMessageLang =  lang('notify.83');
	    	return $result=['success'=>$success, 'message'=>$errorMessageLang, 'continue_process_after_script' => FALSE];
	    }

	    if ($count_approved_promo >= 7) {
	    	$success=false;
			$errorMessageLang =  lang('notify.82');
    		return $result = ['success' => $success, 'message' => $errorMessageLang, 'continue_process_after_script' => FALSE];
	    } else {
	    	$apply_times = $count_approved_promo + 1;
	    }
	    
	    
		$betSetting = empty($bonus_settings[$apply_times]) ? null : $bonus_settings[$apply_times];

		$this->appendToDebugLog('check betSetting and apply_times', ['betSetting' => $betSetting, 'apply_times' => $apply_times]);

		if (!empty($betSetting)) {

			$minDeposit = $betSetting['deposit'];
			$minBet = $betSetting['bet'];

			if($deposit>=$minDeposit && $betsAmount>=$minBet){
                $success = true;
				$bonus_amount = $betSetting['maxBouns'];
				if ($apply_times == 3 || $apply_times == 5 || $apply_times == 7) {
					$bonus_amount = $betSetting['maxBouns'] + $betSetting['extraBouns'];
				}
            }else{
                $errorMessageLang = 'Required deposit amount or bet did not met!';
            }
			
			$this->appendToDebugLog('check bets amount release Bonus success', ['success' => $success,'playerId' => $this->playerId, 'fromDate' => $fromDate, 'toDate' => $toDate,'bonus_amount' => $bonus_amount
			]);

		} else {
			$errorMessageLang = 'Not exist Setting';
			$this->appendToDebugLog('Not exist Setting',['betSetting' => $betSetting]);
		}

		return $result=['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonus_amount];
	}
}