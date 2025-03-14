<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 *
 * Fastwin 15 - 17 Payday Bonus
 *
 * OGP-34553
 *
 * PHP
 * 只计算期间玩家的第一笔成功的存款 （会存在冲突，但客户ok）
 * 每月15 - 17号的首存，领取日是19号
 * 一个期间只能领取一次
 * 取款条件 3倍流水
 * 
condition:
{
    "class": "promo_rule_fastplay_period_first_deposit_bonus",
    "deposit_allowed_date": {
        "start": "15",
        "end": "17"
    },
	"release_date": "19",
	"min_deposit": 250,
    "bonus_settings": [
        {"min_deposit":   250, "max_deposit":   500, "bonus_amount":   10},
        {"min_deposit":   500, "max_deposit":  1000, "bonus_amount":   20},
        {"min_deposit":  1000, "max_deposit":  2000, "bonus_amount":   50},
        {"min_deposit":  2000, "max_deposit":  5000, "bonus_amount":  100},
        {"min_deposit":  5000, "max_deposit": 10000, "bonus_amount":  250},
        {"min_deposit": 10000, "max_deposit": 15000, "bonus_amount":  500},
        {"min_deposit": 15000, "max_deposit": 25000, "bonus_amount":  750},
        {"min_deposit": 25000, "max_deposit":    -1, "bonus_amount": 1000}
    ]
}

 *
 * Fastwin last 3 day of the month Payday Bonus
 * OGP-34556
 * 
 * PHP
 * 每月最后三天的首存，领取日是下个月3号
 * 一个期间只能领取一次
 * 取款条件 3倍流水
{
    "class": "promo_rule_fastplay_period_first_deposit_bonus",
    "deposit_allowed_date": {
        "custom": "last_n_days",
        "n_days": 3
    },
    "release_date": "3",
    "min_deposit": 250,
    "bonus_settings": [
        {"min_deposit":   250, "max_deposit":   500, "bonus_amount":   10},
        {"min_deposit":   500, "max_deposit":  1000, "bonus_amount":   20},
        {"min_deposit":  1000, "max_deposit":  2000, "bonus_amount":   50},
        {"min_deposit":  2000, "max_deposit":  5000, "bonus_amount":  100},
        {"min_deposit":  5000, "max_deposit": 10000, "bonus_amount":  250},
        {"min_deposit": 10000, "max_deposit": 15000, "bonus_amount":  500},
        {"min_deposit": 15000, "max_deposit": 25000, "bonus_amount":  750},
        {"min_deposit": 25000, "max_deposit":    -1, "bonus_amount": 1000}
    ]
}
 *
 *
 *
 */
class Promo_rule_fastplay_period_first_deposit_bonus extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_fastplay_period_first_deposit_bonus';
	}

	/**
	 * run bonus condition checker
	 * @param  array $description original description in rule
	 * @param  array $extra_info
	 * @param  boolean $dry_run
	 * @return  array ['success'=> success, 'message'=> errorMessageLang, 'continue_process_after_script' => TRUE]
	 */
	protected function runBonusConditionChecker($description, &$extra_info, $dry_run){
		$errorMessageLang = null;
		$result = $this->checkCustomizeBounsCondition($description, $errorMessageLang, $extra_info);

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
		$result = $this->checkCustomizeBounsCondition($description, $errorMessageLang, $extra_info);

		return $result;
	}

	protected function getLastNDaysOfLastMonth($n_days=3) {
		$lastNDays = [];
		$day_counter = $n_days-1;

		$currentDate = new DateTime();
		$currentDate->modify('first day of last month');
		$lastDayOfLastMonth = $currentDate->modify('last day of this month');

		for ($i = $day_counter; $i >= 0; $i--) {
			$day = clone $lastDayOfLastMonth;
			$day->modify("-$i day");
			$lastNDays[] = $day->format('Y-m-d');
		}

		return $lastNDays;
	}

	private function checkCustomizeBounsCondition($description, &$errorMessageLang, &$extra_info){
		$success = false;
		$bonus_amount = 0;
		$deposit_amount = 0;
		$deposit_tran_id = null;

		$bonus_settings = !empty($description['bonus_settings']) ? $description['bonus_settings'] : null;
		if(empty($bonus_settings)){
			$errorMessageLang = 'Not exist bet Setting';
			return ['success'=>$success, 'message'=>$errorMessageLang];
		}

		$today=$this->utils->getTodayForMysql();
		if($this->process_mock('today', $today)){
			$this->appendToDebugLog('use mock today', ['today'=>$today]);
		}

		$d=new DateTime($today);
		$deposit_allowed_date=$description['deposit_allowed_date'];

		if(!empty($deposit_allowed_date['custom']) && !empty($deposit_allowed_date['n_days'])){
			$custom = $deposit_allowed_date['custom'];
			$n_days = (int) $deposit_allowed_date['n_days'];
			$lastNDays = $this->getLastNDaysOfLastMonth($n_days);
			if(empty($lastNDays)){
				$errorMessageLang = 'Unknown Error';
				return ['success' => $success, 'message' => $errorMessageLang];
			}

			$startDatetime = $lastNDays[0] . ' ' . Utils::FIRST_TIME;
			$endDatetime = $lastNDays[$n_days-1] . ' ' . Utils::LAST_TIME;
		}else if(!empty($deposit_allowed_date['start']) && !empty($deposit_allowed_date['end'])){
			$startDatetime = $d->format('Y-m-') . $deposit_allowed_date['start'] . ' ' . Utils::FIRST_TIME;
			$endDatetime = $d->format('Y-m-') . $deposit_allowed_date['end'] . ' ' . Utils::LAST_TIME;
		}

		$releaseDate=$d->format('Y-m-') . $description['release_date'];
		$currentDate=$d->format('Y-m-d');

		// check if player has applied this promo in this month
		$approvedPromo = $this->callHelper('count_approved_promo',[$this->promorulesId, self::DATE_TYPE_THIS_MONTH]);
		$this->appendToDebugLog('get released promo', ['this month  release count' => $approvedPromo]);
		if(!empty($approvedPromo)){
			$errorMessageLang = 'notify.83';
			return ['success'=>$success, 'message'=>$errorMessageLang];
		}

		if($currentDate != $releaseDate){
			$errorMessageLang='Not right date';
			return ['success' => $success, 'message' => $errorMessageLang];
		}

		$trans = $this->callHelper('getAnyDepositByDate',[$startDatetime, $endDatetime, 'first', null, null]);
		$this->appendToDebugLog('check date and trans id', ['currentDate'=>$currentDate, 'minDate'=>$startDatetime, 'maxDate'=>$endDatetime, 'trans'=>$trans]);

		if(empty($trans)){
			$errorMessageLang = 'promo_custom.deposit_donot_match_the_requirement';
			return ['success' => $success, 'message' => $errorMessageLang];
		}

		$deposit_amount = $trans['amount'];

		$min_deposit = $description['min_deposit'];
		if($deposit_amount < $min_deposit){
			$errorMessageLang = 'promo_custom.deposit_sum_insufficient';
			return ['success' => $success, 'message' => $errorMessageLang];
		}

		$found = false;
		foreach($bonus_settings as $setting){
			if($deposit_amount >= $setting['min_deposit'] && ($setting['max_deposit']<0 || $deposit_amount < $setting['max_deposit'])){
				$bonus_amount = $setting['bonus_amount'];
				$found = true;
				break;
			}
		}

		if(!$found){
			$errorMessageLang='promo_rule.common.error';
			return ['success' => $success, 'message' => $errorMessageLang];
		}

		$success = true;

		return ['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonus_amount];
	}
}