<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 *
 * OGP-31492 [customize] Lucky wheel
 * 
 * 當日任何存款拿到一次
 * 當日累積存滿300拿到第二次
 * 當日每推薦一個有效好友拿到一次可無限邀請(有機會當日多筆有效好友)
 *
condition:
{
    "class": "promo_rule_roulette_r31492",
    "allowed_date": {
        "start": "",
        "end": ""
    },
    "release_date": {
        "start": "",
        "end": ""
    },
    "daily_spin_limits": 9999,
    "spin_conditions": {
        "type": "deposit",
        "threshold": 300,
        "any_deposit_earn": 1,
        "deposit_earn": 1,
        "referral_earn": 1
    },
    "rouletteName": "r31492",
    "cmsId": 10
}


{
    "bonus_amount":12,
    "is_roulette_api":true,
}
*/
class Promo_rule_roulette_r31492 extends Abstract_promo_rule
{
    public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null)
    {
        parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
    }

    public function getClassName()
    {
        return 'Promo_rule_roulette_r31492';
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

    public function getCondictionEarn($spin_conditions = null)
	{
		$any_deposit = 1;
		$deposit = 1;
		$referral = 1;
		if ($spin_conditions) {
			$any_deposit = $this->utils->safeGetArray($spin_conditions, 'any_deposit_earn', 1);
			$deposit = $this->utils->safeGetArray($spin_conditions, 'deposit_earn', 1);
			$referral = $this->utils->safeGetArray($spin_conditions, 'referral_earn', 1);
		}
		return [$any_deposit, $deposit, $referral];
	}

    private function checkCustomizeBonusCondition($fromDate, $toDate, &$extra_info, $description, &$errorMessageLang, $dry_run = false)
    {
        $success = false;
        $bonus_amount = isset($extra_info['bonus_amount']) ? $extra_info['bonus_amount'] : 0;
        $promoRuleId = $this->promorule['promorulesId'];
        $release_date = $description['release_date'];
        $errorMessageLang = '';
        $playerId = $this->playerId;
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
            $verify_res = $roulette_api->verifyRouletteSpinTimes($playerId, $cmsId);
            $checkReleasedBonusToday = key_exists('used_times', $verify_res) ? $verify_res['used_times'] : 0;
            $spin_conditions = isset($description['spin_conditions'])? $description['spin_conditions'] : false;

            $this->appendToDebugLog(__METHOD__ . " params details [$playerId]", [
                'promoRuleId' => $promoRuleId,
                'api_name' => $api_name,
                'verify_res' => $verify_res,
                'checkReleasedBonusToday' => $checkReleasedBonusToday,
                'spin_conditions' => $spin_conditions,
            ]);

            if($spin_conditions) {

                $verification_amount_type = $this->utils->safeGetArray($spin_conditions, 'type', false);
                $verification_amount = 0;
                $earn_times = 0;
                list($anyDepositEarn, $depositEarn, $referralEarn) = $this->getCondictionEarn($spin_conditions);

                $hasAnyDeposit = $this->callHelper('hasAnyDeposit', [$fromDate, $toDate]);

                $this->appendToDebugLog(__METHOD__ . " hasAnyDeposit params [$playerId]", [
                    'hasAnyDeposit' => $hasAnyDeposit,
                    'verification_amount_type' => $verification_amount_type,
                    'verification_amount' => $verification_amount,
                    'anyDepositEarn' => $anyDepositEarn,
                    'depositEarn' => $depositEarn,
                    'referralEarn' => $referralEarn
                ]);

                if(!$hasAnyDeposit) {
                    throw new Exception("Not Valid Members.");
                }else{
                    $earn_times += $anyDepositEarn;
                }

                $this->appendToDebugLog(__METHOD__ . " hasAnyDeposit earn [$playerId]", [
                    'earn_times' => $earn_times,
                ]);

                $usePlayerReportRecords = array_key_exists('usePlayerReportRecords', $description) && ($description['usePlayerReportRecords'] == true);
                if($usePlayerReportRecords) {
                    $total_deposit = $this->callHelper('sum_deposit_amount', [$fromDate, $toDate, 0]);
                } else {
                    $total_deposit = $this->callHelper('totalDepositByPlayerAndDateTime', [$playerId, $fromDate, $toDate]);
                }

                $threshold = $this->utils->safeGetArray($spin_conditions, 'threshold', 0);
                $verification_amount = (int)$total_deposit;

                if($verification_amount >= $threshold) {
                    $earn_times += $depositEarn;
                }

                $this->appendToDebugLog(__METHOD__ . " deposit params [$playerId]", [
                    'dry_run' => $dry_run,
                    'usePlayerReportRecords' => $usePlayerReportRecords,
                    'total_deposit' => $total_deposit,
                    'threshold' => $threshold,
                    'verification_amount' => $verification_amount,
                    'earn_times' => $earn_times,
                ]);

                $referralBonusList = $this->transactions->getReferralBonusList($playerId, $fromDate, $toDate);
                $countRefList = count($referralBonusList);

                if($countRefList > 0){
                    $earn_times += ($countRefList * $referralEarn);
                }

                $this->appendToDebugLog(__METHOD__ . " referral params [$playerId]", [
                    'referralBonusList' => $referralBonusList,
                    'countRefList' => $countRefList,
                    'earn_times' => $earn_times,
                ]);

                $force_get_available_spin = true;
                list($availableAdditionalSpin, $usedAdditionalSpin, $targetAdditionalSpin) = $roulette_api->getAdditionalSpin($playerId, $fromDate, $toDate, $force_get_available_spin);
                $current_times = $earn_times + $availableAdditionalSpin + $usedAdditionalSpin;

                $this->appendToDebugLog(__METHOD__ . " Total times params [$playerId]", [
                    'earn_times' => $earn_times,
                    'availableAdditionalSpin' => $availableAdditionalSpin,
                    'usedAdditionalSpin' => $usedAdditionalSpin,
                    'targetAdditionalSpin' => $targetAdditionalSpin,
                    'current_times' => $current_times,
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