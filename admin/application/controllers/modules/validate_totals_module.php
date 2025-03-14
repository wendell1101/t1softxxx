<?php

/**
 * General behaviors include :
 *
 * * Sync total information
 * * Fix yesterday total hours
 * * Validate totals
 * * Rebuild totals
 * * Fix day month and year
 * * Sync all total hours
 * * Sync total statistics
 *
 * @category Command line
 * @version 1.8.10
 * @copyright 2013-2022 tot
 */
trait validate_totals_module {

	public function rebuild_month_year_with_player($dateTimeFromStr = null, $dateTimeToStr = null, $playerName = null) {
		$this->sync_long_total_info($dateTimeFromStr, $dateTimeToStr, 'true', $playerName);
	}

	/**
	 * overview : sync long total information
	 * @param date $dateTimeFromStr
	 * @param date $dateTimeToStr
	 * @return mixed
	 */
	public function sync_long_total_info($dateTimeFromStr = null, $dateTimeToStr = null, $update_player='true', $playerName = null) {

		$this->utils->debug_log('dateTimeFromStr', $dateTimeFromStr, 'dateTimeToStr', $dateTimeToStr, 'update_player', $update_player);

		$update_player=$update_player=='true';

		$manager = $this->utils->loadGameManager();
		//default for last month
		if (empty($dateTimeFromStr) || empty($dateTimeToStr)) {
			list($dateTimeFromStr, $dateTimeToStr) = $this->utils->getFromToByMonth($this->utils->getYesterdayForMysql());
			$dateTimeFromStr = $dateTimeFromStr . ' 00:00:00';
			$dateTimeToStr = $dateTimeToStr . ' 23:59:59';
		}

		$dateTimeFrom = new \DateTime($dateTimeFromStr);
		$dateTimeTo = new \DateTime($dateTimeToStr);

		$manager->syncLongTotalStatsAll($dateTimeFrom, $dateTimeTo, $playerName);

		$this->utils->debug_log('sync long total info from', $dateTimeFromStr, 'to', $dateTimeToStr);

		if($update_player){
			$this->load->model(['player_model']);
			// -- OGP-9124
			$this->utils->debug_log('START RUNNING: updatePlayersTotalBettingAmount');
			$updatePlayersTotalBettingAmount = $this->player_model->updatePlayersTotalBettingAmount();

			$this->utils->debug_log('RESULT OF updatePlayersTotalBettingAmount: Total count of players updated = '.$updatePlayersTotalBettingAmount);

			$this->utils->debug_log('END RUNNING: updatePlayersTotalBettingAmount');
		}

	}

	/**
	 * overview : sync total information
	 *
	 * @param $dateTimeFromStr
	 * @param $dateTimeToStr
	 */
	public function sync_total_info($dateTimeFromStr, $dateTimeToStr) {
		$manager = $this->utils->loadGameManager();

		$dateTimeFrom = new \DateTime($dateTimeFromStr);
		$dateTimeTo = new \DateTime($dateTimeToStr);

		$manager->syncTotalStatsAll($dateTimeFrom, $dateTimeTo);

		$msg = $this->utils->debug_log('sync total info from', $dateTimeFromStr, 'to', $dateTimeToStr);
		$this->returnText($msg);
	}

	/**
	 * @deprecated
	 */
	public function validate_daily_balance() {

	}

	/**
	 * overview : get yesterday total hours
	 */
	public function fix_yesterday_hour_totals() {
		$yesterday = $this->utils->getYesterdayForMysql();
		$this->validate_totals($yesterday . ' 00:00:00', $yesterday . ' 23:59:59');
	}

	/**
	 * overview : validate totals
	 * @param date $fromStr
	 * @param date $toStr
	 */
	protected function validate_totals($fromStr = null, $toStr = null) {
		$this->load->model(array('game_logs', 'total_player_game_minute', 'total_player_game_hour', 'total_player_game_day',
			'total_player_game_month', 'total_player_game_year'));
		if (empty($fromStr) || empty($toStr)) {
			$from = new DateTime($this->game_logs->getFirstDatetime());
			$to = $this->utils->getNowDateTime();
			//exclude current hour
			$to = $to->modify('-1 hour');
		} else {
			$from = new DateTime($fromStr);
			$to = new DateTime($toStr);
		}
		$this->utils->debug_log('from', $from, 'to', $to);

		$rlt = "from:" . $this->utils->formatDateTimeForMysql($from) . ", to:" . $this->utils->formatDateTimeForMysql($to) . "\n";

		$fixDays = array();
		$fixMonths = array();
		$fixYears = array();

		list($start, $end, $datehour) = $this->utils->getDateHourSet($from);
		while (!$this->utils->gtEndHour($from, $to)) {
			//rebuild minute
			$this->total_player_game_minute->startTrans();
			$this->total_player_game_minute->sync(new \DateTime($start), new \DateTime($end));
			$rlt = $this->total_player_game_minute->endTransWithSucc();
			$this->utils->debug_log('start', $start, 'end', $end, 'datehour', $datehour, 'rebuild minute result', $rlt);

			list($bet_amount, $result_amount, $win_amount, $loss_amount) = $this->game_logs->sumAmount($start, $end);

			list($bet_amount_compare, $result_amount_compare, $win_amount_compare, $loss_amount_compare) = $this->total_player_game_hour->sumAmount($datehour);

			$betAmountCompareResult = $this->utils->compareResultCurrency($bet_amount, '=', $bet_amount_compare);
			$resultAmountCompareResult = $this->utils->compareResultCurrency($result_amount, '=', $result_amount_compare);
			$winAmountCompareResult = $this->utils->compareResultCurrency($win_amount, '=', $win_amount_compare);
			$lossAmountCompareResult = $this->utils->compareResultCurrency($loss_amount, '=', $loss_amount_compare);
			// $this->utils->debug_log('bet_amount', $bet_amount, 'bet_amount_compare', $bet_amount_compare,
			// 	'result_amount', $result_amount, 'result_amount_compare', $result_amount_compare,
			// 	'bet amount compare', $betAmountCompareResult,
			// 	'result amount compare', $resultAmountCompareResult);

			if (!$betAmountCompareResult || !$resultAmountCompareResult ||
				!$winAmountCompareResult || !$lossAmountCompareResult) {
				//try fix it
				$this->total_player_game_hour->startTrans();

				$count = $this->total_player_game_hour->sync(new DateTime($start), new DateTime($end));
				$this->utils->debug_log('start', $start, 'end', $end, 'datehour', $datehour, 'fix total_player_game_hour', $count);

				$fixResult = $this->total_player_game_hour->endTransWithSucc();
				$rlt .= 'start:' . $start . ', end:' . $end .
					', bet_amount:' . $bet_amount . ', bet_amount_compare:' . $bet_amount_compare .
					', win_amount:' . $win_amount . ', win_amount_compare:' . $win_amount_compare .
					', loss_amount:' . $loss_amount . ', loss_amount_compare:' . $loss_amount_compare .
					', fix result:' . $fixResult . ', count:' . $count .
					"\n";
				$fixDays[] = substr($datehour, 0, 8);
				$fixMonths[] = substr($datehour, 0, 6);
				$fixYears[] = substr($datehour, 0, 4);
			}

			list($start, $end, $datehour) = $this->utils->getNextHour($from);
			$from = new DateTime($start);
		}
		$fixDays = array_unique($fixDays);
		$fixMonths = array_unique($fixMonths);
		$fixYears = array_unique($fixYears);

		$fixRlt = $this->fixDayMonthYear($fixDays, $fixMonths, $fixYears);
		$rlt .= 'days:' . var_export($fixDays, true) . ', months:' . var_export($fixMonths, true) .
		', years:' . var_export($fixYears, true) . "\n";
		$rlt .= 'fix result:' . $fixRlt . "\n";

		$this->returnText($rlt);
	}

