<?php
trait iovation_module {	

    public function iovation_report($request, $is_export = false) {

		$readOnlyDB = $this->getReadOnlyDB();

		$this->load->library('data_tables', array("DB" => $readOnlyDB));
		// $this->load->library([ 'player_manager' ]);
        $this->load->library(['iovation/iovation_lib']);
		$this->load->model(array('transactions', 'player_model', 'iovation_logs'));

		$this->data_tables->is_export = $is_export;

		$input = $this->data_tables->extra_search($request);
		// $this->benchmark->mark('pre_processing_start');
		$where = array();
		$values = array();
		$having = array();
		$group_by = [];


		// if (isset($input['group_by']) && !empty($input['group_by'])) {
		$group_by = array('iovation_logs.id');
		$this->utils->debug_log('group_by sql --->', $group_by);

		# START DEFINE COLUMNS #################################################################################################################################################
		// $i = 0;
		// $regdate_col = 0;
		// $username_col = 1;
		// $result_col = 2;
		// $ip_col = 3;
		// $contact_col = 4;
		// $fullname_col = 5;
		// $updatedat_col = 6;
		// $action_col = 7;

		$col = 0;
		$na = $is_export ? lang('lang.norecyet') : '<i class="text-muted">' . lang('lang.norecyet') . '</i>';

		$columns = array(
			array(
				'dt' => null,
				'alias' => 'firstname',
				'select' => 'playerdetails.firstName',
				'name' => lang('Firstname'),
			),
			array(
				'dt' => null,
				'alias' => 'lastname',
				'select' => 'playerdetails.lastName',
				'name' => lang('Lastname'),
			),
			array(
				'dt' => null,
				'alias' => 'player_username',
				'select' => 'player.username',				
			),
			array(
				'dt' => null,
				'alias' => 'aff_username',
				'select' => 'affiliates.username',				
			),
			array(
				'dt' => null,
				'alias' => 'aff_firstname',
				'select' => 'affiliates.firstname',				
			),
			array(
				'dt' => null,
				'alias' => 'aff_lastname',
				'select' => 'affiliates.lastname'				
			),
			// 0 - regdate
			array(
				'dt' => $col++,
				'alias' => 'created_at',
				'select' => 'iovation_logs.created_at',
				// 'formatter' => 'dateFormatter',
				'name' => lang("report.regdate"),
			),
			array(
				'dt' => $col++,
				'alias' => 'account_code',
				'select' => 'iovation_logs.account_code',
				'name' => lang('Account Code'),
			),
			array(
				'dt' => $col++,
				'alias' => 'username',
				'select' => 'iovation_logs.account_code',
				'name' => lang('Username'),
				'formatter' => function ($d, $row) use ($is_export) {					

					if($row['user_type']=='affiliate'){
						return $row['aff_username'];
					}

					return $row['player_username'];
				}
			),
			array(
				'dt' => $col++,
				'alias' => 'user_type',
				'select' => 'iovation_logs.user_type',
				'name' => lang('User Type'),
			),
			array(
				'dt' => $col++,
				'alias' => 'details',
				'select' => 'iovation_logs.details',
				'name' => lang("Device ID"),
				'formatter' => function ($d, $row) use ($is_export) {
					//process json

					$parsed = json_decode($d, true);					
					$str = '';		
					if(isset($parsed['details']['device']['alias'])){
						if($is_export){
							$str .= 'device: ';
						}
						$str .= $parsed['details']['device']['alias'];
					}

					return $str;
				}
			),
			array(
				'dt' => $col++,
				'alias' => 'status',
				'select' => 'iovation_logs.status',
				'name' => lang("API Response"),
				'formatter' => function ($d, $row) use ($is_export) {
					if($d==0){
						return lang("report.iovationsuccess");
					}else{
						return lang("report.iovationfailed");
					}
				}
			),
			array(
				'dt' => $col++,
				'alias' => 'result',
				'select' => 'iovation_logs.result',
				'name' => lang("Player Verified Status"),
				'formatter' => function ($d, $row) use ($is_export) {
					$str='';
					switch ($d) {
					case 'D':
						$str=lang("report.iovationdeny");
						break;
					case 'R':
						$str=lang("report.iovationreview");
						break;
					case 'A':
						$str=lang("report.iovationallow");
						break;
					default:
						$str='N/A';
					}

					if(!$row['status']==0){
						$str='N/A';
					}

					return $str;
				}
			),
			array(
				'dt' => $col++,
				'alias' => 'ip_address',
				'select' => 'iovation_logs.stated_ip',
				'name' => lang('Registered IP'),
			),
			array(
				'dt' => $col++,
				'alias' => 'contact_number',
				'select' => 'playerdetails.contactNumber',
				'name' => lang('Contact Number'),
			),
			array(
				'dt' => $col++,
				'alias' => 'fullname',
				'select' => 'CONCAT(playerdetails.firstName, " ", playerdetails.lastName)',
				'name' => lang('report.iovationfullname'),
				'formatter' => function ($d, $row) use ($is_export) {					

					if($row['user_type']=='affiliate'){
						return $row['aff_firstname'] . ' ' . $row['aff_lastname'];
					}

					return $row['firstname'] . ' ' . $row['lastname'];
				}
			),
			array(
				'dt' => $col++,
				'alias' => 'type',
				'select' => 'iovation_logs.type',
				'name' => lang('Type'),				
			),
			array(
				'dt' => $col++,
				'alias' => 'updated_at',
				'select' => 'iovation_logs.updated_at',
				// 'formatter' => 'dateFormatter',
				'name' => lang('Last Update Time'),
			),
		);

		if(!$is_export){

			$columns[] = array(
				'dt' => $col++,
				'select' => 'iovation_logs.id',
				'alias' => 'iovation_logs_is',
				'name' => lang('lang.action'),
				'formatter' => function ($d, $row) use ($is_export) {
					if(!$is_export && !$row['status']==0){
						$str='';
						$str.=" <input type='button' class='btn btn-success btn-xs m-b-5' onclick='resendIovation(" . $d . ")' value='".lang('Resend')."'>";

						return $str;
						//$resend = '<a href="javascript:void(0)" class="iovation_report_action" iovation_report_action="resend" iovation_report_id="'.$d.'" data-toggle="tooltip" title="'.lang("Resend").'" class="resend-row" id="resend_row"><span class="glyphicon glyphicon-ok-sign"></span></a>&nbsp;';

						//return $resend;
					}else{
						return '';
					}

				}
			);
		}

		$this->utils->debug_log(__METHOD__, 'columns', $columns);

        if ($this->iovation_lib->use_logs_monthly_table) {
            $table_name = $this->iovation_logs->getCurrentYearMonthTable();

            if (isset($input['by_date_from'])) {
                $table_name = $this->iovation_logs->getYearMonthTableByDate(null, $input['by_date_from']);
            }
        } else {
            $table_name = $this->iovation_logs->tableName;
        }

		$table = "{$table_name} as iovation_logs";
		$joins = array(
			'player' => "player.playerId = iovation_logs.player_id AND (iovation_logs.user_type='player' OR iovation_logs.user_type is null)",
			'affiliates' => "affiliates.affiliateId = iovation_logs.player_id AND iovation_logs.user_type='affiliate'",
			'playerdetails' => "playerdetails.playerId = player.playerId",
		);

		if (isset($input['by_result'])) {
			$where[] = "iovation_logs.result = ?";
			$values[] = $input['by_result'];
		} else {
			$where[] = "iovation_logs.result in (?,?,?,?)";
			$values[] = '';
			$values[] = Iovation_logs::ALLOW;
			$values[] = Iovation_logs::DENY;
			$values[] = Iovation_logs::REVIEW;
		}

		if (isset($input['by_status'])) {
			$where[] = "iovation_logs.status = ?";
			$values[] = $input['by_status'];
		} else {
			$where[] = "iovation_logs.status in (?,?,?)";
			$values[] = '';
			$values[] = Iovation_logs::SUCCESS;
			$values[] = Iovation_logs::FAILED;
		}

		if (isset($input['by_date_from'], $input['by_date_to'])) {
			$where[] = "DATE(iovation_logs.created_at) >=?";
			$where[] = "DATE(iovation_logs.created_at) <=?";
			$values[] = $input['by_date_from'];
			$values[] = $input['by_date_to'];
		}

		if (isset($input['by_user_type'])) {
			$where[] = "iovation_logs.user_type = ?";
			$values[] = $input['by_user_type'];
		}

		if (isset($input['by_type'])) {
			$where[] = "iovation_logs.type = ?";
			$values[] = $input['by_type'];
		}

		if (isset($input['by_username'])) {
			$where[] = "(player.username LIKE ? OR iovation_logs.account_code LIKE ? OR affiliates.username LIKE ?)";
			$values[] = '%' . $input['by_username'] . '%';
			$values[] = '%' . $input['by_username'] . '%';
			$values[] = '%' . $input['by_username'] . '%';
		}
		
		if (isset($input['by_device_id'])) {
			$where[] = "iovation_logs.details LIKE ?";
			$values[] = '%' . $input['by_device_id'] . '%';
		}
		//$this->utils->error_log($where);
		//$this->utils->error_log($values);
		// Default clause
		//$where[] = "player.deleted_at IS NULL";

		# END PROCESS SEARCH FORM #################################################################################################################################################
		if($is_export){
            $this->data_tables->options['is_export']=true;
			// $this->data_tables->options['only_sql']=true;
            if(empty($csv_filename)){
                $csv_filename=$this->utils->create_csv_filename(__FUNCTION__);
            }
            $this->data_tables->options['csv_filename']=$csv_filename;
		}
		$mark = 'data_sql';
		$this->utils->markProfilerStart($mark);
		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins, $group_by, $having);

