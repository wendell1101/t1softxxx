<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 * OGP-29437
 * 升级奖金，每次升級都能領獎金

condition:
{
    "class": "promo_rule_t1bet_upgrade_level_bonus",
    "bonus_settings": {
        "VIP0  → VIP1"   : {"bonus_amount":    0},
        "VIP1  → VIP2"   : {"bonus_amount":    0},
        "VIP2  → VIP3"   : {"bonus_amount":    0},
        "VIP3  → VIP4"   : {"bonus_amount":    0},
        "VIP4  → VIP5"   : {"bonus_amount":    0},
        "VIP5  → VIP6"   : {"bonus_amount":    0},
        "VIP6  → VIP7"   : {"bonus_amount":    0},
        "VIP7  → VIP8"   : {"bonus_amount":    0},
        "VIP8  → VIP9"   : {"bonus_amount":   38},
        "VIP9  → VIP10"  : {"bonus_amount":   88},
        "VIP10 → VIP11"  : {"bonus_amount":  188},
        "VIP11 → VIP12"  : {"bonus_amount":  388},
        "VIP12 → VIP13"  : {"bonus_amount":  588},
        "VIP13 → VIP14"  : {"bonus_amount": 1288},
        "VIP14 → VIP15"  : {"bonus_amount": 3888}
    }
}

 *
 *
 */
class Promo_rule_t1bet_upgrade_level_bonus_monthly extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_t1bet_upgrade_level_bonus_monthly';
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
        $errorMessageLang = null;
        $result = $this->checkCustomizeBonusCondition($description, $errorMessageLang);

        return $result;
    }

    private function checkCustomizeBonusCondition($description, &$errorMessageLang){
        $success = false;
        $bonus_amount = 0;
        $applyRecordWithTheSameLevel = false;
        $errorMessageLang = null;
        $bonus_setting = $description['bonus_settings'];
        $levelId = $this->levelId;

        if(array_key_exists($levelId, $bonus_setting)){
            $applyLevel = [];
            $promorule = $this->promorule;
            $promoRuleId = $promorule['promorulesId'];
            $applyRecord = $this->callHelper('get_all_released_player_promo',[$promoRuleId, null]);
            $this->appendToDebugLog('get all released player_promo', ['applyRecord'=>$applyRecord]);
            if(empty($applyRecord)){
                $setting = $bonus_setting[$levelId];
                $success = true;
                $bonus_amount = $setting['bonus_amount'];
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

                if(array_key_exists($currentLevelId, $applyLevel)){
                    //this level had already applied
                    $errorMessageLang = 'promo_custom.level_already_apply';
                }else{
                    if($applyRecordWithTheSameLevel){
                        // for checking client which had been moved vip level but no upgrade record
                        $errorMessageLang = 'promo_custom.level_already_apply';
                    }else{

                        $setting = $bonus_setting[$levelId];
                        $success = true;
                        $bonus_amount = $setting['bonus_amount'];
                    }
                }
            }
        }else{
            $errorMessageLang = 'promo_custom.not_in_allowed_vip_level';
        }

        return ['success'=>$success, 'message'=>$errorMessageLang, 'bonus_amount'=>$bonus_amount];
    }
}
