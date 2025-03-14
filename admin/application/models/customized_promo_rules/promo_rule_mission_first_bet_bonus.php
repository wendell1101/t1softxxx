<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule_mission.php';

/**
 * OGP-30707
 * 活动开始时间：依据实际上线时间为准。账户注册时间为活动上线后的玩家可以参与
 * 活动结束时间：长期有效。
 * 新手有效期：30天（玩家注册后30天参与有效）
 * 定制活动内容：玩家首次单笔投注大于等于10 R$，手动点击领取按钮 可以领取 1 R$奖励。（限制每个IP只能领取一次）
 * 取款条件：5倍流水取款
 * 示例：
 * 玩家第一笔投注 4 R$，点击领取，应提示不符合资格；
 * 玩家第二笔投注 6 R$，点击领取，应提示不符合资格；
 * 当玩家单笔投注金额大于等于10 R$时，点击领取，可成功领取奖励。
 * define('MISSION_CONDITION_NOT_MET', 1);
 * define('MISSION_CONDITION_MET_NOT_APPLY', 2);
 * define('MISSION_CONDITION_MET_APPLIED', 3);
 * 
 * Bonus condition 
condition:
{
    "class" : "promo_rule_mission_first_bet_bonus",
    "subtype": "bet",
    "bonus_amount" : "1",
    "period_days" : 30,
    "thresh_hold" : 10
}
 *
 */
class Promo_rule_mission_first_bet_bonus extends Abstract_promo_rule_mission{

    const SUBTYPE = 'bet';

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'promo_rule_mission_first_bet_bonus';
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
        list($bet, $deposit)=$this->callHelper('getBetsAndDepositByDate', [$this->playerRegisterDate, $this->currentDate]);
        $thresh_hold = isset($description['thresh_hold']) ? $description['thresh_hold'] : 0;
        $hit_mission = $bet >= $thresh_hold;
        $current_total = $bet;

        $this->appendToDebugLog($this->getClassName() .' checkMission_'.$this->playerId , ["bet"=>$bet, "thresh_hold" => $thresh_hold]);
        return [$hit_mission, $current_total];
    }

    /**
     * @deprecated refer to abstract class Abstract_promo_rule_mission::checkCustomizeBonusCondition
     */
    private function _checkCustomizeBounsCondition($description, &$errorMessageLang){
		$success = false;
        $errorMessageLang = null;
        $bonus_amount = 0;
        $promo_rule_id = $this->promorule['promorulesId'];
        $thresh_hold = isset($description['thresh_hold'])? $description['thresh_hold'] : 1;
        $period_days = isset($description['period_days'])? $description['period_days'] : '30';
        $subtype = isset($description['subtype']) ? $description['subtype'] : self::SUBTYPE;
        
        $player_id = $this->playerId;
        $mission_desc = [];

        $currentDate = $this->playerRegisterDate;
        $playerRegisterDate = $this->currentDate;
        $isPeriodDaysAvailable = $this->checkPeriodDaysAvailable($period_days, $playerRegisterDate, $currentDate);
        
        $is_expired = false;
        list($hit_mission, $current_total) = $this->checkMission($description);

        try{
            $is_allowed = $this->checkAllowedDate($description);

            if(!$is_allowed) {
                $is_expired = true;
                throw new Exception('The promotion expired');
            }

            if(!$isPeriodDaysAvailable) {
                $is_expired = true;
                throw new Exception('The promotion expired');
            }

            if(!$hit_mission) {
                throw new Exception('promo_rule.common.error');
            }
            $success = true;
        }catch(Exception $e){
            $success = false;
            $errorMessageLang = $e->getMessage();
        }

        $release_date['start'] = $playerRegisterDate;
        $release_date['end'] = $currentDate;
        $checkReleasedBonus = $this->callHelper('count_approved_promo', [$promo_rule_id, self::DATE_TYPE_CUSTOMIZE, $release_date]);

        $mission_desc = $this->generate_mission_desc(
            $current_total, 
            $thresh_hold,
            $period_days,
            $subtype,
            $description['bonus_amount'],
            $this->matchMissionStatus($success, $checkReleasedBonus),
            $is_expired,
            $success,
            $errorMessageLang
        );

        $this->appendToDebugLog('mission_desc_'.$player_id, $mission_desc);

		$result=['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonus_amount, 'mission_desc' => $mission_desc];

        $this->appendToDebugLog('params details', 
        [
            'description' => $description, 
            'bet' => $current_total, 
            'now_date' => $currentDate, 
            'register_date' => $playerRegisterDate, 
            'subtype' => $subtype, 
            'player_id' => $player_id, 
            'promo_rule_id' => $promo_rule_id, 
            'check_released_bonus' => $checkReleasedBonus
        ]);

        return $result;
    }
}
