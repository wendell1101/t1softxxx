<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 *
 * OGP-27970
 *
 * 檢查 2022-12-20 ~ 2023-01-21 使否存在至少一筆100(100000VND)的存款紀錄
 * 2023-01-21 ~ 2023-01-25 開放申請優惠
 * 每人只能申請一次
 * 取款條件 5 倍
 *

condition:
{
    "class": "promo_rule_ole777vnd_new_year_daily_bonus",
    "min_deposit": 100,
    "deposit_date": {
        "from_date": "2022-12-20",
        "to_date": "2023-01-21"
    },
    "allowed_date": {
        "from_date": "2023-01-21",
        "to_date": "2023-01-25"
    },
    "bonus_settings": {
        "VIP1" : { "bonus_amount": 17},
        "VIP2" : { "bonus_amount": 37},
        "VIP3" : { "bonus_amount": 77},
        "VIP4" : { "bonus_amount": 107},
        "VIP5" : { "bonus_amount": 170},
        "VIP6" : { "bonus_amount": 270},
        "VIP7" : { "bonus_amount": 370},
        "VIP8" : { "bonus_amount": 770},
        "VIP9" : { "bonus_amount": 1070},
        "VIP10" : { "bonus_amount": 1700}
    }
}

 *
 *
 */
class Promo_rule_ole777vnd_new_year_daily_bonus extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_ole777vnd_new_year_daily_bonus';
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
        $result = $this->checkCustomizeBounsCondition($description, $extra_info, $errorMessageLang);

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
        $result = $this->checkCustomizeBounsCondition($description, $extra_info, $errorMessageLang);

        return $result;
	}

    private function checkCustomizeBounsCondition($description, &$extra_info, &$errorMessageLang){
        $success = false;
        $bonus_amount = 0;

        $get_date_type_now = $this->callHelper('get_date_type',[self::TO_TYPE_NOW]);
        // Y-m-d
        $today = $this->utils->formatDateForMysql( new DateTime($get_date_type_now) );
        $d = new DateTime($today);
        $currentDate = $d->format('Y-m-d');

        $min_deposit = $description['min_deposit'];
        $deposit_date = $description['deposit_date'];
        $allowed_date = $description['allowed_date'];

        if(!empty($allowed_date['from_date']) && !empty($allowed_date['to_date'])){
            $minDate = $this->utils->formatDateForMysql(new DateTime($allowed_date['from_date']));
            $maxDate = $this->utils->formatDateForMysql(new DateTime($allowed_date['to_date']));
        }

        $this->appendToDebugLog('allow date', ['result'=>$allowed_date]);

        $bonus_settings=$description['bonus_settings'];
        $endAt = $this->utils->getNowForMysql();
        $levelId = $this->callHelper('getLastUpgradeLevelOrCurrentLevel',[$endAt]);
        $this->appendToDebugLog('search level id getLastUpgradeLevelOrCurrentLevel', ['endAt' => $endAt, 'levelId' => $levelId]);

        if($currentDate>=$minDate && $currentDate<=$maxDate){
            if(array_key_exists($levelId, $bonus_settings)){
                $depositFrom = $deposit_date['from_date'] . ' ' . Utils::FIRST_TIME;
                $depositTo = $deposit_date['to_date'] . ' ' . Utils::LAST_TIME;

                $deposit_record = $this->callHelper('sum_deposit_amount', [$depositFrom, $depositTo, $min_deposit]);
                $met_deposit = !empty($deposit_record) ? true : false;
                $this->appendToDebugLog('deposit date', ['result' => $deposit_date, 'met deposit' => $met_deposit]);
                if($met_deposit){
                    $setting = $bonus_settings[$levelId];
                    $bonus_amount = $setting['bonus_amount'];
                    $success = true;
                }else{
                    $errorMessageLang = 'promo_custom.deposit_donot_match_the_requirement';
                }
            }else{
                $errorMessageLang = 'Not right group level';
            }
        }else{
            $errorMessageLang = 'Not right date';
        }

        return ['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonus_amount];
    }
}
