<?php
trait hedge_in_ag_module {

    public function hedgeInAG4playerList($request, $is_export = false) {

		$readOnlyDB = $this->getReadOnlyDB();

		$this->load->library('data_tables', array("DB" => $readOnlyDB));
		// $this->load->library([ 'player_manager' ]);
		$this->load->model(array('hedging_total_detail_info', 'hedging_total_detail_player'));

		$this->data_tables->is_export = $is_export;

		$input = $this->data_tables->extra_search($request);
		// $this->benchmark->mark('pre_processing_start');
		$where = array();
		$values = array();
		$having = array();
		$group_by = [];

		$col = 0;
		$na = $is_export ? lang('lang.norecyet') : '<i class="text-muted">' . lang('lang.norecyet') . '</i>';

		$columns = array(
			array (
				'dt' => $col++, // # 1
				'alias' => 'created_at',
				'select' => 'hedging_total_detail_info.created_at',
				// 'formatter' => 'dateFormatter',
				'name' => lang("Create Time"),
			),
			array(
				'dt' => $col++, // # 2
				'alias' => 'username',
				'select' => 'player.username',
				'name' => lang('Username'),
			),
			array(
				'dt' => $col++, // # 3
				'alias' => 'table_id',
				'select' => 'hedging_total_detail_info.table_id',
				'name' => lang("view_hedge_in_ag_preview.table_id")
			),
			array(
				'dt' => $col++, // # 4
				'alias' => 'updated_at',
				'select' => 'hedging_total_detail_info.updated_at',
				// 'formatter' => 'dateFormatter',
				'name' => lang('Last Update Time'),
			),
		);

		$table = 'hedging_total_detail_player';
		$joins = array(
			'player' => "player.playerId = hedging_total_detail_player.player_id",
			'playerdetails' => "playerdetails.playerId = player.playerId",
			'hedging_total_detail_info' => 'hedging_total_detail_player.table_id = hedging_total_detail_info.table_id'
		);

		if (isset($input['by_date_from'], $input['by_date_to'])) {
			$where[] = "DATE(hedging_total_detail_info.created_at) >=?";
			$where[] = "DATE(hedging_total_detail_info.created_at) <=?";
			$values[] = $input['by_date_from'];
			$values[] = $input['by_date_to'];
		}

		if (isset($input['by_username'])) {
			$where[] = "player.username LIKE ?";
			$values[] = '%' . $input['by_username'] . '%';
		}

		# END PROCESS SEARCH FORM #################################################################################################################################################
		if($is_export){
            $this->data_tables->options['is_export']=true;
			// $this->data_tables->options['only_sql']=true;
            if(empty($csv_filename)){
                $csv_filename=$this->utils->create_csv_filename(__FUNCTION__);
            }
            $this->data_tables->options['csv_filename']=$csv_filename;
		}
		$mark = 'data_sql86';
		$this->utils->markProfilerStart($mark);
		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins, $group_by, $having);

		if($is_export){
			return $csv_filename;
		}
		$this->utils->markProfilerEndAndPrint($mark);

		return $result;
	}

}