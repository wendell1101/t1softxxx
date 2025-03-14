<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/abstract_redemption_code.php';

/**
 * 
 *redemption code library class
 * @property Static_redemption_code_model $static_redemption_code_model
 * @property Player_model $player_model
 * @property Transactions $transactions
 */
class Static_redemption_code extends Abstract_redemption_code
{
    const IS_STATIC_CODE = true;
    public $playerId;
    public $redemption_code_detail;
	function __construct()
	{
        parent::__construct();
    }

    public function init($redemption_code, $playerId, &$extra_info) {
        $this->redemption_code = $redemption_code;
        $this->playerId = $playerId;
    }
    public function setRedemptionCode($redemption_code, &$extra_info, $dry_run = false)
    {
        // $this->redemption_code = $redemption_code;
        try {
            if($this->static_redemption_code_model->checkCategoryRedemptionCodeExist($this->redemption_code)){
                $codeDetail = $this->getAssignedCode($this->playerId);
                if($dry_run){
                    $codeDetail = $this->setCodeDetails();
                }
                if(empty($codeDetail)){
                    throw new Exception(lang('redemptionCode.apply.useLimit'));
                }
            } else {
                throw new Exception(lang('redemptionCode.apply.codeIncorrect'));
            }
        } catch (\Throwable $th) {
            $this->utils->debug_log('setRedemptionCode', $th->getMessage());
            return array(false, $th->getMessage());
        }
        return array(true, null);
        // return array(false, 'test fail');
    }

    public function setCodeDetails(){
        $redemption_code_detail = $this->static_redemption_code_model->getDetailsByCode($this->redemption_code);
        if(empty($redemption_code_detail)){
            return null;
        }
        $this->redemption_code_detail = $redemption_code_detail;
        return $redemption_code_detail;
    }

    public function getAssignedCode($playerId, $current_code_id = null){
        $redemption_code_detail = $this->static_redemption_code_model->getPlayerPendingCode($this->redemption_code, $playerId, $current_code_id);
        if(empty($redemption_code_detail)){
            return null;
        }
        $this->redemption_code_detail = $redemption_code_detail;
        return $redemption_code_detail;
    }

    public function releaseLockedCode($redemption_code, &$extra_info){
        $result = false;
        if(!empty($this->redemption_code_detail['id'])){
            if($this->redemption_code_detail['status'] == Static_redemption_code_model::CODE_STATUS_PENDING){
                $result = $this->static_redemption_code_model->updateItem($this->redemption_code_detail['id'], array('player_id' => null, 'status' => Static_redemption_code_model::CODE_STATUS_UNUSED));
            }
            // $result = $this->static_redemption_code_model->updateItem($this->redemption_code_detail['id'], array('player_id' => null, 'status' => Static_redemption_code_model::CODE_STATUS_UNUSED));
        }
        // if(!$result){
        //     throw new Exception(lang('redemptionCode.apply.useLimit'));
        // }
        return empty($result) ? false : true;
    }

    public function setPlayerId($playerId){
        $this->playerId = $playerId;
    }

    public function getCodeDetails(){
        return $this->redemption_code_detail;
    }

    public function checkRedemptionCodeEnable() {
        return $this->utils->getConfig('enable_static_redemption_code_system') === true;
    }

    public function isCategoryActivated() {
        $category_status = $this->redemption_code_detail['category_status'];
        return $category_status == Static_redemption_code_model::CATEGORY_STATUS_ACTIVATED;
    }
    public function isCodeTypeRepeatable($typerule)
    {
        return $typerule['bonusApplicationLimitRule'] == Promorules::BONUS_APPLICATION_LIMIT_RULE_NO_LIMIT ||
            ($typerule['bonusApplicationLimitRule'] == Promorules::BONUS_APPLICATION_LIMIT_RULE_LIMIT_COUNT &&
                $typerule['bonusApplicationLimitRuleCnt'] > 1);
    }

