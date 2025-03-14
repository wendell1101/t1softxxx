<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 *
 * OGP-27945
 * 新年用户红包活动
 * 统计12月1日-12月31日期间的累積投注金額(>=2000)
 * 1月1日發放獎金, 只能申請一次
 * 按照VIP等级发送新年隨機现金赠礼
 *
condition:
{
    "class": "promo_rule_smash_new_year_bonus",
    "application_day":"2023-01-01",
    "allowed_date": {
        "start": "",
        "end": ""
    },
    "min_bet": 2000,
    "bonus_settings": {
		 "VIP1" : { "min_bonus":    1, "max_bonus":    3},
         "VIP2" : { "min_bonus":    3, "max_bonus":    5},
         "VIP3" : { "min_bonus":    9, "max_bonus":   11},
         "VIP4" : { "min_bonus":   19, "max_bonus":   22},
         "VIP5" : { "min_bonus":   50, "max_bonus":   55},
         "VIP6" : { "min_bonus":  200, "max_bonus":  210},
         "VIP7" : { "min_bonus":  500, "max_bonus":  520},
         "VIP8" : { "min_bonus":  999, "max_bonus": 1020},
         "VIP9" : { "min_bonus": 2000, "max_bonus": 2020},
        "VIP10" : { "min_bonus": 9999, "max_bonus": 9999}
    }
}
*
*
*/
class Promo_rule_smash_new_year_bonus extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_smash_new_year_bonus';
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
        $result = $this->checkCustomizeBonusCondition($description, $extra_info, $errorMessageLang);

		return $result;
	}

	private function checkCustomizeBonusCondition($description, &$extra_info, &$errorMessageLang){
        $success = false;
        $bonus_amount = 0;
        $levelId = $this->levelId;

        $allowed_date = $description['allowed_date'];
        $bonus_settings = $description['bonus_settings'];
        $min_bet = $description['min_bet'];
        $application_day = isset($description['application_day']) ? $description['application_day'] : false;
        $today = $this->utils->getTodayForMysql();

        if (!empty($application_day)) {
            if ($today != $application_day) {
                $errorMessageLang =  lang('notify.78');
                return ['success' => $success, 'message' => $errorMessageLang];
            }
        }

        $fromDate = !empty($allowed_date['start']) ? $allowed_date['start'] : $this->get_date_type(self::DATE_LAST_MONTH_START);
        $toDate = !empty($allowed_date['end']) ? $allowed_date['end'] : $this->get_date_type(self::DATE_LAST_MONTH_END);
	    $playerBetByDate = $this->callHelper('getPlayerBetByDate',[$fromDate, $toDate]);
	    $this->appendToDebugLog('bet result',['total bet' => $playerBetByDate, 'fromDate' => $fromDate, 'toDate' => $toDate]);

	    if($playerBetByDate < $min_bet){
            $errorMessageLang =  lang('notify.81');
            return ['success' => $success, 'message' => $errorMessageLang];
	    }

	    if (!empty($bonus_settings)) {
            if(array_key_exists($levelId, $bonus_settings)){
                $setting = $bonus_settings[$levelId];
                list($min_amount, $max_amount) = [ $setting['min_bonus'], $setting['max_bonus'] ];
                $bonus_amount = rand($min_amount, $max_amount);
                $success = true;
                $this->appendToDebugLog('random bonus amount', ['result' => $bonus_amount, 'levelId' => $levelId]);
            }else{
                $errorMessageLang = 'Not right group level';
            }
		} else {
			$errorMessageLang = 'Not exist Setting';
		}

		return ['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonus_amount];
	}
}
