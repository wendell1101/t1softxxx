<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 * OGP-26300
 * 每日可申請一次
 * 計算前一日 game_type 總投注
 * 當日存款有一筆超過100
 * 3倍取款條件
 *
condition:
{
    "class": "promo_rule_ole777idr_game_deposit_bonus",
    "game_type": [1,2,3],
    "min_deposit": 100,
    "bonus_settings": [
        {"min_bet": 5000, "max_bet": 7499, "bonus_amount": 25},
        {"min_bet": 7500, "max_bet": 15499, "bonus_amount": 50},
        {"min_bet": 15000, "max_bet": 999999999, "bonus_amount": 125}
    ],
    "bet_from_date": "2022-07-01",
    "bet_to_date": "2022-07-01"
}

 *
 *
 */
class Promo_rule_ole777idr_game_deposit_bonus extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'promo_rule_ole777idr_game_deposit_bonus';
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
        $min_deposit = $description['min_deposit'];
        $bonus_settings = $description['bonus_settings'];

        // check today deposit record
        $startDate = $this->callHelper('get_date_type', [self::DATE_TODAY_START]);
        $endDate = $this->callHelper('get_date_type', [self::DATE_TODAY_END]);
        $playerDepositByDate = $this->callHelper('getAnyDepositByDate', [$startDate, $endDate, -1, $min_deposit, PHP_INT_MAX]);
        $met_deposit = !is_null($playerDepositByDate) ? true : false;
        $this->appendToDebugLog('check deposit condition', ['start date' => $startDate, 'end date' => $endDate, 'met deposit condition' => $met_deposit]);

        if(!$met_deposit){
            $errorMessageLang = 'notify.43';
            return ['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonus_amount];
        }

        // check yesterday bet record
        $fromDate = !empty($description['bet_from_date']) ? date('Y-m-d 00:00:00', strtotime($description['bet_from_date'])) : $this->callHelper('get_date_type', [self::DATE_YESTERDAY_START]);
        $toDate = !empty($description['bet_to_date']) ? date('Y-m-d 23:59:59', strtotime($description['bet_to_date'])) : $this->callHelper('get_date_type', [self::DATE_YESTERDAY_END]);
        $totalBet = $this->callHelper('getPlayerBetByDate', [$fromDate, $toDate, null, $game_type]);
        $this->appendToDebugLog('check bet condition', ['start date' => $fromDate, 'to date' => $toDate, 'total bet' => $totalBet]);

        if(empty($totalBet)){
            $errorMessageLang = 'promo.total_bet_not_met';
            return ['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonus_amount];
        }

        if(!empty($bonus_settings)){
            foreach ($bonus_settings as $setting){
                if( ($totalBet >= $setting['min_bet']) && ($totalBet <= $setting['max_bet']) ){
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
