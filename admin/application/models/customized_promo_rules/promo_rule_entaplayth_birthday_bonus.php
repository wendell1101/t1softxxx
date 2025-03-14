<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 *
 * OGP-18665
 *
 * 一年一次根據級別發放bouns
 * 發放條件以發起promo當下vip level計算
 * 生日前七天到生日後七天 可申請此優惠
 * 立即發放優惠獎金
 *
condition:
{
    "class": "promo_rule_entaplayth_birthday_bonus",
    "allowed_date": {
        "start": "2020-03-20 00:00:00",
        "end": "2020-03-23 23:59:59"
    },
    "release_date": {
        "start": "2020-03-21 00:00:00",
        "end": "2020-03-21 23:59:59"
    },
    "bonus_settings": {
		"29" : { "releaseBonus": 300},
        "30" : { "releaseBonus": 700},
        "31" : { "releaseBonus": 2100},
        "32" : { "releaseBonus": 5000},
        "33" : { "releaseBonus": 9000},
        "34" : { "releaseBonus": 18000},
        "35" : { "releaseBonus": 36000}
    }
}
*
*
*/
class Promo_rule_entaplayth_birthday_bonus extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_entaplayth_birthday_bonus';
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
		$player_birthdate = $this->callHelper('getPlayerBirthdate', []);

		$this->appendToDebugLog('player_birthdate',['player_birthdate'=>$player_birthdate]);

		if(empty($player_birthdate)){
			$success=false;
			$errorMessageLang = lang('promo_custom.birthdate_not_set_yet');
    		return $result = ['success' => $success, 'message' => $errorMessageLang, 'continue_process_after_script' => FALSE];
		}

		list($startDate, $endDate) = $this->getLast7DayAndNext7Day($player_birthdate);

		if(!$this->checkIsBetweenTime($startDate, $endDate)){
			$success=false;
			$errorMessageLang = lang('Cannot apply for this promo at this time');
			return $result = ['success' => $success, 'message' => $errorMessageLang, 'continue_process_after_script' => FALSE];
		}

		$fromDate = !empty($allowed_date['start']) ? $allowed_date['start'] : $startDate;
		$toDate = !empty($allowed_date['end']) ? $allowed_date['end'] : $endDate;
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
		$success = false;
		$errorMessageLang = null;
		$allowed_date = $description['allowed_date'];
		$bonus_settings = $description['bonus_settings'];
		$player_birthdate = $this->callHelper('getPlayerBirthdate', []);

		$this->appendToDebugLog('player_birthdate',['player_birthdate' => $player_birthdate]);

		#check player birthdate
		if(empty($player_birthdate)){
			$success = false;
			$errorMessageLang = lang('promo_custom.birthdate_not_set_yet');
			return $result=['success' => $success, 'message' => $errorMessageLang, 'continue_process_after_script' => FALSE];
		}

		list($startDate, $endDate) = $this->getLast7DayAndNext7Day($player_birthdate);

		if(!$this->checkIsBetweenTime($startDate, $endDate)){
			$success = false;
			$errorMessageLang = lang('Cannot apply for this promo at this time');
    		return $result=['success' => $success, 'message' => $errorMessageLang, 'continue_process_after_script' => FALSE];
		}

		$fromDate = !empty($allowed_date['start']) ? $allowed_date['start'] : $startDate;
		$toDate = !empty($allowed_date['end']) ? $allowed_date['end'] : $endDate;
		$request = $this->checkCustomizeBounsCondition($bonus_settings, $fromDate, $toDate, $extra_info, $description, $errorMessageLang);

        if($request['success']){
        	return $request;
        }

	}

	private function checkCustomizeBounsCondition($bonus_settings, $fromDate, $toDate, &$extra_info, $description, &$errorMessageLang){
        $success = false;
	    $bonus_amount = 0;
	    $currentVipLevelId = $this->levelId;
	    $promorule = $this->promorule;
	    $promoRuleId = $promorule['promorulesId'];
	    $release_date = $description['release_date'];

	    #check Released Bonus from Last Year Birthdate To Today
	    $checkReleasedBonusLastYearToToday = $this->callHelper('get_last_released_player_promo',[$promoRuleId,self::DATE_TYPE_YEAR]);

	    if(!empty($release_date['start']) && !empty($release_date['end'])){
			$checkReleasedBonusLastYearToToday = $this->callHelper('get_last_released_player_promo',[$promoRuleId,self::DATE_TYPE_CUSTOMIZE,$release_date]);
	    }
	    $this->appendToDebugLog('checkCustomizeBounsCondition get_last_released_player_promo',
	    	['release_date' => $release_date, 'checkReleasedBonusLastYearToToday' => $checkReleasedBonusLastYearToToday]);

	    if($checkReleasedBonusLastYearToToday){
	    	$success=false;
			$errorMessageLang =  lang('notify.83');
	    	return $result = ['success' => $success, 'message' => $errorMessageLang, 'continue_process_after_script' => FALSE];
	    }

		#no need use $deposit
		$betSetting = empty($bonus_settings[$currentVipLevelId]) ? null : $bonus_settings[$currentVipLevelId];
		if(!empty($betSetting)){
			// $turnoverTenTimes = $betSetting['releaseBonus'] * 10;
			$releaseBonus = $betSetting['releaseBonus'];

			$this->appendToDebugLog('check bets amount', [
				'playerId' => $this->playerId, 'fromDate' => $fromDate, 'toDate' => $toDate, 'releaseBonus' => $releaseBonus,
			]);
		}

		if(array_key_exists($currentVipLevelId, $bonus_settings)){
			$success=true;
			$bonus_amount = $releaseBonus;
			$this->appendToDebugLog('release bonus success',['bonus_amount' => $bonus_amount]);
		}else{
			$errorMessageLang = 'Not exist this level in setting';
			$this->appendToDebugLog('Not exist this level in setting');
		}

		return $result = ['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonus_amount];
	}

	public function getLast7DayAndNext7Day($birthdate){
		$today = $this->utils->getTodayForMysql();
		$thisYear = date('Y', strtotime($today));
		$birthdateMon = date('m', strtotime($birthdate));
		$birthdateDay = date('d', strtotime($birthdate));
		$thisYearBirthdate = $thisYear.'-'.$birthdateMon.'-'.$birthdateDay;
		$fromDate = date("Y-m-d", strtotime( " -6 days", strtotime($thisYearBirthdate))).' '.Utils::FIRST_TIME;
		$toDate = date('Y-m-d',strtotime($thisYearBirthdate . '+6 day')).' '.Utils::LAST_TIME;

		$this->appendToDebugLog('getLast7DayAndNext7Day',['thisYear' => $thisYear, 'birthdateMon' => $birthdateMon,'birthdateDay' => $birthdateDay, 'thisYearBirthdate' => $thisYearBirthdate, 'fromDate' => $fromDate, 'toDate' => $toDate]);

		return array($fromDate, $toDate);
	}


	public function checkIsBetweenTime($start,$end){
	    $result = false;
	    $curTime = strtotime($this->get_date_type(self::TO_TYPE_NOW));//当前时分
	    $assignTime1 = strtotime($start);//获得指定分钟时间戳，00:00
	    $assignTime2 = strtotime($end);//获得指定分钟时间戳，01:00

	    if($curTime > $assignTime1 && $curTime < $assignTime2){
	        $result = true;
	    }

	    $this->appendToDebugLog('checkIsBetweenTime',['result'=>$result, 'curTime' => $this->get_date_type(self::TO_TYPE_NOW), 'assignTime1' => $start, 'assignTime2' => $end]);

    	return $result;
	}
}
