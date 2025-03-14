<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 *
 * 新老用户见面礼
 *
 * OGP-27642
 *
 * 根据VIP等级领取对应礼金
 * 礼金份数限量，根据报名顺序送完为止
 * 一人只可領一次
 * 取款條件 x 3

condition:
{
    "class": "promo_rule_ole777_vip_limit_free_bonus",
    "allowed_date": {
        "start": "2023-01-18 00:00:00",
        "end": "2023-12-18 23:59:59"
    },
    "bonus_settings": [
        { "vip_level": [VIP1-1, VIP2-1], "bonus_amount": 17, "bonus_limit_cnt": 500},
        { "vip_level": [VIP1-2, VIP2-2], "bonus_amount": 27, "bonus_limit_cnt": 500},
        { "vip_level": [VIP1-3, VIP2-3], "bonus_amount": 37, "bonus_limit_cnt": 500},
        { "vip_level": [VIP1-4, VIP2-4], "bonus_amount": 77, "bonus_limit_cnt": 100},
        { "vip_level": [VIP1-5, VIP2-5], "bonus_amount": 177, "bonus_limit_cnt": 100},
        { "vip_level": [VIP1-6, VIP2-6], "bonus_amount": 277, "bonus_limit_cnt": 100},
        { "vip_level": [VIP1-7, VIP2-7], "bonus_amount": 377, "bonus_limit_cnt": 50},
        { "vip_level": [VIP1-8, VIP2-8], "bonus_amount": 477, "bonus_limit_cnt": 50},
        { "vip_level": [VIP1-9, VIP2-9], "bonus_amount": 577, "bonus_limit_cnt": 50},
        { "vip_level": [VIP1-10, VIP2-10], "bonus_amount": 677, "bonus_limit_cnt": 10},
        { "vip_level": [VIP1-11, VIP2-11], "bonus_amount": 777, "bonus_limit_cnt": 10}
    ]
}

 *
 *
 */
class Promo_rule_ole777_vip_limit_free_bonus extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_ole777_vip_limit_free_bonus';
	}

	/**
	 * run bonus condition checker
	 * @param  array $description original description in rule
	 * @param  array $extra_info
	 * @param  boolean $dry_run
	 * @return  array ['success'=> success, 'message'=> errorMessageLang, 'continue_process_after_script' => TRUE]
	 */
	protected function runBonusConditionChecker($description, &$extra_info, $dry_run){
		$errorMessageLang=null;

        $result = $this->checkCustomizeBounsCondition($description, $extra_info, $errorMessageLang);

        if(array_key_exists('bonus_amount', $result)){
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
		$errorMessageLang=null;

        $result = $this->checkCustomizeBounsCondition($description, $extra_info, $errorMessageLang);

        return $result;
	}

    public function checkIsBetweenTime($start,$end){
        $result = false;

        if(!empty($start) && !empty($end)){
            $now = $this->get_date_type(self::TO_TYPE_NOW);
            $curTime = strtotime($now);//当前时分
            $assignTime1 = strtotime($start);
            $assignTime2 = strtotime($end);

            if($curTime >= $assignTime1 && $curTime <= $assignTime2){
                $result = true;
            }

            $this->appendToDebugLog('checkIsBetweenTime',[
                'result'=>$result, 'curTime' => $this->get_date_type(self::TO_TYPE_NOW),
                'assignTime1' => $start, 'assignTime2' => $end
            ]);
        }

        return $result;
    }

    private function checkCustomizeBounsCondition($description, &$extra_info, &$errorMessageLang){
	    $success = false;
        $bonus_amount = 0;
        $levelId = $this->levelId;
        $startDate = $description['allowed_date']['start'];
        $endDate = $description['allowed_date']['end'];
        $bonus_settings = $description['bonus_settings'];
        $level_setting = [];

        if(!$this->checkIsBetweenTime($startDate, $endDate)){
            $errorMessageLang = 'Not right date';
            return ['success' => $success, 'message' => $errorMessageLang];
        }

        $this->appendToDebugLog('player level id', ['vip level' => $levelId]);
        if(!empty($bonus_settings)){
            foreach($bonus_settings as $setting){
                if(in_array($levelId, $setting['vip_level'])){
                    $level_setting = $setting;
                    break;
                }
            }
        }

        if(empty($level_setting)){
            $errorMessageLang = 'Not right group level';
            return ['success' => $success, 'message' => $errorMessageLang];
        }

        $promoruleId = $this->promorulesId;
        $count_approved_promo = $this->callHelper('count_approved_promo', [$promoruleId, null]);
        $this->appendToDebugLog('count player approve promo', ['result count' => $count_approved_promo]);
        if(!empty($count_approved_promo)){
            $errorMessageLang = 'notify.83';
            return ['success' => $success, 'message' => $errorMessageLang];
        }

        $_extra_info['level_id'] = $level_setting['vip_level'];
        $_extra_info['ignore_player_id'] = true;
        $level_released = $this->callHelper('get_all_released_player_promo', [$promoruleId, null, $_extra_info]);
        $level_released = !empty($level_released) ? $level_released : [];
        $level_released_count = count($level_released);

        $remain_bonus_limit_cnt = $level_setting['bonus_limit_cnt'] - $level_released_count;
        $this->appendToDebugLog('count same levels approve promo', ['released count' => count($level_released), 'remain bonus limit count' => $remain_bonus_limit_cnt]);

        if($level_released_count < $level_setting['bonus_limit_cnt']){
            $success = true;
            $bonus_amount = $level_setting['bonus_amount'];
        }else{
            $errorMessageLang = 'promo_rule.common.error';
        }

        return ['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonus_amount];
    }
}

