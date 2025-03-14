<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 *
 * OGP-30970 [customize] New roulette
 * 
 * 當日累積存10 拿到一次，累積存30 拿到第二次，累積存100 拿到第三次，累積存300 拿到第四次，累積存500 拿到第五次
 *
    condition:
    {
        "class": "promo_rule_roulette_r30970",
        "allowed_date": {
            "start": "",
            "end": ""
        },
        "release_date": {
            "start": "",
            "end": ""
        },
        "daily_spin_limits": 5,
        "spin_conditions": {
            "type": "deposit",
            "threshold": 10,
            "deposit_amount": [
                {"min_deposit": 10, "max_deposit": 30, "earn": 1},
                {"min_deposit": 30, "max_deposit": 100, "earn": 2},
                {"min_deposit": 100, "max_deposit": 300, "earn": 3},
                {"min_deposit": 300, "max_deposit": 500, "earn": 4},
                {"min_deposit": 500, "max_deposit": 99999999999, "earn": 5}
            ]
        },
        "rouletteName": "r30970",
        "cmsId": 4

    {
        "bonus_amount":12,
        "is_roulette_api":true,
    }
*/
class Promo_rule_roulette_r30970 extends Abstract_promo_rule
{
    public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null)
    {
        parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
    }

    public function getClassName()
    {
        return 'Promo_rule_roulette_r30970';
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
        $start_date = $this->get_date_type(self::DATE_TODAY_START);
        $now_date = $this->get_date_type(self::TO_TYPE_NOW);

        $this->appendToDebugLog('runBonusConditionChecker date params start', ['start_date'=>$start_date, 'now_date'=>$now_date, 'description'=>$description]);

        $fromDate = !empty($allowed_date['start']) ? $allowed_date['start'] : $start_date;
        $toDate = !empty($allowed_date['end']) ? $allowed_date['end'] : $now_date;

        $this->appendToDebugLog('runBonusConditionChecker date params end', ['fromDate' => $fromDate, 'toDate' => $toDate]);

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
        $start_date = $this->get_date_type(self::DATE_TODAY_START);
        $now_date = $this->get_date_type(self::TO_TYPE_NOW);
        $fromDate = !empty($allowed_date['start']) ? $allowed_date['start'] : $start_date;
        $toDate = !empty($allowed_date['end']) ? $allowed_date['end'] : $now_date;

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
        $player_id = $this->playerId;
        $rouletteName = $description['rouletteName'];
        $cmsId = $description['cmsId'];

        if($dry_run){
            if ($this->process_mock('is_roulette_api', $is_roulette_api)) {
                $extra_info['is_roulette_api'] = $is_roulette_api;
            }
            if ($this->process_mock('bonus_amount', $bonus_amount)) {
                $extra_info['bonus_amount'] = $bonus_amount;
            }
        }
        
        try{
            $is_roulette_api = (isset($extra_info['is_roulette_api']) && $extra_info['is_roulette_api'] == true) ? true: false;

            if(! $dry_run && !$is_roulette_api){
                throw new Exception("Unable to apply.");
            }

            $api_name = 'roulette_api_' . $rouletteName;
            $this->load->library('roulette/'.$api_name);
            $roulette_api = $this->$api_name;
            $verify_res = $roulette_api->verifyRouletteSpinTimes($player_id, $cmsId);
            $checkReleasedBonusToday = key_exists('used_times', $verify_res) ? $verify_res['used_times'] : 0;
            $spin_conditions = isset($description['spin_conditions'])? $description['spin_conditions'] : false;

            $this->appendToDebugLog(__METHOD__ . " params details [$player_id]", [
                'promoRuleId' => $promoRuleId,
                'api_name' => $api_name,
                'verify_res' => $verify_res,
                'checkReleasedBonusToday' => $checkReleasedBonusToday,
                'spin_conditions' => $spin_conditions,
            ]);

            if($spin_conditions) {

                $verification_amount_type = $this->utils->safeGetArray($spin_conditions, 'type', false);
                $verification_amount = 0;
                $hasAnyDeposit = $this->callHelper('hasAnyDeposit', [$fromDate, $toDate]);

                $this->appendToDebugLog(__METHOD__ . " hasAnyDeposit params [$player_id]", [
                    'hasAnyDeposit' => $hasAnyDeposit,
                    'verification_amount_type' => $verification_amount_type,
                    'verification_amount' => $verification_amount,
                ]);

                if(!$hasAnyDeposit) {
                    throw new Exception("Not Valid Members.");
                }

                $usePlayerReportRecords = array_key_exists('usePlayerReportRecords', $description) && ($description['usePlayerReportRecords'] == true);
                if($usePlayerReportRecords) {
                    $total_deposit = $this->callHelper('sum_deposit_amount', [$fromDate, $toDate, 0]);
                } else {
                    $total_deposit = $this->callHelper('totalDepositByPlayerAndDateTime', [$player_id, $fromDate, $toDate]);
                }

                $threshold = $this->utils->safeGetArray($spin_conditions, 'threshold', 0);
                $deposit_amount = $this->utils->safeGetArray($spin_conditions, 'deposit_amount', []);
                $verification_amount = (int)$total_deposit;

                $this->appendToDebugLog(__METHOD__ . " promo params [$player_id]", [
                    'dry_run' => $dry_run,
                    'usePlayerReportRecords' => $usePlayerReportRecords,
                    'total_deposit' => $total_deposit,
                    'threshold' => $threshold,
                    'deposit_amount' => $deposit_amount,
                    'verification_amount' => $verification_amount,
                ]);

                if($verification_amount < $threshold) {
                    throw new Exception("Not Valid Threshold, [$verification_amount/$threshold]");
                }

                foreach ($deposit_amount as $val) {
                    if ($total_deposit >= $val['min_deposit'] && $total_deposit < $val['max_deposit']) {
                        $earn_times = $val['earn'];
                        break;
                    }
                }

                $force_get_available_spin = true;
                list($availableAdditionalSpin, $usedAdditionalSpin, $targetAdditionalSpin) = $roulette_api->getAdditionalSpin($player_id, $fromDate, $toDate, $force_get_available_spin);
                $current_times = $earn_times + $availableAdditionalSpin + $usedAdditionalSpin;

                $this->appendToDebugLog(__METHOD__ . " Total times params [$player_id]", [
                    'earn_times' => $earn_times,
                    'availableAdditionalSpin' => $availableAdditionalSpin,
                    'usedAdditionalSpin' => $usedAdditionalSpin,
                    'targetAdditionalSpin' => $targetAdditionalSpin,
                    'current_times' => $current_times,
                    'verify_res' => $verify_res
                ]);

                if(!($current_times == $verify_res['total_times'])){
                    $total_times = $verify_res['total_times'];
                    throw new Exception("Total times not match $total_times / $current_times");
                }

                $available_spin = (($current_times - (int)$checkReleasedBonusToday) >= 1) ? true : false;

                $this->appendToDebugLog(__METHOD__ .' check available_spin', [
                    'available_spin' => $available_spin,
                ]);

                if (!$available_spin) {
                    throw new Exception("Don't have available spin. Today total $verification_amount_type :[$verification_amount], Earn Spin:[$current_times], Applied:[$checkReleasedBonusToday]");
                }

                $daily_spin_limits = $description['daily_spin_limits'];

                $this->appendToDebugLog(__METHOD__ .' check daily_spin_limits', [
                    'daily_spin_limits' => $daily_spin_limits,
                    'checkReleasedBonusToday' => $checkReleasedBonusToday,
                ]);

                if (!($daily_spin_limits > $checkReleasedBonusToday)) {
                    throw new Exception("Reach the daily spin, Limit:[$daily_spin_limits], Apply:[$checkReleasedBonusToday]");
                }
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
            ];

            return $result;
        }
    }
}