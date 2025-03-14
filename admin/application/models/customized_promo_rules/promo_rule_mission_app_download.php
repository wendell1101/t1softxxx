<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule_mission.php';

/**
 * OGP-30704
 * 1. 優惠期間內註冊完成才有參加資格
 * 2. 優惠資格保留30天,過期後不可領用
 * define('MISSION_CONDITION_NOT_MET', 1);
 * define('MISSION_CONDITION_MET_NOT_APPLY', 2);
 * define('MISSION_CONDITION_MET_APPLIED', 3);
 *
condition:
{
    "class": "promo_rule_mission_app_download",
	"allowed_date": {
        "start": "2023-08-22",
        "end": "2033-08-22"
    },
    "by_login_report" : true,
    "by_domains" : ["og.local"],
    "thresh_hold" : 1,
    "subtype": "appDownload",
    "period_days": "30",
    "bonus_amount" : 1
}

 *
 * @property Player_login_report $player_login_report
 * @property Http_request $http_request
 */
class Promo_rule_mission_app_download extends Abstract_promo_rule_mission{

    const SUBTYPE = 'appDownload';

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'promo_rule_mission_app_download';
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
        $errorMessageLang=null;
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

        $hit_mission =  false;
        $by_login_report = isset($description['by_login_report']) ? $description['by_login_report'] : false;
        if($by_login_report){
            $this->load->model('player_login_report');
            $login_report_records = $this->player_login_report->existsPlayerLoginByApp($this->playerId);
            $hit_mission = !!$login_report_records;
        } 
        if(!$hit_mission) {
            $this->load->model('http_request');
            $by_domains = isset($description['by_domains']) ? $description['by_domains'] : [];
            $historyFrom=new DateTime($this->playerRegisterDate);
            $historyTo=new DateTime($this->currentDate);
            $login_list = $this->http_request->getPlayerLoginList($historyFrom, $historyTo, $this->playerId);
            $this->appendToDebugLog('login_list_'.$this->playerId, $login_list);
            foreach ($login_list as $login) {
                $filter_referrer = array_filter($by_domains, function($domain) use ($login) {
                    return strpos($login['referrer'], $domain) !== false;
                });
                $this->appendToDebugLog('filter_referrer_'.$this->playerId, $filter_referrer);
                $hit_mission = !empty($filter_referrer);
                if($hit_mission) {
                    break;
                }
            }
        }
        $current_total = $hit_mission ? 1 : 0;
        return [$hit_mission, $current_total];
    }

    /**
     * @deprecated refer to abstract class Abstract_promo_rule_mission::checkCustomizeBonusCondition
     */
	protected function _checkCustomizeBounsCondition($description, &$extra_info, &$errorMessageLang){
		$success = false;
        $errorMessageLang = null;
        $bonus_amount = 0;
        
        $promo_rule_id = $this->promorule['promorulesId'];
        $thresh_hold = isset($description['thresh_hold'])? $description['thresh_hold'] : 1;
        $period_days = isset($description['period_days'])? $description['period_days'] : '30';
        $subtype = isset($description['subtype'])? $description['subtype'] : 'appDownload';

        $currentDate = $this->playerRegisterDate;
        $playerRegisterDate = $this->currentDate;
        $isPeriodDaysAvailable = $this->checkPeriodDaysAvailable($period_days, $playerRegisterDate, $currentDate);
        
        $release_date['start'] = $playerRegisterDate;
        $release_date['end'] = $currentDate;
        $checkReleasedBonus = $this->callHelper('count_approved_promo', [$promo_rule_id, self::DATE_TYPE_CUSTOMIZE, $release_date]);
        $description['checkReleasedBonus'] = $checkReleasedBonus;

        list($hit_mission, $current_total) = $this->checkMission($description, $extra_info);
        $is_allowed = $this->checkAllowedDate($description);

        try{
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
