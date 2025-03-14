<?php
require_once dirname(__FILE__) . '/base_model.php';

require_once dirname(__FILE__) . '/modules/apply_promorule_module.php';

/**
 * promorules
 *
 * applicationPeriodStart: start date
 * promoType: 0=DEPOSIT,1=NONDEPOSIT
 * noEndDateFlag, always 0, never end
 * depositConditionType, is deprecated
 * depositConditionDepositAmount is deprecated
 * nonfixedDepositAmtCondition is deprecated
 * nonfixedDepositAmtConditionRequiredDepositAmount is deprecated
 * depositConditionNonFixedDepositAmount, 0=DEPOSIT AMOUNT,1=ANY AMOUNT
 * nonfixedDepositMinAmount: min deposit amount
 * nonfixedDepositMaxAmount: max deposit amount
 * bonusApplication: is deprecated
 * depositSuccesionType: 0=1ST DEPOSIT, 3=OTHERS (1=2ND DEPOSIT, 2=3RD DEPOSIT deprecated), 4=NON-1ST DEPOSIT, 5=DEPOSIT_SUCCESION_TYPE_EVERY_TIME
 * depositSuccesionCnt: deposit times
 * depositSuccesionPeriod: 1-STARTING FROM REG, 4-BONUS EXPIRE
 * bonusApplicationRule: is deprecated
 * bonusApplicationLimitRule: 0=NOLIMIT,1=WITH LIMIT
 * bonusApplicationLimitRuleCnt: limit count, default is 1, 1= no repeat
 * bonusApplicationLimitDateType: 0=none, 1=daily, 2=weekly, 3=monthly, 4=yearly
 * repeatConditionBetCnt: is deprecated
 * bonusReleaseRule: 0=BY FIXED BONUS AMOUNT,1=BY PERCENTAGE, 2= BONUS_RELEASE_RULE_BET_PERCENTAGE, 3=BONUS_RELEASE_RULE_CUSTOM
 * bonusReleaseToPlayer: 0-automatic, 1=manual
 * releaseToSubWallet: 0=main, >0 subwallet
 * bonusAmount: fixed bonus amount
 * depositPercentage: percentage of deposit
 * maxBonusAmount: max bonus amount if use percentage
 * max_bonus_by_limit_date_type: check max bonus by bonusApplicationLimitDateType
 * withdrawRequirementRule: is deprecated
 * withdrawRequirementConditionType: 0= >= OF BET AMOUNT,1=(DEPOSIT AMOUNT + BONUS) X NUMBER OF BETTING TIMES, 2= nothing, 3=WITHDRAW_CONDITION_TYPE_CUSTOM
 * withdrawRequirementBetAmount:  >= fixed betting amount
 * withdrawRequirementBetCntCondition: times of betting amount
 * withdrawShouldMinusDeposit: 0=false, 1=true
 * nonDepositPromoType: IF NON-DEPOSIT CONDITION IS SELECTED, 0=BY EMAIL,1=BY MOBILE,2=BY REGISTRATION ACCT,3=BY COMPLETE PLAYER INFO,4=BY BETTING,5=BY LOSS,6=BY WINNING"
 * gameRequiredBet: required betting amount or win amount or loss amount
 * gameRecordStartDate, gameRecordEndDate: available date range
 * promoStatus: is deprecated
 * hide_date: hide/invisible/unavailable on bouns and CMS
 * rescue_min_balance: deprecated only for non-deposit-rescue-type, only in json_info
 * trigger_on_transfer_to_subwallet: is deprecated wallet id, 0=main wallet, use all condition for transfer amount , not deposit amount when checked
 * disabled_pre_application: disable pre application , if not, must first approve pre application
 * show_on_active_available: show on active available
 * disable_cashback_if_not_finish_withdraw_condition: disable cashback
 *
 * add_withdraw_condition_as_bonus_condition: 0=false, 1=true
 * donot_allow_other_promotion: 0=false, 1=true
 * expire_days: default is 0 means same with hide date
 *
 * hide_if_not_allow: default is 0, 0=false, 1=true
 * trigger_wallets: trigger multiple wallets
 * release_to_same_sub_wallet: release to same sub-wallet when trigger
 * always_join_promotion: default is 0
 * 'donot_allow_any_transfer_after_deposit',
 * 'donot_allow_any_withdrawals_after_deposit',
 * 'donot_allow_any_despoits_after_deposit'
 *
 * withdrawal_max_limit: any positive amount, 0 or empty means no limit
 * ignore_withdrawal_max_limit_after_first_deposit: boolean, true: will ignore limit if exists first deposit, will check it on withdrawal
 * always_apply_withdrawal_max_limit_when_first_deposit: boolean, true: apply max limit rule when first deposit
 *
 * formula: {"bonus_release":"","withdraw_condition":"","bonus_condition":""}
 * check runtime_model
 * runtime_data.get_available_deposit_amount($deposit_type)
 * runtime_data.get_game_result_amount(from_type,to_type)
 * runtime_data.get_game_betting_amount(from_type,to_type)
 *
 * deposit_type: 'last', 'first'
 * to_type: 'now'
 * from_type: 'player_reg_date', 'last_withdraw', 'last_same_promo'
 */
class Promorules extends BaseModel {

	protected $tableName = 'promorules';

	function __construct() {
		parent::__construct();
		$this->load->helper('date');
	}

	use apply_promorule_module;

	const SYSTEM_MANUAL_PROMO_TYPE_NAME = '_SYSTEM_MANUAL';
	const SYSTEM_MANUAL_PROMO_RULE_NAME = '_SYSTEM_MANUAL';
	const SYSTEM_MANUAL_PROMO_CMS_NAME = '_SYSTEM_MANUAL';

	const SYSTEM = 1;
	// const APPROVED = 1;

	const IS_HIDE_PROMO = 1;

	const NO_END_DATE_FLAG_TRUE = 0;

	const REQUEST_TO_CANCEL = 1;
	const DECLINE_CANCEL_REQUEST = 2;
	const APPROVED_CANCEL_REQUEST = 3;

	const REQUEST = 0;
	const APPROVED = 1;
	const DECLINED = 2;
	const CANCELLED = 3;

	const PROMO_TYPE_DEPOSIT = 0;
	const PROMO_TYPE_NON_DEPOSIT = 1;

	const NON_FIXED_DEPOSIT_MIN_MAX = 0;
	const NON_FIXED_DEPOSIT_ANY = 1;

	const DEPOSIT_SUCCESION_TYPE_FIRST = 0;
	const DEPOSIT_SUCCESION_TYPE_ANY = 3;
	const DEPOSIT_SUCCESION_TYPE_NOT_FIRST = 4;
	const DEPOSIT_SUCCESION_TYPE_EVERY_TIME = 5;

	const DEPOSIT_SUCCESION_PERIOD_START_FROM_REG = 1;
	const DEPOSIT_SUCCESION_PERIOD_BONUS_EXPIRE = 4;

	const NON_DEPOSIT_PROMO_TYPE_EMAIL = 0;
	const NON_DEPOSIT_PROMO_TYPE_MOBILE = 1;
	const NON_DEPOSIT_PROMO_TYPE_REGISTRATION = 2;
	const NON_DEPOSIT_PROMO_TYPE_COMPLETE_PLAYER_INFO = 3;
	const NON_DEPOSIT_PROMO_TYPE_BETTING = 4;
	const NON_DEPOSIT_PROMO_TYPE_LOSS = 5;
	const NON_DEPOSIT_PROMO_TYPE_WINNING = 6;
	const NON_DEPOSIT_PROMO_TYPE_RESCUE = 7;
	const NON_DEPOSIT_PROMO_TYPE_CUSTOMIZE = 8;
	const NON_DEPOSIT_PROMO_TYPE_LOSS_MINUS_WIN = 9;

	const BONUS_APPLICATION_LIMIT_RULE_NO_LIMIT = 0;
	const BONUS_APPLICATION_LIMIT_RULE_LIMIT_COUNT = 1;
	//bonusApplicationLimitDateType
	const BONUS_APPLICATION_LIMIT_DATE_TYPE_NONE = 0;
	const BONUS_APPLICATION_LIMIT_DATE_TYPE_DAILY = 1;
	const BONUS_APPLICATION_LIMIT_DATE_TYPE_WEEKLY = 2;
	const BONUS_APPLICATION_LIMIT_DATE_TYPE_MONTHLY = 3;
	const BONUS_APPLICATION_LIMIT_DATE_TYPE_YEARLY = 4;

	const BONUS_RELEASE_TO_PLAYER_AUTO = 0;
	const BONUS_RELEASE_TO_PLAYER_MANUAL = 1;

	const BONUS_RELEASE_RULE_FIXED_AMOUNT = 0;
	const BONUS_RELEASE_RULE_DEPOSIT_PERCENTAGE = 1;
	const BONUS_RELEASE_RULE_BET_PERCENTAGE = 2;
	const BONUS_RELEASE_RULE_CUSTOM = 3;
	const BONUS_RELEASE_RULE_BONUS_GAME = 4;

	const WITHDRAW_CONDITION_TYPE_FIXED_AMOUNT = 0;
	const WITHDRAW_CONDITION_TYPE_BETTING_TIMES = 1;
	const WITHDRAW_CONDITION_TYPE_NOTHING = 2;
	const WITHDRAW_CONDITION_TYPE_CUSTOM = 3;
	const WITHDRAW_CONDITION_TYPE_BONUS_TIMES = 4;
    const WITHDRAW_CONDITION_TYPE_BETTING_TIMES_CHECK_WITH_MAX_BONUS = 5;

    const DEPOSIT_CONDITION_TYPE_NOTHING = 0;
	const DEPOSIT_CONDITION_TYPE_MIN_LIMIT = 1;
    const DEPOSIT_CONDITION_TYPE_MIN_LIMIT_SINCE_REGISTRATION = 2;

    const TRANSFER_CONDITION_TYPE_NOTHING = 0;
    const TRANSFER_CONDITION_TYPE_BONUS_TIMES = 1;
    const TRANSFER_CONDITION_TYPE_BETTING_TIMES = 2;
    const TRANSFER_CONDITION_TYPE_FIXED_AMOUNT = 3;
    const TRANSFER_CONDITION_TYPE_CUSTOM = 4;
    const TRANSFER_CONDITION_TYPE_BETTING_TIMES_CHECK_WITH_MAX_BONUS = 5;

	const RANDOM_BONUS_MODE_PERCENT_DEPOSIT = 1;
	const RANDOM_BONUS_MODE_COUNTING = 2;
	const RANDOM_BONUS_MODE_FIXED_ITEM = 3;

	const DEFAULT_RANDOM_BONUS_DAILY_COUNT = 0;
	const RANDOM_BONUS_MODE_COUNTING_RESULT_TYPE_1 = 1;
	const RANDOM_BONUS_MODE_COUNTING_RESULT_TYPE_50 = 2;

	const SHOW_ON_PLAYER_PROMOTION_AND_DEPOSIT = 0;
	const SHOW_ON_PLAYER_PROMOTION = 1;
	const SHOW_ON_PLAYER_DEPOSIT = 2;

	const ENABLED_AUTO_TICK_NEW_GAME = 1;
	const DISABLED_AUTO_TICK_NEW_GAME = 0;

	const CREATE_AND_APPLY_BONUS_MULTI = 'insvr.CreateAndApplyBonusMulti';

	const CLAIM_BONUS_TYPE_DAILY = 1;
	const CLAIM_BONUS_TYPE_WEEKLY = 2;
	const CLAIM_BONUS_TYPE_MONTHLY = 3;

	const MISSION_STATUS_CONDICTION_NOT_MET = 1;
	const MISSION_STATUS_CONDICTION_MET_NOT_CLAIM = 2;
	const MISSION_STATUS_CLAIMED = 3;

	/**
	 * overview : promo rules constants
	 * @return array
	 */
	public function getConst() {
		return array(
			"PROMO_TYPE_DEPOSIT" => self::PROMO_TYPE_DEPOSIT,
			"PROMO_TYPE_NON_DEPOSIT" => self::PROMO_TYPE_NON_DEPOSIT,

			"NON_FIXED_DEPOSIT_MIN_MAX" => self::NON_FIXED_DEPOSIT_MIN_MAX,
			"NON_FIXED_DEPOSIT_ANY" => self::NON_FIXED_DEPOSIT_ANY,

			"DEPOSIT_SUCCESION_TYPE_FIRST" => self::DEPOSIT_SUCCESION_TYPE_FIRST,
			"DEPOSIT_SUCCESION_TYPE_ANY" => self::DEPOSIT_SUCCESION_TYPE_ANY,
			"DEPOSIT_SUCCESION_TYPE_NOT_FIRST" => self::DEPOSIT_SUCCESION_TYPE_NOT_FIRST,
			"DEPOSIT_SUCCESION_TYPE_EVERY_TIME" => self::DEPOSIT_SUCCESION_TYPE_EVERY_TIME,

			"DEPOSIT_SUCCESION_PERIOD_START_FROM_REG" => self::DEPOSIT_SUCCESION_PERIOD_START_FROM_REG,
			"DEPOSIT_SUCCESION_PERIOD_BONUS_EXPIRE" => self::DEPOSIT_SUCCESION_PERIOD_BONUS_EXPIRE,

			"NON_DEPOSIT_PROMO_TYPE_EMAIL" => self::NON_DEPOSIT_PROMO_TYPE_EMAIL,
			"NON_DEPOSIT_PROMO_TYPE_MOBILE" => self::NON_DEPOSIT_PROMO_TYPE_MOBILE,
			"NON_DEPOSIT_PROMO_TYPE_REGISTRATION" => self::NON_DEPOSIT_PROMO_TYPE_REGISTRATION,
			"NON_DEPOSIT_PROMO_TYPE_COMPLETE_PLAYER_INFO" => self::NON_DEPOSIT_PROMO_TYPE_COMPLETE_PLAYER_INFO,
			"NON_DEPOSIT_PROMO_TYPE_RESCUE" => self::NON_DEPOSIT_PROMO_TYPE_RESCUE,
			"NON_DEPOSIT_PROMO_TYPE_CUSTOMIZE" => self::NON_DEPOSIT_PROMO_TYPE_CUSTOMIZE,
			"NON_DEPOSIT_PROMO_TYPE_BETTING" => self::NON_DEPOSIT_PROMO_TYPE_BETTING,
			"NON_DEPOSIT_PROMO_TYPE_LOSS" => self::NON_DEPOSIT_PROMO_TYPE_LOSS,
			"NON_DEPOSIT_PROMO_TYPE_WINNING" => self::NON_DEPOSIT_PROMO_TYPE_WINNING,

			"BONUS_APPLICATION_LIMIT_RULE_NO_LIMIT" => self::BONUS_APPLICATION_LIMIT_RULE_NO_LIMIT,
			"BONUS_APPLICATION_LIMIT_RULE_LIMIT_COUNT" => self::BONUS_APPLICATION_LIMIT_RULE_LIMIT_COUNT,

			"BONUS_APPLICATION_LIMIT_DATE_TYPE_NONE" => self::BONUS_APPLICATION_LIMIT_DATE_TYPE_NONE,
			"BONUS_APPLICATION_LIMIT_DATE_TYPE_DAILY" => self::BONUS_APPLICATION_LIMIT_DATE_TYPE_DAILY,
			"BONUS_APPLICATION_LIMIT_DATE_TYPE_WEEKLY" => self::BONUS_APPLICATION_LIMIT_DATE_TYPE_WEEKLY,
			"BONUS_APPLICATION_LIMIT_DATE_TYPE_MONTHLY" => self::BONUS_APPLICATION_LIMIT_DATE_TYPE_MONTHLY,
			"BONUS_APPLICATION_LIMIT_DATE_TYPE_YEARLY" => self::BONUS_APPLICATION_LIMIT_DATE_TYPE_YEARLY,

			"BONUS_RELEASE_TO_PLAYER_AUTO" => self::BONUS_RELEASE_TO_PLAYER_AUTO,
			"BONUS_RELEASE_TO_PLAYER_MANUAL" => self::BONUS_RELEASE_TO_PLAYER_MANUAL,

			"BONUS_RELEASE_RULE_FIXED_AMOUNT" => self::BONUS_RELEASE_RULE_FIXED_AMOUNT,
			"BONUS_RELEASE_RULE_DEPOSIT_PERCENTAGE" => self::BONUS_RELEASE_RULE_DEPOSIT_PERCENTAGE,
			"BONUS_RELEASE_RULE_BET_PERCENTAGE" => self::BONUS_RELEASE_RULE_BET_PERCENTAGE,
			"BONUS_RELEASE_RULE_CUSTOM" => self::BONUS_RELEASE_RULE_CUSTOM,

			"WITHDRAW_CONDITION_TYPE_FIXED_AMOUNT" => self::WITHDRAW_CONDITION_TYPE_FIXED_AMOUNT,
			"WITHDRAW_CONDITION_TYPE_BETTING_TIMES" => self::WITHDRAW_CONDITION_TYPE_BETTING_TIMES,
			"WITHDRAW_CONDITION_TYPE_NOTHING" => self::WITHDRAW_CONDITION_TYPE_NOTHING,
			"WITHDRAW_CONDITION_TYPE_CUSTOM" => self::WITHDRAW_CONDITION_TYPE_CUSTOM,
			"WITHDRAW_CONDITION_TYPE_BONUS_TIMES" => self::WITHDRAW_CONDITION_TYPE_BONUS_TIMES,
            "WITHDRAW_CONDITION_TYPE_BETTING_TIMES_CHECK_WITH_MAX_BONUS" => self::WITHDRAW_CONDITION_TYPE_BETTING_TIMES_CHECK_WITH_MAX_BONUS,

            "DEPOSIT_CONDITION_TYPE_MIN_LIMIT" => self::DEPOSIT_CONDITION_TYPE_MIN_LIMIT,
            "DEPOSIT_CONDITION_TYPE_MIN_LIMIT_SINCE_REGISTRATION" => self::DEPOSIT_CONDITION_TYPE_MIN_LIMIT_SINCE_REGISTRATION,
            "DEPOSIT_CONDITION_TYPE_NOTHING" => self::DEPOSIT_CONDITION_TYPE_NOTHING,

            "TRANSFER_CONDITION_TYPE_NOTHING" => self::TRANSFER_CONDITION_TYPE_NOTHING,
			"TRANSFER_CONDITION_TYPE_BONUS_TIMES" => self::TRANSFER_CONDITION_TYPE_BONUS_TIMES,
			"TRANSFER_CONDITION_TYPE_BETTING_TIMES_CHECK_WITH_MAX_BONUS" => self::TRANSFER_CONDITION_TYPE_BETTING_TIMES_CHECK_WITH_MAX_BONUS,
		);
	}

	const FIELDS = array('applicationPeriodStart', 'promoType', 'noEndDateFlag',
		'depositConditionType', 'depositConditionDepositAmount', 'nonfixedDepositAmtCondition',
		'nonfixedDepositAmtConditionRequiredDepositAmount',
		'depositConditionNonFixedDepositAmount', 'nonfixedDepositMinAmount', 'nonfixedDepositMaxAmount',
		'bonusApplication', 'depositSuccesionType', 'depositSuccesionCnt',
		'depositSuccesionPeriod', 'bonusApplicationRule', 'bonusApplicationLimitRule',
		'bonusApplicationLimitRuleCnt', 'bonusApplicationLimitDateType', 'repeatConditionBetCnt', 'bonusReleaseRule',
		'bonusReleaseToPlayer', 'releaseToSubWallet', 'bonusAmount', 'depositPercentage',
		'maxBonusAmount', 'max_bonus_by_limit_date_type', 'withdrawRequirementRule', 'withdrawRequirementConditionType',
		'withdrawRequirementBetAmount', 'withdrawRequirementBetCntCondition', 'withdrawShouldMinusDeposit',
        'withdrawRequirementDepositConditionType','withdrawRequirementDepositAmount',
        'transferRequirementWalletsInfo', 'transferRequirementConditionType', 'transferRequirementBetCntCondition',
		'nonDepositPromoType', 'gameRequiredBet', 'gameRecordStartDate', 'gameRecordEndDate',
		'promoStatus', 'hide_date', 'rescue_min_balance',
		'add_withdraw_condition_as_bonus_condition', 'donot_allow_other_promotion',
		'disabled_pre_application', 'disable_cashback_if_not_finish_withdraw_condition',
		'disable_cashback_entirely', 'disable_cashback_length',
		'hide_if_not_allow', 'trigger_wallets', 'release_to_same_sub_wallet', 'always_join_promotion',
		'donot_allow_any_transfer_after_deposit', 'donot_allow_any_withdrawals_after_deposit', 'donot_allow_any_despoits_after_deposit',
        'donot_allow_any_available_bet_after_deposit', 'applicationPeriodEnd', 'promoCode', 'donot_allow_any_transfer_in_after_transfer',
        'donot_allow_any_transfer_out_after_transfer', 'allowed_scope_condition', 'transferRequirementBetAmount', 'transferShouldMinusDeposit',
        'request_limit', 'approved_limit', 'total_approved_limit', 'dont_allow_request_promo_from_same_ips', 'donot_allow_exists_any_bet_after_deposit',
		//'withdrawal_max_limit', 'ignore_withdrawal_max_limit_after_first_deposit', 'always_apply_withdrawal_max_limit_when_first_deposit', 'show_on_active_available', 'expire_days',
		);

