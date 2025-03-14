<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 *
 * Welcome Bonus Sports 180% (1st tier 30%)
 * Welcome Bonus Sports 180% (2nd tier 50%)
 * Welcome Bonus Sports 180% (3rd tier 100%)
 *
 * OGP-23544
 *
 * 最低存款100
 *
 * 如果首存不是存Welcome Bonus Sports 180% (1st tier 30%), 則無法申請此系列優惠
 * 如果首存、第二筆存款不是存Welcome Bonus Sports 180% (1st tier 30%) / (2nd tier 50%), 則無法申請此系列優惠
 *
 * 首存只能申請 Welcome Bonus Sports 180% (1st tier 30%)
 * 第二筆存款只能申請 Welcome Bonus Sports 180% (2nd tier 50%)
 * 第三筆存款只能申請 Welcome Bonus Sports 180% (3nd tier 100%)
 *


condition:
{
    "class": "promo_rule_ole777idr_deposit_welcome_bonus",
    "serise_promorule_id":{
        "first": "",
        "second": "",
        "third": ""
    }
}

 *
 *
 *
 */
class Promo_rule_ole777idr_deposit_welcome_bonus extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_ole777idr_deposit_welcome_bonus';
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

        $serise_promo_1 = $description['serise_promorule_id']['first'];
        $serise_promo_2 = $description['serise_promorule_id']['second'];
        $serise_promo_3 = $description['serise_promorule_id']['third'];
        $this->appendToDebugLog('serise promo id', ['first' => $serise_promo_1, 'second' => $serise_promo_2, 'third' => $serise_promo_3]);

        $fromDate = $this->get_date_type(self::REGISTER_DATE);
        $toDate = $this->get_date_type(self::TO_TYPE_NOW);
        $this->appendToDebugLog('from date and to date times', ['fromDate' => $fromDate, 'toDate' => $toDate]);

        $deposit_cnt = intval($this->callHelper('countDepositByPlayerId',[$fromDate, $toDate]));
        $this->appendToDebugLog('get deposit times', ['deposit_cnt' => $deposit_cnt]);


        $all_player_promo = $this->callHelper('get_all_released_player_promo_by_times', [null, 3]);
        $never_apply_promo = empty($all_player_promo) ? true : false;
        $first_player_promo = !empty($all_player_promo[0]) ? $all_player_promo[0]['promorulesId'] : null;
        $second_player_promo = !empty($all_player_promo[1]) ? $all_player_promo[1]['promorulesId'] : null;
        $third_player_promo = !empty($all_player_promo[2]) ? $all_player_promo[2]['promorulesId'] : null;

        $this->appendToDebugLog('get all released player promo by times', $all_player_promo);

        if(!empty($third_player_promo)){
            if($third_player_promo == $serise_promo_3){
                $this->appendToDebugLog('player cannot apply serise promo 3 again with the third deposit');
            }else{
                $this->appendToDebugLog('player cannot apply serise promo 3, becasue player had applied other promo with the third deposit', ['promoruleId' => $third_player_promo]);
            }
            $errorMessageLang = 'promo_rule.common.error';
        }else if(!empty($second_player_promo)){
            if($first_player_promo == $serise_promo_1){
                if($second_player_promo == $serise_promo_2){
                    $success = true;
                    $this->appendToDebugLog('player can apply serise promo 3 with the third deposit');
                }else{
                    $errorMessageLang = 'promo_rule.common.error';
                    $this->appendToDebugLog('player cannot apply serise promo 3, becasue player had applied other promo with the second deposit', ['promoruleId' => $second_player_promo]);
                }
            }else{
                $this->appendToDebugLog('player cannot apply serise promo 3, becasue player had applied other promo with the first deposit', ['promoruleId' => $first_player_promo]);
            }
        }elseif (!empty($first_player_promo)){
            if($first_player_promo == $serise_promo_1){
                $success = true;
                $this->appendToDebugLog('player can apply serise promo 2 with the second deposit');
            }else{
                $errorMessageLang = 'promo_rule.common.error';
                $this->appendToDebugLog('player cannot apply serise promo 2, becasue player had applied other promo with the first deposit', ['promoruleId' => $first_player_promo]);
            }
        }elseif ($never_apply_promo){
            $success = true;
            $this->appendToDebugLog('player can apply serise promo 1 with the first deposit');
        }else{
            $errorMessageLang = 'promo_rule.common.error';
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
        return $this->returnUnimplemented();
	}
}