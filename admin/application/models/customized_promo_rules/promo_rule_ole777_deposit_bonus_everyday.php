<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 *
 * 棋牌笔笔存送
 *
 * OGP-20845
 *
 * IP限制规则：1天内，同1个IP限制1个玩家可多次申请。
 * 举例：玩家A用A IP申请过此优惠，那么在当天，仅限玩家A再次申请这个优惠，其他玩家不可以申请。

* Bonus condition && Bonus release:
{
    "class": "Promo_rule_ole777_deposit_bonus_everyday",
    "expire_date": "2021-03-01",
    "bonus_settings": {
         "VIP0": {"percentage": 10, "max_bonus":  200, "min_deposit": 100, "max_deposit": 2100, "bet_times":  5},
         "VIP1": {"percentage": 20, "max_bonus":  400, "min_deposit": 100, "max_deposit": 2100, "bet_times": 10},
         "VIP2": {"percentage": 20, "max_bonus":  400, "min_deposit": 100, "max_deposit": 2100, "bet_times": 10},
         "VIP3": {"percentage": 20, "max_bonus":  400, "min_deposit": 100, "max_deposit": 2100, "bet_times": 10},
         "VIP4": {"percentage": 30, "max_bonus":  500, "min_deposit": 100, "max_deposit": 1700, "bet_times": 15},
         "VIP5": {"percentage": 30, "max_bonus":  500, "min_deposit": 100, "max_deposit": 1700, "bet_times": 15},
         "VIP6": {"percentage": 30, "max_bonus":  500, "min_deposit": 100, "max_deposit": 1700, "bet_times": 15},
         "VIP7": {"percentage": 40, "max_bonus":  600, "min_deposit": 100, "max_deposit": 1600, "bet_times": 20},
         "VIP8": {"percentage": 40, "max_bonus":  600, "min_deposit": 100, "max_deposit": 1600, "bet_times": 20},
         "VIP9": {"percentage": 40, "max_bonus":  600, "min_deposit": 100, "max_deposit": 1600, "bet_times": 20},
        "VIP10": {"percentage": 50, "max_bonus": 1000, "min_deposit": 100, "max_deposit": 2100, "bet_times": 20},
        "VIP11": {"percentage": 50, "max_bonus": 1000, "min_deposit": 100, "max_deposit": 2100, "bet_times": 20}
    }
}

 *
 *
 */
