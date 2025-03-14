<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 *
 * OGP-16679
 *
 * 玩家一個月只能申請一次(本月)
 * 玩家上個月跟這個月當下要申請時的vip級別要相同
 * 玩家上個月投注額須符合下方vip level bonus_settings區間
 *
condition:
{
    "class": "promo_rule_dj002_bets_amount_monthly",
    "bonus_settings": {
		"29" : {"minBetAmount": 3000, "maxBetAmount": 11999, "levelUpBonus": 8},
        "30" : {"minBetAmount": 12000, "maxBetAmount": 59999, "levelUpBonus": 18},
        "31" : {"minBetAmount": 60000, "maxBetAmount": 299999, "levelUpBonus": 38},
        "32" : {"minBetAmount": 300000, "maxBetAmount": 1199999, "levelUpBonus": 88},
        "33" : {"minBetAmount": 1200000, "maxBetAmount": 2999999, "levelUpBonus": 388},
        "34" : {"minBetAmount": 3000000, "maxBetAmount": 7199999, "levelUpBonus": 688},
        "35" : {"minBetAmount": 7200000, "maxBetAmount": 17999999, "levelUpBonus": 1088},
        "36" : {"minBetAmount": 18000000, "maxBetAmount": 59999999, "levelUpBonus": 3888},
        "37" : {"minBetAmount": 60000000, "maxBetAmount": 179000000, "levelUpBonus": 5888},
        "38" : {"minBetAmount": 180000000, "maxBetAmount": 9999999999, "levelUpBonus": 18888}
    }
}

mock:
{
    "today": "2018-09-20",
    "getBetsAndDepositByDate": [1000000,100000]
}

*
*
*/
class Promo_rule_dj002_bets_amount_monthly extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_dj002_bets_amount_monthly';
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
		$fromDate = !empty($allowed_date['start']) ? $allowed_date['start'] : $this->get_date_type(self::DATE_LAST_MONTH_START);
		$toDate = !empty($allowed_date['end']) ? $allowed_date['end'] : $this->get_date_type(self::DATE_LAST_MONTH_END);
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
		$fromDate = !empty($allowed_date['start']) ? $allowed_date['start'] : $this->get_date_type(self::DATE_LAST_MONTH_START);
		$toDate = !empty($allowed_date['end']) ? $allowed_date['end'] : $this->get_date_type(self::DATE_LAST_MONTH_END);
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
	    $availableLevelId = null;
	    $bonus_amount=0;
	    $currentVipLevelId = $this->levelId;
	    $promorule = $this->promorule;
	    $promoRuleId = $promorule['promorulesId'];
	    $release_date = $description['release_date'];

	    if(!empty($release_date['start']) && !empty($release_date['end'])){
			$checkReleasedBonusThisMonth = $this->callHelper('get_last_released_player_promo',[$promoRuleId,self::DATE_TYPE_CUSTOMIZE,$release_date]);
	    }
	    $checkReleasedBonusThisMonth = $this->callHelper('get_last_released_player_promo',[$promoRuleId,self::DATE_TYPE_THIS_MONTH]);
	    $this->appendToDebugLog('get_last_released_player_promo', ['release_date'=>$release_date, 'checkReleasedBonusThisMonth'=>$checkReleasedBonusThisMonth]);

		$newLevelId = $this->callHelper('getLastUpgradeLevelOrCurrentLevel',[$toDate]);
		$this->appendToDebugLog('search level id getLastUpgradeLevelOrCurrentLevel', ['toDate'=>$toDate, 'newLevelId'=>$newLevelId]);

	    if($checkReleasedBonusThisMonth){
	    	$success=false;
				$errorMessageLang =  lang('notify.83');
	    	return $result=['success'=>$success, 'message'=>$errorMessageLang, 'continue_process_after_script' => FALSE];
	    }

	    list($betsAmount, $deposit) = $this->callHelper('getBetsAndDepositByDate', [$fromDate, $toDate]);
		#no need use $deposit
		$betSetting = empty($bonus_settings[$currentVipLevelId]) ? null : $bonus_settings[$currentVipLevelId];
		if(!empty($betSetting)){
			$minBetAmount = $betSetting['minBetAmount'];
			$maxBetAmount = $betSetting['maxBetAmount'];
			$levelUpBonus = $betSetting['levelUpBonus'];

			$this->appendToDebugLog('check bets amount', [
				'playerId' => $this->playerId, 'fromDate' => $fromDate, 'toDate' => $toDate,
				'betsAmount' => $betsAmount, 'minBetAmount' => $minBetAmount, 'maxBetAmount' => $maxBetAmount, 'levelUpBonus' => $levelUpBonus,
			]);
		}

		if(!empty($newLevelId)){
			if($newLevelId != $currentVipLevelId){
		    	$errorMessageLang =  lang('VIP level is not the same before and after the last auto upgrade');
		    	return $result=['success'=>$success, 'message'=>$errorMessageLang, 'continue_process_after_script' => FALSE];
		    }else{
		    	if(array_key_exists($currentVipLevelId, $bonus_settings)){
					if($betsAmount >= $minBetAmount && $betsAmount <= $maxBetAmount){
						$success=true;
						$bonus_amount = $levelUpBonus;
						$this->appendToDebugLog('release bonus success',['bonus_amount'=>$bonus_amount]);
					}else{
				    	$errorMessageLang =  lang('Bet amount is not within the rule');
				    	$this->appendToDebugLog('Bet amount is not within the rule.');
					}
				}else{
					$errorMessageLang = 'not exist this level in setting';
					$this->appendToDebugLog('not exist this level in setting');
				}
		    }
		}else{
			if(array_key_exists($currentVipLevelId, $bonus_settings)){
				if($betsAmount >= $minBetAmount && $betsAmount <= $maxBetAmount){
					$success=true;
					$bonus_amount = $levelUpBonus;
					$this->appendToDebugLog('release bonus success',['bonus_amount'=>$bonus_amount]);
				}else{
			    	$errorMessageLang =  lang('Bet amount is not within the rule');
			    	$this->appendToDebugLog('Bet amount is not within the rule.');
				}
			}else{
				$errorMessageLang = 'not exist this level in setting';
				$this->appendToDebugLog('not exist this level in setting');
			}
		}

		return $result=['success'=>$success, 'message'=>$errorMessageLang, 'bonus_amount' => $bonus_amount];
	}
}
