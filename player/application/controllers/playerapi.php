<?php

// require_once dirname(__FILE__) . '/customer_api/t1t_ac_tmpl.php';
require_once dirname(__FILE__) . '/../core/APIException.php';
require_once dirname(__FILE__) . '/api_common.php';
require_once dirname(__FILE__) . '/modules_api_common/player_oauth2_module.php';
require_once dirname(__FILE__) . '/modules_api_common/player_account_module.php';
require_once dirname(__FILE__) . '/modules_api_common/player_sso_module.php';
require_once dirname(__FILE__) . '/modules_api_common/player_agent_module.php';
require_once dirname(__FILE__) . '/modules_api_common/player_cashier_module.php';
require_once dirname(__FILE__) . '/modules_api_common/player_cms_module.php';
require_once dirname(__FILE__) . '/modules_api_common/player_games_module.php';
require_once dirname(__FILE__) . '/modules_api_common/player_game_list_module.php';
require_once dirname(__FILE__) . '/modules_api_common/player_promotion_module.php';
require_once dirname(__FILE__) . '/modules_api_common/player_reports_module.php';
require_once dirname(__FILE__) . '/modules_api_common/player_resource_module.php';
require_once dirname(__FILE__) . '/modules_api_common/player_site_info_module.php';
require_once dirname(__FILE__) . '/modules_api_common/player_wallets_module.php';
require_once dirname(__FILE__) . '/modules_api_common/player_api_mock_module.php';
require_once dirname(__FILE__) . '/modules_api_common/cashier_utils/player_deposit_utils_module.php';
require_once dirname(__FILE__) . '/modules_api_common/cashier_utils/player_withdrawal_utils_module.php';
require_once dirname(__FILE__) . '/modules_api_common/player_utils/player_account_utils_module.php';
require_once dirname(__FILE__) . '/modules_api_common/player_utils/player_sso_utils_module.php';
require_once dirname(__FILE__) . '/modules_api_common/player_utils/player_rank_utils_module.php';
require_once dirname(__FILE__) . '/modules_api_common/player_utils/player_tournament_utils_module.php';
require_once dirname(__FILE__) . '/modules_api_common/promotion_utils/player_promotion_utils_module.php';
require_once dirname(__FILE__) . '/modules_api_common/player_kyc_module.php';
require_once dirname(__FILE__) . '/modules_api_common/player_notification_module.php';
require_once dirname(__FILE__) . '/modules_api_common/player_roulette_module.php';
require_once dirname(__FILE__) . '/modules_api_common/player_mission_module.php';
require_once dirname(__FILE__) . '/modules_api_common/player_crypto_module.php';
require_once dirname(__FILE__) . '/modules_api_common/player_lucky_code_module.php';
require_once dirname(__FILE__) . '/modules_api_common/player_custom_utils_module.php';
require_once dirname(__FILE__) . '/modules_api_common/player_responsible_game_module.php';
require_once dirname(__FILE__) . '/modules_api_common/player_event_module.php';
require_once dirname(__FILE__) . '/modules_api_common/player_tournament_module.php';
require_once dirname(__FILE__) . '/modules_api_common/player_quest_module.php';
require_once dirname(__FILE__) . '/modules_api_common/player_chat_module.php';

/**
 *
 * player center api for oauth2
 *
 * @see  Api_common (player/application/controllers/api_common.php)
 *
 * @property string $current_uri
 * @property string $currency
 * @property \playerapi_lib $playerapi_lib
 * @property \Sale_orders_notes $sale_orders_notes
 * @property \Walletaccount_notes $walletaccount_notes
 * @property \Response_result $response_result
 */
class Playerapi extends Api_common {

	use player_oauth2_module;
	// regular api
	use player_account_module;
	use player_agent_module;
	use player_cashier_module;
	use player_cms_module;
	use player_games_module;
	use player_game_list_module;
	use player_promotion_module;
	use player_reports_module;
	use player_resource_module;
	use player_site_info_module;
	use player_wallets_module;
	use player_api_mock_module;
	use player_deposit_utils_module;
	use player_withdrawal_utils_module;
	use player_account_utils_module;
	use player_promotion_utils_module;
	use player_tournament_utils_module;
	use player_rank_utils_module;
    use player_notification_module;
	use player_roulette_module;
	use player_mission_module;
	use player_crypto_module;
	use player_kyc_module;
	use player_lucky_code_module;
	use player_custom_utils_module;
	use player_responsible_game_module;
	use player_event_module;
	use player_sso_module;
	use player_sso_utils_module;
	use player_tournament_module;
	use player_quest_module;
	use player_chat_module;

	protected $black_list_enabled = true;
	protected $black_list = [];
	protected $white_list_enabled = true;

	protected $white_list = [
		'ping_auth', 'ping_noauth', 'test_sign', 'oauth',
		'agent', 'player', 'site_config', 'site_properties',
		'cashier', 'cms', 'games', 'bonuses', 'campaigns', 'promotions', 'missions',
		'reports', 'player_resource', 'wallets', 'game', 'roulette', 'tournament', 'quest',
	];

	private $start_time;
	private $start_time_ms;
	private $api_name;
	private $params=null;
	private $player_id=null;
	private $username=null;
	private $method=null;
	private $oauth_access_token_id=null;
	/**
	 * @var int $indexLanguage
	 */
	private $indexLanguage=null;

// "/player/register", "/player/verification-question", "/player/forget-password",
// "/games/**", "/random-games", "/site-config/**", "/site-properties/**", "/announcements", "/announcements/**", "/cms/**",
// "/**callback/**", "/captcha-image", "/login/otp"

	//excepted from auth, only match starts-with
	protected $exceptUri=[
		'/oauth/', '/ping_noauth',
		'/player/verification-questions', '/player/register', 'player/sso', '/player/forgot/password/login', '/player/captcha-image', '/player/login/otp',
		'/cms/announcements', '/cms/content-store/all', '/cms/content-store',
		/*'/games/search', '/games/launch-game', '/games/launch-game-lobby',*/ '/games/random-games',
		'/site_config/', '/site_properties/', '/game/rollers/high', '/game/bets/latest', '/game/big/win',

        # public game launch
        '/game/launchDemo', '/game/launchLobbyDemo', '/game/latest/high', '/game/top/player',

        # public game list
        '/gameList', '/game/detail', '/games/platform-list', '/game_currency/list',
		// currency game list
		'/game/platform-list', '/game/game-list', '/game/special-list','/event/list',
        // tournament
        '/tournament/list',
        '/tournament/detail',
		'/tournament/rank-list',
		'/tournament/game/list',
		# public promotion
		'/campaigns/public',
		'/campaigns/category',
		'/campaigns/public/info',
		'/roulette/list',
		'/roulette/latest',
		'/player/rank-list-public',
		'/player/rank-records-public',
		'/player/otp-public',
        '/player/auth-otp',
		'/player/vip-public',
		'/currency/config',
		# game navigation
		'/game/nav/tags',
		'/game/appearance',
	];

