<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 *
 * OGP-30774 [customize] Refer Friend Roulette
 * 
 * 每邀請一人存款>=20获得一次免费抽奖机会
 * 取款条件：3倍流水要求
 * 抽奖有效期：14天，14天后未旋转会作废。

 * 10等奖
 * 中奖概率：
 * 一等奖：中奖概率 0%      筹码：R$ 100二等奖：中奖概率 0%      筹码：R$ 50
 * 三等奖：中奖概率 0%      筹码：R$ 40
 * 四等奖：中奖概率 0%      筹码：R$ 35
 * 五等奖：中奖概率 0%      筹码：R$ 30
 * 六等奖：中奖概率 0%      筹码：R$ 25
 * 七等奖：中奖概率 1%      筹码：R$ 20
 * 八等奖：中奖概率 19%     筹码：R$ 15
 * 九等奖：中奖概率 50%     筹码：R$ 10
 * 十等奖：中奖概率 30%     筹码：R$ 7
 *
condition:
{
    "class": "promo_rule_roulette_r30774",
    "allowed_date": {
        "start": "",
        "end": ""
    },
    "release_date": {
        "start": "",
        "end": ""
    },
    "daily_spin_limits": 100,
    "spin_conditions":{
        "type"  : "deposit",
        "threshold": 20,
        "earn"  : 1,
        "exp_days": 14
    },
    "rouletteName": "r30774",
    "cmsId":4
}

{
    "bonus_amount":12,
    "is_roulette_api":true,
    "player_token":"asdasdasdas"
}
*/
class Promo_rule_roulette_r30774 extends Abstract_promo_rule
{
    public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null)
    {
        parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
    }

    public function getClassName()
    {
        return 'Promo_rule_roulette_r30774';
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
        $start_date = $this->promorule['applicationPeriodStart'];
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
        $start_date = $this->promorule['applicationPeriodStart'];
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

            if(! $dry_run && ( !$is_roulette_api || !$spin_token)){
                throw new Exception("Unable to apply.");
            }

            $api_name = 'roulette_api_' . $rouletteName;
            $this->load->library('roulette/'.$api_name);
            $roulette_api = $this->$api_name;
            $verify_res = $roulette_api->verifyRouletteSpinTimes($player_id, $cmsId);
            $checkReleasedBonusToday = key_exists('used_times', $verify_res) ? $verify_res['used_times'] : 0;
            $spin_conditions = isset($description['spin_conditions'])? $description['spin_conditions'] : false;

            $this->appendToDebugLog(__METHOD__ . " params details [$player_id]", [
                'verify_res' => $verify_res,
                'checkReleasedBonusToday' => $checkReleasedBonusToday,
                'spin_conditions' => $spin_conditions,
            ]);

            if($spin_conditions) {

                $this->load->model(['player_friend_referral']);

                $verification_amount_type = $this->utils->safeGetArray($spin_conditions, 'type', false);
                $verification_amount = 0;

                $threshold = $this->utils->safeGetArray($spin_conditions, 'threshold', 0);
                $referredList = $this->player_friend_referral->getReferredByPlayerIdWithDateRange($player_id, $fromDate, $toDate);

                $referredPlayerIds = array_column($referredList, 'invitedPlayerId');
                $referredPlayerIds = array_unique($referredPlayerIds);
                $referredPlayerIds = array_values($referredPlayerIds);

                $this->appendToDebugLog(__METHOD__ . " referred params [$player_id]", [
                    'referredList' => $referredList,
                    'referredPlayerIds' => $referredPlayerIds,
                ]);

                $countHasAnyDeposit = 0;
                $filteredPlayerIds = [];
                $countRef = 0;

                foreach ($referredPlayerIds as $reFplayerId) {
                    $total_deposit = $this->callHelper('totalDepositByPlayerAndDateTime', [$reFplayerId, $fromDate, $toDate]);

                    $this->appendToDebugLog(__METHOD__ . " total_deposit params [$reFplayerId]", [
                        'total_deposit' => $total_deposit
                    ]);

                    if ($total_deposit >= $threshold) {
                        $countHasAnyDeposit++;
                        $filteredPlayerIds[] = $reFplayerId;
                    }
                }

                $countRef = count($filteredPlayerIds);

                $this->appendToDebugLog(__METHOD__ . " referredList params [$player_id]", [
                    'dry_run' => $dry_run,
                    'countHasAnyDeposit' => $countHasAnyDeposit,
                    'filteredPlayerIds' => $filteredPlayerIds,
                    'countRef' => $countRef
                ]);

                if ($dry_run) {
                    $this->process_mock('countRef', $countRef);
                }

                if($countRef < 1 || $countHasAnyDeposit < 1) {
                    throw new Exception("Not Valid Members.");
                }

                $startDate = $this->utils->getTodayForMysql() .' '.Utils::FIRST_TIME;

                $force_get_available_spin = true;
                list($availableAdditionalSpin, $usedAdditionalSpin, $targetAdditionalSpin) = $roulette_api->getAdditionalSpin($player_id, $startDate, $toDate, $force_get_available_spin);
                $current_times = $availableAdditionalSpin + $usedAdditionalSpin;

                $this->appendToDebugLog(__METHOD__ . " Total times params [$player_id]", [
                    'current_times' => $current_times,
                    'verify_res' => $verify_res
                ]);

                if(!($current_times == $verify_res['total_times'])){
                    $total_times = $verify_res['total_times'];
                    throw new Exception("Total times not match $total_times / $current_times");
                }

                $this->appendToDebugLog('checkCustomizeBonusCondition check params detail', [
                    'release_date' => $release_date,
                    'checkReleasedBonusToday' => $checkReleasedBonusToday,
                    'verification_amount_type' => $verification_amount,
                    'verification_amount' => $verification_amount,
                    'promoRuleId' => $promoRuleId,
                    'current_times' => $current_times,
                    'availableAdditionalSpin' => $availableAdditionalSpin,
                    'usedAdditionalSpin' => $usedAdditionalSpin,
                    'targetAdditionalSpin' => $targetAdditionalSpin
                ]);

                $available_spin = (($current_times - (int)$checkReleasedBonusToday) >= 1) ? true : false;

                if (!$available_spin) {
                    throw new Exception("Don't have available spin. Today total $verification_amount_type :[$countHasAnyDeposit], Earn Spin:[$current_times], Applied:[$checkReleasedBonusToday]");
                }

                $daily_spin_limits = $description['daily_spin_limits'];

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
                'deposit_amount' => $deposit_amount
            ];

            return $result;
        }
    }
}
