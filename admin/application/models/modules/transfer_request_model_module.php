<?php
trait transfer_request_model_module {


	/**
	 * detail: adding transfer request
	 * note: wallet id is game platform id
	 *
	 * @param int $playerId
	 * @param int $fromWalletTypeId
	 * @param int $toWalletTypeId
	 * @param double $amount
	 * @param int $userId
	 * @param int $secure_id wallet account
	 * @return boolean
	 */
	public function addTransferRequest($playerId, $fromWalletTypeId, $toWalletTypeId, $amount,
			$userId = null, &$secure_id = null, $external_system_id=null, $external_transaction_id=null) {

		$prefix = strval('T'.date('ymd'));
		$secure_id = $this->getSecureId('transfer_request', 'secure_id', true, $prefix);
		
		$now=$this->utils->getNowForMysql();
		$data = array(
			'player_id' => $playerId,
			'from_wallet_type_id' => $fromWalletTypeId,
			'to_wallet_type_id' => $toWalletTypeId,
			'amount' => $amount,
			'user_id' => $userId,
			'secure_id' => $secure_id,
			'external_system_id' => $external_system_id,
			'status' => self::STATUS_TRANSFER_REQUEST,
			'created_at' => $now,
			'updated_at' => $now,
			'external_transaction_id' => $external_transaction_id,
			'notes'=>$external_transaction_id,
			// 'guess_success' => time(),
		);
		$this->utils->debug_log('record transfer time '.$secure_id.' start time: '.time());

		$id=$this->insertData('transfer_request', $data);
		if(!empty($id)){
			//insert into transfer_request_external_info
			$extraInfo=[
				'transfer_request_id'=>$id,
				'external_trans_id_from_gamegatewayapi'=>$external_transaction_id,
				'secure_id'=>$secure_id,
				'request_id'=>$this->utils->getRequestId(),
				'created_at'=>$now,
				'updated_at'=>$now,
			];
			$extraId=$this->insertData('transfer_request_external_info', $extraInfo);
			if(empty($extraId)){
				$this->utils->error_log('update transfer_request_external_info failed', $extraInfo);
			}
		}else{
			$this->utils->error_log('update transfer_request failed', $data);
		}
		return $id;
	}

	/**
	 * detail: set status success for transfer request
	 *
	 * @param int $requestId
	 * @param int $response_result_id
	 * @param int $external_transaction_id
	 * @return boolean
	 */
	public function setSuccessToTransferReqeust($requestId, $response_result_id = null, $external_transaction_id = null,
		$transfer_status=null, $reason_id=null, $realAmount=null) {
		$now=$this->utils->getNowForMysql();
		//ms
		$elapsed=intval($this->utils->getExecutionTimeToNow()*1000);
		$this->db->set('status', self::STATUS_TRANSFER_SUCCESS)->set('response_result_id', $response_result_id)
			->set('updated_at', $now)
			->set('external_transaction_id', $external_transaction_id)
			->set('guess_success', $elapsed)
			->where('id', $requestId);
		$this->utils->debug_log('record transfer time '.$requestId.', execution time: '.$elapsed);
		if(!empty($transfer_status)){
			$this->db->set('transfer_status', $transfer_status);
		}
		if(!empty($reason_id)){
			$this->db->set('reason_id', $reason_id);
		}
		if($realAmount!==null){
			$this->db->set('amount', $realAmount);
			$this->utils->debug_log('update real amount to transfer request', $realAmount, $requestId);
		}

		$succ=$this->runAnyUpdateWithoutResult('transfer_request');
		if($succ){
			//update transfer_request_external_info
			$this->db->set('external_transaction_id_from_game', $external_transaction_id)
				->set('updated_at', $now)
				->set('response_result_id', $response_result_id)
				->where('transfer_request_id', $requestId);
			$extraSucc=$this->runAnyUpdateWithoutResult('transfer_request_external_info');
			if(!$extraSucc){
				$this->utils->error_log('setSuccessToTransferReqeust transfer_request_external_info failed', $requestId, $response_result_id, $external_transaction_id);
			}
		}else{
			$this->utils->error_log('setSuccessToTransferReqeust transfer_request failed', $requestId, $response_result_id, $external_transaction_id);
		}
		return $succ;
	}