	//excepted from sign
	// protected $except_sign=['ping', 'test_sign'];
	/**
	 * API methods excluded from db logging
	 * @see returnError(), returnSuccess(), savePlayercenterResponseResult()
	 */
	protected $except_log = [
		'ping_auth', 'ping', 'test_sign'
	];

	const VERSION='3.01';
	const CODE_OK=20000;
	const CODE_LOCKED=20100;
	const CODE_MULTI_STATUS=20700;
	const CODE_GENERAL_CLIENT_ERROR=40000;
	const CODE_GAME_PLATFORM_ID_IS_REQUIRED=40020;
	const CODE_GAME_UNIQUE_ID_IS_REQUIRED=40021;

	const CODE_UNAUTHORIZED=40300;
	const CODE_IP_RESTRICTED=40301;
	const CODE_PLAYER_BLOCKED=40302;
	const CODE_IP_BLOCKED=40303;
	const CODE_CANNOT_EDIT_SUPERADMIN=40305;
	const CODE_CANNOT_EDIT_ADMIN_ROLE=40306;
	const CODE_CANNOT_DELETE_DEFAULT_GROUP_TAG=40307;
	const CODE_OPERATOR_RESTRICTED=40308;
	const CODE_CANNOT_LOCK_REQUEST=40309;
	const CODE_NOT_ACTIVE_PRIVILEGE=40310;
	const CODE_CANNOT_EDIT_NOT_REALTIME_CASHBACK_BONUS=40311;
	const CODE_CANNOT_EDIT_AGENT_NOT_WITH_LEVEL_0_OR_1=40312;
	const CODE_CANNOT_CREATE_SUBAGENT=40313;
	const CODE_CANNOT_CREATE_PLAYER=40314;
	const CODE_NOT_FOUND=40400;
	const CODE_WALLET_NOT_FOUND=40401;
	const CODE_PAYMENT_METHOD_NOT_FOUND=40402;
	const CODE_PLAYER_NOT_FOUND=40410;
	const CODE_PLAYER_GROUP_NOT_FOUND=40411;
	const CODE_PLAYER_TAG_NOT_FOUND=40412;
	const CODE_CURRENCY_NOT_AVAILABLE=40413;
	const CODE_PLAYER_BANK_ACCOUNT_NOT_FOUND=40415;
	const CODE_PLAYER_MESSAGE_NOT_FOUND=40417;
	const CODE_PLAYER_ANNOUNCEMENT_NOT_FOUND=40418;
	const CODE_OPERATOR_NOT_FOUND=40430;
	const CODE_CAMPAIGN_NOT_FOUND=40445;
	const CODE_REFERRAL_NOT_FOUND=40448;
	const CODE_PLAYER_MESSAGE_TEMPLATE_NOT_FOUND=40450;
	const CODE_AUTOMATION_JOB_NOT_FOUND=40451;
	const CODE_CMS_KEY_NOT_FOUND=40452;
	const CODE_API_CONFIG_NOT_FOUND=40453;
	const CODE_DEPOSIT_REQUEST_NOT_FOUND=40454;
	const CODE_WITHDRAW_PASSWORD_NOT_FOUND=40455;
	const CODE_FEE_TERM_NOT_FOUND=40456;
	const CODE_SMP_USER_NOT_FOUND=40457;
	const CODE_SERVER_ID_NOT_FOUND=40458;
	const CODE_FILE_NOT_FOUND=40459;
	const CODE_T1MERCHANT_NOT_FOUND=40460;
	const CODE_GAME_NOT_FOUND=40461;

