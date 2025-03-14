<?php

/**
 * Class onetime_command_module
 *
 * General behaviors include :
 *
 * * only one-time task
 *
 *
 * @category Command Line
 * @version 1.8.10
 * @copyright 2013-2022 tot
 */
trait onetime_command_module {

	/**
	 * overview : game adder
	 * @param date $start
	 * @param date $end
	 */
	public function game_adder($start = null, $end = null) {

		$this->utils->markProfilerStart('game_adder');

		$this->load->model('game_logs');
		$this->load->model('game_type_model');
		$this->load->model('game_description_model');

		$this->db->trans_start(); # ========================================================================================

		$unknown_games = $this->find_unknown_in_game_logs($start, $end);

		foreach ($unknown_games as &$unknown_game) {

			$unknown_game->game_code = str_replace(' ', '', strtolower($unknown_game->external_game_id));
			$unknown_game->game_name = strtolower($unknown_game->system_code . '.' . str_replace(' ', '', $unknown_game->external_game_id));
			$unknown_game->game_type_lang = strtolower($unknown_game->system_code . '_' . str_replace(' ', '_', $unknown_game->type));

			# CREATE GAME TYPE IF NO GAME TYPE ID RETURNED
			$unknown_game->game_type_id = $this->game_type_model->getGameTypeId($unknown_game->type, $unknown_game->game_platform_id);
			if (!$unknown_game->game_type_id) {
				$unknown_game->game_type_id = $this->game_type_model->create(array(
					'game_platform_id' => $unknown_game->game_platform_id,
					'game_type' => $unknown_game->type,
					'game_type_lang' => $unknown_game->game_type_lang,
					'note' => 'SYSTEM GENERATED GAME TYPE',
				));
			}

			# CREATE GAME DESCRIPTION IF NO GAME DESCRIPTION ID RETURNED
			$unknown_game->game_description_id = $this->game_description_model->getGameDescriptionId($unknown_game->game_code);
			if (!$unknown_game->game_description_id) {

				$new_game = array(
					'game_platform_id' => $unknown_game->game_platform_id,
					'external_game_id' => $unknown_game->external_game_id,
					'english_name' => $unknown_game->english_name,
					'clientid' => $unknown_game->clientid,
					'moduleid' => $unknown_game->moduleid,
					'game_type_id' => $unknown_game->game_type_id,
					'game_code' => $unknown_game->game_code,
					'game_name' => $unknown_game->game_name,
					'note' => 'SYSTEM GENERATED GAME DESCRIPTION',
				);

				$this->db->insert('game_description', $new_game);

				$game_description_id = $this->db->insert_id();
				$unknown_game->game_description_id = $game_description_id;
				$new_game['id'] = $game_description_id;
				$new_game_list[] = $new_game;

			} else {

				# UPDATE GAME DESCRIPTION's GAME TYPE ID IF GAME TYPE IS NOT THE SAME
				$game_type_id = $this->game_description_model->getGameTypeIdByGameCode($unknown_game->game_code);
				if ($game_type_id != $unknown_game->game_type_id) {
					$this->game_description_model->update($unknown_game->game_description_id, array(
						'game_type_id' => $unknown_game->game_type_id,
					));
				}

			}

			# UPDATE GAME_LOG
			$this->game_logs->update($unknown_game->id, array(
				'game_platform_id' => $unknown_game->game_platform_id,
				'game_type_id' => $unknown_game->game_type_id,
				'game_description_id' => $unknown_game->game_description_id,
				'game' => $unknown_game->english_name,
				'game_type' => $unknown_game->type,
				'game_code' => $unknown_game->game_code,
				'note' => 'SYSTEM UPDATED GAME LOGS',
			));

		}

		$this->db->trans_commit(); # =====================================================================================

		if ($new_game_list) {
			$this->load->library('email_setting');
			$this->email_setting->sendEmail($this->config->item('email_new_games'), array(
				'subject' => 'New Game Description' . $this->utils->getNowForMysql(),
				'body' => var_export($new_game_list, true),
			));
		}

		$this->utils->markProfilerEnd('game_adder');
		$this->utils->printProfilerLog('game_adder');

		echo "done";
	}



	/**
	 * Insert / Update the data of the json file into the data-table,"player_basic_amount_list".
	 *
	 * @param boolean $is_enforced_sync If its true,it will effect the existing data. Insert, update if the data exists.
	 * @param boolean $is_enforced_sync_while_player_no_exist  If its true, the function will enforced to insert/update data, event the player is not exists.
	 * If its false, the function will make sure the player's name in the data-table,"player", then to insert/update the data.
	 * @return void
	 */
	public function importBaseAmounts($is_enforced_sync = false, $is_enforced_sync_while_player_no_exist = false){
		$this->load->model(['operatorglobalsettings', 'player_basic_amount_list', 'player_model']);
		$theOperatorGlobalSettingName = 'base_amounts_imported_base_amounts_at';
		$targetDataTablename = 'player_basic_amount_list';
		$return_info = [];

		$issue_formats['same_data_issue_in_update'] = 'The player username,"%s" with the same data, count: %s'; // params, player_username, count of affected_rows
		$issue_formats['empty_data_in_update'] = 'There has no data updated with player username,"%s".'; // params, player_username
		$issue_formats['no_data_after_inserted'] = 'There has no data inserted with player username,"%s".'; // params, player_username

		$player_basic_amount_list_json_filename = $this->utils->getConfig('player_basic_amount_list_json_filename');
		$player_basic_amount_list_json_pathfile = APPPATH . '../../secret_keys/'.$player_basic_amount_list_json_filename;
		if ( file_exists($player_basic_amount_list_json_pathfile) ) {
			$strJsonFileContents = file_get_contents($player_basic_amount_list_json_pathfile);
			// $assoc = true;
			// $this->basicAmountList = $this->utils->json_decode_handleErr($strJsonFileContents, $assoc);
		}
		$imported_base_amounts_at = $this->operatorglobalsettings->getSettingValue($theOperatorGlobalSettingName);
		if( empty($imported_base_amounts_at) || $is_enforced_sync){

			if( ! empty($strJsonFileContents ) ){
				$assoc = true;
				$basicAmountList = $this->utils->json_decode_handleErr($strJsonFileContents, $assoc);
				$return_info['total_count'] = empty($basicAmountList)? 0: count($basicAmountList);
				$return_info['inserted_count'] = 0;
				$return_info['updated_count'] = 0;
				$return_info['username_no_exist_count'] = 0;
				$return_info['updated_results'] = [];

				$this->player_basic_amount_list->startTrans();
				foreach($basicAmountList as $indexNumber => $jsonData){

					$data = [];
					$data['player_username'] = $jsonData['username'];
					$data['total_bet_amount'] = $jsonData['total_bet_amount'];
					$data['total_deposit_amount'] = $jsonData['total_deposit_amount'];
					if($is_enforced_sync_while_player_no_exist){
						$is_only_username_exist = false;
					}else{
						$is_only_username_exist = true;
					}
					$results = $this->player_basic_amount_list->syncAmountsByUsername($data, 'player_username', $is_only_username_exist);


					$issue_info = [];
					if($results['is_done'] && isset($results['insert_id']) ){
						if(empty($results['insert_id']) ){
							$issue_info['data'] = $data;
							$issue_info['msg'] = sprintf($issue_formats['no_data_after_inserted'], $data['player_username']);
							$return_info['updated_results']['issue_data'][] = $issue_info;
						}else{
							$return_info['inserted_count']++;
						}
					}else if($results['is_done'] && ! empty($results['affected_rows']) ){
						if($results['affected_rows'] > 1){
							$issue_info['data'] = $data;
							$issue_info['msg'] = sprintf($issue_formats['same_data_issue_in_update'], $data['player_username'], $results['affected_rows']);
							$return_info['updated_results']['issue_data'][] = $issue_info;
						} else if($results['affected_rows'] == 0){
							$issue_info['data'] = $data;
							$issue_info['msg'] = sprintf($issue_formats['empty_data_in_update'], $data['player_username']);
							$return_info['updated_results']['issue_data'][] = $issue_info;
						}
						$return_info['updated_count'] += $results['affected_rows'];
					}else if(!$results['is_done'] && $results['code'] == Player_basic_amount_list::SYNC_RESULT_CODE_USERNAME_NOT_EXIST ){
						$issue_info['data'] = $data;
						$issue_info['msg'] = $this->player_basic_amount_list->getReasonBySyncResultCode($results['code']);
						$return_info['updated_results']['issue_data'][] = $issue_info;
						$return_info['username_no_exist_count']++;
					}

					$this->utils->debug_log('importBaseAmounts.foreach.indexNumber:',$indexNumber, 'total_count:', $return_info['total_count']);

				} // EOF foreach($basicAmountList as $indexNumber => $jsonData){
				$nowForMysql = $this->utils->getNowForMysql();
				if($this->operatorglobalsettings->existsSetting($theOperatorGlobalSettingName)){
					$this->operatorglobalsettings->putSetting($theOperatorGlobalSettingName, $nowForMysql, 'value');
				} else {
					$this->operatorglobalsettings->insertSetting($theOperatorGlobalSettingName, $nowForMysql);
				}
				$rlt = $this->player_basic_amount_list->endTransWithSucc();

				$return_info['synced_count'] = $return_info['inserted_count']+ $return_info['updated_count'];
				$return_info['msg'] = 'Done.';
				$return_info['rlt'] = $rlt;
			}else{
				$return_info['msg'] = 'The json file is empty. '. $player_basic_amount_list_json_filename;
			} // EOF if( ! empty($strJsonFileContents ) ){...
		}else{
			$operatorGlobalSetting = $this->operatorglobalsettings->getSetting($theOperatorGlobalSettingName);
			$imported_at = $operatorGlobalSetting->value;
			$return_info['msg'] = 'the json file had imported at '. $imported_at;
		} // EOF if( empty($imported_base_amounts_at) || $is_enforced_sync){...



			$this->utils->debug_log('importBaseAmounts.return_info:', $return_info);
	} // EOF importBaseAmounts()

	/**
	 * overview : find unknown in game logs
	 *
	 * @param date $start
	 * @param date $end
	 * @return array
	 */
	private function find_unknown_in_game_logs($start = null, $end = null) {

		$this->benchmark->mark('start');
		$this->db->select('game_logs.id');
		$this->db->select('game_logs.game_platform_id');
		$this->db->select('external_system.system_code');
		$this->db->from('game_logs');
		$this->db->join('game_type', 'game_type.id = game_logs.game_type_id', 'left');
		$this->db->join('game_description', 'game_description.id = game_logs.game_description_id', 'left');
		$this->db->join('external_system', 'external_system.id = game_logs.game_platform_id AND external_system.system_type = 1', 'left');
		$this->db->where('game_logs.external_uniqueid IS NOT NULL', null, false);
		if (isset($start, $end)) {
			$this->db->where('(game_logs.end_at =>', $start);
			$this->db->where('(game_logs.end_at =<', $end);
		}
		$this->db->where('(game_description.game_code', 'unknown');
		$this->db->where('game_type.game_type', 'unknown');
		$this->db->or_where('game_description.id IS NULL)', null, false);

		$this->find_row_in_origin();
		$this->guess_game_info();

		$query = $this->db->get();
		$this->benchmark->mark('end');

		$this->utils->debug_log($this->benchmark->elapsed_time('start', 'end'));

		return $query->result();
	}

	/**
	 * overview : find row in origin
	 */
	private function find_row_in_origin() {
		$this->load->model('external_system');
		$platforms = $this->external_system->getAllSytemGameApi();

		# find_row_in_origin
		foreach ($platforms as $platform) {
			$table = strtolower($platform['system_code']) . '_game_logs';
			$game_platform_id = $platform['id'];
			$this->db->join($table, "game_logs.game_platform_id = {$game_platform_id} AND {$table}.external_uniqueid = game_logs.external_uniqueid", 'left');
		}
	}

	/**
	 * overview : guess game information
	 */
	private function guess_game_info() {
		$this->load->model('external_system');
		$platforms = $this->external_system->getAllSytemGameApi();

		# guess_game_info - external_game_id
		$select_code = '(CASE ';
		foreach ($platforms as $platform) {
			$table = strtolower($platform['system_code']) . '_game_logs';
			$game_platform_id = $platform['id'];
			if ($game_platform_id == 1) {
				$select_code .= "WHEN game_logs.game_platform_id = {$game_platform_id} THEN {$table}.gameshortcode ";
			} else if ($game_platform_id == 6) {
				$select_code .= "WHEN game_logs.game_platform_id = {$game_platform_id} THEN {$table}.display_name ";
			}
		}
		$select_code .= ' ELSE null END) external_game_id';
		$this->db->select($select_code, null, false);

		# guess_game_info - english_name
		$select_name = '(CASE ';
		foreach ($platforms as $platform) {
			$table = strtolower($platform['system_code']) . '_game_logs';
			$game_platform_id = $platform['id'];
			if ($game_platform_id == 1) {
				# PT
				$select_name .= "WHEN game_logs.game_platform_id = {$game_platform_id} THEN {$table}.gamename ";
			} else if ($game_platform_id == 6) {
				# MG
				$select_name .= "WHEN game_logs.game_platform_id = {$game_platform_id} THEN {$table}.display_name ";
			}
		}
		$select_name .= ' ELSE null END) english_name';
		$this->db->select($select_name, null, false);

		# guess_game_info - type
		$select_type = '(CASE ';
		foreach ($platforms as $platform) {
			$table = strtolower($platform['system_code']) . '_game_logs';
			$game_platform_id = $platform['id'];
			if ($game_platform_id == 1) {
				# PT
				$select_type .= "WHEN game_logs.game_platform_id = {$game_platform_id} THEN {$table}.gametype ";
			} else if ($game_platform_id == 6) {
				# MG
				$select_type .= "WHEN game_logs.game_platform_id = {$game_platform_id} THEN {$table}.display_game_category ";
			}
		}
		$select_type .= ' ELSE null END) type';
		$this->db->select($select_type, null, false);

		# guess_game_info - clientid
		$select_client = '(CASE ';
		foreach ($platforms as $platform) {
			$table = strtolower($platform['system_code']) . '_game_logs';
			$game_platform_id = $platform['id'];
			if ($game_platform_id == 6) {
				# MG
				$select_client .= "WHEN game_logs.game_platform_id = {$game_platform_id} THEN {$table}.client_id ";
			}
		}
		$select_client .= ' ELSE null END) clientid';
		$this->db->select($select_client, null, false);

		# guess_game_info - moduleid
		$select_module = '(CASE ';
		foreach ($platforms as $platform) {
			$table = strtolower($platform['system_code']) . '_game_logs';
			$game_platform_id = $platform['id'];
			if ($game_platform_id == 6) {
				# MG
				$select_module .= "WHEN game_logs.game_platform_id = {$game_platform_id} THEN {$table}.module_id ";
			}
		}
		$select_module .= ' ELSE null END) moduleid';
		$this->db->select($select_module, null, false);

	}

	/**
	 * overview : get all pt balance
	 *
	 * @param string $username
	 */
	// public function query_pt_balance($username) {
	// 	$this->load->model(array('game_provider_auth', 'player_model'));
	// 	$api = $this->loadApi(PT_API);
	// 	$playerId = $this->player_model->getPlayerIdByUsername($username);
	// 	if (!empty($playerId)) {
	// 		$isRegisteredFlag = $this->game_provider_auth->isRegisterd($playerId, PT_API);
	// 		if ($isRegisteredFlag) {
	// 			$rlt = $api->queryPlayerBalance($username);
	// 			$this->utils->debug_log('rlt', $rlt);
	// 			$this->output->append_output(var_export($rlt, true) . "\n");
	// 		}

	// 	}
	// }

	/**
	 * overview : fix merge mg game logs
	 *
	 * @param string $fromStr
	 * @param string $toStr
	 * @param string $playerName
	 */
	public function fix_merge_mg_game_logs($fromStr, $toStr, $playerName = null) {
		$rlt = array('success' => false);
		$api = $this->loadApi(MG_API);
		if ($api) {
			$token = random_string('unique');
			//set from to
			$dateTimeFrom = null;
			$dateTimeTo = null;
			if (!empty($fromStr)) {
				$dateTimeFrom = new \DateTime($fromStr);
			}
			if (!empty($toStr)) {
				$dateTimeTo = new \DateTime($toStr);
			}
			$api->saveSyncInfoByToken($token, $dateTimeFrom, $dateTimeTo, $playerName);

			$rlt = $api->syncMergeToGameLogs($token);
		}

		$this->output->append_output(var_export($rlt, true) . "\n");
	}

	/**
	 * overview : show affiliate password
	 *
	 * @param string $username
	 */
	public function show_aff_password($username) {
		$this->db->from('affiliates')->where('username', $username);
		$qry = $this->db->get();
		$row = $qry->row();
		$password = '';
		if ($row) {
			$this->load->library(array('salt'));
			$password = $row->password;
			$password = $this->salt->decrypt($password, $this->getDeskeyOG());
		}

		$this->output->append_output("password:" . $password . "\n");
	}

	public function manual_fix_t1t_common_brazil_referral_daily_record($dry_run = false, $dateApplyFrom =_COMMAND_LINE_NULL, $dateApplyTo =_COMMAND_LINE_NULL, $promorulesId = _COMMAND_LINE_NULL, $transactionStatus = _COMMAND_LINE_NULL){
		$this->utils->info_log('start manual_fix_t1t_common_brazil_referral_daily_record');
		$this->load->model(['player_friend_referral', 'player_promo', 'player_model']);
		if($dateApplyFrom == _COMMAND_LINE_NULL){
			$dateApplyFrom = '2024-03-09 11:41:55';
		}
		if($dateApplyTo == _COMMAND_LINE_NULL){
			$dateApplyTo = $this->utils->getNowForMysql();
		}
		if($promorulesId == _COMMAND_LINE_NULL){
			$promorulesId = '17467';
		}
		if($transactionStatus == _COMMAND_LINE_NULL){
			$transactionStatus = Player_friend_referral::STATUS_PAID;
		}

		$dry_run=$dry_run=='true';
		$this->utils->info_log('manual_fix_t1t_common_brazil_referral_daily_record', [
			"dateApplyFrom" => $dateApplyFrom,
			"dateApplyTo" => $dateApplyTo,
			"promorulesId" => $promorulesId,
			"transactionStatus" => $transactionStatus,
			"dry_run" => $dry_run
		]);

		$this->db->select('playerpromo.playerpromoId,
				playerpromo.playerId,
				withdraw_conditions.source_id as transactionId,
				playerpromo.dateApply,
				playerpromo.bonusAmount,
				playerpromo.order_generated_by,
				playerpromo.player_request_ip,
				playerpromo.referralId')
				//playerpromo.promorulesId')
				->from('playerpromo')
				->join('withdraw_conditions', 'withdraw_conditions.player_promo_id = playerpromo.playerpromoId', 'left')
				->where('playerpromo.promorulesId', $promorulesId)
				->where('playerpromo.dateApply >=', $dateApplyFrom)
				->where('playerpromo.dateApply <=', $dateApplyTo)
				->where('playerpromo.transactionStatus !=', $transactionStatus);

		$result = $this->player_friend_referral->runMultipleRowArray();
		$this->utils->printLastSQL();
		$this->utils->debug_log('count', count($result), 'result', $result);

		$grouped = [];
		if(!empty($result)){
			foreach ($result as $item) {
				$ip = $item['player_request_ip']; //player_request_ip
				$playerId = $item['playerId'];
				if (!isset($grouped[$ip])) {
					$grouped[$ip] = [];
				}
				$grouped[$ip][$playerId] = $item;
			}
		}

		$abnormal = [];
		// 现在 $grouped 中包含了按照 IP 地址分组后的数据
		if(!empty($grouped)){
			foreach ($grouped as $ip => &$entries) {
				$count = count($entries);
				if($count != 2){
					$abnormal[$ip] = $entries;
					unset($grouped[$ip]);
					continue;
				}

				/* 如果第一筆record的有referralId (start) */
				$tmp_playerId = null;
				$tmp_invitedPlayerId = null;
				$tmp_referralId = null;
				foreach($entries as $playerId => &$entry){
					if(!empty($tmp_invitedPlayerId) && !empty($tmp_playerId)){
						// 如果第二筆的playerId和第一筆的invitedPlayerId相同，那么第二筆的referralId就是第一筆的referralId
						if($playerId == $tmp_invitedPlayerId){ // $entry[1] = playerId
							$entry['referralId'] = $tmp_referralId;
						}else if($playerId == $tmp_playerId){
							$entry['referralId'] = $tmp_referralId;
						}
						continue;
					}
					if(!empty($entry['referralId'])){ // referralId
						$referralId = $entry['referralId'];
						$row = $this->player_friend_referral->getReferralByReferralId($referralId);
						$tmp_invitedPlayerId = $row['invitedPlayerId'];
						$tmp_playerId = $row['playerId'];
						$tmp_referralId = $referralId;
						continue;
					}
				}
				/* 如果第一筆record的有referralId (end) */
			}

			/* 如果第一筆record沒有referralId (start) */
			foreach($grouped as $ip => &$entries){
				$referralArr = [];
				$first_record_ip = null;
				$first_record_playerId = null;
				$second_record_playerId = null;
				foreach($entries as $playerId => &$entry){
					if(!empty($entry['referralId'])){
						continue;
					}

					if(!empty($referralArr[$playerId])){
						$second_record_playerId = $playerId;
						//用第二筆的playerId與$referralArr比對，如果有找到，就把referralId填入第一筆、第二筆的
						$referralId = $referralArr[$playerId];
						$grouped[$ip][$first_record_playerId]['referralId'] = $referralId;
						$grouped[$ip][$second_record_playerId]['referralId'] = $referralId;
						continue;
					}

					$first_record_playerId = $playerId;
					$referrerList = $this->player_friend_referral->getPlayerReferralList($first_record_playerId);
					$invitedList = $this->player_friend_referral->getPlayerReferralList(null, null, null, null, $first_record_playerId);
					if(!empty($referrerList) && empty($invitedList)){
						foreach($referrerList as $list){
							$referralArr[$list->invitedUserId] = $list->referralId;
						}
					}else if(empty($referrerList) && !empty($invitedList)){
						foreach($invitedList as $list){
							$referralArr[$list->playerId] = $list->referralId;
						}
					}else if(!empty($referrerList) && !empty($invitedList)){
						foreach($referrerList as $list){
							$referralArr[$list->invitedUserId] = $list->referralId;
						}
					}
				}
			}

			/* 如果第一筆record沒有referralId (end) */
			$this->utils->debug_log('grouped', $grouped);
			$this->utils->debug_log('abnormal', $abnormal);

			$patch_data = $grouped;
			$fixed_data = [];
			if(!empty($patch_data)){
				//1. playerpromo.referralId => 會套用到好友推薦優惠申請頁Type, Referrer, Referred
				//2. player.refereePlayerId => 會套用到被推薦人的推薦人是誰: player.refereePlayerId
				//3-1. playerfriendreferral.status4invited, transactionId4invited => 會套用到被推薦人的 狀態(paid)、transactionId
				//3-2. playerfriendreferral.status, transactionId => 會套用到推薦人的 狀態(paid)、transactionId
				foreach($patch_data as $ip => $patch_entries){
					foreach($patch_entries as $patch_entry){
						$playerpromoId = $patch_entry['playerpromoId'];
						$playerId = $patch_entry['playerId'];
						$transactionId = $patch_entry['transactionId'];
						$referralId = $patch_entry['referralId'];

						$this->utils->info_log("process patch_entry ip: $ip, player: $playerId, entry => ", $patch_entry);
						if(empty($referralId)){
							$this->utils->debug_log('referralId is empty');
							continue;
						}

						//1. update playerpromo referralId
						//=> player_promo::updatePlayerPromo
						$patch_playerpromo_data = ['referralId' => $referralId];
						$this->utils->debug_log("update playerpromo=> playerpromoId: $playerpromoId, referralId: $referralId");
						if(!$dry_run){
							$patch_playerpromo = $this->player_promo->updatePlayerPromo($playerpromoId, $patch_playerpromo_data);
						}

						$type = null;
						$patch_player_data = null;
						$patch_player_friend_referral_data = null;
						$referral = $this->player_friend_referral->getReferralByReferralId($referralId);
						$this->utils->debug_log('referral', $referral);

						if($playerId == $referral['invitedPlayerId']){
							$type = 'invited player';
							$this->utils->debug_log('type', $type);

							//2. update player refereePlayerId
							//=> player_model::updatePlayer
							$refereePlayerId = $referral['playerId'];
							if(!$dry_run){
								$patch_player = $this->player_model->updatePlayer($playerId, ['refereePlayerId' => $refereePlayerId]);
							}
							$this->utils->debug_log("update player=> invited playerId: $playerId, refereePlayerId: $refereePlayerId");

							//3-1.
							//update invitedPlayer status4invited, transactionId4invited
							//=> player_friend_referral::paidPlayerFriendReferral2invited
							if(!$dry_run){
								$this->player_friend_referral->paidPlayerFriendReferral2invited($playerId, $transactionId, $referralId);
							}
							$this->utils->debug_log("update $type playerfriendreferral=> invitedPlayerId: $playerId, transactionId4Invited: $transactionId, referralId: $referralId");

						}else if($playerId == $referral['playerId']){
							$type = 'referrer player';
							$this->utils->debug_log('type', $type);

							$patch_player_friend_referral_data = ['referralId' => $referralId, 'transactionId' => $transactionId];
							//3-2.
							//update referrer status, transactionId
							//=> player_friend_referral::paidPlayerFriendReferral
							if(!$dry_run){
								$this->player_friend_referral->paidPlayerFriendReferral($referralId, $transactionId);
							}
							$this->utils->debug_log("update $type playerfriendreferral=> playerId: $playerId, transactionId: $transactionId, referralId: $referralId");
						}else{
							$type = 'unknown';
							$this->utils->error_log('type', $type);
						}

						$fixed_data[] = $patch_entry;
					}
				}
			}
			$this->utils->debug_log('total fixed', count($fixed_data));
			$this->utils->debug_log('fixed_data', $fixed_data);
		}
		$this->utils->info_log('end manual_fix_t1t_common_brazil_referral_daily_record');
	}

	/**
	 * function manul_fix_redemption_code_record
	 * @param int $code_id
	 * @param int $status
	 * @param int $playerpromoId
	 * @param int $player_id
	 * @param string $dry_run OFF to do real update
	 */
	public function manul_fix_redemption_code_record(
		$code_id,
		$status, // 1,2,3,4
		$playerpromoId = _COMMAND_LINE_NULL,
		$player_id = _COMMAND_LINE_NULL,
		$dry_run = 'ON'
		){

		$this->utils->info_log('manul_fix_redemption_code', [
			"code_id" => $code_id,
			"statsu" => $status,
			"playerpromoId" => $playerpromoId,
			"player_id" => $player_id
		]);
		$this->load->model(['static_redemption_code_model', 'player_model']);
		$toUpdate = false;
		$updateData = [];
		$updateData['status'] = $status;
		$is_allowed_status = in_array($status, [
			static_redemption_code_model::CODE_STATUS_UNUSED,
			static_redemption_code_model::CODE_STATUS_USED,
			static_redemption_code_model::CODE_STATUS_EXPIRED,
			static_redemption_code_model::CODE_STATUS_PENDING,
		]);
		$this->utils->debug_log('is_allowed_status', $is_allowed_status, 'status', $status);
		if($is_allowed_status){
			$redeemCode = $this->static_redemption_code_model->getItemField($code_id);
			if(empty($redeemCode)){
				$this->output->append_output("code id not exist.\n");
				return $toUpdate;
			}
			$this->utils->debug_log('redeemCode', $redeemCode);


			if($player_id != _COMMAND_LINE_NULL){
				$_player = $this->player_model->getPlayerById($player_id);
				if(empty($_player)){
					$this->output->append_output("player id not exist.\n");
					return $toUpdate;
				}
				$this->utils->debug_log('_player', $_player);
			}

			switch($status){
				case static_redemption_code_model::CODE_STATUS_UNUSED:
					$updateData['request_at'] = NULL;
					$updateData['player_id'] = NULL;
					$updateData['promo_cms_id'] = NULL;
					$toUpdate = true;
					break;
				case static_redemption_code_model::CODE_STATUS_USED:
					// if($player_id != _COMMAND_LINE_NULL){
					// 	$updateData['player_id'] = $player_id;
					// }
					if($playerpromoId != _COMMAND_LINE_NULL){
						$playerpromo = $this->player_promo->getPlayerPromo($playerpromoId);
						if(empty($playerpromo)){
							$this->output->append_output("playerpromo id not exist.\n");
							break;
						}
						$this->utils->debug_log('playerpromo', $playerpromo);
						$updateData['promo_cms_id'] = $playerpromoId;
					}
					if(empty($updateData['promo_cms_id'])){
						$this->output->append_output("empty promo_cms_id.\n");
						break;
					}
					$updateData['player_id'] = $playerpromo->playerId;
					$updateData['request_at'] = $playerpromo->dateApply;
					$toUpdate = true;
					break;
			}
			$this->utils->info_log("values",[
				'updateData' => $updateData,
				'toUpdate' => $toUpdate,
				'dry_run' => $dry_run]
			);

			if($toUpdate && $dry_run === 'OFF'){
				$this->static_redemption_code_model->startTrans();
				$rlt = $this->static_redemption_code_model->updateItem($code_id, $updateData);
				$this->static_redemption_code_model->endTransWithSucc();
				$this->output->append_output(var_export($rlt, true) . "\n");
			}
		}
		$this->utils->info_log('end manul_fix_redemption_code');
	}

	/**
	 * Undocumented function
	 *
	 * @param string $date_from YYYY-MM-DD HH:MM:SS
	 * @param string $date_end YYYY-MM-DD HH:MM:SS
	 * @param string $dry_run ON / OFF
	 * @return void
	 */
	public function disableAndSoftdeleteStaticRedemptionCodeCate($date_from, $date_end, $set_deleted = 'skip', $dry_run = 'ON') {
		$this->utils->info_log('disableAndSoftdeleteStaticRedemptionCodeCate', [
			'date_from' => $date_from,
			'date_end' => $date_end,
			'dry_run' => $dry_run
		]);

		$this->load->model(['static_redemption_code_model']);
		$this->db
		->from('static_redemption_code_category')
		->where ('created_at >', $date_from)
		->where('created_at <', $date_end);
		$res = $this->static_redemption_code_model->runMultipleRowArray();

		$this->utils->debug_log('get static_redemption_code_category');
		$this->utils->info_log('count', count($res));

		foreach($res as $row){
			$this->utils->debug_log('row', $row);
			$this->utils->info_log('row-id', $row['id']);
			$categoryId = $row['id'];
			$currentActionLogs = $row['action_logs'];
			$this->static_redemption_code_model->startTrans();
			$rlt = 'skip';
			if($dry_run === 'OFF'){
				$timestamp = $this->utils->getNowForMysql();
				$operator = 'command_line';
				$newStatus = static_redemption_code_model::CATEGORY_STATUS_DEACTIVATE;
				$newActionLogs = $currentActionLogs . "</br>[$timestamp] $operator update Status to $newStatus|";
				$update_arr = [
					'status' => $newStatus,
					'updated_by' => $operator,
					'action_logs' => $newActionLogs
				];
				if($row['status'] != $newStatus){
					$this->utils->debug_log('update_arr', $update_arr);
					$result = $this->static_redemption_code_model->updateCategory($categoryId, $update_arr);
					$rlt = $result;
				}
				$this->utils->debug_log('update status '. $categoryId, $rlt);
				$rlt = 'skip';
				if($set_deleted === 'Y') {
					if($row['is_deleted'] != static_redemption_code_model::CATEGORY_IS_DELETED_FLAG) {
						$rlt = $this->static_redemption_code_model->softDeleteCategory($categoryId);
					}
				}
				$this->utils->debug_log('update is_deleted '. $categoryId. $set_deleted, $rlt);
			}
			$this->static_redemption_code_model->endTransWithSucc();
		}
	}

	public function revertDisableAndSoftdeleteStaticRedemptionCodeCate($category_id){
		$this->utils->info_log('revertDisableAndSoftdeleteStaticRedemptionCodeCate', [
			'category_id' => $category_id
		]);
		$this->load->model(['static_redemption_code_model']);
		$cateRow = $this->static_redemption_code_model->getCategory($category_id);
		$currentActionLogs = $cateRow['action_logs'];
		$this->static_redemption_code_model->startTrans();
		$operator = 'command_line';
		$timestamp = $this->utils->getNowForMysql();
		$newActionLogs = $currentActionLogs . "</br>[$timestamp] $operator revert deleted|";
		$update_arr = [
			'is_deleted' => 0,
			'deleted_on' => null,
			'action_logs' => $newActionLogs
		];
		$this->utils->debug_log('update_arr', $update_arr);
		$rlt = $this->static_redemption_code_model->updateCategory($category_id, $update_arr);
		$this->static_redemption_code_model->endTransWithSucc();
		$this->utils->debug_log('revertSoftDeleteCategory', $rlt);
	}

	/**
	 * overview : change auto increment
	 *
	 * @param int $number
	 */
	public function change_auto_increment($number = 16877) {
		$tables = [
			'sale_orders',
			'transactions',
			'player',
			'adminusers',
			'affiliates',
			'promorules',
			'promocmssetting',
			'vipsetting',
			'walletaccount',
			'playeraccount',
		];

		foreach ($tables as $table) {
			$this->db->query("ALTER TABLE {$table} AUTO_INCREMENT {$number}");
		}
		$msg = $this->utils->debug_log($tables, 'change to ', $number);
		$this->returnText($msg);
	}

	/**
	 * overview : show
	 * @param $username
	 */
	public function show_player_password($username) {
		$this->db->from('player')->where('username', $username);
		$qry = $this->db->get();
		$row = $qry->row();
		$password = '';
		if ($row) {
			$this->load->library(array('salt'));
			$password = $row->password;
			$password = $this->salt->decrypt($password, $this->getDeskeyOG());
		}

		$this->utils->debug_log("password:" . $password . "\n");
	}

	/**
	 * overview : sync IPS to transaction
	 */
// 	public function sync_ips_to_trans() {
// 		$this->load->library(array('authentication'));
// 		$this->load->model(array('sale_order'));
// 		$sql = <<<EOD
// select * from sale_orders where external_order_id like 'NT%' and transaction_id is null and status=5
// EOD;

// 		$qry = $this->db->query($sql);
// 		foreach ($qry->result() as $row) {
// 			$orderId = $row->id;
// 			$this->utils->debug_log('update ', $orderId, 'to processing', Sale_order::STATUS_PROCESSING);
// 			$this->db->where('id', $orderId)->set('status', Sale_order::STATUS_PROCESSING)
// 				->update('sale_orders');
// 			$rlt = $this->sale_order->approveSaleOrder($orderId);
// 			$this->utils->debug_log('approveSaleOrder', $orderId, 'result', $rlt);
// 		}

// 	}


	/**
	 * overview : sync game table
	 */
	public function sync_game_table() {
		//sync 'game' table
		//delete game
		//insert from external_system
		$this->load->model(array('external_system'));
		$this->external_system->startTrans();
		$this->external_system->syncGameTable();
		$rlt = $this->external_system->endTransWithSucc();
		$msg = $this->utils->debug_log('sync game table result', $rlt);

		$this->returnText($msg);
	}

	public function test_send_message($anytext){

		$ignore=<<<EOD
{
    "from": "2016-11-18 13:51:14",
    "to": "2016-11-18 14:21:14",
    "result": {
        "success": true,
        "sync_result": {
            "syncOriginalGameLogs": {
                "20": {
                    "success": true,
                    "response_result_id": 7401326
                },
                "22": {
                    "success": true,
                    "count": 24,
                    "sum": 83.7,
                    "real_count": 1074
                },
                "29": {
                    "success": true
                },
                "53": {
                    "success": true
                },
                "66": {
                    "success": true
                },
                "69": {
                    "success": true
                },
                "70": true,
                "71": {
                    "success": false,
                    "lastRowId": null
                },
                "72": {
                    "success": true
                },
                "73": null,
                "84": {
                    "success": false,
                    "response_result_id": 7401673
                },
                "85": {
                    "success": true,
                    "response_result_id": 7401675
                }
            },
            "syncMergeToGameLogs": {
                "20": {
                    "success": true,
                    "count": 0
                },
                "22": {
                    "success": true
                },
                "29": {
                    "success": true
                },
                "53": {
                    "success": true
                },
                "66": {
                    "success": true
                },
                "69": null,
                "70": {
                    "success": false
                },
                "71": {
                    "success": true
                },
                "72": {
                    "success": true
                },
                "73": {
                    "success": false
                },
                "84": {
                    "success": true,
                    "count": 0
                },
                "85": {
                    "success": true
                }
            }
        }
    }
}
EOD;

		$json=json_decode( $anytext, true );

		$this->utils->debug_log('json', $json);

		$this->utils->sendMessageService($json );

	}

	public function add_ezugi_gamelogs(){

		$this->load->dbforge();

		$fields = array(
			'id' => array(
				'type' => 'INT',
				'null' => false,
				'auto_increment' => TRUE,
			),
			'PlayerId' => array(
				'type' => 'INT',
				'constraint' => '11',
				'null' => false,
			),
			'Username' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => false,
			),
			'BetTypeID' => array(
				'type' => 'VARCHAR',
				'constraint' => '11',
				'null' => true,
			),
			'ezugiID' => array(
				'type' => 'VARCHAR',
				'constraint' => '11',
				'null' => true,
			),
			'ezugiID4' => array(
				'type' => 'VARCHAR',
				'constraint' => '11',
				'null' => true,
			),
			'RoundID' => array(
				'type' => 'VARCHAR',
				'constraint' => '11',
				'null' => true,
			),
			'ServerID' => array(
				'type' => 'VARCHAR',
				'constraint' => '11',
				'null' => true,
			),
			'TableID' => array(
				'type' => 'VARCHAR',
				'constraint' => '11',
				'null' => true,
			),
			'UID' => array(
				'type' => 'VARCHAR',
				'constraint' => '11',
				'null' => true,
			),
			'UID2' => array(
				'type' => 'VARCHAR',
				'constraint' => '11',
				'null' => true,
			),
			'OperatorID' => array(
				'type' => 'VARCHAR',
				'constraint' => '11',
				'null' => true,
			),
			'OperatorID2' => array(
				'type' => 'VARCHAR',
				'constraint' => '11',
				'null' => true,
			),
			'SessionCurrency' => array(
				'type' => 'VARCHAR',
				'constraint' => '20',
				'null' => true,
			),
			'SkinID' => array(
				'type' => 'VARCHAR',
				'constraint' => '11',
				'null' => true,
			),
			'BetSequenceID' => array(
				'type' => 'VARCHAR',

				'constraint' => '11',
				'null' => true,
			),
			'Bet' => array(
				'type' => 'DOUBLE',
				'null' => true,
			),
			'Win' => array(
				'type' => 'DOUBLE',
				'null' => true,
			),
			'Bankroll' => array(
				'type' => 'DOUBLE',
				'null' => true,
			),
			'GameString' => array(
				'type' => 'TEXT',
				'null' => true,
			),
			'GameString2' => array(
				'type' => 'TEXT',
				'null' => true,
			),
			'SeatID' => array(
				'type' => 'VARCHAR',
				'constraint' => '11',
				'null' => true,
			),
			'BetStatusID' => array(
				'type' => 'VARCHAR',
				'constraint' => '11',
				'null' => true,
			),
			'BrandID' => array(
				'type' => 'VARCHAR',
				'constraint' => '11',
				'null' => true,
			),
			'RoundDateTime' => array(
				'type' => 'DATETIME',
				'null' => true,
			),
			'ActionID' => array(
				'type' => 'VARCHAR',
				'constraint' => '11',
				'null' => true,
			),
			'BetType' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => true,
			),
			'PlatformID' => array(
				'type' => 'VARCHAR',
				'constraint' => '11',
				'null' => true,
			),
			'DateInserted' => array(
				'type' => 'DATETIME',
				'null' => true,
			),
			'GameTypeID' => array(
				'type' => 'VARCHAR',
				'constraint' => '11',
				'null' => true,
			),
			'BFTransactionFound' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => true,
			),
			'GameTypeName' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => true,
			),
			'DealerID' => array(
				'type' => 'VARCHAR',
				'constraint' => '20',
				'null' => true,
			),
			'ErrorCode' => array(
				'type' => 'VARCHAR',
				'constraint' => '11',
				'null' => true,
			),
			'originalErrorCode' => array(
				'type' => 'VARCHAR',
				'constraint' => '11',
				'null' => true,
			),
			'TransactionID' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => true,
			),
			'external_uniqueid' => array(
				'type' => 'VARCHAR',
				'constraint' => '300',
				'null' => true,
			),
			'response_result_id' => array(
				'type' => 'VARCHAR',
				'constraint' => '300',
				'null' => true,
			)
		);


		$this->dbforge->add_field($fields);
		$this->dbforge->add_key('id', TRUE);

		$this->dbforge->create_table('ezugi_game_logs');
		$this->load->model(['player_model']);
		$this->player_model->addIndex('ezugi_game_logs', 'idx_UID', 'UID');
		$this->player_model->addIndex('ezugi_game_logs', 'idx_RoundDateTime', 'RoundDateTime');
		$this->player_model->addIndex('ezugi_game_logs', 'idx_GameTypeName', 'GameTypeName');

	}

    /**
     * Batch set promotion status with CSV
     *
     * Command,
     * <code>
     * sudo /bin/bash admin/shell/command.sh batch_set_promotion_status_from_csv 'OGP-33106.userrname.dev.csv' 'disable' > ./logs/command_batch_set_promotion_status_from_csv &
     * </code>
     *
     * P.S. the CSV file, "OGP-33106.userrname.dev.csv" should be in the folder, "admin/application/logs/tmp_shell/".
     *
     * @param string $username_list_csv_file
     * @param string $status
     * @return void
     */
    public function batch_set_promotion_status_from_csv($username_list_csv_file='', $status = 'disable'){

        $this->CI->load->model(['player_model']);

        $csv_file_path = BASEPATH.'/../application/logs/tmp_shell/'.$username_list_csv_file;
        // $player_basic_amount_list_json_filename = $this->utils->getConfig('player_basic_amount_list_json_filename');
		// $player_basic_amount_list_json_pathfile = APPPATH . '../../secret_keys/'.$player_basic_amount_list_json_filename;

        $counter_list = [];
		$counter_list['totalInUsername'] = 0;
        $counter_list['totalInPlayerIdsByUsernames'] = 0;

        $username_list = [];

        $_IMPORT_PLAYER_CSV_HEADER=['UserName'];
        $ignore_first_row = false;
        $self = $this;
        $success = $this->utils->loopCSV($csv_file_path, $ignore_first_row, $cnt, $message
                    , function($cnt, $rowData, &$stop_flag)
                    use ($_IMPORT_PLAYER_CSV_HEADER, &$username_list, &$counter_list, &$self)
        {
            $stop_flag = false; // assign true to break
            $row = array_combine($_IMPORT_PLAYER_CSV_HEADER, $rowData);

            if( ! in_array($row['UserName'],$username_list) ){
                array_push($username_list, $row['UserName']);
            }
            $counter_list['totalInUsername']++;

            // $self->utils->debug_log('OGP-33106.1030.cnt:', $cnt, 'rowData:', $rowData);
        }); // EOF $this->utils->loopCSV(...
        // $this->utils->debug_log('OGP-33106.1033.username_list:', $username_list);
        $player_id_list = [];
        $_rows = $this->player_model->getPlayerIdsByUsernames($username_list);
        // $_rows = $this->player_model->getPlayerIdsAndUsernamesByUsernames($username_list);
        if( !empty($_rows) ){
            $counter_list['totalInPlayerIdsByUsernames'] = count($_rows);
            $player_id_list = array_column($_rows, 'playerId');
        }
        $_counter_list = $this->batch_set_promotion_status_by_player_id_list($status, $player_id_list);
        $counter_list['set_promotion_by_player_id'] = $_counter_list;
        // $counter_list['_rows'] = $_rows;

        $this->utils->debug_log('dbg counter_list:', $counter_list);
        return $counter_list;

    } // EOF batch_set_promotion_status_from_csv
    //
    /**
     * Batch set promotion status with player_id list
     *
     * Command,
     * <code>
     * sudo /bin/bash admin/shell/command.sh batch_set_promotion_status_by_player_id_list 'enable' '16807_16972' > ./logs/command_batch_set_promotion_status_by_player_id_list &
     * </code>
     *
     * @param string $status
     * @param string|array $player_id_list
     * @param string $splitStr When $player_id_list is string type, please use $splitStr to connect each player id.
     * @return array $counter_list
     */
    public function batch_set_promotion_status_by_player_id_list($status = 'disable', $player_id_list = [], $splitStr = '_'){
        $this->CI->load->model(['player_model']);
        $this->CI->load->library(['player_manager']);

        // BSPSS = batch_set_promotion_status_settings
        $BSPSS = $this->utils->getConfig('batch_set_promotion_status_settings');

        if(is_string($player_id_list)){
            // string convert to array
            $player_id_list = explode($splitStr, $player_id_list);
        }

        $counter_list = [];
        $counter_list['totalOfPlayerIdList'] = is_array($player_id_list)? count($player_id_list): 0;
        $counter_list['issueInAlreadyEnabled'] = 0;
        $counter_list['issueInAlreadyDisabled'] = 0;
        $counter_list['issueInStatusUnexpected'] = 0;
        $counter_list['issueInOtherIssues'] = 0;
        $counter_list['issueInException'] = 0;
        $counter_list['doneInUpdatedPlayer'] = 0;
        $counter_list['issueInUpdatedPlayer'] = 0;
        $counter_list['ignoreByAlready'] = 0;
        $counter_list['issueInUpdatedPlayerIdList'] = []; // updated failed player
        $counter_list['issueInExceptionPlayerIdList'] = [];

        if( ! empty($player_id_list) ){
            $isTargetStatus2enable = null;
            if(strtolower($status) == 'enable'){
                $isTargetStatus2enable = true;
            }else if(strtolower($status) == 'disable'){
                $isTargetStatus2enable = false;
            }

            $updatedBy = 'command'; // 'Batch Update';
            foreach($player_id_list as $indexNumber => $_playerId){
                try {
                    $this->player_model->startTrans();
                    $_isDisabled = $this->player_model->isDisabledPromotion($_playerId);
                    $_rlt = -99;

                    $forceFalseInRlt = false;
                    if( ! empty($BSPSS['PlayerIdFailedInEnablePromotion'])
                        && in_array($_playerId, $BSPSS['PlayerIdFailedInEnablePromotion'])
                        && $isTargetStatus2enable === true
                    ){
                        // for Test Failed in enablePromotionByPlayerId().
                        $forceFalseInRlt = true;
                    }
                    if(  ! empty($BSPSS['PlayerIdFailedInDisablePromotion'])
                        && in_array($_playerId, $BSPSS['PlayerIdFailedInDisablePromotion'])
                        && $isTargetStatus2enable === false
                    ){
                        // for Test Failed in enablePromotionByPlayerId().
                        $forceFalseInRlt = true;
                    }
                    if( ! empty($BSPSS['PlayerIdFailedInException'])
                        && in_array($_playerId, $BSPSS['PlayerIdFailedInException'])
                    ){
                        throw new Exception('For Test Expected Exception By playerId='. $_playerId);
                    }

                    if($forceFalseInRlt){
                        $_rlt = false;
                    }else if( $isTargetStatus2enable === true){
                        if( $_isDisabled){
                            $updateType = lang("Enable Promotion");
                            $_rlt = $this->player_model->enablePromotionByPlayerId($_playerId);
                            if( ! empty($_rlt) ){
                                $_rlt = true;
                                // $counter_list['doneInUpdatedPlayer']++;
                            }else{
                                $_rlt = false;
                                // $counter_list['issueInUpdatedPlayer']++;
                                // array_push($counter_list['issueInUpdatedPlayerIdList'], $_playerId);
                            }
                        }else{
                            $_rlt = null;
                            // the player already has been enabled.
                            $counter_list['issueInAlreadyEnabled']++;
                        }
                    // EOF if( strtolower($status) == 'enable' ){...
                    }else if($isTargetStatus2enable  === false){
                        if( $_isDisabled){
                            $_rlt = null;
                            // the player already has been disabled.
                            $counter_list['issueInAlreadyDisabled']++;
                        }else{
                            $updateType = lang("Disable Promotion");
                            $_rlt = $this->player_model->disablePromotionByPlayerId($_playerId);
                            if( ! empty($_rlt) ){
                                $_rlt = true;
                                // $counter_list['doneInUpdatedPlayer']++;
                            }else{
                                $_rlt = false;
                                // $counter_list['issueInUpdatedPlayer']++;
                                // array_push($counter_list['issueInUpdatedPlayerIdList'], $_playerId);
                            }
                        }
                    // EOF }else if(strtolower($status) == 'disable'){
                    }else{
                        // $status param is the unexpected value
                        $_rlt = -1;
                    }
                    $this->utils->debug_log('dbg _playerId:', $_playerId, '_rlt:', $_rlt);
                    if( $_rlt === true){
                        // $this->savePlayerUpdateLog($playerId, $updateType, $this->authentication->getUsername());
                        // Player_Management::savePlayerUpdateLog()
                        $today = null;
                        $data = array(
                            'playerId' => $_playerId,
                            'changes' => $updateType,
                            'createdOn' => empty($today) ? date('Y-m-d H:i:s') : $today,
                            'operator' => $updatedBy,
                        );
                        $this->player_manager->addPlayerInfoUpdates($_playerId, $data);

                        $counter_list['doneInUpdatedPlayer']++;
                        $this->utils->debug_log('collect players Done with _playerId:', $_playerId);
                    }else if( $_rlt === false ){
                        $counter_list['issueInUpdatedPlayer']++;
                        array_push($counter_list['issueInUpdatedPlayerIdList'], $_playerId);

                    }else if( is_null($_rlt) ){
                        $counter_list['ignoreByAlready']++;
                        $this->utils->debug_log('collect players IgnoreByAlready with _playerId:', $_playerId);
                    }else if( $_rlt == -1) {
                        $counter_list['issueInStatusUnexpected']++;
                    }else if( $_rlt == -99) {
                        $counter_list['issueInOtherIssues']++;
                    }
                    $this->utils->debug_log('check progress with indexNumber:', $indexNumber,'total:', $counter_list['totalOfPlayerIdList'], 'currPlayerId:', $_playerId);
                    $this->player_model->endTransWithSucc();
                }catch (Exception $e) {
                    array_push($counter_list['issueInExceptionPlayerIdList'], $_playerId);
                    $counter_list['issueInException']++;
                    $this->player_model->rollbackTrans();  // Trans ISSUE END
			        $this->utils->error_log('Exception:', $e);
                } // EOF try {...

            } // EOF foreach($_rows as $indexNumber => $row){...
        } // EOF if( ! empty($player_id_list) ){...

        // for player_id too many
        $this->utils->debug_log('batch_set_promotion_status_by_player_id_list.issueInUpdatedPlayerIdList:', $counter_list['issueInUpdatedPlayerIdList']);
        if( count($counter_list['issueInUpdatedPlayerIdList']) > 50 ){
            $counter_list['issueInUpdatedPlayerIdList'] = 'Too many player, Please Look For the Keyword,"batch_set_promotion_status_by_player_id_list.issueInUpdatedPlayerIdList" .';
        }
        $this->utils->debug_log('batch_set_promotion_status_by_player_id_list.issueInExceptionPlayerIdList:', $counter_list['issueInUpdatedPlayerIdList']);
        if( count($counter_list['issueInExceptionPlayerIdList']) > 50 ){
            $counter_list['issueInExceptionPlayerIdList'] = 'Too many player, Please Look For the Keyword,"batch_set_promotion_status_by_player_id_list.issueInExceptionPlayerIdList" .';
        }

        /// ckeck totals
        // totalOfPlayerIdList = doneInUpdatedPlayer+ ignoreByAlready+ issueInStatusUnexpected+ issueInOtherIssues+ issueInException
        $subTotal = 0;
        $subTotal += $counter_list['doneInUpdatedPlayer'];
        $subTotal += $counter_list['ignoreByAlready'];
        $subTotal += $counter_list['issueInStatusUnexpected'];
        $subTotal += $counter_list['issueInOtherIssues'];
        $subTotal += $counter_list['issueInException'];
        $subTotal += $counter_list['issueInUpdatedPlayer'];

        //
        // ignoreByAlready = issueInAlreadyEnabled+ issueInAlreadyDisabled
        $subTotalInAlready = 0;
        $subTotalInAlready += $counter_list['issueInAlreadyEnabled'];
        $subTotalInAlready += $counter_list['issueInAlreadyDisabled'];
        //
        $ckeckTotals = null;
        $return = [];
        if($counter_list['totalOfPlayerIdList'] !== $subTotal){
            $ckeckTotals = false;
            $return['msg'] = 'Completed, but verification failed in totalOfPlayerIdList, '. 'subTotal:'. $subTotal;
        }else if($counter_list['ignoreByAlready'] !== $subTotalInAlready){
            $ckeckTotals = false;
            $return['msg'] = 'Completed, but verification failed in ignoreByAlready, '. 'subTotal:'. $subTotalInAlready;
        }else{
            $ckeckTotals = true;
            $return['msg'] = 'Done';
        }
        $return['bool'] = $ckeckTotals;
        $return['counter_list'] = $counter_list;
        $this->utils->debug_log('batch_set_promotion_status_by_player_id_list.return:', $return);

        return $return;
    } // EOF batch_set_promotion_status_by_player_id_list()


     /**
     * Batch set promotion status with player_id list
     *
     * Command,
     * <code>
     * sudo /bin/bash admin/shell/command.sh batch_set_cashback_status_by_player_id_list 'enable' '16807_16972' > ./logs/command_batch_set_cashback_status_by_player_id_list &
     * </code>
     *
     * @param string $status
     * @param string|array $player_id_list
     * @param string $splitStr When $player_id_list is string type, please use $splitStr to connect each player id.
     * @return array $counter_list
     */
    public function batch_set_cashback_status_by_player_id_list($status = 'disable', $player_id_list = [], $splitStr = '_'){
        $this->CI->load->model(['player_model']);
        $this->CI->load->library(['player_manager']);

        // BSPSS = batch_set_cashback_status_settings
        $BSPSS = $this->utils->getConfig('batch_set_cashback_status_settings');

        if(is_string($player_id_list)){
            // string convert to array
            $player_id_list = explode($splitStr, $player_id_list);
        }

        $counter_list = [];
        $counter_list['totalOfPlayerIdList'] = is_array($player_id_list)? count($player_id_list): 0;
        $counter_list['issueInAlreadyEnabled'] = 0;
        $counter_list['issueInAlreadyDisabled'] = 0;
        $counter_list['issueInStatusUnexpected'] = 0;
        $counter_list['issueInOtherIssues'] = 0;
        $counter_list['issueInException'] = 0;
        $counter_list['doneInUpdatedPlayer'] = 0;
        $counter_list['issueInUpdatedPlayer'] = 0;
        $counter_list['ignoreByAlready'] = 0;
        $counter_list['issueInUpdatedPlayerIdList'] = []; // updated failed player
        $counter_list['issueInExceptionPlayerIdList'] = [];

        if( ! empty($player_id_list) ){
            $isTargetStatus2enable = null;
            if(strtolower($status) == 'enable'){
                $isTargetStatus2enable = true;
            }else if(strtolower($status) == 'disable'){
                $isTargetStatus2enable = false;
            }

            $updatedBy = 'command'; // 'Batch Update';
            foreach($player_id_list as $indexNumber => $_playerId){
                try {
                    $this->player_model->startTrans();
                    $_isDisabled = $this->player_model->isDisabledCashback($_playerId);
                    $_rlt = -99;

                    $forceFalseInRlt = false;
                    if( ! empty($BSPSS['PlayerIdFailedInEnablePromotion'])
                        && in_array($_playerId, $BSPSS['PlayerIdFailedInEnablePromotion'])
                        && $isTargetStatus2enable === true
                    ){
                        // for Test Failed in enablePromotionByPlayerId().
                        $forceFalseInRlt = true;
                    }
                    if(  ! empty($BSPSS['PlayerIdFailedInDisablePromotion'])
                        && in_array($_playerId, $BSPSS['PlayerIdFailedInDisablePromotion'])
                        && $isTargetStatus2enable === false
                    ){
                        // for Test Failed in enablePromotionByPlayerId().
                        $forceFalseInRlt = true;
                    }
                    if( ! empty($BSPSS['PlayerIdFailedInException'])
                        && in_array($_playerId, $BSPSS['PlayerIdFailedInException'])
                    ){
                        throw new Exception('For Test Expected Exception By playerId='. $_playerId);
                    }

                    if($forceFalseInRlt){
                        $_rlt = false;
                    }else if( $isTargetStatus2enable === true){
                        if( $_isDisabled){
                            $updateType = "Enable Cashback";
                            $_rlt = $this->player_model->enableCashbackByPlayerId($_playerId);
                            if( ! empty($_rlt) ){
                                $_rlt = true;
                                // $counter_list['doneInUpdatedPlayer']++;
                            }else{
                                $_rlt = false;
                                // $counter_list['issueInUpdatedPlayer']++;
                                // array_push($counter_list['issueInUpdatedPlayerIdList'], $_playerId);
                            }
                        }else{
                            $_rlt = null;
                            // the player already has been enabled.
                            $counter_list['issueInAlreadyEnabled']++;
                        }
                    // EOF if( strtolower($status) == 'enable' ){...
                    }else if($isTargetStatus2enable  === false){
                        if( $_isDisabled){
                            $_rlt = null;
                            // the player already has been disabled.
                            $counter_list['issueInAlreadyDisabled']++;
                        }else{
                            $updateType = "Disable Cashback";
                            $_rlt = $this->player_model->disableCashbackByPlayerId($_playerId);
                            if( ! empty($_rlt) ){
                                $_rlt = true;
                                // $counter_list['doneInUpdatedPlayer']++;
                            }else{
                                $_rlt = false;
                                // $counter_list['issueInUpdatedPlayer']++;
                                // array_push($counter_list['issueInUpdatedPlayerIdList'], $_playerId);
                            }
                        }
                    // EOF }else if(strtolower($status) == 'disable'){
                    }else{
                        // $status param is the unexpected value
                        $_rlt = -1;
                    }
                    $this->utils->debug_log('dbg _playerId:', $_playerId, '_rlt:', $_rlt);
                    if( $_rlt === true){
                        // $this->savePlayerUpdateLog($playerId, $updateType, $this->authentication->getUsername());
                        // Player_Management::savePlayerUpdateLog()
                        $today = null;
                        $data = array(
                            'playerId' => $_playerId,
                            'changes' => $updateType,
                            'createdOn' => empty($today) ? date('Y-m-d H:i:s') : $today,
                            'operator' => $updatedBy,
                        );
                        $this->player_manager->addPlayerInfoUpdates($_playerId, $data);

                        $counter_list['doneInUpdatedPlayer']++;
                        $this->utils->debug_log('collect players Done with _playerId:', $_playerId);
                    }else if( $_rlt === false ){
                        $counter_list['issueInUpdatedPlayer']++;
                        array_push($counter_list['issueInUpdatedPlayerIdList'], $_playerId);

                    }else if( is_null($_rlt) ){
                        $counter_list['ignoreByAlready']++;
                        $this->utils->debug_log('collect players IgnoreByAlready with _playerId:', $_playerId);
                    }else if( $_rlt == -1) {
                        $counter_list['issueInStatusUnexpected']++;
                    }else if( $_rlt == -99) {
                        $counter_list['issueInOtherIssues']++;
                    }
                    $this->utils->debug_log('check progress with indexNumber:', $indexNumber,'total:', $counter_list['totalOfPlayerIdList'], 'currPlayerId:', $_playerId);
                    $this->player_model->endTransWithSucc();
                }catch (Exception $e) {
                    array_push($counter_list['issueInExceptionPlayerIdList'], $_playerId);
                    $counter_list['issueInException']++;
                    $this->player_model->rollbackTrans();  // Trans ISSUE END
			        $this->utils->error_log('Exception:', $e);
                } // EOF try {...

            } // EOF foreach($_rows as $indexNumber => $row){...
        } // EOF if( ! empty($player_id_list) ){...

        // for player_id too many
        $this->utils->debug_log('batch_set_cashback_status_by_player_id_list.issueInUpdatedPlayerIdList:', $counter_list['issueInUpdatedPlayerIdList']);
        if( count($counter_list['issueInUpdatedPlayerIdList']) > 50 ){
            $counter_list['issueInUpdatedPlayerIdList'] = 'Too many player, Please Look For the Keyword,"batch_set_cashback_status_by_player_id_list.issueInUpdatedPlayerIdList" .';
        }
        $this->utils->debug_log('batch_set_cashback_status_by_player_id_list.issueInExceptionPlayerIdList:', $counter_list['issueInUpdatedPlayerIdList']);
        if( count($counter_list['issueInExceptionPlayerIdList']) > 50 ){
            $counter_list['issueInExceptionPlayerIdList'] = 'Too many player, Please Look For the Keyword,"batch_set_cashback_status_by_player_id_list.issueInExceptionPlayerIdList" .';
        }

        /// ckeck totals
        // totalOfPlayerIdList = doneInUpdatedPlayer+ ignoreByAlready+ issueInStatusUnexpected+ issueInOtherIssues+ issueInException
        $subTotal = 0;
        $subTotal += $counter_list['doneInUpdatedPlayer'];
        $subTotal += $counter_list['ignoreByAlready'];
        $subTotal += $counter_list['issueInStatusUnexpected'];
        $subTotal += $counter_list['issueInOtherIssues'];
        $subTotal += $counter_list['issueInException'];
        $subTotal += $counter_list['issueInUpdatedPlayer'];

        //
        // ignoreByAlready = issueInAlreadyEnabled+ issueInAlreadyDisabled
        $subTotalInAlready = 0;
        $subTotalInAlready += $counter_list['issueInAlreadyEnabled'];
        $subTotalInAlready += $counter_list['issueInAlreadyDisabled'];
        //
        $ckeckTotals = null;
        $return = [];
        if($counter_list['totalOfPlayerIdList'] !== $subTotal){
            $ckeckTotals = false;
            $return['msg'] = 'Completed, but verification failed in totalOfPlayerIdList, '. 'subTotal:'. $subTotal;
        }else if($counter_list['ignoreByAlready'] !== $subTotalInAlready){
            $ckeckTotals = false;
            $return['msg'] = 'Completed, but verification failed in ignoreByAlready, '. 'subTotal:'. $subTotalInAlready;
        }else{
            $ckeckTotals = true;
            $return['msg'] = 'Done';
        }
        $return['bool'] = $ckeckTotals;
        $return['counter_list'] = $counter_list;
        $this->utils->debug_log('batch_set_cashback_status_by_player_id_list.return:', $return);

        return $return;
    } // EOF batch_set_cashback_status_by_player_id_list()

	public function batch_adjust_cashback_flag($onlyPrintLog='false'){

		$onlyPrintLog= $onlyPrintLog == 'true';

		$this->load->model(['player_promo', 'player_model', 'transactions']);

		$rows=$this->player_promo->getDisableCashbackPlayers();

		if(empty($rows)){
			$now=$this->utils->getNowForMysql();
			foreach ($rows as $row) {
				$playerId=$row['playerId'];
				$startDate=$row['dateProcessed'];

				$this->utils->debug_log('query deposit/withdraw :'.$playerId, $startDate, $now);

				list($totalDeposit, $totalWithdrawal, $totalBonus, $totalCashback)=
					$this->transactions->getTotalDepositWithdrawalBonusCashbackByPlayers(
						$playerId, $startDate, $now);

				if($totalDeposit<=0 && $totalWithdrawal<=0){
					$rlt=false;
					$this->utils->debug_log('disableCashbackByPlayerId player:'.$playerId);
					if(!$onlyPrintLog){
						$rlt=$this->player_model->disableCashbackByPlayerId($playerId);
					}

					$this->utils->debug_log('disableCashbackByPlayerId', $rlt);
				}
			}
		}
	}

    public function reset_0_game_shares_on_aff(){
    	//load all affiliate , if game shares is 0, set it empty
    	$this->load->model(['affiliatemodel']);
    	$this->affiliatemodel->reset_game_shares();

    }

 //    public function batch_delete_aff_for_kgvipcn($dry_run='true'){

	// 	$dry_run=$dry_run=='true';
	// 	$lastLoginDate='2017-10-30 23:59:59';

	// 	$this->utils->debug_log('batch_delete_aff_for_kgvipcn dry run', $dry_run);

	// 	$this->utils->loopRawRows(false, function() use ($lastLoginDate){

	// 		$this->db->select('affiliateId, username, lastLogin')->from('affiliates')->where('lastLogin <=', $lastLoginDate)
	// 			->where('deleted_at is null', null, false);

	// 		return null;

	// 	}, function($row) use($dry_run){

	// 		$success=false;
	// 		$id=$row['affiliateId'];
	// 		$sql="update affiliates set deleted_at='".$this->utils->getNowForMysql()."' where affiliateId=".$id;

	// 		if(!$dry_run){
	// 			//update deleted_at
	// 			$success=$this->utils->executeSqlOnRaw($sql);

	// 			$this->utils->debug_log($sql, $success);
	// 		}else{
	// 			$this->utils->debug_log('dry run', $sql);
	// 			$success=true;
	// 		}

	// 		return $success;

	// 	});

	// }

	public function show_player_password_history($username) {
		$this->db->select('player_password_history.*')->from('player_password_history')
			->join('player', 'player.playerId=player_password_history.player_id')
		    ->where('player.username', $username);
		$qry = $this->db->get();
		$rows = $qry->result_array();
		// $password = '';
		if (!empty($rows)) {
			$this->load->library(array('salt'));
			foreach ($rows as $row) {
				$current_password = $this->salt->decrypt($row['current_password'], $this->getDeskeyOG());
				$new_password = $this->salt->decrypt($row['new_password'], $this->getDeskeyOG());
				$this->utils->debug_log($row['updated_at']." change password: " . $current_password . " to ".$new_password);
			}

		}else{

			$this->utils->debug_log("not found " . $username);

		}

	}

	public function query_api_balance($username, $apiId) {
		$this->load->model(array('game_provider_auth', 'player_model'));
		$api = $this->loadApi($apiId);
		$playerId = $this->player_model->getPlayerIdByUsername($username);
		if (!empty($playerId)) {
			// $isRegisteredFlag = $this->game_provider_auth->isRegisterd($playerId, PT_API);
			// if ($isRegisteredFlag) {
				$rlt = $api->queryPlayerBalance($username);
				$this->utils->debug_log('rlt of '.$username.' in '.$apiId, $rlt);
			// }
		}
	}


	public function updateTransactionSecureIdsPerDay($fromdate=null,$todate=null){
		$begin = new DateTime($fromdate);
		$end   = new DateTime($todate);
		$this->load->model(array('transactions', 'sale_order','wallet_model'));
		$transactionTypes = [
			transactions::DEPOSIT,
			transactions::WITHDRAWAL,
			transactions::TRANSFER_TO_SUB_WALLET_FROM_MAIN_WALLET,
			transactions::TRANSFER_FROM_SUB_WALLET_TO_MAIN_WALLET
		];
		$fields = ['id','transaction_type','external_transaction_id'];
		if(!empty($fromdate) && !empty($todate) ){
			for($i = $begin; $i <= $end; $i->modify('+1 day')){
				$day = $i->format("Y-m-d");
				$transactions = $this->transactions->getTransactionsByTransactionTypesAndDay($transactionTypes,$fields,$day);
				foreach ($transactions as  $transaction) {
					$secure_request_id = null;
					switch ((int)$transaction['transaction_type']) {
						case transactions::DEPOSIT:
						if(isset($transaction['id'])){
							$secure_request_id= $this->sale_order->getSecureIdByTransactionId($transaction['id']);
						}
						break;
						case transactions::WITHDRAWAL:
						if(isset($transaction['id'])){
							$secure_request_id = $this->wallet_model->getTransactionCodeByTransactionId($transaction['id']);
						}
						break;
						case transactions::TRANSFER_FROM_SUB_WALLET_TO_MAIN_WALLET:
						case transactions::TRANSFER_TO_SUB_WALLET_FROM_MAIN_WALLET:
						if(isset($transaction['external_transaction_id'])){
							$secure_request_id = $this->wallet_model->getSecureIdByExternalTransactionId($transaction['external_transaction_id']);
						}
						break;
						default:
							# code...
						break;
					}
					$this->transactions->updateRequestSecureId($transaction['id'], $secure_request_id);
					$this->utils->debug_log('updateRequestSecureId','day', $day, 'secure_id', $secure_request_id, 'transaction_type', $transaction['transaction_type'],'transaction_id', $transaction['id']);
				}
			}
		}
	}

	public function reset_player_password_without_game($playerId, $newPassword){
		$this->load->model(array('player_model'));
		$this->load->library('player_manager');
		$player = $this->player_manager->getPlayerById($playerId);
		$data = array(
			'password' => $newPassword,
		);
		// save player password history
		$this->player_model->insertPasswordHistory($playerId, Player_model::RESET_PASSWORD, $this->utils->encodePassword($newPassword));
		$this->player_manager->resetPassword($data, $playerId);
		$this->utils->debug_log('rlt of player:'.$player['username'].' and new password: '.$newPassword);
	}

    public function decode_vr($merchant_key) {
    	// $merchant_key='';
    	$base64_data="S9lE+agYCALfvvlIHh2CrLOpS+0SbuWTf5f850WwK0zqA1rHNn62rk2lIuCJjfx6JGyv4XNnh4LFFKeO8GX5WZEu28Ib1/IVYrjhXT2uK/fBPWjXTjFtborQqkpI117Evn4Il0iowaXqLV0VnRg5kIkJvgPkig6z+MYxc8v5cLeOYxllLv5AqtrX4YG0OkwdZ2ooVwa7ORruBjOyLqp5uw==";
        $data = base64_decode($base64_data);

        //Decrypt data
        $plain_data = @mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $merchant_key, $data, MCRYPT_MODE_ECB);

        //Remove US-ASCII control character
        $plain_data = trim($plain_data, "\x00..\x1F");

        $this->utils->debug_log($plain_data);
    }

	public function batch_sub_balance($balance_csv_file, $print_each_row="true", $dry_run="true", $max_count="10", $ignore_first_row="true"){

		$this->load->model(['player_model', 'transactions']);

		$debug_info=[
			'ignore_first_row'=>$ignore_first_row=='true',
			'print_each_row'=>$print_each_row=='true',
			'max_count'=>$max_count, 'dry_run'=>$dry_run=='true'];
		$config=['debug_info'=>$debug_info, 'ignore_first_row'=>$ignore_first_row=='true'];

		$result_info=[
			'success'=>false,
			'cnt'=>0,
			'failedCnt'=>0,
			'successCnt'=>0,
			'failed_username'=>[], // username=>failed message
			'username'=>[],
		];

		$balance_csv_file='/home/vagrant/Code/'.$balance_csv_file;

		$controller=$this;
		$cnt=0;
		$message=null;
		$result_info['success']=$this->utils->loopCSV($balance_csv_file, @$config['ignore_first_row'], $cnt, $message,
			function($cnt, $tmpData, &$stop_flag=false)
			use($controller, $config, &$result_info) {

			$debug_info=$config['debug_info'];

			if (@$debug_info['max_count'] && $cnt >= $debug_info['max_count']) {
				$this->utils->debug_log('stopped for debug');
				$stop_flag=true;
				return false;
			}

			if(@$debug_info['print_each_row']){
				$this->utils->debug_log('processing', $tmpData);
			}

			$next=true;

			$username=$tmpData[0];
			$minus_amount=round(doubleval($tmpData[1]), 2);

			if(empty($username) || $minus_amount<=0){

				$result_info['failedCnt']++;
				$result_info['failed_username'][]=$username;
				return $next;
			}

			$playerId=$this->player_model->getPlayerIdByUsername($username);

			if(!empty($playerId)){
				if (!@$debug_info['dry_run']) {

					$success=$controller->lockAndTransForPlayerBalance($playerId, function()
							use($minus_amount, $playerId, $controller, $username){

						$action_name='Minus balance';
						$wallet_name='';
						$adminUserId=1;
						$adjustment_type=Transactions::MANUAL_SUBTRACT_BALANCE;
						$note = sprintf('%s <b>%s</b> balance to <b>%s</b>',
									$action_name, number_format($minus_amount, 2), $username);

						$success=$controller->transactions->createAdjustmentTransaction($adjustment_type, $adminUserId, $playerId,
							$minus_amount, null, $note);

						return $success;
					});

				}else{

					$this->utils->debug_log('dry run...');
					$success=true;
				}
			}else{

				$success=false;

			}

			$result_info['cnt']++;
			if($success){

				$result_info['successCnt']++;
				// $result_info['username'][]=$username;
			}else{
				$result_info['failedCnt']++;
				$result_info['failed_username'][]=['username'=>$username, 'amount'=>$minus_amount];
			}

			return $next;

		});

		$this->utils->debug_log('minus balance',$result_info);

	}


	public function regenerate_gamelogs_transaction($game_platform_id='NULL', $fromdate='NULL', $todate='NULL', $step='1', $dry_run='false'){
		$fromdate = ($fromdate == 'NULL') ? null : $fromdate;
		$todate = ($todate == 'NULL') ? null : $todate;
		$game_platform_id = ($game_platform_id == 'NULL') ? null : $game_platform_id;
		if(empty($fromdate) || empty($todate) || empty($game_platform_id)){
			$this->utils->debug_log('Please provide some date!');
			return;
		}
		$dry_run=$dry_run=='true';
		$this->load->model(array('game_provider_auth','transactions','game_logs'));
		$transactions_model = $this->transactions;
		$game_logs_model = $this->game_logs;
		$game_provider_auth_model = $this->game_provider_auth;
		$step = '+'.$step.' hours';

		$this->utils->loopDateTimeStartEnd($fromdate, $todate, $step, function($from, $to, $step)
			use($game_platform_id, $transactions_model, $game_logs_model,$game_provider_auth_model, $dry_run){

				$from_str = $this->utils->formatDateTimeForMysql($from);
				$to_str = $this->utils->formatDateTimeForMysql($to);
				$rows = $transactions_model->getGamelogsTransactions($game_platform_id,$from_str,$to_str);
				$this->utils->debug_log('Found gamelogs trans :', count($rows), 'from', $from_str,'to', $to_str);
				if(!empty($rows)){
					foreach ($rows as $row) {
						$row->response_result_id = empty($response_result_id) ? '' : $response_result_id;
						$row->username = $game_provider_auth_model->getGameUsernameByPlayerId($row->player_id, $game_platform_id);
						$row->note= 'player ' . $row->player_id . ' ' . $row->username . ' change balance to ' . $row->after_balance . ' amount:' . $row->trans_amount . ' type:' . $row->trans_type ;
						$response_result_id = $transactions_model->getResponseResultIdBySecureId($row->request_secure_id);
						if(!empty($response_result_id )){
							if(!$game_logs_model->isResponseResultIdIfExist($response_result_id)){
	 			 	           //insert
								if(!$dry_run){
									//unset($row['request_secure_id']);
									$game_logs_model->insertGameTransaction( $game_platform_id, $row->player_id, $row->username, $row->after_balance, $row->trans_amount, $response_result_id, $row->trans_type, $row->start_at);
									$this->utils->debug_log('gamelogs trans inserted:',@$row);
								}else{
									$this->utils->debug_log('dry run only: ',@$row);
								}
							}
						}else{
							$this->utils->debug_log('response_result_id not found:  request_secure_id: ',@$row->request_secure_id, 'row', $row);
						}
					}
				}
				return true;
			});
	}

    public function regenerate_affiliate_static_report($aff_userName='NULL', $firstDate='NULL', $secondDate='NULL', $dry_run='false'){
        $this->load->model('affiliate_statistics_model');

        $firstDate = (strtoupper($firstDate) == 'NULL') ? null : $firstDate;
        $secondDate = (strtoupper($secondDate) == 'NULL') ? null : $secondDate;
        $aff_userName = (strtoupper($aff_userName) == 'NULL') ? null : $aff_userName;

        if(empty($firstDate) && empty($secondDate)){
            return $this->utils->debug_log('Please provide some date!');
        }

        $next_day = date("Y-m-d", time() + 86400);
        $dateTo = $next_day;
        if($firstDate == 'this_month'){
            $dateFrom = date('Y-m').'-01';
        }elseif($firstDate == 'last_month'){
            $dateFrom = date('Y-m', strtotime('-1 month')).'-01';
            $dateTo = date("Y-m".'-01');
        }elseif($firstDate == 'today'){
            $dateFrom = date('Y-m-d');
        }else if($firstDate=='yesterday'){
            $dateFrom = date('Y-m-d', strtotime('yesterday'));
            $dateTo = date('Y-m-d');
        }else{
            $dateFrom = $firstDate;
            $dateTo = date('Y-m-d', strtotime($secondDate.'+1 day'));
        }


        if(strtotime($dateFrom) <= strtotime($dateTo)){
            $date_start = new DateTime($dateFrom);
            $date_end = new DateTime($dateTo);

            $interval = DateInterval::createFromDateString('1 day');
            $period = new DatePeriod($date_start, $interval, $date_end);

            foreach ($period as $dt) {
                $dateTime_start = $dt->format("Y-m-d").' ' . '00:00:00';
                $dateTime_end = $dt->format("Y-m-d").' ' .  '23:59:59';

                $this->affiliate_statistics_model->generateStatistics($dateTime_start, $dateTime_end, $aff_userName);
            }
        }
        elseif(strtotime($dateFrom) < strtotime('2015-01-01') /*---minimum date---*/){
            $this->utils->debug_log('Invalid, minimum date 2015-01-01');
        }
        else{
            $this->utils->debug_log('Invalid dates!');
        }

        return true;
	}


	/**
	 * Fix Data in the related common_cashback_multiple_range
	 *
	 * CCMR = common_cashback_multiple_range
	 *
	 * sh command.sh fixDataCommon_cashback_multiple_range
	 * sudo /bin/bash admin/shell/command.sh fixDataCCMR > ./logs/command_fixDataCCMR &
	 * @return void
	 */
	public function fixDataCCMR(){
		$this->load->model(['player_model']);


		// // $this->player_model->startTrans();

		$anyid = random_string('numeric', 5);
		$add_prefix = false;
		$controller = $this;
		return $this->player_model->lockAndTrans(Utils::GLOBAL_LOCK_ACTION_SYSTEM_FEATURE, $anyid, function () use ( $controller ) {
			$issueCountInSettingCCMR = $controller->getIssueCountInSettingCCMR();
			$issueCountInRulesCCMR = $controller->getIssueCountInRulesCCMR();

			$controller->utils->debug_log('fixDataCCMR.1115.issueCountInSettingCCMR:', $issueCountInSettingCCMR );
			$controller->utils->debug_log('fixDataCCMR.1116.issueCountInRulesCCMR:', $issueCountInRulesCCMR );


			// update the Default in templates
			$template = 'Default';
			$controller->scriptAdjustTemplateData($template);

			// update the game_tag in templates
			$template = 'game_tag';
			$controller->scriptAdjustTemplateData($template);


			$isDoMultiRulesDataCheck = true;
			if($isDoMultiRulesDataCheck){
				$isMultiRulesDataCheck = null;
				/// 取得多筆設定的記錄 - step 1
				$multiRulesDataInIssueList = $controller->getMultiRulesDataInIssue();
				$controller->utils->debug_log('fixDataCCMR.1131.multiRulesDataInIssueList:', $multiRulesDataInIssueList);
			}

			// common_cashback_multiple_range_rules
			$table = 'common_cashback_multiple_range_rules';
			$thePKField = 'cb_mr_rule_id';
			$theIssueData4Rules = $controller->getIssueRulesDataCCMR($table, $thePKField);
			$controller->fixIssueData4RulesCCMR( $theIssueData4Rules, $table, $thePKField);


			// common_cashback_multiple_range_settings
			$table = 'common_cashback_multiple_range_settings';
			$thePKField = 'cb_mr_sid';
			$theIssueData4Settings = $controller->getIssueDataCCMR($table, $thePKField);
			$controller->fixIssueData4SettingsCCMR( $theIssueData4Settings, $table, $thePKField);


			$afterFixedCountInSettingCCMR = $controller->getIssueCountInSettingCCMR();
			$afterFixedCountInRulesCCMR = $controller->getIssueCountInRulesCCMR();

			$controller->utils->debug_log('fixDataCCMR.1147.afterFixedCountInSettingCCMR:', $afterFixedCountInSettingCCMR );
			$controller->utils->debug_log('fixDataCCMR.1148.afterFixedCountInRulesCCMR:', $afterFixedCountInRulesCCMR );

			if($isDoMultiRulesDataCheck){
				// Get the Ok data of the Default tpl.
				$tplId4Default = null;
				$template_name = 'Default';
				$active = '1';
				$order_mode = 'asc';
				$theTemplate = $this->getLatestByTemplateName($template_name, $active, $order_mode);
				$tplId4Default = $theTemplate['cb_mr_tpl_id'];
				// Get the Ok data of the game_tag tpl.
				$tplId4GameTag = null;
				$template_name = 'game_tag';
				$active = '1';
				$order_mode = 'asc';
				$theTemplate = $this->getLatestByTemplateName($template_name, $active, $order_mode);
				$tplId4GameTag = $theTemplate['cb_mr_tpl_id'];
				/// 取得多筆設定的記錄 - step 2
				$tplId4Default = 1;
				$tplId4GameTag = 2;
				if( ! empty($multiRulesDataInIssueList['list']) ){
					$multiRulesDataInIssue = $multiRulesDataInIssueList['list'][0];
					// $tpl_id = $this->getTplIdByTypeCCMR( $theIssueData['type'], $tplId4Default, $tplId4GameTag );
					$rlt = $controller->getMultiRulesDataGroupBy( $controller->getTplIdByTypeCCMR( $multiRulesDataInIssue['type'], $tplId4Default, $tplId4GameTag )
														, $multiRulesDataInIssue['type']
														, $multiRulesDataInIssue['type_map_id']);
					$controller->utils->debug_log('fixDataCCMR.1131.rlt:', $rlt); // $rlt['list'][0]['rule_counter']

					$isMultiRulesDataCheck = ($rlt['list'][0]['rule_counter'] == $multiRulesDataInIssue['rule_counter'])? true: false;
				}

				if(! $isMultiRulesDataCheck|| true){
					$controller->utils->debug_log('fixDataCCMR.1161.isMultiRulesDataCheck:', $isMultiRulesDataCheck );
				}
			}


			$isIssueCountZeroInSettingAfterFixed = ($afterFixedCountInSettingCCMR['issue_settings']['count'] == 0)? true: false ;
			$isIssueCountZeroInRulesAfterFixed = ($afterFixedCountInRulesCCMR['issue_rules']['count'] == 0)? true: false;

			$isEqualCountInSetting = ($afterFixedCountInSettingCCMR['real_settings']['count'] == $issueCountInSettingCCMR['real_settings']['count'])? true : false;
			$isEqualCountInRules = ($afterFixedCountInRulesCCMR['real_rules']['count'] == $issueCountInRulesCCMR['real_rules']['count'])? true : false;

			if(! $isIssueCountZeroInSettingAfterFixed|| true){
				$controller->utils->debug_log('fixDataCCMR.1161.isIssueCountZeroInSettingAfterFixed:', $isIssueCountZeroInSettingAfterFixed );
			}

			if(! $isIssueCountZeroInRulesAfterFixed|| true){
				$controller->utils->debug_log('fixDataCCMR.1161.isIssueCountZeroInRulesAfterFixed:', $isIssueCountZeroInRulesAfterFixed );
			}

			if(! $isEqualCountInSetting|| true){
				$controller->utils->debug_log('fixDataCCMR.1161.isEqualCountInSetting:', $isEqualCountInSetting );
			}

			if(! $isEqualCountInRules|| true){
				$controller->utils->debug_log('fixDataCCMR.1161.isEqualCountInRules:', $isEqualCountInRules );
			}

			// $isEqualCountInRules = false; // for test, remove it before git push

			if( $isIssueCountZeroInSettingAfterFixed
				&& $isIssueCountZeroInRulesAfterFixed
				&& $isEqualCountInSetting
				&& $isEqualCountInRules
			){
				return true;
			}

			return false;
		}, $add_prefix);


		// // $this->player_model->endTrans();


	} // EOF fixDataCCMR


	public function getIssueCountInSettingCCMR(){
		$return = [];

		$return['issue_settings']['count'] = 0;
		$return['issue_settings']['last_query'] = '';
		// Check the issue settings data count
		$sql = <<<EOF
		SELECT count( * ) as issue_settings_count
FROM (
	SELECT max(cb_mr_sid) as max_cb_mr_dataid, count(cb_mr_sid) as counter, type,	type_map_id
	FROM `common_cashback_multiple_range_settings`
	Group by type,	type_map_id
)as tmp
WHERE counter > 1;
EOF;
		$sql = $this->removeNoteInSql($sql);
		$q = $this->db->query($sql);
		$last_query = $this->db->last_query();
		$rows = $q->result_array();
		$result_count = $q->num_rows();
		$q->free_result();
		unset($q);
		$this->utils->debug_log('getIssueCountInSettingCCMR.1167.last_query:', $last_query );
		$return['issue_settings']['last_query'] = $last_query;
		if( !empty($rows) ){
			$return['issue_settings']['count'] = $rows[0]['issue_settings_count'];
		}

		$return['real_settings']['count'] = 0;
		$return['real_settings']['last_query'] = '';
		// Check the real settings data count
		$sql =<<<EOF
SELECT count( * ) as real_settings_count
	FROM (
		SELECT count(cb_mr_sid) as counter, type,	type_map_id
		FROM `common_cashback_multiple_range_settings`
		Group by type,	type_map_id
		ORDER BY count(cb_mr_sid) DESC
	) as tmp;
	-- 5956 rows => og.local
EOF;
		$sql = $this->removeNoteInSql($sql);
		$q = $this->db->query($sql);
		$last_query = $this->db->last_query();
		$rows = $q->result_array();
		$result_count = $q->num_rows();
		$q->free_result();
		unset($q);
		$this->utils->debug_log('getIssueCountInSettingCCMR.1198.last_query:', $last_query );
		$return['real_settings']['last_query'] = $last_query;
		if( !empty($rows) ){
			$return['real_settings']['count'] = $rows[0]['real_settings_count'];
		}

		// Check game_platform for single-data


		return $return;
	} //EOF getIssueCountInSettingCCMR

	public function getIssueCountInRulesCCMR(){
		$return = [];
		$return['issue_rules']['count'] = 0;
		$return['issue_rules']['last_query'] = '';
		$sql = <<<EOF
		SELECT count( * ) as issue_rules_count
FROM (
	SELECT *
	, count(cb_mr_rule_id) as counter
	, max(tpl_id) as max_tpl_id
	, max(cb_mr_rule_id) as max_cb_mr_data_id -- This data just detect the result of the issue data after patched.
	FROM (
			SELECT count(cb_mr_rule_id) as rule_counter
			, cb_mr_rule_id
			, tpl_id
			, type
			, type_map_id
			FROM `common_cashback_multiple_range_rules`
			Group by tpl_id, type, type_map_id
	)as tmpInner
	group by type, type_map_id
)as tmp
WHERE counter > 1;
EOF;
		$sql = $this->removeNoteInSql($sql);
		$q = $this->db->query($sql);
		$last_query = $this->db->last_query();
		$rows = $q->result_array();
		$result_count = $q->num_rows();
		$q->free_result();
		unset($q);
		$this->utils->debug_log('getIssueCountInRulesCCMR.1206.last_query:', $last_query );
		$return['issue_rules']['last_query'] = $last_query;
		if( !empty($rows) ){
			$return['issue_rules']['count'] = $rows[0]['issue_rules_count'];
		}

		$return['real_rules']['count'] = 0;
		$return['real_rules']['last_query'] = '';
		$sql = <<<EOF
		SELECT count( * ) as real_rules_count -- 真實的幾筆設定
		FROM (
			SELECT *
			, count(cb_mr_rule_id) as counter -- 同一設定，會有幾筆設定集，大於 1 表示問題記錄
			, max(tpl_id) as max_tpl_id -- 最新的 tpl_id
			FROM (
					SELECT count(cb_mr_rule_id) as rule_counter -- 同一設定集，會有幾筆，可能有多筆
					, cb_mr_rule_id
					, tpl_id
					, type
					, type_map_id
					FROM `common_cashback_multiple_range_rules`
					Group by tpl_id, type, type_map_id
			)as tmpInner
			group by type, type_map_id
		) as tmp;
		-- 65 rows => og.local -> 65
EOF;
		$sql = $this->removeNoteInSql($sql);
		$q = $this->db->query($sql);
		$last_query = $this->db->last_query();
		$rows = $q->result_array();
		$result_count = $q->num_rows();
		$q->free_result();
		unset($q);
		$this->utils->debug_log('getIssueCountInRulesCCMR.1271.last_query:', $last_query );
		$return['real_rules']['last_query'] = $last_query;
		if( !empty($rows) ){
			$return['real_rules']['count'] = $rows[0]['real_rules_count'];
		}

		return $return;
	}// EOF getIssueCountInRulesCCMR


	public function getMultiRulesDataInIssue(){
		$sql = <<<EOF
		SELECT *
		, count(cb_mr_rule_id) as counter
		, max(tpl_id) as max_tpl_id
		, max(cb_mr_rule_id) as max_cb_mr_data_id -- This data just detect the result of the issue data after patched.
		FROM (
				SELECT count(cb_mr_rule_id) as rule_counter
				, cb_mr_rule_id
				, tpl_id
				, type
				, type_map_id
				FROM `common_cashback_multiple_range_rules`
				Group by tpl_id, type, type_map_id
		)as tmpInner
		group by type, type_map_id
EOF;
		$sql = $this->removeNoteInSql($sql);
		$q = $this->db->query($sql);
		$last_query = $this->db->last_query();
		$rows = $q->result_array();
		$result_count = $q->num_rows();
		$q->free_result();
		unset($q);
		// $this->utils->debug_log('getIssueCountInRulesCCMR.1271.last_query:', $last_query );
		// $return['last_query'] = $last_query;
		$issueList = [];
		if( !empty($rows) ){
			foreach($rows as $indexNumber => $row){
				if($row['counter'] > 1 ){
					$issueList[] = $row;
				}
			} // EOF foreach($rows as $indexNumber => $row){
		}

		$theMultiRulesDataInIssueList = [];
		if( !empty($issueList) ){
			foreach($issueList as $indexNumber => $issueData){
				$rlt = $this->getMultiRulesDataGroupBy($issueData['max_tpl_id'], $issueData['type'], $issueData['type_map_id']);
				if(!empty($rlt)){
					foreach($rlt['list'] as $indexNumber => $row){
						if($row['rule_counter'] > 1){
							$theMultiRulesDataInIssueList[] = $row;
						}
					}
				}
			}
		}

		$return['list'] = $theMultiRulesDataInIssueList;
		return $return;
	} // EOF getMultiRulesDataInIssue


	function getMultiRulesDataGroupBy($tpl_id, $type, $type_map_id){
		$return = [];
		$sqlFormater = <<<EOF
		SELECT count(cb_mr_rule_id) as rule_counter
				, cb_mr_rule_id
				, tpl_id
				, type
				, type_map_id
			   FROM `common_cashback_multiple_range_rules`
			   WHERE tpl_id = '%s'
			AND type = '%s'
			AND type_map_id = '%s'
			Group by tpl_id, type, type_map_id
EOF;
		$sql = sprintf($sqlFormater, $tpl_id, $type, $type_map_id);
		$sql = $this->removeNoteInSql($sql);
		$q = $this->db->query($sql);
		$last_query = $this->db->last_query();
		$rows = $q->result_array();
		$result_count = $q->num_rows();
		$q->free_result();
		unset($q);
		$return['last_query'] = $last_query;
		if( ! empty($rows) ){
			// $return['rule_counter'] = $rows[0]['rule_counter'];
			// $return['rows'] = $rows;
			$return['list'] = $rows;
		}
		return $return;
	}// EOF getMultiRulesDataGroupBy


	// active the first one in the template
	public function scriptAdjustTemplateData($template = 'game_tag'){
		$this->load->model(['player_model']);
		// update the game_tag in templates
		$returnInfo =[];
		$table = 'common_cashback_multiple_range_templates';

		// Get the first one
		$theTemplate = $this->getLatestByTemplateName($template, '%', 'asc');
		$this->utils->debug_log('scriptAdjustTemplateData.1131.theTemplate:', $theTemplate);
		$returnInfo['getTheFirstOne']['first'] = $theTemplate;

		// set all template to inactive by the param, $template .
		$qry = $this->db->set([ 'active'=> '0'
				, 'updated_at' => $this->utils->getNowForMysql()
			])
			->where('template_name', $template)
			->update($table);
		$afftectedRows = $this->db->affected_rows();
		$last_query = $this->db->last_query();
		$this->utils->debug_log('scriptAdjustTemplateData.1132.last_query:', $last_query, 'afftectedRows:', $afftectedRows);
		unset($qry);
		$returnInfo['setAllToInactive']['template'] = $template;
		$returnInfo['setAllToInactive']['afftectedRows'] = $afftectedRows;

		$this->db->_reset_select();

		// update the active in the first one
		$qry = $this->db->set([ 'active' => '1'
				, 'updated_at' => $this->utils->getNowForMysql()
			])
			->where('cb_mr_tpl_id', $theTemplate['cb_mr_tpl_id'])
			->update($table);
		$afftectedRows = $this->db->affected_rows();
		$last_query = $this->db->last_query();
		$this->utils->debug_log('scriptAdjustTemplateData.1144.last_query:', $last_query, 'afftectedRows:', $afftectedRows);
		unset($qry);
		$returnInfo['updateActiveInFirst']['afftectedRows'] = $afftectedRows;

		$this->db->_reset_select();

		// Clear the inactive data
		$this->db->where([ 'active'=> '0', 'template_name' => $template]);
		$qry = $this->db->delete($table);
		$afftectedRows = $this->db->affected_rows();
		$last_query = $this->db->last_query();
		unset($qry);
		$this->utils->debug_log('scriptAdjustTemplateData.1153.last_query:', $last_query, 'afftectedRows:', $afftectedRows);
		$returnInfo['clearInactive']['afftectedRows'] = $afftectedRows;

		$this->db->_reset_select();

		$this->utils->debug_log('scriptAdjustTemplateData.1171.returnInfo', $returnInfo);
		return $returnInfo;
	} // EOF scriptAdjustTemplate


	/**
	 * Undocumented function
	 *
	 * @param array $theIssueDataList The representative of the issue data.
	 * @param string $table Usually be common_cashback_multiple_range_rules.
	 * @param string $thePKField The P.K field, cb_mr_rule_id.
	 * @return void
	 */
	public function fixIssueData4RulesCCMR ( $theIssueDataList = []
		, $table = 'common_cashback_multiple_range_rules'
		, $thePKField = 'cb_mr_rule_id'
	){

		// Get the Ok data of the Default tpl.
		$tplId4Default = null;
		$template_name = 'Default';
		$active = '1';
		$order_mode = 'asc';
		$theTemplate = $this->getLatestByTemplateName($template_name, $active, $order_mode);
		$tplId4Default = $theTemplate['cb_mr_tpl_id'];

		// Get the Ok data of the game_tag tpl.
		$tplId4GameTag = null;
		$template_name = 'game_tag';
		$active = '1';
		$order_mode = 'asc';
		$theTemplate = $this->getLatestByTemplateName($template_name, $active, $order_mode);
		$tplId4GameTag = $theTemplate['cb_mr_tpl_id'];

		$this->utils->debug_log('fixIssueData4RulesCCMR.1225.tplId4Default:', $tplId4Default, 'tplId4GameTag:', $tplId4GameTag );
		if( ! empty($theIssueDataList) ){
			foreach($theIssueDataList as $indexNumber => $theIssueData){
				// $theIssueData['tpl_id'] // the field need to fix too
				// $theIssueData['type']
				// $theIssueData['type_map_id']

				/// The fields will be saved.
				$cb_mr_rule_id = $theIssueData['max_cb_mr_data_id']; // P.K.
				// $tpl_id = $this->getTplIdByTypeCCMR( $theIssueData['type'], $tplId4Default, $tplId4GameTag );
				// $type = $theIssueData['type'];
				// $type_map_id = $theIssueData['type_map_id'];
				$keepPKId = $theIssueData['max_cb_mr_data_id'];

				$sameDataList = $this->getRulesDataListByTplIdTypeAndMapId($table,  $thePKField, $keepPKId);


				$this->utils->debug_log('fixIssueData4RulesCCMR.1235.sameDataList.cunter:', empty(count($sameDataList))? 0: count($sameDataList) );

				if( ! empty($sameDataList['keep']) ){

					foreach($sameDataList['keep'] as $indexNumber => $keepData){
						$willUpdateData = $keepData;
						$tpl_id = $this->getTplIdByTypeCCMR( $keepData['type'], $tplId4Default, $tplId4GameTag );
						$willUpdateData['tpl_id'] = $tpl_id;
						$willUpdateData['updated_at'] = $this->utils->getNowForMysql();
						// Update the active = 1 by currect
						$qry = $this->db->set($willUpdateData)
							->where($thePKField, $willUpdateData[$thePKField])
							->update($table);
						$afftectedRows = $this->db->affected_rows();
						$last_query = $this->db->last_query();
						unset($qry);
						$this->utils->debug_log('fixIssueData4RulesCCMR.1245.last_query:', $last_query, 'afftectedRows:', $afftectedRows );
					} // EOF foreach($sameDataList['keep'] as $indexNumber => $keepData){...
				} // EOF if( ! empty($sameDataList['keep']) ){...

				if( ! empty($sameDataList['willDel']) ){
					$willDelIdList = [];
					foreach($sameDataList['willDel'] as $indexNumber => $currData){
						$willDelIdList[] = $currData[$thePKField];
					}
					// To delete data by the list of cb_mr_rule_id
					$this->db->where_in($thePKField, $willDelIdList);
					$qry = $this->db->delete($table);
					$afftectedRows = $this->db->affected_rows();
					$last_query = $this->db->last_query();
					unset($qry);
					$this->utils->debug_log('fixIssueData4RulesCCMR.1258.last_query:', $last_query, 'afftectedRows:', $afftectedRows);
				}

			} // EOF foreach($theIssueDataList as $indexNumber => $theIssueData){
		} // EOF if( ! empty($theIssueDataList) ){

		// fix all data for tpl_id Not Expected
		$this->fixAllData4tplIdInCCMR($table, $thePKField, $tplId4Default, $tplId4GameTag);

	} // EOF fixIssueData4RulesCCMR

	/**
	 * Fix the issue data for settings in CCMR
	 *
	 * @param array $theIssueDataList
	 * @return void
	 */
	public function fixIssueData4SettingsCCMR ( $theIssueDataList = []
											, $table = 'common_cashback_multiple_range_settings'
											, $thePKField = 'cb_mr_sid'
	){


		$tplId4Default = null;
		$template_name = 'Default';
		$active = '1';
		$order_mode = 'asc';
		$theTemplate = $this->getLatestByTemplateName($template_name, $active, $order_mode);
		$tplId4Default = $theTemplate['cb_mr_tpl_id'];

		$tplId4GameTag = null;
		$template_name = 'game_tag';
		$active = '1';
		$order_mode = 'asc';
		$theTemplate = $this->getLatestByTemplateName($template_name, $active, $order_mode);
		$tplId4GameTag = $theTemplate['cb_mr_tpl_id'];

		if( ! empty($theIssueDataList) ){
			foreach($theIssueDataList as $indexNumber => $theIssueData){
				// $theIssueData['max_cb_mr_data_id']
				// $theIssueData['type']
				// $theIssueData['type_map_id']

				/// The fields will be saved.
				$cb_mr_rule_id = $theIssueData['max_cb_mr_data_id']; // P.K.
				$tpl_id = $this->getTplIdByTypeCCMR( $theIssueData['type'], $tplId4Default, $tplId4GameTag );
				$type = $theIssueData['type'];
				$type_map_id = $theIssueData['type_map_id'];
				$keepPKId = $theIssueData['max_cb_mr_data_id'];

				$sameDataList = $this->getDataListByTypeAndMapId($type, $type_map_id, $table,  $thePKField, $keepPKId);
				$this->utils->debug_log('fixIssueData4RulesCCMR.1235.sameDataList.cunter:', empty(count($sameDataList))? 0: count($sameDataList) );

				if( ! empty($sameDataList['keep']) ){
					$willUpdateData = $sameDataList['keep'][0];
					$willUpdateData['updated_at'] = $this->utils->getNowForMysql();
					$tpl_id = $this->getTplIdByTypeCCMR( $willUpdateData['type'], $tplId4Default, $tplId4GameTag );
					$willUpdateData['tpl_id'] = $tpl_id;

					// Update the active = 1 by currect
					$qry = $this->db->set($willUpdateData)
						->where($thePKField, $willUpdateData[$thePKField])
						->update($table);
					$afftectedRows = $this->db->affected_rows();
					$last_query = $this->db->last_query();
					unset($qry);
					$this->utils->debug_log('fixIssueData4RulesCCMR.1245.last_query:', $last_query, 'afftectedRows:', $afftectedRows);
				}

				if( ! empty($sameDataList['willDel']) ){
					$willDelIdList = [];
					foreach($sameDataList['willDel'] as $indexNumber => $currData){
						$willDelIdList[] = $currData[$thePKField];
					}
					// To delete data by the list of cb_mr_rule_id
					$this->db->where_in($thePKField, $willDelIdList);
					$qry = $this->db->delete($table);
					$afftectedRows = $this->db->affected_rows();
					$last_query = $this->db->last_query();
					unset($qry);
					$this->utils->debug_log('fixIssueData4RulesCCMR.1258.last_query:', $last_query, 'afftectedRows:', $afftectedRows);
				}

			} // EOF foreach($theIssueDataList as $indexNumber => $theIssueData){
		} // EOF if( ! empty($theIssueDataList) ){


		// fix all data for tpl_id Not Expected
		$this->fixAllData4tplIdInCCMR($table, $thePKField, $tplId4Default, $tplId4GameTag);

	} // EOF fixIssueData4SettingsCCMR



	public function fixAllData4tplIdInCCMR($table, $thePKField, $tplId4Default, $tplId4GameTag){
		$this->load->model(['wallet_model']);
		$return = [];
		$return['afftectedRows'] = 0;
		// fix all data for tpl_id Not Expected
		$this->db->from($table)
			->where_not_in('tpl_id', [$tplId4Default, $tplId4GameTag]);
		$theList = $this->wallet_model->runMultipleRowArray();
		$last_query = $this->db->last_query();
		$this->utils->debug_log('fixAllData4tplIdInCCMR.1376.latest.last_query:', $last_query, 'afftectedRows:', $return['afftectedRows']);
		if(! empty($theList) ){
			foreach($theList as $indexNumber => $theData){
				$theData['tpl_id'] = $this->getTplIdByTypeCCMR( $theData['type'], $tplId4Default, $tplId4GameTag );
				$theData['updated_at'] = $this->utils->getNowForMysql();
				$qry = $this->db->set($theData)
						->where($thePKField, $theData[$thePKField])
						->update($table);
					$afftectedRows = $this->db->affected_rows();
					$return['afftectedRows'] += $afftectedRows;
					$last_query = $this->db->last_query();
					unset($qry);
			}
		}

		$this->utils->debug_log('fixAllData4tplIdInCCMR.1389.latest.last_query:', $last_query, 'afftectedRows:', $return['afftectedRows']);
		return $return;
	}// EOF fixAllData4tplIdInCCMR

	public function getTplIdByTypeCCMR($theType, $tplId4Default, $tplId4GameTag){
		$theTplId = null;
		switch($theType){
			case 'game_platform':
			case 'game_type':
			case 'game':
				$theTplId = $tplId4Default;
				break;

			case 'game_tag':
				$theTplId = $tplId4GameTag;
				break;
		}
		return $theTplId;
	} // EOF getTplIdByTypeCCMR


	/**
	 * Get the representative of all issue data
	 * The representative data means the one of the same issue data in the all issue data.
	 *
	 * @param string $table Usually be common_cashback_multiple_range_rules table.
	 * @param string $thePKField The P.K. field, cb_mr_rule_id.
	 * @return void
	 */
	public function getIssueRulesDataCCMR($table = 'common_cashback_multiple_range_rules', $thePKField = 'cb_mr_rule_id'){
		$theIssueList = [];

		// The multiple data may be regarded as the same data by tpl_id, type, type_map_id.
		$sql = <<<EOF
		SELECT *
		FROM (
			SELECT *
			, count(cb_mr_rule_id) as counter -- This counter of the issue data by the same data, type, type_map_id
			, max(tpl_id) as max_tpl_id -- This data just detect the result of the issue data after patched.
			, max(cb_mr_rule_id) as max_cb_mr_data_id -- This data just detect the result of the issue data after patched.
			FROM (
					SELECT count(cb_mr_rule_id) as rule_counter -- This counter of the same data,tpl_id, type, type_map_id
					, cb_mr_rule_id -- The cb_mr_rule_id of the issue data.
					, tpl_id  -- The tpl_id of the issue data.
					, type  -- The type of the issue data.
					, type_map_id  -- The type_map_id of the issue data.
					FROM `common_cashback_multiple_range_rules`
					Group by tpl_id, type, type_map_id
			)as tmpInner
			group by type, type_map_id
		)as tmp
		WHERE counter > 1;
EOF;
		$sql = $this->removeNoteInSql($sql);
		$q = $this->db->query($sql);
		$last_query = $this->db->last_query();
		$rows = $q->result_array();
		$result_count = $q->num_rows();
		$q->free_result();
		unset($q);

		$this->utils->debug_log('getIssueRulesDataCCMR.1402.last_query:', $last_query, 'rows.count:', count($rows) );
		$theIssueList = $rows;

		return $theIssueList;
	} // EOF getIssueRulesDataCCMR

	/**
	 * Get The Issue Data CCMR
	 * CCMR = common_cashback_multiple_range
	 * The issue data for common_cashback_multiple_range_settings only.
	 *
	 * @param string $table Usually be common_cashback_multiple_range_settings
	 * @param string $thePKField The P.K field,cb_mr_sid.
	 * @return void
	 */
	public function getIssueDataCCMR($table = 'common_cashback_multiple_range_settings', $thePKField = 'cb_mr_sid'){

		$theIssueList = [];

		$sql = <<<EOF
		SELECT *
		FROM (
			SELECT max( $thePKField ) as max_cb_mr_data_id -- This data should be referenced. -- cb_mr_sid
			, count( $thePKField ) as counter -- The issue counter by the data.
			, type -- The type of the issue data.
			, type_map_id -- The type_map_id of the issue data.
			FROM `$table` -- common_cashback_multiple_range_settings
			Group by type,	type_map_id
		)as tmp
		WHERE counter > 1
EOF;
		$sql = $this->removeNoteInSql($sql);

		$q = $this->db->query($sql);
		$last_query = $this->db->last_query();
		$rows = $q->result_array();
		$result_count = $q->num_rows();
		$q->free_result();
		unset($q);

		$this->utils->debug_log('getIssueDataCCMR.1440.last_query:', $last_query, 'rows.count:', count($rows) );
		$theIssueList = $rows;

		return $theIssueList;
	} // EOF getIssueDataCCMR

	public function getLatestByTemplateName($template_name = 'Default', $active = '%', $order_mode = 'desc'){
		$this->load->model(['wallet_model']);
		$table = 'common_cashback_multiple_range_templates';
		$theLatest = [];
		$this->db->from($table)
			->where('template_name', $template_name)
			->order_by('updated_at',  $order_mode)
			->limit(3);

		$findme = '%';
		if( strpos($active, $findme) !== false){
			$this->db->where('active like', $active);
		}

		$qry = $this->db->get();
		$theList = $qry->result_array();
		$qry->free_result();
		unset($qry);
		// $theList = $this->wallet_model->getMultipleRowArray($qry);
		$last_query = $this->db->last_query();
		unset($qry);
		$this->utils->debug_log('getLatestByTemplateName.1184.last_query:', $last_query, 'theList:', $theList);
		if( !empty($theList) ){
			$theLatest = $theList[0];
		}

		return $theLatest;
	}// EOF getLatestByTemplateName

	public function getDataListByTypeAndMapId($type
											, $type_map_id
											, $table = 'common_cashback_multiple_range_rules'
											, $thePKField = 'cb_mr_rule_id'
											, $keepPKId = null
	){
		$this->load->model(['wallet_model']);

		$returnList = [];
		$returnList['keep'] = [];
		$returnList['willDel'] = [];
		$this->db->from($table)
			->where('type', $type)
			->where('type_map_id', $type_map_id)
			->order_by('updated_at',  "desc");
		$theList = $this->wallet_model->runMultipleRowArray();
		$last_query = $this->db->last_query();
		if( !empty($theList) ){
			foreach( $theList as $indexNumber => $row ){
				if($row[$thePKField] == $keepPKId){
					$returnList['keep'][] = $row;
				}else{
					$returnList['willDel'][] = $row;
				}
			}
		}

		$this->utils->debug_log('getDataListByTypeAndMapId.1423.last_query:', $last_query);

		return $returnList;
	} // getDataListByTypeAndMapId

	/**
	 * Get the all data and distribute to the "keep" array and the "willDel" array
	 *
	 * @param string $table Usually be the table,"common_cashback_multiple_range_rules".
	 * @param string $thePKField the P.K. field, cb_mr_rule_id.
	 * @param integer $keepPKId The P.K field, for keep saving.
	 * @return array The array for distributed the "keep" array and the "willDel" array,
	 * - $returnList['keep'] All data of the same data by tpl_id, type and type_map_id.
	 * - $returnList['willDel'] The another data sets by type and type_map_id.(defference from tpl_id of the $keepPKId)
	 */
	public function getRulesDataListByTplIdTypeAndMapId( $table = 'common_cashback_multiple_range_rules'
													, $thePKField = 'cb_mr_rule_id'
													, $keepPKId = null
	){
		$this->load->model(['wallet_model']);

		// Get a data that will remain saved.
		$returnList = [];
		$returnList['keep'] = [];
		$returnList['willDel'] = [];
		$this->db->from($table)
			->where($thePKField, $keepPKId);
		$theKeepList = $this->wallet_model->runMultipleRowArray();
		$last_query = $this->db->last_query();
		$theKeepData = [];
		if(!empty($theKeepList)){
			$theKeepData = $theKeepList[0];
		}
		$this->utils->debug_log('getRulesDataListByTplIdTypeAndMapId.1584.last_query:', $last_query, 'theKeepData:', $theKeepData);

		$tpl_id = $theKeepData['tpl_id'];
		$type = $theKeepData['type'];
		$type_map_id = $theKeepData['type_map_id'];

		$returnList = [];
		$returnList['keep'] = [];
		$returnList['willDel'] = [];

		/// Get all data that will remain saved.
		// The data set mean the same one by tpl_id, type and type_map_id.
		$this->db->from($table)
			->where('tpl_id', $tpl_id)
			->where('type', $type)
			->where('type_map_id', $type_map_id);
			// ->order_by('updated_at',  "desc");
		$theList = $this->wallet_model->runMultipleRowArray();
		$last_query = $this->db->last_query();
		$keepList = []; // the list means same data for rules.
		if( !empty($theList) ){
			foreach( $theList as $indexNumber => $row ){
				$returnList['keep'][] = $row;
			}
		}
		$this->utils->debug_log('getRulesDataListByTplIdTypeAndMapId.1609.last_query:', $last_query, 'count($theList):', (count($theList)? count($theList):0) );

		/// Get all data that will be deleted.
		$this->db->from($table)
			->where('tpl_id !=', $tpl_id)
			->where('type', $type)
			->where('type_map_id', $type_map_id)
			->order_by('updated_at',  "desc");
		$theList = $this->wallet_model->runMultipleRowArray();
		$last_query = $this->db->last_query();
		if( !empty($theList) ){
			foreach( $theList as $indexNumber => $row ){
				$returnList['willDel'][] = $row;
			}
		}
		$this->utils->debug_log('getRulesDataListByTplIdTypeAndMapId.1624.last_query:', $last_query, 'count($theList):', (count($theList)? count($theList):0));

		$this->utils->debug_log('getRulesDataListByTplIdTypeAndMapId.1626.returnList:', $returnList);
		return $returnList;
	} // EOF getRulesDataListByTplIdTypeAndMapId


	/**
	 * detail: update promo id for a certain player
	 *
	 * @param int $playerPromoId The fiield, player_promo_id of table
	 * @param string $tablename The table will be updated.
	 * @param int $pkId The PK field value of table.
	 * @param string $pkField The PK field name of table.
	 *
	 * @return Boolean
	 */
	public function updatePlayerPromoIdOfTable($playerPromoId, $tablename, $pkId, $pkField = 'id') {

		$qry = $this->db->set('player_promo_id', $playerPromoId)
			->where($pkField, $pkId)
			->update($tablename);
		if($qry===false){
			return false;
		}

		unset($qry);

		return true;
	}

	/**
	 * Get the withdraw_conditions data by $source_id
	 *
	 * @param integer $source_id withdraw_conditions.source_id
	 * @return void
	 */
	function getWithdrawConditionsBySourceId($source_id){
		$this->db->from('withdraw_conditions')
				 ->where('source_id', $source_id);
		$qry = $this->db->get();
		// $rows = [];
		// foreach ($qry->result_array() as $row)
		// {
		// 	$rows[] = $row;
		// }
		$rows = $qry->result_array();
		return $rows;
	}

	/**
	 *
	 * Cloned from admin/application/controllers/BaseController.php
	 *
	 * @param [type] $agent_id
	 * @param [type] $username
	 * @param boolean $insert_only
	 * @param [type] $rlt
	 * @return void
	 */
	public function syncAgentCurrentToMDBWithLock($agent_id, $username, $insert_only=false, &$rlt=null){
		if(!$this->utils->isEnabledMDB()){
			return true;
		}

		return $this->utils->globalLockAgencyRegistration($username, function ()
				use ($agent_id, $insert_only, &$rlt) {
			return $this->syncAgentCurrentToMDB($agent_id, $insert_only, $rlt);
		});
	} // EOF syncAgentCurrentToMDBWithLock
	/**
	 *
	 * Cloned from admin/application/controllers/BaseController.php
	 *
	 * @param [type] $agent_id
	 * @param boolean $insertOnly
	 * @param [type] $rlt
	 * @return void
	 */
	public function syncAgentCurrentToMDB($agent_id, $insertOnly=false, &$rlt=null){
		if(!$this->utils->isEnabledMDB()){
			return true;
		}

		$this->load->model(['multiple_db_model']);
		$rlt=$this->multiple_db_model->syncAgencyFromCurrentToOtherMDB($agent_id, $insertOnly);
		$this->utils->debug_log('syncAgencyFromCurrentToOtherMDB :'.$agent_id, $rlt);
		$success=false;
		if(!empty($rlt)){
			foreach ($rlt as $key => $dbRlt) {
				$success=$dbRlt['success'];
				if(!$success){
					break;
				}
			}
		}
		return $success;
	}// EOF syncAgentCurrentToMDB

	/**
	 * overview : show affiliate password
	 *
	 * @param string $username
	 */
	public function show_agency_password($username) {
		$this->db->from('agency_agents')->where('agent_name', $username);
		$qry = $this->db->get();
		$row = $qry->row();
		$password = '';
		if ($row) {
			$this->load->library(array('salt'));
			$password = $row->password;
			$password = $this->salt->decrypt($password, $this->getDeskeyOG());
		}

		$this->output->append_output("password:" . $password . "\n");
	}

    public function generateUpdateAffiliatePassword($length=8, $saveToCsv='FALSE', $onlyUpdateNull='TRUE'){

    	$this->load->model(array('affiliatemodel'));
    	$this->load->library('salt');

    	$onlyUpdateNull= ($onlyUpdateNull == 'TRUE') ?  true : false;
    	$saveToCsv= ($saveToCsv == 'TRUE') ?  true : false;
    	$d = new DateTime();
    	$directory = '/home/vagrant/Code/';
    	$csv_filepath =  $directory.(__FUNCTION__).'-'.$this->_app_prefix.'-'.$d->format('Y_m_d_H_i_s').'.csv' ;
    	$affMap = $this->affiliatemodel->getUsernameMap();
    	$passList = [];

    	if($saveToCsv){

    		$header_data = ['affiliateId','username','password'];

    		$fp = fopen($csv_filepath, 'w');
    		if ($fp) {
    			$BOM = "\xEF\xBB\xBF";
                fwrite($fp, $BOM); //
            } else {
                //create report failed
            	$this->utils->error_log('create csv file failed', $csv_filepath);
            	return;
            }

            fputcsv($fp, $header_data, ',', '"');
        }


        $count = 0;

        foreach ($affMap as $username => $affiliateId ) {

        	$password = $this->utils->generate_password_no_special_char($length);
        	$hash = $this->salt->encrypt($password, $this->getDeskeyOG());
        	$data = array('affiliateId'=>$affiliateId, 'username'=>$username, 'password'=>$password);
        	$this->utils->debug_log("Affiliate password loop at " , $data);

        	$this->affiliatemodel->startTrans();

        	if($onlyUpdateNull){
        		$affiliate= $this->affiliatemodel->getAffPassword($affiliateId);
        		if(empty($affiliate['password']) || $affiliate['password'] == ''){
        			$this->affiliatemodel->editAffiliates(array('password'=>$hash), $affiliateId);
        			$this->utils->debug_log("Affiliate password update row only null " , $data);
        			if($saveToCsv) {
        				fputcsv($fp, $data, ',', '"');
        			}else{
        				array_push($passList, $data);
        			}
        			$count++;
        		}
	       	}else{//update all
	       		$this->affiliatemodel->editAffiliates(array('password'=>$hash), $affiliateId);
	       		$this->utils->debug_log("Affiliate password update row all affiliates" , $data);
	       		if($saveToCsv) {
	       			fputcsv($fp, $data, ',', '"');
	       		}else{
	       			array_push($passList, $data);
	       		}
	       		$count++;
	       	}

	       	if ($this->affiliatemodel->isErrorInTrans()){
	       		$this->utils->error_log("Failed update aff password" , $affiliateId);
	       	}

       	$this->affiliatemodel->endTrans();

       }//loop

       if($saveToCsv) {
       	fclose($fp);
       	$this->utils->info_log("CSV Affiliate password is here " , $csv_filepath, 'Update count', $count);
       }else{
       	$this->utils->info_log("Affiliate passlist password is here " , @$passList , 'Update count', $count);
       }
   }


   public function setOneWorksAgentPlayersBetlimit($agent_name='_null',$playerId='_null',$dry_run='true'){

   	if(empty($agent_name) || $agent_name=='_null' ){
   		$this->utils->error_log('Please provide agent_name');
   		return;
   	}

   	$dry_run = $dry_run == 'true' ? true : false;
   	$playerId = $playerId != "_null" ? $playerId : null;

   	$this->load->model(['agency_model','player_model','game_provider_auth']);
   	/*
   	$config['oneworks_bet_limit_settings'] = [
        [
            'sport_type' => 1,
            'min_bet' => 10,
            'max_bet' => 50000,
            'max_bet_per_match' => 100000
        ],
        [
            'sport_type' => 2,
            'min_bet' => 10,
            'max_bet' => 50000,
            'max_bet_per_match' => 100000
        ],
        [
            'sport_type' => 3,
            'min_bet' => 10,
            'max_bet' => 50000,
            'max_bet_per_match' => 100000
        ]
     ];
     */
     $oneworks_bet_limit_settings = $this->utils->getConfig('oneworks_bet_limit_settings');

     $api = $this->utils->loadExternalSystemLibObject(ONEWORKS_API);
     $playerMap = $this->player_model->getPlayerIdUsernameMap();
     $agent_id = $this->agency_model->getAgentIdByUsername($agent_name);
     if(empty($agent_id)){
     	$this->utils->error_log('agent id not is empty with agent_name: '.$agent_name);
     	return;
     }

     $playerIds = null;
     if(empty($playerId)){
     	$playerIds = $this->agency_model->get_players_by_agent_id($agent_id);
     }else{
     	$playerIds = [$playerId];
     }

     $failCount = 0;
     $totalCount = count($playerIds);
     $failedList = [];
     $no_of_registered = 0;
     $successList=[];

     if($api){
     	if(!empty($playerIds)){
     		foreach ($playerIds as $playerId) {
     			if($this->game_provider_auth->isRegisterd($playerId,ONEWORKS_API)){
     				$playerName = $playerMap[$playerId];
     				$rlt=[];
     				if(isset($playerName)){
     					if(!empty($oneworks_bet_limit_settings)){
     						if($dry_run){
     							$this->utils->info_log('Dry run only for ', 'playerId', $playerId, 'playerName',$playerName);
     						}else{
     							$rlt = $api->setMemberBetSetting($playerName,$oneworks_bet_limit_settings);
     							if(!$rlt['success']){
     								array_push($failedList,['playerid'=>$playerId, 'reason'=>'Api request failed']);
     								$failCount++;
     							}else{
     								array_push($successList, ['playerid'=>$playerId, 'playerName'=>$playerName]);
     							}
     						}
     					}else{
     						$this->utils->error_log('oneworks_assigned_bet_limit config , Please set it!');
     						break;
     					}
     				}else{
     					array_push($failedList,['playerid'=>$playerId, 'reason'=>'Player name not exist in sbe']);
     					$failCount++;
     					$this->utils->error_log('Playername not exist', $playerId);
     				}
     				$no_of_registered++;
     			}
     		}
     	}else{
     		$this->utils->error_log('Empty playerids!');
     	}

     }else{
     	$this->utils->error_log(' ONEWORKS_API not set!');
   	}//if
   	$successCount = $totalCount - $failCount;
   	$this->utils->info_log("Oneworks set betting limit done, [$successCount] out of [$totalCount] succeed. no_of_registered = [$no_of_registered]", "failedList", $failedList, 'dry_run', $dry_run);
   	$this->utils->info_log("successList",$successList);
   	$this->utils->debug_log("oneworks_bet_limit_settings", @$oneworks_bet_limit_settings);
   }

   	public function delete_gameplay_original_gamelogs_duplicate($startDate=null,$endDate=null){
   		$this->utils->info_log('Now Query duplicate map for Gameplay gamelogs duplicate');
   		$query = "SELECT MAX(id) as max_id,external_uniqueid FROM gameplay_game_logs GROUP BY external_uniqueid HAVING COUNT(external_uniqueid) > 1";
   		$duplicates = [];

   		$query = $this->db->query($query);
   		if ($query && $query->num_rows() > 0) {
			$duplicates=json_decode(json_encode($query->result()),true);
			$query->free_result();
		}

   		$this->utils->debug_log('duplicates',$duplicates);
		$external_uniqueid_map = array_column($duplicates, "max_id", "external_uniqueid");
		$dateTimeFrom = new \DateTime($startDate);
		$dateTimeTo = new \DateTime($endDate);
		$interval = new DateInterval('P1D');
		$daterange = new DatePeriod($dateTimeFrom, $interval ,$dateTimeTo);

		# QUERY GAMELOGS PER DAY
		foreach($daterange as $date){
			$gameRecords = [];
			$dataStartDate = $date->format("Y-m-d 00:00:00");
			$dataEndDate = $date->format("Y-m-d 23:59:59");
			$dataSql = <<<EOD
			SELECT
			  id,external_uniqueid
			FROM
			  gameplay_game_logs
			WHERE (
			    trans_date BETWEEN ?
			    AND ?
			  )
			  OR (
			    timeBet BETWEEN ?
			    AND ?
			  )
EOD;
			$query2 = $this->db->query($dataSql, array(
				$dataStartDate,
				$dataEndDate,
				$dataStartDate,
				$dataEndDate,
			));

	   		if ($query2 && $query2->num_rows() > 0) {
				$gameRecords=json_decode(json_encode($query2->result()),true);
				$query2->free_result();
			}

			foreach ($gameRecords as $record) {
				$maxid = @$external_uniqueid_map[$record['external_uniqueid']];
				# check if latest record if not delete
				if($maxid&&$maxid!=$record['id']){
			        $this->db->where('id', $record['id']);
			        $this->db->delete('gameplay_game_logs');
					$this->utils->debug_log("Gameplay Duplicate record delete id = " , $record['id']);
				}
			}
		}
    }

    public function delete_gameplay_original_gamelogs_duplicate_via_temp_table(){
   		$this->utils->info_log('Now DELETING DUPLICATE Gamelogs for Gameplay');
   		$query = "SELECT id FROM gameplay_game_logs_temp";
   		$duplicates = [];

   		$query = $this->db->query($query);
   		if ($query && $query->num_rows() > 0) {
			$duplicates=json_decode(json_encode($query->result()),true);
			$query->free_result();
		}
		foreach ($duplicates as $record) {
			# check if latest record if not delete
	        $this->db->where('id', $record['id']);
	        $this->db->delete('gameplay_game_logs');
			$this->utils->debug_log("Gameplay Duplicate record delete id = " , $record['id']);
		}
    }

	/**
	 * batch_change_player_level
	 *
	 * The sample CMDs,
	 * - sudo /bin/bash admin/shell/command.sh batch_change_player_level '13 22 43 54' 53 >> ./logs/command_batch_change_player_level.log &
	 * - nohup sudo /bin/bash admin/shell/command.sh batch_change_player_level '13 22 43 54' 53 >> ./logs/command_batch_change_player_level.log 2>&1 &
	 * - sudo sh command_mdb_noroot.sh brl batch_change_player_level '13 22 43 54' 53 >> ./logs/command_batch_change_player_level.log 2>&1 &
     *
     * - sudo /bin/bash admin/shell/command.sh batch_change_player_level 'all' 31 >> ./logs/command_batch_change_player_level.log &
     * - sudo sh command_mdb_noroot.sh brl batch_change_player_level 'all' 53 >> ./logs/command_batch_change_player_level.log 2>&1 &
     *
	 * @param string $player_id_list The player.playerId list with space concat. The keyword, "all" that means all players.
	 * @param integer $targetPlayerLevel The param, $vipgrouplevelId of the URI, http://admin.og.local/vipsetting_management/editVipGroupLevel/57 .
	 * @param integer $processed_by For SBE, default by cli.
	 * @return void The keyword,"batch_change_player_level.reportDetails" in log. for report amounts of the result.
	 */
    public function batch_change_player_level($player_id_list,$targetPlayerLevel, $processed_by = Users::SUPER_ADMIN_ID){

		$this->load->model(array('group_level', 'player_model'));

        $array_player_id_list = [];
        if($player_id_list == 'all'){
            $doPlayerFilter = $this->config->item('do_player_filter_in_batch_change_player_level'); // 0
            $lastLoginTimeBeginDateTime = new DateTime( $this->config->item('lastLoginTime_begin_batch_change_player_level') ); // -2 days
			$lastLoginTimeEndDateTime = new DateTime( $this->config->item('lastLoginTime_end_batch_change_player_level') ); // now
			if( ! empty($doPlayerFilter) ){
                $login_time_min = $this->utils->formatDateTimeForMysql($lastLoginTimeBeginDateTime);
				$login_time_max = $this->utils->formatDateTimeForMysql($lastLoginTimeEndDateTime);
                $except_levelId = $targetPlayerLevel;
                $playerIds = $this->player_model->getAllEnabledPlayersByActivityTime($login_time_min, $login_time_max, $except_levelId);
            }else{
                $select = 'player.playerId';
                $except_levelId = $targetPlayerLevel;
                $playerIds = $this->player_model->getAllEnabledPlayers($select, $except_levelId);
            }

            $array_player_id_list = array_column($playerIds, 'playerId');
            unset($playerIds);
        }else{
            $array_player_id_list = array_map('trim', array_filter(explode(' ', $player_id_list)));
        }

		$reportDetails = [];
		$reportDetails['success_count'] = 0;
		$reportDetails['fail_count'] = 0;
		$reportDetails['fail_player_id_list'] = [];
		$reportDetails['total'] = count($array_player_id_list);

		if( ! empty($array_player_id_list) ){
			foreach($array_player_id_list as $indexNumber => $player_id ){
				$arr = $this->change_player_level($player_id, $targetPlayerLevel, $processed_by);
				/// for tail progress, batch_change_player_level.done.change_player_level.arr
                $this->utils->debug_log('batch_change_player_level.done.change_player_level.indexNumber', $indexNumber, 'total:', $reportDetails['total'], 'player_id:', $player_id, 'arr:', $arr);

				if($arr['status'] == 'success'){
					$reportDetails['success_count']++;
				}else{ // others
					$reportDetails['fail_count']++;
					array_push($reportDetails['fail_player_id_list'],$player_id);

					$resultCaseNo = $arr['resultCaseNo'];
					$resultCaseNoCounterKey = sprintf('resultCaseNo_%d_count', $resultCaseNo);
					$resultCaseNoPlayerListKey = sprintf('resultCaseNo_%d_player_id_list', $resultCaseNo);
					if( empty($reportDetails[$resultCaseNoCounterKey]) ){
						$reportDetails[$resultCaseNoCounterKey] = 0;
					}
					$reportDetails[$resultCaseNoCounterKey]++;
					if( empty($reportDetails[$resultCaseNoPlayerListKey]) ){
						$reportDetails[$resultCaseNoPlayerListKey] = [];
					}
					array_push($reportDetails[$resultCaseNoPlayerListKey],$player_id);

					$resultCaseNoIntroKey = sprintf('resultCaseNo_%d_intro', $resultCaseNo);
					if( empty($reportDetails[$resultCaseNoIntroKey]) ){
						switch($resultCaseNo){
							case Utils::RESULT_CASE_THE_PLAYER_ALREADY_IN_THE_LEVEL:
								$reportDetails[$resultCaseNoIntroKey] = 'THE_PLAYER_ALREADY_IN_THE_LEVEL';
								break;
							case Utils::RESULT_CASE_THE_ERROR_IN_TRANS:
								$reportDetails[$resultCaseNoIntroKey] = 'THE_ERROR_IN_TRANS';
								break;
							case Utils::RESULT_CASE_DONE_IN_TRANS:
								$reportDetails[$resultCaseNoIntroKey] = 'DONE_IN_TRANS';
								break;
							case Utils::RESULT_CASE_TARGET_LEVEL_NOT_EXIST:
								$reportDetails[$resultCaseNoIntroKey] = 'TARGET_LEVEL_NOT_EXIST';
								break;
							case Utils::RESULT_CASE_THE_PLAYER_NOT_IN_ANY_LEVEL:
								$reportDetails[$resultCaseNoIntroKey] = 'THE_PLAYER_NOT_IN_ANY_LEVEL';
								break;
						} // EOF switch($resultCaseNo){...
					}

				}
                $arr = [];
                unset($arr); // free mem.
			} // EOF foreach($array_player_id_list as $indexNumber => $player_id ){...
		}
		$this->utils->debug_log("batch_change_player_level.reportDetails:" , $reportDetails);
		return $reportDetails;
	}// EOF batch_change_player_level

	/**
	 * batch_fill_field_banktype_id_info_in_affiliatepayment
	 *
	 * sudo /bin/bash admin/shell/command.sh batch_fill_field_banktype_id_info_in_affiliatepayment >> ./logs/command_batch_fill_field_banktype_id_info_in_affiliatepayment.log &
	 * nohup sudo /bin/bash admin/shell/command.sh batch_fill_field_banktype_id_info_in_affiliatepayment >> ./logs/command_batch_fill_field_banktype_id_info_in_affiliatepayment.log 2>&1 &
	 *
	 * bash admin/shell/command_mdb_noroot.sh brl batch_fill_field_banktype_id_info_in_affiliatepayment >> ./logs/command_batch_fill_field_banktype_id_info_in_affiliatepayment.log &
	 * nohup bash admin/shell/command_mdb_noroot.sh brl batch_fill_field_banktype_id_info_in_affiliatepayment >> ./logs/command_batch_fill_field_banktype_id_info_in_affiliatepayment.log 2>&1 &
	 *
	 * @return void
	 */
	public function batch_fill_field_banktype_id_info_in_affiliatepayment(){
		$this->load->model(['vipsetting']);
		$this->load->library(['language_function']);
		$rows = $this->getNullBanktypeDataById([], $result_count);

		$report_info = [];
		$report_info['total_rows'] = 0;

		$report_info['update_completed'] = [];
		$report_info['update_completed']['count'] = 0;

		$report_info['others_issue_case'] = [];
		$report_info['others_issue_case']['count'] = 0;
		$report_info['others_issue_case']['list'] = [];

		$report_info['more_1_row_by_a_lang_string'] = [];
		$report_info['more_1_row_by_a_lang_string']['count'] = 0;
		$report_info['more_1_row_by_a_lang_string']['list'] = [];

		$report_info['not_found_by_a_lang_string'] = [];
		$report_info['not_found_by_a_lang_string']['count'] = 0;
		$report_info['not_found_by_a_lang_string']['list'] = [];

		$report_info['update_failed'] = [];
		$report_info['update_failed']['count'] = 0;
		$report_info['update_failed']['list'] = [];
		if($result_count > 0){
			$total_rows = count($rows);
			$report_info['total_rows'] = $total_rows;

			foreach ($rows as $indexNumber => $row) {

				$this->utils->debug_log('will fix indexNumber:', $indexNumber, 'total_rows:',$total_rows, 'affiliatePaymentId:', $row['affiliatePaymentId']);

				$controller = $this;
				$result = null; // for collect the result detail.
				$success = $this->vipsetting->runDBTransOnly( $this->vipsetting->db
					, $result
					,  function($_db, &$_result) use ($row, $controller, &$report_info) {
						$_affiliatePaymentId = $row['affiliatePaymentId'];
						$_bankName = $row['bankName'];
						$is_use_lang_mapping = 0; // mapping by lang(bankName)
						$langId = 0;
						$langStr = '';
						$is_use_bankName_mapping = 0; // mapping by bankName, Int Type
						$bankNameInt = 0;
						$is_ready_fill = 0; // will update the banktype_id
						$fill_banktype_id = 0;
						if( is_numeric( trim($_bankName) ) ){
							// update banktype_id clond from bankName
							$is_use_bankName_mapping = 1;
							$bankNameInt = trim($_bankName);
// $this->utils->debug_log('2608.2566.bankNameInt:', $bankNameInt);
							// re-assign for update
							$is_ready_fill = 1;
							$fill_banktype_id = trim($_bankName);
						}else if( lang($_bankName, Language_function::INT_LANG_ENGLISH) ==  substr($_bankName, 6) ){
							// lang() parse failed
							$_lang_list = [];
							$_extracted = $this->extract_crush_langs_string_to_list($_bankName, $_lang_list);
// $this->utils->debug_log('2608.2576._extracted:', $_extracted, '_lang_list', $_lang_list);
							// first lang for compare
							if( empty($_lang_list) ){
								// Not found by a lang string, not_found_by_a_lang_string
								$report_info['not_found_by_a_lang_string']['count']++;
								$report_info['not_found_by_a_lang_string']['list'][] = $row['affiliatePaymentId']; // affiliatepayment
							}else if( ! empty($_lang_list[Language_function::INT_LANG_ENGLISH]) ){
								$is_use_lang_mapping = 1;
								$langId = Language_function::INT_LANG_ENGLISH;
								$langStr = $_lang_list[Language_function::INT_LANG_ENGLISH];
// $this->utils->debug_log('2608.2580.langId:', $langId);
							}else{
								// @todo other languages, but so far, its no requirement.
							}
						}else if( lang($_bankName, Language_function::INT_LANG_ENGLISH) != $_bankName){
							// use lang($_bankName) to mapping
							$is_use_lang_mapping = 1;
							$langId = Language_function::INT_LANG_ENGLISH;
							$langStr = lang($_bankName, Language_function::INT_LANG_ENGLISH);
// $this->utils->debug_log('2608.2572.lang._bankName:', lang($_bankName, Language_function::INT_LANG_ENGLISH));
// $this->utils->debug_log('2608.2572.langStr:', $langStr);
// $this->utils->debug_log('2608.2572.langId:', $langId);
						}else if( substr($_bankName, 0, 6) !== '_json:'){ // stored a string.
							$is_use_lang_mapping = 1;
							$langId = 0;
							$langStr = $_bankName;
// $this->utils->debug_log('2608.2596.langId:', $langId);
						}else{
							// others_issue_case
							$report_info['others_issue_case']['count']++;
							$report_info['others_issue_case']['list'][] = $row['affiliatePaymentId']; // affiliatepayment
						}

						if( ! empty($is_use_lang_mapping) ){
							if( ! empty($langId) && ! empty($langStr) ){
// $this->utils->debug_log('unicode.2609.langStr:', $langStr);
								/// ธนาคารไทยพาณิช convert to \u0e18\u0e19\u0e32\u0e04\u0e32\u0e23\u0e44\u0e17\u0e22\u0e1e\u0e32\u0e13\u0e34\u0e0a\u0e22\u0e4c
								$langStr = json_encode($langStr); // , JSON_UNESCAPED_UNICODE);
// error_log('unicode.2610.langStr:', 3, "/var/tmp/my-errors.log");
// error_log($langStr, 3, "/var/tmp/my-errors.log");
// error_log(PHP_EOL, 3, "/var/tmp/my-errors.log");

								$langStr = str_replace('\\\\u', '\u', $langStr); // for crush langs string, _json:{"1":"\u0e17\u0e35\u0e40\u0e2d\u0e47\u0e21\u0e1a\u0e35\u0e18\u0e19\u0e0a\u0e32\u0e15","2":"\u0e17\u0e35\u0e40\u0e2d\u0e47\u0e21\u0e1a\u0e35\u0e18\u0e19\u0e0a\u0e32\u0e15","5":"\u0e17\u0e35\u0e40
// error_log('unicode.2612.langStr:', 3, "/var/tmp/my-errors.log");
// error_log($langStr, 3, "/var/tmp/my-errors.log");
// error_log(PHP_EOL, 3, "/var/tmp/my-errors.log");

								$langStr = str_replace('\u', '%', $langStr);
// error_log('unicode.2615.langStr:', 3, "/var/tmp/my-errors.log");
// error_log($langStr, 3, "/var/tmp/my-errors.log");
// error_log(PHP_EOL, 3, "/var/tmp/my-errors.log");

								$langStr = substr($langStr, 1, -1);
// error_log('unicode.2623.langStr:', 3, "/var/tmp/my-errors.log");
// error_log($langStr, 3, "/var/tmp/my-errors.log");
// error_log(PHP_EOL, 3, "/var/tmp/my-errors.log");
// $this->utils->debug_log('unicode.2613.langStr:', $langStr);
								$langStr = trim($langStr);
								$bankNameLikeFormater = '"%s":"%s"';
								$conditionBankNameLike = sprintf($bankNameLikeFormater, $langId, $langStr);
							} else if( empty($langId) && ! empty($langStr) ){
								$langStr = trim($langStr);
								$conditionBankNameLike = $langStr;
							}

							$SQL = <<<EOF
							SELECT bankTypeId
							, bankName
							, status
							FROM `banktype`
							WHERE bankName like '%$conditionBankNameLike%'
EOF;
// $this->utils->debug_log('2608.SQL:', $SQL);
							$q=$this->vipsetting->db->query($SQL);
							$_rows = $q->result_array();

							if( ! empty($_rows) ){
// $this->utils->debug_log('2608._rows.count:', count($_rows));
// $this->utils->debug_log('2608._rows:', $_rows);
								if(count($_rows) == 1){
									// re-assign for update
									$is_ready_fill = 1;
									$fill_banktype_id = $_rows[0]['bankTypeId'];
								} else {
									// more 1 row by a lang string, more_1_row_by_a_lang_string
									$report_info['more_1_row_by_a_lang_string']['count']++;
									$report_info['more_1_row_by_a_lang_string']['list'][] = $row['affiliatePaymentId']; // affiliatepayment
								}
							}else{
								// Not found by a lang string, not_found_by_a_lang_string
								$report_info['not_found_by_a_lang_string']['count']++;
								$report_info['not_found_by_a_lang_string']['list'][] = $row['affiliatePaymentId']; // affiliatepayment
							} // EOF if( ! empty($_rows) ){...

						} // EOF if( ! empty($is_use_lang_mapping) ){...

						if( ! empty($is_ready_fill) && ! empty($fill_banktype_id) ){
							// update banktype_id to affiliatepayment.banktype_id by affiliatePaymentId and NULL banktype_id
							$tableName = 'affiliatepayment';
							$data = [];
							$data['banktype_id'] = $fill_banktype_id;
							$_db->where('affiliatePaymentId', $_affiliatePaymentId);
							$_db->where('banktype_id IS NULL', null, false);
							$qry = $_db->update($tableName, $data);
							if($qry===false){
								$affected_rows = false;
							}else{
								$affected_rows = $_db->affected_rows();
							}
							unset($qry);

							if( empty($affected_rows) ){
								// update failed, update_failed
								$report_info['update_failed']['count']++;
								$report_info['update_failed']['list'][] = $row['affiliatePaymentId']; // affiliatepayment
							}else{
								$report_info['update_completed']['count']++;
							}

						} // EOF if( ! empty($is_ready_fill) && ! empty($fill_banktype_id) ){...

						$success = true;
						return $success;
				}); // EOF vipsetting->runDBTransOnly()
			}// EOF foreach ($rows as $indexNumber => $row) {...

			$this->utils->debug_log('after fixed report_info:', $report_info);

			if( ! empty($report_info['others_issue_case']['list'] ) ){
				$_rows = $this->getNullBanktypeDataById($report_info['others_issue_case']['list'], $_result_count);
				$report_info['others_issue_case']['rows'] = $_rows;
			}
			if( ! empty($report_info['more_1_row_by_a_lang_string']['list'] ) ){
				$_rows = $this->getNullBanktypeDataById($report_info['more_1_row_by_a_lang_string']['list'], $_result_count);
				$report_info['more_1_row_by_a_lang_string']['rows'] = $_rows;
			}
			if( ! empty($report_info['not_found_by_a_lang_string']['list'] ) ){
				$_rows = $this->getNullBanktypeDataById($report_info['not_found_by_a_lang_string']['list'], $_result_count);
				$report_info['not_found_by_a_lang_string']['rows'] = $_rows;
			}
			if( ! empty($report_info['update_failed']['list'] ) ){
				$_rows = $this->getNullBanktypeDataById($report_info['update_failed']['list'], $_result_count);
				$report_info['update_failed']['rows'] = $_rows;
			}
			$this->utils->debug_log('after fixed report_info with rows:', $report_info);
		} // EOF if($result_count > 0){...


	}// EOF batch_fill_field_banktype_id_info_in_affiliatepayment
	/**
	 * Get the Null banktype_id data for batch patch.
	 *
	 * @param array $affiliatePaymentId_list
	 * @param integer $result_count the amount of the result.
	 * @return void
	 */
	public function getNullBanktypeDataById($affiliatePaymentId_list = [], &$result_count = null){
		$this->load->model(['vipsetting']);
		$affiliatePaymentIdsStr = '1';
		if( ! empty($affiliatePaymentId_list) ){
			$affiliatePaymentIdsStr = ' affiliatePaymentId IN ('. implode(', ', $affiliatePaymentId_list ). ') ';
		}
		// Requireds: affiliatePaymentId, bankName
		// reply to ITDESK: affiliateId, accountNumber, accountInfo
		$SQL = <<<EOF
		SELECT affiliatePaymentId
		, affiliateId
		, accountNumber
		, accountInfo
		, bankName
		, status
		FROM affiliatepayment
		WHERE banktype_id IS NULL
		AND $affiliatePaymentIdsStr
EOF;
		$q=$this->vipsetting->db->query($SQL);
		$rows = $q->result_array();
		$result_count = $q->num_rows();
		unset($q);
		return $rows;
	}
	/**
	 * extract crush langs string to list
	 * Ref. to https://regex101.com/r/puGd2x/1
	 *
	 * @param string $_lang The _json prefix string of json format in multi-lang.
	 * @param array &$lang_list The key-value Array as array[lang id] = lang string for caller.
	 * @return array The extracted array
	 */
	public function extract_crush_langs_string_to_list($_lang, &$lang_list = null){
		$re = '/\"(?P<lang_id>\d)+\":\"(?P<lang_string>[^\r\n\t\f\v\"]+)\"/m';

		preg_match_all($re, $_lang, $matches, PREG_SET_ORDER, 0);

		// Print the entire match result
		// var_dump($matches);
		$extracted_list = [];
		$lang_list = [];
		if( !empty($matches) ){
			foreach($matches as $matche){
				$extracted = [];
				$extracted['id'] = $matche['lang_id'];
				$extracted['lang'] = $matche['lang_string'];
				if( !empty($matche['lang_id']) &&  !empty($matche['lang_string']) ){
					$extracted_list[] = $extracted;
					$lang_list[$matche['lang_id']] = $matche['lang_string'];
				}
			}
		}

		return $extracted_list;
	} // EOF extract_crush_langs_string_to_list


    /**
     * The batch patch is used the field, "playerpromo.vip_level_info" to extractive a copy of the fields, "groupName" and "vipLevel".
     * The copy of fields, "groupName" and "vipLevel", them will used in the column sorting of the data_tables() plugin of promo request list of SBE.
     *
     * The related commands,
     * ```
     * sudo /bin/bash admin/shell/command.sh batch_extractive_copy_group_level_FVLIIP >> ./logs/command_batch_extractive_copy_group_level_FVLIIP.log &
     * ```
     * P.S. FVLIIP = from_vip_level_info_in_playerpromo
     * Orignal function name, batch_extractive_copy_group_level_from_vip_level_info_in_playerpromo
     *
     * @return void
     */
    public function batch_extractive_copy_group_level_FVLIIP(){
        $this->load->model(['vipsetting']);

		// total_cashback_player_game_daily
		// playerpromo
		$SQL = <<<EOF
		SELECT playerpromoId, playerId, vip_level_info
		FROM playerpromo
		WHERE group_name_on_created IS NULL
        AND vip_level_info IS NOT NULL
EOF;
		$q=$this->vipsetting->db->query($SQL);
		$rows = $q->result_array();
		$result_count = $q->num_rows();
		$report_info = [];
		$report_info['issue_counter'] = 0; // total issue counter
		$report_info['added_count'] = 0; // added the field counter
		$report_info['failed_count'] = 0; // patch failed counter
		$report_info['No affected rows'] = 0; // issue case, "No affected rows" counter
		$report_info['The player has Empty vip_level_info'] = 0; // issue case, "The player has Empty vip_level_info" counter
		$report_info['failed_id_list'] = []; // all patch failed id list.

        $this->utils->debug_log('will fix result_count:', $result_count);
        $report_info['issue_counter'] = 0;
		if($result_count > 0){
			$total_rows = count($rows);
			$report_info['issue_counter'] = $total_rows;

			$this->utils->debug_log('total need fix issue_counter:', $report_info['issue_counter']);

			foreach ($rows as $indexNumber => $row) {
				$player_id = $row['playerId'];
                $vip_level_info = $row['vip_level_info'];
                $playerpromoId = $row['playerpromoId'];
				// $playerCounter = $row['playerCounter'];
				$this->utils->debug_log('will fix indexNumber:', $indexNumber, 'total_rows:',$total_rows, 'player_id:', $player_id);

				$controller = $this;
				$result = null; // for collect the result detail.
				$success = $this->vipsetting->runDBTransOnly( $this->vipsetting->db
					, $result
					,  function($_db, &$_result) use ($playerpromoId, $player_id, $controller, &$report_info, $vip_level_info) {

						$affected_rows = null;
						$_db->_reset_select();
						$tableName = 'playerpromo';
						if( ! empty($vip_level_info) ){
                            // Not Empty vip_level_info
							$data = [];
							$_vip_level_info = json_decode($vip_level_info, true);
                            if(!empty($_vip_level_info['vipsetting']['groupName'])){
                                $group_name_on_created = $_vip_level_info['vipsetting']['groupName'];
                                $data['group_name_on_created'] = $group_name_on_created;
                            }
                            if(!empty( $_vip_level_info['vipsettingcashbackrule']['vipLevel'])){
                                $vip_level_on_created = $_vip_level_info['vipsettingcashbackrule']['vipLevel'];
                                $data['vip_level_on_created'] = $vip_level_on_created;
                            }

                            $affected_rows = null;
                            $_db->where('playerpromoId', $playerpromoId);
                            $_db->where('vip_level_info IS NOT NULL', null, false);
                            if( !empty($data)){
                                $qry = $_db->update($tableName, $data);
                                if($qry===false){
                                    $affected_rows = false;
                                }else{
                                    $affected_rows = $_db->affected_rows();
                                }
                                unset($qry);
                            }

							// collect details
							if( ! empty($affected_rows) ){
								$report_info['added_count'] += $affected_rows;
								$_result['bool'] = true; // will endTransWithSucc
							}else{
								$_result['msg'] = 'No affected rows';
								$_result['bool'] = false; // will endTransWithSucc

								$_result['affected_rows'] = 0;
								$_db->select('playerpromoId as id')->from($tableName);
								$_rows = $controller->vipsetting->runMultipleRowArray($_db);
								$_result['issue_rows'] = $_rows;
								$count = 0;
								if( ! empty($_rows) ){
									$count = count($_rows);
									foreach($_rows as $_row){
										array_push($report_info['failed_id_list'], $_row['id']);
									}
								}
								$report_info['failed_count'] += $count;
								$report_info[$_result['msg']] += $count;
							}
							$_result['affected_rows'] = $affected_rows;
							$_result['player_id'] = $player_id;
							// $_result['bool'] = true; // will endTransWithSucc

						}else{
                            // Empty vip_level_info
                            array_push($report_info['failed_id_list'], $playerpromoId);

                            // collect details
							$_result['msg'] = 'The id has Empty vip_level_info';
							$_result['bool'] = false; // will rollbackTrans
							$_result['affected_rows'] = 0;
							$_result['issue_rows'] = $rows;
							// $_result['vip_level_info'] = $vip_level_info;
							$_result['playerpromoId'] = $playerpromoId;
							$report_info['failed_count'] += $count;
							$report_info[$_result['msg']] += $count;
						}
                        $this->utils->debug_log('3081._result.bool:', $_result['bool']);
						return $_result['bool'];
				}); // EOF vipsetting->runDBTransOnly()

				if($success){
					$this->utils->debug_log('batch_extractive_copy_group_level_from_vip_level_info_in_playerpromo.OK.affected_rows:', $result['affected_rows'], 'player_id:', $result['player_id'] );
				}else{
					$this->utils->debug_log('batch_extractive_copy_group_level_from_vip_level_info_in_playerpromo.NG.result:',$result);
				}
			} // EOF foreach ($rows as $row) {...

			$this->utils->debug_log('after fixed, added_count:', $report_info['added_count'], 'failed_count:', $report_info['failed_count'], 'total issue_counter:', $report_info['issue_counter'], $report_info);
            $this->utils->debug_log('after fixed, report_info:', $report_info);
		} // EOF if($result_count > 0){...
    } // EOF batch_extractive_copy_group_level_from_vip_level_info_in_playerpromo
    //
	/**
	 * batch_fill_field_vip_level_info_in_playerpromo
	 *
	 * sudo /bin/bash admin/shell/command.sh batch_fill_field_vip_level_info_in_playerpromo >> ./logs/command_batch_fill_field_vip_level_info_in_playerpromo.log &
	 * nohup sudo /bin/bash admin/shell/command.sh batch_fill_field_vip_level_info_in_playerpromo >> ./logs/command_batch_fill_field_vip_level_info_in_playerpromo.log 2>&1 &
	 *
	 * @return void
	 */
	public function batch_fill_field_vip_level_info_in_playerpromo(){
		$this->load->model(['vipsetting']);

		// total_cashback_player_game_daily
		// playerpromo
		$SQL = <<<EOF
		SELECT playerId, count(playerId) as playerCounter
		FROM playerpromo
		WHERE vip_level_info IS NULL
		GROUP BY playerId
EOF;
		$q=$this->vipsetting->db->query($SQL);
		$rows = $q->result_array();
		$result_count = $q->num_rows();
		$report_info = [];
		$report_info['issue_counter'] = 0; // total issue counter
		$report_info['added_count'] = 0; // added the field counter
		$report_info['failed_count'] = 0; // patch failed counter
		$report_info['No affected rows'] = 0; // issue case, "No affected rows" counter
		$report_info['The player has Empty vip_level_info'] = 0; // issue case, "The player has Empty vip_level_info" counter
		$report_info['failed_id_list'] = []; // all patch failed id list.

$this->utils->debug_log('will fix result_count:', $result_count);
		if($result_count > 0){
			$total_rows = count($rows);

			$report_info['issue_counter'] = 0;
			foreach ($rows as $indexNumber => $row) {
				$report_info['issue_counter'] += $row['playerCounter'];
			}
			$this->utils->debug_log('total need fix issue_counter:', $report_info['issue_counter']);

			foreach ($rows as $indexNumber => $row) {
				$player_id = $row['playerId'];
				$playerCounter = $row['playerCounter'];
				$this->utils->debug_log('will fix indexNumber:', $indexNumber, 'total_rows:',$total_rows, 'player_id:', $player_id);

				$controller = $this;
				$result = null; // for collect the result detail.
				$success = $this->vipsetting->runDBTransOnly( $this->vipsetting->db
					, $result
					,  function($_db, &$_result) use ($player_id, $controller, &$report_info) {

						$vip_level_info = $controller->vipsetting->getVipGroupLevelInfoByPlayerId($player_id);
						$affected_rows = null;
						$_db->_reset_select();
						$tableName = 'playerpromo';
						if( ! empty($vip_level_info) ){
							$data = [];
							$data['vip_level_info'] = json_encode($vip_level_info);
							$_db->where('playerId', $player_id);
							$_db->where('vip_level_info IS NULL', null, false);
							$qry = $_db->update($tableName, $data);
							if($qry===false){
								$affected_rows = false;
							}else{
								$affected_rows = $_db->affected_rows();
							}
							unset($qry);

							// collect details
							if( ! empty($affected_rows) ){
								$report_info['added_count'] += $affected_rows;
								$_result['bool'] = true; // will endTransWithSucc
							}else{
								$_result['msg'] = 'No affected rows';
								$_result['bool'] = false; // will endTransWithSucc

								$_result['affected_rows'] = 0;
								$_db->select('playerpromoId as id')->from($tableName);
								$rows = $controller->vipsetting->runMultipleRowArray($_db);
								$_result['issue_rows'] = $rows;
								$count = 0;
								if( ! empty($rows) ){
									$count = count($rows);
									foreach($rows as $row){
										array_push($report_info['failed_id_list'], $row['id']);
									}
								}
								$report_info['failed_count'] += $count;
								$report_info[$_result['msg']] += $count;
							}
							$_result['affected_rows'] = $affected_rows;
							$_result['player_id'] = $player_id;
							// $_result['bool'] = true; // will endTransWithSucc

						}else{

							$_db->select('playerpromoId as id')->from($tableName);
							$_db->where('playerId', $player_id);
							$rows = $controller->vipsetting->runMultipleRowArray($_db);
							$count = 0;
							if( ! empty($rows) ){
								$count = count($rows);
								foreach($rows as $row){
									array_push($report_info['failed_id_list'], $row['id']);
								}
							}

							// collect details
							$_result['msg'] = 'The player has Empty vip_level_info';
							$_result['bool'] = false; // will rollbackTrans
							$_result['affected_rows'] = 0;
							$_result['issue_rows'] = $rows;
							$_result['vip_level_info'] = $vip_level_info;
							$_result['player_id'] = $player_id;
							$report_info['failed_count'] += $count;
							$report_info[$_result['msg']] += $count;
						}
						return $_result['bool'];
				}); // EOF vipsetting->runDBTransOnly()

				if($success){
					$this->utils->debug_log('batch_fill_field_vip_level_info_in_playerpromo.OK.affected_rows:', $result['affected_rows'], 'player_id:', $result['player_id'] );
				}else{
					$this->utils->debug_log('batch_fill_field_vip_level_info_in_playerpromo.NG.result:',$result);
				}

			} // EOF foreach ($rows as $row) {...

			$this->utils->debug_log('after fixed added_count:', $report_info['added_count'], 'failed_count:', $report_info['failed_count'], 'total issue_counter:', $report_info['issue_counter'], $report_info);
		} // EOF if($result_count > 0){...

	} // EOF batch_fill_field_vip_level_info_in_playerpromo


	public function sync_cashback_to_bet_list_mapping(){

		$this->latest_mapping_id_list = [];
		// SCTBLM4TPGHWC = sync_cashback_to_bet_list_mapping4TotalPlayerGameHourWithChunk
		$this->latest_mapping_id_list['SCTBLM4TPGHWC'] = [];
		$this->latest_mapping_id_list['SCTBLM4TPGHWC']['total_player_game_hour'] = 0;
		$this->latest_mapping_id_list['SCTBLM4TPGHWC']['game_logs'] = 0;
		// SCTBLM4CRWC = sync_cashback_to_bet_list_mapping4CashbackReportWithChunk
		$this->latest_mapping_id_list['SCTBLM4CRWC'] = [];
		$this->latest_mapping_id_list['SCTBLM4CRWC']['total_cashback_player_game_daily'] = 0;
		// dynamic initialization for total_cashback_player_game_daily_xxx in sync_cashback_to_bet_list_mapping4TotalPlayerGameHourWithChunk()

		$this->is_done = [];
		/// SCTBLM4TPGHWC = sync_cashback_to_bet_list_mapping4TotalPlayerGameHourWithChunk
		$this->is_done['SCTBLM4TPGHWC'] = [];
		// initialied as 0, true OR false for result.
		$this->is_done['SCTBLM4TPGHWC']['total_player_game_hour'] = 0;
		$this->is_done['SCTBLM4TPGHWC']['game_logs'] = 0;
		// SCTBLM4CRWC = sync_cashback_to_bet_list_mapping4CashbackReportWithChunk
		$this->is_done['SCTBLM4CRWC'] = [];
		$this->is_done['SCTBLM4CRWC']['total_cashback_player_game_daily'] = 0;
		// dynamic initialization for total_cashback_player_game_daily_xxx in sync_cashback_to_bet_list_mapping4TotalPlayerGameHourWithChunk()

		$chunk_amount = 1000;
		$maxWhileAround = 9999999;
		$whileIndex = 0;

		$rlt = [];
		$rlt['totalCount'] = 0;
		$rlt['successCount'] = 0;
		$rlt['noAffectedCount'] = 0;
		$rlt['failedCount'] = 0;
		$rlt['failedMappingIdList'] = [];

		$theIssueCounter = $this->sync_cashback_to_bet_list_mapping4queryIssueData(0);
		$theIssueCounterBefore = $theIssueCounter;

		$this->utils->debug_log('OGP-27272.2994.theIssueCounter:', $theIssueCounter);


		while ( ! empty($theIssueCounter) && $whileIndex < $maxWhileAround ){

			// ex, total_cashback_player_game_daily_634ccfcba54f6
			//
			// SCTBLM4CRWC = sync_cashback_to_bet_list_mapping4CashbackReportWithChunk
			$cashback_report_tablename_list = $this->utils->getConfig('cashback_report_tablename_list4SCTBLM4CRWC');
			if( empty($cashback_report_tablename_list) ){
				$cashback_report_tablename_list[] = 'total_cashback_player_game_daily';
			}
			//
			foreach ( $cashback_report_tablename_list as $cashback_report_tablename ){

				// $cashback_report_tablename = 'total_cashback_player_game_daily';
				$rlt4CashbackReport = $this->sync_cashback_to_bet_list_mapping4CashbackReportWithChunk($chunk_amount, $cashback_report_tablename);
				// $this->utils->debug_log('OGP-27272.2935', 'rlt:', $rlt);
				// {"message":"OGP-27272.2932","context":["rlt:",{"successCount":998,"noAffectedCount":0,"failedCount":2,"totalCount":1000,"failedMappingIdList":["802448aaaaaa","802449aaaaaa"],"isTotalInSuccess":false}],"level":100,"level_name":"DEBUG","channel":"default-og","datetime":"2022-10-25T16:55:34+08:00","trace":"../admin/shell/ci_cli.php:195@require_once > ","extra":{"tags":{"request_id":"b773c078fe320b2166f436b5149e43a9","env":"live.og_local","version":"6.178.01.001","hostname":"default-og"},"process_id":23845,"memory_peak_usage":"32.25 MB","memory_usage":"32.25 MB"}}
				$rlt['totalCount'] += $rlt4CashbackReport['totalCount']; // the amoun of this time
				$rlt['successCount'] += $rlt4CashbackReport['successCount'];
				$rlt['noAffectedCount'] += $rlt4CashbackReport['noAffectedCount'];
				$rlt['failedCount'] += $rlt4CashbackReport['failedCount'];
				//
				if( ! empty($rlt4CashbackReport['failedMappingIdList']) ){
					foreach($rlt4CashbackReport['failedMappingIdList'] as $failedMappingId) {
						array_push($rlt['failedMappingIdList'], $failedMappingId);
					}
				}
			}

			$bet_logs_tablename = 'total_player_game_hour';
			$rlt4TotalPlayerGameHour = $this->sync_cashback_to_bet_list_mapping4TotalPlayerGameHourWithChunk($chunk_amount, $bet_logs_tablename);
			// $this->utils->debug_log('OGP-27272.2927', 'rlt:', $rlt);
			// // {"message":"OGP-27272.2927","context":["rlt:",{"successCount":1000,"totalCount":1000}],"level":100,"level_name":"DEBUG","channel":"default-og","datetime":"2022-10-25T16:27:12+08:00","trace":"../admin/shell/ci_cli.php:195@require_once > ","extra":{"tags":{"request_id":"c96283b9f7f3d8823a2c7dd2285810fd","env":"live.og_local","version":"6.178.01.001","hostname":"default-og"},"process_id":23708,"memory_peak_usage":"32.25 MB","memory_usage":"32.25 MB"}}
			// // {"message":"OGP-27272.2927","context":["rlt:",{"successCount":12,"totalCount":1000,"isTotalInSuccess":false}],"level":100,"level_name":"DEBUG","channel":"default-og","datetime":"2022-10-25T16:30:26+08:00","trace":"../admin/shell/ci_cli.php:195@require_once > ","extra":{"tags":{"request_id":"050f94440291e0bdf28a690cb94f00c0","env":"live.og_local","version":"6.178.01.001","hostname":"default-og"},"process_id":23722,"memory_peak_usage":"32.25 MB","memory_usage":"32.25 MB"}}
			$rlt['totalCount'] += $rlt4TotalPlayerGameHour['totalCount']; // the amoun of this time
			$rlt['successCount'] += $rlt4TotalPlayerGameHour['successCount'];
			$rlt['noAffectedCount'] += $rlt4TotalPlayerGameHour['noAffectedCount'];
			$rlt['failedCount'] += $rlt4TotalPlayerGameHour['failedCount'];
			//
			if( ! empty($rlt4TotalPlayerGameHour['failedMappingIdList']) ){
				foreach($rlt4TotalPlayerGameHour['failedMappingIdList'] as $failedMappingId) {
					array_push($rlt['failedMappingIdList'], $failedMappingId);
				}
			}

			$bet_logs_tablename = 'game_logs';
			$rlt4GameLogs = $this->sync_cashback_to_bet_list_mapping4TotalPlayerGameHourWithChunk($chunk_amount, $bet_logs_tablename);
			// $this->utils->debug_log('OGP-27272.2932', 'rlt:', $rlt);
			// {"message":"OGP-27272.2932","context":["rlt:",{"successCount":20,"noAffectedCount":0,"failedCount":0,"totalCount":20,"isTotalInSuccess":true}],"level":100,"level_name":"DEBUG","channel":"default-og","datetime":"2022-10-25T17:00:29+08:00","trace":"../admin/shell/ci_cli.php:195@require_once > ","extra":{"tags":{"request_id":"ab3dbc48cdde7c1e43b658d789c18be3","env":"live.og_local","version":"6.178.01.001","hostname":"default-og"},"process_id":23874,"memory_peak_usage":"32.25 MB","memory_usage":"32.25 MB"}}
			$rlt['totalCount'] += $rlt4GameLogs['totalCount']; // the amoun of this time
			$rlt['successCount'] += $rlt4GameLogs['successCount'];
			$rlt['noAffectedCount'] += $rlt4GameLogs['noAffectedCount'];
			$rlt['failedCount'] += $rlt4GameLogs['failedCount'];
			//
			if( ! empty($rlt4GameLogs['failedMappingIdList']) ){
				foreach($rlt4GameLogs['failedMappingIdList'] as $failedMappingId) {
					array_push($rlt['failedMappingIdList'], $failedMappingId);
				}
			}

			// refresh $theIssueCounter
			$theIssueCounter = $this->sync_cashback_to_bet_list_mapping4queryIssueData(); // limit 1 for confirm that the next is exists?

			$is_all_done4SCTBLM4TPGHWC = $this->utils->notEmptyValuesInArray(array_keys($this->is_done['SCTBLM4TPGHWC']), $this->is_done['SCTBLM4TPGHWC']);
			$is_all_done4SCTBLM4CRWC = $this->utils->notEmptyValuesInArray(array_keys($this->is_done['SCTBLM4CRWC']), $this->is_done['SCTBLM4CRWC']);

			$this->utils->debug_log('OGP-27272.2993.rlt.subCounts:'
				, 'whileIndex:', $whileIndex
				, 'theIssueCounter:', $theIssueCounter
				// , 'latest_mapping_id_list.SCTBLM4TPGHWC.total_player_game_hour', $this->latest_mapping_id_list['SCTBLM4TPGHWC']['total_player_game_hour']
				// , 'is_done.SCTBLM4TPGHWC.total_player_game_hour', $this->is_done['SCTBLM4TPGHWC']['total_player_game_hour']
				, 'latest_mapping_id_list.SCTBLM4CRWC.total_cashback_player_game_daily', $this->latest_mapping_id_list['SCTBLM4CRWC']['total_cashback_player_game_daily']
				, 'is_done.SCTBLM4CRWC.total_cashback_player_game_daily', $this->is_done['SCTBLM4CRWC']['total_cashback_player_game_daily']
				, 'this.is_done', $this->is_done
				, 'totalCount:', $rlt['totalCount']
				, 'successCount:', $rlt['successCount']
				, 'noAffectedCount:', $rlt['noAffectedCount']
				, 'failedCount:', $rlt['failedCount']
				, 'is_all_done4SCTBLM4TPGHWC', $is_all_done4SCTBLM4TPGHWC
				, 'is_all_done4SCTBLM4CRWC', $is_all_done4SCTBLM4CRWC
			);

			if( $is_all_done4SCTBLM4TPGHWC && $is_all_done4SCTBLM4CRWC){
				break; // exit the while loop
			}

			$whileIndex++;
		}

		$this->utils->debug_log('OGP-27272.2994.rlt.processedCounts:'
								, 'totalCount:', $rlt['totalCount']
								, 'successCount:', $rlt['successCount']
								, 'noAffectedCount:', $rlt['noAffectedCount']
								, 'failedCount:', $rlt['failedCount']
							);
		$this->utils->debug_log('OGP-27272.2994.rlt.failedMappingIdList:', $rlt['failedMappingIdList']);

		$this->utils->debug_log('OGP-27272.2994.rlt:', $rlt
								, 'theIssueCounterBefore:', $theIssueCounterBefore
								, 'processedCount:', $rlt['totalCount']
								, 'processedSuccessCount:', $rlt['successCount']
								, 'processedNoAffectedCount:', $rlt['noAffectedCount']
								, 'processedFailedCount:', $rlt['failedCount']
							);
	}

	public function sync_cashback_to_bet_list_mapping4queryIssueData($limit_amount = 1){
		$this->load->model(['vipsetting']);
		$limit_query = '';
		if( ! empty( $chunk_amount) ){
			$limit_query .= " LIMIT $chunk_amount ";
		}

		$SQL = <<<EOF
				SELECT count(mapping.id) as count
				FROM `cashback_to_bet_list_mapping` as `mapping`
				WHERE mapping.player_id = 0
				$limit_query
EOF;
		$q=$this->vipsetting->db->query($SQL);
		$rows = $q->result_array();
		$q->free_result();

		return $rows[0]['count'];
	}

	// SCTBLM4TPGHWC = sync_cashback_to_bet_list_mapping4TotalPlayerGameHourWithChunk
	public function sync_cashback_to_bet_list_mapping4TotalPlayerGameHourWithChunk($chunk_amount = 1000, $bet_logs_tablename = 'total_player_game_hour'){
		$this->load->model(['vipsetting']);
		// -- total_player_game_hour
		// UPDATE cashback_to_bet_list_mapping mapping
		// INNER JOIN total_player_game_hour bet_logs
		// 	ON bet_logs.id = mapping.bet_source_id
		// 	AND bet_source_table = 'total_player_game_hour'
		// 	AND mapping.player_id = 0
		// SET mapping.player_id = bet_logs.player_id
		// ;

		$limit_query = '';
		if( ! empty( $chunk_amount) ){
			$limit_query .= " LIMIT $chunk_amount ";
		}

		$conditionOfmapping_id = '';
		// SCTBLM4TPGHWC = sync_cashback_to_bet_list_mapping4TotalPlayerGameHourWithChunk
		if( ! is_null( $this->latest_mapping_id_list['SCTBLM4TPGHWC'][$bet_logs_tablename] ) ){
			$latest_mapping_id = $this->latest_mapping_id_list['SCTBLM4TPGHWC'][$bet_logs_tablename];
			$conditionOfmapping_id .= " AND mapping.id > $latest_mapping_id ";
		}




		$SQL = <<<EOF
				SELECT mapping.id as mapping_id
				, bet_logs.player_id as bet_logs_player_id
				FROM `cashback_to_bet_list_mapping` as `mapping`
				INNER JOIN `$bet_logs_tablename` as `bet_logs`
					ON bet_logs.id = mapping.bet_source_id
						AND bet_source_table = '$bet_logs_tablename'
						AND mapping.player_id = 0
				WHERE mapping.player_id = 0
				$conditionOfmapping_id
				ORDER BY `mapping`.`id` ASC
				$limit_query
EOF;

		if( $this->is_done['SCTBLM4TPGHWC'][$bet_logs_tablename] !== true ){
			$q=$this->vipsetting->db->query($SQL);
			$rows = $q->result_array();
			$q->free_result();
		}else{
			$rows = [];
		}

		$rowsCount = count($rows);

		$result = null; // for collect the result detail.
		$report_info = [];
		$report_info['successCount'] = 0;
		$report_info['noAffectedCount'] = 0;
		$report_info['failedCount'] = 0; // for Exception

		$report_info['totalCount'] = $rowsCount;
		$controller = $this;
		// $success = $this->vipsetting->runDBTransOnly( $this->vipsetting->db
		// 		, $result
		// 		,  function($_db, &$_result) use ($rows, $controller, &$report_info) {
		//
		// }); // EOF  $this->vipsetting->runDBTransOnly(...

		if( $rowsCount ){
			$this->is_done['SCTBLM4TPGHWC'][$bet_logs_tablename] = false;
		}else{
			$this->is_done['SCTBLM4TPGHWC'][$bet_logs_tablename] = true;
		}


		$_db = $this->vipsetting->db;
		foreach ($rows as $indexNumber => $row) {

			$_mapping_id = $row['mapping_id'];
			$_mapping_player_id =$row['bet_logs_player_id'];
$this->utils->debug_log('OGP-27272.3170._mapping_id', $_mapping_id);
			// /// Done Test Exception
			// if($indexNumber == 12 || $indexNumber == 13 ){
			// 	$_mapping_id .= 'aaaaaa';
			// }
			try {
				if( ! empty($_mapping_player_id) ){
					$updateSQL = <<<EOF
					UPDATE cashback_to_bet_list_mapping
					SET player_id = $_mapping_player_id
					WHERE id = $_mapping_id
EOF;
					$query = $_db->query($updateSQL, []);
					$affected_rows = $_db->affected_rows();
// $this->utils->debug_log('OGP-27272.3116.row: ', $row, 'affected_rows', $affected_rows); die();
				}
				if($affected_rows){
					$report_info['successCount']++;
				}else{
					$report_info['noAffectedCount']++;
					$report_info['noAffectedMappingIdList'][] = $_mapping_id;
				}

				// SCTBLM4TPGHWC = sync_cashback_to_bet_list_mapping4TotalPlayerGameHourWithChunk
				/// update latest_mapping_id_list['SCTBLM4TPGHWC'][$bet_logs_tablename]
				if( ! is_null( $this->latest_mapping_id_list['SCTBLM4TPGHWC'][$bet_logs_tablename] ) ){
					$this->latest_mapping_id_list['SCTBLM4TPGHWC'][$bet_logs_tablename] = $_mapping_id;
				}

			} catch (Exception $e) {
				$formatStr = 'Exception in sync_cashback_to_bet_list_mapping4TotalPlayerGameHourWithChunk(). (%s)';
				$controller->utils->error_log( sprintf( $formatStr, $e->getMessage() ) );
				// $success = false;
				$report_info['failedCount']++;
				$report_info['failedMappingIdList'][] = $_mapping_id;
			} // EOF try{...
// 27272.3153.latest_mapping_id_list.SCTBLM4TPGHWC.total_player_game_hour
$this->utils->debug_log('OGP-27272.3153.latest_mapping_id_list.SCTBLM4TPGHWC.'.$bet_logs_tablename.': ', $this->latest_mapping_id_list['SCTBLM4TPGHWC'][$bet_logs_tablename]);
		} // EOF foreach ($rows as $row) {...

		// $this->utils->debug_log('OGP-27272.2988', 'report_info:', $report_info);

		$report_info['isTotalInSuccess']  = null;
		if( $report_info['successCount'] == $report_info['totalCount']){
			$report_info['isTotalInSuccess']  = true;
		}else{
			$report_info['isTotalInSuccess']  = false;
		}

		$rows = []; // free mem.

		return $report_info;
	} // EOF sync_cashback_to_bet_list_mapping4TotalPlayerGameHourWithChunk

	// SCTBLM4CRWC = sync_cashback_to_bet_list_mapping4CashbackReportWithChunk
	public function sync_cashback_to_bet_list_mapping4CashbackReportWithChunk($chunk_amount = 1000, $cashback_report_tablename = 'total_cashback_player_game_daily'){
		$this->load->model(['vipsetting']);
		// total_cashback_player_game_daily

/*
		-- reset
		UPDATE cashback_to_bet_list_mapping mapping
		SET mapping.player_id = 0;

		-- total_cashback_player_game_daily_xxxx v
		UPDATE `cashback_to_bet_list_mapping` SET
		`player_id` = '0'
		WHERE `id` = '2';

		-- total_cashback_player_game_daily v
		UPDATE cashback_to_bet_list_mapping mapping
		INNER JOIN total_cashback_player_game_daily cashback_report
			ON cashback_report.id = mapping.cashback_id
			AND cashback_table = 'total_cashback_player_game_daily'
			AND mapping.player_id = 0
		SET mapping.player_id = cashback_report.player_id
		;

		-- total_player_game_hour v
		UPDATE cashback_to_bet_list_mapping mapping
		INNER JOIN total_player_game_hour bet_logs
			ON bet_logs.id = mapping.bet_source_id
			AND bet_source_table = 'total_player_game_hour'
			AND mapping.player_id = 0
		SET mapping.player_id = bet_logs.player_id
		;

		-- game_logs
		UPDATE cashback_to_bet_list_mapping mapping
		INNER JOIN game_logs bet_logs
			ON bet_logs.id = mapping.bet_source_id
			AND bet_source_table = 'game_logs'
			AND mapping.player_id = 0
		SET mapping.player_id = bet_logs.player_id
		;
*/


		$limit_query = '';
		if( ! empty( $chunk_amount) ){
			$limit_query .= " LIMIT $chunk_amount ";
		}

		/// dynamic initialization for total_cashback_player_game_daily_xxx
		if( ! array_key_exists( $cashback_report_tablename, $this->latest_mapping_id_list['SCTBLM4CRWC'] ) ){
			$this->latest_mapping_id_list['SCTBLM4CRWC'][$cashback_report_tablename] = 0;
		}
		if( ! array_key_exists( $cashback_report_tablename, $this->is_done['SCTBLM4CRWC'] ) ){
			$this->is_done['SCTBLM4CRWC'][$cashback_report_tablename] = 0;
		}

		$conditionOfmapping_id = '';
		// SCTBLM4CRWC = sync_cashback_to_bet_list_mapping4CashbackReportWithChunk
		if( ! is_null( $this->latest_mapping_id_list['SCTBLM4CRWC'][$cashback_report_tablename] ) ){
			$latest_mapping_id = $this->latest_mapping_id_list['SCTBLM4CRWC'][$cashback_report_tablename];
			$conditionOfmapping_id .= " AND mapping.id > $latest_mapping_id ";
		}

		$SQL = <<<EOF
				SELECT `mapping`.id as mapping_id
				, cashback_report.player_id as cashback_report_player_id
				FROM `cashback_to_bet_list_mapping` as `mapping`
				INNER JOIN `$cashback_report_tablename` as `cashback_report`
					ON `mapping`.`cashback_id` = `cashback_report`.`id`
						AND cashback_table = '$cashback_report_tablename'
						AND mapping.player_id = 0
				WHERE mapping.player_id = 0
				$conditionOfmapping_id
				ORDER BY `mapping`.`id` ASC
				$limit_query
EOF;
		if( $this->is_done['SCTBLM4CRWC'][$cashback_report_tablename] !== true ){
			$q=$this->vipsetting->db->query($SQL);
			$rows = $q->result_array();
			$q->free_result();
		}else{
			$rows = [];
		}
		$rowsCount = count($rows);

		$result = null; // for collect the result detail.
		$report_info = [];
		$report_info['successCount'] = 0;
		$report_info['noAffectedCount'] = 0;
		$report_info['failedCount'] = 0; // for Exception
		$report_info['totalCount'] = $rowsCount; // the amount in this time
		$controller = $this;
		// $success = $this->vipsetting->runDBTransOnly( $this->vipsetting->db
		// 		, $result
		// 		,  function($_db, &$_result) use ($rows, $controller, &$report_info) {
		//
		// }); // EOF  $this->vipsetting->runDBTransOnly(...

		if( $rowsCount ){
			$this->is_done['SCTBLM4CRWC'][$cashback_report_tablename] = false;
		}else{
			$this->is_done['SCTBLM4CRWC'][$cashback_report_tablename] = true;
		}


		$_db = $this->vipsetting->db;

		foreach ($rows as $indexNumber => $row) {
			$_mapping_id = $row['mapping_id'];
			$_mapping_player_id =$row['cashback_report_player_id'];
// $this->utils->debug_log('OGP-27272.3331._mapping_id', $_mapping_id);
// 			/// Done Test Exception
// 			if($indexNumber == 12 || $indexNumber == 13 ){
// 				$_mapping_id .= 'aaaaaa';
// 			}

			try {
				if( ! empty($_mapping_player_id) ){
					$updateSQL = <<<EOF
					UPDATE cashback_to_bet_list_mapping
					SET player_id = $_mapping_player_id
					WHERE id = $_mapping_id
EOF;
					$query = $_db->query($updateSQL, []);
					$affected_rows = $_db->affected_rows();

				}
				if($affected_rows){
					$report_info['successCount']++;
				}else{
					$report_info['noAffectedCount']++;
					$report_info['noAffectedMappingIdList'][] = $_mapping_id;
				}

				// SCTBLM4CRWC = sync_cashback_to_bet_list_mapping4CashbackReportWithChunk
				/// update latest_mapping_id_list['SCTBLM4CRWC'][$cashback_report_tablename]
				if( ! is_null( $this->latest_mapping_id_list['SCTBLM4CRWC'][$cashback_report_tablename] ) ){
					$this->latest_mapping_id_list['SCTBLM4CRWC'][$cashback_report_tablename] = $_mapping_id;
				}

			} catch (Exception $e) {
				$formatStr = 'Exception in sync_cashback_to_bet_list_mapping4CashbackReportWithChunk(). (%s)';
				$controller->utils->error_log( sprintf( $formatStr, $e->getMessage() ) );
				// $success = false;
				$report_info['failedCount']++;
				$report_info['failedMappingIdList'][] = $_mapping_id;
			} // EOF try{...
$this->utils->debug_log('OGP-27272.3361.latest_mapping_id_list.SCTBLM4CRWC.'.$cashback_report_tablename.': ', $this->latest_mapping_id_list['SCTBLM4CRWC'][$cashback_report_tablename]);
		} // EOF foreach ($rows as $row) {...


		$report_info['isTotalInSuccess']  = null;
		if( $report_info['successCount'] == $report_info['totalCount']){
			$report_info['isTotalInSuccess']  = true;
		}else{
			$report_info['isTotalInSuccess']  = false;
		}

		$rows = []; // free mem.

		// $this->utils->debug_log('OGP-27272.3087', 'report_info:', $report_info);
		return $report_info;

	} // EOF sync_cashback_to_bet_list_mapping4CashbackReportWithChunk

	/**
	 * batch_fill_field_vip_level_info_in_TCPGD
	 * TCPGD = total_cashback_player_game_daily
	 *
	 * sudo /bin/bash admin/shell/command.sh batch_fill_field_vip_level_info_in_TCPGD >> ./logs/command_batch_fill_field_vip_level_info_in_TCPGD.log &
	 *
	 * nohup sudo /bin/bash admin/shell/command.sh batch_fill_field_vip_level_info_in_TCPGD >> ./logs/command_batch_fill_field_vip_level_info_in_TCPGD.log 2>&1 &
	 * @return void
	 */
	public function batch_fill_field_vip_level_info_in_TCPGD(){

		$this->load->model(['vipsetting']);

		// total_cashback_player_game_daily
		// playerpromo
		$SQL = <<<EOF
		SELECT player_id, count(player_id) as playerCounter
		FROM total_cashback_player_game_daily
		WHERE vip_level_info IS NULL
		GROUP BY player_id
EOF;
		$q=$this->vipsetting->db->query($SQL);
		$rows = $q->result_array();
		$result_count = $q->num_rows();
		$report_info = [];
		$report_info['issue_counter'] = 0; // total issue counter
		$report_info['added_count'] = 0; // added the field counter
		$report_info['failed_count'] = 0; // patch failed counter
		$report_info['No affected rows'] = 0; // issue case, "No affected rows" counter
		$report_info['The player has Empty vip_level_info'] = 0; // issue case, "The player has Empty vip_level_info" counter
		$report_info['failed_id_list'] = []; // all patch failed id list.
$this->utils->debug_log('will fix result_count:', $result_count);
		if($result_count > 0){
			$total_rows = count($rows);

			$report_info['issue_counter'] = 0;
			foreach ($rows as $indexNumber => $row) {
				$report_info['issue_counter'] += $row['playerCounter'];
			}
			$this->utils->debug_log('total need fix issue_counter:', $report_info['issue_counter']);

			foreach ($rows as $indexNumber => $row) {
				$player_id = $row['player_id'];
				$playerCounter = $row['playerCounter'];
				$this->utils->debug_log('will fix indexNumber:', $indexNumber, 'total_rows:',$total_rows, 'player_id:', $player_id );

				$controller = $this;
				$result = null; // for collect the result detail.
				$success = $this->vipsetting->runDBTransOnly( $this->vipsetting->db
					, $result
					,  function($_db, &$_result) use ($player_id, $controller, &$report_info ) { // , &$added_count, &$failed_count) {

						$vip_level_info = $controller->vipsetting->getVipGroupLevelInfoByPlayerId($player_id);
						$affected_rows = null;
						$_db->_reset_select();
						$tableName = 'total_cashback_player_game_daily';
						if( ! empty($vip_level_info) ){
							$data = [];
							$data['vip_level_info'] = json_encode($vip_level_info);
							$_db->where('player_id', $player_id);
							$_db->where('vip_level_info IS NULL', null, false);
							$qry = $_db->update($tableName, $data);
							if($qry===false){
								$affected_rows = false;
							}else{
								$affected_rows = $_db->affected_rows();
							}
							unset($qry);
							// $this->utils->debug_log('2468.affected_rows:', $affected_rows);
							// collect details
							if( ! empty($affected_rows) ){
								$report_info['added_count'] += $affected_rows;
								$_result['bool'] = true; // will endTransWithSucc
							}else{
								$_result['msg'] = 'No affected rows';
								$_result['bool'] = false; // will endTransWithSucc

								$_result['affected_rows'] = 0;
								$_db->select('id')->from($tableName);
								$rows = $controller->vipsetting->runMultipleRowArray($_db);
								$_result['issue_rows'] = $rows;
								$count = 0;
								if( ! empty($rows) ){
									$count = count($rows);
									foreach($rows as $row){
										array_push($report_info['failed_id_list'], $row['id']);
									}
								}
								$report_info['failed_count'] += $count;
								$report_info[$_result['msg']] += $count;
							}
							$_result['affected_rows'] = $affected_rows;
							$_result['player_id'] = $player_id;

						}else{
							$_db->select('id')->from($tableName);
							$_db->where('player_id', $player_id);
							$rows = $controller->vipsetting->runMultipleRowArray($_db);
							$count = 0;
							if( ! empty($rows) ){
								$count = count($rows);
								foreach($rows as $row){
									array_push($report_info['failed_id_list'], $row['id']);
								}
							}

							// collect details
							$_result['msg'] = 'The player has Empty vip_level_info';
							$_result['bool'] = false; // will rollbackTrans
							$_result['affected_rows'] = 0;
							$_result['issue_rows'] = $rows;
							$_result['vip_level_info'] = $vip_level_info;
							$_result['player_id'] = $player_id;
							$report_info['failed_count'] += $count;
							$report_info[$_result['msg']] += $count;
						}
						return $_result['bool'];
				}); // EOF vipsetting->runDBTransOnly()

				if($success){
					$this->utils->debug_log('batch_fill_field_vip_level_info_in_TCPGD.OK.affected_rows:', $result['affected_rows'], 'player_id:', $result['player_id'] );
				}else{
					$this->utils->debug_log('batch_fill_field_vip_level_info_in_TCPGD.NG.result:',$result);
				}

			} // EOF foreach ($rows as $row) {...

			$this->utils->debug_log('after fixed added_count:', $report_info['added_count'], 'failed_count:', $report_info['failed_count'], 'total issue_counter:', $report_info['issue_counter'], $report_info);

		} // EOF if($result_count > 0){...

	} // EOF batch_fill_field_vip_level_info_in_TCPGD


	/**
	 * batch_fill_field_cashback_target_in_TCPGD
	 * TCPGD = total_cashback_player_game_daily
	 *
	 * sudo /bin/bash admin/shell/command.sh batch_fill_field_cashback_target_in_TCPGD >> ./logs/command_batch_fill_field_cashback_target_in_TCPGD.log &
	 *
	 * nohup sudo /bin/bash admin/shell/command.sh batch_fill_field_cashback_target_in_TCPGD >> ./logs/command_batch_fill_field_cashback_target_in_TCPGD.log 2>&1 &
	 *
	 * @return void
	 */
	public function batch_fill_field_cashback_target_in_TCPGD(){
		$this->load->model(['vipsetting']);

		$controller = $this;
		$result = null; // for collect the result detail.
		$success = $this->vipsetting->runDBTransOnly( $this->vipsetting->db
				, $result
				,  function($_db, &$_result) use ( $controller ) { // , &$added_count, &$failed_count) {
					// update the cashback_target field from the extracted of the field,vip_level_info
					$rlt = null;
					try{
						$SQL = <<<EOF
						UPDATE total_cashback_player_game_daily
						SET cashback_target = IFNULL(JSON_UNQUOTE(json_extract(vip_level_info, '$.vipsettingcashbackrule.cashback_target')), 0)
						WHERE vip_level_info IS NOT NULL;
EOF;
						$query = $_db->query($SQL, []);
						$affected_rows = $_db->affected_rows();
						$_result['affected_rows'] = $affected_rows;
						$controller->utils->debug_log('[REPORT] had updated cashback_target by vip_level_info, affected_rows:', $affected_rows);
						$rlt = true;
					}catch(Exception $e){
						$_result['Exception'] = $e;
						$this->utils->error_log('[ERROR] batch_fill_field_cashback_target_in_TCPGD error', $e);
						$rlt = false;
					}
					return $rlt; // true will be commit, and false will be rollback
		}); // EOF $this->vipsetting->runDBTransOnly(...

		if($success){
			$this->utils->debug_log('[REPORT] OK.affected_rows:', $result['affected_rows'] );
		}else{
			$this->utils->debug_log('[REPORT] NG.result:',$result);
		}


		// detect the issue case, vip_level_info IS NULL
		$SQL = <<<EOF
		SELECT id
		, player_id
		FROM total_cashback_player_game_daily
		WHERE vip_level_info IS NULL
EOF;
 		// batch_fill_field_vip_level_info_in_TCPGD
		$q=$this->vipsetting->db->query($SQL);
		$rows = $q->result_array();
		$result_count = $q->num_rows();
		if($result_count > 0){
			$id_list = [];
			$player_list = [];
			foreach ($rows as $indexNumber => $row) {
				$id_list[] = $row['id'];
				$player_list[] = $row['player_id'];
			}

			$this->utils->debug_log('[REPORT] vip_level_info field is NULL, recommand to execute the CMD,batch_fill_field_vip_level_info_in_TCPGD, then execute again, result_count:', $result_count
				, 'the issue id list:', implode(', ',$id_list)
				, 'player_id list:', implode(', ', array_unique($player_list) )
			);

		}

		// detect the issue case, cashback_target = 0. the cause by json_extract() failed.
		$SQL = <<<EOF
		SELECT id
		, player_id
		FROM total_cashback_player_game_daily
		WHERE cashback_target = 0
EOF;
		$q=$this->vipsetting->db->query($SQL);
		$rows = $q->result_array();
		$result_count = $q->num_rows();
		if($result_count > 0){
			$id_list = [];
			$player_list = [];
			foreach ($rows as $indexNumber => $row) {
				$id_list[] = $row['id'];
				$player_list[] = $row['player_id'];
			}

			$this->utils->debug_log('[REPORT] cashback_target is Zero, Need to check the structure of the vip_level_info field, result_count:', $result_count
				, 'the issue id list:', implode(', ',$id_list)
				, 'player_id list:', implode(', ', array_unique($player_list) ) );
		}
	} // EOF function batch_fill_field_cashback_target_in_TCPGD(){...

	public function update_player_gameplatform_set_member_bet_settings($game_platform_id, $player_username = null, $is_setmemberbetsetting = true){
   		$this->utils->info_log('update_player_gameplatform_set_member_bet_settings', $game_platform_id, $player_username);

		# LOAD GAME API
		$this->api = $this->utils->loadExternalSystemLibObject($game_platform_id);
		if(!empty($player_username)){
			if($is_setmemberbetsetting){
				$updateMember = $this->api->setMemberBetSetting($player_username);
			} else {
				$updateMember = $this->api->updateMemberSetting($player_username);
			}

			if($updateMember['success']){
				$this->utils->debug_log('Player ', $player_username, ' Updated!');
			}else{
				$this->utils->error_log('Player ', $player_username, ' Update Failed!');
			}
		}

		$this->utils->debug_log('Done Updating player member settings!');
	}
    
    public function batch_update_gameplatform_set_member_bet_settings($game_platform_id, $is_setmemberbetsetting = true){
   		$this->utils->info_log('Now Query Game Accounts to Update');
   		$SQL = <<<EOD
   		SELECT
		  plyr.username
		FROM
		  `game_provider_auth` AS auth
		  LEFT JOIN player AS plyr
		    ON auth.player_id = plyr.playerId
		WHERE auth.game_provider_id = ?
		  AND auth.register = 1
EOD;
		$players = [];
   		$query = $this->db->query($SQL,array($game_platform_id));
   		if ($query && $query->num_rows() > 0) {
			$players=json_decode(json_encode($query->result()),true);
			$query->free_result();
		}

		# LOAD GAME API
		$this->api = $this->utils->loadExternalSystemLibObject($game_platform_id);
		if(!empty($players)){
			foreach ($players as $key => $player) {
				if($is_setmemberbetsetting){
					$updateMember = $this->api->setMemberBetSetting($player['username']);
				} else {
					$updateMember = $this->api->updateMemberSetting($player['username']);
				}
				if($updateMember['success']){
					$this->utils->debug_log('Player ', $player['username'], ' Updated!');
				}else{
					$this->utils->error_log('Player ', $player['username'], ' Update Failed!');
				}
			}
		}

		$this->utils->debug_log('Done Updating player member settings!');
	}

	/**
	 * overview : update player bet settings, only for debug
	 *
	 * @param int	 $gamePlatformId
	 * @param string $username
	 * @param int $sport_type
	 * @param int $market_type
	 * @param int $min_bet
	 * @param int $max_bet
	 * @param int $maxPerMatch
	 * @return mixed
	 */

	public function update_player_bet_settings($gamePlatformId, $username, $sport_type, $market_type, $min_bet, $max_bet, $maxPerMatch) {


		$api=$this->utils->loadExternalSystemLibObject($gamePlatformId);
		//$gamePlatformId = SBOBET_API;
		if(!empty($api)){
			$rlt=$api->updatePlayerBetSettingsBySportTypeAndMarketType($username, $sport_type, $market_type, $min_bet, $max_bet, $maxPerMatch);
			$this->utils->debug_log('result of updatePlayerBetSettingsBySportTypeAndMarketType', $rlt);

		}else{
			$this->utils->error_log('NOT FOUND API', $gamePlatformId);
		}

		$this->utils->debug_log('========= end update_player_bet_settings ============================');

		$this->utils->debug_log('gamePlatformId', $gamePlatformId, 'username', $username,
			'sport_type', $sport_type,'market_type', $market_type,
			'min_bet', $min_bet, 'max_bet', $max_bet,
			'maxPerMatch', $maxPerMatch, 'result', $rlt);
	}

	/**
	 * overview : batch update all players bet settings
	 * @param int $gamePlatformId
	 * @return mixed
	 */

	public function update_all_players_bet_settings($gamePlatformId, $gameUsername = _COMMAND_LINE_NULL) {
		$this->utils->debug_log("COMMAND: (update_all_players_bet_settings)");
		$this->CI->load->model('game_provider_auth');

		$cnt = 0;
		$gamePlatformId = isset($gamePlatformId)?$gamePlatformId:SBOBET_API;
		$api=$this->utils->loadExternalSystemLibObject($gamePlatformId);

		if(!empty($api)){

			if(isset($gameUsername) && $gameUsername==_COMMAND_LINE_NULL){
				$gameUsername = $this->CI->game_provider_auth->getAllGameUsernames($api->getPlatformCode());
			}else{
				$gameUsername = array($gameUsername);
			}

			foreach($gameUsername as $username) {
				$result = $api->updatePlayerBetSettings($username);
				$this->utils->debug_log("COMMAND: (update_all_players_bet_settings) username: ". $username, $result);
				$cnt ++;
			}
		}else{
			$this->utils->error_log('NOT FOUND API', $gamePlatformId);
		}

		$this->utils->info_log("COMMAND: (update_all_players_bet_settings) Update count: " . $cnt);
		return;
	}

	public function update_agent_bet_settings($gamePlatformId) {
		$api=$this->utils->loadExternalSystemLibObject($gamePlatformId);

		if(!empty($api)){
			$rlt=$api->updateAgentBetSettings();
			$this->utils->debug_log('result of processResultForUpdateAgentBetSettings', $rlt);

		}else{
			$this->utils->error_log('NOT FOUND API', $gamePlatformId);
		}

		$this->utils->debug_log('========= end update_player_bet_settings ============================');

		$this->utils->debug_log('gamePlatformId', $gamePlatformId, 'result', $rlt);
	}

	/**
	 * overview : update player bet settings for DG Game, only for debug
	 *
	 * @param string $limit_group
	 * @return mixed
	 */

	public function update_player_bet_limit_group($limit_group) {

		$api=$this->utils->loadExternalSystemLibObject(DG_API);
		if(!empty($api)){
			$rlt=$api->updatePlayerBetLimitGroup($limit_group);
			$this->utils->debug_log('result of updatePlayerBetLimitGroup', $rlt);

		}else{
			$this->utils->error_log('NOT FOUND API', $gamePlatformId);
		}

		$this->utils->debug_log('========= end update_player_bet_limit_group ============================');

		$this->utils->debug_log('update_player_bet_limit_group result', $rlt);
	}

	/**
	 * overview : update player bet settings for MTECH OG, only for debug
	 *
	 * @param string $gameprovider $video_roulette_limit_id $type
	 * @return mixed
	 */

	public function update_player_bet_limit_id($gameprovider,$video_roulette_limit_id,$type) {

		$api=$this->utils->loadExternalSystemLibObject(MTECH_OG_API);
		if(!empty($api)){
			$rlt=$api->updatePlayerBetLimitGroup($gameprovider,$video_roulette_limit_id,$type);
			$this->utils->debug_log('result of updatePlayerBetLimitGroup', $rlt);

		}else{
			$this->utils->error_log('NOT FOUND API', $gamePlatformId);
		}

		$this->utils->debug_log('========= end update_player_bet_limit_group ============================');

		$this->utils->debug_log('update_player_bet_limit_group result', $rlt);
	}

	public function delete_duplicate_system_feature(){
		$this->load->model(['system_feature']);
		$this->system_feature->clearDuplicateName();
	}

	public function export_player_password(){

		$this->load->model(array('player_model'));
		$rows = $this->player_model->getPlayerIdUsernameMap();

		$d = new DateTime();
		$csv_file =  './players_'.$d->format('Y_m_d').'.csv' ;
		$fp = fopen($csv_file, 'w');

		fputcsv($fp, ['PlayerID', 'Username', 'Password'], ',', '"');

		if($fp){
			$BOM = "\xEF\xBB\xBF";
			fwrite($fp, $BOM); //
		}else{
			$this->utils->error_log("error occured try again");
			return;
		}

		foreach($rows as $playerId => $username) {
			$player = [];
			$player['playerId'] = $playerId;
			$player['username'] = $username;
			$player['password'] = $this->player_model->getPasswordByUsername($username);
			fputcsv($fp, $player, ',', '"');
		}
	}

	public function batch_reset_agent_key_password($filename){
		$filename='/home/vagrant/Code/'.$filename;
		$this->utils->debug_log('try load file', $filename);
		$agentNameString=trim(file_get_contents($filename));
		if(empty($agentNameString)){
			$this->utils->error_log('empty agent name', $agentNameString);
			$this->utils->info_log('usage: batch_reset_agent_key_password <agent_name_file>');
			return;
		}
		$this->utils->debug_log('get agent name from file', $agentNameString);

		$agentNameList=explode(',', $agentNameString);
		if(empty($agentNameList)){
			$this->utils->error_log('empty agentNameList', $agentNameList);
			$this->utils->info_log('usage: batch_reset_agent_key_password <agent_name_file>');
			return;
		}
		$this->load->model(['agency_model']);
		$agentIdList=$this->agency_model->getIdListByAgentNameList($agentNameList);
		$this->utils->debug_log('get agent id list', $agentIdList);
		if(count($agentIdList)!=count($agentNameList)){
			$this->utils->error_log('miss something on agentIdList', count($agentIdList), count($agentNameList));
			$this->utils->info_log('usage: batch_reset_agent_key_password <agentNameList>');
			return;
		}

		$this->agency_model->resetKeyAndPasswordByIdList($agentIdList);

		$this->utils->info_log('result of reset', $agentIdList);
	}

	public function batch_hard_delete_players_info($config_key,$dbKey=_COMMAND_LINE_NULL, $dry_run = 'TRUE'){
         //specify dbkey if config->game_logs_group has keys ex. idr, cny if not let _null value
		$tables = $this->utils->getConfig('delete_player_info_settings');
		$player_ids = $this->utils->getConfig($config_key);
		if(empty($player_ids)){
			return $this->utils->error_log('No player Ids supplied');
		}
		$d = new DateTime();
		$date_str = $d->format('Y_m_d_H_i_s') . '_' . rand(1, 9999);
		$token=$config_key.'_'.$date_str ;
		if($dbKey !=_COMMAND_LINE_NULL){
			$token=$dbKey.'-'.$token;
		}
		$csv_log_headers = [];
		foreach ($tables as $table => $value) {
			array_push($csv_log_headers,$table);
		}
		$filepath=null;
		$dry_run = (strtolower($dry_run) == 'true') ? true : false;
		foreach ($player_ids as  $player_id) {
			$this->player_model->startTrans();
			$this->hard_delete_player_info($tables, $player_id, __FUNCTION__, $dbKey, $filepath, $dry_run, $token);
			$this->player_model->endTrans();
		}
		$this->utils->info_log(__FUNCTION__.'_log file '.$filepath,'dry_run:'.$dry_run);

	}


	public function hard_delete_players_by_agent_id($agent_id,$dbKey=_COMMAND_LINE_NULL,$dry_run='TRUE'){
        //specify dbkey if config->game_logs_group has keys ex. idr, cny if not let _null value
		$tables = $this->utils->getConfig('delete_player_info_settings');
		$this->load->model(['player_model']);
		$dry_run = (strtolower($dry_run) == 'true') ? true : false;
		$player_count=0;
		$total_count=0;
		$player_map= $this->player_model->getAgentPlayerIdUsernameMap($agent_id);
		if(empty($player_map)){
			return $this->utils->error_log('Empty player map');
		}else{
			$total_count =  count($player_map);
		}
		$d = new DateTime();
		$date_str = $d->format('Y_m_d_H_i_s') . '_' . rand(1, 9999);
		$token=$agent_id.'_'.$date_str;
		if($dbKey !=_COMMAND_LINE_NULL){
			$token=$dbKey.'-'.'agent_id_'.$agent_id.'-'.$date_str;
		}
		$csv_log_headers = [];
		foreach ($tables as $table => $value) {
			array_push($csv_log_headers,$table);
		}
		$filepath=null;
		$this->utils->_appendSaveDetailedResultToRemoteLog($token, __FUNCTION__, [], $filepath, true, $csv_log_headers);

		foreach ($player_map as $player_id => $player) {
			$this->player_model->startTrans();
			$this->hard_delete_player_info($tables, $player_id, __FUNCTION__, $dbKey, $filepath, $dry_run, $token);
			$this->player_model->endTrans();
			$player_count++;
			$this->utils->info_log('processed  '.$player_count.' out of '.$total_count.' at player_id:'.$player_id.' at agent_id:'.$agent_id);

		}
		$this->utils->info_log(__FUNCTION__.'_log file '.$filepath,'dry_run:'.$dry_run);

	}


	protected function hard_delete_player_info($tables, $playerId, $func, $dbKey, $filepath, $dry_run, $token){

		$table_as_columns_row = [];

		foreach ($tables as $table => &$details) {

			$all_pages_table_data = [];
			$affected_rows_arr=[];
			$affected_rows = 1;//trigger while loop
			$limit=500;//default
			$offset=0;
			$page=0;
			$save_info_to_csv = (isset($details['save_info_to_csv']) && $details['save_info_to_csv'] === true) ? true : false;


			$game_logs_details =[];

			if($table == 'game_logs_group'){

				if(isset($details[$dbKey]['game_logs'])){
					$details = $details[$dbKey]['game_logs'];
					$game_logs_details = $details;
				}else{
					$game_logs_details = $details['game_logs'];
				}
			}

			while($affected_rows > 0){

				if($table == 'game_logs_group'){

					$player_id_field = $game_logs_details['player_id_field'];
					$limit = $game_logs_details['sql_delete_limit'];
					$game_logs_original_tbls = $game_logs_details['game_logs_originals'];
					$game_logs_originals_str_for_select = implode(".external_uniqueid,", $game_logs_original_tbls).'.external_uniqueid';
					$game_logs_originals_str_for_delete = implode(",", $game_logs_original_tbls);
					$left_joins = [];

					foreach ($game_logs_original_tbls as $original_log_tbl) {
						$join_str = 'LEFT JOIN '. $original_log_tbl.' ON '. $original_log_tbl.'.external_uniqueid = '. 'game_logs.external_uniqueid COLLATE utf8_unicode_ci';
						array_push($left_joins, $join_str);
					}
					$where = 'game_logs.'.$player_id_field.'= '.$playerId;

					if($dry_run){
						$sql = 'SELECT '. $game_logs_originals_str_for_select. ' FROM game_logs '.implode(" ", $left_joins).' WHERE '.$where.' LIMIT '.$limit.'  OFFSET '.$offset.' ';
						$is_dry_run = 'true';
					}else{
						$sql = 'DELETE '. $game_logs_originals_str_for_delete. ' FROM game_logs '.implode(" ", $left_joins).' WHERE '.$where;
						$is_dry_run = 'false';
					}

				}else if($table == 'transactions_from_type'){
					$player_id_field = $details['player_id_field'];
					$limit = $details['sql_delete_limit'];
					if($dry_run){
						$sql = 'SELECT  '.$player_id_field.' FROM transactions where from_type = '.Transactions::PLAYER.' and from_id = "'.$playerId.'"  LIMIT '.$limit.'  OFFSET '.$offset.' ';
						$is_dry_run = 'true';
					}else{
						$sql = 'DELETE FROM transactions where from_type = '.Transactions::PLAYER.' and from_id = "'.$playerId.'" LIMIT '.$limit;
						$is_dry_run = 'false';
					}

				}else if($table == 'transactions_to_type'){
					$player_id_field = $details['player_id_field'];
					$limit = $details['sql_delete_limit'];
					if($dry_run){
						$sql = 'SELECT  '.$player_id_field.' FROM transactions where to_type = '.Transactions::PLAYER.'  and to_id = "'.$playerId.'"  LIMIT '.$limit.'  OFFSET '.$offset.' ';
						$is_dry_run = 'true';
					}else{
						$sql = 'DELETE  FROM transactions where to_type = '.Transactions::PLAYER.'  and to_id = "'.$playerId.'" LIMIT '.$limit;
						$is_dry_run = 'false';
					}

				}else{
					$player_id_field = $details['player_id_field'];
					$limit = $details['sql_delete_limit'];

					if($dry_run){
						$sql_for_saving =  'SELECT * FROM '.$table.' WHERE '.$player_id_field.' = "'.$playerId.'"  LIMIT '.$limit.'  OFFSET '.$offset.' ';
						$sql = 'SELECT '.$player_id_field.' FROM '.$table.' WHERE '.$player_id_field.' = "'.$playerId.'"  LIMIT '.$limit.'  OFFSET '.$offset.' ';
						$is_dry_run = 'true';
					}else{
						$sql_for_saving =  'SELECT * FROM '.$table.' WHERE '.$player_id_field.' = "'.$playerId.'"  LIMIT '.$limit;
						$sql = 'DELETE  FROM '.$table.' WHERE '.$player_id_field.' = '.$playerId.' LIMIT '.$limit;
						$is_dry_run = 'false';
					}
				}

				$page++;
				$for_save_rlt=[];
				if($save_info_to_csv){
					$q = $this->db->query($sql_for_saving);
					$for_save_rlt = $q->result_array();
				}

				$qry = $this->db->query($sql);
				$affected_rows = $this->db->affected_rows();
				//save important info
				if($save_info_to_csv){
					foreach ($for_save_rlt as &$value) {
						if($table == 'player'){
							$value['big_wallet'] = json_decode($value['big_wallet']);
						}
					}
					array_push($affected_rows_arr,$for_save_rlt);
				}
				else{
					if($affected_rows > 0){
						array_push($affected_rows_arr, $affected_rows);
					}
				}
				if($dry_run){
					$this->utils->debug_log('table',$table, 'affected count:'.$affected_rows , 'dry_run:'.$is_dry_run, 'playerId:'.$playerId, 'page_no:'.$page,'LIMIT '.$limit.'  OFFSET '.$offset);
					$offset = $offset + $limit;
				}else{
					$this->utils->debug_log($table, 'affected count:'.$affected_rows , 'dry_run:'.$is_dry_run, 'playerId:'.$playerId, 'page_no:'.$page);
				}

            	if($affected_rows < $limit){ //prevent another offset or last page
            		$affected_rows=0;//stop while loop
            	}

            }//while loop
            array_push($all_pages_table_data, $affected_rows_arr);
            array_push($table_as_columns_row , serialize($all_pages_table_data));

		}//foreach table
		$this->utils->_appendSaveDetailedResultToRemoteLog($token, $func, $table_as_columns_row, $filepath, true, []);

	}


	public function batch_hard_delete_agency_agents_info($config_key,$dry_run = 'TRUE'){

		$agent_ids = $this->utils->getConfig($config_key);
		$this->load->model(['player_model']);

		if(empty($agent_ids)){
			return $this->utils->error_log('No agent Ids supplied');
		}
		$d = new DateTime();
		$token=$config_key.'_'.$d->format('Y_m_d_H_i_s') . '_' . rand(1, 9999);
		$tables_details = $this->utils->getConfig('delete_agent_info_settings');
		$csv_log_headers = array_keys($tables_details);
		$filepath=null;
		$dry_run = (strtolower($dry_run) == 'true') ? true : false;
		$this->utils->_appendSaveDetailedResultToRemoteLog($token, __FUNCTION__, [], $filepath, true, $csv_log_headers);
		foreach ($agent_ids as $agentId) {
			$this->player_model->startTrans();
			$this->hard_delete_agency_agent_info($agentId,__FUNCTION__,$filepath, $dry_run, $token);
			$this->player_model->endTrans();
		}
		$this->utils->info_log(__FUNCTION__.'_log file '.$filepath,'dry_run:'.$dry_run);
	}


	protected function hard_delete_agency_agent_info($agentId, $func, $filepath, $dry_run, $token){

		if(empty($agentId)){
			return $this->utils->error_log('No agent Id supplied');
		}
		$tables_details = $this->utils->getConfig('delete_agent_info_settings');
		$table_as_columns_row = [];

		foreach ($tables_details as $table => &$details) {

			$all_pages_table_data = [];
			$affected_rows_arr=[];
			$affected_rows = 1;//trigger while loop
			$limit = $details['sql_delete_limit'];

			$offset=0;
			$page=0;
			$agent_id_field = $details['agent_id_field'];
			$save_info_to_csv = (isset($details['save_info_to_csv']) && $details['save_info_to_csv'] === true) ? true : false;

			while($affected_rows > 0){

				if($table == 'transactions_from_type'){
					if($dry_run){
						$sql = 'SELECT  '.$agent_id_field.' FROM transactions where from_type = '.Transactions::AGENT.' and from_id = "'.$agentId.'"  LIMIT '.$limit.'  OFFSET '.$offset.' ';
						$is_dry_run = 'true';
					}else{
						$sql = 'DELETE FROM transactions where from_type = '.Transactions::AGENT.' and from_id = "'.$agentId.'" LIMIT '.$limit;
						$is_dry_run = 'false';
					}

				}else if($table == 'transactions_to_type'){
					if($dry_run){
						$sql = 'SELECT  '.$agent_id_field.' FROM transactions where to_type = '.Transactions::AGENT.'  and to_id = "'.$agentId.'"  LIMIT '.$limit.'  OFFSET '.$offset.' ';
						$is_dry_run = 'true';
					}else{
						$sql = 'DELETE  FROM transactions where to_type = '.Transactions::AGENT.'  and to_id = "'.$agentId.'" LIMIT '.$limit;
						$is_dry_run = 'false';
					}

				}else{

					if($dry_run){
						$sql = 'SELECT '.$agent_id_field.' FROM '.$table.' WHERE '.$agent_id_field.' = "'.$agentId.'"  LIMIT '.$limit.'  OFFSET '.$offset.' ';
						$sql_for_saving =  'SELECT * FROM '.$table.' WHERE '.$agent_id_field.' = "'.$agentId.'"  LIMIT '.$limit.'  OFFSET '.$offset.' ';
						$is_dry_run = 'true';
					}else{
						$sql_for_saving =  'SELECT * FROM '.$table.' WHERE '.$agent_id_field.' = "'.$agentId.'"  LIMIT '.$limit ;
						$sql = 'DELETE  FROM '.$table.' WHERE '.$agent_id_field.' = '.$agentId.' LIMIT '.$limit;
						$is_dry_run = 'false';
					}
				}

				$page++;
				$for_save_rlt=[];
				if($save_info_to_csv){
					$q = $this->db->query($sql_for_saving);
					$for_save_rlt = $q->result_array();

				}
				if($dry_run === false){
                	//start trans
				}
				$qry = $this->db->query($sql);

				$affected_rows = $this->db->affected_rows();
				//save important info
				if($save_info_to_csv){
					array_push($affected_rows_arr,$for_save_rlt);
				}
				else{
					if($affected_rows > 0){
						array_push($affected_rows_arr, $affected_rows);
					}
				}
				if($dry_run){
					$this->utils->debug_log('table',$table, 'affected count:'.$affected_rows , 'dry_run:'.$is_dry_run, 'agentId:'.$agentId, 'page_no:'.$page,'LIMIT '.$limit.'  OFFSET '.$offset);
					$offset = $offset + $limit;
				}else{
					$this->utils->debug_log($table, 'affected count:'.$affected_rows , 'dry_run:'.$is_dry_run, 'agentId:'.$agentId, 'page_no:'.$page);
				}

            	if($affected_rows < $limit){ //prevent another offset or last page
            		$affected_rows=0;//stop while loop
            	}

    		}//while loop
    		array_push($all_pages_table_data, $affected_rows_arr);
    		array_push($table_as_columns_row , serialize($all_pages_table_data));
    	}// foreach
    	$this->utils->_appendSaveDetailedResultToRemoteLog($token, $func, $table_as_columns_row, $filepath, true, []);

    }

    /**
	 * overview : Get total rake of player
	 * @param array or int $playerIds
	 * @param string date $dateTimeFrom
	 * @param string date $dateTimeTo
	 * @param array or int $gamePlatformId
	 * @return sum of total rake
	 */

    public function get_total_rake($playerIds, $dateTimeFrom = null, $dateTimeTo = null, $gamePlatformId = null){
    	$this->CI->load->model(array('game_logs'));
    	$test_array_player_ids_for_generating_total_rake = $this->utils->getConfig('test_array_player_ids_for_generating_total_rake');
    	if(!empty($test_array_player_ids_for_generating_total_rake)){
    		$playerIds = $test_array_player_ids_for_generating_total_rake;
    	}
    	// print_r($playerIds);exit();
    	$total_rake   = $this->CI->game_logs->getTotalRakeByPlayers($playerIds, $dateTimeFrom, $dateTimeTo, $gamePlatformId);
    	$this->utils->debug_log('total_rake =========>'.$total_rake);
    }

    public function create_agent_on_api($game_platform_id){
    	$api = $this->utils->loadExternalSystemLibObject($game_platform_id);
		if(!empty($api)){
			$rlt=$api->createAgent();
			$this->utils->debug_log('result of create_agent_on_api', $rlt);

		}else{
			$this->utils->error_log('NOT FOUND API', $game_platform_id);
		}
		$this->utils->debug_log('========= end create_agent_on_api ============================');
    }

    public function create_test_player($username=null,$password=_COMMAND_LINE_NULL,$pass_length=_COMMAND_LINE_NULL,$levelId=_COMMAND_LINE_NULL,$registered_by=_COMMAND_LINE_NULL,$mm_key=_COMMAND_LINE_NULL,$tag=_COMMAND_LINE_NULL){
     //sudo ./command.sh create_test_player aris1007  aris1001 _null 9 _null test_mattermost_notif local

    	if(empty($username)){
    		$this->utils->error_log('missing username param');
    		return;
    	}
    	$this->load->library('salt');
    	$this->load->model(['group_level','player_model']);

    	if($levelId == _COMMAND_LINE_NULL){
    		$levelId = '1';
    	}
    	if($pass_length == _COMMAND_LINE_NULL){
    		$pass_length = 7;
    	}
    	if($password == _COMMAND_LINE_NULL){
    		$password = $this->utils->generate_password_no_special_char($pass_length);
    	}
    	$rand_chars=$this->utils->generate_password_no_special_char(7);
    	$first_name = 'testplayer'.$rand_chars;
    	$last_name = 'testplayer'.$rand_chars;

    	if($registered_by == _COMMAND_LINE_NULL){
    		$registered_by = 'T1';
    	}

    	$level=$this->group_level->getLevelById($levelId);
    	$levelName=$level['vipLevelName'];
    	$group=$this->group_level->getGroupById($level['vipSettingId']);
    	$groupName = $group['groupName'];
    	$username = trim($username);
    	if(!$this->player_model->usernameExist($username)){
    		$player_basic_info = array(
    			'levelId' => $levelId,
    			'levelName' => $levelName,
    			'groupName' => $groupName,
    			'password' => $this->salt->encrypt($password, $this->getDeskeyOG()),
    			'username' => $username,
    			'registered_by' => $registered_by,
    			'email' => $first_name.'@nothing.com',
    			'createdOn' => $this->utils->getNowForMysql()
    		);
    		$player_id = $this->player_model->insertData('player', $player_basic_info);
            $player_basic_info['password']=$password;//override hash
            $player_details = array(
            	'playerId' => $player_id,
            	'firstName' => $first_name,
            	'lastName' => $last_name,
            );
            $this->player_model->insertData('playerdetails',  $player_details);
            //required to view on new userinfo
            $playeraccount = array(
            	'playerId' => $player_id,
            	'currency' =>  $this->player_model->getActiveCurrencyCode(),
            	'type' => Wallet_model::TYPE_MAINWALLET,
            	'typeOfPlayer' => Wallet_model::TYPE_OF_PLAYER_REAL,
            	'totalBalanceAmount' => 0,
            	'typeId' => Wallet_model::MAIN_WALLET_ID ,
            	'status' => Player_model::DEFAULT_PLAYERACCOUNT_STATUS
            );
            $this->player_model->insertData('playeraccount',  $playeraccount);
            $this->utils->info_log('testplayer created',$player_basic_info);

            if(!empty($player_id)){
                $this->wallet_model->refreshBigWalletOnDB($player_id, $this->player_model->db);
            }

            if($mm_key != _COMMAND_LINE_NULL){
            	$this->load->helper('mattermost_notification_helper');
            	$channel =  $mm_key;
            	$texts_and_tags ='#'.__FUNCTION__.(new Datetime())->format('Ymd').' #year_mo_'.(new Datetime())->format('Ym');
            	if($tag != _COMMAND_LINE_NULL){
            		$texts_and_tags ='#'.__FUNCTION__.(new Datetime())->format('Ymd').' #year_mo_'.(new Datetime())->format('Ym').' #'.$tag;
            	}
            	$notif_message = array(array('text' => "```json\n".json_encode($player_basic_info,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n```",'type' => 'info'));
            	sendNotificationToMattermost(__FUNCTION__, $channel,$notif_message,$texts_and_tags);
            }
        }else{
        	$this->utils->error_log($username.' already exist');
        }
    }

    /**
     * Undocumented function
     *
     * batch_update_password_with_csv
     * @param integer $ignore_first_row
     * @param integer $enforce_override
     * @return void
     */
    public function batch_update_password_with_csv($ignore_first_row = 1, $enforce_override = 0){
        set_time_limit(0);

        $this->load->model(array( 'game_provider_auth', 'player_model','users'));
        $this->load->library(['player_manager', 'player_library', 'language_function']);

        $_import_csv_header = [];
        $_import_csv_header[] = 'Username';
        $_import_csv_header[] = 'Password';
        $_import_csv_header[] = 'BonusAmount';
        $_import_csv_header[] = 'BetTimes';
        $_import_csv_header[] = 'WageringRequirement';
        $_import_csv_header[] = 'DepositAmountRemarks';


        $_csv_file = $this->utils->getConfig('batch_update_password_with_csv_path_file');
        if(!file_exists($_csv_file) || empty($_csv_file) ){
            if( empty($_csv_file) ){
                $_csv_file = 'N/A'; // for error_log()
            }
    		return $this->utils->error_log("File not exist! csv_file: ". $_csv_file);
    	}

        $fp = file($_csv_file);// this one works
        $totalCount =  count($fp);
        if(!!$ignore_first_row){
            $totalCount--;
        }
		$fp = [];
		unset($fp);
        $this->utils->debug_log("OGP-28398.4315.totalCount" , $totalCount);

        $result_info=[
			'success'=>false,
			'cnt'=>0,
            'totalCnt' => $totalCount,
			'failedCnt'=>0,
			'successCnt'=>0,
            'failedCnt4NotExist'=>0,
            'failedCnt4HasTheSamePassword'=>0,
            'failed_username_in_exception' => [],
            'failed_username_in_not_exist' => [],
            'failed_username_in_has_the_same_password' => [],
			'failed_username'=>[], // username=>failed message
			// 'username'=>[],
		];

        $_config = [];
        $_config['_import_csv_header'] = $_import_csv_header;
        $_config['enforce_override'] = $enforce_override;

        $controller = $this;
        $cnt=0;
		$message=null;

        $result_info['success'] = $this->utils->loopCSV( $_csv_file
                                    , !!$ignore_first_row
                                    , $cnt
                                    , $message
                                    , function( $cnt, $csv_row, &$stop_flag=false ) use ($controller, $_config, &$result_info) {

            $_failed_username = []; // reset
            $_username_exception_in_gameapi_changepassword = $controller->utils->getConfig('batch_update_password_with_csv_exception_in_gameapi_changepassword_by_username');
            $controller->utils->debug_log("compare column headings" , $_config['_import_csv_header'], $csv_row);
            $row = array_combine($_config['_import_csv_header'], $csv_row);
            $row = $controller->utils->_extract_row($row);

            $enforce_override = $_config['enforce_override'];

            // for current player
            $username = $row['Username'];
            $_playerId = $controller->player_model->getPlayerIdByUsername($username);
            $_player = $controller->player_manager->getPlayerById($_playerId);

            try {

                if( empty($_player) ) {
                    // failed by not exists
                    $_failed_username[$username] = "the username,'{$username}' is not exists.";
                    $result_info['failedCnt4NotExist']++;
                    $result_info['failed_username_in_not_exist'][] = $username;
                } else if ( !$enforce_override // ignore when enforce_override = 0
                    && $row['Password'] == $controller->utils->decodePassword($_player['password'])
                ){
                    $_failed_username[$username] = "the username,'{$username}' has the same password.";
                    $result_info['failedCnt4HasTheSamePassword']++;
                    $result_info['failed_username_in_has_the_same_password'][] = $username;
                }else{
                    $newPassword = $row['Password'];
                    $reset_password_by_admin = $controller->utils->getConfig('reset_password_by_admin');
                    if ($reset_password_by_admin) {
                        $password_action = Player_model::RESET_PASSWORD_BY_ADMIN;
                    }else{
                        $password_action = Player_model::RESET_PASSWORD;
                    }
                    $controller->player_model->startTrans();

                    // save player password history
                    $controller->player_model->insertPasswordHistory($_playerId, $password_action, $controller->utils->encodePassword($newPassword));

                    $data = array(
                        'password' => $newPassword,
                    );
                    $controller->player_manager->resetPassword($data, $_playerId);
                    $data = [];
                    unset($data);

                    //call api
                    $gameApis = $controller->utils->getAllCurrentGameSystemList();
                    foreach ($gameApis as $apiId) {
                        $api = $controller->utils->loadExternalSystemLibObject($apiId);
                        if(!empty($api)) {
                            $oldPassword = $controller->game_provider_auth->getPasswordByPlayerId($_playerId, $apiId);
                            if( ! empty($oldPassword) ){
                                if($oldPassword != $newPassword){
                                    $controller->utils->debug_log("It will call Game API for update newPassword in the player, {$username} , GameID = {$apiId} .");
                                    if( ! empty($_username_exception_in_gameapi_changepassword)
                                        && $_player['username'] == $_username_exception_in_gameapi_changepassword
                                    ){
                                        throw new Exception('Expected Exception in gameApi::changePassword().'. ' apiId='. $apiId);
                                    }
                                    $api->changePassword($_player['username'], $oldPassword, $newPassword);

                                }else{
                                    $controller->utils->debug_log("The oldPassword is the same as newPassword in the player, {$username} does not have  in the game, GameID = {$apiId} .");
                                }
                            }else{
                                $controller->utils->debug_log("The player, {$username} does not have oldPassword in the game, GameID = {$apiId} .");
                            }
                        }
                        unset( $api ); // free mem.
                    } // EOF foreach ($gameApis as $apiId) {...

                    $adminUserId = Users::SUPER_ADMIN_ID;
                    $adminUsename=$controller->users->getUsernameById($adminUserId);
                    #sending email
                    $controller->load->library(['email_manager']);
                    $template = $controller->email_manager->template('player'
                                                                , 'player_change_login_password_successfully'
                                                                , array('player_id' => $_playerId, 'new_login_password' => $newPassword)
                                                            );
                    $template_enabled = $template->getIsEnableByTemplateName();
                    if($template_enabled['enable'] && ! empty($_player['email']) ){
                        $template->sendingEmail($_player['email'], Queue_result::CALLER_TYPE_ADMIN, $adminUserId);
                    }
                    unset($template);

                    // $controller->savePlayerUpdateLog($_playerId, lang('system.word8'), $adminUsename); // Add log in playerupdatehistory
                    $changes = lang('system.word8', language_function::INT_LANG_ENGLISH);
                    $today = date('Y-m-d H:i:s');
                    $updatedBy = $adminUsename;
                    $data = array(
                        'playerId' => $_playerId,
                        'changes' => $changes,
                        'createdOn' => empty($today) ? date('Y-m-d H:i:s') : $today,
                        'operator' => $updatedBy,
                    );
                    $controller->player_manager->addPlayerInfoUpdates($_playerId, $data);
                    $data = [];
                    unset($data);

                    $controller->player_model->endTrans();

                    $controller->player_library->syncPlayerCurrentToMDBWithLock($_playerId, $username, false);

                    $controller->utils->debug_log("The player, {$username} had updated. cnt:", $cnt, 'totalCnt:', $result_info['totalCnt']);

                    // $message = lang('con.plm31') . " <b>" . $player['username'] . " </b> " . lang('con.plm43');

                } // EOF if( empty($_player) ) {...

            } catch (Exception $e) {
                $_failed_username[$username] = "the current username,'{$username}' has exception, ". $e->getMessage();
                $result_info['failed_username_in_exception'][] = $username;
                $controller->player_model->rollbackTrans();

            } finally {
                if( ! empty($_failed_username)) {
                    $result_info['failed_username'][] = $_failed_username;
                    $result_info['failedCnt']++;
                }else{
                    $result_info['successCnt']++;
                }
                $result_info['cnt']++;
            } // EOF try {...

            $_failed_username = [];
            unset($_failed_username);

            $_player = [];
        }); // EOF $this->utils->loopCSV(...

        $this->utils->debug_log("simple report, total.cnt:" , $result_info['cnt']
                                    , "failedCnt:", $result_info['failedCnt']
                                    , 'successCnt:', $result_info['successCnt']
                                );
        $this->utils->debug_log("simple report, total.failedCnt:" , $result_info['failedCnt']
                                    , "failedCnt4NotExist:", $result_info['failedCnt4NotExist']
                                    , 'failedCnt4HasTheSamePassword:', $result_info['failedCnt4HasTheSamePassword']
                                    , 'othersFailed:', $result_info['failedCnt'] - $result_info['failedCnt4NotExist'] - $result_info['failedCnt4HasTheSamePassword']
                                );
        $this->utils->debug_log("simple report, failed username in exception:", $result_info['failed_username_in_exception'] );
        $this->utils->debug_log("simple report, failed username in not_exist:", $result_info['failed_username_in_not_exist'] );
        $this->utils->debug_log("simple report, failed username in has_the_same_password:", $result_info['failed_username_in_has_the_same_password'] );

        $failed_username_list =[];
        array_walk($result_info['failed_username'], function($value, $key) use (&$failed_username_list){
            $_failed_username = array_keys($value);
            $failed_username_list[] = $_failed_username[0];
        }, 'fruit');
        $this->utils->debug_log("simple report, failed details:", $result_info['failed_username']);

    } // EOF batch_update_password_with_csv()

    public function batch_create_test_players($config_key){

	// $config['sample']=
	// "password_length" =>null,
	// 'mm_key' => 'test_mattermost_notif',
	// 'tag' => 'local'
	// 'players' =>[
	// 	"username" => "testplayer",
	// 	"password"=> "4AOYNG" // null,
	// 	"registered_by" => "t1",
	// ],
	// []...
	//    ];

    	$config = $this->utils->getConfig($config_key);
    	if(empty($config)){
    		$this->utils->error_log('config key not found');
    		return;
    	}
    	if(!isset($config['test_players'])){
    		$this->utils->error_log('players not set in config');
    		return;
    	}
    	$test_players = $config['test_players'];
    	if(empty($test_players)){
    		$this->utils->error_log('config key not found');
    		return;
    	}
    	$mm_key=$pass_length=$tag= _COMMAND_LINE_NULL;
    	if(isset($config['pass_length'])){
    		$pass_length=$config['pass_length'];
    	}
    	if(isset($config['mm_key'])){
    		$mm_key=$config['mm_key'];
    	}
    	if(isset($config['tag'])){
    		$tag=$config['tag'];
    	}

    	foreach ($test_players as $test_player) {

    		if(isset($test_player['username'])){
    			$parts = preg_split('/\s+/', $test_player['username']);
    			if(count($parts) > 1){
    				$this->utils->error_log('please check your config at ',$test_player);
    				continue;
    			}
    		}else{
    			$this->utils->error_log('please check your config at ',$test_player);
    			continue;
    		}
    		$username=preg_replace('/\s+/', '', $test_player['username']);
    		$password=$levelId=$first_name=$last_name=$last_name=$registered_by=_COMMAND_LINE_NULL;

    		if(isset($test_player['password'])){
    			$password=$test_player['password'];
    		}
    		if(isset($test_player['levelId'])){
    			$levelId=$test_player['levelId'];
    		}
    		if(isset($test_player['registered_by'])){
    			$registered_by=$test_player['registered_by'];
    		}
    		$this->create_test_player($username,$password,$pass_length,$levelId,$registered_by,$mm_key,$tag);
    	}
    }

    public function import_csv_to_db_subwallet($csv_file,$subwallet_id,$batch_limit=_COMMAND_LINE_NULL,$is_username_from_game_name='false'){

    	$this->load->model(['agency_model','player_model']);

    	$og_home = realpath(dirname(__FILE__) . "/../../../");

    	$csv_file='/home/vagrant/Code/'.$csv_file;
    	$limit=10;

    	if($batch_limit !=_COMMAND_LINE_NULL){
    		$limit= $batch_limit;
    	}

    	$is_username_from_game_name = $is_username_from_game_name == 'true';

    	$controller = $this;
    	if(!file_exists($csv_file)){
    		return $controller->utils->error_log("File not exist!");
    	}

    	$arrangement = $this->utils->getConfig('import_csv_init_subwallet_cols_arrangement');

    	if(empty($arrangement)){
    		 #default
    		$arrangement = [
    			'csv_col_count'=>2,
    			'username_col_index' =>0,
    			'amount_col_index' =>1,
    		];

    	}

    	$ignore_first_row = true;
    	$player_list = [];

    	$success=$this->utils->loopCSV($csv_file, $ignore_first_row, $cnt, $message,
    		function($cnt, $tmpData, $stop_flag) use($controller,&$player_list,$arrangement){

    			for ($i = 0; $i <= ($arrangement['csv_col_count']-1); ++$i) {
    				$tmpData[$i] = trim($tmpData[$i]);
    				if($i == $arrangement['username_col_index']){
    					$tmpData[$i] =  strval($tmpData[$arrangement['username_col_index']]);
    				}
    				if($i == $arrangement['amount_col_index']){
    					$tmpData[$i] =  floatval($tmpData[$arrangement['amount_col_index']]);
    				}
    			}
    			array_push($player_list, array('username' => $tmpData[$arrangement['username_col_index']], 'amount'=> $tmpData[$arrangement['amount_col_index']]));
    			return true;
    		});

    	$player_cnt=0;
    	$player_chunks=null;
    	$total_pages_cnt=0;

    	if(!empty($player_list)){
    		$player_chunks = array_chunk($player_list,$limit);
    		$player_cnt =count($player_list);
    	}else{
    		return $this->utils->debug_log('empty players');
    	}

    	if($player_cnt > $limit){
    		$total_pages_cnt = ceil($player_cnt/$limit);
    	}else{
    		$total_pages_cnt = 1;
    	}

    	$lang=null;
    	$funcName='import_csv_to_db_subwallet';
    	$caller=0;
    	$callerType=Queue_result::CALLER_TYPE_SYSTEM;
    	$state=null;
    	$log_filepath = null;
    	$remote_log_file_path = $this->utils->getSharingUploadPath('/remote_logs');
    	$params=['func_name'=>'import_csv_to_db_subwallet','remote_log_file_path'=>$remote_log_file_path];
    	$token=  $this->createQueueOnCommand($funcName, $params,$lang , $callerType, $caller, $state);
    	$csv_headers=[
    		'isApiClassLoaded',
    		'playerId',
    		'isPlayerIdFound',
    		'csvUsername',
    		'createdGameLoginName',
    		'amount',
    		'isInitSubwalletSuccess',
    		'isSetGameAccountRegistered',
    		'isSeamLessGame',
    	];

    	$message_log =[];
    	$this->utils-> _appendSaveDetailedResultToRemoteLog($token, 'import_csv_to_db_subwallet', $message_log, $log_filepath, true, $csv_headers);

    	$agents_player_prefixes = $this->agency_model->getAllPlayerPrefix();

    	for ($i=1; $i <= $total_pages_cnt ; $i++) {

    		$players_detail = $player_chunks[$i-1];
    		$param=[];

    		foreach ($players_detail  as  $player_detail) {

    			$username=$player_detail['username'];

    			if($is_username_from_game_name === true){
    				$username=null;
    				$username=$this->agency_model->checkAndGetUsernameByAgentPrefix($agents_player_prefixes,$player_detail['username']);
    			}

    			if(!empty($username)){
    				array_push($param, $username.'-'.$player_detail['amount']);
    			}else{
    				$message_log =[
    					'false',
    					'NULL',
    					'not_found',
    					$player_detail['username'],
    					'NULL',
    					$player_detail['amount'],
    					'false',
    					'false',
    					'not_checked'
    				];
    				$this->utils-> _appendSaveDetailedResultToRemoteLog($token, 'import_csv_to_db_subwallet', $message_log, $log_filepath, true, []);
    			}
    		}
    		$param_cnt = count($param);
    		$cmd = 'bash '.$og_home . '/shell/batch_init_sub_wallet.sh "' .implode(" ", $param).'" "'.$subwallet_id.'" "'.$token.'"' ;
    		$this->utils->debug_log('cmd',$cmd,'log_filepath',$log_filepath);
    		$this->utils->info_log('page '.$i.' out of '.$total_pages_cnt.' pages','limit: '.$limit, 'total_player_cnt:'.$player_cnt, 'param_cnt:'.$param_cnt, 'platform_id:'.$subwallet_id );
    		shell_exec($cmd);
    		sleep(1);

    	}
    }

    public function init_sub_wallet($username_amount,$game_platform_id,$token){

    	$this->load->model(['wallet_model','game_provider_auth','player_model']);
    	$arr = explode("-",$username_amount);
    	$username=$arr[0];
    	$amount=$arr[1];
    	$isApiClassLoaded='true';
    	$isPlayerIdFound='not_found';
    	$csv_username=$username;
    	$createdGameLoginName='NULL';
    	$isInitSubwalletSuccess='false';
    	$isSetGameAccountRegistered='false';
    	$isSeamLessGame='false';


    	$api = $this->utils->loadExternalSystemLibObject($game_platform_id);

    	if(!$api){
    		$isApiClassLoaded='false';
    		$this->utils->error_log('game_platform_id: '.$game_platform_id.' not loaded');
    	}else{

    		$playerId = $this->player_model->getPlayerIdByUsername($username);

    		if(!empty($playerId)){
    			$isPlayerIdFound='found';
    			//creates game account if player has no account
    			$api->getGameUsernameByPlayerId($playerId);

    			$controller=$this;
    			$wallet_model=$this->wallet_model;

    			$success = $this->lockAndTransForPlayerBalance($playerId, function() use($controller,$wallet_model,$playerId,$game_platform_id, $amount) {

    				return $controller->wallet_model->syncSubWallet($playerId, $game_platform_id, $amount);
    			});

    			if($success){
    				$isInitSubwalletSuccess='true';
    			}

    			if($api->isSeamLessGame()){
    				if($api->setGameAccountRegistered($playerId)){
    					$isSeamLessGame='true';
    					$isSetGameAccountRegistered='true';
    				}
    			}

    			$createdGameLoginName=$this->game_provider_auth->getGameUsernameByPlayerId($playerId,$game_platform_id);
    			if(empty($createdGameLoginName)){
    				$createdGameLoginName='not created';
    			}

    		}

    		$data = $this->queue_result->getResult($token);
    		$param=null;

    		if(isset($data['full_params'])){
    			$param=json_decode($data['full_params'],true) ;
    		}
    		$remote_log_file_path=$param['remote_log_file_path'];
    		$funcName= $param['func_name'];

    		$file_name = $funcName.'_'.$token;
    		$log_filepath = rtrim($remote_log_file_path, '/').'/'.$file_name.'_log.csv';
    		$playerId=(!empty($playerId)) ? $playerId : 'NULL';

    	}
    	$message_log = [
    		$isApiClassLoaded,
    		$playerId,
    		$isPlayerIdFound,
    		$csv_username,
    		$createdGameLoginName,
    		$amount,
    		$isInitSubwalletSuccess,
    		$isSetGameAccountRegistered,
    		$isSeamLessGame,

    	];

    	$this->utils-> _appendSaveDetailedResultToRemoteLog($token,'import_csv_to_db_subwallet', $message_log, $log_filepath, true, []);
    	$rlt=['success'=>true,'log_filepath'=>site_url().'remote_logs/'.basename($log_filepath)];
    	$this->queue_result->updateResult($token, $rlt);
    }
    /**
	 * overview : Sync game list from json
	 * @param int or string $game_platform_id
	 */
    public function sync_gamelist_from_json($game_platform_id){
		$this->load->library("game_list_lib");
		$result =  $this->game_list_lib->sync_gamelist_from_json($game_platform_id);
		$this->utils->debug_log('sync_gamelist_from_json result ==>', $result);
	}

	public function patch_data_for_player_center_withdraw_page_setting(){
		$this->load->model('operatorglobalsettings');
		$data = [];
		#player_center_hide_time_in_remark value is ["enable"]
        $data['player_center_hide_time_in_remark'] = json_decode($this->operatorglobalsettings->getSetting('player_center_hide_time_in_remark')->value, true);
        $data['show_max_withdrawal_per_transaction'] = json_decode($this->operatorglobalsettings->getSetting('show_max_withdrawal_per_transaction')->value, true);
        $data['show_daily_max_withdrawal_amount'] = json_decode($this->operatorglobalsettings->getSetting('show_daily_max_withdrawal_amount')->value, true);

        $this->utils->debug_log('-----------------data', $data);
        $patch_data = [];
        if(!empty($data)){
			foreach ($data as $name => $value) {
				if($value === '1' || $value == 1 || $value){
					$patch_data[]=$name;
				}
			}
			$this->utils->debug_log('-----------------patch_data', $patch_data);
			$this->operatorglobalsettings->setPatchPlayerCenterWithdrawalPageSetting(json_encode($patch_data));
        }
	}

	/**
	 * revertData : Sync game list from json
	 * @param
	 * $view_update_data true or false
	 * $startDate yyyy-mm-dd
	 * $endDate yyyy-mm-dd
	 */
	public function revertData($view_update_data = true, $startDate, $endDate){
	    $this->utils->debug_log("revertData START ==============================================================>>>>>>>>");
	        $this->load->model(['player_promo']);
	        $this->player_promo->revertData($view_update_data, $startDate, $endDate);
	    $this->utils->debug_log("<<<<<<<============================================================== revertData END");
	}

	/**
	 * overview : update operator/default bet setting,
	 *
	 * @param string $game_platform_id
	 * @param string $limit_group
	 * @return mixed
	 */
	public function update_operator_bet_limit_group($game_platform_id, $limit_group = null) {

		$api=$this->utils->loadExternalSystemLibObject($game_platform_id);
		if(!empty($api)){
			$rlt=$api->setOperatorBetSetting($limit_group);
			$this->utils->debug_log('result of update_operator_bet_limit_group', $rlt);

		}else{
			$this->utils->error_log('NOT FOUND API', $game_platform_id);
		}

		$this->utils->debug_log('========= end update_operator_bet_limit_group ============================');

		$this->utils->debug_log('update_operator_bet_limit_group result', $rlt);
	}

	/**
	 * overview : update all players bet setting,
	 *
	 * @param string $game_platform_id
	 * @param string $limit_group
	 * @return mixed
	 */
	public function update_players_bet_limit_group($game_platform_id, $limit_group = null, $playerId = null) {
		$cnt = 0;
		$api=$this->utils->loadExternalSystemLibObject($game_platform_id);
		if(!empty($api)){

			$gameUsernames = $this->CI->game_provider_auth->getAllGameRegisteredUsernames($game_platform_id, $playerId);

			foreach($gameUsernames as $username) {
				$result = $api->setMemberBetSettingByGameUsername($username, $limit_group);
				$this->utils->debug_log("COMMAND: (update_all_players_bet_settings) username: ". $username, $result);
				$cnt ++;
			}

		}else{
			$this->utils->error_log('NOT FOUND API', $game_platform_id);
		}

		$this->utils->debug_log('========= end update_players_bet_limit_group ============================');

		$this->utils->debug_log('update_players_bet_limit_group result');
	}

	/*
	 * OGP-18473 sync player promo and withdraw condition data
	 * step1: list specify player promo
	 * step2: append player_promo_id on withdraw condition
	 * step3: set those player promo to finish if withdraw condition already finished
	 *
	 * OGP-18473 START
	 */
    // step1
    public function getSpecifyPlayPromo($promorulesId, $promocmssettingId){
        $this->utils->debug_log('============================== getSpecifyPlayPromo START ==============================');
        $this->load->model(['player_promo']);
        $result = $this->player_promo->getSpecifyPlayPromo($promorulesId, $promocmssettingId);
        $this->utils->debug_log('========================================= getSpecifyPlayPromo result', $result);
        $this->utils->debug_log('========================================= getSpecifyPlayPromo total result', count($result));
        $this->utils->debug_log('============================== getSpecifyPlayPromo END ==============================');
    }

    // step2
    public function appendPlayerPromoIdOnWithdrawCondition($promorulesId, $promocmssettingId){
        $this->utils->debug_log('============================== appendPlayerPromoIdOnWithdrawCondition START ==============================');
        $this->load->model(['withdraw_condition']);
        $this->withdraw_condition->appendPlayerPromoIdOnWithdrawCondition($promorulesId, $promocmssettingId);
        $this->utils->debug_log('============================== appendPlayerPromoIdOnWithdrawCondition END ==============================');

    }

    // step3
    public function setFinishedToPlayerPromo($promorulesId){
        $this->utils->debug_log('============================== setFinishedToPlayerPromo START ==============================');
        $this->load->model(['withdraw_condition']);
        $this->withdraw_condition->setFinishedToPlayerPromo($promorulesId);
        $this->utils->debug_log('============================== setFinishedToPlayerPromo END ==============================');
    }
    //OGP-18473 END

	public function setFinishedToPlayerPromoByPromoruleId($promorulesId, $dry_run = 'Y'){
        $this->utils->debug_log('============================== setFinishedToPlayerPromo START ==============================', $promorulesId);
        $this->load->model(['player_promo', 'promorules']);
		$promorule = $this->promorules->getPromoRules($promorulesId);
		$this->utils->printLastSQL();

		$noAnyConditions = true;
		if($promorule['withdrawRequirementConditionType'] != promorules::WITHDRAW_CONDITION_TYPE_NOTHING){
			$noAnyConditions = false;
        }

        $allowDepositConditionType = [promorules::DEPOSIT_CONDITION_TYPE_MIN_LIMIT,promorules::DEPOSIT_CONDITION_TYPE_MIN_LIMIT_SINCE_REGISTRATION];
        if(in_array($promorule['withdrawRequirementDepositConditionType'],$allowDepositConditionType)){
			$noAnyConditions = false;
        }

        $transferRequirementWalletsInfo = null;
        if(isset($promorule['transferRequirementConditionType'])){
            $transferRequirementWalletsInfo = json_decode($promorule['transferRequirementWalletsInfo']);
        }

        if(isset($transferRequirementWalletsInfo) && ($promorule['transferRequirementConditionType'] != promorules::TRANSFER_CONDITION_TYPE_NOTHING)){
			$noAnyConditions = false;
        }

		if(!$noAnyConditions){
			$this->utils->debug_log('============================== setFinishedToPlayerPromo found condictions ==============================');

		} else {

			$this->player_promo->setFinishedToPlayerPromo($promorulesId, $dry_run);
		}
        $this->utils->debug_log('============================== setFinishedToPlayerPromo END ==============================');
    }



	/**
	 * divides main csv to part by part ex. 600k players / 100k per page = 5-> no of process
	 * and send to init_multi_process_import.sh
	 * @param string $csv_file - filename of main csv to be import
	 * @param string $no_of_process result of total no of players / per page csv
	 * @param string $csv_detail_type - ex. import_player_csv_file ,import_player_contact_csv_file
	 * @param string $importer_formatter - importer library ex. importer_ole, importer_lequ
	 * ex. sudo ./command.sh run_multi_process_importer import_player_csv_file 5  import_player_csv_file importer_ole
	 */
    public function run_multi_process_importer($csv_file,$no_of_process=1,$csv_detail_type='import_player_csv_file',$importer_formatter){

    	$og_home = realpath(dirname(__FILE__) . "/../../../");

    	$csv_file_path='/home/vagrant/Code/'.$csv_file;

    	if(!file_exists($csv_file_path)){
    		return $this->utils->error_log("File not exist!");
    	}

    	if(!is_readable($csv_file_path)){
    		return $this->utils->error_log('Open CSV File Failed');
    	}

    	//count rows
     	$fp = file($csv_file_path);// for big file
     	$total_row_cnt =  count($fp) - 1;

     	$whole_page=[];

     	if($no_of_process > $total_row_cnt){
     		return $this->utils->error_log('no of process cannot be greater than total no of csv rows');
     	}

     	$rows_per_page = $total_row_cnt / $no_of_process;
     	for ($i=1; $i <= $total_row_cnt ; $i++)  {
     		array_push($whole_page, $i);
     	}

     	$pages_start_marker=[];
     	$pages	= array_chunk($whole_page,$rows_per_page);

     	foreach ($pages as $page) {
     		array_push($pages_start_marker,$page[0]);// use zero index to get start or a page
     	}

        $header=null;
     	$header_cnt = count($header);
     	$ignore_first_row = true;
     	$page_no = 1;
     	$random_string = random_string();
     	$uploadCsvFilepath=$this->utils->getSharingUploadPath('/upload_temp_csv');
     	$filenames = [];
     	$cnt=0;

     	$csv_file_path_open = fopen($csv_file_path, "r");

     	if(!feof($csv_file_path_open)) {
    		$header = fgetcsv($csv_file_path_open);
    	}

     	$csv_file_name = explode(".",$csv_file)[0];

     	while (!feof($csv_file_path_open)) {

     		$tmpData = fgetcsv($csv_file_path_open);
     		if($cnt==0 && $ignore_first_row){
     			$this->utils->debug_log('ignore first row', $tmpData);
     			$cnt++;
     			continue;
     		}

     		if(empty($tmpData)){
     			continue;
     		}

     		for ($i = 0; $i <= ($header_cnt-1); $i++) {
     			$tmpData[$i] = strip_tags($tmpData[$i]);//clean tags to prevent sql injection attack
     		}

     		if(in_array($cnt,$pages_start_marker)){

     			$page_name = $csv_file_name.'_'.date('YmdH').'_'.$random_string.'_page_'.$page_no.'.csv' ;
     			array_push($filenames, $page_name);
     			$csv_output_file = $uploadCsvFilepath.'/'.$page_name;
     			$this->utils->info_log('filename:'.$csv_output_file.' page_no:'.$page_no.' at count:'.$cnt);
     			$fpw = fopen($csv_output_file, 'w');

     			if($fpw){
     				$BOM = "\xEF\xBB\xBF";
     				fwrite($fpw, $BOM);
     			}else{
     				return $this->utils->error_log('error happened in writing csv');
     			}

     			fputcsv($fpw,$header, ',', '"');
     			$page_no++;

     		}

     		fputcsv($fpw,$tmpData, ',', '"');
     		$cnt++;

     	}

     	fclose($csv_file_path_open);

     	$cmd = 'bash '.$og_home . '/shell/init_multi_process_import.sh "' .implode(" ", $filenames).'" "'.$csv_detail_type.'" "'.$importer_formatter.'"' ;
     	$this->utils->debug_log('cmd',$cmd);
     	shell_exec($cmd . " > /dev/null 2>/dev/null &");

     }

	/**
	 * runs any importer library
	 *
	 * @param string $filename
	 * @param string $csv_detail_type ex. import_player_csv_file ,import_player_contact_csv_file
	 * @param string $importer_formatter - importer library ex. importer_ole, importer_lequ
	 */
    public function run_importer($filename,$csv_detail_type,$importer_formatter){

    	$this->load->model(['player_model']);
    	$summary=[];
    	$message=null;

    	$files=[
    		'import_player_csv_file'=>null,
    		'import_agency_csv_file'=>null,
    		'import_aff_csv_file'=>null,
    		'import_aff_contact_csv_file'=>null,
    		'import_player_contact_csv_file'=>null,
    		'import_player_bank_csv_file'=>null,
    	];
    	$files[$csv_detail_type]=$filename;

    	$success=$this->player_model->importFromCSV($importer_formatter, $files, $summary, $message);
    	$rlt['summary']=$summary;
    	$rlt['message']=$message;
    	$rlt['importer_formatter']=$importer_formatter;

    }

    /**
	 * Command that will update Transaction is_manual_adjustment == transactions::MANUALLY_ADJUSTE
	 *
	 * @param $periodFrom 2020-08-20 12:00:00
	 */
	public function updateTransactionIsManualAdjustment($periodFrom)
	{
		$this->utils->info_log('========= start updateTransactionIsManualAdjustment =========');

		$this->load->model(['transactions']);

		$success = $this->transactions->updateTransactionIsManualAdjustment($periodFrom);

		$this->utils->info_log('done, success is : '.$success);

		$this->utils->info_log('========= end updateTransactionIsManualAdjustment =========');
	}


    /**
	 * Fix player lever based on player.levelId
	 *
	 * @param $limit - per batch player limit to process
	 * @param $dry_run - if test only or not
	 */
    public function batch_check_and_fix_player_level($limit='1000',$dry_run='false'){

        $dry_run = (strtolower($dry_run) == 'true') ? true : false;
        $this->load->model(['vipsetting','group_level']);
        $vipsettingIds = $this->vipsetting->getAllvipsettingcashbackruleId();
        $vipsettingIdArr = [];
        foreach ($vipsettingIds as  $vipsettingId) {
        	array_push($vipsettingIdArr, $vipsettingId['vipsettingcashbackruleId']);
        }
    	$result_count = 1;//trigger while loop
    	$affected_rows = 0;
    	$offset = 0;
    	$page=0;
    	$current_rows_count = 0;
    	$time=time();
    	$failedList=[];
    	$failedCnt=0;
    	$successList=[];
    	$successCnt=0;

    	while($result_count > 0){

	//assumed player.levelId is the true playerGroupId
	$sql = <<<EOD
SELECT playerId, username, levelId FROM player WHERE deleted_at IS NULL LIMIT {$limit} OFFSET {$offset}
EOD;

    		$q=$this->db->query($sql);
    		$rows = $q->result_array();
    		$result_count = $q->num_rows();
    		if($result_count > 0){
    			if($dry_run === false){
    				foreach ($rows as $row) {
    					$playerId=$row['playerId'];
    					$username=$row['username'];
    					$playerGroupId=$row['levelId'];
    					if(!in_array($playerGroupId, $vipsettingIdArr)){
    						$failedCnt++;
    						$row['reason']= 'playerGroupId '.$playerGroupId.' is not in vipsettingcashbackrule';
    						array_push($failedList, $row);
    						continue ;
    					}
    					$playerlevelPlayerGroupId = $this->group_level->getPlayerGroupIdFromPlayerlevelTbl($playerId);
    					$row['replacedPlayerGroupId']=$playerlevelPlayerGroupId;//for previous record only
    					if($playerGroupId == $playerlevelPlayerGroupId){
    						continue;
    					}
    					$this->db->where('playerId', $playerId);
    					$this->db->update('playerlevel', array('playerGroupId'=>$playerGroupId));
    					$affected_cnt = $this->db->affected_rows();
    					if($affected_cnt > 0){
    						$affected_rows+=$affected_cnt;
    						array_push($successList, $row);
    					}
    				}
    			}
    		}

    		$q->free_result();
    		unset($q);

    		$this->utils->debug_log('sql',$sql,'result_count',$result_count);
    		$current_rows_count=$current_rows_count+$result_count;
    		$offset = $offset+$limit;
    		if($result_count < $limit){ //prevent another offset or last page
    			$result_count=0;//stop while loop
    		}
    		$page++;
    		$this->utils->debug_log('dry_run',$dry_run,'failedCnt: '.$failedCnt,'page '.$page.' limit: '.$limit,'affected_rows',$affected_rows,'total_rows_count','current_rows_count',$current_rows_count, 'current cost time',(time() - $time).'s');

    	} //end while
    	$this->utils->info_log('updatedList',$successList,'failedList',$failedList,'dry_run',$dry_run,'totalFailed: '.$failedCnt,'total page '.$page.' limit: '.$limit,'affected_rows',$affected_rows,'total_rows_count',$current_rows_count, 'total cost time',(time() - $time).'s');
    }

    /**
	 * batch_fill_registrationIp_from_http_request
     * @param string player_id_listStr Thats for check the specified players. The player_id list connected string with "_".
     * @param string dry_run The dryrun mode, for preview the patch result, and Not really update data.
     *
     * In Dry Run Mode with the specified players,
	 * sudo /bin/bash admin/shell/command.sh batch_fill_registrationIp_from_http_request '16857_16858_16859' 1 > ./logs/command_batch_fill_registrationIp_from_http_request.log &
	 * nohup sudo /bin/bash admin/shell/command.sh batch_fill_registrationIp_from_http_request '16857_16858_16859' 1 >> ./logs/command_batch_fill_registrationIp_from_http_request.log 2>&1 &
     *
     * In Dry Run Mode with all players,
	 * sudo /bin/bash admin/shell/command.sh batch_fill_registrationIp_from_http_request 0 1 > ./logs/command_batch_fill_registrationIp_from_http_request.log &
	 * nohup sudo /bin/bash admin/shell/command.sh batch_fill_registrationIp_from_http_request 0 1 >> ./logs/command_batch_fill_registrationIp_from_http_request.log 2>&1 &
     *
     * In the specified players,
	 * sudo /bin/bash admin/shell/command.sh batch_fill_registrationIp_from_http_request '16857_16859' > ./logs/command_batch_fill_registrationIp_from_http_request.log &
	 * nohup sudo /bin/bash admin/shell/command.sh batch_fill_registrationIp_from_http_request '16857_16859' >> ./logs/command_batch_fill_registrationIp_from_http_request.log 2>&1 &
	 *
     * Patch all issue data in playerdetails,
	 * bash admin/shell/command_mdb_noroot.sh brl batch_fill_registrationIp_from_http_request >> ./logs/command_batch_fill_registrationIp_from_http_request.log &
	 * nohup bash admin/shell/command_mdb_noroot.sh brl batch_fill_registrationIp_from_http_request >> ./logs/command_batch_fill_registrationIp_from_http_request.log 2>&1 &
	 *
	 * @return void
	 */
    public function batch_fill_registrationIp_from_http_request($player_id_listStr = '0', $dry_run = '0'){
        $this->load->model(['player_model']);

        $_dry_run = !empty($dry_run);// convert to boolean
        $report = [];
        $report['bool'] = null;
        $report['msg'] = '';
        $report['details'] = [];
        $report['details']['issue_total'] = 0;
        $report['details']['issue_not_found'] = [];
        $report['details']['issue_update_done'] = [];
        $report['details']['issue_update_ng'] = [];
        $report['details']['issue_update_exception'] = [];
        $report['details']['dry_run'] = [];
        $report['details']['dry_run']['bool'] = $_dry_run;
        $report['details']['dry_run']['patch_sql_list'] = [];
        $issue_sql = '
        SELECT playerId
        FROM `playerdetails`
        WHERE ( `registrationIP` = "" OR `registrationIP` IS NULL )
        ';
        if( !empty($player_id_listStr) ){ // for test in STG
            $_player_id_list = explode('_', $player_id_listStr);
            $_player_id_list = array_unique($_player_id_list, SORT_NUMERIC);

            if( ! empty($_player_id_list) ){
                $issue_sql .= ' AND playerId IN ( '. implode(', ', $_player_id_list).' )';
            }
        }

        // 2 params, registrationIP and playerId
        $patch_sql = ' UPDATE `playerdetails`
        SET `registrationIP` = ?
        WHERE `playerId` = ?
        AND ( `registrationIP` = "" OR `registrationIP` IS NULL )
        LIMIT 1;';

        // 1 param, playerId
        $query4http_request = '
        SELECT ip
        FROM http_request
        WHERE playerId = ?
        AND type = 1 /* Http_request::TYPE_REGISTRATION */
        LIMIT 1
        ';
        $issue_rows = $this->player_model->runRawArraySelectSQL($issue_sql);
        $report['details']['issue_total'] = empty($issue_rows)? 0: count($issue_rows);

        $result = null;
        if(!empty($issue_rows)){
            $controller = $this;
            $index = 0;
    		foreach ($issue_rows as $row) {
                $ip_row = $this->player_model->runOneRawSelectSQLArray($query4http_request, $row['playerId'] );
                if( !empty($ip_row) ){
                    $patch_ip = $ip_row['ip'];

                    $success = $this->player_model->runDBTransOnly( $this->player_model->db
                    , $result
                    ,  function($_db, &$_result) use ($row, $controller, &$report, &$index, $patch_sql, $patch_ip, $_dry_run) {
                        $_return = false;

                        try {
                            if( $_dry_run ){
                                $_affected_rows = 1;
                                $patch_sql_format = str_replace( "?", '"%s"', $patch_sql );
                                $patch_sql = sprintf($patch_sql_format, $patch_ip, $row['playerId']); // 2 params, registrationIP and playerId
                                $report['details']['dry_run']['patch_sql_list'][$row['playerId']] = $patch_sql;

                                $this->utils->debug_log('player_id_list.dry_run.progress.index:', $index
                                                            ,'total:', $report['details']['issue_total']
                                                            , 'playerId:', $row['playerId']
                                                            , 'patch_sql:', $patch_sql );
                            }else{
                                $_affected_rows = $controller->player_model->runRawUpdateInsertSQL($patch_sql, [ $patch_ip, $row['playerId'] ]);
                                $this->utils->debug_log('player_id_list.progress.index:', $index
                                                            ,'total:', $report['details']['issue_total']
                                                            , 'playerId:', $row['playerId']
                                                            , 'patch_sql:', $controller->player_model->db->last_query() );
                            }

                            if( ! empty($_affected_rows) ){
                                $_return = true;
                                if( $_dry_run ){
                                    array_push($report['details']['issue_update_done'], '-'. $row['playerId']);
                                }else{
                                    array_push($report['details']['issue_update_done'], $row['playerId']);
                                }

                            }else{
                                array_push($report['details']['issue_update_ng'], $row['playerId']);
                            }
                        }catch(Exception $e){
                            $controller->utils->error_log('Error:', $e);
                            $_data = [];
                            $_data['playerId'] = $row['playerId'];
                            $_data['exception'] = $e;
                            $controller->utils->debug_log('player_id_list.progress.index:', $index
                                                    ,'total:', $report['details']['issue_total']
                                                    , 'playerId:', $row['playerId']
                                                    , 'exception', $e);
                            array_push($report['details']['issue_update_exception'], $_data);
                        }

                        return $_return;
                    }); // EOF player_model->runDBTransOnly()
                }else{
                    $this->utils->debug_log('player_id_list.progress.index:', $index
                                            ,'total:', $report['details']['issue_total']
                                            , 'playerId:', $row['playerId']
                                            , 'notFoundInHttp_request' );
                    array_push($report['details']['issue_not_found'], $row['playerId']);
                }// EOF if( !empty($ip_row) ){...

                $index++; // for trace progress
            } // EOF foreach ($issue_rows as $row) {...

        } // EOF if(!empty($issue_rows)){...

        $this->utils->debug_log('player_id_list.notFound:', $report['details']['issue_not_found'] );
        $this->utils->debug_log('player_id_list.done:', $report['details']['issue_update_done'] );
        $this->utils->debug_log('player_id_list.ng:', $report['details']['issue_update_ng'] );
        $this->utils->debug_log('player_id_list.exception:', $report['details']['issue_update_exception'] );

        $_bool = ( count($report['details']['issue_update_done']) == $report['details']['issue_total'])? true: false;
        if( $_bool ){
            $report['msg'] = 'Patch completed.';
            if( $_dry_run ){
                $report['msg'] = 'Patch completed in Dry Run mode.';
            }
        }else{
            $report['msg'] = 'Patch completed, but not all players have filled. Please check details.';
        }

        $report['bool'] = $_bool;

        $this->utils->debug_log('subtotals.'
            , 'total:', $report['details']['issue_total']
            , 'notFound:', count($report['details']['issue_not_found'])
            , 'done:',count($report['details']['issue_update_done'])
            , 'ng:', count($report['details']['issue_update_ng'])
            , 'exception:', count($report['details']['issue_update_exception'])
            , 'dry_run', $_dry_run
            , 'msg:', $report['msg']
            , 'bool:', $report['bool']
        );

    } // EOF batch_fill_registrationIp_from_http_request

    /**
	 * batch Update BeforeLastLoginTime
	 *
	 * @param
	 */
	public function batchUpdateBeforeLastLoginTime()
	{
		$this->utils->debug_log('========= start batchUpdateBeforeLastLoginTime =========');

		$this->load->model(['player_model']);

		$success = $this->player_model->batchUpdateBeforeLastLoginTime();

		$this->utils->debug_log('done, success is : '.$success);

		$this->utils->debug_log('========= end batchUpdateBeforeLastLoginTime =========');
	}

    public function refresh_target_player_wallet($tableName){
    	$this->load->model(['wallet_model']);
    	$this->db->select('playerId')->from($tableName);
    	$playerList=$this->wallet_model->runMultipleRowArray();
    	// $playerList=$this->player_model->getUnlimitedPlayerId();
    	$failed_player=[];
    	$cnt=0;
    	if(!empty($playerList)){
    		foreach ($playerList as $row) {
    			if(!empty($row['playerId'])){
    				$playerId=$row['playerId'];
	    			$success=$this->wallet_model->lockAndTransForPlayerBalance($playerId, function()
	    				use($playerId){
		    			//lock
	    				$success=$this->wallet_model->initCreateAllWalletForRegister($playerId);
	    				if($success){
		    				$bigWallet=$this->wallet_model->getBigWalletByPlayerId($playerId);
		    				if(!empty($bigWallet)){
			    				$success=$this->wallet_model->updateBigWalletByPlayerId($playerId, $bigWallet);
		    				}else{
		    					$success=false;
		    				}
	    				}
	    				return $success;

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

	public function clear_duplicate_vip_cashback_settings(){
		$cnt=$this->group_level->clear_duplicate_cashback();
		$this->utils->debug_log('clear_duplicate_cashback', $cnt);
	}

	/**
	 * Exports players who has 3 deposits and up
	 *
	 * @param string $client - for specific client csv name
	 * @param string $from - from date '2020-01-01'
	 * @param string $to - to date '2020-01-31'
	 * @param string $last_mo_only - for auto calculated last month
	 */
	public function exportPlayersHasDeposited3TimeAndUpsSince($client,$from=_COMMAND_LINE_NULL,$to=_COMMAND_LINE_NULL,$last_mo_only="false"){

		$last_mo_only =  strtolower($last_mo_only) == "false" ? false : true;

		if($last_mo_only === true){
			$now = new Datetime();
			$last_mo = $now->modify('- 1 months');
			$curMonth = $last_mo->format('F');
			$curYear  = $last_mo->format('Y');
			$timestamp    = strtotime($curMonth.' '.$curYear);
			$from= date('Y-m-01', $timestamp);
			$to  = date('Y-m-t', $timestamp);
		}

		if($from == _COMMAND_LINE_NULL || $to == _COMMAND_LINE_NULL){
			return $this->utils->error_log("Please provide dates");
		}

		$result_count = 1;//trigger while loop
		$affected_rows = 0;
		$offset = 0;
		$limit=1000;
		$page=0;
		$current_rows_count = 0;
		$time=time();

		$csv_filepath = '/home/vagrant/Code/'.$client.'player_has_3deposits_and_bet_since'.$from.'_to_'.$to.'.csv';

		$fp = fopen($csv_filepath, 'w');
		if ($fp) {
			$BOM = "\xEF\xBB\xBF";
			fwrite($fp, $BOM); // NEW LINE
		} else {
		//create report failed
			$this->utils->error_log('create csv file failed', $csv_filepath);
		}
		fputcsv($fp, ['PlayerID','Username','No of Deposits','BetCount(hourly based)'], ',', '"');

		while($result_count > 0){

			$sql = <<<EOD
SELECT playerId, username FROM player WHERE deleted_at IS NULL LIMIT {$limit} OFFSET {$offset}
EOD;
            $q=$this->db->query($sql);
            $rows = $q->result_array();
            $result_count = $q->num_rows();
            if($result_count > 0){
				foreach ($rows as $row) {
					$playerId=$row['playerId'];
					$username=$row['username'];
					$sql2 = 'SELECT count(id) AS deposit_count FROM transactions WHERE  to_id = '.$playerId.' AND  transaction_type = "1" ';

					$q2=$this->db->query($sql2);
					$rows = $q2->result_array();
					$row['deposit_count'] = $rows[0]['deposit_count'];
					if($rows[0]['deposit_count'] >= 3){

						$sql3 = 'SELECT count(id) bet_count FROM total_player_game_hour  WHERE player_id = '.$playerId.' AND `date` >= "'.$from.'" AND `date` <=  "'.$to.'"  ';
						$this->utils->info_log($sql3);
						$q3=$this->db->query($sql3);
						$rows3 = $q3->result_array();
						if($rows3[0]['bet_count'] > 0){
							$row['bet_count']=$rows3[0]['bet_count'];
							fputcsv($fp, $row, ',', '"');
						}
					}
				}
			}
			$this->utils->info_log('sql',$sql,'result_count',$result_count);
			$current_rows_count=$current_rows_count+$result_count;
			$offset = $offset+$limit;
			if($result_count < $limit){ //prevent another offset or last page
			  $result_count=0;//stop while loop
			}
			$page++;
			$this->utils->debug_log('page '.$page.' limit: '.$limit,'total_rows_count','current_rows_count',$current_rows_count, 'current cost time',(time() - $time).'s');

		} //end while

	}

	/*
		ex:
		sudo sh command.sh updatePlayersDispatchAccountLevel 3 1
		$update2newLevel = 3
		$originalGroup = 1
	 */
	public function updatePlayersDispatchAccountLevel($update2newLevel, $originalGroup = null){
		$this->load->model(array('dispatch_account'));
		$this->utils->debug_log('==============updatePlayersDispatchAccountLevel start',$originalGroup,$update2newLevel);

		if (empty($originalGroup) && empty($update2newLevel)) {
			$this->utils->debug_log('==============updatePlayersDispatchAccountLevel originalGroup or update2newLevel is empty');
			return false;
		}

		$updatePlayerDispatchAccountLevel = $this->dispatch_account->updateSpecificGroupPlayerDispatchAccountLevel($update2newLevel, $originalGroup);

		$this->utils->debug_log('==============updatePlayersDispatchAccountLevel updatePlayerDispatchAccountLevel',$updatePlayerDispatchAccountLevel);

		if ($updatePlayerDispatchAccountLevel) {
			$this->refreshPlayersDispatchAccountLevel();
		}

		$this->utils->debug_log('==============updatePlayersDispatchAccountLevel end');
	}


	public function fix_wrong_group_name_players($player_id = null){
		$this->load->model(array('group_level'));
		$this->utils->debug_log('==============fix_wrong_group_name_players start',$player_id);

		$result = $this->group_level->fix_wrong_group_name_players($player_id);

		$this->utils->debug_log('==============fix_wrong_group_name_players edn',$result);
	}

	public function query_didnt_receive_vip_upgrade_bouns_players($start_date = '', $end_date = '', $vipLevelId = ''){
		$this->utils->debug_log('==============query_didnt_receive_vip_upgrade_bouns_players start',$start_date, $end_date, $vipLevelId);
		$this->db->select('player_id,request_time,newvipId,vipsettingId,vipsettingcashbackruleId,applypromomsg')->from('vip_grade_report');

		if (!empty($start_date) && !empty($end_date)) {
			$from = $start_date . ' 00:00:00';
			$to = $end_date . ' 23:59:59';
			$this->db->where('request_time >= ',$from);
			$this->db->where('request_time <= ',$to);
		}

		if (!empty($vipLevelId)) {
			$this->db->where('newvipId',$vipLevelId);
		}

		$this->db->group_by('player_id');

		$q=$this->db->get();
		$rows = $q->result_array();
		$this->utils->printLastSQL();
		$result_count = $q->num_rows();

		$this->utils->info_log(__METHOD__,'rows',$rows,'result_count',$result_count);

		$result = false;
		$this->load->model(array('player_model'));
		if (!empty($rows)) {
			$count_applied = 0;
			$count_not_applied = 0;
			$data = [];
			$applied = [];
			$not_applied =[];
			$nowDate = $this->utils->getNowForMysql();
			foreach ($rows as $player) {
				$playerId = $player['player_id'];
				$sql = 'SELECT * FROM playerpromo  WHERE playerId = '.$playerId.' AND `dateProcessed` >= "'.$from.'" AND `dateProcessed` <=  "'.$nowDate.'" AND promorulesId = "17098" AND bonusAmount = "77" ';
				$this->utils->info_log('-------------rows',$sql);
				$query=$this->db->query($sql);
				$player_promo = $query->result_array();
				$username = $this->player_model->getUsernameById($playerId);

				$this->utils->debug_log('------------check player apply player_promo and username',$player_promo,$username);
				$data['username'] = $username;
				$data['playerId'] = $playerId;

				if (!empty($player_promo)) {
					$applied[] = $data;
					$count_applied += 1;
				} else {
					$not_applied[] = $data;
					$count_not_applied += 1;
				}
			}
			$this->utils->info_log('------------check player apply result','applied',$applied,'count_applied',$count_applied,'not_applied',$not_applied,'count_not_applied',$count_not_applied);

			$result = true;
		}

		$this->utils->debug_log('==============query_didnt_receive_vip_upgrade_bouns_players edn',$result);
	}

	public function removeNoteInSql($sql){
		/// https://regex101.com/r/AMkhsW/1
		$findme = '/-- [\S ]+/m';
		$replaceTo = '';
		$sql = preg_replace($findme, $replaceTo, $sql);
		$findme = '/\n/m';
		$replaceTo = ' ';
		$sql = preg_replace($findme, $replaceTo, $sql);
		$findme = '/\r/m';
		$replaceTo = ' ';
		$sql = preg_replace($findme, $replaceTo, $sql);
		$findme = '/\t/m';
		$replaceTo = ' ';
		$sql = preg_replace($findme, $replaceTo, $sql);
		$findme = '/[ ]+/m';
		$replaceTo = ' ';
		$sql = preg_replace($findme, $replaceTo, $sql);
		$sql = str_replace($findme, $replaceTo, $sql);
		return $sql;
	}

	/**
	 * overview : update adminusers approvedWidAmt
	 *
	 * @param string $userId
	 * @return
	 */
	public function update_adminusers_widamt($userId = null, $dwstatus = 'paid', $setCloumn = 'approvedWidAmt') {
		$this->load->model(array('wallet_model','users'));
		$this->utils->debug_log('========= start update_adminusers_approvedWidAmt ============================');

		$rlt = false;
		$amount = 0;

		$getAllApproveAmt = $this->wallet_model->getAmountByStatus();
		$this->utils->debug_log(__METHOD__,"total $dwstatus amount", $getAllApproveAmt);

		if(!empty($userId)){
			$amount = $this->wallet_model->sumAdminuserWithdrawAmountByStatus($userId,$dwstatus);
			if ($amount != 0) {
				$rlt = $this->users->incUserWidAmtByStatus($userId, $amount, $setCloumn);
			}

			$this->utils->debug_log(__METHOD__,'one user', $amount, 'result', $rlt);

		}else{
			$allUsers = $this->users->getAllAdminUsers();
			$sumTotalAmt = 0;

			foreach ($allUsers as $user) {
				$amount = $this->wallet_model->sumAdminuserWithdrawAmountByStatus($user['userId'],$dwstatus);
				if ($amount != 0) {
					$rlt = $this->users->incUserWidAmtByStatus($user['userId'], $amount, $setCloumn);

					$sumTotalAmt += $amount;

					$this->utils->debug_log(__METHOD__,'all user id ',$user['userId'] ,'amount', $amount, 'result', $rlt);
				}
			}
			$this->utils->debug_log(__METHOD__,'all allUsers',$allUsers,'sumTotalAmt', $sumTotalAmt);
		}
		$this->utils->debug_log('========= end update_adminusers_approvedWidAmt ============================');
	}

	//sudo bash ./admin/shell/command.sh run_importer_lovebet_test '[fullfilapath with extension]' 'import_aff_csv_file' 'importer_lovebet'
    public function run_importer_lovebet($filename,$csv_detail_type,$importer_formatter){

    	$this->CI->load->model(['player_model']);
    	$summary=[];
    	$message=null;

    	$files=[
			'import_tag_csv_file'=>null,
			'import_aff_tag_csv_file'=>null,
			'import_aff_csv_file'=>null,
			'import_aff_terms_csv_file'=>null,
    		'import_aff_bank_csv_file'=>null,
    		'import_aff_links_csv_file'=>null,
    		'import_players_csv_file'=>null,
    		'import_players_banks_csv_file'=>null,
    		'import_players_transactions_csv_file'=>null,
    	];
    	$files[$csv_detail_type]=$filename;
		$this->CI->utils->debug_log('bermar run_importer_lovebet_test');
    	$success=$this->player_model->importFromCSV($importer_formatter, $files, $summary, $message);
    	$rlt['summary']=$summary;
    	$rlt['message']=$message;
    	$rlt['importer_formatter']=$importer_formatter;

    }

	//sudo bash ./admin/shell/command.sh run_exporter_lovebet_test '07142021_tags' 'import_tag_csv_file' 'importer_lovebet'
    public function run_exporter_lovebet($filename,$csv_detail_type,$importer_formatter){

    	$this->load->model(['player_model']);
    	$summary=[];
    	$message=null;

    	$files=[
    		'export_tag_csv_file'=>null,
			'export_aff_tag_csv_file'=>null,
			'export_aff_csv_file'=>null,
			'export_aff_terms_csv_file'=>null,
			'export_aff_links_csv_file'=>null,
			'export_aff_bank_csv_file'=>null,
			'export_players_csv_file'=>null,
			'export_players_transactions_csv_file'=>null,
			'export_players_banks_csv_file'=>null,
    	];
    	$files[$csv_detail_type]=$filename;

    	$success=$this->player_model->exportToCSV($importer_formatter, $files, $summary, $message);
    	$rlt['summary']=$summary;
    	$rlt['message']=$message;
    	$rlt['importer_formatter']=$importer_formatter;

    }

	/**
	 * overview : fakeDeletePlayerBankDetails
	 * OGP-22649
	 * ole777cn call player center api listPlayerWithdrawAccounts and manualWithdraw but the bankDetailsId is null
	 * so will add a new bank and withdrawal success (This will produce the wrong bank details )
	 * @param string $startDate YYYY-mm-dd ex. 2021-07-01
	 * @param string $dry_run ON or OFF
	 * @param string $player_id player id
	 * @return
	 */
	public function fakeDeletePlayerBankDetailsForOGP22649($startDate = _COMMAND_LINE_NULL, $dry_run = 'ON', $player_id = _COMMAND_LINE_NULL){
		$this->utils->debug_log('========= start fakeDeletePlayerBankDetails ==========',$startDate);
		if($startDate == _COMMAND_LINE_NULL){
			$startDate = null;
		}
		$fromdate = !empty($startDate) ? $startDate . ' 00:00:00' : $this->utils->getTodayForMysql() . ' 00:00:00';
		$todate = $this->utils->getNowForMysql();
		$sql = 'SELECT
				`playerbankdetails`.`playerBankDetailsId`,
				`playerbankdetails`.`playerId` as pId,
				`player`.`username`,
				`playerbankdetails`.`bankAccountFullName`,
				`playerbankdetails`.`bankAccountNumber`
				FROM
				(`playerbankdetails`)
				LEFT JOIN `player` ON `player`.`playerId` = `playerbankdetails`.`playerId`
				WHERE
				`playerbankdetails`.`createdOn` >= "'.$fromdate.'" AND `playerbankdetails`.`createdOn` <= "' .$todate.'" AND `playerbankdetails`.`bankAddress` = "0"
				AND `playerbankdetails`.`isDefault` = "0" AND `playerbankdetails`.`status` != "2"';
		if($player_id != _COMMAND_LINE_NULL){
			$sql .= ' AND `playerbankdetails`.`playerId` = "'.$player_id.'"';
		}

		$q=$this->db->query($sql);
		$rows = $q->result_array();

		$this->utils->info_log(__METHOD__,'--------sql',$sql);
		$this->utils->debug_log(__METHOD__,'--------rows',$rows);

		$count = 0;
		$countf = 0;
		if (!empty($rows)) {
			$this->load->model(['playerbankdetails']);

			foreach ($rows as $playerbankdetails) {
				$bank_details_id = $playerbankdetails['playerBankDetailsId'];
				$res = false;
				if($dry_run === 'OFF') {
					$res = $this->playerbankdetails->deletePlayerBankInfo($bank_details_id);
				}
				if ($res) {
					$count += 1;
				} else {
					$countf += 1;
				}
			}
		}

		$this->utils->info_log(__METHOD__,'--------count',$count);
		$this->utils->info_log(__METHOD__,'--------countf',$countf);
		$this->utils->debug_log('========= end fakeDeletePlayerBankDetails ==========');
	}

	public function generate_player_oauth2_password_client($name){
		if(empty($name)){
			return $this->utils->error_log('empty name', $name);
		}

		$this->load->model(['player_oauth2_model']);
		$secret=random_string('alnum', 36);
		$clientId=$name;
		$success=$this->player_oauth2_model->generatePasswordClient($clientId, $name, $secret);
		if($success){
			$this->utils->info_log('generate client success, id is '.$clientId.', secret is '.$secret);
		}else{
			$this->utils->error_log('generate client failed', $success, $name);
		}
	}

	public function reset_secret_of_player_oauth2_client($name){
		if(empty($name)){
			return $this->utils->error_log('empty name', $name);
		}

		$this->load->model(['player_oauth2_model']);
		$secret=random_string('alnum', 36);
		//reset secret
		$clientId=null;
		$success=$this->player_oauth2_model->updateSecretByName($name, $secret, $clientId);
		if($success){
			$this->utils->info_log('reset secret client success, id is '.$clientId.', secret is '.$secret);
		}else{
			$this->utils->error_log('reset secret client failed', $success, $name);
		}
	}

	public function revoke_player_oauth2_client($name){
		if(empty($name)){
			return $this->utils->error_log('empty name', $name);
		}

		$this->load->model(['player_oauth2_model']);
		$secret=random_string('alnum', 36);
		//revoke client
		$success=$this->player_oauth2_model->revokeClientByName($name);
		if($success){
			$this->utils->info_log('revoke client success, '.$name);
		}else{
			$this->utils->error_log('reset secret client failed', $success, $name);
		}
	}

	public function test_getTransIdByWalletAccountId($walletAccountId){
		$this->load->model(['wallet_model']);
		$id=$this->wallet_model->getTransIdByWalletAccountId($walletAccountId);
        $this->utils->printLastSQL();
		$this->utils->debug_log('get id', $id);
	}

	public function correct_wrong_bankname_of_walletaccount_data($bankTypeId,$startDate = null){

		$this->utils->debug_log(__METHOD__,'========= start correct_wrong_bankname_of_walletaccount_data ==========',$bankTypeId);

		$fromdate = !empty($startDate) ? $startDate . ' 00:00:00' : $this->utils->getTodayForMysql() . ' 00:00:00';
		$todate = $this->utils->getNowForMysql();

		$this->db->select('walletaccount.*')->from('walletaccount')->where('RIGHT(bankName,1) <> "}"');

		$q = $this->db->get();
		$rows = $q->result_array();

		$this->utils->printLastSQL();
		$this->utils->debug_log(__METHOD__,'--------rows',$rows);

		$count = 0;
		$success_id = [];

		if (!empty($rows)) {
			$this->load->model(['wallet_model','banktype']);

			foreach ($rows as $walletAccounts) {
				$walletAccountId = $walletAccounts['walletAccountId'];
				$bankType = $this->banktype->getBankTypeById($bankTypeId);
				$bankName = $bankType->bankName;

				$this->utils->info_log(__METHOD__,'--------bankName',$bankName,'walletAccountId',$walletAccountId);

				$res = false;

				if (!empty($walletAccountId)) {
					$this->db->set('bankName', $bankName)->where('walletAccountId', $walletAccountId);
					$res = $this->wallet_model->runAnyUpdate('walletaccount');
				}

				if ($res) {
					$count += 1;
					$success_id[] = $walletAccounts['transactionCode'];
				}
			}
		}
		$this->utils->info_log(__METHOD__,'========= end correct_wrong_bankname_of_walletaccount_data ==========',$count,$success_id);
	}

	public function init_player_password_grant_client_for_dev(){
		$this->load->model(['player_oauth2_model']);
		$clientId='T1SBE-player';
		$clientSecret='T1SBE-rocks';
		$name=$clientId;
		// check exists first
		$client=$this->player_oauth2_model->queryClientById($clientId);
		if(!empty($client)){
			$this->utils->info_log('client already exist, id is '.$clientId.', secret is '.$clientSecret);
			return;
		}

		$success=$this->player_oauth2_model->generatePasswordClient($clientId, $name, $clientSecret);
		if($success){
			$this->utils->info_log('generate client success, id is '.$clientId.', secret is '.$clientSecret);
		}else{
			$this->utils->error_log('generate client failed', $success, $name);
		}
	}

	public function get_real_ip_without_white_ip($remoteAddr, $xForwardedFor){
		$_SERVER['REMOTE_ADDR']=$remoteAddr;
		$_SERVER['HTTP_X_FORWARDED_FOR']=$xForwardedFor;
		$ip=$this->utils->tryGetRealIPWithoutWhiteIP();
		$this->utils->debug_log('tryGetRealIPWithoutWhiteIP', $ip);
	}

	public function batch_update_all_players_game_info($gamePlatformId, $agentId, $username = _COMMAND_LINE_NULL) {
		$this->utils->debug_log("COMMAND: (batch_update_all_players_game_info)");
		$this->CI->load->model('game_provider_auth');

		if(empty($gamePlatformId)){
			$this->utils->error_log('batch_update_all_players_game_info gamePlatformId cannot be empty', $gamePlatformId);
			return;
		}

		if(empty($username) || $username==_COMMAND_LINE_NULL){
			//$this->utils->error_log('batch_update_all_players_game_info username cannot be empty', $username);
			//return;
		}

		if(empty($agentId) || $agentId==_COMMAND_LINE_NULL){
			$this->utils->error_log('batch_update_all_players_game_info agentId cannot be empty', $agentId);
			return;
		}

		$cnt = 0;
		$api=$this->utils->loadExternalSystemLibObject($gamePlatformId);

		if(!empty($api)){

			if(isset($username) && $username==_COMMAND_LINE_NULL){
				$usernames = $this->CI->game_provider_auth->getAllGameRegisteredPlayerUsername($api->getPlatformCode(), 0, $agentId);
			}else{
				$usernames = array($username);
			}

			foreach($usernames as $username) {
				$result = $api->updatePlayerInfo($username, []);
				$this->utils->debug_log("COMMAND: (batch_update_all_players_game_info) username: ". $username, $result);
				$cnt ++;
			}
		}else{
			$this->utils->error_log('NOT FOUND API', $gamePlatformId);
		}

		$this->utils->info_log("COMMAND: (batch_update_all_players_game_info) Update count: " . $cnt);
		return;
	}

	public function run_job_by_generate_command_line() {
        $command_list = $this->utils->getConfig('run_job_by_generate_command_line_list');
        $this->utils->info_log('start run_job_by_generate_command_line list', $command_list);

        if (empty($command_list)) {
            $this->utils->info_log('end run_job_by_generate_command_line list is empty', $command_list);
            return;
        }

        $is_blocked = false;
        $db = $this->CI->db;
        $res = null;

        foreach ($command_list as $command) {
            $this->utils->info_log('run_job_by_generate_command_line funcName', $command);

            if (method_exists('Command', $command)) {
                $dbName = !empty($db) ? $db->getOgTargetDB() : null;
                $file_list = [];
                $command_params = [];

                $cmd = $this->utils->generateCommandLine($command, $command_params, $is_blocked, $file_list, $dbName);
                $this->utils->info_log('run_job_by_generate_command_line cmd' . (empty($db) ? ' empty db' : ' db'), $cmd, $dbName);

                if (!empty($cmd)) {
                    $res = $this->utils->runCmd($cmd);
                    $this->utils->info_log('run_job_by_generate_command_line res', $res);
                }
            } else {
                $this->utils->info_log('run_job_by_generate_command_line command not exist', $command);
            }
        }
        $this->utils->info_log('end run_job_by_generate_command_line list', $command_list, $res);
    }

	public function batch_update_playeraccount_currency($currency_from = 'CNY', $currency_to = 'IDR'){
		$this->utils->debug_log(__METHOD__, "COMMAND: start");
		$this->load->model(['player_model', 'transactions', 'wallet_model']);

		$count_process = 0;
		$count_success = 0;
		$failed_player = array();
		$success_result = array();

		$players = $this->CI->wallet_model->getPlayersWalletByCurrency($currency_from);

		$this->utils->debug_log(__METHOD__, "COMMAND: players: ", $players, $currency_from);

		if(empty($players)){
			$this->utils->debug_log(__METHOD__, "COMMAND: no players");
			return;
		}

		foreach($players as $player) {
			$success_data = array();
			$count_process = $count_process+1;
			$player_id = $player['playerId'];
			$lockedKey=null;
			$lock_it = $this->lockPlayerBalanceResource($player_id, $lockedKey);

			try {
				if ($lock_it) {
					$this->startTrans();

					$result = $this->CI->wallet_model->updatePlayerAccountCurrecy($player_id, $currency_from, $currency_to);
					$this->utils->debug_log(__METHOD__, "COMMAND: player_id: ". $player_id, $result);

					// $this->rollbackTrans();
					if ($result <= 0) {
						//rollback and quit;
						$this->rollbackTrans();
						// return array('success' => false);
						array_push($failed_player, $player_id);
						continue;
					}

				}
				$res = $this->endTransWithSucc();

				if($res){
					$success_data['pid'] = $player_id;
					$success_data['cnt'] = $result;
					$count_success = $count_success+1;
				}

			}finally {
				// release it
				$success_result[] = $success_data;
				$rlt = $this->releasePlayerBalanceResource($player_id, $lockedKey);
			}
		}

		$this->utils->info_log(__METHOD__, "COMMAND: done", $count_process);
		$this->utils->info_log(__METHOD__, "COMMAND: success", $count_success);
		$this->utils->info_log(__METHOD__, "COMMAND: success_result", $success_result);
		$this->utils->info_log(__METHOD__, "COMMAND: failed", $failed_player);
		$this->utils->debug_log(__METHOD__, "COMMAND: end");
		return;
	}

	public function batch_clear_player_balance($player_usernames = null){

		$count_process = 0;
		$count_success = 0;
		$failed_player = array();

		$this->utils->debug_log('========================start batch_clear_player_balance count', $count_process);

		if(empty($player_usernames)) {
			$this->utils->debug_log('empty input');
			return;
		}
		$this->load->model(['player_model', 'transactions', 'wallet_model']);

		$arr_player_usernames = array_map('trim', array_filter(explode(' ', $player_usernames)));

		foreach ($arr_player_usernames as $player_name) {
            $count_process = $count_process+1;

			$player_id = $this->player_model->getPlayerIdByUsername($player_name);
			if(empty($player_id)){
				$this->utils->debug_log('batch_clear_player_balance player not found', $player_name);
				array_push($failed_player, $player_name);
				continue;
			}

			$totalBeforeBalance = $this->wallet_model->getTotalBalance($player_id);
			$this->utils->debug_log('=======clear from player_id', $player_id, 'totalBeforeBalance', $totalBeforeBalance);
			if($totalBeforeBalance == 0) {
				$this->utils->debug_log('skip no balance', $player_name);
				array_push($failed_player, $player_name);
				continue;
			}

			$lock_type = Utils::LOCK_ACTION_BALANCE;
			$lockedKey=null;
			$lock_it = $this->lockPlayerBalanceResource($player_id, $lockedKey);

			try {
				if ($lock_it) {
					$this->startTrans();

					$before_adjustment = $this->player_model->getMainWalletBalance($player_id);
					$amount = $before_adjustment;
					$after_adjustment = $before_adjustment - $amount;
					$action_name = 'Subtract';

					$note = sprintf(
						'%s <b>%s</b> balance to <b>%s</b>\'s <b>%s</b>(<b>%s</b> to <b>%s</b>) by <b>%s</b>, <b>%s</b>',
						$action_name,
						number_format($amount, 2),
						$player_name,
						'Main Wallet',
						number_format($before_adjustment, 2),
						number_format($after_adjustment, 2),
						'system',
						''
					);
					$reason = 'request to clear balance';
                    $note_reason = sprintf('<i>Reason:</i> %s <br>', $reason);
                    $note = (trim($reason) != '')  ? ($note_reason . sprintf('<i>Normal Note:</i> %s <br>', $note)) : $note;
					$promo_category = null;
					$show_in_front_end = false;
					$adjustment_category = null;

					$transaction = $this->transactions->createAdjustmentTransaction(
						Transactions::MANUAL_SUBTRACT_BALANCE,
						1,
						$player_id,
						$amount,
						$before_adjustment,
						$note,
						$totalBeforeBalance,
						$promo_category,
						$show_in_front_end,
						$reason,
						null,
						$adjustment_category,
						Transactions::MANUALLY_ADJUSTED
					);


					if (!$transaction) {
						//rollback and quit;
						$this->rollbackTrans();
						// return array('success' => false);
						array_push($failed_player, $player_name);
						continue;
					}

					$this->payment_manager->addPlayerBalAdjustmentHistory(array(
						'playerId' => $transaction['to_id'],
						'adjustmentType' => $transaction['transaction_type'],
						'walletType' => 0, # 0 - MAIN WALLET
						'amountChanged' => $transaction['amount'],
						'oldBalance' => $transaction['before_balance'],
						'newBalance' => $transaction['after_balance'],
						'reason' => $reason,
						'adjustedOn' => $transaction['created_at'],
						'adjustedBy' => $transaction['from_id'],
						'show_flag' => $show_in_front_end == '1',
					));
				}
				$result['success'] = $this->endTransWithSucc();
				$count_success = $count_success+1;

			}finally {
				// release it
				$rlt = $this->releasePlayerBalanceResource($player_id, $lockedKey);
			}
		}
		$this->utils->debug_log('========================done batch_clear_player_balance count', $count_process );
		$this->utils->debug_log('========================Success', $count_success );
		$this->utils->debug_log('========================failed', $failed_player );
	}

	public function reset_broadcastid_default_null(){
		$this->load->model(array('internal_message'));
		$this->utils->info_log('==============reset_broadcastid_default_null start');

		$result = $this->internal_message->resetMessagesBroadcastIdDefaultToNull();

		$this->utils->info_log('==============reset_broadcastid_default_null edn',$result);
	}

	public function clearAllUnverifyPlayerEmail($player_usernames = null){
		$this->utils->debug_log('========================run clearAllUnverifyPlayerEmail', $player_usernames );
		$this->load->library('player_library');
		$notExistPlayers = array();
		$existPlayers = array();
		$countSuccess = 0;
		$countFailed = 0;
		if($player_usernames != null){

			$arr = array_map('trim', array_filter(explode(' ', $player_usernames)));

			foreach ($arr as $u) {
				$sql1= 'SELECT * FROM player  WHERE username = ? AND verified_email = 0 and email <> ""';
				$q1 = $this->db->query($sql1,array($u));

				if($q1->num_rows() > 0){

					$playerId = $this->player_model->getPlayerIdByUsername($u);
					$playerdetails['email'] = "";
					$modifiedFields = $this->player_library->checkModifiedFields($playerId, $playerdetails);
					// $this->player_library->editPlayerDetails($playerdetails, $playerId);
					$this->player_library->savePlayerUpdateLog($playerId, lang('Edit Player Information')  . ' (' . $modifiedFields . ')', 'system'); // Add log in playerupdatehistory
					$sql2 = "UPDATE player SET email = '' WHERE username = ?";
					$this->db->query($sql2,array($u));
					array_push($existPlayers, $u);
					$countSuccess += 1;
				}else{
					array_push($notExistPlayers, $u);
					$countFailed += 1;
				}
				$this->utils->debug_log("process player :", $u);
			}

		} else {
			$playersList = $this->player_model->getPlayersListOfUnverifyEmail();
			$player_usernames = $playersList;
			$playerdetails = [];
			foreach ($playersList as $u) {
				$playerId = $u['playerId'];
				$username = $u['username'];
				$playerdetails['email'] = "";
				$modifiedFields = $this->player_library->checkModifiedFields($playerId, $playerdetails);
				// $this->player_library->editPlayerDetails($playerdetails, $playerId);
				$this->player_library->savePlayerUpdateLog($playerId, lang('Edit Player Information')  . ' (' . $modifiedFields . ')', 'system'); // Add log in playerupdatehistory
				$sql2 = "UPDATE player SET email = '' WHERE username = ?";
				$this->db->query($sql2,array($username));
				array_push($existPlayers, $username);
				$countSuccess += 1;
				$this->utils->debug_log("process player :", $username);
			}
		}
		$this->utils->debug_log("Success to clear mail for Usernames count[$countSuccess] :", $existPlayers);
		$this->utils->debug_log("Not existed or no changes Usernames count[$countFailed] :", $notExistPlayers);
		$this->utils->debug_log('========================done clearAllUnverifyPlayerEmail', $player_usernames );
	}

	/**
	 * To use the method, big_wallet_module::getLastTotalBalanceByDate()
	 *
	 *  sudo /bin/bash admin/shell/command.sh getLastTotalBalanceByDate 123 '2022-09-07' > ./logs/command_getLastTotalBalanceByDate.log &
	 *
	 * @param integer $playerId
	 * @param string $date The end date, ex: '2022-09-07'.
	 * @return void
	 */
	public function getLastTotalBalanceByDate($playerId, $date){
		$lastBalance = $this->wallet_model->getLastTotalBalanceByDate($playerId, $date);
        $date_from = null;
		$this->utils->debug_log("getLastTotalBalanceByDate.playerId:", $playerId, 'lastBalance:', $lastBalance);
		return $lastBalance;
	}



	/**
	 * batch affiliate binding by phone
	 *
	 * Upload the csv file, that contains phone.
	 * A phone number per line, and no column name on first line.
	 *
	 * @param string $phone_list_csv_file The csv file, that contains phone.
	 * @param string $aff_username The affiliation username.
	 * @param boolean $allow_duplicate_phone_apply
	 * @return void
	 */
	public function batch_affiliate_binding_with_phone_list($phone_list_csv_file, $aff_username , $allow_duplicate_phone_apply = false){

		$this->load->model(['affiliatemodel', 'player_model', 'affiliate']);


		$aff = $this->affiliate->getAffiliateByName($aff_username);

		if(empty($aff) ){
			return $this->utils->error_log("aff_username not exist!");
		}
		$affiliateId = $aff['affiliateId'];


		$sql_formater = <<<EOF
		SELECT player.playerId
		, player.username
		, playerdetails.contactNumber
		, player.createdOn
		, player.affiliateId
		, player.agent_id
		, player.refereePlayerId
		FROM player
		INNER JOIN playerdetails ON playerdetails.playerId = player.playerId
		WHERE player.deleted_at is null
		-- AND ( player.affiliateId = 0 OR player.affiliateId is null )
		-- AND ( player.agent_id = 0 OR player.agent_id is null )
		-- AND ( player.refereePlayerId = 0 OR player.refereePlayerId is null )
		AND playerdetails.contactNumber IN (
%s
		)
EOF;
// EOF sql_formater with 1 param.

		// under tmp_shell
		$csv_file_path = BASEPATH.'/../application/logs/tmp_shell/'.$phone_list_csv_file;
		// $csv_file_path='/home/vagrant/Code/'.$phone_list_csv_file;

    	if(!file_exists($csv_file_path)){
    		return $this->utils->error_log("File not exist!");
    	}

		$fp = file($csv_file_path);// this one works
		$totalCount =  count($fp);
		$fp = [];
		unset($fp);

		$phone_list =[];
		$counter_list =[];
		$counter_list['total'] = $totalCount;
		$counter_list['executed'] = 0; // for line of csv
		$counter_list['done'] = 0; // by phone
		$counter_list['done_in_updated_player'] = 0;
		$counter_list['failed'] = 0;
		$counter_list['collect_list_updated_player_id'] = [];


		$controller=$this;
		$phone_index_of_columns = 0;
		$ignore_first_row = false;

		$arrangement = [
			'allow_duplicate_phone_apply' => $allow_duplicate_phone_apply,
			'affiliateId' => $affiliateId,
			'sql_formater' => $sql_formater,
			'phone_index_of_columns' => $phone_index_of_columns,
			'totalCount' => $totalCount,
		];


		$success = $this->utils->loopCSV($csv_file_path, $ignore_first_row, $cnt, $message,
			function($cnt, $tmpData, $stop_flag) use($controller, &$phone_list, &$counter_list, $arrangement){

				$counter_list['executed']++;

// $this->utils->debug_log('batch_affiliate_binding_with_phone_list.loopCSV.tmpData:', $tmpData, 'cnt', $cnt);
				// arrangement assign
				$totalCount = $arrangement['totalCount'];
				$allow_duplicate_phone_apply = $arrangement['allow_duplicate_phone_apply'];
				$affiliateId = $arrangement['affiliateId'];
				$sql_formater = $arrangement['sql_formater'];
				$phone_index_of_columns = $arrangement['phone_index_of_columns'];

				$phone_info = []; // init

				$phone = trim($tmpData[$phone_index_of_columns]);
				$phone_info['phone'] = $phone;

				// the player(s) by the phone
				$sql = sprintf( $sql_formater, "'{$phone}'" );
// $this->utils->debug_log('batch_affiliate_binding_with_phone_list.loopCSV.phone:', $phone, 'cnt', $cnt);
// $this->utils->debug_log('batch_affiliate_binding_with_phone_list.loopCSV.sql:', $sql, 'cnt', $cnt);
				$q=$this->db->query($sql);
				$rows = $q->result_array();
				$q->free_result();
				unset($q);


				$rows_count = count($rows);
				if( empty($phone) ){
					$counter_list['failed']++;
					$phone_info['bool'] = false;
					$phone_info['reason'] = 'Empty phone at cnt:'. $cnt;
					$rows=[]; // free
					$phone_list[$phone] = $phone_info;

					$this->utils->debug_log('progress cnt:', $cnt, 'totalCount:', $totalCount, 'phone:', 'EMPTY');
					return true; // skip this phone/ line of csv
				}else if( $rows_count == 0){

					$phone_info['bool'] = false;
					$phone_info['reason'] = 'No player with this phone';
					$rows=[]; // free
					$phone_list[$phone] = $phone_info;

					$counter_list['failed']++;
					$this->utils->debug_log('progress cnt:', $cnt, 'totalCount:', $totalCount, 'phone:', $phone);
					return true; // skip this phone/ line of csv
				}else if( $rows_count > 1){
					// check allow_duplicate_phone_apply
					if(!$allow_duplicate_phone_apply){

						// disallow with duplicate phone
						$phone_info['bool'] = false;
						$phone_info['reason'] = 'disallow with duplicate phone';
						$phone_info['playerId_list'] = implode(', ', array_column($rows, 'playerId') );
						$rows=[]; // free
						$phone_list[$phone] = $phone_info;

						$counter_list['failed']++;
						$this->utils->debug_log('progress cnt:', $cnt, 'totalCount:', $totalCount, 'phone:', $phone);
						return true; // skip this phone/ line of csv
					} // EOF if(!$allow_duplicate_phone_apply){...
				}


				// handle duplicate phone apply
				$phone_info['return_from_do'] = [];
				foreach($rows as $indexNumber => $row){
					$playerId = $row['playerId'];
					$_return_from_do = $controller->do_affiliate_binding_with_direct_player($playerId, $affiliateId);
					array_push($phone_info['return_from_do'], $_return_from_do);


					// $counter_list['collect_list_updated_player_id'] = [];
					if( $_return_from_do['bool'] ){
						$counter_list['done_in_updated_player']++;
						array_push( $counter_list['collect_list_updated_player_id'], $_return_from_do['playerId'] );
					}
					$this->utils->debug_log('collect players _return_from_do:', $_return_from_do);
					// TODO: collect updated playerId
				} // EOF foreach($rows as $indexNumber => $row){...


				$phone_info['bool'] = true;
				$phone_info['reason'] = 'Operation complete';
				// $phone_info['playerId_list'] = implode(', ', array_column($rows, 'playerId') );
				array_push($phone_list, $phone_info);

				$counter_list['done']++;
				$this->utils->debug_log('progress cnt:', $cnt, 'totalCount', $totalCount);
				return true; // next
		}); // EOF $this->utils->loopCSV(...

		$this->utils->debug_log('batch_affiliate_binding_with_phone_list.counter_list.collect_list_updated_player_id:', $counter_list['collect_list_updated_player_id'] );
		$this->utils->debug_log('batch_affiliate_binding_with_phone_list.counter_list.amounts.'
			, 'total:', $counter_list['total']
			, 'executed:', $counter_list['executed']
			, 'done:', $counter_list['done']
			, 'failed:', $counter_list['failed']
			, 'done_in_updated_player:', $counter_list['done_in_updated_player']
		);

		$this->utils->debug_log('batch_affiliate_binding_with_phone_list.phone_list:', $phone_list );
	} // EOF batch_affiliate_binding_with_phone_list



	/**
	 * Affiliate binding with direct player
	 *
	 * sudo /bin/bash admin/shell/command.sh do_affiliate_binding_with_direct_player 123 456 > ./logs/command_do_affiliate_binding_with_direct_player 2>&1 &
	 *
	 * @param integer $playerId The field, "player.playerId".
	 * @param integer $affiliateId The field, "affiliates.affiliateId".
	 * @return array $return The formats as followings,
	 * - $return['bool'] bool For Check the action ASAP.
	 * - $return['caseNo'] integer The Line Number, thats not used __LINE__, for faster trace cause.
	 * - $return['msg'] string The message for debug.
	 * - $return['affiliateId'] integer Optional, The param, affiliateId.
	 * - $return['playerId'] integer Optional, The param, playerId.
	 * - $return['detials'] array Optional, more details.
	 * - $return['detials']['player_row'] array Optional, about the player detail.
	 * - $return['detials']['current_affiliate'] array Optional. About the current affiliate, after binding.
	 *
	 * TEST:
	 * - affiliate not exists
	 * - player not exists
	 * - not direct player
	 * - binding completed, check update history, affiliate player
	 */
	public function do_affiliate_binding_with_direct_player($playerId, $affiliateId){
		$this->load->model(['affiliate']);
		$this->load->library(['player_manager', 'language_function']);
		$return = [];
		$return['bool'] = null;
		$return['msg'] = null;
$this->utils->debug_log('do_affiliate_binding_with_direct_player.playerId:', $playerId, 'affiliateId:', $affiliateId );
		// 1 param, $playerId
		$sql_formater = <<<EOF
		SELECT player.playerId
		, player.username
		, playerdetails.contactNumber
		, player.createdOn
		, player.affiliateId
		, player.agent_id
		, player.refereePlayerId
		FROM player
		INNER JOIN playerdetails ON playerdetails.playerId = player.playerId
		WHERE player.deleted_at is null
		-- AND ( player.affiliateId = 0 OR player.affiliateId is null )
		-- AND ( player.agent_id = 0 OR player.agent_id is null )
		-- AND ( player.refereePlayerId = 0 OR player.refereePlayerId is null )
		AND player.playerId = %s
EOF;

		// check affiliate
		$aff = $this->affiliate->getAffiliateById($affiliateId);
		if( ! empty($aff) ){
			$affiliateId = $aff['affiliateId'];
		}else{
			$affiliateId = 0;
			$return['bool'] = false;
			$return['caseNo'] = 5490;
			$return['msg'] = 'affiliateId not exist';
$this->utils->debug_log('do_affiliate_binding_with_direct_player.return:', $return );
			return $return;
		}

		// check player
		$sql = sprintf( $sql_formater, $playerId );
		$q=$this->db->query($sql);
		$player_rows = $q->result_array();
		$q->free_result();
		unset($q);
		//
		if( ! empty($player_rows) ){
			$player_row = $player_rows[0];
			if( empty($player_row['affiliateId'])
				&& empty($player_row['agent_id'])
				&& empty($player_row['refereePlayerId'])
			){
				$playerId = $player_row['playerId'];
			}else{
				// Not direct player
				$playerId = 0;
				$return['bool'] = false;
				$return['caseNo'] = 5514;
				$return['msg'] = 'playerId is not direct player';
				$return['detials'] = [];
				$return['detials']['player_row'] = $player_row;
$this->utils->debug_log('do_affiliate_binding_with_direct_player.return:', $return );
				return $return;
			}
		}else{
			$playerId = 0;
			$return['bool'] = false;
			$return['caseNo'] = 5523;
			$return['msg'] = 'playerId not exist';
$this->utils->debug_log('do_affiliate_binding_with_direct_player.return:', $return );
			return $return;
		} // EOF if( ! empty($player_rows) ){...

		try {
			if( ! empty($playerId)
				&& ! empty($affiliateId)
			){

				$return['affiliateId'] = $affiliateId;
				$return['playerId'] = $playerId;
				$return['detials'] = [];

				$this->affiliate->startTrans(); // Trans Begin

				$current_affiliate = $this->player_manager->addAffiliateToPlayer($playerId, $affiliateId);

				// insert into update history
				$updatedBy = 'command';
				$data = array(
					'playerId' => $playerId,
					'changes' => sprintf(lang('adjustmenthistory.adjustment.affiliate', Language_function::INT_LANG_ENGLISH), $current_affiliate),
					'createdOn' => date('Y-m-d H:i:s'),
					'operator' => $updatedBy,
				);
				$this->player_manager->addPlayerInfoUpdates($playerId, $data);

				//sync
				if( !! $this->utils->isEnabledMDB()){
					$this->load->model(['multiple_db_model']);
					$_rlt=$this->multiple_db_model->syncPlayerFromCurrentToOtherMDB($playerId, true);
					$this->utils->debug_log('syncPlayerFromCurrentToOtherMDB', $_rlt);
				}

				$this->utils->recordAction(
					'Player Management', // self::ACTION_MANAGEMENT_TITLE, // const ACTION_MANAGEMENT_TITLE = 'Player Management';
					lang('player.ui71') . ' : ' . lang('player.ufr01') . '(' . $playerId . ') - ' . lang('adjustmenthistory.title.afteradjustment') . ' (' . $current_affiliate . ') ',
					"System has adjusted player '" . $playerId . "'"
				);

				$rlt = $this->affiliate->endTransWithSucc();  // Trans END

				if($rlt){
					$return['detials']['current_affiliate'] = $current_affiliate;
				}
				if($rlt){
					// reload player
					$q=$this->db->query($sql);
					$_player_rows = $q->result_array();
					$q->free_result();
					unset($q);

					if( ! empty($_player_rows) ){
						$_player_row = $_player_rows[0];
					}

					$return['detials']['player_row'] = $_player_row;
				}

				$return['bool'] = $rlt;
				$return['caseNo'] = 5584;
				$return['msg'] = 'Operation complete';

			}else{
				$return['bool'] = false;
				$return['caseNo'] = 5589;
				$return['msg'] = '';
				if( empty($playerId) ){
					$return['msg'] .= 'playerId not exist. ';
				}
				if( empty($affiliateId) ){
					$return['msg'] .= 'affiliateId not exist. ';
				}
			} // EOF if( ! empty($playerId) && ! empty($affiliateId) ){...

		}catch(Exception $e){

			$this->affiliate->rollbackTrans();  // Trans ISSUE END

			$this->utils->error_log('[ERROR] do_affiliate_binding_with_direct_player error', $e);

			$return['bool'] = false;
			$return['caseNo'] = 5606;
			$return['affiliateId'] = $affiliateId;
			$return['playerId'] = $playerId;
			$return['msg'] = $e->getMessage();
		}

$this->utils->debug_log('do_affiliate_binding_with_direct_player.return:', $return );
		return $return;
	} // EOF do_affiliate_binding_with_direct_player

	public function batchBlockPlayerByUsername($playerUsernames, $lastLoginDate, $dry_run = true) {

		$this->utils->debug_log(__METHOD__,'========= start batchBlockPlayerByUsername ==========',["lastLoginDate" => $lastLoginDate, "usernameStr" => $playerUsernames]);

		if(empty($lastLoginDate)){
			return;
		}

		if(empty($playerUsernames)) {
			return;
		}

		$arr = array_map('trim', array_filter(explode(' ', $playerUsernames)));

		if(empty($arr)){
			return;
		}
		$this->utils->debug_log('count username', count($arr));

		$this->load->model('player_model', 'player');
        $this->load->library(array('player_library', 'player_manager'));

		$admin_user_id  = 1;
		$admin_username = 'Batch Update';

		$successArr = [];
		$failArr = [];

		$this->player_model->startTrans(); // Trans Begin

		try{
			foreach ($arr as $u) {

				$this->utils->debug_log('username', $u);
				$playerUsername = $u;
				$playerId = $this->player_model->getPlayerIdByUsername($playerUsername);

				if(empty($playerId)) {

					$this->utils->debug_log('not found username', $playerUsername);

					array_push($failArr, $playerUsername);

				} else {

					$playerLastLoginDate = $this->player_model->getPlayerLogInTime($playerId);
					$isBlocked = $this->player_model->isBlocked($playerId);
					if(!$isBlocked && (strtotime($lastLoginDate) >= strtotime($playerLastLoginDate))) {

						$updateData = [];
						$updateData['blockedUntil'] = 0;

						$updateData['blocked'] = Player_model::BLOCK_STATUS;

						if($dry_run === 'N') {

							$this->player_model->updatePlayer($playerId, $updateData);

							$kickedPlayer = $this->player_library->kickPlayer($playerId);
							if($kickedPlayer){
								$this->player_library->kickPlayerGamePlatform($playerUsername, $playerId);
							}

							$msg = 'Block/UnBlock Player - Before Adjustment (Unblock) After Adjustment (Block) ';
							$data = array(
								'playerId' => $playerId,
								'changes' => $msg,
								'createdOn' => date('Y-m-d H:i:s'),
								'operator' => $admin_username,
							);
							$this->player_manager->addPlayerInfoUpdates($playerId, $data);

							$this->utils->recordAction('Player Management', lang('member.log.block.website'), "User " . $admin_username . " has adjusted player '" . $playerId . "'");
						}

						$this->utils->debug_log("block Success", $playerUsername);

						array_push($successArr, $playerUsername);
					} else {
						$this->utils->debug_log("skip username:[$playerUsername] playerLastLoginDate:[$playerLastLoginDate]");
						array_push($failArr, $playerUsername);
					}
				}
			}

		}catch(Exception $e){
			$this->player_model->rollbackTrans();  // Trans ISSUE END
			$this->utils->error_log('Error:', $e);
		}

		$rlt = $this->player_model->endTransWithSucc();
		$this->utils->debug_log(__METHOD__,'========= end batchBlockPlayerByUsername ==========',["S" => count($successArr), "F" => count($failArr)]);


	}

	public function patch_lucky_code_ipinfo($period_id){
		$this->utils->info_log('========= start patch_lucky_code_ipinfo ============================pid:'.$period_id);
		$this->load->model(['lucky_code', 'player_model']);
		$tableName = 'lucky_code';
		if($this->db->field_exists('ip', $tableName) && $this->db->field_exists('country', $tableName) && $this->db->field_exists('city', $tableName)){
			// $this->load->model('player_model');
			// $player_registrationIp   = $this->player_model->registrationIP($data['player_id']);
			// $insertArr['ip'] = $player_registrationIp['ip'];
			// $noneFormatIp = $player_registrationIp['noneFormatIp'];


			$this->lucky_code->startTrans();
			$count = $this->lucky_code->get_none_ip_records_count($period_id);
			$this->utils->debug_log('get_none_ip_records_count', $count);

			$records = $this->lucky_code->get_none_ip_records($period_id);
			$this->utils->debug_log('get_none_ip_records', $records);
			$successCount = 0;
			foreach ($records as $key => $value) {
				$playerId = $value['player_id'];
				$period_id = $value['period_id'];
				$affected_rows = $this->lucky_code->update_lucky_code_ipinfo($playerId, $period_id);
				if($affected_rows > 0){
					$this->utils->info_log('update_lucky_code_ipinfo success', $affected_rows, $playerId, $period_id);
					$successCount += $affected_rows;
				}
			}
			$this->utils->debug_log('update_lucky_code_ipinfo successCount', $successCount);
			// $this->lucky_code->get_none_ip_records($period_id);
			$rlt = $this->lucky_code->endTransWithSucc();
			$this->utils->debug_log('get_none_ip_records trans', $rlt);
        } else {
			$this->utils->info_log('patch_lucky_code_ipinfo, not found ip, country, city field');
		}
		$this->utils->info_log('========= end patch_lucky_code_ipinfo ============================');
	}

	/**
	 * batch_delete_internal_messages
	 *
	 * @param string $before_date 2000-01-01
	 * @param string $include_broadcast N/Y
	 * @param number $player_id
	 * @return void
	 */
	public function batch_delete_internal_messages($before_date, $player_id = _COMMAND_LINE_NULL)
	{
		$this->utils->info_log(__METHOD__,'========= start batch_delete_internal_messages ==========',[
			"before_date"=> $before_date,
			"player_id"=> $player_id,
		]);

		$this->load->model(['internal_message']);

		if (empty($before_date)) {
			$this->utils->debug_log(__METHOD__,'before_date is empty.');
			return;
		} else {

			$_date = DateTime::createFromFormat('Y-m-d', $before_date);
			if ($_date == false) {
				$this->utils->debug_log(__METHOD__,"$before_date is not a valid date.");
				return;
			}
			$before_date = $before_date . ' 00:00:00';
		}
		$this->internal_message->startTrans();
		try {
			$player_id = $player_id === _COMMAND_LINE_NULL ? null : $player_id;
			$success = $this->internal_message->deleteMessageByDate($before_date, $player_id, 1);
			$this->utils->info_log("deleteMessageByDate rows:[$success]");
		} catch (Exception $e) {
			$this->internal_message->rollbackTrans();  // Trans ISSUE END
			$this->utils->error_log('Error:', $e);
		}
		$rlt = $this->player_model->endTransWithSucc();

		$this->utils->info_log(__METHOD__,'========= end batch_delete_internal_messages ==========');
	}

	public function patch_data_for_player_promo_referral_id($dateStr = _COMMAND_LINE_NULL){
		$this->utils->info_log('========= start patch_data_for_player_promo_referral_id ==========');
		$this->load->model(['player_promo']);

		$currentDate = $this->utils->getTodayForMysql();
		if($dateStr != _COMMAND_LINE_NULL){
			$currentDate = $this->utils->formatDateForMysql(new DateTime($dateStr));
		}
		$this->utils->debug_log('patch_data_for_player_promo_referral_id current date', $currentDate);

		$playerPromo_rows = [];
		$referralLevel_rows = [];

		$this->db->select('last_referral_id, player_promo_id, release_date')
				 ->from('player_friend_referral_level')
				 ->where('release_date', $currentDate);
		$referralLevel_rows = $this->player_promo->runMultipleRowArray();

		if(!empty($referralLevel_rows)){
			foreach ($referralLevel_rows as $row){
				$player_promo_id = $row['player_promo_id'];
				$referral_id = $row['last_referral_id'];

				$playerPromo_rows[] = $player_promo_id;
				$this->player_promo->updatePlayerPromo($player_promo_id, ['referralId' => $referral_id]);
			}
		}
		$this->utils->info_log('========= end patch_data_for_player_promo_referral_id ==========', $playerPromo_rows);
	}

	public function batch_delete_broadcast_messages($before_date, $broadcast_message_id = _COMMAND_LINE_NULL)
	{
		$this->utils->info_log(__METHOD__,'========= start batch_delete_internal_messages ==========',[
			"before_date"=> $before_date,
			"broadcast_message_id"=> $broadcast_message_id,
		]);

		$this->load->model(['internal_message']);

		if (empty($before_date)) {
			$this->utils->debug_log(__METHOD__,'before_date is empty.');
			return;
		} else {

			$_date = DateTime::createFromFormat('Y-m-d', $before_date);
			if ($_date == false) {
				$this->utils->debug_log(__METHOD__,"$before_date is not a valid date.");
				return;
			}
			$before_date = $before_date . ' 00:00:00';
		}
		$this->internal_message->startTrans();
		try {
			$broadcast_message_id = $broadcast_message_id === _COMMAND_LINE_NULL ? null : $broadcast_message_id;
			$success = $this->internal_message->deleteBroadcastMessagesByDate($before_date, $broadcast_message_id, 1);
			$this->utils->info_log("deleteBroadcastMessagesByDate rows:[$success]");

			$success = $this->internal_message->deletePlayerBroadcastMessages($before_date, $broadcast_message_id, 1);
			$this->utils->info_log("deletePlayerBroadcastMessages rows:[$success]");

		} catch (Exception $e) {
			$this->internal_message->rollbackTrans();  // Trans ISSUE END
			$this->utils->error_log('Error:', $e);
		}
		$rlt = $this->player_model->endTransWithSucc();

		$this->utils->info_log(__METHOD__,'========= end batch_delete_internal_messages ==========');
	}
	public function batchInsertWalletaccountSpentTime($startDate = null, $endDate = null){
		// $startDate = !empty($startDate) ? $startDate . ' 00:00:00' : $this->utils->getTodayForMysql() . ' 00:00:00';
		// $endDate = !empty($endDate) ? $endDate . ' 00:00:00' : $this->utils->getTodayForMysql() . ' 00:00:00';
		$this->utils->debug_log(__METHOD__,'========= start batchInsertWalletaccountSpentTime ==========',$startDate,$endDate);

		$this->load->model(['wallet_model','walletaccount_timelog']);

		$this->db->select('walletaccount.dwDateTime,walletaccount.dwStatus,walletaccount.playerId,walletaccount.walletAccountId,walletaccount.processDateTime')->from('walletaccount')->where('dwStatus', Wallet_model::PAID_STATUS)->where('spent_time is NULL');

		if (!empty($startDate)) {
			$this->db->where('walletaccount.dwDateTime >= ', $startDate);
		}
		if (!empty($endDate)) {
			$this->db->where('walletaccount.dwDateTime <= ', $endDate);
		}

		$q = $this->db->get();
		$rows = $q->result_array();

		$this->utils->printLastSQL();
		$this->utils->debug_log(__METHOD__,'--------count rows',count($rows), $rows);

		$count = 0;
		$count_failed = 0;
		$success_id = [];
		$failed_id = [];
		$no_timelog = [];

		if (!empty($rows)) {

			foreach ($rows as $walletAccounts) {
				$walletAccountId = $walletAccounts['walletAccountId'];
				$dwDateTime = $walletAccounts['dwDateTime'];
				$processDateTime = $walletAccounts['processDateTime'];
				$timelog = $this->walletaccount_timelog->getWalletAccountTimeLogByWalletAccountId($walletAccountId, Wallet_model::PAID_STATUS);

				$this->utils->debug_log(__METHOD__,'--------timelog',$timelog,'walletAccountId',$walletAccountId);

				if (!empty($timelog)) {
					$spent_time = strtotime($timelog['create_date']) - strtotime($dwDateTime);
					$this->utils->info_log(__METHOD__,'--------spent_time - dwDateTime',$spent_time,'walletAccountId',$walletAccountId);
				}else{
					$spent_time = strtotime($processDateTime) - strtotime($dwDateTime);
					$this->utils->info_log(__METHOD__,'--------processDateTime - dwDateTime',$spent_time,'walletAccountId',$walletAccountId);
					$no_timelog[] = $walletAccountId;
				}

				$res = false;

				if (!empty($spent_time)) {
					// $this->utils->info_log(__METHOD__,'--------test set inser table walletAccountId',$walletAccountId);
					$this->db->set('spent_time', $spent_time)->where('walletAccountId', $walletAccountId);
					$res = $this->wallet_model->runAnyUpdate('walletaccount');
				}

				if ($res) {
					$count += 1;
					$success_id[] = $walletAccountId;
				}else{
					$count_failed += 1;
					$failed_id[] = $walletAccountId;
				}
			}
		}
		$this->utils->info_log(__METHOD__,'========= end batchInsertWalletaccountSpentTime ==========',$count,$success_id,$count_failed,$failed_id,'no_timelog',$no_timelog);
	}


	public function generateT1SeamlessOriginalPlatform(){
		if(!$this->utils->getConfig('enabled_remote_seamless_wallet_balance_history')){
			$this->utils->error_log("enabled_remote_seamless_wallet_balance_history is not enabled!!");
			return false;
		}
		$this->load->model('external_system');
		$game_apis = $this->external_system->getAllActiveSeamlessGameApi();
		$t1_games = [];
		if(!empty($game_apis)){
			foreach ($game_apis as $key => $value) {
				$id = $value['id'];
				if( $id >= 1500 && $id < 2000 && empty($value['original_game_platform_id'])){
					$t1_games[] = $value;
				}
			}
		}
		$count = count($t1_games);
		$this->utils->debug_log("Count of t1games to update [{$count}]");
		if(!empty($t1_games)){
			foreach ($t1_games as $keyi => $valuei) {
				$game_api_id = $valuei['id'];
				$api = $this->utils->loadExternalSystemLibObject($game_api_id);
				$original_id = $api->getOriginalPlatformCode();
				if($original_id){
					$data['original_game_platform_id'] = $original_id;
					if($this->external_system->updateGameApi($data, $game_api_id)){
						$this->utils->info_log("Success update original platform id of [{$game_api_id}] to [{$original_id}]");
					} else {
						$this->utils->error_log("Failed update original platform id of [{$game_api_id}] to [{$original_id}]");
					}
				}
			}
		}
	}

	public function insertNewTagRemark($tagRemark){
		$this->utils->debug_log('==insertNewTagRemark==',$tagRemark);
		if(empty($tagRemark)){
			return $this->utils->error_log('empty tagRemark');
		}
		$this->load->model('player_model');
		$success=$this->player_model->createTagRemarks($tagRemark);
		if($success){
			$this->utils->info_log('create tagRemark success', $success, $tagRemark);
		}else{
			$this->utils->error_log('create tagRemark failed', $success);
		}

		$q = $this->db->get();
		$rows = $q->result_array();

		$this->utils->printLastSQL();
		$this->utils->debug_log(__METHOD__,'--------count rows',count($rows), $rows);

		$this->utils->info_log(__METHOD__,'========= insertNewTagRemark end ==========');
	}

	## bash admin/shell/command_mdb_noroot.sh targetmdb sync_adminuser_tele_to_adminuser_tele
	## bash admin/shell/command.sh sync_adminuser_tele_to_adminuser_tele

	public function sync_tele_id_to_adminuser_tele($systemCode,$tele_id_col,$dry_run = 'on'){

		$this->load->model('users');

        $allow_col = ['tele_id', 'tele_id_2', 'tele_id_3'];
        if(!in_array($tele_id_col, $allow_col)){
            return false;
        }
        $this->db->select('userId')
        ->select($tele_id_col)
        ->from('adminusers')
        ->where($tele_id_col.' IS NOT NULL');
        $adminusers = $this->users->runMultipleRowArray();

		$this->utils->debug_log('sync_tele_id_to_adminuser_tele Get adminusers:',[$adminusers]);
		foreach($adminusers as $value){
			$userId=$value['userId'];
			// if($value['tele_id']==$tele_id){
				$checkAdminTel=$this->users->getAdminuserTele($userId, $systemCode);
				if(empty($checkAdminTel)){
					if($dry_run ==='off'){
						$this->users->insertAdminuserTele($userId,$systemCode,$value[$tele_id_col]);
					}else{
						$this->utils->debug_log('sync_tele_id_to_adminuser_tele sync_adminuser_teles Dry_Run SUCCESS','user_id:',$userId,"systemCode:",$systemCode,"tele_id:",$value[$tele_id_col]);
					}
				}else{
					$this->utils->debug_log('sync_tele_id_to_adminuser_tele adminer_tele isset','user_id:',$userId,"tele_id:",$value[$tele_id_col]);
				}
			// }

		}
	}

	public function batch_refund($game_platform_id){
		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);
		$batch_refund_file_path = $api->getSystemInfo("batch_refund_file_path", null);
		// $file = realpath(dirname(__FILE__) . "/../../../") . '/public/bgsoft_refund_december.csv';
		if (!file_exists($batch_refund_file_path)) {;
			$this->utils->error_log("FILE NOT FOUND : batch_refund()", $batch_refund_file_path);
			return;
		}
		// Read the file content
		$file_content = file_get_contents($batch_refund_file_path);
        // Convert CSV content to an array
        $bet_ids = str_getcsv($file_content, "\n");

		if(!empty($api)){
			$rlt=$api->batchRefund($bet_ids);
			$this->utils->debug_log('result of batch refund: ', $rlt);

		}else{
			$this->utils->error_log('NOT FOUND API / batch_refund method not exist on game', $game_platform_id);
		}
		$this->utils->info_log('========= end batch refund ============================');
	}

	public function totalPlayerBettingAmountWithLimitByVIP($playerId, $from, $to){
		$this->load->model(['total_player_game_minute']);
		$this->total_player_game_minute->totalPlayerBettingAmountWithLimitByVIP($playerId, $from, $to);
	}

    /**
    * overview : batchGeneratePlayerCryptoWalletAddress
    * @access	public
    * @param	string separated by spaces
    * @example  sudo ./command.sh batchGeneratePlayerCryptoWalletAddress  '12122 144l 112'
    */
    public function batchGeneratePlayerCryptoWalletAddress($playerIds){
        $this->utils->debug_log('========= start generate crypto wallet address =========');

        $arr = array_map('trim', array_filter(explode(' ', $playerIds)));
        $this->load->model(['player_crypto_wallet_info']);
        $this->load->library('crypto_currency_lib');

        if(FALSE === $this->crypto_currency_lib->init()){
            $this->utils->debug_log('========= Exception result : crypto wallet api not setted =========');
        }

        foreach ($arr as $playerId) {
            $allAddress = $this->crypto_currency_lib->getAllAddress($playerId);
            if(!empty($allAddress) && is_array($allAddress)){
                foreach ($allAddress as $cryptoInfo) {
                    if(empty($cryptoInfo['coinId']) || empty($cryptoInfo['chains'])){
                        $this->utils->debug_log('========= Exception result : crypto is empty =========', $cryptoInfo);
                        continue;
                    }
                    $token = $cryptoInfo['coinId'];
                    foreach ($cryptoInfo['chains'] as $chainInfo) {
                        $isAddressExisted = $this->player_crypto_wallet_info->checkExistedAddress($chainInfo['address']);
                        if($isAddressExisted){
                            $this->utils->debug_log('========= Exception result : the address already exist =========', $chainInfo);
                            continue;
                        }

                        $chain = $chainInfo['chainName'];
                        $address = $chainInfo['address'];
                        $network = $this->player_crypto_wallet_info->getNetworkWithChain($token, $chain);
                        $playerCryptoWallet = $this->player_crypto_wallet_info->getPlayerCryptoWalletByChain($playerId, $chain, $token);

                        if(empty($playerCryptoWallet)){
                            $insertedData = [
                                'token' => $token,
                                'chain' => $chain,
                                'network' => $network,
                                'address' => $address,
                                'externalSystemId' => $this->config->item('crypto_currency_use_api'),
                                'status' => Player_crypto_wallet_info::STATUS_ACTIVE,
                            ];
                            $result = $this->player_crypto_wallet_info->insertCryptoWalletInfo($playerId, $insertedData);
                            $this->utils->debug_log('========= insert result =========', $result, $insertedData);                                             
                        }else{                
                            $updatedData = [
                                'network' => $network,
                                'address' => $address,
                                'externalSystemId' => $this->config->item('crypto_currency_use_api'),
                                'status' => Player_crypto_wallet_info::STATUS_ACTIVE,
                            ];
                            $result = $this->player_crypto_wallet_info->updateCryptoWalletInfo($playerCryptoWallet['id'], $updatedData);
                            $this->utils->debug_log('========= update result =========', $result, $updatedData);
                        }
                    }
                }
            }
        }
        $this->utils->debug_log('========= end generate crypto wallet address =========');
    }

	/**
	 * overview : import tounament series
	 * @param dryRun $dryRun
	 */
	public function oneworks_importer_tounaments($dryRun = 'TRUE', $updateTarget = null) {
		$dryRun = $dryRun=='TRUE';
		$this->utils->info_log('========= start import tournaments ============================', 'dryRun Mode', $dryRun);
		$importerTounaments = $this->utils->getConfig('oneworks_importer_tounaments');
		$this->load->model(['tournament_model']);
		$this->load->library('tournament_lib');

		//step 1 : validate tournament data
		// $requireDatas = ['tournament', 'tournament_schedule', 'tournament_event', 'tournament_game'];
		$requireDatas = ['tournament', 'tournament_schedule', 'tournament_event'];
		$vaildedGameData = [];

		foreach ($requireDatas as $data) {
			if(empty($importerTounaments[$data])){
				$this->utils->error_log("Failed: missing require data" , $data);
				return;
			}
			if($data == 'tournament'){
				// "gamePlatformId" => array(1567),
				// "gameTypeId" => array(1418),
				// "gameTagId" => array(),
				// "gameDescriptionId" => array(18563),

				$importerTounaments['tournament']['gamePlatformId'] = json_encode($importerTounaments['tournament']['gamePlatformId']);
				$importerTounaments['tournament']['gameTypeId'] = json_encode($importerTounaments['tournament']['gameTypeId']);
				$importerTounaments['tournament']['gameTagId'] = json_encode($importerTounaments['tournament']['gameTagId']);
				$importerTounaments['tournament']['gameDescriptionId'] = json_encode($importerTounaments['tournament']['gameDescriptionId']);

			}
			if($data == 'tournament_game'){
				$vaildedGameData = [];
				$tournamentGameData = $importerTounaments['tournament_game'];
				$tagId = $tournamentGameData['gameTagId'];
				foreach ($tournamentGameData['gamePlatforms'] as $games) {
					$combinations = [
						'platformId' => $games['platformId'],
						'gameTypeId' => $games['data']['typeId'],
						'gameId' => $games['data']['descriptionId'],
						'gameTagId' => $tagId,
					];
					$vailded = $this->tournament_lib->checkGameSettingsFlow($combinations);
					if(!$vailded['status']){
						$this->utils->error_log("Failed: illegally game rule", $combinations, $vailded['message']);
						return;
					}
					$vaildedGameData[] = $combinations;
				}
			}
		}
		$success = function() use($dryRun, $importerTounaments, $vaildedGameData, $updateTarget){
			if($dryRun){
				$this->utils->info_log('========= dry run success =======', $dryRun);
				return true;
			}

			//step 2 : insert tournament series data
			$tournamenst = $importerTounaments['tournament'];
			$tournamentSchedule = $importerTounaments['tournament_schedule'];
			$tournamentEvents = $importerTounaments['tournament_event'];
			if(!empty($updateTarget)){
				$this->utils->error_log("update tournament id::".$updateTarget, $tournamenst);
				$tournamentId = $this->tournament_model->updateTournament($tournamenst, $updateTarget);
				// return true;
				$tournamentId = $updateTarget;
			} else {
				$tournamentId = $this->tournament_model->createTournament($tournamenst);
			}
			if(empty($tournamentId)){
				$this->utils->error_log("Failed: create tournament", $tournamenst);
				return false;
			}

			$tournamentSchedule['tournamentId'] = $tournamentId;
			$currentScheduleId = null;
			if(!empty($tournamentSchedule['currentScheduleId'])){
				$currentScheduleId = $tournamentSchedule['currentScheduleId'];
				$this->utils->error_log("update schedule id::".$tournamentSchedule['currentScheduleId'], $tournamentSchedule);
				unset($tournamentSchedule['currentScheduleId']);
				$scheduleId = $this->tournament_model->updateTournamentSchedule($tournamentSchedule, $currentScheduleId);
			} else {
				$scheduleId = $this->tournament_model->createTournamentSchedule($tournamentSchedule);
			}
			if(empty($scheduleId)){
				$this->utils->error_log("Failed: create tournament_schedule", $tournamentSchedule);
				return false;
			}
			if(!empty($currentScheduleId) || !empty($updateTarget)){
				return true;
			}

            foreach ($tournamentEvents as $eventData) {
                $eventData['scheduleId'] = $scheduleId;
                $ranks = $eventData['tournament_rank'];
                if(isset($eventData['tournament_rank'])){
                    unset($eventData['tournament_rank']);
                }
                $eventId = $this->tournament_model->createTournamentEvent($eventData);
                if(empty($eventId)){
                    $this->utils->error_log("Failed: create tournament_event", $eventData);
                    return false;
                }else{
                    $this->utils->debug_log("Success Setted Game event", $eventId);
                    foreach ($ranks as $rank) {
                        $rank['eventId'] = $eventId;
                        $rankId = $this->tournament_model->createTournamentRank($rank);
                        if(!empty($rankId)){
                            $this->utils->debug_log("Success Setted Game rank", $rankId);
                        }
                    }
                }
            }

			if(!empty($vaildedGameData)){
				foreach ($vaildedGameData as $data) {
					$tournamentGame['tournamentId'] = $tournamentId;
					$tournamentGame['gameTagId'] = $data['gameTagId'];
					$tournamentGame['gameTypeId'] = $data['gameTypeId'];
					$tournamentGame['gamePlatformId'] = $data['platformId'];
					$tournamentGame['gameDescriptionId'] = $data['gameId'];
					$tournamentGameId = $this->tournament_model->createTournamentGame($tournamentGame);
					if(!empty($eventId)){
						$this->utils->debug_log("Success Setted Game Id", $tournamentGameId);
					}
				}
			}
			return true;
		};
		if($success()){
			$this->utils->info_log('=========import status success=========');
		}else{
			$this->utils->info_log('=========import status failed=========');
		}
		$this->utils->info_log('========= end import tournaments ============================');
	}

    /**
     * overview : batchSyncReferralId
     * 
     * @param string $dry_run
     * sh admin/shell/command_mdb_noroot.sh thb batchSyncReferralId 'TRUE'
     */
    public function batchSyncReferralId($dry_run = 'TRUE'){
        $this->utils->info_log('========= start batchSyncReferralId ============================');
        $this->load->model(['Player_friend_referral', 'player_model']);

        $mismatchPlayers = $this->Player_friend_referral->getMismatchInvitedPlayer();
        $this->utils->debug_log('========= get mismatchIds ============================', $mismatchPlayers);
        $successArr = [
            "successCount" => 0,
            "successUser" => []
        ];

        if( count($mismatchPlayers) > 0 ){
            foreach ($mismatchPlayers as $mismatchPlayer) {
                $success = false;
                $invited_player = $mismatchPlayer['childPlayerId'];
                $parent_player = $mismatchPlayer['parentPlayerId'];
                if($dry_run !== 'OFF'){
                    $successArr['successCount'] += 1;
                    $successArr['successUser'][] = $mismatchPlayer['username'];
                    continue;
                } 
                if($dry_run === 'OFF'){
                    $success = $this->player_model->updatePlayer($invited_player,array("refereePlayerId" => $parent_player));
                }
                if($success){
                    $successArr['successCount'] += 1;
                    $successArr['successUser'][] = $mismatchPlayer['username'];
                }
            }
        }

        $this->utils->info_log('========= end batchSyncReferralId ============================',  $successArr);
        return true;

    }

    public function soft_delete_tournament_series_by_tournament_id($tournamentId, $dryRun = 'TRUE'){
        $this->utils->info_log('========= start softDeleteTournament ============================');
        $this->load->model(['tournament_model']);
        $dryRun = $dryRun=='TRUE';
        if($dryRun){
            $result = $this->tournament_model->getTournamentSeriesIds($tournamentId);
            $this->utils->info_log('========= dry run success =======', $dryRun);
            $this->utils->info_log('========= soft delete data =======', $result);
            return true;
        }else{
            $result = $this->tournament_model->getTournamentSeriesIds($tournamentId);
            if(!empty($result)){
                if(!isset($result['tournamentId']) || !isset($result['scheduleId']) || !isset($result['eventIds'])){
                    $this->utils->error_log('tournamentId or scheduleId or eventIds not found', $result);
                    return false;
                }
                $this->tournament_model->softDeleteTournament($result['tournamentId']);
                $this->tournament_model->softDeleteTournamentSchedule($result['scheduleId']);
                $eventIds = explode(",", $result['eventIds']);
                foreach ($eventIds as $id) {
                    $this->tournament_model->softDeleteTournamentEvent($id);
                }
                $this->utils->info_log('========= soft delete data =======', $result);
                $this->utils->info_log('========= soft delete success =======');
            }
        }
        $this->utils->info_log('========= end softDeleteTournament ============================');
    }

    public function checkGameClassWithCallback(){
    	$gameApis = $this->external_system->getAllGameApis();
    	$classWithCallback = [];
		if(!empty($gameApis)){
			foreach($gameApis as $key => $gameApi){
				$api = $this->utils->loadExternalSystemLibObject($gameApi['id']);
				if(!$api){
					continue;
				}

				try {

					if(!method_exists($api, 'callback')) {
						continue;
					}

					$classWithCallback[$gameApi['id']] = $gameApi['system_code'];
		        } catch (Exception $e) {
		        	$this->CI->utils->error_log('checkGameClassWithCallback  ERROR: '.$e->getMessage());
		            continue;
		        }
			}
		}
		$this->utils->info_log('========= Games With Callback =======', $classWithCallback);
    }

    public function syncSaGamingReferenceTransactionId($dateTimeFrom, $dateTimeTo) {
        $game_platform_id = SA_GAMING_SEAMLESS_API;

        $game_api = $this->utils->loadExternalSystemLibObject($game_platform_id);
        $result = $game_api->syncReferenceTransactionId($dateTimeFrom, $dateTimeTo);

        $this->utils->info_log(__METHOD__, 'result', $result);

        return $result;
    }

    public function syncTournamentRecords($game_platform_id, $start_time, $end_time, $game_username = null, $tournament_ids = null) {
        $game_api = $this->utils->loadExternalSystemLibObject($game_platform_id);

        $result = [
            'inserted' => 0,
            'updated' => 0,
        ];

        if (!empty($game_username) && $game_username == '_null') {
            $game_username = null;
        }

        // $tournament_ids can be string or array
        // if use array $tournament_ids in command use this format '6291:6299'
        if (!empty($tournament_ids) && !is_array($tournament_ids) && strpos($tournament_ids, ':') !== false) {
            $tournament_ids = explode(':', $tournament_ids);
        }

        $request_params = [
            'start_time' => $start_time,
            'end_time' => $end_time,
            'tournament_ids' => $tournament_ids,
            'player_name' => $game_username,
            'save_to_game_logs' => true,
        ];

        // function
        $save_tournament_records = function ($request_params) use ($game_api, &$result) {
            // save tournament records to game logs
            $response = $game_api->getTournamentRecords($request_params);
                            
            if (!empty($response['inserted'])) {
                $result['inserted'] += $response['inserted'];
            }

            if (!empty($response['updated'])) {
                $result['updated'] += $response['updated'];
            }

            $this->utils->info_log(__METHOD__, $result);
        };

        if (empty($tournament_ids)) {
            // get tournament list
            $tournament_list = $game_api->getTournamentList([
                'start_time' => $start_time,
                'end_time' => $end_time,
            ]);

            if (!empty($tournament_list['data']) && is_array($tournament_list['data'])) {
                foreach ($tournament_list['data'] as $data) {
                    $request_params['tournament_ids'] = !empty($data['tournamentId']) ? $data['tournamentId'] : null;
                    $save_tournament_records($request_params);
                }
            }
        } else {
            $request_params['tournament_ids'] = $tournament_ids;
            $save_tournament_records($request_params);
        }

        $this->utils->info_log(__METHOD__, 'total', $result);
    }
}