	/**
	 * overview : rebuild all total hours and minutes
	 *
	 * @param int $hours
	 */
	public function rebuild_total_minute_hours($hours = 7) {

		$msg = $this->utils->debug_log('hours', $hours);
		$this->returnText($msg);

		$fromDateTime = new DateTime('-' . $hours. ' hours');
		//to now
		$toDateTime = new DateTime('-1 hour');

		//don't sync month, year, rebuild minute, hour and day
		return $this->rebuild_totals($fromDateTime->format('Y-m-d H:').'00:00',
			$toDateTime->format('Y-m-d H:').'59:59', 'true', 'true',
			null, 'false', 'false', 'false', 'true');

	}

	public function rebuild_total_only_minute_hours($fromStr, $toStr) {
		$this->utils->debug_log('fromStr', $fromStr, 'toStr', $toStr);

		$options=[
			'rebuild_hour'=>true,
			'rebuild_minute'=>true,
			'rebuild_month'=>false,
			'rebuild_day'=>false,
			'rebuild_year'=>false,
			'update_player'=>false,
			'game_platform_id'=>null,
			'player_username'=>null,
			'token'=>null,
		];
		//don't sync day, month, year
		return $this->rebuild_totals_options($fromStr, $toStr, $options);
	}

	public function rebuild_total_only_minute($fromStr, $toStr, $gamePlatformId=_COMMAND_LINE_NULL, $playerUsername=_COMMAND_LINE_NULL) {
		$this->utils->debug_log('fromStr', $fromStr, 'toStr', $toStr, $gamePlatformId, $playerUsername);
		$gamePlatformId= $gamePlatformId==_COMMAND_LINE_NULL ? null : $gamePlatformId;
		$playerUsername= $playerUsername==_COMMAND_LINE_NULL ? null : $playerUsername;

		$options=[
			'rebuild_hour'=>false,
			'rebuild_minute'=>true,
			'rebuild_month'=>false,
			'rebuild_day'=>false,
			'rebuild_year'=>false,
			'update_player'=>false,
			'game_platform_id'=>$gamePlatformId,
			'player_username'=>$playerUsername,
			'token'=>null,
		];
		//don't sync day, month, year
		return $this->rebuild_totals_options($fromStr, $toStr, $options);
	}

	public function rebuild_total_only_hour($fromStr, $toStr, $gamePlatformId=_COMMAND_LINE_NULL, $playerUsername=_COMMAND_LINE_NULL) {
		$this->utils->debug_log('fromStr', $fromStr, 'toStr', $toStr, $gamePlatformId, $playerUsername);
		$gamePlatformId= $gamePlatformId==_COMMAND_LINE_NULL ? null : $gamePlatformId;
		$playerUsername= $playerUsername==_COMMAND_LINE_NULL ? null : $playerUsername;

		$options=[
			'rebuild_hour'=>true,
			'rebuild_minute'=>false,
			'rebuild_month'=>false,
			'rebuild_day'=>false,
			'rebuild_year'=>false,
			'update_player'=>false,
			'game_platform_id'=>$gamePlatformId,
			'player_username'=>$playerUsername,
			'token'=>null,
		];
		//don't sync day, month, year
		return $this->rebuild_totals_options($fromStr, $toStr, $options);
	}

	public function rebuild_total_only_day($fromStr, $toStr, $gamePlatformId=_COMMAND_LINE_NULL, $playerUsername=_COMMAND_LINE_NULL) {
		$this->utils->debug_log('fromStr', $fromStr, 'toStr', $toStr, $gamePlatformId, $playerUsername);
		$gamePlatformId= $gamePlatformId==_COMMAND_LINE_NULL ? null : $gamePlatformId;
		$playerUsername= $playerUsername==_COMMAND_LINE_NULL ? null : $playerUsername;

		$options=[
			'rebuild_hour'=>false,
			'rebuild_minute'=>false,
			'rebuild_month'=>false,
			'rebuild_day'=>true,
			'rebuild_year'=>false,
			'update_player'=>false,
			'game_platform_id'=>$gamePlatformId,
			'player_username'=>$playerUsername,
			'token'=>null,
		];
		//don't sync day, month, year
		return $this->rebuild_totals_options($fromStr, $toStr, $options);
	}

	public function rebuild_total_only_month($fromStr, $toStr, $gamePlatformId=_COMMAND_LINE_NULL, $playerUsername=_COMMAND_LINE_NULL) {
		$this->utils->debug_log('fromStr', $fromStr, 'toStr', $toStr, $gamePlatformId, $playerUsername);
		$gamePlatformId= $gamePlatformId==_COMMAND_LINE_NULL ? null : $gamePlatformId;
		$playerUsername= $playerUsername==_COMMAND_LINE_NULL ? null : $playerUsername;

		$options=[
			'rebuild_hour'=>false,
			'rebuild_minute'=>false,
			'rebuild_month'=>true,
			'rebuild_day'=>false,
			'rebuild_year'=>false,
			'update_player'=>false,
			'game_platform_id'=>$gamePlatformId,
			'player_username'=>$playerUsername,
			'token'=>null,
		];
		//only sync month
		return $this->rebuild_totals_options($fromStr, $toStr, $options);
	}

	/**
	 * overview : rebuild only total hours and minutes
	 *
	 * @param int $hours
	 */
	public function rebuild_total_only_minute_hours_day($fromStr, $toStr) {

		$this->utils->debug_log('fromStr', $fromStr, 'toStr', $toStr);

		//don't sync month, year
		return $this->rebuild_totals($fromStr, $toStr, 'true', 'true', null, 'false', 'false', 'false');

	}

	public function rebuild_total_day_month_year_without_player($fromStr = '_null', $toStr = '_null', $token='_null') {
		return $this->rebuild_totals($fromStr, $toStr, 'false', 'false',
			null, 'true', 'true', 'false');
	}

	public function rebuild_total_only_minute_hours_day_only_one_player($fromStr, $toStr, $playerUsername) {
		if(empty($fromStr) || empty($toStr) || empty($playerUsername)){
			$this->utils->error_log('wrong parameter, cannot be empty', 'fromStr', $fromStr, 'toStr', $toStr, 'playerUsername', $playerUsername);
			return false;
		}
		$options=[
			'rebuild_hour'=>true,
			'rebuild_minute'=>true,
			'rebuild_month'=>false,
			'rebuild_day'=>true,
			'rebuild_year'=>false,
			'update_player'=>false,
			'game_platform_id'=>null,
			'player_username'=>$playerUsername,
			'token'=>null,
		];
		$this->utils->debug_log('fromStr', $fromStr, 'toStr', $toStr, $playerUsername, $options);
		//don't sync month, year
		$this->rebuild_totals_options($fromStr, $toStr, $options);
	}

	public function rebuild_total_only_minute_hours_day_only_one_game($fromStr, $toStr, $gamePlatformId) {
		if(empty($fromStr) || empty($toStr) || empty($gamePlatformId)){
			$this->utils->error_log('wrong parameter, cannot be empty', 'fromStr', $fromStr, 'toStr', $toStr, 'gamePlatformId', $gamePlatformId);
			return false;
		}

		$options=[
			'rebuild_hour'=>true,
			'rebuild_minute'=>true,
			'rebuild_month'=>false,
			'rebuild_day'=>true,
			'rebuild_year'=>false,
			'update_player'=>false,
			'game_platform_id'=>$gamePlatformId,
			'player_username'=>null,
			'token'=>null,
		];
		$this->utils->debug_log('fromStr', $fromStr, 'toStr', $toStr, $gamePlatformId, $options);
		//don't sync month, year
		$this->rebuild_totals_options($fromStr, $toStr, $options);
	}

