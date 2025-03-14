<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 * Birthday Bonus
 * OGP-22203
 *
 * 生日当月允许申请，按等于发奖金，一年一次
 *
 * OGP-35752: add last_n_days_have_deposit

 * Bonus condition && Bonus release:
    {
        "class": "promo_rule_ole777idr_birthday_bonus",
        "last_n_days_have_deposit": "180",
        "verify_mobile": true, //(optional)
        "verify_email": true,  //(optional)
        "bonus_settings": {
            "VIP2": { "bonus":   27 },
            "VIP3": { "bonus":   37 },
            "VIP4": { "bonus":  177 },
            "VIP5": { "bonus":  577 },
            "VIP6": { "bonus": 1777 },
            "VIP7": { "bonus": 3777 },
            "VIP8": { "bonus": 5777 },
            "VIP9": { "bonus": 7777 }
        }
    }
 *
 *
 */
class Promo_rule_ole777idr_birthday_bonus extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_ole777idr_birthday_bonus';
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

        $bonus_settings=$description['bonus_settings'];
        $inBirthMonth = $this->inBirthMonth($description);
        $verified_phone = $this->isVerifiedPhone($description);
        $verified_email = $this->isVerifiedEmail($description);
        $last_n_days_have_deposit = !empty($description['last_n_days_have_deposit']) ? $description['last_n_days_have_deposit'] : null;
        $verified_deposit = $this->callHelper('isDepositInPeriod', [$last_n_days_have_deposit]);

        if(!array_key_exists($this->levelId, $bonus_settings)){
            $errorMessageLang = 'notify.35';
            return ['success'=>$success, 'message'=>$errorMessageLang];
        }

        if(!$verified_phone){
            $errorMessageLang = 'promo.rule_is_player_verified_mobile';
            return ['success'=>$success, 'message'=>$errorMessageLang];
        }

        if(!$verified_email){
            $errorMessageLang = 'promo.rule_is_player_verified_email';
            return ['success'=>$success, 'message'=>$errorMessageLang];
        }

        if(!$inBirthMonth){
            $errorMessageLang = 'promo_custom.not_in_the_month_of_birthdate';
            return ['success'=>$success, 'message'=>$errorMessageLang];
        }

        if(!$verified_deposit){
            $errorMessageLang = 'notify.80';
            return ['success'=>$success, 'message'=>$errorMessageLang];
        }

        $success = true;

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

        $levelId = $this->levelId;
        $bonus_settings=$description['bonus_settings'];
        $inBirthMonth = $this->inBirthMonth($description);
        $verified_phone = $this->isVerifiedPhone($description);
        $verified_email = $this->isVerifiedEmail($description);
        $last_n_days_have_deposit = !empty($description['last_n_days_have_deposit']) ? $description['last_n_days_have_deposit'] : null;
        $verified_deposit = $this->callHelper('isDepositInPeriod', [$last_n_days_have_deposit]);

        if(!array_key_exists($this->levelId, $bonus_settings)){
            $errorMessageLang = 'notify.35';
            return ['success'=>$success, 'message'=>$errorMessageLang];
        }

        if(!$verified_phone){
            $errorMessageLang = 'promo.rule_is_player_verified_mobile';
            return ['success'=>$success, 'message'=>$errorMessageLang];
        }

        if(!$verified_email){
            $errorMessageLang = 'promo.rule_is_player_verified_email';
            return ['success'=>$success, 'message'=>$errorMessageLang];
        }

        if(!$inBirthMonth){
            $errorMessageLang = 'promo_custom.not_in_the_month_of_birthdate';
            return ['success'=>$success, 'message'=>$errorMessageLang];
        }

        if(!$verified_deposit){
            $errorMessageLang = 'notify.80';
            return ['success'=>$success, 'message'=>$errorMessageLang];
        }

        $success = true;
        $bonus_amount = $bonus_settings[$levelId]['bonus'];

		$result=['success'=>$success, 'message'=>$errorMessageLang, 'bonus_amount'=>$bonus_amount];
		return $result;
	}

	protected function inBirthMonth($description){
        $this->load->model(['player_model']);

        $thisMonth = date('m');
        $thisMonth = !empty($description['thisMonth']) ? $description['thisMonth'] : $thisMonth;

        $birthMonth = null;
        $player = $this->player_model->getPlayerInfoDetailById($this->playerId);
        $birthdate = !empty($description['birthdate']) ? $description['birthdate'] : $player['birthdate'];
        if(!empty($birthdate)){
            $birthMonth = substr($birthdate,5,2);
        }

        $inBirthMonth = $thisMonth == $birthMonth;
        $this->appendToDebugLog('in valid month', ['inBirthMonth'=>$inBirthMonth, 'thisMonth'=>$thisMonth, 'birthMonth'=>$birthMonth]);
        if(!$inBirthMonth){
            $this->appendToDebugLog('not right month',['birthMonth'=>$birthMonth]);
        }

        return $inBirthMonth;
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
}
