<?php
trait seamless_balance_history_module {	    

	public function seamless_balance_history($player_id , $request, $is_export = false, $csv_filename = null) {
		$readOnlyDB = $this->getReadOnlyDB();
        $this->load->library('data_tables', array("DB" => $readOnlyDB));
		
		if(!$is_export){
			$request = $this->input->post();
		}
		$input = $this->data_tables->extra_search($request);
		
		$date=new DateTime();
		$dateStr=$date->format('Y-m-d H:i:s');
		if (isset($input['date_from'])) {
			$date=new DateTime($input['date_from']);
			$dateStr=$date->format('Y-m-d H:i:s');
		}
		$table = $this->utils->getSeamlessBalanceHistoryTable($dateStr);

		$this->data_tables->is_export = $is_export;

		$controller = $this;

		# START DEFINE COLUMNS #################################################################################################################################################
		$i = 0;
		$columns = array(
			array(				
				'alias' => 'trans_type',
				'select' => "{$table}.transaction_type",			
			),
			array(				
				'alias' => 'game_platform_id',
				'select' => "{$table}.game_platform_id",			
			),
			array(
				'dt' => $i++,
				'alias' => 'transaction_date',
				'select' => "{$table}.transaction_date",
				'formatter' => 'dateTimeFormatter',
				'name' => lang('Date')
			),
			array(
				'dt' => $i++,
				'alias' => 'balance_history_id',
				'select' => "{$table}.id",	
				'name' => lang('ID'),			
			),
			array(
				'dt' => $i++,
				'alias' => 'amount',
				'select' => "{$table}.amount",
				'name' => lang('Amount'),	
				'formatter' => function ($d, $row) use ($is_export) {
					$val = $d;
					$type = (int)$row['trans_type'];					
					switch ($type) {
						case Transactions::GAME_API_ADD_SEAMLESS_BALANCE:			
						case Transactions::DEPOSIT:			
						case Transactions::MANUAL_ADD_BALANCE:	
						case Transactions::ADD_BONUS:			
						case Transactions::AUTO_ADD_CASHBACK_TO_BALANCE:			
						case Transactions::PLAYER_REFER_BONUS:		
							
							if($is_export){
								return '+'.$this->utils->formatCurrencyWithSpecificApisDecimal($val, $row['game_platform_id']);
							}	
							if($val>0){
								return sprintf('<span style="font-weight:bold;color:#008000">+ %s</span>',$this->utils->formatCurrencyWithSpecificApisDecimal($val, $row['game_platform_id']));
							}											
							return sprintf('<span style="font-weight:bold;">+ %s</span>',$this->utils->formatCurrencyWithSpecificApisDecimal($val, $row['game_platform_id']));
							break;

						case Transactions::GAME_API_SUBTRACT_SEAMLESS_BALANCE:	
						case Transactions::WITHDRAWAL:	
						case Transactions::MANUAL_SUBTRACT_BALANCE:	
						case Transactions::SUBTRACT_BONUS:	
							if($is_export){
								return '-'.$this->utils->formatCurrencyWithSpecificApisDecimal($val, $row['game_platform_id']);
							}
							if($val>0){						
								return sprintf('<span style="font-weight:bold;color:#8B0000">- %s</span>',$this->utils->formatCurrencyWithSpecificApisDecimal($val, $row['game_platform_id']));
							}
							return sprintf('<span style="font-weight:bold;">- %s</span>',$this->utils->formatCurrencyWithSpecificApisDecimal($val, $row['game_platform_id']));
							break;

						default: 
							return $this->data_tables->currencyFormatter(0);
							break;
					}
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'before_balance',
				'select' => "{$table}.before_balance",
				'formatter' => 'currencyFormatter',
				'name' => lang('Before Balance'),
				'formatter' => function ($d, $row) use ($is_export) {
					return $this->utils->formatCurrencyWithSpecificApisDecimal($d, $row['game_platform_id']);
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'after_balance',
				'select' => "{$table}.after_balance",
				'formatter' => 'currencyFormatter',	
				'name' => lang('After Balance'),
				'formatter' => function ($d, $row) use ($is_export) {
					return $this->utils->formatCurrencyWithSpecificApisDecimal($d, $row['game_platform_id']);
				},			
			),
			array(
				'dt' => $i++,
				'alias' => 'transaction_type',
				'select' => "{$table}.transaction_type",
				'name' => lang('Transaction type'),	
				'formatter' => function ($d, $row) {
					$val = $d;
					$type = (int)$val;					
					switch ($type) {
						case Transactions::GAME_API_ADD_SEAMLESS_BALANCE:			
						case Transactions::DEPOSIT:			
						case Transactions::MANUAL_ADD_BALANCE:	
						case Transactions::ADD_BONUS:			
						case Transactions::AUTO_ADD_CASHBACK_TO_BALANCE:			
						case Transactions::PLAYER_REFER_BONUS:							
							return '(+) '.lang('Added');
							break;
						case Transactions::GAME_API_SUBTRACT_SEAMLESS_BALANCE:	
						case Transactions::WITHDRAWAL:	
						case Transactions::MANUAL_SUBTRACT_BALANCE:	
						case Transactions::SUBTRACT_BONUS:							
							return '(-) '.lang('Deducted');
							break;
						default: 
							return lang('Unknown');
							break;
					}
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'game_platform',
				'select' => 'external_system.system_code',
				'name' => lang('Game Platform'),
                'formatter' => function ($d, $row) {
					$d_parsed = json_decode($d, true);
					$val = $d . " ({$row['game_platform_id']})";

					return $val;
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'round_no',
				'select' => "{$table}.round_no",
				'name' => lang('Round'),
			),
			array(
				'dt' => $i++,
				'alias' => 'external_uniqueid',
				'select' => "{$table}.external_uniqueid",
				'name' => lang('External Unique ID'),
			),

			array(
				'dt' => $i++,
				'alias' => 'details',
				'select' => "{$table}.extra_info",
				'name' => lang('Details'),
				'formatter' => function ($d, $row) use ($is_export) {
					$d_parsed = json_decode($d, true);
					$val = '';

					if(!$is_export){
						$val .= '<ul>';							
						if(isset($d_parsed['trans_type'])){
							$val .= '<li><b>Action:</b> '.$d_parsed['trans_type'] . '</li>';
						}

						if(isset($d_parsed['note'])){
							$val .= '<li><b>Note:</b> '.$d_parsed['note'] . '</li> ';
						}

						if(isset($d_parsed['items'])){							
							
							foreach($d_parsed['items'] as $key1 => $items){								
								if(is_array($items)){										
									foreach($items as $key2 => $item){
										$val .= '<li><b>'.$key2.':</b> '.$item . '</li>';
									}									
									//$val .= '<hr>';
								}								
							}							
						}
						$val .= '</ul>';

					} else {
						if(isset($d_parsed['trans_type'])){
							$val .= 'Action: '.$d_parsed['trans_type'];
						}

						if(isset($d_parsed['note'])){
							$val .= 'Note: '.$d_parsed['note'];
						}
					}

					return $val;
				},
			),
		);
		# END DEFINE COLUMNS #################################################################################################################################################

		$joins = array(
			'external_system' => "external_system.id={$table}.game_platform_id",			
		);

		# START PROCESS SEARCH FORM #################################################################################################################################################
		$where = array();
		$values = array();

		if ($player_id) {
			$where[] = "{$table}.player_id = ?";
			$values[] = $player_id;
		}
		
		if (isset($input['by_game_platform_id']) && !empty($input['by_game_platform_id'])) {
			$where[] = "{$table}.game_platform_id = ?";
			$values[] = $input['by_game_platform_id'];
		}
		
		if (isset($input['date_from'], $input['date_to'])) {
			$where[] = "{$table}.transaction_date BETWEEN ? AND ?";
			$values[] = $input['date_from'];
			$values[] = $input['date_to'];
		}

		# END PROCESS SEARCH FORM #################################################################################################################################################
		if($is_export){
            $this->data_tables->options['is_export']=true;
            if(empty($csv_filename)){
                $csv_filename=$this->utils->create_csv_filename(__FUNCTION__);
            }
            $this->data_tables->options['csv_filename']=$csv_filename;
        }
		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins);
		if($is_export){
			return $csv_filename;
		}

		return $result;
		// return $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins);
	}

    public function seamless_missing_payout_report($request, $is_export = false) {

		$readOnlyDB = $this->getReadOnlyDB();

		$this->load->library('data_tables', array("DB" => $readOnlyDB));
		// $this->load->library([ 'player_manager' ]);
		$this->load->model(array('transactions', 'player_model', 'game_logs', 'seamless_missing_payout'));

		$this->data_tables->is_export = $is_export;

		$input = $this->data_tables->extra_search($request);
		// $this->benchmark->mark('pre_processing_start');
		$where = array();
		$values = array();
		$having = array();
		$group_by = [];

		$col = 0;
		$na = $is_export ? lang('lang.norecyet') : '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
		
		$hideAction = false;
		if((isset($permissions['hide_action']) && $permissions['hide_action']==true) || $is_export){
			$hideAction = true;
		}

		$columns = array(
			array(
				'dt' => ($hideAction) ? NULL : $col++,
				'select' => 's.id',
				'alias' => 'action',
				'name' => lang('Action'),
				'formatter' => function ($d, $row) use($is_export) {
					if(!$is_export){
						$str='';
						if($row['status']<>1){
						$str.=" <input type='button' class='btn btn-success btn-xs m-b-5' onclick='queryStatus(" . $d . ")' value='".lang('Query Status')."'>";
						}
						# hide autofix
						// if($row['status']<>1){
						// 	$str.=" <input type='button' class='btn btn-danger btn-xs m-b-5' onclick='autoFix(" . $d . ")' value='".lang('Auto Fix')."'>";
						// }
						return $str;
					}
				},
			),			
			array(
				'dt' => $col++,
				'alias' => 'transaction_date',
				'select' => 's.transaction_date',
				'formatter' => 'dateFormatter',				
			),
			array(
				'dt' => $col++,
				'alias' => 'username',
				'select' => 'player.username',
				'name' => lang('Username'),
			),
			array(
				'dt' => $col++,
				'alias' => 'api_id',
				'select' => 'external_system.id',
				'name' => lang("API ID")				
			),
			array(
				'dt' => $col++,
				'alias' => 'api_name',
				'select' => 'external_system.system_code',
				'name' => lang("API Name")				
			),			
			array(
				'dt' => $col++,
				'alias' => 'round',
				'select' => 's.round_id'				
			),			
			array(
				'dt' => $col++,
				'alias' => 'transaction',
				'select' => 's.transaction_id'				
			),	
			array(
				'dt' => $col++,
				'alias' => 'deducted_amount',
				'select' => 's.deducted_amount'				
			),
			array(
				'dt' => $col++,
				'alias' => 'transaction_type',
				'select' => 's.transaction_type'				
			),	
			array(
				'dt' => $col++,
				'alias' => 'transaction_status',
				'select' => 's.transaction_status',
				'formatter' => function ($d, $row) use ($is_export) {
					$statusMap = array(
						Game_logs::STATUS_SETTLED   => 'Settled',
						Game_logs::STATUS_PENDING   => 'Pending',
						Game_logs::STATUS_REJECTED  => 'Rejected',
						Game_logs::STATUS_CANCELLED => 'Cancelled',
						Game_logs::STATUS_REFUND    => 'Refunded'
					);
					
					return isset($statusMap[$d]) ? lang($statusMap[$d]) : 'Unknown';
				}
			),
			array(
				'dt' => $col++,
				'alias' => 'external_uniqueid',
				'select' => 's.external_uniqueid'				
			),	
			array(
				'dt' => $col++,
				'alias' => 'status',
				'select' => 's.status',
				'name' => lang("Status"),
				'formatter' => function ($d, $row) use ($is_export) {
					if($d==1){
						return lang('Yes');
					}else{
						return lang('No');
					}
				}
			),
			array(
				'dt' => $col++,
				'alias' => 'fixed_by',
				'select' => 's.fixed_by',
				'name' => lang("Status"),
				'formatter' => function ($d, $row) use ($is_export) {					
					if(!empty($d)){
						
						return $d;
					}
					return 'N/A';
				}
			),
			array(
				'dt' => $col++,
				'alias' => 'notes',
				'select' => 's.note',
				'name' => lang("Status"),
				'formatter' => function ($d, $row) use ($is_export) {					
					if(!empty($d)){
						$arr = json_decode($d, true);
						$str = '';						
						foreach($arr as $key => $value){
							if(is_array($value)){
								$str .= $key.':<br>';
								foreach($value as $key2 => $value2){
									$str .= $value2.'<br>';
								}
							}else{
								$str .= $key.': '.$value.'<br>';
							}
						}
						return $str;
					}
					return 'N/A';
				}
			),
		);

		$this->utils->debug_log(__METHOD__, 'columns', $columns);

		$table = 'seamless_missing_payout_report s';
		$joins = array(
			'player' => "player.playerId = s.player_id",
			'external_system' => "external_system.id = s.game_platform_id",
		);

		if (isset($input['by_status'])) {
			$where[] = "s.status = ?";
			$values[] = $input['by_status'];
		}

		if (isset($input['by_date_from'], $input['by_date_to'])) {
			$where[] = "DATE(s.transaction_date) >=?";
			$where[] = "DATE(s.transaction_date) <=?";
			$values[] = $input['by_date_from'];
			$values[] = $input['by_date_to'];
		}

		if (isset($input['by_username'])) {
			$where[] = "player.username LIKE ?";
			$values[] = '%' . $input['by_username'] . '%';			
		}

		if (isset($input['by_game_platform_id'])) {
			$where[] = "s.game_platform_id = ?";
			$values[] = $input['by_game_platform_id'];			
		}
		
		# END PROCESS SEARCH FORM #################################################################################################################################################
		if($is_export){
            $this->data_tables->options['is_export']=true;			
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

		return $result;
	}


}