	/**
	 * detail: set status failed for transfer request
	 *
	 * @param int $requestId
	 * @param int $response_result_id
	 * @param int $external_transaction_id external transactio
	 * @return boolean
	 */
	public function setFailedToTransferReqeust($requestId, $response_result_id = null, $external_transaction_id = null,
		$transfer_status=null, $reason_id=null, $realAmount=null) {
		$now=$this->utils->getNowForMysql();
		//ms
		$elapsed=intval($this->utils->getExecutionTimeToNow()*1000);
		$this->db->set('status', self::STATUS_TRANSFER_FAILED)->set('response_result_id', $response_result_id)
			->set('updated_at', $now)
			->set('external_transaction_id', $external_transaction_id)
			->set('guess_success', $elapsed)
			->where('id', $requestId);

		$this->utils->debug_log('record transfer time '.$requestId.', execution time: '.$elapsed);
		if(!empty($transfer_status)){
			$this->db->set('transfer_status', $transfer_status);
		}
		if(!empty($reason_id)){
			$this->db->set('reason_id', $reason_id);
		}
		if($realAmount!==null){
			$this->db->set('amount', $realAmount);
			$this->utils->debug_log('update real amount to transfer request', $realAmount, $requestId);
		}

		$succ=$this->runAnyUpdateWithoutResult('transfer_request');
		if($succ){
			//update transfer_request_external_info
			$this->db->set('external_transaction_id_from_game', $external_transaction_id)
				->set('updated_at', $now)
				->set('response_result_id', $response_result_id)
				->where('transfer_request_id', $requestId);
			$extraSucc=$this->runAnyUpdateWithoutResult('transfer_request_external_info');
			if(!$extraSucc){
				$this->utils->error_log('setSuccessToTransferReqeust transfer_request_external_info failed', $requestId, $response_result_id, $external_transaction_id);
			}
		}else{
			$this->utils->error_log('setSuccessToTransferReqeust transfer_request failed', $requestId, $response_result_id, $external_transaction_id);
		}
		return $succ;
	}

	/**
	 * detail: get the last failed transfer request
	 *
	 * @param int $gamePlatfromId it must be the wallet type id
	 * @param int $playerId transfer_request field
	 * @param int $transferRequestType @deprecated
	 * @return array
	 */
	public function lastFailedTransferRequest($gamePlatformId, $playerId, $transferRequestType) {

		// switch ($transferRequestType) {

		// 	case Transactions::TRANSFER_FROM_SUB_WALLET_TO_MAIN_WALLET:
		// 		$transferRequestType = 'transfer_request.from_wallet_type_id';
		// 		break;

		// 	case Transactions::TRANSFER_TO_SUB_WALLET_FROM_MAIN_WALLET:
		// 		$transferRequestType = 'transfer_request.to_wallet_type_id';
		// 		break;

		// 	default:
		// 		return false;
		// 		break;
		// }

		$this->db->select('transfer_request.*');
		$this->db->select('response_results.request_params');
		$this->db->from('transfer_request');
		$this->db->join('response_results', 'response_results.id = transfer_request.response_result_id');
		// $this->db->where($transferRequestType, $gamePlatformId);
		$this->db->where('(transfer_request.from_wallet_type_id', $gamePlatformId);
		$this->db->or_where('transfer_request.to_wallet_type_id', $gamePlatformId . ')', false);
		$this->db->where('transfer_request.player_id', $playerId);
		$this->db->where('transfer_request.status', self::STATUS_TRANSFER_FAILED);
		$this->db->where('transfer_request.updated_at >=', date('Y-m-d H:i:s', strtotime('-6 hours'))); # only for past 6 hours
		$this->db->order_by('transfer_request.created_at', 'DESC');
		$this->db->limit(1);

		$query = $this->db->get();

		$row = $query->row_array();

		if ($row) {
			$row['request_params'] = json_decode($row['request_params'], true);
		}

		return $row;
	}

	/**
	 * detail: count all transfer request
	 *
	 * @return array
	 */
	public function transfer_request_count() {
		$this->db->select('status');
		$this->db->select('count(1) as count', null, false);
		$this->db->from('transfer_request');
		$this->db->group_by('status');
		$this->db->order_by('status', 'asc');

		$result = $this->runMultipleRowArray();
		return @array_combine(array_column($result, 'status'), array_column($result, 'count'));
	}

	public function getTransferRequestById($id){

		if(!empty($id)){
			$this->db->from('transfer_request')->where('id', $id);
			$row= $this->runOneRowArray();

			//try fix external_system_id
			if(empty($row['external_system_id'])){
				if($row['from_wallet_type_id']==self::MAIN_WALLET_ID){

					$row['external_system_id']=$row['to_wallet_type_id'];

				}else{

					$row['external_system_id']=$row['from_wallet_type_id'];

				}
			}

			return $row;
		}

		return null;

	}

