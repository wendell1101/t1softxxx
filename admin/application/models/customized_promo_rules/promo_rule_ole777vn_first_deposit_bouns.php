<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 *
 * ole777vn first deposit bouns
 *
 * OGP-21166
 *
 * 100%真人娱乐首存优惠
 * 活動有效期14天
 * 首存優惠,只能領一次
 * 最低存款100000(10萬)
 * 獎金計算 > 首存 * 108%
 * 取款條件 > (首存 + 獎金) * $betConditionTimes
 * 1. 從註冊日開始 到申請優惠當下  只能領一次
 * 2. 首次存款（count deposit > 1  就不能申請）首存金額大於等於10萬
 * 3. 如果有申請過 welcome Bonus底下優惠 超過一次 就不能申
 * 4. 金額 *1000 ,算完再 /1000

condition:
{
    "class": "promo_rule_ole777vn_first_deposit_bouns",
    "allowed_date":{
        "start": "",
        "end": ""
    },
    "release_date": {
        "start": "",
        "end": ""
    },
    "categoryId":7,
    "max_bouns":"7770000",
    "min_deposit_limit":"100000",
    "bonus_settings": [
		{"min_deposit": 100000, "max_deposit": 999999, "percentage": 108, "betConditionTimes":18},
		{"min_deposit": 1000000, "max_deposit": 2999999, "percentage": 108, "betConditionTimes":16},
		{"min_deposit": 3000000, "max_deposit": 999999999999999, "percentage": 108, "betConditionTimes":21}
    ]
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
class Promo_rule_ole777vn_first_deposit_bouns extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_ole777vn_first_deposit_bouns';
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
		$fromDate = !empty($allowed_date['start']) ? $allowed_date['start'] : $this->get_date_type(self::REGISTER_DATE);
		$toDate = !empty($allowed_date['end']) ? $allowed_date['end'] : $this->get_date_type(self::TO_TYPE_NOW);
		$today = $this->utils->getTodayForMysql();

		if($this->process_mock('today', $today)){
			//use mock data
			$this->appendToDebugLog('use mock today', ['today'=>$today]);
		}

		$deposit_page_amount = !empty($extra_info['depositAmount']) ? $extra_info['depositAmount'] : 0;

        $this->utils->debug_log('runBonusConditionChecker.extra_info:', $extra_info);
		$this->appendToDebugLog('runBonusConditionChecker.lite', ['dry_run' => $dry_run,'deposit_page_amount' => $deposit_page_amount]);

		$result = $this->checkCustomizeBounsCondition($bonus_settings, $fromDate, $toDate, $extra_info, $description, $errorMessageLang, $deposit_page_amount);

		if(array_key_exists('bonus_amount',$result)){
			unset($result['bonus_amount']);
		}

		if(array_key_exists('deposit_amount',$result)){
			unset($result['deposit_amount']);
		}

		if(array_key_exists('betConditionTimes',$result)){
			unset($result['betConditionTimes']);
		}

		if ($result['success']) {
			$result['continue_process_after_script'] = true;
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

        $times = $result['betConditionTimes'];
        $bonus_amount = $result['bonus_amount']*1000;
        $deposit_amount = $result['deposit_amount'];

        if($times > 0){
            $withdrawal_condition_amount = ($bonus_amount + $deposit_amount) * $times;
            $success = $withdrawal_condition_amount > 0;
        }else{
            $errorMessageLang='Lost betConditionTimes in settings';
        }

        $this->appendToDebugLog('get bonus_amount and deposit_amount and times', ['success' => $success, 'bonus_amount'=>$bonus_amount, 'deposit_amount'=>$deposit_amount, 'times'=>$times, 'withdrawal_condition_amount' => $withdrawal_condition_amount]);


        $result=['success'=>$success, 'message'=>$errorMessageLang, 'withdrawal_condition_amount'=>round($withdrawal_condition_amount/1000, 2)];
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

        $times = $result['betConditionTimes'];
        $bonus_amount = $result['bonus_amount']*1000;
        $deposit_amount = $result['deposit_amount'];

        if($times > 0){
            $transfer_condition_amount = ($bonus_amount + $deposit_amount) * $times;
            $success = $transfer_condition_amount > 0;
        }else{
            $errorMessageLang='Lost transfer betConditionTimes in settings';
        }

        $this->appendToDebugLog('get bonus_amount and deposit_amount and times', ['success' => $success, 'bonus_amount'=>$bonus_amount, 'deposit_amount'=>$deposit_amount, 'times'=>$times, 'transfer_condition_amount' => $transfer_condition_amount]);


        $result=['success'=>$success, 'message'=>$errorMessageLang, 'transfer_condition_amount'=>round($transfer_condition_amount/1000, 2)];
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
		$result = [];
		$allowed_date = $description['allowed_date'];
		$bonus_settings = $description['bonus_settings'];
		$fromDate = !empty($allowed_date['start']) ? $allowed_date['start'] : $this->get_date_type(self::REGISTER_DATE);
		$toDate = !empty($allowed_date['end']) ? $allowed_date['end'] : $this->get_date_type(self::TO_TYPE_NOW);
        $today = $this->utils->getTodayForMysql();

        if($this->process_mock('today', $today)){
            //use mock data
            $this->appendToDebugLog('use mock today', ['today'=>$today]);
        }

        $deposit_page_amount = $this->depositAmount;

		$this->utils->debug_log('releaseBonus.extra_info', ['extra_info'=>$extra_info]);
        $this->appendToDebugLog('releaseBonus.lite', ['dry_run' => $dry_run,'deposit_page_amount' => $deposit_page_amount]);

		$result = $this->checkCustomizeBounsCondition($bonus_settings, $fromDate, $toDate, $extra_info, $description, $errorMessageLang, $deposit_page_amount);

		if ($result['success']) {
			$result['bonus_amount'] = $result['bonus_amount']/1000;
		}

		return $result;
	}

	private function checkCustomizeBounsCondition($bonus_settings, $fromDate, $toDate, &$extra_info, $description, &$errorMessageLang, $deposit_page_amount){

        $success = false;
	    $bonus_amount = 0;
	    $deposit_amount = 0;
	    $betConditionTimes = 0;
	    $promoRuleId = $this->promorule['promorulesId'];
	    $release_date = $description['release_date'];
	    $min_deposit_limit = $description['min_deposit_limit'];
	    $max_bouns = $description['max_bouns'];
	    $promoCategoryId = $description['categoryId'];
	    $date_config['start'] = $fromDate;
	    $date_config['end'] = $toDate;

	    $getFirstDepositByDate = $this->callHelper('getAnyDepositByDate',[$fromDate, $toDate, 'first', null, null]);
	    $firstDepositAmount = intval($getFirstDepositByDate['amount']);

	    #check Released Bonus
	    $checkReleasedBonus = $this->callHelper('get_last_released_player_promo',[$promoRuleId,self::DATE_TYPE_CUSTOMIZE],$date_config);

	    #check first deposit
	    $countDepositByPlayerId = $this->callHelper('countDepositByPlayerId',[$fromDate, $toDate,0]);

	    #check applied promo
	    $appliedPromo = $this->callHelper('getPlayerActivePromo',[$this->playerId]);

	    $checkIfOver30Day = (strtotime($toDate) - strtotime($fromDate))/ (60*60*24);

	    if(!empty($release_date['start']) && !empty($release_date['end'])){
	    	$checkReleasedBonus = $this->callHelper('get_last_released_player_promo',[$promoRuleId, self::DATE_TYPE_CUSTOMIZE, $release_date]);
	    }

        $this->utils->debug_log('check params detail.extra_info', $extra_info);
	    $this->appendToDebugLog('check params detail.lite', ['checkReleasedBonus' => $checkReleasedBonus, 'getFirstDepositByDate' => $getFirstDepositByDate, 'firstDepositAmount' => $firstDepositAmount, 'countDepositByPlayerId' => $countDepositByPlayerId, 'appliedPromo' => $appliedPromo, 'description' => $description, 'promoRuleId' => $promoRuleId, 'date_config' => $date_config, 'release_date' => $release_date, 'checkIfOver30Day' => $checkIfOver30Day, 'deposit_page_amount' => $deposit_page_amount]);

	    if ($checkIfOver30Day > 30) {
			$success=false;
			$errorMessageLang =  lang('promo_rule.common.error');
			return $result = ['success' => $success, 'message' => $errorMessageLang, 'continue_process_after_script' => FALSE];
	    }

	    if($checkReleasedBonus){
	    	$success=false;
			$errorMessageLang =  lang('notify.83');
	    	return $result = ['success' => $success, 'message' => $errorMessageLang, 'continue_process_after_script' => FALSE];
	    }else{
	    	$count = 0;
	    	foreach ($appliedPromo as $key => $value) {
	    		# 如果有申請過 welcome Bonus底下優惠 超過一次 就不能申
	    		if ($promoCategoryId == $value->promoCategory) {
		    		$count += 1;
		    	}
	    	}

	    	$this->appendToDebugLog('count apply welcome Bonus promo',['count' => $count]);

	    	if ($count > 1) {
	    		$success=false;
				$errorMessageLang = lang('promo_rule.common.error');
		    	return $result=['success' => $success, 'message' => $errorMessageLang, 'continue_process_after_script' => FALSE];
	    	}

			if($countDepositByPlayerId == 1){#one approve deposit only
				$firstDeposit = $firstDepositAmount*1000;
			}elseif ($countDepositByPlayerId < 1) {
				$firstDeposit = $deposit_page_amount*1000;
		    }
		    else{
				$success=false;
				$errorMessageLang = lang('notify.80');
				return $result=['success' => $success, 'message' => $errorMessageLang, 'continue_process_after_script' => FALSE];
		    }

		    $this->appendToDebugLog('check firstDeposit amount is',['firstDeposit' => $firstDeposit]);

		    if( $firstDeposit < $min_deposit_limit){#A single deposit needs to be greater than 100000 thb
				$success=false;
				$errorMessageLang = lang('notify.79');
				return $result=['success' => $success, 'message' => $errorMessageLang, 'continue_process_after_script' => FALSE];
			}else{
				$deposit_amount = $firstDeposit;
			}
	    }

		if (!empty($bonus_settings)) {
			if(is_array($bonus_settings)){
                foreach ($bonus_settings as $list) {
                    if($deposit_amount >= $list['min_deposit'] && $deposit_amount <= $list['max_deposit']){
                    	$bonus_amount = $deposit_amount * ($list['percentage']/100);
                    	if ($bonus_amount > $max_bouns) {
                    		$bonus_amount = $max_bouns;
                    	}
                    	$betConditionTimes = $list['betConditionTimes'];
                        $success = true;
                    } else {
                        continue;
                    }
                }
            }

		$this->appendToDebugLog('check bets amount release Bonus success', ['success' => $success,'playerId' => $this->playerId, 'fromDate' => $fromDate, 'toDate' => $toDate, 'bonus_settings' => $bonus_settings,'bonus_amount' => $bonus_amount, 'deposit_amount' => $deposit_amount, 'betConditionTimes' => $betConditionTimes]);

		} else {
			$errorMessageLang = lang('Not exist bet Setting');
			$this->appendToDebugLog('Not exist bet Setting',['bonus_settings' => $bonus_settings]);
		}

		return $result=['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonus_amount, 'deposit_amount' => $deposit_amount, 'betConditionTimes' => $betConditionTimes, 'continue_process_after_script' => FALSE];
	}
}