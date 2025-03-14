<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 *
 * daily battles
 *
 * OGP-25515
 *

 * Bonus condition && Bonus release:
    {
        "class": "promo_rule_smash_daily_battles_bonus",
        "condition": [
            {"daily_bet": 2000, "daily_deposit": 300, "daily_profit": 300}
        ],
        "bonus_settings":{
            "1": { "bonus": 5},
            "2": { "bonus": 13},
            "3": { "bonus": 35}
        },
        "cmsId" : 15
    }
 * QA Testing (can add specific info into custom script):
    {
        "fromDate": "1999-07-07 00:00:00",
        "toDate": "1999-07-07 23:59:59",
        "bet": 500,
        "deposit": 30000,
        "profit": 100
    }
 *
 *
 */
class Promo_rule_smash_daily_battles_bonus extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'promo_rule_smash_daily_battles_bonus';
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
        $promorule = $this->promorule;
        $promoRuleId = $promorule['promorulesId'];
        $bonus_settings=$description['bonus_settings'];
        $condition=$description['condition'];

        list($fromDate, $toDate) = $this->getCalculateDate($description);
        list($met_deposit, $met_bet, $met_profit, $completion, $msg_arr) = $this->checkPlayerDepositAndBetAndProfit($condition, $fromDate, $toDate, $description);

        $release_date['start'] = $fromDate;
        $release_date['end'] = $toDate;
        $checkReleasedBonus = $this->callHelper('get_last_released_player_promo',[$promoRuleId,self::DATE_TYPE_CUSTOMIZE,$release_date]);
        if($checkReleasedBonus){
            $success=false;
            $errorMessageLang =  lang('notify.83');
            return $result = ['success' => $success, 'message' => $errorMessageLang, 'continue_process_after_script' => FALSE];
        }

        if($completion > 0){
            $success = true;
            $bonus_amount = $bonus_settings[$completion]['bonus'];
        }else{
			$errorMessageLang='promo_rule.common.error';
		}

        $cmsId = !empty($description['cmsId']) ? $description['cmsId'] : false;
        $enabled_progression_btn = $this->utils->getConfig('enabled_progression_btn');

        if (isset($enabled_progression_btn[$cmsId]) && $dry_run) {
            $errorMessageLang = sprintf(lang($enabled_progression_btn[$cmsId]), $msg_arr['bet_res'], $msg_arr['deposit_res'], $msg_arr['profit_res'], $bonus_amount, $bonus_settings[3]['bonus']);
            $success = false;
        }

		$result=['success'=>$success, 'message'=>$errorMessageLang, 'continue_process_after_script' => FALSE];
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
		$success=false;
		$errorMessageLang=null;
		$bonus_amount=0;
        $promorule = $this->promorule;
        $promoRuleId = $promorule['promorulesId'];

		$bonus_settings=$description['bonus_settings'];
        $condition=$description['condition'];

        list($fromDate, $toDate) = $this->getCalculateDate($description);
        list($met_deposit, $met_bet, $met_profit, $completion, $msg_arr) = $this->checkPlayerDepositAndBetAndProfit($condition, $fromDate, $toDate, $description);

        $release_date['start'] = $fromDate;
        $release_date['end'] = $toDate;
        $checkReleasedBonus = $this->callHelper('get_last_released_player_promo',[$promoRuleId,self::DATE_TYPE_CUSTOMIZE,$release_date]);
        if($checkReleasedBonus){
            $success=false;
            $errorMessageLang =  lang('notify.83');
            return $result = ['success' => $success, 'message' => $errorMessageLang, 'continue_process_after_script' => FALSE];
        }

        if($completion > 0){
            $success = true;
            $bonus_amount = $bonus_settings[$completion]['bonus'];
        }else{
            $errorMessageLang='promo_rule.common.error';
        }

        $cmsId = !empty($description['cmsId']) ? $description['cmsId'] : false;
        $enabled_progression_btn = $this->utils->getConfig('enabled_progression_btn');

        if (isset($enabled_progression_btn[$cmsId]) && $dry_run) {
            $success = false;
            $errorMessageLang = sprintf(lang($enabled_progression_btn[$cmsId]), $msg_arr['bet_res'], $msg_arr['deposit_res'], $msg_arr['profit_res'], $bonus_amount, $bonus_settings[3]['bonus']);
        }

		$result=['success'=>$success, 'message'=>$errorMessageLang, 'bonus_amount'=>$bonus_amount];
		return $result;
	}

    protected function getCalculateDate($description){
        list($fromDate, $toDate) = $this->utils->getTodayStringRange();
        $fromDate = !empty($description['fromDate']) ? $description['fromDate'] : $fromDate;
        $toDate = !empty($description['toDate']) ? $description['toDate'] : $toDate;
        $this->appendToDebugLog('getTodayStringRange',['fromDate'=>$fromDate,'toDate'=>$toDate]);

        return [$fromDate, $toDate];
    }

	private function checkPlayerDepositAndBetAndProfit($condition, $fromDate, $toDate, $description){
        $met_deposit = false;
        $met_bet = false;
        $met_profit = false;
        $exist_condition = false;
        $completion = 0;
        $msg_arr = [];
        if(!empty($condition)){
            $exist_condition = true;
        }

        if($exist_condition){
            $setting = $condition[0];
            $min_deposit = !empty($setting['daily_deposit']) ? $setting['daily_deposit'] : 0;
            $min_bet = !empty($setting['daily_bet']) ? $setting['daily_bet'] : 0;
            $min_profit = !empty($setting['daily_profit']) ? $setting['daily_profit'] : 0;
            list($bet, $deposit)=$this->callHelper('getBetsAndDepositByDate', [$fromDate, $toDate]);
            list($totalBet, $totalResult, $totalWin, $totalLoss) = $this->callHelper('getTotalAmountFromHourlyReportByPlayerAndDateTime', [$this->playerId, $fromDate, $toDate]);
            $deposit = !empty($description['deposit']) ? $description['deposit'] : $deposit;
            $bet = !empty($description['bet']) ? $description['bet'] : $bet;
            $profit = !empty($description['profit']) ? $description['profit'] : $totalWin - $totalLoss;

            $this->appendToDebugLog('condition and getBetsAndDepositByDate and profit ',['condition'=>$setting,
             'min_deposit'=>$min_deposit, 'deposit'=>$deposit, 'min_bet'=>$min_bet,'bet'=>$bet, 'min_profit'=>$min_profit,'profit'=>$profit]);

            //only check deposit amount
            if($min_deposit > 0){
                $met_deposit = $deposit >= $min_deposit ? true :false;
                if ($met_deposit) {
                    $completion +=1;
                    $msg_arr['deposit_res'] = lang('Completed');
                }else{
                    $msg_arr['deposit_res'] = sprintf(lang('Remaining'),$min_deposit - $deposit) ;
                }
            }

            //only check bet amount
            if($min_bet > 0){
                $met_bet = $bet >= $min_bet ? true : false;
                if ($met_bet) {
                    $completion +=1;
                    $msg_arr['bet_res'] = lang('Completed');
                }else{
                    $msg_arr['bet_res']  = sprintf(lang('Remaining'),$min_bet - $bet);
                }
            }

            if($min_profit > 0){
                $met_profit = $profit >= $min_profit ? true : false;
                if ($met_profit) {
                    $completion +=1;
                    $msg_arr['profit_res'] = lang('Completed');
                }else{
                    if ($profit < 0) {
                        $msg_arr['profit_res']  = sprintf(lang('Remaining'),$min_profit);
                    } else {
                        $msg_arr['profit_res']  = sprintf(lang('Remaining'),$min_profit - $profit);
                    }
                }
            }

            $this->appendToDebugLog('met deposit and bet result', ['met_deposit' => $met_deposit, 'met_bet' => $met_bet,  'met_profit' => $met_profit, 'completion' => $completion, 'msg_arr' => $msg_arr]);
        }else{
            $this->appendToDebugLog('not valid levelId', ['exist_condition' => $exist_condition, 'description' => $description]);
        }

        return [$met_deposit, $met_bet, $met_profit, $completion, $msg_arr];
	}
}
