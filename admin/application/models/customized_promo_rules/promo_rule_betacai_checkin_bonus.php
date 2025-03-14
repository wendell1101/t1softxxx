<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 *
 * OGP-31873
 * 跨月續簽
 * 每日簽到優惠
 * 每日存款>=20BRL
 * 一天只能申請一次(不保留沒申請的,無法一次領)
 * 需連續簽到,斷簽需重新累積簽到次數歸零至第一日,七天為一週期
 * 
condition:
{
	"class": "promo_rule_betacai_checkin_bonus",
	"allowed_date": {
		"start": "",
		"end": ""
	},
	"release_date": {
		"start": "",
		"end": ""
	},
	"bonus_settings": {
		"1" : { "bonus": 0.3},
		"2" : { "bonus": 0.5},
		"3" : { "bonus": 0.3},
		"4" : { "bonus": 0.4},
		"5" : { "bonus": 0.2},
		"6" : { "bonus": 0.2},
		"7" : { "bonus": 0.6}
	},
	"released_yesterday" : 0.3,
	"released_before_yesterday" : 0.5
}
*
*
*/
class Promo_rule_betacai_checkin_bonus extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_betacai_checkin_bonus';
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
		$allowed_date = $description['allowed_date'];
		$bonus_settings = $description['bonus_settings'];
		$fromDate = !empty($allowed_date['start']) ? $allowed_date['start'] : $this->get_date_type(self::DATE_TODAY_START);
		$toDate = !empty($allowed_date['end']) ? $allowed_date['end'] : $this->get_date_type(self::DATE_TODAY_END);

		$result = $this->checkCustomizeBonusCondition($bonus_settings, $fromDate, $toDate, $extra_info, $description, $errorMessageLang);

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
		$result = [];
		$allowed_date = $description['allowed_date'];
		$bonus_settings = $description['bonus_settings'];
		$fromDate = !empty($allowed_date['start']) ? $allowed_date['start'] : $this->get_date_type(self::DATE_TODAY_START);
		$toDate = !empty($allowed_date['end']) ? $allowed_date['end'] : $this->get_date_type(self::DATE_TODAY_END);

		$request = $this->checkCustomizeBonusCondition($bonus_settings, $fromDate, $toDate, $extra_info, $description, $errorMessageLang);

		if($request['success']){
			return $request;
		}
		return $result;
	}

	private function checkCustomizeBonusCondition($bonus_settings, $fromDate, $toDate, &$extra_info, $description, &$errorMessageLang){
		$success = false;
		$errorMessageLang = lang('notify.78');
		$bonus_amount = 0;
		$player_id = $this->playerId;		
		$promorule = $this->promorule;
		$promoRuleId = $promorule['promorulesId'];
		$release_date = $description['release_date'];
		$amountLimit = isset($description['amountLimit']) ? $description['amountLimit'] : 20;
		$application_day = isset($description['application_day']) ? $description['application_day'] : false;
		$nowDate = $this->get_date_type(self::TO_TYPE_NOW);
		$today = $this->utils->getTodayForMysql();

		$this->appendToDebugLog(__METHOD__ . " params check start [$player_id]", [
			'promoRuleId' => $promoRuleId,
			'fromDate' => $fromDate,
			'toDate' => $toDate,
			'nowDate' => $nowDate,
			'today' => $today,
			'description' => $description,
		]);

		// check is release today
		$is_released = $this->get_last_released_player_promo($promoRuleId, self::DATE_TYPE_TODAY);

		$this->appendToDebugLog(__METHOD__ . " params check release [$player_id]", [
			'is_released' => $is_released,
		]);

		if($is_released){
			$success=false;
			$errorMessageLang =  lang('notify.77');
			return ['success' => $success, 'message' => $errorMessageLang, 'continue_process_after_script' => FALSE];
		}

		// check is allow day
		$this->appendToDebugLog(__METHOD__ . " params check allow day [$player_id]", [
			'application_day' => $application_day,
			'today' => $today
		]);

		if (!empty($application_day)) {
			if (strtotime($today) < strtotime($application_day)) {
				$success = false;
				$errorMessageLang =  lang('notify.78');
				return ['success' => $success, 'message' => $errorMessageLang, 'continue_process_after_script' => FALSE];
			}
		}

		// check is deposit < 20
		$total_deposit = $this->callHelper('totalDepositByPlayerAndDateTime', [$player_id, $fromDate, $toDate]);

		$this->appendToDebugLog(__METHOD__ . " params check deposit [$player_id]", [
			'total_deposit' => $total_deposit,
		]);

		if($total_deposit < $amountLimit){
			$success=false;
			$errorMessageLang =  lang('notify.79');
			return ['success' => $success, 'message' => $errorMessageLang, 'continue_process_after_script' => FALSE];
		}

        $extra_info['returnOneRow'] = isset($description['returnOneRow']) ? $description['returnOneRow'] : true;
        $released_yesterday = $this->get_last_released_player_promo($promoRuleId, self::DATE_TYPE_YESTERDAY, $extra_info);
        $released_before_yesterday = $this->get_last_released_player_promo($promoRuleId, self::DATE_TYPE_BEFORE_YESTERDAY, $extra_info);

        $this->appendToDebugLog(__METHOD__ . " params listReleasedDates [$player_id]", [
			'released_yesterday' => $released_yesterday,
			'released_before_yesterday' => $released_before_yesterday,
			'extra_info' => $extra_info
		]);

        $released_yesterday_description = isset($description['released_yesterday']) ? $description['released_yesterday'] : 0;
        $released_before_yesterday_description = isset($description['released_before_yesterday']) ? $description['released_before_yesterday'] : 0;

        $this->appendToDebugLog(__METHOD__ . " params listReleasedDates by description [$player_id]", [
            'released_yesterday_description' => $released_yesterday_description,
            'released_before_yesterday_description' => $released_before_yesterday_description,
        ]);

		if (!empty($bonus_settings)) {
			$bonus_yesterday = isset($released_yesterday['bonusAmount']) ? $released_yesterday['bonusAmount'] : $released_yesterday_description;
			$bonus_day_before_yesterday = isset($released_before_yesterday['bonusAmount']) ? $released_before_yesterday['bonusAmount'] : $released_before_yesterday_description;
			$bonus_amount = isset($bonus_settings['1']['bonus']) ? $bonus_settings['1']['bonus'] : 0.3;
            $bonus_data = array_column($description['bonus_settings'], 'bonus');

            $this->appendToDebugLog(__METHOD__ . "params get bonus amount [$player_id]", [
                'bonus_yesterday' => $bonus_yesterday,
                'bonus_day_before_yesterday' => $bonus_day_before_yesterday,
                'bonus_amount' => $bonus_amount,
                'bonus_data' => $bonus_data
            ]);

			if ($bonus_yesterday > 0 && in_array($bonus_yesterday, $bonus_data, true)) {
				switch ($bonus_yesterday) {
					case 0.2:
						$bonus_amount = ($bonus_day_before_yesterday == 0.2) ? 0.6 : 0.2;
						break;
					case 0.4:
						$bonus_amount = 0.2;
						break;
					case 0.3:
						$bonus_amount = ($bonus_day_before_yesterday == 0.5) ? 0.4 : 0.5;
						break;
					case 0.5:
						$bonus_amount = 0.3;
						break;
					case 0.6:
						$bonus_amount = 0.3; // 重置
						break;
				}
                $success = true;
			}elseif ($bonus_yesterday == 0){
                $success = true;
                $bonus_amount = 0.3;
            }
            $this->appendToDebugLog("check release bonus success [$player_id]", [
                'success' => $success,
                'bonus_amount' => $bonus_amount
            ]);
		}else{
			$errorMessageLang = 'Not exist Setting';
			$this->appendToDebugLog('Not exist Setting',['bonus_settings' => $bonus_settings]);
		}

		return ['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonus_amount];
	}
}
