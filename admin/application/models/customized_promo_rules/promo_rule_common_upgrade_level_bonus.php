<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 * OGP-28667 json-editor
 * 公共優惠 vip upgrade level, 參考OGP-24340
 *
 * 升级奖金，一个级别只能申请一次，降级不可申请
 * 使用json-editor 產生下列cusotm promo json
 *

condition:
{
    "class": "promo_rule_common_upgrade_level_bonus",
    "bonus_settings":[
        {"level_id": "10", "bonus_amount":    5},
        {"level_id": "11", "bonus_amount":   10},
        {"level_id": "12", "bonus_amount":   20},
        {"level_id": "13", "bonus_amount":   50},
        {"level_id": "14", "bonus_amount":  100},
        {"level_id": "15", "bonus_amount":  200},
        {"level_id": "16", "bonus_amount":  500},
        {"level_id": "17", "bonus_amount": 1000},
        {"level_id": "18", "bonus_amount": 1000},
        {"level_id": "19", "bonus_amount": 5000}
    ]
}

 *
 *
 */
class Promo_rule_common_upgrade_level_bonus extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_common_upgrade_level_bonus';
	}

    public function getConditionSchema() {
        return [];
    }

    public function getReleaseSchema() {
        $this->load->model(['player_model']);
        $all_vip_levels = $this->player_model->getAllPlayerLevels();
        $vip_list = [];
        foreach ($all_vip_levels as $vip_level){
            $vip_list[] = [
                'title' => lang($vip_level['groupName']) . ' - ' . lang($vip_level['vipLevelName']),
                'value' => $vip_level['vipsettingcashbackruleId']
            ];

        }

        $releaseSchema['type'] = 'array';
        $releaseSchema['format'] = 'table';
        $releaseSchema['uniqueItems'] = true;
        $releaseSchema['items'] = [
            'type' => 'object',
            'title' => 'settings',
            'properties' => [
                'level_id' => [
                    'title' => lang('promo.customized_promo_rules.' . __CLASS__ . '.level_id'),
                    'type' => 'string',
                    'enumSource' => [
                        [
                            'source' => $vip_list,
                            'title' => '{{item.title}}',
                            'value' => '{{item.value}}'
                        ],
                    ]
                ],
                'bonus_amount' => [
                    'title' => lang('promo.customized_promo_rules.' . __CLASS__ . '.bonusAmount'),
                    'type' => 'integer'
                ],
            ]
        ];

        return $releaseSchema;
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

        $endAt = $this->utils->getNowForMysql();
        $gradeRecord = $this->callHelper('getLastUpgradeLevelOrCurrentLevel',[$endAt, 'upgrade_or_downgrade', $returnOneRow]);
        if(!empty($gradeRecord) && !empty($gradeRecord['level_from']) && !empty($gradeRecord['level_to'])){
            if($gradeRecord['level_from'] < $gradeRecord['level_to']){
                $isUpgrade = true; // @todo $gradeRecord['request_grade'] == Group_level::RECORD_UPGRADE
            }
        }
        $this->appendToDebugLog('search grade record getLastUpgradeLevelOrCurrentLevel', ['endAt'=>$endAt, 'gradeRecord'=>$gradeRecord, 'isUpgrade'=>$isUpgrade]);

        if($isUpgrade){
            $applyLevel = [];
            $promorule = $this->promorule;
            $promoRuleId = $promorule['promorulesId'];
            $applyRecord = $this->callHelper('get_all_released_player_promo',[$promoRuleId, null]);
            $this->appendToDebugLog('get all released player_promo', ['applyRecord'=>$applyRecord]);

            if(empty($applyRecord)){
                $success = true;
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
                    }else{
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

        // public promo rule => use json-editor to generate custom json
        foreach ($bonus_setting as $setting){
            if(!empty($setting['level_id'])){
                if((int)$setting['level_id'] == $levelId){
                    $bonus_amount = $setting['bonus_amount'];
                    $success = true;
                    break;
                }
            }
        }

        if(!$success){
            $errorMessageLang = 'promo_custom.not_in_allowed_vip_level';
        }

        $result=['success'=>$success, 'message'=>$errorMessageLang, 'bonus_amount'=>$bonus_amount];
        return $result;
	}
}
