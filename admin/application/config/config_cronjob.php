<?php

//move to worker
$config['all_cron_jobs'] = array(
	//responsible gaming self-exclusion: every minute
	'cronjob_exec_self_exclusion' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh executeSelfExclusion',
		'cron' => '*/1 * * * *',
		'default_enabled' => false,
		'note' => 'Will check for self exclusion request',
	),
	//responsible gaming unself-exclusion: every minute
	'cronjob_exec_unself_exclusion' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh executeUnSelfExclusion',
		'cron' => '*/1 * * * *',
		'default_enabled' => false,
		'note' => 'Will check for due self exclusion',
	),
	//responsible gaming cooling off: every minute
	'cronjob_exec_cool_off' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh executeCoolOff',
		'cron' => '*/1 * * * *',
		'default_enabled' => false,
		'note' => 'Will check for cool off request',
	),
	//responsible gaming uncooling off: every minute
	'cronjob_exec_uncool_off' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh executeUnCoolOff',
		'cron' => '*/1 * * * *',
		'default_enabled' => false,
		'note' => 'Check if cool off has ended',
	),
	//responsible gaming session limit checker: every minute
	'cronjob_exec_session_limit_checker' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh executeSessionLimitChecker',
		'cron' => '*/1 * * * *',
		'default_enabled' => false,
		'note' => 'Will check if player reached the playing time period',
	),
	//responsible gaming loss limit request: every minute
	'cronjob_approve_loss_limit_request' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh approveLossLimitRequest',
		'cron' => '*/1 * * * *',
		'default_enabled' => false,
		'note' => 'Will approve the loss limit request',
	),
	//responsible gaming deposit limit request: every minute
	'cronjob_approve_deposit_limit_request' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh approveDepositLimitRequest',
		'cron' => '*/1 * * * *',
		'default_enabled' => false,
		'note' => 'Will approve the deposit limit request',
	),
	//responsible gaming deposit and loss limit player reactivation: every minute
	'cronjob_exec_deposit_and_loss_limit_player_reactivation' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh executeDepositAndLossLimitPlayerReactivation',
		'cron' => '*/1 * * * *',
		'default_enabled' => false,
		'note' => 'Will reactivate due player',
	),
    'cronjob_exec_deposit_limit_auto_subscribe' => array(
        'cmd' => '{OGHOME}/admin/shell/command.sh executeDepositLimitAutoSubscribe',
        'cron' => '30 * * * *',
        'default_enabled' => false,
        'note' => 'Will check for deposit limit auto subscribe',
    ),
    'cronjob_exec_wagering_limit_auto_subscribe' => array(
        'cmd' => '{OGHOME}/admin/shell/command.sh executeWageringLimitAutoSubscribe',
        'cron' => '30 * * * *',
        'default_enabled' => false,
        'note' => 'Will check for wagering limit auto subscribe',
    ),
    'cronjob_exec_deposit_limit_auto_expire' => array(
        'cmd' => '{OGHOME}/admin/shell/command.sh executeDepositLimiAutoExpire',
        'cron' => '30 * * * *',
        'default_enabled' => false,
        'note' => 'Will check for deposit limit auto expire',
    ),
    'cronjob_exec_wagering_limit_auto_expire' => array(
        'cmd' => '{OGHOME}/admin/shell/command.sh executeWageringLimiAutoExpire',
        'cron' => '30 * * * *',
        'default_enabled' => false,
        'note' => 'Will check for wagering limit auto expire',
    ),
	//responsible gaming loss limit checker: daily
	'cronjob_exec_daily_loss_limit_checker' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh executeDailyLossLimitChecker',
		'cron' => '0 * * * *', //daily (midnight)
		'default_enabled' => false,
		'note' => 'Will check for active player with loss limit daily',
	),
	//responsible gaming loss limit request: weekly
	'cronjob_exec_weekly_loss_limit_checker' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh executeWeeklyLossLimitChecker',
		'cron' => '0 * * * *', //weekly (sunday midnight)
		'default_enabled' => false,
		'note' => 'Will check for active player with loss limit weekly',
	),
	//responsible gaming loss limit request: monthly
	'cronjob_exec_monthly_loss_limit_checker' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh executeMonthlyLossLimitChecker',
		'cron' => '0 * * * *', //montly (1st day of the month midnight)
		'default_enabled' => false,
		'note' => 'Will check for active player with loss limit monthly',
	),
	//responsible gaming loss limit checker: daily
	'cronjob_exec_daily_deposit_limit_checker' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh executeDailyDepositLimitChecker',
		'cron' => '0 * * * *', //daily (midnight)
		'default_enabled' => false,
		'note' => 'Will check for active player with deposit limit daily',
	),
	//responsible gaming deposit limit request: weekly
	'cronjob_exec_weekly_deposit_limit_checker' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh executeWeeklyDepositLimitChecker',
		'cron' => '0 * * * *', //weekly (sunday midnight)
		'default_enabled' => false,
		'note' => 'Will check for active player with deposit limit weekly',
	),
	//responsible gaming deposit limit request: monthly
	'cronjob_exec_monthly_deposit_limit_checker' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh executeMonthlyDepositLimitChecker',
		'cron' => '0 * * * *', //montly (1st day of the month midnight)
		'default_enabled' => false,
		'note' => 'Will check for active player with deposit limit monthly',
	),
	//cashback: hourly
	'cronjob_calc_cashback' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh calculateCashback',
		'cron' => '30 * * * *',
		'default_enabled' => true,
		'note' => 'Calculate cashback',
	),
	//cashback: hourly
	'cronjob_pay_cashback' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh onlyPayCashback',
		'cron' => '1 * * * *',
		'default_enabled' => true,
		'note' => 'Pay cashback',
	),
	'cronjob_do_settle_temp_deduct_of_calc_cashback_daily_cron' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh doSettleTempDeductOfCalcCashbackDailyCron',
        'cron' => '13 2 * * *',
        'default_enabled' => false,
        'note' => 'Execute the temporary deduction settlement Of the withdraw conditions in the cashback calculation',
    ),
	//cashback: hourly
	 'cronjob_calc_weekly_cashback' => array(
	 	'cmd' => '{OGHOME}/admin/shell/command.sh calculateWeeklyCashback',
	 	'cron' => '35 * * * *',
	 	'default_enabled' => false,
	 	'note' => 'Calculate weekly cashback',
	 ),
	//cashback: hourly
	// 'cronjob_calc_weekly_cashback_in_vip' => array(
	// 	'cmd' => '{OGHOME}/admin/shell/command.sh calculateWeeklyCashbackUnderVIP',
	// 	'cron' => '40 * * * *',
	// 	'default_enabled' => false,
	// 	'note' => 'Calculate weekly cashback under VIP',
	// ),
	//update geoip: 12:01 daily
	'cronjob_geoipupdate' => array(
		'cmd' => 'geoipupdate -v',
		'cron' => '2 12 * * *',
		'default_enabled' => false,
		'note' => 'Update GEOIP',
	),
	//clear session: 09:01 daily
	'cronjob_clear_sessions' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh clearSessions',
		'cron' => '3 3,11,19 * * *',
		'default_enabled' => true,
		'note' => 'Clear timeout sessions',
	),
	//clear timeout SMS: 03:05 every day
	'cronjob_clear_timeout_sms' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh clearTimeoutSms',
		'cron' => '5 3 * * *',
		'default_enabled' => false,
		'note' => 'Clear timeout SMS',
	),
	//set hiding promo rules: daily
	'cronjob_check_hiding_promo' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh checkPromoForHiding',
		'cron' => '4 0 * * *',
		'default_enabled' => true,
		'note' => 'Check hiding promo rules',
	),
	//reset withdraw limit: daily
	'cronjob_reset_approved_withdraw_amount' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh resetApprovedWithdrawAmount',
		'cron' => '2 0 * * *',
		'default_enabled' => true,
		'note' => 'Reset approved withdraw amount daily back to original',
	),
	//backup sites to rackspace: daily
	'cronjob_upload_to_rackspace' => array(
		'cmd' => '{OGHOME}/admin/shell/upload_to_rackspace.sh',
		'cron' => '6 11 * * *',
		'default_enabled' => false,
		'note' => 'Upload sites to rackspace',
	),
	//affiliate monthly earnings: 01:01 daily
	'cronjob_calculate_monthly_earnings' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh calculate_monthly_earnings',
		'cron' => '0 0 * * *',
		'default_enabled' => true,
		'note' => 'Calculate monthly affiliate earnings',
	),
	//affiliate monthly earnings hourly: xx:25 daily
	'cronjob_calculate_monthly_earnings_hourly' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh calculate_current_monthly_earnings',
		'cron' => '25 * * * *',
		'default_enabled' => false,
		'note' => 'Calculate monthly affiliate earnings hourly',
	),
	// 'cronjob_calculate_monthly_earnings_ibetg' => array(
	//         'cmd' => '{OGHOME}/admin/shell/command.sh calculate_monthly_earnings_ibetg',
	//         'cron' => '7 1 * * *',
	//         'default_enabled' => true,
	//         'note' => 'Calculate monthly affiliate earnings for ibetg',
	// ),
	//friend referral monthly earnings : monthly date 2 for ibetg
	'cronjob_calculate_friend_referral_monthly_earnings_ibetg' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh calculateFriendReferrialMonthlyEarnings_ibetg',
		'cron' => '8 1 2 * *',
		'default_enabled' => false,
		'note' => 'Calculate monthly affiliate earnings for ibetg',
	),
	//friend referral daily log report
	'cronjob_friend_referral_daily_log' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh getFriendReferrialDailyLogs_ibetg',
		'cron' => '9 1 * * *',
		'default_enabled' => false,
		'note' => 'Calculate friend referral daily log for ibetg',
	),
	//check player referral: 12:01 daily
	'cronjob_check_referral' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh checkReferral',
		'cron' => '8 12 * * *',
		'default_enabled' => true,
		'note' => 'Check player referral',
	),
	//sync total hours : 11:00 and 23:00
	'cronjob_sync_all_total_hours' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh rebuild_total_minute_hours',
		'cron' => '40 2,5,8,11,14,17,20,23 * * *',
		'default_enabled' => true,
		'note' => 'Sync all total hours',
	),
	//last 7 days without hours: 06:00 daily
	'cronjob_rebuild_totals' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh rebuild_total_day_month_year_without_player',
		'cron' => '10 6 * * *',
		'default_enabled' => true,
		'note' => 'Rebuild all totals info',
	),
	'cronjob_sync_long_total_info' => array( //batch total daily
		'cmd' => '{OGHOME}/admin/shell/command.sh rebuild_month_year_with_player',
		'cron' => '15 8 * * *',
		'default_enabled' => true,
		'note' => 'Sync long total month/year info daily',
	),
	//last 7 days without hours: 06:00 daily
	'cronjob_rebuild_all_totals' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh rebuild_totals null null true true',
		'cron' => '10 6 * * *',
		'default_enabled' => false,
		'note' => 'Rebuild minutes, hours, days totals for the past 7 days',
	),
	//rebuild game logs: 00:01 daily
	'cronjob_rebuild_game_logs_by_timelimit' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh rebuild_game_logs_by_timelimit',
		'cron' => '11 1 * * *',
		'default_enabled' => true,
		'note' => 'Rebuild game logs daily',
	),
	'cronjob_rebuild_game_logs_last_hour' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh rebuild_game_logs_last_hour',
		'cron' => '12 * * * *',
		'default_enabled' => false,
		'note' => 'Rebuild game logs hourly',
	),
	'cronjob_rebuild_game_logs_last_2hours_without_totals' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh rebuild_game_logs_last_2hours_without_totals',
		'cron' => '12 * * * *',
		'default_enabled' => false,
		'note' => 'Rebuild game logs last 2hours without game report',
	),
	//update total deposit amount of player: 06:00 daily
	'cronjob_update_players_total_deposit_amount' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh updatePlayersTotalDepositAmount',
		'cron' => '13 0 * * *',
		'default_enabled' => true,
		'note' => 'Update players total deposit amount',
	),
	'cronjob_update_players_deposit_count' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh updatePlayerDepositCount',
		'cron' => '*/5 * * * *',
		'default_enabled' => true,
		'note' => 'Update players approved deposit count',
	),
    'cronjob_update_players_approved_deposit_count' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh updatePlayersApprovedDepositCount',
		'cron' => '*/5 * * * *',
		'default_enabled' => true,
		'note' => 'Update players approved deposit count',
	),
    'cronjob_update_players_declined_deposit_count' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh updatePlayersDeclinedDepositCount',
		'cron' => '13 * * * *',
		'default_enabled' => true,
		'note' => 'Update players declined deposit count',
	),
	'cronjob_fix_affiliate_count' => array( //fix count info on affiliates
		'cmd' => '{OGHOME}/admin/shell/command.sh fix_affiliate_count',
		'cron' => '14 5 * * *',
		'default_enabled' => true,
		'note' => 'Fix affiliate count info',
	),
	'cronjob_clear_small_negative' => array( //clear <0.01 wallet
		'cmd' => '{OGHOME}/admin/shell/command.sh clear_small_negative',
		'cron' => '16 5 * * *',
		'default_enabled' => true,
		'note' => 'Clear small negative in wallets daily',
	),
	// 'cronjob_batch_sync_balance_all' => array( // try sync balance info
	// 	'cmd' => '{OGHOME}/admin/shell/command.sh batch_sync_balance_all',
	// 	'cron' => '50 * * * *',
	// 	'default_enabled' => false,
	// 	'note' => 'Sync balance history hourly',
	// ),
	'cronjob_batch_sync_balance_hourly' => array( // try sync balance info
		'cmd' => '{OGHOME}/admin/shell/command.sh batch_sync_balance_by last_one_hour false 9999',
		'cron' => '55 * * * *',
		'default_enabled' => false,
		'note' => 'Sync balance history hourly',
	),
	'cronjob_batch_sync_balance_daily' => array( // try sync balance daily
		'cmd' => '{OGHOME}/admin/shell/command.sh batch_sync_balance_by available false 99999',
		'cron' => '50 5 * * *',
		'default_enabled' => false,
		'note' => 'Sync balance history daily',
	),
	'cronjob_adjust_level' => array( // monthly down/up level
		'cmd' => '{OGHOME}/admin/shell/command.sh adjust_level',
		'cron' => '17 6 1 * *',
		'default_enabled' => false,
		'note' => 'Down/up level monthly',
	),
	'cronjob_batch_player_level_upgrade' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh batch_player_level_upgrade 0',
		//'cron' => '0 0 * * *', //daily (midnight)
		'cron' => '55 23 * * *', // 23:55 pm
		'default_enabled' => false,
		'note' => 'Upgrade level daily',
	),
	'cronjob_batch_player_level_upgrade_hourly' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh batch_player_level_upgrade_check_hourly 0',
		'cron' => '1 * * * *', // every hour
		'default_enabled' => false,
		'note' => 'Check upgrade level hourly',
	),
	'cronjob_batch_player_level_downgrade' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh batch_player_level_downgrade',
		//'cron' => '0 0 * * *', //daily (midnight)
		'cron' => '59 23 * * *', // 23:59 pm
		'default_enabled' => false,
		'note' => 'Downgrade level daily',
	),
	'cronjob_archive_game_log' => array(
		'cmd' => '{OGHOME}/admin/shell/archive_game_log.sh',
		'cron' => '18 5 * * *',
		'default_enabled' => false,
		'note' => 'Archive game logs',
	),
	'cronjob_sync_ag_ftp' => array(
		'cmd' => '{OGHOME}/admin/shell/sync_ag_ftp.sh',
		'cron' => '* * * * *',
		'default_enabled' => false,
		'note' => 'Sync AG game logs ftp',
	),
	'cronjob_sync_entwine_ftp' => array(
		'cmd' => '{OGHOME}/admin/shell/sync_entwine_ftp.sh',
		'cron' => '* * * * *',
		'default_enabled' => false,
		'note' => 'Sync Entwine game logs ftp',
	),
	'cronjob_fix_level_history' => array( // daily
		'cmd' => '{OGHOME}/admin/shell/command.sh fix_level_history',
		'cron' => '19 0 * * *',
		'default_enabled' => false,
		'note' => 'Fix level history level',
	),
	'cronjob_friend_referral_daily_report' => array( // daily 1am
		'cmd' => '{OGHOME}/admin/shell/command.sh getFriendReferrialDailyLogs_ibetg',
		'cron' => '0 1 * * *',
		'default_enabled' => true,
		'note' => 'Generate Friend Referral Daily Report',
	),
	//sync silverpop database every midnight
	'cronjob_sync_silverpop_database' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh sync_silverpop_database',
		'cron' => '20 0 * * *',
		'default_enabled' => false,
		'note' => 'Sync silverpop database',
	),
	'cronjob_clear_clockwalk' => array( // daily
		'cmd' => '{OGHOME}/admin/shell/clear_clockwalk.sh',
		'cron' => '22 0 * * *',
		'default_enabled' => false,
		'note' => 'Clear clockwalk',
	),
	'cronjob_restart_queue_worker' => array( // daily
		'cmd' => '/bin/bash {OGHOME}/admin/shell/restart_queue_worker.sh',
		'cron' => '23 4 * * *',
		'default_enabled' => false,
		'note' => 'Restart Queue Worker',
	),
	'cronjob_kill_mysql_client' => array( // daily
		'cmd' => '/bin/bash {OGHOME}/admin/shell/kill_mysql_client.sh',
		'cron' => '24 4 * * *',
		'default_enabled' => false,
		'note' => 'Kill MySQL Client',
	),
	'cronjob_create_resp_table' => array( // daily
		'cmd' => '{OGHOME}/admin/shell/command.sh initRespTable',
		'cron' => '25 4 * * *',
		'default_enabled' => true,
		'note' => 'Create Response Table',
	),
	'cronjob_create_resp_cashier_table' => array( // monthly
		'cmd' => '{OGHOME}/admin/shell/command.sh initRespCashierTable',
		'cron' => '30 4 * * *',
		'default_enabled' => true,
		'note' => 'Create Response Cashier Table',
	),
	'cronjob_collect_sub_wallet_balance_daily' => array( // daily 5am
		'cmd' => '{OGHOME}/admin/shell/command.sh collectSubWalletBalanceDaily',
		'cron' => '26 5 * * *',
		'default_enabled' => true,
		'note' => 'Collect Sub Wallet Balance Daily',
	),
	'cronjob_clear_response_result' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh clear_response_result',
		'cron' => '27 6 * * *',
		'default_enabled' => true,
		'note' => 'Clear Response Result',
	),
	'cronjob_batch_generate_daily_balance' => array( // generate daily balance of all player
		'cmd' => '{OGHOME}/admin/shell/command.sh generateDailyBalance',
		'cron' => '50 0,11,23 * * *',
		'default_enabled' => true,
		'note' => 'Generate daily balance of player hourly',
	),
	// 'cronjob_generateDailyBalanceReport' => array( // generate daily balance by date
	// 	'cmd' => '{OGHOME}/admin/shell/command.sh generateDailyPlayerBalance',
	// 	'cron' => '5 0 * * *',
	// 	'default_enabled' => true,
	// 	'note' => 'Generate daily balance report by date',
	// ),
	'cronjob_agency_settlement_wl' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh generate_agency_settlement_wl',
		'cron' => '0 */6 * * *', # every 6 hours
		'default_enabled' => false,
		'note' => 'Calculate agent Win Loss Settlement recent data, runs every 6 hours',
	),
	'cronjob_agency_settlement_daily' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh generate_agency_settlement_daily',
		'cron' => '0 6 * * *', # daily at 6am
		'default_enabled' => false,
		'note' => 'Calculate agent Win Loss Settlement data daily',
	),
	'cronjob_generateDailyCurrencyRate' => array( // daily 1am
		'cmd' => '{OGHOME}/admin/shell/command.sh generateDailyCurrencyRate',
		'cron' => '0 1 * * *',
		'default_enabled' => false,
		'note' => 'Generate Daily Currency Rate',
	),
    'cronjob_sync_game_list' => array( // hourly
        'cmd' => '{OGHOME}/admin/shell/command.sh sync_new_games',
        'cron' => '0 * * * *',
        'default_enabled' => true,
        'note' => 'Sync New Games',
    ),
    'cronjob_sync_game_list_daily' => array(
        'cmd' => '{OGHOME}/admin/shell/command.sh sync_game_list_daily',
        'cron' => '0 1 * * *',
        'default_enabled' => true,
        'note' => 'Sync Game List daily',
    ),
    'cronjob_sync_game_list_through_api_daily' => array(
        'cmd' => '{OGHOME}/admin/shell/command.sh sync_game_list_through_api',
        'cron' => '0 1 * * *',
        'default_enabled' => false,
        'note' => 'Sync Game List Through API daily',
    ),
    'cronjob_update_player_online_status' => array( // every 5 minutes
        'cmd' => '{OGHOME}/admin/shell/command.sh update_player_online_status',
        'cron' => '*/5 * * * *',
        'default_enabled' => true,
        'note' => 'Update player online status',
    ),
	//use cronjob
	// 'cronjob_generate_dashboard' => array( // daily
	// 	'cmd' => '{OGHOME}/admin/shell/command.sh generate_admin_dashboard',
	// 	'cron' => '*/5 * * * *',
	// 	'default_enabled' => true,
	// 	'note' => 'Generate admin dashboard',
	// ),
	// 'cronjob_sync_http_request' => array( // 5 minutes
	// 	'cmd' => '{OGHOME}/admin/shell/command.sh generate_http_request_summary',
	// 	'cron' => '*/5 * * * *',
	// 	'default_enabled' => false,
	// 	'note' => 'Sync the http request to http request summary',
	// ),
    //refference from twinbet branch

	//03-08-2018 | OGP-5331
	//Super Report | super_report
	// send super_report data: daily
	'cronjob_send_super_report_data_daily' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh updateSuperReportDaily',
		'cron' => '0 0 * * *',
		'default_enabled' => false,
		'note' => 'Will send data to main site daily',
	),

	// send super_report data: hourly
	'cronjob_send_super_report_data_hourly' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh updateSuperReportHourly',
		'cron' => '0 * * * *',
		'default_enabled' => false,
		'note' => 'Will send data to main site hourly',
	),
	//hourly
	'cronjob_batch_scan_suspicious_transfer_request' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh batch_scan_suspicious_transfer_request_cronjob',
		'cron' => '0 * * * *',
		'default_enabled' => true,
		'note' => 'Batch scan suspicious transfer request',
	),
    'cronjob_sync_player_session_file_into_relay' => array( // for reference by [player_session_files]
		'cmd' => '{OGHOME}/admin/shell/command.sh cronjob_sync_player_session_file_into_relay',
		'cron' => '0 3 * * *',  //daily ( 3:00 )
		'default_enabled' => false,
		'note' => 'sync player session file into relay table',
	),
	// 'cronjob_clear_short_admin_sessions' => array(
	// 	'cmd' => '{OGHOME}/admin/shell/command.sh clearShortAdminSessions',
	// 	'cron' => '29 * * * *',
	// 	'default_enabled' => true,
	// 	'note' => 'Clear short admin timeout sessions',
	// ),
	//batch update current bet from withdraw condition 0:15am
	'cronjob_batch_update_bet_from_withdraw_condition' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh batch_update_bet_from_withdraw_condition',
		'cron' => '15 0 * * *',
		'default_enabled' => false,
		'note' => 'Batch update current bet from withdrawal condition yesterday',
	),
	//batch update current bet from withdraw condition hourly
	'cronjob_batch_update_bet_from_withdraw_condition_hourly' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh batch_update_bet_from_withdraw_condition_hourly',
		'cron' => '31 * * * *',
		'default_enabled' => false,
		'note' => 'Batch update current bet from withdrawal condition hourly',
	),
	'cronjob_clear_timeout_common_tokens' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh clear_timeout_common_tokens',
		'cron' => '32 3 * * *',
		'default_enabled' => true,
		'note' => 'Clear timeout tokens daily',
	),
	'cronjob_sync_sa_gaming_api' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh sync_sa_gaming_api',
		'cron' => '20,40 0 * * *',
		'default_enabled' => false,
		'note' => 'Sync SA Gaming Api Daily',
	),
    //generate affiliate static data
    'cronjob_generate_affiliate_statistics' => array(
        'cmd' => '{OGHOME}/admin/shell/command.sh generateAffiliateStatistics',
        'cron' => '0 */6 * * *',
        'default_enabled' => false,
        'note' => 'Every 6 hours, affiliate statistics report will be generated',
    ),
    //generate affiliate static data
    'cronjob_generate_affiliate_statistics_hourly' => array(
        'cmd' => '{OGHOME}/admin/shell/command.sh generateAffiliateStatisticsHourly',
        'cron' => '0 * * * *',
        'default_enabled' => false,
        'note' => 'Every 1 hour, affiliate statistics report will be generated',
    ),
	// Daily Income Access Reports Upload to SFTP server
	'cronjob_upload_daily_income_access_report' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh upload_daily_income_access_report',
		'cron' => '0 * * * *',
		'default_enabled' => false,
		'note' => 'Upload Income Access Daily Registration and Sales Report to specified SFTP server',
	),
	'cronjob_regenerate_t1_players_password' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh regenerate_and_sync_t1_players_password',
		'cron' => '0 6 1 * *', //montly (1st day of the month at 6am )
		'default_enabled' => true,
		'note' => 'Will regenerate t1 players passwords monthly',
	),
	//refresh total daily deposit amount of all payment accounts
	'cronjob_refresh_all_payment_accounts_total_daily_deposit_amount_daily' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh refresh_all_payment_accounts_total_daily_deposit_amount_daily',
		'cron' => '5 0 * * *', //daily (00:05 am)
		'default_enabled' => true,
		'note' => 'Will refresh total daily deposit amount of all payment accounts daily',
	),

	'cronjob_refresh_all_payment_accounts_total_daily_deposit_count_daily' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh refresh_all_payment_accounts_total_daily_deposit_count_daily',
		'cron' => '10 0 * * *', //daily (00:10 am)
		'default_enabled' => true,
		'note' => 'Will refresh total daily deposit count of all payment accounts daily',
	),

	//refresh total daily deposit amount of all payment accounts hourly
	'cronjob_refresh_all_payment_accounts_total_daily_deposit_amount_hourly' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh refresh_all_payment_accounts_total_daily_deposit_amount_daily',
		'cron' => '3 * * * *', //hourly (At minute 3.)
		'default_enabled' => false,
		'note' => 'Will refresh the total daily deposit amount of all payment accounts hourly',
	),

	'cronjob_ole777_wagers_calc' => array(
		'cmd' => '{OGHOME}/admin/shell/ole777.sh wagers_calc',
		'cron' => '30 12 * * *', // Daily (12:30)
		'default_enabled' => false,
		'note' => 'OLE777 only: calculate today\'s wager records for reward system',
	),
	'cronjob_ole777_wagers_sync' => array(
		'cmd' => '{OGHOME}/admin/shell/ole777.sh wagers_sync',
		'cron' => '5 17 * * *', // Daily (17:05)
		'default_enabled' => false,
		'note' => 'OLE777 only: sync checked wager records to reward system',
	),
	'cronjob_ole777_userinfo_sync' => array(
		'cmd' => '{OGHOME}/admin/shell/ole777.sh userinfo_dailysync',
		'cron' => '0 17 * * *', // Daily (17:00)
		'default_enabled' => false,
		'note' => 'OLE777 only: sync today\'s changes of player profiles to reward system',
	),
	'cronjob_refresh_players_dispatch_account_level' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh refreshPlayersDispatchAccountLevel',
		'cron' => '0 2 * * *', // Daily (02:00)
		'default_enabled' => false,
		'note' => 'refresh dispatch account level of all players',
	),
	'cronjob_generate_oneworks_game_report' => array( // every 10 mins
		'cmd' => '{OGHOME}/admin/shell/command.sh generate_oneworks_report_daily',
		'cron' => '*/10 * * * *',
		'default_enabled' => false,
		'note' => 'Generate Oneworks Game Report',
	),
	'cronjob_generate_kingrich_summary_report_hourly' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh generate_kingrich_summary_report_hourly',
		'cron' => '0 * * * *',
		'default_enabled' => false,
		'note' => 'Generate Kingrich Summary Report Hourly',
	),
	'cronjob_set_game_maintenance_schedule_in_maintenance' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh set_game_maintenance_schedule_in_maintenance',
		'cron' => '*/2 * * * *', // every 2 mins
		'default_enabled' => false,
		'note' => 'Game Maintenance Schedule In Maintenance',
	),
	// Sync or Update all players withrawal and deposit total infos under player table
	'cronjob_syncAllPlayersWithdrawAndDepositRelatedFields' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh syncAllPlayersWithdrawAndDepositRelatedFields',
		'cron' => '0 * * * *', // -- every hour
		'default_enabled' => false,
		'note' => 'Sync all players\' total withdraw and total deposit information every 1 hour',
	),
	'cronjob_generate_sbobet_game_report_daily' => array( // every 8 mins
		'cmd' => '{OGHOME}/admin/shell/command.sh generate_sbobet_game_report_daily',
		'cron' => '*/8 * * * *',
		'default_enabled' => false,
		'note' => 'Generate SBObet Sports Game Report',
	),
	//10 minutes
	'cronjob_generate_player_report_hourly' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh generate_player_report_hourly',
		'cron' => '5,35 * * * *',
		'default_enabled' => true,
		'note' => 'Generate player report hourly',
	),
	'cronjob_generate_summary2_report_daily' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh generate_summary2_report_daily',
		'cron' => '0,10,20,30,40,50 * * * *',
		'default_enabled' => true,
		'note' => 'Generate summary 2 report daily',
	),
	'cronjob_generate_all_report_daily' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh generate_all_report_daily',
		'cron' => '33 4 * * *',
		'default_enabled' => true,
		'note' => 'Resync yesterday all report',
	),
	'cronjob_delete_table_data_daily' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh cron_daily_delete_data',
		'cron' => '0 6 * * *', //everyday at 6am
		'default_enabled' => false,
		'note' => 'Will delete designated table data by date field (should configure table_deletions_settings in config on og_sync)',
	),
	'cronjob_service_status_checker' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh service_status_checker',
		'cron' => '*/5 * * * *',
		'default_enabled' => true,
		'note' => 'Check service status',
	),
	'cronjob_resync_ipm_unsettle_game_records' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh resync_ipm_unsettle_game_records',
		'cron' => '*/10 * * * *', // every 10 mins
		'default_enabled' => false,
		'note' => 'Resync IPM Unsettled Records',
	),
    'cronjob_generate_duplicate_account_report' => array(
        'cmd' => '{OGHOME}/admin/shell/command.sh updateDupReport',
        'cron' => '0 6 * * *',
        'default_enabled' => false,
        'note' => 'Generate Duplicate Account Report',
    ),
    'cronjob_sync_incomplete_games' => array(
        'cmd' => '{OGHOME}/admin/shell/command.sh sync_incomplete_games',
        'cron' => '*/2 * * * *',
        'default_enabled' => false,
        'note' => 'Sync Incomplete Games',
    ),
    //generate kingrich send data schedule every 5 minutes
    'cronjob_generate_kingrich_send_data_schedule' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh generate_kingrich_send_data_schedule',
		'cron' => '*/5 * * * *',
		'default_enabled' => false,
		'note' => 'Generate Kingrich Summary Send Data Schedule',
	),
	##generate VR game reports daily
	'cronjob_generate_vr_game_report' => array( // every 10 mins
		'cmd' => '{OGHOME}/admin/shell/command.sh generate_vr_report_daily',
		'cron' => '*/10 * * * *',
		'default_enabled' => false,
		'note' => 'Generate VR Game Report',
	),

	#generate report for agency agents
	'cronjob_generate_agency_agent_report' => array( // every 1.05 hr
		'cmd' => '{OGHOME}/admin/shell/command.sh generate_agency_agent_report',
		'cron' => '5 * * * *',
		'default_enabled' => false,
		'note' => 'Generate Agency Agent Summary Report',
	),

	// sync player_relay for player who Not in player_relay table
	'cronjob_sync_newplayer_into_player_relay' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh sync_new_player_player_relay 999999999', // params, limit
		'cron' => '*/7 * * * *', // every 7 min: 7,14,21,28,35,42,49,56 of hour.
		'default_enabled' => false,
		'note' => 'sync player_relay table for Conversion Rate Report(for new player)',
	),
	// sync player_relay the player already in player_relay table
	'cronjob_sync_exists_player_in_player_relay' => array( // every 1.05 hr
		'cmd' => '{OGHOME}/admin/shell/command.sh sync_exists_player_player_relay 999999999', // params, limit
		'cron' => '*/23 * * * *', // about half a hour: 23,46 min of hour
		'default_enabled' => false,
		'note' => 'sync player_relay table for Conversion Rate Report(for exists player)',
	),
	/// 這會有正在更新中A欄位，B欄位因為共用 sync_last_update_at，而變成順序較低的問題。
	// 所以若有需要再開發，針對各自欄位的同步頻率區隔。
	'cronjob_generate_afb88_game_report' => array( // every 10 mins
		'cmd' => '{OGHOME}/admin/shell/command.sh generate_afb88_report_daily',
		'cron' => '*/10 * * * *',
		'default_enabled' => false,
		'note' => 'Generate afb88 Game Report',
	),
    'cronjob_fetch_solid_gaming_gamelist' => array(
        'cmd' => '{OGHOME}/admin/shell/command.sh fetch_solid_gaming_gamelist',
        'cron' => '30 9 * * 1', // Every Monday at 09:30
        'default_enabled' => false,
        'note' => 'Fetch Solid Gaming gamelist',
    ),
    'cronjob_fetch_gamelist_from_provider' => array(
        'cmd' => '{OGHOME}/admin/shell/command.sh fetch_gamelist_from_provider',
        'cron' => '30 9 * * 1', // Every Monday at 09:30
        'default_enabled' => false,
        'note' => 'Fetch Gamelist from Game Provider',
    ),
    'cronjob_generate_transactions_daily_summary_report' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh generate_transactions_daily_summary_report',
		'cron' => '0 * * * *',
		'default_enabled' => true,
		'note' => 'Generate data for transactions daily summary report',
	),
	'cronjob_generate_quickfire_game_report' => array( // daily
		'cmd' => '{OGHOME}/admin/shell/command.sh generate_quickfire_report',
		'cron' => '0 0 * * *',
		'default_enabled' => false,
		'note' => 'Generate Quickfire Game Report',
	),
	//minutely
	'cronjob_batch_scan_timeout_transfer_request_then_go_maintenance' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh batch_scan_timeout_transfer_request_then_go_maintenance_cronjob',
		'cron' => '* * * * *',
		'default_enabled' => false,
		'note' => 'Batch scan timeout transfer request then go maintenance',
	),
	//every 15 minutes
	'cronjob_get_transaction_timeout_transfer_request' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh get_transaction_timeout_transfer_request_cronjob',
		'cron' => '0,15,30,45 * * * *',
		'default_enabled' => false,
		'note' => 'Get timeout transfer request',
	),
	'cronjob_get_transaction_timeout_transfer_request_every_minute' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh get_transaction_timeout_transfer_request_cronjob',
		'cron' => '* * * * *',
		'default_enabled' => false,
		'note' => 'Get timeout transfer request every minute',
	),
	'cronjob_aff_dashboard_calc' => [
		'cmd' => '{OGHOME}/admin/shell/command.sh aff_dashboard_calc',
		'cron' => '35 7,12,17 * * *', // 7:35, 12:35, 17:35
		'default_enabled' => false,
		'note' => 'Updates affiliate dashboards every 5 hours',
	],
	'cronjob_generate_top_bet_players_list' => [
		'cmd' => '{OGHOME}/admin/shell/command.sh generate_top_bet_players_list',
		'cron' => '0 12 * * 1', // Every Monday at 12:00
		'default_enabled' => false,
		'note' => 'Generate top betting amount player list',
	],
	#OGP-20623  payment and player abnormal warning report
	'cronjob_generate_payment_abnormal_history' => [
		'cmd' => '{OGHOME}/admin/shell/command.sh generate_payment_abnormal_history',
		'cron' => '0 * * * *', //hourly
		'default_enabled' => false,
		'note' => 'Generate payment abnormal warning history',
	],
	'cronjob_generate_player_abnormal_history' => [
		'cmd' => '{OGHOME}/admin/shell/command.sh generate_player_abnormal_history',
		'cron' => '0 * * * *', //hourly
		'default_enabled' => false,
		'note' => 'Generate player abnormal warning history',
	],
	// OGP-19329 every 5 minutes check for suspicious withdrawal
	'cronjob_check_suspicious_withdrawal' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh cronjob_check_suspicious_withdrawal',
		'cron' => '*/5 * * * *',
		'default_enabled' => false,
		'note' => 'Check suspicious withdrawal doubled amount and duplicate withdrawal',
	),
	// OGP-19333 every hour run bet to points
	'cronjob_calculate_bet_to_points_hourly' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh calculate_bet_to_points_hourly',
		'cron' => '0 * * * *',
		'default_enabled' => false,
		'note' => 'Compute bet to points hourly',
	),
	'cronjob_generate_games_report_timezone' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh generate_games_report_timezone',
		'cron' => '0,15,30,45 * * * *',
		'default_enabled' => false,
		'note' => 'Generate games report timezone',
	),
	'cronjob_check_seamless_error_logs' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh get_seamless_error_logs_cronjob',
		'cron' => '*/5 * * * *',
		'default_enabled' => false,
		'note' => 'Check seamless error logs.',
	),
	// every 1 hour, delete external_common_token
	'cronjob_deletion_of_external_common_tokens' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh batchDeleteOfExternalCommonToken',
		'cron' => '1 * * * *',
		'default_enabled' => true,
		'note' => 'Deletion of external_common_token every hour for past 3 days',
	),
	//minutely
	'cronjob_get_transaction_timeout_transfer_request_by_cost_ms_cronjob' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh get_transaction_timeout_transfer_request_by_cost_ms_cronjob',
		'cron' => '* * * * *',
		'default_enabled' => false,
		'note' => 'Get timeout transfer timeout request by cost ms.',
	),
	'cronjob_sync_mgquickfire_livedealer_gamelogs' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh sync_mgquickfire_livedealer_gamelogs_cronjob',
		'cron' => '*/5 * * * *',
		'default_enabled' => false,
		'note' => 'Sync MG Quickfire livedealer gamelogs',
	),

	//check amb poker seamless bet status
	'cronjob_check_seamless_round_status' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh check_seamless_round_status',
		'cron' => '*/5 * * * *',
		'default_enabled' => false,
		'note' => 'Check seamless round status',
	),
	'cronjob_init_balance_monthly_table' => array( // monthly
		'cmd' => '{OGHOME}/admin/shell/command.sh init_balance_monthly_table',
		'cron' => '30 4 * * *',
		'default_enabled' => true,
		'note' => 'Init Balance Monthly Table',
	),

	'cronjob_batch_supplement_run_process_pre_checker_in_queue' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh batch_supplement_run_process_pre_checker_in_queue',
		'cron' => '3,13,23,33,43,53 * * * *', // 23:59 pm
		'default_enabled' => false,
		'note' => 'Batch supplement execute No trigged processPreCheckers in the queue .',
	),
	'cronjob_rebuild_seamless_balance_history' => array(
        'cmd' => '{OGHOME}/admin/shell/command.sh rebuild_seamless_balance_history',
        'cron' => '*/10 * * * *',
        'default_enabled' => false,
        'note' => 'Rebuild seamless balance report',
    ),
    'cronjob_exec_auto_check_withdraw_condition' => array(
        'cmd' => '{OGHOME}/admin/shell/command.sh execute_auto_check_withdraw_condition',
        'cron' => '30 2 * * *',
        'default_enabled' => false,
        'note' => 'Will auto check withdraw condition',
    ),
    #OGP-22346
	'cronjob_auto_decline_pending_deposit_request' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh auto_decline_pending_deposit_request',
		'cron' => '0 * * * *', //hourly
		'default_enabled' => false,
		'note' => 'Auto decline deposit requests that have been [Pending] for longer than specific time',
	),
	#OGP-23405
	'cronjob_auto_apply_and_release_bonus_for_customize_promo' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh auto_apply_and_release_bonus_for_customize_promo',
		'cron' => '*/3 * * * *', //every 3 mins
		'default_enabled' => false,
		'note' => 'Auto apply and release bonus for customize promo',
	),
    #OGP-25552
    'cronjob_auto_apply_bonus_for_ole777th_consecutive_deposit_bonus' => array(
        'cmd' => '{OGHOME}/admin/shell/command.sh auto_apply_bonus_for_ole777th_consecutive_deposit_bonus',
        'cron' => '0 0 10 * *', // 12:00 a.m. on the 10th of every month
        'default_enabled' => false,
        'note' => 'Auto apply bonus for consecutive deposit monthly',
    ),#OGP-26866
    'cronjob_auto_release_bonus_for_ole777th_consecutive_deposit_bonus' => array(
        'cmd' => '{OGHOME}/admin/shell/command.sh auto_release_bonus_for_ole777th_consecutive_deposit_bonus',
        'cron' => '0 10 10 * *', // 10:00 a.m. on the 10th of every month
        'default_enabled' => false,
        'note' => 'Auto release bonus for consecutive deposit monthly',
    ),
	'cronjob_monitor_player_login_via_same_ip' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh monitorManyPlayerLoginViaSameIp',
		'cron' => '9,19,29,39,49,59 * * * *',
		'default_enabled' => false,
		'note' => 'Monitor multiple players logging in via the same IP.',
	),
	// OGP-23159
	'cronjob_alert_suspicious_player_login' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh alert_suspicious_player_login',
		'cron' => '*/30 * * * *',
		'default_enabled' => false,
		'note' => 'Alert suspicious player login',
	),
	//OGP-23546
	'cronjob_generate_summary2_report_montly' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh generate_summary2_report_monthly',
		'cron' => '0,10,20,30,40,50 * * * *',
		'default_enabled' => true,
		'note' => 'Generate summary 2 report monthly',
	),
	'cronjob_init_admin_logs_monthly_table' => array( // monthly
		'cmd' => '{OGHOME}/admin/shell/command.sh init_admin_logs_monthly_table',
		'cron' => '31 4 * * *',
		'default_enabled' => true,
		'note' => 'Init Admin Logs Monthly Table',
	),

	'cronjob_clear_cooldown_expired_in_player_center_api' => array( // monthly
		'cmd' => '{OGHOME}/admin/shell/command.sh clear_cooldown_expired_in_player_center_api',
		'cron' => '33 * * * *',
		'default_enabled' => false,
		'note' => 'Clear the cooldown expired data in player_center_api',
	),
	'cronjob_sync_failed_transactions_and_update' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh cronjob_sync_failed_transactions_and_update',
		'cron' => '*/10 * * * *', // every 10 minutes
		'default_enabled' => false,
		'note' => 'Sync failed transactions and update.',
	),
    'cronjob_sync_player_rank_with_score_f' => array(
        'cmd' => '{OGHOME}/admin/shell/command.sh syncPlayerRankWithScore',
        'cron' => '*/5 * * * *',
        'default_enabled' => false,
        'note' => 'Sync Player Rank With Score every 5min',
    ),
	'cronjob_sync_player_rank_with_score' => array(
        'cmd' => '{OGHOME}/admin/shell/command.sh syncPlayerRankWithScore',
        'cron' => '*/20 * * * *',
        'default_enabled' => false,
        'note' => 'Sync Player Rank With Score every 20min',
    ),
	#OGP-25340
	'cronjob_auto_apply_and_release_bonus_for_smash_newbet' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh auto_apply_and_release_bonus_for_smash_newbet',
		'cron' => '3 1 * * *', //every 3 mins
		'default_enabled' => false,
		'note' => 'Auto apply and release bonus for Newbet Bonus',
	),
    #OGP-25984
    'cronjob_auto_apply_and_release_bonus_for_smash_sportsbet' => array(
        'cmd' => '{OGHOME}/admin/shell/command.sh auto_apply_and_release_bonus_for_smash_sportsbet',
        'cron' => '0 12 * * *', //every monday 03:00am
        'default_enabled' => false,
        'note' => 'Auto apply and release bonus for Sports Bonus',
    ),
    #OGP-25981
    'cronjob_auto_apply_for_ole777idr_total_losses_weekly' => array(
        'cmd' => '{OGHOME}/admin/shell/command.sh auto_apply_for_ole777idr_total_losses_weekly',
        'cron' => '0 10 * * 1', //every monday 10:00
        'default_enabled' => false,
        'note' => 'Auto apply for Total Losses Weekly Bonus',
    ),
    #OGP-33467
    'cronjob_auto_apply_and_release_bonus_for_alpha_bet_weekly' => array(
        'cmd' => '{OGHOME}/admin/shell/command.sh auto_apply_and_release_bonus_for_alpha_bet_weekly',
        'cron' => '0 15 * * 1', //every monday 15:00
        'default_enabled' => false,
        'note' => 'Auto apply for Alpha Weekly Rebate Bonus',
    ),
	#OGP-31961
    'cronjob_auto_apply_for_ole777idr_live_dealer_total_losses_weekly' => array(
        'cmd' => '{OGHOME}/admin/shell/command.sh auto_apply_for_ole777idr_live_dealer_total_losses_weekly',
        'cron' => '0 10 * * 1', //every monday 10:00
        'default_enabled' => false,
        'note' => 'Auto apply for Live Dealer Total Losses Weekly Bonus',
    ),
    'cronjob_auto_release_bonus_for_ole777idr_live_dealer_total_losses_weekly' => array(
        'cmd' => '{OGHOME}/admin/shell/command.sh auto_release_bonus_for_ole777idr_live_dealer_total_losses_weekly',
        'cron' => '0 14 * * 1', //every monday 14:00
        'default_enabled' => false,
        'note' => 'Auto release bonus for Live Dealer Total Losses Weekly Bonus',
    ),
    #OGP-33784
    'cronjob_auto_apply_and_release_bonus_for_amusino_friend_referral' => array(
        'cmd' => '{OGHOME}/admin/shell/command.sh auto_apply_and_release_bonus_for_amusino_friend_referral',
        'cron' => '0 4 * * *', //everyday at 04:00
        'default_enabled' => false,
        'note' => 'Auto apply for Amusino Friend Referral Daily Bonus',
    ),
    #OGP-31871
    'cronjob_auto_apply_and_release_bonus_for_king_total_losses_weekly_bonus' => array(
        'cmd' => '{OGHOME}/admin/shell/command.sh auto_apply_and_release_bonus_for_king_total_losses_weekly_bonus',
        'cron' => '0 0 * * 1', //every monday 10:00
        'default_enabled' => false,
        'note' => 'Auto apply for King Total Losses Weekly Bonus',
    ),
    #OGP-26516
    'cronjob_auto_release_bonus_for_ole777idr_total_losses_weekly' => array(
        'cmd' => '{OGHOME}/admin/shell/command.sh auto_release_bonus_for_ole777idr_total_losses_weekly',
        'cron' => '0 14 * * 1', //every monday 14:00
        'default_enabled' => false,
        'note' => 'Auto release bonus for Total Losses Weekly Bonus',
    ),
	'cronjob_automation_batch_send_internal_msg_for_OGP24282' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh automation_batch_send_internal_msg_for_OGP24282',
		'cron' => '14 2 1 * *',
		'default_enabled' => false,
		'note' => 'Automation batch send internal msg for OGP24282.',
	),
	'cronjob_auto_enable_player_withdrawal_after_datetime_limit' => array( // hourly
		'cmd' => '{OGHOME}/admin/shell/command.sh auto_enable_player_withdrawal_after_datetime_limit',
		'cron' => '0 * * * *',
		'default_enabled' => false,
		'note' => 'Check and update player withdrawal status hourly',
	),
	'cronjob_check_new_game_and_add_to_quest_tree' => array( // At every 15th minute
		'cmd' => '{OGHOME}/admin/shell/command.sh checkNewGameAndAddToQuest',
		'cron' => '*/15 * * * *',
		'default_enabled' => false,
		'note' => 'Check new game and add to quest manager game tree',
	),
	'cronjob_check_new_game_and_add_to_promorules_tree' => array( // At every 15th minute
		'cmd' => '{OGHOME}/admin/shell/command.sh checkNewGameAndAddToPromorules',
		'cron' => '*/15 * * * *',
		'default_enabled' => false,
		'note' => 'Check new game and add to promorules game tree',
	),
	'cronjob_check_new_game_and_add_to_vip_cashback_tree' => array( // At every 15th minute
		'cmd' => '{OGHOME}/admin/shell/command.sh checkNewGameAndAddToVipCashback',
		'cron' => '*/15 * * * *',
		'default_enabled' => false,
		'note' => 'Check new game and add to vip cashback tree',
	),
	'cronjob_auto_get_usdt_crypto_currency_rate' => array( // hourly
		'cmd' => '{OGHOME}/admin/shell/command.sh auto_get_usdt_crypto_currency_rate',
		'cron' => '10 * * * *',
		'default_enabled' => false,
		'note' => 'Get USDT Crypto Currency Rate hourly',
	),
	//untagged new game: every day at 00:00
	'cronjob_untagged_new_game' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh untagged_new_games',
		'cron' => '0 0 * * *',
		'default_enabled' => true,
		'note' => 'Untagged new game',
	),

    # OGP-28038
	'cronjob_send_data_to_getreponse_hourly' => array( // hourly
		'cmd' => '{OGHOME}/admin/shell/command.sh send_data_to_getreponse_hourly',
		'cron' => '30 * * * *',
		'default_enabled' => false,
		'note' => 'Send data to get response hourly',
	),
	'cronjob_send_data_to_getreponse' => array( // every 5th minutes
		'cmd' => '{OGHOME}/admin/shell/command.sh send_data_to_getreponse',
		'cron' => '*/5 * * * *',
		'default_enabled' => false,
		'note' => 'Send data to get response hourly',
	),

	'cronjob_generatate_game_tournament_winners' => array( // every 15 mins
		'cmd' => '{OGHOME}/admin/shell/command.sh generatate_game_tournament_winners',
		'cron' => '*/15 * * * *',
		'default_enabled' => false,
		'note' => 'Generate Tournaments Winners Report',
	),
	// Send Internal Message For First Deposit
	'cronjob_batch_send_internal_msg_for_new_player_OGP28228' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh batch_send_internal_msg_for_new_player_OGP28228', // params, limit
		'cron' => '22 1 * * *', // every day at 01:22
		'default_enabled' => false,
		'note' => 'Send Internal Message For First Deposit And New Register',
	),
	#OGP-28853
	'cronjob_auto_apply_and_release_bonus_for_sssbet_friend_referral' => array(
        'cmd' => '{OGHOME}/admin/shell/command.sh auto_apply_and_release_bonus_for_sssbet_friend_referral',
        'cron' => '0 12 * * *', // every day at 12:00
        'default_enabled' => false,
        'note' => 'Auto apply for friend referral bonus daily in sssbet',
    ),
	#OGP-32537
	'cronjob_sync_sssbet_friend_referral_depositors_count' => array(
        'cmd' => '{OGHOME}/admin/shell/command.sh sync_sssbet_friend_referral_depositors_count',
        'cron' => '0 0 * * *', // every day at 00:00
        'default_enabled' => false,
        'note' => 'Sync friend referral depositors in sssbet',
    ),

	'cronjob_clear_recent_game' => array(
        'cmd' => '{OGHOME}/admin/shell/command.sh cronjob_clear_recent_game',
        'cron' => '0 12 * * *',
        'default_enabled' => false,
        'note' => 'Clear recent game play history of active players',
    ),
    'cronjob_auto_apply_for_t1bet_total_losses_weekly' => array(
        'cmd' => '{OGHOME}/admin/shell/command.sh auto_apply_for_t1bet_total_losses_weekly',
        'cron' => '0 12 * * 1', //every monday 12:00
        'default_enabled' => false,
        'note' => 'Auto apply for Total Losses Weekly Bonus in t1bet',
    ),
    'cronjob_auto_apply_and_release_bonus_for_t1bet_total_revenue_daily_bonus' => array(
        'cmd' => '{OGHOME}/admin/shell/command.sh auto_apply_and_release_bonus_for_t1bet_total_revenue_daily_bonus',
        'cron' => '30 11 * * *', //every day 11:30 am
        'default_enabled' => false,
        'note' => 'Auto apply and release for Total Revenue Daily Bonus in t1bet',
    ),
    'cronjob_auto_apply_and_release_bonus_for_ole777th_friend_referral' => array(
        'cmd' => '{OGHOME}/admin/shell/command.sh auto_apply_and_release_bonus_for_ole777th_friend_referral',
        'cron' => '0 12 * * *', // every day at 12:00
        'default_enabled' => false,
        'note' => 'Auto apply for friend referral bonus daily in ole777th',
    ),
    'cronjob_auto_apply_and_release_bonus_for_king_referral_daily_bonus' => array(
        'cmd' => '{OGHOME}/admin/shell/command.sh auto_apply_and_release_bonus_for_king_referral_daily_bonus',
        'cron' => '0 0 * * *', // every day at 00:00
        'default_enabled' => false,
        'note' => 'Auto apply for King Referral Daily Bonus',
    ),
    #OGP-30065
    'cronjob_auto_apply_and_release_bonus_for_t1bet_referral_program' => array(
        'cmd' => '{OGHOME}/admin/shell/command.sh auto_apply_and_release_bonus_for_t1bet_referral_program',
        'cron' => '0 12 * * *', // every day at 12:00
        'default_enabled' => false,
        'note' => 'Auto apply for friend referral bonus daily',
    ),
    #OGP-30847
    'cronjob_auto_apply_and_release_bonus_for_t1bet_weekly_deposit_bonus' => array(
        'cmd' => '{OGHOME}/admin/shell/command.sh auto_apply_and_release_bonus_for_t1bet_weekly_deposit_bonus',
        'cron' => '0 1 * * 1', // every monday at 01:00
        'default_enabled' => false,
        'note' => 'Auto apply for weekly deposit bonus in t1bet',
    ),
    #OGP-29529
    'cronjob_auto_apply_for_t1bet_sports_total_losses_weekly' => array(
        'cmd' => '{OGHOME}/admin/shell/command.sh auto_apply_for_t1bet_sports_total_losses_weekly',
        'cron' => '0 23 * * 1', //every monday 23:00
        'default_enabled' => false,
        'note' => 'Auto apply for Sports Total Losses Weekly Bonus in t1bet',
    ),
	#OGP-30774
	'cronjob_auto_generate_friend_referral_roulette_bonus' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh auto_generate_friend_referral_roulette_bonus',
		'cron' => '0 10 * * *', // every day at 10:00
		'default_enabled' => false,
		'note' => 'Auto generate friend referral roulette bonus',
	),
	'cronjob_auto_apply_and_release_t1t_common_bonus' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh auto_apply_and_release_t1t_common_bonus',
		'cron' => '0 8 * * *', // every day at 08:00
		'default_enabled' => false,
		'note' => 'Auto apply for t1t common bonus',
	),
	'cronjob_auto_apply_and_release_t1t_common_brazil_referral_daily_bonus' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh auto_apply_and_release_bonus_for_t1t_common_brazil_referral_daily_bonus',
		'cron' => '5 * * * *', // every hour on 5 minutes
		'default_enabled' => false,
		'note' => 'Auto apply for t1t common referral bonus',
	),
	//OGP-31185
	'cronjob_sync_game_events' => array(
        'cmd' => '{OGHOME}/admin/shell/command.sh sync_game_events',
        'cron' => '0 * * * *',
        'default_enabled' => false,
        'note' => 'Sync game events',
    ),
	//OGP-31185
	'cronjob_update_promorule_release_bonus_count' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh cronjob_update_promorule_release_bonus_count',
		'cron' => '*/15 * * * *',
		'default_enabled' => false,
		'note' => 'Update promorule release count',
	),
	//OGP-31294
	'cronjob_generate_lucky_code' => array(
        'cmd' => '{OGHOME}/admin/shell/command.sh generate_lucky_code',
        'cron' => '2 * * * *',
        'default_enabled' => false,
        'note' => 'Generate Lucky Code',
    ),
	'cronjob_auto_get_exchange_rate_from_api' => array( // hourly
		'cmd' => '{OGHOME}/admin/shell/command.sh auto_get_exchange_rate_for_master_currency',
		'cron' => '10 * * * *',
		'default_enabled' => false,
		'note' => 'Get Exchange Rate hourly',
	),
	'cronjob_generate_game_summary_data' => array(
        'cmd' => '{OGHOME}/admin/shell/command.sh generateSummaryGameTotalBet',
        'cron' => '0 1 * * *',
        'default_enabled' => false,
        'note' => 'Generate Game Summary Data',
    ),
	'cronjob_generatePlayerQuestState' => array( // hourly
		'cmd' => '{OGHOME}/admin/shell/command.sh generatePlayerQuestState _null _null _null',
		'cron' => '45 * * * *',
		'default_enabled' => false,
		'note' => 'Generateet Player Quset State',
	),
	'cronjob_sync_outlet_agent_list' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh sync_outlet_agent_list',
		'cron' => '10 8,20 * * *', // every day at 08:10, 20:10
		'default_enabled' => false,
		'note' => 'Sync Outlet Agent List',
	),
	'cronjob_rebuild_game_biling_report' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh rebuild_game_biling_report',
		'cron' => '59 23 * * *', // At 23:59
		'default_enabled' => false,
		'note' => 'Rebuild Game Billing Report',
	),
	'cronjob_generateGamelogsExportLinks' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh generateGamelogsExportLinks',
		'cron' => '0 * * * *', // At minute 0.
		'default_enabled' => false,
		'note' => 'Generate gamelogs export links hourly',
	),
	'cronjob_deleteOldGamelogsExportedFiles' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh deleteOldGamelogsExportedFiles',
		'cron' => '0 1,13 * * *', // every 1AM and 1PM
		'default_enabled' => false,
		'note' => 'Delete old gamelogs exported files',
	),
	'cronjob_sync_player_last_played' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh sync_player_last_played_cronjob',
		'cron' => '0,15,30,45 * * * *',
		'default_enabled' => false,
		'note' => 'Sync player last played via game logs stream',
	),
  	'cronjob_sync_total_game_transaction_monthly' => array(
		'cmd' => '{OGHOME}/admin/shell/command.sh sync_total_game_transaction_monthly',
		'cron' => '59 23 * * *',
		'default_enabled' => false,
		'note' => 'Sync player life time data',
	),
    'cronjob_process_transferring_sale_orders' => array(
        'cmd' => '{OGHOME}/admin/shell/command.sh process_transferring_to_queue_approve _null _null',
        'cron' => '30 * * * *', //every hour
        'default_enabled' => false,
        'note' => 'Process transferring sale orders every hour',
    ),
);


