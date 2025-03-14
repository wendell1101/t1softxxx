<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 *
 * OGP-23997
 *
 * 需完善註冊
 * 需在2021-10-21 00:00:00後註冊的玩家
 *

condition:
{
    "class": "promo_rule_smash_registration_bonus",
    "completed_player_info": true, //(optional)
    "allow_after_datetime": "2021-10-21 00:00:00"
}

 *
 *
 */
class Promo_rule_smash_registration_bonus extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_smash_registration_bonus';
	}

    protected function completed_player_info($description){
        $conditionResult = $this->player_model->getPlayerAccountInfoStatus($this->playerId);
        $completed_player_info = $conditionResult['status'];

        $completed_player_info = isset($description['completed_player_info']) ? $description['completed_player_info'] : $completed_player_info;
        if(!$completed_player_info){
            $conditionResultMissingFields = !empty($conditionResult['missing_fields']) ? $conditionResult['missing_fields'] : NULL;
            $this->appendToDebugLog('not complete player info',['missing_fields'=>$conditionResultMissingFields]);
        }

        return $completed_player_info;
    }

	/**
	 * run bonus condition checker
	 * @param  array $description original description in rule
	 * @param  array $extra_info
	 * @param  boolean $dry_run
	 * @return  array ['success'=> success, 'message'=> errorMessageLang, 'continue_process_after_script' => FALSE]
	 */
	protected function runBonusConditionChecker($description, &$extra_info, $dry_run){
        $success = false;
        $errorMessageLang = null;

        $completed_player_info = $this->completed_player_info($description);

        $now = $this->utils->getNowForMysql();
        $player_create_at = $this->callHelper('get_date_type',[self::REGISTER_DATE]);
        $allow_after_datetime = !empty($description['allow_after_datetime']) ? $description['allow_after_datetime'] : $now;

        if(strtotime($player_create_at) >= strtotime($allow_after_datetime)){
            if($completed_player_info){
                $success = true;
            }else{
                $errorMessageLang = 'notify.93';
            }
        }else{
            $errorMessageLang = 'promo_rule.common.error';
        }

        $this->appendToDebugLog('completed_player_info ['.$completed_player_info.'], 
            original allow_after_datetime ['.$description['allow_after_datetime'].'], 
            timestamp of allow_after_datetime ['.strtotime($allow_after_datetime).'], 
            original player create on datetime ['.$player_create_at.'], 
            timestamp of player create on ['.strtotime($player_create_at).']');

        $result=['success'=>$success, 'message'=>$errorMessageLang, 'continue_process_after_script' => FALSE];
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
