<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 *
 * OGP-31647
 * 玩家allow_apply_range累積總存款
 * 按總存款金額依照比例發獎金
 * 只能申請一次
 * 5倍取款條件
 * 玩家需到玩家中心申請優惠, 自動發放獎金
 *
condition:
{
    "class": "promo_rule_sssbet_deposit_percentage_bonus",
    "allow_apply_range": {
        "from_date": "12-01",
        "to_date": "12-24"
    },
    "deposit_trans_range": {
        "from_date": "12-25",
        "to_date": "12-25"
    },
    "min_deposit": 50,
    "bonus_settings": [
        {"min_deposit":    50, "max_deposit":  2000, "bonus_percentage":  6},
        {"min_deposit":  2000, "max_deposit":  5000, "bonus_percentage":  7},
        {"min_deposit":  5000, "max_deposit": 10000, "bonus_percentage":  8},
        {"min_deposit": 10000, "max_deposit": 30000, "bonus_percentage":  9},
        {"min_deposit": 30000, "max_deposit":    -1, "bonus_percentage": 10}
    ]
}
*
*
*/
class Promo_rule_sssbet_deposit_percentage_bonus extends Abstract_promo_rule{

    public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
        parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
    }

    public function getClassName(){
        return 'Promo_rule_sssbet_deposit_percentage_bonus';
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

    private function getTotalDepositAmount($from_date, $to_date){
        $total_deposit_amount = 0;
        $total_deposit_amount = $this->callHelper('sum_deposit_amount',[$from_date, $to_date, 0]);
        return $total_deposit_amount;
    }

    private function checkCustomizeBonusCondition($description, &$errorMessageLang){
        $success = false;
	    $bonus_amount = 0;
        $deposit_amount = 0;
        $percentage=0;

        $allow_apply_range = $description['allow_apply_range'];
        $deposit_trans_range = $description['deposit_trans_range'];
        $bonus_settings = $description['bonus_settings'];
        $min_deposit = $description['min_deposit'];

        $today = $this->utils->getTodayForMysql();
        if($this->process_mock('today', $today)){
            $this->appendToDebugLog('use mock today', ['today'=>$today]);
        }

        $this->appendToDebugLog('runBonusConditionChecker check date', ['today'=>$today, 'allow_apply_range'=>$allow_apply_range, 'deposit_trans_range'=>$deposit_trans_range]);

        $d = new DateTime($today);
        $currentDate = $d->format('Y-m-d');

        if(!empty($allow_apply_range['from_date']) && !empty($allow_apply_range['to_date'])){
            $allow_apply_from = $d->format('Y-') . $allow_apply_range['from_date'];
            $allow_apply_to = $d->format('Y-') . $allow_apply_range['to_date'];
        }

        if(!empty($deposit_trans_range['from_date']) && !empty($deposit_trans_range['to_date'])){
            $dep_trans_from = $d->format('Y-') . $deposit_trans_range['from_date'] . ' ' . Utils::FIRST_TIME;
            $dep_trans_to = $d->format('Y-') . $deposit_trans_range['to_date'] . ' ' . Utils::LAST_TIME;
        }

        $deposit_amount = $this->getTotalDepositAmount($dep_trans_from, $dep_trans_to);
        $found=false;

        if($currentDate>=$allow_apply_from && $currentDate<=$allow_apply_to){
            if($deposit_amount >= $min_deposit){
                foreach ($bonus_settings as $setting){
                    if($setting['min_deposit']<=$deposit_amount && ($deposit_amount<$setting['max_deposit'] || $setting['max_deposit']<0)){
                        //max_deposit<0 means no limit
                        $percentage = $setting['bonus_percentage']/100;
                        $bonus_amount = $deposit_amount * $percentage;
                        $found=true;
                        break;
                    }
                }
                if($found){
                    $success = $bonus_amount>0;
                }else{
                    $errorMessageLang='Not fit any bonus setting';
                }
            }else{
                $errorMessageLang = 'promo_custom.deposit_sum_insufficient';
            }
        }else{
            $errorMessageLang = 'Not right date';
        }
        $this->appendToDebugLog('apply result', ['bonus amount'=>$bonus_amount, 'total deposit'=>$deposit_amount, 'percentage'=>$percentage]);

        return ['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonus_amount];
    }
}