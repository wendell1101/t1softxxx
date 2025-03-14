<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule_mission.php';

/**
 * OGP-30706
 * 活动开始时间：依据实际上线时间为准。账户注册时间为活动上线后的玩家可以参与
 * 活动结束时间：长期有效。
 * 新手有效期：30天（玩家注册后30天参与有效）
 * 定制活动内容：玩家累计存款达到50 R$后 ，手动点击领取按钮 可以领取 1 R$奖励。（限制每个IP只能领取一次）
 * 取款条件：5倍流水取款
 * 示例：
 * 1. 当玩家第一笔存款20 R$，点击领取，提示不符合资格，回传进度40%；
 * 2. 当玩家第二笔存款 20R$，点击领取，提示不符合资格，回传进度80%；
 * 3. 当玩家第三笔存款 20R$, 点击领取，成功领取奖励，回传进度100%；
 * define('MISSION_CONDITION_NOT_MET', 1);
 * define('MISSION_CONDITION_MET_NOT_APPLY', 2);
 * define('MISSION_CONDITION_MET_APPLIED', 3);
 * 
 * Bonus condition 
condition:
{
    "class" : "promo_rule_mission_first_deposit_bonus",
    "subtype": "deposit",
    "bonus_amount" : "1",
    "period_days" : 30,
    "thresh_hold" : 50
}
 *
 *
 */
class Promo_rule_mission_first_deposit_bonus extends Abstract_promo_rule_mission{
    
    const SUBTYPE = 'deposit';

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'promo_rule_mission_first_deposit_bonus';
	}


	/**
	 * run bonus condition checker
	 * @param  array $description original description in rule
	 * @param  array $extra_info
	 * @param  boolean $dry_run
	 * @return  array ['success'=> success, 'message'=> errorMessageLang, 'continue_process_after_script' => FALSE]
	 */
	protected function runBonusConditionChecker($description, &$extra_info, $dry_run){
        $errorMessageLang = null;
        $result = $this->checkCustomizeBonusCondition($description, $extra_info, $errorMessageLang);

        $extra_info['mission_desc'] = isset($result['mission_desc']) ? $result['mission_desc'] : null;
        $keys_to_remove = ['bonus_amount', 'mission_desc'];
        $result = array_diff_key($result, array_flip($keys_to_remove));

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
        $result = $this->checkCustomizeBonusCondition($description, $extra_info, $errorMessageLang);

        $extra_info['mission_desc'] = isset($result['mission_desc']) ? $result['mission_desc'] : null;

        if (isset($result['mission_desc'])) {
            unset($result['mission_desc']);
        }

        return $result;
    }
    protected function getSubtype() {
        return self::SUBTYPE;
    }
    protected function checkMission($description, &$extra_info) {
        $hit_mission = false;
        $current_total = 0;
        $use_player_report_records = !empty($description) && array_key_exists('use_player_report_records', $description) && ($description['use_player_report_records'] == true);
        if ($use_player_report_records) {
            list($bet, $deposit)=$this->callHelper('getBetsAndDepositByDate', [$this->playerRegisterDate, $this->currentDate]);
		} else {
			$deposit = $this->CI->transactions->totalDepositByPlayerAndDateTime($this->playerId, $this->playerRegisterDate, $this->currentDate);
		}
        $thresh_hold = $this->utils->safeGetArray($description, 'thresh_hold', 0);
        $hit_mission = $deposit >= $thresh_hold;
        $current_total = $deposit;

        $this->appendToDebugLog($this->getClassName() .' checkMission_'.$this->playerId , ["deposit"=>$deposit, "thresh_hold" => $thresh_hold]);
        return [$hit_mission, $current_total];
    }

    /**
     * @deprecated refer to abstract class Abstract_promo_rule_mission::checkCustomizeBonusCondition
     */
    private function _checkCustomizeBounsCondition($description, &$errorMessageLang){
        $success = false;
        $bonus_amount = 0;
        $player_id = $this->playerId;
        $promo_rule_id = $this->promorule['promorulesId'];
        $period_days = $description['period_days'];
        $thresh_hold = $description['thresh_hold'];
        $subtype = isset($description['subtype']) ? $description['subtype'] : self::SUBTYPE;
        $mission_desc = [];

        $now_date = $this->get_date_type(self::TO_TYPE_NOW);
        $register_date = $this->callHelper('get_date_type', [self::REGISTER_DATE]);
        $seconds_per_day = 60 * 60 * 24;
        $register_date_to_now = (int) ((strtotime($now_date) - strtotime($register_date)) / $seconds_per_day);
        $is_expired = false;
        $use_player_report_records = !empty($description) && array_key_exists('use_player_report_records', $description) && ($description['use_player_report_records'] == true);
        $hit_mission = false;
        if ($use_player_report_records) {
            list($bet, $deposit)=$this->callHelper('getBetsAndDepositByDate', [$register_date, $now_date]);
		} else {
			$deposit = $this->CI->transactions->totalDepositByPlayerAndDateTime($player_id, $register_date, $now_date);
		}

        $release_date['start'] = $register_date;
        $release_date['end'] = $now_date;
        $check_released_bonus = $this->callHelper('count_approved_promo', [$promo_rule_id, self::DATE_TYPE_CUSTOMIZE, $release_date]);

        $this->appendToDebugLog('params details', ['description' => $description, 'deposit' => $deposit, 'now_date' => $now_date, 'register_date' => $register_date, 'register_date_to_now' => $register_date_to_now, 'subtype' => $subtype, 'player_id' => $player_id, 'promo_rule_id' => $promo_rule_id, 'check_released_bonus' => $check_released_bonus]);

        $hit_mission = $deposit >= $thresh_hold;
        if ($register_date_to_now <= $period_days) {
            if (!$hit_mission) {
                $errorMessageLang = 'promo_rule.common.error';
            } else {
                $success = true;
                $bonus_amount = $description['bonus_amount'];
            }
        } else {
            $is_expired = true;
            $errorMessageLang = 'promo_rule.common.error';
        }

        $mission_desc = $this->generate_mission_desc(
            $deposit, 
            $thresh_hold,
            $period_days,
            $subtype,
            $description['bonus_amount'],
            $this->matchMissionStatus($hit_mission, $check_released_bonus),
            $is_expired,
            $success,
            $errorMessageLang
        );

        $this->appendToDebugLog('mission_desc_'.$player_id, $mission_desc);

		$result=['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonus_amount, 'mission_desc' => $mission_desc];

        return $result;
    }
}
