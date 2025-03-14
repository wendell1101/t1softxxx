<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/abstract_promo_rule.php';

/**
 *
 * OGP-25570
 * nolonger support
 *
condition:
{
    "class": "promo_rule_smash_redemption_code"
}
 */
class Promo_rule_smash_redemption_code extends Abstract_promo_rule
{
    public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount = null)
    {
        parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
    }

    public function getClassName()
    {
        return 'promo_rule_smash_redemption_code';
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
        return $this->returnUnimplemented();

        // $success = false;
        // $errorMessageLang = null;

        // $redemption_code = isset($extra_info['redemption_code']) ? $extra_info['redemption_code'] : '';

        // $this->appendToDebugLog('runBonusConditionChecker check redemption_code', ['redemption_code' => $redemption_code]);

        // $result = $this->checkCustomizeBonusCondition($extra_info, $description, $errorMessageLang, $dry_run);

        // if (array_key_exists('bonus_amount', $result)) {
        //     unset($result['bonus_amount']);
        // }

        // if (array_key_exists('deposit_amount', $result)) {
        //     unset($result['deposit_amount']);
        // }

        // return $result;
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
        // $redemption_code = isset($extra_info['redemption_code']) ? $extra_info['redemption_code'] : '';
        // $redemption_code_detail = $this->redemption_code_model->getDetailsByCode($redemption_code);
        // $withdrawalRules = json_decode($redemption_code_detail['withdrawal_rules'], true);
        // $playerBonusAmount = $redemption_code_detail['bonus'];
        // $depositAmount = 0;
        // $withdrawBetAmtCondition = 0;
        // $playerBonusAmountRelate = 0;
        // switch ($withdrawalRules['withdrawRequirementBettingConditionOption']) {
        //     case Promorules::WITHDRAW_CONDITION_TYPE_FIXED_AMOUNT:
        //         $withdrawBetAmtCondition = $withdrawalRules['withdrawReqBetAmount'];
        //         break;
        //     case Promorules::WITHDRAW_CONDITION_TYPE_BETTING_TIMES:

        //         $non_promo_withdraw_setting = $this->operatorglobalsettings->getSettingDoubleValue('non_promo_withdraw_setting');
        //         $betTimes = $withdrawalRules['withdrawReqBonusTimes'];

        //         $withdrawBetAmtCondition = ($playerBonusAmount + $depositAmount) * $betTimes;

        //         if ($withdrawalRules['withdrawShouldMinusDeposit'] > 0) {
        //             $withdrawBetAmtCondition = $withdrawBetAmtCondition - ($depositAmount * $non_promo_withdraw_setting);
        //         }

        //         break;
        //     case Promorules::WITHDRAW_CONDITION_TYPE_BETTING_TIMES_CHECK_WITH_MAX_BONUS:
        //         $betTimes = $withdrawalRules['withdrawReqBonusTimes'];
        //         $depositPercentage = $withdrawalRules['depositPercentage'] / 100;
        //         $depAmtLessThanOrEqualToMaxBonusDividedByBonusPercent = $depositAmount <= ($withdrawalRules['maxBonusAmount'] / $depositPercentage);
        //         $depAmtGreaterThanMaxBonusDividedByBonusPercent = $depositAmount > ($withdrawalRules['maxBonusAmount'] / $depositPercentage);

        //         if ($depAmtLessThanOrEqualToMaxBonusDividedByBonusPercent) {
        //             $withdrawBetAmtCondition = ($depositAmount + $playerBonusAmount) * $betTimes;
        //         }

        //         if ($depAmtGreaterThanMaxBonusDividedByBonusPercent) {
        //             $withdrawBetAmtCondition = ((($playerBonusAmount / $depositPercentage) + $withdrawalRules['maxBonusAmount']) * $betTimes)
        //                 + ($depositAmount - ($withdrawalRules['maxBonusAmount'] / $depositPercentage));
        //         }

        //         break;
        //     case Promorules::WITHDRAW_CONDITION_TYPE_BONUS_TIMES:

        //         $betTimes = $withdrawalRules['withdrawReqBonusTimes'];

        //         $withdrawBetAmtCondition = $playerBonusAmount * $betTimes;

        //         break;
        // }
        // $result = ['success' => true, 'withdrawal_condition_amount' => round($withdrawBetAmtCondition, 2)];
        // return $result;
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
        return $this->returnUnimplemented();
        // $success = false;
        // $errorMessageLang = null;

        // $redemption_code = isset($extra_info['redemption_code']) ? $extra_info['redemption_code'] : '';


        // $this->appendToDebugLog('runBonusConditionChecker check redemption_code', ['redemption_code' => $redemption_code]);


        // $request = $this->checkCustomizeBonusCondition($extra_info, $description, $errorMessageLang, $dry_run);

        // return $request;
    }

    private function checkCustomizeBonusCondition(&$extra_info, $description, &$errorMessageLang, $dry_run = false)
    {
        return $result = ['success' => false, 'message' => lang('disabled')];
        // $success = true;
        // $bonus_amount = 0;

        // if ($dry_run && $this->process_mock('redemption_code', $redemption_code)) {
        //     //use mock data
        //     $this->appendToDebugLog('use mock redemption_code', ['redemption_code' => $redemption_code]);
        //     $extra_info['redemption_code'] = $redemption_code;
        // }
        // $redemption_code = isset($extra_info['redemption_code']) ? $extra_info['redemption_code'] : '';
        // if (empty($redemption_code)) { // code not found
        //     $success = false;

        //     $this->appendToDebugLog('checkCustomizeBonusCondition: redemption_code not found.', ['player_id' => $this->playerId, 'redemption_code' => $redemption_code]);
        //     $errorMessageLang = lang('redemptionCode.apply.codeIncorrect'); //lang('The current redemption code is incorrect, please use a valid redemption code.');
        //     return $result = ['success' => $success, 'message' => $errorMessageLang];
        // }

        // $this->load->model(['redemption_code_model']);

        // $redemption_code_detail = $this->redemption_code_model->getDetailsByCode($redemption_code);
        // if (empty($redemption_code_detail)) { // code not found
        //     $success = false;

        //     $this->appendToDebugLog('checkCustomizeBonusCondition: redemption_code not found.', ['player_id' => $this->playerId, 'redemption_code' => $redemption_code]);
        //     $errorMessageLang = lang('redemptionCode.apply.codeIncorrect'); //lang('The current redemption code is incorrect, please use a valid redemption code.');
        //     return $result = ['success' => $success, 'message' => $errorMessageLang, 'redemption_code_detail' => $redemption_code_detail];
        // }

        // // end check apply limitation
        // $code_status = $redemption_code_detail['code_status'];
        // $category_status = $redemption_code_detail['category_status'];
        // if ($code_status != redemption_code_model::CODE_STATUS_UNUSED || !empty($redemption_code_detail['player_id']) || !empty($redemption_code_detail['promo_cms_id'])) { // code used
        //     $success = false;

        //     $this->appendToDebugLog('checkCustomizeBonusCondition: redemption_code used.', ['player_id' => $this->playerId, 'redemption_code' => $redemption_code]);
        //     $errorMessageLang = lang('redemptionCode.apply.codeUsed'); //lang('Current redemption code has been used, please use a valid redemption code.');
        //     return $result = ['success' => $success, 'message' => $errorMessageLang];
        // }

        // if ($category_status != redemption_code_model::CATEGORY_STATUS_ACTIVATED) { // code not found
        //     $success = false;

        //     $this->appendToDebugLog('checkCustomizeBonusCondition: redemption_code not found.', ['player_id' => $this->playerId, 'redemption_code' => $redemption_code]);
        //     $errorMessageLang = lang('redemptionCode.apply.codeExpired'); //lang('Current redemption code has expired, please use a valid redemption code.');
        //     return $result = ['success' => $success, 'message' => $errorMessageLang, 'redemption_code_detail' => $redemption_code_detail];
        // }

        // $apply_time = $this->utils->getNowForMysql();
        // $expires_at = $redemption_code_detail['expires_at'];
        // $valid_forever = $redemption_code_detail['valid_forever'];
        // if ($apply_time > $expires_at && $valid_forever != 1) { // code expired
        //     $success = false;

        //     $this->appendToDebugLog('checkCustomizeBonusCondition: redemption_code expired.', ['player_id' => $this->playerId, 'redemption_code' => $redemption_code]);
        //     $errorMessageLang = lang('redemptionCode.apply.codeExpired'); //lang('Current redemption code has expired, please use a valid redemption code.');
        //     return $result = ['success' => $success, 'message' => $errorMessageLang];
        // }
        // // check apply limitation
        // list($success, $errorMessageLang) = $this->validCodeTypeRepeatable($this->playerId, $redemption_code_detail, $extra_info);
        // if (!$success) {
        //     $this->appendToDebugLog('checkCustomizeBonusCondition: validCodeTypeRepeatable reach the limit.', ['player_id' => $this->playerId, 'redemption_code' => $redemption_code]);
        //     return $result = ['success' => $success, 'message' => $errorMessageLang, 'redemption_code_detail' => $redemption_code_detail];
        // }

        // $bonus_amount = $redemption_code_detail['bonus'];
        // $errorMessageLang =  sprintf(lang('redemptionCode.apply.applySuccess'), $bonus_amount); //"Redemption is successful, $bonus_amount BRL has been awarded.";
        // $extra_info['force_custom_success_message'] = sprintf(lang('redemptionCode.apply.applySuccess'), $bonus_amount);

        // return $result = ['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonus_amount];
    }

    public function isCodeTypeRepeatable($typerule)
    {
        return $typerule['bonusApplicationLimitRule'] == Promorules::BONUS_APPLICATION_LIMIT_RULE_NO_LIMIT ||
            ($typerule['bonusApplicationLimitRule'] == Promorules::BONUS_APPLICATION_LIMIT_RULE_LIMIT_COUNT &&
                $typerule['bonusApplicationLimitRuleCnt'] > 1);
    }

    public function validCodeTypeRepeatable($playerId, $redemption_code_detail, &$extra_info = null)
    {
        $withdraw_condition = json_decode($redemption_code_detail['withdrawal_rules'], true);
        if (array_key_exists('bonusApplicationLimit', $withdraw_condition)) {
            $bonusApplicationLimitDefineds = [
                Promorules::BONUS_APPLICATION_LIMIT_DATE_TYPE_NONE => lang('redemptionCode.apply.reachLimit.None'),
                Promorules::BONUS_APPLICATION_LIMIT_DATE_TYPE_DAILY => lang('redemptionCode.apply.reachLimit.Daily'),
                Promorules::BONUS_APPLICATION_LIMIT_DATE_TYPE_WEEKLY => lang('redemptionCode.apply.reachLimit.Weekly'),
                Promorules::BONUS_APPLICATION_LIMIT_DATE_TYPE_MONTHLY => lang('redemptionCode.apply.reachLimit.Monthly'),
                Promorules::BONUS_APPLICATION_LIMIT_DATE_TYPE_YEARLY => lang('redemptionCode.apply.reachLimit.Yearly'),
            ];
            $this->load->model(array('player_model', 'player_promo', 'redemption_code_model'));
            $this->utils->debug_log('redemption_code_detail', $redemption_code_detail);
            $typerule = [];
            $success = false;
            $message = 'error.default.message';
            $bonusApplicationLimit = $withdraw_condition['bonusApplicationLimit'];
            $typerule['bonusApplicationLimitRule'] = $bonusApplicationLimit['bonusReleaseTypeOptionByNonSuccessionLimitOption'];
            $typerule['bonusApplicationLimitRuleCnt'] = $bonusApplicationLimit['limitCnt'];
            $typerule['bonusApplicationLimitDateType'] = $bonusApplicationLimit['bonusApplicationLimitDateType'];

            $repeatable = $this->isCodeTypeRepeatable($typerule);

            list($fromDatetime, $toDatetime) = $this->utils->getLimitDateRangeForPromo($typerule['bonusApplicationLimitDateType']);
            $this->utils->debug_log('getLimitDateRangeForPromo', 'fromDatetime', $fromDatetime, 'toDatetime', $toDatetime, 'bonusApplicationLimitDateType', $typerule['bonusApplicationLimitDateType']);

            $getPlayerDuplicateTypeCount = $this->redemption_code_model->getPlayerDuplicateType(
                $playerId,
                $redemption_code_detail['category_id'],
                $fromDatetime,
                $toDatetime
            );
            $this->utils->debug_log('is getPlayerDuplicateTypeCount', $getPlayerDuplicateTypeCount, $fromDatetime, $toDatetime);
            $noDupType = $getPlayerDuplicateTypeCount <= 0;
            $this->utils->debug_log('noDupPromo: ', array(
                'repeatable' => $repeatable,
                'playerDuplicatePromoCount' => $getPlayerDuplicateTypeCount,
                'noDupPromo' => $noDupType,
            ));
            if (!$repeatable) {
                if ($noDupType) {
                    $message = null;
                    $success = true;
                } else {
                    $message = sprintf(lang('redemptionCode.apply.reachLimit'), $bonusApplicationLimitDefineds[$typerule['bonusApplicationLimitDateType']]);
                    $success = false;
                }
            } else {

                if ($typerule['bonusApplicationLimitRule'] == Promorules::BONUS_APPLICATION_LIMIT_RULE_NO_LIMIT) {
                    //no limit
                    $message = null;
                    $success = true;
                    //check how much bonus player can get
                    // list($success, $message) = $this->processNonDepositPromo($playerId, $promorule, $promoCmsSettingId);
                } else {
                    //with limit
                    if ($getPlayerDuplicateTypeCount < $typerule['bonusApplicationLimitRuleCnt']) {
                        //if do not exceeds limit
                        $message = null;
                        $success = true;
                        //check how much bonus player can get
                        // list($success, $message) = $this->processNonDepositPromo($playerId, $promorule, $promoCmsSettingId);
                    } else {
                        //if exceeds limit
                        //You cannot join this promo anymore because you exceeds join promotion limit.
                        $message = sprintf(lang('redemptionCode.apply.reachLimit'), $bonusApplicationLimitDefineds[$typerule['bonusApplicationLimitDateType']]);
                        $success = false;
                    }
                }
            }
        } else {
            $message = null;
            $success = true;
        }
        return array($success, $message);
    }
}
