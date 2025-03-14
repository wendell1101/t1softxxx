<?php

$config['RUNTIME_ENVIRONMENT'] = 'live';

//for 3 sites

//database
$config['db.default.hostname'] = 'mysqlserver';
$config['db.default.port'] = '3306';
$config['db.default.username'] = 'og';
$config['db.default.password'] = 'dcrajUg01';
$config['db.default.database'] = 'og';
$config['db.default.dbdriver'] = 'mysqli';
$config['db.default.dbprefix'] = '';
$config['db.default.pconnect'] = true;
$config['db.default.db_debug'] = true;
$config['db.default.cache_on'] = false;
$config['db.default.cachedir'] = '';
$config['db.default.char_set'] = 'utf8';
$config['db.default.dbcollat'] = 'utf8_unicode_ci';
$config['db.default.swap_pre'] = '';
$config['db.default.autoinit'] = true;
$config['db.default.stricton'] = false;

//read only, maybe different with main db, maybe delay
$config['db.readonly.hostname'] = 'mysqlserver';
$config['db.readonly.port'] = '3306';
$config['db.readonly.username'] = 'og';
$config['db.readonly.password'] = 'dcrajUg01';
$config['db.readonly.database'] = 'og';
$config['db.readonly.dbdriver'] = 'mysqli';
$config['db.readonly.dbprefix'] = '';
$config['db.readonly.pconnect'] = true;
$config['db.readonly.db_debug'] = true;
$config['db.readonly.cache_on'] = false;
$config['db.readonly.cachedir'] = '';
$config['db.readonly.char_set'] = 'utf8';
$config['db.readonly.dbcollat'] = 'utf8_unicode_ci';
$config['db.readonly.swap_pre'] = '';
$config['db.readonly.autoinit'] = true;
$config['db.readonly.stricton'] = false;

//multiple databases, always >= 2
$config['disabled_multiple_database']=true;
$config['multiple_databases']=[];
$config['multiple_databases_default_setting']=[
	'dbdriver' => 'mysqli',
	'dbprefix' => '',
	'pconnect' => TRUE,
	'db_debug' => TRUE,
	'cache_on' => FALSE,
	'cachedir' => '',
	'char_set' => 'utf8',
	'dbcollat' => 'utf8_unicode_ci',
	'swap_pre' => '',
	'autoinit' => TRUE,
	'stricton' => FALSE,
];
//always sync object data to mdb, but enable or not
$config['enable_object_to_other_mdb']=[
	'player'=>true, 'user'=>true, 'agency'=>true, 'affiliate'=>true, 'role'=>true,
];

$config['sync_middle_exchange_rate_in_mdb'] = true;
$config['enable_userInformation_sync_player_to_mdb'] = false;

/// sample:
// 'cny'=>['code'=>'CNY', 'name'=>'人民币', 'short_name'=>'元', 'symbol'=>'¥', 'decimals'=>2, 'dec_point'=>'.', 'thousands_sep'=>','
//                 , 'enable_selection_for_old_player_center' => true
//                 , 'enable_selection_for_new_player_center_api' => true
//                 , "white_list_ip" => ['1.2.3.4', '192.168.0.123']
//                 , "white_list_domain" => ['localhost', 'sssbet.vercel.app']
//         ],
//
//same key with multiple_databases
$config['multiple_currency_list']=[];
$config['default_currency_on_super']=[];
// $config['super_on_multiple_databases']=null;
//domain=>currency key
$config['domain_to_currency_key']=[];
$config['ip_country_to_currency_key']=[];
$config['super_default_currency_info']=['code'=>'CNY', 'name'=>'CN Yuan', 'short_name'=>'元', 'symbol'=>'¥', 'decimals'=>2, 'dec_point'=>'.', 'thousands_sep'=>',',
	'player_default_language'=>'Chinese', 'player_default_level_id'=>1,
	'default_language'=>'Chinese',
];

/// default is cloned from $config['db.default.database'], then to add the suffix, "_extra".
// "%s" for the param of the sprintf(), please reference to Utils::_getDailyBalanceInExtraDbWithMethod().
// If its in mdb: UnderLine+currency, ex:"og_cny_extra".
// If its in prod: EMPTY string. ex:"og_extra"
// The example in prod, onestop-STG:
// $config['extrasuffix_database_formater'] = 'og_onestop_staging%s_extra'; // result: og_onestop_staging_extra
// The example in mdb, sexycasino-STG:
// $config['extrasuffix_database_formater'] = 'og_sexycasino_staging%s_extra'; // result: og_sexycasino_staging_thb_extra
$config['extrasuffix_database_formater'] = 'og%s_extra';
$config['extrasuffix_database_table_list'] = []; // for game_logs_module::exportDeleteSqlByDay()
$config['balance_history_in_extra_db_method_list'] = [];
$config['daily_balance_in_extra_db_method_list'] = [];

/* sample:
	$config['super_report_settings']=[
		'master_currency' => [
			'code'=>'USDT',
			'name'=>'USDT',
			'short_name'=>'USDT',
			'symbol'=>'Tether',
			'decimals'=>2,
			'dec_point'=>'.',
			'thousands_sep'=>','
		],
		'master_exchange_rate_api' => [
			'default' => 'exchange_rate_api_huobi_super'
		]
	];
*/
$config['super_report_settings']=[
	'master_currency' => [],
	'master_exchange_rate_api' => [
        'default' => 'exchange_rate_api_huobi_super'
    ]
];
$config['equal_exchange_rate'] =['usd', 'usdt', 'usdc'];
$config['exchange_rate_api_huobi_super_decimal_place'] = 4;

//api constants

//external system settings
$config['default_prefix_for_username'] = '';
$config['external_system_types'] = array(SYSTEM_GAME_API, SYSTEM_PAYMENT);
$config['external_system_map'] = array();
$config['enable_payment_api_list_include_telephone_api'] = false;

$config['default_open_payment_iframe_mobile'] = false;
$config['default_open_payment_iframe_desktop'] = false;
# List the config keys here, and identify the ones that will be used for special database fields
# e.g. 'url' => 'API_URL', meaning $config['API_URL'] will be stored in both live_url and sandbox_url fields
# config keys not indexed by 'url|key|secret|account' will go in the extra_info field
$config['external_system_config_names'] = array();

$config['payment_account_types_all'] = array(
	MANUAL_ONLINE_PAYMENT => array('lang_key' => "pay.manual_online_payment", 'enabled' => true),
	LOCAL_BANK_OFFLINE => array('lang_key' => "pay.local_bank_offline", 'enabled' => true),
	AUTO_ONLINE_PAYMENT => array('lang_key' => "pay.auto_online_payment", 'enabled' => true),
);

$config['payment_account_second_category_types_all'] = array(
	SECOND_CATEGORY_ONLINE_BANK => array('lang_key' => "pay.second_category_online_bank", 'enabled' => true),
	SECOND_CATEGORY_ALIPAY => array('lang_key' => "pay.second_category_alipay", 'enabled' => true),
	SECOND_CATEGORY_WEIXIN => array('lang_key' => "pay.second_category_weixin", 'enabled' => true),
	SECOND_CATEGORY_QQPAY => array('lang_key' => "pay.second_category_qqpay", 'enabled' => true),
	SECOND_CATEGORY_UNIONPAY => array('lang_key' => "pay.second_category_unionpay", 'enabled' => true),
	SECOND_CATEGORY_QUICKPAY => array('lang_key' => "pay.second_category_quickpay", 'enabled' => true),
	SECOND_CATEGORY_PIXPAY => array('lang_key' => "pay.second_category_pixpay", 'enabled' => true),
	SECOND_CATEGORY_BANK_TRANSFER => array('lang_key' => "pay.second_category_bank_transfer", 'enabled' => true),
	SECOND_CATEGORY_ATM_TRANSFER => array('lang_key' => "pay.second_category_atm_transfer", 'enabled' => true),
	SECOND_CATEGORY_CRYPTOCURRENCY => array('lang_key' => "pay.second_category_cryptocurrency", 'enabled' => true),
);

$config['payment_account_types'] = array(
	MANUAL_ONLINE_PAYMENT => array('lang_key' => "pay.manual_online_payment", 'enabled' => true),
	AUTO_ONLINE_PAYMENT => array('lang_key' => "pay.auto_online_payment", 'enabled' => true),
	LOCAL_BANK_OFFLINE => array('lang_key' => "pay.local_bank_offline", 'enabled' => true),
);

//password
$config['queue_secret'] = null; // to config_local
$config['DESKEY_OG'] = null; // to config_local

$config['default_3rdparty_payment'] = IPS_PAYMENT_API;
#OGP-22917
$config['only_allow_one_pending_3rd_deposit'] = false;

$config['sync_sleep_seconds'] = 30;
$config['sync_balance_sleep_seconds'] = 120;
$config['sync_totals_sleep_seconds'] = 120;
$config['sync_batch_payout_sleep_seconds'] = 10;
$config['sync_get_response_sleep_seconds'] = 10;

$config['usePartitionTables4getPlayerTotalBetWinLoss'] = false;
$config['enablePartitionTables4getPlayerTotalBetWinLoss'] = [];
// total_player_game_minute always in enabled.
$config['enablePartitionTables4getPlayerTotalBetWinLoss'][] = 'total_player_game_hour'; // It has used in SBE > Report > Games Report
// $config['enablePartitionTables4getPlayerTotalBetWinLoss'][] = 'total_player_game_day';
// $config['enablePartitionTables4getPlayerTotalBetWinLoss'][] = 'total_player_game_month';
// $config['enablePartitionTables4getPlayerTotalBetWinLoss'][] = 'total_player_game_year';

$config['deposit_timeout_seconds'] = 3600;

$config['player_register_uri'] = '/iframe_module/iframe_register';

$config['show_realtime_balance'] = false;

//logs
$config['payment_error_log'] = APPPATH . 'logs/payment_error.log';
$config['queue_error_log'] = APPPATH . 'logs/queue_error.log';
$config['app_debug_log'] = APPPATH . 'logs/app_debug.log';
$config['runtime_debug_log'] = APPPATH . 'logs/runtime_debug.log';
// $config['aff_error_log'] = APPPATH . 'logs/aff_error.log';
$config['app_error_log'] = APPPATH . 'logs/app_error.log';

$config['testing_debug_log'] = APPPATH . 'logs/testing_debug.log';
$config['sync_game_records_log'] = APPPATH . 'logs/sync_game_records.log';
$config['sync_balance_log'] = APPPATH . 'logs/sync_balance.log';

$config['player_server_host'] = 'og.local';
$config['websocket_server_host'] = 'og.local:10080';

$config['enabled_origin'] = false;

$config['safe_origin_list'] = array('http://og.local', 'http://www.og.local', 'http://m.og.local', 'http://admin.og.local', 'http://aff.og.local');

// $config['msg_codes'] = array('login_failed' => 'error.login_failed', 'login_successfully' => 'notify.1',
//  'login_pt_failed' => 'error.login_pt_failed', 'login_pt_wrong_region' => 'login_pt_wrong_region');

//currency
$config['currency_list'] = array(
	'CNY' => 'CNY',
);

$config['default_currency'] = 'CNY';
$config['default_currency_name'] = '人民币';
$config['default_currency_short_name'] = '元';
$config['default_currency_symbol'] = '¥';
$config['default_currency_decimals'] = 2;
$config['default_currency_dec_point'] = '.';
$config['default_currency_thousands_sep'] = ',';

$config['display_currency_order'] = ['currency_name', 'currency_code', 'currency_symbol', 'currency_number', 'currency_short_name'];

$config['custom_deposit_rate'] = 1; #custom_deposit_rate > 1 means player should deposit more
$config['custom_withdrawal_rate'] = 1; #custom_withdrawal_rate < 1 means player will withdraw less
$config['custom_withdrawal_fee'] = 0.05; #fee rate, 0.05 means 5% will be reduced before transfering to player
$config['withdraw_amount_step_limit'] = 0.01;
$config['custom_cumulative_calculation_interval_for_max_daily_withdrawal'] = false; #count the Max Daily Withdrawal / H:i:s / set 15:01:01 will be 2020-08-01 15:01:01 to 2020-08-02 15:01:01;

//OGLANG-1594
$config['default_crypto_withdrawal_label'] = ['hint', 'rate', 'rate-msg'];

$config['default_withdrawal_conversion_label'] = ['hint', 'rate', 'rate-msg'];

$config['enable_auto_finish_promo_when_no_conditions'] = false;

$config['withdrawal_api_before_submit_dialog'] = array(
    '5107' => array(
        'title' => 'OTP',
        'message' => 'Check google authentication: <input id="player_input" type="text" class="form-control">',
        'confirm_label' => 'Confirm',
        'close_label' => 'Close'
    ),
    '5135' => array(
        'title' => 'OTP',
        'message' => 'Check google authentication: <input id="player_input" type="text" class="form-control">',
        'confirm_label' => 'Confirm',
        'close_label' => 'Close'
    )
);


$config['deposit_api_before_submit_dialog'] = array(
    '5191' => array(
        'title' => '来帐编号',
        'message' => '请输入来帐编号: <input id="player_input" type="text" class="form-control">',
        'confirm_label' => 'Confirm',
        'close_label' => 'Close'
    )
);

$config['ip_city_db_path'] = '/usr/share/GeoIP/GeoLite2-City.mmdb';
$config['ip_county_db_path'] = '/usr/share/GeoIP/GeoLite2-Country.mmdb';
$config['special_payment_list'] = array('bank_type_alipay', 'bank_type_wechat');
$config['default_sync_game_logs_max_time_second'] = 10800; //3 hours

$config['default_lang'] = 'english';

$config['default_items_pre_page'] = 10;

$config['multiple_range_items_per_page'] = 10;
$config['multiple_range_by_game_types_items_per_page'] = 10;

$config['account_image_width'] = 150;
$config['account_image_height'] = 150;

// a list of games can bet on both side , for example bet both on "banker" and "player" in bacarrat game.
$config['can_bet_both_side'] = array(
	PT_API => array(
		'ba', 'pba', 'sb', 'rop', 'ro', 'ro_g', 'ro3d', 'rodz', 'rodz_g', 'rom',
		'rop_g', 'rouk', '7bal', 'bal', 'bjl', 'plba', 'rofl', 'rol', 'sbl', 'vbal',
	),
	AG_API => array(
		'bac', 'cbac', 'link', 'dt', 'shb', 'rou', 'ft', 'lbac',
	),
);

$config['default_network_timeout'] = 22;
$config['default_soap_timeout'] = $config['default_network_timeout'];
$config['default_http_timeout'] = $config['default_network_timeout'];
$config['default_timeout_for_queryPlayerBalance']=10;
$config['default_connect_timeout'] = 3;
$config['default_connect_timeout_for_queryPlayerBalance']=3;

$config['default_player_bootstrap_css'] = 'themes/bootstrap.yeti.css';

$config['use_specific_css_in_mobile'] = false; //'m', 'www', 'player' or any prefix in config prefix_website_list

$config['default_site_name'] = 'default';

$config['si_background'] = APPPATH . 'libraries/captcha/backgrounds/bg5.png';

$config['si_active'] = 'si_easy'; // easy, medium, hard
$config['si_active_domain_assignment'] = [];

$config['si_general'] = array(
    'namespace' => 'default',
	'ttf_file' => APPPATH . '/libraries/captcha/AHGBold.ttf',
	'image_signature' => '',
	'signature_color' => '#abcdef',
	'case_sensitive' => false,
	'image_height' => 40,
	'image_bg_color' => '#0099CC',
	'text_color' => '#EAEAEA',
	'line_color' => '#EAEAEA',
	'image_type' => 'Securimage::SI_IMAGE_JPEG',
	'use_wordlist' => false,
	'text_transparency_percentage' => 90,
	'use_transparent_text' => false,
	'charset' => '0123456789',
	// 'charset' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ23456789',
);

$config['si_easy'] = array(
	'code_length' => 4,
	'perturbation' => .5,
	'num_lines' => '2',
	'image_width' => 120,
	'noise_level' => 10,
);

$config['si_medium'] = array(
	'code_length' => 7,
	'perturbation' => .82,
	'num_lines' => rand(8, 10),
	'image_width' => 260,
);

$config['si_hard'] = array(
	'code_length' => 9,
	'perturbation' => 1.1,
	'num_lines' => rand(10, 12),
	'image_width' => 320,
);

/**
 * @deprecated
 */
$config['captcha_registration'] = true;
$config['captcha_login'] = false;
$config['captcha_agency_login'] = false;

$config['site_logo_width'] = 80;
$config['site_logo_height'] = 80;

//default is 5000k
//dont change the default file size. thanks.
$config['max_upload_size_byte'] = 5000000; // Ref. to https://talk.letschatchat.com/smartbackend/pl/dpjy5omjd3rpmggpcffgc888uo
$config['upload_image_max_width'] = 500;
$config['upload_image_max_height'] = 100;
$config['upload_promo_csv_max_row'] = 50;
// OGP-19541: Set size limit for promo csv file upload
$config['upload_promo_file_max_size'] = 2097152;
$config['promotion_list_available_days'] = null;

//show game in game tree
$config['show_particular_game_in_tree'] = true;
//show player level in tree
$config['show_player_level_in_tree'] = true;
//only pay > min_cashback_amount
$config['min_cashback_amount'] = 1;

$config['balance_from_api'] = false;

//for checking deposit
$config['checking_deposit_locking'] = true;
$config['checking_withdrawal_locking'] = true;

$config['debug_session_lost'] = false;
$config['show_password_in_login_page'] = false;

$config['ping_time'] = 120000;

$config['enable_profiler'] = false;

$config['color'] = array(
	// Game Log
	"free_game" => "E45072", // bet = 0
	"trans_in_game_log" => "A4E450", // flag = 2
);

$config['defaultMinDepositDaily'] = NULL;
$config['defaultMaxDepositDaily'] = 1000000;
$config['defaultTotalDeposit'] = 10000000;
$config['priority_display_manual_deposit_list_category_view'] = false;

#OGP-23414
$config['enable_manual_deposit_bank_hyperlink'] = false;

$config['deploy_token'] = null;
$config['debug_bofo'] = false;

// Affiliate
$config['baseIncomeConfig'] = 1;
$config['subAffiliateLevels'] = 10;
$config['aff_min_withdrawal'] = 100;
$config['enable_player_benefit_fee_queue'] = true;

$config['dont_save_response_in_api'] = false;
$config['active_only_in_api'] = false;

$config['report_path'] = APPPATH . '/../public/reports';
$config['transaction_report_start_hour'] = '12';
$config['transaction_report_end_hour'] = '11';

$config['debug_promo'] = false;
$config['promo_application_list_default_sort'] = array('sort' => 'createdOn', 'orderBy' => 'DESC');#original is promoName

// monthly commission
$config['show_calculate_button'] = false;
$config['slack_url'] = ''; // https://hooks.slack.com/services/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
$config['slack_user'] = '';
$config['slack_channel'] = '#php_fatal_error';
$config['slack_notify_channel'] = '#service_notification';
$config['payment_account_notify_url'] = 'https://talk.letschatchat.com/hooks/1kyocb1hm38n3krcd57gxhj3hh';
$config['payment_account_notify_user'] = 'default';
$config['payment_account_notify_channel'] = '#payment-account-notify';
$config['payment_alert_channel'] = '#payment-alert';

$config['payment_account_over_limit_percentage'] = 90;
$config['payment_account_max_deposit_daily_over_limit_percentage'] = 90;
$config['update_payment_account_deposit_amount_when_approve_sale_order'] = true;
$config['update_player_approved_deposit_count_when_approve_sale_order'] = true;
$config['update_player_declined_deposit_count_when_approve_sale_order'] = true;
$config['debug_data_table_sql'] = false;

$config['enable_async_approve_sale_order'] = false;

$config['debug_print_sql'] = true;
$config['loadjs_timeout'] = 0;

$config['default_affiliate_settings'] = '{"baseIncomeConfig": "2","level_master":"50","minimumPayAmount": "0","paymentDay": "1","admin_fee": "0", "transaction_fee": "0", "bonus_fee": "0","cashback_fee": "100"}';
$config['affiliate_default_terms'] = '{"terms": {"terms_type": "option1","totalactiveplayer": "10","minimumBetting": "10000","minimumDeposit": "1000","provider": []}}';
$config['sub_affiliate_default_terms'] = '{"terms": {"terms_type": "allow","manual_open":"manual","sub_link":"link","sub_level":"5","sub_levels":[25,2,1,0,0]}}';
$config['use_total_hour'] = true;
$config['use_total_minute'] = true;

$config['use_total_summary'] = true;

$config['min_win_amount_for_newest'] = 900;
$config['min_win_amount_for_top10'] = 1000;
$config['enabled_web_push'] = false;

$config['enabled_rescue_promotion'] = true;
$config['rescue_promotion_amount'] = 5.0;

$config['daily_max_random_bonus'] = 3000;
$config['random_bonus_withdraw_condition_times'] = 5;
$config['update_withdraw_condition_bet_amount'] = false;
$config['min_random_bonus_rate'] = 1;
$config['max_random_bonus_rate'] = 5;

$config['random_bonus_top_img_url'] = 'random_bonus/hongbao_bgtop.png';
$config['random_bonus_center_img_url'] = 'random_bonus/hongbao.png';
$config['random_bonus_result_img_url'] = 'random_bonus/hongbao_bg.jpg';
$config['random_bonus_liondance1_swf_url'] = 'random_bonus/liondance1.swf';
$config['random_bonus_liondance50_swf_url'] = 'random_bonus/liondance50.swf';
$config['random_bonus_result_1_swf_url'] = '/resources/images/random_bonus/liondance1.swf';
$config['random_bonus_result_50_swf_url'] = '/resources/images/random_bonus/liondance50.swf';
$config['random_bonus_result_1_img_url'] = '/resources/images/random_bonus/liondance1.jpg';
$config['random_bonus_result_50_img_url'] = '/resources/images/random_bonus/liondance50.jpg';

$config['oneworks_sportsbook_img_url'] = 'oneworks/oneworks_sportsbook_bg.jpg';

#deposit_loading_gif option 1~6
$config['deposit_loading_gif'] = '/resources/images/loading/loading_player_submit_deposit_img_2.gif';

// const RANDOM_BONUS_MODE_PERCENT_DEPOSIT = 1;
// const RANDOM_BONUS_MODE_COUNTING = 2;
// const RANDOM_BONUS_MODE_FIXED_ITEM = 3;
$config['random_bonus_mode'] = 1;
$config['use_self_pick_group'] = false;
$config['use_self_pick_subwallets'] = false;
$config['big_bonus_trigger_count_number'] = 50;
$config['big_random_bonus_amount'] = 50;
$config['small_bonus_amount'] = 1;
$config['random_bonus_mode_counting_sandbox_mode'] = false;

$config['random_bonus_fixed_items'] = array(
	array('rate_by_deposit' => 8, 'probability' => 12.5, 'limit' => -1),
	array('rate_by_deposit' => 18, 'probability' => 12.5, 'limit' => -1),
	array('rate_by_deposit' => 28, 'probability' => 12.5, 'limit' => -1),
	array('rate_by_deposit' => 38, 'probability' => 12.5, 'limit' => -1),
	array('rate_by_deposit' => 58, 'probability' => 12.5, 'limit' => -1),
	array('rate_by_deposit' => 68, 'probability' => 12.5, 'limit' => -1),
	array('rate_by_deposit' => 78, 'probability' => 12.5, 'limit' => -1),
	array('rate_by_deposit' => 88, 'probability' => 12.5, 'limit' => -1),
);

$config['temp_disabled_game_api'] = array();

$config['auto_set_checking_to_request'] = true;

//default setting of cashback,start from yesterday, don't use number, use string, begin with 0 if only one number
$config['cashback_start_hour'] = '12';
$config['cashback_end_hour'] = '11';
$config['pay_time_hour'] = '14:00';
$config['cashback_days_ago'] = '1';

$config['print_verbose_game_api'] = false;
$config['salt_only_8_keys'] = true;
$config['default_open_search_panel'] = true;
$config['ignore_amount_limit_for_loadcard'] = true;
$config['min_loadcard_amount'] = 10;

$config['temp_disable_sbe_acc_setting'] = true;
$config['temp_disable_sbe_register_setting'] = true;

// $config['live_chat_url'] = 'https://live.chatchat365.local';
// $config['live_chat_auto_login_uri'] = '/index.php/user/autologin';
// $config['live_chat_secret'] = ''; //from autologin key
// $config['live_chat_encrypt_key1'] = ''; // from start chat key
// $config['live_chat_encrypt_key2'] = ''; // from start chat key
// $config['live_chat_api_key'] = ''; // rest key
// $config['live_chat_api_secret'] = ''; // rest key
// $config['live_chat_options'] = array('widget_height' => 340, 'widget_width' => 300,
//     'popup_height' => 520, 'popup_width' => 500, 'theme'=>1, 'department'=>1);
// $config['live_chat_frontend_host'] = 'live.chatchat365.local';
// $config['live_chat_department'] = 1;

$config['payment_account_hide_bank_info'] = array('bank_type_wechat');
$config['payment_account_hide_bank_type'] = array('bank_type_alipay');
$config['auto_open_payment_account'] = false;

$config['token_timeout'] = 7200;

$config['random_bonus_intro_url'] = 'http://www.og.local/promo/random_bonus_intro.html';
$config['random_bonus_result_url'] = 'http://www.og.local/promo/random_bonus_result.html';
$config['random_bonus_not_available_url'] = 'http://www.og.local/promo/random_bonus_not_available.html';

$config['default_affiliate_id'] = null;

$config['enable_3rd_party_affiliate'] = false;
$config['assigned_affiliate_for_affiliate_network'] = false;

$config['default_manually_deposit_status'] = 'pending'; // or pending
$config['default_manually_withdraw_status'] = 'pending'; // or settled

$config['enable_ping'] = false;

$config['enable_bank_box_for_deposit'] = false;
$config['use_site_url_for_resource'] = true;
$config['aff_analytic_code'] = "";
$config['agency_analytic_code'] = "";
$config['player_analytic_code'] = "";
$config['admin_analytic_code'] = "";

$config['default_site_name'] = 'default';

$config['hide_host_on_url'] = true;
$config['blocked_country_for_admin'] = array();
$config['blocked_country_for_player'] = array();
$config['enable_debugbar'] = false;

#OGP-21771
$config['hide_currency_list_in_player_login_page'] = false;
#OGP-27278
$config['hide_currency_list_in_player_logged_page'] = false;

$config['always_https'] = false;

$config['default_forbidden_names'] = ['admin', 'moderator', 'hoster', 'administrator', 'mod', 'shit', 'fuck'];

$config['admin_captcha_secret_key'] = null;

$config['record_full_ip'] = false;
$config['hide_player_contact_on_aff'] = false;
$config['disable_transfer_on_credit_aff'] = false;

$config['always_keep_admin_session'] = false;
$config['not_change_session_id_on_update'] = true;
$config['user_game_logs_for_cashback'] = false;
$config['user_game_logs_for_withdraw_condition'] = true;
$config['logout_pt_before_login'] = false;

$config['view_template'] = 'iframe'; // ['ttt'] default : iframe
$config['template_settings'] = []; //logo, icon or css
$config['view_template_extra'] = array();
$config['webet_routing_active'] = false;
$config['affiliate_view_template'] = 'affiliate'; // default : affiliate
$config['affiliate_signin_show_header'] = false;
$config['aff_contact_has_message_header'] = TRUE;
$config['aff_contact_email'] = 'sales@tot.bet';
$config['aff_contact_qq'] = '';
$config['aff_contact_skype'] = '';
$config['aff_contact_whatsapp'] = '';
# If aff_contact_type and aff_contact_type_label are both defined, the qq and skype setup above will be disregarded
$config['aff_contact_type'] = '';
$config['aff_contact_type_label'] = 'QQ联系';
$config['live_chat_used'] = 'default'; // liveperson
$config['deposit_bank_slip_upload'] = false;
//should be link to Code/pub/$hostname/upload
$config['UPLOAD_PATH'] = realpath(APPPATH . '/../public/upload/');
$config['PUBLIC_UPLOAD_PATH'] = '/upload';
$config['PLAYER_INTERNAL_BASE_URL'] = '/player/upload';

$config['mail_smtp_server'] = 'mail.nothing.com';
$config['mail_smtp_port'] = 587;
$config['mail_smtp_auth'] = true;
$config['mail_smtp_secure'] = 'tls';
$config['mail_smtp_username'] = 'noreply@nothing.com';
$config['mail_smtp_password'] = ''; // to config_secret_local
$config['mail_from'] = 'noreply@nothing.com'; //'noreply@mail.smartbackend.com';
$config['disable_smtp_ssl_verify'] = true;
$config['launcher_domain'] = 'http://nothing/';

$config['gearman_server'] = array('127.0.0.1');
$config['gearman_port'] = array('4730');

$config['disable_copy_cut'] = false;
$config['disable_contextmenu'] = false;

// $config['enable_always_active_for_api'] = false;
//top level
$config['disabled_api_list'] = array(); // array(IMPT_API);
$config['enabled_show_password'] = false;

$config['enable_clockwork'] = false;
$config['enable_readonly_db'] = true;

/////for sms
$config['sms_global_max_per_minute'] = 60; # Number of total Verification Code SMS allowed system-wide per minute
$config['sms_cooldown_time'] = 60; # Number of seconds between customer can request to send Verification Code SMS again
$config['sms_cooldown_time_per_ip'] = 2; # per ip
$config['sms_max_per_num_per_day'] = 5; # For a same number, only allow to send X SMS per day
$config['sms_valid_time'] = 600; # Number of seconds a SMS verification code remains valid
$config['sms_api'] = array(); # array('sms_api_luosimao', 'sms_api_ucpaas'); # classname of sms api implementation, also takes an array
$config['telephone_api'] = array();
$config['sms_from'] = ''; # this will appear at the end of the SMS sent
$config['sms_lang'] = 2; # Empty, or 1, or 2. When it's not empty, sms content will be forced to use the defined language. 1 = en, 2 = zh-cn
$config['sms_default_country'] = '86';
# Note : (Currently sms_restrict_send_num has only player center's phone verification settings)
$config['sms_restrict_send_num'] = false; # false or int, Use enable_restrict_sms_send_num_in_player_center_phone_verification option to enable the function in system feature
$config['sms_content_template'] = '欢迎您加入, 您的验证码： {%s} 更多优惠详情请联系在线客服。';
$config['sms_registration_template'] = '欢迎您加入，请进入 {player_center_url} ，使用用户名 {player_username} 登录';
$config['disabled_sms'] = false;
$config['disabled_voice'] = true;
$config['voice_api'] = array();
$config['notify_api'] = array(); // notify in app
$config['notify_api_chunk_amount'] = 100;


$config['Sms_api_luosimao_apikey'] = '';
$config['Sms_api_twilio'] = [
	'account_sid' => '',
	'auth_token'  => '',
	'from_phone_number' => ''
];

# For email
$config['email_cooldown_time'] = 30; # Number of seconds between a same player can trigger an email to be sent from the system (forget password, etc)

$config['enabled_player_cancel_pending_withdraw'] = false;
$config['enabled_agency'] = false;

$config['agent_tracking_link_format'] = 'ag';

