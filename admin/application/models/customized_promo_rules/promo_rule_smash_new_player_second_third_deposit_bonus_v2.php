<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 *
 * OGP-29728
 *
 * A. 首存優惠
 * 存款 >= 20, 獎金80%, 獎金最高2888
 * 取款條件38倍
 *
 * B. 訂製優惠
 * 申請過 A. 才允許申請B
 * 
 * [original] promo_rule_smash_new_player_second_third_deposit_bonus
 * 在首存後的第2天, 存款 >= 50, 可申請 B.(訂製優惠), 獎金 10%, 獎金最高2000
 * 在首存後的第3天, 存款 >= 50, 可申請 B.(訂製優惠), 獎金 10%, 獎金最高2000, 如果首存後第2天沒申請, 第3天還是可以申請
 * 取款條件38倍
 *
 * 沒申請過A, 不可申請
 * 申請過A, 相差一日, 當日可申請B
 * 申請過A, 相差二日, 不管有可申請過B
 * 申請過A, 相差大於二日, 不可申請
 *
 * --------------------------------------------------------------------------------------------------------
 * 
 * OGP-31331
 * [v2] promo_rule_smash_new_player_second_third_deposit_bonus_v2
 * 这个部分 “当你的职业生涯中进行首次存款后的第二天和第三天。”
 * 修改成：“ 生涯第二次存款，生涯第三次存款。”
 * 取消天数的限制。
 * 
 * 

condition:
{
    "class": "promo_rule_smash_new_player_second_third_deposit_bonus_v2",
    "promo_cms_id": "",
    "allowed_date": {
        "start": "",
        "end": ""
    }
}

 *
 *
 */
class Promo_rule_smash_new_player_second_third_deposit_bonus_v2 extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_smash_new_player_second_third_deposit_bonus_v2';
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
        return $this->returnUnimplemented();
	}

    private function checkCustomizeBounsCondition($description, &$extra_info, &$errorMessageLang){
        $this->load->model(['promorules']);
        $success = false;

        $promo_cms_id = $description['promo_cms_id'];
        $this->appendToDebugLog('promo cms id', ['result' => $promo_cms_id]);
        if(empty($promo_cms_id)){
            $errorMessageLang = 'Not setting valid promo'; //不允許申請
            return ['success' => $success, 'message' => $errorMessageLang];
        }

        $today = $this->utils->getTodayForMysql();
        if($this->process_mock('today', $today)){
            //use mock data
            $this->appendToDebugLog('use mock today', ['today'=>$today]);
        }

        // promo A
        $firstPromoRuleId = $this->promorules->getPromorulesIdByPromoCmsId($promo_cms_id);
        $firstPlayerPromo = $this->callHelper('count_approved_promo', [$firstPromoRuleId, null]);
        $this->appendToDebugLog('check player first approved promo', ['total cnt' => $firstPlayerPromo]);
        if(empty($firstPlayerPromo)){
            $errorMessageLang = 'promo_rule.common.error'; // You are not suited for this promo yet
            return ['success' => $success, 'message' => $errorMessageLang];
        }

        $fromDate = !empty($allowed_date['allowed_date']['start']) ? $allowed_date['allowed_date']['start'] : $this->get_date_type(self::REGISTER_DATE);
        $toDate = !empty($allowed_date['allowed_date']['end']) ? $allowed_date['allowed_date']['end'] : $this->get_date_type(self::TO_TYPE_NOW);

        #At least one deposit is required from the registration date to the last login
        $countDepositByPlayerId = $this->callHelper('countDepositByPlayerId',[$fromDate, $toDate]);
        $countDepositByPlayerId = intval($countDepositByPlayerId);
        $this->appendToDebugLog('check player deposit cnt', ['cnt' => $countDepositByPlayerId, 'fromDate' => $fromDate, 'toDate' => $toDate]);
        if(empty($countDepositByPlayerId) || ($countDepositByPlayerId > 3)){
            $errorMessageLang = 'promo_rule.common.error'; // You are not suited for this promo yet
            return ['success' => $success, 'message' => $errorMessageLang];
        }

        // check total release
        $totalReleasedBonus = $this->callHelper('count_approved_promo', [$this->promorulesId, null]);
        $this->appendToDebugLog('check second third promo total release', ['result' => $totalReleasedBonus]);
        if($totalReleasedBonus >= 2){
            $errorMessageLang = 'notify.83'; //You already applied for the promotion and cannot apply again.
            return ['success' => $success, 'message' => $errorMessageLang];
        }

        $success = true;

        return ['success' => $success, 'message' => $errorMessageLang, 'continue_process_after_script' => TRUE];
    }
}