	/**
	 * rebuild_totals
	 * @param  string $fromStr
	 * @param  string $toStr
	 * @param  string $rebuild_hour
	 * @param  string $rebuild_minute
	 * @param  string $token
	 * @param  string $rebuild_month
	 * @param  string $rebuild_year
	 * @param  string $update_player
	 */
	public function rebuild_totals($fromStr = null, $toStr = null, $rebuild_hour = 'false', $rebuild_minute='false',
		$token=null, $rebuild_month='true', $rebuild_year='true', $update_player='true', $rebuild_day='true') {

		$options=[
			'rebuild_hour'=>$rebuild_hour=='true',
			'rebuild_minute'=>$rebuild_minute=='true',
			'rebuild_month'=>$rebuild_month=='true',
			'rebuild_day'=>$rebuild_day=='true',
			'rebuild_year'=>$rebuild_year=='true',
			'update_player'=>$update_player=='true',
			'token'=>$token,
			'game_platform_id'=>null,
			'player_username'=>null,
		];
		$this->rebuild_totals_options($fromStr, $toStr, $options);
	}

	/**
	 *
	    'from'=>string,
	    'to'=>string,
		'rebuild_hour'=>boolean,
		'rebuild_minute'=>boolean,
		'rebuild_month'=>boolean,
		'rebuild_day'=>boolean,
		'rebuild_year'=>boolean,
		'update_player'=>boolean,
		'game_platform_id'=>int,
		'player_username'=>string,
		'token'=>string,

	 *
	 * @param  string $configFile
	 */
	public function rebuild_totals_from_config_file($configFile) {
		if(empty($configFile)){
			$this->utils->error_log('config file is required');
			return false;
		}
		$configFilePath=realpath($configFile);
		if(!file_exists($configFilePath)){
			$this->utils->error_log('config file does not exist', $configFilePath);
			return false;
		}
		$configJson=json_decode(file_get_contents($configFilePath), true);
		if(empty($configJson)){
			$this->utils->error_log('wrong config file', $configFilePath, file_get_contents($configFilePath));
			return false;
		}
		$fromStr=$configJson['from'];
		$toStr=$configJson['to'];
		$options=$configJson;
		unset($options['from']);
		unset($options['to']);
		$this->rebuild_totals_options($fromStr, $toStr, $options);
	}

	/**
	 * overview : rebuild totals
	 * @param date $fromStr
	 * @param date $toStr
	 * @param array $options
	 */
	public function rebuild_totals_options($fromStr = null, $toStr = null, $options=null) {

		$lock_rebuild_reports_range= $this->utils->getConfig('lock_rebuild_reports_range');
		$from_day = $this->utils->formatDateForMysql(new DateTime($fromStr));
		$to_day = $this->utils->formatDateForMysql(new DateTime($toStr));
		if(!empty($lock_rebuild_reports_range)){
			if($this->utils->isRebuildReportDateLocked($lock_rebuild_reports_range,$from_day,$to_day,$rlt)){
				$this->utils->error_log(sprintf(lang("Regenarate All Report has lock - should not be equal or older than  %s  "),$rlt['cutoff_day']));
				return;
			}
		}
		$this->utils->debug_log('rebuild_totals_options', $fromStr, $toStr, $options);

		$rebuild_hour=@$options['rebuild_hour'];
		$rebuild_minute=@$options['rebuild_minute'];
		$rebuild_day=@$options['rebuild_day'];
		$rebuild_month=@$options['rebuild_month'];
		$rebuild_year=@$options['rebuild_year'];
		$update_player=@$options['update_player'];
		$player_username=@$options['player_username'];
		$game_platform_id=@$options['game_platform_id'];
		$token=@$options['token'];
		if($token=='_null' || $token=='null'){
			$token=null;
		}
		$playerId = null;
		if(!empty($player_username)){
			$playerId = $this->player_model->getPlayerIdByUsername($player_username);
		}

		$default_sync_game_logs_max_time_second = $this->config->item('default_sync_game_logs_max_time_second');

		set_time_limit($default_sync_game_logs_max_time_second);

		$this->utils->debug_log('fromStr', $fromStr, 'toStr', $toStr, 'rebuild_hour', $rebuild_hour, 'rebuild_minute', $rebuild_minute,
			'token', $token, 'rebuild_month', $rebuild_month, 'rebuild_year', $rebuild_year, 'update_player', $update_player);

		$this->load->model(array('game_logs', 'total_player_game_minute', 'total_player_game_hour', 'total_player_game_day',
			'total_player_game_month', 'total_player_game_year', 'player_model', 'points_transaction_report_hour'));
		if (empty($fromStr) || empty($toStr) || $fromStr == "null" || $toStr == "null" || $fromStr == "_null" || $toStr == "_null") {
			// $from = new DateTime($this->game_logs->getFirstDatetime());
			//last 7 days
			$from = $this->utils->getNowDateTime();
			$from = $from->modify('-7 days');
			$to = $this->utils->getNowDateTime();
			//exclude current hour
			$to = $to->modify('-1 day');
			$to=new DateTime($to->format('Y-m-d').' 23:59:59');
		} else {
			$from = new DateTime($fromStr);
			$to = new DateTime($toStr);
		}
		$this->utils->debug_log('from', $from, 'to', $to);

		$fixDays = array();
		$fixMonths = array();
		$fixYears = array();

		list($start, $end, $datehour) = $this->utils->getDateHourSet($from);
		while (!$this->utils->gtEndHour($from, $to)) {

			$fixDays[] = substr($datehour, 0, 8);
			$fixMonths[] = substr($datehour, 0, 6);
			$fixYears[] = substr($datehour, 0, 4);
			$fixResultMinute = false;
			$fixResultHour = false;

			if($rebuild_minute){
				//make half hour
				$halfHour=new DateTime($start);
				$halfHour->modify('+30 minutes');

				if($halfHour->format('Y-m-d H:i:s')<=$end){
					$this->total_player_game_minute->startTrans();
					//rebuild minute first
					$count = $this->total_player_game_minute->sync(new DateTime($start), $halfHour, $playerId, $game_platform_id);
					$this->utils->debug_log('start', $start, 'halfHour', $halfHour, 'datehour', $datehour, 'fix total_player_game_minute', $count);
					$fixResultMinute = $this->total_player_game_minute->endTransWithSucc();

					$this->utils->debug_log('commit minute half hour', $fixResultMinute);

					$this->total_player_game_minute->startTrans();
					//rebuild minute first
					$count = $this->total_player_game_minute->sync($halfHour, new DateTime($end), $playerId, $game_platform_id);
					$this->utils->debug_log('halfHour', $halfHour, 'end', $end, 'datehour', $datehour, 'fix total_player_game_minute', $count);
					$fixResultMinute = $this->total_player_game_minute->endTransWithSucc();

					$this->utils->debug_log('commit minute', $fixResultMinute);
				}else{
					$this->total_player_game_minute->startTrans();
					//rebuild minute first
					$count = $this->total_player_game_minute->sync(new DateTime($start), new DateTime($end), $playerId, $game_platform_id);
					$this->utils->debug_log('start', $start, 'end', $end, 'datehour', $datehour, 'fix total_player_game_minute', $count);
					$fixResultMinute = $this->total_player_game_minute->endTransWithSucc();

					$this->utils->debug_log('commit minute', $fixResultMinute);
				}
			}

			if ($rebuild_hour) {
				$this->total_player_game_hour->startTrans();

				$count = $this->total_player_game_hour->sync(new DateTime($start), new DateTime($end), $playerId, $game_platform_id);
				$msg = $this->utils->debug_log('start', $start, 'end', $end, 'datehour', $datehour, 'fix total_player_game_hour', $count);
				// $this->returnText($msg);

				$fixResultHour = $this->total_player_game_hour->endTransWithSucc();

				$msg = $this->utils->debug_log('commit hour', $fixResultHour);
				// $this->returnText($msg);

				$this->load->model(array('game_logs'));
				$this->points_transaction_report_hour->startTrans();
				$pointsTransactionTeportHourData = $this->points_transaction_report_hour->sync(new DateTime($start), new DateTime($end), $playerId, 'true');
				$msg = $this->utils->debug_log('start', $start, 'end', $end);
				$fixPointsTransactionResultHour = $this->points_transaction_report_hour->endTransWithSucc();
			}

			list($start, $end, $datehour) = $this->utils->getNextHour($from);
			$from = new DateTime($start);
		}
		$fixDays = array_unique($fixDays);
		$fixMonths = array_unique($fixMonths);
		$fixYears = array_unique($fixYears);

		$msg = $this->utils->debug_log('days', $fixDays, 'months', $fixMonths, 'years', $fixYears);
		// $this->returnText($msg);
		if(!$rebuild_day){
			$fixDays=null;
		}
		if(!$rebuild_month){
			$fixMonths=null;
		}
		if(!$rebuild_year){
			$fixYears=null;
		}
		$fixRlt = $this->fixDayMonthYear($fixDays, $fixMonths, $fixYears, $playerId, $game_platform_id);
		$msg = $this->utils->debug_log('fixDayMonthYear result', $fixRlt);

		if(!empty($token)){
			$result = array('minute'=>$fixResultMinute,'hour'=>$fixResultHour, 'days'=> $fixDays, 'months'=> $fixMonths, 'years'=> $fixYears, 'fixDayMonthYearResult' => $fixRlt);
			$done=true;
			if ($fixRlt) {
				$this->queue_result->appendResult($token, ['request_id'=>_REQUEST_ID, 'result'=> $result ], $done, false);
				$this->utils->debug_Log("remote_rebuild_games_total  token:" . $token . " result: ", $result);

			} else {
				$this->queue_result->appendResult($token, ['request_id'=>_REQUEST_ID, 'result'=> $result ], $done, true);
				$this->utils->error_Log("remote_rebuild_games_total  token:" . $token . " result: ", $result);
			}
		}

		if($update_player){
			// -- OGP-9124
			$this->utils->debug_log('START RUNNING: updatePlayersTotalBettingAmount');
			$updatePlayersTotalBettingAmount = $this->player_model->updatePlayersTotalBettingAmount();

			$this->utils->debug_log('RESULT OF updatePlayersTotalBettingAmount: Total count of players updated = '.$updatePlayersTotalBettingAmount);

			$this->utils->debug_log('END RUNNING: updatePlayersTotalBettingAmount');
		}

		// $this->returnText($msg);
	}