	public function updateTransferQueryStatus($id, $transfer_status){
		if(!empty($id)){
			$data=['transfer_status'=>$transfer_status, 'updated_at'=>$this->utils->getNowForMysql()];
			$this->db->set($data)->where('id', $id);

			return $this->runAnyUpdate('transfer_request');
		}

		return null;
	}

	public function getTransferStatusFromTransferRequest($id){
		if(!empty($id)){
			$this->db->from('transfer_request')->select('transfer_status')->where('id', $id);

			return $this->runOneRowOneField('transfer_status');
		}

		return null;
	}

	public function getFixFlagFromTransferRequest($id){
		if(!empty($id)){
			$this->db->from('transfer_request')->select('fix_flag')->where('id', $id);

			return $this->runOneRowOneField('fix_flag');
		}

		return null;
	}

	public function updateTransferQueryStatusAndFixFlag($id, $transfer_status, $fix_flag){
		if(!empty($id)){
			$data=['transfer_status'=>$transfer_status, 'fix_flag'=> $fix_flag, 'updated_at'=>$this->utils->getNowForMysql()];
			$this->db->set($data)->where('id', $id);

			return $this->runAnyUpdate('transfer_request');
		}

		return null;
	}

	/**
	 * searchSuspiciousTransferRequest
	 *
	 * 2 rules:
	 * search all deposit and status is success and transfer status is unknown or declined
	 * withdraw and status is failed and transfer status is unknown or approved
	 *
	 * @param  string $from from date time
	 * @param  string $to   to date time
	 * @return array rows of result or empty
	 */
	public function searchSuspiciousTransferRequest($from, $to){

		$this->load->model(['wallet_model', 'external_system', 'transactions', 'player_model']);

		$this->utils->loadAnyGameApiObject();

		$db = $this->db;

		// $secondReadDB->from('transfer_request')->where('created_at >=', $from)
		// 	->where('created_at <=', $to)->where();

		$sql=<<<EOD
select *
from transfer_request
where created_at >= ? and created_at <= ?
and (
  (from_wallet_type_id=? and status=? and transfer_status in (?, ?)) or
  (to_wallet_type_id=? and status=? and transfer_status in (?, ?))
 )
and fix_flag= ?
EOD;
		// $this->db->from('game_logs');

		//deposit and success and transfer status is unknown or declined
		//withdrawal and failed and status is failed and transfer status is unknown or approved
		$qry=$db->query($sql, [$from, $to,
			self::MAIN_WALLET_ID, self::STATUS_TRANSFER_SUCCESS,
				Abstract_game_api::COMMON_TRANSACTION_STATUS_DECLINED, Abstract_game_api::COMMON_TRANSACTION_STATUS_UNKNOWN,
			self::MAIN_WALLET_ID, self::STATUS_TRANSFER_FAILED,
				Abstract_game_api::COMMON_TRANSACTION_STATUS_APPROVED, Abstract_game_api::COMMON_TRANSACTION_STATUS_UNKNOWN,
			self::DB_FALSE]);

		// $success=!empty($qry);
		$action_of_search_suspicious_transfer_request=$this->utils->getConfig('action_of_search_suspicious_transfer_request');
		$cnt=0;

		$this->utils->debug_log('action_of_search_suspicious_transfer_request', $action_of_search_suspicious_transfer_request);

		//set $row , if row is null, just quit
		while (!empty($qry) && $row=$qry->nextRowArray()) {

		// $this->loopRowsFromReadySql(function($row){

			// $stop=false;

			$m1=memory_get_usage();

			// $this->utils->debug_log($row['id'].' '.$row['bet_amount'].', '.round(memory_get_usage()/(1024*1024), 2));

			//process
			$this->utils->debug_log($row['id'].' '.$row['amount'].', '.round(memory_get_usage()/(1024*1024), 2));

			if($action_of_search_suspicious_transfer_request=='update_status'){

				if($row['transfer_status']==Abstract_game_api::COMMON_TRANSACTION_STATUS_UNKNOWN){
					//try query status first
					list($success, $status, $error_message, $message)=$this->utils->queryAndUpdateTransferRequestStatus($row);
					if($success){
						$this->appendNoteToTransferRequest($row['id'], 'query api status:'.$status.', status:'.$row['status']);
					}
					$this->utils->debug_log('queryAndUpdateTransferRequestStatus:'.$row['id'],$success, $status, $error_message, $message);
				}else{

					$this->utils->debug_log('ignore queryAndUpdateTransferRequestStatus:'.$row['id'],$success, $status, $error_message, $message);
				}

			}else if($action_of_search_suspicious_transfer_request=='auto_fix'){

				list($success, $status, $error_message, $message)=$this->utils->fixTransferRequest($row);
				$this->utils->debug_log('fixTransferRequest:'.$row['id'],$success, $status, $error_message, $message);

			}else{
				//none
				$this->utils->debug_log('ignore action');
			}


			$m2=memory_get_usage();

			$cnt++;
			unset($row);

			$m3=memory_get_usage();

			$this->utils->debug_log($m1.' > '.$m2.' > '.$m3.', '.($m2-$m1).', '.($m3-$m2));

			// return $stop;

		// });
		}

		$qry->free_result();

		$this->utils->debug_log('count', $cnt);
	}

