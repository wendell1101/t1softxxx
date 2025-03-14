<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/abstract_promo_rule.php';

/**
 *
 * OGP-25570
 * @property Redemption_code_model $redemption_code_model
 * @property Static_redemption_code_model $static_redemption_code_model
 *
 *
condition:
{
    "class": "promo_rule_common_redemption_code"
}
 */
class Promo_rule_common_redemption_code extends Abstract_promo_rule
{
    public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount = null)
    {
        parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
    }

    public function getClassName()
    {
        return 'promo_rule_common_redemption_code';
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
        $this->load->model(['redemption_code_model', 'static_redemption_code_model']);
        $redemption_code = isset($extra_info['redemption_code']) ? $extra_info['redemption_code'] : '';
        // $is_static_code = (strlen($redemption_code) > 10);
        $is_static_code = isset($extra_info['is_static_code']) ? $extra_info['is_static_code'] : false;
        // $redemption_code_detail = $is_static_code ? $this->static_redemption_code_model->getDetailsByCode($redemption_code) : $this->redemption_code_model->getDetailsByCode($redemption_code);
        if ($is_static_code) {
            $redemption_code_detail = $this->static_redemption_code_model->getPlayerPendingCode($redemption_code, $this->playerId, null);
        } else {
            $redemption_code_detail = $this->redemption_code_model->getPlayerPendingCode($redemption_code, $this->playerId, null);
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

        $request = $this->checkCustomizeBonusCondition($description, $extra_info, $errorMessageLang, $dry_run);

        return $request;
    }

    private function checkCustomizeBonusCondition($description, &$extra_info, &$errorMessageLang, $dry_run = false)
    {
        $success = true;
        $bonus_amount = 0;
        $is_static_code = false;

        if ($dry_run && $this->process_mock('redemption_code', $redemption_code)) {
            //use mock data
            $this->appendToDebugLog('use mock redemption_code', ['redemption_code' => $redemption_code]);
            $extra_info['redemption_code'] = $redemption_code;
        }
        $redemption_code = isset($extra_info['redemption_code']) ? $extra_info['redemption_code'] : '';
        if (empty($redemption_code)) { // code not found
            $success = false;

            $this->appendToDebugLog('checkCustomizeBonusCondition: redemption_code not found.', ['player_id' => $this->playerId, 'redemption_code' => $redemption_code]);
            $errorMessageLang = lang('redemptionCode.apply.codeIncorrect'); //lang('The current redemption code is incorrect, please use a valid redemption code.');
            return $result = ['success' => $success, 'message' => $errorMessageLang];
        }
        $extra_info['reason'] = 'Redemption Code: ' . $redemption_code;
        $this->load->model(['redemption_code_model', 'static_redemption_code_model']);
        // $is_static_code = (strlen($redemption_code) > 10);
        $is_static_code = isset($extra_info['is_static_code']) ? $extra_info['is_static_code'] : false;

        if ($is_static_code && !$this->static_redemption_code_model->checkRedemptionCodeEnable()) {
            return $result = ['success' => false, 'message' => lang('redemptionCode.apply.codeIncorrect')];
        }

        if (!$is_static_code && !$this->redemption_code_model->checkRedemptionCodeEnable()) {
            return $result = ['success' => false, 'message' => lang('redemptionCode.apply.codeIncorrect')];
        }
        $redemption_code_detail = [];
        if ($is_static_code) {
            $redemption_code_detail = $this->static_redemption_code_model->getPlayerPendingCode($redemption_code, $this->playerId, null);

        } else {
            $redemption_code_detail = $this->redemption_code_model->getPlayerPendingCode($redemption_code, $this->playerId, null);
        }

        $this->appendToDebugLog('checkCustomizeBonusCondition: code type and detail', ['player_id' => $this->playerId, 'redemption_code_detail' => $redemption_code_detail, 'is_static_code' => $is_static_code]);

        if (empty($redemption_code_detail)) { // code not found
            $success = false;

            $this->appendToDebugLog('checkCustomizeBonusCondition: redemption_code not found.', ['player_id' => $this->playerId, 'redemption_code' => $redemption_code]);
            $errorMessageLang = lang('redemptionCode.apply.codeIncorrect'); //lang('The current redemption code is incorrect, please use a valid redemption code.');
            return $result = ['success' => $success, 'message' => $errorMessageLang, 'redemption_code_detail' => $redemption_code_detail];
        }

        // end check apply limitation
        $code_status = $redemption_code_detail['code_status'];
        $category_status = $redemption_code_detail['category_status'];

        // if ($code_status != redemption_code_model::CODE_STATUS_UNUSED || !empty($redemption_code_detail['player_id']) || !empty($redemption_code_detail['promo_cms_id'])) { // code used
        if ($code_status != redemption_code_model::CODE_STATUS_PENDING || !empty($redemption_code_detail['promo_cms_id'])) { // code used        
            $success = false;
            $this->appendToDebugLog('checkCustomizeBonusCondition: redemption_code used.', ['player_id' => $this->playerId, 'redemption_code' => $redemption_code]);
            $errorMessageLang = lang('redemptionCode.apply.codeUsed'); //lang('Current redemption code has been used, please use a valid redemption code.');
            return $result = ['success' => $success, 'message' => $errorMessageLang];
        }

        if ($category_status != redemption_code_model::CATEGORY_STATUS_ACTIVATED) { // inactive
            $success = false;

            $this->appendToDebugLog('checkCustomizeBonusCondition: category is inactive.', ['player_id' => $this->playerId, 'redemption_code' => $redemption_code]);
            $errorMessageLang = lang('redemptionCode.apply.codeExpired'); //lang('Current redemption code has expired, please use a valid redemption code.');
            return $result = ['success' => $success, 'message' => $errorMessageLang, 'redemption_code_detail' => $redemption_code_detail];
        }

        $apply_time = $this->utils->getNowForMysql();
        $expires_at = $redemption_code_detail['expires_at'];
        $valid_forever = $redemption_code_detail['valid_forever'];
        if ($apply_time > $expires_at && $valid_forever != 1) { // code expired
            $success = false;

            $this->appendToDebugLog('checkCustomizeBonusCondition: redemption_code expired.', ['player_id' => $this->playerId, 'redemption_code' => $redemption_code]);
            $errorMessageLang = lang('redemptionCode.apply.codeExpired'); //lang('Current redemption code has expired, please use a valid redemption code.');
            return $result = ['success' => $success, 'message' => $errorMessageLang];
        }
        // check apply limitation
        list($success, $errorMessageLang) = $this->validCodeTypeRepeatable($this->playerId, $redemption_code_detail, $extra_info, $is_static_code);
        if (!$success) {
            $this->appendToDebugLog('checkCustomizeBonusCondition: validCodeTypeRepeatable reach the limit.', ['player_id' => $this->playerId, 'redemption_code' => $redemption_code]);
            return $result = ['success' => $success, 'message' => $errorMessageLang, 'redemption_code_detail' => $redemption_code_detail];
        }

        $bonus_amount = $redemption_code_detail['bonus'];
        if ($is_static_code) {
            list($success, $errorMessageLang) = $this->validBonusRules($this->playerId, $redemption_code_detail, $extra_info);
            if (!$success) {
                $this->appendToDebugLog('checkCustomizeBonusCondition: validBonusRules reach the limit.', ['player_id' => $this->playerId, 'redemption_code' => $redemption_code]);
                return $result = ['success' => $success, 'message' => $errorMessageLang, 'redemption_code_detail' => $redemption_code_detail];
            }
            list($success, $errorMessageLang) = $this->validateApplyConditions($redemption_code_detail, $extraInfo, $apply_time);
            if (!$success) {
                $this->appendToDebugLog('checkCustomizeBonusCondition: validateApplyConditions', ["message" => 'validateApplyConditions fail.', 'errorMessageLang' => $errorMessageLang, 'redemption_code' => $redemption_code]);

                return $result = ['success' => $success, 'message' => lang("promo_rule.common.error"), 'redemption_code_detail' => $redemption_code_detail];
            }

            $extra_info['is_static_code'] = $is_static_code;
            $bonus_amount = $redemption_code_detail['current_bonus'];
        }

        $errorMessageLang = sprintf(lang('redemptionCode.apply.applySuccess'), $bonus_amount); //"Redemption is successful, $bonus_amount BRL has been awarded.";
        $extra_info['force_custom_success_message'] = sprintf(lang('redemptionCode.apply.applySuccess'), $bonus_amount);

        return $result = ['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonus_amount];
    }

    public function isCodeTypeRepeatable($typerule)
    {
        return $typerule['bonusApplicationLimitRule'] == Promorules::BONUS_APPLICATION_LIMIT_RULE_NO_LIMIT ||
            ($typerule['bonusApplicationLimitRule'] == Promorules::BONUS_APPLICATION_LIMIT_RULE_LIMIT_COUNT &&
                $typerule['bonusApplicationLimitRuleCnt'] > 1);
    }

    public function validCodeTypeRepeatable($playerId, $redemption_code_detail, &$extra_info = null, $is_static_code = false)
    {
        $withdraw_condition = json_decode($redemption_code_detail['withdrawal_rules'], true);
        $category_id = $redemption_code_detail['category_id'];
        if (array_key_exists('bonusApplicationLimit', $withdraw_condition)) {
            $bonusApplicationLimitDefineds = [
                Promorules::BONUS_APPLICATION_LIMIT_DATE_TYPE_NONE => lang('redemptionCode.apply.reachLimit.None'),
                Promorules::BONUS_APPLICATION_LIMIT_DATE_TYPE_DAILY => lang('redemptionCode.apply.reachLimit.Daily'),
                Promorules::BONUS_APPLICATION_LIMIT_DATE_TYPE_WEEKLY => lang('redemptionCode.apply.reachLimit.Weekly'),
                Promorules::BONUS_APPLICATION_LIMIT_DATE_TYPE_MONTHLY => lang('redemptionCode.apply.reachLimit.Monthly'),
                Promorules::BONUS_APPLICATION_LIMIT_DATE_TYPE_YEARLY => lang('redemptionCode.apply.reachLimit.Yearly'),
            ];
            $this->load->model(array('player_model', 'player_promo', 'redemption_code_model', 'static_redemption_code_model'));
            $this->utils->debug_log('redemption_code_detail', $redemption_code_detail, 'is_static_code', $is_static_code);
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

            $getPlayerDuplicateTypeCount = $is_static_code ?
                $this->static_redemption_code_model->getPlayerDuplicateType(
                    $playerId,
                    $category_id,
                    $fromDatetime,
                    $toDatetime
                ) :
                $this->redemption_code_model->getPlayerDuplicateType(
                    $playerId,
                    $category_id,
                    $fromDatetime,
                    $toDatetime
                );

            $this->utils->debug_log('is getPlayerDuplicateTypeCount', $getPlayerDuplicateTypeCount, $fromDatetime, $toDatetime);
            $noDupType = $getPlayerDuplicateTypeCount <= 0;
            $this->utils->debug_log('noDupPromo: ', array(
                'repeatable' => $repeatable,
                'playerDuplicatePromoCount' => $getPlayerDuplicateTypeCount,
                'noDupPromo' => $noDupType,
            )
            );
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

    public function validBonusRules($playerId, $redemptionCodeDetail, &$extraInfo = null)
    {
        if (!is_array($redemptionCodeDetail) || !isset($redemptionCodeDetail['total_redeemable_count']) || !isset($redemptionCodeDetail['category_id']) || !isset($redemptionCodeDetail['bonus_rules'])) {
            return array(false, lang('redemptionCode.invalid.parameters'));
        }

        $this->load->model('static_redemption_code_model');

        $redeemableCountLimit = $redemptionCodeDetail['total_redeemable_count'];
        $categoryId = $redemptionCodeDetail['category_id'];
        $bonusRules = json_decode($redemptionCodeDetail['bonus_rules'], true);
        // $countUsedCode = $this->static_redemption_code_model->countUsedCode($categoryId);

        // if ($countUsedCode >= $redeemableCountLimit) {
        //     return array(false, lang('redemptionCode.apply.useLimit'));
        // }

        $countUnusedCode = $this->static_redemption_code_model->countCode($categoryId, Static_redemption_code_model::CODE_STATUS_UNUSED);
        $countPendingCode = $this->static_redemption_code_model->countCode($categoryId, Static_redemption_code_model::CODE_STATUS_PENDING);
        $countCode = $countUnusedCode + $countPendingCode;
        if ($countCode <= 0) {
            throw new Exception(lang('redemptionCode.apply.useLimit'));
        }

        if (isset($bonusRules['bonusReleaseTypeOption']) && $bonusRules['bonusReleaseTypeOption'] == Promorules::BONUS_RELEASE_RULE_CUSTOM) {
            $bonusCap = isset($bonusRules['bonusCap']) ? $bonusRules['bonusCap'] : 0;
            $totalBonus = $this->static_redemption_code_model->sumUsedCodeBonus($categoryId);

            if ($totalBonus >= $bonusCap) {
                return array(false, lang('redemptionCode.apply.bonusLimit'));
            }
        }
        return array(true, null);
    }

    protected function getLastDays($base_date, $require_consecutive_days, $disableOffset = false){
        $consecutive_days = $require_consecutive_days;
        $fromDate = date("Y-m-d", strtotime( " -$consecutive_days days", strtotime($base_date)));
        if($disableOffset){
            $toDate = date("Y-m-d", strtotime($base_date));
        } else {
            $toDate = date("Y-m-d", strtotime( " -1 days", strtotime($base_date)));
        }
        return [$fromDate, $toDate];
    }

    public function validateApplyConditions($redemptionCodeDetail, &$extraInfo = null, $base_date = null){
        $static_settings = $this->utils->getConfig('static_redemption_code_setting');
        $enable_custom_deposit_condictions = $this->utils->safeGetArray($static_settings, 'deposit_condictions', false);
        $enable_filled_cpf = $this->utils->safeGetArray($static_settings, 'filled_cpf', false);
        $success = false;
        $message = '';
        $this->load->model('player_model');

        if($enable_filled_cpf === true){
            $filled_cpf = $this->player_model->isFilledCPFnumber($this->playerId);
            if(!$filled_cpf){
                $message = lang('redemptionCode.apply.filledCPF');
                return array($success, $message);
            }
        }

        if($enable_custom_deposit_condictions === true){
            // $redemptionCodeDetail = $this->redemption_code_detail;
            $bonusRules = json_decode($redemptionCodeDetail['bonus_rules'], true);
    
            // "{"bonus": "3", "bonusCap": 0, "pastDayDepositDays": "99", "enablePastDayDeposit": true, "enableSameDayDeposit": true, "pastDayDepositAmount": "88", "sameDayDepositAmount": "999", "bonusReleaseTypeOption": "0", "nonfixedBonusMaxAmount": null, "nonfixedBonusMinAmount": null}"
    
            $this->load->model('transactions');
            $todayTotalDeposit = 0;
            if($bonusRules['enableSameDayDeposit'] == 1){
                $date_only = isset($base_date) ? date('Y-m-d', strtotime($base_date)) : $this->utils->getTodayForMysql();
                $todayTotalDeposit = $this->transactions->getTotalDepositsToday($this->playerId, $date_only);
                $this->utils->debug_log("{$this->playerId}-todayTotalDeposit", $todayTotalDeposit);
                if($todayTotalDeposit < $bonusRules['sameDayDepositAmount']){
                    $message = sprintf(lang('redemptionCode.apply.depositAmountNotMet'), $bonusRules['sameDayDepositAmount']);
                    return array($success, $message);
                }
                $this->utils->debug_log("{$this->playerId}-todayTotalDeposit", [
                    "base_date" => $base_date,
                    "date_only" => $date_only,
                    "todayTotalDeposit" => $todayTotalDeposit,
                ]);
            }
            $consecutive_deposit = [];
            $countAccumulated = 0;
            if($bonusRules['enablePastDayDeposit'] == 1){
                $require_consecutive_days = $bonusRules['pastDayDepositDays'];
                $minDeposit = $bonusRules['pastDayDepositAmount'];
                if($require_consecutive_days > 0) {
                    list($fromDate, $toDate) = $this->getLastDays($base_date, $require_consecutive_days);
                    $consecutive_deposit = $this->transactions->getConsecutiveDepositAndDateByDateTime($this->playerId, $fromDate, $toDate, $minDeposit);
                    if(empty($consecutive_deposit) || !is_array($consecutive_deposit)){
                        $message = sprintf(lang('redemptionCode.apply.depositDaysNotmet'), $require_consecutive_days, $minDeposit);
                        return array($success, $message);
                    }
                    $countAccumulated = count($consecutive_deposit);
                    if($countAccumulated < $require_consecutive_days){
                        $message = sprintf(lang('redemptionCode.apply.depositDaysNotmet'), $require_consecutive_days, $minDeposit);
                        return array($success, $message);
                    }
                    $this->utils->debug_log("{$this->playerId}-todayTotalDeposit", [
                        "base_date" => $base_date,
                        "fromDate" => $fromDate, 
                        "toDate" => $toDate,
                        "consecutiveDeposit" => $consecutive_deposit,
                        "countAccumulated" => $countAccumulated
                    ]);
                }
            }
            if($bonusRules['enablePastDaysTotalDeposit'] == 1){
                $require_consecutive_days = $bonusRules['pastDaysTotalDeposit'];
                $minDeposit = $bonusRules['pastDaysTotalDepositAmount'];
                if($require_consecutive_days > 0) {
                    list($fromDate, $toDate) = $this->getLastDays($base_date, $require_consecutive_days);
                    $dateTimeFrom = $fromDate . ' ' . Utils::FIRST_TIME;
                    $dateTimeTo = $toDate . ' ' . Utils::LAST_TIME;
                    $totalDeposit = $this->transactions->totalDepositByPlayerAndDateTime($this->playerId, $dateTimeFrom, $dateTimeTo);

                    $this->utils->debug_log("{$this->playerId}-pastDaysTotalDeposit", [
                        "base_date" => $base_date,
                        "fromDate" => $dateTimeFrom, 
                        "toDate" => $dateTimeTo,
                        "require_consecutive_days" => $require_consecutive_days,
                        "minDeposit" => $minDeposit,
                        "totalDeposit" => $totalDeposit
                    ]);
                    $totalDeposit = empty($totalDeposit) ? 0 : $totalDeposit;
                    if($totalDeposit < $minDeposit){
                        $message = sprintf(lang('redemptionCode.pastDaysTotalDeposit.amountNotMet'));
                        return array($success, $message);
                    }
                }
            }
        }

        $enable_custom_condiction_under_affiliate = $this->utils->safeGetArray($static_settings, 'under_affiliate', false);
        $enable_custom_condiction_under_player = $this->utils->safeGetArray($static_settings, 'under_player', false);
        $enable_custom_condiction_player_level = $this->utils->safeGetArray($static_settings, 'player_level', false);

        //defautl true
        $check_under_affiliate = ($enable_custom_condiction_under_affiliate === true) && isset($bonusRules['allowedAffiliates']);
        $check_under_player = ($enable_custom_condiction_under_player === true) && isset($bonusRules['allowedPlayers']);
        $check_under_player_level = ($enable_custom_condiction_player_level === true) && isset($bonusRules['allowedPlayerLevels']);
        $is_under_affiliate = false;
        $is_under_player = false;
        $is_under_player_level = false;

        if($check_under_player_level){
            if(isset($bonusRules['allowedPlayerLevels'])){
                $allowedPlayerLevels = $bonusRules['allowedPlayerLevels'];
                $player = $this->player_model->getPlayerById($this->playerId);
                $levelId = $player->levelId;
                if(is_array($allowedPlayerLevels) && !in_array($levelId, $allowedPlayerLevels)){
                    $message = sprintf(lang('redemptionCode.apply.notAllowedPlayerLevels'));
                    $is_under_player_level = false;
                }else{
                    $is_under_player_level = true;
                }
                $this->utils->debug_log("{$this->playerId}-allowedPlayerLevels", [
                    "allowedPlayerLevels" => $allowedPlayerLevels,
                    "levelId" => $levelId,
                    "is_under_player_level" => $is_under_player_level,
                ]);
            }
        }
        if($check_under_affiliate){
            if(isset($bonusRules['allowedAffiliates'])){
                $allowedAffiliates = $bonusRules['allowedAffiliates'];
                $affiliateId = $this->player_model->getAffiliateOfPlayer($this->playerId, 'affiliateId');
                if(is_array($allowedAffiliates) && !in_array($affiliateId, $allowedAffiliates)){
                    $message = sprintf(lang('redemptionCode.apply.notAllowedAffiliates'));
                    // return array($success, $message);
                    $is_under_affiliate = false;
                } else {
                    $is_under_affiliate = true;
                }
                $this->utils->debug_log("{$this->playerId}-allowedAffiliates", [
                    "allowedAffiliates" => $allowedAffiliates,
                    "affiliateId" => $affiliateId,
                ]);
            }
        }
        $this->utils->debug_log("{$this->playerId}-check_under_player", [
            "check_under_affiliate" => $check_under_affiliate,
            "check_under_player" => $check_under_player,
            "is_under_affiliate" => $is_under_affiliate,
        ]);
        if($check_under_player){
            if(empty($is_under_affiliate)){
                if(isset($bonusRules['allowedPlayers'])){
                    $allowedPlayers = $bonusRules['allowedPlayers'];
                    // $allowedPlayers = array_values($_allowedPlayers);
                    $refereePlayerId = $this->player_model->getRefereePlayerId($this->playerId);

                    if(is_array($allowedPlayers) && !in_array($refereePlayerId, $allowedPlayers)){
                        $message = sprintf(lang('redemptionCode.apply.notAllowedPlayers'));
                        // return array($success, $message);
                        $is_under_player = false;
                    } else {
                        $is_under_player = true;
                    }
                    $this->utils->debug_log("{$this->playerId}-allowedPlayers", [
                        "allowedPlayers" => $allowedPlayers,
                        "refereePlayerId" => $refereePlayerId,
                        "is_under_player" => $is_under_player,
                    ]);
                }
            }
        }
        switch(1) {
            case $check_under_affiliate && $check_under_player && $check_under_player_level:
                $success = $is_under_affiliate || $is_under_player;
                break;
            case $check_under_affiliate:
                $success = $is_under_affiliate;
                break;
            case $check_under_player:
                $success = $is_under_player;
                break;
            case $check_under_player_level:
                $success = $is_under_player_level;
                break;
            default:
                $success = true;
                break;
        }
        if(!$success){
            $this->utils->debug_log("{$this->playerId}-check_under_player faild", [
                "check_under_affiliate" => $check_under_affiliate,
                "check_under_player" => $check_under_player,
                "check_under_player_level" => $check_under_player_level,
                "is_under_affiliate" => $is_under_affiliate,
                "is_under_player" => $is_under_player,
                "is_under_player_level" => $is_under_player_level,
            ]);
            return array($success, $message);
        }
        return array(true, $message);
    }
}