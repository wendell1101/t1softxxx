<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 *
 * OGP-20271
 * 回歸首存優惠
 * 從註冊日到上次登入至少需要有一筆存款
 * 回歸後第一筆有效存款
 * 距離上次登入到今天需要大於三十天(只在簽到第一天判斷)
 * 只能申請一次
 * 
 *
condition:
{
    "class": "promo_rule_kinggaming_regress_first_deposit_bonus",
    "allowed_date": {
        "start": "",
        "end": ""
    },
    "release_date": {
        "start": "",
        "end": ""
    },
    "betConditionTimes" : 8,
    "bonus_settings": [
		{"min_deposit": 888, "max_deposit": 3888, "bonus": 18},
		{"min_deposit": 3888, "max_deposit": 8888, "bonus": 88},
		{"min_deposit": 8888, "max_deposit": 11888, "bonus": 188},
		{"min_deposit": 11888, "max_deposit": 38888, "bonus": 288},
		{"min_deposit": 38888, "max_deposit": 58888, "bonus": 388},
		{"min_deposit": 58888, "max_deposit": 88888, "bonus": 588},
		{"min_deposit": 88888, "max_deposit": 9999999999999999, "bonus": 888}
    ]
}
*
*
*/
class Promo_rule_kinggaming_regress_first_deposit_bonus extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_kinggaming_regress_first_deposit_bonus';
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

        $times = $description['betConditionTimes'];
        $bonus_amount = $result['bonus_amount'];
        $deposit_amount = $result['deposit_amount'];
        $this->appendToDebugLog('get bonus_amount deposit_amount and times', ['bonus_amount'=>$bonus_amount, 'times'=>$times, 'deposit_amount'=>$deposit_amount]);

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

        return $request;
	}

	private function checkCustomizeBounsCondition($bonus_settings, $fromDate, $toDate, &$extra_info, $description, &$errorMessageLang){
        $success = false;
	    $bonus_amount = 0;
	    $deposit_amount = 0;
	    $currentVipLevelId = $this->levelId;
	    $promorule = $this->promorule;
	    $promoRuleId = $promorule['promorulesId'];
	    $release_date = $description['release_date'];
	    $today = $this->get_date_type(self::DATE_TODAY_START);
	    $nowDate = $this->get_date_type(self::TO_TYPE_NOW);

	    #At least one deposit is required from the registration date to the last login
	    $countDepositByPlayerId = $this->callHelper('countDepositByPlayerId',[$fromDate, $toDate,0]);

	    #check Released Bonus
	    $checkReleasedBonus = $this->callHelper('get_last_released_player_promo',[$promoRuleId,self::DATE_TYPE_CUSTOMIZE]);

	    #getPlayerInfoById
	    $getPlayerInfoById = $this->callHelper('getPlayerInfoById',[$this->playerId]);
	    $lastLoginTime =  $getPlayerInfoById['before_last_login_time'];
	    $notLoginDay = (strtotime($nowDate) - strtotime($lastLoginTime))/ (60*60*24);

	    #get first deposit from regress date to now
	    #$this->getAnyDepositByDate($start, $end, 'first', null, null, $check_transfer, $orderBy);
	    $getFirstDepositByDate = $this->callHelper('getAnyDepositByDate',[$today, $nowDate, 'first', null, null]);
	    $firstDepositAmount = intval($getFirstDepositByDate['amount']);


	    if(!empty($release_date['start']) && !empty($release_date['end'])){
			$checkReleasedBonus = $this->callHelper('get_last_released_player_promo',[$promoRuleId,self::DATE_TYPE_CUSTOMIZE,$release_date]);
	    }

	    $this->appendToDebugLog('checkCustomizeBounsCondition get_last_released_player_promo',
	    	['release_date' => $release_date, 'countDepositByPlayerId' => $countDepositByPlayerId, 'getPlayerInfoById' => $getPlayerInfoById, 'lastLoginTime' => $lastLoginTime, 'notLoginDay' => $notLoginDay, 'getFirstDepositByDate' => $getFirstDepositByDate, 'firstDepositAmount' => $firstDepositAmount, 'checkReleasedBonus' => $checkReleasedBonus]);

	    if($countDepositByPlayerId < 1){
	    	$success=false;
			$errorMessageLang =  lang('notify.80');
	    	return $result = ['success' => $success, 'message' => $errorMessageLang, 'continue_process_after_script' => FALSE];
	    }

	    if($checkReleasedBonus){
	    	$success=false;
			$errorMessageLang =  lang('notify.83');
	    	return $result = ['success' => $success, 'message' => $errorMessageLang, 'continue_process_after_script' => FALSE];
	    }else{
	    	if ($notLoginDay <= 30) {
	    		$success=false;
				$errorMessageLang =  lang('promo_rule.common.error');
	    		return $result = ['success' => $success, 'message' => $errorMessageLang, 'continue_process_after_script' => FALSE];
	    	}else{
	    		if ($firstDepositAmount >= 888) {
	    			$deposit_amount = $firstDepositAmount;
	    		} else {
	    			$success=false;
					$errorMessageLang =  lang('notify.79');
	    			return $result = ['success' => $success, 'message' => $errorMessageLang, 'continue_process_after_script' => FALSE];
	    		}
	    	}
	    }

	    if (!empty($bonus_settings)) {
			if(is_array($bonus_settings)){
                foreach ($bonus_settings as $list) {
                    if($deposit_amount >= $list['min_deposit'] && $deposit_amount < $list['max_deposit']){
                        $success = true;
                        $bonus_amount = $list['bonus'];
                    } else {
                        continue;
                    }
                }
            }

			$this->appendToDebugLog('check bets amount release Bonus success', ['success' => $success,'playerId' => $this->playerId, 'fromDate' => $fromDate, 'toDate' => $toDate, 'bonus_settings' => $bonus_settings,'bonus_amount' => $bonus_amount, 'deposit_amount' => $deposit_amount]);
			
		} else {
			$errorMessageLang = 'Not exist bet Setting';
			$this->appendToDebugLog('Not exist bet Setting',['bonus_settings' => $bonus_settings]);
		}

		return $result=['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonus_amount, 'deposit_amount' => $deposit_amount];
	}
}
