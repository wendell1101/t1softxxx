<?php

require_once dirname(__FILE__) . '/BaseController.php';
require_once dirname(__FILE__) . '/modules/withdrawal_process_flow_module.php';
require_once dirname(__FILE__) . '/modules/middle_exchange_rate_log_module.php';
require_once dirname(__FILE__) . '/modules/transfer_request_module.php';
require_once dirname(__FILE__) . '/modules/points_transaction_module.php';

/**
 * Payment Management
 *
 *
 * General behaviors include
 * * add/edit new bank type
 * * able to activate/deactivate bank type status
 * * able to change the withdrawal status to deactivate/normal
 * * able to change the deposit status to deactivate/normal
 * * able to search data on the entire lists
 * * Lists all transfer request
 * * able to filter transfer request
 * * displays the count of (Transfer Request, Transfer success, Transfer Failed)
 * * Loads Template
 * * Displays Transactions
 * * View Adjustment transaction_list
 * * Exports data to Excel
 *
 * @category payment_management
 * @version 1.8.10
 * @copyright 2013-2022 tot
 *
 */
class Payment_management extends BaseController {

	use withdrawal_process_flow_module;
	use transfer_request_module;
	use points_transaction_module;
    use middle_exchange_rate_log_module;

	function __construct() {
		parent::__construct();
		$this->load->helper(array('date_helper', 'url'));
		$this->load->library(array('permissions', 'form_validation', 'template', 'payment_manager', 'pagination', 'marketing_functions', 'form_validation', 'player_manager', 'user_functions', 'report_functions', 'salt', 'excel', 'bankaccount_manager', 'thirdpartyaccount_manager', 'og_utility', 'marketing_manager', 'promo_library', 'payment_library'));
		$this->load->model(array('player_model', 'wallet_model', 'payment', 'sale_orders_timelog'));

		$this->permissions->checkSettings();
		$this->permissions->setPermissions(); //will set the permission for the logged in user
	}

	const ACTION_LOG_TITLE = 'Payment Management';

	const DEPOSIT_FIRST = 1;
	const DEPOSIT_SUCCEEDING = 2;

	const WITHDRAW_CONDITION_DEPOSIT_BONUS_BET_TIMES_DEDUCT_DEPOSIT='1';
	const WITHDRAW_CONDITION_ONLY_DEPOSIT='2';
	const WITHDRAW_CONDITION_NONE='3';

	const DEFAULT_DEPOSIT_COUNT_SETTING = 2;

	const DEPOSIT_THIS_WEEK = 1;
	const DEPOSIT_THIS_MONTH = 2;
	const DEPOSIT_THIS_YEAR = 3;
	const DEPOSIT_TOTAL_ALL = 4;

	const DECLINED_WITHDRAW_REQUEST = 101;
	const DECLINED_WITHDRAW_PAID = 102;
	const DECLINED_WITHDRAW_BATCH = 103;
	const DECLINED_WITHDRAW_BY_PLAYER = 104;

	const FINANCIAL_ACCOUNT_SETTING_OTHERS_LIST = array(
		'bank_number_validator_mode',
		'financial_account_enable_deposit_bank',
		'financial_account_require_deposit_bank_account',
		'financial_account_require_withdraw_bank_account',
		'financial_account_deposit_account_limit',
		'financial_account_deposit_account_limit_type',
		'financial_account_max_deposit_account_number',
		'financial_account_deposit_account_limit_range_conditions',
		'financial_account_can_be_withdraw_and_deposit',
		'financial_account_deposit_account_default_unverified',
		'financial_account_withdraw_account_limit',
		'financial_account_withdraw_account_limit_type',
		'financial_account_max_withdraw_account_number',
		'financial_account_withdraw_account_limit_range_conditions',
		'financial_account_allow_edit',
		'financial_account_one_account_per_institution',
		'financial_account_allow_delete',
		'financial_account_withdraw_account_default_unverified',
        'financial_account_complete_required_userinfo_before_withdrawal',
	);

	/**
	 * Loads template for view based on regions in
	 * config > template.php
	 *
	 */
	private function loadTemplate($title, $description, $keywords, $activenav) {
		$this->template->write('title', $title);
		$this->template->write('description', $description);
		$this->template->write('keywords', $keywords);
		$this->template->write('activenav', $activenav);
		$this->template->write('username', $this->authentication->getUsername());
		$this->template->write('userId', $this->authentication->getUserId());

		$this->template->add_js('resources/js/datatables.min.js');
        $this->template->add_js('resources/js/dataTables.responsive.min.js');
        $this->template->add_js('resources/js/dataTables.order.dom-checkbox.js');
		$this->template->add_css('resources/css/datatables.min.css');

		$this->template->add_js('resources/js/jquery.numeric.min.js');
		$this->template->add_js('resources/js/clipboard.min.js');
		$this->template->add_css('resources/css/general/style.css');
		$this->template->add_css('resources/css/jquery-checktree.css');

		$this->template->add_js('resources/js/payment_management/payment_management.js');
		$this->template->add_css('resources/css/payment_management/style.css');
	}

	/**
	 * detail: Shows Error message if user can't access the page
	 *
	 * @return	rendered Template
	 */
	private function error_access($from = 'payment') {
		$this->loadTemplate(lang('role.72'), '', '', 'payment');
		$paymentUrl = $this->utils->activePaymentSidebar();
		$reportUrl = $this->utils->activeReportSidebar();
		$systemUrl = $this->utils->activeSystemSidebar();
		if($from == 'payment'){
			$data['redirect'] = $paymentUrl;
		}

		elseif($from == 'system'){
			$data['redirect'] = $systemUrl;
		}

		else{
			$data['redirect'] = $reportUrl;
		}

		$message = lang('con.pym01');
		$this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);

