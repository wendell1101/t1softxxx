<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 *
 * Sports betting bonus-Bônus de apostas esportivas
 *
 * OGP-27136
 *
 * 1、Sports betting amount over(>=) 20 BRL yesterday, will receive 2 BRL.
 * 2、Sports betting amount over(>=) 50 BRL yesterday, will receive 5 BRL.
 * 3、Sports betting amount over(>=) 100 BRL yesterday, will receive 10 BRL.
 * 4、Sports betting amount over(>=) 1000 BRL yesterday, will receive 100 BRL.
 *
 * Bet Amount = Bonus x 5
 *
 *
condition:
{
    "class": "promo_rule_sambabet_sportsbet_bonus",
    "game_type": [],
    "bet_from": "2022-11-01",
    "bet_to": "2022-11-01",
    "bonus_settings": [
        {"min_bet": 20, "max_bet": 50, "bonus_amount": 2},
        {"min_bet": 50, "max_bet": 100, "bonus_amount": 5},
        {"min_bet": 100, "max_bet": 1000, "bonus_amount": 10},
        {"min_bet": 1000, "max_bet": 999999999, "bonus_amount": 100}
    ]
}

 *
 *
 */
class Promo_rule_sambabet_sportsbet_bonus extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_sambabet_sportsbet_bonus';
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
        $game_type = $description['game_type'];
        $bonus_settings = $description['bonus_settings'];

        #check game type bet
        $fromDate = $this->callHelper('get_date_type', [self::DATE_YESTERDAY_START]);
        $toDate = $this->callHelper('get_date_type', [self::DATE_YESTERDAY_END]);
        if(!empty($description['bet_from']) && !empty($description['bet_to'])){
            $fromDate = date('Y-m-d 00:00:00', strtotime($description['bet_from']));
            $toDate = date('Y-m-d 23:59:59', strtotime($description['bet_to']));
        }

        if(empty($game_type)){
            $errorMessageLang = 'promo_rule.common.error';
            return ['success' => $success, 'message' => $errorMessageLang];
        }

        $totalBet = $this->callHelper('getPlayerBetByDate', [$fromDate, $toDate, null, $game_type]);
        $this->appendToDebugLog('player bet by date between ' . $fromDate . ' AND ' . $toDate, ['total bet' => $totalBet]);
        if(empty($totalBet)){
            $errorMessageLang = 'promo.total_bet_not_met';
            return ['success' => $success, 'message' => $errorMessageLang];
        }

        if (!empty($bonus_settings)) {
            foreach ($bonus_settings as $list) {
                if($totalBet >= $list['min_bet'] && $totalBet < $list['max_bet']){
                    $success = true;
                    $bonus_amount = $list['bonus_amount'];
                }
            }
        }

        if(!$success){
            $errorMessageLang = 'promo.total_bet_not_met';
            return ['success' => $success, 'message' => $errorMessageLang];
        }

        return ['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonus_amount];
	}

}
