<?php
require_once dirname(__FILE__) . "/base_cli.php";

require_once dirname(__FILE__) . '/../modules/import_data_module.php';
require_once dirname(__FILE__) . '/../modules/validate_totals_module.php';
require_once dirname(__FILE__) . '/../modules/sync_command_module.php';
require_once dirname(__FILE__) . '/../modules/scheduler_module.php';
require_once dirname(__FILE__) . '/../modules/onetime_command_module.php';
require_once dirname(__FILE__) . '/../modules/command_queue_module.php';
require_once dirname(__FILE__) . '/../modules/notify_in_app_module.php';

require_once dirname(__FILE__) . '/../modules/report_command_module.php';
require_once dirname(__FILE__) . '/../modules/gamegateway_module.php';
require_once dirname(__FILE__) . '/../modules/withdrawal_risk_api_module.php';
require_once dirname(__FILE__) . '/../modules/player_profile.php';
require_once dirname(__FILE__) . '/../modules/log_recorder_module.php';
//for testing
require_once dirname(__FILE__) . '/../modules/test_command_module.php';
require_once dirname(__FILE__) . '/../modules/game_logs_module.php';
require_once dirname(__FILE__) . '/../modules/points_command_module.php';
require_once dirname(__FILE__) . '/../modules/affiliate_command_module.php';
require_once dirname(__FILE__) . '/../modules/seamless_command_module.php';
require_once dirname(__FILE__) . '/../modules/alert_command_module.php';
require_once dirname(__FILE__) . '/../modules/sync_batch_payout_command_module.php';
require_once dirname(__FILE__) . '/../modules/player_center_api_cool_down_time_module.php';
require_once dirname(__FILE__) . '/../modules/player_score_module.php';
require_once dirname(__FILE__) . '/../modules/tournament_command_module.php';
require_once dirname(__FILE__) . '/../modules/redemption_code_module.php';
require_once dirname(__FILE__) . '/../modules/customized_promo_rules_module.php';
require_once dirname(__FILE__) . '/../modules/sync_latest_game_records_command_module.php';
require_once dirname(__FILE__) . '/../modules/sync_3rdparty_command_module.php';
require_once dirname(__FILE__) . '/../modules/sync_pos_player_latest_game_logs_command_module.php';
require_once dirname(__FILE__) . '/../modules/player_command_module.php';
require_once dirname(__FILE__) . '/../modules/static_redemption_code_module.php';
require_once dirname(__FILE__) . '/../modules/sync_tags_to_3rd_api_module.php';
require_once dirname(__FILE__) . '/../modules/roulette_command_module.php';
require_once dirname(__FILE__) . '/../modules/game_api_command_module.php';
require_once dirname(__FILE__) . '/../modules/game_tag_command_module.php';
require_once dirname(__FILE__) . '/../modules/quest_command_module.php';
require_once dirname(__FILE__) . '/../modules/player_activity_command_module.php';
require_once dirname(__FILE__) . '/../modules/payment_command_module.php';


use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * General behaviors include :
 *
 * * Copy api error
 * * Resetting player level history
 * * Clear sessions (player, admin, affiliate, agency)
 * * Calculate and pay cash back player
 * * Reset/Approved withdraw amount
 * * Check promo hiding and data
 * * Fix and check referral
 * * Calculate earnings
 * * Get/update player deposit
 * * Batch process active status
 * * Export transactions
 * * Get all unknown games
 * * Merging game logs
 * * Show affiliate/player password
 * * Convert player bet points
 * * Search admin, player and affiliate session
 * * Kickout admin, player and affiliate
 * * Fix less bet affiliate
 * * Migrate game name translation
 * * Fix less bet affiliate
 * * Execute inclusions, cooloff and uncooloff
 * * Deposit limit details
 * * Generating password and fake game logs
 * * Create player for game logs generator
 *
 * @category Command line
 * @version 5.02.02
 * @copyright 2013-2022 tot
 */
class Command extends Base_cli {

	use import_data_module;
	use validate_totals_module;
	use sync_command_module;
	use scheduler_module;
	use onetime_command_module;
	use command_queue_module;
	use report_command_module;
	use gamegateway_module;
	use withdrawal_risk_api_module;
	use player_profile;
	use log_recorder_module;
	use test_command_module;
	use game_logs_module;
	use points_command_module;
    use affiliate_command_module;
	use seamless_command_module;
	use alert_command_module;
	use sync_batch_payout_command_module;
	use player_center_api_cool_down_time_module;
	use player_score_module;
	use tournament_command_module;
	use redemption_code_module;
    use notify_in_app_module;
	use customized_promo_rules_module;
	use sync_latest_game_records_command_module;
    use sync_3rdparty_command_module;
    use sync_pos_player_latest_game_logs_command_module;
	use player_command_module;
    use static_redemption_code_module;
    use sync_tags_to_3rd_api_module;
    use roulette_command_module;
    use game_api_command_module;
    use game_tag_command_module;
	use quest_command_module;
	use player_activity_command_module;
  	use payment_command_module;

	private $rabbitmq_online=false;
	private $rabbitmq_host=null;
	private $rabbitmq_port=null;
	private $rabbitmq_username=null;
	private $rabbitmq_password=null;
	private $rabbitmq_exchange_name=null;
	private $rabbitmq_connection=null;
	private $rabbitmq_channel=null;

	public $oghome=null;

	// const LOG_RECORDER_CHANNEL_NAME='og-log-recorder';
	const MAX_RETRY_RABBITMQ_TIME=20;

	const MDB_SUFFIX_STRING_IN_CMD = '__MDB_SUFFIX_STRING__';

	private $climate;

	/**
	 * overview : Command constructor. Initialize Data
	 */
	public function __construct() {
		parent::__construct();

		// $this->config->set_item('app_debug_log', APPPATH . 'logs/command.log');

		$this->config->set_item('print_log_to_console', $this->input->is_cli_request());

		$default_sync_game_logs_max_time_second = $this->config->item('default_sync_game_logs_max_time_second');
		set_time_limit($default_sync_game_logs_max_time_second);

		$this->oghome = realpath(dirname(__FILE__) . "/../../../");
		$this->climate = new \League\CLImate\CLImate;
	}

	/**
	 * overview : export informations class
	 */
	public function index() {
		ReflectionClass::export('Command');
	}

    /**
     * overview : Fetch Solid Gaming Game List
     *
     */
    public function fetch_solid_gaming_gamelist() {

        $api = $this->utils->loadExternalSystemLibObject(SOLID_GAMING_THB_API);
        $games = $api->queryGameListFromGameProvider(true)['games'];
        $result = $api->updateGameList($games);

        echo "Total Games Fetched: " . $result['data_count'] . PHP_EOL;
        echo "Total Games Inserted: " . $result['data_count_insert'] . PHP_EOL;
        echo "Total Games Updated: " . $result['data_count_update'] . PHP_EOL;
    }

    public function fetch_gamelist_from_provider() {
    	$game_apis = $this->config->item('sync_gamelist_from_provider');

    	if(isset($game_apis) && !empty($game_apis)) {
	    	foreach($game_apis as $game_api) {
		        $api = $this->utils->loadExternalSystemLibObject($game_api);

		        if ($api) {
			        $games = $api->queryGameListFromGameProvider(true)['games'];
			        $result = $api->updateGameList($games);

					$this->utils->debug_log('Total Games Fetched: ', $result['data_count'], 'Total Games Inserted: ', $result['data_count_insert'], 'Total Games Updated: ', $result['data_count_update']);
				}else {
					$this->utils->error_log('Load API Failed: ', $game_api);
					exit(1);
				}
		    }
		}
    }

	/**
	 * overview : copy api error
	 *
	 * @param $dateTimeFromStr
	 * @param $dateTimeToStr
	 */
	public function copy_api_error($dateTimeFromStr, $dateTimeToStr) {

		$dateTimeFrom = new \DateTime($dateTimeFromStr);
		$dateTimeTo = new \DateTime($dateTimeToStr);

		$this->load->model(array('response_result'));
		$list = $this->response_result->getErrorResult($dateTimeFrom, $dateTimeTo);

		if (!empty($list)) {
			foreach ($list as $resp) {
				if (!empty($resp->filepath) && file_exists(RESPONSE_RESULT_PATH . $resp->filepath)) {
					$source_path = RESPONSE_RESULT_PATH . $resp->filepath;
					$path_parts = pathinfo($resp->filepath);
					@mkdir('/tmp' . $path_parts['dirname'], 0777, true);
					$target_path = '/tmp' . $resp->filepath;
					echo $source_path . " to " . $target_path . "\n";
					//copy to tmp
					copy($source_path, $target_path);
				}
			}
		}
	}

	/**
	 * overview : create player, admin, affiliate, agency session
	 */
	function clearSessions() {
		// $this->load->model(array('users'));
		$timeout = 10*3600; // 10 hours

		$this->load->model(['player_model']);
		$sessTables=['ci_player_sessions', 'ci_admin_sessions', 'ci_aff_sessions', 'ci_agency_sessions'];
		$rlt=[];
		foreach ($sessTables as $sessionTable) {

			//count first
			$countSQL=<<<EOD
select count(*) as cnt from {$sessionTable}
where last_activity<=unix_timestamp()-$timeout
EOD;
			$rows=$this->player_model->runRawSelectSQLArray($countSQL);
			$cnt=$rows[0]['cnt'];
			$this->utils->debug_log('will delete '.$cnt.' from '.$sessionTable);

			// $input=$this->climate->input('get '.$cnt.' rows');
			// $response = $input->prompt();

			if($cnt>10000){
				$this->utils->debug_log('batchDeleteSession '.$timeout);
				$rlt[]=$this->player_model->batchDeleteSession($timeout, $sessionTable);
			}else{
				$sql = <<<EOD
delete from {$sessionTable}
where last_activity<=unix_timestamp()-$timeout;
EOD;

				$this->utils->debug_log('delete sql', $sql);
				$rlt[] = 'delete ' . $this->player_model->runRawUpdateInsertSQL($sql) . " rows from ".$sessionTable;
			}

			$rlt[] = 'delete redis session: '.$this->player_model->batchCleanExpiredSessionId($sessionTable);
		}

		$this->utils->debug_log('clear sessions', $rlt);

	}

	/**
	 * Clear timeout SMS records
	 * @return	string	SMS deletion log entry
	 */
	function clearTimeoutSms() {
		$this->load->model('sms_verification');
		$del_mesg = $this->sms_verification->deleteTimeoutSms();

		$this->returnText($del_mesg);
	}


	/**
	 * clear short admin sessions, 6 hours
	 *
	 */
	function clearShortAdminSessions() {
		$this->load->model(array('users'));
		$timeout = 3*3600; // 3 hours

		$sql = <<<EOD
delete from ci_admin_sessions
where last_activity<=unix_timestamp()-$timeout;
EOD;

		$rlt = 'delete ' . $this->users->runRawUpdateInsertSQL($sql) . " rows from ci_admin_sessions";

		$this->utils->debug_log($rlt);
	}

	/**
	 * Trigged from the daily cron,
	 * Do temporary deduction settlement Of the withdraw conditions in the cashback calculation by the cashBackSettings.
	 *
	 *  CMD,
	 * sudo /bin/bash admin/shell/command.sh doSettleTempDeductOfCalcCashbackDailyCron > ./logs/command_doSettleTempDeductOfCalcCashbackDailyCron.log 2>&1 &
	 *
	 * @param bool $dryMode Enabled for detect the time limit.
	 * @return void
	 */
	function doSettleTempDeductOfCalcCashbackDailyCron($dryMode = false){
		$this->load->model(['group_level']);

		if($this->utils->isEnabledFeature('enabled_use_decuct_flag_to_filter_withdraw_condition_when_calc_cackback')){
			$beginDateTime = null;
			$endDateTime = null;

			// 使用共同返水設定的起始日期，當 在 withdraw_condition，使用 decuct_flag。
			// WUDFIWC = while_used_decuct_flag_in_withdraw_condition
			$use_calc_cackback_start_WUDFIWC = $this->utils->getConfig('use_calc_cackback_start_while_used_decuct_flag_in_withdraw_condition');
			// 使用共同返水設定的結束日期，當 在 withdraw_condition，使用 decuct_flag。
			$use_calc_cackback_end_WUDFIWC = $this->utils->getConfig('use_calc_cackback_end_while_used_decuct_flag_in_withdraw_condition');

			$is_empty_use_calc_cackback_start_WUDFIWC = empty($use_calc_cackback_start_WUDFIWC);
			$is_empty_use_calc_cackback_end_WUDFIWC = empty($use_calc_cackback_end_WUDFIWC);

			if($is_empty_use_calc_cackback_start_WUDFIWC){
				$start_WUDFIWC = '0';
			}else{
				$start_WUDFIWC = '1';
			}
			if($is_empty_use_calc_cackback_end_WUDFIWC){
				$end_WUDFIWC = '0';
			}else{
				$end_WUDFIWC = '1';
			}
			switch($start_WUDFIWC. $end_WUDFIWC){
				case '00':
					$beginDateTime = '-365 days';
					$endDateTime = '-1 days';
					break;
				case '01':
					$beginDateTime = '-365 days';
					$endDateTime = '-1 days';
					break;
				case '10':
					$beginDateTime = '-2 days';
					$endDateTime = '-1 days';
					break;
				case '11':
					$beginDateTime = '-2 days';
					$endDateTime = '-1 days';
					break;
			}

			$cashBackSettings = $this->group_level->getCashbackSettings();
			$endHour =  $cashBackSettings->toHour;
			if (intval($endHour) == 23) {
                //the $endDateTime -1 days, H:i:s will be handle in doSettledTempDeductOfCalcCashbackBySettingsWithDateRange()
				$endDT = new DateTime($endDateTime);
				$endDT->modify('-1 days');
				$endDateTime =  $this->utils->formatDateTimeForMysql($endDT);
			}

			$this->utils->debug_log('doSettleTempDeductOfCalcCashbackDailyCron.beginDateTime:', $beginDateTime, 'endDateTime', $endDateTime);
			return $this->doSettledTempDeductOfCalcCashbackBySettingsWithDateRange($beginDateTime, $endDateTime, $dryMode);
		}
	} // EOF doSettleTempDeductOfCalcCashbackDailyCron
	/**
	 *
	 * It always get the time limit from cashBackSettings.
	 * So please assign $beginDateTime and $endDateTime, if need 1 day more.
	 *
	 * CMD examples:
	 * It will to update the data of the time limit, the day before yesterday To yesterday.
	 * sudo /bin/bash admin/shell/command.sh doSettledTempDeductOfCalcCashbackBySettingsWithDateRange "-2 days" "-1 days" > ./logs/command_doSettledTempDeductOfCalcCashbackBySettingsWithDateRange.log 2>&1 &
	 *
	 * It will to update the data of the time limit too, the day before yesterday To yesterday.
	 * sudo /bin/bash admin/shell/command.sh doSettledTempDeductOfCalcCashbackBySettingsWithDateRange "-1 days" "-2 days" > ./logs/command_doSettledTempDeductOfCalcCashbackBySettingsWithDateRange.log 2>&1 &
	 *
	 * It will to update the data of the time limit, the day before yesterday To yesterday.
	 * sudo /bin/bash admin/shell/command.sh doSettledTempDeductOfCalcCashbackBySettingsWithDateRange "-1 days" > ./logs/command_doSettledTempDeductOfCalcCashbackBySettingsWithDateRange.log 2>&1 &
	 *
	 * If n day is today, it will to Update the data of the time limit,n-2 day to n-3 day.
	 * sudo /bin/bash admin/shell/command.sh doSettledTempDeductOfCalcCashbackBySettingsWithDateRange "-2 days" > ./logs/command_doSettledTempDeductOfCalcCashbackBySettingsWithDateRange.log 2>&1 &
	 *
	 * for doSettleTempDeductOfCalcCashbackDailyCron(), two usaged,
	 * sudo /bin/bash admin/shell/command.sh doSettledTempDeductOfCalcCashbackBySettingsWithDateRange "-365 days" "-1 days" > ./logs/command_doSettledTempDeductOfCalcCashbackBySettingsWithDateRange.log 2>&1 &
	 * sudo /bin/bash admin/shell/command.sh doSettledTempDeductOfCalcCashbackBySettingsWithDateRange "-2 days" "-1 days" > ./logs/command_doSettledTempDeductOfCalcCashbackBySettingsWithDateRange.log 2>&1 &
	 *
	 * Enabled dryMode for detect the time limit, and it is Not Update any data,
	 * sudo /bin/bash admin/shell/command.sh doSettledTempDeductOfCalcCashbackBySettingsWithDateRange "-365 days" "-1 days" "true" > ./logs/command_doSettledTempDeductOfCalcCashbackBySettingsWithDateRange.log 2>&1 &
	 * sudo /bin/bash admin/shell/command.sh doSettledTempDeductOfCalcCashbackBySettingsWithDateRange "yesterday" 0 1 > ./logs/command_doSettledTempDeductOfCalcCashbackBySettingsWithDateRange.log 2>&1 &
	 *
	 * @param string $beginDateTime The begin data time for the time limit. e.q. "2021-08-19 13:23:45", "yesterday", "-2 days",...
	 * @param string $endDateTime The end data time for the time limit. e.q. "2021-08-19 13:23:45", "yesterday", "-2 days",...
	 * @param bool $dryMode Enabled for detect the time limit.
	 * @return array The result of Command::_doSettledTempDeductOfCalcCashback();
	 */
	function doSettledTempDeductOfCalcCashbackBySettingsWithDateRange($beginDateTime = null, $endDateTime = null, $dryMode = false){
		$this->load->model(['group_level']);

		if( ! empty($beginDateTime) ){
			$beginDT = new DateTime($beginDateTime);
			$beginDateTime = $this->utils->formatDateTimeForMysql($beginDT); // convert to the format,"Y-m-d H:i:s".
		}else{
			$beginDateTime = null;// The begin time is No limit
		}

		if( ! empty($endDateTime) ){
			$endDT = new DateTime($endDateTime);
			$endDateTime = $this->utils->formatDateTimeForMysql($endDT); // convert to the format,"Y-m-d H:i:s".
		}else{
			$endDateTime = null; // The end time is No limit
		}

		if( ! empty($beginDateTime) && ! empty($endDateTime) ){
			if($beginDT->getTimestamp() > $endDT->getTimestamp() ){
				// swap beginDT and endDT
				$_beginDT = clone $beginDT;
				$beginDT = $endDT;
				$endDT = $_beginDT;
				unset($_beginDT);

				$beginDateTime = $this->utils->formatDateTimeForMysql($beginDT);
				$endDateTime = $this->utils->formatDateTimeForMysql($endDT);
			}
		}

		$currentDateTime = new DateTime(); // default
		if( ! empty($beginDateTime) ){
			$currentDateTime = clone $beginDT;
		}
		if( ! empty($endDateTime) ){
			$currentDateTime = clone $endDT;
		}

		$cashBackSettings = $this->group_level->getCashbackSettings();

		$currentDate = $this->utils->formatDateForMysql($currentDateTime);
		$date = $currentDate;
		$start_date = null;
		$end_date = null;
		if( ! empty($beginDateTime) ){
			$start_date = $this->utils->formatDateForMysql($beginDT);
		}
		if( ! empty($endDateTime) ){
			$end_date = $this->utils->formatDateForMysql($endDT);
		}
		$startHour =  $cashBackSettings->fromHour;
		$endHour =  $cashBackSettings->toHour;
		$theDateTimesToCalcCashback = $this->utils->getDateTimesToCalcCashback($date, $startHour, $endHour, $start_date, $end_date);
		$this->utils->debug_log('doSettledTempDeductOfCalcCashbackBySettingsWithDateRange.theDateTimesToCalcCashback:', $theDateTimesToCalcCashback);
		$player_id = null; // for all players
		$from = $theDateTimesToCalcCashback['startDateTime'];
		$to = $theDateTimesToCalcCashback['endDateTime'];
		$is_deducted_from_calc_cashback_list_in_where = [Withdraw_condition::TEMP_DEDUCT_FROM_CALC_CASHBACK];

		return $this->_doSettledTempDeductOfCalcCashback($player_id, $from, $to, $is_deducted_from_calc_cashback_list_in_where, $dryMode);
	} // EOF doSettledTempDeductOfCalcCashbackBySettingsWithDateRange
	/**
	 * In the time limit,
	 * update the Settled,"Withdraw_condition::TEMP_DEDUCT_FROM_CALC_CASHBACK" into the Field,"withdraw_conditions.is_deducted_from_calc_cashback".
	 *
	 * CMD:
	 * sudo /bin/bash admin/shell/command.sh doSettledTempDeductOfCalcCashbackWithDatetimeRange "-2 days" "-1 days" > ./logs/command_doSettledTempDeductOfCalcCashbackWithDatetimeRange.log 2>&1 &
	 * sudo /bin/bash admin/shell/command.sh doSettledTempDeductOfCalcCashbackWithDatetimeRange "-1 days" "-2 days" > ./logs/command_doSettledTempDeductOfCalcCashbackWithDatetimeRange.log 2>&1 &
	 *
	 * @param string $beginDateTime The begin data time for the time limit. e.q. "2021-08-19 13:23:45", "yesterday",...
	 * @param string $endDateTime The end data time for the time limit. e.q. "2021-08-19 13:23:45", "yesterday",...
	 * @return array The result of Command::_doSettledTempDeductOfCalcCashback();
	 */
	function doSettledTempDeductOfCalcCashbackWithDatetimeRange($beginDateTime = null, $endDateTime = null, $dryMode = false ){

		if( ! empty($beginDateTime) ){
			$beginDT = new DateTime($beginDateTime);
			$beginDateTime = $this->utils->formatDateTimeForMysql($beginDT); // convert to the format,"Y-m-d H:i:s".
		}else{
			$beginDateTime = null;// The begin time is No limit
		}

		if( ! empty($endDateTime) ){
			$endDT = new DateTime($endDateTime);
			$endDateTime = $this->utils->formatDateTimeForMysql($endDT); // convert to the format,"Y-m-d H:i:s".
		}else{
			$endDateTime = null; // The end time is No limit
		}

		if( ! empty($beginDateTime) && ! empty($endDateTime) ){
			if($beginDT->getTimestamp() > $endDT->getTimestamp() ){
				// swap beginDateTime and endDateTime
				$beginDateTime = $this->utils->formatDateTimeForMysql($endDT);
				$endDateTime = $this->utils->formatDateTimeForMysql($beginDT);
			}
		}

		$player_id = null; // for all players
		$from = $beginDateTime;
		$to = $endDateTime;
		$is_deducted_from_calc_cashback_list_in_where = [Withdraw_condition::TEMP_DEDUCT_FROM_CALC_CASHBACK];

		return $this->_doSettledTempDeductOfCalcCashback($player_id, $from, $to, $is_deducted_from_calc_cashback_list_in_where, $dryMode);
	} // EOF doSettledTempDeductOfCalcCashbackWithDatetimeRange
	/**
	 * Update Settled,"Withdraw_condition::TEMP_DEDUCT_FROM_CALC_CASHBACK" into the Field,"withdraw_conditions.is_deducted_from_calc_cashback".
	 *
	 * After updated,
	 * the data of the field,is_deducted_from_calc_cashback Will be excluded while calc the Cashback.
	 *
	 * @param integer $player_id The player.playerId.
	 * @param string $from The begin date time in WHERE clause. e.q. "2021-08-16 12:00:00".
	 * @param string $to The end date time in WHERE clause. e.q. "2021-08-17 11:59:59".
	 * @param array $is_deducted_from_calc_cashback_list_in_where The target condition of the data will be updated in the WHERE clause.
	 * e.q. Withdraw_condition::TEMP_DEDUCT_FROM_CALC_CASHBACK
	 * @param bool $dryMode Enabled for detect the time limit.
	 * @return array $report_info The results after updated. The format as followings,
	 * - $report_info['willUpdateDataCount'] integer The data count will be update.
	 * - $report_info['updatedSuccessDataCount'] integer The success data count after updated.
	 * - $report_info['updatedFailedDataCount'] integer The fail data count after updated.
	 * - $report_info['updatedFailedIdList'] array The failed id,"withdraw_conditions.id" list.
	 */
	protected function _doSettledTempDeductOfCalcCashback($player_id, $from, $to, $is_deducted_from_calc_cashback_list_in_where =[], $dryMode = false) {
		$this->load->library(['og_utility']);
		$this->load->model(['withdraw_condition']);
		$ids = []; // for collect the id, that is will be updated.

		if( empty($is_deducted_from_calc_cashback_list_in_where) ){
			$is_deducted_from_calc_cashback_list_in_where = [Withdraw_condition::TEMP_DEDUCT_FROM_CALC_CASHBACK];
		}
		$this->utils->debug_log('_doSettledTempDeductOfCalcCashback.datetime.from:', $from, 'to:',$to);
		$wc_amount_map = $this->withdraw_condition->getAllPlayersAvailableAmountOnWithdrawConditionByDeductFlag($player_id, $from, $to, $is_deducted_from_calc_cashback_list_in_where);
		if( ! empty($wc_amount_map) ){
			foreach($wc_amount_map as $player_id => $rows ){
				$id_list = $this->og_utility->array_pluck($rows, 'wc_id');
				$ids = array_merge($ids, $id_list);

				$this->utils->debug_log('_doSettledTempDeductOfCalcCashback.wc_amount_map player_id', $player_id, 'id_list', $id_list);
			}
		}
		$this->utils->debug_log('_doSettledTempDeductOfCalcCashback.ids.count', count($ids));

		$report_info = [];
		$report_info['willUpdateDataCount'] = null;
		$report_info['updatedSuccessDataCount'] = null;
		$report_info['updatedFailedDataCount'] = null;
		$report_info['updatedFailedIdList'] = null; // withdraw_conditions.id list
		$report_info['params'] = [];
		$report_info['params']['from'] = $from;
		$report_info['params']['to'] = $to;
		$report_info['params']['dryMode'] = $dryMode;

		$willUpdateDataCount = count($ids);
		$report_info['willUpdateDataCount'] = $willUpdateDataCount;

		if( ! empty($ids) ){

			$report_info['updatedSuccessDataCount'] = 0;
			$report_info['updatedFailedDataCount'] = 0;
			$report_info['updatedFailedIdList'] = [];

			foreach($ids as $indexNumber => $id){

				if( empty($dryMode) ){
					$this->startTrans();

					/// Used to monitor progress
					$this->utils->debug_log('_doSettledTempDeductOfCalcCashback.will update indexNumber', $indexNumber, 'willUpdateDataCount:', $willUpdateDataCount);

					$rlt = $this->withdraw_condition->updateDeductFromCalcCashbackFlag([$id], Withdraw_condition::SETTLED_DEDUCT_FROM_CALC_CASHBACK);

					if( empty($rlt) ){
						// withdraw_conditions.id
						$this->utils->debug_log('updateDeductFromCalcCashbackFlag() failed, $id:', $id); // for get the id of the failed data .
						$report_info['updatedFailedIdList'][] = $id;
					}
					if ($this->endTransWithSucc()) {
						$report_info['updatedSuccessDataCount']++;
					}else{
						$report_info['updatedFailedDataCount']++;
					}
				} // EOF if( empty($dryMode) ){...
			} // EOF foreach($ids as $id){...
		} // EOF if( ! empty($ids) ){...
		/// for reporting
		$this->utils->debug_log('doSettled_temp_deduct_of_calc_cashback.after need_update_count:', $report_info['willUpdateDataCount']
							, ' updated_count', $report_info['updatedSuccessDataCount']
							, ' failed_count', $report_info['updatedFailedDataCount']
							, ' failed_id_list', $report_info['updatedFailedIdList']
							, 'report_info', $report_info);
		return $report_info;
	} // EOF _doSettledTempDeductOfCalcCashback


    public function calculateCashbackWithAccumulateDeduction($cashBackSettings, $currentDate, $endTimeStr = null, $playerId = null, $the_recalculate_cashback = null, $the_uniqueId = null){
		 // defaults
		$recalculate_cashback = null;
		$uniqueId = null;

		if( $the_recalculate_cashback !==  null){
			// replaced by param
			$recalculate_cashback = $the_recalculate_cashback;
		}
		if( ! empty($the_uniqueId) ){
			// replaced by param
			$uniqueId = $the_uniqueId;
		}
		if( $recalculate_cashback === null){
			list($recalculate_cashback, $uniqueId) = $this->group_level->isRecalculateCashback($currentDate);
		}

        $endDate = $this->group_level->getCalculateCashbackEndDate($currentDate, $endTimeStr);

        $date_peiord = $this->utils->dateRange($currentDate, $endDate, true);
        $this->utils->debug_log('date_period', $date_peiord, 'start_date', $currentDate, 'end_date', $endDate);

        foreach($date_peiord as $date){
            #Calculate cashback
            $calcResult = $this->player_cashback_library->calculateDailyTotalCashbackBySettings($cashBackSettings, $date, (int)$playerId, false, $recalculate_cashback, $uniqueId);
            $this->utils->debug_log('calculate cashback with multi days, start to recalculate on ', $date, 'calcResult', $calcResult);
            #Change the lastUpdate field for update
        }

        return $calcResult;
    }
	/**
	 * Execute player_cashback_library::calculateDailyTotalCashbackBySettings() for calculate cashback,
	 * when the setting, 'use_accumulate_deduction_when_calculate_cashback' is false.
	 *
	 * And the script will execute this::calculateCashbackWithAccumulateDeduction() while the setting, 'use_accumulate_deduction_when_calculate_cashback' is true.
	 *
	 * @param object $cashBackSettings the return of group_level::getCashbackSettings().
	 * @param string $currentDate The date string form utils->formatDateForMysql().
	 * @param null|string $endTimeStr For command::calculateCashbackWithAccumulateDeduction().The date string form utils->formatDateForMysql().
	 * @param null|integer $playerId The field, "player.playerId".
	 * @return void
	 */
	public function do_calculate_cashback_with_accumulate_deduction( $cashBackSettings, $currentDate, $endTimeStr, $playerId, $the_recalculate_cashback = null, $the_uniqueId = null ){
		$calcResult = [];
		if($this->utils->getConfig('use_accumulate_deduction_when_calculate_cashback')) {
			$calcResult = $this->calculateCashbackWithAccumulateDeduction($cashBackSettings, $currentDate, $endTimeStr, (int)$playerId, $the_recalculate_cashback, $the_uniqueId);
		}else{
			$calcResult = $this->player_cashback_library->calculateDailyTotalCashbackBySettings($cashBackSettings, $currentDate, (int)$playerId);
		}
		return $calcResult;
	}// EOF do_calculate_cashback_with_accumulate_deduction

	/**
	 * overview : calculate cash back
	 *
	 * @param string $dateTimeStr
	 * @param int $playerId
	 * @param string $endTimeStr For re-calculate, "calculateCashbackWithAccumulateDeduction()".
	 * When assign $dateTimeStr and use_accumulate_deduction_when_calculate_cashback=true,
	 * In default, this function will re-calculate between $dateTimeStr to now of execution,
	 * If its needed, assign $endTimeStr for the duration to shorter.
	 */
	function calculateCashback($dateTimeStr = null, $playerId = null, $endTimeStr = null) {
		$this->utils->debug_log('=========start calculateCashback============================');
		$currentDateTime = new DateTime();
		if (!empty($dateTimeStr)) {
			$currentDateTime = new DateTime($dateTimeStr);
		}

		$currentDate = $this->utils->formatDateForMysql($currentDateTime);
		$currentServertime = $this->utils->formatDateTimeForMysql($currentDateTime);

		$this->load->model(array('group_level'));
		$this->load->library(array('player_cashback_library'));

		$cashBackSettings = $this->group_level->getCashbackSettings();
		$this->utils->debug_log('cashBackSettings', $cashBackSettings);

		// if setting is weekly should not run daily cashback
		if(isset($cashBackSettings->period) && $cashBackSettings->period == Group_level::CASHBACK_PERIOD_SETTING_WEEKLY) {
			$this->utils->debug_log("Disable daily cashback, period was set to weekly", $cashBackSettings->period );
			return;
		}

		$enabled_chopped_lock_in_calculatecashback = $this->utils->getConfig('enabled_chopped_lock_in_calculatecashback');
		if ($enabled_chopped_lock_in_calculatecashback) {
			$_endTransResultList = []; /// for collect all return of endTransWithSucc().
			$do_next = $this->utils->notEmptyValuesInArray(array_keys($_endTransResultList), $_endTransResultList);
		}else{
			$this->startTrans();
			$do_next = true; // always be true
		}

		if ($cashBackSettings->toHour == 23) {
			// last time means 00:00 , 23:59:59
			$calcEnabled = $currentDateTime->format('H') == '00';
		} else {
			$calcDateTime = $currentDate . ' ' . $cashBackSettings->toHour . ':59:59';
			$maxCalcDate = new DateTime($calcDateTime);
			$maxCalcDate->modify('+55 minutes');
			$this->utils->debug_log('currentServertime', $currentServertime, 'calcDateTime', $calcDateTime, 'maxCalcDate', $maxCalcDate);
			$calcEnabled = $currentServertime >= $calcDateTime && $currentServertime <= $this->utils->formatDateTimeForMysql($maxCalcDate);
		}

		$calcResult = $calcReferralResult =  'ignore calc';
		if ($calcEnabled) {
			if ($this->utils->getConfig('always_resync_game_logs_for_cashback') && $do_next) {

				if ($enabled_chopped_lock_in_calculatecashback) {
					$this->startTrans(); /// for rebuild_game_logs_by_timelimit()
				}

				//rebuild game logs
				$this->rebuild_game_logs_by_timelimit(24, $currentDateTime->format('Y-m-d H:00:00'));

				if ($enabled_chopped_lock_in_calculatecashback) {
					$_endTransResultList['Command::rebuild_game_logs_by_timelimit'] = $this->endTransWithSucc(); /// for rebuild_game_logs_by_timelimit()
					$do_next = $this->utils->notEmptyValuesInArray(array_keys($_endTransResultList), $_endTransResultList);
				}
			}

			if($this->utils->getConfig('always_rebuild_totals_before_cashback') && $do_next){
				if ($enabled_chopped_lock_in_calculatecashback) {
					$this->startTrans();/// for rebuild_totals_options()
				}
				$endDT=new DateTime($currentDateTime->format('Y-m-d H:59:59'));
				$endDT->modify('-1 hour');
				$startDT=new DateTime($currentDateTime->format('Y-m-d H:00:00'));
				$startDT->modify('-24 hours');
				//build minute and hour
				$optionsRebuild=[
					'rebuild_hour'=>true,
					'rebuild_minute'=>true,
					'rebuild_day'=>false,
					'rebuild_month'=>false,
					'rebuild_year'=>false,
					'update_player'=>false,
					'game_platform_id'=>null,
					'player_username'=>null,
					'token'=>null,
				];

				$this->rebuild_totals_options($startDT->format('Y-m-d H:00:00'), $endDT->format('Y-m-d H:59:59'), $optionsRebuild);

				$this->utils->debug_log('finish rebuild_totals_options', $startDT, $endDT, $optionsRebuild);
				if ($enabled_chopped_lock_in_calculatecashback) {
					$_endTransResultList['Command::rebuild_totals_options'] = $this->endTransWithSucc();/// for rebuild_totals_options()
					$do_next = $this->utils->notEmptyValuesInArray(array_keys($_endTransResultList), $_endTransResultList);
				}
			}
			$this->utils->debug_log('currentDate', $currentDate);

			$this->utils->debug_log('OGP-27272.701.enabled_chopped_lock_in_calculatecashback', $enabled_chopped_lock_in_calculatecashback, 'do_next:', $do_next );
            if ( ! $enabled_chopped_lock_in_calculatecashback && $do_next) {

				$calcResult = $this->do_calculate_cashback_with_accumulate_deduction($cashBackSettings, $currentDate, $endTimeStr, $playerId);
				$this->utils->debug_log('calcResult_calculateDailyTotalCashbackBySettings', $calcResult);
            }else if ( $enabled_chopped_lock_in_calculatecashback && $do_next) {

                $calcResult = $this->do_calculate_cashback_with_accumulate_deduction($cashBackSettings, $currentDate, $endTimeStr, $playerId);

				$this->utils->debug_log('calcResult_calculateDailyTotalCashbackBySettings', $calcResult);

                $_endTransResultList['Command::do_calculate_cashback_with_accumulate_deduction'] = $calcResult;


                // $_endTransResultList['Command::rebuild_totals_options'] = $this->endTransWithSucc();/// for rebuild_totals_options()
                // $do_next = $this->utils->notEmptyValuesInArray(array_keys($_endTransResultList), $_endTransResultList);

			}else if ( $enabled_chopped_lock_in_calculatecashback && $do_next && false) { // ignore for Performance Issue.
                // disable by the solution, move the lock into deeper function.
				list($isRecalculateCashback, $uniqueId) = $this->group_level->isRecalculateCashback($currentDate); // related to  syncReCalculateCashbackDaily()
				$recalc_rows = [];
				$date_peiord = [];
				if( $this->utils->getConfig('use_accumulate_deduction_when_calculate_cashback') ){
					if( ! empty($endTimeStr) ){
						$endDate = $this->group_level->getCalculateCashbackEndDate($currentDate, $endTimeStr);
						$date_peiord = $this->utils->dateRange($currentDate, $endDate, true);

					}else{
						$date_peiord[] = $currentDate;
					}
				}

				foreach($date_peiord as $_currDate){
					$recalc_row = $this->group_level->query_total_date_recalculate_cashback($_currDate);
					if( $isRecalculateCashback ){ // $uniqueId will be the data of recalculate_cashback.
						if( ! empty($recalc_row) ){
							$recalc_row['uniqueid'] = $uniqueId;
							$recalc_rows[] = $recalc_row;
						}else{
							// 第一次的重算，但這應該是第一次算
							// 所以這邊不可能發生
							// The related method,"Group_level::syncReCalculateCashbackDaily()"
							$this->utils->error_log('OGP-27272.643.empty.recalc_row.isRecalculateCashback', $isRecalculateCashback, 'recalc_row:', $recalc_row);
						}

					}else if( ! $isRecalculateCashback) {
						// this block means cash back is calculate at 1st time.
						$recalc_row['recalculate_times'] = -1; // for update to zero, after calc.
						$recalc_row['uniqueid'] = 'NULL'; // for query of update, after calc // $uniqueId;
						$recalc_row['total_date'] = $_currDate;
						$recalc_rows[] = $recalc_row;
					}

				}// EOF foreach($date_peiord as $_currDate){...
				$this->utils->debug_log('OGP-27272.715.isRecalculateCashback:', $isRecalculateCashback, 'uniqueId:', $uniqueId, 'recalc_rows:', $recalc_rows);
				if( ! empty($playerId) ){
					$playerListFromBetMap[] = $playerId;
				}else{
                    $startTime=microtime(true);
					// query for the players had bets.
					$isUseGetPlayerBetBySettledDateLite = true; /// Patch Query error: MySQL server has gone away Code:2006
					$playerListFromBetMap = $this->player_cashback_library->getPlayerListFromBetMap($cashBackSettings, $currentDate, $isUseGetPlayerBetBySettledDateLite);
                    $this->utils->debug_log("OGP-27272 cost of 763.player_cashback_library->getPlayerListFromBetMap", microtime(true)-$startTime);
				}
				if( ! empty($playerListFromBetMap) ){
					switch((int)$cashBackSettings->common_cashback_rules_mode){
						case Player_cashback_library::COMMON_CASHBACK_RULES_MODE_BY_MULTIPLE_RANGE:
							if ($enabled_chopped_lock_in_calculatecashback) {
								$this->startTrans();/// for common_cashback_multiple_rules::init_caculate_cashback_require_data()
							}
                            $startTime=microtime(true);
							$this->player_cashback_library->common_cashback_multiple_rules->init_caculate_cashback_require_data();
                            $this->utils->debug_log("OGP-27272 cost of 773.common_cashback_multiple_rules->init_caculate_cashback_require_data", microtime(true)-$startTime);
							if ($enabled_chopped_lock_in_calculatecashback) {
								$_endTransResultList['common_cashback_multiple_rules::init_caculate_cashback_require_data'] = $this->endTransWithSucc();
								$do_next = $this->utils->notEmptyValuesInArray(array_keys($_endTransResultList), $_endTransResultList);
							}
						break;
						case Player_cashback_library::COMMON_CASHBACK_RULES_MODE_BY_SINGLE:
						default:
						break;
					} // EOF switch((int)$cashBackSettings->common_cashback_rules_mode){...

					$_this = $this;
					$calcResult_list = [];
					$calcResultFailed_list = [];
					$playerListFromBetMapCount =  empty($playerListFromBetMap)? 0: count($playerListFromBetMap);
					$this->CI->utils->cloneArrayWithForeach($playerListFromBetMap, function($_element, $_array){ // aka. skipCondiCB($_curr, $arr)
						return false; // always to execute every round
					}, function( $_player_id, $_key, &$_calcResult_list, $_playerListFromBetMap ) use ($_this, &$_endTransResultList, $cashBackSettings, $currentDate, &$calcResultFailed_list, $endTimeStr, $isRecalculateCashback, $uniqueId, $playerListFromBetMapCount ){
						// aka. handCurrCB( $_curr, $_key, &$new_arr, $arr )
						try{
							/// for do_calculate_cashback_with_accumulate_deduction()
							$playerId = $_player_id;
							$add_prefix = true; // default
							$isLockFailed = false; // default
							$doExceptionPropagation = true; // true: catch error from outer
							$success = $_this->lockAndTransForPlayerBalance($playerId, function() // $callbakcable // #2
							use ($_this, &$_endTransResultList, $cashBackSettings, $currentDate, $playerId, &$_calcResult_list, &$calcResultFailed_list, $endTimeStr, $isRecalculateCashback, $uniqueId, $playerListFromBetMapCount){
                                $startTime=microtime(true);
								$_calcResult_list[$playerId] = $_this->do_calculate_cashback_with_accumulate_deduction($cashBackSettings, $currentDate, $endTimeStr, $playerId, $isRecalculateCashback, $uniqueId);
								// $_calcResult_list[$playerId] = $_this->player_cashback_library->calculateDailyTotalCashbackBySettings($cashBackSettings, $currentDate, (int)$playerId);
                                $_this->utils->debug_log("OGP-27272 cost of 799.do_calculate_cashback_with_accumulate_deduction", microtime(true)-$startTime);
								$succ = true; // always be true.
								$_this->utils->debug_log('OGP-27272.721._calcResult_list.count:', count($_calcResult_list), 'total:', $playerListFromBetMapCount, 'playerId:', $playerId, 'playerId.result', $_calcResult_list[$playerId]);

								// delay test by player_id
								$idle_in_chopped_lock_of_calculatecashback = $_this->utils->getConfig('idle_in_chopped_lock_of_calculatecashback');
								$_player_id_list = $idle_in_chopped_lock_of_calculatecashback['player_id_list'];
								$_idleSec = $idle_in_chopped_lock_of_calculatecashback['idleSec'];
								if( ! empty($_idleSec) && in_array($playerId, $_player_id_list ) ){
									$_this->utils->debug_log('OGP-27272 725.will idleSec', $_idleSec, 'playerId:', $playerId);
									$_this->utils->idleSec($_idleSec);
								}

								return $succ;
							}, $add_prefix // #3
								, $isLockFailed // #4
								, $doExceptionPropagation // #5
							); // EOF lockAndTransForPlayerBalance

							if( ! $success ){
								$calcResultFailed_list[$playerId] = 'lockAndTransForPlayerBalance() failed.';
							}
						} catch(Exception $e) {
							$success = false;
							$calcResultFailed_list[$playerId] = $e->getMessage();
						} // EOF try {...

					}, $calcResult_list); // EOF $this->CI->utils->cloneArrayWithForeach(...


					$calcResultSuccCounter = empty($calcResult_list)? 0: count($calcResult_list);
					$calcResultFailedCounter = empty($calcResultFailed_list)? 0: count($calcResultFailed_list);
					$calcResultCounter = 0;
					$calcResultCounter += $calcResultSuccCounter;
					$calcResultCounter += $calcResultFailedCounter;

					// Detect the count of $playerListFromBetMap Should Be eq. to  $calcResultSuccCounter+ $calcResultFailedCounter
					if($playerListFromBetMapCount == $calcResultCounter){
						$_successByCounter = true;
					}else{
						$_successByCounter = false;
					}
					$_endTransResultList['cloneArrayWithForeach.player_cashback_library::calculateDailyTotalCashbackBySettings'] = $_successByCounter;
					$do_next = $this->utils->notEmptyValuesInArray(array_keys($_endTransResultList), $_endTransResultList);

					$_this->utils->debug_log('OGP-27272.692.calcResultSuccCounter:', $calcResultSuccCounter, 'total:', $playerListFromBetMapCount);
					$_this->utils->debug_log('OGP-27272.692.calcResultFailedCounter:', $calcResultFailedCounter, 'total:', $playerListFromBetMapCount);
					$_this->utils->debug_log('OGP-27272.692.calcResultFailed_list.player_id:', array_keys($calcResultFailed_list));
					$_this->utils->debug_log('OGP-27272.692.calcResultFailed_list:', $calcResultFailed_list);


					if( !empty($calcResultSuccCounter) ){
						// fix the data of recalculate_cashback
						$_this->utils->debug_log('OGP-27272.864.recalc_rows:', $recalc_rows );
						if( ! empty($recalc_rows) ){
							$update_recalculate_times_rlt_list = [];

							if ($enabled_chopped_lock_in_calculatecashback) {
								$this->startTrans();/// for group_level::update_recalculate_times_by_uniqueid()
							}

							foreach($recalc_rows as $recalc_row){
								$_total_date = $recalc_row['total_date'];
								$update_recalculate_times_rlt_list[$_total_date] = $_this->group_level->update_recalculate_times_by_uniqueid( $recalc_row['recalculate_times']+ 1
																						, $_total_date
																						, $recalc_row['uniqueid'] );
							}

							// collect all total_date in update recalculate_times part.
							$update_recalculate_times_rlt = $this->utils->notEmptyValuesInArray(array_keys($update_recalculate_times_rlt_list), $update_recalculate_times_rlt_list);

							/// assign to $_endTransResultList
							$_endTransResultList['foreach.group_level::update_recalculate_times_by_uniqueid'] = $update_recalculate_times_rlt;
							$_endTransResultList['foreach.group_level::update_recalculate_times_by_uniqueid.endTransWithSucc'] = $this->endTransWithSucc();
							$do_next = $this->utils->notEmptyValuesInArray(array_keys($_endTransResultList), $_endTransResultList);

						}
					}// EOF if( !empty($calcResultSuccCounter) ){...
				} // EOF if( ! empty($playerListFromBetMap) ){...

			// aka. EOF }else if ( $enabled_chopped_lock_in_calculatecashback && $do_next) {...
			}// EOF if ( ! $enabled_chopped_lock_in_calculatecashback) {...

			# Calculate friend referral cashback
			if ($this->utils->isEnabledFeature('enable_friend_referral_cashback') && false) {
				$calcReferralResult = $this->group_level->totalCashbackDailyFriendReferralBySettings($cashBackSettings, $currentDate, (int)$playerId);
			}

		} // EOF if ($calcEnabled) {...


		$payDateTime = $currentDate . ' ' . $cashBackSettings->payTimeHour . ':00';
		$this->utils->debug_log('currentServertime', $currentServertime, 'payDateTime', $payDateTime);

		if ($enabled_chopped_lock_in_calculatecashback) {
			$rlt_endTransWithSucc = false;
			if( ! empty($_endTransResultList) ){
				$rlt_endTransWithSucc = $this->utils->notEmptyValuesInArray(array_keys($_endTransResultList), $_endTransResultList);
				// $rlt_endTransWithSucc = true;
				// foreach($_endTransResultList as $transName => $transResult){
				// 	$rlt_endTransWithSucc = $rlt_endTransWithSucc && $transResult;
				// }
			}
		}else{
			$rlt_endTransWithSucc = $this->endTransWithSucc();
		}
		$payResult = 'ignore pay';
		if ($rlt_endTransWithSucc) {
			$this->utils->debug_log('cashback is success', 'calcResult', $calcResult, 'payResult', $payResult);
			if ($this->utils->isEnabledFeature('enable_friend_referral_cashback') && false) {
				$this->utils->debug_log('referral cashback is success', 'calcResult', $calcReferralResult, 'payResult', $payResult);
			}
		} else {
			if( ! empty($_endTransResultList) ){
				$this->utils->debug_log('cashback is failed, _endTransResultList:', $_endTransResultList);
			}else{
				$this->utils->debug_log('cashback is failed', 853);
			}
		}
		$this->utils->debug_log('=========end calculateCashback============================');
	}

    /**
     * @deprecated
     * @param null $dateTimeStr
     */
	function calculateWeeklyCashbackUnderVIP($dateTimeStr = null) {

		$this->load->model(array('group_level'));

		if($this->utils->isEnabledFeature('enabled_cashback_period_in_vip')) {

			$currentDateTime = new DateTime();
			if (!empty($dateTimeStr)) {
				$currentDateTime = new DateTime($dateTimeStr);
			}

			$currentDate = $this->utils->formatDateForMysql($currentDateTime);

			$cashBackSettings = $this->group_level->getCashbackSettings();

			$vipCashbackWeeklySettings = $this->group_level->getWeeklyPeriodInCashbackRule(); // get weekly period in VIP

			$this->group_level->processWeeklyCashbackinVIP($currentDate, $cashBackSettings, $vipCashbackWeeklySettings);

		} else {
			$this->utils->debug_log('===========Cashback Period not enabled in VIP Settings===========');
		}
	}

	function calculateWeeklyCashback($dateTimeStr = null, $playerId = null) {
		if(!$this->utils->isEnabledFeature('enabled_weekly_cashback')){
			return;
		}

		$msg = $this->utils->debug_log('=========start calculateCashback============================');
		$this->returnText($msg);

		$currentDateTime = new DateTime();
		if (!empty($dateTimeStr)) {
			$currentDateTime = new DateTime($dateTimeStr);
		}

		$currentDate = $this->utils->formatDateForMysql($currentDateTime);
		$currentServertime = $this->utils->formatDateTimeForMysql($currentDateTime);

		$this->load->model(array('group_level'));
        $this->load->library(array('player_cashback_library'));

		$cashBackSettings = $this->group_level->getCashbackSettings();
		$this->utils->debug_log('cashBackSettings', $cashBackSettings);

		// if setting is daily should not run weekly
		if($cashBackSettings->period == Group_level::CASHBACK_PERIOD_SETTING_DAILY) {
			$this->utils->debug_log("Disable daily cashback, period was set to weekly", $cashBackSettings->period );
			return;
		}

		$this->startTrans();

		if ($cashBackSettings->toHour == 23) {
			// last time means 00:00 , 23:59:59
			$calcEnabled = $currentDateTime->format('H') == '00';
		} else {

			$calcDateTime = $currentDate . ' ' . $cashBackSettings->toHour . ':59:59';
			$maxCalcDate = new DateTime($calcDateTime);
			$maxCalcDate->modify('+55 minutes');
			$this->utils->debug_log('currentServertime', $currentServertime, 'calcDateTime', $calcDateTime, 'maxCalcDate', $maxCalcDate);
			$calcEnabled = $currentServertime >= $calcDateTime && $currentServertime <= $this->utils->formatDateTimeForMysql($maxCalcDate);
		}

		$calcResult = 'ignore calc';
		if ($calcEnabled) {

			if ($this->utils->getConfig('always_resync_game_logs_for_cashback')) {
				//rebuild game logs
				$this->rebuild_game_logs_by_timelimit(24, $currentDateTime->format('Y-m-d H:00:00'));
			}

			$this->utils->debug_log('currentDate', $currentDate);
            $calcResult = $this->player_cashback_library->calculateWeeklyTotalCashbackBySettings($cashBackSettings, $currentDate, $playerId);
		}

		$payDateTime = $currentDate . ' ' . $cashBackSettings->payTimeHour . ':00';
		$this->returnText($this->utils->debug_log('currentServertime', $currentServertime, 'payDateTime', $payDateTime));

		$payResult = 'ignore pay';

		if ($this->endTransWithSucc()) {
			$msg = $this->utils->debug_log('cashback is success', 'calcResult', $calcResult, 'payResult', $payResult);
			$this->returnText($msg);
		} else {
			$msg = $this->utils->debug_log('cashback is failed', 950);
			$this->returnText($msg);
		}
		$msg = $this->utils->debug_log('=========end calculateCashback============================');
		$this->returnText($msg);
	}

	/**
	 * import new player into player_relay
	 * So far for Conversion Rate Report.
	 *
	 *
	 * @param integer $limit max records of players.
	 * @return void
	 */
	function sync_new_player_player_relay($limit = 9999999999){
		$this->load->model(['player_relay']);
		$func_name = __FUNCTION__;
		$isExecing = $this->isExecingWithPS($func_name, $this->oghome);
		if( ! $isExecing ){
			$this->player_relay->cron4syncNewPlayer($limit);
		}else{
			$this->utils->debug_log($func_name. ' already running...');
		}
	}

	/**
	 * import new player into player_relay
	 * So far for Conversion Rate Report.
	 *
	 *
	 * @param integer $limit max records of players.
	 * @return void
	 */
	function sync_exists_player_player_relay($limit = 9999999999){
		$this->load->model(['player_relay']);
		$func_name = __FUNCTION__;
		$isExecing = $this->isExecingWithPS($func_name, $this->oghome);
		if( ! $isExecing){
			$this->player_relay->cron4syncExistsPlayer($limit);
		}else{
			$this->utils->debug_log($func_name. ' already running...');
		}
	}

	function calcReportDailyBalance($date_arg = null) {
		if (empty($date_arg)) {
			$date_arg = date('Y-m-d');
		}
		$this->utils->debug_log("Rebuilding daily balance report for date: '{$date_arg}'");
		$this->load->model([ 'daily_balance' ]);
		$func_name = __FUNCTION__;
		$is_execing = $this->isExecingWithPS($func_name, $this->oghome);

		if (!$is_execing) {
			$this->daily_balance->generateDailyBalance($date_arg);
		}
		else {
			$this->utils->debug_log($func_name. ' is already running.');
		}
	}

	/**
	 * Patch player_promo_id into transactions and withdraw_conditions tables.
	 *
	 * Patch OGP_17892 issue
	 *
	 * The cli,
	 * admin/shell/command.sh sync_player_promo_id_to_transactions_withdraw_conditions
	 * @return void
	 */
	function sync_player_promo_id_to_transactions_withdraw_conditions(){

		$this->load->model(['transactions','withdraw_condition']);

		$issue_begin_time = '2020-05-19 00:00:00';
		$add_bonus_type = Transactions::ADD_BONUS;
		$from_type = Transactions::ADMIN;
		$to_type = Transactions::PLAYER;
		$sql=<<<EOF
select transactions.id as tid
	, transactions.player_promo_id as t_player_promo_id
	, transactions.amount
	, playerpromo.bonusAmount
	, playerpromo.playerpromoId
	, playerpromo.promoCmsSettingId /* for get promo_category */
	, transactions.to_id /* player_id */
from playerpromo
join transactions on transactions.from_type = $from_type and transactions.from_id = playerpromo.requestAdminId  /* From Admin */
and transactions.to_type = $to_type and transactions.to_id = playerpromo.playerId /* to player */
and transactions.player_promo_id = 1  /* issue field, value */
and transactions.transaction_type = $add_bonus_type  /* ADD_BONUS */
and playerpromo.bonusAmount = transactions.amount /* //save to player promo */
AND transactions.updated_at > "$issue_begin_time"
and bonusAmount
where playerpromo.dateApply > "$issue_begin_time"
EOF;
		// t_player_promo_id 要填上 playerpromo.playerpromoId
		$qry = $this->db->query($sql);
		$rows = $this->promorules->getMultipleRowArray($qry);
		if( ! empty($rows) ){
			foreach($rows as $row){
				$msg = $this->utils->debug_log('---- START ----', 'player:', $row['to_id']);
				// $this->returnText($msg);

				$playerPromoId = $row['playerpromoId'];
				$tablename = 'transactions';
				$pkId = $row['tid'];
				$pkField = 'id';
				$result = $this->updatePlayerPromoIdOfTable($playerPromoId, $tablename, $pkId, $pkField);
				$msg = $this->utils->debug_log('updated transactions PlayerPromoId. result:', $result,'transactions.id', $row['tid'], 'transactions.player_promo_id', $row['playerpromoId']);
				// $this->returnText($msg);

				if($result){

					// withdraw_conditions.player_promo_id 要填上 playerpromo.playerpromoId
					// withdraw_conditions.source_id = transactions.id
					$source_id = $row['tid'];
					$withdrawConditionList = $this->getWithdrawConditionsBySourceId($source_id);
					$msg = $this->utils->debug_log('withdrawConditionList.count:', count($withdrawConditionList));
// $this->returnText($msg);
					if( count($withdrawConditionList) == 1){

						$withdrawCondition = $withdrawConditionList[0];
// $msg = $this->utils->debug_log($withdrawCondition);
// $this->returnText($msg);
						$playerPromoId = $row['playerpromoId'];
						$tablename = 'withdraw_conditions';
						$pkId = $withdrawCondition['id'];
						$pkField = 'id';
						$result = $this->updatePlayerPromoIdOfTable($playerPromoId, $tablename, $pkId, $pkField);
						// $this->withdraw_condition->updatePlayerPromoId($wc_id, $row['playerpromoId']);

						$msg = $this->utils->debug_log('updated withdraw_conditions PlayerPromoId. result:', $result, 'withdraw_conditions.id', $pkId, 'withdraw_conditions.player_promo_id', $row['playerpromoId']);
// $this->returnText($msg);

					}else{
						// error handle
						$msg = $this->utils->debug_log('!!!! Not found withdraw_conditions by transactions.id, withdraw_conditions.source_id', $source_id);
						// $this->returnText($msg);
					}
				} // EOF if($result){

			} // EOF foreach($rows as $row){...
		} // EOF if( ! empty($rows) ){...

	} // EOF OGP_17892_Patch_Service


	/**
	 * Sync sub-agents settlement settings from root agent.
	 *
	 * The cli,
	 * admin/shell/command.sh sync_settlement_settings_from_root_agent
	 * @return void
	 */
	function sync_settlement_settings_from_root_agent(){

		$this->load->model(['agency_model']);
		$this->load->library(array('agency_library'));

		$only_master = true;
		$ordered_by_name = false;
		$agents = $this->agency_model->get_active_agents($only_master, $ordered_by_name);
		if( ! empty($agents) ) {

			foreach($agents as $indexNumber => $root_agent){
				$EAIBUSP = []; // EAIBUSP = effected_agent_ids_by_update_settlement_period
				$root_agent_id = $root_agent['agent_id'];
				$is_except_root_agent = true;
				$settlement_period = $root_agent['settlement_period'];
				$start_day  = $root_agent['settlement_start_day'];

				$this->agency_model->startTrans();
				$results = $this->agency_model->update_settlement_period_all_downlines_agents($root_agent_id // #1
							, $settlement_period // #2
							, $start_day // #3
							, $is_except_root_agent // #4
							, $EAIBUSP ); // #5
				$succ = $this->agency_model->endTransWithSucc();
				if($succ){
					if( ! empty($EAIBUSP) ){
						foreach($EAIBUSP as $indexNumber => $sub_agent_id){
							$sub_agent = $this->agency_model->get_agent_by_id($sub_agent_id);
							$lockKey = 'EAIBUSP_'. $sub_agent['agent_name'];
							$this->syncAgentCurrentToMDBWithLock($sub_agent_id, $lockKey, false);
						}
					}
				}
			} // EOF foreach
		}

	} // EOF sync_settlement_settings_from_root_agent

	/**
	 * overview : pay cashback
	 *
	 * @param string $dateTimeStr
	 * @param int $playerId
	 */
	function onlyPayCashback($dateTimeStr = null, $playerId = 0, $debug_mode='false', $token=null, $forceToPay=false) {
        $this->load->library(array('player_cashback_library', 'language_function'));
		$this->load->model(array('group_level', 'player_model'));

		if(!empty($token)){
			$result = array('dateTimeStr'=>$dateTimeStr, 'playerId'=>$playerId, 'debug_mode'=>$debug_mode);
			$done=false;
			$this->queue_result->appendResult($token, ['request_id'=>_REQUEST_ID, 'result'=> $result ], $done);
		}

		$msg = $this->utils->debug_log('=========start onlyPayCashback============================', $token);

		$currentDateTime = new DateTime();
		if (!empty($dateTimeStr)) {
			$currentDateTime = new DateTime($dateTimeStr);
		}

		$currentDate = $this->utils->formatDateForMysql($currentDateTime);
		$currentServertime = $this->utils->formatDateTimeForMysql($currentDateTime);

		$cashBackSettings = $this->group_level->getCashbackSettings();
		$this->utils->debug_log('cashBackSettings', $cashBackSettings);

		$calcResult = 'ignore calc';

		$payDateTime = $currentDate . ' ' . $cashBackSettings->payTimeHour . ':00';
		$this->utils->debug_log('currentServertime', $currentServertime, 'payDateTime', $payDateTime);

		$payResult = 'ignore pay';
		//only compare year month day hour

        $payEnabled = substr($currentServertime, 0, 13) == substr($payDateTime, 0, 13);

		if ($payEnabled) {

			if(empty($token)){
				$funcName = 'remote_pay_cashback_daily';
				$params = [
					'date' => $currentServertime,
					'forceToPay' => $forceToPay,
					'triggerFrom' => __FUNCTION__
				];
				$lang = $this->language_function->getCurrentLanguage();
				$callerType = Queue_result::CALLER_TYPE_SYSTEM;
				$caller = 0;
				$state = null;
				$token = $this->createQueueOnCommand($funcName, $params, $lang, $callerType, $caller, $state);

				if(!empty($token)){
					$result = array('dateTimeStr'=>$dateTimeStr, 'playerId'=>$playerId, 'debug_mode'=>$debug_mode);
					$done = false;
					$this->queue_result->appendResult($token, ['request_id'=>_REQUEST_ID, 'result'=> $result ], $done);
				}
			}

			#Calculate the days consumed, from the last cashback pay

			//calc first
			if($this->utils->isEnabledFeature('always_calc_before_pay_cashback')){
				// always calculate base on period setting
				if($cashBackSettings->period == Group_level::CASHBACK_PERIOD_SETTING_DAILY) {
                    if($this->utils->getConfig('use_accumulate_deduction_when_calculate_cashback')) {
                        $calcResult = $this->calculateCashbackWithAccumulateDeduction($cashBackSettings, $currentDate, null, (int)$playerId);
						$this->utils->debug_log('calcResult_calculateCashbackWithAccumulateDeduction', $calcResult);
                    }else{
                        $calcResult = $this->player_cashback_library->calculateDailyTotalCashbackBySettings($cashBackSettings, $currentDate, $playerId, $forceToPay);
						$this->utils->debug_log('calcResult_calculateDailyTotalCashbackBySettings', $calcResult);
                    }
					$this->utils->debug_log('calcResult ', $currentDateTime, $calcResult);
				} else {
                    $calcResult = $this->player_cashback_library->calculateWeeklyTotalCashbackBySettings($cashBackSettings, $currentDate, $playerId, $forceToPay);
                    $this->utils->debug_log('calculateWeeklyResult ', $currentDateTime, $calcResult);
				}
			}

			if ($this->player_model->isDisabledCashback($playerId)) {
				$this->utils->debug_log(__METHOD__, 'Player cashback disabled, payResult: skipping', [ 'playerId' => $playerId ]);
			}
			else {
				$currentDate = $this->utils->formatDateForMysql($currentDateTime);
				$startHour = $cashBackSettings->fromHour;
				$endHour = $cashBackSettings->toHour;
				$withdraw_condition_bet_times= isset($cashBackSettings->withdraw_condition) ? $cashBackSettings->withdraw_condition : 0 ;

				$payResult = $this->group_level->payCashback($this->utils->formatDateForMysql($currentDateTime), $playerId, $debug_mode=='true');
				$this->group_level->updateCashbackLastTime($this->utils->getNowForMysql());

				$this->utils->debug_log('payResult ', $currentDateTime, $payResult);
			}
		}

		$this->utils->debug_log('cashback is success', 'calcResult', $calcResult, 'payResult', $payResult);
		$this->utils->debug_log('=========end onlyPayCashback============================', $token);

		if(!empty($token)){
			$result = array('payResult'=>$payResult, 'payEnabled'=>$payEnabled);
			$done=true;
			if ($payEnabled) {
				//success
				$this->queue_result->appendResult($token, ['request_id'=>_REQUEST_ID, 'result'=> $result ], $done, false);
			} else {
				$this->queue_result->appendResult($token, ['request_id'=>_REQUEST_ID, 'result'=> $result ], $done, true);
			}
		}

		// $this->returnText($msg);
	} // EOF onlyPayCashback

	/**
	 * overview : new cashback
	 *
	 * @deprecated
	 *
	 * @param string $dateTimeStr
	 * @param string $mode
	 * @param int $playerId
	 */
	function calculateAndPayCashback($dateTimeStr = null, $mode = 'calcAndPay', $playerId = null) {
        $this->load->library(array('player_cashback_library'));

		$msg = $this->utils->debug_log('=========start calculateAndPayCashback============================');
		$this->returnText($msg);

		$currentDateTime = new DateTime();
		if (!empty($dateTimeStr)) {
			$currentDateTime = new DateTime($dateTimeStr);
		}

		$currentDate = $this->utils->formatDateForMysql($currentDateTime);
		$currentServertime = $this->utils->formatDateTimeForMysql($currentDateTime);

		$this->load->model(array('group_level'));

		$cashBackSettings = $this->group_level->getCashbackSettings();
		$this->returnText( $this->utils->debug_log('cashBackSettings', $cashBackSettings) );

		$this->startTrans();
		if ($cashBackSettings->toHour == 23) {
			// last time means 00:00 , 23:59:59
			$calcEnabled = $currentDateTime->format('H') == '00';
		} else {
			$calcDateTime = $currentDate . ' ' . $cashBackSettings->toHour . ':59:59';
			$maxCalcDate = new DateTime($calcDateTime);
			$maxCalcDate->modify('+50 minutes');
			$this->returnText( $this->utils->debug_log('currentServertime', $currentServertime, 'calcDateTime', $calcDateTime, 'maxCalcDate', $maxCalcDate) );
			$calcEnabled = $currentServertime >= $calcDateTime && $currentServertime <= $this->utils->formatDateTimeForMysql($maxCalcDate);
		}
		$this->returnText($this->utils->debug_log('calcEnabled', $calcEnabled));
		$calcResult = 'ignore calc';
		if ($calcEnabled) {
			#Calculate cashback
            $calcResult = $this->player_cashback_library->calculateDailyTotalCashbackBySettings($cashBackSettings, $currentDate, $playerId);
			#Change the lastUpdate field for update
		}

		$payDateTime = $currentDate . ' ' . $cashBackSettings->payTimeHour . ':00';
		$this->returnText($this->utils->debug_log('currentServertime', $currentServertime, 'payDateTime', $payDateTime));

		$payResult = 'ignore pay';
		//only compare year month day hour
		$payEnabled = ($mode == 'calcAndPay' && substr($currentServertime, 0, 13) == substr($payDateTime, 0, 13));

		$this->returnText($this->utils->debug_log('payEnabled', $payEnabled));
		if ($payEnabled) {
			#Calculate the days consumed, from the last cashback pay

			if( empty($cashBackSettings->daysAgo) ){
				$cashBackSettings->daysAgo = 0; // default
			}
			// $cashBackSettings->daysAgo = 60; // @todo for test, remove it before commit.
			$daysAgo = intval($cashBackSettings->daysAgo);
			#if  consumed days is greater than Settings daysAgo, process the payback
			$msg = $this->utils->debug_log('daysAgo', $daysAgo );
			$this->returnText($msg);
			//from to
			$startDateTime = clone $currentDateTime;
			$startDateTime->modify('-' . $daysAgo . ' day');

			for ($i = 0; $i < $cashBackSettings->daysAgo; $i++) {
				$startDateTime = $startDateTime->modify('+1 day');

				#Process payCashback
				$payResult = $this->group_level->payCashback($this->utils->formatDateForMysql($startDateTime), $playerId);
				$this->group_level->updateCashbackLastTime($this->utils->getNowForMysql());
			}
		}

		if ( $this->endTransWithSucc() ) {
			$msg = $this->utils->debug_log( 'cashback is success.', 'calcResult: ', $calcResult, 'payResult: ', $payResult );
			$this->returnText($msg);
		} else {
			$msg = $this->utils->debug_log('cashback is failed', 1322);
			$this->returnText($msg);
		}
		$msg = $this->utils->debug_log('=========send calculateAndPayCashback============================');
		$this->returnText($msg);
	} // EOF calculateAndPayCashback

	/**
	 * overview : reset approved withdrawal
	 *
	 * detail : from Approved_withdrawal_amount_resetter
	 */
	function resetApprovedWithdrawAmount() {

		$this->load->model('users');
		$this->users->startTrans();

		$cnt = $this->users->resetApprovedWithdrawal(array(
			'approvedWidAmt' => 0,
			'cs0approvedWidAmt' => 0,
			'cs1approvedWidAmt' => 0,
			'cs2approvedWidAmt' => 0,
			'cs3approvedWidAmt' => 0,
			'cs4approvedWidAmt' => 0,
			'cs5approvedWidAmt' => 0,
		));

		$this->users->endTrans();
		$this->returnText($this->users->succInTrans() ? 'resetApprovedWithdrawAmount: true, count:' . $cnt : 'resetApprovedWithdrawAmount: false');
	}

	/**
	 * overview : check promo for hiding
	 *
	 * detail : from Hide_promo_checker
	 */
	function checkPromoForHiding() {
		$this->load->model(array('promorules'));
		$this->promorules->startTrans();

		$cnt = $this->promorules->checkPromoForHiding();

		$this->promorules->endTrans();
		$this->returnText($this->promorules->succInTrans() ? 'checkPromoForHiding: true, count:' . $cnt : 'checkPromoForHiding: false');
	}

	/**
	 * overview : disable IP blocking
	 */
	function disableIPBlocking() {
		$this->load->model('operatorglobalsettings');
		$data = array(
			'name' => 'ip_rules',
			'value' => false,
		);

		$this->operatorglobalsettings->startTrans();
		$this->operatorglobalsettings->setOperatorGlobalSetting($data);
		$this->operatorglobalsettings->endTrans();
		$msg = $this->utils->debug_log('disable ip blocking', $this->operatorglobalsettings->succInTrans());

		$this->returnText($msg);
	}

	/**
	 * overview : fix referral
	 */
	public function fix_referral() {
		$this->load->model(array('player_friend_referral', 'player_model'));

		$success = $this->player_friend_referral->checkAllReferrals();

		$this->returnText('checkAllReferrals:' . $success . "\n");
	}

	/**
	 * overview : sync sssbet friend referral depositors count
	 */
	public function sync_sssbet_friend_referral_depositors_count($force_today = false, $dry_run = false){
        $this->utils->info_log('start sync_sssbet_friend_referral_depositors_count');
        $force_today=$force_today=='true';
		$dry_run=$dry_run=='true';
        $this->utils->debug_log('setting', ['force_today'=>$force_today, 'dry_run'=>$dry_run]);
        $this->load->model(['player_friend_referral']);
        list($success_cnt, $success_player) = $this->player_friend_referral->updateReferredDepositCount($force_today, $dry_run);
        $this->utils->info_log('end sync_sssbet_friend_referral_depositors_count', $success_cnt, $success_player);
    }

	/**
	 * overview : check referral
	 */
	function checkReferral() {

		$this->load->model(array('player_friend_referral'));

		list($success, $qualified, $paid_array) = $this->player_friend_referral->checkReferral();

		$this->utils->debug_log(sprintf("%s out of %s qualified referral has been updated successfully.", $success, $qualified), $paid_array);

		if (($success > 0) && $this->utils->getConfig('enable_player_invite_calculation')) {
			$this->syncPlayerInvitations();
		}
	}

	public function calculate_earnings() {
		$this->load->model('affiliate_earnings');

		$rlt = false;
		if ($this->affiliate_earnings->isPayday()) {
			$rlt = $this->affiliate_earnings->generate_earnings();
		}

		$this->returnText('result:' . $rlt . "\n");
	}

	/**
	 * overview : calculate monthly earnings
	 * @param string $yearmonth
	 */
	public function calculate_monthly_earnings($yearmonth = null, $affiliate_username = NULL) {
		$this->load->model('affiliate_earnings');

		$rlt = false;
		echo "<pre>";

		if ($this->utils->isEnabledFeature('switch_to_ibetg_commission')) {

			$this->utils->debug_log('using ibetg commission');

			var_dump('USING IBETG COMMISSION');

			return $this->calculate_monthly_earnings_ibetg($yearmonth, $affiliate_username);

		} else if ($this->utils->isEnabledFeature('switch_to_affiliate_daily_earnings') && $this->utils->isEnabledFeature('switch_to_affiliate_platform_earnings')) {

			$this->returnText("running calculate_daily_earnings\n");

			$cron_schedule_date = date('Y-m-d ' . $this->utils->getConfig('affiliate_cron_schedule'));
			$cron_schedule_time = strtotime($cron_schedule_date);

			echo "today is payday: " . date('Y-m-d H:i:s') . "\n";

			if ($cron_schedule_time > time()) {
				echo "waiting for:     " . $cron_schedule_date . "\n";
				time_sleep_until($cron_schedule_time);
				$this->db->reconnect();
			}

			$this->test_generate_affiliate_earnings(date('Y-m-d 00:00:00', strtotime('-1 day')), date('Y-m-d 23:59:59', strtotime('-1 day')));

			# sleep until end of the day
			$tomorrow_midnight = strtotime('midnight tomorrow');
			echo "sleeping until:     " . date('Y-m-d H:i:s', $tomorrow_midnight) . "\n";
			time_sleep_until($tomorrow_midnight);
			return;

		} else if ($this->utils->getConfig('use_old_affiliate_commission_formula')) {

			$this->returnText("running calculate_monthly_earnings using OLD formula\n");

			if ( ! empty($yearmonth)) {
				$rlt = $this->affiliate_earnings->monthlyEarnings($yearmonth);
			} else if ($this->affiliate_earnings->todayIsPayday()) {

				$cron_schedule_date = date('Y-m-d ' . $this->utils->getConfig('affiliate_cron_schedule'));
				$cron_schedule_time = strtotime($cron_schedule_date);

				echo "today is payday: " . date('Y-m-d H:i:s') . "\n";

				if ($cron_schedule_time > time()) {
					echo "waiting for:     " . $cron_schedule_date . "\n";
					time_sleep_until($cron_schedule_time);
					$this->db->reconnect();
				}

				$rlt = $this->affiliate_earnings->monthlyEarnings();
			} else {
				$this->returnText("today is not payday\n");
			}

			$this->returnText('result:' . $rlt . "\n");

		} else {

			$this->returnText("running calculate_monthly_earnings using NEW formula\n");

			if ( ! empty($yearmonth)) {
				return $this->calculateMonthlyEarnings_2($yearmonth, $affiliate_username);
			} else if ($this->affiliate_earnings->todayIsPayday()) {

				$cron_schedule_date = date('Y-m-d ' . $this->utils->getConfig('affiliate_cron_schedule'));
				$cron_schedule_time = strtotime($cron_schedule_date);

				echo "today is payday: " . date('Y-m-d H:i:s') . "\n";

				if ($cron_schedule_time > time()) {
					echo "waiting for:     " . $cron_schedule_date . "\n";
					time_sleep_until($cron_schedule_time);
					$this->db->reconnect();
				}

				$this->calculateMonthlyEarnings_2(NULL, $affiliate_username);

				# sleep until end of the day
				$tomorrow_midnight = strtotime('midnight tomorrow');
				echo "sleeping until:     " . date('Y-m-d H:i:s', $tomorrow_midnight) . "\n";
				time_sleep_until($tomorrow_midnight);
				return;

			} else {
				$this->returnText("today is not payday\n");
			}
		}
	}

	public function calculate_daily_earnings($from_date = null, $to_date = null) {

		$this->load->library(array('affiliate_commission_2'));

    	try {

    		if (empty($from_date)) {
    			$from_date = date('Y-m-d', strtotime('-7 day'));
    		}

    		if (empty($to_date)) {
    			$to_date = date('Y-m-d', strtotime('-1 day'));
    		} else {
    			$to_date = min($to_date, date('Y-m-d', strtotime('-1 day')));
    		}

    		do {

				$this->affiliate_commission_2->generate_daily_earnings_for_all($from_date);
				$message = "Affiliate Commission for the date {$from_date} has been successfully generated.";
    			$this->utils->debug_log("==== {$message} ====");

				$from_date = date('Y-m-d', strtotime($from_date . ' +1 day'));

    		} while($from_date <= $to_date);

    		return TRUE;

    	} catch (Exception $e) {
    		$message = $e->getMessage();
			$this->utils->debug_log("==== {$message} ====");
    		return FALSE;
    	}
	}

	public function test_generate_affiliate_earnings($from_date, $to_date, $username = null, $period = 'daily') {

		$this->load->library(array('affiliate_commission_3'));

		try {

			if ($period == 'daily') {

    			$until = $to_date;

	    		do {

					$to_date = date('Y-m-d H:i:s', strtotime($from_date . ' +1 day') - 1);
					$to_date = min($to_date, $until);

					if ( ! empty($username)) {
						$this->affiliate_commission_3->generate_earnings_by_username($username, $from_date, $to_date);
					} else {
						$this->affiliate_commission_3->generate_earnings_for_all($from_date, $to_date);
					}

					$message = "{$from_date} to {$to_date} has been successfully generated.";
	    			$this->utils->debug_log("==== {$message} ====");

					$from_date = date('Y-m-d H:i:s', strtotime($from_date . ' +1 day'));

	    		} while($to_date < $until);

			} else {

				if ( ! empty($username)) {
					$this->affiliate_commission_3->generate_earnings_by_username($username, $from_date, $to_date);
				} else {
					$this->affiliate_commission_3->generate_earnings_for_all($from_date, $to_date);
				}

				$message = "Affiliate Commission for the date {$from_date} has been successfully generated.";
				$this->utils->debug_log("==== {$message} ====");
			}

			return TRUE;

		} catch (Exception $e) {
			$message = $e->getMessage();
			$this->utils->debug_log("==== {$message} ====");
			return FALSE;
		}
	}

    /**
     * Calculate aff earning reports for this month
     */
    public function calculate_current_monthly_earnings($yearmonth = NULL, $affiliate_username = NULL) {
        $this->load->library(array('affiliate_commission', 'user_agent'));

        try {
			if(!$yearmonth) {
				$yearmonth = $this->utils->getThisYearMonth();
			}
            $this->affiliate_commission->generate_monthly_earnings_for_all($yearmonth, $affiliate_username);

            $message = lang("Affiliate Commission for the Year Month of {$yearmonth} has been successfully generated.");
        } catch (Exception $e) {
            $message = $e->getMessage();
        }

        $this->utils->debug_log("==== {$message} ====");
	}

	/**
     * Calculate aff earning reports for this month by selected aff (for player benefit fee)
     */
    public function calculate_selected_aff_monthly_earnings($affiliate_username, $yearmonth, $type, $fee) {
		$this->load->library(array('affiliate_commission'));

        try {
			$this->utils->debug_log("==== calculate_selected_aff_monthly_earnings ====", $affiliate_username, $yearmonth, $type, $fee);
			if ($affiliate_username) {
				$affiliate_id = $this->affiliatemodel->getAffiliateIdByUsername($affiliate_username);
			}

			switch ($type) {
				case 'addon_platform_fee':

					$success = $this->affiliatemodel->updateAddonPlatformFee($affiliate_id, $yearmonth, $fee, true);
					break;
				case 'player_benefit_fee':

					$success = $this->affiliatemodel->updatePlayerBenefitFee($affiliate_id, $yearmonth, $fee, true);
					break;
			}

			if ($success) {
				return $this->affiliate_commission->generate_monthly_earnings_for_all($yearmonth, $affiliate_username);
			} else {
				return false;
			}

        } catch (Exception $e) {
            $message = $e->getMessage();
        }

    }

	/**
	 * add by spencer.kuo 2017.05.11
	 */
	public function calculate_monthly_earnings_ibetg($yearmonth = null, $affiliate_username = NULL) {
		$this->load->model('affiliate_earnings');
		$this->returnText("running calculate_monthly_earnings using ibetg formula\n");
		if ( ! empty($yearmonth)) {
			return $this->calculateMonthlyEarnings_ibetg($yearmonth, $affiliate_username);
		} else if ($this->affiliate_earnings->todayIsPayday()) {
			$this->returnText("today is payday\n");
			return $this->calculateMonthlyEarnings_ibetg(NULL, $affiliate_username);
		} else {
			$this->returnText("today is not payday\n");
		}
	}

	/**
	 * add by spencer.kuo 2017.05.11
	 */
	public function calculateMonthlyEarnings_ibetg($yearmonth = null, $affiliate_username = NULL) {
    	$this->load->library(array('affiliate_commission_ibetg', 'user_agent'));
    	try {

			$yearmonth = ! empty($yearmonth) ? $yearmonth : $this->utils->getLastYearMonth();

    		$this->affiliate_commission_ibetg->generate_monthly_earnings_for_all($yearmonth, $affiliate_username);

    		$message = lang("Affiliate Commission for the Year Month of {$yearmonth} has been successfully generated.");
    	} catch (Exception $e) {
    		$message = $e->getMessage();
    	}

    	$this->utils->debug_log("==== {$message} ====");
    	$url = $this->agent->referrer();
    	echo "<script type=\"text/javascript\">window.location.href = '{$url}';</script>";
	}

	/**
	 * over view Referral Commission ibetg
	 * add by spencer.kuo 2017.05.12
	 */
	public function calculateFriendReferrialMonthlyEarnings_ibetg($yearmonth = null, $player_username = null) {
    	$this->load->library(array('player_commission_ibetg', 'user_agent'));
		try
		{
			$yearmonth = !empty($yearmonth) ? $yearmonth : $this->utils->getLastYearMonth();
			$this->player_commission_ibetg->generate_monthly_earnings_for_all($yearmonth, $player_username);
    		$message = lang("Player Commission for the Year Month of {$yearmonth} has been successfully generated.");
		}
		catch(Exception $e){
			$message = $e->getMessage();
		}
    	$this->utils->debug_log("==== {$message} ====");
    	$url = $this->agent->referrer();
    	echo "<script type=\"text/javascript\">window.location.href = '{$url}';</script>";
	}

	public function getFriendReferrialDailyLogs_ibetg($yearmonthdate = null, $player_username = null) {
		$this->load->library(array('player_commission_ibetg', 'user_agent'));
		try
		{
			$previous_date = new DateTime();
			$previous_date->sub(new DateInterval('P1D'));
			$yearmonthdate = !empty($yearmonthdate) ? $yearmonthdate : $previous_date->format('Ymd');
			$this->player_commission_ibetg->generate_player_friend_refferred_logs_for_all($yearmonthdate, $player_username);
    		$message = lang("Player Friend Referrial Daily Logs for the Year Month Date of {$yearmonthdate} has been successfully generated.");
		} catch(Exception $e) {
			$message = $e->getMessage();
		}
    	$this->utils->debug_log("==== {$message} ====");
    	$url = $this->agent->referrer();
    	echo "<script type=\"text/javascript\">window.location.href = '{$url}';</script>";
	}

	public function decrypt_message_hrpay_urlencodemessge($message, $paymentSystemId = 148) {
		$private_key = "-----BEGIN RSA PRIVATE KEY-----\nMIICXQIBAAKBgQCu2dbtnU9SVUPj+U6x0N/SyCrPmNByE1kmrDOhYcuqNegftEKP\nyX7qppJ135vhG+wVWmQio32mWhF4bGzBr4Z1YnpL4DqLQ+fX/MXHfQIPpq+tMn9b\nkvHaUnR1y4cwnQKeR8DlE8HtxIYZXbX9WZbFx3XuZZHPA/+VL/lBX4y2iQIDAQAB\nAoGBAKxfVOMrEee45xT30fy6Te0eKBZAMD5FlL3rRXrzTjSesBeRPY1CtzvcusY5\nixKA1Fy4n78GLfixEkgFm7HVuFad2ajoIrImcKOZWchGGWsPwgo4m07rQcarQCUh\nb33nE+YaR8eCWchG5/1LLsJbCPF9lEwdM75hX/1nGWw6oweBAkEA5xCoT+nizgX5\nN5W7KnMYuzEHXFHpfD3OuvyCG3Z7JlMvs3WaXW+VpP0rxp0fvEjdYk0KMj0Ktj4q\nKLIQCGUWRwJBAMG4Oa6zM2RfnIsgMThukE5X30vSYHAnnJgTVpT542ajdgEHk3Pg\nL6w2nwci9KcF1p4x2wB8mRwO5Rq20qvrpK8CQCDU3RV9xhw//QlI3S9K61itvr3/\nZY2hup4XAuWkEBuB9mUpcKWWFU08K9wstzWppMsy5x/8TGlYq+TcaCrJMkECQQCV\nw0EE6JFwpeI2RLsIr6Fsj86XpZrc2iLcBwdGwTGmpfSSszKxwL3aW5fyQnn1rjPP\ntSdkZu9Pm8xPLMwOEW0NAkAQ2eR8djWzfCwpArVIsrjeKuHCRmTINbCXYoon7jf0\nBvyE+xAiFXw1g6OiWBxbj45rzP4zdH8EcDGhubwpZCRX\n-----END RSA PRIVATE KEY-----";

		$split_array = explode('@', urldecode($message));
		$decrypt_string = "";
		foreach ($split_array as $part) {
			$result = openssl_private_decrypt(base64_decode($part), $decrypt_data, $private_key);
			$decrypt_string .= iconv('GB2312', 'UTF-8', $decrypt_data);
		}
		var_dump(rawurldecode($decrypt_string));
	}

	/**
	 * overview : update players total deposit amount
	 */
	public function updatePlayersTotalDepositAmount() {
		$this->load->model('transactions');

		$this->db->select('player.playerId');
		$this->db->select_sum('transactions.amount', 'totalDepositAmount');
		$this->db->from('transactions');
		$this->db->join('player', 'player.playerId = transactions.to_id AND transactions.to_type = ' . Transactions::PLAYER);
		$this->db->where('transactions.transaction_type', Transactions::DEPOSIT);
		$this->db->where('transactions.status', Transactions::APPROVED);
		$this->db->group_by('player.playerId');
		$query = $this->db->get();
		$list = $query->result();

		//should split by 500
		$msg = "\n" . $this->utils->debug_log('total count', count($list));
		$updateList = array_chunk($list, 500);
		foreach ($updateList as $updateSet) {
			$this->db->update_batch('player', $updateSet, 'playerId');
			$result = $this->db->affected_rows();
			$msg .= "\n" . $this->utils->debug_log('list count', count($updateSet), "$result player(s) has been updated");
		}
		$this->returnText($msg);
	}

	/**
	 * overview : active status of batch process
	 */
	public function batch_process_active_status() {
		$this->load->model(array('player_model'));
		$this->startTrans();
		$cnt = $this->player_model->batchProcessActiveStatus();
		$rlt = $this->endTransWithSucc();

		$this->output->append_output('result: ' . $rlt . ' , cnt: ' . $cnt . "\n");
	}

	/**
	 * overview : export daily transaction
	 * @deprecated
	 */
	public function export_transaction_excel_daily() {

		$this->load->library('excel');
		// $this->load->library('utils');
		$this->load->model('transactions');
		$this->load->model('reports');

		#without time;
		$currentDate = $this->utils->getTodayForMysql();
		#date
		$startHour = $this->config->item('transaction_report_start_hour');
		$endHour = $this->config->item('transaction_report_end_hour');
		#yesterday
		$lastDate = $this->utils->getLastDay($currentDate);

		//$start = '2015-01-01 00:00:00';
		$start = $lastDate . ' ' . $startHour . ':00:00';
		$end = $currentDate . ' ' . $endHour . ':00:00';

		$created_at = date('Y-m-d H:i:s');
		$date_string = date('Y_m_d_H_i_s');
		$filename = $date_string . '_daily_transactions';

		$daily_transactions = array(

			'title' => 'Daily Transactions ',
			'date' => $start . ' TO ' . $end,
			'filename' => $filename,
			'transactionsData' => array(),

		);

		$this->utils->debug_log('=========START DAILY TRANSACTION REPORT============================');
		//$this->startTrans();
		$deposits = array(
			'name' => 'Deposits',
			'type' => 'deposits',
			'transDataColTitles' => array('Created at', 'Transaction Type', 'From Username', 'To Username', 'Subwallet', 'Promo Type Name', 'Flag', 'External Transaction Id', 'Note', 'Amount', 'Before Balance', 'After Balance', 'Total Balance'),
			'transactionsData' => $this->transactions->getAllDailyTransactions(array(Transactions::DEPOSIT), $start, $end),
			'summaryColTitles' => array('Payment Type Name', 'Payment Account Name', 'Totals'),
			'summary' => $this->transactions->getEachSummary(array(Transactions::DEPOSIT), $start, $end),
			'total' => $this->transactions->getTotalsPerTransactionType(array(Transactions::DEPOSIT), $start, $end),
		);
		array_push($daily_transactions['transactionsData'], $deposits);

		$withdrawals = array(
			'name' => 'Withdrawals',
			'type' => 'withdrawals',
			'transDataColTitles' => array('Created at', 'Transaction Type', 'From Username', 'To Username', 'Subwallet', 'Promo Type Name', 'Flag', 'External Transaction Id', 'Note', 'Amount', 'Before Balance', 'After Balance', 'Total Balance'),
			'transactionsData' => $this->transactions->getAllDailyTransactions(array(Transactions::WITHDRAWAL), $start, $end),
			'total' => $this->transactions->getTotalsPerTransactionType(array(Transactions::WITHDRAWAL), $start, $end),
		);
		array_push($daily_transactions['transactionsData'], $withdrawals);

		$bonuses = array(
			'name' => 'Bonuses',
			'type' => 'bonuses',
			'transDataColTitles' => array('Created at', 'Transaction Type', 'From Username', 'To Username', 'Subwallet', 'Promo Type Name', 'Flag', 'External Transaction Id', 'Note', 'Amount', 'Before Balance', 'After Balance', 'Total Balance'),
			'transactionsData' => $this->transactions->getAllDailyTransactions(array(Transactions::ADD_BONUS, Transactions::AUTO_ADD_CASHBACK_TO_BALANCE, Transactions::MEMBER_GROUP_DEPOSIT_BONUS, Transactions::MEMBER_GROUP_DEPOSIT_BONUS, Transactions::PLAYER_REFER_BONUS), $start, $end),
			'summaryColTitles' => array('Transaction Type', 'System Code', 'Totals'),
			'summary' => $this->transactions->getEachBonusSummary(array(Transactions::ADD_BONUS, Transactions::AUTO_ADD_CASHBACK_TO_BALANCE, Transactions::MEMBER_GROUP_DEPOSIT_BONUS, Transactions::MEMBER_GROUP_DEPOSIT_BONUS, Transactions::PLAYER_REFER_BONUS), $start, $end),
			'total' => $this->transactions->getTotalsPerTransactionType(array(Transactions::ADD_BONUS, Transactions::AUTO_ADD_CASHBACK_TO_BALANCE, Transactions::MEMBER_GROUP_DEPOSIT_BONUS, Transactions::MEMBER_GROUP_DEPOSIT_BONUS, Transactions::PLAYER_REFER_BONUS), $start, $end),
		);
		array_push($daily_transactions['transactionsData'], $bonuses);

		if ($this->excel->transactionToExcel($daily_transactions)) {

			$data = array(
				'created_at' => $created_at,
				'filepath' => $filename,
			);

			$this->reports->recordTransactionReport($data);
			$this->output->append_output('success');
		} else {
			$this->output->append_output('problem occured');
		}
		$this->utils->debug_log('=========END DAILY TRANSACTION REPORT============================');
	}

	/**
	 * overview : check no prefix
	 */
	public function check_no_prefix() {
		//scan all game_provider_auth
		$this->load->model(array('game_provider_auth'));
		$prefixRows = $this->game_provider_auth->getNoPrefix();

		$this->utils->debug_log($prefixRows);
	}

	/**
	 * overview : fix the win and lost amount
	 *
	 * @param string $fromStr
	 * @param string $toStr
	 */
	public function fix_win_loss_amount($fromStr, $toStr) {
		set_time_limit(0);
		//update game_logs ,
		//win_amount=IF(result_amount > 0, result_amount , 0),
		//loss_amount=IF(result_amount < 0, ABS(result_amount) , 0)
		$sql = <<<EOD
update game_logs
set win_amount=IF(result_amount > 0, result_amount , 0),
loss_amount=IF(result_amount < 0, ABS(result_amount) , 0)
where end_at>=? and end_at<=?
EOD;

		$msg = $this->utils->debug_log('try update game_logs', $sql);
		$this->returnText($msg);

		$this->db->query($sql, array($fromStr, $toStr));

		$cnt = $this->db->affected_rows();

		$msg = $this->utils->debug_log('update game_logs cnt', $cnt);
		$this->returnText($msg);

		//

	}

	/**
	 * overview : game list of NT api
	 */
	public function nt_game_list() {
		$this->lang->load('main', 'chinese');
		$sql = "select game_code,english_name,game_name from game_description where game_platform_id=" . NT_API;
		$qry = $this->db->query($sql);

		$rlt = $qry->result();
		foreach ($rlt as $row) {
			$this->utils->debug_log('game_code:' . $row->game_code . ' , name:' . lang($row->game_name));
		}

	}

	/**
	 * overview : convert player bet to point
	 */
	public function convertPlayerBetToPoint() {
		$this->load->model(array('total_player_game_hour', 'player_model', 'group_level'));
		$date = $this->utils->getTodayForMysql();
		$playerGameRecord = $this->total_player_game_hour->getAllRecordPerDayOfAllPlayer($date, $date);

		foreach ($playerGameRecord as $key) {
			$player = $this->player_model->getPlayerById($key->player_id);
			$betConvertRate = $this->group_level->getVipGroupLevelDetails($player->levelId)['bet_convert_rate'];
			$betToPoint = $key->betting_amount * $betConvertRate / 100;
			$newPoint = $player->point + $betToPoint;
			//update player point balance
			$this->player_model->updatePlayerPointBalance($key->player_id, $newPoint);
		}
	}

	/**
	 * overview : update players affiliate
	 */
	public function updatePlayersAffilitate() {
		$default_affiliate_id = $this->utils->getConfig('default_affiliate_id');
		if (!empty($default_affiliate_id)) {
			$this->db->select('playerId,affiliateId');
			$this->db->from('player');
			$this->db->where('player.affiliateId', null);
			$query = $this->db->get();

			$list = $query->result_array();
			foreach ($list as $key) {
				$row['playerId'] = $key['playerId'];
				$row['affiliateId'] = $default_affiliate_id;
				$data[] = $row;
			}

			$this->db->update_batch('player', $data, 'playerId');
			$result = $this->db->affected_rows();
			echo "$result player(s) has been updated";
		}

	}

	/**
	 * overview : search admin user in sessoin
	 *
	 * @param int $userid
	 */
	public function search_adminuser_in_session($userid) {
		$this->load->model(array('users'));

		$this->utils->debug_log('search_adminuser_in_session', $this->users->searchAdminSession($userid));
	}

	/**
	 * overview : kickout adminuser
	 *
	 * @param int $userid
	 */
	public function kickout_adminuser($userid) {
		$this->load->model(array('users'));

		$this->utils->debug_log('kickout_adminuser', $this->users->kickoutAdminuser($userid));
	}

	/**
	 * overview : search player in session
	 *
	 * @param $playerId
	 */
	public function search_player_in_session($playerId) {
		$this->load->model(array('player_model'));

		$this->utils->debug_log('search_player_in_session', $this->player_model->searchPlayerSession($playerId));
	}

	/**
	 * overview : kickout player
	 * @param $playerId
	 */
	public function kickout_player($playerId) {
        $this->load->library(array('player_library'));

		$this->utils->debug_log('kickout_player', $this->player_library->kickPlayer($playerId));
	}

	/**
	 * overview : search affiliate in session
	 *
	 * @param int $affId
	 */
	public function search_aff_in_session($affId) {
		$this->load->model(array('affiliatemodel'));

		$this->utils->debug_log('search_aff_in_session', $this->affiliatemodel->searchAffSession($affId));
	}

	/**
	 * overview : kickout affiliate
	 *
	 * @param int $affId
	 */
	public function kickout_aff($affId) {
		$this->load->model(array('affiliatemodel'));

		$this->utils->debug_log('kickout_aff', $this->affiliatemodel->kickoutAffuser($affId));
	}

	/**
	 * overview : fix less bet affiliate
	 */
	public function fix_lesbet_affiliate() {
		$this->load->library(array('salt'));
		$this->load->model(array('affiliatemodel'));
		$qry = $this->db->query('select * from affiliates where registered_by=? and externalId is not null and externalId!=""',
			array('importer'));
		$key = 'commonex';
		// [ 0x12, 0x34, 0x56, 0x78, 0x90, 0xAB, 0xCD, 0xEF ];
		$iv = hex2bin('1234567890ABCDEF');

		foreach ($qry->result() as $row) {
			//set password and email
			$qry = $this->db->query('select * from afaccount_0321 where id=? ', array($row->externalId));
			$afRow = $this->affiliatemodel->getOneRow($qry);
			if ($afRow) {
				$password = $this->utils->decryptBase64DES($afRow->passkey, $key, $iv);
				$email = $afRow->email;

				if (empty($password)) {
					$password = 'qwe123rty456';
				}

				$password = $this->salt->encrypt($password, $this->getDeskeyOG());

				$this->db->query('update affiliates set password=?, email=? where affiliateId=?',
					array($password, $email, $row->affiliateId));

				$this->utils->debug_log('update ', $row->affiliateId, 'email', $email, 'externalId', $row->externalId);
			}
		}
	}

	/**
	 * overview : test decrypt
	 */
	public function test_decrpt() {
		$passkey = 'g2ph3Zow7C8=';
		$key = 'commonex';
		// [ 0x12, 0x34, 0x56, 0x78, 0x90, 0xAB, 0xCD, 0xEF ];
		$iv = hex2bin('1234567890ABCDEF');
		$this->returnText($this->utils->decryptBase64DES($passkey, $key, $iv));
	}

	/**
	 * overview : create game account
	 *
	 * @param $platformId
	 * @param $playerUsername
	 */
	public function create_game_account($platformId, $playerUsername) {

		$api = $this->utils->loadExternalSystemLibObject($platformId);
		if ($api) {

			$this->db->from('player')->where('username', $playerUsername);
			$qry = $this->db->get();
			$row = $qry->row();
			$password = '';
			if ($row) {
				$this->load->library(array('salt'));
				$password = $row->password;
				$password = $this->salt->decrypt($password, $this->getDeskeyOG());
				// $playerId = $this->player_model->getPlayerIdByUsername($playerUsername);
				$rlt = $api->createPlayer($playerUsername, $row->playerId, $password);
				$msg = $this->utils->debug_log('create game account result', $playerUsername, $rlt);
				$this->returnText($msg);
			} else {
				$msg = $this->utils->debug_log('donot find user', $playerUsername);
				$this->returnText($msg);
			}
		} else {
			$this->returnText('load api failed');
		}
	}

	/**
	 * overview : get AB api handicaps
	 */
	public function get_ab_handicaps() {
		$rlt = array('success' => false);
		$api = $this->loadApi(AB_API);
		if ($api) {
			$result = $api->queryAgentHandicap(null);
		}
		$this->output->set_content_type('application/json');
		$this->output->set_output(json_encode($result, JSON_PRETTY_PRINT));
	}

	/**
	 * overview : export affiliate statistics
	 *
	 * @param string $from_date_time
	 * @param string $to_date_time
	 */
	public function export_aff_stats($from_date_time, $to_date_time) {
		set_time_limit(0);

		$this->load->model('report_model');

		$request = array(
			'extra_search' => array(
				array('name' => 'by_date_from', 'value' => $from_date_time),
				array('name' => 'by_date_to', 'value' => $to_date_time),
				array('name' => 'enable_date', 'value' => 'true'),
			),
			'draw' => 1,
			'length' => -1,
			'start' => 0,
		);

		$is_export = true;
		$result = $this->report_model->affiliateStatistics($request, $is_export);

		$d = new DateTime();
		$link = $this->utils->create_excel($result, 'affiliate_statistics_' . $d->format('Y_m_d_H_i_s') . '_' . rand(1, 9999), TRUE);

		$this->utils->debug_log('export excel', $link);
		$this->returnText($link);
	}

	/**
	 * overview : migrate game name transaction
	 */
	public function migrate_game_name_translation() {
		$this->returnText("This command updates game_description table with languages defined in main_lang.php.");

		$this->returnText("Making backup for game_description table...");
		$this->db->trans_start();
		$dateString = date("YmdHis");
		$this->db->query("CREATE TABLE game_description_$dateString LIKE game_description");
		$this->db->query("INSERT game_description_$dateString SELECT * FROM game_description;");
		$this->db->trans_commit();
		$this->returnText("Backup completed, table name: game_description_$dateString");

		$this->returnText("Updating game_description table...");
		$originalData = array();
		$englishData = array();
		$chineseData = array();
		$this->db->trans_start();
		$query = $this->db->get('game_description');

		foreach ($query->result() as $row) {
			if (strpos($row->game_name, '_json:') !== false) {
				continue;
			}
			$originalData[$row->id] = $row->game_name;
		}

		# Load english
		$this->lang->load("main", "english");
		foreach ($originalData as $id => $lang_key) {
			$englishData[$id] = $this->lang->line($lang_key);
			// ignore those not translated
			if ($englishData[$id] == $lang_key) {
				unset($englishData[$id]);
			}
		}

		# clear loaded language
		$this->lang->is_loaded = array();
		$this->lang->language = array();

		# Load chinese
		$this->lang->load("main", "chinese");
		foreach ($originalData as $id => $lang_key) {
			$chineseData[$id] = $this->lang->line($lang_key);
			// ignore those not translated
			if ($chineseData[$id] == $lang_key) {
				unset($chineseData[$id]);
			}
			// ignore those that are the same as English
			elseif ($chineseData[$id] == $englishData[$id]) {
				unset($chineseData[$id]);
			}
		}

		# Write back to database with _json:{} format
		$databaseUpdate = array();
		$count = 0;
		foreach ($originalData as $id => $lang_key) {
			if (!array_key_exists($id, $englishData) && !array_key_exists($id, $chineseData)) {
				continue;
			}
			$jsonStr = "_json:{";
			if (array_key_exists($id, $englishData)) {
				$jsonStr .= "\"1\" : \"" . $englishData[$id] . "\" ,";
			}
			if (array_key_exists($id, $chineseData)) {
				$jsonStr .= "\"2\" : \"" . $chineseData[$id] . "\" ,";
			}
			$jsonStr = rtrim($jsonStr, ',');
			$jsonStr .= "}";
			array_push($databaseUpdate, array('id' => $id, 'game_name' => $jsonStr));
			$count++;
		}
		$this->db->update_batch('game_description', $databaseUpdate, 'id');

		$this->db->trans_commit();
		$this->returnText("$count records updated.");
		$this->returnText("Done");
	}

	/**
	 * overview : clear small negative
	 */
	public function clear_small_negative() {

		//TODO clear aff and agency wallet too

		$sql = <<<EOD
update playeraccount set totalBalanceAmount=0
where totalBalanceAmount < 0.01 and totalBalanceAmount > 0
EOD;
		$this->db->query($sql);
		$msg = $this->utils->debug_log('update <0.01', $this->db->affected_rows());
		$this->returnText($msg);

		$sql = <<<EOD
update playeraccount set totalBalanceAmount=0
where totalBalanceAmount > -0.01 and totalBalanceAmount < 0
EOD;
		$this->db->query($sql);
		$msg .= "\n" . $this->utils->debug_log('update >-0.01', $this->db->affected_rows());
		$this->returnText($msg);

	}

	/**
	 * overview : fix transaction from/to username
	 */
	public function fix_from_to_username() {
		//update username
		$type = Transactions::ADMIN;
		$sql = <<<EOD
update transactions set to_username=(select adminusers.username from adminusers where transactions.to_id=adminusers.userId)
where to_type={$type} and to_username is null
EOD;
		$this->db->query($sql);
		$msg = $this->utils->debug_log('update to_username', $type, $this->db->affected_rows());
		$this->returnText($msg);

		$sql = <<<EOD
update transactions set from_username=(select adminusers.username from adminusers where transactions.from_id=adminusers.userId)
where from_type={$type} and from_username is null
EOD;
		$this->db->query($sql);
		$msg = $this->utils->debug_log('update from_username', $type, $this->db->affected_rows());
		$this->returnText($msg);

		$type = Transactions::PLAYER;
		$sql = <<<EOD
update transactions set to_username=(select player.username from player where transactions.to_id=player.playerId)
where to_type={$type} and to_username is null
EOD;
		$this->db->query($sql);
		$msg = $this->utils->debug_log('update to_username', $type, $this->db->affected_rows());
		$this->returnText($msg);

		$sql = <<<EOD
update transactions set from_username=(select player.username from player where transactions.from_id=player.playerId)
where from_type={$type} and from_username is null
EOD;
		$this->db->query($sql);
		$msg = $this->utils->debug_log('update from_username', $type, $this->db->affected_rows());
		$this->returnText($msg);

		$type = Transactions::AFFILIATE;
		$sql = <<<EOD
update transactions set to_username=(select affiliates.username from affiliates where transactions.to_id=affiliates.affiliateId)
where to_type={$type} and to_username is null
EOD;
		$this->db->query($sql);
		$msg = $this->utils->debug_log('update to_username', $type, $this->db->affected_rows());
		$this->returnText($msg);

		$sql = <<<EOD
update transactions set from_username=(select affiliates.username from affiliates where transactions.from_id=affiliates.affiliateId)
where from_type={$type} and from_username is null
EOD;
		$this->db->query($sql);
		$msg = $this->utils->debug_log('update from_username', $type, $this->db->affected_rows());
		$this->returnText($msg);

		$sql = <<<EOD
update transactions set trans_date=date(created_at)
where created_at is not null
EOD;
		$this->db->query($sql);
		$msg = $this->utils->debug_log('update trans_date', $this->db->affected_rows());
		$this->returnText($msg);

		$sql = <<<EOD
update transactions set trans_year_month=date_format(created_at,'%Y%m')
where created_at is not null
EOD;
		$this->db->query($sql);
		$msg = $this->utils->debug_log('update trans_year_month', $this->db->affected_rows());
		$this->returnText($msg);

		$sql = <<<EOD
update transactions set trans_year=date_format(created_at,'%Y')
where created_at is not null
EOD;
		$this->db->query($sql);
		$msg = $this->utils->debug_log('update trans_year', $this->db->affected_rows());
		$this->returnText($msg);

	}

	/**
	 * overview : fix last device ip
	 */
	public function fix_last_device_ip() {
		$this->startTrans();

		$this->db->query('delete from player_device_last_request');

		$sql = <<<EOD
insert into player_device_last_request(player_id,device,last_datetime,http_request_id)
select playerId,device,max(createdat),id from http_request
group by playerId
EOD;

		$this->db->query($sql);

		$this->db->query('delete from player_ip_last_request');

		$sql = <<<EOD
insert into player_ip_last_request(player_id,ip,last_datetime,http_request_id)
select playerId,ip,max(createdat),id from http_request
group by playerId
EOD;

		$this->db->query($sql);

		$rlt = $this->endTransWithSucc();

		$msg = $this->utils->debug_log('rlt', $rlt);
		$this->returnText($msg);
	}

	/**
	 * overview : sync the promotion template
	 */
	public function sync_promotion_template() {
		$this->load->model(array('promo_rule_templates'));

		$rlt = $this->promo_rule_templates->fixDefaultTemplates();

		$msg = $this->utils->debug_log('rlt', $rlt);
		$this->returnText($msg);
	}

	/**
	 * overview : export game usernames
	 *
	 * @param int $gamePlatformId
	 * @param int $split
	 */
	public function export_gameusernames($gamePlatformId, $split = 1000) {
		$this->load->model(array('player_model'));

		$data = $this->player_model->getAllUsernamesByGamePlatform($gamePlatformId);
		$msg = $this->utils->debug_log('export user', count($data));
		//split by 1000
		$arr = array_chunk($data, $split);
		foreach ($arr as $idx => $val) {

			$data = implode("\n", array_column($val, 'login_name'));
			file_put_contents('/tmp/export_username_' . $idx . '.csv', $data);
		}

		$this->returnText($msg);
	}

	/**
	 * overview : reset bank type
	 */
	public function reset_banktype() {
		$this->db->select('bankTypeId');
		$this->db->select('bankName');
		$this->db->from('banktype');
		$query = $this->db->get();
		$bankTypes = $query->result_array();

		// language = English
		$this->lang->is_loaded = array();
		$this->lang->language = array();
		$this->lang->load('main', 'english');

		foreach ($bankTypes as &$bankType) {
			if ($bankType['bankTypeId'] <= 20) {
				$bankType['bankName_en'] = lang('bank_type' . $bankType['bankTypeId']);
			} else {
				$bankNameRaw = $bankType['bankName'];
				if (substr($bankNameRaw, 0, 6) === '_json:') {
					$bankNameRaw_decoded = json_decode(substr($bankNameRaw, 6), true);
					$bankNameRaw = $bankNameRaw_decoded[Language_function::INT_LANG_ENGLISH];
				}

				if (strpos(strtolower($bankNameRaw), 'alipay') !== false) {
					$bankType['bankName_en'] = lang('bank_type_alipay');
				} elseif (strpos(strtolower($bankNameRaw), 'wechat') !== false) {
					$bankType['bankName_en'] = lang('bank_type_wechat');
				} elseif (strpos(strtolower($bankNameRaw), 'spdb') !== false || strpos(strtolower($bankNameRaw), 'spk bank') !== false) {
					$bankType['bankName_en'] = lang('bank_type_spdb');
				} else {
					$bankType['bankName_en'] = lang($bankNameRaw);
				}
			}
		}

		// language = Chinese
		$this->lang->is_loaded = array();
		$this->lang->language = array();
		$this->lang->load('main', 'chinese');

		foreach ($bankTypes as &$bankType) {
			if ($bankType['bankTypeId'] <= 20) {
				$bankType['bankName_zh'] = lang('bank_type' . $bankType['bankTypeId']);
			} else {
				$bankNameRaw = $bankType['bankName'];
				if (substr($bankNameRaw, 0, 6) === '_json:') {
					$bankNameRaw_decoded = json_decode(substr($bankNameRaw, 6), true);
					$bankNameRaw = $bankNameRaw_decoded[Language_function::INT_LANG_CHINESE];
				}
				if (strpos(strtolower($bankNameRaw), 'alipay') !== false) {
					$bankType['bankName_zh'] = lang('bank_type_alipay');
				} elseif (strpos(strtolower($bankNameRaw), 'wechat') !== false) {
					$bankType['bankName_zh'] = lang('bank_type_wechat');
				} elseif (strpos(strtolower($bankNameRaw), 'spdb') !== false || strpos(strtolower($bankNameRaw), 'spk bank') !== false) {
					$bankType['bankName_zh'] = lang('bank_type_spdb');
				} else {
					$bankType['bankName_zh'] = lang($bankNameRaw);
				}
			}
		}

		// combine
		foreach ($bankTypes as &$bankType) {
			$bankType['bankName'] = '_json:' . json_encode(array(
				Language_function::INT_LANG_ENGLISH => $bankType['bankName_en'],
				Language_function::INT_LANG_CHINESE => $bankType['bankName_zh'],
			), JSON_UNESCAPED_UNICODE);
			unset($bankType['bankName_en']);
			unset($bankType['bankName_zh']);
		}

		$this->db->update_batch('banktype', $bankTypes, 'bankTypeId');
	}

	/**
	 * overview : adjust level
	 * @param string $lastYearMonth	format YYYYmm
	 */
	public function adjust_level($lastYearMonth = null) {
		if (empty($lastYearMonth)) {
			$d = new DateTime('last month');
			$lastYearMonth = $d->format('Ym');
		}
		$this->load->model(array('group_level'));

		list($fromDatetime, $toDatetime) = $this->utils->getMonthRange($lastYearMonth);

		$rlt = $this->group_level->batchUpDownLevel($fromDatetime, $toDatetime);
		$msg = $this->utils->debug_log('result', $rlt);
		$this->returnText($msg);
	}

	/**
	 * Let system idle for a few seconds
	 *
	 * @param integer $idleTotalSec
	 * @return void
	 */
	public function idleSec($idleTotalSec = 180){
		return $this->utils->idleSec($idleTotalSec);
	} // EOF idleSec

	public function isExecingCB4Self($match){
		$isExecing = false;
		// if has two data ,should be shelf and another same func bg ps.
		if(count($match[0]) > 1){
			$isExecing = true;
		}
		return $isExecing;
	}
	public function isExecingCB4Related($match){
		$isExecing = false;
		if(count($match[0]) > 0){
		// if has data ,should be another related func bg ps.
			$isExecing = true;
		}
		return $isExecing;
	}

	/**
	 * Batch sync "vipsetting.groupName" to "player.groupName".
	 *
	 * sudo /bin/bash admin/shell/command.sh batch_sync_group_name_in_player > ./logs/command_batch_sync_group_name_in_player.log &
	 *
	 * queue_token
	 * sudo /bin/bash admin/shell/command.sh batch_sync_group_name_in_player 24 0 > ./logs/command_batch_sync_group_name_in_player.log &
	 *
	 * @param integer $theVipSettingId The field,"vipsetting,vipSettingId".
     * @param string $queue_token The token field in the queue_results data-table.
	 * If there is no need to update in the queue, set it to zero.
	 ** @return void
	 */
	public function batch_sync_group_name_in_player($theVipSettingId = null, $queue_token = '_replace_to_queue_token_'){
		$this->load->library(array('player_library'));
		$this->utils->debug_log('batch_sync_group_name_in_player', $this->player_library->batch_sync_group_name_in_player($theVipSettingId, $queue_token));
	} // EOF batch_sync_group_name_in_player

	public function filter_mdb_suiffix_in_args($_args){
		if( ! empty( $_args ) ){
			foreach($_args as $i => $arg){
				$mystring = $arg;
				$findme = self::MDB_SUFFIX_STRING_IN_CMD;
				$pos = strpos($mystring, $findme);
				if($pos !== false){
					unset($_args[$i]);
				}
			}
			$_args = array_values($_args);
		}
		return $_args;
	}

	/**
	 * For new features of VIP
	 * Dynamically get date range
	 * params will ignore if schedule has set
	 */
	public function batch_player_level_upgrade($manual_batch = false, $suffix4mdb = '') {
		// bplu = batch_player_level_upgrade
		$bplu_options = $this->config->item('batch_player_level_upgrade');

		/// The tasks,batch_player_level_upgrade and batch_player_level_upgrade_check_hourly
		// just one task can to execute at same time.
		$hasSelf = null;
		$currPS = null;
		$match = null;
		$func_name = __FUNCTION__;
		$funcList = [$func_name, 'batch_player_level_upgrade_check_hourly'];
		$isEnabledMDB=$this->utils->isEnabledMDB();

		$_this = $this;
		$_isExecingCB = function($match) use ($func_name, &$hasSelf, &$_this) { // isExecingCB
			$hasSelf = false;
			$isExecing = null;
			foreach($match as $ps) {
				if(strpos($ps[0], $func_name) !== false){
					$hasSelf = true;
					break;
				}
			}
			if($hasSelf){
				/// will call $this->isExecingCB4Self($match);
				$isExecing = call_user_func_array([$_this,'isExecingCB4Self'], [$match]);
			}else{
				/// will call $this->isExecingCB4Related($match);
				$isExecing = call_user_func_array([$_this,'isExecingCB4Related'], [$match]);
			}

			return $isExecing;
		}; // EOF $_isExecingCB

		if( ! $isEnabledMDB ){
			// Disabled MDB
			$is_execing = $this->isExecingListWithPS($funcList, $this->oghome, $_isExecingCB, $currPS, $match);
		}else{
			// Enabled MDB
			$is_execing = $this->isExecingListWithPSWithMDB($funcList, $this->oghome, $_isExecingCB, $currPS, $match, $func_name, $suffix4mdb);
		}

		/// OGP-25378 Remove it,"idleSec_in_cronjob_after_isExecingListWithPS" in live,
		$_idleSec = 0;
		$idleSec_in_cronjob_after_isExecingListWithPS=$this->utils->getConfig('idleSec_in_cronjob_after_isExecingListWithPS');
		if( ! empty($idleSec_in_cronjob_after_isExecingListWithPS['cronjob_batch_player_level_upgrade']) ){
			$_idleSec = $idleSec_in_cronjob_after_isExecingListWithPS['cronjob_batch_player_level_upgrade'];
		}
		if( ! empty($_idleSec) ){
			$this->utils->debug_log('will.idleSec.sec', $_idleSec );
			$this->utils->idleSec($_idleSec );
			$this->utils->debug_log('after.idleSec.sec', $_idleSec );
		}


		// /// If the related tasks is executing,
		// // it will delay to executed, and waiting for the related tasks done.
		// $maxWaitingTimes = $bplu_options['maxWaitingTimes']; // will give up while over the maxWaitingTimes.
		// $waitingSec = $bplu_options['waitingSec']; // 10 sec
		// $funcList = [];
		// // $funcList[] = $func_name;
		// // $funcList[] = 'batch_player_level_upgrade_check_hourly';
		// $funcList[] = 'batch_player_level_downgrade';
		// $isOverWaitingTime = $this->isOverWaitingTimeWithWaitingByPS($funcList, function($match){ // isExecingCB
		// 	// will call $this->isExecingCB4Related($match);
		// 	return call_user_func_array([$this,'isExecingCB4Related'], [$match]);
		// }, $maxWaitingTimes, $waitingSec);
		$isOverWaitingTime = false;
$this->utils->debug_log($func_name, ' is_execing:', $is_execing,'$isOverWaitingTime:', $isOverWaitingTime);
		if (!$is_execing && !$isOverWaitingTime ) {
			$this->idleSec($bplu_options['idleSec']);
			$time_exec_begin = date('c', time());
			$this->utils->debug_log(__METHOD__, 'Begin execution', [ 'time_exec_begin' => $time_exec_begin ]);
			set_time_limit(0);

			$this->load->model(array('group_level', 'player_model', 'player_promo'));

			$this->group_level->setGradeRecord([
				'request_type'  => Group_level::REQUEST_TYPE_AUTO_GRADE,
				'request_grade' => Group_level::RECORD_UPGRADE,
				'request_time'  => $time_exec_begin
			]);
			// need to get all enabled players first
			// settings of upgrade can set deposit only.
			$playerIds = $this->player_model->getEnabledPlayers();
			$order_generated_by = ['order_generated_by' => Player_promo::ORDER_GENERATED_BY_BATCH_PLAYER_LEVEL_UPGRADE];
			$result = $this->group_level->batchUpDownLevelUpgrade($playerIds, $manual_batch, null, $time_exec_begin, $order_generated_by);

			$this->utils->debug_log('Total Player Upgrade', $result, $result['totalPlayerUpgrade']);
			$this->utils->debug_log(__METHOD__, 'End execution');

			#Delete failed data for vip_grade_report
			$bool = $this->group_level->retainFailedDataForSpecificDay();
			$this->utils->debug_log('Delete vip_grade_report failed data:' . ($bool ? 'true' : 'false'));
		} else {
			if($isOverWaitingTime){
				$this->utils->debug_log($func_name. ' is over waiting times.');
			}
			if($is_execing){
				if($hasSelf){
					/// $hasSelf Not exactly correct!
					// Because "batch_player_level_upgrade" is the part of "batch_player_level_upgrade_check_hourly".
					$msg = $func_name. ' is already running.';
				}else{
					$msg = 'The related task is already running.';
				}
				$this->utils->debug_log($msg, '$currPS:', $currPS);
			}
		}
	} // EOF batch_player_level_upgrade

	public function batch_player_level_upgrade_check_hourly($manual_batch = false, $suffix4mdb = '') {
		// bpluch = batch_player_level_upgrade_check_hourly
		$bpluch_options = $this->config->item('batch_player_level_upgrade_check_hourly');
		/// The tasks,batch_player_level_upgrade and batch_player_level_upgrade_check_hourly
		// just one task can to execute at same time.
		$hasSelf = null;
		$currPS = null;
		$match = null;
		$func_name = __FUNCTION__;
		$funcList = [$func_name, 'batch_player_level_upgrade'];
		$isEnabledMDB=$this->utils->isEnabledMDB();

		$_this = $this;
		$_isExecingCB = function($match) use ($func_name, &$hasSelf, &$_this){ // isExecingCB
			$hasSelf = false;
			$isExecing = null;
			foreach($match as $ps) {
				if(strpos($ps[0], $func_name) !== false){
					$hasSelf = true;
					break;
				}
			}
$_this->utils->debug_log($func_name, ' hasSelf:', $hasSelf, 'match:', $match);
			if($hasSelf){
				/// will call $this->isExecingCB4Self($match);
				$isExecing = call_user_func_array([$_this,'isExecingCB4Self'], [$match]);
			}else{
				/// will call $this->isExecingCB4Related($match);
				$isExecing = call_user_func_array([$_this,'isExecingCB4Related'], [$match]);
			}

			return $isExecing;
		}; // EOF $_isExecingCB

		if( ! $isEnabledMDB ){
			$is_execing = $this->isExecingListWithPS($funcList, $this->oghome, $_isExecingCB, $currPS, $match);
		}else{
			$is_execing = $this->isExecingListWithPSWithMDB($funcList, $this->oghome, $_isExecingCB, $currPS, $match, $func_name, $suffix4mdb);
		}

		/// OGP-25378 Remove it,"idleSec_in_cronjob_after_isExecingListWithPS" in live,
		$_idleSec = 0;
		$idleSec_in_cronjob_after_isExecingListWithPS=$this->utils->getConfig('idleSec_in_cronjob_after_isExecingListWithPS');
		if( ! empty($idleSec_in_cronjob_after_isExecingListWithPS['cronjob_batch_player_level_upgrade_hourly']) ){
			$_idleSec = $idleSec_in_cronjob_after_isExecingListWithPS['cronjob_batch_player_level_upgrade_hourly'];
		}
		if( ! empty($_idleSec) ){
			$this->utils->debug_log('will.idleSec.sec', $_idleSec );
			$this->utils->idleSec($_idleSec );
			$this->utils->debug_log('after.idleSec.sec', $_idleSec );
		}

		// /// If the related tasks is executing,
		// // it will delay to executed, and waiting for the related tasks done.
		// $maxWaitingTimes = $bpluch_options['maxWaitingTimes']; // will give up while over the maxWaitingTimes.
		// $waitingSec = $bpluch_options['waitingSec'];; // 10 sec
		// $funcList = [];
		// // $funcList[] = $func_name;
		// // $funcList[] = 'batch_player_level_upgrade';
		// $funcList[] = 'batch_player_level_downgrade';
		// $isOverWaitingTime = $this->isOverWaitingTimeWithWaitingByPS($funcList, function($match){ // isExecingCB
		// 	// will call $this->isExecingCB4Related($match);
		// 	return call_user_func_array([$this,'isExecingCB4Related'], [$match]);
		// }, $maxWaitingTimes, $waitingSec);
		$isOverWaitingTime = false;
$this->utils->debug_log($func_name. ' is_execing.', $is_execing);
		if (!$is_execing && !$isOverWaitingTime) {
			$this->idleSec($bpluch_options['idleSec']);
			$time_exec_begin = date('c', time());
			$this->utils->debug_log(__METHOD__, 'Begin execution', [ 'time_exec_begin' => $time_exec_begin ]);
			$this->load->model(array('group_level', 'player_model', 'player_promo'));

			$this->group_level->setGradeRecord([
				'request_type'  => Group_level::REQUEST_TYPE_AUTO_GRADE,
				'request_grade' => Group_level::RECORD_UPGRADE,
				'request_time'  => $time_exec_begin
			]);
			// need to get all enabled players first
			// settings of upgrade can set deposit only.
			$doPlayerFilter = $this->config->item('do_player_filter_in_batch_player_level_upgrade_check'); // 0
			$lastLoginTimeBeginDateTime = new DateTime( $this->config->item('lastLoginTime_begin_batch_player_level_upgrade_check') ); // -2 days
			$lastLoginTimeEndDateTime = new DateTime( $this->config->item('lastLoginTime_end_batch_player_level_upgrade_check') ); // now
			if( ! empty($doPlayerFilter) ){
				$login_time_min = $this->utils->formatDateTimeForMysql($lastLoginTimeBeginDateTime);
				$login_time_max = $this->utils->formatDateTimeForMysql($lastLoginTimeEndDateTime);
				$playerIds = $this->player_model->getAllEnabledPlayersByActivityTime($login_time_min, $login_time_max);
			}else{
				$playerIds = $this->player_model->getAllEnabledPlayers();
			}

			$order_generated_by = ['order_generated_by' => Player_promo::ORDER_GENERATED_BY_BATCH_PLAYER_LEVEL_UPGRADE_CHECK_HOURLY];
			$result = $this->group_level->batchUpDownLevelUpgrade($playerIds, $manual_batch, true, $time_exec_begin, $order_generated_by);
			$this->utils->debug_log('Total Player Upgrade', $result, $result['totalPlayerUpgrade']);
			$this->utils->debug_log(__METHOD__, 'End execution');
		} else {
			if($isOverWaitingTime){
				$this->utils->debug_log($func_name. ' is over waiting times.');
			}
			if($is_execing){
				if($hasSelf){
					$msg = $func_name. ' is already running.';
				}else{
					$msg = 'The related task is already running.';
				}
				$this->utils->debug_log($msg, '$currPS:', $currPS);
			}
		}
	}

	public function batch_player_level_downgrade($manual_batch = false, $time_exec_begin = null) {
		// bpld = batch_player_level_downgrade
		$bpld_options = $this->config->item('batch_player_level_downgrade');
		$func_name = __FUNCTION__;
		$is_execing = $this->isExecingWithPS($func_name, $this->oghome);

		// /// If the related tasks is executing,
		// // it will delay to executed, and waiting for the related tasks done.
		// $maxWaitingTimes = $bpld_options['maxWaitingTimes']; // will give up while over the maxWaitingTimes.
		// $waitingSec = $bpld_options['waitingSec']; // 10 sec
		// $funcList = [];
		// // $funcList[] = $func_name;
		// $funcList[] = 'batch_player_level_upgrade';
		// $funcList[] = 'batch_player_level_upgrade_check_hourly';
		// $isOverWaitingTime = $this->isOverWaitingTimeWithWaitingByPS($funcList, function($match){ // isExecingCB
		// 	// will call $this->isExecingCB4Related($match);
		// 	return call_user_func_array([$this,'isExecingCB4Related'], [$match]);
		// }, $maxWaitingTimes, $waitingSec);
		$isOverWaitingTime = false;

		if ( !$is_execing && !$isOverWaitingTime ) {
			$this->idleSec($bpld_options['idleSec']);
			if( empty($time_exec_begin) ){
				$time_exec_begin = date('c', time());
			}

			$this->utils->debug_log(__METHOD__, 'Begin execution', [ 'time_exec_begin' => $time_exec_begin ]);
			set_time_limit(0);
			$this->load->model(array('group_level', 'player_model', 'player_promo'));

			$this->group_level->setGradeRecord([
				'request_type'  => Group_level::REQUEST_TYPE_AUTO_GRADE,
				'request_grade' => Group_level::RECORD_DOWNGRADE,
				'request_time'  => $time_exec_begin
			]);
			// need to get all enabled players first
			// settings of upgrade can set deposit only.
            $filterLowestLevels = !empty($bpld_options['filterLowestLevels']);
			$playerIds = $this->player_model->getEnabledPlayers([], $filterLowestLevels);
            $bpld_options = $this->config->item('batch_player_level_downgrade');
            $this->utils->debug_log(__METHOD__, 'playerIds.count:', empty($playerIds)? 0: count($playerIds), 'filterLowestLevels:', $filterLowestLevels );

			$order_generated_by = ['order_generated_by' => Player_promo::ORDER_GENERATED_BY_BATCH_PLAYER_LEVEL_DOWNGRADE];
			$result = $this->group_level->batchUpDownLevelDowngrade($playerIds, $manual_batch, $order_generated_by, $time_exec_begin);

			$this->utils->debug_log('Total Player Downgrade', $result, $result['totalPlayerDowngrade']);
			$this->utils->debug_log(__METHOD__, 'End execution');

			#Delete failed data for vip_grade_report
			$bool = $this->group_level->retainFailedDataForSpecificDay();
			$this->utils->debug_log('Delete vip_grade_report failed data:' . ($bool ? 'true' : 'false'));
		} else {
			if($isOverWaitingTime){
				$this->utils->debug_log($func_name. ' is over waiting times.');
			}
			if($is_execing){
				$this->utils->debug_log($func_name. ' is already running.');
			}
		}
	}

	/**
	 * Execute specified player(s) Upgrade Check
	 *
	 * The cli for simulated from cronjob to execute,
	 * sudo /bin/bash ./admin/shell/command.sh player_level_upgrade_by_playerId "5357_5522" 0 "2021-04-01 18:58:47" 0 > ./logs/OGP21051.5357_5522.210401185847.sasb.log
	 *
	 * @param string|integer $playerId The field,"player_id". The separator:_ (under line)
	 * @return void
	 */
	// public function player_level_upgrade_by_playerId($playerId = null) {
	// 	$this->load->model(array('group_level', 'player_model'));
	// 	$result = $this->group_level->batchUpDownLevelUpgrade($playerId, true);
	// }
	public function player_level_upgrade_by_playerId($playerId = null, $manual_batch = true, $time_exec_begin = null, $check_hourly = false) {
		$this->load->model(array('group_level', 'player_model'));

		if( empty($time_exec_begin) ){
			$time_exec_begin = date('c', time());
		}

		$separator = '_'; // separator:_ (under line)
		if(strpos($playerId, $separator) !== false){
			$playerId = explode($separator, $playerId); // string convert to array for batchUpDownLevelDowngrade().
			$playerId = array_filter($playerId); // filter empty string
			$playerId = array_values($playerId); // resort by values.
			$playerId = $this->player_model->getEnabledPlayers($playerId); // If array type, will get rows of the data-table,"player".
		}

		$this->group_level->setGradeRecord([
			'request_type'  => Group_level::REQUEST_TYPE_AUTO_GRADE,
			'request_grade' => Group_level::RECORD_UPGRADE,
			'request_time'  => $time_exec_begin
		]);
		// $check_hourly // 若有勾選 「Hourly Check Upgrade」 ，則通過升級檢查，會升級；若沒有開，則就算通過升級檢查，也不會升級。
		$order_generated_by = ['order_generated_by' => Player_promo::ORDER_GENERATED_BY_BATCH_PLAYER_LEVEL_UPGRADE_CHECK_HOURLY];
		$this->utils->debug_log('2232.$playerId=', $playerId, '$manual_batch=', $manual_batch, '$check_hourly=', $check_hourly, '$time_exec_begin=', $time_exec_begin);
		$result = $this->group_level->batchUpDownLevelUpgrade($playerId, $manual_batch, $check_hourly, $time_exec_begin, $order_generated_by);
	}
	/**
	 * Execute specified player(s) Upgrade Check
	 *
	 * The cli for simulated from cronjob to execute,
	 * sudo /bin/bash ./admin/shell/command.sh player_level_upgrade_by_playerIdV2 "5357_5522" 0 "2021-04-01 18:58:47" 0 > ./logs/OGP21051.5357_5522.210401185847.sasb.log
	 *
	 * @param string|integer $playerId The field,"player_id". The separator:_ (under line)
	 * @return void
	 */
	public function player_level_upgrade_by_playerIdV2($playerId = null, $manual_batch = true, $time_exec_begin = null, $check_hourly = false) {
		return $this->player_level_upgrade_by_playerId($playerId, $manual_batch, $time_exec_begin, $check_hourly);
	}



	/**
	 * Execute specified player(s) Downgrade Check
	 *
	 * The cli for simulated from cronjob to execute,
	 * sudo /bin/bash ./admin/shell/command.sh player_level_downgrade_by_playerId "5357_5522" 0 "2021-04-01 18:58:47" > ./logs/OGP21051.5357_5522.210401185847.sasb.log
	 *
	 * @param string|integer $playerId The field,"player_id". The separator:_ (under line).
	 * @param boolean $manual_batch If with true means simulated trigger by the player detail info page of SBE.
	 * If with false means simulated trigger by cronjob.
	 * @param null|string $time_exec_begin The trigger datetime.If null that's means current time.
	 * @return void
	 */
	public function player_level_downgrade_by_playerId($playerId = null, $manual_batch = true, $time_exec_begin = null) {
		$this->load->model(array('group_level', 'player_model'));
		if( empty($time_exec_begin) ){
			$time_exec_begin = date('c', time());
		}
		$separator = '_'; // separator:_ (under line)
		if(strpos($playerId, $separator) !== false){
			$playerId = explode($separator, $playerId); // string convert to array for batchUpDownLevelDowngrade().
			$playerId = array_filter($playerId); // filter empty string
			$playerId = array_values($playerId); // resort by values.
			if( empty($manual_batch) ){
				$playerId = $this->player_model->getEnabledPlayers($playerId); // If array type, will get rows of the data-table,"player".
			}else{ // ignore for triggered by SBE.
				// for simulate executed from SBE
				$playerIdList = [];
				if( ! empty($playerId) ){
					foreach($playerId as $thePlayerId){
						$_playerId = [];
						$_playerId['playerId'] = $thePlayerId;
						$playerIdList[] = (object)$_playerId;
					}
				}
				$playerId = $playerIdList;
			}
		}

		$this->group_level->setGradeRecord([
			'request_type'  => Group_level::REQUEST_TYPE_AUTO_GRADE,
			'request_grade' => Group_level::RECORD_DOWNGRADE,
			'request_time'  => $time_exec_begin
		]);

		$order_generated_by = ['order_generated_by' => Player_promo::ORDER_GENERATED_BY_BATCH_PLAYER_LEVEL_DOWNGRADE];
		$this->utils->debug_log('2232.$playerId=', $playerId, '$manual_batch=', $manual_batch, '$time_exec_begin=', $time_exec_begin);
		$result = $this->group_level->batchUpDownLevelDowngrade($playerId, $manual_batch, $order_generated_by, $time_exec_begin);

	}

	/**
	 * overview : Responsible gaming (Self Exclusion)
	 *
	 * detail : Must block the player in the website
	 * 		    Must block all games
	 * 		    Should run every minute, will check for self exclusion request
	 */
	public function executeSelfExclusion() {
		$this->utils->debug_log("<=============================== RESP_GAMING: EXECUTE SELF EXCLUSION ===============================>");

		$this->load->model(array('player_model', 'game_provider_auth', 'responsible_gaming', 'responsible_gaming_history', 'communication_preference_model'));
        $selfExclusionData = $this->responsible_gaming->getData(null, [Responsible_gaming::SELF_EXCLUSION_TEMPORARY, Responsible_gaming::SELF_EXCLUSION_PERMANENT],
			Responsible_gaming::STATUS_REQUEST, $this->utils->getCurrentDatetime());

		if (!empty($selfExclusionData)) {
			foreach ($selfExclusionData as $sed) {
				$this->utils->blockAndKickPlayerInGameAndWebsite($sed->player_id, true, true, false);
				if($this->responsible_gaming->setSelfExclusionToApprove($sed->id,$sed->player_id)){
                    $this->responsible_gaming_history->addSelfExclusionAutoApprovedRecord($sed->id, $sed->status);

                    if($this->utils->isEnabledFeature('enable_communication_preferences')) {
                        $this->communication_preference_model->updateCommunicationPreferenceWithSelfExclusion($sed->player_id, 'Auto-Off All Communication Preference By Cronjob Auto-Approve Self Exclusion id '.$sed->id);
                    }

                    $this->utils->debug_log("executeSelfExclusion:-----------------------------> ",json_encode($sed));
                }else{
                    $this->utils->debug_log("SET SELF_EXCLUSION TO APPROVE FAILED-----------------------------> ");
                }
			}
		}else{
            $this->utils->debug_log("GET EMPTY SELF_EXCLUSION DATA-----------------------------> ");
        }
	}

	/**
	 * overview : Responsible gaming (Temporary Unself Exclusion)
	 * Will automatically unblock player in website and games unlike permanent the player will unself
	 * exclude upon request.
	 * Should run every minute, will check for due temporary self exclusion
	 */
	public function executeUnSelfExclusion() {
		$this->utils->debug_log("<=============================== RESP_GAMING: EXECUTE UNSELF EXCLUSION ===============================>");

		$this->load->model(array('player_model', 'game_provider_auth', 'responsible_gaming','responsible_gaming_history','operatorglobalsettings'));
		$currentDatetime = $this->utils->getCurrentDatetime();
		$temporarySelfExclusionData = $this->responsible_gaming->getData(null, Responsible_gaming::SELF_EXCLUSION_TEMPORARY,
			[Responsible_gaming::STATUS_REQUEST, Responsible_gaming::STATUS_APPROVED, Responsible_gaming::STATUS_COOLING_OFF]);
        $auto_reopen = (int)$this->operatorglobalsettings->getSettingIntValue('automatic_reopen_temp_self_exclusion_account',0);
        $this->utils->debug_log("AUTO REOPEN TEMP SELF EXCLUSION ACCOUNT:---------------------------->",(bool)$auto_reopen);
		if (!empty($temporarySelfExclusionData)) {
			foreach ($temporarySelfExclusionData as $tse) {
                if( ($tse->status == Responsible_gaming::STATUS_APPROVED) && ($tse->date_to <= $currentDatetime) && ($tse->cooling_off_to > $currentDatetime) ) {
                    if($this->responsible_gaming->setSelfExclusionToCoolingOff($tse->id,$tse->player_id)) {
                        $this->responsible_gaming_history->addSelfExclusionAutoCoolingOffRecord($tse->id, $tse->status);
                        $this->utils->debug_log("executeUnSelfExclusion CoolingOff:-----------------------------> ",json_encode($tse));
                        continue;
                    }else{
                        $this->utils->debug_log("SET SELF_EXCLUSION TO COOLING OFF FAILED-----------------------------> ");
                    }
                }

                if(in_array($tse->status,[Responsible_gaming::STATUS_REQUEST,Responsible_gaming::STATUS_APPROVED])){
                    $this->utils->debug_log("SELF_EXCLUSION STATUS NOT IN COOLING OFF-----------------------------> ",$tse->id);
                    continue;
                }

                //requests below all in cooling_off_to status
                if($tse->cooling_off_to >= $currentDatetime){
                    $this->utils->debug_log("SELF_EXCLUSION STILL IN COOLING OFF-----------------------------> ",$tse->id);
                    continue;
                }

                if(!$auto_reopen) {
                    $this->utils->debug_log("DISABLED AUTO REOPEN SELF_EXCLUSION ACCOUNT-----------------------------> ",$tse->id);
                    continue;
                }

                //cooling_off_to    equal or less than   currentDateTime
                $this->utils->unblockPlayerInGameAndWebsite($tse->player_id);
                if($this->responsible_gaming->setSelfExclusionToExpire($tse->id,$tse->player_id)){
                    $this->responsible_gaming_history->addSelfExclusionExpiredRecord($tse->id, $tse->status);
                    $this->utils->debug_log("executeUnSelfExclusion:-----------------------------> ",json_encode($tse));
                }else{
                    $this->utils->debug_log("SET SELF_EXCLUSION TO EXPIRED FAILED-----------------------------> ");
                }
            }
		}else{
            $this->utils->debug_log("GET EMPTY SELF_EXCLUSION DATA-----------------------------> ");
        }
	}

	/**
	 * overview : Responsible gaming (Cool off)
	 *
	 * detail : Must unblock the player in the website
	 * 		    Must unblock all games
	 * 			Should run every minute, will check for cool off request
	 */
	public function executeCoolOff() {
		$this->utils->debug_log("<=============================== RESP_GAMING: EXECUTE COOLOFF ===============================>");

		$this->load->model(array('player_model', 'game_provider_auth', 'responsible_gaming', 'responsible_gaming_history', 'communication_preference_model'));
		$coolOffData = $this->responsible_gaming->getData(null, Responsible_gaming::COOLING_OFF,
			Responsible_gaming::STATUS_REQUEST, $this->utils->getCurrentDatetime());

		if (!empty($coolOffData)) {
			foreach ($coolOffData as $co) {
				$this->utils->blockAndKickPlayerInGameAndWebsite($co->player_id, true, true, true);
                if($this->responsible_gaming->setCoolOffToApprove($co->id, $co->player_id)){
                    $this->responsible_gaming_history->addCoolOffAutoApprovedRecord($co->id, $co->status);

                    if($this->utils->isEnabledFeature('enable_communication_preferences')) {
                        $this->communication_preference_model->updateCommunicationPreferenceWithSelfExclusion($co->player_id, 'Auto-Off All Communication Preference By Cronjob Auto-Approve CoolOff id '.$co->id);
                    }

                    $this->utils->debug_log("executeCoolOff:-----------------------------> ",json_encode($co));
                }else{
                    $this->utils->debug_log("SET COOL_OFF TO APPROVE FAILED-----------------------------> ");
                }
			}
		}else{
            $this->utils->debug_log("GET EMPTY COOL_OFF DATA-----------------------------> ");
        }
	}

	/**
	 * overview : Responsible gaming (Remove Cool off)
	 *
	 * detail : Must unblock the player in the website
	 * 			Must unblock all games
	 * 			Should run every minute, will check for due cool off
	 */
	public function executeUnCoolOff() {
		$this->utils->debug_log("<=============================== RESP_GAMING: EXECUTE UNCOOLOFF ===============================>");

		$this->load->model(array('player_model', 'game_provider_auth', 'responsible_gaming','responsible_gaming_history'));
		$coolOffData = $this->responsible_gaming->getData(null, Responsible_gaming::COOLING_OFF,
            [Responsible_gaming::STATUS_REQUEST, Responsible_gaming::STATUS_APPROVED], null, $this->utils->getCurrentDatetime());

		if (!empty($coolOffData)) {
			foreach ($coolOffData as $co) {
				$this->utils->unblockPlayerInGameAndWebsite($co->player_id);
                if($this->responsible_gaming->setCoolOffToExpire($co->id, $co->player_id)){
                    $this->responsible_gaming_history->addCoolOffAutoExpiredRecord($co->id, $co->status);
                    $this->utils->debug_log("executeUnCoolOff:-----------------------------> ",json_encode($co));
                }else{
                    $this->utils->debug_log("SET COOL_OFF TO EXPIRED FAILED-----------------------------> ");
                }
			}
		}else{
            $this->utils->debug_log("GET EMPTY COOL_OFF DATA-----------------------------> ");
        }
	}

	/**
	 * overview : Responsible gaming (Session Limit Checker)
	 * For player with session limit settings
	 * Should run every minute and check if player reached the playing time period set by the player
	 * if period reached system should automatically logout the player and message player that he will be logout automatically
	 */
	public function executeSessionLimitChecker() {
		$this->utils->debug_log("<=============================== RESP_GAMING: EXECUTE SESSION LIMIT CHECKER ===============================>");

		$this->load->model(array('player_model', 'game_provider_auth', 'responsible_gaming', 'external_system'));
		$sessionLimitData = $this->responsible_gaming->getData(null, Responsible_gaming::SESSION_LIMITS, [Responsible_gaming::STATUS_REQUEST, Responsible_gaming::STATUS_APPROVED]);

		if (!empty($sessionLimitData)) {
			foreach ($sessionLimitData as $sld) {
				//get player's totol playing time
				$currentPlayerPlayingTime = $this->player_model->getPlayerTotalPlayingTimeInMinutes($sld->player_id, $sld->date_from);
				if ($sld->period_cnt == round($currentPlayerPlayingTime->total)) {
					//change self session limit status request status to expired
					$data = array("player_id" => $sld->player_id,
						"type" => $sld->type,
						"updated_at" => $this->utils->getNowForMysql(),
						"status" => Responsible_gaming::STATUS_EXPIRED,
					);
					$this->responsible_gaming->updateResponsibleGamingData($data);

					sleep(1);
					$this->utils->blockAndKickPlayerInGameAndWebsite($ld->player_id, true);
				}
			}
		}
	}

	/**
	 * overview : Responsible gaming (Loss Limit)
	 * For player with loss limit settings
	 * Should run every minute, approve the deposit limit request
	 */
	public function approveLossLimitRequest() {
		$this->utils->debug_log("<=============================== RESP_GAMING: APPROVED LOSS LIMIT REQUEST ===============================>");

		$this->load->model(array('responsible_gaming'));
		$lossLimitData = $this->responsible_gaming->getData(null, Responsible_gaming::LOSS_LIMITS,
			Responsible_gaming::STATUS_REQUEST, $this->utils->getCurrentDatetime());
		if (!empty($lossLimitData)) {
			foreach ($lossLimitData as $lld) {
				$data = array("player_id" => $lld->player_id,
					"type" => $lld->type,
					"updated_at" => $this->utils->getNowForMysql(),
					"status" => Responsible_gaming::STATUS_APPROVED,
				);
				$this->responsible_gaming->updateResponsibleGamingData($data);
			}
		}
	}

	/**
	 * overview : Responsible gaming (Loss Limit daily)
	 * For player with loss limit settings
	 * Should run daily, get all player total loss amount setting and compare to actual total loss amount
	 * Once player total amount setting is reached system should kick,block the player from game and the website
	 * And will also set reactivation period as per player settings
	 */
	public function executeDailyLossLimitChecker() {
		$this->utils->debug_log("<=============================== RESP_GAMING: EXECUTE DAILY LOSS LIMIT CHECKER ===============================>");

		$this->load->model(array('responsible_gaming'));
		$lossLimitData = $this->responsible_gaming->getData(null, Responsible_gaming::LOSS_LIMITS,
			Responsible_gaming::STATUS_APPROVED, null, null, Responsible_gaming::PERIOD_TYPE_DAY);

		$date_from = $this->utils->getTodayForMysql() . " 00:00:00";
		$date_to = $this->utils->getTodayForMysql() . " 23:59:59";
		$this->checkPlayerDepositOrLossLimit($lossLimitData, $date_from, $date_to, Responsible_gaming::LOSS_LIMITS);
	}

	/**
	 * overview : Responsible gaming (Loss Limit weekly)
	 * For player with loss limit settings
	 * Should run weekly, get all player total loss amount setting and compare to actual total loss amount
	 * Once player total amount setting is reached system should kick,block the player from game and the website
	 * And will also set reactivation period as per player settings
	 */
	public function executeWeeklyLossLimitChecker() {
		$this->utils->debug_log("<=============================== RESP_GAMING: EXECUTE WEEKLY LOSS LIMIT CHECKER ===============================>");

		$this->load->model(array('responsible_gaming'));
		$lossLimitData = $this->responsible_gaming->getData(null, Responsible_gaming::LOSS_LIMITS,
			Responsible_gaming::STATUS_APPROVED, null, null, Responsible_gaming::PERIOD_TYPE_WEEK);

		$date_from = new DateTime($this->utils->getNowForMysql());
		$date_from->sub(new DateInterval('P7D'));
		$date_from = $date_from->format('Y-m-d') . " 00:00:00";
		$date_to = $this->utils->getTodayForMysql() . " 23:59:59";

		$this->checkPlayerDepositOrLossLimit($lossLimitData, $date_from, $date_to, Responsible_gaming::LOSS_LIMITS);
	}

	/**
	 * overview : Responsible gaming (Loss Limit montly)
	 * For player with loss limit settings
	 * Should run monthly, get all player total loss amount setting and compare to actual total loss amount
	 * Once player total amount setting is reached system should kick,block the player from game and the website
	 * And will also set reactivation period as per player settings
	 */
	public function executeMonthlyLossLimitChecker() {
		$this->utils->debug_log("<=============================== RESP_GAMING: EXECUTE MONTHLY LOSS LIMIT CHECKER ===============================>");

		$this->load->model(array('responsible_gaming'));
		$lossLimitData = $this->responsible_gaming->getData(null, Responsible_gaming::LOSS_LIMITS,
			Responsible_gaming::STATUS_APPROVED, null, null, Responsible_gaming::PERIOD_TYPE_MONTH);

		$date_from = new DateTime($this->utils->getNowForMysql());
		$date_from->sub(new DateInterval('P1M'));
		$date_from = $date_from->format('Y-m-d') . " 00:00:00";
		$date_to = $this->utils->getTodayForMysql() . " 23:59:59";

		$this->checkPlayerDepositOrLossLimit($lossLimitData, $date_from, $date_to, Responsible_gaming::LOSS_LIMITS);
	}

    /**
     * overview : auto check withdraw condition
     */
    public function execute_auto_check_withdraw_condition(){
        $this->utils->debug_log("<============= START: AUTO CHECK WITHDRAW CONDITION =============>");

        $this->load->model(['withdraw_condition', 'wallet_model']);
        $unfinished_players = $this->withdraw_condition->getPlayerByUnfinishedAllWithdrawCondition(null, false);
        $this->utils->debug_log('unfinished_players', $unfinished_players);


        if(!empty($unfinished_players)){
            $controller = $this;
            $failed_player=[];
            $cnt=0;

            foreach($unfinished_players as $player_id){
                $message = null;
                $extra_info = ['auto_check_wc_from' => Withdraw_condition::AUTO_CHECK_WITHDRAW_CONDITION_AND_MOVE_BIG_WALLET_FROM_SCHEDULER];
                $success = $controller->wallet_model->lockAndTransForPlayerBalance($player_id, function () use ($controller, $player_id, &$message, $extra_info) {
                    return $controller->withdraw_condition->autoCheckWithdrawConditionAndMoveBigWallet($player_id, $message, null, false, true, null, $extra_info);
                });

                $this->utils->debug_log('execute auto check withdraw condition foreach result', $success, $player_id, $message);
                if(!$success){
                    $this->utils->error_log('refresh player: '. $player_id .' failed');
                    $failed_player[]= $player_id ;
                }
            }

            if(!empty($failed_player)){
                $this->utils->error_log('failed_player', $failed_player);
            }

            $this->utils->info_log('done : '.$cnt);
        }else{
            $this->utils->debug_log('execute auto check withdraw condition, no player has withdraw condition', $unfinished_players);
        }

        $this->utils->debug_log("<============= END: AUTO CHECK WITHDRAW CONDITION =============>");
	}

	/**
	 * overview : check player deposit or loss limit
	 *
	 * @param $limitData
	 * @param $date_from
	 * @param $date_to
	 * @param $type
     *
     * @deprecated curtis
	 */
	private function checkPlayerDepositOrLossLimit($limitData, $date_from, $date_to, $type) {
	    /*
		$this->load->model(array('responsible_gaming', 'game_logs', 'transactions'));
		if (!empty($limitData)) {
			foreach ($limitData as $ld) {
				if ($type == Responsible_gaming::LOSS_LIMITS) {
					$totalPlayerLoss = $this->game_logs->sumBetsWinsLossByDatetime($ld->player_id, $date_from, $date_to);
					$compareData = $totalPlayerLoss['2'];
				} elseif ($type == Responsible_gaming::DEPOSIT_LIMITS) {
					$totalDepositAmount = $this->transactions->getPlayerTotalDeposits($ld->player_id, $date_from, $date_to);
					$compareData = $totalDepositAmount;
				}

				if ($compareData == $ld->amount) {
                    if ($type == Responsible_gaming::LOSS_LIMITS) {
                        $this->utils->blockAndKickPlayerInGameAndWebsite($ld->player_id, true, true, true);
                    }
					$currentDate = new DateTime();
					$datetime_to_add = new DateInterval('P' . $ld->period_cnt . 'D'); //based on player setting
					$currentDate->add($datetime_to_add);
					$reactivation_period = $currentDate->format("Y-m-d H:i");

					$data = array("player_id" => $ld->player_id,
						"type" => $ld->type,
						"updated_at" => $this->utils->getNowForMysql(),
						"date_to" => $reactivation_period, //reactivation date,
						"status" => Responsible_gaming::STATUS_PLAYER_DEACTIVATED,
					);
					$this->responsible_gaming->updateResponsibleGamingData($data);
				}
			}
		}
	    */
	}

	/**
	 * overview : Responsible gaming (Deposit & Loss Limit Reactivation)
	 * For player with deposit & loss limit settings
	 * Should run every minute, get all player with reactivation period then unblock to website and game
     *
     * @deprecated curtis
	 */
	public function executeDepositAndLossLimitPlayerReactivation() {
	    /*

		$this->utils->debug_log("<=============================== RESP_GAMING: EXECUTE DEPOSIT AND LOSS LIMIT PLAYER REACTIVATION ===============================>");

		$this->load->model(array('operatorglobalsettings', 'responsible_gaming'));

		$lossLimitData = $this->responsible_gaming->getData(null, Responsible_gaming::LOSS_LIMITS,
			Responsible_gaming::STATUS_PLAYER_DEACTIVATED, null, $this->utils->getCurrentDatetime());

		$depositLimitData = $this->responsible_gaming->getData(null, Responsible_gaming::DEPOSIT_LIMITS,
			Responsible_gaming::STATUS_PLAYER_DEACTIVATED, null, $this->utils->getCurrentDatetime());

		$limitData = array_merge((array) $lossLimitData, (array) $depositLimitData);

		if (!empty($limitData)) {
			foreach ($limitData as $lld) {
				$this->utils->unblockPlayerInGameAndWebsite($lld->player_id);

				$player_reactication_day_cnt = $this->operatorglobalsettings->getSetting('player_reactication_day_cnt');
				$currentDate = new DateTime();
				$datetime_from_add = new DateInterval('P' . $player_reactication_day_cnt->value . 'D');
				$currentDate->add($datetime_from_add);
				$date_from = $currentDate->format("Y-m-d H:i");

				$data = array("player_id" => $lld->player_id,
					"type" => $lld->type,
					"updated_at" => $this->utils->getNowForMysql(),
					"date_from" => $date_from, //will change to request date
					"status" => Responsible_gaming::STATUS_APPROVED,
				);
				$this->responsible_gaming->updateResponsibleGamingData($data);
			}
		}
	    */
	}

    /**
     * overview : Responsible gaming (Deposit Limit Auto Subscribe)
     *
     * detail : Must Auto Subscribe Deposit Limit
     * 		    Should run every hour, will check for apprved of deposit limit
     */
    public function executeDepositLimitAutoSubscribe(){
        $this->utils->debug_log("<=============================== RESP_GAMING: EXECUTE DEPOSIT_LIMIT AUTO SUBSCRIBE ===============================>");

        $this->load->model(array('responsible_gaming'));
        $this->load->library(array('utils','player_responsible_gaming_library'));

        $depositLimitData = $this->responsible_gaming->getData(null,Responsible_gaming::DEPOSIT_LIMITS,[Responsible_gaming::STATUS_REQUEST,Responsible_gaming::STATUS_APPROVED]);
        if (empty($depositLimitData)) {
            $this->utils->debug_log("GET EMPTY DEPOSIT_LIMIT DATA-----------------------------> ");
        }else{
            foreach ($depositLimitData as $depositLimit) {
                //less than 1 hour
                if(!$this->utils->isTimeoutNow($depositLimit->date_to,'60','-')){
                    $this->utils->debug_log("DEPOSIT_LIMIT AUTO SUBSCRIBE FAILED, BACASUSE REQUEST STILL REMAIN OVER ONE HOUR-----------------------------> ", $depositLimit->id);
                    continue;
                }

                //if amount id zero
                if(empty($depositLimit->amount)){
                    $this->utils->debug_log("BECAUSE SET ZERO TO DEPOSIT_LIMIT, THEN DEPOSIT_LIMIT WILL NOT AUTO SUBSCRIBE AFTER THIS CYCLE-----------------------------> ", $depositLimit->id);
                    continue;
                }

                $date_from = strtotime($depositLimit->date_to)+1;
                $new_date_from = date ("Y-m-d H:i:s", intval($date_from));

                //get data with playerId and new_date_from
                $playerDepositLimitData = $this->responsible_gaming->getData($depositLimit->player_id,Responsible_gaming::DEPOSIT_LIMITS,[Responsible_gaming::STATUS_REQUEST,Responsible_gaming::STATUS_APPROVED],$new_date_from);
                if(empty($playerDepositLimitData)){
                    $this->utils->debug_log("DEPOSIT_LIMIT GET EMPTY DATA WITH PLAYER ID AND NEW DATE FROM-----------------------------> ", $depositLimit->player_id.'|'.$new_date_from);
                    continue;
                }

                //There are 2 requests that are the most requests at the same time, and auto subscribe only for player who have one request
                $needAutoSubscribe = (count($playerDepositLimitData) == Responsible_gaming::ONLY_HAVE_ONE_REQUEST);
                if(!$needAutoSubscribe){
                    $this->utils->debug_log("PLAYER HAS ALREADY REQUESTED NEXT CYCLE'S WAGERING_LIMIT  -----------------------------> ", json_encode($playerDepositLimitData));
                    continue;
                }

                foreach($playerDepositLimitData as $playerData){
                    $insert_id = $this->player_responsible_gaming_library->AutoSubscribeDepositLimit($playerData->player_id, $playerData->period_cnt, $playerData->date_to, $playerData->amount);

                    if(empty($insert_id)){
                        $this->utils->debug_log("DEPOSIT_LIMIT AUTO SUBSCRIBE FAILED WITH-----------------------------> ", json_encode($depositLimit));
                    }else{
                        $this->utils->debug_log("DEPOSIT_LIMIT AUTO SUBSCRIBE SUCCESSFULLY WITH-----------------------------> ", $insert_id);
                    }
                }
            }
        }
    }

    /**
     * overview : Responsible gaming (Wagering Limit Auto Subscribe)
     *
     * detail : Must Auto Subscribe Wagering Limit
     * 		    Should run every hour, will check for apprved of wagering limit
     */
    public function executeWageringLimitAutoSubscribe(){
        $this->utils->debug_log("<=============================== RESP_GAMING: EXECUTE WAGERING_LIMIT AUTO SUBSCRIBE ===============================>");

        $this->load->model(array('responsible_gaming'));
        $this->load->library(array('utils','player_responsible_gaming_library'));

        $wageringLimitData = $this->responsible_gaming->getData(null,Responsible_gaming::WAGERING_LIMITS,[Responsible_gaming::STATUS_REQUEST,Responsible_gaming::STATUS_APPROVED]);
        if (empty($wageringLimitData)) {
            $this->utils->debug_log("GET EMPTY WAGERING_LIMIT DATA-----------------------------> ");
        }else{
            foreach ($wageringLimitData as $wageringLimit) {
                //if more than 1 hour
                if(!$this->utils->isTimeoutNow($wageringLimit->date_to,'60','-')) {
                    $this->utils->debug_log("WAGERING_LIMIT AUTO SUBSCRIBE FAILED, BACASUSE REQUEST STILL REMAIN OVER ONE HOUR-----------------------------> ", $wageringLimit->id);
                    continue;
                }

                //if amount is zero
                if(empty($wageringLimit->amount)) {
                    $this->utils->debug_log("BECAUSE SET ZERO TO WAGERING_LIMIT, THEN WAGERING_LIMIT WILL NOT AUTO SUBSCRIBE AFTER THIS CYCLE-----------------------------> ", $wageringLimit->id);
                    continue;
                }

                $date_from = strtotime($wageringLimit->date_to)+1;
                $new_date_from = date ("Y-m-d H:i:s", intval($date_from));

                //get data with playerId and new_date_from
                $playerWageringLimitData = $this->responsible_gaming->getData($wageringLimit->player_id,Responsible_gaming::WAGERING_LIMITS,[Responsible_gaming::STATUS_REQUEST,Responsible_gaming::STATUS_APPROVED],$new_date_from);
                if(empty($playerWageringLimitData)){
                    $this->utils->debug_log("WAGERING_LIMIT GET EMPTY DATA WITH PLAYER ID AND NEW DATE FROM-----------------------------> ", $wageringLimit->player_id.'|'.$new_date_from);
                    continue;
                }

                //There are 2 requests that are the most requests at the same time, and auto subscribe only for player who have one request
                $needAutoSubscribe = (count($playerWageringLimitData) == Responsible_gaming::ONLY_HAVE_ONE_REQUEST);
                if(!$needAutoSubscribe){
                    $this->utils->debug_log("PLAYER HAS ALREADY REQUESTED NEXT CYCLE'S WAGERING_LIMIT  -----------------------------> ", json_encode($playerWageringLimitData));
                    continue;
                }

                foreach($playerWageringLimitData as $playerData){
                    $insert_id = $this->player_responsible_gaming_library->AutoSubscribeWageringLimit($playerData->player_id, $playerData->period_cnt, $playerData->date_to, $playerData->amount);
                    if(empty($insert_id)){
                        $this->utils->debug_log("WAGERING_LIMIT AUTO SUBSCRIBE FAILED WITH-----------------------------> ", json_encode($wageringLimit));
                    }else{
                        $this->utils->debug_log("WAGERING_LIMIT AUTO SUBSCRIBE SUCCESSFULLY WITH-----------------------------> ", $insert_id);
                    }
                }
            }
        }
    }

	/**
	 * overview : Responsible gaming (Deposit Limit)
	 * For player with deposit limit settings
	 * Should run every minute, approved the deposit limit request
	 */
	public function approveDepositLimitRequest() {
		$this->utils->debug_log("<=============================== RESP_GAMING: APPROVE DEPOSIT LIMIT REQUEST ===============================>");

		$this->load->model(array('responsible_gaming'));
		$depLimitData = $this->responsible_gaming->getData(null, Responsible_gaming::DEPOSIT_LIMITS,
			Responsible_gaming::STATUS_REQUEST, $this->utils->getCurrentDatetime());
		if (!empty($depLimitData)) {
			foreach ($depLimitData as $dld) {
				$data = array("player_id" => $dld->player_id,
					"type" => $dld->type,
					"updated_at" => $this->utils->getNowForMysql(),
					"status" => Responsible_gaming::STATUS_APPROVED,
				);
				$this->responsible_gaming->updateResponsibleGamingData($data);
			}
		}
	}

	/*
     * overview : Responsible Gaming (Deposit Limit Auto Expire)
	 * detail auto exipire deposit limit due to player seldom login in and go responsible gaming page
	 */
	public function executeDepositLimiAutoExpire() {
        $this->utils->debug_log("<=============================== RESP_GAMING: AUTO EXPIRE DEPOSIT LIMIT REQUEST ===============================>");

        $this->load->model(['responsible_gaming', 'responsible_gaming_history']);
        $depLimitData = $this->responsible_gaming->getData(null, Responsible_gaming::DEPOSIT_LIMITS,
            [Responsible_gaming::STATUS_REQUEST, Responsible_gaming::STATUS_APPROVED], null, $this->utils->getCurrentDatetime());

        if (!empty($depLimitData)) {
            foreach ($depLimitData as $dld) {
                if($this->responsible_gaming->setDepositLimitsToExpire($dld->id, $dld->player_id)){
                    $this->utils->debug_log("AUTO EXPIRE DEPOSIT LIMIT REQUEST ============ id [" . $dld->id . "] , player id [" . $dld->player_id . "]===================>");
                    $this->responsible_gaming_history->addDepositLimitsAutoExpiredRecord($dld->id,$dld->status);
                }
            }
        }
    }

    /*
	 * overview : Responsible Gaming (Deposit Limit Auto Expire)
     * detail auto exipire deposit limit due to player seldom login in and go responsible gaming page
	 */
    public function executeWageringLimiAutoExpire() {
        $this->utils->debug_log("<=============================== RESP_GAMING: AUTO EXPIRE WAGERING LIMIT REQUEST ===============================>");

        $this->load->model(['responsible_gaming', 'responsible_gaming_history']);
        $wgrLimitData = $this->responsible_gaming->getData(null, Responsible_gaming::WAGERING_LIMITS,
            [Responsible_gaming::STATUS_REQUEST, Responsible_gaming::STATUS_APPROVED], null, $this->utils->getCurrentDatetime());

        if (!empty($wgrLimitData)) {
            foreach ($wgrLimitData as $wld) {
                if($this->responsible_gaming->setWageringLimitsToExpire($wld->id, $wld->player_id)){
                    $this->utils->debug_log("AUTO EXPIRE WAGERING LIMIT REQUEST ============ id [" . $wld->id . "] , player id [" . $wld->player_id . "]===================>");
                    $this->responsible_gaming_history->addWageringLimitsAutoExpiredRecord($wld->id,$wld->status);
                }
            }
        }
    }

	/**
	 * overview : Responsible gaming (Deposit Limit daily)
	 * For player with deposit limit settings
	 * Should run daily, get all player deposit amount setting and compare to actual total deposit amount
	 * Once player total deposit amount setting is reached system should kick,block the player from game and the website
	 * And will also set reactivation period as per player settings
     *
     * @deprecated curtis
	 */
	public function executeDailyDepositLimitChecker() {
	    /*
		$this->utils->debug_log("<=============================== RESP_GAMING: EXECUTE DAILY DEPOSIT LIMIT CHECKER ===============================>");

		$this->load->model(array('responsible_gaming'));
		$depositLimitData = $this->responsible_gaming->getData(null, Responsible_gaming::DEPOSIT_LIMITS,
			Responsible_gaming::STATUS_APPROVED, null, null, Responsible_gaming::PERIOD_TYPE_DAY);

		$date_from = $this->utils->getTodayForMysql() . " 00:00:00";
		$date_to = $this->utils->getTodayForMysql() . " 23:59:59";

		$this->checkPlayerDepositOrLossLimit($depositLimitData, $date_from, $date_to, Responsible_gaming::DEPOSIT_LIMITS);
	    */
	}
	/**
	 * overview : Responsible gaming (Deposit Limit weekly)
	 * For player with deposit limit settings
	 * Should run daily, get all player deposit amount setting and compare to actual total deposit amount
	 * Once player total deposit amount setting is reached system should kick,block the player from game and the website
	 * And will also set reactivation period as per player settings
     *
     * @deprecated curtis
	 */
	public function executeWeeklyDepositLimitChecker() {
        /*
		$this->utils->debug_log("<=============================== RESP_GAMING: EXECUTE WEEKLY DEPOSIT LIMIT CHECKER ===============================>");

		$this->load->model(array('responsible_gaming'));
		$depositLimitData = $this->responsible_gaming->getData(null, Responsible_gaming::DEPOSIT_LIMITS,
			Responsible_gaming::STATUS_APPROVED, null, null, Responsible_gaming::PERIOD_TYPE_WEEK);

		$date_from = new DateTime($this->utils->getNowForMysql());
		$date_from->sub(new DateInterval('P7D'));
		$date_from = $date_from->format('Y-m-d') . " 00:00:00";
		$date_to = $this->utils->getTodayForMysql() . " 23:59:59";

		$this->checkPlayerDepositOrLossLimit($depositLimitData, $date_from, $date_to, Responsible_gaming::DEPOSIT_LIMITS);
        */
	}

	/**
	 * overview : Responsible gaming (Deposit Limit monthly)
	 * For player with deposit limit settings
	 * Should run daily, get all player deposit amount setting and compare to actual total deposit amount
	 * Once player total deposit amount setting is reached system should kick,block the player from game and the website
	 * And will also set reactivation period as per player settings
     *
     * @deprecated curtis
	 */
	public function executeMonthlyDepositLimitChecker() {
        /*
		$this->utils->debug_log("<=============================== RESP_GAMING: EXECUTE MONTHLY DEPOSIT LIMIT CHECKER ===============================>");

		$this->load->model(array('responsible_gaming'));
		$depositLimitData = $this->responsible_gaming->getData(null, Responsible_gaming::DEPOSIT_LIMITS,
			Responsible_gaming::STATUS_APPROVED, null, null, Responsible_gaming::PERIOD_TYPE_MONTH);

		$player_reactication_day_cnt = $this->operatorglobalsettings->getSetting('player_reactication_day_cnt');
		$date_from = new DateTime($this->utils->getNowForMysql());
		$date_from->sub(new DateInterval('P' . $player_reactication_day_cnt->value . 'D'));
		$date_from = $date_from->format('Y-m-d') . " 00:00:00";
		$date_to = $this->utils->getTodayForMysql() . " 23:59:59";

		$this->checkPlayerDepositOrLossLimit($depositLimitData, $date_from, $date_to, Responsible_gaming::DEPOSIT_LIMITS);
        */
	}

	/**
	 * overview : generate password
	 *
	 * @param string $password
	 */
	public function generate_password($password) {

		require_once APPPATH . 'libraries/phpass-0.1/PasswordHash.php';

		$hasher = new PasswordHash('8', TRUE);
		$this->returnText($hasher->HashPassword($password));
	}

	/**
	 * overview : import payment account setting
	 */
	public function importPaymentAccountSetting() {
		$this->load->model('operatorglobalsettings');
		$this->operatorglobalsettings->importPaymentAccountSetting();
	}

	/**
	 * overview : add all jobs
	 */
	public function add_all_jobs() {
		$this->load->model(array('operatorglobalsettings'));
		$this->operatorglobalsettings->startTrans();

		$success = $this->operatorglobalsettings->addAllCronJobs();

		$success = $this->operatorglobalsettings->endTransWithSucc() && $success;

		$msg = $this->utils->debug_log('success', $success);

		$this->returnText($msg);
	}

	/**
	 * overview : fixed duplicate wallet
	 */
	public function fix_dup_wallet() {
		$sql = <<<EOD
drop table if exists dup_wallet;
EOD;

		$this->db->query($sql);

		$sql = <<<EOD
create table dup_wallet
select * from playeraccount
where concat(playerId,'-',`type`,'-',typeId) in
(
select concat(playerId,'-',`type`,'-',typeId) from playeraccount
where type='subwallet'
group by playerId, `type`, typeId
having count(playerAccountId)>1
);
EOD;

		$this->db->query($sql);

		$this->db->query("create index idx_playeraccountid on dup_wallet(playerAccountId)");
		$this->db->query("create index idx_playerId on dup_wallet(playerId)");
		$this->db->query("create index idx_type on dup_wallet(type)");
		$this->db->query("create index idx_typeId on dup_wallet(typeId)");

		//call wallet model
		$this->load->model(array('wallet_model'));
		$result = $this->wallet_model->clearDupWallet('dup_wallet');

		$this->returnText($result);

	}

	/**
     * Deprecated by curtis.php.tw due to no one use
	 * overview : apply promo rule
	 *
	 * @param $playerId
	 * @param $promoCmsSettingId
	 */
	public function apply_promo_rule($playerId, $promoCmsSettingId) {
		$this->load->model(array('promorules'));

		$success = false;
		$message = 'error.default.message';

		$promorule = $this->promorules->getPromoruleByPromoCms($promoCmsSettingId);
		$promorulesId = $promorule['promorulesId'];

		if (!empty($playerId) && !empty($promorule)) {
			$this->utils->debug_log('playerId', $playerId, 'promorule', $promorule['promorulesId'], 'promoCmsSettingId', $promoCmsSettingId);

			$ruleId = $promorulesId;
			//lock it
			// $lock_it = $this->lockActionById($playerId, 'promo');
			$lock_it = $this->utils->lockResourceBy($playerId, Utils::LOCK_ACTION_BALANCE, $lockedKey);
			// $lock_it = $this->lockPlayerBalance($playerId);
			$this->utils->debug_log('lock promo', $playerId, 'ruleId', $ruleId, $lock_it);
			$success = $lock_it;

			if ($lock_it) {
				//lock success
				try {
					$this->startTrans();
					list($success, $message) = $this->promorules->checkAndProcessPromotion(
						$playerId, $promorule, $promoCmsSettingId);
					$transSucc = $this->endTransWithSucc();
					$success = $success && $transSucc;
					// if ($promorule['promoType'] == Promorules::PROMO_TYPE_DEPOSIT) {
					// 	list($success, $message) = $this->processDepositPromo($playerId, $promorule, $promoCmsSettingId);
					// } else {
					// 	list($success, $message) = $this->processNonDepositPromo($playerId, $promorule, $promoCmsSettingId);
					// }
				} finally {
					// release it
					$rlt = $this->utils->releaseResourceBy($playerId, Utils::LOCK_ACTION_BALANCE, $lockedKey);
					// $rlt = $this->releasePlayerBalance($playerId);
					// $rlt = $this->player_model->transReleaseLock($trans_key);
					$this->utils->debug_log('release promo lock', $playerId, 'ruleId', $ruleId, $rlt);
				}
			}

			if (!$success) {
				//DB transaction failed
				$success = false;
				$message = $message; //'error.default.message';
				// $this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);
			}
		}
		$msg = $this->utils->debug_log('success', $success, 'message', $message);

		$this->returnText($msg);
	}

	/**
	 * overview : fix sub level
	 */
	public function fix_sub_level() {
		$this->load->model(array('affiliatemodel'));
		$this->affiliatemodel->startTrans();
		$success = $this->affiliatemodel->fixSubLevelForAllSettings();
		$success = $this->affiliatemodel->endTransWithSucc() && $success;
		$msg = $this->utils->debug_log('fix_sub_level result', $success);

		$this->returnText($msg);
	}

	/**
	 * overview : create player for game logs generator
	 *
	 * @param int $playerCount
	 * @param int $numberOfGameLogs
	 */
	public function createPlayerForGameLogsGenerator($playerCount = 3, $numberOfGameLogs = 5) {
		$game_api = array("PT_API" => PT_API, "MG_API" => MG_API, "BBIN_API" => BBIN_API, "LB_API" => LB_API, "IBC_API" => IBC_API, "EBET_API" => EBET_API);
		$players = array();
		for ($i = 0; $i < $playerCount; $i++) {
			$playerName = "testpgl" . random_string('numeric');
			$password = $playerName;

			//set other half of player with affiliate
			if ($i % 2 == 0) {
				$withAffiliateFlag = true;
			} else {
				$withAffiliateFlag = false;
			}

			//add to player table
			$this->registerPlayer($playerName, $password, $withAffiliateFlag);

			//create game api account
			foreach ($game_api as $game) {
				$api = $this->utils->loadExternalSystemLibObject($game);

				if ($api) {
					//create game api acoount for player
					$this->create_game_account($game, $playerName);

					//generate game logs for created player
					$this->generateFakeGameLogs($game, $playerName, $numberOfGameLogs);

					$token = random_string('unique');
					$dateTimeFrom = new DateTime($this->utils->getTodayForMysql());
					$dateTimeTo = new DateTime($this->utils->getTodayForMysql());
					$api->syncInfo[$token] = array("playerName" => null, "dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);

					$api->syncTotalStats($token);
					$api->syncLongTotalStats($token);

				}
			}
		}
	}

	/**
	 * overview : generate fake game logs
	 *
	 * @param $game_platform_id
	 * @param $playerName
	 * @param int $numberOfGameLogs
	 */
	public function generateFakeGameLogs($game_platform_id, $playerName, $numberOfGameLogs = 10) {
		$this->load->model(array('ebet_game_logs'));
		for ($i = 0; $i < $numberOfGameLogs; $i++) {
			$gameLogData = $this->getFakeGameLogs($game_platform_id, $playerName);
			if ($game_platform_id == EBET_API) {
				$this->ebet_game_logs->insertEbetGameLogs($gameLogData);
			}
		}
	}

	/**
	 * overview : generate fake game logs for registered player only
	 * @param int 	 $gamePlatformId
	 * @param string $playerName
	 * @param int 	 $numberOfGameLogs
	 */
	public function generateFakeGameLogsForRegisteredPlayer($gamePlatformId, $playerName, $numberOfGameLogs = 10) {
		$this->load->model(array('ebet_game_logs'));
		for ($i = 0; $i < $numberOfGameLogs; $i++) {
			$gameLogData = $this->getFakeGameLogs($gamePlatformId, $playerName);
			if ($gameLogData) {
				$this->ebet_game_logs->insertEbetGameLogs($gameLogData);
			}
		}
	}

	/**
	 * overview : get fake game logs
	 *
	 * detail : currently ebet is only added here, will add other game api later
	 *
	 * @param $game_platform_id
	 * @param $playerName
	 */
	public function getFakeGameLogs($game_platform_id, $playerName) {
		$this->load->model("game_description_model");
		$api_game_name = $this->game_description_model->getGameDescriptionListByGamePlatformId($game_platform_id, 'game_name');

		if ($api_game_name) {
			$game_api = array(EBET_API => $api_game_name);

			if (!array_key_exists($game_platform_id, $game_api)) {
				return;
			}

			$rand_game_name = array_rand($game_api[$game_platform_id], 1);
			$game_name = $game_api[$game_platform_id][$rand_game_name];
			$game_code = $this->game_description_model->getGameCodeByGameName($game_name, $game_platform_id);
			$uniqueid = uniqid();
			$result_amount = mt_rand(-50, 50);
			$bet_amount = $result_amount < 0 ? abs($result_amount) : mt_rand(10, 50);
			$now = $this->utils->getNowForMysql();
			$game_api_fields = array(
				EBET_API => array(
					'gameType' => $game_name,
					'gameshortcode' => $game_code,
					'roundNo' => random_string('numeric'),
					'payout' => $result_amount,
					'createTime' => $now,
					'payoutTime' => $now,
					'betHistoryId' => random_string('numeric'),
					'validBet' => $bet_amount,
					'userId' => $playerName,
					'username' => $playerName,
					'uniqueid' => $uniqueid,
					'external_uniqueid' => $uniqueid,
					'response_result_id' => random_string('numeric'),
				),
				PT_API => array(
					'playername' => $playerName,
					'gamename' => $playerName,
					'gameshortcode' => $game_code,
					'gamecode' => $game_code,
					'bet' => $bet_amount,
					'win' => $result_amount,
					'gamedate' => $now,
					'sessionid' => random_string('numeric'),
					'gametype' => $game_name,
					'currentbet' => $currentbet,
					'gameid' => $game_code,
					'external_uniqueid' => $uniqueid,
					'response_result_id' => random_string('numeric'),
				),
				MG_API => array(
					'row_id' => $uniqueid,
					'account_number' => $playerName,
					'display_name' => $game_name,
					'gamecode' => $game_code,
					'session_id' => $uniqueid,
					'total_payout' => $result_amount,
					'total_wager' => $bet_amount,
					'game_end_time' => $now,
					'uniqueid' => $uniqueid,
					'external_uniqueid' => $uniqueid,
					'response_result_id' => random_string('numeric'),
				),
				BBIN_API => array(
					'username' => $playerName,
					'wagers_id' => $uniqueid,
					'wagers_date' => $now,
					'game_type' => $game_code,
					'result' => $result_amount,
					'bet_amount' => $bet_amount,
					'payoff' => $result_amount,
					'external_uniqueid' => $uniqueid,
					'response_result_id' => random_string('numeric'),
				),
				LB_API => array(
					'member_id' => $playerName,
					'bet_id' => $uniqueid,
					'wagers_date' => $now,
					'match_id' => $game_code,
					'bet_money' => $bet_amount,
					'bet_winning' => $result_amount,
					'bet_winning' => $result_amount < 0 ? 'lost' : 'win',
					'bet_status' => 'settled',
					'bet_time' => $now,
					'trans_time' => $now,
					'external_uniqueid' => $uniqueid,
					'response_result_id' => random_string('numeric'),
				),
				IBC_API => array(
					'trans_id' => $uniqueid,
					'player_name' => $playerName,
					'transaction_time' => $now,
					'match_id' => $game_code,
					'league_id' => $game_code,
					'stake' => $bet_amount,
					'winlose_amount' => $result_amount,
					'external_uniqueid' => $uniqueid,
					'response_result_id' => random_string('numeric'),
				),
			);
			return $game_api_fields[$game_platform_id];
		}
	}

	/**
	 * overview : register player
	 *
	 * @param string $playerName
	 * @param string $password
	 * @param bool|false $withAffiliateFlag
	 * @param string $playerGameName
	 */
	private function registerPlayer($playerName, $password = null, $withAffiliateFlag = false, $playerGameName = null) {
		$this->load->model(array('player_model', 'affiliatemodel'));
		$this->load->library(array('salt'));
		$trackingCode = null;
		if ($withAffiliateFlag) {
			$is_unique_trackingCode = false;
			while (!$is_unique_trackingCode) {
				$trackingCode = $this->affiliatemodel->randomizer('trackingCode');
				$is_unique_trackingCode = !$this->affiliatemodel->checkTrackingCode($trackingCode);
			}

			$affiliateId = $this->affiliatemodel->addAffiliate(
				array(
					'parentId' => 0,
					'affiliatePayoutId' => 0,
					'username' => "aff" . $playerName,
					'password' => $this->salt->encrypt("aff" . $password, $this->getDeskeyOG()),
					'status' => '1',
					'createdOn' => $this->utils->getNowForMysql(),
					'trackingCode' => $trackingCode,
				)
			);
		}

		$playerData = array(
			'username' => $playerName,
			'firstName' => $playerName,
			'lastName' => $playerName,
			'gameName' => $playerGameName ? $playerGameName : $playerName,
			'password' => $password ? $password : random_string('numeric'),
			'email' => $playerName . '@test.com',
			'secretQuestion' => 'Favorite Sports Team',
			'secretAnswer' => 'Gilas Team',
			'verify' => null,
			'verified_phone' => 0,
			'language' => 'English',
			'gender' => 'Male',
			'birthdate' => '1983-06-30',
			'contactNumber' => random_string('numeric'),
			'citizenship' => 'Chinese',
			'imAccount' => random_string('numeric'),
			'imAccountType' => 'QQ',
			'imAccount2' => null,
			'imAccountType2' => null,
			'birthplace' => null,
			'registrationIp' => null,
			'registrationWebsite' => null,
			'residentCountry' => null,
			'affiliate_code' => $trackingCode,
		);
		$playerId=$this->player_model->register($playerData);

		//sync
		$this->load->model(['multiple_db_model']);
		$rlt=$this->multiple_db_model->syncPlayerFromCurrentToOtherMDB($playerId, true);
		$this->utils->debug_log('syncPlayerFromCurrentToOtherMDB', $rlt);
	}

	/**
	 * overview : create agent settlement
	 *
	 * @param string $agent_name
	 */
	public function create_agent_settlement($agent_name) {
		$this->load->library(['agency_library']);
		$this->load->model(['agency_model']);
		$agent = $this->agency_model->get_agent_by_name($agent_name);
		$msg = $this->utils->debug_log('agent settlement', $this->agency_library->create_settlement($agent['agent_id']));
		$this->returnText($msg);
	}

	/**
	 * overview : update all agents settlement
	 */
	public function update_all_agents_settlement() {
		$this->load->library(['agency_library']);
		$this->load->model(['agency_model']);
		$agents = $this->agency_model->get_all_sub_agents();
		$agentIds = array_column($agents, 'agent_id');
		foreach ($agentIds as $agentId) {
			$this->agency_library->update_settlement($agentId);
		}
		$msg = 'done';
		$this->returnText($msg);
	}

	/**
	 * overview : fill secure id on player
	 */
	public function fill_secure_id_on_player() {
		$this->load->model(['player_model']);
		$rlt = $this->player_model->batchCreateSecureId();
		$msg = $this->utils->debug_log('fill_secure_id_on_player', $rlt);
		$this->returnText($msg);
	}

	public function fix_empty_acc_name_on_pix_account($player_id_list) {
		$this->utils->debug_log('fix_empty_acc_name_on_pix_account start');
		$counts = 0;
		$not_empty_name_players = [];
		if($player_id_list == "all") {
			$not_empty_name_players = $this->player_model->getAllNotEmptyNamePlayers();
		} else {
			$ids = array_map('trim', array_filter(explode(' ', $player_id_list)));
			$not_empty_name_players = $this->player_model->getAllNotEmptyNamePlayers($ids);
		}
		$this->load->model(['player_model', 'playerbankdetails', 'users', 'player']);
		$this->utils->debug_log('not empty name players', $not_empty_name_players);
		foreach ($not_empty_name_players as $player) {
			$playerId = $player['playerId'];
			$firstName = $player['firstName'];
			$lastName = $player['lastName'];
			$fullName = trim("{$lastName} {$firstName}");
			if(!empty($fullName)){
				$playerPixAccInfo = $this->playerbankdetails->getPixAccountInfo($playerId);
				foreach($playerPixAccInfo as $transactionType => $data){
					foreach ($data as $pixType => $playerAccInfo) {
						if(!empty($playerAccInfo['bankDetailsId']) && empty($playerAccInfo['bankAccountFullName'])){
							$playerBankDetailsId = $playerAccInfo['bankDetailsId'];
							$data = [
								'bankAccountFullName' => $fullName,
								'updatedOn' =>  $this->utils->getNowForMysql(),
							];
							$updateSucc = $this->db->where('playerBankDetailsId', $playerBankDetailsId)->update('playerbankdetails', $data);
							if($updateSucc){
								$changes = array(
									'playerBankDetailsId' => $playerBankDetailsId,
									'changes' => "update pix account name, type is $pixType",
									'createdOn' => date("Y-m-d H:i:s"),
									'operator' => $this->users->getSuperAdmin()->username,
								);
								$this->player->saveBankChanges($changes);
								$counts ++;
								$this->utils->debug_log('updated playerId', $playerId, 'playerBankDetailsId', $playerBankDetailsId, 'bankAccountFullName', $fullName);
							}
						}
					}
				}
			}
		}

		$this->utils->debug_log('fix_empty_acc_name_on_pix_account total playerbankdetails updated', $counts);
	}

	/**
	 * overview : copy permission
	 *
	 * @param int $funcId
	 * @param int $replaceFuncId
	 */
	public function copy_permission($funcId, $replaceFuncId) {
		//search roles exist funcid
		$this->load->model(['roles']);
		$roles = $this->roles->getRolesByFuncId($funcId);
		$funcArr = explode(',', $replaceFuncId);
		$cnt = 0;
		if (!empty($roles) && !empty($funcArr)) {
			foreach ($roles as $roleId) {
				$sql = "SELECT roleId,funcId FROM rolefunctions where roleId = ? and funcId = ?";
				$query = $this->db->query($sql, array($roleId, $funcId));
				$result = $query->row_array();

				if (!$result) {
					foreach ($funcArr as $fId) {
						$this->db->insert('rolefunctions', array('roleId' => $roleId, 'funcId' => $fId));
					}
					$cnt++;
				}
			}
		}

		$msg = $this->utils->debug_log('copy to roles', 'count', $cnt);
		$this->returnText($msg);
	}

	/**
	 * overview : fix game provider authentication
	 */
	public function fix_game_provider_auth() {
		$this->load->model('game_provider_auth');
		$this->load->model('player_model');
		$this->load->library('game_platform/game_platform_manager');

		$cnt = array();

		$players = $this->player_model->getAllEnabledPlayers();
		$players = json_decode(json_encode($players), true);
		$players = array_column($players, 'username', 'playerId');

		foreach ($players as $playerId => $playerName) {
			$result = $this->game_platform_manager->checkIfPlayerExistOnAllPlatforms($playerName);
			foreach ($result as $game_platform_id => $game_platform_result) {
				if (isset($game_platform_result['success'], $game_platform_result['exists']) && $game_platform_result['success'] == TRUE) {
					$this->game_provider_auth->updateRegisterFlag($playerId, $game_platform_id, array(
						'register' => ($game_platform_result['exists'] == TRUE ? game_provider_auth::DB_TRUE : game_provider_auth::DB_FALSE),
					));
					$cnt[] = $playerId;
				}
			}
		}

		$cnt = count(array_unique($cnt));

		$msg = $this->utils->debug_log('fix game_provider_auth successfully', 'count', $cnt);
		$this->returnText($msg);
	}

	/**
	 * overview : sync silverpop database
	 *
	 * @param int $database_id
	 */
	function sync_silverpop_database($database_id = 5642787) {

		set_time_limit(3600);

		$msg = "";

		$empty_email = 0;
		$invalid_email = 0;
		$success = 0;
		$errors = array();

		$silverpop_options = $this->config->item('silverpop_options');

		if (empty($silverpop_options) || !isset($silverpop_options['apiHost'], $silverpop_options['username'], $silverpop_options['password'])) {
			die('Please configure silverpop first');
		}

		$this->load->model(array('player_model', 'transactions', 'game_provider_auth', 'game_logs'));
		$this->load->library('silverpop_library', $silverpop_options);
		$this->CI->load->library('player_manager');

		try {

			$login_result = $this->silverpop_library->login();

			$players = $this->player_model->getAvailablePlayers();
			$players = json_decode(json_encode($players), true);

			foreach ($players as $player) {

				$player_id = $player['playerId'];

				$playerDetails = $this->player_model->getPlayerAccountInfo($player_id);

				if (empty($player['email'])) {
					$empty_email++;
					continue;
				}

				if (filter_var($player['email'], FILTER_VALIDATE_EMAIL) === false) {
					$invalid_email++;
					continue;
				}

				$player = array_merge($player, $playerDetails);

				$first_last_deposit_date = $this->player_manager->getPlayerFirstLastApprovedTransaction($player_id, Transactions::DEPOSIT);
				$player['first_deposit_date'] = $first_last_deposit_date['first'];
				$player['last_deposit_date'] = $first_last_deposit_date['last'];

				$player['totalDepositAmount'] = $this->transactions->getPlayerTotalDeposits($player_id);

				$player['approved_deposit_count'] = $this->transactions->getTransactionCount(array(
					'to_id' => $player_id,
					'to_type' => Transactions::PLAYER,
					'transaction_type' => Transactions::DEPOSIT,
					'status' => Transactions::APPROVED,
				));

				$player['approvedWithdrawAmount'] = $this->transactions->getTransactionCount(array(
					'to_id' => $player_id,
					'to_type' => Transactions::PLAYER,
					'transaction_type' => Transactions::WITHDRAWAL,
					'status' => Transactions::APPROVED,
				));

				$game_platforms = $this->game_provider_auth->getGamePlatforms($player_id);
				$game_logs = $this->game_logs->getSummary($player_id);
				$last_activity_date = $this->game_logs->get_last_activity_date($player_id);

				foreach ($game_platforms as $game_platform) {

					$game_platform_id = $game_platform['id'];
					$system_code = $game_platform['system_code'];

					if (isset($game_logs[$game_platform_id])) {
						$player[$system_code . ' Turnover'] 			= $game_logs[$game_platform_id]['bet']['sum'];
						$player[$system_code . ' GGR'] 					= $game_logs[$game_platform_id]['gain_loss']['sum'];
						$player[$system_code . ' NGR'] 					= $game_logs[$game_platform_id]['gain_loss']['sum'];
					}

					if (isset($last_activity_date[$game_platform_id])) {
						$player[$system_code . ' Last Activity Date'] 	= $last_activity_date[$game_platform_id];
					}

				}

				try {

					$addRecipient_result = $this->silverpop_library->addRecipient($database_id, $player);
					$success++;

				} catch (Exception $e) {

					$this->utils->debug_log(array(
						'getLastRequest' => $this->silverpop_library->getLastRequest(),
						'getLastResponse' => $this->silverpop_library->getLastResponse(),
						'getLastFault' => $this->silverpop_library->getLastFault(),
					));

					@$errors[$e->getMessage()]++;

				}

			}

			$logout_result = $this->silverpop_library->logout();

			$data['available_players'] = count($players);
			$data['success'] = $success;
			$data['errors'] = $errors;
			$data['errors']['empty_email'] = $empty_email;
			$data['errors']['invalid_email'] = $invalid_email;

			$msg = print_r($data, true);

		} catch (Exception $e) {

			$msg = $this->utils->debug_log(array(
				'getLastRequest' => $this->silverpop_library->getLastRequest(),
				'getLastResponse' => $this->silverpop_library->getLastResponse(),
				'getLastFault' => $this->silverpop_library->getLastFault(),
			));

		}

		$this->returnText($msg);
	}

	/**
	 * overview : random date
	 *
	 * @param string $start
	 * @param string $end
	 * @return bool|string
	 */
	function random_date($start, $end = null) {
		$start = strtotime($start);
		$end = $end ? strtotime($end) : time();
		$timestamp = mt_rand($start, $end);
		return date("Y-m-d H:i:s", $timestamp);
	}

	/**
	 * overview : sync silverpop database dummy
	 *
	 * @param int $database_id
	 */
	function sync_silverpop_database_dummy($database_id = 5642787) {
		header('Content-Type: text/plain');
		set_time_limit(3600);

		$msg = "";

		$empty_email = 0;
		$invalid_email = 0;
		$success = 0;
		$errors = array();

		$silverpop_options = $this->config->item('silverpop_options');

		if (empty($silverpop_options) || !isset($silverpop_options['apiHost'], $silverpop_options['username'], $silverpop_options['password'])) {
			die('Please configure silverpop first');
		}

		$this->load->model(array('player_model', 'transactions', 'game_provider_auth', 'game_logs'));
		$this->load->library('silverpop_library', $silverpop_options);
		$this->CI->load->library('player_manager');

		$genders = array('Male', 'Female');
		$languages = array('English', 'Chinese');

		try {

			$login_result = $this->silverpop_library->login();

			$players = $this->player_model->getAvailablePlayers();
			$players = json_decode(json_encode($players), true);

			foreach ($players as $player) {

				$player_id = $player['playerId'];

				$playerDetails = $this->player_model->getPlayerAccountInfo($player_id);

				if (empty($player['email'])) {
					$empty_email++;
					continue;
				}

				if (filter_var($player['email'], FILTER_VALIDATE_EMAIL) === false) {
					$invalid_email++;
					continue;
				}

				$player = array_merge($player, $playerDetails);
				$player['firstName'] 				= $player['firstName'] ? : random_string('alnum', 12);
				$player['lastName'] 				= $player['lastName'] ? : random_string('alnum', 12);

				$player['gender'] 					= $player['gender'] ? : $genders[array_rand($genders)];
				$player['language'] 				= $player['language'] ? : $languages[array_rand($languages)];

				$player['city'] 					= $player['city'] ? : random_string('alnum', 12);
				$player['residentCountry'] 			= $player['residentCountry'] ? : random_string('alnum', 12);
				$player['birthdate'] 				= $this->random_date('1980-01-01 00:00:00');
				$player['contactNumber'] 			= random_string('numeric', 11);
				$player['imAccount'] 				= random_string('alnum', 12);

				$player['lastLoginTime'] 			= $this->random_date($player['createdOn']);
				$player['first_deposit_date'] 		= $this->random_date($player['createdOn']);
				$player['last_deposit_date'] 		= $this->random_date($player['first_deposit_date']);
				$player['total_total'] 				= intval(random_string('numeric', 9));
				$player['totalDepositAmount'] 		= intval(random_string('numeric', 9));
				$player['approved_deposit_count'] 	= intval(random_string('numeric', 3));
				$player['approvedWithdrawAmount'] 	= intval(random_string('numeric', 3));

				$game_platforms = $this->game_provider_auth->getGamePlatforms($player_id);
				foreach ($game_platforms as $game_platform) {

					$system_code = $game_platform['system_code'];

					$player[$system_code . ' Turnover'] 			= intval(random_string('numeric', 9));
					$player[$system_code . ' GGR'] 					= intval(random_string('numeric', 9));
					$player[$system_code . ' Bonus']				= 0;
					$player[$system_code . ' NGR'] 					= intval(random_string('numeric', 9));
					$player[$system_code . ' Last Activity Date'] 	= $this->random_date($player['first_deposit_date']);

				}

				try {

					$addRecipient_result = $this->silverpop_library->addRecipient($database_id, $player);
					$success++;

				} catch (Exception $e) {

					$this->utils->debug_log(array(
						'getLastRequest' => $this->silverpop_library->getLastRequest(),
						'getLastResponse' => $this->silverpop_library->getLastResponse(),
						'getLastFault' => $this->silverpop_library->getLastFault(),
					));

					@$errors[$e->getMessage()]++;

				}

			}

			$logout_result = $this->silverpop_library->logout();

			$data['available_players'] = count($players);
			$data['success'] = $success;
			$data['errors'] = $errors;
			$data['errors']['empty_email'] = $empty_email;
			$data['errors']['invalid_email'] = $invalid_email;

			$msg = print_r($data, true);

		} catch (Exception $e) {

			$msg = $this->utils->debug_log(array(
				'getLastRequest' => $this->silverpop_library->getLastRequest(),
				'getLastResponse' => $this->silverpop_library->getLastResponse(),
				'getLastFault' => $this->silverpop_library->getLastFault(),
			));

		}

		$this->returnText($msg);
	}

	/**
	 * overview :get all approve manual request without bonus
	 *
	 * detail : will get all manual request without bonus release then will approve to release
	 *
	 * @throws WrongBonusException
	 *
	 * Not used at 19-07-30.("getAllApprovedManualRequestWithoutBonusRelease" only defined at function name)
	 */
	public function getAllApprovedManualRequestWithoutBonusRelease() {
		$this->load->model('player_promo');
		$data = $this->player_promo->getAllPromoApplication(Player_promo::TRANS_STATUS_MANUAL_REQUEST_APPROVED_WITHOUT_RELEASE_BONUS);
		foreach ($data as $key) {
			$this->approvePromo($key['playerId'], $key['playerId'], $key['promoCmsSettingId'], $key['playerpromoId']);
		}
	} // EOF getAllApprovedManualRequestWithoutBonusRelease

	/**
	 * overview : aprove promo
	 * @param int $playerId
	 * @param int $promorulesId
	 * @param int $promoCmsSettingId
	 * @param int $playerPromoId
	 * @param int $adminId
	 * @param int $depositAmount
	 * @param int $tranId
	 * @throws WrongBonusException
	 */
	public function approvePromo($playerId, $promorulesId, $promoCmsSettingId, $playerPromoId, $adminId = 1,
		$depositAmount = null, $tranId = null) {
		$this->load->model(array('promorules', 'wallet_model', 'transactions', 'player_promo', 'sale_order', 'withdraw_condition'));

		$promorule = $this->promorules->getPromoRules($promorulesId);

		$playerBonusAmount = 0;
		if (!empty($playerPromoId)) {
			$playerPromo = $this->player_promo->getPlayerPromo($playerPromoId);
			if (!empty($playerPromo)) {
				$playerBonusAmount = $playerPromo->bonusAmount;
				$withdrawBetAmtCondition = $playerPromo->withdrawConditionAmount;
			} else {
				$playerPromoId = null;
			}
		}

		if ($playerBonusAmount <= 0 || empty($playerBonusAmount)) {
			throw new WrongBonusException('Wrong bonus amount', 'wrong bonus amount:' . $playerBonusAmount);
		}

		$playerPromoId = $this->player_promo->approvePromoToPlayer($playerId, $promorulesId,
			$playerBonusAmount, $promoCmsSettingId, $adminId, $playerPromoId);

		$currentMainwalletBal = $this->wallet_model->getMainWalletBalance($playerId);
		$totalBeforeBalance = $this->wallet_model->getTotalBalance($playerId);

		$bonusTransId = $this->transactions->createBonusTransaction($adminId, $playerId, $playerBonusAmount,
			$currentMainwalletBal, $playerPromoId, $tranId, Transactions::PROGRAM, $totalBeforeBalance,
			Transactions::ADD_BONUS, null, @$promorule['promoCategory']);

		$bet_times = 0;
		if ($promorule['withdrawRequirementConditionType'] == Promorules::WITHDRAW_CONDITION_TYPE_BETTING_TIMES) {
			$bet_times = $promorule['withdrawRequirementBetCntCondition'];
		}

		$isDepositPromoFlag = $promorule['promoType'] == Promorules::PROMO_TYPE_DEPOSIT ? true : false;
		$this->withdraw_condition->createWithdrawConditionForPromoruleBonus($isDepositPromoFlag // #1
									, $playerId // #2
									, $bonusTransId // #3
									, $withdrawBetAmtCondition // #4
									, $depositAmount // #5
									, $playerBonusAmount // #6
									, $bet_times // #7
									, $promorulesId  // #8
								);
        $this->updatePromoId($promorule, $tranId, $playerPromoId, []);
	}

	/**
	 * overview : move big wallet
	 *
	 * @param int $playerId
	 * @param $from
	 * @param $from_type
	 * @param $to
	 * @param $to_type
	 * @param $subwallet_id
	 * @param $amount
	 */
	public function move_big_wallet($playerId, $from, $from_type, $to, $to_type, $subwallet_id, $amount){
		$this->load->model(['wallet_model']);

		$this->startTrans();

		$beforeBigWallet=$this->wallet_model->getBigWalletByPlayerId($playerId);

		$success=$this->wallet_model->moveAnyBigWallet($playerId, $from, $from_type, $to, $to_type, $subwallet_id, $amount);

		$afterBigWallet=$this->wallet_model->getBigWalletByPlayerId($playerId);

		$msg=$this->utils->debug_log('beforeBigWallet', $beforeBigWallet, 'afterBigWallet', $afterBigWallet);
		$this->returnText($msg);

		if ($this->endTransWithSucc()) {
			$msg = $this->utils->debug_log('move_big_wallet is success');
			$this->returnText($msg);
		} else {
			$msg = $this->utils->debug_log('move_big_wallet is failed', $success);
			$this->returnText($msg);
		}

		// $this->returnText($success);
	}

	/**
	 * overview : convert transactions sub wallet
	 */
	public function convert_transactions_subwallet_id(){

		$this->load->model(['transactions']);

		$success=$this->transactions->convertSubwalletId();

		$this->returnText('convert_transactions_subwallet_id success: '.$success);
	}

	/**
	 * overview : set subwallet
	 *
	 * @param int $playerId
	 * @param int $subWalletId
	 * @param int $balance
	 */
	public function set_subwallet($playerId, $subWalletId, $balance){
		$this->load->model(['wallet_model']);

		$success=$this->wallet_model->refreshSubWalletOnBigWallet($playerId, $subWalletId, $balance);

		$msg=$this->utils->debug_log('set_subwallet', $playerId, $subWalletId, 'to', $balance);

		$this->returnText($msg);
	}

	/**
	 * overview : import win007 players
	 *
	 * @param $filename
	 */
    public function import_win007_players($filename){
        $file = fopen($filename, "r");
        $this->load->model(['player_model','wallet_model']);
        while (!feof($file)) {
            $tmpData = fgetcsv($file);
            if(empty($tmpData)) continue;
            $player['username'] = strval($tmpData[0]);
            $player['password'] = strval($tmpData[1]);
            $player_id = $this->player_model->import_win007_players($player);
            if($player_id)
            {
                $this->wallet_model->import_win007_players($player_id);
                //$wallet = $this->wallet_model->getMainWalletBy($player_id);
                //if(!$wallet){
                //    $this->wallet_model->updateMainWallet($player_id, 0);
                //}
                echo 'id',$player_id,' name:', $player['username'] ,' password:',$player['password'],
                ' balance:',($wallet)?$wallet['totalBalanceAmount']:'0',PHP_EOL;
            }

        }
        fclose($file);

    }

	/**
	 * overview : generate admin dashboard
	 */
    public function generate_admin_dashboard(){
    $this->load->model(['transactions']);

        if ($this->utils->isEnabledMDB()) {
            $result=$this->transactions->foreachMultipleDB(function ($db, &$rlt) {
                return $this->transactions->syncDashboard();
            });
        } else {
            $result=$this->transactions->syncDashboard();
        }

        $msg = $this->utils->debug_log('generate_admin_dashboard: ', $result);

        $this->returnText($msg);

	}

	/**
	 * overview : generate_top_bet_players
	 */
    public function generate_top_bet_players_list($settlement_date=null, $limit = 20){
		$this->load->model(['transactions']);
		$dates = [];
		if(!empty($settlement_date)){
			$dates['date_base'] = $settlement_date;
		}
    	$success=$this->transactions->generateTopBetPlayersList($dates, $limit);

    	$msg=$this->utils->debug_log('generate_top_bet_players: '.$success);

    	$this->returnText($msg);
    }


	/**
	 * overview : generate affiliate monthly earnings
	 *
	 * @param string $yearmonth	format:YYYYMM
	 */
	public function generate_aff_monthly_earnings($yearmonth = null) {

		// $this->utils->debug_log('sleeping...');
		// sleep(10);
		// $this->utils->debug_log('sleep 10');

		$this->load->model('affiliate_earnings');

		$rlt = false;
		if (!empty($yearmonth)) {
			$rlt = $this->affiliate_earnings->generate_monthly_earnings($yearmonth);
		} else if ($this->affiliate_earnings->todayIsPayday()) {
			//generate last month
			$rlt = $this->affiliate_earnings->generate_monthly_earnings();
		}

		$this->returnText('result:' . $rlt . "\n");
	}

	/**
	 * overview : check agency
	 */
	public function check_agency(){
		$this->load->model(['agency_model', 'transactions']);
		$this->db->from('agency_agents');

		$rows=$this->transactions->runMultipleRowArray();
		foreach ($rows as $row) {
			$sql=<<<EOD
select sum(case when (transaction_type in (24,29)) or (transaction_type=26 and to_id=?) or (transaction_type=27 and to_id=?) then amount
when (transaction_type in (25,28)) or (transaction_type=26 and from_id=?) or (transaction_type=27 and from_id=?) then -amount else 0
end ) as trans_amount
from transactions
where transaction_type in (24,25,26,27,28,29)
and ((to_id=? and to_type=4) or (from_id=? and from_type=4) )

EOD;

			$agent_id=$row['agent_id'];
			$rowsTrans=$this->transactions->runRawSelectSQLArray($sql, [$agent_id, $agent_id, $agent_id, $agent_id, $agent_id, $agent_id]);

			if(!empty($rowsTrans)){
				$rowTrans=$rowsTrans[0];
				$calc_amount=$rowTrans['trans_amount'];
				if($calc_amount!==null || $row['available_credit']>0){
					if($calc_amount!=$row['available_credit']){
						$this->utils->debug_log($row['agent_id'].' '.$row['agent_name'].' is wrong',
							$calc_amount, $row['available_credit'], $row['available_credit'] - $calc_amount , $row['credit_limit']);
					}
				}
			}
		}
	}

	/**
	 * overview : check rename agency
	 */
	public function check_agency_rename(){
		$this->load->model(['agency_model', 'transactions']);

		$sql=<<<EOD
select * from transactions where to_type=4 or from_type=4
EOD;

		$rowsTrans=$this->transactions->runRawSelectSQLArray($sql);

		if(!empty($rowsTrans)){
			$transIdArr=[];
			foreach ($rowsTrans as $rowTrans) {
				if($rowTrans['to_type']=='4'){
					$to_id=$rowTrans['to_id'];
					$this->db->select('agent_name')->from('agency_agents')->where('agent_id', $to_id);
					$agent_name=$this->transactions->runOneRowOneField('agent_name');
					$to_username=$rowTrans['to_username'];
					if($agent_name!=$to_username){
						$this->utils->debug_log('transaction:'.$rowTrans['id'].' type:'.$rowTrans['transaction_type'].' , agent name:'.$agent_name.' to username:'.$to_username, $rowTrans['note']);

						//update
						$this->db->where('id', $rowTrans['id'])->set('to_username', $agent_name);
						if($rowTrans['transaction_type']=='26' || $rowTrans['transaction_type']=='1'){
							$this->db->set('note', str_replace($to_username, $agent_name, $rowTrans['note']));
						}
						$this->transactions->runAnyUpdate('transactions');
						$this->utils->debug_log('update id '.$rowTrans['id'].' to_username='.$agent_name.' note='.str_replace($to_username, $agent_name, $rowTrans['note']));
						$transIdArr[]=$rowTrans['id'];
					}
				}

				if($rowTrans['from_type']=='4'){
					$from_id=$rowTrans['from_id'];
					$this->db->select('agent_name')->from('agency_agents')->where('agent_id', $from_id);
					$agent_name=$this->transactions->runOneRowOneField('agent_name');
					$from_username=$rowTrans['from_username'];
					if($agent_name!=$from_username){
						$this->utils->debug_log('transaction:'.$rowTrans['id'].' type:'.$rowTrans['transaction_type'].' , agent name:'.$agent_name.' from_username:'.$from_username, $rowTrans['note']);

						$this->db->where('id', $rowTrans['id'])->set('from_username', $agent_name);
						if($rowTrans['transaction_type']=='27' || $rowTrans['transaction_type']=='2'){
						  	$this->db->set('note', str_replace($from_username, $agent_name, $rowTrans['note']));
						}
						$this->transactions->runAnyUpdate('transactions');
						$this->utils->debug_log('update id '.$rowTrans['id'].' from_username='.$agent_name.' note='.str_replace($from_username, $agent_name, $rowTrans['note']));

						$transIdArr[]=$rowTrans['id'];
					}
				}


			}

			$this->utils->debug_log('transIdArr', $transIdArr);
		}


	}

	/**
	 * overview : convert old wallet to big
	 */
	public function convert_old_wallet_to_big(){
		$this->load->model(['player_model', 'wallet_model']);

		$rows=$this->player_model->getAllImportPlayers();
		foreach ($rows as $row) {
			$playerId=$row->playerId;
			$rlt=$this->wallet_model->convertOldWalletToBigWallet($playerId);
			$this->utils->debug_log('convert '.$playerId.' to big wallet, result', !!$rlt);
		}
	}

	/**
	 * @deprecated
	 */
	public function scan_balance(){
		//transfer between sub-main, but total balance is changed
		//order by player id and date time
	}

	/**
	 * scan affiliate
	 * @param  string $yearmonth format YYYYmm
	 * @return int 1 or 0
	 */
	public function scan_affiliate($yearmonth){

		$this->load->model(['affiliatemodel', 'game_logs', 'transactions']);

		list($start, $end)=$this->utils->getStartEndDateTime($yearmonth);
		$startDateTime= $this->utils->formatDateTimeForMysql($start);
		$endDateTime= $this->utils->formatDateTimeForMysql($end);

		$affRows=$this->affiliatemodel->getAllAffiliates();
		$this->utils->debug_log('scan by', $yearmonth, 'start', $startDateTime, 'end', $endDateTime);

		foreach ($affRows as $row) {
			$playerIds=$this->affiliatemodel->getAllPlayerIdByAffiliateId($row['affiliateId']);

			list($totalBet, $totalWin, $totalLoss)=$this->game_logs->getTotalBetsWinsLossByPlayersForce(
				$playerIds, $startDateTime, $endDateTime);

			if(count($playerIds)>0){

				$this->utils->debug_log('affiliate', $row['affiliateId'], $row['username'], 'playerIds', count($playerIds),
					'totalBet:'.$totalBet.', totalWin:'.$totalWin.', totalLoss:'.$totalLoss);
			}
		}

		return true;
	}

	public function resetbalance($username, $platformId=null){}

	public function updateDupReport() {
        $this->utils->debug_log("==================" . __FUNCTION__ . '================== start');
		$this->load->model(['duplicate_account_setting', 'duplicate_account_info', 'player']);
		$this->load->library('duplicate_account');

        $this->duplicate_account_info->clearDuplicateRelationTable();

        $this->utils->debug_log("==================" . __FUNCTION__ . '================== table clear');

        $this->utils->debug_log("==================" . __FUNCTION__ . '================== dup calc start');

		$result_insert_arr = [];
		$result_insert_arr = $this->duplicate_account_info->generateDupIp($result_insert_arr);
		$result_insert_arr = $this->duplicate_account_info->generateDupRealName($result_insert_arr);
		$result_insert_arr = $this->duplicate_account_info->generateDupPassword($result_insert_arr);
		$result_insert_arr = $this->duplicate_account_info->generateDupEmail($result_insert_arr);
		$result_insert_arr = $this->duplicate_account_info->generateDupMobile($result_insert_arr);
		$result_insert_arr = $this->duplicate_account_info->generateDupAddress($result_insert_arr);
		$result_insert_arr = $this->duplicate_account_info->generateDupCountry($result_insert_arr);
		$result_insert_arr = $this->duplicate_account_info->generateDupCity($result_insert_arr);
		$result_insert_arr = $this->duplicate_account_info->generateDupCookie($result_insert_arr);
		$result_insert_arr = $this->duplicate_account_info->generateDupReferrer($result_insert_arr);
		$result_insert_arr = $this->duplicate_account_info->generateDupDevice($result_insert_arr);

        $this->utils->debug_log("==================" . __FUNCTION__ . '================== dup calc end');

		$this->duplicate_account_info->createDupReport($result_insert_arr);

        $this->utils->debug_log("==================" . __FUNCTION__ . '================== end');
	}

	public function remove_dup_playeraccount($max_count=20){
		$this->load->model(['player_model']);
		//all player get from big wallet, reset playeraccount, should lock balance
		$this->db->from('delete_playeraccount');
		$rows=$this->player_model->runMultipleRowArray();
		$cnt=0;
		foreach ($rows as $row) {
			$playerAccountId=$row['playerAccountId'];
			$this->db->delete('playeraccount', ['playerAccountId'=>$playerAccountId]);
			if($cnt > $max_count){
				$this->utils->debug_log('stopped on', $max_count);
				break;
			}
			$cnt++;
			if($cnt % 1000 == 0 ){
				$this->utils->debug_log('deleted',$cnt);
			}
		}

		$this->utils->debug_log('all deleted',$cnt);
	}

	public function encrypt_admin_password($password){
		$this->generate_password($password);
	}

	public function generate_http_request_summary(){

		// $this->load->model(array('http_request', 'http_request_summary'));

		//load last id from operator_settings
		// $record = $this->http_request->get_summary_from_last_id($last_id);

		// $inserted = $this->http_request_summary->insertBatch( $record );

		// $msg = $this->utils->debug_log(count($record) . " http request row(s) has been sync");

		// $this->returnText($msg);
	}

	public function get_ip_city($ip){
		$this->utils->debug_log($this->utils->getIpCityAndCountry($ip));
	}

	public function generate_player_without_referral_code_with_time_limit($dateTimeFromStr = null, $dateTimeToStr = null){


		$this->load->model(['player_model']);
		$addedRefCodeCount = 0;
        $playerNotAddedRefCode = array();
        $playerIdsNoRefCode = $this->player_model->getPlayerWithoutRefCode($dateTimeFromStr, $dateTimeToStr);
        $cnt = count($playerIdsNoRefCode);

        if($dateTimeFromStr != null && $dateTimeToStr != null){
        	if(!empty($playerIdsNoRefCode)){
        		foreach ($playerIdsNoRefCode as $playerId) {
        			$referralCode = $this->player_model->generateReferralCode();
        			if($this->player_model->addReferralCodeToPlayer($playerId,$referralCode)){
        				$addedRefCodeCount++;
        			}else{
        				array_push($playerNotAddedRefCode, $playerId);
        			}
        		}
        	}
		}


     	$this->utils->debug_log('TOTAL PLAYER WITHOUT REFERRAL CODE', $cnt ,
     	 'TOTAL PLAYER ADDED REFERRAL CODE', $addedRefCodeCount, 'PLAYER IDS', $playerIdsNoRefCode,'FAILED PLAYER IDS', $playerNotAddedRefCode);
	}

	public function transfer_all_players_subwallet_to_main_wallet($customApi=null, $max_bal=1, $min_bal=0){
		set_time_limit(0);

		$this->load->model(['wallet_model', 'player_model']);
		#get all players in game_provider_auth is registered
		#need field:type_of_player = "real" type= subwallet subwallets = [{gameplatform  }]
		$players = $this->wallet_model->getMaxBalancePlayerList($customApi, $max_bal, $min_bal);

		#load apis
		$apis = $this->utils->getAllCurrentGameSystemList();

		// array_walk($players, array($this, 'transfer_subwallet_to_main_wallet'));
		$apiArr=[];
		if(empty($customApi)){
			foreach ($apis as $game_platform_id) {
				$api = $this->utils->loadExternalSystemLibObject($game_platform_id);
				$apiArr[]=$api;
			}
		}else{
			$api = $this->utils->loadExternalSystemLibObject($customApi);
			$apiArr[]=$api;
		}

		$cnt=0;
		if(!empty($players)){
			foreach ($players as $player) {
				$cnt++;
				$this->utils->debug_log('=========process player:'.$player['username'].' total:'.count($players).' current:'.$cnt);
				$this->transfer_subwallet_to_main_wallet($player, $apiArr, $max_bal);
			}
		}
		return $cnt;
	}

	public function transfer_all_players_subwallet_to_main_wallet_for_blocked_api($customApi=null, $max_bal=1, $min_bal=0){
		set_time_limit(0);

		$this->load->model(['wallet_model', 'player_model']);

		if(is_null($customApi)){
			$this->utils->error_log('API ID is required');
			return false;
		}

		$players = $this->wallet_model->getMaxBalancePlayerList($customApi, $max_bal, $min_bal);

		if(empty($players)){
			$this->utils->info_log('No players result');
			return false;
		}

		$cnt=0;
		if(!empty($players)){
			foreach ($players as $player) {
				$playerId = isset($player['playerId']) ? intval($player['playerId']) : null;
				$api = intval($customApi);

				$self = $this;
				$isDecremented = false;
				$isIncremented = false;


				$this->wallet_model->lockAndTransForPlayerBalance($playerId,function() use($self,$playerId,$api,$player,&$isDecremented,&$isIncremented,&$cnt){

					$bigWallet = $this->wallet_model->getBigWalletInclBlockedGameByPlayerId($playerId, $api);

					$balance = doubleval($bigWallet['sub'][$api]['real']);
					$theResults = false;

					if($balance > 0){
						# decrement sub wallet
						$isDecremented = $self->wallet_model->decBlockedSubWallet($playerId,$api,$balance);
						# increment main wallet
						$isIncremented = $self->wallet_model->incMainDepositOnBigWalletWithBlockedSub($playerId,$balance,$api);
						$theResults = ($isDecremented && $isIncremented);

						if($theResults){
							$self->wallet_model->refreshSubWalletOnBigWalletIncludingBlockedApi($playerId,$api,$balance);
							$cnt++;
							$self->utils->info_log('refresh subwallet of player with ID of: >>>>>>>>', $playerId,'sub wallet ID:', $api,'balance:', $balance);
						}else{
							$self->utils->error_log(__METHOD__.' ERROR transferring balance to main wallet: ',$api,'playerId',$playerId,'balance',$balance,'isDecremented',$isDecremented,'isIncremented',$isIncremented);
						}

						return $theResults;
					}else{
						$this->utils->info_log("ZERO BALANCE: ",$balance,'player ID',$playerId);
					}

					return $theResults;
				});

					$this->utils->info_log('=========process player:'.$player['username'].' total:'.count($players).' current:'.$cnt);

			}
		}
	}

	protected function transfer_subwallet_to_main_wallet($player, $apiArr, $max_bal) {
		$this->load->model(['external_system','wallet_model']);
		$playerId = $player['playerId'];
		$playerName = $player['username'];
		foreach ($apiArr as $api) {
			$game_platform_id=$api->getPlatformCode();

			if ($api) {
				$isPlayerExist = $api->isPlayerExist($playerName);
				if ($isPlayerExist) {
					if($max_bal<=1){
						if($api->onlyTransferPositiveInteger()){
							//ignore positive integer only
							continue;
						}
					}

					$result = $api->queryPlayerBalance($playerName);
					if (isset($result['success']) && $result['success'] && isset($result['balance'])) {
						$balance = $result['balance'];

						//update with lock
						$api->updatePlayerSubwalletBalance($playerId, $balance);
						if($balance>0){
							//only transfer balance >0

							$result = $this->utils->transferWallet($playerId, $playerName, $game_platform_id, Wallet_model::MAIN_WALLET_ID, $balance);
							if (isset($result['success']) && $result['success']) {
								$this->utils->debug_log('transfer '.$playerName.' from '.$api->getPlatformCode().' balance:'.$balance.' success');
							} else {
								$this->utils->error_log('transfer '.$playerName.' from '.$api->getPlatformCode().' balance:'.$balance.' failed');
							}
						}
					}else{
						$this->utils->debug_log('query balance failed');
					}
				}
			}else{
				$this->utils->error_log('wrong api');
			}
		}
	}

	public function batch_create_bbin_mobile_account(){

		$this->load->model(['player_model']);
		$gamePlatformId = BBIN_API;

		$players = $this->player_model->getAllPlayersByGamePlatform($gamePlatformId);

		foreach ($players as $player) {
			$this->create_bbin_mobile_account($player['username'], $player['player_id'], $gamePlatformId);
		}
	}

	public function create_bbin_mobile_account($playerName, $playerId, $game_platform_id){
		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);
		$password = $api->getPassword($playerName)['password'];
		$rlt = $api->createMobilePlayer($playerName, $playerId, $password, null);
		$this->utils->debug_log('== Create BBIN Mobile Account ==', $rlt, 'playerName', $playerName , 'playerId', $playerId, 'game_platform_id', $game_platform_id);
	}

	public function convert_to_new_cashback_percentage(){

		$this->load->model(['group_level']);

		$this->group_level->convertToNewCashbackPercentage();
	}

    public function syncAllFeatures(){
    	$this->load->model('system_feature');

		$this->system_feature->syncAllFeatures();

    	$this->utils->debug_log('==== System Features has been already Sync ====');
    }

    public function convert_withdrawal_password(){
    	$this->load->model('player_model');

    	$this->db->select('withdraw_password, playerId, username')->from('player')->where('withdraw_password is not null', null, false)
    		->where('length(withdraw_password)>11');

    	$players=$this->player_model->runMultipleRowArray();

    	if(!empty($players)){

	    	foreach ($players as $player) {

	    		$withdraw_password=$player['withdraw_password'];
	    		$new_pass=$this->utils->decodePassword($withdraw_password);
	    		if(!empty($new_pass)){
		    		$this->db->set('withdraw_password', $new_pass)->where('playerId', $player['playerId']);
		    		$this->player_model->runAnyUpdate('player');
		    		$this->utils->debug_log('update new pass ', $new_pass, 'playerId', $player['playerId']);
	    		}else{
	    			$this->utils->debug_log('new pass is empty, old password', $withdraw_password, 'playerId', $player['playerId']);
	    		}

	    	}
    	}
    }

    public function try_payment_callback($resp_file, $pay_domain=null){

		// {
		// "callbackExtraInfo":{"type":"addTransfer","pay_time":"20170127191611","bank_id":"1","amount":"3.50","mownecum_order_num":"LY20170127028250712153","company_order_num":"D612418904201","pay_card_num":"6222021702017087438","pay_card_name":"\u6c5f\u6d9b","channel":"\u7f51\u4e0a\u94f6\u884c","area":"\u90d1\u5dde","fee":"0.00","transaction_charge":"0.01","key":"c5eb7d4a6aa8b9f79ed4fa2ff7003dfc","deposit_mode":"1","base_info":"","operating_time":"20170127194646"},
		// "_REQUEST":{"type":"addTransfer","pay_time":"20170127191611","bank_id":"1","amount":"3.50","mownecum_order_num":"LY20170127028250712153","company_order_num":"D612418904201","pay_card_num":"6222021702017087438","pay_card_name":"\u6c5f\u6d9b","channel":"\u7f51\u4e0a\u94f6\u884c","area":"\u90d1\u5dde","fee":"0.00","transaction_charge":"0.01","key":"c5eb7d4a6aa8b9f79ed4fa2ff7003dfc","deposit_mode":"1","base_info":"","operating_time":"20170127194646"},
		// "_SERVER":{"USER":"www-data","HOME":"\/var\/www","FCGI_ROLE":"RESPONDER","QUERY_STRING":"type=addTransfer","REQUEST_METHOD":"POST","CONTENT_TYPE":"application\/x-www-form-urlencoded","CONTENT_LENGTH":"332","SCRIPT_NAME":"\/index.php","REQUEST_URI":"\/callback\/fixed_process\/114?type=addTransfer","DOCUMENT_URI":"\/index.php","DOCUMENT_ROOT":"\/home\/vagrant\/Code\/og\/player\/public","SERVER_PROTOCOL":"HTTP\/1.0","REQUEST_SCHEME":"http","GATEWAY_INTERFACE":"CGI\/1.1","SERVER_SOFTWARE":"nginx\/1.10.1","REMOTE_ADDR":"192.168.3.2","REMOTE_PORT":"37102","SERVER_ADDR":"192.168.3.1","SERVER_PORT":"80","SERVER_NAME":"~^player\\.(?<maindomain>(?!staging\\.).*)$","REDIRECT_STATUS":"200","SCRIPT_FILENAME":"\/home\/vagrant\/Code\/og\/player\/public\/index.php","HTTP_HOST":"player.le8858.com","HTTP_X_REAL_IP":"23.99.100.108","HTTP_X_FORWARDED_FOR":"23.99.100.108","HTTP_CONNECTION":"close","HTTP_CONTENT_LENGTH":"332","HTTP_CONTENT_TYPE":"application\/x-www-form-urlencoded","PHP_SELF":"\/index.php","REQUEST_TIME_FLOAT":1485517607.9317,"REQUEST_TIME":1485517607}
		// }

		$json=file_get_contents('/tmp/'.$resp_file);
		if(!empty($json)){

			$obj=$this->utils->decodeJson($json);
			$callbackExtraInfo=$obj['callbackExtraInfo'];
			$server_info=$obj['_SERVER'];
			if(empty($pay_domain)){
				$pay_domain=$server_info['HTTP_HOST'];
			}
			//call
			$url='http://'.$pay_domain.'/'.$server_info['REQUEST_URI'];
			//remove QUERY_STRING
			if(!empty($server_info['QUERY_STRING'])){
				$url=str_replace($server_info['QUERY_STRING'],'',$url);
			}
			$method=isset($server_info['REQUEST_METHOD']) ? $server_info['REQUEST_METHOD'] : 'GET';
			$params=$callbackExtraInfo;
			$this->utils->debug_log('url', $url, 'method', $method, 'params', $params);
			list($header, $resultText, $statusCode, $statusText, $errCode, $error, $resultObj)=
				$this->utils->callHttp($url, $method, $params);

			$this->utils->debug_log('result',$header, $resultText, $statusCode, $statusText, $errCode, $error, $resultObj);
		}else{
			$this->utils->debug_log('empty response file', $resp_file);
		}

    	// $this->load->model(['response_result']);
    	// $resp=$this->response_result->getResponseResultInfoById($response_result_id);

    	// if(!empty($resp)){
	    	//load response from file, try callback again
    	// 	$filepath=$resp['filepath'];
    	// 	$arr=explode('/', $filepath);
    	// 	if(isset($arr[2])){
    	// 		$apiId=$arr[2];
    	// 		$api=$this->utils->loadExternalSystemLibObject($apiId);
    	// 		if(!empty($api)){

    	// 			//load file
    	// 			$json=file_get_contents($resp_file);
    	// 			$obj=$this->utils->decodeJson($json);
    	// 			$callbackExtraInfo=$obj['callbackExtraInfo'];

    	// 			//call
    	// 			$client = new \GuzzleHttp\Client();

    	// 			// $orderId=$api->getOrderIdFromParameters($callbackExtraInfo);
    	// 			// if(!empty($orderId)){
	    // 				// $api->callbackFromServer($orderId, $callbackExtraInfo);
    	// 			// }else{
			  //   		// $this->utils->error_log('load order id faild', $callbackExtraInfo);
    	// 			// }
    	// 		}else{
		   //  		$this->utils->error_log('load api faild', $apiId);
    	// 		}
    	// 	}else{
    	// 		$this->utils->error_log('load response file faild', $filepath);
    	// 	}
    	// }else{
    	// 	$this->utils->error_log('load response result faild', $resp);
    	// }
    }

    public function calculateMonthlyEarnings_2($yearmonth = NULL, $affiliate_username) {

    	$this->load->library(array('affiliate_commission', 'user_agent'));

    	try {

			$yearmonth = ! empty($yearmonth) ? $yearmonth : $this->utils->getLastYearMonth();

    		$this->affiliate_commission->generate_monthly_earnings_for_all($yearmonth, $affiliate_username);

    		$message = lang("Affiliate Commission for the Year Month of {$yearmonth} has been successfully generated.");
    	} catch (Exception $e) {
    		$message = $e->getMessage();
    	}

    	$this->utils->debug_log("==== {$message} ====");
    	$url = $this->agent->referrer();
    	echo "<script type=\"text/javascript\">window.location.href = '{$url}';</script>";
    }

    /**
     * @param $start_date
     * @param $end_date
     * @param string $affiliate_username
     * @param null $yearMonth
     * @return bool
     */
    public function calculate_monthly_earnings_by_specific_date($start_date, $end_date, $affiliate_username = '_null', $yearMonth = NULL){
        $this->load->library('affiliate_commission');

        if($affiliate_username == '_null'){
            $affiliate_username = null;
        }

        if(strtotime($start_date) <= strtotime($end_date)){
            $yearmonth = ! empty($yearMonth) ? $yearMonth : $this->utils->getLastYearMonth();

            $this->affiliate_commission->generate_monthly_earnings_for_all($yearmonth, $affiliate_username, $start_date, $end_date);

            $message = lang("Affiliate Commission for the Year Month of {$yearmonth} has been successfully generated.");
            $this->utils->debug_log("==== {$message} ====");
        }else{
            $this->utils->debug_log('Invalid dates!');
        }
        return true;
    }

    public function updateOneworksPlayer($playerUsername){
    	$api=$this->utils->loadExternalSystemLibObject(ONEWORKS_API);
    	if(!empty($api)){
    		$this->utils->debug_log($api->updateMemberSetting($playerUsername));
    	}else{
			$this->utils->error_log('load ONEWORKS_API api failed');
    	}
    }

    public function batchUpdateOneworksPlayer(){
    	$api=$this->utils->loadExternalSystemLibObject(ONEWORKS_API);
    	if(!empty($api)){
    		$this->load->model(['player_model']);
    		$players=$this->player_model->getPlayersList();
    		foreach ($players as $p) {
	    		$this->utils->debug_log($api->updateMemberSetting($p['username']));
    		}
    	}else{
			$this->utils->error_log('load ONEWORKS_API api failed');
    	}
    }

    public function isAvailableWithdrawal($walletAccountId){
    	$this->load->model(['wallet_model']);
    	$rlt=$this->wallet_model->isAvailableWithdrawal($walletAccountId, $status);

    	$this->utils->debug_log('isAvailableWithdrawal ', $rlt, $status);
    }

	/**
	 * Sync Points (Last Hour)
	 *
	 * eg. current time 14:10 =>   13:00 - 13:59
	 *
	 * @param $currenDate 	yyyy-mm-dd
	 */
	public function syncPointsLastHour() {

		$this->load->model(array('group_level'));

		$fromDatetime = date('Y-m-d H:00:00', strtotime('-1 hour'));
		$toDatetime = date('Y-m-d H:59:59', strtotime('-1 hour'));

		$rlt = $this->group_level->convertToPoints($fromDatetime, $toDatetime);
		$msg = $this->utils->debug_log('result', $rlt);
		$this->returnText($msg);
	}

	public function generate_agency_settlement($only_agent_id=null){
		$this->load->model(['agency_model']);
		$this->load->library(['agency_library']);
		$agent_id_list=$this->agency_model->get_agent_id_list();

		if(!empty($agent_id_list)){
			foreach ($agent_id_list as $agent_info) {
				$agent_id=$agent_info['agent_id'];
				if($only_agent_id!=null && $only_agent_id!=$agent_id){
					continue;
				}
				$this->utils->debug_log('create_settlement '.$agent_id);
				$this->agency_library->create_settlement($agent_id);
			}
		}

		$this->utils->debug_log('done '.count($agent_id_list));
	}

	public function generate_current_agency_settlement($only_agent_id=null){

		// $only_current= $only_current=='true';

		$this->load->model(['agency_model']);
		$this->load->library(['agency_library']);
		$agent_id_list=$this->agency_model->get_agent_id_list();

		if(!empty($agent_id_list)){
			foreach ($agent_id_list as $agent_info) {
				$agent_id=$agent_info['agent_id'];
				if($only_agent_id!=null && $only_agent_id!=$agent_id){
					continue;
				}
				$this->utils->debug_log('create_current_settlement '.$agent_id);
				$this->agency_library->create_current_settlement($agent_id);
			}
		}

		$this->utils->debug_log('done '.count($agent_id_list));
	}

    public function generateDailyBalance($arg_date = null) {
    	// OGP-14487: Add date argument
    	$this->load->model('daily_balance');
    	$this->daily_balance->generateDailyBalance($arg_date);
    }

    public function generateDailyPlayerBalance($startDate = null, $endDate = null) {
    	$this->load->model('daily_balance');
    	$date_start = new DateTime();
    	$date_start->modify('-1 day 00:00:00');
    	// $date_start->modify('first day of this month 00:00:00');
    	$date_end = new DateTime();
    	$date_end->modify('-1 day 23:59:59');
    	if (!empty($startDate)) {
    		$date_start->modify($startDate . ' 00:00:00');
    		$date_end->modify($startDate . ' 23:59:59');
    	}
    	if (!empty($endDate)) {
    		$date_end->modify($endDate . ' 23:59:59');
    	}
    	$this->utils->debug_log('start date : '. $date_start->format('Y-m-d H:i:s'));
    	$this->utils->debug_log('start date : '. $date_end->format('Y-m-d H:i:s'));
   		$days = $date_end->diff($date_start)->days;
   		$this->utils->debug_log($days);
   		for ($i = 0; $i <= $days; $i++) {
   			if ($i > 0) {
    			$date_start->modify('+1 day');
   			}
   			$startDate = $date_start->format('Y-m-d 00:00:00');
   			$endDate = $date_start->format('Y-m-d 23:59:59');
   			$this->utils->debug_log('calculate start date : ' . $startDate);
   			$this->utils->debug_log('calculate start date : ' . $endDate);
   			$this->daily_balance->generatePlayerBalanceByDate($startDate, $endDate);
   		}
    }

    public function copyTemplateSettingToDB(){
    	$this->load->model(['operatorglobalsettings']);
    	$this->operatorglobalsettings->copyTemplateSettingToDB();

    	$this->utils->debug_log('copyTemplateSettingToDB :'.$this->utils->getConfig('view_template'));
    }

     /**
	 * Batch Adjust Player Level
	 *
	 * @param int $fromLevelId
	 * @param int $toLevelId
	 * @param datetime	 $playerRegisterDatetimeStar 'createdOn < ',$dateTimeTo
	 * @param datetime   $playerRegisterDatetimeTo -'createdOn < ',$dateTimeTo
	 * @param bool  $isFromTheStart
	 * Examples
	 * A. without date: at certain level regardless of date: | batchChangeVIPLevel 1 2  | means from level 1 to level 2
     * B. all players rergardless of players or players current level: | batchChangeVIPLevel  0 2  | means  all-players to level 2
     * C. within range of date at certain level : | batchChangeVIPLevel 1 2 '2015-03-17 04:14:22' '2015-03-17 04:30:46' | means from level 1 to level 2  at certain range date
          OR | batchChangeVIPLevel 1 2 '2015-03-17 04:14:22' '2015-03-17 04:30:46' false |
     * D. all players rergardless of current level at certain range date: | batchChangeVIPLevel 0 2 '2015-03-17 04:14:22' '2015-03-17 04:30:46'  | means all-players to level 2  at certain range date
     * E. all players rergardless of current level before $playerRegisterDatetimeTo: | batchChangeVIPLevel 0 2 0 '2015-03-17 04:30:46' true  | means all-players to level 2 before $playerRegisterDatetimeTo
     *

	 */

          public function batchChangeVIPLevel($fromLevelId=0,$toLevelId=0,$playerRegisterDatetimeStart=null, $playerRegisterDatetimeTo=null, $isFromTheStart = false){

          	if( is_numeric($fromLevelId) && $fromLevelId  >= 0  &&  is_numeric($toLevelId) && $toLevelId > 0  ){

          		$this->load->model(array('player','group_level'));
          		$dateTimeFrom = null;
          		$dateTimeTo = null;

          		$isFromTheStart = ($isFromTheStart=='true' || $isFromTheStart == 'TRUE') ? true : false;

    			# check if truly or intentionally has datateime arguments
          		$numargs = func_num_args();
          		if($numargs == 3 ){
          			$this->utils->debug_log('BATCH_ADJUST_PLAYER_LEVEL Please check your arguments ');
          			return;
          		}

          		if( is_numeric($playerRegisterDatetimeStart) && $playerRegisterDatetimeStart == '0' && $isFromTheStart ){

          			$dateTimeFrom = 0;

          			if(date('Y-m-d H:i:s',strtotime($playerRegisterDatetimeTo)) == $playerRegisterDatetimeTo){
          				$dateTimeTo = $this->utils->formatDateTimeForMysql( new \DateTime($playerRegisterDatetimeTo));
          			}else{
          				$this->utils->debug_log('BATCH_ADJUST_PLAYER_LEVEL Invalid $playerRegisterDatetimeTo ');
          				return;
          			}

          		}else{

          			if(date('Y-m-d H:i:s',strtotime($playerRegisterDatetimeStart)) == $playerRegisterDatetimeStart   && date('Y-m-d H:i:s',strtotime($playerRegisterDatetimeTo)) == $playerRegisterDatetimeTo  ) {
          				$dateTimeFrom = $this->utils->formatDateTimeForMysql(new \DateTime($playerRegisterDatetimeStart));
          				$dateTimeTo = $this->utils->formatDateTimeForMysql( new \DateTime($playerRegisterDatetimeTo));
          			}else{
          				if($numargs != 2){
          					$this->utils->debug_log('BATCH_ADJUST_PLAYER_LEVEL Invalid datetime OR $isFromTheStart should be true');
          					return;
          				}
          			}
          		}

          		$players = $this->player->getAllPlayersByLevelId($fromLevelId,$dateTimeFrom ,$dateTimeTo,$isFromTheStart);

          		$updatedPlayers = array();

          		if(!empty($players)){
          			$this->group_level->startTrans();
          			foreach ($players as $value ) {
          				$result = $this->group_level->batchAdjustPlayerLevel($value['playerid'], $toLevelId);
          				$result['registeredOn'] = $value['createdOn'];
          				$result['playerId'] = $value['playerid'];
          				$updatedPlayers[$value['playerid']] =  $result;
          			}

          			$this->utils->debug_log('BATCH_ADJUST_PLAYER_LEVEL ',  'TOTAL COUNT', count($updatedPlayers),'IS_FROM_THE_START',$isFromTheStart,  'REGISTER DATE FROM', $dateTimeFrom,  'REGISTER DATE TO', $dateTimeTo, 'FROM_LEVEL',$fromLevelId, 'TO_LEVEL',$toLevelId,json_encode($updatedPlayers));
          			$this->group_level->endTrans();

          		}else{
          			$this->utils->debug_log('BATCH_ADJUST_PLAYER_LEVEL  No players is under that level or not in range of datetime you entered  ');
          		}

          	}else{
          		$this->utils->debug_log('BATCH_ADJUST_PLAYER_LEVEL Please check your input Level ids  ');
          	}

          }


    /**
     * Update player that is not registered on Entwine game provider
     */
    function updatePlayersInGameProviderEntwine(){
        $player_usernames = array("cnytest01", "cnytest02", "cnytest03", "cnytest04", "cnytest05", "light", "test002", "testking", "testlight", "fwcnytst1", "fwcnytst2", "testblack", "testwhite", "testdarkred", "testgreen", "cnytest01", "test02", "test03", "test04", "test05", "test0502", "testcloud");

        $not_registered = 0;
        $api_id = ENTWINE_API;
        $data = [ "register" => $not_registered ];

        $this->db->where("game_provider_id", $api_id);
        $this->db->where_not_in("login_name",$player_usernames);
        $this->db->update("game_provider_auth",$data);

        echo $this->db->last_query();
    }

    /**
	 * Export daily transactions within date range
	 * Note: maximum no. of transaction rows tested: 19000+/day
	 * @access	public
	 * @param	datetime
	 * @param	datetime
	 * @param	string or integer
	 * @return	void
	 */
    public function export_transaction_csv_daily($fromDateTimeSt, $endDateTimeStr, $language = 1) {
	  	$this->load->model(array('report_model'));
	  	$this->load->library(array('language_function'));

	  	switch ($language) {

	  		case 'english':
	  		case Language_function::INT_LANG_ENGLISH:

	  		$this->language_function->setCurrentLanguage(Language_function::INT_LANG_ENGLISH);
	  		$this->lang->is_loaded = array();
	  		$this->lang->language = array();
	  		$this->lang->load('main', 'english');
	  		break;

	  		case 'chinese':
	  		case Language_function::INT_LANG_CHINESE:

	  		$this->language_function->setCurrentLanguage(Language_function::INT_LANG_CHINESE);
	  		$this->lang->is_loaded = array();
	  		$this->lang->language = array();
	  		$this->lang->load('main', 'chinese');
	  		break;

	  		case 'indonesian':
	  		case Language_function::INT_LANG_INDONESIAN:

	  		$this->language_function->setCurrentLanguage(Language_function::INT_LANG_INDONESIAN);
	  		$this->lang->is_loaded = array();
	  		$this->lang->language = array();
	  		$this->lang->load('main', 'indonesian');
	  		break;

	  		case 'vietnamese':
	  		case Language_function::INT_LANG_VIETNAMESE:

	  		$this->language_function->setCurrentLanguage(Language_function::INT_LANG_VIETNAMESE);
	  		$this->lang->is_loaded = array();
	  		$this->lang->language = array();
	  		$this->lang->load('main', 'vietnamese');
	  		break;

	  		case 'korean':
	  		case Language_function::INT_LANG_KOREAN:

	  		$this->language_function->setCurrentLanguage(Language_function::INT_LANG_KOREAN);
	  		$this->lang->is_loaded = array();
	  		$this->lang->language = array();
	  		$this->lang->load('main', 'korean');
	  		break;

	  		default:
	  		$this->language_function->setCurrentLanguage(Language_function::INT_LANG_ENGLISH);
	  		$this->lang->is_loaded = array();
	  		$this->lang->language = array();
	  		$this->lang->load('main', 'english');
	  		break;

	  	}

	  	$timelimit = 1; //day

	  	$msg = $this->utils->debug_log('=========start export_daily_transaction_in_csv============================',
	  		'fromDateTimeSt', $fromDateTimeSt, 'endDateTimeStr', $endDateTimeStr, 'timelimit', $timelimit);
	  	$this->returnText($msg);
	  	$mark = 'export_daily_transaction_in_csv';
	  	$this->utils->markProfilerStart($mark);

	  	$dateTimeFrom = $this->utils->formatDateForMysql(new \DateTime($fromDateTimeSt));
	  	$dateTimeTo = $this->utils->formatDateForMysql(new \DateTime($endDateTimeStr));
	  	$folderName = $dateTimeFrom.'_to_'.$dateTimeTo.'_Transactions';


	  	$dateTimeFrom = new \DateTime($dateTimeFrom);
	  	$dateTimeTo = new \DateTime($dateTimeTo);

	  	while ($dateTimeFrom <= $dateTimeTo  ) {

	  		$to = $this->utils->getNextTime($dateTimeFrom , '+' . $timelimit . ' day');
	  		$fromDate = $dateTimeFrom->format('Y-m-d ').Utils::FIRST_TIME;
	  		$toDate = $dateTimeFrom->format('Y-m-d ').Utils::LAST_TIME;

	    // export to csv
	  		$filename =  $dateTimeFrom->format('Y-m-d-') . 'transactions';

	  		$request = array();
	  		$request['CdateRangeValueStart'] = $fromDate;
	  		$request['CdateRangeValueEnd'] = $toDate;
	  		$request['draw'] =0;
	  		$is_export = true;
	  		$transactions = $this->report_model->transaction_details(null, $request, $is_export);

	  		$transactions['folder_name'] = $folderName;
	  		$this->utils->create_csv($transactions, $filename,TRUE);

	  		$this->utils->debug_log('from', $fromDate,'to',$toDate, 'filename', $filename,'at foldername', $folderName, 'count',count(@$transactions['data']));
	  		$dateTimeFrom = $to;
	  	}


	  	$msg = $this->utils->markProfilerEndAndPrint($mark);
	  	$this->returnText($msg);

	  	$msg = $this->utils->debug_log('=========end  export_daily_transaction_in_csv=============================');
	  	$this->returnText($msg);
	}


    public function sync_service_api_env(){
    	//load config
    	//write env
    	$key="base64:ChHZStGduTxnJgE/q89/xRVI8gWcQSdizDA6Kmk0qi0=";
    	$db_default_hostname=$this->utils->getConfig('db.default.hostname');
    	$db_default_port=$this->utils->getConfig('db.default.port');
    	$db_default_username=$this->utils->getConfig('db.default.username');
    	$db_default_database=$this->utils->getConfig('db.default.database');
    	$db_default_password=$this->utils->getConfig('db.default.password');

    	$db_readonly_hostname=$this->utils->getConfig('db.readonly.hostname');
    	$db_readonly_port=$this->utils->getConfig('db.readonly.port');
    	$db_readonly_username=$this->utils->getConfig('db.readonly.username');
    	$db_readonly_database=$this->utils->getConfig('db.readonly.database');
    	$db_readonly_password=$this->utils->getConfig('db.readonly.password');

    	$debug=$this->utils->isDebugMode() ? 'true' : 'false';
    	$RUNTIME_ENVIRONMENT=$this->utils->getConfig('RUNTIME_ENVIRONMENT');

    	$app_name="service_api";
    	$app_url=$this->utils->getSystemUrl('admin', '/service');
    	$app_log_level='debug';
    	$cache_driver='redis';
    	$session_driver='redis';
    	$queue_driver='redis';
    	$broadcast_driver='redis';

    	$redis_host=$this->utils->getConfig('lock_servers')[0][0];
    	$redis_port=$this->utils->getConfig('lock_servers')[0][1];

    	$env_file=<<<EOD
APP_NAME={$app_name}
APP_ENV={$RUNTIME_ENVIRONMENT}
APP_KEY={$key}
APP_DEBUG={$debug}
APP_LOG_LEVEL={$app_log_level}
APP_URL={$app_url}

DB_CONNECTION=mysql
DB_HOST={$db_default_hostname}
DB_PORT={$db_default_port}
DB_DATABASE={$db_default_database}
DB_USERNAME={$db_default_username}
DB_PASSWORD={$db_default_password}

DB_READONLY_HOST={$db_readonly_hostname}
DB_READONLY_PORT={$db_readonly_port}
DB_READONLY_DATABASE={$db_readonly_database}
DB_READONLY_USERNAME={$db_readonly_username}
DB_READONLY_PASSWORD={$db_readonly_password}

BROADCAST_DRIVER={$broadcast_driver}
CACHE_DRIVER={$cache_driver}
SESSION_DRIVER={$session_driver}
QUEUE_DRIVER={$queue_driver}

REDIS_HOST=${redis_host}
REDIS_PASSWORD=null
REDIS_PORT=${redis_port}

MAIL_DRIVER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null

PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
EOD;

		$env_filepath= realpath(APPPATH.'../..').'/service_api/.env';

		$this->utils->debug_log('write to '.$env_filepath, $env_file);

		file_put_contents($env_filepath, $env_file);

    }

	public function scanRegisterGameAccount($game_platform_id){

		$api=$this->utils->loadExternalSystemLibObject($game_platform_id);

		$qry=$this->db->select('player.username, game_provider_auth.id')->from('game_provider_auth')->join('player', 'game_provider_auth.player_id=player.playerId')->where('game_provider_id', $game_platform_id)
				->where('register', 1)->get();

		$rows=$qry->result_array();
		foreach ($rows as $row) {
			$rlt=$api->isPlayerExist($row['username']);
			if($rlt['success'] && $rlt['exists']===false){
				$this->db->set('register', 0)->where('id', $row['id']);
				$this->db->update('game_provider_auth');
				$this->utils->debug_log('updated register to false', $row['username']);
			}
		}
	}

    /**
	 * Batch Copy lost XMLs to right folder
	 * For resolving  missing xmls when syncing and resyncing
	 *
	 * @param date or datetime  2017-06-01 00:00:00
	 * @param date or datetime  2017-06-30 23:59:59
	 * Example: sudo ./command.sh batchCopyLostAGXmlToDesignatedDir  '2017-06-01 00:00:00' '2017-06-30 23:59:59' | Scans all platform by date range
	 * Example2: sudo ./command.sh batchCopyLostAGXmlToDesignatedDir | NOTE: Scans all directories Warning can consume more memory
     */
	public function batchCopyLostAGXmlToDesignatedDir($dateTimeFromStr=null,$dateTimeToStr=null){
		$platformTypes = ['AGIN','BBIN','BG','HG','HUNTER','NYX','PT','SABAH','XIN'];
		$byChosenDateRange = (!empty($dateTimeFromStr)  && !empty($dateTimeToStr));

		foreach ($platformTypes as $platformType) {
			if($byChosenDateRange){
				$this->copyLostAGXmlToDesignatedDirectory($platformType,$dateTimeFromStr,$dateTimeToStr);
			}else{
				$this->copyLostAGXmlToDesignatedDirectory($platformType,null,null);
			}
		}
	}

	/**
	* Copy lost XMLs to right folder
	* For resolving  missing xmls when syncing and resyncing
	*
	* @param string Ex. AGIN or BBIN or BG or HG or HUNTER or NYX or PT or SABAH or XIN
	* @param date or datetime  2017-06-01 00:00:00
	* @param date or datetime  2017-06-30 23:59:59
	* Example: sudo ./command.sh copyLostAGXmlToDesignatedDirectory 'HUNTER' '2017-06-01 00:00:00' '2017-06-30 23:59:59' | Scan by date range
	* Example2: sudo ./command.sh copyLostAGXmlToDesignatedDirectory 'HUNTER' | Scans the whole HUNTER directory
	*/
	public function copyLostAGXmlToDesignatedDirectory($platformType,$dateTimeFromStr=null,$dateTimeToStr=null){
		$platformType = strtoupper($platformType);
		$byChosenDateRange = (!empty($dateTimeFromStr)  && !empty($dateTimeToStr));
		$platformTypes = ['AGIN','BBIN','BG','HG','HUNTER','NYX','PT','SABAH','XIN'];
		$rangeDayFolders = [];

		if($byChosenDateRange){
			$startDate = $this->utils->formatDateForMysql(new \DateTime($dateTimeFromStr));
			$endDate = $this->utils->formatDateForMysql(new \DateTime($dateTimeToStr));
			$startRef = $startDate = new \DateTime($startDate);
			$endDate =  new \DateTime($endDate);

			$day_diff = $endDate->diff($startDate)->format("%a");
			$day_diff = $day_diff > 0 ? $day_diff : 2;

			$rangeDayFolders = [$startRef->format('Ymd')];//pre add the base date to array

			for($i=0; $i<$day_diff; $i++){
				$day = $startDate->modify('+1 day')->format('Ymd');
				array_push($rangeDayFolders, $day);
			}
		}

		if(!in_array($platformType, $platformTypes)){
			$this->utils->debug_log( $platformType.' NOT BELONG TO AG PLATFORM TYPES------------------------', $platformTypes);
			return;
		}
		$agPath = '/var/game_platform/ag/';
		$lostAndfound = 'lostAndfound';

		$directory = $agPath.$platformType ;// /var/game_platform/ag/HUNTER

		if (file_exists($directory)) {
			$agXmlFolders = [];
			if(!empty($rangeDayFolders)){
				$agXmlFolders = $rangeDayFolders;
			}else{
				$agXmlFolders = array_diff(scandir($directory), array('..', '.'));
			}
			$this->utils->debug_log( 'DAYS FOLDERS TO SCAN -----  '.$directory , $agXmlFolders);
			foreach ($agXmlFolders as $xmlFolder) {
				#for xmls not in lostAndfound folder
				if($xmlFolder != $lostAndfound){
					$xmlPath = $directory.'/'.$xmlFolder;
					$this->processCopyXml($directory,$xmlPath,$xmlFolder);
				}
			}
		}

		$lostAndfoundDir = $agPath.$platformType.'/'.$lostAndfound ;// /var/game_platform/ag/HUNTER/lostAndfound

		if (file_exists($lostAndfoundDir)) {
			$lostAndfoundFolders = [];
			if(!empty($rangeDayFolders)){
				$lostAndfoundFolders  = $rangeDayFolders;
			}else{
				$lostAndfoundFolders  = array_diff(scandir($lostAndfoundDir), array('..', '.'));
			}
			$this->utils->debug_log( 'DAYS FOLDERS TO SCAN -----  '.$lostAndfoundDir, $lostAndfoundFolders);
			//for lost and found
			foreach ($lostAndfoundFolders as $xmlFolder) {
				$xmlPath = $lostAndfoundDir.'/'.$xmlFolder;
				$this->processCopyXml($directory,$xmlPath,$xmlFolder);
			}
		}
	}


	/**
	* Process Copying of xml to designated directory
	* @param string $directrory
	* @param string $xmlPath
	* @param string $xmlFolder
	*
	*/
	private function processCopyXml($directory,$xmlPath,$xmlFolder){
	  	if (file_exists($xmlPath)) {
	  		$xmls = array_diff(scandir($xmlPath), array('..', '.'));
	  		foreach ($xmls as $xml) {
	  			$xmlname = substr($xml, 0, 8);
				//if not belong to folder copy to designated folder
	  			if($xmlname != $xmlFolder){
	  				$fileToCopyTo = $directory.'/'.$xmlname.'/'.$xml;
	  				$fileToCopyToDir = $directory.'/'.$xmlname;
	  				$fileFrom = $xmlPath.'/'.$xml ;
	  				$fileToExt = pathinfo($fileToCopyTo, PATHINFO_EXTENSION);
	  				$fileFromExt = pathinfo($fileFrom , PATHINFO_EXTENSION);
	  				if (!file_exists($fileToCopyToDir)) {
	  					mkdir($fileToCopyToDir, 0777);
	  					$this->utils->debug_log('AG DAY FOLDER CREATED BEC NOT EXIST ',$fileToCopyToDir);
	  				}
	  				if (!file_exists($fileToCopyTo) && $fileToExt == 'xml' && $fileFromExt == 'xml') {
	  					copy($fileFrom,$fileToCopyTo);
	  					chmod($fileToCopyTo,0777);
	  					$this->utils->debug_log('CURRENT DIR', $directory, 'FILE '.$xml.' IS FROM ----', $fileFrom ,' TO  ----'.$fileToCopyTo );
	  				}
	  			}
	  		}
	  	}
  	}




         /**
		 * Export player info in csv file
		 * Note: Run this only on you local
		 * @access	public
		 * @param	datetime
		 * @param	datetime
		  */
         public function exportPlayersInfoToCsv($fromRegisterDateTimeStr, $endRegisterDateTimeStr){

         	$this->load->model(array('player_model','wallet_model'));
         	$this->load->library(array('salt'));

         	$dateTimeFrom = $this->utils->formatDateTimeForMysql(new \DateTime($fromRegisterDateTimeStr));
         	$dateTimeTo = $this->utils->formatDateTimeForMysql(new \DateTime($endRegisterDateTimeStr));



         	$msg = $this->utils->debug_log('=========start exportPlayersInfoToCsv============================',
         		'fromDateTimeSt', $dateTimeFrom, 'endDateTimeStr', $dateTimeTo);
         	$this->returnText($msg);
         	$mark = 'exportPlayersInfoToCsv';
         	$this->utils->markProfilerStart($mark);

         	$walletMap = $this->utils->getGameSystemMap();


		$sql = <<<EOD
SELECT
  player.playerId,
  player.username,
  CONCAT(
    playerdetails.firstName,
    ' ',
    playerdetails.lastName,
    ' '
  ) AS realname,
  player.`password`,
  player.`withdraw_password`,
  playerdetails.`contactNumber` AS phone,
  player.email,
  PB.BankInfo,
  promorules.promoName,
  player.createdOn AS registerTime,
  player.`invitationCode` AS friendReferralCode,
  affiliates.username AS affiliate
FROM
  player
  LEFT JOIN playerdetails
    ON playerdetails.playerId = player.playerId
  LEFT JOIN
    (SELECT
      playerId,
      GROUP_CONCAT(
    CONCAT(
        'BANK-NAME:  ',
      banktype.`bankName`,
      ' ',
        'ACCOUNT-NAME:  ',
      playerbankdetails.`bankAccountFullName`,
      ' ',
      '     BANK-ACCOUNT:  ',
      playerbankdetails.bankAccountNumber,
      ' ',
      '     BANK-BRANCH:  ',
      playerbankdetails.branch,
      '    '
    )
  ) AS BankInfo
    FROM
      playerbankdetails
      LEFT JOIN banktype ON banktype.`bankTypeId` = playerbankdetails.`bankTypeId`
    GROUP BY playerId) AS PB
    ON PB.`playerId` = player.`playerId`
    LEFT JOIN affiliates
    ON affiliates.`affiliateId` = player.affiliateId
  LEFT JOIN playerpromo
    ON playerpromo.playerId = player.`playerId`
  LEFT JOIN promorules
    ON promorules.promorulesId = playerpromo.promorulesId
WHERE player.createdOn >= ?  AND player.createdOn <= ?
GROUP BY player.playerId
ORDER BY player.playerId DESC

EOD;



         	$query = $this->db->query($sql,array($dateTimeFrom,$dateTimeTo));
         	$result = $query->result_array();
         	$newPlayerList = array();

         	foreach ($result as $v) {

         		$decryptedPwd = $this->player_model->getPasswordByUsername($v['username']);
         		$big_wallet =  $this->wallet_model->getBigWalletByPlayerId($v['playerId']);
         		$walletInfo = array();

         		$totalBalance = array(
         			'totalBalance' => $big_wallet['total']
         			);
         		array_push($walletInfo,$totalBalance);

         		$wallet = array(
         			'wallet' => 'Main wallet' ,
         			'balance' => $big_wallet['main']['total']
         			);
         		array_push($walletInfo,$wallet);


         		foreach ($big_wallet['sub'] as $key => $subwallet) {
         			$wallet = array(
         				'wallet' => $walletMap[$key],
         				'balance' => $subwallet['total']
         				);
         			array_push($walletInfo,$wallet);
         		}
         		$v['wallet'] = json_encode($walletInfo) ;
         		$v['password'] = $decryptedPwd;
         		array_push($newPlayerList, $v);

         	}


         	$forExportData = array(
         		'header_data' => ['PlayerId','Username','RealName','Password', 'WithdrawPassword','Phone','Email','BankAccounts', 'PromoName','RegisterTime', 'FriendReferralCode','Affiliate','Wallet'],
         		'data' => $newPlayerList
         		);


         	$filename = 'playerInfoList'.time();
         	$this->utils->create_csv($forExportData , $filename);

         	$msg = $this->utils->markProfilerEndAndPrint($mark);
         	$this->returnText($msg);

         	$msg = $this->utils->debug_log('=========end  exportPlayersInfoToCsv=============================');
         	$this->returnText($msg);


         }


          /**
		 * Export Affiliates info in csv file
		 * Note: Run this only on you local
		 * @access	public
		 * @param	datetime
		 * @param	datetime
		  */
          public function exportAffiliatesInfoToCsv($fromRegisterDateTimeStr, $endRegisterDateTimeStr){

          	$dateTimeFrom = $this->utils->formatDateTimeForMysql(new \DateTime($fromRegisterDateTimeStr));
          	$dateTimeTo = $this->utils->formatDateTimeForMysql(new \DateTime($endRegisterDateTimeStr));

          	$msg = $this->utils->debug_log('=========start exportaffiliatessInfoToCsv============================',
          		'fromDateTimeSt', $dateTimeFrom, 'endDateTimeStr', $dateTimeTo);
          	$this->returnText($msg);
          	$mark = 'exportaffiliatessInfoToCsv';
          	$this->utils->markProfilerStart($mark);

          	$this->load->library(array('salt'));


		$sql = <<<EOD
SELECT
  affiliates.affiliateId,
  affiliates.`createdOn`,
  affiliates.username,
  affiliates.trackingCode,
  affiliates.`password`,
    GROUP_CONCAT(
    CONCAT(
      'BANK-NAME:  ',
      affiliatepayment.`accountName`,
      ' ',
       'ACCOUNT-NAME:  ',
      affiliatepayment.`bankName`,
      ' ',
      ' BANK-ACCOUNT:  ',
      affiliatepayment.accountNumber,
      ' ',
      ' BANK-ACCOUNT-INFO  ',
      affiliatepayment.accountInfo,
      ' '
    )
  ) AS BankInfo
FROM
  affiliates
 LEFT JOIN affiliatepayment
 ON affiliatepayment.affiliateId = affiliates.affiliateId
WHERE affiliates.createdOn >= ?  AND affiliates.createdOn <= ?
GROUP BY affiliates.affiliateId

EOD;

          	$query = $this->db->query($sql,array($dateTimeFrom,$dateTimeTo));
          	$result = $query->result_array();
          	$newAffiliatesList = array();

          	foreach ($result as $v) {

          		$decryptedPwd = $this->salt->decrypt($v['password'], $this->getDeskeyOG());
          		$v['password'] = $decryptedPwd;
          		array_push($newAffiliatesList, $v);

          	}
          	$forExportData = array(
          		'header_data' => ['AffiliateId','CreatedOn','Username','trackingCode','Password', 'BankInfo'],
          		'data' => $newAffiliatesList
          		);

          	$filename = 'afffiliateInfoList'.time();
          	$this->utils->create_csv($forExportData , $filename);

          	$msg = $this->utils->markProfilerEndAndPrint($mark);
          	$this->returnText($msg);

          	$msg = $this->utils->debug_log('=========end  exportAffiliatesInfoToCsv=============================');
          	$this->returnText($msg);

         }

	public function initRespTable($date=null){
		if(empty($date)){
			$date=strtotime('tomorrow');
		}else{
			$date=strtotime($date);
		}

		$today=date('Ymd', strtotime('-1 day', $date));
		list($t1, $t2)=$this->utils->initAllRespTablesByDate($today);
		$this->utils->debug_log("initAllRespTablesByDate today ".$t1, $t2);
		$tomorrow=date('Ymd', $date);
		list($t1, $t2)=$this->utils->initAllRespTablesByDate($tomorrow);
		$this->utils->debug_log("initAllRespTablesByDate tomorrow".$t1, $t2);
		$tomorrow=date('Ymd', strtotime('+1 day', $date));
		list($t1, $t2)=$this->utils->initAllRespTablesByDate($tomorrow);
		$this->utils->debug_log("done initAllRespTablesByDate ".$t1, $t2);
	}

	public function initRespCashierTable($date=null){
		if(empty($date)){
			$date=new DateTime('tomorrow');
		}else{
			$date=new DateTime($date);
		}
		$monthStr=$date->format('Ym');
		$this->utils->initRespCashierTableByMonth($monthStr);
		$this->utils->info_log('initRespCashierTableByMonth this month', $monthStr);

		//next month
		$date->modify('+1 month');
		$monthStr=$date->format('Ym');
		$this->utils->initRespCashierTableByMonth($monthStr);
		$this->utils->info_log('initRespCashierTableByMonth next month', $monthStr);

	}

	public function delete_day_by_day($table_name, $date_field, $start_day, $max_day,  $dry_run='false', $save_to_table=_COMMAND_LINE_NULL, $token=_COMMAND_LINE_NULL){

		$this->load->model(['player_model']);

		$player_model = $this->player_model;

		if($token != _COMMAND_LINE_NULL){
			$this->queue_result->appendResult($token, ['request_id'=>_REQUEST_ID, 'result'=> [$table_name, $date_field, $start_day, $max_day]] , false, false);

		}

		$end=new DateTime($max_day.' '.Utils::LAST_TIME);

		$start=new DateTime($start_day.' '.Utils::FIRST_TIME);

		$step='+24 hours';

		$dry_run=$dry_run=='true';

		$cnt = 0 ;

		$success=$this->utils->loopDateTimeStartEnd($start, $end, $step, function($from, $to, $step)
			use($table_name,$save_to_table, $date_field, $dry_run , &$cnt, $player_model, $token){

            // put it in mysql trans to prevent data loss
				$player_model->startTrans();
            // if not to save the data
				if($save_to_table != _COMMAND_LINE_NULL){
					$cnt = $cnt + $this->archiveDataBeforeDelete($from,$to,$table_name,$save_to_table,$date_field,$token,$dry_run=false);
				}

				$sql='DELETE FROM `'.$table_name.'` WHERE `'.$date_field."`>='".$this->utils->formatDateTimeForMysql($from)
				."' and ".$date_field."<='".$this->utils->formatDateTimeForMysql($to)."'";

				$this->utils->debug_log('try run sql', $sql, $from, $to, $step);
				$deletedNumber=0;
				if($dry_run){
    			//ignore
				}else{
					$this->db->query($sql);
					$deletedNumber=$this->db->affected_rows();
					sleep(1);
				}
				$this->utils->debug_log('after exec sql, affected_rows', $deletedNumber);

                //just in case error in trans occured
				if ($player_model->isErrorInTrans()){
					$client_tag = [
							':warning:',
							'#delete_and_archive_warning',
							'#'.str_replace("og_","",$this->_app_prefix),
							'#data_deletion'. $this->utils->formatYearMonthForMysql(new DateTime),
							'#job_'.$token
						];
				        $details = ['warning' => 'Deletion encountered TransError ', 'details' => ['from' => $from, 'to'=>$to, 'sourceTable'=>$table_name,'targetTable' =>$save_to_table, 'deletedNumber'=>$deletedNumber]  ];
						$msg = "Delete start time: ".$this->utils->getNowForMysql()." | Hostname: ". $this->utils->getHostname();
						$msg .= "\n";
						$msg .= "``` json \n".json_encode($details, JSON_PRETTY_PRINT)." \n```";
						$this->sendNotificationToMattermost('Delete and Archive Data', 'delete_table_data', $msg, 'warning', $client_tag);
				}

				$player_model->endTrans();

				return true;

			});

		if($success){
			$this->utils->debug_log('queue: '.$token.' done');
			$details = '';
			if($save_to_table != _COMMAND_LINE_NULL){
				$details = ['table' => $table_name, 'delete_process_status'=>'done','process_end_time'=>$this->utils->getNowForMysql(), 'deleted_counts' => $cnt];
			}else{
				$details = ['table' => $table_name, 'delete_process_status'=>'done','process_end_time'=>$this->utils->getNowForMysql(), 'deleted_counts' => 'cannot be counted -deleted not saved'];
			}
    		//done
			$this->queue_result->appendResult($token, ['request_id'=>_REQUEST_ID, 'result'=> $success, 'details'=> $details], true, false);
		}else{
			$this->utils->error_log('queue: '.$token.' failed');
    		//error
			$this->queue_result->appendResult($token, ['request_id'=>_REQUEST_ID, 'result'=> $success ], false, true);
		}
		if($token != _COMMAND_LINE_NULL){
			$client_tag = [
				':information_source:',
				'#'.str_replace("og_","",$this->_app_prefix),
				'#data_deletion'. $this->utils->formatYearMonthForMysql(new DateTime),
				'#job_'.$token
			];
			$deleted_counts = ($save_to_table != _COMMAND_LINE_NULL) ? $cnt : 'cannot be counted -delete only';
			$msg = "``` json \n".json_encode(['table' => $table_name, 'delete_process_status'=>'done','process_end_time'=>$this->utils->getNowForMysql(), 'deleted_counts' => $deleted_counts])." \n```";
			$this->sendNotificationToMattermost('Delete and Archive Data', 'delete_table_data', $msg, 'info', $client_tag);

		}
	}

    public function generate_recalculate_cashback_report($fromDate, $toDate, $tempRecalculateCashbackReportTable, $token=_COMMAND_LINE_NULL){
        $this->utils->debug_log('=========start generate_recalculate_cashback_report============================', $token);

        if($token != _COMMAND_LINE_NULL){
            $result = array('fromDate'=>$fromDate, 'toDate'=>$toDate, 'recalculateCashbackReportTable'=>$tempRecalculateCashbackReportTable);
            $done = false;
            $this->queue_result->appendResult($token, ['request_id'=>_REQUEST_ID, 'result'=> $result ], $done, false);
        }

        $this->load->model(['group_level']);
        $this->load->dbforge();

        if($this->db->table_exists($tempRecalculateCashbackReportTable)){
            $this->dbforge->drop_table($tempRecalculateCashbackReportTable);
            $this->utils->debug_log('drop temp recalculate cashback report table', $tempRecalculateCashbackReportTable);
        }

        $this->db->query("CREATE TABLE $tempRecalculateCashbackReportTable LIKE total_cashback_player_game_daily");
        $this->utils->debug_log('[CreateTempTable] Recalculate Cashback Report, table:', $tempRecalculateCashbackReportTable);

        $allRecalculateCashbackRecords = [];
        $success = false;

        $recalculate_cashback_report_tables = $this->group_level->getRecalculateCashbackTableByDate($fromDate, $toDate);

        if(!empty($recalculate_cashback_report_tables)){
            $allRecalculateCashbackRecords = $this->group_level->getRecalculateCashbackRecordByDate($recalculate_cashback_report_tables);
        }

        $this->utils->debug_log('toral recalculate cashback record cnt', count($allRecalculateCashbackRecords));

        if(empty($allRecalculateCashbackRecords)){
            $this->utils->debug_log('no recalculate cashback records', $allRecalculateCashbackRecords);
        }else{
            // combine each date's recalculate cashback record to temp table
            $success = $this->db->insert_batch($tempRecalculateCashbackReportTable, $allRecalculateCashbackRecords);
        }

        $this->utils->debug_log('=========end generate_recalculate_cashback_report============================', $token);

        if(!empty($token)){
            $result = array('batch insert result'=>$success);
            $done=true;
            if ($success) {
                //success
                $this->queue_result->appendResult($token, ['request_id'=>_REQUEST_ID, 'result'=> $result ], $done, false);
            } else {
                $this->queue_result->appendResult($token, ['request_id'=>_REQUEST_ID, 'result'=> $result ], $done, true);
            }
        }
	}

    public function generate_recalculte_wcdp_report($fromDate, $toDate, $tempRecalculateWCDPReportTable, $token = _COMMAND_LINE_NULL){
        // wcdp = withdraw condition deduction process
        $this->utils->debug_log('=========start generate_recalculte_wcdp_report============================', $token);

        if($token != _COMMAND_LINE_NULL){
            $result = array('fromDate' => $fromDate, 'toDate' => $toDate, 'recalculateWCDPReportTable' => $tempRecalculateWCDPReportTable);
            $done = false;
            $this->queue_result->appendResult($token, ['request_id'=>_REQUEST_ID, 'result'=> $result ], $done, false);
        }

        $this->load->model(['group_level']);
        $this->load->dbforge();

        if($this->db->table_exists($tempRecalculateWCDPReportTable)){
            $this->dbforge->drop_table($tempRecalculateWCDPReportTable);
            $this->utils->debug_log('drop temp recalculate withdraw condition deduction process report table', $tempRecalculateWCDPReportTable);
        }

        $this->db->query("CREATE TABLE $tempRecalculateWCDPReportTable LIKE withdraw_condition_deducted_process");
        $this->utils->debug_log('[CreateTempTable] Recalculate Withdraw Condition Deduction Process Report, table:', $tempRecalculateWCDPReportTable);

        $allRecalculateWCDPRecords = [];
        $success = false;

        $originRecalculateTable = 'withdraw_condition_deducted_process';
        $recalculate_wcdp_report_tables = $this->group_level->getRecalculateCashbackTableByDate($fromDate, $toDate, $originRecalculateTable);

        if(!empty($recalculate_wcdp_report_tables)){
            $_where_column = 'cashback_total_date';
            $allRecalculateWCDPRecords = $this->group_level->getRecalculateCashbackRecordByDate($recalculate_wcdp_report_tables, $_where_column);
        }

        $this->utils->debug_log('total withdraw conditon deduction process record cnt', count($allRecalculateWCDPRecords));

        if(empty($allRecalculateWCDPRecords)){
            $this->utils->debug_log('no withdraw conditon deduction process records', $allRecalculateWCDPRecords);
        }else{
            // combine each date's recalculate cashback record to temp table
            $success = $this->db->insert_batch($tempRecalculateWCDPReportTable, $allRecalculateWCDPRecords);
        }

        $this->utils->debug_log('=========end generate_recalculte_wcdp_report============================', $token);

        if(!empty($token)){
            $result = array('batch insert result'=>$success);
            $done=true;
            if ($success) {
                //success
                $this->queue_result->appendResult($token, ['request_id'=>_REQUEST_ID, 'result'=> $result ], $done, false);
            } else {
                $this->queue_result->appendResult($token, ['request_id'=>_REQUEST_ID, 'result'=> $result ], $done, true);
            }
        }
    }

	function sync_cashback($dry_run, $dateTimeStr, $playerId=null) {

		// if($this->utils->isEnabledFeature('enabled_time_period_cashback_mode')){
		// 	return $this->generate_cashback($dateTimeStr, $playerId);
		// }

		$msg = $this->utils->debug_log('=========start sync_cashback============================');
		// $this->returnText($msg);

		$currentDateTime = new DateTime();
		if (!empty($dateTimeStr)) {
			$currentDateTime = new DateTime($dateTimeStr);
		}

		$currentDate = $this->utils->formatDateForMysql($currentDateTime);
		$currentServertime = $this->utils->formatDateTimeForMysql($currentDateTime);

		$this->load->model(array('group_level'));
        $this->load->library(array('player_cashback_library'));

		$cashBackSettings = $this->group_level->getCashbackSettings();
		$this->utils->debug_log('cashBackSettings', $cashBackSettings);

		$this->startTrans();
		// $this->db->trans_start();

		// $payLastUpdate = null;
		// if (isset($cashBackSettings->payLastUpdate) && !empty($cashBackSettings->payLastUpdate)) {
		// $payLastUpdate = new DateTime($cashBackSettings->payLastUpdate);
		// }

		if ($cashBackSettings->toHour == 23) {
			// last time means 00:00 , 23:59:59
			$calcEnabled = $currentDateTime->format('H') == '00';
		} else {

			$calcDateTime = $currentDate . ' ' . $cashBackSettings->toHour . ':59:59';
			$maxCalcDate = new DateTime($calcDateTime);
			$maxCalcDate->modify('+55 minutes');
			$this->utils->debug_log('currentServertime', $currentServertime, 'calcDateTime', $calcDateTime, 'maxCalcDate', $maxCalcDate);
			$calcEnabled = $currentServertime >= $calcDateTime && $currentServertime <= $this->utils->formatDateTimeForMysql($maxCalcDate);
		}

		$calcResult = 'ignore calc';
		if ($calcEnabled) {

			if ($this->utils->getConfig('always_resync_game_logs_for_cashback')) {
				//rebuild game logs
				$this->rebuild_game_logs_by_timelimit(24, $currentDateTime->format('Y-m-d H:00:00'));
			}

			$this->utils->debug_log('currentDate', $currentDate);

            if($this->utils->getConfig('use_accumulate_deduction_when_calculate_cashback')) {
                $calcResult = $this->calculateCashbackWithAccumulateDeduction($cashBackSettings, $currentDate, null, (int)$playerId);
            }else{
                #Calculate cashback
                $calcResult = $this->player_cashback_library->calculateDailyTotalCashbackBySettings($cashBackSettings, $currentDate, $playerId);
                #Change the lastUpdate field for update
            }
		}

		$payDateTime = $currentDate . ' ' . $cashBackSettings->payTimeHour . ':00';
		$this->returnText($this->utils->debug_log('currentServertime', $currentServertime, 'payDateTime', $payDateTime));

		$payResult = 'ignore pay';

		if ($this->endTransWithSucc()) {
			$msg = $this->utils->debug_log('cashback is success', 'calcResult', $calcResult, 'payResult', $payResult);
			// $this->returnText($msg);
		} else {
			$msg = $this->utils->debug_log('cashback is failed', 6300);
			// $this->returnText($msg);
		}
		$msg = $this->utils->debug_log('=========end sync_cashback============================');
	}

	public function collectSubWalletBalanceDaily()  {

		set_time_limit(3600);

		$customApi = null;
		$max_bal = 1;
		$min_bal=0;

		$this->utils->debug_log('collectSubWalletBalanceDaily max_bal', $max_bal, $min_bal);

		$this->load->model(['wallet_model', 'player_model']);
		#get all players in game_provider_auth is registered
		#need field:type_of_player = "real" type= subwallet subwallets = [{gameplatform  }]
		$players = $this->wallet_model->getMaxBalancePlayerList($customApi, $max_bal, $min_bal);

		$this->utils->printLastSQL();
		#load apis
		$this->load->model(['external_system','wallet_model']);

		$cnt=0;
		if(!empty($players)){
			foreach ($players as $player) {
				$cnt++;
				$this->utils->debug_log('=========process player:'.$player['username'].', api: '.$player['typeId'].' total:'.count($players).' current:'.$cnt);
				if(in_array($player['typeId'],[BBIN_API, T1BBIN_API])){
					continue;
				}
				$api = $this->utils->loadExternalSystemLibObject($player['typeId']);
				$playerId = $player['playerId'];
				$playerName = $player['username'];

				$game_platform_id=$api->getPlatformCode();

				if ($api) {
					$isPlayerExist = $api->isPlayerExist($playerName);
					if ($isPlayerExist) {
						if($max_bal<=1){
							if($api->onlyTransferPositiveInteger()){
								//ignore positive integer only
								continue;
							}
						}

						$result = $api->queryPlayerBalance($playerName);
						if (isset($result['success']) && $result['success'] && isset($result['balance'])) {
							$balance = $result['balance'];

							//update with lock
							$api->updatePlayerSubwalletBalance($playerId, $balance);
							if($balance>=$min_bal && $balance<$max_bal){
								//only transfer balance >0

								$result = $this->utils->transferWallet($playerId, $playerName, $game_platform_id, Wallet_model::MAIN_WALLET_ID, $balance);
								if (isset($result['success']) && $result['success']) {
									$this->utils->debug_log('transfer '.$playerName.' from '.$api->getPlatformCode().' balance:'.$balance.' success');
								} else {
									$this->utils->error_log('transfer '.$playerName.' from '.$api->getPlatformCode().' balance:'.$balance.' failed');
								}

							}else{
								$this->utils->info_log('ignore wrong balance');
							}
						}else{
							$this->utils->error_log('query balance failed');
						}
					}
				}else{
					$this->utils->error_log('wrong api');
				}

			}
		}

	}

	public function clearCache() {
		echo $this->utils->deleteCache() ? 'Success' : 'Failed';
	}

	public function testLockBalance($username, $sleep = 10){
		$this->load->model(['player_model']);

		$playerId=$this->player_model->getPlayerIdByUsername($username);

		$success=$this->lockAndTransForPlayerBalance($playerId, function() use($playerId, $sleep){

			$success=true;

			$this->utils->debug_log('locked player:'.$playerId.' balance');
			sleep($sleep);

			return $success;
		});

		return $success;

	}

    /**
	 * Soft Delete Players by usernames specially Test Players
	 * @access	public
	 * @param	string separated by spaces
	 * @example  sudo ./command.sh softDeletePlayersByUsername  'username1 username2 username3'
	 */
    public function softDeletePlayersByUsername($player_usernames){

		$arr = array_map('trim', array_filter(explode(' ', $player_usernames)));
		$notExistPlayers = array();
		$existPlayers = array();

		foreach ($arr as $u) {
			$sql1= 'SELECT * FROM player  WHERE username = ?';
			$q1 = $this->db->query($sql1,array($u));

			if($q1->num_rows() > 0){
				$sql2 = 'UPDATE player SET deleted_at = NOW() WHERE username = ?';
				$this->db->query($sql2,array($u));
				array_push($existPlayers, $u);
			}else{
				array_push($notExistPlayers, $u);
			}
		}

		$this->utils->debug_log('Success to SoftDelete Usernames', $existPlayers);
		$this->utils->debug_log('Not Existed Usernames', $notExistPlayers);

	}

	/**
	* Soft Delete Players by player ids specially Test Players
    * @access	public
	* @param	string separated by spaces
    * @example  sudo ./command.sh softDeletePlayersByPlayerId  '12122 144l 112'
	*/
 	public function softDeletePlayersByPlayerId($player_ids){

		$arr = array_map('trim', array_filter(explode(' ', $player_ids)));

		$notExistPlayers = array();
		$existPlayers = array();

		foreach ($arr as $playerId) {
			if(is_numeric($playerId)){
				$sql1= 'SELECT * FROM player  WHERE playerId = ?';
				$q1 = $this->db->query($sql1,array($playerId));

				if($q1->num_rows() > 0){
					$sql2 = 'UPDATE player SET deleted_at = NOW() WHERE playerId = ?';
					$this->db->query($sql2,array($playerId));
					array_push($existPlayers, $playerId);
				}else{
					array_push($notExistPlayers, $playerId);
				}
			}
		}

		$this->utils->debug_log('Success to SoftDelete playerIds', $existPlayers);
		$this->utils->debug_log('Not Existed playerIds', $notExistPlayers);

	}

    /**
    * Revert Soft Delete Players by player ids specially Test Players
    * @access	public
    * @param	string separated by spaces
    * @example  sudo ./command.sh softDeletePlayersByPlayerId  '12122 144l 112'
    */
	public function revertSoftDeletePlayersByPlayerId($player_ids){
        $arr = array_map('trim', array_filter(explode(' ', $player_ids)));
        $notExistPlayers = array();
        $existPlayers = array();

        foreach ($arr as $playerId) {
            if (is_numeric($playerId)) {
                $sql1= 'SELECT * FROM player  WHERE playerId = ? AND deleted_at IS NOT NULL';
                $q1 = $this->db->query($sql1, array($playerId));

                if ($q1->num_rows() > 0) {
                    $sql2 = 'UPDATE player SET deleted_at = null WHERE playerId = ?';
                    $this->db->query($sql2, array($playerId));
                    array_push($existPlayers, $playerId);
                } else {
                    array_push($notExistPlayers, $playerId);
                }
            }
        }

        $this->utils->debug_log('Success to SoftDelete playerIds', $existPlayers);
        $this->utils->debug_log('Not Existed playerIds', $notExistPlayers);

	}

	/**
     * Revert Soft Delete Players by usernames specially Test Players
     * @access	public
     * @param	string separated by spaces
     * @example  sudo ./command.sh revertSoftDeletePlayersByPlayerUsername  'username1 username2 username3'
     */
	public function revertSoftDeletePlayersByUsername($player_usernames){
		$arr = array_map('trim', array_filter(explode(' ', $player_usernames)));
        $notExistPlayers = array();
        $existPlayers = array();

        foreach ($arr as $u) {
            $sql1= 'SELECT * FROM player  WHERE username = ? AND deleted_at IS NOT NULL';
            $q1 = $this->db->query($sql1, array($u));

            if ($q1->num_rows() > 0) {
                $sql2 = 'UPDATE player SET deleted_at = null WHERE username = ?';
                $this->db->query($sql2, array($u));
                array_push($existPlayers, $u);
            } else {
                array_push($notExistPlayers, $u);
            }
        }

        $this->utils->debug_log('Success to SoftDelete Usernames', $existPlayers);
        $this->utils->debug_log('Not Existed Usernames', $notExistPlayers);

	}

	public function removePlayerBankCardByPlayerid($player_ids = null){
		if (empty($player_ids)) {
            $this->utils->debug_log('empty input');
            return;
        }

		$arr = array_map('trim', array_filter(explode(' ', $player_ids)));
        $notExistPlayers = array();
        $existPlayers = array();
		$count_process = 0;

        $this->load->model(['wallet_model']);
		foreach ($arr as $player_id) {
			$success=$this->lockAndTransForRegistration($player_id,function () use($player_id) {
				$this->load->model(['playerbankdetails']);
				$this->db->where('playerId', $player_id);
				$this->db->set('playerbankdetails.bankAccountNumber', 'concat("del_", bankAccountNumber)', false);
				return $this->db->update('playerbankdetails', array(
					'playerbankdetails.deletedOn' => $this->utils->getNowForMysql(),
					'playerbankdetails.status' => playerbankdetails::STATUS_DELETED
				));
			});
			$count_process++;
		}
		if($success){
			array_push($existPlayers, $player_id);
		} else {
			array_push($notExistPlayers, $player_id);
		}
		$this->utils->debug_log('Success to SoftDelete Usernames', $existPlayers);
        $this->utils->debug_log('Not Existed Usernames', $notExistPlayers);
        $this->utils->debug_log('count items', "[ALL=> $count_process, SUCCESS=> ".count($existPlayers).", Not Existed=> ". count($notExistPlayers) ."]");

	}

	/**
	 * Soft Delete Affiliate by usernames
	 * @access public
	 * @param string $affiliate_usernames separated by spaces
	 * @param bool $ignore_has_players If it is true, the script will ignore the affiliate that owns the player.Otherwise, the player will be remove linked and the affiliate will be soft-deleted.
	 * @example  sudo ./command.sh softDeleteAffiliateByUsername  'username1 username2 username3' 1
	 */
    public function softDeleteAffiliateByUsername($affiliate_usernames = null, $ignore_has_players = false){
		if(empty($affiliate_usernames)) {
			$this->utils->debug_log('empty input');
			return;
		}
		$this->load->model(['affiliatemodel', 'player_model']);

		$arr_affiliate_usernames = array_map('trim', array_filter(explode(' ', $affiliate_usernames)));
		$notExistAff = array();
		$existAff = array();
		$alreadySoftDeletedAffList = array();
		$deleteFailedAffList = array();
		$ignoredAffListHasPlayer = array();
		$count_process = 0;

		foreach ($arr_affiliate_usernames as $username) {
			$affiliateId = $this->affiliatemodel->getAffiliateIdByUsername($username);

			if(!empty($affiliateId)){
				$this->utils->debug_log('Will Remove aff', $username);

				$is_players_under_aff = false;
				$players_under_aff = $this->affiliatemodel->getAllPlayersUnderAffiliate($affiliateId);
				if( ! empty($players_under_aff) ){
					$is_players_under_aff = true;
				}

				try{
					$add_prefix=true;
					$isLockFailed=false;
					$doExceptionPropagation=true;
					$success=$this->lockAndTransForAffiliateBalance($affiliateId // #1
						// $success=$this->lockAndTransForRegistration($affiliateId // #1
						, function () use($affiliateId, $username, $is_players_under_aff, $players_under_aff, $ignore_has_players, &$ignoredAffListHasPlayer, &$deleteFailedAffList, &$existAff, &$alreadySoftDeletedAffList) { // #2, $callbakcable
							$isToHide = null;
							if( $ignore_has_players && $is_players_under_aff){ // 清除時，要忽略有玩家的代理。 該代理有玩家。
								// NotHide
								$isToHide = false;
							}else if( $ignore_has_players && ! $is_players_under_aff ){// 清除時，要忽略有玩家的代理。 該代理沒有玩家。
								// ToHide
								$isToHide = true;
							}else if( ! $ignore_has_players && $is_players_under_aff){// 清除時，不要忽略有玩家的代理。 該代理有玩家。
								// ToHide
								$isToHide = true;
							}else if( ! $ignore_has_players && ! $is_players_under_aff){// 清除時，不要忽略有玩家的代理。 該代理沒有玩家。
								// ToHide
								$isToHide = true;
							}

							if($isToHide){
								// ToHide
								$sql2 = 'UPDATE affiliates SET deleted_at = NOW(), status = '. affiliatemodel::STATUS_DELETED .' WHERE username = ? and deleted_at is null';
								$q2 = $this->db->query($sql2,array($username));
								$affected_rows = $this->db->affected_rows();
								if($affected_rows) {
									//reomve linked player
									// $players_under_aff = $this->affiliatemodel->getAllPlayersUnderAffiliate($affiliateId);
									var_dump($players_under_aff);
									if( ! empty($players_under_aff) ){
										foreach ($players_under_aff as $player) {
											$player_id = $player['playerId'];
											$this->player_model->removeAffiliateId($player_id, null);
											$message = 'Remove affiliate when delete affiliate, player[%s]-[%s]';
											$this->utils->debug_log(sprintf($message, $player['playerId'],  $player["username"]));
											$this->player_model->savePlayerUpdateLog($player_id, "Remove affiliate when delete affiliate: [$username]", 'command');
										}
									}

									$this->affiliatemodel->updateAffdomain($affiliateId, null);
									$this->affiliatemodel->removeAllSourceCode($affiliateId);
									$this->affiliatemodel->removeAllAdditionalAffdomain($affiliateId);
									array_push($existAff, $username);
									$this->utils->debug_log('Done aff:', $username);
									return true; // aka. $success=true, will commit
								}else {
									$this->utils->debug_log('Already softDeleted aff:', $username);
									array_push($alreadySoftDeletedAffList, $username);
									return false;// aka. $success=false, will rollback
								}
							}else{
								// NotHide
								$this->utils->debug_log('Ignored, aff:', $username);
								array_push($ignoredAffListHasPlayer, $username);
								return false;// aka. $success=false, will rollback
							}
						}, $add_prefix // #3
						, $isLockFailed // #4
						, $doExceptionPropagation // #5
					); // EOF $success=$this->lockAndTransForRegistration(...

				}catch(Exception $e){
					$this->utils->debug_log('SoftDelete Failed aff:', $username);
					array_push($deleteFailedAffList, $username);
					$this->utils->error_log('got exception in softDeleteAffiliateByUsername', $e);
					$success=false;
				}

			}else{
				$this->utils->debug_log('Not exist, aff:', $username);
                array_push($notExistAff, $username);
            }

			$count_process++;
		} // EOF foreach ($arr_affiliate_usernames as $username) {...

		$this->utils->debug_log('Success to SoftDelete Usernames (existAff):', $existAff);
		$this->utils->debug_log('Not Existed Usernames (notExistAff):', $notExistAff);
		$this->utils->debug_log('Already SoftDeleted Usernames (alreadySoftDeletedAffList):', $alreadySoftDeletedAffList);
		$this->utils->debug_log('Delete Failed Aff List (deleteFailedAffList):', $deleteFailedAffList);
		$this->utils->debug_log('Ingore soft delete of owning player (ignoredAffListHasPlayer):', $ignoredAffListHasPlayer);
        $this->utils->debug_log('count items', "[ALL=> $count_process, SUCCESS=> ".count($existAff).", FAILED=> ".count($deleteFailedAffList).", Not Existed=> ". count($notExistAff) .", Ignored Has Player => ". count($ignoredAffListHasPlayer) ." ]");

	}// EOF softDeleteAffiliateByUsername

    public function revertSoftDeleteAffiliateByUsername($affiliate_usernames = null){
		if(empty($affiliate_usernames)) {
			$this->utils->debug_log('empty input');
			return;
		}
		$this->load->model(['affiliatemodel']);

		$arr_affiliate_usernames = array_map('trim', array_filter(explode(' ', $affiliate_usernames)));
		$notExistAff = array();
		$existAff = array();
		$count_process = 0;

		foreach ($arr_affiliate_usernames as $username) {
			$affiliateId = $this->affiliatemodel->getAffiliateIdByUsername($username);
			if(!empty($affiliateId)){
				$sql2 = 'UPDATE affiliates SET deleted_at = null, status = '. affiliatemodel::OLD_STATUS_INACTIVE .' WHERE username = ? and deleted_at is not null';
				$q2 = $this->db->query($sql2,array($username));
				array_push($existAff, $username);
			}else{
                array_push($notExistAff, $username);
            }
			$count_process++;
		}

		$this->utils->debug_log('Success to revertSoftDelete Usernames', $existAff);
		$this->utils->debug_log('Not Existed Usernames', $notExistAff);
        $this->utils->debug_log('count items', "[ALL=> $count_process, SUCCESS=> ".count($existAff).", Not Existed=> ". count($notExistAff) ."]");
	}



	public function decodeYungu(){

		require_once APPPATH.'/libraries/game_platform/game_api_yungu.php';

		$resultText='Q/dRdjyhm8y04eki38BLRsRmqgA/hD0ReQj6l+hSoi/p9EbgUBAhqVcmQijt2UOS';
		$api_key='524535077562d9f0';
        $outStr = YunguCryptAES::decrypt($resultText, $api_key);

        $this->utils->debug_log($outStr);
	}

	# This function will build up daily player settlement records
	public function generate_agency_daily_player_settlement($recent_record_only = 'true', $run_for_date = null) {
		set_time_limit(0); # this process could take hours
		$recent_record_only= $recent_record_only=='true';
		$this->utils->debug_log("Generating daily settlement records. Recent records only? [$recent_record_only]");
		$this->load->library('agency_library');
		$this->agency_library->generate_agency_daily_player_settlement($recent_record_only, $run_for_date);
		$this->agency_library->generate_agency_daily_agent_settlement($recent_record_only, $run_for_date);
		$this->utils->debug_log("Done generating daily settlement records. Recent records only? [$recent_record_only]");
	}

	# Calculates settlement data within longer date range.
	# This function will be called daily, only when data is small! Otherwise, use generate_agency_settlement_wl below
	public function generate_agency_settlement_daily() {
		$this->generate_agency_daily_player_settlement(false);
	}

	# This function will be called by cron jobs to build up daily player settlement records and calculate wl settlement
	# If called from commandline with agent name, it refreshes the given agent's wl settlement
	# based on existing daily settlement records
    public function generate_agency_settlement_wl($agent_name = NULL) {
		set_time_limit(0);

    	$this->load->model('agency_model');
    	$this->load->library('agency_library');

		if(empty($agent_name)) { # Call from cron job, run generate daily settlement
			$this->generate_agency_daily_player_settlement();
			$this->delete_phantom_agency_settlements();
		}

    	$all_agents =  array();
    	if ($agent_name) {
	    	$agent = $this->agency_model->get_agent_by_name($agent_name);
	    	if ($agent && isset($agent['agent_id'])) {
        		$all_agents[] = $agent;
	    	}
        } else {
            $all_agents = $this->agency_model->get_active_agents();
        }

        if ( ! empty($all_agents)) {
            foreach ($all_agents as $agent) {
            	$agent_name = $agent['agent_name'];
    			$this->utils->debug_log("Creating win/loss settlement for agent [$agent_name]");
                $this->agency_library->create_settlement_by_win_loss($agent['agent_id']);
    			$this->utils->debug_log("Done creating win/loss settlement for agent [$agent_name]");
            }
        }
        $this->utils->debug_log("generate_agency_settlement_wl: done");
    }

	public function delete_phantom_agency_settlements($recent_record_only = true) {
		$this->load->library('agency_library');
		$this->agency_library->delete_phantom_settlements($recent_record_only);
	}

	public function removeDuplicateBalanceHistory($startDate, $numDays = 1) {
		$recordDate = strtotime($startDate);
		$this->load->model('wallet_model');
		while($numDays > 0) {
			$recordDateSql = date('Y-m-d', $recordDate);
			# 24 hours of the date
			$hour = 23;
			while($hour >= 0) {
				$hourStr = sprintf('%02d', $hour);
				$this->wallet_model->removeDuplicateBalanceHistory(
					"$recordDateSql $hourStr:00:00", "$recordDateSql $hourStr:59:59");
				$hour--;
			}
			$recordDate = strtotime('+1 day', date($recordDate));
			$numDays--;
		}
	}

    public function delete_resp_day($start_day, $end_day, $dry_run='true'){

    	$this->load->dbforge();

    	$end=new DateTime($end_day);

    	$current=new DateTime($start_day);

    	$dry_run=$dry_run=='true';

    	while($current<=$end){

    		$table_name='resp_'.$current->format('Ymd');

    		$this->utils->debug_log('drop table '.$table_name);
    		if($dry_run){
    			//ignore
    		}else{
    			if($this->db->table_exists($table_name)){
					$this->dbforge->drop_table($table_name);
    			}
    		}

    		$table_name='resp_sync_'.$current->format('Ymd');

    		$this->utils->debug_log('drop table '.$table_name);
    		if($dry_run){
    			//ignore
    		}else{
    			if($this->db->table_exists($table_name)){
					$this->dbforge->drop_table($table_name);
    			}
    		}

    		$current->add(DateInterval::createFromDateString('1 day'));
    	}

    }

    public function clear_response_result($only_keep_days=7){

    	$dry_run='false';

    	//clear
    	// $sql="delete from response_results where created_at='0000-00-00 00:00:00'";
    	$this->db->delete('response_results', ['created_at'=>'0000-00-00 00:00:00']);

    	//from min to 30 days ago
    	$minDateTime=$this->response_result->getMinCreatedAt();
    	$start_date=new DateTime($minDateTime);

    	$end_date=new DateTime();
    	$end_date->sub(DateInterval::createFromDateString($only_keep_days.' days'));

    	//delete response_results
    	$table_name='response_results';
    	$date_field='created_at';
    	$start_day=$start_date->format('Y-m-d');
    	$end_day=$end_date->format('Y-m-d');
    	if($start_day<=$end_day){

    		$this->utils->debug_log('delete_day_by_day '.$table_name.' by '.$date_field.', '.$start_day.' to '.$end_day);

	    	$this->delete_day_by_day($table_name, $date_field, $start_day, $end_day, $dry_run);

	    	$this->delete_resp_day($start_day, $end_day, $dry_run);

    	}else{
    		$this->utils->debug_log('do not clear anything', $start_day, $end_day);
	    	//same day
	    	$start_day=$end_day;
    	}
	    $this->delete_resp_day($start_day, $end_day, $dry_run);

    }

    public function generateDailyCurrencyRate($date= null, $source = null){
    	$this->load->model('daily_currency');
        $isEnabledMDB=$this->utils->isEnabledMDB();
        if( $isEnabledMDB
            && is_null($source)
        ){ // under MDB, and in default
            $source = '__CURRENT_CURRENCY_KEY__';
        }
    	$this->daily_currency->generateDailyCurrencyRate($date, $source);
    }

    public function show_last_time_of_cashback(){
    	$this->load->model(['group_level']);
    	$this->utils->debug_log($this->group_level->getUpdateCashbackLastTime());
    }

    public function update_now_last_time_of_cashback(){
    	$this->load->model(['group_level']);

    	$this->group_level->updateCashbackLastTime($this->utils->getNowForMysql());

    	$this->show_last_time_of_cashback();
    }

    public function batchUpdatePlayerInfoByGamePlatform($platform){
    	$api = $this->utils->loadExternalSystemLibObject($platform);
    	if(!empty($api)){
    		$this->load->model('game_provider_auth');
	    	$players = $this->game_provider_auth->getPlayerListByPlatformCode($platform);
	    	$count = 0;

	    	if(!empty($players)){
	    		foreach ($players as $player) {
			   		$result = $api->updatePlayerInfo($player->username);
			   		$count++;
			   		$this->utils->error_log('result === :',$result);
	    		}
	    	}
	    	$this->utils->error_log('count :',$count);
    	}
    	$this->utils->error_log('load selected api failed');
    }

    /**
     * generate admin password
     *
     * @return array list of password
     */
    public function reset_admin_password(){

		$this->config->set_item('print_log_to_console', false);

    	$this->load->model(['users']);

    	echo $this->users->resetAdminPassword();

    }

    public function reset_superadmin_password(){

		$this->config->set_item('print_log_to_console', false);

    	$this->load->model(['users']);

    	echo $this->users->resetSuperAdminPassword();

    }

    /**
     * generate t1 password
     *
     * @return array list of password
     */
    public function generate_t1_password(){

    	$this->load->model(['roles']);

    	$random_password=true;
    	$pass_list=[];
    	$rlt=$this->roles->syncStandardRoles($random_password, $pass_list);

    	$this->utils->debug_log('result', $rlt, 'pass_list', $pass_list);
    }

     /**
     * regenerate t1 test password
     * @param	string or number -length of password
     * @param   sring $forceSetPlayerPassToDefault ,player usernames separated by space resets to its default password
     */
     public function regenerate_and_sync_t1_players_password($length=6,$forceSetPlayerPassToDefault='null'){
     	$this->load->model('player_model');
     	$this->load->library('salt');
     	$jsonFile=APPPATH.'config/standard_t1_players.json';
     	$json=file_get_contents($jsonFile);
     	$t1_players = $this->utils->decodeJson($json);
     	$new_player_pass['update_time']=$this->utils->getNowForMysql();
     	$new_player_pass['app_prefix'] = $this->_app_prefix;
     	$new_player_pass['players'] = [];
     	$game_platform_ids = $this->utils->getGameSystemMap();
     	$players_set_to_default = null;
     	if($forceSetPlayerPassToDefault != 'null'){
     		$players_set_to_default = explode(" ", $forceSetPlayerPassToDefault);
     	}

     	foreach ($t1_players as $group_title => $groups) {

     		$new_player_pass['players'][$group_title] =[];

     		foreach ($groups as $player) {
     			$password = $this->utils->generate_password_no_special_char($length);
     			$username = $player['username'];
     			$player_id = $this->player_model->getPlayerIdByUsername($username);
     			$hash = $this->salt->encrypt($password, $this->getDeskeyOG());
     			$is_passwd_changeable = $player['isPassChangeable'];
     			$new_player_cred = null;
     			if(!empty($player_id)){
    				//force set to default
     				if(!empty($players_set_to_default) && in_array($username, $players_set_to_default)){
     					$hash = $this->salt->encrypt($player['password'], $this->getDeskeyOG());
     					$new_player_cred = array('username'=> $username,'password'=> $player['password'], 'isPlayerExistInSbe'=> true, 'isPassChangeable' => $is_passwd_changeable);
     					$update_data = array('password' => $hash);
     					$this->player_model->resetPassword($player_id, $update_data);
	    			    	//sync password
     					foreach ($game_platform_ids  as $game_platform_id) {
	    					//void
     						$this->sync_password($game_platform_id, $username);
    						//sleep(1);
     					}
     				}else{
     					if($is_passwd_changeable){
     						$new_player_cred = array('username'=> $username,'password'=> $password, 'isPlayerExistInSbe'=> true, 'isPassChangeable' => $is_passwd_changeable);
     						$update_data = array('password' => $hash);
     						$this->player_model->resetPassword($player_id, $update_data);
	    			    	//sync password
     						foreach ($game_platform_ids  as $game_platform_id) {
	    					//void
     							$this->sync_password($game_platform_id, $username);
    						//sleep(1);
     						}
     					}else{
     						$new_player_cred = array('username'=> $username,'password'=> $player['password'],'isPlayerExistInSbe'=> true, 'isPassChangeable' => $is_passwd_changeable);
     					}
     				}
     			}else{
     				$new_player_cred = array('username'=> $username,'password'=> $player['password'],'isPlayerExistInSbe'=> false, 'isPassChangeable' => $is_passwd_changeable);
     			}
     			array_push($new_player_pass['players'][$group_title],$new_player_cred);
    		}//loop player
    	}//loop t1_players;

    	$players =  $new_player_pass['players'];
    	$client_tag = [
    		':closed_lock_with_key:',
    		'#'.str_replace("og_","",$new_player_pass['app_prefix']),
    		'#pass'. $this->utils->formatYearMonthForMysql(new DateTime)
    	];

    	foreach ($players as $key => $player_group) {
    		$data = [];
    		$data['group'] = $key;
    		$data['players']=[];
    		foreach ($player_group as $player) {
    			$arr = [];
    			$arr['username'] = $player['username'];
    			$arr['password'] = $player['password'];
    			array_push($data['players'], $arr);
    		}
    		$msg = "Update: ".$new_player_pass['update_time']." | Hostname: ". $this->utils->getHostname();
    		$msg .= "\n";
    		$msg .= "``` json \n".json_encode($data)." \n```";
              // echo $msg;
    		$this->utils->debug_log($key,$msg,'tag', $client_tag);
    		$this->sendNotificationToMattermost($key, $key, $msg, 'info', $client_tag);
    		sleep(1);
    	}
    	$msg2 = "Update: ".$new_player_pass['update_time']." | Hostname: ". $this->utils->getHostname();
    	$msg2 .= "\n";
    	$msg2 .= "``` json \n".json_encode($new_player_pass, JSON_PRETTY_PRINT)." \n```";
    	$this->utils->debug_log('t1_players_master',$msg2,'tag', $client_tag);
    	$this->sendNotificationToMattermost('t1_players_master', 't1_players_master', $msg2, 'info', $client_tag);
    	// echo $msg2;
    }

    public function update_walletaccount_bankdetails() {
    	$this->load->model('wallet_model');
    	$result = $this->wallet_model->updateWalletaccountBankdetails();
    }

    public function update_player_online_status()
    {
    	$this->load->model(['player_model']);
        $this->player_model->syncPlayerOnlineStatus();
    }

    //copy from twinbet branch
	//03-08-2018 | OGP-5331
	//Super Report | super_report
	public function sendCashbackReport($date = NULL) {
		$url = $this->utils->getConfig('super_report_receiver');
    	if(empty($url)){
    		echo "super_report_receiver config is empty or not setup";
    	} else {
	        $this->load->model('super_report');
	        //$this->load->library(array('user_functions','http_utils'));

	        //$this->http_utils->set_postfields_method('json_encode');
	        //$this->http_utils->set_headers(['Content-Type:application/json']);

	        $currency = $this->utils->getCurrentCurrency()['currency_code'];
	        $rows = $this->super_report->getCashbackReportData($date);

	        if ( ! empty($rows)) {

		        foreach ($rows as &$row) {
		            $row['currency'] = $currency['currencyCode'];
		            $row['backoffice_id'] = $currency['currencyCode'];
		            $row['paid_date'] = $row['paid_date'] ? : '0000-00-00'; # TODO: ALLOW NULL
		        }

	        }

	        $postdata = array("data" => $rows);
	        $url = $url . 'super_cashback_report';
	        $response = $this->utils->simpleSubmitPostForm($url, $postdata);

	        //old Code
	        //$response = $this->http_utils->curl($url . 'super_cashback_report', array('data' => $rows));

	        echo "super_cashback_report: " . count($rows) . "\n";

	        $response_data = json_decode($response, TRUE);

	        if ($response_data && $response_data['success']) {

	        } else {
	        	echo $response;
	        }
    	}
    }

    public function sendSummaryReport($date = NULL) {
    	$url = $this->utils->getConfig('super_report_receiver');
    	if(empty($url)){
    		echo "super_report_receiver config is empty or not setup";
    	} else {
	        $this->load->model('super_report');
	        //$this->load->library(array('http_utils'));

	        //$this->http_utils->set_postfields_method('json_encode');
	        //$this->http_utils->set_headers(['Content-Type:application/json']);

	        $currency = $this->utils->getCurrentCurrency()['currency_code'];
	        $row = $this->super_report->getSummaryReportData($date);

	        $row['currency'] = $currency;
	        $row['backoffice_id'] = $currency;
	        //echo "<pre>";print_r($row);echo "<br>";
	        $postdata = array("data" => [$row]);
	        $url = $url . 'super_summary_report';

	        $response = $this->utils->simpleSubmitPostForm($url, $postdata);

	        //old Code
	        //$response = $this->http_utils->curl($url . 'super_summary_report', array('data' => [$row]));

	        echo "super_summary_report[{$date}]: 1\n";

	        $response_data = json_decode($response, TRUE);

	        if ($response_data && $response_data['success']) {

	        } else {
	        	echo $response;
	        }
    	}
    }

    public function sendPlayerReport($date = NULL) {
    	$url = $this->utils->getConfig('super_report_receiver');
    	if(empty($url)){
    		echo "super_report_receiver config is empty or not setup";
    	} else {
	        $this->load->model('super_report');
	        //$this->load->library(array('user_functions','http_utils'));

	        //$this->http_utils->set_postfields_method('json_encode');
	        //$this->http_utils->set_headers(['Content-Type:application/json']);

	        $currency = $this->utils->getCurrentCurrency()['currency_code'];
	        $rows = $this->super_report->getPlayerReportData($date);

	        if ( ! empty($rows)) {

	            foreach ($rows as &$row) {
	                $row['currency'] = $currency;
	                $row['backoffice_id'] = $currency;
	            }

	        }

	        $postdata = array("data" => $rows);
	        $url = $url . 'super_player_report';
	        $response = $this->utils->simpleSubmitPostForm($url, $postdata);

	        //old Code
	        //$response = $this->http_utils->curl($url . 'super_player_report', array('data' => $rows));

	        echo "super_player_report[{$date}]: " . count($rows) . "\n";

	        $response_data = json_decode($response, TRUE);

	        if ($response_data && $response_data['success']) {

	        } else {
	        	echo $response;
	        }
    	}
    }

    public function sendGameReport($date = NULL, $hour = NULL) {
    	$url = $this->utils->getConfig('super_report_receiver');
    	if(empty($url)){
    		echo "super_report_receiver config is empty or not setup";
    	} else {
	        $this->load->model('super_report');
	        //$this->load->library(array('user_functions','http_utils'));

	        //$this->http_utils->set_postfields_method('json_encode');
	        //$this->http_utils->set_headers(['Content-Type:application/json']);

	        $currency = $this->utils->getCurrentCurrency()['currency_code'];
	        $rows = $this->super_report->getGameReportData($date, $hour);
	        if ( ! empty($rows)) {

	            foreach ($rows as &$row) {
	                $row['currency'] = $currency;
	                $row['backoffice_id'] = $currency;
	            }
	        }

	        $postdata = array("data" => $rows);
	        $url = $url . 'super_game_report';
	        $response = $this->utils->simpleSubmitPostForm($url, $postdata);

	        //old code
	        //$response = $this->http_utils->curl($url . 'super_game_report', array('data' => $rows));

	        echo "super_game_report[{$date} {$hour}:00:00]: " . count($rows) . "\n";

	        $response_data = json_decode($response, TRUE);

	        if ($response_data && $response_data['success']) {

	        } else {
	        	echo $response;
	        }
    	}
    }

    public function sendPaymentReport($date = NULL) {
    	$url = $this->utils->getConfig('super_report_receiver');
    	if(empty($url)){
    		echo "super_report_receiver config is empty or not setup";
    	} else {
    		$this->load->model('super_report');
	        //$this->load->library(array('user_functions','http_utils'));

	        //$this->http_utils->set_postfields_method('json_encode');
	        //$this->http_utils->set_headers(['Content-Type:application/json']);

	        $currency = $this->utils->getCurrentCurrency()['currency_code'];
	        $rows = $this->super_report->getPaymentReportData($date);
	        if ( ! empty($rows)) {

	            foreach ($rows as &$row) {
	                $row['currency'] = $currency;
	                $row['backoffice_id'] = $currency;
	            }
	        }

	        $postdata = array("data" => $rows);
	        $url = $url . 'super_payment_report';
	        $response = $this->utils->simpleSubmitPostForm($url, $postdata);

	        //old Code
	        //$response = $this->http_utils->curl($url . 'super_payment_report', array('data' => $rows));

	        echo "super_payment_report[{$date}]: " . count($rows) . "\n";

	        $response_data = json_decode($response, TRUE);

	        if ($response_data && $response_data['success']) {

	        } else {
	        	echo $response;
	        }
    	}


    }

    public function sendPromotionReport($date = NULL) {
    	$url = $this->utils->getConfig('super_report_receiver');
    	if(empty($url)){
    		echo "super_report_receiver config is empty or not setup";
    	} else {
	        $this->load->model('super_report');
	        /*$this->load->library(array('user_functions','http_utils'));

	        $this->http_utils->set_postfields_method('json_encode');
	        $this->http_utils->set_headers(['Content-Type:application/json']);*/


	        $currency = $this->utils->getCurrentCurrency()['currency_code'];
	        $rows = $this->super_report->getPromotionReportData($date);
	        if ( ! empty($rows)) {

	            foreach ($rows as &$row) {
	                $row['currency'] = $currency;
	                $row['backoffice_id'] = $currency;
	            }
	        }

	        $postdata = array("data" => $rows);
	        $url = $url . 'super_promotion_report';
	        $response = $this->utils->simpleSubmitPostForm($url, $postdata);

	        //old Code
	        //$response = $this->http_utils->curl($url . 'super_promotion_report', array('data' => $rows));

	        echo "super_promotion_report[{$date}]: " . count($rows) . "\n";

	        $response_data = json_decode($response, TRUE);

	        if ($response_data && $response_data['success']) {

	        } else {
	        	echo $response;
	        }
    	}
    }

    public function updateSuperReportDaily($date = NULL) {
    	if ( ! $date) {
    		$date = date('Y-m-d', strtotime('-1 day'));
    	}
	    $this->sendSummaryReport($date);
		$this->sendPlayerReport($date);
	    $this->sendCashbackReport($date);
	    $this->sendPaymentReport($date);
	    $this->sendPromotionReport($date);
    }

    public function updateSuperReportHourly($date = NULL, $hour = NULL) {

    	if ( ! $date) {
    		$date = date('Y-m-d', strtotime('-1 hour'));
    	}

    	if ($hour === NULL) {
    		$hour = date('H', strtotime('-1 hour'));
    	}

	    $this->sendGameReport($date, $hour);
	    $this->sendSummaryReport($date);
		$this->sendPlayerReport($date);
	    $this->sendCashbackReport($date);
	    $this->sendPaymentReport($date);
	    $this->sendPromotionReport($date);
    }

    public function updateSuperReportByDateRange($start_date, $end_date) {
    	$date = $start_date;
    	do {
			$this->updateSuperReportDaily($date);
			for ($hour = 0; $hour < 24; $hour++) {
				$this->updateSuperReportHourly($date, $hour);
			}
			$date = date('Y-m-d', strtotime($date . ' +1day'));
    	} while ($date <= $end_date);
    }

    public function getAllActiveGameApiIds(){
    	$result = $this->utils->getGameSystemMap();
    	echo implode(array_keys($result), " " );
    }

    public function sendNotificationToMattermost($user,$channel,$message,$notifType,$texts_and_tags=null){
    	$this->load->helper('mattermost_notification_helper');

    	$notif_message = array(
    		array(
    			'text' => $message,
    			'type' => $notifType
    		)
    	);
    	sendNotificationToMattermost($user, $channel, $notif_message, $texts_and_tags);
    }


	public function createMinifyFile($project)
	{
	    return false;
		$this->load->library('minify/lib_minify');
		$this->lib_minify->minify($project);
	}

	public function calculateReferralCashback($dateTimeStr = null, $playerId = null) {

		if ( ( ! $this->utils->isEnabledFeature('enable_friend_referral_cashback') )|| true){
			$this->utils->debug_log("cannot calc referral, should enable feature first");
			return;
		}

		// try test for one player
		// to check config //$config['allowed_player_for_referral_cashback'] = ['test002','testzai01'];
		if (empty($playerId)) {
		#	$this->utils->debug_log('cannot proccess empty player id');
		#	return;
		}

		$currentDateTime = new DateTime();
		if (!empty($dateTimeStr)) {
			$currentDateTime = new DateTime($dateTimeStr);
		}

		$currentDate = $this->utils->formatDateForMysql($currentDateTime);
		$currentServertime = $this->utils->formatDateTimeForMysql($currentDateTime);

		$this->load->model(array('group_level'));

		$cashBackSettings = $this->group_level->getCashbackSettings();
		$this->utils->debug_log('cashBackSettings', $cashBackSettings);

		$this->startTrans();

		$calcReferralResult = $this->group_level->totalCashbackDailyFriendReferralBySettings($cashBackSettings, $currentDate, $playerId);

		$payDateTime = $currentDate . ' ' . $cashBackSettings->payTimeHour . ':00';
		$this->utils->debug_log('currentServertime', $currentServertime, 'payDateTime', $payDateTime);

		$payResult = 'ignore pay';
		if ($this->endTransWithSucc()) {
			$this->utils->debug_log('referral cashback is success', 'calcResult', $calcReferralResult, 'payResult', $payResult);
		} else {
			$this->utils->debug_log('referral cashback is failed');
		}
	}

	public function parsePngJsonFileManually($fromdate=null,$todate=null){
		$begin = new DateTime($fromdate);
		$end   = new DateTime($todate);
		if(!empty($fromdate) && !empty($todate) ){
			$api = $this->utils->loadExternalSystemLibObject(PNG_API);
			for($i = $begin; $i <= $end; $i->modify('+1 day')){
				$pngManualFilePath = '/var/game_platform/png/png_manual_parse/'.$i->format("Y-m-d");
				if (is_dir($pngManualFilePath)) {
					$png_json_files = array_diff(scandir($pngManualFilePath), array('..', '.'));
					foreach ($png_json_files as $json) {
						$jsonRecordsStr = file_get_contents($pngManualFilePath.'/'.$json, true);
						$this->utils->debug_log('PNG manual parsed file ',$pngManualFilePath.'/'.$json);
						$api->syncLDF(json_decode($jsonRecordsStr, true), 'web');
					}
				}
			}
		}
	}

	public function batch_scan_suspicious_transfer_request_cronjob($type='last_1hour'){

		//one hour
		set_time_limit(3600);

		$from=null;
		if($type=='last_4hours'){
			$from=$this->utils->formatDateTimeForMysql(new DateTime('-4 hours'));
		}elseif($type=='last_6hours'){
			$from=$this->utils->formatDateTimeForMysql(new DateTime('-6 hours'));
		}elseif($type=='last_8hours'){
			$from=$this->utils->formatDateTimeForMysql(new DateTime('-8 hours'));
		}else{
			//default is last hour
			$from=$this->utils->formatDateTimeForMysql(new DateTime('-1 hour'));
		}

		$this->batch_scan_suspicious_transfer_request($from);
	}

	/**
	 *
	 * batch_scan_suspicious_transfer_request, should be service
	 *
	 * search all deposit and status is success and transfer status is unknown or declined
	 * withdraw and status is failed and transfer status is unknown or approved
	 *
	 * @param string $from from date time
	 * @param string $to to date time
	 *
	 */
	public function batch_scan_suspicious_transfer_request($from=null, $to=null){

		// $from='2017-01-01 00:00:00';
		// $to='2018-04-01 00:00:00';

		if(empty($to)){
			$to=$this->utils->getNowForMysql();
		}
		if(empty($from)){
			//last 24 hours
			$from=$this->utils->formatDateTimeForMysql((new DateTime($to))->modify('-24 hours'));
		}

		$this->utils->debug_log('batch_scan_suspicious_transfer_request', $from, $to);

		$this->load->model(['wallet_model']);
		$this->wallet_model->searchSuspiciousTransferRequest($from , $to);

		$this->utils->debug_log('getAllSuspiciousBy', $from, $to, $this->wallet_model->getAllSuspiciousBy($from, $to));

	}

	/**
	 *
	 * Copy or clone tables exactly to remote table by cronjob
	 * @param string $url_sync_key - url -check config sync_to_remote_batch_tables
	 * @param string $interval - hourly, daily, weekly
	 *
	 */
	public function sync_remote_tables($url_sync_key,$interval){
		$remote_sync_url_config = $this->utils->getConfig('sync_to_remote_batch_tables');
		if(!array_key_exists($url_sync_key, $remote_sync_url_config )){
			$this->utils->debug_log('sync_remote_tables - url_sync_key not exist!');
			return;
		}
		$chosen_remote_sync_url = $remote_sync_url_config[$url_sync_key];
		if(!empty($chosen_remote_sync_url)){
			$sync_tables = $chosen_remote_sync_url[$interval];
			switch ($interval) {
				case 'hourly':
				$from = new DateTime('-1 hour');
				$to = new DateTime();
				$start_datetime = $this->utils->formatDateTimeForMysql($from->modify('-10 minutes'));
				$end_datetime = $this->utils->formatDateTimeForMysql($to->modify('+10 minutes'));
				break;
				case 'daily':
				$from = new DateTime('-1 day');
				$start_datetime = $this->utils->formatDateTimeForMysql($from->modify('-1 hour'));
				$end_datetime = $this->utils->formatDateTimeForMysql(new DateTime());
				break;
				case 'weekly':
				$start_datetime = $this->utils->formatDateTimeForMysql(new DateTime('-1 week'));
				$end_datetime = $this->utils->formatDateTimeForMysql(new DateTime());
				break;
				default:
    			# code...
				break;
			}
			foreach ($sync_tables as $key => $value) {
				$table_name = $key;
				$unique_id_field =  isset($value['unique_id_field']) ? $value['unique_id_field'] : 'NULL';
				$format_date_by =  isset($value['format_date_by']) ? $value['format_date_by'] : 'date_time';
				if($value['sync_by'] == 'date'){
					$this->sync_remote_table_by_date($url_sync_key, $table_name, $value['date_field'], $value['id_field'], $unique_id_field, $start_datetime ,$end_datetime, $format_date_by);
				}else{
					$this->sync_remote_table_by_id($url_sync_key, $table_name, $value['id_field'], $unique_id_field,'NULL','NULL',  $value['row_per_page']);
				}
				$this->utils->debug_log('tables------', $table_name , $value);
			}
		}else{
			$this->utils->debug_log('$remote_sync_url_config is empty ,please configure settings');
			return;
		}
	}


	/**
	 *
	 * Copy or clone tables exactly to remote manually by date (NOTE:will delete remote rows if not in range by this function)
	 * @param string $url_sync_key - url -check config sync_to_remote_batch_tables
	 * @param string $table_name - the table you want to sync, must have date
	 * @param string $date_field - the date_field of the table as the basis
	 * @param string $id_field - the id field of  table
	 * @param string $unique_id_field - unique - to prevent duplicate error
	 * @param string $start_datetime- datetime
	 * @param string $end_datetime- datetime
	 * @param string $format_date_by - date_time (default) | date_minute | date_hour | date | year_month
	 * @param string $step_time_unit- hour | minutes unit time will be used
	 * @param in $step_interval - number will be add upon looping related to step_time_unit
	 * @param string $dry_run if only test or not
	 */
	public function sync_remote_table_by_date($url_sync_key, $table_name, $date_field, $id_field, $unique_id_field='NULL',
		$start_datetime='NULL', $end_datetime='NULL', $format_date_by = 'date_time', $step_time_unit='hour', $step_interval='1', $dry_run='false'){

		$remote_sync_url_config = $this->utils->getConfig('sync_table_receiver_by_date');
		if(!array_key_exists($url_sync_key, $remote_sync_url_config )){
			$this->utils->debug_log('url_sync_key not exist!');
			return;
		}
		$start_datetime = ($start_datetime == 'NULL') ? null : $start_datetime;
		$end_datetime = ($end_datetime == 'NULL') ? null : $end_datetime;
		if(empty($start_datetime) || empty($end_datetime)){
			$this->utils->debug_log('Please provide some date!');
			return;
		}
		$url = $remote_sync_url_config[$url_sync_key];
		$end=new DateTime($end_datetime);
		$start=new DateTime($start_datetime);
		$step = null;
		switch ($step_time_unit) {
			case 'minute':
			$step = '+'.$step_interval.' minutes';
			break;
			case 'day':
			$step = '+'.$step_interval.' days';
			break;
			default:
			$step = '+'.$step_interval.' hours';
			break;
		}
		$dry_run=$dry_run=='true';
		$unique_id_field = ($unique_id_field == 'NULL') ? null : $unique_id_field ;
		$format_date_by  = ($format_date_by  == 'NULL') ? null : $format_date_by;

		if(!empty($unique_id_field)){
			$url = $url.$table_name.'/'.$id_field.'/'.$date_field.'/'.$unique_id_field;
		}else{
			$url = $url.$table_name.'/'.$id_field.'/'.$date_field ;
		}
		$this->utils->debug_log('sync_remote_table_by_date url',$url);
		//return ;
		$this->utils->loopDateTimeStartEnd($start, $end, $step, function($from, $to, $step)
			use($table_name, $id_field, $date_field, $url, $format_date_by, $dry_run){
				$response=null;
				$from_str = null;
				$to_str = null;
				switch ($format_date_by) {
					case 'date_hour':
					$from_str = $this->utils->formatDateHourForMysql($from);
					$to_str = $this->utils->formatDateHourForMysql($to);
					break;
					case 'date_minute':
					$from_str = $this->utils->formatDateMinuteForMysql($from);
					$to_str = $this->utils->formatDateMinuteForMysql($to);
					break;
					case 'date':
					$from_str = $this->utils->formatDateForMysql($from);
					$to_str = $this->utils->formatDateForMysql($to);
					break;
					case 'year_month':
					$from_str = $this->utils->formatYearMonthForMysql($from);
					$to_str = $this->utils->formatYearMonthForMysql($to);
					break;
					// case 'year':
					// $from_str = $this->utils->formatYearForMysql($from);
					// $to_str = $this->utils->formatYearForMysql($to);
					// break;
					default://date_time
					$from_str = $this->utils->formatDateTimeForMysql($from);
					$to_str = $this->utils->formatDateTimeForMysql($to);
					break;
				}
				$sql='SELECT * FROM `'.$table_name.'` WHERE `'.$date_field.'` >= "'.$from_str.'" and `'.$date_field.'` <= "'.$to_str.'" ';
				$rows=0;
				if($dry_run){
    			//ignore
				}else{
					$query = $this->db->query($sql);
					$rows = $query->result_array();

					if ( ! empty($rows)) {
						foreach ($rows as &$row) {
							$row['table_date_sync_from'] = $from_str;
							$row['table_date_sync_to'] = $to_str;
						}
					}
					if(!empty($rows)){
						$postdata = array("data" => $rows);
						$response = $this->utils->simpleSubmitPostForm($url,$postdata);
						$response_data = json_decode($response, TRUE);
						if ($response_data && $response_data['success']) {
							echo $response;
						}
					}
				}
				$this->utils->debug_log($sql, $from, $to, $step, $response);
				return true;
			});
	}

	/**
	 * Best use for table without date field and short row table
	 * Copy or clone tables exactly to remote manually by id (NOTE:will delete remote rows if not in range by this function)
	 * @param string $url_sync_key - url -check config sync_to_remote_batch_tables
	 * @param string $table_name - the table you want to sync, must have date
	 * @param string $id_field - the id field of  table
	 * @param string unique_id_field - unique - to prevent duplicate error
	 * @param string $from_id - start id
	 * @param string $from_id - to id
	 * @param string $step - number of rows to be posted
	 * @param string $dry_run if only test or not
	 */
	public function sync_remote_table_by_id($url_sync_key, $table_name, $id_field, $unique_id_field='NULL', $from_id ='NULL', $to_id = 'NULL',  $step = 30, $dry_run='false'){
		$remote_sync_url_config = $this->utils->getConfig('sync_table_receiver_by_id');
		if(!array_key_exists($url_sync_key, $remote_sync_url_config )){
			$this->utils->debug_log('$url_sync_key not exist!');
			return;
		}
		$url = $remote_sync_url_config[$url_sync_key];
		$from_id = ($from_id == 'NULL') ? null : $from_id;
		$to_id = ($to_id == 'NULL') ? null : $to_id;
		// $sql1='SELECT COUNT(`'.$id_field.'`) as id_count FROM `'.$table_name.'` WHERE `'.$id_field."`>=".$from_id.' AND `'.$id_field."`<=".$to_id.'';
		$sql1 = <<<EOF
		SELECT COUNT(`$id_field`) as id_count FROM `$table_name` WHERE `$id_field`>="$from_id" AND `$id_field`<= $to_id
EOF;
		$sql1 = trim($sql1);

		if(empty($from_id) || empty($to_id)){
			$sql1='SELECT COUNT(`'.$id_field.'`) as id_count FROM `'.$table_name.'`';
		}
		$query = $this->db->query($sql1);
		$this->utils->debug_log('sql1', $sql1);
		$id_count = $query->row()->id_count;
		$offset = 0;
		$limit =  $step;
		$dry_run=$dry_run=='true';
		$min_id = 0;
		$max_id = 0;
		$unique_id_field = ($unique_id_field == 'NULL') ? null : $unique_id_field ;
		if(!empty($unique_id_field)){
			$url = $url.$table_name.'/'.$id_field.'/'.$unique_id_field;
		}else{
			$url = $url.$table_name.'/'.$id_field;
		}
		$this->utils->debug_log('sync_remote_table_by_id url',$url);

		//if no id range  provided, this is for deleting not included id on remote server
		if(empty($from_id) || empty($to_id)){
			$sql3='SELECT min(`'.$id_field.'`) as min_id, max(`'.$id_field.'`) as max_id  FROM `'.$table_name.'`';
			$query3 = $this->db->query($sql3);
			$min_id = $query3->row()->min_id;
			$max_id = $query3->row()->max_id;
			$this->utils->debug_log('sql3', $sql3, $min_id, $max_id);
		}
       //get per page and post
		while ($offset < $id_count) {
			//sample
			// SELECT * FROM `player` LIMIT 25 OFFSET 0
			// SELECT * FROM `player` LIMIT 25 OFFSET 25
			// SELECT * FROM `player` LIMIT 25 OFFSET 50
			$sql2='SELECT *  FROM `'.$table_name.'` WHERE `'.$id_field.'`>='.$from_id.' AND `'.$id_field.'`<='.$to_id.'  LIMIT '.$limit.'  OFFSET '.$offset.' ';
			if(empty($from_id) || empty($to_id)){
				$sql2='SELECT *  FROM `'.$table_name.'`  LIMIT '.$limit.'  OFFSET '.$offset.' ';
			}

			if($dry_run){
				//ignore
			}else{
				$query2 = $this->db->query($sql2);
				$rows = $query2->result_array();
				$limit = $step;
				$offset = $offset + $step;
				if (!empty($rows)) {
					foreach ($rows as &$row) {
					// for range ids
						$row['table_id_sync_from'] = $from_id;
						$row['table_id_sync_to'] = $to_id;
					// for all ids
						$row['table_min_id_'] = $min_id;
						$row['table_max_id_'] = $max_id;
					}
					$postdata = array("data" => $rows);
				//$this->utils->debug_log('URL-------------------',$url,$rows);
					$response = $this->utils->simpleSubmitPostForm($url,$postdata);
					$response_data = json_decode($response, TRUE);
					if ($response_data && $response_data['success']) {
						echo $response;
					}
				}
			}
		}
	}


	public function batch_update_bet_from_withdraw_condition($date_time_str = null, $player_id = null) {
		$this->load->model(array('withdraw_condition'));

		$current_date_time = new DateTime();
		if (!empty($date_time_str)) {
			$current_date_time = new DateTime($date_time_str);
		}

		$current_date = $this->utils->formatDateForMysql($current_date_time);
		$yesterday_date = $this->utils->getLastDay($current_date);

		// get whole day yesterday
		$start_date = $yesterday_date .' ' . '00:00:00';
		$end_date = $yesterday_date .' ' .  '23:59:59';

		$this->utils->debug_log('batch update from yesterday wc', $start_date, $end_date);

		return $this->withdraw_condition->getPlayerCancelledAndFinishedCondition($player_id, $start_date, $end_date);
	}

	/// update current bet for all. active and finished
	public function batch_update_bet_from_withdraw_condition_hourly($date_time_str = null, $player_id = null) {
		$this->load->model(array('withdraw_condition'));

		$current_date_time = new DateTime();
		if (!empty($date_time_str)) {
			$current_date_time = new DateTime($date_time_str);
		}

		$current_date = $this->utils->formatDateForMysql($current_date_time);

		$start_date = $current_date .' ' . '00:00:00';
		$end_date = $this->utils->getNowForMysql();

		$this->utils->debug_log('batch update wc now', $start_date, $end_date);

		$hourly_update = true;
		return $this->withdraw_condition->getPlayerCancelledAndFinishedCondition($player_id, $start_date, $end_date, $hourly_update);
	}

    public function generateAffiliateStatistics($date_time_str = null, $aff_userName = null){
        $this->load->model('affiliate_statistics_model');

        $current_date_time = new DateTime();
        if (!empty($date_time_str)) {
            $current_date_time = new DateTime($date_time_str);
        }

        $report_date = $this->utils->formatDateForMysql($current_date_time);
        $start_date = $report_date .' ' . '00:00:00';
        $end_date = $report_date .' ' .  '23:59:59';

        #Regenerate yesterday’s report before six o’clock
        $nowDateTime = $this->utils->getNowForMysql();
        $sixoclock = $report_date .' ' .  '06:00:00';

        if (strtotime($nowDateTime) <= strtotime($sixoclock)) {
			$yesterday = date('Y-m-d', strtotime('-1 day',strtotime($report_date)));
			$yesterday_start_date = $yesterday .' ' . '00:00:00';
			$yesterday_end_date = $yesterday .' ' . '23:59:59';
			$this->utils->debug_log('run yesterday report generateAffiliateStatistics', $yesterday_start_date, $yesterday_end_date, $aff_userName);
			$this->affiliate_statistics_model->generateStatistics($yesterday_start_date, $yesterday_end_date, $aff_userName);
        }
        return $this->affiliate_statistics_model->generateStatistics($start_date, $end_date, $aff_userName);
    }

	//it runs last hour data
    public function generateAffiliateStatisticsHourly($date_time_str = null, $aff_userName = null){
        $this->load->model('affiliate_statistics_model');

        $current_date_time = new DateTime();
        if (!empty($date_time_str)) {
            $current_date_time = new DateTime($date_time_str);
        }

		$current_date_time->modify('-1 hour');

        $start_date = $current_date_time->format('Y-m-d 00:00:00');
        $end_date = $current_date_time->format('Y-m-d H:59:59');

        return $this->affiliate_statistics_model->generateStatistics($start_date, $end_date, $aff_userName);
    }

	public function clear_timeout_common_tokens(){

		$dt=new DateTime();
		$dt->sub(new DateInterval('PT60M'));

		$this->load->model(['common_token']);
		$success=$this->common_token->deleteTimeoutTokens($dt);

		$this->utils->printLastSQL();

		$this->utils->debug_log('clear_common_tokens', $success);

		return $success;
	}

	public function process_execute_time_log($filename){

		$filename='/home/vagrant/Code/'.$filename;

    	if(!file_exists($filename)){
    		$this->utils->error_log('file does not exist', $filename);
    		return false;
    	}

		$file = fopen($filename, "r");
		if ($file !== false) {
			try{

				while ( ($tmpData = fgets($file)) !== false) {
					$json=$this->utils->decodeJson($tmpData);
					$t=$json['context']['elapsed'];
					$this->utils->debug_log('t', $t, 'request id', $json['extra']['tags']['request_id']);
				}

			} finally {
				fclose($file);
			}
		}else{
    		$this->utils->error_log('open file failed', $filename);
		}

		$this->utils->debug_log('done');
	}


	/**
	 * Saves player records with btag code in csv format for tracking to uploads folder.
	 * The date range can be set manually, but by default it set to yesterday
	 *
	 * @param  string/date $from From date
	 * @param  string/date $to   To date
	 * @return boolean Export status
	 * @author Cholo Miguel Antonio
	 */
	public function upload_daily_income_access_report($from = null, $to = null) {

		if(!$this->utils->isEnabledFeature('enable_income_access')){
			$this->utils->debug_log('---------> Income Access is not enabled under System Features. Command will not start..');
			return false;
		}

		//-- include SFTP library
		set_include_path(APPPATH.'libraries/unencrypt/phpseclib');
		include('Net/SFTP.php');

		//-- initialize variables
		$orig_from_date = $from;
		$from 	= $from ? urldecode($from) : date('Y-m-d 00:00:00', strtotime(date('Y-m-d 00:00:00').' - 1 days'));
		$to 	= $to ? urldecode($to) : date('Y-m-d 23:59:59', strtotime(date('Y-m-d 23:59:59').' - 1 days'));
		$from 	= date('Y-m-d 00:00:00', strtotime($from));
		$to 	= date('Y-m-d 23:59:59', strtotime($to));

		// -- Check cashback pay hour
		$this->load->model('group_level');
		$cashbackSettings = $this->group_level->getCashbackSettings();

		$currentDateTime = new DateTime();
		if (!empty($orig_from_date)) {
			$currentDateTime = new DateTime($from);
		}

		$currentDate = $this->utils->formatDateForMysql($currentDateTime);

		$payDateTime = $currentDate . ' ' . $cashbackSettings->payTimeHour . ':00';
		$valid_time = date('Y-m-d H:i:s', strtotime($payDateTime.' + 1 hours'));

		if(strtotime($valid_time) > strtotime(date('Y-m-d H:i:s'))){
			$this->utils->debug_log('------------ Aborting... Cashback has not yet been paid to the players. Current time: '.date('Y-m-d H:i:s').' ; Valid time: '.$valid_time.' ----------------');
			return false;
		}

		$this->utils->debug_log('------------ START GENERATING income access daily reports (registration and sales) (FROM '.$from.' TO '.$to.') ----------------');

		$server 	= $this->utils->getConfig('ia_sftp_hostname');
		$port 		= $this->utils->getConfig('ia_sftp_port');
		$username 	= $this->utils->getConfig('ia_sftp_username');
		$password 	= $this->utils->getConfig('ia_sftp_password');

		$signup_filename 		= $this->utils->getConfig('ia_daily_signup_filename_prefix');
		$signup_filepath 		= $this->utils->getConfig('ia_daily_signup_filepath');
		$signup_file_extension 	= $this->utils->getConfig('ia_daily_signup_file_extension');
		$signup_csv_headers 	= $this->utils->getConfig('ia_daily_signup_csv_headers');

		$sales_filename 		= $this->utils->getConfig('ia_daily_sales_filename_prefix');
		$sales_filepath 		= $this->utils->getConfig('ia_daily_sales_filepath');
		$sales_file_extension 	= $this->utils->getConfig('ia_daily_sales_file_extension');
		$sales_csv_headers 		= $this->utils->getConfig('ia_daily_sales_csv_headers');

		//-- Check configurations
		if(empty($server) || empty($port) || empty($username) || empty($password) || empty($signup_filename) || empty($signup_filepath) || empty($signup_file_extension) || empty($signup_csv_headers) || empty($sales_filename) || empty($sales_filepath) || empty($sales_file_extension) || empty($sales_csv_headers))
		{
			$this->utils->debug_log('Aborting...lacking of configuration.');
			return false;
		}

		//-- Initialize the SFTP Connection
		$sftp = new Net_SFTP($server,$port);
		if (!$sftp->login($username, $password)) {
			$this->utils->debug_log('Unable to authenticate with server.');
			return false;
		}

		$this->utils->debug_log('Connection successful, uploading file now...');


		$this->load->model(array('player_model'));

		$report_types = array('signup','sales');

		foreach ($report_types as $report_key => $report_value) {
			// -- Retrieve Result Set
			if($report_value == 'signup')
				$data = $this->player_model->getDailySignupWithBtag($from, $to);
			else
				$data = $this->player_model->getDailySalesWithBtag($from, $to);

			$count = 0;

			if(!empty($data)) $count = count($data);

			$term = $report_value == 'signup' ? 'Registration' : 'Sales';

			$this->utils->debug_log('------------ IN PROGRESS: Daily '.$term.' Report (FROM '.$from.' TO '.$to.'): count = '. $count. '----------------');


			//-- File uploading to SFTP server
			${$report_value . '_filename'} 	  .= '_'. date('Ymd', strtotime($from));
			${$report_value . '_csv_content'}  = implode(',', ${$report_value . '_csv_headers'});
			${$report_value . '_csv_content'} .= "\r\n";

			// -- Prepare CSV Content
			if($count > 0){
				foreach ($data as $key => $value) {
					${$report_value . '_csv_content'} .= implode(',',$value);
					${$report_value . '_csv_content'} .= "\r\n";
				}
			}

			/*
			// -- Saving file to our own directory
			$this->load->helper('file');

			$file_path = $this->utils->getUploadPath().'/income_access/'.${$report_value . "_filepath"};

			if( ! is_dir($this->utils->getUploadPath().'/income_access'))
				@mkdir($this->utils->getUploadPath().'/income_access');

			if( ! is_dir($file_path))
				@mkdir($file_path);
			*/

			$file_path = "/${$report_value . '_filepath'}/${$report_value . '_filename'}${$report_value . '_file_extension'}";

			$this->utils->debug_log("-------------- File to be saved (with file path):",$file_path );

			//if ( ! write_file($file_path, ${$report_value . '_csv_content'}))
			if ( !$sftp->put($file_path, ${$report_value . '_csv_content'}))
				$this->utils->debug_log("INCOME ACCESS REPORT SAVING UNSUCCESSFUL: DAILY ".$term." (FROM '.$from.' TO '.$to.')");
			else
				$this->utils->debug_log("INCOME ACCESS REPORT SAVING SUCCESSFUL: DAILY ".$term." (FROM '.$from.' TO '.$to.')");

		}

		$this->utils->debug_log('------------ END GENERATING income access daily report (FROM '.$from.' TO '.$to.') ----------------');

		return true;
	}

	public function untagged_new_games(){
		$this->load->model(['game_description_model']);

		$update = $this->game_description_model->updateFlagNewGame();

		$success = false;

		if($update){
			$success = true;
			$msg = $this->utils->debug_log('untagged_new_games: SUCCESS');
		}else{
			$msg = $this->utils->debug_log('untagged_new_games: FAILED');
		}

		$this->utils->debug_log('untagged_new_games', $success);


		$this->returnText($msg);
	}

	public function query_last_6months_played_game($game_platform_id=AGIN_API, $date_str="-6 months"){

		$this->load->model(['total_player_game_day']);

		$d=new DateTime($date_str);

		$this->utils->debug_log('start from', $d);

		$rows=$this->total_player_game_day->queryBetPlayerUsername($d->format('Y-m-d'), $game_platform_id);

		$result=['username'=>[]];
		$this->utils->printLastSQL();
		foreach ($rows as $row) {
			$result['username'][]=$row['username'];
		}

		// $this->utils->debug_log($result);

		$file='/home/vagrant/Code/query_last_6months_played_game_'.date('YmdHis').'.json';
		file_put_contents($file, json_encode($result, JSON_PRETTY_PRINT));

		$this->utils->debug_log('start from', $d, $d->format('Y-m-d'), $file);

	}

	/**
	 * Minify the embed script files in player site
	 * Used for front-end referenced.
	 *
	 * Executed by followings,
	 * - vagrant@default_og_livestablemdb-PHP7:~/Code/og$ ./minify_player_embed_scripts.sh
	 * - vagrant@default_og_livestablemdb-PHP7:~/Code/og$ ./create_links.sh
	 *
	 * @return void
	 */
	public function minifyPlayerEmbedScripts(){
		$this->load->library(['player_main_js_library']);

		$formater = "Minify the embed script file,'%s' Done."; // 1 params

		/// will minify the file, /resources/player/embed/promotionDetails.src.js
		// And referenced by the followings,
		// http://player.og.local/resources/player/built_in/embed.promotionDetails.min.js
		// http://www.og.local/resources/player/built_in/embed.promotionDetails.min.js
		$srcPathFilename = '/resources/player/embed/promotionDetails.src';
		$outputFilename = 'embed.promotionDetails';
		$this->player_main_js_library->generate_embed_scripts($srcPathFilename, $outputFilename);
		$this->utils->info_log( sprintf($formater, $srcPathFilename. '.js') );

		/// will minify the file, /resources/player/embed/speed_detection.src.js
		// And referenced by the followings,
		// http://player.og.local/resources/player/built_in/embed.speed_detection.min.js
		// http://www.og.local/resources/player/built_in/embed.speed_detection.min.js
		$srcPathFilename = '/resources/player/embed/speed_detection.src';
		$outputFilename = 'embed.speed_detection';
		$is_content_wrapped = true;
		$this->player_main_js_library->generate_embed_scripts($srcPathFilename, $outputFilename, $is_content_wrapped);
		$this->utils->info_log( sprintf($formater, $srcPathFilename. '.js') );

		/// will copy the file, admin/public/resources/player/embed/speed_detection.templates.html into built_in folder,
		// And accessed by the following URI,
		// - //player.og.local/resources/player/built_in/embed.speed_detection.templates.html
		// - //www.og.local/resources/player/built_in/embed.speed_detection.templates.html
		$srcPathFullFilename = '/resources/player/embed/speed_detection.templates.html';
		$outputFilename = 'embed.speed_detection.templates.html';
		$this->player_main_js_library->generate_embed_htmls($srcPathFullFilename, $outputFilename);
		$this->utils->info_log( sprintf($formater, $srcPathFullFilename) );
	}



	/**
	 * Create Player Main JS
	 *
	 * @return void
	 */
	public function createPlayerMainJS(){
        $this->CI->load->model(array('static_site'));
        $this->load->library(['player_main_js_library']);

        $all_static_sites = $this->static_site->getAllStaticSites(null, null, 'id');

        if(empty($all_static_sites)){
            return $this->utils->info_log('Create Player Main JS Done.');
        }

		/// moved to minifyPlayerEmbedScripts().

        foreach($all_static_sites as $static_site){
            $site_name = $static_site['site_name'];
            $content = $this->player_main_js_library->generate_static_scripts($site_name);

            if($this->player_main_js_library->generate_result()){
                $this->utils->info_log("Create the '{$site_name}' static site Fail.");
            }else{
                $this->utils->info_log("Create the '{$site_name}' static site Done.");
            }
        }

        $this->utils->info_log('Create Player Main JS Done.');
    }

	public function refresh_all_payment_accounts_total_daily_deposit_amount_daily() {
		$this->CI->load->model(array('payment_account'));

		$all_payment_accounts = $this->payment_account->getAllPaymentAccount();
		foreach ($all_payment_accounts as $each_payment_account) {
			$this->utils->debug_log('start refreshing payment account total_daily_deposit_amount, id: ', $each_payment_account['id']);
			$result = $this->payment_account->updateDeposit($each_payment_account['id']);
			$this->utils->debug_log('refreshing payment account total_daily_deposit_amount result: ', $result);
		}
		$this->utils->info_log('======= Refresh all payment accounts total_daily_deposit_amount Done. =======');
	}

    public function incrementCMSVersion(){
        $this->load->model(['operatorglobalsettings']);
        $cms_version = $this->operatorglobalsettings->getSettingJson('cms_version');

        $segments = explode('.', $cms_version);
        if(empty($segments)){
            $this->CI->operatorglobalsettings->syncSettingJson("cms_version", '1.000.000.001', 'value');
            return FALSE;
        }

        $last_version_code = (int)$segments[count($segments) - 1];
        $last_version_code = sprintf('%03d', $last_version_code+1);
        $segments[count($segments) - 1] = $last_version_code;

        $this->CI->operatorglobalsettings->syncSettingJson("cms_version", implode('.', $segments), 'value');
        $cms_version = $this->operatorglobalsettings->getSettingJson('cms_version');

        $this->utils->debug_log('Setup cms_version: ' . $cms_version);
    }

    /**
     * create upload path
     * @param  string $hostId
     *
     */
    public function fix_upload_path_on_mdb($hostId){

    	$codeDir=realpath(dirname(__FILE__).'/../../../../..');
    	$projectDir=realpath(dirname(__FILE__).'/../../../..');

    	$this->utils->debug_log('codeDir:'.$codeDir.', projectDir:'.$projectDir);
    	$pubDir=$codeDir.'/pub';
    	$this->safeCreateDir($pubDir);
    	$storageDir=$pubDir.'/'.$hostId;
    	$this->safeCreateDir($storageDir);
		// ln -sfn ${TMP_PUB_DIR}/$HOST_ID $PROJECT_HOME/admin/storage
		// ln -sfn ${TMP_PUB_DIR}/$HOST_ID $PROJECT_HOME/player/storage
    	$this->safeCreateSymLink($storageDir, $projectDir.'/admin/storage');
    	$this->safeCreateSymLink($storageDir, $projectDir.'/player/storage');

    	//for remote upload
    	$sharingUpload=$pubDir.'/sharing_upload';
    	$this->safeCreateDir($sharingUpload);
    	$this->safeCreateSymLink($sharingUpload, $projectDir.'/admin/sharing_upload');
    	$this->safeCreateSymLink($sharingUpload, $projectDir.'/player/sharing_upload');
    	$this->safeCreateSymLink($sharingUpload, $projectDir.'/aff/sharing_upload');
    	$this->safeCreateSymLink($sharingUpload, $projectDir.'/agency/sharing_upload');

    	//for private upload
    	$sharingPrivate=$pubDir.'/sharing_private';
    	$this->safeCreateDir($sharingPrivate);
    	$this->safeCreateSymLink($sharingPrivate, $projectDir.'/sharing_private');

    	//upload dir on pod
    	$reportsDir=$storageDir.'/reports';
    	$this->safeCreateDir($reportsDir);
    	$sharingSqlsDir=$sharingUpload.'/remote_sqls';
        $this->safeCreateDir($sharingSqlsDir);

    	$sharingReportsDir=$sharingUpload.'/remote_reports';
    	$this->safeCreateDir($sharingReportsDir);
    	$this->safeCreateSymLink($sharingReportsDir, $projectDir.'/admin/public/reports');
    	$this->safeCreateSymLink($sharingReportsDir, $projectDir.'/player/public/reports');
    	$this->safeCreateSymLink($sharingReportsDir, $projectDir.'/aff/public/reports');
    	$this->safeCreateSymLink($sharingReportsDir, $projectDir.'/agency/public/reports');

    	$sharingLogsDir=$sharingUpload.'/remote_logs';
    	$this->safeCreateDir($sharingLogsDir);

    	$this->safeCreateDir($storageDir.'/player');
    	$playerInternalDir=$storageDir.'/player/internal';
    	//$this->safeCreateDir($storageDir.'/player/internal');
    	$this->safeCreateDir($playerInternalDir);
        $this->safeCreateSymLink($sharingLogsDir, $playerInternalDir.'/remote_logs');
        $this->safeCreateSymLink($sharingSqlsDir, $playerInternalDir.'/remote_sqls');

    	$uploadDir=$storageDir.'/upload';
    	$this->safeCreateDir($uploadDir);
    	//build in dir, default_all.min.js
    	$playerPubDir=$storageDir.'/player_pub';
    	$this->safeCreateDir($playerPubDir);
    	$this->safeCreateSymLink($playerPubDir, $projectDir.'/admin/public/resources/player/built_in');

    	$bannerDir=$uploadDir.'/banner';
    	$this->safeCreateDir($bannerDir);
    	$this->safeCreateDir($uploadDir.'/notifications');
    	$this->safeCreateDir($uploadDir.'/themes');
    	$this->safeCreateDir($uploadDir.'/shared_images');
    	$imagesAccountDir=$uploadDir.'/shared_images/account';
    	$this->safeCreateDir($imagesAccountDir);
    	$imagesBannerDir=$uploadDir.'/shared_images/banner';
    	$this->safeCreateDir($imagesBannerDir);
    	$imagesDepositslipDir=$uploadDir.'/shared_images/depositslip';
    	$this->safeCreateDir($imagesDepositslipDir);

        $sharingGameTypeIconDir=$uploadDir.'/cms_game_types';
        $this->safeCreateDir($sharingGameTypeIconDir);
        $this->safeCreateSymLink($sharingGameTypeIconDir, $projectDir.'/admin/public/resources/images/cms_game_types');
        $this->safeCreateSymLink($sharingGameTypeIconDir, $projectDir.'/player/public/resources/images/cms_game_types');

        $sharingGamePlatformIconDir=$uploadDir.'/cms_game_platforms';
        $this->safeCreateDir($sharingGamePlatformIconDir);
        $this->safeCreateSymLink($sharingGamePlatformIconDir, $projectDir.'/admin/public/resources/images/cms_game_platforms');
        $this->safeCreateSymLink($sharingGamePlatformIconDir, $projectDir.'/player/public/resources/images/cms_game_platforms');

		$sharingGameTypeIconDir=$uploadDir.'/hedge_in_ag';
        $this->safeCreateDir($sharingGameTypeIconDir);
        $this->safeCreateSymLink($sharingGameTypeIconDir, $projectDir.'/admin/public/resources/hedge_in_ag');
		// $this->safeCreateSymLink($sharingGameTypeIconDir, $projectDir.'/player/public/resources/hedge_in_ag');

    	//upload links
    	$this->safeCreateSymLink($uploadDir, $projectDir.'/admin/public/upload');
    	$this->safeCreateSymLink($uploadDir, $projectDir.'/player/public/upload');
    	$this->safeCreateSymLink($uploadDir, $projectDir.'/aff/public/upload');
    	$this->safeCreateSymLink($uploadDir, $projectDir.'/agency/public/upload');
    	//banner links
    	$this->safeCreateSymLink($bannerDir, $projectDir.'/admin/public/banner');
    	$this->safeCreateSymLink($bannerDir, $projectDir.'/player/public/banner');
    	$this->safeCreateSymLink($bannerDir, $projectDir.'/aff/public/banner');
    	$this->safeCreateSymLink($bannerDir, $projectDir.'/agency/public/banner');
    	//reports links
    	// $this->safeCreateSymLink($reportsDir, $projectDir.'/admin/public/reports');
    	// $this->safeCreateSymLink($reportsDir, $projectDir.'/player/public/reports');
    	// $this->safeCreateSymLink($reportsDir, $projectDir.'/aff/public/reports');
    	// $this->safeCreateSymLink($reportsDir, $projectDir.'/agency/public/reports');

    	//public/resources/images/account
    	$this->safeCreateSymLink($imagesAccountDir, $projectDir.'/admin/public/resources/images/account');
    	$this->safeCreateSymLink($imagesAccountDir, $projectDir.'/player/public/resources/images/account');
    	$this->safeCreateSymLink($imagesAccountDir, $projectDir.'/aff/public/resources/images/account');
    	$this->safeCreateSymLink($imagesAccountDir, $projectDir.'/agency/public/resources/images/account');
    	//public/resources/images/banner
    	$this->safeCreateSymLink($imagesBannerDir, $projectDir.'/admin/public/resources/images/banner');
    	$this->safeCreateSymLink($imagesBannerDir, $projectDir.'/player/public/resources/images/banner');
    	$this->safeCreateSymLink($imagesBannerDir, $projectDir.'/aff/public/resources/images/banner');
    	$this->safeCreateSymLink($imagesBannerDir, $projectDir.'/agency/public/resources/images/banner');
    	//public/resources/images/depositslip
    	$this->safeCreateSymLink($imagesDepositslipDir, $projectDir.'/admin/public/resources/images/depositslip');
    	$this->safeCreateSymLink($imagesDepositslipDir, $projectDir.'/player/public/resources/images/depositslip');
    	$this->safeCreateSymLink($imagesDepositslipDir, $projectDir.'/aff/public/resources/images/depositslip');
    	$this->safeCreateSymLink($imagesDepositslipDir, $projectDir.'/agency/public/resources/images/depositslip');

    }
    /**
     * create dir and change mod
     * @param  string  $dir
     * @param  boolean $createMDB
     * @return boolean
     */
    private function safeCreateDir($dir, $createMDB=true){
    	$success=true;
    	$this->utils->debug_log('create/check dir: '.$dir);
    	if(!file_exists($dir)){
    		$success=mkdir($dir, 0777);
    		chmod($dir, 0777);
    	}

    	if($createMDB){
    		//get mdb list
    		$mdbList=$this->utils->getMDBList();
    		if(!empty($mdbList)){
	    		foreach ($mdbList as $key) {
	    			$mdbDir=$dir.'/'.$key;
			    	if(!file_exists($mdbDir)){
			    		$success=mkdir($mdbDir, 0777);
			    		chmod($mdbDir, 0777);
			    	}
	    		}
    		}
    	}
    	return $success;
    }
    /**
     * create sym link, delete before
     * @param  string $source
     * @param  string $target
     * @return boolean
     */
    private function safeCreateSymLink($source, $target){
    	$success=true;
    	$this->utils->debug_log('create/check link: '.$source.' to '.$target);
    	if(file_exists($target)){
			unlink($target);
    	}
		$success=symlink($source, $target);
		return $success;
    }

    public function test_promo_rule(){
		require_once dirname(__FILE__) . '/../../models/customized_promo_rules/promo_rule_ole777_upgrade_level_bonus.php';
		// $promo_rule=Promo_rule_ole777_upgrade_level_bonus::getSingletonInstance();
		$this->load->model('customized_promo_rules/promo_rule_ole777_upgrade_level_bonus');

		$this->promo_rule_ole777_upgrade_level_bonus->init(null,null);

		$this->utils->debug_log('done');
    }

    public function refresh_all_main_wallet(){
    	$this->load->model(['wallet_model']);
    	$playerList=$this->player_model->getUnlimitedPlayerId();

		$refreshByConfig = $this->utils->getConfig('refresh_all_main_wallet_player_id_list');
		$refreshByCustomized = $this->utils->getConfig('refresh_all_main_wallet_by_customized_condition');
		$dryRun = $this->utils->getConfig('refresh_all_main_wallet_dry_run');

		$this->utils->info_log('refresh_all_main_wallet', $refreshByConfig, $refreshByCustomized);
		if(!empty($refreshByConfig)){
			$playerList = $refreshByConfig;
		}else if(!empty($refreshByCustomized)){
			$playerList = $this->player_model->getPlayersByCustomizedCondition($refreshByCustomized);
		}

		if($dryRun === true){
			$this->utils->info_log('dryRun', $playerList);
			return;
		}

    	$failed_player=[];
    	$cnt=0;
    	if(!empty($playerList)){
    		foreach ($playerList as $playerId) {
    			if(!empty($playerId)){
	    			$success=$this->wallet_model->lockAndTransForPlayerBalance($playerId, function()
	    				use($playerId){
		    			//lock
	    				$bigWallet=$this->wallet_model->getBigWalletByPlayerId($playerId);
	    				if(!empty($bigWallet)){
		    				return $this->wallet_model->updateBigWalletByPlayerId($playerId, $bigWallet);
	    				}
		    			// return $this->wallet_model->moveAllToRealOnMainWallet($playerId);
	    			});
	    			if($success){
	    				$cnt++;
	    				$this->utils->debug_log('refresh player: '.$playerId);
	    			}else{
	    				$this->utils->error_log('refresh player: '.$playerId.' failed');
	    				$failed_player[]=$playerId;
	    			}
    			}
    		}
    	}

    	if(!empty($failed_player)){
    		$this->utils->error_log('failed_player', $failed_player);
    	}

	   	$this->utils->info_log('done : '.$cnt);

	}

    /**
     * This method will generate unique referral codes
     * for each and every players that has no existing referral codes
     * and update them at once
     *
     * @return void
     */
    public function batchInsertReferralCodeToPlayersWithoutReferralCode(){

    	// -- log prefix
    	$debug_log_prefix = 'batchInsertReferralCodeToPlayersWithoutReferralCode =====>    ';

		$processed_data_ctr = 0; // -- collects count of processed data

    	$this->load->model('player_model');

    	// -- Select all players that has no invitation codes
    	$this->db->select('p.playerId');
		$this->db->from('player p');
		$this->db->where('p.invitationCode', '0')->or_where('p.invitationCode', '');
		$query = $this->db->get();
		$result_set = $query->result();

		// -- Check if there are players to be processed
		if(count($result_set) <= 0){
			$this->utils->debug_log($debug_log_prefix.'No players without referral code has been found.');
			return true;
		}

		$this->utils->debug_log($debug_log_prefix.'START BATCH INSERT OF REFERRAL CODES TO PLAYERS: Total count of players to be updated', count($result_set));

		// -- uncomment if we don't want to update via chunking
		/*foreach ($result_set as $key => $player) {

			$response = $this->player_model->generateReferralCodePerPlayer($player->playerId);

			if(!$response)
				$this->utils->debug_log($debug_log_prefix.'ERROR: Player '.$player->playerId.' was not updated due to error.');
			else
				$this->utils->debug_log($debug_log_prefix.'SUCCESS: Player '.$player->playerId.' was updated successfully. Referral code = '.$response.'.');


		}*/

		// -- Chunk data by 500 records
		$updateList = array_chunk($result_set, 500);

		foreach ($updateList as $key => $updateSet) {

			$player_count = count($updateSet); // -- get count of current chunk
			$invitationCodes = array(); // -- used invitation codes
			$update_data = array(); // -- data to be updated

			$this->utils->debug_log($debug_log_prefix.'IN PROGRESS: Player Count = '.$player_count.' ; Records:', $updateSet);

			// -- generate referral code for each player
			foreach ($updateSet as $key => $player) {

				// -- initial generation of code which is unique from the database
				$tmp_code = $this->player_model->generateReferralCode();

				// -- Keep on generating a code if the code exists in the current pool
				while (in_array($tmp_code, $invitationCodes)) {
					$tmp_code = $this->player_model->generateReferralCode();
				}

				// -- add the valid code to the current pool of unique codes
				$invitationCodes[] = $tmp_code;

				// -- prepare player data to be updated.
				$update_data[] = array(
					'playerId' => $player->playerId,
					'invitationCode' => $tmp_code,
				);
			}

			// -- batch update query
			$this->db->update_batch('player', $update_data,'playerId');

			if($this->db->affected_rows() <= 0 ){
				$this->utils->error_log($debug_log_prefix.'ERROR: Update failed. Records being updated:', $update_data);
			}
			else{
				$processed_data_ctr += count($update_data);
				$this->utils->debug_log($debug_log_prefix.'SUCCESS: Update successful. Records updated:', $update_data);
			}
		}

		$this->utils->debug_log($debug_log_prefix.'END BATCH INSERT OF REFERRAL CODES TO PLAYERS. Count of records proccessed: '. $processed_data_ctr, $processed_data_ctr);

    } // EOF batchInsertReferralCodeToPlayersWithoutReferralCode

    /**
	 * Select all pending maintenance and validate thru interval time difference then will update the status
	 * in game_maintenance_schedule to IN MAINTENANCE and update the maintenance_mode to MAINTENANCE START in external_system table
	 **/
    public function set_game_maintenance_schedule_in_maintenance($oDate=null){
        $this->load->model(['external_system']);
      	$date = strtotime($oDate);
        $intervalset = External_system::MAINTENANCE_INTERVAL_TIME; //2 mins
        if ( empty($date) ) {
	       	$date = strtotime($this->utils->getNowForMysql());
	    }
	    $pending = $this->external_system->getMaintenanceScheduleByStatus(External_system::MAINTENANCE_STATUS_PENDING);
	    if( !empty($pending) ) {
	    	foreach ( $pending as $key => $value ) {
			    $start_date = strtotime($value['start_date']);
			    $id = $value['id'];
				    if( $date >=  $start_date ) {
				        $diff = ($date - $start_date)/60;
				        if( $diff <= $intervalset ){
							$orignalStatus = null; // default
							$result = $this->external_system->getGameMaintenanceScheduleById($id);
							if( count($result) > 0){
								$orignalGameMaintenanceSchedule = $result[0];
								$orignalStatus = $orignalGameMaintenanceSchedule->status;
							}

							$gameApiId = $this->external_system->getGameApiIdGameMaintenanceSchedule($id);
							$data = array('maintenance_mode' =>  External_system::MAINTENANCE_START);
							$external = $this->external_system->setToMaintenanceOrPauseMode($data,$gameApiId);
							$data2 = array('status' => External_system::MAINTENANCE_STATUS_IN_MAINTENANCE);
							$this->external_system->editDetailsGameMaintenanceSchedule($data2,$id);

							if( ! is_null($orignalStatus) // not default
								&& $orignalStatus != $data2['status'] // on change
							){ // will change to...
								//ATGAH = addToGameApiHistory
								$data4ATGAH= (array)$this->external_system->getSystemById($gameApiId);
								$data4ATGAH['action'] = External_system::GAME_API_HISTORY_ACTION_UNDER_MAINTENANCE;
								$data4ATGAH['updated_at']= $this->utils->getNowForMysql();
								$data4ATGAH['game_platform_id'] = $gameApiId;
								$data4ATGAH['user_id'] = $orignalGameMaintenanceSchedule->last_edit_user;
								unset($data4ATGAH['id']);
								$this->external_system->addToGameApiHistory($data4ATGAH);
							}
				        }// EOF if( $diff <= $intervalset ){...
				    }
			    }
	    } else {
	    	$this->utils->error_log('ERROR: No schedule for maintenance.');
	    }
	    $this->set_game_maintenance_schedule_in_maintenance_done($oDate);
    } // EOF set_game_maintenance_schedule_in_maintenance

    /**
	 * Select all in maintenance schedule and validate thru interval time difference then will update the status
	 * in game_maintenance_schedule to MAINTENANCE and update the maintenance_mode to MAINTENANCE FINISH in external_system table
	 **/
    public function set_game_maintenance_schedule_in_maintenance_done($date=null){
        $this->load->model(['external_system']);
      	$date = strtotime($date);
        $intervalset = External_system::MAINTENANCE_INTERVAL_TIME; //2 mins
        if ( empty($date) ) {
	       	$date = strtotime($this->utils->getNowForMysql());
	    }
	    $inmaintenance = $this->external_system->getMaintenanceScheduleByStatus(External_system::MAINTENANCE_STATUS_IN_MAINTENANCE);
	    if( !empty($inmaintenance) ){
    		foreach ($inmaintenance as $key => $value) {
    	 		$end_date = strtotime($value['end_date']);
    	 		$id = $value['id'];
    	 		if( $date >= $end_date ){
					 $diff = ($date - $end_date)/60;

    	 			if( $diff <= $intervalset ){
						$orignalStatus = null; // default
						$result = $this->external_system->getGameMaintenanceScheduleById($id);
						if( count($result) > 0){
							$orignalGameMaintenanceSchedule = $result[0];
							$orignalStatus = $orignalGameMaintenanceSchedule->status;
						}

						$gameApiId = $this->external_system->getGameApiIdGameMaintenanceSchedule($id);
						$data = array('maintenance_mode' =>  External_system::MAINTENANCE_FINISH);
						$external = $this->external_system->setToMaintenanceOrPauseMode($data,$gameApiId);
						$data2 = array('status' => External_system::MAINTENANCE_STATUS_DONE);
						$this->external_system->editDetailsGameMaintenanceSchedule($data2,$id);

						if( ! is_null($orignalStatus) // not default
							&& $orignalStatus != $data2['status'] // on change
						){ // will change to...
							//ATGAH = addToGameApiHistory
							$data4ATGAH= (array)$this->external_system->getSystemById($gameApiId);
							$data4ATGAH['action'] = External_system::GAME_API_HISTORY_ACTION_FINISH_MAINTENANCE;
							$data4ATGAH['updated_at']= $this->utils->getNowForMysql();
							$data4ATGAH['game_platform_id'] = $gameApiId;
							$data4ATGAH['user_id'] = $orignalGameMaintenanceSchedule->last_edit_user;
							unset($data4ATGAH['id']);
							$this->external_system->addToGameApiHistory($data4ATGAH);
						}
    	 			} // EOF if( $diff <= $intervalset ){...
    			}
	    	}
	    }else{
	    	$this->utils->error_log('ERROR: No schedule for maintenance done.');
	    }
	}// EOF set_game_maintenance_schedule_in_maintenance_done

	public function test_remove_dup_uniqueid(){
		$this->load->model(['original_game_logs_model']);

		$rows=[
			['trans_id'=>100, 'ticket_status'=>'running'],
			['trans_id'=>100, 'ticket_status'=>'won'],
			['trans_id'=>200, 'ticket_status'=>'loss'],
			['trans_id'=>300, 'ticket_status'=>'loss'],
			['trans_id'=>200, 'ticket_status'=>'loss'],
			['trans_id'=>200, 'ticket_status'=>'loss'],
		];
		$this->utils->info_log(count($rows),$rows);

		$this->original_game_logs_model->removeDuplicateUniqueid($rows, 'trans_id', function($row1st, $row2nd){
			//compare status
			$status1st=strtolower($row1st['ticket_status']);
			$status2nd=strtolower($row2nd['ticket_status']);
			//if same status, keep sencond
			if($status1st==$status2nd){
				return 2;
			}else if($status1st=='waiting'){
				return 2;
			}else if($status2nd=='waiting'){
				return 1;
			}else if($status1st=='running'){
				return 2;
			}else if($status2nd=='running'){
				return 1;
			}
			//default is last
			return 2;
		});

		$this->utils->info_log(count($rows),$rows);

	}

	public function refreshPlayersDispatchAccountLevel() {
		$this->utils->debug_log('==============refreshPlayersDispatchAccountLevel start');
		$this->load->model(array('dispatch_account', 'player_model', 'transactions'));
		$player_username_list = $this->config->item('log_refresh_dispatch_account_level_player_username_list');
		$sender = lang('system');

		$group_with_level_order_lists = $this->dispatch_account->getDispatchAccountGroupIdsWithLevelOrders();
		$this->utils->debug_log('============refreshPlayersDispatchAccountLevel group_with_level_lists', $group_with_level_order_lists);

		if(empty($group_with_level_order_lists)) {
			$message = lang("dispatch_account_level.refresh_no_available_groups_and_levels");
			$this->returnJsonResult(array('success' => false, 'msg' => $message));
		}

		$player_ids = $this->player_model->getAvailablePlayers();
		foreach ($player_ids as $player) {
			$player_id = $player->playerId;
			$player_level_id = $player->dispatch_account_level_id;
			$level = $this->dispatch_account->getDispatchAccountLevelDetailsById($player_level_id);
			$player_group_id = $level['group_id'];
			$player_level_order = $level['level_order'];

			if(empty($level)) {
				$this->utils->debug_log('============refreshPlayersDispatchAccountLevel get empty level by level id', $player_level_id, $level);
					continue;
			}

			//0. check if player over observation period
			$now = new DateTime('now');
			$registed = new DateTime($player->createdOn);
			$interval = $registed->diff($now)->days;
			$observation_days = $level['level_observation_period'];
			if($interval < $observation_days){
				if(is_array($player_username_list)) {
					if(in_array($player->username, $player_username_list)) {
						$this->utils->debug_log('============refreshPlayersBelongLevel skip because player still under observation period',
							'player id and username: '. $player_id.', '.$player->username,
							'registed on: '. $player->createdOn,
							'registed: '.$interval.' days');
					}
				}
				continue;
			}

			if(array_key_exists($player_group_id, $group_with_level_order_lists)) {
				$last_key = count($group_with_level_order_lists[$player_group_id]) - 1;
				$high_level_order_of_the_group = $group_with_level_order_lists[$player_group_id][$last_key]['level_order'];

				//1. check if player is in the highest level of the belong group. (get the highest level order of the group)
				//No need to refresh new level if the player was alredy in the highest level order
				if($player_level_order == $high_level_order_of_the_group) {
					if(is_array($player_username_list)) {
						if(in_array($player->username, $player_username_list)) {
							$this->utils->debug_log('============refreshPlayersDispatchAccountLevel no need to refresh because player was already in the highest level of the group.',
								'player id and username: '. $player_id.', '.$player->username,
								'group_id: '. $player_group_id,
								'player level_id and level_order: '. $player_level_id.', '.$player_level_order);
						}
					}
					continue;
				}
				else {
					//2. get the dispatch account level upgrade condition of the player
					$player_refresh_target_level_id = 999999;

					$register_date = new DateTime($player->createdOn);
					$now = new DateTime();
					$from_datetime = $register_date->format('Y-m-d H:i:s');
		            $to_datetime = $now->format('Y-m-d H:i:s');

					//get single max deposit
					$single_max_deposit_amount = $this->transactions->getPlayerSingleMaxDeposit($player_id, $from_datetime, $to_datetime);

					//get total deposit
					$total_deposit_amount = $this->transactions->getPlayerTotalDeposits($player_id, $from_datetime, $to_datetime);

					//get total deposit count
					$total_deposit_count = $this->transactions->getPlayerTotalDepositCount($player_id, $from_datetime, $to_datetime);

					//get total withdrawal
					$total_withdrawal_amount = $this->transactions->getPlayerTotalWithdrawals($player_id, false, $from_datetime, $to_datetime);

					//get total withdrawal count
					$total_withdrawal_count = $this->transactions->getPlayerTotalWithdrawalCount($player_id, false, $from_datetime, $to_datetime);

					$player_refresh_target_group = $group_with_level_order_lists[$player_group_id];
					$player_refresh_target_levels = array();

					//3. compare each conditon of player with the level from highest level order to the level of player
					if(count($player_refresh_target_group) > 0) {
						$player_refresh_target_levels =
							array_values(array_filter($player_refresh_target_group, function ($levels) use ($player_level_order, $single_max_deposit_amount, $total_deposit_amount, $total_deposit_count, $total_withdrawal_amount, $total_withdrawal_count) {
								if (($levels['level_order'] > $player_level_order) &&
									(
										$single_max_deposit_amount >= $levels['level_single_max_deposit'] ||
										$total_deposit_amount      >= $levels['level_total_deposit'] ||
										$total_deposit_count       >= $levels['level_deposit_count'] ||
										$total_withdrawal_amount   >= $levels['level_total_withdraw'] ||
										$total_withdrawal_count    >= $levels['level_withdraw_count']
									)
								   ) {
									return true;
								}
								return false;
							}));

						$count_levels = count($player_refresh_target_levels);
						if($count_levels > 0) {
							$player_refresh_target_level_id = $player_refresh_target_levels[$count_levels - 1]['id'];
							$player_refresh_target_level_member_limit = $player_refresh_target_levels[$count_levels - 1]['level_member_limit'];
							$current_level = $this->dispatch_account->getDispatchAccountLevelDetailsById($player_refresh_target_level_id);

							//4. check if the member count of target level is full. If it is, copy the target level setup and create a same level.
							// $current_level_member_count = $this->player_model->getPlayerCountByDispatchAccountLevelId($player_refresh_target_level_id);

							// if(is_null($player_refresh_target_level_member_limit)) {
							// 	$current_level = $this->dispatch_account->getDispatchAccountLevelDetailsById($player_refresh_target_level_id);
							// 	if(!empty($current_level)) {
							// 		$player_refresh_target_level_member_limit = $current_level['level_member_limit'];
							// 	}
							// 	else {
							// 		$isLevelMemberFull = false;
							// 	}
							// }

							// $isLevelMemberFull = ($current_level_member_count >= $player_refresh_target_level_member_limit) ? true : false;

							// //member is full
							// if($isLevelMemberFull) {
							// 	//copy another level
							// 	$player_refresh_target_level_id = $this->dispatch_account->copyDispatchAccountLevelByGroupId($player_group_id, $player_refresh_target_level_id, true);
							// 	if(!$player_refresh_target_level_id) {
							// 		$this->utils->debug_log('============refreshPlayersDispatchAccountLevel copy level id failed.',
							// 			'player id and username: '. $player_id.', '.$player->username,
							// 			'old level_id: '. $player->dispatch_account_level_id);
							// 	}
							// 	if(is_array($player_username_list)) {
							// 		if(in_array($player->username, $player_username_list)) {
							// 			$this->utils->debug_log('============refreshPlayersDispatchAccountLevel copy level due to member full.',
							// 				'player id and username: '. $player_id.', '.$player->username,
							// 				'full_level_id: '. $player_refresh_target_levels[$count_levels - 1]['id'],
							// 				'copy_level_id: '. $player_refresh_target_level_id);
							// 		}
							// 	}
							// }

							//5. refresh player with new level id
							$refresh_level_result = $this->player_model->adjustDispatchAccountLevel($player_id, $player_refresh_target_level_id);
							if(!$refresh_level_result) {
								$this->utils->debug_log('============refreshPlayersDispatchAccountLevel run update level failed.',
									'player id and username: '. $player_id.', '.$player->username,
									'should refresh to level_id: '. $player_refresh_target_level_id);
							} else {
								$this->player_model->savePlayerUpdateLog($player_id, lang('Adjust Dispatch Account Level') . ' - ' .lang('Before Adjustment') . ' (' . lang($level['group_name']) . ' - ' . 
								$level['level_name'] . ') ' . lang('After Adjustment') . ' (' . lang($current_level['group_name']) . ' - ' . $current_level['level_name'] . ') ', $sender);
							}
						}
						else {
							$player_refresh_target_level_id = $player_level_id;
						}
					}
					else {
						$this->utils->debug_log('============refreshPlayersDispatchAccountLevel cannot refresh because no levels exist in player_refresh_target_group', 'group_id: '. $player_group_id, 'player_username: '. $player->username);
						continue;
					}

					if(is_array($player_username_list)) {
						if(in_array($player->username, $player_username_list)) {
							$this->utils->debug_log('============refreshPlayersDispatchAccountLevel player current condition.',
								'player id and username: '. $player_id.', '.$player->username,
								'single_max_deposit_amount: '. $single_max_deposit_amount,
								'total_deposit_amount: '. $total_deposit_amount,
								'total_deposit_count: '. $total_deposit_count,
								'total_withdrawal_amount: '. $total_withdrawal_amount,
								'total_withdrawal_count: '. $total_withdrawal_count);
							$this->utils->debug_log('============refreshPlayersDispatchAccountLevel player_refresh_target_group', $player_refresh_target_group);
							$this->utils->debug_log('============refreshPlayersDispatchAccountLevel player_refresh_target_levels', $count_levels, $player_refresh_target_levels);

							if($player_refresh_target_level_id == $player_level_id) {
								$this->utils->debug_log('============refreshPlayersDispatchAccountLevel player did not refreshe level due to condition not qualify.',
									'player id and username: '. $player_id.', '.$player->username,
									'old_level_id: '. $player_level_id);
							}
							else {
								$this->utils->debug_log('============refreshPlayersDispatchAccountLevel player successfully refreshed level.',
									'player id and username: '. $player_id.', '.$player->username,
									'old_level_id: '. $player_level_id,
									'new_level_id: '. $player_refresh_target_level_id);
							}
						}
					}
				}
			}
			else {
				$this->utils->debug_log('============refreshPlayersDispatchAccountLevel Cannot refresh because player_group_id not exists.', 'group_id: '. $player_group_id, 'player_username: '. $player->username);
					continue;
			}            
            
		}

        foreach ($group_with_level_order_lists as $key => $value) {
            $this->dispatch_account->refreshGroupLevelCount($key);
        }


		$this->utils->debug_log('==============refreshPlayersDispatchAccountLevel end. Memory use: ', (memory_get_usage() / 1024 / 1024).'M');
	}

	public function batchCopyDataFromSaleOrdersAndTransactionNotes(){
		$this->load->model(array('sale_orders_notes'));
		$this->utils->debug_log('==============batchCopyDataFromSaleOrders start');

		$success = $this->sale_orders_notes->batchCopyDataFromSaleOrders();
		if($success){
			$this->utils->debug_log('==============batchCopyDataFromSaleOrders end SUCCESS');
			$this->utils->debug_log('==============batchCopyDataFromTransactionNotes start');

			$success = $this->sale_orders_notes->batchCopyDataFromTransactionNotes();
			if($success){
				$this->utils->debug_log('==============batchCopyDataFromTransactionNotes end SUCCESS');
			} else {
				$this->utils->debug_log('==============batchCopyDataFromTransactionNotes end FAILED');
			}
		} else {
			$this->utils->debug_log('==============batchCopyDataFromSaleOrders end FAILED');
		}
	}

	public function batchCopyDataFromWalletaccountAndTransactionNotes(){
		$this->load->model(array('walletaccount_notes'));
		$this->utils->debug_log('==============batchCopyDataFromWalletaccount start');

		$success = $this->walletaccount_notes->batchCopyDataFromWalletaccount();
		if($success){
			$this->utils->debug_log('==============batchCopyDataFromWalletaccount end SUCCESS');
			$this->utils->debug_log('==============batchCopyDataFromTransactionNotes start');

			$success = $this->walletaccount_notes->batchCopyDataFromTransactionNotes();
			if($success){
				$this->utils->debug_log('==============batchCopyDataFromTransactionNotes end SUCCESS');
				if($success){
					$success = $this->walletaccount_notes->copyExternalNotesByWalletAccountId();
					$this->utils->debug_log('==============copyExternalNotesByWalletAccountId end SUCCESS');
				} else {
					$this->utils->debug_log('==============copyExternalNotesByWalletAccountId end FAILED');
				}
			} else {
				$this->utils->debug_log('==============batchCopyDataFromTransactionNotes end FAILED');
			}
		} else {
			$this->utils->debug_log('==============batchCopyDataFromWalletaccount end FAILED');
		}
	}

	public function batchInsertDataFromPlayer(){
		$this->load->model(array('player_api_verify_status'));
		$success = $this->player_api_verify_status->batchInsertDataFromPlayer();
		if($success){
			$this->utils->debug_log('==============batchInsertDataFromPlayer end SUCCESS');
		}else{
			$this->utils->debug_log('==============batchInsertDataFromPlayer end FAILED');
		}
	}

	public function batchInsertDataFromWalletAccount(){
		$this->load->model(array('walletaccount_timelog'));
		$success = $this->walletaccount_timelog->batchInsertDataFromWalletAccount();
		if($success){
			$this->utils->debug_log('==============batchInsertDataFromWalletAccount end SUCCESS');
		}else{
			$this->utils->debug_log('==============batchInsertDataFromWalletAccount end FAILED');
		}
	}

	public function batchInsertDataFromTransactionNotes(){
		$this->load->model(array('walletaccount_timelog'));
		$success = $this->walletaccount_timelog->batchInsertDataFromTransactionNotes();
		if($success){
			$this->utils->debug_log('==============batchInsertDataFromTransactionNotes end SUCCESS');
		}else{
			$this->utils->debug_log('==============batchInsertDataFromTransactionNotes end FAILED');
		}
	}

	public function batchInsertDataToSaleOrdersTimelogFromSaleOrders(){
		$this->load->model(array('sale_orders_timelog'));
		$success = $this->sale_orders_timelog->batchInsertDataToSaleOrdersTimelogFromSaleOrders();
		if($success){
			$this->utils->debug_log('==============batchInsertDataToSaleOrdersTimelogFromSaleOrders end SUCCESS');
		}else{
			$this->utils->debug_log('==============batchInsertDataToSaleOrdersTimelogFromSaleOrders end FAILED');
		}
	}

	/**
	 * OGP-9124
	 * -
	 * This method will update all players':
	 * > totalBettingAmount
	 * > approvedWithdrawAmount
	 * > approvedWithdrawCount
	 * > totalDepositAmount
	 * > total_deposit_count
	 * > first_deposit
	 * > second_deposit
	 *
	 * @return void
	 */
	public function syncAllPlayersWithdrawAndDepositRelatedFields(){

		$this->load->model(['player_model']);

		$this->utils->debug_log('Start syncing / updating ALL player records (totalBettingAmount, approvedWithdrawAmount, totalDepositAmount, approvedWithdrawCount, total_deposit_count, first_deposit, second_deposit)');

		// -- temporarilySaveAllPlayerSummary
		$this->player_model->temporarilySaveAllPlayerSummary();

		$this->utils->debug_log('START SYNCING OF PLAYER SUMMARY');
		$syncing_result = $this->player_model->syncAllPlayersSummary();

		if($syncing_result === FALSE)
			$this->utils->error_log('AN ERROR OCCURED WHILE UPDATING PLAYER RECORDS: '.$this->db->_error_message());
		else
			$this->utils->debug_log('TOTAL NUMBER OF PLAYERS UPDATED: '.$syncing_result);

		$this->utils->debug_log('END SYNCING OF PLAYER SUMMARY');

	}

	/**
     * This method will update all old manual adjustment records
     * in transactions table for them to be displayed in the
     * new adjustment history.
     *
     * @return void
     */
    public function update_old_manual_adjustments_for_new_adjustment_history(){

    	$this->load->model('transactions');

    	$this->utils->debug_log('Start updating old manual adjustments for new adjustment history');

    	$manual_adjustment_transaction_types = array(
    		Transactions::MANUAL_ADD_BALANCE,
    		Transactions::MANUAL_SUBTRACT_BALANCE,
    		Transactions::SUBTRACT_BONUS,
    	);

    	$this->utils->debug_log('transaction types to be updated', $manual_adjustment_transaction_types);

    	$this->db->where_in('transaction_type', $manual_adjustment_transaction_types);
    	$this->db->update('transactions',array('is_manual_adjustment' => Transactions::MANUALLY_ADJUSTED));

    	$this->utils->debug_log('End update - Total count of transaction updated: '. $this->db->affected_rows());

    }

	/**
	 * batch supplement run the process pre-checker in the Queue.
	 *
	 * @return void
	 */
	public function batch_supplement_run_process_pre_checker_in_queue(){

		$func_name = __FUNCTION__;
		$is_execing = $this->isExecingWithPS($func_name, $this->oghome);
		$isOverWaitingTime = false;
		if ( !$is_execing && !$isOverWaitingTime ) {

			set_time_limit(0);
			$this->load->model(['wallet_model']);

			$nowDateTime = new DateTime();
			// Get the time range, -10 minutes ~ -2 days
			$endDateTime = clone $nowDateTime;
			$offsetTime = $this->config->item('offsetTime_batch_supplement_run_process_pre_checker_in_queue');
			$endDateTime->modify($offsetTime);
			$beginDateTime = clone $nowDateTime;
			$beginDateTime->modify($offsetTime)->modify('-2 day');

			$func_name = 'remote_processPreChecker';
			// $full_params = 'walletAccountId';
			$full_params = null;
			$params = 'walletAccountId';
			$like_side = 'both';
			$result = 'null';
			$created_at_range = [];
			$created_at_range[0] = $this->utils->formatDateTimeForMysql($beginDateTime);
			$created_at_range[1] = $this->utils->formatDateTimeForMysql($endDateTime);
			$order_by = [];
			$order_by['field'] = 'created_at';
			$order_by['by'] = 'asc';
			$resultList = $this->queue_result->getResultListByFuncNameAndFullParamsOrParams($func_name, $full_params, $result, $created_at_range, $order_by, $params, $like_side);

			// Collect the miss walletAccountId in queue waiting to processPreChecker.
			$walletAccountIdList = [];
			$tokenList = [];
			if( ! empty($resultList) ){
				foreach($resultList as $aResult){
					$_full_params = $this->utils->json_decode_handleErr($aResult['full_params'], true);
	// $this->utils->debug_log('OGP-21225._full_params', $_full_params);
					if( ! empty($_full_params['walletAccountId']) ){
						$walletAccountIdList[] = $_full_params['walletAccountId'];
						$tokenList[] = $aResult['token'];
					}
				}
			}// EOF if( ! empty($resultList) ){...

			$processedTokenList = [];
			if( ! empty($walletAccountIdList) ){

				foreach($walletAccountIdList as $indexNumber => $walletAccountId){

					$walletAccount = $this->wallet_model->getWalletAccountBy($walletAccountId);
					$isIgnoreByDwStatus = !in_array($walletAccount->dwStatus, [Wallet_model::REQUEST_STATUS, Wallet_model::PENDING_REVIEW_STATUS]); // for 6.1
					if($isIgnoreByDwStatus){
						// @todo Where does the ignore reason display in SBE.
						$dbgMsg = sprintf('Ignore rerun, because the withdrawal request(walletAccountId=%s) has the disallow dwStatus, %s.', $walletAccountId, $walletAccount->dwStatus); // 2 params
						$this->utils->debug_log($dbgMsg);
					}else{
						$token = $tokenList[$indexNumber];
						$processedTokenList[] = $token;
						$this->processPreCheckerWithToken($token, 'batch_supplement_run_process_pre_checker_in_queue');
					}
				}
			}
			$this->utils->debug_log('Processed the tokens,', $processedTokenList);


		} else {
			if($isOverWaitingTime){
				$this->utils->debug_log($func_name. ' is over waiting times.');
			}
			if($is_execing){
				$this->utils->debug_log($func_name. ' is already running.');
			}
		}

	} // EOF batch_supplement_run_process_pre_checker_in_queue

	/**
	 * To call processPreChecker() with queue token.
	 *
	 * @param string $token The field,"queue_results.token".
	 * @param null|string $triggerBy To appand the triggerBy into the field,"result" of the table,"queue_results".
	 *
	 */
	public function processPreCheckerWithToken($token, $triggerBy = null){
		$this->load->model(['wallet_model']);
		if( ! empty( $this->utils->getConfig('disabled_remote_processPreChecker_on_sbe') ) ){
            $this->utils->error_log('!!!donot allow processPreChecker by disabled_remote_processPreChecker_on_sbe in config!!!');
            return false;
        }

		if(empty($token)) {
    		$this->utils->error_log('processPreCheckerWithToken: EMPTY TOKEN PROVIDED');
    		return false;
		}
		$task = $this->queue_result->getResult($token);
		if(empty($task)){
	    	$this->utils->error_log('processPreCheckerWithToken: queue job does not exist. TOKEN:', $token);
    		return false;
		}
		// -- Get job's full parameters
	    $params = $this->utils->decodeJson($task['full_params']);

		if( !empty($params['walletAccountId']) ){
			$walletAccountId = $params['walletAccountId'];
			$resultInfo = [];
			$resultInfo['result'] = null;
			$walletAccount = $this->wallet_model->getWalletAccountBy($walletAccountId);
			$resultInfo['transactionCode'] = $walletAccount->transactionCode ;
			if( ! empty($triggerBy) ){
				$resultInfo['triggerBy'] = $triggerBy;
			}
			try{
				$this->processPreChecker($walletAccountId);
				$resultInfo['result'] = true;
				$resultInfo['msg'] = 'complete';
			} catch (Exception $e) {
				$formatStr = 'Exception in processPreChecker(). (%s)';
				$this->utils->error_log( sprintf( $formatStr, $e->getMessage() ) );
				$resultInfo['result'] = false;
				$resultInfo['msg'] = $e->getMessage();
			}
			$hasError = false;
			$isDone = true;
			$result = $resultInfo;
			if( ! $resultInfo['result'] ){
				$hasError = true;
			}

			$this->queue_result->updateResultWithCustomStatus($token, $result, $isDone, $hasError);
		}else{
			$this->utils->error_log('processPreCheckerWithToken: EMPTY walletAccountId PROVIDED. TOKEN:', $token);
    		return false;
		}

	}// EOF processPreCheckerWithToken

	/**
	 * execute send2Insvr4CreateAndApplyBonusMulti by $token
	 *
	 * @param string $token The field, queue_results.token .
	 * @return void
	 */
	public function send2Insvr4CreateAndApplyBonusMultiWithToken($token){
		$this->load->model(['promorules']);

		if(empty($token)) {
    		$this->utils->error_log('send2Insvr4CreateAndApplyBonusMultiWithToken: EMPTY TOKEN PROVIDED');
    		return false;
		}
		$task = $this->queue_result->getResult($token);
		if(empty($task)){
	    	$this->utils->error_log('send2Insvr4CreateAndApplyBonusMultiWithToken: queue job does not exist. TOKEN:', $token);
    		return false;
		}
		// -- Get job's full parameters
	    $params = $this->utils->decodeJson($task['full_params']);

		if( ! empty($params['promorulesId']) ){
			$promorulesId = $params['promorulesId'];
		}else{
			$this->utils->error_log('send2Insvr4CreateAndApplyBonusMultiWithToken: EMPTY promorulesId PROVIDED. TOKEN:', $token);
    		return false;
		}
		if( ! empty($params['playerId']) ){
			$playerId = $params['playerId'];
		}else{
			$this->utils->error_log('send2Insvr4CreateAndApplyBonusMultiWithToken: EMPTY playerId PROVIDED. TOKEN:', $token);
    		return false;
		}
		if( ! empty($params['playerPromoId']) ){
			$playerPromoId = $params['playerPromoId'];
		}else{
			$this->utils->error_log('send2Insvr4CreateAndApplyBonusMultiWithToken: EMPTY playerPromoId PROVIDED. TOKEN:', $token);
    		return false;
		}
		if( ! empty($promorulesId)
			&& ! empty($playerId)
			&& ! empty($playerPromoId)
		){
			$resultInfo = [];
			$resultInfo['result'] = null;
			try{

				$thePromorulesId = $promorulesId;
				$thePlayerId = $playerId;
				// $this->promorules->send2Insvr4CreateAndApplyBonusMulti($thePromorulesId, $thePlayerId, $playerPromoId);
				$this->promorules->send2Insvr4CreateAndApplyBonusMultiPreGameDescription($thePromorulesId, $thePlayerId, $playerPromoId);

				$resultInfo['result'] = true;
				$resultInfo['msg'] = 'complete';
			} catch (Exception $e) {
				$formatStr = 'Exception in send2Insvr4CreateAndApplyBonusMulti(). (%s)';
				$this->utils->error_log( sprintf( $formatStr, $e->getMessage() ) );
				$resultInfo['result'] = false;
				$resultInfo['msg'] = $e->getMessage();
			}
			$hasError = false;
			$isDone = true;
			$result = $resultInfo;
			if( ! $resultInfo['result'] ){
				$hasError = true;
			}

			$this->queue_result->updateResultWithCustomStatus($token, $result, $isDone, $hasError);
		}else{
			$this->utils->error_log('send2Insvr4CreateAndApplyBonusMultiWithToken: EMPTY params PROVIDED. TOKEN:', $token);
    		return false;
		}
	} // EOF send2Insvr4CreateAndApplyBonusMultiWithToken

    /**
     * Sends an alert to Mattermost once a private IP
     * of a player was detected
     *
     * @param  string $token job queue token
     * @return void
     */
    public function send_player_private_ip_mm_alert($token = null){

    	if(empty($token)) {
    		$this->utils->error_log('send_player_private_ip_mm_alert: EMPTY TOKEN PROVIDED');
    		return false;
    	}

    	$this->utils->debug_log('Commence sending of private IP alert to mattermost. METHOD: send_player_private_ip_mm_alert');

	    $this->load->helper('mattermost_notification_helper');
	    $this->load->model(['queue_result','player_model']);

	    $task = $this->queue_result->getResult($token);

	    if(empty($task)){
	    	$this->utils->error_log('send_player_private_ip_mm_alert: queue job does not exist. TOKEN:', $token);
    		return false;
	    }

	    // -- Get job's full parameters
	    $params = $this->utils->decodeJson($task['full_params']);

	    $final_params = array(
	    	'CURRENT URL' 		=> $params['current_url'],
	    	'PLAYER ID' 		=> $params['player_id'],
	    	'PLAYER USERNAME' 	=> $params['player_id'] ? $this->player_model->getUsernameById($params['player_id']) ?: "N/A" : "N/A",
	    	'ACTION TYPE' 		=> $params['http_request_type'],
	    	'IP ADDRESS' 		=> $params['ip_address'],
	    	'REFERRER' 			=> $params['referrer'],
	    	'DEVICE' 			=> $params['device'],
	    	'USER AGENT' 		=> $params['user_agent'],
	    	'IS MOBILE' 		=> $params['is_mobile'],
	    	'BROWSER TYPE' 		=> $params['browser_type'],
	    	'DATE TIME' 		=> $params['datetime'],
	    	'TIMEZONE' 			=> $params['timezone'],
	    	'APP PREFIX' 		=> $params['app_prefix'],
	    );

	    $this->utils->debug_log('Command > send_player_private_ip_mm_alert: PARAMS = ', $final_params);

	    // -- Check the most important fields first before continuing.
	    if(!isset($params['player_id'], $params['http_request_type'], $params['ip_address'], $params['app_prefix'])){

	    	// -- update failed queue result
			$message = 'Command > send_player_private_ip_mm_alert: Empty/Incomplete params provided. Job will not be processed. ';
			$isDone = true;
			$hasError = true;

			$result = array(
				'message' => $message,
				'final_params' => $final_params
			);

			$this->_sendPlayerPrivateIpMmAlert_updateQueueResult($token, $result, $isDone, $hasError);

			$this->utils->error_log($message.'PARAMS:', $final_params);
	    	return false;
	    }

	    // -- Start preparing MM alert message/content structure
		$message_header = ":warning: #ALERT_".date('Y_m')." #".str_replace("og_","",$params['app_prefix'])."_".date('Y_m')." \n"."App prefix: #".$params['app_prefix']."\n";

		$text = "Use of Private IP Address detected! Datetime: ".date('Y-m-d H:i:s')."\n";

		foreach ($final_params as $key => $value) {
			$text .= $key . ": " .$value . "\n";
		}

		$message_content = array(
	    	$message = array(
	    		'type' => 'danger',
	    		'text' => $text
	    	)
	    );

		sendNotificationToMattermost('Private IP Alert','private_ip_alert', $message_content, $message_header);

		// -- Update queue result status
		$result = $final_params;
		$result['MESSAGE HEADER'] = $message_header;
		$result['MESSAGE CONTENT'] = $message_content;

    	$this->utils->debug_log('Command > send_player_private_ip_mm_alert: FULL DETAILS = ', $result);
    	$this->utils->debug_log('Command > send_player_private_ip_mm_alert: TOKEN = ', $token);
		$this->_sendPlayerPrivateIpMmAlert_updateQueueResult($token, $result);
    }

    /**
	 * Update queue result of sending player's private IP to MM thru alert
	 * --
	 * @param  string  $token
	 * @param  array   $result
	 * @param  boolean $isDone
	 * @param  boolean $hasError
	 * @return void
	 */
	private function _sendPlayerPrivateIpMmAlert_updateQueueResult($token = null, $result = array(), $isDone = true, $hasError = false){

		if(empty($token)) return;

		$this->load->model('queue_result');

		$this->queue_result->updateResultWithCustomStatus($token, $result, $isDone, $hasError);

		if (!$hasError)
			$this->utils->debug_Log("send_player_private_ip_mm_alert  token:" . $token . " result: ",$result);
		else
			$this->utils->error_Log("send_player_private_ip_mm_alert  token:" . $token . " result: ",$result);
	}


	/**
	 * Syncs player KYC level id to playerdetails table
	 *
	 * @param int $chunk_limit player batch limit
	 * @return void
	 * @author Cholo Miguel Antonio
	 */
	public function syncPlayerKYCLevel($chunk_limit = 100){

		$this->utils->debug_log('START SYNCING OF PLAYER KYC LEVEL UNDER PLAYERDETAILS');

		if (! $this->utils->isEnabledFeature('show_kyc_status')){
			$this->utils->debug_log('ABORT syncPlayerKYCLevel: System feature disabled');
			return;
		}

		$total_affected_rows = $this->player_model->processAllPlayersByBatch(function(&$total_affected_rows, &$player_ids_array){

			$this->load->model(['player_kyc','player_model']);

			// -- update all player details kyc level
			foreach ($player_ids_array as $player_id_key => $player_id) {

				// -- get KYC status
				$kyc_level_id = $this->player_kyc->getPlayerCurrentKycStatus($player_id, true);

				$this->player_model->editPlayerDetails(array('kyc_status_id' => $kyc_level_id),$player_id);
				$total_affected_rows += $this->db->affected_rows();
			}

			// -- report if error occurs
			if($this->db->_error_message()){
				$this->utils->error_log('syncPlayerKYCLevel > ERROR:'. $this->db->_error_message());
				return FALSE;
			}

			$this->utils->debug_log('syncPlayerKYCLevel > On going... Updated player count: '. $total_affected_rows);

			return TRUE;

		}, $chunk_limit);

		$this->utils->debug_log('END SYNCING OF PLAYER KYC LEVEL UNDER PLAYERDETAILS');
		$this->utils->debug_log('Total number of player records updated: '. $total_affected_rows);

	}

	/**
	 * Syncs player Risk Score Level to playerdetails table
	 *
	 * @param int $chunk_limit player batch limit
	 * @return void
	 * @author Cholo Miguel Antonio
	 */
	public function syncPlayerRiskScoreLevel($chunk_limit = 100){

		$this->utils->debug_log('START SYNCING OF PLAYER RISK SCORE LEVEL UNDER PLAYERDETAILS');

		if (! $this->utils->isEnabledFeature('show_risk_score')){
			$this->utils->debug_log('ABORT syncPlayerRiskScoreLevel: System feature disabled');
			return;
		}

		$total_affected_rows = $this->player_model->processAllPlayersByBatch(function(&$total_affected_rows, &$player_ids_array){

			$this->load->model(['risk_score_model','player_model']);

			// -- update all player details risk score
			foreach ($player_ids_array as $player_id_key => $player_id) {

				$risk_score_info = $this->risk_score_model->generate_player_risk_score($player_id, false);
				$total_affected_rows += $this->db->affected_rows();
			}

			// -- report if error occurs
			if($this->db->_error_message()){
				$this->utils->error_log('syncPlayerRiskScoreLevel > ERROR:'. $this->db->_error_message());
				return FALSE;
			}

			$this->utils->debug_log('syncPlayerRiskScoreLevel > ON GOING... Updated player count: '. $total_affected_rows);

			return TRUE;

		}, $chunk_limit);

		$this->utils->debug_log('END SYNCING OF PLAYER RISK SCORE LEVEL UNDER PLAYERDETAILS');
		$this->utils->debug_log('Total number of player records updated: '. $total_affected_rows);

	}

	/**
	 * Synchronize all player proof attachment files status to attached_file_status
	 *
	 * @param int $chunk_limit player batch limit
	 * @return void
	 * @author Cholo Miguel Antonio
	 */
	public function syncPlayerAttachmentFileStatus($chunk_limit = 100){
		$this->utils->debug_log('START SYNCING OF PLAYER PROOF ATTACHMENT FILES STATUS TO ATTACHED_FILE_STATUS');

		$total_affected_rows = $this->player_model->processAllPlayersByBatch(function(&$total_affected_rows, &$player_ids_array){

			$this->load->model(['kyc_status_model', 'player_model', 'player_attached_proof_file_model']);

			// -- update all player details risk score
			foreach ($player_ids_array as $player_id_key => $player_id) {

				if($this->player_attached_proof_file_model->saveAttachedFileStatusHistory($player_id));
					$total_affected_rows++;
			}

			$this->utils->debug_log('syncPlayerAttachmentFileStatus > ON GOING, Please wait... Updated player count: '. $total_affected_rows);

			return TRUE;

		}, $chunk_limit);

		$this->utils->debug_log('END SYNCING OF PLAYER PROOF ATTACHMENT FILES STATUS TO ATTACHED_FILE_STATUS');
		$this->utils->debug_log('Total number of player records updated: '. $total_affected_rows);
	}

	/**
	 * OGP-11493 Batch update for all affiliates platform shares percentage
	 * This method will override all of the affiliates' platform shares percentage based on the currently set up
	 * common / default platform shares percentage
	 *
	 * @return void
	 * @author Cholo Miguel Antonio
	 */
	public function forceSyncAllAffiliatePlatformSharesPercentage(){

		$this->load->model(['affiliatemodel','external_system']);

		$common_settings = $this->affiliatemodel->getDefaultAffSettings(); # -- Get affiliate common settings

		if(!is_array($common_settings) || empty($common_settings)){
			$this->utils->error_log('Common setting was found empty', $common_settings);
			return false;
		}

		if(!isset($common_settings['platform_shares'])){
			$this->utils->error_log('Common platform shares percentage was not found', $common_settings);
			return false;
		}

		if(!isset($common_settings['level_master'])){
			$this->utils->error_log('Common master percentage was not found', $common_settings);
			return false;
		}

		$terms_table = 'affiliate_terms';

		$all_affiliates = $this->affiliatemodel->getAllAffiliates(); # -- all affiliates

		$affiliate_ids = array_column($all_affiliates, 'affiliateId');

		$affiliate_terms = $this->db->select('affiliateId, optionType, optionValue')->from($terms_table)->where_in('affiliateId', $affiliate_ids)->get()->result_array(); # -- affiliate terms

		$total_affected_rows = 0;

		$updated_affiliates = array();

		$decoded_terms = array();

		$new_platform_shares = $common_settings['platform_shares'];

		$level_master = $common_settings['level_master'];

		$this->utils->debug_log('START UPDATING / OVERRIDING ALL AFFILIATE PLATFORM SHARES PERCENTAGE');

		# -- Update each affiliate terms
		foreach ($affiliate_terms as $key => &$term) {

			if($term['optionType'] != 'all_settings') continue;

			$decoded_terms = json_decode($term['optionValue'], true);

			if(!is_array($decoded_terms)) continue;

			if(!isset($decoded_terms['platform_shares'])){
				$decoded_terms['platform_shares'] = $new_platform_shares;
			}

			# -- Force update of platform shares
			foreach ($new_platform_shares as $new_platform_share_key => $new_platform_share) {
				$decoded_terms['platform_shares'][$new_platform_share_key] = doubleval($new_platform_share);
			}

			# -- Force update of level master
			$decoded_terms['level_master'] = doubleval($level_master);

			$this->db->set('optionValue',json_encode($decoded_terms));
			$this->db->where('affiliateId',$term['affiliateId']);
			$this->db->where('optionType','all_settings');
			$this->db->update($terms_table);

			if($this->db->affected_rows() > 0){
				$total_affected_rows++;
				array_push($updated_affiliates, $term['affiliateId']);
			}

			$decoded_terms = array();
		}

		$this->utils->debug_log('DONE UPDATING / OVERRIDING OF ALL AFFILIATE PLATFORM SHARES PERCENTAGE. TOTAL UPDATE COUNT: '.$total_affected_rows, $updated_affiliates);

	}

	/**
	 * OGP-12100 Batch update of all games for all agents
	 *
	 * @return void
	 */
	public function bacth_update_agents_game_api($game_providers){
		$myquery = <<<EOD
select agent_id from agency_agent_game_platforms GROUP BY agent_id
EOD;
		$query = $this->db->query($myquery);
		$agents = $query->result_array($query);

		$game_providers = explode('.', urldecode($game_providers));

		foreach ($agents as $agent) {
			foreach ($game_providers as $gp){
				echo $agent['agent_id']. "-" .$gp;
				$this->check_if_game_api_exist_in_agent($agent['agent_id'],$gp);
			}
		}
	}

	public function check_if_game_api_exist_in_agent($agent_id,$game_platform_id){
		$this->db->select("agent_id");
		$this->db->where("agent_id",$agent_id);
		$this->db->where("game_platform_id",$game_platform_id);
        $qry = $this->db->get('agency_agent_game_platforms');
        $res = $qry && $qry->num_rows() > 0;

        if(!$res){
        	$this->db->insert("agency_agent_game_platforms", ["agent_id"=>$agent_id,"game_platform_id"=>$game_platform_id]);
        	$this->utils->debug_log('Inserted Agency Game Platform: '.$game_platform_id.' to Agent: '.$agent_id);
        }else{
			$this->utils->debug_log('Game Platform: '.$game_platform_id.' already exists to Agent: '.$agent_id);
		}
	}

	public function delete_hour_by_hour($table_name, $date_field, $start_day, $max_day,  $dry_run='false', $save_to_table=_COMMAND_LINE_NULL, $token=_COMMAND_LINE_NULL){

		$this->load->model(['player_model']);

		$player_model = $this->player_model;

		if($token != _COMMAND_LINE_NULL){
			$this->queue_result->appendResult($token, ['request_id'=>_REQUEST_ID, 'result'=> [$table_name, $date_field, $start_day, $max_day]] , false, false);

		}

		$end=new DateTime($max_day.' '.Utils::LAST_TIME);

		$start=new DateTime($start_day.' '.Utils::FIRST_TIME);

		$step='+1 hour';

		$dry_run=$dry_run=='true';

		$cnt = 0 ;

		$success=$this->utils->loopDateTimeStartEnd($start, $end, $step, function($from, $to, $step)
			use($table_name,$save_to_table, $date_field, $dry_run , &$cnt, $player_model, $token){

            // put it in mysql trans to prevent data loss
				$player_model->startTrans();
            // if not to save the data
				if($save_to_table != _COMMAND_LINE_NULL){
					$cnt = $cnt + $this->archiveDataBeforeDelete($from,$to,$table_name,$save_to_table,$date_field,$token,$dry_run=false);
				}

				$sql='DELETE FROM `'.$table_name.'` WHERE `'.$date_field."`>='".$this->utils->formatDateTimeForMysql($from)
				."' and ".$date_field."<='".$this->utils->formatDateTimeForMysql($to)."'";

				$this->utils->debug_log('try run sql', $sql, $from, $to, $step);
				$deletedNumber=0;
				if($dry_run){
    			//ignore
				}else{
					$this->db->query($sql);
					$deletedNumber=$this->db->affected_rows();
					sleep(1);
				}
				$this->utils->debug_log('after exec sql, affected_rows', $deletedNumber);

                //just in case error in trans occured
				if ($player_model->isErrorInTrans()){
					$client_tag = [
							':warning:',
							'#delete_and_archive_warning',
							'#'.str_replace("og_","",$this->_app_prefix),
							'#data_deletion'. $this->utils->formatYearMonthForMysql(new DateTime),
							'#job_'.$token
						];
				        $details = ['warning' => 'Deletion encountered TransError ', 'details' => ['from' => $from, 'to'=>$to, 'sourceTable'=>$table_name,'targetTable' =>$save_to_table, 'deletedNumber'=>$deletedNumber]  ];
						$msg = "Delete start time: ".$this->utils->getNowForMysql()." | Hostname: ". $this->utils->getHostname();
						$msg .= "\n";
						$msg .= "``` json \n".json_encode($details, JSON_PRETTY_PRINT)." \n```";
						$this->sendNotificationToMattermost('Delete and Archive Data', 'delete_table_data', $msg, 'warning', $client_tag);
				}

				$player_model->endTrans();

				return true;

			});

		if($success){
			$this->utils->debug_log('queue: '.$token.' done');
			$details = '';
			if($save_to_table != _COMMAND_LINE_NULL){
				$details = ['table' => $table_name, 'delete_process_status'=>'done','process_end_time'=>$this->utils->getNowForMysql(), 'deleted_counts' => $cnt];
			}else{
				$details = ['table' => $table_name, 'delete_process_status'=>'done','process_end_time'=>$this->utils->getNowForMysql(), 'deleted_counts' => 'cannot be counted -deleted not saved'];
			}
    		//done
			$this->queue_result->appendResult($token, ['request_id'=>_REQUEST_ID, 'result'=> $success, 'details'=> $details], true, false);
		}else{
			$this->utils->error_log('queue: '.$token.' failed');
    		//error
			$this->queue_result->appendResult($token, ['request_id'=>_REQUEST_ID, 'result'=> $success ], false, true);
		}
		if($token != _COMMAND_LINE_NULL){
			$client_tag = [
				':information_source:',
				'#'.str_replace("og_","",$this->_app_prefix),
				'#data_deletion'. $this->utils->formatYearMonthForMysql(new DateTime),
				'#job_'.$token
			];
			$deleted_counts = ($save_to_table != _COMMAND_LINE_NULL) ? $cnt : 'cannot be counted -delete only';
			$msg = "``` json \n".json_encode(['table' => $table_name, 'delete_process_status'=>'done','process_end_time'=>$this->utils->getNowForMysql(), 'deleted_counts' => $deleted_counts])." \n```";
			$this->sendNotificationToMattermost('Delete and Archive Data', 'delete_table_data', $msg, 'info', $client_tag);

		}
	}

	public function delete_minute_by_minute($table_name, $date_field, $startDateTime, $endDateTime, $sleepTimeSeconds=3, $dry_run='false', $save_to_table=_COMMAND_LINE_NULL, $token=_COMMAND_LINE_NULL){

		$this->load->model(['player_model']);

		$player_model = $this->player_model;

		if($token != _COMMAND_LINE_NULL){
			$this->queue_result->appendResult($token, ['request_id'=>_REQUEST_ID, 'result'=> [$table_name, $date_field, $start_day, $max_day]] , false, false);

		}

		$end=new DateTime($endDateTime);

		$start=new DateTime($startDateTime);

		$step='+1 minute';

		$dry_run=$dry_run=='true';

		$cnt = 0 ;

		$success=$this->utils->loopDateTimeStartEnd($start, $end, $step, function($from, $to, $step)
			use($table_name,$save_to_table, $date_field, $dry_run , $sleepTimeSeconds, &$cnt, $player_model, $token){

            // put it in mysql trans to prevent data loss
				$player_model->startTrans();
            // if not to save the data
				if($save_to_table != _COMMAND_LINE_NULL){
					$cnt = $cnt + $this->archiveDataBeforeDelete($from,$to,$table_name,$save_to_table,$date_field,$token,$dry_run=false);
				}

				$sql='DELETE FROM `'.$table_name.'` WHERE `'.$date_field."`>='".$this->utils->formatDateTimeForMysql($from)
				."' and ".$date_field."<='".$this->utils->formatDateTimeForMysql($to)."'";

				$this->utils->debug_log('try run sql', $sql, $from, $to, $step);
				$deletedNumber=0;
				if($dry_run){
    			//ignore
				}else{
					$this->db->query($sql);
					$deletedNumber=$this->db->affected_rows();
				}
				sleep($sleepTimeSeconds);
				$this->utils->debug_log('after exec sql, affected_rows', $deletedNumber, 'sleep', $sleepTimeSeconds);

				$player_model->endTrans();

                //just in case error in trans occured
				if ($player_model->isErrorInTrans()){
					$client_tag = [
							':warning:',
							'#delete_and_archive_warning',
							'#'.str_replace("og_","",$this->_app_prefix),
							'#data_deletion'. $this->utils->formatYearMonthForMysql(new DateTime),
							'#job_'.$token
						];
				        $details = ['warning' => 'Deletion encountered TransError ', 'details' => ['from' => $from, 'to'=>$to, 'sourceTable'=>$table_name,'targetTable' =>$save_to_table, 'deletedNumber'=>$deletedNumber]  ];
						$msg = "Delete start time: ".$this->utils->getNowForMysql()." | Hostname: ". $this->utils->getHostname();
						$msg .= "\n";
						$msg .= "``` json \n".json_encode($details, JSON_PRETTY_PRINT)." \n```";
						$this->sendNotificationToMattermost('Delete and Archive Data', 'delete_table_data', $msg, 'warning', $client_tag);
				}

				return true;

			});

		if($success){
			$this->utils->debug_log('queue: '.$token.' done');
			$details = '';
			if($save_to_table != _COMMAND_LINE_NULL){
				$details = ['table' => $table_name, 'delete_process_status'=>'done','process_end_time'=>$this->utils->getNowForMysql(), 'deleted_counts' => $cnt];
			}else{
				$details = ['table' => $table_name, 'delete_process_status'=>'done','process_end_time'=>$this->utils->getNowForMysql(), 'deleted_counts' => 'cannot be counted -deleted not saved'];
			}
    		//done
			$this->queue_result->appendResult($token, ['request_id'=>_REQUEST_ID, 'result'=> $success, 'details'=> $details], true, false);
		}else{
			$this->utils->error_log('queue: '.$token.' failed');
    		//error
			$this->queue_result->appendResult($token, ['request_id'=>_REQUEST_ID, 'result'=> $success ], false, true);
		}
		if($token != _COMMAND_LINE_NULL){
			$client_tag = [
				':information_source:',
				'#'.str_replace("og_","",$this->_app_prefix),
				'#data_deletion'. $this->utils->formatYearMonthForMysql(new DateTime),
				'#job_'.$token
			];
			$deleted_counts = ($save_to_table != _COMMAND_LINE_NULL) ? $cnt : 'cannot be counted -delete only';
			$msg = "``` json \n".json_encode(['table' => $table_name, 'delete_process_status'=>'done','process_end_time'=>$this->utils->getNowForMysql(), 'deleted_counts' => $deleted_counts])." \n```";
			$this->sendNotificationToMattermost('Delete and Archive Data', 'delete_table_data', $msg, 'info', $client_tag);

		}
	}

	public function batch_set_subwallet_to_zero($game_platform_id,$playerNames=_COMMAND_LINE_NULL){
		$this->load->model(['wallet_model']);

		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);

		if($playerNames==_COMMAND_LINE_NULL){
        	//get all game_platform players
			$playerNames = $this->wallet_model->getPlayerNamesByGamePlatformId($game_platform_id);

		}else{
			// no use
			// $playerNames =  array_filter(explode(" ", $playerNames))  ;
			$playerNames =  explode(" ", $playerNames);
		}
		if(!empty($playerNames )){
			foreach ($playerNames as $playerName) {
				$this->set_subwallet_to_zero($game_platform_id,trim($playerName),$api);
			}
		}

	}

	public function set_subwallet_to_zero($game_platform_id,$playerName=_COMMAND_LINE_NULL,$api=_COMMAND_LINE_NULL){

		$this->load->model(['player_model']);

		if($playerName==_COMMAND_LINE_NULL){
			$this->utils->error_log(' no playername inputted');
			return;
		}

		if($api==_COMMAND_LINE_NULL){
			$api = $this->utils->loadExternalSystemLibObject($game_platform_id);
		}

		$playerId = $this->player_model->getPlayerIdByUsername($playerName);
		if(empty($playerId)){
			$this->utils->error_log($playerName.' not exist');
			return;
		}
		$new_balance = 0;
		$api->setPlayerOldBlockedApiSubwalletToZeroWithLock($playerId,$new_balance);
	}

	/**
	 * check the amount diff between player total_total_nofrozen and playeraccount sum(totalBalanceAmount)
	 *
	*/
	public function fix_unsync_playeraccount($start_from = false) {
		# Get min & max createdOn from player table
		$this->utils->info_log('=========start fix_unsync_playeraccount=========');
		$this->load->model(['player_model', 'wallet_model']);
		$player_model = $this->player_model;
		$createdOn = $this->player_model->getPlayerListCreatedOn();
		$this->utils->info_log('====get min and max createdon===', $createdOn);

        if ($start_from) {
            $min = explode('-', $start_from);
            $this->utils->info_log('====set start from===', $start_from);
        } else {
            $min = explode('-', $createdOn->min);
        }

		$max = explode('-', $createdOn->max);

		for ($y=$min[0]; $y<=$max[0]; $y++) {
			if ($min[0] == $y) {
				$from = $start_from ? : $createdOn->min;
			} else {
				$from = "$y-01-01 00:00:00";
			}
			$to = "$y-12-31 23:59:59";

			# get diff player where total_total_nofrozen and playeraccount sum(totalBalanceAmount) by year
			$playerList = array();
			$playerList = $this->wallet_model->getPlayerIdsByCreatedOn($from, $to);

			$this->utils->info_log("====get playerId list from $from to $to===", $playerList);

			# sync the bigwallet to paymentaccount
			$failed_player=[];
			$cnt=0;
			if(!empty($playerList)){
				foreach ($playerList as $player) {
					$playerId = $player['playerId'];
					if(!empty($playerId)){
						$success=$this->wallet_model->lockAndTransForPlayerBalance($playerId, function()
							use($playerId){
							//lock
                            // $bigWallet=$this->wallet_model->getBigWalletByPlayerId($playerId);
                            // if(!empty($bigWallet)){
                                // return $this->wallet_model->updateBigWalletByPlayerId($playerId, $bigWallet);
                                return $this->wallet_model->initCreateAllWalletForRegister($playerId);
                                //initCreateAllWalletForRegister
                            // }
						});
						if($success){
							$cnt++;
							$this->utils->debug_log('refresh player: '.$playerId);
						}else{
							$this->utils->error_log('refresh player: '.$playerId.' failed');
							$failed_player[]=$playerId;
						}
					}
				}
			}

			if(!empty($failed_player)){
				$this->utils->error_log('failed_player', $failed_player);
			}

			$this->utils->info_log('done : '.$cnt);
		}

		$this->utils->info_log('=========end fix_unsync_playeraccount=========');

	}

	/**
	 * Command that will cancel the deleted promotion in withdraw_condition table
	 *
	 * @return void
	 */
	public function cancel_all_deleted_promotion_in_withdraw_condition()
	{
		$this->utils->info_log('========= start cancel deleted promotion =========');

		$this->load->model(['withdraw_condition']);

		$row_count = $this->withdraw_condition->cancelWithdrawalConditionOfDeletedPromotion();

		$this->utils->info_log('done, rows count is : '.$row_count);

		$this->utils->info_log('========= end cancel deleted promotion =========');
	}

	/**
	 * clear short player sessions
	 *
	 */
	function clear_short_player_sessions($timeoutSeconds=10800) {
		$this->load->model(array('player_model'));

		$sql = <<<EOD
delete from ci_player_sessions
where last_activity<=unix_timestamp()-?;
EOD;

		$rlt = 'delete ' . $this->player_model->runRawUpdateInsertSQL($sql, [$timeoutSeconds]) . " rows from ci_player_sessions";

		$this->utils->debug_log($rlt);
	}

	public function fix_totalplayer_of_aff_earnings_report($yearmonth = false){
        $this->load->library(array('affiliate_commission', 'user_agent'));

        try {
            if ($yearmonth) {
                //$yearmonth = $this->utils->getThisYearMonth();
				$this->affiliate_commission->generate_monthly_earnings_for_all($yearmonth,null,null,null,true);
				$message = lang("fix_totalplayer_of_aff_earnings_report for the Year Month of {$yearmonth} has been successfully fixed.");
            } else {
				$message = lang("fix_totalplayer_of_aff_earnings_report no Year Month given.");
			}
        } catch (Exception $e) {
            $message = $e->getMessage();
        }

		$this->utils->debug_log("==== {$message} ====");

	}

	/**
	 * Detect the related tasks in PS and waiting for the related tasks done.
	 *
	 * @param array $funcList The related tasks
	 * @param integer $maxWaitingTimes The Max waiting round.
	 * @param integer $waitingSec The waiting time as a round, unit:"sec".
	 * @return boolean $isOverWaitingTime If Over waiting time, return true. recomend give up to execute.
	 */
	public function isOverWaitingTimeWithWaitingByPS($funcList = [], $isExecingCB = null, $maxWaitingTimes = 35, $waitingSec = 60){
		$waitingTimeCounter = 0;
		$isOverWaitingTime = false;

		// detect the related tasks is executing?
		$currPS = null;
		$match = null;
		$is_execing4once_only = $this->isExecingListWithPS($funcList, $this->oghome, $isExecingCB, $currPS, $match);
// $this->utils->debug_log(__METHOD__, 'is_execing4once_only:', $is_execing4once_only, '$maxWaitingTimes:', $maxWaitingTimes, $currPS, $match);
		while( !!$is_execing4once_only
			&& $waitingTimeCounter < $maxWaitingTimes
		){
			$this->idleSec($waitingSec);
			$waitingTimeCounter++;
			// overide
			$is_execing4once_only = $this->isExecingListWithPS($funcList, $this->oghome, $isExecingCB, $currPS, $match);
		} // EOF while
// $this->utils->debug_log(__METHOD__, 'waitingTimeCounter:', $waitingTimeCounter, '$maxWaitingTimes:', $maxWaitingTimes);
		if( $waitingTimeCounter >= $maxWaitingTimes ){
			$isOverWaitingTime = true; // for give up
			$this->utils->debug_log(__METHOD__, 'Over Max Waiting Times,  $currPS:', $currPS, '$match:', $match, '$funcList:', $funcList);
		}
		return $isOverWaitingTime;
	} // EOF isOverWaitingTimeWithWaitingByPS

	/**
	 * Check the tasks is running in list?
	 * for example, "batch_player_level_upgrade" and "checkReferral" cron jobs:
	 * cli, ps aux|grep -E "\bbatch_player_level_upgrade\b|\bcheckReferral\b"
	 *
	 * Reference to https://www.thegeekstuff.com/2011/01/advanced-regular-expressions-in-grep-command-with-10-examples-%E2%80%93-part-ii/
	 *
	 * @param array $grepStrList The functions of Command class in file,"admin/application/controllers/cli/command.php". for detect ps.
	 * @param string $preStr The pe-string for make sure running a cronjob per client. usually with oghome. ex, "/home/vagrant/Code/{CLIENT_STRING}/og_sync"
	 * @param callable  $isExecingCB {
	 * 	@param array $match
	 *  @return boolean will be return of isExecingListWithPS().
	 * } The rule for check isExecing value.
	 * @param string $currPS To catch for debug and confirm work.
	 * @param array $match To catch for debug and confirm work.
	 * @param string $func_name The functin name of the caller.
	 * @param string $suffix4mdb The suffix in mdb. ex:__MDB_SUFFIX_STRING__thb
	 * @return boolean true means running in bg else false.
	 */
	function isExecingListWithPSWithMDB($grepStrList=[], $preStr = '', $isExecingCB = null, &$currPS = null, &$match = null, $func_name, $suffix4mdb = '', $scriptType = 'sh'){
		$isExecing = false;

		$grepList = [];
		if( ! empty($grepStrList) ){
			foreach($grepStrList as $indexNumber => $grepStr ){
				$grepList[] = sprintf('\b%s\b',$grepStr);
			}
			$grepImploded = implode('|', $grepList);
			$cmd ='ps aux|grep -E "'. $grepImploded.'" ';
			$cmd .= '|grep -v -e "chmod a" '; // filted "chmod a+w /home/vagr..."
		}else{
			$cmd ='ps aux';
		}

		// Example #2 popen() example, Ref. to https://www.php.net/popen
		$handle = popen($cmd.' 2>&1', 'r');
		$read = '';
		// Example #3 Remote fread() examples in the url,"https://www.php.net/manual/en/function.fread.php".
		while (!feof($handle)) {
			$read .= fread($handle, 1024);
		}
		pclose($handle);
		$currPS = $read; // catch for debug
		$grepStr = '.*';
		if( ! empty($suffix4mdb) ){
			/// MDB_SUFFIX_STRING_IN_CMD, db::getOgTargetDB().
			// ex: __MDB_SUFFIX_STRING__thb
			if($scriptType == 'php'){
				$grepStr = '[\S ]+';
				$grepStr .= $func_name;
				$grepStr .= '[\S ]+';
				$grepStr .= $suffix4mdb;
			}else{
				$grepStr = $func_name;
				$grepStr .= '[\S ]+';
				$grepStr .= $suffix4mdb;
			}
		}

		$pattern = '~'. $preStr. '[\S ]+shell[\S ]+\.'. $scriptType. ' '. $grepStr. '~';
		preg_match_all($pattern, $read, $match);
// $theCallTrace = $this->utils->generateCallTrace();
$this->utils->debug_log(__METHOD__, '--------------cmd', $cmd
	, 'currPS', $currPS
	, 'match', $match
	, 'pattern:', $pattern
	, 'preStr:', $preStr
	, 'grepStr:', $grepStr);
		if( !empty($match) ){
			if( is_null($isExecingCB) ){
				$isExecingCB = function ($match) {
					$isExecing = false;
					if(count($match[0]) > 0){ // if has data ,should be same func bg ps.
						$isExecing = true;
					}
					return $isExecing;
				}; // EOF $isExecingCB
			}


			$isExecing = call_user_func_array($isExecingCB, [$match]);
		} //EOF if( !empty($match) ){...

		return $isExecing;
	} // EOF isExecingListWithPSWithMDB

	/**
	 * Check the tasks is running in list?
	 * for example, "batch_player_level_upgrade" and "checkReferral" cron jobs:
	 * cli, ps aux|grep -E "\bbatch_player_level_upgrade\b|\bcheckReferral\b"
	 *
	 * Reference to https://www.thegeekstuff.com/2011/01/advanced-regular-expressions-in-grep-command-with-10-examples-%E2%80%93-part-ii/
	 *
	 * @param array $grepStrList The functions of Command class in file,"admin/application/controllers/cli/command.php". for detect ps.
	 * @param string $preStr The pe-string for make sure running a cronjob per client. usually with oghome. ex, "/home/vagrant/Code/{CLIENT_STRING}/og_sync"
	 * @param callable  $isExecingCB {
	 * 	@param array $match
	 *  @return boolean will be return of isExecingListWithPS().
	 * } The rule for check isExecing value.
	 * @param string $currPS To catch for debug and confirm work.
	 * @param array $match To catch for debug and confirm work.
	 * @return boolean true means running in bg else false.
	 */
	function isExecingListWithPS($grepStrList=[], $preStr = '', $isExecingCB = null, &$currPS = null, &$match = null){
		$isExecing = false;

		$grepList = [];
		if( ! empty($grepStrList) ){
			foreach($grepStrList as $indexNumber => $grepStr ){
				$grepList[] = sprintf('\b%s\b',$grepStr);
			}
			$grepImploded = implode('|', $grepList);
			$cmd ='ps aux|grep -E "'. $grepImploded.'" ';
			$cmd .= '|grep -v -e "chmod a" '; // filted "chmod a+w /home/vagr..."
		}else{
			$cmd ='ps aux';
		}

		// Example #2 popen() example, Ref. to https://www.php.net/popen
		$handle = popen($cmd.' 2>&1', 'r');
		$read = '';
		// Example #3 Remote fread() examples in the url,"https://www.php.net/manual/en/function.fread.php".
		while (!feof($handle)) {
			$read .= fread($handle, 1024);
		}
		pclose($handle);
		$currPS = $read; // catch for debug
		$grepStr = '.*';
		$pattern = '~'. $preStr. '.*cli/command/'. $grepStr. '~';
		preg_match_all($pattern, $read, $match);
// $theCallTrace = $this->utils->generateCallTrace();
// $this->utils->debug_log(__METHOD__, '$theCallTrace', $theCallTrace);
$this->utils->debug_log(__METHOD__, '$cmd', $cmd, '$currPS', $currPS, '$match', $match);
		if( !empty($match) ){
			if( is_null($isExecingCB) ){
				$isExecingCB = function ($match) {
					$isExecing = false;
					if(count($match[0]) > 0){ // if has data ,should be same func bg ps.
						$isExecing = true;
					}
					return $isExecing;
				}; // EOF $isExecingCB
			}

// $theCallTrace = $this->utils->generateCallTrace();
// $this->utils->debug_log(__METHOD__, '$theCallTrace', $theCallTrace);
			$isExecing = call_user_func_array($isExecingCB, [$match]);
		} //EOF if( !empty($match) ){...
// $theCallTrace = $this->utils->generateCallTrace();
// $this->utils->debug_log(__METHOD__, '$theCallTrace', $theCallTrace);
// $this->utils->debug_log(__METHOD__, '$currPS', $currPS, '$match', $match);

		return $isExecing;
	} // EOF isExecingListWithPS()

	/**
	 * Check the task is running?
	 * cli, ps aux|grep sync_exists_player_player_relay
	 *
	 *
	 * @param string $grepStr function of Command class in file,"admin/application/controllers/cli/command.php". for detect ps.
	 * @param string $preStr The pre-string for make sure running a cronjob per client. usually with oghome. ex, "/home/vagrant/Code/{CLIENT_STRING}/og_sync"
	 * @return boolean true means running in bg else false.
	 */
	function isExecingWithPS($grepStr='sync_exists_player_player_relay', $preStr = ''){
		$isExecing = false;

		$cmd ='ps aux|grep '. $grepStr;
		// Example #2 popen() example, Ref. to https://www.php.net/popen
		$handle = popen($cmd.' 2>&1', 'r');
		$read = '';
		// Example #3 Remote fread() examples in the url,"https://www.php.net/manual/en/function.fread.php".
		while (!feof($handle)) {
			$read .= fread($handle, 1024);
		}
		pclose($handle);

		$pattern = '~'. $preStr. '.*cli/command/'. $grepStr. '~';
		preg_match_all($pattern, $read, $match);
		if( !empty($match) ){
			if(count($match[0]) > 1){ // if two ,should be shelf and same func bg ps.
				$isExecing = true;
			}
		}
		return $isExecing;
	} // EOF isExecingWithPS

	public function sync_game_logs_structure_to_new($newTableName=null){
		$this->load->model(['player_model']);
		$rlt=$this->player_model->syncGameLogsStructureToNewTable($newTableName);
		$this->utils->debug_log('syncGameLogsStructureToNewTable result', $rlt);
	}

	public function adjust_id_of_game_logs_new($newTableName=null){
		$this->load->model(['player_model']);
		$rlt=$this->player_model->adjustIdOfGameLogsNew($newTableName);
		$this->utils->debug_log('adjustIdOfGameLogsNew result', $rlt);
	}

	public function sync_game_logs_structure_to_new_and_adjust_id($newTableName=null){
		$this->sync_game_logs_structure_to_new($newTableName);
		$this->adjust_id_of_game_logs_new($newTableName);
	}

	/**
	 * Refresh all Players in specific game provider, game API should not block
	 *
	 * @param int $gamePlatformId the game Platform ID
	 *
	 * @return int $walletCountRefreshed count of wallet refreshed
	 */
	public function refresh_all_player_balance_in_specific_game_provider($gamePlatformId=_COMMAND_LINE_NULL, $onlyRegistered = false){
		$this->load->model(['wallet_model','external_system','player_model','game_provider_auth']);


		if($gamePlatformId == _COMMAND_LINE_NULL){
			$this->utils->error_log('Game Platform ID must not empty');
			return false;
		}

		# check if game API is block in SBE
		$isGameApiEnable = $this->external_system->isAnyEnabledApi([$gamePlatformId]);
		$gamePlatformName = $this->external_system->getSystemName($gamePlatformId);

		if(! $isGameApiEnable){

			$this->utils->error_log('Game Platform is block in SBE, game platform name is: >>>>>>>>',$gamePlatformName,'Game Platform ID is: ',$gamePlatformId);
			return false;
		}

		$api = $this->utils->loadExternalSystemLibObject($gamePlatformId);

		$playerNames = [];
		if($onlyRegistered){
			$this->utils->error_log('refresh_all_player_balance_in_specific_game_provider getAllGameRegisteredPlayerUsername');
			$players = $this->game_provider_auth->getAllGameRegisteredPlayerUsername($gamePlatformId);
			$playerNames = (is_array($players) && count($players)>0) ? $players : [];
		}else{
			$this->utils->error_log('refresh_all_player_balance_in_specific_game_provider getPlayerNamesByGamePlatformId');
			$players = $this->wallet_model->getPlayerNamesByGamePlatformId($gamePlatformId);
			$playerNames = (is_array($players) && count($players)>0) ? $players : [];
		}


		$walletCountRefreshed = 0;

		if(count($playerNames) > 0){
			$self = $this;
			foreach($playerNames as $name){

				$apiResult = $api->queryPlayerBalance($name);

				if(isset($apiResult['unimplemented']) && $apiResult['unimplemented']){
					$this->utils->error_log('refresh_all_player_balance_in_specific_game_provider failed because queryPlayerBalance is unimplemented with this API : >>>>>>>>',$gamePlatformName);
					break;
				}

				if(isset($apiResult['success']) && $apiResult['success']){
					$playerId = $this->player_model->getPlayerIdByUsername($name);
					if(isset($apiResult['balance']) && ! is_null($apiResult['balance'])){

						$balance = $apiResult['balance'];

						$this->wallet_model->lockAndTransForPlayerBalance($playerId,function() use($self,$playerId,$balance,$gamePlatformId){
							$self->CI->wallet_model->refreshSubWalletOnBigWallet($playerId, $gamePlatformId, $balance);
							return true;
						});
						$walletCountRefreshed++;
					}
				}else{
					$this->utils->error_log('refresh_all_player_balance_in_specific_game_provider failed for this player: >>>>>>>>',$name);
					continue;
				}
				# sleep for 1 second, to prevent timeout
				sleep(1);
			}
		}

		$this->utils->info_log('refresh_all_player_balance_in_specific_game_provider wallet count refresh is: >>>>>>>>',$walletCountRefreshed);

		return $walletCountRefreshed;
	}

	//sudo ./admin/shell/command.sh refresh_all_player_balance_in_specific_game_provider_withbalance_registered 8 0 1 0
	public function refresh_all_player_balance_in_specific_game_provider_withbalance_registered($gamePlatformId=_COMMAND_LINE_NULL, $balance = 0, $isRegistered = 1, $startWithPlayer = 0){
		$this->load->model(['wallet_model','external_system','player_model','game_provider_auth']);
		if($gamePlatformId == _COMMAND_LINE_NULL){
			$this->utils->error_log('Game Platform ID must not empty');
			return false;
		}

		# check if game API is block in SBE
		$isGameApiEnable = $this->external_system->isAnyEnabledApi([$gamePlatformId]);
		$gamePlatformName = $this->external_system->getSystemName($gamePlatformId);

		if(! $isGameApiEnable){

			$this->utils->error_log('Game Platform is block in SBE, game platform name is: >>>>>>>>',$gamePlatformName,'Game Platform ID is: ',$gamePlatformId);
			return false;
		}

		$api = $this->utils->loadExternalSystemLibObject($gamePlatformId);

		$playerNames = [];
		$this->utils->info_log('refresh_all_player_balance_in_specific_game_provider_withbalance_registered getAllGamePlayerUsernameBalanceAndRegistered',
		'gamePlatformId', $gamePlatformId,
		'balance', $balance,
		'isRegistered', $isRegistered,
		'startWithPlayer', $startWithPlayer);
		$players = $this->game_provider_auth->getAllGamePlayerUsernameBalanceAndRegistered($gamePlatformId, $balance, $isRegistered, $startWithPlayer);
		$playerNames = (is_array($players) && count($players)>0) ? $players : [];


		$walletCountRefreshed = 0;

		if(count($playerNames) > 0){
			$self = $this;
			foreach($playerNames as $playerInfo){
				$name = $playerInfo->username;

				$apiResult = $api->queryPlayerBalance($name);

				if(isset($apiResult['unimplemented']) && $apiResult['unimplemented']){
					$this->utils->error_log('refresh_all_player_balance_in_specific_game_provider_withbalance_registered failed because queryPlayerBalance is unimplemented with this API : >>>>>>>>',$gamePlatformName);
					break;
				}

				if(isset($apiResult['success']) && $apiResult['success']){
					$playerId = $this->player_model->getPlayerIdByUsername($name);
					if(isset($apiResult['balance']) && ! is_null($apiResult['balance'])){

						$balance = $apiResult['balance'];

						$this->wallet_model->lockAndTransForPlayerBalance($playerId,function() use($self,$playerId,$balance,$gamePlatformId){
							$self->CI->wallet_model->refreshSubWalletOnBigWallet($playerId, $gamePlatformId, $balance);
							return true;
						});
						$walletCountRefreshed++;
					}
				}else{
					$this->utils->error_log('refresh_all_player_balance_in_specific_game_provider_withbalance_registered failed for this player: >>>>>>>>',$name);
					continue;
				}
				# sleep for 1 second, to prevent timeout
				sleep(1);
			}
		}

		$this->utils->info_log('refresh_all_player_balance_in_specific_game_provider_withbalance_registered wallet count refresh is: >>>>>>>>',$walletCountRefreshed);

		return $walletCountRefreshed;
	}

	public function change_and_sync_players_password($username, $password=_COMMAND_LINE_NULL, $pass_length=_COMMAND_LINE_NULL,$mm_key=_COMMAND_LINE_NULL,$tag=_COMMAND_LINE_NULL){

		$this->load->library('salt');
		$this->load->model(['player_model']);

		$player_id = $this->player_model->getPlayerIdByUsername($username);

		if(empty($player_id)){
			$this->utils->error_log($username.' not found');
			return;
		}
		if($pass_length == _COMMAND_LINE_NULL){
			$pass_length=7;
		}
		if($password == _COMMAND_LINE_NULL){
			$password = $this->utils->generate_password_no_special_char($pass_length);
		}
		$hash = $this->salt->encrypt($password, $this->getDeskeyOG());
		$update_data = array('password' => $hash);
		$this->player_model->resetPassword($player_id, $update_data);
		//for mm sending
		$mm_data['username'] = $username;
		$mm_data['password'] = $password;
		$game_platform_ids = $this->utils->getGameSystemMap();
		foreach ($game_platform_ids  as $game_platform_id) {
			$this->sync_password($game_platform_id, $username);
		}
		$this->utils->info_log('changed player password',$mm_data);

		if($mm_key != _COMMAND_LINE_NULL){
			$this->load->helper('mattermost_notification_helper');
			$channel =  $mm_key;
			$texts_and_tags ='#'.__FUNCTION__.(new Datetime())->format('Ymd').' #year_mo_'.(new Datetime())->format('Ym');
			if($tag != _COMMAND_LINE_NULL){
				$texts_and_tags ='#'.__FUNCTION__.(new Datetime())->format('Ymd').' #year_mo_'.(new Datetime())->format('Ym').' #'.$tag;
			}
			$notif_message = array(array('text' => "```json\n".json_encode($mm_data)."\n```",'type' => 'info'));
			sendNotificationToMattermost(__FUNCTION__, $channel,$notif_message,$texts_and_tags);
		}
	}

	public function batch_change_and_sync_players_password($config_key){
		// $config['sample']=
		// "password_length" =>null,
		// 'mm_key' => 'test_mattermost_notif',
		// 'tag' => 'local'
		// 'players' =>[
		// 		[
		// 	 "username" => "testplayer",
		// 	 "password"=> "4AOYNG" // null
		// 	],
		//     []...
	    //    ];
		$config = $this->utils->getConfig($config_key);
		if(empty($config)){
			$this->utils->error_log('config key not found');
			return;
		}
		if(!isset($config['players'])){
			$this->utils->error_log('players not set in config');
			return;
		}
		$players=$config['players'];
		$tag=$mm_key=$pass_length=_COMMAND_LINE_NULL;
		if(isset($config['tag'])){
			$tag=$config['tag'];
		}
		if(isset($config['pass_length'])){
			$pass_length=$config['pass_length'];
		}
		if(isset($config['mm_key'])){
			$mm_key=$config['mm_key'];
		}
		foreach ($players as $player) {
			if(isset($player['username'])){
				$parts = preg_split('/\s+/', $player['username']);
				if(count($parts) > 1){
					$this->utils->error_log('please check your config at ',$player);
					continue;
				}
				$username=$player['username'];
				$password=$player['password'];
				if(!isset($player['password'])){
					$password=_COMMAND_LINE_NULL;
				}
				$this->change_and_sync_players_password($username,$password,$pass_length,$mm_key,$tag);
			}else{
				$this->utils->error_log('please check your config at ',$player);
				continue;
			}
		}
	}

	/**
	 * overview : update operator_settings mail_smtp_password
	 */
	public function updateSmtpPassword() {
		$this->db->select('name,value');
		$this->db->from('operator_settings');
		$this->db->where('operator_settings.name', 'mail_smtp_password');
		$query = $this->db->get();
		$list = $query->result_array();
		$originPassword= $list[0]['value'];
		$error = "";
		$encryptPassword = $this->utils->encryptPassword($originPassword, $error);
		$this->utils->debug_log('updateSmtpPassword origin password', $encryptPassword);
		$this->utils->debug_log('updateSmtpPassword origin error', $error);
		$this->db->set("operator_settings.value", $encryptPassword);
		$this->db->where('operator_settings.name', 'mail_smtp_password');
		$this->db->update('operator_settings');

		$result = $this->db->affected_rows();
		echo "$result smpt password has been updated";
	}

	public function refresh_all_payment_accounts_total_daily_deposit_count_daily() {
		$this->CI->load->model(array('payment_account'));

		$all_payment_accounts = $this->payment_account->getAllPaymentAccount();
		foreach ($all_payment_accounts as $each_payment_account) {
			$this->utils->debug_log('start refreshing payment account daily_deposit_count, id: ', $each_payment_account['id']);
			$result = $this->payment_account->resetDailyDepositCount($each_payment_account['id']);
			$this->utils->debug_log('refreshing payment account daily_deposit_count result: ', $result);
		}
		$this->utils->info_log('======= Refresh all payment accounts daily_deposit_count Done. =======');
	}

	public function fixNoneWalletAccountPlayers($date) {
		if(empty($date)) {
			$this->utils->info_log('======= please enter date =======');
			exit;
		}
		// $date formate ex:'2020-04-28 00:00:00'
		$this->utils->info_log('======= fixNoneWalletAccountPlayers Start. =======');
		$this->CI->load->model(array('wallet_model'));
		$players = $this->wallet_model->getNoneWalletAccountPlayers($date);
		$this->utils->info_log("====playerList last sql", $this->db->last_query());
		$this->utils->debug_log('getNoneWalletAccountPlayers, ids: ', $players);
		$controller = $this;
		if(!empty($players)){
			foreach ($players as $key => $player) {
				$playerId = $player['playerId'];
				$this->utils->debug_log('sync wallet, player: ', $playerId);
				$this->wallet_model->lockAndTransForPlayerBalance($playerId, function () use ($controller,$playerId) {
					return $this->wallet_model->refreshBigWalletOnDB($playerId, $this->db);
					// return $this->wallet_model->initCreateAllWalletForRegister($playerId);
				});

			}
		}
		$this->utils->info_log('======= fixNoneWalletAccountPlayers End =======');
	}

	public function exportSubwalletBalanceToCsv($subWalletId){

		$this->load->model(['wallet_model','game_provider_auth']);

		$players = $this->wallet_model->getPlayerNamesIdByGamePlatformId($subWalletId);

		$total_player_cnt = 0;

		if(empty($players)){
			return $this->utils->error_log('empty player list');
		}

		$total_player_cnt = count($players);

        //record job
		$lang=null;
		$funcName=__FUNCTION__;
		$caller=0;
		$callerType=Queue_result::CALLER_TYPE_SYSTEM;
		$state=null;
		$log_filepath = null;

		$remote_log_file_path = $this->utils->getSharingUploadPath('/remote_logs');
		$params=['func_name'=>__FUNCTION__,'remote_log_file_path'=>$remote_log_file_path];
		$token=  $this->createQueueOnCommand($funcName, $params,$lang , $callerType, $caller, $state);

		$csv_headers=[
			'playerId',
			'username',
			'game_name',
			'walletId',
			'balance',
		];
		$this->utils-> _appendSaveDetailedResultToRemoteLog($token, __FUNCTION__, [], $log_filepath, true, $csv_headers);

		$written_cnt=1;
		foreach ($players as $playerId => $username) {

			$wallet_row = $this->wallet_model->getPlayerBalanceInDB($subWalletId, $playerId);
			$login_name = $this->game_provider_auth->getGameUsernameByPlayerId($playerId,$subWalletId);

			$playerDetails=[
				'playerId'=> $playerId,
				'username' => $username,
				'game_name'=> $login_name,
				'wallet_id' => $subWalletId,
				'balance'=>$wallet_row['balance'],
			];
			$this->utils-> _appendSaveDetailedResultToRemoteLog($token, __FUNCTION__, $playerDetails, $log_filepath, true, []);

			$this->utils->debug_log('log_filepath',$log_filepath);
			$this->utils->info_log('written '.$written_cnt.' out of '.$total_player_cnt);

			$written_cnt++;

		}
		$rlt=['success'=>true,'log_filepath'=>site_url().'remote_logs/'.basename($log_filepath)];
		$this->queue_result->updateResult($token, $rlt);

	}

	public function batch_scan_timeout_transfer_request_then_go_maintenance_cronjob($minutes=-1){
		//one minute
		set_time_limit(60);

		//default is last 10 minutes
		$scan_timeout_transfer_request_minutes=$this->utils->getConfig('scan_timeout_transfer_request_minutes');
		if($minutes<=0){
			$minutes=$scan_timeout_transfer_request_minutes;
		}
		$this->utils->debug_log('scan last '.$minutes.' minutes');
		$from=$this->utils->formatDateTimeForMysql(new DateTime('-'.$minutes.' minutes'));

		$this->batch_scan_timeout_transfer_request_then_go_maintenance($from);
	}

	public function batch_scan_timeout_transfer_request_then_go_maintenance($from=null, $to=null){
		if(empty($to)){
			$to=$this->utils->getNowForMysql();
		}
		if(empty($from)){
			$minutes=$this->utils->getConfig('scan_timeout_transfer_request_minutes');
			//last x minutes
			$from=$this->utils->formatDateTimeForMysql((new DateTime($to))->modify('-'.$minutes.' minutes'));
		}

		$this->utils->debug_log('batch_scan_suspicious_transfer_request', $from, $to);

		$this->load->model(['wallet_model']);
		$this->wallet_model->scanTimeTransferRequestThemGoMaintenance($from , $to);

	}

	public function add_lucky_code_period($start_day, $end_day, $period_name){
		$this->load->model(['lucky_code']);
		$this->lucky_code->addLuckyCodePeriod($start_day, $end_day, $period_name);
	}

	public function update_lucky_code_period($Id, $start_date, $end_date, $status=1, $period_name=null){
		$this->load->model(['lucky_code']);
		$this->lucky_code->updateLuckyCodePeriod($Id, $start_date, $end_date, $status, $period_name);
	}

	public function generate_lucky_code($start_time=null, $end_time=null, $order_ids=null){
		$this->load->model(['lucky_code']);

		if($end_time == null && $start_time == null){
			$end_time = date('Y-m-d H:00:00');
			$start_time = date('Y-m-d H:00:00', strtotime('-1 Hour', strtotime($end_time)));
		}else{
			if (strtotime($start_time) > strtotime($end_time)) {
				$this->utils->debug_log('====generate_lucky_code date wrong====', ['start_time'=> $start_time, 'end_time'=> $end_time, 'order_ids'=> $order_ids]);
				exit;
			}
		}

		$this->utils->debug_log('====generate_lucky_code start====', ['start_time'=> $start_time, 'end_time'=> $end_time, 'order_ids'=> $order_ids]);

		if($order_ids == null){
			//check the day is in the period
			$period = $this->lucky_code->getLuckyCodePeriod($start_time, $end_time);
			if(empty($period)){
				$this->utils->debug_log('generate_lucky_code', 'not in period or beyond one period');
				exit;
			}

			$period_id = $period->id;
			$code_period = $this->numberToEnglishWord($period_id);

			$sale_orders = $this->lucky_code->getSaleOrdersWithPeriod($start_time, $end_time);
			$this->utils->debug_log('====sale_orders====', ['sale_orders'=>$sale_orders]);
			if(empty($sale_orders)){
				$this->utils->debug_log('generate_lucky_code', 'no sale order');
			}else{
				$hasluckycodeorders = $this->lucky_code->getHasLuckyCodeOrder($start_time, $end_time);
				if(empty($hasluckycodeorders)){
					$orders = [];
				}else{
					$orders = array_column($hasluckycodeorders, 'trans_id');
				}

				foreach($sale_orders as $key => $val){
					if(!in_array($val['id'], $orders)){
						if(!$this->utils->getConfig('per_amount_for_lucky_code')){
							$this->utils->debug_log('====no set per_amount_for_lucky_code====');
							exit;
						}
						$this->utils->debug_log('====generate order lucky code start====', ['order_id' => $val['id']]);
						$piece = intval($val['amount'] / $this->utils->getConfig('per_amount_for_lucky_code'));
						$LastLuckyCodeId = $this->lucky_code->generateLuckyCode($piece, $val, $period_id, $code_period);
						$this->utils->debug_log('====generate order lucky code end====', ['order_id' => $val['id']]);
					}
				}
			}
		}else{
			$order_ids = str_replace(' ', ',', $order_ids);
			//check the day is in the period
			$period = $this->lucky_code->getLuckyCodePeriod($start_time, $end_time);
			if(empty($period)){
				$this->utils->debug_log('generate_lucky_code', 'not in period or beyond one period');
				exit;
			}

			$period_id = $period->id;
			$code_period = $this->numberToEnglishWord($period_id);

			$sale_orders = $this->lucky_code->getSaleOrdersWithPeriod($start_time, $end_time, $order_ids);
			$this->utils->debug_log('====sale_orders====', ['sale_orders'=>$sale_orders]);
			if(empty($sale_orders)){
				$this->utils->debug_log('generate_lucky_code', 'no sale order or has lucky code');
			}else{
				$hasluckycodeorders = $this->lucky_code->getHasLuckyCodeOrder($start_time, $end_time);
				if(empty($hasluckycodeorders)){
					$orders = [];
				}else{
					$orders = array_column($hasluckycodeorders, 'trans_id');
				}

				foreach($sale_orders as $key => $val){
					if(!in_array($val['id'], $orders)){
						if(!$this->utils->getConfig('per_amount_for_lucky_code')){
							$this->utils->debug_log('====no set per_amount_for_lucky_code====');
							exit;
						}
						$this->utils->debug_log('====generate order lucky code start====', ['order_id' => $val['id']]);
						$piece = intval($val['amount'] / $this->utils->getConfig('per_amount_for_lucky_code'));
						$LastLuckyCodeId = $this->lucky_code->generateLuckyCode($piece, $val, $period_id, $code_period);
						$this->utils->debug_log('====generate order lucky code end====', ['order_id' => $val['id']]);
					}
				}
			}
		}
	}

	public function deleteLuckyCode($player_id = null, $trans_id = null){
		$this->load->model(['lucky_code']);
		$this->lucky_code->deleteLuckyCode($player_id, $trans_id);

	}

	public function fix_player_last_transaction($player_id = null, $startDate = null, $dryRun = false){
		$this->load->model(['transactions']);
		$this->transactions->fixPlayerLastTransaction($player_id, $startDate, $dryRun);
	}

	public function numberToEnglishWord($number) {
		$letters = '';
		while ($number > 0) {
			$remainder = ($number - 1) % 26;
			$letters = chr(65 + $remainder) . $letters; // 65 is A' ASCII code
			$number = intval(($number - $remainder) / 26);
		}
		return $letters;
	}

	/**
	 * Get the  player list, the players had no do deposit during a date time range.
	 *
	 * The CMD usages,
	 * - Default,
	 * sudo /bin/bash admin/shell/command.sh no_deposit_player_by_date_range >> logs/cronjob_no_deposit_player_by_date_range.log 2>&1 &
	 * - Specify last Sunday to last Saturday.
	 * sudo /bin/bash admin/shell/command.sh no_deposit_player_by_date_range "2022-06-19 00:00:00" "2022-06-25 23:59:59" >> logs/cronjob_no_deposit_player_by_date_range.log 2>&1 &
	 *
	 * @param string|null $deposit_date_from The begin date time, default is last Sunday.
	 * @param string|null $deposit_date_to The end date time, default is last Saturday.
	 * @return void
	 */
	public function no_deposit_player_by_date_range($deposit_date_from = null, $deposit_date_to = null){
		$this->load->model(array('report_model'));

		if( empty($deposit_date_from) || empty($deposit_date_to) ){
			$curr_DT = new DateTime();
			$curr_year = $curr_DT->format("Y");
			$curr_week_no = $curr_DT->format("W");
			$pre_week_no = ($curr_week_no == 1)? 52: ($curr_week_no- 1);
		}
		if(empty($deposit_date_from)){
			$week_day = 0;
			$by_date_fromDT = new DateTime();
			$by_date_fromDT->setISODate($curr_year, $pre_week_no, $week_day); // 0: Sunday, 1: Monday,...
			$by_date_fromDT->setTime(0, 0, 0);
			$by_date_from = $this->utils->formatDateTimeForMysql($by_date_fromDT);
		}else{
			$by_date_from = $deposit_date_from;
		}
		if(empty($deposit_date_to)){
			$week_day = 6;
			$by_date_toDT = new DateTime();
			$by_date_toDT->setISODate($curr_year, $pre_week_no, $week_day); // 0: Sunday, 1: Monday,...
			$by_date_toDT->setTime(23, 59, 59);
			$by_date_to = $this->utils->formatDateTimeForMysql($by_date_toDT);
		}else{
			$by_date_to = $deposit_date_to;
		}
		$i = 0;
		$request['extra_search'][$i]['name'] = 'deposit_date_from';
		$request['extra_search'][$i]['value'] = $by_date_from;
		$i++;
		$request['extra_search'][$i]['name'] = 'deposit_date_to';
		$request['extra_search'][$i]['value'] = $by_date_to;

		//$request= $this->input->post();
		$is_export = true;

		$result = $this->report_model->no_deposit_player($request, $is_export);
		$this->utils->debug_log('[no_deposit_player_by_date_range].result:', $result); // the csv filename

	}

	public function get_transaction_timeout_transfer_request_cronjob($minutes=-1){

		//default is last 15 minutes
		$get_timeout_transfer_request_minutes=$this->utils->getConfig('get_timeout_transfer_request_minutes');
		if($minutes<=0){
			$minutes=$get_timeout_transfer_request_minutes;
		}

		set_time_limit($minutes*60);

		$this->utils->debug_log('transaction scan last '.$minutes.' minutes');
		$from=$this->utils->formatDateTimeForMysql(new DateTime('-'.$minutes.' minutes'));

		$this->get_transaction_timeout_transfer_request_new($from);
	}

	public function get_transaction_timeout_transfer_request_new($from=null, $to=null){

		if(empty($to)){
			$to=$this->utils->getNowForMysql();
		}
		if(empty($from)){
			$minutes=$this->utils->getConfig('get_timeout_transfer_request_minutes');
			//last x minutes
			$from=$this->utils->formatDateTimeForMysql((new DateTime($to))->modify('-'.$minutes.' minutes'));
		}
		$to = date('Y-m-d H:i:59', strtotime($to));
		$from = date('Y-m-d H:i:00', strtotime($from));

		$db=$this->db;
		$db_name=$db->database;

		$this->utils->debug_log('get_transaction_timeout_transfer_request', $from, $to);

		$this->load->model(['wallet_model']);
		$rows = $this->wallet_model->getTimeOutTransferRequest($from , $to, null, $this->db, $db_name);
		#Process if not empty rows

		if(!empty($rows)){

			$this->wallet_model->generate_timeout_transfer_request_notification_non_mdb($rows, $from, $to, $db_name);
		}

        $this->utils->debug_log('gttr done process ==>', $db_name, $from, $to);

	}

	public function get_transaction_timeout_transfer_request($from=null, $to=null){

		if(empty($to)){
			$to=$this->utils->getNowForMysql();
		}
		if(empty($from)){
			$minutes=$this->utils->getConfig('get_timeout_transfer_request_minutes');
			//last x minutes
			$from=$this->utils->formatDateTimeForMysql((new DateTime($to))->modify('-'.$minutes.' minutes'));
		}

		$this->utils->debug_log('get_transaction_timeout_transfer_request', $from, $to);

		if($this->utils->isEnabledMDB()){
            $export_details = $this->utils->foreachMultipleDBToCIDB(function($db,$db_name) use ($from, $to) {
            	$this->load->model(['wallet_model']);
				$rows = $this->wallet_model->getTimeOutTransferRequest($from , $to, null, $db, $db_name);
				#Process if not empty rows
				if(!empty($rows)){
					$this->CI->load->library(['language_function']);

					#config parameters
					$config = $this->utils->getConfig('export_timeout_request_cron_settings');
            		$params = json_decode($config['param_json_template'], TRUE);
            		$fixed_index_base_on_params = 1;
            		#queue parameters
	            	$lang=$this->language_function->getCurrentLanguage();
	                $funcName='remote_export_csv';
	                $caller=0;
	                $callerType=Queue_result::CALLER_TYPE_SYSTEM;
	                $state=null;

	                #Loop extra search params to update date range
	                array_walk($params[$fixed_index_base_on_params]['extra_search'], function($d_row, $index) use ($params, $from, $to, $fixed_index_base_on_params){
	                	if(isset($d_row['name'])){
	                		if($d_row['name'] == "date_from"){
		                		$params[$fixed_index_base_on_params]['extra_search'][$index]['value'] = $from;
		                	}
		                	if($d_row['name'] == "date_to"){
		                		$params[$fixed_index_base_on_params]['extra_search'][$index]['value'] = $to;
		                	}
	                	}
	                });
	                $token=  $this->createQueueOnCommand($funcName, $params,$lang , $callerType, $caller, $state);

	                # Bash params
			        $php_str=$this->utils->find_out_php();
			        $og_admin_home = realpath(dirname(__FILE__) . "/../../../");
					$dbEnv = "\n".'__OG_TARGET_DB='.$db_name;
	                $function = 'do_remote_export_csv_job';
	                $cmd =  $dbEnv.' '.$php_str.' '.$og_admin_home.'/shell/ci_cli.php cli/command/'.$function.' "'.$token.'"' . "\n"."wait";
	                #execute bash
	                $this->executeBashCommand($cmd);
	                #generate notification
	                $this->wallet_model->generate_timeout_transfer_request_notification($rows, $from, $to, $token, $this->db->database);

	                $this->utils->debug_log('gttr done process ==>', $db_name, $from, $to);
				}
			},null,array('super'));
        }
	}

	/**
	 * Re-calculate aff_dashboard information
	 * Command line:
	 * 	Manual calc					: sudo admin/shell/command.sh aff_dashboard_calc
	 *  Delete only, skipping calc	: sudo admin/shell/command.sh aff_dashboard_calc 0 del_only
	 * @param	date	$date		'YYYY-mm-dd', '0' to skip
	 * @param	boolean	$del_only	any nonblank value for true
	 * @return	none
	 */
	public function aff_dashboard_calc($date = null, $del_only = false) {
		$this->load->model([ 'affiliatemodel' ]);

		$this->utils->debug_log('aff dashboard calc: starting', [ 'date' => $date, 'del_only' => $del_only ]);

		$this->affiliatemodel->affDashboardUpdateAll($date, $del_only);

		$this->utils->debug_log('aff dashboard calc: ending');
	}

	public function executeBashCommand($cmdStr){
		$uniqueid=random_string('md5');
        $log_dir=BASEPATH.'/../application/logs/tmp_shell';
        if(!file_exists($log_dir)){
            @mkdir($log_dir, 0777 , true);
        }
        $title = __FUNCTION__;
        $func = $title;

        $noroot_command_shell=<<<EOD
#!/bin/bash

echo "start {$title} `date`"

start_time=`date "+%s"`
{$cmdStr}
end_time=`date "+%s"`

echo "Total run time: `expr \$end_time - \$start_time` (s)"
echo "done {$title} `date`"

EOD;
		# Execute
        $tmp_shell=$log_dir.'/'.$func.'_'.$uniqueid.'.sh';
        file_put_contents($tmp_shell, $noroot_command_shell);
        $cmd='bash '.$tmp_shell;
        exec($cmd);
        $this->utils->debug_log('execute cmd ==>', $cmd);
	}

	/**
     * This will update account info Amount in Withdrawal Process
     * big_wallet frozen amount
     *
     * @return void
     */
    public function update_withdrawal_fee_amount_in_withdrawal_process($playerId, $amount){

		$this->load->model(array('wallet_model','transactions'));
		$this->utils->debug_log('Start update_withdrawal_fee_amount_in_withdrawal_process');
		$controller=$this;
		$success = $this->lockAndTransForPlayerBalance($playerId, function ()
			use ($playerId, $amount, $controller) {
			$success=false;
			$controller->utils->debug_log('amount playerId', $amount,$playerId);
			$success = $controller->wallet_model->decFrozenOnBigWallet($playerId, $amount);

			return $success;
		});
		$this->utils->debug_log('End update_withdrawal_fee_amount_in_withdrawal_process', $success);
	}

	public function cronjob_check_suspicious_withdrawal($minutes = null){
		$settings = $this->utils->getConfig('suspicious_withdrawal_settings');

		if(!$settings){
			$this->utils->error_log('cronjob_check_suspicious_withdrawal missing settings');
			exit;
		}

		if(empty($minutes)){
			$minutes = $settings['adjust_minutes'];
		}

		$max_amount = 1000000;
		$multiplier = 1000;
		$currency = strtolower($this->utils->getCurrentCurrency()['currency_code']);
		if(isset($settings[$currency])){
			$max_amount = (isset($settings[$currency]['max_amount']) && $settings[$currency]['max_amount']?$settings[$currency]['max_amount']:1000000);
			$multiplier = (isset($settings[$currency]['multiplier']) && $settings[$currency]['multiplier']?$settings[$currency]['multiplier']:1000);
		}
		$this->utils->debug_log('cronjob_check_suspicious_withdrawal', $max_amount, $multiplier, $currency);

		$target = '';

		$from = new DateTime($this->utils->getNowForMysql());
		$from->modify('-'.$minutes.' minutes');
		$to = new DateTime($this->utils->getNowForMysql());
		$this->utils->debug_log('cronjob_check_suspicious_withdrawal', $from->format('Y-m-d H:i:s'), $to->format('Y-m-d H:i:s'));

		//get witdrawal transfer request from subwallet status approved
		$data = $this->wallet_model->searchSuspiciousTransferFromSubwalletRequest($from->format('Y-m-d H:i:s'), $to->format('Y-m-d H:i:s'));

		$this->utils->debug_log('####### SUPICIOUS WITHDRAWAL');

		if(!empty($data)){
			$user='Suspicious withdrawal';
			$channel = $settings['mm_channel'];

			foreach($data as $row){

				$send = false;
				$message = "@all ".($settings['base_url'])."\n\n";
				$message .= "|action|player id|secure id|comment|amount|created at|response result id|\n";
				$message .= "|----|----|----|----|----|----|----|\n";

				$link = $settings['base_url'].'/payment_management/transfer_request?search_reg_date=false&secure_id='.$row['secure_id'];
				$playerlink = $settings['base_url'].'/player_management/userInformation/'.$row['player_id'];
				$responselink = $settings['base_url'].'/system_management/view_resp_result?result_id='.$row['response_result_id'];

				$lastTransferLink='';
				if($row['last_transfer']){
					$lastTransferLink = $settings['base_url'].'/payment_management/transfer_request?search_reg_date=false&secure_id='.$row['last_transfer']['secure_id'];
					$lastTransferResponselink = $settings['base_url'].'/system_management/view_resp_result?result_id='.$row['last_transfer']['response_result_id'];
					$lastTransferMessage = "|transfer in|[".$row['last_transfer']['player_id']."]($playerlink)|[".$row['last_transfer']['secure_id']."]($lastTransferLink)|previous deposit|".$row['last_transfer']['amount']."|".$row['last_transfer']['created_at']."|[".$row['last_transfer']['response_result_id']."]($lastTransferResponselink)|\n";
				}

				if($row['huge_amount']==true){
					$message .= "|transfer out|[".$row['player_id']."]($playerlink)|[".$row['secure_id']."]($link)|wihdraw >= ".$max_amount."|".$row['amount']."|".$row['created_at']."|[".$row['response_result_id']."]($responselink)|-|\n";
				}

				if($row['doubled']==true){
					$message .= $lastTransferMessage;
					$message .= "|transfer out|[".$row['player_id']."]($playerlink)|[".$row['secure_id']."]($link)|doubled last deposit amount (".$row['last_transfer']['amount'].")|".$row['amount']."|".$row['created_at']."|[".$row['response_result_id']."]($responselink)|\n";
				}

				if($row['multiplied']==true){
					$message .= $lastTransferMessage;
					$message .= "|transfer out|[".$row['player_id']."]($playerlink)|[".$row['secure_id']."]($link)|multiplied last deposit (".$row['last_transfer']['amount'].")|".$row['amount']."|".$row['created_at']."|[".$row['response_result_id']."]($responselink)|\n";
				}

				$message .= "\n";

				$this->sendNotificationToMattermost($user, $channel, $message, 'warning');
			}

		}
		exit;
	}

	/**
	 * OGP-20380 fix player playeraccount subwallet empty
	 * sync the subwallet to paymentaccount
	*/
	public function fix_playeraccount_subwallet_empty($from = null, $to = null) {
		# Get min & max createdOn from player table
		$this->utils->info_log('=========start fix_playeraccount_subwallet_empty=========',$from, $to);
		$this->load->model(['player_model', 'wallet_model']);
		$player_model = $this->player_model;

		# get playeraccount subwallet empty player
		$playerList = array();
		$playerList = $this->wallet_model->getPlayerListByCreatedOn($from, $to);

		$this->utils->info_log("====playerList last sql", $this->db->last_query());
		$this->utils->info_log("====playerList fix_playeraccount_subwallet_empty", $playerList);

		# sync the subwallet to paymentaccount
		$failed_player=[];
		$cnt=0;
		if(!empty($playerList)){
			foreach ($playerList as $player) {
				$playerId = $player['playerId'];
				if(!empty($playerId)){
					$success=$this->wallet_model->lockAndTransForPlayerBalance($playerId, function()
						use($playerId){
                            return $this->wallet_model->initCreateAllWalletForRegister($playerId);
					});
					if($success){
						$cnt++;
						$this->utils->debug_log('refresh player: '.$playerId);
					}else{
						$this->utils->error_log('refresh player: '.$playerId.' failed');
						$failed_player[]=$playerId;
					}
				}
			}
		}

		if(!empty($failed_player)){
			$this->utils->error_log('failed_player', $failed_player);
		}

		$this->utils->info_log('done : '.$cnt);

		$this->utils->info_log('=========end fix_playeraccount_subwallet_empty=========');

	}

	public function get_seamless_error_logs_cronjob($minutes=-1){
		$get_seamless_error_logs_request_minutes=$this->utils->getConfig('get_seamless_error_logs_request_minutes');
		if($minutes<=0){
			$minutes=$get_seamless_error_logs_request_minutes;
		}

		set_time_limit($minutes*60);
		$this->utils->debug_log('seamless_error_logs last '.$minutes.' minutes');
		$from=$this->utils->formatDateTimeForMysql(new DateTime('-'.$minutes.' minutes'));
		//adjust to 00 second
		$from=substr($from, 0, 17).'00';

		$this->get_seamless_error_logs($from);
	}

	public function get_seamless_error_logs($from=null, $to=null, $is_sleep='true'){
		$is_sleep=$is_sleep=='true';
		if(empty($to)){
			$to=$this->utils->getNowForMysql();
		}
		if(empty($from)){
			$minutes=$this->utils->getConfig('get_seamless_error_logs_request_minutes');
			//last x minutes
			$from=$this->utils->formatDateTimeForMysql((new DateTime($to))->modify('-'.$minutes.' minutes'));
		}

		$db=$this->db;
		$db_name=$db->database;
		$this->load->model(['common_seamless_error_logs']);
		$rows = $this->common_seamless_error_logs->get_seamless_error_logs($from , $to, $db, $db_name);
		$this->common_seamless_error_logs->generate_seamless_error_log_notification($rows, $from, $to, $db_name);
		$this->utils->debug_log('get_seamless_error_logs done process ==>', $db_name, $from, $to);
	}

	/**
	 * overview : generatePaymentAbnormalHistory
	 */
    public function generate_payment_abnormal_history($settlement_date = null){
		$this->load->model(['payment_abnormal_notification']);

		$this->utils->info_log('=========start generate_payment_abnormal_history=========',$settlement_date);

		$dates = [];
		if(!empty($settlement_date)){
			$dates['date_base'] = $settlement_date;
		}
		$success=$this->payment_abnormal_notification->generatePaymentAbnormalHistory($dates);

		$msg=$this->utils->debug_log('generate_payment_abnormal_history: '.$success);

		$this->utils->info_log('=========end generate_payment_abnormal_history=========',$msg);

		$this->returnText($msg);
    }

    /**
	 * overview : generatePlayerAbnormalHistory
	 */
    public function generate_player_abnormal_history($settlement_date = null){
		$this->load->model(['payment_abnormal_notification']);

		$this->utils->info_log('=========start generate_player_abnormal_history=========',$settlement_date);

		$dates = [];
		if(!empty($settlement_date)){
			$dates['date_base'] = $settlement_date;
		}
		$success=$this->payment_abnormal_notification->generatePlayerAbnormalHistory($dates);

		$msg=$this->utils->debug_log('generate_player_abnormal_history: '.$success);

		$this->utils->info_log('=========end generate_player_abnormal_history=========',$msg);

		$this->returnText($msg);
    }

    /**
	 * overview : generateWithdrawalAbnormalHistoryAllPlayer
	 */
    public function generateWithdrawalAbnormalHistoryAllPlayer($settlement_date = null){
		$this->load->model(['payment_abnormal_notification']);

		$this->utils->info_log('=========start generateWithdrawalAbnormalHistoryAllPlayer=========',$settlement_date);

		$dates = [];
		if(!empty($settlement_date)){
			$dates['date_base'] = $settlement_date;
		}
		$success=$this->payment_abnormal_notification->generateWithdrawalAbnormalHistoryAllPlayer($dates);

		$msg=$this->utils->debug_log('generateWithdrawalAbnormalHistoryAllPlayer msg: '.$success);

		$this->utils->info_log('=========end generateWithdrawalAbnormalHistoryAllPlayer=========',$msg);
    }

    /**
     * overview : auto_decline_pending_deposit_request
	 */
    public function auto_decline_pending_deposit_request($from = null, $to = null, $note = 'auto decline pending deposit by T1'){
		$this->load->model(['payment_abnormal_notification']);

        $date_from = !empty($from) ? date('Y-m-d H:i:s', strtotime($from)) : $this->utils->getYesterdayForMysql() . " 00:00:00";
		$date_to = !empty($to) ? date('Y-m-d H:i:s', strtotime($to)) : $this->utils->getNowForMysql();
		$timeout_seconds = $this->utils->getConfig('decline_deposit_timeout_seconds');

		$this->utils->info_log('=========start auto_decline_pending_deposit_request=========',$date_from, $date_to, $timeout_seconds);

		$success = false;

		if(!empty($date_from) && !empty($date_to)){
            $this->load->model(['sale_order','payment_account']);

            $success_cnt = 0;
            $success_id = [];

            $saleOrders = $this->sale_order->getSaleOrdersByStatus(Sale_order::VIEW_STATUS_REQUEST, $date_from, $date_to);
            $this->utils->printLastSQL();
            $this->utils->debug_log(__METHOD__,'-------- saleOrders --------', $saleOrders);

            foreach($saleOrders as $order){

				$created_at = $order->created_at;
				$system_id = $order->system_id;
				$payment_type = null;

				if (!$this->utils->getConfig('allowed_decline_all_pending_deposit')) {
					$payment_account = $this->payment_account->getPaymentAccountBySystemId($system_id);
					$this->utils->debug_log(__METHOD__,'-------- payment_account --------', $payment_account);

					if ($payment_account) {
						$payment_type = $payment_account->flag;
					}
				}

				$this->utils->debug_log(__METHOD__,'-------- params -------- created_at', $created_at, 'system_id', $system_id, 'payment_type',$payment_type);

				if ($payment_type == AUTO_ONLINE_PAYMENT) {
					$this->utils->info_log('not allowed_decline_all_pending_deposit continue',$order->id);
					continue;
				}

				if ((strtotime($date_to) - strtotime($created_at)) < $timeout_seconds) {
					$this->utils->info_log("Deposit Requests that have not been [Pending] for longer than [$timeout_seconds]",$order->id);
					continue;
				}

                $success = $this->set_deposit_declined($order->id, $note);

                if($success){
                    $success_cnt += 1;
                    $success_id[] = $order->id;
                }
            }

            $this->utils->info_log(__METHOD__,'batch set deposit declined success count', $success_cnt, 'success sale order id', $success_id);
        }

		$this->utils->info_log('=========end auto_decline_pending_deposit_request=========',$success);
    }

    public function get_transaction_timeout_transfer_request_by_cost_ms_cronjob($minutes = 1){

		set_time_limit($minutes*60);

		$this->utils->debug_log('transaction scan last '.$minutes.' minutes');
		$df= new DateTime('-'.$minutes.' minutes');
		$from = $df->format('Y-m-d H:i:00');
		$to = $df->format('Y-m-d H:i:59');

		$this->utils->debug_log("get_transaction_timeout_transfer_request_by_cost_ms from ==> {$from} && to ==> {$to}");
		$this->get_transaction_timeout_transfer_request_by_cost_ms($from);
	}

	public function get_transaction_timeout_transfer_request_by_cost_ms($from=null, $to=null, $is_sleep='true', $is_manual = 'false'){

		$config_ms=$this->utils->getConfig('default_timeout_transfer_request_time_on_millisecond');
		if($config_ms <= 0){
			$this->utils->debug_log('get_transaction_timeout_transfer_request_by_cost_ms config disabled');
			return false;
		}

		$is_sleep=$is_sleep=='true';
		$is_manual=$is_manual=='true';
		if(empty($to)){
			$minutes=1;
			$dt=(new DateTime($to))->modify('-'.$minutes.' minutes');
			$to = $dt->format('Y-m-d H:i:59');
		}

		if(empty($from)){
			$minutes=1;
			$df=(new DateTime($from))->modify('-'.$minutes.' minutes');
			$from = $df->format('Y-m-d H:i:00');
		}

		$this->utils->debug_log("get_transaction_timeout_transfer_request_by_cost_ms from ==> {$from} && to ==> {$to}");
		if($is_sleep){
			#try sleep to fetch data once response result is created
			$sleep_time = $this->utils->getConfig('get_timeout_transfer_request_sleep_time');
			$this->utils->debug_log('start sleep time ', $sleep_time, 'from', $from);
			sleep($sleep_time);
			$this->utils->debug_log('end sleep, start process =========> from', $from);
		}

		$db=$this->db;
		$this->utils->debug_log('get_transaction_timeout_transfer_request_by_cost_ms', $from, $to);

		$this->load->model(['wallet_model']);
		$cnt = $this->wallet_model->getTimeOutTransferRequestByCostMs($from, $to, $db, $is_manual);
		$this->utils->debug_log('get_transaction_timeout_transfer_request_by_cost_ms rows count =========>', $cnt);
	}

    /**
	 * Deletion of external_common_tokens data
	 */
    public function batchDeleteOfExternalCommonToken($dry_run=false){

    	$this->load->model('external_common_tokens');

    	$timeInterval = $this->utils->getConfig('time_interval_for_deletion_of_external_common_token_hours');
    	$dateTimeRange = isset($timeInterval) && !empty($timeInterval) ? $timeInterval : 72;
    	$configLimit = $this->utils->getConfig('limit_for_deletion_of_external_common_token');
        $limit = isset($configLimit) ? $configLimit : 0;
        $dry_run = (strtolower($dry_run) == 'false' || $dry_run === false ) ? false : true;
        $date_field = date('Y-m-d H:i:s', strtotime('-'.$dateTimeRange.' hours'));

        $sql = $this->external_common_tokens->batchDeleteOfExternalCommonToken($dry_run,$limit,$date_field);

        $this->CI->utils->debug_log('<-----delete external_common_tokens data----->','removed data',$sql);
    }

	public function init_balance_monthly_table($date=null){
		if(empty($date)){
			$date=new DateTime('tomorrow');
		}else{
			$date=new DateTime($date);
		}
		$currentMonthStr = $monthStr=$date->format('Ym');
		$this->utils->initBalanceMonthlyTableByDate($monthStr);
		$this->utils->info_log('initBalanceMonthlyTableByDate', $monthStr);
		$this->utils->initSeamlessBalanceMonthlyTableByDate($monthStr);
		$this->utils->info_log('initSeamlessBalanceMonthlyTableByDate', $monthStr);
		$this->utils->initT1lotteryTransactionsMonthlyTableByDate($monthStr);
		$this->utils->info_log('initT1lotteryTransactionsMonthlyTableByDate', $monthStr);

		//next month
		$date->modify('+1 month');
		$nextMonthStr = $monthStr=$date->format('Ym');
		$this->utils->initBalanceMonthlyTableByDate($monthStr);
		$this->utils->info_log('initBalanceMonthlyTableByDate', $monthStr);
		$this->utils->initSeamlessBalanceMonthlyTableByDate($monthStr);
		$this->utils->info_log('initSeamlessBalanceMonthlyTableByDate', $monthStr);
		$this->utils->initT1lotteryTransactionsMonthlyTableByDate($monthStr);
		$this->utils->info_log('initT1lotteryTransactionsMonthlyTableByDate', $monthStr);


		// $apis = $this->utils->getConfig('generate_monthly_seamless_transactions_apis');
		$this->load->model(['external_system']);
		$apis = $this->external_system->getAllGameApis();
		if(!empty($apis)){
			$apis = array_column($apis, 'id');
			foreach($apis as $apiId){
				$api = $this->utils->loadExternalSystemLibObject($apiId);
				if(!$api){
					continue;
				}

				try {

					if(!method_exists($api, 'initGameTransactionsMonthlyTableByDate')) {
						continue;
					}

					$currentMonth = $api->initGameTransactionsMonthlyTableByDate($currentMonthStr);
					$nextMonth = $api->initGameTransactionsMonthlyTableByDate($nextMonthStr);

	            	if(is_null($currentMonth) || is_null($nextMonth)) {
	                	throw new Exception("Error on creating monthly table!!! API ID: {$apiId}");
	            	}

	            	if(empty($currentMonth) || empty($nextMonth)) {
	                	throw new Exception("Empty monthly table!!! API ID: {$apiId}");
	            	}

	            	$this->utils->info_log("initGameTransactionsMonthlyTableByDate API ID:{$apiId}", $apiId);

		        } catch (Exception $e) {
		        	$this->CI->utils->error_log('initGameTransactionsMonthlyTableByDate  ERROR: '.$e->getMessage());
		            continue;
		        }
			}
		}
	}

	/**
	 * Manual Sync for fixing common_seamless_wallet_transactions' game_id and round_id data for PGSoft Seamless only
	 * bash ./command_mdb_noroot.sh <db> fix_gameid_and_roundid_data '2020-11-04 00:00:00' '2020-11-04 23:59:59'
	 * sudo ./command.sh fix_gameid_and_roundid_data '2020-10-03 00:00:00' '2020-10-18 23:59:59'
	 *
	 * @param str $dateTimeFromStr
	 * @param str $dateTimeToStr
	*/
	public function fix_gameid_and_roundid_data($dateTimeFromStr = "null", $dateTimeToStr = "null") {

		$this->load->model('common_seamless_wallet_transactions');

		$result[] = $this->CI->utils->loopDateTimeStartEnd($dateTimeFromStr,$dateTimeToStr,'+1 day',function($dateTimeFromStr,$dateTimeToStr) {
			$gamePlatformId=LIVE12_SEAMLESS_GAME_API;
			$dateTimeFromStr = $dateTimeFromStr->format('Y-m-d H:i:s');
            $dateTimeToStr = $dateTimeToStr->format('Y-m-d H:i:s');

			$getTransactions = $this->common_seamless_wallet_transactions->getTransactions($gamePlatformId, $dateTimeFromStr, $dateTimeToStr);

			$this->CI->utils->info_log('<-----get count data----->','count', count($getTransactions), 'startDate', $dateTimeFromStr,'endDate',$dateTimeToStr);

			foreach ($getTransactions as $transaction) {
				$external_unique_id = $transaction['external_unique_id'];
				$extra_info = json_decode($transaction['extra_info'],true);
				$gameId = isset($extra_info['GameId']) ? $extra_info['GameId'] : null;
				$productType = isset($extra_info['ProductType']) ? $extra_info['ProductType'] : null;
				$data = [
					'game_id' => $productType,
					'round_id' => $gameId
				];

				$this->CI->utils->debug_log('<-----updated game & round data----->','updated data',$data);

				$this->common_seamless_wallet_transactions->updateTransaction($gamePlatformId, $external_unique_id, $data);

			}

			return true;

		});

	}

	public function fakeDeleteVIPGroup($vipsettingId){
		$this->load->model(array('group_level'));

		$this->utils->info_log('---------start fakeDeleteVIPGroup---------',$vipsettingId);

		if (!empty($vipsettingId)) {
			$result = $this->group_level->fakeDeleteVIPGroup($vipsettingId);
			$this->utils->printLastSQL();
		}

		$this->utils->info_log('---------end fakeDeleteVIPGroup---------',$result);
	}

	/**
	 * overview : copy permission v2
	 *
	 * @param int $funcId
	 * @param int $replaceFuncId
	 */
	public function copy_permission_v2($funcId, $replaceFuncId) {
		$this->load->model(['roles']);

		$this->utils->info_log('------------ start copy to roles', 'funcId', $funcId, 'replaceFuncId', $replaceFuncId);
		$funcArr = explode(',', $replaceFuncId);
		$cnt = 0;

		$sql = "SELECT rolefunctions.roleId, group_concat(rolefunctions.funcId) as func_id_string,roles.roleName FROM `rolefunctions` left join roles on rolefunctions.roleId=roles.roleId WHERE `funcId` IN (?,?) group by roleId order by roleId ";

		$query = $this->db->query($sql, array($funcId, $replaceFuncId));
		$roles = $query->result_array();

		$this->utils->printLastSQL();

		$this->utils->debug_log('------------ copy_permission_v2 query roles', $roles, 'funcArr', $funcArr);

		if (!empty($roles) && !empty($funcArr)) {
			foreach ($roles as $roleId) {
				$this->utils->debug_log('copy_permission_v2 roleId', $roleId);

				if (stristr($roleId['func_id_string'], $replaceFuncId) !== false) {
					$this->utils->info_log('------------ continue');
					continue;
				}

				foreach ($funcArr as $fId) {
					$this->db->insert('rolefunctions', array('roleId' => $roleId['roleId'], 'funcId' => $fId));
				}
				$cnt++;
			}
		}

		$this->utils->info_log('------------ end copy to roles', 'count', $cnt);
	}

    public function set_deposit_declined($saleOrderId, $note) {
        $success = false;

        $this->load->model(array('sale_order', 'transactions', 'sale_orders_notes'));
        $saleOrder = $this->sale_order->getSaleOrderById($saleOrderId);
        $loggedAdminUserId = Users::SUPER_ADMIN_ID;

        $lockedKey=null;
        $lock_it = $this->lockPlayerBalanceResource($saleOrder->player_id, $lockedKey);

        if (!$lock_it) {
            $this->utils->debug_log('set_deposit_declined lock failed, saleOrderId', $saleOrderId);
            return $success;
        }

        //lock success
        try {
            $actionlogNotes = $note;
            $this->startTrans();

            $this->sale_order->declineSaleOrder($saleOrderId, $actionlogNotes, null);
            $this->transactions->createDeclinedDepositTransaction($saleOrder, $loggedAdminUserId, Transactions::MANUAL);

            $success = $this->endTransWithSucc();
        } finally {
            // release it
            $this->releasePlayerBalanceResource($saleOrder->player_id, $lockedKey);
        }

        if($success) {
            $this->sale_order->userUnlockDeposit($saleOrderId);
        }

        if($success){
            if($this->utils->getConfig('enable_fast_track_integration')) {
                $this->load->library('fast_track');
                $this->fast_track->declineDeposit((array) $saleOrder);
            }
        }

        return $success;
    }

    /**
     *
     * overview: Batch Set Deposit as declined
     * detail: batch set the status of deposit request as declined
     * @param string $from date time
     * @param string $to date time
     * @param string $note reason
     *
     */
    public function batch_set_deposit_declined($from = null, $to = null, $note = 'batch set deposit decliend by T1') {
        $this->utils->debug_log('============== START batch set deposit declined ==============');

        if(is_string($from)){
            $from = new DateTime($from);
        }
        $dateTimeFromStr = $this->utils->formatDateTimeForMysql($from);

        if(is_string($to)){
            $to = new DateTime($to);
        }
        $dateTimeToStr = $this->utils->formatDateTimeForMysql($to);

        if(!empty($dateTimeFromStr) && !empty($dateTimeToStr)){
            $this->load->library(['payment_library']);
            $this->load->model(['sale_order']);

            $success_cnt = 0;
            $success_id = [];

            $declinedSaleOrders = $this->sale_order->getSaleOrdersByStatus(Sale_order::VIEW_STATUS_REQUEST, $dateTimeFromStr, $dateTimeToStr);

            foreach($declinedSaleOrders as $declinedSaleOrder){
                $success = $this->set_deposit_declined($declinedSaleOrder->id, $note);

                if($success){
                    $success_cnt += 1;
                    $success_id[] = $declinedSaleOrder->id;
                }
            }

            $this->utils->debug_log('============== batch set deposit declined success count ==============', $success_cnt, 'success sale order id', $success_id);
        }

        $this->utils->debug_log('============== END batch set deposit declined ==============');
    }

    public function transfer_all_to_one_api($apiId, $dry_run='true', $enabledDeclineWithdrawal='true'){
    	if(empty($apiId)){
    		$this->utils->error_log('wrong api id', $apiId);
    		exit(1);
    	}
    	$apiId=intval($apiId);
    	$adminUserId=1;
    	$successCnt=0;
    	$failedUsername=[];
    	$failedWithdrawalUsername=[];
    	$enabledDeclineWithdrawal=$enabledDeclineWithdrawal=='true';
    	$dry_run=$dry_run=='true';
    	$this->db->from('tmp_player_for_om_rollback');
    	$rows=$this->player_model->runMultipleRowArray();
    	if(!empty($rows)){
    		foreach ($rows as $row) {
    			if($enabledDeclineWithdrawal){
					$this->declineWithdrawalForPlayer($dry_run, $adminUserId, $row['player_id'], $row['username'], $failedWithdrawalUsername);
    			}
    			$result=$this->transferAllToOnewallet($dry_run, $adminUserId, $row['player_id'], $row['username'], $apiId);
    			if(empty($result) || !$result['success']){
    				$failedUsername[]=$row['username'];
    				$this->utils->error_log('transfer failed', $row, $apiId);
    			}else{
    				$successCnt++;
    			}
    		}
    	}

    	$this->utils->info_log('successCnt', $successCnt, 'failedUsername', $failedUsername, 'failedWithdrawalUsername', $failedWithdrawalUsername);
    }

	protected function transferAllToOnewallet($dry_run, $adminUserId, $playerId, $playerName, $walletId) {
		$this->load->model(['external_system','wallet_model']);
		if(empty($playerId) || empty($playerName) || empty($walletId)){
			return ['success'=>false];
		}
		if($dry_run){
			$this->utils->debug_log('dry run', $playerId, $playerName, $walletId);
			return ['success'=>true];
		}else{
			return $this->utils->transferAllWallet($playerId, $playerName, $walletId,
				$adminUserId, null, null, true);
		}
	}

	public function decline_all_request_withdrawal($dry_run='true'){
		$dry_run=$dry_run=='true';
		$adminUserId=1;
		$successCnt=0;
		$failedWithdrawalUsername=[];
		$rows=$this->wallet_model->getPlayerIdForAllRequestWithdrawal();
		if(!empty($rows)){
			foreach ($rows as $row) {
				$this->declineWithdrawalForPlayer($dry_run, $adminUserId, $row['playerId'],
					$row['username'], $failedWithdrawalUsername);
				$successCnt++;
			}
		}

		$this->utils->info_log('success player', $successCnt, 'failedWithdrawalUsername', $failedWithdrawalUsername);
	}

	protected function declineWithdrawalForPlayer($dry_run, $adminUserId, $playerId, $playerName, &$failedWithdrawalUsername){
		$rows=$this->wallet_model->getAllRequestWithdrawalByPlayerId($playerId);
		if(!empty($rows)){
			$actionlogNotes='Batch decline withdrawal';
			$showDeclinedReason=false;
			foreach ($rows as $row) {
				$success=$this->lockAndTransForPlayerBalance($playerId, function()
					use($dry_run, $adminUserId, $playerId, $playerName, $row, $actionlogNotes, $showDeclinedReason){
					$succ=true;
					if($dry_run){
						$this->utils->debug_log('dry run decline withdrawal', $row['walletAccountId'], $playerName);
					}else{
						$succ = $this->wallet_model->declineWithdrawalRequest($adminUserId,
							$row['walletAccountId'], $actionlogNotes, $showDeclinedReason);
					}
					return $succ;
				});

				if(!$success){
					$failedWithdrawalUsername[]=$playerName;
				}
			}
		}else{
			$this->utils->debug_log('no any withdrawal', $playerId, $playerName);
		}
	}

	public function batch_update_unknown_status($from, $to){
		$this->utils->debug_log('batch_update_unknown_status', $from, $to);

		$this->load->model(['wallet_model']);
		$cnt=$this->wallet_model->updateUnknownStatusForTransferRequest($from , $to);

		$this->utils->debug_log('updateUnknownStatusForTransferRequest', $from, $to, $cnt);
	}

	public function batch_auto_fix_lost_balance($from, $to){
		$this->utils->debug_log('batch_auto_fix_lost_balance', $from, $to);

		$this->load->model(['wallet_model']);
		$cnt=$this->wallet_model->batchAutoFixLostBalance($from , $to);

		$this->utils->debug_log('batchAutoFixLostBalance', $from, $to, $cnt);
	}


    protected function transfer_main_wallet_to_subwallet($player, $api, $max_bal) {
        $this->load->model(['external_system','wallet_model']);
        $playerId = $player['playerId'];
        $playerName = $player['username'];

        $game_platform_id = $api->getPlatformCode();
        $failed_transfers = [];
        $success = false;
        if ($api) {
            $isPlayerExist = $api->isPlayerExist($playerName);
            if ($isPlayerExist) {
                if($max_bal<=1){
                    if($api->onlyTransferPositiveInteger()){
                        //ignore positive integer only
                        $this->utils->debug_log('positive integer only');
                    }
                }
                else {
                    $balance = floatval($this->utils->get_main_wallet($playerId));
                    if ($balance <= $max_bal) {

                        if($balance>0){
                            $this->load->model(['sale_order']);
                            //only transfer balance >0
                            $result = $this->utils->transferWallet($playerId, $playerName, Wallet_model::MAIN_WALLET_ID, $game_platform_id, $balance);
                            if (isset($result['success']) && $result['success']) {
                                $this->utils->debug_log('transfer '.$playerName.' from '.$api->getPlatformCode().' balance:'.$balance.' success');
                                return true;
                            } else {
                                $this->utils->error_log('transfer '.$playerName.' from '.$api->getPlatformCode().' balance:'.$balance.' failed');
                            }
                        }
                    }else{
                        $this->utils->debug_log('query balance failed');
                    }
                }

            }
        }else{
            $this->utils->error_log('wrong api');
        }
        return false;
    }

    public function transfer_all_players_main_wallet_subwallet($game_platform_id = null, $max_bal = 1, $min_bal = 0){
        set_time_limit(0);

        $this->load->model(['wallet_model', 'player_model']);
        #get all players in game_provider_auth is registered
        #need field:type_of_player = "real" type= subwallet subwallets = [{gameplatform  }]
        $players = $this->wallet_model->getMaxMainWalletBalancePlayerList($max_bal, $min_bal);

        $this->utils->debug_log('player who have balance', $players);
        $api = $this->utils->loadExternalSystemLibObject($game_platform_id);

        $cnt=0;
        if(!empty($players)){
            $player_list = [];
            $success = 0;
            foreach ($players as $player) {
                $cnt++;
                $this->utils->debug_log('=========process player:'.$player['username'].' total:'.count($players).' current:'.$cnt);
                $result = $this->transfer_main_wallet_to_subwallet($player, $api, $max_bal);
                if(!$result) {
                    $player_list[] = $player['username'];
                }
                else {
                    $success++;
                }
            }
            $this->utils->error_log(__FUNCTION__ . ' transfer failed players', $player_list);
            $this->utils->info_log(__FUNCTION__ . ' transfer success players count', $success);
        }
    }

    public function transfer_players_main_wallet_to_subwallet_using_csv($csv_file, $game_platform_id){
        set_time_limit(0);

        $csv_file='/home/vagrant/Code/og/'.$csv_file;

        if(!file_exists($csv_file)){
            return $this->utils->error_log("File not exist!");
        }
        $api = $this->utils->loadExternalSystemLibObject($game_platform_id);

        if(!$api) {
            $this->utils->error_log('wrong api');
        }
        else {
            $csv_file = file($csv_file);

            $csv_file[0] = str_replace("\xEF\xBB\xBF", '', $csv_file[0]);
            $csv_array = array_map('str_getcsv', $csv_file);

            array_walk($csv_array, function(&$a) use ($csv_array) {
                $a = array_combine($csv_array[0], $a);
            });
            array_shift($csv_array);
            $player_list = [];
            $success = 0;
            if($csv_array) {
                foreach($csv_array as $player) {
                    $player = (array) $api->getPlayerInfoByUsername($player['player_name']);
                    $result = $this->transfer_main_wallet_to_subwallet($player, $api, PHP_INT_MAX);
                    if(!$result) {
                        $player_list[] = $player['username'];
                    }
                    else {
                        $success++;
                    }
                }

                $this->utils->error_log(__FUNCTION__ . ' transfer failed players', $player_list);
                $this->utils->info_log(__FUNCTION__ . ' transfer success players count', $success);
            }
            else {
                $this->utils->error_log('no players in csv');
            }
        }

    }

    /**
     * detail: getNotReleasedPromoPlayerListById
     *
     * @param period year/week/days/hours/minutes/seconds
     * @param time_length any number
     * @param date_base specified day
     *
     * format: date_base -time_length period
     * example : 2021-09-05 12:23:44 -1 hours
     * auto_apply_and_release_bonus_for_customize_promo/hours/1/2020-12-31
     * auto_apply_and_release_bonus_for_customize_promo/minutes/15
     * auto_apply_and_release_bonus_for_customize_promo/day/1
     *
     * @return boolean
     */

    public function auto_apply_and_release_bonus_for_customize_promo($period = null, $time_length = null, $date_base = null){
		$dates = [];

		if(!empty($period)){
			$dates['period'] = $period;
		}
		if(!empty($time_length)){
			$dates['time_length'] = $time_length;
		}
		if(!empty($date_base)){
			$dates['date_base'] = $date_base;
		}

		$promocms_ids = $this->utils->getConfig('auto_apply_and_release_bonus_for_customizepromo_promocms_id');
		$this->utils->info_log('start auto_apply_and_release_bonus_for_customize_promo promocms_ids',$promocms_ids, $period, $time_length, $date_base);
		$succ = false;
		$count = 0;
		$success_apply_id = [];

		if (!empty($promocms_ids)) {
			$this->load->model(['promorules','player_promo','player_model']);

			foreach ($promocms_ids as $promocms_id) {
				$promorule=$this->promorules->getPromoruleByPromoCms($promocms_id);
				$promorulesId = $promorule['promorulesId'];
				$players = $this->player_model->getNotReleasedPromoPlayerListById($promorulesId,$dates);
				$this->utils->printLastSQL();
				$this->utils->info_log('get not released players',$players);

				if (!empty($players)) {
					foreach ($players as $player) {
						$playerId = $player->playerId;
						$registerIp = $player->registrationIp;
						$username = $player->username;

						$test_player_list = $this->utils->getConfig('auto_apply_and_release_bonus_player_list');
						if (!empty($test_player_list)) {
							if (!in_array($username, $test_player_list)) {
								continue;
							}
						}

						if($this->player_promo->existsPlayerPromoFromSameRequestIp($promorulesId, $registerIp)){
							$this->utils->debug_log('check same registerIp', $registerIp, $playerId);
							continue;
			            }

			            try{
							$msg=null;

                            $succ=$this->lockAndTransForPlayerBalance($playerId, function()
								use($promorule, $promocms_id, $playerId, &$msg, &$extra_info, &$res, $registerIp){

								$success = true;
								$preapplication=false;
								$extra_info = [];
	                            $extra_info['order_generated_by'] = Player_promo::ORDER_GENERATED_BY_AUTO_APPLY_FROM_CRONJOB;
	                            $extra_info['player_request_ip'] = $registerIp;

								list($res, $msg)=$this->promorules->triggerPromotionFromCronjob($playerId, $promorule, $promocms_id, $preapplication, null, $extra_info);
								return $success;
							});

							if($succ && $res){
								$count += 1;
								$success_apply_id[] = $playerId;
							}
							$this->utils->info_log(__METHOD__,'apply promo result on order:',$playerId, $succ, $res, $msg, $promocms_id, $extra_info, $registerIp);

                        }catch(WrongBonusException $e){
							$this->utils->error_log($e);
						}
					}
				}else{
					$this->utils->info_log('No eligible players found',$players);
				}
			}
		}
		$this->utils->info_log('end auto_apply_and_release_bonus_for_customize_promo', $count, $success_apply_id);
    }


    /**
     * Command to replace permissions under roles with new ones.
     * Usage ex: sudo./command.sh replace_permissions '1_2' '3_4'
     *           sudo./command.sh replace_permissions '1' '3_4'
     *           sudo./command.sh replace_permissions '5' '2'
     * result: all roles with the permission in $old_permissions will get those
     *         permissions deleted and be replaced by permissions in $new_permissions
     * note: this will check the current 'functions' table insteand of the permissions.json file
     *
     * @param string $old_permissions (underscore_separated funcIds of permissions)
     * @param string $new_permissions (underscore_separated funcIds of permissions)
     * @return null
     */
    public function replace_permissions($old_permissions, $new_permissions){
        //check to see if parameters are not missing.
        if(!$old_permissions || !$new_permissions){
            return $this->utils->error_log('Some parameters are missing.');
        }

        $old_permissions = array_unique(explode('_',$old_permissions));
        $new_permissions = array_unique(explode('_',$new_permissions));

        $this->utils->info_log('START OF FUNCTION', 'old_permissions', $old_permissions, 'new_permissions', $new_permissions);
        $this->load->model(['role_functions']);

        //checking if there is conflicting funcIds in each parameter
        foreach($old_permissions as $old_permission) {
            if(in_array($old_permission, $new_permissions)){
                return $this->utils->error_log('The funcId ' . $old_permission . ' cannot also be in $new_permissions.');
            }
        }

        //checking if permissions in $old_permissions and $new_permissions exists
        foreach(array_merge($old_permissions,$new_permissions) as $func_id){
            if (!$this->role_functions->functionExists($func_id)) {
                return $this->utils->error_log('The funcId ' . $func_id . ' does not exist.');
            }
        }

        //getting all roles with permissions provided by $old_permissions
        $rolefunctions = $this->role_functions->getRoleFunctionsByFuncId($old_permissions);
        $rolefunctions_giving = $this->role_functions->getRoleFunctionsGivingByFuncId($old_permissions);

        $this->utils->info_log('START ADDING NEW FUNCTIONS');
        //adding permissions to roles provided by $new_permissions
        $function_roleIds = []; //roleIds that have been given the new roles
        foreach($rolefunctions as $rolefunction) {
            //check if permissions have been given to the same role
            if(!in_array($rolefunction['roleId'], $function_roleIds)){
                foreach($new_permissions as $func_id){
                    //check if permission already exists with the role
                    if(!$this->role_functions->roleFunctionExistsByRoleId($func_id, $rolefunction['roleId'])) {
                        //adding the new permission to the role
                        $insert_data = [
                            'funcId' => $func_id,
                            'roleId' => $rolefunction['roleId']
                        ];
                        $this->utils->debug_log( 'inserting into rolefunctions:', $insert_data);
                        $this->role_functions->insertIntoRoleFunctions($insert_data);
                    }
                }
            }
            $function_roleIds[] = $rolefunction['roleId'];
        }

        $rolefunctions_giving_roleIds = []; //roleIds that have been given the new roles
        foreach($rolefunctions_giving as $rolefunction_giving) {
            //check if permissions have been given to the same role
            if(!in_array($rolefunction_giving['roleId'], $rolefunctions_giving_roleIds)){
                foreach($new_permissions as $func_id){
                    //check if permission already exists with the role
                    if(!$this->role_functions->roleFunctionGivingExistsByRoleId($func_id, $rolefunction_giving['roleId'])) {
                        //adding the new permission to the role
                        $insert_data = [
                            'funcId' => $func_id,
                            'roleId' => $rolefunction_giving['roleId']
                        ];
                        $this->utils->debug_log('inserting into rolefunctions_giving:', $insert_data);
                        $this->role_functions->insertIntoRoleFunctionsGiving($insert_data);
                    }
                }
            }
            $rolefunctions_giving_roleIds[] = $rolefunction_giving['roleId'];
        }

        $this->utils->info_log( 'START DELETING OLD FUNCTIONS');
        //removing their permissions that is in $old_permissions
        foreach($rolefunctions as $rolefunction) {
            $this->utils->debug_log('deleting from rolefunctions:', $rolefunction);
            $this->role_functions->deleteFromRoleFunctions($rolefunction);
        }
        foreach($rolefunctions_giving as $rolefunction_giving) {
            $this->utils->debug_log('deleting from rolefunctions_giving:', $rolefunction_giving);
            $this->role_functions->deleteFromRoleFunctionsGiving($rolefunction_giving);
        }
        $this->utils->info_log('END OF FUNCTION');
    }

	public function init_admin_logs_monthly_table($date=null){
		if(empty($date)){
			$date=new DateTime('tomorrow');
		}else{
			$date=new DateTime($date);
		}
		$monthStr=$date->format('Ym');
		$this->utils->initAdminLogsTableByDate($monthStr);
		$this->utils->info_log('initAdminLogsTableByDate this month', $monthStr);

		//next month
		$date->modify('+1 month');
		$monthStr=$date->format('Ym');
		$this->utils->initAdminLogsTableByDate($monthStr);
		$this->utils->info_log('initAdminLogsTableByDate next month', $monthStr);

	}

	public function cronjob_sync_failed_transactions_and_update(){
    	$games_with_failed_transactions_enabled = $this->config->item('games_with_failed_transactions_enabled');
    	$this->utils->debug_log('games_with_failed_transactions_enabled:', $games_with_failed_transactions_enabled);
    	$update = true;
    	if(!empty($games_with_failed_transactions_enabled)){
    		foreach ($games_with_failed_transactions_enabled as $api_id) {
    			if($this->utils->loadExternalSystemLibObject($api_id)){
    				$seconds = $this->utils->getConfig('sync_t1_sleep_seconds');
    				sleep($seconds);
    				$result  = $this->syncGameFailedTransactionAndUpdate($api_id, $update);
    				$this->utils->info_log("API ID", $api_id,'result', $result, 'sleep' , $seconds);
    			}
    		}
    	}
    	$this->utils->info_log('end');
    }

    public function automation_batch_send_internal_msg_for_OGP24282($this_month = null, $playerId = null){

		$messages_details_key = 'OGP24282';
		$messages_details = !empty($this->utils->getConfig('enabled_automation_batch_send_internal_msg')[$messages_details_key]) ? $this->utils->getConfig('enabled_automation_batch_send_internal_msg')[$messages_details_key] : false;

        $this->utils->debug_log(__METHOD__, 'messages_details', $messages_details_key, $messages_details);

		if(!$messages_details){
            return $this->utils->debug_log(__METHOD__, 'the config are missing.');
        }

        $this->load->library(['player_message_library','authentication']);
        $this->load->model(array('player_model', 'internal_message','users'));

		$group     	= isset($messages_details['group']) ? $messages_details['group'] : null;
		$min_level 	= isset($messages_details['min_level']) ? $messages_details['min_level'] : null;
		$max_level 	= isset($messages_details['max_level'])? $messages_details['max_level'] : null;
        $subject  	= isset($messages_details['subject'])? $messages_details['subject'] : null;
		$message   	= isset($messages_details['message'])? $messages_details['message'] : null;
		$disabled_reply = true;
		$currentDateTime = new DateTime();
        $today 		= $this->utils->getTodayForMysql();
        $this_month = !empty($this_month) ? $this_month : $currentDateTime->format('m');
        $userId 	= 1;
        $sender 	= $this->users->getUsernameById($userId);

        $this->utils->debug_log(__METHOD__, '------ params', $group, $min_level, $max_level, $subject, $message, $disabled_reply, $today, $this_month, $playerId, $userId, $sender);
        $allPlayer = array();
        $allPlayer = $this->player_model->getPlayerByCustomizedConditions($this_month, $group, $min_level, $max_level, $playerId);

        $this->utils->printLastSQL();
        $this->utils->debug_log(__METHOD__, '------ allPlayer', $allPlayer);

		if(count($allPlayer) <= 0){
			return $this->utils->debug_log(__METHOD__, lang('No eligible players found.'));
		}

        $this->startTrans();

        if ($allPlayer) {
			$count_suc = 0;
			$ids = [];
            foreach ($allPlayer as $player) {
				$message_lang = sprintf(lang($message), $player['username']);
				$res = $this->internal_message->addNewMessageAdmin($userId, $player['playerId'], $sender, $subject, $message_lang, TRUE, $disabled_reply);
                if ($res) {
					$count_suc += 1;
					$ids[] = $player['playerId'];
                }
            }
            $this->utils->info_log(__METHOD__, 'result', 'count', $count_suc, 'player id', $ids);
        }

        $succ = $this->endTransWithSucc();

        if (!$succ) {
            return $this->utils->info_log(__METHOD__, lang('sys.ga.erroccured'));
        } else {
            return $this->utils->info_log(__METHOD__, lang('mess.19'));
        }
    }
	/**
	 *
	 * batch_send_internal_msg_for_OGP28228
	 * @param [datetime] $date_time '20000101'
	 * @param [datetime] $periodTo   '2000-01-01 00:00:00'
	 * @param [float] $minAmount	 default to _null
	 * @param [float] $maxAmount     default to _null
	 * @param [int] $playerId        default to _null
	 * @return array
	 */
	public function batch_send_internal_msg_for_new_player_OGP28228(
		$date_time = _COMMAND_LINE_NULL,
		$minAmount = _COMMAND_LINE_NULL,
		$maxAmount = _COMMAND_LINE_NULL,
		$playerId = _COMMAND_LINE_NULL
	){

		$config = $this->utils->getConfig('batch_send_internal_msg_for_OGP28228_setting');

		if(!$this->utils->safeGetArray($config, 'enabled', false)) {
			$this->utils->debug_log(' ======================== not enabled ', ['config' => $config]);
			return;
		}

        $this->load->model(array('player_relay'));
		$_minAmount = $minAmount == _COMMAND_LINE_NULL ? null : $minAmount;
		$_maxAmount = $maxAmount == _COMMAND_LINE_NULL ? null : $maxAmount;
		$_playerId  = $playerId  == _COMMAND_LINE_NULL ? null : $playerId;
		$_date      = $date_time == _COMMAND_LINE_NULL ? $this->utils->getYesterdayForMysql() : $date_time;
		$base_minAmount = $this->utils->safeGetArray($config, 'base_minAmount', 20);
		$base_maxAmount = $this->utils->safeGetArray($config, 'base_maxAmount', 100);

		list($ftd_from, $ftd_to) = $this->utils->convertDayToStartEnd(date('Ymd', strtotime($_date)));

		$this->utils->debug_log(' ======================== conditions', [
			'_minAmount' => $_minAmount,
			'_maxAmount' => $_maxAmount,
			'_playerId' => $_playerId,
			'_date' => $_date,
			'ftd_from' => $ftd_from,
			'ftd_to' => $ftd_to,
			'base_minAmount' => $base_minAmount,
			'base_maxAmount' => $base_maxAmount,
		]);

		$playerList = $this->player_relay->getListByFirstDeposit($ftd_from, $ftd_to, $_minAmount, $_maxAmount, $_playerId);

		$this->utils->debug_log(' ======================== playerList', $playerList);

		if($playerList) {
			$this->load->library(['player_message_library','authentication']);
			$this->load->model(array('player_model', 'internal_message','users', 'player_trackingevent'));

			$count_suc = 0;
			$ids = [];
			$userId 	= 1;
			$msgSenderName = $this->player_message_library->getDefaultAdminSenderName() ?: $this->users->getUsernameById($userId);
			$this->startTrans();
			foreach ($playerList as $player) {
				// var_dump($player);

				$in_range = false;
				$same_day = false;
				$_first_deposit_amount = $player->first_deposit_amount;
				$date_ftd = $player->ftd;
				$date_reg = $player->reg;
				switch(1){
					case !empty($base_minAmount) && !empty($base_maxAmount):
						$in_range = $base_maxAmount >= $_first_deposit_amount && $base_minAmount <= $_first_deposit_amount;
						break;

					case !empty($base_minAmount) && empty($base_maxAmount):
						$in_range = $base_minAmount <= $_first_deposit_amount;
						break;

					case empty($base_minAmount) && !empty($base_maxAmount):
						$in_range = $base_maxAmount >= $_first_deposit_amount;
						break;
				}

				if($date_ftd == $date_reg){
					$same_day = true;
				}

				$subject  	= '';
				$message   	= '';
				$disabled_reply = true;

				if($same_day && $in_range) {
					$this->utils->debug_log(" ======================== same date, player_id:".$player->player_id, [
						'same_day' => $same_day,
						'in_range'=> $in_range,
						'_first_deposit_amount'=> $_first_deposit_amount
					]);
					// send rule 2
					$r2 = $this->utils->safeGetArray($config, 'r2', []);
					$subject = $this->utils->safeGetArray($r2, 'subject', 'É o seu dia de sorte!');
					$message = $this->utils->safeGetArray($r2, 'content', '');
				} else if (!$same_day) {
					$this->utils->debug_log(" ======================== diff date, player_id:".$player->player_id, [
						'same_day' => $same_day,
						'in_range'=> $in_range,
						'_first_deposit_amount'=> $_first_deposit_amount
					]);
					// send rule 1
					$r1 = $this->utils->safeGetArray($config, 'r1', []);
					$subject = $this->utils->safeGetArray($r1, 'subject', 'Parabéns pelo seu primeiro depósito!');
					$message = $this->utils->safeGetArray($r1, 'content', '');
				} else {
					$this->utils->debug_log(" ======================== skip, player_id:".$player->player_id, [
						'same_day' => $same_day,
						'in_range'=> $in_range,
						'_first_deposit_amount'=> $_first_deposit_amount
					]);
					continue;
				}
				$res = false;
				if(!$this->player_trackingevent->getNotifyBySource($player->player_id, player_trackingevent::TRACKINGEVENT_SOURCE_TYPE_R1)) {
					$message_lang = sprintf(lang($message), $player->username);
					$res = $this->internal_message->addNewMessageAdmin($userId, $player->player_id, $msgSenderName, $subject, $message_lang, TRUE, $disabled_reply);
				}
                if ($res) {
					$count_suc += 1;
					$ids[] = $player->player_id;
					$this->player_trackingevent->createSettledNotify($player->player_id, player_trackingevent::TRACKINGEVENT_SOURCE_TYPE_R1, array());
                }
            }
			$succ = $this->endTransWithSucc();
            $this->utils->info_log(__METHOD__, 'result', 'count', $count_suc, 'player id', $ids);

			if (!$succ) {
				return $this->utils->info_log(__METHOD__, 'batch_send_internal_msg_for_OGP28228 success');
			} else {
				return $this->utils->info_log(__METHOD__, 'batch_send_internal_msg_for_OGP28228 fail');
			}
		} else {
			$this->utils->info_log(__METHOD__, 'batch_send_internal_msg_for_OGP28228 empty list');
		}
    }

	public function BatchAddPrefixForDeletedPlayers($player_id_list, $include_username = "N") {
		// "sample 1111 2222 3333 4444" or all

		$this->load->model(['player_model']);

		$total_updated_players = 0;

		$playerIds = [];

		if($player_id_list == "all") {
			$players = $this->player_model->getAllSoftDeletedPlayers();
		} else {

			$ids = array_map('trim', array_filter(explode(' ', $player_id_list)));
			$players = $this->player_model->getAllSoftDeletedPlayers($ids);
		}

		foreach($players as $player) {


			$email = $player["email"];

			$contactNumber = $player["contactNumber"];

			$deleted_at = $player["deleted_at"];

			$player_id = $player["playerId"];

			$username = $player["username"];

			$updated = false;

			$date = date("YmdHis", strtotime($deleted_at));

			if(!empty($email)) {

				// check if the deleted_at is already prepend in the email
				// $date = date("YmdHis", strtotime($deleted_at));

				if (strpos($email, $date . "-") === false) {
					$new_email = $date . "-" . $email;

					$res = $this->player_model->updatePlayerEmail($player_id, $new_email);

					if($res) {
						$updated = true;
						$this->utils->info_log('(email) UPDATED PLAYER', $username, 'OLD EMAIL', $email, 'UPDATED EMAIL', $new_email);
					}
				}

			}

			if(!empty($contactNumber)) {

				// check if the deleted_at is already prepend in the email
				// $date = date("YmdHis", strtotime($deleted_at));

				if (strpos($contactNumber, $date . "-") === false) {
					$new_contact = $date . "-" . $contactNumber;

					$this->player_model->updatePlayerdetails($player_id, ["contactNumber" => $new_contact]);


					$updated = true;


					$this->utils->info_log('(contactNo) UPDATED PLAYER', $username, 'OLD CONTACT', $contactNumber, 'UPDATED CONTACT', $new_contact);

				}

			}

			if($include_username === 'Y') {
				if (strpos($username, $date . "-") === false) {
					$new_username = $date . "-" . $username;

					$this->player_model->updatePlayer($player_id, ["username" => $new_username]);
					$this->utils->info_log('(username) UPDATED PLAYER', $username, 'OLD USERNAME', $username, 'UPDATED USERNAME', $new_username);

					$updated = true;
				}

				$gamename = isset($player["gameName"]) ? $player["gameName"]: '';
				$new_gamename = '';
				if ((strpos($gamename, $date . "-") === false) && !empty(trim($gamename))) {
					$new_gamename = $date . "-" . $gamename;
					$this->player_model->updatePlayer($player_id, ["gameName" => $new_gamename]);
					$this->utils->info_log('(gameName) UPDATED PLAYER', $gamename, 'OLD gameName', $gamename, 'UPDATED gameName', $new_gamename);
				}
			}

			if($updated) {

				$total_updated_players++;

				$playerIds[] = $player_id;

			}

		}

		$this->utils->info_log(__METHOD__, 'result', 'total_updated_players', $total_updated_players, 'player id', $playerIds);
	}

	public function BatchRevertPrefixFromDeletedPlayersUsername ($player_id_list){
		$this->load->model(['player_model']);

		$total_updated_players = 0;

		$playerIds = [];

		if($player_id_list == "all") {
			$players = $this->player_model->getAllSoftDeletedPlayers();
		} else {

			$ids = array_map('trim', array_filter(explode(' ', $player_id_list)));
			$players = $this->player_model->getAllSoftDeletedPlayers($ids);
		}

		foreach($players as $player) {

			$player_id = $player["playerId"];

			$username = $player["username"];

			$deleted_at = $player["deleted_at"];

			$updated = false;

			$date = date("YmdHis", strtotime($deleted_at));

			$to_upadte = [];

			if (strpos($username, $date . "-") !== false) {
				$new_username = str_replace("$date-" , '', $username);
				if($this->player_model->usernameExist($new_username)) {
					$this->utils->debug_log('(username) skip usernameExist', $new_username, $username );

					continue;
				}
				// $this->player_model->updatePlayer($player_id, ["username" => $new_username]);
				$to_update["username"] = $new_username;
				$this->utils->info_log('(username) UPDATED PLAYER', $username, 'OLD USERNAME', $username, 'UPDATED USERNAME', $new_username);


			}

			$gamename = isset($player["gameName"]) ? $player["gameName"]: '';
			$new_gamename = '';
			if ((strpos($gamename, $date . "-") !== false) && !empty(trim($gamename))) {
				$new_gamename = str_replace("$date-" , '', $gamename);
				$to_update["gameName"] = $new_gamename;
				// $this->player_model->updatePlayer($player_id, ["gameName" => $new_gamename]);
				$this->utils->info_log('(gameName) UPDATED PLAYER', $gamename, 'OLD gameName', $gamename, 'UPDATED gameName', $new_gamename);
			}

			if (!empty($to_update)){
				$this->startTrans();
				$this->player_model->updatePlayer($player_id, $to_update);
				$updated = true;
				$succ = $this->endTransWithSucc();
				if(!$succ) {

					$this->utils->info_log(__METHOD__, "fail to revert player playerId [$player_id] username [$username]");
				}
			}
			if($updated) {

				$total_updated_players++;

				$playerIds[] = $player_id;

			}

		}

		$this->utils->info_log(__METHOD__, 'result', 'total_updated_players', $total_updated_players, 'player id', $playerIds);
	}

    /**
	* Soft Delete PaymentAccount by payment account ids
    * @access	public
	* @param	string separated by spaces
    * @example  sudo ./command.sh softDeletePaymentAccountByPaymentAccountId  '12122 144l 112'
	*/
 	public function softDeletePaymentAccountByPaymentAccountIds($payment_account_ids, $revertSoftDelete = 'false'){
 		$this->CI->load->model(array('payment_account'));
		$arr = array_map('trim', array_filter(explode(' ', $payment_account_ids)));
		if($revertSoftDelete == 'true'){
			$data = array(
               'status' => payment_account::STATUS_INACTIVE,
               'updated_at' => $this->utils->getNowForMysql(),
               'deleted_at' => null,
	        );
	        $this->db->where_in('id', $arr);
			$this->db->update('payment_account', $data);

			$this->utils->debug_log('Success to RevertSoftDelete payment account Ids', $arr);
			$this->utils->recordAction('Payment Account Management', 'Delete Payment Account', "Command is softDeletePlayersByPlayerId ,revert deleted payment account ids :".json_encode($arr));
		}else{
			$data = array(
               'status' => payment_account::STATUS_DELETE,
               'deleted_at' => $this->utils->getNowForMysql(),
	        );
	        $this->db->where_in('id', $arr);
			$this->db->update('payment_account', $data);

			$this->utils->debug_log('Success to SoftDelete payment account Ids', $arr);
			$this->utils->recordAction('Payment Account Management', 'Delete Payment Account', "Command is softDeletePlayersByPlayerId ,deleted payment account ids :".json_encode($arr));
		}
	}

    public function auto_enable_player_withdrawal_after_datetime_limit() {
        $this->load->model(['player_preference']);
        $current_date = date('Y-m-d H:i:s');
        $this->utils->debug_log('============================ Start '.__METHOD__.' ============================', $current_date);
        $data_count_updated = $this->player_preference->enablePlayerWithdrawalByCurrentDate($current_date);
        $this->utils->info_log(__METHOD__, ' Current Date Time', $current_date, 'data_count_updated', $data_count_updated);
        $this->utils->debug_log('============================ End '.__METHOD__.' ============================', $current_date);
    }

    /**
     * Check the data-table,"game_description"
     * The above games are limited by flag_new_game=1, and created_on/updated_at during with in the params, $dataFrom and $dateTo
     *
     * The command examples,
     * sudo ./admin/shell/command.sh checkNewGameAndAddToQuest
     * nohup bash ./admin/shell/command_mdb_noroot.sh thb checkNewGameAndAddToQuest >> logs/command_mdb_noroot-checkNewGameAndAddToQuest.thb.log 2>&1 &
     * sudo ./admin/shell/command.sh checkNewGameAndAddToQuest '2022-09-19 19:56:35' '2022-09-19 19:58:35' >> logs/debug_generate_player_report_hourly.log 2>&1 &
     * sudo ./admin/shell/command.sh checkNewGameAndAddToQuest '2022-01-04 12:05:33' '2022-01-04 12:07:33' >> logs/debug_generate_player_report_hourly.log 2>&1 &
     *
     * @param string $dataFrom
     * @param string $dateTo
     * @return void
     */
    public function checkNewGameAndAddToQuest($dataFrom = null, $dateTo = null){
		$this->load->model(['game_description_model', 'quest_manager']);
		$lists = $this->game_description_model->queryNewGamesByDateTime($dataFrom, $dateTo);
		$this->utils->debug_log('Start checkNewGameAndAddToQuest');
		$this->utils->debug_log('Count new games : ', count($lists));

		$results = [];
		if(!empty($lists)){
            $self = $this;
			foreach ($lists as $key => $list) {
                $uniqueId = $list['game_platform_id'] . '_'. random_string('numeric');
				$this->lockAndTransForGameSyncing($uniqueId,function() use($self, $list, &$results){
					$failedQuestManagerId = [];

                    $gameDescId = $list['id'];
                    $self->quest_manager->addGameIntoManagerGameType($gameDescId, $failedQuestManagerId);
                    if(!empty($failedQuestManagerId)){
						$failedList = implode(", ", $failedQuestManagerId);
						$results[] = array(
							"id" => $list['id'],
							"english_name" => $list['english_name'],
							"questManagerIds" => $failedList,
						);
					}
                    $success = true;

                    return $success;
                }); // EOF $this->lockAndTransForGameSyncing(...
            }// EOF foreach ($lists as $key => $list) {...
        }// EOF if(!empty($lists)){...

        if(!empty($results)){
            $body = "| Game Description Id | Game Name | Quest Manager Ids  |\n";
            $body .= "| :--- | :--- | :--- |\n";
            foreach ($results as $key => $result) {
                $body .= "| {$result['id']} | {$result['english_name']} | {$result['questManagerIds']} |\n";
            }
            $dbName = $this->db->database;

            $messages = [
                "# Insert Failed!! Quest Manager Game Tree  \n",
                $body,
                "#system_alert"
            ];

            $channel = !empty($this->utils->getConfig('add_game_tree_notification_channel')) ? $this->utils->getConfig('add_game_tree_notification_channel') : 'test_mattermost_notif';
            $this->load->helper('mattermost_notification_helper');
            $user = "{$dbName}";

            sendNotificationToMattermost($user, $channel, [], $messages);
        }

        $this->utils->debug_log('End checkNewGameAndAddToQuest');
    } // EOF checkNewGameAndAddToQuest()

    /**
     * Check the data-table,"game_description"
     * The above games are limited by flag_new_game=1, and created_on/updated_at during with in the params, $dataFrom and $dateTo
     *
     * The command examples,
     * sudo ./admin/shell/command.sh checkNewGameAndAddToPromorules
     * nohup bash ./admin/shell/command_mdb_noroot.sh thb checkNewGameAndAddToPromorules >> logs/command_mdb_noroot-checkNewGameAndAddToPromorules.thb.log 2>&1 &
     * sudo ./admin/shell/command.sh checkNewGameAndAddToPromorules '2022-09-19 19:56:35' '2022-09-19 19:58:35' >> logs/debug_generate_player_report_hourly.log 2>&1 &
     * sudo ./admin/shell/command.sh checkNewGameAndAddToPromorules '2022-01-04 12:05:33' '2022-01-04 12:07:33' >> logs/debug_generate_player_report_hourly.log 2>&1 &
     *
     * @param string $dataFrom
     * @param string $dateTo
     * @return void
     */
    public function checkNewGameAndAddToPromorules($dataFrom = null, $dateTo = null){
		$this->load->model(['game_description_model', 'promorules']); //, 'group_level'
		$lists = $this->game_description_model->queryNewGamesByDateTime($dataFrom, $dateTo);
		$this->utils->debug_log('Start checkNewGameAndAddToPromorules');
		$this->utils->debug_log('Count new games : ', count($lists));
		$results = [];
		if(!empty($lists)){
            $self = $this;
			foreach ($lists as $key => $list) {
                $uniqueId = $list['game_platform_id'] . '_'. random_string('numeric');
				$this->lockAndTransForGameSyncing($uniqueId,function() use($self, $list, &$results){
					$failedPromoruleId = [];

                    $gameDescId = $list['id'];
                    $self->promorules->addGameIntoPromoRuleGameType($gameDescId, $failedPromoruleId);
                    if(!empty($failedPromoruleId)){
						$failedList = implode(", ", $failedPromoruleId);
						$results[] = array(
							"id" => $list['id'],
							"english_name" => $list['english_name'],
							"promorulesIds" => $failedList,
						);
					}
                    $success = true;

                    return $success;
                }); // EOF $this->lockAndTransForGameSyncing(...

            }// EOF foreach ($lists as $key => $list) {...
        }// EOF if(!empty($lists)){...

        if(!empty($results)){
            $body = "| Game Description Id | Game Name | promorules Ids  |\n";
            $body .= "| :--- | :--- | :--- |\n";
            foreach ($results as $key => $result) {
                $body .= "| {$result['id']} | {$result['english_name']} | {$result['promorulesIds']} |\n";
            }
            $dbName = $this->db->database;

            $messages = [
                "# Insert Failed!! promorules Game Tree  \n",
                $body,
                "#system_alert"
            ];

            $channel = !empty($this->utils->getConfig('add_game_tree_notification_channel')) ? $this->utils->getConfig('add_game_tree_notification_channel') : 'test_mattermost_notif';
            $this->load->helper('mattermost_notification_helper');
            $user = "{$dbName}";

            sendNotificationToMattermost($user, $channel, [], $messages);
        }

        $this->utils->debug_log('End checkNewGameAndAddToPromorules');
    } // EOF checkNewGameAndAddToPromorules()

	public function checkNewGameAndAddToVipCashback($dataFrom = null, $dateTo = null){
		$this->load->model(['game_description_model', 'group_level']);
		$lists = $this->game_description_model->queryNewGamesByDateTime($dataFrom, $dateTo);
		$this->utils->debug_log('Start checkNewGameAndAddToVipCashback');
		$this->utils->debug_log('Count new games : ', count($lists));
		$results = [];
		if(!empty($lists)){
			foreach ($lists as $key => $list) {
				$uniqueId = $list['game_platform_id'] . '_'. random_string('numeric');
				$self = $this;
				$this->lockAndTransForGameSyncing($uniqueId,function() use($self, $list, &$results){
					$failedCashbacRulekIds = [];
					$success =  $this->group_level->addGameOnlyIntoVipGroupCashback($list['game_platform_id'], $list['game_type_id'], $list['id'], $failedCashbacRulekIds);
					if(!empty($failedCashbacRulekIds)){
						$failedList = implode(", ", $failedCashbacRulekIds);
						$results[] = array(
							"id" => $list['id'],
							"english_name" => $list['english_name'],
							"cash_back_rule_ids" => $failedList,
						);
					}
					return $success;
				});
			}

			if(!empty($results)){
				$body = "| Game Description Id | Game Name | Cashback Rule Ids  |\n";
            	$body .= "| :--- | :--- | :--- |\n";
            	foreach ($results as $key => $result) {
            		$body .= "| {$result['id']} | {$result['english_name']} | {$result['cash_back_rule_ids']} |\n";
            	}
            	$dbName = $this->db->database;

            	$messages = [
		            "# Insert Failed!! Cashback Game Tree  \n",
		            $body,
		            "#system_alert"
		        ];

		        $channel = !empty($this->utils->getConfig('add_game_tree_notification_channel')) ? $this->utils->getConfig('add_game_tree_notification_channel') : 'test_mattermost_notif';
		        $this->load->helper('mattermost_notification_helper');
		        $user = "{$dbName}";

		        sendNotificationToMattermost($user, $channel, [], $messages);
			}
		}
		$this->utils->debug_log('End checkNewGameAndAddToVipCashback');
	}

	public function deactive_expired_promorules(){
		$cnt = 0;
		$promorules = $this->promorules->getAllPromoRule();
		$this->utils->debug_log('start deactive_expired_promorules', $promorules);
		if (!empty($promorules)) {
			$this->load->model(array('promorules'));
			foreach ($promorules as $rules) {
				if ($rules['hide_date'] < $this->utils->getNowForMysql() && $rules['status'] == 0) {

					$data['promorulesId'] = $rules['promorulesId'];
					$data['status'] = 1; // 0: active ; 1: inactive
					$data['updatedOn'] = date("Y-m-d H:i:s");
					$data['updatedBy'] = 1;

					$this->promorules->editPromoRules($data);
					$this->utils->recordAction('Marketing Management triggerGenerateCommandEvent', 'deactive_expired_promorules', json_encode($data));
					$cnt += 1;
				}
			}
		}
		$this->utils->debug_log('end deactive_expired_promorules', $cnt);
	}

	public function auto_get_usdt_crypto_currency_rate(){
		$this->CI->load->library(['cryptorate/cryptorate_get']);
		$this->CI->load->model(array('payment_account'));
		$this->utils->debug_log('Start getCryptoCurrencyRate');
		$crypto_currency = 'USDT';
		$api_name = '';
		$apiNames = $this->CI->config->item('custom_cryptorate_api');
        if(is_array($apiNames)){
            foreach ($apiNames as $customApiCryptoCurrency => $apiName) {
                if(strpos(strtoupper($crypto_currency), $customApiCryptoCurrency) !== false){
                    $api_name = $apiName;
                }
            }
        }
		list($crypto, $rate) = $this->cryptorate_get->getConvertCryptoCurrency(1, $crypto_currency, $crypto_currency, 'deposit');
		$this->utils->debug_log('===================crypto currency rate data', $api_name, $crypto_currency, $rate, 'deposit');
		if(!empty($api_name) && !empty($rate)){
			$addCryptoCurrencyRate = $this->payment_account->addCryptoCurrencyRateByJob($api_name, $crypto_currency, $rate, 'deposit');
			$channel = 'PSH004'; /// PSH004, PHP Personal Notification 004
			$message =
				'```'. PHP_EOL .
				"title: Get Crypto Currency Rate By Job".
				"currency: " .$crypto_currency. PHP_EOL.
				"rate: " .$rate. PHP_EOL.
				"run DateTime: " . $this->utils->getNowForMysql(). PHP_EOL.
				'```'. PHP_EOL;
			;
			$this->utils->debug_log('===================set data in crypto currency rate table', $addCryptoCurrencyRate);
			$this->utils->debug_log('=====notificationMMWhenGetCryptoRateByJob', $crypto_currency, $message);
			$this->sendNotificationToMattermost('Get Crypto Currency Rate By Job', $channel, $message, 'info');
		}
		$this->utils->debug_log('End getCryptoCurrencyRate');
	}

	public function auto_get_exchange_rate_for_master_currency(){
		$this->utils->debug_log('Start auto_get_exchange_rate_for_master_currency');
		$this->load->library(['exchange_rate/exchange_rate_get', 'playerapi_lib', 'super_report_lib']);
		$this->load->model(['super_report']);
		$isEnabledMDB = $this->utils->isEnabledMDB();
		$availableCurrencyList = $this->utils->getAvailableCurrencyList();
		$masterCurrency = $this->super_report_lib->getMasterCurrencyCode();
		$superReportModel = $this->super_report;
		if(!empty($masterCurrency) && !empty($availableCurrencyList)){
			$targetCurrencyKeies = array_keys($availableCurrencyList);
			foreach ($targetCurrencyKeies as $targetCurrency) {
				$rateApi = $this->super_report_lib->getExchangeApiForMaster($targetCurrency);
				$result = $this->playerapi_lib->switchCurrencyForAction($targetCurrency, function() use ($rateApi, $targetCurrency, $masterCurrency, $superReportModel){
					$result = $this->exchange_rate_get->getExchangeRate($rateApi, $masterCurrency, $targetCurrency);
					if(!empty($result) && is_array($result)){
						foreach ($result as $combinations) {
							$base = $combinations['base'];
							$target = $combinations['target'];
							$exchangeRate = $combinations['exchangeRate'];
							$superReportModel->addExchangeRateByJob($rateApi, $base, $target, $exchangeRate);
							$channel = 'PSH004'; // PSH004, PHP Personal Notification 004
							$message =
								'```'. PHP_EOL .
								"title: Get Exchange Rate By Job ". PHP_EOL.
								"apiName: ".$rateApi. PHP_EOL.
								"base: " .strtolower($base). PHP_EOL.
								"target: " .strtolower($target). PHP_EOL.
								"rate: " .$exchangeRate. PHP_EOL.
								"run DateTime: " . $this->utils->getNowForMysql(). PHP_EOL.
								'```'. PHP_EOL;
							;
							$this->utils->debug_log('=====notificationMMWhenGetExchangeRateByJob', $base, $target, $message);
							$this->sendNotificationToMattermost('Get Exchange Currency Rate By Job', $channel, $message, 'info');
						}
					}
					return $result;
				});
				//sync to super start
				if(!empty($result) && is_array($result)){
					foreach ($result as $combinations) {
						$base = $combinations['base'];
						$target = $combinations['target'];
						$exchangeRate = $combinations['exchangeRate'];
						if($isEnabledMDB){
							$result = $this->playerapi_lib->switchCurrencyForAction('super', function() use ($superReportModel, $rateApi, $base, $target, $exchangeRate){
								return $superReportModel->addExchangeRateByJob($rateApi, $base, $target, $exchangeRate);
							});
						}
					}
				}
				// sync to super end
			}
		}
		$this->utils->debug_log('End auto_get_exchange_rate_for_master_currency');
	}
    /**
	 * Refresh specific Player in specific game provider, game API should not block
	 *
	 * @param int $gamePlatformId the game Platform ID
	 *
	 * @return int $walletCountRefreshed count of wallet refreshed
	 */
	public function refresh_specific_player_balance_in_specific_game_provider($game_platform_id = _COMMAND_LINE_NULL, $player_username, $only_registered = true) {
		$this->load->model(['wallet_model', 'external_system', 'player_model', 'game_provider_auth']);

		if ($game_platform_id == _COMMAND_LINE_NULL) {
			$this->utils->error_log('Game Platform ID must not empty');
			return false;
		}

        if (empty($player_username)) {
			$this->utils->error_log('Player Username must not empty');
			return false;
		}

		# check if game API is block in SBE
		$isGameApiEnable = $this->external_system->isAnyEnabledApi([$game_platform_id]);
		$gamePlatformName = $this->external_system->getSystemName($game_platform_id);
        $player_id = $this->player_model->getPlayerIdByUsername($player_username);

		if (!$isGameApiEnable) {
			$this->utils->error_log('Game Platform is block in SBE, game platform name is: >>>>>>>>', $gamePlatformName, 'Game Platform ID is: ', $game_platform_id);
			return false;
		}

        if ($only_registered) {
            if (!$this->game_provider_auth->isRegisterd($player_id, $game_platform_id)) {
                $this->utils->error_log('Player Username not registered');
                return false;
            }
        }

		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);
		$apiResult = $api->queryPlayerBalance($player_username);

        if (isset($apiResult['unimplemented']) && $apiResult['unimplemented']) {
            $this->utils->error_log('refresh_specific_player_balance_in_specific_game_provider failed because queryPlayerBalance is unimplemented with this API : >>>>>>>>', $gamePlatformName);
            return false;
        }

        if (isset($apiResult['success']) && $apiResult['success']) {
            if (isset($apiResult['balance']) && ! is_null($apiResult['balance'])) {
                $self = $this;
                $balance = $apiResult['balance'];

                $this->wallet_model->lockAndTransForPlayerBalance($player_id, function() use($self, $player_id, $balance, $game_platform_id) {
                    $self->CI->wallet_model->refreshSubWalletOnBigWallet($player_id, $game_platform_id, $balance);
                    return true;
                });
            }
        }else{
            $this->utils->error_log('refresh_specific_player_balance_in_specific_game_provider failed for this player: >>>>>>>>', $player_username, 'apiResult', $apiResult);
            return false;
        }

		$this->utils->info_log('refresh_specific_player_balance_in_specific_game_provider apiResult: >>>>>>>>', $apiResult);

		return true;
	}

    public function transfer_specific_player_subwallet_to_main_wallet($game_platform_id, $player_username, $max_bal = 2) {
		set_time_limit(0);
		$this->load->model(['wallet_model', 'player_model']);

        if (empty($player_username)) {
			$this->utils->error_log('Player Username must not empty');
			return false;
		}

        $player_id = $this->player_model->getPlayerIdByUsername($player_username);

        $player = [
            'playerId' => $player_id,
            'username' => $player_username,
        ];

		#load apis
		$apis = $this->utils->getAllCurrentGameSystemList();

		// array_walk($players, array($this, 'transfer_subwallet_to_main_wallet'));
		$apiArr=[];

		if (empty($game_platform_id)) {
			foreach ($apis as $game_platform_id) {
				$api = $this->utils->loadExternalSystemLibObject($game_platform_id);
				$apiArr[] = $api;
			}
		} else {
			$api = $this->utils->loadExternalSystemLibObject($game_platform_id);
			$apiArr[] = $api;
		}

		if (!empty($player)) {
			$this->utils->debug_log('transfer_specific_player_subwallet_to_main_wallet ========= process player:', $player);
			$this->transfer_subwallet_to_main_wallet($player, $apiArr, $max_bal);
		} else {
            $this->utils->debug_log('transfer_specific_player_subwallet_to_main_wallet ======== please input player username');
            return false;
        }
	}

	public function sync_player_high_rollers_stream($dateTimeFromStr = null, $dateTimeToStr = null){
		$default_sync_game_logs_max_time_second = $this->config->item('default_sync_game_logs_max_time_second');
		set_time_limit($default_sync_game_logs_max_time_second);

		$this->utils->debug_log('>>> start sync_player_high_rollers_stream >>> date', $dateTimeFromStr ,'-' ,$dateTimeToStr);

		$this->load->model(array('player_high_rollers_stream'));
        $dateTimeTo = $dateTimeFrom = new DateTime();
        if(empty($dateTimeFromStr)){
            $dateTimeFromStr = $dateTimeFrom->modify('-15 minutes')->format('Y-m-d H:i:00');

        }
        if(empty($dateTimeToStr)){
            $dateTimeToStr = $dateTimeTo->format('Y-m-d H:i:s');
        }
        $resp = $this->player_high_rollers_stream->sync(new DateTime($dateTimeFromStr), new DateTime($dateTimeToStr));
		$this->utils->debug_log('>>> end sync_player_high_rollers_stream >>>','response', $resp);
		return;
	}

    public function refresh_player_main_wallet_for_all_currency($username){
    	$this->load->model(['player_model', 'multiple_db_model']);
		$playerId=$this->player_model->getPlayerIdByUsername($username);
		if(!empty($playerId)){
			$succ=$this->multiple_db_model->refreshAllCurrencyWalletsForPlayer($playerId);
			if(!$succ){
				$this->utils->error_log('refresh all currency wallets failed', $username);
			}
		}else{
			$this->utils->error_log('not found username', $username);
		}

	   	$this->utils->info_log('done');

	}

	public function sync_player_to_other_currency($username){
    	$this->load->model(['player_model', 'multiple_db_model']);
		$playerId=$this->player_model->getPlayerIdByUsername($username);
		if(!empty($playerId)){
			$succ=$this->syncPlayerCurrentToMDB($playerId, true);
			if(!$succ){
				return $this->utils->error_log('sync_player_to_other_currency failed', $username);
			}
			$succ=$this->multiple_db_model->refreshAllCurrencyWalletsForPlayer($playerId);
			if(!$succ){
				return $this->utils->error_log('refresh all currency wallets failed', $username);
			}
		}else{
			$this->utils->error_log('not found username', $username);
		}
	}

	public function batch_update_player_sales_agent($token){
		$this->utils->debug_log(__METHOD__, 'start token', $token);
		$this->load->model(array('player_model', 'sales_agent'));

		/** @var \Sales_agent $sales_agent */
		$sales_agent = $this->{"sales_agent"};

		$queue_result_model = $this->queue_result;
		$data = $this->initJobData($token);

		$count_success = [];
		$count_failed = [];

		$params = [];
		if (isset($data['params']) && !empty($data['params'])) {
			$params = $data['params'];
		}

		$player_id_list = isset($params['player_ids']) ? $params['player_ids'] : null;
		$sales_agent_id = isset($params['sales_agent_id']) ? $params['sales_agent_id'] : null;
		$operator = isset($params['operator']) ? $params['operator'] : null;

		$playerIds = [];
		foreach($player_id_list as $player_id) {
			$player_sales_agent = $sales_agent->getPlayerSalesAgentDetailById($player_id);
			if (!empty($player_sales_agent)) {
				$data = [
					'sales_agent_id' => $sales_agent_id,
					'updated_by' => $operator,
					'updated_at' => $this->utils->getNowForMysql()
				];

				$sales_agent->updatePlayerSalesAgent($player_id, $data);
			}else{
				$data = [
					'player_id' => $player_id,
					'sales_agent_id' => $sales_agent_id,
					'created_at' => $this->utils->getNowForMysql()
				];

				$sales_agent->addPlayerSalesAgent($data);
			}
			$count_success[] = $player_id;
		}
		$this->utils->debug_log(__METHOD__, 'end result', 'total_updated_players', $count_success, 'player id', $playerIds);
	}

	#testing
	public function doRollbackRemoteWalletTransaction($playerId = 2, $gamePlatformId = 6062, $uniqueId = 'game-6062-debit-64cb4884110c580001c81c3c'){
		$this->load->model(['wallet_model']);
		$result = $this->wallet_model->rollbackRemoteWallet($playerId, $gamePlatformId, $uniqueId);
		$this->utils->debug_log('doRollbackRemoteWalletTransaction result: ', $result);
		return true;
	}

	public function testLockTable($table, $sleep = 60){
		$this->load->model(['player_model']);
		$success = $this->player_model->testlockTable($table, $sleep);
		return $success;
	}

	public function do_manual_sync_gamelist_from_gamegateway($game_platform_id = null, $is_update = false, $force_game_list_update = false){
		$this->load->library("game_list_lib");

		$uniqueId = $game_platform_id . '_'. random_string('numeric');
		if(empty($game_platform_id)){
			return false;
		}

		$self = $this;
		$syncedJsonResult = null;
		$result = $this->lockAndTransForGameSyncing($uniqueId,function() use($self,$game_platform_id,&$syncedJsonResult,$is_update,$force_game_list_update){
			$syncedJsonResult = $self->game_list_lib->do_sync_game_list_from_gamegateway($game_platform_id,$is_update,$force_game_list_update);
			$isValidJson = $self->utils->isValidJson($syncedJsonResult);

			return $isValidJson;
		});

		if($result){
			// echo $syncedJsonResult;
			return true;
		}else{
			$this->utils->error_log(__METHOD__.' ERROR inserting games into group level cashback tables: group_level_cashback_game_platform,group_level_cashback_game_type,group_level_cashback_game_description,error inserting into promorulesgamebetrule table for promo rule OR  in game syncing via gamegateway');
			// echo $syncedJsonResult;
			return false;
		}
	}

	public function do_manual_sync_gamelist_from_gamegateway_by_queue($token){
		//load from token
        $data=$this->initJobData($token);
        $token = $data['token'];
        $params = json_decode($data['full_params'],true);
        $gamePlatformId = $params['game_platform_id'];
        $this->utils->debug_log('load from queue:', $token, $params, 'JobData:', $data);
       	$this->do_manual_sync_gamelist_from_gamegateway($gamePlatformId);
	}

	public function do_sync_game_tag_from_one_to_other_mdb($sourceDb, $gameId = null){
		$this->load->model(['multiple_db_model']);
        $rlt = $this->multiple_db_model->syncGameTagFromOneToOtherMDB($sourceDb, $gameId);
        $this->utils->debug_log('do_sync_game_tag_from_one_to_other_mdb:'.$sourceDb, $rlt);
	}

	public function monitor_duplicate_external_account_id() {
		$this->load->model(['game_provider_auth']);
		$this->load->helper('mattermost_notification_helper');
	
		$monitor_duplicate_game_account_base_url = $this->utils->getConfig('monitor_duplicate_game_account_base_url');
		$baseUrl = isset($monitor_duplicate_game_account_base_url) && $monitor_duplicate_game_account_base_url != null ? $monitor_duplicate_game_account_base_url : 'http://admin.og.local';
		$message_header = ":warning: #ALERT_DUPLICATE_GAME_ACCOUNT_" . date('Y_m') . "\n";
		$text = "Duplicate game accounts detected! Datetime: " . date('Y-m-d H:i:s') . "\n\n";
		$text .= "Domain: " . $baseUrl . "\n\n";
	
		$table = "| # | Game Provider ID | External Account Id | Login Name       | Account Count |\n";
		$table .= "|---|------------------|---------------------|------------------|---------------|\n";
	
		$apis = $this->utils->getConfig("monitor_duplicate_game_account_game_apis");
		$count = 1;
		$totalDuplicateAccounts = 0;
		$results_exist = false;
	
		foreach ($apis as $api) {
			$results = $this->getDuplicateExternalAccountId($api);
	
			if (!empty($results)) {
				$results_exist = true;
				foreach ($results as $data) {
					$table .= sprintf(
						"| %-2d | %-16d | %-19s | %-16s | %-13d |\n",
						$count++,
						$data['game_provider_id'],
						$data['external_account_id'],
						$data['login_name'],
						$data['account_count']
					);
					$totalDuplicateAccounts += $data['account_count'];
				}
			}
		}
	
		if ($results_exist) {
			$text .= $table;
			$text .= "\nTOTAL duplicates count: " . ($totalDuplicateAccounts) . "\n";
	
			$message_content = array(
				$message = array(
					'type' => 'warning',
					'text' => $text
				)
			);
			$channel = $this->utils->getConfig('monitor_duplicate_game_account_mattermost_channel');
			$user = $this->utils->getConfig('monitor_duplicate_game_account_mattermost_user');
	
			sendNotificationToMattermost($user, $channel, $message_content, $message_header);
		}
	}
	
	private function getDuplicateExternalAccountId($game_platform_id) {
		$sql = <<<EOD
select 
external_account_id,
game_provider_id, 
GROUP_CONCAT(login_name) as login_name,
count(external_account_id) account_count 
from game_provider_auth
where game_provider_id = ? 
and external_account_id is not null
and external_account_id<>''
and register=1
group by external_account_id
having account_count > 1
EOD;
	
		$params = [$game_platform_id];
	
		$this->utils->debug_log('getDuplicateExternalAccountId sql', $sql, $params);
	
		return $this->game_provider_auth->runRawSelectSQLArray($sql, $params);
	}

    public function cache_active_games($game_platform_id, $ttl = 3600, $clear_cache = false) {
        $this->load->model('original_seamless_wallet_transactions', 'master');
        $cache_key = "cache_active_games_{$game_platform_id}";
        $game_codes = [];

        $params = [
            'cache_key' => $cache_key,
            'game_platform_id' => $game_platform_id,
            'ttl' => $ttl,
            'clear_cache' => $clear_cache,
        ];

        if ($clear_cache) {
            $this->utils->deleteCache($cache_key);
        }

        $data_from_cache = $this->utils->getJsonFromCache($cache_key);

        if (empty($data_from_cache)) {
            $log_title = 'data from DB';

            $selected = [
                'game_platform_id',
                'external_game_id',
            ];
    
            $where = [
                'game_platform_id' => $game_platform_id,
                'status' => 1,
            ];
    
            $data = $this->master->queryPlayerTransactionsCustom('game_description', $where, $selected);

            foreach ($data as $result) {
                array_push($game_codes, $result['external_game_id']);
            }

            $data = $game_codes;
            $total_games = count($data);
        } else {
            $log_title = 'data from cache';
            $data = $data_from_cache;
            $total_games = count($data);
        }

        $this->utils->info_log('OGP-34898', 'game_whitelist', __METHOD__, $log_title, 'params', $params, 'total_games', $total_games, 'data', $data);

        $this->utils->saveJsonToCache($cache_key, $data, $ttl);

        return array(
            'cach_key' => $cache_key,
            'total_games' => $total_games,
            'game_codes' => $data,
        );
    }

    public function depositMissingBalanceAlertByTransactionId($transactionId) {
        $this->load->model(['transactions']);
        $this->utils->info_log(__METHOD__, "Start trigger manual deposit missing balance alert by transaction id");
        $this->transactions->depositMissingBalanceAlertByTransactionId($transactionId);
        $this->utils->info_log(__METHOD__, "End trigger manual deposit missing balance alert by transaction id");

        return true;
    }
}
