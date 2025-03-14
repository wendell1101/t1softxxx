<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 *
 * OGP-29564
 * 連續存款七日,第八日即可獲得對應獎金
 * 5倍取款條件
 * 玩家需到玩家中心申請優惠, 自動發放獎金
 *
condition:
{
    "class": "promo_rule_sssbet_cumalative_deposit_bonus",
    "allowed_date": {
        "start": "",
        "end": ""
    },
    "cumalative_days": 7,
    "bonus_settings": [
        {"min_deposit":     200, "max_deposit":     500, "bonus_amount":     7},
        {"min_deposit":     500, "max_deposit":    1000, "bonus_amount":    17},
        {"min_deposit":    1000, "max_deposit":   10000, "bonus_amount":    38},
        {"min_deposit":   10000, "max_deposit":   50000, "bonus_amount":    88},
        {"min_deposit":   50000, "max_deposit":  300000, "bonus_amount":   277},
        {"min_deposit":  300000, "max_deposit":  600000, "bonus_amount":   877},
        {"min_deposit":  600000, "max_deposit": 2000000, "bonus_amount":  1177},
        {"min_deposit": 2000000, "max_deposit": 5000000, "bonus_amount":  2555},
        {"min_deposit": 5000000, "max_deposit":      -1, "bonus_amount": 15000}
    ]
}
*
*
*/
class Promo_rule_sssbet_cumalative_deposit_bonus extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_sssbet_cumalative_deposit_bonus';
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
    protected function getDateRangeByCumalativeDays($cumalative_days){
        $modify = $cumalative_days - 1 ;
        $yesterday = $this->utils->getYesterdayForMysql();
        $lastDay = new Datetime($yesterday);
        $to = $lastDay->format('Y-m-d');

        $firstDay = clone $lastDay;
        $firstDay->modify('-' . $modify . ' days');
        $from = $firstDay->format('Y-m-d');

        return [$from, $to];
    }

	private function checkCustomizeBonusCondition($description, &$extra_info, &$errorMessageLang){
        $success = false;
	    $bonus_amount = 0;
        //$metDepositAndDateCondition = false;
        $bonus_settings = $description['bonus_settings'];

        $today = $this->utils->getTodayForMysql();
        if($this->process_mock('today', $today)){
            //use mock data
            $this->appendToDebugLog('use mock today', ['today'=>$today]);
        }

        $_extra_info['week_start'] = !empty($description['week_start']) ? $description['week_start'] : 'monday';
        $fromDatetime = $this->callHelper('get_date_type', [self::DATE_LAST_WEEK_START, $_extra_info]);
        $toDatetime = $this->callHelper('get_date_type', [self::DATE_LAST_WEEK_END, $_extra_info]);

        if(!empty($description['allowed_date']['start']) && !empty($description['allowed_date']['end'])){
            $fromDatetime = $description['allowed_date']['start'] . ' ' . Utils::FIRST_TIME;
            $toDatetime = $description['allowed_date']['end'] . ' ' . Utils::LAST_TIME;
        }

        /*
        if(!empty($description['cumalative_days'])){
            $requireCumalativeDays = $description['cumalative_days'];
            list($fromDate, $toDate) = $this->getDateRangeByCumalativeDays($requireCumalativeDays);
            $fromDatetime = $fromDate . ' ' . Utils::FIRST_TIME;
            $toDatetime = $toDate . ' ' . Utils::LAST_TIME;
        }

        // check met deposit and days condition
        $records = $this->callHelper('getConsecutiveDepositAndDateByDateTime', [$this->playerId, $fromDate, $toDate, 0]);
        $total_deposit_days = count($records);
        if($total_deposit_days === $requireCumalativeDays){
            $metDepositAndDateCondition = true;
        }

        if(!$metDepositAndDateCondition){
            $errorMessageLang = 'promo_custom.deposit_donot_match_the_requirement';
            return ['success' => $success, 'message' => $errorMessageLang];
        }
        */

        $this->appendToDebugLog('runBonusConditionChecker check date', [
            'today'=>$today, 'fromDatetime'=>$fromDatetime, 'toDatetime'=>$toDatetime,
            //'met cumalative condition' => $metDepositAndDateCondition,
            //'required cumalative deposit days' => $requireCumalativeDays,
            //'total deposit days' => $total_deposit_days,
            //'total deposit records' => $records
        ]);


	    #get 7 days total deposit
	    $sum_deposit_amount = $this->callHelper('sum_deposit_amount',[$fromDatetime, $toDatetime, 0]);
	    $this->appendToDebugLog('total deposit', ['sum_deposit_amount' => $sum_deposit_amount]);

        if (!empty($bonus_settings)) {
            foreach ($bonus_settings as $list) {
                if(($list['min_deposit'] <= $sum_deposit_amount) &&
                    ($sum_deposit_amount < $list['max_deposit'] || $list['max_deposit']<0)){
                    //max_max<0 means no limit
                    $success = true;
                    $bonus_amount = $list['bonus_amount'];
                } else {
                    $errorMessageLang = 'promo_custom.deposit_sum_insufficient';
                }
            }
        } else {
            $errorMessageLang = 'promo_rule.common.error';
        }

		return ['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonus_amount];
	}
}