	const CODE_WITHDRAW_REQUEST_NOT_FOUND=40061;
	const CODE_AGENT_NOT_FOUND=40062;
	const CODE_PARENT_AGENT_NOT_FOUND=40063;
	const CODE_INVALID_PARAMETER=46000;
	const CODE_INVALID_PASSWORD=46001;
	const CODE_USERNAME_ALREADY_EXISTS=46002;
	const CODE_INVALID_GROUP_ID=46003;
	const CODE_INVALID_TAG_ID=46004;
	const CODE_INVALID_FILE_TYPE=46005;
	const CODE_INVALID_ANSWER=46006;
	const CODE_INVALID_OTP=46007;
	const CODE_PLAYER_BANK_ACCOUNT_ALREADY_EXISTS=46008;
	const CODE_PLAYER_EMAIL_ALREADY_EXISTS=46009;
	const CODE_INVALID_CAPTCHA=46010;
	const CODE_DUPLICATED_PHONE_NUMBER=46011;
	const CODE_INVALID_PARENT_AGENT=46012;
	const CODE_INVALID_AGENT_LEVEL_WITH_EXCEED_10=46013;
	const CODE_INVALID_PHONE_NUMBER=46014;
	const CODE_EMAIL_NOT_VERIFIED=46015;
	const CODE_PHONENUMBER_NOT_VERIFIED=46016;
	const CODE_INVALID_STATUS=47000;
	const CODE_INVALID_CAMPAIGN_STATUS=47011;
	const CODE_INVALID_API_CONFIG_STATUS=47012;
	const CODE_INVALID_DEPOSIT_REQUEST_STATUS=47013;
	const CODE_WALLET_DISABLED=47014;
	const CODE_FILE_SIZE_EXCEEDS=47015;
	const CODE_API_UNDER_MAINTENANCE=47016;
	const CODE_WITHDRAWAL_DISABLED=47017;
	const CODE_DEPOSIT_DISABLED=47018;
	const CODE_GAME_LAUNCH_DISABLED=47019;
	const CODE_CAMPAIGN_CONDITIONS_NOT_MET=47020;
    const CODE_APPLY_PROMO_FAILED = 47021;
	const CODE_API_IS_UNAVAILABLE=47029;
	const CODE_KYC_ALREADY_VERIFIED=47030;
	const CODE_KYC_UPLOAD_FAILED=47031;
	const CODE_KYC_UPDATE_FAILED=47032;
	const CODE_KYC_NOT_VERIFIED=47033;
	const CODE_INVALID_FORMAT=48000;
	const CODE_MALFORMED_PARAMETER=48001;
	const CODE_OPERATION_FAILED=49000;
	const CODE_INSUFFICIENT_BALANCE=49001;
	const CODE_API_OPERATION_FAILED=49010;
	const CODE_DEPOSIT_REQUEST_OPERATION_FAILED=49011;
	const CODE_WITHDRAW_REQUEST_OPERATION_FAILED=49012;
	const CODE_PAYMENT_API_CONFIG_OPERATION_FAILED=49013;
	const CODE_PLAYER_MESSAGE_OPERATION_FAILED=49014;
	const CODE_CLEAN_TOKEN_FAILED=49015;
	const CODE_TRANSFER_ALL_GAME_TO_MAIN_WALLET_FAILED=49017;
	const CODE_CMS_OPERATION_FAILED=49018;
	const CODE_FILE_SAVE_FAILED=49019;
	const CODE_WITHDRAW_CONDITION_OPERATION_FAILED=49020;
	const CODE_INITIALIZE_NEW_SITE_FAILED=49021;
	const CODE_PLAYER_PHONE_OPERATION_FAILED=49022;
	const CODE_CREATE_T1MERCHANT_FAILED=49023;
	const CODE_PLAYER_ALREADY_IS_AGENT=49024;
	const CODE_PLAYER_MESSAGE_DISABLED_REPLY=49025;
	const CODE_RANK_TYPE_NOT_FOUND = 49026;
	const CODE_SITE_DISABLED = 49308;
	const CODE_SERVER_ERROR=50000;
	const CODE_EXTERNAL_API_ERROR=50010;
	const CODE_EXTERNAL_PAYMENT_API_ERROR=50011;
	const CODE_EXTERNAL_GAME_API_ERROR=50012;
	const CODE_EXTERNAL_SMS_API_ERROR=50013;
	const CODE_EXTERNAL_EMAIL_API_ERROR=50014;
	const CODE_EXTERNAL_T1GMERCHANT_API_ERROR=50015;
	const CODE_EXTERNAL_EXCHANGE_RATE_API_ERROR=50016;
	const CODE_EXTERNAL_WITHDRAW_API_ERROR=50017;
	const CODE_GAME_DOES_NOT_SUPPORT_DEMO=50018;
	const CODE_ROULETTE_TRANSACTIONS_FAILED=50019;
	const CODE_ROULETTE_RECORDS_FAIL=50020;
	const CODE_ROULETTE_NOT_FOUND=50021;
	const CODE_ROULETTE_REQUEST_FAIL=50022;
	const CODE_PROMOTION_CMS_ID_INVALID=50023;
	const CODE_PROMOTION_DISABLED=50024;
	const CODE_WITHDRAWAL_PASSWORD_DISABLED=50025;
	const CODE_DEPOSIT_UPLOAD_FAILED=50026;
	const CODE_AVATAR_UPLOAD_FAILED=50027;
    const CODE_GAME_DOES_NOT_SUPPORT_LOBBY=50028;
    const CODE_VIP_GROUP_SETTING_ERROR=50029;
    const CODE_DUPLICATED_CPF_NUMBER=50030;
    const CODE_REDEMPTIONCODE_NOT_ENABLED = 50031;
	const CODE_LOGIN_FAILED = 50032;
	const CODE_LOGIN_USER_UNDER_SELF_EXCLUSION = 50033;
	const CODE_GET_LUCKY_CODE_ERROR = 50034;
	const CODE_POPUP_MANAGER_NOT_ENABLED = 50035;
	const CODE_RPG_REQ_ALREADY_SENT_NO_MORE_ALLOWED = 50036;
	const CODE_REQUEST_QUEST_PROGRESS_FAILED = 50037;
	const CODE_REQUEST_QUEST_APPLY_FAILED = 50038;
    const CODE_LOGIN_FAILED_WITH_DUPLICATE_PHONE = 50039;
    const CODE_INVALID_CREDENTIALS = 50040;
	const CODE_TOURNAMENT_EVENT_NOT_FOUND = 59000;
	const CODE_TOURNAMENT_APPLY_FAILED = 59001;
    const CODE_NOTIFICATION_INFORMED_FAILED = 59002;
    const CODE_EMPTY_TARGET_CONTRACT_INFO_PARAMETER = 59003;
    const CODE_DUPLICATED_CONTACT_INFO = 59004;
    const CODE_INVALID_AFFILIATE_CODE = 59005;
    const CODE_LOGIN_FAILED_WITH_DUPLICATE_EMAIL = 59006;
    const CODE_PASSWORD_NEED_TO_RESEST = 59007;

	// #region crypto - 51000 ~ 51999;
	const CODE_CRYPTO_CURRENCY_NOT_ENABLED=51000;
	// #endregion crypto


	//reference by ACCUMULATION_MODE on group level
	const CODE_VIP_RANGE_YESTERDAY=1;
	const CODE_VIP_RANGE_LASTER_WEEK=2;
	const CODE_VIP_RANGE_LAST_MONTH=3;
	const CODE_VIP_RANGE_FROM_REGISTRATION=4;
	const CODE_VIP_RANGE_FROM_VIP_START=5;

	const WITHDRAWAL_PASSWORD_POLICY_ENABLE=1;
	const WITHDRAWAL_PASSWORD_POLICY_DISABLE=2;