	public function rebuild_totals_by_player($fromStr = null, $toStr = null, $playerName=null) {

		$rebuild_hour = true;
		$rebuild_minute = true;
		$token = null;
		$rebuild_month = true;
		$rebuild_year = true;
		$update_player = true;

		$lock_rebuild_reports_range= $this->utils->getConfig('lock_rebuild_reports_range');
		$from_day = $this->utils->formatDateForMysql(new DateTime($fromStr));
		$to_day = $this->utils->formatDateForMysql(new DateTime($toStr));
		if(!empty($lock_rebuild_reports_range)){
			if($this->utils->isRebuildReportDateLocked($lock_rebuild_reports_range,$from_day,$to_day,$rlt)){
				$this->utils->error_log(sprintf(lang("Regenarate All Report has lock - should not be equal or older than  %s  "),$rlt['cutoff_day']));
				return;
			}
		}

		$rebuild_hour=$rebuild_hour=='true';
		$rebuild_minute=$rebuild_minute=='true';
		$rebuild_month=$rebuild_month=='true';
		$rebuild_year=$rebuild_year=='true';
		$update_player=$update_player=='true';
		if($token=='_null' || $token=='null'){
			$token=null;
		}

		$playerId = null;
		if($playerName){
			$playerId = $this->player_model->getPlayerIdByUsername($playerName);
		}
		$this->utils->debug_log('Rebuild for player: '. $playerId . ' ' . $playerName);

		$default_sync_game_logs_max_time_second = $this->config->item('default_sync_game_logs_max_time_second');

		set_time_limit($default_sync_game_logs_max_time_second);

		$this->utils->debug_log('fromStr', $fromStr, 'toStr', $toStr, 'rebuild_hour', $rebuild_hour, 'rebuild_minute', $rebuild_minute,
			'token', $token, 'rebuild_month', $rebuild_month, 'rebuild_year', $rebuild_year, 'update_player', $update_player);

		$this->load->model(array('game_logs', 'total_player_game_minute', 'total_player_game_hour', 'total_player_game_day',
			'total_player_game_month', 'total_player_game_year', 'player_model'));
		if (empty($fromStr) || empty($toStr) || $fromStr == "null" || $toStr == "null" || $fromStr == "_null" || $toStr == "_null") {
			// $from = new DateTime($this->game_logs->getFirstDatetime());
			//last 7 days
			$from = $this->utils->getNowDateTime();
			$from = $from->modify('-7 days');
			$to = $this->utils->getNowDateTime();
			//exclude current hour
			$to = $to->modify('-1 day');
			$to=new DateTime($to->format('Y-m-d').' 23:59:59');
		} else {
			$from = new DateTime($fromStr);
			$to = new DateTime($toStr);
		}
		$msg = $this->utils->debug_log('from', $from, 'to', $to);
		// $this->returnText($msg);

		$fixDays = array();
		$fixMonths = array();
		$fixYears = array();

		list($start, $end, $datehour) = $this->utils->getDateHourSet($from);
		while (!$this->utils->gtEndHour($from, $to)) {

			$fixDays[] = substr($datehour, 0, 8);
			$fixMonths[] = substr($datehour, 0, 6);
			$fixYears[] = substr($datehour, 0, 4);
			$fixResultMinute = false;
			$fixResultHour = false;



			if($rebuild_minute){
				//make half hour
				$halfHour=new DateTime($start);
				$halfHour->modify('+30 minutes');

				if($halfHour->format('Y-m-d H:i:s')<=$end){
					$this->total_player_game_minute->startTrans();
					//rebuild minute first
					$count = $this->total_player_game_minute->sync(new DateTime($start), $halfHour, $playerId);
					$this->utils->debug_log('start', $start, 'halfHour', $halfHour, 'datehour', $datehour, 'fix total_player_game_minute', $count);
					$fixResultMinute = $this->total_player_game_minute->endTransWithSucc();

					$this->utils->debug_log('commit minute half hour', $fixResultMinute);

					$this->total_player_game_minute->startTrans();
					//rebuild minute first
					$count = $this->total_player_game_minute->sync($halfHour, new DateTime($end), $playerId);
					$this->utils->debug_log('halfHour', $halfHour, 'end', $end, 'datehour', $datehour, 'fix total_player_game_minute', $count);
					$fixResultMinute = $this->total_player_game_minute->endTransWithSucc();

					$this->utils->debug_log('commit minute', $fixResultMinute);
				}else{
					$this->total_player_game_minute->startTrans();
					//rebuild minute first
					$count = $this->total_player_game_minute->sync(new DateTime($start), new DateTime($end), $playerId);
					$this->utils->debug_log('start', $start, 'end', $end, 'datehour', $datehour, 'fix total_player_game_minute', $count);
					$fixResultMinute = $this->total_player_game_minute->endTransWithSucc();

					$this->utils->debug_log('commit minute', $fixResultMinute);
				}
			}


			if ($rebuild_hour) {
				$this->total_player_game_hour->startTrans();

				$count = $this->total_player_game_hour->sync(new DateTime($start), new DateTime($end), $playerId);
				$msg = $this->utils->debug_log('start', $start, 'end', $end, 'datehour', $datehour, 'fix total_player_game_hour', $count);
				// $this->returnText($msg);

				$fixResultHour = $this->total_player_game_hour->endTransWithSucc();

				$msg = $this->utils->debug_log('commit hour', $fixResultHour);
				// $this->returnText($msg);
			}

			list($start, $end, $datehour) = $this->utils->getNextHour($from);
			$from = new DateTime($start);
		}
		$fixDays = array_unique($fixDays);
		$fixMonths = array_unique($fixMonths);
		$fixYears = array_unique($fixYears);

		$msg = $this->utils->debug_log('days', $fixDays, 'months', $fixMonths, 'years', $fixYears);
		// $this->returnText($msg);
		if(!$rebuild_month){
			$fixMonths=null;
		}
		if(!$rebuild_year){
			$fixYears=null;
		}
		$fixRlt = $this->fixDayMonthYear($fixDays, $fixMonths, $fixYears, $playerId);
		$msg = $this->utils->debug_log('fixDayMonthYear result', $fixRlt);

		if(!empty($token)){
			$result = array('minute'=>$fixResultMinute,'hour'=>$fixResultHour, 'days'=> $fixDays, 'months'=> $fixMonths, 'years'=> $fixYears, 'fixDayMonthYearResult' => $fixRlt);
			$done=true;
			if ($fixRlt) {
				$this->queue_result->appendResult($token, ['request_id'=>_REQUEST_ID, 'result'=> $result ], $done, false);
				$this->utils->debug_Log("remote_rebuild_games_total  token:" . $token . " result: ", $result);

			} else {
				$this->queue_result->appendResult($token, ['request_id'=>_REQUEST_ID, 'result'=> $result ], $done, true);
				$this->utils->error_Log("remote_rebuild_games_total  token:" . $token . " result: ", $result);
			}
		}

		if($update_player){
			// -- OGP-9124
			$this->utils->debug_log('START RUNNING: updatePlayersTotalBettingAmount');
			$updatePlayersTotalBettingAmount = $this->player_model->updatePlayersTotalBettingAmount($playerId);

			$this->utils->debug_log('RESULT OF updatePlayersTotalBettingAmount: Total count of players updated = '.$updatePlayersTotalBettingAmount);

			$this->utils->debug_log('END RUNNING: updatePlayersTotalBettingAmount');
		}

		// $this->returnText($msg);
	}