		if($is_export){
			return $csv_filename;
		}
		$this->utils->markProfilerEndAndPrint($mark);

		//$summary = $this->data_tables->summary($request, $table, $joins, '', null, $columns, $where, $values);

		return $result;
	}

    public function iovation_evidence($request, $is_export = false) {

		$readOnlyDB = $this->getReadOnlyDB();

		$this->load->library('data_tables', array("DB" => $readOnlyDB));
		// $this->load->library([ 'player_manager' ]);
		$this->load->model(array('transactions', 'player_model', 'iovation_evidence'));

		$this->data_tables->is_export = $is_export;

		$input = $this->data_tables->extra_search($request);
		// $this->benchmark->mark('pre_processing_start');
		$where = array();
		$values = array();
		$having = array();
		$group_by = [];


		// if (isset($input['group_by']) && !empty($input['group_by'])) {
		$group_by = array('iovation_evidence.id');
		$this->utils->debug_log('group_by sql --->', $group_by);

		# START DEFINE COLUMNS #################################################################################################################################################
		// $i = 0;
		// $created_col = 0;
		// $username_col = 1;
		// $fullname_col = 2;
		// $evidencetype_col = 3;
		// $appliedto_col = 4;
		// $comment_col = 5;
		// $status_col = 6;
		// $updatedat_col = 7;
		// $action_col = 8;
		$this->load->library(array('iovation/iovation_lib'));

		$setCheckboxChecked = false;
		if (isset($input['triggered_by']) && !empty($input['triggered_by']) && $input['triggered_by']=='batch_remove_tags') {
			$setCheckboxChecked = true;
		}
		

		$col = 0;
		$na = $is_export ? lang('lang.norecyet') : '<i class="text-muted">' . lang('lang.norecyet') . '</i>';

		$columns = array(
            array(
				'dt' => $col++,
				'alias' => 'checkbox',
				'select' => 'iovation_evidence.id',
				'formatter' => function ($d, $row) use ($is_export, $setCheckboxChecked) {
					$str = '';
					if($setCheckboxChecked){
						$str = 'checked';
					}
					if ($is_export) {
						return '';
					} else {
                        return "
                            <input type='checkbox' class='checkWhite' id='evidenceId_{$d}' name='evidenceIds[]' value='{$d}' onclick='uncheckAll(this.id)' {$str}>
                        ";
					}
				},
				'name' => '',
			),
			array(
				'dt' => null,
				'alias' => 'firstname',
				'select' => 'playerdetails.firstName',
				'name' => lang('Firstname'),
			),
			array(
				'dt' => null,
				'alias' => 'lastname',
				'select' => 'playerdetails.lastName',
				'name' => lang('Lastname'),
			),
			array(
				'dt' => null,
				'alias' => 'player_username',
				'select' => 'player.username',				
			),
			array(
				'dt' => null,
				'alias' => 'aff_username',
				'select' => 'affiliates.username',				
			),
			array(
				'dt' => null,
				'alias' => 'player_id',
				'select' => 'iovation_evidence.player_id',				
			),
			array(
				'dt' => null,
				'alias' => 'aff_firstname',
				'select' => 'affiliates.firstname',				
			),
			array(
				'dt' => null,
				'alias' => 'aff_lastname',
				'select' => 'affiliates.lastname'				
			),
			// 0 - regdate
			array(
				'dt' => $col++,
				'alias' => 'created_at',
				'select' => 'iovation_evidence.created_at',
				// 'formatter' => 'dateFormatter',
				'name' => lang("report.regdate"),
			),
			array(
				'dt' => $col++,
				'alias' => 'username',
				'select' => 'iovation_evidence.account_code',
				'name' => lang('Username'),
				'formatter' => function ($d, $row) use ($is_export) {	

					if(!$is_export){
						if($row['user_type']=='affiliate'){
							return $row['aff_username'];							
						}
						return $row['player_username'];
					}else{
						if($row['user_type']=='affiliate'){							
							return '<i class="fa fa-user" ></i> ' . ($d ? '<a href="/affiliate_management/userInformation/' . $row['affiliate_id'] . '" target="_blank">' . $row['aff_username'] . '</a>' : '<i class="text-muted">' . lang('lang.norecyet') . '</i>');
						}
						return '<i class="fa fa-user" ></i> ' . ($d ? '<a href="/player_management/userInformation/' . $row['player_id'] . '" target="_blank">' . $row['player_username'] . '</a>' : '<i class="text-muted">' . lang('lang.norecyet') . '</i>');
					}
					
				}				
			),
			array(
				'dt' => $col++,
				'alias' => 'user_type',
				'select' => 'iovation_evidence.user_type',
				'name' => lang('User Type')
			),
			array(
				'dt' => $col++,
				'alias' => 'fullname',
				'select' => 'CONCAT(playerdetails.firstName, " ", playerdetails.lastName)',
				'name' => lang('report.iovationfullname'),
				'formatter' => function ($d, $row) use ($is_export) {
					if($row['user_type']=='affiliate'){
						return $row['aff_firstname'] . ' ' . $row['aff_lastname'];
					}

					return $row['firstname'] . ' ' . $row['lastname'];
				}
			),
			array(
				'dt' => $col++,
				'alias' => 'evidence_type',
				'select' => 'iovation_evidence.evidence_type',
				'name' => lang("Evidence Type"),
				'formatter' => function ($d, $row) use ($is_export) {	
					$type = @Iovation_lib::EVIDENCE_TYPES[$d];
					return lang($type);
				}
			),
			array(
				'dt' => $col++,
				'alias' => 'applied_to_type',
				'select' => 'iovation_evidence.applied_to_type',
				'name' => lang("Type"),
				'formatter' => function ($d, $row) use ($is_export) {						
					return $d;
				}
			),
			array(
				'dt' => $col++,
				'alias' => 'account_code',
				'select' => 'iovation_evidence.account_code',
				'name' => lang('Account Code'),				
			),
			array(
				'dt' => $col++,
				'alias' => 'device_alias',
				'select' => 'iovation_evidence.device_alias',
				'name' => lang('Device ID'),
				'formatter' => function ($d, $row) use ($is_export) {	
					return $d;
				}
			),
			array(
				'dt' => $col++,
				'alias' => 'applied_to',
				'select' => 'iovation_evidence.applied_to',
				'name' => lang("Applied To"),
				'formatter' => function ($d, $row) use ($is_export) {
					if($is_export){
						return $d;
					}

					$parsed = json_decode($d, true);					
					$str = '';					
					if(isset($parsed['accountCode'])){
						$str .= 'Account: '.$parsed['accountCode'];
					}
					if(isset($parsed['deviceAlias'])){
						$str .= 'Device: '.$parsed['deviceAlias'];
					}
					return $str;
				}
			),
			array(
				'dt' => $col++,
				'alias' => 'comment',
				'select' => 'iovation_evidence.comment',
				'name' => lang("Comment"),
				'formatter' => function ($d, $row) use ($is_export) {					
					return $d;
				}
			),
			array(
				'dt' => $col++,
				'alias' => 'status',
				'select' => 'iovation_evidence.status',
				'name' => lang("API Response"),
				'formatter' => function ($d, $row) use ($is_export) {
					if($d==0){
						return lang("report.iovationsuccess");
					}else{
						return lang("report.iovationfailed");
					}
				}
			),
			array(
				'dt' => $col++,
				'alias' => 'evidence_status',
				'select' => 'iovation_evidence.evidence_status',
				'name' => lang("Action"),
				'formatter' => function ($d, $row) use ($is_export) {
					if($d==0){
						return lang("Added");
					}elseif($d==1){
						return lang("Change/Updated");
					}elseif($d==2){
						return lang("Retracted");
					}else{
						return '';
					}
				}
			),
			array(
				'dt' => $col++,
				'alias' => 'updated_at',
				'select' => 'iovation_evidence.updated_at',
				// 'formatter' => 'dateFormatter',
				'name' => lang('Last Update Time'),
			),
		);

		if(!$is_export){

			$columns[] = array(
				'dt' => $col++,
				'select' => 'iovation_evidence.id',
				'alias' => 'iovation_evidence_id',
				'name' => lang('lang.action'),
				'formatter' => function ($d, $row) use ($is_export) {
					if($is_export){
						return '';
					}else{
						$str='';
						if($row['status']==0 && $row['evidence_status']<>2){
							$str.=" <input type='button' class='btn btn-success btn-xs m-b-5' onclick='showFormModal(" . $d . ")' value='".lang('Edit')."'>";
							$str.=" <input type='button' class='btn btn-danger btn-xs m-b-5' onclick='retractEvidence(" . $d . ")' value='".lang('iovation_evidence.retract')."'>";
						}
						return $str;						
					}

				}
			);
		}

		$this->utils->debug_log(__METHOD__, 'columns', $columns);

		$table = 'iovation_evidence';
		$joins = array(
			'player' => "player.playerId = iovation_evidence.player_id AND (iovation_evidence.user_type='player' OR iovation_evidence.user_type is null)",		
			'affiliates' => "affiliates.affiliateId = iovation_evidence.affiliate_id AND iovation_evidence.user_type='affiliate'",
			'playerdetails' => "playerdetails.playerId = player.playerId",
		);
		

		if (isset($input['by_status']) && !empty($input['by_status'])) {
			$where[] = "iovation_evidence.status = ?";
			$values[] = $input['by_status'];
		} else {
			$where[] = "iovation_evidence.status in (?,?,?)";
			$values[] = '';
			$values[] = iovation_evidence::SUCCESS;
			$values[] = iovation_evidence::FAILED;
		}

		if (isset($input['by_date_from']) && !empty($input['by_date_from'])) {
			$where[] = "DATE(iovation_evidence.created_at) >=?";			
			$values[] = $input['by_date_from'];
		}

		if (isset($input['by_date_to']) && !empty($input['by_date_to'])) {			
			$where[] = "DATE(iovation_evidence.created_at) <=?";			
			$values[] = $input['by_date_to'];
		}

		if (isset($input['by_username']) && !empty($input['by_username'])) {
			$where[] = "(player.username LIKE ? OR iovation_evidence.account_code LIKE ? OR affiliates.username LIKE ?)";
			$values[] = '%' . $input['by_username'] . '%';
			$values[] = '%' . $input['by_username'] . '%';
			$values[] = '%' . $input['by_username'] . '%';
		}

		if (isset($input['by_user_type']) && !empty($input['by_user_type'])) {
			$where[] = "iovation_evidence.user_type = ?";
			$values[] = $input['by_user_type'];
		}

		if (isset($input['by_device_id']) && !empty($input['by_device_id'])) {
			$where[] = "iovation_evidence.device_alias LIKE ?";
			$values[] = '%' . $input['by_device_id'] . '%';
		}

		if (isset($input['by_account_code']) && !empty($input['by_account_code'])) {
			$where[] = "iovation_evidence.account_code LIKE ?";
			$values[] = '%' . $input['by_account_code'] . '%';
		}

		if (isset($input['by_evidence_type']) && !empty($input['by_evidence_type'])) {
			$where[] = "iovation_evidence.evidence_type = ?";
			$values[] = $input['by_evidence_type'];
		}

		// Default clause
		//$where[] = "player.deleted_at IS NULL";

		# END PROCESS SEARCH FORM #################################################################################################################################################
		if($is_export){
            $this->data_tables->options['is_export']=true;
			// $this->data_tables->options['only_sql']=true;
            if(empty($csv_filename)){
                $csv_filename=$this->utils->create_csv_filename(__FUNCTION__);
            }
            $this->data_tables->options['csv_filename']=$csv_filename;
		}
		$mark = 'data_sql';
		$this->utils->markProfilerStart($mark);
		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins, $group_by, $having);

		if($is_export){
			return $csv_filename;
		}
		$this->utils->markProfilerEndAndPrint($mark);

		//$summary = $this->data_tables->summary($request, $table, $joins, '', null, $columns, $where, $values);

		return $result;
	}

}