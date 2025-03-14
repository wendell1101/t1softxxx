<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 *
 * OGP-26578
 * 每日只能申請一次
 * 規則：
 * 当日净输超过50BRL返还 3%！最高返还10,000BRL ！
 *
condition:
{
    "class": "promo_rule_smash_sportsresurrection_bonus",
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
class Promo_rule_smash_sportsresurrection_bonus extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'promo_rule_smash_sportsresurrection_bonus';
	}

	/**
	 * run bonus condition checker
	 * @param  array $description original description in rule
	 * @param  array $extra_info
	 * @param  boolean $dry_run
	 * @return  array ['success'=> success, 'message'=> errorMessageLang]
	 */
	protected function runBonusConditionChecker($description, &$extra_info, $dry_run){
		$success = false;
		$errorMessageLang = null;
		$allowed_date = $description['allowed_date'];
		$bonus_settings = null;
		$application_day = $description['application_day'];
		$fromDate = !empty($allowed_date['start']) ? $allowed_date['start'] : $this->get_date_type(self::DATE_YESTERDAY_START);
		$toDate = !empty($allowed_date['end']) ? $allowed_date['end'] : $this->get_date_type(self::DATE_YESTERDAY_END);
		$today = $this->utils->getTodayForMysql();

		if($this->process_mock('today', $today)){
			//use mock data
			$this->appendToDebugLog('use mock today', ['today'=>$today]);
		}

		$this->appendToDebugLog('runBonusConditionChecker check date', ['fromDate'=>$fromDate, 'toDate'=>$toDate, 'today'=>$today]);

		if (strtotime($today) <= strtotime($application_day)) {
			$success = false;
			$errorMessageLang =  lang('notify.78');
			return $result = ['success' => $success, 'message' => $errorMessageLang, 'continue_process_after_script' => FALSE];
		}

		$result = $this->checkCustomizeBonusCondition($bonus_settings, $fromDate, $toDate, $extra_info, $description, $errorMessageLang);

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
		$success = false;
		$errorMessageLang = null;
		$allowed_date = $description['allowed_date'];
		$bonus_settings = null;
		$application_day = $description['application_day'];
		$fromDate = !empty($allowed_date['start']) ? $allowed_date['start'] : $this->get_date_type(self::DATE_YESTERDAY_START);
		$toDate = !empty($allowed_date['end']) ? $allowed_date['end'] : $this->get_date_type(self::DATE_YESTERDAY_END);
		$today = $this->utils->getTodayForMysql();

		if (strtotime($today) <= strtotime($application_day)) {
			$success = false;
			$errorMessageLang =  lang('notify.78');
			return $result = ['success' => $success, 'message' => $errorMessageLang, 'continue_process_after_script' => FALSE];
		}

        if($this->process_mock('today', $today)){
            //use mock data
            $this->appendToDebugLog('use mock today', ['today'=>$today]);
        }

		$request = $this->checkCustomizeBonusCondition($bonus_settings, $fromDate, $toDate, $extra_info, $description, $errorMessageLang);

        return $request;
	}

	private function checkCustomizeBonusCondition($bonus_settings, $fromDate, $toDate, &$extra_info, $description, &$errorMessageLang){
        $success = false;
	    $bonus_amount = 0;
	    $currentVipLevelId = $this->levelId;
	    $promorule = $this->promorule;
	    $promoRuleId = $promorule['promorulesId'];
	    $release_date = $description['release_date'];
	    $game_type = $description['game_type'];
	    $daily_netloss = $description['daily_netloss'];
	    $percentage = $description['percentage'];
	    $errorMessageLang =  lang('notify.79');

	    #check Released Bonus
	    $checkReleasedBonus = $this->callHelper('get_last_released_player_promo',[$promoRuleId,self::DATE_TYPE_TODAY]);

	    if(!empty($release_date['start']) && !empty($release_date['end'])){
			$checkReleasedBonus = $this->callHelper('get_last_released_player_promo',[$promoRuleId,self::DATE_TYPE_CUSTOMIZE,$release_date]);
	    }

	    #get game log win / loss
        $playerTotalBetWinLoss = $this->callHelper('getPlayerTotalBetWinLoss',[$fromDate, $toDate, 'total_player_game_day', 'date', null, $game_type]
        );

        $this->appendToDebugLog('getPlayerTotalBetWinLoss: ', $playerTotalBetWinLoss);
        $totalWin = $playerTotalBetWinLoss['total_win'];
        $totalLoss = $playerTotalBetWinLoss['total_loss'];
        $netloss = abs($totalWin - $totalLoss);

	    $this->appendToDebugLog('checkCustomizeBonusCondition get_last_released_player_promo',
	    	['release_date' => $release_date, 'playerTotalBetWinLoss' => $playerTotalBetWinLoss, 'checkReleasedBonus' => $checkReleasedBonus, 'netloss' => $netloss]);

        if(empty($playerTotalBetWinLoss['total_bet'])){
            $errorMessageLang = 'Player Do Not Have Bet in Sports Game Type';
            $this->appendToDebugLog('checkCustomizeBonusCondition: Player Do Not Have Bet in Sports Game Type Between ' . $fromDate . ' AND ' . $toDate, ['playerTotalBetWinLoss' => $playerTotalBetWinLoss]);
            return ['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonus_amount];
        }

	    if($checkReleasedBonus){
	    	$success=false;
			$errorMessageLang =  lang('notify.83');
	    	return $result = ['success' => $success, 'message' => $errorMessageLang, 'continue_process_after_script' => FALSE];
	    }else{
	    	if (!empty($daily_netloss)) {
                if($netloss >= $daily_netloss){
                    $success = true;
                    $bonus_amount = $netloss * ($percentage / 100);
                    $bonus_amount = $bonus_amount > $description['max_bonus'] ? $description['max_bonus'] : $bonus_amount;
					$this->appendToDebugLog('check bets amount release Bonus success', ['success' => $success,'playerId' => $this->playerId, 'fromDate' => $fromDate, 'toDate' => $toDate, 'bonus_settings' => $bonus_settings,'bonus_amount' => $bonus_amount]);

                }else{
                	$errorMessageLang = "The player's netloss on the day does not exceed ". $daily_netloss ."BRL";
            		$this->appendToDebugLog($errorMessageLang ,['netloss' => $netloss, 'daily_netloss' => $daily_netloss]);
            		return ['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonus_amount];
                }
			} else {
				$errorMessageLang = 'Not exist bet Setting';
				$this->appendToDebugLog('Not exist bet Setting',['bonus_settings' => $bonus_settings]);
			}
	    }
		return $result=['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $this->utils->roundCurrencyForShow($bonus_amount)];
	}
}