	/**
	 * overview : Fix day month and year
	 * @param $fixDays
	 * @param $fixMonths
	 * @param $fixYears
	 * @return mixed
	 */
	private function fixDayMonthYear($fixDays, $fixMonths, $fixYears, $playerId = null, $gamePlatformId=null) {

		$this->load->model(array('total_player_game_hour', 'total_player_game_day',
			'total_player_game_month', 'total_player_game_year'));

		$result=true;

		if (!empty($fixDays)) {
			$this->total_player_game_day->startTrans();
			// $fixDays = array_unique($fixDays);
			foreach ($fixDays as $day) {
				list($start, $end) = $this->utils->convertDayToStartEnd($day);
				$this->utils->debug_log('fix day', $day, 'start', $start, 'end', $end);
				$this->total_player_game_day->sync(new DateTime($start), new DateTime($end), $playerId, $gamePlatformId);
			}
			$result=$this->total_player_game_day->endTransWithSucc();
		}
		if (!empty($fixMonths)) {
			$this->total_player_game_month->startTrans();
			// $fixMonths = array_unique($fixMonths);
			foreach ($fixMonths as $month) {
				list($start, $end) = $this->utils->convertMonthToStartEnd($month);
				$this->utils->debug_log('fix month', $month, 'start', $start, 'end', $end);
				$this->total_player_game_month->sync(new DateTime($start), new DateTime($end), $playerId, $gamePlatformId);
			}
			$result=$this->total_player_game_month->endTransWithSucc();
		}
		if (!empty($fixYears)) {
			$this->total_player_game_year->startTrans();
			// $fixYears = array_unique($fixYears);
			foreach ($fixYears as $year) {
				list($start, $end) = $this->utils->convertYearToStartEnd($year);
				$this->utils->debug_log('fix year', $year, 'start', $start, 'end', $end);
				$this->total_player_game_year->sync(new DateTime($start), new DateTime($end), $playerId, $gamePlatformId);
			}
			$result=$this->total_player_game_year->endTransWithSucc();
		}

		return $result;
	}

	/**
	 * overview : sync total stats
	 *
	 * @param date $dateTimeFromStr
	 * @param date $dateTimeToStr
	 * @return mixed
	 */
	public function syncTotalStats($dateTimeFromStr = null, $dateTimeToStr = null) {
		$default_sync_game_logs_max_time_second = $this->config->item('default_sync_game_logs_max_time_second');

		set_time_limit($default_sync_game_logs_max_time_second);

		$dateTimeFrom = new \DateTime($dateTimeFromStr);
		$dateTimeTo = new \DateTime($dateTimeToStr);
		list($todayFrom, $todayTo) = $this->utils->getTodayDateTimeRange();
		//set default datetime from to
		if (empty($dateTimeFromStr)) {
			$dateTimeFrom = $todayFrom;
		}
		if (empty($dateTimeToStr)) {
			$dateTimeTo = $todayTo;
		}

		// $this->utils->debug_log('dateTimeFrom', $dateTimeFrom, 'dateTimeTo', $dateTimeTo,
		// 	'dateTimeFromStr', $dateTimeFromStr, 'dateTimeToStr', $dateTimeToStr);

		$abstractApi = $this->utils->loadAnyGameApiObject();

		$token = random_string('unique');
		$abstractApi->saveSyncInfoByToken($token, $dateTimeFrom, $dateTimeTo);

		$rlt = $abstractApi->syncTotalStats($token);

		$abstractApi->clearSyncInfo($token);

		return $rlt;
	}

	public function rebuild_player_daily_total_balance($date) {
		if (empty($date)) {
			return false;
		}

		$this->load->model('daily_balance');
		$this->daily_balance->generateDailyTotal($date, true);
	}

	public function batch_update_player_betting_amount(){
		$this->load->model(['player_model']);
		$this->utils->debug_log('START RUNNING: updatePlayersTotalBettingAmount');
		$updatePlayersTotalBettingAmount = $this->player_model->updatePlayersTotalBettingAmount();
		$this->utils->debug_log('RESULT OF updatePlayersTotalBettingAmount: Total count of players updated = '.$updatePlayersTotalBettingAmount);

		$this->utils->debug_log('END RUNNING: updatePlayersTotalBettingAmount');
	}

