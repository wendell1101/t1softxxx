<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 *
 * OGP-31682 [customize] TH Lucky wheel
 * 
 * 當日累積存5000 拿到1次，累積存50000 拿到5次，累積存100000 拿到20次
 * 上限26次
condition:
{
    "class": "promo_rule_roulette_r31682",
    "allowed_date": {
        "start": "",
        "end": ""
    },
    "release_date": {
        "start": "",
        "end": ""
    },
    "daily_spin_limits": 26,
    "spin_conditions": {
        "type": "deposit",
        "deposit_amount": [
            {"deposit": 5000, "earn": 1},
            {"deposit": 50000, "earn": 5},
            {"deposit": 100000, "earn": 20}
        ]
    },
    "rouletteName": "r31682",
    "cmsId": 11
}

{
    "bonus_amount":12,
    "is_roulette_api":true
}
*/
class Promo_rule_roulette_r31682 extends Abstract_promo_rule
{
    public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null)
    {
        parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
    }

    public function getClassName()
    {
        return 'Promo_rule_roulette_r31682';
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
        $earn_times = 0;

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
                    if ($total_deposit >= $val['deposit']) {
                        $earn_times += $val['earn'];
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