	public function appendNoteToTransferRequest($id, $note){

		$sql = "update transfer_request set notes=concat(ifnull(notes,''),' | ',?) where id=?";
		return $this->runRawUpdateInsertSQL($sql, array($note, $id));
	}

	public function getSecureIdByExternalTransactionId($external_transaction_id){
		$this->db->select('secure_id');
		$this->db->where('external_transaction_id', $external_transaction_id);
		$this->db->from('transfer_request');
		return $this->runOneRowOneField('secure_id');
	}

	public function getAllSuspiciousBy($from, $to){

		$sql=" select id, secure_id, created_at from transfer_request ".
			" where ((transfer_request.status=".Wallet_model::STATUS_TRANSFER_SUCCESS." and transfer_request.transfer_status = '".
			Abstract_game_api::COMMON_TRANSACTION_STATUS_DECLINED."' ) or (transfer_request.status=".Wallet_model::STATUS_TRANSFER_FAILED.
			" and transfer_request.transfer_status ='".Abstract_game_api::COMMON_TRANSACTION_STATUS_APPROVED."'))".
			" and created_at>=? and created_at<=?";

		return $this->runRawSelectSQLArray($sql, [$from, $to]);

	}

	public function getTransferRequestByExternalTransactionIdAndExternalSystemId($external_transaction_id, $external_system_id){
		$this->db->where('external_transaction_id', $external_transaction_id)->where('external_system_id', $external_system_id);
		$this->db->from('transfer_request');
		$row= $this->runOneRowArray();
		if(empty($row)){
			//not find, retry
			$this->db->select('transfer_request.*')
			  ->from('transfer_request')->join('transfer_request_external_info', 'transfer_request_external_info.transfer_request_id=transfer_request.id')
			  ->where('transfer_request_external_info.external_trans_id_from_gamegatewayapi', $external_transaction_id)
			  ->where('transfer_request.external_system_id', $external_system_id);
			$row= $this->runOneRowArray();
		}
		//try fix external_system_id
		if(!empty($row) && empty($row['external_system_id'])){
			if($row['from_wallet_type_id']==self::MAIN_WALLET_ID){

				$row['external_system_id']=$row['to_wallet_type_id'];

			}else{

				$row['external_system_id']=$row['from_wallet_type_id'];

			}
		}

		return $row;
	}

	public function existsTransferRequestByExternalTransactionIdAndExternalSystemId($external_transaction_id, $external_system_id){
		$this->db->where('external_transaction_id', $external_transaction_id)->where('external_system_id', $external_system_id);
		$this->db->from('transfer_request');
		return $this->runExistsResult();
	}

	public function scanTimeTransferRequestThemGoMaintenance($from, $to){

		$this->load->model(['external_system', 'transactions', 'player_model']);

		$this->utils->loadAnyGameApiObject();

		$db = $this->db;
		//28,27,26,23,55,56
		$codes=implode(',',Abstract_game_api::DEFAULT_GUESS_SUCCESS_CODE);

		$sql=<<<EOD
select count(transfer_request.id) as cnt, transfer_request.external_system_id
from transfer_request join response_results on (transfer_request.response_result_id=response_results.id)
where transfer_request.created_at >= ? and transfer_request.created_at <= ?
and response_results.sync_id in ($codes)
group by transfer_request.external_system_id
EOD;

		$this->utils->debug_log('scanTimeTransferRequestThemGoMaintenance sql', $sql, $from, $to);

		$qry=$db->query($sql, [$from, $to]);
		$cnt=0;
		$threshold_of_transfer_timeout=$this->utils->getConfig('threshold_of_transfer_timeout');
		//set $row , if row is null, just quit
		while (!empty($qry) && $row=$qry->nextRowArray()) {

		// $this->loopRowsFromReadySql(function($row){

			// $stop=false;

			$m1=memory_get_usage();

			// $this->utils->debug_log($row['id'].' '.$row['bet_amount'].', '.round(memory_get_usage()/(1024*1024), 2));

			//process
			$this->utils->debug_log('found '.$row['cnt'].' timeout on '.$row['external_system_id'].', '.round(memory_get_usage()/(1024*1024), 2));
			if($row['cnt']>=$threshold_of_transfer_timeout){
				if($this->utils->getConfig('scan_timeout_transfer_then_go_maintenance')){
					$this->utils->debug_log('set game '.$row['external_system_id'].' to maintenance mode');
					// $result=$this->external_system->enterMaintenanceMode($row['external_system_id']);
					if(!$result){
						$this->utils->error_log('set maintenance failed on game '.$row['external_system_id']);
					}
				}else{
					$this->utils->debug_log('scan_timeout_transfer_then_go_maintenance is false, do nothing');
				}
			}else{
				$this->utils->debug_log('count of timeout '.$row['cnt'].' < '.$threshold_of_transfer_timeout.', do nothing');
			}

			$m2=memory_get_usage();

			$cnt++;
			unset($row);

			$m3=memory_get_usage();

			$this->utils->debug_log('memory: '.$m1.' > '.$m2.' > '.$m3.', '.($m2-$m1).', '.($m3-$m2));

			// return $stop;

		// });
		}

		$qry->free_result();

		$this->utils->debug_log('count', $cnt);
		return $cnt;
	}

