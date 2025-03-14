<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 *
 * 存送优惠
 *
 * OGP-19310
 *
 * 每月m-n号内，允许玩家申请一次免费奖金

condition:
{
    "class": "promo_rule_ole777_free_bonus_monthly",
    "allowed_date":{
        "start": "21",
        "end": "25"
    },
    "bonus_settings":{
        "242" : {"bonus_amount": 7},
        "243" : {"bonus_amount": 17},
        "244" : {"bonus_amount": 37},
        "245" : {"bonus_amount": 77},
        "246" : {"bonus_amount": 177},
        "247" : {"bonus_amount": 577},
        "248" : {"bonus_amount": 777},
        "249" : {"bonus_amount": 1777},
        "250" : {"bonus_amount": 2777},
        "251" : {"bonus_amount": 3777},
        "252" : {"bonus_amount": 7777}
    }
}

Promo Manager Mock For Class:
{
    // "today":"2020-09-10",
	"get_date_type_now":"2021-09-22 12:23:55", // use the input,"get_date_type_now" of the form
    "getLastUpgradeLevelOrCurrentLevel":"4"
}
 *
 *
 *
 */
class Promo_rule_ole777_free_bonus_monthly extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_ole777_free_bonus_monthly';
	}

	/**
	 * run bonus condition checker
	 * @param  array $description original description in rule
	 * @param  array $extra_info
	 * @param  boolean $dry_run
	 * @return  array ['success'=> success, 'message'=> errorMessageLang, 'continue_process_after_script' => TRUE]
	 */
	protected function runBonusConditionChecker($description, &$extra_info, $dry_run){
		$success=false;
		$errorMessageLang=null;

		$get_date_type_now = $this->callHelper('get_date_type',[self::TO_TYPE_NOW]);
		// Y-m-d
		$today = $this->utils->formatDateForMysql( new DateTime($get_date_type_now) );
		if($this->process_mock('today', $today)){
			$this->appendToDebugLog('use mock today', ['today'=>$today]);
		}

		$bonus_settings=$description['bonus_settings'];
		$endAt=$this->utils->getNowForMysql();
		$levelId=$this->callHelper('getLastUpgradeLevelOrCurrentLevel',[$endAt]);
		$this->appendToDebugLog('search level id getLastUpgradeLevelOrCurrentLevel', ['endAt'=>$endAt, 'levelId'=>$levelId]);

		if(array_key_exists($levelId, $bonus_settings)){
			$allowed_date=$description['allowed_date'];
			$this->appendToDebugLog('check allowed_date', ['allowed_date'=>$allowed_date]);

			$d=new DateTime($today);
            $currentDate=$d->format('Y-m-d');

            if(!empty($allowed_date['from_date']) && !empty($allowed_date['end_date'])){
                $minDate=$this->utils->formatDateForMysql(new DateTime($allowed_date['from_date']));
                $maxDate=$this->utils->formatDateForMysql(new DateTime($allowed_date['end_date']));
            }else{
                $minDate=$d->format('Y-m-').$allowed_date['start'];
                $maxDate=$d->format('Y-m-').$allowed_date['end'];
            }

			if($currentDate>=$minDate && $currentDate<=$maxDate){
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

        $today=$this->utils->getTodayForMysql();
        if($this->process_mock('today', $today)){
            $this->appendToDebugLog('use mock today', ['today'=>$today]);
        }

		$bonus_settings=$description['bonus_settings'];
		$allowed_date=$description['allowed_date'];
        $endAt=$this->utils->getNowForMysql();
		$levelId=$this->callHelper('getLastUpgradeLevelOrCurrentLevel',[$endAt]);
		$this->appendToDebugLog('search level id getLastUpgradeLevelOrCurrentLevel', ['endAt'=>$endAt, 'levelId'=>$levelId]);
		$this->appendToDebugLog('calc bonus', ['bonus_settings'=>$bonus_settings, 'allowed_date'=>$allowed_date, 'levelId'=>$levelId]);

		if(array_key_exists($levelId, $bonus_settings)){
			$setting=$bonus_settings[$levelId];

			$this->appendToDebugLog('get bonus setting', ['bonus_settings'=>$setting, 'levelId'=>$levelId]);

            $d=new DateTime($today);
            $currentDate=$d->format('Y-m-d');

            if(!empty($allowed_date['from_date']) && !empty($allowed_date['end_date'])){
                $minDate=$this->utils->formatDateForMysql(new DateTime($allowed_date['from_date']));
                $maxDate=$this->utils->formatDateForMysql(new DateTime($allowed_date['end_date']));
            }else{
                $minDate=$d->format('Y-m-').$allowed_date['start'];
                $maxDate=$d->format('Y-m-').$allowed_date['end'];
            }

            if($currentDate>=$minDate && $currentDate<=$maxDate){
                $success = true;
                $bonus_amount = $setting['bonus_amount'];
            }else{
                $errorMessageLang = 'Not right date';
            }
		}else{
            $errorMessageLang = 'Not right group level';
        }

		$result=['success'=>$success, 'message'=>$errorMessageLang, 'bonus_amount'=>$bonus_amount];
		return $result;
	}
}

