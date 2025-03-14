<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 *
 * OGP-32268
 * BONUS DOWNLOAD APP
 * Starting from 2023/4/14, career accumulated deposits >=77
 * Need to downloaded oleID APP
 * Bonus fixed 20
 * Withdrawal Condition: bonus * 5
condition:
{
    "class": "promo_rule_ole777id_app_download_bonus",
    "start_date": "2023-04-14",
    "deposit_limit": 77,
    "fixed_bonus": 20
}
 *
 *
 */
class Promo_rule_ole777id_app_download_bonus extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_ole777id_app_download_bonus';
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
        $result = $this->checkCustomizeBonusCondition($description, $errorMessageLang, $extra_info);

        // if(array_key_exists('bonus_amount',$result)){
		// 	unset($result['bonus_amount']);
		// }

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
        $result = $this->checkCustomizeBonusCondition($description, $errorMessageLang, $extra_info);

        if(array_key_exists('bonus_amount',$result)){
			unset($result['bonus_amount']);
		}

        return $result;
	}

    private function checkCustomizeBonusCondition($description, &$errorMessageLang, &$extra_info){
        $success = false;
        $errorMessageLang = null;
        $allowed_date = isset($description['allowed_date']) ? $description['allowed_date'] : [];
        $fixedBonus = isset($description['fixed_bonus']) ? $description['fixed_bonus'] : 0;

        $promorule = $this->promorule;
	    $promoRuleId = $promorule['promorulesId'];

        $startDate = isset($description['start_date']) ? $description['start_date'] . ' 00:00:00' : null;
        $registerDate = $this->get_date_type(self::REGISTER_DATE);

        $fromDate = !empty($allowed_date['start']) ? $allowed_date['start'] : $registerDate;
		$toDate = !empty($allowed_date['end']) ? $allowed_date['end'] : $this->get_date_type(self::TO_TYPE_NOW);

        $this->appendToDebugLog(__METHOD__ . ' date info', ['startDate', $startDate, 'registerDate', $registerDate, 'fromDate', $fromDate, 'toDate', $toDate]);

        if(!is_null($startDate)){
            if($registerDate < $startDate){
                $errorMessageLang = 'notify.78';
                return ['success' => $success, 'message' => $errorMessageLang];
            }
        }

        //check if player apply the same series of promo on other days
        $existPromoRecord = false;
        if(!empty($description['not_allow_promo_on_other_days']['promorule_ids'])){
            $promorule_ids = $description['not_allow_promo_on_other_days']['promorule_ids'];
            $this->_checkNotAllowOtherPromoRecords($promorule_ids, $existPromoRecord);
        }

        $this->appendToDebugLog(__METHOD__ . ' existPromoRecord', ['existPromoRecord', $existPromoRecord]);

        if($existPromoRecord){
            $errorMessageLang = 'notify.134';
            return ['success' => $success, 'message' => $errorMessageLang];
        }

        $checkReleasedBonus = $this->callHelper('get_last_released_player_promo',[$promoRuleId, self::DATE_TYPE_CUSTOMIZE, ['start' => $fromDate, 'end' => $toDate]]);

        $this->appendToDebugLog(__METHOD__ . ' checkReleasedBonus', ['checkReleasedBonus', $checkReleasedBonus]);

        if($checkReleasedBonus){
			$errorMessageLang =  lang('notify.83');
	    	return ['success' => $success, 'message' => $errorMessageLang];
	    }

        $isDownloadApp = $this->isDownloadApp($description, $extra_info);

        $this->appendToDebugLog(__METHOD__ . ' isDownloadApp', ['isDownloadApp', $isDownloadApp]);
        if(!$isDownloadApp) {
            $errorMessageLang = 'promo_rule.common.error';
            return ['success' => $success, 'message' => $errorMessageLang];
        }

        $depositLimit = isset($description['deposit_limit']) ? $description['deposit_limit'] : 0;
        $depositAmount =  $this->callHelper('sum_deposit_amount', [$fromDate, $toDate, 0]);

        $this->appendToDebugLog(__METHOD__ . 'deposit info', ['depositAmount', $depositAmount, 'deposit_limit', $depositLimit, 'fromDate', $fromDate, 'toDate', $toDate]);

        if($depositAmount < $depositLimit){
            $errorMessageLang = 'notify.79';
            return ['success' => $success, 'message' => $errorMessageLang];
        }

        $success = true;

        $this->appendToDebugLog(__METHOD__ . ' result', ['success', $success, 'errorMessageLang', $errorMessageLang]);

        $result=['success'=>$success, 'message'=>$errorMessageLang, 'continue_process_after_script' => TRUE];
        return $result;
    }

    protected function isDownloadApp($description, &$extra_info) {

        $isDownloadApp =  false;
        $byLoginReport = isset($description['byLoginReport']) ? $description['byLoginReport'] : true;
        if($byLoginReport){
            $this->load->model('player_login_report');
            $existLoginByAppRecord = $this->player_login_report->existsPlayerLoginByApp($this->playerId);
            $isDownloadApp = !!$existLoginByAppRecord;
        } 
        if(!$isDownloadApp) {
            $this->load->model('http_request');
            $by_domains = isset($description['by_domains']) ? $description['by_domains'] : [];
            $historyFrom=new DateTime($this->get_date_type(self::REGISTER_DATE));
            $historyTo=new DateTime($this->get_date_type(self::TO_TYPE_NOW));
            $login_list = $this->http_request->getPlayerLoginList($historyFrom, $historyTo, $this->playerId);
            $this->appendToDebugLog('login_list_'.$this->playerId, $login_list);
            foreach ($login_list as $login) {
                $filter_referrer = array_filter($by_domains, function($domain) use ($login) {
                    return strpos($login['referrer'], $domain) !== false;
                });
                $this->appendToDebugLog('filter_referrer_'.$this->playerId, $filter_referrer);
                $isDownloadApp = !empty($filter_referrer);
                if($isDownloadApp) {
                    break;
                }
            }
        }
        $this->appendToDebugLog('download app',['result' => $isDownloadApp]);
        return $isDownloadApp;
    }
}