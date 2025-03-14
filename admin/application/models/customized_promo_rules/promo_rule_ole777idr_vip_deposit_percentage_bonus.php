<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 *
 * OGP-32088 - 127% BONUS MIKI
 * 
 * 玩家申請前需先存款, 須符合VIP最低存款金額才允許申請
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

condition:
{
    "class": "promo_rule_ole777idr_vip_deposit_percentage_bonus",
    "allowed_date":{
        "start_date": "10",
        "end_date": "16"
    },
    "not_allow_promo_on_other_days": {
        "promorule_ids": ["id_1", "id_2", "id_3"]
    },
    "check_unfinished_wc": {
        "promorule_ids": ["id_1", "id_2", "id_3"]
    },
    "bonus_settings" : {
        "VIP0" : {"min_deposit": 100, "bonus_percentage":  77, "max_bonus": 1000, "bet_condition_times": 15},
        "VIP1" : {"min_deposit":  50, "bonus_percentage": 127, "max_bonus": 2400, "bet_condition_times": 12},
        "VIP2" : {"min_deposit":  50, "bonus_percentage": 127, "max_bonus": 2400, "bet_condition_times": 12},
        "VIP3" : {"min_deposit":  50, "bonus_percentage": 127, "max_bonus": 2400, "bet_condition_times": 12},
        "VIP4" : {"min_deposit":  50, "bonus_percentage": 127, "max_bonus": 2400, "bet_condition_times": 12},
        "VIP5" : {"min_deposit":  50, "bonus_percentage": 127, "max_bonus": 2400, "bet_condition_times": 12},
        "VIP6" : {"min_deposit":  50, "bonus_percentage": 127, "max_bonus": 2400, "bet_condition_times": 12},
        "VIP7" : {"min_deposit":  50, "bonus_percentage": 127, "max_bonus": 2400, "bet_condition_times": 12},
        "VIP8" : {"min_deposit":  50, "bonus_percentage": 127, "max_bonus": 2400, "bet_condition_times": 12},
        "VIP9" : {"min_deposit":  50, "bonus_percentage": 127, "max_bonus": 2400, "bet_condition_times": 12}
    }
}
 *
 */
class Promo_rule_ole777idr_vip_deposit_percentage_bonus extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_ole777idr_vip_deposit_percentage_bonus';
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
        $existPromoRecord = false;
        if(!empty($description['not_allow_promo_on_other_days']['promorule_ids'])){
            $promorule_ids = $description['not_allow_promo_on_other_days']['promorule_ids'];
            $this->_checkNotAllowOtherPromoRecords($promorule_ids, $existPromoRecord);
        }
        if($existPromoRecord){
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
        $this->appendToDebugLog('get bonus_amount and deposit_amount and times', ['bonus_amount'=>$bonus_amount, 'deposit_amount'=>$deposit_amount, 'times'=>$times]);

        if($times > 0){
            $withdrawal_condition_amount = ($bonus_amount + $deposit_amount) * $times;
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
        $lastDepositAmount = $this->depositAmount;
        
        $bonus_settings = $description['bonus_settings'];
        if(!isset($bonus_settings[$this->levelId])){
            $errorMessageLang = 'notify.35';
            return ['success' => $success, 'message' => $errorMessageLang];
        }
        
        $settings = $bonus_settings[$this->levelId];
        $min_deposit = $settings['min_deposit'];
        if($lastDepositAmount < $min_deposit){
            $errorMessageLang = 'promo_custom.deposit_donot_match_the_requirement';
            return ['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonus_amount];
        }

        $bonus_percentage = $settings['bonus_percentage'];
        $max_bonus = $settings['max_bonus'];
        $bet_condition_times = $settings['bet_condition_times'];

        $this->appendToDebugLog('cehck release bonus', [
            'min_deposit' => $min_deposit,
            'lastDepositAmount' => $lastDepositAmount,
            'bonus_percentage' => $bonus_percentage,
            'max_bonus' => $max_bonus,
            'bet_condition_times' => $bet_condition_times,
            'success' => $success
        ]);


        $success = true;
        $bonus_amount = $lastDepositAmount * ($bonus_percentage/100);
        if($bonus_amount >= $max_bonus){
            $bonus_amount = $max_bonus;
        }
        
		$result=['success'=>$success, 'message'=>$errorMessageLang, 'bonus_amount'=>$bonus_amount, 'bet_condition_times'=>$bet_condition_times, 'deposit_amount'=>$lastDepositAmount];
		return $result;
	}
}

