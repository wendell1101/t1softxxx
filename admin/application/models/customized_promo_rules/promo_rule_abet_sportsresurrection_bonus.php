<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 *
 * OGP-28596
 * 每日只能申請一次
 * 規則：
 * 当日净输超过50BRL返还 3%！最高返还10,000BRL ！
 *
condition:
{
    "class": "promo_rule_abet_sportsresurrection_bonus",
    "allowed_date": {
        "start": "",
        "end": ""
    },
    "release_date": {
        "start": "",
        "end": ""
    },
    "application_day" : "2022-07-28",
    "daily_netloss" : 50,
    "max_bonus" : 10000,
    "percentage" : 3,
    "game_type" :[662]
}
*
*
*/
class Promo_rule_abet_sportsresurrection_bonus extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'promo_rule_abet_sportsresurrection_bonus';
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

	private function checkCustomizeBonusCondition($description, &$extra_info, &$errorMessageLang){
        $success = false;
	    $bonus_amount = 0;
	    $game_type = $description['game_type'];
	    $daily_netloss = $description['daily_netloss'];
	    $percentage = $description['percentage'];
        $today = $this->utils->getTodayForMysql();

	    $fromDate = $this->get_date_type(self::DATE_YESTERDAY_START);
        $toDate = $this->get_date_type(self::DATE_YESTERDAY_END);
        if(!empty($description['allowed_date']['start']) && !empty($description['allowed_date']['end'])){
            $fromDate = $description['allowed_date']['start'];
            $toDate = $description['allowed_date']['end'];
        }
        $this->appendToDebugLog('checkCustomizeBonusCondition check date', ['fromDate' => $fromDate, 'toDate' => $toDate, 'today' => $today]);

        #check valid appliction day
        if(!empty($description['application_day'])){
            $application_day = $description['application_day'];
            if (strtotime($today) <= strtotime($application_day)) {
                $errorMessageLang =  lang('notify.78');
                return ['success' => $success, 'message' => $errorMessageLang];
            }
        }

	    #check Released Bonus
        $promoRuleId = $this->promorulesId;
	    $checkReleasedBonus = $this->callHelper('get_last_released_player_promo',[$promoRuleId, self::DATE_TYPE_TODAY]);
        if(!empty($description['release_date']['start']) && !empty($description['release_date']['end'])){
            $release_date['start'] = $description['release_date']['start'];
            $release_date['end'] = $description['release_date']['end'];
			$checkReleasedBonus = $this->callHelper('get_last_released_player_promo', [$promoRuleId, self::DATE_TYPE_CUSTOMIZE, $release_date]);
        }
        $this->appendToDebugLog('checkCustomizeBonusCondition get last released player promo', ['checkReleasedBonus' => $checkReleasedBonus]);

        if(empty($game_type)){
            $errorMessageLang = 'promo_rule.common.error';
            return ['success' => $success, 'message' => $errorMessageLang];
        }

	    #get game log win / loss
        $playerTotalBetWinLoss = $this->callHelper('getPlayerTotalBetWinLoss',[$fromDate, $toDate, 'total_player_game_day', 'date', null, $game_type]);
        $totalWin = $playerTotalBetWinLoss['total_win'];
        $totalLoss = $playerTotalBetWinLoss['total_loss'];
        $netloss = abs($totalWin - $totalLoss);
	    $this->appendToDebugLog('checkCustomizeBonusCondition getPlayerTotalBetWinLoss', ['checkReleasedBonus' => $checkReleasedBonus, 'getPlayerTotalBetWinLoss', $playerTotalBetWinLoss, 'netloss' => $netloss]);

        if(empty($playerTotalBetWinLoss['total_bet'])){
            $errorMessageLang = 'Player Do Not Have Bet in Sports Game Type';
            return ['success' => $success, 'message' => $errorMessageLang];
        }

	    if($checkReleasedBonus){
			$errorMessageLang =  lang('notify.83');
	    	return ['success' => $success, 'message' => $errorMessageLang];
	    }else{
	    	if (!empty($daily_netloss)) {
                if($netloss >= $daily_netloss){
                    $success = true;
                    $bonus_amount = $netloss * ($percentage / 100);
                    $bonus_amount = $bonus_amount > $description['max_bonus'] ? $description['max_bonus'] : $bonus_amount;
                }else{
                	$errorMessageLang = "The player's net loss on the day does not exceed ". $daily_netloss ."BRL";
            		$this->appendToDebugLog($errorMessageLang ,['netloss' => $netloss, 'daily_netloss' => $daily_netloss]);
            		return ['success' => $success, 'message' => $errorMessageLang];
                }
			} else {
				$errorMessageLang = 'Not exist bet Setting';
				$this->appendToDebugLog('Not exist daily net loss Setting');
			}
	    }

		return ['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonus_amount];
	}
}
