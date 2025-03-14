<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 *
 * OGP-32109 - DRAGON'S SUPER BONUS
 * 
 * 玩家申請前需先存款, 須符合最低存款金額才允許申請
 * 期間內允许玩家申请一次奖金
 * 
 * New Member, min deposit 100, 77% bonus, max bonus 1000, WC x15
 * VIP 1-9, min deposit 50, 127% bonus, max bonus 2400, WC x12
 * 
 * 存款可選優惠, 或是存款成功後至優惠頁申請
 * 
 * 有互斥條件, 申請優惠前 必須先完成互斥(舊)優惠的流水
 * 流水完成的定義是, (舊)優惠的withdraw_condition的is_finished=1
 * 因為ole777idr 不使用 檢查取款條件的 feature, 客戶只要求檢查互斥優惠的流水是否完成(is_finished=1)
 * is_finished=1 代表(特定取款條件的)流水完成
 * 
 * existUnifinishWcRecords 有加入 $_extra_info['updateWithdrawalCondition'] = true;
 * 代表再檢查互斥條件前, 會先更新取款條件
 * 
 * OGP-32620 adjust wc formula
 * Minus Transfer Condition of Deposit = (Deposit + Bonus) x Times - Deposit x Deposit Times
 * 

condition:
{
    "class": "promo_rule_ole777idr_deposit_percentage_bonus",
    "allowed_date":{
        "start": "10",
        "end": "16"
    },
    "check_unfinished_wc": {
        "promorule_ids": ["id_1", "id_2", "id_3"]
    },
    "not_allow_promo_on_other_days": {
        "promorule_ids": ["id_1", "id_2", "id_3"]
    },
    "bonus_settings" : [
       {"min_deposit":   100, "max_deposit": 2000, "bonus_percentage": 39, "max_bonus": 1000, "bet_condition_times": 12},
       {"min_deposit":  2000, "max_deposit": 5000, "bonus_percentage": 50, "max_bonus": 1500, "bet_condition_times": 10},
       {"min_deposit":  5000, "max_deposit":   -1, "bonus_percentage": 70, "max_bonus": 2500, "bet_condition_times":  8}
    ]
}
 *
 */
class Promo_rule_ole777idr_deposit_percentage_bonus extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_ole777idr_deposit_percentage_bonus';
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
        $allowed_date = $description['allowed_date'];
        
        $today = $this->utils->getTodayForMysql();
        if($this->process_mock('today', $today)){
            $this->appendToDebugLog('use mock today', ['today' => $today]);
        }

        $existUnfinishedWc = false;
        if(!empty($description['check_unfinished_wc']['promorule_ids'])){
            $_promorule_ids = $description['check_unfinished_wc']['promorule_ids'];
            $_extra_info['updateWithdrawalCondition'] = true;
            $existUnfinishedWc = $this->existUnifinishWcRecords($_promorule_ids, null, $_extra_info);
        }

        if($existUnfinishedWc){
            $errorMessageLang = 'promo_rule.common.error';
            $this->appendToDebugLog('existUnfinishedWc', ['exist unfinished mutually exclusive withdraw condition' => $existUnfinishedWc]);
            return ['success' => $success, 'message' => $errorMessageLang];
        }

        //check if player apply the same series of promo on other days
        $isReleasedBonusOtherDays = false;
        if(!empty($description['not_allow_promo_on_other_days']['promorule_ids'])){
            $promorule_ids = $description['not_allow_promo_on_other_days']['promorule_ids'];
            $this->_checkNotAllowOtherPromoRecords($promorule_ids, $isReleasedBonusOtherDays);
        }
        if(!empty($isReleasedBonusOtherDays)){
            $errorMessageLang = 'notify.134';
            return ['success' => $success, 'message' => $errorMessageLang];
        }

        # check date
        $d = new DateTime($today);
        $currentDate = $d->format('Y-m-d');
        if(!empty($allowed_date['start_date']) && !empty($allowed_date['end_date'])){
            $minDate = $this->utils->formatDateForMysql(new DateTime($allowed_date['start_date']));
            $maxDate = $this->utils->formatDateForMysql(new DateTime($allowed_date['end_date']));
        }else if(!empty($allowed_date['start']) && !empty($allowed_date['end'])){
            $minDate = $d->format('Y-m-').$allowed_date['start'];
            $maxDate = $d->format('Y-m-').$allowed_date['end'];
        }

        $this->appendToDebugLog('allow date', ['minDate' => $minDate, 'maxDate' => $maxDate, 'currentDate' => $currentDate]);

        if(($currentDate >= $minDate) && ($currentDate <= $maxDate)){
            $success = true;
        }else{
            $errorMessageLang = 'Not right date';
        }

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
        $success = false;
        $errorMessageLang = null;
        $withdrawal_condition_amount = 0;

        $result = $this->releaseBonus($description, $extra_info, $dry_run);
        $times = $result['bet_condition_times'];
        $bonus_amount = $result['bonus_amount'];
        $deposit_amount = $result['deposit_amount'];
        $timesForDeposit=$this->non_promo_withdraw_setting;
        $this->appendToDebugLog('get bonus_amount and deposit_amount and times', ['bonus_amount'=>$bonus_amount, 'deposit_amount'=>$deposit_amount, 'times'=>$times]);

        if($times > 0){
            $withdrawal_condition_amount = ($bonus_amount + $deposit_amount) * $times - ($deposit_amount * $timesForDeposit);
            $success = $withdrawal_condition_amount > 0;
        }else{
            $errorMessageLang='Lost bet_condition_times in settings';
        }

        $result=['success'=>$success, 'message'=>$errorMessageLang, 'withdrawal_condition_amount'=>round($withdrawal_condition_amount, 2)];
        return $result;
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
		$success = false;
		$errorMessageLang = null;
		$bonus_amount = 0;
        $bonus_percentage = 0;
        $max_bonus = 0;
        $bet_condition_times = 0;
        
        $bonus_settings = $description['bonus_settings'];
        $lastDepositAmount = $this->depositAmount;

        if (!empty($bonus_settings) && is_array($bonus_settings)) {
            foreach ($bonus_settings as $list) {
                if(($list['min_deposit'] <= $lastDepositAmount) &&
                    ($lastDepositAmount < $list['max_deposit'] || $list['max_deposit']<0)){
                    //max_deposit<0 means no limit
                    $success = true;
                    $bonus_percentage = $list['bonus_percentage'];
                    $max_bonus = $list['max_bonus'];
                    $bet_condition_times = $list['bet_condition_times'];
                }
            }
		}

        $this->appendToDebugLog('cehck release bonus', [
            'lastDepositAmount' => $lastDepositAmount,
            'bonus_percentage' => $bonus_percentage,
            'max_bonus' => $max_bonus,
            'bet_condition_times' => $bet_condition_times,
            'success' => $success
        ]);

        if(!$success){
            $errorMessageLang = 'promo_custom.deposit_donot_match_the_requirement';
            return ['success' => $success, 'message' => $errorMessageLang];
        }

        $bonus_amount = $lastDepositAmount * ($bonus_percentage/100);
        if($bonus_amount >= $max_bonus){
            $bonus_amount = $max_bonus;
        }
        
		$result=['success'=>$success, 'message'=>$errorMessageLang, 'bonus_amount'=>$bonus_amount, 'bet_condition_times'=>$bet_condition_times, 'deposit_amount'=>$lastDepositAmount];
		return $result;
	}
}