# withdraw_verification defines the verification method used when user submits a withdrawal request.
# possible values: off, withdrawal_password, password, sms
# Note: This setting should be configured under config_secret_local as it's used on both admin and player domain
# password: same as login password
# sms: send an SMS verification code to registered handphone number
# withdrawal_password: separate withdrawal password
$config['withdraw_verification'] = 'off';

$config['withdraw_api'] = /*PAY24K_PAYMENT_API;*/'off'; # possible values: off (means manual withdraw only), or API constant like PAY24K_PAYMENT_API

$config['show_point_on_player'] = false;
$config['show_group_level_on_player'] = false;
$config['responsible_gaming'] = false;
$config['responsible_gaming_self_exclusion_period_list'] = [
    ['value' => 6, 'interval_spec' =>'P6M', 'text_suffix' => 'reg.59'],
    ['value' => 1, 'interval_spec' =>'P1Y', 'text_suffix' => 'reg.14'],
    ['value' => 3, 'interval_spec' =>'P3Y', 'text_suffix' => 'reg.14'],
    ['value' => 5, 'interval_spec' =>'P5Y', 'text_suffix' => 'reg.14'],
];
$config['make_up_transfer_record'] = false;
$config['enable_sbe_login_page_logo'] = true;

//url link
$config['admin_url'] = '';
$config['player_site_url'] = '';
$config['aff_site_url'] = '';
$config['aff_sub_affiliate_link'] = 'affiliate/register';

$config['cronjob_email_from'] = 'admin@nothing.com';
$config['cronjob_email_to'] = '';

/// The cronjob name of $config['all_cron_jobs'] in the list
$config['cronjob_list_func_name_with_mdb_suffix_at_tail'] = [];
/// The specified cronjob will be append mdb suffix param to cmd,
// please DONOT ignore default params until to $suffix4mdb.
$config['cronjob_list_func_name_with_mdb_suffix_at_tail'][] = 'cronjob_batch_player_level_upgrade'; // cronjob name
$config['cronjob_list_func_name_with_mdb_suffix_at_tail'][] = 'cronjob_batch_player_level_upgrade_hourly'; // cronjob name
$config['cronjob_list_func_name_with_mdb_suffix_at_tail'][] = 'cronjob_generatePlayerQuestState'; // cronjob name

$config['pubnub_subscribe_key'] = '';
$config['channel_admin_announcement'] = 'admin_announcement';

$config['server_name'] = '';

$config['always_enable_unknown_games_on_callback'] = true;

$config['country_list'] = [
	'AF' => 'AFGHANISTAN',
	'AL' => 'ALBANIA',
	'DZ' => 'ALGERIA',
	'AS' => 'AMERICAN SAMOA',
	'AD' => 'ANDORRA',
	'AO' => 'ANGOLA',
	'AI' => 'ANGUILLA',
	'AQ' => 'ANTARCTICA',
	'AG' => 'ANTIGUA AND BARBUDA',
	'AR' => 'ARGENTINA',
	'AM' => 'ARMENIA',
	'AW' => 'ARUBA',
	'AU' => 'AUSTRALIA',
	'AT' => 'AUSTRIA',
	'AZ' => 'AZERBAIJAN',
	'BS' => 'BAHAMAS',
	'BH' => 'BAHRAIN',
	'BD' => 'BANGLADESH',
	'BB' => 'BARBADOS',
	'BY' => 'BELARUS',
	'BE' => 'BELGIUM',
	'BZ' => 'BELIZE',
	'BJ' => 'BENIN',
	'BM' => 'BERMUDA',
	'BT' => 'BHUTAN',
	'BO' => 'BOLIVIA',
	'BA' => 'BOSNIA AND HERZEGOVINA',
	'BW' => 'BOTSWANA',
	'BV' => 'BOUVET ISLAND',
	'BR' => 'BRAZIL',
	'IO' => 'BRITISH INDIAN OCEAN TERRITORY',
	'BN' => 'BRUNEI DARUSSALAM',
	'BG' => 'BULGARIA',
	'BF' => 'BURKINA FASO',
	'BI' => 'BURUNDI',
	'KH' => 'CAMBODIA',
	'CM' => 'CAMEROON',
	'CA' => 'CANADA',
	'CV' => 'CAPE VERDE',
	'KY' => 'CAYMAN ISLANDS',
	'CF' => 'CENTRAL AFRICAN REPUBLIC',
	'TD' => 'CHAD',
	'CL' => 'CHILE',
	'CN' => 'CHINA',
	'CX' => 'CHRISTMAS ISLAND',
	'CC' => 'COCOS (KEELING) ISLANDS',
	'CO' => 'COLOMBIA',
	'KM' => 'COMOROS',
	'CG' => 'CONGO',
	'CD' => 'CONGO, THE DEMOCRATIC REPUBLIC OF THE',
	'CK' => 'COOK ISLANDS',
	'CR' => 'COSTA RICA',
	'CI' => 'COTE D IVOIRE',
	'HR' => 'CROATIA',
	'CU' => 'CUBA',
	'CY' => 'CYPRUS',
	'CZ' => 'CZECH REPUBLIC',
	'DK' => 'DENMARK',
	'DJ' => 'DJIBOUTI',
	'DM' => 'DOMINICA',
	'DO' => 'DOMINICAN REPUBLIC',
	'TP' => 'EAST TIMOR',
	'EC' => 'ECUADOR',
	'EG' => 'EGYPT',
	'SV' => 'EL SALVADOR',
	'GQ' => 'EQUATORIAL GUINEA',
	'ER' => 'ERITREA',
	'EE' => 'ESTONIA',
	'ET' => 'ETHIOPIA',
	'FK' => 'FALKLAND ISLANDS (MALVINAS)',
	'FO' => 'FAROE ISLANDS',
	'FJ' => 'FIJI',
	'FI' => 'FINLAND',
	'FR' => 'FRANCE',
	'GF' => 'FRENCH GUIANA',
	'PF' => 'FRENCH POLYNESIA',
	'TF' => 'FRENCH SOUTHERN TERRITORIES',
	'GA' => 'GABON',
	'GM' => 'GAMBIA',
	'GE' => 'GEORGIA',
	'DE' => 'GERMANY',
	'GH' => 'GHANA',
	'GI' => 'GIBRALTAR',
	'GR' => 'GREECE',
	'GL' => 'GREENLAND',
	'GD' => 'GRENADA',
	'GP' => 'GUADELOUPE',
	'GU' => 'GUAM',
	'GT' => 'GUATEMALA',
	'GN' => 'GUINEA',
	'GW' => 'GUINEA-BISSAU',
	'GY' => 'GUYANA',
	'HT' => 'HAITI',
	'HM' => 'HEARD ISLAND AND MCDONALD ISLANDS',
	'VA' => 'HOLY SEE (VATICAN CITY STATE)',
	'HN' => 'HONDURAS',
	'HK' => 'HONG KONG',
	'HU' => 'HUNGARY',
	'IS' => 'ICELAND',
	'IN' => 'INDIA',
	'ID' => 'INDONESIA',
	'IR' => 'IRAN, ISLAMIC REPUBLIC OF',
	'IQ' => 'IRAQ',
	'IE' => 'IRELAND',
	'IL' => 'ISRAEL',
	'IT' => 'ITALY',
	'JM' => 'JAMAICA',
	'JP' => 'JAPAN',
	'JO' => 'JORDAN',
	'KZ' => 'KAZAKSTAN',
	'KE' => 'KENYA',
	'KI' => 'KIRIBATI',
	'KP' => 'KOREA DEMOCRATIC PEOPLES REPUBLIC OF',
	'KR' => 'KOREA REPUBLIC OF',
	'KW' => 'KUWAIT',
	'KG' => 'KYRGYZSTAN',
	'LA' => 'LAO PEOPLES DEMOCRATIC REPUBLIC',
	'LV' => 'LATVIA',
	'LB' => 'LEBANON',
	'LS' => 'LESOTHO',
	'LR' => 'LIBERIA',
	'LY' => 'LIBYAN ARAB JAMAHIRIYA',
	'LI' => 'LIECHTENSTEIN',
	'LT' => 'LITHUANIA',
	'LU' => 'LUXEMBOURG',
	'MO' => 'MACAU',
	'MK' => 'MACEDONIA, THE FORMER YUGOSLAV REPUBLIC OF',
	'MG' => 'MADAGASCAR',
	'MW' => 'MALAWI',
	'MY' => 'MALAYSIA',
	'MV' => 'MALDIVES',
	'ML' => 'MALI',
	'MT' => 'MALTA',
	'MH' => 'MARSHALL ISLANDS',
	'MQ' => 'MARTINIQUE',
	'MR' => 'MAURITANIA',
	'MU' => 'MAURITIUS',
	'YT' => 'MAYOTTE',
	'MX' => 'MEXICO',
	'FM' => 'MICRONESIA, FEDERATED STATES OF',
	'MD' => 'MOLDOVA, REPUBLIC OF',
	'MC' => 'MONACO',
	'MN' => 'MONGOLIA',
	'MS' => 'MONTSERRAT',
	'MA' => 'MOROCCO',
	'MZ' => 'MOZAMBIQUE',
	'MM' => 'MYANMAR',
	'NA' => 'NAMIBIA',
	'NR' => 'NAURU',
	'NP' => 'NEPAL',
	'NL' => 'NETHERLANDS',
	'AN' => 'NETHERLANDS ANTILLES',
	'NC' => 'NEW CALEDONIA',
	'NZ' => 'NEW ZEALAND',
	'NI' => 'NICARAGUA',
	'NE' => 'NIGER',
	'NG' => 'NIGERIA',
	'NU' => 'NIUE',
	'NF' => 'NORFOLK ISLAND',
	'MP' => 'NORTHERN MARIANA ISLANDS',
	'NO' => 'NORWAY',
	'OM' => 'OMAN',
	'PK' => 'PAKISTAN',
	'PW' => 'PALAU',
	'PS' => 'PALESTINIAN TERRITORY, OCCUPIED',
	'PA' => 'PANAMA',
	'PG' => 'PAPUA NEW GUINEA',
	'PY' => 'PARAGUAY',
	'PE' => 'PERU',
	'PH' => 'PHILIPPINES',
	'PN' => 'PITCAIRN',
	'PL' => 'POLAND',
	'PT' => 'PORTUGAL',
	'PR' => 'PUERTO RICO',
	'QA' => 'QATAR',
	'RE' => 'REUNION',
	'RO' => 'ROMANIA',
	'RU' => 'RUSSIAN FEDERATION',
	'RW' => 'RWANDA',
	'SH' => 'SAINT HELENA',
	'KN' => 'SAINT KITTS AND NEVIS',
	'LC' => 'SAINT LUCIA',
	'PM' => 'SAINT PIERRE AND MIQUELON',
	'VC' => 'SAINT VINCENT AND THE GRENADINES',
	'WS' => 'SAMOA',
	'SM' => 'SAN MARINO',
	'ST' => 'SAO TOME AND PRINCIPE',
	'SA' => 'SAUDI ARABIA',
	'SN' => 'SENEGAL',
	'SC' => 'SEYCHELLES',
	'SL' => 'SIERRA LEONE',
	'SG' => 'SINGAPORE',
	'SK' => 'SLOVAKIA',
	'SI' => 'SLOVENIA',
	'SB' => 'SOLOMON ISLANDS',
	'SO' => 'SOMALIA',
	'ZA' => 'SOUTH AFRICA',
	'GS' => 'SOUTH GEORGIA AND THE SOUTH SANDWICH ISLANDS',
	'ES' => 'SPAIN',
	'LK' => 'SRI LANKA',
	'SD' => 'SUDAN',
	'SR' => 'SURINAME',
	'SJ' => 'SVALBARD AND JAN MAYEN',
	'SZ' => 'SWAZILAND',
	'SE' => 'SWEDEN',
	'CH' => 'SWITZERLAND',
	'SY' => 'SYRIAN ARAB REPUBLIC',
	'TW' => 'TAIWAN, PROVINCE OF CHINA',
	'TJ' => 'TAJIKISTAN',
	'TZ' => 'TANZANIA, UNITED REPUBLIC OF',
	'TH' => 'THAILAND',
	'TG' => 'TOGO',
	'TK' => 'TOKELAU',
	'TO' => 'TONGA',
	'TT' => 'TRINIDAD AND TOBAGO',
	'TN' => 'TUNISIA',
	'TR' => 'TURKEY',
	'TM' => 'TURKMENISTAN',
	'TC' => 'TURKS AND CAICOS ISLANDS',
	'TV' => 'TUVALU',
	'UG' => 'UGANDA',
	'UA' => 'UKRAINE',
	'AE' => 'UNITED ARAB EMIRATES',
	'GB' => 'UNITED KINGDOM',
	'US' => 'UNITED STATES',
	'UM' => 'UNITED STATES MINOR OUTLYING ISLANDS',
	'UY' => 'URUGUAY',
	'UZ' => 'UZBEKISTAN',
	'VU' => 'VANUATU',
	'VE' => 'VENEZUELA',
	'VN' => 'VIET NAM',
	'VG' => 'VIRGIN ISLANDS, BRITISH',
	'VI' => 'VIRGIN ISLANDS, U.S.',
	'WF' => 'WALLIS AND FUTUNA',
	'EH' => 'WESTERN SAHARA',
	'YE' => 'YEMEN',
	'YU' => 'YUGOSLAVIA',
	'ZM' => 'ZAMBIA',
	'ZW' => 'ZIMBABWE',
];

$config['always_run_cron_job'] = array();

$config['log_server_enabled'] = false;
$config['log_server_address'] = '';
$config['log_server_port'] = 12201;

$config['default_level_id'] = 1; //VIP LEVEL
$config['default_dispatch_account_group_id'] = 1;
$config['default_dispatch_account_level_id'] = 1;
$config['dispatch_account_batch_move_player_limit'] = 1000;
$config['dispatch_account_min_member_limit'] = 100; //Minimum member of each level
$config['enabled_features'] = array('promorules.allowed_affiliates', 'promorules.allowed_players',
    'player_list_on_affiliate', 'auto_refresh_balance_on_cashier',
    'generate_player_token_login', 'create_ag_demo', 'transaction_request_notification',
    'show_admin_support_live_chat', 'popup_window_on_player_center_for_mobile',
    'hide_deposit_approve_decline_button_on_timeout', 'affiliate_monthly_earnings', 'show_payment_account_image', 'agent_settlement_to_wallet',
    'enable_currency_symbol_in_the_withdraw', 'always_calc_before_pay_cashback'
);

$config['default_to_new_features'] = ['promorules.allowed_affiliates', 'promorules.allowed_players', 'agency', 'switch_to_player_secure_id_on_affiliate', 'show_admin_support_live_chat', 'transaction_request_notification', 'affiliate_additional_domain', 'affiliate_source_code', 'show_unsettle_game_logs', 'auto_refresh_balance_on_cashier', 'generate_player_token_login', 'login_as_agent', 'rolling_comm_for_player_on_agency', 'always_update_subagent_and_player_status', 'export_excel_on_queue', 'deposit_withdraw_transfer_list_on_player_info' ,'sync_api_password_on_update' ,'check_player_session_timeout' ,'show_bet_detail_on_game_logs' ,'donot_show_registration_verify_email' ,'popup_window_on_player_center_for_mobile' ,'enabled_withdrawal_password' ,'affiliate_player_report' ,'affiliate_game_history' ,'affiliate_credit_transactions' ,'notification_promo' ,'notification_messages' ,'notification_local_bank' ,'notification_thirdparty' ,'notification_withdraw' ,'notify_affiliate_withdraw' ,'affiliate_monthly_earnings' ,'enabled_refresh_message_on_player' ,'send_sms_after_registration' ,'display_referral_code_in_player_details' ,'disabled_auto_create_game_account_on_registration' ,'show_total_balance_without_pending_withdraw_request' ,'hide_promo_code_on_deposit_page' ,'enabled_single_wallet_switch' ,'create_ag_demo' ,'create_agin_demo' ,'enabled_check_frondend_block_status' ,'enabled_switch_to_mobile_on_www' ,'player_center_sidebar_transfer' ,'player_center_sidebar_deposit' ,'show_decimal_amount_hint' ,'disable_player_deposit_bank' ,'show_pending_deposit' ,'show_declined_deposit' ,'show_total_deposit_amount_today' ,'check_withdrawal_conditions' ,'check_withdrawal_conditions_foreach' ,'enabled_auto_clear_withdraw_condition' ,'enabled_auto_check_withdraw_condition' ,'enabled_display_change_withdrawal_password_message_note' ,'show_total_withdrawal_amount_today' ,'enable_player_center_live_chat' ,'enabled_switch_www_to_https' ,'enabled_auto_switch_to_mobile_on_www' ,'disable_player_multiple_upgrade' ,'hidden_vip_betting_Amount_part' ,'disable_display_agent_code_on_player_center_agent_register_page' ,'disable_display_affiliate_code_on_player_center_affiliate_register_page' ,'hidden_affiliate_code_on_player_center_when_exists_referral_code' ,'hidden_agent_code_on_player_center_when_exists_referral_code' ,'enabled_forgot_withdrawal_password_use_email_to_reset' ,'agent_settlement_to_wallet' ,'show_agent_name_on_game_logs' ,'agency_information_self_edit' ,'enable_player_report_generator' ,'use_https_for_agent_tracking_links' ,'settlement_include_all_downline' ,'affiliate_tracking_code_numbers_only' ,'summary_report_2' ,'hide_retype_email_field_on_registration' ,'kickout_game_when_kickout_player' ,'bind_promorules_to_friend_referral' ,'always_calc_before_pay_cashback' ,'enable_dynamic_header' ,'enable_dynamic_footer' ,'display_earning_reports_schedule' ,'send_message' ,'exporting_on_queue' ,'show_sub_total_for_game_logs_report' ,'column_visibility_report' ,'enable_dynamic_registration' ,'enabled_player_referral_tab' ,'enable_player_center_mobile_live_chat' ,'enable_player_center_mobile_main_menu_live_chat' ,'enable_custom_script_mobile' ,'player_center_hide_time_in_remark' ,'mobile_show_vip_referralcode' ,'show_sports_game_columns_in_game_logs' ,'enable_registered_show_success_popup' ,'ignore_notification_permission' ,'show_new_games_on_top_bar' ,'enable_friend_referral_cashback' ,'hide_disabled_games_on_game_tree' ,'enable_isolated_vip_game_tree_view' ,'enable_player_report_2' ,'enable_agency_player_report_generator' ,'hide_empty_game_type_on_game_tree' ,'use_role_permission_management_v2','disable_captcha_before_sms_send'];


$config['deprecated_features'] = [
    'use_default_deposit_flow', // add by elvis
    'hidden_transaction_number_input_in_deposit', // add by elvis
    'hidden_note_input_in_deposit', // add by elvis
    'player_center_sidebar_tour', // add by elvis
    'confirm_manual_deposit_details', // add by vincent
    'enable_3dparty_payment_in_modal', // add by vincent
    'enable_manual_deposit_detail', // add by vincent
	'hide_deposit_approve_decline_button_on_timeout', // add by vincent
	'show_deposit_bank_details_first', // add by vincent
	'ignore_bind_transaction_with_player_promo_when_trigger_collection_account_promo', // add by vincent
	'add_close_status', // add by vincent
	'add_notes_for_player', // add by vincent
	'allow_duplicate_contact_number', // add by vincent
	'auto_fix_2_days_cashback', // add by vincent
	'create_single_withdraw_condition_even_applied_promo', //add by ivan
	'player_cancel_pending_withdraw', //add by ivan
	'set_status_of_playerpromo_when_cancel_withdraw_condition', // add by ivan
	'show_sub_total_for_withdrawal_list_report', //add by ivan
	'check_disable_cashback_by_promotion',//add by ivan
    'redirect_to_player_center_when_go_to_register_page_if_islogin', // add by elvis
    'disabled_manually_transfer_on_player_center', // add by andrew
    'show_sub_total_for_deposit_list_report', // add by andrew
    'show_deposit_bank_details', // add by andrew
    'enabled_preapplication_on_promotion', //add by andrew
    'dpulicate_bank_account_number_verify_status_active', // add by elvis
    'enable_mobile_3rdparty_deposit_close_1_btn_and_append_redirecturl', // add by andrew
    'show_tag_for_unavailable_deposit_accounts', // add by andrew
    'new_promo_manager', // add by andrew & elvis
    'enabled_subwallet_to_subwallet_transfer', // add by elvis
    'enable_currency_symbol_in_the_deposit', // add by elvis
    'hide_pending_3rd_party_deposit', // add by elvis
    'enable_cashback_weekly_period', // add by elvis
    'show_all_pending_deposit_on_top_bar', // add by elvis
    'notification_all_pending_deposit', // add by elvis
    'enable_subwallet_by_category', // add by elvis
    'disabled_adjust_player_dispatch_account_level', //add by noke
    'only_allow_atm_deposit_upload_file', //add by curtis
    'update_player_last_activity', //add by elvis
    'disable_agency_game_report_in_sbe', // added by yunfei, OGP-9805
    'disable_agency_player_report_in_sbe', // added by yunfei, OGP-9805
    'enable_redirect_to_setted_up_config_link', // add by elvis
    'enabled_wrong_password_blocked', // add by elvis
    'ignore_cancelled_withdraw_condition_when_deduct_betting_amount', // add by elvis
    'use_www_css_for_player_center', // add by elvis
    'use_www_js_for_player_center', // add by elvis
    'show_password_recovery_option_by_email', // add by curtis
    'show_password_recovery_option_by_security_question', // add by curtis
    'show_password_recovery_option_by_sms', // add by curtis
    'user_guide_link', // add by elvis
    'support_ticket_link', // add by elvis
    'enable_player_center_mobile_3rd_party_live_chat', // add by elvis
    'pending_deposit_permission', // add by elvis
    'enable_transfer_all_to_main_wallet', // add by elvis
    'enable_affiliate_player_report_generator', // add by bryson
    'force_set_player_to_default_language', // add by elvis
    'enabled_change_lang_tutorial', // add by bryson
    'skip_save_http_request_with_login_from_admin', // add by bryson
    'display_one_week_data_on_withdraw_condition', // added by elvis
    'mobile_allow_edit_IM', // add by bryson
    'disable_player_change_email_no_matter_email_has_already_been_verified_or_not', // add by bryson
    'disable_player_change_password', // added by elvis
    'enable_popup_login', // add by bryson
    'enable_popup_registration', // add by bryson
    'show_vip_group_on_first_login', //add by bryson
    'enable_set_block_status_after_register', // add by bryson
    'enable_first_logon_show_ad_popup', // add by bryson
    'display_profile_update_detail_validation_result', // added by elvis
    'hide_promo_code_on_deposit_page', // add by curtis
    'show_player_messages_tab', // add by jessie
    'disable_player_deposit_bank', // add by jouan
    'hide_bank_account_full_name_in_payment_account_detail_player_center', // add by jouan
    'force_setup_player_deposit_bank_when_if_it_is_empty', // add by jouan
    'hide_bank_branch_in_payment_account_detail_player_center', // add by jouan
    'enable_confirm_birthday_before_setting_up_withdrawal_bank_account', // add by jouan
    'allow_only_bank_account_limit', // add by jouan
    'player_bind_one_bank', // add by jouan
    'player_bankAccount_input_numbers_limit', // add by jouan
    'player_bind_one_address_each_cryptocurrency', // add by jouan
    'always_duplicate_player_any_bank_when_add', // add by jouan
    'player_can_edit_bank_account', // add by jouan
    'player_can_delete_bank_account', // add by jouan
    'player_bank_show_detail_form_validation_results', // add by jouan
    'disabled_send_email_contactus', // add by jouan
    'disabled_send_email_upon_aff_registration', // add by jouan
    'disabled_send_email_upon_change_withdrawal_password', // add by jouan
    'disabled_send_email_upon_player_registration', // add by jouan
    'disabled_send_email_upon_promotion', // add by jouan
    'send_email_after_verification', // add by jouan
    'send_email_promotion_template_after_verification', // add by jouan
    'enabled_active_affiliate_by_email', // add by jouan
    'show_time_interval_in_deposit_processing_list', // add by jouan
    'summary_report_2', // add by jouan
    'enable_player_report_2', // add by jouan
    'use_new_account_for_manually_withdraw', // add by jessie
    'send_message', // add by jouan
    'donot_show_registration_verify_email', // add by jouan
    'enable_trasfer_all_quick_transfer', // add by jouan
    'player_center_sidebar_transfer', // add by jouan
    'enabled_transfer_all_and_refresh_button_on_new_transfer_ui', // add by jouan
    'enable_default_logic_transaction_period',// add by kris
    'show_unsettle_game_logs' , // Added by rupert.chen.php.tw - OGP-14934
	'default_settled_status_on_player_deposit_list' , // Added by rupert.chen.php.tw - OGP-14934
    'declined_forever_promotion', //add by curtis
    'enable_default_logic_transaction_period', // add by kris
    'realign_the_dashboard_in_deposit_list_and_make_them_clickable', // add by kris
    'untick_time_out_deposit_request_if_load_deposit_list', // add by kris
    'untick_3rd_party_payment_if_load_deposit_list', // add by kris
    'untick_atm_cashier_if_load_deposit_list', // add by kris
	'untick_enabled_date_if_load_deposit_list', // add by kris
	'show_disabled_games_on_game_tree', // add by ping
	'bind_promorules_to_friend_referral', // OGP-17962
    'agency', // add by jouan
    'iovation_fraud_prevention', // add by jouan
    'enable_super_report', //add by gary
    'www_sidebar', // add by kris
    'www_quick_transfer_sidebar', // add by kris
    'www_deposit_sidebar', // add by kris
	'www_live_chat_sidebar', // add by kris
	'hide_retype_email_field_on_registration', // add by Min
    'show_deposit_3rdparty_on_top_bar', //add by kris
    'notification_thirdparty_settled_on_top_bar', //add by kris
    'notification_local_bank', //add by kris
    'notification_thirdparty', //add by kris
    'notification_withdraw', //add by kris
    'only_use_dropdown_list_for_notification', //add by kris
    'transaction_request_notification', //add by kris
    'notified_at', //add by ping
    'ignore_notification_permission', //add by kris
    'disabled_auto_create_game_account_on_registration', //add by kris
    'default_search_all_players', // add by kris
	'display_locked_status_column', //add by gary
	'enabled_display_change_withdrawal_password_message_note', // OGP-17218
	'enable_friend_referral_cashback', // OGP-17607
    'show_player_address_in_list', // add by jouan
    'show_id_card_number_in_list', // add by jouan
	'auto_pay_cashback_when_regenerate',  // add by ping
    'show_zip_code_in_list', // add by jouan
    'disable_action_buttons_in_player_list_table', // add by jouan
    'player_center_hide_time_in_remark', // moved to, SBE > System Settings > Player Center > Cashier Center
    'add_security_on_deposit_transaction',// add by kris
	'enabled_display_withdrawal_password_notification', // moved to, SBE > System Settings > Player Center > Cashier Center
    'separate_approve_decline_withdraw_pending_review_and_request_permission',// add by kris
    'eanble_display_mobile_user_icon',// add by kris
    'disable_account_name_letter_format', // add by gary
    'enabled_withdrawal_password', // moved to, SBE > System Settings > Player Center > Cashier Center by gary
    'enabled_forgot_withdrawal_password_use_livechat_to_reset', // moved to, SBE > System Settings > Player Center > Cashier Center by gary
    'disable_player_change_withdraw_password', // moved to, SBE > System Settings > Player Center > Cashier Center by gary
    'enabled_login_password_on_withdrawal', // add by gary
    'enable_manually_deposit_cool_down_time',//by kris OGP-17634
    'ole777_on_first_popup_after_register',//by kris OGP-18418
    'enabled_iovation_in_registration','enabled_iovation_in_promotion',// moved to, SBE > System Settings > Player Center > Player Center by bermar
    'enable_registered_triggerRegisterEvent_for_xinyan_api','enable_show_trigger_XinyanApi_validation_btn',//by kris OGP-26562
    'use_www_game_icon_for_player_center',// by kris OGP-26604
    'show_player_contact_on_agency',
    'parent_aff_code_on_register',
    'promorules.allowed_affiliates',
    'show_affiliate_list_on_search_player',
    'integrate_lottery_agent_to_admin',
    'close_livechat',
    'display_referral_code_in_player_details',
    'enable_upload_depostit_slip',
    'enabled_maintaining_mode',
    'enabled_whitelist_duplicate_record',
    'exporting_on_queue',
    'force_disable_all_promotion_dropdown_on_player_center',
    'hide_contact_on_player_center',
    'only_6_hours_game_records',
    'player_deposit_reference_number',
    'promorules.allowed_players',
    'try_disable_time_ranger_on_cashback',
    'auto_pay_cashback_when_regenerate',
    'display_exclude_player_tag',
    'registration_date_on_friend_referraL_setting',//by kris OGP-26604
];

//** Adding a function code to deprecate the permissions
$config['deprecated_permissions'] = [
	//added by frans.php.ph
	'settlement',
	'agency_setting',
	'agent_contact_info',
	'edit_agent_term',
	'edit_agent_info',
	'agent_earnings',
	'edit_agency_settings',
	'agency_player_deposit',
	'agency_player_withdraw',
	'send_message',
	'support_ticket',
	'duplicate_account_checker_setting',
	'vip_settings_group_add',
	'vip_settings_group_edit',
	'vip_settings_group_delete',
	'vip_settings_group_preview',
	'vip_settings_group_export',
	'vip_settings_level_increase_decrease',
	'vip_settings_level_edit',
	'vip_settings_level_export',
	'vip_rebate_rules_template',
	'vip_rebate_rules_template_add',
	'vip_rebate_rules_template_edit',
	'vip_rebate_rules_template_delete',
	'vip_rebate_rules_template_active',
	'vip_rebate_rules_template_export',
	'vip_rebate_values',
	'vip_rebate_values_export',
	'vip_request_list',
	'vip_request_list_export',
	'vip_request_list_approve',
	'vip_request_list_reject',
	'vip_settings_group_level_save',
	'vip_settings_group_save',
	'export_cashback_request',
	//added by frans.php.ph
	// -- start -- added by cholo.php.ph
	'upload_sbe_logo',
	'verified_phone_and_email_info',
	'replacement_transfer_order',
	'promo_cancellation_setting',
	'bannerPromotion',
	'promoplayer_list',
	'affiliate_player_report',
	'api_report',
	'payment_auto3rdparty_deposit_list',
	'payment_manual3rdparty_deposit_list',
	'cancel_member_withdaw_condition',
	'approve_declient_3rdparty_deposit',
	'agency_wl_report',
	'allow_to_do_settlement',
	'agency_player_report',
	'agency_game_report',
	'vip_settings',
	// -- end -- added by cholo.php.ph
	// -- start -- added by andrew.php.ph
	'transactions_report',
	'export_all_username',
	'export_batch_create',
	'force_change_affiliate_of_player',
	// -- end -- added by andrew.php.ph
	// -- start -- added by kris.php.tw
	'deposit_count_list',
	'update_deposit_attachment',
	'upload_deposit_slip',
	// -- end -- added by kris.php.tw
	'payment_settings',
    'message_history', // added by elvis.php.tw
    'game_setting', // added by elvis.php.tw
    'export_report', // added by elvis.php.tw
    'block_game_setting', // added by elvis.php.tw
    'cashback_daily', // added by elvis.php.tw
    'system_api_docs', // added by elvis.php.tw
    'show_kyc_status', // added by elvis.php.tw
    'show_risk_score', // added by elvis.php.tw
	'show_allowed_withdrawal_status', // added by elvis.php.tw
	'deposit_count_setting', // added by jessie.php.tw
	'summary_report', // added by joaun.php.tw
	'export_summary_report', // added by joaun.php.tw
	'compensation_setting', // added by joaun.php.tw
	'system_settings', // added by rupert.chen.php.tw - OGP-14823
	'generate_to_site_header', // added by ping.php.tw - OGP-17499 : deprecate "generate to site" button
	'generate_to_site_footer', // added by ping.php.tw - OGP-17499 : deprecate "generate to site" button
	'view_player_list_summary_info', // added by ping.php.tw - OGP-14393 : Enhancement of Conversion Rate Report performance
	'allow_search_player_phone_number_no_view', // added by ping.php.tw - OGP-14393 : Enhancement of Conversion Rate Report performance
	'allow_search_player_email_no_view', // added by ping.php.tw - OGP-14393 : Enhancement of Conversion Rate Report performance
	'allow_search_player_im_no_view', // added by ping.php.tw - OGP-14393 : Enhancement of Conversion Rate Report performance
	'marketing_setting', // added by min.php.tw - OGP-15605 : Deprecate permissions under Marketing Management
	'edit_game_type', // added by min.php.tw - OGP-15605 : Deprecate permissions under Marketing Management
	'export_duplicate_account_report', // added by jessie.php.tw - OGP-15604 : Duplicate Account Report Modification
	'player_communication_preference', // added by joaun.php.tw
	'approve_withdrawal_to_CS0',// added by kris.php.tw
	'approve_withdrawal_to_CS1',// added by kris.php.tw
	'approve_withdrawal_to_CS2',// added by kris.php.tw
	'approve_withdrawal_to_CS3',// added by kris.php.tw
	'approve_withdrawal_to_CS4',// added by kris.php.tw
	'approve_withdrawal_to_CS5',// added by kris.php.tw
	'approve_withdrawal_to_payProc',// added by kris.php.tw
	'withdraw_pending_review_list',// added by kris.php.tw
	'approve_decline_withdraw',// added by kris.php.tw
	'approve_decline_withdraw_pending_request',// added by kris.php.tw
	'approve_decline_withdraw_pending_review',// added by kris.php.tw
	'set_withdraw_status_to_paid',// added by kris.php.tw
	'minimum_withdraw_settings', // added by joaun.php.tw
	'delete_affiliate', // added by curtis.php.tw
	'allow_to_delete_declined_promotion',// added by kris.php.tw
	'live_chat', // added by gary.php.tw
	'player_live_chat_link', // added by gary.php.tw
    'previous_balances_checking_setting', // added by allen.php.ph
];

