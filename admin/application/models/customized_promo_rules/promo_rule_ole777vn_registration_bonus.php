<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 *
 * OGP-22916
 *
 * 需完成手机验证 和 Email验证 和 填写生日
 *
 *

condition:
{
    "class": "promo_rule_ole777vn_registration_bonus"
}

 *
 *
 */
class Promo_rule_ole777vn_registration_bonus extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_ole777vn_registration_bonus';
	}

    protected function isVerifiedPhone(){
        $verified_phone = $this->player_model->isVerifiedPhone($this->playerId);
        if(!$verified_phone){
            $this->appendToDebugLog('not verified phone',['result' => $verified_phone]);
        }

        return $verified_phone;
    }

    protected function isVerifiedEmail(){
        $verified_email = $this->player_model->isVerifiedEmail($this->playerId);
        if(!$verified_email){
            $this->appendToDebugLog('not verified email',['result' => $verified_email]);
        }

        return $verified_email;
    }

    protected function isFilledBankAccount(){
        $this->load->model(['playerbankdetails']);
        $bankdetails = $this->playerbankdetails->getNotDeletedBankInfoList($this->playerId);
        $hasBankAccount = !empty((!empty($bankdetails['deposit']) || !empty($bankdetails['withdrawal']))) ? true : false;
        if(!$hasBankAccount){
            $this->appendToDebugLog('not add bank account',['result' => $hasBankAccount]);
        }

        return $hasBankAccount;
    }

    protected function isFilledBirthdate(){
        $this->load->model(['player_model']);
        $player = $this->player_model->getPlayerInfoDetailById($this->playerId);
        $filledBirthdate = (!empty($player['birthdate']) && ($player['birthdate'] != '1970-01-01')) ? $player['birthdate'] : false;
        if(!$filledBirthdate){
            $this->appendToDebugLog('not valid birthday',['result' => $filledBirthdate]);
        }

        return $filledBirthdate;
    }
	/**
	 * run bonus condition checker
	 * @param  array $description original description in rule
	 * @param  array $extra_info
	 * @param  boolean $dry_run
	 * @return  array ['success'=> success, 'message'=> errorMessageLang, 'continue_process_after_script' => FALSE]
	 */
	protected function runBonusConditionChecker($description, &$extra_info, $dry_run){
        $success = false;
        $errorMessageLang = null;

        $verified_phone = $this->isVerifiedPhone();
        $verified_email = $this->isVerifiedEmail();
        $filled_birthdate = $this->isFilledBirthdate();
        $hasBankAccount = $this->isFilledBankAccount();

        if($verified_phone){
            if($verified_email){
                if($filled_birthdate){
                    if($hasBankAccount){
                        $success = true;
                    }else{
                        $errorMessageLang = 'Please Add Bank Account';
                    }
                }else{
                    $errorMessageLang = 'promo_custom.birthdate_not_set_yet';
                }
            }else{
                $errorMessageLang = 'promo.rule_is_player_verified_email';
            }
        }else{
            $errorMessageLang = 'promo.rule_is_player_verified_mobile';
        }

        $this->appendToDebugLog('verified_phone ['.$verified_phone.'], verified_email ['.$verified_email.'], filled_birthdate ['.$filled_birthdate.']');

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
}
