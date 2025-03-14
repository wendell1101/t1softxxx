<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 * OGP-29436
 * VIP Rewards - Daily Sign In
 *
 * 每天都可以領
 * 檢查玩家申請當下的VIP
 * 一天領一次
 * 取款條件1倍
 *

condition:
{
    "class": "promo_rule_t1bet_vip_level_bonus_everyday",
    "bonus_settings": {
        "VIP0"   : {"bonus_amount":    0},
        "VIP1"   : {"bonus_amount":    1},
        "VIP2"   : {"bonus_amount":    1},
        "VIP3"   : {"bonus_amount":    1},
        "VIP4"   : {"bonus_amount":    1},
        "VIP5"   : {"bonus_amount":    1},
        "VIP6"   : {"bonus_amount":    3},
        "VIP7"   : {"bonus_amount":    5},
        "VIP8"   : {"bonus_amount":    6},
        "VIP9"   : {"bonus_amount":    7},
        "VIP10"  : {"bonus_amount":   35},
        "VIP11"  : {"bonus_amount":   96},
        "VIP12"  : {"bonus_amount":  240},
        "VIP13"  : {"bonus_amount":  480},
        "VIP14"  : {"bonus_amount": 1600},
        "VIP15"  : {"bonus_amount": 3200}
    }
}

 *
 *
 */
class Promo_rule_t1bet_vip_level_bonus_everyday extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_t1bet_vip_level_bonus_everyday';
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
        $errorMessageLang = null;
        $bonus_setting = $description['bonus_settings'];
        $levelId = $this->levelId;

        if(!array_key_exists($levelId, $bonus_setting)){
            $errorMessageLang = 'promo_custom.not_in_allowed_vip_level';
            return ['success'=>$success, 'message'=>$errorMessageLang, 'bonus_amount'=>$bonus_amount];
        }

        $todayReleasedCnt = $this->callHelper('count_approved_promo',[$this->promorulesId, self::DATE_TYPE_TODAY]);
        $this->appendToDebugLog('get today released promo', ['today release count' => $todayReleasedCnt]);
        if(!empty($todayReleasedCnt)){
            $errorMessageLang = 'promo_custom.level_already_apply';
            return ['success'=>$success, 'message'=>$errorMessageLang, 'bonus_amount'=>$bonus_amount];
        }

        $setting = $bonus_setting[$levelId];
        $success = true;
        $bonus_amount = $setting['bonus_amount'];

        return ['success'=>$success, 'message'=>$errorMessageLang, 'bonus_amount'=>$bonus_amount];
    }
}