	/**
	 * overview : get promo rules list
	 *
	 * @return array
	 */
	public function getPromoRulesList() {
		return $this->db
			// git issue #1371
			->where('promorules.deleted_flag IS NULL', null)
			->get('promorules')->result_array();
	}

	/**
	 * overview : get promo rules
	 * @param $promo_rule_id
	 * @return mixed
	 */
	public function getPromoRules($promo_rule_id) {
		return $this->db
			// git issue #1371
			->where('promorules.deleted_flag IS NULL', null)
			->where('promorulesId', $promo_rule_id)
			->get('promorules', ['promorulesId', $promo_rule_id])
			->row_array();
	}

	/**
	 * overview : update player promo
	 *
	 * @param int $player_promo_id
	 * @param int $player_promo
	 * @return bool
	 */
	public function updatePlayerPromo($player_promo_id, $player_promo) {
		$this->db->update('playerpromo', $player_promo, ['playerpromoId' => $player_promo_id]);
		if ($this->db->affected_rows()) {
			return $this->db->get_where('playerpromo', ['playerpromoId' => $player_promo_id])->row_array();
		}return FALSE;
	}

	/**
	 * overview : check promo for hiding
	 *
	 * @return bool
	 */
	public function checkPromoForHiding() {
		$this->db->select('promorulesId')->from('promorules');
		$this->db->where('hide_date','0000-00-00 00:00:00');
		$this->db->where('promorules.deleted_flag IS NULL', null);
		$qry = $this->db->get();
		$result = $qry->result_array();
		$promoData['hide_date'] = '2099-12-31 00:00:00';
		foreach ($result as $key) {
			$this->db->where('promorulesId', $key['promorulesId'])->set($promoData);
			$this->runAnyUpdate('promorules');
		}

		$now = $this->utils->getNowForMysql();
		$promo['status'] = self::IS_HIDE_PROMO;
		$this->db->where('hide_date <= ', $now)->set($promo);
		return $this->runAnyUpdate('promorules');
	}

	/**
	 * overview : get promo list
	 *
	 * @param int $limit
	 * @param int $offset
	 * @return mixed
	 */
	public function getAllPromo($limit = null, $offset = null, $promoCmsSettingId = null, $promoCategory = null) {
		// $this->load->library(array('language_function'));
		// $language = $this->language_function->getCurrentLangForPromo();
		$this->db->select(array(
			'promocmssetting.promoCmsSettingId',
			'promocmssetting.promoName',
			'promocmssetting.promoDescription',
			'promocmssetting.promoDetails',
			'promocmssetting.promoThumbnail',
			'promocmssetting.promoId',
			'promocmssetting.status',
			'promocmssetting.hide_on_player',
			'promocmssetting.promo_code',
			'promocmssetting.tag_as_new_flag',
			'promocmssetting.is_default_banner_flag',
			'promocmssetting.promo_category',
			'promocmssetting.promo_multi_lang',
			'promocmssetting.allow_claim_promo_in_promo_page',
			'promocmssetting.claim_button_url',
			'promocmssetting.claim_button_link',
			'promocmssetting.claim_button_name',
			'promorules.add_withdraw_condition_as_bonus_condition',
			'promorules.applicationPeriodStart',
			'promorules.hide_date',
			'promocmssetting.display_apply_btn_in_promo_page',
			'promorules.promo_period_countdown',
		));
		$this->db->from('promocmssetting')->join('promorules', 'promorules.promorulesId=promocmssetting.promoId');
		// $promoCmsSettingId ? $this->db->where('promocmssetting.promoCmsSettingId', $promoCmsSettingId) : "";

		if (is_array($promoCmsSettingId)) {
			$this->db->where_in('promocmssetting.promoCmsSettingId', $promoCmsSettingId);
		}
		else if (!empty($promoCmsSettingId)) {
			$this->db->where('promocmssetting.promoCmsSettingId', $promoCmsSettingId);
		}

		$this->db->where('promocmssetting.status', 'active')
			->where('promorules.status', self::OLD_STATUS_ACTIVE);

		$promotion_list_available_days = (int)$this->utils->getConfig('promotion_list_available_days');
		if($promotion_list_available_days) {
			$this->db->where('promorules.applicationPeriodStart <=', $this->utils->getNowAdd($promotion_list_available_days * 86400));
			$this->db->where('promorules.hide_date >=', $this->utils->getNowSub($promotion_list_available_days * 86400));
		} else {
			$this->db->where('promorules.applicationPeriodStart <=', $this->utils->getNowForMysql());
			$this->db->where('promorules.hide_date >=', $this->utils->getNowForMysql());
		}

			// git issue #1371
		$this->db->where('promocmssetting.deleted_flag IS NULL', null, false)
			->where('promorules.deleted_flag IS NULL', null)
		;

		if (!empty($promoCategory)) {
			$this->db->where('promorules.promoCategory', $promoCategory);
		}

		if ($this->utils->getConfig('enabled_promorulesorder')) {
			$this->db->order_by('promocmssetting.promoOrder', 'DESC');
		} else {
			$this->db->order_by('promocmssetting.promoCmsSettingId', 'DESC');
		}

		if ($limit != null) {
			if ($offset != null && $offset != 'undefined') {
				$this->db->limit($limit, $offset);
			} else {
				$this->db->limit($limit);
			}
		}
		$query = $this->db->get();
		$result = $query->result_array();
		return $result;
	}

	/**
	 * getPlayerReferralPagination
	 *
	 * @param int $categoryId
	 * @param int $limit
	 * @param int $page
	 * @return array
	 */
    public function getAllPromoPagination($promoCmsSettingId = null, $promoCategory = null, $pagination)
    {
    	$limit = isset($pagination['limit'])? $pagination['limit'] : 20;
        $page = isset($pagination['page'])? $pagination['page'] : 1;
		$promo_type = isset($pagination['promo_type'])? $pagination['promo_type'] : null;
        $only_visible_category = $this->utils->safeGetArray($pagination, 'only_visible_category', false);
		$table = 'promocmssetting';
        $result = $this->getDataWithAPIPagination($table, function() use($promoCmsSettingId, $promoCategory, $limit, $page, $table, $only_visible_category, $promo_type) {
        	$this->db->select(array(
				'promocmssetting.promoCmsSettingId',
				'promocmssetting.promoName',
				'promocmssetting.promoDescription',
				'promocmssetting.promoDetails',
				'promocmssetting.promoThumbnail',
				'promocmssetting.promoId',
				'promocmssetting.status',
				'promocmssetting.hide_on_player',
				'promocmssetting.promo_code',
				'promocmssetting.tag_as_new_flag',
				'promocmssetting.is_default_banner_flag',
				'promocmssetting.promo_category',
				'promocmssetting.promo_multi_lang',
				'promocmssetting.allow_claim_promo_in_promo_page',
				'promocmssetting.claim_button_url',
				'promocmssetting.claim_button_link',
				'promocmssetting.claim_button_name',
				'promorules.add_withdraw_condition_as_bonus_condition',
				'promorules.applicationPeriodStart',
				'promorules.hide_date',
				'promorules.promo_period_countdown',
				'promocmssetting.display_apply_btn_in_promo_page',
				'promocmssetting.promoOrder',
			));

			$this->db->join('promorules', 'promorules.promorulesId=promocmssetting.promoId');

			if (is_array($promoCmsSettingId)) {
				$this->db->where_in('promocmssetting.promoCmsSettingId', $promoCmsSettingId);
			}
			else if (!empty($promoCmsSettingId)) {
				$this->db->where('promocmssetting.promoCmsSettingId', $promoCmsSettingId);
			}

			$this->db->where('promocmssetting.status', 'active')
				->where('promorules.status', self::OLD_STATUS_ACTIVE);

			$promotion_list_available_days = (int)$this->utils->getConfig('promotion_list_available_days');
			if($promotion_list_available_days) {
				$this->db->where('promorules.applicationPeriodStart <=', $this->utils->getNowAdd($promotion_list_available_days * 86400));
				$this->db->where('promorules.hide_date >=', $this->utils->getNowSub($promotion_list_available_days * 86400));
			} else {
				$this->db->where('promorules.applicationPeriodStart <=', $this->utils->getNowForMysql());
				$this->db->where('promorules.hide_date >=', $this->utils->getNowForMysql());
			}

				// git issue #1371
			$this->db->where('promocmssetting.deleted_flag IS NULL', null, false)
				->where('promorules.deleted_flag IS NULL', null)
				->where('promocmssetting.hide_on_player IS NOT NULL', null, false);

			if (!empty($promoCategory)) {
				$this->db->where('promorules.promoCategory', $promoCategory);
			}

			if (!empty($promo_type)) {
				if ($promo_type == 'deposit') {
					$this->db->where('promorules.promoType', self::PROMO_TYPE_DEPOSIT);
				} else if ($promo_type == 'task') {
					$this->db->where('promorules.promoType', self::PROMO_TYPE_NON_DEPOSIT);
				}
			}

			if($only_visible_category){
				$this->db->join('promotype', 'promorules.promoCategory = promotype.promotypeId AND promotype.isUseToPromoManager > 0');
			}

			if ($this->utils->getConfig('enabled_promorulesorder')) {
				$this->db->order_by('promocmssetting.promoOrder', 'DESC');
			} else {
				$this->db->order_by('promocmssetting.promoCmsSettingId', 'DESC');
			}

        }, $limit, $page);
        return $result;
    }

	/**
	 * overview : get all promo information
	 * @return array
	 */
	public function getAllPromoInfo() {
		$sql = "SELECT promorules.*,admin1.username AS createdBy, admin2.username AS updatedBy
			FROM promorules
			LEFT JOIN adminusers AS admin1
			ON admin1.userId = promorules.createdBy
			LEFT JOIN adminusers AS admin2
			ON admin2.userId = promorules.updatedBy
			where promorules.promoName != ?
			AND deleted_flag IS NULL
			";

		return $this->runRawSelectSQLArray($sql, array(self::SYSTEM_MANUAL_PROMO_RULE_NAME));

		// $cnt = 0;
		// if ($query->num_rows() > 0) {
		// foreach ($query->result_array() as $row) {
		// 	$row['createdOn'] = mdate('%M %d, %Y - %h:%i:%s %A', strtotime($row['createdOn']));
		// 	if ($row['updatedOn'] != null) {
		// 		$row['updatedOn'] = mdate('%M %d, %Y - %h:%i:%s %A', strtotime($row['updatedOn']));
		// 	}
		// 	$data[] = $row;
		// 	// $data[$cnt]['promorulesallowedplayerlevel'] = $this->getDepositPromoPlayerLevelLimit($row['promorulesId']);
		// 	$cnt++;
		// }
		//var_dump($data);exit();
		// return $data;
		// }
		// return false;
	}