$config['deprecated_registeration_template'] = [
	'template_3' //added by min.php.tw - OGP-17474 : deprecate registeration template_3
];

$config['disable_show_deprecated_system_feature'] = false;
$config['disable_show_deprecated_permissions'] = false;
$config['show_last_login_date_notification'] = false;
$config['enable_system_feature_search'] = true;

// for notification [add to enabled_features features]:
//  * transaction_request_notification(all notificaition enabled)
//  * notification_promo (promo)
//  * notification_messages (messages)
//  * notification_local_bank (local_bank)
//  * notification_thirdparty (thirdparty)
//  * notification_withdraw (withdraw)

$config['live_chat'] = [
	'admin_login_url' => 'https://live.chatchat365.local/index.php/user/autologin',
	'admin_login_secret' => '',
	'encrypt_key1' => '',
	'encrypt_key2' => '',
	'api_key' => '',
	'api_secret' => '',
	'www_chat_options' => [
		'widget_height' => 340, 'widget_width' => 300,
		'popup_height' => 520, 'popup_width' => 500,
		'theme' => 1, 'department' => 1, "survey_id" => null,
		'lang' => 'chn',
		'onlylink' => false,
		'load_livechat_link_selector' => '.load_livechat',
		'popup_livechat_link_selector' => '.popup_livechat',
		'pop_embed_livechat_link_selector' => '.pop_embed_livechat',
	],
	'www_chat_host' => 'live.chatchat365.local',
	'www_chat_https_enabled' => false,
	'attach_link_on_player_center' => false,
	'show_on_player_center' => false,
	'external_url'=>'',
	'external_onclick'=>'',
];

$config['hide_player_info_on_aff'] = false;
$config['show_version_on_player'] = true;

$config['display_closed_gameinfo_in_sbe_player_info']= false;

/**
 * OGP-22569 [Affiliate] Adding a List of Dedicated and Additional Domains under Domain List
 * /affiliate_management/viewDomain
 * $config['enable_dedicated_additional_domain_list'] = false;
 */
$config['enable_dedicated_additional_domain_list'] = false;

$config['editable_tracking_code_on_aff'] = false;
$config['enable_tracking_all_pages_by_aff_code'] = false;
$config['current_tracking_promotion_dir'] = '/promotion.html';

$config['logger_settings'] = [
	'handlers' => array('json'), // 'loggly' valid handlers are file | new_relic | hipchat
	'channel' => $config['RUNTIME_ENVIRONMENT'], // channel name which appears on each line of log
	'threshold' => '5', //'OFF' => '0', 'ERROR' => '1', 'INFO' => '2', 'DEBUG' => '3', 'VERBOSE' => '4', 'ALL' => '5',

	'json_level' => 'debug',
    'json_file' => BASEPATH.'/../application/logs/log-json.log',
    'enabled_json_file' => false,

    'file_logfile' => BASEPATH.'/../application/logs/log-ci.log',
    'max_files' => 10,

    'loggly_logfile' => BASEPATH . '/../application/logs/loggly.log', //'/root/wrong.log', //
    'loggly_level' => 'error',
    'loggly_token' => '',

	// exclusion list for pesky messages which you may wish to temporarily suppress with strpos() match
	'exclusion_list' => array(),
];

$config['default_logger_json_file']=null;

$config['enabled_logger_json_file']=true;
$config['enabled_logger_file_channel']=true;
$config['enabled_debug_redis_logger']=false;

$config['logentries_settings'] = [
	'token' => null,
	'persistent' => true,
	'ssl' => true,
	'severity' => LOG_INFO,
	// 'HOST_ID'=>$config['RUNTIME_ENVIRONMENT'],

	'DATAHUB_ENABLED' => false,
	'DATAHUB_IP_ADDRESS' => null,
	'DATAHUB_PORT' => null,
	'HOST_NAME' => '',
	'HOST_NAME_ENABLED' => true,
	'ADD_LOCAL_TIMESTAMP' => true,

];

$config['log_time_elapsed'] = 'debug';

$config['always_resync_game_logs_for_cashback'] = false;

$config['default_transfer_subwallet_id'] = null;

$config['batch_sync_all_players'] = false;

$config['verbose_log'] = true;

//sould check max limit for real and bonus
$config['big_wallet_rules'] = [
	'enabled_cache_bin_wallet' => false,

	'big_wallet_manually_order' => ['real', 'win_real', 'real_for_bonus', 'bonus', 'win_bonus'],

	'big_wallet_win_order' => ['win_bonus', 'win_real', 'real'], //hide rule: should check >0 before add to win wallet
	'big_wallet_loss_order' => ['real', 'real_for_bonus', 'win_real', 'win_bonus', 'bonus'],
	'big_wallet_transfer_to_main_order' => ['real', 'win_real'], //'real_for_bonus', 'win_bonus', 'bonus'],
	'big_wallet_transfer_from_main_order' => ['real', 'win_real', 'real_for_bonus', 'win_bonus', 'bonus'],

	'dec_frozen_order' => ['real', 'real_for_bonus'],
	'inc_frozen_order' => ['real', 'real_for_bonus'],

	'cashback_wallet_type' => 'bonus',

	'sub_to_main_only_allow' => ['real', 'win_real'],
	'withdraw_main_only_allow' => ['real'],

	'force_real' => true,

];


$config['promotion_rules'] = [
	'enabled_notallow_transaction_type' => true,
	'from_date_order' => ['player_reg_date', 'last_withdraw'],
	'enabled_show_withdraw_condition_detail_betting' => true,
//	'enabled_auto_check_withdraw_condition' => true,
	'enabled_auto_clear_withdraw_condition' => false,
	'enabled_request_without_check' => false,
	'release_on_admin_approve' => true,
	//to control feature of promo rules
	'finish_last_withdraw_condition_before_apply_promotion' => false,
	'allow_manually_promotion' => false,
	// 'allow_auto_trigger_sub_wallet' => false,
	'always_limit_one_promotion' => false,
	'enabled_auto_finish_playerpromo' => true,

	'show_extra_message_on_transfer_promotion' => false,

	'add_withdraw_condition_as_bonus_condition' => true,

	// 'only_real_when_refresh'=>false,

	'only_real_when_release_bonus' => false,

	'allow_decline_on_approved_and_without_release' => false,

	'disable_pre_application_on_release_bonus_first' => false,

	'start_betting_amount_from_player_apply' => false,

	'search_transaction_order' => 'desc',

	// 'enabled_delete_declined_promo'=>true,
	// 'enabled_move_all_to_real'=>true,
];

$config['hide_player_center_promo_date_applied'] = false;
$config['hide_player_center_promo_type'] = false;

$config['hidden_promorule_allow_zero_bonus_checkbox'] = true;
$config['hidden_promorule_trigger_on_transfer_to_sub_wallet'] = false;
$config['hidden_promorule_transfer_records_options'] = false;
$config['hidden_promorule_wallet_selection_of_bonus_release'] = false;
$config['enabled_promo_previewlink'] = false;
$config['admin_support_live_chat'] = [
	'encrypt_key1' => '7feb4025e4025bfa93128ce0360a5d6f025bfa93128ce0362',
	'encrypt_key2' => '8feb4ce0360a5d6f025b025e4025bfa93128fa93128ce0360',
	'www_chat_options' => [
		'widget_height' => 340, 'widget_width' => 300,
		'popup_height' => 520, 'popup_width' => 500,
		'theme' => 1, 'department' => 3, "survey_id" => null,
		'lang' => 'chn',
		'onlylink' => false,
		'load_livechat_link_selector' => '.load_livechat',
		'popup_livechat_link_selector' => '.popup_livechat',
	],
	'www_chat_host' => 'live.letschatchat.com',
	'www_chat_https_enabled' => false,
];

$config['use_ci_db_to_lock'] = true;

$config['max_size_duplicate_accounts'] = 30;

$config['silverpop_options'] = array(
	'apiHost' => null,
	'username' => null,
	'password' => null,
);

$config['big_wallet_transfer_to_main_real'] = true;

$config['aff_banner_url'] = null;

$config['pub_api_key'] = '';

$config['operator_unique_name'] = 'homestead';
$config['service_api_login'] = 'service/api/login_by_token';

$config['minus_player_deposit_fee_from_deposit'] = true;
$config['minus_transaction_fee_from_deposit'] = false;
$config['minus_transaction_fee_from_withdraw'] = false;
#OGP-18411
$config['enable_withdrawl_fee_from_player'] = false;
$config['withdrawal_fee_levels'] = false;
$config['withdrawal_fee_rates'] = false;

$config['show_agency_rev_share_etc'] = false;
$config['email_registration_subject'] = null;
$config['hide_commission_settings'] = true;

$config['record_activity_timeout_seconds'] = 10;

$config['close_db_on_each_request'] = false;
$config['agency_settlement_time'] = '12:00:00';

$config['player_form_registration'] = [
    'terms' => TRUE,
    'newsletter_subscription' => TRUE,
    /* communication_preferences */
    /* See $config['communication_preferences'] */
    'communication_preferences_email' => TRUE,
    'communication_preferences_sms' => TRUE,
    'communication_preferences_phone_call' => TRUE,
    'communication_preferences_post' => TRUE
    /* ========================= */
];
$config['enable_restrict_username_more_options'] = false;
$config['player_validator'] = [
	'username' => ['min' => 6
					, 'max' => 12
                    // https://regex101.com/r/ZKlrUM/1
					, 'default_regex' => '/^[a-z0-9]+$/'
					, 'restrict_regex' => '/^(?=.*[a-z])(?=.*[0-9])[a-z0-9]+$/'
					, 'restrict_regex_number_only' => '/^(?=.*[0-9])[0-9]+$/'
					, 'restrict_regex_letters_only' => '/^(?=.*[a-zA-Z])[a-zA-Z]+$/'
					, 'restrict_regex_number_letters_only' => '/^(?=.*[a-zA-Z0-9])[a-zA-Z0-9]+$/'
					, 'default_regex_js' => '^[a-z0-9]+$'
					, 'restrict_regex_js' => '^(?=.*[a-z])(?=.*[0-9])[a-z0-9]+$'
					, 'restrict_regex_js_number_only' => '^(?=.*[0-9])[0-9]+$'
					, 'restrict_regex_js_letters_only' => '^(?=.*[a-zA-Z])[a-zA-Z]+$'
					, 'restrict_regex_js_number_letters_only' => '^(?=.*[a-zA-Z0-9])[a-zA-Z0-9]+$'
				],
	'password' => ['min' => 6, 'max' => 20, 'default_regex' =>'/^(?=.*[A-Za-z])(?=.*[0-9])[A-Za-z0-9]+$/', 'regexType' => FIELD_REGEX_TYPE_REQUIRE_ALPHA_AND_NUMERIC],
	'security_answer' => ['min' => 1, 'max' => 50],
	'first_name' => ['min' => 2, 'max' => 20],
	'last_name' => ['max' => 20],
	'contact_number' => ['min' => 8, 'max' => 12], // extra_length : min == max
	'cpf_number' => ['min' => 11, 'max' => 11], // extra_length : min == max
];

$config['is_contact_number_begin_on_nonzero'] = false; // OGP-32860

$config['new_api_verify_names_invalid_chars'] = '~!@#$%^&*():<>{}();+-_0123456789[]/，、！：；？（）…｛｝‧．。\\.|\'"=?,`';

$config['default_min_size_username'] = $config['player_validator']['username']['min'];
$config['default_max_size_username'] = $config['player_validator']['username']['max'];
$config['default_regex_username'] = $config['player_validator']['username']['default_regex'];
$config['default_regex_username_js'] = $config['player_validator']['username']['default_regex_js'];
$config['default_min_size_password'] = $config['player_validator']['password']['min'];
$config['default_max_size_password'] = $config['player_validator']['password']['max'];
$config['default_regex_password'] = $config['player_validator']['password']['default_regex'];
$config['default_min_size_security_answer'] = $config['player_validator']['security_answer']['min'];
$config['default_max_size_security_answer'] = $config['player_validator']['security_answer']['max'];

$config['default_min_size_first_name'] = $config['player_validator']['first_name']['min'];
$config['default_max_size_first_name'] = $config['player_validator']['first_name']['max'];
$config['default_max_size_last_name'] = $config['player_validator']['last_name']['max'];

$config['restrict_regex_username'] = $config['player_validator']['username']['restrict_regex'];
$config['restrict_regex_username_js'] = $config['player_validator']['username']['restrict_regex_js'];

$config['player_myfavorite_limit_count'] = 12;

$config['admin_support_ticket'] = 'https://www.tot.bet';

//seconds
$config['timeout_refresh_queue'] = 10;

$config['update_bet_limit_on_batch_create'] = false;

$config['print_log_to_console'] = false;

$config['feedback_api_key'] = 'ed50cbbd-0c04-4e2a-81f6-2b81980fc5ee';

$config['player_session_timeout_seconds'] = 5 * 60;
$config['check_session_timeout_ms'] = 30000;

$config['default_agency_login_language'] = 'chinese';

$config['app_lock_timeout'] = 180;
//lock failed timeout=lock_retry_delay * retryCount(20)
$config['lock_retry_delay'] = 2000;
$config['use_lock_server_to_lock'] = true;
$config['lock_servers'] = [
	['redisserver', 6379, 0.01],
	// ['127.0.0.1', 6389, 0.01],
	// ['127.0.0.1', 6399, 0.01],
];

$config['default_group_id_on_registration'] = null;

$config['disable_cashback_on_register'] = false;
$config['disable_promotion_on_register'] = false;

$config['debug_sync_balance_in_game_logs_include'] = [];

$config['og_load_testing'] = false;
$config['enabled_registration_captcha'] = true;

$config['ip_headers'] = null;

//default_player_language always uppercase the first letter
$config['default_player_language'] = 'Chinese';

$config['domain_prefer_language'] = NULL;

$config['show_terms_on_player_register'] = false;
$config['popup_deposit_after_login'] = false;

$config['main_prefix_of_player'] = ''; //will be added to username, not just show

$config['default_admin_login_language'] = 'english';

$config['default_datatable_page_length'] = 25;

$config['common_block_page_url'] = '';

$config['auto_generate_domain'] = false;
$config['auto_domain_pattern'] = ''; //like {AFFDOMAIN}.xxx.com
$config['auto_domain_start'] = 100;
$config['bank_address_disabled'] = true;

$config['registration_hint'] = '';

$config['csrf_header_token_name'] = 'XCSRFTOKEN';

$config['coutry_rules_mode'] = 'allow_all'; //allow_all, deny_all
$config['blocked_page_url'] = '/block-page.html';
/**
 * @link https://www.ipip.net/ip.html Country Name Ref
 * @example
 * <code>
 * $config['blocked_page_url_with_locale'] = [
 *     'Taiwan' => 'https://www.og.local/',
 *     'Philippines' => 'https://www.og.local/'
 * ];
 * </code>
 */
$config['blocked_page_url_with_locale'] = [];

$config['www_white_ip_list'] = ['127.0.0.1'];
$config['www_block_ip_list'] = [];

$config['set_success_when_transfer_timeout'] = true;

$config['hide_deposit_lower_limit'] = false;
$config['hide_deposit_upper_limit'] = false;
$config['enabled_sync_in_balance'] = false;

$config['ignore_undefined_php'] = false;
$config['ignore_unexpect_warning_php']=false;
$config['ignore_count_warning']=false;
$config['ignore_php_warning_reference_variable_on_param']=false;
$config['ignore_all_notice_error']=false;
$config['disabled_all_php_page_error']=false;

$config['hide_confirm_email'] = true; // hide confirm email on registration

//for xcbet profile info form
$config['disabled_username_fld'] = true;
$config['disabled_currency_fld'] = true;
$config['disabled_invitationCode_fld'] = true;
$config['disabled_language_fld'] = true;
$config['disabled_contact_number_fld'] = true;
$config['disabled_email_fld'] = true;
$config['disabled_selection_im_account_fld'] = true;
$config['disabled_address_fld'] = true;
$config['disabled_city_fld'] = true;
$config['disabled_resident_country_fld'] = true;
$config['disabled_country_fld'] = true;
$config['disabled_citizenship_fld'] = true;
$config['disabled_birthdate_fld'] = true;
$config['disabled_birthplace_fld'] = true;

$config['enabled_promo_pagination'] = true;

$config['blocked_ip_for_aff'] = [];

$config['aff_timeout_second_password'] = 1800;
$config['aff_commission_settings'] = array(
	'baseIncomeConfig' => 1,
	'minimumDeposit' => 0,
	'admin_fee' => 0,
	'transaction_fee' => 0,
	'bonus_fee' => 0,
	'cashback_fee' => 100,
	'minimumPayAmount' => 0,
	'autoTransferToWallet' => 'none',
	'paymentSchedule' => 0,
	'manual_open' => true,
	'sub_link' => true,
	'sub_levels' => array_fill(0, 10, 0),
	'tiers' => array(
		array(
			'active_players' => 0,
			'net_revenue' => 0,
			'commission_percentage' => 0,
		),
	),
);

