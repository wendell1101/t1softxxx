<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 *
 * 100%首存奖金
 *
 * OGP-27520
 * 活動一: Day1
 * 1. 客戶會自己新增首存優惠規則
 * 2. 首存(Day1)只允許 >= 20 BRL 時申請  (玩家可在存款頁申請, Auto Released)
 * 3. 獎金 100%, 最高888 BRL, 都是当天才能领 ，过期不候
 * 4. 取款條件 50x
 * 5. 若Day1無申請首存獎金, 則無法申請活動二的Day2, Day3 獎金
 * 6. 領完首存獎金後, 隔日(Day2)可申請活動二獎金(2 BRL)
 * 7. 領完隔日(Day2)活動二獎金後, 後天(Day3)可申請活動二獎金(5 BRL)
 *
 * 活動二: Day2, Day3
 * 1. Day1有領過活動ㄧ,才可申請活動二
 * 2. 都是当天才能领 ，过期不候
 * 3. Day2 獎金 2 BRL, 玩家需在優惠頁申請, 無取款條件 (玩家申請, Auto Released)
 * 4. Day3 獎金 5 BRL, 玩家需在優惠頁申請, 無取款條件 (玩家申請, Auto Released)
 *

condition:
{
    "class": "promo_rule_smash_first_deposit_free_bonus_everyday",
    "promo_cms_id": id,
    "bonus_settings": {
        "1" : {"bonus_amount": 2},
        "2" : {"bonus_amount": 5}
    }
}

 *
 *
 */
class Promo_rule_smash_first_deposit_free_bonus_everyday extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_smash_first_deposit_free_bonus_everyday';
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
        $result = $this->checkCustomizeBonusCondition($description, $errorMessageLang);

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
		$errorMessageLang=null;
        $result = $this->checkCustomizeBonusCondition($description, $errorMessageLang);

		return $result;
	}

    private function checkCustomizeBonusCondition($description, &$errorMessageLang){
	    $this->load->model(['promorules']);
        $success = false;
        $bonus_amount = 0;

        $bonus_settings = $description['bonus_settings'];
        $promo_cms_id = !empty($description['promo_cms_id']) ? $description['promo_cms_id'] : 0;
        $this->appendToDebugLog('promo cms id', ['result' => $promo_cms_id]);
        if(empty($promo_cms_id)){
            $errorMessageLang = 'Not setting valid promo'; //不允許申請
            return ['success' => $success, 'message' => $errorMessageLang];
        }


        $completed_player_info = $this->completed_player_info($description);
        if(!$completed_player_info){
            $errorMessageLang = 'notify.93';
            return ['success' => $success, 'message' => $errorMessageLang];
        }

        // 活動一
        $firstPromoRuleId = $this->promorules->getPromorulesIdByPromoCmsId($promo_cms_id);

        // 活動二
        $secondPromoRuleId = $this->promorulesId;
        $count_approved_second_promo = $this->callHelper('count_approved_promo', [$secondPromoRuleId, null]);

        if($count_approved_second_promo == 2){
            $errorMessageLang = 'notify.82';
            return ['success' => $success, 'message' => $errorMessageLang];
        }elseif ($count_approved_second_promo == 1){
            // 領活動二(第二次獎金), 檢查前一天有沒有發過活動二獎金
            $date_type = self::DATE_TYPE_YESTERDAY;
            $second_promo_release_datetime = [];
            $count_approved_second_promo_yesterday = $this->callHelper('count_approved_promo', [$secondPromoRuleId, $date_type]);

            if(!empty($description['second_promo_from_datetime']) && !empty($description['second_promo_to_datetime'])){
                $date_type = self::DATE_TYPE_CUSTOMIZE;
                $second_promo_release_datetime['start'] = $description['second_promo_from_datetime'];
                $second_promo_release_datetime['end'] = $description['second_promo_to_datetime'];
                $count_approved_second_promo_yesterday = $this->callHelper('count_approved_promo', [$secondPromoRuleId, $date_type, $second_promo_release_datetime]);
            }
            $this->appendToDebugLog('count approved second promo', ['count_approved_promo date type' => $date_type, 'time range' => $second_promo_release_datetime, 'count' => $count_approved_second_promo_yesterday]);

            if($count_approved_second_promo_yesterday){
                $success = true;
                $bonus_amount = $bonus_settings["2"]["bonus_amount"];
            }else{
                $errorMessageLang = 'promo_rule.common.error'; //前一天未領取優惠, 因此不能領第二天獎金
                return ['success' => $success, 'message' => $errorMessageLang];
            }
        }elseif (empty($count_approved_second_promo)){
            // 領活動二(第ㄧ次獎金), , 檢查前一天有沒有發過活動ㄧ獎金
            $date_type = self::DATE_TYPE_YESTERDAY;
            $first_promo_release_datetime = [];
            $count_approved_first_promo = $this->callHelper('count_approved_promo', [$firstPromoRuleId, $date_type]);

            if(!empty($description['first_promo_from_datetime']) && !empty($description['first_promo_to_datetime'])){
                $date_type = self::DATE_TYPE_CUSTOMIZE;
                $first_promo_release_datetime['start'] = $description['first_promo_from_datetime'];
                $first_promo_release_datetime['end'] = $description['first_promo_to_datetime'];
                $count_approved_first_promo = $this->callHelper('count_approved_promo', [$firstPromoRuleId, $date_type, $first_promo_release_datetime]);
            }
            $this->appendToDebugLog('count approved first promo', ['count_approved_promo date type' => $date_type, 'time range' => $first_promo_release_datetime, 'count' => $count_approved_first_promo]);

            if($count_approved_first_promo){
                $success = true;
                $bonus_amount = $bonus_settings["1"]["bonus_amount"];
            }else{
                $errorMessageLang = 'promo_rule.common.error'; //前一天未領取優惠, 因此不能領第二天獎金
                return ['success' => $success, 'message' => $errorMessageLang];
            }
        }

        return ['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonus_amount, 'continue_process_after_script' => FALSE];
    }

    protected function completed_player_info($description){
        $completed_player_info = true;

        if(!empty($description['completed_player_info']) && $description['completed_player_info']){
            $conditionResult = $this->player_model->getPlayerAccountInfoStatus($this->playerId);
            $completed_player_info = $conditionResult['status'];
        }

        if(!$completed_player_info){
            $conditionResultMissingFields = !empty($conditionResult['missing_fields']) ? $conditionResult['missing_fields'] : NULL;
            $this->appendToDebugLog('not complete player info',['missing_fields'=>$conditionResultMissingFields]);
        }

        return $completed_player_info;
    }
}

