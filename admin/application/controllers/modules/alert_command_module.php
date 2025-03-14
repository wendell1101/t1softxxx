<?php

trait alert_command_module{

	/**
	 * alert_suspicious_player_login
	 * get last activity from http_request < 7days
	 * compare ip area
	 *
	 * @param  string $fromStr
	 * @param  string $toStr
	 * @return boolean
	 */
	public function alert_suspicious_player_login($fromStr=null, $toStr=null){
		$this->utils->debug_log('get parameters', $fromStr, $toStr);
		$alert_suspicious_player_login_minutes=$this->utils->getConfig('alert_suspicious_player_login_minutes');
		if(empty($toStr)){
			$toStr='-1 minutes';
		}
		if(empty($fromStr)){
			$fromStr='-'.$alert_suspicious_player_login_minutes.' minutes';
		}
		$to=new DateTime($toStr);
		$from=new DateTime($fromStr);
		$from=new DateTime($from->format('Y-m-d H:i').':00');
		$to=new DateTime($to->format('Y-m-d H:i').':59');
		$this->load->model(['http_request', 'alert_message_model']);
		$this->utils->debug_log('search by', $from, $to);
		$countAll=0;
		$list=$this->http_request->searchSuspiciousPlayerLogin($from, $to, $countAll);
		if(!empty($list)){
			$succ=$this->alert_message_model->sendAllToMattermost($list);
			if(!$succ){
				$this->utils->error_log('send message failed', $list);
			}
		}

		$this->utils->info_log('search and get suspicious', count($list), 'countAll', $countAll);
	}


