<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 * 5000 to 7500 PHP can claim Php 50
 * 7501 to 20000 PHP can claim Php 100
 * 20001 to 30000 PHP can claim Php 250
 * 30001 to 40000 PHP can claim Php 500
 * 40001 to 50000 PHP can claim Php750
 * 50001 PHP + can claim Php 1000
 
{
    "class": "promo_rule_festival_deposit_bonus",
    "start": "Y-m-d H:i:s",
    "end": "Y-m-d H:i:s",
    "bonus_settings": [
        {"min_deposit": 5000, "max_deposit": 7500, "bonus_amount": 50},
        {"min_deposit": 7501, "max_deposit": 20000, "bonus_amount": 100},
        {"min_deposit": 20001, "max_deposit": 30000, "bonus_amount": 250},
        {"min_deposit": 30001, "max_deposit": 40000, "bonus_amount": 500},
        {"min_deposit": 40001, "max_deposit": 50000, "bonus_amount": 750},
        {"min_deposit": 50001, "max_deposit": 999999999999999, "bonus_amount": 1000}
    ]
}
 *
 */
class Promo_rule_festival_deposit_bonus extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_festival_deposit_bonus';
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

    public function getPlayersDepositByDate($playerId, $fromDate, $toDate){
	    $this->load->model(['sale_order']);
	    return $this->sale_order->sumDepositRequestsByDate($playerId, $fromDate, $toDate);
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
        $bonusAmount = 0;
		$bonus_settings = $description['bonus_settings'];
        $min_deposit = $bonus_settings[0]['min_deposit'];

        $this->appendToDebugLog('checkCustomizeBonusCondition promorulesId', ['promorulesId' => $this->promorulesId]);

        $fromDate = $description['start'];
        $toDate = $description['end'];

        $today = $this->utils->getTodayForMysql();
        $this->appendToDebugLog('runBonusConditionChecker check date', ['fromDate'=>$fromDate, 'toDate'=>$toDate, 'today'=>$today]);

        #check if it has been claimed
        $count_approved_promo = $this->callHelper('count_approved_promo', [$this->promorulesId, 'customize', array('start'=>$fromDate, 'end'=>$toDate)]);
        if($count_approved_promo > 0){
            $errorMessageLang = 'Promo has been claimed';
            return ['success' => $success, 'message' => $errorMessageLang];
        }

        $playerTotalDeposit = $this->getPlayersDepositByDate($this->playerId, $fromDate, $toDate);
        $this->appendToDebugLog('checkCustomizeBonusCondition playerTotalDeposit', ['playerTotalDeposit' => $playerTotalDeposit]);
        
        if (!empty($bonus_settings)) {
            if(is_array($bonus_settings)){
                if ($playerTotalDeposit < $min_deposit) {
                    $errorMessageLang = 'No enough deposit';
                }else{
                    foreach ($bonus_settings as $list) {
                        if($playerTotalDeposit >= $list['min_deposit'] && $playerTotalDeposit <= $list['max_deposit']){
                            $success = true;
                            $bonusAmount = $list['bonus_amount'];
                        }
                    }
                }
            }
        } else {
            $this->appendToDebugLog('Not exist bonus Setting');
            $errorMessageLang = 'promo_rule.common.error';
        }

        $this->appendToDebugLog('checkCustomizeBonusCondition', ['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonusAmount]);
        return ['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonusAmount];
    }
}
