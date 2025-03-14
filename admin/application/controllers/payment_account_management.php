<?php

require_once dirname(__FILE__) . '/BaseController.php';

/**
 * Payment Account Management
 *
 * General behaviors include
 * * add/update collection accounts
 * * able to activate/deactivate the status of a certain collection account
 * * able to upload QR Code Image for a certain collection account
 * * able to upload Bank/Payment Logo for a certain collection account
 * * able to Export Report for the entire lists or filtered lists.
 * * able to wired to the link of "Default Collection Account" page.
 *
 *
 * @category payment_account_management
 * @version 1.8.10
 * @copyright 2013-2022 tot
 *
 */
class Payment_account_management extends BaseController {

	function __construct() {
		parent::__construct();
		$this->load->helper(array('date_helper', 'url'));
		$this->load->model(array('payment_account', 'group_level', 'banktype'));
		$this->load->library(array('authentication', 'permissions', 'excel', 'form_validation', 'template', 'pagination'));

		$this->permissions->checkSettings();
		$this->permissions->setPermissions(); //will set the permission for the logged in user
	}

	const MANAGEMENT_NAME = 'Payment Account Management';
	const HOME_URI = 'payment_account_management/view_payment_account';

	/**
	 * Loads template for view based on regions in
	 * config > template.php
	 *
	 */
	private function loadTemplate($title, $description, $keywords, $activenav) {
		$this->template->add_css('resources/css/payment_account_management/style.css');
		$this->template->add_js('resources/js/jquery.numeric.min.js');

		$this->template->add_js('resources/js/chosen.jquery.min.js');
		$this->template->add_js('resources/js/summernote.min.js');
		// $this->template->add_js('resources/js/dataTables.responsive.min.js');
		$this->template->add_js('resources/js/datatables.min.js');

		$this->template->add_css('resources/css/general/style.css');
		$this->template->add_css('resources/css/datatables.min.css');
		$this->template->add_css('resources/css/chosen.min.css');
		$this->template->add_css('resources/css/summernote.css');

		$this->template->write('title', $title);
		$this->template->write('description', $description);
		$this->template->write('keywords', $keywords);
		$this->template->write('activenav', $activenav);
		$this->template->write('username', $this->authentication->getUsername());
		$this->template->write('userId', $this->authentication->getUserId());
	}

	/**
	 * Shows Error message if user can't access the page
	 *
	 * @return	rendered Template
	 */
	private function error_access() {
		$this->loadTemplate(lang('pay.payment_account'), '', '', 'payment');
		$systemUrl = $this->utils->activeSystemSidebar();
		$data['redirect'] = $systemUrl;

		$message = lang('con.bnk01');
		$this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);