	/**
	 * The CMDs,
	 *
	 * - To do the detection now,
	 * sudo ./admin/shell/command.sh monitorManyPlayerLoginViaSameIp
	 *
	 * - To simulate the detection at a certain moment,
	 * sudo ./admin/shell/command.sh monitorManyPlayerLoginViaSameIp "2021-10-28 09:33:00"
	 *
	 */
	public function monitorManyPlayerLoginViaSameIp($current_datetime = null){
		$this->load->model(['player_model', 'player_login_via_same_ip_logs', 'users']);

		$moniter_player_login_via_same_ip = $this->utils->getConfig('moniter_player_login_via_same_ip');
		$return = [];
		$return['result_list'] = [];
		$result_rows = null;
		$query_time_range = [];
		if($moniter_player_login_via_same_ip['is_enabled']){
			$query_interval = $moniter_player_login_via_same_ip['query_interval'];
			$except_ip_list = $moniter_player_login_via_same_ip['except_ip_list'];
			// $notify_in_mattermost_channel = $moniter_player_login_via_same_ip['notify_in_mattermost_channel'];
			// $tag_name_detected = $moniter_player_login_via_same_ip['tag_name_detected'];
			$result_rows = $this->_monitorManyPlayerLoginViaSameIp4query($current_datetime,  $query_interval, $except_ip_list, $query_time_range);
			$return['result_list']['query'] = $result_rows;
		}
		if( ! empty($result_rows) ){
			// hook the field, inserted_id.
			$this->_monitorManyPlayerLoginViaSameIp4insertLogs($result_rows);
		}

		if( ! empty($result_rows) ){
			$default_admin_usernames = $this->utils->getConfig('default_admin_usernames');
			$mattermost_channels = $this->utils->getConfig('mattermost_channels');
			$notify_in_mattermost_channel = $moniter_player_login_via_same_ip['notify_in_mattermost_channel'];
			if( ! empty($mattermost_channels) && ! empty($mattermost_channels[$notify_in_mattermost_channel])){
				$custom_lang = $this->utils->getConfig('custom_lang');
				$dateDiffStr = $this->utils->dateDiff($query_time_range['begin'], $query_time_range['end']);

				$theLatestOneReferrer = $this->users->getLatestOneReferrerOfUserLogs($default_admin_usernames);
				if( ! empty($theLatestOneReferrer) ){
					$parse = parse_url($theLatestOneReferrer);
					$sbe_doamin = $parse['scheme']. '://'. $parse['host'];
					$reportUri = $sbe_doamin. '/report_management/viewPlayerLoginViaSameIp';
					$query_params = [];
					$query_params['created_at_enabled_date'] = 0;
					$query_params['logged_in_at_enabled_date'] = 1;
					$query_params['logged_in_at_date_from'] = $query_time_range['begin'];
					$query_params['logged_in_at_date_to'] = $query_time_range['end'];
					$reportUri .= '?';
					$reportUri .= http_build_query($query_params);
				}

				// ole777idn site player login with same IP in past 10 min, please inform client to check it
				// smash site player login with same IP in past hour, please inform client to check it
				$sprintf_format = '%s site player login with same IP in past %s, please inform client to check it.'; // 2 params
				if( ! empty($theLatestOneReferrer) ){
					$sprintf_format .= PHP_EOL;
					$sprintf_format .= 'Please visit [SBE Report](%s).';// + 1 params
					$pretext = sprintf($sprintf_format, $custom_lang, $dateDiffStr, $reportUri);
				}else{
					$pretext = sprintf($sprintf_format, $custom_lang, $dateDiffStr);
				}

				$result4notifyInMM = $this->_monitorManyPlayerLoginViaSameIp4notifyInMM($result_rows, $notify_in_mattermost_channel, $pretext);
				$return['result_list']['notifyInMM'] = $result4notifyInMM;
			}
		} // EOF if( ! empty($result_rows) ){

		if( ! empty($result_rows) ){
			$tag_name_detected = $moniter_player_login_via_same_ip['tag_name_detected'];
			// Get the setting form operator_settings
			$detected_tag_id_key = Player_login_via_same_ip_logs::_operator_setting_name4detected_tag_id;
			$detected_tag_id = $this->operatorglobalsettings->getSettingValueWithoutCache($detected_tag_id_key);
			$tagName = $this->player_model->getTagNameByTagId($detected_tag_id);
			if( ! empty($tagName) ){
				// if it is exists, and assign from operator_settings
				$tag_name_detected = $tagName;
			}

			// username convert to payer_id
			$player_id_list = [];
			$logs_id_list = [];
			foreach($result_rows as $indexNumber => $row){
				array_push($player_id_list, $row['playerId']); // $this->player_model->getPlayerIdByPlayerName($row['username']));
				if( ! empty($row['inserted_id']) ){
					array_push($logs_id_list, $row['inserted_id']);
				}
			}// EOF foreach($result_rows as $indexNumber => $row){...
			$_this = $this;
			$addedTagCB = function($indexNumber, $_playerId, $_tagId, $_tagNameDetected) use ($_this, $logs_id_list){
				$_this->load->model(['player_login_via_same_ip_logs']);
				$logs_id = $logs_id_list[$indexNumber];
				$logs_data = [];
				$logs_data['tag_id'] = $_tagId;
				$logs_data['tagged_name'] = $_tagNameDetected;
				$logs_data['updated_at'] = $_this->utils->getNowForMysql();
				return $_this->player_login_via_same_ip_logs->update($logs_id, $logs_data);
			};
			$result4addTagInPlayers = $this->_monitorManyPlayerLoginViaSameIp4addTagInPlayers($tag_name_detected, $player_id_list, $addedTagCB);

			$return['result_list']['addTagInPlayers'] = $result4addTagInPlayers;
		}// EOF if( ! empty($result_rows) ){
		$this->utils->debug_log("monitorManyPlayerLoginViaSameIp.return:", $return);

		return $return;
	} // EOF monitorManyPlayerLoginViaSameIp
	/**
	 * The action, Add Tag In the Detected players
	 *
	 * @param string $tag_name_detected The tag name.
	 * @param array $player_id_list The player_id list.
	 * @param callable $addedTagCB Function name (int $indexNumber, int $playerId, int $tagId, string $tag_name_detected):void
	 * @return array The return array format as followings,
	 * - $return['msg'] string For trace reference.
	 * - $return['total_player_counter'] integer (optional) The number of players that need to tagged.
	 * - $return['added_counter'] integer (optional) The number of players that had tagged.
	 * - $return['ignore_adding_by_already_own_counter'] integer (optional) The number of skip the adding, that players that had tagged.
	 * - $return['add_failed_list'] integer (optional) The number of players that tagged fail.
	 */
	protected function _monitorManyPlayerLoginViaSameIp4addTagInPlayers($tag_name_detected = '', $player_id_list = [], callable $addedTagCB){
		$this->load->model(['player_model']);
		$return = [];
		$adminUserId = Transactions::ADMIN;
		if( ! empty($tag_name_detected) ){
			$tagId = $this->player_model->getTagIdByTagName($tag_name_detected);
			if(empty($tagId)){
				$tagId = $this->player_model->createNewTags($tag_name_detected, $adminUserId);
			}
			if( ! empty($player_id_list) ){
				$return['total_player_counter'] = count($player_id_list);
				$return['added_counter'] = 0;
				$return['ignore_adding_by_already_own_counter'] = 0;
				$return['add_failed_list'] = [];
				foreach($player_id_list as $indexNumber => $playerId){
					$isNeedAdded = null;
					$thePlayerTags = $this->player_model->getPlayerTagsForApi($playerId, $tagId, $adminUserId);
					if( ! empty($thePlayerTags) ){
						if( in_array($tag_name_detected, $thePlayerTags) ){
							$isNeedAdded = false;
							$return['ignore_adding_by_already_own_counter']++;
						}else{
							$isNeedAdded = true;
						}
					}else{
						$isNeedAdded = true;
					} // EOF if( ! empty($thePlayerTags) ){...
					if($isNeedAdded){
						$rlt = $this->player_model->addTagToPlayer($playerId, $tagId, $adminUserId);
						if($rlt){
							$return['added_counter']++;
							// update into player_login_via_same_ip_logs
							$addedTagCB($indexNumber, $playerId, $tagId, $tag_name_detected);
						}else{
							$add_failed_info = [];
							$add_failed_info['playerId'] = $playerId;
							$return['add_failed_list'][] = $add_failed_info;
						}
					} // EOF if($isNeedAdded){...
				} // EOF foreach($player_id_list as $indexNumber => $playerId){...
				$return['add_failed_counter'] = 0;
				if( ! empty($return['add_failed_list']) ){
					$return['add_failed_counter'] = count($return['add_failed_list']);
				}
				$return['msg'] = 'action completed.';
			}else{
				$return['msg'] = 'player_id_list is empty.';
			} // EOF if( ! empty($player_id_list) ){...
		}else{
			$return['msg'] = 'tag_name_detected is empty.';
		} // EOF if( ! empty($tag_name_detected) ){...
		return $return;
	} // EOF _monitorManyPlayerLoginViaSameIp4addTagInPlayers
	/**
	 * The query for the hacking case, Many Player Login Via Same Ip
	 *
	 * @param null|string $current_datetime The current date time, the format as "YYYY-mm-dd HH:ii:ss", e.q. "2021-10-12 03:12:23".
	 * @param integer $query_interval The interval sec of the query.
	 * @param array $except_ip_list The except ip list.
	 * @param (array)point $return_time_range The begin time and end time of query in database.
	 * @return array The rows.
	 */
	protected function _monitorManyPlayerLoginViaSameIp4query($current_datetime = null, $query_interval = 600, $except_ip_list = [], &$return_time_range = []){ // , $tag_name_detected= ''
		$this->load->model(['player_model']);

		$d = new DateTime($current_datetime); // 2021-10-29 12:23:34
		$end_time = $this->utils->formatDateTimeForMysql($d);
		$d = $d->sub(new DateInterval('PT' . $query_interval . 'S'));
		$start_time = $this->utils->formatDateTimeForMysql($d);
		$return_time_range['begin'] = $start_time;
		$return_time_range['end'] = $end_time;
		$ip_condition_sentence = '';
		if( ! empty($except_ip_list) ){
			$except_ip_list_imploded = '"';
			$except_ip_list_imploded .= implode('", "', $except_ip_list);
			$except_ip_list_imploded .= '"';
			$ip_condition_format = ' AND ip NOT IN ( %s ) '; // 1 param
			$ip_condition_sentence = sprintf($ip_condition_format, $except_ip_list_imploded);
		}

		$sql = <<<EOF
SELECT player_login_report.ip -- for param, csv_cols
, player_login_report.create_at as logged_in_at -- for param, csv_cols
, player.username -- for param, csv_cols
, (	CASE WHEN player_login_report.login_result=1 THEN "success" ELSE "failed" END) AS login_result -- for param, csv_cols
, player.playerId
, player_login_report.login_result as login_result_int
FROM player_login_report
INNER JOIN player on player_login_report.player_id = player.playerId
WHERE create_at BETWEEN "$start_time" AND "$end_time"
AND ip in(
	SELECT ip
	FROM player_login_report
	WHERE create_at BETWEEN "$start_time" AND "$end_time"
	$ip_condition_sentence
	GROUP BY ip
	HAVING COUNT(DISTINCT player_id)>2
)
EOF;

		$rows = $this->player_model->runRawSelectSQLArray($sql);
		return $rows;
	} // EOF function _monitorManyPlayerLoginViaSameIp4query()

