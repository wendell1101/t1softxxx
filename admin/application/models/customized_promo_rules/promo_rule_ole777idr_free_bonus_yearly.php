<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 *
 * Feast Holiday Bonus
 * OGP-22224
 * 允许玩家一年申请一次免费奖金
 *
 * OGP-35752: add last_n_days_have_deposit

condition:
{
    "class": "promo_rule_ole777idr_free_bonus_yearly",
    "last_n_days_have_deposit": "180",
    "verify_mobile": true, //(optional)
    "verify_email": true,  //(optional)
    "allow_date": {
        "0": {"date": "05-12"},
        "1": {"date": "12-25"}
    },
    "bonus_settings":{
        "VIP2" : {"bonus_amount": 27},
        "VIP3" : {"bonus_amount": 37},
        "VIP4" : {"bonus_amount": 177},
        "VIP5" : {"bonus_amount": 577},
        "VIP6" : {"bonus_amount": 777},
        "VIP7" : {"bonus_amount": 3777},
        "VIP8" : {"bonus_amount": 5777},
        "VIP9" : {"bonus_amount": 7777}
    }
}
 *
 *
 *
 */
class Promo_rule_ole777idr_free_bonus_yearly extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_ole777idr_free_bonus_yearly';
	}

	/**
	 * run bonus condition checker
	 * @param  array $description original description in rule
	 * @param  array $extra_info
	 * @param  boolean $dry_run
	 * @return  array ['success'=> success, 'message'=> errorMessageLang, 'continue_process_after_script' => TRUE]
	 */
	protected function runBonusConditionChecker($description, &$extra_info, $dry_run){
		$success=false;
		$errorMessageLang=null;

        $today=$this->utils->getTodayForMysql();
        if($this->process_mock('today', $today)){
            $this->appendToDebugLog('use mock today', ['today'=>$today]);
        }

        $met_date = false;
        $d=new DateTime($today);
        $currentDate=$d->format('Y-m-d');
        $currentDateTime=$d->format('Y-m-d H:i:s');
        if(!empty($description['allow_date'])){
            $allow_dates = [];
            foreach ($description['allow_date'] as $k => $v){
                $allow_dates[]['date'] = $d->format('Y-') . $v['date'];
            }

            $allow_request_date = array_column($allow_dates, 'date');

            if(in_array($currentDate, $allow_request_date)){
                $met_date = true;
            }
            $this->appendToDebugLog('runBonusConditionChecker result', ['allow_request_date'=>$allow_request_date, 'current_date'=>$currentDate]);
        }else{
            $applicationPeriodStart = $this->promorule['applicationPeriodStart'];
            if($currentDateTime >= $applicationPeriodStart){
                $met_date = true;
            }
            $this->appendToDebugLog('runBonusConditionChecker result', ['promo allow claim from'=>$applicationPeriodStart, 'current_date_time'=>$currentDate]);
        }

        # check first apply this year
        $count_approved_promo = $this->callHelper('count_approved_promo',[$this->promorulesId, self::DATE_TYPE_YEAR]);
		$bonus_settings=$description['bonus_settings'];
		$levelId = $this->levelId;

        $verified_phone = $this->isVerifiedPhone($description);
        $verified_email = $this->isVerifiedEmail($description);
        $last_n_days_have_deposit = !empty($description['last_n_days_have_deposit']) ? $description['last_n_days_have_deposit'] : null;
        $verified_deposit = $this->callHelper('isDepositInPeriod', [$last_n_days_have_deposit]);

        if(!$verified_phone){
            $errorMessageLang = 'promo.rule_is_player_verified_mobile';
            return ['success'=>$success, 'message'=>$errorMessageLang];
        }

        if(!$verified_email){
            $errorMessageLang = 'promo.rule_is_player_verified_email';
            return ['success'=>$success, 'message'=>$errorMessageLang];
        }

        if(!$met_date){
            $errorMessageLang = 'Not right date';
            return ['success'=>$success, 'message'=>$errorMessageLang];
        }

        if(!empty($count_approved_promo)){
            $errorMessageLang = 'notify.83';
            return ['success'=>$success, 'message'=>$errorMessageLang];
        }

        if(!array_key_exists($levelId, $bonus_settings)){
            $errorMessageLang = 'notify.35';
            return ['success'=>$success, 'message'=>$errorMessageLang];
        }

        if(!$verified_deposit){
            $errorMessageLang = 'notify.80';
            return ['success'=>$success, 'message'=>$errorMessageLang];
        }

        $success = true;
        $this->appendToDebugLog('runBonusConditionChecker result', ['levelId'=>$levelId, 'count_approved_promo'=>$count_approved_promo, 'today'=>$currentDate]);

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
		$success=false;
		$errorMessageLang=null;
		$bonus_amount=0;

        $today=$this->utils->getTodayForMysql();
        if($this->process_mock('today', $today)){
            $this->appendToDebugLog('use mock today', ['today'=>$today]);
        }

        $met_date = false;
        $d=new DateTime($today);
        $currentDate=$d->format('Y-m-d');
        $currentDateTime=$d->format('Y-m-d H:i:s');
        if(!empty($description['allow_date'])){
            $allow_dates = [];
            foreach ($description['allow_date'] as $k => $v){
                $allow_dates[]['date'] = $d->format('Y-') . $v['date'];
            }

            $allow_request_date = array_column($allow_dates, 'date');

            if(in_array($currentDate, $allow_request_date)){
                $met_date = true;
            }
            $this->appendToDebugLog('runBonusConditionChecker result', ['allow_request_date'=>$allow_request_date, 'current_date'=>$currentDate]);
        }else{
            $applicationPeriodStart = $this->promorule['applicationPeriodStart'];
            if($currentDateTime >= $applicationPeriodStart){
                $met_date = true;
            }
            $this->appendToDebugLog('runBonusConditionChecker result', ['promo allow claim from'=>$applicationPeriodStart, 'current_date_time'=>$currentDate]);
        }

        # check first apply this year
        $count_approved_promo = $this->callHelper('count_approved_promo',[$this->promorulesId, self::DATE_TYPE_YEAR]);
        $bonus_settings=$description['bonus_settings'];
        $levelId = $this->levelId;

        $verified_phone = $this->isVerifiedPhone($description);
        $verified_email = $this->isVerifiedEmail($description);
        $last_n_days_have_deposit = !empty($description['last_n_days_have_deposit']) ? $description['last_n_days_have_deposit'] : null;
        $verified_deposit = $this->callHelper('isDepositInPeriod', [$last_n_days_have_deposit]);

        if(!$verified_phone){
            $errorMessageLang = 'promo.rule_is_player_verified_mobile';
            return ['success'=>$success, 'message'=>$errorMessageLang];
        }

        if(!$verified_email){
            $errorMessageLang = 'promo.rule_is_player_verified_email';
            return ['success'=>$success, 'message'=>$errorMessageLang];
        }

        if(!$met_date){
            $errorMessageLang = 'Not right date';
            return ['success'=>$success, 'message'=>$errorMessageLang];
        }

        if(!empty($count_approved_promo)){
            $errorMessageLang = 'notify.83';
            return ['success'=>$success, 'message'=>$errorMessageLang];
        }

        if(!array_key_exists($levelId, $bonus_settings)){
            $errorMessageLang = 'notify.35';
            return ['success'=>$success, 'message'=>$errorMessageLang];
        }

        if(!$verified_deposit){
            $errorMessageLang = 'notify.80';
            return ['success'=>$success, 'message'=>$errorMessageLang];
        }

        $setting=$bonus_settings[$levelId];
        $success = true;
        $bonus_amount = $setting['bonus_amount'];

		$result=['success'=>$success, 'message'=>$errorMessageLang, 'bonus_amount'=>$bonus_amount];
		return $result;
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