		$this->template->write_view('main_content', 'error_page', $data);
		$this->template->render();
	}

	/**
	 * Index Page of Payment Management
	 *
	 *
	 * @return	void
	 */
	public function index() {
		redirect(self::HOME_URI, 'refresh');
	}

	/**
	 * view bank account manager
	 *
	 * @return Array
	 */

	public function list_payment_account() {
		if (!$this->permissions->checkPermissions('collection_account')) {
			$this->error_access();
		} else {

			$this->load->model(array('external_system','affiliatemodel','transactions','promorules','agency_model'));

			if (($this->session->userdata('sidebar_status') == NULL)) {
				$this->session->set_userdata(array('sidebar_status' => 'active'));
			}

			$condtion = [];
			if($this->config->item('only_select_active_accounts_for_collection_account_pages')){
				$condtion['status'] = Payment_account::STATUS_ACTIVE;
			}

            $data['banks'] 					= $this->payment_account->getPaymentAccountList("payment_type", $condtion);
			$data['form'] 					= &$form;
			$data['playerGroup'] 			= $this->group_level->getPlayerGroup();
			$data['payment_types'] 			= $this->banktype->getBankTypeKV();
			$data['payment_account_flags'] 	= $this->utils->insertEmptyToHeader($this->utils->getPaymentAccountAllFlagsKV(), '', lang('select.empty.line'));
			$data['second_category_flags'] 	= $this->utils->insertEmptyToHeader($this->utils->getPaymentAccountSecondCategoryAllFlagsKV(), '', lang('select.empty.line'));
			$data['payment_type_list'] 		= $this->banktype->getList();

			# ALLOWED LIST
			$data['levels'] 				= array_column($this->group_level->getAllPlayerLevelsForSelect(), 'groupLevelName', 'vipsettingcashbackruleId');
			$data['affiliates'] 			= array_column($this->affiliatemodel->getAllActivtedAffiliates(false, true), 'username', 'affiliateId');
            $active_agent                   = $this->agency_model->get_active_agents(false, true);
			$data['agents'] 				= is_array($active_agent) ? array_column($active_agent, 'agent_name', 'agent_id') : null ;

			if($this->utils->isEnabledFeature('only_manually_add_active_promotion')){
				$promoCms = $this->promorules->getAvailablePromoCMSList();
			}else{
				$promoCms = $this->promorules->getAllPromoCMSList();
			}
            $data['promoCms'] = $promoCms;

			//permission checking
			($this->permissions->checkPermissions('delete_collection_account')) ? $data['delete_collection_account'] = true :"";
			($this->permissions->checkPermissions('export_report')) ? $data['export_report_permission'] = true :"";

			$tly_actived=$this->external_system->isGameApiActive(TLY_PAYMENT_API);
			$data['tly_actived']=$tly_actived;
            $data['random'] = uniqid();

			$this->loadTemplate(lang('pay.payment_account'), '', '', 'system');
			$this->template->add_js('resources/js/select2.min.js');
			$this->template->add_css('resources/css/select2.min.css');
			$this->template->write_view('sidebar', 'system_management/sidebar');
			$this->template->write_view('main_content', 'payment_management/payment_account/view_payment_account', $data);
			$this->template->render();
		}
	}

	/**
	 * list_payment_account_lite for output html by paging.
	 *
	 * @param integer $offset The offset by paging
	 * @param integer $limit The limit as per page.
	 * @return void
	 */
	public function list_payment_account_lite($offset = 0,$limit = 25) {
		$enabled_display_inactived_collection_account_page = $this->config->item('enabled_display_inactived_collection_account_page');
		if (!$this->permissions->checkPermissions('collection_account') || !$enabled_display_inactived_collection_account_page) {
			$this->error_access();
		} else {

			$this->load->model(array('external_system','affiliatemodel','transactions','promorules','agency_model'));

			if (($this->session->userdata('sidebar_status') == NULL)) {
				$this->session->set_userdata(array('sidebar_status' => 'active'));
			}

			// for paging
			$total_count_without_limit = '';

			$only_select_accounts_status = Payment_account::STATUS_INACTIVE;

			$data['conditions'] = $this->safeLoadParams(array(
				'payment_account_name' => '',
				'payment_account_number' => '',
			));

			$where = $this->input->post();

			// var_dump($where);

			$sort 							= "payment_type";
			$rows 							= $this->payment_account->getAllPaymentAccountDetails($sort, $limit , $offset, $only_select_accounts_status, $total_count_without_limit, $where);
			$data['form'] 					= &$form;
			$data['playerGroup'] 			= $this->group_level->getPlayerGroup();
			$data['payment_types'] 			= $this->banktype->getBankTypeKV();
			$data['payment_account_flags'] 	= $this->utils->insertEmptyToHeader($this->utils->getPaymentAccountAllFlagsKV(), '', lang('select.empty.line'));
			$data['second_category_flags'] 	= $this->utils->insertEmptyToHeader($this->utils->getPaymentAccountSecondCategoryAllFlagsKV(), '', lang('select.empty.line'));
			$data['payment_type_list'] 		= $this->banktype->getList();
			$data['banks'] 					= $rows;

			# ALLOWED LIST
			$data['levels'] 				= array_column($this->group_level->getAllPlayerLevelsForSelect(), 'groupLevelName', 'vipsettingcashbackruleId');
			$data['affiliates'] 			= array_column($this->affiliatemodel->getAllActivtedAffiliates(false, true), 'username', 'affiliateId');
            $active_agent                   = $this->agency_model->get_active_agents(false, true);
			$data['agents'] 				= is_array($active_agent) ? array_column($active_agent, 'agent_name', 'agent_id') : null ;

			if($this->utils->isEnabledFeature('only_manually_add_active_promotion')){
				$promoCms = $this->promorules->getAvailablePromoCMSList();
			}else{
				$promoCms = $this->promorules->getAllPromoCMSList();
			}


            $data['promoCms'] = $promoCms;

			//permission checking
			($this->permissions->checkPermissions('delete_collection_account') && false) ? $data['delete_collection_account'] = true :""; // disable for lite
			($this->permissions->checkPermissions('export_report')) ? $data['export_report_permission'] = true :"";

			$tly_actived=$this->external_system->isGameApiActive(TLY_PAYMENT_API);
			$data['tly_actived']=$tly_actived;
			/// -----


			$paging = [];

			$paging['page_list'] = [];
			$total_page_count = ceil($total_count_without_limit / $limit);
			$lite_uri = site_url('/payment_account_management/list_payment_account_lite/%s/%s');// offset, limit

			for($i = 0;$i < $total_page_count;$i++){
				$aPageInfo['number'] = $i+1;

				$aPageInfo['limit'] = $limit;
				$aPageInfo['offset'] = $limit* $i;

				$aPageInfo['is_curr'] = false;
				if($aPageInfo['offset'] == $offset){
					$aPageInfo['is_curr'] = true;
				}

				$aPageInfo['uri'] = sprintf($lite_uri, $aPageInfo['offset'], $aPageInfo['limit']);
				$paging['page_list'][$i]  = $aPageInfo;
			}
			$paging['from'] = $offset+1;
			$paging_to = $paging['from']+ $limit- 1;
			$paging['to'] = ($paging_to < $total_count_without_limit)? $paging_to: $total_count_without_limit;
			$paging['total'] = $total_count_without_limit;
			$paging['limit'] = $limit; // for payment_account_table_length
			$paging['curr_uri'] = sprintf($lite_uri, $offset, $limit);
			$paging['curr_uri_urlencoded'] = urlencode($paging['curr_uri']);

			$data['paging'] = $paging;

			$this->loadTemplate(lang('pay.payment_account'), '', '', 'system');
			$this->template->add_js('resources/js/select2.min.js');
			$this->template->add_css('resources/css/select2.min.css');
			$this->template->write_view('sidebar', 'system_management/sidebar');
			$this->template->write_view('main_content', 'payment_management/payment_account/view_payment_account_lite', $data);
			$this->template->render();

		}
	}

	# SELECT 2
	public function players() {
		$this->load->model('player_model');

		$q 		= $this->input->get('q');
		$page 	= $this->input->get('page');


		$players = $this->player_model->getAvailablePlayers($q);
		if(!empty($players)){
			array_walk($players, function(&$player) {
				$player = array(
					'id' 	=> $player->playerId,
					'text' 	=> $player->username,
				);
			});
		}

		$data = array(
			'items' => $players,
		);

		$this->returnJsonResult($data);
	}
	# SELECT 2
	public function batch_players() {
		$response = [];
		$uploadFieldName = 'batch_player_csv';
		$filepath='';
		$msg='';
		if($this->existsUploadField($uploadFieldName)){
			//check file type
			if($this->saveUploadFileToRemote($uploadFieldName, ['csv'], $filepath, $msg)){
				//get $filepath
				//echo 'uploaded';
				$success = 1;
				$message = lang('uploaded csv file');
			}else{
				$message=lang('Upload csv file failed').', '.$msg;
				$success = 0;
			}
		}
		$response['success'] = $success;
		$response['msg'] = $message;
		// $response['filepath'] = $filepath;

		$return_player_list = [];
		$csv_file = $filepath;
		$ignore_first_row = false;
		$cnt = 0; // for collect the result of utils::loopCSV().
		$message = ''; // for collect the result of utils::loopCSV().
		// for use()
		$controller = $this;
        $this->utils->loopCSV($csv_file, $ignore_first_row, $cnt, $message,
            function($cnt, $csv_row, $stop_flag)
            use( $controller, &$return_player_list ) {
				$controller->utils->debug_log('OGP-27281.320.csv_row:', $csv_row );
				$controller->utils->debug_log('OGP-27281.320.csv_row[0]:', $csv_row[0] );
				$username = $csv_row[0];
				$playerId = $controller->player_model->getPlayerIdByUsername($username);
				$element = [];
				if( !empty($playerId) ){
					$element['id'] = $playerId;
					$element['text'] = $username;
				}
				if( ! empty($element) ){
					$return_player_list[] = $element;
				}
		});// EOF utils->loopCSV()
		$response['items'] = $return_player_list;

		$this->returnJsonResult($response);
	}
	public function getUploadPath4analysisReport(){
		$path = $this->utils->getUploadPath();
		$path .= DIRECTORY_SEPARATOR. 'analysis_report';
		$this->utils->addSuffixOnMDB($path);
		return $path;
	}


	public function view_payment_account($id = null) {
		return redirect('payment_account_management/list_payment_account');
	}

	public function view_payment_account_lite($id = null) {
		return redirect('payment_account_management/list_payment_account_lite');
	}

	public function add_payment_account() {
		$this->loadTemplate('', '', '', '');
		$this->template->write_view('sidebar', 'payment_management/sidebar');
		$this->template->write_view('main_content', 'payment_management/payment_account/add_payment_account', $data);
		$this->template->render();
	}

	public function delete_image(){
		$this->load->helper('security');

		$payment_account_id = $_POST['account_id'];
		$image = $_POST['image'];
        $source = $_POST['source_img'];

        $isEnableMdb = $this->utils->isEnabledMdb();
        $currency =  $this->utils->getActiveCurrencyKeyOnMDB();

		if($source == 'logo'){
			$qrcodeConfig = $this->prepareUploadConfig(self::IMG_TYPE_ICON, $payment_account_id);
			unlink($qrcodeConfig['upload_path']."/".$image); // delete file
			$result =  $this->payment_account->removeLogoImage($payment_account_id);
		}else if($source == 'qrcode'){
            $qrcodeConfig = $this->prepareUploadConfig(self::IMG_TYPE_QRCODE, $payment_account_id);
			unlink($qrcodeConfig['upload_path']."/".$image); // delete file
			$result =  $this->payment_account->removeQrCodeImage($payment_account_id);
		}
		return json_encode($result);

	}

    public function upload_image($type) {
		$this->form_validation->set_rules('account_image_filepath', lang('pay.account_image'), 'trim|xss_clean');

		// store array of allowed image extensions
        $ext_allowed = array("jpg", "jpeg", "gif", "png");

        $isEnableMdb = $this->utils->isEnabledMDB();
        $currency =  $this->utils->getActiveCurrencyKeyOnMDB();

		if ($type == self::IMG_TYPE_QRCODE) {
			$qrcodeImageName = $_FILES['qrcodeImageName']['name'];
			$qrcodeImageNameExt = explode('.', $qrcodeImageName);
			$qrcodeImageNameExt = $qrcodeImageNameExt[count($qrcodeImageNameExt) - 1];
			if (in_array($qrcodeImageNameExt, $ext_allowed) <= 0) {
				$message = lang('cashier.113');
				$this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);

				redirect('payment_account_management/view_payment_account', 'refresh');
			}
		} elseif ($type == self::IMG_TYPE_ICON) {
			$iconImageName = $_FILES['iconImageName']['name'];
			$iconImageNameExt = explode('.', $iconImageName);
			$iconImageNameExt = $iconImageNameExt[count($iconImageNameExt) - 1];

			if (in_array($iconImageNameExt, $ext_allowed) <= 0) {
				$message = lang('cashier.113');
				$this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);

				redirect('payment_account_management/view_payment_account', 'refresh');
			}
		}

		$payment_account_id = $this->input->post('payment_account_id');
		if ($payment_account_id) {
			if ($type == self::IMG_TYPE_QRCODE) {
				$qrcodeConfig = $this->prepareUploadConfig(self::IMG_TYPE_QRCODE, $payment_account_id, $isEnableMdb? $currency: '');
				$this->load->library('upload', $qrcodeConfig);
				$this->upload->do_upload('qrcodeImageName');
				$data['account_image_filepath'] = $qrcodeConfig['file_name'] . '.' . $qrcodeImageNameExt;
                
                if ($isEnableMdb) {
                    $data['account_image_filepath'] = "{$currency}/{$data['account_image_filepath']}";
                }

				$this->saveAction(self::MANAGEMENT_NAME, 'Uploaded QR Code Image', "User " . $this->authentication->getUsername() . " has uploaded qrcode image with payment account id: " . $payment_account_id . ".");
			} elseif ($type == self::IMG_TYPE_ICON) {
                $logoConfig = $this->prepareUploadConfig(self::IMG_TYPE_ICON, $payment_account_id, $isEnableMdb? $currency: '');

				$this->load->library('upload', $logoConfig);
				$this->upload->do_upload('iconImageName');
				$data['account_icon_filepath'] = $logoConfig['file_name'] . '.' . $iconImageNameExt;

                if ($isEnableMdb) {
                    $data['account_icon_filepath'] = "{$currency}/{$data['account_icon_filepath']}";
                }

				$this->saveAction(self::MANAGEMENT_NAME, 'Uploaded Logo Image', "User " . $this->authentication->getUsername() . " has uploaded qrcode image with payment account id: " . $payment_account_id . ".");
			}

			$today = $this->utils->getNowForMysql();
			$data['updated_at'] = $today;
			$data['updated_by_userid'] = $this->authentication->getUserId();

			$this->payment_account->editPaymentAccount($payment_account_id, $data);
			$message = lang('collection.upload.msg.success');
			$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, $message);
		} else {
			$message = lang('collection.upload.msg.unsuccess');
			$this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);
		}

		redirect('payment_account_management/view_payment_account', 'refresh');
	}

	const IMG_TYPE_QRCODE = 1;
	const IMG_TYPE_ICON = 2;
	const ACCOUNT_IMAGE_STORAGE = "account";
	private function prepareUploadConfig($type, $id, $prefolder= '') {
		$path = APPPATH . "../public/resources/images/" . self::ACCOUNT_IMAGE_STORAGE;
        if (!empty($prefolder)) {
            $path = "{$path}/{$prefolder}";
        }

		$config = array(
			'allowed_types' => 'jpg|jpeg|gif|png',
			'upload_path' => realpath($path),
			'max_size' => $this->utils->getMaxUploadSizeByte(),
			'overwrite' => true,
			'file_name' => $type == self::IMG_TYPE_QRCODE ? $id . '_qrcode' : $id . '_icon',
        );

		return $config;
	}

	/**
	 * export report to excel
	 *
	 * @return	excel format
	 */
	public function export_to_excel() {

		$this->saveAction(self::MANAGEMENT_NAME, 'Exported Payment Account List', "User " . $this->authentication->getUsername() . " exported Payment Account List");

		$result = $this->payment_account->getPaymentAccountListToExport();
		$d = new DateTime();
		$this->utils->create_excel($result, 'paymentaccountlist_' . $d->format('Y_m_d_H_i_s').'_'.rand(1,999), false, function(&$row) {
			$row['payment_type'] = lang($row['payment_type']);
		});
	}

	/**
	 * check duplicate payment gateway
	 *
	 *
	 */
	function duplicate_payment_gateway($payment_type_id) {
		$rlt = true;
		if (!empty($payment_type_id)) {
			$id = $this->input->post('payment_account_id');
			$rlt = !$this->payment_account->existsPaymentGateway($payment_type_id, $id);
			if (!$rlt) {
				$this->form_validation->set_message('duplicate_payment_gateway', lang('pay.duplicate_payment_gateway'));
			}
		}

		return $rlt;
	}

	/**
	 * add/edit Bank account setting
	 *
	 * @return	array
	 */
	public function add_edit_payment_account($sourcePage = '') {
		$this->form_validation->set_rules('payment_type_id', lang('pay.payment_name'), 'trim|required|xss_clean|callback_duplicate_payment_gateway');
		$this->form_validation->set_rules('flag', lang('pay.payment_account_flag'), 'trim|required|xss_clean');
		$this->form_validation->set_rules('second_category_flag', lang('pay.second_category_flag'), 'trim|required|xss_clean');
		$this->form_validation->set_rules('payment_account_name', lang('pay.payment_account_name'), 'trim|required|xss_clean');

		$flag = $this->input->post('flag');
		$payment_account_number_rules = '';
		if($this->utils->isEnabledFeature('allow_special_characters_on_account_number')) {
			$payment_account_number_rules = 'trim|xss_clean';
		} else {
			$payment_account_number_rules = 'trim|xss_clean|numeric';
		}
		if($flag != AUTO_ONLINE_PAYMENT) {
			$payment_account_number_rules .= '|required';
		}
		$this->form_validation->set_rules('payment_account_number', lang('pay.payment_account_number'), $payment_account_number_rules);

		$second_category_flag = $this->input->post('second_category_flag');
		if(($second_category_flag == SECOND_CATEGORY_CRYPTOCURRENCY) && ($flag != AUTO_ONLINE_PAYMENT) && ($this->utils->getConfig('use_branch_as_network'))){
			$payment_branch_name_rules = 'trim|xss_clean|required';
		}else{
			$payment_branch_name_rules = 'trim|xss_clean';
		}

		$this->form_validation->set_rules('payment_branch_name', ( $this->utils->getConfig('use_branch_as_ifsc_in_withdrawal_accounts') ? lang('financial_account.ifsc') : lang('pay.payment_branch_name')),$payment_branch_name_rules);

		$this->form_validation->set_rules('max_deposit_daily', lang('pay.daily_max_depsit_amount'), 'trim|xss_clean|numeric|callback_max_deposit_daily');
		$this->form_validation->set_rules('min_deposit_trans', lang('Min deposit per transaction'), 'trim|xss_clean|numeric');
		$this->form_validation->set_rules('max_deposit_trans', lang('Max deposit per transaction'), 'trim|xss_clean|numeric');
		$this->form_validation->set_rules('payment_order', lang('pay.payment_order'), 'trim|xss_clean|numeric');
		$this->form_validation->set_rules('total_approved_deposit_count', lang('player.ui14'), 'trim|xss_clean|numeric');
		$this->form_validation->set_rules('promocms_id', lang('cms.06'), 'trim|xss_clean|numeric');
		$this->form_validation->set_rules('daily_deposit_limit_count', lang('pay.daily_max_transaction_count'), 'trim|xss_clean|numeric|max_length[5]');
		$this->form_validation->set_rules('deposit_fee_percentage', lang('Deposit Fee Percentage'), 'trim|xss_clean|numeric');
		$this->form_validation->set_rules('min_deposit_fee', lang('Min Deposit Fee'), 'trim|xss_clean|numeric');
		$this->form_validation->set_rules('max_deposit_fee', lang('Max Deposit Fee'), 'trim|xss_clean|numeric');
		$this->form_validation->set_rules('player_deposit_fee_percentage', lang('Player Deposit Fee Percentage'), 'trim|xss_clean|numeric');
		$this->form_validation->set_rules('min_player_deposit_fee', lang('Player Min Deposit Fee'), 'trim|xss_clean|numeric');
		$this->form_validation->set_rules('max_player_deposit_fee', lang('Player Max Deposit Fee'), 'trim|xss_clean|numeric');
		$this->form_validation->set_rules('total_deposit', lang('pay.total_deposit_limit'), 'trim|xss_clean|numeric|callback_total_deposit');
		$this->form_validation->set_rules('notes', lang('player.upay05'), 'trim|xss_clean');
		$this->form_validation->set_rules('preset_amount_buttons', lang('pay.preset_amount_buttons'), 'trim|xss_clean|callback_preset_amount_buttons');
		$this->form_validation->set_rules('exchange', lang('pay.exchange'), 'trim|xss_clean');
		$this->form_validation->set_rules('qrcode_content', lang('QR Code Content'), 'trim|xss_clean');

		$this->form_validation->set_message('numeric', lang('%s must be numeric!'));
		$this->form_validation->set_message('required', sprintf(lang("field is required"), lang('%s')));
		$this->form_validation->set_message('max_length', lang('formvalidation.max_length'));

		if (!$this->form_validation->run()) {
			$message = validation_errors();
			$this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);
			if($sourcePage == 'view_payment_account_lite'){
				$this->view_payment_account_lite();
			}else{
				$this->view_payment_account();
			}
		} else {

			$this->load->model(array('banktype'));

			$today 			= $this->utils->getNowForMysql();
			$nextOrder 		= $this->payment_account->getNextOrder();
			$user_id 		= $this->authentication->getUserId();

			$id 			= $this->input->post('payment_account_id');
			$playerLevels 	= $this->input->post('playerLevels') ? : array();
			$affiliates 	= $this->input->post('affiliates') ? : array();
			$agents 		= $this->input->post('agents') ? : array();
			$players 		= $this->input->post('players') ? : array();
			$qrcode_content = $this->input->post('qrcode_content');

			$data = $this->getPostData(array(
				'payment_type_id',
				'flag',
				'second_category_flag',
				'payment_account_name',
				'payment_account_number',
				'payment_branch_name',
				'max_deposit_daily',
				'min_deposit_trans',
				'max_deposit_trans',
				'daily_deposit_limit_count',
				'payment_order',
				'total_approved_deposit_count',
				'logo_link',
				'total_deposit',
				'promocms_id',
				'deposit_fee_percentage',
				'min_deposit_fee',
				'max_deposit_fee',
				'player_deposit_fee_percentage',
				'min_player_deposit_fee',
				'max_player_deposit_fee',
				'notes',
				'preset_amount_buttons',
                'bonus_percent_on_deposit_amount',
				'exchange'
			));

			$data['updated_by_userid'] 	= $user_id;
			$data['updated_at'] 		= $today;


			if(!empty($qrcode_content)){
				$extra_info['qrcode_content'] = $qrcode_content;
				$data['extra_info'] = json_encode($extra_info);
			}

			if ($banktype = $this->banktype->getBankTypeById($data['payment_type_id'])) {
				$data['external_system_id'] = $banktype ? $banktype->external_system_id ? : 0 : 0;
			}

			$this->payment_account->startTrans();
			if ($id) {
				# EDIT PAYMENT ACCOUNT
				$this->payment_account->editPaymentAccount($id, $data, $playerLevels, $affiliates, $agents, $players);
				$message = lang('con.bnk03') . " <b>" . $data['payment_account_name'] . "</b> " . lang('con.bnk04');
				$this->saveAction(self::MANAGEMENT_NAME, 'Edit Payment Account', "User " . $this->authentication->getUsername() . " has edited Payment Account " . $data['payment_account_name'] . ".");
			} else {
				# ADD PAYMENT ACCOUNT
				$data['created_by_userid'] 	= $user_id;
				$data['created_at'] 		= $today;
				$data['payment_order'] 		= $data['payment_order'] ? : $nextOrder;

				$this->payment_account->addPaymentAccount($data, $playerLevels, $affiliates, $agents, $players);
				$message = lang('con.bnk03') . " <b>" . $data['payment_account_name'] . "</b> " . lang('con.bnk05');
				$this->saveAction(self::MANAGEMENT_NAME, 'Add Payment Account ', "User " . $this->authentication->getUsername() . " has added new Payment Account " . $data['payment_account_name'] . ".");
			}

			if ($this->payment_account->endTransWithSucc()) {
				$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, $message);
				if($sourcePage == 'view_payment_account_lite'){
					redirect('payment_account_management/view_payment_account_lite', 'refresh');
				}else{
					redirect('payment_account_management/view_payment_account', 'refresh');
				}
			} else {
				$this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('save.failed'));
				if($sourcePage == 'view_payment_account_lite'){
					$this->view_payment_account_lite();
				}else{
					$this->view_payment_account();
				}
			}
		}
	}

	/**
	 * Custom Validations
	 * Checks the value of max_deposit_daily
	 * --- Rules
	 * Daily Max Deposit Amount should be > 0
	 * OG-890
	 */
	function max_deposit_daily() {

		$msd = $this->input->post('max_deposit_daily');
		$td = $this->input->post('total_deposit');

		if ($msd == 0) {
			$this->form_validation->set_message('max_deposit_daily', lang('pay.daily_max_dep_msg'));
			return FALSE;
		} else {
			return TRUE;
		}

	}

	/**
	 * Custom Validations
	 * Checks the value of total_deposit
	 * --- Rules
	 * Total Deposit Limit should be >= Daily Max Deposit Amount
	 *  OG-890
	 */
	function total_deposit() {
		$msd = $this->input->post('max_deposit_daily');
		$td = $this->input->post('total_deposit');

		if ($td < $msd || $td == 0) {
			$this->form_validation->set_message('total_deposit', lang('pay.total_dep_msg'));
			return FALSE;
		} else {
			return TRUE;
		}

	}

	/**
	 * Custom Validations
	 * Checks the value of preset_amount_buttons
	 * only input number and "|"
	 */
	function preset_amount_buttons(){
		$preset_amount_buttons  = $this->input->post('preset_amount_buttons');
		$pattern_1 = '/^[1-9][0-9|]/';
		$pattern_2 = '/^[1-9][0-9]{0,15}$/';
		$preset_amount_buttons_limit_count = $this->config->item('preset_amount_buttons_limit_count');

		$this->utils->debug_log("--------------preset_amount_buttons: ", $preset_amount_buttons);
		if (!empty($preset_amount_buttons)) {
			if (!preg_match($pattern_1,$preset_amount_buttons)) {
				$this->form_validation->set_message('preset_amount_buttons', lang('pay.preset_amount_buttons_msg'));
				return FALSE;
			} else {
				$max_length = explode('|', $preset_amount_buttons);
				$this->utils->debug_log("--------------preset_amount_buttons max_length: ", $max_length);
				if (count($max_length) > $preset_amount_buttons_limit_count) {
					$this->form_validation->set_message('preset_amount_buttons', sprintf(lang('pay.preset_amount_buttons_count_msg'), $preset_amount_buttons_limit_count));
					return FALSE;
				}
				foreach ($max_length as $key => $amount) {
					$this->utils->debug_log("--------------preset_amount_buttons amount: ", $amount);
					if (!preg_match($pattern_2,$amount)) {
						$this->form_validation->set_message('preset_amount_buttons', lang('pay.preset_amount_buttons_msg'));
						return FALSE;
					}
				}
				return TRUE;
			}
		}
	}

	/**
	 * get bank account details
	 *
	 * @param 	bankAccountId
	 * @return	redirect
	 */
	public function get_payment_account_details($id) {
		$this->returnJsonResult($this->payment_account->getPaymentAccountDetails($id));
	}

	/**
	 * Delete bank account
	 *
	 * @param 	int
	 * @return	redirect
	 */
	public function delete_selected_payment_account($paymentAccountId = null) {
		if (!$this->permissions->checkPermissions('collection_account')) {
			return $this->error_access();
		}

		$payment_account = ($paymentAccountId) ? $paymentAccountId : $this->input->post('payment_account');

		$save_action_payment_account = is_array($payment_account) ? implode(",", $payment_account) : $payment_account;
        $this->saveAction(self::MANAGEMENT_NAME, 'Delete Selected Payment Account', "User " . $this->authentication->getUsername() . " deleted payment account: " . $save_action_payment_account);

		if (!empty($payment_account)) {
			if(is_array($payment_account)){
				foreach ($payment_account as $payment_account_id) {
					$this->payment_account->softDeletePaymentAccount($payment_account_id);
				}
			}else{
				$this->payment_account->softDeletePaymentAccount($payment_account);
			}

			$message = lang('con.bnk07');
			$this->alertMessage(1, $message); //will set and send message to the user
			redirect(self::HOME_URI);
		} else {
			$message = lang('con.bnk08');
			$this->alertMessage(2, $message);
			redirect(self::HOME_URI);
		}
	}

	public function delete_payment_account_item($id) {
		if (!$this->permissions->checkPermissions('collection_account')) {
			$this->error_access();
		} else {

			$paymentAccount = $this->payment_account->getPaymentAccountDetails($id);
			if ($paymentAccount) {
				$this->payment_account->deletePaymentAccount($id);

				$this->saveAction(self::MANAGEMENT_NAME, 'Delete Payment Account', "User " . $this->authentication->getUsername() . " deleted payment account: " . $paymentAccount->payment_account_name);
			}
			$message = lang('con.bnk09');
			$this->alertMessage(1, $message);
			redirect(self::HOME_URI, 'refresh');
		}
	}

	public function active_payment_account_item($id, $redirectUrlenabledURI = '') {
		if (!$this->permissions->checkPermissions('collection_account')) {
			return $this->error_access();
		}

		$this->payment_account->startTrans();

		$this->payment_account->enablePaymentAccount($id);
		//force the bank to be active
		$bankTypeId = $this->payment_account->getPaymentTypeId( $id );
		$this->banktype->updateBanktype($bankTypeId, array(
			'status' => 'active',
		));
		$this->saveAction(self::MANAGEMENT_NAME, 'Active Payment Account', "User " . $this->authentication->getUsername() . " active payment account: " . $id);

		$this->payment_account->endTransWithSucc();

		$message = lang('Actived account');
		$this->alertMessage(1, $message);
		$redirectURI = self::HOME_URI;
		if( ! empty($redirectUrlenabledURI) ){
			$redirectURI = urldecode($redirectUrlenabledURI);
		}
		redirect($redirectURI);

	}


	public function inactive_payment_account_item($id, $redirectUrlenabledURI = '') {
		if (!$this->permissions->checkPermissions('collection_account')) {
			return $this->error_access();
		}
		$this->load->model(['payment_account']);
		$this->payment_account->startTrans();

		$this->payment_account->disablePaymentAccount($id);
		$this->saveAction(self::MANAGEMENT_NAME, 'Inactive Payment Account', "User " . $this->authentication->getUsername() . " Inactived payment account: " . $id);

		$this->payment_account->endTransWithSucc();

		$message = lang('Inactived account');
		$this->alertMessage(1, $message);
		$redirectURI = self::HOME_URI;
		if( ! empty($redirectUrlenabledURI) ){
			$redirectURI = urldecode($redirectUrlenabledURI);
		}
		redirect($redirectURI);

	}

	public function api_bank_list($apiId){
		$this->load->model(['banktype']);
		$this->load->helper('form');

		$api=$this->utils->loadExternalSystemLibObject($apiId);
		$data=['api'=>$api,
			'title'=> lang('API Bank List').' - '.$api->getSystemInfo('_system_code')];

		$bankList=$api->getSystemInfo('bank_info_list', []);

		$data['bankList'] = $bankList;
		$data['apiId']=$apiId;
		$data['bankDropdown']=$this->banktype->getActiveBankDropdown();

		$data['levels'] = array_column($this->group_level->getAllPlayerLevelsForSelect(), 'groupLevelName', 'vipsettingcashbackruleId');
		$this->utils->debug_log("Player levels: ", $data['levels']);
		$data['form'] = &$form;

		//show bank list
		$this->loadTemplate(lang('pay.payment_account'), '', '', 'payment');
		$this->addBoxDialogToTemplate();
		$this->template->write_view('sidebar', 'payment_management/sidebar');
		$this->template->write_view('main_content', 'payment_management/payment_account/api_bank_list', $data);
		$this->template->render();

	}

	public function delete_api_bank_info($apiId, $bankIndex){
		$this->load->model(['external_system']);
		$api=$this->utils->loadExternalSystemLibObject($apiId);

		$bankList=$api->getSystemInfo('bank_info_list', []);
		if(isset($bankIndex) && $bankIndex!==''){
			unset($bankList[$bankIndex]);
		}

		//save back
		$success=$this->external_system->updateBankList($bankList, $apiId);

		if($success){
			$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang('Delete api bank info successfully'));
		}else{
			$this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('Delete failed'));
		}

		$this->saveAction(self::MANAGEMENT_NAME, 'Delete API Bank Info', "delete api bank info ".$apiId);

		redirect('/payment_account_management/api_bank_list/'.$apiId);

	}

	public function save_api_bank_info($apiId){

		//save
		$bank_id=intval($this->input->post('bank_id'));
		$bank_index=$this->input->post('bank_index');
		$card_number=$this->input->post('card_number');
		$name=$this->input->post('name');
		$address=$this->input->post('address');
		$playerLevels = $this->input->post('playerLevels');

		$this->load->model(['banktype', 'external_system']);
		$this->load->helper('form');

		$banktype=$this->banktype->getBankTypeById($bank_id);

		if(empty($apiId) || empty($bank_id) || empty($card_number) || empty($name)){

			$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang('Wrong request, please check again'));

			return redirect('/payment_account_management/api_bank_list/'.$apiId);
		}

		$api=$this->utils->loadExternalSystemLibObject($apiId);

		$bankList=$api->getSystemInfo('bank_info_list', []);

		if($bank_index!=='' && $bank_index!==null){

			$enabled= isset($bankList[$bank_index]['enabled']) ? $bankList[$bank_index]['enabled'] : true;

			$bankList[$bank_index]=[
				'bank_name'=> $banktype->bankName,
				'db_bank_id'=> $bank_id,
				'bank_code' => $banktype->bank_code,
				'card_number' => $card_number,
				'address'=> $address,
				'name' => $name,
				'enabled' => $enabled, # original 'enabled' value
				'playerLevels' => $playerLevels
			];
		}else{
			$bankList[]=[
				'bank_name'=> $banktype->bankName,
				'db_bank_id'=> $bank_id,
				'bank_code' => $banktype->bank_code,
				'card_number' => $card_number,
				'name' => $name,
				'address'=> $address,
				'enabled' => 1,
				'playerLevels' => $playerLevels
			];

		}

		//save back
		$success=$this->external_system->updateBankList($bankList, $apiId);

		if($success){
			$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang('Save api bank info successfully'));
		}else{
			$this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('Save failed'));
		}

		$this->saveAction(self::MANAGEMENT_NAME, 'Save API Bank Info', "save api bank info ".$apiId);

		redirect('/payment_account_management/api_bank_list/'.$apiId);
	}

	public function enable_api_bank_info($apiId, $bankIndex, $enable = '1') {
		$this->load->model(['external_system']);
		$api=$this->utils->loadExternalSystemLibObject($apiId);

		$bankList = $api->getSystemInfo('bank_info_list', []);
		if(isset($bankIndex) && $bankIndex!==''){
			$bankList[$bankIndex]['enabled'] = $enable=='1';
		}

		//save back
		$success=$this->external_system->updateBankList($bankList, $apiId);

		if($success){
			$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang('API bank info '.($enable ? 'enabled' : 'disabled')));
		}else{
			$this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('Update failed'));
		}

		$this->saveAction(self::MANAGEMENT_NAME, ($enable ? 'Enable' : 'Disable') . ' API Bank Info', ($enable ? 'Enable' : 'Disable') . ' API Bank Info'.$apiId);

		redirect('/payment_account_management/api_bank_list/'.$apiId);
	}
}

///END OF FILE/////