	/**
	 * Insert the Data Into the data-table, "player_login_via_same_ip_logs".
	 *
	 * After inserted data, the rows will append the inserted id to each row.
	 * @param array $player_login_report_rows The rows that had detected.
	 * @return void
	 */
	protected function _monitorManyPlayerLoginViaSameIp4insertLogs(&$player_login_report_rows = [] ){
		// Player_login_via_same_ip_logs
		$this->load->model(['player_login_via_same_ip_logs']);
		if( ! empty($player_login_report_rows) ){
			//  player_login_via_same_ip_logs
			//  id, ip, logged_in_at, username, login_result, player_id, tag_id,  tagged_name, create_at, updated_at
			$nowForMysql = $this->utils->getNowForMysql();
			foreach($player_login_report_rows as $indexNumber => $row){
				$insert_data = [];
				$insert_data['ip'] = $row['ip'];
				$insert_data['logged_in_at'] = $row['logged_in_at'];
				$insert_data['username'] = $row['username'];
				$insert_data['player_id'] = $row['playerId'];
				$insert_data['login_result'] = $row['login_result_int'];
				$insert_data['create_at'] = $nowForMysql;
				$inserted_id = $this->player_login_via_same_ip_logs->create($insert_data);

				// append inserted id to each row.
				$player_login_report_rows[$indexNumber]['inserted_id'] = $inserted_id;
			}
		}
	} // EOF _monitorManyPlayerLoginViaSameIp4insertLogs
	/**
	 * The action, notify detected players in channel MM
	 *
	 * @param array $player_login_report_rows
	 * @param string $channel
	 * @return void
	 */
	protected function _monitorManyPlayerLoginViaSameIp4notifyInMM($player_login_report_rows = [], $channel = 'PPN002', $pretext = ''){
		$mmResult = null;
		if( ! empty($player_login_report_rows) ){
			try{ // convert to csv format
				ob_start();
				$output = fopen("php://output",'w');
				$csv_cols = ['ip', 'logged_in_at', 'username', 'login_result'];
				fputcsv($output, $csv_cols, "\t");
				foreach($player_login_report_rows as $row) {
					// filter by $csv_cols.
					$csv_row = [];
					foreach($csv_cols as $csv_col) {
						$csv_row[$csv_col] = $row[$csv_col];
					}

					fputcsv($output, $csv_row, "\t");
				} // EOF foreach($player_login_report_rows as $row) {...
				fclose($output);
				$csv = ob_get_contents();
				ob_end_clean();
			} catch (Exception $e) {
				// $message = $e->getMessage();
				// $this->utils->debug_log("==== {$message} ====");
				$this->utils->error_log('alert_command_module._monitorManyPlayerLoginViaSameIp4notifyInMM.e:', $e);
				$csv = false;
			}

			$level = 'danger';// 'info';
			if( empty($pretext) ){
				$pretext = 'Recently, the player site appeared to login with the same IP.';
			}
			$title = '';
			$message = '';
			$message .= '```PHP'. PHP_EOL;
			$message .= $csv. PHP_EOL;
			$message .= '```'. PHP_EOL;
			// $this->utils->debug_log("_monitorManyPlayerLoginViaSameIp4notifyInMM.pretext:", $pretext
			// 	, 'message:', $message
			// 	, 'title:', $title
			// 	, 'level:', $level
			// 	, 'channel:', $channel );
			if( ! empty($csv) ){
				$mmResult = $this->utils->sendMessageToMattermostChannel($channel, $level, $title, $message, $pretext);
			}
		} // EOF if(! empty($player_login_report_rows) ){
		return $mmResult;
	} // EOF _monitorManyPlayerLoginViaSameIp4notifyInMM()

}