	protected $codes=[
		self::CODE_OK=>'OK',
		self::CODE_LOCKED=>'Data has been locked',
		self::CODE_MULTI_STATUS=>'Multi status',
		self::CODE_GENERAL_CLIENT_ERROR=>'Invalid request',
		self::CODE_GAME_PLATFORM_ID_IS_REQUIRED=>'Game platform id is required',
		self::CODE_GAME_UNIQUE_ID_IS_REQUIRED=>'Game unique id is required',

		self::CODE_UNAUTHORIZED=>'Access denied: no permission.',
		self::CODE_IP_RESTRICTED=>'IP is restricted.',
		self::CODE_PLAYER_BLOCKED=>'Player is blocked.',
		self::CODE_CANNOT_EDIT_SUPERADMIN=>'Cannot edit superadmin.',
		self::CODE_CANNOT_EDIT_ADMIN_ROLE=>'Cannot edit admin role.',
		self::CODE_CANNOT_DELETE_DEFAULT_GROUP_TAG=>'Cannot delete system Default group tag.',
		self::CODE_OPERATOR_RESTRICTED=>'The operator is restricted.',
		self::CODE_CANNOT_LOCK_REQUEST=>'Cannot lock this request',
		self::CODE_NOT_ACTIVE_PRIVILEGE=>'Not allow to use the site privilege',
		self::CODE_CANNOT_EDIT_NOT_REALTIME_CASHBACK_BONUS=>'Cannot edit not realtime cashback bonus.',
		self::CODE_CANNOT_EDIT_AGENT_NOT_WITH_LEVEL_0_OR_1=>'Only agents with a level of zero or one can be edited.',
		self::CODE_CANNOT_CREATE_SUBAGENT=>'Cannot create sub agent becuase the player is not a agnet.',
		self::CODE_CANNOT_CREATE_PLAYER=>'Cannot create a new player becuase player is not a agnet.',
		self::CODE_NOT_FOUND=>'The entity you want to operate was not found.',
		self::CODE_WALLET_NOT_FOUND=>'Wallet not found.',
		self::CODE_PAYMENT_METHOD_NOT_FOUND=>'Payment Method not found.',
		self::CODE_PLAYER_NOT_FOUND=>'Player not found.',
		self::CODE_PLAYER_GROUP_NOT_FOUND=>'Player group not found.',
		self::CODE_PLAYER_TAG_NOT_FOUND=>'Player tag not found.',
		self::CODE_CURRENCY_NOT_AVAILABLE=>'The currency is not available in this site.',
		self::CODE_PLAYER_BANK_ACCOUNT_NOT_FOUND=>'Player bank account not found.',
		self::CODE_PLAYER_MESSAGE_NOT_FOUND=>'Player message not found.',
		self::CODE_PLAYER_ANNOUNCEMENT_NOT_FOUND=>'Player announcement not found.',
		self::CODE_OPERATOR_NOT_FOUND=>'Operator not found.',
		self::CODE_CAMPAIGN_NOT_FOUND=>'Campaign not found.',
		self::CODE_REFERRAL_NOT_FOUND=>'Referral not found.',
		self::CODE_PLAYER_MESSAGE_TEMPLATE_NOT_FOUND=>'Player message template not found.',
		self::CODE_AUTOMATION_JOB_NOT_FOUND=>'Automation job not found.',
		self::CODE_CMS_KEY_NOT_FOUND=>'Key not found in CMS.',
		self::CODE_API_CONFIG_NOT_FOUND=>'Api config not found.',
		self::CODE_DEPOSIT_REQUEST_NOT_FOUND=>'Deposit request not found.',
		self::CODE_WITHDRAW_PASSWORD_NOT_FOUND=>'Please setup withdraw password.',
		self::CODE_FEE_TERM_NOT_FOUND=>'Fee term not found in site.',
		self::CODE_SMP_USER_NOT_FOUND=>'T1SMP user was not found',
		self::CODE_SERVER_ID_NOT_FOUND=>'Server ID was not found',
		self::CODE_FILE_NOT_FOUND=>'File not found.',
		self::CODE_T1MERCHANT_NOT_FOUND=>'T1 merchant was not found',
		self::CODE_GAME_NOT_FOUND=>'game not found',

		self::CODE_WITHDRAW_REQUEST_NOT_FOUND=>'Withdraw request not found',
		self::CODE_AGENT_NOT_FOUND=>'Agent not found',
		self::CODE_PARENT_AGENT_NOT_FOUND=>'Parent agent not found',
		self::CODE_INVALID_PARAMETER=>'Invalid parameter',
        self::CODE_EMPTY_TARGET_CONTRACT_INFO_PARAMETER=>'The target is empty',
		self::CODE_INVALID_PASSWORD=>'The password entered was invalid.',
		self::CODE_USERNAME_ALREADY_EXISTS=>'Username already exists.',
		self::CODE_INVALID_GROUP_ID=>'This is not a group id for player.',
		self::CODE_INVALID_TAG_ID=>'This is not a tag id for player.',
		self::CODE_INVALID_FILE_TYPE=>'Invalid file type.',
		self::CODE_INVALID_ANSWER=>'The answer entered was invalid.',
		self::CODE_INVALID_OTP=>'The OTP entered was incorrect or has expired.',
		self::CODE_PLAYER_BANK_ACCOUNT_ALREADY_EXISTS=>'Bank account already exists for the player.',
		self::CODE_PLAYER_EMAIL_ALREADY_EXISTS=>'Email already exists in another player.',
		self::CODE_INVALID_CAPTCHA=>'Incorrect captcha',
		self::CODE_DUPLICATED_PHONE_NUMBER=>'Phone number is used by another player',
		self::CODE_INVALID_PARENT_AGENT=>'The parent agent cannot be one of the child agents to which it belongs.',
		self::CODE_INVALID_AGENT_LEVEL_WITH_EXCEED_10=>'All sub-agent levels cannot exceed 10.',
		self::CODE_INVALID_PHONE_NUMBER=>'Invalid phone number.',
		self::CODE_EMAIL_NOT_VERIFIED=>'Email is not verified.',
		self::CODE_PHONENUMBER_NOT_VERIFIED=>'Phone number is not verified.',
		self::CODE_INVALID_STATUS=>'Changes are not allowed because the execution conditions are not met.',
		self::CODE_INVALID_CAMPAIGN_STATUS=>'Cannot edit campaign because the status does not meet the conditions.',
		self::CODE_INVALID_API_CONFIG_STATUS=>'Cannot edit api config because the status does not meet the conditions.',
		self::CODE_INVALID_DEPOSIT_REQUEST_STATUS=>'Cannot edit deposit request because the status does not meet the conditions.',
		self::CODE_WALLET_DISABLED=>'The wallet is disabled.',
		self::CODE_FILE_SIZE_EXCEEDS=>'The file exceeds its maximum permitted size.',
		self::CODE_API_UNDER_MAINTENANCE=>'The api is under maintenance.',
		self::CODE_WITHDRAWAL_DISABLED=>'Withdrawal has been disabled',
		self::CODE_DEPOSIT_DISABLED=>'Deposit has been disabled',
		self::CODE_GAME_LAUNCH_DISABLED=>'Launching game has been disabled',
		self::CODE_CAMPAIGN_CONDITIONS_NOT_MET=> 'Campaign conditions not met',
		self::CODE_API_IS_UNAVAILABLE=>'API is unavailable',
		self::CODE_KYC_ALREADY_VERIFIED=>'The kyc type is already verified.',
		self::CODE_KYC_UPLOAD_FAILED=>'Failed to upload for KYC documents.',
		self::CODE_KYC_UPDATE_FAILED=>'Faild to update for KYC infomation.',
		self::CODE_INVALID_FORMAT=>'Invalid format',
		self::CODE_MALFORMED_PARAMETER=>'Failure parsing input.',
		self::CODE_OPERATION_FAILED=>'Operation failed.',
		self::CODE_INSUFFICIENT_BALANCE=>'Insufficient balance.',
		self::CODE_API_OPERATION_FAILED=>'Api operation failed.',
		self::CODE_DEPOSIT_REQUEST_OPERATION_FAILED=>'Failed creating deposit request.',
		self::CODE_WITHDRAW_REQUEST_OPERATION_FAILED=>'Failed updating players balance and/or withdraw request.',
		self::CODE_PAYMENT_API_CONFIG_OPERATION_FAILED=>'Failed to modify payment api config.',
		self::CODE_PLAYER_MESSAGE_OPERATION_FAILED=>'Failed to modify player message.',
		self::CODE_PLAYER_MESSAGE_DISABLED_REPLY=>'Disabled player reply message.',
		self::CODE_CLEAN_TOKEN_FAILED=>'Failed to clean token.',
		self::CODE_TRANSFER_ALL_GAME_TO_MAIN_WALLET_FAILED=>'Failed to transfer all game balance back to main wallet.',
		self::CODE_CMS_OPERATION_FAILED=>'Failed to edit single store entry by key.',
		self::CODE_FILE_SAVE_FAILED=>'Failed to save file.',
		self::CODE_WITHDRAW_CONDITION_OPERATION_FAILED=>'Failed to edit withdraw condition.',
		self::CODE_INITIALIZE_NEW_SITE_FAILED=>'Failed to create new SBE site',
		self::CODE_PLAYER_PHONE_OPERATION_FAILED=>'Failed to validate players phone, should invalidate phone fist.',
		self::CODE_CREATE_T1MERCHANT_FAILED=>'Failed to create T1 merchant.',
		self::CODE_PLAYER_ALREADY_IS_AGENT=>'Failed to convert the player as a agent, becuase the player is already as a agent.',
		self::CODE_SERVER_ERROR=>'Unknown error',
		self::CODE_EXTERNAL_API_ERROR=>'Failure communicating with external system.',
		self::CODE_EXTERNAL_PAYMENT_API_ERROR=>'Failure communicating with external payment system.',
		self::CODE_EXTERNAL_GAME_API_ERROR=>'Failure communicating with external game provider system.',
		self::CODE_EXTERNAL_SMS_API_ERROR=>'Failure communicating with external sms system.',
		self::CODE_EXTERNAL_EMAIL_API_ERROR=>'Failure communicating with external email system.',
		self::CODE_EXTERNAL_T1GMERCHANT_API_ERROR=>'Failure creating T1-gateway merchant',
		self::CODE_EXTERNAL_EXCHANGE_RATE_API_ERROR=>'Failure communicating with external exchange rate system.',
		self::CODE_EXTERNAL_WITHDRAW_API_ERROR=>'Failure communicating with external withdraw system.',
		self::CODE_GAME_DOES_NOT_SUPPORT_DEMO=>'Game does not support demo',
		self::CODE_ROULETTE_TRANSACTIONS_FAILED=>'Created roulette transactions failed.',
		self::CODE_ROULETTE_RECORDS_FAIL=>'Roulette records fail.',
		self::CODE_ROULETTE_NOT_FOUND=>' Cannot find roulette.',
		self::CODE_ROULETTE_REQUEST_FAIL=>'You are not suited for this roulette yet.',
		self::CODE_PROMOTION_CMS_ID_INVALID=>'Invalid promo cms id.',
		self::CODE_PROMOTION_DISABLED=>'Promotion has been disabled.',
		self::CODE_WITHDRAWAL_PASSWORD_DISABLED=>'Withdrawal password is disabled.',
		self::CODE_CRYPTO_CURRENCY_NOT_ENABLED => 'Crypto currency is not enabled.',
		self::CODE_GAME_DOES_NOT_SUPPORT_LOBBY => 'Game does not support lobby.',
		self::CODE_VIP_GROUP_SETTING_ERROR => 'Vip group setting error',
		self::CODE_DUPLICATED_CPF_NUMBER=>'CPF number is used by another player',
		self::CODE_LOGIN_FAILED => 'Login failed.',
        self::CODE_LOGIN_FAILED_WITH_DUPLICATE_PHONE => 'Login failed with duplicate phone number.',
        self::CODE_INVALID_CREDENTIALS => 'The user credentials were incorrect.',
		self::CODE_LOGIN_USER_UNDER_SELF_EXCLUSION => 'Login user under self exclusion.',
		self::CODE_POPUP_MANAGER_NOT_ENABLED => 'Popup manager is not enabled.',
		self::CODE_RPG_REQ_ALREADY_SENT_NO_MORE_ALLOWED => 'Failed creating responsible game request.',
		self::CODE_REQUEST_QUEST_PROGRESS_FAILED => 'Failed quest progress request.',
		self::CODE_REQUEST_QUEST_APPLY_FAILED => 'Failed quest apply request.',
		self::CODE_TOURNAMENT_EVENT_NOT_FOUND => 'Tournament event not found.',
		self::CODE_TOURNAMENT_APPLY_FAILED => 'Failed to apply tournament.',
        self::CODE_NOTIFICATION_INFORMED_FAILED => 'Failed to informed notification.',
        self::CODE_DUPLICATED_CONTACT_INFO => 'The contact info includes duplicate players',
        self::CODE_INVALID_AFFILIATE_CODE => 'Tracking code does not exist.  Please use a tracking code provided by one of our affiliates.',
        self::CODE_LOGIN_FAILED_WITH_DUPLICATE_EMAIL => 'Login failed with duplicate email.',
        self::CODE_PASSWORD_NEED_TO_RESEST => 'Player password need to reset.',
	];