	public function getTimeOutTransferRequest($from , $to, $token = null, $db = null, $db_name = null){
		/* format
		Date Time: May 27, 2020 (17:00 - 18:00) 0 timeout
		idngame: 0
		idngame2: 0
		idngameother: 0
		*/
		$this->utils->loadAnyGameApiObject();
		$sync_id = CURLE_OPERATION_TIMEOUTED;
		$codes=implode(',',Abstract_game_api::DEFAULT_GUESS_SUCCESS_CODE);
        // $db = $this->db;

		$sql=<<<EOD
select
group_concat(tr.secure_id) as secure_ids,
rr.system_type_id as system_type_id,
count(tr.id) as count,
tr.player_id as player_id

from transfer_request as tr
JOIN response_results as rr on rr.id = tr.response_result_id

where
tr.created_at >= ? and tr.created_at <= ? and rr.sync_id in ($codes)
group by rr.system_type_id,tr.player_id;
EOD;
		$this->utils->debug_log('getTimeOutTransferRequestSummary sql', $sql, $from, $to);

		$qry =$db->query($sql, [$from, $to]);
		$rows =$qry->result_array();

		$this->utils->debug_log('getTimeOutTransferRequestSummary rows', $rows);
		return $rows;
	}

	public function generate_timeout_transfer_request_notification($rows, $from, $to, $token, $db_name){
		$wallet_map = $this->utils->getGameSystemMap();
		$total_count = 0;

		$user = "# GW001 Timeout Request";
		$config = $this->utils->getConfig('export_timeout_request_cron_settings');

		$export_link = null;
		$body = "**Date Time**:{$from} to {$to}  ";
		$body .= "**Database**:{$db_name}  ";

		if($config){
	        $user .= " ({$config['host']})";
	        $export_link = $config['client_csv_download_base_url'].site_url('/export_data/queue/'.$token);
		}

		if(!empty($rows)){
			foreach ($rows as $row) {
				$platform = isset($wallet_map[$row['system_type_id']]) ? $wallet_map[$row['system_type_id']] : "Unkown";
				$body .= "  **{$platform}**: _{$row['count']}_  ";
				$total_count += $row['count'];
			}
		}
		$this->utils->debug_log('timeout_transfer_request totalcount', $total_count);
		$body .= "  **Total**: _{$total_count}_ timeout";
		$message = [
			null,
			$body,
			"[#Export Link]({$export_link})\n"
		];

		#Checking for maintenance notification
		$platform_for_maintenance = $this->get_timeout_platforms_for_maintenance($rows);
		if(!empty($platform_for_maintenance)){
			$platform_name_list = array();
			$str_maintenance_link = "";
			foreach ($platform_for_maintenance as $platform_id) {
				$plaform_name= isset($wallet_map[$platform_id]) ? $wallet_map[$platform_id] : "Unkown";
				$platform_name_list[] = $plaform_name;
				$maintenance_link = $config['client_csv_download_base_url'].site_url('/game_api/view_set_game_api_for_maintenance?platforms='.$platform_id);
				$str_maintenance_link .= "  [#{$plaform_name} Set Maintenance Link]({$maintenance_link})  ";
			}
			$str_list = implode(", ",$platform_name_list);
			$body .=  " \n **Alert For Game Maintenance**: _{$str_list}_   ";
			// $platforms = implode("%2c",$platform_for_maintenance);
			// $maintenance_link = $config['client_csv_download_base_url'].site_url('/game_api/view_set_game_api_for_maintenance?platforms='.$platforms);
			$message = [
				null,
				$body,
				"{$str_maintenance_link}",
				"[#Export Link]({$export_link})\n"
			];
		}

        $channel = $this->utils->getConfig('get_timeout_transfer_request_notification_channel');
        $this->CI->load->helper('mattermost_notification_helper');

        $channel = $channel;
        sendNotificationToMattermost($user, $channel, [], $message);
	}

