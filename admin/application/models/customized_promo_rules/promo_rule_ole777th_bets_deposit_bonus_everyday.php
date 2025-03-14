<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 *
 * Cash Drop
 *
 * OGP-35786
 * 優惠期間 2025/4/1 - 2025/4/31
 * 依照不同VIP level 和 存款/投注金額，拿不同獎金
 * 每日都可以申請一次
 * 玩家每日需要先達到存款 和 投注要求，才能申請
 * 取款條件: 獎金 x 5
 * 
 * 例:
 * 玩家testA，vip 3，今日存50,000 和 投注500,000，申請可拿獎金500
 * 取款條件: 500 x 5 = 2500
 * 
 * 玩家testB，vip 9，今日存100,000 和 投注5,000,000，申請可拿獎金1,000
 * 取款條件: 1000 x 5 = 5000
 * 
 * 
 * LEVEL   deposit        bet   Bonus   WC
 * VIP0-3    5,000     50,000     100   5x
 *          50,000    500,000     500   5x
 *         100,000  1,000,000   1,000   5x
 * VIP4-8   20,000    200,000     200   5x
 *         100,000  1,000,000   1,000   5x
 *         300,000  3,000,000   2,000   5x
 * VIP9-11 100,000  1,000,000   1,000   5x
 *         300,000  3,000,000   2,000   5x
 *         500,000  5,000,000   4,000   5x
 * 

condition:
{
    "class": "promo_rule_ole777th_bets_deposit_bonus_everyday",
	"allowed_date": {
        "start": "2025-04-01",
        "end": "2025-04-31"
    },
    "bonus_settings": [
		{
			"level": [VIP0,VIP1,VIP2,VIP3],
			"conditions": [
				{"min_bets": 50000, "min_deposit": 5000, "fixed_bonus": 100},
				{"min_bets": 500000, "min_deposit": 50000, "fixed_bonus": 500},
				{"min_bets": 1000000, "min_deposit": 100000, "fixed_bonus": 1000}
			]
		},
		{
			"level": [VIP4,VIP5,VIP6,VIP7,VIP8],
			"conditions": [
				{"min_bets": 200000, "min_deposit": 20000, "fixed_bonus": 200},
				{"min_bets": 1000000, "min_deposit": 100000, "fixed_bonus": 1000},
				{"min_bets": 3000000, "min_deposit": 300000, "fixed_bonus": 2000}
			]
		},
		{
			"level": [VIP9,VIP10,VIP11],
			"conditions": [
				{"min_bets": 1000000, "min_deposit": 100000, "fixed_bonus": 1000},
				{"min_bets": 3000000, "min_deposit": 300000, "fixed_bonus": 2000},
				{"min_bets": 5000000, "min_deposit": 500000, "fixed_bonus": 4000}
			]
		}
    ]
}

mock:
{
    "today": "2018-09-20"
}

 *
 *
 */
class Promo_rule_ole777th_bets_deposit_bonus_everyday extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_ole777th_bets_deposit_bonus_everyday';
	}

	/**
	 * run bonus condition checker
	 * @param  array $description original description in rule
	 * @param  array $extra_info
	 * @param  boolean $dry_run
	 * @return  array ['success'=> success, 'message'=> errorMessageLang]
	 */
	protected function runBonusConditionChecker($description, &$extra_info, $dry_run){
		$errorMessageLang = null;
		$result = $this->checkCustomizeBonusCondition($description, $extra_info, $errorMessageLang);

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
		$request = $this->checkCustomizeBonusCondition($description, $extra_info, $errorMessageLang);

        return $request;
	}

	protected function checkCustomizeBonusCondition($description, &$extra_info, &$errorMessageLang){
		$success = false;
		$bonus_amount = 0;

		$today=$this->utils->getTodayForMysql();
		if($this->process_mock('today', $today)){
			//use mock data
			$this->appendToDebugLog('use mock today', ['today'=>$today]);
		}

		//check date
		$allowed_date=$description['allowed_date'];
		$this->appendToDebugLog('check allowed_date', ['allowed_date'=>$allowed_date]);

		$d=new DateTime($today);
		$currentDate=$d->format('Y-m-d');
		$minDate = !empty($allowed_date['start']) ? $allowed_date['start'] : null;
		$maxDate = !empty($allowed_date['end']) ? $allowed_date['end'] : null;
		if($minDate==null || $maxDate==null){
			$errorMessageLang='No date setting';
			return ['success' => $success, 'message' => $errorMessageLang];
		}

		$availableDate = false;
		if($minDate<=$currentDate && $currentDate<=$maxDate){
			$availableDate=true;
		}
		$this->appendToDebugLog('check date', ['minDate'=>$minDate, 'maxDate'=>$maxDate, 'currentDate'=>$currentDate, 'availableDate'=>$availableDate]);

		if(!$availableDate){
			$errorMessageLang='Not right date';
			return ['success' => $success, 'message' => $errorMessageLang];
		}

        $fromDate = $today . ' ' . Utils::FIRST_TIME;
        $toDate = $today . ' ' . Utils::LAST_TIME;

		$approvedPromoToday = $this->callHelper('count_approved_promo',[$this->promorulesId, self::DATE_TYPE_TODAY]);
        $this->appendToDebugLog('get today released promo', ['today release count' => $approvedPromoToday]);
        if(!empty($approvedPromoToday)){
            $errorMessageLang = 'notify.83';
            return ['success'=>$success, 'message'=>$errorMessageLang];
        }

		$bonus_settings = !empty($description['bonus_settings']) ? $description['bonus_settings'] : null;
		if(empty($bonus_settings)){
			$errorMessageLang = 'No bonus settings';
			return ['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonus_amount];
		}

		$settings = null;
		foreach($bonus_settings as $bonus_setting){
			$level = $bonus_setting['level'];
			$conditions = $bonus_setting['conditions'];

			if(in_array($this->levelId, $level)){
				$settings = $conditions;
				break;
			}
		}

		if(empty($settings)){
			$errorMessageLang = 'Not right vip level';
			return ['success' => $success, 'message' => $errorMessageLang];
		}

		//check bets and deposit
		$deposit = $this->callHelper('sum_deposit_amount', [$fromDate, $toDate, 0]);
		$playerGameTotal = $this->callHelper('getPlayerTotalBetWinLoss', [$fromDate, $toDate]);
		$bets = $playerGameTotal['total_bet'];
		$this->appendToDebugLog('getBetsAndDepositByDate', ['fromDate'=>$fromDate, 'toDate'=>$toDate, 'bets'=>$bets, 'deposit'=>$deposit]);

		foreach($settings as $setting){
			$min_bets = $setting['min_bets'];
			$min_deposit = $setting['min_deposit'];
			$fixed_bonus = $setting['fixed_bonus'];

			$betsValid = $bets >= $min_bets;
            $depositValid = $deposit >= $min_deposit;

			if(!$betsValid || !$depositValid){
				$this->appendToDebugLog('checkBetsAndDeposit', ['min_bets'=>$min_bets, 'min_deposit'=>$min_deposit, 'betsValid'=>$betsValid, 'depositValid'=>$depositValid]);
			}

			if ($betsValid && $depositValid) {
                $bonus_amount = $fixed_bonus;
				$success = true;
            }
		}

		if (!$success) {
			$errorMessageLang = "promo_rule.common.error";
			return ['success' => $success, 'message' => $errorMessageLang];
		}

		return ['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonus_amount];
	}

}