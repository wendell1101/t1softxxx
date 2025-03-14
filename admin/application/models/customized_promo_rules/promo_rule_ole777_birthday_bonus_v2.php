<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 *
 * Birthday Bonus
 *
 * OGP-18455
 *
 * 生日当月允许申请，当月存款和流水符合条件，按等于发奖金，一年一次

 * Bonus condition && Bonus release:
    {
        "class": "promo_rule_ole777_birthday_bonus_v2",
        "bonus_settings": {
            "1": { "bonus": 100, "min_deposit": 500, "min_bet": 0},
            "2": { "bonus": 100, "min_deposit": 500, "min_bet": 0},
            "3": { "bonus": 377, "min_deposit": 0, "min_bet": 30000},
            "4": { "bonus": 577, "min_deposit": 0, "min_bet": 30000},
            "5": { "bonus": 1377, "min_deposit": 0, "min_bet": 30000},
            "6": { "bonus": 1777, "min_deposit": 0, "min_bet": 30000},
            "7": { "bonus": 3777, "min_deposit": 0, "min_bet": 30000},
            "8": { "bonus": 5777, "min_deposit": 0, "min_bet": 30000},
            "9": { "bonus": 7777, "min_deposit": 0, "min_bet": 30000}
        }
    }

 * QA Testing (can add specific info into custom script):
    {
        "birthdate": "1999-07-07",
        "thisMonth": "07",
        "completed_player_info": true,
        "verified_phone": true,
        "bet": 500,
        "deposit": 30000
    }

 *
 *
 */
class Promo_rule_ole777_birthday_bonus_v2 extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_ole777_birthday_bonus_v2';
	}

	/**
	 * run bonus condition checker
	 * @param  array $description original description in rule
	 * @param  array $extra_info
	 * @param  boolean $dry_run
	 * @return  array ['success'=> success, 'message'=> errorMessageLang]
     * condition
     1.檢查是否於生日當月
     2.完善註冊 & 驗證手機號
     3.取得玩家vip level並檢查該level所需存款與流水
	 */
	protected function runBonusConditionChecker($description, &$extra_info, $dry_run){
		$success = false;
		$errorMessageLang = null;
        $bonus_settings=$description['bonus_settings'];

        $inBirthMonth = $this->inBirthMonth($description);
        $completed_player_info = $this->completed_player_info($description);
        $verified_phone = $this->isVerifiedPhone($description);
        list($fromDate, $toDate) = $this->getCalculateDate($description);
        list($met_deposit, $met_bet) = $this->checkPlayerDepositAndBet($bonus_settings, $fromDate, $toDate, $description);

        if($inBirthMonth && $completed_player_info && $verified_phone && $met_deposit && $met_bet){
            $success = true;
		}else{
			$errorMessageLang='promo_rule.common.error';
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
		$success=false;
		$errorMessageLang=null;
		$bonus_amount=0;

		$bonus_settings=$description['bonus_settings'];

        $inBirthMonth = $this->inBirthMonth($description);
        $completed_player_info = $this->completed_player_info($description);
        $verified_phone = $this->isVerifiedPhone($description);
        list($fromDate, $toDate) = $this->getCalculateDate($description);
        list($met_deposit, $met_bet) = $this->checkPlayerDepositAndBet($bonus_settings, $fromDate, $toDate, $description);

        if($inBirthMonth && $completed_player_info && $verified_phone && $met_deposit && $met_bet){
            $success = true;
            $bonus_amount = $bonus_settings[$this->levelId]['bonus'];
        }else{
            $errorMessageLang='promo_rule.common.error';
        }

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

    protected function completed_player_info($description){
        $conditionResult = $this->player_model->getPlayerAccountInfoStatus($this->playerId);
        $completed_player_info = $conditionResult['status'];

        $completed_player_info = isset($description['completed_player_info']) ? $description['completed_player_info'] : $completed_player_info;
        if(!$completed_player_info){
            $conditionResultMissingFields = !empty($conditionResult['missing_fields']) ? $conditionResult['missing_fields'] : NULL;
            $this->appendToDebugLog('not complete player info',['missing_fields'=>$conditionResultMissingFields]);
        }

        return $completed_player_info;
    }

    protected function isVerifiedPhone($description){
        $verified_phone = $this->player_model->isVerifiedPhone($this->playerId);

        $verified_phone = !empty($description['verified_phone']) ? $description['verified_phone'] : $verified_phone;
        if(!$verified_phone){
            $this->appendToDebugLog('not verified phone',['verified_phone'=>$verified_phone]);
        }

        return $verified_phone;
    }

    protected function getCalculateDate($description){
        list($fromDate, $toDate) = $this->utils->getThisMonthRange();
        $fromDate = !empty($description['fromDate']) ? $description['fromDate'] : $fromDate;
        $toDate = !empty($description['toDate']) ? $description['toDate'] : $toDate;
        $this->appendToDebugLog('getThisMonthRange',['fromDate'=>$fromDate,'toDate'=>$toDate]);

        return [$fromDate, $toDate];
    }

	private function checkPlayerDepositAndBet($bonus_settings, $fromDate, $toDate, $description){
        $met_deposit = false;
        $met_bet = false;
        $existLevel = false;
        if(isset($bonus_settings[$this->levelId])){
            $existLevel = true;
        }

        if($existLevel){
            $setting = $bonus_settings[$this->levelId];
            $min_deposit = !empty($setting['min_deposit']) ? $setting['min_deposit'] : 0;
            $min_bet = !empty($setting['min_bet']) ? $setting['min_bet'] : 0;
            list($bet, $deposit)=$this->callHelper('getBetsAndDepositByDate', [$fromDate, $toDate]);

            $deposit = !empty($description['deposit']) ? $description['deposit'] : $deposit;
            $bet = !empty($description['bet']) ? $description['bet'] : $bet;

            $this->appendToDebugLog('bonus_setting and getBetsAndDepositByDate',['bonus_setting'=>$setting,
             'min_deposit'=>$min_deposit, 'deposit'=>$deposit, 'min_bet'=>$min_bet,'bet'=>$bet]);

            //only check deposit amount
            if($min_deposit > 0){
                $met_deposit = $deposit >= $min_deposit ? true :false;
            }else{
                $met_deposit = true;
            }

            //only check bet amount
            if($min_bet > 0){
                $met_bet = $bet >= $min_bet ? true : false;
            }else{
                $met_bet = true;
            }

            $this->appendToDebugLog('met deposit and bet result', ['met_deposit' => $met_deposit, 'met_bet' => $met_bet]);
        }else{
            $this->appendToDebugLog('not valid levelId', ['currentlevelId' => $this->levelId, 'bonus_setting' => $bonus_settings]);
        }

        return [$met_deposit, $met_bet];
	}
}
