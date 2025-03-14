<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 *
 * OGP-33237 - Instant VIP
 * 
 * 非存款優惠
 * 玩家申請前需有優惠期間首存, 須符合VIP最低存款金額才允許申請
 * 期間內允许玩家申请一次奖金
 * 
 * (選填)
 * - check_unfinished_wc: 檢查互斥(舊)優惠的流水
 * - not_allow_promo_on_other_days
 * - not_allow_promo_on_the_same_day
 * 
 * check_unfinished_wc: 
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
    "class": "promo_rule_ole777idr_instant_vip_bonus",
    "check_unfinished_wc": {
        "promorule_ids": ["id_1", "id_2", "id_3"]
    },
    "not_allow_promo_on_other_days": {
        "promorule_ids": ["id_1", "id_2", "id_3"]
    },
    "not_allow_promo_on_the_same_day": {
        "promorule_ids": ["id_1", "id_2", "id_3"]
    },
    "bonus_percentage": 10,
    "bonus_settings" : [
       {"level_id": VIP5, "first_deposit":  2500, "max_bonus":  5000, "bet_condition_times": 5},
       {"level_id": VIP6, "first_deposit":  3500, "max_bonus":  7500, "bet_condition_times": 5},
       {"level_id": VIP7, "first_deposit":  5000, "max_bonus": 10000, "bet_condition_times": 5},
       {"level_id": VIP8, "first_deposit":  7500, "max_bonus": 15000, "bet_condition_times": 5},
       {"level_id": VIP9, "first_deposit": 10000, "max_bonus": 20000, "bet_condition_times": 5}
    ]
}
 *
 */
class Promo_rule_ole777idr_instant_vip_bonus extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_ole777idr_instant_vip_bonus';
	}

	/**
	 * run bonus condition checker
	 * @param  array $description original description in rule
	 * @param  array $extra_info
	 * @param  boolean $dry_run
	 * @return  array ['success'=> success, 'message'=> errorMessageLang, 'continue_process_after_script' => TRUE]
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
            $this->appendToDebugLog('get bonus_amount and deposit_amount and times in times', $withdrawal_condition_amount);
            $success = $withdrawal_condition_amount > 0;
        }else{
            $this->appendToDebugLog('get bonus_amount and deposit_amount and times out');
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
        $errorMessageLang = null;
        $result = $this->checkCustomizeBounsCondition($description, $extra_info, $errorMessageLang);

        return $result;
	}

    private function checkCustomizeBounsCondition($description, &$extra_info, &$errorMessageLang){
        $success = false;
        $bonus_amount = 0;
        $level = $this->levelId;
        $bet_condition_times = 0;
        $firstDepositAmount = 0;
        $bonus_settings = $description['bonus_settings'];

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

        $settings = null;
        if(!empty($bonus_settings) && is_array($bonus_settings)){
            foreach($bonus_settings as $list){
                if($level == $list['level_id']){
                    $settings = $list;
                    break;
                }
            }
        }

        if(!empty($settings)){
            $percentage = !empty($description['bonus_percentage']) ? $description['bonus_percentage'] : 10;
            $promorule = $this->promorule;
            $from = $promorule['applicationPeriodStart'];
            $to = $this->get_date_type(self::TO_TYPE_NOW);

            $getFirstDepositByDate = $this->callHelper('getAnyDepositByDate',[$from, $to, 'first', null, null]);
            $firstDepositAmount = intval($getFirstDepositByDate['amount']);
            $this->appendToDebugLog('first deposit', ['amount' => $firstDepositAmount, 'from' => $from, 'to' => $to, 'level' => $level, 'settings' => $settings]);

            if($firstDepositAmount>=$settings['first_deposit']){
                $success = true;
                $bonus_amount = $firstDepositAmount * ($percentage / 100);
                $bet_condition_times = $settings['bet_condition_times'];
                if($bonus_amount >= $settings['max_bonus']){
                    $bonus_amount = $settings['max_bonus'];
                }
            }else{
                $errorMessageLang = 'promo_custom.deposit_donot_match_the_requirement';
            }
        }else{
            $errorMessageLang = 'promo_custom.not_in_allowed_vip_level';
        }

        return ['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonus_amount, 'bet_condition_times'=>$bet_condition_times, 'deposit_amount'=>$firstDepositAmount];
    }
}

