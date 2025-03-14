<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 *
 * OGP-32979
 * 40% PROMO NGABUBURIT (SLOT)
 * 40% PROMO NGABUBURIT (SPORT)
 * 40% PROMO NGABUBURIT (LIVE CASINO)
 * 
 * Slot / Live Casino / Sports 申請前需先存款, 若申請過其中一個, 則不可再申請另外兩個
 * 期間內允许玩家申请一次奖金
 * 
 * 最低存款额为200, 獎金40%, 最高1000
 * 
 * 存款可選優惠, 或是存款成功後至優惠頁申請
 * 
 * 有互斥條件, 申請slot/live casino/sports其中一個(新)優惠前 必須先完成互斥(舊)優惠的流水
 * 流水完成的定義是, (舊)優惠的withdraw_condition的is_finished=1
 * 因為ole777idr 不使用 檢查取款條件的 feature, 客戶只要求檢查互斥優惠的流水是否完成(is_finished=1)
 * is_finished=1 代表(特定取款條件的)流水完成
 * 
 * existUnifinishWcRecords 有加入 $_extra_info['updateWithdrawalCondition'] = true;
 * 代表再檢查互斥條件前, 會先更新取款條件
 * 

condition:
{
    "class": "promo_rule_ole777idr_common_deposit_bonus",
    "allowed_date":{
        "start": "26", // "start_date": "2024-05-26",
        "end": "28"    // "end_date": "2024-05-28"
    },
    "min_deposit": 200,
    "max_bonus": 1000,
    "bonus_percentage": 40,
    "check_unfinished_wc": {
        "promorule_ids": ["id_1", "id_2", "id_3"]
    },
    "not_allow_promo_on_the_same_day": {
        "promorule_ids": ["17501", "17502", "17503", "17468", "17082", "17296", "17087", "17196", "17114", "16909", "16953", "17166", "16908", "16952", "16970", "17633","17684"]
    },
    "not_allow_promo_on_other_days": {
        "promorule_ids": ["17679", "17680"]
    }
}
 *
 */
class Promo_rule_ole777idr_common_deposit_bonus extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_ole777idr_common_deposit_bonus';
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
        $allowed_date = !empty($description['allowed_date']) ? $description['allowed_date'] : [];
        
        $today = $this->utils->getTodayForMysql();
        if($this->process_mock('today', $today)){
            $this->appendToDebugLog('use mock today', ['today' => $today]);
        }
        
        # check date
        $d = new DateTime($today);
        $currentDate = $d->format('Y-m-d');
        if(empty($allowed_date)){
            $minDate = $currentDate;
            $maxDate = $currentDate;
        }

        if(!empty($allowed_date['start_date']) && !empty($allowed_date['end_date'])){
            $minDate = $this->utils->formatDateForMysql(new DateTime($allowed_date['start_date']));
            $maxDate = $this->utils->formatDateForMysql(new DateTime($allowed_date['end_date']));
        }else if(!empty($allowed_date['start']) && !empty($allowed_date['end'])){
            $minDate = $d->format('Y-m-').$allowed_date['start'];
            $maxDate = $d->format('Y-m-').$allowed_date['end'];
        }

        $fromDate = $minDate.' '.Utils::FIRST_TIME;
        $toDate = $maxDate.' '.Utils::LAST_TIME;
        $this->appendToDebugLog('allow date', ['minDate' => $minDate, 'maxDate' => $maxDate, 'currentDate' => $currentDate]);

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

        $isReleasedBonusToday = false;
        if(!empty($description['not_allow_promo_on_the_same_day']['promorule_ids'])){
            $_promorule_ids = $description['not_allow_promo_on_the_same_day']['promorule_ids'];
            $this->_checkNotAllowOtherPromoOnTheSameDay($_promorule_ids, $isReleasedBonusToday);
        }
        if(!empty($isReleasedBonusToday)){
            $errorMessageLang = 'notify.134';
            return ['success' => $success, 'message' => $errorMessageLang];
        }

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
		$success = false;
		$errorMessageLang = null;
		$bonus_amount = 0;

        $lastDepositAmount = $this->depositAmount;
        $min_deposit = $description['min_deposit'];
        $bonus_percentage = $description['bonus_percentage'];
        $max_bonus = $description['max_bonus'];
        $this->appendToDebugLog('cehck release bonus', [
            'min_deposit' => $min_deposit,
            'lastDepositAmount' => $lastDepositAmount,
            'bonus_percentage' => $bonus_percentage,
            'success' => $success
        ]);

        if($lastDepositAmount < $min_deposit){
            $errorMessageLang = 'promo_custom.deposit_donot_match_the_requirement';
            return ['success' => $success, 'message' => $errorMessageLang];
        }

        $success = true;
        $bonus_amount = $lastDepositAmount * ($bonus_percentage/100);
        if($bonus_amount >= $max_bonus){
            $bonus_amount = $max_bonus;
        }
        
		$result=['success'=>$success, 'message'=>$errorMessageLang, 'bonus_amount'=>$bonus_amount];
		return $result;
	}
}

