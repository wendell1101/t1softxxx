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
 * 2. 首次存款（count deposit > 1  就不能申請）首存金額大於等於10萬vnd (aka SBE $100)
 * 3. 如果有申請過 welcome Bonus底下優惠 超過一次 就不能申
 * 
 * OGP-35515
 * Update WC formula
 * 
 * max bonus with min deposit = max bonus / bonus rate (use php ceiling 無條件進位)
 * excess_deposit_amount = deposit amount - max bonus with min deposit
 * 
 * If Deposit amount > (max)bonus amount
 * New WC formula = (max bonus + max bonus with min deposit) * betConditionTimes + excess_deposit_amount
 *
 * If Deposit amount < bonus amount
 * Original WC formula = (bonus amount + deposit amount) * betConditionTimes
 *
 * IF Deposit amount > (max)bonus amount
 * e.g.
 *      100% bonus, up to 666, wc 22 times
 *      dp 2000
 *      WC = (666+666)x 22+(2000-666) = 30638
 * 
 * IF Deposit amount < bonus amount
 * e.g.
 *      100% bonus, up to 666, wc 22 times
 *      dp 333
 *      WC = (333+333) x 22 = 14652
 * 
 * IF Deposit amount < bonus amount
 * e.g.
 *      50% bonus, up to 666, wc 22 times
 *      dp 1000
 *      WC = (1000+500) x 22 = 33000
 * 
 * Refator v2 version

condition:
{
    "class": "promo_rule_ole777vn_first_deposit_bouns_v2",
    "allowed_date":{
        "start": "",
        "end": ""
    },
    "release_date": {
        "start": "",
        "end": ""
    },
    "categoryId":7,
    "max_bouns":"7770",
    "min_deposit_limit":"100",
    "bonus_settings": [
		{"min_deposit": 100, "max_deposit": 1000, "percentage": 108, "betConditionTimes":18},
		{"min_deposit": 1000, "max_deposit": 3000, "percentage": 108, "betConditionTimes":16},
		{"min_deposit": 3000, "max_deposit": -1, "percentage": 108, "betConditionTimes":21}
    ]
}
 *
 *
 *
 */
