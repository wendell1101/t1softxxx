<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 *
 *
 * OGP-19401
 * 升级奖金，一个级别只能申请一次，降级不可申请
 *
 * OGP-21908
 * 需完成手机验证和Email验证

condition:
{
    "class": "promo_rule_ole777_upgrade_level_bonus",
    "verify_mobile": true, //(optional)
    "verify_email": true,  //(optional)
    "bonus_settings":{
        "242" : {"bonus_amount": 17},
        "243" : {"bonus_amount": 177},
        "244" : {"bonus_amount": 377},
        "245" : {"bonus_amount": 777},
        "246" : {"bonus_amount": 1777},
        "247" : {"bonus_amount": 2777},
        "248" : {"bonus_amount": 4777},
        "249" : {"bonus_amount": 7777},
        "250" : {"bonus_amount": 12777},
        "251" : {"bonus_amount": 17777},
        "252" : {"bonus_amount": 77777}
    }
}

mock:
{
    "now": "2018-09-10 12:00:00",
    "searchFirstGradeRecord": 1
}

 *
 *
 */
class Promo_rule_ole777_upgrade_level_bonus extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_ole777_upgrade_level_bonus';
	}

    protected function isVerifiedPhone($description){
        $verified_phone = true;

        if(!empty($description['verified_phone']) && $description['verified_phone']){
            $verified_phone = $this->player_model->isVerifiedPhone($this->playerId);
        }

        if(!$verified_phone){
            $this->appendToDebugLog('not verified phone',['verified_phone'=>$verified_phone]);
        }

        return $verified_phone;
    }

    protected function isVerifiedEmail($description){
        $verified_email = true;

        if(!empty($description['verified_email']) && $description['verified_email']){
            $verified_email = $this->player_model->isVerifiedEmail($this->playerId);
        }

        if(!$verified_email){
            $this->appendToDebugLog('not verified email',['verified_email'=>$verified_email]);
        }

        return $verified_email;
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
        $isUpgrade = false;
        $returnOneRow = true;
        $applyRecordWithTheSameLevel = false;
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
                return ['success' => $success, 'message' => $extra_info['error_message']];
            }
        }

        $endAt = $this->utils->getNowForMysql();
        $gradeRecord = $this->callHelper('getLastUpgradeLevelOrCurrentLevel',[$endAt, 'upgrade_or_downgrade', $returnOneRow]);
        if(!empty($gradeRecord) && !empty($gradeRecord['level_from']) && !empty($gradeRecord['level_to'])){
            if($gradeRecord['level_from'] < $gradeRecord['level_to']){
                $isUpgrade = true; // @todo $gradeRecord['request_grade'] == Group_level::RECORD_UPGRADE
            }
        }
        $this->appendToDebugLog('search grade record getLastUpgradeLevelOrCurrentLevel', ['endAt'=>$endAt, 'gradeRecord'=>$gradeRecord, 'isUpgrade'=>$isUpgrade]);

        $verified_phone = $this->isVerifiedPhone($description);
        $verified_email = $this->isVerifiedEmail($description);

        if($isUpgrade){
            $applyLevel = [];
            $promorule = $this->promorule;
            $promoRuleId = $promorule['promorulesId'];
            $applyRecord = $this->callHelper('get_all_released_player_promo',[$promoRuleId, null]);
            $this->appendToDebugLog('get all released player_promo', ['applyRecord'=>$applyRecord]);

            if(empty($applyRecord)){
                if(!$verified_phone){
                    $errorMessageLang = 'promo.rule_is_player_verified_mobile';
                }elseif (!$verified_email){
                    $errorMessageLang = 'promo.rule_is_player_verified_email';
                }elseif($verified_phone && $verified_email){
                    $success = true;
                }
            }else{
                // apply in other time
                $currentLevelId = $this->levelId;

                foreach($applyRecord as $k => $v){
                    $applyLevelId = $v['level_id'];
                    if($applyLevelId == $currentLevelId){
                        $applyRecordWithTheSameLevel = true;
                        $this->appendToDebugLog('find out apply reocord with the same level', ['current level id' => $currentLevelId, 'record level_id' => $v]);
                        break;
                    }

                    if(!empty($v['dateApply'])){
                        $dateApply = $v['dateApply'];
                        $applyLevel[$applyLevelId] = $dateApply;
                        $this->appendToDebugLog('get last upgarde level when apply promo', ['dateApply' => $dateApply, 'applyLevelId' => $applyLevelId]);
                    }
                }

                $this->appendToDebugLog('applied level', ['applyLevel' => $applyLevel]);

                if(array_key_exists($currentLevelId, $applyLevel)){
                    //this level had already applied
                    $errorMessageLang = 'promo_custom.level_already_apply';
                }else{
                    if($applyRecordWithTheSameLevel){
                        // for checking client which had been moved vip level but no upgrade record
                        $errorMessageLang = 'promo_custom.level_already_apply';
                    }elseif(!$verified_phone){
                        $errorMessageLang = 'promo.rule_is_player_verified_mobile';
                    }elseif (!$verified_email){
                        $errorMessageLang = 'promo.rule_is_player_verified_email';
                    }elseif($verified_phone && $verified_email){
                        $success = true;
                    }
                }
            }
        }else{
            $errorMessageLang = 'promo_custom.level_upgrade_record_not_found';
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
        $success = false;
        $errorMessageLang = null;
        $bonus_amount = 0;

        $bonus_setting = $description['bonus_settings'];
        $levelId = $this->levelId;
        $this->appendToDebugLog('get bonus setting', ['bonus_settings'=>$bonus_setting, 'levelId'=>$levelId]);

        if(array_key_exists($levelId, $bonus_setting)){
            $setting = $bonus_setting[$levelId];
            $success = true;
            $bonus_amount = $setting['bonus_amount'];
            if (!empty($description['amount_convert_rate'])) {
                $bonus_amount = $bonus_amount/1000;
            }
        }else{
            $errorMessageLang = 'promo_custom.not_in_allowed_vip_level';
        }

        $result=['success'=>$success, 'message'=>$errorMessageLang, 'bonus_amount'=>$bonus_amount];
        return $result;
	}
}