	public function rebuild_seamless_balance_history($dateTimeFromStr = null, $dateTimeToStr = null, $gamePlatformId = null, $minutes = 10, $includeGameTransaction = true, $includeTransaction = true, $queue_token = '_null') {

		$this->CI->load->model('external_system','original_game_logs_model');
		$offset = (int)$this->utils->getConfig('rebuild_seamless_balance_history_offset_minutes');
		$ignoreApis = $this->utils->getConfig('ignore_rebuild_seamless_balance_history');
		if(!is_array($ignoreApis)){
			$ignoreApis = (array)$ignoreApis;
		}

		$this->utils->info_log('START rebuild_seamless_balance_history', 'offset', $offset, 'ignoreApis', $ignoreApis);

		if (empty($dateTimeFromStr)) {
			$dateTimeFromObj = new DateTime();
			$dateTimeFromObj->modify("-$offset minutes");
			$dateTimeFromStr = $dateTimeFromObj->format('Y-m-d H:i:00');
		}else{
			$dateTimeFromObj = new DateTime($dateTimeFromStr);
		}

		if(empty($dateTimeToStr)){
			$dateTimeToObj = new DateTime();
			$dateTimeToStr = $dateTimeToObj->format('Y-m-d H:i:59');
		}else{
			$dateTimeToObj = new DateTime($dateTimeToStr);
		}

		//get all active seamless game api
		if(!empty($gamePlatformId) && $gamePlatformId!='_null' && $gamePlatformId!=null){
			$activeGames = [$gamePlatformId];
		}else{
			$activeGames = $this->external_system->getActivedGameApiList();
		}

		if($queue_token=='_null'){
			$queue_token=null;
		}

		$success = true;
		$done = false;
		$is_error = false;
		$rlt = ['game_platform_ids'=>$activeGames, 'ignore_apis'=>$ignoreApis];
		$this->queue_result->appendResult($queue_token, [
			'request_id'=>_REQUEST_ID, 'func'=>'rebuild_seamless_balance_history', 'success'=> $success,
			'result'=>$rlt], $done, $is_error);

		$dateRanges = $this->utils->generateDateRangeSplitMonth($dateTimeFromObj->format("Y-m-d H:i:s"), $dateTimeToObj->format("Y-m-d H:i:s"), $minutes);
		$this->utils->info_log('initSeamlessBalanceMonthlyTableByDate', 'dateRanges', $dateRanges);
		foreach($dateRanges as $dateRange){

			$dateTimeFromObj = new DateTime($dateRange['from']);
			$dateTimeToObj = new DateTime($dateRange['to']);

			$dateStr=$dateTimeFromObj->format('Y-m-d H:i:00');
			$this->utils->getSeamlessBalanceHistoryTable($dateStr);
			$this->utils->info_log('initSeamlessBalanceMonthlyTableByDate', $dateStr);

			//for each api sync
			if($includeGameTransaction){
				foreach($activeGames as $gamePlatformId){

					$success = true;
					$done = false;
					$is_error = false;
					$rlt = ['game_platform_id'=>$gamePlatformId, 'transactions'=>false, 'from'=>$dateTimeFromObj->format('Y-m-d H:i:s'), 'to'=>$dateTimeToObj->format('Y-m-d H:i:s')];
					$this->queue_result->appendResult($queue_token, [
						'request_id'=>_REQUEST_ID, 'func'=>'rebuild_seamless_balance_history', 'success'=> $success,
						'result'=>$rlt], $done, $is_error);

					if(in_array($gamePlatformId, $ignoreApis)){
						$this->utils->info_log('ignore rebuild_seamless_balance_history gamePlatformId', $gamePlatformId);
						continue;
					}
					$this->utils->info_log('START initSeamlessBalanceMonthlyTableByDate',
					'gamePlatformId', $gamePlatformId,
					'dateFrom', $dateTimeFromObj->format('Y-m-d H:i:s'),
					'dateTo', $dateTimeToObj->format('Y-m-d H:i:s'));

					$api = $this->utils->loadExternalSystemLibObject($gamePlatformId);
					if($api && $api->isSeamLessGame()){
						//get transactions by date range, and save to balance history
						$api_token = random_string('unique');
						$api->saveSyncInfoByToken($api_token, $dateTimeFromObj, $dateTimeToObj, null, null, null, null);
						$result = $api->syncBalanceHistory($api_token);
						$this->utils->info_log('rebuild_seamless_balance_history gamePlatformId', $gamePlatformId, 'result', $result);

					}else{
						$this->utils->info_log('rebuild_seamless_balance_history SKIPPED gamePlatformId', $gamePlatformId);
					}
				}
			}

			//add process to include balance history
			//select * from balanceadjustmenthistory order by balanceAdjustmentHistoryId desc limit 10

			if($includeTransaction){
				$success = true;
				$done = false;
				$is_error = false;
				$rlt = ['game_platform_id'=>null, 'transactions'=>true, 'from'=>$dateTimeFromObj->format('Y-m-d H:i:s'), 'to'=>$dateTimeToObj->format('Y-m-d H:i:s')];
				$this->queue_result->appendResult($queue_token, [
					'request_id'=>_REQUEST_ID, 'func'=>'rebuild_seamless_balance_history', 'success'=> $success,
					'result'=>$rlt], $done, $is_error);


				$this->db->select(array('t.to_id player_id',
				't.created_at created_at',
				't.created_at transaction_date',
				't.amount',
				't.before_balance',
				't.after_balance',
				't.request_secure_id round_no',
				't.transaction_type transaction_type',
				't.id external_uniqueid',
				't.changed_balance changed_balance',
				't.note note')
				);
				$this->db->from('transactions t');
				$this->db->where('t.created_at >=', $dateTimeFromObj->format('Y-m-d H:i:s'));
				$this->db->where('t.created_at <=', $dateTimeToObj->format('Y-m-d H:i:s'));
				$this->db->where('t.status', Transactions::APPROVED);
                $this->db->where('t.transaction_type <>', Transactions::WITHDRAWAL);//exclude withdrawal
				$this->db->order_by('t.created_at', 'ASC');
				$query = $this->db->get();
				$transactions = $query->result_array();

				foreach($transactions as $key => &$transaction){
					if($transaction['transaction_type']==Transactions::DEPOSIT){
						$changeBalance = json_decode($transaction['changed_balance'], true);
						if(isset($changeBalance['before']) && isset($changeBalance['before']['main_wallet'])){
							$transaction['before_balance'] = $changeBalance['before']['main_wallet'];
						}
						if(isset($changeBalance['after']) && isset($changeBalance['after']['main_wallet'])){
							$transaction['after_balance'] = $changeBalance['after']['main_wallet'];
						}
					}
					unset($transaction['changed_balance']);

					$extra = ['trans_type'=>'','note'=>$transaction['note']];
					$transactions[$key]['extra_info']=json_encode($extra);
					$transactions[$key]['game_platform_id']=0;
					$transactions[$key]['external_uniqueid']='T'.$transaction['player_id'].'-'.$transaction['external_uniqueid'];
					unset($transactions[$key]['note']);
					unset($transactions[$key]['created_at']);
				}

				$groupedTransactions = $this->utils->groupTransactionsByDate($transactions);
				unset($transactions);
				foreach($groupedTransactions as $dateStr => $transactions){
					$tableDate = new DateTime($dateStr);
					$tableName = $this->utils->getSeamlessBalanceHistoryTable($tableDate->format('Y-m-d 00:00:00'));
					$cnt = 0;
					$success=$this->CI->original_game_logs_model->runBatchInsertWithLimit($this->CI->db, $tableName, $transactions, 100, $cnt, true);
					$this->utils->info_log('initSeamlessBalanceMonthlyTableByDate', 'transactions', $cnt, 'tableName', $tableName);
				}

				unset($groupedTransactions);



                //get withdrawal transactions from walletaccount
                $this->db->select(array('wa.playerId player_id',
				'wa.dwDateTime created_at',
				'wa.dwDateTime transaction_date',
				'wa.amount',
				'wa.before_balance',
				'wa.after_balance',
				'wa.transactionCode round_no',
				'wa.transactionType transaction_type',
				'wa.dwStatus transaction_status',
				'wa.transactionCode external_uniqueid')
				);
				$this->db->from('walletaccount wa');
				$this->db->where('wa.dwDateTime >=', $dateTimeFromObj->format('Y-m-d H:i:s'));
				$this->db->where('wa.dwDateTime <=', $dateTimeToObj->format('Y-m-d H:i:s'));
				$this->db->order_by('wa.dwDateTime', 'ASC');
				$query = $this->db->get();
				$transactions = $query->result_array();

				foreach($transactions as $key => &$transaction){
					$extra = ['trans_type'=>$transaction['transaction_type'],'trans_status'=>$transaction['transaction_status']];
					if($transaction['transaction_type']=='withdrawal'){
						$transactions[$key]['transaction_type']=Transactions::GAME_API_SUBTRACT_SEAMLESS_BALANCE;
					}
					$transactions[$key]['extra_info']=json_encode($extra);
					$transactions[$key]['game_platform_id']=0;
					$transactions[$key]['external_uniqueid']=/*$transaction['player_id'].'-'.*/$transaction['external_uniqueid'];
					unset($transactions[$key]['transaction_status']);
					unset($transactions[$key]['created_at']);
				}

				$groupedTransactions = $this->utils->groupTransactionsByDate($transactions);
				unset($transactions);
				foreach($groupedTransactions as $dateStr => $transactions){
					$tableDate = new DateTime($dateStr);
					$tableName = $this->utils->getSeamlessBalanceHistoryTable($tableDate->format('Y-m-d 00:00:00'));
					$cnt = 0;
					$success=$this->CI->original_game_logs_model->runBatchInsertWithLimit($this->CI->db, $tableName, $transactions, 100, $cnt, true);
					$this->utils->info_log('initSeamlessBalanceMonthlyTableByDate', 'transactions', $cnt, 'tableName', $tableName);
				}

				unset($groupedTransactions);
			}


			$this->utils->info_log('END initSeamlessBalanceMonthlyTableByDate',
			'gamePlatformId', $gamePlatformId,
			'dateFrom', $dateTimeFromObj->format('Y-m-d H:i:s'),
			'dateTo', $dateTimeToObj->format('Y-m-d H:i:s'));
		}

		if(!empty($queue_token)){
			$rlt = [];
			$success = true;
			$done=true;
			$is_error=!$success;
			if($success){
				$this->queue_result->appendResult($queue_token, [
					'request_id'=>_REQUEST_ID, 'func'=>'rebuild_seamless_balance_history', 'success'=> $success,
					'result'=>$rlt], $done, $is_error);
			}else{
				$error_message = 'Error rebuilding seamless balance history';
				$this->queue_result->appendResult($queue_token, [
					'request_id'=>_REQUEST_ID, 'func'=>'rebuild_seamless_balance_history', 'success'=> $success, 'error_message'=>$error_message,
					'result'=>$rlt], $done, $is_error);
			}
		}


		return;
	}

