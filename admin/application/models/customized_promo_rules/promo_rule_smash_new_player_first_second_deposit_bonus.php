<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 *
 * OGP-26693
 *
 * 此活动仅限活动开始日期后注册的新玩家。 在这个日期之前注册的玩家，都不能参与。
 * 新玩家的生涯第一次存款送10%，第二次存款超过150BRL 才送5%，忘記申請可回頭領。
 * 一日最多申请两次(第一次存款和第二次存款超过150BRL)
 *
 * 在活动开始日期之前的所有玩家。 不补偿任何奖金。 不要重复发放

condition:
{
    "class": "promo_rule_smash_new_player_first_second_deposit_bonus",
    "first_deposit_percentage": 10,
    "second_deposit_percentage": 5,
    "min_second_deposit": 150
}

 *
 *
 */
class Promo_rule_smash_new_player_first_second_deposit_bonus extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_smash_new_player_first_second_deposit_bonus';
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

        $promorule = $this->promorule;
        $applicationPeriodStart = $promorule['applicationPeriodStart'];

        // check player registered after promo start
        $appStart = new DateTime($applicationPeriodStart);
        $appStartDateTime = $appStart->format('Y-m-d H:i:s');

        $register_date = $this->callHelper('get_date_type', [self::REGISTER_DATE]);
        $playerRegister = new DateTime($register_date);
        $playerRegDateTime = $playerRegister->format('Y-m-d H:i:s');

        $this->appendToDebugLog('check player created and promo start datetime',
            ['promo start on' => $appStartDateTime, 'player registered on ' => $playerRegDateTime, 'is valid to apply' => ($playerRegDateTime < $appStartDateTime)]
        );

        if($playerRegDateTime < $appStartDateTime){
            return ['success' => false, 'message' => lang('promo.dont_allow_not_within_claim_time')];
        }

        $nowDateTime = $this->utils->getNowForMysql();
        $first_deposit_percentage = $description["first_deposit_percentage"];
        $second_deposit_percentage = $description["second_deposit_percentage"];
        $min_second_deposit = $description["min_second_deposit"];

        // check player approve promo cnt
        $release_date['start'] = $appStartDateTime;
        $release_date['end'] = $nowDateTime;
        $releasedBonusCnt = $this->callHelper('count_approved_promo', [$this->promorulesId,self::DATE_TYPE_CUSTOMIZE, $release_date]);
        $this->appendToDebugLog('check player approved promo count', ['count' => $releasedBonusCnt]);


        $getFirstDepositByDate = $this->callHelper('getAnyDepositByDate',[$appStartDateTime, $nowDateTime, 'first', null, null]);
        $firstDepositAmount = intval($getFirstDepositByDate['amount']);
        $this->appendToDebugLog('first deposit', ['amount' => $firstDepositAmount]);

        $getSecondDepositByDate = $this->callHelper('getAnyDepositByDate',[$appStartDateTime, $nowDateTime, 2, null, null]);
        $secondDepositAmount = intval($getSecondDepositByDate['amount']);
        $this->appendToDebugLog('second deposit', ['amount' => $secondDepositAmount]);

        // use first deposit apply promo
        if(empty($releasedBonusCnt)){
            if(!empty($firstDepositAmount)){
                $success = true;
                $bonus_amount = $firstDepositAmount * ($first_deposit_percentage / 100);
            }else{
                return ['success' => false, 'message' => lang('notify.79')];
            }
        }

        // use second deposit apply promo
        if($releasedBonusCnt == 1){
            if(!empty($secondDepositAmount) && ($secondDepositAmount >= $min_second_deposit)){
                $success = true;
                $bonus_amount = $secondDepositAmount * ($second_deposit_percentage / 100);
            }else{
                return ['success' => false, 'message' => lang('notify.79')];
            }
        }

        if($releasedBonusCnt > 1){
            return ['success' => false, 'message' => lang('notify.83')];
        }

        return ['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonus_amount];
    }
}