class Promo_rule_ole777_deposit_bonus_everyday extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_ole777_deposit_bonus_everyday';
	}

	private function isDuplicateIpWithTheSamePlayer($fromDate, $toDate, $extra_info){
        $result = true;
        $player_ip = !empty($extra_info['player_request_ip'])?:$this->CI->utils->getIp();

        $requestPromo = $this->callHelper('countRequestPromoFromSameIp', [$player_ip, $fromDate, $toDate]);
        $this->appendToDebugLog('request promo from same ips', ['requestPromo'=>$requestPromo, 'request_player_id'=>$this->playerId]);

        //check duplicate request ip with different player
        if(!empty($requestPromo)){
            $requestPlayer = array_column($requestPromo, 'playerId');
            $this->appendToDebugLog('countRequestPromoFromSameIp', ['requestPromo'=>$requestPromo, 'count requestPromo'=>count($requestPromo)]);
            if(!in_array($this->playerId, $requestPlayer)){
                $result = false;
            }
        }

        return $result;
    }

	/**
	 * run bonus condition checker
	 * @param  array $description original description in rule
	 * @param  array $extra_info
	 * @param  boolean $dry_run
	 * @return  array ['success'=> success, 'message'=> errorMessageLang, 'continue_process_after_script' => FALSE]
	 */
	protected function runBonusConditionChecker($description, &$extra_info, $dry_run){
		$success=false;
		$errorMessageLang=null;

        $today=$this->utils->getTodayForMysql();
        if($this->process_mock('today', $today)){
            //use mock data
            $this->appendToDebugLog('use mock today', ['today'=>$today]);
        }

        $d=new DateTime($today);
        $currentDate=$d->format('Y-m-d');
        $maxDate=$description['expire_date'];
        $fromDate = $this->callHelper('get_date_type', [self::DATE_TODAY_START]);
        $toDate = $this->callHelper('get_date_type', [self::TO_TYPE_NOW]);

        //check apply in available date
        if($currentDate <= $maxDate){
            $isDuplicateIpWithTheSamePlayer = $this->isDuplicateIpWithTheSamePlayer($fromDate, $toDate, $extra_info);
            if($isDuplicateIpWithTheSamePlayer){
                $bonus_settings = $description['bonus_settings'];
                if(isset($bonus_settings[$this->levelId])){
                    $getLastDepositByDate = $this->callHelper('getLastDepositByDate',[$fromDate, $toDate]);
                    $lastDepositAmount = intval($getLastDepositByDate['amount']);
                    $setting = $bonus_settings[$this->levelId];

                    if($lastDepositAmount>=$setting['min_deposit']){
                        $success=true;
                    }else{
                        $errorMessageLang = 'No enough deposit';
                    }
                }else{
                    $errorMessageLang = 'Not right group level';
                }

            }else{
                $errorMessageLang = 'promo.dont_allow_request_promo_from_same_ips';
            }
        }else{
            $success = false;
            $errorMessageLang='Not right date';
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
        $success = false;
        $errorMessageLang = null;
        $withdrawal_condition_amount = 0;

        $result = $this->releaseBonus($description, $extra_info, $dry_run);
        $bonus_amount = $result['bonus_amount'];
        $bet_times = $result['bet_times'];

        if($bet_times > 0){
            $withdrawal_condition_amount = $bonus_amount * $bet_times;
            $success = $withdrawal_condition_amount > 0;
        }else{
            $errorMessageLang='Lost bet_condition_times in settings';
        }

        $result=['success'=>$success, 'message'=>$errorMessageLang, 'withdrawal_condition_amount'=>round($withdrawal_condition_amount, 2)];
        return $result;
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
        $bet_times = 0;

        $today=$this->utils->getTodayForMysql();
        if($this->process_mock('today', $today)){
            //use mock data
            $this->appendToDebugLog('use mock today', ['today'=>$today]);
        }

        $d=new DateTime($today);
        $currentDate=$d->format('Y-m-d');
        $maxDate=$description['expire_date'];
        $fromDate = $this->callHelper('get_date_type', [self::DATE_TODAY_START]);
        $toDate = $this->callHelper('get_date_type', [self::TO_TYPE_NOW]);

        //check apply in available date
        if($currentDate <= $maxDate){
            $isDuplicateIpWithTheSamePlayer = $this->isDuplicateIpWithTheSamePlayer($fromDate, $toDate, $extra_info);
            if($isDuplicateIpWithTheSamePlayer){
                $bonus_settings = $description['bonus_settings'];
                if(isset($bonus_settings[$this->levelId])){
                    $getLastDepositByDate = $this->callHelper('getLastDepositByDate',[$fromDate, $toDate]);
                    $lastDepositAmount = intval($getLastDepositByDate['amount']);

                    $setting = $bonus_settings[$this->levelId];
                    $this->appendToDebugLog('getLastDepositByDate',['getLastDepositByDate'=>$getLastDepositByDate, 'lastDepositAmount'=>$lastDepositAmount]);

                    if($lastDepositAmount>=$setting['min_deposit']){
                        if($lastDepositAmount >= $setting['max_deposit']){
                            $bonus_amount = $setting['max_bonus'];
                        }else{
                            $bonus_amount = $lastDepositAmount*($setting['percentage']/100);
                        }

                        $bet_times = $setting['bet_times'];
                        $success=true;
                        $this->appendToDebugLog('calc bonus', ['setting'=>$setting, 'bonus_amount'=>$bonus_amount]);

                    }else{
                        $errorMessageLang = 'No enough deposit';
                        $this->appendToDebugLog('not met min deposit');
                    }

                }else{
                    $errorMessageLang = 'Not right group level';
                    $this->appendToDebugLog('not exist available level');
                }
            }
        }else{
            $errorMessageLang='Not right date';
            $this->appendToDebugLog('not right date', ['currentDate'=>$currentDate, 'expire_date'=>$maxDate]);
        }

        $result=['success'=>$success, 'message'=>$errorMessageLang, 'bonus_amount'=>$bonus_amount, 'bet_times'=>$bet_times];
        return $result;
    }
}
