<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 * OGP-29316
 * Each Deposit
 * 需完善註冊
 * 每日可領取一次
 * 
 * Bonus condition 
condition:
{
    "class": "promo_rule_t1bet_deposit_bonus",
    "allowed_date": {
        "start": "",
        "end": ""
    },
    "verified_phone":true,
    "verified_email":true,
    "completed_player_info":true,
    "bonus_settings":[
        {"min_deposit": 500, "max_deposit": 999, "bonus_amount": 58},
        {"min_deposit": 1000, "max_deposit": 4999, "bonus_amount": 128},
        {"min_deposit": 5000, "max_deposit": 999999999999999, "bonus_amount": 588}
    ]
}
 *
 *
 */
class Promo_rule_t1bet_deposit_bonus extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'promo_rule_t1bet_deposit_bonus';
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
        $allowed_date = $description['allowed_date'];
        $bonus_settings = $description['bonus_settings'];
        $min_deposit = $bonus_settings[0]['min_deposit']; 
        $fromDate = !empty($allowed_date['start']) ? $allowed_date['start'] : $this->get_date_type(self::DATE_TODAY_START);
		$toDate = !empty($allowed_date['end']) ? $allowed_date['end'] : $this->get_date_type(self::TO_TYPE_NOW);

        $verified_phone = $this->isVerifiedPhone($description);
        $verified_email = $this->isVerifiedEmail($description);
        $completed_player_info = $this->completed_player_info($description);

        #get last deposit from today start to now
	    $getFirstDepositByDate = $this->callHelper('getLastDepositByDate',[$fromDate, $toDate]);
	    $lastDepositAmount = intval($getFirstDepositByDate['amount']);
        $this->appendToDebugLog('checkCustomizeBonusCondition lastDepositAmount:'.$lastDepositAmount);

        if(!$verified_phone || !$verified_email || !$completed_player_info){

            if (!$verified_phone) {
                $errorMessageLang = 'promo.rule_is_player_verified_mobile';
            } elseif (!$verified_email) {
                $errorMessageLang = 'promo.rule_is_player_verified_email';
            } elseif (!$completed_player_info) {
                $errorMessageLang = 'notify.93';
            } else {
                $errorMessageLang='promo_rule.common.error';
            }

		} else {
            if (!empty($bonus_settings)) {
				if(is_array($bonus_settings)){
	                foreach ($bonus_settings as $list) {
	                    if($lastDepositAmount >= $list['min_deposit'] && $lastDepositAmount <= $list['max_deposit']){
	                        $success = true;
	                        $bonus_amount = $list['bonus_amount'];
	                    } elseif ($lastDepositAmount < $min_deposit) {
                            $errorMessageLang = 'No enough deposit';
                        }
	                }
	            }
			} else {
                $this->appendToDebugLog('Not exist bonus Setting');
                $errorMessageLang = 'promo_rule.common.error';
			}
        }

		$result=['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonus_amount];
        return $result;
    }

}
