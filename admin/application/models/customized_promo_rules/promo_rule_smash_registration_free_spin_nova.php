<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/abstract_promo_rule.php';

/**
 *
 * OGP-27120
 *
 *

condition:
{
    "class": "promo_rule_smash_registration_free_spin_nova",
    "allow_after_datetime": "2022-08-01 00:00:00",
    "allow_end_datetime": "2022-10-30 00:00:00",
    "bindWithRoulette": {
        "target1": {
            "name": "normal_1",
            "ratio": 1,
            "limit": 0
        },
        "target2": {
            "name": "super_1"
        }
    },
    "t2tierSetting":{
            ["threshold" => 20, "award" => 1],
            ["threshold" => 50, "award" => 3],
            ["threshold" => 100, "award" => 9],
            ["threshold" => 200, "award" => 19],
            ["threshold" => 500, "award" => 49],
            ["threshold" => 1000, "award" => 99]
    }
}

 *
 *
 */
class Promo_rule_smash_registration_free_spin_nova extends Abstract_promo_rule
{

    public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount = null)
    {
        parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
    }

    public function getClassName()
    {
        return 'Promo_rule_smash_registration_free_spin_nova';
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


        $now = $this->utils->getNowForMysql();
        // $player_create_at = $this->callHelper('get_date_type', [self::REGISTER_DATE]);
        $allow_after_datetime = !empty($description['allow_after_datetime']) ? $description['allow_after_datetime'] : $now;

        $bindWithRoulette = $this->checkRouletteSetting($description, $extra_info);
        if (!$bindWithRoulette) {
            $success = false;
            $errorMessageLang = $dry_run ? 'Roulette setting not found' : 'promo_rule.common.error';
        }

        $timestamp_allow_after_datetime = strtotime($allow_after_datetime);

        $this->appendToDebugLog(
            "original allow_after_datetime [$allow_after_datetime],"
            . "timestamp of allow_after_datetime [$timestamp_allow_after_datetime],"
        );

        $this->load->model(['player_friend_referral']);
        $fromDatetime = date('Y-m-d 00:00:00', strtotime($description['allow_after_datetime']));
        $toDatetime = date('Y-m-d 23:59:59', strtotime($description['allow_end_datetime']));

        $_referral_infos = $this->player_friend_referral->getPlayerTotalFriendRefferalCountByDatetimeAndStatus($fromDatetime, $toDatetime, $this->playerId);
        if (!empty($_referral_infos)) {
            $referral_infos = $_referral_infos[0];
            $countRef = (isset($referral_infos['total_referral']) ? $referral_infos['total_referral'] : 0);
        }
        
        if ($dry_run) {
            $this->process_mock('countRef', $countRef);
        }
        if (!empty($countRef)) {
            $extra_info['countRef'] = $countRef;
            $success = true;
        } else {
            $success = false;
            $errorMessageLang = $dry_run ? 'countRef = 0' : 'promo_rule.common.error';
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

            list($t1times, $t2times, $tot1Released, $tot2Released) = $this->generateFreespinTimes($description, $extra_info, $dry_run);
            $extra_info['force_custom_success_message'] = "get target1:$t1times/$tot1Released, target2:$t2times/$tot2Released";
            $this->appendToDebugLog('generateFreespinTimes', $extra_info['force_custom_success_message']);

            if (isset($extra_info['isBindWithRoulette']) && $extra_info['isBindWithRoulette'] == true) {
                $extra_info['additionalSpinDetail'] = [];
                $promorule = $this->promorule;
                $promoRuleId = $promorule['promorulesId'];

                $earnT1 = $t1times - $tot1Released;
                $target1 = $extra_info['target1'];
                $rouletteName1 =  $target1['name'];
                if($earnT1 > 0){


                    $extra_info['additionalSpinDetail'][$rouletteName1] = [
                        'targetRoulette' => $rouletteName1,
                        'promorulesId' => $promoRuleId,
                        'quantity' => $earnT1,
                        'generateBy' => $this->getClassName(),
                        'exp_at' => isset($target1['exp_at']) ?$target1['exp_at'] : null,
                    ];
                }

                $earnT2 = 0;
                $target2 = $extra_info['target2'];
                $rouletteName2 =  $target2['name'];
                if( ($earnT2 = ($t2times - $tot2Released)) > 0){

                    $extra_info['additionalSpinDetail'][$rouletteName2] = [
                        'targetRoulette' => $rouletteName2,
                        'promorulesId' => $promoRuleId,
                        'quantity' => $earnT2,
                        'generateBy' => $this->getClassName(),
                        'exp_at' => isset($target2['exp_at']) ?$target2['exp_at'] : null,
                    ];
                }

                $extra_info['reason'] = "release free spin to [$rouletteName1 : $earnT1] [$rouletteName2 : $earnT2]";
                $extra_info['force_custom_success_message'] = $dry_run ? "release free spin to [$rouletteName1 : $earnT1] [$rouletteName2 : $earnT2]" : lang('Free Spins Claimed Successfully.');
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

    /**
     * generateFreespinTimes function
     * return the reslut of target roulette times
     *
     * @param array $description
     * @param array $extra_info
     * @param boolean $dry_run
     * @return array 
     */
    private function generateFreespinTimes($description, &$extra_info, $dry_run)
    {
        $tot1 = $tot2 = $countRef = 0;
        // $t2tierSetting = [
        //     ["threshold" => 20, "award" => 1],
        //     ["threshold" => 50, "award" => 3],
        //     ["threshold" => 100, "award" => 9],
        //     ["threshold" => 200, "award" => 19],
        //     ["threshold" => 500, "award" => 49],
        //     ["threshold" => 1000, "award" => 99]
        // ];
        $t2tierSetting = $description['t2tierSetting'];
        $countRef = $extra_info['countRef'];
        $tot1 = $countRef;
        $firstlevel = $t2tierSetting[0];
        $lastlevel = end($t2tierSetting);
        if (!($countRef < $firstlevel['threshold'])) {
            $tierType = array_key_exists('tierType', $description) ? $description['tierType'] : 'default';
            if($tierType == 'currentLevel'){

                for ($i = count($t2tierSetting); $i >= 1; $i--) {
                    $level = [];
                    $this->appendToDebugLog("generateFreespinTimes check tier", ['level' => $t2tierSetting[$i - 1]]);
    
                    $level = $t2tierSetting[$i - 1];
                    $threshold = $level["threshold"];
                    $award = $level["award"];
                    if ($countRef >= $threshold) {
                        $tot2 = $award;
                        break;
                    }
                }
            } else {

                $countArr = count($t2tierSetting);
                for ($i = 0; $i < $countArr; $i++) {
                    $level = [];
                    $this->appendToDebugLog("generateFreespinTimes check tier", ['level' => $t2tierSetting[$i]]);
                    $level = $t2tierSetting[$i];
                    $threshold = $level["threshold"];
                    $award = $level["award"];
                    if ($countRef >= $threshold) {
                        $tot2 = $tot2 + $award;
                    }
                }
            }
            
        }
        list($tot1Released, $tot2Released) = $this->getReleasedSpinTimes($description, $extra_info, $dry_run);

        return [$tot1, $tot2, $tot1Released, $tot2Released];
    }
    private function getReleasedSpinTimes($description, &$extra_info, $dry_run)
    {
        $tot1Released = $tot2Released = 0;

        $target1 = $extra_info['target1'];
        $api_name = 'roulette_api_' . $target1['name'];
        $this->load->library('roulette/' . $api_name);
        $this->roulette_api = $this->$api_name;
        $tot1Released = $target1additionalSpin = $this->roulette_api->getSpinByGenerateBy($this->playerId, $this->getClassName());
        $this->appendToDebugLog("getReleasedSpinTimes", [$target1['name'] => $target1additionalSpin]);

        $target2 = $extra_info['target2'];
        $api_name = 'roulette_api_' . $target2['name'];
        $this->load->library('roulette/' . $api_name);
        $this->roulette_api = $this->$api_name;
        $tot2Released = $target2additionalSpin = $this->roulette_api->getSpinByGenerateBy($this->playerId, $this->getClassName());
        $this->appendToDebugLog("getReleasedSpinTimes", [$target2['name'] => $target2additionalSpin]);

        return  [$tot1Released, $tot2Released];
    }
}