	/**
	 * overview : get player promo
	 *
	 * @param int	$limit
	 * @param int	$offset
	 * @return bool
	 */
	public function getAllPlayersPromo($limit, $offset) {
		if ($limit != null) {
			$limit = "LIMIT " . $limit;
		}

		if ($offset != null && $offset != 'undefined') {
			$offset = "OFFSET " . $offset;
		} else {
			$offset = ' ';
		}

		// $query = $this->db->query("SELECT mkt_promo.promoName,mkt_promo.promoCode,mkt_promodescription.promoHtmlDescription
		// 	FROM mkt_promocategory
		// 	LEFT JOIN cmspromo
		// 	ON cmspromo.promoId = mkt_promocategory.promoId
		// 	LEFT JOIN mkt_promo
		// 	ON mkt_promocategory.promoId = mkt_promo.promoId
		// 	LEFT JOIN mkt_promodescription
		// 	ON mkt_promodescription.promoId = mkt_promo.promoId
		// 	WHERE cmspromo.status = 'active'
		// 	AND mkt_promocategory.category = 'all'
		// 	ORDER BY mkt_promo.promoId DESC
		// 	$limit
		// 	$offset
		// ");
		// $language = "'" . $this->session->userdata('currentLanguage') . "'";
		// $this->load->library(array('language_function'));
		// $language = $this->language_function->getCurrentLangForPromo();
		$query = $this->db->query("SELECT promocmssetting.*
			FROM promocmscategory
			LEFT JOIN promocmssetting
		 	ON promocmssetting.promoCmsSettingId = promocmscategory.promoCmsSettingId
			WHERE promocmssetting.status = 'active'
				AND promocmssetting.deleted_flag IS NULL
			AND promocmssetting.language = ?
			AND promocmscategory.promoCmsCatId = 4
			ORDER BY promocmssetting.createdOn DESC
			$limit
			$offset
		");

		if ($query->num_rows() > 0) {
			// foreach ($query->result_array() as $row) {
			// 	$data[] = $row;
			// }
			// //var_dump($data);exit();
			// return $data;
			return $query->result_array();
		}
		return false;
	}

	/**
	 * overview : get all VIP Promo
	 * @param int	$limit
	 * @param int	$offset
	 * @return bool
	 */
	public function getAllVIPPromo($limit, $offset) {
		if ($limit != null) {
			$limit = "LIMIT " . $limit;
		}

		if ($offset != null && $offset != 'undefined') {
			$offset = "OFFSET " . $offset;
		} else {
			$offset = ' ';
		}

		// $query = $this->db->query("SELECT mkt_promo.promoName,mkt_promo.promoCode,mkt_promodescription.promoHtmlDescription
		// 	FROM mkt_promocategory
		// 	LEFT JOIN cmspromo
		// 	ON cmspromo.promoId = mkt_promocategory.promoId
		// 	LEFT JOIN mkt_promo
		// 	ON mkt_promocategory.promoId = mkt_promo.promoId
		// 	LEFT JOIN mkt_promodescription
		// 	ON mkt_promodescription.promoId = mkt_promo.promoId
		// 	WHERE cmspromo.status = 'active'
		// 	AND mkt_promocategory.category = 'vip'
		// 	ORDER BY mkt_promo.promoId DESC
		// 	$limit
		// 	$offset
		// ");
		// $language = "'" . $this->session->userdata('currentLanguage') . "'";
		// $this->load->library(array('language_function'));
		// $language = $this->language_function->getCurrentLangForPromo();
		$query = $this->db->query("SELECT promocmssetting.*
			FROM promocmscategory
			LEFT JOIN promocmssetting
		 	ON promocmssetting.promoCmsSettingId = promocmscategory.promoCmsSettingId
			WHERE promocmssetting.status = 'active'
				AND promocmssetting.deleted_flag IS NULL
			AND promocmssetting.language = ?
			AND promocmscategory.promoCmsCatId = 3
			ORDER BY promocmssetting.createdOn DESC
			$limit
			$offset
		");

		if ($query->num_rows() > 0) {
			// foreach ($query->result_array() as $row) {
			// 	$data[] = $row;
			// }
			// //var_dump($data);exit();
			// return $data;
			return $query->result_array();
		}
		return false;
	}

	/**
	 * overview : get promo cms details
	 * @param $promocmsId
	 * @return array|bool
	 */
	public function getPromoCmsDetails($promocmsId) {
		$this->db->select([
                'promocmssetting.promoCmsSettingId',
                'promocmssetting.promoName',
                'promocmssetting.promoDescription',
                'promocmssetting.promoDetails',
                'promocmssetting.promoThumbnail',
                'promocmssetting.promoId',
                'promocmssetting.status',
                'promocmssetting.hide_on_player',
                'promocmssetting.promo_code',
                'promocmssetting.tag_as_new_flag',
                'promocmssetting.is_default_banner_flag',
                'promocmssetting.promo_category',
                'promocmssetting.promo_multi_lang',
                'promorules.add_withdraw_condition_as_bonus_condition'
        ])
			->from('promocmssetting')
			->join('promorules', 'promorules.promorulesId = promocmssetting.promoId', 'left');
		$this->db->where('promocmssetting.promoCmsSettingId', $promocmsId);
		// git issue #1371
		$this->db->where('promocmssetting.deleted_flag IS NULL', null, false);
		$this->db->where('promorules.deleted_flag IS NULL', null);
		$query = $this->db->get();
		if ($query->num_rows() > 0) {
			foreach ($query->result_array() as $row) {
				$data[] = $row;
			}
			//var_dump($data);exit();
			return $data;
		}
		return false;
	}

	/**
	 * overview : get cms promo
	 * @param  string	$promoCode
	 * @return array|bool
	 */
	public function getPromoCmsDetailsByPromoCode($promoCode) {
		$this->db->select('promocmssetting.promoName,
						   promocmssetting.promoDetails,
						   promocmssetting.promoDescription,
						   promocmssetting.promoId,
						   promocmssetting.promoCmsSettingId,
						   promorules.promoType,
						   promorules.add_withdraw_condition_as_bonus_condition
						   ')
			->from('promocmssetting')
			->join('promorules', 'promorules.promorulesId = promocmssetting.promoId', 'left');
		$this->db->where('promocmssetting.promo_code', $promoCode);
		$query = $this->db->get();
		if ($query->num_rows() > 0) {
			foreach ($query->result_array() as $row) {
				$data[] = $row;
			}
			//var_dump($data);exit();
			return $data;
		}
		return false;
	}

	/**
	 * overview : get promo rule game type
	 *
	 * @param  int	$promorulesId
	 * @return array|bool
	 */
	public function getPromoRuleGameType($promorulesId) {
		$this->db->select('promorulesgametype.gameType,game.game')->from('promorulesgametype');
		$this->db->where('promorulesgametype.promoruleId', $promorulesId);
		$this->db->join('game', 'game.gameId = promorulesgametype.gameType', 'left');
		$query = $this->db->get();
		if ($query->num_rows() > 0) {
			foreach ($query->result_array() as $row) {
				$data[] = $row;
			}
			//var_dump($data);exit();
			return $data;
		}
		return false;
	}

	/**
	 * overview : getPlayerPromoStartDate
	 *
	 * @param	int		$playerpromoId
	 * @return	$array
	 */
	public function getPlayerPromoStartDate($playerpromoId) {
		$this->db->select('dateApply as date')->from('playerpromo');
		$this->db->where('playerpromo.playerpromoId', $playerpromoId);
		$query = $this->db->get();
		return $query->row_array();
	}

	const AUTO = 1;

	/**
	 * overview : check if auto approve is cancel
	 * @return bool
	 */
	public function isAutoApproveCancel() {
		$this->db->select('value')->from('operator_settings');
		$this->db->where('name', 'promo_cancellation_setting');
		$query = $this->db->get();
		$cancelSetup = $query->row_array();

		if ($cancelSetup['value'] == self::AUTO) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * overview : get promo rules for non deposit promo type
	 * @param  int $promorulesId
	 * @return array
	 */
	public function getPromoRuleNonDepositPromoType($promorulesId) {
		$this->db->select('promorules.nonDepositPromoType')
			->from('promorules');
		$this->db->where('promorules.promorulesId', $promorulesId);
		$this->db->where('promorules.deleted_flag IS NULL', null);
		$query = $this->db->get();

		return $query->row_array();
	}

	/**
	 * overview : get registration fields
	 *
	 * @param  int	$playerpromoId
	 * @return array
	 */
	function getPromoRulesId($playerpromoId) {
		$query = $this->db->query("SELECT promorulesId FROM playerpromo
			WHERE playerpromoId = '" . $playerpromoId . "'
		");

		return $query->row_array();
	}

	/**
	 * overview : view promo rules details
	 *
	 * @param $promoruleId
	 * @return null
	 */
	// Using BaseController::TRUE from cronjob, it will cause to Fatal Error (E_ERROR): Class 'BaseController' not found
	public function viewPromoRuleDetails($promoruleId, $filter_deleted_rule = 1, $do_extra_allowed_items = true) { // BaseController::TRUE=1
        $enabled_log_OGP29899_performance_trace = $this->utils->getConfig('enabled_log_OGP29899_performance_trace');
        global $BM;
		$this->db->select('promorules.*,
						   promotype.promoTypeName,
						   admin1.userName as createdBy,
						   admin2.userName as updatedBy')->from('promorules');
		$this->db->join("promotype", "promotype.promotypeId = promorules.promoCategory", 'left');
		$this->db->join('adminusers as admin1', 'admin1.userId = promorules.createdBy', 'left');
		$this->db->join('adminusers as admin2', 'admin2.userId = promorules.updatedBy', 'left');

		if($filter_deleted_rule){
            $this->db->where('promorules.deleted_flag IS NULL', null);
        }

		$this->db->where('promorulesId', $promoruleId)->limit(1);

        if($enabled_log_OGP29899_performance_trace){
            $BM->mark('performance_trace_time_828');
        } // EOF if($enabled_log_OGP29899_performance_trace){...

		$row = $this->runOneRowArray();

        if($enabled_log_OGP29899_performance_trace){
            $BM->mark('performance_trace_time_834');
        } // EOF if($enabled_log_OGP29899_performance_trace){...

		if (!empty($row) && $do_extra_allowed_items) {
		    $row['promoTypeName'] = lang($row['promoTypeName']);
			$row['playerLevels'] = $this->getAllowedPlayerLevels($row['promorulesId']);
			$row['playerLevels'] = empty($row['playerLevels']) ? array() : $row['playerLevels'];

            if($enabled_log_OGP29899_performance_trace){
                $BM->mark('performance_trace_time_843');
            } // EOF if($enabled_log_OGP29899_performance_trace){...

			$row['affiliates'] = $this->getAllowedAffiliates($row['promorulesId']);
			$row['affiliates'] = empty($row['affiliates']) ? array() : $row['affiliates'];

            if($enabled_log_OGP29899_performance_trace){
                $BM->mark('performance_trace_time_850');
            } // EOF if($enabled_log_OGP29899_performance_trace){...

			$row['agents'] = $this->getAllowedAgents($row['promorulesId']);
			$row['agents'] = empty($row['agents']) ? array() : $row['agents'];

            if($enabled_log_OGP29899_performance_trace){
                $BM->mark('performance_trace_time_857');
            } // EOF if($enabled_log_OGP29899_performance_trace){...

			$row['players'] = $this->getAllowedPlayers($row['promorulesId']);
			$row['players'] = empty($row['players']) ? array() : $row['players'];

            if($enabled_log_OGP29899_performance_trace){
                $BM->mark('performance_trace_time_864');
            } // EOF if($enabled_log_OGP29899_performance_trace){...

			$row['gameType'] = $this->getAllowedGameType($row['promorulesId']);

            if($enabled_log_OGP29899_performance_trace){
                $BM->mark('performance_trace_time_871');
            } // EOF if($enabled_log_OGP29899_performance_trace){...

            $row['gameBetCondition'] = $this->getGameBetCondition($row['promorulesId']);

            if($enabled_log_OGP29899_performance_trace){
                $BM->mark('performance_trace_time_876');
            } // EOF if($enabled_log_OGP29899_performance_trace){...


			if (empty($row['releaseToSubWallet'])) {
				$row['releaseToSubWallet'] = 0;
			}

            $row['claimBonusPeriodDayArr'] = $this->adjust_claim_bonus_period_day($row['claim_bonus_period_day']);

			$row = $this->updateJsonToObj($row);
		}

        if( $enabled_log_OGP29899_performance_trace ){
            $elapsed_time = [];
            // 834_828, runOneRowArray()
            $elapsed_time['828_834'] = $BM->elapsed_time('performance_trace_time_828', 'performance_trace_time_834');

            $_marker_key_list = array_keys( $BM->marker);
            if( in_array('performance_trace_time_843', $_marker_key_list) ){
                // 843_834, getAllowedPlayerLevels()
                $elapsed_time['834_843'] = $BM->elapsed_time('performance_trace_time_834', 'performance_trace_time_843');
            }
            if( in_array('performance_trace_time_850', $_marker_key_list) ){
                // 850_843, getAllowedAffiliates()
                $elapsed_time['843_850'] = $BM->elapsed_time('performance_trace_time_843', 'performance_trace_time_850');
            }
            if( in_array('performance_trace_time_857', $_marker_key_list) ){
                // 857_850, getAllowedAgents()
                $elapsed_time['850_857'] = $BM->elapsed_time('performance_trace_time_850', 'performance_trace_time_857');
            }
            if( in_array('performance_trace_time_864', $_marker_key_list) ){
                // 864_857, getAllowedPlayers()
                $elapsed_time['857_864'] = $BM->elapsed_time('performance_trace_time_857', 'performance_trace_time_864');
            }
            if( in_array('performance_trace_time_871', $_marker_key_list) ){
                // 871_864, getAllowedGameType()
                $elapsed_time['864_871'] = $BM->elapsed_time('performance_trace_time_864', 'performance_trace_time_871');
            }
            if( in_array('performance_trace_time_876', $_marker_key_list) ){
                // 876_871, getGameBetCondition()
                $elapsed_time['871_876'] = $BM->elapsed_time('performance_trace_time_871', 'performance_trace_time_876');
            }

            $this->utils->debug_log('viewPromoRuleDetails elapsed_time:', $elapsed_time
                                    , 'promoruleId:', $promoruleId
                                    , 'filter_deleted_rule:', $filter_deleted_rule
                                    , 'enabled_isAllowedPlayerBy_with_lite:', $this->config->item('enabled_isAllowedPlayerBy_with_lite')
                                );
            $elapsed_time = [];
            unset($elapsed_time);
        }
		return $row;

		// $query = $this->db->get();

		// if ($query->num_rows() > 0) {
		// 	foreach ($query->result_array() as $row) {
		// 		// $row['applicationPeriodStart'] = mdate('%Y-%m-%dT%h:%i', strtotime($row['applicationPeriodStart']));
		// 		//$row['applicationPeriodEnd'] = mdate('%Y-%m-%dT%h:%i', strtotime($row['applicationPeriodStart']));
		// 		// $row['createdOn'] = mdate('%M %d, %Y ', strtotime($row['createdOn']));
		// 		$row['playerLevels'] = $this->getAllowedPlayerLevels($row['promorulesId']);
		// 		$row['gameType'] = $this->getAllowedGameType($row['promorulesId']);
		// 		$row['gameBetCondition'] = $this->getGameBetCondition($row['promorulesId']);
		// 		$row = $this->updateJsonToObj($row);
		// 		// $row['gameRecordStartDate'] = mdate('%Y-%m-%dT%h:%i', strtotime($row['gameRecordStartDate']));
		// 		// $row['gameRecordEndDate'] = mdate('%Y-%m-%dT%h:%i', strtotime($row['gameRecordEndDate']));
		// 		// $row['hide_date'] = mdate('%Y-%m-%dT%h:%i', strtotime($row['hide_date']));
		// 		// if ($row['updatedOn'] != null) {
		// 		// 	$row['updatedOn'] = mdate('%M %d, %Y - %h:%i:%s %A', strtotime($row['updatedOn']));
		// 		// }

		// 		$data[] = $row;
		// 	}
		// 	//var_dump($data);exit();
		// 	return $data;
		// }
		// return false;
	}

	/**
	 * overview : get promo rules levels
	 *
	 * @param $promoruleId
	 * @return array|bool
	 */
	public function getPromoRuleLevels($promoruleId) {
		$this->db->select('vipsettingcashbackrule.vipsettingcashbackruleId')->from('promorulesallowedplayerlevel');
		$this->db->join('vipsettingcashbackrule', 'vipsettingcashbackrule.vipsettingcashbackruleId = promorulesallowedplayerlevel.playerLevel', 'left');
		$this->db->join('vipsetting', 'vipsetting.vipSettingId = vipsettingcashbackrule.vipSettingId', 'left');
		$this->db->where('promoruleId', $promoruleId);
		$query = $this->db->get();
		if ($query->num_rows() > 0) {
			foreach ($query->result_array() as $row) {
				$data[] = $row['vipsettingcashbackruleId'];
			}
			return $data;
		}
		return false;
	}

	/**
	 * overview : get promo type
	 * @return bool
	 */
	public function getPromoType() {
		$qry = "SELECT promotype.*,admin1.username AS createdBy, admin2.username AS updatedBy
			FROM promotype
			LEFT JOIN adminusers AS admin1
			ON admin1.userId = promotype.createdBy
			LEFT JOIN adminusers AS admin2
			ON admin2.userId = promotype.updatedBy
			where promotype.promoTypeName != ?
			AND promotype.deleted <> 1
			ORDER BY promotype.promotypeId DESC";
		$query = $this->db->query($qry, array(self::SYSTEM_MANUAL_PROMO_TYPE_NAME));

		if ($query->num_rows() > 0) {
			$data = $query->result_array();
			// foreach ($query->result_array() as $row) {
			// 	$row['createdOn'] = mdate('%M %d, %Y %h:%i:%s %A', strtotime($row['createdOn']));
			// 	$row['updatedOn'] = mdate('%M %d, %Y %h:%i:%s %A', strtotime($row['updatedOn']));
			// 	$data[] = $row;
			// }
			return $data;
		}
		return false;
	}

	/**
	 * overview : get allowed game type
	 *
	 * @param $promoruleId
	 * @return array|bool
	 */
	public function getAllowedGameType($promoruleId) {
		$this->db->select('game.game')->from('promorulesgametype');
		$this->db->join('game', 'game.gameId = promorulesgametype.gameType', 'left');
		$this->db->where('promorulesgametype.promoruleId', $promoruleId);
		$query = $this->db->get();

		if ($query->num_rows() > 0) {
			foreach ($query->result_array() as $row) {
				$data[] = $row;
			}
			//var_dump($data);exit();
			return $data;
		}
		return false;
	}
	// public function getAllowedGameType($promoruleId) {
	// 	$this->db->select('game.game')->from('promorulesgametype');
	// 	$this->db->join('game', 'game.gameId = promorulesgametype.gameType', 'left');
	// 	$this->db->where('promorulesgametype.promoruleId', $promoruleId);
	// 	$query = $this->db->get();

	// 	if ($query->num_rows() > 0) {
	// 		foreach ($query->result_array() as $row) {
	// 			$data[] = $row;
	// 		}
	// 		//var_dump($data);exit();
	// 		return $data;
	// 	}
	// 	return false;
	// }

	/**
	 * overview : game bet condition
	 *
	 * @param  int	$promoruleId
	 * @return array|bool
	 */
	public function getGameBetCondition($promoruleId) {
		$this->db->select('game_description.game_name as gameName,game_description.game_code as gameCode,promorulesgamebetrule.betrequirement,game.game')->from('promorulesgamebetrule');
		$this->db->join('game_description', 'game_description.id = promorulesgamebetrule.game_description_id', 'left');
		$this->db->join('game', 'game.gameId = game_description.game_platform_id', 'left');
		$this->db->where('promorulesgamebetrule.promoruleId', $promoruleId);
		$query = $this->db->get();

		if ($query->num_rows() > 0) {
			foreach ($query->result_array() as $row) {
				$row['gameName'] = lang($row['gameName']);
				$data[] = $row;
			}
			//var_dump($data);exit();
			return $data;
		}
		return false;
	}

	// public function getGameBetCondition($promoruleId) {
	// 	$this->db->select('cmsgame.gameName,cmsgame.gameCode,promorulesgamebetrule.betrequirement,game.game')->from('promorulesgamebetrule');
	// 	$this->db->join('cmsgame', 'cmsgame.cmsGameId = promorulesgamebetrule.cmsgameId', 'left');
	// 	$this->db->join('game', 'game.game = cmsgame.gameCompany', 'left');
	// 	$this->db->where('promorulesgamebetrule.promoruleId', $promoruleId);
	// 	$query = $this->db->get();

	// 	if ($query->num_rows() > 0) {
	// 		foreach ($query->result_array() as $row) {
	// 			$data[] = $row;
	// 		}
	// 		//var_dump($data);exit();
	// 		return $data;
	// 	}
	// 	return false;
	// }

	/**
	 * overview : get condition amount
	 *
	 * @param $playerpromoId
	 * @return null
	 */
	public function getConditionAmount($playerpromoId) {
		$data = $this->getPromoRulesId($playerpromoId);
		return $this->viewPromoRuleDetails($data['promorulesId']);
	}

	/**
	 * overview : check player level
	 *
	 * @param $playerId
	 * @param $promorulesId
	 * @return bool
	 */
	public function checkPromoLevelRule($playerId, $promorulesId) {
		$this->db->select('promoruleId')->from('promorulesallowedplayerlevel')
			->join('player', 'player.levelId = promorulesallowedplayerlevel.playerLevel');
		$this->db->where('player.playerId', $playerId);
		$this->db->where('promorulesallowedplayerlevel.promoruleId', $promorulesId); //request status

		$query = $this->db->get();

		if ($query->num_rows() > 0) {
			return TRUE;
		}
		return false;
	}

	/**
	 * overview : get promo details
	 * @param $promorulesId
	 * @return array
	 */
	public function getPromoDetails($promorulesId) {
		$this->db->select('*')
			->from('promorules');
		$this->db->where('promorulesId', $promorulesId);
		$this->db->where('promorules.deleted_flag IS NULL', null);

		$query = $this->db->get();

		return $query->result_array();
	}

	/**
	 * get the promo detail with the formula info.
	 *
	 * @param integer $promorulesId the field, "promorules.promorulesId".
	 * @return array The promorules row data.
	 *
	 */
	public function getPromoDetailsWithFormulas($promorulesId){
		$thePromoDetails = $this->getPromoDetails($promorulesId);
		$thePromoDetail = [];
		if( ! empty($thePromoDetails) ){
			$thePromoDetail = $thePromoDetails[0];
// $this->utils->debug_log('527.958.thePromoDetail:', $thePromoDetail);
			// $bonusReleaseRule = null;
			// if( isset($thePromoDetail['bonusReleaseRule'])){
			// 	$bonusReleaseRule = $thePromoDetail['bonusReleaseRule'];
			// }
			$formula = [];
			if( ! empty($thePromoDetail['formula']) ){
				$formula = json_decode($thePromoDetail['formula'], true);
// $this->utils->debug_log('527.974.formula:', $formula);
				if( ! empty($formula['bonus_release']) ){
					$bonus_release = $this->utils->json_decode_handleErr($formula['bonus_release'], true);
					if( ! is_null($bonus_release) ) {
						$formula['bonus_release'] = $bonus_release;
					}
				}

				if( ! empty($formula['withdraw_condition']) ){
					$withdraw_condition = json_decode($formula['withdraw_condition'], true);
					$formula['withdraw_condition'] = $withdraw_condition;
				}

				if( ! empty($formula['bonus_condition']) ){
					$bonus_condition = json_decode($formula['bonus_condition'], true);
					$formula['bonus_condition'] = $bonus_condition;
				}

			}
			$thePromoDetail['formula'] = $formula;
// $this->utils->debug_log('527.985.formula:', $formula);
		}
		return $thePromoDetail;
	} // EOF getPromoDetailsWithFormulas


	/**
	 * Add Data into insvr_log Without Response.
	 *
	 * @param integer $thePlayerId The field, player.playreId .
	 * @param JSON $theRequest The field, insvr_log.request and data type Must be JSON string.
	 * @param array $gameDescriptionIdList The field, "game_description.id" array and Recommend match the GameKeyNames of $theRequest.
	 * @param string $uriKey CAABM = CreateAndApplyBonusMulti
	 * @param boolean $isTestUri The switch for test and live target URI.
	 * @return integer|false The field,insvr_log.id
	 */
	public function addInsvrLogWithoutResp($thePlayerId, $theRequest = '', $gameDescriptionIdList = [], $uriKey = 'CAABM', $isTestUri = true){
		$this->load->library(['insvr_api']);
		$this->load->model(['insvr_log']);
		/// Two Phase for add data without resp. into insvr_log.
		$url = $this->insvr_api->getUriWithKey($uriKey, $isTestUri);
		$insvr_log_data = [];
		if( ! empty($theRequest) ){
			if( ! $this->utils->isValidJson($theRequest) ){
				$theRequest = json_encode($theRequest);
			}
			$insvr_log_data['request'] = $theRequest;
		}

		$insvr_log_data['uri'] = $url;
		$insvr_log_data['gameDescriptionIdList'] = $gameDescriptionIdList;
		$insvr_log_data['player_id'] = $thePlayerId;
		// $insvr_log_data['playerpromo_id'] = $thePlayerPromoId;
		$insvr_log_id = $this->insvr_log->add($insvr_log_data);
		return $insvr_log_id;
	} // EOF addInsvrLogWithoutResp

	/**
	 * Update data,"response" with the field,insvr_log.id
	 *
	 * @param integer $theInsvrLogId The field,insvr_log.id
	 * @param string $theResponse The field,insvr_log.response. The data type Recommend be JSON string.
	 * @param integer $thePlayerPromoId The field, "playerpromo.playerpromoId".
	 * @return void
	 */
	public function updateInsvrLogWithResp($theInsvrLogId, $theResponse, $thePlayerPromoId = 0, $others = []){
		$this->load->model(['insvr_log']);
		$insvr_log_data = [];
		$_response = $theResponse;
		if( ! $this->utils->isValidJson($theResponse) ){
			$_response = json_encode($theResponse);
		}
		$insvr_log_data['response'] = $_response;
		if( ! empty($thePlayerPromoId) ){
			$insvr_log_data['playerpromo_id'] = $thePlayerPromoId;
		}
		if( ! empty( $others ) ){
			$insvr_log_data = array_merge($insvr_log_data, $others);
		}
		$affected_rows = $this->insvr_log->update($theInsvrLogId, $insvr_log_data);
		return $affected_rows;
	}// EOF updateInsvrLogWithResp


	/**
	 * Send Request to insvr By Game Description
	 *
	 * @param integer $theGameDescriptionId The field,"game_description.id".
	 * @param integer $thePlayerId The field, "player.playerId".
	 * @param array $theSettings4CAABM The Settings array for CAABM API (CAABM = CreateAndApplyBonusMulti).
	 * @return array The array format,
	 * - array[0] string $header The header of the response.
	 * - array[1] string $content The body of the response.
	 * - array[2] string $url The target URI.
	 * - array[3] string the header of the response.
	 */
	public function send2InsvrByGameDescription($theGameDescriptionId, $thePlayerId, $theSettings4CAABM = [] ){
		$this->load->library(['insvr_api']);
		$this->load->model(['game_provider_auth','insvr_log','game_description_model']);

		$this->insvr_api->reset();
		$this->insvr_api->CAABM_updateSetting($theSettings4CAABM);

		$gameDescriptionDetail = (array)$this->game_description_model->getGameDescription($theGameDescriptionId);

		if( !  empty($gameDescriptionDetail['game_platform_id']) ){
			$gamePlatformId = $gameDescriptionDetail['game_platform_id'];

			// theGameDescriptionId
			// getGameCodeByGameDescriptionId
			// getGamePlatformIdByGameDescriptionId
			// getGameDescription
			$username = $this->game_provider_auth->getGameUsernameByPlayerId($thePlayerId, $gamePlatformId); // $playerId,
			$this->insvr_api->CAABM_addPlayerToSettings($username);
		}

		if( ! empty($gameDescriptionDetail['game_code']) ){
			$gameCode = $gameDescriptionDetail['game_code'];
			$this->insvr_api->CAABM_addGameKeyNameToSettings($gameCode); // game_description.game_code
		}

		// send to insvr
		list($header, $content, $url, $params) = $this->insvr_api->send2Insvr();
// $this->utils->debug_log('527.respResult', $header, $content, $url, $params);
		return [$header, $content, $url, $params];
	} // EOF send2InsvrByGameDescription

	/**
	 * Send To Insvr for CreateAndApplyBonusMulti per Games(game_description.id)
	 *
	 * @param integer $thePromorulesId The field, "promorules.promorulesId".
	 * @param integer $thePlayerId The field, "player.playerId".
	 * @param integer $thePlayerPromoId The field, "playerpromo.playerpromoId".
	 * @return void
	 */
	function send2Insvr4CreateAndApplyBonusMultiPreGameDescription($thePromorulesId, $thePlayerId, $thePlayerPromoId = null){
		$promoDetail = $this->getPromoDetailsWithFormulas($thePromorulesId);

		$isAutoReleaseBonus = false;
		if( Promorules::BONUS_RELEASE_TO_PLAYER_AUTO == $promoDetail['bonusReleaseToPlayer'] ){
			$isAutoReleaseBonus = true;
		}

		$settings4CAABM = [];// CAABM = CreateAndApplyBonusMulti
		$isTestUri = true; // default
		if( ! empty( $promoDetail['formula']['bonus_release'][Promorules::CREATE_AND_APPLY_BONUS_MULTI] ) ){
			// append BrandId and APIKey from configure file, "".
			$configInsvr = $this->config->item('insvr');
			if( !empty($configInsvr) ) {
				$settings4CAABM['BrandId'] = $configInsvr['BrandId'];
				$settings4CAABM['APIKey'] = $configInsvr['APIKey'];
				$isTestUri = $configInsvr['isTestUri'];
			}
			$_settings4CAABM = $promoDetail['formula']['bonus_release'][Promorules::CREATE_AND_APPLY_BONUS_MULTI];
			$settings4CAABM = array_merge($settings4CAABM, $_settings4CAABM);
		}

		$theGameDescriptionList = [];
		// parse the _GameKeyNames key array and add into $theGameDescriptionList for send to insvr.
		if( ! empty($settings4CAABM['_GameKeyNames']) ){
			$_GameKeyNames = $settings4CAABM['_GameKeyNames'];
			$_GameDescriptionList = $this->convert_GameKeyNames2Codea($_GameKeyNames);
			// @todo game_description
			$byKeyName = 'game_description_id';
			$theGameDescriptionList = $this->addGameDescriptionIntroList($_GameDescriptionList, $theGameDescriptionList, $byKeyName);

		}

		if($isAutoReleaseBonus){
			$uriKey = 'CAABM'; // CAABM = CreateAndApplyBonusMulti
			foreach($theGameDescriptionList as $theGameDescription){
				$gameDescriptionIdList = [ $theGameDescription['game_description_id'] ];
				$theRequest = ''; //$settings4CAABM; // disable for avoid duplication with a new game_description_id.
				$theInsvrLogId = $this->addInsvrLogWithoutResp($thePlayerId, $theRequest, $gameDescriptionIdList, $uriKey, $isTestUri);

				list($header, $content, $url, $params) =$this->send2InsvrByGameDescription($theGameDescription['game_description_id'], $thePlayerId, $settings4CAABM );

				$theResponse = $content;
				// $thePlayerPromoId = $thePlayerPromoId;
				$others = [];
				$others['request'] = $params;
// $this->utils->debug_log('527.will updateInsvrLogWithResp:"', $theInsvrLogId, $theResponse, $thePlayerPromoId, $others);
				$this->updateInsvrLogWithResp($theInsvrLogId, $theResponse, $thePlayerPromoId, $others);
			}// EOF if($isAutoReleaseBonus){...
		} // EOF if($isAutoReleaseBonus){...

	} // EOF send2Insvr4CreateAndApplyBonusMultiPreGameDescription

	/**
	 * Add the new array into the original array and unique the element by the key string of the element.
	 * Example, reference to http://sandbox.onlinephpfunctions.com/code/5bfb0db826c77467775b09bd6027864c3c55aeb5
	 *
	 * @param array $addList The array will be added into $theOrigList. Recomment the element Must be contains the key string,"$byKeyName".
	 * @param array $theOrigList The original array (Recomment the element Must be contains the key string,"$byKeyName".)
	 * @param string $byKeyName
	 * @return void
	 */
	public function addGameDescriptionIntroList($addList, $theOrigList, $byKeyName='game_description_id'){
		$merged = array_merge($theOrigList, $addList);
		$uniqueIdList = array_unique(array_column( $merged, $byKeyName));
		$uniqueMerged = [];
		foreach($uniqueIdList as $uniqueId){
			$key = array_search($uniqueId, array_column($merged, $byKeyName));
			$uniqueMerged[] = $merged[$key];
		}
		return $uniqueMerged;
	} // EOF addGameDescriptionIntroList

	/**
	 * convert _GameKeyNames (means the game_description_id list) array to the game_description Intro List.
	 *
	 * @param array $_GameKeyNames The game_description_id,"game_description.id" list.
	 * @return array $gameDescriptionIntroList The game_description intro data, just contains game_code, game_description_id and game_platform_id.
	 */
	public function convert_GameKeyNames2Codea($_GameKeyNames){
		$this->load->model(['game_description_model']);
		$gameDescriptionIntroList =[];
		foreach( $_GameKeyNames as $indexNumber => $theGameDescriptionId){
			$gameDescriptionDetail = (array)$this->game_description_model->getGameDescription($theGameDescriptionId);
			if( ! empty($gameDescriptionDetail) ){
				$gameDescriptionIntroList[$indexNumber]['game_code'] = $gameDescriptionDetail['game_code'];
				$gameDescriptionIntroList[$indexNumber]['game_description_id'] = $gameDescriptionDetail['id'];
				$gameDescriptionIntroList[$indexNumber]['game_platform_id'] = $gameDescriptionDetail['game_platform_id'];
			}
		}
		return $gameDescriptionIntroList;
	} // EOF convert_GameKeyNames2Codea

	/**
	 * overview : get promo rule row
	 *
	 * @param $promorulesId
	 * @return array|null|object
	 */
	public function getPromoRuleRow($promorulesId) {
		$this->db->from('promorules')->where('promorulesId', $promorulesId);
		$this->db->where('promorules.deleted_flag IS NULL', null);

		$row = $this->runOneRowArray();
		$row = $this->updateJsonToObj($row);
		return $row;
	}


    /**
     * overview: disable apply promo by manually change hidden promocms id in player center promo page
     * @param $promoCmsSettingId
     * @param $extra_info
     * @param int $order_generated_by
     * @return bool
     */
    public function isPlayerTryToApplyNotDisplayPromo($promoCmsSettingId, $extra_info, $order_generated_by = Player_promo::ORDER_GENERATED_BY_PLAYER_CENTER_PROMOTION_PAGE){
        $result = false;

	    $promoCms = $this->getAllPromo(null, null, $promoCmsSettingId);
        if(!empty($promoCms)){
            $isDisplayApplyBtnInPromoPage = (int)$promoCms[0]['display_apply_btn_in_promo_page'];
            if(!$isDisplayApplyBtnInPromoPage){
                if(!empty($extra_info['order_generated_by']) && ($extra_info['order_generated_by'] == $order_generated_by)){
                    $result = true;
                }
            }
        }

        return $result;
	}

	/**
	 * overview : update promo rules
	 *
	 * @param array $data
	 */
	public function editPromoRules($data) {

		$data = $this->packToJsonInfo($data);

		$this->db->where('promorulesId', $data['promorulesId']);
		$this->db->update('promorules', $data);
	}

	/**
	 * overview : clear promo items
	 *
	 * @param  int	$promorulesId
	 * @return bool
	 */
	public function clearPromoItems($promorulesId) {
		$this->db->delete('promorulesallowedplayerlevel', array('promoruleId' => $promorulesId));
		$this->db->delete('promorulesgamebetrule', array('promoruleId' => $promorulesId));
		$this->db->delete('promorulesgametype', array('promoruleId' => $promorulesId));

		if ($this->db->affected_rows() == '1') {
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * overview : add promo rules
	 *
	 * @param $data
	 * @return bool
	 */
	public function addPromoRules($data) {

		$data = $this->packToJsonInfo($data);

		$this->db->insert('promorules', $data);

		//checker
		if ($this->db->affected_rows() == '1') {
			//return TRUE;
			return $this->db->insert_id();
		}

		return FALSE;
	}

	/**
	 * overview : add game requirements
	 *
	 * @param $data
	 */
	public function addGameRequirements($data) {
		$this->db->insert('promorulesgamebetrule', $data);
	}

	/**
	 * overview : add promo rule allowed player level
	 * @param $data
	 */
	public function addPromoRuleAllowedPlayerLevel($data) {
		$this->db->insert('promorulesallowedplayerlevel', $data);
	}

	/**
	 * overview : get promo rule games
	 *
	 * @param $promoruleId
	 * @return array|bool
	 */
	public function getPromoRuleGamesType($promoruleId) {
		$this->db->select('promorulesgamebetrule.game_description_id,game_description.game_type_id')->from('promorulesgamebetrule');
		$this->db->join('game_description', 'game_description.id = promorulesgamebetrule.game_description_id', 'left');
		$this->db->join('game_type', 'game_type.id = game_description.game_type_id', 'left');
		$this->db->where('promoruleId', $promoruleId);
		$this->db->group_by('game_description.game_type_id');
		$query = $this->db->get();
		if ($query->num_rows() > 0) {
			foreach ($query->result_array() as $row) {
				$data[] = $row['game_type_id'];
			}
			return $data;
		}
		return false;
	}

	/**
	 * Get the Game tree By promorules.promorulesId
	 *
	 * @param integer $ruleId The field, "promorules.promorulesId".
	 * @param array $allowPlatformIdList The field,"external_system.id" aka "game_description.game_platform_id".
	 * @return void
	 */
	public function getGameTreeByGameDescriptionList($selectedGameDescriptionList = [], $allowPlatformIdList = null) {

		$this->load->model(array('game_description_model'));

		$gameDescriptionList = [];

		if(! empty($selectedGameDescriptionList) ){
			$gameDescriptionList = $this->game_description_model->getGameDescriptionByIdList( $selectedGameDescriptionList );
		}

		if( ! empty($gameDescriptionList) ) {
			foreach($gameDescriptionList as $indexNumber => $gameDescription ){
				$gamePlatformList[$gameDescription['game_platform_id']] = '';
				$gameTypeList[$gameDescription['game_type_id']] = '';
				$gameDescList[$gameDescription['id']] = '';
			}
		}else{
			$gamePlatformList = [];
			$gameTypeList = [];
			$gameDescList = [];
		}

		$showGameDescTree = $this->config->item('show_particular_game_in_tree');

		// defaults
		$percentage = false;
		$filterColumn = [];
		// $allowPlatformIdList = [];
		return $this->game_description_model->getGameTreeArray( $gamePlatformList // # 1
																, $gameTypeList // # 2
																, $gameDescList // # 3
																, $percentage // # 4
																, $showGameDescTree // # 5
																, $filterColumn // # 6
																, $allowPlatformIdList // # 7
															);
	} // EOF getGameTreeByPromoruleId

	/**
	 * overview : get promo rules games
	 * @param  int	$promoruleId
	 * @return array|bool
	 */
	public function getPromoRuleGames($promoruleId) {
		$this->db->select('promorulesgamebetrule.game_description_id')->from('promorulesgamebetrule');
		$this->db->join('game_description', 'game_description.id = promorulesgamebetrule.game_description_id', 'left');
		$this->db->join('game_type', 'game_type.id = game_description.game_type_id', 'left');
		$this->db->where('promoruleId', $promoruleId);

		$query = $this->db->get();
		if ($query->num_rows() > 0) {
			foreach ($query->result_array() as $row) {
				$data[] = $row['game_description_id'];
			}
			return $data;
		}
		return false;
	}

	/**
	 * overview : get promo rule games provider
	 *
	 * @param $promoruleId
	 * @return array|bool
	 */
	public function getPromoRuleGamesProvider($promoruleId) {
		$this->db->select('game_type.game_platform_id')->from('promorulesgamebetrule');
		$this->db->join('game_description', 'game_description.id = promorulesgamebetrule.game_description_id', 'left');
		$this->db->join('game_type', 'game_type.id = game_description.game_type_id', 'left');
		$this->db->where('promoruleId', $promoruleId);
		$this->db->group_by('game_type.game_platform_id');
		$query = $this->db->get();
		if ($query->num_rows() > 0) {
			foreach ($query->result_array() as $row) {
				$data[] = $row['game_platform_id'];
			}
			return $data;
		}
		return false;
	}

	/**
	 * overview : get promo rule
	 *
	 * @param  int	$promorulesId
	 * @return mixed
	 */
	public function getPromorule($promorulesId) {
		$this->db->from('promorules');
		$this->db->join('promotype', 'promotype.promotypeId = promorules.promoCategory', 'left');
		$this->db->where('promorulesId', $promorulesId);
		$this->db->where('promorules.deleted_flag IS NULL', null);

		$query = $this->db->get();

		return $query->row_array();
	}

	#get promo rules by promotype.promotypeId
	public function getPromorulesByPromoTypeId($promotypeId) {
		$this->db->from('promorules');
		$this->db->join('promotype', 'promotype.promotypeId = promorules.promoCategory', 'left');
		$this->db->where('promotype.promotypeId', $promotypeId);
		$this->db->where('promorules.deleted_flag IS NULL', null);

		$query = $this->db->get();

		return $query->result_array();
	}

	public function getPromoTypeIdByPromocmsId($promocmsId) {
		$this->db->select('promotype.promotypeId promotypeId')->from('promocmssetting');
		$this->db->join('promorules', 'promocmssetting.promoId = promorules.promorulesId');
		$this->db->join('promotype', 'promorules.promoCategory = promotype.promotypeId');
		$this->db->where('promocmssetting.promoCmsSettingId', $promocmsId);
		$this->db->where('promocmssetting.deleted_flag IS NULL', null);
		$this->db->where('promorules.deleted_flag IS NULL', null);

		return $this->runOneRowOneField('promotypeId');
	}

	/**
	 * overview : get all promo category
	 *
	 * @return array
	 */
	public function getAllPromoCategory() {
		$this->db->select('*')->from('promotype');
		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * overview : print debug information
	 *
	 * @param array	$msgArr
	 */
	public function printDebugInfo($msgArr) {
		// if ($this->getConfig('debug_promo')) {
		$this->utils->debug_log($msgArr);
		// }
	}

	/**
	 * overview : update json to object
	 *
	 * @param array $promorule
	 * @return array|object
	 */
	public function updateJsonToObj($promorule) {
		$isArr = false;
		if (!empty($promorule)) {
			if (is_array($promorule)) {
				$promorule = (object) $promorule;
				$isArr = true;
			}

			$json_info = $promorule->json_info;
			if (!empty($json_info)) {
				$jsonArr = json_decode($json_info, true);
				foreach ($jsonArr as $key => $value) {
					$promorule->$key = $value;
				}
			}
		}

		if ($isArr) {
			$promorule = (array) $promorule;
		}

		return $promorule;
	}

	/**
	 * overview : pack to json info
	 *
	 * @param array $promorule
	 * @return array|object
	 */
	public function packToJsonInfo($promorule) {
		$isArr = false;
		if (is_array($promorule)) {
			$promorule = (object) $promorule;
			$isArr = true;
		}
		$json_arr = array();
		foreach (self::FIELDS as $key) {
			if (isset($promorule->$key)) {
				$json_arr[$key] = $promorule->$key;
			}
		}

		$promorule->json_info = json_encode($json_arr);
		if ($isArr) {
			$promorule = (array) $promorule;
		}

		return $promorule;
	}

	/**
	 * overview : sync to json info
	 *
	 * @param $promorule
	 * @return bool
	 */
	public function syncToJsonInfo($promorule) {
		$promorule = $this->packToJsonInfo($promorule);

		$this->db->where('promorulesId', $promorule->promorulesId)
			->set('json_info', $promorule->json_info);
		return $this->runAnyUpdate($this->tableName);
	}

	/**
	 * overview : get first non deposit promo by type
	 *
	 * @param $nonDepositPromoType
	 * @return null
	 */
	public function getFirstNonDepositPromoByType($nonDepositPromoType) {
		// $this->load->library(array('language_function'));
		// $language = $this->language_function->getCurrentLangForPromo();
		$this->db->select(array(
			'promocmssetting.promoCmsSettingId',
			'promocmssetting.promoName',
			'promocmssetting.promoDescription',
			'promocmssetting.promoDetails',
			'promocmssetting.promoThumbnail',
			'promocmssetting.promoId',
			'promocmssetting.status',
			'promorules.promorulesId',
		));
		$this->db->from('promocmssetting')
			->join('promorules', 'promorules.promorulesId=promocmssetting.promoId');
		$this->db->where('promocmssetting.status', 'active');
		// $this->db->where('promocmssetting.language', $language);
		$this->db->where('promorules.promoType', self::PROMO_TYPE_NON_DEPOSIT);
		$this->db->where('promorules.nonDepositPromoType', $nonDepositPromoType);
		// git issue #1371
		$this->db->where('promocmssetting.deleted_flag IS NULL', null, false);
		$this->db->where('promorules.deleted_flag IS NULL', null);
		// $this->db->order_by('promoCmsSettingId', 'DESC');
		// if ($limit != null) {
		// 	if ($offset != null && $offset != 'undefined') {
		// 		$this->db->limit($limit, $offset);
		// 	} else {
		// 		$this->db->limit($limit);
		// 	}
		// }

		return $this->runOneRowArray();
		// $query = $this->db->get();
		// $result = $query->result_array();
		// return $result;
	}

	/**
	 * overview : get promo rule by promo cms
	 *
	 * @param $promoCmsSettingId
	 * @return array
	 */
	public function getPromoruleByPromoCms($promoCmsSettingId) {
		$this->db->select('promorules.*')->from('promorules')
			->join('promocmssetting', 'promocmssetting.promoId=promorules.promorulesId')
			->where('promocmssetting.promoCmsSettingId', $promoCmsSettingId)
			->where('promocmssetting.deleted_flag IS NULL', null, false)
			->where('promorules.deleted_flag IS NULL', null);

		$row = $this->runOneRowArray();
		if ($row) {
			$this->updateJsonToObj($row);
		}
		return $row;
	}

	/**
	 * @param  int	$promorulesId
	 * @return array
	 */
	public function getPromoruleById($promorulesId) {
		$this->db->select('promorules.*')->from('promorules')
			->where('promorulesId', $promorulesId);

		$row = $this->runOneRowArray();
		if ($row) {
			$this->updateJsonToObj($row);
		}
		return $row;
	}

	// public function getPlayerGames($promoId) {
	// 	$this->db->select('game_description_id')->from('promorulesgamebetrule');
	// 	$this->db->where('promoruleId', $promoId);
	// 	$qry = $this->db->get();
	// 	if ($qry && $qry->num_rows() > 0) {
	// 		foreach ($qry->result_array() as $row) {
	// 			$data[] = $row['game_description_id'];
	// 		}
	// 		return $data;
	// 	}

	// 	return null;
	// }

	/**
	 * overview : get all promo rule list
	 * @return array
	 */
	public function getAllPromorulesList($hide_system = true) {
		$this->db->select('promorulesId as id, promoName as label')
                 ->from('promorules')
		         ->where('deleted_flag IS NULL', null);

        if ($hide_system) {
            $this->db->where('promoName !=', self::SYSTEM_MANUAL_PROMO_RULE_NAME);
        }

        if ($this->utils->getConfig('hide_expired_and_inactive_promo_in_report')) {
            $this->db->where('hide_date >=', $this->utils->getNowForMysql());
            $this->db->where('status !=', '1');  // 0: active ; 1: inactive
        }

        if ($this->utils->getConfig('sort_by_promoName_in_report')) {
            $this->db->order_by("promoName", 'asc');
        } else {
            $this->db->order_by('promorulesId', 'desc');
        }

		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * overview : get all promo type list
	 *
	 * @return array
	 */
	public function getAllPromoTypeList() {
		$this->db->select('promotypeId as id,promoTypeName as label')->from('promotype');

		return $this->runMultipleRowArray();
	}

	/**
	 * overview : get system manual promo type
	 *
	 * @return array
	 */
	public function getSystemManualPromoType() {
		$this->db->from('promotype')->where('promoTypeName', self::SYSTEM_MANUAL_PROMO_TYPE_NAME);
		return $this->runOneRow();
	}

	/**
	 * overview : get system manual promo rule
	 *
	 * @return array
	 */
	public function getSystemManualPromoRule() {
		$this->db->from('promorules')->where('promoName', self::SYSTEM_MANUAL_PROMO_RULE_NAME);
		return $this->runOneRow();
	}

	/**
	 * overview : get system manual promo cms
	 *
	 * @return array
	 */
	public function getSystemManualPromoCMS() {
		$this->db->from('promocmssetting')->where('promoName', self::SYSTEM_MANUAL_PROMO_CMS_NAME);
		return $this->runOneRow();
	}

	/**
	 * overview : get system manual promo type id
	 *
	 * @return int
	 */
	public function getSystemManualPromoTypeId() {
		$row = $this->getSystemManualPromoType();
		if ($row) {
			return $row->promotypeId;
		}
		return 0;
	}

	/**
	 * overview :get system manual promo rule id
	 *
	 * @return int
	 */
	public function getSystemManualPromoRuleId() {
		$row = $this->getSystemManualPromoRule();
		if ($row) {
			return $row->promorulesId;
		}
		return 0;
	}

	/**
	 * overview : get system manual promo cms id
	 *
	 * @return int
	 */
	public function getSystemManualPromoCMSId() {
		$row = $this->getSystemManualPromoCMS();
		if ($row) {
			return $row->promoCmsSettingId;
		}
		return 0;
	}

	/**
	 * overview : get promo name and type
	 *
	 * @param $transaction_type
	 * @param $transPromoTypeName
	 * @param $promoTypeName
	 * @param $promoName
	 * @param $promoDetails
	 * @param $vipLevelName
	 * @return array
	 */
	public function getPromoNameAndType($transaction_type, $transPromoTypeName, $promoTypeName,
		$promoName, $promoDetails, $vipLevelName) {
		$this->load->model(array('transactions'));

		$promoName = $promoName;
		$promoType = $transPromoTypeName;
		$promoDetails = $promoDetails;
		switch ($transaction_type) {
		case Transactions::ADD_BONUS:
			//show promorules
			//show transPromoType
			if ($promoName == Promorules::SYSTEM_MANUAL_PROMO_RULE_NAME) {
				$promoName = lang('Manual Promotion');
				$promoType = $transPromoTypeName;

				$promoDetails = $transPromoTypeName;

			} else {
				// $row['promoName'] = $row['promoName'];
				$promoType = $promoTypeName;
				// $row['promoDetails'] = $row['vipLevelName'];

			}
			break;
		case Transactions::MEMBER_GROUP_DEPOSIT_BONUS:
			//show vip bonus
			$promoName = lang('VIP Group Bonus');
			$promoDetails = $vipLevelName;
			$promoType = lang('VIP Group Bonus');
			break;
		case Transactions::RANDOM_BONUS:
			//show random bonus
			$promoName = lang('Random Bonus'); //$row['vipLevelName'];
			$promoType = empty($transPromoTypeName) || $transPromoTypeName == self::SYSTEM_MANUAL_PROMO_TYPE_NAME ? lang('Random Bonus') : $transPromoTypeName;
			$promoDetails = $promoType;
			break;
		case Transactions::PLAYER_REFER_BONUS:
			$promoName = lang('Player Refer Bonus');
			$promoType = empty($transPromoTypeName) || $transPromoTypeName == self::SYSTEM_MANUAL_PROMO_TYPE_NAME ? lang('Player Refer Bonus') : $transPromoTypeName;
			$promoDetails = $promoType;
			break;
		}

		if ($promoType == self::SYSTEM_MANUAL_PROMO_TYPE_NAME) {
			$promoType = '';
		}

		if ($promoName == self::SYSTEM_MANUAL_PROMO_RULE_NAME) {
			$promoName = '';
		}

		// $this->utils->debug_log('promoName', $promoName, 'promoType', $promoType, 'promoDetails', $promoDetails);
		return array($promoName, $promoType, $promoDetails);
	}

	/**
	 * overview : get all promo rule
	 *
	 * @param bool|true $hide_system
	 * @return array
	 */
	public function getAllPromoRule($hide_system = true, $show_deleted = false, $search = []) {
		$this->load->model(array('player_promo'));
		$this->db->select('promorules.*,
						   promotype.promoTypeName,
						   admin1.userName as createdBy,
						   admin2.userName as updatedBy'
						);
		if (!$this->utils->getConfig('disabled_promo_bonus_release_count')){
			if(!$this->utils->getConfig('use_cronjob_for_promo_bonus_release_count')) {
				$this->db->select('count(playerpromo.promoRulesId) as bonusCount');
				$this->db->join('playerpromo', 'playerpromo.promoRulesId = promorules.promoRulesId and transactionStatus in (' . Player_promo::TRANS_STATUS_FINISHED_WITHDRAW_CONDITION .',' . Player_promo::TRANS_STATUS_APPROVED .')' , 'left');
			}
		}
		$this->db->from('promorules');
		$this->db->join("promotype", "promotype.promotypeId = promorules.promoCategory", 'left');
		// $this->db->join('promocmssetting', 'promorules.promorulesId = promocmssetting.promoId');
		$this->db->join('adminusers as admin1', 'admin1.userId = promorules.createdBy', 'left');
		$this->db->join('adminusers as admin2', 'admin2.userId = promorules.updatedBy', 'left');
		$this->db->group_by('promorules.promoRulesId');
		if (!$show_deleted) {
			$this->db->where('promorules.deleted_flag IS NULL', null);
		}

		if ($hide_system) {
			$this->db->where('promorules.promoName !=', self::SYSTEM_MANUAL_PROMO_RULE_NAME);
		}
        if(!empty($search)) {
            foreach($search as $key => $value) {
                $this->db->where($key, $value);
            }
        }
		// $this->runMultipleRowArray();
		// echo $this->db->last_query();exit();
		return $this->runMultipleRowArray();

		// $query = $this->db->get();

		// if ($query->num_rows() > 0) {
		// 	// foreach ($query->result_array() as $row) {
		// 	// 	$row['createdOn'] = $row['createdOn'];
		// 	// 	$row['updatedOn'] = $row['updatedOn'];
		// 	// 	$row['applicationPeriodStart'] = $row['applicationPeriodStart'];
		// 	// 	$row['applicationPeriodEnd'] = $row['applicationPeriodEnd'];

		// 	// 	$data[] = $row;
		// 	// }
		// 	// return $data;

		// 	return $query->result_array();
		// }
		// return false;
	}

	/**
	 * countReleaseBonus function
	 *
	 * @param datetime $lastSyncFrom (Y-m-d H:i:s)
	 * @param int $promoruleId (promorules.promorulesId)
	 * @return void
	 */
	public function getPromoRuleReleaseCount($lastSyncFrom, $promoruleId){
			$this->load->model(array('player_promo'));

			$this->db->select('count(playerpromo.promoRulesId) as bonusCount');
			$this->db->from('playerpromo');
			$this->db->where('playerpromo.promoRulesId', $promoruleId);
			$this->db->where('playerpromo.dateProcessed >=', $lastSyncFrom);
			$this->db->where_in('playerpromo.transactionStatus', [Player_promo::TRANS_STATUS_FINISHED_WITHDRAW_CONDITION, Player_promo::TRANS_STATUS_APPROVED]);
			$query = $this->db->get();
			return $this->getOneRowOneField($query, 'bonusCount');
	}

	/**
	 * updatePromoRuleReleaseCount function
	 * @param int $count
	 * @param int $promoruleId
	 */
	public function updatePromoRuleReleaseCount($count, $promoruleId){
		$this->db->where('promorulesId', $promoruleId);
		$this->db->set('bonusReleaseCount', $count);
		$this->db->set('syncReleaseCountAt', $this->utils->getNowForMysql());
		$this->db->update('promorules');
		return $this->db->affected_rows();
	}

	/**
	 * overview : get promo setting list
	 *
	 * @param int	$sort
	 * @param int	$limit
	 * @param int	$offset
	 * @return array
	 */
	public function getPromoSettingList($sort, $limit = null, $offset = null, $show_deleted = false, $orderBy = 'asc', $search = [], $hide_system = true) {
		$this->db->select('promocmssetting.*, promotype.promoTypeName, admin1.username AS createdBy, admin2.username AS updatedBy, promorules.promoName AS promoRuleName, promorules.promorulesId');
		$this->db->from('promocmssetting');
		$this->db->join('adminusers AS admin1', 'admin1.userId = promocmssetting.createdBy', 'left');
		$this->db->join('adminusers AS admin2', 'admin2.userId = promocmssetting.updatedBy', 'left');
		$this->db->join('promorules', 'promorules.promorulesId = promocmssetting.promoId', 'left');
		$this->db->join('promotype', 'promotype.promotypeId = promocmssetting.promo_category', 'left');

		if($hide_system){
			//hide system
			$this->db->where('promocmssetting.promoName !=', self::SYSTEM_MANUAL_PROMO_CMS_NAME);
		}

		// git issue #1371
		if (!$show_deleted) {
			$this->db->where('promocmssetting.deleted_flag IS NULL', null, false);
			$this->db->where('promorules.deleted_flag IS NULL', null);
		}

		$this->db->order_by($sort, $orderBy);
		if ($limit) {
			$this->db->limit($limit, $offset);
		}

        if(!empty($search)) {
            foreach($search as $key => $value) {
                $this->db->where($key, $value);
            }
        }
		$query = $this->db->get();
		$list = $query->result_array();

		foreach ($list as &$list_item) {
			if(!$hide_system && ($list_item['promoName'] == self::SYSTEM_MANUAL_PROMO_CMS_NAME)){
				$list_item['promoName'] = lang('promo.'. $list_item['promoName']);
			}
			$list_item['promoCmsCatId'] = $this->getPromoCmsCategory($list_item['promoCmsSettingId']);
		}

		return $list;
	}

	/**
	 * overview : get promo cms category
	 *
	 * @param  int	$promoCmsSettingId
	 * @return array
	 */
	public function getPromoCmsCategory($promoCmsSettingId) {
		$this->db->select('*');
		$this->db->from('promocmscategory');
		$this->db->where('promoCmsSettingId', $promoCmsSettingId);
		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * overview : get deposit promo
	 *
	 * @param bool|true $hide_timeout
	 * @param bool|true $hide_system
	 * @return mixed
	 */
	public function getDepositPromo($hide_timeout = true, $hide_system = true) {
		$this->db->select('promorulesId, promoName, promoCategory, promoType');
		$this->db->from('promorules');
		$this->db->where('promorules.deleted_flag IS NULL', null);
		if ($hide_system) {
			$this->db->where('promorules.promoName !=', self::SYSTEM_MANUAL_PROMO_RULE_NAME);
		}
		if ($hide_timeout) {
			$this->db->where('hide_date >', $this->utils->getNowForMysql());
		}

		$query = $this->db->get();
		return $query->result_array();
	}

	public function getUsablePromorules($hide_timeout = true, $hide_system = true, $promoList = []){
	    $result = [];

        $promorules = $this->getDepositPromo($hide_timeout, $hide_system);
        if(empty($promorules)){
            return $result;
        }

        $promoListKV = [];
        if(!empty($promoList)){
            foreach ($promoList as $data){
                $promoListKV[$data['promorulesId']] = '';
            }
        }

        foreach ($promorules as $key => $value) {
            if(isset($promoListKV[$value['promorulesId']])){
               $promorules[$key]['used'] = true;
            }else{
               $promorules[$key]['used'] = false;
            }
        }

        return $promorules;
    }

	/**
	 * overview : get promo category list KV
	 *
	 * @return array
	 */
	function getPromoCategoryListKV() {
		$result = array();
		$this->db->from('promotype')->where('status', self::OLD_STATUS_ACTIVE)
			->where('promoTypeName !=', self::SYSTEM_MANUAL_PROMO_TYPE_NAME)
			->where('deleted', 0);
		$rows = $this->runMultipleRowArray();
		foreach ($rows as $row) {
			$result[$row['promotypeId']] = lang($row['promoTypeName']);
		}
		return $result;
	}

	/**
	 * overview : save template to promo rule
	 *
	 * @param string	$promoCategory
	 * @param string	$template_name
	 * @param string	$template_content
	 * @param int		$adminUserId
	 * @return array
	 */
	public function saveTemplateToPromoRule($promoCategory, $template_name, $template_content, $adminUserId) {
		$promorule = array(
			'promoCategory' => $promoCategory,
			'promoName' => $template_name,
			'json_info' => json_encode($template_content['json_info']),
			'formula' => json_encode($template_content['formula']),
			'createdOn' => $this->utils->getNowForMysql(),
			'updatedOn' => null,
			'createdBy' => $adminUserId,
			'updatedBy' => null,
			'status' => self::OLD_STATUS_ACTIVE,
		);
		$promorule = $this->updateJsonToObj($promorule);
		$this->utils->debug_log('promorule', $promorule);
		//save to db

		return $this->insertData('promorules', $promorule);
	}

	/**
	 * overview : replace applicable player levels
	 *
	 * @param int	$promoruleId
	 * @param int	$player_levels
	 */
	public function replaceApplicablePlayerLevels($promoruleId, $player_levels) {
		$this->db->delete('promorulesallowedplayerlevel', array('promoruleId' => $promoruleId));
		if (!empty($player_levels)) {
			array_walk($player_levels, function (&$player_level, $index, $promoruleId) {
				$player_level = array('promoruleId' => $promoruleId, 'playerLevel' => $player_level);
			}, $promoruleId);
			$this->db->insert_batch('promorulesallowedplayerlevel', $player_levels);
		}
	}

	/**
	 * overview : replace applicable affiliates
	 *
	 * @param int	$promoruleId
	 * @param array $affiliates
	 */
	public function replaceApplicableAffiliates($promoruleId, $affiliates) {
		$this->db->delete('promorulesallowedaffiliate', array('promoruleId' => $promoruleId));
		if (!empty($affiliates)) {
			array_walk($affiliates, function (&$affiliate, $index, $promoruleId) {
				$affiliate = array('promoruleId' => $promoruleId, 'affiliateId' => $affiliate);
			}, $promoruleId);
			$this->db->insert_batch('promorulesallowedaffiliate', $affiliates);
		}
	}

	public function replaceApplicableAgents($promoruleId, $agents) {
		$this->db->delete('promorulesallowedagent', array('promoruleId' => $promoruleId));
		if (!empty($agents)) {
			array_walk($agents, function (&$agent, $index, $promoruleId) {
				$agent = array('promoruleId' => $promoruleId, 'agent_id' => $agent);
			}, $promoruleId);
			$this->db->insert_batch('promorulesallowedagent', $agents);
		}
	}

	/**
	 * overview : replace applicable players
	 *
	 * @param int	$promoruleId
	 * @param $players
	 */
	public function replaceApplicablePlayers($promoruleId, $players) {
		$this->db->delete('promorulesallowedplayer', array('promoruleId' => $promoruleId));
		if (!empty($players)) {
			array_walk($players, function (&$player, $index, $promoruleId) {
				$player = array('promoruleId' => $promoruleId, 'playerId' => $player);
			}, $promoruleId);
			$this->db->insert_batch('promorulesallowedplayer', $players);
		}

	}

	// public function getAllowedPlayerLevels($promoruleId) {
	// 	$this->db->select('vipsettingcashbackrule.vipsettingcashbackruleId, vipsettingcashbackrule.vipLevelName, vipsetting.groupName')
	// 			 ->from('promorulesallowedplayerlevel');
	// 	$this->db->join('vipsettingcashbackrule', 'vipsettingcashbackrule.vipsettingcashbackruleId = promorulesallowedplayerlevel.playerLevel', 'left');
	// 	$this->db->join('vipsetting', 'vipsetting.vipSettingId = vipsettingcashbackrule.vipSettingId', 'left');
	// 	$this->db->where('promoruleId', $promoruleId);
	// 	$query = $this->db->get();

	// 	if ($query->num_rows() > 0) {
	// 		foreach ($query->result_array() as $row) {
	// 			$data[] = $row;
	// 		}
	// 		return $data;
	// 	}
	// 	return false;
	// }

	/**
	 * overview : get allowed promo rules
	 *
	 * @param array $promoruleIdArray
	 * @param int	$playerId
	 * @return array
	 */
	public function getAllowedPromoRulesArrayPlayer($promoruleIdArray, $playerId) {
		$this->load->model(['player_model']);
		$player = $this->player_model->getPlayerById($playerId);

		return $this->getAllowedPromoRulesArrayPlayerBy($promoruleIdArray, $player->levelId, $player->playerId, $player->affiliateId);
	}

	/**
	 * overview : get all promo rules
	 *
	 * @param int	$promoruleIdArray
	 * @param int	$levelId
	 * @param int	$playerId
	 * @param int	$affId
	 * @return array
	 */
	public function getAllowedPromoRulesArrayPlayerBy($promoruleIdArray, $levelId, $playerId, $affId) {

		$allowedIdArray = [];

		if (empty($promoruleIdArray)) {
			return $allowedIdArray;
		}

		$affQry = null;
		$affSel = null;

		$idStr = implode(',', $promoruleIdArray);

		$params = array($levelId, $playerId);
		if (empty(!$affId)) {
			$affSel = ' promorulesallowedaffiliate.affiliateId, ';
			$affQry = ' left join promorulesallowedaffiliate on promorulesallowedaffiliate.promoruleId=promorules.promorulesId and promorulesallowedaffiliate.affiliateId=? ';
			$params = array($levelId, $affId, $playerId);
		}

		$sql = <<<EOD
select promorules.promorulesId, promorulesallowedplayerlevel.playerLevel,
{$affSel} promorulesallowedplayer.playerId
from promorules
left join promorulesallowedplayerlevel on promorulesallowedplayerlevel.promoruleId=promorules.promorulesId and promorulesallowedplayerlevel.playerLevel=?
{$affQry}
left join promorulesallowedplayer on promorulesallowedplayer.promoruleId=promorules.promorulesId and promorulesallowedplayer.playerId=?
where promorules.promorulesId in ({$idStr})
EOD;

		$rows = $this->runRawSelectSQL($sql, $params);

		// $result = false;
		if (!empty($rows)) {
			foreach ($rows as $row) {
				if (empty($affId)) {
					if (!empty($row->playerLevel) || !empty($row->playerId)) {
						$allowedIdArray[] = $row->promorulesId;
						// $result = true;
						// break;
					}
				} else {
					if (!empty($row->playerLevel) || !empty($row->affiliateId) || !empty($row->playerId)) {
						$allowedIdArray[] = $row->promorulesId;
						// $result = true;
						// break;
					}
				}
			}
		}

		// $this->db->select('')->from('promorules')
		// ->join('promorulesallowedplayerlevel','promorulesallowedplayerlevel.promoruleId=promorules.promorulesId','left')
		// ->join('promorulesallowedaffiliate','promorulesallowedaffiliate.promoruleId=promorules.promorulesId','left')
		// ->join('promorulesallowedplayer','promorulesallowedplayer.promoruleId=promorules.promorulesId','left')
		// ->where('promorules.promoruleId', $promorulesId);

		$this->utils->debug_log('promoruleIdArray', $promoruleIdArray, 'allowedIdArray', $allowedIdArray);

		return $allowedIdArray;
	}

	/**
	 * overview : check if player is allowed
	 * @param int $promoruleId
	 * @param int $playerId
	 * @return bool
	 */
	public function isAllowedPlayer($promoruleId, $promorule, $playerId, &$allowedResult=[], &$ignoreResult=[]) {
		$this->load->model(['player_model']);
		$player = $this->player_model->getPlayerById($playerId);

		return !$player->disabled_promotion && $this->isAllowedPlayerBy($promoruleId, $promorule,
			$player->levelId, $player->playerId, $player->affiliateId, $hide, $player->agent_id,
			$allowedResult, $ignoreResult);
	}

    public function isActivePromo($promorulesId, $promoCmsSettingId){
	    $isActivePromo = true;

	    $this->db->select('status')
             ->from($this->tableName)
             ->where('promorulesId', $promorulesId)
             ->where('status !=', self::OLD_STATUS_INACTIVE)
             ->where('deleted_flag IS NULL', null, false);
	    $promorule_active = $this->runExistsResult();

	    $this->db->select('status')
             ->from('promocmssetting')
             ->where('promoId', $promorulesId)
             ->where('promoCmsSettingId', $promoCmsSettingId)
             ->where('status !=', 'inactive')
             ->where('deleted_flag IS NULL', null, false);
	    $promocms_active = $this->runExistsResult();

	    if(!$promorule_active || !$promocms_active){
            $isActivePromo = false;
        }

        $this->utils->debug_log($promorulesId . ' promorule is active', $promorule_active,
            'promocms is active', $promocms_active,
            'promo is active result', $isActivePromo);

        return $isActivePromo;
	}
	/**
	 * overview : allowed player
	 *
	 * @param int 	$promoruleId
	 * @param int	$levelId
	 * @param int	$playerId
	 * @param int	$affId
	 * @return bool
	 */
	public function isAllowedPlayerBy($promoruleId, $promorule, $levelId, $playerId, $affId, &$hide = false, $agentId=null,
			&$_allowedResult=[], &$_ignoreResult=[]) {
        global $BM;
        $enabled_log_OGP29899_performance_trace = $this->utils->getConfig('enabled_log_OGP29899_performance_trace');

        $_allowedResult = ['level' => FALSE, 'affiliate' => FALSE, 'agency' => FALSE];
        $_ignoreResult = ['level' => FALSE, 'affiliate' => FALSE, 'agency' => FALSE];

        if($enabled_log_OGP29899_performance_trace){
            $BM->mark('performance_trace_time_2322');
        } // EOF if($enabled_log_OGP29899_performance_trace){...

        $ignore_player = FALSE;
        $allowed_player = $this->isAllowedPlayerByPlayerId($promoruleId, $playerId, $ignore_player);
        $_ignoreResult['player'] = $ignore_player;
        $_allowedResult['player'] = $allowed_player;

        if($enabled_log_OGP29899_performance_trace){
            $BM->mark('performance_trace_time_2329');
        } // EOF if($enabled_log_OGP29899_performance_trace){...

        $ignore_level = FALSE;
        $allowed_level = $this->isAllowedPlayerByPlayerLevel($promoruleId, $levelId, $ignore_level);
        $_ignoreResult['level'] = $ignore_level;
        $_allowedResult['level'] = $allowed_level;

        if($enabled_log_OGP29899_performance_trace){
            $BM->mark('performance_trace_time_2340');
        } // EOF if($enabled_log_OGP29899_performance_trace){...

        $ignore_affiliate = FALSE;
        $allowed_affiliate = $this->isAllowedPlayerByAffiliate($promoruleId, $affId, $ignore_affiliate);
        $_ignoreResult['affiliate'] = $ignore_affiliate;
        $_allowedResult['affiliate'] = $allowed_affiliate;

        if($enabled_log_OGP29899_performance_trace){
            $BM->mark('performance_trace_time_2349');
        } // EOF if($enabled_log_OGP29899_performance_trace){...

        $ignore_agency = FALSE;
        $allowed_agency = $this->isAllowedPlayerByAgency($promoruleId, $agentId, $ignore_agency);
        $_ignoreResult['agency'] = $ignore_agency;
        $_allowedResult['agency'] = $allowed_agency;

        if($enabled_log_OGP29899_performance_trace){
            $BM->mark('performance_trace_time_2358');
        } // EOF if($enabled_log_OGP29899_performance_trace){...

        if(!!$promorule['allowed_scope_condition']){
            $allowed = TRUE;
            // $allowed = ($_ignoreResult['player']) ? $allowed : ($allowed && $_allowedResult['player']);
            $allowed = ($_ignoreResult['level']) ? $allowed : ($allowed && $_allowedResult['level']);
            $allowed = ($_ignoreResult['affiliate']) ? $allowed : ($allowed && $_allowedResult['affiliate']);
            $allowed = ($_ignoreResult['agency']) ? $allowed : ($allowed && $_allowedResult['agency']);
        }else{
            $allowed = ($_ignoreResult['level'] && $_ignoreResult['affiliate'] && $_ignoreResult['agency']);
            // $allowed = ($_ignoreResult['player']) ? $allowed : ($allowed && $_allowedResult['player']);
            $allowed = ($_ignoreResult['level']) ? $allowed : ($allowed || $_allowedResult['level']);
            $allowed = ($_ignoreResult['affiliate']) ? $allowed : ($allowed || $_allowedResult['affiliate']);
            $allowed = ($_ignoreResult['agency']) ? $allowed : ($allowed || $_allowedResult['agency']);
        }

        $allowed = ($_ignoreResult['player']) ? $allowed : $_allowedResult['player'];

		$this->utils->debug_log('allowed:'.$allowed, $_allowedResult, $_ignoreResult);

		$hide_if_not_allow=!!$promorule['hide_if_not_allow'];

		if ($hide_if_not_allow && !$allowed) {
			$this->utils->debug_log('hide_if_not_allow', $hide_if_not_allow, 'result', $allowed);
			//not allow
			$hide = true;
		}

        if($enabled_log_OGP29899_performance_trace){
            $BM->mark('performance_trace_time_2388');
        } // EOF if($enabled_log_OGP29899_performance_trace){...

		#OGP-19754 add promo player tags feature
		$_allowedResult['player_tags'] = true;
		if ($this->utils->isEnabledFeature('enable_player_tag_in_promorules')) {
			if ($this->checkPlayerIfTagIsUnderPromoRuleTag($playerId, $promoruleId)) {
				$allowed = false;
				$_allowedResult['player_tags'] = $allowed;
				$this->utils->debug_log('checkPlayerIfTagIsUnderPromoRuleTag:',$allowed, $_allowedResult, $playerId, $promoruleId);
			}
		}

        if($enabled_log_OGP29899_performance_trace){
            $BM->mark('performance_trace_time_2402');
        } // EOF if($enabled_log_OGP29899_performance_trace){...

        if( $enabled_log_OGP29899_performance_trace ){
            $elapsed_time = [];
            // 2322_2329, isAllowedPlayerByPlayerId()
            $elapsed_time['2322_2329'] = $BM->elapsed_time( 'performance_trace_time_2322', 'performance_trace_time_2329');
            // 2329_2340, isAllowedPlayerByPlayerLevel()
            $elapsed_time['2329_2340'] = $BM->elapsed_time('performance_trace_time_2329', 'performance_trace_time_2340');
            // 2340_2349, isAllowedPlayerByAffiliate()
            $elapsed_time['2340_2349'] = $BM->elapsed_time('performance_trace_time_2340', 'performance_trace_time_2349');
            // 2349_2358, isAllowedPlayerByAgency()
            $elapsed_time['2349_2358'] = $BM->elapsed_time('performance_trace_time_2349', 'performance_trace_time_2358');
            // 2388_2402, checkPlayerIfTagIsUnderPromoRuleTag()
            $elapsed_time['2388_2402'] = $BM->elapsed_time('performance_trace_time_2388', 'performance_trace_time_2402');

            $this->utils->debug_log('isAllowedPlayerBy elapsed_time:', $elapsed_time
                , 'promoruleId:', $promoruleId
                // , 'promorule:', $promorule
                , 'levelId:', $levelId
                , 'playerId:', $playerId
                , 'affId:', $affId
                , 'agentId:', $agentId
                , 'enabled_isAllowedPlayerBy_with_lite:', $this->config->item('enabled_isAllowedPlayerBy_with_lite')
            );
            $elapsed_time = [];
            unset($elapsed_time);
        }
		return $allowed;
	}
    public function isAllowedPlayerByPlayerIdLite($promoruleId, $playerId, &$ignore=false) {

        $this->db->select('promorulesallowedplayer.id')
            ->select('promorulesallowedplayer.playerId')
            ->from('promorulesallowedplayer')
            ->where('promorulesallowedplayer.promoruleId', $promoruleId);
        $rows = $this->runMultipleRowArray();
        list($allowed, $ignore) = $this->_isAllowedPlayerWithRowsAndCheckField($rows, 'playerId', $playerId);
        // if(!empty($rows)){
        //     $ignore = false; // assign
        //     $_list = array_column($rows, 'playerId');
        //     if(in_array($playerId, $_list)){
        //         $allowed= true;
        //     }else{
        //         $allowed= false;
        //     }
        //     $_list = [];
        //     unset($_list);
        // }else{
        //     $ignore = true; // assign
        // }
        // $rows = [];
        // unset($rows);
        $this->utils->debug_log('2539.isAllowedPlayerByPlayerIdLite.allowed:', $allowed, 'ignore:', $ignore, 'promoruleId:', $promoruleId, 'playerId:', $playerId);
        return $allowed;
    }// EOF isAllowedPlayerByPlayerIdLite
    //
	public function isAllowedPlayerByPlayerId($promoruleId, $playerId, &$ignore=false) {

        if( $this->config->item('enabled_isAllowedPlayerBy_with_lite') ){
            return $this->isAllowedPlayerByPlayerIdLite($promoruleId, $playerId, $ignore);
        }

		$allowed=true;
		$this->db->select('promorulesallowedplayer.id')
			->from('promorulesallowedplayer')
			->where('promorulesallowedplayer.promoruleId', $promoruleId);
		if($this->runExistsResult()){
			$this->db->select('promorulesallowedplayer.id')
				->from('promorulesallowedplayer')
				->where('promorulesallowedplayer.promoruleId', $promoruleId)
				->where('promorulesallowedplayer.playerId', $playerId);

            $ignore = FALSE;
			$allowed=$this->runExistsResult();
		}else{
            $ignore = TRUE;
        }
        $this->utils->debug_log('2563.isAllowedPlayerByPlayerId.allowed:', $allowed, 'ignore:', $ignore, 'promoruleId:', $promoruleId, 'playerId:', $playerId);
		return $allowed;
	}

    public function isAllowedPlayerByPlayerLevelLite($promoruleId, $levelId, &$ignore=false) {
        $allowed=true;
        $this->db->select('promorulesallowedplayerlevel.promorulesallowedplayerlevelId')
                ->select('promorulesallowedplayerlevel.playerLevel')
                ->from('promorulesallowedplayerlevel')
                ->where('promorulesallowedplayerlevel.promoruleId', $promoruleId);
        $rows = $this->runMultipleRowArray();
        list($allowed, $ignore) = $this->_isAllowedPlayerWithRowsAndCheckField($rows, 'playerLevel', $levelId);
        $this->utils->debug_log('2576.isAllowedPlayerByPlayerLevelLite.allowed:', $allowed, 'ignore:', $ignore, 'promoruleId:', $promoruleId, 'levelId:', $levelId);
        return $allowed;
    }
	public function isAllowedPlayerByPlayerLevel($promoruleId, $levelId, &$ignore=false) {

        if( $this->config->item('enabled_isAllowedPlayerBy_with_lite') ){
            return $this->isAllowedPlayerByPlayerLevelLite($promoruleId, $levelId, $ignore);
        }

		$allowed=true;
		$this->db->select('promorulesallowedplayerlevel.promorulesallowedplayerlevelId')
			->from('promorulesallowedplayerlevel')
			->where('promorulesallowedplayerlevel.promoruleId', $promoruleId);
		if($this->runExistsResult()){
			$this->db->select('promorulesallowedplayerlevel.promorulesallowedplayerlevelId')
				->from('promorulesallowedplayerlevel')
				->where('promorulesallowedplayerlevel.promoruleId', $promoruleId)
				->where('promorulesallowedplayerlevel.playerLevel', $levelId);

            $ignore = FALSE;
            $allowed = $this->runExistsResult();
        }else{
            $ignore = TRUE;
        }
        $this->utils->debug_log('2599.isAllowedPlayerByPlayerLevel.allowed:', $allowed, 'ignore:', $ignore, 'promoruleId:', $promoruleId, 'levelId:', $levelId);
		return $allowed;
	}

	//OGP-19313
	public function isAllowedByClaimPeriod($promoruleId) {

		$allowed=true;

		$this->db->from('promorules')->where('promorulesId', $promoruleId);
		$promorule = $this->runOneRowArray();

		if(empty($promorule)){
		    return false;
		}

		if(isset($promorule['claim_bonus_period_type'])){

			$day = (int)date('w');
			$date = (int)date('d');
			$time = date('H:i:s');

            $daysArr = $this->adjust_claim_bonus_period_day($promorule['claim_bonus_period_day']);
			$dateArr = explode(',', $promorule['claim_bonus_period_date']);

			$checkTime = true;

			switch ($promorule['claim_bonus_period_type']) {
				case '1'://daily
				case 1://daily

				  break;
				case '2'://daily
				case 2://weekly
					if(in_array($day, $daysArr)){
						$allowed = true;
					}else{
						$allowed = false;
						$checkTime = false;
					}
				  break;
				case '3'://daily
				case 3://monthly
					if(in_array($date, $dateArr)){
						$allowed = true;
					}else{
						$allowed = false;
						$checkTime = false;
					}
					break;
				default:
					$checkTime = false;
					break;
			  }

			  //check time
			  if($checkTime){
				  if($time>=$promorule['claim_bonus_period_from_time']
				  && $time<=$promorule['claim_bonus_period_to_time']){
					  $allowed = true;
				  }else{
					  $allowed = false;
				  }
			  }
		}

		$this->utils->debug_log('2315.isAllowedByClaimPeriod', $promorule['claim_bonus_period_type'], $allowed, $promoruleId, $day, $daysArr, $date, $dateArr, $time, $promorule);
		return $allowed;
	}

    public function isAllowedPlayerByAffiliateLite($promoruleId, $affiliateId, &$ignore=false) {
        $this->db->select('promorulesallowedaffiliate.id')
        ->select('promorulesallowedaffiliate.affiliateId')
        ->from('promorulesallowedaffiliate')
        ->where('promorulesallowedaffiliate.promoruleId', $promoruleId);
        $rows = $this->runMultipleRowArray();
        list($allowed, $ignore) = $this->_isAllowedPlayerWithRowsAndCheckField($rows, 'affiliateId', $affiliateId);
        $this->utils->debug_log('2676.isAllowedPlayerByAffiliateLite.allowed:', $allowed, 'ignore:', $ignore, 'promoruleId:', $promoruleId, 'affiliateId:', $affiliateId);
        return $allowed;
    } // EOF isAllowedPlayerByAffiliateLite
    //
	public function isAllowedPlayerByAffiliate($promoruleId, $affiliateId, &$ignore=false) {

        if( $this->config->item('enabled_isAllowedPlayerBy_with_lite') ){
            return $this->isAllowedPlayerByAffiliateLite($promoruleId, $affiliateId, $ignore);
        }

		$allowed=true;
		$this->db->select('promorulesallowedaffiliate.id')
			->from('promorulesallowedaffiliate')
			->where('promorulesallowedaffiliate.promoruleId', $promoruleId);
		if($this->runExistsResult()){
			$this->db->select('promorulesallowedaffiliate.id')
				->from('promorulesallowedaffiliate')
				->where('promorulesallowedaffiliate.promoruleId', $promoruleId)
				->where('promorulesallowedaffiliate.affiliateId', $affiliateId);

            $ignore = FALSE;
            $allowed = $this->runExistsResult();
        }else{
            $ignore = TRUE;
        }
        $this->utils->debug_log('2701.isAllowedPlayerByAffiliate.allowed:', $allowed, 'ignore:', $ignore, 'promoruleId:', $promoruleId, 'affiliateId:', $affiliateId);
		return $allowed;

	}

    private function _isAllowedPlayerWithRowsAndCheckField(&$rows = [], $checkFieldName = 'playerId', $checkFieldValue = 0){
        $allowed=true; // default
        if(!empty($rows)){
            $ignore = false; // assign
            $_list = array_column($rows, $checkFieldName);
            if(in_array($checkFieldValue, $_list)){
                $allowed= true;
            }else{
                $allowed= false;
            }
            $_list = []; // free
            unset($_list);
        }else{
            $ignore = true; // assign
        }
        $rows = []; // free
        unset($rows);
        return [$allowed, $ignore];
    }// EOF _isAllowedPlayerWithRowsAndCheckField

    public function isAllowedPlayerByAgencyLite($promoruleId, $agentId, &$ignore=false) {
        $this->db->select('promorulesallowedagent.id')
        ->select('promorulesallowedagent.agent_id')
        ->from('promorulesallowedagent')
        ->where('promorulesallowedagent.promoruleId', $promoruleId);
        $rows = $this->runMultipleRowArray();
        list($allowed, $ignore) = $this->_isAllowedPlayerWithRowsAndCheckField($rows, 'agent_id', $agentId);
        $this->utils->debug_log('2733.isAllowedPlayerByAgencyLite.allowed:', $allowed, 'ignore:', $ignore, 'promoruleId:', $promoruleId, 'agentId:', $agentId);
        return $allowed;
    } // EOF isAllowedPlayerByAgencyLite
    //
	public function isAllowedPlayerByAgency($promoruleId, $agentId, &$ignore=false) {

        if( $this->config->item('enabled_isAllowedPlayerBy_with_lite') ){
            return $this->isAllowedPlayerByAgencyLite($promoruleId, $agentId, $ignore);
        }

		$allowed=true;
		$this->db->select('promorulesallowedagent.id')
			->from('promorulesallowedagent')
			->where('promorulesallowedagent.promoruleId', $promoruleId);
		if($this->runExistsResult()){

			$this->db->select('promorulesallowedagent.id')
				->from('promorulesallowedagent')
				->where('promorulesallowedagent.promoruleId', $promoruleId)
				->where('promorulesallowedagent.agent_id', $agentId);

            $ignore = FALSE;
            $allowed = $this->runExistsResult();
        }else{
            $ignore = TRUE;
        }
        $this->utils->debug_log('2759.isAllowedPlayerByAgency.allowed:', $allowed, 'ignore:', $ignore, 'promoruleId:', $promoruleId, 'agentId:', $agentId);
		return $allowed;

	}

	public function checkPlayerIfTagIsUnderPromoRuleTag($playerId, $promoruleId){
		$this->load->model(array('player_model','operatorglobalsettings'));
		$playerTag = $this->player_model->getPlayerTags($playerId,true);
        $excludedPlayerTag_list = [];
        $do_extra_allowed_items = !empty($this->utils->getConfig('do_extra_allowed_items4checkPlayerIfTagIsUnderPromoRuleTag') )? true: false ;
        $_list = $this->viewPromoRuleDetails($promoruleId, 1, $do_extra_allowed_items);
        if( !empty($_list) ){
            $excludedPlayerTag_list = $_list['excludedPlayerTag_list'];
        }
		$promoRulesTags = [];

		if(!$playerTag) return false;

		if( ! empty($excludedPlayerTag_list) ){
            $promoRulesTags = explode(',', $excludedPlayerTag_list);
        }

		if(!empty($promoRulesTags)){
			foreach ($playerTag as $key => $value) {
				if(in_array($value, $promoRulesTags)){
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * overview : get allowed player levels
	 *
	 * @param  int	$promoruleId
	 * @return array
	 */
	public function getAllowedPlayerLevels($promoruleId) {
		$this->db->select('vipsettingcashbackrule.vipsettingcashbackruleId,
						   vipsettingcashbackrule.vipLevel,
						   vipsettingcashbackrule.vipLevelName,
						   vipsetting.groupName')
			->from('promorulesallowedplayerlevel')
			->join('vipsettingcashbackrule', 'vipsettingcashbackrule.vipsettingcashbackruleId = promorulesallowedplayerlevel.playerLevel', 'left')
			->join('vipsetting', 'vipsetting.vipSettingId = vipsettingcashbackrule.vipSettingId', 'left');
		$this->db->where('promorulesallowedplayerlevel.promoruleId', $promoruleId);

		$query = $this->db->get();

		$result = $this->getMultipleRowArray($query);

		if(empty($result)){
		    return NULL;
        }

        foreach($result as &$entry){
		    $entry['groupName'] = lang($entry['groupName']);
		    $entry['vipLevelName'] = lang($entry['vipLevelName']);
        }

		return $result;
	}

	/**
	 * overview : get allowed affiliates
	 *
	 * @param  int	$promoruleId
	 * @return array
	 */
	public function getAllowedAffiliates($promoruleId) {
		$this->db->select('affiliates.affiliateId, affiliates.username')
			->from('promorulesallowedaffiliate')
			->join('affiliates', 'affiliates.affiliateId = promorulesallowedaffiliate.affiliateId', 'left');
		$this->db->where('promorulesallowedaffiliate.promoruleId', $promoruleId);

		$query = $this->db->get();

		return $this->getMultipleRowArray($query);
	}

	public function getAllowedAgents($promoruleId) {
		$this->db->select('agency_agents.agent_id, agency_agents.agent_name')
			->from('promorulesallowedagent')
			->join('agency_agents', 'agency_agents.agent_id = promorulesallowedagent.agent_id', 'left');
		$this->db->where('promorulesallowedagent.promoruleId', $promoruleId);

		$query = $this->db->get();

		return $this->getMultipleRowArray($query);
	}

	/**
	 * overview : get allowed players
	 *
	 * @param $promoruleId
	 * @return null
	 */
	public function getAllowedPlayers($promoruleId) {
		$this->db->select('player.playerId, player.username')
			->from('promorulesallowedplayer')
			->join('player', 'player.playerId = promorulesallowedplayer.playerId', 'left');
		$this->db->where('promorulesallowedplayer.promoruleId', $promoruleId);

		$query = $this->db->get();

		return $this->getMultipleRowArray($query);
	}

	/**
	 * overview : transfer sub wallet
	 *
	 * @param int	$player_id
	 * @param int $transfer_to
	 * @return array
	 */
	public function getRequestTriggerOnTransferSubWallet($player_id, $transfer_to) {
		$this->load->model(['player_promo']);

		$this->db->from('playerdetails')->where('playerId', $player_id);
		$row = $this->runOneRowArray();
		$lang = $row['language'] == 'Chinese' ? 'ch' : 'en';

		$playerPromoMap = [];
		$promorules = [];

		//no hide, connect cms promo
		//only transfer to
		//manual request - declined forever

		//not expire
		$sql = <<<EOD
select promorules.promorulesId, playerpromo.playerpromoId , promorules.trigger_wallets
from playerpromo
join promorules on playerpromo.promorulesId=promorules.promorulesId
where
promorules.bonusReleaseToPlayer=?
and playerpromo.playerId=?
and playerpromo.transactionStatus=?
and promorules.hide_date >=?
and promorules.trigger_wallets is not null
and (promorules.expire_days<=0 or date_format( DATE_ADD(playerpromo.dateProcessed, INTERVAL promorules.expire_days DAY), '%Y-%m-%d') >= DATE_FORMAT(now(), '%Y-%m-%d') )
EOD;

		$params = [self::BONUS_RELEASE_TO_PLAYER_MANUAL, $player_id,
			Player_promo::TRANS_STATUS_REQUEST,
			$this->utils->getNowForMysql()];

		$manualRows = $this->runRawSelectSQLArray($sql, $params);
		if (!empty($manualRows)) {
			foreach ($manualRows as $row) {
				$trigger_wallets = $row['trigger_wallets'];
				$arr = explode(',', $trigger_wallets);
				if (in_array($transfer_to, $arr)) {
					$promorules[] = $row['promorulesId'];
					$playerPromoMap[$row['promorulesId']] = $row['playerpromoId'];
				}
			}
		}

		$this->utils->debug_log('+ manual and bonus promorules', $promorules, 'playerPromoMap', $playerPromoMap);

		$sql = <<<EOD
select promorules.promorulesId from playerpromo
join promorules on playerpromo.promorulesId=promorules.promorulesId
where
playerpromo.playerId=?
and playerpromo.transactionStatus=?

EOD;

		$params = [$player_id, Player_promo::TRANS_STATUS_DECLINED_FOREVER];

		$declinedRows = $this->runRawSelectSQLArray($sql, $params);

		$this->utils->debug_log('declined promorules', $declinedRows);

		if (!empty($declinedRows) && !empty($promorules)) {
			foreach ($declinedRows as $row) {
				$key = array_search($row['promorulesId'], $promorules);
				if ($key !== false) {
					unset($promorules[$key]);
				}
			}
		}

		if (!empty($promorules)) {
			$promorules = $this->getAllowedPromoRulesArrayPlayer($promorules, $player_id);
		}

		$this->utils->debug_log('getAllowedPromoRulesArrayPlayer promorules', $promorules);

		//get all available
		$promoruleRows = null;
		if (!empty($promorules)) {
			$this->db->select('promorules.*, promocmssetting.promoCmsSettingId')->from('promorules')
				->join('promocmssetting', 'promorules.promorulesId=promocmssetting.promoId')
				->where_in('promorulesId', $promorules);
			// ->where('promocmssetting.language', $lang);
			$promoruleRows = $this->runMultipleRowArray();
		}

		$this->utils->debug_log('last promoruleRows', count($promoruleRows));

		return array($promoruleRows, $playerPromoMap);
	}

	/**
	 * overview : get available trigger on transfer wallet
	 *
	 * @param int	$player_id
	 * @param int	$transfer_to
	 * @return array
	 */
	public function getAvailTriggerOnTransferSubWallet($player_id, $transfer_to) {
		$this->load->model(['player_promo']);

		$this->db->from('playerdetails')->where('playerId', $player_id);
		$row = $this->runOneRowArray();
		$lang = $row['language'] == 'Chinese' ? 'ch' : 'en';

		$playerPromoMap = [];
		$promorules = [];

		//no hide, connect cms promo
		//only transfer to
		//auto + manual pending - declined forever
		//only auto
		$sql = <<<EOD
select promorules.promorulesId from promorules
where
promorules.bonusReleaseToPlayer=?
and promorules.trigger_wallets is not null
and promorules.hide_date >=?
EOD;

		$params = [self::BONUS_RELEASE_TO_PLAYER_AUTO, $this->utils->getNowForMysql()];

		$autoRows = $this->runRawSelectSQLArray($sql, $params);
		if (!empty($autoRows)) {
			foreach ($autoRows as $row) {
				$trigger_wallets = $row['trigger_wallets'];
				$arr = explode(',', $trigger_wallets);
				if (in_array($transfer_to, $arr)) {
					$promorules[] = $row['promorulesId'];
				}
			}
		}

		$this->utils->debug_log('auto promorules', $promorules, 'player_id', $player_id);
		//not expire
		$sql = <<<EOD
select promorules.promorulesId, playerpromo.playerpromoId from playerpromo
join promorules on playerpromo.promorulesId=promorules.promorulesId
where
promorules.bonusReleaseToPlayer=?
and playerpromo.playerId=?
and playerpromo.transactionStatus=?
and promorules.trigger_wallets is not null
and promorules.hide_date >=?
and (promorules.expire_days<=0 or date_format( DATE_ADD(playerpromo.dateProcessed, INTERVAL promorules.expire_days DAY), '%Y-%m-%d') >= DATE_FORMAT(now(), '%Y-%m-%d') )
EOD;

		$params = [self::BONUS_RELEASE_TO_PLAYER_MANUAL, $player_id,
			Player_promo::TRANS_STATUS_MANUAL_REQUEST_APPROVED_WITHOUT_RELEASE_BONUS,
			$this->utils->getNowForMysql()];

		$manualRows = $this->runRawSelectSQLArray($sql, $params);
		if (!empty($manualRows)) {
			foreach ($manualRows as $row) {
				$trigger_wallets = $row['trigger_wallets'];
				$arr = explode(',', $trigger_wallets);
				if (in_array($transfer_to, $arr)) {
					$promorules[] = $row['promorulesId'];
					$playerPromoMap[$row['promorulesId']] = $row['playerpromoId'];
				}
			}
		}

		$this->utils->debug_log('+ manual and bonus promorules', $promorules, 'playerPromoMap', $playerPromoMap);

		$sql = <<<EOD
select promorules.promorulesId from playerpromo
join promorules on playerpromo.promorulesId=promorules.promorulesId
where
playerpromo.playerId=?
and playerpromo.transactionStatus=?

EOD;

		$params = [$player_id, Player_promo::TRANS_STATUS_DECLINED_FOREVER];

		$declinedRows = $this->runRawSelectSQLArray($sql, $params);

		$this->utils->debug_log('declined promorules', $declinedRows);

		if (!empty($declinedRows) && !empty($promorules)) {
			foreach ($declinedRows as $row) {
				$key = array_search($row['promorulesId'], $promorules);
				if ($key !== false) {
					unset($promorules[$key]);
				}
			}
		}

		if (!empty($promorules)) {
			$promorules = $this->getAllowedPromoRulesArrayPlayer($promorules, $player_id);
		}

		$this->utils->debug_log('getAllowedPromoRulesArrayPlayer promorules', $promorules);

		//get all available
		$promoruleRows = null;
		if (!empty($promorules)) {
			$this->db->select('promorules.*, promocmssetting.promoCmsSettingId')->from('promorules')
				->join('promocmssetting', 'promorules.promorulesId=promocmssetting.promoId')
				->where_in('promorulesId', $promorules);
			// ->where('promocmssetting.language', $lang);
			$promoruleRows = $this->runMultipleRowArray();
		}

		$this->utils->debug_log('last promoruleRows', count($promoruleRows));

		return array($promoruleRows, $playerPromoMap);
	}

	/**
	 * overview : get available promo rule list
	 *
	 * @param bool|true $hide_system
	 * @return array
	 */
	public function getAvailablePromoruleList($hide_system = true) {
		$this->db->from('promorules')->where('hide_date >=', $this->utils->getNowForMysql());

		if ($hide_system) {
			$this->db->where('promorules.promoName !=', self::SYSTEM_MANUAL_PROMO_RULE_NAME);
		}

		return $this->runMultipleRowArray();
	}

	/**
	 * overview : get available promo CMS list
	 *
	 * @param bool|true $hide_system
	 * @return result
	 */
	public function getAvailablePromoCMSList($hide_system = true, $sort = null, $orderBy = 'asc') {
		$this->db->select('promocmssetting.*, promorules.depositSuccesionType')->from('promocmssetting')
			->join('promorules', 'promorules.promorulesId=promocmssetting.promoId')
			->where('promorules.hide_date >=', $this->utils->getNowForMysql())
			->where('promocmssetting.deleted_flag IS NULL', null, false)
			->where('promorules.deleted_flag IS NULL', null, false)
			->where('promocmssetting.status', 'active');

		if ($hide_system) {
			$this->db->where('promocmssetting.promoName !=', self::SYSTEM_MANUAL_PROMO_CMS_NAME);
		}

		if(!empty($sort)){
			$this->db->order_by($sort, $orderBy);
		}

		return $this->runMultipleRowArray();
	}

	/**
	 * overview : get all promo CMS list
	 *
	 * @param bool|true $hide_system
	 * @return rows or null
	 */
	public function getAllPromoCMSList($hide_system = true, $sort = null, $orderBy = 'asc') {
		$this->db->select('promocmssetting.*, promorules.depositSuccesionType')->from('promocmssetting')
			->join('promorules', 'promorules.promorulesId=promocmssetting.promoId')
			->where('promorules.hide_date >=', $this->utils->getNowForMysql())
			->where('ifnull(promocmssetting.deleted_flag,0) !=', self::DB_TRUE)
			->where('ifnull(promorules.deleted_flag,0) !=', self::DB_TRUE)
			->where('promorules.status', self::OLD_STATUS_ACTIVE);
		// ->where('promocmssetting.status', 'active');

		if ($hide_system) {
			$this->db->where('promocmssetting.promoName !=', self::SYSTEM_MANUAL_PROMO_CMS_NAME);
		}

		if(!empty($sort)){
			$this->db->order_by($sort, $orderBy);
		}

		return $this->runMultipleRowArray();
	}

	/**
	 * overview : approve pre application
	 *
	 * @param int	$promocmsId
	 * @param int	$promorulesId
	 * @param int	$disabled_pre_application
	 * @param int	$playerId
	 * @param int $playerPromoId
	 * @return bool
	 */
	public function approvedPreApplication($promocmsId, $promorulesId, $disabled_pre_application,
		$playerId, &$playerPromoId = null) {
		//if it's disabled_pre_application then always approved
		if ($disabled_pre_application == '1') {
			return true;
		} else {

			$this->load->model(['player_promo']);
			$playerPromoId = $this->player_promo->getApprovedPlayerPromo($playerId, $promorulesId);
			return !empty($playerPromoId);

			//search approved pre application
			// $promorulesId
			// $this->db->from('playerpromo')
			// 	->where('playerpromo.promorulesId', $promorulesId)
			// 	->where('playerpromo.transactionStatus', Player_promo::TRANS_STATUS_MANUAL_REQUEST_APPROVED_WITHOUT_RELEASE_BONUS)
			//    	->where('playerpromo.playerId', $playerId);

			// $this->utils->printLastSQL();

			//    $row=$this->runOneRowArray();
			// if(!empty($row)){
			//     $playerPromoId=$row['playerpromoId'];
			// }
			//    return !empty($row);
		}
	}

	/**
	 * overview : get available promo on deposit
	 *
	 * @param int	$playerId
	 * @param array $promocmsList
	 * @return mixed
	 */
	public function getAvailPromoOnDeposit($playerId, $requestByApi=false) {
        $this->CI->load->model(array('player_model', 'player_promo'));
        $player = $this->CI->player_model->getPlayerById($playerId);

        $availPromoList = FALSE;

        if($player->disabled_promotion){
            return $availPromoList;
        }

		$promocmsList = $this->getAvailPromocmsListByType($playerId, self::PROMO_TYPE_DEPOSIT);
		// return $promocmsList;

		if (empty($promocmsList)) {
            return $availPromoList;
		}

        $use_self_pick_subwallets = $this->utils->isEnabledFeature('use_self_pick_subwallets');
        $hide_promo_when_reached_total_approved_limit = $this->utils->getConfig('hide_promo_when_reached_total_approved_limit');
        $enable_multi_lang_promo_manager = $this->utils->isEnabledFeature("enable_multi_lang_promo_manager");
        $currentPlayerCenterLang = $this->CI->language_function->getCurrentLangForPromo(true);
        $promorulesId = null;

        foreach ($promocmsList as $promo_item) {
            if($requestByApi){
                $promorule['trigger_wallets'] = $promo_item['trigger_wallets'];
            }else{
                if ($promo_item['hide_on_player'] == 1 || $promo_item['hide_on_player'] == null) {
                    //ignore
                    continue;
                }

                $promorulesId = $promo_item['promoId'];
                $promorule = $this->getPromorule($promorulesId);

                $hide = false;
                $playerIsAllowed = $this->isAllowedPlayerBy($promorulesId, $promorule, $player->levelId, $player->playerId, $player->affiliateId, $hide);

                if ($hide || !$playerIsAllowed) {
                    $this->utils->debug_log('ingore promotion', $promorulesId, 'player id', $player->playerId);
                    continue;
                }

                if($hide_promo_when_reached_total_approved_limit){
                    $promorule['total_approved_limit'] = $promo_item['total_approved_limit'];
                    if(!empty($promorule['total_approved_limit'])){
                        $requestCount=$this->player_promo->getTotalPromoApproved($promorulesId);
                        if($requestCount>=$promorule['total_approved_limit']){
                            $this->utils->debug_log('ignore promo', $promorulesId, 'player id', $player->playerId, ' reached total approved limit');
                            continue;
                        }
                    }
                }

                if($enable_multi_lang_promo_manager){
                    $multiPromoItems = @json_decode($promo_item['promo_multi_lang'],true);

                    if(!empty($multiPromoItems)){
                        //OGP-9767 If the selected language has no data, it will set to english(default); OGP-19166 adding extra guard
                        if(empty($multiPromoItems['multi_lang'][$currentPlayerCenterLang]) || $multiPromoItems['multi_lang'][$currentPlayerCenterLang]['promo_title_'.$currentPlayerCenterLang] == null ||
                            $multiPromoItems['multi_lang'][$currentPlayerCenterLang]['short_desc_'.$currentPlayerCenterLang] == null ||
                            $multiPromoItems['multi_lang'][$currentPlayerCenterLang]['details_'.$currentPlayerCenterLang] == null ||
                            $multiPromoItems['multi_lang'][$currentPlayerCenterLang]['banner_'.$currentPlayerCenterLang] == null)
                            $currentPlayerCenterLang = 'en';

                        $newPromoName = $multiPromoItems['multi_lang'][$currentPlayerCenterLang]['promo_title_'.$currentPlayerCenterLang];
                        $promo_item['promoName'] = $newPromoName ?: $promo_item['promoName'];

                        $newPromoDesc = $multiPromoItems['multi_lang'][$currentPlayerCenterLang]['short_desc_'.$currentPlayerCenterLang];
                        $promo_item['promoDescription'] = $newPromoDesc ?: $promo_item['promoDescription'];

                        $newPromoDetails = $multiPromoItems['multi_lang'][$currentPlayerCenterLang]['details_'.$currentPlayerCenterLang];
                        $promo_item['promoDetails'] = $newPromoDetails ?: $promo_item['promoDetails'];

                        $newPromothumbnail = $multiPromoItems['multi_lang'][$currentPlayerCenterLang]['banner_'.$currentPlayerCenterLang];

                        $promo_item['promoThumbnail'] = $newPromothumbnail;
                    }
                }

                switch($promo_item['claim_button_link']){
                    case 'deposit':
                        $claim_button_url = $this->utils->getPlayerDepositUrl();
                        break;
                    case 'referral':
                        $claim_button_url = $this->utils->getPlayerReferralUrl();
                        break;
                    case 'custom':
                    default:
                        $claim_button_url = '';
                        if(!empty($promo_item['claim_button_url'])){
                            $claim_button_url = $promo_item['claim_button_url'];
                        }
                }
                $promo_item['claim_button_url'] = $claim_button_url;
            }

            if(!$use_self_pick_subwallets && !empty($promorule['trigger_wallets'])){
                $this->utils->debug_log('ingore promotion', $promorulesId, 'player id', $player->playerId, ' disabled pick subwallets');
                continue;
            }

            $availPromoList[$promo_item['promoCmsSettingId']] = $promo_item['promoName'];
        }

		return $availPromoList;
	}

    /**
     * overview: get avail promo list for select promo in deposit page
     *
     * @param $playerId
     * @param $disable_preload
     * @return array
     */
    public function getAvailPromoCmsList($playerId, $disable_preload = false){
        $apl = [];
        if(!$disable_preload){
            $avail_promocms_list = $this->getAvailPromoOnDeposit($playerId);
            if(empty($avail_promocms_list)){
                $avail_promocms_list = [];
            }

            $promoCategoryList = $this->utils->getAllPromoType();

            foreach ($avail_promocms_list as $promo_cms_id => $promo_name) {
                $promo_rule_id = $this->getPromorulesIdByPromoCmsId($promo_cms_id);
                $promorule = $this->getPromoruleById($promo_rule_id);
                foreach ($promoCategoryList as $row) {
                    if ($promorule['promoCategory'] == $row['id']) {
                        $apl[$promo_cms_id] = $promo_name;
                    }
                }
            }
        }
        return $apl;
    }

	/**
	 * overview : get available promo
	 *
	 * @param $playerId
	 * @param $type
	 * @return array
	 */
	public function getAvailPromocmsListByType($playerId, $type) {
		//active cms promo and available and allowed and language
		$this->db->select('promocmssetting.*, promorules.*')
            ->from('promocmssetting')
			->join('promorules', 'promorules.promorulesId=promocmssetting.promoId')
			->where('promorules.hide_date >=', $this->utils->getNowForMysql())
            ->where('promocmssetting.status', 'active')
            ->where('ifnull(promocmssetting.deleted_flag,0) !=', self::DB_TRUE)
			->where('ifnull(promorules.deleted_flag,0) !=', self::DB_TRUE)
			->where('promorules.status', self::OLD_STATUS_ACTIVE)
            ->where('promorules.applicationPeriodStart <=', $this->utils->getNowForMysql())
            ->where('promorules.hide_date >=', $this->utils->getNowForMysql())
//			->where('promorules.show_on_active_available', 1)
			->where('promorules.promoType', $type);

		$this->db->where('promocmssetting.promoName !=', self::SYSTEM_MANUAL_PROMO_CMS_NAME);

		$rows = $this->runMultipleRowArray();
		$promoList = [];

		if (!empty($rows)) {
			foreach ($rows as $row) {
				$promoList[] = $row;
			}
		}

		return $promoList;
	}

// 	public function getAvailPlayerPromoList($playerId, $promoType=null){
	// 		$params=[$playerId, self::TRANS_STATUS_MANUAL_REQUEST_APPROVED_WITHOUT_RELEASE_BONUS];
	// 		if($promoType!==null){
	// 			$qry=" and promorules.promoType=? ";
	// 			$params[]=$promoType;
	// 		}

// 		$sql=<<<EOD
	// select playerpromo.playerpromoId, promocmssetting.promoName as description, promocmssetting.promoDescription

// from playerpromo join promorules on playerpromo.promorulesId=promorules.promorulesId
	// join promocmssetting on playerpromo.promoCmsSettingId=promocmssetting.promoCmsSettingId
	// where playerpromo.playerId=?
	// and playerpromo.transactionStatus=?
	// {$qry}

// EOD;

// 		return $this->runRawSelectSQLArray($sql, $params);
	// 	}

	/**
	 * overview : get promo code by cms
	 *
	 * @param string	$cms_promo_code_or_cms_promo_id
	 * @return array
	 */
	public function getByCmsPromoCodeOrId($cms_promo_code_or_cms_promo_id) {
		//try code first
		$this->db->from('promocmssetting')
            ->where(" promo_code LIKE '$cms_promo_code_or_cms_promo_id' ", null, false)
			->where('deleted_flag IS NULL', null, false);
		$row=$this->runOneRowArray();
		if(empty($row)){
			//load by id
			$this->db->from('promocmssetting')->where('promoCmsSettingId', $cms_promo_code_or_cms_promo_id);
			$row = $this->runOneRowArray();
			$this->utils->debug_log('getByCmsPromoCodeOrId-1', $row);
		}
		$promoCmsSettingId = null;
		//load to promorules
		if(!empty($row)){
			$promoCmsSettingId=$row['promoCmsSettingId'];
			$this->db->from('promorules')->where('promorulesId', $row['promoId'])
				->where('deleted_flag IS NULL', null);
			$row=$this->runOneRowArray();
			$this->utils->debug_log('getByCmsPromoCodeOrId-2', $row);
		}
		$this->utils->debug_log('getByCmsPromoCodeOrId-result', array($row, $promoCmsSettingId));
		return array($row, $promoCmsSettingId);

	}
	/**
	* overview : get promo code by cms
	*
	* @param string	$cms_promo_code_or_cms_promo_id
	* @return array
	*/
	public function getByPromoCmsId($cms_promo_id)
	{

		$this->db->from('promocmssetting')->where('promoCmsSettingId', $cms_promo_id);
		$row = $this->runOneRowArray();
		$this->utils->debug_log('getByCmsPromoCodeOrId-1', $row);
		$promoCmsSettingId = null;
		//load to promorules
		if (!empty($row)) {
			$promoCmsSettingId = $row['promoCmsSettingId'];
			$this->db->from('promorules')->where('promorulesId', $row['promoId'])
			->where('deleted_flag IS NULL', null);
			$row = $this->runOneRowArray();
			$this->utils->debug_log('getByCmsPromoCodeOrId-2', $row);
		}
		$this->utils->debug_log('getByCmsPromoCodeOrId-result', array($row, $promoCmsSettingId));
		return array($row, $promoCmsSettingId);
	}

	public function getGameTreeForPromoRuleById($promoId, $filterColumn=array()) {

		$this->load->model(array('game_description_model'));

		list($gamePlatformList, $gameTypeList, $gameDescList) = $this->getPromoIdGameTypeAndDesc($promoId);

		$showGameDescTree = $this->config->item('show_particular_game_in_tree');

		return $this->game_description_model->getGameTreeArray($gamePlatformList, $gameTypeList, $gameDescList, false, $showGameDescTree, $filterColumn);
	}

	public function getGameTreeForPromoRule($filterColumn=array()) {

		$this->load->model(array('game_description_model'));

		$gamePlatformList = array();
		$gameTypeList = array();
		$gameDescList = array();

		$showGameDescTree = $this->config->item('show_particular_game_in_tree');

		return $this->game_description_model->getGameTreeArray($gamePlatformList, $gameTypeList, $gameDescList, false, $showGameDescTree, $filterColumn);
	}

	/*
		* Generic game list in tree
	*/
	public function getGameListInTree() {

		$this->load->model(array('game_description_model'));

		$gamePlatformList = array();
		$gameTypeList = array();
		$gameDescList = array();

		$showGameDescTree = $this->config->item('show_particular_game_in_tree');

		return $this->game_description_model->getGameTreeArray($gamePlatformList, $gameTypeList, $gameDescList, false, $showGameDescTree);
	}

	public function getPromoIdGameTypeAndDesc($promoId) {
		$this->db->select('game_description.game_platform_id,game_description.game_type_id, game_description.id as game_description_id')
			->from('promorulesgamebetrule')
			->join('game_description', 'game_description.id = promorulesgamebetrule.game_description_id', 'left')
			->where('promorulesgamebetrule.promoruleId', $promoId);

		$rows = $this->runMultipleRowArray();

		$gamePlatformList = array();
		$gameTypeList = array();
		$gameDescList = array();

		if (!empty($rows)) {
			foreach ($rows as $row) {
				$gamePlatformList[$row['game_platform_id']] = 0; //$row['game_platform_percentage'];
				$gameTypeList[$row['game_type_id']] = 0; //$row['game_type_percentage'];
				$gameDescList[$row['game_description_id']] = 0; //$row['game_desc_percentage'];
			}
		}

		return array($gamePlatformList, $gameTypeList, $gameDescList);
	}

	public function batchAddAllowedGames($promoId, $gamesAptList) {
		$this->utils->debug_log('batchAddAllowedGames ===========>', $promoId);
		$this->deletePromoRulesGameBetRule($promoId);

		foreach ($gamesAptList as $gameDescriptionId) {
			$this->db->select('promorulesgametypeId')->where('game_description_id',$gameDescriptionId['id'])->where('promoruleId',$promoId);
			$isGameExist = $this->db->get('promorulesgamebetrule');

			if ($isGameExist->row('promorulesgametypeId')) continue;

			$data[] = array(
				'promoruleId' => $promoId,
				'game_description_id' => $gameDescriptionId['id'],
			);
		}

		if (!empty($data)) {
			return $this->db->insert_batch('promorulesgamebetrule', $data);
		}

		return true;
	}

	public function deletePromoRulesGameBetRule($promoId = '') {
		$this->db->where('promoruleId', $promoId);
		$this->db->delete('promorulesgamebetrule');
	}

	public function batchAddAllowedPlayer($promoId, $allowedPlayerIds) {
		foreach ($allowedPlayerIds as $ids) {
			$data[] = array(
				'promoruleId' => $promoId,
				'playerLevel' => $ids,
			);
		}
		if (!empty($data)) {
			$this->db->insert_batch('promorulesallowedplayerlevel', $data);
		}
	}

	public function getGameTreeForPlayerLevel($promoId = '') {

		$showPlayerLevelInTree = $this->config->item('show_player_level_in_tree');

		$this->CI->load->model(array('group_level'));
		$playerLevel = $this->CI->group_level->getGroupPlayerLevels($showPlayerLevelInTree);

		$promo = array();
		foreach ($playerLevel as $gpId => $playerLvlInfo) {
			$data[$gpId] = array(
				'id' => 'gp_' . $gpId,
				'number' => $gpId,
				'percentage' => false,
				'set_number' => true,
				'text' => lang($playerLvlInfo['groupName']),
				'children' => array(),
			);
			foreach ($playerLvlInfo['playerLvlTree'] as $id => $level) {
				if (!empty($promoId)) {
					$promo = $this->getPromoRulesAllowedPlayer($promoId, $level['playerLevelId']);
				}
				$isPlayerAllowed = !empty($promo) ? true : false;

				$data[$gpId]['children'][] = array(
					'id' => 'gp_' . $gpId . '_gt_' . $level['playerLevelId'],
					'number' => $level['playerLevelId'],
					'percentage' => false,
					'set_number' => true,
					'text' => lang($level['playerLevelName']),
					'state' => array('checked' => $isPlayerAllowed, 'opened' => false),
				);
			}
		}
		return array_values($data);
	}

	public function getActiveGameListInTree() {

		$showPlayerLevelInTree = $this->config->item('show_player_level_in_tree');

		$this->CI->load->model(array('group_level'));
		$playerLevel = $this->CI->group_level->getGroupPlayerLevels($showPlayerLevelInTree);

		$promo = array();
		foreach ($playerLevel as $gpId => $playerLvlInfo) {
			$data[$gpId] = array(
				'id' => 'gp_' . $gpId,
				'number' => $gpId,
				'percentage' => false,
				'set_number' => true,
				'text' => $playerLvlInfo['groupName'],
				'children' => array(),
			);
			foreach ($playerLvlInfo['playerLvlTree'] as $id => $level) {

				$data[$gpId]['children'][] = array(
					'id' => 'gp_' . $gpId . '_gt_' . $level['playerLevelId'],
					'number' => $level['playerLevelId'],
					'percentage' => false,
					'set_number' => true,
					'text' => $level['playerLevelName'],
					'state' => array('checked' => true, 'opened' => false),
				);
			}
		}
		return array_values($data);
	}

	public function getPromoRulesAllowedPlayer($promorulesId, $playerId) {
		$this->db->select('promorulesallowedplayerlevel.*')->from('promorulesallowedplayerlevel')
			->where('promoruleId', $promorulesId)
			->where('playerLevel', $playerId);

		$row = $this->runOneRowArray();
		return $row;
	}

	/**
	 * overview : get promo rule by promo cms
	 *
	 * @param $promoCmsSettingId
	 * @return promoRulesId
	 */
	public function getPromorulesIdByPromoCmsId($promoCmsSettingId) {
		$this->db->select('promoId')->from('promocmssetting')
			->where('promoCmsSettingId', $promoCmsSettingId);

		return $this->runOneRowOneField('promoId');
	}

	public function getPromoCmsByPromoruleId($promoruleId){
        $this->db->select('promocmssetting.*')->from('promocmssetting')
                 ->join('promorules','promocmssetting.promoId=promorules.promorulesId','left')
                 ->where('promocmssetting.deleted_flag is null', null, false)
                 ->where('promocmssetting.promoId', $promoruleId);
		return $this->runMultipleRowArray();
    }

    public function getPromoCmsStatusByPromoruleId($promoruleId){
        $this->db->from('promocmssetting')
            ->where('promocmssetting.deleted_flag is null', null, false)
            ->where('promocmssetting.promoId', $promoruleId);
        return $this->runOneRowOneField('status');
    }

	/**
	 * overview : get available promo on deposit
	 *
	 * @param int	$playerId
	 * @param array $promocmsList
	 * @return mixed
	 */
	public function getAllPromoOnDeposit() {

		$promocmsList = $this->getAvailPromocmsListByType(null, self::PROMO_TYPE_DEPOSIT);
		$allPromoList[0] = lang('select.empty.line');

		if (!empty($promocmsList)) {
			foreach ($promocmsList as $promocms) {
				// $promorulesId = $promocms['promoId'];
				// if ($this->approvedPreApplication($promocms['promoCmsSettingId'], $promorulesId, $promocms['disabled_pre_application'], $playerId)
				// && $this->isAllowedPlayer($promorulesId, $this->getPromorule($promorulesId), $playerId)) {
				$allPromoList[$promocms['promoCmsSettingId']] = $promocms['promoName'] . (!empty($promocms['promo_code']) ? ' (' . $promocms['promo_code'] . ')' : $promocms['promo_code']);
				// }
			}
		}

		return $allPromoList;
	}

	/**
	 * Check if specified promorule is locked
	 * @param	int		$promorulesId	== promorule.promorulesId
	 * @return	bool	true if locked; otherwise false
	 */
	public function isLocked($promorulesId) {
		$this->db->from($this->tableName)
			->select([ 'enable_edit' ])
			->where([ 'promorulesId' => $promorulesId ]);

		$enable_edit = $this->runOneRowOneField('enable_edit');

		$res = $enable_edit <= 0;

		return $res;
	}

	/**
	 * Counterpart of ::isLocked()
	 * @param	int		$promorulesId	== promorule.promorulesId
	 * @return	bool	true if edit enabled; otherwise false
	 */
	public function isEditEnabled($promorulesId) {
		return !$this->isLocked($promorulesId);
	}

	/**
	 * Get Info of Promo rules where field auto_tick_new_game_in_cashback_tree is set to 1
	 *
	 * @param string $rows
	 *
	 * @return array
	*/
	public function getPromoRuleInfoOfEnableAutoTickNewGame($rows='pr.promorulesid')
	{
		$query = $this->db->select($rows)
					->from($this->tableName." pr")
					->where('pr.auto_tick_new_game_in_cashback_tree',self::ENABLED_AUTO_TICK_NEW_GAME)
					->get();
		return $this->getMultipleRowArray($query);
	}

    /**
     * Check if Game is duplicate/exist in table promorulesgamebetrule
     *
     * @param int $gameDescriptionId
     * @param int $promoruleId
	 *
     * @return boolean
    */
    public function isDuplicatePromoRuleGameBetRule($gameDescriptionId,$promoruleId){

		$this->db->select('promorulesgametypeId')
				->from('promorulesgamebetrule')
				->where("game_description_id",$gameDescriptionId)
				->where("promoruleId",$promoruleId);

        return $this->runExistsResult();
    }

	/**
	 * Add Game/s to Promo Rule if auto_tick_new_game_in_cashback_tree = 1 field in vipsetting table
	 * @param int $gameDescriptionId
	 *
	 * @return void
	*/
	public function addGameIntoPromoRuleGameType($gameDescriptionId, &$failedPromoruleId)
	{
		$this->load->model(['game_description_model']);

		$promoRules = $this->getPromoRuleInfoOfEnableAutoTickNewGame();
		$isGameExist = $this->game_description_model->getGamePlatformIdByGameDescriptionId($gameDescriptionId);
		$failedPromoruleId = [];
		if(is_array($promoRules) && count($promoRules) > 0){
			foreach($promoRules as $promoRule){
				if( !empty($gameDescriptionId) && $isGameExist){
					$promoruleId = isset($promoRule['promorulesid']) ? $promoRule['promorulesid'] : null;
					$isDuplicatePromoRuleGameBetRule = $this->isDuplicatePromoRuleGameBetRule($gameDescriptionId,$promoruleId);

					# insert it if not yet exist
					if(! $isDuplicatePromoRuleGameBetRule){
						$data = [
							'promoruleId' => $promoruleId,
							'game_description_id' => $gameDescriptionId,
						];

						$this->insertData('promorulesgamebetrule', $data);

                        if( empty($this->db->insert_id() ) ){
                            $this->utils->error_log('sync promo game description failed', ['promoruleId'=> $promoruleId, 'game_description_id'=>$gameDescriptionId]);
                            $failedPromoruleId[] = $promoruleId;
                        }

					}
				}
			}
		}
	}

    /*
     * overview: fix number of Sunday in DateTime Format from 7 to 0
     *
     * @param $claim_bonus_period_day_str
     * @return array
     */
    public function adjust_claim_bonus_period_day($claim_bonus_period_day_str = null){
        $claim_bonus_period_day_arr = [];

        if(is_null($claim_bonus_period_day_str)){
            return $claim_bonus_period_day_arr;
        }

        $claim_bonus_period_day_str = str_replace('7', '0', $claim_bonus_period_day_str);
        $claim_bonus_period_day_arr = explode(',', $claim_bonus_period_day_str);

        return $claim_bonus_period_day_arr;
    }

    /**
     * dry run promo
     * @param  int $cmsPromoId
     * @param array $options
     * @param  array &$result
     * @param  string &$debug_log
     * @return
     */
    public function dryRunPromo($cmsPromoId, $notnull_mock, $options, &$result, &$debug_log){
    	$is_random_player=$options['is_random_player'];
    	$batch_mode_times=$options['batch_mode_times'];
    	$playerId=$options['playerId'];
    	$deposit_amount=$options['deposit_amount'];
    	$dry_run=true;
		$preapplication=false;
		$playerPromoId=null;
		$triggerEvent='manual_admin';
		$promorule=$this->promorules->getPromoruleByPromoCms($cmsPromoId);
		for ($i=0; $i < $batch_mode_times; $i++) {
			if($is_random_player){
				//query random player id
				$playerId=$this->player_model->queryOneRandomPlayerId();
			}
			$extra_info=['debug_log'=>'', 'mock'=>$notnull_mock];
			$this->utils->debug_log('run promo: '.$i);
			$success=false;
			$message=null;
			$errorMessageLang=null;
			$playerBonusAmount=null;
			if(!empty($playerId)){
				list($success, $message)=$this->promorules->checkAndProcessPromotion($playerId, $promorule, $cmsPromoId,
					$preapplication, $playerPromoId, $extra_info, $triggerEvent, $dry_run);
				$message=lang(strip_tags($message));

				//calc bonus
				if($success){
					if(!empty($extra_info['bonusAmount'])){
						$playerBonusAmount = $extra_info['bonusAmount'];
					}else{
						$playerBonusAmount = $this->promorules->getBonusAmount($promorule, $deposit_amount, $playerId, $errorMessageLang, $extra_info, $dry_run);
					}
				}
			}else{
				$this->utils->error_log('empty player id');
			}
			//dry run promo rule
			$result[]=[
				'cms_promo_id'=>$cmsPromoId,
				'success'=> $success,
				'message'=> $message,
				'errorMessageLang'=>$errorMessageLang,
				'playerBonusAmount'=>$playerBonusAmount,
				// 'debug_log'=>$extra_info['debug_log'],
			];
			$debug_log[]=$extra_info['debug_log'];
		}
		return $result;
    }

	public function getCustomPromoInfoByExtraInfo($player_id, $promo_cms_id, $info = []){
		$result = [];
		if(empty($player_id) || empty($promo_cms_id) || empty($info)){
			return $result;
		}

		$promorulesId = $this->getPromorulesIdByPromoCmsId($promo_cms_id);
		$PromoDetail = $this->getPromoDetailsWithFormulas($promorulesId);

		switch($info){
			case 'totalDeposit':
				$result = $this->getCustomPromoTotalDeposit($player_id, $PromoDetail);
				break;
			case 'totalLoss':
				$result = $this->getCustomPromoTotalLoss($player_id, $PromoDetail);
				break;
			default:
				break;
		}
		
		return $result;
	}

	public function getCustomPromoTotalDeposit($player_id, $promoDetail = []){
		$result = [];
		if(empty($promoDetail)){
			return $result;
		}
		
		$total_deposit = 0;
		$from_date = null;
		$to_date = null;
		$min_amount = 0;
		$promo_class = !empty($promoDetail['formula']['bonus_condition']['class']) ? $promoDetail['formula']['bonus_condition']['class'] : null;
		$bonus_condition = !empty($promoDetail['formula']['bonus_condition']) ? $promoDetail['formula']['bonus_condition'] : null;

		if(!empty($promo_class) && !empty($bonus_condition)){
			if($promo_class == 'promo_rule_sssbet_deposit_percentage_bonus'){
				$thisYear = $this->utils->getThisYearForMysql();
				$from_date = $thisYear . '-' . $bonus_condition['deposit_trans_range']['from_date'] . ' ' . Utils::FIRST_TIME;
				$to_date = $thisYear . '-' . $bonus_condition['deposit_trans_range']['to_date'] . ' ' . Utils::LAST_TIME;
			}
		}
		
		if(!empty($from_date) && !empty($to_date)){
			$this->load->model(['transactions']);
			$total_deposit = $this->transactions->sumDepositAmount($player_id, $from_date, $to_date, $min_amount);
		}

		return ['totalDeposit' => $total_deposit];
	}

	public function getCustomPromoTotalLoss($player_id, $promoDetail = []){
		$result = [];
		if(empty($promoDetail)){
			return $result;
		}

		$totalNetloss = 0;
		$from_date = null;
		$to_date = null;
		$promo_class = !empty($promoDetail['formula']['bonus_condition']['class']) ? $promoDetail['formula']['bonus_condition']['class'] : null;
		$bonus_condition = !empty($promoDetail['formula']['bonus_condition']) ? $promoDetail['formula']['bonus_condition'] : null;

		if(!empty($promo_class) && !empty($bonus_condition)){
			if($promo_class == 'promo_rule_king_total_losses_weekly_bonus'){
				list($from_date, $to_date) = $this->utils->getLastWeekRange('monday');

				if(!empty($bonus_condition['allowed_date']['start']) && !empty($bonus_condition['allowed_date']['end'])){
					$from_date = $bonus_condition['allowed_date']['start'];
					$to_date = $bonus_condition['allowed_date']['end'];
				}

				if(!empty($from_date) && !empty($to_date)){
					$this->load->model(['total_player_game_day']);
					$playerTotalBetWinLoss = $this->total_player_game_day->getPlayerTotalBetWinLoss($player_id,$from_date,$to_date);

					$totalWin = $playerTotalBetWinLoss['total_win'];
					$totalLoss = $playerTotalBetWinLoss['total_loss'];
					$netloss = $totalWin - $totalLoss;

					if ($netloss < 0) {
						$totalNetloss = abs($netloss);
					}
				}
			}
		}

		return ['totalLoss' => $totalNetloss];
	}

	public function getCustomDailySignInPromoStatus($player_id, $promo_cms_id, $year = null, $month = null){
		$this->load->model('player_promo');
		$promorulesId = $this->getPromorulesIdByPromoCmsId($promo_cms_id);

		$today = $this->utils->getTodayForMysql();
		list($monthStart, $monthEnd) = $this->utils->getThisMonthRangeWithoutTime();
		if(!empty($year) && !empty($month)){
			$withTime   = false;
			$yearmonth  = $this->utils->getStringYearMonth($year, $month);
			list($monthStart, $monthEnd) = $this->utils->getMonthRange($yearmonth, $withTime);
		}
		$this->utils->debug_log('monthStart', $monthStart, 'monthEnd', $monthEnd);

		$allReleasedPlayerPromo = $this->player_promo->getAllReleasedPlayerPromo($player_id, $promorulesId, $monthStart, $monthEnd);
		$monthDates = $this->utils->getEveryDayBetweenTwoDate($monthStart, $monthEnd);

		$released_promo = [];
		if(!empty($allReleasedPlayerPromo)){
			foreach ($allReleasedPlayerPromo as $record) {
				$format_date = $this->utils->convertToDateString($record['dateProcessed']);
				$released_promo[$format_date] = $record['playerpromoId'];
			}
		}

		krsort($monthDates);    // sort $monthDates key desc

		$result = [];
		$dailysignin_promo_able_to_claim    = 1;
		$dailysignin_promo_not_yet_able     = 2;
		$dailysignin_promo_already_claimed  = 3;
		$dailysignin_promo_not_claimed      = 4;

		foreach ( $monthDates as $date ) {
			if (strtotime($date) > strtotime($today)) {
				$result[$date] = $dailysignin_promo_not_yet_able;
				continue;
			}
			if (strtotime($today) == strtotime($date)) {
				if (!empty($released_promo[$date])) {
					$result[$date] = $dailysignin_promo_already_claimed;
				} else {
					$result[$date] = $dailysignin_promo_able_to_claim;
				}
				continue;
			}
			if (!empty($released_promo[$date])) {
				$result[$date] = $dailysignin_promo_already_claimed;
			} else {
				$result[$date] = $dailysignin_promo_not_claimed;
			}
		}

		return $result;
	}

	public function getCustomMonthlySignInPromoStatus($player_id, $promo_cms_id, $year = null, $month = null){
		$this->load->model('player_promo');
		$promorule=$this->getPromoruleByPromoCms($promo_cms_id);
		$promorulesId = $promorule['promorulesId'];

		list($monthStart, $monthEnd) = $this->utils->getThisMonthRangeWithoutTime();
		if(!empty($year) && !empty($month)){
			$withTime   = false;
			$yearmonth  = $this->utils->getStringYearMonth($year, $month);
			list($monthStart, $monthEnd) = $this->utils->getMonthRange($yearmonth, $withTime);
		}
		$this->utils->debug_log('monthStart', $monthStart, 'monthEnd', $monthEnd);

		$allReleasedPlayerPromo = $this->player_promo->getAllReleasedPlayerPromo($player_id, $promorulesId, $monthStart, $monthEnd);
		list($check_success, $check_message) = $this->checkOnlyPromotion($player_id, $promorule, $promo_cms_id);

		$monthlysignin_promo_able_to_claim    = 1;
		$monthlysignin_promo_not_yet_able     = 2;
		$monthlysignin_promo_already_claimed  = 3;
		$monthlysignin_promo_not_claimed      = 4;

		$currentMonth = date('Y-m-01');
		if(strtotime($monthStart) < strtotime($currentMonth)){
			//check if player already claimed promo before this month
			if(!empty($allReleasedPlayerPromo)){
				$status = $monthlysignin_promo_already_claimed;
			}else{
				$status = $monthlysignin_promo_not_claimed;
			}
		}else{
			//check if player already claimed promo this month
			if(!empty($allReleasedPlayerPromo)){
				$status = $monthlysignin_promo_already_claimed;
			}else{
				if($check_success){
					$status = $monthlysignin_promo_able_to_claim;
				}else{
					$status = $monthlysignin_promo_not_yet_able;
				}
			}
		}

		$month_key = date('Y-m', strtotime($monthStart));
		return [$month_key => $status];
	}

    /**
     * getPromoCmsIdByPromoruleId
     * @param  int $promoruleId
     * @return int
     */
	public function getPromoCmsIdByPromoruleId($promoruleId){
        $this->db->select('promoCmsSettingId')->from('promocmssetting')
                 ->join('promorules','promocmssetting.promoId=promorules.promorulesId')
                 ->where('promocmssetting.deleted_flag is null', null, false)
                 ->where('promocmssetting.promoId', $promoruleId);
        return $this->runOneRowOneField('promoCmsSettingId');
    }

    /**
	 * overview : get promo rules list order by promoName asc
	 *
	 * @return array
	 */
	public function getPromoRulesListOrderByPromoNameAsc(){
		$this->db->select('promorulesId, promoName')
            ->from($this->tableName)
            ->where('promorules.deleted_flag IS NULL', null)
            ->order_by('promoName', 'asc');

        return $this->runMultipleRowArray();
	}

} // End of class Promorules

class WrongBonusException extends Exception {

	public $error_message_lang = null;

	public function __construct($error_message_lang, $message, $code = 0) {
		$this->error_message_lang = $error_message_lang;
		parent::__construct($message, $code);
	}

}
/////end of file///////