	/**
	 * rebuild_totals_from_batch_file
	 * @param  string $batchFile json file
	 * @return
	 */
	public function rebuild_totals_from_batch_file($batchFile) {
		/*
		json format:
[
  {
    "from": "2021-06-01 00:00:00", "to": "2021-06-01 00:59:59",
    "rebuld_minute": true, "rebuild_hour": true,
    "rebuild_day": false, "rebuild_month": false, "rebuild_year": false,
    "game_platform_id": null, "player_username": null
  },
  {
    "from": "2021-06-03 00:00:00", "to": "2021-06-03 00:59:59",
    "rebuld_minute": true, "rebuild_hour": true,
    "rebuild_day": false, "rebuild_month": false, "rebuild_year": false,
    "game_platform_id": null, "player_username": null
  }
]
		 */
		if(empty($batchFile)){
			$this->utils->error_log('batch file is required');
			return false;
		}

		$batchFilePath='/home/vagrant/Code/'.$batchFile;
		if(!file_exists($batchFilePath)){
			$this->utils->error_log('batch file does not exist', $batchFilePath);
			return false;
		}

		$batchJson=json_decode(file_get_contents($batchFilePath), true);
		if(empty($batchJson) || !is_array($batchJson)){
			$this->utils->error_log('wrong batch file', $batchFilePath, file_get_contents($batchFilePath));
			return false;
		}
		$this->utils->info_log('batchJson', $batchJson);
		foreach ($batchJson as $configJson) {
			$this->rebuild_totals_from_config($configJson);
		}
	}

