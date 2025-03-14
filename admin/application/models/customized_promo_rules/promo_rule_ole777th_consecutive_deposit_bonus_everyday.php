<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 *
 * 日优惠
 * 连续 [7日] 存款 , 每日最低 [300THB] , 可以获得 [300THB] Bonus
 * 连续 [7日] 存款 , 每日最低 [500THB] , 可以获得 [500THB] Bonus
 * 连续 [7日] 存款 , 每日最低 [1000THB] , 可以获得 [1000THB] Bonus
 * 提款条件: Bonus金额的5倍流水
 * 中断的情况：重置天數。
 *
 * OGP-25551
 * 連續七日存款達成了之後，這七日的存款中即便有拿去申請其他優惠活動，也符合申請日優惠的資格。
 * 獎金發放次數：不限。
 *
 *

* Bonus condition && Bonus release:
{
    "class": "promo_rule_ole777th_consecutive_deposit_bonus_everyday",
    "deposit_from": "2022-04-01",(optional for test)
    "deposit_to": "2022-04-07",(optional for test)
    "consecutive_days": 6,
    "bonus_settings": [
        {"bonus_amount":   300, "daily_min_deposit":  300},
        {"bonus_amount":   500, "daily_min_deposit":  500},
        {"bonus_amount":  1000, "daily_min_deposit": 1000}
    ]
}

 *
 *
 */
class Promo_rule_ole777th_consecutive_deposit_bonus_everyday extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_ole777th_consecutive_deposit_bonus_everyday';
	}

    public function getLast7Days($description, $require_consecutive_days){
        $consecutive_days = $require_consecutive_days - 1;
        $fromDate = date("Y-m-d", strtotime( " -$consecutive_days days", strtotime('now')));
        $toDate = $this->utils->getTodayForMysql();;

        if(!empty($description['deposit_from']) && !empty($description['deposit_to'])){
            $fromDate = $description['deposit_from'];
            $toDate = $description['deposit_to'];
        }

        $this->appendToDebugLog('getLast ' . $require_consecutive_days . ' Days',['fromDate'=>$fromDate, 'toDate'=>$toDate]);

        return [$fromDate, $toDate];
    }

    public function checkDepositAndDate($description){
	    $met_condition = false;
        $bonus_settings = $description['bonus_settings'];
        $require_consecutive_days = (int) $description['consecutive_days'];

        $bonusAmount = 0;

        // get deposit and days in 7 consecutive days
        list($fromDate, $toDate) = $this->getLast7Days($description, $require_consecutive_days);


        $depositAndDate = [];
        foreach ($bonus_settings as $settings){
            $metDepositAndDateCondition = false;

            $require_min_deposit = (int)$settings['daily_min_deposit'];
            $depositAndDate[$require_min_deposit]['required_min_deposit'] = $require_min_deposit;

            $records = $this->callHelper('getConsecutiveDepositAndDateByDateTime', [$this->playerId, $fromDate, $toDate, $require_min_deposit]);
            $depositAndDate[$require_min_deposit]['records'] = $records;

            // check met deposit and days condition
            $deposit_consecutive_days = count($records);
            if($deposit_consecutive_days === $require_consecutive_days){
                $metDepositAndDateCondition = true;
            }
            $depositAndDate[$require_min_deposit]['metDepositAndDateCondition'] = $metDepositAndDateCondition;

            // obtain bonus amount
            if($metDepositAndDateCondition){
                $met_condition = true;
                $bonusAmount = $settings['bonus_amount'];
            }
        }

        $this->appendToDebugLog('checkDepositAndDate', ['description'=>$description,'depositAndDate'=>$depositAndDate, 'bonus_amount'=>$bonusAmount]);

        return [$met_condition, $bonusAmount];
    }

	/**
	 * run bonus condition checker
	 * @param  array $description original description in rule
	 * @param  array $extra_info
	 * @param  boolean $dry_run
	 * @return  array ['success'=> success, 'message'=> errorMessageLang, 'continue_process_after_script' => FALSE]
	 */
	protected function runBonusConditionChecker($description, &$extra_info, $dry_run){
		$success=false;
		$errorMessageLang=null;

		// check today apply promo or not
        $isReleasedBonusToday = $this->callHelper('get_last_released_player_promo', [$this->promorulesId, self::DATE_TYPE_TODAY]);
        $this->appendToDebugLog('get_last_released_player_promo', ['promorulesId'=>$this->promorulesId, 'isReleasedBonusToday'=>$isReleasedBonusToday]);

        // check deposit and date met condition or not
        list($met_condition) = $this->checkDepositAndDate($description);

        if(!$isReleasedBonusToday){
            if($met_condition){
                $success = true;
            }else{
                $errorMessageLang = 'promo_custom.deposit_donot_match_the_requirement';
            }
        }else{
            $errorMessageLang = 'notify.83';
        }

        $result=['success'=>$success, 'message'=>$errorMessageLang, 'continue_process_after_script' => FALSE];

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
        $bonus_amount = 0;

        // check today apply promo or not
        $isReleasedBonusToday = $this->callHelper('get_last_released_player_promo', [$this->promorulesId, self::DATE_TYPE_TODAY]);
        $this->appendToDebugLog('get_last_released_player_promo', ['promorulesId'=>$this->promorulesId, 'isReleasedBonusToday'=>$isReleasedBonusToday]);

        // check deposit and date met condition or not
        list($met_condition, $bonusAmount) = $this->checkDepositAndDate($description);

        if(!$isReleasedBonusToday){
            if($met_condition){
                $bonus_amount = $bonusAmount;
                $success = true;
            }else{
                $errorMessageLang = 'promo_custom.deposit_donot_match_the_requirement';
            }
        }else{
            $errorMessageLang = 'notify.83';
        }

        $result=['success'=>$success, 'message'=>$errorMessageLang, 'bonus_amount'=>$bonus_amount];
        return $result;
    }
}
