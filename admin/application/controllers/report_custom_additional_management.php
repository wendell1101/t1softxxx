<?php
require_once dirname(__FILE__) . '/BaseReport.php';
require_once dirname(__FILE__) . '/kyc_status.php';
require_once dirname(__FILE__) . '/report_management.php';

/**
 * Report Custom Additional Management
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
 * @see Redirect redirect to custom report page
 *
 * @category report_management
 * @version 1.8.10
 * @copyright 2013-2022 tot
 */
class Report_custom_additional_management extends Report_management {

	use kyc_status;

    const TAG_ALL = 'all';

	const _operator_setting_name4detected_tag_id = 'detected_tag_id_in_view_player_login_via_same_ip';

	function __construct() {
		parent::__construct();
		// $this->load->helper(array('date_helper', 'url'));
		// $this->load->library(array('permissions', 'excel', 'form_validation', 'template', 'pagination', 'player_manager', 'report_functions', 'gcharts', 'marketing_functions', 'depositpromo_manager', 'transactions_library'));

		// $this->permissions->checkSettings();
		// $this->permissions->setPermissions(); //will set the permission for the logged in user
	}


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
		$this->template->write_view('sidebar', 'custom_report_management/sidebar');
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

    public function viewPlayerAdditionalRouletteReport() {
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
            ));
            $data['export_report_permission'] = $this->permissions->checkPermissions('export_player_report');

            $data['allLevels'] = $this->player_manager->getAllPlayerLevels();
			$data['tags'] = $this->affiliatemodel->getActiveTags();
			$data['selected_tags'] =$a= $this->input->get_post('tag_list');
			$data['selected_include_tags'] =$a= $this->input->get_post('tag_list_included');
            $data['player_tags'] = $this->player->getAllTagsOnly();

			$this->loadTemplate(lang('Player Additional Roulette Report'), '', '', 'custom_report');
			// $this->template->add_js('resources/js/dataTables.fixedColumns.min.js'); // disable for the feature Not required
			$this->template->add_css('resources/third_party/bootstrap-multiselect-master/dist/css/bootstrap-multiselect.css');
			$this->template->add_js('resources/third_party/bootstrap-multiselect-master/dist/js/bootstrap-multiselect.js');
			$this->template->add_css('resources/floating_scroll/jquery.floatingscroll.css');
			$this->template->add_js('resources/floating_scroll/jquery.floatingscroll.js');
			$this->template->write_view('main_content', 'custom_report_management/view_player_additional_roulette_report', $data);
			$this->template->render();
        }
    }

    public function viewPlayerAdditionalReport() {
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
            ));
            $data['export_report_permission'] = $this->permissions->checkPermissions('export_player_report');

            $data['allLevels'] = $this->player_manager->getAllPlayerLevels();
            $data['tags'] = $this->affiliatemodel->getActiveTags();
            $data['selected_tags'] = $this->input->get_post('tag_list');
            $data['selected_include_tags'] = $this->input->get_post('tag_list_included');
            $data['player_tags'] = $this->player->getAllTagsOnly();

            $this->loadTemplate(lang('Player Additional Report'), '', '', 'custom_report');
            // $this->template->add_js('resources/js/dataTables.fixedColumns.min.js'); // disable for the feature Not required
            $this->template->add_css('resources/third_party/bootstrap-multiselect-master/dist/css/bootstrap-multiselect.css');
            $this->template->add_js('resources/third_party/bootstrap-multiselect-master/dist/js/bootstrap-multiselect.js');
            $this->template->add_css('resources/floating_scroll/jquery.floatingscroll.css');
            $this->template->add_js('resources/floating_scroll/jquery.floatingscroll.js');
            $this->template->write_view('main_content', 'custom_report_management/view_player_additional_report', $data);
            $this->template->render();
        }
    }
}

/* End of file report_management.php */
/* Location: ./application/controllers/report_management.php */
