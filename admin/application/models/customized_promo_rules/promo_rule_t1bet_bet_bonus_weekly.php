<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 * OGP-28901
 * 每周可申請一次
 * 計算前一周 game_type 總投注
 * 3倍取款條件
 *
condition:
{
    "class": "promo_rule_t1bet_bet_bonus_weekly",
    "bet_from_date": "2022-07-01",
    "bet_to_date": "2022-07-01",
    "game_type": [1,2,3],
    "bonus_settings": [
        {"min_bet": 10000, "max_bet": 30000, "bonus_amount": 18},
        {"min_bet": 30000, "max_bet": 100000, "bonus_amount": 28},
        {"min_bet": 100000, "max_bet": 300000, "bonus_amount": 58},
        {"min_bet": 300000, "max_bet": 500000, "bonus_amount": 188},
        {"min_bet": 500000, "max_bet": 1000000, "bonus_amount": 388},
        {"min_bet": 1000000, "max_bet": 3000000, "bonus_amount": 888},
        {"min_bet": 3000000, "max_bet": 5000000, "bonus_amount": 1888},
        {"min_bet": 5000000, "max_bet": 10000000, "bonus_amount": 3888},
        {"min_bet": 10000000, "max_bet": 30000000, "bonus_amount": 5888},
        {"min_bet": 30000000, "max_bet": -1, "bonus_amount": 8888}
    ]
}

 *
 *
 */
class Promo_rule_t1bet_bet_bonus_weekly extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'promo_rule_t1bet_bet_bonus_weekly';
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

        $game_type = $description['game_type'];
        $bonus_settings = $description['bonus_settings'];

        if(empty($game_type)){
            $errorMessageLang = 'promo_rule.common.error';
            $this->appendToDebugLog('Invalid Game Type Settings');
            return ['success' => $success, 'message' => $errorMessageLang];
        }

        // check bet record
        $fromDate = !empty($description['bet_from_date']) ? date('Y-m-d 00:00:00', strtotime($description['bet_from_date'])) : $this->callHelper('get_date_type', [self::DATE_LAST_WEEK_START]);
        $toDate = !empty($description['bet_to_date']) ? date('Y-m-d 23:59:59', strtotime($description['bet_to_date'])) : $this->callHelper('get_date_type', [self::DATE_LAST_WEEK_END]);
        $totalBet = $this->callHelper('getPlayerBetByDate', [$fromDate, $toDate, null, $game_type]);
        $this->appendToDebugLog('check bet condition', ['start date' => $fromDate, 'to date' => $toDate, 'total bet' => $totalBet]);

        if(empty($totalBet)){
            $errorMessageLang = 'promo.total_bet_not_met';
            return ['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonus_amount];
        }

        if(!empty($bonus_settings)){
            foreach ($bonus_settings as $setting){
                if($setting['min_bet']<=$totalBet &&
                    //max_bet<0 means no limit
                    ($totalBet<$setting['max_bet'] || $setting['max_bet']<0)){
                    $success = true;
                    $bonus_amount = $setting['bonus_amount'];
                }else{
                    $errorMessageLang = 'promo.total_bet_not_met';
                }
            }
        }

        return ['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonus_amount, 'continue_process_after_script' => FALSE];
        
    }
}
