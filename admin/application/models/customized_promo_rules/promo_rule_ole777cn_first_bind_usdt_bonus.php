<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 *
 * OGP-20027
 *
 * 会员在首次绑定USDT账户后自动发放。
 * 会员只能领取1次。
 *
condition:
{
    "class": "promo_rule_ole777cn_first_bind_usdt_bonus",
    "allowed_date": {
        "start": "",
        "end": ""
    },
    "release_date": {
        "start": "",
        "end": ""
    },
    "bonus_settings": {
		"1" : { "releaseBonus": 7, "betConditionTimes": 3},
        "39" : { "releaseBonus": 17, "betConditionTimes": 1},
        "41" : { "releaseBonus": 27, "betConditionTimes": 1},
        "32" : { "releaseBonus": 37, "betConditionTimes": 1},
        "33" : { "releaseBonus": 57, "betConditionTimes": 1},
        "34" : { "releaseBonus": 67, "betConditionTimes": 1},
        "35" : { "releaseBonus": 77, "betConditionTimes": 1},
        "36" : { "releaseBonus": 177, "betConditionTimes": 1},
        "37" : { "releaseBonus": 277, "betConditionTimes": 1},
        "38" : { "releaseBonus": 377, "betConditionTimes": 1},
        "40" : { "releaseBonus": 777, "betConditionTimes": 1}
    }
}
*
*
*/
class Promo_rule_ole777cn_first_bind_usdt_bonus extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_ole777cn_first_bind_usdt_bonus';
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
		$fromDate = !empty($allowed_date['start']) ? $allowed_date['start'] : $this->get_date_type(self::DATE_THIS_MONTH_START);
		$toDate = !empty($allowed_date['end']) ? $allowed_date['end'] : $this->get_date_type(self::TO_TYPE_NOW);
		$today = $this->utils->getTodayForMysql();

		if($this->process_mock('today', $today)){
			//use mock data
			$this->appendToDebugLog('use mock today', ['today'=>$today]);
		}

		$result = $this->checkCustomizeBounsCondition($bonus_settings, $fromDate, $toDate, $extra_info, $description, $errorMessageLang);

		if(array_key_exists('bonus_amount',$result)){
			unset($result['bonus_amount']);
		}

		if(array_key_exists('bet_condition_times',$result)){
			unset($result['bet_condition_times']);
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

        $bonus_amount = $result['bonus_amount'];
        $times = $result['bet_condition_times'];
        $this->appendToDebugLog('get bonus_amount and bet_condition_times', ['bonus_amount'=>$bonus_amount, 'times'=>$times]);

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
		$fromDate = !empty($allowed_date['start']) ? $allowed_date['start'] : $this->get_date_type(self::DATE_THIS_MONTH_START);
		$toDate = !empty($allowed_date['end']) ? $allowed_date['end'] : $this->get_date_type(self::TO_TYPE_NOW);
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
	    $currentVipLevelId = $this->levelId;
	    $promorule = $this->promorule;
	    $promoRuleId = $promorule['promorulesId'];
	    $release_date = $description['release_date'];

	    #check Released Bonus
	    $checkReleasedBonus = $this->callHelper('get_last_released_player_promo',[$promoRuleId,self::DATE_TYPE_CUSTOMIZE]);
		#check exist usdt account
	    $checkExistUsdtAccount = $this->callHelper('getCryptoAccountByPlayerId',[$this->playerId,'deposit','USDT']);

	    if(!empty($release_date['start']) && !empty($release_date['end'])){
			$checkReleasedBonus = $this->callHelper('get_last_released_player_promo',[$promoRuleId,self::DATE_TYPE_CUSTOMIZE,$release_date]);
	    }
	    $this->appendToDebugLog('checkCustomizeBounsCondition get_last_released_player_promo',
	    	['release_date' => $release_date, 'checkReleasedBonus' => $checkReleasedBonus]);

	    if($checkReleasedBonus){
	    	$success=false;
			$errorMessageLang =  lang('notify.83');
	    	return $result = ['success' => $success, 'message' => $errorMessageLang, 'continue_process_after_script' => FALSE];
	    }

	    if(array_key_exists('not_exist', $checkExistUsdtAccount) && is_array($checkExistUsdtAccount)){
	    	$success=false;
			$errorMessageLang =  lang('Please bind a crypto wallet before apply this promo');
	    	return $result = ['success' => $success, 'message' => $errorMessageLang, 'continue_process_after_script' => FALSE];
	    }

		$betSetting = empty($bonus_settings[$currentVipLevelId]) ? null : $bonus_settings[$currentVipLevelId];
		$bet_condition_times = 0;
		if (!empty($betSetting)) {
			if(array_key_exists($currentVipLevelId, $bonus_settings)){
				$success = true;
				$bonus_amount = $betSetting['releaseBonus'];
				$bet_condition_times = $betSetting['betConditionTimes'];

				$this->appendToDebugLog('check bets amount release Bonus success', ['success' => $success,'playerId' => $this->playerId, 'fromDate' => $fromDate, 'toDate' => $toDate,'bonus_amount' => $bonus_amount, 'bet_condition_times' => $bet_condition_times
				]);
			}else{
				$errorMessageLang = 'Not exist this level in setting';
				$this->appendToDebugLog('Not exist this level in setting',['currentVipLevelId' => $currentVipLevelId]);
			}
		} else {
			$errorMessageLang = 'Not exist bet Setting';
			$this->appendToDebugLog('Not exist bet Setting',['betSetting' => $betSetting]);
		}

		return $result=['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonus_amount, 'bet_condition_times' => $bet_condition_times];
	}
}
