<?php
require_once dirname(__FILE__) . '/BaseReport.php';
require_once dirname(__FILE__) . '/kyc_status.php';

/**
 * Report Management
 *
 * General behaviors include :
 * * Summary Reports
 * * List of new members
 * * Show list of total members
 * * View and searching of player reports
 * * Exporting data in player reports
 * * Game reports
 * * Payment reports
 * * Promotion reports
 * * List of duplication accounts
 * * Viewing of transaction list
 *
 * @see Redirect redirect to report page
 *
 * @category report_management
 * @version 1.8.10
 * @copyright 2013-2022 tot
 */
class Report_management extends BaseReport {

	use kyc_status;

    const TAG_ALL = 'all';

	const _operator_setting_name4detected_tag_id = 'detected_tag_id_in_view_player_login_via_same_ip';

	function __construct() {
		parent::__construct();
		$this->load->helper(array('date_helper', 'url'));
		$this->load->library(array('permissions', 'excel', 'form_validation', 'template', 'pagination', 'player_manager', 'report_functions', 'gcharts', 'marketing_functions', 'depositpromo_manager', 'transactions_library'));

		$this->permissions->checkSettings();
		$this->permissions->setPermissions(); //will set the permission for the logged in user
	}

	/**
	 * set message for users
	 *
	 * @param   int
	 * @param   string
	 * @return  set session user data
	 */
	// public function alertMessage($type, $message) {
	// 	switch ($type) {
	// 	case '1':
	// 		$show_message = array(
	// 			'result' => 'success',
	// 			'message' => $message,
	// 		);
	// 		$this->session->set_userdata($show_message);
	// 		break;

	// 	case '2':
	// 		$show_message = array(
	// 			'result' => 'danger',
	// 			'message' => $message,
	// 		);
	// 		$this->session->set_userdata($show_message);
	// 		break;

	// 	case '3':
	// 		$show_message = array(
	// 			'result' => 'warning',
	// 			'message' => $message,
	// 		);
	// 		$this->session->set_userdata($show_message);
	// 		break;
	// 	}
	// }

	/**
	 * Loads template for view based on regions in
	 * config > template.php
	 *
	 */
	private function loadTemplate($title, $description, $keywords, $activenav) {
		$this->template->add_css('resources/css/report_management/style.css');
		$this->template->add_js('resources/js/report_management/report_management.js');

		// $this->template->add_js('resources/js/numeral.min.js');
		// $this->template->add_js('resources/js/moment.min.js');
		// $this->template->add_js('resources/js/jquery.daterangepicker.js');
		// $this->template->add_css('resources/css/daterangepicker.css');

		//$this->template->add_js('resources/js/jquery-1.11.1.min.js');
		$this->template->add_js('resources/js/datatables.min.js');
		$this->template->add_js('resources/js/select2.full.js');
		$this->template->add_css('resources/css/select2.min.css');
		$this->template->add_css('resources/css/general/style.css');
		$this->template->add_css('resources/css/datatables.min.css');

		$this->template->write('title', $title);
		$this->template->write('description', $description);
		$this->template->write('keywords', $keywords);
		$this->template->write('activenav', $activenav);
		$this->template->write('username', $this->authentication->getUsername());
		$this->template->write('userId', $this->authentication->getUserId());
		$this->template->write_view('sidebar', 'report_management/sidebar');
	}

	/**
	 * detail: Shows Error message if user can't access the page
	 *
	 * @return  rendered Template
	 */
	private function error_access($error_message = null) {
		$this->loadTemplate('Report Management', '', '', 'report');
		$reportUrl = $this->utils->activeReportSidebar();
		$data['redirect'] = $reportUrl;

		$message = lang('con.rpm01');
		if(!empty($error_message)){
			$message = $error_message;
		}
		$this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);

