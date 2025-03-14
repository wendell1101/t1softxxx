<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule_mission.php';

/**
 * OGP-30709
 * 首次好友邀請獎金
 * 1. 優惠期間內註冊完成才有參加資格
 * 2. 好友邀請須在優惠期間內完成
 * 3. 優惠資格保留30天,過期後不可領用
 * define('MISSION_CONDITION_NOT_MET', 1);
 * define('MISSION_CONDITION_MET_NOT_APPLY', 2);
 * define('MISSION_CONDITION_MET_APPLIED', 3);
condition:
{
    "class": "Promo_rule_mission_first_invitation",
	"allowed_date": {
        "start": "2023-08-22",
        "end": "2033-08-22"
    },
    "thresh_hold" : 1,
    "subtype": "friendInvitation",
    "period_days": "30",
    "bonus_amount" : 5
}

 *
 *
 */
class Promo_rule_mission_first_invitation extends Abstract_promo_rule_mission{

    const SUBTYPE = 'friendInvitation';

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'promo_rule_mission_first_invitation';
	}

	/**
	 * run bonus condition checker
	 * @param  array $description original description in rule
	 * @param  array $extra_info
	 * @param  boolean $dry_run
	 * @return  array ['success'=> success, 'message'=> errorMessageLang, 'continue_process_after_script' => FALSE]
	 */
	protected function runBonusConditionChecker($description, &$extra_info, $dry_run){
		$errorMessageLang=null;
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
        $errorMessageLang=null;
        $result = $this->checkCustomizeBonusCondition($description, $extra_info, $errorMessageLang);

        $extra_info['mission_desc'] = isset($result['mission_desc']) ? $result['mission_desc'] : null;

        if (isset($result['mission_desc'])) {
            unset($result['mission_desc']);
        }

        return $result;
	}

	public function getPlayerFriendReferral(){
		$this->load->model('player_friend_referral');
		$referralList = [];
		$playerFriendReferral = $this->player_friend_referral->getPlayerReferralList($this->playerId);


		if(!empty($playerFriendReferral)){
			foreach ($playerFriendReferral as $value) {
                $referralList[$value->referralId] = [
                    'playerId' => $value->playerId,
                    'referredOn' => $value->registerTime
                ];
			}
		}

		$this->appendToDebugLog('getPlayerFriendReferral', ['playerFriendReferral'=>$playerFriendReferral, 'referralList'=>  $referralList]);

		return $referralList;
	}
    protected function getSubtype() {
        return self::SUBTYPE;
    }
    protected function checkMission($description, &$extra_info) {
        $thresh_hold = isset($description['thresh_hold'])? $description['thresh_hold'] : 1;
        $referredList = $this->getPlayerFriendReferral();
        $successCounts = 0;
        if(!empty($referredList)){
            foreach ($referredList as $referralId => $referralData){
                $referredOn = $referralData['referredOn'];
                if($referredOn >= $this->playerRegisterDate){
                    $successCounts+=1;
                }
            }
        }

        $hit_mission =  $successCounts >= $thresh_hold;
        $current_total = $successCounts;

        $this->appendToDebugLog($this->getClassName() .' checkMission_'.$this->playerId , ["successCounts"=>$successCounts, "thresh_hold" => $thresh_hold]);
        return [$hit_mission, $current_total];
    }

    /**
     * @deprecated refer to abstract class Abstract_promo_rule_mission::checkCustomizeBonusCondition
     */
	protected function _checkCustomizeBounsCondition($description, &$extra_info, &$errorMessageLang){
		$success = false;
        $errorMessageLang = null;
        $bonus_amount = 0;
        $is_expired = false;

        try{
            $promo_rule_id = $this->promorule['promorulesId'];
            $thresh_hold = isset($description['thresh_hold'])? $description['thresh_hold'] : 1;
            $period_days = isset($description['period_days'])? $description['period_days'] : '30';
            $subtype = isset($description['subtype'])? $description['subtype'] : 'friendInvitation';
    
            $currentDate = $this->playerRegisterDate;
            $playerRegisterDate = $this->currentDate;
            $isPeriodDaysAvailable = $this->checkPeriodDaysAvailable($period_days, $playerRegisterDate, $currentDate);
            
            $release_date['start'] = $playerRegisterDate;
            $release_date['end'] = $currentDate;
            $checkReleasedBonus = $this->callHelper('count_approved_promo', [$promo_rule_id, self::DATE_TYPE_CUSTOMIZE, $release_date]);
            $description['checkReleasedBonus'] = $checkReleasedBonus;
    
            list($hit_mission, $current_total) = $this->checkMission($description);

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

        $mission_desc = $this->generate_mission_desc(
            $current_total, 
            $thresh_hold,
            $period_days,
            $subtype,
            $description['bonus_amount'],
            $this->matchMissionStatus($hit_mission, $checkReleasedBonus),
            $is_expired,
            $success,
            $errorMessageLang
        );

        $this->appendToDebugLog('mission_desc_'.$this->playerId, $mission_desc);

		$result=['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonus_amount, 'mission_desc' => $mission_desc];

        return $result;
	}
}
