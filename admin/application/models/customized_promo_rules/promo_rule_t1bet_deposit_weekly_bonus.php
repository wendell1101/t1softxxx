<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 *
 * OGP-28902
 * 每週可申請一次
 * 周循环是从周一开始到周日结束计算
 * 上周结束之后自动派发奖励，所以当周的奖励是看上周的存款。
 * 本次活动只需3倍流水即可申请提现
 * 玩家需到玩家中心申請優惠, 自動發放獎金
 *
condition:
{
    "class": "promo_rule_t1bet_deposit_weekly_bonus",
    "allowed_date": {
        "start": "2022-01-01 00:00:00",
        "end": "2022-02-23 23:59:59"
    },
    "release_date": {
        "start": "",
        "end": ""
    },
    "bonus_settings": [
        {"min_deposit": 500, "max_deposit": 1000, "bonus_amount": 8},
        {"min_deposit": 1000, "max_deposit": 5000, "bonus_amount": 88},
        {"min_deposit": 5000, "max_deposit": 10000, "bonus_amount": 88},
        {"min_deposit": 10000, "max_deposit": 30000, "bonus_amount": 178},
        {"min_deposit": 30000, "max_deposit": 50000, "bonus_amount": 588},
        {"min_deposit": 50000, "max_deposit": 100000, "bonus_amount": 888},
        {"min_deposit": 100000, "max_deposit": -1, "bonus_amount": 1888}
    ]
}
*
*
*/
class Promo_rule_t1bet_deposit_weekly_bonus extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_t1bet_deposit_weekly_bonus';
	}

	/**
	 * run bonus condition checker
	 * @param  array $description original description in rule
	 * @param  array $extra_info
	 * @param  boolean $dry_run
	 * @return  array ['success'=> success, 'message'=> errorMessageLang]
	 */
	protected function runBonusConditionChecker($description, &$extra_info, $dry_run){
		$errorMessageLang = null;
        $result = $this->checkCustomizeBonusCondition($description, $extra_info, $errorMessageLang);

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
		$errorMessageLang = null;
		$request = $this->checkCustomizeBonusCondition($description, $extra_info, $errorMessageLang);

        return $request;
	}

	private function checkCustomizeBonusCondition($description, &$extra_info, &$errorMessageLang){
        $success = false;
	    $bonus_amount = 0;
	    $promoRuleId = $this->promorulesId;
        $bonus_settings = $description['bonus_settings'];
        $allowed_date = $description['allowed_date'];

		$_extra_info = [];
		$_extra_info['week_start'] = 'monday';
        $fromDate = !empty($allowed_date['start']) ? $allowed_date['start'] : $this->get_date_type(self::DATE_LAST_WEEK_START, $_extra_info);
        $toDate = !empty($allowed_date['end']) ? $allowed_date['end'] : $this->get_date_type(self::DATE_LAST_WEEK_END, $_extra_info);
        $this->appendToDebugLog('runBonusConditionChecker check date', ['fromDate'=>$fromDate, 'toDate'=>$toDate]);

	    #get last week deposit
	    $sum_deposit_amount = $this->callHelper('sum_deposit_amount',[$fromDate, $toDate, 0]);

	    #check Released Bonus
	    $checkReleasedBonus = $this->callHelper('count_approved_promo',[$promoRuleId, self::DATE_TYPE_THIS_WEEK]);

	    $release_date = $description['release_date'];
	    if(!empty($release_date['start']) && !empty($release_date['end'])){
			$checkReleasedBonus = $this->callHelper('count_approved_promo',[$promoRuleId, self::DATE_TYPE_CUSTOMIZE, $release_date]);
	    }

	    $this->appendToDebugLog('checkCustomizeBonusCondition count_approved_promo',
	    	['release_date' => $release_date, 'sum_deposit_amount' => $sum_deposit_amount, 'checkReleasedBonus' => $checkReleasedBonus]);

	    if($checkReleasedBonus){
			$errorMessageLang =  'notify.83';
	    }else{
            if (!empty($bonus_settings)) {
                foreach ($bonus_settings as $list) {
                    if(($list['min_deposit'] <= $sum_deposit_amount) &&
                        ($sum_deposit_amount < $list['max_deposit'] || $list['max_deposit']<0)){
                        //max_max<0 means no limit
                        $success = true;
                        $bonus_amount = $list['bonus_amount'];
                    } else {
                        $errorMessageLang = 'promo_custom.deposit_donot_match_the_requirement';
                    }
                }
            } else {
                $errorMessageLang = 'promo_rule.common.error';
            }
        }

		return ['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonus_amount];
	}
}