$config['export_gamelogs_thru_cron_settings'] = [
        // 'is_disable' => true,
        // 'mattermost_user'=> 'GW001 Export Data',
        // 'mattermost_key' => 'gw001_gamelogs_export',
        // 'client_csv_download_base_url' => 'http://admin.og.local',
        // 'export_times' => [
        //     ['from'=> '00:00:00' , 'to'=> '05:59:59' , 'fromYesterday'=> true, 'untilToday'=>false],
        //     ['from'=> '06:00:00' , 'to'=> '11:59:59' , 'fromYesterday'=> true, 'untilToday'=>false],
        //     ['from'=> '12:00:00' , 'to'=> '17:59:59' , 'fromYesterday'=> true, 'untilToday'=>false],
        //     ['from'=> '18:00:00' , 'to'=> '23:59:59' , 'fromYesterday'=> true, 'untilToday'=>false],
        // ],
        // 'param_json_template' => '[{"extra_search":{"0":{"name":"game_description_id","value":""},"1":{"name":"by_date_type","value":"1"},"2":{"name":"by_date_from","value":""},"3":{"name":"by_date_to","value":""},"4":{"name":"timezone","value":"+8"},"5":{"name":"by_group_level","value":""},"6":{"name":"by_username_match_mode","value":"2"},"7":{"name":"by_username","value":""},"8":{"name":"by_affiliate","value":""},"9":{"name":"by_game_code","value":""},"10":{"name":"by_game_platform_id","value":""},"11":{"name":"round_no","value":""},"12":{"name":"by_game_flag","value":"1"},"13":{"name":"by_bet_type","value":"1"},"14":{"name":"by_amount_from","value":""},"15":{"name":"by_amount_to","value":""},"16":{"name":"by_bet_amount_from","value":""},"17":{"name":"by_bet_amount_to","value":""},"18":{"name":"agency_username","value":""},"19":{"name":"game_type_id","value":""},"target_func_name":"gamesHistory"},"export_format":"csv","export_type":"queue","draw":1,"length":-1,"start":0},null,true]',
        // 'game_apis_per_currency' => [
        //     'cny' => ['game_api_ids'=>[2014,2018]],
        //     'super' => ['game_api_ids'=>[9998]],

        // ]
];
