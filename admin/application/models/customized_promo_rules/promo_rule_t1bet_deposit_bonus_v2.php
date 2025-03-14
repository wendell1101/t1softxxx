<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 * OGP-29316
 * OGP-30692 v2
 * 
 * Each Deposit
 * 需完善註冊
 * 每日可領取一次
 * 
 * Bonus condition 
condition:
{
    "class": "promo_rule_t1bet_deposit_bonus_v2",
    "allowed_date": {
        "start": "",
        "end": ""
    },
    "verified_phone":true,
    "verified_email":true,
    "completed_player_info":true,
    "bonus_settings":[
        {"min_deposit": 100, "max_deposit": 1000, "percentage": 12},
        {"min_deposit": 1000, "max_deposit": 5000, "percentage": 15},
        {"min_deposit": 5000, "max_deposit": 10000, "percentage": 20},
        {"min_deposit": 10000, "max_deposit": -1, "percentage": 25}
    ]
}
 *
 *
 */
class Promo_rule_t1bet_deposit_bonus_v2 extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'promo_rule_t1bet_deposit_bonus_v2';
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

    protected function isVerifiedEmail($description){
        $verified_email = true;

        if(!empty($description['verified_email']) && $description['verified_email']){
            $verified_email = $this->player_model->isVerifiedEmail($this->playerId);
        }

        if(!$verified_email){
            $this->appendToDebugLog('not verified email',['verified_email'=>$verified_email]);
        }

        return $verified_email;
    }

    protected function completed_player_info($description){
        $completed_player_info = true;

        if(!empty($description['completed_player_info']) && $description['completed_player_info']){
            $conditionResult = $this->player_model->getPlayerAccountInfoStatus($this->playerId);
            $completed_player_info = $conditionResult['status'];
        }

        if(!$completed_player_info){
            $conditionResultMissingFields = !empty($conditionResult['missing_fields']) ? $conditionResult['missing_fields'] : NULL;
            $this->appendToDebugLog('not complete player info',['missing_fields'=>$conditionResultMissingFields]);
        }

        return $completed_player_info;
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
        $result = $this->checkCustomizeBonusCondition($description, $errorMessageLang, $extra_info);

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
        $result = $this->checkCustomizeBonusCondition($description, $errorMessageLang, $extra_info);

        return $result;
    }

    private function checkCustomizeBonusCondition($description, &$errorMessageLang, &$extra_info){

        $success = false;
        $bonus_amount = 0;
        $allowed_date = isset($description['allowed_date']) ? $description['allowed_date'] : [];
        $bonus_settings = $description['bonus_settings'];
        $min_deposit = $bonus_settings[0]['min_deposit']; 
        $fromDate = !empty($allowed_date['start']) ? $allowed_date['start'] : $this->get_date_type(self::DATE_TODAY_START);
		$toDate = !empty($allowed_date['end']) ? $allowed_date['end'] : $this->get_date_type(self::TO_TYPE_NOW);

        $verified_phone = $this->isVerifiedPhone($description);
        $verified_email = $this->isVerifiedEmail($description);
        $completed_player_info = $this->completed_player_info($description);

        $deposit_page_amount = !empty($extra_info['depositAmount']) ? $extra_info['depositAmount'] : 0;
        $this->appendToDebugLog('checkCustomizeBonusCondition deposit_page_amount:'.$deposit_page_amount);

        #get last deposit from today start to now
	    $getFirstDepositByDate = $this->callHelper('getLastDepositByDate',[$fromDate, $toDate]);
	    $lastDepositAmount = intval($getFirstDepositByDate['amount']);
        $this->appendToDebugLog('checkCustomizeBonusCondition lastDepositAmount:'.$lastDepositAmount);

        $player_deposit_amount = $deposit_page_amount > 0 ? $deposit_page_amount : $lastDepositAmount;
        $this->appendToDebugLog('checkCustomizeBonusCondition player_deposit_amount:'.$player_deposit_amount);

        if(!$verified_phone){
            $errorMessageLang = 'promo.rule_is_player_verified_mobile';
            return ['success' => $success, 'message' => $errorMessageLang];
        }
        
        if(!$verified_email){
            $errorMessageLang = 'promo.rule_is_player_verified_email';
            return ['success' => $success, 'message' => $errorMessageLang];
        }
        
        if(!$completed_player_info){
            $errorMessageLang = 'notify.93';
            return ['success' => $success, 'message' => $errorMessageLang];
        }

        if(($player_deposit_amount < $min_deposit)){
            $errorMessageLang = 'notify.79';
            return ['success' => $success, 'message' => $errorMessageLang];
        }

        if(!empty($bonus_settings)){
            foreach ($bonus_settings as $list) {
                if( ($list['min_deposit'] <= $player_deposit_amount) &&
                    ($player_deposit_amount < $list['max_deposit'] || $list['max_deposit']<0) ){ //max_deposit<0 means no limit
                        $success = true;
                        $bonus_amount = ($list['percentage'] / 100) * $player_deposit_amount;
                }
            }
        }else{
            $this->appendToDebugLog('Not exist bonus Setting');
            $errorMessageLang = 'promo_rule.common.error';
        }

		$result=['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonus_amount];
        return $result;
    }

}
