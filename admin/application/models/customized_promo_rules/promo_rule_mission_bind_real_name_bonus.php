<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule_mission.php';

/**
 * OGP-30700
 * New Player Mission - Bind Real Name
 * 新手有效期內，綁定姓名成功
 * 
 * Bonus condition 
condition:
{
    "class" : "promo_rule_mission_bind_real_name_bonus",
    "subtype": "kyc",
    "bonus_amount" : "1",
    "period_days" : 30,
    "thresh_hold" : 1
}
 *
 * @property Player_model $player_model
 * @property Player_security_library $player_security_library
 *
 */
class Promo_rule_mission_bind_real_name_bonus extends Abstract_promo_rule_mission{

    const SUBTYPE = 'kyc';
    const PERIOD_DAYS = 30;
    const THRESH_HOLD = 1;

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'promo_rule_mission_bind_real_name_bonus';
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

    protected function isVerifiedRealName($usePlayerkyc){
        $verified_real_name = false;
        if($usePlayerkyc){
            $this->load->library('player_security_library');
            $player_verification = $this->player_security_library->player_verification_info($this->playerId);
            $verified_real_name = $player_verification['verified'];
        } else {
            $playerInfo = $this->callHelper('getPlayerInfoById', [$this->playerId]);
            $verified_real_name = !empty($playerInfo['firstName']) && !empty($playerInfo['lastName']);
        }

        return $verified_real_name;
    }
    protected function getSubtype() {
        return self::SUBTYPE;
    }
    protected function checkMission($description, &$extra_info) {
        
        $usePlayerkyc = isset($description['use_player_kyc']) ? $description['use_player_kyc'] : false;
        $verified_real_name = $this->isVerifiedRealName($usePlayerkyc);
        $hit_mission = $verified_real_name;
        $current_total = $hit_mission ? 1 : 0;

        $this->appendToDebugLog($this->getClassName() .' checkMission_'.$this->playerId , ["usePlayerkyc"=>$usePlayerkyc, "verified_real_name" => $verified_real_name]);
        return [$hit_mission, $current_total];
    }

    /**
     * @deprecated refer to abstract class Abstract_promo_rule_mission::checkCustomizeBonusCondition
     */
    private function _checkCustomizeBounsCondition($description, &$errorMessageLang){
        $success = false;
        $bonus_amount = 0;
        $current_total = '0';
        $mission_desc = [];
        $player_id = $this->playerId;
        $promo_rule_id = $this->promorule['promorulesId'];
        $verified_real_name = false; //$this->isVerifiedRealName();
        $period_days = isset($description['period_days']) ? $description['period_days'] : self::PERIOD_DAYS;
        $thresh_hold = isset($description['thresh_hold']) ? $description['thresh_hold'] : self::THRESH_HOLD;
        $subtype = isset($description['subtype']) ? $description['subtype'] : self::SUBTYPE;
        $is_expired = false;
        $now_date = $this->get_date_type(self::TO_TYPE_NOW);
        $register_date = $this->callHelper('get_date_type',[self::REGISTER_DATE]);
	    $register_date_to_now = (strtotime($now_date) - strtotime($register_date)) / (60*60*24);

        $release_date['start'] = $register_date;
        $release_date['end'] = $now_date;
        $check_released_bonus = $this->callHelper('count_approved_promo', [$promo_rule_id, self::DATE_TYPE_CUSTOMIZE, $release_date]);

        $this->appendToDebugLog('params details', ['description' => $description, 'verified_real_name' => $verified_real_name, 'now_date' => $now_date, 'register_date' => $register_date, 'register_date_to_now' => $register_date_to_now, 'subtype' => $subtype, 'player_id' => $player_id, 'promo_rule_id' => $promo_rule_id, 'check_released_bonus' => $check_released_bonus]);



        $hit_mission = false;
        $usePlayerkyc = isset($description['use_player_kyc']) ? $description['use_player_kyc'] : false;
        if($usePlayerkyc){
            $verified_real_name = $this->isVerifiedRealName();
        } else {
            $playerInfo = $this->callHelper('getPlayerInfoById', [$player_id]);
            $verified_real_name = !empty($playerInfo['firstName']) && !empty($playerInfo['lastName']);
        }
        $hit_mission = $verified_real_name;

        if ($register_date_to_now <= $period_days) {

            if (!$verified_real_name) {
                $errorMessageLang = 'notify.93';
            } else {
                $success = true;
                $bonus_amount = $description['bonus_amount'];
            }

        } else {
            $is_expired = true;
            $errorMessageLang = 'promo_rule.common.error';
        }

        $mission_desc = $this->generate_mission_desc(
            $hit_mission ? 1 : 0,
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
