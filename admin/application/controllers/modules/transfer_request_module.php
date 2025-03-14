<?php
trait transfer_request_module {

	/**
	 *
	 * detail: Display lists for transfer request
	 *
	 * @return load template
	 */
	public function transfer_request($from = 'payment') {
		if ($this->permissions->checkPermissions(['transfer_request', 'report_transfer_request'])) {
			$this->load->model(['wallet_model', 'external_system']);

			$this->utils->loadAnyGameApiObject();

			$data=['game_platforms' => $this->external_system->getAllActiveSytemGameApi()];

			$data['conditions']=$this->safeLoadParams([
				'timezone'=>8,
				'status'=>'', //Wallet_model::STATUS_TRANSFER_SUCCESS,
				'by_game_platform_id'=>'',
				'secure_id'=>'',
				'search_reg_date'=>'on',
				'result_id'=>'',
				'suspicious_trans'=>'',
				'query_status'=>'',
			]);

			if(!empty($data['conditions']['secure_id'])){
				$data['conditions']['search_reg_date']=false;
			}else{
				if($this->input->get_post('search_reg_date')===FALSE){
					//set default value
					$data['conditions']['search_reg_date']='on';
				}
			}

			$data['enable_freeze_top_in_list'] = $this->utils->_getEnableFreezeTopWithMethod(__METHOD__, $this->config->item('enable_freeze_top_method_list'));

			if ($from == 'report') {
				$activenav = 'report';
			} else {
				$activenav = 'payment';
			}

			$this->loadTemplate(lang('Transfer Request'), '', '', $activenav);
			$this->template->add_js('resources/js/payment_management/resetFromJson.js');

			if ($from == 'report') {
				$this->template->write_view('sidebar', 'report_management/sidebar', ['active' => 'transfer_request']);
			} else {
				$this->template->write_view('sidebar', 'payment_management/sidebar', ['active' => 'transfer_request']);
			}

			$this->template->write_view('main_content', 'payment_management/transfer_request', $data);
			$this->template->render();
		} else {
			$this->error_access($from);
		}
	}

	public function query_transfer_status($id){

		if (!$this->permissions->checkAnyPermissions(['transfer_request', 'report_transfer_request']) || empty($id)) {
			return $this->returnErrorStatus('403');
		}

		$this->load->model(['wallet_model', 'player_model']);
		//load transfer request record
		$transferRequest=$this->wallet_model->getTransferRequestById($id);
		if(empty($transferRequest)){
			return $this->returnErrorStatus();
		}
		//validate data

		list($success, $status, $error_message, $status_message)=$this->utils->queryAndUpdateTransferRequestStatus($transferRequest);

		$this->returnJsonResult(['success'=>$success, 'error_message'=>$error_message, 'status'=>$status, 'status_message'=>$status_message]);
	}

	public function auto_fix_transfer($id){

		if (!$this->permissions->checkAnyPermissions(['make_up_transfer_record']) || empty($id)) {
			return $this->returnErrorStatus('403');
		}

		$this->load->model(['wallet_model', 'external_system', 'transactions']);
		//load transfer request record
		$transferRequest=$this->wallet_model->getTransferRequestById($id);
		if(empty($transferRequest)){
			return $this->returnErrorStatus();
		}
		//validate data
		// $external_system_id=$transferRequest['external_system_id'];

		// $success=true;
		// $error_message=null;
		// $status=null;
		// $message=null;

		list($success, $status, $error_message, $message)=$this->utils->fixTransferRequest($transferRequest);

		$this->returnJsonResult(['success'=>$success, 'error_message'=>$error_message, 'status'=>$status, 'message'=>$message]);

	}

	/**
	 * updateUnknownStatusForTransferRequest
	 * @param  string $from
	 * @param  string $to
	 * @param  object $db
	 * @return int count
	 */
	public function updateUnknownStatusForTransferRequest($from, $to, $db=null){
		$this->load->model(['wallet_model', 'external_system', 'transactions', 'player_model']);

		$this->utils->loadAnyGameApiObject();
		if(empty($db)){
			$db = $this->db;
		}
		$transferRequestTable=$this->utils->getTransferRequestTable($from);

		$sql=<<<EOD
select *
from $transferRequestTable as transfer_request
where created_at >= ? and created_at <= ?
and transfer_status=?
and external_system_id!=?
EOD;
		// $this->db->from('game_logs');

		//deposit and success and transfer status is unknown or declined
		//withdrawal and failed and status is failed and transfer status is unknown or approved
		$qry=$db->query($sql, [$from, $to,
			Abstract_game_api::COMMON_TRANSACTION_STATUS_UNKNOWN, DUMMY_GAME_API]);
		$this->utils->printLastSQL();
		$cnt=0;
		$cntSucc=0;

		//set $row , if row is null, just quit
		while (!empty($qry) && $row=$qry->nextRowArray()) {
			$m1=memory_get_usage();
			//process
			$this->utils->debug_log($row['id'].' '.$row['amount'].', '.round(memory_get_usage()/(1024*1024), 2));

			//try query status first
			list($success, $status, $error_message, $message)=$this->utils->queryAndUpdateTransferRequestStatus($row);
			if($success){
				$this->appendNoteToTransferRequest($row['id'], 'query api status:'.$status.', status:'.$row['status']);
				$cntSucc++;
			}
			$cnt++;
			$this->utils->debug_log('queryAndUpdateTransferRequestStatus:'.$row['id'],$success, $status, $error_message, $message);

			$m2=memory_get_usage();
			unset($row);
			$m3=memory_get_usage();
			$this->utils->debug_log($m1.' > '.$m2.' > '.$m3.', '.($m2-$m1).', '.($m3-$m2));
		}

		$qry->free_result();
		$this->utils->debug_log('count of all', $cnt, 'success', $cntSucc);
		return $cnt;
	}

