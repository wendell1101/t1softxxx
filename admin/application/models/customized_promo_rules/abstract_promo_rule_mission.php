<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 * abstract class for mission promo rule
condition:
{
    "class": "abstract_promo_rule_mission",
	"allowed_date": {
        "start": "2023-08-22",
        "end": "2033-08-22"
    },
    "thresh_hold" : 1,
    "subtype": "profile",
    "period_days": "30",
    "bonus_amount" : 1
}

 *
 * @property Player_model $player_model
 * 
 */
abstract class Abstract_promo_rule_mission extends Abstract_promo_rule{

    protected $playerRegisterDate;
    protected $currentDate;

    const SUBTYPE = 'mission';

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
        $this->currentDate = $this->get_date_type(self::TO_TYPE_NOW);
        $this->playerRegisterDate = $this->get_date_type(self::REGISTER_DATE);
	}

	public function getClassName(){
		return 'abstract_promo_rule_mission';
	}

    /**
	 * run bonus condition checker
	 * @param  array $description original description in rule
	 * @param  array $extra_info
	 * @param  boolean $dry_run
	 * @return  array ['success'=> success, 'message'=> errorMessageLang]
	 */
	protected function runBonusConditionChecker($description, &$extra_info, $dry_run){
		return $this->returnUnimplemented();
	} // EOF runBonusConditionChecker

	/**
	 * generate withdrawal condition
	 * @param  array $description original description in rule
	 * @param  array $extra_info
	 * @param  boolean $dry_run
	 * @return  array ['success'=> success, 'message'=> errorMessageLang, 'withdrawal_condition_amount'=> withdrawal condition amount]
	 */
	protected function generateWithdrawalCondition($description, &$extra_info, $dry_run){
		return $this->returnUnimplemented();
	} // EOF generateWithdrawalCondition

    /**
     * generate transfer condition
     * @param  array $description original description in rule
     * @param  array $extra_info exchange data
     * @param  boolean $dry_run
     * @return  array ['success'=> success, 'message_lang'=> errorMessageLang, 'withdrawal_condition_amount'=> withdrawal condition amount]
     */
    protected function generateTransferCondition($description, &$extra_info, $dry_run){
		return $this->returnUnimplemented();
    } // EOF generateTransferCondition

	/**
	 * release bonus
	 * @param  array $description original description in rule
	 * @param  array $extra_info
	 * @param  boolean $dry_run
	 * @return  array ['success'=> success, 'message'=> errorMessageLang, 'bonus_amount'=> bonus amount]
	 */
	protected function releaseBonus($description, &$extra_info, $dry_run){
		return $this->returnUnimplemented();
	}// EOF releaseBonus


    abstract protected function getSubtype();
    
    /**
     * abstract checkMission function
     *
     * @param array $description
     * @return array [hit_mission, current_total]
     * hit_mission : boolean
     * current_total: integer
     */
    abstract protected function checkMission($description, &$extra_info);    
    


    /**
     * checkAllowedDate
     *
     * @param string $a_start 2023-01-01 00:00:00
     * @param string $a_end 2023-01-01 00:00:00
     * @param string $currenct_date 2023-01-01 00:00:00
     * @return boolean
     */
    protected function checkAllowedDate($description) {
        $allowed_date = isset($description['allowed_date'])? $description['allowed_date'] : null;
        $promoteFirstDate = !empty($allowed_date['start']) ? $this->utils->formatDateForMysql(new DateTime($allowed_date['start'])): null;
        $promoteLastDate = !empty($allowed_date['end']) ? $this->utils->formatDateForMysql(new DateTime($allowed_date['end'])) : null;
        $promoAnchorType = !empty($allowed_date['anchor_type']) ? $allowed_date['anchor_type'] : null;
        $allowed_anchor_date = $promoAnchorType == 'setup' ? $this->currentDate : $this->playerRegisterDate; 
        $start = strtotime($promoteFirstDate);
        $end = strtotime($promoteLastDate);
        $anchor = strtotime($allowed_anchor_date);
        $checked = 0;

        if($start && $start >= $anchor){
            $checked += 1 ;
        }
        if($end && $end <= $anchor){
            $checked += 1 ;
        }

        return $checked === 0;
    }

    /**
     * checkPeriodDaysAvailable function
     *
     * @param integer $period_days
     * @param string $start Y-m-d H:i:s 2023-01-01 00:00:00
     * @param string $end Y-m-d H:i:s 2023-01-01 00:00:00
     * @return boolean
     */
    protected function checkPeriodDaysAvailable($period_days, $start, $end = null){
        $period_days = intval($period_days)?: 30;
        $from = strtotime($start);
        $to = $end ?:  (new \DateTime())->format('Y-m-d H:i:s');
        $to = strtotime($to);

        $start_to_end = $to - $from;

        $days = floor($start_to_end / 86400);

        return $days <= $period_days;
    }

    protected function matchMissionStatus($success, $check_released_bonus) {
        switch (true) {
            case !$success:
                $status = MISSION_CONDITION_NOT_MET;
                break;
            case $success && $check_released_bonus < 1:
                $status = MISSION_CONDITION_MET_NOT_APPLY;
                break;
            case $success && $check_released_bonus >= 1:
                $status = MISSION_CONDITION_MET_APPLIED;
                break;
            default:
                $status = MISSION_CONDITION_NOT_MET;
                break;
        }
        $this->appendToDebugLog($this->getClassName() . ' matchMissionStatus_'.$this->playerId, ["hit_mission" => $success, "check_released_bonus" => $check_released_bonus]);
        return $status;
    }

    protected function generate_mission_desc(
        $current_total, 
        $thresh_hold,
        $period_days,
        $subtype,
        $bonus_amount,
        $status,
        $is_expired,
        $success,
        $errorMessageLang
    ) {

        return [
            'mission' => [
                'current_total' => $current_total,
                'thresh_hold' => $thresh_hold,
                'period_days' => $period_days,
                'subtype' => $subtype,
                'bonus_amount' => $bonus_amount,
                'status' => $status,
                'is_expired' => $is_expired,
            ],
            'mission_status' => [
                'success' => $success,
                'error_message_lang' => $errorMessageLang,
            ],
        ];

    }

    protected function checkCustomizeBonusCondition($description, &$extra_info, &$errorMessageLang){
		$success = false;
        $errorMessageLang = null;
        $bonus_amount = 0;
        $is_expired = false;
        $is_allowed = false;
        $promo_rule_id = $this->promorule['promorulesId'];
        $thresh_hold = isset($description['thresh_hold'])? $description['thresh_hold'] : 1;
        $period_days = isset($description['period_days'])? $description['period_days'] : '30';
        $subtype = isset($description['subtype'])? $description['subtype'] : $this->getSubtype();
        $bonus_amount = isset($description['bonus_amount']) ? $description['bonus_amount'] : 1; 

        
        try{
            $playerRegisterDate = $this->playerRegisterDate;
            $currentDate = $this->currentDate;
            
            $release_date['start'] = $playerRegisterDate;
            $release_date['end'] = $currentDate;
            $checkReleasedBonus = $this->callHelper('count_approved_promo', [$promo_rule_id, self::DATE_TYPE_CUSTOMIZE, $release_date]);
            $description['checkReleasedBonus'] = $checkReleasedBonus;
            if($checkReleasedBonus >= $bonus_amount){
                $hit_mission = true;
                $current_total = $thresh_hold;
                throw new Exception('The mission completed');
            }

            list($hit_mission, $current_total) = $this->checkMission($description, $extra_info);
            
            $is_allowed = $this->checkAllowedDate($description);
            if(!$is_allowed) {
                $is_expired = true;
                throw new Exception('The mission expired');
            }

            $isPeriodDaysAvailable = $this->checkPeriodDaysAvailable($period_days, $playerRegisterDate, $currentDate);
            if(!$isPeriodDaysAvailable) {
                $is_expired = true;
                throw new Exception('The mission expired');
            }

            if(!$hit_mission) {
                throw new Exception('promo_rule.common.error');
            }
            $success = true;
        }catch(Exception $e){
            $success = false;
            $errorMessageLang = $e->getMessage();
        }
        $mission_status = $this->matchMissionStatus($hit_mission, $checkReleasedBonus);
        $mission_desc = $this->generate_mission_desc(
            $current_total, 
            $thresh_hold,
            $period_days,
            $subtype,
            $bonus_amount,
            $mission_status,
            $is_expired,
            $success,
            $errorMessageLang
        );
        $this->appendToDebugLog('mission_desc_'.$this->playerId, $mission_desc);

        $result=['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonus_amount, 'mission_desc' => $mission_desc];

        return $result;
	}
}
