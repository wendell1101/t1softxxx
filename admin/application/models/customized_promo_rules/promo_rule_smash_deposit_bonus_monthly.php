<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 *
 * 每月会员充值日
 *
 * OGP-24286
 *
 * 每月18号充值固定金额将获得平台赠送的奖金
 * 不限充值次数

condition:
{
    "class": "promo_rule_smash_deposit_bonus_monthly",
    "allowed_day": 18,
    "bonus_settings": [
        {"fix_deposit_amount": 500, "bonus_amount": 50},
        {"fix_deposit_amount": 1000, "bonus_amount": 110},
        {"fix_deposit_amount": 2000, "bonus_amount": 250},
        {"fix_deposit_amount": 5000, "bonus_amount": 700},
        {"fix_deposit_amount": 10000, "bonus_amount": 1500},
        {"fix_deposit_amount": 20000, "bonus_amount": 3200},
        {"fix_deposit_amount": 50000, "bonus_amount": 9000}
    ]
}

 *
 *
 */
class Promo_rule_smash_deposit_bonus_monthly extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_smash_deposit_bonus_monthly';
	}

	/**
	 * run bonus condition checker
	 * @param  array $description original description in rule
	 * @param  array $extra_info
	 * @param  boolean $dry_run
	 * @return  array ['success'=> success, 'message'=> errorMessageLang, 'continue_process_after_script' => TRUE]
	 */
	protected function runBonusConditionChecker($description, &$extra_info, $dry_run){
		$success = false;
		$errorMessageLang = null;

		$result = ['success' => $success, 'message' => $errorMessageLang, 'continue_process_after_script' => TRUE];

		$today = $this->utils->getTodayForMysql();
		if($this->process_mock('today', $today)){
			//use mock data
			$this->appendToDebugLog('use mock today', ['today' => $today]);
		}

		$bonus_settings = $description['bonus_settings'];
        $allowed_day = $description['allowed_day'];
        $this->appendToDebugLog('check allowed_date', ['allowed_date' => $allowed_day]);

        $d = new DateTime($today);
        $currentDate = $d->format('Y-m-d');
        $allowDate = $d->format('Y-m-') . $allowed_day;
        $endAt = $this->utils->getNowForMysql();

        $trans = $this->callHelper('getLastDepositByDate', [$allowDate.' '.Utils::FIRST_TIME, $endAt]);
        $this->appendToDebugLog('check date and trans id', ['currentDate' => $currentDate, 'endAt' => $endAt, 'trans' => $trans]);


        if($currentDate == $allowDate){
            if(!empty($trans)){
                $met_fix_deposit_amount = false;
                $result['deposit_tran_id'] = $trans['id'];
                foreach ($bonus_settings as $setting){
                    $target_amount = $setting['fix_deposit_amount'];
                    if($target_amount == $trans['amount']){
                        $met_fix_deposit_amount = true;
                        break;
                    }
                }

                $this->appendToDebugLog('check deposit met fix_deposit_amount or not', ['met fix deposit amount' => $met_fix_deposit_amount]);

                if($met_fix_deposit_amount){
                    $result['success']=true;
                }else{
                    $result['message']='promo_custom.deposit_donot_match_the_requirement';
                }
            }else{
                $result['message']='promo_custom.no_deposit_record_found';
            }
        }else{
            $result['message']='Sorry, you cannot apply this promotion yet';
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

		$success=false;
		$errorMessageLang=null;
		$bonus_amount=0;

        $today = $this->utils->getTodayForMysql();
        if($this->process_mock('today', $today)){
            //use mock data
            $this->appendToDebugLog('use mock today', ['today' => $today]);
        }

        $bonus_settings = $description['bonus_settings'];
        $allowed_day = $description['allowed_day'];

        $d = new DateTime($today);
        $currentDate = $d->format('Y-m-d');
        $allowDate = $d->format('Y-m-') . $allowed_day;
        $endAt = $this->utils->getNowForMysql();

        $trans = $this->callHelper('getLastDepositByDate', [$allowDate.' '.Utils::FIRST_TIME, $endAt]);
        $this->appendToDebugLog('check date and trans id', ['currentDate' => $currentDate, 'endAt' => $endAt, 'trans' => $trans]);

        if($currentDate == $allowDate){
            if(!empty($trans)){
                $result['deposit_tran_id'] = $trans['id'];
                foreach ($bonus_settings as $setting){
                    $target_amount = $setting['fix_deposit_amount'];
                    if($target_amount == $trans['amount']){
                        $success = true;
                        $bonus_amount = $setting['bonus_amount'];
                        break;
                    }
                }
            }
        }

		$result=['success'=>$success, 'message'=>$errorMessageLang, 'bonus_amount'=>$bonus_amount];
		return $result;
	}
}