class Promo_rule_ole777vn_first_deposit_bouns_v2 extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_ole777vn_first_deposit_bouns_v2';
	}

	/**
	 * run bonus condition checker
	 * @param  array $description original description in rule
	 * @param  array $extra_info
	 * @param  boolean $dry_run
	 * @return  array ['success'=> success, 'message'=> errorMessageLang, 'continue_process_after_script' => TRUE]
	 */
	protected function runBonusConditionChecker($description, &$extra_info, $dry_run){
		$errorMessageLang = null;
		$result = $this->checkCustomizeBounsCondition($description, $extra_info, $errorMessageLang);

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
        $times = $result['betConditionTimes'];
        $bonus_amount = $result['bonus_amount'];
        $deposit_amount = $result['deposit_amount'];
        $excess_deposit_amount = $result['excess_deposit_amount'];
		$max_bonus_need_min_dep = $result['max_bonus_need_min_dep'];

        if($times > 0){
            if( !empty($excess_deposit_amount) && !empty($max_bonus_need_min_dep) ){
                $withdrawal_condition_amount = (($bonus_amount + $max_bonus_need_min_dep) * $times) + $excess_deposit_amount;
            }else{
                $withdrawal_condition_amount = ($bonus_amount + $deposit_amount) * $times;
            }
            $success = $withdrawal_condition_amount > 0;
        }else{
            $errorMessageLang='Lost betConditionTimes in settings';
        }

        return ['success'=>$success, 'message'=>$errorMessageLang, 'withdrawal_condition_amount'=>round($withdrawal_condition_amount, 2)];
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
        $bonus_amount = $result['bonus_amount'];
        $deposit_amount = $result['deposit_amount'];

        if($times > 0){
            $transfer_condition_amount = ($bonus_amount + $deposit_amount) * $times;
            $success = $transfer_condition_amount > 0;
        }else{
            $errorMessageLang='Lost transfer betConditionTimes in settings';
        }

        return ['success'=>$success, 'message'=>$errorMessageLang, 'transfer_condition_amount'=>round($transfer_condition_amount, 2)];
    }

	/**
	 * release bonus
	 * @param  array $description original description in rule
	 * @param  array $extra_info
	 * @param  boolean $dry_run
	 * @return  array ['success'=> success, 'message'=> errorMessageLang, 'bonus_amount'=> bonus amount]
	 */
	protected function releaseBonus($description, &$extra_info, $dry_run){
		$errorMessageLang = null;
		$result = $this->checkCustomizeBounsCondition($description, $extra_info, $errorMessageLang);
		return $result;
	}

	private function existApplyOtherPromo($description){
		$result = false;

		$promoCategoryId = !empty($description['categoryId']) ? $description['categoryId'] : null;
		if(empty($promoCategoryId)){
			return $result;
		}

		$appliedPromo = $this->callHelper('getPlayerActivePromo',[$this->playerId]);
		$this->appendToDebugLog('existApplyOtherPromo appliedPromo',['promoCategoryId' => $promoCategoryId]);
		
		if(empty($appliedPromo)){
			return $result;
		}

		$count = 0;
		foreach ($appliedPromo as $key => $value) {
			# 如果有申請過 welcome Bonus底下優惠 超過一次 就不能申
			if ($promoCategoryId == $value->promoCategory) {
				$count += 1;
			}
		}

		if(!empty($count)) {
			$result = true;
		}

		$this->appendToDebugLog('count apply welcome Bonus promo',['count' => $count]);
		return $result;
	}

	private function checkCustomizeBounsCondition($description, &$extra_info, &$errorMessageLang){
		$success = false;
		$bonus_amount = 0;
		$betConditionTimes = 0;
		$deposit_amount = 0;
		$promoRuleId = $this->promorulesId;

		$bonus_settings = !empty($description['bonus_settings']) ? $description['bonus_settings'] : null;
		if(empty($bonus_settings)){
			$errorMessageLang = 'Not exist bet Setting';
			return ['success' => $success, 'message' => $errorMessageLang];
		}

		#check first deposit
		$fromDate = !empty($description['allowed_date']['start']) ? $description['allowed_date']['start'] : $this->get_date_type(self::REGISTER_DATE);
		$toDate = !empty($description['allowed_date']['end']) ? $description['allowed_date']['end'] : $this->get_date_type(self::TO_TYPE_NOW);
		$countDepositByPlayerId = $this->callHelper('countDepositByPlayerId',[$fromDate, $toDate]);
		$this->appendToDebugLog('countDepositByPlayerId',['formDate' => $fromDate, 'toDate'=> $toDate, 'result' => $countDepositByPlayerId]);

		#check Released Bonus
		$release_date_range['start'] = !empty($description['release_date']['start']) ? $description['release_date']['start'] : $fromDate;
		$release_date_range['end'] = !empty($description['release_date']['end']) ? $description['release_date']['end'] : $toDate;
		$checkReleasedBonus = $this->callHelper('get_last_released_player_promo',[$promoRuleId, self::DATE_TYPE_CUSTOMIZE, $release_date_range]);
		$this->appendToDebugLog('checkReleasedBonus',['release_date_range' => $release_date_range, 'result' => $checkReleasedBonus]);
		if($checkReleasedBonus){
			$errorMessageLang =  'notify.83';
			return ['success' => $success, 'message' => $errorMessageLang];
		}

		if($this->callHelper('isCheckingBeforeDeposit',[])){ // deposit amount from deposit page
			$this->appendToDebugLog('ignore trans', ['is_checking_before_deposit'=>$extra_info['is_checking_before_deposit']]);
			$deposit_page_amount = !empty($extra_info['depositAmount']) ? $extra_info['depositAmount'] : 0;
			$deposit_amount = $deposit_page_amount;

			#only allow first deposit to apply promo from deposit page today, or need to apply from promotion page
			if(!empty($countDepositByPlayerId)){
				$errorMessageLang = 'notify.80';
				return ['success' => $success, 'message' => $errorMessageLang];
			}
		}else{ #get first deposit amount
			$getFirstDepositByDate = $this->callHelper('getAnyDepositByDate',[$fromDate, $toDate, 'first', null, null]);
			$deposit_amount = intval($getFirstDepositByDate['amount']);
		}

		$min_deposit_limit = !empty($description['min_deposit_limit']) ? $description['min_deposit_limit'] : 0;
		if($deposit_amount < $min_deposit_limit){
			$errorMessageLang = 'notify.79';
			return ['success' => $success, 'message' => $errorMessageLang];
		}

		if($countDepositByPlayerId > 1){
			$errorMessageLang = 'notify.80';
			return ['success' => $success, 'message' => $errorMessageLang];
		}

		#check applied promo
		$existApplyOtherPromo = $this->existApplyOtherPromo($description);
		if($existApplyOtherPromo){
			$errorMessageLang = 'promo_rule.common.error';
			return ['success' => $success, 'message' => $errorMessageLang];
		}

		#check if over 30 days
		$checkIfOver30Day = (strtotime($toDate) - strtotime($fromDate))/ (60*60*24);
		$this->appendToDebugLog('checkIfOver30Day', ['result' => $checkIfOver30Day]);
		if ($checkIfOver30Day > 30) {
			$errorMessageLang =  'promo_rule.common.error';
			return ['success' => $success, 'message' => $errorMessageLang];
		}

		$max_bouns = !empty($description['max_bouns']) ? $description['max_bouns'] : 0;
		$excess_deposit_amount = 0;
		$max_bonus_need_min_dep = 0;

		foreach ($bonus_settings as $list) {
			//max_deposit<0 means no limit
			if( ($list['min_deposit'] <= $deposit_amount) && ($deposit_amount <= $list['max_deposit'] || $list['max_deposit'] < 0) ){
				$rate = $list['percentage']/100;
				$bonus_amount = $deposit_amount * $rate;

				if ($bonus_amount > $max_bouns) {
					$max_bonus_need_min_dep = ceil($max_bouns/$rate); // 無條件進位: 7194.444 => 7195
					$excess_deposit_amount = $deposit_amount - $max_bonus_need_min_dep;
					$bonus_amount = $max_bouns;
				}

				$betConditionTimes = $list['betConditionTimes'];
				$success = true;
			}
		}

		$this->appendToDebugLog('checkCustomizeBounsCondition success', [
			'bonus_amount' => $bonus_amount, 
			'deposit_amount' => $deposit_amount, 
			'betConditionTimes' => $betConditionTimes, 
			'excess_deposit_amount' => $excess_deposit_amount, 
			'max_bonus_need_min_dep' => $max_bonus_need_min_dep
		]);

		$result = [
			'success' => $success, 
			'message' => $errorMessageLang, 
			'bonus_amount' => $bonus_amount, 
			'deposit_amount' => $deposit_amount, 
			'betConditionTimes' => $betConditionTimes, 
			'excess_deposit_amount' => $excess_deposit_amount, 
			'max_bonus_need_min_dep' => $max_bonus_need_min_dep,
			'continue_process_after_script' => TRUE
		];

		return $result;
	}
}
