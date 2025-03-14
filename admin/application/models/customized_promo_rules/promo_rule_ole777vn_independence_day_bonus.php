<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 * OGP-26579
 *
condition:
{
    "class": "promo_rule_ole777vn_independence_day_bonus",
    "allow_apply_range": {
        "from_date": "09-02",
        "to_date": "09-05"
    },
    "deposit_trans_range": {
        "from_date": "08-03",
        "to_date": "09-01"
    },
    "min_deposit_amount": 100,
    "bonus_settings":{
        "VIP1" : {"bonus_amount": 17},
        "VIP2" : {"bonus_amount": 37},
        "VIP3" : {"bonus_amount": 77},
        "VIP4" : {"bonus_amount": 107},
        "VIP5" : {"bonus_amount": 170},
        "VIP6" : {"bonus_amount": 270},
        "VIP7" : {"bonus_amount": 370},
        "VIP8" : {"bonus_amount": 570},
        "VIP9" : {"bonus_amount": 770},
        "VIP0" : {"bonus_amount": 1070}
    }
}

  *
 *
 */
class Promo_rule_ole777vn_independence_day_bonus extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'promo_rule_ole777vn_independence_day_bonus';
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
        $deposit_amount = 0;

        $allow_apply_range = $description['allow_apply_range'];
        $deposit_trans_range = $description['deposit_trans_range'];
        $bonus_settings = $description['bonus_settings'];
        $min_deposit_amount = $description['min_deposit_amount'];
        $not_allow_bet_after_deposit = $description['donot_allow_any_available_bet_after_deposit'];

        $endAt = $this->utils->getNowForMysql();
        $levelId = $this->callHelper('getLastUpgradeLevelOrCurrentLevel',[$endAt]);

        $today = $this->utils->getTodayForMysql();
        if($this->process_mock('today', $today)){
            $this->appendToDebugLog('use mock today', ['today'=>$today]);
        }

        $this->appendToDebugLog('runBonusConditionChecker check date', ['today'=>$today, 'allow_apply_range'=>$allow_apply_range, 'deposit_trans_range'=>$deposit_trans_range]);

        $d=new DateTime($today);
        $currentDate=$d->format('Y-m-d');

        if(!empty($allow_apply_range['from_date']) && !empty($allow_apply_range['to_date'])){
            $allow_apply_from = $d->format('Y-') . $allow_apply_range['from_date'];
            $allow_apply_to = $d->format('Y-') . $allow_apply_range['to_date'];
        }

        if(!empty($deposit_trans_range['from_date']) && !empty($deposit_trans_range['to_date'])){
            $dep_trans_from = $d->format('Y-') . $deposit_trans_range['from_date'] . ' ' . Utils::FIRST_TIME;
            $dep_trans_to = $d->format('Y-') . $deposit_trans_range['to_date'] . ' ' . Utils::LAST_TIME;
        }

        if($not_allow_bet_after_deposit){
            $promorule = $this->promorule;
            $periodFrom = $promorule['applicationPeriodStart'];
            $periodTo = $promorule['hide_date'];
            $trans = $this->callHelper('getLastDepositByDate', [$periodFrom, $periodTo]);
            $this->appendToDebugLog('check date and trans id', ['periodFrom' => $periodFrom, 'periodTo' => $periodTo, 'trans' => $trans]);

            $this->load->model(['game_logs']);
            $playerId = $this->playerId;
            $created_at = $trans['created_at'];

            list($totalBet, $totalWin, $totalLoss) = $this->game_logs->getTotalBetsWinsLossByPlayers($playerId, $created_at, $this->utils->getNowForMysql());
            $totalWin = (float)$totalWin;
            $totalLoss = (float)$totalLoss;
            if($totalWin != 0 || $totalLoss != 0){
                $extra_info['error_message']='notify.promo_donot_allow_any_available_bet_after_deposit';
                $this->appendToDebugLog($extra_info['debug_log'], 'customize promo_donot_allow_any_available_bet_after_deposit',
                    ['created_at'=>$created_at, 'playerId'=>$playerId]);
                return ['success' => $success, 'message' => $extra_info['error_message']];
            }
        }

        if($currentDate>=$allow_apply_from && $currentDate<=$allow_apply_to){
            if(array_key_exists($levelId, $bonus_settings)){
                $top_deposit_amount = $this->callHelper('getTopDepositByDate', [$dep_trans_from, $dep_trans_to]);

                $setting=$bonus_settings[$levelId];
                $this->appendToDebugLog('get bonus setting', ['bonus_settings'=>$setting, 'levelId'=>$levelId, 'top deposit'=>$top_deposit_amount]);

                if(!empty($top_deposit_amount)){
                    $deposit_amount = $top_deposit_amount;
                }

                if($deposit_amount >= $min_deposit_amount){
                    $success = true;
                    $bonus_amount = $setting['bonus_amount'];
                }else{
                    $errorMessageLang = 'promo_custom.deposit_donot_match_the_requirement';
                }

            }else{
                $errorMessageLang = 'Not right group level';
            }
        }else{
            $errorMessageLang = 'Not right date';
        }

        return ['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonus_amount];
    }
}
