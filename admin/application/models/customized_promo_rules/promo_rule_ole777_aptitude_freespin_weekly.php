<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 * Only allow player to apply on each Monday (set by promo rule)
 * Filter player who reach accumulated deposit minimum of IDR 700 on the previous week
 * If yes, be able to claim this promo on the promo page.
 *
 * Else if, pop up the promo is not qualified to apply
 *
 * Reflect the previous week's accumulated deposits on the remarks of promo request
 *
 * condition:
{
    "class": "promo_rule_ole777_aptitude_freespin_weekly",
    "allowed_each_weekday": 1,
    "deposit_minimum_previous_week": 700,
	"insvr.CreateAndApplyBonusMulti": {}
}
allowed_each_weekday: 1 (for Monday) through 7 (for Sunday)

The related inputs,
- Username
- get_date_type_now
- get_date_type_last_week_start
- get_date_type_last_week_end

Mock for class:
{
    "getLastDepositByDate":{"amount":"800"}
}
 */
class promo_rule_ole777_aptitude_freespin_weekly extends Abstract_promo_rule{
	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_ole777_aptitude_freespin_weekly';
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

		$runBonusConditionResult = $this->runBonusConditionChecker($description, $extra_info, $dry_run);
		$success = $runBonusConditionResult['success'];
		$errorMessageLang = $runBonusConditionResult['message'];
		$bonus_amount = 0;

		$result=['success'=>$success, 'message'=>$errorMessageLang, 'bonus_amount'=>$bonus_amount];
		return $result;
	}

	/**
	 * run bonus condition checker
	 * @param  array $description original description in rule
	 * @param  array $extra_info
	 * @param  boolean $dry_run
	 * @return  array ['success'=> success, 'message'=> errorMessageLang, 'continue_process_after_script' => TRUE]
	 */
	protected function runBonusConditionChecker($description, &$extra_info, $dry_run){
		$isEachWeekday = null;
		$errorMessageLang = null;
		$isAppliedToday = null;
		$isReachAccumulatedDeposit = false;
		$caseNo = null;
		$extra_info['reason'] = '';
		$promorule = $this->promorule;
		$promoRuleId = $promorule['promorulesId'];

		$get_date_type_now = $this->get_date_type(self::TO_TYPE_NOW);

		$allowed_each_weekday = $description['allowed_each_weekday'];
		$this->appendToDebugLog('check allowed_each_weekday', ['get_date_type_now'=> $get_date_type_now, 'allowed_each_weekday'=>$allowed_each_weekday]);

		$d=new DateTime($get_date_type_now);
		$currentDate = $d->format('Y-m-d');
		$currentWeekday=$d->format('N');

		if( $currentWeekday == $allowed_each_weekday ){
			$isEachWeekday = true;
		}else{
			$isEachWeekday = false;
		}

		if($isEachWeekday === true){
			/// 同一天不可以領兩次 (阻擋當天已經有通過)
			// already_released_promo_rule
			// get_last_released_player_promo
			$_extra_info = [];
			$_extra_info['start'] = $currentDate.' '.Utils::FIRST_TIME; // 2021-11-05 00:00:00
			$_extra_info['end'] = $currentDate.' '.Utils::LAST_TIME; // 2021-11-05 23:59:59
			$checkReleasedBonus = $this->callHelper('get_last_released_player_promo',[$promoRuleId, self::DATE_TYPE_CUSTOMIZE, $_extra_info]);
			if($checkReleasedBonus){
				$isAppliedToday = true;
			}else{
				$isAppliedToday = false;
			}
		}

		// Filter player who reach accumulated deposit minimum of IDR 700 on the previous week
		if($isEachWeekday === true && $isAppliedToday === false){
			$_extra_info = [];
			$_extra_info['week_start'] = 'monday';
			$fromDate = $this->get_date_type(self::DATE_LAST_WEEK_START, $_extra_info);
			$toDate = $this->get_date_type(self::DATE_LAST_WEEK_END, $_extra_info);
			$getLastDepositAmount = $this->callHelper('sum_deposit_amount',[$fromDate, $toDate, 0]);
	    	$lastDepositAmount = floatval($getLastDepositAmount);

			// The reason will append into note of Promo Request List.
			$sprintf_format = 'lastDepositAmount %s during: %s to %s, '; // 3 params
			$extra_info['reason'] .= sprintf($sprintf_format, $lastDepositAmount, $fromDate, $toDate);
			//
			$requestCount = $this->callHelper('get_request_count_player_promo',[$this->playerId, 0, $promoRuleId, $currentDate]);
			$sprintf_format_request_counter = 'today accumulated request count: %s '; // 1 params
			$extra_info['reason'] .= sprintf($sprintf_format_request_counter, $requestCount);

			$deposit_minimum_previous_week = $description['deposit_minimum_previous_week'];



			if($lastDepositAmount >= $deposit_minimum_previous_week){
				$isReachAccumulatedDeposit = true;
			}else{
				$isReachAccumulatedDeposit = false;
			}
$this->appendToDebugLog('check lastDepositAmount', [ 'fromDate'=>$fromDate
							, 'toDate'=>$toDate
							, 'lastDepositAmount'=>$lastDepositAmount
							, 'deposit_minimum_previous_week'=>$deposit_minimum_previous_week
							, 'isReachAccumulatedDeposit'=>$isReachAccumulatedDeposit
						]);
		}

		if( ! empty($isReachAccumulatedDeposit) && ! empty($isEachWeekday)){
			$success=true;
			$caseNo = 1;
		}else if( empty($isEachWeekday) ) {
			$success=false;
			$errorMessageLang = 'Not right date';
			$caseNo = 169;
		}else if( $isAppliedToday ){
			$success=false;
			// You already applied for the promotion and cannot apply again.
			$errorMessageLang = 'notify.83';
			$caseNo = 175;
		}else if( empty($isReachAccumulatedDeposit) ) {
			$success=false;
			// You did not meet the minimum deposit amount requirement!
			$errorMessageLang = 'notify.43';
			$caseNo = 172;
		}

		$result=['success'=>$success
			, 'message'=>$errorMessageLang
			, 'continue_process_after_script' => false
			, 'caseNo', $caseNo // for trace issue case
		];
		return $result;
	} // EOF runBonusConditionChecker()
}
