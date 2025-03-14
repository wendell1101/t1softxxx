<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 *
 * amusino VIP Daily Deposit Bonus
 *
 * OGP-34400
 *
 * USD
 * 最低领取奖金 0.1
 * 最高可领取奖金 300
 * 最低存款 5
 * 取款条件 3倍流水
 * 
 * JPY
 * 最低领取奖金 16
 * 最高可领取奖金 48000
 * 最低存款 1000
 * 取款条件 3倍流水
 * 
 * PHP
 * 最低领取奖金 5
 * 最高可领取奖金 15000
 * 最低存款 100
 * 取款条件 3倍流水
 * 
 * 每日存款,一天限申請一次 （採用申請當下最新一筆沒申請過優惠的存款做獎金基準）
 * 奖金根据VIP等级的%
 * 如果發起存款時有選優惠, 到帳就會自動派發,
 * 如果發起存款時沒選優惠, 玩家就要自行申請
 * 
 * 取款條件: 存款 * 1+獎金 * 3
 * 

condition:
{
    "class": "promo_rule_amusino_vip_daily_deposit_bonus",
	"min_bonus": 0.1,
	"max_bonus": 300,
	"min_deposit": 5,
	"bet_condition_times": 3,
	"existsTransByTypesAfter": false, (optional, not enabled as default)
	"bonus_settings": {
		 "84" : {"bonus_percentage":   1},
		 "85" : {"bonus_percentage":   1},
		 "86" : {"bonus_percentage":   1},
		 "87" : {"bonus_percentage":   1},
		 "88" : {"bonus_percentage": 1.5},
		 "89" : {"bonus_percentage": 1.5},
		 "90" : {"bonus_percentage": 1.5},
		 "91" : {"bonus_percentage": 1.5},
		 "92" : {"bonus_percentage":   2},
		 "93" : {"bonus_percentage":   2},
		 "94" : {"bonus_percentage":   2},
		 "95" : {"bonus_percentage":   2},
		 "96" : {"bonus_percentage":   3},
		 "97" : {"bonus_percentage":   3},
		 "98" : {"bonus_percentage":   3},
		 "99" : {"bonus_percentage":   3},
		"100" : {"bonus_percentage":   4},
		"101" : {"bonus_percentage":   4},
		"102" : {"bonus_percentage":   4},
		"103" : {"bonus_percentage":   4},
		"104" : {"bonus_percentage":   5}
	}
}

 *
 *
 *
 */
class Promo_rule_amusino_vip_daily_deposit_bonus extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_amusino_vip_daily_deposit_bonus';
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
		$result = $this->checkCustomizeBounsCondition($description, $errorMessageLang, $extra_info);

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

        $times = $description['bet_condition_times'];
        $bonus_amount = $result['bonus_amount'];
        $deposit_amount = $result['deposit_amount'];
        $this->appendToDebugLog('get bonus_amount and deposit_amount and times', ['bonus_amount'=>$bonus_amount, 'deposit_amount'=>$deposit_amount, 'times'=>$times]);

        if($times > 0){
            $withdrawal_condition_amount = ($bonus_amount * $times) + ($deposit_amount * $this->non_promo_withdraw_setting);
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
		$errorMessageLang = null;
		$result = $this->checkCustomizeBounsCondition($description, $errorMessageLang, $extra_info);

		return $result;
	}

	private function checkCustomizeBounsCondition($description, &$errorMessageLang, &$extra_info){
		$success = false;
		$bonus_amount = 0;
		$deposit_amount = 0;

		// check vip level
		$levelId = $this->levelId;
		$bonus_settings = !empty($description['bonus_settings']) ? $description['bonus_settings'] : null;
		if(!array_key_exists($levelId, $bonus_settings)){
			$errorMessageLang = 'promo_custom.not_in_allowed_vip_level';
			return ['success'=>$success, 'message'=>$errorMessageLang];
		}

		// check if player has applied this promo today
        $approvedPromoToday = $this->callHelper('count_approved_promo',[$this->promorulesId, self::DATE_TYPE_TODAY]);
        $this->appendToDebugLog('get today released promo', ['today release count' => $approvedPromoToday]);
        if(!empty($approvedPromoToday)){
            $errorMessageLang = 'notify.83';
            return ['success'=>$success, 'message'=>$errorMessageLang];
        }

		$depositTranId = null;
		$start = $this->callHelper('get_date_type', [self::DATE_TODAY_START]);
		$end = $this->callHelper('get_date_type', [self::TO_TYPE_NOW]);
		$min_deposit = !empty($description['min_deposit']) ? $description['min_deposit'] : 0;
		$checkExistsTransByTypesAfter = !empty($description['existsTransByTypesAfter']) ? $description['existsTransByTypesAfter'] : false;
		$existsTransByTypesAfter = false;

		if($this->callHelper('isCheckingBeforeDeposit',[])){
			// deposit amount from deposit page
			$this->appendToDebugLog('ignore trans', ['is_checking_before_deposit'=>$extra_info['is_checking_before_deposit']]);
			$deposit_amount = !empty($extra_info['depositAmount']) ? $extra_info['depositAmount'] : 0;

			#only allow first deposit to apply promo from deposit page today, or need to apply from promotion page
			$totalDepositToday = $this->callHelper('countDepositByPlayerId', [$start, $end]);
			if(!empty($totalDepositToday)){
				$errorMessageLang = 'notify.80';
				return ['success' => $success, 'message' => $errorMessageLang];
			}
		}else{
			$trans = $this->callHelper('getLastDepositByDate', [$start, $end]);
			$this->appendToDebugLog('check date and trans id', ['start'=>$start, 'end'=>$end, 'trans'=>$trans]);

			if(empty($trans)){
				$errorMessageLang = 'promo_custom.deposit_donot_match_the_requirement';
				return ['success' => $success, 'message' => $errorMessageLang];
			}

			$depositTranId = $trans['id'];
			$deposit_amount = $trans['amount'];
			if($checkExistsTransByTypesAfter){
				$existsTransByTypesAfter = $this->callHelper('existsTransByTypesAfter', [$this->playerId, $this->promorule, $trans['created_at'], $extra_info]);
				$this->appendToDebugLog('check existsTransByTypesAfter in custom promo', ['ret'=> $existsTransByTypesAfter]);
			}
		}

		if($deposit_amount < $min_deposit){
			$errorMessageLang = 'notify.79';
			return ['success' => $success, 'message' => $errorMessageLang];
		}

		if($checkExistsTransByTypesAfter && $existsTransByTypesAfter){
			$errorMessageLang = 'promo_rule.common.error';
			$this->appendToDebugLog('checkExistsTransByTypesAfter and existsTransByTypesAfter');
			return ['success' => $success, 'message' => $errorMessageLang];
		}

        $setting = $bonus_settings[$levelId];
        $bonus_percentage = $setting['bonus_percentage'];
		$bonus_amount = $deposit_amount * ($bonus_percentage/100);

		$min_bonus = !empty($description['min_bonus']) ? $description['min_bonus'] : 0;
		$max_bonus = !empty($description['max_bonus']) ? $description['max_bonus'] : 0;

		if($bonus_amount < $min_bonus){
			$errorMessageLang = 'promo_rule.common.error';
			return ['success' => $success, 'message' => $errorMessageLang];
		}

		if($bonus_amount >= $max_bonus){
			$bonus_amount = $max_bonus;
		}

		$success = true;

		return $result=['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonus_amount, 'deposit_tran_id' => $depositTranId, 'deposit_amount' => $deposit_amount];
	}
}