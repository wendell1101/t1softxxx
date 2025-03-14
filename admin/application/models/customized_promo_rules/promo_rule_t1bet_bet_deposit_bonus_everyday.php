<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 * OGP-29318
 * Continuous Deposit Bonus
 * Slots & Mini [Game Type]
 * 第1日 , 存款>= 100 & 投注> 1000, Bonus 10
 * 第2日 , 存款>= 100 & 投注> 1500, Bonus 20
 * 第3日 , 存款>= 100 & 投注> 1500, Bonus 30
 * 第4日 , 存款>= 100 & 投注> 1500, Bonus 40
 * 第5日 , 存款>= 100 & 投注> 1500, Bonus 50
 * 第6日 , 存款>= 100 & 投注> 1500, Bonus 60
 * 第7日 , 存款>= 100 & 投注> 2000, Bonus 70
 * 第8日 , 存款>= 100 & 投注> 3000, Bonus 300
 *
 * 提款条件: Bonus金额的3倍流水
 * 中断的情况：不可繼續領取。
 *
 *

* Bonus condition && Bonus release:
{
    "class": "promo_rule_t1bet_bet_deposit_bonus_everyday",
    "min_deposit": 100,
    "bonus_settings": [
        {"day": 1, "min_bet": 1000, "bonus_amount":   10},
        {"day": 2, "min_bet": 1500, "bonus_amount":   20},
        {"day": 3, "min_bet": 1500, "bonus_amount":   30},
        {"day": 4, "min_bet": 1500, "bonus_amount":   40},
        {"day": 5, "min_bet": 1500, "bonus_amount":   50},
        {"day": 6, "min_bet": 1500, "bonus_amount":   60},
        {"day": 7, "min_bet": 2000, "bonus_amount":   70},
        {"day": 8, "min_bet": 3000, "bonus_amount":  300}
    ]
}

 *
 *
 */
class Promo_rule_t1bet_bet_deposit_bonus_everyday extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_t1bet_bet_deposit_bonus_everyday';
	}

    protected function isVerifiedPhone($description){
        $verified_phone = true;

        if(!empty($description['verified_phone']) && $description['verified_phone']){
            $verified_phone = $this->player_model->isVerifiedPhone($this->playerId);
        }

        if(!$verified_phone){
            $this->appendToDebugLog('not verified phone',['verified_phone'=>$verified_phone]);
        }

        return $verified_phone;
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
        $bonus_settings = $description['bonus_settings'];
        $game_type = $description['game_type'];
        $min_deposit = $description['min_deposit'];

        // check today apply promo or not
        $isReleasedBonusToday = $this->callHelper('get_last_released_player_promo', [$this->promorulesId, self::DATE_TYPE_TODAY]);
        $this->appendToDebugLog('get_last_released_player_promo', ['promorulesId'=>$this->promorulesId, 'isReleasedBonusToday'=>$isReleasedBonusToday]);

        if($isReleasedBonusToday){
            $errorMessageLang = 'notify.83';
            return ['success' => $success, 'message' => $errorMessageLang];
        }

        $verified_phone = $this->isVerifiedPhone($description);
        if(!$verified_phone){
            $errorMessageLang = 'promo.rule_is_player_verified_mobile';
            return ['success' => $success, 'message' => $errorMessageLang];
        }


        // check today deposit record
        $startDate = $this->callHelper('get_date_type', [self::DATE_TODAY_START]);
        $endDate = $this->callHelper('get_date_type', [self::TO_TYPE_NOW]);
        $deposit = $this->callHelper('sum_deposit_amount',[$startDate, $endDate, 0]);
        $deposit = empty($deposit) ? 0 : $deposit;
        $met_deposit = $deposit >= $min_deposit;
        $this->appendToDebugLog('check deposit condition', ['start date' => $startDate, 'end date' => $endDate, 'total deposit' => $deposit, 'met deposit condition' => $met_deposit]);

        if(!$met_deposit){
            $errorMessageLang = 'notify.43';
            return ['success' => $success, 'message' => $errorMessageLang];
        }

        // check today bet record
        $fromDate = $this->callHelper('get_date_type', [self::DATE_TODAY_START]);
        $toDate = $this->callHelper('get_date_type', [self::TO_TYPE_NOW]);
        $totalBet = $this->callHelper('getPlayerBetByDate', [$fromDate, $toDate, null, $game_type]);
        $this->appendToDebugLog('check bet condition', ['start date' => $fromDate, 'to date' => $toDate, 'total bet' => $totalBet]);

        if(empty($totalBet)){
            $errorMessageLang = 'promo.total_bet_not_met';
            return ['success' => $success, 'message' => $errorMessageLang];
        }

        $allPlayerPromo = $this->callHelper('get_all_released_player_promo', [$this->promorulesId, null]);
        $totalBonusSettings = count($bonus_settings);
        $this->appendToDebugLog('start check player deposit', ['player promo history' => $allPlayerPromo, 'total bonus settings' => $totalBonusSettings]);

        // first time apply
        if(empty($allPlayerPromo)){
            $this->appendToDebugLog('first time apply');
            $settings = $bonus_settings[0];
            $min_bet = $settings['min_bet'];

            if($totalBet > $min_bet){
                $success = true;
                $bonus_amount = $settings['bonus_amount'];
            }else{
                $this->appendToDebugLog('first time bet not enough, min bet should greater than', $min_bet);
                $errorMessageLang = 'promo.total_bet_not_met';
                return ['success' => $success, 'message' => $errorMessageLang];
            }
        }

        // other time apply
        if(!empty($allPlayerPromo)) {
            $this->appendToDebugLog('other time apply');
            $allPlayerPromo = array_reverse($allPlayerPromo);
            $totalPlayerPromo = count($allPlayerPromo);


            // if player already apply 8 times, can't apply forever
            if($totalBonusSettings == $totalPlayerPromo){
                $this->appendToDebugLog('already apply all promo bonus');
                $errorMessageLang = 'notify.83';
                return ['success' => $success, 'message' => $errorMessageLang];
            }

            $lastDateProcessed = $allPlayerPromo[$totalPlayerPromo-1]['dateProcessed'];
            $lastPromoDate = $this->utils->formatDateForMysql(new DateTime($lastDateProcessed));
            $yesterday = $this->utils->getYesterdayForMysql();

            if($lastPromoDate == $yesterday){
                $settings = $bonus_settings[$totalPlayerPromo];
                $min_bet = $settings['min_bet'];

                if($totalBet > $min_bet){
                    $success = true;
                    $bonus_amount = $settings['bonus_amount'];
                }else{
                    $this->appendToDebugLog('other time bet not enough, min bet should greater than', $min_bet);
                    $errorMessageLang = 'promo.total_bet_not_met';
                    return ['success' => $success, 'message' => $errorMessageLang];
                }
            }else{
                $this->appendToDebugLog('yesterday did not apply promo, can not continue apply again');
                $errorMessageLang = 'promo_rule.common.error';
                return ['success' => $success, 'message' => $errorMessageLang];
            }
        }

        if(!$success){
            $errorMessageLang = 'promo_rule.common.error';
            return ['success' => $success, 'message' => $errorMessageLang];
        }

        return ['success'=>$success, 'message'=>$errorMessageLang, 'bonus_amount'=>$bonus_amount];
    }
}
