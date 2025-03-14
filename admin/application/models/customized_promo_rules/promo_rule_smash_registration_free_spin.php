<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/abstract_promo_rule.php';

/**
 *
 * OGP-23997
 *
 * 需完善註冊
 * 需在2021-10-21 00:00:00後註冊的玩家
 *

condition:
{
    "class": "promo_rule_smash_registration_free_spin",
    "completed_player_info": true, //(optional)
    "verified_phone": true, //(optional)
    "verified_email": true, //(optional)
    "filled_cpf": true,
    "allow_after_datetime": "2022-08-01 00:00:00",
    "bindWithRoulette": "normal_1", //string
}

 *
 *
 */
class Promo_rule_smash_registration_free_spin extends Abstract_promo_rule
{

    public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount = null)
    {
        parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
    }

    public function getClassName()
    {
        return 'Promo_rule_smash_registration_free_spin';
    }

    protected function completed_player_info($description)
    {
        $completed_player_info = false;
        $completed_player_info = isset($description['completed_player_info']) ? $description['completed_player_info'] : $completed_player_info;
        if ($completed_player_info) {
            $conditionResult = $this->player_model->getPlayerAccountInfoStatus($this->playerId);
            $completed_player_info = $conditionResult['status'];
            $conditionResultMissingFields = !empty($conditionResult['missing_fields']) ? $conditionResult['missing_fields'] : NULL;
            $this->appendToDebugLog('not complete player info', ['missing_fields' => $conditionResultMissingFields]);
        }

        return $completed_player_info;
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

        $completed_player_info = $this->completed_player_info($description);

        $now = $this->utils->getNowForMysql();
        $player_create_at = $this->callHelper('get_date_type', [self::REGISTER_DATE]);
        $allow_after_datetime = !empty($description['allow_after_datetime']) ? $description['allow_after_datetime'] : $now;
        $verified_email = $this->isVerifiedEmail($description);
        $verified_phone = $this->isVerifiedPhone($description);
        $filled_cpf = $this->isFilledCPF($description);
        if (strtotime($player_create_at) >= strtotime($allow_after_datetime)) {

            switch (true) {
                case (!$completed_player_info):
                    $errorMessageLang = lang('Please fill in the player information completely.');
                    $errorMessageLang .=' <br> <a class="btn promoRedriectBtn" href="/player_center/dashboard/index#accountInformation">'.lang('Go Complete').'</a>';
                    break;
                case (!$verified_phone):
                    $errorMessageLang = lang('Please verify mobile number.');
                    $errorMessageLang .=' <br> <a class="btn promoRedriectBtn" href="/player_center2/security">'.lang('Go Complete').'</a>';
                    break;
                case (!$verified_email):
                    $errorMessageLang = lang('Please verify email.');
                    $errorMessageLang .=' <br> <a class="btn promoRedriectBtn" href="/player_center2/security">'.lang('Go Complete').'</a>';
                    break;
                case (!$filled_cpf):
                    $errorMessageLang = lang('Please fill in your CPF.');
                    $errorMessageLang .=' <br> <a class="btn promoRedriectBtn"href="/player_center/dashboard/index#accountInformation">'.lang('Go Complete').'</a>';
                    break;
                default:
                    $success = true;
                    break;
            }
            if($dry_run) {
                $errorMessageLang = htmlspecialchars($errorMessageLang);
            }
            // $errorMessageLang .=' <br> <a href="/player_center/dashboard/index#accountInformation">'.lang('Go Complete').'</a>';

        } else {
            // $errorMessageLang = 'promo_rule.common.error';
            $errorMessageLang = lang('promo_rule_smash_registration_free_spin.not_new_player');
        }

        $bindWithRoulette = $this->checkRouletteSetting($description, $extra_info);
        if (!$bindWithRoulette) {
            $success = false;
            $errorMessageLang = $dry_run ? 'Roulette setting not found' : 'promo_rule.common.error';
        }

        $timestamp_allow_after_datetime = strtotime($allow_after_datetime);
        $timestamp_player_create_on = strtotime($player_create_at);

        $this->appendToDebugLog(
            "completed_player_info [$completed_player_info],"
                . "original allow_after_datetime [$allow_after_datetime],"
                . "timestamp of allow_after_datetime [$timestamp_allow_after_datetime],"
                . "original player create on datetime [$player_create_at],"
                . "timestamp of player create on [$timestamp_player_create_on]"
        );

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
        if ($success == true) {
            if (isset($extra_info['isBindWithRoulette']) && $extra_info['isBindWithRoulette'] == true) {

                $extra_info['force_custom_success_message'] = lang('Free Spins Claimed Successfully.');
                $rouletteName = $description['bindWithRoulette'];

                $promorule = $this->promorule;
                $promoRuleId = $promorule['promorulesId'];
                $extra_info['additionalSpinDetail'][$rouletteName] = [
                    'targetRoulette' => $description['bindWithRoulette'],
                    'promorulesId' => $promoRuleId,
                    'quantity' => isset($description['quantity']) ?$description['quantity'] : 1,
                    'generateBy' => $this->getClassName(),
                    'exp_at' => isset($description['exp_at']) ?$description['exp_at'] : null,
                ];
                $extra_info['reason'] = "release free spin to [$rouletteName]";
                $this->appendToDebugLog('additionalSpinDetail', $extra_info['additionalSpinDetail']);
            }
        }

        $result = ['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonus_amount];
        return $result;
    }

    protected function isVerifiedPhone($description)
    {
        $verified_phone = true;

        if (!empty($description['verified_phone']) && $description['verified_phone']) {
            $verified_phone = $this->player_model->isVerifiedPhone($this->playerId);
        }

        if (!$verified_phone) {
            $this->appendToDebugLog('not verified phone', ['verified_phone' => $verified_phone]);
        }

        return $verified_phone;
    }

    protected function isVerifiedEmail($description)
    {
        $verified_email = true;

        if (!empty($description['verified_email']) && $description['verified_email']) {
            $verified_email = $this->player_model->isVerifiedEmail($this->playerId);
        }

        if (!$verified_email) {
            $this->appendToDebugLog('not verified email', ['verified_email' => $verified_email]);
        }

        return $verified_email;
    }

    protected function isFilledCPF($description)
    {
        $filled_cpf = true;

        if (!empty($description['filled_cpf']) && $description['filled_cpf']) {
            $filled_cpf = $this->player_model->isFilledCPFnumber($this->playerId);
        }

        if (!$filled_cpf) {
            $this->appendToDebugLog('not Filled cpf', ['filled_cpf' => $filled_cpf]);
        }

        return $filled_cpf;
    }

    private function checkRouletteSetting($description, &$extra_info)
    {
        $roulette_reward_odds_settings = $this->utils->getConfig('roulette_reward_odds_settings');
        if (!empty($description['bindWithRoulette']) && array_key_exists($description['bindWithRoulette'], $roulette_reward_odds_settings)) {
            $extra_info['isBindWithRoulette'] = true;
            $extra_info['bindWithRoulette'] = $description['bindWithRoulette'];
            $this->appendToDebugLog(
                "Promo bind to roulette ",
                ['roulette' => $description['bindWithRoulette']]
            );
            $rouletteName = $description['bindWithRoulette'];
            $api_name = 'roulette_api_' . $rouletteName;
            $classExists = file_exists(strtolower(APPPATH . 'libraries/roulette/' . $api_name . ".php"));
            if (!$classExists) {
                return false;
            }
            $this->load->library('roulette/' . $api_name);
            $this->roulette_api = $this->$api_name;
            return true;
        }
        return false;
    }
}
