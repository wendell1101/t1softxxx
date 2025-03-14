<?php
/**
 *   filename:   api_agency_module.php
 *   date:       2016-06-12
 *   @brief:     APIs for agency sub system
 */

trait api_agency_module {
	public function agency_player_reports() {
    	if(!$this->isAdminOrAgency('agency_player_report')){
    		return;
    	}

		$this->load->model(array('report_model'));

		$request = $this->input->post();
        $this->utils->debug_log('agency_player_reports', $request);
		$viewPlayerInfoPerm = true;
		$is_export = false;


        $result = null;
        if ($this->utils->isEnabledFeature('enable_agency_player_report_generator')) {
            $result = $this->report_model->get_agency_player_reports_hourly($request, $viewPlayerInfoPerm, $is_export);
        } else {
            $result = $this->report_model->get_agency_player_reports($request, $viewPlayerInfoPerm, $is_export);
        }
        // $this->utils->debug_log('agency_player_reports, result', $result);

		// $this->output->set_content_type('application/json')->set_output(json_encode($result));
		$this->returnJsonResult($result);
	}

	public function agency_agent_reports() {
    	$logged_agent_id=null;
    	if(!$this->isLoggedAgency($logged_agent_id)){
    		return;
    	}

		$this->load->model(array('report_model'));

		$request = $this->input->post();
        $this->utils->debug_log('agency_agent_reports', $request);
		$viewAgentInfoPerm = true;
		$is_export = false;
		$result = $this->report_model->get_agency_agent_reports($request, $viewAgentInfoPerm, $is_export);
        // $this->utils->debug_log('agency_agent_reports, result', $result);

		// $this->output->set_content_type('application/json')->set_output(json_encode($result));
		$this->returnJsonResult($result);
	}

	public function agency_game_reports($player_id = null) {
    	if(!$this->isAdminOrAgency('agency_game_report')){
    		return;
    	}

		$this->load->model(array('report_model'));
		$request = $this->input->post();
        $this->utils->debug_log('GAME_REPORTS REQUEST', $request);
		$is_export = false;
		$result = $this->report_model->get_agency_game_reports($request, $player_id, $is_export);

        // $this->utils->debug_log('GAME_REPORTS RESULT', $result);

		// $this->output->set_content_type('application/json')->set_output(json_encode($result));
		$this->returnJsonResult($result);
	}

    /**
     *  fetch hierarchical tree data for a given agent
     *
     *  @param  in agent_id
     *  @return JSON data for tree view
     */
    public function get_agent_hierarchical_tree($agent_id) {
    	if(!$this->isAdminOrAgency('view_agent')){
    		return;
    	}

        $this->utils->debug_log('in agent tree', $agent_id);
        $this->load->model(array('agency_model'));
        $result = $this->agency_model->get_agent_hierarchical_tree($agent_id);

        // $this->utils->debug_log('agent tree', $result);
        $this->returnJsonResult($result, false, '*', false);
    }

    /**
     *  fetch data from agency_structures table
     *
     *  @param
     *  @return output in JSON
     */
    public function structure_list() {
		$this->load->library(array('permissions'));
		$this->permissions->setPermissions();
        if (!$this->permissions->checkPermissions('structure_list')) {
    		return;
    	}

        $this->load->model(array('agency_model'));
        $request = $this->input->post();
        $result = $this->agency_model->get_structure_list($request);

        $this->returnJsonResult($result);
    }

    /**
     *  fetch data from agency_agents table
     *
     *  @param
     *  @return output in JSON
     */
    public function agent_list() {
    	//it's for admin
    	if(!$this->isAdminOrAgency('agent_list')){
    		return;
    	}

        if($this->utils->isAgencySubProject()){
            if($this->isAgencyReadonlySubaccountLogged()){
                return show_error('No permission', 403);
            }
        }

        $this->load->model(array('agency_model'));
        $request = $this->input->post();
        //$this->utils->debug_log('agent_list request', $request);
        $result = $this->agency_model->get_agent_list($request);

        //$this->utils->debug_log($result);

        $this->returnJsonResult($result);
    }

    /**
     *  fetch data from agency_transactions table
     *
     *  @param
     *  @return output in JSON
     */
    public function credit_transactions() {
    	if(!$this->isAdminOrAgency('credit_transactions')){
    		return;
    	}

        $this->load->model(array('agency_model'));
        $request = $this->input->post();
        //$this->utils->debug_log($request);
        $result = $this->agency_model->get_transactions($request);

        //$this->utils->debug_log($result);

        $this->returnJsonResult($result);
    }