	// prevent T1t_ac_tmpl
	// protected $enable_cross_domain=false;

	function __construct() {
		parent::__construct();
		$this->load->library([ 'playerapi_lib']);
	}

	public function _remap($method)
	{
		global $CI, $URI;

		try {
			$this->initVariables();

			if($this->_isOptionsMethodRequest()){
				//don't accept options
				$this->_addCORSHeadersWithOrigin();
				return false;
			}

			if (!in_array(strtolower($method), array_map('strtolower', get_class_methods($CI)))) {
				return $this->returnUnimplemented('', true, '*', false, false, 501, "Not Implemented");
			}

			return call_user_func_array(array(&$CI, $method), array_slice($URI->rsegments, 2));
		} catch(\APIException $e) {
			return $this->returnErrorWithResult($e->getResult());
		} catch(\Exception $e) {
			$this->utils->error_log('player apiexception');
			return $this->returnErrorWithResult([
				'code' => static::CODE_SERVER_ERROR,
				'errorMessage' => $this->codes[static::CODE_SERVER_ERROR],
				'exception' => $e->getMessage()
			]);
		} catch (\Throwable $th) {
			return $this->returnErrorWithResult([
				'code' => $th->getCode(),
				'file' => $th->getFile(),
				'line' => $th->getLine(),
				'message' => $th->getMessage(),
				'trace' => $th->getTrace()
			]);
		}
	}