$config['iovation_api'] = [];
$config['gbg_api_url'] = [
	'wsdl' => 'https://pilot.id3global.com/ID3gWS/ID3global.svc?wsdl',
	'wss_ns' => 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd'];

$config['datatable_error_mode'] = 'throw';
$config['fav_icon'] = "/favicon.ico"; // sample replace fav icon on player and admin
$config['site_domain'] = "default"; // sample replace fav icon on player and admin
// $config['contact_number_show_withdrawal'] = true;

$config['mail_smtp_hostname'] = 'localhost.localdomain';

$config['demo_admin'] = 'superadmin';
$config['demo_player'] = 'demouser';

$config['demo_account_send_mail_address'] = 'marketing@smartbackend.com';

//for isoftbet freespin credentials
$config['isoftbet_api_url'] = 'https://stage-rms-lux.isoftbet.com/api/irs/freeround/management/';
$config['isoftbet_api_licensee'] = '169';
$config['isoftbet_api_secret_key'] = 'nYqJNfHlsCnUQicysfvBtYuRmOAOM87h';

# Tele api setting
$config['tele_api_setting'] = [
	'live_url', 'live_key'
];
$config['default_tele_id'] = '';
$config['call_tele_cooldown'] = 10; # defines number of seconds allowed in between two Tele Marketing API calls

$config['password_recovery_cooldown'] = 10; # defines number of seconds allowed in between two password recovery SMS / Emails

$config['tracking_code_priority_levels'] = array('trackingCodeInRef', 'trackingCodeInDomain', 'trackingCodeInSession');
$config['ANNOUCEMENT_STYLE'] = <<<EOF

<style>
    body {
        margin: 0px;
        height: 38px;
    }
    marquee {
        font: 15px "\5FAE\8F6F\96C5\9ED1", "微软雅黑", "Microsoft YaHei";
        color: #fff;

    }
</style>

EOF;

$config['ANNOUCEMENT_STYLE_MOBILE'] = <<<EOF

<style>
/*for mobile*/
</style>

EOF;

$config['transaction_type_for_disable_cashback'] = [];

$config['agency_player_rolling_settings'] = [
	'min_rolling_comm' => 0,
	'max_rolling_comm' => 3,
];

$config['agency_max_start_rolling'] = '-1 month';

$config['agency_tracking_link_bonus_rate_list'] = [
    ['start' => 'MAX_BONUS_RATE', 'end' => 1800, 'step' => -2],
    // ['start' => 1930, 'end' => 1800, 'step' => -10],
];

$config['redirect_security_when_contactnumber_unverified'] = false;
$config['redirect_input_verification_code_when_send_verification'] = false;

$config['set_language_by_subdomain'] = false;

$config['player_center_stat_server'] = '';
$config['player_center_stat_site_id'] = null;
$config['player_center_stat_script'] = <<<EOD
  var _paq = _paq || [];
  var _username='{player_center_stat_user_id}';
  /* tracker methods like "setCustomDimension" should be called before "trackPageView" */
  _paq.push(['trackPageView']);
  _paq.push(['enableLinkTracking']);
  (function() {
    var u="//{player_center_stat_server}/";
    _paq.push(['setTrackerUrl', u+'piwik.php']);
    _paq.push(['setSiteId', '{player_center_stat_site_id}']);
    if(_username!=''){
		_paq.push(['setUserId', _username]);
    }
    var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
    g.type='text/javascript'; g.async=true; g.defer=true; g.src=u+'piwik.js'; s.parentNode.insertBefore(g,s);
  })();
EOD;

$config['admin_stat_site_id'] = '4';

$config['hide_select_language'] = false;
$config['real_name_reminder'] = false;
// $config['player_deposit_reference_number'] = false;
$config['allow_multiplayer_to_have_existing_bank_account'] = false;
$config['european_address_format'] = false;
$config['debug_version_info'] = false;
$config['hide_bank_address_branch'] = true;

$config['deposit_list_default_page_size'] = 25;
$config['default_datatable_lengthMenu'] = [10, 25, 50, 100];
$config['default_player_report_order'] = 0;

$config['enalbed_processed_time_in_deposit_list_condition'] = false;

$config['enable_col_application_time_shopping_request_list'] = false; //OGP-25572

$config['balance_transactions_columnDefs'] = [
	"not_visible_balance_transactions_report" => [],
	"className_text-balance_transactions_report" => [4, 5, 6], // Amount, Before Balance, After Balance
	"not_visible_player_information" => [],
	"className_text-right_player_information" => [4, 5, 6, 8] // Amount, Before Balance, After Balance, Request ID
];
$config['transactions_columnDefs'] = [

	"not_visible_transactions_report" => [10, 11, 13, 15], // Promo Title, Promo Rule, Changed Balance, Request ID
	"not_visible_player_information" => [9, 10, 11, 12, 13], // Promo Title, Promo Rule, Promo Request ID, Changed Balance, Flag
	"className_text-transactions_report" => [4, 5, 6], // Transaction Amount, Before Balance, After Balance
	"className_text-right_player_information" => [4, 5, 6, 9] // Amount, Before Balance, After Balance, Request ID
];
$config['mobile_random_username_size'] = 5;
$config['mobile_fake_email'] = '@fake-email.com';

$config['use_og_config_to_setup'] = false;
$config['player_upload_folder'] = "player";

$config['default_promo_cms_banner_url'] = 'promo_cms/default_promo_cms_1.jpg';
$config['use_old_affiliate_commission_formula'] = false;

$config['fixed_banner_player_url'] = '';

$config['external_login'] = false;
$config['external_login_url'] = null;
$config['external_login_username_field'] = 'loginName';
$config['external_login_password_field'] = 'pwd';
$config['external_login_captcha_field'] = 'captcha';
$config['external_proxy'] = null;
$config['maintaining_page_uri'] = '/maintain';
$config['class_pc_version_www_a_link'] = "._pc_version_www_a_link";
$config['class_mobile_version_m_a_link'] = "._mobile_version_m_a_link";
$config['class_player_center_a_link'] = "._player_center_a_link";
$config['use_new_vip_upgrade_feature'] = false;
$config['recently_played_item_display_limit'] = 12;
$config['use_new_player_center_mobile_version'] = false;
$config['legal_age'] = 18;
$config['current_php_timezone']='Asia/Hong_Kong';
$config['show_message_list_title_in_mobile'] = false;
$config['force_default_timezone_option'] = false;

$config['initial_player_center_features'] = array(
	'donot_show_registration_verify_email',
	'enabled_player_center_preloader',
	'enabled_vipsetting_birthday_bonus',
	'enable_dynamic_registration',
);

$config['initial_system_features'] = array(
	'notify_affiliate_withdraw',
	'promorules.allowed_affiliates',
	'promorules.allowed_players',
	'player_list_on_affiliate',
	'show_transactions_history_on_affiliate',
	'show_admin_support_live_chat',
	// 'transaction_request_notification',
	'affiliate_additional_domain',
	'individual_affiliate_term',
	'affiliate_source_code',
	'show_unsettle_game_logs',
	'auto_refresh_balance_on_cashier',
	'generate_player_token_login',
	'export_excel_on_queue',
	'deposit_withdraw_transfer_list_on_player_info',
	'sync_api_password_on_update',
	'show_bet_detail_on_game_logs',
	'popup_window_on_player_center_for_mobile',
	'affiliate_player_report',
	'affiliate_game_history',
	'affiliate_credit_transactions',
	'notification_promo',
	'notification_messages',
	// 'notification_local_bank',
	// 'notification_thirdparty',
	// 'notification_withdraw',
	'affiliate_monthly_earnings',
	'enable_cashback_after_withdrawal_deposit',
	'default_search_all_players',
	'enabled_refresh_message_on_player',
	'show_cashback_and_bonus_on_aff_player_report',
	'force_disable_all_promotion_dropdown_on_player_center',
	'display_referral_code_in_player_details',
	'disabled_auto_create_game_account_on_registration',
	'show_total_balance_without_pending_withdraw_request',
	'enabled_single_wallet_switch',
	'create_ag_demo',
	'create_agin_demo',
	'enabled_check_frondend_block_status',
	'enabled_switch_to_mobile_on_www',
	'show_deposit_bank_details_first',
	'summary_report_2',
	'show_deposit_bank_details',
	'hide_retype_email_field_on_registration',
	'kickout_game_when_kickout_player',
	'create_single_withdraw_condition_even_applied_promo',
	'bind_promorules_to_friend_referral',
	'match_wild_char_on_affiliate_domain',
	//'show_upload_documents',
	'enabled_change_lang_tutorial',
	'show_pending_deposit',
	'show_declined_deposit',
	'set_status_of_playerpromo_when_cancel_withdraw_condition',
	'enable_dynamic_header',
	'enabled_edit_affiliate_bank_account',
	'enable_dynamic_footer',
	//'show_pep_status',
	'display_earning_reports_schedule',
	'exporting_on_queue',
	'add_notes_for_player',
	'show_sub_total_for_game_logs_report',
    'enabled_auto_clear_withdraw_condition',
    'show_promotion_view_all'
);

$config['new_player_center_default_template'] = "stable_center2";

$config['registration_fields_default_order'] = [
    'email',
    'dialing_code',
    'contactNumber',
    'sms_verification_code',
    'imAccount',
    'imAccount2',
    'imAccount3',
    'imAccount4',
    'firstName',
    'lastName',
    'gender',
    'birthdate',
    'birthplace',
    'citizenship',
    'language',
    'zipcode',
    'residentCountry',
    'region',
    'city',
    'address',
    'address2',
    'secretQuestion',
    'invitationCode',
    'affiliateCode',
    'agent_tracking_code',
    'withdrawPassword',
    'id_card_number',
    'player_preference',
    'player_preference_email',
    'player_preference_sms',
    'player_preference_phone_call',
    'player_preference_post',
    'terms',
];

$config['excluded_in_registration_settings'] = [
    'id_card_type', 'bankName', 'bankAccountNumber', 'bankAccountName', 'newsletter_subscription','pix_number',
	'issuingLocation', 'issuanceDate', 'middleName', 'maternalName', 'isPEP', 'acceptCommunications','secretQuestion',
	'secretAnswer', 'imAccount5', 'player_preference_imaccount', 'player_preference_imaccount2', 'player_preference_imaccount3',
	'player_preference_imaccount4', 'player_preference_imaccount5', 'player_preference_time_preference'
];

$config['excluded_in_account_info_settings'] = [
    'id_card_type', 'bankName', 'bankAccountNumber', 'bankAccountName', 'newsletter_subscription',
    'sms_verification_code', 'terms', 'agent_tracking_code', 'invitationCode', 'affiliateCode',
    'secretQuestion', 'residentCountry', 'id_card_number', 'withdrawPassword','pix_number',
	'issuingLocation', 'issuanceDate', 'middleName', 'maternalName', 'isPEP', 'acceptCommunications',
	'imAccount5', 'player_preference_imaccount', 'player_preference_imaccount2', 'player_preference_imaccount3',
	'player_preference_imaccount4', 'player_preference_imaccount5', 'player_preference_time_preference'
];

$config['excluded_in_account_info_settings_sbe'] = [
	'storeCode','sourceIncome', 'natureWork', 'middleName', 'maternalName', 'issuingLocation',
    'issuanceDate', 'expiryDate', 'isPEP', 'acceptCommunications', 'isInterdicted', 'isInjunction'
];

$config['excluded_in_games_report_search'] = [
    'storeCode',
];

$config['only_edited_once_in_player'] = [];
$config['options_in_registration_fields'] = [];
$config['enable_default_country_when_post_dialing_code'] = true;

$config['account_imformation_imaccount_sort'] = ['IMACCOUNT','IMACCOUNT2','IMACCOUNT3','IMACCOUNT4','IMACCOUNT5'];

# Defines the page used when game is under maintenance. Disable respective game API and game will redirect to this URL.
$config['maintenance_url'] = array(
    'bbin' => '/others/maintain.html',
    '*' => '/others/maintain2.html',
);

$config['get_new_msg_interval'] = 60000;

//35.187.153.12
$config['redis_server'] = null;// ['server'=>'10.140.0.9', 'port'=>6379, 'password'=>null, 'timeout'=>1, 'retry_timeout'=>10];
$config['fake_hostname']='';

$config['debug_print_json_sql']=false;
$config['initial_collection_account_setup'] = array(
	'flag' => 1,
	'payment_account_name' => 'default collection',
	'payment_account_number' => 123456789,
	'payment_branch_name' => '',
	'max_deposit_daily' => 10000,
	'min_deposit_trans' => 100,
	'max_deposit_trans' => 1000,
);

$config['user_terms_url'] = '';
$config['privacy_policy_url'] = '';

$config['blocked_ip_for_player']=[];

$config['promotion_mock']=[
	'get_game_result_amount'=>null,
	'current_player_total_balance'=>null,
    'times_released_bonus_on_this_promo_today'=>null,
    'sum_deposit_amount'=>null,
    'sum_withdrawal_amount'=>null,
    'get_game_betting_amount'=>null,
    'get_game_betting_count'=>null,
    'count_deposit_by_date'=>null,
	'count_withdraw_by_date'=>null,
	'get_available_deposit_amount'=>null,

	'get_date_type_now'=>null,
	'get_date_type_last_week_start'=>null,
	'get_date_type_last_week_end'=>null,
	'get_date_type_yesterday_start'=>null,
	'get_date_type_yesterday_end'=>null,
	'get_date_type_today_start'=>null,
	'get_date_type_today_end'=>null,
	'get_date_type_last_release_bonus_time'=>null,
	'get_max_date_type'=>null,
	'get_min_date_type'=>null,
	'checkPlayerRegisteredDate'=>null,
	'is_player_filled_first_name'=>null,
	'is_player_filled_last_name'=>null,
	'is_player_at_least_one_withdrawal_bank'=>null,

	'fixed_time_deposit_transaction_id'=>null,
	'fixed_time_deposit_amount'=>null,

	'every_time_deposit_transaction_id'=>null,
	'every_time_deposit_amount'=>null,

	'not_first_time_deposit_transaction_id'=>null,
	'not_first_time_deposit_amount'=>null,

	'already_released_promo_rule'=>null,
	'exists_double_realname'=>null,
	'exists_double_ip'=>null,
	'is_player_verified_mobile'=>null,
	'sum_bonus_amount'=>null,
	'sum_all_bonus_amount'=>null,
	'sum_bonus_amount_today'=>null,
	'is_available_vip_level'=>null,
    'get_close_loss_include_game_log_by_date'=>null,
    'count_approved_promo_by_date'=>null,
];

$config['max_ogg_file_size']=2*1024*1024;

$config['target_banktypes_order']=[];

$config['hide_transfer_tab_in_player_center'] = false;

$config['mobile_main_menu_col']=4;

$config['mobile_menu_bar'] = [
    [
        'key' => 'under_home',
        'sort' => 1,
        'enable' => true,
        'site' => 'm',
        'url' => '',
        'img' => '/includes/images/under_home.png',
        'lang_code' => 'header.home'
    ],
    [
        'key' => 'under_gift',
        'sort' => 2,
        'enable' => true,
        'site' => 'm',
        'url' => '/promotions.html',
        'img' => '/includes/images/under_gift.png',
        'lang_code' => 'lang.promo'
    ],
    [
        'key' => 'under_chat',
        'sort' => 3,
        'enable' => true,
        'site' => 'player',
        'url' => 'pub/live_chat_link',
        'img' => '/includes/images/under_chat.png',
        'lang_code' => 'customer.service.mobile',
    ],
    [
        'key' => 'under_game',
        'sort' => 4,
        'enable' => true,
        'site' => 'm',
        'url' => '/slot.html',
        'img' => '/includes/images/under_games.png',
        'lang_code' => 'lang.games',
    ],
    [
        'key' => 'under_bank',
        'sort' => 5,
        'enable' => true,
        'site' => 'player',
        'url' => 'player_center2/deposit',
        'img' => '/includes/images/under_bank.png',
        'lang_code' => 'lang.deposit',
    ],
    [
        'key' => 'under_me',
        'sort' => 6,
        'enable' => true,
        'site' => 'player',
        'url' => 'player_center/menu',
        'img' => '/includes/images/under_me.png',
        'lang_code' => 'Player Center',
    ],
];

$config['custom_lang'] = FALSE;

$config['donot_auto_redirect_to_https_list']=['.+\\.og\\.local'];
$config['auto_redirect_to_https_list']=[];

$config['log_server_link']="http://logs.t1t.link:5601/app/kibana#/dashboard/575ef2b0-5667-11e7-8020-e143669329ac?_a=(query:(query_string:(analyze_wildcard:!t,query:'extra.tags.request_id:{{request_id}}')))";

$config['agent_max_credit_limit']='10000000000001';

//register/www/player_center/promotion/<url>
$config['redirect_agent_tracking_link_to']='register';

//affiliate_only/agent_only/both
$config['player_registration_tracking_code_mode']='affiliate_only';

$config['player_center_custom_header']='';
$config['mobile_player_center_custom_header']='';

$config['player_center_custom_footer']='';
$config['mobile_player_center_custom_footer']='';
$config['mobile_player_center_custom_dynamic_footer']='';

$config['affiliate_custom_header']='';
$config['affiliate_custom_footer']='';

$config['affiliate_cron_schedule'] = '01:07:00';

$config['mobile_lgoin_redirect_url'] = '/player_center/menu';

$config['enable_timezone_query_method_list'] = [];
// $config['enable_timezone_query_method_list'][] = 'Payment_management::deposit_list';

$config['enable_freeze_top_method_list'] = [];
// $config['enable_freeze_top_method_list'][] = 'Report_management::viewPlayerReport2';

$config['enable_go_1st_page_another_search_method_list'] = [];

//deposit/withdrawal/transfer
/**
 * @deprecated Change to use promotion setting. by Elvis_Chen at 2018/02/21
 */
$config['deposit_promotion_disabled_transaction_type_list']=['deposit'];

$config['deposit_secure_verify_show_payment_account_counts'] = 1; // FALSE: unlimit

$config['announcement_scrollamount']=5;
$config['announcement_mobile_scrollamount']=5;

$config['enabled_announcement_detail']=false;


$config['locked_ibc_game_url']='';

$config['mobile_redirection_url_prefix']='m';
$config['session_timeout'] = '-1 hours';
$config['default_auto_logout_sess_expiration'] = 30; //minute
$config['sess_expiration_use_custom_setting'] = ['ci_admin_sessions'];


$config['agency_view_template']='agency';
$config['locked_agency_template'] = NULL;

$config['agency_registration_fields']=[
	'username'=>['visible'=>true, 'required'=>true],
	'password'=>['visible'=>true, 'required'=>true],
	'email'=>['visible'=>true, 'required'=>true],
	'firstname'=>['visible'=>true, 'required'=>false],
	'lastname'=>['visible'=>true, 'required'=>false],
	'gender'=>['visible'=>false, 'required'=>false],
	'mobile'=>['visible'=>true, 'required'=>false],
	'im1'=>['visible'=>false, 'required'=>false],
	'im2'=>['visible'=>false, 'required'=>false],
	'note'=>['visible'=>true, 'required'=>false],
	'language'=>['visible'=>true, 'required'=>false],
];

$config['cust_non_lang_translation'] = [
    'Instant Message 1' => '',
    'Instant Message 2' => '',
    'Instant Message 3' => '',
    'Instant Message 4' => '',
	'Instant Message 5' => '',
];

//this is for skip isPlayerCompleteProfile field
$config['skip_fields_in_registration_fields'] = ['affiliateCode','withdrawPassword','bankName','bankAccountNumber','bankAccountName'];

//this is for side content for registration template 1
$config['registration_template1_content'] = [
					'Your information is safe with our high-level security structure.',
					'Fast deposit and withdrawal processing',
					'Up to 100% Free Bonus',
					'Join us now and enjoy various promotions and exclusive benefits!'
				];

$config['how_many_hours_from_utc']=8;

//this is for target function lost for auto generate kyc
$config['kyc_target_function_list'] = [
					'player_no_attached_document' => ['lang_format' => "target_function_5"],
					'player_depositor' => ['lang_format' => "target_function_1"],
					'player_identity_verification' => ['lang_format' => "target_function_2"],
					'player_valid_documents' => ['lang_format' => "target_function_3"],
					'player_valid_identity_and_proof_of_address' => ['lang_format' => "target_function_4"],
					'player_valid_proof_of_income' => ['lang_format' => "target_function_6"],
				];

$config['custom_promo_www_url']= [
        'site' => 'player',
        'url' => 'player_center2/promotion'
    ];

$config['verification'] = [
        "no_attach" => [
                'description' => 'No Attachment',
            ],
        "wrong_attach" => [
                'description' => 'Wrong Attachment',
            ],
        "verified" => [
                'description' => 'Verified',
            ],
        "not_verified" => [
                'description' => 'Not Verified',
            ]
    ];

$config['proof_attachment_type'] = [
    "photo_id" => [
        'description' => 'Photo ID',
        'tag' => 'photo_id'
    ],
    "proof_of_address" => [
        'description' => 'Proof of address',
        'tag' => 'address'
    ],
    "proof_of_income" => [
        'description' => 'Proof of source of wealth',
        'tag' => 'income'
    ],
    "proof_of_dep_wd" => [
        'description' => 'Proof of deposit and withdrawal',
        'tag' => 'dep_wd'
    ]
];

$config['allowed_upload_file'] = "jpg|jpeg|png|gif|PNG";
$config['password_adjust_game_report']='575sdfW$ef2b0';


$config['game_logs_hidden_cols'] =[18];
$config['game_logs_hidden_cols_for_aff'] =[2, 18];

$config['game_reports_hidden_cols'] =[];
$config['game_logs_show_row_colors'] = true;

$config['cloudflare_email']='';
$config['cloudflare_api_key']='';

$config['cloudflare_default_source_ip']='';

$config['cloudflare_t1t_email']='';
$config['cloudflare_t1t_api_key']='';
$config['cloudflare_t1t_games_zone_id']='a8ae652430713cdc5c40a2244a40ea57';

$config['id_card_number_validator'] = [
            "char_lenght" => 18
        ];
$config['currency_api_access_key']='af3d77556e504b24910d7a2bacbb0aaa';
$config['currency_api_url']='http://apilayer.net/api/live';
$config['currency_api_historical']='http://apilayer.net/api/historical';
$config['currency_api_currencies']='CNY';
$config['currency_api_source']='USD';

// skip the captcha validation for a given user used by test automation script
$config['is_auto_machine_user_list'] = [
	'testauto', 'testauto2', 'testauto3'
];

//$config['adjust_cashback_by_odds']=['api'=>[IBC_API, PINNACLE_API], 'hk_max_odds'=>0.5, 'eu_max_odds'=>1.5];
$config['adjust_cashback_by_odds']= [];
//$config['pinnacle_odds_format'] = [1 => 'EU', 2 => 'HK'];  // odds format from pinnacle logs

$config['memcached'] = array(
	'hostname' => '127.0.0.1',
	'port'        => 11211,
	'weight'    => 1
);

$config['local_lock_servers'] = [
	['redisserver', 6379, 0.01],
	// ['127.0.0.1', 6389, 0.01],
	// ['127.0.0.1', 6399, 0.01],
];

$config['prefix_website_list']=['player', 'www', 'admin', 'aff', 'agency', 'm', 'pay', 'pay2', 'l8b8mlgb8', 'yjyladmin171120', 't4j4cbk6'];
$config['prefix_admin_domains']=['admin', 'l8b8mlgb8', 'yjyladmin171120', 't4j4cbk6'];

/**
 * Unable to resolve the problem definition, do not use
 *
 * by Elvis
 */
$config['list_of_ips_to_be_excluded_in_duplicate_account'] = [];

/**
 * The item in this array will be calculate
 * Can set column : ["ip","realname","password","email","mobile","address","country","city","cookie","referrer","device"]
 */
$config['duplicate_account_info_enalbed_condition'] = array("ip", "password");

/**
 * Calculate duplicate account reports within a config days
 */
$config['duplicate_account_calc_days'] = 30;

/**
 * Ignore specified IP to avoid duplicate accounts
 */
$config['skip_save_http_request_from_ip_list'] = [
    "104.155.216.142",
    "35.187.147.215",
    "35.185.156.66",
    "180.232.149.98",
    "119.9.106.90",
    "220.135.118.23",
    "180.232.78.2",
    "119.9.106.90",     //PC-SAFE-BROWSER
    "106.75.166.72",    //PC-SAFE-BROWSER
    "35.201.204.2",     //PC-SAFE-BROWSER
    "106.75.166.72",    //MB-SAFE-BROWSER
    "35.201.204.2",     //MB-SAFE-BROWSER
];

$config['password_nginx_stats']='575sdfWsdfsj234ILLef2b0';
//value of #player_info_dropdown option
$config['player_log_shortcut'] = [ "depositHistory", "withdrawHistory", "transferHistory", "adjustmentHistory2", "gamesHistory", "promoStatus", "cancelledWithdrawalCondition", "cancelledTransferCondition"];

// start for deposit list in player information and payment management
$config['deposit_list_columnDefs'] = [
	"not_visible_payment_management" => [ 9, 12, 14, 16, 19, 22, 24, 25 ],
	"not_visible_player_information" => [ 0, 3, 8, 9, 10, 12, 14, 16, 18, 19, 20, 21, 22, 24, 25, 26],
	"className_text-right_payment_management" => [ 11, 12 ],
	"className_text-right_player_information" => [ 11, 12 ],
];
// $config['hidden_colvis_for_deposit_list_player'] = [ 0, 23, 27 ];
// $config['hidden_colvis_for_deposit_list_payment'] =[ 23, 27 ];
// $config['hidden_colvis_for_deposit_list_locked_deposit'] = [ 23, 27 ];

$config['hidden_colvis_for_deposit_list_player'] = [ 0, 24, 28 ]; # adjust by OGP-28145
$config['hidden_colvis_for_deposit_list_payment'] =[ 24, 28 ]; # adjust by OGP-28145
$config['hidden_colvis_for_deposit_list_locked_deposit'] = [ 24, 28 ]; # adjust by OGP-28145
$config['default_sort_columns'] = [
	'locked_deposit_list' => 5,
	'locked_withdrawal_list' => 5,
	'withdrawal_list' => 5
];

$config['use_default_sort_column'] = false;
// end for deposit list in player information and payment management

// for withdrawal list in player information and payment management
$config['withdrawal_list_columnDefs'] = [
	"not_visible_payment_management" => [ 19, 20, 22, 25, 30, 32 ],
	"not_visible_player_information" => [ 2, 3, 8, 9, 10, 18, 19, 20, 21, 24, 29, 30, 31 ],
    "className_text-right_payment_management" => [ 8, 12, 29 ],
	"className_text-right_player_information" => [ 7, 11, 28 ],
];
/// Replaced the data-field_id attribute in "@.withdrawal_list_columnDefs.not_visible_payment_management" .
// ref. to the data-field_id attribute in the view file, admin/application/views/payment_management/withdrawal_list.php
$config['withdrawal_list_columnDefs']['not_visible_payment_management'] = [
	'province',
	'city',
	'withdraw_location',
	'withdrawal_id',
	'currency',
	'withdrawal_payment_api',
];

//for player list in default column order
$config['player_list_column_order'] = [
	"default_order" => ["batch_message_action","username","online","lastLoginTime","lastLoginIp","blocked","createdOn","registered_by","registrationWebsite","ip_address","referral_code","refereePlayerId","affiliate","agent","tagName","group_level","first_name","last_name","email","contactNumber","imAccount1","imAccount2","imAccount3", "imAccount4","city","country","birthdate", "id_card_number","priority","total_deposit_times","total_total_nofrozen","approved_deposit_count","total_deposit_amount","approved_withdraw_count","total_withdrawal_amount","total_betting_amount"]
];
$config['player_list_columnDefs'] = [
    "not_visible_columns" => ["city","country","birthdate","approved_deposit_count","approved_withdraw_count"]
];
$config['display_last_deposit_col'] = false;
$config['display_net_loss_col'] = false;
$config['allow_record_last_transaction'] = false;

$config['enable_goto_page_pagination_in_player_list'] = false; //OGP-24716

$config['custom_player_center_withdrawal_display_items_sequence_for_mobile'] = [];
$config['custom_player_center_withdrawal_display_items_sequence_for_desktop'] = [];
$config['display_player_balance_in_mobile_withdrawal_page'] = false;

$config['web_user_terms_url'] = 'terms_and_conditions.html';
$config['web_privacy_policy_url'] = 'privacy_policy.html';

$config['log_use_elasticsearch']=false;

//Mode of Deposit for manual Deposit
$config['mode_of_deposit'] = ['internet_banking','over_the_counter_deposit','atm_transfer','mobile_banking','cash_deposit_machine','wechat','alipay','tenpay','qqpay'];

$config['write_response_result_to_dir']=null;

$config['mobile_player_center_custom_buttons']=null;

$config['always_delete_total_tmp_shell']=true;

$config['game_list_api_url'] = "http://admin.gamegateway.t1t.games";
$config['game_list_image_path_url'] = "/gamegatewayincludes";
$config['custom_game_list_image_path_url'] = [];

$config['export_data_white_functions']=[
	'admin'=>['player_reports', 'player_list_reports', 'transaction_details', 'balance_transaction_details', 'promotion_report',
		'duplicate_account_report', 'game_report', 'game_report_results', 'active_player_report_results',
		'cashback_report', 'recalculate_cashback_report', 'withdraw_condition_deduction_report', 'recalculate_withdraw_condition_deduction_report',
        'affiliate_statistics', 'affiliate_statistics2', 'affiliate_earnings', 'aff_list', 'payment_report', 'gameDescriptionList','gameDescriptionHistory',
		'queue', 'check_queue', 'depositList', 'withdrawList', 'promoApplicationList', 'referralPromoApplicationList', 'hugebetReferralPromoApplicationList', 'smsVerificationCodeReport', 'withdrawCheckingReport', 'depositCheckingReport',
		'affiliateTag', 'gamesHistory', 'affiliatePayment', 'taggedPlayers', 'playerTagManagement',
		'batchCreate', 'friendReferral', 'ipTagList', 'bankPaymentList', 'transferRequest', 'agency_logs',
		'credit_transactions', 'agent_list', 'structure_list', 'paymentAPI', 'getAllGameType', 'userLogs',
		'viewRoles', 'countryRules', 'exception_order_list', 'export_vip_setting_list', 'export_collection_account',
		'export_task_list', 'export_response_result_list','export_super_player_report','export_super_summary_report','export_super_game_report',
		'export_super_payment_report','export_super_promotion_report','export_super_cashback_report','gamesHistoryV2','getAllGameTypeHistory',
		'agency_game_reports','adminusers_results', 'grade_report', 'rank_report', 'redemptionCodeReport','staticRedemptionCodeReport','exportResponsibleGamingReport', 'playertaggedlist','shoppingItemList',
        'agency_player_reports', 'player_analysis_report','agency_settlement_list_wl','payment_status_history_report','kingrichApiResponseLogs',
        'exportCommunicationPreferenceReport','exportIncomeAccessSignupReports', 'exportIncomeAccessSalesReports','kingrich_summary_report','stop_queue',
        'player_daily_balance', 'player_realtime_balance', 'player_reports_2', 'playerAdditionalRouletteReports', 'playerAdditionalReports', 'exportPlayerAttachmentFileList',
        'kingrich_scheduler_report','kingrich_scheduler_summary_logs','kyc_c6_acuris_by_player_report','conversion_rate_report','gameApiUpdateHistory','transactions_daily_summary_report', 'iovationReport',
		'iovationEvidence', 'achieveThresholdReport', 'game_report_timezone','shopping_point_report', 'export_png_freegame', 'abnormalPaymentReport','friendReferral',
		'playertaggedlist','communicationPreferenceReports','playerAttachmentFileList','achieve_threshold_report','export_ip_history','playerLoginReport','export_affiliate_traffic_statistics',
		'export_player_basic_amount_list', 'adjusted_deposits_game_totals_via_queue', 'excessWithdrawalRequestsList','export_free_spin_campaign_list','dedicated_additional_domains_report','export_system_feature_search_result',
        'reportManyPlayerLoginViaSameIpLogsListViaQueue', 'reportManyPlayerLoginViaSameIpLogsListdDirectly','adjustmentScoreReport','affiliatePartners','playerRouletteReport', 'seamless_balance_history', 'export_player_center_api_domains',
        'playertaggedlistHistory', 'showActivePlayers', 'playertaggedHistory', 'tournamentWinnerReports', 'getAllGameTags', 'remote_wallet_balance_history','playerDuplicateContactNumberReport','export_message_list_report','export_player_remarks_report', 'quest_report', 'game_billing_report', 'player_game_and_transaction_summary_report'],
	'aff'=>['export_game_report_from_aff', 'traffic_statistics_aff', 'queue', 'check_queue','game_report_results','export_game_report_from_aff','stop_queue','gameApiUpdateHistory'],
	'agency'=>['agency_player_reports', 'agency_agent_reports', 'agency_game_reports', 'agency_settlement_list',
		'agency_invoice', 'create_invoice_name','agency_settlement_list_wl','queue', 'check_queue','agency_game_history','stop_queue', 'affiliate_traffic_statistics'],
];

$config['export_data_remote_functions']=[
        'admin' => ['gamesHistory','player_list_reports','transaction_details','depositList','withdrawList','transferRequest','gameReports','payment_report','promotionReport',
        'cashbackReport','recalculate_cashback_report','withdraw_condition_deduction_report','recalculate_withdraw_condition_deduction_report',
        'playerGradeReports','promoApplicationList','referralPromoApplicationList','hugebetReferralPromoApplicationList','aff_list','aff_daily_earnings','aff_monthly_earnings','player_reports', 'player_daily_balance', 'player_reports_2', 'playerAdditionalRouletteReports', 'playerAdditionalReports', 'gamesHistoryV2',
        'affiliateStatistics','affiliateStatistics2','gameApiUpdateHistory','task_list','withdrawCheckingReport','depositCheckingReport','transactions_daily_summary_report',
        'shopping_point_report','friendReferral','ipTagList','playertaggedlist', 'shoppingItemList','player_analysis_report','communicationPreferenceReports','playerAttachmentFileList','achieve_threshold_report', 'export_ip_history','export_message_list_report',
        'export_player_basic_amount_list', 'exportPlayerLoginViaSameIpLogsList', 'balance_transaction_details',
        'super_summary_report','super_player_report','super_game_report','super_payment_report','super_promotion_report','super_cashback_report', 'seamless_balance_history','export_player_remarks_report',
		'redemptionCodeReport', 'playertaggedlistHistory', 'playertaggedHistory', 'remote_wallet_balance_history','staticRedemptionCodeReport', 'gameBillingReports','playerGameAndTransactionSummaryReport'
        ],
        'aff' => ['gameReports'],
        'agency' =>['agency_game_history'],
];

$config['use_export_csv_with_progress']=[
        'admin' => ['gamesHistory','player_list_reports','transaction_details','depositList','withdrawList','transferRequest','gameReports','payment_report','promotionReport',
        'cashbackReport','recalculate_cashback_report','withdraw_condition_deduction_report','recalculate_withdraw_condition_deduction_report',
        'playerGradeReports','promoApplicationList','referralPromoApplicationList','hugebetReferralPromoApplicationList','aff_list','aff_daily_earnings','aff_monthly_earnings','getUserLogs','player_reports', 'player_daily_balance', 'player_reports_2', 'playerAdditionalRouletteReports', 'playerAdditionalReports', 'gamesHistoryV2',
        'affiliateStatistics','affiliateStatistics2','gameApiUpdateHistory','task_list','withdrawCheckingReport','depositCheckingReport','transactions_daily_summary_report',
        'shopping_point_report','friendReferral','ipTagList','playertaggedlist','player_analysis_report','communicationPreferenceReports','playerAttachmentFileList','achieve_threshold_report', 'export_ip_history',
        'export_player_basic_amount_list', 'exportPlayerLoginViaSameIpLogsList', 'balance_transaction_details',
        'super_summary_report','super_player_report','super_game_report','super_payment_report','super_promotion_report','super_cashback_report', 'seamless_balance_history', 'playertaggedlistHistory', 'playertaggedHistory', 'remote_wallet_balance_history','export_message_list_report','export_player_remarks_report', 'gameBillingReports', 'playerGameAndTransactionSummaryReport'
        ],
        'aff' => ['gameReports'],
        'agency' =>['agency_game_history'],
];

$config['type_of_id_card']=[
	'passport' => '_json:{"1":"Passport","2":"护照","3":"Passport","4":"Passport","5":"Passport"}',
	'drivers_license' => '_json:{"1":"Drivers License","2":"驾照","3":"Drivers License","4":"Drivers License","5":"Drivers License"}'
];

$config['cookie_name_lang'] = "_lang";

$config['default_admin_usernames'] = ['admin','superadmin'];
$config['enable_thousands_separator_in_the_withdraw_amount'] = false;
$config['enable_thousands_separator_in_the_deposit_amount'] = false;

$config['logger_file_list']=[
 //    'sync_totals' => BASEPATH.'/../application/logs/sync_totals.log',
	// 'sync_game_records'=> BASEPATH.'/../application/logs/sync_game_records.log',
	// 'sync_balance'=> BASEPATH.'/../application/logs/sync_balance.log',
	// 'sync_t1_gamegateway'=> BASEPATH.'/../application/logs/sync_t1_gamegateway.log',
	// 'batch_scan_suspicious_transfer_request_cronjob'=>BASEPATH.'/../application/logs/batch_scan_suspicious_transfer_request_cronjob.log',
	// 'generate_player_report_hourly' => BASEPATH.'/../application/logs/generate_player_report_hourly.log',
    // 'generate_admin_dashboard' => BASEPATH.'/../application/logs/generate_admin_dashboard.log',
    // 'rebuild_totals'=> BASEPATH.'/../application/logs/rebuild_totals.log',
    // 'command' => BASEPATH.'/../application/logs/command.log',
    'queue_server' => BASEPATH.'/../application/logs/queue_server.log',
];

$config['adjust_datetime_minutes_sync_totals']='-60 minutes';

$config['SHARING_PRIVATE_PATH'] = realpath(APPPATH . '/../../sharing_private');

$config['player_report_dt_config'] = [

	"text_right_targets" => ['total-deposit-bonus', 'total-cashback-bonus','total-referral-bonus', 'manual-bonus', 'subtract-bonus', 'total-bonus', 'subtract-balance', 'total-first-deposit', 'total-last-deposit', 'total-second-deposit', 'total-deposit', 'deposit-times','total-withdrawal','deposit-withdraw','deposit-minus-withdrawal','total-bets','payout','payout-rate',
                    'deposit-and-bonus', 'bonus-over-deposit', 'deposit-minus-withdrawal', 'withdrawal-over-deposit', 'turn-around-time', 'total-win', 'total-loss','total-revenue'], //Add 'total-last-deposit' OGP-23926
	"hidden_cols_targets" => ['realname', 'affliate', 'email', 'phone', 'registered-ip', 'last-login-ip','last-logout-date','register-date'], //Remove 'last-login-date' OGP-23926
	"disable_cols_order_target" => ['bet-details'] ,
	'default_export_cols' => ['username','risk_level','kyc_level','total_bets','total_payout','payout_rate']

];

$config['currency_list_super_report'] = [
                                            'CNY' => ['label-color' => 'label-warning'],
                                            'IDR' => ['label-color' => 'label-primary'],
                                            'VND' => ['label-color' => 'label-success'],
                                        ];

$config['game_with_no_free_spin'] =  [];

$config['app_api_key'] = 'cdf55701104c0936c6c03d30cd567332';

//default is 60
$config['adjust_sync_datetime']='30';
$config['mattermost_channels'] = [
	'manual_sync' => "https://talk.letschatchat.com/hooks/9xou8tqf13rubxmahoeo3unaec",
	'qa_test_players' => "https://talk.letschatchat.com/hooks/a5syniigsffmzraue5emrq35ie",
	'dev_test_players' => "https://talk.letschatchat.com/hooks/u98gte5hk7nczb6e7qfghr5z7r",
	'itdesk_test_players' => "https://talk.letschatchat.com/hooks/6aee64k3sjgat8xq1xqsryzsza",
	'auto_test_players' => "https://talk.letschatchat.com/hooks/cftrtyc11bngbpirbqq71ezixw",
	't1_players_master' => "https://talk.letschatchat.com/hooks/t8n35zdn4bnbifg94toiuyexew",
	'game_list_update' => "https://talk.letschatchat.com/hooks/xdr8nbi5ytyk88y31br87hrhcw",
	'test_mattermost_notif' => "https://talk.letschatchat.com/hooks/kakwc6z3ubdadb1781sp655wgc",
	'private_ip_alert' => "https://talk.letschatchat.com/hooks/iqbek33uefr39jz15zpgi1khke",
	'delete_table_data'=> "https://talk.letschatchat.com/hooks/k7d91bksh7yub8gz717umuf3pr",
	'system_alert' => "https://talk.letschatchat.com/hooks/7qq9gpy977nwfjwkximnksaume",
	'payment_alert' => 'https://talk.letschatchat.com/hooks/1g8w8ywud38yzejqbjcybcg87y',
	'clients_test_players' => 'https://talk.letschatchat.com/hooks/48neewysuifzdxgax3zyyh94ra',
	'gw001_timeout_monitor' => 'https://talk.letschatchat.com/hooks/jaqrducsztrwpddct7cezw967y',
	'entaplay_sql_export_and_delete'=> "https://talk.letschatchat.com/hooks/baszsu8a9pngxmhi4oizwokuwr",
	'ole777_sql_export_and_delete' => "https://talk.letschatchat.com/hooks/e4oys7oxypn8ub3r3zc4d1fs7h",
	'high_level_monitor' => "https://talk.letschatchat.com/hooks/id5kmaj3xir49bb4y79mxxk8qc",
	'db_high_level_error_monitor' => "https://talk.letschatchat.com/hooks/8xw7t98wipbtfnpu3rk4sg8iue",
	'onestop_system_feature_alert' => "https://talk.letschatchat.com/hooks/wwjk791i4tbidnfow4rsf4yxha",
	'sexycasino_seamless_balance_monitoring' => 'https://talk.letschatchat.com/hooks/opgtyz98cjg6jf5946fsi1dy1c',
	'php_alert' => 'https://talk.letschatchat.com/hooks/t8ox7sfo1jra5d3cw3k155o9kc',
	'data_alert' => 'https://talk.letschatchat.com/hooks/t8rxhe9jpbrb7ynpfi6mepcsao',
];

/// PPN002, PHP Personal Notification 002
$config['mattermost_channels']['PPN002'] = 'https://talk.letschatchat.com/hooks/sy7dqz35ting5rsynfb989krco';
// OGP-23595
$config['detectSmallestNegativeBalanceAndNotifyIntoMM'] = [];
$config['detectSmallestNegativeBalanceAndNotifyIntoMM']['enable'] = false;
$config['detectSmallestNegativeBalanceAndNotifyIntoMM']['warningAmount'] = -0.01;
$config['detectSmallestNegativeBalanceAndNotifyIntoMM']['forcedNotify'] = [];
// $config['detectSmallestNegativeBalanceAndNotifyIntoMM']['forcedNotify']['playerId'] = 170951;
// $config['detectSmallestNegativeBalanceAndNotifyIntoMM']['forcedNotify']['playerAccountId'] = 718967;

// OGP-23811
$config['moniter_player_login_via_same_ip'] = [];
$config['moniter_player_login_via_same_ip']['is_enabled'] = false;
$config['moniter_player_login_via_same_ip']['query_interval'] = 600; // 10 min
$config['moniter_player_login_via_same_ip']['except_ip_list'] = [];
// $config['moniter_player_login_via_same_ip']['except_ip_list'][] = '119.9.106.90';
// $config['moniter_player_login_via_same_ip']['except_ip_list'][] = '220.135.118.23';
$config['moniter_player_login_via_same_ip']['notify_in_mattermost_channel'] = ''; // 'high_level_monitor';// mattermost_channels.key
$config['moniter_player_login_via_same_ip']['tag_name_detected'] = ''; // The tag name of the player after detected, ex: 'Suspected hacked'.

/// if its null, pgrm_start_time/pgrm_end_time will be  the moment of current.
// If its integer type assign, pgrm_start_time/pgrm_end_time will be  specified delay sec by request_time.
$config['force_pgrm_start_time_delay_by_request_time_sec'] = false;
$config['force_pgrm_end_time_delay_by_request_time_sec'] = false;

$config['force_created_at_delay_by_request_time_sec_in_log_accumulated_amount'] = null;
$config['force_updated_at_delay_by_request_time_sec_in_log_accumulated_amount'] = null;

// OGP-24903
$config['enable_accumulation_reset_ui']=false;
// OGP-28078
$config['assign_last_changed_into_from_of_period_in_accumulation_mode_reset_if_met'] = false;

$config['sbe_default_theme'] = 'yeti';

$config['sbe_contact_info'] = [
    'company_title' => 'TOT',
    'email' => 'sales@tot.bet',
    'skype' => NULL,
    'website' => [
        'display_name' => "www.tot.bet",
        'url' => "https://www.tot.bet"
    ]
];
$config['show_login_sbe_contact'] = false;

$config['login_settings']['failed_attempt_limit'] = 12;
$config['login_settings']['failed_attempt_time_frame'] = 600;
$config['login_settings']['mattermost_channel'] = 'high_level_monitor';

//default getSecureId random length
$config['get_secureid_random_length'] = '5';

$config['check_sub_wallect_balance_in_withdrawal'] = false;

$config['minify_setting'] = [
	'admin'  => [],
	'player' => [
		'css' => [
			'stable_center2/style.css',
			'stable_center2/styles/base-theme-blue.css',
			'stable_center2/styles/base-theme-green.css',
			'stable_center2/styles/base-theme-lottery.css',
			'stable_center2/styles/base-theme-orange.css',
			'stable_center2/styles/base-theme-purple.css',
			'stable_center2/styles/base-theme-red.css',
			'stable_center2/css/style-mobile-default.css'
		],
		'js'  =>[
			'resources/js/validator.js',
            'common/js/main.js',
            'common/js/main-mobile.js',
            'stable_center2/js/template-script.js',
            'stable_center2/js/template-script-mobile.js'
		]
	]
];

# white list for friend referral cashback
# only referrer will pay.
//$config['allowed_player_for_referral_cashback'] = ['test002','testzai01'];
$config['allowed_player_for_referral_cashback'] = [];
$config['call_gamegateway_api_url']='http://admin.og.local';

//update_status, auto_fix, none
$config['action_of_search_suspicious_transfer_request']='update_status';
$config['registered_image_poup_path'] = 'images/registered-popup.png';



$config['sync_table_receiver_by_date'] = [
  //'newrainbow' => 'http://player.newrainbow.t1t.games/api/sync_table_receiver_by_date/'
  //'newrainbow' => 'http://player.og.local/api/sync_table_receiver_by_date/'
];
$config['sync_table_receiver_by_id'] = [
  //'newrainbow' => 'http://player.newrainbow.t1t.games/api/sync_table_receiver_by_id/'
  //'newrainbow' => 'http://player.og.local/api/sync_table_receiver_by_id/'
];


$config['sync_to_remote_batch_tables'] = [

	// 'newrainbow' => [
	// 	'hourly' => [
	// 		'game_logs' => ['sync_by' => 'date' , 'step_time_interval'=> 30, 'date_field' => 'start_at', 'id_field' => 'id'],
	// 		'total_player_game_hour' => ['sync_by' => 'date','unique_id_field' => 'uniqueid', 'step_time_interval'=> 30, 'date_field' => 'date_hour', 'id_field' => 'id','format_date_by' => 'date_hour'],
	// 		'player' => ['sync_by' => 'date', 'step_time_interval'=> 30, 'date_field' => 'createdOn', 'id_field' => 'playerId' ],
	// 		'playeraccount' =>  ['sync_by' => 'id', 'row_per_page'=> 30, 'id_field' => 'playerAccountId' ],
	// 		'tag'=> ['sync_by' => 'id', 'row_per_page'=> 30, 'id_field' => 'tagId' ],
	// 		'playertag' => ['sync_by' => 'id', 'row_per_page'=> 50, 'id_field' => 'playerTagId' ],
	// 		'adminusers'=> ['sync_by' => 'id', 'row_per_page'=> 30, 'id_field' => 'userId' ],
	// 	],
	// 	'daily' => [
 //                //'tag'=> ['sync_by' => 'id', 'row_per_page'=> 30, 'id_field' => 'tagId' ],
 //                //'playertag' => ['sync_by' => 'id', 'row_per_page'=> 30, 'id_field' => 'playerTagId' ],
	// 		'playerdetails' => ['sync_by' => 'id', 'row_per_page'=> 30, 'id_field' => 'playerDetailsId' ],
	// 		'game_type' => ['sync_by' => 'id', 'row_per_page'=> 30, 'id_field' => 'id' ],
	// 		'game_description' => ['sync_by' => 'id', 'row_per_page'=> 30, 'id_field' => 'id' ]
	// 	],
	// 	'weekly' =>[
	// 		'external_system'=> ['sync_by' => 'id', 'row_per_page'=> 10, 'id_field' => 'id' ]
	// 	]
	// ]

 //    ,
	// 'other' =>[]
];

$config['overview'] = [
    // null => not limited, or define list
    'today_total_betting_platforms' => null
];

$config['api_not_allowed_cents'] = [BBIN_API,AGSHABA_API,EBET_BBTECH_API,EBET_GGFISHING_API,EBET_IMPT_API,
		EBET_OPUS_API,EBET_SPADE_GAMING_API,EBET_BBIN_API,GSAG_API,MWG_API,YUNGU_GAME_API, AB_API];

# use for force testing error message
$config['player_for_testing_custom_error_message'] = 'testmaria';

$config['IM_list'] = ['_json:{"1":"Weixin","2":"Weixin"}', '_json:{"1":"QQ","2":"QQ"}', '_json:{"1":"Line","2":"Line"}', '_json:{"1":"Skype","2":"Skype"}', '_json:{"1":"Other IM","2":"其他IM"}'];

$config['register_mobile_number_regex'] = NULL; # Example, China mobile number: '^(13[0-9]|14[579]|15[0-3,5-9]|17[0135678]|18[0-9])\d{8}$'

$config['shorturl'] = [
    'curl' => [
        'timeout_second' => 10,
        'connect_timeout' => 5,
        'skip_ssl_verify' => TRUE
    ],
    'use_service' => 'ft12'
];

$config['top_agent_config'] = [
    'tracking_code' => '_top_agent_0_',
    'bonus_rate' => 1980,
    'rebate_rate' => 0,
    'player_type' => AGENCY_TRACKING_SOURCE_CODE_PLAYER_TYPE_AGENT,
    'agent_template_id' => NULL,
];

$config['agency_auto_create_from_player'] = [
    'default_parent_agent_id' => 0,
    'username_prefix' => 'ag_',
    'can_have_sub_agent' => 1,
    'can_have_players' => 1,
    'show_bet_limit_template' => 1,
    'show_rolling_commission' => 1,
    'agent_template_id' => NULL,
    'can_view_agents_list_and_players_list' => 1,
    'settlement_period' => 'Weekly',
    'settlement_start_day' => 'Monday',
	'player_type_no_source_code'=>AGENCY_TRACKING_SOURCE_CODE_PLAYER_TYPE_AGENT,
	'player_belongs_to_agent'=>'parent_agent', //current_agent, parent_agent
];

# add withdrawal api id to show up "Check Withdraw Status" button
$config['enabledCheckWithdrawalStatusIdList'] = ['9997','99', '375', '384', '385', '401', '494', '555', '572', '600', '624', '665', '719', '810', '816', '933', '982', '5002', '5076', '5208', '5220', '5224', '5346', '5385', '5386', '5405', '5413', '5472', '5524', '5748', '5838', '5844', '5908', '6011' ,'6017', '6039', '6040', '6046'];

# add Deposit api id to show up "Check Deposit Status" button
$config['enabledCheckDepositStatusIdList'] = ['560', '561', '5134', '5145', '5191', '5292'];

# enabled Deposit bonus of payment account
$config['enabled_bonus_percent_on_deposit_amount'] = false;

$config['t1admin_username_list']=['superadmin', 't1_qaadmin', 't1_autoadmin1', 't1_autoadmin2','t1_qatest','t1_qatest2','t1_qatest3','t1_qamanager1','t1_qamanager2'];

$config['t1_username_list']=['superadmin', 't1_qaadmin', 't1_qatest', 't1_qatest2', 't1_manualqa1', 't1_manualqa2', 't1_manualqa3',
	't1_reporting', 't1_designer', 't1_designer2', 't1_designer3', 't1lottery_master', 't1_autoadmin1', 't1_autoadmin2','t1_qamanager1','t1_qamanager2'];

$config['authorized_view_payment_secret_list'] = ['superadmin'];
$config['authorized_view_sms_verification_code'] = ['_all_user'];
$config['authorized_view_email_verification_code'] = ['_all_user'];


$config['game_history_override_handicap_if_zero_platform_ids'] = array();
//30000=30seconds
$config['refresh_balance_interval_millisecond']=30000;

$config['agency_contact_email'] = 'agency@nothing.com';
$config['agency_contact_qq'] = '';
$config['agency_contact_skype'] = '';
$config['agency_contact_type'] = '';
$config['agency_contact_type_label'] = 'QQ联系';

$config['t1games_using_external_game_id'] = [MG_API,ISB_API,GD_API,SUNCITY_API,KYCARD_API];

$config['auto_refresh_cold_down_time_milliseconds']=5000;

$config['turn_off_batch_sync_balance_all']=true;

// -- IOM -- Communication Preferences

$config['communication_preferences'] = array('email' 		=> '_json:{"1":"Email","2":"Email","3":"Email","4":"Email","5":"Email","6":"Email"}',
											 'sms'   		=> '_json:{"1":"SMS","2":"SMS","3":"SMS","4":"SMS","5":"SMS","6":"SMS"}',
											 'phone_call'   => '_json:{"1":"Phone Call","2":"Phone Call","3":"Phone Call","4":"Phone Call","5":"Phone Call","6":"Phone Call"}',
											 'post'   		=> '_json:{"1":"Post","2":"Post","3":"Post","4":"Post","5":"Post","6":"Post"}',
											);

$config['sync_t1_sleep_seconds']=10;

$config['alwaysEnableQueue']=false;
//call unsettle t1 api
$config['enable_sync_t1_unsettle']=true;
$config['enabled_sync_t1_quickly']=false;
//process game_logs_unsettle: processUnsettleGameLogs
$config['enabled_game_logs_unsettle']=true;

$config['always_load_system_feature_to_cache']=true;

//live database is 1, staging database is 2
//35.187.153.12
$config['queue_redis_server'] = null;
// ['server'=>'10.140.0.9', 'port'=>6379, 'password'=>null, 'timeout'=>1, 'retry_timeout'=>10, 'password'=>null, 'database'=>null];
$config['queue_redis_pop_timeout'] = 600;

$config['rabbitmq_server']= null; //['host'=>'rabbitmq', 'port'=>5672, 'username'=>'php', 'password'=>'php']

$config['debug_preprocess_js']=false;

$config['debug_last_version_key']=null;

$config['player_auto_lock_password_failed_attempt'] = 3;


// -- INCOME ACCRESS
$config['ia_sftp_hostname'] = '';
$config['ia_sftp_port'] = '';
$config['ia_sftp_username'] = '';
$config['ia_sftp_password'] = '';


$config['ia_daily_signup_filename_prefix'] = '';
$config['ia_daily_signup_filepath'] = 'Downloads';
$config['ia_daily_signup_file_extension'] = '.csv';
$config['ia_daily_signup_filename_prefix'] = '';

$config['ia_daily_signup_csv_headers'] = array('ACCOUNT_OPENING_DATE', 'BTAG', 'PLAYER_ID', 'USERNAME', 'PLAYER_COUNTRY');


$config['ia_daily_sales_filename_prefix'] = '';
$config['ia_daily_sales_filepath'] = 'Sales';
$config['ia_daily_sales_file_extension'] = '.csv';
$config['ia_daily_sales_csv_headers'] = array('TRANSACTION_DATE','PLAYER_ID','BTAG','PLAYER_COUNTRY','DEPOSITS','CHARGEBACKS','CASINO_BETS','CASINO_REVENUE','CASINO_STAKE','CASINO_BONUS','SPORTSBOOK_BETS','SPORTSBOOK_REVENUE','SPORTSBOOK_STAKE','SPORTSBOOK_BONUS');
$config['ia_daily_sales_filename_prefix'] = '';
$config['ia_daily_sales_products'] = array('CASINO' => 'live_dealer,slots', 'SPORTSBOOK' => 'sports');

$config['enable_change_player_status_pep_authentication'] = false;

$config['manually_deposit_cool_down_minutes'] = array(2, 6, 10, 30, 60);

$config['default_importer']='importer_standard';

$config['withdrawal_name_required_before_withdrawal'] = false;

$config['importer_kash_enabled'] = [];
$config['importer_kash_enabled']['IMPORT_PLAYER_CSV_HEADER'] = [ 'RealName' // #1
                                                                , 'Birthday' // #2
                                                                , 'GenderID' // #3
                                                                , 'CountryID' // #4
                                                                , 'Mobile' // #5
                                                                , 'CurrencyID' // #6
                                                                , 'Email' // #7
                                                                , 'CreateDate' // #8
                                                                , 'UserCode' // #9
                                                                , 'password' // #10
                                                                , 'AffiliateCode' // #11
                                                                , 'AvailableBalance' // #12
                                                                , 'Status' // #13
                                                                , 'LastLogOutTime' // #14
																, 'IMAccount' // #15
                                                            ];
$config['importer_kash_enabled']['IMPORT_AFF_CSV_HEADER'] = [ 'AffiliateCode' // #1
                                                                , 'password' // #2
                                                                , 'Status' // #3
                                                                , 'AffiliateID' // #4
                                                                , 'ParentAffiliateUsername' // #5
                                                                , 'CurrencyID' // #6
                                                                , 'CountryID' // #7
                                                                , 'RealName' // #8
                                                                , 'Gender' // #9
                                                                , 'CreateDate' // #10
                                                                , 'Birthday' // #11
                                                                , 'Mobile' // #12
                                                                , 'Email' // #13
                                                                , 'PromotionWebsite' // #14
                                                                , 'AffTrackingCode' // #15
                                                            ];

//enable this to allow admin user to manually adjust the score in pep even it's link in id3
$config['enable_change_player_pep_status_when_binding_ID3'] = false;

$config['deposit_limits_day_options'] = ['1','7','14','30'];
$config['wagering_limits_day_options'] = ['1','7','14','30'];

$config['comapi_getPlayerPasswordPlain_max_time_diff_allowed'] = 60;
$config['SHARING_UPLOAD_PATH'] = realpath(APPPATH . '/../sharing_upload/');


/**
 * NEW SMTP API CONFIGURATIONS
 */
$config['enable_smtp_api'] = false;
$config['current_smtp_api'] = null;
$config['smtp_api_info'] = array();
$config['display_smtp_api_config_detail'] = false;

$config['enable_player_list_batch_send_smtp'] = false; // OGP-22752 [Email: Sendgrid API] Integrate Dynamic Transactional Templates
$config['send_batch_email_type'] = array('SMTP');
$config['sendgrid_api_setting'] = array();
$config['export_player_list_batch_send_mail_fail_data_only'] = false;


$config['dt_use_fetch_all_on_csv_export'] = true;

$config['enabled_t1_directly_sync_api_list']=[];
$config['game_api_list_for_syncing_even_maintenance'] = [];
$config['t1_games_local_path'] = 'game_platform/t1_api';
$config['enabled_additional_error_log_file']=true;

$config['disable_cache']=false;

$config['adjust_datetime_minutes_sync_t1_gamegateway']=5;
$config['adjust_datetime_minutes_sync_t1_gamegateway_stream']=0;
$config['always_manually_sync_t1_gamegateway']=false;
$config['enabled_sync_t1_gamegateway_stream']=false;
$config['gamegateway_stream_query_max_limit_seconds']=60;
$config['gamegateway_stream_query_max_range_hours']=2;
$config['gamegateway_query_game_logs_max_range_hours']=2;
$config['gamegateway_enable_save_http_request']=true;

$config['enabled_rabbitmq_logger']=false;
$config['enabled_redis_logger']=true;

$config['log_recorder_path']=null;

$config['apis_with_bet_limit'] = [EBET_API, EBET2_API];

$config['updatePlayerTag4usernameListMaxLimit'] = 100;

//call_socks5_proxy, call_http_proxy_host, call_http_proxy_port, ignore_ssl_verify
$config['external_login_settings']=null;
$config['external_login_api_class']=null;

$config['ignore_auto_add_new_game_to_vip_cashback']=false;
$config['manual_game_list_sync']=false;
$config['use_custom_im_account_fields']=false;

$config['get_only_success_grade_report'] = true;
$config['show_non_active_game_api_game_list'] = false;

$config['game_tags_colors'] = ['#E6B970','#A07FE3','#E39A9A','#DD6D8F','#52CCC6','#FFD81B','#F7E08F','#3CC74D','#E4A5E2','#BB40A6','#B18627','#B5D6FF','#FFC8C8','#6BA52D','#6474BC','#CCCCCC','#F964AF','#7EDDB6','#DCAD92','#C3B1B1','#E594C4','#99B1DC'];
#OGP-19636 add game tags tag_code
$config['hide_not_support_game_tags_for_dashboard'] = [];

// This config is for totbet agent master key
$config['agent_master_key'] = "";

$config['monitor_cache_key']=null;
// This config is for to recalculate api by settled time
$config['api_array_when_calc_cashback_by_settled_time'] = [];
// This is for OGLANG-191
$config['contact_number_note'] = false;
$config['allow_batch_game_list_update'] = false;

$config['player_notification_display_time'] = 5;

/**
 * This defines how long can the date range be set when a user searches for game logs.
 * By default, the Marketing/Reports Management Tab > Game Logs can only be searched with a date range not exceeding 2 days.
 * 		- If a player username was supplied in the report's filter, the date range will be allowed to a maximum of 31 days.
 * By default, the Player User Information > Game History can only be searched with a date range not exceeding 7 days.
 * By default, the Affiliate BO > Game History can only be searched with a date range not exceeding 2 days.
 * By default, the Agency BO > Game History can only be searched with a date range not exceeding 2 days.
 *
 * The value is expected to be a positive integer.
 *
 * Set all to null / false to disable restriction
 * OGP-9224 && OGP-9485 && OGP-9647
 */
$config['game_logs_report_date_range_restriction'] = 2;
$config['game_logs_report_with_username_date_range_restriction'] = 31;
$config['player_game_history_date_range_restriction'] = 7;
$config['affiliate_game_logs_report_date_range_restriction'] = 2;
$config['agency_game_logs_report_date_range_restriction'] = 2;
$config['affiliate_statistics2_report_date_range_restriction'] = 31;

$config['affiliate_statistics2_deposited_player_with_affiliate_ttl'] = 0; // unit: sec

//this config to allow to display oneworks game report
$config['show_oneworks_report'] = false;

$config['monitor_operator_settings']=[];
$config['affiliate_external_login_api_class']=null;
$config['affiliate_external_login_settings']=null;

$config['daily_player_balance_report_limit']=1000;
$config['exempted_game_type_codes_for_sync']=['tip','yoplay'];
$config['is_vue_dev'] = false;
$config['game_provider_with_game_list_api'] = [
	QT_API=>['api_auto_update'=>0],
	PRAGMATICPLAY_API=>['api_auto_update'=>0],
	LIVEGAMING_API=>['api_auto_update'=>0],
	EVOLUTION_SEAMLESS_GAMING_API=>['api_auto_update'=>0],
];
$config['billing_day']=15;
$config['remote_batch_add_cashback_bonus_max_error_stop'] = 100;

$config['multiple_currency_enabled'] = false;
$config['kyc_limit_of_upload_attachment'] = 8;
$config['player_kyc_access_key'] = '';
$config['player_kyc_valid_time_interval'] = 60; // please make sure this num can divide 60

$config['default_super_settings']=[
	'pageSizeList'=>[10, 25, 50, 100, 500],
	'showSubTotal'=>true,
];
//summary2=>['page_size_list']
$config['super_report_settings']=[];
$config['debug_report_mode']=false;
$config['remote_log_viewing_file_size_limit'] = 10485760; // -- 10mb

$config['management_logs_list'] = ['player_management','user_management','Player Management','payment_management','Payment Management','marketing_management','cms_management','payment_account_management','system_management','cs_management','report_management','theme_management','System Management','Marketing Management','country_rules_management','affiliate_management','vipsetting_management','notification_management','livechat_management','ip_management','role_management','Added IP','Theme Management','VIP Setting Management','agency_management','Add CMS Promo','CMS Promo Management','Marketing Promo Management','Withdraw Condition','Payment Account Management','CashBack Period Setting Management','config_management','CMS Promo Setting Management','CMS Management','Block IP','Delete IP','game_api','Close Message','payment_api','Unlock Tracking Code','Add Domain','Deactivate Domain','Affiliate Tag','Affiliate Management','Affiliate Banner','Delete Domain','Edit Domain','已添加IP','Edit CMS Email Manager','Delete Banner','Enable IP Whitelisting','cmsbanner_management','Reset Password of Affiliate','Add notes to affiliate','dispatch_account_management','Reply Message','Dispatch Account Management','Edit IP','testing_lib_game_rwb_api','Disable IP Whitelisting','Allow IP','Activate Affiliates','export_data'];


$config['enable_kingrich_gametypes_new'] = false;

// -- OGP-10398 | player_model > syncAllPlayersOnlineStatus > chunk limit
$config['sync_all_player_online_status_chunk_limit'] = 100;

// -- OGP-10685 | player_model > syncAllPlayersSummary > chunk limit
$config['sync_all_player_withdraw_deposit_totals_chunk_limit'] = 100;

$config['enabled_remote_async_event']=true;
// -- OGLANG-385 | if set to TRUE, this will use the language full_custom_affiliate_message_after_registration for the message upon affiliate registration
$config['full_custom_affiliate_message_after_registration'] = FALSE;

/* Request Form - start <editor-fold>{{{ */
$config['message_request_form_default_allow_submit_times'] = 5;
$config['message_request_form_default_subject'] = 'message.request_form.default_subject';
/* Request Form - end }}}</editor-fold> */

// -- OGP-10691 | set of languages visible under langauge selections
// -- currently applied under all affiliate pages
$config['visible_options_under_language_selection'] = [1,2,3,4,5,6];

$config['pushover_send_api_url']='https://api.pushover.net/1/messages.json';
$config['pushover_default_priority']=1; //high
$config['pushover_user']=null;
$config['pushover_token']=null;
$config['monitor_event_timeout_seconds']=120;
$config['hb_common_apis'] = [HB_API,HB_IDR1_API,HB_IDR2_API,HB_IDR3_API,HB_IDR4_API,HB_THB2_API,HB_VND2_API,HB_CNY2_API,HB_MYR2_API,HB_THB1_API,HB_VND1_API,HB_CNY1_API,HB_MYR1_API,HB_IDR5_API,HB_IDR6_API,HB_IDR7_API,HB_VND3_API];
$config['exists_incomplete_game_api_list']=[HB_IDR1_API,HB_IDR2_API,HB_IDR3_API,HB_IDR4_API,HB_THB2_API,HB_VND2_API,HB_CNY2_API,HB_MYR2_API,HB_THB1_API,HB_VND1_API,HB_CNY1_API,HB_MYR1_API,HB_IDR5_API,HB_IDR6_API,HB_IDR7_API,
    PRAGMATICPLAY_API, PRAGMATICPLAY_SEAMLESS_API, PRAGMATICPLAY_IDR1_API, PRAGMATICPLAY_IDR2_API, PRAGMATICPLAY_IDR3_API,
    PRAGMATICPLAY_IDR4_API, PRAGMATICPLAY_IDR5_API, PRAGMATICPLAY_IDR6_API, PRAGMATICPLAY_IDR7_API, PRAGMATICPLAY_IDR8_API,
    PRAGMATICPLAY_IDR9_API, PRAGMATICPLAY_IDR10_API, PRAGMATICPLAY_VND1_API, PRAGMATICPLAY_VND2_API, PRAGMATICPLAY_VND3_API,
    PRAGMATICPLAY_THB1_API, PRAGMATICPLAY_THB2_API, PRAGMATICPLAY_CNY1_API, PRAGMATICPLAY_CNY2_API, PRAGMATICPLAY_MYR1_API,
    PRAGMATICPLAY_MYR2_API, PRAGMATICPLAY_LIVEDEALER_IDR1_API, PRAGMATICPLAY_LIVEDEALER_CNY1_API, PRAGMATICPLAY_LIVEDEALER_THB1_API,
    PRAGMATICPLAY_LIVEDEALER_MYR1_API, PRAGMATICPLAY_LIVEDEALER_VND1_API,PRAGMATICPLAY_LIVEDEALER_USD1_API, PRAGMATICPLAY_LIVEDEALER_SEAMLESS_IDR1_API,
    PRAGMATICPLAY_LIVEDEALER_SEAMLESS_CNY1_API,PRAGMATICPLAY_LIVEDEALER_SEAMLESS_THB1_API, PRAGMATICPLAY_LIVEDEALER_SEAMLESS_USD1_API,
    PRAGMATICPLAY_LIVEDEALER_SEAMLESS_VND1_API, PRAGMATICPLAY_LIVEDEALER_SEAMLESS_MYR1_API, PRAGMATICPLAY_SEAMLESS_IDR1_API, PRAGMATICPLAY_SEAMLESS_IDR2_API,
    PRAGMATICPLAY_SEAMLESS_IDR3_API, PRAGMATICPLAY_SEAMLESS_IDR4_API, PRAGMATICPLAY_SEAMLESS_IDR5_API, PRAGMATICPLAY_SEAMLESS_CNY1_API, PRAGMATICPLAY_SEAMLESS_CNY2_API,
    PRAGMATICPLAY_SEAMLESS_CNY3_API, PRAGMATICPLAY_SEAMLESS_CNY4_API, PRAGMATICPLAY_SEAMLESS_CNY5_API, PRAGMATICPLAY_SEAMLESS_THB1_API, PRAGMATICPLAY_SEAMLESS_THB2_API,
    PRAGMATICPLAY_SEAMLESS_THB3_API, PRAGMATICPLAY_SEAMLESS_THB4_API, PRAGMATICPLAY_SEAMLESS_THB5_API, PRAGMATICPLAY_SEAMLESS_USD1_API, PRAGMATICPLAY_SEAMLESS_USD2_API,
    PRAGMATICPLAY_SEAMLESS_USD3_API, PRAGMATICPLAY_SEAMLESS_USD4_API, PRAGMATICPLAY_SEAMLESS_USD5_API, PRAGMATICPLAY_SEAMLESS_VND1_API, PRAGMATICPLAY_SEAMLESS_VND2_API,
    PRAGMATICPLAY_SEAMLESS_VND3_API, PRAGMATICPLAY_SEAMLESS_VND4_API, PRAGMATICPLAY_SEAMLESS_VND5_API, PRAGMATICPLAY_SEAMLESS_MYR1_API, PRAGMATICPLAY_SEAMLESS_MYR2_API,
    PRAGMATICPLAY_SEAMLESS_MYR3_API, PRAGMATICPLAY_SEAMLESS_MYR4_API, PRAGMATICPLAY_SEAMLESS_MYR5_API,PRAGMATICPLAY_SEAMLESS_STREAMER_API
];
$config['isb_common_apis'] = [ISB_IDR1_API,ISB_IDR2_API,ISB_IDR3_API,ISB_IDR4_API,
							  ISB_IDR5_API,ISB_IDR6_API,ISB_IDR7_API,
							  ISB_THB1_API,ISB_THB2_API,ISB_THB3_API,ISB_THB4_API,
							  ISB_VND1_API,ISB_VND2_API,ISB_VND3_API,ISB_VND4_API,ISB_VND5_API,
							  ISB_CNY1_API,ISB_CNY2_API,ISB_CNY3_API,ISB_CNY4_API,
							  ISB_MYR1_API,ISB_MYR2_API,ISB_MYR3_API,ISB_MYR4_API,ISB_INR1_API,
							 ];
$config['pp_common_apis'] = [
    PRAGMATICPLAY_API, PRAGMATICPLAY_SEAMLESS_API, PRAGMATICPLAY_IDR1_API, PRAGMATICPLAY_IDR2_API, PRAGMATICPLAY_IDR3_API,
    PRAGMATICPLAY_IDR4_API, PRAGMATICPLAY_IDR5_API, PRAGMATICPLAY_IDR6_API, PRAGMATICPLAY_IDR7_API, PRAGMATICPLAY_IDR8_API,
    PRAGMATICPLAY_IDR9_API, PRAGMATICPLAY_IDR10_API, PRAGMATICPLAY_VND1_API, PRAGMATICPLAY_VND2_API, PRAGMATICPLAY_VND3_API,
    PRAGMATICPLAY_THB1_API, PRAGMATICPLAY_THB2_API, PRAGMATICPLAY_CNY1_API, PRAGMATICPLAY_CNY2_API, PRAGMATICPLAY_MYR1_API,
    PRAGMATICPLAY_MYR2_API, PRAGMATICPLAY_LIVEDEALER_IDR1_API, PRAGMATICPLAY_LIVEDEALER_CNY1_API, PRAGMATICPLAY_LIVEDEALER_THB1_API,
    PRAGMATICPLAY_LIVEDEALER_MYR1_API, PRAGMATICPLAY_LIVEDEALER_VND1_API,PRAGMATICPLAY_LIVEDEALER_USD1_API, PRAGMATICPLAY_LIVEDEALER_SEAMLESS_IDR1_API,
    PRAGMATICPLAY_LIVEDEALER_SEAMLESS_CNY1_API,PRAGMATICPLAY_LIVEDEALER_SEAMLESS_THB1_API, PRAGMATICPLAY_LIVEDEALER_SEAMLESS_USD1_API,
    PRAGMATICPLAY_LIVEDEALER_SEAMLESS_VND1_API, PRAGMATICPLAY_LIVEDEALER_SEAMLESS_MYR1_API, PRAGMATICPLAY_SEAMLESS_IDR1_API, PRAGMATICPLAY_SEAMLESS_IDR2_API,
    PRAGMATICPLAY_SEAMLESS_IDR3_API, PRAGMATICPLAY_SEAMLESS_IDR4_API, PRAGMATICPLAY_SEAMLESS_IDR5_API, PRAGMATICPLAY_SEAMLESS_CNY1_API, PRAGMATICPLAY_SEAMLESS_CNY2_API,
    PRAGMATICPLAY_SEAMLESS_CNY3_API, PRAGMATICPLAY_SEAMLESS_CNY4_API, PRAGMATICPLAY_SEAMLESS_CNY5_API, PRAGMATICPLAY_SEAMLESS_THB1_API, PRAGMATICPLAY_SEAMLESS_THB2_API,
    PRAGMATICPLAY_SEAMLESS_THB3_API, PRAGMATICPLAY_SEAMLESS_THB4_API, PRAGMATICPLAY_SEAMLESS_THB5_API, PRAGMATICPLAY_SEAMLESS_USD1_API, PRAGMATICPLAY_SEAMLESS_USD2_API,
    PRAGMATICPLAY_SEAMLESS_USD3_API, PRAGMATICPLAY_SEAMLESS_USD4_API, PRAGMATICPLAY_SEAMLESS_USD5_API, PRAGMATICPLAY_SEAMLESS_VND1_API, PRAGMATICPLAY_SEAMLESS_VND2_API,
    PRAGMATICPLAY_SEAMLESS_VND3_API, PRAGMATICPLAY_SEAMLESS_VND4_API, PRAGMATICPLAY_SEAMLESS_VND5_API, PRAGMATICPLAY_SEAMLESS_MYR1_API, PRAGMATICPLAY_SEAMLESS_MYR2_API,
    PRAGMATICPLAY_SEAMLESS_MYR3_API, PRAGMATICPLAY_SEAMLESS_MYR4_API, PRAGMATICPLAY_SEAMLESS_MYR5_API,PRAGMATICPLAY_SEAMLESS_STREAMER_API
];

$config['tracking_script_with_domain'] = [
    'default' => [
        'player' => [
            'gtm' => [
                'header' => null,
                'footer' => null,
            ],
            'ga' => null
        ],
        'player_mobile' => [
            'gtm' => [
                'header' => null,
                'footer' => null,
            ],
            'ga' => null
        ],
        'aff' => [
            'gtm' => [
                'header' => null,
                'footer' => null,
            ],
            'ga' => null
        ],
        'agency' => [
            'gtm' => [
                'header' => null,
                'footer' => null,
            ],
            'ga' => null
        ],
    ]
];

//testing_url,merchant_code,merchant_prefix,add_prefix_to_player,secure_key,sign_key,currency
$config['gamegateway_testing_merchant']=[];
$config['gamegateway_testing_merchant_group']=[];

//max is 5m
$config['max_allow_response_content']=5*1024*1024;
$config['trim_game_name_on_gamelist_api'] = false;
$config['enable_hints_for_sexycasino_on_deposit_upload_file'] = false;

$config['enable_gamegateway_api'] = false;
$config['external_otp_api_class']='otp_api_google_auth';
//max_compatibility or min_compatibility,
//min_compatibility=check every ip in white ip list from x-forwarded-for
//max_compatibility=only check last ip from x-forwarded-for
$config['admin_white_ip_list_mode']='min_compatibility';
//proxy or cloudflare
$config['default_cdn_ip_list']=[
    //cloudflare
    '173.245.48.0/20', '103.21.244.0/22', '103.22.200.0/22', '103.31.4.0/22', '141.101.64.0/18',
    '108.162.192.0/18', '190.93.240.0/20', '188.114.96.0/20', '197.234.240.0/22', '198.41.128.0/17',
    '162.158.0.0/15', '104.16.0.0/13', '104.24.0.0/14', '172.64.0.0/13', '131.0.72.0/22',
	// google cdn
	'34.149.252.165', '34.120.80.9', '34.149.194.16', '35.190.53.178', '34.102.250.64',
	'8.34.208.0/20', '8.35.192.0/21', '8.35.200.0/23', '23.236.48.0/20', '23.251.128.0/19',
    '34.64.0.0/11', '34.96.0.0/14', '34.100.0.0/16', '34.102.0.0/15', '34.104.0.0/14', '34.124.0.0/18',
	'34.124.64.0/20', '34.124.80.0/23', '34.124.84.0/22', '34.124.88.0/23', '34.124.92.0/22',
	'34.125.0.0/16', '35.184.0.0/14', '35.188.0.0/15', '35.190.0.0/17', '35.190.128.0/18',
	'35.190.192.0/19', '35.190.224.0/20', '35.190.240.0/22', '35.192.0.0/14', '35.196.0.0/15',
	'35.198.0.0/16', '35.199.0.0/17', '35.199.128.0/18', '35.200.0.0/13', '35.208.0.0/13', '35.216.0.0/15',
    '35.219.192.0/24', '35.220.0.0/14', '35.224.0.0/13', '35.232.0.0/15', '35.234.0.0/16',
    '35.235.0.0/17', '35.235.192.0/20', '35.235.216.0/21', '35.235.224.0/20', '35.236.0.0/14',
    '35.240.0.0/13', '104.154.0.0/15', '104.196.0.0/14', '107.167.160.0/19', '107.178.192.0/18',
    '108.170.192.0/20', '108.170.208.0/21', '108.170.216.0/22', '108.170.220.0/23', '108.170.222.0/24',
    '108.59.80.0/20', '130.211.128.0/17', '130.211.16.0/20', '130.211.32.0/19', '130.211.4.0/22',
    '130.211.64.0/18', '130.211.8.0/21', '146.148.16.0/20', '146.148.2.0/23', '146.148.32.0/19',
    '146.148.4.0/22', '146.148.64.0/18', '146.148.8.0/21', '162.216.148.0/22', '162.222.176.0/21',
    '173.255.112.0/20', '192.158.28.0/22', '199.192.112.0/22', '199.223.232.0/22', '199.223.236.0/23',
    '208.68.108.0/23',
];

$config['default_proxy_ip_list']=[
//t1tazureproxy
'40.83.74.128', '52.229.200.53',
//t1tazuresgproxy
'20.205.234.204', '13.67.64.103',
//kgvipenproxysql
'35.189.109.164',
//kgvipenproxysql2
'35.189.76.122',
//t1thkproxy
'35.241.113.87',
//t1ttwproxy
'104.199.226.113',
//lotteryhkproxy
'34.96.251.60',
//smashbrproxy-1
'35.198.3.155',
//smashbrproxy-2
'35.247.228.218',
//smashbrproxy-2
'34.95.192.97',
//t1tinproxy
'34.93.187.136',
//t1tbrproxy
'34.95.140.181',
//singaporeproxy
'35.198.200.57',
//awsproxy
'16.163.53.0',
//t1tjpproxy
'13.112.224.75',
//t1tproxy
'35.194.204.126',
//t1tlotterybrproxy
'34.95.189.185',
];

$config['default_white_ip_list']=array_merge($config['default_proxy_ip_list'],$config['default_cdn_ip_list']);

$config['enable_white_ip_checker']=true;
// ss ip , treat as real ip
$config['ss_ip_list'] = [
    '13.112.224.75', '119.9.106.90',
];

$config['player_white_ip_list_mode']='min_compatibility';
$config['enabled_white_ip_on_login']=false;

$config['retain_vip_grade_report_failed_data_for_specific_days'] = 7;

$config['max_number_of_account_on_tier_setting'] = 48;

$config['enable_sync_afterbalance_for_gameplatform'] = [];

$config['kingrich_scheduler_status'] = [
	        '1' => [
		            'label' => 'Pending'
		        ],
	        '2' => [
		            'label' => 'On-going'
		        ],
	        '3' => [
		            'label' => 'Paused'
		        ],
	        '4' => [
		            'label' => 'Stopped'
		        ],
	        '5' => [
		            'label' => 'Done'
		        ],
];
$config['use_new_china_ip_lib']=true;
$config['lock_when_create_player_account_on_gamegateway_api']=false;

$config['disable_player_register_and_redirect_to_login']=false;
$config['allow_to_create_or_update_game_api_by_api']=false;
$config['update_total_deposit_when_create_transactions']=true;
$config['record_balance_history_when_create_transactions']=true;
$config['peronal_information_custom_key_code'] = array(48, 49, 50, 51, 52, 53, 54, 55, 56, 57, 58, 187, 189); //0~9,-,+
$config['allow_to_create_or_update_agent_by_api']=false;
$config['lock_rebuild_reports_range']= [/*'no'=>45, 'time_unit' => 'days|months'*/];
$config['allow_to_update_game_list_by_api']=false;
$config['game_allowed_multiple_login'] = [ONEWORKS_API,PT_API];

$config['enabled_service_status_checker']=false;
$config['print_insert_update_row_for_debug_merge_logs']=false;
$config['enabled_dynamic_class']=true;
$config['enabled_cache_dynamic_class']=true;
$config['password_reset_code_expire_mins'] = 30;
$config['verify_link_expire_mins'] = 720;

$config['table_export_sql_and_deletions_settings'] = [
	// 'client'=>'GW001_table_export_sql_deletions',
	// 'client_url' => 'http://admin.idngame.t1t.in',
	// 'mattermost_key'=>'testnotif',
	// 'sql_back_up_path' => '/backup_db/idngame_export_sqls' // if it is customize | or null
	// 'settings'=>[ 						   // array of tables can be used but it will be queued
	// 	'game_logs'=> [                        // target table to back up delete
	// 		'dry_run'=>false,			       // will run export sql but it will not delete
	// 		'target_db_keys' => ['idr'],       // if multi db if not leave it empty
	// 		'date_field' => 'end_at',          // target date field - table datefield basis
	// 		'number_range' => 45,              // number where you can delete depends on delete_by
	// 		'delete_by'=> 'by_days' ,          // depends on number_range ex. by_days or by_months
	// 		'delete_step'=> '1 minutes',       // steps in deleting ex. 1 minutes |  1 hours | 1 days
	// 		'delete_data_after_backup'=>true,  // if  want to delete at the same time w/ export sql | it is like dry_run
	// 		'only_start_at_max_day' => false,  // if want to start at max day or next 2nd range
	// 		'overwrite_file' => true,          // if want to overrite exported file but if it is deleted already just set to false
	// 		'export_sleep' => 0,               // just in case sleep is needed on export
	// 		'delete_sleep' => 0,               // just in case sleep is needed on delete
	// 		'delete_last_month_dir_not_in_range' => true, //if want to delete old 2nd range -old month
	// 	],
	// ]
];

$config['table_archive_and_deletions_settings'] = [
	// 'client'=>'OLE77cn_table_archive_delete',
	// 'client_url' => 'http://admin.staging.ole777cn.t1t.in',
	// 'mattermost_key'=>'testnotif',
	// 'settings'=>[									 // array of tables can be used but it will be queued
	// 	'game_logs'=> [							     // will run export sql but it will not delete
	// 		'dry_run'=>false,						 // target table to back up delete
	// 		'target_db_keys' => [],					 // if multi db if not leave it empty
	// 		'date_field' => 'end_at', 				 // target date field - table datefield basis
	// 		'number_range' => 3,                     // number where you can delete depends on delete_by
	// 		'save_to_table' => 'game_logs' ,         // archive table name ####_yyyymm
	// 		'delete_by'=> 'by_months' ,              // depends on number_range ex. by_days or by_months
	// 		'delete_step'=> '1 hours',               // steps in deleting ex. 1 minutes |  1 hours | 1 days
	// 		'archive_sleep' => 0,                    // just in case sleep is needed on archive
	// 		'delete_sleep' => 0,                     // just in case sleep is needed on delete
	// 		'only_start_at_max_day' => true,         // if want to start at max day or next 2nd range
	// 		'delete_data_after_backup'=>false,      // if  want to delete at the same time w/ export sql | it is like dry_run
	// 		'drop_last_month_archive_table_not_in_range'=>true,  //if want to delete old 2nd range -old month
 //         'use_index_str' =>  'USE INDEX (IDX_END_AT)' //  if want to use index when selecting before archiving or just set to null
 //        ],
 //    ]
];

#OGP-16006 if enable this config , can set player center register default Dialing Code
$config['enable_default_dialing_code'] = [
	// "country" => "nums",
];

$config['enable_verify_phone_number_in_account_information_of_player_center'] = false;

$config['use_new_sbe_color']=true;

$config['max_agency_readonly_account']=10;

$config['COMMON_KEY_FOR_PASSWORD'] = null; // to config_local
$config['payment_api_key'] = null;


$config['limit_the_number_of_words_displayed'] = '50'; #payment notes RFE-3060

$config['allow_lobby_in_provider'] = [];

$config['redis_sentinel_list'] = [];
$config['default_mastername_of_redis_sentinel'] ='defaultmaster';
$config['default_master_redis'] = null;
$config['default_redis_for_id_generator'] = null;

//ci_memcached or redis
$config['cache_driver_type']='ci_memcached';
$config['default_redis_expire_time']=360000;

$config['enabled_explain_function_for_query_stream']=false;
$config['max_limit_of_query_stream']=500000;
$config['max_minutes_limit_for_query_stream']=60;

$config['go_player_login_if_not_login_when_launch_game'] = [];
$config['enabled_unbuffered_mysql_query']=true;
$config['golden_race_multiple_currency_domain_mapping']=[];

// 12 hours
$config['force_timeout_of_session_redis']=43200;
//use redis to generate game logs.id
$config['enabled_generate_game_logs_id_from_redis']=false;
$config['enabled_generate_game_logs_unsettled_id_from_redis']=false;
$config['disable_last_transaction_list_on_agent_info_page']=false;

$config['remove_agent_prefix_on_all_game_account']=false;

$config['delete_player_info_settings'] = [

	// 'game_logs_group' => [

	// 	'idr' => [

	// 		'game_logs'=>[
	// 			'player_id_field'=> 'player_id',
	// 			'index_id'=> 'id',
	// 			'has_multi_rows'=>true,
	// 			'sql_delete_limit'=> 1000,
	// 			'save_info_to_csv'=>false,
	// 			'game_logs_originals' => [
	// 				'haba88_idr7_game_logs',
	// 				'mgplus_idr7_game_logs',
	// 				'isb_idr1_game_logs',
	// 	],//idr

	// ],

	// 'balance_history'=>[
	// 	'player_id_field'=> 'player_id',
	// 	'index_id'=> 'id',
	// 	'sql_delete_limit'=> 500,
	// ],
	// 	'player'=>[
// 		'player_id_field'=> 'playerId',
// 		'index_id'=> 'playerId',
// 		'save_info_to_csv'=>true,
// 		'sql_delete_limit'=> 500,
// 	],

];


$config['delete_agent_info_settings'] = [

	//transactions
	// 'transactions_from_type'=>[
	// 	'agent_id_field'=> 'id',
	// 	'index_id'=> 'id',
	// 	'sql_delete_limit'=> 500,
	// 	'save_info_to_csv'=>false,
	// ],
	// 'transactions_to_type'=>[
	// 	'agent_id_field'=> 'id',
	// 	'index_id'=> 'id',
	// 	'sql_delete_limit'=> 500,
	// 	'save_info_to_csv'=>false,
	// ],
	// 'transfer_request'=>[
	// 	'agent_id_field'=> 'agent_id',
	// 	'index_id'=> 'id',
	// 	'sql_delete_limit'=> 500,
	// 	'save_info_to_csv'=> false,
	// ],
	// 'agency_agents'=>[
	// 	'agent_id_field'=> 'agent_id',
	// 	'index_id'=> 'agent_id',
	// 	'sql_delete_limit'=> 500,
	// 	'save_info_to_csv'=> true,
	// ],

];

//for player center api
$config['player_center_api_X-DEBUG-SIGN-KEY']=null;
$config['disable_validate_contact_number_display'] = false ;
$config['goWebsiteHomeAfterRegister'] = false;

# add the method & input name you want to skip the xss clean filter
# 'class/method' => ['key_name', 'key_name2'] OR 'class/method' => 'ALL' if you want to skip all input
$config['skip_xss_filter_list']=[
	'cms_management/saveStaticSites' => ['login_template', 'logged_template', 'player_center_css', 'admin_css', 'aff_css'],
	'cms_management/save_custom_script' => ['taCustomScript'],
	'marketing_management/preparePromo' => ['formula_bonus_condition', 'formula_bonus_release', 'formula_withdraw_condition', 'formula_transfer_condition'],
	'payment_api/updatePaymentApi' => ['extra_info','sandbox_extra_info'],
];

$config['player_center_api_cool_down_time'] = [];
// $config['player_center_api_cool_down_time'][] = [
//     'class' => 'player_center',
//     'method' => 'manualWithdraw',
//     'cool_down_sec' => 5,
//     // 'force_use' => 'database', default as redis, if Not work, that will be database
// ];

/// OGP-27441
$config['api_key_player_center_public_key_in_smash_promo_auth'] = 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDr+4gbK2USVgETE3Rd00zWhoym9pqYTZ2KZYWmI4Wp+vjgCjNZq161KlsfX7qeAZLqHEi349rT9SPUV8rVVFuOHHndOH7it+dEWilKHUPS82UZb9CbU5xoM8R4QNabOX6S2t1Kd2Rode663U1yvBkO6OLWvyiUoSkHCoK7XOaf9QIDAQAB';
$config['player_center_api_ttl_in_smash_promo'] = 900; // 15 min
/// About api, bet_info
// T1 Lottery Seamless(5928) > Lottery > Crash : 10748, game_type_id= 1054
// T1 Lottery Seamless(5928) > Table Games > Dice : 11480, game_type_id= 1093
// T1 Lottery Seamless(5928)	> Lottery > Double : 10749, game_type_id= 1054
// BISTRO SEAMLESS(6012) > Lottery > Mines : 12742, game_type_id= 1124
// BISTRO SEAMLESS(6012) > Others > Cryptos : 12939, game_type_id= 1128
// TRUCO(6036) > Card Games > Truco : 13004, game_type_id= 1131
// If its empty then no game assign in query.
$config['player_center_api_bet_info_game_list'] = [];
// game_platform_id => [ game_code list ]
$config['player_center_api_bet_info_game_list'][5928] = ['crash', 'dice', 'double'];
$config['player_center_api_bet_info_game_list'][6012] = [60, 61];
$config['player_center_api_bet_info_game_list'][6036] = ['truco'];
// If its emoty then all game type in query
$config['player_center_api_bet_info_game_type_list'] = [];
// game_type.game_type_code string for single. ex: 'live_dealer'.
$config['player_center_api_bet_info_game_type_list'] =  ['sports', 'slots', 'live_dealer'];
// OGP-27441
$config['event_url_list_by_ogp_27441'] = 'https://smashup.work/worldCup/jumpUrl?token={TOKEN}';
$config['player_center_api_bet_info_game_no_mapping'] = [];
// game_code
$config['player_center_api_bet_info_game_no_mapping']['crash'] = 1; // Crash
$config['player_center_api_bet_info_game_no_mapping']['double'] = 2; // Double
$config['player_center_api_bet_info_game_no_mapping']['dice'] = 3; // Dice
$config['player_center_api_bet_info_game_no_mapping']['60'] = 4; // Mines
$config['player_center_api_bet_info_game_no_mapping']['61'] = 5; // Hi-Lo aka. Cryptos
$config['player_center_api_bet_info_game_no_mapping']['truco'] = 6; // Truco
// game_type_code
$config['player_center_api_bet_info_game_no_mapping']['slots'] = 7; // Slots
$config['player_center_api_bet_info_game_no_mapping']['live_dealer'] = 8; // Live Casino
$config['player_center_api_bet_info_game_no_mapping']['sports'] = 9; // Sports

//10 times per hour
$config['ip_limit_hourly']=['register'=>10];

//player center internal key
$config['internal_player_center_api_key']='42f77fd0409566c6123d58551e85ee87';
$config['print_debug_on_assert_function']=false;
$config['disabled_sync_game_logs_on_sbe']=false;
$config['disabled_manually_sync_game_logs']=false;

//OGP-23827 for player center hide/show of player username
$config['enable_hide_show_username_player_center'] = false ;

// OGP-15871: Disable 'manual update admin dashboard' in dev functions by default
$config['enable_dev_func_manual_update_admin_dashboard'] = false;
// OGP-16017
$config['enable_contact_number_custom_display'] = false;
// OGP-19208 Enable show only active game tags
$config['show_active_game_types_in_dashboard'] = false;

/**
 * ['xxx.xxx.xxx.xxx']
 */
$config['backend_api_white_ip_list']=[];
//ignore sign if set this key
$config['backend_api_X-DEBUG-SIGN-KEY']=null;
$config['player_register_item_sequence_mobile'] = [
    'USERNAME',
    'PASSWORD',
    'EMAIL',
    'REGISTRATION_FIELDS',
    'COMMUNICATION_PREFERENCES',
    'REGISTER_BTN',
    'TERMS_AND_CONDITIONS',
    'HAVE_ACCOUNT',
    'LIVE_CHAT',
];

$config['player_register_item_sequence_web'] = [
    'USERNAME',
    'PASSWORD',
    'EMAIL',
    'REGISTRATION_FIELDS',
    'TERMS_AND_CONDITIONS',
    'COMMUNICATION_PREFERENCES',
    'REGISTER_BTN',
    'LOGIN_LINK',
];

$config['login_page_show_register_link'] = true; // OGP-28068
$config['registration_mod_has_toggle_password'] = false;
$config['enable_customized_register_template'] = true; // for switch to register_mobile4XXX, ex: register_mobile4igmbet
$config['registration_mod_prepend_html_list'] = [];
/// disabled, thats added in STG.
// $config['registration_mod_prepend_html_list']['register_mobile4sexycasino_line'] =
// $config['registration_mod_prepend_html_list']['register_mobile'] = true; // embedded in the templates, register_mobile4igmbet AND register_recommended4igmbet.
// $config['registration_mod_prepend_html_list']['register_recommended4sexycasino_line'] =
// $config['registration_mod_prepend_html_list']['register_template_4'] =
// $config['registration_mod_prepend_html_list']['register_recommended'] = true; // embedded in the templates, register_mobile4igmbet AND register_recommended4igmbet.
$config['registration_mod_register_btn_list'] = [];
/// disabled, thats added in STG.
// $_index = 0;
// // $config['registration_mod_register_btn_list'] = []; /// disabled by defined in common
// $config['registration_mod_register_btn_list'][$_index]['wrapper_class'] = 'col-md-4';
// $config['registration_mod_register_btn_list'][$_index]['btn_type'] = 'button';
// $config['registration_mod_register_btn_list'][$_index]['btn_class'] = 'btn btn-link';
// // $config['registration_mod_register_btn_list'][$_index]['btn_lang'] = null;
// $config['registration_mod_register_btn_list'][$_index]['btn_case_str'] = 'HOME_BTN';
// $_index++; // # 1
// $config['registration_mod_register_btn_list'][$_index]['wrapper_class'] = 'col-md-4';
// $config['registration_mod_register_btn_list'][$_index]['btn_type'] = 'button';
// $config['registration_mod_register_btn_list'][$_index]['btn_class'] = 'btn btn-outline';
// // $config['registration_mod_register_btn_list'][$_index]['btn_lang'] = null;
// $config['registration_mod_register_btn_list'][$_index]['btn_case_str'] = 'SIGN_IN_BTN';
// $_index++; // # 2
// $config['registration_mod_register_btn_list'][$_index]['wrapper_class'] = 'col-md-4';
// $config['registration_mod_register_btn_list'][$_index]['btn_type'] = 'submit';
// $config['registration_mod_register_btn_list'][$_index]['btn_class'] = 'btn btn-primary';
// // $config['registration_mod_register_btn_list'][$_index]['btn_lang'] = null;
// $config['registration_mod_register_btn_list'][$_index]['btn_case_str'] = 'REGISTERING_BTN';
$config['registration_mod_has_toggle_password'] = false;
// OGP-27932
$config['frm_login_login_now_btn_list'] = [];

#OGP-27759
$config['enable_social_media'] = false;
$config['enable_forget_password_custom_block']= false;
$config['forget_password_custom_block_content'] = '';
$config['show_reminder_message'] = false;
$config['use_old_userinformation_page'] = false;
$config['use_auto_check_withdraw_condition_when_access_userinformation'] = false;
/**
$config['block_user_agent_on_login']=[
	'black_user_agent'=>[
		'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/65.0.3314.0 Safari/537.36 SE 2.X MetaSr 1.0',
	],
	'enabled_any_domain'=>false,
	'source_domain'=>[
		'player.haoli787.com',
        'player.haoli797.com',
	],
    'default_redirect_to_url'=>'https://www.haoli787.com/login_issue.html',
	'redirect_to_url'=>[
        'player.haoli787.com'=>'https://www.haoli787.com/login_issue.html',
        'player.haoli797.com'=>'https://www.haoli797.com/login_issue.html',
    ],
];
*/
$config['block_user_agent_on_login']=null;
/**
$config['block_x-real-ip_on_login']=[
	'x-real-ip_black_list'=>[
		'49.89.204.126',
	],
	'enabled_any_domain'=>false,
	'source_domain'=>[
		'player.haoli787.com',
		'player.haoli797.com',
	],
    'default_redirect_to_url'=>'https://www.haoli787.com/login_issue.html',
	'redirect_to_url'=>[
		'player.haoli787.com'=>'https://www.haoli787.com/login_issue.html',
		'player.haoli797.com'=>'https://www.haoli797.com/login_issue.html',
	],
];
*/
$config['block_x-real-ip_on_login']=null;

$config['cache_app_prefix_on_utils']=true;

//? this config disables transfer wallet modules and functions
$config['seamless_main_wallet_reference_enabled'] = false;

//? this config shows certain transfer module pages even while seamless_main_wallet_reference_enabled is set to true
$config['still_enabled_transfer_list_on_seamless_wallet'] = false;

$config['telesale_api_list'] = [];
$config['gamegateway_delete_all_player_token_when_kicked'] = false;

$config['comapi_get_player_reports_query_limit'] = 50;
$config['comapi_frontend_game_api_baseurl'] = 'http://admin.gamegateway.t1t.in/game_description/getFrontendGames';
$config['comapi_enable_force_verified_phone_in_add_player_usdt_account'] = false;

$config['onlyAllowPositiveAmountInPlayerWallet'] = true;

$config['enable_processed_on_custom_stage_time'] = false;

//update sub-wallet directly by game api, don't enable it on live site
$config['allow_to_transfer_directly_by_api']=false;
$config['check_duplicate_external_transaction_id']=false;

$config['force_index_getDWCountAllStatus'] = true;
$config['force_index_countSaleOrders'] = true;
$config['force_index_withdrawList'] = true;

$config['use_old_view_game_api_ui']=false;
$config['enable_pending_review_custom'] = false;
$config['popup_withdrawal_details_window']=false;
$config['enabled_bank_info_verified_flag_in_withdrawal_details']=false;
$config['disable_deposit_upload_file_2'] = false;

$config['idle_sec_before_withdrawalARP_pushStage'] = 0;
$config['offsetTime_batch_supplement_run_process_pre_checker_in_queue'] = '-10 minutes';

// TT-4632 Optimize player upgrade checking.
$config['do_player_filter_in_batch_player_level_upgrade_check'] = 0; // 0, 1
$config['lastLoginTime_begin_batch_player_level_upgrade_check'] = '-2 days';
$config['lastLoginTime_end_batch_player_level_upgrade_check'] = 'now';

$config['do_player_filter_in_batch_change_player_level'] = false; // 0, 1
$config['lastLoginTime_begin_batch_change_player_level'] = '-2 days';
$config['lastLoginTime_end_batch_change_player_level'] = 'now';

// OGP-17644 - if unset will be system timezone.
$config['default_timezone_option_in_agency'] = 8; // for setup +8
$config['scan_timeout_transfer_request_minutes']=10;
$config['threshold_of_transfer_timeout']=5;
$config['scan_timeout_transfer_then_go_maintenance']=false;
$config['get_timeout_transfer_request_minutes']=15;

$config['force_index_on_player_report_simple_game_daily']=true;

// crypto currency rate setting
$config['cryptorate_api_coingecko_usdt_decimal_place'] = 4;
$config['cryptorate_api_coingecko_usdt_crypto_input_decimal_place'] = 4;
$config['cryptorate_api_coingecko_usdt_cust_fix_deposit_rate'] = 1;
$config['cryptorate_api_coingecko_usdt_cust_fix_withdrawal_rate'] = 1;
$config['cryptorate_api_coingecko_usdt_update_timing'] = 30 * 60; //sec
$config['cryptorate_api_coingecko_usdt_allow_compare_digital'] = 0;

$config['cryptorate_api_coingecko_eth_decimal_place'] = 8;
$config['cryptorate_api_coingecko_eth_crypto_input_decimal_place'] = 8;
$config['cryptorate_api_coingecko_eth_cust_fix_deposit_rate'] = 1;
$config['cryptorate_api_coingecko_eth_cust_fix_withdrawal_rate'] = 1;
$config['cryptorate_api_coingecko_eth_update_timing'] = 40 * 60; //min * sec 單位 秒
$config['cryptorate_api_coingecko_eth_allow_compare_digital'] = 0;

$config['cryptorate_api_coingecko_btc_decimal_place'] = 8;
$config['cryptorate_api_coingecko_btc_crypto_input_decimal_place'] = 8;
$config['cryptorate_api_coingecko_btc_cust_fix_deposit_rate'] = 1;
$config['cryptorate_api_coingecko_btc_cust_fix_withdrawal_rate'] = 1;
$config['cryptorate_api_coingecko_btc_update_timing'] = 30 * 60; //sec
$config['cryptorate_api_coingecko_btc_allow_compare_digital'] = 0;

$config['cryptorate_api_huobi_usdt_decimal_place'] = 4;
$config['cryptorate_api_huobi_usdt_crypto_input_decimal_place'] = 4;
$config['cryptorate_api_huobi_usdt_cust_fix_deposit_rate'] = 1;
$config['cryptorate_api_huobi_usdt_cust_fix_withdrawal_rate'] = 1;
$config['cryptorate_api_huobi_usdt_update_timing'] = 30 * 60; //sec
$config['cryptorate_api_huobi_usdt_allow_compare_digital'] = 0;

$config['cryptorate_api_btse_eth_decimal_place'] = 8;
$config['cryptorate_api_btse_eth_crypto_input_decimal_place'] = 8;
$config['cryptorate_api_btse_eth_cust_fix_deposit_rate'] = 1;
$config['cryptorate_api_btse_eth_cust_fix_withdrawal_rate'] = 1;
$config['cryptorate_api_btse_eth_update_timing'] = 40 * 60; //min * sec 單位 秒
$config['cryptorate_api_btse_eth_allow_compare_digital'] = 0;

$config['cryptorate_api_btse_btc_decimal_place'] = 8;
$config['cryptorate_api_btse_btc_crypto_input_decimal_place'] = 8;
$config['cryptorate_api_btse_btc_cust_fix_deposit_rate'] = 1;
$config['cryptorate_api_btse_btc_cust_fix_withdrawal_rate'] = 1;
$config['cryptorate_api_btse_btc_update_timing'] = 30 * 60; //sec
$config['cryptorate_api_btse_btc_allow_compare_digital'] = 0;

$config['cryptorate_api_indodax_usdt_decimal_place'] = 4;
$config['cryptorate_api_indodax_usdt_crypto_input_decimal_place'] = 4;
$config['cryptorate_api_indodax_usdt_cust_fix_deposit_rate'] = 1;
$config['cryptorate_api_indodax_usdt_cust_fix_withdrawal_rate'] = 1;
$config['cryptorate_api_indodax_usdt_update_timing'] = 30 * 60; //sec
$config['cryptorate_api_indodax_usdt_allow_compare_digital'] =0;

$config['cryptorate_api_indodax_eth_decimal_place'] = 8;
$config['cryptorate_api_indodax_eth_crypto_input_decimal_place'] = 8;
$config['cryptorate_api_indodax_eth_cust_fix_deposit_rate'] = 1;
$config['cryptorate_api_indodax_eth_cust_fix_withdrawal_rate'] = 1;
$config['cryptorate_api_indodax_eth_update_timing'] = 40 * 60; //min * sec 單位 秒
$config['cryptorate_api_indodax_eth_allow_compare_digital'] = 0;

$config['cryptorate_api_indodax_btc_decimal_place'] = 8;
$config['cryptorate_api_indodax_btc_crypto_input_decimal_place'] = 8;
$config['cryptorate_api_indodax_btc_cust_fix_deposit_rate'] = 1;
$config['cryptorate_api_indodax_btc_cust_fix_withdrawal_rate'] = 1;
$config['cryptorate_api_indodax_btc_update_timing'] = 30 * 60; //sec
$config['cryptorate_api_indodax_btc_allow_compare_digital'] = 0;

$config['cryptorate_api_coingecko_bnb_decimal_place'] = 8;
$config['cryptorate_api_coingecko_bnb_crypto_input_decimal_place'] = 8;
$config['cryptorate_api_coingecko_bnb_cust_fix_deposit_rate'] = 1;
$config['cryptorate_api_coingecko_bnb_cust_fix_withdrawal_rate'] = 1;
$config['cryptorate_api_coingecko_bnb_update_timing'] = 5; //sec
$config['cryptorate_api_coingecko_bnb_allow_compare_digital'] = 0;

$config['cryptorate_api_indodax_bnb_decimal_place'] = 4;
$config['cryptorate_api_indodax_bnb_crypto_input_decimal_place'] = 4;
$config['cryptorate_api_indodax_bnb_cust_fix_deposit_rate'] = 1;
$config['cryptorate_api_indodax_bnb_cust_fix_withdrawal_rate'] = 1;
$config['cryptorate_api_indodax_bnb_update_timing'] = 30 * 60; //sec
$config['cryptorate_api_indodax_bnb_allow_compare_digital'] = 0;

$config['cryptorate_api_okx_usdt_decimal_place'] = 4;
$config['cryptorate_api_okx_usdt_crypto_input_decimal_place'] = 4;
$config['cryptorate_api_okx_usdt_cust_fix_deposit_rate'] = 1;
$config['cryptorate_api_okx_usdt_cust_fix_withdrawal_rate'] = 1;
$config['cryptorate_api_okx_usdt_update_timing'] = 30 * 60; //sec
$config['cryptorate_api_okx_usdt_allow_compare_digital'] = 0;

$config['cryptorate_api_dummy_usdc_decimal_place'] = 4;
$config['cryptorate_api_dummy_usdc_crypto_input_decimal_place'] = 4;
$config['cryptorate_api_dummy_usdc_cust_fix_deposit_rate'] = 1;
$config['cryptorate_api_dummy_usdc_cust_fix_withdrawal_rate'] = 1;
$config['cryptorate_api_dummy_usdc_update_timing'] = 30 * 60; //sec
$config['cryptorate_api_dummy_usdc_allow_compare_digital'] = 0;

$config['crypto_to_currecny_exchange_rate'] = array('IDR' => array('per'=>1,'exchange'=>1000),
													'VND' => array('per'=>1,'exchange'=>1000)
												);
$config['force_using_fixed_usd_stablecoin_rate'] = false;
#region - external system currency setting
$config['external_system_currency_setting'] = [
    NOPAY_USDT_PAYMENT_API => CRYPTO_CURRENCY_COIN_USDT,
    NOPAY_USDC_PAYMENT_API => CRYPTO_CURRENCY_COIN_USDC,
];
#endregion - external system currency setting

// OGP-17949: set true to use cronjob-calculated aff dashboard
$config['calculate_affiliate_dashboard_by_cronjob'] = false;
$config['use_loop_on_query_total_rake']=false;
$config['mobile_vip_status_ExpBar']=false;
$config['disabled_mobile_expbar_vip_level_name']= false;

// OGP-18154: disable point setting from editVipGroupLevel page
$config['enable_beting_amount_to_point'] = false;

// OGP-21735: disable/enable auto add points on deposit
$config['enable_deposit_amount_to_point'] = false;

//OGP-25193 this config shows shop history of the player logged in
$config['enable_account_history_shop_history'] = false;

# OGP-18418 change the button in player center
$config['player_first_login_page_button_setting'] = [
	'deposit_btn' => ['lang_key' => 'Deposit immediately', 'account' => 'Account', 'account_balance' => 'Account balance'],
	'home_btn' => ['lang_key' => 'Go to the game hall'],
	'promo_btn' => ['lang_key' => 'View it now'],
];

$config['smsManagerTypeList'] = [
    1=>'cms.registered_msg',
    2=>'cms.withdrawal_msg_request',
    3=>'cms.withdrawal_msg_success',
    4=>'cms.withdrawal_msg_decline'
    ];

// OGP-18075: set cmsbanner_use_new_version = true to use new version
$config['cmsbanner_use_new_version'] = false;

// OGP-18451: Hide mobile player center logout button, default false
$config['player_center_mobile_hide_logout_button'] = false;

// OGP-18457: set true to hide deposit type titles, default value = false
$config['player_center_mobile_hide_deposit_type_titles'] = false;
$config['player_center_desktop_hide_deposit_type_titles'] = false;

$config['player_center_withdrawal_page_fee_hint'] = false;

// OGP-18666 Deposit Report to be DEFAULT & placed as FIRST report
$config['default_player_center_account_history_tab'] = 'rebate'; //default to rebate; 'rebate', 'deposit', 'withdrawal', 'transfer_request', 'promoHistory', 'game', 'referralFriend'
$config['default_player_center_account_history_tab_order'] = ['rebate', 'deposit', 'withdrawal', 'transfer_request', 'promoHistory', 'game', 'transaction', 'referralFriend', 'unsettledGame', 'shop'];
$config['custom_player_center_account_history_tab_order'] = [];
$config['hide_account_history_deposit_column'] = [];
$config['use_custom_hamburger_menu'] = false;

// OGP-19024 Auto open verify mobile number
$config['open_verify_contactnumber_when_contactnumber_unverified']=false;

//iovation
$config['iovation'] = array(
	'subscriber_id' 		=> '',
	'subscriber_account'    => '',
	'subscriber_passcode'   => '',
	'endpoint_type'    		=> '',
	'api_url'    			=> '',
	'account_prefix'    	=> '',
	'use_first_party'    	=> true,
	'first_party_js_config' => '',
	'is_api_ready' 			=> false,
	'use_logs_monthly_table' => false,
);

$config['use_view_player_settings_v2'] = false;
$config['enable_drag_drop_deposit_proof'] = false;
$config['show_referFriend_bonus'] = true;

/// OGP-21591
$config['system_feature_page_read_only'] = false;

$config['track_visit_only_once'] = true;
$config['sbe.user.realname.maxlength'] = 16;
$config['test_mode_for_mattermost_message']=false;
$config['alert_db_error_to_mm']=true;

$config['report_management_columnDefs'] = [
	"not_visible_gradereport" => [ 9, 10, 11 ],
];
$config['disabled_mobile_verification_in_security'] = false;
#25973
$config['disabled_sms_verified_btn_in_security'] = false;
$config['enable_split_player_username_and_affiliate'] = false;
$config['enable_sms_verify_in_add_crypto_bank_account'] = false;
$config['enable_sms_verify_in_add_bank_account'] = false;
$config['enable_sms_verify_in_add_ewallet'] = false;
$config['enable_cpf_number'] = false;
$config['deposit_list_customization_external_id'] = [];
$config['enable_financial_account_can_be_withdraw_and_deposit_in_usdt'] = false;
$config['datetime_of_exclude_withdraw_condition_when_calculate_cashback'] = '';
$config['allow_crypto_bank_in_disable_deposit_bank'] = false;
$config['use_confirm_on_get_promo'] = 'true';
$config['ignore_promotion_disabled'] = false;
$config['max_change_password_game_count'] = 100;

$config['promo_auto_redirect_to_deposit_page'] = false;
$config['sexycasino_no_promo_msg']=false;
$config['isb_multiple_currency_api_mapping'] = [
	'idni' => 2253,
	'idnm' => 2254,
	'idnt' => 2255,
	'idnv' => 2257
];

$config['enable_mutiple_login_notify'] = false;
$config['mutiple_login_notify_setting_override'] = [];
$config['mutiple_login_notify_setting'] = [
	'time_range' => 120,
	'notify_url' => 'https://talk.letschatchat.com/hooks/97od6ytuhfn85ciy91z4os7puw',
	'notify_user' => 'default',
	'notify_channel' => '#player-multiple-login',
];

//OGP-19329
$config['suspicious_withdrawal_settings'] = [
	'base_url' => 'http://admin.og.local',
	'adjust_minutes' => 5,
	'adjust_minutes_get_last_transfer' => 30,
	'mm_channel' => 'test_mattermost_notif',
	'cny' => [
		'multiplier' => 1000,
		'min_amount' => 0,
		'max_amount' => 1000000,
		'doubled_min_amount' => 0,
		'doubled_max_amount' => 1000000,
	],
	'thb' => [
		'multiplier' => 1000,
		'min_amount' => 0,
		'max_amount' => 1000000,
		'doubled_min_amount' => 0,
		'doubled_max_amount' => 1000000,
	],
	'idr' => [
		'multiplier' => 1000,
		'min_amount' => 0,
		'max_amount' => 1000000000,
		'doubled_min_amount' => 0,
		'doubled_max_amount' => 1000000,
	],
	'vnd' => [
		'multiplier' => 1000,
		'min_amount' => 0,
		'max_amount' => 1000000,
		'doubled_min_amount' => 0,
		'doubled_max_amount' => 1000000,
	],
	'myr' => [
		'multiplier' => 1000,
		'min_amount' => 0,
		'max_amount' => 1000000,
		'doubled_min_amount' => 0,
		'doubled_max_amount' => 1000000,
	],
];
$config['new_features_alert_settings'] = [
	// 'mm_channel' => 'test_mattermost_notif',
	// 'mm_user'=>'System Feature',
	// 'sbe_client' => 'og_onestop',
	// 'msg_per_page'=>20,
];

$config['enable_fast_track_integration'] = false;
$config['fast_track_ip_whitelist'] = [];
$config['fast_track_api_key'] = '';
$config['fast_track_server_api_key'] = '';
$config['fast_track_api_base_url'] = '';

$config['never_insert_tranfer_to_game_logs']=false;
$config['show_blocked_game_api_data_on_games_report']=false;
$config['use_simple_deposit_request_sql']=false;
$config['exclude_wc_available_bet_after_cancelled_wc'] = false;
$config['games_with_report_timezone']= [];
$config['sum_deposit_promo_bonus_as_total_deposit_bonus'] = false;
$config['sum_add_bonus_as_manual_bonus'] = false;
$config['enable_change_referral_limit_to_monthly'] = false;
$config['enabled_referred_bonus'] = false;
$config['player_fullname_rule'] = 0;
$config['ignore_ceated_transaction_and_set_status_to_paid'] = false;

$config['enable_form_validation_under_registration'] =
['username', 'password', 'cpassword', 'email', 'firstName'
, 'lastName', 'terms', 'birthdate', 'gender', 'citizenship', 'birthplace'
, 'language', 'contactNumber', 'imAccount', 'im_type', 'imAccount2'
, 'im_type2', 'imAccount3', 'im_type3',  'imAccount4', 'im_type4','secretQuestion', 'secretAnswer'
, 'residentCountry' , 'invitationCode', 'tracking_code', 'withdrawPassword', 'affiliate_name'
, 'bankName', 'bankAccountNumber', 'bankAccountName', 'region', 'city'
, 'address', 'address2', 'zipcode', 'id_card_number', 'dialing_code'
, 'id_card_type', 'sms_verification_code', 'captcha', 'newsletter_subscription','imAccount5', 'im_type5'
, 'issuingLocation', 'issuanceDate', 'middleName', 'maternalName', 'isPEP', 'acceptCommunications'
, 'player_preference_email', 'player_preference_sms', 'player_preference_phone_call', 'player_preference_post'
];

$config['enable_form_required_under_registration'] = ['Contact Number', 'SMS Verification Code'];

// if enable cpassword and Must add "password" too.
// if enable terms and Must add "birthdate" too.
// check the setting,"registration_captcha_enabled" in configure.

$config['batch_player_level_upgrade'] = [];
$config['batch_player_level_upgrade']['maxWaitingTimes'] = 40; // Will give up after checked times.
$config['batch_player_level_upgrade']['waitingSec'] = 60; // Check PS by interval time.
$config['batch_player_level_upgrade']['idleSec'] = 0; // for simulate executing (debug/test).
$config['batch_player_level_upgrade']['BLAidleSec'] = 0; // BLA = playerLevelAdjust

/// for simulate executing (debug/test).
// default is 0, setup 60 for test.
$config['batch_player_level_upgrade']['BUDLUintervalSec'] = 0; // BUDLU = batchUpDownLevelUpgrade
$config['batch_player_level_upgrade_check_hourly'] = [];
$config['batch_player_level_upgrade_check_hourly']['maxWaitingTimes'] = 40; // Will give up after checked times.
$config['batch_player_level_upgrade_check_hourly']['waitingSec'] = 60; // Check PS by interval time.
$config['batch_player_level_upgrade_check_hourly']['idleSec'] = 0; // for simulate executing (debug/test).
$config['batch_player_level_downgrade'] = [];
$config['batch_player_level_downgrade']['maxWaitingTimes'] = 40; // Will give up after checked times.
$config['batch_player_level_downgrade']['waitingSec'] = 60; // Check PS by interval time.
$config['batch_player_level_downgrade']['idleSec'] = 0; // for simulate executing (debug/test).
$config['batch_player_level_downgrade']['BLADidleSec'] = 0; // BLAD = playerLevelAdjustDowngrade
/// for simulate executing (debug/test).
// default is 0, setup 60 for test.
$config['batch_player_level_downgrade']['BUDLDintervalSec'] = 0; // BUDLD = batchUpDownLevelDowngrade
$config['batch_player_level_downgrade']['filterLowestLevels'] = false;
$config['batch_player_level_downgrade']['isLastGradeEq2PlayerLevelId'] = null;
$config['batch_player_level_downgrade']['immediate4lastGradeRecordRow'] = false; // If false, it means the begin moment of cronjob, if true, it means the current moment.

/// OGP-31183
// as default 20000, its means to delay 0.02s per retry
$config['interval_retryScript_readBySessionIdFromFile'] = 20000;
// as 99, it will take 2s(, 0.02s x (99+1) ) to retry in worst case.
$config['maxRetryCount_retryScript_readBySessionIdFromFile'] = 99;
// as default 20000, its means to delay 0.02s per retry
$config['interval_retryScript_writeToFile'] = 20000;
// as Zero, it will take 0.02s(, 0.02s x (0+1) ) to retry in worst case.
$config['maxRetryCount_retryScript_writeToFile'] = 0;// Zero means No retry time,  just operate once.
// The number of retries will be empty within N times.
$config['emptyInRetryTime_retryScript_readBySessionIdFromFile'] = 0;  // Zero as disabled.
$config['enabled_lockSessFileResource_in_readBySessionIdFromFile'] = false;
$config['prefixMode_lockSessFileResource_in_readBySessionIdFromFile'] = 'common'; // common, read
//
$config['emptyInRetryTime_retryScript_writeToFile'] = 0;  // Zero as disabled.
$config['enabled_lockSessFileResource_in_writeToFile'] = false;
$config['prefixMode_lockSessFileResource_in_writeToFile'] = 'common'; // common, write
$config['waitSec_exceptionHandlerInMaxRetry'] = 3; // wait 3 sec and then reload
$config['URIs_ignoreShowAlertOfAjaxReplayWithDelay'] = [];
$config['URIs_ignoreShowAlertOfAjaxReplayWithDelay'][] = '/api/transaction_request_notification';
$config['URIs_ignoreShowAlertOfAjaxReplayWithDelay'][] = '/api/notified_at';
// $config['URIs_ignoreShowAlertOfAjaxReplayWithDelay'][] = '/player_management/changeSidebarStatus/';
// $config['URIs_ignoreShowAlertOfAjaxReplayWithDelay'][] = '/api/getResultsByTransCode/';
// $config['URIs_ignoreShowAlertOfAjaxReplayWithDelay'][] = '/payment_management/checkSubwallectBalance/';


/// OGP-28577 Ver2.
// it's working under disabled_multiple_database=false,
$config['enable_multi_currencies_totals'] = false;
//
/// sync the player and level
$config['adjust_player_level2others_method_list'] = []; // for VIP levels up/down checking and sync to others MDB
// To diabled the player level sync actions bu Not yet required.
// $config['adjust_player_level2others_method_list'][] = 'Group_level::fakeDeleteVIPGroup';
// $config['adjust_player_level2others_method_list'][] = 'Group_level::playerLevelAdjust';
// $config['adjust_player_level2others_method_list'][] = 'Group_level::playerLevelAdjustDowngrade';
// $config['adjust_player_level2others_method_list'][] = 'player_profile::change_player_level';
//
$config['sync_vip_group2others_method_list'] = [];
// /// $config['sync_vip_group2others_method_list'][] = 'Vipsetting_Management::addVipGroup'; // test #4 - disabled for preview about before
// $config['sync_vip_group2others_method_list'][] = 'SyncMDBSubscriber::syncMDB'; // diable it to always to dryRun.

// OGP-21658
$config['multiple_range_settings_priority'] = 'platform'; // platform or tag

$config['player_basic_amount_list_json_filename'] = 'base_amount_list.default.json'; // should be Not exists file in default
$config['player_basic_amount_enable_in_upgrade'] = false;

// OGP-25796
$config['count_deposit_requests_interval_with_cache_sec'] = 10;

$config['enable_vip_downgrade_switch'] = true;
$config['allow_both_vip_downgrade_and_maintain_switch_on'] = false;

// OGP-19332
$config['vip_setting_form_ver'] = '1';// 1: old, 2: ver2.
$config['enabled_new_resp_table']=true;
// skip response_results
$config['disabled_response_results_table_only']=false;
$config['enabled_new_resp_table_on_report']=false;
$config['enabled_resp_sync_table']=false;
$config['show_player_center_bet_details']=false;
$config['assign_message_of_fail_promo_validate']=false;

$config['accumulaten_manual_bonus_in_check_referral'] = true;

$config['enable_line_registration_in_mobile'] = false;
$config['enable_line_registration_in_desktop'] = false;

$config['use_branch_as_ifsc_in_withdrawal_accounts'] = false;
$config['enabled_line_add_friend_after_line_login'] = false;
$config['force_line_player_to_register'] = false;

$config['acl_login_for_domain_list_only']=null;
$config['default_add_zero_in_contact_number']=false;

// OGP-20546: 'select all players of result' will be disabled
// if result size is greather than this limit in player list search
$config['player_list_select_all_result_size_limit'] = 50000;
$config['force_index_on_saleorders']=false;
$config['disabel_deposit_bank']=false;

// OGP-19262
$config['agin_prefix_for_username'] = ''; // for parse members and mapping to player
$config['enabled_total_player_game_minute_additional']=false;
$config['enabled_total_player_game_hour_additional']=false;

#OGP-20597
$config['not_show_the_copy_button_when_static'] = [];

$config['hide_credit_system_on_affiliate'] = false;
$config['split_player_notes'] = false;
$config['get_seamless_error_logs_request_minutes'] = 5;

$config['time_interval_for_deletion_of_external_common_token_hours']=72;
$config['limit_for_deletion_of_external_common_token']=10000;

/// init_wallet_action:
// 0: disable, but still call wallet_model::initCreateAllWalletForRegister() with lock.
// 1: without lock , aka. init_wallet_without_lock_when_register_player=true
// 2: with lock
$config['init_wallet_action_when_register_player']=1;
// $config['init_wallet_without_lock_when_register_player']=true; // deprecated.
$config['enabled_auto_approved_on_sub_affiliate'] = false;
$config['hide_friend_referral_setting_bind_promo'] = true;
$config['enable_friend_referral_referrer_bet'] = false;
$config['enable_friend_referral_referrer_deposit'] = false;
$config['enable_friend_referral_referrer_deposit_count'] = false;

$config['enable_friend_referral_extra_info'] = false;
$config['enable_friend_referral_mobile_share'] = false;
$config['friendRefMobileSharingTitle'] = false;
$config['friendRefMobileSharingText'] = false;

$config['enabled_collection_name_in_deposit_checking_report'] = false;
$config['switch_last_name_order_before_first_name'] = false;

//OGP-20373
$config['enable_check_seamless_api_bet_status'] = false;
$config['check_seamless_api_bet_status_auto_maintenance_threshold'] = 50;
$config['check_seamless_api_bet_status_auto_maintenance_mm_channel'] = 'test_mattermost_notif';
$config['check_seamless_api_bet_status_auto_maintenance_client'] = '';
$config['check_seamless_api_bet_status_auto_maintenance_apis'] = [];
$config['check_seamless_api_bet_status_api_ids'] = [];
$config['check_seamless_api_bet_status_offset'] = 15;
$config['check_seamless_api_bet_status_step'] = 5;
$config['check_seamless_api_bet_status_channel'] = null;
$config['check_seamless_api_bet_status_enable_auto_maintenance_game'] = false;

$config['seamless_cancel_service_api'] = [
	#'5849' => 'http://admin.staging.sexycasino.t1t.in/amb_service_api/cancel/5849'
];
#OGP-21302
$config['only_show_default_dialing_code'] = false;

$config['hide_player_center_history_list_controls_when_no_data'] = false;
$config['player_center_datatables_use_custom_lang'] = false;

$config['sub_game_provider_to_main_game_provider']=[
    FLOW_GAMING_PNG_SEAMLESS_API=>FLOW_GAMING_SEAMLESS_THB1_API,
    FLOW_GAMING_QUICKSPIN_SEAMLESS_API=>FLOW_GAMING_SEAMLESS_THB1_API,
    FLOW_GAMING_MAVERICK_SEAMLESS_API=>FLOW_GAMING_SEAMLESS_THB1_API,
    FLOW_GAMING_4THPLAYER_SEAMLESS_API=>FLOW_GAMING_SEAMLESS_THB1_API,
    FLOW_GAMING_RELAXGAMING_SEAMLESS_API=>FLOW_GAMING_SEAMLESS_THB1_API,
    FLOW_GAMING_YGGDRASIL_SEAMLESS_API=>FLOW_GAMING_SEAMLESS_THB1_API,
    // FLOW_GAMING_NETENT_SEAMLESS_THB1_API=>FLOW_GAMING_SEAMLESS_THB1_API,
    FLOW_GAMING_PLAYTECH_SEAMLESS_API=>FLOW_GAMING_SEAMLESS_THB1_API,
    FLOW_GAMING_NETENT_SEAMLESS_API=>FLOW_GAMING_SEAMLESS_THB1_API,
	FLOW_GAMING_MG_SEAMLESS_API=>FLOW_GAMING_SEAMLESS_THB1_API,

    LIVE12_PGSOFT_SEAMLESS_API=>LIVE12_SEAMLESS_GAME_API,
	LIVE12_SPADEGAMING_SEAMLESS_API=>LIVE12_SEAMLESS_GAME_API,
	LIVE12_REDTIGER_SEAMLESS_API=>LIVE12_SEAMLESS_GAME_API,
	LIVE12_EVOLUTION_SEAMLESS_API=>LIVE12_SEAMLESS_GAME_API,
	#pariplay
	HACKSAW_PARIPLAY_SEAMLESS_API => PARIPLAY_SEAMLESS_API,
	AMATIC_PARIPLAY_SEAMLESS_API => PARIPLAY_SEAMLESS_API,
	BEFEE_PARIPLAY_SEAMLESS_API => PARIPLAY_SEAMLESS_API,
	OTG_GAMING_PARIPLAY_SEAMLESS_API => PARIPLAY_SEAMLESS_API,
	HIGH5_PARIPLAY_SEAMLESS_API => PARIPLAY_SEAMLESS_API,
	PLAYSON_PARIPLAY_SEAMLESS_API => PARIPLAY_SEAMLESS_API,
	ORYX_PARIPLAY_SEAMLESS_API => PARIPLAY_SEAMLESS_API,
	FBM_PARIPLAY_SEAMLESS_API => PARIPLAY_SEAMLESS_API,
	BOOMING_PARIPLAY_SEAMLESS_API => PARIPLAY_SEAMLESS_API,
	TRIPLECHERRY_PARIPLAY_SEAMLESS_API => PARIPLAY_SEAMLESS_API,
	DARWIN_PARIPLAY_SEAMLESS_API => PARIPLAY_SEAMLESS_API,
	SPINOMENAL_PARIPLAY_SEAMLESS_API => PARIPLAY_SEAMLESS_API,
	SMARTSOFT_PARIPLAY_SEAMLESS_API => PARIPLAY_SEAMLESS_API,
	SPRIBE_PARIPLAY_SEAMLESS_API => PARIPLAY_SEAMLESS_API,
	SPINMATIC_PARIPLAY_SEAMLESS_API => PARIPLAY_SEAMLESS_API,
	WIZARD_PARIPLAY_SEAMLESS_API => PARIPLAY_SEAMLESS_API,
	#SBOBET
	AFB_SBOBET_SEAMLESS_GAME_API => SBOBET_SEAMLESS_GAME_API,
	#qtech
	QT_HACKSAW_SEAMLESS_API => QT_SEAMLESS_API,
	QT_NOLIMITCITY_SEAMLESS_API => QT_SEAMLESS_API,
	#jgameworks
	PG_JGAMEWORKS_SEAMLESS_API => JGAMEWORKS_SEAMLESS_API,
	JILI_JGAMEWORKS_SEAMLESS_API => JGAMEWORKS_SEAMLESS_API,
	PP_JGAMEWORKS_SEAMLESS_API => JGAMEWORKS_SEAMLESS_API,
];

$config['enabled_otp_on_affiliate']=false;
$config['enable_white_ip_on_affiliate']=false;
$config['white_ip_of_affiliate']=[];
$config['enabled_otp_on_agency']=false;

//OGP-18484
$config['gamegateway_api_create_sub_agent_default_settings'] = [
	'is_enabled' => true,
	'settlement_period' => 'Monthly',
	'credit_limit' => 0,
	'available_credit' => 0,
	'can_have_sub_agents' => 1,
	'can_have_players' => 1,
	'can_do_settlement' => 1,
	'can_view_agents_list_and_players_list' => 1,
	'show_bet_limit_template' => 1,
	'show_rolling_commission' => 1,
	'status' => 'active',
	'agent_level' => null,
	'agent_level_name' => null,
	'settlement_start_day' => 'Monday',
    'can_view_agents_list_and_players_list' => 1,
	'settlement_period' => 'Weekly',
	'game_type_revenue_share' => 100,
	'settlement_period' => 'Monthly',//
	'credit_limit' => 0,//
	'available_credit' => 0,//
	'can_have_sub_agents' => 1,//
	'can_have_players' => 1,//
	'can_do_settlement' => 1,//
	'can_view_agents_list_and_players_list' => 1,//
	'show_bet_limit_template' => 1,//
	'show_rolling_commission' => 1,//
	'status' => 'active',//
	'agent_level' => null,
	'agent_level_name' => null,
	'settlement_start_day' => 'Monday',
    'can_view_agents_list_and_players_list' => 1,
	'settlement_period' => 'Weekly',
	'game_type_revenue_share' => 100,
	'credit_limit' => 1000000,
	'available_credit' => 1000000,
];
$config['enable_registration_date_on_friend_referraL_setting'] = false; #config enabled signup date on friend refferal settings

// agent name=>url(without domain)
$config['maintenance_url_for_agent'] = [];

$config['ignore_rebuild_seamless_balance_history']=[];
$config['rebuild_seamless_balance_history_offset_minutes']=10;

$config['affdash_entry_lifetime'] = 3;
$config['affdash_update_reduction'] = false;
$config['affdash_update_reduction_group_1_min_days_after_lastlogin'] = 30;
$config['affdash_update_reduction_group_1_update_min_interval'] = 1;
$config['try_real_ip_on_acl_api']=false;
$config['record_player_center_api_register_to_aff_trafic'] = false;
$config['redirect_to_player_center_if_from_www'] = false;
$config['enabled_query_player_username'] = false;
$config['enable_system_feature_on_staging_for_non_t1_users'] = false;

$config['registration_time_aff_tracking_code_validation'] = false;
$config['disable_newtab_player_lobby']= true;

#set default by OGP-22134
$config['disable_advanced_conditions_collapse_clear_featrue'] = true;
$config['get_var_resuming_from_token_timeout'] = 'timeout_resume';
// [['from'=>, 'to'=> ], ['from'=>, 'to'=> ],]
$config['forbidden_time_range_on_rebuild_total_minute']=null;
$config['always_rebuild_totals_before_cashback']=false;
$config['affliate_network_source_list'] = [];
$config['show_top_10_in_withdrawal'] = false;
$config['cache_top_10_withdrawal'] = true;

$config['enabled_chopped_lock_in_calculatecashback'] = false;
// for Delay Test
$config['idle_in_chopped_lock_of_calculatecashback'] = [];
$config['idle_in_chopped_lock_of_calculatecashback']['idleSec'] = 0;
$config['idle_in_chopped_lock_of_calculatecashback']['player_id_list'] = [];
// for test/debug (5+5=)10 players in clac Cashback.
$config['suffixInGetPlayerBetByDate'] = ''; // limit 5
$config['suffixInGetPlayerBetBySettledDate'] = ''; // limit 5
$config['retroactiveTimeLimitInGetPlayerBetBySettledDate'] = '-15 days';
$config['enabled_dryrun_in_calculatecashback'] = false;


// OGP-23556
$config['player_ip_last_request_cache_expired']=60; // sec


#region KYC Type - Settings
$config['kyc_doc_options']['identify'] = [
    [
        "type" => 'idCard',
        "fileMinLimit" => 2,
        "fileMaxLimit" => 2,            
    ],
    [
        "type"=> "passport",
        "fileMinLimit" => 1,
        "fileMaxLimit" => 1,            
    ],       
];

$config['kyc_doc_options']['address'] = [
    [
        "type" => 'bill',
        "fileMinLimit" => 1,
    ],  
];

$config['kyc_doc_options']['income'] = [
    [
        "type" => 'salarySlip',
        "fileMinLimit" => 1,
    ],  
];

$config['kyc_doc_options']['transaction'] = [
    [
        "type" => 'bankBook',
        "fileMinLimit" => 1,
    ],  
];
#region-end

//OGP-22268
$config['calculate_referrer_deposit_bet_by_signup_date'] = false;

//OGP-22542
$config['cms_navigation_game_platform_override']=[];

//  <api id>=>'/tmp/sync_totals_with_runtime_config-<client>-<api id>.json',
$config['runtime_file_config']=[];
$config['last_datetime_of_game_logs']='-2 minutes';

$config['player_oauth2_settings']=[
    'private_key'=>dirname(__FILE__) . '/secret_keys/oauth-private.key',
    'public_key'=>dirname(__FILE__) . '/secret_keys/oauth-public.key',
    // base64_encode(random_bytes(32)); : bAAkvRdLj0S0W4QzCp2+LWOv/lbM1vd1Z/Zm3sGtoew=
    'encryption_key'=>null,
    'refresh_token_ttl'=>'P1M', // DateInterval format
    'access_token_ttl'=>'PT1H', // DateInterval format
    'lib_class_path'=>'libraries5.6/LibPlayerOauth2.php',
    'scopes' => ['read', 'write'],
];

$config['enable_pop_up_banner_when_player_login_desktop'] = false;
$config['enable_pop_up_banner_when_player_login_mobile'] = false;
$config['pop_up_banner_when_player_login_img_path'] = '';

$config['enable_pop_up_banner_function'] = false;

$config['game_list_notification_channel'] = 'test_mattermost_notif';
$config['mock_of_playerapi']=[];

$config['game_amount_round_down'] = true;
$config['show_maintenance_status_on_get_frontend_games'] = false;

$config['verification_email_cooldown_time_sec'] = 60;
$config['enable_player_login_report']=true;
#25549
$config['enabled_roulette_report'] = false;
$config['public_roulette_latest_record_default_date'] = '-1 days';
$config['search_history_last_activity_from']='-7 days';
$config['always_convert_area_when_search_last_activity']=false;
$config['common_channel_for_alert_message']='php_alert';
$config['alert_when_login_ip_changed']=false;
$config['alert_suspicious_player_login_minutes']=30;

$config['lovebet_migration_viplevel_match'] = [];
$config['assigned_game_apis_map'] = [];
$config['lovebet_migration_banktype_map'] = [];
$config['lovebet_migration_playertag_switch_map'] = [];

$config['lucky_wheel_icon'] = '';
$config['lucky_wheel_show_icon'] = false;

// OGP-22826
$config["enable_reset_sms_verification_limit"] = false;
#OGP-22751
$config["use_new_sms_api_setting"] = false;

$config['show_email_verification_on_login'] = false;
$config['game_fixed_prefix_to_be_remove'] = []; # array of required prefix per t1xxx game platform [<t1gameplatform> => "<required prefix"][T1MTECHBBIN_API => "t1vnds"]
$config['show_message_list_title_in_mobile'] = true;
$config['only_exist_single_address_key_in_acc_info'] = false; //OGLANG-1410

$config['hidden_colvis_for_summary_report'] = [];

$config['summary_report_columnDefs'] = [
	"not_visible_summary_report" => [],
];

#OGP-23730 to 23735
$config["roulette_reward_odds_settings"] = [];
$config['comapi_roulette_cmsid_whitelist'] = false;

// OGP-23571
$config['gamegateway_api_topup_advisory_url'] =  '/topup-advisory';
$config["enable_total_shows_results_in_summary_report"] = false;
$config['exclude_deleted_player_when_visit_userinfo'] = true;

#OGP-24540
$config['enabled_lock_trans_by_singel_role'] = false;
$config['display_total_amount_in_withdrawal_quick_filter'] = false;
$config['display_total_amount_in_deposit_quick_filter'] = false;

$config['filter_unknow_game_type_in_player_site'] = false;
$config['filter_unknow_game_name_in_player_site'] = false;

#OGP-23730 to 23735
$config["roulette_prize_settings"] = false;

$config['use_accumulate_deduction_when_calculate_cashback'] = false;
$config["do_mark_duplicated_ip_to_red_in_player_list"] = false;
$config["enabled_cms_banner_dimension_validation"] = false;
$config["default_cms_banner_dimension"] = [
	"home_banner" => ['width' => 1920, 'height' => 470],
	"promo_banner" => ['width' => 588, 'height' => 250],
];

#OGP-24282 24283
$config['enabled_automation_batch_send_internal_msg'] = [];

$config['player_tag_allowed_games'] = [];

#OGP-24237
$config['enable_sms_verified_phone_in_withdrawal'] = false;
$config['enable_sms_verified_phone_in_promotion'] = false;

$config['enabled_player_tag_in_deposit'] = false;

#OGP-24400
$config['enable_aff_logo_link_force_redirecting_domain'] = false;

#OGP-24900
$config['only_select_active_accounts_for_collection_account_pages'] = false;

// this is for odds of customize
$config['enabled_batch_dryrun_promo']=false;
$config['enabled_captcha_on_player_center_api']=false;
$config['enabled_captcha_on_player_center_api_smsRegCreatePlayer']=false; // for wait client develop smsRegCreatePlayerCaptcha
$config['enabled_captcha_on_player_center_api_createPlayer']=false; // for wait client develop createPlayerCaptcha
// use utils->tryGetRealIPWithoutWhiteIP
$config['use_real_ip_on_player']=false;
$config['record_blocked_player_login']=false;
//OGP-24578
$config['enabled_new_broadcast_message_job'] = false;

#OGP-25005
$config['enable_multiple_cashback_type']=false;

#25701
$config['enabled_viplevel_filter_in_transactions'] = false;

$config['enabled_dispatch_withdrawal_results_monthly_table']=false;

$config['enabled_admin_logs_monthly_table']=false;
$config['enabled_validate_white_referrer_rule']=false;

$config['enabled_display_inactived_collection_account_page']=false;

$config['limit_manual_adjustment_by_roles'] = [
    // sample
    // '9073' => [ // T1_QA_Manager on local
    //     'max_daily_add_balance' =>  1000,
    //     'max_daily_add_bonus' =>  100,
    //     'max_amount_for_add_balance' =>  100,
    //     'max_amount_for_add_bonus' =>  100,
    // ]
];

// OGP-24454
$config['hide_default_forgot_password_settings'] = false;

//OGP-24926
$config['gamegateway_api_disable_round_to_amount'] = false;
#OGP-25017
$config['enabled_hidden_financial_account_banklist_show_hide_btn'] = false;

//OGP-24614
$config['seamless_batch_payout_settings'] = [
	'base_url' => 'http://admin.og.local',
];
$config['enable_sync_batch_payout_for_gameplatform']=[];

// OGP-25037
$config['game_list_column_order'] = [
	"default_order" => ['action', 'english_name', 'game_type', 'system_code', 'game_name', 'game_code','external_game_id', 'rtp', 'attributes', 'progressive', 'desktop_enabled', 'mobile_enabled', 'flash_enabled', 'html_five_enabled', 'enabled_on_ios', 'enabled_on_android', 'dlc_enabled', 'offline_enabled', 'flag_hot_game', 'flag_new_game', 'note', 'no_cash_back', 'void_bet', 'status', 'flag_show_in_site', 'game_order', 'tag_game_order', 'release_date', 'created_on', 'updated_at', 'deleted_at', 'locked_flag', 'demo_link']

];

#25321
$config['enabled_player_center_customized_accountinfo'] = false;

$config['enabled_priority_player_features'] = false;
$config['enabled_notifi_failed_login_attempt_features'] = false;
#26302
$config['enabled_fixed_promo_err_msg'] = false;

#OGP-25147
$config['custom_promo_sucess_msg'] = false;

$config['hide_dispatch_account_level_on_registering_in_aff'] = true; // OGP-26220

#25827
$config['enabled_multiple_type_tags_in_promotions'] = false;

$config['game_list_columnDefs'] = [
    "not_visible_columns" => ["external_game_id","no_cash_back", "void_bet", "game_order", "deleted_at", "locked_flag"]
];

$config['enabled_captcha_of_3rdparty'] = [	'3rdparty_label' => '',
											'site_key'=>'',
											'secret'=>'',
											'theme'=>'', //dark or light
											'size'=>'', //normal or compact
											'hcaptcha_timeout_seconds' => 30,
											'captcha_js' => '',
											'verify_url' => '',
										];

$config['og_sync_ignore_api'] =[];
//OGP-25143
$config['saba_odds_direct_api_base_url'] = '';
$config['saba_odds_direct_api_key'] = '';
$config['saba_odds_direct_api_server_key'] = '';
$config['saba_odds_direct_api_version'] = '';
$config['saba_odds_direct_api_vendor_id'] = '';
$config['saba_odds_direct_curl_proxy'] = '';

$config['keep_shop_request_filter'] = true;
$config['enable_shop_frozen_points'] = false;
$config['enable_auto_approve_shop_items'] = false;
$config['enable_hide_shop_claim_button'] = false;
$config['enable_shop_claim_item_auto_reload_desktop'] = false;
$config['enable_send_internal_message_in_shop_process'] = true;
$config['show_deposit_hint_in_deposit_sidebar'] = false;
$config['enable_account_history_unsettled_game'] = false;

//OGP-24614 Ex: [T1LOTTERY_SEAMLESS_API=>20]
$config['seamless_game_split_batch_payout'] = [];
// OGP-25718
$config['hide_iptaglist'] = true;

$config['show_low_balance_prompt_on_game_launch'] = [];
$config['show_low_balance_prompt_on_game_launch_on_desktop'] = true;
$config['show_low_balance_prompt_on_game_launch_on_mobile'] = true;

$config['display_affiliate_player_ip_history_in_player_report'] = true;

//OGP-35661
$config['update_affiliate_player_total'] = false;

//25996
$config['enable_crypto_details_in_crypto_bank_account'] = false;

$config['enabled_player_score'] = false;
$config['enabled_batch_adjust_player_score'] = false;
$config['custom_player_rank_list'] = [];
$config['auto_apply_and_release_bonus_for_smash_newbet_promocms_id'] = false;
$config['enable_send_internal_message_in_newbet_process'] = false;
$config['enable_syne_newbet_when_build_report'] = false;

$config['p2p_chat_api'] = null;

$config['p2p_chat_api_default'] = null;

$config['deny_the_request_to_disable_OTP_for_all_users'] = false;

$config['enable_redemption_code_system'] = false;
$config['enable_redemption_code_system_in_playercenter'] = false;
$config['default_redemption_Code_generate_type'] = 'byCode';
$config['redemption_code_promo_cms_id'] = false;
$config['redemption_code_exclud_deleted_type'] = true;
$config['generate_redemption_code_with_message_default_subject'] = 'Código de resgate';
$config['generate_redemption_code_with_message_default_message'] = "Parabéns! Membros valiosos [username] recebem código de bônus! Obrigado por seu apoio ao! Boa sorte com o jackpot! Seu código de resgate é: [code]";

$config['enable_player_invite_calculation'] = false;

$config['enabled_tracking_platform_system'] = false;
$config['tracking_setting']['platform'] = ["google", "meta", "appsflyer", "kwai"];
$config['tracking_setting']['event'] = ["pageView", "appOpened", "register", "login", "logout", "dockClick", "rightPaneItemClick", "walletModalDeposit", "depositSuccess", "depositFirstSuccess", "walletModalWithdraw", "promotionEnter", "promotionClaim", "userInfoModalSave", "kycModalSave", "resetPasswordModalSave", "bettingRecordModalSearch", "transactionModalSearch", "depositHistoryModalSearch", "spinModalSpin", "funPlay", "realPlay", "enterGame", "gameAddFavorite", "taskHubModalGoTo", "taskHubModalClaim"];
//OGP-25411
$config['custom_registered_popup'] = false;

$config['hide_registered_modal'] = false;

#26646
$config['prevent_player_list_preload'] = false;
#26647
$config['enabled_playerlist_search_cooldown_time'] = false;

#26222 set 0 means disabled the auto logout, default 60s
$config['player_auto_lock_window_auto_logout'] = 60;

$config['use_queue_for_kick_player_by_game_platform_id'] = true;

$config['player_center_api_enable_refresh_subwallet'] = false;
// cache condition promo on utils->getPlayerAvailablePromoList
$config['cache_condition_promo_on_available_list']=false;
$config['cache_condition_promo_on_available_list_expired_seconds']=600;

//OGP-26694
$config['display_player_notification_in_playerhost_only'] = false;

$config['enable_batch_update_tier_commission_settings']=false;
$config['enable_batch_update_commission_on_agency_info_page']=false;
$config['prevent_unsettle_game_auto_clear_withdrawal_conditions'] = true; //false; set to true OGP-29193

#26871,26872
$config['enabled_roulette_transactions'] = false;
$config['roulette_description'] = [];
$config['prevent_aff_tracking_link_redriect_to_m'] = false;

$config['enable_redirect_after_registraction'] = false;
#27503
$config['reset_password_by_admin'] = false;
#27504
$config['force_reset_password_after_operator_reset_password_in_sbe'] = false;
#28221
$config['enabled_currency_sign_in_preset_amount'] = false;
#28262
$config['hide_withdrawal_transfer_balance_text'] = false;
#28036
$config['restrict_player_login_pwd_cannot_same_withdrawal_pwd'] = false;
$config['restrict_player_withdrawal_pwd_cannot_same_login_pwd'] = false;

#28297
$config['aff_earnings_report_display_zero_when_amount_is_negative'] = false;

#28392
$config['enabled_withdrawal_page'] = true;

$config['sync_latest_game_records_sleep_seconds']=900;
$config['sync_latest_game_records_cache_ttl']=3600;
$config['sync_latest_game_records_save_cache_enabled']=false;
$config['latest_bets_game_code']=[];
$config['latest_bets_game_type']=[];
$config['get_latest_game_records_use_start_date_today']= false; // '2020-10-31 00:00:00';
$config['player_center_report_cache_ttl']=900;

$config['player_center_list_games_cache_ttl']=7200;

$config['enable_restrict_username_more_options']=false;

$config['generate_monthly_seamless_transactions_apis']=[];

$config['allowed_trackingevent_source_type']=false;
$config['allowed_trackingevent_source_type']=[
	"TRACKINGEVENT_SOURCE_TYPE_PAGE_VIEW",
    "TRACKINGEVENT_SOURCE_TYPE_LAST_LOGIN",
    "TRACKINGEVENT_SOURCE_TYPE_CREATE_DEPOSIT",
    "TRACKINGEVENT_SOURCE_TYPE_DEPOSIT_SUCCESS",
    "TRACKINGEVENT_SOURCE_TYPE_FIRST_DEPOSIT_SUCCESS",
    "TRACKINGEVENT_SOURCE_TYPE_WITHDRAWAL",
    "TRACKINGEVENT_SOURCE_TYPE_VIP_UPGRADE",
    "TRACKINGEVENT_SOURCE_TYPE_VIP_DOWNGRADE",
    "TRACKINGEVENT_SOURCE_TYPE_REGISTER_COMMOM",
    "TRACKINGEVENT_SOURCE_TYPE_REGISTER_LINE",
    "TRACKINGEVENT_SOURCE_TYPE_REGISTER_FACEBOOK",
    "TRACKINGEVENT_SOURCE_TYPE_REGISTER_GOOGLE",
    "TRACKINGEVENT_SOURCE_TYPE_DEPOSIT_FAILED",
    "TRACKINGEVENT_SOURCE_TYPE_WITHDRAWAL_SUCCESS",
    "TRACKINGEVENT_SOURCE_TYPE_WITHDRAWAL_FAILED",
	"TRACKINGEVENT_SOURCE_TYPE_PROMO_PENDING",
    "TRACKINGEVENT_SOURCE_TYPE_PROMO_APPROVED",
    "TRACKINGEVENT_SOURCE_TYPE_PROMO_REJECTED",
	"TRACKINGEVENT_SOURCE_TYPE_CREATE_DEPOSIT_FAILED",
	"TRACKINGEVENT_SOURCE_TYPE_SENT_MESSAGE_SUCCESS",
	"TRACKINGEVENT_SOURCE_TYPE_PLAY"
];

$config['player_center_api_x_custom_header'] = '';
$config['disabled_promo_bonus_release_count'] = false;
$config['use_cronjob_for_promo_bonus_release_count'] = false;

$config['preset_amount_buttons_limit_count'] = 5;

$config['disabled_deposit_page_decimal'] = false;
$config['disabled_withdrawal_page_decimal'] = false;
$config['disabled_withdrawal_page_thousands'] = false;

$config['enable_deposit_custom_view'] = false;

$config['set_default_round_currency_precision_for_show'] = 2;
$config['disable_write_transaction']=false;
$config['enable_custom_report_tab'] = false;
$config['custom_report_tab_sidebar_item'] = [];
$config['enabled_check_captcha_static_site']= 'static_site';
$config['enable_custom_crypto_bank_lang'] = false;

// for test player lock balance dev function
$config['enable_test_player_lock_balance'] = true;
$config['show_test_player_lock_balance_seconds'] = true;
$config['use_select_test_player_lock_balance_seconds'] = true;
$config['test_player_lock_balance_seconds_list'] = [10, 30, 60, 100, 120];
$config['gamegateway_agent_ip_whitelist_enable'] = false;
$config['gamegateway_agent_ip_whitelist'] = [];
$config['softswiss_provider_for_acceptance'] = SOFTSWISS_BGAMING_SEAMLESS_GAME_API;

#OGP-32138 - set bet limit for game provider
$config['set_game_provider_bet_limit'] = true;

$config['game_launch_allow_only_verified_contact'] = false;
$config['game_launch_allow_only_complete_contact'] = false;
$config['game_launch_allow_only_complete_contact_except_no_balance'] = false;

$config['custom_game_description_tag'] = [
];

//OGP-28038
$config['third_party_get_response'] = [];

$config['country_code_list_cache_ttl']=6 * 60 * 60;
$config['country_phone_code_list_cache_ttl']=6 * 60 * 60;

$config['cache_setting']['player_vip_current_total_deposit_and_bet'] = [
    'ttl' => 1 * 60 * 60, //one hour
    'enable' => true,
];

$config['cache_setting']['player_current_vip_group_info'] = [
    'ttl' => 1 * 60 * 60, //one hour
    'enable' => true,
];

$config['unset_iso2_country_list']=['Congo','Congo (Brazzaville)','Saint Vincent'];

// [api id, api id]
$config['retry_api_in_response_result']=[REMOTE_WALLET_API];
$config['retry_button_only_for_error_flag']=true;

require_once dirname(__FILE__) . '/admin/application/config/config_cronjob.php';
require_once dirname(__FILE__) . '/admin/application/config/config_api_acl.php';

$config['game_description_flag_new_game_date_sub_interval'] = 'month'; #day/week/month - default month
$config['game_description_flag_new_game_date_sub_interval_value'] = '1'; #default 1 month

$config['allow_empty_blackbox'] = false;

$config['games_with_tournament_feature'] = [PRAGMATICPLAY_API];
$config['header_forwarded_for_unset_append_string_array'] = ["unknown"];

$config['get_players_bet_list_by_date_cache_ttl'] = 3600;
$config['get_players_deposit_list_by_date_cache_ttl'] = 3600;
$config['get_players_game_logs_cache_ttl'] = 3600;
$config['get_players_game_logs_default_by'] = 'latest'; // latest | today | date | date_range
$config['cache_players_game_logs_from_scheduler_cronjob'] = false;
$config['use_player_latest_game_logs'] = true;

#31397
$config['allow_clear_session_when_launch_game'] = true;
$config['kickout_player_oauth2_token'] = false;

$config['main_custom_specific_apis_decimals'] = [
    TADA_SEAMLESS_GAME_API => ['precision' => 4],
    T1_TADA_SEAMLESS_GAME_API => ['precision' => 4],
];

$config['sync_tags_to_3rd_api'] = [];

$config['check_the_default_collection_account_when_callback'] = false;

$config['add_custom_specific_apis_decimals'] = [
    // format: TADA_SEAMLESS_GAME_API => ['precision' => 4],
];

$config['enable_player_to_register_with_existing_contactnumber'] = false;
$config['notification_duplicate_contactnumber'] = false;
$config['duplicate_contactnumber_day_limit'] = 90;

$config['clear_time_on_notify_last_login'] = date('Y-m-d 00:00:00');

$config['game_list_limit_player_center_api'] = 50;

$config['player_center_api_special_game_tags'] = ['original', 'popular', 'epic', 'recent'];

$config['get_pos_bet_details_cache_ttl'] = 3600;

$config['get_pos_bet_details_default_params'] = [
    'order_by' => 'bet_at',
    'order_type' => 'desc',
    'limit' => 10,
    'offset' => 0,
];
$config['enabled_turnover_amount_in_player_report']=false;
$config['enabled_get_allpromo_with_category_via_ajax'] = false;
$config['enabled_log_OGP29899_performance_trace'] = false;
$config['enabled_isAllowedPlayerBy_with_lite'] = false;

// default is Portugal separator style - https://en.wikipedia.org/wiki/Decimal_separator
$config['get_pos_bet_details_currency_style'] = [
    'code' => 'BRL',
    'name' => 'Brazilian real',
    'short_name' => 'BRL',
    'symbol' => 'R$',
    'decimals' => 2,
    'decimal_separator' => ',',
    'thousands_separator' => ' ',
];

$config['pos_tag_name'] = 'POS';
$config['sync_pos_player_latest_game_logs_by_game_platform_ids'] = [PINNACLE_SEAMLESS_GAME_API, BETBY_SEAMLESS_GAME_API];
$config['sync_pos_player_latest_game_logs_sleep_seconds'] = 30;
$config['sync_pos_player_latest_game_logs_modify_date_from_minutes'] = 30;

$config['playerapi_api_cache_ttl']=60;
$config['playerapi_api_cache_ttl_for_top_games_player']=3600;
$config['playerapi_sync_auth_token_to_all_currency']=true;
$config['betby_testing_password'] = '4821bd1c1a3bd7b4630a36f123921654da2950fc';


#region - crypto currency lib
$config['crypto_currency_use_api'] = CCE_CRYPTO_PAYMENT_API;
$config['crypto_currency_enabled_coins'] = [
	CRYPTO_CURRENCY_COIN_USDT,
];
$config['crypto_currency_enabled_chain'] = [
	CRYPTO_CURRENCY_CHAIN_ETH,
	CRYPTO_CURRENCY_CHAIN_TRON
];
$config['crypto_target_db'] = [
    CRYPTO_CURRENCY_COIN_USDT => ['mdb_key' => 'usdt']
];
$config['enabled_crypto_currency_wallet'] = false;
#endregion - crypto currency lib

$config['get_only_flagged_custom_game_tags'] = true;
$config['get_top_games_by_players_cache_ttl'] = 3600;

$config['pix_system_info'] = [
    'auto_build_pix_account'        => ['enabled' => false, 'allow_type' => [ PIX_TYPE_CPF, PIX_TYPE_EMAIL, PIX_TYPE_PHONE ]],
    'edit_pix_account'     			=> ['enabled' => false, 'allow_type' => [ PIX_TYPE_CPF, PIX_TYPE_EMAIL, PIX_TYPE_PHONE ]],
    'identify_cpf_numer_on_kyc'     => ['enabled' => false],
    'locked_pix_acc'			 	=> ['enabled' => false], //Not yet implement
];

$config['auto_build_pix_account'] = false;

#OGP-30381
$config['no_game_img_url_game_api_list'] = [
	BETBY_SEAMLESS_GAME_API,
];

$config['game_tag_code_for_new_release'] = 'new';

$config['game_tag_new_release_interval'] = [
    'expr' => 1,
    'unit' => 'MONTH'
];


$config['limit_update_player_times'] = [];

#OGP-30973
$config['aff_bo_banner_list_order'] = "asc";

$config['protected_system_game_tags'] = [
    'slots',
    'lottery',
    'fishing_game',
    'live_dealer',
    'casino',
    'gamble',
    'table_games',
    'table_and_cards',
    'card_games',
    'e_sports',
    'fixed_odds',
    'arcade',
    'horse_racing',
    'progressives',
    'sports',
    'unknown',
    'video_poker',
    'poker',
    'mini_games',
    'others',
    'soft_games',
    'scratch_card',
    'cock_fight',
    'graph',
    'chess',
    'dingdong',
    'tip',
    'shooting_games',
    'bac_bo',
    'virtual_sports',
    'bingo',
    'racing',
    'hot',
    'new',
];

#OGP-30626
$config['except_game_api_list'] = [

];

$config['disable_quest_category_override_mnanger_countdown'] = true;
$config['single_condition_type'] = [1,3,5,6,7,8,9,10,11,12];
$config['multiple_condition_type'] = [5];
$config['quest_display_panel'] = [];

$config['search_report_summary2_with']= 'orig';
$config['generate_summary2_report_monthly_from'] = 'orig';

$config['default_sbe_login_logo'] = "og-login-logo2.png";

$config['enable_latest_bets_fake_data'] = false;
$config['latest_bets_fake_data'] = [
    'number_of_fake_data' => 8,
    'fake_username_prefix' => '',
    'random_game_tag_code' => 'top',
    'range' => [
        'min_bet_amount' => 200000,
        'max_bet_amount' => 500000,
    ],
    'fake_usernames' => []
];

$config['enable_latest_high_rollers_fake_data'] = false;
$config['latest_high_rollers_fake_data'] = [
    'number_of_fake_data' => 8,
    'fake_username_prefix' => '',
    'random_game_tag_code' => 'top',
    'range' => [
        'min_result_amount' => 1000,
        'max_result_amount' => 50000,
    ],
    'fake_usernames' => []
];
$config['provider_launcher_language'] = [];//example ['58' => 1]
$config['oneworks_game_report_platform_id'] = ONEWORKS_API;#default
$config['sbobet_game_report_platform_id'] = SBOBET_API;#default

$config['enable_limit_player_latest_game_logs']=true;

$config['game_api_with_free_rounds'] = [
	SPINOMENAL_SEAMLESS_GAME_API,
];

#OGP-31947
$config['multiple_type_tags_in_promotions'] = array('New','Favourite','EndSoon','NoTag');

#OGP-32591
$config['enable_batch_export_player_id'] = true;

$config['super_cashback_report_main_currency'] = '';

$config['set_game_list_default_order_by_to_game_order'] = false;

$config['set_game_list_default_order_by_to_game_order_direction'] = 'asc';

$config['lobby_launch_provider_category'] = [
    // live_dealer
    BBIN_API => 'live_dealer',
    SA_GAMING_API => 'live_dealer',
    MIKI_WORLDS_GAME_API => 'live_dealer',
    NTTECH_V3_API => 'live_dealer',
    DG_API => 'live_dealer',
    OGPLUS_API => 'live_dealer',
    AB_V2_GAME_API => 'live_dealer',
    NTTECH_IDR_B1_API => 'live_dealer',

    // sports
    SBTECH_API => 'sports',
    SPORTSBOOK_FLASH_TECH_GAME_API => 'sports',
    AFB88_API => 'sports',
    RGS_API => 'sports',

    // slots
    LUCKY365_GAME_API => 'slots',
    LIONKING_GAME_API => 'slots',
    BETSOFT_API => 'slots',
    GOLDENF_PGSOFT_API => 'slots',
    MG_API => 'slots',
    CQ9_API => 'slots',

    // lottery
    NEX4D_GAME_API => 'lottery',
    ISIN4D_IDR_B1_API => 'lottery',
    T1LOTTERY_API => 'lottery',

    // cock_fight
    WGB_GAME_API => 'cock_fight',
    WCC_GAME_API => 'cock_fight',

    // poker
    IDNPOKER_API => 'poker',

    // fishing_game
];

#OGP-33232
$config['enable_get_latest_high_rollers_weekly'] = false;

$config['moniter_changing_ip_rule']['channel'] = 'high_level_monitor';
$config['moniter_changing_user_password']['channel'] = 'high_level_monitor';

$config['enabled_sync_game_logs_stream'] = false;

$config['change_source_type_to_cashback'] = false;

#gameprovider_transaction_service_api && mega_xcess_service_api
$config['internal_game_provider_api_key']='42f77fd0409566c6123d58551e85ee87';

$config['fastwin_backend_api_white_api_list'] = [];
$config['fastwin_tester_white_ip_list'] = [];
$config['fastwin_remove_white_ip_list'] = [];
$config['games_with_valid_bet_checking'] = [];
$config['game_provider_default_outlet_code'] = null;
$config['game_provider_default_outlet_name'] = null;
$config['game_provider_max_query_date_time_range'] = 24;
$config['game_provider_default_row_limit'] = 1000;
$config['game_provider_default_table_name'] = 'game_logs';
$config['game_provider_use_main_outlet_code'] = false;

$config['fastwin_outlet_mapping'] = [
	'FWUAYT' => ['FWMCUT', 'FWAYLT'],
	'FWEEIT' => ['FWVETT'],
	'FWRNGT' => ['FWTF2T', 'FWNEGT'],
	'FWFEST' => [],
	'FWFGGT' => ['FWSEST', 'FWTTGT'],
	'FWFEWT' => ['FWMCWT'],
	'FWLLAT' => [],
	'FWMSNT' => [],
	'FWIGGT' => [],
	'FWRCAT' => [],
	'FWLGST' => ['FWESTT', 'FWLONT'],
	'FWDDLT' => [],
	'FWNRAT' => [],
];

$config['mx_api_where_condition_date'] = ['start_at' => 'end_at', 'end_at' => 'end_at'];
$config['fastwin_mx_api_exclude_player_tag_ids'] = [];

$config['fastwin_enable_ip_validation']=true;
$config['fastwin_mx_api_precision']=3;
$config['fastwin_mx_api_allowed_promo_ids'] = [];

$config['get_high_rollers_cache_ttl'] = 3600;
$config['display_game_only_bet_wallet'] = false;

#OGP-34618
$config['default_latest_bet_limit'] = 20;

#OGP-34634
$config['monitor_duplicate_game_account_base_url'] = 'http://admin.og.local';
$config['monitor_duplicate_game_account_game_apis'] = [PINNACLE_API, PINNACLE_SEAMLESS_GAME_API];
$config['monitor_duplicate_game_account_mattermost_channel'] = "test_mattermost_notif";
$config['monitor_duplicate_game_account_mattermost_user'] = "testt1dev";

#OGP-35057
$config['payment_list_auto_refresh_time'] = [
    "deposit" => 30000,
    "withdrawal" => 30000
];

$config['enable_sync_latest_game_records'] = true;
$config['enable_cancel_game_round'] = true;
$config['enable_clear_game_logs_md5_sum'] = true;

$config['t1_games_mapping'] = [
    EVOLUTION_SEAMLESS_GAMING_API => T1_EVOLUTION_SEAMLESS_GAME_API,
];

$config['enable_balance_check_report'] = false;
$config['enable_refresh_all_player_balance_in_specific_game_provider'] = false;
$config['days_old_for_delete_gamelogs_exported_files'] = 3;

$config['enable_player_activity_logs'] = false;
$config['player_activity_logs_allowed_days'] = 3;
$config['player_activity_logs_offset'] = 10;
$config['player_activity_logs_step'] = 5;

$config['player_activity_logs_player_center_request_api_mapping'] = [
    '/playerapi/oauth/token' => 'login',
    '/playerapi/game/launch' => 'game_launch',
    '/playerapi/game/launch/demo' => 'game_launch_demo',
    '/playerapi/game/launch/lobby' => 'game_launch_lobby',
];
$config['player_activity_logs_enable_delete_old_records'] = true;
$config['get_sync_player_last_played_minutes'] = 15;

$config['deposit_missing_balance_alert_config'] = [
    'enable' => false,
    'mm_channel' => 'test_mattermost_notif',
    'base_url' => '', // required - sample: http://admin.og.local
    'alert_skip_threshold' => 120, // default 2 minutes
];

$config['enable_gateway_mode'] = false;
///END OF FILE//////////
