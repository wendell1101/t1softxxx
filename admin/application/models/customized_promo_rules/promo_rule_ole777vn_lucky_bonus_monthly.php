<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 *
 * Start of the Month Lucky Chips (based on VIP & Deposit Amt)
 *
 * OGP-25078
 *
 * 每月m-n号内，允许玩家申请一次免费奖金

condition:
{
    "class": "promo_rule_ole777vn_lucky_bonus_monthly",
    "allowed_date":{
        "start": "01",
        "end": "05"
    },
    "release_date": {
        "start": "",
        "end": ""
    },
    "deposit_date": {
        "start": "",
        "end": ""
    },
    "bonus_settings":[
    	{"level_id":  "12", "deposit_amount_start":     120, "deposit_amount_end":    3700, "max_bonus":   17},
        {"level_id":   "2", "deposit_amount_start":    3700, "deposit_amount_end":    7700, "max_bonus":   37},
        {"level_id":   "3", "deposit_amount_start":    7700, "deposit_amount_end":   17000, "max_bonus":   77},
        {"level_id":   "1", "deposit_amount_start":   17000, "deposit_amount_end":   47000, "max_bonus":  170},
        {"level_id":  "11", "deposit_amount_start":    1700, "deposit_amount_end":    3700, "max_bonus":   17},
        {"level_id":  "22", "deposit_amount_start":    3700, "deposit_amount_end":    7700, "max_bonus":   37},
        {"level_id":  "33", "deposit_amount_start":    7700, "deposit_amount_end":   17000, "max_bonus":   77},
        {"level_id":  "44", "deposit_amount_start":   17000, "deposit_amount_end":   47000, "max_bonus":  170},
        {"level_id":  "55", "deposit_amount_start":   47000, "deposit_amount_end":   77000, "max_bonus":  470},
        {"level_id":  "66", "deposit_amount_start":   77000, "deposit_amount_end":  170000, "max_bonus":  770},
        {"level_id":  "77", "deposit_amount_start":  170000, "deposit_amount_end":  770000, "max_bonus": 1700},
        {"level_id":  "88", "deposit_amount_start":  770000, "deposit_amount_end": 1700000, "max_bonus": 2700},
        {"level_id":  "99", "deposit_amount_start": 1700000, "deposit_amount_end": 2700000, "max_bonus": 3700},
        {"level_id": "100", "deposit_amount_start": 2700000, "deposit_amount_end": 9999999, "max_bonus": 5700}
    ]
}

 *
 *
 *
 */
class Promo_rule_ole777vn_lucky_bonus_monthly extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_ole777vn_lucky_bonus_monthly';
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
	    $currentVipLevelId = $this->levelId;
	    $promorule = $this->promorule;
	    $promoRuleId = $promorule['promorulesId'];
	    $release_date = $description['release_date'];
        $bonus_settings = $description['bonus_settings'];
	    $today = $this->utils->getTodayForMysql();
	    $not_allow_bet_after_deposit = $description['donot_allow_any_available_bet_after_deposit'];

	   	#check first apply this month
		$count_approved_promo = $this->callHelper('count_approved_promo',[$this->promorulesId, self::DATE_TYPE_THIS_MONTH]);

	    if(!empty($release_date['start']) && !empty($release_date['end'])){
			$count_approved_promo = $this->callHelper('count_approved_promo',[$promoRuleId, self::DATE_TYPE_CUSTOMIZE, $release_date]);
	    }

	    $from_deposit_date = !empty($deposit_date['start']) ? $deposit_date['start'] : $this->get_date_type(self::DATE_LAST_MONTH_START);
		$to_deposit_date = !empty($deposit_date['end']) ? $deposit_date['end'] : $this->get_date_type(self::DATE_LAST_MONTH_END);
		$last_month_deposit = $this->callHelper('sum_deposit_amount',[$from_deposit_date, $to_deposit_date, 0]);

		$this->appendToDebugLog('search count_approved_promo', ['currentVipLevelId' => $currentVipLevelId, 'from_deposit_date' => $from_deposit_date, 'to_deposit_date' => $to_deposit_date, 'last_month_deposit' => $last_month_deposit, 'count_approved_promo' => $count_approved_promo]);

		$all_vipids = array_column($bonus_settings, 'level_id');
		$count_all_vipids = count($all_vipids);
		$check_id_inarray = in_array($currentVipLevelId, $all_vipids);
		$get_key_by_vipId = array_search($currentVipLevelId, $all_vipids);
		$curr_set = $bonus_settings[$get_key_by_vipId];

		$this->appendToDebugLog('get setting', ['all_vipids' => $all_vipids, 'count_all_vipids' => $count_all_vipids, 'currentVipLevelId' => $currentVipLevelId, 'check_id_inarray' => $check_id_inarray, 'get_key_by_vipId' => $get_key_by_vipId, 'curr_set' => $curr_set]);

		if(!empty($count_approved_promo)){
			$errorMessageLang = lang('notify.83');
	    	return ['success' => $success, 'message' => $errorMessageLang];
	    }

        if(empty($last_month_deposit)){
            $errorMessageLang = 'promo_custom.deposit_donot_match_the_requirement';
            return ['success' => $success, 'message' => $errorMessageLang];
        }

		if($check_id_inarray){
			$allowed_date=$description['allowed_date'];
			$this->appendToDebugLog('check allowed_date', ['allowed_date'=>$allowed_date]);

			$d=new DateTime($today);
            $currentDate=$d->format('Y-m-d');

            if(!empty($allowed_date['from_date']) && !empty($allowed_date['end_date'])){
                $minDate=$this->utils->formatDateForMysql(new DateTime($allowed_date['from_date']));
                $maxDate=$this->utils->formatDateForMysql(new DateTime($allowed_date['end_date']));
            }else{
                $minDate=$d->format('Y-m-').$allowed_date['start'];
                $maxDate=$d->format('Y-m-').$allowed_date['end'];
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

			if($currentDate>=$minDate && $currentDate<=$maxDate){
				if ($last_month_deposit >= $curr_set['deposit_amount_end']) {
					$success = true;
					$bonus_amount = $curr_set['max_bonus'];
				}else if ($last_month_deposit >= $curr_set['deposit_amount_start'] && $last_month_deposit < $curr_set['deposit_amount_end']) {
					$success = true;
					$bonus_amount = $curr_set['max_bonus'];
				} else {
					if ($get_key_by_vipId > 0) {
						for ($i = $get_key_by_vipId; $i >= 0; $i--) { 
							$curr_set = $bonus_settings[$i];
							if ($last_month_deposit >= $curr_set['deposit_amount_start'] && $last_month_deposit < $curr_set['deposit_amount_end']) {
								$success = true;
								$bonus_amount = $curr_set['max_bonus'];
								break;
							}
						}
						$this->appendToDebugLog('recheck result', ['get_key_by_vipId' => $get_key_by_vipId, 'curr_set' => $curr_set, 'currentVipLevelId' => $currentVipLevelId, 'last_month_deposit' => $last_month_deposit, 'bonus_amount' => $bonus_amount, 'success' => $success]);
					}else{
						$errorMessageLang = 'Not right deposit amount';
					}
				}
			}else{
				$errorMessageLang = 'Not right date';
			}
		}else{
			$errorMessageLang = 'Not right group level';
		}

		return ['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonus_amount];
	}
}