	public function get_timeout_platforms_for_maintenance($rows){
		$enabled_timeout_request_maintenance = $this->utils->getConfig('enabled_timeout_request_maintenance');
		$config_timeout_count = $this->utils->getConfig('timeout_request_count_for_maintenance');
		$platform_for_maintenance= array();
		if($enabled_timeout_request_maintenance){
			if(!empty($rows)){
				foreach ($rows as $key => $row) {
					if($row['count'] >= $config_timeout_count){
						$platform_for_maintenance[] = $row['system_type_id'];
					}
				}
			}
		}
		return $platform_for_maintenance;
	}

	public function searchSuspiciousTransferFromSubwalletRequest($from, $to, $db=null){
		$settings = $this->utils->getConfig('suspicious_withdrawal_settings');
		$adjust_minutes_get_last_transfer = $settings['adjust_minutes_get_last_transfer'];
		$min_amount = 0;
		$max_amount = 1000000;
		$doubled_min_amount = 0;
		$doubled_max_amount = 1000000;
		$multiplier = 1000;
		$currency = strtolower($this->utils->getCurrentCurrency()['currency_code']);
		if(isset($settings[$currency])){
			$min_amount = isset($settings[$currency]['min_amount']) && $settings[$currency]['min_amount']?$settings[$currency]['min_amount']:0;
			$max_amount = isset($settings[$currency]['max_amount']) && $settings[$currency]['max_amount']?$settings[$currency]['max_amount']:1000000;
			$multiplier = isset($settings[$currency]['multiplier']) && $settings[$currency]['multiplier']?$settings[$currency]['multiplier']:1000;	
			$doubled_min_amount = isset($settings[$currency]['doubled_min_amount']) && $settings[$currency]['doubled_min_amount']?$settings[$currency]['doubled_min_amount']:0;
			$doubled_max_amount = isset($settings[$currency]['doubled_max_amount']) && $settings[$currency]['doubled_max_amount']?$settings[$currency]['doubled_max_amount']:1000000;		
		}

		$from_adjusted = new DateTime($from);
		$from_adjusted->modify('-'.$adjust_minutes_get_last_transfer.' hours');
		$from_adjusted_str = $from_adjusted->format('Y-m-d H:i:s');
		$this->utils->debug_log('searchSuspiciousTransferFromSubwalletRequest', $from, $to, $from_adjusted_str);

		$this->load->model(['wallet_model', 'external_system', 'transactions', 'player_model']);
		$this->utils->loadAnyGameApiObject();
		if(empty($db)){
			$db = $this->db;
		}

		//Get all withdrawal transfer request
$w_sql=<<<EOD
select *
from transfer_request
where created_at >= ? and created_at <= ?
and (
  (to_wallet_type_id=? and status=? and transfer_status = ?)
 )
and fix_flag = ? order by created_at desc;
EOD;

		//withdrawal is approved status success
		$qry=$db->query($w_sql, [$from, $to,
			self::MAIN_WALLET_ID,
			self::STATUS_TRANSFER_SUCCESS,
			Abstract_game_api::COMMON_TRANSACTION_STATUS_APPROVED,
			self::DB_FALSE]);

		//$this->utils->debug_log('action_of_search_suspicious_transfer_request', $this->db->last_query());

		$cnt = 0;

		$result = [];

		//set $withdraw , if withdraw is null, just quit
		while (!empty($qry) && $withdraw=$qry->nextRowArray()) {

			//$this->utils->debug_log('action_of_search_suspicious_transfer_request $withdraw: ', $withdraw);
			$withdraw['doubled'] = false;
			$withdraw['multiplied'] = false;
			$withdraw['huge_amount'] = false;
			$withdraw['last_transfer'] = null;

			$isSuspicious = false;
			$current_player_id = $withdraw['player_id'];
			if(!$current_player_id){
				continue;
			}

			//get last transfer withdraw|deposit
			$fromLastRequest = new DateTime($withdraw['created_at']);
			$fromLastRequest->modify('-'.$adjust_minutes_get_last_transfer.' minutes');
			$last_transfer = $this->getPlayerLastTransfer($current_player_id, $fromLastRequest->format('Y-m-d H:i:s'), $withdraw['created_at'], $withdraw['id']);

			$withdraw['last_transfer'] = $last_transfer;

			//check if last transfer is same game whether in or out wallet
			if($last_transfer &&
			($last_transfer['from_wallet_type_id'] == $withdraw['from_wallet_type_id'] ||
			$last_transfer['to_wallet_type_id'] == $withdraw['from_wallet_type_id']) &&
			$last_transfer['id']<>$withdraw['id']){

				//check if deposit and doubled
				$checkAmount = floatval($last_transfer['amount']) * 2;

				if($last_transfer['to_wallet_type_id']==$withdraw['from_wallet_type_id'] &&
				floatval($withdraw['amount']) == $checkAmount &&
				floatval($withdraw['amount']) >= $doubled_min_amount){
					$isSuspicious = true;
					$withdraw['doubled'] = true;
				}

				$checkAmount = floatval($last_transfer['amount']) * floatval($multiplier);

				if($last_transfer['to_wallet_type_id']==$withdraw['from_wallet_type_id'] &&
				floatval($withdraw['amount']) >= $checkAmount &&
				floatval($withdraw['amount']) < $max_amount &&
				floatval($withdraw['amount']) >= $min_amount){
					$isSuspicious = true;
					$withdraw['multiplied'] = true;
				}
			}

			if(floatval($withdraw['amount']) >= $max_amount){
				$isSuspicious = true;
				$withdraw['huge_amount'] = true;
			}

			if($isSuspicious){
				array_push($result, $withdraw);
			}
		}

		$qry->free_result();

		return $result;
	}