	protected function initVariables(){
		$this->start_time=time();
		$this->start_time_ms=microtime(true);
		$this->load->library(['uri']);
		$this->api_name=$this->uri->ruri_string();
		$this->current_uri=substr($this->api_name, strlen('/playerapi'));
		$this->method=null;
		if (isset($_SERVER['REQUEST_METHOD'])) {
			$this->method=strtolower(@$_SERVER['REQUEST_METHOD']);
		}
		$this->utils->debug_log('get api name', $this->api_name, $this->current_uri, 'method', $this->method);
	}

	/**
	 * init api first
	 * @return boolean init result
	 */
	protected function initApi(){
		if(empty($this->method) || $this->_isOptionsMethodRequest()){
			//don't accept options
			$this->_addCORSHeadersWithOrigin();
			return false;
		}

		if(!$this->checkBlockPlayerIPOnly(true)){
			$this->returnErrorWithCode(self::CODE_IP_BLOCKED);
			return false;
		}

		if($this->processParameters()){
			//safe
		}else{
			$this->returnErrorWithCode(self::CODE_GENERAL_CLIENT_ERROR);
			return false;
		}
		//example: /playercenterapi/ping
		// currency order: json inside, ?__OG_TARGET_DB=, ?currency=
		$this->currency=strtolower($this->getParam('currency'));
		if(empty($this->currency)){
			//try get it from __OG_TARGET_DB
			$this->currency=$this->input->get(Multiple_db::__OG_TARGET_DB);
			if(empty($this->currency)){
				$this->currency=strtolower($this->input->get('currency'));
				if(empty($this->currency)){
					$this->currency=strtolower($this->input->post('currency'));
					if(empty($this->currency)){
						$this->currency=$this->utils->getActiveTargetDB();
						if(empty($this->currency)){
							//still empty
							$this->currency=null;
						}
					}
				}
			}
		}

		if($this->validateCurrencyAndSwitchDB()){
			//safe
		}else{
			$this->returnErrorWithCode(self::CODE_CURRENCY_NOT_AVAILABLE);
			return false;
		}

		$this->checkAndSwitchLanguage();

		//validate token and get sign key
		$errorResponse=null;
		if($this->validateOauth2Token($errorResponse)){
			//safe
		}else{
			$this->utils->debug_log('invalid oauth2 token');
			$this->returnErrorFromResponse($errorResponse);
			return false;
		}

		// if($this->validateSign()){
		//     //safe
		// }else{
		//     $this->returnError(self::CODE_INVALID_SIGN);
		//     return false;
		// }

		//return mock
		if($this->_returnMockDataOnly($this->current_uri)){
			//mock data only
			return false;
		}

		return true;
	}

	protected function _returnMockDataOnly($uri){
		$mock_of_playerapi=$this->utils->getConfig('mock_of_playerapi');
		//check config, return mock data
		if(array_key_exists($uri, $mock_of_playerapi) && $mock_of_playerapi[$uri]){
			$mock_data = $this->_mockDataForPlayerapi();
			$this->returnSuccessWithResult($mock_data);
			return true;
		}
		return false;
	}

	protected function _isOptionsMethodRequest(){
		return $this->method=='options';
	}

	protected function _isGetMethodRequest(){
		return $this->method=='get';
	}

	protected function _isPostMethodRequest(){
		return $this->method=='post';
	}

	protected function _isPutMethodRequest(){
		return $this->method=='put';
	}

	protected function _isDeleteMethodRequest(){
		return $this->method=='delete';
	}

	protected function checkAndSwitchLanguage(){
		// read x-lang from header
		$lang=$this->input->get_request_header('X-Lang');
		$this->load->library(['language_function']);
		if(empty($lang)){
			// fallback lang
			$this->indexLanguage=intval($this->language_function->getCurrentLanguage());
		}else{
			$this->indexLanguage=intval($this->language_function->isoLangCountryToIndex($lang));
		}
		$this->language_function->setCurrentLanguage($this->indexLanguage);
		return true;
	}

	/**
	 * process parameter
	 * decode json parameter
	 * copy json parameter to $_POST
	 */
	protected function processParameters(){
		$success=true;

		//read json
		$json = file_get_contents('php://input');
		if(!empty($json)){
			//only decode not empty
			$this->params=$this->utils->decodeJson($json);
			// $this->input->copyParametersToInput($this->params);

			//record raw call
			$this->utils->debug_log('-------- get json from input on game api', $json, $this->params);
			// $this->utils->debug_log('====print $_POST', $_POST);
		}
		unset($json);

		return $success;
	}

	/**
	 * only fo mdb, check currency option and change db to target currency
	 * @return boolean
	 */
	protected function validateCurrencyAndSwitchDB(){
		if(!$this->utils->isEnabledMDB()){
			return true;
		}
		if(empty($this->currency)){
			return false;
		}else{
			//validate currency name
			if(!$this->utils->isAvailableCurrencyKey($this->currency, false)){
				//invalid currency name
				return false;
			}else{
				//switch to target db
				$_multiple_db=Multiple_db::getSingletonInstance();
				$_multiple_db->switchCIDatabase($this->currency);
				return true;
			}
		}
	}

	protected function loadOauth2Lib(&$errorResponse){
		$player_oauth2_settings=$this->utils->getConfig('player_oauth2_settings');
		require_once dirname(__FILE__).'/../'.$player_oauth2_settings['lib_class_path'];
		$libPlayerOauth2=null;
		try{
			$libPlayerOauth2=\LibPlayerOauth2::generateInstance();
		}catch(Exception $e){
			$this->utils->error_log('get lib player oauth2 failed', $e);
			//return error
			// generate 500
			$errorResponse=\League\OAuth2\Server\Exception\OAuthServerException::serverError('Init lib failed')
				->generateHttpResponse(new \Response());
		}
		return $libPlayerOauth2;
	}

