<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 * OGP-25984
 * 一週發一次獎金
 * 計算上週 一到日 [(總投注/1000)取整數]*5
 * 採用累積發放 發放滿500,之後就不會再發了
 * 無取款條件
 * 排程申請優惠
 *
condition:
{
    "class": "promo_rule_smash_sportsbet_bonus",
    "accumulate_bet": 1000,
    "thousands_bonus": 5,
    "max_bonus": 500
}

 * $config['auto_apply_and_release_bonus_for_smash_sportsbet_promocms_id'] = ['92'];
 * $config['promo_rule_smash_sportsbet_bonus_allow_game_type'] = ['1113', '1138'];
 *
 *
 */
class Promo_rule_smash_sportsbet_bonus extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'promo_rule_smash_sportsbet_bonus';
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

        $thousands_bonus = $description['thousands_bonus'];
        $max_bonus = $description['max_bonus'];
        $game_type = $this->utils->getConfig('promo_rule_smash_sportsbet_bonus_allow_game_type');
        $accumulate_bet = $description['accumulate_bet'];

		$fromDate = !empty($description['from_date']) ? date('Y-m-d 00:00:00', strtotime($description['from_date'])) : $this->callHelper('get_date_type', [self::DATE_YESTERDAY_START]);
		$toDate = !empty($description['to_date']) ? date('Y-m-d 23:59:59', strtotime($description['to_date'])) : $this->callHelper('get_date_type', [self::DATE_YESTERDAY_END]);
		$today = $this->utils->getTodayForMysql();

		if($this->process_mock('today', $today)){
			//use mock data
			$this->appendToDebugLog('use mock today', ['today'=>$today]);
		}

		$this->appendToDebugLog('runBonusConditionChecker check date', ['fromDate'=>$fromDate, 'toDate'=>$toDate, 'today'=>$today]);

		$result = $this->checkCustomizeBonusCondition($game_type, $thousands_bonus, $max_bonus, $accumulate_bet, $fromDate, $toDate, $errorMessageLang);

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

        $thousands_bonus = $description['thousands_bonus'];
        $max_bonus = $description['max_bonus'];
        $game_type = $this->utils->getConfig('promo_rule_smash_sportsbet_bonus_allow_game_type');
        $accumulate_bet = $description['accumulate_bet'];

        $fromDate = !empty($description['from_date']) ? date('Y-m-d 00:00:00', strtotime($description['from_date'])) : $this->callHelper('get_date_type', [self::DATE_YESTERDAY_START]);
        $toDate = !empty($description['to_date']) ? date('Y-m-d 23:59:59', strtotime($description['to_date'])) : $this->callHelper('get_date_type', [self::DATE_YESTERDAY_END]);
        $today = $this->utils->getTodayForMysql();

        if($this->process_mock('today', $today)){
            //use mock data
            $this->appendToDebugLog('use mock today', ['today'=>$today]);
        }

        $this->appendToDebugLog('runBonusConditionChecker check date', ['fromDate'=>$fromDate, 'toDate'=>$toDate, 'today'=>$today]);

        $result = $this->checkCustomizeBonusCondition($game_type, $thousands_bonus, $max_bonus, $accumulate_bet, $fromDate, $toDate, $errorMessageLang);

        return $result;
	}

    private function checkCustomizeBonusCondition($game_type, $thousands_bonus, $max_bonus, $accumulate_bet, $fromDate, $toDate, &$errorMessageLang){
        $success = false;
	    $bonus_amount = 0;

        $applyRecord = $this->callHelper('get_all_released_player_promo', [$this->promorulesId, null]);
        $total_release_bonus = 0;

        if(!empty($applyRecord)){
            foreach ($applyRecord as $record){
                $total_release_bonus += $record['bonusAmount'];
            }
        }
        $this->appendToDebugLog('checkCustomizeBonusCondition: accumuate bonus.', ['player_id' => $this->playerId, 'total bonus amount now' => $total_release_bonus]);

        if($total_release_bonus == $max_bonus){
            $errorMessageLang = 'Player Already Got Max Bonus (Accumulation)';
            $this->appendToDebugLog('checkCustomizeBonusCondition: bonus amount already reach max bonus.', ['player already get bonus amount' => $total_release_bonus]);
            return ['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonus_amount];
        }

        $totalBet = $this->callHelper('getPlayerBetByDate', [$fromDate, $toDate, null, $game_type]);

        if(empty($totalBet)){
            $errorMessageLang = 'Player Do Not Have Bet in Sports Game Type';
            $this->appendToDebugLog('checkCustomizeBonusCondition: Player Do Not Have Bet in Sports Game Type Between ' . $fromDate . ' AND ' . $toDate, ['total bet' => $totalBet]);
            return ['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonus_amount];
        }

        $bonus_base = floor($totalBet / $accumulate_bet);
        if(empty($bonus_base)){
            $errorMessageLang = 'Bet in Sports Game Type Not Met Bonus Base ' . $accumulate_bet;
            $this->appendToDebugLog('checkCustomizeBonusCondition: Bet in Sports Game Type Not Met Bonus Base.', ['total bet' => $totalBet]);
            return ['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonus_amount];
        }

        $expect_bonus_amount = ($bonus_base * $thousands_bonus);
        if($expect_bonus_amount >= $max_bonus){
            $expect_bonus_amount = $max_bonus;
        }

        $bonus_amount = $expect_bonus_amount - $total_release_bonus;
        if(empty($bonus_amount) || ($bonus_amount < 0)){
            $errorMessageLang = 'Bet in Sports Game Type Not Met Bonus Base ' . $accumulate_bet . ' current accumulate bet ' . $totalBet;
            $this->appendToDebugLog('checkCustomizeBonusCondition: Bonus Amount Not Valid.', [
                'bonus amount' => $bonus_amount, 'expect bonus amount' => $expect_bonus_amount, 'total release bonus' => $total_release_bonus
            ]);
            return ['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonus_amount];
        }

        $success = true;

        $this->appendToDebugLog('checkCustomizeBonusCondition: check player total bet.', [
            'bet' => $totalBet, 'bonus base' => $bonus_base,
            'expect bonus amount' => $expect_bonus_amount, 'bonus amount' => $bonus_amount
        ]);

        return ['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonus_amount];
        
    }
}
