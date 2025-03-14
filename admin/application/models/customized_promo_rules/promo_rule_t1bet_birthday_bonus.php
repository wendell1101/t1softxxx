<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 *
 * Birthday Bonus
 *
 * OGP-29530
 *
 * 生日当月允许申请
 * 1倍取款條件 bonus 
 * 生日30內天至少五筆存款

 * Bonus condition && Bonus release:
    {
        "class": "promo_rule_t1bet_birthday_bonus",
        "bonus_settings": {
            "VIP8":  { "bonus": 88   },
            "VIP9":  { "bonus": 188  },
            "VIP10": { "bonus": 288  },
            "VIP11": { "bonus": 388  },
            "VIP12": { "bonus": 588  },
            "VIP13": { "bonus": 888  },
            "VIP14": { "bonus": 1888 },
            "VIP15": { "bonus": 5888 }
        },
        "days_before_birthday": 30,
        "reached_deposit_count":5
    }

    * QA Testing (can add specific info into custom script):
    {
        "birthdate": "1999-07-07",
        "thisMonth": "07",
        "deposit_count" :10
    }

 */
class Promo_rule_t1bet_birthday_bonus extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_t1bet_birthday_bonus';
	}

	/**
	 * run bonus condition checker
	 * @param  array $description original description in rule
	 * @param  array $extra_info
	 * @param  boolean $dry_run
	 * @return  array ['success'=> success, 'message'=> errorMessageLang]
     * condition
     檢查是否於生日當月
     檢查30天內至少五筆存款
	 */
	protected function runBonusConditionChecker($description, &$extra_info, $dry_run){
		
        $errorMessageLang = null;
        $result = $this->checkCustomizeBounsCondition($description, $extra_info, $errorMessageLang);

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
        $result = $this->checkCustomizeBounsCondition($description, $extra_info, $errorMessageLang);

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

    protected function getCalculateDate($description){
        $player = $this->player_model->getPlayerInfoDetailById($this->playerId);
        $birthdate = !empty($description['birthdate']) ? $description['birthdate'] : $player['birthdate'];
        $days_before_birthday = !empty($description['days_before_birthday']) ? $description['days_before_birthday'] : 30;

        $thisY = date('Y');
        $getbirthdateMD = date('m-d', strtotime($birthdate));
        $thisYbirthdate = $thisY.'-'.$getbirthdateMD;

        $fromBirthdate = strtotime('-' . $days_before_birthday .' day', strtotime($thisYbirthdate));
        $toBirthdate = strtotime('-1 day', strtotime($thisYbirthdate));

        $this->appendToDebugLog('before getThisMonthRange',['fromBirthdate'=>$fromBirthdate,'toBirthdate'=>$toBirthdate,'birthdate'=>$birthdate,'days_before_birthday'=>$days_before_birthday]);

        $fromBirthdate = date('Y-m-d', $fromBirthdate).' '.Utils::FIRST_TIME;
        $toBirthdate = date('Y-m-d',$toBirthdate).' '.Utils::LAST_TIME;
        $fromDate = !empty($description['fromDate']) ? $description['fromDate'] : $fromBirthdate;
        $toDate = !empty($description['toDate']) ? $description['toDate'] : $toBirthdate;
        $this->appendToDebugLog('after getThisMonthRange',['fromDate'=>$fromDate,'toDate'=>$toDate]);

        return [$fromDate, $toDate];
    }

	private function checkCustomizeBounsCondition($description, $extra_info, $errorMessageLang){

        $success = false;
        $bonus_amount = 0;
        $bonus_settings = $description['bonus_settings'];
        $deposit_times = !empty($description['reached_deposit_count']) ? $description['reached_deposit_count'] : 5;
        $inBirthMonth = $this->inBirthMonth($description);

        list($fromDate, $toDate) = $this->getCalculateDate($description);
        $countDeposit = !empty($description['deposit_count']) ? $description['deposit_count'] : $this->callHelper('countDepositByPlayerId',[$fromDate, $toDate]);
        $isReachedDeposit = ($countDeposit >= $deposit_times) ? true : false;

        $this->appendToDebugLog('player deposit count',['count'=>$countDeposit]);
        $this->appendToDebugLog('player should reached Deposit count',['count'=>$deposit_times]);
        $this->appendToDebugLog('player level id',['Id'=>$this->levelId]);

        if(array_key_exists($this->levelId, $bonus_settings)){
            if($inBirthMonth){
                if($isReachedDeposit){
                    $success = true;
                    $bonus_amount = $bonus_settings[$this->levelId]['bonus'];
                }else{
                    $errorMessageLang = 'promo_custom.failed_deposit_gt_1000_for_conseq_3_months_before_application';
                }    
            }else{
                $errorMessageLang = 'promo_custom.not_in_the_month_of_birthdate';
            }   
        }else{
            $errorMessageLang = 'notify.35';
        }

        return $result=['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonus_amount];
	}
}
