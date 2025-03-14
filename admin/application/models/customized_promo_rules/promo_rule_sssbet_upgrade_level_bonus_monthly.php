<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 * OGP-28822
 * 会员可在每个自然月第二天收到每月彩金
 * 每月只可領取一次，不可重複領取
 *
 * 判斷依據，以每月的最后一天的级别作爲判斷依據，進行派发
 * 例子：如您在任意月最后一天的等级为10级，即可在次月第二天获得對應VIP10級別的255雷亚尔。
 * 流水倍數為1倍即可提款
 *
 *
condition:
{
    "class": "promo_rule_sssbet_upgrade_level_bonus_monthly",
    "level_end_at": "2023-03-21 00:00:00",
    "allowed_date": {
        "start": "02",
        "end": "02"
    },
    "bonus_settings": {
        "38" : {"bonus_amount": 15},
        "39" : {"bonus_amount": 35},
        "40" : {"bonus_amount": 55},
        "41" : {"bonus_amount": 95},
        "42" : {"bonus_amount": 145},
        "43" : {"bonus_amount": 195},
        "44" : {"bonus_amount": 255},
        "45" : {"bonus_amount": 355},
        "46" : {"bonus_amount": 455},
        "47" : {"bonus_amount": 655},
        "48" : {"bonus_amount": 955},
        "49" : {"bonus_amount": 1355},
        "50" : {"bonus_amount": 1855},
        "51" : {"bonus_amount": 3555},
        "52" : {"bonus_amount": 5555},
        "53" : {"bonus_amount": 7555},
        "54" : {"bonus_amount": 9555}
    }
}
 *
 *
 */
class Promo_rule_sssbet_upgrade_level_bonus_monthly extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_sssbet_upgrade_level_bonus_monthly';
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
        $allowed_date = $description['allowed_date'];
        $bonus_setting = $description['bonus_settings'];

        $today=$this->utils->getTodayForMysql();
        if($this->process_mock('today', $today)){
            //use mock data
            $this->appendToDebugLog('use mock today', ['today'=>$today]);
        }

        $d=new DateTime($today);
        $currentDate=$d->format('Y-m-d');
        $minDate=$d->format('Y-m-').$allowed_date['start'];
        $maxDate=$d->format('Y-m-').$allowed_date['end'];

        $endAt = $this->callHelper('get_date_type', [self::DATE_LAST_MONTH_END]);
        if(!empty($description['level_end_at'])){
            $endAt = $description['level_end_at'];
        }
        $levelId = $this->callHelper('getLastUpgradeLevelOrCurrentLevel',[$endAt]);
        $this->appendToDebugLog('search grade record getLastUpgradeLevelOrCurrentLevel', [
            'endAt'=>$endAt, 'levelId at the end of last month'=>$levelId, 'current level id' =>$this->levelId
        ]);

        if($currentDate>=$minDate && $currentDate<=$maxDate){
            if(array_key_exists($levelId, $bonus_setting)){
                $setting = $bonus_setting[$levelId];
                $success = true;
                $bonus_amount = $setting['bonus_amount'];
            }else{
                $errorMessageLang = 'promo_custom.not_in_allowed_vip_level';
            }
        }else{
            $errorMessageLang='Not right date';
        }

        return ['success'=>$success, 'message'=>$errorMessageLang, 'bonus_amount'=>$bonus_amount];
    }
}
