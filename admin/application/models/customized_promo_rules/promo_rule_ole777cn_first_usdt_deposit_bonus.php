<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 *
 * OGP-20028
 *
 * 会员在首次使用USDT账户存款后自动发放。
 * 仅限首次，不限制金额。
 *
condition:
{
    "class": "promo_rule_ole777cn_first_usdt_deposit_bonus",
    "allowed_date": {
        "start": "",
        "end": ""
    },
    "release_date": {
        "start": "",
        "end": ""
    },
    "betConditionTimes" : 7,
    "bonus_settings": {
		"1" : { "maxBouns": 50, "percentage": 5},
        "39" : { "maxBouns": 100, "percentage": 5},
        "41" : { "maxBouns": 200, "percentage": 5},
        "32" : { "maxBouns": 300, "percentage": 5},
        "33" : { "maxBouns": 500, "percentage": 5},
        "34" : { "maxBouns": 750, "percentage": 5},
        "35" : { "maxBouns": 1200, "percentage": 5},
        "36" : { "maxBouns": 1800, "percentage": 5},
        "37" : { "maxBouns": 2600, "percentage": 5},
        "38" : { "maxBouns": 3600, "percentage": 5},
        "40" : { "maxBouns": 6000, "percentage": 5}
    }
}
*
*
*/
class Promo_rule_ole777cn_first_usdt_deposit_bonus extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_ole777cn_first_usdt_deposit_bonus';
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

        $times = $description['betConditionTimes'];
        $bonus_amount = $result['bonus_amount'];
        $usdt_deposit_amount = $result['usdt_deposit_amount'];
        $this->appendToDebugLog('get bonus_amount and usdt_deposit_amount and times', ['bonus_amount'=>$bonus_amount, 'usdt_deposit_amount'=>$usdt_deposit_amount, 'times'=>$times]);

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
	    $usdt_deposit_amount = 0;
	    $currentVipLevelId = $this->levelId;
	    $promorule = $this->promorule;
	    $promoRuleId = $promorule['promorulesId'];
	    $release_date = $description['release_date'];

	    #check Released Bonus
	    $checkReleasedBonus = $this->callHelper('get_last_released_player_promo',[$promoRuleId,self::DATE_TYPE_CUSTOMIZE]);
		#check exist usdt account
	    $checkExistUsdtAccount = $this->callHelper('getCryptoAccountByPlayerId',[$this->playerId,'deposit','USDT']);

	    #get Usdt Deposit List
	    $getUsdtDepositListBy = $this->callHelper('getUsdtDepositListBy',[$this->playerId, 1]);

	    if(!empty($release_date['start']) && !empty($release_date['end'])){
			$checkReleasedBonus = $this->callHelper('get_last_released_player_promo',[$promoRuleId,self::DATE_TYPE_CUSTOMIZE,$release_date]);
	    }

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

	    if(empty($getUsdtDepositListBy)){
	    	$success=false;
			$errorMessageLang =  lang('Need to use USDT account to deposit');
	    	return $result = ['success' => $success, 'message' => $errorMessageLang, 'continue_process_after_script' => FALSE];
	    }

	    $this->appendToDebugLog('checkCustomizeBounsCondition get_last_released_player_promo',
	    	['release_date' => $release_date, 'checkReleasedBonus' => $checkReleasedBonus, 'checkExistUsdtAccount' => $checkExistUsdtAccount, 'getUsdtDepositListBy' => $getUsdtDepositListBy]);

		$betSetting = empty($bonus_settings[$currentVipLevelId]) ? null : $bonus_settings[$currentVipLevelId];

		if (!empty($betSetting)) {
			if(array_key_exists($currentVipLevelId, $bonus_settings)){
				$success = true;

				$usdt_deposit_amount = $getUsdtDepositListBy[0]->amount;
				$maxBetAmount = $betSetting['maxBouns'];
				$percentage = $betSetting['percentage'] / 100;

				if(($usdt_deposit_amount * $percentage) > $maxBetAmount){
					$bonus_amount = $maxBetAmount;
				}else{
					$bonus_amount = $usdt_deposit_amount;
				}

				$this->appendToDebugLog('check bets amount release Bonus success', ['success' => $success,'playerId' => $this->playerId, 'fromDate' => $fromDate, 'toDate' => $toDate,'bonus_amount' => $bonus_amount, 'usdt_deposit_amount' => $usdt_deposit_amount
				]);
			}else{
				$errorMessageLang = 'Not exist this level in setting';
				$this->appendToDebugLog('Not exist this level in setting',['currentVipLevelId' => $currentVipLevelId]);
			}
		} else {
			$errorMessageLang = 'Not exist bet Setting';
			$this->appendToDebugLog('Not exist bet Setting',['betSetting' => $betSetting]);
		}

		return $result=['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonus_amount, 'usdt_deposit_amount' => $usdt_deposit_amount];
	}
}
