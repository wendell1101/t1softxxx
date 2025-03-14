<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 * OGP-27145
 * 升级奖金，每次升級都能領獎金

condition:
{
    "class": "promo_rule_abet_upgrade_level_bonus",
    "bonus_settings": {
        "VIP0 → VIP1"  : {"bonus_amount":    5},
        "VIP1 → VIP2"  : {"bonus_amount":   10},
        "VIP2 → VIP3"  : {"bonus_amount":   20},
        "VIP3 → VIP4"  : {"bonus_amount":   50},
        "VIP4 → VIP5"  : {"bonus_amount":  100},
        "VIP5 → VIP6"  : {"bonus_amount":  200},
        "VIP6 → VIP7"  : {"bonus_amount":  500},
        "VIP7 → VIP8"  : {"bonus_amount": 1000},
        "VIP8 → VIP9"  : {"bonus_amount": 2000},
        "VIP9 → VIP10" : {"bonus_amount": 5000},
    }
}

 *
 *
 */
class Promo_rule_abet_upgrade_level_bonus extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_abet_upgrade_level_bonus';
	}

	/**
	 * run bonus condition checker
	 * @param  array $description original description in rule
	 * @param  array $extra_info
	 * @param  boolean $dry_run
	 * @return  array ['success'=> success, 'message'=> errorMessageLang, 'continue_process_after_script' => FALSE]
	 */
	protected function runBonusConditionChecker($description, &$extra_info, $dry_run){
        $success = false;
        $errorMessageLang = null;
        $isUpgrade = false;
        $applyRecordWithTheSameLevel = false;
        $returnOneRow = true;

        $endAt = $this->utils->getNowForMysql();
        $gradeRecord = $this->callHelper('getLastUpgradeLevelOrCurrentLevel',[$endAt, 'upgrade_or_downgrade', $returnOneRow]);
        if(!empty($gradeRecord) && !empty($gradeRecord['level_from']) && !empty($gradeRecord['level_to'])){
            if($gradeRecord['level_from'] < $gradeRecord['level_to']){
                $isUpgrade = true; // @todo $gradeRecord['request_grade'] == Group_level::RECORD_UPGRADE
            }
        }
        $this->appendToDebugLog('search grade record getLastUpgradeLevelOrCurrentLevel', ['endAt'=>$endAt, 'gradeRecord'=>$gradeRecord, 'isUpgrade'=>$isUpgrade]);

        if($isUpgrade){
            $applyLevel = [];
            $promorule = $this->promorule;
            $promoRuleId = $promorule['promorulesId'];
            $applyRecord = $this->callHelper('get_all_released_player_promo',[$promoRuleId, null]);
            $this->appendToDebugLog('get all released player_promo', ['applyRecord'=>$applyRecord]);

            if(empty($applyRecord)){
                $success = true;
            }else{
                // apply in other time
                $currentLevelId = $this->levelId;

                foreach($applyRecord as $k => $v){
                    $applyLevelId = $v['level_id'];
                    if($applyLevelId == $currentLevelId){
                        $applyRecordWithTheSameLevel = true;
                        $this->appendToDebugLog('find out apply reocord with the same level', ['current level id' => $currentLevelId, 'record level_id' => $v]);
                        break;
                    }

                    if(!empty($v['dateApply'])){
                        $dateApply = $v['dateApply'];
                        $applyLevel[$applyLevelId] = $dateApply;
                        $this->appendToDebugLog('get last upgarde level when apply promo', ['dateApply' => $dateApply, 'applyLevelId' => $applyLevelId]);
                    }
                }

                $this->appendToDebugLog('applied level', ['applyLevel' => $applyLevel]);

                if(array_key_exists($currentLevelId, $applyLevel)){
                    //this level had already applied
                    $errorMessageLang = 'promo_custom.level_already_apply';
                }else{
                    if($applyRecordWithTheSameLevel){
                        // for checking client which had been moved vip level but no upgrade record
                        $errorMessageLang = 'promo_custom.level_already_apply';
                    }else{
                        $success = true;
                    }
                }
            }
        }else{
            $errorMessageLang = 'promo_custom.level_upgrade_record_not_found';
        }

        $result=['success'=>$success, 'message'=>$errorMessageLang, 'continue_process_after_script' => FALSE];
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

        $bonus_setting = $description['bonus_settings'];
        $levelId = $this->levelId;
        $this->appendToDebugLog('get bonus setting', ['bonus_settings'=>$bonus_setting, 'levelId'=>$levelId]);

        if(array_key_exists($levelId, $bonus_setting)){
            $setting = $bonus_setting[$levelId];
            $success = true;
            $bonus_amount = $setting['bonus_amount'];
        }else{
            $errorMessageLang = 'promo_custom.not_in_allowed_vip_level';
        }

        $result=['success'=>$success, 'message'=>$errorMessageLang, 'bonus_amount'=>$bonus_amount];
        return $result;
	}
}
