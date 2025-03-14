<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 *
 * 7th Deposit amount of the month
 *
 * OGP-35743
 *
 * 最低存款 500
 * 每月第7筆存款可申請,一個月限申請一次
 * 取款條件: 存款+獎金 * 5
 *
condition:
{
    "class": "promo_rule_ole777idr_nth_deposit_bonus_monthly",
	"min_dep_count": 7,
	"min_dep": 500,
	"bonus_settings": [
		{"min_deposit":    500, "max_deposit":   1000, "bonus_amount":   50},
		{"min_deposit":   1000, "max_deposit":  10000, "bonus_amount":   77},
		{"min_deposit":  10000, "max_deposit":  50000, "bonus_amount":  777},
		{"min_deposit":  50000, "max_deposit": 100000, "bonus_amount": 3700},
		{"min_deposit": 100000, "max_deposit":     -1, "bonus_amount": 7700}
	]
}

 *
 *
 *
 */
class Promo_rule_ole777idr_nth_deposit_bonus_monthly extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_ole777idr_nth_deposit_bonus_monthly';
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

	private function checkCustomizeBounsCondition($description, &$errorMessageLang, &$extra_info){
		$success = false;
		$bonus_amount = 0;
		$deposit_amount = 0;
		$depositTranId = null;

		// check if player has applied this promo this month
        $approvedPromo = $this->callHelper('count_approved_promo',[$this->promorulesId, self::DATE_TYPE_THIS_MONTH]);
        $this->appendToDebugLog('get month released promo', ['month release count' => $approvedPromo]);
        if(!empty($approvedPromo)){
            $errorMessageLang = 'notify.83';
            return ['success'=>$success, 'message'=>$errorMessageLang];
        }

		$start = $this->callHelper('get_date_type', [self::DATE_THIS_MONTH_START]);
		$end = $this->callHelper('get_date_type', [self::TO_TYPE_NOW]);
		$depositCount = $this->callHelper('countDepositByPlayerId', [$start, $end]);
		$minDepCount = !empty($description['min_dep_count']) ? $description['min_dep_count'] : 7;
		$this->appendToDebugLog('get deposit count', ['start'=>$start, 'end'=>$end, 'depositCount'=>$depositCount, 'minDepCount'=>$minDepCount]);

		if($this->callHelper('isCheckingBeforeDeposit',[])){
			// deposit amount from deposit page
			$this->appendToDebugLog('ignore trans', ['is_checking_before_deposit'=>$extra_info['is_checking_before_deposit']]);

			// // check if player have deposit 6 times this month
			$limitCount = $minDepCount-1;
			// 存綁優, 只有當月存了6筆才能申請
			if($limitCount != $depositCount){
				$errorMessageLang = 'notify.80';
				return ['success' => $success, 'message' => $errorMessageLang];
			}

			// 7th deposit amount
			$deposit_amount = !empty($extra_info['depositAmount']) ? $extra_info['depositAmount'] : 0;
		}else{
			// check if player have deposit 7 times this month
			// 只有當月第七筆才能申請
			if($minDepCount != $depositCount){
				$errorMessageLang = 'notify.80';
				return ['success' => $success, 'message' => $errorMessageLang];
			}
			// // get 7th deposit amount
			$trans = $this->callHelper('getLastDepositByDate', [$start, $end]);
			$this->appendToDebugLog('check date and trans id', ['start'=>$start, 'end'=>$end, 'trans'=>$trans]);

			if(empty($trans)){
				$errorMessageLang = 'promo_custom.deposit_donot_match_the_requirement';
				return ['success' => $success, 'message' => $errorMessageLang];
			}

			$depositTranId = $trans['id'];
			$deposit_amount = $trans['amount'];

			$existsTransByTypesAfter = false;
			if(!empty($trans)) {
				$existsTransByTypesAfter = $this->callHelper('existsTransByTypesAfter', [$this->playerId, $this->promorule, $trans['created_at'], $extra_info]);
				$this->appendToDebugLog('check existsTransByTypesAfter in custom promo', ['existsTransByTypesAfter'=> $existsTransByTypesAfter]);
			}

			if($existsTransByTypesAfter){
				$errorMessageLang = 'promo_rule.common.error';
				return ['success' => $success, 'message' => $errorMessageLang];
			}
		}

		$min_dep = !empty($description['min_dep']) ? $description['min_dep'] : 500;
		if($min_dep > $deposit_amount){
			$errorMessageLang = 'notify.79';
			return ['success' => $success, 'message' => $errorMessageLang];
		}

		$bonus_settings = !empty($description['bonus_settings']) ? $description['bonus_settings'] : [];
		if (!empty($bonus_settings) && is_array($bonus_settings)) {
            foreach ($bonus_settings as $list) {
                if(($list['min_deposit'] <= $deposit_amount) &&
                    ($deposit_amount < $list['max_deposit'] || $list['max_deposit']<0)){
                    //max_deposit<0 means no limit
                    $success = true;
                    $bonus_amount = $list['bonus_amount'];
                }
            }
		}

		return ['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonus_amount, 'deposit_tran_id' => $depositTranId];
	}
}