<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 *
 * OGP-29758 [Customized Promo] - Daily Deposit Spin Wheel
 * 每日可申請2次
 * 取款條件：3倍
 * 
 * 
condition:
{
    "class": "promo_rule_roulette_r29758",
    "allowed_date": {
        "start": "",
        "end": ""
    },
    "release_date": {
        "start": "",
        "end": ""
    },
    "daily_spin_limits": 2,
    "spin_conditions":{
        "type"  : "deposit",
        "per_amount": 500,
        "earn"  : 1,
        "login" : 0,
        "any_deposit": 1,
        "threshold": 50
    },
    "rouletteName": "r29758",
    "cmsId":6
}
*/
class Promo_rule_roulette_r29758 extends Abstract_promo_rule
{
    public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null)
    {
        parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
    }

    public function getClassName()
    {
        return 'promo_rule_roulette_r29758';
    }

    public function getRetentionTime($description)
	{
        $found_promo_setting = false;
        $retention_time = 0;
        if(isset($description['retention_time'])) {
            $retention_time = $description['retention_time'];
            $found_promo_setting = true;
			$this->utils->debug_log(__METHOD__." Found in description",['retention_time'=>$retention_time]);
        }

		if(!$found_promo_setting) {

            $config_key = $description['rouletteName'];
            $retention_time_config = $this->utils->getConfig('roulette_retention_time');
            $retention_time = 0;
            if($retention_time_config) {

                $retention_time = $this->utils->safeGetArray($retention_time_config, $config_key, $this->utils->safeGetArray($retention_time_config, 'default', 0));

			    $this->utils->debug_log(__METHOD__." Use Config",['config' => $retention_time_config,'retention_time'=>$retention_time]);
            }
		}
		return $retention_time;
	}

    /**
     * run bonus condition checker
     * @param  array $description original description in rule
     * @param  array $extra_info
     * @param  boolean $dry_run
     * @return  array ['success'=> success, 'message'=> errorMessageLang]
     */
    protected function runBonusConditionChecker($description, &$extra_info, $dry_run)
    {
        $success = false;
        $errorMessageLang = null;
        $allowed_date = $description['allowed_date'];
        $today_start = $this->get_date_type(self::DATE_TODAY_START);
        $now_date = $this->get_date_type(self::TO_TYPE_NOW);
        if(!$this->utils->isTimeoutNow($today_start, $this->getRetentionTime($description))){
            $today_start = $this->get_date_type(self::DATE_YESTERDAY_START);
        }

        $this->appendToDebugLog('runBonusConditionChecker date params start', ['today_start'=>$today_start, 'now_date'=>$now_date, 'description'=>$description]);

        $fromDate = !empty($allowed_date['start']) ? $allowed_date['start'] : $today_start;
        $toDate = !empty($allowed_date['end']) ? $allowed_date['end'] : $now_date;
        $today = $this->utils->getTodayForMysql();

        $this->appendToDebugLog('runBonusConditionChecker date params end', ['description' => $description, 'fromDate' => $fromDate, 'toDate' => $toDate]);

        if ($this->process_mock('today', $today)) {
            //use mock data
            $this->appendToDebugLog('use mock today', ['today'=>$today]);
        }

        $result = $this->checkCustomizeBonusCondition($fromDate, $toDate, $extra_info, $description, $errorMessageLang, $dry_run);

        if (array_key_exists('bonus_amount', $result)) {
            unset($result['bonus_amount']);
        }

        if (array_key_exists('deposit_amount', $result)) {
            unset($result['deposit_amount']);
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
    protected function generateWithdrawalCondition($description, &$extra_info, $dry_run)
    {
        return $this->returnUnimplemented();
    }

    /**
     * generate transfer condition
     * @param  array $description original description in rule
     * @param  array $extra_info exchange data
     * @param  boolean $dry_run
     * @return  array ['success'=> success, 'message_lang'=> errorMessageLang, 'withdrawal_condition_amount'=> withdrawal condition amount]
     */
    protected function generateTransferCondition($description, &$extra_info, $dry_run)
    {
        return $this->returnUnimplemented();
    }

    /**
     * release bonus
     * @param  array $description original description in rule
     * @param  array $extra_info
     * @param  boolean $dry_run
     * @return  array ['success'=> success, 'message'=> errorMessageLang, 'bonus_amount'=> bonus amount]
     */
    protected function releaseBonus($description, &$extra_info, $dry_run)
    {
        $success = false;
        $errorMessageLang = null;
        $bonus_amount = 0;
        $allowed_date = $description['allowed_date'];
        $today_start = $this->get_date_type(self::DATE_TODAY_START);
        $now_date = $this->get_date_type(self::TO_TYPE_NOW);
        if (!$this->utils->isTimeoutNow($today_start, $this->getRetentionTime($description))) {
            $today_start = $this->get_date_type(self::DATE_YESTERDAY_START);
        }

        $fromDate = !empty($allowed_date['start']) ? $allowed_date['start'] : $today_start;
        $toDate = !empty($allowed_date['end']) ? $allowed_date['end'] : $now_date;
        $today = $this->utils->getTodayForMysql();

        if ($this->process_mock('today', $today)) {
            //use mock data
            $this->appendToDebugLog('use mock today', ['today'=>$today]);
        }

        $request = $this->checkCustomizeBonusCondition($fromDate, $toDate, $extra_info, $description, $errorMessageLang, $dry_run);

        if ($request['success']) {
            return $request;
        }
        $result =['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonus_amount];
        return $result;
    }

    private function checkCustomizeBonusCondition($fromDate, $toDate, &$extra_info, $description, &$errorMessageLang, $dry_run = false)
    {
        $success = false;
        $bonus_amount = isset($extra_info['bonus_amount']) ? $extra_info['bonus_amount'] : 0;
        $deposit_amount = 0;
        $promoRuleId = $this->promorule['promorulesId'];
        $release_date = $description['release_date'];
        $errorMessageLang = '';
        $rouletteName = $description['rouletteName'];
        $cmsId = $description['cmsId'];
        $daily_times = $this->utils->safeGetArray($description, 'daily_spin_limits');

        if($dry_run){

            if ($this->process_mock('player_token', $player_token)) {
                $extra_info['player_token'] = strtolower($player_token);
            }
            if ($this->process_mock('is_roulette_api', $is_roulette_api)) {
                $extra_info['is_roulette_api'] = $is_roulette_api;
            }
            if ($this->process_mock('bonus_amount', $bonus_amount)) {
                $extra_info['bonus_amount'] = $bonus_amount;
            }
        }
        
        try{
            $is_roulette_api = (isset($extra_info['is_roulette_api']) && $extra_info['is_roulette_api'] == true) ? true: false;
            $spin_token = isset($extra_info['player_token'])? $extra_info['player_token']: false;

            if ($fromDate == $this->get_date_type(self::DATE_YESTERDAY_START)) {
                $release_date['start'] = $fromDate;
                $release_date['end'] = $toDate;
            }
         
            $api_name = 'roulette_api_' . $rouletteName;
            $this->load->library('roulette/'.$api_name);
            $roulette_api = $this->$api_name;
            $verify_res = $roulette_api->verifyRouletteSpinTimes($this->playerId, $cmsId);
            $checkReleasedBonusToday = key_exists('used_times', $verify_res) ? $verify_res['used_times'] : 0;

            $spin_conditions = isset($description['spin_conditions'])? $description['spin_conditions'] : false;

            $hasAnyDeposit = $this->callHelper('hasAnyDeposit', [null, null]);

            if(!$hasAnyDeposit) {
                throw new Exception("Not Valid Members.");
            }
            if($spin_conditions) {
                //get today deposit amount
                $verification_amount_type = $this->utils->safeGetArray($spin_conditions, 'type', false);
                $verification_amount = 0;

                $usePlayerReportRecords = array_key_exists('usePlayerReportRecords', $description) && ($description['usePlayerReportRecords'] == true);
                if($usePlayerReportRecords) {
                    $total_deposit = $this->callHelper('sum_deposit_amount', [$fromDate, $toDate, 0]);
                } else {
                    $total_deposit = $this->callHelper('totalDepositByPlayerAndDateTime', [$this->playerId, $fromDate, $toDate]);
                }
                $verification_amount = (int) $total_deposit;

                $per_amount = $this->utils->safeGetArray($spin_conditions, 'per_amount', 500);
                $per_earn = $this->utils->safeGetArray($spin_conditions, 'earn', 0);
                $threshold = $this->utils->safeGetArray($spin_conditions, 'threshold', 0);
                $any_deposit = $this->utils->safeGetArray($spin_conditions, 'any_deposit',0);

                if($verification_amount < $threshold) {
                    throw new Exception("Not Valid Threshold, [$verification_amount/$threshold]");
                }

                $earn_times = floor($verification_amount/$per_amount) * $per_earn;

                if ($verification_amount >= $threshold) {
                    $earn_times += $any_deposit;
                }

                $earn_times = $earn_times > $daily_times ? $daily_times : $earn_times;
        
                $this->appendToDebugLog('checkCustomizeBonusCondition earn_times', ['earn_times' => $earn_times]);

                $force_get_available_spin = true;
                list($availableAdditionalSpin, $usedAdditionalSpin, $targetAdditionalSpin) = $roulette_api->getAdditionalSpin($this->playerId, $fromDate, $toDate, $force_get_available_spin);
                $current_times = $earn_times + $availableAdditionalSpin + $usedAdditionalSpin;
                if(!($current_times == $verify_res['total_times'])){
                    $total_times = $verify_res['total_times'];
                    throw new Exception("Total times not match $total_times / $current_times");
                }

                $this->appendToDebugLog('checkCustomizeBonusCondition check params detail', ['release_date' => $release_date, 'checkReleasedBonusToday' => $checkReleasedBonusToday, 'verification_amount_type' => $verification_amount, 'verification_amount' => $verification_amount, 'promoRuleId' => $promoRuleId]);

                $available_spin = (($current_times - (int)$checkReleasedBonusToday) >= 1) ? true : false;
                if (!$available_spin) {
                    throw new Exception("Don't have available spin. Today total $verification_amount_type :[$verification_amount], Earn Spin:[$current_times], Applied:[$checkReleasedBonusToday]");
                }

                $daily_spin_limits = $description['daily_spin_limits'];
                if (!($daily_spin_limits > $checkReleasedBonusToday)) {
                    throw new Exception("Reach the daily spin, Limit:[$daily_spin_limits], Apply:[$checkReleasedBonusToday]");

                }
                $this->appendToDebugLog("Reach the daily spin, Limit:[], Apply:[]");

            } else {
                throw new Exception("Empty spin conditions.");
            }

            $success = true;

        }catch (Exception $e){

            $success = false;
            $errorMessageLang = $e->getMessage();
            $result['continue_process_after_script'] = FALSE;

        } finally {
            $result=[
                'success' => $success,
                'message' => $errorMessageLang,
                'bonus_amount' => $bonus_amount,
                'deposit_amount' => $deposit_amount
            ];

            return $result;
        }
    }
}
