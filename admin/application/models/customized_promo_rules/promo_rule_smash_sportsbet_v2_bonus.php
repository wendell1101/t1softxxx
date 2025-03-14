<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 *
 * OGP-26577
 * 每日只能申請一次
 * 規則：
 * 1、玩家前一天体育投注金额大于20BRL则获得2BRL奖金。
 * 2、玩家前一天体育投注金额大于50BRL则获得5BRL奖金。
 * 3、玩家前一天体育投注金额大于100BRL则获得10BRL奖金。
 * 4、玩家前一天体育投注金额大于1000BRL则获得100BRL奖金。
 *
 * 提款条件：投注金额=奖金×5
 *
condition:
{
    "class": "promo_rule_smash_sportsbet_v2_bonus",
    "allowed_date": {
        "start": "",
        "end": ""
    },
    "release_date": {
        "start": "",
        "end": ""
    },
    "application_day" : "2022-07-28",
    "betConditionTimes" : 5,
    "game_type" :[662],
    "bonus_settings": [
		{"min_bet": 20, "max_bet": 50, "bonus": 2},
		{"min_bet": 50, "max_bet": 100, "bonus": 5},
		{"min_bet": 100, "max_bet": 1000, "bonus": 10},
		{"min_bet": 1000, "max_bet": 999999999999999, "bonus": 100}
    ]
}
*
*
*/
class Promo_rule_smash_sportsbet_v2_bonus extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'promo_rule_smash_sportsbet_v2_bonus';
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
		$bonus_settings = $description['bonus_settings'];
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
		$success = false;
        $errorMessageLang = null;
        $withdrawal_condition_amount = 0;

        $result = $this->releaseBonus($description, $extra_info, $dry_run);

        $times = $description['betConditionTimes'];
        $bonus_amount = $result['bonus_amount'];
        $this->appendToDebugLog('get bonus_amount and times', ['bonus_amount'=>$bonus_amount, 'times'=>$times]);

        if($times > 0){
            $withdrawal_condition_amount = $bonus_amount * $times;
            $success = $withdrawal_condition_amount > 0;
        }else{
            $errorMessageLang='Lost bet_condition_times in settings';
        }

        $result=['success'=>$success, 'message'=>$errorMessageLang, 'withdrawal_condition_amount'=>round($withdrawal_condition_amount, 2)];
        return $result;
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
		$bonus_settings = $description['bonus_settings'];
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
	    $errorMessageLang =  lang('notify.81');

	    #check Released Bonus
	    $checkReleasedBonus = $this->callHelper('get_last_released_player_promo',[$promoRuleId,self::DATE_TYPE_TODAY]);

	    if(!empty($release_date['start']) && !empty($release_date['end'])){
			$checkReleasedBonus = $this->callHelper('get_last_released_player_promo',[$promoRuleId,self::DATE_TYPE_CUSTOMIZE,$release_date]);
	    }

	    $totalBet = $this->callHelper('getPlayerBetByDate', [$fromDate, $toDate, null, $game_type]);

	    $this->appendToDebugLog('checkCustomizeBonusCondition get_last_released_player_promo',
	    	['release_date' => $release_date, 'totalBet' => $totalBet, 'checkReleasedBonus' => $checkReleasedBonus]);

        if(empty($totalBet)){
            $errorMessageLang = 'Player Do Not Have Bet in Sports Game Type';
            $this->appendToDebugLog('checkCustomizeBonusCondition: Player Do Not Have Bet in Sports Game Type Between ' . $fromDate . ' AND ' . $toDate, ['total bet' => $totalBet]);
            return ['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonus_amount];
        }

	    if($checkReleasedBonus){
	    	$success=false;
			$errorMessageLang =  lang('notify.83');
	    	return $result = ['success' => $success, 'message' => $errorMessageLang, 'continue_process_after_script' => FALSE];
	    }else{
	    	if (!empty($bonus_settings)) {
				if(is_array($bonus_settings)){
	                foreach ($bonus_settings as $list) {
	                    if($totalBet > $list['min_bet'] && $totalBet <= $list['max_bet']){
	                        $success = true;
	                        $bonus_amount = $list['bonus'];
	                    } else {
	                        continue;
	                    }
	                }
	            }
				$this->appendToDebugLog('check bets amount release Bonus success', ['success' => $success,'playerId' => $this->playerId, 'fromDate' => $fromDate, 'toDate' => $toDate, 'bonus_settings' => $bonus_settings,'bonus_amount' => $bonus_amount]);
			} else {
				$errorMessageLang = 'Not exist bet Setting';
				$this->appendToDebugLog('Not exist bet Setting',['bonus_settings' => $bonus_settings]);
			}
	    }
		return $result=['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonus_amount];
	}
}
