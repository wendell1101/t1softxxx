<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 * OGP-29317
 * 每月可申請一次
 * 計算前一周 game_type 總投注
 * 3倍取款條件
 *
condition:
{
    "class": "promo_rule_t1bet_bet_deposit_bonus_monthly",
    "allow_day": "01",
    "game_type": [16],
    "bonus_settings": [
        {"min_bet": 10000, "max_bet": 20000, "bonus_amount": 188},
        {"min_bet": 20000, "max_bet": -1, "bonus_amount": 388}
    ]
}

{
    "class": "promo_rule_t1bet_bet_deposit_bonus_monthly",
    "allow_day": "11",
    "game_type": [16],
    "min_deposit": 1999,
    "bonus_settings": [
        {"min_bet": 19990, "max_bet": -1, "bonus_amount": 588}
    ]
}

 *
 *
 *
 *
 */
class Promo_rule_t1bet_bet_deposit_bonus_monthly extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'promo_rule_t1bet_bet_deposit_bonus_monthly';
	}

	/**
	 * run bonus condition checker
	 * @param  array $description original description in rule
	 * @param  array $extra_info
	 * @param  boolean $dry_run
	 * @return  array ['success'=> success, 'message'=> errorMessageLang, 'continue_process_after_script' => FALSE]
	 */
    protected function runBonusConditionChecker($description, &$extra_info, $dry_run){
		$errorMessageLang = null;

		$result = $this->checkCustomizeBonusCondition($description, $errorMessageLang);

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
        $result = $this->checkCustomizeBonusCondition($description, $errorMessageLang);

        return $result;
	}

    private function checkCustomizeBonusCondition($description, &$errorMessageLang){
        $success = false;
	    $bonus_amount = 0;
        $bonus_settings = $description['bonus_settings'];

        $today = $this->utils->getTodayForMysql();
        if($this->process_mock('today', $today)){
            //use mock data
            $this->appendToDebugLog('use mock today', ['today'=>$today]);
        }

        $d = new DateTime($today);
        $currentDate = $d->format('Y-m-d');
        $minDate = $maxDate = $d->format('Y-m-').$description['allow_day'];
        $fromDate = date('Y-m-d 00:00:00', strtotime($minDate));
        $toDate = date('Y-m-d 23:59:59', strtotime($maxDate));
        $this->appendToDebugLog('check date', ['today'=>$currentDate, 'minDate'=>$minDate, 'maxDate'=>$maxDate]);

        if($currentDate<$minDate || $currentDate>$maxDate) {
            $errorMessageLang='Not right date';
            return ['success' => $success, 'message' => $errorMessageLang];
        }

        // deposit record
        if(!empty($description['min_deposit'])){
            $min_deposit = $description['min_deposit'];
            $toal_deposit = $this->callHelper('sum_deposit_amount',[$fromDate, $toDate, 0]);
            $toal_deposit = empty($toal_deposit) ? 0 : $toal_deposit;
            $this->appendToDebugLog('check deposit', ['min deposit'=>$min_deposit, 'total deposit'=>$toal_deposit]);

            if($toal_deposit < $min_deposit){
                $errorMessageLang = 'promo_custom.deposit_sum_insufficient';
                return ['success' => $success, 'message' => $errorMessageLang];
            }
        }

        $game_type = isset($description['game_type']) ? $description['game_type'] : null;
        // if(empty($game_type)){
        //     $errorMessageLang = 'promo_rule.common.error';
        //     return ['success' => $success, 'message' => $errorMessageLang];
        // }

        // bet record
        $total_bet = $this->callHelper('getPlayerBetByDate', [$fromDate, $toDate, null, $game_type]);
        $this->appendToDebugLog('check bet', ['total bet'=>$total_bet]);

        foreach ($bonus_settings as $setting){
            if($setting['min_bet']<=$total_bet &&
                ($total_bet<$setting['max_bet'] || $setting['max_bet']<0)){ //max_lossing<0 means no limit
                $success = true;
                $bonus_amount = $setting['bonus_amount'];
            }else{
                $errorMessageLang = 'notify.81';
            }
        }

        return ['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonus_amount, 'continue_process_after_script' => FALSE];
        
    }
}
