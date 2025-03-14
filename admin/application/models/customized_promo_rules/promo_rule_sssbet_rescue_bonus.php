<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 *
 * OGP-28806
 * 只能申請一次
 *
 *
 *
condition:
{
    "class": "promo_rule_sssbet_rescue_bonus",
    "allowed_date": {
        "start": "",
        "end": ""
    },
    "game_type" :[662],
    "min_lose": 500,
    "bonus_settings": [
        {"min_lossing": 500, "max_lossing": 5000, "bonus_amount": 15},
        {"min_lossing": 5000, "max_lossing": 10000, "bonus_amount": 55},
        {"min_lossing": 10000, "max_lossing": 50000, "bonus_amount": 135},
        {"min_lossing": 50000, "max_lossing": 100000, "bonus_amount": 355},
        {"min_lossing": 100000, "max_lossing": 500000, "bonus_amount": 555},
        {"min_lossing": 500000, "max_lossing": 1000000, "bonus_amount": 5555},
        {"min_lossing": 1000000, "max_lossing": -1, "bonus_amount": 15555}
    ]
}
*
*
*/
class Promo_rule_sssbet_rescue_bonus extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'promo_rule_sssbet_rescue_bonus';
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

	    $game_type = $description['game_type'];
        $bonus_settings = $description['bonus_settings'];
        $allowed_date = $description['allowed_date'];
        $min_lose = $description['min_lose'];

        $fromDate = !empty($allowed_date['start']) ? $allowed_date['start'] : $this->get_date_type(self::DATE_YESTERDAY_START);
        $toDate = !empty($allowed_date['end']) ? $allowed_date['end'] : $this->get_date_type(self::DATE_YESTERDAY_END);

        $today = $this->utils->getTodayForMysql();
        if($this->process_mock('today', $today)){
            //use mock data
            $this->appendToDebugLog('use mock today', ['today'=>$today]);
        }

        $this->appendToDebugLog('runBonusConditionChecker check date', ['fromDate'=>$fromDate, 'toDate'=>$toDate, 'today'=>$today]);

        if(empty($game_type)){
            $errorMessageLang = 'promo_rule.common.error';
            $this->appendToDebugLog('Invalid Game Type Settings');
            return ['success' => $success, 'message' => $errorMessageLang];
        }

	    #get game log win / loss
        $playerTotalBetWinLoss = $this->callHelper('getPlayerTotalBetWinLoss',[$fromDate, $toDate, 'total_player_game_day', 'date', null, $game_type]);
        $totalBet = $playerTotalBetWinLoss['total_bet'];
        $totalWin = $playerTotalBetWinLoss['total_win'];
        $totalLoss = $playerTotalBetWinLoss['total_loss'];

        if(empty($totalBet)){
            $errorMessageLang = 'promo_rule.common.error';
            $this->appendToDebugLog('Player Do Not Have Bet in Game Type');
            return ['success' => $success, 'message' => $errorMessageLang];
        }

        $gameRevenue = ($totalLoss - $totalWin);
        $allowApplyPromo = $gameRevenue >= $min_lose;

        $this->appendToDebugLog('getPlayerTotalBetWinLoss: ', [
            'playerTotalBetWinLoss' => $playerTotalBetWinLoss,
            'game revenue = loss - win' => $gameRevenue,
            'allow to apply promo' => $allowApplyPromo
        ]);

        if(!$allowApplyPromo){
            $errorMessageLang = 'promo_rule.common.error';
            $this->appendToDebugLog('(loss amount - win amount) not met');
            return ['success' => $success, 'message' => $errorMessageLang];
        }


        if(!empty($bonus_settings)){
            foreach ($bonus_settings as $setting){
                if($setting['min_lossing']<=$gameRevenue && ($gameRevenue<$setting['max_lossing'] || $setting['max_lossing']<0)){ //max_lossing<0 means no limit
                    $bonus_amount = $setting['bonus_amount'];
                    $success=true;
                    break;
                }
            }
        }else{
            $errorMessageLang = 'Not exist bonus Setting';
            $this->appendToDebugLog('Not exist Setting',['bonus_settings' => $bonus_settings]);
        }

		return ['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $this->utils->roundCurrencyForShow($bonus_amount)];
	}
}