	/**
	 * validateOauth2Token
	 * @param ResponseInterface $errorResponse
	 * @return boolean
	 */
	protected function validateOauth2Token(&$errorResponse){
		//check except
		// if(in_array($this->api_name, $this->except)){
		// 	return true;
		// }
		if(!$this->_isDeleteMethodRequest()){
			//not delete
			foreach ($this->exceptUri as $val) {
				if($this->utils->startsWith($this->current_uri, $val)){
					//matched
					return true;
				}
			};
		}
		$libPlayerOauth2=$this->loadOauth2Lib($errorResponse);
		if(empty($libPlayerOauth2)){
			return false;
		}
		$request=$libPlayerOauth2->generatePsr7Request();
		$errorResponse=null;
		$success=$libPlayerOauth2->validateToken($request, $errorResponse, $username, $oauth_access_token_id);
		$this->utils->debug_log('return request', $request->getAttributes(), $request->getQueryParams());
		if($success){
			if(!empty($username)){
				$this->player_id=$this->player_model->getPlayerIdByUsername($username);
				if(!empty($this->player_id)){
					$this->username=$username;
					$this->oauth_access_token_id=$oauth_access_token_id;
					return true;
				}
			}
		}
		$errorResponse=$libPlayerOauth2->makeAccessDeniedResponse('cannot find player');
		return false;
	}

	/**
	 * get parameter
	 * @param  string $key
	 * @param  mixin $default
	 * @return mixin|string value
	 */
	protected function getParam($key, $default=null){
		if(isset($this->params[$key])){
			return $this->params[$key];
		}

		return $default;
	}

	/**
	 * reset all params
	 *
	 */
	protected function resetAllParams(){
		unset($this->params);
		$this->params=[];
	}

	/**
	 * get int param
	 * @param  string $key
	 * @param  int $default
	 * @return int value
	 */
	protected function getIntParam($key, $default=null){
		return intval($this->getParam($key, $default));
	}

	/**
	 * get bool param
	 * @param  string $key
	 * @param  boolean $default
	 * @return boolean value
	 */
	protected function getBoolParam($key, $default=null){
		$val=$this->getParam($key, $default);
		if(is_bool($val)){
			return $val;
		}
		if(is_string($val)){
			//string true
			return strtolower($val)=='true';
		}
		if(is_int($val)){
			//!=0 is true
			return $val!=0;
		}

		return boolval($val);
	}

	/**
	 * return error
	 * @param  ResponseInterface $response
	 *
	 */
	protected function returnErrorFromResponse($response){
		$result=$this->utils->decodeJson($response->getBody()->__toString(), true);
		$this->returnErrorWithResult($result, $response->getStatusCode());
	}

	/**
	 * return error
	 * @param  string $code
	 * @param  string $customized_message
	 * @param  string $detail
	 *
	 */
	protected function returnErrorWithCode($code, $customized_message=null, $detail=null){
		// $message=$customized_message;
		// if(empty($message)){
		// 	$message=$this->codes[$code];
		// }

		$result=['code'=>$code,
			'errorMessage'=>$customized_message,
		];

		// $result=['code'=>$code, 'data'=>['success'=>false]];
		if(!empty($detail)){
			$result['detail']=$detail;
		}

		return $this->returnErrorWithResult($result);
	}

	/**
	 * return error with result
	 * @param  string|array  $result
	 * @param  integer $statusCode
	 */
	protected function returnErrorWithResult($result, $statusCode=200){
		//append
		$this->appendServerInfoToResult($result);
		if (!in_array($this->api_name, $this->except_log)) {
			$is_error=true;
			$this->savePlayercenterResponseResult($result, $is_error, null, $statusCode);
		}
		$this->output->set_status_header($statusCode);
		return $this->returnJsonResultAPI($result);
	}

	/**
	 * return error
	 * @param  ResponseInterface $response
	 *
	 */
	protected function returnSuccessFromResponse($response){
		$result=$this->utils->decodeJson($response->getBody()->__toString(), true);
		$this->returnSuccessWithResult($result, $response->getStatusCode());
	}

	/**
	 * return success with result
	 * @param  array $result
	 */
	protected function returnSuccessWithResult($result){
		$this->appendServerInfoToResult($result);
		if (!in_array($this->api_name, $this->except_log)) {
			$is_error=false;
			$this->savePlayercenterResponseResult($result, $is_error);
		}

		return $this->returnJsonResultAPI($result);
	}

	/**
	 * return json result
	 * @param  array  $result
	 * @param  boolean $addOrigin
	 * @param  string  $origin
	 * @return output header and json
	 */
	protected function returnJsonResultAPI($result, $addOrigin = true, $origin = "*") {
		if($this->internal_json_result){
			$this->_json_result_array=$result;
			return true;
		}

		$txt = json_encode($result);

		$this->output->set_content_type('application/json');
		$this->output->set_output($txt);
		if ($addOrigin) {
			$this->_addCORSHeadersWithOrigin($origin);
		}

		return true;
	}

	protected function _addCORSHeadersWithOrigin($origin='*'){
		//copy header
		// header('Access-Control-Allow-Origin: *');
		// header('Access-Control-Allow-Methods: PATCH,POST,GET,OPTIONS,DELETE');
		// header('Access-Control-Max-Age: 3600');
		// header('Access-Control-Allow-Headers: x-requested-with, authorization, Content-Type, Authorization, credential, X-XSRF-TOKEN');

		if ($origin == '*') {
			header("Access-Control-Allow-Origin: " . $this->getAvailableOrigin());
		} else {
			header("Access-Control-Allow-Origin: " . $origin);
		}


		$customHeader = $this->utils->getConfig('player_center_api_x_custom_header');
		$this->utils->debug_log(__FUNCTION__, "customHeader", $customHeader);

		$this->addOriginHeader($origin);
		header("Access-Control-Allow-Methods: GET, POST, OPTIONS, DELETE");
		header("Access-Control-Expose-Headers: X-Requested-With, Access-Control-Allow-Origin". $customHeader);
		header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, authorization, Authorization, credential, X-XSRF-TOKEN, X-Lang, x-lang' . $customHeader);
		header("Access-Control-Allow-Credentials: true");
		// header('Access-Control-Max-Age: 3600');
		header('X-Content-Type-Options: nosniff');
	}

	/**
	 * appendServerInfoToResult
	 * @param  array &$result
	 * @return void
	 */
	protected function appendServerInfoToResult(&$result){
		$result['server']=[
			'version'=>self::VERSION,
			'requestId'=>$this->utils->getRequestId(),
			'serverTime'=>$this->playerapi_lib->formatDateTime($this->utils->getNowForMysql()),
			'costMs'=>(microtime(true)-$this->start_time_ms),
			'externalRequestId'=>$this->_external_request_id,
		];
	}