	/**
	 * detail: transfer request of a certain player for agency
	 *
	 * @param int $playerId transfer_request player_id
	 * @return string json
	 */
	public function agency_transfer_request($playerId = null) {
		$logged_agent_id=null;
    	if(!$this->isLoggedAgency($logged_agent_id)){
    		return;
		}

		$this->load->model(array('report_model'));
		$this->load->library(array('permissions'));
		$this->permissions->setPermissions();

		$request = $this->input->post();

		$permissions = array(
			'make_up_transfer_record' => false,
			'hide_action' => true,
		);

		$request['agent_id'] = $this->session->userdata('agent_id');

		$is_export = false;
		$result = $this->report_model->transferRequest($playerId, $request, $permissions, $is_export);
		$this->returnJsonResult($result);

	}

    /**
     *  fetch settlement data
     *
     *  @param
     *  @return output in JSON
     */
    public function agency_settlement() {
    	if(!$this->isAdminOrAgency('settlement')){
    		return;
    	}

        $this->load->model(array('agency_model'));
        $request = $this->input->post();
        //$this->utils->debug_log('logs request', $request);
        $result = $this->agency_model->get_settlement($request);

        //$this->utils->debug_log($result);

        $this->returnJsonResult($result);
    }

    /**
     *  fetch data from agency_logs table
     *
     *  @param
     *  @return output in JSON
     */
    public function agency_logs() {
		$this->load->library(array('permissions'));
		$this->permissions->setPermissions();
        if (!$this->permissions->checkPermissions('agency_logs')) {
    		return;
    	}
    	// $logged_agent_id=null;
    	// if(!$this->isLoggedAgency($logged_agent_id)){
    	// 	return;
    	// }

        $this->load->model(array('agency_model'));
        $request = $this->input->post();
        //$this->utils->debug_log('logs request', $request);
        $result = $this->agency_model->get_logs($request);

        //$this->utils->debug_log($result);

        $this->returnJsonResult($result);
    }

    /**
     *  get players under a given agent
     *
     *  @param
     *  @return output in JSON
     */
    public function players_list_under_agent() {
    	$logged_agent_id=null;
    	if(!$this->isLoggedAgency($logged_agent_id)){
    		return;
    	}

        if($this->isAgencyReadonlySubaccountLogged()){
            return show_error('No permission', 403);
        }

        $this->load->model(array('player_model'));
        $request = $this->input->post();
        $readonlyLogged=$this->isAgencyReadonlySubaccountLogged();
        // $this->utils->debug_log('logs request', $request);
        $result = $this->player_model->get_players_under_agent($request, $readonlyLogged);

        // $this->utils->debug_log($result);

        $this->returnJsonResult($result);
    }

	public function agency_game_history($player_id = null) {
    	$logged_agent_id=null;
    	if(!$this->isLoggedAgency($logged_agent_id)){
    		return;
    	}

        $this->load->model(['report_model']);
        $is_export = false;
        $request=$this->input->post();
        $result=$this->report_model->agency_game_history($request, $player_id, $is_export);

		$this->returnJsonResult($result);
	}

