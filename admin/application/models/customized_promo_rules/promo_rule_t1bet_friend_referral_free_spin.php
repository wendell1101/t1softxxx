<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/abstract_promo_rule.php';

/**
 *
 * OGP-30774 [customize] Refer Friend Roulette
 * 
 * 每邀請一人存款>=20获得一次免费抽奖机会
 * 取款条件：3倍流水要求
 * 抽奖有效期：14天，14天后未旋转会作废。

 * 10等奖
 * 中奖概率：
 * 一等奖：中奖概率 0%      筹码：R$ 100
 * 二等奖：中奖概率 0%      筹码：R$ 50
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
    "class": "promo_rule_t1bet_friend_referral_free_spin",
    "bindWithRoulette": {
        "target1": {
            "name": "r30774"
        }
    },
    "allowed_date": {
        "start": "",
        "end": ""
    },
    "release_date": {
        "start": "",
        "end": ""
    },
    "daily_spin_limits": 100,
    "threshold": 20,
}

mock for class
{
    "countRef":3,
    "countHasAnyDeposit":2
}
 *
 *
 */
class Promo_rule_t1bet_friend_referral_free_spin extends Abstract_promo_rule
{

    public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount = null)
    {
        parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
    }

    public function getClassName()
    {
        return 'Promo_rule_t1bet_friend_referral_free_spin';
    }

    /**
     * run bonus condition checker
     * @param  array $description original description in rule
     * @param  array $extra_info
     * @param  boolean $dry_run
     * @return  array ['success'=> success, 'message'=> errorMessageLang, 'continue_process_after_script' => FALSE]
     */
    protected function runBonusConditionChecker($description, &$extra_info, $dry_run)
    {
        $success = false;
        $errorMessageLang = null;
        $player_id = $this->playerId;
        $start_date = $this->promorule['applicationPeriodStart'];
        $now_date = $this->get_date_type(self::TO_TYPE_NOW);
        $threshold = $description['threshold'];
        $bindWithRoulette = $this->checkRouletteSetting($description, $extra_info);

        if (!$bindWithRoulette) {
            $success = false;
            $errorMessageLang = $dry_run ? 'Roulette setting not found' : 'promo_rule.common.error';
        }

        $this->load->model(['player_friend_referral']);

        $referredList = $this->player_friend_referral->getReferredByPlayerIdWithDateRange($player_id, $start_date, $now_date);

        $referredPlayerIds = array_column($referredList, 'invitedPlayerId');
        $referredPlayerIds = array_unique($referredPlayerIds);
        $referredPlayerIds = array_values($referredPlayerIds);

        $countRef = 0;
        $countHasAnyDeposit = 0;
        $filteredPlayerIds = [];

        foreach ($referredPlayerIds as $playerId) {
            $deposit = $this->callHelper('totalDepositByPlayerAndDateTime', [$playerId, $start_date, $now_date]);

            if ($deposit >= $threshold) {
                $countHasAnyDeposit++;
                $filteredPlayerIds[] = $playerId;
            }
        }

        $countRef = count($filteredPlayerIds);

        $this->appendToDebugLog(__METHOD__ . "generatePlayerRoulette params [$player_id]", [
            'description' => $description,
            'referredPlayerIds' => $referredPlayerIds,
            'countHasAnyDeposit' => $countHasAnyDeposit,
            'filteredPlayerIds' => $filteredPlayerIds,
            'countRef' => $countRef,
            'extra_info' => $extra_info,
            'dry_run' => $dry_run,
            'start_date' => $start_date,
            'now_date' => $now_date,
        ]);

        if ($dry_run) {
            $this->process_mock('countRef', $countRef);
            $this->process_mock('countHasAnyDeposit', $countHasAnyDeposit);
            $this->appendToDebugLog("is dryrun countRef [$countRef]/countHasAnyDeposit [$countHasAnyDeposit]");
        }

        if ($countRef < 1 || $countHasAnyDeposit < 1) {
            $success = false;
            $errorMessageLang = $dry_run ? 'countRef = 0' : 'promo_rule.common.error';
        }else{
            $extra_info['countRef'] = $countRef;
            $extra_info['countHasAnyDeposit'] = $countHasAnyDeposit;
            $extra_info['start_date'] = $start_date;
            $extra_info['now_date'] = $now_date;
            $success = true;
        }
 
        $result = ['success' => $success, 'message' => $errorMessageLang, 'continue_process_after_script' => FALSE];
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
        $isSpinReleased = false;
        $errorMessageLang = null;
        $bonus_amount = 0;

        $result = $this->runBonusConditionChecker($description, $extra_info, $dry_run);
        $success = $result['success'];
        $errorMessageLang = $result['message'];

        if ($success) {

            $countRef = $extra_info['countRef'];
            $countHasAnyDeposit = $extra_info['countHasAnyDeposit'];

            list($tot1Released, $tot2Released) = $this->getReleasedSpinTimes($description, $extra_info, $dry_run);

            $extra_info['force_custom_success_message'] = "get target1:$countHasAnyDeposit/$tot1Released";
            $this->appendToDebugLog('generateFreespinTimes', $extra_info['force_custom_success_message']);

            $this->appendToDebugLog(__METHOD__ . "releaseBonus params", [
                'generateFreespinTimes' => $extra_info['force_custom_success_message'],
                'countHasAnyDeposit' => $countHasAnyDeposit,
                'countRef' => $countRef,
                'extra_info' => $extra_info
            ]);

            if (isset($extra_info['isBindWithRoulette']) && $extra_info['isBindWithRoulette'] == true) {
                $extra_info['additionalSpinDetail'] = [];
                $promorule = $this->promorule;
                $promoRuleId = $promorule['promorulesId'];

                $earnT1 = $countHasAnyDeposit - $tot1Released;
                $target1 = $extra_info['target1'];
                $rouletteName1 =  $target1['name'];
                $exp_days = isset($target1['exp_days']) ? $target1['exp_days'] : 14;
                $exp_at = date('Y-m-d', strtotime("+{$exp_days} days"));

                if($earnT1 > 0){
                    $extra_info['additionalSpinDetail'][$rouletteName1] = [
                        'targetRoulette' => $rouletteName1,
                        'promorulesId' => $promoRuleId,
                        'quantity' => $earnT1,
                        'generateBy' => $rouletteName1 . '_refer_friend',
                        'exp_at' => $exp_at,
                    ];
                }

                $extra_info['reason'] = "release free spin to [$rouletteName1 : $earnT1]";
                $extra_info['force_custom_success_message'] = $dry_run ? "release free spin to [$rouletteName1 : $earnT1]" : lang('Free Spins Claimed Successfully.');
                $this->appendToDebugLog('additionalSpinDetail', $extra_info['additionalSpinDetail']);
            }
        }
        $result = ['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonus_amount];
        return $result;
    }

    private function checkRouletteSetting($description, &$extra_info)
    {
        $roulette_reward_odds_settings = $this->utils->getConfig('roulette_reward_odds_settings');
        $extra_info['isBindWithRoulette'] = false;

        if (array_key_exists('bindWithRoulette', $description) && is_array($description['bindWithRoulette'])) {

            foreach ($description['bindWithRoulette'] as $key => $target) {
                $rouletteName = array_key_exists('name', $target)?$target['name'] : false;
                if(!$rouletteName) {return false;}
                $api_name = 'roulette_api_' . $rouletteName;
                $classExists = file_exists(strtolower(APPPATH . 'libraries/roulette/' . $api_name . ".php"));
                if (!$classExists) {
                    return false;
                }
                $extra_info[$key] =  $target;
            }
            $this->appendToDebugLog(
                "Promo bind to roulette ",
                ['roulette' => $description['bindWithRoulette']]
            );
            $extra_info['isBindWithRoulette'] = true;
            return true;
        }
        return false;
    }

    private function getReleasedSpinTimes($description, &$extra_info, $dry_run)
    {
        $tot1Released = $tot2Released = 0;

        $start_date = $extra_info['start_date'];
        $end_date = $extra_info['now_date'];
        $target1 = $extra_info['target1'];
        $api_name = 'roulette_api_' . $target1['name'];
        $this->load->library('roulette/' . $api_name);
        $this->roulette_api = $this->$api_name;
        $player_id = $this->playerId;
        // $tot1Released = $target1additionalSpin = $this->roulette_api->getSpinByGenerateBy($this->playerId, $this->getClassName());

        list($availableAdditionalSpin, $usedAdditionalSpin) = $this->roulette_api->getAdditionalSpin($this->playerId, $start_date, $end_date, true);
        $tot1Released = $availableAdditionalSpin + $usedAdditionalSpin;
        $this->appendToDebugLog("getReleasedSpinTimes", [$target1['name'] => $tot1Released, 'availableAdditionalSpin' => $availableAdditionalSpin, 'usedAdditionalSpin' => $usedAdditionalSpin, 'playerId' => $player_id]);

        // $target2 = $extra_info['target2'];
        // $api_name = 'roulette_api_' . $target2['name'];
        // $this->load->library('roulette/' . $api_name);
        // $this->roulette_api = $this->$api_name;
        // $tot2Released = $target2additionalSpin = $this->roulette_api->getSpinByGenerateBy($this->playerId, $this->getClassName());
        // $this->appendToDebugLog("getReleasedSpinTimes", [$target2['name'] => $target2additionalSpin]);

        return  [$tot1Released, $tot2Released];
    }
}
