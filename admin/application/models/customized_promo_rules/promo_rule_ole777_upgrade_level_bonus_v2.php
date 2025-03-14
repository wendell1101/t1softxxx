<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';
require_once dirname(__FILE__) .'/promo_rule_ole777_upgrade_level_bonus.php';
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
    "class": "promo_rule_ole777_upgrade_level_bonus_v2",
    "verify_mobile": false,
    "verify_email": false,
    "bonus_settings":{
        "227" : {"bonus_amount": 17},
        "235" : {"bonus_amount": 177},
        "239" : {"bonus_amount": 377},
        "240" : {"bonus_amount": 777}
    }
}

mock:
{
    "first_level_to": 3,
    "get_all_released_player_promo": [{
      "playerpromoId" : "96477",
      "playerId" : "229160",
      "promorulesId" : "17207",
      "promoCmsSettingId" : "17184",
      "dateApply" : "2020-09-28 17:27:49",
      "dateProcessed" : "2020-09-28 17:27:49",
      "cancelRequestDate" : null,
      "dateCancelled" : null,
      "dateCancelDeclined" : null,
      "dateApplicationDeclined" : null,
      "declinedCancelReason" : null,
      "declinedApplicationReason" : null,
      "processedBy" : "1",
      "depositAmount" : "0",
      "bonusAmount" : "17",
      "currency" : null,
      "verificationStatus" : "0",
      "promoStatus" : "0",
      "cancelRequestStatus" : "0",
      "withdrawalStatus" : "0",
      "transactionStatus" : "1",
      "level_id" : "242",
      "withdrawConditionAmount" : "17",
      "triggered_subwallet_id" : "0",
      "note" : null,
      "betTimes" : "1",
      "release_to_sub_wallet" : null,
      "login_ip" : "220.135.118.23",
      "finish_max_limit_withdrawal" : "0",
      "transferConditionAmount" : "0",
      "requestAdminId" : null,
      "requestPlayerId" : "229160",
      "order_generated_by" : "1",
      "player_request_ip" : "220.135.118.23",
      "vip_level_info" : null
    }]
}

 *
 *
 */
class Promo_rule_ole777_upgrade_level_bonus_v2 extends Promo_rule_ole777_upgrade_level_bonus {

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_ole777_upgrade_level_bonus_v2';
	}

	/**
	 * run bonus condition checker
	 * @param  array $description original description in rule
	 * @param  array $extra_info
	 * @param  boolean $dry_run
	 * @return  array ['success'=> success, 'message'=> errorMessageLang, 'continue_process_after_script' => FALSE]
	 */
	protected function runBonusConditionChecker($description, &$extra_info, $dry_run){

        $rlt = parent::runBonusConditionChecker($description, $extra_info, $dry_run);

        $success = $rlt['success'];
        $message = $rlt['message'];
        $continue_process_after_script = $rlt['continue_process_after_script'];

        if($success){ // pass from parent::runBonusConditionChecker()

            // Check upgrade record in period
            $from_date = null;
            $to_date = null;
            $changedGrade = null;
            $isCrossGroupFiltered = true;
			$upg_recs = $this->callHelper('searchAllGradeRecords', [$from_date, $to_date, $changedGrade, $isCrossGroupFiltered]); // [ $from_date, $to_date ]);

            $first_level_to = PHP_INT_MAX;
            if ( ! empty($upg_recs)) {
                $first_level_to = $upg_recs[count($upg_recs)-1]['level_to'];
            }

            if($this->process_mock('first_level_to', $first_level_to)){
                //use mock data
                $this->appendToDebugLog('use mock first_level_to', ['first_level_to'=>$first_level_to]);
            }

            $this->appendToDebugLog('Player vipLevel greater than the begin vip level.', ['player.vipLevel:', $this->vipLevel
                , 'first_level_to:', $first_level_to]);

            if( $this->vipLevel > $first_level_to){
                $success = true;
                $message = null;
                $this->appendToDebugLog('Player vipLevel greater than the begin vip level.', ['player.vipLevel:', $this->vipLevel, 'first_level_to:', $first_level_to ]);
            }else if($first_level_to == PHP_INT_MAX){
                $success = false;
                $message = 'promo_custom.level_upgrade_record_not_found';
                $this->appendToDebugLog('first_level_to Not Fount.', ['upg_recs.count:', count($upg_recs) ]);
            }else{
                $success = false;
                $message = 'promo_custom.not_greater_than_begin_vip_level';
                $this->appendToDebugLog('Player vipLevel Not greater than the begin vip level.', ['player.vipLevel:', $this->vipLevel, 'first_level_to:', $first_level_to ]);
            }

        }

        $this->appendToDebugLog('Promo_rule_ole777_upgrade_level_bonus_v2.runBonusConditionChecker:'
         , 'success:', $success
         , 'message:', $message
         , 'continue_process_after_script:', $continue_process_after_script
        );

        $result=['success'=>$success, 'message'=>$message, 'continue_process_after_script' => $continue_process_after_script];
        return $result;
	} // EOF runBonusConditionChecker

    /// Disable, directly calling.
    // protected function releaseBonus($description, &$extra_info, $dry_run){
    //
    //     $rlt = parent::releaseBonus($description, $extra_info, $dry_run);
    //
    //     $success = $rlt['success'];
    //     $message = $rlt['message'];
    //     $bonus_amount = $rlt['bonus_amount'];
    //
    //     $result=['success'=>$success, 'message'=>$message, 'bonus_amount'=>$bonus_amount];
    //     return $result;
    // } // EOF releaseBonus

} // EOF Promo_rule_ole777_upgrade_level_bonus_v2
