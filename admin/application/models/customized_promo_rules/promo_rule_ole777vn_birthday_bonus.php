<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 *
 * Birthday Bonus
 *
 * OGP-25080
 *
 * 生日当月允许申请，流水符合条件，按等于发奖金，一年一次
 * 取款條件 bonus x 3
 * 生日前30天至少投注5000000

 * Bonus condition && Bonus release:
    {
        "class": "promo_rule_ole777vn_birthday_bonus",
        "bonus_settings": {
            "1": { "bonus": 77000, "min_bet": 5000000},
            "2": { "bonus": 107000, "min_bet": 5000000},
            "3": { "bonus": 270000, "min_bet": 5000000},
            "4": { "bonus": 370000, "min_bet": 5000000},
            "5": { "bonus": 770000, "min_bet": 5000000},
            "6": { "bonus": 1070000, "min_bet": 5000000},
            "7": { "bonus": 1700000, "min_bet": 5000000}
        },
        "days_before_birthday": 30
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
class Promo_rule_ole777vn_birthday_bonus extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_ole777vn_birthday_bonus';
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
        $not_allow_bet_after_deposit = $description['donot_allow_any_available_bet_after_deposit'];

        if($not_allow_bet_after_deposit){
            $promorule = $this->promorule;
            $periodFrom = $promorule['applicationPeriodStart'];
            $periodTo = $promorule['hide_date'];
            $trans = $this->callHelper('getLastDepositByDate', [$periodFrom, $periodTo]);
            $this->appendToDebugLog('check date and trans id', ['periodFrom' => $periodFrom, 'periodTo' => $periodTo, 'trans' => $trans]);

            $this->load->model(['game_logs']);
            $playerId = $this->playerId;
            $created_at = $trans['created_at'];

            list($totalBet, $totalWin, $totalLoss) = $this->game_logs->getTotalBetsWinsLossByPlayers($playerId, $created_at, $this->utils->getNowForMysql());
            $totalWin = (float)$totalWin;
            $totalLoss = (float)$totalLoss;
            if($totalWin != 0 || $totalLoss != 0){
                $extra_info['error_message']='notify.promo_donot_allow_any_available_bet_after_deposit';
                $this->appendToDebugLog($extra_info['debug_log'], 'customize promo_donot_allow_any_available_bet_after_deposit',
                    ['created_at'=>$created_at, 'playerId'=>$playerId]);
                return ['success' => $success, 'message' => $extra_info['error_message']];
            }
        }

        $inBirthMonth = $this->inBirthMonth($description);
        list($fromDate, $toDate) = $this->getCalculateDate($description);
        list($met_deposit, $met_bet) = $this->checkPlayerDepositAndBet($bonus_settings, $fromDate, $toDate, $description);

        if($inBirthMonth && $met_bet){
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
        list($fromDate, $toDate) = $this->getCalculateDate($description);
        list($met_deposit, $met_bet) = $this->checkPlayerDepositAndBet($bonus_settings, $fromDate, $toDate, $description);

        if($inBirthMonth && $met_bet){
            $success = true;
            $bonus_amount = $bonus_settings[$this->levelId]['bonus'];
        }else{
            $errorMessageLang='promo_rule.common.error';
        }

		$result=['success'=>$success, 'message'=>$errorMessageLang, 'bonus_amount'=>$bonus_amount/1000];
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

	private function checkPlayerDepositAndBet($bonus_settings, $fromDate, $toDate, $description){
        $met_deposit = false;
        $met_bet = false;
        $existLevel = false;
        if(isset($bonus_settings[$this->levelId])){
            $existLevel = true;
        }

        if($existLevel){
            $setting = $bonus_settings[$this->levelId];
            // $min_deposit = !empty($setting['min_deposit']) ? $setting['min_deposit'] : 0;
            $min_bet = !empty($setting['min_bet']) ? $setting['min_bet'] : 0;
            list($bet, $deposit)=$this->callHelper('getBetsAndDepositByDate', [$fromDate, $toDate]);

            $deposit = !empty($description['deposit']) ? $description['deposit'] : $deposit;
            $bet = !empty($description['bet']) ? $description['bet'] : $bet;

            $this->appendToDebugLog('bonus_setting and getBetsAndDepositByDate',['bonus_setting'=>$setting, 'deposit'=>$deposit, 'min_bet'=>$min_bet,'bet'=>$bet]);

            // //only check deposit amount
            // if($min_deposit > 0){
            //     $met_deposit = $deposit >= $min_deposit ? true :false;
            // }else{
            //     $met_deposit = true;
            // }

            //only check bet amount
            if($min_bet > 0){
                $met_bet = ($bet * 1000) >= $min_bet ? true : false;
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