	public function getPlayerLastTransfer($playerId, $from, $to, $withdrawId){
$sql=<<<EOD
select *
from transfer_request
where player_id = ? and created_at >= ? and created_at <= ? and status=? and transfer_status = ? and fix_flag= ? and id <> ?
order by created_at desc LIMIT 1;
EOD;

		$params = [$playerId, $from, $to,
		self::STATUS_TRANSFER_SUCCESS, Abstract_game_api::COMMON_TRANSACTION_STATUS_APPROVED,
		self::DB_FALSE, $withdrawId];
		$qry = $this->db->query($sql, $params);
		return $this->getOneRowArray($qry);
	}

	public function checkDuplicateExternalTransactionId($external_trans_id, $game_platform_id){
		$exists=false;
		//try insert , if throw exception means exists or other error, can't go on
		$data=['external_trans_id_from_gamegatewayapi'=>$external_trans_id,
			'created_at'=>$this->utils->getNowForMysql(), 'game_platform_id'=>$game_platform_id];
		try{
			$id=$this->insertData('transfer_fund_external_info', $data);
		}catch(Exception $e){
			$this->utils->error_log('exist external transaction id', $e);
			$exists=true;
		}
		$this->utils->printLastSQL();
        $this->utils->debug_log('check duplicate external transaction id', $external_trans_id);

		return $exists;
	}

	public function getTimeOutTransferRequestByCostMs($from, $to, $db, $is_manual){

		$this->utils->loadAnyGameApiObject();
		$codes=implode(',',Abstract_game_api::DEFAULT_GUESS_SUCCESS_CODE);
		if(empty($db)){
	        $db = $this->db;
		}
		$cnt = 0;

		$default_transfer_request_limit_count = 60;
		$config_limit_count=$this->utils->getConfig('transfer_request_limit_count');
		if($config_limit_count){
			$default_transfer_request_limit_count = $config_limit_count;
		}

		$db_name=$db->database;
        $disabled_response_results_table_only=$this->utils->getConfig('disabled_response_results_table_only');
        $enabled_new_resp_table_on_report=$disabled_response_results_table_only || $this->utils->getConfig('enabled_new_resp_table_on_report');
        $config_ms=$this->utils->getConfig('default_timeout_transfer_request_time_on_millisecond');
        if($enabled_new_resp_table_on_report){
			$dt=new DateTime($from);
			$searchDay=$dt->format('Ymd');
        	$respTableName=$this->utils->getRespTableFullName($searchDay).' as rr';
	        $errCodeField='rr.error_code';
        	$sysIdField='rr.external_system_id';

        	$sql=<<<EOD
select
{$sysIdField} as system_type_id,
count(tr.id) as count

from transfer_request as tr
JOIN {$respTableName} on rr.id = tr.response_result_id
where
tr.created_at >= ? and tr.created_at <= ? and {$errCodeField} in ($codes)
and tr.fix_flag=? and rr.cost_ms > {$config_ms}
group by {$sysIdField}
EOD;
			$this->utils->debug_log('getTimeOutTransferRequestByCostMs sql', $sql, $from, $to);

			$rows =$this->runRawSelectSQLArray($sql, [$from, $to, self::DB_FALSE], $db);

			if(!empty($rows)){
				$wallet_map = $this->utils->getGameSystemMap();

				$user = "# GW001 Timeout Request Reached {$config_ms}ms";
				$config = $this->utils->getConfig('export_timeout_request_cron_settings');

				$export_link = null;
				$body = "**Date Time**:{$from} to {$to}  ";
				$body .= "**Database**:{$db_name}  ";

				if($config){
			        $user .= " ({$config['host']})";
				}

				foreach ($rows as $row) {
					$platform = isset($wallet_map[$row['system_type_id']]) ? $wallet_map[$row['system_type_id']] : "Unkown";
					$body .= "| **{$platform}**: **{$row['count']}**, ";
					$cnt += $row['count'];
				}

				$message = [
					null,
					$body,
					null
				];

				$channel = $this->utils->getConfig('get_timeout_transfer_request_notification_channel');
		        $this->load->helper('mattermost_notification_helper');

		        if($cnt >= $default_transfer_request_limit_count || $is_manual){
		        	sendNotificationToMattermost($user, $channel, [], $message);

		        }
			}
        }		
        return $cnt;
	}
	public function getRemoteWalletTransferRequestById($id, $date){
		$table = $this->utils->getRemoteWalletBalanceHistoryTable($date);
		if(!empty($id)){
			$this->db->from($table)->where('id', $id);
			$row= $this->runOneRowArray();
			return $row;
		}
		return null;
	}