	protected function returnResultFromInternal(){
		$apiResult=$this->getInternalJsonResult();
		//['success'=>, 'code'=>, 'message'=>, ]
		if(!empty($apiResult) && is_array($apiResult)){
			if($apiResult['success']){
				$result=$this->convertInternalResultToAPIResult($apiResult);
				return $this->returnSuccessWithResult($result);
			}else{
				//convert code
				$code=$this->convertInternalCodeToAPICode($apiResult['code']);
				return $this->returnErrorWithCode($code);
			}
		}
		return $this->returnErrorWithCode(self::CODE_SERVER_ERROR);
	}

	/**
	 * save response result
	 * @param  string  $requstApi
	 * @param  array  $returnJson
	 * @param  boolean $is_error
	 * @param  array  $extra
	 * @param  integer $statusCode
	 * @param  string  $statusText
	 * @return int id
	 */
	protected function savePlayercenterResponseResult($returnJson,
			$is_error=false, $extra=null, $statusCode=200, $statusText=null){
		$requstApi=$this->api_name;
		$this->load->model(['response_result']);
		$systemId=PLAYER_API;
		$flag= $is_error ? Response_result::FLAG_ERROR : Response_result::FLAG_NORMAL;
		$requestParams=json_encode($this->params);
		if(empty($returnJson)){
			$returnJson=[];
		}
		if(!is_array($returnJson)){
			$returnJson=[$returnJson];
		}
		$returnJson['cost']=time()-$this->start_time;
		$costMs=(microtime(true)-$this->start_time_ms)*1000;
		$resultText=json_encode($returnJson);
		
		 // Append newly generated extra info
		$extra = $this->generateExtraInfo($extra);

		return $this->response_result->saveResponseResult($systemId, $flag, $requstApi,
			$requestParams, $resultText, $statusCode, $statusText, $extra,
			['player_id'=>$this->player_id, 'full_url'=>$this->utils->paddingHostHttp(uri_string())],
			false, $this->_external_request_id, $costMs);
	}

	protected function generateExtraInfo($extra)
	{
		$this->load->library(array('user_agent'));
		$device = ($this->CI->agent->is_mobile() == TRUE) ? $this->CI->agent->mobile() : $this->CI->agent->browser() . " " . $this->CI->agent->version();
		$browser_type = 0;
		if (isset($_SERVER['HTTP_X_APP_IOS'])) {
			$browser_type = Http_request::HTTP_BROWSER_TYPE_IOS;
		} elseif (isset($_SERVER['HTTP_X_APP_ANDROID'])) {
			$browser_type = Http_request::HTTP_BROWSER_TYPE_ANDROID;
		} else {
			$browser_type = $this->CI->agent->is_mobile() 
				? Http_request::HTTP_BROWSER_TYPE_MOBILE 
				: Http_request::HTTP_BROWSER_TYPE_PC;
		}
	
		$newExtra = [
			'user_agent'   => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null,
			'device'       => $device,
			'browser_type' => $this->getBrowserType($browser_type),
		];
	
		if (!is_array($extra)) {
			$extra = [];
		}
	
		$extra[] = $newExtra;
	
		return json_encode($extra);
	}

	private function getBrowserType($type) {
		$browserTypes = [
			Http_request::HTTP_BROWSER_TYPE_PC => 'pc',
			Http_request::HTTP_BROWSER_TYPE_MOBILE => 'mobile',
			Http_request::HTTP_BROWSER_TYPE_IOS => 'ios',
			Http_request::HTTP_BROWSER_TYPE_ANDROID => 'android'
		];
	
		return isset($browserTypes[$type]) ? $browserTypes[$type] : null;
	}

	/**
	 * return unimplemented
	 * @param  string $customized_message
	 *
	 */
	protected function returnUnimplemented($customized_message=null, $addOrigin = true, $origin = "*", $pretty = false, $partial_output_on_error = false, $http_status_code = 0, $http_status_text = ''){

		$code=self::CODE_OK;
		$message=$customized_message;
		if(empty($message)){
			$message=$this->codes[$code];
		}

		$result=['success'=>true, 'code'=>$code, 'message'=> $message, 'request_id'=>$this->utils->getRequestId(),
			'detail'=>['unimplemented'=>true]];

		return $this->returnJsonResult($result, $addOrigin, $origin, $pretty, $partial_output_on_error, $http_status_code, $http_status_text);

	}

	/**
	 * get internal api key from config
	 * @return string
	 */
	protected function getInternalAPIKey(){
		return $this->utils->getConfig('internal_player_center_api_key');
	}

	/**
	 * ping before login
	 * @return array 'pong'=>true
	 */
	public function ping_noauth(){
		if(!$this->initApi()){
			return false;
		}
		$result=[
			'code'=>self::CODE_OK,
			'data'=>[
				'pong'=>true,
			],
		];

		return $this->returnSuccessWithResult($result);
	}

	/**
	 * ping after login with token
	 * @return array 'pong'=>true, 'logged'=><boolean>
	 */
	public function ping_auth(){
		if(!$this->initApi()){
			return false;
		}

		$this->enableInternalJsonResult();
		$this->apiEcho($this->getInternalAPIKey());
		$this->disableInternalJsonResult();
		$apiResult=$this->getInternalJsonResult();

		$this->utils->debug_log('api echo result', $apiResult);

		if(!$apiResult['success']){
			$errorCode=self::CODE_SERVER_ERROR;
			if(isset($apiResult['code'])){
				$errorCode=$apiResult['code'];
			}
			return $this->returnErrorWithCode($errorCode);
		}else{
			$this->utils->debug_log('got player id', $this->player_id);
			$result=[
				'code'=>self::CODE_OK,
				'data'=>[
					'pong'=>true, 'logged'=>!empty($this->player_id),
				],
			];

			return $this->returnSuccessWithResult($result);
		}
	}

	protected function extractVirtualGameId($virtualGameId){
		$gamePlatformId=null;
		$gameUniqueId=null;
		if(!empty($virtualGameId)){
			$arr=explode('-', $virtualGameId);
			if(count($arr)>1){
				$gamePlatformId=intval(array_shift($arr));
				$gameUniqueId=implode('-', $arr);
			}
		}

		return [$gamePlatformId, $gameUniqueId];
	}

	protected function extractVirtualEventId($virtualEventId){
		return $this->extractVirtualGameId($virtualEventId);
	}

}