	public function game_rolling_comm_info() {
    	$logged_agent_id=null;
    	if(!$this->isLoggedAgency($logged_agent_id)){
    		return;
    	}

        $this->utils->debug_log('GAME_ROLLING_COMM_INFO start');
		$this->load->model(array('game_logs', 'agency_model'));

		$table = 'game_logs';
		$joins = array(
			'player' => 'player.playerId = game_logs.player_id',
			'game_description' => 'game_description.id = game_logs.game_description_id',
			'game_type' => 'game_type.id = game_description.game_type_id',
			'external_system' => 'game_logs.game_platform_id = external_system.id',
            'agency_game_rolling_comm' =>
            'agency_game_rolling_comm.agent_id = player.agent_id AND agency_game_rolling_comm.game_description_id = game_logs.game_description_id',
        );

		# START PROCESS SEARCH FORM ################################################################
		$where = array();
		$values = array();

		$request = $this->input->post();
		$input = $this->data_tables->extra_search($request);
        $this->utils->debug_log('GAME_ROLLING_COMM_INFO input', $input);

        $settlement_id = $input['settlement_id'];
        $player_id = $input['player_id'];

        $settlement = $this->agency_model->get_settlement_by_id($settlement_id);

        $where[] = "game_logs.flag = ".Game_logs::FLAG_GAME;
        $where[] = "game_logs.end_at BETWEEN ? AND ?";
        $values[] = $settlement['settlement_date_from'];
        $values[] = $settlement['settlement_date_to'];

        $where[] = "player.playerId = ?";
        $values[] = $player_id;

        $this->utils->debug_log('GAME_ROLLING_COMM_INFO where values', $where, $values);
        # START DEFINE COLUMNS #####################################################################
		$i = 0;
		$columns = array(
			array(
				'alias' => 'game_type',
				'select' => 'game_type.game_type',
			),
			array(
				'alias' => 'game_code',
				'select' => 'game_description.game_code',
			),
			array(
				'alias' => 'playerId',
				'select' => 'player.playerId',
			),
			array(
				'alias' => 'game_platform_percentage',
				'select' => 'agency_game_rolling_comm.game_platform_percentage',
            ),
			array(
				'alias' => 'game_type_percentage',
				'select' => 'agency_game_rolling_comm.game_type_percentage',
            ),
			array(
				'dt' => $i++,
				'alias' => 'player_username',
				'select' => 'player.username',
				'formatter' => function ($d, $row) {
					return sprintf('<a href="/agency/player_information/%s">%s</a>', $row['playerId'], $d);
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'end_at',
				'select' => 'game_logs.end_at',
				'formatter' => 'dateTimeFormatter',
			),
			array(
				'dt' => $i++,
				'alias' => 'game',
				'select' => 'external_system.system_code',
				'formatter' => 'defaultFormatter',
			),
			array(
				'dt' => $i++,
				'alias' => 'game_type_lang',
				'select' => 'game_type.game_type_lang',
				'formatter' => function ($d, $row) {
					return $this->data_tables->languageFormatter($d);
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'game_name',
				'select' => 'game_description.game_name',
				'formatter' => function ($d, $row) {
					return $this->data_tables->languageFormatter($d);
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'roundno',
				'select' => 'game_logs.table',
			),
			array(
				'dt' => $i++,
				'alias' => 'bet_amount',
				//'select' => 'game_logs.bet_amount',
				'select' => 'SUM(game_logs.bet_amount)',
				'formatter' => function ($d, $row) {
					return $this->utils->formatCurrencyNoSym($d);
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'result_amount',
				//'select' => 'game_logs.result_amount',
				'select' => 'SUM(game_logs.result_amount)',
				'formatter' => function ($d, $row) {
					return $this->utils->formatCurrencyNoSym($d);
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'bet_plus_result_amount',
				//'select' => 'game_logs.bet_amount + game_logs.result_amount',
				'select' => 'game_logs.player_id',
				//'formatter' => 'currencyFormatter',
				'formatter' => function ($d, $row) {
                    $sum = $row['bet_amount'] + $row['result_amount'];
					return $this->utils->formatCurrencyNoSym($sum);
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'game_rolling_comm',
				'select' => 'agency_game_rolling_comm.game_desc_percentage',
				'formatter' => function ($d, $row) {
                    $rolling_comm = 0.0;
                    if(!empty($d) && $d > 0) {
                        $rolling_comm = $d;
                    } else if (!empty($row['game_type_percentage']) && $row['game_type_percentage'] > 0) {
                        $rolling_comm = $row['game_type_percentage'];
                    } else if (!empty($row['game_platform_percentage']) && $row['game_platform_percentage'] > 0) {
                        $rolling_comm = $row['game_platform_percentage'];
                    }
					return $rolling_comm;
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'rolling_comm_amt',
				'select' => 'player.rolling_comm',
				'formatter' => function ($d, $row) {
                    // TODO: which one has higher priority, player_rolling_comm or game_rolling_comm?
                    if ($row['game_rolling_comm'] > 0) {
                        $rolling_comm = $row['game_rolling_comm'];
                    } else {
                        $rolling_comm = $d;
                    }
                    $rolling_comm_amt = $row['bet_amount'] * $rolling_comm / 100.0;
					return $rolling_comm_amt;
				},
			),
		);
		# END DEFINE COLUMNS #######################################################################

		# END PROCESS SEARCH FORM ##################################################################

		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins);
        //$this->utils->debug_log('AGENCY_GAME_HISTORY request', $request, $where, $values, $joins);
        //$this->utils->debug_log('AGENCY_GAME_HISTORY result', $result);

		$this->returnJsonResult($result);
	}

    /**
     *  fetch data from agency_structures table
     *
     *  @param
     *  @return output in JSON
     */
    public function agent_tier_comm_pattern_list() {
		$this->load->library(array('permissions'));
		$this->permissions->setPermissions();
        //if (!$this->permissions->checkPermissions('tier_comm_pattern_list')) {
        if (!$this->permissions->checkPermissions('view_agent')) {
    		return;
    	}

        $this->load->model(array('agency_model'));
        $request = $this->input->post();
        //$this->utils->debug_log($request);
        $result = $this->agency_model->get_tier_comm_pattern_list($request);

        //$this->utils->debug_log($result);

        $this->returnJsonResult($result);
    }

    public function bet_limit_template_list($agent_id = null){
    	$logged_agent_id=null;
    	if(!$this->isLoggedAgency($logged_agent_id)){
    		return;
    	}

		$this->load->model(['game_description_model','agency_model']);

		$request = $this->input->post();
		$input = $this->data_tables->extra_search($request);

		# START DEFINE COLUMNS #####################################################################
		$i = 0;
		$columns = array(
			array(
				'alias' => 'agent_id',
				'select' => 'bet_limit_template_list.agent_id',
			),
			array(
				'alias' => 'default_template',
				'select' => 'IF(agent_id = '.$input['agent_id'].', default_template, 0)',
			),
			array(
				'dt' => $i++,
				'alias' => 'id',
				'select' => 'bet_limit_template_list.id',
				'formatter' => function ($d, $row) use ($input) {
					return $row['agent_id'] == $input['agent_id'] ? ('<a class="btn btn-xs btn-primary" href="/agency/edit_bet_limit_template/'.$d.'">'.lang('Edit').'</a>') : '';
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'updated_at',
				'select' => 'bet_limit_template_list.updated_at',
				'formatter' => 'dateTimeFormatter',
			),
			array(
				'dt' => $i++,
				'alias' => 'template_name',
				'select' => 'bet_limit_template_list.template_name',
				'formatter' => function ($d, $row) {
					return $d . ($row['default_template'] == 1 ? (' (' . lang('Default') . ')') : '');
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'note',
				'select' => 'bet_limit_template_list.note',
				// 'formatter' => 'defaultFormatter',
			),
			array(
				'dt' => $i++,
				'alias' => 'status',
				'select' => 'bet_limit_template_list.status',
				'formatter' => function ($d, $row) {
					$txt=lang('Active');

					if($d==Game_description_model::STATUS_DISABLED){
						$txt=lang('Inactive');
					}
					return $txt;
				},
			),
		);
		# END DEFINE COLUMNS #######################################################################

		$table = 'bet_limit_template_list';
		$joins = array(
			// 'player' => 'player.playerId = game_logs.player_id',
			// 'game_description' => 'game_description.id = game_logs.game_description_id',
			// 'game_type' => 'game_type.id = game_description.game_type_id',
			// 'external_system' => 'game_logs.game_platform_id = external_system.id',
		);

		# START PROCESS SEARCH FORM ################################################################
		$where = array();
		$values = array();

        if (isset($input['agent_id']) && !empty($input['agent_id'])) {
        	$upline = $this->agency_model->get_upline($agent_id, false);
        	if(!empty($upline)){
	            $where[] = "(bet_limit_template_list.agent_id = ? OR (bet_limit_template_list.agent_id IN(".implode(',', $upline).") AND bet_limit_template_list.public_to_downline = 1))";
				$values[] = $agent_id;
        	}
        }

        if (isset($input['by_game_platform_id'])) {
			$where[] = "bet_limit_template_list.game_platform_id = ?";
			$values[] = $input['by_game_platform_id'];
		}

		if (isset($input['date_from'], $input['date_to'])) {
			$where[] = "bet_limit_template_list.updated_at >= ?";
			$values[] = $input['date_from'];
			$where[] = "bet_limit_template_list.updated_at <= ?";
			$values[] = $input['date_to'];
		}

		if (isset($input['template_name'])) {
			$where[] = "bet_limit_template_list.template_name LIKE ?";
			$values[] = '%' . $input['template_name'] . '%';
		}

		# END PROCESS SEARCH FORM ##################################################################

		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins);

		$this->returnJsonResult($result);
    }

    public function player_rolling_comm(){
    	$logged_agent_id=null;
    	if(!$this->isLoggedAgency($logged_agent_id)){
    		return;
    	}

        $this->load->model(array('player_model','report_model'));
        $request = $this->input->post();
        $is_export=false;
        $result = $this->report_model->player_rolling_comm($request, $logged_agent_id, $is_export);

        $this->returnJsonResult($result);
    }

    private function isAdminOrAgency($permission = null){

    	if(!$this->isLoggedAgency()){
            if($permission == null)
                return false;
    		//not agency
			$this->load->library(array('permissions'));
			$this->permissions->setPermissions();
			return $this->permissions->checkPermissions($permission);
    	}

    	return true;
    }

    public function agency_settlement_wl($mode = 'only_agent') {
        if(!$this->isAdminOrAgency('settlement_wl')){
            return;
        }

        $readonlyLogged=$this->isAgencyReadonlySubaccountLogged();

        $this->load->model(array('agency_model'));
        $request = $this->input->post();
        $is_export=false;
        //$this->utils->debug_log('logs request', $request);
        $result = $this->agency_model->getWlSettlement($request, $mode, $is_export, $readonlyLogged);

        //$this->utils->debug_log($result);

        $this->returnJsonResult($result);
    }

    public function agency_settlement_detail_wl() {
        if(!$this->isAdminOrAgency('settlement_wl')){
            return;
        }

        $this->load->model(array('agency_model'));
        $request = $this->input->post();
        $result = $this->agency_model->getWlSettlementDetail($request);
        $this->returnJsonResult($result);
    }
}
// zR to open all folded lines
// vim:ft=php:fdm=marker
// end of api_agency_module.php
