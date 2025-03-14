<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 * OGP-21982
 * OGP-23967
 *
 * 只能申请一次
 * 需完成手机验证和Email验证
 * 從註冊開始當申請當下, 存款需累積超過250

condition:
{
    "class": "promo_rule_ole777_verification_bonus",
    "verified_phone": true,
    "verified_email": true,
    "min_deposit": 250
}

 *
 *
 */
class Promo_rule_ole777_verification_bonus extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_ole777_verification_bonus';
	}

    protected function isVerifiedPhone($description){
        $verified_phone = false;

        if(!empty($description['verified_phone']) && $description['verified_phone']){
            $verified_phone = $this->player_model->isVerifiedPhone($this->playerId);
        }

        if(!$verified_phone){
            $this->appendToDebugLog('not verified phone',['verified_phone'=>$verified_phone]);
        }

        return $verified_phone;
    }

    protected function isVerifiedEmail($description){
        $verified_email = false;

        if(!empty($description['verified_email']) && $description['verified_email']){
            $verified_email = $this->player_model->isVerifiedEmail($this->playerId);
        }

        if(!$verified_email){
            $this->appendToDebugLog('not verified email',['verified_email'=>$verified_email]);
        }

        return $verified_email;
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

        $verified_phone = $this->isVerifiedPhone($description);
        $verified_email = $this->isVerifiedEmail($description);

        $startAt = $this->callHelper('get_date_type', [self::REGISTER_DATE]);
        $endAt = $this->utils->getNowForMysql();

        $min_deposit = !empty($description['min_deposit']) ? $description['min_deposit'] : 0;
        $total_deposit = $this->callHelper('sum_deposit_amount', [$startAt, $endAt, 0]);

        $this->appendToDebugLog('total deposit',['total deposit' => $total_deposit, 'min deposit' => $min_deposit,
            'player create at' => $startAt, 'now' => $endAt]);

        $applicationPeriodStart = $this->promorule['applicationPeriodStart'];
        $dateRange = ['start'=>$applicationPeriodStart, 'end'=>$endAt];
        $appliedPromo = $this->callHelper('get_last_released_player_promo', [$this->promorulesId, self::DATE_TYPE_CUSTOMIZE, $dateRange]);
        $this->appendToDebugLog('exist apply record before',['result' => $appliedPromo]);


        if(!$appliedPromo){
            if($verified_phone){
                if($verified_email){
                    if($total_deposit >= $min_deposit){
                        $success = true;
                    }else{
                        $errorMessageLang = 'No enough deposit';
                    }
                }else{
                    $errorMessageLang = 'promo.rule_is_player_verified_email';
                }
            }else{
                $errorMessageLang = 'promo.rule_is_player_verified_mobile';
            }
        }else{
            $errorMessageLang = 'notify.83';
        }

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
        return $this->returnUnimplemented();
	}
}