	/**
	 * batchAutoFixLostBalance
	 * @param  string $from
	 * @param  string $to
	 * @param  object $db
	 * @return int
	 */
	public function batchAutoFixLostBalance($from, $to, $db=null){
		$this->load->model(['wallet_model', 'external_system', 'transactions', 'player_model']);

		$this->utils->loadAnyGameApiObject();
		if(empty($db)){
			$db = $this->db;
		}
		$transferRequestTable=$this->utils->getTransferRequestTable($from);

		// $secondReadDB->from('transfer_request')->where('created_at >=', $from)
		// 	->where('created_at <=', $to)->where();

		$sql=<<<EOD
select *
from $transferRequestTable as transfer_request
where created_at >= ? and created_at <= ?
and (
  (from_wallet_type_id=? and status=? and transfer_status=?) or
  (to_wallet_type_id=? and status=? and transfer_status=?)
 )
and fix_flag= ?
EOD;
		// $this->db->from('game_logs');

		//deposit and success and transfer status is unknown or declined
		//withdrawal and failed and status is failed and transfer status is unknown or approved
		$qry=$db->query($sql, [$from, $to,
			self::MAIN_WALLET_ID, self::STATUS_TRANSFER_SUCCESS,
				Abstract_game_api::COMMON_TRANSACTION_STATUS_DECLINED,
			self::MAIN_WALLET_ID, self::STATUS_TRANSFER_FAILED,
				Abstract_game_api::COMMON_TRANSACTION_STATUS_APPROVED,
			self::DB_FALSE]);

		$cnt=0;
		$successCnt=0;
		//set $row , if row is null, just quit
		while (!empty($qry) && $row=$qry->nextRowArray()) {
			$m1=memory_get_usage();
			//process
			$this->utils->debug_log($row['id'].' '.$row['amount'].', '.round(memory_get_usage()/(1024*1024), 2));

			list($success, $status, $error_message, $message)=$this->utils->fixTransferRequest($row);
			$this->utils->debug_log('fixTransferRequest:'.$row['id'],$success, $status, $error_message, $message);
			if($success){
				$successCnt++;
			}
			$m2=memory_get_usage();
			$cnt++;
			unset($row);
			$m3=memory_get_usage();
			$this->utils->debug_log($m1.' > '.$m2.' > '.$m3.', '.($m2-$m1).', '.($m3-$m2));
		}

		$qry->free_result();

		$this->utils->debug_log('count', $cnt, 'success', $successCnt);
		return $cnt;
	}

	public function query_remote_wallet_transaction_status($id){

		if (!$this->permissions->checkAnyPermissions(['transfer_request', 'report_transfer_request']) || empty($id)) {
			return $this->returnErrorStatus('403');
		}

		$date = $this->utils->getTodayForMysql();
		if(isset($_POST["date"])) {
       $date = $_POST["date"];
    }


		$this->load->model(['wallet_model']);
		$transferRequest = $this->wallet_model->getRemoteWalletTransferRequestById($id, $date);
		$this->returnJsonResult($transferRequest);
		if(empty($transferRequest)){
			return $this->returnErrorStatus();
		}

		list($success, $status, $error_message, $status_message)=$this->utils->queryAndUpdateRemoteWalletRequestStatus($transferRequest);

		$this->returnJsonResult(['success'=>$success, 'error_message'=>$error_message, 'status'=>$status, 'status_message'=>$status_message]);
	}

	public function auto_fix_remote_wallet_transaction($id){

		if (!$this->permissions->checkAnyPermissions(['transfer_request', 'report_transfer_request']) || empty($id)) {
			return $this->returnErrorStatus('403');
		}

		$date = $this->utils->getTodayForMysql();
		if(isset($_POST["date"])) {
       $date = $_POST["date"];
    }


		$this->load->model(['wallet_model']);
		$transferRequest = $this->wallet_model->getRemoteWalletTransferRequestById($id, $date);
		$this->returnJsonResult($transferRequest);
		if(empty($transferRequest)){
			return $this->returnErrorStatus();
		}

		list($success, $status, $error_message, $status_message)=$this->utils->fixRemoteWalletRequest($transferRequest);

		$this->returnJsonResult(['success'=>$success, 'error_message'=>$error_message, 'status'=>$status, 'status_message'=>$status_message]);
	}

}
