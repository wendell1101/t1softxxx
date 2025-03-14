<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 *
 * Daily Challenge!-Sambabet Desafio Diário
 *
 * OGP-27143
 *
 * 每日皆可申请，檢查當日投注 / 存款 / 净利(贏-輸) 是否符合条件，
 * 不分順序看完成幾個任務就發放對應的獎金
 * 每日可申請一次
 *
 * The content of 3 tasks:
 * 1. Daily bet amount ≥ 2000 BRL
 * 2. Daily deposit amount ≥ 300 BRL
 * 3. Player's daily profit amount (Win - Lose) ≥ 300 BRL
 *
 * 無取款條件
 *
 *
condition:
{
    "class": "promo_rule_sambabet_bets_deposit_daily_bonus",
    "bet_amount": 2000,
    "deposit_amount": 300,
    "profit_amount": 300,
    "bonus_settings": [
        {"finished_task": 1, "bonus_amount": 5},
        {"finished_task": 2, "bonus_amount": 13},
        {"finished_task": 3, "bonus_amount": 35}
    ]
}

 *
 *
 */
class Promo_rule_sambabet_bets_deposit_daily_bonus extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_sambabet_bets_deposit_daily_bonus';
	}

	/**
	 * run bonus condition checker
	 * @param  array $description original description in rule
	 * @param  array $extra_info
	 * @param  boolean $dry_run
	 * @return  array ['success'=> success, 'message'=> errorMessageLang]
	 */
	protected function runBonusConditionChecker($description, &$extra_info, $dry_run){
		$errorMessageLang=null;
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
		$errorMessageLang=null;
        $result = $this->checkCustomizeBounsCondition($description, $extra_info, $errorMessageLang);

		return $result;
	}

	private function checkCustomizeBounsCondition($description, &$extra_info, &$errorMessageLang){
	    $success = false;
        $bonus_amount = 0;
        $finished_task_cnt = 0;

        $required_bet_amount = $description['bet_amount'];
        $required_deposit_amount = $description['deposit_amount'];
        $required_profit_amount = $description['profit_amount'];
        $bonus_settings = $description['bonus_settings'];

        $fromDate = $this->callHelper('get_date_type', [self::DATE_TODAY_START]);
        $toDate = $this->callHelper('get_date_type', [self::DATE_TODAY_END]);
        $this->appendToDebugLog('Date Ragne', ['from Date' => $fromDate, 'to Date' => $toDate]);

        $total_deposit = $this->callHelper('sum_deposit_amount', [$fromDate, $toDate, 0]);

        $playerGameTotal = $this->callHelper('getPlayerTotalBetWinLoss', [$fromDate, $toDate]);
        $total_bet = $playerGameTotal['total_bet'];
        $total_win = $playerGameTotal['total_win'];
        $total_loss = $playerGameTotal['total_loss'];
        $total_profit_amount = $total_win - $total_loss;

        $met_deposit_condition = $total_deposit >= $required_deposit_amount;
        if($met_deposit_condition){
            $finished_task_cnt+=1;
        }

        $met_bet_condition = $total_bet >= $required_bet_amount;
        if($met_bet_condition){
            $finished_task_cnt+=1;
        }

        $met_profit_condition = $total_profit_amount >= $required_profit_amount;
        if($met_profit_condition){
            $finished_task_cnt+=1;
        }

        $this->appendToDebugLog('met deposit task', [
            'player Game Total' => $playerGameTotal,
            'met deposit condition' => $met_deposit_condition, 'total_deposit' => $total_deposit, 'required_deposit_amount' => $required_deposit_amount,
            'met bet condition' => $met_bet_condition, 'total_bet' => $total_bet, 'required_bet_amount' => $required_bet_amount,
            'met profit condition' => $met_profit_condition, 'total_profit' => $total_profit_amount, 'required_profit_amount' => $required_profit_amount
        ]);

        if(!empty($finished_task_cnt)){
            if(!empty($bonus_settings)){
                foreach ($bonus_settings as $setting){
                    if($finished_task_cnt == $setting['finished_task']){
                        $success = true;
                        $bonus_amount = $setting['bonus_amount'];
                    }
                }
            }
        }else{
            $errorMessageLang = 'promo.dont_allow_not_within_claim_time';
        }

        return ['success'=>$success, 'message'=>$errorMessageLang, 'bonus_amount' => $bonus_amount, 'continue_process_after_script' => FALSE];
	}

}