    public function validateApplyConditions($base_date = null){
        $static_settings = $this->utils->getConfig('static_redemption_code_setting');
        $enable_custom_deposit_condictions = $this->utils->safeGetArray($static_settings, 'deposit_condictions', false);
        $enable_filled_cpf = $this->utils->safeGetArray($static_settings, 'filled_cpf', false);
        $success = false;
        $message = '';
        $this->load->model('player_model');
        $this->player_model = $this->ci->player_model;

        if($enable_filled_cpf === true){
            $filled_cpf = $this->player_model->isFilledCPFnumber($this->playerId);
            if(!$filled_cpf){
                $message = lang('redemptionCode.apply.filledCPF');
                return array($success, $message);
            }
        }

        if($enable_custom_deposit_condictions === true){
            $redemptionCodeDetail = $this->redemption_code_detail;
            $bonusRules = json_decode($redemptionCodeDetail['bonus_rules'], true);
    
            // "{"bonus": "3", "bonusCap": 0, "pastDayDepositDays": "99", "enablePastDayDeposit": true, "enableSameDayDeposit": true, "pastDayDepositAmount": "88", "sameDayDepositAmount": "999", "bonusReleaseTypeOption": "0", "nonfixedBonusMaxAmount": null, "nonfixedBonusMinAmount": null}"
    
            $this->load->model('transactions');
            $this->transactions = $this->ci->transactions;
            $todayTotalDeposit = 0;
            if($bonusRules['enableSameDayDeposit'] == 1){
                $date_only = isset($base_date) ? date('Y-m-d', strtotime($base_date)) : $this->utils->getTodayForMysql();
                $todayTotalDeposit = $this->transactions->getTotalDepositsToday($this->playerId, $date_only);
                $this->utils->debug_log("{$this->playerId}-todayTotalDeposit", [
                    "base_date" => $base_date,
                    "date_only" => $date_only,
                    "todayTotalDeposit" => $todayTotalDeposit,
                ]);
                if($todayTotalDeposit < $bonusRules['sameDayDepositAmount']){
                    $message = sprintf(lang('redemptionCode.apply.depositAmountNotMet'), $bonusRules['sameDayDepositAmount']);
                    return array($success, $message);
                }
            }
            $consecutive_deposit = [];
            $countAccumulated = 0;
            if($bonusRules['enablePastDayDeposit'] == 1){
                $require_consecutive_days = $bonusRules['pastDayDepositDays'];
                $minDeposit = $bonusRules['pastDayDepositAmount'];
                if($require_consecutive_days > 0) {
                    list($fromDate, $toDate) = $this->getLastDays($base_date, $require_consecutive_days);
                    $consecutive_deposit = $this->transactions->getConsecutiveDepositAndDateByDateTime($this->playerId, $fromDate, $toDate, $minDeposit);
                    $countAccumulated = count($consecutive_deposit);

                    $this->utils->debug_log("{$this->playerId}-pastDayDepositDays", [
                        "base_date" => $base_date,
                        "fromDate" => $fromDate, 
                        "toDate" => $toDate,
                        "consecutiveDeposit" => $consecutive_deposit,
                        "countAccumulated" => $countAccumulated
                    ]);
                    if(empty($consecutive_deposit) || !is_array($consecutive_deposit)){
                        $message = sprintf(lang('redemptionCode.apply.depositDaysNotmet'), $require_consecutive_days, $minDeposit);
                        return array($success, $message);
                    }
                    if($countAccumulated < $require_consecutive_days){
                        $message = sprintf(lang('redemptionCode.apply.depositDaysNotmet'), $require_consecutive_days, $minDeposit);
                        return array($success, $message);
                    }
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

        return array(true,null);
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

    public function validCodeTypeRepeatable()
    {
        $success = false;
        $redemption_code_detail = $this->redemption_code_detail;
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
            $this->utils->debug_log('redemption_code_detail', $redemption_code_detail, 'is_static_code', self::IS_STATIC_CODE);
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

            $getPlayerDuplicateTypeCount = 
            $this->static_redemption_code_model->getPlayerDuplicateType(
                $this->playerId,
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

    public function validBonusRules()
    {
        $redemptionCodeDetail = $this->redemption_code_detail;
        $success = false;
        $message = '';
        try {
            if (!is_array($redemptionCodeDetail)) {
                throw new Exception(lang('redemptionCode.invalid.parameters'));
            }
            if(!$this->utils->notEmptyValuesInArray(['total_redeemable_count', 'category_id', 'bonus_rules'], $redemptionCodeDetail)){
                throw new Exception(lang('redemptionCode.invalid.parameters'));
            }
    
            $categoryId = $redemptionCodeDetail['category_id'];
            $redeemableCountLimit = $redemptionCodeDetail['total_redeemable_count'];
            // $countUsedCode = $this->static_redemption_code_model->countUsedCode($categoryId);
            $countUnusedCode = $this->static_redemption_code_model->countCode($categoryId, Static_redemption_code_model::CODE_STATUS_UNUSED);
            $countPendingCode = $this->getAssignedCode($this->playerId, $this->redemption_code_detail['id']);
            // $countCode = $countUnusedCode + !empty($countPendingCode);
            if ($countUnusedCode <= 0 && empty($countPendingCode)) {
                throw new Exception(lang('redemptionCode.apply.useLimit'));
            }
            
            $bonusRules = json_decode($redemptionCodeDetail['bonus_rules'], true);
            if ($this->utils->safeGetArray($bonusRules, 'bonusReleaseTypeOption', false) == Promorules::BONUS_RELEASE_RULE_CUSTOM) {
                $bonusCap = $this->utils->safeGetArray($bonusRules, 'bonusCap', 0);
                $totalBonus = $this->static_redemption_code_model->sumUsedCodeBonus($categoryId);
                if ($totalBonus >= $bonusCap) {
                    // throw new Exception(lang('redemptionCode.apply.bonusLimit'));
                }
            }
            $success = true;
        } catch (\Throwable $th) {
            $message = $th->getMessage();
        }

        return array($success, $message);
    }

    public function validateRedemptionCodeStatus()
    {
        $redemptionCodeDetail = $this->redemption_code_detail;
        $code_status = $redemptionCodeDetail['code_status'];
        $category_status = $redemptionCodeDetail['category_status'];
        // $code_status != redemption_code_model::CODE_STATUS_UNUSED || !empty($redemptionCodeDetail['player_id']) || !empty($redemptionCodeDetail['promo_cms_id'])
        if($this->utils->notEmptyValuesInArray(['promo_cms_id'], $redemptionCodeDetail)){
            return true;
        }
        // if($code_status == Static_redemption_code_model::CODE_STATUS_UNUSED) {
        //     return false;
        // }
        if($code_status == Static_redemption_code_model::CODE_STATUS_PENDING && $redemptionCodeDetail['player_id'] == $this->playerId) {
            return false;
        }
        return true;
    }

    /**
     * validateRedemptionCodeExpiry function
     *
     * @param string $apply_time date mysql format
     * @return boolean
     */
    public function validateRedemptionCodeExpiry($apply_time)
    {
        $redemptionCodeDetail = $this->redemption_code_detail;
        $expires_at = $redemptionCodeDetail['expires_at'];
        $valid_forever = $redemptionCodeDetail['valid_forever'];
        
        if ($apply_time > $expires_at && $valid_forever != 1) { // code expired
            return true;
        }
        return false;
    }
}