	public function updateRemoteWalletTransferQueryStatus($id, $transfer_status, $status, $table, $fix_flag = null, $reason = null){
		if(!empty($id)){
			$data=['transfer_status'=>$transfer_status, 'status' => $status, 'updated_at'=>$this->utils->getNowForMysql()];
			if($fix_flag){
				$data['fix_flag'] = $fix_flag;
			}
			if($reason){
				$data['reason'] = $reason;
			}
			$this->db->set($data)->where('id', $id);

			return $this->runAnyUpdate($table);
		}

		return null;
	}
	public function generate_timeout_transfer_request_notification_non_mdb($rows, $from, $to, $db_name){
		$wallet_map = $this->utils->getGameSystemMap();
		$settings = $this->utils->getConfig('suspicious_withdrawal_settings');
		$total_count = 0;

		$user = "# Transfer Timeout Request";

		$export_link = null;
		$body = "**Date Time**:{$from} to {$to}  ";
		$body .= "**Database**:{$db_name}  ";

		$body .= PHP_EOL ;
		$body .= PHP_EOL ;
		$body .= "| System Type ID  | Count | ID's | Player |
		| :------------ |:---------------:| :-----:| :-----:";
		$body .= PHP_EOL ;
		if(!empty($rows)){
			foreach ($rows as $row) {
				$total_count += $row['count'];
				$playerlink = $settings['base_url'].'/player_management/userInformation/'.$row['player_id'];
				$platform = isset($wallet_map[$row['system_type_id']]) ? $wallet_map[$row['system_type_id']] : "Unkown";
				$body .= "| ".$row['system_type_id'].' - '.$platform." | ".$row['count']." | ".$row['secure_ids']." | [".$row['player_id']."](".$playerlink.") |".PHP_EOL;
			}
		}
		$body .= PHP_EOL ;
		$body .= "**TOTAL: ".$total_count."**";
		/*$message = [
			null,
			$body,
			""
		];*/

        $channel = $this->utils->getConfig('get_timeout_transfer_request_notification_channel');
        $this->CI->load->helper('mattermost_notification_helper');
		$this->utils->debug_log('timeout_transfer_request data', $total_count, $rows);
		$this->sendNotificationToMattermost($user, $channel, $body, 'warning');

	}

    /**
     * sendNotificationToMattermost
     * @param  string $user
     * @param  string $channel
     * @param  string $message
     * @param  string $notifType
     * @param  string $texts_and_tags
     * @return
     */
    public function sendNotificationToMattermost($user,$channel,$message,$notifType,$texts_and_tags=null){
    	$this->load->helper('mattermost_notification_helper');
    	if($this->utils->getConfig('test_mode_for_mattermost_message')){
    		$message='**(TEST ONLY)** '.$message;
    	}
    	$notif_message = array(
    		array(
    			'text' => $message,
    			'type' => $notifType
    		)
    	);
    	return sendNotificationToMattermost($user, $channel, $notif_message, $texts_and_tags);
    }
}

