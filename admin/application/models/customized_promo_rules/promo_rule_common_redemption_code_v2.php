<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/abstract_promo_rule.php';

/**
 *
 * OGP-25570
 * @property Redemption_code_model $redemption_code_model
 * @property Static_redemption_code_model $static_redemption_code_model
 * @property Static_redemption_code $static_redemption_code
 * @property Operatorglobalsettings $operatorglobalsettings
 *
 *
condition:
{
    "class": "promo_rule_common_redemption_code_v2"
}
 */
class Promo_rule_common_redemption_code_v2 extends Abstract_promo_rule
{
    // related setting
    // $config['enable_static_redemption_code_system'] = true;
    // $config['enable_static_redemption_code_edit'] = true;
    // $config['enable_redemption_code_system_in_playercenter'] = true;
    // $config['static_redemption_code_setting'] = [
    //     "custom_code" => true,
    //     "deposit_condictions" => true,
    // ];

    // $config['redemption_code_promo_cms_id'] = 9; //common rule
    // $config['fallback_currency_for_redemption_code']['stander'] = [
    //     "cny" =>["cms_id" => 9,],
    // ]; //common rule

    public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount = null)
    {
        parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
    }

    public function getClassName()
    {
        return 'Promo_rule_common_redemption_code_v2';
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

        $redemption_code = isset($extra_info['redemption_code']) ? $extra_info['redemption_code'] : '';

        $this->appendToDebugLog('runBonusConditionChecker check redemption_code', ['redemption_code' => $redemption_code]);

        $result = $this->checkCustomizeBonusCondition($description, $extra_info, $errorMessageLang, $dry_run);

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
        $this->load->model('static_redemption_code_model');
        $redemption_code = isset($extra_info['redemption_code']) ? $extra_info['redemption_code'] : '';
        $is_static_code = true; //(strlen($redemption_code) > 10);
        $playerId = $this->playerId;
        // $current_code_id = $this->utils->safeGetArray($extra_info, 'locked_code_id', false);
        $redemption_code_detail = $this->static_redemption_code_model->getPlayerPendingCode($redemption_code, $playerId, null);
        if (empty($redemption_code_detail) && $dry_run) {
            $redemption_code_detail = $this->static_redemption_code_model->getDetailsByCode($redemption_code, Static_redemption_code_model::CODE_STATUS_UNUSED);
        }
        if(empty($redemption_code_detail)){
            $result = ['success' => false];
            return $result;
        }
        $playerBonusAmount = $redemption_code_detail['bonus'];
        if ($is_static_code) {
            $playerBonusAmount = $redemption_code_detail['current_bonus'];
        }
        $withdrawalRules = json_decode($redemption_code_detail['withdrawal_rules'], true);
        $depositAmount = 0;
        $withdrawBetAmtCondition = 0;
        $playerBonusAmountRelate = 0;
        switch ($withdrawalRules['withdrawRequirementBettingConditionOption']) {
            case Promorules::WITHDRAW_CONDITION_TYPE_FIXED_AMOUNT:
                $withdrawBetAmtCondition = $withdrawalRules['withdrawReqBetAmount'];
                break;
            case Promorules::WITHDRAW_CONDITION_TYPE_BETTING_TIMES:

                $non_promo_withdraw_setting = $this->operatorglobalsettings->getSettingDoubleValue('non_promo_withdraw_setting');
                $betTimes = $withdrawalRules['withdrawReqBonusTimes'];

                $withdrawBetAmtCondition = ($playerBonusAmount + $depositAmount) * $betTimes;

                if ($withdrawalRules['withdrawShouldMinusDeposit'] > 0) {
                    $withdrawBetAmtCondition = $withdrawBetAmtCondition - ($depositAmount * $non_promo_withdraw_setting);
                }

                break;
            case Promorules::WITHDRAW_CONDITION_TYPE_BETTING_TIMES_CHECK_WITH_MAX_BONUS:
                $betTimes = $withdrawalRules['withdrawReqBonusTimes'];
                $depositPercentage = $withdrawalRules['depositPercentage'] / 100;
                $depAmtLessThanOrEqualToMaxBonusDividedByBonusPercent = $depositAmount <= ($withdrawalRules['maxBonusAmount'] / $depositPercentage);
                $depAmtGreaterThanMaxBonusDividedByBonusPercent = $depositAmount > ($withdrawalRules['maxBonusAmount'] / $depositPercentage);

                if ($depAmtLessThanOrEqualToMaxBonusDividedByBonusPercent) {
                    $withdrawBetAmtCondition = ($depositAmount + $playerBonusAmount) * $betTimes;
                }

                if ($depAmtGreaterThanMaxBonusDividedByBonusPercent) {
                    $withdrawBetAmtCondition = ((($playerBonusAmount / $depositPercentage) + $withdrawalRules['maxBonusAmount']) * $betTimes)
                        + ($depositAmount - ($withdrawalRules['maxBonusAmount'] / $depositPercentage));
                }

                break;
            case Promorules::WITHDRAW_CONDITION_TYPE_BONUS_TIMES:

                $betTimes = $withdrawalRules['withdrawReqBonusTimes'];

                $withdrawBetAmtCondition = $playerBonusAmount * $betTimes;

                break;
        }
        $result = ['success' => true, 'withdrawal_condition_amount' => round($withdrawBetAmtCondition, 2)];
        return $result;
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

        $redemption_code = isset($extra_info['redemption_code']) ? $extra_info['redemption_code'] : '';


        $this->appendToDebugLog('runBonusConditionChecker check redemption_code', ['redemption_code' => $redemption_code]);

        $is_release = true;
        $request = $this->checkCustomizeBonusCondition($description, $extra_info, $errorMessageLang, $dry_run, $is_release);

        return $request;
    }

    private function checkCustomizeBonusCondition($description, &$extra_info, &$errorMessageLang, $dry_run = false, $is_release = false)
    {
        $success = false;
        $bonus_amount = 0;
        $is_static_code = true;
        $player_id = $this->playerId;
        $extra_info['is_static_code'] = $is_static_code;
        $redemption_code = $this->utils->safeGetArray($extra_info, 'redemption_code', false);
        $log_keyword = __METHOD__ . ' playerid-' . $this->playerId;
        $extra_info['reason'] = 'Redemption Code: ' . $redemption_code;
        if ($dry_run && $this->process_mock('redemption_code', $redemption_code)) {
            //use mock data
            $this->appendToDebugLog('use mock redemption_code', ['redemption_code' => $redemption_code]);
            $extra_info['redemption_code'] = $redemption_code;
        }
        $this->appendToDebugLog(__METHOD__ . "checking code [$redemption_code]", $extra_info);
        // $lock_it = false;
        // $lock_type = Utils::LOCK_ACTION_REDEMPTION_CODE;
        try {
            if (!$redemption_code) { // code not found
                $this->appendToDebugLog($log_keyword, ["message" => 'redemption_code not found.', 'redemption_code' => $redemption_code]);
                throw new \Exception(lang('redemptionCode.apply.codeIncorrect'));
            }
            $this->load->library('redemptioncode/static_redemption_code');
            $myCode = new $this->static_redemption_code();
            if (!$myCode->checkRedemptionCodeEnable()) {
                throw new \Exception(lang('redemptionCode.apply.codeIncorrect'));
            }

            $errorMessageLang = '';
            $myCode->init($redemption_code, $player_id, $extra_info);

            list($success, $errorMessageLang) = $myCode->setRedemptionCode($redemption_code, $extra_info, $dry_run);
            if (!$success) {
                $this->appendToDebugLog($log_keyword, ["message" => 'setRedemptionCode fail.', 'redemption_code' => $redemption_code]);
                throw new \Exception($errorMessageLang);
            }

            $redemption_code_detail = $myCode->redemption_code_detail;

            if (empty($redemption_code_detail)) { // code not found
                $this->appendToDebugLog($log_keyword, ["message" => 'redemption_code not found.', 'redemption_code' => $redemption_code]);
                throw new \Exception(lang('redemptionCode.apply.codeIncorrect'));
            }
            $this->appendToDebugLog($log_keyword, ['redemption_code_detail' => $redemption_code_detail]);

            if (!$myCode->isCategoryActivated()) { // inactive
                $this->appendToDebugLog($log_keyword, ["message" => 'category is inactive.', 'redemption_code' => $redemption_code]);
                throw new \Exception(lang('redemptionCode.apply.codeExpired'));
            }

            $apply_time = $this->utils->getNowForMysql();
            if ($myCode->validateRedemptionCodeExpiry($apply_time)) { // code expired

                $this->appendToDebugLog($log_keyword, ["message" => 'redemption_code expired.', 'redemption_code' => $redemption_code]);
                throw new \Exception(lang('redemptionCode.apply.codeExpired'));
            }

            // check apply limitation
            list($success, $errorMessageLang) = $myCode->validCodeTypeRepeatable();
            if (!$success) {
                $this->appendToDebugLog($log_keyword, ["message" => 'validCodeTypeRepeatable reach the limit.', 'redemption_code' => $redemption_code]);
                throw new \Exception($errorMessageLang);
            }

            list($success, $errorMessageLang) = $myCode->validBonusRules();
            if (!$success) {
                $this->appendToDebugLog($log_keyword, ["message" => 'validBonusRules reach the limit.', 'redemption_code' => $redemption_code]);
                throw new \Exception($errorMessageLang);
            }

            list($success, $errorMessageLang) = $myCode->validateApplyConditions($apply_time);
            if (!$success) {
                $this->appendToDebugLog($log_keyword, ["message" => 'validateApplyConditions fail.', 'errorMessageLang' => $errorMessageLang, 'redemption_code' => $redemption_code]);

                // throw new \Exception($errorMessageLang);
                throw new \Exception(lang("promo_rule.common.error"));
            }

            if ($myCode->validateRedemptionCodeStatus()) { // code disabled
                $this->appendToDebugLog($log_keyword, ["message" => 'redemption_code used.', 'redemption_code' => $redemption_code]);
                throw new \Exception(lang('redemptionCode.apply.codeUsed'));
            }

        } catch (\Exception $e) {
            $success = false;
            $this->appendToDebugLog($log_keyword, ["errorMsg" => $e->getMessage()]);
            $errorMessageLang = $e->getMessage();
        }

        if ($success) {
            $bonus_amount = $this->utils->safeGetArray($myCode->redemption_code_detail, 'current_bonus', 0); //$redemption_code_detail['current_bonus'];
            $errorMessageLang = sprintf(lang('redemptionCode.apply.applySuccess'), $bonus_amount); //"Redemption is successful, $bonus_amount BRL has been awarded.";
            $extra_info['force_custom_success_message'] = sprintf(lang('redemptionCode.apply.applySuccess'), $bonus_amount);
        }
        return ['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonus_amount];
    }

    public function getLast7Days($description, $require_consecutive_days)
    {
        $consecutive_days = $require_consecutive_days - 1;
        $fromDate = date("Y-m-d", strtotime(" -$consecutive_days days", strtotime('now')));
        $toDate = $this->utils->getTodayForMysql();

        $this->appendToDebugLog('getLast ' . $require_consecutive_days . ' Days', ['fromDate' => $fromDate, 'toDate' => $toDate]);

        return [$fromDate, $toDate];
    }
}