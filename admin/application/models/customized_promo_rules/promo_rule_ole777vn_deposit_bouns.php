<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 *
 * ole777vn deposit bouns
 *
 * OGP-25079
 *
 * 活動有效期11-17
 * 只能領一次
 * 最低存款100(10萬 vnd)
 * 獎金計算 > 100 * percentage
 * 取款條件 > (首款 + 獎金) * $bet_condition_times
 * 11-17日存款金額大於等於100(10萬vnd)

condition:
{
    "class": "promo_rule_ole777vn_deposit_bouns",
    "allowed_date":{
        "start": "11",
        "end": "17"
    },
    "release_date": {
        "start": "",
        "end": ""
    },
    "bonus_settings":{
        "1" : {"percentage": 20, "max_bonus": 1000, "bet_condition_times": 10},
        "2" : {"percentage": 20, "max_bonus": 1000, "bet_condition_times": 10},
        "3" : {"percentage": 20, "max_bonus": 1000, "bet_condition_times": 10},
        "4" : {"percentage": 22, "max_bonus": 1500, "bet_condition_times": 12},
        "5" : {"percentage": 22, "max_bonus": 1500, "bet_condition_times": 12},
        "6" : {"percentage": 22, "max_bonus": 1500, "bet_condition_times": 12},
        "7" : {"percentage": 25, "max_bonus": 2000, "bet_condition_times": 15},
        "8" : {"percentage": 25, "max_bonus": 2000, "bet_condition_times": 15},
        "9" : {"percentage": 30, "max_bonus": 2500, "bet_condition_times": 15},
        "10" : {"percentage": 30, "max_bonus": 3500, "bet_condition_times": 15}
    },
    "deposit_min_limit": 100
}
 *
 *
 *
 */
class Promo_rule_ole777vn_deposit_bouns  extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_ole777vn_deposit_bouns';
	}

	/**
	 * run bonus condition checker
	 * @param  array $description original description in rule
	 * @param  array $extra_info
	 * @param  boolean $dry_run
	 * @return  array ['success'=> success, 'message'=> errorMessageLang, 'continue_process_after_script' => TRUE]
	 */
	protected function runBonusConditionChecker($description, &$extra_info, $dry_run){
		$errorMessageLang = null;
		$result = $this->checkCustomizeBounsCondition($description, $extra_info, $errorMessageLang);

		if(array_key_exists('bonus_amount',$result)){
			unset($result['bonus_amount']);
		}

		if(array_key_exists('deposit_amount',$result)){
			unset($result['deposit_amount']);
		}

		if(array_key_exists('bet_condition_times',$result)){
			unset($result['bet_condition_times']);
		}

		if ($result['success']) {
			$result['continue_process_after_script'] = true;
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

        $times = $result['bet_condition_times'];
        $bonus_amount = $result['bonus_amount'];
        $deposit_amount = $result['deposit_amount'];

        if($times > 0){
            $withdrawal_condition_amount = ($bonus_amount + $deposit_amount) * $times;
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
        $success = false;
        $errorMessageLang = null;
        $transfer_condition_amount = 0;

        $result = $this->releaseBonus($description, $extra_info, $dry_run);

        $times = $result['bet_condition_times'];
        $bonus_amount = $result['bonus_amount'];
        $deposit_amount = $result['deposit_amount'];

        if($times > 0){
            $transfer_condition_amount = ($bonus_amount + $deposit_amount) * $times;
            $success = $transfer_condition_amount > 0;
        }else{
            $errorMessageLang='Lost transfer bet_condition_times in settings';
        }

        $result=['success'=>$success, 'message'=>$errorMessageLang, 'transfer_condition_amount'=>round($transfer_condition_amount, 2)];
        return $result;
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
		$result = $this->checkCustomizeBounsCondition($description, $extra_info, $errorMessageLang);

		return $result;
	}

	private function checkCustomizeBounsCondition($description, &$extra_info, &$errorMessageLang){
        $success = false;
	    $bonus_amount = 0;
	    $deposit_amount = 0;
	    $bet_condition_times = 0;
	    $promoRuleId = $this->promorulesId;
        $allowed_date=$description['allowed_date'];
        $bonus_settings = $description['bonus_settings'];
		$levelId = $this->levelId;
		$today = $this->utils->getTodayForMysql();
		$not_allow_bet_after_deposit = $description['donot_allow_any_available_bet_after_deposit'];

	    $count_approved_promo = $this->callHelper('count_approved_promo',[$promoRuleId,self::DATE_TYPE_THIS_MONTH]);
        if(!empty($description['release_date'])){
            $release_date = $description['release_date'];
            $this->appendToDebugLog('check release_date', ['release_date'=>$release_date]);
            if(!empty($release_date['start']) && !empty($release_date['end'])){
                $count_approved_promo = $this->callHelper('count_approved_promo',[$promoRuleId, self::DATE_TYPE_CUSTOMIZE, $release_date]);
            }
        }
		if(!empty($count_approved_promo)){
            $errorMessageLang = 'notify.83';
	    	return ['success' => $success, 'message' => $errorMessageLang];
	    }

		$d=new DateTime($today);
        $currentDate=$d->format('Y-m-d');
        if(!empty($allowed_date['from_date']) && !empty($allowed_date['end_date'])){
            $minDate=$this->utils->formatDateForMysql(new DateTime($allowed_date['from_date']));
            $maxDate=$this->utils->formatDateForMysql(new DateTime($allowed_date['end_date']));
        }else{
            $minDate=$d->format('Y-m-').$allowed_date['start'];
            $maxDate=$d->format('Y-m-').$allowed_date['end'];
        }
        $this->appendToDebugLog('check date info', ['allowed_date'=>$allowed_date, 'minDate' => $minDate, 'maxDate' => $maxDate]);

		$last_deposit_bydate = $this->callHelper('getLastDepositByDate',[$minDate.' '.Utils::FIRST_TIME, $maxDate.' '.Utils::LAST_TIME]);
        $last_deposit_amount = intval($last_deposit_bydate['amount']);
	    $this->appendToDebugLog('check params detail', ['allowed_date'=>$allowed_date, 'minDate' => $minDate, 'maxDate' => $maxDate,
            'count_approved_promo' => $count_approved_promo, 'last_deposit_amount' => $last_deposit_amount]);

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

        if(array_key_exists($levelId, $bonus_settings)){
        	if($currentDate>=$minDate && $currentDate<=$maxDate){
        		$setting = $bonus_settings[$levelId];
	            if( $last_deposit_amount < $description['deposit_min_limit']){ //depost greater than 100(100000 vnd)
					$errorMessageLang = 'notify.79';
					return ['success' => $success, 'message' => $errorMessageLang];
				}

	            if(!empty($last_deposit_amount)){
	                $deposit_amount = $last_deposit_amount;
	                $bonus_amount = ($setting['percentage'] / 100) * $last_deposit_amount;
	                if($bonus_amount > $setting['max_bonus']){
	                    $bonus_amount = $setting['max_bonus'];
	                }
	                $bet_condition_times = $setting['bet_condition_times'];
	                $success=true;
	            }
			}else{
				$errorMessageLang = 'Not right date';
			}
        }else {
			$errorMessageLang = 'Not exist bet Setting';
		}

		return ['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonus_amount, 'deposit_amount' => $deposit_amount, 'bet_condition_times' => $bet_condition_times];
	}
}