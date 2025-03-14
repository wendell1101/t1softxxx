<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';
require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 *
 * 8% bônus de depósito diário
 *
 * OGP-25828
 *
 * 优惠申请期限 , 存款优惠仅能是今日的存款
 * 今天存，今天可领优惠 ； 昨天存，今日不能领 需在今日请再存一笔

* Bonus condition && Bonus release:
{
    "class": "promo_rule_smash_deposit_bonus_on_deposit_day"
}

 *
 *
 */
class Promo_rule_smash_deposit_bonus_on_deposit_day extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_smash_deposit_bonus_on_deposit_day';
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

        $fromDate = $this->callHelper('get_date_type', [self::DATE_TODAY_START]);
        $toDate = $this->callHelper('get_date_type', [self::TO_TYPE_NOW]);
        // $hasDepositToday = $this->callHelper('getLastDepositByDate', [$fromDate, $toDate]);

        if($this->callHelper('isCheckingBeforeDeposit',[])){
            $this->appendToDebugLog('ignore trans', ['is_checking_before_deposit'=>$extra_info['is_checking_before_deposit']]);
            //it's for deposit select promotion
            $success = true;
        }else{
            $promorule =  $this->promorule;
            // if(!empty($hasDepositToday)){
                $depositSuccesionCnt = $this->promorule['depositSuccesionCnt'];
                if ($promorule['depositSuccesionType'] == promorules::DEPOSIT_SUCCESION_TYPE_NOT_FIRST) {
                    //found transaction or force
                    $depositSuccesionCnt = 2;
                } elseif ($promorule['depositSuccesionType'] == promorules::DEPOSIT_SUCCESION_TYPE_EVERY_TIME) {
                    //found transaction or force
                    $depositSuccesionCnt = -1;
                } else {
                    if ($promorule['depositSuccesionType'] == promorules::DEPOSIT_SUCCESION_TYPE_FIRST) {
                        $depositSuccesionCnt = 1;
                    } else {
                        $depositSuccesionCnt = $promorule['depositSuccesionCnt'];
                    }
                }
                $tran = $this->promorules->getAvailableDepositTran($this->playerId, $this->promorule, $depositSuccesionCnt,  $fromDate, $extra_info);
                if(!empty($tran)){
                    $success = true;
                    $tranRow = $tran;
                    $depositAmount = $tranRow->amount;
                    $tranId = $tranRow->id;
                    $bonusAmount = $this->promorules->getBonusAmount($this->promorule, $depositAmount, $this->playerId, $errorMessageLang, $extra_info, $dry_run);
                    $extra_info['bonusAmount'] = $bonusAmount;
                    $extra_info['depositAmount'] = $depositAmount;
                    $extra_info['depositTranId'] = $tranId;
                    $this->appendToDebugLog('runBonusConditionChecker found depositTrans', [
                        'depositTranId'=> $tranId,
                        'depositAmount'=> $depositAmount,
                        'bonusAmount'=> $bonusAmount,
                    ]);
                } else {

                    $errorMessageLang = 'promo_custom.no_deposit_record_found';
                }
            // }else{
            //     $errorMessageLang = 'promo_custom.no_deposit_record_found';
            // }
        }
        
        // $result=['success'=>$success, 'message'=>$errorMessageLang, 'continue_process_after_script' => TRUE];
        $result=[
            'success'=>$success, 
            'message'=>$errorMessageLang, 
            'continue_process_after_script' => FALSE,
            'deposit_tran_id' => !empty($tranId) ? $tranId : null
        ];
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
        return $this->returnUnimplemented();
    }
}