		$this->template->write_view('main_content', 'error_page', $data);
		$this->template->render();
	}

	/**
	 * detail: Index Page of Report Management
	 *
	 *
	 * @return  void
	 */
	public function index() {
		redirect('report_management/viewDailyReport'); //this will redirect to viewLogs instead
	}

	###########################################################################################################################################################

	/**
	 * detail: display player report lists
	 *
	 * @return load template
	 */
	public function viewPlayerReport() {
		if (true) { # !$this->permissions->checkPermissions('player_report')
			$this->error_access();
		} else {
			$this->load->model('affiliatemodel');

			$data['conditions'] = $this->safeLoadParams(array(
				'date_from' => $this->utils->getTodayForMysql(),
				'hour_from' => '00',
				'date_to' => $this->utils->getTodayForMysql(),
				'hour_to' => '23',
				'date_from' => $this->utils->getTodayForMysql().' '.Utils::FIRST_TIME,
				'date_to' => $this->utils->getTodayForMysql().' '.Utils::LAST_TIME,
				'depamt2' => '',
				'depamt1' => '',
				'widamt2' => '',
				'widamt1' => '',
				'playerlevel' => '',
				'group_by' => '',
				'username' => '',
				'affiliate_name' => '',
				'affiliate_tags' => '',
				'agent_name' => '',
				'search_by' => '',
				'player_tag' => '',
				'include_all_downlines' => '',
				'aff_include_all_downlines' => '',
				'affiliate_agent' => '',
				'referrer' => '',
			));
            $data['export_report_permission'] = $this->permissions->checkPermissions('export_player_report');

			$data['allLevels'] = $this->player_manager->getAllPlayerLevels();
			$data['tags'] = $this->affiliatemodel->getActiveTags();
			$data['player_tags'] = $this->player->getAllTagsOnly();

			$this->loadTemplate('Player Report', '', '', 'report');
			$this->template->write_view('main_content', 'report_management/player_report/view_player_report', $data);
			$this->template->render();
		}
	}

    public function viewPlayerReport2() {
        $this->load->model('affiliatemodel');
        if (!$this->permissions->checkPermissions('player_report')) {
            $this->error_access();
        } else {

			$enable_freeze_top_in_list = false; // default
			$enable_freeze_top_method_list = $this->config->item('enable_freeze_top_method_list');
			if( !empty($enable_freeze_top_method_list) ){
				if( in_array(__METHOD__, $enable_freeze_top_method_list) ){ // __METHOD__, "Payment_management::deposit_list"
					$enable_freeze_top_in_list = true;
				}
			}
			$data['enable_freeze_top_in_list'] = $enable_freeze_top_in_list;

			$enable_timezone_query = false; // default
			$enable_timezone_query_method_list = $this->config->item('enable_timezone_query_method_list');
			if( !empty($enable_timezone_query_method_list) ){
				if( in_array(__METHOD__, $enable_timezone_query_method_list) ){ // __METHOD__, "Payment_management::deposit_list"
					$enable_timezone_query = true;
				}
			}
			$data['enable_timezone_query'] = $enable_timezone_query;

			$start_today = date("Y-m-d") . ' 00:00:00';
			$end_today = date("Y-m-d") . ' 23:59:59';
			$search_reg_date_default = 'off'; // Default: a. enable checkbox.

            $data['conditions'] = $this->safeLoadParams(array(
                'hour_from' => '00',
                'hour_to' => '23',
                'date_from' => $this->utils->getTodayForMysql().' '.Utils::FIRST_TIME,
                'date_to' => $this->utils->getTodayForMysql().' '.Utils::LAST_TIME,
                'depamt2' => '',
                'depamt1' => '',
                'widamt2' => '',
                'widamt1' => '',
                'playerlevel' => '',
                'group_by' => '',
                'username' => '',
                'affiliate_name' => '',
                'affiliate_tags' => '',
                'agent_name' => '',
                'search_by' => '',
                'tag_list' => array(),
                'include_all_downlines' => '',
                'aff_include_all_downlines' => '',
                'affiliate_agent' => '',
                'referrer' => '',
				'timezone' => '',
				'turnovermt_greater_than' => '',
				'turnovermt_less_than' => '',
				'registration_date_from' => $start_today,
				'registration_date_to' => $end_today,
				'search_reg_date' => $search_reg_date_default
            ));
            $data['export_report_permission'] = $this->permissions->checkPermissions('export_player_report');

            $data['allLevels'] = $this->player_manager->getAllPlayerLevels();
			$data['tags'] = $this->affiliatemodel->getActiveTags();
			$data['selected_tags'] =$a= $this->input->get_post('tag_list');
			$data['selected_include_tags'] =$a= $this->input->get_post('tag_list_included');
            $data['player_tags'] = $this->player->getAllTagsOnly();

			$this->loadTemplate(lang('report.s09'), '', '', 'report');
			// $this->template->add_js('resources/js/dataTables.fixedColumns.min.js'); // disable for the feature Not required
			$this->template->add_css('resources/third_party/bootstrap-multiselect-master/dist/css/bootstrap-multiselect.css');
			$this->template->add_js('resources/third_party/bootstrap-multiselect-master/dist/js/bootstrap-multiselect.js');
			$this->template->add_css('resources/floating_scroll/jquery.floatingscroll.css');
			$this->template->add_js('resources/floating_scroll/jquery.floatingscroll.js');
			$this->template->write_view('main_content', 'report_management/player_report/view_player_report2', $data);
			$this->template->render();
        }
    }

    /**
	 * detail: display game report lists
	 *
	 * @return load template
	 */
	public function viewGamesReport() {
		$this->load->model('game_type_model');

		if (!$this->permissions->checkPermissions('game_report')) {
			$this->error_access();
		} else {
			$this->load->helper('form');

			if (!$this->permissions->checkPermissions('export_games_report')) {
				$data['export_report_permission'] = FALSE;
			} else {
				$data['export_report_permission'] = TRUE;
			}

			$this->load->model(array('player', 'affiliatemodel'));

		//	$queryDateTimeEnd = $startDate->modify($this->sync_time_interval)->format('Y-m-d H:i:s');

			$start_today = date("Y-m-d") . ' 00:00:00';
			$end_today = date("Y-m-d") . ' 23:59:59';
			$data['conditions'] = $this->safeLoadParams(array(
				'date_from' => $this->utils->getTodayForMysql(),
				'hour_from' => '00',
				'date_to' => $this->utils->getTodayForMysql(),
				'hour_to' => '23',
				'datetime_from' => $this->utils->getTodayForMysql().' '.Utils::FIRST_TIME,
				'datetime_to' => $this->utils->getTodayForMysql().' '.Utils::LAST_TIME,
				'datetime_from_timezone' =>  $this->utils->getTodayForMysql().' '.Utils::FIRST_TIME,
				'datetime_to_timezone' =>  $this->utils->getTodayForMysql().' '.Utils::LAST_TIME,
				'total_bet_from' => '',
				'total_bet_to' => '',
				'total_loss_from' => '',
				'total_loss_to' => '',
				'total_gain_from' => '',
				'total_gain_to' => '',
				'group_by' => '',
				'username' => '',
				'affiliate_username' => '',
				'agent_name' => '',
				'external_system' => '',
				'game_type' => '',
				'game_type_multiple' => '',
				'show_multiselect_filter' => '',
				'total_player' => '',
				'timezone' => '',
                'include_all_downlines' => '',
				'affiliate_agent' => '',
				'referrer' => '',
				'include_all_downlines_aff' => '',
                'agency_code' => '',
			));


			$show_blocked_game_api_data_on_games_report = $this->utils->getConfig('show_blocked_game_api_data_on_games_report');

			$data['game_apis_map'] = [];
			$data['mulitple_select_game_map']=[];

			if($show_blocked_game_api_data_on_games_report === true){
				$data['game_apis_map'] = $this->utils->getGameSystemMap(false);
				foreach ($data['game_apis_map']  as $game_platform_id => $value) {
					$game_type_rows = $this->game_type_model->getAllGameTypeList([$game_platform_id]);
					foreach ($game_type_rows as $game_type_row) {
						$data['mulitple_select_game_map'][$game_platform_id][] = $game_type_row;
					}
				}
			}else{
				$data['game_apis_map'] = $this->utils->getGameSystemMap(true);
				$data['mulitple_select_game_map']= $this->game_type_model->getActiveGamePlatformGameTypes();
			}

			$data['conditions']['search_unsettle_game']= $this->input->get('search_unsettle_game')=='true';

       		// $data['playerList'] = $this->player_model->getAllEnabledPlayers();

			// $this->utils->debug_log($data['conditions']);

			// $this->utils->debug_log($data['conditions']);
			// $data['conditions']['enableDate'] = $this->safeGetParam('enableDate', true, true);

			//OGP-25040
			$data['selected_tags'] = $this->input->get_post('tag_list');
			$data['tags'] = $this->player->getAllTagsOnly();

			$data['selected_affiliate_tags'] = $this->input->get_post('affiliate_tag_list');
			$data['affiliate_tags'] = $this->affiliatemodel->getActiveTags();

			$data['enable_freeze_top_in_list'] = $this->utils->_getEnableFreezeTopWithMethod(__METHOD__, $this->config->item('enable_freeze_top_method_list'));

			$this->loadTemplate(lang('report.s07'), '', '', 'report');
			$this->template->add_css('resources/third_party/bootstrap-multiselect-master/dist/css/bootstrap-multiselect.css');
        	$this->template->add_js('resources/third_party/bootstrap-multiselect-master/dist/js/bootstrap-multiselect.js');
			$this->template->write_view('main_content', 'report_management/games_report/view_games_report', $data);
			$this->template->render();
		}
	}

	 /**
	 * detail: display oneworks game report lists
	 *
	 * @return load template
	 */
	public function viewOneworksGameReport() {
		$this->load->model(array('game_type_model','external_system'));
		$game_apis = $this->utils->getGameSystemMap(false);
		$api_id = $this->utils->getConfig('oneworks_game_report_platform_id');
        if(empty($api_id)){
        	$this->error_access();
			return;
        }

		if (!$this->utils->isEnabledFeature('enabled_oneworks_game_report') || !array_key_exists($api_id,$game_apis)) {
			$this->error_access();
			return;
		}
		if (!$this->permissions->checkPermissions('game_report')) {
			$this->error_access();
		} else {
			$this->load->helper('form');

			if (!$this->permissions->checkPermissions('export_games_report')) {
				$data['export_report_permission'] = FALSE;
			} else {
				$data['export_report_permission'] = TRUE;
			}

			$this->load->model(array('player'));

			$start_today = date("Y-m-d") . ' 00:00:00';
			$end_today = date("Y-m-d") . ' 23:59:59';
			$data['conditions'] = $this->safeLoadParams(array(
				'date_from' => $this->utils->getTodayForMysql(),
				'hour_from' => '00',
				'date_to' => $this->utils->getTodayForMysql(),
				'hour_to' => '23',
				'datetime_from' => $this->utils->getTodayForMysql().' '.Utils::FIRST_TIME,
				'datetime_to' => $this->utils->getTodayForMysql().' '.Utils::LAST_TIME,
				'datetime_from_timezone' =>  $this->utils->getTodayForMysql().' '.Utils::FIRST_TIME,
				'datetime_to_timezone' =>  $this->utils->getTodayForMysql().' '.Utils::LAST_TIME,
				'total_bet_from' => '',
				'total_bet_to' => '',
				'total_loss_from' => '',
				'total_loss_to' => '',
				'total_gain_from' => '',
				'total_gain_to' => '',
				'group_by' => '',
				'username' => '',
				'affiliate_username' => '',
				'agent_name' => '',
				'external_system' => '',
				'game_type' => '',
				'game_type_multiple' => '',
				'show_multiselect_filter' => '',
				'total_player' => '',
				'timezone' => '',
                'include_all_downlines' => '',
				'affiliate_agent' => '',
				'referrer' => '',
			));

			$data['game_apis_map'] = $game_apis;
			$data['platform_name'] = $this->external_system->getNameById($api_id);
			$data['mulitple_select_game_map']= $this->game_type_model->getActiveGamePlatformGameTypes();
			$data['conditions']['search_unsettle_game']= $this->input->get('search_unsettle_game')=='true';
			$data['enable_freeze_top_in_list'] = $this->utils->_getEnableFreezeTopWithMethod(__METHOD__, $this->config->item('enable_freeze_top_method_list'));

			$this->loadTemplate(lang('Games Report'), '', '', 'report');
			$this->template->write_view('main_content', 'report_management/games_report/view_oneworks_game_report', $data);
			$this->template->render();
		}
	}

	/**
	 *
	 * detail: display the game report of the certain player
	 *
	 * @param int $player_id player id
	 * @return load template
	 */
	public function viewPlayerGameReport($player_id) {
		if (!$this->permissions->checkPermissions('game_report')) {
			$this->error_access();
		} else {
			$this->loadTemplate('Games Report', '', '', 'report');
			$player = $this->player_model->getPlayerById($player_id);
			$this->template->write_view('main_content', 'report_management/games_report/view_player_game_report', array(
				'player_id' => $player_id,
				'username' => $player->username,
			));
			$this->template->render();
		}
	}

	###########################################################################################################################################################

	/**
	 * detail: display the list of user logs
	 *
	 * @return load template
	 */
	public function viewLogs() {
		if (!$this->permissions->checkPermissions('report')) {
			$this->error_access();
		} else {
			$this->loadTemplate('System Management', '', '', 'report');

			$data['count_all'] = count($this->report_functions->getAllLogs(null, null));
			$config['base_url'] = "javascript:get_log_pages(";
			$config['total_rows'] = $data['count_all'];
			$config['per_page'] = '10';
			$config['num_links'] = '1';

			$config['first_tag_open'] = '<li>';
			$config['last_tag_open'] = '<li>';
			$config['next_tag_open'] = '<li>';
			$config['prev_tag_open'] = '<li>';
			$config['num_tag_open'] = '<li>';

			$config['first_tag_close'] = '</li>';
			$config['last_tag_close'] = '</li>';
			$config['next_tag_close'] = '</li>';
			$config['prev_tag_close'] = '</li>';
			$config['num_tag_close'] = '</li>';

			$config['cur_tag_open'] = "<li><span><b>";
			$config['cur_tag_close'] = "</b></span></li>";

			$this->pagination->initialize($config);

			$data['total_pages'] = ceil($data['count_all'] / $config['per_page']);
			$data['logs'] = $this->report_functions->getAllLogs(null, null);

			//export report permission checking
			// OGP-10782 This is not being used anymore.
			// if (!$this->permissions->checkPermissions('export_report')) {
			// 	$data['export_report_permission'] = FALSE;
			// } else {
			// 	$data['export_report_permission'] = TRUE;
			// }

			$this->template->write_view('main_content', 'report_management/view_logs', $data);
			$this->template->render();
		}
	}

	/**
	 * detail: export logs report to excel
	 *
	 * @return  excel format
	 */
	public function exportLogsReportToExcel() {
		$data = array(
			'username' => $this->authentication->getUsername(),
			'management' => 'Report Management',
			'userRole' => $this->rolesfunctions->getRoleByUserId($this->authentication->getUserId())['roleName'],
			'action' => 'Exported Logs Report List',
			'description' => "User " . $this->authentication->getUsername() . " exported logs report list.",
			'logDate' => date("Y-m-d H:i:s"),
			'status' => 0,
		);

		$this->report_functions->recordAction($data);

		$result = $this->report_functions->getLogsReportListToExport();
		/*var_dump($result);exit();*/
		$this->excel->to_excel($result, 'logsreportlist-excel');
	}

	/**
	 * detail: export logs report to excel
	 *
	 * @return  excel format
	 */
	public function exportPTGameApiReportToExcel() {
		$data = array(
			'username' => $this->authentication->getUsername(),
			'management' => 'Report Management',
			'userRole' => $this->rolesfunctions->getRoleByUserId($this->authentication->getUserId())['roleName'],
			'action' => 'Exported PT Game API Report List',
			'description' => "User " . $this->authentication->getUsername() . " exported pt game api report list.",
			'logDate' => date("Y-m-d H:i:s"),
			'status' => 0,
		);

		$this->report_functions->recordAction($data);

		$result = $this->report_functions->getPTGameAPIReportListToExport();
		//var_dump($result);exit();
		//$this->excel->to_excel($result, 'logsreportlist-excel');
		$d = new DateTime();
		$this->utils->create_excel($result, 'logsreportlist_' . $d->format('Y_m_d_H_i_s') . '_' . rand(1, 999));
	}

	/**
	 * detail: View a Sorted Users
	 *
	 * @param int $segment
	 * @return  rendered Template with array of data
	 */
	public function get_log_pages($segment = '') {
		$data['count_all'] = count($this->report_functions->getAllLogs(null, null));
		$config['base_url'] = "javascript:get_log_pages(";
		$config['total_rows'] = $data['count_all'];
		$config['per_page'] = '10';
		$config['num_links'] = '1';

		$config['first_tag_open'] = '<li>';
		$config['last_tag_open'] = '<li>';
		$config['next_tag_open'] = '<li>';
		$config['prev_tag_open'] = '<li>';
		$config['num_tag_open'] = '<li>';

		$config['first_tag_close'] = '</li>';
		$config['last_tag_close'] = '</li>';
		$config['next_tag_close'] = '</li>';
		$config['prev_tag_close'] = '</li>';
		$config['num_tag_close'] = '</li>';

		$config['cur_tag_open'] = "<li><span><b>";
		$config['cur_tag_close'] = "</b></span></li>";

		$this->pagination->initialize($config);

		$data['total_pages'] = ceil($data['count_all'] / $config['per_page']);
		$data['logs'] = $this->report_functions->getAllLogs(null, $segment);

		$this->load->view('report_management/ajax_logs', $data);
	}

	/**
	 * view monthly earnings
	 *
	 * @param string $type
	 * @return  void
	 */
	public function viewReport($type) {
		switch ($type) {
		//line chart report
		case 1:
			redirect('report_management/viewEarningsReport');
			break;

		case 2:
			redirect('report_management/viewPlayerReport');
			break;

		case 3:
			redirect('report_management/viewPaymentReport');
			break;

		case 4:
			redirect('report_management/viewPromotionReport?enableDate=true');
			break;

		case 5:
			redirect('report_management/viewPlayerBlanceReport');
			break;

		default:
			redirect('report_management/viewPaymentReport');
			break;
		}
	}

	/**
	 * view player report
	 *
	 * @return  array
	 */
	/*public function viewPlayerReport() {
		$sortBy['sortByUsername'] = $this->input->post('sortByUsername');
		$sortBy['sortByPlayerLevel'] = $this->input->post('sortByPlayerLevel');
		$sortBy['sortByBalanceAmountLessThan'] = $this->input->post('sortByBalanceAmountLessThan');
		$sortBy['sortByBalanceAmountGreaterThan'] = $this->input->post('sortByBalanceAmountGreaterThan');
		$sortBy['sortByGender'] = $this->input->post('sortByGender');
		$sortBy['sortByAffiliate'] = $this->input->post('sortByAffiliate');
		$sortBy['sortByTag'] = $this->input->post('sortByTag');
		$sortBy['orderByReport'] = $this->input->post('orderByReport');
		$sortBy['sortBySortby'] = $this->input->post('sortBySortby');
		$sortBy['sortByItemCnt'] = $this->input->post('sortByItemCnt');
		$sortBy['sortByItemCnt'] == '' ?  $sortBy['sortByItemCnt'] = 5 : '';
		$sortBy['sortBySignUpPeriodFrom'] = $this->input->post('sortBySignUpPeriodFrom');
		$sortBy['sortBySignUpPeriodTo'] = $this->input->post('sortBySignUpPeriodTo');
		$sortBy['sortByLastLoginFrom'] = $this->input->post('sortByLastLoginFrom');
		$sortBy['sortByLastLoginTo'] = $this->input->post('sortByLastLoginTo');
		$sortBy['sortByLastLogoutFrom'] = $this->input->post('sortByLastLogoutFrom');
		$sortBy['sortByLastLogoutTo'] = $this->input->post('sortByLastLogoutTo');

		$this->session->set_userdata('sortByUsername',$sortBy['sortByUsername']);
		$this->session->set_userdata('sortByPlayerLevel',$sortBy['sortByPlayerLevel']);
		$this->session->set_userdata('sortByBalanceAmountLessThan',$sortBy['sortByBalanceAmountLessThan']);
		$this->session->set_userdata('sortByBalanceAmountGreaterThan',$sortBy['sortByBalanceAmountGreaterThan']);
		$this->session->set_userdata('sortByGender',$sortBy['sortByGender']);
		$this->session->set_userdata('sortByAffiliate',$sortBy['sortByAffiliate']);
		$this->session->set_userdata('sortByTag',$sortBy['sortByTag']);
		$this->session->set_userdata('orderByReport',$sortBy['orderByReport']);
		$this->session->set_userdata('sortBySortby',$sortBy['sortBySortby']);
		$this->session->set_userdata('sortByItemCnt',$sortBy['sortByItemCnt']);

		$this->session->set_userdata('sortBySignUpPeriodFrom',$sortBy['sortBySignUpPeriodFrom']);
		$this->session->set_userdata('sortBySignUpPeriodTo',$sortBy['sortBySignUpPeriodTo']);
		$this->session->set_userdata('sortByLastLoginFrom',$sortBy['sortByLastLoginFrom']);
		$this->session->set_userdata('sortByLastLoginTo',$sortBy['sortByLastLoginTo']);
		$this->session->set_userdata('sortByLastLogoutFrom',$sortBy['sortByLastLogoutFrom']);
		$this->session->set_userdata('sortByLastLogoutTo',$sortBy['sortByLastLogoutTo']);

		$data['count_all'] = count($this->report_functions->getPlayersReport(null,null, null));
		$config['base_url'] = "javascript:get_log_pages(";
		$config['total_rows'] = $data['count_all'];
		$config['per_page'] = $sortBy['sortByItemCnt'];
		$config['num_links'] = '1';

		$config['first_tag_open'] = '<li>';
		$config['last_tag_open']= '<li>';
		$config['next_tag_open']= '<li>';
		$config['prev_tag_open'] = '<li>';
		$config['num_tag_open'] = '<li>';

		$config['first_tag_close'] = '</li>';
		$config['last_tag_close']= '</li>';
		$config['next_tag_close']= '</li>';
		$config['prev_tag_close'] = '</li>';
		$config['num_tag_close'] = '</li>';

		$config['cur_tag_open'] = "<li><span><b>";
		$config['cur_tag_close'] = "</b></span></li>";

		$this->pagination->initialize($config);

		$data['total_pages'] = ceil($data['count_all'] / $config['per_page']);
		$data['playerReportData'] = $this->report_functions->getPlayersReport($sortBy, $config['per_page'], null);

		$data['playerLevels'] = $this->player_manager->getAllPlayerLevels();
		$data['playerTags'] = $this->player_manager->getAllTags();
		//var_dump($data);exit();
		$this->loadTemplate('Player Report', '', '', 'report');

		$this->template->write_view('main_content', 'report_management/view_player_report',$data);
		$this->template->render();
	*/

	/**
	 * detail: search player report
	 *
	 * $POST $period
	 * $POST $start_date
	 * $POST $end_date
	 * $POST $date_range_value
	 * $POST $depamt1
	 * $POST $depamt2
	 * $POST $widamt1
	 * $POST $widamt2
	 * $POST $status
	 * $POST $playerlevel
	 * $POST $username
	 * @return  array
	 */
	public function searchPlayerReport() {
		if (!$this->permissions->checkPermissions('player_report')) {
			$this->error_access();
		} else {
			$period = $this->input->post('period');
			$start_date = $this->input->post('dateRangeValueStart');
			$end_date = $this->input->post('dateRangeValueEnd');
			$date_range_value = date("F j, Y", strtotime($start_date)) . ' - ' . date("F j, Y", strtotime($end_date));

			$depamt1 = $this->input->post('depamt1');
			$depamt2 = $this->input->post('depamt2');
			$widamt1 = $this->input->post('widamt1');
			$widamt2 = $this->input->post('widamt2');

			$status = $this->input->post('status');
			$playerlevel = $this->input->post('playerlevel');
			$username = $this->input->post('username');

			/*echo $start_date; echo "<br/>";
				echo $end_date; echo "<br/>";
				echo $date_range_value; echo "<br/>";
			*/

			$this->session->set_userdata(array(
				'period' => $period,
				'start_date' => $start_date,
				'end_date' => $end_date,
				'date_range_value' => $date_range_value,
				'depamt1' => $depamt1,
				'depamt2' => $depamt2,
				'widamt1' => $widamt1,
				'widamt2' => $widamt2,
				'report_player_status' => $status,
				'report_player_level' => $playerlevel,
				'report_player_username' => $username,
			));

			if ($period == "daily") {
				$this->viewPlayerReportDaily($start_date, $end_date);
			} elseif ($period == "weekly") {
				$this->viewPlayerReportWeekly($start_date, $end_date);
			} elseif ($period == "monthly") {
				$this->viewPlayerReportMonthly($start_date, $end_date);
			} elseif ($period == "yearly") {
				$this->viewPlayerReportYearly($start_date, $end_date);
			} else {
				$this->viewPlayerReportToday($start_date);
			}
		}
	}

	/**
	 * detail: player report today page
	 *
	 * @param string $date
	 * @return load template
	 */
	public function viewPlayerReportToday($date) {
		if (!$this->permissions->checkPermissions('player_report')) {
			$this->error_access();
		} else {
			$this->loadTemplate('Player Report', '', '', 'report');

			//export report permission checking
			if (!$this->permissions->checkPermissions('export_report')) {
				$data['export_report_permission'] = FALSE;
			} else {
				$data['export_report_permission'] = TRUE;
			}

			if ($date != null) {
				$date = urldecode($date);
				$start_date = $date . " 00:00:00";
				$end_date = $date . " 23:59:59";

				$data['player_report'] = $this->report_functions->getPlayerReportToday($start_date, $end_date);
			} else {
				$data['player_report'] = $this->report_functions->getPlayerReport();
			}
			$this->load->model('affiliatemodel');
			$data['allLevels'] = $this->player_manager->getAllPlayerLevels();
			$data['tags'] = $this->affiliatemodel->getActiveTags();

			$this->template->write_view('main_content', 'report_management/player_report/view_player_report', $data);
			$this->template->render();
		}
	}

	/**
	 * detail: player report daily page
	 *
	 * @param string $start_date
	 * @param string $end_date
	 * @return load template
	 */
	public function viewPlayerReportDaily($start_date, $end_date) {
		if (!$this->permissions->checkPermissions('player_report')) {
			$this->error_access();
		} else {
			$this->loadTemplate('Player Report', '', '', 'report');

			//export report permission checking
			if (!$this->permissions->checkPermissions('export_report')) {
				$data['export_report_permission'] = FALSE;
			} else {
				$data['export_report_permission'] = TRUE;
			}
			$this->load->model('affiliatemodel');
			$data['player_report'] = $this->report_functions->getPlayerReportDaily($start_date, $end_date);
			$data['allLevels'] = $this->player_manager->getAllPlayerLevels();
			$data['tags'] = $this->affiliatemodel->getActiveTags();

			$this->template->write_view('main_content', 'report_management/player_report/view_player_report_daily', $data);
			$this->template->render();
		}
	}

	/**
	 * detail: player report weekly page
	 *
	 * @param string $start_date
	 * @param string $end_date
	 * @return load template
	 */
	public function viewPlayerReportWeekly($start_date, $end_date) {
		if (!$this->permissions->checkPermissions('player_report')) {
			$this->error_access();
		} else {
			$this->loadTemplate('Player Report', '', '', 'report');

			//export report permission checking
			if (!$this->permissions->checkPermissions('export_report')) {
				$data['export_report_permission'] = FALSE;
			} else {
				$data['export_report_permission'] = TRUE;
			}
			$this->load->model('affiliatemodel');
			$data['player_report'] = $this->report_functions->getPlayerReportWeekly($start_date, $end_date);
			$data['allLevels'] = $this->player_manager->getAllPlayerLevels();
			$data['tags'] = $this->affiliatemodel->getActiveTags();

			$this->template->write_view('main_content', 'report_management/player_report/view_player_report_weekly', $data);
			$this->template->render();
		}
	}

	/**
	 * detail: player report monthly page
	 *
	 * @param string $start_date
	 * @param string $end_date
	 * @return load template
	 */
	public function viewPlayerReportMonthly($start_date, $end_date) {
		if (!$this->permissions->checkPermissions('player_report')) {
			$this->error_access();
		} else {
			$this->loadTemplate('Player Report', '', '', 'report');

			//export report permission checking
			if (!$this->permissions->checkPermissions('export_report')) {
				$data['export_report_permission'] = FALSE;
			} else {
				$data['export_report_permission'] = TRUE;
			}
			$this->load->model('affiliatemodel');
			$data['player_report'] = $this->report_functions->getPlayerReportMonthly($start_date, $end_date);
			$data['allLevels'] = $this->player_manager->getAllPlayerLevels();
			$data['tags'] = $this->affiliatemodel->getActiveTags();

			$this->template->write_view('main_content', 'report_management/player_report/view_player_report_monthly', $data);
			$this->template->render();
		}
	}

	/**
	 * detail: player report yearly page
	 *
	 * @param string $start_date
	 * @param string $end_date
	 * @return load template
	 */
	public function viewPlayerReportYearly($start_date, $end_date) {
		if (!$this->permissions->checkPermissions('player_report')) {
			$this->error_access();
		} else {
			$this->loadTemplate('Player Report', '', '', 'report');

			//export report permission checking
			if (!$this->permissions->checkPermissions('export_report')) {
				$data['export_report_permission'] = FALSE;
			} else {
				$data['export_report_permission'] = TRUE;
			}
			$this->load->model('affiliatemodel');
			$data['player_report'] = $this->report_functions->getPlayerReportYearly($start_date, $end_date);
			$data['allLevels'] = $this->player_manager->getAllPlayerLevels();
			$data['tags'] = $this->affiliatemodel->getActiveTags();

			$this->template->write_view('main_content', 'report_management/player_report/view_player_report_yearly', $data);
			$this->template->render();
		}
	}

	/**
	 * detail: player report registered player today page
	 *
	 * @param string $date
	 * @return load template
	 */
	public function viewRegisteredPlayerToday($date) {
		if (!$this->permissions->checkPermissions('player_report')) {
			$this->error_access();
		} else {
			$this->loadTemplate('Player Report', '', '', 'report');

			//export report permission checking
			if (!$this->permissions->checkPermissions('export_report')) {
				$data['export_report_permission'] = FALSE;
			} else {
				$data['export_report_permission'] = TRUE;
			}

			$date = urldecode($date);
			$start_date = $date . " 00:00:00";
			$end_date = $date . " 23:59:59";

			$this->load->model('affiliatemodel');
			$data['player_report'] = $this->report_functions->getRegisteredPlayerToday($start_date, $end_date);
			$data['allLevels'] = $this->player_manager->getAllPlayerLevels();
			$data['tags'] = $this->affiliatemodel->getActiveTags();

			$this->template->write_view('main_content', 'report_management/player_report/view_player_report', $data);
			$this->template->render();
		}
	}

	/**
	 * detail: export player report to excel
	 *
	 * @return  excel format
	 */
	public function exportPlayerReportToExcel() {
		$data = array(
			'username' => $this->authentication->getUsername(),
			'management' => 'Report Management',
			'userRole' => $this->rolesfunctions->getRoleByUserId($this->authentication->getUserId())['roleName'],
			'action' => 'Exported Player Report List',
			'description' => "User " . $this->authentication->getUsername() . " exported logs report list.",
			'logDate' => date("Y-m-d H:i:s"),
			'status' => 0,
		);
		$this->report_functions->recordAction($data);

		$result = $this->report_functions->getPlayerReportListToExport();
		/*var_dump($result);exit();*/
		$this->excel->to_excel($result, 'playerreportlist-excel');
	}

	/**
	 * detail: view promotion report
	 *
	 * @return load template
	 */
	public function viewPromotionReport() {
		if (!$this->permissions->checkPermissions('promotion_report')) {
			$this->error_access();
		} else {
			$this->load->model(array('promorules', 'player_promo', 'group_level', 'player_model'));
			$data['vipgrouplist'] = $this->group_level->getVipGroupList();
			$data['vipGroupListWithLevel'] = $this->player_model->getAllPlayerLevels();;
			$data['allPromo'] = $this->promorules->getAllPromorulesList();
			$data['allPromoTypes'] = $this->promorules->getAllPromoTypeList();
			//export report permission checking
			if (!$this->permissions->checkPermissions('export_promotion_report')) {
				$data['export_report_permission'] = FALSE;
			} else {
				$data['export_report_permission'] = TRUE;
			}

			$start_today = date("Y-m-d") . ' 00:00:00';
			$end_today = date("Y-m-d") . ' 23:59:59';
			$search_reg_date_default = 'off'; // Default: a. enable checkbox.

			$data['conditions'] = $this->safeLoadParams(array(
				'byUsername' => '',
				'byPlayerLevel' => '',
				'byPromotionType' => '',
				'byPromotionId' => '',
				'byBonusAmountLessThan' => '',
				'byBonusAmountGreaterThan' => '',
				'byPromotionStatus' => '',
				'tag_list' => array(),
				'byBonusPeriodJoinedFrom' => $this->utils->getTodayForMysql(),
				'byBonusPeriodJoinedTo' => $this->utils->getTodayForMysql(),
				'registration_date_from' => $start_today,
				'registration_date_to' => $end_today,
				'search_reg_date' => $search_reg_date_default,

			));

			$data['conditions']['enableDate'] = $this->safeGetParam('enableDate', true, true);
            $data['tags'] = $this->player->getAllTagsOnly();
            $data['selected_tags'] = $this->input->get_post('tag_list');
            $data['player_tags'] = $this->player->getAllTagsOnly();
			// $this->utils->debug_log($data['conditions']);

			$enable_freeze_top_in_list = $this->utils->_getEnableFreezeTopWithMethod(__METHOD__, $this->config->item('enable_freeze_top_method_list'));
			$data['enable_freeze_top_in_list'] = $enable_freeze_top_in_list;

			//var_dump($data['allPromo']);exit();
			$this->loadTemplate(lang('report.s02'), '', '', 'report');
            $this->template->add_js('resources/third_party/bootstrap-multiselect-master/dist/js/bootstrap-multiselect.js');
			$this->template->add_js($this->utils->thirdpartyUrl('bootstrap-switch/3.3.4/js/bootstrap-switch.min.js'));
			$this->template->add_css($this->utils->thirdpartyUrl('bootstrap-switch/3.3.4/css/bootstrap3/bootstrap-switch.min.css'));
            $this->template->add_css('resources/third_party/bootstrap-multiselect-master/dist/css/bootstrap-multiselect.css');
			$this->template->write_view('main_content', 'report_management/view_promotion_report', $data);
			$this->template->render();
		}
	}

	/**
	 * detail: view promotion pages
	 *
	 * @param string $segment
	 * @return load template
	 */
	public function get_promotion_pages($segment) {
		$sortBy['sortByUsername'] = $this->input->post('sortByUsername');
		$sortBy['sortByPlayerLevel'] = $this->input->post('sortByPlayerLevel');
		$sortBy['sortByBonusAmountLessThan'] = $this->input->post('sortByBonusAmountLessThan');
		$sortBy['sortByBonusAmountGreaterThan'] = $this->input->post('sortByBonusAmountGreaterThan');
		$sortBy['sortByPromoStatus'] = $this->input->post('sortByPromoStatus');
		$sortBy['sortByPromotionType'] = $this->input->post('sortByPromotionType');

		$sortBy['orderByReport'] = $this->input->post('orderByReport');
		$sortBy['sortBySortby'] = $this->input->post('sortBySortby');
		$sortBy['sortByItemCnt'] = $this->input->post('sortByItemCnt');
		$sortBy['sortByItemCnt'] == '' ? $sortBy['sortByItemCnt'] = 5 : '';
		$sortBy['sortByBonusPeriodJoinedFrom'] = $this->input->post('sortByBonusPeriodJoinedFrom');
		$sortBy['sortByBonusPeriodJoinedTo'] = $this->input->post('sortByBonusPeriodJoinedTo');

		$this->session->set_userdata('sortByUsername', $sortBy['sortByUsername']);
		$this->session->set_userdata('sortByPlayerLevel', $sortBy['sortByPlayerLevel']);
		$this->session->set_userdata('sortByBonusAmountLessThan', $sortBy['sortByBonusAmountLessThan']);
		$this->session->set_userdata('sortByBonusAmountGreaterThan', $sortBy['sortByBonusAmountGreaterThan']);
		$this->session->set_userdata('sortByPromoStatus', $sortBy['sortByPromoStatus']);
		$this->session->set_userdata('sortByPromotionType', $sortBy['sortByPromotionType']);

		$this->session->set_userdata('orderByReport', $sortBy['orderByReport']);
		$this->session->set_userdata('sortBySortby', $sortBy['sortBySortby']);
		$this->session->set_userdata('sortByItemCnt', $sortBy['sortByItemCnt']);

		$this->session->set_userdata('sortByBonusPeriodJoinedFrom', $sortBy['sortByBonusPeriodJoinedFrom']);
		$this->session->set_userdata('sortByBonusPeriodJoinedTo', $sortBy['sortByBonusPeriodJoinedTo']);

		$data['count_all'] = count($this->report_functions->getPromotionReport(null, null, null));
		$config['base_url'] = "javascript:get_promotion_pages(";
		$config['total_rows'] = $data['count_all'];
		$config['per_page'] = $sortBy['sortByItemCnt'];
		$config['num_links'] = '1';

		$config['first_tag_open'] = '<li>';
		$config['last_tag_open'] = '<li>';
		$config['next_tag_open'] = '<li>';
		$config['prev_tag_open'] = '<li>';
		$config['num_tag_open'] = '<li>';

		$config['first_tag_close'] = '</li>';
		$config['last_tag_close'] = '</li>';
		$config['next_tag_close'] = '</li>';
		$config['prev_tag_close'] = '</li>';
		$config['num_tag_close'] = '</li>';

		$config['cur_tag_open'] = "<li><span><b>";
		$config['cur_tag_close'] = "</b></span></li>";

		$this->pagination->initialize($config);

		$this->load->model(['group_level']);

		$data['total_pages'] = ceil($data['count_all'] / $config['per_page']);
		$data['promoReportData'] = $this->report_functions->getPromotionReport($sortBy, null, $segment);

		$data['vipgrouplist'] = $this->group_level->getVipGroupList();
		$data['allPromo'] = $this->depositpromo_manager->getAllDepositPromoName();

		//export report permission checking
		// OGP-10782 This is not being used anymore.
		// if (!$this->permissions->checkPermissions('export_report')) {
		// 	$data['export_report_permission'] = FALSE;
		// } else {
		// 	$data['export_report_permission'] = TRUE;
		// }

		$this->load->view('report_management/ajax_promotion_report', $data);
	}

	/**
	 * detail: export promotion report to excel
	 *
	 * @return  excel format
	 */
	public function exportPromotionReportToExcel() {
		$data = array(
			'username' => $this->authentication->getUsername(),
			'management' => 'Report Management',
			'userRole' => $this->rolesfunctions->getRoleByUserId($this->authentication->getUserId())['roleName'],
			'action' => 'Exported Promotion Report List',
			'description' => "User " . $this->authentication->getUsername() . " exported promotion report list.",
			'logDate' => date("Y-m-d H:i:s"),
			'status' => 0,
		);

		$this->report_functions->recordAction($data);

		$result = $this->report_functions->getPromotionReportListToExport();
		//var_dump($result);exit();
		//$this->excel->to_excel($result, 'promotionreportlist-excel');
		$d = new DateTime();
		$this->utils->create_excel($result, 'promotionreportlist_' . $d->format('Y_m_d_H_i_s') . '_' . rand(1, 999));

	}

	/**
	 * detail: view payment report
	 *
	 * @return load template
	 */
	public function viewPaymentReport() {
		if (!$this->permissions->checkPermissions('payment_report')) {
			return $this->error_access();
		}

		$this->load->model(array('group_level', 'transactions'));

		$data['conditions'] = $this->safeLoadParams(array(
			'by_date_from' => $this->utils->get7DaysAgoForMysql(),
			'by_date_to' => $this->utils->getTodayForMysql(),
			'by_username' => '',
			'by_player_level' => '',
			'by_transaction_type' => '',
			'by_amount_greater_than' => '',
			'by_amount_less_than' => '',
			'group_by' => '',
			'enable_date' => false,
			'search_by' => 1,
            'include_all_downlines' => '',
            'affiliate_username' => '',
            'agent_name' => '',
            'referrer_username' => '' ,
            'admin_username' => '' ,
		));

		$data['vipgrouplist'] = $this->group_level->getGroupLevelListKV();
        $data['vipgrouplist'][''] = lang('lang.selectall');
		$data['trans_list'] = array(
			'' => lang('lang.selectall'),
			Transactions::DEPOSIT => lang('Deposit'),
			Transactions::WITHDRAWAL => lang('Withdraw'),
			-1 => lang('Bonus'),
			Transactions::AUTO_ADD_CASHBACK_TO_BALANCE => lang('Cashback'),
		);
		if (!$this->permissions->checkPermissions('export_payment_report')) {
			$data['export_report_permission'] = FALSE;
		} else {
			$data['export_report_permission'] = TRUE;
		}

		$data['group_by_list'] = array(
			''			=> lang('pay_report.by_coll_account'),
			'by_player'	=> lang('pay_report.by_player'),
			'by_level'	=> lang('pay_report.by_player_level'),
			'by_aff'	=> lang('pay_report.by_affiliate'),
			'by_agency'	=> lang('pay_report.by_agency'),
			'by_ref'	=> lang('pay_report.by_referrer'),
			'by_admuser'=> lang('pay_report.by_admin_user'),
		);

		/**
		 * Hide unwanted columns by js for html output
		 * columns: (as of OGP-11982)
		 * 0 - date
		 * 1 - player-username
		 * 2 - aff-user
		 * 3 - agent-user
		 * 4 - refer-user
		 * 5 - adm-user
		 * 6 - group-level
		 * 7 - pay-type (coll-account)
		 * 8 - promo-cat
		 * 9 - tx-type
		 * 10 - amount
		 */

		$hide_cols = [ 1, 2, 3, 6 ];
		switch ($data['conditions']['group_by']) {
			case 'by_player':
				$hide_cols = [ 5, 6, 7, 8 ];
				break;
			case 'by_level':
				$hide_cols = [ 1, 2, 3, 4, 5 ,7, 8 ];
				break;
			case 'by_admuser':
				$hide_cols = [ 1, 2, 3, 4, 6, 7, 8 ];
				break;
			case 'by_aff' :
				$hide_cols = [ 1, 3, 4, 5, 6, 7, 8 ];
				break;
			case 'by_agency' :
				$hide_cols = [ 1, 2, 4, 5, 6, 7, 8 ];
				break;
			case 'by_ref' :
				$hide_cols = [ 1, 2, 3, 5, 6, 7, 8 ];
				break;
			case 'by_payment_type':
			default:
				$hide_cols = [ 1, 2, 3, 4, 5, 6, 8 ];
				break;
		}

		$data['hide_cols'] = $hide_cols;

		$enable_freeze_top_in_list = $this->utils->_getEnableFreezeTopWithMethod(__METHOD__, $this->config->item('enable_freeze_top_method_list'));
		$data['enable_freeze_top_in_list'] = $enable_freeze_top_in_list;

		$this->loadTemplate(lang('Payment Report'), '', '', 'report');
		$this->template->add_js('resources/js/bootstrap-switch.min.js');
		$this->template->add_css('resources/css/bootstrap-switch.min.css');
		$this->template->write_view('main_content', 'report_management/view_payment_report', $data);
		$this->template->render();

	}

    /**
     * detail: view payment report
     *
     * @return load template
     */
    public function viewPaymentStatusHistoryReport() {
        if (!$this->permissions->checkPermissions('payment_status_history_report')) {
            return $this->error_access();
        }

        $this->load->model(array('group_level', 'transactions'));

        $data['conditions'] = $this->safeLoadParams(array(
            'by_date_from' => $this->utils->get7DaysAgoForMysql(),
            'by_date_to' => $this->utils->getTodayForMysql(),
            'by_accountname' => '',
            'by_success_rate_greater_than' => '',
            'by_success_rate_less_than' => '',
            'by_failed_rate_greater_than' => '',
            'by_failed_rate_less_than' => '',
            'enable_date' => true
        ));

        if (!$this->permissions->checkPermissions('export_payment_status_history_report')) {
            $data['export_report_permission'] = FALSE;
        } else {
            $data['export_report_permission'] = TRUE;
        }

		$data['enable_freeze_top_in_list'] = $this->utils->_getEnableFreezeTopWithMethod(__METHOD__, $this->config->item('enable_freeze_top_method_list'));

        $this->loadTemplate(lang('report.s10'), '', '', 'report');
        $this->template->add_js('resources/js/bootstrap-switch.min.js');
        $this->template->add_css('resources/css/bootstrap-switch.min.css');
        $this->template->write_view('main_content', 'report_management/view_payment_status_history_report', $data);
        $this->template->render();

    }

	/**
	 * The Conversion Rate Report Page
	 * location, admin > report > conversion_rate_report
	 *
	 * @return void
	 */
	public function conversion_rate_report(){
		if (!$this->permissions->checkPermissions('conversion_rate_report')) {
            return $this->error_access();
		}
		$data = array();
		if (!$this->permissions->checkPermissions('export_conversion_rate_report')) {
            $data['export_report_permission'] = FALSE;
        } else {
            $data['export_report_permission'] = TRUE;
		}
		$SummaryBy = array();
		$SummaryBy['All'] = 'All';
		$SummaryBy['DirectPlayer'] = 'DirectPlayer';
		$SummaryBy['Affiliate'] = 'Affiliate';
		$SummaryBy['Agency'] = 'Agency';
		$SummaryBy['Referrer'] = 'Referrer';
		$SummaryBy['ReferredAffiliate'] = 'Referredaffiliate';
		$SummaryBy['ReferredAgent'] = 'ReferredAgent';

		$conditions = array();

		$data['SummaryBy'] = $SummaryBy;
		$data['conditions'] = $this->safeLoadParams(array(
			// 'byUsername' => '',
			// 'byPlayerLevel' => '',
			// 'byPromotionType' => '',
			// 'byPromotionId' => '',
			// 'byBonusAmountLessThan' => '',
			// 'byBonusAmountGreaterThan' => '',
			// 'byPromotionStatus' => '',
			// 'byBonusPeriodJoinedFrom' => $this->utils->getTodayForMysql(),
			// 'byBonusPeriodJoinedTo' => $this->utils->getTodayForMysql(),

			'SummaryBy' => $SummaryBy['All'],
			'search_first_deposit_date' => '',

			'search_first_deposit_date_switch' => '',

			'registration_date_to' => '',
			'registration_date_from' => '',
			'first_deposit_date_to' => '',
			'first_deposit_date_from' => '',

			/// optional
			'affiliate_username' => '',
			'agency_username' => '',
			'referrers_username' => '',
		));
		$data['conditions']['enableDate'] = $this->safeGetParam('enableDate', true, true);

		$data['enable_freeze_top_in_list'] = $this->utils->_getEnableFreezeTopWithMethod(__METHOD__, $this->config->item('enable_freeze_top_method_list'));

		//check cronjob
		$checkCron = ['cronjob_sync_newplayer_into_player_relay', 'cronjob_sync_exists_player_in_player_relay'];
		foreach ($checkCron as $cron) {
			$data[$cron] = $this->operatorglobalsettings->getSettingBooleanValue($cron);
		}
		$this->utils->debug_log('=======conversion rate data=======',$data);
		
		$this->loadTemplate(lang('Conversion Rate Report'), '', '', 'report');
        // $this->template->add_js('resources/js/bootstrap-switch.min.js');
        // $this->template->add_css('resources/css/bootstrap-switch.min.css');
        $this->template->write_view('main_content', 'report_management/view_conversion_rate_report', $data);
        $this->template->render();
	} // EOF conversion_rate_report

    /**
	 * detail: view payment pages
	 *
	 * @param string $segment
	 * @return load template
	 */
	public function get_payment_pages($segment) {
		$sortBy['paymentReportsortByUsername'] = $this->input->post('paymentReportsortByUsername');
		$sortBy['paymentReportSortByPlayerLevel'] = $this->input->post('paymentReportSortByPlayerLevel');
		$sortBy['paymentReportSortByTransaction'] = $this->input->post('paymentReportSortByTransaction');
		$sortBy['paymentReportSortByTransactionStatus'] = $this->input->post('paymentReportSortByTransactionStatus');
		$sortBy['paymentReportSortByDWAmountLessThan'] = $this->input->post('paymentReportSortByDWAmountLessThan');
		$sortBy['paymentReportSortByDWAmountGreaterThan'] = $this->input->post('paymentReportSortByDWAmountGreaterThan');
		$sortBy['paymentReportOrderByReport'] = $this->input->post('paymentReportOrderByReport');
		$sortBy['paymentReportSortBySortby'] = $this->input->post('paymentReportSortBySortby');
		$sortBy['paymentReportSortByItemCnt'] = $this->input->post('paymentReportSortByItemCnt');
		$sortBy['paymentReportSortByItemCnt'] == '' ? $sortBy['paymentReportSortByItemCnt'] = 5 : '';
		$sortBy['paymentReportSortByDateRangeValueStart'] = $this->input->post('dateRangeValueStart');
		$sortBy['paymentReportSortByDateRangeValueEnd'] = $this->input->post('dateRangeValueEnd');
		$sortBy['paymentReportSortByOnly1stDeposit'] = $this->input->post('only1stDeposit');

		$this->session->set_userdata('paymentReportsortByUsername', $sortBy['paymentReportsortByUsername']);
		$this->session->set_userdata('paymentReportSortByPlayerLevel', $sortBy['paymentReportSortByPlayerLevel']);
		$this->session->set_userdata('paymentReportSortByTransaction', $sortBy['paymentReportSortByTransaction']);
		$this->session->set_userdata('paymentReportSortByTransactionStatus', $sortBy['paymentReportSortByTransactionStatus']);
		$this->session->set_userdata('paymentReportSortByDWAmountLessThan', $sortBy['paymentReportSortByDWAmountLessThan']);
		$this->session->set_userdata('paymentReportSortByDWAmountGreaterThan', $sortBy['paymentReportSortByDWAmountGreaterThan']);
		$this->session->set_userdata('paymentReportOrderByReport', $sortBy['paymentReportOrderByReport']);
		$this->session->set_userdata('paymentReportSortBySortby', $sortBy['paymentReportSortBySortby']);
		$this->session->set_userdata('paymentReportSortByItemCnt', $sortBy['paymentReportSortByItemCnt']);

		$this->session->set_userdata('dateRangeValueStart', $sortBy['paymentReportSortByDateRangeValueStart']);
		$this->session->set_userdata('dateRangeValueEnd', $sortBy['paymentReportSortByDateRangeValueEnd']);

		$dateRangeValue = $this->input->post('dateRangeValue');
		$this->session->set_userdata('dateRangeValue', $dateRangeValue);

		$data['count_all'] = count($this->report_functions->getPaymentsReport(null, null, null));
		$config['base_url'] = "javascript:get_payment_pages(";
		$config['total_rows'] = $data['count_all'];
		$config['per_page'] = $sortBy['paymentReportSortByItemCnt'];
		$config['num_links'] = '1';

		$config['first_tag_open'] = '<li>';
		$config['last_tag_open'] = '<li>';
		$config['next_tag_open'] = '<li>';
		$config['prev_tag_open'] = '<li>';
		$config['num_tag_open'] = '<li>';

		$config['first_tag_close'] = '</li>';
		$config['last_tag_close'] = '</li>';
		$config['next_tag_close'] = '</li>';
		$config['prev_tag_close'] = '</li>';
		$config['num_tag_close'] = '</li>';

		$config['cur_tag_open'] = "<li><span><b>";
		$config['cur_tag_close'] = "</b></span></li>";

		$this->pagination->initialize($config);

		$data['total_pages'] = ceil($data['count_all'] / $config['per_page']);
		$data['paymentReportData'] = $this->report_functions->getPaymentsReport($sortBy, null, $segment);

		//export report permission checking
		// OGP-10782 This is not being used anymore.
		// if (!$this->permissions->checkPermissions('export_report')) {
		// 	$data['export_report_permission'] = FALSE;
		// } else {
		// 	$data['export_report_permission'] = TRUE;
		// }

		$this->load->model(['group_level']);

		$data['vipgrouplist'] = $this->group_level->getVipGroupList();

		$this->load->view('report_management/ajax_payment_report', $data);
	}

	/**
	 * detail: export payment report to excel
	 *
	 * @return  excel format
	 */
	public function exportPaymentReportToExcel() {
		$data = array(
			'username' => $this->authentication->getUsername(),
			'management' => 'Report Management',
			'userRole' => $this->rolesfunctions->getRoleByUserId($this->authentication->getUserId())['roleName'],
			'action' => 'Exported Payment Report List',
			'description' => "User " . $this->authentication->getUsername() . " exported payment report list.",
			'logDate' => date("Y-m-d H:i:s"),
			'status' => 0,
		);

		$this->report_functions->recordAction($data);

		$result = $this->report_functions->getPaymentReportListToExport();
		//var_dump($result);exit();
		//$this->excel->to_excel($result, 'paymentreportlist-excel');
		$d = new DateTime();
		$this->utils->create_excel($result, 'paymentreportlist_' . $d->format('Y_m_d_H_i_s') . '_' . rand(1, 999));
	}

	/**
	 * detail: view monthly earnings
	 *
	 * @return load template
	 */
	public function viewEarningsReport() {
		//Load in th controller
		$this->gcharts->load('LineChart');

		$this->gcharts->DataTable('Earnings')
			->addColumn('date', 'Dates', 'dates')
			->addColumn('number', 'AG', 'ag_game')
			->addColumn('number', 'EA', 'ea_game')
			->addColumn('number', 'PT', 'pt_game')
			->addColumn('number', 'OPUS', 'opus_game');

		for ($a = 0; $a < 12; $a++) {
			$data = array(
				new jsDate(2014, $a, 1), //Date object
				//new jsDate($a),
				rand(100000, 1000000), //Line 1's data
				rand(100000, 1000000), //Line 2's data
				rand(100000, 1000000), //Line 3's data
				rand(100000, 1000000), //Line 4's data
			);

			$this->gcharts->DataTable('Earnings')->addRow($data);
		}

		//Either Chain functions together to set configuration options
		$titleStyle = $this->gcharts->textStyle()
			->color('#333')
			->fontName('Georgia')
			->fontSize(18);

		$legendStyle = $this->gcharts->textStyle()
			->color('#aaa')
			->fontName('Arial')
			->fontSize(20);

		$legend = $this->gcharts->legend()
			->position('bottom')
			->alignment('start')
			->textStyle($legendStyle);

		//Or pass an array with the configuration options into the function
		$tooltipStyle = new textStyle(array(
			'color' => '#333',
			'fontName' => 'Courier New',
			'fontSize' => 10,
		));

		$tooltip = new tooltip(array(
			'showColorCode' => TRUE,
			'textStyle' => $tooltipStyle,
		));

		$config = array(
			'backgroundColor' => new backgroundColor(array(
				'stroke' => '#fff',
				'strokeWidth' => 2,
				'fill' => '#fff',
			)),
			'chartArea' => new chartArea(array(
				'left' => 100,
				'top' => 75,
				'width' => '85%',
				'height' => '55%',
			)),
			'titleTextStyle' => $titleStyle,
			'legend' => $legend,
			'tooltip' => $tooltip,
			'title' => '2014 Monthly Earnings',
			'titlePosition' => 'out',
			'curveType' => 'function',
			'width' => 915,
			'height' => 400,
			'pointSize' => 3,
			'lineWidth' => 1,
			'colors' => array('#1166a6', '#ce362d', '#d9811f', '9a99ff'),
			'hAxis' => new hAxis(array(
				'baselineColor' => '#fc32b0',
				'gridlines' => array(
					'color' => '#78ae3e',
					'count' => 12,
				),
				'minorGridlines' => array(
					'color' => '#fff',
					'count' => 2,
				),
				'textPosition' => 'out',
				'textStyle' => new textStyle(array(
					'color' => '#C42B5F',
					'fontName' => 'Tahoma',
					'fontSize' => 10,
				)),
				'slantedText' => TRUE,
				'slantedTextAngle' => 30,
				'title' => 'Months',
				'titleTextStyle' => new textStyle(array(
					'color' => '#993364',
					'fontName' => 'Arial',
					'fontSize' => 14,
				)),
				'maxAlternation' => 6,
				'maxTextLines' => 2,
			)),
			'vAxis' => new vAxis(array(
				'baseline' => 0,
				'baselineColor' => '#CF3BBB',
				//'format' => '## hrs',
				'textPosition' => 'out',
				'textStyle' => new textStyle(array(
					'color' => '#d0352d',
					'fontName' => 'Arial Bold',
					'fontSize' => 10,
				)),
				'title' => 'Amount',
				'titleTextStyle' => new textStyle(array(
					'color' => '#d0352d',
					'fontName' => 'Verdana',
					'fontSize' => 14,
				)),
			)),
		);

		// Call the LineChart function with "Earnings" as the param to use that dataTable
		$this->gcharts->LineChart('Earnings')->setConfig($config);

		//----------------------------------------------------------------------------------------------
		$this->gcharts->load('ColumnChart');

		$this->gcharts->DataTable('Earnings-cc')
			->addColumn('date', 'Dates', 'dates')
			->addColumn('number', 'AG', 'ag_game')
			->addColumn('number', 'EA', 'ea_game')
			->addColumn('number', 'PT', 'pt_game')
			->addColumn('number', 'OPUS', 'opus_game');

		for ($a = 0; $a < 12; $a++) {
			$data = array(
				new jsDate(2014, $a, 1), //Date object
				//new jsDate($a),
				rand(100000, 1000000), //Line 1's data
				rand(100000, 1000000), //Line 2's data
				rand(100000, 1000000), //Line 3's data
				rand(100000, 1000000), //Line 4's data
			);

			$this->gcharts->DataTable('Earnings-cc')->addRow($data);
		}

		$legendStyle = $this->gcharts->textStyle()
			->color('#aaa')
			->fontName('Arial')
			->fontSize(14);

		$legend = $this->gcharts->legend()
			->position('right')
			->alignment('start')
			->textStyle($legendStyle);

		$config = array(
			'axisTitlesPosition' => 'out',
			'backgroundColor' => new backgroundColor(array(
				'stroke' => '#fff',
				'strokeWidth' => 2,
				'fill' => '#fff',
			)),
			'barGroupWidth' => '80%',
			'chartArea' => new chartArea(array(
				'left' => 100,
				'top' => 80,
				'width' => '80%',
				'height' => '60%',
			)),
			'titleTextStyle' => $titleStyle,
			'legend' => $legend,
			'tooltip' => $tooltip,
			'title' => '2014 Montly Earnings',
			'titlePosition' => 'out',
			'width' => 950,
			'height' => 400,
			'colors' => array('#1166a6', '#ce362d', '#d9811f', '9a99ff'),
			'hAxis' => new hAxis(array(
				'baselineColor' => '#BB99BB',
				'gridlines' => array(
					'color' => '#ABCDEF',
					'count' => 1,
				),
				'minorGridlines' => array(
					'color' => '#FEBCDA',
					'count' => 12,
				),
				'textPosition' => 'out',
				'textStyle' => new textStyle(array(
					'color' => '#C42B5F',
					'fontName' => 'Tahoma',
					'fontSize' => 10,
				)),
				'slantedText' => TRUE,
				'slantedTextAngle' => 30,
				'title' => 'Months',
				'titleTextStyle' => new textStyle(array(
					'color' => '#993364',
					'fontName' => 'Arial',
					'fontSize' => 14,
				)),
				'maxAlternation' => 2,
				'maxTextLines' => 10,
				'showTextEvery' => 1,
			)),
			'vAxis' => new vAxis(array(
				'baseline' => 0,
				'baselineColor' => '#CF3BBB',
				//'format' => '## hrs',
				'textPosition' => 'out',
				'textStyle' => new textStyle(array(
					'color' => '#d0352d',
					'fontName' => 'Arial Bold',
					'fontSize' => 10,
				)),
				'title' => 'Amount',
				'titleTextStyle' => new textStyle(array(
					'color' => '#d0352d',
					'fontName' => 'Verdana',
					'fontSize' => 14,
				)),
			)),
		);

		$this->gcharts->ColumnChart('Earnings-cc')->setConfig($config);
		//----------------------------------------------------------------------------------------

		$this->loadTemplate('Report Management', '', '', 'report');
		$this->template->write_view('main_content', 'report_management/view_earnings_report');
		$this->template->render();
	}

	/**
	 * detail: view daily report
	 *
	 * @return load template
	 */
	public function viewDailyReport() {
		$this->loadTemplate('Report Management', '', '', 'report');
		//export report permission checking
		if (!$this->permissions->checkPermissions('export_report')) {
			$data['export_report_permission'] = FALSE;
		} else {
			$data['export_report_permission'] = TRUE;
		}

		$signup_range = null;
		$period = null;

		if ($this->input->post('start_date') && $this->input->post('end_date') && $this->input->post('sign_time_period') == 'specify') {
			if ($this->input->post('start_date') < $this->input->post('end_date')) {
				$signup_range = "'" . $this->input->post('start_date') . "' AND '" . $this->input->post('end_date') . "'";
			} else {
				$message = lang('con.rpm02');
				$this->alertMessage(2, $message);
			}
		} else {
			$period = $this->input->post('sign_time_period');
		}

		$search = array(
			'sign_time_period' => $period,
			'signup_range' => $signup_range,
		);

		$data['count_all'] = count($this->report_functions->getSummaryReport($search, null, null));
		$config['base_url'] = "javascript:get_summary_pages(";
		$config['total_rows'] = $data['count_all'];
		$config['per_page'] = '5';
		$config['num_links'] = '1';

		$config['first_tag_open'] = '<li>';
		$config['last_tag_open'] = '<li>';
		$config['next_tag_open'] = '<li>';
		$config['prev_tag_open'] = '<li>';
		$config['num_tag_open'] = '<li>';

		$config['first_tag_close'] = '</li>';
		$config['last_tag_close'] = '</li>';
		$config['next_tag_close'] = '</li>';
		$config['prev_tag_close'] = '</li>';
		$config['num_tag_close'] = '</li>';

		$config['cur_tag_open'] = "<li><span><b>";
		$config['cur_tag_close'] = "</b></span></li>";

		$this->pagination->initialize($config);

		$data['summary_report'] = $this->report_functions->getSummaryReport($search, null, null);

		$this->template->write_view('main_content', 'report_management/view_summary_report', $data);
		$this->template->render();
	}

	/**
	 * detail: view summary report
	 *
	 * @param string @segment
	 * @return load template
	 */
	public function get_summary_pages($segment) {
		$signup_range = null;
		$period = null;

		if ($this->input->post('start_date') && $this->input->post('end_date') && $this->input->post('sign_time_period') == 'specify') {
			if ($this->input->post('start_date') < $this->input->post('end_date')) {
				$signup_range = "'" . $this->input->post('start_date') . "' AND '" . $this->input->post('end_date') . "'";
			} else {
				$message = lang('con.rpm02');
				$this->alertMessage(2, $message);
			}
		} else {
			$period = $this->input->post('sign_time_period');
		}

		$search = array(
			'sign_time_period' => $period,
			'signup_range' => $signup_range,
		);

		$data['count_all'] = count($this->report_functions->getSummaryReport($search, null, null));
		$config['base_url'] = "javascript:get_summary_pages(";
		$config['total_rows'] = $data['count_all'];
		$config['per_page'] = '5';
		$config['num_links'] = '1';

		$config['first_tag_open'] = '<li>';
		$config['last_tag_open'] = '<li>';
		$config['next_tag_open'] = '<li>';
		$config['prev_tag_open'] = '<li>';
		$config['num_tag_open'] = '<li>';

		$config['first_tag_close'] = '</li>';
		$config['last_tag_close'] = '</li>';
		$config['next_tag_close'] = '</li>';
		$config['prev_tag_close'] = '</li>';
		$config['num_tag_close'] = '</li>';

		$config['cur_tag_open'] = "<li><span><b>";
		$config['cur_tag_close'] = "</b></span></li>";

		$this->pagination->initialize($config);

		$data['summary_report'] = $this->report_functions->getSummaryReport($search, null, $segment);

		$this->load->view('report_management/ajax_summary_report', $data);
	}

	/**
	 * detail: export summary report to excel
	 *
	 * @return  excel format
	 */
	public function exportDailyReportToExcel() {
		$data = array(
			'username' => $this->authentication->getUsername(),
			'management' => 'Report Management',
			'userRole' => $this->rolesfunctions->getRoleByUserId($this->authentication->getUserId())['roleName'],
			'action' => 'Exported Summary Report List',
			'description' => "User " . $this->authentication->getUsername() . " exported logs report list.",
			'logDate' => date("Y-m-d H:i:s"),
			'status' => 0,
		);
		$this->report_functions->recordAction($data);

		$result = $this->report_functions->getSummaryReportListToExport();
		//var_dump($result);exit();
		$this->excel->to_excel($result, 'dailyreportlist-excel');
	}

	/**
	 * view income report
	 *
	 * @return  array
	 */
	/*public function viewIncomeReport() {
		if(!$this->permissions->checkPermissions('income_report')){
		$this->error_access();
		} else {
		$this->loadTemplate('Income Report', '', '', 'report');

		//export report permission checking
		if(!$this->permissions->checkPermissions('export_report')){
		$data['export_report_permission'] = FALSE;
		} else {
		$data['export_report_permission'] = TRUE;
		}

		$this->session->unset_userdata(array(
		'period' => "",
		'start_date' => "",
		'end_date' => "",
		'date_range_value' => "",
		'depamt1' => "",
		'depamt2' => "",
		'widamt1' => "",
		'widamt2' => "",
		'report_player_status' => "",
		'report_player_level' => "",
		'report_player_username' => "",
		));

		$data['income_report'] = $this->report_functions->getIncomeReport();
		$data['allLevels'] = $this->player_manager->getAllPlayerLevels();

		$this->template->write_view('main_content', 'report_management/income_report/view_income_report', $data);
		$this->template->render();
		}
	*/

	/**
	 * detail: search income report
	 *
	 * @return  array
	 */
	public function searchIncomeReport() {
		if (!$this->permissions->checkPermissions('income_report')) {
			$this->error_access();
		} else {
			$period = $this->input->post('period');
			$start_date = $this->input->post('dateRangeValueStart');
			$end_date = $this->input->post('dateRangeValueEnd');
			$date_range_value = date("F j, Y", strtotime($start_date)) . ' - ' . date("F j, Y", strtotime($end_date));

			$depamt1 = $this->input->post('depamt1');
			$depamt2 = $this->input->post('depamt2');
			$widamt1 = $this->input->post('widamt1');
			$widamt2 = $this->input->post('widamt2');

			$status = $this->input->post('status');
			$playerlevel = $this->input->post('playerlevel');
			$username = $this->input->post('username');

			$this->session->set_userdata(array(
				'period' => $period,
				'start_date' => $start_date,
				'end_date' => $end_date,
				'date_range_value' => $date_range_value,
				'depamt1' => $depamt1,
				'depamt2' => $depamt2,
				'widamt1' => $widamt1,
				'widamt2' => $widamt2,
				'report_income_status' => $status,
				'report_income_level' => $playerlevel,
				'report_income_username' => $username,
			));

			if ($period == "daily") {
				$this->viewIncomeReportDaily($start_date, $end_date);
			} elseif ($period == "weekly") {
				$this->viewIncomeReportWeekly($start_date, $end_date);
			} elseif ($period == "monthly") {
				$this->viewIncomeReportMonthly($start_date, $end_date);
			} elseif ($period == "yearly") {
				$this->viewIncomeReportYearly($start_date, $end_date);
			} else {
				$this->viewIncomeReportToday($start_date);
			}
		}
	}

	/**
	 * detail: view the income report today page
	 *
	 * @return load template
	 */
	public function viewIncomeReportToday($date) {
		if (!$this->permissions->checkPermissions('income_report')) {
			$this->error_access();
		} else {
			$this->loadTemplate('Income Report', '', '', 'report');

			//export report permission checking
			if (!$this->permissions->checkPermissions('export_report')) {
				$data['export_report_permission'] = FALSE;
			} else {
				$data['export_report_permission'] = TRUE;
			}

			if ($date != null) {
				$date = urldecode($date);
				$start_date = $date . " 00:00:00";
				$end_date = $date . " 23:59:59";

				$data['income_report'] = $this->report_functions->getIncomeReportToday($start_date, $end_date);
			} else {
				$data['income_report'] = $this->report_functions->getIncomeReport();
			}

			$data['allLevels'] = $this->player_manager->getAllPlayerLevels();

			$this->template->write_view('main_content', 'report_management/income_report/view_income_report', $data);
			$this->template->render();
		}
	}

	/**
	 * detail: view income report daily page
	 *
	 * @return load template
	 */
	public function viewIncomeReportDaily($start_date, $end_date) {
		if (!$this->permissions->checkPermissions('income_report')) {
			$this->error_access();
		} else {
			$this->loadTemplate('Income Report', '', '', 'report');

			//export report permission checking
			if (!$this->permissions->checkPermissions('export_report')) {
				$data['export_report_permission'] = FALSE;
			} else {
				$data['export_report_permission'] = TRUE;
			}

			$data['income_report'] = $this->report_functions->getIncomeReportDaily($start_date, $end_date);
			$data['allLevels'] = $this->player_manager->getAllPlayerLevels();

			$this->template->write_view('main_content', 'report_management/income_report/view_income_report_daily', $data);
			$this->template->render();
		}
	}

	/**
	 * detail: view income report weekly page
	 *
	 * @return load template
	 */
	public function viewIncomeReportWeekly($start_date, $end_date) {
		if (!$this->permissions->checkPermissions('income_report')) {
			$this->error_access();
		} else {
			$this->loadTemplate('Income Report', '', '', 'report');

			//export report permission checking
			if (!$this->permissions->checkPermissions('export_report')) {
				$data['export_report_permission'] = FALSE;
			} else {
				$data['export_report_permission'] = TRUE;
			}

			$data['income_report'] = $this->report_functions->getIncomeReportWeekly($start_date, $end_date);
			$data['allLevels'] = $this->player_manager->getAllPlayerLevels();

			$this->template->write_view('main_content', 'report_management/income_report/view_income_report_weekly', $data);
			$this->template->render();
		}
	}

	/**
	 * detail: view income report monthly page
	 *
	 * @return load template
	 */
	public function viewIncomeReportMonthly($start_date, $end_date) {
		if (!$this->permissions->checkPermissions('income_report')) {
			$this->error_access();
		} else {
			$this->loadTemplate('Income Report', '', '', 'report');

			//export report permission checking
			if (!$this->permissions->checkPermissions('export_report')) {
				$data['export_report_permission'] = FALSE;
			} else {
				$data['export_report_permission'] = TRUE;
			}

			$data['income_report'] = $this->report_functions->getIncomeReportMonthly($start_date, $end_date);
			$data['allLevels'] = $this->player_manager->getAllPlayerLevels();

			$this->template->write_view('main_content', 'report_management/income_report/view_income_report_monthly', $data);
			$this->template->render();
		}
	}

	/**
	 * detail: view income report yearly page
	 *
	 * @return load template
	 */
	public function viewIncomeReportYearly($start_date, $end_date) {
		if (!$this->permissions->checkPermissions('income_report')) {
			$this->error_access();
		} else {
			$this->loadTemplate('Income Report', '', '', 'report');

			//export report permission checking
			if (!$this->permissions->checkPermissions('export_report')) {
				$data['export_report_permission'] = FALSE;
			} else {
				$data['export_report_permission'] = TRUE;
			}

			$data['income_report'] = $this->report_functions->getIncomeReportYearly($start_date, $end_date);
			$data['allLevels'] = $this->player_manager->getAllPlayerLevels();

			$this->template->write_view('main_content', 'report_management/income_report/view_income_report_yearly', $data);
			$this->template->render();
		}
	}

	/**
	 * deail: export income report to excel
	 *
	 * @return  excel format
	 */
	public function exportIncomeReportToExcel() {
		$data = array(
			'username' => $this->authentication->getUsername(),
			'management' => 'Report Management',
			'userRole' => $this->rolesfunctions->getRoleByUserId($this->authentication->getUserId())['roleName'],
			'action' => 'Exported Income Report List',
			'description' => "User " . $this->authentication->getUsername() . " exported logs report list.",
			'logDate' => date("Y-m-d H:i:s"),
			'status' => 0,
		);
		$this->report_functions->recordAction($data);

		$result = $this->report_functions->getIncomeReportListToExport();
		//var_dump($result);exit();
		$this->excel->to_excel($result, 'incomereportlist-excel');
	}

	/**
	 * detail: search games report
	 *
	 * @return  array
	 */
	public function searchGamesReport() {
		if (!$this->permissions->checkPermissions('game_report')) {
			$this->error_access();
		} else {
			$period = $this->input->post('period');
			$start_date = $this->input->post('dateRangeValueStart');
			$end_date = $this->input->post('dateRangeValueEnd');
			$date_range_value = date("F j, Y", strtotime($start_date)) . ' - ' . date("F j, Y", strtotime($end_date));

			$betamt1 = $this->input->post('betamt1');
			$betamt2 = $this->input->post('betamt2');

			$lossamt1 = $this->input->post('lossamt1');
			$lossamt2 = $this->input->post('lossamt2');

			$winamt1 = $this->input->post('winamt1');
			$winamt2 = $this->input->post('winamt2');

			$earnamt1 = $this->input->post('earnamt1');
			$earnamt2 = $this->input->post('earnamt2');

			$playerlevel = $this->input->post('playerlevel');
			$username = $this->input->post('username');

			$this->session->set_userdata(array(
				'period' => $period,
				'start_date' => $start_date,
				'end_date' => $end_date,
				'date_range_value' => $date_range_value,
				'betamt1' => $betamt1,
				'betamt2' => $betamt2,
				'lossamt1' => $lossamt1,
				'lossamt2' => $lossamt2,
				'winamt1' => $winamt1,
				'winamt2' => $winamt2,
				'earnamt1' => $earnamt1,
				'earnamt2' => $earnamt2,
				'report_game_level' => $playerlevel,
				'report_game_username' => $username,
			));

			if ($period == "daily") {
				$this->viewGamesReportDaily($start_date, $end_date);
			} elseif ($period == "weekly") {
				$this->viewGamesReportWeekly($start_date, $end_date);
			} elseif ($period == "monthly") {
				$this->viewGamesReportMonthly($start_date, $end_date);
			} elseif ($period == "yearly") {
				$this->viewGamesReportYearly($start_date, $end_date);
			} else {
				$this->viewGamesReportToday($start_date);
			}
		}
	}

	/**
	 * detail: games report today page
	 *
	 * @return load template
	 */
	public function viewGamesReportToday($date) {
		if (!$this->permissions->checkPermissions('game_report')) {
			$this->error_access();
		} else {
			$this->loadTemplate('Games Report', '', '', 'report');

			//export report permission checking
			if (!$this->permissions->checkPermissions('export_games_report')) {
				$data['export_report_permission'] = FALSE;
			} else {
				$data['export_report_permission'] = TRUE;
			}

			if ($date != null) {
				$date = urldecode($date);
				$start_date = $date . " 00:00:00";
				$end_date = $date . " 23:59:59";

				$data['games_report'] = $this->report_functions->getGamesReportToday($start_date, $end_date);
			} else {
				$data['games_report'] = $this->report_functions->getGamesReport();
			}

			$data['allLevels'] = $this->player_manager->getAllPlayerLevels();

			$this->template->write_view('main_content', 'report_management/games_report/view_games_report', $data);
			$this->template->render();
		}
	}

	/**
	 * detail: games report daily page
	 *
	 * @return load template
	 */
	public function viewGamesReportDaily($start_date, $end_date) {
		if (!$this->permissions->checkPermissions('game_report')) {
			$this->error_access();
		} else {
			$this->loadTemplate('Games Report', '', '', 'report');

			// $data['games_report'] = $this->report_functions->getGamesReport($search, null, null);

			//export report permission checking
			if (!$this->permissions->checkPermissions('export_games_report')) {
				$data['export_report_permission'] = FALSE;
			} else {
				$data['export_report_permission'] = TRUE;
			}

			$data['games_report'] = $this->report_functions->getGamesReportDaily($start_date, $end_date);
			$data['allLevels'] = $this->player_manager->getAllPlayerLevels();

			$this->template->write_view('main_content', 'report_management/games_report/view_games_report_daily', $data);
			$this->template->render();
		}
	}

	/**
	 * detail: games report weekly page
	 *
	 * @return load template
	 */
	public function viewGamesReportWeekly($start_date, $end_date) {
		if (!$this->permissions->checkPermissions('game_report')) {
			$this->error_access();
		} else {
			$this->loadTemplate('Games Report', '', '', 'report');

			//export report permission checking
			if (!$this->permissions->checkPermissions('export_games_report')) {
				$data['export_report_permission'] = FALSE;
			} else {
				$data['export_report_permission'] = TRUE;
			}

			$data['games_report'] = $this->report_functions->getGamesReportWeekly($start_date, $end_date);
			$data['allLevels'] = $this->player_manager->getAllPlayerLevels();

			$this->template->write_view('main_content', 'report_management/games_report/view_games_report_weekly', $data);
			$this->template->render();
		}
	}

	/**
	 * detail: games report monthly page
	 *
	 * @return load template
	 */
	public function viewGamesReportMonthly($start_date, $end_date) {
		if (!$this->permissions->checkPermissions('game_report')) {
			$this->error_access();
		} else {
			$this->loadTemplate('Games Report', '', '', 'report');

			//export report permission checking
			if (!$this->permissions->checkPermissions('export_games_report')) {
				$data['export_report_permission'] = FALSE;
			} else {
				$data['export_report_permission'] = TRUE;
			}

			$data['games_report'] = $this->report_functions->getGamesReportMonthly($start_date, $end_date);
			$data['allLevels'] = $this->player_manager->getAllPlayerLevels();

			$this->template->write_view('main_content', 'report_management/games_report/view_games_report_monthly', $data);
			$this->template->render();
		}
	}

	/**
	 * detail: games report yearly page
	 *
	 * @return load template
	 */
	public function viewGamesReportYearly($start_date, $end_date) {
		if (!$this->permissions->checkPermissions('game_report')) {
			$this->error_access();
		} else {
			$this->loadTemplate('Games Report', '', '', 'report');

			//export report permission checking
			if (!$this->permissions->checkPermissions('export_games_report')) {
				$data['export_report_permission'] = FALSE;
			} else {
				$data['export_report_permission'] = TRUE;
			}

			$data['games_report'] = $this->report_functions->getGamesReportYearly($start_date, $end_date);
			$data['allLevels'] = $this->player_manager->getAllPlayerLevels();

			$this->template->write_view('main_content', 'report_management/games_report/view_games_report_yearly', $data);
			$this->template->render();
		}
	}

	/**
	 * detail: export game report to excel
	 *
	 * @return  excel format
	 */
	public function exportGameReportToExcel() {
		$data = array(
			'username' => $this->authentication->getUsername(),
			'management' => 'Report Management',
			'userRole' => $this->rolesfunctions->getRoleByUserId($this->authentication->getUserId())['roleName'],
			'action' => 'Exported Game Report List',
			'description' => "User " . $this->authentication->getUsername() . " exported logs report list.",
			'logDate' => date("Y-m-d H:i:s"),
			'status' => 0,
		);
		$this->report_functions->recordAction($data);

		$result = $this->report_functions->getGameReportListToExport();
		/*var_dump($result);exit();*/
		$this->excel->to_excel($result, 'gamereportlist-excel');
	}

	/**
	 * detail: view summary report
	 *
	 * @return load template
	 */
	public function viewSummaryReport() {
		if (!$this->permissions->checkPermissions('report_transactions')) {
			$this->error_access();
		} else {
			$this->loadTemplate('Report Management', '', '', 'report');
			$this->template->add_js('resources/js/date.js');
			$this->template->write_view('main_content', 'report_management/summary_report/view_summary_report');
			$this->template->render();
		}
	}

	/**
	 * detail: view excel files list
	 *
	 * @deprecated No longer used.
	 */
	public function viewTransactionReport() {
		if (!$this->permissions->checkPermissions('summary_report')) {
			$this->error_access();
		} else {
			$this->loadTemplate('Report Management', '', '', 'excel report');
			$this->template->write_view('main_content', 'report_management/summary_report/view_exported_reports');
			$this->template->render();
		}
	}

	/**
	 * get excel files
	 *AJAX
	 * @return  json
	 */
	public function exportedTransactionsReport() {
		$this->load->model('reports');
		$this->reports->getExportedTransactionsReport();
	}

	/**
	 * detail: Download excel file
	 *
	 * @param string $file
	 * @return download
	 */
	public function downloadTransactionReport($file) {

		$this->load->helper('download');
		$filename = $file . '.xls';
		$dir = $this->config->item('report_path') . '/';
		$data = file_get_contents($dir . $filename);

		force_download($filename, $data);
	}

	/**
	 * detail: export summary report to excel
	 *
	 * @return  excel format
	 */
	public function exportSummaryReportToExcel() {

		$data = array(
			'username' => $this->authentication->getUsername(),
			'management' => 'Report Management',
			'userRole' => $this->rolesfunctions->getRoleByUserId($this->authentication->getUserId())['roleName'],
			'action' => 'Exported Summary Report List',
			'description' => "User " . $this->authentication->getUsername() . " exported logs report list.",
			'logDate' => date("Y-m-d H:i:s"),
			'status' => 0,
		);

		$this->report_functions->recordAction($data);

		$result = $this->report_functions->getSummaryReportListToExport(null);
		/*var_dump($result);exit();*/
		//$this->excel->to_excel($result, 'summaryreportlist');
		$d = new DateTime();
		$this->utils->create_excel($result, 'summaryreportlist_' . $d->format('Y_m_d_H_i_s') . '_' . rand(1, 999));
	}

	/**
	 * detail: display new registered player
	 *
	 * @return  excel format
	 */
	public function viewNewRegisteredPlayer($date) {
		$this->loadTemplate('Report Management', '', '', 'report');
		$this->template->add_js('resources/js/date.js');

		//export report permission checking
		if (!$this->permissions->checkPermissions('export_report')) {
			$data['export_report_permission'] = FALSE;
		} else {
			$data['export_report_permission'] = TRUE;
		}

		$data['player'] = $this->report_functions->getNewRegisteredPlayer($date . ' 00:00:00', $date . ' 23:59:59');

		$this->template->write_view('main_content', 'report_management/summary_report/view_registered_players', $data);
		$this->template->render();
	}

	/**
	 * detail: display total registered player
	 *
	 * @param string @date
	 * @return  excel format
	 */
	public function viewRegisteredPlayer($date) {
		$this->loadTemplate('Report Management', '', '', 'report');
		$this->template->add_js('resources/js/date.js');

		//export report permission checking
		if (!$this->permissions->checkPermissions('export_report')) {
			$data['export_report_permission'] = FALSE;
		} else {
			$data['export_report_permission'] = TRUE;
		}

		$data['player'] = $this->report_functions->getRegisteredPlayer($date);

		$this->template->write_view('main_content', 'report_management/summary_report/view_registered_players', $data);
		$this->template->render();
	}

	/**
	 * detail: display cashback player
	 *
	 * @param string $date
	 * @return  excel format
	 */
	public function viewCashbackPlayer($date) {
		$this->loadTemplate('Report Management', '', '', 'report');
		$this->template->add_js('resources/js/date.js');

		//export report permission checking
		if (!$this->permissions->checkPermissions('export_report')) {
			$data['export_report_permission'] = FALSE;
		} else {
			$data['export_report_permission'] = TRUE;
		}

		$data['player'] = $this->report_functions->getCashbackPlayer($date);

		$this->template->write_view('main_content', 'report_management/summary_report/view_cashback_players', $data);
		$this->template->render();
	}

	/**
	 * detail: display bonus player
	 *
	 * @param string $date
	 * @return  excel format
	 */
	public function viewBonusPlayer($date) {
		$this->loadTemplate('Report Management', '', '', 'report');
		$this->template->add_js('resources/js/date.js');

		//export report permission checking
		if (!$this->permissions->checkPermissions('export_report')) {
			$data['export_report_permission'] = FALSE;
		} else {
			$data['export_report_permission'] = TRUE;
		}

		$data['player'] = $this->report_functions->getBonusPlayer($date);

		$this->template->write_view('main_content', 'report_management/summary_report/view_bonus_players', $data);
		$this->template->render();
	}

	# KAISER DAPAR #######################################################################################################################

	/**
	 *
	 * detail: display the list of summary reports filtered by year and months
	 *
	 * @param string @year
	 * @param string @month
	 * @return load template
	 */
	public function summary_report($year = null, $month = null) {
		if (true) { #!$this->permissions->checkPermissions('summary_report')
			$this->summary_report_2();
		}else{

            $this->template->add_css('resources/third_party/bootstrap-multiselect-master/dist/css/bootstrap-multiselect.css');
            $this->template->add_js('resources/third_party/bootstrap-multiselect-master/dist/js/bootstrap-multiselect.js');

            $data = array(
				'year' => $year,
				'month' => $month,
			);
            $data['selected_tags'] = $this->input->get_post('tag_list') ?: '';
            $data['tags'] = $this->player_manager->getTags('tagId', null, null);

			$this->loadTemplate('Report Management', '', '', 'report');
			$this->template->write_view('main_content', 'report_management/summary_report/summary_report', $data);
			$this->template->render();
		}
	}

	/**
	 *
	 * detail: display the list of summary reports filtered by date from and date to
	 *
	 * @param string @dateFrom
	 * @param string @dateTo
	 * @return load template
	 */
	public function summary_report_2($dateFrom = null, $dateTo = null, $month_only='false') {
		//echo $dateFrom;die();
		if (!$this->permissions->checkPermissions('summary_report_2')) {
			$this->error_access();
		}else{
			$data = array(
				'dateFrom' => (!$dateFrom) ? date("Y-m-01") : $dateFrom,
				'dateTo' => (!$dateTo) ? date("Y-m-d") : $dateTo,
				'month_only' => $month_only,
			);


			$enable_freeze_top_in_list = $this->utils->_getEnableFreezeTopWithMethod(__METHOD__, $this->config->item('enable_freeze_top_method_list'));
			// $enable_freeze_top_in_list = false; // default
			// $enable_freeze_top_method_list = $this->config->item('enable_freeze_top_method_list');
			// if( !empty($enable_freeze_top_method_list) ){
			// 	if( in_array(__METHOD__, $enable_freeze_top_method_list) ){ // __METHOD__, "Payment_management::deposit_list"
			// 		$enable_freeze_top_in_list = true;
			// 	}
			// }
			$data['enable_freeze_top_in_list'] = $enable_freeze_top_in_list;

			$currency = $this->utils->getCurrentCurrency();
            $data['currency_decimals'] = $currency['currency_decimals'];

			$this->load->model(['functions_report_field']);

			$roleId = $this->permissions->getRoleId();
			$data['fields'] = [];
			$fieldsPermission= $this->functions_report_field->getFunctionPermission($roleId,'summary_report_2');
			if (!$fieldsPermission['exist']) {
				$data['fields'] = array_keys($this->config->item('summary_report_2','roles_report')?:[]);
			} else {
				$data['fields'] = $fieldsPermission['permission'];
			}

			$data['enable_roles_report'] = !empty($this->config->item('enable_roles_report')) ? true : false;
			$data['summary_report'] = $this->config->item('summary_report_2','roles_report') ?:[];

			$this->loadTemplate(lang('report.s08'), '', '', 'report');
			$this->template->write_view('main_content', 'report_management/summary_report/summary_report_2', $data);
			$this->template->render();
		}
	}


	/**
	 * detail: display the lists of new members
	 *
	 * @param string $year
	 * @param string $month
	 * @param string $day
	 * @return load template
	 */
	public function new_members($year = null, $month = null, $day = null, $dateFrom = null, $dateTo = null) {

		$temp_data = array(
			'year' => $year,
		);
		if(strlen($year) == 6){
			$year_month = str_split($year, 4);
			$data = array(
				'year' => $year_month[0],
				'month' => $year_month[1],
				'day' => $day,
			);
		}elseif(!empty($dateFrom) && !empty($dateTo)){
			$data = array(
				'year' => null,
				'month' => null,
				'day' => null,
				'dateFrom' => $dateFrom,
				'dateTo' => $dateTo,
			);
		}else{
			$data = array(
				'year' => $year,
				'month' => $month,
				'day' => $day,
			);
		}

		$data['selected_tags'] =$a= $this->input->get_post('tag_list');
        $data['affiliate_username'] = $this->input->get_post('affiliate_username');

		$data['tags'] =$b= $this->player->getAllTagsOnly();

		$this->loadTemplate('Report Management', '', '', 'report');
		$this->template->add_js('resources/js/datatables.min.js');
        $this->template->add_js('resources/js/dataTables.responsive.min.js');
        $this->template->add_js('resources/js/dataTables.order.dom-checkbox.js');
		$this->template->add_css('resources/css/datatables.min.css');
		$this->template->add_css('resources/third_party/bootstrap-multiselect-master/dist/css/bootstrap-multiselect.css');
        $this->template->add_js('resources/third_party/bootstrap-multiselect-master/dist/js/bootstrap-multiselect.js');

		$this->template->write_view('main_content', 'report_management/summary_report/new_members', $data);
		$this->template->render();
	}

	/**
	 * detail: display the lists of total members
	 *
	 * @param string $year
	 * @param string $month
	 * @param string $day
	 * @return load template
	 */
	// public function total_members($year = null, $month = null, $day = null) {
	public function total_members($year = null, $month = null, $day = null, $dateFrom = null, $dateTo = null) {

		$temp_data = array(
			'year' => $year,
		);
		if(strlen($year) == 6){
			$year_month = str_split($year, 4);
			$data = array(
			'year' => $year_month[0],
			'month' => $year_month[1],
			'day' => $day,
		);
		}
		else{

			$data = array(
				'year' => $year,
				'month' => $month,
				'day' => $day,
			);
		}

		$data['selected_tags'] =$a= $this->input->get_post('tag_list');

		$data['tags'] =$b= $this->player->getAllTagsOnly();

		$this->loadTemplate('Report Management', '', '', 'report');
		$this->template->add_js('resources/js/datatables.min.js');
        $this->template->add_js('resources/js/dataTables.responsive.min.js');
        $this->template->add_js('resources/js/dataTables.order.dom-checkbox.js');
		$this->template->add_css('resources/css/datatables.min.css');
		$this->template->add_css('resources/third_party/bootstrap-multiselect-master/dist/css/bootstrap-multiselect.css');
        $this->template->add_js('resources/third_party/bootstrap-multiselect-master/dist/js/bootstrap-multiselect.js');

		$this->template->write_view('main_content', 'report_management/summary_report/total_members', $data);
		$this->template->render();
	}

	/**
	 * detail: display the lists of total deposit members
	 *
	 * @param string $year
	 * @param string $month
	 * @param string $day
	 * @return load template
	 */
	public function total_deposit_members($year = null, $month = null, $day = null) {

		$temp_data = array(
			'year' => $year,
		);
		if(strlen($year) == 6){
			$year_month = str_split($year, 4);
			$data = array(
			'year' => $year_month[0],
			'month' => $year_month[1],
			'day' => $day,
		);
		}
		else{

			$data = array(
				'year' => $year,
				'month' => $month,
				'day' => $day,
			);
		}

		$this->loadTemplate('Report Management', '', '', 'report');
		$this->template->write_view('main_content', 'report_management/summary_report/total_deposit_members', $data);
		$this->template->render();
	}

    /**
     * detail: display the lists of total deposit members v2
     *
     * @param string $year
     * @param string $month
     * @param string $day
     * @return load template
     */
    public function total_deposit_members_2($year = null, $month = null, $day = null) {
        if(strlen($year) == 6){
            $year_month = str_split($year, 4);
            $data = array(
                'year' => $year_month[0],
                'month' => $year_month[1],
                'day' => $day,
            );
        }else{
            $data = array(
                'year' => $year,
                'month' => $month,
                'day' => $day,
            );
        }

        $this->loadTemplate('Report Management', '', '', 'report');
        $this->template->write_view('main_content', 'report_management/summary_report/total_deposit_members_2', $data);
        $this->template->render();
    }

	/**
	 * detail: display the lists of first deposit
	 *
	 * @param string $year
	 * @param string $month
	 * @param string $day
	 * @return load template
	 */
	public function first_deposit($year = null, $month = null, $day = null, $dateFrom = null, $dateTo = null) {

		$this->load->model('player_model');

		$temp_data = array(
			'year' => $year,
		);
		if(strlen($year) == 6){
			$year_month = str_split($year, 4);
			$data = array(
				'year' => $year_month[0],
				'month' => $year_month[1],
				'day' => $day,
			);
		}elseif(!empty($dateFrom) && !empty($dateTo)){
			$data = array(
				'year' => null,
				'month' => null,
				'day' => null,
				'dateFrom' => $dateFrom,
				'dateTo' => $dateTo,
			);
		}else{

			$data = array(
				'year' => $year,
				'month' => $month,
				'day' => $day,
			);
		}


		$data['selected_tags'] =$a= $this->input->get_post('tag_list');
		$data['affiliate_username'] = $this->input->get_post('affiliate_username');

		$data['tags'] =$b= $this->player_model->getAllTagsOnly();

		$this->loadTemplate('Report Management', '', '', 'report');

		$this->template->add_css('resources/css/select2.min.css');
		$this->template->add_js('resources/js/select2.full.min.js');
		$this->template->add_css('resources/third_party/bootstrap-multiselect-master/dist/css/bootstrap-multiselect.css');
        $this->template->add_js('resources/third_party/bootstrap-multiselect-master/dist/js/bootstrap-multiselect.js');

		$this->template->write_view('main_content', 'report_management/summary_report/first_deposit', $data);
		$this->template->render();
	}

	/**
	 * detail: display the lists of second deposit
	 *
	 * @param string $year
	 * @param string $month
	 * @param string $day
	 * @return load template
	 */
	public function second_deposit($year = null, $month = null, $day = null, $dateFrom = null, $dateTo = null) {

		$this->load->model('player_model');

		$temp_data = array(
			'year' => $year,
		);
		if(strlen($year) == 6){
				$year_month = str_split($year, 4);
				$data = array(
				'year' => $year_month[0],
				'month' => $year_month[1],
				'day' => $day,
			);
		}elseif(!empty($dateFrom) && !empty($dateTo)){
			$data = array(
				'year' => null,
				'month' => null,
				'day' => null,
				'dateFrom' => $dateFrom,
				'dateTo' => $dateTo,
			);
		}else{

			$data = array(
				'year' => $year,
				'month' => $month,
				'day' => $day,
			);
		}

		$data['selected_tags'] =$a= $this->input->get_post('tag_list');
        $data['affiliate_username'] = $this->input->get_post('affiliate_username');

		$data['tags'] =$b= $this->player_model->getAllTagsOnly();

		$this->loadTemplate('Report Management', '', '', 'report');

		$this->template->add_css('resources/css/select2.min.css');
		$this->template->add_js('resources/js/select2.full.min.js');
		$this->template->add_css('resources/third_party/bootstrap-multiselect-master/dist/css/bootstrap-multiselect.css');
        $this->template->add_js('resources/third_party/bootstrap-multiselect-master/dist/js/bootstrap-multiselect.js');

		$this->template->write_view('main_content', 'report_management/summary_report/second_deposit', $data);
		$this->template->render();
	}

	/**
	 *
	 * detail: display the lists of the Cashback reports
	 *
	 * @return load template
	 */
	public function cashback_report() {
		if (!$this->permissions->checkPermissions('cashback_report')) {
			$this->error_access();
		} else {
			$this->load->model(array('promorules', 'group_level'));
			$playerLevels = $this->group_level->getAllPlayerLevelsForSelect();
			array_walk($playerLevels, function (&$row) {
				$data = (explode("|",$row['groupLevelName']));
				if(!empty($data)){
					$row['groupLevelName'] = lang($data[0]).' - '.lang($data[1]);
				}
			});
			$data['vipgrouplist'] = array('' => lang('Select All')) + array_column($playerLevels, 'groupLevelName', 'vipsettingcashbackruleId');

			$data['allPromo'] = $this->promorules->getAllPromorulesList();
			$data['allPromoTypes'] = $this->promorules->getAllPromoTypeList();

			$start_today = date("Y-m-d") . ' 00:00:00';
			$end_today = date("Y-m-d") . ' 23:59:59';
			$search_reg_date_default = 'off'; // Default: a. enable checkbox.

			//export report permission checking
			if (!$this->permissions->checkPermissions('export_cashback_report')) {
				$data['export_report_permission'] = FALSE;
			} else {
				$data['export_report_permission'] = TRUE;
			}

			$data['conditions'] = $this->safeLoadParams(array(
				'by_date_from' => $this->utils->getTodayForMysql(),
				'by_date_to' => $this->utils->getTodayForMysql(),
				'by_username' => '',
				'by_player_level' => '',
				'by_amount_greater_than' => '',
				'by_amount_less_than' => '',
				'by_paid_flag' => '',
				'by_cashback_type' => '',
				'affiliate_username' => '',
				'registration_date_from' => $start_today,
				'registration_date_to' => $end_today,
				'search_reg_date' => $search_reg_date_default,
			));

			$data['conditions']['enable_date'] = $this->safeGetParam('enable_date', true, true);

			$enable_freeze_top_in_list = $this->utils->_getEnableFreezeTopWithMethod(__METHOD__, $this->config->item('enable_freeze_top_method_list'));
			$data['enable_freeze_top_in_list'] = $enable_freeze_top_in_list;

			//OGP-25040
			$data['selected_tags'] = $this->input->get_post('tag_list');
			$data['tags'] = $this->player->getAllTagsOnly();

			$this->loadTemplate(lang('Cashback Report'), '', '', 'report');
			$this->template->add_js('resources/js/bootstrap-switch.min.js');
			$this->template->add_css('resources/css/bootstrap-switch.min.css');
			$this->template->add_css('resources/third_party/bootstrap-multiselect-master/dist/css/bootstrap-multiselect.css');
        	$this->template->add_js('resources/third_party/bootstrap-multiselect-master/dist/js/bootstrap-multiselect.js');
			$this->template->write_view('main_content', 'report_management/view_cashback_report', $data);
			$this->template->render();
		}
	}

    /**
     *
     * detail: display the lists of the Recalculate Cashback reports
     *
     * @return load template
     */
    public function recalculate_cashback_report() {
        if (!$this->permissions->checkPermissions('cashback_report') && !$this->utils->getConfig('use_accumulate_deduction_when_calculate_cashback')) {
            $this->error_access();
        } else {
            $this->load->model(array('promorules', 'group_level'));
            $playerLevels = $this->group_level->getAllPlayerLevelsForSelect();
            array_walk($playerLevels, function (&$row) {
                $data = (explode("|",$row['groupLevelName']));
                if(!empty($data)){
                    $row['groupLevelName'] = lang($data[0]).' - '.lang($data[1]);
                }
            });
            $data['vipgrouplist'] = array('' => lang('Select All')) + array_column($playerLevels, 'groupLevelName', 'vipsettingcashbackruleId');

            //export report permission checking
            if (!$this->permissions->checkPermissions('export_cashback_report')) {
                $data['export_report_permission'] = FALSE;
            } else {
                $data['export_report_permission'] = TRUE;
            }

            $data['conditions'] = $this->safeLoadParams(array(
                'by_date_from' => $this->utils->getTodayForMysql(),
                'by_date_to' => $this->utils->getTodayForMysql(),
                'by_username' => '',
                'by_player_level' => '',
                'by_amount_greater_than' => '',
                'by_amount_less_than' => '',
            ));

            $data['conditions']['enable_date'] = $this->safeGetParam('enable_date', true, true);

            $this->loadTemplate(lang('Recalculate Cashback Report'), '', '', 'report');
            $this->template->write_view('main_content', 'report_management/view_recalculate_cashback_report', $data);
            $this->template->render();
        }
    }

    /**
     *
     * detail: display withdraw condition deduction process report
     *
     * @return load template
     */
    public function wc_deduction_process_report() {
        if (!$this->permissions->checkPermissions('cashback_report') && !$this->utils->getConfig('use_accumulate_deduction_when_calculate_cashback')) {
            $this->error_access();
        } else {
            //export report permission checking
            if (!$this->permissions->checkPermissions('export_cashback_report')) {
                $data['export_report_permission'] = FALSE;
            } else {
                $data['export_report_permission'] = TRUE;
            }

            $data['conditions'] = $this->safeLoadParams(array(
                'by_date_from' => $this->utils->getTodayForMysql(),
                'by_date_to' => $this->utils->getTodayForMysql(),
                'by_username' => ''
            ));

            $data['conditions']['enable_date'] = $this->safeGetParam('enable_date', true, true);

            $this->loadTemplate(lang('wc_dudection_process.title'), '', '', 'report');
            $this->template->write_view('main_content', 'report_management/view_wc_deduction_process_report', $data);
            $this->template->render();
        }
    }

    /**
     *
     * detail: display recalculte withdraw condition deduction process report
     *
     * @return load template
     */
    public function recalculate_wc_deduction_process_report() {
        if (!$this->permissions->checkPermissions('cashback_report') && !$this->utils->getConfig('use_accumulate_deduction_when_calculate_cashback')) {
            $this->error_access();
        } else {
            //export report permission checking
            if (!$this->permissions->checkPermissions('export_cashback_report')) {
                $data['export_report_permission'] = FALSE;
            } else {
                $data['export_report_permission'] = TRUE;
            }

            $data['conditions'] = $this->safeLoadParams(array(
                'by_date_from' => $this->utils->getTodayForMysql(),
                'by_date_to' => $this->utils->getTodayForMysql(),
                'by_username' => ''
            ));

            $data['conditions']['enable_date'] = $this->safeGetParam('enable_date', true, true);

            $this->loadTemplate(lang('wc_dudection_process.recalculate.title'), '', '', 'report');
            $this->template->write_view('main_content', 'report_management/view_recalculate_wc_deduction_process_report', $data);
            $this->template->render();
        }
    }

	/**
	 *
	 * detail: display the lists of the Duplicate account report
	 *
	 * @return load template
	 */
	public function duplicate_account_report() {
		if (!$this->permissions->checkPermissions('duplicate_account_report')) {
			$this->error_access();
		} else {
			$this->utils->debug_log('In report_management.php/duplicate_account_report');
			//export report permission checking
			// if (!$this->permissions->checkPermissions('export_duplicate_account_report')) {
			// 	$data['export_report_permission'] = FALSE;
			// } else {
				// $data['export_report_permission'] = TRUE;
			// }

			$data['conditions'] = $this->safeLoadParams(array(
				'by_username' => '',
			));

			$this->utils->debug_log($data['conditions']);

			$data['enable_freeze_top_in_list'] = $this->utils->_getEnableFreezeTopWithMethod(__METHOD__, $this->config->item('enable_freeze_top_method_list'));

			$this->loadTemplate(lang('Duplicate Account Report'), '', '', 'report');
			$this->template->add_js('resources/js/bootstrap-switch.min.js');
			$this->template->add_css('resources/css/bootstrap-switch.min.css');
			$this->template->write_view('main_content', 'report_management/view_duplicate_account_report', $data);
			$this->template->render();
		}
	}

	/**
	 * View duplicate account info for specified username
	 *
	 * @param	string	$username	== duplicate_account_info.username
	 * @uses	db table duplicate_account_info
	 * @return load template
	 *
	 */
	public function duplicate_account_detail_by_username($username = null) {
		$this->load->library('duplicate_account');

		try {
			if (empty($username)) {
				throw new Exception('Username missing');
			}
			$this->load->model('report_model');
			if (!$this->report_model->usernameExistsInDuplicateAccountInfo($username)) {
				$this->load->library('player_manager');
				$player_id = $this->player_manager->getPlayerIdByUsername($username);
				if (!$player_id) {
					throw new Exception('Username unknown');
				}
			}
			$data['title'] = lang('Duplicate Accounts Details');
			// $data['player_id'] = $player_id;
			$data['username'] = $username;
			//$this->load->view('payment_management/ajax_view_duplicate_accounts_detail', $data);
			$this->loadTemplate(lang('Duplicate Accounts Details'), '', '', 'report');
			// $this->template->add_js('resources/js/bootstrap-switch.min.js');
			// $this->template->add_css('resources/css/bootstrap-switch.min.css');
			// $this->template->write_view('sidebar', 'report_management/sidebar');
            $data['dup_enalbed_column'] = $this->utils->getConfig('duplicate_account_info_enalbed_condition');
			$this->template->write_view('main_content', 'report_management/view_duplicate_accounts_detail', $data);
			$this->template->render();

		} catch (Exception $ex) {
			$this->alertMessage(self::MESSAGE_TYPE_WARNING, lang($ex->getMessage()));
			redirect(BASEURL . '/report_management/duplicate_account_report');
		}
	}

	/**
	 * detail: display registered mobile number and verification code
	 *
	 * @return load template
	 */
	public function viewSmsReport() {
		if(!$this->permissions->checkPermissions('sms_report'))
			return $this->error_access();

		$enable_freeze_top_in_list = $this->utils->_getEnableFreezeTopWithMethod(__METHOD__, $this->config->item('enable_freeze_top_method_list'));
		$data['enable_freeze_top_in_list'] = $enable_freeze_top_in_list;

		$this->loadTemplate(lang('SMS Verification Code'), '', '', 'report');
		$this->template->write_view('main_content', 'report_management/view_sms_report', $data);
		$this->template->render();
	}

	/**
     * detail: display email verification report
     *
     * @return load template
     */
	public function viewEmailVerificationReport() {
		if(!$this->permissions->checkPermissions('view_email_verification_report'))
			return $this->error_access();

		$this->load->model('email_verification');

		// $this->email_verification->syncQueueResult();
        $data['email_template_options'] = $this->email_verification->getEmailTemplateOptions();
		$data['enable_freeze_top_in_list'] = $this->utils->_getEnableFreezeTopWithMethod(__METHOD__, $this->config->item('enable_freeze_top_method_list'));

		$this->loadTemplate(lang('Email Verification Report'), '', '', 'report');
		$this->template->write_view('main_content', 'report_management/view_email_verification_report',$data);
		$this->template->render();
	}

	public function viewActivePlayers(){
        if(!$this->permissions->checkPermissions('active_player_report')){
            return $this->error_access();
        }

		if( empty( $_SERVER['QUERY_STRING'] ) ){
			$date_from = urlencode(date('Y-m-d', time()));
			$date_to = urlencode(date('Y-m-d', time()));
			$query_string = 'view_type=daily&date_form=' . $date_from . '&date_to=' . $date_to;

			redirect('report_management/viewActivePlayers?' . $query_string);
		}

		$this->load->model(array('external_system','total_player_game_hour'));

		$data = array();
		if (!$this->permissions->checkPermissions('export_active_player_report')) {
			$data['export_report_permission'] = FALSE;
		} else {
			$data['export_report_permission'] = TRUE;
		}

		$data['game_provider'] = $this->external_system->getAllGameApis();
		$data['enable_freeze_top_in_list'] = $this->utils->_getEnableFreezeTopWithMethod(__METHOD__, $this->config->item('enable_freeze_top_method_list'));

		$this->loadTemplate(lang('Active Player Report'), '', '', 'report');
		$this->template->write_view('main_content', 'report_management/active_player', $data);
		$this->template->render();

	}

	public function viewTotalActivePlayers(){
		$this->load->model(array('external_system','total_player_game_day'));
        $data = array();

        $data['conditions'] = $this->safeLoadParams(array(
            'username' => '',
            'date_start' => $this->utils->getTodayForMysql(),
            'date_end' => $this->utils->getTodayForMysql(),
        ));

        $data['game_provider'] = $this->external_system->getAllGameApis();

		//OGP-25040
		$data['selected_tags'] = $this->input->get_post('tag_list');
		$data['tags'] = $this->player->getAllTagsOnly();

		if (!$this->permissions->checkPermissions('export_active_player_report')) {
			$data['export_report_permission'] = FALSE;
		} else {
			$data['export_report_permission'] = TRUE;
		}

        $this->loadTemplate('Total Active Players', '', '', 'report');
		$this->template->add_css('resources/third_party/bootstrap-multiselect-master/dist/css/bootstrap-multiselect.css');
        $this->template->add_js('resources/third_party/bootstrap-multiselect-master/dist/js/bootstrap-multiselect.js');
        $this->template->write_view('main_content', 'report_management/view_total_active_player', $data);
        $this->template->render();

	}

	public function dailyPlayerBalanceReport() {
		if (true) { #!$this->permissions->checkPermissions('daily_player_balance_report')
			$this->error_access();
		} else {
            $this->template->add_css('resources/third_party/bootstrap-multiselect-master/dist/css/bootstrap-multiselect.css');
            $this->template->add_js('resources/third_party/bootstrap-multiselect-master/dist/js/bootstrap-multiselect.js');

			$this->load->model('daily_balance');
			$data['date'] = $this->input->get_post('date');
			$data['date'] = $data['date'] !== false ? $data['date'] : date('Y-m-d', strtotime('-1 day'));
			$data['username'] = $this->input->get_post('username');

            $data['selected_tags'] = $this->input->get_post('tag_list');
            $exclude_player =  is_array($data['selected_tags']) ? array_column($this->player->excludePlayerByTags($data['selected_tags']),'playerId') : null;

			extract($data);

			$data['game_platforms'] = $this->utils->getActiveGameSystemList();
			$data['rows'] = $this->daily_balance->get_daily_balance($date, $username, $exclude_player);
            $sort = "tagId";
            $data['tags'] = $this->player_manager->getTags($sort, null, null);
            $data['limit'] = $this->utils->getConfig('daily_player_balance_report_limit');
			$this->loadTemplate('Daily Balance Report', '', '', 'report');
			$this->template->write_view('main_content', 'report_management/daily_balance_report', $data);
			$this->template->render();

		}
	}

	public function daily_player_balance_report() {
		if ( ! $this->permissions->checkPermissions('daily_player_balance_report')) {
			$this->error_access();
		} else {
            $this->template->add_css('resources/third_party/bootstrap-multiselect-master/dist/css/bootstrap-multiselect.css');
            $this->template->add_js('resources/third_party/bootstrap-multiselect-master/dist/js/bootstrap-multiselect.js');

			$this->load->model('daily_balance');

			if (!$this->permissions->checkPermissions('export_daily_player_balance_report')) {
				$data['export_report_permission'] = FALSE;
			} else {
				$data['export_report_permission'] = TRUE;
			}

			$defaultBalanceFilterAmount = $this->utils->getConfig('default_daily_player_report_total_balance_filter_amount');

			$data['conditions'] = $this->safeLoadParams(array(
				'date_filter' => $this->utils->getYesterdayForMysql(),
				'username' => '',
				'total_balance' => !empty($defaultBalanceFilterAmount) ? $defaultBalanceFilterAmount: 10,
			));

			$data['enable_freeze_top_in_list'] = $this->utils->_getEnableFreezeTopWithMethod(__METHOD__, $this->config->item('enable_freeze_top_method_list'));

			$data['game_platforms'] = $this->utils->getAllGameSystemList();
            $data['selected_tags'] = $this->input->get_post('tag_list');
            $exclude_player =  is_array($data['selected_tags']) ? array_column($this->player->excludePlayerByTags($data['selected_tags']),'playerId') : null;
            $data['tags'] = $this->player_manager->getTags("tagId", null, null);
			$this->loadTemplate(lang('Sidebar Daily Player Balance Report'), '', '', 'report');
			$this->template->write_view('main_content', 'report_management/daily_balance_report_2', $data);
			$this->template->render();
		}
	}

	/**
	 * Rebuilds daily player balance report for given date
	 * @uses	datestring	GET:date	The date
	 * @return	JSON		Standard AJAX return structure [ success, code, message, result ]
	 */
	public function daily_player_balance_report_rebuild() {
		$date_src = $this->input->get('date');
		$date = date('Y-m-d', strtotime($date_src));
		$res = [
			'success'	=> false ,
			'code'		=> -127 ,
			'message'	=> '',
			'result'	=> [ 'date' => $date ]
		];
		try {
			$this->load->model('daily_balance');

			// $this->daily_balance->generateDailyBalance($date);
			$token = $this->generateDailyBalanceByQueue($date);

			$res['code']	= 0;
			$res['message']	= "Daily balance report rebuild for '{$date}' is under way.  The process will take 5 to 10 minutes, please come back later.";
			$res['success']	= true;
			$res['token'] = $token;
		}
		catch (Exception $ex) {
			$res['code']	= $ex->getCode();
			$res['message']	= $ex->getMessage();
		}
		finally {
			$this->returnJsonResult($res);
		}
	}

	protected function generateDailyBalanceByQueue($arg_date) {
		$this->load->library([ 'lib_queue', 'language_function' ]);
		$this->load->model([ 'queue_result' ]);

		$caller = $this->authentication->getUserId();
		$state = null;
		$lang = $this->language_function->getCurrentLanguage();

		$funcName = 'calcReportDailyBalance';
		$callerType = Queue_result::CALLER_TYPE_ADMIN;
		$systemId = Queue_result::SYSTEM_UNKNOWN;

		$params = [
			'arg_date' => $arg_date
		];

		$token = $this->lib_queue->commonAddRemoteJob($systemId, $funcName, $params, $callerType, $caller, $state, $lang);

		return $token;
	}

	public function playerRealtimeBalance() {
		if ( ! $this->permissions->checkPermissions('player_balance_report')) {
			$this->error_access();
		} else {
			$this->load->model(['daily_balance','functions_report_field']);

			if (!$this->permissions->checkPermissions('export_player_balance_report')) {
				$data['export_report_permission'] = FALSE;
			} else {
				$data['export_report_permission'] = TRUE;
			}

			$start_today = date("Y-m-01") . ' 00:00:00';
			$end_today   = date("Y-m-d") . ' 23:59:59';

			$data['conditions'] = $this->safeLoadParams(array(
				'date_filter' => $this->utils->getTodayForMysql(),
				'registration_date_from' => $start_today,
				'registration_date_to' => $end_today,
				'search_reg_date' => '',
				'username' => '',
				'total_balance' => '',
				'total_balance_grater_then' => ''
			));

			$data['enable_freeze_top_in_list'] = $this->utils->_getEnableFreezeTopWithMethod(__METHOD__, $this->config->item('enable_freeze_top_method_list'));

			$data['game_platforms'] = $this->utils->getActiveGameSystemList();
            $data['selected_tags'] = $this->input->get_post('tag_list');
            $data['tags'] = $this->player->getAllTagsOnly();

			$roleId = $this->users->getRoleIdByUserId($this->authentication->getUserId());
			$data['enable_report_field'] = $this->config->item('enable_roles_report', false);
			$data['fields_permission'] = [];
			if ($data['enable_report_field']) {
				$functionFieldPermission = $this->functions_report_field->getFunctionPermission($roleId, 'player_balance_report');
				if (!$data['enable_report_field'] || !$functionFieldPermission['exist']) {
					$data['fields_permission'] = array_keys($this->config->item('roles_report')['player_balance_report']);
				} else {
					$data['fields_permission'] = $functionFieldPermission['permission'];	
				}
			}


			$this->loadTemplate(lang('role.259'), '', '', 'report');
            $this->template->add_css('resources/third_party/bootstrap-multiselect-master/dist/css/bootstrap-multiselect.css');
            $this->template->add_js('resources/third_party/bootstrap-multiselect-master/dist/js/bootstrap-multiselect.js');
			$this->template->write_view('main_content', 'report_management/realtime_balance_report', $data);
			$this->template->render();
		}
	}

    public function responsibleGamingReport() {
        if ( ! $this->permissions->checkPermissions('responsible_gaming_report')) {
            $this->error_access();
        } else {
            $this->load->model(['group_level','player','kyc_status_model']);
            $data['playerLevel']=$this->group_level->getAllPlayerLevelsForSelect();
            array_walk($data['playerLevel'], function (&$row) {
				$data = (explode("|",$row['groupLevelName']));
				if(!empty($data)){
					$row['groupLevelName'] = lang($data[0]).' - '.lang($data[1]);
				}
			});
            $data['tags'] = $this->player->getAllTagsOnly();
            //getAllKycStatus
            $data['kyc'] = $this->kyc_status_model->getAllKycStatus();
            $this->loadTemplate(lang('Responsible Gaming Report'), '', '', 'report');
            $this->template->write_view('main_content', 'report_management/view_responsible_gaming_report', $data);
            $this->template->render();

        }
    }


	public function viewPlayerBlanceReport() {
		if (true) {
			$this->error_access();
		} else {
			$this->load->model(['daily_balance']);
			$this->load->library(['language_function']);
			// $this->load->model(['banktype','external_system', 'game_type_model', 'game_logs', 'wallet_model']);
			$now = new DateTime('now');
			$date_from = $now->format('Y-m-01');
			$dateto = $now;
			$dateto->modify('last day of this month');
			$date_to = $dateto->format('Y-m-d');
			$data['date_from'] = ($this->input->get_post('date_from')?$this->input->get_post('date_from'):$date_from);
			$data['date_to'] = ($this->input->get_post('date_to')?$this->input->get_post('date_to'):$date_to);
			$datefrom = new DateTime($data['date_from']);
			$dateto = new DateTime($data['date_to']);
			$DailyBalance = $this->daily_balance->getPlayerBalanceByDate($datefrom->format('Y-m-d'), $dateto->format('Y-m-d'));
			//group by date
			$datas = [];
			$datas['data'] = [];
			$depositMenuList = $this->utils->getDepositMenuList();
			$datas['deposit'] = [];
			foreach($depositMenuList as $deposit) {
				$datas['deposit'][] = array(
					'bankTypeId' => $deposit->bankTypeId,
					'bankName' => $deposit->bankName,
					'external_system_id' => $deposit->external_system_id
					);
			}
			$datas['withdraw'] = [];
			$withdrawalBanks = $this->banktype->getBankTypes();
			foreach ($withdrawalBanks as $bank) {
				$datas['withdraw'][] = array(
					'bankTypeId' => $bank->bankTypeId,
					'bankName' => $bank->bankName,
					'external_system_id' => $bank->external_system_id
				);
			}
			$datas['game_platform'] = [];
			$gameApiList = $this->external_system->getAllActiveSytemGameApi();
			// $gametree = array();
			foreach ($gameApiList as $row) {
			// 	$gameTypeList = $this->game_type_model->getGameTypeListByGamePlatformId($row['id']);
				$datas['game_platform'][] = array(
						'id' => $row['id'],
						'gamePlatformName' => $row['system_code'],
			// 			'gametype' => $gameTypeList,
				);
			}
			if (!empty($DailyBalance)) {
				foreach ($DailyBalance as $row) {
					if (!isset($datas['data'][$row['data_date']])) {
						$datas['data'][$row['data_date']] = [];
					}
					switch((int)$row['data_type']) {
						case 1 : //open value
							$datas['data'][$row['data_date']]['open'] = $row['data_value'];
							break;
						case 2 : //deposit value
							if (!isset($datas['data'][$row['data_date']]['deposit']))
								$datas['data'][$row['data_date']]['deposit'] = [];
							$datas['data'][$row['data_date']]['deposit'][$row['data_key_id']] = $row['data_value'];
							break;
						case 3 : //deposit value
							if (!isset($datas['data'][$row['data_date']]['withdraw']))
								$datas['data'][$row['data_date']]['withdraw'] = [];
							$datas['data'][$row['data_date']]['withdraw'][$row['data_key_id']] = $row['data_value'];
							break;
						case 4 : //game bet
						case 5 : //game win
						// case 6 : //game loss
						case 7 : //game cancel bet
						case 8 : //game manual adjust
						case 9 : //game real bonus
							if (!isset($datas['data'][$row['data_date']]['gamePlatform']))
								$datas['data'][$row['data_date']]['gamePlatform'] = [];
							if (!isset($datas['data'][$row['data_date']]['gamePlatform'][$row['data_key_id']]))
								$datas['data'][$row['data_date']]['gamePlatform'][$row['data_key_id']] = [];
							$datas['data'][$row['data_date']]['gamePlatform'][$row['data_key_id']][$row['data_type']] = $row['data_value'];
							break;
						case 10 :
							$datas['data'][$row['data_date']]['closing'] = $row['data_value'];
							break;
						case 11 :
							$datas['data'][$row['data_date']]['unsettle'] = $row['data_value'];
							break;
						case 12 :
							$datas['data'][$row['data_date']]['totalBalance'] = $row['data_value'];
							break;
					}
				}
				// $days = $dateto->diff($datefrom)->format("%a");
			}
			// $datas = array();
			// $cal_date = clone $datefrom;
			// $open_date = clone $datefrom;
			// $open_date->sub(new DateInterval('P1D'));
			// $lastdayAmount = $this->wallet_model->getTotalBigWalletBalanceByDate($open_date->format('Y-m-d 23:59:59'));
			// $openingBalance = round($lastdayAmount, 2);
			// for ($i = 0; $i <= $days; $i++) {
			// 	$todayData = Array(
			// 		'date' => $cal_date->format('Y-m-d'),
			// 		'openingRealPlayerBalance' => $openingBalance,
			// 	);
			// 	$depositBalance = array();
			// 	$closingRealPlayerBalance = $openingBalance;
			// 	foreach($payments['deposit'] as $deposit) {
			// 		$depositAmount = $this->transactions->getTotalDepositWithdrawalByPaymentAccount($deposit['bankTypeId'], Transactions::DEPOSIT, $cal_date->format('Y-m-d 00:00:00'), $cal_date->format('Y-m-d 23:59:59'));
			// 		$depositAmount = empty($depositAmount)?0:$depositAmount;
			// 		$depositBalance[$deposit['bankTypeId']] = $depositAmount;
			// 		$openingBalance += $depositAmount;
			// 		$closingRealPlayerBalance += $depositAmount;
			// 	}
			// 	$withdrawalBlance = array();
			// 	foreach($payments['withdrawal'] as $withdrawal) {
			// 		$withdrawalAmount = $this->transactions->getTotalDepositWithdrawalByPaymentAccount($withdrawal['bankTypeId'], Transactions::WITHDRAWAL, $cal_date->format('Y-m-d 00:00:00'), $cal_date->format('Y-m-d 23:59:59'));
			// 		$withdrawalAmount = empty($withdrawalAmount)?0:$withdrawalAmount;
			// 		$withdrawalBlance[$withdrawal['bankTypeId']] = $withdrawalAmount;
			// 		$openingBalance -= $withdrawalAmount;
			// 		$closingRealPlayerBalance -= $withdrawalAmount;
			// 	}
			// 	$gamePlatformInfo = array();
			// 	foreach($gametree as $gamePlatform) {
			// 		$totalBet = 0;
			// 		$totalWin = 0;
			// 		$totalLoss = 0;
			// 		list($totalBet, $totalWin, $totalLoss) = $this->game_logs->getTotalBetsWinsLoss($cal_date->format('Y-m-d 00:00:00'), $cal_date->format('Y-m-d 23:59:59'), $gamePlatform['id']);
			// 		$cancelBets = $this->game_logs->getUnsettledBets(array(Game_logs::STATUS_CANCELLED), $cal_date->format('Y-m-d 00:00:00'), $cal_date->format('Y-m-d 23:59:59'), $gamePlatform['id']);
			// 		$gamePlatformInfo[$gamePlatform['id']]['totalBet'] = empty($totalBet)?0:$totalBet;
			// 		$gamePlatformInfo[$gamePlatform['id']]['totalWin'] = empty($totalWin)?0:$totalWin;
			// 		$gamePlatformInfo[$gamePlatform['id']]['canceledBets'] = empty($cancelBets)?0:$cancelBets;
			// 		$ManualAdjustments = $this->transactions->getTotalManualAdjustmentByWalletId($cal_date->format('Y-m-d 00:00:00'), $cal_date->format('Y-m-d 23:59:59'), $gamePlatform['id']);
			// 		$openingBalance += $ManualAdjustments;
			// 		$closingRealPlayerBalance += $ManualAdjustments;
			// 		if ($ManualAdjustments < 0)
			// 			$ManualAdjustments = 0 - $ManualAdjustments;
			// 		$gamePlatformInfo[$gamePlatform['id']]['ManualAdjustments'] = $ManualAdjustments;
			// 		$realBonus = $this->transactions->getTotalBonusByWalletId($cal_date->format('Y-m-d 00:00:00'), $cal_date->format('Y-m-d 23:59:59'), $gamePlatform['id']);
			// 		$openingBalance += $realBonus;
			// 		$closingRealPlayerBalance += $realBonus;
			// 		if ($realBonus < 0)
			// 			$realBonus = 0 - $realBonus;
			// 		$gamePlatformInfo[$gamePlatform['id']]['RealBonus'] = $realBonus;
			// 		$closingRealPlayerBalance -= $totalBet;
			// 		$closingRealPlayerBalance += $totalWin;
			// 	}
			// 	$totalUnsettledBets = $this->game_logs->getUnsettledBets(array(Game_logs::STATUS_PENDING), $cal_date->format('Y-m-d 00:00:00'), $cal_date->format('Y-m-d 23:59:59'));
			// 	$totalUnsettledBets = empty($totalUnsettledBets)?0:$totalUnsettledBets;
			// 	$todayData['deposit'] = $depositBalance;
			// 	$todayData['withdrawal'] = $withdrawalBlance;
			// 	$todayData['gamePlatform'] = $gamePlatformInfo;
			// 	$todayData['closingRealPlayerBalance'] = $closingRealPlayerBalance;
			// 	$todayData['totalUnsettledBets'] = $totalUnsettledBets;
			// 	$todayData['totalPlayerBalanceCoverageRequirement'] = $closingRealPlayerBalance + $totalUnsettledBets;
			// 	$datas[] = $todayData;
			// 	$cal_date->add(new DateInterval('P1D'));
			// }
			$data['datas'] = $datas;
			$this->loadTemplate('Player Balance Report', '', '', 'report');
			$this->template->write_view('main_content', 'report_management/player_report/view_player_balance_report', $data);
			$this->template->render();

		}
	}

	public function generateDailyBalanceReport() {
		set_time_limit(60*5); //set time out 5 mins
		$startDate = $this->input->post('date_from');
		$endDate = $this->input->post('date_to');
		if (empty($startDate) && empty($endDate)) {
			$this->utils->debug_log('empty');
			$this->returnJsonResult(['success'=>false]);
		}
		$this->utils->debug_log('start date : ' . $startDate . ', end date : ' . $endDate);
		$this->load->model(['daily_balance']);
    	$date_start = new DateTime();
    	$date_start->modify('00:00:00');
    	// $date_start->modify('first day of this month 00:00:00');
    	$date_end = new DateTime();
    	$date_end->modify('23:59:59');
    	if (!empty($startDate)) {
    		$date_start->modify($startDate);
    		$date_end->modify($startDate);
    	}
    	if (!empty($endDate)) {
    		$date_end->modify($endDate);
    	}
   		$days = $date_end->diff($date_start)->days;
   		for ($i = 0; $i <= $days; $i++) {
   			if ($i > 0) {
    			$date_start->modify('+1 day');
   			}
   			$startDate = $date_start->format('Y-m-d 00:00:00');
   			$endDate = $date_start->format('Y-m-d 23:59:59');
   			$this->daily_balance->generatePlayerBalanceByDate($startDate, $endDate);
   		}
		$this->returnJsonResult(['success'=>true]);
	}

    public function bonusGamesReport() {
    	if(!$this->permissions->checkPermissions('bonus_games_report'))
    		return $this->error_access();

    	$this->load->model(['promo_games', 'group_level']);

    	$data = [];
    	$data['vipgrouplist'] = $this->group_level->getVipGroupList();
    	$data['allPromoTypes'] = $this->promorules->getAllPromoTypeList();


    	$data['promorules'] = $this->promo_games->get_all_linked_promorules_for_select();
		$data['gametypes'] = $this->promo_games->get_all_gametypes_for_select();
		$data['bonus_types'] = $this->promo_games->get_all_bonus_types_for_select();
		$data['export_report_permission'] = false && $this->permissions->checkPermissions('export_bonus_games_report');

		// Construct player level array for select
		// Array: P.levelId => (levelName)
		$all_player_levels = $this->group_level->getAllPlayerLevelsForSelect();
		$plevels_for_select = [];
		foreach ($all_player_levels as $plevel) {
			$plevel_name = sprintf('%s - %s', lang($plevel['groupName']), lang($plevel['vipLevelName']));
			$plevels_for_select[$plevel['vipsettingcashbackruleId']] = $plevel_name;
		}

    	$data['allPlayerLevels'] = $plevels_for_select;


    	$data['args'] = $this->safeLoadParams([
    		'date_from'		=> '',
    		'date_to'		=> '',
    		'enable_date'	=> $this->safeGetParam('enable_date', true, true),
			'player_username'	=> '',
			'player_match'		=> 'partial',
			'player_level_id'	=> '',
			'game_type'		=> '',
			'promo_type'	=> '',
			'promo_rule'	=> '',
			'bonus_type'	=> '',
			'amount_min'	=> '',
			'amount_max'	=> '',
		]);


        $this->loadTemplate(lang('Bonus Games Report'), '', '', 'report');
        $this->template->write_view('main_content', 'report_management/view_bonus_games_report', $data);
        $this->template->render();
    }

    /**
	 *
	 * detail: display the lists of the Player Analysis Report
	 *
	 * @return load template
	 */
	public function player_analysis_report() {
		if (!$this->permissions->checkPermissions('player_analysis_report')) {
			$this->error_access();
		} else {
			$this->load->model(array('game_provider_auth','game_logs'));
			$this->utils->debug_log('In report_management.php/player_analysis_report');
			//export report permission checking
			if (!$this->permissions->checkPermissions('export_player_analysis_report')) {
				$data['export_report_permission'] = FALSE;
			} else {
				$data['export_report_permission'] = TRUE;
			}
			$data['get_game_system_map'] = $this->utils->getGameSystemMap();
			$data['enable_freeze_top_in_list'] = $this->utils->_getEnableFreezeTopWithMethod(__METHOD__, $this->config->item('enable_freeze_top_method_list'));

			$this->loadTemplate(lang('Player Analysis Report'), '', '', 'report');
			$this->template->add_css('resources/css/bootstrap-switch.min.css');
			$this->template->add_css('resources/css/collapse-style.css');
			$this->template->add_css('resources/css/jquery-checktree.css');
			$this->template->add_js('resources/js/bootstrap-switch.min.js');
			$this->template->add_js('resources/js/ace/ace.js');
			$this->template->add_js('resources/js/ace/mode-javascript.js');
			$this->template->add_js('resources/js/ace/theme-tomorrow.js');
			$this->template->add_js('resources/js/jquery-checktree.js');
			$this->template->add_js('resources/js/select2.min.js');
			$this->template->add_css('resources/css/select2.min.css');
			$this->template->write_view('main_content', 'report_management/view_player_analysis_report', $data);
			$this->template->render();
		}
	}

	/**
	 * detail: display grade report lists
	 *
	 * @return load template
	 */
	public function viewGradeReport() {
		if (!$this->permissions->checkPermissions('grade_report')) {
			$this->error_access();
		} else {
			$this->load->model(['group_level','player']);
			$data['request_type_list'] = [
				Group_level::REQUEST_TYPE_AUTO_GRADE => 'report.gr.auto_grade',
				Group_level::REQUEST_TYPE_MANUAL_GRADE => 'report.gr.manual_grade',
				Group_level::REQUEST_TYPE_SPECIFIC_GRADE => 'report.gr.specific_grade'
			];

			$data['behavior_list'] = [
				Group_level::RECORD_UPGRADE => 'report.gr.upgrade',
				Group_level::RECORD_DOWNGRADE => 'report.gr.downgrade',
				Group_level::RECORD_SPECIFICGRADE => 'report.gr.specificgrade'
			];

			if (!$this->permissions->checkPermissions('export_grade_report')) {
				$data['export_report_permission'] = FALSE;
			} else {
				$data['export_report_permission'] = TRUE;
			}

			$data['allPlayerLevels'] = $this->group_level->getAllPlayerLevels();
			$data['get_only_success_grade_report'] =  $this->utils->getConfig('get_only_success_grade_report');

			$data['selected_tags'] = $this->input->get_post('tag_list');
			$data['tags'] = $this->player->getAllTagsOnly();
			$data['enable_freeze_top_in_list'] = $this->utils->_getEnableFreezeTopWithMethod(__METHOD__, $this->config->item('enable_freeze_top_method_list'));

			$this->loadTemplate(lang('Grade Report'), '', '', 'report');
			$this->template->add_css('resources/third_party/bootstrap-multiselect-master/dist/css/bootstrap-multiselect.css');
        	$this->template->add_js('resources/third_party/bootstrap-multiselect-master/dist/js/bootstrap-multiselect.js');
			$this->template->write_view('main_content', 'report_management/view_player_grade_report', $data);
			$this->template->render();
		}
	}

	public function viewCommunicationPreferenceReport($value='')
	{
		if (!$this->permissions->checkPermissions('view_communication_preference_report') || !$this->utils->isEnabledFeature('enable_communication_preferences'))
			return $this->error_access();

		$this->load->model('communication_preference_model');
		//die('<pre>'.print_r($this->group_level->getAllPlayerLevels(),true));
		$data['allPlayerLevels'] = $this->group_level->getAllPlayerLevels();
		$data['config_comm_pref'] = $this->utils->getConfig('communication_preferences');
		/// Patch OGP-13339 communication pref report shows http error 500
		// Non-used in view_communication_preference_report.php
		// $data['communication_preference_reports'] = $this->communication_preference_model->getCommunicationPreferenceHistory();
		$data['export_report_permission'] = $this->permissions->checkPermissions('export_communication_preference_report');

		// if (!$this->permissions->checkPermissions('export_report'))
		// 	$data['export_report_permission'] = FALSE;

		$this->loadTemplate(lang('Communication Preference Report'), '', '', 'report');
		$this->template->write_view('main_content', 'report_management/view_communication_preference_report', $data);
		$this->template->render();

	}

	/**
	 * Display income access report
	 * @return load template
	 */
	public function viewIncomeAccessReport()
	{
		if (!($this->permissions->checkPermissions('view_income_access_signup_report') || $this->permissions->checkPermissions('view_income_access_sales_report')) || !$this->utils->isEnabledFeature('enable_income_access'))
			return $this->error_access();

		$this->load->model('player_model');

		$from = $to = $username = null;

		if($this->input->get())
		{
			$from = $this->input->get('date_from') ?: null;
			$to = $this->input->get('date_to') ?: null;
			$username = $this->input->get('username') ?: null;
		}

		$data['daily_signup_data'] = $this->player_model->getDailySignupWithBtag($from, $to, $username);
		$data['daily_sales_data'] = $this->player_model->getDailySalesWithBtag($from, $to, $username);

		$data['export_signup_report_permission'] = $this->permissions->checkPermissions('export_income_access_signup_report');
		$data['export_sales_report_permission'] = $this->permissions->checkPermissions('export_income_access_sales_report');

		// OGP-10782 Remove export_report permission
		// if (!$this->permissions->checkPermissions('export_report')) {
		// 	$data['export_signup_report_permission'] = FALSE;
		// 	$data['export_sales_report_permission']  = FALSE;
		// }

		$this->loadTemplate('Income Access Report', '', '', 'report');
		$this->template->write_view('main_content', 'report_management/view_income_access_report', $data);
		$this->template->render();

	}

	public function viewSbobetGameReport() {
		$this->load->model(array('game_type_model','external_system'));
		$game_apis = $this->utils->getGameSystemMap(false);
		$api_id = $this->utils->getConfig('sbobet_game_report_platform_id');
        if(empty($api_id)){
        	$this->error_access();
			return;
        }
		// $active = $this->external_system->isGameApiActive(SBOBET_API);
		if (!$this->utils->isEnabledFeature('enabled_sbobet_sports_game_report') || !array_key_exists($api_id,$game_apis)) {
			$this->error_access();
			return;
		}
		if (!$this->permissions->checkPermissions('game_report')) {
			$this->error_access();
		} else {
			$this->load->helper('form');

			if (!$this->permissions->checkPermissions('export_games_report')) {
				$data['export_report_permission'] = FALSE;
			} else {
				$data['export_report_permission'] = TRUE;
			}

			$this->load->model(array('player'));

			$start_today = date("Y-m-d") . ' 00:00:00';
			$end_today = date("Y-m-d") . ' 23:59:59';
			$data['conditions'] = $this->safeLoadParams(array(
					'date_from' => $this->utils->getTodayForMysql(),
					'hour_from' => '00',
					'date_to' => $this->utils->getTodayForMysql(),
					'hour_to' => '23',
					'datetime_from' => $this->utils->getTodayForMysql().' '.Utils::FIRST_TIME,
					'datetime_to' => $this->utils->getTodayForMysql().' '.Utils::LAST_TIME,
					'datetime_from_timezone' =>  $this->utils->getTodayForMysql().' '.Utils::FIRST_TIME,
					'datetime_to_timezone' =>  $this->utils->getTodayForMysql().' '.Utils::LAST_TIME,
					'total_bet_from' => '',
					'total_bet_to' => '',
					'total_loss_from' => '',
					'total_loss_to' => '',
					'total_gain_from' => '',
					'total_gain_to' => '',
					'group_by' => '',
					'username' => '',
					'affiliate_username' => '',
					'agent_name' => '',
					'external_system' => '',
					'game_type' => '',
					'game_type_multiple' => '',
					'show_multiselect_filter' => '',
					'total_player' => '',
					'timezone' => '',
					'include_all_downlines' => '',
					'affiliate_agent' => '',
					'referrer' => '',
			));

			$data['game_apis_map'] = $game_apis;
			$data['platform_name'] = $this->external_system->getNameById(SBOBET_API);
			$data['mulitple_select_game_map']= $this->game_type_model->getActiveGamePlatformGameTypes();
			$data['conditions']['search_unsettle_game']= $this->input->get('search_unsettle_game')=='true';
			$data['enable_freeze_top_in_list'] = $this->utils->_getEnableFreezeTopWithMethod(__METHOD__, $this->config->item('enable_freeze_top_method_list'));

			$this->loadTemplate(lang('Games Report'), '', '', 'report');
			$this->template->write_view('main_content', 'report_management/games_report/view_sbobet_game_report', $data);
			$this->template->render();
		}
	}

	public function viewVRGameReport() {

		$this->load->model(array('game_type_model','external_system'));
		$game_apis = $this->utils->getGameSystemMap(false);
		$active = $this->external_system->isGameApiActive(VR_API);
		if (!$this->utils->isEnabledFeature('enabled_vr_game_report') || !array_key_exists(VR_API,$game_apis)) {
			$this->error_access();
			return;
		}
		if (!$this->permissions->checkPermissions('game_report')) {
			$this->error_access();
		} else {
			$this->load->helper('form');

			if (!$this->permissions->checkPermissions('export_games_report')) {
				$data['export_report_permission'] = FALSE;
			} else {
				$data['export_report_permission'] = TRUE;
			}

			$this->load->model(array('player'));

			$start_today = date("Y-m-d") . ' 00:00:00';
			$end_today = date("Y-m-d") . ' 23:59:59';
			$data['conditions'] = $this->safeLoadParams(array(
				'date_from' => $this->utils->getTodayForMysql(),
				'hour_from' => '00',
				'date_to' => $this->utils->getTodayForMysql(),
				'hour_to' => '23',
				'datetime_from' => $this->utils->getTodayForMysql().' '.Utils::FIRST_TIME,
				'datetime_to' => $this->utils->getTodayForMysql().' '.Utils::LAST_TIME,
				'datetime_from_timezone' =>  $this->utils->getTodayForMysql().' '.Utils::FIRST_TIME,
				'datetime_to_timezone' =>  $this->utils->getTodayForMysql().' '.Utils::LAST_TIME,
				'total_bet_from' => '',
				'total_bet_to' => '',
				'total_loss_from' => '',
				'total_loss_to' => '',
				'total_gain_from' => '',
				'total_gain_to' => '',
				'group_by' => '',
				'username' => '',
				'affiliate_username' => '',
				'agent_name' => '',
				'external_system' => '',
				'game_type' => '',
				'game_type_multiple' => '',
				'show_multiselect_filter' => '',
				'total_player' => '',
				'timezone' => '',
                'include_all_downlines' => '',
				'affiliate_agent' => '',
				'referrer' => '',
			));

			$data['game_apis_map'] = $game_apis;
			$data['platform_name'] = $this->external_system->getNameById(VR_API);
			$data['mulitple_select_game_map']= $this->game_type_model->getActiveGamePlatformGameTypes();
			$data['conditions']['search_unsettle_game']= $this->input->get('search_unsettle_game')=='true';

			$this->loadTemplate(lang('Games Report'), '', '', 'report');
			$this->template->write_view('main_content', 'report_management/games_report/view_vr_game_report', $data);
			$this->template->render();
		}
	}

	/**
	 * Display the lists of the Quest Report
	 * @return load template
	 */
	public function viewQuestReport() {
		$this->load->model(array('quest_manager'));

		$enable_timezone_query = false; // default
		$enable_timezone_query_method_list = $this->config->item('enable_timezone_query_method_list');
		if( !empty($enable_timezone_query_method_list) ){
			if( in_array(__METHOD__, $enable_timezone_query_method_list) ){ // __METHOD__, "Payment_management::deposit_list"
				$enable_timezone_query = true;
			}
		}
		$data['enable_timezone_query'] = $enable_timezone_query;

		$data['conditions'] = $this->safeLoadParams(array(
			'hour_from' => '00',
			'hour_to' => '23',
			'date_from' => $this->utils->getTodayForMysql().' '.Utils::FIRST_TIME,
			'date_to' => $this->utils->getTodayForMysql().' '.Utils::LAST_TIME,
			'category_id' => '',
			'manager_title' => '',
			'status' => '',
			'username' => '',
			'search_by' => '',
			'timezone' => '',
			'group_by' => '',
			'search_by_ip' => '',
			'ip_address' => '',
		));

		$data['export_report_permission'] = $this->permissions->checkPermissions('export_player_report');
		$data['allCategoryTitle'] = $this->quest_manager->getAllCategoryTitle();

		$this->loadTemplate(lang('report.s12'), '', '', 'report');
		// $this->template->add_js('resources/js/dataTables.fixedColumns.min.js'); // disable for the feature Not required
		$this->template->add_css('resources/third_party/bootstrap-multiselect-master/dist/css/bootstrap-multiselect.css');
		$this->template->add_js('resources/third_party/bootstrap-multiselect-master/dist/js/bootstrap-multiselect.js');
		$this->template->add_css('resources/floating_scroll/jquery.floatingscroll.css');
		$this->template->add_js('resources/floating_scroll/jquery.floatingscroll.js');
		$this->template->write_view('main_content', 'report_management/quest_report/view_quest_report', $data);
		$this->template->render();
	}

	/**
	 * detail: display a custom game report for the specific platform, dedicated to games like sports if BO using match date on filtering
	 * example : oneworks, sbobet, afb
	 * @param int $game_platform_id
	 * @return load template
	 */
	public function view_gameprovider_report_by_platform($game_platform_id) {

		$this->load->model(array('game_type_model','external_system'));
		$game_apis = $this->utils->getGameSystemMap(false);
		if (!array_key_exists($game_platform_id,$game_apis)) {
			$this->error_access();
			return;
		}
		if (!$this->permissions->checkPermissions('game_report')) {
			$this->error_access();
		} else {
			$this->load->helper('form');

			if (!$this->permissions->checkPermissions('export_games_report')) {
				$data['export_report_permission'] = FALSE;
			} else {
				$data['export_report_permission'] = TRUE;
			}

			$this->load->model(array('player'));

			$start_today = date("Y-m-d") . ' 00:00:00';
			$end_today = date("Y-m-d") . ' 23:59:59';
			$data['conditions'] = $this->safeLoadParams(array(
				'date_from' => $this->utils->getTodayForMysql(),
				'hour_from' => '00',
				'date_to' => $this->utils->getTodayForMysql(),
				'hour_to' => '23',
				'datetime_from' => $this->utils->getTodayForMysql().' '.Utils::FIRST_TIME,
				'datetime_to' => $this->utils->getTodayForMysql().' '.Utils::LAST_TIME,
				'datetime_from_timezone' =>  $this->utils->getTodayForMysql().' '.Utils::FIRST_TIME,
				'datetime_to_timezone' =>  $this->utils->getTodayForMysql().' '.Utils::LAST_TIME,
				'total_bet_from' => '',
				'total_bet_to' => '',
				'total_loss_from' => '',
				'total_loss_to' => '',
				'total_gain_from' => '',
				'total_gain_to' => '',
				'group_by' => '',
				'username' => '',
				'affiliate_username' => '',
				'agent_name' => '',
				'external_system' => '',
				'game_type' => '',
				'game_type_multiple' => '',
				'show_multiselect_filter' => '',
				'total_player' => '',
				'timezone' => '',
                'include_all_downlines' => '',
				'affiliate_agent' => '',
				'referrer' => '',
			));

			$data['game_platform_id'] = $game_platform_id;
			$data['game_apis_map'] = $game_apis;
			$data['platform_name'] = $this->external_system->getNameById($game_platform_id);
			$data['mulitple_select_game_map']= $this->game_type_model->getActiveGamePlatformGameTypes();
			$data['conditions']['search_unsettle_game']= $this->input->get('search_unsettle_game')=='true';
			$data['enable_freeze_top_in_list'] = $this->utils->_getEnableFreezeTopWithMethod(__METHOD__, $this->config->item('enable_freeze_top_method_list'));

			$this->loadTemplate(lang('Games Report'), '', '', 'report');
			$this->template->write_view('main_content', 'report_management/games_report/view_gameprovider_report', $data);
			$this->template->render();
		}
	}

	/**
	 *
	 * detail: display the lists of the Transactions Summary Reports
	 *
	 * @return load template
	 */
	public function transactionsSummaryReport()
	{
		if (!$this->permissions->checkPermissions('transactions_daily_summary_report'))
		{
			$this->error_access();
		} else {
			//export report permission checking
			if (!$this->permissions->checkPermissions('export_transactions_daily_summary_report')) {
				$data['export_report_permission'] = FALSE;
			} else {
				$data['export_report_permission'] = TRUE;
			}

			$data['conditions'] = $this->safeLoadParams(array(
				'by_transaction_date' => $this->utils->getTodayForMysql(),
				'by_username' => '',
				'by_balance_validation' => 'Not Tallied',
			));

			$data['enable_freeze_top_in_list'] = $this->utils->_getEnableFreezeTopWithMethod(__METHOD__, $this->config->item('enable_freeze_top_method_list'));

			$this->utils->debug_log($data['conditions']);
			$this->loadTemplate(lang('report.transactions_daily_summary_report'), '', '', 'report');
			$this->template->add_js('resources/js/bootstrap-switch.min.js');
			$this->template->add_css('resources/css/bootstrap-switch.min.css');
			$this->template->write_view('main_content', 'report_management/view_transaction_summary_report', $data);
			$this->template->render();
		}
	}

	/**
	 * Import the datatable row(s) into the tables, "hedging_total_detail_info" and "hedging_total_detail_player" from xls file by each row.
	 * @param void Recommand POST the followings,
	 * - $_POST[filename] string The xls file name.
	 * - $_POST[ids] array The index number of the xls file.
	 *
	 * @return string $jsonResult
	 */
	public function importHedgeInAG4HedgingDetailInfoXls(){
		if (!$this->permissions->checkPermissions('view_hedge_in_ag_upload')) {
			return $this->error_access();
		}

		$jsonResult = [];
		// $this->input->post()
		$importRanges = $this->safeLoadParams(array(
			'filename' => '',
			'ids' => null,
		));

		// $importRanges['filename'] // filename
		// $importRanges['ids'] // indexNumber

		$path = $this->getUploadPath4hedge_in_ag();
		$file_path = $path. DIRECTORY_SEPARATOR. $importRanges['filename']. '.xls';

		$sheetData = $this->utils->loadExcel($file_path);

		// print_r($sheetData);
		$row_result_list = [];
		if( ! empty($importRanges['ids']) ){
			foreach($importRanges['ids'] as $indexNumber){
				$row_result = $this->import_HedgingDetailInfo($sheetData[$indexNumber]);
				$row_result_list[] = $row_result;
			}
		}

		// calc for subtotal.
		$importedCounter = 0;
		$ignoreForExistsCounter = 0;
		if( ! empty($row_result_list) ){
			foreach($row_result_list as $row_result){
				if( ! empty($row_result['result']['result']) ){
					$importedCounter++;
				}
				if( ! empty($row_result['result']['rowInTable']) ){
					$ignoreForExistsCounter++;
				}
			}
		}

		$jsonResult['row_list'] = $row_result_list;
		$jsonResult['importedCounter'] = $importedCounter;
		$jsonResult['ignoreForExistsCounter'] = $ignoreForExistsCounter;
		$this->returnJsonResult($jsonResult);
	} // EOF importHedgeInAG4HedgingDetailInfoXls

	/**
	 * inpurt a row data
	 *
	 * @param array $theRow
	 * @return void
	 */
	public function import_HedgingDetailInfo($theRow){
		$this->load->model(['hedging_total_detail_info', 'hedging_total_detail_player']);

		$return = [];
		$return['result'] = null;
		$columns = $this->db->list_fields($this->hedging_total_detail_info->tableName);

		$table2xls = $this->hedging_total_detail_info->columnsMapping('table2xls');

		// theRow(the fields of xls)convert to params(the fields of table).
		$field_list = $this->hedging_total_detail_info->db->field_data($this->hedging_total_detail_info->tableName);
		$params = [];
		foreach($field_list as $field){

			if( ! in_array($field->name, $this->hedging_total_detail_info->exceptFieldsWhileImport) ){
				if( ! empty($table2xls[$field->name]) ){
					$theRowField = $table2xls[$field->name]; // 'A';
					$params[$field->name] =	$theRow[$theRowField];
				}
			}

		} // EOF foreach($field_list as $field){...

		// filter the invalid or empty row
		$doCheckUsernameExist = false; // disable to query player table by usernames
		$matchPlayerList = $this->hedging_total_detail_info->parseMembersField2player($params['members'], $this->utils->getConfig('agin_prefix_for_username'), $doCheckUsernameExist);

		if( ! empty($params) && ! empty($matchPlayerList) ){
			$md5sum = $this->hedging_total_detail_info->md5sum_row($theRow, []);
			$return['result'] = $this->hedging_total_detail_info->addByMd5sum($params);

			if( ! empty($return['result']['result']) ){ // imported
				$importedId = $return['result']['result'];
				$importedRow = $this->hedging_total_detail_info->getDetailById($importedId);

				$parsedMembers = []; // parse the members field for catch the player username
				if( ! empty($this->utils->getConfig('agin_prefix_for_username')) ){
					$members = $importedRow['members'];
					$parsedMembers = $this->hedging_total_detail_info->parseMembersField2player($members, $this->utils->getConfig('agin_prefix_for_username') );
				}
			}else if( ! empty($return['result']['rowInTable']) ){ // ignoreForExists
				$parsedMembers = []; // parse the members field for catch the player username
				$members = $return['result']['rowInTable']['members'];
				if( ! empty($this->utils->getConfig('agin_prefix_for_username')) ){
					$parsedMembers = $this->hedging_total_detail_info->parseMembersField2player($members, $this->utils->getConfig('agin_prefix_for_username') );
				}
			}
			// print_r('$parsedMembers:');
			// print_r($parsedMembers);
			// Array
			// (
			// 	[159805] => test002
			// )

			/// Add table_id and player_id into the table,"hedging_total_detail_player" for search.
			if( ! empty( $parsedMembers )){
				$return['result']['parsedMembers'] = [];
				foreach($parsedMembers as $player_id => $username){
					$table_id = $params['table_id'];
					$_params['table_id'] = $table_id;
					$_params['player_id'] = $player_id;
					$parsedMembersResult = $this->hedging_total_detail_player->addByTableAndPlayer($_params);
					$return['result']['parsedMembers'][] = $parsedMembersResult;
				} // EOF foreach($parsedMembers as $player_id => $username){...
			}



		}
		return $return;
	} // EOF import_HedgingDetailInfo

	/**
	 * Get the path for hedge_in_ag function,
	 *
	 * @return string the path.
	 */
	public function getUploadPath4hedge_in_ag(){
		$path = $this->utils->getUploadPath();
		$path .= DIRECTORY_SEPARATOR. 'hedge_in_ag';
		return $path;
	} // EOF getUploadPath4hedge_in_ag

	/**
	 * Upload the xls file
	 * @param void redirect to preview
	 * @return void
	 */
	public function uploadHedgeInAG4HedgingDetailInfoXls(){
		if (!$this->permissions->checkPermissions('view_hedge_in_ag_upload')) {
			return $this->error_access();
		}
		// print_r($_FILES);
		$path_image = $_FILES['userfile']['name'];
		$ext = pathinfo($path_image, PATHINFO_EXTENSION);

		// $keyWord = 'hedgeInAG';
		$keyWord = 'hedgingTotalDetailInfo';
		// $path = realpath(APPPATH . '../public/resources/'. $keyWord);

		// $path = $this->utils->getUploadPath();
		// $path .= '/'. 'hedge_in_ag';
		$path = $this->getUploadPath4hedge_in_ag();

		//upload image
		$tokenFileName = $this->utils->generateRandomCode(7,$keyWord);
		$config = array(
			'allowed_types' => '*', // 'xlsx|csv|xls', need change mimetype.
			'upload_path' => $path,
			'max_size' => $this->utils->getMaxUploadSizeByte(),
			'overwrite' => true,
			'file_name' => $tokenFileName,
			// 'remove_spaces' => true,
		);
		$this->load->library('upload', $config);
		$result = $this->upload->do_upload('userfile');
		$uploaded = ($result) ? $this->CI->upload->file_name : FALSE;

		if( substr($uploaded, -4) != '.xls' ){
			$message = lang('Note: Upload file format must be XLS.');
			$this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);
			redirect('report_management/viewHedgeInAG4upload');

		}else if( empty($result) ){
			$err = $this->upload->display_errors();
			$message = lang('Upload Failed');
			$message .= '<br>';
			$message .= '( '. $err. ' )';
			$this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);
			redirect('/report_management/viewHedgeInAG4upload');
		}else{
			redirect('/report_management/viewHedgeInAG4preview/'.$tokenFileName);
		}
	} // EOF uploadHedgeInAG4HedgingDetailInfoXls

	/**
	 * The preview page after upload the field.
	 *
	 * @param string $tokenFileName
	 * @return void
	 */
	public function viewHedgeInAG4preview($tokenFileName = null){
		if (!$this->permissions->checkPermissions('view_hedge_in_ag_upload')) {
			return $this->error_access();
		}
		$this->load->model(['hedging_total_detail_info', 'hedging_total_detail_player']);

		// $path = $this->utils->getUploadPath();
		// $path .= DIRECTORY_SEPARATOR. 'hedge_in_ag';
		$path = $this->getUploadPath4hedge_in_ag();

		$file_path = $path. DIRECTORY_SEPARATOR. $tokenFileName. '.xls';

		$errorMessage = null;
		$sheetData = [];

		try {
			$sheetData = $this->utils->loadExcel($file_path);
		} catch (Exception $e) {
			$errorMessage = $e->getMessage();
		}

		$columnsMapping = $this->hedging_total_detail_info->columnsMapping('table2xls');

		if( empty($sheetData) ){
			/// redirect
			$message = lang('The file is Empty. Please upload again.');
			if( ! empty( $errorMessage )) {
				$message .= '<br>';
				$message .= '( '. $errorMessage. ' )';
			}
			$this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);
			return redirect('/report_management/viewHedgeInAG4upload');
		}else if( count($sheetData[1]) != count($columnsMapping)){
			/// redirect
			$message = lang('Invalid file content.');
			$message .= '<br>';
			$message .= lang('Please Download and reference to the Sample file.');

			$this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);
			return redirect('/report_management/viewHedgeInAG4upload');
		}


		$data = [];
		$data['tokenFileName'] = $tokenFileName;
		$data['sheetData'] = $sheetData;
		$this->loadTemplate(lang('report.hedge_in_ag_report'), '', '', 'report');
		// $this->template->add_js('resources/js/bootstrap-switch.min.js');
		// $this->template->add_css('resources/css/bootstrap-switch.min.css');
		$this->template->write_view('main_content', 'report_management/view_hedge_in_ag_preview', $data);
		$this->template->render();
	} // EOF  viewHedgeInAG4preview

	/**
	 * The upload xls file page
	 */
	public function viewHedgeInAG4upload(){
		if (!$this->permissions->checkPermissions('view_hedge_in_ag_upload')) {
			return $this->error_access();
		}

		$this->load->model(['hedging_total_detail_info', 'hedging_total_detail_player']);

		$data = [];
		$this->loadTemplate(lang('report.hedge_in_ag_report'), '', '', 'report');
		// $this->template->add_js('resources/js/bootstrap-switch.min.js');
		// $this->template->add_css('resources/css/bootstrap-switch.min.css');
		$this->template->write_view('main_content', 'report_management/view_hedge_in_ag_upload', $data);
		$this->template->render();
	}

	/**
	 * The search player in HedgingDetailInfo.xls
	 *
	 * @return void
	 */
	public function viewHedgeInAG4playerList(){ // for HedgingDetailInfo.xls

		if (!$this->permissions->checkPermissions('view_hedge_in_ag_player_list')) {
			return $this->error_access();
		}

		$this->load->model(['hedging_total_detail_info', 'hedging_total_detail_player']);

		$data['conditions'] = $this->safeLoadParams(array(
			'by_date_from' => $this->utils->getTodayForMysql(),
			'by_date_to' => $this->utils->getTodayForMysql(),
			'by_username' => '',
			'by_result' => '',
			'by_status' => '',
		));

		$data['result_list'] = array(
			'' => lang('N/A'),
			'' => lang('All'),
			// Iovation_logs::ALLOW => lang('report.iovationallow'),
			// Iovation_logs::DENY => lang('report.iovationdeny'),
			// Iovation_logs::REVIEW => lang('report.iovationreview'),
		);
		$data['status_list'] = array(
			'' => lang('N/A'),
			'' => lang('All'),
			// Iovation_logs::SUCCESS => lang('Success'),
			// Iovation_logs::FAILED => lang('Failed'),
		);

		// if (!$this->permissions->checkPermissions('export_payment_report')) {
		// 	$data['export_report_permission'] = FALSE;
		// } else {
		// 	$data['export_report_permission'] = true;
		// }


		$hide_cols = [];
		$data['hide_cols'] = $hide_cols;

		// admin/application/controllers/hedge_in_AG.php

		// // $filename = 'HedgingDetailInfo.xls';
		// $filename = 'HedgingTotalDetailInfo.xls';
		// $file = './'. $filename;
		// $rlt = $this->utils->loadExcel($file);
		// echo '<pre>';
		// print_r($rlt);

		$data['enable_freeze_top_in_list'] = $this->utils->_getEnableFreezeTopWithMethod(__METHOD__, $this->config->item('enable_freeze_top_method_list'));

		$this->loadTemplate(lang('report.hedge_in_ag_report'), '', '', 'report');
		// $this->template->add_js('resources/js/bootstrap-switch.min.js');
		// $this->template->add_css('resources/css/bootstrap-switch.min.css');
		$this->template->write_view('main_content', 'report_management/view_hedge_in_ag_player_list', $data);
		$this->template->render();


		// $this->template->write_view('main_content', 'report_management/view_iovation_report', $data);
		// $this->template->render();
	} // EOF viewHedgeInAG4playerList


	public function viewPlayerLoginViaSameIp(){
		$this->load->model(['operatorglobalsettings', 'player_login_via_same_ip_logs']);
		if (!$this->permissions->checkPermissions('view_player_login_via_same_ip_list')) {
			return $this->error_access();
		}

		if (!$this->permissions->checkPermissions(['tag_player'])) {
			// for select tag name detected.

		}

		$from = $this->utils->getNowSub(600); // 600 has ref. by the setting of config, moniter_player_login_via_same_ip.query_interval
		$to = $this->utils->getNowForMysql();
		/// alert_command_module::monitorManyPlayerLoginViaSameIp() has referred the params,
		// created_at_enabled_date, logged_in_at_enabled_date, logged_in_at_date_from and logged_in_at_date_to
		$data['conditions'] = $this->safeLoadParams(array(
			'created_at_date_from' => $from,
			'created_at_date_to' => $to,
			'created_at_enabled_date' => 1,
			'logged_in_at_date_from' => $from,
			'logged_in_at_date_to' => $to,
			'logged_in_at_enabled_date' => 0,
			'search_by' => 2,
			'username' => '',
		));


		$data['tags'] = $this->player_manager->getAllTags();

		$detected_tag_id = $this->operatorglobalsettings->getSettingValueWithoutCache(Player_login_via_same_ip_logs::_operator_setting_name4detected_tag_id);
		if( empty($detected_tag_id) ){
			// operator global settings Not found the setting, Player_login_via_same_ip_logs::_operator_setting_name4detected_tag_id.
			$defaultTagId = $this->player_login_via_same_ip_logs->getTagIdByTagNameDetectedOfConfig();
			$detected_tag_id = $defaultTagId;
		}
		$currect_tag_id = 0;
		if( ! empty($detected_tag_id) ){
			/// the tags will be updated/deleted any time
			// this is why its need to detect, the tag is exist in all cases
			$tagName = $this->player_model->getTagNameByTagId($detected_tag_id);
			if( ! empty($tagName) ){
				$currect_tag_id = $detected_tag_id;
			}
		}
		$data['conditions']['detected_tag']	= $currect_tag_id; // get from operator_settings, default by config

		$this->loadTemplate(lang('report.viewPlayerLoginViaSameIp'), '', '', 'report');
		// $this->template->add_js('resources/js/bootstrap-switch.min.js');
		// $this->template->add_css('resources/css/bootstrap-switch.min.css');
		$this->template->add_js('resources/js/report_management/view_player_login_via_same_ip.js');


		$this->template->write_view('main_content', 'report_management/view_player_login_via_same_ip', $data);
		$this->template->render();
 	}


	/**
	 * detail: view daily report
	 *
	 * @return load template
	 */
	public function viewIovationReport() {
		if (!$this->permissions->checkPermissions('view_and_operate_iovation_report')) {
			return $this->error_access();
		}

		$this->load->model(array('group_level', 'transactions', 'iovation_logs'));

		$data['conditions'] = $this->safeLoadParams(array(
			'by_date_from' => $this->utils->getTodayForMysql(),
			'by_date_to' => $this->utils->getTodayForMysql(),
			'by_username' => '',
			'by_result' => '',
			'by_status' => '',
			'by_device_id' => '',
			'by_user_type' => '',
			'by_type' => '',
		));

		$data['result_list'] = array(
			'' => lang('N/A'),
			'' => lang('All'),
			Iovation_logs::ALLOW => lang('report.iovationallow'),
			Iovation_logs::DENY => lang('report.iovationdeny'),
			Iovation_logs::REVIEW => lang('report.iovationreview'),
		);
		$data['status_list'] = array(
			'' => lang('N/A'),
			'' => lang('All'),
			Iovation_logs::SUCCESS => lang('Success'),
			Iovation_logs::FAILED => lang('Failed'),
		);
		$data['user_type_list'] = array(
			'' => lang('N/A'),
			'player' => lang('Player'),
			'affiliate' => lang('Affiliate')
		);

		$data['type_list'] = array(
			'' => lang('All'),
			Iovation_logs::LOG_TYPE_registration => lang('Player Registration'),
			Iovation_logs::LOG_TYPE_affiliateRegistration => lang('Affiliate Registration'),
			Iovation_logs::LOG_TYPE_affiliateLogin => lang('Affiliate Login'),
			Iovation_logs::LOG_TYPE_promotion => lang('Player Promotion'),
			Iovation_logs::LOG_TYPE_depositSelectPromotion => lang('Player Deposit'),
		);

		if (!$this->permissions->checkPermissions('export_payment_report')) {
			$data['export_report_permission'] = FALSE;
		} else {
			$data['export_report_permission'] = TRUE;
		}

		/**
		 * Hide unwanted columns by js for html output
		 * columns: (as of OGP-11982)
		 * 0 - date
		 * 1 - player-username
		 * 2 - aff-user
		 * 3 - agent-user
		 * 4 - refer-user
		 * 5 - adm-user
		 * 6 - group-level
		 * 7 - pay-type (coll-account)
		 * 8 - promo-cat
		 * 9 - tx-type
		 * 10 - amount
		 */

		$hide_cols = [];


		$data['hide_cols'] = $hide_cols;

		$data['enable_freeze_top_in_list'] = $this->utils->_getEnableFreezeTopWithMethod(__METHOD__, $this->config->item('enable_freeze_top_method_list'));

		$this->loadTemplate(lang('report.iovation_report'), '', '', 'report');
		$this->template->add_js('resources/js/bootstrap-switch.min.js');
		$this->template->add_css('resources/css/bootstrap-switch.min.css');
		$this->template->write_view('main_content', 'report_management/view_iovation_report', $data);
		$this->template->render();
	}

	/**
	 * detail: view daily report
	 *
	 * @return load template
	 */
	public function viewIovationEvidence() {
		if (!$this->permissions->checkPermissions('view_and_operate_iovation_report')) {
			return $this->error_access();
		}

		$this->load->model(array('group_level', 'transactions', 'iovation_evidence'));

		$data['conditions'] = $this->safeLoadParams(array(
			'by_date_from' => $this->utils->getTodayForMysql(),
			'by_date_to' => $this->utils->getTodayForMysql(),
			'by_status' => '',
			'by_username' => '',
			'by_evidence_id' => '',
			'by_device_id' => '',
			'by_evidence_type' => '',
			'by_user_type' => '',
		));
		$data['status_list'] = array(
			'' => lang('N/A'),
			'' => lang('All'),
			Iovation_evidence::SUCCESS => lang('Success'),
			Iovation_evidence::FAILED => lang('Failed'),
		);
		$data['user_type_list'] = array(
			'' => lang('N/A'),
			'player' => lang('Player'),
			'affiliate' => lang('Affiliate')
		);

		$data['evidence_type_list'] = array(
			'' => lang('N/A'),
			'' => lang('All'),
			'1-1' => lang('iovation.evidencetype1.1'),
			'1-2' => lang('iovation.evidencetype1.2'),
			'1-3' => lang('iovation.evidencetype1.3'),
			'1-4' => lang('iovation.evidencetype1.4'),
			'1-5' => lang('iovation.evidencetype1.5'),
			'1-6' => lang('iovation.evidencetype1.6'),
			'1-7' => lang('iovation.evidencetype1.7'),
			'1-8' => lang('iovation.evidencetype1.8'),
			'1-9' => lang('iovation.evidencetype1.9'),
			'1-10' => lang('iovation.evidencetype1.10'),
			'1-11' => lang('iovation.evidencetype1.11'),
			'1-12' => lang('iovation.evidencetype1.12'),

			'2-1' => lang('iovation.evidencetype2.1'),
			'2-2' => lang('iovation.evidencetype2.2'),
			'2-3' => lang('iovation.evidencetype2.3'),
			'2-4' => lang('iovation.evidencetype2.4'),

			'3-1' => lang('iovation.evidencetype3.1'),
			'3-2' => lang('iovation.evidencetype3.2'),
			'3-3' => lang('iovation.evidencetype3.3'),
			'3-4' => lang('iovation.evidencetype3.4'),
			'3-5' => lang('iovation.evidencetype3.5'),
			'3-51' => lang('iovation.evidencetype3.51'),
			'3-52' => lang('iovation.evidencetype3.52'),
			'3-53' => lang('iovation.evidencetype3.53'),
			'3-54' => lang('iovation.evidencetype3.54'),
			'3-55' => lang('iovation.evidencetype3.55'),
			'3-56' => lang('iovation.evidencetype3.56'),
			'3-57' => lang('iovation.evidencetype3.57'),
			'3-58' => lang('iovation.evidencetype3.58'),
			'3-59' => lang('iovation.evidencetype3.59'),
			'3-6' => lang('iovation.evidencetype3.6'),
			'3-7' => lang('iovation.evidencetype3.7'),
			'3-8' => lang('iovation.evidencetype3.8'),
			'3-9' => lang('iovation.evidencetype3.9'),
			'3-10' => lang('iovation.evidencetype3.10'),
			'3-11' => lang('iovation.evidencetype3.11'),
			'3-12' => lang('iovation.evidencetype3.12'),

			'4-1' => lang('iovation.evidencetype4.1'),
			'4-2' => lang('iovation.evidencetype4.2'),
			'4-3' => lang('iovation.evidencetype4.3'),
			'4-4' => lang('iovation.evidencetype4.4'),
			'4-5' => lang('iovation.evidencetype4.5'),

			'5-1' => lang('iovation.evidencetype5.1'),
			'5-2' => lang('iovation.evidencetype5.2'),
			'5-3' => lang('iovation.evidencetype5.3'),
			'5-4' => lang('iovation.evidencetype5.4'),
			'5-5' => lang('iovation.evidencetype5.5'),
			'5-6' => lang('iovation.evidencetype5.6'),
			'5-7' => lang('iovation.evidencetype5.7'),

			'10-1' => lang('iovation.evidencetype10.1'),
			'10-2' => lang('iovation.evidencetype10.2'),
			'10-3' => lang('iovation.evidencetype10.3'),
			'10-4' => lang('iovation.evidencetype10.4'),
			'10-5' => lang('iovation.evidencetype10.5'),
			'10-6' => lang('iovation.evidencetype10.6'),

			'99-1' => lang('iovation.evidencetype99.1'),
			'99-2' => lang('iovation.evidencetype99.2'),
			'99-3' => lang('iovation.evidencetype99.3'),

			'100-1' => lang('iovation.evidencetype100.1'),
		);

		/**
		 * Hide unwanted columns by js for html output
		 * columns: (as of OGP-11982)
		 * 0 - date
		 * 1 - player-username
		 * 2 - aff-user
		 * 3 - agent-user
		 * 4 - refer-user
		 * 5 - adm-user
		 * 6 - group-level
		 * 7 - pay-type (coll-account)
		 * 8 - promo-cat
		 * 9 - tx-type
		 * 10 - amount
		 */

		$hide_cols = [];


		$data['hide_cols'] = $hide_cols;

		$data['enable_freeze_top_in_list'] = $this->utils->_getEnableFreezeTopWithMethod(__METHOD__, $this->config->item('enable_freeze_top_method_list'));

		// Test for commit insert to comment of jira, again.
		$this->loadTemplate(lang('report.iovation_evidence'), '', '', 'report');
		$this->template->add_css('resources/css/player_management/tag_player_list.css');
			$this->template->add_js('resources/js/bootstrap-switch.min.js');
		$this->template->add_css('resources/css/bootstrap-switch.min.css');
		$this->template->write_view('main_content', 'report_management/view_iovation_evidence', $data);
		$this->template->render();
	}

	/**
	 * overview : tagged player  of tags to remove
	 *
	 * detail : view page for black list
	 */


	/**
	 * overview : tagged player  of tags to remove
	 *
	 * detail : view page for black list
	 */
	public function iovationEvidenceBatchAction() {
		$this->utils->debug_log('running batch_remove_playertag_ids');
		$data = array('title' => lang('Batch Remove Player tags'), 'sidebar' => 'player_management/sidebar',
			'activenav' => 'tag_player');

        $this->loadTemplate(lang('report.iovation_evidence'), '', '', 'report');
        $this->template->add_js('resources/js/bootstrap-switch.min.js');
        $this->template->add_css('resources/css/bootstrap-switch.min.css');

		//check parameters
		$this->CI->load->library('data_tables');
		$request = $this->input->post('json_search');
		$request = json_decode($request, true);
		$input = $this->CI->data_tables->extra_search($request);
		$data = [];
		$data['evidenceIds'] = isset($input['evidenceIds'])?(array)$input['evidenceIds']:[];

		if(empty($data['evidenceIds'])){
			$message = lang('Please evidence to remove.');
			$this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);
			redirect('/report_management/viewIovationEvidence');
			exit;
		}

		$this->template->write_view('sidebar', 'player_management/sidebar');
		$this->template->write_view('main_content', 'report_management/view_evidence_remove', $data);
		$this->template->render();
	}

	/**
	 * detail: view view_player_achieve_threshold_report
	 *
	 * @return load template
	 */
	public function view_player_achieve_threshold_report() {
		if (!$this->permissions->checkPermissions('show_player_deposit_withdrawal_achieve_threshold')) {
			return $this->error_access();
		}

		$this->load->model(array('player_dw_achieve_threshold', 'transactions'));

		$data['conditions'] = $this->safeLoadParams(array(
			'by_date_from' => $this->utils->getTodayForMysql(). ' 00:00:00',
			'by_date_to' => $this->utils->getTodayForMysql(). ' 23:59:59',
			'by_username' => '',
			'by_status' => '',
		));

		$data['status_list'] = array(
			'' => lang('N/A'),
			Player_dw_achieve_threshold::ACHIEVE_THRESHOLD_DEPOSIT => lang('sys.achieve.threshold.deposit'),
			Player_dw_achieve_threshold::ACHIEVE_THRESHOLD_WITHDRAWAL => lang('sys.achieve.threshold.withdrawal'),
		);

		$data['enable_freeze_top_in_list'] = $this->utils->_getEnableFreezeTopWithMethod(__METHOD__, $this->config->item('enable_freeze_top_method_list'));

		$this->loadTemplate(lang('sys.achieve.threshold.report'), '', '', 'report');
		$this->template->add_js('resources/js/bootstrap-switch.min.js');
		$this->template->add_css('resources/css/bootstrap-switch.min.css');
		$this->template->write_view('main_content', 'report_management/view_player_achieve_threshold_report', $data);
		$this->template->render();
	}

	/**
	 * detail: display game report lists
	 *
	 * @return load template
	 */
	public function viewGamesReportTimezone() {
		$this->load->model('game_type_model');

		if (!$this->permissions->checkPermissions('game_report')) {
			$this->error_access();
		} else {
			$games_with_report_timezone = $this->utils->getConfig('games_with_report_timezone');
			if(empty($games_with_report_timezone)){
				return $this->error_access(lang("No available game for report!"));
			}
			$this->load->helper('form');

			if (!$this->permissions->checkPermissions('export_games_report')) {
				$data['export_report_permission'] = FALSE;
			} else {
				$data['export_report_permission'] = TRUE;
			}

			$this->load->model(array('player'));
			$data['conditions'] = $this->safeLoadParams(array(
				'date_from' => $this->utils->getTodayForMysql(),
				'hour_from' => '00',
				'date_to' => $this->utils->getTodayForMysql(),
				'hour_to' => '23',
				'datetime_from' => $this->utils->getTodayForMysql().' '.Utils::FIRST_TIME,
				'datetime_to' => $this->utils->getTodayForMysql().' '.Utils::LAST_TIME,
				'datetime_from_timezone' =>  $this->utils->getTodayForMysql().' '.Utils::FIRST_TIME,
				'datetime_to_timezone' =>  $this->utils->getTodayForMysql().' '.Utils::LAST_TIME,
				'total_bet_from' => '',
				'total_bet_to' => '',
				'total_loss_from' => '',
				'total_loss_to' => '',
				'total_gain_from' => '',
				'total_gain_to' => '',
				'group_by' => '',
				'username' => '',
				'affiliate_username' => '',
				'agent_name' => '',
				'external_system' => '',
				'game_type' => '',
				'game_type_multiple' => '',
				'show_multiselect_filter' => '',
				'total_player' => '',
				'timezone' => '',
                'include_all_downlines' => '',
				'affiliate_agent' => '',
				'referrer' => '',
				'include_all_downlines_aff' => '',
			));


			$show_blocked_game_api_data_on_games_report = $this->utils->getConfig('show_blocked_game_api_data_on_games_report');

			$data['game_apis_map'] = [];
			$data['mulitple_select_game_map']=[];

			if($show_blocked_game_api_data_on_games_report === true){
				$data['game_apis_map'] = $this->utils->getGameSystemMap(false);
				foreach ($data['game_apis_map']  as $game_platform_id => $value) {
					if(!in_array($game_platform_id, $games_with_report_timezone)){
						continue;
					}
					$game_type_rows = $this->game_type_model->getAllGameTypeList([$game_platform_id]);
					foreach ($game_type_rows as $game_type_row) {
						$data['mulitple_select_game_map'][$game_platform_id][] = $game_type_row;
					}
				}
			}else{
				$data['game_apis_map'] = $this->utils->getGameSystemMap(true);
				if(!empty($data['game_apis_map'])){
					foreach ($data['game_apis_map'] as $key => $value) {
						if(!in_array($key, $games_with_report_timezone)){
							unset($data['game_apis_map'][$key]);
						}
					}
				}

				$data['mulitple_select_game_map']= $this->game_type_model->getActiveGamePlatformGameTypes();
				if(!empty($data['mulitple_select_game_map'])){
					foreach ($data['mulitple_select_game_map'] as $key => $value) {
						if(!in_array($key, $games_with_report_timezone)){
							unset($data['mulitple_select_game_map'][$key]);
						}
					}
				}
			}
			if(empty($data['game_apis_map'])){
				return $this->error_access(lang("No available game for report!"));
			}

			$data['conditions']['search_unsettle_game']= $this->input->get('search_unsettle_game')=='true';

			$data['enable_freeze_top_in_list'] = $this->utils->_getEnableFreezeTopWithMethod(__METHOD__, $this->config->item('enable_freeze_top_method_list'));

			$this->loadTemplate(lang('report.s07'), '', '', 'report');
			$this->template->write_view('main_content', 'report_management/games_report/view_games_report_timezone', $data);
			$this->template->render();
		}
	}

	public function viewShoppingPointReport() {
		$this->load->model('affiliatemodel');
        if (!$this->permissions->checkPermissions('shopping_center_manager')) {
            $this->error_access();
        } else {
            $data['conditions'] = $this->safeLoadParams(array(
                'hour_from' => '00',
                'hour_to' => '23',
                'date_from' => $this->utils->getTodayForMysql().' '.Utils::FIRST_TIME,
                'date_to' => $this->utils->getTodayForMysql().' '.Utils::LAST_TIME,
                'depamt2' => '',
                'depamt1' => '',
                'playerlevel' => '',
                'group_by' => '',
                'username' => '',
                'search_by' => '',
				'affiliate_agent' => '',
				'point_type' => '1'
            ));
            $data['export_report_permission'] = $this->permissions->checkPermissions('export_player_report');

            $data['allLevels'] = $this->player_manager->getAllPlayerLevels();

			//OGP-25040
			$data['selected_tags'] = $this->input->get_post('tag_list');
			$data['tags'] = $this->player->getAllTagsOnly();

            $this->loadTemplate(lang('report.s11'), '', '', 'report');
            $this->template->add_css('resources/floating_scroll/jquery.floatingscroll.css');
            $this->template->add_js('resources/floating_scroll/jquery.floatingscroll.js');
			$this->template->add_css('resources/third_party/bootstrap-multiselect-master/dist/css/bootstrap-multiselect.css');
        	$this->template->add_js('resources/third_party/bootstrap-multiselect-master/dist/js/bootstrap-multiselect.js');
            $this->template->write_view('main_content', 'report_management/point_report/view_shopping_point_report', $data);
            $this->template->render();
        }
	}

	/**
	 * detail: viewPlayerLoginReport
	 *
	 * @return load template
	 */
	public function viewPlayerLoginReport() {
		if (!$this->permissions->checkPermissions('view_player_login_report')) {
			return $this->error_access();
		}

		$this->load->model(array('player_login_report', 'player_model','http_request'));

		$data['conditions'] = $this->safeLoadParams(array(
			'by_date_from' => $this->utils->getTodayForMysql(). ' 00:00:00',
			'by_date_to' => $this->utils->getTodayForMysql(). ' 23:59:59',
			'by_username' => '',
			'search_by' => 1,
			'by_login_result' => '',
			'by_player_status' => '',
			'by_login_from' => '',
			'login_ip' => '',
			'by_client_end' => '',
            'group_by' => player_login_report::GROUP_BY_NONE,
		));

		$data['login_result'] = array(
			'' => lang('All'),
			player_login_report::LOGIN_SUCCESS => lang('player_login_report_login_success'),
			player_login_report::LOGIN_FAILED => lang('player_login_report_login_failed'),
		);

		$data['player_status'] = array(
			'' => lang('All'),
			0 => lang('status.normal'),
			player_model::BLOCK_STATUS => lang('Blocked'),
			player_model::SUSPENDED_STATUS => lang('Suspended'),
			player_model::SELFEXCLUSION_STATUS => lang('Self Exclusion'),
			player_model::STATUS_BLOCKED_FAILED_LOGIN_ATTEMPT => lang('Failed Login Attempt'),
		);

		$data['login_from'] = array(
			'' => lang('All'),
			player_login_report::LOGIN_FROM_ADMIN => lang('player_login_report_login_from_admin'),
			player_login_report::LOGIN_FROM_PLAYER => lang('player_login_report_login_from_player'),
		);

		$data['client_end'] = array(
			'' => lang('All'),
			Http_request::HTTP_BROWSER_TYPE_PC => lang('PC'),
			Http_request::HTTP_BROWSER_TYPE_MOBILE => lang('MOBILE'),
			Http_request::HTTP_BROWSER_TYPE_IOS => lang('APP IOS'),
			Http_request::HTTP_BROWSER_TYPE_ANDROID => lang('APP ANDROID'),
		);
        $data['group_by'] = array(
			player_login_report::GROUP_BY_NONE => '-'. lang('None'). '-',
			player_login_report::GROUP_BY_CLIENTEND_PLAYER => lang('Client End and Player'),
			player_login_report::GROUP_BY_CLIENTEND_LOGINIP => lang('Client End and Login IP'),
            player_login_report::GROUP_BY_USERNAME_CLIENTEND_LOGINIP => lang('Username, Login IP and Client End'),
		);

		$data['export_report_permission'] = $this->permissions->checkPermissions('export_player_report');
		$data['enable_freeze_top_in_list'] = $this->utils->_getEnableFreezeTopWithMethod(__METHOD__, $this->config->item('enable_freeze_top_method_list'));

		$this->loadTemplate(lang('Player Login Report'), '', '', 'report');
		$this->template->add_js('resources/js/bootstrap-switch.min.js');
		$this->template->add_css('resources/css/bootstrap-switch.min.css');
		$this->template->write_view('main_content', 'report_management/view_player_login_report', $data);
		$this->template->render();
	}

		/**
	 * detail: viewRouletteReport
	 *
	 * @return load template
	 */
	public function viewRouletteReport() {
		if (!$this->permissions->checkPermissions('view_roulette_report')) {
			return $this->error_access();
		}

		$this->load->model(array('roulette_api_record', 'player_model'));

		$sortSetting = $this->utils->getConfig('promo_application_list_default_sort');
        $data['promoList'] = $this->promorules->getPromoSettingList($sortSetting['sort'], null, null, false, $sortSetting['orderBy']);

		$data['conditions'] = $this->safeLoadParams(array(
			'by_date_from' => $this->utils->getTodayForMysql(). ' 00:00:00',
			'by_date_to' => $this->utils->getTodayForMysql(). ' 23:59:59',
			'by_prize_from' => $this->utils->getTodayForMysql(). ' 00:00:00',
			'by_prize_to' => $this->utils->getTodayForMysql(). ' 23:59:59',
			'by_username' => '',
			'promoCmsSettingId' => '',
			'by_roulette_name' => '',
			'by_product_id' => '',
			'by_affiliate' => ''
		));

		$r_settings = $this->CI->utils->getConfig('roulette_reward_odds_settings');
		$roulette_name_types = roulette_api_record::ROULETTE_NAME_TYPES;

		$get_rname = array_keys($r_settings);
		$rname_data = [];
		$rsettings = [];
		foreach ($get_rname as $key => $val) {
			if (array_key_exists($val, $roulette_name_types)) {
				//map roulette name array
				$rname_data[$val] = $roulette_name_types[$val];
				//map roulette settings array
				$rsettings[$rname_data[$val]] = $r_settings[$val];
			}
		}

		$data['r_name'] = $rname_data;
		$data['r_settings'] = $rsettings;
		$data['all_prize'] = [];

		if ($data['conditions']['by_roulette_name'] != '') {
			$rsettings = $rsettings[$data['conditions']['by_roulette_name']] == '' ? false : $rsettings[$data['conditions']['by_roulette_name']];

			if ($rsettings) {
				$ro_data = [];
				foreach ($rsettings as $key => $value) {
					if (isset($value['product_id'])) {
						$ro_data[$value['product_id']] = lang($value['prize']);
					}
				}
				$data['all_prize'] = $ro_data;
			}
		}

		$data['export_report_permission'] = $this->permissions->checkPermissions('view_roulette_report');

		$this->loadTemplate(lang('Player Roulette Report'), '', '', 'report');
		$this->template->add_js('resources/js/bootstrap-switch.min.js');
		$this->template->add_css('resources/css/bootstrap-switch.min.css');
		$this->template->write_view('main_content', 'report_management/view_roulette_report', $data);
		$this->template->render();
	}

	/**
	 * detail: view viewAdjustmentScoreReport
	 *
	 * @return load template
	 */
	public function viewAdjustmentScoreReport() {
		if (!$this->permissions->checkPermissions('view_adjustment_score_report')) {
			return $this->error_access();
		}

		$this->load->model(array('player_score_model','player_model'));

		$data['conditions'] = $this->safeLoadParams(array(
			'by_date_from' => $this->utils->getTodayForMysql(). ' 00:00:00',
			'by_date_to' => $this->utils->getTodayForMysql(). ' 23:59:59',
			'search_by' => 1,
			'by_username' => '',
			'by_score_type' => ''
		));

		$data['transaction_type'] = array(
			'' => lang('All'),
			Player_score_model::MANUAL_ADD_SCORE => lang('score_history.add'),
			Player_score_model::MANUAL_SUBTRACT_SCORE => lang('score_history.subtract'),
		);

		$data['all_players'] = $this->player_model->getPlayersList();
		$userId = $this->authentication->getUserId();
		$data['double_submit_hidden_field'] = $this->initDoubleSubmitAndReturnHiddenFieldForAdmin($userId);
		$data['export_report_permission'] = $this->permissions->checkPermissions('view_adjustment_score_report');

		$this->loadTemplate(lang('score_history.title'), '', '', 'report');
		$this->template->add_css('resources/third_party/bootstrap-multiselect-master/dist/css/bootstrap-multiselect.css');
        $this->template->add_js('resources/third_party/bootstrap-multiselect-master/dist/js/bootstrap-multiselect.js');
		$this->template->add_js('resources/js/bootstrap-switch.min.js');
		$this->template->add_css('resources/css/bootstrap-switch.min.css');
		$this->template->write_view('main_content', 'report_management/view_adjustment_score_report', $data);
		$this->template->render();
	}

	public function adjust_player_score_post() {
		$this->utils->debug_log(__METHOD__,'post' , $this->input->post());

		if (!$this->permissions->checkPermissions('execute_adjustment_score_report')) {
			$this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('con.pym01'));
			redirect('report_management/viewAdjustmentScoreReport');
			return;
		}

		$this->load->model(array('player_score_model','player_model'));

		$userId = $this->authentication->getUserId();

		if(!$this->verifyAndResetDoubleSubmitForAdmin($userId)){
			$message = lang('Please refresh and try, and donot allow double submit');
			$this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);
			redirect('report_management/viewAdjustmentScoreReport');
			return;
		}

		if ($this->utils->getConfig('enabled_batch_adjust_player_score')) {
			$player_id = $this->input->post('all_players');
		}else{
			$username = $this->input->post('username');

			$checkUsernameExist = $this->player_manager->checkUsernameExist($username);

			if (!$checkUsernameExist) {
				$message = lang('con.plm03') . " <b>" . $username . "</b> " . lang('con.plm04');
				$this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);
				redirect('report_management/viewAdjustmentScoreReport');
			}
			$player_id = $this->player_model->getPlayerIdByUsername($username);
		}

		$score = $this->input->post('score');
		$reason = $this->input->post('adjustment_reason');
		$score_type = $this->input->post('manual_add_subtract_score_type');
		$controller = $this;
		$message = lang('notify.61');
		$today = $this->utils->getTodayForMysql();

		$this->utils->debug_log(__METHOD__, 'today', $today);

		if(empty($player_id)){
			$message = lang('Player cannot empty');
            $this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);
            redirect('report_management/viewAdjustmentScoreReport');
            return;
		}

		if(strlen($reason)>200) {
            $message = lang('Maximum 200 characters (including spaces).');
            $this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);
            redirect('report_management/viewAdjustmentScoreReport');
            return;
		}

		$reason = htmlentities($reason, ENT_QUOTES);

		if (is_array($player_id)) {
			$cnt = 0;
			$suc_ids = [];
			$fail_ids =[];
			foreach ($player_id as $player_id) {
				$success = $this->lockAndTrans(Utils::LOCK_ACTION_MANUALLY_ADJUST_PLAYER_SCORE, $player_id, function ()
					use ($controller, $userId, $player_id, $score_type, $score, $reason, $today, &$message) {

					$success = false;
					//create score adjustment record
					$game_score = $this->player_score_model->sumPlayerGameScore($player_id);
					$manual_score = $this->player_score_model->sumPlayerManualScore($player_id);
					$before_score = number_format($game_score + $manual_score, 2, '.', '');
					$admin_user = $this->users->getUsernameById($userId);
					$total_manual_score = 0;
					$from_date_time = $today.' '.Utils::FIRST_TIME;
					$to_date_time = $today.' '.Utils::LAST_TIME;

					$player_score_history = $this->player_score_model->getPlayerScoreHistoryDetails($player_id, $from_date_time, $to_date_time);
					$today_manual_score = $this->player_score_model->sumPlayerManualScore($player_id);

					$this->utils->debug_log('start process player score',$player_id, $game_score, $manual_score, $before_score, $total_manual_score, $player_score_history, $from_date_time, $to_date_time);

					if (!empty($player_score_history)) {
						if ($player_score_history['after_score'] != $before_score) {
							//notification mm
							$mmResult = $this->notificationMMWhenTotalScoreMismatch($player_id, $game_score, $manual_score, $before_score, $player_score_history, $from_date_time, $to_date_time, true);
							$this->utils->debug_log('---- mmResult', $mmResult);
						}

						//update or insert today total score table > manual score
						switch ((int)$score_type) {
							case Player_score_model::MANUAL_ADD_SCORE:
								$after_score = number_format($before_score + $score, 2, '.', '');
								$action_log = sprintf(lang('score_history.manual_add_score_by'), $score, $admin_user);
								$total_manual_score = number_format($today_manual_score + $score, 2, '.', '');
								break;
							case Player_score_model::MANUAL_SUBTRACT_SCORE:
								if (floatval($before_score) <= 0) {
									$message = lang('score_history.before_score_zero');
									return false;
								}
								$after_score = number_format($before_score - $score, 2, '.', '');
								$action_log = sprintf(lang('score_history.manual_subtract_score_by'), $score, $admin_user);
								$total_manual_score = number_format($today_manual_score - $score, 2, '.', '');
								break;
						}
					}else{
						switch ((int)$score_type) {
							case Player_score_model::MANUAL_ADD_SCORE:
								$after_score = number_format($before_score + $score, 2, '.', '');
								$action_log = sprintf(lang('score_history.manual_add_score_by'), $score, $admin_user);
								$total_manual_score = number_format($today_manual_score + $score, 2, '.', '');
								break;
							case Player_score_model::MANUAL_SUBTRACT_SCORE:
								if (floatval($before_score) <= 0) {
									$message = lang('score_history.before_score_zero');
									return false;
								}
								$after_score = number_format($before_score - $score, 2, '.', '');
								$action_log = sprintf(lang('score_history.manual_subtract_score_by'), $score, $admin_user);
								$total_manual_score = number_format($today_manual_score - $score, 2, '.', '');
								break;
						}
					}

					$this->utils->debug_log('end process player score',$player_id, $game_score, $manual_score, $before_score, $after_score, $total_manual_score, $player_score_history);

					$success = $controller->player_score_model->createAdjustmentRecord($userId, $player_id, $score_type, $score, $reason, $action_log, $before_score, $after_score);
					$this->utils->debug_log('createAdjustmentRecord result', $success);
					if (!$success) {
						return false;
					}

					//update player score
					$success = $controller->player_score_model->insertUpdatePlayerManualScore($player_id, $total_manual_score, $userId);
					$this->utils->debug_log('insertUpdatePlayerScore result',$player_id, $success, $total_manual_score);
					if (!$success) {
						return false;
					}
					return $success;
				});

				if ($success) {
					$cnt += 1;
					$suc_ids[] = $player_id;
				}else{
					$fail_ids[] = $player_id;
				}
			}
			$this->utils->debug_log(__METHOD__,'multiple player adjustment result', $cnt, $suc_ids, $fail_ids);
		} else {
			$success = $this->lockAndTrans(Utils::LOCK_ACTION_MANUALLY_ADJUST_PLAYER_SCORE, $player_id, function ()
				use ($controller, $userId, $player_id, $score_type, $score, $reason, $today, &$message) {

				$success = false;
				//create score adjustment record
				$game_score = $this->player_score_model->sumPlayerGameScore($player_id);
				$manual_score = $this->player_score_model->sumPlayerManualScore($player_id);
				$before_score = number_format($game_score + $manual_score, 2, '.', '');
				$admin_user = $this->users->getUsernameById($userId);
				$total_manual_score = 0;
				$from_date_time = $today.' '.Utils::FIRST_TIME;
				$to_date_time = $today.' '.Utils::LAST_TIME;

				$player_score_history = $this->player_score_model->getPlayerScoreHistoryDetails($player_id, $from_date_time, $to_date_time);
				$today_manual_score = $this->player_score_model->sumPlayerManualScore($player_id);

				$this->utils->debug_log('start process player score',$player_id, $game_score, $manual_score, $before_score, $total_manual_score, $player_score_history, $from_date_time, $to_date_time);

				if (!empty($player_score_history)) {
					if ($player_score_history['after_score'] != $before_score) {
						//notification mm
						$mmResult = $this->notificationMMWhenTotalScoreMismatch($player_id, $game_score, $manual_score, $before_score, $player_score_history, $from_date_time, $to_date_time, true);
						$this->utils->debug_log('---- mmResult', $mmResult);
					}

					//update or insert today total score table > manual score
					switch ((int)$score_type) {
						case Player_score_model::MANUAL_ADD_SCORE:
							$after_score = number_format($before_score + $score, 2, '.', '');
							$action_log = sprintf(lang('score_history.manual_add_score_by'), $score, $admin_user);
							$total_manual_score = number_format($today_manual_score + $score, 2, '.', '');
							break;
						case Player_score_model::MANUAL_SUBTRACT_SCORE:
							if (floatval($before_score) <= 0) {
								$message = lang('score_history.before_score_zero');
								return false;
							}
							$after_score = number_format($before_score - $score, 2, '.', '');
							$action_log = sprintf(lang('score_history.manual_subtract_score_by'), $score, $admin_user);
							$total_manual_score = number_format($today_manual_score - $score, 2, '.', '');
							break;
					}
				}else{
					switch ((int)$score_type) {
						case Player_score_model::MANUAL_ADD_SCORE:
							$after_score = number_format($before_score + $score, 2, '.', '');
							$action_log = sprintf(lang('score_history.manual_add_score_by'), $score, $admin_user);
							$total_manual_score = number_format($today_manual_score + $score, 2, '.', '');
							break;
						case Player_score_model::MANUAL_SUBTRACT_SCORE:
							if (floatval($before_score) <= 0) {
								$message = lang('score_history.before_score_zero');
								return false;
							}
							$after_score = number_format($before_score - $score, 2, '.', '');
							$action_log = sprintf(lang('score_history.manual_subtract_score_by'), $score, $admin_user);
							$total_manual_score = number_format($today_manual_score - $score, 2, '.', '');
							break;
					}
				}

				$this->utils->debug_log('end process player score',$player_id, $game_score, $manual_score, $before_score, $after_score, $total_manual_score, $player_score_history);

				$success = $controller->player_score_model->createAdjustmentRecord($userId, $player_id, $score_type, $score, $reason, $action_log, $before_score, $after_score);
				$this->utils->debug_log('createAdjustmentRecord result', $success);
				if (!$success) {
					return false;
				}

				//update player score
				$success = $controller->player_score_model->insertUpdatePlayerManualScore($player_id, $total_manual_score, $userId);
				$this->utils->debug_log('insertUpdatePlayerScore result',$player_id, $success, $total_manual_score);
				if (!$success) {
					return false;
				}

				return $success;
			});
		}
		$this->utils->debug_log(__METHOD__, 'success result', $success);

		if ($success) {
			$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang('score_history.successfully'));
		} else {
			$this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);
		}
		redirect('report_management/viewAdjustmentScoreReport');
	}

	public function notificationMMWhenTotalScoreMismatch($player_id, $game_score, $manual_score, $before_score, $player_score_history, $from_date_time, $to_date_time, $isDryRun = false){
		$this->utils->debug_log(__METHOD__, $game_score, $manual_score, $before_score, $player_score_history, $from_date_time, $to_date_time, $isDryRun);
		$channel = 'PSH003'; /// PSH003, PHP Personal Notification 003
		$level = 'danger';
		$message =
			'```'. PHP_EOL .
			"total_score > game_score: " .$game_score. PHP_EOL.
			"total_score > manual_score: " .$manual_score. PHP_EOL.
			"total_score > sum total_score: " .$before_score. PHP_EOL.
			"score_history > id: " .$player_score_history['id']. PHP_EOL.
			"score_history > score: " .$player_score_history['score']. PHP_EOL.
			"score_history > created_at: " .$player_score_history['created_at']. PHP_EOL.
			"score_history > after_score: " .$player_score_history['after_score']. PHP_EOL.
			"run DateTime: " ."from " . $from_date_time ." to " . $to_date_time. PHP_EOL.
			'```'. PHP_EOL;
		;
		$title = "The player(id:`$player_id`) total score has mismatch the last adjusted score";

		if( $isDryRun){
			$message .= ' #DRY_RUN ';
		}
		$pretext = null;
		$mmResult = $this->utils->sendMessageToMattermostChannel($channel, $level, $title, $message, $pretext);
		return $mmResult;
	}

	/**
	 * detail: display player rank report lists
	 *
	 * @return load template
	 */
	public function viewRankReport() {

		if (!$this->permissions->checkPermissions('player_rank_report')) {
			$this->error_access();
		} else {

			$data = [];
			if (!$this->permissions->checkPermissions('player_rank_report')) {
				$data['export_report_permission'] = FALSE;
			} else {
				$data['export_report_permission'] = TRUE;
			}

			$this->load->model(array('player_score_model','player_model'));
			$data['getAllRankKey'] = $this->player_score_model->getAllRankKey();

			$this->loadTemplate(lang('player_rank_report.title'), '', '', 'report');
			$this->template->write_view('main_content', 'report_management/view_player_rank_report', $data);
			$this->template->render();
		}
	}//end of

	/**
	 * detail: seamless missing payour report
	 *
	 * @return load template
	 */
	public function viewSeamlessMissingPayoutReport() {
		$this->load->model(array('group_level', 'transactions', 'seamless_missing_payout'));

		$data['conditions'] = $this->safeLoadParams(array(
			'by_date_from' => $this->utils->getTodayForMysql(),
			'by_date_to' => $this->utils->getTodayForMysql(),
			'by_username' => '',
			'by_status' => '',
			'by_game_platform_id' => '',
		));

		$data['status_list'] = array(
			'' => lang('All'),
			Seamless_missing_payout::FIXED => lang('Fixed'),
			Seamless_missing_payout::NOT_FIXED => lang('Not Fixed'),
		);

		$data['game_platforms'] = $this->external_system->getAllActiveSytemGameApi();

		$data['export_report_permission'] = TRUE;

		/**
		 * Hide unwanted columns by js for html output
		 * columns: (as of OGP-11982)
		 * 0 - date
		 * 1 - player-username
		 * 2 - game api id
		 * 3 - game api name
		 * 4 - round
		 * 5 - transaction type
		 * 6 - unique id
		 * 7 - amount
		 * 8 - status
		 * 9 - action
		 */

		$hide_cols = [];


		$data['hide_cols'] = $hide_cols;

		$this->loadTemplate(lang('Seamless Games Missing Payout Report'), '', '', 'report');
		$this->template->add_js('resources/js/bootstrap-switch.min.js');
		$this->template->add_css('resources/css/bootstrap-switch.min.css');
		$this->template->write_view('main_content', 'report_management/view_seamless_missing_payout_report', $data);
		$this->template->render();
	}

	 /**
	 * detail: display oneworks game report lists
	 *
	 * @return load template
	 */
	public function viewTournamentWinners() {
		if (!$this->permissions->checkPermissions('game_report')) {
			$this->error_access();
		} else {
			$start_today = date("Y-m-d") . ' 00:00:00';
			$end_today = date("Y-m-d") . ' 23:59:59';
			$data['conditions'] = $this->safeLoadParams(array(
				'date_from' => $this->utils->getTodayForMysql(),
				'hour_from' => '00',
				'date_to' => $this->utils->getTodayForMysql(),
				'hour_to' => '23',
				'datetime_from' => $this->utils->getTodayForMysql().' '.Utils::FIRST_TIME,
				'datetime_to' => $this->utils->getTodayForMysql().' '.Utils::LAST_TIME,
				'datetime_from_timezone' =>  $this->utils->getTodayForMysql().' '.Utils::FIRST_TIME,
				'datetime_to_timezone' =>  $this->utils->getTodayForMysql().' '.Utils::LAST_TIME,
				'username' => '',
			));


			$this->loadTemplate(lang('Tournament Report'), '', '', 'report');
			$this->template->write_view('main_content', 'report_management/games_report/tournament_winners_report', $data);
			$this->template->render();
		}
	}

	public function batch_remove_iovation_evidence_result($token){
		$data['result_token']=$token;
		$this->loadTemplate(lang('Batch Retract Evidence Report'), '', '', 'report');
		//$this->template->write_view('sidebar', 'report_management/sidebar');
		$this->template->write_view('main_content', 'report_management/batch_remove_iovation_evidence_result', $data);
		$this->template->render();
	}

	public function batchRemoveIovationEvidenceByIds(){
        $this->utils->debug_log('running batch_remove_playertag_ids');
		$data = array('title' => lang('Batch Remove Iovation Evidence'), 'sidebar' => 'report_management/sidebar',
			'activenav' => 'iovation_evidence');

		//check parameters
		$this->CI->load->library('data_tables');
		$request = $this->input->post('json_search');
		$request = json_decode($request, true);
        $comment = isset($request['comment'])?$request['comment']:'';
		$input = $this->CI->data_tables->extra_search($request);
		$evidenceIds = isset($input['evidenceIds'])?$input['evidenceIds']:[];

		//get logged user
		$admin_user_id=$this->authentication->getUserId();

		$this->load->library(['lib_queue']);
		//add it to queue job
		$callerType=Queue_result::CALLER_TYPE_ADMIN;
		$caller=$this->authentication->getUserId();
		$state='';
		$this->load->library(['language_function']);
		$lang=$this->language_function->getCurrentLanguage();

        if(empty($evidenceIds)){
            $message = lang('Please select evidence to remove.');
			$this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);
			redirect('/report_management/viewIovationEvidence');
			exit;
        }

		$params = [
			"iovation_evidence_ids" => $evidenceIds,
			"comment" => $comment,
			"runner_username"=>$this->CI->authentication->getUsername()
		];


        //var_dump($evidenceIds);exit;
		//run command
		$caller = $this->authentication->getUserId();
		$state = null;
		$lang = $this->language_function->getCurrentLanguage();
		$systemId = Queue_result::SYSTEM_UNKNOWN;
		$funcName = 'batch_remove_iovation_evidence';
		$token=  $this->lib_queue->commonAddRemoteJob($systemId, $funcName, $params, $callerType, $caller, $state, $lang);
		$this->utils->debug_log(__METHOD__.' commonAddRemoteJob', $params, $token);
		redirect('/report_management/batch_remove_iovation_evidence_result/'.$token);
	}

	public function viewDuplicateContactNumberReport() {
		if (!$this->permissions->checkPermissions('view_duplicate_contactnumber')) {
			return $this->error_access();
		}

		$this->load->model(array('duplicate_contactnumber_model', 'player_model'));

		$data['conditions'] = $this->safeLoadParams(array(
			'by_date_from' => $this->utils->getTodayForMysql(). ' 00:00:00',
			'by_date_to' => $this->utils->getTodayForMysql(). ' 23:59:59',
			'by_username' => '',
			'search_by' => 1,
			'login_ip' => '',
		));

		$data['export_report_permission'] = $this->permissions->checkPermissions('view_duplicate_contactnumber');
		$data['enable_freeze_top_in_list'] = $this->utils->_getEnableFreezeTopWithMethod(__METHOD__, $this->config->item('enable_freeze_top_method_list'));

		$this->loadTemplate(lang('duplicate_contactnumber_model.2'), '', '', 'report');
		$this->template->add_js('resources/js/bootstrap-switch.min.js');
		$this->template->add_css('resources/css/bootstrap-switch.min.css');
		$this->template->write_view('main_content', 'report_management/view_duplicate_contactnumber', $data);
		$this->template->render();
	}

	/**
	 * detail: display remote wallet balance history
	 *
	 * @return load template
	 */
	public function viewRemoteWalletBalanceHistory() {
		if (!$this->permissions->checkPermissions('game_report') || !$this->utils->getConfig('enabled_remote_seamless_wallet_balance_history')) {
			$this->error_access();
		} else {

			$game_apis = $this->external_system->getAllActiveSeamlessGameApi();
			$t1_games = [];
			if(!empty($game_apis)){
				foreach ($game_apis as $key => $value) {
					$id = $value['id'];
					if( $id >= 1500 && $id < 2000 && !empty($value['original_game_platform_id'])){
						$t1_games[] = $value;
					}
				}
			}

			$start_today = date("Y-m-d") . ' 00:00:00';
			$end_today = date("Y-m-d") . ' 23:59:59';
			$data['conditions'] = $this->safeLoadParams(array(
				'date_from' => $this->utils->getTodayForMysql(),
				'hour_from' => '00',
				'date_to' => $this->utils->getTodayForMysql(),
				'hour_to' => '23',
				'datetime_from' => $this->utils->getTodayForMysql().' '.Utils::FIRST_TIME,
				'datetime_to' => $this->utils->getTodayForMysql().' '.Utils::LAST_TIME,
				'datetime_from_timezone' =>  $this->utils->getTodayForMysql().' '.Utils::FIRST_TIME,
				'datetime_to_timezone' =>  $this->utils->getTodayForMysql().' '.Utils::LAST_TIME,
				'username' => '',
			));
			$data['game_platforms'] = $t1_games;

			$this->loadTemplate(lang('Remote wallet balance history'), '', '', 'report');
			$this->template->add_css('resources/third_party/bootstrap-multiselect-master/dist/css/bootstrap-multiselect.css');
        	$this->template->add_js('resources/third_party/bootstrap-multiselect-master/dist/js/bootstrap-multiselect.js');
			$this->template->write_view('main_content', 'report_management/games_report/view_remote_wallet_balance_history', $data);
			$this->template->render();
		}
	}


	/**
	 * detail: display game biling report lists
	 *
	 * @return load template
	 */
	public function viewGamesBillingReport() {
		$this->load->model('game_type_model');

		if (!$this->permissions->checkPermissions('game_report')) {
			$this->error_access();
		} else {
			$this->load->helper('form');

			if (!$this->permissions->checkPermissions('export_games_report')) {
				$data['export_report_permission'] = FALSE;
			} else {
				$data['export_report_permission'] = TRUE;
			}

			$data['conditions'] = $this->safeLoadParams(array(
				'external_system' => '',
				'game_type' => '',
				'game_type_multiple' => '',
				'show_multiselect_filter' => '',
				'game_type_multiple' => '',
			));

			$show_blocked_game_api_data_on_games_report = $this->utils->getConfig('show_blocked_game_api_data_on_games_report');
			$data['game_apis_map'] = [];
			$data['mulitple_select_game_map']=[];

			if($show_blocked_game_api_data_on_games_report === true){
				$data['game_apis_map'] = $this->utils->getGameSystemMap(false);
				foreach ($data['game_apis_map']  as $game_platform_id => $value) {
					$game_type_rows = $this->game_type_model->getAllGameTypeList([$game_platform_id]);
					foreach ($game_type_rows as $game_type_row) {
						$data['mulitple_select_game_map'][$game_platform_id][] = $game_type_row;
					}
				}
			}else{
				$data['game_apis_map'] = $this->utils->getGameSystemMap(true);
				$data['mulitple_select_game_map']= $this->game_type_model->getActiveGamePlatformGameTypes();
			}

			$data['enable_freeze_top_in_list'] = $this->utils->_getEnableFreezeTopWithMethod(__METHOD__, $this->config->item('enable_freeze_top_method_list'));

			$this->loadTemplate(lang('Game Billing Report'), '', '', 'report');
			$this->template->write_view('main_content', 'report_management/games_report/view_games_billing_report', $data);
			$this->template->render();
		}
	}

	/**
	 * detail: display player transactions and game summary
	 *
	 * @return load template
	 */
	public function viewPlayerGameAndTransactionsSummary() {
		$this->load->model('game_type_model');

		if (!$this->permissions->checkPermissions('player_life_time_report')) {
			$this->error_access();
		} else {
			if (!$this->permissions->checkPermissions('export_player_life_time_report')) {
				$data['export_report_permission'] = FALSE;
			} else {
				$data['export_report_permission'] = TRUE;
			}
			$this->loadTemplate(lang('Player Life Time Data'), '', '', 'report');
			$this->template->write_view('main_content', 'report_management/games_report/view_player_game_and_transaction_summary', $data);
			$this->template->render();
		}
	}

}

/* End of file report_management.php */
/* Location: ./application/controllers/report_management.php */
