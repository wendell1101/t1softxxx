<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 *
 * Retention - Inactive Member 77K Freebet
 *

condition:
{
    "class": "promo_rule_member_77k_freebet",
    "donot_allow_any_available_bet_after_deposit": true
}

 *
 *
 */
class Promo_rule_member_77k_freebet extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_member_77k_freebet';
	}

	/**
	 * run bonus condition checker
	 * @param  array $description original description in rule
	 * @param  array $extra_info
	 * @param  boolean $dry_run
	 * @return  array ['success'=> success, 'message'=> errorMessageLang, 'continue_process_after_script' => TRUE]
	 */
	protected function runBonusConditionChecker($description, &$extra_info, $dry_run){
		$success = true;
		$errorMessageLang = null;

        $not_allow_bet_after_deposit = $description['donot_allow_any_available_bet_after_deposit'];

        if($not_allow_bet_after_deposit){
            $promorule = $this->promorule;
            $periodFrom = $promorule['applicationPeriodStart'];
            $periodTo = $promorule['hide_date'];
            $trans = $this->callHelper('getLastDepositByDate', [$periodFrom, $periodTo]);
            $this->appendToDebugLog('check date and trans id', ['periodFrom' => $periodFrom, 'periodTo' => $periodTo, 'trans' => $trans]);

            $this->load->model(['game_logs']);
            $playerId = $this->playerId;
            $created_at = $trans['created_at'];

            list($totalBet, $totalWin, $totalLoss) = $this->game_logs->getTotalBetsWinsLossByPlayers($playerId, $created_at, $this->utils->getNowForMysql());
            $totalWin = (float)$totalWin;
            $totalLoss = (float)$totalLoss;
            if($totalWin != 0 || $totalLoss != 0){
                $extra_info['error_message']='notify.promo_donot_allow_any_available_bet_after_deposit';
                $this->appendToDebugLog($extra_info['debug_log'], 'customize promo_donot_allow_any_available_bet_after_deposit',
                    ['created_at'=>$created_at, 'playerId'=>$playerId]);
                $success = false;
                return ['success' => $success, 'message' => $extra_info['error_message']];
            }
        }

		return ['success' => $success, 'message' => $errorMessageLang, 'continue_process_after_script' => FALSE];
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