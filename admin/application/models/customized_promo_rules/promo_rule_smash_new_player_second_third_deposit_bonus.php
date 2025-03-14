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
 * 在首存後的第2天, 存款 >= 50, 可申請 B.(訂製優惠), 獎金 10%, 獎金最高2000
 * 在首存後的第3天, 存款 >= 50, 可申請 B.(訂製優惠), 獎金 10%, 獎金最高2000, 如果首存後第2天沒申請, 第3天還是可以申請
 * 取款條件38倍
 *
 * 沒申請過A, 不可申請
 * 申請過A, 相差一日, 當日無申請B
 * 申請過A, 相差二日, 不管有無申請過B
 * 申請過A, 相差大於二日, 不可申請
 *

condition:
{
    "class": "promo_rule_smash_new_player_second_third_deposit_bonus",
    "promo_cms_id": ""
}

 *
 *
 */
class Promo_rule_smash_new_player_second_third_deposit_bonus extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_smash_new_player_second_third_deposit_bonus';
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
        $extraInfo_ = ['returnOneRow' => true];
        $firstPromoRuleId = $this->promorules->getPromorulesIdByPromoCmsId($promo_cms_id);
        $firstPlayerPromo = $this->callHelper('get_last_released_player_promo', [$firstPromoRuleId, null, $extraInfo_]);
        $this->appendToDebugLog('check player first approved promo', ['row' => $firstPlayerPromo]);
        if(empty($firstPlayerPromo)){
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

        // check today release
        $todayReleasedBonus = $this->callHelper('count_approved_promo', [$this->promorulesId, self::DATE_TYPE_TODAY]);
        $this->appendToDebugLog('check second third promo today release', ['result' => $todayReleasedBonus]);
        if(!empty($todayReleasedBonus)){
            $errorMessageLang = 'promo_rule.common.error'; //You are not suited for this promo yet
            return ['success' => $success, 'message' => $errorMessageLang];
        }

        // count date diff with promo A
        $firstPromo = new DateTime($firstPlayerPromo['dateProcessed']);
        $firstPromoDate = $firstPromo->format('Y-m-d');

        $startDate = new DateTime($firstPromoDate);
        $endDate =  new DateTime($today);
        $day_diff = $endDate->diff($startDate)->format("%a");
        $day_diff = $day_diff > 0 ? $day_diff : 0;
        $this->appendToDebugLog('compare days diff', ['start date' => $firstPromoDate, 'end date' => $today, 'date diff' => $day_diff]);

        // promo A and promo B apply on the same day or promo B apply over 2 days
        if(empty($day_diff) || $day_diff > 2){
            $errorMessageLang = 'promo_rule.common.error'; //You are not suited for this promo yet
            return ['success' => $success, 'message' => $errorMessageLang];
        }

        $success = true;

        return ['success' => $success, 'message' => $errorMessageLang, 'continue_process_after_script' => TRUE];
    }
}
