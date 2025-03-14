<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 *
 * Early Month Start Bonus
 * OGP-22175
 *
 * End of Month Bonus
 * OGP-22175
 *
 * 每月m-n号内，允许玩家申请一次免费奖金，
 * ㄧ年一次，一個等級一次

condition:
{
    "class": "promo_rule_ole777idr_free_bonus_monthly",
    "allowed_date":{
        "start": "01",
        "end": "03"
    }
    "bonus_settings":{
        "VIP1" : {"bonus_amount": 17},
        "VIP2" : {"bonus_amount": 27},
        "VIP3" : {"bonus_amount": 37},
        "VIP4" : {"bonus_amount": 57},
        "VIP5" : {"bonus_amount": 177},
        "VIP6" : {"bonus_amount": 377},
        "VIP7" : {"bonus_amount": 777},
        "VIP8" : {"bonus_amount": 5777},
        "VIP9" : {"bonus_amount": 7777}
    }
}
 *
 */
class Promo_rule_ole777idr_free_bonus_monthly extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_ole777idr_free_bonus_monthly';
	}

	/**
	 * run bonus condition checker
	 * @param  array $description original description in rule
	 * @param  array $extra_info
	 * @param  boolean $dry_run
	 * @return  array ['success'=> success, 'message'=> errorMessageLang, 'continue_process_after_script' => TRUE]
	 */
	protected function runBonusConditionChecker($description, &$extra_info, $dry_run){
        $success = false;
        $errorMessageLang = null;

        $today = $this->utils->getTodayForMysql();
        if($this->process_mock('today', $today)){
            $this->appendToDebugLog('use mock today', ['today' => $today]);
        }

        $bonus_settings = $description['bonus_settings'];
        $endAt = $this->utils->getNowForMysql();
        $levelId = $this->callHelper('getLastUpgradeLevelOrCurrentLevel',[$endAt]);
        $this->appendToDebugLog('search level id getLastUpgradeLevelOrCurrentLevel', ['endAt' => $endAt, 'levelId' => $levelId]);

        $completed_player_info = $this->completed_player_info();

        if(array_key_exists($levelId, $bonus_settings)){
            $allowed_date = $description['allowed_date'];
            $this->appendToDebugLog('check allowed_date', ['allowed_date' => $allowed_date]);

            $d = new DateTime($today);
            $currentDate = $d->format('Y-m-d');
            if(!empty($allowed_date['start']) && !empty($allowed_date['end']) && ($allowed_date['end'] == 'end_of_the_month')){
                $minDate = $d->format('Y-m-').$allowed_date['start'];
                $maxDate = $this->callHelper('get_date_type', [self::DATE_THIS_MONTH_END]);
            }else{
                $minDate = $d->format('Y-m-').$allowed_date['start'];
                $maxDate = $d->format('Y-m-').$allowed_date['end'];
            }

            $this->appendToDebugLog('min max date', ['minDate'=>$minDate, 'maxDate'=>$maxDate]);

            if(($currentDate >= $minDate) && ($currentDate <= $maxDate) && $completed_player_info){
                $success = true;
            }else{
                $errorMessageLang = 'Not right date';
            }
        }else{
            $errorMessageLang = 'Not right group level';
        }

        $result=['success'=>$success, 'message'=>$errorMessageLang, 'continue_process_after_script' => TRUE];
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
		$success=false;
		$errorMessageLang=null;
		$bonus_amount=0;

        $today = $this->utils->getTodayForMysql();
        if($this->process_mock('today', $today)){
            $this->appendToDebugLog('use mock today', ['today' => $today]);
        }

        $endAt=$this->utils->getNowForMysql();
        $levelId=$this->callHelper('getLastUpgradeLevelOrCurrentLevel',[$endAt]);
		$this->appendToDebugLog('search level id getLastUpgradeLevelOrCurrentLevel', ['endAt'=>$endAt, 'levelId'=>$levelId]);

        $bonus_settings = $description['bonus_settings'];
		if(array_key_exists($levelId, $bonus_settings)){
            $allowed_date = $description['allowed_date'];
            $this->appendToDebugLog('check allowed_date', ['allowed_date' => $allowed_date]);

            $d = new DateTime($today);
            $currentDate = $d->format('Y-m-d');
            if(!empty($allowed_date['start']) && !empty($allowed_date['end']) && ($allowed_date['end'] == 'end_of_the_month')){
                $minDate = $d->format('Y-m-').$allowed_date['start'];
                $maxDate = $this->callHelper('get_date_type', [self::DATE_THIS_MONTH_END]);
            }else{
                $minDate = $d->format('Y-m-').$allowed_date['start'];
                $maxDate = $d->format('Y-m-').$allowed_date['end'];
            }

            $this->appendToDebugLog('min max date', ['minDate'=>$minDate, 'maxDate'=>$maxDate]);

            $completed_player_info = $this->completed_player_info();

            if(($currentDate >= $minDate) && ($currentDate <= $maxDate) && $completed_player_info){
                $setting=$bonus_settings[$levelId];
                $this->appendToDebugLog('get bonus setting', ['bonus_settings'=>$setting, 'levelId'=>$levelId]);

                $bonus_amount = $setting['bonus_amount'];
                $success = true;
            }else{
                $errorMessageLang = 'Not right date';
            }

		}else{
            $errorMessageLang = 'Not right group level';
        }

		$result=['success'=>$success, 'message'=>$errorMessageLang, 'bonus_amount'=>$bonus_amount];
		return $result;
	}

    protected function completed_player_info(){
        $conditionResult = $this->player_model->getPlayerAccountInfoStatus($this->playerId);
        $completed_player_info = $conditionResult['status'];

        if(!$completed_player_info){
            $conditionResultMissingFields = !empty($conditionResult['missing_fields']) ? $conditionResult['missing_fields'] : NULL;
            $this->appendToDebugLog('not complete player info',['missing_fields'=>$conditionResultMissingFields]);
        }

        return $completed_player_info;
    }
}

