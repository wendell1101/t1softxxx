<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 *
 * 20% Reload Harian Slot
 *
 * OGP-21930
 *
 * 优惠申请期限 , 存款优惠仅能是今日的存款
 * 今天存，今天可领优惠 ； 昨天存，今日不能领 需在今日请再存一笔

* Bonus condition && Bonus release:
{
    "class": "promo_rule_ole777_deposit_bonus_on_deposit_day",
    "not_allow_promo_on_other_days": {
        "promorule_ids": ["id_1", "id_2", "id_3"]
    },
    "not_allow_promo_on_the_same_day": {
        "promorule_ids": ["id_1", "id_2", "id_3"]
    }
}

 *
 *
 */
class Promo_rule_ole777_deposit_bonus_on_deposit_day extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_ole777_deposit_bonus_on_deposit_day';
	}

	/**
	 * run bonus condition checker
	 * @param  array $description original description in rule
	 * @param  array $extra_info
	 * @param  boolean $dry_run
	 * @return  array ['success'=> success, 'message'=> errorMessageLang, 'continue_process_after_script' => FALSE]
	 */
	protected function runBonusConditionChecker($description, &$extra_info, $dry_run){
		$success=false;
		$errorMessageLang=null;

        $isDepositToday = false;
        $now = $this->callHelper('get_date_type', [self::TO_TYPE_NOW]);
        $fromDate = $this->callHelper('get_date_type', [self::DATE_TODAY_START]);
        $toDate = $this->callHelper('get_date_type', [self::TO_TYPE_NOW]);

        $depositCntToday = $this->callHelper('countDepositByPlayerId', [$fromDate, $toDate]);
        $depositCntToday = !empty($depositCntToday) ? $depositCntToday : 0;

        $isReleasedBonusToday = $this->callHelper('get_last_released_player_promo',[$this->promorulesId, self::DATE_TYPE_TODAY]);

        $getLastDepositWithoutApplyPromoByDate = $this->callHelper('getLastDepositByDate', [$fromDate, $toDate]);
        if(!empty($getLastDepositWithoutApplyPromoByDate)){
            $created_at = $getLastDepositWithoutApplyPromoByDate['created_at'];
            $isDepositToday = substr($now, 0, 10) == substr($created_at, 0, 10);
            $this->appendToDebugLog('getLastDepositByDate today', ['last deposit date' => $created_at]);
        }

        $this->checkNotAllowOtherPromoOnTheSameDay($description, $isReleasedBonusToday);

        //check if player apply the same series of promo on other days
        if(!empty($description['not_allow_promo_on_other_days']['promorule_ids'])){
            $promorule_ids = $description['not_allow_promo_on_other_days']['promorule_ids'];
            $this->_checkNotAllowOtherPromoRecords($promorule_ids, $isReleasedBonusToday);
        }

        //deposit amount rule
        $insufficient = null; // ignore in default
        if( ! empty($extra_info['depositAmountSourceMethod'])
            && $extra_info['depositAmountSourceMethod'] == 'Api::getPlayerPromoApplyable'
        ){
            $depositAmount = $extra_info['depositAmount'];
            if(!empty($depositAmount) ){
                $isDepositToday = true;
                $depositCntToday = true;
            }

            $promorule = $this->promorule;

            //deposit amount rule
            if ($promorule['depositConditionNonFixedDepositAmount'] == promorules::NON_FIXED_DEPOSIT_MIN_MAX) {
                if ( $depositAmount >= $promorule['nonfixedDepositMinAmount']
                    && $depositAmount <= $promorule['nonfixedDepositMaxAmount']
                ) { // when depositAmount between nonfixedDepositMinAmount and nonfixedDepositMaxAmount
                    $insufficient = false;
                } else {
                    $insufficient = true;
                	// You have insufficient amount of deposit to join the promo
                    $errorMessageLang = 'notify.37';
                }
            }else if($promorule['depositConditionNonFixedDepositAmount'] == promorules::NON_FIXED_DEPOSIT_ANY){
                $insufficient = false;
            }
        } // EOF if( ! empty($extra_info['depositAmountSourceMethod']) && $extra_info['depositAmountSourceMethod'] == 'Api::getPlayerPromoApplyable'){...

        $this->appendToDebugLog('OGP31638.94', [ 'extra_info.depositAmountSourceMethod' => empty($extra_info['depositAmountSourceMethod'])? null: $extra_info['depositAmountSourceMethod']
                , 'extra_info.depositAmount' => empty($extra_info['depositAmount'])? null: $extra_info['depositAmount']
                , 'nonfixedDepositMinAmount' => $this->promorule['nonfixedDepositMinAmount']
                , 'nonfixedDepositMaxAmount' => $this->promorule['nonfixedDepositMaxAmount']
                , 'insufficient' => $insufficient
                , 'isReleasedBonusToday' => $isReleasedBonusToday
        ]);

        if($insufficient === true){
            $errorMessageLang = 'notify.37';
        }else if($isReleasedBonusToday){
            $errorMessageLang = 'notify.134';
        }else{
            if($isDepositToday){
                if(!empty($depositCntToday)){
                    $success = true;
                }else{
                    $errorMessageLang = 'promo_custom.deposit_donot_match_the_requirement';
                }
            }else{
                $errorMessageLang = 'promo_custom.no_deposit_record_found';
            }
        }

        $result=['success'=>$success, 'message'=>$errorMessageLang, 'continue_process_after_script' => TRUE];

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
        return $this->returnUnimplemented();
    }

    protected function checkNotAllowOtherPromoOnTheSameDay($description, &$isReleasedBonusToday){
        $releasedBonusToday = [];

        if(!empty($description['not_allow_promo_on_the_same_day']['promorule_ids'])){
            $promorule_ids = $description['not_allow_promo_on_the_same_day']['promorule_ids'];
            if(!empty($promorule_ids) && is_array($promorule_ids)){
                foreach ($promorule_ids as $promorule_id){
                    $get_last_released_player_promo = $this->callHelper('get_last_released_player_promo',[$promorule_id, self::DATE_TYPE_TODAY]);
                    if($get_last_released_player_promo){
                        $releasedBonusToday[$promorule_id] = $get_last_released_player_promo;
                    }
                }
            }
        }

        if(!empty($releasedBonusToday)){
            $isReleasedBonusToday = true;
            $this->appendToDebugLog('exist other promo on the same day', ['releasedBonusToday' => $releasedBonusToday]);
        }
    }
}