	private function rebuild_totals_from_config($config=null) {
		$fromStr=@$config['from'];
		$toStr=@$config['to'];

		$lock_rebuild_reports_range= $this->utils->getConfig('lock_rebuild_reports_range');
		$from_day = $this->utils->formatDateForMysql(new DateTime($fromStr));
		$to_day = $this->utils->formatDateForMysql(new DateTime($toStr));
		if(!empty($lock_rebuild_reports_range)){
			if($this->utils->isRebuildReportDateLocked($lock_rebuild_reports_range,$from_day,$to_day,$rlt)){
				$this->utils->error_log(sprintf(lang("Regenarate All Report has lock - should not be equal or older than  %s  "),$rlt['cutoff_day']));
				return false;
			}
		}
		$this->utils->debug_log('rebuild_totals_from_config', $config);
		//validate field
		if(!array_key_exists('from', $config)){
			$this->utils->error_log('from field is required', $config);
			return false;
		}
		if(!array_key_exists('to', $config)){
			$this->utils->error_log('to field is required', $config);
			return false;
		}
		if(!array_key_exists('rebuild_minute', $config)
			&& !array_key_exists('rebuild_hour', $config)
			&& !array_key_exists('rebuild_day', $config)
			&& !array_key_exists('rebuild_month', $config)
			&& !array_key_exists('rebuild_year', $config)){
			$this->utils->error_log('require rebuild_minute,rebuild_hour,rebuild_day,rebuild_month,rebuild_year at least one field', $config);
			return false;
		}

		$this->load->model(array('game_logs', 'total_player_game_minute', 'total_player_game_hour', 'total_player_game_day',
			'total_player_game_month', 'total_player_game_year', 'player_model', 'points_transaction_report_hour'));

		$rebuild_hour=@$config['rebuild_hour'];
		$rebuild_minute=@$config['rebuild_minute'];
		$rebuild_day=@$config['rebuild_day'];
		$rebuild_month=@$config['rebuild_month'];
		$rebuild_year=@$config['rebuild_year'];
		$update_player=@$config['update_player'];
		$player_username=@$config['player_username'];
		$game_platform_id=@$config['game_platform_id'];
		$token=@$config['token'];
		if($token=='_null' || $token=='null'){
			$token=null;
		}
		$playerId = null;
		if(!empty($player_username)){
			$playerId = $this->player_model->getPlayerIdByUsername($player_username);
		}

		$default_sync_game_logs_max_time_second = $this->config->item('default_sync_game_logs_max_time_second');

		set_time_limit($default_sync_game_logs_max_time_second);

		$this->utils->debug_log('fromStr', $fromStr, 'toStr', $toStr, 'rebuild_hour', $rebuild_hour, 'rebuild_minute', $rebuild_minute,
			'token', $token, 'rebuild_month', $rebuild_month, 'rebuild_year', $rebuild_year, 'update_player', $update_player);

		$fixResultMinute = false;
		$fixResultHour = false;
		$fixResultDay=false;
		$fixResultMonth=false;
		$fixResultYear=false;
		$fixDays = array();
		$fixMonths = array();
		$fixYears = array();

		try{

		$from = new DateTime($fromStr);
		$to = new DateTime($toStr);
		$this->utils->debug_log('from', $from, 'to', $to);

		list($start, $end, $datehour) = $this->utils->getDateHourSet($from);
		while (!$this->utils->gtEndHour($from, $to)) {

			$fixDays[] = substr($datehour, 0, 8);
			$fixMonths[] = substr($datehour, 0, 6);
			$fixYears[] = substr($datehour, 0, 4);

			if($rebuild_minute){
				//make half hour
				$halfHour=new DateTime($start);
				$halfHour->modify('+30 minutes');

				if($halfHour->format('Y-m-d H:i:s')<=$end){
					$this->total_player_game_minute->startTrans();
					//rebuild minute first
					$count = $this->total_player_game_minute->sync(new DateTime($start), $halfHour, $playerId, $game_platform_id);
					$this->utils->debug_log('start', $start, 'halfHour', $halfHour, 'datehour', $datehour, 'fix total_player_game_minute', $count);
					$fixResultMinute = $this->total_player_game_minute->endTransWithSucc();

					$this->utils->debug_log('commit minute half hour', $fixResultMinute);
					if(!$fixResultMinute){
						throw new Exception('sync minute failed, '.$start.' to '.$halfHour);
					}

					$this->total_player_game_minute->startTrans();
					//rebuild minute first
					$count = $this->total_player_game_minute->sync($halfHour, new DateTime($end), $playerId, $game_platform_id);
					$this->utils->debug_log('halfHour', $halfHour, 'end', $end, 'datehour', $datehour, 'fix total_player_game_minute', $count);
					$fixResultMinute = $this->total_player_game_minute->endTransWithSucc();

					$this->utils->debug_log('commit minute', $fixResultMinute);
					if(!$fixResultMinute){
						throw new Exception('sync minute failed, '.$halfHour.' to '.$end);
					}
				}else{
					$this->total_player_game_minute->startTrans();
					//rebuild minute first
					$count = $this->total_player_game_minute->sync(new DateTime($start), new DateTime($end), $playerId, $game_platform_id);
					$this->utils->debug_log('start', $start, 'end', $end, 'datehour', $datehour, 'fix total_player_game_minute', $count);
					$fixResultMinute = $this->total_player_game_minute->endTransWithSucc();

					$this->utils->debug_log('commit minute', $fixResultMinute);
					if(!$fixResultMinute){
						throw new Exception('sync minute failed, '.$start.' to '.$end);
					}
				}
			}

			if ($rebuild_hour) {
				$this->total_player_game_hour->startTrans();

				$count = $this->total_player_game_hour->sync(new DateTime($start), new DateTime($end), $playerId, $game_platform_id);
				$this->utils->debug_log('start', $start, 'end', $end, 'datehour', $datehour, 'fix total_player_game_hour', $count);

				$fixResultHour = $this->total_player_game_hour->endTransWithSucc();

				$this->utils->debug_log('commit hour', $fixResultHour);
				if(!$fixResultHour){
					throw new Exception('sync hour failed, '.$start.' to '.$end);
				}

				$this->load->model(array('game_logs'));
				$this->points_transaction_report_hour->startTrans();
				$pointsTransactionTeportHourData = $this->points_transaction_report_hour->sync(new DateTime($start), new DateTime($end), $playerId, 'true');
				$this->utils->debug_log('start', $start, 'end', $end);
				$fixPointsTransactionResultHour = $this->points_transaction_report_hour->endTransWithSucc();
				if(!$fixPointsTransactionResultHour){
					throw new Exception('sync points failed, '.$start.' to '.$end);
				}
			}

			list($start, $end, $datehour) = $this->utils->getNextHour($from);
			$from = new DateTime($start);
		}
		$fixDays = array_unique($fixDays);
		$fixMonths = array_unique($fixMonths);
		$fixYears = array_unique($fixYears);

		$this->utils->debug_log('days', $fixDays, 'months', $fixMonths, 'years', $fixYears);
		if(!$rebuild_day){
			$fixDays=null;
		}
		if(!$rebuild_month){
			$fixMonths=null;
		}
		if(!$rebuild_year){
			$fixYears=null;
		}

		if (!empty($fixDays)) {
			$this->total_player_game_day->startTrans();
			// $fixDays = array_unique($fixDays);
			foreach ($fixDays as $day) {
				list($start, $end) = $this->utils->convertDayToStartEnd($day);
				$this->utils->debug_log('fix day', $day, 'start', $start, 'end', $end);
				$this->total_player_game_day->sync(new DateTime($start), new DateTime($end), $playerId, $gamePlatformId);
			}
			$fixResultDay=$this->total_player_game_day->endTransWithSucc();
			if(!$fixResultDay){
				throw new Exception('sync day failed, '.$start.' to '.$end);
			}
		}else{
			$this->utils->info_log('ignore sync day');
		}
		if (!empty($fixMonths)) {
			$this->total_player_game_month->startTrans();
			// $fixMonths = array_unique($fixMonths);
			foreach ($fixMonths as $month) {
				list($start, $end) = $this->utils->convertMonthToStartEnd($month);
				$this->utils->debug_log('fix month', $month, 'start', $start, 'end', $end);
				$this->total_player_game_month->sync(new DateTime($start), new DateTime($end), $playerId, $gamePlatformId);
			}
			$fixResultMonth=$this->total_player_game_month->endTransWithSucc();
			if(!$fixResultMonth){
				throw new Exception('sync month failed, '.$start.' to '.$end);
			}
		}else{
			$this->utils->info_log('ignore sync month');
		}
		if (!empty($fixYears)) {
			$this->total_player_game_year->startTrans();
			// $fixYears = array_unique($fixYears);
			foreach ($fixYears as $year) {
				list($start, $end) = $this->utils->convertYearToStartEnd($year);
				$this->utils->debug_log('fix year', $year, 'start', $start, 'end', $end);
				$this->total_player_game_year->sync(new DateTime($start), new DateTime($end), $playerId, $gamePlatformId);
			}
			$fixResultYear=$this->total_player_game_year->endTransWithSucc();
			if(!$fixResultYear){
				throw new Exception('sync year failed, '.$start.' to '.$end);
			}
		}else{
			$this->utils->info_log('ignore sync year');
		}

		if($update_player){
			// -- OGP-9124
			$this->utils->debug_log('START RUNNING: updatePlayersTotalBettingAmount');
			$this->dbtransOnly(function(){
				$updatePlayersTotalBettingAmount = $this->player_model->updatePlayersTotalBettingAmount();
				$this->utils->debug_log('RESULT OF updatePlayersTotalBettingAmount: Total count of players updated = '.$updatePlayersTotalBettingAmount);
				$this->utils->debug_log('END RUNNING: updatePlayersTotalBettingAmount');
				return true;
			});
		}else{
			$this->utils->info_log('ignore update player');
		}

		if(!empty($token)){
			$result = array('minute'=>$fixResultMinute,'hour'=>$fixResultHour, 'days'=> $fixDays, 'months'=> $fixMonths, 'years'=> $fixYears,
				'fixResultDay' => $fixResultDay, 'fixResultMonth'=>$fixResultMonth, 'fixResultYear'=>$fixResultYear);
			$done=true;
			$this->queue_result->appendResult($token, ['request_id'=>_REQUEST_ID, 'result'=> $result ], $done, false);
			$this->utils->debug_log("remote_rebuild_games_total  token:" . $token . " result: ", $result);
		}

		}catch(Exception $e){
			$this->utils->error_log('', $e);
			if(!empty($token)){
				$result = array('minute'=>$fixResultMinute,'hour'=>$fixResultHour, 'days'=> $fixDays, 'months'=> $fixMonths, 'years'=> $fixYears,
					'fixResultDay' => $fixResultDay, 'fixResultMonth'=>$fixResultMonth, 'fixResultYear'=>$fixResultYear);
				$done=true;
				$this->queue_result->appendResult($token, ['request_id'=>_REQUEST_ID, 'result'=> $result ], $done, true);
				$this->utils->error_log("remote_rebuild_games_total  token:" . $token . " result: ", $result);
			}

			return false;
		}
	}

	public function rebuild_game_biling_report($yearMonth = "_null", $gamePlatformId = null, $playerId = null){
		if($yearMonth == "_null"){
			$yearMonth = null;
		}

		$this->load->model(array('total_player_game_month', 'external_system'));
		$activeGames = $this->external_system->getActivedGameApiForBillingReport($gamePlatformId);
		if(!empty($activeGames)){
			foreach ($activeGames as $key => $game) {
				$dateFrom = (new DateTime('first day of last month'));
				$dateTo = (new DateTime('last day of last month'));
				if(!empty($yearMonth)){
					$dateFrom = (new DateTime($yearMonth))->modify('first day of this month');
					$dateTo = (new DateTime($yearMonth))->modify('last day of this month');
				}

				$billingStartDate = isset($game['start_of_the_month']) ? intval($game['start_of_the_month']) : 1;
				$dateFrom = $dateFrom->format("Y-m-{$billingStartDate} 00:00:00");
				$dateTo = $dateTo->format('Y-m-d 23:59:59');

				$timezone = isset($game['timezone']) ? $game['timezone'] : null;
				// $timezone = "GMT-4";
				if(!empty($timezone)){ #timezone format example:  GMT+8 or GMT-4
					$timezoneDateFrom = new DateTime($dateFrom);
					$timezoneDateTo = new DateTime($dateTo);
					$timezoneDateFrom->setTimezone(new DateTimeZone($timezone));
					$timezoneDateTo->setTimezone(new DateTimeZone($timezone));
					$dateFrom = $timezoneDateFrom->format('Y-m-d H:00:00');
					$dateTo = $timezoneDateTo->format('Y-m-d H:59:59');
				}

				$dataParams = array(
					"dateFrom" => $dateFrom,
					"dateTo" => $dateTo,
					"gamePlatformId" => $game['id']
				);

				$this->utils->debug_log('rebuild_game_biling_report dataParams', $dataParams);

				$this->total_player_game_month->startTrans();
				$this->total_player_game_month->syncGameBillingReport(new DateTime($dataParams['dateFrom']), new DateTime($dataParams['dateTo']), $dataParams['gamePlatformId'], $playerId);
				$result=$this->total_player_game_month->endTransWithSucc();
				$this->utils->debug_log('rebuild_game_biling_report', $result);
			}
		}
	}
}

///END OF FILE/////////////////