		$this->template->write_view('main_content', 'error_page', $data);
		$this->template->render();
	}

	/**
	 * Get the enable_timezone_query setting on the current page.
	 *
	 * @param string $theMethod
	 * @return bool If true, should display the timezone input for query.
	 */
	private function _getEnableTimezoneQueryWithMethod($theMethod){
		return $this->utils->_getEnableTimezoneQueryWithMethod($theMethod);
	} // EOF _getEnableTimezoneQueryWithMethod

	/**
	 *
	 * overview: Monthly earnings
	 *
	 * detail: Calculate the monthly earnings result
	 *
	 *
	 */
	public function calculate_monthly_earnings() {

		$this->load->model('affiliate_earnings');

		$payDay = $this->affiliate_earnings->getPayDay();

		if ($payDay > date('t')) {
			$payDay = date('t');
		}

		if ($payDay == date('t')) {
			$this->affiliate_earnings->monthlyEarnings();
		}
	}

	/**
	 *
	 * overview: Player Deposits
	 *
	 * detail: Update all player total deposit amount with correspoding transaction type and status
	 *
	 * note: always double check the query of getting the players before running through the updates
	 *
	 * @return String
	 *
	 */
	public function updatePlayersTotalDepositAmount() {
		$this->load->model('transactions');

		$this->db->select('player.playerId');
		$this->db->select_sum('transactions.amount', 'totalDepositAmount');
		$this->db->from('transactions');
		$this->db->join('player', 'player.playerId = transactions.to_id AND transactions.to_type = ' . Transactions::PLAYER);
		$this->db->where('transactions.transaction_type', Transactions::DEPOSIT);
		$this->db->where('transactions.status', Transactions::APPROVED);
		$this->db->group_by('player.playerId');
		$query = $this->db->get();
		$list = $query->result();

		$this->db->update_batch('player', $list, 'playerId');
		$result = $this->db->affected_rows();
		echo "$result player(s) has been updated";
	}

	/**
	 *
	 * overview: Process active status
	 *
	 * detail: To process active status records by batch
	 *
	 * note: simple run this function
	 *
	 * @return String
	 *
	 */
	public function batch_process_active_status() {
		$this->load->model(array('player_model'));
		$this->startTrans();
		$cnt = $this->player_model->batchProcessActiveStatus();
		$rlt = $this->endTransWithSucc();

		echo 'result: ' . $rlt . ' , cnt: ' . $cnt . "\n";
	}

	/**
	 *
	 * overview: Decoding password
	 *
	 * detail: Decode password using salt library by putting randomize password or which preffered
	 *
	 * note: simple run this method
	 *
	 * @return String
	 *
	 */
	public function decode_pass() {
		$this->load->library(array('salt'));
		$pass = 'OqzfaDnBIKVsOYi/ETnb6A==';
		$password = $this->salt->decrypt($pass, $this->getDeskeyOG());
		echo $password . "\n";
	}

	/**
	 *
	 * overview: Index Page of Payment Management
	 *
	 * @return	void
	 *
	 */
	public function index() {
		redirect('/payment_management/deposit_list/?dwStatus=requestAll&select_all=true');
	}

	/**
	 * Returns deposit counts for deposit list headers
	 * Invoked by ajax in deposit list view file
	 * OGP-11751
	 * @see		views/payment_management/deposit_list.php
	 * @return	JSON
	 */
	public function deposit_list_header_counts() {
		$count_interval = self::DEFAULT_DEPOSIT_COUNT_SETTING;
		$hide_timeout = $this->input->get('excludeTimeout');
		$deposit_counts = $this->sale_order->countDepositRequests($count_interval, $hide_timeout);
		$this->returnJsonResult($deposit_counts);
	}

	/**
	 *
	 * overview: List Deposit Request
	 *
	 * detail: List all deposit request records
	 *
	 * note: List all deposit request records
	 *
	 * @param string $dwStatus	deposit status
	 *
	 */
	public function deposit_list($dwStatus = Sale_order::VIEW_STATUS_REQUEST, $paymentType = null) {
		if (!$this->permissions->checkPermissions('deposit_list')) {
			$this->error_access();
			return;
		}

		$data['enable_timezone_query'] = $this->_getEnableTimezoneQueryWithMethod(__METHOD__);

		$t=microtime(true);
		$this->utils->debug_log('start deposit_list', $t);

		$this->load->model(array('payment_account','users','payment_abnormal_notification'));

		$firstDayOfMon = $this->utils->getFirstDateOfCurrentMonth();
		$start_today = date("Y-m-d") . ' 00:00:00';
		$end_today = date("Y-m-d") . ' 23:59:59';

		$data['depositCountList']            = self::DEFAULT_DEPOSIT_COUNT_SETTING;
		$data['checking_deposit_locking']    = $this->config->item('checking_deposit_locking');
		$data['auto_checking_request']       = $this->config->item('auto_set_checking_to_request');
		$data['payment_account_list']        = $this->payment_account->getAllPaymentAccountDetails();
        /// disable fast-fix
		// $data['payment_account_list']        = $this->payment_account->getAllPaymentAccountDetails('payment_order', null , null, Payment_account::STATUS_ACTIVE);

		$this->utils->debug_log('after getAllPaymentAccountDetails', microtime(true));
		$data['deposit_slip_image_path']     = site_url() . 'upload/deposit_slips/';

		// OGP-25168
		// $is_https = isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off';
		// $http = $is_https ? 'https://' : 'http://';
		$deposit_receipt_image_path = $this->utils->getPlayerInternalUrl('admin',PLAYER_INTERNAL_DEPOSIT_RECEIPT_PATH);
		$deposit_receipt_image_path = $this->utils->remove_http($deposit_receipt_image_path);

		$deposit_receipt_image_path_url = "//" . $deposit_receipt_image_path;

		$data['deposit_receipt_image_path']  = $deposit_receipt_image_path_url;
		// $this->utils->debug_log('after getPlayerInternalUrl', microtime(true));
		$data['payment_account_flags']       = $this->utils->getPaymentAccountAllFlagsKV();
		// $this->utils->debug_log('after getPaymentAccountAllFlagsKV', microtime(true));
		$data['defaultSortColumn']           = $this->utils->getDefaultSortColumn('deposit_list');
		// $this->utils->debug_log('after getDefaultSortColumn', microtime(true));
		// $data['aggregatedPaymentPercentage'] = json_encode($this->sale_order->getAggregatedPaymentPercentage());
		// $data['users']                       = $this->users->getAllUsernames();
		$data['searchStatus']				 = array(
												Sale_order::STATUS_PROCESSING => lang('deposit_list.st.pending'),
												Sale_order::STATUS_SETTLED => lang('deposit_list.st.approved'),
												Sale_order::STATUS_DECLINED => lang('deposit_list.st.declined')
												);                                                
		$data['conditions']                  = $this->safeLoadParams(array(
			'dwStatus'=>$dwStatus,
			'deposit_date_from' => $start_today,
			'deposit_date_to' => $end_today,
			'excludeTimeout' => '',
			'secure_id' => '',
			'username' => '',
			'realname' => '',
			'amount_from' => '',
			'amount_to' => '',
			'paybus_order_id' => '',
			'external_order_id' => '',
			'bank_order_id' => '',
			'affiliate' => '',
			'processed_by' => '',
			'timezone' => '',
			'payment_flag_1' => '1',
			'payment_flag_2' => '1',
			'payment_flag_3' => '1',
			'payment_flag_4' => '1',
			'date_range' => '1',
			'search_time' => '1',
			'search_status' => $dwStatus,
			'searchBtn' => '0',
			'enable_date' => '1',
			'referrer' => ''
		));
        
        if($this->config->item('enable_async_approve_sale_order')){
            $data['enable_async_approve'] = true;
        }else{
            $data['enable_async_approve'] = false;
        }

		$data['tags'] = $this->player_manager->getAllTags();
		$data['selected_tags'] =$a= $this->input->get_post('tag_list');
		$data['selected_include_tags'] =$a= $this->input->get_post('tag_list_included');
		$data['player_tags'] = $this->player->getAllTagsOnly();

		// $dtIndex = $this->input->get('datatable_index');
		// $dtDirection = $this->input->get('datatable_direction');
		// $data['conditions']['datatable_index'] = ($dtIndex) ? $dtIndex : $data['defaultSortColumn'];
		// $data['conditions']['datatable_direction'] = ($dtDirection) ? $dtDirection : 'desc';
		// $data['conditions']['enable_date'] = $this->safeGetParam('enable_date', true, true);
		$data['conditions']['select_all'] = $this->safeGetParam('select_all', true, true);

		if(is_array($data['payment_account_list'])){
			foreach ($data['payment_account_list'] as $payment_account) {
				$id=$payment_account->payment_account_id;
				$key='payment_account_id_'.$id;
				$val=$this->input->get($key);
				if($this->config->item('deposit_list_use_post')){
					$val=$this->input->post($key);
				}
				$data['conditions'][$key]=$data['conditions']['select_all'] ? 'true' : $val;
			}
		}

		if ($this->config->item('enabled_abnormal_payment_notification')) {
			$data['abnormal_payment_notification'] = $this->config->item('enabled_abnormal_payment_notification');
			$data['abnormal_payment_permission'] = $this->permissions->checkPermissions('view_abnormal_order_alert_of_the_deposit_list');
			$data['abnormal_payment'] = $this->payment_abnormal_notification->paymentAbnormaList(Payment_abnormal_notification::ABNORMAL_PAYMENT);
			$data['abnormal_player'] = $this->payment_abnormal_notification->paymentAbnormaList(Payment_abnormal_notification::ABNORMAL_PLAYER);
			$data['count_payment_abnorma_list'] = $this->payment_abnormal_notification->countPaymentAbnormaList();
		} else {
			$data['abnormal_payment_notification'] = false;
			$data['abnormal_payment_permission'] = false;
		}

		// $this->utils->debug_log('after foreach', microtime(true));

		$this->loadTemplate(lang('role.74'), '', '', 'payment');
		$this->utils->debug_log('after loadTemplate', microtime(true));

		$this->template->add_css('resources/third_party/bootstrap-multiselect-master/dist/css/bootstrap-multiselect.css');
		$this->template->add_js('resources/third_party/bootstrap-multiselect-master/dist/js/bootstrap-multiselect.js');

		$this->template->add_css('resources/css/dashboard.css');
		$this->template->add_css('resources/css/ekko-lightbox.min.css');
		$this->template->add_js('resources/js/ekko-lightbox.min.js');
		$this->template->add_js('resources/js/highcharts.js');
		$this->template->write_view('sidebar', 'payment_management/sidebar');
		$this->utils->debug_log('before render view', microtime(true));
		$this->template->write_view('main_content', 'payment_management/deposit_list', $data);

		$this->utils->debug_log('cost of deposit_all', (microtime(true)-$t));
		$this->template->render();
	}

    public function update_middle_exchange_rate(){
        if (!$this->permissions->checkPermissions('middle_conversion_exchange_rate_log')) {
			$this->error_access();
			return;
		}
        // @todo update the operator setting
        $rate = $this->input->get_post('rate');

        $updated_by = $this->authentication->getUserId();
        $this->update_middle_exchange_rate_in_operator( $rate, $updated_by);

		$message = lang('Middle Conversion Exchange Rate') . ' ' . lang('con.pym11');
		$this->alertMessage(1, $message);
        redirect('payment_management/middle_exchange_rate_log');

    } // EOF update_middle_exchange_rate


    public function middle_exchange_rate_log(){
        if (!$this->permissions->checkPermissions('middle_conversion_exchange_rate_log')) {
			$this->error_access();
			return;
		}

        $this->load->model(['middle_exchange_rate_log']);

        $this->loadTemplate(lang('Multi-currencies Middle Conversion Exchange Rate'), '', '', 'payment');
		$this->template->add_js('resources/js/payment_management/middle_exchange_rate_log.js');
        $_rate = $this->operatorglobalsettings->getSettingDoubleValue(Operatorglobalsettings::MIDDLE_CONVERSION_EXCHANGE_RATE);

        $data = [];
        $data['rate'] = empty($_rate)? '0': $_rate;

		$this->template->write_view('sidebar', 'payment_management/sidebar');
		$this->template->write_view('main_content', 'payment_management/middle_exchange_rate_log', $data);
		$this->template->render();
    } // EOF middle_exchange_rate_log()

	/**
	 * Withdrawal Risk Process List
	 *
	 * @return void
	 */
	public function withdrawal_risk_process_list(){

		if (!$this->permissions->checkPermissions('withdrawal_risk_process_list')) {
			$this->error_access();
			return;
		}
		$data = [];

		$this->load->model(['dispatch_withdrawal_definition']);

		// 		$playerId = 67;
		// 		$playerId = 26;
		// 		$gameTypeIdList = [];
		// 		$betAndWithdrawalRate = $this->dispatch_withdrawal_definition->getBetAndWithdrawalRateByPlayerId($playerId, $gameTypeIdList);
		// print_r($betAndWithdrawalRate);
		// 		$winAndDepositRate = $this->dispatch_withdrawal_definition->getWinAndDepositRateByPlayerId($playerId, $gameTypeIdList);
		// print_r($winAndDepositRate);
		// 		$gameRevenuePercentage = $this->dispatch_withdrawal_definition->getGameRevenuePercentageByPlayerId($playerId, $gameTypeIdList);
		// print_r($gameRevenuePercentage);

		// for view, form_dropdown('eligible2dwStatus', $eligible2dwStatus4Options, '');


		$setting = $this->operatorglobalsettings->getCustomWithdrawalProcessingStage();
		$eligible2dwStatus4Options = [];
		$eligible2dwStatus4Options[Wallet_model::PAY_PROC_STATUS] = $this->wallet_model->getStageName(Wallet_model::PAY_PROC_STATUS); // Manual Payment
		// $eligible2dwStatus4Options[Wallet_model::PAY_PROC_STATUS] = lang('PaymentWithAPI'); // 3rd Payment - should with external_system_id, and some params,
		// - transaction_fee: 0
		// - ignoreWithdrawalAmountLimit: 0
		// - ignoreWithdrawalTimesLimit: 0
		/// ignore, no requirement,"Paid".
		// $eligible2dwStatus4Options[Wallet_model::PAID_STATUS] = lang('Paid'); // Pending Review
		        //         case Wallet_model::PAID_STATUS :
				// case Wallet_model::APPROVED_STATUS :
		if($this->utils->getConfig('enable_pending_review_custom') && $this->permissions->checkPermissions('view_pending_custom_stage')){
			if($setting['pendingCustom']['enabled']) {
				$eligible2dwStatus4Options[Wallet_model::PENDING_REVIEW_CUSTOM_STATUS] = $this->wallet_model->getStageName(Wallet_model::PENDING_REVIEW_CUSTOM_STATUS); // Pending VIP
			}
		}
		if($this->utils->isEnabledFeature("enable_withdrawal_pending_review") && $this->permissions->checkPermissions('view_pending_review_stage')){
			$eligible2dwStatus4Options[Wallet_model::PENDING_REVIEW_STATUS] = $this->wallet_model->getStageName(Wallet_model::PENDING_REVIEW_STATUS); // Pending Review
		}
        // $eligible2dwStatus4Options[Wallet_model::PAY_PROC_STATUS] =  Wallet_model::PAY_PROC_STATUS; // payProc
		for($i = 0; $i < CUSTOM_WITHDRAWAL_PROCESSING_STAGES; $i++){
			if ( ! empty($setting[$i]['enabled'])) {
				if ( ! empty($setting[$i]['name'])) {
					$eligible2dwStatus4Options['custom_stage_'. ($i+1)] = $setting[$i]['name'];
				}
			}
		}
		$eligible2dwStatus4Options[Wallet_model::DECLINED_STATUS] =  $this->wallet_model->getStageName(Wallet_model::DECLINED_STATUS); // Declined

		$this->loadTemplate(lang('Withdrawal Risk Process List'), '', '', 'payment');

		$this->template->add_js('resources/js/payment_management/withdrawal_risk_process.js');
		// $this->template->add_js('resources/js/payment_management/payment_management.js');
		$this->template->add_js('resources/js/ace/ace.js');
		$this->template->add_js('resources/js/ace/mode-json.js');
		$this->template->add_js('resources/js/ace/theme-tomorrow.js');
		$this->template->add_js($this->utils->thirdpartyUrl('bootstrap-select/1.12.4/bootstrap-select.min.js'));
		$this->template->add_css($this->utils->thirdpartyUrl('bootstrap-select/1.12.4/bootstrap-select.min.css'));


		$data['eligible2dwStatus4Options'] = $eligible2dwStatus4Options;
		$this->template->write_view('sidebar', 'payment_management/sidebar');
		$this->template->write_view('main_content', 'payment_management/withdrawal_risk_process_list', $data);
		$this->template->render();
	}// EOF withdrawal_risk_process_list

	/**
	 * Dispatch Withdrawal Condition List
	 * The Dispatch Withdrawal Condition List Under a Definition.
	 *
	 * @param integer $definition_id The field, "dispatch_withdrawal_definition.id"
	 * @return void
	 */
	public function dispatch_withdrawal_condition_list($definition_id){
		if (!$this->permissions->checkPermissions('withdrawal_risk_process_list')) {
			$this->error_access();
			return;
		}

		$this->load->model(['dispatch_withdrawal_definition','dispatch_withdrawal_conditions', 'player']);

		$data = [];
		$data['definition_id'] = $definition_id;
		$data['definitionDetail'] = $this->dispatch_withdrawal_definition->getDetailById($definition_id);

		$data['player_tags'] = $this->player->getAllTagsOnly();
		// $data['tags'] = [];//$this->affiliatemodel->getActiveTags();
		$data['selected_tags'] = [];// $this->input->get_post('tag_list');

		$data['player_levels'] = $this->player_model->getAllPlayerLevels(); // all_vip_levels
		$data['selected_levels'] = [];

		$this->template->add_js('resources/js/payment_management/dispatch_withdrawal_conditions.js');
		$this->template->add_js('resources/third_party/bootstrap-multiselect-master/dist/js/bootstrap-multiselect.js');
		$this->template->add_css('resources/third_party/bootstrap-multiselect-master/dist/css/bootstrap-multiselect.css');
		$this->template->add_js($this->utils->thirdpartyUrl('bootstrap-select/1.12.4/bootstrap-select.min.js'));
		$this->template->add_css($this->utils->thirdpartyUrl('bootstrap-select/1.12.4/bootstrap-select.min.css'));
		$this->template->add_js('resources/js/bootstrap-switch.min.js');
		$this->template->add_css('resources/css/bootstrap-switch.min.css');
		$this->addJsTreeToTemplate();

		$this->loadTemplate(lang('Dispatch Withdrawal Condition List'), '', '', 'payment');

		$this->template->write_view('sidebar', 'payment_management/sidebar');
		$this->template->write_view('main_content', 'payment_management/dispatch_withdrawal_condition_list', $data);
		$this->template->render();
	} // EOF dispatch_withdrawal_condition_list



	/**
	 *
	 * overview: List Deposit Processing
	 *
	 * detail: List all deposit Processing records
	 *
	 * note: List all deposit Processing records
	 *
	 */
	public function getSaleOrderReport() {
		if (!$this->permissions->checkPermissions('deposit_list')) {
			$this->error_access();
			return;
		}

        $data['searchStatus'] = array(
			Sale_order::STATUS_PROCESSING => lang('deposit_list.st.pending'),
			Sale_order::STATUS_SETTLED => lang('deposit_list.st.approved'),
			Sale_order::STATUS_DECLINED => lang('deposit_list.st.declined')
		);

        $start_today = date("Y-m-d") . ' 00:00:00';
        $end_today = date("Y-m-d") . ' 23:59:59';

        $data['conditions'] = $this->safeLoadParams(array(
			'search_status' => '',
	        'username' => '',
	        'processed_by' => '' ,
	        'deposit_date_from' => $start_today,
	        'deposit_date_to' => $end_today,
        ));

        $this->utils->debug_log("=================getDepositReport conditions: ", $data['conditions']);

        $this->loadTemplate(lang('payment.depositProcessingTimeRecord'), '', '', 'payment');
        $this->template->write_view('sidebar', 'payment_management/sidebar');
        $this->template->write_view('main_content', 'payment_management/view_deposit_report_list', $data);
        $this->template->render();
	}

	/**
	 *
	 * overview: List Withdraw Processing
	 *
	 * detail: List all withdraw Processing records
	 *
	 * note: List all withdraw Processing records
	 *
	 */
	public function getWithdrawReport() {
		if (!$this->permissions->checkPermissions(array('payment_withdrawal_list'))) {
			$this->error_access();
			return;
		}

		$data['setting'] = $this->operatorglobalsettings->getCustomWithdrawalProcessingStage();
		$customStageCount = 0;
		for ($i = 0; $i < count($data['setting']); $i++) {
			if (array_key_exists($i, $data['setting'])) {
				$customStageCount += ($data['setting'][$i]['enabled'] ? 1 : 0);
			}
		}

		#get all search select status ,view stage permission ,view detail button permission
		$status_and_permission = $this->payment_library->getWithdrawalAllStatusPermission($data['setting'],$customStageCount);
		$data['searchStatus'] = json_encode($status_and_permission);

		$start_today = date("Y-m-d") . ' 00:00:00';
		$end_today = date("Y-m-d") . ' 23:59:59';

		$data['conditions'] = $this->safeLoadParams(array(
			'search_status' => '',
			'username' => '',
			'processed_by' => '' ,
			'withdrawal_date_from' => $start_today,
			'withdrawal_date_to' => $end_today,
		));

		$this->loadTemplate(lang('Withdraw Processing Time Record'), '', '', 'payment');
		$this->template->write_view('sidebar', 'payment_management/sidebar');
		$this->template->write_view('main_content', 'payment_management/view_withdraw_report_list', $data);
		$this->template->render();
	}

	/**
	 *
	 * overview: Deposit details
	 *
	 * detail: Getting the deposit details
	 *
	 * note: always add a the parameter $id, it will cause an error.
	 *
	 * @param int $id sale order id
	 * @return json
	 */
	public function get_deposit_detail($id, $return_json = true) {
		$rlt = false;
		if ($this->permissions->checkPermissions('deposit_list')) {
			$this->load->model(array('sale_order', 'player_promo', 'player_attached_proof_file_model', 'sale_orders_notes', 'vipsetting', 'sale_orders_timelog', 'sale_orders_additional'));

			$this->utils->debug_log('load getSaleOrderDetailById start in get_deposit_detail()');
			$t = time();
			$saleOrderDetail = $this->sale_order->getSaleOrderDetailById($id);
			$this->utils->debug_log('load getSaleOrderDetailById end in get_deposit_detail()', time() - $t);

			if ($saleOrderDetail) {
				$saleOrderDetail->sub_wallet_name = '';
				if ($saleOrderDetail->sub_wallet_id) {
					$gameMap = $this->utils->getGameSystemMap();
					$saleOrderDetail->sub_wallet_name = $gameMap[$saleOrderDetail->sub_wallet_id];
				}
				$origin_amount = $saleOrderDetail->amount;
				$saleOrderDetail->amount = $this->utils->formatCurrencyNoSym($saleOrderDetail->amount);
				//translate
				if (!empty($saleOrderDetail->payment_type_name)) {
					$saleOrderDetail->payment_type_name = lang($saleOrderDetail->payment_type_name);
				}
				if (!empty($saleOrderDetail->player_payment_type_name)) {
					$saleOrderDetail->player_payment_type_name = lang($saleOrderDetail->player_payment_type_name);
				}
				$saleOrderDetail->playerActivePromo = $this->player_promo->getAvailPlayerPromoById($saleOrderDetail->player_promo_id, $origin_amount);
				$saleOrderDetail->promotion_account = $this->sale_order->getPromoRuleFromPaymentAccount($saleOrderDetail->payment_account_id);

				$player_id = $saleOrderDetail->player_id;
				$sale_orders_id = $saleOrderDetail->id;
				$the_sale_orders_additional = $this->sale_orders_additional->getDetailBySaleOrderId($sale_orders_id);
				$sprintf_format = '%s - %s'; // params: groupName, vipLevelName
				$groupName = lang('N/A'); // defaults
				$vipLevelName = lang('N/A'); // defaults
				if( ! empty($the_sale_orders_additional['vip_level_info']) ){
					$vip_level_info = json_decode($the_sale_orders_additional['vip_level_info'], true);
				}else{
					$vip_level_info = $this->vipsetting->getVipGroupLevelInfoByPlayerId($player_id);
				}
				if( ! empty($vip_level_info['vipsetting']['groupName']) ){
					$groupName = lang($vip_level_info['vipsetting']['groupName']);
				}
				if( ! empty($vip_level_info['vipsettingcashbackrule']['vipLevelName']) ){
					$vipLevelName =  lang($vip_level_info['vipsettingcashbackrule']['vipLevelName']);
				}
				$saleOrderDetail->group_level_name = sprintf($sprintf_format, $groupName, $vipLevelName);


				if(!empty($saleOrderDetail->group_level_id)){

					$this->utils->debug_log('load get getVipGroupLevelDetails start in get_deposit_detail()');
					$t = time();
					$request_level = $this->vipsetting->getVipGroupLevelDetails($saleOrderDetail->group_level_id);
					$this->utils->debug_log('load get getVipGroupLevelDetails end in get_deposit_detail()', time() - $t);

					$saleOrderDetail->request_group_level_name = lang($request_level['groupName']) . ' - ' . lang($request_level['vipLevelName']);
				}

                if($this->utils->isEnabledFeature('enable_deposit_upload_documents')) {
				    if($this->utils->isEnabledFeature('only_showing_atm_deposit_upload_attachment_in_deposit_list') && ($saleOrderDetail->player_deposit_method != Payment_account::FLAG_MANUAL_LOCAL_BANK) ){
                        $deposit_receipt_info = null;
                    }else{
                        $deposit_receipt_info = $this->player_attached_proof_file_model->getAttachementRecordInfo($saleOrderDetail->player_id, null, player_attached_proof_file_model::Deposit_Attached_Document, null, false, $saleOrderDetail->id, true, false);
                    }

                    $saleOrderDetail->deposit_receipt_info = !empty($deposit_receipt_info) ? json_encode($deposit_receipt_info) : '';
                }

				# Translate payment method code to word
				switch ($saleOrderDetail->player_deposit_method) {
					case Payment_account::FLAG_MANUAL_LOCAL_BANK:
						$saleOrderDetail->player_deposit_method = lang('pay.local_bank_offline');
						break;
					case Payment_account::FLAG_MANUAL_ONLINE_PAYMENT:
						$saleOrderDetail->player_deposit_method = lang('pay.manual_online_payment');
						break;
					case Payment_account::FLAG_AUTO_ONLINE_PAYMENT:
						$saleOrderDetail->player_deposit_method = lang('pay.auto_online_payment');
						break;
					default:
						$saleOrderDetail->player_deposit_method = lang('pay.auto_online_payment');
						break;
				}

				$this->utils->debug_log('load get sale_order_player_notes start in get_deposit_detail()');
				$t = time();
				$saleOrderDetail->sale_order_player_notes   = $this->sale_orders_notes->getBySaleOrdersNotes(Sale_orders_notes::PLAYER_NOTES, $saleOrderDetail->id);
				$this->utils->debug_log('load get sale_order_player_notes end in get_deposit_detail()', time() - $t);

				$this->utils->debug_log('load get sale_order_internal_notes start in get_deposit_detail()');
				$t = time();
				$saleOrderDetail->sale_order_internal_notes = $this->sale_orders_notes->getBySaleOrdersNotes(Sale_orders_notes::INTERNAL_NOTE, $saleOrderDetail->id);
				$this->utils->debug_log('load get sale_order_internal_notes end in get_deposit_detail()', time() - $t);

				$this->utils->debug_log('load get sale_order_external_notes start in get_deposit_detail()');
				$t = time();
				$saleOrderDetail->sale_order_external_notes = $this->sale_orders_notes->getBySaleOrdersNotes(Sale_orders_notes::EXTERNAL_NOTE, $saleOrderDetail->id);
				$this->utils->debug_log('load get sale_order_external_notes end in get_deposit_detail()', time() - $t);

				//for feature enable_manual_deposit_input_depositor_name
				if($this->utils->isEnabledFeature('enable_manual_deposit_input_depositor_name')){
					//if player_payment_account_name is not empty
					if(!empty($saleOrderDetail->player_payment_account_name)){
						$saleOrderDetail->realname = $saleOrderDetail->player_payment_account_name;
					}
				}
                $saleOrderDetail->player_tags = $this->player_model->getPlayerTags($saleOrderDetail->player_id);
				$rlt = $saleOrderDetail;
				if(!$return_json) {
				    return $rlt;
                }
			}
		}
		$this->returnJsonResult($rlt);
	}

	/**
	 *
	 * overview: Deposite Columns
	 *
	 * detail: Manage the columns of the deposit lists
	 *
	 * @return void
	 *
	 */
	public function postChangeColumnsDeposit() {
		$depslip = $this->input->post('depslip') ? "checked" : "unchecked";
		$userName = $this->input->post('userName') ? "checked" : "unchecked";
		$realName = $this->input->post('realName') ? "checked" : "unchecked";
		$playerlev = $this->input->post('playerlev') ? "checked" : "unchecked";
		$amt = $this->input->post('amt') ? "checked" : "unchecked";
		$depstatus = $this->input->post('depstatus') ? "checked" : "unchecked";
		$createdon = $this->input->post('createdon') ? "checked" : "unchecked";
		$collection_name = $this->input->post('collection_name') ? "checked" : "unchecked";
		$collection_account_name = $this->input->post('collection_account_name') ? "checked" : "unchecked";
		$deposit_payment_name = $this->input->post('deposit_payment_name') ? "checked" : "unchecked";
		$deposit_payment_account_name = $this->input->post('deposit_payment_account_name') ? "checked" : "unchecked";
		$deposit_payment_account_number = $this->input->post('deposit_payment_account_number') ? "checked" : "unchecked";
		$deposit_transaction_code = $this->input->post('deposit_transaction_code') ? "checked" : "unchecked";
		$promoname = $this->input->post('promoname') ? "checked" : "unchecked";
		$promobonus = $this->input->post('promobonus') ? "checked" : "unchecked";
		$ip = $this->input->post('ip') ? "checked" : "unchecked";
		$updatedon = $this->input->post('updatedon') ? "checked" : "unchecked";
		$timeoutAt = $this->input->post('timeoutAt') ? "checked" : "unchecked";

		$depositCustomColumn = array(
			'depslip' => $depslip,
			'userName' => $userName,
			'realName' => $realName,
			'playerlev' => $playerlev,
			'amt' => $amt,
			'depstatus' => $depstatus,
			'createdon' => $createdon,
			'collection_name' => $collection_name,
			'collection_account_name' => $collection_account_name,
			'deposit_payment_name' => $deposit_payment_name,
			'deposit_payment_account_name' => $deposit_payment_account_name,
			'deposit_payment_account_number' => $deposit_payment_account_number,
			'deposit_transaction_code' => $deposit_transaction_code,
			'deposit_payment_account_number' => $deposit_payment_account_number,
			'promoname' => $promoname,
			'promobonus' => $promobonus,
			'ip' => $ip,
			'updatedon' => $updatedon,
			'timeoutAt' => $timeoutAt,
		);
		$this->session->set_userdata($depositCustomColumn);
		redirect('payment_management/deposit_list/request');
	}

	/**
	 *
	 * overview: Set Deposit as declined
	 *
	 * detail: set the status of deposit request as declined
	 *
	 * note: $saleOrderId is required when calling this function
	 *
	 * @param int $saleOrderId sale order id
	 * @return json
	 *
	 */
	public function set_deposit_declined($saleOrderId) {
		$success = false;
		if ($this->permissions->checkPermissions(array('deposit_list', 'approve_decline_deposit')) || $this->permissions->checkPermissions(array('deposit_list', 'single_approve_decline_deposit'))) {
			$this->load->model(array('sale_order', 'transactions', 'sale_orders_notes'));
			$ord = $this->sale_order->getSaleOrderById($saleOrderId);
            $loggedAdminUserId = method_exists($this->authentication, 'getUserId') ? $this->authentication->getUserId() : Users::SUPER_ADMIN_ID;

			$lockedKey=null;
			$lock_it = $this->lockPlayerBalanceResource($ord->player_id, $lockedKey);

			if (!$lock_it) {
				$this->returnJsonResult(array('success' => $success));
				return;
			}
			//lock success
			try {
				$actionlogNotes = $this->input->post("actionlogNotes");
				$this->startTrans();

				$this->sale_order->declineSaleOrder($saleOrderId, $actionlogNotes, null);
                $saleOrder = $this->sale_order->getSaleOrderById($saleOrderId);

                $this->transactions->createDeclinedDepositTransaction($saleOrder, $loggedAdminUserId, Transactions::MANUAL);
				$this->saveAction(self::ACTION_LOG_TITLE, 'Declined Deposit/Withdrawal Request', "User " . $this->authentication->getUsername() . " has declined deposit/withdrawal request.");

				$success = $this->endTransWithSucc();
			} finally {
				// release it
				$rlt = $this->releasePlayerBalanceResource($ord->player_id, $lockedKey);
			}
			if($success) {
				$this->sale_order->userUnlockDeposit($saleOrderId);
			}

			if($success){
                $this->triggerDepositEvent($ord->player_id, $saleOrderId, null, null, $ord->payment_account_id, $loggedAdminUserId);

                if($this->utils->getConfig('enable_fast_track_integration')) {
                    $this->load->library('fast_track');
                    $this->fast_track->declineDeposit((array) $ord);
                }
			}

			$trans = $this->transactions->getTransactionBySaleOrderId($ord->id);
			$currency = $this->utils->getCurrentCurrency();

			$this->utils->playerTrackingEvent($ord->player_id, 'delineDeposit',
			array(
				'orderid'			=> $ord->id,
				'secure_id' 		=> $ord->secure_id,
				'amount'			=> $ord->amount,
				"Type"              => "Deposit",
				"Status"            => "Failed",
				"Currency"          => $currency['currency_code'],
				"TransactionID"     => $ord->secure_id,
				"Channel"           => $ord->payment_account_name,
				"TimeTaken"         => strtotime($trans->created_at) - strtotime($ord->created_at),
				"LastDepositAmount" => $ord->amount,
				'Date'				=> $ord->created_at,
			));

            $this->load->library(['player_notification_library']);
            $this->player_notification_library->danger($ord->player_id, Player_notification::SOURCE_TYPE_DEPOSIT, [
                'player_notify_danger_deposit_title',
                $ord->secure_id,
                $ord->created_at,
                $this->utils->displayCurrency($ord->amount),
                $this->utils->getLiveChatLink(),
                $this->utils->getLiveChatOnClick()
            ], [
                'player_notify_danger_deposit_message',
                $ord->secure_id,
                $ord->created_at,
                $this->utils->displayCurrency($ord->amount),
                $this->utils->getLiveChatLink(),
                $this->utils->getLiveChatOnClick()
            ]);
		}
		$player=$this->player_model->getPlayerArrayById($ord->player_id);
		$this->processTrackingCallback($ord->player_id, $player, (array)$ord, 'deposit');
		
		$this->returnJsonResult(array('success' => $success));
	}

	public function processTrackingCallback($playerId, $player, $order, $type) {
        $this->load->library(['player_trackingevent_library']);
        $tracking_info = $this->player_trackingevent_library->getTrackingInfoByPlayerId($playerId);
		$this->utils->debug_log('============processTrackingCallback============ ', $playerId, $tracking_info);
		if($this->utils->getConfig('third_party_tracking_platform_list')){
			$tracking_list = $this->utils->getConfig('third_party_tracking_platform_list');
			switch($type){
				case 'deposit':
					$this->utils->debug_log('============processTrackingCallback saleOrder============ ', $order);
					foreach($tracking_list as $key => $val){
						if(isset($val['always_tracking'])){
							$recid = $key;
							$this->player_trackingevent_library->processPaymentFailed($recid, Player_trackingevent::TRACKINGEVENT_SOURCE_TYPE_DEPOSIT_FAILED, $tracking_info, $playerId, $player, $order);
						}
					}
					break;
				case 'withdrawal':
					$this->utils->debug_log('============processTrackingCallback walletAccount============ ', $order);
					foreach($tracking_list as $key => $val){
						if(isset($val['always_tracking'])){
							$recid = $key;
							$this->player_trackingevent_library->processWithdrawalFailed($recid, Player_trackingevent::TRACKINGEVENT_SOURCE_TYPE_WITHDRAWAL_FAILED, $tracking_info, $playerId, $player, $order);
						}
					}
					break;
			}

		}
    }
	/**
 	 *
 	 * overview: approve sale order
 	 *
 	 * detail: update status of sale order, add balance to player, add point to player
 	 *
 	 * note: lock balance by player id, use same DB transaction to make sure data consistency
 	 *
 	 * @param int $saleOrderId            sale order id
 	 * @param string $enabledCompensationVal @deprecated
 	 * @param string $compensationFeeVal     @deprecated
 	 *
 	 */
	public function set_deposit_approved($saleOrderId, $enabledCompensationVal = '', $compensationFeeVal = '') {
		$success = false;
		$error_message= null;
		$promo_result= null;

		if ($this->permissions->checkPermissions(array('deposit_list', 'approve_decline_deposit')) || $this->permissions->checkPermissions(array('deposit_list', 'single_approve_decline_deposit'))) {

			$this->load->model(array('sale_order', 'promorules'));
			$ord = $this->sale_order->getSaleOrderById($saleOrderId);

            $approvedSubWallet = $this->input->post('approvedSubWallet') == 'true';
            $approve_promotion = $this->input->post('approve_promotion') == 'true';
            $approve_player_group_level_request = $this->input->post('approve_player_group_level_request') === 'true';
			$saleOrder=null;
			$player=null;
			$extra_info=null;
			$adminUserId = $this->authentication->getUserId();

			if($this->utils->notEmptyTextFromCache($ord->secure_id.'_approved')){
				$this->returnJsonResult(array('success' => $success, 'error_message' => lang('Deposit request has been approved by other admin')));
				return;
			}else{
				$this->utils->saveTextToCache($ord->secure_id.'_approved', true, 300);
			}

			$lockedKey=null;
			$lock_it = $this->lockPlayerBalanceResource($ord->player_id, $lockedKey);

			$this->utils->debug_log(__METHOD__, "check lock result and params", $lockedKey, $ord->player_id, $ord->secure_id ,$lock_it);

			if (!$lock_it) {
				$message = lang('lock resource fail');
				$this->returnJsonResult(array('success' => $success, 'error_message' => $message));
				return;
			}

			//lock success
			try {
				$this->load->model(array('sale_order', 'withdraw_condition', 'point_transactions', 'player_model', 'group_level', 'sale_orders_notes', 'transactions'));
				$actionlogNotes = $this->input->post("actionlogNotes");
				$this->startTrans();

				$saleOrder = $this->sale_order->getSaleOrderWithPlayerById($saleOrderId);
				$player = $saleOrder->player;

				// clear withdraw condition
				// $this->withdraw_condition->checkAndCleanWithdrawCondition($saleOrder->player_id);
                $extra_info['approve_SubWallet'] = $approvedSubWallet;
                $extra_info['approve_promotion'] = $approve_promotion;
				$extra_info['is_payment_account_promo'] = FALSE;
				$success=$this->sale_order->approveSaleOrder($saleOrderId, $actionlogNotes, null, $extra_info);

				if(!$success && isset($extra_info['error_message'])){
                    $error_message = lang($extra_info['error_message']);
				}

				if($success && isset($extra_info['apply_promo_success'])){
					$promo_result=['apply_promo_success'=>$extra_info['apply_promo_success'],
						'apply_promo_message'=>lang('Deposit Successfully').' '.@$extra_info['apply_promo_message']];
				}
				// ######### start deposit to points
				$isEnabledDepositToPoint = $this->utils->getConfig('enable_deposit_amount_to_point');
				if($isEnabledDepositToPoint){
					//get deposit convert rate of this player
					$depositConvertRate = $this->group_level->getVipGroupLevelDetails($player->levelId)['deposit_convert_rate'];

					//point based on convert rate
					// $point = $this->utils->truncateAmountDecimal($saleOrder->amount * $depositConvertRate / 100, 4);
					$point = intval($saleOrder->amount * $depositConvertRate / 100, 4);

					$extra['source_amount']= $saleOrder->amount;
					$extra['current_rate']= $depositConvertRate;
					//player current point
					// $beforePointBalance = $player->point;
					// $beforePointBalance = $this->utils->truncateAmountDecimal($this->point_transactions->getPlayerAvailablePoints($player->playerId),4);
					$beforePointBalance = intval($this->point_transactions->getPlayerAvailablePoints($player->playerId),4);

					//get limit setting
					$pointLimit = $this->CI->group_level->getVipGroupLevelDetails($player->levelId)['points_limit'];
					$pointLimitType = $this->CI->group_level->getVipGroupLevelDetails($player->levelId)['points_limit_type'];

					//get allowed points
					$dateWithinObj = new DateTime();
					$dateWithin = $dateWithinObj->format('Y-m-d');

					$calculateDepositToPointsResult = $this->CI->point_transactions->calculateDepositToPoints($player->playerId, $saleOrder->amount, $saleOrderId, $depositConvertRate,$pointLimit,$pointLimitType,$point,$dateWithin);

					$point = $calculateDepositToPointsResult['points_allowed_to_add'];
					$reason = 'Deposit to points. '. $calculateDepositToPointsResult['remarks'];

					// $newPointBalance = $this->utils->truncateAmountDecimal($beforePointBalance + $point, 4);
					$newPointBalance = intval($beforePointBalance + $point, 4);

					$extra = $calculateDepositToPointsResult['extra'];

					$this->point_transactions->createPointTransaction(
						$adminUserId,
						$player->playerId,
						$point,
						$beforePointBalance,
						$newPointBalance,
						$saleOrder->id,
						$saleOrder->player_promo_id,
						Point_transactions::DEPOSIT_POINT,
						$reason,
						null,
						1,
						$extra
					);

					//update player point balance
					$this->player_model->updatePlayerPointBalance($player->playerId, $newPointBalance);
				}else{
					$this->utils->debug_log('disabled enable_deposit_amount_to_point');
				}
				// ######### end deposit to points


				// change player group level
				if ($approve_player_group_level_request && isset($saleOrder->group_level_id) && $saleOrder->group_level_id > 0) {
					$this->load->model('group_level');
					$this->group_level->adjustPlayerLevel($player->playerId, $saleOrder->group_level_id);
				}

				$this->saveAction(self::ACTION_LOG_TITLE, 'Approve Deposit Request', "User " . $this->authentication->getUsername() . " has successfully approve deposit request of " . $player->username . ".");

				if (!$success) {
					//rollback
					$this->rollbackTrans();
					$this->utils->debug_log('rollback transaction , because transfer failed', $saleOrder);
				} else {
					$success = $this->endTransWithSucc();
				}

			} finally {
				// release it
				$rlt = $this->releasePlayerBalanceResource($ord->player_id, $lockedKey);
			}

			if($success){
				$this->triggerDepositEvent($ord->player_id, $ord->id, null, null, $ord->payment_account_id, $adminUserId);
			}

            $this->load->library(['player_notification_library']);
            if($success){

                if($this->utils->getConfig('enable_fast_track_integration')) {
                    $this->load->library('fast_track');
                    $this->fast_track->approveDeposit((array) $ord);
                }

				$trans = $this->transactions->getTransactionBySaleOrderId($ord->id);
				$currency = $this->utils->getCurrentCurrency();

				$this->utils->playerTrackingEvent($ord->player_id, 'approveDeposit',
				array(
					'orderid'			=> $ord->id,
					'secure_id' 		=> $ord->secure_id,
					'amount'			=> $ord->amount,
					"Type"              => "Deposit",
					"Status"            => "Success",
					"Currency"          => $currency['currency_code'],
					"TransactionID"     => $ord->secure_id,
					"Channel"           => $ord->payment_account_name,
					"TimeTaken"         => strtotime($trans->created_at) - strtotime($ord->created_at),
					"LastDepositAmount" => $ord->amount,
					'Date'				=> $ord->created_at,
				));
                $depositFlag = $this->transactions->isOnlyFirstDeposit($ord->player_id) ? Player_notification::FLAG_FIRST_DEPOSIT : Player_notification::FLAG_COMMON_DEPOSIT;
                $this->player_notification_library->success($ord->player_id, Player_notification::SOURCE_TYPE_DEPOSIT, [
                    'player_notify_success_deposit_title',
                    $ord->secure_id,
                    $ord->created_at,
                    $this->utils->displayCurrency($ord->amount),
                    $this->utils->getPlayerHistoryUrl('deposit')
                ], [
                    'player_notify_success_deposit_message',
                    $ord->secure_id,
                    $ord->created_at,
                    $this->utils->displayCurrency($ord->amount),
                    $this->utils->getPlayerHistoryUrl('deposit'),
					$depositFlag
                ]);
            }else{
                $this->player_notification_library->danger($ord->player_id, Player_notification::SOURCE_TYPE_DEPOSIT, [
                    'player_notify_danger_deposit_title',
                    $ord->secure_id,
                    $ord->created_at,
                    $this->utils->displayCurrency($ord->amount),
                    $this->utils->getLiveChatLink(),
                    $this->utils->getLiveChatOnClick()
                ], [
                    'player_notify_danger_deposit_message',
                    $ord->secure_id,
                    $ord->created_at,
                    $this->utils->displayCurrency($ord->amount),
                    $this->utils->getLiveChatLink(),
                    $this->utils->getLiveChatOnClick()
                ]);
            }

            if($success && $extra_info){
                $bonusResult = $this->promorules->releaseToAfterApplyPromo($extra_info);
            }

            if($success){
                $this->sale_order->userUnlockDeposit($saleOrderId);
            }

            if($success){
                $result_approveSubWallet = $this->sale_order->approveSaleOrderSubWalletWithLock($saleOrderId, $extra_info);

                $success = $result_approveSubWallet;
				$error_message = (!empty($extra_info['error_message'])) ? lang($extra_info['error_message']) : NULL;
            }

            if($success){
                $result_approveSaleOrderPromotion = $this->sale_order->approveSaleOrderPlayerPromotionWithLock($saleOrderId, $extra_info);

				$success = $result_approveSaleOrderPromotion;
				$error_message = (!empty($extra_info['apply_promo_message'])) ? lang($extra_info['apply_promo_message']) : NULL;
			}

			if ($this->utils->isEnabledFeature('show_player_deposit_withdrawal_achieve_threshold')) {
				if ($success) {
					$this->load->model(['player_dw_achieve_threshold']);
					$this->load->library(['payment_library']);
					$this->payment_library->verify_dw_achieve_threshold_amount($ord->player_id, Player_dw_achieve_threshold::ACHIEVE_THRESHOLD_DEPOSIT);
				}
			}

			if( ! empty($extra_info['addRemoteJob']) ){ // $extra_info['addRemoteJob'] is Not Empty, Not only in the case, $success = true.
				$funcName = 'addRemoteSend2Insvr4CreateAndApplyBonusMultiJob';
				if( ! empty($extra_info['addRemoteJob'][$funcName]) ){ // add the array in some cases via $extra_info.
					$thePromorulesId = $extra_info['addRemoteJob'][$funcName]['params']['promorulesId'];
					$thePlayerId = $extra_info['addRemoteJob'][$funcName]['params']['playerId'];
					$thePlayerPromoId = $extra_info['addRemoteJob'][$funcName]['params']['playerPromoId'];
					try {
						$this->load->library(["lib_queue"]);
						$callerType = Queue_result::CALLER_TYPE_ADMIN;
						$caller = $thePlayerId;
						$state  = null;
						$lang=null;
						// $this->lib_queue->addRemoteProcessPreCheckerJob($walletAccountId, $callerType, $caller, $state, $lang);
						$token = $this->lib_queue->addRemoteSend2Insvr4CreateAndApplyBonusMultiJob($thePromorulesId // #1
																	, $thePlayerId // #2
																	, $thePlayerPromoId // #3
																	, $callerType // #4
																	, $caller // #5
																	, $state // #6
																	, $lang // #7
																);
						if( ! empty($token) ){
							unset($extra_info['addRemoteJob'][$funcName]); // completed
						}
					} catch (Exception $e) {
						$formatStr = 'Exception in set_deposit_approved(). (%s)';
						$this->utils->error_log( sprintf( $formatStr, $e->getMessage() ) );
					}
				} // EOF if( ! empty($extra_info['addRemoteJob'][$funcName]) )
			} // EOF if( ! empty($extra_info['addRemoteJob']) ){

		}// EOF if ($this->permissions->checkPermissions(array('deposit_list', 'approve_decline_deposit')))


		$this->returnJsonResult(array('success' => $success, 'error_message'=>$error_message,
			'promo_result'=>$promo_result));
	} // EOF set_deposit_approved

    public function set_deposit_queue_approve($saleOrderId){
        $this->load->library('sale_order_library');
        $success = false;
		$errorMessage = null;
		$promoResult= null;

        $actionlogNotes = $this->input->post("actionlogNotes");
        $approvePlayerGroupLevelRequest = $this->input->post('approve_player_group_level_request') === 'true';
        $approveSubWallet = $this->input->post('approvedSubWallet') == 'true';
        $approvePromotion = $this->input->post('approve_promotion') == 'true';

        if(!$this->sale_order_library->checkProcessingOrderPermissions()) {
            $errorMessage = lang('You do not have permission to approve the deposit request.');
            $this->returnJsonResult(array('success' => $success, 'error_message' => $errorMessage));
        }

        if($this->sale_order_library->isCacheLocked($saleOrderId)){
            $errorMessage = lang('Deposit request has been approved by other admin');
            $this->returnJsonResult(array('success' => $success, 'error_message' => $errorMessage));
        }

        $extraInfo = [
            'actionLog' => $actionlogNotes,
            'showReasonToPlayer' => null,
            'approvePlayerGroupLevelRequest' =>  $approvePlayerGroupLevelRequest,
        ];

        $setted = $this->sale_order_library->setProcessingToQueueApprove($saleOrderId, $extraInfo);
        
        if(!$setted['success']){
            $this->returnJsonResult(array('success' => $success, 'error_message' => $setted['message']));
        }

        $this->sale_order_library->userUnlockDeposit($saleOrderId);

        $handleResult = $this->sale_order_library->handleQueueApprove($saleOrderId, $approveSubWallet, $approvePromotion, Sale_order_library::PROCESSING_BY_QUEUE);

        if(!$handleResult['success']){
            $this->returnJsonResult(array('success' => $success, 'error_message' => $handleResult['message']));
        }
        
        $this->returnJsonResult([
            'success' => true, 
            'error_message' => $errorMessage,
            'promo_result' => $promoResult
        ]);
    }

    /**
	 *
	 * overview: check the disbled status of payment account
	 *
	 * @param int $secureId
	 * @return boolean
	 *
	 */
	public function check_paymentAccount_status_disabled($secureId) {
		$this->load->model(array('payment_account'));
		$this->utils->debug_log('checkPaymentAccountStatusDisabled start in check_paymentAccount_status_disabled()');
		$t = time();
		$result['success'] = $this->payment_account->checkPaymentAccountStatusDisabled($secureId);
		$this->utils->debug_log('checkPaymentAccountStatusDisabled end in check_paymentAccount_status_disabled()', time() - $t);
		return $this->returnJsonResult($result);
	}

    /**
	 *
	 * overview: Withdrawal checking
	 *
	 * detail: it will update all withdrawal to checking
	 *
	 * note: $saleOrderId and $playerId are mandatory, need this once you call this method
	 *
	 * @param int $saleOrderId sale order id
	 * @param int $playerId player id
	 * @return json
	 *
	 */
	public function set_withdrawal_checking($saleOrderId, $playerId) {
		$success = false;
		$this->load->model(array('payment', 'player'));
		$data = array('is_checking' => 'true',
			'processed_checking_time' => $this->utils->getNowForMysql(),
			'processedBy' => $this->authentication->getUserId());
		$playerDetail = $this->player->getPlayerById($playerId);
		if ($this->payment->updateWithdrawalToChecking($saleOrderId, $data)) {
			$this->saveAction(self::ACTION_LOG_TITLE, 'Checking Withdrawal Request', "User " . $this->authentication->getUsername() . "  is checking deposit request of " . $playerDetail['username'] . ".");
			$success = true;
		}
		$this->returnJsonResult(array('success' => $success));
	}

	/**
	 *
	 * overview: Withdrawal request list
	 *
	 * detail: Display the lists of the all withdrawal records with respective status
	 *
	 * @param string $status requet list status
	 * @return load template
	 *
	 */
	public function viewWithdrawalRequestList($status = 'request') {
        $this->load->model('external_system');
		$this->load->library('duplicate_account');
		$this->load->model(array('withdraw_condition','users','common_category','transactions','payment_abnormal_notification'));

		if ($this->permissions->checkPermissions(array('payment_withdrawal_list'))) {
			$firstDayOfMon = $this->utils->getFirstDateOfCurrentMonth();

			$data['enable_timezone_query'] = $this->_getEnableTimezoneQueryWithMethod(__METHOD__);

			$seamless_main_wallet_reference_enabled = $this->utils->getConfig('seamless_main_wallet_reference_enabled');
			if($seamless_main_wallet_reference_enabled) {
				$nonSeamlessGameMap=$this->utils->getNonSeamlessGameSystemMap();
				$this->utils->debug_log('nonSeamlessGameMap', $nonSeamlessGameMap);
				if(!empty($nonSeamlessGameMap)){
					$data['game_platforms'] = array_keys($nonSeamlessGameMap);
				}else{
					$data['game_platforms'] =[];
				}
			}else {
				$data['game_platforms'] = $this->external_system->getAllActiveSytemGameApi();
			}

			// $data['game_platforms'] = $this->external_system->getAllActiveSytemGameApi();
			$data['checking_withdrawal_locking'] = $this->config->item('checking_withdrawal_locking');
			$data['withdrawAPIs'] = $this->external_system->getWithdrawPaymentSystemsKV();
			$data['setting'] = $this->operatorglobalsettings->getCustomWithdrawalProcessingStage();
			$customStageCount = 0;
			for ($i = 0; $i < count($data['setting']); $i++) {
				if (array_key_exists($i, $data['setting'])) {
					$customStageCount += ($data['setting'][$i]['enabled'] ? 1 : 0);
				}
			}
			$data['customStageCount'] = $customStageCount;
			$data['canManagePaymentStatus'] = true;

			$start_today = date("Y-m-d") . ' 00:00:00';
			$end_today = date("Y-m-d") . ' 23:59:59';
			$data['users'] = $this->users->getAllUserNamesNotDeleted();
			if ($this->utils->isEnabledFeature('enable_withdrawal_declined_category') ){
				$data['withdrawalDeclinedCategory'] = $this->common_category->getActiveCategoryByType(common_category::CATEGORY_WITHRAWAL_DECLINED);
			}

            if ($this->utils->getConfig('show_top_10_in_withdrawal')) {
                $data['top_withdrawal_count'] = $this->transactions->getTodayTopWithdraw(10, false, $this->utils->getTodayForMysql());
            }

			#get all search select status ,view stage permission ,view detail button permission
			$status_and_permission = $this->payment_library->getWithdrawalAllStatusPermission($data['setting'],$customStageCount);
			$data['searchStatus'] = json_encode($status_and_permission);

			$data['defaultSortColumn'] = $this->utils->getDefaultSortColumn('withdrawal_list');
			$data['forceSortColumn'] = '';
			$data['forceSortOrder'] = '';

			$data['withdrawal_crypto_currency'] = $this->config->item('enable_withdrawal_crypto_currency');

			# set initial withdraw api filters value
			$withdrawAPIsIds = array_keys($data['withdrawAPIs']);
			array_push($withdrawAPIsIds, 0);

			#add tag
			$data['tags'] = $this->player_manager->getAllTags();
			$data['selected_tags'] =$a= $this->input->get_post('tag_list');
			$data['selected_include_tags'] =$a= $this->input->get_post('tag_list_included');
			$data['player_tags'] = $this->player->getAllTagsOnly();

			$data['conditions'] = $this->safeLoadParams(array(
				'dwStatus'=> $status,
				'search_status' => $status,
				'withdrawal_date_from' => $firstDayOfMon . ' 00:00:00',
				'withdrawal_date_to' => $end_today,
				'withdraw_code' => '',
				'withdrawAPI' => $withdrawAPIsIds,
				'amount_from'=> '',
				'amount_to'=> '',
				'username' => '',
				'realname' => '',
				'processed_by' => '' ,
				'enable_date' => '1',
				'date_range' => '1',
				'search_time' => '1',
				'searchBtn' => '0',
				'timezone' => '',
				'referrer' => '',
				'affiliate' => '',
				'paybus_id' => '',
				'external_id' => '',
				));

			$this->utils->debug_log("=================viewWithdrawalRequestList conditions: ", $data['conditions']);
			
			if(!empty($this->utils->getConfig('conditional_sort_columns'))){
				if($data['conditions']['search_status'] == Wallet_model::PAID_STATUS && $data['conditions']['date_range'] == '2' ){
					$force_sort_data = $this->utils->getConditionSortColumn('withdrawal_list', 'paid_today');	
					$data['forceSortColumn'] = $force_sort_data['column'];
					$data['forceSortOrder'] = $force_sort_data['order'];
				}
			}
			
			$data['abnormal_payment_notification'] = false;
			$data['abnormal_payment_permission'] = false;
			$withdrawal_abnormal_notification = $this->config->item('enabled_withdrawal_abnormal_notification');
			if ($withdrawal_abnormal_notification) {
				$data['abnormal_payment_notification'] = $withdrawal_abnormal_notification;
				$data['abnormal_payment_permission'] = $this->permissions->checkPermissions('view_abnormal_order_alert_of_the_withdrawal_list');
				$data['abnormal_withdrawal'] = $this->payment_abnormal_notification->paymentAbnormaList(Payment_abnormal_notification::ABNORMAL_WITHDRAWAL, Payment_abnormal_notification::ABNORMAL_UNREAD, null, null, 5, 'DESC');
				// $data['count_withdrawal_abnormal'] = $this->payment_abnormal_notification->countWithdrawalAbnorma();
			}

			$this->loadTemplate(lang('pay.04'), '', '', 'payment');
			$this->template->add_css('resources/css/dashboard.css');
			$this->template->add_js('resources/js/payment_management/withdrawalRiskResults.js');
			$this->template->add_js('resources/js/ace/ace.js');
			$this->template->add_js('resources/js/ace/mode-json.js');
			$this->template->add_js('resources/js/ace/theme-tomorrow.js');
			$this->template->add_css('resources/third_party/bootstrap-multiselect-master/dist/css/bootstrap-multiselect.css');
			$this->template->add_js('resources/third_party/bootstrap-multiselect-master/dist/js/bootstrap-multiselect.js');

			$this->template->write_view('sidebar', 'payment_management/sidebar');
			$this->template->write_view('main_content', 'payment_management/withdrawal_list', $data);
			$this->template->render();
		} else {
			$this->error_access();
		}
	}

	public function withdrawal_list_header_counts(){
		$this->load->model(array('wallet_model'));
		$firstDayOfMon = $this->utils->getFirstDateOfCurrentMonth();
		$today_date = date("Y-m-d");

		$statusCountMonthResult = $this->payment_manager->getDWCountAllStatus('withdrawal', $firstDayOfMon , $today_date);
		$statusCountTodayResult = $this->payment_manager->getDWCountAllStatus('withdrawal', $today_date, $today_date);

		$data['statusTotalAmtMonth'] = array();
		$data['statusTotalAmtToday'] = array();

		if ($this->utils->getConfig('display_total_amount_in_withdrawal_quick_filter')) {
			$totalAmtMonthResult = $this->wallet_model->sumAllStatusWithdrawalAmount($firstDayOfMon , $today_date);
			$totalAmtTodayResult = $this->wallet_model->sumAllStatusWithdrawalAmount($today_date , $today_date);

			foreach ($totalAmtMonthResult as $row) {
				$data['statusTotalAmtMonth'][$row['dwStatus']] = $this->utils->formatInt($row['total']);
			}

			foreach ($totalAmtTodayResult as $row) {
				$data['statusTotalAmtToday'][$row['dwStatus']] = $this->utils->formatInt($row['total']);
			}
		}

		$data['statusCountMonth'] = array();
		foreach ($statusCountMonthResult as $row) {
			$data['statusCountMonth'][$row['dwStatus']] = $this->utils->formatInt($row['count']);
		}

		$data['statusCountToday'] = array();
		foreach ($statusCountTodayResult as $row) {
			$data['statusCountToday'][$row['dwStatus']] = $this->utils->formatInt($row['count']);
		}

		$this->utils->debug_log('Payment_management::withdrawal_list_header_counts', ['count_month' => $data['statusCountMonth'], 'count_today' => $data['statusCountToday'] ]);
		$this->returnJsonResult($data);
	}

	/**
	 *
	 * overview: Get Deposit Request Details
	 *
	 * @param int $walletAccountId wallet account id
	 * @param int $paymentMethodId @deprecated
	 * @return	json
	 *
	 */
	public function reviewDepositRequest($walletAccountId, $paymentMethodId) {
		echo json_encode($this->payment_manager->getDepositWithdrawalTransactionDetail($walletAccountId, $paymentMethodId));
	}

	/**
	 * overview: Get Deposit Request Details
	 *
	 * @param int $walletAccountId wallet account id
	 * @param int $paymentMethodId @deprecated
	 * @return	json
	 *
	 */
	public function reviewManualThirdPartyDepositRequest($walletAccountId, $paymentMethodId) {
		echo json_encode($this->payment_manager->reviewManualThirdPartyDepositRequest($walletAccountId, $paymentMethodId));
	}

	/**
	 * overview: Get Deposit Request Details
	 *
	 * note: FYI, the $walletAccountId variable is actually a payment method id, and $id is the walletAccountId
	 *
	 * @param int $walletAccountId payment method id
	 * @param int $id wallet account id
	 * @return	json
	 */
	public function getPaymentMethodDetails($walletAccountId, $id) {
		echo json_encode($this->payment_manager->getPaymentMethodDetails($walletAccountId, $id));
	}

	/**
	 * overview: Get 3rd party Deposit Request Details
	 *
	 * @return	json
	 * @deprecated
	 *
	 */
	public function getAutoThirdPartyDepositWithdrawalTransactionDetail($walletAccountId, $paymentMethodId) {
		echo json_encode($this->payment_manager->getAutoThirdPartyDepositWithdrawalTransactionDetail($walletAccountId, $paymentMethodId));
	}

	/**
	 * overview: Get Withdrawal Request Details
	 *
	 * detail: reviews withdrawal request for a certain wallet account and player
	 *
	 * note: parameters are both manadatory, error will occur.
	 *
	 * @param int $walletAccountId wallet account id
	 * @param int @playerId player id
	 * @return	json
	 *
	 */
	public function reviewWithdrawalRequest($walletAccountId, $playerId) {
		$this->load->model(array('withdraw_condition', 'transaction_notes', 'wallet_model', 'users', 'walletaccount_notes'));

		$data['transactionDetails'] = $this->withdraw_condition->getWithdrawalTransactionDetail($walletAccountId, $playerId);
		$data['walletAccountInternalNotes'] = $this->formatPaymentNotes($this->walletaccount_notes->getWalletAccountNotes(Walletaccount_notes::INTERNAL_NOTE, $walletAccountId), true);
		$data['walletAccountExternalNotes'] = $this->formatPaymentNotes($this->walletaccount_notes->getWalletAccountNotes(Walletaccount_notes::EXTERNAL_NOTE, $walletAccountId), true);
		$data['hasUnfinishedWithdrawCondition'] = $this->withdraw_condition->checkIfPlayerHasUnfinishedCondition($playerId);
		$data['dailyMaxWithdrawal'] = $this->utils->formatCurrencyNoSym($this->player_model->getPlayerWithdrawalRule($playerId)['dailyMaxWithdrawal']);
		$data['totalWithdrawalToday'] = $this->utils->formatCurrencyNoSym($this->player_model->getPlayerCurrentTotalWithdrawalToday($playerId)[0]['totalWithdrawalToday']);
		$data['withdrawSetting'] = $this->operatorglobalsettings->getCustomWithdrawalProcessingStage();
		$this->enableInternalJsonResult();
		$this->checkSubwallectBalance($playerId);
		$data['checkSubwallectBalance'] = $this->_json_result_array;

        if($this->utils->getConfig('enable_wdremark_in_tag_management')){
            $data['wdRemarkText'] = $this->getWdRemarkText($playerId);
        }

		$log_data_encode = $data;
		$this->utils->debug_log('========================================log_data_encode',$log_data_encode);
		$this->output->set_header('Access-Control-Allow-Origin: *');
		$this->output->set_content_type('application/json');
		$this->output->set_output(json_encode($data));
	}

    public function getWdRemarkText($playerId){
        $this->load->model(array('player'));
        $wdRemarkText = lang('player.tm10').' : ';
        $tags = $this->player->getPlayerTags($playerId);
        if(!empty($tags)){
            foreach ($tags as $tag) {
                $wdRemarkText .= $tag['wdRemark']."<br>";
            }
        }
        return $wdRemarkText;
    }

	public function get_withdraw_condition_by_withdraw_id($walletAccountId, $playerId) {
		$this->load->model(array('withdraw_condition', 'transaction_notes'));

		$data['withdrawConditions'] = $this->withdraw_condition->getPlayerWithdrawalCondition($playerId);
		$this->returnJsonResult($data);
	}

	/**
	 *
	 * overview: Transaction Notes
	 *
	 * detail: to format the transaction notes based on the given parameter array
	 *
	 * @param array $transactionNotes
	 * @return string
	 *
	 */
	private function formatTransNotes($transactionNotes) {
		$noteString = '';
		foreach ($transactionNotes as $aNote) {
			$noteString .= sprintf("[%s] %s: %s\n", $aNote['create_date'], $aNote['admin_name'], $aNote['note']);
		}
		$this->utils->debug_log("Formatted transaction notes: ", $noteString);
		return $noteString;
	}

	/**
	 *
	 * overview: depoist and withdrawal Notes
	 *
	 * detail: to format the transaction notes based on the given parameter array
	 *
	 * @param array $transactionNotes
	 * @return string
	 *
	 */
	private function formatPaymentNotes($paymentNotes, $showCustomStage = false) {
		$noteString = '';
		if(!empty($paymentNotes)){
			foreach ($paymentNotes as $aNote) {
				$aNote['content'] = html_entity_decode($aNote['content']) == $aNote['content'] ? $aNote['content'] : html_entity_decode($aNote['content']);

				if($showCustomStage){
					if($aNote['status_name'] == null){
						$aNote['status_name'] = lang('no status');
					}
					$noteString .= sprintf("[%s] %s_%s: %s\n", $aNote['created_at'], $aNote['creater_name'], $aNote['status_name'], $aNote['content']);
				}else{
					$noteString .= sprintf("[%s] %s: %s\n", $aNote['created_at'], $aNote['creater_name'], $aNote['content']);
				}
			}
		}
		$this->utils->debug_log("Formatted Payment Notes: ", $noteString);
		return $noteString;
	}

	/**
	 *
	 * overview: Duplicate accounts
	 *
	 * detail: view duplicate accounts
	 *
	 * @param int $player_id player id
	 * @return load template
	 */
	public function viewDuplicateAccounts($player_id) {
		$data['duplicate_accounts'] = $this->getDuplicateAccounts($player_id);

		$this->load->view('payment_management/ajax_view_duplicate_accounts', $data);
	}

	/**
	 *
	 * overview: Duplicate accounts
	 *
	 * detail: view duplicate accounts in details using player id
	 *
	 * @deprecated
	 *
	 * @param int player_id
	 * @return load template
	 */
	public function viewDuplicateAccountsDetail($player_id = null) {
		$this->load->library('duplicate_account');
		if ($player_id != null) {
			$data['title'] = "Duplicate Account List for Player $player_id";
			$data['player_id'] = $player_id;
			$this->load->view('payment_management/ajax_view_duplicate_accounts_detail', $data);
		} else {
			echo "player_id cannot be NULL! Please add player ID to the URL.\n";
		}
	}

	/**
	 *
	 * overview: Duplicate accounts
	 *
	 * detail: view duplicate account using username
	 *
	 * @param string $username player username
	 * @return load template
	 *
	 */
	public function viewDuplicateAccountsDetailByUsername($username = null) {
		$this->load->library('duplicate_account');
		if ($username != null) {
			$this->load->library('player_manager');
			$player_id = $this->player_manager->getPlayerIdByUsername($username);
			if ($player_id) {
				$data['title'] = lang('Duplicate Accounts Details');
				$data['player_id'] = $player_id;
				$data['username'] = $username;
				//$this->load->view('payment_management/ajax_view_duplicate_accounts_detail', $data);
				$this->loadTemplate(lang('Duplicate Accounts Details'), '', '', 'report');
				$this->template->add_js('resources/js/bootstrap-switch.min.js');
				$this->template->add_css('resources/css/bootstrap-switch.min.css');
				$this->template->write_view('sidebar', 'report_management/sidebar');
				$this->template->write_view('main_content', 'payment_management/view_duplicate_accounts_detail', $data);
				$this->template->render();
			}
		} else {
			echo "player_id cannot be NULL! Please add player ID to the URL.\n";
		}
	}

	/**
	 *
	 * overview: Duplicate accounts
	 *
	 * detail: view duplicate accounts in details using player id
	 *
	 * @param int player_id
	 * @return load template
	 */
	public function viewDuplicateAccountsDetailById($player_id) {
		$this->load->library('duplicate_account');
		if ($player_id) {
			$data['title'] = lang('Duplicate Accounts Details');
			$data['player_id'] = $player_id;
			$this->load->view('payment_management/view_duplicate_accounts_detail_fieldset', $data);
		} else {
			echo "player_id cannot be NULL! Please add player ID to the URL.\n";
		}
	}

	public function viewPlayerCenterFinancialAccountSettings($type = 1) {
		if (!$this->permissions->checkPermissions('player_financial_account_rules_setting')) {
			$this->error_access();
		} else {
			$data['type'] = $type;
			$this->loadTemplate(lang('pay.financial_account_setting'), '', '', 'system');
			$this->template->write_view('sidebar', 'system_management/sidebar');
			$this->template->write_view('main_content', 'payment_management/financial_account_setting/view_player_center_financial_account_setting', $data);
			$this->template->render();
		}
	}

	public function changeFinancialAccountSetting($type = 1) {
		if (!$this->permissions->checkPermissions('player_financial_account_rules_setting')) {
			$this->error_access();
		} else {
			if($type == 0){
				$data = $this->operatorglobalsettings->getSystemSettings(self::FINANCIAL_ACCOUNT_SETTING_OTHERS_LIST);
			} else {
				$this->load->model('financial_account_setting');
				$financial_account_rule = $this->financial_account_setting->getPlayerFinancialAccountRulesByPaymentAccountFlag($type);
				$data['payment_type_flag'] = $type;

				if(is_array($financial_account_rule)){
					$data['account_number_min_length'] = $financial_account_rule['account_number_min_length'];
					$data['account_number_max_length'] = $financial_account_rule['account_number_max_length'];
					$data['account_number_only_allow_numeric'] = $financial_account_rule['account_number_only_allow_numeric'];
					$data['account_name_allow_modify_by_players'] = $financial_account_rule['account_name_allow_modify_by_players'];
					$data['field_show'] = explode(',', $financial_account_rule['field_show']);
					$data['field_required'] = explode(',', $financial_account_rule['field_required']);
				}
			}

			switch ($type) {
				case 0:
					$this->load->view('payment_management/financial_account_setting/ajax_financial_account_setting_others', $data);
					break;
				case 1:
					$this->load->view('payment_management/financial_account_setting/ajax_financial_account_setting_bank', $data);
					break;
				case 2:
					$this->load->view('payment_management/financial_account_setting/ajax_financial_account_setting_ewallet', $data);
					break;
				case 3:
					$cryptocurrencies = $this->utils->getConfig('cryptocurrencies');
					$network_options = $this->utils->getConfig('network_options');
					$crypto_network_options = [];
					$enabled_network_options = false;
					if(!empty($cryptocurrencies) && is_array($cryptocurrencies)
						&& !empty($network_options) && is_array($network_options)){
						foreach ($cryptocurrencies as $key => $crypto) {
							if(isset($network_options[$crypto])){
								foreach ($network_options[$crypto] as $network_option) {
									$crypto_network_options[] = $network_option;
								}
								$enabled_network_options = true;
							}
						}
					}
					$data['enabled_network_options'] = $enabled_network_options;
					$data['crypto_network_options'] = implode(", ", $crypto_network_options);
					$this->load->view('payment_management/financial_account_setting/ajax_financial_account_setting_crypto', $data);
					break;
				default:
					break;
			}
		}
	}
	/**
	 * getMaximumNumberAccountSetting function
	 *
	 * @param [type] $dwbank deposit/withdraw
	 * @return JSON
	 */

	public function getMaximumNumberAccountSetting($dwbank) {
		$this->load->model(['playerbankdetails']);
		switch ($dwbank) {
			case playerbankdetails::DEPOSIT_BANK:
				$MaximumNumberAccountSetting = $this->operatorglobalsettings->getSettingValueWithoutCache('financial_account_deposit_account_limit_range_setting_list');
				break;

			case playerbankdetails::WITHDRAWAL_BANK:
				$MaximumNumberAccountSetting = $this->operatorglobalsettings->getSettingValueWithoutCache('financial_account_withdraw_account_limit_range_setting_list');
			break;
		}

		$return['success'] = !empty($MaximumNumberAccountSetting)? true : false;
		$return['MaximumNumberAccountSetting'] = !empty($MaximumNumberAccountSetting)? $MaximumNumberAccountSetting : null;
		echo json_encode($return);
	}

	public function updateMaximumNumberAccountSetting($dwbank) {
		$this->load->model(['playerbankdetails']);
		$settings = $this->input->post('setting');
        $lastSettings = $this->input->post('lastSetting');
		$success = false;
		$validateFormate = false;
		if(empty(($settings))) {
			$settings = '[{ "rangeTo":"1000", "noOfAccountsAllowed":"1" }]';
			$validateFormate = true;
		} else {
			if(!is_array($settings) && !is_array(json_decode($settings))){
				$validateFormate = false;
				$return['error_msg'] = lang('Save Setting Fail.');
			} else {
				$settings = is_array($settings) ? $settings : json_decode($settings);
				$validateFormate = true;
				foreach ($settings as $key=>$tierItem) {
					$rangeTo = $tierItem['rangeTo'];
					$noOfAccountsAllowed = $tierItem['noOfAccountsAllowed'];
					if ($rangeTo != 'Infinity' && !is_numeric($rangeTo)) {
						$validateFormate = false;
						break;
					} else if (is_numeric($rangeTo)) {
						$rangeTo = number_format($rangeTo, 0, '', '');
						$noOfAccountsAllowed = number_format($noOfAccountsAllowed, 0, '', '');
						if($noOfAccountsAllowed > $this->config->item('max_number_of_account_on_tier_setting')) {
								$validateFormate = false;
                                break;
						}
						if (!empty($settings[$key-1])) {
							if((is_numeric($settings[$key-1]['rangeTo']) && ($rangeTo <= $settings[$key-1]['rangeTo']))
								|| $settings[$key]['noOfAccountsAllowed'] <= $settings[$key-1]['noOfAccountsAllowed']) {
								$validateFormate = false;
								break;
							}
						}
                        if (!empty($settings[$key+1])) {
							if((is_numeric($settings[$key+1]['rangeTo']) && ($rangeTo >= $settings[$key+1]['rangeTo']))
								|| $settings[$key]['noOfAccountsAllowed'] >= $settings[$key+1]['noOfAccountsAllowed']) {
                                $validateFormate = false;
                                break;
                            }
						}
						$settings[$key]['rangeTo'] = $rangeTo;
                        $settings[$key]['noOfAccountsAllowed'] = $noOfAccountsAllowed;
					}
				}
			}
		}

		if(!$validateFormate) {
			$return['error_msg'] = lang('Save Setting Fail.');
		}else{
			switch ($dwbank) {
				case playerbankdetails::DEPOSIT_BANK:
					$list = 'financial_account_deposit_account_limit_range_setting_list';
					break;
				case playerbankdetails::WITHDRAWAL_BANK:
					$list = 'financial_account_withdraw_account_limit_range_setting_list';
				break;
			}
			$old_settings = $this->operatorglobalsettings->getSettingValueWithoutCache($list);
			$this->operatorglobalsettings->saveSettings(array($list => $settings));
			$MaximumNumberAccountSetting = $this->operatorglobalsettings->getSettingValueWithoutCache($list);

			if(!empty(trim($MaximumNumberAccountSetting))) {
				$success = true;
				$return['MaximumNumberAccountSetting'] = $MaximumNumberAccountSetting;
				$this->saveAction(self::ACTION_LOG_TITLE, 'updateMaximumNumberAccountTierListSetting', $this->authentication->getUsername() . "  update $list Success");

			} else {
				$this->operatorglobalsettings->saveSettings(array($list => $old_settings));
				$return['MaximumNumberAccountSetting'] = $old_settings;
				$return['error_msg'] = lang('Save Setting Fail.');
				$this->saveAction(self::ACTION_LOG_TITLE, 'updateMaximumNumberAccountTierListSetting', $this->authentication->getUsername() . "  update $list Fail.");
			}
		}
		$return['success'] = $success;
		echo json_encode($return);
	}

	public function openEditMaximumNumberAccountSettingModal($dwbank, $index= 'ADD') {
		$data['bank_type'] = $dwbank;
        $data['index'] = $index;
        $this->load->view('payment_management/financial_account_setting/ajax_ui_edit_tier', $data);
	}

    public function saveFinancialAccountSetting($type) {
        if (!$this->permissions->checkPermissions('player_financial_account_rules_setting')) {
            $this->error_access();
        } else {
			// OGP-21111: redirect to dashboard when resuming from login timeout
			$var_timeout_resume = $this->utils->getConfig('get_var_resuming_from_token_timeout');
			if ($this->input->get($var_timeout_resume)) {
				$this->utils->debug_log(__METHOD__, 'Redirecting to /home to prevent empty POST');
		    	$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang('Redirecting to dashboard'));
				redirect('/home');
				return;
			}
			$this->load->model('financial_account_setting');
			$field_show = is_array($this->input->post('field_show')) ? $this->input->post('field_show') : array();
			$field_required = is_array($this->input->post('field_required')) ? $this->input->post('field_required') : array();
            $data = array(
                'account_number_min_length'            => $this->input->post("length_min"),
                'account_number_max_length'            => $this->input->post("length_max"),
                'account_number_only_allow_numeric'    => $this->input->post("number_only"),
                'account_name_allow_modify_by_players' => $this->input->post("name_edit"),
                'field_show'                           => implode(',', $field_show),
                'field_required'                       => implode(',', $field_required),
            );

            $this->financial_account_setting->updatePlayerFinancialAccountRules($type, $data);

            $message = lang('con.pym05');
            $this->alertMessage(self::MESSAGE_TYPE_SUCCESS, $message);
            redirect('payment_management/viewPlayerCenterFinancialAccountSettings/'.$type);
        }
    }

	public function saveFinancialAccountSettingOthers() {
		if (!$this->permissions->checkPermissions('player_financial_account_rules_setting')) {
			$this->error_access();
		} else {
			// OGP-21111: redirect to dashboard when resuming from login timeout
			$var_timeout_resume = $this->utils->getConfig('get_var_resuming_from_token_timeout');
			if ($this->input->get($var_timeout_resume)) {
				$this->utils->debug_log(__METHOD__, 'Redirecting to /home to prevent empty POST');
		    	$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang('Redirecting to dashboard'));
				redirect('/home');
				return;
			}
			$this->load->model('operatorglobalsettings');
			foreach (self::FINANCIAL_ACCOUNT_SETTING_OTHERS_LIST as $key) {
				$settings[$key] = $this->input->post($key);
			}

			$this->operatorglobalsettings->saveSettings($settings);

			$message = lang('con.pym05');
			$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, $message);
			redirect('payment_management/viewPlayerCenterFinancialAccountSettings/0');
		}
	}

	/**
	 *
	 * detail: Get Duplicate Accounts
	 *
	 * @param int $player_id player id
	 * @return array
	 */
	public function getDuplicateAccounts($player_id) {
		$this->load->library('duplicate_account');

		$duplicates = $this->duplicate_account->scanDuplicateAccount($player_id); // search for duplicate accounts

		$result = $this->duplicate_account->listOfDuplicates($duplicates, $player_id); // create one list and get rating

		return $result;
	}

	/**
	 * detail: Get Deposit Approved Details
	 *
	 * @param int $walletAccountId wallet account id
	 * @return json
	 */
	public function reviewDepositApprovedLocalBank($walletAccountId) {
		echo json_encode($this->payment_manager->getDepositApprovedTransactionDetail($walletAccountId));
	}

	/**
	 * detail: Get Deposit Approved Details
	 *
	 * @param int $walletAccountId wallet account id
	 * @return json
	 */
	public function reviewDepositApproved($walletAccountId) {
		echo json_encode($this->payment_manager->getDepositApprovedTransactionDetail($walletAccountId));
	}

	/**
	 * detail: Get Deposit Approved Details
	 *
	 * @param int $walletAccountId wallet account id
	 * @return json
	 */
	public function reviewManualThirdPartyDepositApproved($walletAccountId) {
		echo json_encode($this->payment_manager->getManualThirdPartyDepositApprovedTransactionDetail($walletAccountId));
	}

	/**
	 * detail: Get Deposit Approved Details
	 *
	 * @param int $walletAccountId wallet account id
	 * @return json
	 */
	public function reviewManualThirdPartyDepositDeclined($walletAccountId) {
		echo json_encode($this->payment_manager->getManualThirdPartyDepositDeclinedTransactionDetail($walletAccountId));
	}

	/**
	 * detail: Get Withdrawal Approved Details
	 *
	 * @param int $walletAccountId wallet account id
	 * @return json
	 */
	public function reviewWithdrawalApproved($walletAccountId) {
		$this->load->model(array('transaction_notes', 'wallet_model','walletaccount_notes'));
		$data = $this->wallet_model->getWithdrawalApprovedTransactionDetail($walletAccountId);

		$data['walletAccountInternalNotes'] = $this->formatPaymentNotes($this->walletaccount_notes->getWalletAccountNotes(Walletaccount_notes::INTERNAL_NOTE, $walletAccountId), true);
		$data['walletAccountExternalNotes'] = $this->formatPaymentNotes($this->walletaccount_notes->getWalletAccountNotes(Walletaccount_notes::EXTERNAL_NOTE, $walletAccountId), true);

		$data['withdrawSetting'] = $this->operatorglobalsettings->getCustomWithdrawalProcessingStage();
		$withdrawAPIs = $this->external_system->getWithdrawPaymentSystemsKV();
		$withdrawAPI_id = $data[0]['paymentAPI'];
		$data['withdraw_method_display'] = array_key_exists($withdrawAPI_id, $withdrawAPIs) ? $withdrawAPIs[$withdrawAPI_id] : lang('Manual Payment');
		$data['withdraw_id_hidden'] = $withdrawAPI_id;

        if($this->utils->getConfig('enable_wdremark_in_tag_management')){
            $data['wdRemarkText'] = $this->getWdRemarkText($data[0]['playerId']);
        }

		$this->output->set_header('Access-Control-Allow-Origin: *');
		$this->output->set_content_type('application/json');
		$this->output->set_output(json_encode($data));
	}

	/**
	 * detail: Get Deposit Declined Details
	 *
	 * @param int $depositRequestId wallet account id
	 * @return json
	 */
	public function reviewDepositDeclined($depositRequestId) {
		$this->returnJsonResult($this->payment_manager->getDepositDeclinedTransactionDetail($depositRequestId));
	}

	/**
	 * detail: Get Deposit Declined Details
	 *
	 * @param int $depositRequestId wallet account id
	 * @return json
	 */
	public function reviewAuto3rdPartyDepositDeclined($depositRequestId) {
		echo json_encode($this->payment_manager->reviewAuto3rdPartyDepositDeclined($depositRequestId));
	}

	/**
	 * detail: Get Withdrawal Declined Details
	 *
	 * @param int $depositRequestId wallet account id
	 * @return json
	 */
	public function reviewWithdrawalDeclined($depositRequestId) {
		$this->load->model(['wallet_model']);
        $data = $this->wallet_model->getWithdrawalDeclinedTransactionDetail($depositRequestId);
        if($this->utils->getConfig('enable_wdremark_in_tag_management')){
            $data['wdRemarkText'] = $this->getWdRemarkText($data[0]['playerId']);
        }

		$this->returnJsonResult($data);
	}

	/**
	 * setPlayerTotalDeposit
	 *
	 * @return	array
	 */
	public function setPlayerTotalDeposit($playerAccountId) {
		$totalDeposit = $this->payment_manager->getPlayerTotalDeposit($playerAccountId);
	}

	/**
	 * overview: Deposit transaction fee
	 *
	 * detail: add deposit transaction fee
	 *
	 * @return json
	 */
	// public function addDepositTransactionFee() {
	// 	$this->load->model(array('sale_order', 'player_promo', 'transactions'));
	// 	$transaction = "deposit";
	// 	$adminUserId = $this->authentication->getUserId();
	// 	$transaction_fee = $_GET['transaction_fee'];
	// 	$saleorder_id = $_GET['saleorder_id'];
	// 	$saleOrderDetail = $this->sale_order->getSaleOrderById($saleorder_id);
	// 	if ($saleOrderDetail && !empty($transaction_fee)) {
	// 		$saleOrderDetail->amount = $this->utils->formatCurrencyNoSym($saleOrderDetail->amount);
	// 		//translate
	// 		if (!empty($saleOrderDetail->payment_type_name)) {
	// 			$saleOrderDetail->payment_type_name = lang($saleOrderDetail->payment_type_name);
	// 		}
	// 		if (!empty($saleOrderDetail->player_payment_type_name)) {
	// 			$saleOrderDetail->player_payment_type_name = lang($saleOrderDetail->player_payment_type_name);
	// 		}
	// 		$saleOrderDetail->playerActivePromo = $this->player_promo->getPlayerActivePromo($saleOrderDetail->player_id);
	// 		$rlt = (array) $saleOrderDetail;
	// 		$playerId = $rlt['player_id'];
	// 		$related_trans_id = $rlt['transaction_id'];
	// 		$success=$this->transactions->createTransactionFee($transaction_fee, $transaction, $adminUserId, $playerId, $related_trans_id, null, null, $saleorder_id);
	// 		$result = array('success' => $success);
	// 	} else {
	// 		$result = array('success' => false);
	// 	}
	// 	$this->returnJsonResult($result);
	// }

	/**
	 * clearPlayerBonus
	 *
	 * @return	array
	 */
	public function clearPlayerBonus($playerDepositPromoId) {
		if ($playerDepositPromoId != 0) {
			$promoData = array('promoStatus' => 2, //cleared
			);

			$this->payment_manager->clearPlayerDepositBonus($playerDepositPromoId, $promoData);
		}
	}

	public function getPlayerBonusAmount($playerDepositPromoId) {
		$bonusAmount = $this->payment_manager->getPlayerBonusAmount($playerDepositPromoId);
	}

	public function setWithdrawToRequest($walletAccountId, $data = null) {
		$adminUserId = $this->authentication->getUserId();
		$adminUserName = $this->authentication->getUsername();
		$reason = 'Unlock Order by '.$adminUserName. ' successful.';

        $wallet_account = $this->wallet_model->getWalletAccountObject($walletAccountId);
        if(empty($wallet_account)){
            $this->returnJsonResult(['success' => false, 'message'=>lang('Can not find transaction')]);
            return;
        }

        $this->utils->debug_log("=====setWithdrawToRequest transactionCode", $wallet_account['transactionCode'], "adminUserId", "data", $data ,'dwStatus', $wallet_account['dwStatus']);

        $result = $this->wallet_model->requestWithdrawal($adminUserId, $walletAccountId, $reason, false);
        if(!$result) {
			$reason = 'Unlock Order by '.$adminUserName. ' failed.';
        }
        $this->wallet_model->addWalletaccountNotes($walletAccountId,$adminUserId,$reason,$wallet_account['dwStatus'],Wallet_model::REQUEST_STATUS);
        $this->returnJsonResult(['success' => $result, 'message'=>$reason]);
	}

	public function setWithdrawToPayProc($walletAccountId, $withdrawApi = 0, $data = null) {
		if (!$this->permissions->checkPermissions('pass_decline_payment_processing_stage')) {
			$this->utils->error_log('permission failed', 'pass_decline_payment_processing_stage');
			$this->error_access();
			return;
		}

		$actionlogNotes = 'Manual Payment : Set Withdraw To PayProc';
		$adminUserId = $this->authentication->getUserId();
		$transaction_fee = $this->input->post('transaction_fee');
		// $showRemarksToPlayerForPaid = $this->input->post(null');
		$ignoreWithdrawalAmountLimit = $this->input->post('ignoreWithdrawalAmountLimit');
		$ignoreWithdrawalTimesLimit = $this->input->post('ignoreWithdrawalTimesLimit');

        $wallet_account = $this->wallet_model->getWalletAccountObject($walletAccountId);
        if(empty($wallet_account)){
            $this->returnJsonResult(['success' => false, 'message'=>lang('Can not find transaction')]);
            return;
        }

		$this->utils->debug_log("=====setWithdrawToPayProc transactionCode", $wallet_account['transactionCode'], "adminUserId", $adminUserId, "withdrawApi", $withdrawApi, "data", $data);

        $playerId = $wallet_account['playerId'];
        $amount = $wallet_account['amount'];
        $playerWithdrawalRule = $this->utils->getWithdrawMinMax($playerId);
        list($withdrawalProcessingCount, $processingAmount) = $this->wallet_model->countTodayProcessingWithdraw($playerId);
		list($withdrawalPaidCount, $paidAmount) = $this->transactions->count_today_withdraw($playerId);

		#check if withdrawal amount exceeds limit
		$amount_used = $processingAmount + $paidAmount;
		if ($amount + $amount_used > $playerWithdrawalRule['daily_max_withdraw_amount']) {
			if($ignoreWithdrawalAmountLimit && $this->permissions->checkPermissions('ignore_vip_daily_withdrawal_maximum_amount_settings_when_approve')){
				$this->utils->debug_log("=====setWithdrawToPayProc ignoreWithdrawalAmountLimit", $ignoreWithdrawalAmountLimit, "adminUserId", $adminUserId);
			}
			else{
				$result=['success'=>false, 'message'=> lang('notify.56') . ' ( $'. $amount_used . '/ $'.$playerWithdrawalRule['daily_max_withdraw_amount'].' )'];
				$this->returnJsonResult($result);
				return;
			}
		}
        if ($withdrawalProcessingCount + $withdrawalPaidCount >= $playerWithdrawalRule['withdraw_times_limit']) {
			if($ignoreWithdrawalTimesLimit && $this->permissions->checkPermissions('ignore_vip_daily_withdrawal_maximum_times_settings_when_approve')){
				$this->utils->debug_log("=====setWithdrawToPayProc ignoreWithdrawalTimesLimit", $ignoreWithdrawalTimesLimit, "adminUserId", $adminUserId);
			}
			else{
	            $result=['success'=>false, 'message'=>lang('notify.106')];
	            $this->returnJsonResult($result);
	            return;
			}

        }

		if ($withdrawApi > 0) {
			$filter_status_before_submit = $this->utils->getConfig('filter_status_before_submit_3rd_withdraw_api');
			if(!empty($filter_status_before_submit) && is_array($filter_status_before_submit)) {
                if(isset($wallet_account['dwStatus'])){
                	if(in_array($wallet_account['dwStatus'], $filter_status_before_submit)){
                		$result=['success'=>false, 'message'=>lang('pay.notstayprocess')];
	            		$this->returnJsonResult($result);
	            		return;
                	}
                }
            }
			# Trigger the API payment process automatically. This will put the status to payProc
			$this->setWithdrawToPaid($walletAccountId, $withdrawApi, $data);
		} else {
			$this->load->model(array('wallet_model'));
			$succ = $this->wallet_model->payProcWithdrawal($adminUserId, $walletAccountId, $withdrawApi, $actionlogNotes, $transaction_fee, null);
			$this->returnJsonResult(['success' => $succ]);
			return;
		}
	}

	/**
	 * detail: update the wallet to paid
	 *
	 * @param int $walletAccountId wallet account id
	 * @param int $withdrawApi costum
	 * @return json
	 */
	public function setWithdrawToPaid($walletAccountId, $withdrawApi = -1, $data = null, $batchApproved = false) {
		$result = array('success' => false, 'message' => '');
		if (!$this->permissions->checkPermissions('set_withdrawal_request_to_paid')) {
			$result=['success'=>false, 'message'=>lang('Sorry, no permission')];
			$this->returnJsonResult($result);
			return;
		}

		$this->load->model(['wallet_model', 'withdraw_condition','system_feature','walletaccount_timelog']);
		$adminUserId = $this->authentication->getUserId();

		if($this->wallet_model->getWalletAccountStatus($walletAccountId) == 'paid'){
			$result = ['success' => false, 'message'=>lang('pay.alreadypaid')];
			return $this->returnJsonResult($result);
		}

		//check current user withdrawal amount
		if(!$this->wallet_model->checkUserWithdrawalAmount($adminUserId, $walletAccountId)){
			$result = ['success' => false, 'message'=>lang('No enough withdrawal amount permission')];
			return $this->returnJsonResult($result);
		}

		$controller = $this;
		// $result = array(); # will be passed to closure as reference
        $wallet_account = $this->wallet_model->getWalletAccountObject($walletAccountId);
        if(empty($wallet_account)){
            $this->returnJsonResult(['success' => false]);
            return;
        }

        $playerId = $wallet_account['playerId'];

		$success = $this->lockAndTrans(Utils::LOCK_ACTION_BALANCE, $playerId, function () use ($controller, $walletAccountId, $adminUserId, $withdrawApi, &$result, $playerId, $data, $batchApproved) {
			$result = array('success' => false, 'message' => '');

			if($this->wallet_model->isLockedForManual($walletAccountId, $adminUserId)){
				$result = ['success' => false, 'message'=>lang('this withdrawal has been locked')];

				return $result['success'];
			}

			$transaction_fee = $controller->input->post('transaction_fee') ?: 0;
			// $showRemarksToPlayerForPaid = $controller->input->post('showRemarksToPlayerForPaid');

			# Get payment detail
			$transactionDetail = $controller->withdraw_condition->getWithdrawalTransactionDetail($walletAccountId);
            $applyWithdrawDatetime = isset($transactionDetail[0]['dwDateTime']) ? $transactionDetail[0]['dwDateTime'] : null;

			if (count($transactionDetail) < 1) {
				$controller->utils->error_log("No transaction detail found for walletAccountId [$walletAccountId]");
				$result['message'] = lang('error.withdrawal_failed');
				return $result['success'];
			}

			$isPayProc = ($transactionDetail[0]['dwStatus'] == Wallet_model::PAY_PROC_STATUS);

			$actionlogNotes = $batchApproved ? 'Batch Approved.' : 'Set Withdraw To Paid.';
			# Perform API withdraw
			if ($withdrawApi > 0 && !$isPayProc) {
				# Update status to 'payProc' that we are submitting to API
				$result = $controller->wallet_model->submitWithdrawalToAPI($adminUserId, $walletAccountId, $actionlogNotes, $transaction_fee, null, $withdrawApi);
				if($result['success']){

					# Perform payment using API
					list($loaded, $apiClassName) = $controller->utils->loadExternalSystemLib($withdrawApi);
					if ($loaded) {
						$controller->utils->debug_log('withdraw', $transactionDetail[0]['transactionCode']);
						if(is_null($data)){
							$paymentResult = $controller->$apiClassName->submitWithdrawRequest(
								$transactionDetail[0]['bankTypeId'],
								$transactionDetail[0]['bankAccountNumber'],
								$transactionDetail[0]['bankAccountFullName'],
								$transactionDetail[0]['amount'],
								$transactionDetail[0]['transactionCode']
							);
						}
						else{
							$paymentResult = $controller->$apiClassName->submitWithdrawRequest(
								$transactionDetail[0]['bankTypeId'],
								$transactionDetail[0]['bankAccountNumber'],
								$transactionDetail[0]['bankAccountFullName'],
								$transactionDetail[0]['amount'],
								$transactionDetail[0]['transactionCode'],
								$data
							);
						}

						$controller->utils->debug_log('paymentResult', $paymentResult);

						$result['success'] = $paymentResult['success'];
						$result['message'] = $paymentResult['message'];
						$result['response_result'] = (isset($paymentResult['response_result'])) ? $paymentResult['response_result'] : FALSE;
						$result['lock'] = (isset($paymentResult['lock'])) ? $paymentResult['lock'] : FALSE;

						if($result['success']){
							$this->wallet_model->addWalletaccountNotes($walletAccountId, $adminUserId, $result['message'], $transactionDetail[0]['dwStatus'], Wallet_model::PAY_PROC_STATUS, Walletaccount_timelog::ADMIN_USER);
						}

                        $withdrawConditionIds = $this->withdraw_condition->getAvailableWithdrawConditionIds($playerId, false, $applyWithdrawDatetime);
						$this->withdraw_condition->disablePlayerWithdrawalCondition( $playerId  // #1
														, Withdraw_condition::REASON_AFBW  // #2
														, Withdraw_condition::DETAIL_STATUS_FINISHED_BETTING_AMOUNT_WHEN_WITHDRAW  // #3
                                                        , $withdrawConditionIds //$4
													);

					} else {
						# load API failed, mark payment as failed
						$controller->utils->debug_log("Load API [$withdrawApi] for withdrawal failed, payment is not done through API.");
						$result['message'] = lang('error.withdrawal_failed');
						$result['success'] = false;
					}

				}else{
					$this->utils->error_log('submitWithdrawalToAPI failed', $adminUserId, $walletAccountId);
				}
			} else {
				# API payment not enabled or already in payProc (API already processed but failed)
				# Set the withdraw request status to 'paid' only when API payment is not enabled
				$message='';
				$result['success'] = $controller->wallet_model->paidWithdrawal($adminUserId, $walletAccountId,
					$actionlogNotes, $transaction_fee, null, false, $message);
				if(!empty($message)){
					$result['message']= $message;
				}

                $withdrawConditionIds = $this->withdraw_condition->getAvailableWithdrawConditionIds($playerId, false, $applyWithdrawDatetime);
				$this->withdraw_condition->disablePlayerWithdrawalCondition($playerId // #1
												, Withdraw_condition::REASON_AFBW // #2
												, Withdraw_condition::DETAIL_STATUS_FINISHED_BETTING_AMOUNT_WHEN_WITHDRAW // #3
                                                , $withdrawConditionIds //#4
											);
				$wallet_account = $this->wallet_model->getWalletAccountObject($walletAccountId);
				$last_deposit_amount = $this->CI->transactions->queryAmountByPlayerIdFromLastTransaction($wallet_account['playerId']);
				$is_first_withdrawal = $this->CI->transactions->isOnlyFirsWithdrawal($wallet_account['playerId']);
				$currency = $this->utils->getCurrentCurrency();
				
				$this->utils->playerTrackingEvent($playerId, 'TRACKINGEVENT_SOURCE_TYPE_WITHDRAWAL_SUCCESS',
				array(
					'transactionCode' 	=> $wallet_account['transactionCode'],
					'dwDateTime' 		=> $wallet_account['dwDateTime'],
					'amount' 			=> $wallet_account['amount'],
					"Type"				=> "Withdrawal",
					"Status"			=> "Success",
					"Currency"			=> $currency['currency_code'],
					"TransactionID"	    => $wallet_account['transactionCode'],
					"Channel"			=> ($wallet_account['paymentAPI'] > 0) ? $this->CI->external_system->getSystemName($wallet_account['paymentAPI']) : "Manual Payment",
					"TimeTaken" 		=> strtotime($wallet_account['processDatetime']) - strtotime($wallet_account['dwDateTime']),
					"LastDepositAmount" => $last_deposit_amount,
					"FirstWithdrawal"	=> ($is_first_withdrawal) ? "Yes" : "No",
				));
            }

			return $result['success'];
		});


		$controller->utils->debug_log('result', $result, 'success', $success);

		if (!$success && empty($result['message']) && isset($result['err_msg'])) {
			if(!isset($result['err_msg'])){
                $result['message'] = lang('error.default.db.message');
            }else{
			    $result['message'] = $result['err_msg'];
            }
		}

		if (!$success) {
			if(isset($result['response_result']) && $result['response_result']){
				$response_result = $result['response_result'];
				list($loaded, $apiClassName) = $controller->utils->loadExternalSystemLib($withdrawApi);
				if ($loaded) {
					$response_result_id = $controller->$apiClassName->submitPreprocess($response_result[0], $response_result[1], $response_result[2], $response_result[3], $response_result[4], $response_result[5]);
					$controller->utils->debug_log('==============setWithdrawToPaid record response_result when fail, response_result_id', $response_result_id);
				}
			}

			$this->utils->error_log('withdraw api failed, keep processing', $walletAccountId, $result);
			# API fail, do not set the order status to fail, but append a message
			# $this->respondToWithdrawalDeclined($walletAccountId, 'api failed');
			$transactionDetail = $controller->withdraw_condition->getWithdrawalTransactionDetail($walletAccountId);

			if($result['lock']) {
				$succ = $this->wallet_model->updateWithdrawalRequestStatusToUnknownStatus($adminUserId, $walletAccountId, $result['message']);
				if($succ) {
					$this->wallet_model->addWalletaccountNotes($walletAccountId, $adminUserId, lang("Payment Failed") . " - " . $result['message'], $transactionDetail[0]['dwStatus'], Wallet_model::LOCK_API_UNKNOWN_STATUS, Walletaccount_timelog::ADMIN_USER);
					$this->saveAction(self::ACTION_LOG_TITLE, 'Modify Witdrawal Request Status', "User " . $this->authentication->getUsername() . " has changed withdrawal request [$walletAccountId] to ".wallet_model::LOCK_API_UNKNOWN_STATUS);
				}
			}else{
				$this->wallet_model->addWalletaccountNotes($walletAccountId, $adminUserId, lang("Payment Failed") . " - " . $result['message'], $transactionDetail[0]['dwStatus'], null, Walletaccount_timelog::ADMIN_USER);
			}
		}

		if ($success && $withdrawApi == -1) {
			# send prompt message when withdrawal order is success
			if ($this->utils->isEnabledFeature('enable_sms_withdrawal_prompt_action_success')) {

				$this->load->model(['cms_model', 'queue_result', 'player_model']);
				$this->load->library(["lib_queue", "sms/sms_sender"]);

				$player = $this->player_model->getPlayerInfoDetailById($playerId);
				$mobileNumIsVeridied = $player['verified_phone'];

				if($mobileNumIsVeridied){
					$isUseQueueToSend = $this->utils->isEnabledFeature('enabled_send_sms_use_queue_server');
					$dialingCode = $player['dialing_code'];
					$mobileNum = !empty($dialingCode)? $dialingCode.'|'.$player['contactNumber'] : $player['contactNumber'];
					$smsContent = $this->cms_model->getManagerContent(Cms_model::SMS_MSG_WITHDRAWAL_SUCCESS);
					$use_new_sms_api_setting = $this->utils->getConfig('use_new_sms_api_setting');
					$useSmsApi = null;
					$sms_setting_msg = '';
					if ($use_new_sms_api_setting) {
					#restrictArea = action type
						$sessionId = $this->session->userdata('session_id');
						$restrictArea = 'sms_api_manager_setting';
						list($useSmsApi, $sms_setting_msg) = $this->utils->getSmsApiNameByNewSetting($playerId, $mobileNum, $restrictArea, $sessionId);
					}

					$this->utils->debug_log(__METHOD__, 'get new sms api',$useSmsApi, $sms_setting_msg);

					if ($isUseQueueToSend) {
						$callerType = Queue_result::CALLER_TYPE_ADMIN;
						$caller = $adminUserId;
						$state  = null;
						$this->lib_queue->addRemoteSMSJob($mobileNum, $smsContent, $callerType, $caller, $state, null);
					} else {
						$this->sms_sender->send($mobileNum, $smsContent, $useSmsApi);
					}
				}
			}
		}

        $this->load->library(['player_notification_library']);
        if($success){

            if($this->utils->getConfig('enable_fast_track_integration')) {
                $this->load->library('fast_track');
                $this->fast_track->approveWithdraw($wallet_account);
            }

			$wallet_account = $this->wallet_model->getWalletAccountObject($walletAccountId);
			$last_deposit_amount = $this->CI->transactions->queryAmountByPlayerIdFromLastTransaction($wallet_account['playerId']);
			$is_first_withdrawal = $this->CI->transactions->isOnlyFirsWithdrawal($wallet_account['playerId']);
			$currency = $this->utils->getCurrentCurrency();

			$this->utils->playerTrackingEvent($playerId, 'TRACKINGEVENT_SOURCE_TYPE_WITHDRAWAL',
			array(
				'transactionCode' 	=> $wallet_account['transactionCode'],
				'dwDateTime' 		=> $wallet_account['dwDateTime'],
				'amount' 			=> $wallet_account['amount'],
				"Type"				=> "Withdrawal",
				"Status"			=> "Success",
				"Currency"			=> $currency['currency_code'],
				"TransactionID"	    => $wallet_account['transactionCode'],
				"Channel"		    => ($wallet_account['paymentAPI'] > 0) ? $this->CI->external_system->getSystemName($wallet_account['paymentAPI']) : "Manual Payment",
				"TimeTaken" 		=> strtotime($wallet_account['processDatetime']) - strtotime($wallet_account['dwDateTime']),
				"LastDepositAmount" => $last_deposit_amount,
				"FirstWithdrawal"	=> ($is_first_withdrawal) ? "Yes" : "No",

			));

			$userId = $this->users->getSuperAdminId();
			$this->triggerWithdrawalEvent($playerId, $walletAccountId, null, null, $userId);

            $this->player_notification_library->success($wallet_account['playerId'], Player_notification::SOURCE_TYPE_WITHDRAWAL, [
                'player_notify_success_withdrawal_title',
                $wallet_account['transactionCode'],
                $wallet_account['dwDateTime'],
                $this->utils->displayCurrency($wallet_account['amount']),
                $this->utils->getPlayerHistoryUrl('withdrawal')
            ], [
                'player_notify_success_withdrawal_message',
                $wallet_account['transactionCode'],
                $wallet_account['dwDateTime'],
                $this->utils->displayCurrency($wallet_account['amount']),
                $this->utils->getPlayerHistoryUrl('withdrawal')
            ]);
        }else{
            $this->player_notification_library->danger($wallet_account['playerId'], Player_notification::SOURCE_TYPE_WITHDRAWAL, [
                'player_notify_danger_withdrawal_title',
                $wallet_account['transactionCode'],
                $wallet_account['dwDateTime'],
                $this->utils->displayCurrency($wallet_account['amount']),
                $this->utils->getLiveChatLink(),
                $this->utils->getLiveChatOnClick()
            ], [
                'player_notify_danger_withdrawal_message',
                $wallet_account['transactionCode'],
                $wallet_account['dwDateTime'],
                $this->utils->displayCurrency($wallet_account['amount']),
                $this->utils->getLiveChatLink(),
                $this->utils->getLiveChatOnClick()
            ]);
        }

        if ($this->utils->isEnabledFeature('show_player_deposit_withdrawal_achieve_threshold')) {
			if ($success) {
				$this->load->model(['player_dw_achieve_threshold']);
				$this->load->library(['payment_library']);
				$this->payment_library->verify_dw_achieve_threshold_amount($playerId, Player_dw_achieve_threshold::ACHIEVE_THRESHOLD_WITHDRAWAL);
			}
        }
		$this->returnJsonResult($result);
	}

	/**
	 * detail: Respond to Withdrawal approve
	 *
	 * @param int $walletAccountId wallet account id
	 * @param int $playerId player id
	 * @param string $showRemarksToPlayer costum
	 * @param string $nextStatus
	 * @return string
	 */
	public function respondToWithdrawalRequest($walletAccountId, $playerId, $showRemarksToPlayer = '0', $nextStatus, $batchProcess = false) {

		$dwStatus=$this->wallet_model->getWalletAccountStatus($walletAccountId);

		$getWithdrawStatusPermissions = $this->getWithdrawStatusPermissions($dwStatus);

		if (!$this->permissions->checkPermissions($getWithdrawStatusPermissions)) {
			$this->saveAction(self::ACTION_LOG_TITLE, 'Pass Withdrawal Request Permission denied', "User " . $this->authentication->getUsername() . " permission failed " .json_encode($getWithdrawStatusPermissions) . ' current status:' . $dwStatus);
			$this->utils->error_log('permission failed', $getWithdrawStatusPermissions);
			$this->returnText(lang('No Permission'));
			return;
		}

		$actionlogNotes = $batchProcess ? 'Batch Process.' : 'Set Withdraw To Custom Stage';

		if (empty($walletAccountId)) {
			$this->utils->error_log('walletAccountId is empty', $walletAccountId, $playerId, $showRemarksToPlayer, $nextStatus);
			$this->returnText(lang('Not Found ID'));
			return;
		}

		$adminUserId = $this->authentication->getUserId();
		if ($this->utils->getConfig('enabled_adminusers_withdrawal_cs_stage_setting')) {
			//check current user withdrawal amount
			if(!$this->wallet_model->checkUserWithdrawalAmount($adminUserId, $walletAccountId,$nextStatus)){
				$this->returnText(lang('No enough withdrawal amount permission'));
				return;
			}
		}

		$this->load->model(array('wallet_model', 'withdraw_condition', 'system_feature'));

		$playerId = $this->wallet_model->getPlayerIdFromWalletAccount($walletAccountId);
		$walletAccount = $this->wallet_model->getWalletAccountBy($walletAccountId);

		$lockedKey=null;
		$lock_it = $this->lockPlayerBalanceResource($playerId, $lockedKey);

		if (!$lock_it) {
			$this->utils->error_log('lock balance failed', 'id', $playerId);
			$this->returnText(lang('Lock Failed'));
			return;
		}

		//lock success
		try {
			$this->startTrans();

			$user_id = $this->authentication->getUserId();
			$currentStatus = $walletAccount->dwStatus;

			$succ = $this->wallet_model->updateWithdrawalRequestStatus($user_id, $walletAccountId, $nextStatus, $actionlogNotes, $showRemarksToPlayer);
			$this->saveAction(self::ACTION_LOG_TITLE, 'Modify Witdrawal Request Status', "User " . $this->authentication->getUsername() . " has changed withdrawal request [$walletAccountId] from [$currentStatus] to [$nextStatus].");

			$succ = $this->endTransWithSucc() && $succ;
			if ($succ) {
				return $this->returnText('success');
			} else {
				return $this->returnText(lang('Update Status Failed'));
			}

		} finally {
			// release it
			$rlt = $this->releasePlayerBalanceResource($playerId, $lockedKey);
		}
	}

	public function reCreateWithdrawalAfterDeclined($declinedWalletAccountId){
		if(!$this->utils->getConfig('enable_recreate_withdrawal_after_declined')){
			return;
		}

		$this->load->library(['payment_library']);
		$this->load->model(array('playerbankdetails', 'player_model', 'wallet_model', 'daily_player_trans', 'operatorglobalsettings', 'banktype', 'walletaccount_notes','walletaccount_timelog','group_level', 'http_request'));


		$result = array('success' => false, 'message' => '');
		$wallet_account = $this->wallet_model->getWalletAccountObject($declinedWalletAccountId);
        if(empty($wallet_account)){
			$result['message'] = lang('Can not find withdrawal order');
            return $result;
        }

		$isDecline = $wallet_account['dwStatus'] == Wallet_model::DECLINED_STATUS;
		if(!$isDecline){
			$result['message'] = lang('Withdrawal Status Invalid');
            return $result;
		}

		$getWithdrawalCustomSetting = $this->utils->getConfig('enable_pending_review_custom') ? json_decode($this->operatorglobalsettings->getSetting('custom_withdrawal_processing_stages')->template,true) : array();

		$playerId = $wallet_account['playerId'];
		$controller=$this;

		$result=['success'=>false, 'message'=>lang('error.default.db.message')];
		$success = $this->lockAndTrans(Utils::LOCK_ACTION_BALANCE, $playerId, function ()
			use ($controller, $playerId, &$result, $getWithdrawalCustomSetting, $wallet_account) {

			$userId              	 = $controller->authentication->getUserId();
			$adminUsername       	 = $controller->authentication->getUsername();
			$amount              	 = $wallet_account['amount'];
			$date                	 = $controller->utils->getNowForMysql();
			$internal_note       	 = "Re Create Withdrawal After Declined {$wallet_account['transactionCode']} By {$adminUsername}";
			$external_note       	 = null;
			$status 			 	 = 'request';
			$playerBankDetailsId 	 = $wallet_account['player_bank_details_id'];
			$player                  = $controller->player_model->getPlayerById($playerId);
			$ipAddress               = $controller->utils->getIP();
			$playerAccount           = $controller->wallet_model->getMainWalletBy($playerId);
			$enabled_withdrawal      = $player->enabled_withdrawal;
			$playerMainWalletBalance = $playerAccount->totalBalanceAmount;
			$playerAccountId         = $playerAccount->playerAccountId;
			$geolocation             = $controller->utils->getGeoplugin($ipAddress);
			$transactionCode         = $controller->wallet_model->getRandomTransactionCode();
			$playerBankDetails       = $this->playerbankdetails->getBankList(array('playerBankDetailsId' => $playerBankDetailsId))[0];
			$bankName                = $this->banktype->getBankTypeById($playerBankDetails['bankTypeId'])->bankName;
			$dwLocation              = implode(',', array_values($geolocation));

			if ($enabled_withdrawal == Player_model::WITHDRAWAL_DISABLED) {
				$result['message']=lang("Player Withdrawal is disabled");
				$result['success']=false;
				return $result['success'];
			}

			if ($playerMainWalletBalance < $amount) {
				$result['message']=lang("Withdrawal Amount is greater than Current Balance");
				$result['success']=false;
				return $result['success'];
			}

			$withdrawFeeAmount = 0;
			$calculationFormula = '';
			#if enable config get withdrawFee from player
			if($this->utils->getConfig('enable_withdrawl_fee_from_player') && $this->group_level->isOneWithdrawOnly($playerId)){
				list($withdrawFeeAmount,$calculationFormula) = $this->payment_library->chargeFeeWhenWithdrawalAmountOverMonthlyAmount($playerId, $player->levelId, $amount);

				if ($playerMainWalletBalance < $amount + $withdrawFeeAmount) {
					$result['message']=lang("Withdrawal Amount + Withdrawal fee is greater than Current Balance");
					$result['success']=false;
					return $result['success'];
				}
			}

			$withdrawBankFeeAmount = 0;
			$calculationFormulaBank = '';
			if ($this->utils->getConfig('enable_withdrawl_bank_fee') && $this->group_level->isOneWithdrawOnly($playerId)) {
				list($withdrawBankFeeAmount,$calculationFormulaBank) = $this->payment_library->calculationWithdrawalBankFee($playerId, $this->banktype->getBankTypeById($playerBankDetails['bankTypeId'])->bank_code, $amount);

				if ($withdrawFeeAmount > 0) {
					$checkFeeAmt = $amount + $withdrawBankFeeAmount + $withdrawFeeAmount;
				}else{
					$checkFeeAmt = $amount + $withdrawBankFeeAmount;
				}

				$this->utils->debug_log('enable_withdrawl_bank_fee' , $playerMainWalletBalance, $amount, $withdrawBankFeeAmount, $calculationFormulaBank);
				if ($checkFeeAmt > $playerMainWalletBalance) {
					$result['message']=lang("Withdrawal Amount + Withdrawal bank fee is greater than Current Balance");
					$result['success']=false;
					return $result['success'];
				}
			}

			$walletAccountData = array(
				'playerAccountId'        => $playerAccountId,
				'walletType'             => 'Main',
				'amount'                 => $amount,
				'dwMethod'               => 1,
				'dwStatus'               => Wallet_model::REQUEST_STATUS,
				'dwDateTime'             => $date,
				'transactionType'        => 'withdrawal',
				'dwIp'                   => $ipAddress,
				'dwLocation'             => $dwLocation,
				'transactionCode'        => $transactionCode,
				'status'                 => '0',
				'before_balance'         => $playerMainWalletBalance,
				'after_balance'          => $playerMainWalletBalance - $amount,
				'playerId'               => $playerId,
				'player_bank_details_id' => $playerBankDetailsId,
				'notes'                  => '',
				'bankAccountFullName'    => $playerBankDetails['bankAccountFullName'],
				'bankAccountNumber'      => $playerBankDetails['bankAccountNumber'],
				'bankName'               => $bankName,
				'bankAddress'            => $playerBankDetails['bankAddress'],
				'bankCity'               => $playerBankDetails['city'],
				'bankProvince'           => $playerBankDetails['province'],
				'bankBranch'             => $playerBankDetails['branch'],
				'withdrawal_fee_amount'	 => $withdrawFeeAmount,
				'withdrawal_bank_fee' 	 => $withdrawBankFeeAmount,
			);

			# OGP-3531
			if($this->system_feature->isEnabledFeature('enable_withdrawal_pending_review')){
				if($this->checkPlayerIfTagIsUnderPendingWithdrawTag($playerId)){
					$walletAccountData['dwStatus'] = Wallet_model::PENDING_REVIEW_STATUS;
				}
			}

			#OGP-17242
			if($this->utils->getConfig('enable_pending_review_custom')){
				if(!empty($getWithdrawalCustomSetting['pendingCustom']) && $getWithdrawalCustomSetting['pendingCustom']['enabled']){
					if($this->checkPlayerIfTagIsUnderPendingCustomWithdrawTag($playerId)){
						$walletAccountData['dwStatus'] = Wallet_model::PENDING_REVIEW_CUSTOM_STATUS;
						$this->utils->debug_log("check player tag under pending custom walletAccountData", $walletAccountData['dwStatus']);
					}
				}
			}

			$localBankWithdrawalDetails = array(
				'withdrawalAmount' => $amount,
				'playerBankDetailsId' => $playerBankDetailsId,
				'depositDateTime' => $date,
				'status' => 'active',
			);

			$walletAccountId = $controller->wallet_model->newWithdrawal($walletAccountData, $localBankWithdrawalDetails, $playerId);

			#add notes to walletaccount_notes content
			if(isset($walletAccountId)){
				if (!empty($internal_note)) {
					$this->walletaccount_notes->add($internal_note, $userId, Walletaccount_notes::INTERNAL_NOTE, $walletAccountId);
				}

				if (!empty($external_note)) {
					$this->walletaccount_notes->add($external_note, $userId, Walletaccount_notes::EXTERNAL_NOTE, $walletAccountId);
				}

				$newWithdrawalActionlogPending = sprintf("New Withdrawal is success processing, status => %s", $status);
				if($this->utils->getConfig('enable_withdrawl_fee_from_player') && $this->group_level->isOneWithdrawOnly($playerId)){
					$newWithdrawalActionlogPending = sprintf("New Withdrawal is success processing, status => %s ; %s ; Withdrawal Fee Amount is %s",$status, $calculationFormula, $withdrawFeeAmount);
				}

				if($this->utils->getConfig('enable_withdrawl_bank_fee') && $this->group_level->isOneWithdrawOnly($playerId)){
					if ($withdrawBankFeeAmount > 0) {
						$newWithdrawalActionlogPending = $newWithdrawalActionlogPending . ' ' . $calculationFormulaBank;
					}
				}

				$this->wallet_model->addWalletaccountNotes($walletAccountId, $userId, $newWithdrawalActionlogPending, $status, null,Walletaccount_timelog::ADMIN_USER);
			}

			$result['success']=!!$walletAccountId;
			$result['walletAccountId']=$walletAccountId;

			if($result['success']){
				$result['message']=null;
			}

			return $result['success'];

		}); // end of lock and trans

		if($result['success']){
			$this->saveHttpRequest($playerId, Http_request::TYPE_WITHDRAWAL);
			$newOrderId = !empty($result['walletAccountId']) ? $result['walletAccountId'] : null;
			$result['success'] = true;
			$result['message'] = "Recreate Successfully, new order: {$newOrderId}";
		} else {
			$message = $result['message'];
			$result['message'] = "Recreate Failed, result: {$message}";
		}

		$this->utils->debug_log('recreate withdrawal result', $result);
		return $result;
	}

	/**
	 * detail: Respond to Withdrawal declined
	 *
	 * @param int $walletAccountId wallet account id
	 * @param string $showDeclinedReason
	 * @param int $playerpromoId player promo id
	 * @return json
	 */
	public function respondToWithdrawalDeclined($walletAccountId, $showDeclinedReason, $playerpromoId = '', $status = null) {
		$this->load->model(array('wallet_model'));

        $getWithdrawStatusPermissions = $this->getWithdrawStatusPermissions($status);

		if (!$this->permissions->checkPermissions($getWithdrawStatusPermissions)) {
			$this->saveAction(self::ACTION_LOG_TITLE, 'Declined Withdrawal Request Permission denied', "User " . $this->authentication->getUsername() . " permission failed " .json_encode($getWithdrawStatusPermissions) . 'current status:' . $status);
			$this->utils->error_log('permission failed', $getWithdrawStatusPermissions);
			$this->returnText('failed');
			return;
		}

		$notesType = $this->input->get('notesType');
		$this->utils->debug_log('======================== notesType ' . $notesType);
		switch ($notesType) {
			case self::DECLINED_WITHDRAW_REQUEST : //'forRequest':
				$actionlogNotes = 'Set Withdraw To Declined For Request';
				break;

			case self::DECLINED_WITHDRAW_PAID ://'forPaid':
				$actionlogNotes = 'Set Withdraw To Declined For Paid';
				break;

			case self::DECLINED_WITHDRAW_BATCH: //'batchDeclined':
				$actionlogNotes = 'Batch Declined.';
				break;
			default:
				$this->returnJsonResult(['success' => false, 'message'=>lang('Error')]);
				return;
		}

		$succ = false;
        $wallet_account = $this->wallet_model->getWalletAccountObject($walletAccountId);
        if(empty($wallet_account)){
            $this->returnJsonResult(['success' => false]);
            return;
        }

        $playerId = $wallet_account['playerId'];
        $lockedKey=null;
		$lock_it = $this->lockPlayerBalanceResource($playerId, $lockedKey);
		if (!$lock_it) {
			//withdrawl failed
			$this->returnJsonResult(['success' => $succ]);
			return;
		}

		$mainwalletAfterBalance = $mainwalletBeforeBalance = 0;

		//lock success
		try {

			$adminUserId = $this->authentication->getUserId();
			$adminUsername = $this->authentication->getUsername();

			$mainwalletBeforeBalance = $this->wallet_model->getMainWalletTotalNofrozenOnBigWalletByPlayer($playerId);

			if($this->wallet_model->isLockedForManual($walletAccountId, $adminUserId)){
				$result = ['success' => false, 'message'=>lang('this withdrawal has been locked')];

				return $this->returnJsonResult($result);
			}

			$this->startTrans();
			$declinedCategoryId = null;
			if ($this->utils->isEnabledFeature('enable_withdrawal_declined_category') ){
				$declinedCategoryId = $this->input->get('declined_category_id');
				$this->utils->debug_log('declinedCategoryId init ', $declinedCategoryId);
			}

			$succ = $this->wallet_model->declineWithdrawalRequest($adminUserId, $walletAccountId, $actionlogNotes, $showDeclinedReason, $declinedCategoryId);

			$mainwalletAfterBalance = $this->wallet_model->getMainWalletTotalNofrozenOnBigWalletByPlayer($playerId);

			$this->saveAction(self::ACTION_LOG_TITLE, 'Declined Withdrawal Request', "User " . $adminUsername . " has declined deposit/withdrawal request.");

			$succ = $this->endTransWithSucc() && $succ;
		} finally {
			// release it
			$rlt = $this->releasePlayerBalanceResource($playerId, $lockedKey);
		}

		if($succ) {
			$this->wallet_model->userUnlockWithdrawal($walletAccountId);

			# send prompt message when withdrawal order is decline
			if ($this->utils->isEnabledFeature('enable_sms_withdrawal_prompt_action_declined')) {

				$this->load->model(['cms_model', 'queue_result', 'player_model']);
				$this->load->library(["lib_queue", "sms/sms_sender"]);

				$player = $this->player_model->getPlayerInfoDetailById($playerId);
				$mobileNumIsVeridied = $player['verified_phone'];

				if($mobileNumIsVeridied) {
					$isUseQueueToSend    = $this->utils->isEnabledFeature('enabled_send_sms_use_queue_server');
					$dialingCode = $player['dialing_code'];
					$mobileNum = !empty($dialingCode)? $dialingCode.'|'.$player['contactNumber'] : $player['contactNumber'];
					$smsContent = $this->cms_model->getManagerContent(Cms_model::SMS_MSG_WITHDRAWAL_DECLINE);
					$use_new_sms_api_setting = $this->utils->getConfig('use_new_sms_api_setting');
					$useSmsApi = null;
					$sms_setting_msg = '';
					if ($use_new_sms_api_setting) {
					#restrictArea = action type
						$sessionId = $this->session->userdata('session_id');
						$restrictArea = 'sms_api_manager_setting';
						list($useSmsApi, $sms_setting_msg) = $this->utils->getSmsApiNameByNewSetting($playerId, $mobileNum, $restrictArea, $sessionId);
					}

					$this->utils->debug_log(__METHOD__, 'get new sms api',$useSmsApi, $sms_setting_msg);

					if ($isUseQueueToSend) {
						$callerType = Queue_result::CALLER_TYPE_ADMIN;
						$caller = $adminUserId;
						$state  = null;
						$this->lib_queue->addRemoteSMSJob($mobileNum, $smsContent, $callerType, $caller, $state, null);
					} else {
						$this->sms_sender->send($mobileNum, $smsContent, $useSmsApi);
					}
				}
			}

            if($this->utils->getConfig('enable_fast_track_integration')) {
                $this->load->library('fast_track');
                $this->fast_track->declineWithdraw($wallet_account);
            }

			// save seamless balance history
			if($this->utils->getConfig('seamless_main_wallet_reference_enabled')) {
				$this->load->model(['seamless_balance_history']);
				$tableDate = new DateTime();
				$tableName = $this->utils->getSeamlessBalanceHistoryTable($tableDate->format('Y-m-d 00:00:00'));
				$this->seamless_balance_history->setTableName($tableName);
                $this->seamless_balance_history->saveSeamlessBalanceHistoryDeclineWithdrawal($playerId, $mainwalletBeforeBalance, $mainwalletAfterBalance, $walletAccountId);
            }

			if($this->utils->getConfig('enable_recreate_withdrawal_after_declined')){
				$reCreate = $this->safeGetParam('reCreate', false, true);
				if($reCreate){
					$reCreateResult = $this->reCreateWithdrawalAfterDeclined($walletAccountId);
					$this->utils->debug_log('recreate withdrawal result', $reCreateResult);
				}
			}
		}

		$wallet_account = $this->wallet_model->getWalletAccountObject($walletAccountId);
		$last_deposit_amount = $this->CI->transactions->queryAmountByPlayerIdFromLastTransaction($wallet_account['playerId']);
		$is_first_withdrawal = $this->CI->transactions->isOnlyFirsWithdrawal($wallet_account['playerId']);
		$currency = $this->utils->getCurrentCurrency();

		$this->utils->playerTrackingEvent($playerId, 'TRACKINGEVENT_SOURCE_TYPE_WITHDRAWAL_FAILED',
		array(
			'transactionCode' 	=> $wallet_account['transactionCode'],
			'dwDateTime' 		=> $wallet_account['dwDateTime'],
			'amount' 			=> $wallet_account['amount'],
			"Type"				=> "Withdrawal",
			"Status"			=> "Failed",
			"Currency"			=> $currency['currency_code'],
			"TransactionID"	    => $wallet_account['transactionCode'],
			"Channel"			=> $this->CI->external_system->getSystemName($wallet_account['paymentAPI']),
			"TimeTaken" 		=> strtotime($wallet_account['processDatetime']) - strtotime($wallet_account['dwDateTime']),
			"LastDepositAmount" => $last_deposit_amount,
			"FirstWithdrawal"	=> ($is_first_withdrawal) ? "Yes" : "No",
		));

        $this->load->library(['player_notification_library']);
        $this->player_notification_library->danger($wallet_account['playerId'], Player_notification::SOURCE_TYPE_WITHDRAWAL, [
            'player_notify_danger_withdrawal_title',
            $wallet_account['transactionCode'],
            $wallet_account['dwDateTime'],
            $this->utils->displayCurrency($wallet_account['amount']),
            $this->utils->getLiveChatLink(),
            $this->utils->getLiveChatOnClick()
        ], [
            'player_notify_danger_withdrawal_message',
            $wallet_account['transactionCode'],
            $wallet_account['dwDateTime'],
            $this->utils->displayCurrency($wallet_account['amount']),
            $this->utils->getLiveChatLink(),
            $this->utils->getLiveChatOnClick()
        ]);

		$walletAccount=$this->wallet_model->getWalletAccountInfoById($walletAccountId);
		$player=$this->player_model->getPlayerArrayById($playerId);
		$this->processTrackingCallback($playerId, $player, $walletAccount, 'withdrawal');

		$this->returnJsonResult(['success' => $succ]);
	}

	/**
	 *
	 * detail: checking respond withdrawal Permissions
	 *
	 * @param string $status
	 * @return array
	 *
	 */
	public function getWithdrawStatusPermissions($status = null) {
		$permissions = array();
		switch ($status) {
			case Wallet_model::REQUEST_STATUS:
				 $permissions = array('payment_withdrawal_list', 'pass_decline_pending_stage');
				break;
			case Wallet_model::PENDING_REVIEW_STATUS:
				$permissions = array('payment_withdrawal_list', 'pass_decline_pending_review_stage');
				break;
			case Wallet_model::PENDING_REVIEW_CUSTOM_STATUS:
				$permissions = array('payment_withdrawal_list', 'execute_pass_decline_in_pending_custom_stage');
				break;
			case Wallet_model::PAY_PROC_STATUS:
				$permissions = array('payment_withdrawal_list', 'pass_decline_payment_processing_stage');
				break;
			case substr($status,0, 2) == 'CS':
				for($i = 0; $i < CUSTOM_WITHDRAWAL_PROCESSING_STAGES; $i++) {
					if($status == "CS$i") {
						$permissions = array('payment_withdrawal_list', 'pass_decline_withdraw_custom_stage_CS'.$i);
					}
				}
				break;
			default:
				$permissions = array('payment_withdrawal_list');
				break;
		}
		return $permissions;
	}

	/**
	 *
	 * detail: adding notes to a certain transaction
	 *
	 * @param string $transaction
	 * @param int $transactionId transaction id
	 * @return json
	 *
	 */
	public function addDepositNotes($note_type, $saleOrderId) {
		if(isset($saleOrderId)){
			$this->load->model(array('sale_orders_notes','sale_order'));
			$status = $this->sale_order->getStatus($saleOrderId);
			$stage_name = $this->getStageName($status);
			$adminUserId = $this->authentication->getUserId();
			$note = htmlentities($this->input->post('notes'));

			if($note_type == Sale_orders_notes::INTERNAL_NOTE){
				$lastId   = $this->sale_orders_notes->add($note, $adminUserId, $note_type, $saleOrderId, $stage_name);
				$notesArr = $this->sale_orders_notes->getBySaleOrdersNotes($note_type, $saleOrderId);
				$notes    = $this->formatPaymentNotes($notesArr);
			}elseif($note_type == Sale_orders_notes::EXTERNAL_NOTE){
				$lastId   = $this->sale_orders_notes->add($note, $adminUserId, $note_type, $saleOrderId, $stage_name);
				$notesArr = $this->sale_orders_notes->getBySaleOrdersNotes($note_type, $saleOrderId);
				$notes    = $this->formatPaymentNotes($notesArr);
			}else{
				$this->returnJsonResult(array('success'=>false));
			}
			$this->returnJsonResult(array('success' => true, 'notes' => $notes,'notesArr' => $notesArr));
		}else{
			$this->returnJsonResult(array('success'=>false));
		}
	}

	/**
	 *
	 * detail: adding notes to a certain transaction
	 *
	 * @param string $transaction
	 * @param int $transactionId transaction id
	 * @return json
	 *
	 */
	public function addWithdrawalNotes($transaction, $transactionId) {
		if(isset($transactionId)){
			$this->load->model(array('transaction_notes','wallet_model','walletaccount_notes'));
			$adminUserId = $this->authentication->getUserId();
			$note = htmlentities($this->input->post('notes'));
			$note_type = $this->input->post('noteTypes');
			$status = $this->input->post('status');
			$stage_name = $this->getStageName($status);

			// $this->wallet_model->userUnlockWithdrawal($transactionId);		// unlock withdrawal

			#save to walletaccount_notes , $transaction = Withdrawal $transactionId = walletAccountId
			if($note_type == Walletaccount_notes::ACTION_LOG){
				$lastId   = $this->walletaccount_notes->add($note, Users::SUPER_ADMIN_ID, $note_type, $transactionId);
				$notesArr = $this->walletaccount_notes->getWalletAccountNotes($note_type, $transactionId);
				$notes    = $this->formatPaymentNotes($notesArr);
				$this->returnJsonResult(array('success' => true, 'notes' => $notes, 'notesArr' => $notesArr, 'ntype' => $note_type));
			}elseif($note_type == Walletaccount_notes::INTERNAL_NOTE){
				$lastId   = $this->walletaccount_notes->add($note, $adminUserId, $note_type, $transactionId, $stage_name);
				$notesArr = $this->walletaccount_notes->getWalletAccountNotes($note_type, $transactionId);
				$notes    = $this->formatPaymentNotes($notesArr, true);
				$this->returnJsonResult(array('success' => true, 'notes' => $notes, 'notesArr' => $notesArr, 'ntype' => $note_type));
			}elseif($note_type == Walletaccount_notes::EXTERNAL_NOTE){
				$lastId   = $this->walletaccount_notes->add($note, $adminUserId, $note_type, $transactionId, $stage_name);
				$notesArr = $this->walletaccount_notes->getWalletAccountNotes($note_type, $transactionId);
				$notes    = $this->formatPaymentNotes($notesArr, true);
				$this->returnJsonResult(array('success' => true, 'notes' => $notes, 'notesArr' => $notesArr, 'ntype' => $note_type));
			}else{
				$this->returnJsonResult(array('success'=>false));
			}

		}else{
			$this->returnJsonResult(array('success'=>false));
		}
	}

	/**
	 * Get the Stage Name From dwStatus for The reporting.
	 *
	 * @param string $status The field, "walletaccount.dwStatus".
	 * @return string The lang string.
	 */
	public function getStageName($status){
		$this->load->model(array('wallet_model'));
		return $this->wallet_model->getStageName($status);
	}

	public function getWithdrawalDetialNotes($walletAccountId, $note_type){
		if(isset($walletAccountId)){
			$this->load->model(array('wallet_model','walletaccount_notes'));
			$getWallectAccountInfo = $this->wallet_model->getWalletAccountInfoById($walletAccountId);
			$transactionCode = $getWallectAccountInfo['transactionCode'];

			if($note_type == Walletaccount_notes::INTERNAL_NOTE){
				$noteSubTitle = lang('Internal Note');
			}elseif ($note_type == Walletaccount_notes::EXTERNAL_NOTE){
				$noteSubTitle = lang('External Note');
			}
			$allNotes = $this->walletaccount_notes->getWalletAccountNotes($note_type, $walletAccountId);
			$formatNotes = $this->formatPaymentNotes($allNotes, true);

			$this->returnJsonResult(array('success' => true, 'formatNotes' => $formatNotes, 'transactionCode' => $transactionCode, 'noteSubTitle' => $noteSubTitle));
		}else{
			$this->returnJsonResult(array('success'=>false));
		}
	}

	public function getDepositDetialNotes($saleOrderId, $note_type){
		if(isset($saleOrderId)){
			$this->load->model(array('sale_orders_notes','sale_order'));
			$getSaleOrderInfo = $this->sale_order->getSaleOrderById($saleOrderId);
			$secure_id = $getSaleOrderInfo->secure_id;

			if($note_type == Sale_orders_notes::INTERNAL_NOTE){
				$noteSubTitle = lang('Internal Note');
			}elseif ($note_type == Sale_orders_notes::EXTERNAL_NOTE){
				$noteSubTitle = lang('External Note');
			}

			$allNotes = $this->sale_orders_notes->getBySaleOrdersNotes($note_type, $saleOrderId);
			$formatNotes = $this->formatPaymentNotes($allNotes);
			$this->returnJsonResult(array('success' => true, 'formatNotes' => $formatNotes, 'secure_id' => $secure_id, 'noteSubTitle' => $noteSubTitle));
		}else{
			$this->returnJsonResult(array('success'=>false));
		}
	}

	public function checkWithdrawStatus($walletAccountId, $withdrawApi) {
		$result['success'] = false;
		$this->load->model('withdraw_condition');
		$transactionDetail = $this->withdraw_condition->getWithdrawalTransactionDetail($walletAccountId);

		if (count($transactionDetail) < 1) {
			$this->utils->error_log("No transaction detail found for walletAccountId [$walletAccountId]");
			$result['message'] = lang('error.withdrawal_failed');
			$this->returnJsonResult($result);
		}

		# Check status using API function
		list($loaded, $apiClassName) = $this->utils->loadExternalSystemLib($withdrawApi);
		if ($loaded) {
			$this->utils->debug_log('Checking withdraw status for ', $transactionDetail[0]['transactionCode']);
			$paymentResult = $this->$apiClassName->checkWithdrawStatus($transactionDetail[0]['transactionCode']);

			# Definition of $paymentResult can be found in abstract_payment_api
			$result = $paymentResult;
		} else {
			$this->utils->debug_log("Load API [$withdrawApi] for withdrawal failed, payment is not done through API.");
			$result['message'] = lang('error.withdrawal_failed');
		}
		$this->returnJsonResult($result);
	}

    public function checkDepositStatus($saleOrderId , $system_id, $data = null){
		$this->load->model('sale_orders_notes');
	    $deposit_details = $this->get_deposit_detail($saleOrderId, false);
        $secure_id = $deposit_details->secure_id;
        $result['success'] = false;

        # Check status using API function
        list($loaded, $apiClassName) = $this->utils->loadExternalSystemLib($system_id);
        if ($loaded) {
            $this->utils->debug_log('Checking deposit status for ', $system_id);
            if(!is_null($data)){
            	$paymentResult = $this->$apiClassName->checkDepositStatus($secure_id, $data);
            }
            else{
            	$paymentResult = $this->$apiClassName->checkDepositStatus($secure_id);
            }

            # Definition of $paymentResult can be found in abstract_payment_api
            $result = $paymentResult;
            #add notes to sale_orders_notes action log content
            if(isset($result)){
				$this->sale_orders_notes->add($result['message'], Users::SUPER_ADMIN_ID, Sale_orders_notes::ACTION_LOG, $saleOrderId);
			}
        } else {
            $this->utils->debug_log("Load API [$system_id] for deposit failed, payment is not done through API.");
        }

        $this->returnJsonResult($result);
    }

	/**
	 * detail: export adjustment history report
	 *
	 * @return excel format
	 */
	public function exportAdjustmentHistoryToExcel() {
		$data = array(
			'username' => $this->authentication->getUsername(),
			'management' => 'Payment Management',
			'userRole' => $this->rolesfunctions->getRoleByUserId($this->authentication->getUserId())['roleName'],
			'action' => 'Exported Adjustment History',
			'description' => "User " . $this->authentication->getUsername() . " exported adjustment history.",
			'logDate' => date("Y-m-d H:i:s"),
			'status' => '0',
		);
		$this->report_functions->recordAction($data);

		$result = $this->payment_manager->exportAdjustmentHistoryToExcel();
		$this->excel->to_excel($result, 'adjustmenthistorylist-excel');
	}

	/**
	 * detail: View Player Balance Details
	 *
	 * @param int $playerId player id
	 * @return json
	 */
	public function getPlayerBalanceDetails($playerId) {
		echo json_encode($this->payment_manager->getPlayerBalanceDetails($playerId));
	}

	/**
	 * detail: View Player Transaction Log
	 *
	 * @param int $playerId player id
	 * @return json
	 */
	public function getPlayerTransactionLog($playerId) {
		echo json_encode($this->payment_manager->getPlayerTransactionLog($playerId));
	}

	/**
	 * detail: Set Player Balance
	 *
	 * @param int $playerId player id
	 * @param float $currentBalAmount
	 * @param float $newBalAmount
	 * @return	Boolean
	 */
	public function setPlayerNewBalAmount($playerId, $currentBalAmount, $newBalAmount) {
		$player = $this->player_manager->getPlayerById($playerId);

		$playerAcctdata = array('totalBalanceAmount' => $newBalAmount);

		if ($this->payment_manager->setPlayerNewBalAmount($playerId, $playerAcctdata)) {
			echo json_encode($this->addPlayerBalAdjustmentHistory($playerAccountId, $currentBalAmount, $newBalAmount));
		}

		$this->saveAction(self::ACTION_LOG_TITLE, 'Adjust Balance', "User " . $this->authentication->getUsername() . " has adjusted balance of player " . $player['username'] . ".");
	}

	/**
	 * addPlayerBalAdjustmentHistory
	 *
	 * @param int $playerId player id
	 * @param float $currentBalAmount
	 * @param float $newBalAmount
	 * @return string
	 */
	public function addPlayerBalAdjustmentHistory($playerAcctId, $currentBalAmount, $newBalAmount) {
		$playerAcctdata = array('playerAccountId' => $playerAcctId,
			'currentBalanceAmount' => $currentBalAmount,
			'totalBalanceAmount' => $newBalAmount,
			'setOn' => date("Y-m-d H:i:s"),
			'setBy' => $this->authentication->getUserId());

		if ($this->payment_manager->addPlayerBalAdjustmentHistory($playerAcctdata)) {
			return 'success';
		}
	}

	/**
	 * detail: Get Ranking List
	 *
	 * @return json
	 */
	public function getRankingList() {
		echo json_encode($this->payment_manager->getRankingList());
	}

	/**
	 * detail: Get player transaction history
	 *
	 * @return json
	 */
	public function getPlayerTransactionHistory($playerAccountId) {
		echo json_encode($this->payment_manager->getPlayerTransactionHistory($playerAccountId));
	}

	/**
	 * detail: check the withdrawal status after click the withdrawal details button
	 *
	 * @return json
	 */
	public function checkOrderStatus($walletaccountId) {
		if(!empty($walletaccountId)){
			$this->load->model(array('wallet_model'));
			$dwStatus = $this->input->post('status');
			$playerId = $this->input->post('playerId');
			$nowStatus = $this->wallet_model->getWalletAccountStatus($walletaccountId);
			$msg = lang('Withdrawal status has been modified');

			$this->utils->debug_log(__METHOD__, $this->input->post(), $nowStatus, $dwStatus, $msg);
			if ($dwStatus != $nowStatus) {
				$this->returnJsonResult(array('success'=>false, 'message' => $msg));
			}
		}else{
			$this->returnJsonResult(array('success'=>true));
		}
	}

	//--------------------------------------------------------------------------------------------------- bank card management

	/**
	 * detail: Get Ranking Level Setting Details
	 *
	 * @return void
	 */
	public function actionPaymentMethod() {
		$this->form_validation->set_rules('bankName', 'Bank Name', 'trim|required|xss_clean');
		$this->form_validation->set_rules('accountNumber', 'Account Number', 'trim|required|xss_clean');
		$this->form_validation->set_rules('accountName', 'Account Name', 'trim|required|xss_clean');
		$this->form_validation->set_rules('dailyMaxDepositAmount', 'Daily Max Deposit Amount', 'trim|required|xss_clean|numeric');
		$this->form_validation->set_rules('description', 'Description', 'trim|required|xss_clean');
		$this->form_validation->set_rules('processingTime', 'Processing Time', 'trim|required|xss_clean');

		if ($this->form_validation->run() == false) {
			$this->viewBankManagement();
		} else {

			$bankName = $this->input->post('bankName');
			$accountNumber = $this->input->post('accountNumber');
			$accountName = $this->input->post('accountName');
			$dailyMaxDepositAmount = $this->input->post('dailyMaxDepositAmount');
			$description = $this->input->post('description');
			$processingTime = $this->input->post('processingTime');
			$otcPaymentMethodId = $this->input->post('otcPaymentMethodId');
			$today = date("Y-m-d H:i:s");

			if ($otcPaymentMethodId) {

				$data = array(
					'bankName' => $bankName,
					'accountNumber' => $accountNumber,
					'accountName' => $accountName,
					'dailyMaxDepositAmount' => $dailyMaxDepositAmount,
					'description' => $description,
					'processingTime' => $processingTime,
					'updatedOn' => $today,
					'status' => 0,
				);

				$this->payment_manager->editPaymentMethod($data, $otcPaymentMethodId);
				$message = lang('con.pym10') . " <b>" . $bankName . "</b> " . lang('con.pym11');
				$this->saveAction(self::ACTION_LOG_TITLE, 'Edit Payment Method', "User " . $this->authentication->getUsername() . " has edited payment method " . $bankName . ".");
			} else {

				$data = array(
					'bankName' => $bankName,
					'accountNumber' => $accountNumber,
					'accountName' => $accountName,
					'dailyMaxDepositAmount' => $dailyMaxDepositAmount,
					'description' => $description,
					'processingTime' => $processingTime,
					'createdOn' => $today,
					'updatedOn' => $today,
					'status' => 0,
				);

				$this->payment_manager->insertPaymentMethod($data);
				$message = lang('con.pym10') . " <b>" . $bankName . "</b> " . lang('con.pym12');
				$this->saveAction(self::ACTION_LOG_TITLE, 'Add Payment Method', "User " . $this->authentication->getUsername() . " has added new payment method " . $bankName . ".");
			}

			$this->alertMessage(1, $message);
			redirect('payment_management/viewBankManagement');
		}
	}

	/**
	 * detail: Get Ranking Level Setting Details
	 *
	 * @param int $otcPaymentMethodId OTC Payment method
	 * @return void
	 */
	public function changeStatusPaymentMethod($otcPaymentMethodId) {
		$payment_method = $this->payment_manager->getOTCPaymentMethodDetails($otcPaymentMethodId);

		$data = array(
			'status' => $payment_method[0]['status'] == 0 ? '1' : '0',
		);

		$this->payment_manager->changeStatusPaymentMethod($data, $otcPaymentMethodId);

		$status = $payment_method[0]['status'] == 0 ? 'Locked' : 'Normal';

		$message = lang('con.pym10') . " <b>" . $payment_method[0]['bankName'] . "</b>" . lang('con.pym13') . " " . $status . "";
		$this->alertMessage(1, $message);

		$this->saveAction(self::ACTION_LOG_TITLE, 'Lock/Unlock Payment Method', "User " . $this->authentication->getUsername() . " has " . $status . " payment method " . $payment_method[0]['bankName'] . ".");

		redirect('payment_management/viewBankManagement');
	}

	/**
	 * detail: Get Ranking Level Setting Details
	 *
	 * @param int $otcPaymentMethodId OTC Payment method
	 * @return void
	 */
	public function deletePaymentMethod($otcPaymentMethodId) {
		$payment_method = $this->payment_manager->getOTCPaymentMethodDetails($otcPaymentMethodId);

		$this->payment_manager->deletePaymentMethod($otcPaymentMethodId);

		$message = lang('con.pym10') . " <b>" . $payment_method[0]['bankName'] . "</b> " . lang('con.pym14');
		$this->alertMessage(1, $message);

		$this->saveAction(self::ACTION_LOG_TITLE, 'Delete Payment Method', "User " . $this->authentication->getUsername() . " has delete payment method " . $payment_method[0]['bankName'] . ".");

		redirect('payment_management/viewBankManagement');
	}

	//------------------------------------------------------------------------------------------------- player promo

	/**
	 *
	 * detail: withdraw balance from a certain player account
	 *
	 * @param int $player_account_from player account id
	 * @param float $amount
	 * @return void
	 *
	 */
	public function withdrawFromPlayerAccount($player_account_from, $amount) {
		$balance = $this->payment_manager->getBalanceByPlayerAccountId($player_account_from);

		$data = array(
			'totalBalanceAmount' => $balance - $amount,
		);

		$this->payment_manager->setPlayerNewBalAmountByPlayerAccountId($player_account_from, $data);
	}

	/**
	 *
	 * detail: withdraw balance to a certain player account
	 *
	 * @param int $player_account_to player account id
	 * @param float $amount
	 * @return void
	 *
	 */
	public function depositFromPlayerAccount($player_account_to, $amount) {
		$balance = $this->payment_manager->getBalanceByPlayerAccountId($player_account_to);

		$data = array(
			'totalBalanceAmount' => $balance + $amount,
		);

		$this->payment_manager->setPlayerNewBalAmountByPlayerAccountId($player_account_to, $data);
	}

	/**
	 * overview: Previous Balances Checking Setting
	 *
	 * @return load template
	 */
	public function previousBalanceSetting($from = 'system') {
		if (!$this->permissions->checkPermissions('view_previous_balances_checking_setting') &&
            !$this->permissions->checkPermissions('edit_previous_balances_checking_setting')) {
			$this->error_access($from);
		} else {

			if (($this->session->userdata('sidebar_status') == NULL)) {
				$this->session->set_userdata(array('sidebar_status' => 'active'));
			}

			$data['previousBalanceSetting'] = $this->payment_manager->getOperatorGlobalSetting('previous_balance_set_amount');
			$subwalletList = [];
			$apis = $this->utils->getActiveGameSystemList();
			foreach ($apis as $api) {
				$subwalletList[$api['id']] = [
					"id" => $api['id'],
					"label" => $api['system_code'],
					"value" => $data['previousBalanceSetting'][0]['value'],
				];
			}
			$data['clear_withdraw_cond_by_subwallet'] = $subwalletList;
			//save back
			$this->operatorglobalsettings->syncSettingJson('clear_withdraw_cond_by_subwallet', $data['clear_withdraw_cond_by_subwallet']);

            //added permission if user can edit this setting.
            //this will add the disabled attribute to the respective elements so user cannot edit the settings.
            $data['is_disabled'] = $this->permissions->checkPermissions('edit_previous_balances_checking_setting') ? '' : ' disabled';

			$this->loadTemplate(lang('pay.17'), '', '', 'system');
			$this->template->write_view('sidebar', 'system_management/sidebar');
			$this->template->write_view('main_content', 'payment_management/view_previous_balance_setting', $data);
			$this->template->render();
		}
	}

	/**
	 * overview: Save Payment Settings Changes
	 *
	 * @POST $previousBalanceSetAmount
	 * @return	void
	 */
	public function savePreviousBalanceSetting($from = 'system') {
        if (!$this->permissions->checkPermissions('edit_previous_balances_checking_setting')) {
			$this->error_access($from);
		} else {

            $previousBalanceSetAmount = $this->input->post('previousBalanceSetAmount');

            $data['value'] = $previousBalanceSetAmount;
            $data['name'] = 'previous_balance_set_amount';
            $this->payment_manager->setOperatorGlobalSetting($data);

            //save by subwallet
            $subwalletList = [];
            $apis = $this->utils->getActiveGameSystemList();
            foreach ($apis as $api) {
                $name = 'clear_withdraw_condition_' . $api['id'];
                $value = $this->input->post($name);
                if (empty($value)) {
                    $value = $previousBalanceSetAmount;
                }
                $subwalletList[$api['id']] = [
                    "id" => $api['id'],
                    "label" => $api['system_code'],
                    "value" => $value,
                ];
            }
            $this->operatorglobalsettings->syncSettingJson('clear_withdraw_cond_by_subwallet', $subwalletList);

            $this->saveAction(self::ACTION_LOG_TITLE, 'Update Previous Balances Checking Setting', "User " . $this->authentication->getUsername() . " has successfully update previous balance cheking setting.");

            $message = lang('pay.17') . ' ' . lang('con.pym11');
            $this->alertMessage(1, $message);
            redirect('payment_management/previousBalanceSetting');
        }
	}

	/**
	 * overview: Non-promo Withdraw Setting
	 *
	 * detail: display the page for Non-promo Withdraw Setting
	 *
	 * @return load template
	 */
	public function nonPromoWithdrawSetting($from = 'system') {
		if (!$this->permissions->checkPermissions('nonpromo_withdraw_setting')) {
			$this->error_access($from);
		} else {

			if (($this->session->userdata('sidebar_status') == NULL)) {
				$this->session->set_userdata(array('sidebar_status' => 'active'));
			}

			$data['times'] = $this->payment_manager->getOperatorGlobalSetting('non_promo_withdraw_setting');

			$this->loadTemplate(lang('pay.18'), '', '', 'system');
			$this->template->write_view('sidebar', 'system_management/sidebar');
			$this->template->write_view('main_content', 'payment_management/view_nonpromo_withdraw_setting', $data);
			$this->template->render();
		}
	}

	/**
	 * overview: Non-promo Withdraw Setting Changes
	 *
	 * detail: saving data for non promo withdraw setting
	 *
	 * @POST $time
	 * @return	void
	 */
	public function saveNonPromoWithdrawSetting() {
		$times = $this->input->post('times');

		$data['value'] = $times;
		$data['name'] = 'non_promo_withdraw_setting';
		$this->payment_manager->setOperatorGlobalSetting($data);

		$this->saveAction(self::ACTION_LOG_TITLE, 'Update Non-promo Withdraw Setting', "User " . $this->authentication->getUsername() . " has successfully update non-promo withdraw setting.");

		$message = lang('pay.18') . ' ' . lang('con.pym11');
		$this->alertMessage(1, $message);
		redirect('payment_management/nonPromoWithdrawSetting');
	}

	/**
	 *
	 * @param int $bankAccountId
	 * @return void
	 */
	function calculateBankDeposit($bankAccountId) {
		$this->payment_manager->calculateBankDeposit($bankAccountId);
	}

	public function refreshBalanceInfo($player_id) {
		$this->player_manager->updateBalances($player_id);
		$data['walletAccount'] = $this->payment->getPlayerCurrentWalletBalance($player_id);
		$this->load->view('payment_management/ajax_player_balance_adjustment', $data);
	}


	public function viewBalanceTransactionList($from = 'report') {
		if (!$this->permissions->checkPermissions('report_balance_transactions')) {
			return $this->error_access($from);
		}
		$this->load->model(array('payment_account', 'affiliatemodel','common_category','player'));

		$transaction_id = $this->input->get('transaction_id');
		$data['transaction_id'] = $transaction_id;
		$data['promo_category_list'] = [];
		// $data['promo_category_list'] = $this->promorules->getPromoType();
		if ($this->utils->isEnabledFeature('enable_adjustment_category') && false){
			$data['adjustment_category_list'] = $this->common_category->getActiveCategoryByType(common_category::CATEGORY_ADJUSTMENT);
		}

		// disable for hidden "Collection Acc. (Holder's) Name".
		// $data['payment_account_list'] = $this->payment_account->getAllPaymentAccountDetails();


		$only_master = false;
		$ordered_by_name = true;
		$data['affiliates'] = $this->affiliatemodel->getAllActivtedAffiliates($only_master, $ordered_by_name);
		$data['form']	= &$form;
		$data['tag_list'] = $this->player->getAllTagsOnly();
		if ($from == 'report') {
			$activenav = 'report';
		} else {
			$activenav = 'payment';
		}

		if ($this->permissions->checkPermissions('friend_referral_player') && false) {

			$data['referrer'] = $this->input->get('referrer');

		}


		$this->loadTemplate(lang('pay.balance_transactions'), '', '', $activenav);

		$this->template->add_js('resources/js/select2.min.js');
		$this->template->add_js('resources/js/highlight.pack.js');
		$this->template->add_css('resources/css/hljs.tomorrow.css');
		$this->template->add_js('resources/js/chosen.jquery.min.js');
		$this->template->add_css('resources/css/chosen.min.css');
		$this->template->add_css('resources/css/select2.min.css');

		if ($from == 'report') {
			$this->template->write_view('sidebar', 'report_management/sidebar', ['active' => 'view_balance_transaction']);
		} else {
			$this->template->write_view('sidebar', 'payment_management/sidebar', ['active' => 'view_balance_transaction']);
		}

		$data['from'] = $from;

		$this->template->write_view('main_content', 'payment_management/view_balance_transaction_list', $data);
		$this->template->render();
	} // EOF viewBalanceTransactionList

	/**
	 * detail: view transaction list
	 *
	 * @param string $from
	 * @return  rendered UI
	 */
	public function viewTransactionList($from = 'payment') {
		if (!$this->permissions->checkPermissions('report_transactions')) {
			return $this->error_access($from);
		}

		$this->load->model(array('payment_account', 'affiliatemodel','common_category','player','group_level'));

		$transaction_id = $this->input->get('transaction_id');
		$data['transaction_id'] = $transaction_id;
		$data['promo_category_list'] = $this->promorules->getPromoType();
        $data['promo_rules_list'] = $this->promorules->getPromoRulesListOrderByPromoNameAsc();

        if ($this->utils->getConfig('enabled_viplevel_filter_in_transactions')) {
			$data['levels'] = $this->group_level->getAllPlayerLevelsDropdown(false);
        }

		if ($this->utils->isEnabledFeature('enable_adjustment_category')){
			$data['adjustment_category_list'] = $this->common_category->getActiveCategoryByType(common_category::CATEGORY_ADJUSTMENT);
		}

		$data['payment_account_list'] = $this->payment_account->getAllPaymentAccountDetails();

		$only_master = false;
		$ordered_by_name = true;
		$data['affiliates'] = $this->affiliatemodel->getAllActivtedAffiliates($only_master, $ordered_by_name);
		$data['form']	= &$form;
		$data['tag_list'] = $this->player->getAllTagsOnly();
		if ($from == 'report') {
			$activenav = 'report';
		} else {
			$activenav = 'payment';
		}

		if ($this->permissions->checkPermissions('friend_referral_player')) {

			$data['referrer'] = $this->input->get('referrer');

		}

		$data['enable_freeze_top_in_list'] = $this->utils->_getEnableFreezeTopWithMethod(__METHOD__, $this->config->item('enable_freeze_top_method_list'));

		$this->loadTemplate(lang('pay.transactions'), '', '', $activenav);

		$this->template->add_js('resources/js/select2.min.js');
		$this->template->add_js('resources/js/highlight.pack.js');
		$this->template->add_css('resources/css/hljs.tomorrow.css');
		$this->template->add_js('resources/js/chosen.jquery.min.js');
		$this->template->add_css('resources/css/chosen.min.css');
		$this->template->add_css('resources/css/select2.min.css');
		$this->template->add_css('resources/third_party/bootstrap-multiselect-master/dist/css/bootstrap-multiselect.css');
        $this->template->add_js('resources/third_party/bootstrap-multiselect-master/dist/js/bootstrap-multiselect.js');

		if ($from == 'report') {
			$this->template->write_view('sidebar', 'report_management/sidebar', ['active' => 'view_transaction']);
		} else {
			$this->template->write_view('sidebar', 'payment_management/sidebar', ['active' => 'view_transaction']);
		}

		$data['from'] = $from;

		$this->template->write_view('main_content', 'payment_management/view_transaction_list', $data);
		$this->template->render();
	}

	/**
	 * AJAX
	 * Check if username exist
	 *
	 * @return json
	 */
	public function checkUsernames() {

		$this->load->model('affiliate');
		$this->load->model('users');
		$this->load->model('player');
		$this->load->helper('security');

		$username = xss_clean($this->input->post('username'));
		$userGroup = xss_clean($this->input->post('userGroup'));
		$data = '';
		switch ($userGroup) {
		case "affiliates":
			$data = $this->affiliate->checkUsernameIfExist($username);
			break;
		case "adminusers":
			$data = $this->users->checkUsernameIfExist($username);
			break;
		case "player":
			$data = $this->player->checkUsernameIfExist($username);
			break;
		}

		if ($data['isExist']) {
			$arr = array('status' => 'success', 'msg' => '<b>' . $username . '</b>  ' . lang('player.uab09'), 'userdata' => $data);
			echo json_encode($arr);
		} else {
			$data['username'] = $username;
			$arr = array('status' => 'notfound', 'msg' => '<b>' . $username . '</b>  ' . lang('player.uab10'), 'userdata' => $data);
			echo json_encode($arr);
		}
	}

	/**
	 * detail: view transaction list
	 *
	 * @return  rendered UI
	 */
	public function view_adjustment_transaction_list() {
		if (!$this->permissions->checkPermissions('report_transactions')) {
			$this->error_access();
		} else {
			$this->loadTemplate(lang('role.72'), '', '', 'payment');
			// $this->template->add_css('resources/css/general/fontawesome/font-awesome.css');
			$this->template->add_css('resources/css/general/fontawesome/build.css');
			$this->template->write_view('sidebar', 'payment_management/sidebar');
			$this->template->write_view('main_content', 'payment_management/view_adjustment_transaction_list');
			$this->template->render();
		}
	}

	# BANK TYPES ###########################################################################################################################

	/**
	 *
	 * overview: Bank type payment lists
	 *
	 * detail: List of all payment banks
	 *
	 * @return load template
	 *
	 */
	public function bank3rdPaymentList($from = 'system') {
		if (!$this->permissions->checkPermissions('bank/3rd_payment_list')) {
			$this->error_access($from);
		} else {
			$this->load->model(array('banktype', 'users', 'payment_account', 'financial_account_setting'));

			$data['bankTypes'] = $this->banktype->getAllBanktype();
			foreach ($data['bankTypes'] as &$bankType) {
				$bankType['bankName'] = lang($bankType['bankName']);
			}
			$data['payment_type_flags'] = $this->utils->insertEmptyToHeader($this->financial_account_setting->getPaymentTypeAllFlagsKV(), '', lang('select.empty.line'));

			$this->loadTemplate(lang('pay.bt.paneltitle'), '', '', 'system');
			$this->template->write_view('sidebar', 'system_management/sidebar');
			$this->template->write_view('main_content', 'payment_management/view_bank_type_list', $data);
			$this->template->render();
		}
	}

	/**
	 *
	 * detail: adding new bank type form
	 *
	 * @return load template
	 */
	public function newBankType( $playerBankDetailsId = '' ) {
		$this->load->model(['banktype', 'financial_account_setting']);

		if ($bankName = $this->input->post('bankname')) {
			$bankName = str_replace("\t", '', $bankName);
			if( ! is_array($bankName) ){
				$bankName = array(
					"1" => $bankName,
					"2" => $bankName,
					"3" => $bankName,
					"4" => $bankName,
					"5" => $bankName
				);
			}

			$bankName = '_json:' . json_encode($bankName);

			$bankCode=$this->input->post('bank_code');
			if(empty($bankCode)){
				$bankCode=null;
			}

			$icon_file_name = null;

			if (!$this->input->post('chkUseDefaultIcon')) {
				if (!empty($_FILES['filBankIcon'])) {
					# upload bank icon
					$upload_response = $this->uploadBankIcon();

					if ($upload_response['status'] == 'success' && isset($upload_response['fileName'])) {
						if (!empty($upload_response['fileName'])) {
							$icon_file_name = $upload_response['fileName'];
						}
					}
				}
			}

			$isBankCodeDuplicate = $this->banktype->checkIfBankCodeExist($bankCode, 'new_id');
			if($isBankCodeDuplicate) {
				$this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('pay.bank_code.duplicate'));
				redirect('payment_management/newBankType');
				return;
			}

			$banktypeId = $this->banktype->addBankType(
				$bankName,
				$this->authentication->getUserId(),
				$this->input->post('external_system_id'),
				$bankCode,
				$this->input->post('payment_type_flag'),
				$icon_file_name,
				'active',
				$this->input->post('bank_order')
			);

			$this->load->model('playerbankdetails');
			if( ! empty( $playerBankDetailsId ) ) $this->playerbankdetails->updateBanktypeId($playerBankDetailsId, $banktypeId);

			if( $this->input->is_ajax_request() ){
				$this->returnJsonResult(array('status' => 'success', 'msg' => $banktypeId));
				return;
			}

			$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang("pay.bank_code.saved"));
			redirect('payment_management/bank3rdPaymentList');
		} else {
			$data['payment_type_flags'] = $this->utils->insertEmptyToHeader($this->financial_account_setting->getPaymentTypeAllFlagsKV(), '', lang('select.empty.line'));
			$this->loadTemplate(lang('pay.bt.paneltitle'), '', '', 'system');
			$this->template->write_view('sidebar', 'system_management/sidebar');
			$this->template->write_view('main_content', 'payment_management/view_bank_type_form', $data);
			$this->template->render();
		}
	}

	/**
	 * detail: update bank type form
	 *
	 * @param int $bankTypeId bank type id
	 * @return load template
	 *
	 */
	public function editBankType($bankTypeId) {
		$this->load->model(['banktype', 'financial_account_setting']);
		if ($bankName = $this->input->post('bankname')) {

			if(!isset($bankName[1]) || empty($bankName[1]) || trim($bankName[1]) == '')
				return redirect('payment_management/bank3rdPaymentList');

			foreach ($bankName as $key => $name) {
				$bankName[$key] = str_replace("\t", '', $name);
				if($key == '1') continue;

				if(empty($name) || trim($name) == '')
					$bankName[$key] = $bankName[1];//Use english language by default
			}

			$bankName = '_json:' . json_encode($bankName);

			$bankCode=$this->input->post('bank_code');
			if(empty($bankCode)){
				$bankCode=null;
			}

			$data = array(
				'bankName' => $bankName,
				'external_system_id' => $this->input->post('external_system_id'),
				'bank_code' => $bankCode,
				'payment_type_flag' => $this->input->post('payment_type_flag'),
				'bank_order' => $this->input->post('bank_order')
			);

			$icon_file_name = null;
			if (!$this->input->post('chkUseDefaultIcon')) {
				if (!empty($_FILES['filBankIcon'])) {
					if (!empty($_FILES['filBankIcon']['name'][0])) {
						# upload bank icon
						$upload_response = $this->uploadBankIcon();

						if ($upload_response['status'] == 'success' && isset($upload_response['fileName'])) {
							if (!empty($upload_response['fileName'])) {
								$icon_file_name = $upload_response['fileName'];
								$data['bankIcon'] = $icon_file_name;
							}
						}
					}
				}
			}else{
				$data['bankIcon'] = $icon_file_name;
			}

			$isBankCodeDuplicate = $this->banktype->checkIfBankCodeExist($bankCode, $this->input->post('bankTypeId'));
			if($isBankCodeDuplicate) {
				$this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('pay.bank_code.duplicate'));
				redirect('payment_management/editBankType/'.$this->input->post('bankTypeId'));
				return;
			}

			$this->banktype->updateBankType(
				$this->input->post('bankTypeId'),
				$data
			);

			$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang("pay.bank_code.saved"));
			redirect('payment_management/bank3rdPaymentList');
		} else {
			$bankTypeRow = $this->banktype->getBankTypeById($bankTypeId);
			$data['payment_type_flags'] = $this->utils->insertEmptyToHeader($this->financial_account_setting->getPaymentTypeAllFlagsKV(), '', lang('select.empty.line'));

			$data['bankTypeId'] = $bankTypeRow->bankTypeId;
			$data['bankName'] = $bankTypeRow->bankName;
			$data['external_system_id'] = $bankTypeRow->external_system_id;
			$data['bank_code'] = $bankTypeRow->bank_code;
			$data['payment_type_flag'] = $bankTypeRow->payment_type_flag;
			$data['bank_icon'] = $bankTypeRow->bankIcon;
			$data['bank_order'] = $bankTypeRow->bank_order;

			$this->loadTemplate(lang('pay.bt.paneltitle'), '', '', 'system');
			$this->template->write_view('sidebar', 'system_management/sidebar');
			$this->template->write_view('main_content', 'payment_management/view_bank_type_form', $data);
			$this->template->render();
		}
	}

	/**
	 *
	 * detail: delete bank type
	 *
	 * @param int $bankTypeId bank type id
	 * @return void
	 */
	// public function deleteBankType($bankTypeId) {
	// 	$this->alertMessage(2, lang('pay.bt.error.occured'));
	// 	redirect('payment_management/bank3rdPaymentList');
	// }

	/**
	 *
	 * overview: Hidden Bank type payment lists
	 *
	 * detail: List of all hidden payment banks
	 *
	 * @return load template
	 *
	 */
	public function hiddenBank3rdPaymentList(){

		if(!$this->permissions->checkPermissions('hidden_banktype_list')) {
			$this->error_access('system');
		}

		$this->loadTemplate(lang('Hidden Banktype List'), '', '', 'system');
		$this->template->write_view('sidebar', 'system_management/sidebar');
		$this->template->write_view('main_content', 'payment_management/view_hidden_banktype');
		$this->template->render();

	}

	/**
	 *
	 * detail: show bank type
	 *
	 * @param int $bankTypeId bank type id
	 * @return json
	 */
	public function showBankType($bankTypeId) {

		$this->load->model(['banktype']);

		$param = array(
				'is_hidden' => 0,
			);
		$message = 'BankType '. lang('con.vsm05');
		$arr = array('status' => 'success' ,'msg' => $message);

		if(!$this->banktype->updateBankType($bankTypeId,$param) === true){
			$message = lang('notify.61');
			$arr = array('status' => 'failed','msg' => $message);
		}
		$this->returnJsonResult($arr);

	}

	/**
	 *
	 * detail: hide bank type
	 *
	 * @param int $bankTypeId bank type id
	 * @return void
	 */
	public function hideBankType($bankTypeId) {

		$this->load->model(['banktype']);

		$param = array(
				'is_hidden' => 1,
			);
		if($this->banktype->updateBankType($bankTypeId,$param) === true){
			$message = 'BankType '. lang('con.vsm05');
			$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, $message);
		}else{
			$message = lang('notify.61');
			$this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);
		}

		redirect('payment_management/bank3rdPaymentList');

	}
	/**
	 *
	 * detail: soft delete bank type
	 *
	 * @param int $bankTypeId bank type id
	 * @return void
	 */
	public function deleteBankType($bankTypeId) {

		$this->load->model(['playerbankdetails','banktype']);
		$playerBankDetails = $this->playerbankdetails->getNotDeletedBankDetailsByBankTypeId($bankTypeId);
	
		if (!empty($playerBankDetails)) {
			$message = lang('notify.135');
			$this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);
		} else {
			$param = array(
					'deleted_at' => $this->utils->getNowForMysql(),
					'status' => Banktype::STATUS_DELETE
				);
			
			if($this->banktype->updateBankType($bankTypeId,$param) === true){
				$message = 'BankType '. lang('con.vsm05');
				$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, $message);
			}else{
				$message = lang('notify.61');
				$this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);
			}
		}

		redirect('payment_management/bank3rdPaymentList');
	}

	/**
	 * detail: update the status of the bank type
	 *
	 * @param int $bankTypeId bank type id
	 * @param string $status
	 * @return void
	 */
	public function toggleBanktypeStatus($bankTypeId, $status) {

		$this->load->model(array('banktype', 'payment_account'));

		$payment_account_id = $this->payment_account->getPaymentAccountInfo( $bankTypeId );

		if ($this->banktype->updateBanktype($bankTypeId, array(
			'status' => $status,
		))) {

			//change the status of the collection account for this bank
			if( ! empty($payment_account_id) && urldecode($status) == 'not active' && $payment_account_id->status != BaseModel::STATUS_DISABLED ){
				$this->payment_account->disablePaymentAccount( $payment_account_id->id );
			}elseif( ! empty($payment_account_id) && urldecode($status) == 'active' ){
				$this->payment_account->enablePaymentAccount( $payment_account_id->id );
			}
			//end

			$this->alertMessage(1, lang('pay.bt.successfully.saved'));
		} else {
			$this->alertMessage(2, lang('pay.bt.error.occured'));
		}

		redirect('payment_management/bank3rdPaymentList');
	}

	/**
	 *
	 * @param string $type
	 * @param int $bankTypeId bank type id
	 * @param string $status
	 * @return void
	 */
	public function toggleBankType($type, $bankTypeId, $status) {

		$this->load->model('banktype');

		if ($this->banktype->updateBanktype($bankTypeId, array(
			$type => $status,
		))) {
			$this->alertMessage(1, lang('pay.bt.successfully.saved'));
		} else {
			$this->alertMessage(2, lang('pay.bt.error.occured'));
		}

		redirect('payment_management/bank3rdPaymentList');
	}

	# END BANK TYPES ###########################################################################################################################

	# ADJUST BALANCE ###########################################################################################################################

	/**
	 *
	 * detail: display list of all the member balances
	 *
	 * @return load template
	 */
	public function member_balance() {

		$this->load->model('external_system');

		if ($this->permissions->checkPermissions('payment_player_adjustbalance')) {
			$this->loadTemplate(lang('role.72'), '', '', 'payment');

			if (($this->session->userdata('sidebar_status') == NULL)) {
				$this->session->set_userdata(array('sidebar_status' => 'active'));
			}

			$itemCnt = 0;
			$sort_by = array();
			$sort_by['transactionType'] = 'deposit';
			$sort_by['dwStatus'] = 'request';

			$this->session->set_userdata('dwStatus', $sort_by['dwStatus']);
			if ($this->session->userdata('itemCnt') != null) {
				$itemCnt = $this->session->userdata('itemCnt');
			} else {
				$itemCnt = 10;
			}

			if ($this->session->userdata('playerLevel')) {
				$sort_by['searchVal'] = $this->session->userdata('searchVal');
			}
			if ($this->session->userdata('currency')) {
				$sort_by['currency'] = $this->session->userdata('currency');
			}
			if ($this->session->userdata('orderBy')) {
				$sort_by['orderBy'] = $this->session->userdata('orderBy');
			}
			if ($this->session->userdata('playerLevel')) {
				$sort_by['playerLevel'] = $this->session->userdata('playerLevel');
			}
			if ($this->session->userdata('paymentMethod')) {
				$sort_by['paymentMethod'] = $this->session->userdata('paymentMethod');
			}

			$data['game_platforms'] = $this->external_system->getAllActiveSytemGameApi();
			$data['playerDetails'] = $this->player_model->getPlayersSubWalletBalance(null, $data['game_platforms']);
			$data['transactionType'] = lang('con.pb');
			$data['games'] = $this->payment_manager->getGames();
			$this->template->write_view('sidebar', 'payment_management/sidebar');
			$this->template->write_view('main_content', 'payment_management/balance_adjustment/member_balance', $data);
			$this->template->render();
		} else {
			$this->error_access();
		}
	}

	/**
	 * overview: Adjustment history
	 *
	 * detail: display the balance history
	 *
	 * @param int $player_id player id
	 * @return load template
	 */
	public function adjustment_history($player_id = null) {

		$this->load->model('external_system');

		if ($this->permissions->checkPermissions('report_adjustment_history')) {
			$start_date = $this->input->get('start_date');
			$end_date = $this->input->get('end_date');
			$limit = $this->input->get('items_per_page');
			$game_platforms = $this->external_system->getAllActiveSytemGameApi();
			$data['player_id'] = $player_id;
			$data['game_platforms'][] = array(
				'system_code' => 'Main',
			);
			foreach ($game_platforms as $game_platform) {
				$data['game_platforms'][$game_platform['id']] = $game_platform;
			}

			$data['adjustment_history'] = $this->payment->viewAdjustmentHistoryV2($player_id, $start_date, $end_date, $limit);
            $currency = $this->utils->getCurrentCurrency();
            $data['currency_decimals'] = $currency['currency_decimals'];

			$this->loadTemplate(lang('role.72'), '', '', 'payment');
			$this->template->write_view('sidebar', 'payment_management/sidebar');
			$this->template->write_view('main_content', 'payment_management/balance_adjustment/adjustment_history', $data);
			$this->template->render();
		}
	}

	/**
	 * overview: balance adjustment
	 *
	 * detail: display the balance adjustment
	 *
	 * @param int $player_id player id
	 * @return load template
	 */
	public function adjust_balance($playerId) {
		if (!$this->permissions->checkPermissions('payment_player_adjustbalance')) {
			$this->error_access();
		} else {
			$this->load->model(array('external_system', 'game_provider_auth', 'player_model', 'payment', 'point_transactions'));

			$data['playerDetails'] = $this->player_model->getPlayerDetails($playerId);
			$data['game_platforms'] = $this->game_provider_auth->getGamePlatforms($playerId);
			$data['walletAccounts'] = $this->player_model->getPlayersSubWalletBalance($playerId);
			$data['player_id'] = $playerId;
            $currency = $this->utils->getCurrentCurrency();
            $data['currency_decimals'] = $currency['currency_decimals'];

			$bigWallet = $this->utils->getBigWalletByPlayerId($playerId);
			$data['walletAccounts']['total'] = $bigWallet['total_nofrozen'];
			$game_platforms = $data['game_platforms'];
			if(!empty($game_platforms)){
				foreach ($game_platforms as $key => $value) {
					$api = $this->utils->loadExternalSystemLibObject($value['id']);
                    if(empty($api) || ($this->utils->getConfig('seamless_main_wallet_reference_enabled') && $api->isSeamLessGame())) {
                        unset($game_platforms[$key]);
                        unset($data['game_platforms'][$key]);
                        continue;
                    }
					$data['game_platforms'][$key]['is_seamless'] = $api->isSeamLessGame();
					$data['game_platforms'][$key]['total_nofrozen_balance'] = isset($bigWallet['sub'][$value['id']]['total_nofrozen']) ? $bigWallet['sub'][$value['id']]['total_nofrozen'] : 0;
				}

			}

			//OGP-19415
			$data['walletAccounts']['points'] = $this->point_transactions->pointTotal($playerId);

			$this->loadTemplate(lang('Adjust Balance'), '', '', 'payment');
			$this->template->write_view('sidebar', 'payment_management/sidebar');
			$this->template->write_view('main_content', 'payment_management/balance_adjustment/adjust_balance', $data);
			$this->template->render();
		}
	}

	/**
	 *
	 * @param int $player_id player id
	 * @return load template
	 */
	public function add_withdraw_condition($player_id = null) {
		$this->load->model(array('promorules', 'external_system'));
		$data = array(
			'platform_id' => '0',
			'platform_name' => lang('pay.mainwallt'),
			'player_id' => $player_id,
		);

		$data['promoCmsSettings'] = $this->promorules->getAvailablePromoCMSList();
		$userId=$this->authentication->getUserId();
		$data['double_submit_hidden_field']=$this->initDoubleSubmitAndReturnHiddenFieldForAdmin($userId);

		$this->session->set_flashdata('prevent_refresh', true);

		$this->load->view('payment_management/balance_adjustment/add_withdraw_condition_form', $data);
	}

	/**
	 *
	 * detail: add withdraw condition for a certain player
	 *
	 * @param int $player_id player id
	 * @return void
	 */
	public function add_withdraw_condition_post($player_id) {
		$this->load->model(array('transactions', 'wallet_model', 'withdraw_condition', 'player_promo', 'promorules'));

		$controller = $this;
		$adminId = $this->authentication->getUserId();

		if(!$this->verifyAndResetDoubleSubmitForAdmin($adminId)){
			$message = lang('Please refresh and try, and donot allow double submit');
			$this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);
			redirect('payment_management/adjust_balance/' . $player_id);
			return;
		}

		$success = $this->lockAndTrans(Utils::LOCK_ACTION_WITHDRAW_CONDITION, $player_id, function ()
			use ($controller, $player_id, $adminId) {

				# GET REQUIRED PARAMETERS FROM REQUEST
				$amount                 = $controller->input->post('amount');
				$depositAmtCondition    = $controller->input->post('depositAmtCondition');
				$betTimes               = $controller->input->post('betTimes');
				$reason                 = $controller->input->post('reason');
				$show_in_front_end      = $controller->input->post('show_in_front_end');
				$promoCmsSettingId      = $controller->input->post('promoCmsSettingId');
				$adjustWithdrawCondType = $controller->input->post('adjustWithdrawCondType');
				$make_up_date           = $controller->input->post('make_up_date');

				if ($promoCmsSettingId) {
					list($promorule, $promoCmsSettingId) = $controller->promorules->getByCmsPromoCodeOrId($promoCmsSettingId);
					$promoRuleId = $promorule['promorulesId'];

					$controller->utils->debug_log('promoRuleId', $promoRuleId, 'show_in_front_end', $show_in_front_end, 'amount', $amount, 'reason', $reason);
				}

				$success = true;
				$isDeposit = !empty($depositAmtCondition);
				$bonusTransId = 0;
				$depositTransId = 0;
				$adjustWithdrawCondType = $controller->input->post('adjustWithdrawCondType');
				if ($adjustWithdrawCondType == Payment_management::WITHDRAW_CONDITION_ONLY_DEPOSIT) {
					//Only Calculate Deposit
					$withdrawBetAmtCondition = doubleval($depositAmtCondition) * doubleval($betTimes);
				} elseif ($adjustWithdrawCondType == Payment_management::WITHDRAW_CONDITION_DEPOSIT_BONUS_BET_TIMES_DEDUCT_DEPOSIT) {
					//deduct deposit amount
					$withdrawBetAmtCondition = (doubleval($depositAmtCondition) + doubleval($amount)) * doubleval($betTimes) - doubleval($depositAmtCondition);
				} elseif ($adjustWithdrawCondType == Payment_management::WITHDRAW_CONDITION_NONE) {
					$withdrawBetAmtCondition = doubleval($amount) * doubleval($betTimes);
				} else {
					//normal
					$withdrawBetAmtCondition = (doubleval($depositAmtCondition) + doubleval($amount)) * doubleval($betTimes);
				}
				// $withdrawBetAmtCondition=;

				$this->utils->debug_log('adjust withdraw_conditions player_id', $player_id, 'withdrawBetAmtCondition', $withdrawBetAmtCondition);

				if ($promoCmsSettingId) {
					//make playerpromo
					$playerpromoId = null;
					$playerpromoId = $controller->player_promo->approvePromoToPlayerWithouRelease($player_id, $promoRuleId, $amount,
							$promoCmsSettingId, $adminId, $playerpromoId, $withdrawBetAmtCondition);

					//make withdraw condition
					$controller->withdraw_condition->createWithdrawConditionForPromoruleBonus($isDeposit, $player_id, $bonusTransId,
							$withdrawBetAmtCondition, $depositAmtCondition, $amount, $betTimes, $promorule,
							$depositTransId, $playerpromoId, $make_up_date, $reason);
				} else {
					// cashback withdraw condition (no promo)
					if ($adjustWithdrawCondType == Payment_management::WITHDRAW_CONDITION_ONLY_DEPOSIT) {
						$controller->withdraw_condition->createWithdrawCondForDepositOnly($bonusTransId, $withdrawBetAmtCondition, $betTimes, $player_id, $depositAmtCondition, $reason);
					}

					if ($adjustWithdrawCondType == Payment_management::WITHDRAW_CONDITION_NONE) {
						$controller->withdraw_condition->createWithdrawConditionForCashback($bonusTransId, $withdrawBetAmtCondition, $betTimes, $player_id, $amount, $reason);
					}
				}

				return $success;
			}
		);

		$rlt = array('success' => $success);

		if (!$rlt['success']) {
			if (isset($rlt['message']) && !empty($rlt['message'])) {
				$message = $rlt['message'];
			} else {
				$message = lang('notify.61');
			}
			$this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);
		} else {
			$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang('con.pym09'));
		}

		redirect('payment_management/adjust_balance/' . $player_id);
	}

	public function adjust_seamless_balance_form($platform_id, $transaction_type, $player_id){
		if (!$this->permissions->checkPermissions('adjust_manually_seamless_wallet')) {
			$this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('con.pym01'));
			redirect('payment_management/adjust_balance/' . $player_id);
			return;
		}
		$this->load->model(array('promorules', 'external_system', 'common_category'));
		$platform_name = $platform_id == '0' ? lang('pay.mainwallt') : $this->external_system->getNameById($platform_id) . ' ' . lang('cashier.42');
		$data = array(
			'platform_id' => $platform_id,
			'platform_name' => $platform_name,
			'transaction_type' => $transaction_type,
			'player_id' => $player_id,
		);

		$userId=$this->authentication->getUserId();
		$data['double_submit_hidden_field']=$this->initDoubleSubmitAndReturnHiddenFieldForAdmin($userId);
		$this->session->set_flashdata('prevent_refresh', true);
		$this->load->view('payment_management/balance_adjustment/adjust_seamless_balance_form', $data);
	}

	/**
	 *
	 * detail: view adjust balance form
	 *
	 * @param int $platform_id platform_id Zero means Main wallet, the others have not used.
	 * @param string $transation_type The enumerated values,  Transactions::ADD_BONUS, Transactions::AUTO_ADD_CASHBACK_TO_BALANCE, Transactions::SUBTRACT_BONUS and Transactions::MANUAL_SUBTRACT_BALANCE
	 * @param int $player_id player id
	 * @return load template
	 */
	public function adjust_balance_form($platform_id, $transaction_type, $player_id = null, $is_own_page = 'false') {
        $this->load->model(array('transactions','promorules', 'external_system', 'common_category'));

		$platform_name = $platform_id == '0' ? lang('pay.mainwallt') : $this->external_system->getNameById($platform_id) . ' ' . lang('cashier.42');
		$data = array(
			'platform_id' => $platform_id,
			'platform_name' => $platform_name,
			'transaction_type' => $transaction_type,
			'player_id' => $player_id,
		);
		$data['promoCategory'] = $this->promorules->getAllPromoCategory();
		$data['promoRules'] = $this->promorules->getAvailablePromoruleList();
		$data['manual_subtract_balance_tags'] = $this->player_manager->getAllManualSubtractBalanceTags();
		if($this->utils->isEnabledFeature('enable_adjustment_category')){
			$data['adjustmentCategory'] = $this->common_category->getActiveCategoryByType(common_category::CATEGORY_ADJUSTMENT);
		}

		if($this->utils->isEnabledFeature('only_manually_add_active_promotion')){
			$data['promoCms'] = $this->promorules->getAvailablePromoCMSList();
		}else{
			$data['promoCms'] = $this->promorules->getAllPromoCMSList();
		}

		$userId=$this->authentication->getUserId();
		$data['double_submit_hidden_field']=$this->initDoubleSubmitAndReturnHiddenFieldForAdmin($userId);

		$this->session->set_userdata('prevent_refresh', true);
		//move to marketing_management

		$currency = $this->utils->getCurrentCurrency();
		$data['currency_decimals'] = $currency['currency_decimals'];
		$data['min_decimals'] = round(1/pow(10, $currency['currency_decimals']), $currency['currency_decimals']);

        $data['is_own_page'] = $is_own_page ;
        $data['amount'] = "";
		$data['manual_subtract_all_balance'] = 0;
		$data['disabled_input_amount'] = "";
        if ($transaction_type == Transactions::MANUAL_SUBTRACT_BALANCE) {
			$bigWallet = $this->utils->getBigWalletByPlayerId($player_id);
			$data['manual_subtract_all_balance'] = 0;
            $data['player_total_balance'] = $bigWallet['total_nofrozen']; 
        }

		$this->load->view('payment_management/balance_adjustment/adjust_balance_form', $data);
	}
    /**
     * The form of adjust balance with amounts
     *
	 * @param int $platform_id platform_id Zero means Main wallet, the others have not used.
	 * @param string $transation_type The enumerated values,  Transactions::ADD_BONUS, Transactions::AUTO_ADD_CASHBACK_TO_BALANCE, Transactions::SUBTRACT_BONUS and Transactions::MANUAL_SUBTRACT_BALANCE
     * @param string $is_own_page
     * @return void
     */
    public function adjust_amounts_balance_form($platform_id, $transaction_type, $is_own_page = 'false') {

        $this->load->model(array('promorules', 'external_system', 'common_category'));
		$platform_name = $platform_id == '0' ? lang('pay.mainwallt') : $this->external_system->getNameById($platform_id) . ' ' . lang('cashier.42');
		$data = array(
			'platform_id' => $platform_id, // non-used
			'platform_name' => $platform_name,
			'transaction_type' => $transaction_type,
			// 'player_id' => $player_id,
		);
		$data['promoCategory'] = $this->promorules->getAllPromoCategory();
		$data['promoRules'] = $this->promorules->getAvailablePromoruleList();
		$data['manual_subtract_balance_tags'] = $this->player_manager->getAllManualSubtractBalanceTags();
		if($this->utils->isEnabledFeature('enable_adjustment_category')){
			$data['adjustmentCategory'] = $this->common_category->getActiveCategoryByType(common_category::CATEGORY_ADJUSTMENT);
		}

		if($this->utils->isEnabledFeature('only_manually_add_active_promotion')){
			$data['promoCms'] = $this->promorules->getAvailablePromoCMSList();
		}else{
			$data['promoCms'] = $this->promorules->getAllPromoCMSList();
		}

		$userId=$this->authentication->getUserId();
		$data['double_submit_hidden_field']=$this->initDoubleSubmitAndReturnHiddenFieldForAdmin($userId);

		$this->session->set_userdata('prevent_refresh', true);
		//move to marketing_management

		$currency = $this->utils->getCurrentCurrency();
		$data['currency_decimals'] = $currency['currency_decimals'];
		$data['min_decimals'] = round(1/pow(10, $currency['currency_decimals']), $currency['currency_decimals']);

		$data['is_own_page'] = $is_own_page ;
        $this->load->view('payment_management/balance_adjustment/adjust_amounts_balance_form', $data);
    } // EOF adjust_amounts_balance_form

	/**
	 *
	 * detail: view adjust withdrawal fee
	 *
	 * @param string $transation_type
	 * @param int $player_id player id
	 * @return load template
	 */
	public function adjust_withdrawal_fee_form($transaction_type, $player_id = null) {
		$this->load->model(array('promorules', 'external_system', 'common_category'));
		$platform_name = lang('pay.mainwallt');
		$data = array(
			'platform_name' => $platform_name,
			'transaction_type' => $transaction_type,
			'player_id' => $player_id,
		);

		$userId=$this->authentication->getUserId();
		$data['double_submit_hidden_field']=$this->initDoubleSubmitAndReturnHiddenFieldForAdmin($userId);

		$this->session->set_userdata('prevent_refresh', true);

		$this->load->view('payment_management/balance_adjustment/adjust_withdrawal_fee_form', $data);
	}

	public function adjust_withdrawal_fee_post($transaction_type, $player_id) {
		if (!$this->permissions->checkPermissions('manual_subtract_withdrawal_fee')) {
			$this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('con.pym01'));
			redirect('payment_management/adjust_balance/' . $player_id);
			return;
		}

		$this->load->model(array('transactions', 'wallet_model', 'response_result','player_model'));

		$userId=$this->authentication->getUserId();

		if(!$this->verifyAndResetDoubleSubmitForAdmin($userId)){
			$message = lang('Please refresh and try, and donot allow double submit');
			$this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);
			redirect('payment_management/adjust_balance/' . $player_id);
			return;
		}

		$this->utils->debug_log('----------------------post' , $this->input->post());

		$beforeBalance = $this->wallet_model->getBalanceDetails($player_id);
		$frozen = $beforeBalance['frozen'];
		$totalBalance = $beforeBalance;
		$amount = $this->input->post('amount');
		$_reason = $this->input->post('reason');
		$withdraw_code = $this->input->post('withdraw_code');
		$only_create_transaction = $this->input->post('only_create_transaction');
		$transaction = "Withdrawal";
		$related_trans_id = null;
		$walletAccountId = null;
		$withdra_status = null;
		$controller = $this;
		$success = false;
		$message = lang('notify.61');

		if(!empty($withdraw_code)){
			$walletAccountId = $this->wallet_model->getWalletaccountIdByTransactionCode($withdraw_code);
		}else{
			$message = lang('Withdraw code is required');
            $this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);
            redirect('payment_management/adjust_balance/' . $player_id);
            return;
		}

		if(!empty($walletAccountId)){
			$withdra_status = $this->wallet_model->getWalletAccountStatus($walletAccountId);
		}else{
			$message = lang('Not found wallet account id');
            $this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);
            redirect('payment_management/adjust_balance/' . $player_id);
            return;
		}

		if(!empty($withdra_status) && $withdra_status == wallet_model::PAID_STATUS){
			$related_trans_id = $this->transactions->getRelatedTransIdBySecureId($withdraw_code);
		}else{
			$message = lang('this withdraw not yet approv');
            $this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);
            redirect('payment_management/adjust_balance/' . $player_id);
            return;
		}

		if(strlen($_reason)>120) {
            $message = lang('Maximum 120 characters (including spaces).');
            $this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);
            redirect('payment_management/adjust_balance/' . $player_id);
            return;
		}

		$reason = htmlentities($_reason, ENT_QUOTES);

		if ($only_create_transaction) {
				$success = $this->lockAndTransForPlayerBalance($player_id, function ()
					use ($controller, $amount, $transaction, $userId, $player_id, $related_trans_id, $walletAccountId, $only_create_transaction, &$totalBalance) {

				$success=false;
				$controller->utils->debug_log('amount playerId', $amount,$player_id);
				$success = $controller->transactions->createTransactionFee($amount, $transaction, $userId, $player_id, $related_trans_id, $walletAccountId, null, null, Transactions::MANUAL_SUBTRACT_WITHDRAWAL_FEE, Transactions::MANUAL, Transactions::MANUALLY_ADJUSTED, $only_create_transaction);
				$totalBalance = $controller->wallet_model->getBalanceDetails($player_id);
				$controller->utils->debug_log('----------- only_create_transaction adjust_withdrawal_fee_post success amount playerId', $amount,$player_id,$success);

				return $success;
			});
		}else{
			if($amount > 0 && $amount <= $frozen){
				$success = $this->lockAndTransForPlayerBalance($player_id, function ()
					use ($controller, $amount, $transaction, $userId, $player_id, $related_trans_id, $walletAccountId, $only_create_transaction, &$totalBalance) {

					$success=false;
					$controller->utils->debug_log('amount playerId', $amount,$player_id);
					$success = $controller->transactions->createTransactionFee($amount, $transaction, $userId, $player_id, $related_trans_id, $walletAccountId, null, null, Transactions::MANUAL_SUBTRACT_WITHDRAWAL_FEE, Transactions::MANUAL, Transactions::MANUALLY_ADJUSTED, $only_create_transaction);
					$totalBalance = $controller->wallet_model->getBalanceDetails($player_id);
					$controller->utils->debug_log('-----------adjust_withdrawal_fee_post success amount playerId', $amount,$player_id,$success);

					return $success;
				});
			}else{
				$message = lang('Please check player Main Wallet or Amount in Withdrawal Process');
			}
		}

		$this->utils->debug_log('-----------adjust_withdrawal_fee_post params detail' ,'success', $success, 'amount', $amount, 'transaction', $transaction, 'userId', $userId, 'player_id', $player_id, 'related_trans_id', $related_trans_id, 'walletAccountId', $walletAccountId, 'only_create_transaction', $only_create_transaction, 'beforeBalance', $beforeBalance, 'totalBalance', $totalBalance);

		$this->saveAction(self::ACTION_LOG_TITLE, 'Adjust withdrawal fee balance', $success);

		if ($success) {
			$this->payment_manager->addPlayerBalAdjustmentHistory(array(
				'playerId' => $player_id,
				'adjustmentType' => $transaction_type,
				'walletType' => 0, # 0 - MAIN WALLET
				'amountChanged' => $amount,
				'oldBalance' => $beforeBalance && is_array($beforeBalance) ? $beforeBalance['total_balance'] : null,
				'newBalance' => $totalBalance && is_array($totalBalance) ? $totalBalance['total_balance'] : null,
				'reason' => $reason,
				'adjustedOn' => $this->utils->getDatetimeNow(),
				'adjustedBy' => $userId,
				'show_flag' => false,
			));
			$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang('con.pym09'));
		} else {
			$this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);
		}
		redirect('payment_management/adjust_balance/' . $player_id);
	}

	public function adjust_seamless_balance_post($game_platform_id, $transaction_type, $player_id){
		if (!$this->permissions->checkPermissions('adjust_manually_seamless_wallet')) {
			$this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('con.pym01'));
			redirect('payment_management/adjust_balance/' . $player_id);
			return;
		}
		$userId=$this->authentication->getUserId();

		if(!$this->verifyAndResetDoubleSubmitForAdmin($userId)){
			$message = lang('Please refresh and try, and donot allow double submit');
			$this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);
			redirect('payment_management/adjust_balance/' . $player_id);
			return;
		}

		$amount = (float)$this->input->post('amount');
		$reason = $this->input->post('reason');
		$success = false;
		$controller = $this;
		$beforeBalance = $this->wallet_model->getBalanceDetails($player_id);
		$totalBalance = $beforeBalance;
		$message = lang('notify.61');

		if($amount > 0){
			if($transaction_type == Transactions::MANUAL_SUBTRACT_SEAMLESS_BALANCE){
				$success = $this->lockAndTransForPlayerBalance($player_id, function() use($controller, $player_id, $amount, $game_platform_id,&$totalBalance, &$message) {
					$wallet_balance = $controller->CI->player_model->getPlayerSubWalletBalance($player_id, $game_platform_id);
					if($this->utils->compareResultFloat($wallet_balance, '<', $amount)){
						$message = lang('Do not have enough available balance');
						return false;
					} else {
						$result = $controller->wallet_model->decSubWallet($player_id, $game_platform_id, $amount);
						$totalBalance = $this->wallet_model->getBalanceDetails($player_id);
						return $result;
					}

				});
			} elseif($transaction_type == Transactions::MANUAL_ADD_SEAMLESS_BALANCE) {
				$success = $this->lockAndTransForPlayerBalance($player_id, function() use($controller, $player_id, $amount, $game_platform_id, &$totalBalance) {
						$result = $controller->wallet_model->incSubWallet($player_id, $game_platform_id, $amount);
						$totalBalance = $this->wallet_model->getBalanceDetails($player_id);
						return $result;
					});
			}
		}


		if($success){
			$this->payment_manager->addPlayerBalAdjustmentHistory(array(
				'playerId' => $player_id,
				'adjustmentType' => $transaction_type,
				'walletType' => $game_platform_id,
				'amountChanged' => $amount,
				'oldBalance' => $beforeBalance && is_array($beforeBalance) ? $beforeBalance['total_balance'] : null,
				'newBalance' => $totalBalance && is_array($totalBalance) ? $totalBalance['total_balance'] : null,
				'reason' => $reason,
				'adjustedOn' => $this->utils->getDatetimeNow(),
				'adjustedBy' => $userId,
				'show_flag' => false,
			));

			$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang('con.pym09'));
		}
		else {
			$this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);
		}
		redirect('payment_management/adjust_balance/' . $player_id);
	}

	/**
	 *
	 * detail: save/update the balance for a certain player
	 *
	 * @POST $amount
	 * @POST $reason
	 * @POST $show_in_front_end
	 * @param int $gamePlatformId game_platform_id
	 * @param string $transaction_type
	 * @param int $player_id player id
	 * @return void
	 */
	public function adjust_balance_post($gamePlatformId, $transaction_type, $player_id, $is_own_page = 'false') {
		$userId=$this->authentication->getUserId();

		if(!$this->verifyAndResetDoubleSubmitForAdmin($userId)){
			$message = lang('Please refresh and try, and donot allow double submit');
			$this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);
			redirect('payment_management/adjust_balance/' . $player_id);
			return;
		}

		//standalone page
		$is_own_page= $is_own_page=='true';

		# GET PARAMETERS FROM SYSTEM
		$current_timestamp = $this->utils->getNowForMysql();
		$user_id = $this->authentication->getUserId();
		$this->load->model(array('transactions', 'wallet_model', 'response_result','player_model', 'users'));
		# GET REQUIRED PARAMETERS FROM REQUEST
		$manual_subtract_balance_tag_id = $this->input->post('manual_subtract_balance_tag_id');
		$amount = $this->input->post('amount');
		$_reason = $this->input->post('reason');
		$isClearAllBalance = $this->input->post('manual_subtract_all_balance');

		if(strlen($_reason)>120) {
            $message = lang('Maximum 120 characters (including spaces).');
            $this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);
            redirect('payment_management/adjust_balance/' . $player_id);
            return;
		}

		if ($isClearAllBalance) {
			$bigWallet = $this->utils->getBigWalletByPlayerId($player_id);
			$amount = $bigWallet['total_nofrozen'];
		}

        $role_id = $this->users->getRoleIdByUserId($user_id);
        $limit_by_role = $this->utils->getConfig('limit_manual_adjustment_by_roles');
        if(array_key_exists($role_id, $limit_by_role)) {

            if($transaction_type == transactions::MANUAL_ADD_BALANCE) {
                if($amount > $limit_by_role[$role_id]['max_amount_for_add_balance']) {
                    $message = sprintf(lang('manual_adjust.max_add_balance'), $limit_by_role[$role_id]['max_amount_for_add_balance']);
                    $this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);
                    redirect('payment_management/adjust_balance/' . $player_id);
                    return;
                }
                $transactions_by_user = $this->transactions->getTransactionTotalByTransactionTypesAndDayAndUserId([transactions::MANUAL_ADD_BALANCE], date('Y-m-d'), $user_id);
                $manual_add_balance_today = !empty($transactions_by_user[transactions::MANUAL_ADD_BALANCE]) ? $transactions_by_user[transactions::MANUAL_ADD_BALANCE] : 0;
                if(($manual_add_balance_today + $amount) > $limit_by_role[$role_id]['max_daily_add_balance']) {
                    $message = lang('manual_adjust.max_daily_add_balance');
                    $this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);
                    redirect('payment_management/adjust_balance/' . $player_id);
                    return;
                }
            }

        }

		$reason = htmlentities($_reason, ENT_QUOTES);

		$show_in_front_end = $this->input->post('show_in_front_end');
		$adjustment_category = null;
		if($this->utils->isEnabledFeature('enable_adjustment_category')){
			$adjustment_category = $this->input->post('adjustment_category_id');
		}

		$promo_category = null;
		$promoRuleId=null;
		$promoCmsSettingId = $this->input->post('promo_cms_id') ;
		if(!empty($promoCmsSettingId)){

			$promoRuleId  = $this->promorules->getPromorulesIdByPromoCmsId($promoCmsSettingId);

	    	if (!empty($promoRuleId)) {
				$promorule = $this->promorules->getPromoRuleRow($promoRuleId);
				$promo_category = $promorule['promoCategory'];
			}

		}

		$isTransfer = $transaction_type == Transactions::MANUAL_ADD_BALANCE_ON_SUB_WALLET ||
		$transaction_type == Transactions::MANUAL_SUBTRACT_BALANCE_ON_SUB_WALLET;
		$make_up_only = $this->input->post('make_up_only');

		$this->utils->debug_log('isTransfer', $isTransfer, 'transaction_type', $transaction_type, 'make_up_only', $make_up_only, 'make_up_transfer_record', $this->permissions->checkPermissions('make_up_transfer_record'));

		if ($isTransfer && $make_up_only == 'true' && $this->permissions->checkPermissions('make_up_transfer_record')) {
			//fix balance
			// $this->startTrans();

			// $really_fix_balance = $this->input->post('really_fix_balance') == 'true';
			// $playerAccount = $this->wallet_model->getPlayerWalletByType($player_id, $gamePlatformId);
			// $wallet_id = $playerAccount->playerAccountId;

			// $player_name = $this->player_model->getUsernameById($player_id);
			// $note = 'make up transfer record subwallet:' . $wallet_id . ', amount:' . $amount . ', player id:' . $player_id . '. reason:' . $reason;
			// $transId = $this->transactions->makeUpTransferTransaction($player_id, $transaction_type, $wallet_id, $gamePlatformId,
			// 	$amount, $note, $really_fix_balance, $this->input->post('make_up_date'),null,$adjustment_category);

			// $success = !empty($transId);

			// if ($success) {
			// 	if($this->utils->isEnabledFeature('auto_add_reason_in_adjustment_main_wallet_to_player_notes')){
			// 		$this->player_model->addPlayerNote($player_id, $user_id, $reason);
			// 	}
			// 	//call api to
			// 	if ($really_fix_balance && $transaction_type == Transactions::MANUAL_ADD_BALANCE_ON_SUB_WALLET) {
			// 		$api = $this->utils->loadExternalSystemLibObject($gamePlatformId);
			// 		$apiResult = $api->depositToGame($player_name, $amount);
			// 		$success = $apiResult['success'];
			// 	}
			// }

			// if ($success) {
			// 	$success = $this->endTransWithSucc();
			// } else {
			// 	//rollback
			// 	$this->rollbackTrans();
			// }
			// $rlt = array('success' => $success);
			$rlt = array('success' => false);
		} else {
			$rlt = $this->_commonBalanceAdjusmentLogic($player_id, $gamePlatformId, $transaction_type,
				$amount, $user_id, $reason, $promo_category, $show_in_front_end, $promoRuleId, $promoCmsSettingId, $manual_subtract_balance_tag_id,$adjustment_category);
		}

		if(!isset($rlt['response_result_id']) && $gamePlatformId!='0'){
			$this->utils->error_log('lost response_result_id on game platform', $gamePlatformId);
		}

		if(isset($rlt['reason_id']) && !empty($rlt['reason_id'])){
			$abstractApi=$this->utils->loadAnyGameApiObject();
			$message='API: '.$abstractApi->translateReasonId($rlt['reason_id']);
		}else{
			$responseResData = $this->response_result->getResponseResultById(@$rlt['response_result_id']);
			if (!empty($responseResData)) {
				$message = $responseResData->status_text;
			}
		}

		if(isset($gamePlatformId) && $gamePlatformId == BBIN_API) {
			if(strrpos($amount, '.')) {
				$amount=floatval($amount);
				if($amount < 1) {
					$rlt['success'] = false;
					$rlt['message'] = lang('not_allow_decimal');
				}
			}
		}

		if (!$rlt['success']) {
			if (isset($rlt['message']) && !empty($rlt['message'])) {
				$message = $rlt['message'];
			} else {
				if($transaction_type == Transactions::MANUAL_SUBTRACT_BALANCE) {
					$message = lang('con.insufficientBalance');
				} else {
					$message = lang('notify.61');
				}
			}
			$this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);
		} else {
			$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang('con.pym09'));
		}

		redirect('payment_management/adjust_balance/' . $player_id);
	}

	/**
	 *
	 * @param int $gamePlatformId game_platform_id
	 * @param string $transaction_type
	 * @return load template
	 */
	public function doBatchBalanceAdjustment($gamePlatformId, $transaction_type) {
		$this->load->model('player_model');

		if ($this->session->userdata('prevent_refresh') == null) {
			redirect('/marketing_management/batchBalanceAdjustment');
		}

		# GET PARAMETERS FROM SYSTEM
		$current_timestamp = $this->utils->getNowForMysql();
		$user_id = $this->authentication->getUserId();

		# GET REQUIRED PARAMETERS FROM REQUEST
		$amount = $this->input->post('amount');
		$_reason = $this->input->post('reason');
        $reason = htmlentities($_reason, ENT_QUOTES);

		$promo_category = null;
		$promoRuleId = $this->input->post('promoRuleId');
		$show_in_front_end = $this->input->post('show_in_front_end');

		$text = file_get_contents($_FILES['usernames']['tmp_name']);
		$usernames = explode("\n", $text);
		$usernames = array_filter($usernames);
		$usernames = array_unique($usernames);

		$result = array('failed_count' => 0, 'success' => 0, 'failed' => []);
		$result['total_count'] = count($usernames);
		$success_users = [];

		foreach ($usernames as $username) {

			$username = trim($username);
			$player_id = $this->player_model->getPlayerIdByUsername($username);

			if ($player_id) {
				$this->startTrans();
				$rlt = $this->_commonBalanceAdjusmentLogic($player_id, $gamePlatformId, $transaction_type,
					$amount, $user_id, $reason, $promo_category, $show_in_front_end, $promoRuleId);
				$success = $this->endTransWithSucc();

				if (!$success || !$rlt['success']) {

					if (isset($rlt['message']) && !empty($rlt['message'])) {
						$message = $rlt['message'];
					} else {
						$message = lang('notify.61');
					}

					$result['failed'][$message][] = $username;
					@$result['failed_count']++;

				} else {
					$success_users[] = $username;
				}

			} else {
				$result['failed']['User does not exist'][] = $username;
				@$result['failed_count']++;
			}
		}

		$result['success_users'] = $success_users;
		$this->session->unset_userdata('prevent_refresh');

		$this->loadTemplate('Batch Balance Adjustment', '', '', 'payment');
		$this->template->write_view('sidebar', 'payment_management/sidebar');
		$this->template->write_view('main_content', 'payment_management/batch_balance_adjustment_result', $result);
		$this->template->render();
	}

    public function doBatchBalanceAdjustmentWithAmounts($gamePlatformId, $transaction_type) {
		$this->load->model('player_model');

		if ($this->session->userdata('prevent_refresh') == null) {
			redirect('/marketing_management/batchBalanceAdjustment');
		}

		# GET PARAMETERS FROM SYSTEM
		$current_timestamp = $this->utils->getNowForMysql();
		$user_id = $this->authentication->getUserId();

		# GET REQUIRED PARAMETERS FROM REQUEST
		// $amount = $this->input->post('amount');
		$_reason = $this->input->post('reason');
        $reason = htmlentities($_reason, ENT_QUOTES);

        $adjustment_category = null;
        if($this->utils->isEnabledFeature('enable_adjustment_category')){
            if( ! empty($this->input->post('adjustment_category_id')) ){
                $adjustment_category = $this->input->post('adjustment_category_id');
            }
        }

		$promo_category = null;
		$promoRuleId = $this->input->post('promoRuleId');
		$show_in_front_end = $this->input->post('show_in_front_end');

		$import_csv_header =['username', 'amount'];
        $csv_file = $_FILES['usernames_amounts']['tmp_name'];
        $ignore_first_row = false;
        $cnt = 0; // for collect
        $message = null; // for collect
        $controller = $this;
        $result = []; // for collect
        $result['total_count'] = 0;
        $result['success_users'] = [];
        $result['failed'] = [];
        $result['failed_count'] = 0;
        $this->utils->loopCSV( $csv_file, $ignore_first_row, $cnt, $message , function ($_cnt, $csv_row, &$stop_flag)
                use ( $import_csv_header, $controller, $gamePlatformId, $transaction_type, $user_id, $reason, $promo_category, $show_in_front_end, $promoRuleId, $adjustment_category, &$result)
        { // callback
            $stop_flag = false; // assign true to break
            $row = [];
            if( count($import_csv_header) == count($csv_row) ){
                $row = array_combine( $import_csv_header, $csv_row);
                $row = $controller->utils->_extract_row($row);
            }
            if ( empty($row) ) {
                // nothing todo for skip this round
            }else if( ! empty($row['username']) ){
                $username = $row['username'];
                $player_id = $controller->player_model->getPlayerIdByUsername($username);
                $amount = $row['amount'];

                if(empty($player_id)){
                    $result['failed']['User does not exist'][] = $username;
                    @$result['failed_count']++;
                }else{
                    $controller->utils->debug_log('OGP31519.adjustment_category:', $adjustment_category, 'gamePlatformId:', $gamePlatformId);
                    $controller->startTrans();
                    $rlt = $controller->_commonBalanceAdjusmentLogic( $player_id // #1
                                                                    , $gamePlatformId // #2
                                                                    , $transaction_type // #3
                                                                    , $amount // #4
                                                                    , $user_id // #5
                                                                    , $reason // #6
                                                                    , $promo_category // #7
                                                                    , $show_in_front_end // #8
                                                                    , $promoRuleId // #9
                                                                    , null // #10
                                                                    , null // #11
                                                                    , $adjustment_category // #12
                                                                );
                    $success = $controller->endTransWithSucc();
                    if (!$success || !$rlt['success']) {

                        if (isset($rlt['message']) && !empty($rlt['message'])) {
                            $message = $rlt['message'];
                        } else {
                            $message = lang('notify.61');
                        }

                        $result['failed'][$message][] = $username;
                        @$result['failed_count']++;

                    } else {
                        $result['success_users'][] = $username;
                            // $success_users[] = $username;
                    }
                } // EOF if(empty($player_id)){...
            // EOF if( ! empty($row['username']) ){...
            }else{
                $message = lang('notify.61');
                $result['failed'][$message][] = $username;
                // $result['failed']['User does not exist'][] = $username;
                @$result['failed_count']++;
            }
		}); // EOF $this->utils->loopCSV(...
        $result['total_count'] = count($result['success_users']) +$result['failed_count'];

		$this->session->unset_userdata('prevent_refresh');

		$this->loadTemplate('Batch Balance Adjustment With Amounts', '', '', 'payment');
		$this->template->write_view('sidebar', 'payment_management/sidebar');
		$this->template->write_view('main_content', 'payment_management/batch_balance_adjustment_with_amounts_result', $result);
		$this->template->render();
	} // EOF doBatchBalanceAdjustmentWithAmounts

	private function _commonBalanceAdjusmentLogic($player_id // #1
                                                , $wallet_type // #2
                                                , $adjustment_type // #3
                                                , $amount // #4
                                                , $user_id // #5
                                                , $reason // #6
                                                , $promo_category = null // #7
                                                , $show_in_front_end = null // #8
                                                , $promoRuleId = null // #9
                                                , $promoCmsSettingId = null // #10
                                                , $manual_subtract_balance_tag_id = null // #11
                                                , $adjustment_category = null // #12
    ) {
		$this->load->model(array('transactions', 'external_system', 'player_model', 'users', 'player_promo', 'promorules', 'wallet_model', 'transaction_notes'));

		$current_timestamp = $this->utils->getNowForMysql();
		$deposit_amt_condition = $this->input->post('depositAmtCondition') ? $this->input->post('depositAmtCondition') : null;
        $generate_withdrawal_condition = !!$this->input->post('generate_withdrawal_condition');

		$wallet_name = $wallet_type ? $this->external_system->getNameById($wallet_type) . ' Subwallet' : 'Main Wallet';
		$player_name = $this->player_model->getUsernameById($player_id);
		$user_name = $this->users->selectUsersById($user_id)['username'];

		//set promo category
		$promo_category = null;
		if (!empty($promoRuleId)) {
			$promorule = $this->promorules->getPromoRuleRow($promoRuleId);
			$promo_category = $promorule['promoCategory'];
		}

		$result = array('success' => false);

		switch ($adjustment_type) {

		case Transactions::MANUAL_ADD_BALANCE_ON_SUB_WALLET:
			$from_id = 0; # main wallet
			$to_id = $wallet_type; # wallet id
		case Transactions::AUTO_ADD_CASHBACK_TO_BALANCE:
		case Transactions::MANUAL_ADD_BALANCE:
		case Transactions::ADD_BONUS:
			$action_name = 'Add';
			break;
		case Transactions::MANUAL_SUBTRACT_BALANCE_ON_SUB_WALLET:
			$from_id = $wallet_type; # wallet id
			$to_id = 0; # main wallet
		case Transactions::SUBTRACT_BONUS:
		case Transactions::MANUAL_SUBTRACT_BALANCE:
			$action_name = 'Subtract';
			break;

		default:
			return array('success' => false);
			break;
		}


		if (!$deposit_amt_condition) {
			$deposit_amount_note = '';
		} else {
			$deposit_amount_note = 'with deposit condition of ' . $deposit_amt_condition;
		}

		if ($wallet_type) {
			$result = $this->utils->transferWallet($player_id, $player_name, $from_id, $to_id, $amount, $user_id, null, null, false, $reason, Transactions::MANUALLY_ADJUSTED);

			if ($result['success']) {
				$totalBalance = $this->wallet_model->getBalanceDetails($player_id);

				$this->payment_manager->addPlayerBalAdjustmentHistory(array(
					'playerId' => $player_id,
					'adjustmentType' => $adjustment_type,
					'walletType' => $wallet_type ?: 0, # 0 - MAIN WALLET
					'amountChanged' => $amount,
					'oldBalance' => $totalBalance && is_array($totalBalance) ? $totalBalance['total_balance'] : null,
					'newBalance' => $totalBalance && is_array($totalBalance) ? $totalBalance['total_balance'] : null,
					'reason' => $reason,
					'adjustedOn' => $current_timestamp,
					'adjustedBy' => $user_id,
					'show_flag' => $show_in_front_end == '1',
				));
			}

			$note = 'transfer wallet from ' . $from_id . ' to ' . $to_id . ', player:' . $player_name .
				', wallet_name:' . $wallet_name . ', amount:' . $amount;
		} else {
			//only main wallet
			//lock
			$lock_type = Utils::LOCK_ACTION_BALANCE;
			$lockedKey=null;
			$lock_it = $this->lockPlayerBalanceResource($player_id, $lockedKey);
			try {
				if ($lock_it) {

					$this->startTrans();

					$totalBeforeBalance = $this->wallet_model->getTotalBalance($player_id);
					$this->utils->debug_log('player_id', $player_id, 'totalBeforeBalance', $totalBeforeBalance);

					$before_adjustment = $this->player_model->getMainWalletBalance($player_id);
					switch ($adjustment_type) {
					case Transactions::AUTO_ADD_CASHBACK_TO_BALANCE:
						$after_adjustment = $before_adjustment + $amount;
						$action_name = 'Add manually cakback';
						break;
					case Transactions::MANUAL_ADD_BALANCE:
						$after_adjustment = $before_adjustment + $amount;
						$action_name = 'Add';
						break;
					case Transactions::ADD_BONUS:
						$after_adjustment = $before_adjustment + $amount;
						$action_name = 'Add';
						//create player promo
						//promoRuleId
						break;
					case Transactions::SUBTRACT_BONUS:
					case Transactions::MANUAL_SUBTRACT_BALANCE:
						$after_adjustment = $before_adjustment - $amount;
						$action_name = 'Subtract';
						break;
					}

					if ($after_adjustment < 0) {
						$this->rollbackTrans();
						return array('success' => false);
					}
					$currency = $this->utils->getCurrentCurrency();
					$currency_decimals = $currency['currency_decimals'];

					$note = sprintf('%s <b>%s</b> balance to <b>%s</b>\'s <b>%s</b>(<b>%s</b> to <b>%s</b>) by <b>%s</b>, <b>%s</b>',
						$action_name, number_format($amount, $currency_decimals), $player_name, $wallet_name,
						number_format($before_adjustment, $currency_decimals), number_format($after_adjustment, $currency_decimals),
						$user_name, $deposit_amount_note);
					$note_reason = sprintf('<i>Reason:</i> %s <br>',$reason);
					$note = (trim($reason) != '')  ? ($note_reason . sprintf('<i>Normal Note:</i> %s <br>',$note)) : $note;
					$status = $this->input->post('status');
					$betTimes = $this->input->post('betTimes');


					#if want pending, don't create transaction, only create player promo
					if($adjustment_type == Transactions::ADD_BONUS && $status == Player_promo::TRANS_STATUS_REQUEST ){

		             	// request promo
						$this->player_promo->requestPromoToPlayer($player_id, $promoRuleId, $amount, $promoCmsSettingId, $user_id, null, $deposit_amt_condition , Player_promo::TRANS_STATUS_REQUEST, $betTimes, $reason );

						 $result['success'] = $this->endTransWithSucc();
						 return $result;
					}


					$transaction = $this->transactions->createAdjustmentTransaction($adjustment_type,
						$user_id, $player_id, $amount, $before_adjustment, $note, $totalBeforeBalance,
						$promo_category, $show_in_front_end, $reason,null,$adjustment_category,Transactions::MANUALLY_ADJUSTED);


					if (!$transaction) {
						//rollback and quit;
						$this->rollbackTrans();
						return array('success' => false);
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

                    switch((int)$adjustment_type){
                        case Transactions::ADD_BONUS:
                            $deductDeposit = $this->input->post('deductDeposit');
                            $deposit_amt_condition = $this->input->post('depositAmtCondition') ? $this->input->post('depositAmtCondition') : null;


                            if ($deductDeposit) {
                                $condition = (($amount + $deposit_amt_condition) * $betTimes) - $deposit_amt_condition;
                            } else {
                                $condition = ($amount + $deposit_amt_condition) * $betTimes;
                            }

                            $promorulesId = empty($promoRuleId) ? $this->promorules->getSystemManualPromoRuleId() : $promoRuleId;

                            $this->payment_manager->savePlayerWithdrawalCondition([
                                'source_id' => $transaction['id'],
                                'source_type' => 4, # manual
                                'started_at' => $current_timestamp,
                                'condition_amount' => $condition,
                                'status' => 1, # enabled
                                'player_id' => $player_id,
                                'promotion_id' => $promorulesId,
                                'bet_times' => $betTimes,
                                'bonus_amount' => $amount,
                                'deposit_amount' => $deposit_amt_condition,
                            ]);

                            //save to player
                            if (!empty($promorulesId)) {
                                //load from
                                $promorules = $this->promorules->getPromoruleById($promorulesId);
                                $promo_category = $promorules['promoCategory'];
                            }
                            $promoCmsSettingId = $this->promorules->getSystemManualPromoCMSId();
                            $playerBonusAmount = $amount;
                            $player_promo_id = $this->player_promo->approvePromoToPlayer($player_id, $promorulesId, $playerBonusAmount,
                                $promoCmsSettingId, $user_id);
                            //update player promo id of transaction
                            $this->transactions->updatePlayerPromoId($transaction['id'], $player_promo_id, $promo_category);
                            break;
                        case Transactions::MANUAL_SUBTRACT_BALANCE:
                            if(!empty($manual_subtract_balance_tag_id)){
                                $tags = explode(',', $manual_subtract_balance_tag_id);
                                foreach($tags as $tag) {
                                    $data = Array(
                                        'rtn_id' => $transaction['id'],
                                        'msbt_id' => $tag,
                                        'created_at' => $transaction['created_at'],
                                        'updated_at' => $transaction['created_at']
                                    );
                                    $this->player_manager->insertTransactionsTag($data);
                                }
                            }
                            break;
                        case Transactions::AUTO_ADD_CASHBACK_TO_BALANCE:
                            $commonSettings = (array)$this->group_level->getCashbackSettings();
                            $commonMaxBonus = $commonSettings['max_cashback_amount'];
                            $withdraw_condition = (isset($commonSettings['withdraw_condition'])) ? (float)$commonSettings['withdraw_condition'] : 0;

                            $withdraw_condition_amount = $amount * $withdraw_condition;

                            if($generate_withdrawal_condition){
                                $this->withdraw_condition->createWithdrawConditionForCashback($transaction['id'], $withdraw_condition_amount, $withdraw_condition, $player_id, $amount);
                            }else{
								$withdraw_condition_amount = 0;
							}

                            $adminUserId = $this->authentication->getUserId();
                            $this->transaction_notes->add($reason, $adminUserId, $adjustment_type, $transaction['id']);

							/// for MANUALLY_ADD_CASHBACK in total_cashback_player_game_daily.cashback_type
							$currentDateTime = new DateTime();
							$currentDate = $this->utils->formatDateForMysql($currentDateTime);

							$cashback_type = Group_level::MANUALLY_ADD_CASHBACK;
							$original_bet_amount = 0;
							$game_platform_id = 0;
							$game_description_id = 0;
							$game_type_id = 0;
							$total_date = $currentDate;
							$cashback_amount = $amount;
							$history_id = $transaction['id']; // source_id
							$level_id = 0; // @todo player.levelId
							$rate = 1;
							$bet_amount = 0;
							// $withdraw_condition_amount
							$max_bonus = 0;
							$transaction_id = $transaction['id'];
							$uniqueid = sprintf("%s_%s_%s_%s", $total_date, $cashback_type, $player_id, $transaction_id);
							$affected_id = $this->group_level->syncCashbackDaily( $player_id // #1
								, $game_platform_id // #2
								, $game_description_id // #3
								, $total_date // #4
								, $cashback_amount // #5
								, $history_id // #6
								, $game_type_id // #7
								, $level_id // #8
								, $rate // #9
								, $this->utils->roundCurrencyForShow($bet_amount) // #10
								, $withdraw_condition_amount // #11
								, $max_bonus // #12
								, $original_bet_amount // #13
								, $cashback_type // #14
								, null // #15, $invited_player_id
								, $uniqueid// #16
							);
							if( ! empty($affected_id) ){
								// update PaidFlag
								$paid_amount = $cashback_amount;
								$this->group_level->setPaidFlag($affected_id, $paid_amount);
							}
                            break;
                        default:
                    }


					$result['success'] = $this->endTransWithSucc();
				}
			} finally {
				// release it
				$rlt = $this->releasePlayerBalanceResource($player_id, $lockedKey);
			}

		}

		if (!$result['success']) {
			$note = $note . ' failed';
		}
		$this->saveAction(self::ACTION_LOG_TITLE, 'Adjust Balance', $note);

		return $result;
	}
	# END ADJUST BALANCE ###########################################################################################################################

	/**
	 * detail: display page for Mass bunos form
	 * @deprecated
	 *
	 * @return load template
	 */
	public function mass_add_bonus() {
		$this->load->model(array('group_level', 'player_model'));
		$this->template->add_css('resources/css/collapse-style.css');
		$this->template->add_css('resources/css/jquery-checktree.css');

		$this->template->add_css('resources/third_party/bootstrap-multiselect-master/docs/css/prettify.css');
		$this->template->add_css('resources/third_party/bootstrap-multiselect-master/dist/css/bootstrap-multiselect.css');

		$this->template->add_js('resources/third_party/bootstrap-multiselect-master/docs/js/prettify.js');
		$this->template->add_js('resources/third_party/bootstrap-multiselect-master/dist/js/bootstrap-multiselect.js');

		$data['memberGroup'] = $this->group_level->getAllMemberGroup();
		$data['playerLvl'] = $this->group_level->getAllPlayerLevelsForSelect();
		$data['players'] = $this->player_model->getPlayersList(); //get all players

		$this->loadTemplate(lang('payment.massBonusAdd'), '', '', 'payment');
		$this->template->write_view('sidebar', 'payment_management/sidebar');
		$this->template->write_view('main_content', 'payment_management/mass_bonus_add/view_mass_bonus_add_form', $data);
		$this->template->render();
	}

    /**
     *
     * detail: save minimum withdraw seting datas
     *
     * $POST $min_withdraw
     * @return void
     */
    public function saveMinWithdrawSetting() {
        if (!$this->permissions->checkPermissions('withdrawal_workflow')) {
            return $this->error_access();
        }

        $this->load->model(['operatorglobalsettings']);
        $min_withdraw = $this->input->post('min_withdraw');

        $data['name'] = 'min_withdraw';
        $data['value'] = $min_withdraw;
        $this->payment_manager->setOperatorGlobalSetting($data);

        $this->saveAction(self::ACTION_LOG_TITLE, 'Update Minimum Withdraw Setting', "User " . $this->authentication->getUsername() . " has successfully update minimum withdraw setting.");

        $message = lang('pay.minwithsetting') . ' ' . lang('con.pym11');
        $this->alertMessage(self::MESSAGE_TYPE_SUCCESS, $message);
        redirect('payment_management/customWithdrawalProcessingStageSetting');
    }

    /**
     *
     * detail: save Withdrawal Preset Amount
     *
     * $POST $withdrawal_preset_amount
     * @return void
     */
    public function saveWithdrawalPresetAmountSetting() {
        if (!$this->permissions->checkPermissions('withdrawal_workflow')) {
            return $this->error_access();
        }

        $this->load->model(['operatorglobalsettings']);
        $withdrawal_preset_amount = $this->input->post('withdrawal_preset_amount');

        $data['name'] = 'withdrawal_preset_amount';
        $data['value'] = $withdrawal_preset_amount;
        $this->payment_manager->setOperatorGlobalSetting($data);

        $this->saveAction(self::ACTION_LOG_TITLE, 'Update Withdrawal Preset Amount Setting', "User " . $this->authentication->getUsername() . " has successfully update Withdrawal Preset Amount setting.");

        $message = lang('Withdrawal Preset Amount') . ' ' . lang('con.pym11');
        $this->alertMessage(self::MESSAGE_TYPE_SUCCESS, $message);
        redirect('payment_management/customWithdrawalProcessingStageSetting');
    }

	protected function process_player_promo($playerId, $promo_cms_id, $transferAmount=null, $subWalletId=null, &$error=null){
		$this->load->model(['player_promo', 'promorules']);
		list($promorule, $promoCmsSettingId)=$this->promorules->getByCmsPromoCodeOrId($promo_cms_id);

		$player_promo_id=null;
		if(!empty($playerId) && !empty($promo_cms_id) && !empty($promorule)){
			$promorulesId=$promorule['promorulesId'];

			$allowedFlag = $this->promorules->isAllowedPlayer($promorulesId, $promorule, $playerId);
			if(!$allowedFlag){
				$error=lang('notify.35');
				return null;
			}

			//if this promorule is required pre-application
			if($promorule['disabled_pre_application']!='1'){
				//should have approved player promo
				$this->load->model(['player_promo']);
				$player_promo_id=$this->player_promo->getApprovedPlayerPromo($playerId, $promorulesId);
			}else{
				//check sub wallet only
				if($this->promorules->isTransferPromo($promorule)){

					//check
					if ($promorule['depositConditionNonFixedDepositAmount'] == Promorules::NON_FIXED_DEPOSIT_MIN_MAX) {
						if ($transferAmount >= $promorule['nonfixedDepositMinAmount'] && $transferAmount <= $promorule['nonfixedDepositMaxAmount']) {
						} else {
							$error = lang('notify.37');
							return null;
						}
					}

					$trigger_wallets=$promorule['trigger_wallets'];
					$trigger_wallets_arr=[];
					if(!empty($trigger_wallets)){
						$trigger_wallets_arr=explode(',',$trigger_wallets);
					}
					if(!in_array($subWalletId, $trigger_wallets_arr)){
						$this->utils->error_log('subWalletId should be ', $trigger_wallets_arr ,'current',$subWalletId);
						// $message = 'Only trigger on transfer right sub-wallet';
						$error = lang('Must choose correct sub-wallet');
						return null;
					}

				}

				if($this->promorules->isDepositPromo($promorule) && !empty($transferAmount)){
					//check
					if ($promorule['depositConditionNonFixedDepositAmount'] == Promorules::NON_FIXED_DEPOSIT_MIN_MAX) {
						if ($transferAmount >= $promorule['nonfixedDepositMinAmount'] && $transferAmount <= $promorule['nonfixedDepositMaxAmount']) {
						} else {
							$error = lang('notify.37');
							return null;
						}
					}
				}

				//create player promo
                $extra_info = ['order_generated_by' => Player_promo::ORDER_GENERATED_BY_SBE_NEW_DEPOSIT];
				$player_promo_id=$this->player_promo->requestPromoToPlayer($playerId, $promorulesId, null, $promo_cms_id, null, null, null, Player_promo::TRANS_STATUS_REQUEST, null, null, null, null, $extra_info);
			}
		}

		$this->utils->debug_log('process player promo', $player_promo_id, 'player id', $playerId, 'promo_cms_id', $promo_cms_id, 'error', $error);

		return $player_promo_id;
	}

	/**
	 * detail: display form for adding deposit
	 *
	 * @POST $username
	 * @POST $amount
	 * @POST $subwallet
	 * @POST $currency
	 * @POST $payment_account_id
	 * @POST $date
	 * @POST $internal_note
	 * @POST $external_note
	 * @POST $status
	 * @return load template
	 */
	public function newDeposit() {
		if (!$this->permissions->checkPermissions('new_deposit')) {
			return $this->error_access();
		}
		$userId=$this->authentication->getUserId();
		$this->utils->debug_log(__METHOD__, 'post data', $this->input->post());

		$this->load->library('form_validation');
		$this->load->model(array('payment_account', 'sale_order', 'wallet_model', 'promorules', 'sale_orders_notes', 'sale_orders_timelog', 'http_request'));

		$this->form_validation->set_rules('username', lang('Member Username'), 'trim|xss_clean|required|callback_is_exist[player.username]');
		$this->form_validation->set_rules('amount', lang('Amount'), 'trim|xss_clean|required');
		$this->form_validation->set_rules('account', lang('Account'), 'trim|xss_clean|required');
		$this->form_validation->set_rules('date', lang('Date'), 'trim|xss_clean|required');
		$this->form_validation->set_rules('internal_note', lang('Internal Note'), 'trim|xss_clean|required');
		$this->form_validation->set_rules('external_note', lang('External Note'), 'trim|xss_clean');
		$this->form_validation->set_rules('status', lang('Status'), 'trim|xss_clean|required');

		if ($this->form_validation->run()) {

			$username = $this->input->post('username');
			$playerId = $this->player_model->getPlayerIdByUsername($username);
			$amount = $this->input->post('amount');
			$subwallet = $this->input->post('subwallet');
			$promo_cms_id = $this->input->post('promo_cms_id');
			$paymentKind = Sale_order::PAYMENT_KIND_DEPOSIT;

			$bankAccountOwnerName = $this->input->post('bank_account_owner_name');

			if(!$this->verifyAndResetDoubleSubmitForAdmin($userId)){
				$message = lang('Please refresh and try, and donot allow double submit');
				$this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);
				redirect('/payment_management/newDeposit/'.$username);
				return;
			}

			$actionlogbybankAccountOwnerName = $bankAccountOwnerName." ".sprintf("Manual New Deposit %s to %s", $this->utils->formatCurrencyNoSym($amount), $username);
			$player_promo_id = $this->process_player_promo($playerId, $promo_cms_id, $amount, $subwallet, $error);

			$this->utils->debug_log('process player promo result', $player_promo_id, $error);

			$currency = $this->config->item('default_currency');
			$payment_account_id = $this->input->post('account');
			$paymentAccount = $this->payment_account->getPaymentAccount($payment_account_id);
			$systemId = $paymentAccount->external_system_id;
			$date = $this->input->post('date');
			$internal_note = $this->input->post('internal_note');
			$external_note = $this->input->post('external_note');
			$status = $this->input->post('status');
			$show_reason = false;
			$success = false;

			if ($this->utils->getConfig('enable_newdeposit_upload_documents')) {
	            $file1 = isset($_FILES['file1']) ? $_FILES['file1'] : null;
	            $file2 = isset($_FILES['file2']) ? $_FILES['file2'] : null;
	            $this->utils->debug_log('=========== upload attached document exist ? file1 [ '.json_encode($file1).' ] , file2 [ '.json_encode($file2).' ] ');
	        }

			$lockedKey=null;
			$locked = $this->lockPlayerBalanceResource($playerId, $lockedKey);
			try {
				if ($locked) {
					$this->payment_account->startTrans();



					$saleOrderId = $this->sale_order->createSaleOrder($systemId, $playerId, $amount, $paymentKind,
						Sale_order::STATUS_PROCESSING, null, $player_promo_id, $currency, $payment_account_id, $date, null,
						$subwallet);
					$this->player_model->incTotalDepositCount($playerId);
					$success = !empty($saleOrderId);

					if($success){
						$this->sale_orders_notes->add($actionlogbybankAccountOwnerName, Users::SUPER_ADMIN_ID, Sale_orders_notes::ACTION_LOG, $saleOrderId);
						$this->sale_orders_timelog->add($saleOrderId, Sale_orders_timelog::ADMIN_USER, $userId, array('before_status' => Sale_order::STATUS_PROCESSING, 'after_status' => null));
						if (!empty($internal_note)) {
							$this->sale_orders_notes->add($internal_note, $userId, Sale_orders_notes::INTERNAL_NOTE, $saleOrderId);
						}
						if (!empty($external_note)) {
							$this->sale_orders_notes->add($external_note, $userId, Sale_orders_notes::EXTERNAL_NOTE, $saleOrderId);
						}

						if($this->utils->getConfig('enable_newdeposit_upload_documents')){
	                        $response_1 = $this->upload_attached_document($file1,$saleOrderId,$playerId,true);
	                        $response_2 = $this->upload_attached_document($file2,$saleOrderId,$playerId,true);
	                        if(isset($response_1['status'])) {
	                            if($response_1['status'] != 'success') {
	                                $message = lang('File')."1: ".lang('Upload failed.');
	                                $this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);
									redirect('/payment_management/newDeposit');
									return;
	                            }
	                        }
	                        if(isset($response_2['status'])) {
	                            if($response_2['status'] != 'success') {
	                                $message = lang('File')."2: ".lang('Upload failed.');
									$this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);
									redirect('/payment_management/newDeposit');
									return;
	                            }
	                        }
	                    }
					}

					if (empty($status)) {
						$status == Sale_order::STATUS_PROCESSING;
					}

					if ($status == Sale_order::STATUS_PROCESSING) {
						//pending
						$newDepositActionlogPending = sprintf("New Deposit is success processing, status => %s", $status);
						$this->sale_orders_notes->add($newDepositActionlogPending, Users::SUPER_ADMIN_ID, Sale_orders_notes::ACTION_LOG, $saleOrderId);

					} else if ($status == Sale_order::STATUS_SETTLED) {
						$newDepositActionlogApproved = sprintf("New Deposit is success settled, status => %s", $status);
						$suc = $this->sale_order->approveSaleOrder($saleOrderId, $newDepositActionlogApproved, $show_reason);

						if ($this->utils->isEnabledFeature('show_player_deposit_withdrawal_achieve_threshold')) {
							if ($suc) {
								$this->load->model(['player_dw_achieve_threshold']);
								$this->load->library(['payment_library']);
								$this->payment_library->verify_dw_achieve_threshold_amount($playerId, Player_dw_achieve_threshold::ACHIEVE_THRESHOLD_DEPOSIT);
							}
				        }

						//transfer to subwallet
						$transfer_to = $subwallet;
						if ($this->utils->existsSubWallet($transfer_to)) {
							$transfer_from = Wallet_model::MAIN_WALLET_ID;
							$rlt = $this->utils->transferWallet($playerId, $username, $transfer_from, $transfer_to, $amount, $userId);

							$this->utils->debug_log('transfer to subwallet failed', $playerId);
						}
					}

                    $this->utils->debug_log(__METHOD__, "player_id_{$playerId}", 'data', [
                        'saleOrderId' => !empty($saleOrderId) ? $saleOrderId : null,
                        'status' => $status,
                        'userId' => $userId,
                        'playerId' => $playerId,
                        'username' => $username,
                        'amount' => $amount,
                        'transfer_from' => !empty($transfer_from) ? $transfer_from : null,
                        'transfer_to' => !empty($transfer_to) ? $transfer_to : null,
                    ]);
				}

				$success = $this->payment_account->endTransWithSucc() && $success;
			} finally {
				// release it
				$rlt = $this->releasePlayerBalanceResource($playerId, $lockedKey);
				$this->utils->debug_log('newDeposit releasePlayerBalance', $playerId, $rlt);
			}

			if($success){
				$this->triggerDepositEvent($playerId, $saleOrderId, null, null, null, $userId);
				$this->saveHttpRequest($playerId, Http_request::TYPE_DEPOSIT);
			}

			if ($success) {
				$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang('new_deposit.success.add').' '.$error);
			} else {
				$this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('error.default.db.message').' '.$error);
			}

			if ($status == Sale_order::STATUS_PROCESSING) {
				redirect('home/nav/requestToday');
			} else {
				redirect('home/nav/approvedToday');
			}
		}

		$data['account_list'] = $this->payment_account->getAllPaymentAccountDetails(array(
			'banktype.bankName' => 'asc',
			'payment_account.payment_account_name' => 'asc',
		));

		$data['subwallets'] = $this->wallet_model->getSubwalletMap();
		$data['avail_promocms_list']=$this->promorules->getAllPromoOnDeposit();
		$data['double_submit_hidden_field']=$this->initDoubleSubmitAndReturnHiddenFieldForAdmin($userId);

		$this->loadTemplate(lang('lang.newDeposit'), '', '', 'payment');
		$this->template->write_view('sidebar', 'payment_management/sidebar');
		$this->template->add_css('resources/third_party/bootstrap-multiselect-master/dist/css/bootstrap-multiselect.css');
        $this->template->add_js('resources/third_party/bootstrap-multiselect-master/dist/js/bootstrap-multiselect.js');
		$this->template->write_view('main_content', 'payment_management/newDeposit', $data);
		$this->template->render();
	}

	/**
	 * detail: display form for adding withdrawal
	 *
	 * @POST $amount
	 * @POST $date
	 * @POST $username
	 * @POST $reason
	 * @POST $type
	 * @POST $playerBankDetailsId
	 * @POST $bankType
	 * @POST $accountName
	 * @POST $accountNumber
	 * @POST $province
	 * @POST $city
	 * @POST $branch
	 * @POST $status
	 * @return load template
	 */
	public function newWithdrawal($username=null) {
		if (!$this->permissions->checkPermissions('new_withdrawal')) {
			return $this->error_access();
		}
		$userId=$this->authentication->getUserId();

		$this->load->library(array('form_validation','payment_library'));
		$this->load->model(array('playerbankdetails', 'player_model', 'wallet_model', 'daily_player_trans', 'operatorglobalsettings', 'banktype', 'walletaccount_notes','walletaccount_timelog','group_level', 'http_request'));

		$this->form_validation->set_rules('username', 'Member Username', 'trim|xss_clean|required|callback_is_exist[player.username]');
		$this->form_validation->set_rules('amount', 'Amount', "trim|xss_clean|required");
		$this->form_validation->set_rules('date', 'Date', 'trim|xss_clean|required');
		$this->form_validation->set_rules('internal_note', lang('Internal Note'), 'trim|xss_clean|required');
		$this->form_validation->set_rules('external_note', lang('External Note'), 'trim|xss_clean');
		$this->form_validation->set_rules('type', 'Type', 'trim|xss_clean|required');

		if ($this->input->post('type') !== null) {
			switch ($this->input->post('type')) {

			case '0':
				$this->form_validation->set_rules('bankType', 'Bank Name', 'trim|xss_clean|required');
				$this->form_validation->set_rules('accountName', 'Bank Account Full Name', 'trim|xss_clean|required');
				$this->form_validation->set_rules('accountNumber', 'Bank Acount Number', 'trim|xss_clean|required');
				$this->form_validation->set_rules('province', 'Province', 'trim|xss_clean|required');
				$this->form_validation->set_rules('city', 'City', 'trim|xss_clean|required');
				$this->form_validation->set_rules('branch', 'Branch', 'trim|xss_clean|required');
				break;

			case '1':
				$this->form_validation->set_rules('bank', 'Bank', 'trim|xss_clean|required');
				break;

			}
		}

		#OGP-17242
		$getWithdrawalCustomSetting = $this->utils->getConfig('enable_pending_review_custom') ? json_decode($this->operatorglobalsettings->getSetting('custom_withdrawal_processing_stages')->template,true) : array();

		if ($this->form_validation->run()) {

			$controller=$this;

			$username = $this->input->post('username');
			$playerId = $this->player_model->getPlayerIdByUsername($username);

			if(!$this->verifyAndResetDoubleSubmitForAdmin($userId)){
				$message = lang('Please refresh and try, and donot allow double submit');
				$this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);
				redirect('/payment_management/newWithdrawal/'.$username);
				return;
			}

			$result=['success'=>false, 'message'=>lang('error.default.db.message')];
			$success = $this->lockAndTrans(Utils::LOCK_ACTION_BALANCE, $playerId, function ()
				use ($controller, $playerId, &$result, $getWithdrawalCustomSetting) {

				$userId              = $controller->authentication->getUserId();

				$amount              = $controller->input->post('amount');
				$date                = $controller->input->post('date');
				$internal_note       = $controller->input->post('internal_note');
				$external_note       = $controller->input->post('external_note');
				$type                = $controller->input->post('type');
				$playerBankDetailsId = $controller->input->post('bank');
				$bankType            = $controller->input->post('bankType');
				$accountName         = $controller->input->post('accountName');
				$accountNumber       = $controller->input->post('accountNumber');
				$province            = $controller->input->post('province');
				$city                = $controller->input->post('city');
				$branch              = $controller->input->post('branch');
				$status              = $controller->input->post('status');

				$player                  = $controller->player_model->getPlayerById($playerId);
				$ipAddress               = $controller->utils->getIP();
				$playerAccount           = $controller->wallet_model->getMainWalletBy($playerId);
				$enabled_withdrawal      = $player->enabled_withdrawal;
				$playerMainWalletBalance = $playerAccount->totalBalanceAmount;
				$playerAccountId         = $playerAccount->playerAccountId;
				$geolocation             = $controller->utils->getGeoplugin($ipAddress);
				$transactionCode         = $controller->wallet_model->getRandomTransactionCode();
				$playerBankDetails       = $this->playerbankdetails->getBankList(array('playerBankDetailsId' => $playerBankDetailsId))[0];
				$bankName                = $this->banktype->getBankTypeById($playerBankDetails['bankTypeId'])->bankName;
				$dwLocation              = implode(',', array_values($geolocation));

				if ($enabled_withdrawal == Player_model::WITHDRAWAL_DISABLED) {
					$result['message']=lang("Player Withdrawal is disabled");
					$result['success']=false;
					return $result['success'];
				}

				if ($playerMainWalletBalance < $amount) {
					$result['message']=lang("Withdrawal Amount is greater than Current Balance");
					$result['success']=false;
					return $result['success'];
				}

				$withdrawFeeAmount = 0;
				$calculationFormula = '';
				#if enable config get withdrawFee from player
				if($this->utils->getConfig('enable_withdrawl_fee_from_player') && $this->group_level->isOneWithdrawOnly($playerId)){
					list($withdrawFeeAmount,$calculationFormula) = $this->payment_library->chargeFeeWhenWithdrawalAmountOverMonthlyAmount($playerId, $player->levelId, $amount);

					if ($playerMainWalletBalance < $amount + $withdrawFeeAmount) {
						$result['message']=lang("Withdrawal Amount + Withdrawal fee is greater than Current Balance");
						$result['success']=false;
						return $result['success'];
					}
				}

				$withdrawBankFeeAmount = 0;
				$calculationFormulaBank = '';
				if ($this->utils->getConfig('enable_withdrawl_bank_fee') && $this->group_level->isOneWithdrawOnly($playerId)) {
					list($withdrawBankFeeAmount,$calculationFormulaBank) = $this->payment_library->calculationWithdrawalBankFee($playerId, $this->banktype->getBankTypeById($playerBankDetails['bankTypeId'])->bank_code, $amount);


					if ($withdrawFeeAmount > 0) {
						$checkFeeAmt = $amount + $withdrawBankFeeAmount + $withdrawFeeAmount;
					}else{
						$checkFeeAmt = $amount + $withdrawBankFeeAmount;
					}

					$this->utils->debug_log('enable_withdrawl_bank_fee' , $playerMainWalletBalance, $amount, $withdrawBankFeeAmount, $calculationFormulaBank);
					if ($checkFeeAmt > $playerMainWalletBalance) {
						$result['message']=lang("Withdrawal Amount + Withdrawal bank fee is greater than Current Balance");
						$result['success']=false;
						return $result['success'];
					}
				}

				$walletAccountData = array(
					'playerAccountId'        => $playerAccountId,
					'walletType'             => Wallet_model::TYPE_MAINWALLET,
					'amount'                 => $amount,
					'dwMethod'               => 1,
					'dwStatus'               => Wallet_model::REQUEST_STATUS,
					'dwDateTime'             => $date,
					'transactionType'        => 'withdrawal',
					'dwIp'                   => $ipAddress,
					'dwLocation'             => $dwLocation,
					'transactionCode'        => $transactionCode,
					'status'                 => '0',
					'before_balance'         => $playerMainWalletBalance,
					'after_balance'          => $playerMainWalletBalance - $amount,
					'playerId'               => $playerId,
					'player_bank_details_id' => $playerBankDetailsId,
					'notes'                  => '',
					'bankAccountFullName'    => $playerBankDetails['bankAccountFullName'],
					'bankAccountNumber'      => $playerBankDetails['bankAccountNumber'],
					'bankName'               => $bankName,
					'bankAddress'            => $playerBankDetails['bankAddress'],
					'bankCity'               => $playerBankDetails['city'],
					'bankProvince'           => $playerBankDetails['province'],
					'bankBranch'             => $playerBankDetails['branch'],
					'withdrawal_fee_amount'	 => $withdrawFeeAmount,
					'withdrawal_bank_fee' 	 => $withdrawBankFeeAmount,
				);

				# OGP-3531
				if($this->system_feature->isEnabledFeature('enable_withdrawal_pending_review')){
					if($this->checkPlayerIfTagIsUnderPendingWithdrawTag($playerId)){
						$walletAccountData['dwStatus'] = Wallet_model::PENDING_REVIEW_STATUS;
					}
				}

				#OGP-17242
				if($this->utils->getConfig('enable_pending_review_custom')){
					if(!empty($getWithdrawalCustomSetting['pendingCustom']) && $getWithdrawalCustomSetting['pendingCustom']['enabled']){
						if($this->checkPlayerIfTagIsUnderPendingCustomWithdrawTag($playerId)){
							$walletAccountData['dwStatus'] = Wallet_model::PENDING_REVIEW_CUSTOM_STATUS;
							$this->utils->debug_log("check player tag under pending custom walletAccountData", $walletAccountData['dwStatus']);
						}
					}
				}

				$localBankWithdrawalDetails = array(
					'withdrawalAmount' => $amount,
					'playerBankDetailsId' => $playerBankDetailsId,
					'depositDateTime' => $date,
					'status' => 'active',
				);

				$walletAccountId = $controller->wallet_model->newWithdrawal($walletAccountData, $localBankWithdrawalDetails, $playerId);

				#add notes to walletaccount_notes content
				if(isset($walletAccountId)){
					if (!empty($internal_note)) {
					$this->walletaccount_notes->add($internal_note, $userId, Walletaccount_notes::INTERNAL_NOTE, $walletAccountId);
					}

					if (!empty($external_note)) {
						$this->walletaccount_notes->add($external_note, $userId, Walletaccount_notes::EXTERNAL_NOTE, $walletAccountId);
					}

					$newWithdrawalActionlogPending = sprintf("New Withdrawal is success processing, status => %s", $status);
					if($this->utils->getConfig('enable_withdrawl_fee_from_player') && $this->group_level->isOneWithdrawOnly($playerId)){
						$newWithdrawalActionlogPending = sprintf("New Withdrawal is success processing, status => %s ; %s ; Withdrawal Fee Amount is %s",$status, $calculationFormula, $withdrawFeeAmount);
					}

					if($this->utils->getConfig('enable_withdrawl_bank_fee') && $this->group_level->isOneWithdrawOnly($playerId)){
						if ($withdrawBankFeeAmount > 0) {
							$newWithdrawalActionlogPending = $newWithdrawalActionlogPending . ' ' . $calculationFormulaBank;
						}
					}

					$this->wallet_model->addWalletaccountNotes($walletAccountId, $userId, $newWithdrawalActionlogPending, $status, null,Walletaccount_timelog::ADMIN_USER);
				}

				$result['success']=!!$walletAccountId;

				if($result['success']){
					$result['message']=null;

					#OGP-22453,22538
			   //      if ($this->utils->getConfig('enabled_withdrawal_abnormal_notification')) {
						// $this->triggerWithdrawalEvent($playerId, $walletAccountId, null, null, $userId);
			   //      }

				}

				return $result['success'];

			}); // end of lock and trans

			$this->utils->debug_log('new withdrawal result', $result);

			if($result['success']){
				$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang('New withdrawal has been successfully added'));
				$start_today = date("Y-m-d") . ' 00:00:00';
				$end_today = date("Y-m-d") . ' 23:59:59';

				if($this->permissions->checkPermissions('view_pending_stage')){
					$status = Wallet_model::REQUEST_STATUS;
				}else {
					$status = Wallet_model::PAID_STATUS;
				}

				$this->saveHttpRequest($playerId, Http_request::TYPE_WITHDRAWAL);

				if($this->checkPlayerIfTagIsUnderPendingWithdrawTag($playerId) && $this->system_feature->isEnabledFeature('enable_withdrawal_pending_review') && $this->permissions->checkPermissions('view_pending_review_stage')){
					$status = Wallet_model::PENDING_REVIEW_STATUS;
				}

				if($this->utils->getConfig('enable_pending_review_custom') && $this->permissions->checkPermissions('view_pending_custom_stage')){
					if($this->checkPlayerIfTagIsUnderPendingCustomWithdrawTag($playerId) && !empty($getWithdrawalCustomSetting['pendingCustom'])){
						$status = Wallet_model::PENDING_REVIEW_CUSTOM_STATUS;
					}
				}
				redirect('payment_management/viewWithdrawalRequestList?dwStatus='.$status.'&enable_date=true&withdrawal_date_from='.$start_today.'&withdrawal_date_to'.$end_today.'&date_range=2&search_status='.$status);
			} else {
				$this->alertMessage(self::MESSAGE_TYPE_ERROR, $result['message']);
			}

		}

		$data['username']=$username;
		$data['bank_list'] = $this->banktype->getBankTypeKV();
		$data['min_amount'] = $this->operatorglobalsettings->getSettingDoubleValue('min_withdraw');
		$data['double_submit_hidden_field']=$this->initDoubleSubmitAndReturnHiddenFieldForAdmin($userId);

		$this->loadTemplate(lang('lang.newWithdrawal'), '', '', 'payment');
		$this->template->write_view('sidebar', 'payment_management/sidebar');
		$this->template->write_view('main_content', 'payment_management/newWithdrawal', $data);
		$this->template->render();
	}

	/**
	 * overview: Payment Settings
	 *
	 * @return load template
	 */
	public function defaultCollectionAccount($from = 'system') {
		if (!$this->permissions->checkPermissions('default_collection_account')) {
			return $this->error_access($from);
		}
		if (($this->session->userdata('sidebar_status') == NULL)) {
			$this->session->set_userdata(array('sidebar_status' => 'active'));
		}

		# payment_account_types
		$data['payment_account_types'] = json_decode($this->operatorglobalsettings->getPaymentAccountTypes(true), true);

		# special_payment_list
		$this->load->model('payment_account');
		$special_payment_list = $this->operatorglobalsettings->getSpecialPaymentList();
		$special_payment_list_mobile = $this->operatorglobalsettings->getSpecialPaymentListMobile();
		## Get configured 3rd party payment accounts and index them using payment account ID
		$paymentAccounts = $this->payment_account->getAllPaymentAccountDetails();
		if(is_array($paymentAccounts)){
			foreach ($paymentAccounts as $aPaymentAccount) {
				$data['payment_account_list'][$aPaymentAccount->id] = $aPaymentAccount;
			}
		}

		if (!empty($special_payment_list)) {
			$data['special_payment_list'] = array_map('intval', $special_payment_list);
		} else {
			$data['special_payment_list'] = array();
		}

		if (!empty($special_payment_list_mobile)) {
			$data['special_payment_list_mobile'] = array_map('intval', $special_payment_list_mobile);
		} else {
			$data['special_payment_list_mobile'] = array();
		}

		$this->loadTemplate(lang('Default Collection Account'), '', '', 'system');
		$this->template->write_view('sidebar', 'system_management/sidebar');
		$this->template->write_view('main_content', 'payment_management/view_paymentaccount_setting', $data);
		$this->template->render();
	}

	/**
	 * detail: save data for paymetn account setting
	 *
	 * @return	void
	 */
	public function savePaymentAccountSetting() {
		$selectedPaymentAccountType = $this->input->post('selectedPaymentAccountType');
		$special_payment_list = $this->input->post('special_payment_list');
		$special_payment_list_mobile = $this->input->post('special_payment_list_mobile');

		if (empty($selectedPaymentAccountType)) {
			$selectedPaymentAccountType = [];
		}

		$payment_account_types = "{" .
			'"1" : { "lang_key": "pay.manual_online_payment", "enabled": ' . (in_array("1", $selectedPaymentAccountType) ? 'true' : 'false') . '},' .
			'"2" : { "lang_key": "pay.auto_online_payment", "enabled": ' . (in_array("2", $selectedPaymentAccountType) ? 'true' : 'false') . '},' .
			'"3" : { "lang_key": "pay.local_bank_offline", "enabled": ' . (in_array("3", $selectedPaymentAccountType) ? 'true' : 'false') . '}' .
			"}";

		$this->operatorglobalsettings->setPaymentAccountTypes($payment_account_types, true);
		$this->operatorglobalsettings->setSpecialPaymentList($special_payment_list);
		$this->operatorglobalsettings->setSpecialPaymentListMobile($special_payment_list_mobile);

		$this->saveAction(self::ACTION_LOG_TITLE, 'Update Payment Account Setting', "User " . $this->authentication->getUsername() . " has successfully update payment account setting.");

		$message = lang('report.log06');
		$this->alertMessage(1, $message);
		redirect('payment_management/defaultCollectionAccount');
	}

    /**
     *
     * detail: Defines the processing stages under a withdrawal request, User can define how many of these stages are enabled, and the names of these stages
     *
     * @return load template
     */
    public function customWithdrawalProcessingStageSetting() {
        if (!$this->permissions->checkPermissions('withdrawal_workflow')) {
            return $this->error_access();
        }
        $data['setting'] = $this->operatorglobalsettings->getCustomWithdrawalProcessingStage();
        //$data['withdrawal_setting'] = $this->operatorglobalsettings->getPlayerCenterWithdrawalPageSetting();
        $data['tag_list'] = $this->player->getAllTagsOnly();
        $data['taggedStatus'] = json_decode($this->operatorglobalsettings->getSetting('withdraw_pending_review_tags')->value, true);
        $data['customTaggedStatus'] = json_decode($this->operatorglobalsettings->getSetting('withdraw_pending_custom_tags')->value, true);

        $data['min_withdraw'] = $this->payment_manager->getOperatorGlobalSetting('min_withdraw');
        $data['withdrawal_preset_amount'] = $this->payment_manager->getOperatorGlobalSetting('withdrawal_preset_amount');

        $this->session->userdata('sidebar_status', 'active');
        $this->loadTemplate(lang('Withdrawal Workflow'), '', '', 'system');
        $this->template->write_view('sidebar', 'system_management/sidebar');
        $this->template->write_view('main_content', 'payment_management/view_custom_withdrawal_processing_stages', $data);
        $this->template->render();
    }

	/**
	 * detail: save data for withdrawal processing stage
	 *
	 * $POST $name_payProc
	 * $POST $enabled_payProc
	 * $POST $force_comment
	 * @return void
	 */
	public function saveCustomWithdrawalProcessingStageSetting() {
		$old_setting = $this->operatorglobalsettings->getCustomWithdrawalProcessingStage();
		unset($old_setting['maxCSIndex']);
		$this->utils->debug_log('-----------------old_setting',$old_setting);

		$exist = false;
		$msg = '';
		$new_setting = array();

		for ($i = 0; $i < CUSTOM_WITHDRAWAL_PROCESSING_STAGES; $i++) {
			array_push($new_setting, array(
				"name" => $this->input->post('name_' . $i),
				"enabled" => $this->input->post('enabled_' . $i),
			));
		}
		$new_setting['payProc']['name'] = lang('pay.processing');
		$new_setting['payProc']['enabled'] = $this->input->post('enabled_payProc');

		if($this->utils->getConfig('enable_pending_review_custom')){
			$new_setting['pendingCustom']['name'] = lang('pay.pendingreviewcustom');
			$new_setting['pendingCustom']['enabled'] = $this->input->post('pending_custom');
		}

		$this->utils->debug_log('-----------------new_setting',$new_setting);

		foreach ($old_setting as $old_status => $old_value) {
			if (is_int($old_status)) {
				if ($old_value['enabled'] != $new_setting[$old_status]['enabled'] &&  $new_setting[$old_status]['enabled'] == false) {
					$exist = $this->wallet_model->checkWithdrawalExistsByStatus('CS'.$old_status);
	                $msg = $old_value['name'];
				}
			}else if($old_status == 'pendingCustom'){
				if ($old_value['enabled'] != $new_setting[$old_status]['enabled'] &&  $new_setting[$old_status]['enabled'] == false) {
					$exist = $this->wallet_model->checkWithdrawalExistsByStatus(Wallet_model::PENDING_REVIEW_CUSTOM_STATUS);
	                $msg = $old_value['name'];
				}
			}
        }

        $this->utils->debug_log('-----------------result',$exist,$msg);

		if (!$exist) {
			$this->operatorglobalsettings->setCustomWithdrawalProcessingStage($new_setting);
			$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang('sys.gd25'));
			redirect('payment_management/customWithdrawalProcessingStageSetting');
		}else{
			$this->alertMessage(self::MESSAGE_TYPE_ERROR, sprintf(lang("stages_setting_error_msg"), $msg));
			redirect('payment_management/customWithdrawalProcessingStageSetting');
		}
	}

    public function cryptoCurrencySetting(){
    	if (!$this->permissions->checkPermissions('crypto_currency_conversion_setting')) {
			return $this->error_access();
		}
    	$this->load->model('payment_account');
    	$cryptoCurrencySetting = $this->payment_account->getCryptoCurrencySetting($this->config->item('cryptocurrencies'));
    	if(!empty($cryptoCurrencySetting) && is_array($cryptoCurrencySetting)){
    		foreach ($cryptoCurrencySetting as $key => $row) {
    			$cryptoCurrencySetting[$key]['update_by'] = $this->user_functions->getUserById($row['update_by'])['username'];
    		}
    		$data['cryptoCurrencySetting'] = $cryptoCurrencySetting;
    	}else{
    		$data['cryptoCurrencySetting'] = [];
    	}
        $this->session->userdata('sidebar_status', 'active');
        $this->loadTemplate(lang('pay.crypto_currency_setting'), '', '', 'system');
        $this->template->write_view('sidebar', 'system_management/sidebar');
        $this->template->write_view('main_content', 'payment_management/view_crypto_currency_setting_stages', $data);
        $this->template->render();
    }

    public function getCryptoCurrencySetting($cryptoCurrecny, $transaction){
    	$this->load->model('payment_account');
    	echo json_encode($this->payment_account->getCryptoCurrencySetting($cryptoCurrecny, $transaction));
    }

    public function saveEditCryptoCurrencySetting(){
    	$this->form_validation->set_rules('editExchangeRateMultiplier', lang('Exchange Rate Multiplier'), 'trim|required|xss_clean|numeric');
    	if ($this->form_validation->run()){
			$editCryptoCurrency = $this->input->post('editCryptoCurrency');
			$editTransation = $this->input->post('editTransation');
			$editExchangeRateMultiplier = $this->input->post('editExchangeRateMultiplier');
			$adminUserId = $this->authentication->getUserId();
			$this->load->model('payment_account');
			$success = $this->payment_account->updateCryptoCurrencySetting($editCryptoCurrency, $editTransation, $editExchangeRateMultiplier, $adminUserId);
			if ($success){
			$this->saveAction(self::ACTION_LOG_TITLE, 'Update Crypto Currency Setting', "User " . $this->authentication->getUsername() . " has successfully update crypto currency setting.");
			$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang('save.success'));
			}else {
				if(!is_numeric($editExchangeRateMultiplier)){
					$message = lang('only_numeric');
				}else{
					$message = lang('error.default.db.message');
				}
				$this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);
			}
		}else{
			$this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('error.default.db.message'));
		}
		redirect('payment_management/cryptoCurrencySetting');
    }

	public function depositCountSetting() {
		if (!$this->permissions->checkPermissions('deposit_count_setting')) {
			return $this->error_access();
		}
		$this->loadTemplate(lang('role.72'), '', '', 'system');

		if (($this->session->userdata('sidebar_status') == NULL)) {
			$this->session->set_userdata(array('sidebar_status' => 'active'));
		}

		$this->load->model(['operatorglobalsettings']);

		$isSettingExist = $this->operatorglobalsettings->getSetting('deposit_count_list');

		$data['count'] = $isSettingExist->value ? $isSettingExist->value : self::DEFAULT_DEPOSIT_COUNT_SETTING;

		$this->template->write_view('sidebar', 'system_management/sidebar');
		$this->template->write_view('main_content', 'payment_management/view_deposit_count_list', $data);
		$this->template->render();
	}

	public function saveDepositCountList() {
		$this->load->model(array('operatorglobalsettings'));

		$depositCount = $this->input->post('deposit_count');

		$isSettingExist = $this->operatorglobalsettings->getSetting('deposit_count_list');

		if ($isSettingExist) {
			$this->operatorglobalsettings->putSetting('deposit_count_list', $depositCount[0], 'value');
		} else {
			$this->operatorglobalsettings->addRecord([ 'name' => 'deposit_count_list', 'value' => $depositCount[0] ]);
		}

		$this->returnJsonResult([$isSettingExist]);
	}

	public function exception_order_list(){
		if(!$this->permissions->checkPermissions('exception_order_list'))
			return $this->error_access();

		$this->loadTemplate(lang('Exception Orders'), '', '', 'payment');

		if (($this->session->userdata('sidebar_status') == NULL)) {
			$this->session->set_userdata(array('sidebar_status' => 'active'));
		}

		$data=['title'=>lang('Exception Orders')];
		$data['export_report_permission']=$this->permissions->checkPermissions('export_exception_order');
		$data['conditions'] = $this->safeLoadParams(array(
			'player_bank_account_name'=>'',
			'player_bank_account_number' => '',
			'collection_bank_account_name' => '',
			'by_date_from'=>'',
			'by_date_to'=>'',
			'order_secure_id'=>'',
			'withdrawal_order_id'=>'',

		));

		$this->template->write_view('sidebar', 'payment_management/sidebar');
		$this->template->write_view('main_content', 'payment_management/exception_order_list', $data);
		$this->template->render();
	}

	public function lockedWithdrawalList() {
		if(!$this->permissions->checkPermissions('lock_withdrawal_list'))
			return $this->error_access();

		$this->loadTemplate(lang('Locked Withdrawal List'), '', '', 'payment');
		$data['defaultSortColumn'] = $this->utils->getDefaultSortColumn('locked_withdrawal_list');

		$this->template->write_view('sidebar', 'payment_management/sidebar');
		$this->template->write_view('main_content', 'payment_management/locked_withdrawal_list',$data);
		$this->template->render();
	}

	public function lockedDepositList() {
		if(!$this->permissions->checkPermissions('lock_deposit_list'))
			return $this->error_access();

		$this->loadTemplate(lang('Locked Deposit List'), '', '', 'payment');

		$data['defaultSortColumn'] = $this->utils->getDefaultSortColumn('locked_deposit_list');

		$this->template->write_view('sidebar', 'payment_management/sidebar');
		$this->template->write_view('main_content', 'payment_management/locked_deposit_list', $data);
		$this->template->render();
	}

	public function unusualNotificationRequestsList() {
		if(!$this->permissions->checkPermissions('unusual_notification_requests_list'))
			return $this->error_access();

		$this->loadTemplate(lang('Unusual Notification Requests'), '', '', 'payment');

		if (($this->session->userdata('sidebar_status') == NULL)) {
			$this->session->set_userdata(array('sidebar_status' => 'active'));
		}

		$data=['title'=>lang('Unusual Notification Requests')];
		$data['conditions'] = $this->safeLoadParams(array(
			'data_transaction_id'=>'',
			'by_date_from'=>'',
			'by_date_to'=>'',
		));
		$this->template->write_view('sidebar', 'payment_management/sidebar');
		$this->template->write_view('main_content', 'payment_management/unusual_notification_requests_list', $data);
		$this->template->render();
	}

	public function batchDeposit() {
		if ( ! $this->permissions->checkPermissions('batch_deposit') ) {
			return $this->error_access();
		}

		$this->load->model('payment_account');

		$data['account_list'] = $this->payment_account->getAllPaymentAccountDetails(array(
			'banktype.bankName' => 'asc',
			'payment_account.payment_account_name' => 'asc',
		));

		$this->session->set_userdata('prevent_refresh', true);

		$this->loadTemplate(lang('Batch Deposit'), '', '', 'payment');
		$this->template->write_view('sidebar', 'payment_management/sidebar');
		$this->template->write_view('main_content', 'payment_management/batch_deposit', $data);
		$this->template->render();
	}

	public function postBatchDeposit(){

		if ($this->session->userdata('prevent_refresh') == null) {
			redirect('/payment_management/batchDeposit');
		}

		$this->load->model(array('payment_account', 'sale_orders_notes', 'sale_orders_timelog'));

		$csv = file_get_contents($_FILES['usernames']['tmp_name']);

		$row = explode("\n", $csv);
		$row = array_filter($row);
		$row = array_unique($row);

		$adminUserId=$this->authentication->getUserId();

		$payment_account_id = $this->input->post('account');
		$paymentAccount = $this->payment_account->getPaymentAccount($payment_account_id);
		$systemId = $paymentAccount->external_system_id;
		$date = $this->input->post('date');
		$internal_note = $this->input->post('internal_note');
		$external_note = $this->input->post('external_note');
		$paymentKind = Sale_order::PAYMENT_KIND_DEPOSIT;
		$currency = $this->config->item('default_currency');
		$status = $this->input->post('status');

		$show_reason = false;

		$result = array('failed_count' => 0, 'success' => 0, 'failed' => []);
		$result['total_count'] = count($row);
		$success_users = [];

		foreach ($row as $key => $value) {

			$row_val = explode(',', $value);

			$username = trim($row_val[0]);
			$playerId = $this->player_model->getPlayerIdByUsername($username);

			if(empty($row_val[1])){
				$amount = 0;
			}else{
				$amount = trim($row_val[1]);
			}

			if ($playerId) {

				$actionlogNotes = sprintf("Manual Batch Deposit %s to %s", $this->utils->formatCurrencyNoSym($amount), $username);

				$success = false;
				$this->payment_account->startTrans();

				$lockedKey=null;
				$locked = $this->lockPlayerBalanceResource($playerId, $lockedKey);
				try {
					if ($locked) {

						$saleOrderId = $this->sale_order->createSaleOrder($systemId, $playerId, $amount, $paymentKind,
							Sale_order::STATUS_PROCESSING, null, null, $currency, $payment_account_id, $date, null,
							null);
						$this->player_model->incTotalDepositCount($playerId);
						$success = !empty($saleOrderId);

						if($success){
							$this->sale_orders_notes->add($actionlogNotes, Users::SUPER_ADMIN_ID, Sale_orders_notes::ACTION_LOG, $saleOrderId);
							$this->sale_orders_timelog->add($saleOrderId, Sale_orders_timelog::ADMIN_USER, $adminUserId, array('before_status' => Sale_order::STATUS_PROCESSING, 'after_status' => null));
							if (!empty($internal_note)) {
								$this->sale_orders_notes->add($internal_note, $adminUserId, Sale_orders_notes::INTERNAL_NOTE, $saleOrderId);
							}
							if (!empty($external_note)) {
								$this->sale_orders_notes->add($external_note, $adminUserId, Sale_orders_notes::EXTERNAL_NOTE, $saleOrderId);
							}
						}

						if (empty($status)) {
							$status == Sale_order::STATUS_PROCESSING;
						}

						if ($status == Sale_order::STATUS_PROCESSING) {
							$batchDepositActionlogPending = sprintf("Batch Deposit is success processing, status => %s", $status);
							$this->sale_orders_notes->add($batchDepositActionlogPending, Users::SUPER_ADMIN_ID, Sale_orders_notes::ACTION_LOG, $saleOrderId);

						} else if ($status == Sale_order::STATUS_SETTLED) {
							$batchDepositActionlogApproved = sprintf("Batch Deposit is success settled, status => %s", $status);
							$this->sale_order->approveSaleOrder($saleOrderId, $batchDepositActionlogApproved, $show_reason);

						}

					}
				} finally {

					$rlt = $this->releasePlayerBalanceResource($playerId, $lockedKey);

				}

				$success = $this->payment_account->endTransWithSucc() && $success;

				if($success){
					$this->triggerDepositEvent($playerId, $saleOrderId, null, null, null, $adminUserId);
				}

				if ($success) {

					$success_users[] = $username;

				}else{

					$result['failed'][$message][] = $username;
					@$result['failed_count']++;

				}

			} else {
				$result['failed']['User does not exist'][] = $username;
				@$result['failed_count']++;
			}
		}

		$result['success_users'] = $success_users;

		$this->session->unset_userdata('prevent_refresh');

		$this->loadTemplate(lang('Batch Deposit'), '', '', 'payment');
		$this->template->write_view('sidebar', 'payment_management/sidebar');
		$this->template->write_view('main_content', 'payment_management/batch_deposit_result', $result);
		$this->template->render();
	}

	public function checkSubwallectBalance($playerId){
		$this->load->model(array('wallet_model'));
		$check_result = false;
		$message = '';
		$negativeBalanceDetailList = [];
		if($this->utils->getConfig('check_sub_wallect_balance_in_withdrawal')){
			$balanceDetails = $this->wallet_model->getBalanceDetails($playerId);
			if(is_array($balanceDetails) && !empty($balanceDetails)){
				foreach ($balanceDetails['sub_wallet'] as $subWallet) {
					if($subWallet['totalBalanceAmount'] < 0){
						// extract negative balance detail into the list
						$negativeBalanceDetail = [];
						$negativeBalanceDetail['game'] = $subWallet['game'];
						$negativeBalanceDetail['totalBalanceAmount'] = $subWallet['totalBalanceAmount'];
						$negativeBalanceDetailList[] = $negativeBalanceDetail;

						$negativeBalanceDetail['totalBalanceAmount'] = $subWallet['totalBalanceAmount'];
						$message = sprintf(lang('This player has negative balance'),$subWallet['game'],$subWallet['totalBalanceAmount']);
						$check_result = true;
					}
				}
			}
		}else{
			$check_result = false; // ignore the player who has negative balance
		}

		// /// to test for The player has negative balance.
		// $check_result = true;
		// $message = 'This player has negative balance';
		// $negativeBalanceDetail = [];
		// $negativeBalanceDetail['game'] = 'system_code1';
		// $negativeBalanceDetail['totalBalanceAmount'] = '-123';
		// $negativeBalanceDetailList[] = $negativeBalanceDetail;
		// $negativeBalanceDetail = [];
		// $negativeBalanceDetail['game'] = 'system_code2';
		// $negativeBalanceDetail['totalBalanceAmount'] = '-345';
		// $negativeBalanceDetailList[] = $negativeBalanceDetail;

		return $this->returnJsonResult(['message' => $message, 'check_result'=>$check_result, 'negative_balance_detail_list' => $negativeBalanceDetailList]);
	}

	public function userLockWithdrawal($walletAccountId) {
		$this->load->model(array('wallet_model', 'users'));

		$adminUserId = $this->authentication->getUserId();
		$controller = $this;
		$message = '';
		$lock_result=false;

		$lock_withdrawal=$this->lockAndTransForWithdrawLock($walletAccountId, function() use (
			$controller, $walletAccountId, $adminUserId, &$message, &$lock_result) {

			$lockedByUserId = $this->wallet_model->checkWithdrawLocked($walletAccountId);

			if($lockedByUserId) {
				//locked by self and other
				if($adminUserId != $lockedByUserId) {
					$adminUsername = $this->users->getUsernameById($lockedByUserId);  // transaction is locked by this user
					$message = lang('Withdrawal transaction was already locked by').' '.'<b>'.$adminUsername.'</b>';
				}else{
					//locked by self
					$lock_result=true;
				}
			} else {
				$lock_result=$this->wallet_model->userLockWithdrawal($walletAccountId, $adminUserId);
			}

			return true;
		});

		if(!$lock_withdrawal){
			$message=lang('Lock failed');
			$lock_result=false;
		}

		$this->returnJsonResult(['message' => $message, 'lock_result'=>$lock_result]);
	}

	public function userLockDeposit($salesOrderId) {
		$this->load->model(array('sale_order', 'users'));
		$adminUserId = $this->authentication->getUserId();
		$controller = $this;
		$message = '';
		$lock_result=false;

		$this->lockAndTransForDepositLock($salesOrderId, function() use (
				$controller, $salesOrderId, $adminUserId, &$message, &$lock_result) {
			$lockedByUserId = $this->sale_order->checkDepositLocked($salesOrderId);

			if($lockedByUserId) {
				if($adminUserId != $lockedByUserId) {
					$adminUsername = $this->users->getUsernameById($lockedByUserId);  // transaction is locked by this user
					$message = lang('Deposit transaction was already locked by').' '.'<b>'.$adminUsername.'</b>';
				}else{
					//locked by self
					$lock_result=true;
				}
			} else {
				$lock_result=$this->sale_order->userLockDeposit($salesOrderId, $adminUserId);
			}
			return true;
		});
		$this->returnJsonResult(['message' => $message, 'lock_result'=>$lock_result]);
	}

	public function unlockDepositTransaction() {
		$this->load->model(array('sale_order'));
		$controller = $this;
		$salesOrderId = $this->input->post('salesOrderId');

		$this->lockAndTransForDepositLock($salesOrderId, function() use ($controller, $salesOrderId) {
			$controller->sale_order->userUnlockDeposit($salesOrderId);
			return true;
		});
	}

	public function unlockWithdrawTransaction() {
		$this->load->model(array('wallet_model'));
		$controller = $this;
		$walletAccountId = $this->input->post('walletAccountId');

		$this->lockAndTransForDepositLock($walletAccountId, function() use ($controller, $walletAccountId) {
			$controller->wallet_model->userUnlockWithdrawal($walletAccountId);
			return true;
		});
	}

	public function batchUnlockTransaction() {
		$this->load->model(array('sale_order'));
		$this->sale_order->batchUnlockTransactions($this->input->post('saleOrdersId'));
	}

	public function batchUnlockWithdrawTransaction() {
		$this->load->model(array('wallet_model'));
		$this->wallet_model->batchUnlockWithdrawTransaction($this->input->post('walletAccountId'));
	}

	public function newInternalWithdrawal() {
		if(!$this->permissions->checkPermissions('new_internal_withdrawal'))
			return $this->error_access();

		$this->load->library('form_validation');
		$this->load->model(array('payment_account', 'transactions'));

		$this->form_validation->set_rules('amount', lang('Amount'), 'trim|xss_clean|required');
		$this->form_validation->set_rules('account', lang('Account'), 'trim|xss_clean|required');

		if ($this->form_validation->run()) {

			$userId = $this->authentication->getUserId();
			$paymentAccount = $this->payment_account->getPaymentAccount($this->input->post('account'));
			$amount = $this->input->post('amount');
			//create transaction log
			$success = $this->transactions->createTransactionInternalWithdrawal($amount, $userId);

			if ($success) {
				$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang('New Internal Withdrawal Successfully Added'));
			} else {
				$this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('error.default.db.message'));
			}
		}
		$data['account_list'] = $this->payment_account->getAllPaymentAccountInfo();
		$this->loadTemplate(lang('New Internal Withdrawal'), '', '', 'payment');
		$this->template->write_view('sidebar', 'payment_management/sidebar');
		$this->template->write_view('main_content', 'payment_management/newInternalWithdrawal', $data);
		$this->template->render();
	}

	public function uploadBankIcon() {
		$image = isset($_FILES['filBankIcon']) ? $_FILES['filBankIcon'] : null;

		if(empty($image['name'][0])) {
			return array('status' => 'success');
		}

		$fullpath = $this->utils->getBanIconPath();

		$config = array(
            'allowed_types' => "jpg|jpeg|png|gif",
            'max_size'      => $this->utils->getMaxUploadSizeByte(),
            'overwrite'     => true,
            'remove_spaces' => true,
            'upload_path'   => $fullpath,
        );

		if (!empty($image)) {
	        $this->load->library('multiple_image_uploader');
			$response = $this->multiple_image_uploader->do_multiple_uploads($image, $fullpath, $config);


			if (strtolower($response['status']) == "success") {
				return array('status' => 'success', 'fileName' => $response['filename'][0]);
			}

			if(strtolower($response['status']) == "fail"){
				return array('status' => 'error', 'msg' => $response['message']);
			}
		}

		return false;
	}

    public function delete_deposit_attachment(){
		$this->load->model(array('player_attached_proof_file_model'));
		$playerId = $this->input->post('playerId');
		$tag = $this->input->post('tag');
		$data = [
			'picId' => $this->input->post('picId'),
			'playerId' => $playerId,
		];

		$response = $this->player_attached_proof_file_model->remove_proof_document($data);

		if(!empty($response)){
			$this->alertMessage($response['msg_type'], $response['msg']);
			if($response['status'] == "success"){
				if(!empty($tag)){
						$action = lang('role.238');
						$description = lang('role.238') . ' - '. lang('Image successfully deleted!') . ' - ' .lang('Image Document').' '.lang('upload_tag_'.$tag);

						$playerInfo = $this->player_model->getPlayerDetailsTagsById($playerId);
						if(!empty($playerInfo)){
							$this->savePlayerUpdateLog($playerId, $description, $this->authentication->getUsername());
						}
				}
			}

			if ($this->input->is_ajax_request()) {
				$this->returnJsonResult($response);
				return;
			}
		}
	}

	/**
	 * overview : save log on player update history
	 *
	 * @param int $player_id	player_id
	 * @param $changes
	 * @param $updatedBy
	 */
	public function savePlayerUpdateLog($player_id, $changes, $updatedBy) {
		$data = array(
			'playerId' => $player_id,
			'changes' => $changes,
			'createdOn' => date('Y-m-d H:i:s'),
			'operator' => $updatedBy,
		);
		$this->player_manager->addPlayerInfoUpdates($player_id, $data);
	}

	public function batchRecoverWithdrawCondition() {
		if (!$this->permissions->checkPermissions('recover_withdraw_condition')) {
			$this->error_access();
			return;
		}

		$ids = $this->input->post('withdraw_condition_ids');
		# check if post data is array or not empty
		if (empty($ids) || !is_array($ids)) {
			return;
		}

		# check valid ids
		foreach ($ids as $key => $value) {
			$intValue = intval($value);

			if (empty($intValue)) {
				unset($ids[$key]);
			} else {
				$ids[$key] = $intValue;
			}
		}

		# final check if validated ids is not empty or array
		if (empty($ids) || !is_array($ids)) {
			return;
		}

		$this->load->model(array('withdraw_condition'));
		$this->withdraw_condition->updateStatus($ids, BaseModel::STATUS_NORMAL);
	}

	/**
	 * Displays images of proof slips based on player ID and sales order ID
	 *
	 * @param  string $player_id      Player ID
	 * @param  string $sales_order_id Sales order ID
	 * @return view                   Display
	 */
	public function show_proof_slip($player_id,$sales_order_id, $tag = 'deposit') {
		if (!$this->permissions->checkPermissions('kyc_attached_documents') || !$this->utils->isEnabledFeature('enable_deposit_upload_documents')) {
			$data['permission_verified'] = lang('con.plm01');
			$this->load->view('payment_management/proof_slip_attachments', $data);
		} else {
			$this->load->model(array('player_attached_proof_file_model','player_kyc'));
			$data['playerId'] = $player_id;
			$data['attachment_info'] = $this->player_attached_proof_file_model->getAttachementRecordInfo($player_id,null,'deposit',null,null,$sales_order_id,null,false);
			$this->load->view('payment_management/proof_slip_attachments', $data);
		}
	}

	/**
	 * withdrawal details
	 * @param  string $mode            request/approved/declined
	 * @param  string $walletAccountId
	 * @param  string $dwStatus
	 *
	 */
	public function withdrawal_details($walletAccountId) {

		if (!$this->permissions->checkPermissions(['payment_withdrawal_list'])) {
			$this->utils->error_log('permission failed', 'approve_decline_withdraw , payment_withdrawal_list');
			return $this->error_access();
		}
		$this->load->model(['external_system', 'operatorglobalsettings', 'common_category']);

		$row=$this->wallet_model->getWalletAccountById($walletAccountId);
		$cryptoWithdrawalOrder=$this->wallet_model->getCryptoWithdrawalOrderById($walletAccountId);
		if(empty($walletAccountId) || empty($row)){
			return $this->error_access();
		}
		$this->utils->debug_log('query from '.$walletAccountId, $row);
		$dwStatus=$row['dwStatus'];

		$modalMode='request';
		$modalFlag=Wallet_model::WITHDRAWAL_REQUEST_MODAL;
		if ($row['dwStatus'] == Wallet_model::APPROVED_STATUS || $row['dwStatus'] == Wallet_model::PAY_PROC_STATUS) {
			$modalMode='approved';
			$modalFlag=Wallet_model::WITHDRAWAL_APPROVED_MODAL;
		} else if ($row['dwStatus'] == Wallet_model::DECLINED_STATUS || $row['dwStatus'] == Wallet_model::PAID_STATUS) {
			# Decline and Paid use a same modal
			$modalMode='declined';
			$modalFlag=Wallet_model::WITHDRAWAL_DECLINED_MODAL;
		} else {
			// Determines the target modal to be showed
			$customWithdrawalSetting = $this->operatorglobalsettings->getCustomWithdrawalProcessingStage();
			$useApprovedModal = false;

			if ($customWithdrawalSetting['maxCSIndex'] == -1) {
				$useApprovedModal = true;
			} else if (substr($row['dwStatus'], 0, 2) == 'CS') {
				$currentCSIndex = intval(substr($row['dwStatus'], 2));
				$useApprovedModal = $currentCSIndex >= $customWithdrawalSetting['maxCSIndex'];
			}

			if ($useApprovedModal) {
				$modalMode='request';
				$modalFlag=Wallet_model::WITHDRAWAL_APPROVED_MODAL;
			} else {
				$modalMode='request';
				$modalFlag=Wallet_model::WITHDRAWAL_REQUEST_MODAL;
			}
		}

		$data=[];
		$data['game_platforms'] = $this->external_system->getAllActiveSytemGameApi();
		$data['checking_withdrawal_locking'] = $this->utils->getConfig('checking_withdrawal_locking');
		$data['withdrawAPIs'] = $this->external_system->getWithdrawPaymentSystemsKV();
		$data['setting'] = $this->operatorglobalsettings->getCustomWithdrawalProcessingStage();
		$customStageCount = 0;
		for ($i = 0; $i < count($data['setting']); $i++) {
			if (array_key_exists($i, $data['setting'])) {
				$customStageCount += ($data['setting'][$i]['enabled'] ? 1 : 0);
			}
		}
		$data['customStageCount'] = $customStageCount;
		$data['canManagePaymentStatus'] = true;

		if ($this->utils->isEnabledFeature('enable_withdrawal_declined_category') ){
			$data['withdrawalDeclinedCategory'] = $this->common_category->getActiveCategoryByType(common_category::CATEGORY_WITHRAWAL_DECLINED);
		}

		#get all search select status ,view stage permission ,view detail button permission
		$status_and_permission = $this->payment_library->getWithdrawalAllStatusPermission($data['setting'],$customStageCount);

		$data['searchStatus'] = json_encode($status_and_permission);
		$data['promoRulesConfig'] = $this->utils->getConfig('promotion_rules');
        $data['enabled_show_withdraw_condition_detail_betting'] = $data['promoRulesConfig']['enabled_show_withdraw_condition_detail_betting'];
		$data['conditions'] = ['dwStatus'=>$dwStatus];
		$data['walletAccountId']=$walletAccountId;
		$data['playerId']=$row['playerId'];
		$data['modalFlag']=$modalFlag;
		$data['modalMode']=$modalMode;
		$data['verifiedBankFlag']=$row['verifiedBankFlag'];
		$data['enabled_bank_info_verified_flag_in_withdrawal_details']=$this->utils->getConfig('enabled_bank_info_verified_flag_in_withdrawal_details');
		$data['transfered_crypto']=$cryptoWithdrawalOrder['transfered_crypto'];

		$this->loadTemplate(lang('pay.withdetl'). ' - ' . $row['transactionCode'], '', '', 'payment');
		$this->template->add_css('resources/css/dashboard.css');
		$this->template->add_js('resources/third_party/easytimer/easytimer.min.js');
		$this->template->add_js('resources/player/jquery.ba-postmessage.min.js');
		$this->template->write_view('sidebar', 'payment_management/sidebar');
		$this->template->write_view('main_content', 'payment_management/view_withdrawal_details', $data);
		$this->template->render();
	}

	/*
     * Show daily currency
     *
     * @return view
     */
    public function show_daily_currency() {
        $this->load->model('daily_currency');
        $data['daily_currency'] = $this->daily_currency->getDailyCurrency();
        $this->template->write_view('sidebar', 'payment_management/sidebar');
		$this->template->write_view('main_content', 'payment_management/daily_currency_list', $data);
		$this->template->render();
    }

    /**
     * set_bank_info_verified_flag
     * @param string $walletAccountId
     * @param string $verifiedFlag 'true' or 'false'
     */
	public function set_bank_info_verified_flag($walletAccountId, $verifiedFlag) {

        $result=['success'=>false];
		if (!$this->permissions->checkPermissions(['payment_withdrawal_list'])) {
			$result['error']=lang('Sorry, no permission');
			return $this->returnJsonResult($result);
		}
		if(empty($walletAccountId) || empty($verifiedFlag)){
			$result['error']=lang('Bad Request');
			return $this->returnJsonResult($result);
		}
		$this->load->model(['external_system', 'wallet_model']);
		$verifiedFlag=$verifiedFlag=='true';
		$walletAccountId=intval($walletAccountId);
		$result['success']=$this->wallet_model->setBankInfoVerifiedFlagInWalletAccount($walletAccountId, $verifiedFlag);
		if(!$result['success']){
			$result['error']=lang('Set bank info flag failed');
		}
		return $this->returnJsonResult($result);
	}

	    /**
	 * detail: view view_withdrawal_abnormal
	 *
	 * @return load template
	 */
	public function view_withdrawal_abnormal() {
		if (!$this->permissions->checkPermissions('view_withdrawal_abnormal') || !$this->config->item('enabled_withdrawal_abnormal_notification')) {
			return $this->error_access();
		}

		$this->load->model(array('payment_abnormal_notification', 'users'));

		$firstDayOfMon = $this->utils->getFirstDateOfCurrentMonth();
		$todayDate = $this->utils->getTodayForMysql();
		$data['conditions'] = $this->safeLoadParams(array(
			'by_date_from' => $firstDayOfMon. ' 00:00:00',
			'by_date_to' => $todayDate. ' 23:59:59',
			'username' =>'',
			'by_type' => '',
			'by_status' => '2',
			'update_by' => '',
		));

		$data['status_list'] = array(
			'' => lang('sys.vu05'),
			Payment_abnormal_notification::ABNORMAL_READ => lang('cs.abnormal.payment.read'),
			Payment_abnormal_notification::ABNORMAL_UNREAD => lang('cs.abnormal.payment.unread'),
		);

		$data['user_group'] = $this->users->getAllAdminUsers();

		$data['export_report_permission'] = $this->permissions->checkPermissions('export_player_report');

		$this->loadTemplate(lang('Excess Withdrawal Requests'), '', '', 'payment');
		$this->template->add_css('resources/css/dashboard.css');
		$this->template->add_js('resources/js/payment_management/withdrawalRiskResults.js');
		$this->template->add_js('resources/js/ace/ace.js');
		$this->template->add_js('resources/js/ace/mode-json.js');
		$this->template->add_js('resources/js/ace/theme-tomorrow.js');
		$this->template->write_view('sidebar', 'payment_management/sidebar');
		$this->template->write_view('main_content', 'payment_management/view_withdrawal_abnormal_report', $data);
		$this->template->render();
	}

	/**
     * set Abnormal payment to read
     *
     * @param
     * @return redirect
     */
    public function setWithdrawalStatusToRead($status = '1') {
        if(!$this->permissions->checkPermissions('adjust_withdrawal_abnormal')){
            return $this->error_access();
        }
        $this->load->library(['player_message_library']);
        $this->load->model(array('payment_abnormal_notification', 'users'));

		$adminUserId = $this->authentication->getUserId();
        $abnormalOrderId = $this->input->post('abnormalOrder');
        $success = false;
        $this->utils->debug_log('---------abnormalOrderId', $abnormalOrderId);

        if (!empty($abnormalOrderId)) {
			$this->startTrans();

	        // foreach ($abnormalOrder as $abnormalOrderId) {
				$this->payment_abnormal_notification->updatePaymentAbnormalStatus($adminUserId,$status,$abnormalOrderId);
			// }
			$this->saveAction(self::ACTION_LOG_TITLE, 'Update Payment Abnormal Status', "User " . $this->authentication->getUsername() . " has update payment abnormal id [" . $abnormalOrderId . "]");

			$success = $this->endTransWithSucc();

			$this->utils->debug_log('---------setAbnormalStatusToRead', $success);

			if ($success) {
	            $message = lang('cs.abnormal.payment.update.succ');
	            $this->returnJsonResult(array('success' => $success, 'message' => $message));
				return;
	        } else {
	            $message = lang('cs.abnormal.payment.update.err');
	            $this->returnJsonResult(array('success' => $success, 'message' => $message));
				return;
	        }

		} else {
			$message = lang('cs.abnormal.payment.selected.empty');
            $this->returnJsonResult(array('success' => $success, 'message' => $message));
			return;
        }
    }

    public function upload_attached_document($file , $reference_id , $playerId, $return_origin_msg= false){
        if(!empty($file) && !empty($reference_id) && !empty($playerId)){
            $this->load->model(array('player_attached_proof_file_model'));

            $input = array(
                "player_id"       => $playerId,
                "tag"             => player_attached_proof_file_model::Deposit_Attached_Document,
                "sales_order_id"  => $reference_id,
            );

            $data = [
                'input' => $input,
                'image' => $file
            ];

            $response = $this->player_attached_proof_file_model->upload_deposit_receipt($data);

            if($return_origin_msg) return $response;
            return $this->returnJsonResult($response);
        }
    }
}

/* End of file payment_management.php */
/* Location: ./application/controllers/payment_management.php */
