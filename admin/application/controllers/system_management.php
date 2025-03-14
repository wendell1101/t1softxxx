<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/BaseController.php';
require_once dirname(__FILE__) . '/allowed_withdrawal_status.php';
require_once dirname(__FILE__) . '/kyc_status.php';

/**
 * system management
 * @property Lib_queue $lib_queue
 * @property Queue_result $queue_result
 */
class System_management extends BaseController {
	use allowed_withdrawal_status;
	use kyc_status;

	CONST PLAYER_CENTER_LANGUAGE_OPERATORSETTING_NAME = 'player_center_language';
	CONST ACTION_MANAGEMENT_TITLE = 'System Management';
	function __construct() {
		parent::__construct();
		$this->load->helper('url');
		$this->load->library(array('permissions', 'form_validation', 'template', 'data_tables', 'Multiple_image_uploader'));

		$this->permissions->checkSettings();
		$this->permissions->setPermissions();
	}

	const SETTINGS_LIST=[
		'approve_transfer_to_main'=>[
			'note'=>'Allow admin user to approve transfer to main',
			'description_json' => [
				"type" => "checkbox",
				"default_value" => false, "label_lang" => "Approve Transfer To Main",
			],
		],
		'approve_transfer_from_main'=>[
			'note'=>'Allow admin user to approve transfer from main',
			'description_json' => [
				"type" => "checkbox",
				"default_value" => false, "label_lang" => "Approve Transfer From Main",
			],
		],
	];

	const SETTINGS_CASHIER_CENTER_NAME_LIST = array(
		'deposit_process',
		'player_center_withdrawal_page',
		'enabled_display_withdrawal_password_notification',
		'enabled_change_withdrawal_password',
		'manual_deposit_request_cool_down',
		'manual_deposit_request_cool_down_time',
        // 'approve_transfer_to_main',
        // 'approve_transfer_from_main',
        // OGP-14009 5.3 Please deprecate these functions if the functions have no effect on the system after code review.
    );

	const SETTINGS_PLAYER_CENTER_NAME_LIST = [
        'limit_of_single_ip_registrations_per_day',
        'player_auto_lock',
        'player_auto_lock_time_limit',
        'player_auto_lock_password_failed_attempt',
        'player_center_logout_action',
        'birthday_option',
        'announcement_option',
        'single_player_session',
        'player_center_enabled_language',
        // 'deposit_successful_action', // Deprecated.
        'registered_success_popup',
        'player_center_currency_display_format',
        'player_center_notification',
        'player_center_notification_check_interval',
        'player_center_notification_source_type',
		'sms_api_list',
		'voice_api_list',
        'notify_api_list',
		'iovation_api_list',
		'telephone_api_list',
    ];

    const SETTINGS_PREFERENCE_NAME_LIST = [];

    const SMS_API_SETTING_LIST = [
		'sms_api_register_setting',
		'sms_api_login_setting',
		'sms_api_bankinfo_setting',
		'sms_api_sendmessage_setting',
		'sms_api_forgotpassword_setting',
		'sms_api_security_setting',
		'sms_api_accountinfo_setting',
		'sms_api_manager_setting'
    ];

    /**
	 * Will redirect to another sidebar if the permission was disabled
	 *
	 * Created by Mark Andrew Mendoza (andrew.php.ph)
	 */
    private function error_redirection($from = 'system'){
		$systemUrl = $this->utils->activeSystemSidebar();
		$reportUrl = $this->utils->activeReportSidebar();
		$playerUrl = $this->utils->activePlayerSidebar();

		if($from == 'system')
			$data['redirect'] = $systemUrl;

		elseif($from == 'report')
			$data['redirect'] = $reportUrl;

		else
			$data['redirect'] = $playerUrl;

		$message = lang('con.vsm01');
		$this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);


		$this->template->write_view('main_content', 'error_page', $data);
		$this->template->render();
	}

	/**
	 * Controller for system_settings pages
	 * OGP-14821: Separated to subpages smart_backend and player_center
	 * @param	string	$subpage	valid values: 'smart_backend' | 'player_center'.  Defaults to 'smart_backend'.
	 *
	 * @link 	system_management/system_settings/smart_backend
	 * @link 	system_management/system_settings/player_center
	 *
	 * @see		views/system_management/sys_settings_smart_backend	(view)
	 * @see		views/system_management/sys_settings_player_center	(view)
	 * @see		resources/js/system_management/system_management.js	(js)
	 * @see		System_management::save_system_settings()		(post endpoint)
	 * @see		System_management::upload_sbe_logo()			(post endpoint)
	 *
	 * @return	Rendered view
	 */
	public function system_settings($subpage = 'smart_backend') {
		$data = array('title' => lang('System Settings'), 'sidebar' => 'system_management/sidebar',
			'activenav' => 'system');

		switch ($subpage) {
			case 'player_center' :
				if (!$this->permissions->checkPermissions('system_settings_player_center') ) {
					return $this->error_redirection();
				}
				break;
			case 'smart_backend' :
				if (!$this->permissions->checkPermissions('system_settings_smart_backend') ) {
					return $this->error_redirection();
				}
				break;
			default :
				redirect('system_management/system_settings/smart_backend');
				return;
		}

		$this->load->model(array('operatorglobalsettings','static_site'));
		$logoSettings = $this->operatorglobalsettings->getSettingJson('sys_default_logo');
		$adminAutoLogoutSetting = $this->operatorglobalsettings->getSettingJson('admin_sess_expire');
		$this->utils->debug_log('======================== adminAutoLogoutSetting:' . json_encode($adminAutoLogoutSetting) );

		if (!$this->utils->isUploadedLogoExist() || !$this->utils->isLogoOperatorSettingsExist() || !$this->utils->isLogoSetOnDB()) {
			$data['useSysDefault'] = 'checked';
		} else {
			$data['useSysDefault'] =  $logoSettings['use_sys_default'] ? 'checked' : '';
		}

		$data['enable_admin_auto_logout'] = $adminAutoLogoutSetting['enable'];
		$data['auto_logout_sess_expiration'] = $adminAutoLogoutSetting['sess_expiration']/60;

	    $data['logo_image'] = $data['useSysDefault'] == 'checked' ? $this->utils->getDefaultLogoUrl() : $this->utils->getUploadedSysLogoUrl();
        $data['playerCenterLanguage'] = $this->getPlayerCenterLanguage();

		$userId = $this->authentication->getUserId();
		$username = $this->authentication->getUsername();
		$render = true;

		$this->addBoxDialogToTemplate();

		$this->load->library(array('sms/sms_sender'));
		$data['smsBalances'] = $this->sms_sender->getSmsBalances();

		switch ($subpage) {
			case 'player_center' :
				$data['settings'] = [
		            'player_center' => [
		                'name' => lang('Player Center'),
		                'options' => $this->filter_player_center_options()
		            ],
		            'cashier' => [
		                'name' => lang('Cashier Center'),
		                'options' => $this->operatorglobalsettings->getSystemSettings(self::SETTINGS_CASHIER_CENTER_NAME_LIST)
		            ],
		        ];
				$view_filename = "system_management/sys_settings_player_center";
				break;
			case 'smart_backend' : default :
				$data['settings'] = [
		           // 'preference' => [
		           //     'name' => lang('System Settings'),
		           //     'options' => $this->operatorglobalsettings->getSystemSettings(self::SETTINGS_PREFERENCE_NAME_LIST)
		           // ],
		            'cron_jobs' => [
		                'name' => lang('sys_settings_cron_jobs'),
		                'options' => $this->operatorglobalsettings->getSystemSettings($this->operatorglobalsettings->getOperatorNameListForAllCronJobs())
		            ],
		        ];
				$view_filename = "system_management/sys_settings_smart_backend";
				break;
		}



		$this->loadDefaultTemplate([ 'resources/js/system_management/system_management.js' ] ,
			array('resources/css/general/style.css'),
			array('title' => $data['title'],
				'activenav' => $data['activenav'],
				'userId' => $userId,
				'username' => $username), $data['sidebar'],
			$view_filename, $data, $render);
	}

	public function filter_player_center_options(){
		$option_list = $this->operatorglobalsettings->getSystemSettings(self::SETTINGS_PLAYER_CENTER_NAME_LIST);
		foreach ($option_list as $option => $option_val) {
			switch ($option) {
				case 'sms_api_list':
					if ($this->utils->getConfig('sms_api_hidden_list')) {
						$hide_list = $this->utils->getConfig('sms_api_hidden_list');
						$sms_api_list = $option_val['params']['list'];
						foreach ($sms_api_list as $api_index => $api_name) {
							if (in_array($api_name, $hide_list)) {
								$this->utils->debug_log(__METHOD__,'hide api name',$api_name);
								unset($option_list['sms_api_list']['params']['list'][$api_index]);
							}
						}
					}
				break;
			}
		}
		return $option_list;
	}

	/**
	 * Post endpoint for /system_management/system_settings/{smart_backend,player_center}
	 * Saves system settings
	 * OGP-15126 fixed bug when saving system settings
	 *
	 * @see		OGP-15126, OGP-14821
	 * @see		System_management::system_settings()
	 * @return	none
	 */
	public function save_system_settings() {
		$from_url = $this->input->server('HTTP_REFERER');
		$from_path = parse_url($from_url, PHP_URL_PATH);
		$from_path_parts = explode('/', $from_path);
		/**
		 * $subpage: last part of $from_path
		 * should be any of [ 'player_center', 'smart_backend' ]
		 */
		$subpage = end($from_path_parts);

		// $this->utils->debug_log(__METHOD__, ['subpage' => $subpage]);

		$this->load->model(array('operatorglobalsettings'));

		$this->startTrans();
		$settings = array();

		// If from .../system_settings/smart_backend
		if ($subpage == 'smart_backend') {
			// Update preferences (empty now)
			foreach (self::SETTINGS_PREFERENCE_NAME_LIST as $name) {
				$settings[$name] = $this->input->post('value_' . $name);
			}
			// Update cronjobs
			foreach ($this->operatorglobalsettings->getOperatorNameListForAllCronJobs() as $name) {
				$settings[$name] = $this->input->post('value_' . $name);
			}
		}

		// If from .../system_settings/player_center
		if ($subpage == 'player_center') {
			// Update cashier center settings
			foreach (self::SETTINGS_CASHIER_CENTER_NAME_LIST as $name) {
				$settings[$name] = $this->input->post('value_' . $name);
			}
			// Update player center settings
			foreach (self::SETTINGS_PLAYER_CENTER_NAME_LIST as $name) {
				$settings[$name] = $this->input->post('value_' . $name);
			}
		}

		// $this->utils->debug_log(__METHOD__, ['settings' => $settings]);

		if (!empty($settings)) {
			$this->operatorglobalsettings->saveSettings($settings);
		}

		//$this->saveAction($management, $action, $description);

		$success = $this->endTransWithSucc();
		if ($success) {
			$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang('Save system settings successfully'));
		} else {
			$this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('error.default.db.message'));
		}

		redirect($from_path);
		// redirect('system_management/system_settings');
	}

	public function save_operator_settings(){
		//only admin
		if (!$this->permissions->checkPermissions('system_settings')) {
			$this->returnErrorStatus();
			// $this->showErrorAccess(lang('System Message'),'system_management/sidebar','system_message');
		}

		$success=$this->utils->putOperatorSetting('sms_registration_template', $this->input->post('sms_registration_template'));
		$message=null;
		//update player_center_template, validate dir
		$dir=dirname(__FILE__).'/../../../player/application/views/'.$this->input->post('player_center_template');
		if(is_dir($dir)){
			$success=$success && $this->utils->putOperatorSetting('player_center_template', $this->input->post('player_center_template'));
		}else{
			$message=lang('Wrong template of player center');
			$success=false;
		}

		if($success){
			$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang('Save settings successfully'));
		} else {
			$this->alertMessage(self::MESSAGE_TYPE_ERROR, !empty($message) ? $message : lang('error.default.db.message'));
		}

		redirect('system_management/system_settings');
	}

	public function save_system_message(){
		//only admin
		if (!$this->permissions->checkPermissions('system_settings')) {
			$this->returnErrorStatus();
			// $this->showErrorAccess(lang('System Message'),'system_management/sidebar','system_message');
		}

		$result=['success'=>true];

		$msg=$this->input->post('msg');
		if(!empty($msg)){
			$this->load->model(array('users'));
			$server_name = $this->utils->getConfig('server_name');

			$msg=json_decode($msg,true);

			$content = $msg['content'];
			$options = @$msg['options'];
			$servers = $msg['servers'];
			$user = $msg['user'];

			if (in_array($server_name, $servers)) {
				$result['success']=!!$this->users->writeUnreadAdminMessage($user, $content, $options);
			} else {
				$this->utils->debug_log('drop message', $servers, 'user', $user);
			}

		}

		$this->returnJsonResult($result);
	}

	public function get_system_messages(){

		$this->load->view('system_management/system_messages', $data);
	}

	public function system_features(){
		$data = array('title' => lang('System Features'), 'sidebar' => 'system_management/sidebar',
			'activenav' => 'system');

		if(!($this->permissions->checkPermissions('system_features') && ($this->users->isT1User($this->authentication->getUsername()) || ($this->utils->getConfig('RUNTIME_ENVIRONMENT') == 'staging' && $this->utils->getConfig('enable_system_feature_on_staging_for_non_t1_users'))))) {
			return $this->error_redirection();
		}
		// enable_system_feature_search
		if($this->utils->getConfig('enable_system_feature_search')){
			return $this->system_features_search();
		}

		$this->load->model(array('system_feature'));

		$data['system_feature'] = $this->system_feature->get();

		$userId = $this->authentication->getUserId();
		$username = $this->authentication->getUsername();
		$render = true;

		$this->loadDefaultTemplate([ 'resources/js/system_management/system_management.js' ] ,
			array('resources/css/general/style.css'),
			array('title' => $data['title'],
				'activenav' => $data['activenav'],
				'userId' => $userId,
				'username' => $username), $data['sidebar'],
			'system_management/system_features', $data, $render);
	}

	public function system_features_search(){
		$data = array('title' => lang('System Features'), 'sidebar' => 'system_management/sidebar',
		'activenav' => 'system');

		if(!($this->permissions->checkPermissions('system_features')
		&& ($this->users->isT1User($this->authentication->getUsername())
		|| ($this->utils->getConfig('RUNTIME_ENVIRONMENT') == 'staging'
		&& $this->utils->getConfig('enable_system_feature_on_staging_for_non_t1_users'))))) {
			return $this->error_redirection();
		}
		$keyword = !empty(trim($this->input->get_post('keyword'))) ? $this->input->get_post('keyword'): '';
		$show_default_features = $this->input->get_post('show_default_features') == 'on' ? true: false;
		$data['search_conditions'] = [
			'keyword' => $keyword,
			'show_default_features' => $show_default_features,
		];
		$this->utils->debug_log('========================system_features_search search_conditions', $data['search_conditions'] );
		$this->load->model(array('system_feature'));

		$system_feature_groups = [];
		$system_feature_items = $this->system_feature->get($keyword);
		$disable_show_deprecated_system_feature = $this->utils->getConfig('disable_show_deprecated_system_feature');

		// filter default
        $default_to_new_features = $this->utils->getConfig('default_to_new_features');
		if($show_default_features && empty($keyword)) {
			foreach ($system_feature_items as $idx => $feature) {
				if (!in_array($feature['name'], $default_to_new_features)) {
					unset($system_feature_items[$idx]);
				}
			}
		}
		// filter deprecated
		$deprecated_features = $this->utils->getConfig('deprecated_features');
		foreach ($system_feature_items as $idx => $feature){
			if(in_array($feature['name'],$deprecated_features)){
				if(!$disable_show_deprecated_system_feature){
					continue;
				}
				$feature['is_deprecated'] = true;
			}
			$type = $feature['type'];
			$system_feature_groups[$type][] = $feature;
		}

		//classify feature
		foreach ($system_feature_groups as $categorie => $items) {
			if(empty($categorie) || !in_array($categorie, System_feature::FEATURE_TYPES)){
				unset($data['system_feature'][$categorie]);
			}
		}
		$data['system_feature'] = $system_feature_groups;
		$userId = $this->authentication->getUserId();
		$username = $this->authentication->getUsername();
		$render = true;

		$params = http_build_query($data['search_conditions']);

		$this->loadDefaultTemplate([ 'resources/js/system_management/system_management.js' ] ,
			array('resources/css/general/style.css'),
			array('title' => $data['title'],
				'activenav' => $data['activenav'],
				'userId' => $userId,
				'username' => $username), $data['sidebar'],
			'system_management/system_features_search', $data, $render);
	}

	function saveSystemFeatures(){
		$system_feature_page_read_only = $this->utils->getConfig('system_feature_page_read_only');
        if(!($this->permissions->checkPermissions('system_features') && ($this->users->isT1User($this->authentication->getUsername()) || ($this->utils->getConfig('RUNTIME_ENVIRONMENT') == 'staging' && $this->utils->getConfig('enable_system_feature_on_staging_for_non_t1_users'))))) {
			return $this->error_redirection();
		}
		if(!empty($system_feature_page_read_only)){
			return $this->error_redirection();
		}

		$this->load->model(array('system_feature'));
		$featureList = $this->input->post('enabled');

		$success=$this->lockAndTrans(Utils::LOCK_ACTION_SYSTEM_FEATURE, 0, function()
			use($featureList){
			$succ=true;
			if( ! empty( $featureList ) ){
				foreach ($featureList as $idx => $feature) {
					$succ=$this->system_feature->updateFeaturesWithoutClearCache(
						$feature['id'], ['enabled' => $feature['enabled']]);
					if(!$succ){
						return $succ;
					}
				}
			}
			return $succ;
		});
		$result=['success'=>$success];

		if ($success) {
			$this->utils->deleteCache();
			$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang('Save system features successfully'));
		} else {
			$this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('error.default.db.message'));
		}

		$this->returnJsonResult($result);
	}

	/**
	 * overview: upload file to app then set as system logo
	 *
	 * @param  none
	 * @return void
	 */
	public function upload_sbe_logo() {
		$default_dir_logo = $this->utils->default_upload_sub_dir_for_logo;

		$this->load->model(array('operatorglobalsettings'));
		// Use default logo
		if ($this->input->post('setDefaultLogo')) {
			$this->setToDefaultLogo();
			redirect('system_management/system_settings');
			return;
		}

	    $sysLogoValue = $this->utils->getSysLogoSettingFromOperatorSettings();

		// If no file to upload
		if (trim($_FILES["fileToUpload"]["name"])  == "") {

	    	// Check file name setup in the operatorssetting.
	    	if (trim($sysLogoValue['filename']) == "") {
	    		$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, "Please upload a logo first.");
	    		redirect('system_management/system_settings');
	    		return;
	    	}

	    	// Check if file exist in directory
	    	// $file_loc = $_SERVER['DOCUMENT_ROOT'] . '/resources/images/' . $sysLogoValue['path'] . $sysLogoValue['filename'];
	    	$file_loc = $this->utils->getUploadedSysLogoRealPath();

			if (!file_exists($file_loc)) {
				$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, "Please upload a logo first.");
	    		redirect('system_management/system_settings');
	    		return;
			}

			$sysLogoValue['use_sys_default'] = false;
			$this->operatorglobalsettings->putSettingJson($settingName, $sysLogoValue);
			$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, "System logo set successfully.");
			redirect('system_management/system_settings');
			return;
		}

		/** Upload file and set as default logo */
		// $target_dir = "{$this->utils->getUploadPath()}/{$default_dir_logo}";
		$target_dir = $this->utils->getSysLogoUploadPath();

		if (!file_exists($target_dir)) {
			$this->utils->debug_log('Creating logo upload dir', $target_dir);
			// Make sure directory priv is 777:
			// (1) Store umask (2) set umask 000 (3) create dir (4) restore umask
			$old_umask = umask(0);
			mkdir($target_dir, 0777 , 'recursive');
			umask($old_umask);

			if (!file_exists($target_dir)) {

				$this->utils->error_log("error creating logo upload dir", $target_dir);
				$this->alertMessage(self::MESSAGE_TYPE_ERROR, "Error creating logo upload directory $target_dir");
				return;
			}
		}
		$target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);

		$uploadOk = 1;
		$message = "";

		$imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
		// Check if image file is a actual image or fake image
	    if(!getimagesize($_FILES["fileToUpload"]["tmp_name"])) {
	        $message = "File is not an image.";
	        $uploadOk = 0;
	    }

		// Check file size
		if ($_FILES["fileToUpload"]["size"] > 30000) {
		    $message = "Sorry, your file is too large.";
		    $uploadOk = 0;
		}
		// Allow certain file formats
		if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif" ) {
		    $message = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
		    $uploadOk = 0;
		}
		// Check if $uploadOk is set to 0 by an error
		if ($uploadOk == 0) {
		    $this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);
		// if everything is ok, try to upload file
		} else {
		    if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
		    	$settingName = $this->utils->getDefaultLogoSettingName();
		    	$sysLogoValue = $this->operatorglobalsettings->getSettingJson($settingName);
		    	$fileName = $_FILES["fileToUpload"]["name"];

		    	$settingValue = array('path' => $default_dir_logo, 'filename' => $fileName, 'use_sys_default' => false);
		    	// If sys_default_log value doesn't exist
		    	if (!count($sysLogoValue)) {
		    		// Insert setting
		    		$this->operatorglobalsettings->insertSettingJson($settingName, $settingValue);
				} else {
					// Update setting
					$this->operatorglobalsettings->putSettingJson($settingName, $settingValue);
				}

		        $this->alertMessage(self::MESSAGE_TYPE_SUCCESS, "System logo set successfully.");
		    } else {
		    	$this->alertMessage(self::MESSAGE_TYPE_ERROR, "Sorry, there was an error uploading your file.");
		    }
		}
		redirect('system_management/system_settings');
	}

	public function testFunction() {
		$sysLogoValue['filename'] = $fileName;
		$sysLogoValue['use_sys_default'] = false;
		$this->operatorglobalsettings->putSettingJson($settingName, $sysLogoValue);
	}

	/**
	 * overview: Set systen logo to default
	 *
	 */
	public function setToDefaultLogo() {
		$settingName = $this->utils->getDefaultLogoSettingName();
		$sysLogoValue = $this->operatorglobalsettings->getSettingJson($settingName);
		$sysLogoValue['use_sys_default'] = true;
		$this->operatorglobalsettings->putSettingJson($settingName, $sysLogoValue);
		$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, "Logo set to system default.");
	}

	public function setPlayerCenterDefaultLanguage() {
		$this->load->model(array('operatorglobalsettings'));
		$this->utils->initialiazeDefaultPlayerCenterLanguage();

		$selectedLanguage = $this->input->post('rdbLanguage');
		$isHideLangOpt = $this->input->post('chkHideLangOption');

		if (!isset($selectedLanguage) || empty($selectedLanguage)) {
			$selectedLanguage = '0';
		}

		if (!isset($isHideLangOpt)) {
			$isHideLangOpt = false;
		}

		$langSetupValue = $this->operatorglobalsettings->getSettingJson(self::PLAYER_CENTER_LANGUAGE_OPERATORSETTING_NAME);
		$langSetupValue['language'] = $selectedLanguage;
		$langSetupValue['hide_lang'] = $isHideLangOpt;

		$this->operatorglobalsettings->putSettingJson(self::PLAYER_CENTER_LANGUAGE_OPERATORSETTING_NAME, $langSetupValue);

		if ($this->endTransWithSucc()) {
			$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang('Save system settings successfully'));
		} else {
			$this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('error.default.db.message'));
		}

		redirect('system_management/system_settings');
	}

	public function getPlayerCenterLanguage() {
		$this->load->model(array('operatorglobalsettings'));
		$langSetup = $this->operatorglobalsettings->getSettingJson(self::PLAYER_CENTER_LANGUAGE_OPERATORSETTING_NAME);

		if (!isset($langSetup)) {
			$this->utils->initialiazeDefaultPlayerCenterLanguage();
			$langSetup = $this->operatorglobalsettings->getSettingJson(self::PLAYER_CENTER_LANGUAGE_OPERATORSETTING_NAME);
		}

		return $langSetup;
	}

	public function setupNewPlayerCenter($setup_type = "manual") {
		if (!$this->permissions->checkPermissions('system_settings')) {
			return $this->showErrorAccess($data['title'], $data['sidebar'], $data['system_settings']);
		}

		# Load models
		$this->load->model(array('system_feature'));

		$userId = $this->authentication->getUserId();
		$username = $this->authentication->getUsername();
		$render = true;

		# Prepare view data
		$data = array(
			'title' => lang('Setup New Player Center'),
			'sidebar' => 'system_management/sidebar',
			'activenav' => 'system_settings',
			'setupType' => $setup_type
		);

		// initial_player_center_features
		$data['system_feature'] = $this->system_feature->get();

		$this->loadDefaultTemplate(array(),
			array('resources/css/general/style.css'),
			array('title' => $data['title'],
				'activenav' => $data['activenav'],
				'userId' => $userId,
				'username' => $username), $data['sidebar'],
				'system_management/setup_new_player_center', $data, $render);
	}

	public function setNewPlayeCenterTemplate(){
		//update player_center_template, validate dir
		$result=['success'=>true];
		$success = false;

		$dir=dirname(__FILE__).'/../../../player/application/views/'.$this->utils->getConfig('new_player_center_default_template');
		if(is_dir($dir)){
			$success = $this->utils->putOperatorSetting('player_center_template', $this->utils->getConfig('new_player_center_default_template'));
		} else {
			$message=lang('Wrong template of player center');
		}

		if(!$success){
			$this->alertMessage(self::MESSAGE_TYPE_ERROR, !empty($message) ? $message : lang('error.default.db.message'));
			redirect('system_management/system_settings');
			$result=['success'=>false];
		}

		$this->returnJsonResult($result);
	}

	public function setNewPlayeCenterLanguage() {
		$this->load->model(array('operatorglobalsettings'));
		$selectedLanguage = $this->input->post('language');

		$result=['success'=>true];
		$success = false;

		if (!isset($selectedLanguage) || empty($selectedLanguage)) {
			$selectedLanguage = '0';
		}
		$langSetupValue = $this->operatorglobalsettings->getSettingJson(self::PLAYER_CENTER_LANGUAGE_OPERATORSETTING_NAME);
		$langSetupValue['language'] = $selectedLanguage;
		$langSetupValue['hide_lang'] = false;

		$success = $this->operatorglobalsettings->putSettingJson(self::PLAYER_CENTER_LANGUAGE_OPERATORSETTING_NAME, $langSetupValue);

		if(!$success){
			$this->alertMessage(self::MESSAGE_TYPE_ERROR, !empty($message) ? $message : lang('error.default.db.message'));
			$result=['success'=>false];
		}

		$this->returnJsonResult($result);
	}

	public function upload_player_logo() {

		if ($this->input->post('setDefaultPlayerLogo')) {
			$this->operatorglobalsettings->syncSettingJson("player_center_logo", null, 'value');
			$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang('Logo set to player default'));
			redirect('system_management/system_settings');
			return;
		}

		$file_header_logo = isset($_FILES['fileToUpload']) ? $_FILES['fileToUpload'] : null;
		$path_logo_image =$this->utils->getLogoTemplatePath();
		$path_logo_image=rtrim($path_logo_image, '/');
		$file_name = 'playercenter_header_logo_'.strtotime(today);
		$this->load->model(array('operatorglobalsettings'));

		if(empty($file_header_logo['size'][0])) {
				$this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('player.mp14'));
				redirect('system_management/system_settings');
				return;
		} else {
			 	$config_logo_image = array(
		            'allowed_types' => 'png',//array("jpg","jpeg","png","gif", "PNG"),
		            'max_size'      => $this->utils->getMaxUploadSizeByte(),
		            'overwrite'     => true,
		            'remove_spaces' => true,
		            'upload_path'   => $path_logo_image,
		        );

		        $file_to_upload[] = array(
					"file_details" => $file_header_logo,
					"upload_path" => $path_logo_image,
					"config_header" => $config_logo_image,
					"file_name" => $file_name,
				);

		        foreach ($file_to_upload as $key => $value) {
		        	$response = $this->multiple_image_uploader->do_multiple_uploads($value['file_details'], $value['upload_path'], $value['config_header'], $value['file_name']);
			        if($response['status'] == "fail" ) {
			        	$this->alertMessage(self::MESSAGE_TYPE_ERROR, $response['message']);
			        	redirect('system_management/system_settings');
						return;
			        }
		        }

		        $this->operatorglobalsettings->syncSettingJson("player_center_logo", $file_name, 'value');
		        $this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang('Player logo set successfully'));
		        redirect('system_management/system_settings');
		}
	}

	public function save_cms_version(){
		//only admin
		if (!$this->permissions->checkPermissions('system_settings')) {
			$this->returnErrorStatus();
			redirect('cms_management/playerCenterSettings');
			return;
		}
		$cms_version = $this->input->post('cms_version');

		if ($cms_version) {
			$this->operatorglobalsettings->syncSettingJson("cms_version", $cms_version , 'value');
			$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang('CMS version successfully update'));
		} else {
			$this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('player.mp14'));
		}

		redirect('system_management/system_settings');
	}

	public function upload_player_favicon() {

		if ($this->input->post('setDefaultPlayerFavicon')) {
			$this->operatorglobalsettings->syncSettingJson("player_center_favicon", null, 'value');
			$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, "Favicon set to player default.");
			redirect('system_management/system_settings');
			return;
		}

		$file_header_favicon = isset($_FILES['fileToUpload']) ? $_FILES['fileToUpload'] : null;
		$path_favicon_image =$this->utils->getFaviconTemplatePath();
		$path_favicon_image=rtrim($path_favicon_image, '/');
		$file_name = 'favicon_'.strtotime(today);
		$this->load->model(array('operatorglobalsettings'));

		if(empty($file_header_favicon['size'][0])) {
				$this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('player.mp14'));
				redirect('system_management/system_settings');
				return;
		} else {
			 	$config_logo_image = array(
		            'allowed_types' => 'ico',//array("jpg","jpeg","png","gif", "PNG"),
		            'max_size'      => $this->utils->getMaxUploadSizeByte(),
		            'overwrite'     => true,
		            'remove_spaces' => true,
		            'upload_path'   => $path_favicon_image,
		        );

		        $file_to_upload[] = array(
					"file_details" => $file_header_favicon,
					"upload_path" => $path_favicon_image,
					"config_header" => $config_logo_image,
					"file_name" => $file_name,
				);

		        foreach ($file_to_upload as $key => $value) {
		        	$response = $this->multiple_image_uploader->do_multiple_uploads($value['file_details'], $value['upload_path'], $value['config_header'], $value['file_name']);
			        if($response['status'] == "fail" ) {
			        	$this->alertMessage(self::MESSAGE_TYPE_ERROR, $response['message']);
			        	redirect('system_management/system_settings');
						return;
			        }
		        }

		        $this->operatorglobalsettings->syncSettingJson("player_center_favicon", $file_name, 'value');
		        $this->alertMessage(self::MESSAGE_TYPE_SUCCESS, "Player favicon set successfully.");
		        redirect('system_management/system_settings');
		}
	}

	public function save_player_center_title(){
		//only admin
		if (!$this->permissions->checkPermissions('system_settings')) {
			$this->returnErrorStatus();
			redirect('system_management/system_settings');
			return;
		}
		$player_center_title = $this->input->post('player_center_title');

		if ($player_center_title) {
			$this->operatorglobalsettings->syncSettingJson("player_center_title", $player_center_title , 'value');
			$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, "Player Center title successfully update.");
		} else {
			$this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('player.mp14'));
		}

		redirect('system_management/system_settings');
	}

	public function post_sync_cashback(){
		$data = array('title' => lang('Sync Cashback'), 'sidebar' => 'system_management/sidebar', 'activenav' => 'sync_game_logs');

		if (!$this->permissions->checkPermissions('manually_calculate_cashback') ||
			!$this->permissions->checkPermissions('dev_functions') ||
			!$this->users->isT1User($this->authentication->getUsername()) ||
			!$this->users->isT1Admin($this->authentication->getUsername())
		) {
			return $this->showErrorAccess($data['title'], $data['sidebar'], $data['activenav']);
		}

		show_error('The function is disabled', 403);

		$cashback_date=$this->input->post('cashback_date');

		$this->utils->debug_log('post_sync_cashback cashback_date', $cashback_date);

		$success = true;
		$dry_run = $this->input->post('dry_run')=='true';
		$dry_run = $dry_run ? 'true' : 'false';

		//run command
		$is_blocked=false;
		$cmd = $this->utils->generateCommandLine('sync_cashback', [$dry_run, $cashback_date], $is_blocked);

		pclose(popen($cmd, 'r'));

		//go back
	    if ($success) {
	        $this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang('Running cashback').'... '.$cashback_date.' '.($dry_run=='true' ? lang('Dry Run') : ''));
	    } else {
	        $this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('error.default.db.message'));
	    }

		redirect('/system_management/other_functions');
	}

	public function post_sync_game_logs($isAll="true"){
		$data = array('title' => lang('Sync Game Logs'), 'sidebar' => 'player_management/sidebar', 'activenav' => 'sync_game_logs');

		if (!$this->permissions->checkPermissions('sync_game_logs')) {
			return $this->showErrorAccess($data['title'], $data['sidebar'], $data['activenav']);
		}

		$by_date_from_str = $this->input->post('by_date_from');
		$by_date_to_str   = $this->input->post('by_date_to');

		//THERE'S A RULE FOR SYNC, DON'T CHANGE IT
		list($min_datetime_str, $max_datetime_str)=$this->utils->getSyncDateRule();
		$this->utils->debug_log('min/max date rule', $min_datetime_str, $max_datetime_str);

		$success = true;
		$msg = null;
		$min_datetime_obj = new DateTime($min_datetime_str);
		$max_datetime_obj = new DateTime($max_datetime_str);
		$by_date_from_obj = new DateTime($by_date_from_str);
		$by_date_to_obj   = new DateTime($by_date_to_str);

		if ($by_date_from_obj < $min_datetime_obj) {
			//wrong start time
			$msg = lang('min datetime is') . $min_datetime_str;
			$success = false;

		}
		if ($by_date_to_obj > $max_datetime_obj) {
			//wrong datetime
			$msg = lang('max datetime is') . $max_datetime_str;
			$success = false;
		}

		//go back
	    if (!$success) {
	       $this->alertMessage(self::MESSAGE_TYPE_ERROR, $msg);
	       return redirect('/system_management/other_functions');
	    }

		$by_game_platform_id = !empty($this->input->post('by_game_platform_id')) ? $this->input->post('by_game_platform_id') : '';
		$playerName = !empty($this->input->post('playerName')) ? $this->input->post('playerName') : '';
		$this->utils->debug_log('by_date_from', $by_date_from_obj, 'by_date_to', $by_date_to_obj, 'by_game_platform_id', $by_game_platform_id);

		$dry_run       = $this->input->post('dry_run')=='true';
		$merge_only    = $this->input->post('merge_only')=='true';
		$only_original = $this->input->post('only_original')=='true';

		$this->load->library(['lib_queue', 'language_function']);
		$this->load->model(['queue_result']);

		//run command
		$caller = $this->authentication->getUserId();
		$state = null;
		$timelimit = 30; //minutes
		$lang = $this->language_function->getCurrentLanguage();
		$token = $this->lib_queue->addRemoteSyncGameLogsJob($by_date_from_str, $by_date_to_str, $by_game_platform_id,
			Queue_result::CALLER_TYPE_ADMIN, $caller, $state, $lang, $playerName, $dry_run, $timelimit,
			$merge_only, $only_original);

		//goto queue page
		redirect('/system_management/common_queue/'.$token);
	}

	public function post_calculate_aff_earnings() {
		$data = array('title' => lang('Sync Game Logs'), 'sidebar' => 'system_management/sidebar',
			'activenav' => 'sync_game_logs');

		if (!$this->permissions->checkPermissions('dev_functions') || !$this->users->isT1User($this->authentication->getUsername())) {
			return $this->showErrorAccess($data['title'], $data['sidebar'], $data['activenav']);
		}

		if (!$this->users->isT1Admin($this->authentication->getUsername())) {
			return $this->showErrorAccess($data['title'], $data['sidebar'], $data['activenav']);
		}

		$startDate = $this->input->post('startDate');
		$endDate = $this->input->post('endDate');
		$username = $this->input->post('username');

		$this->utils->debug_log('startDate', $startDate, 'endDate', $endDate, 'username', $username);

		$success = TRUE;

		$dry_run = $this->input->post('dry_run') == 'true';
		$dry_run = $dry_run ? 'true' : 'false';

		$this->load->library(['lib_queue', 'language_function']);
		$this->load->model(['queue_result']);

		$caller = $this->authentication->getUserId();
		$state 	= null;
		$lang 	= $this->language_function->getCurrentLanguage();

		$token 	= $this->lib_queue->addCalculateAffEarnings($startDate, $endDate, $username, Queue_result::CALLER_TYPE_ADMIN, $caller, $state, $lang, $dry_run);
		redirect('/system_management/common_queue/' . $token);
	}

	public function post_rebuild_games_total($isAll="true"){
		$data = array('title' => lang('Dev Functions'), 'sidebar' => 'system_management/sidebar',
			'activenav' => 'dev_functions');

		if (!$this->permissions->checkPermissions('sync_game_logs') || !$this->users->isT1User($this->authentication->getUsername())) {
			return $this->showErrorAccess($data['title'], $data['sidebar'], $data['activenav']);
		}

		$by_date_from=$this->input->post('by_date_from');
		$by_date_to=$this->input->post('by_date_to');
		$rebuild_hour=$this->input->post('rebuild_hour')=='true';
		$rebuild_minute=$this->input->post('rebuild_minute')=='true';
		$rebuild_hour = $rebuild_hour ? 'true' : 'false';
		$rebuild_minute = $rebuild_minute ? 'true' : 'false';

		$lock_rebuild_reports_range= $this->utils->getConfig('lock_rebuild_reports_range');

		if(!empty($lock_rebuild_reports_range)){
			$from = $this->utils->formatDateForMysql(new DateTime($by_date_from));
			$to = $this->utils->formatDateForMysql(new DateTime($by_date_to));
			if($this->utils->isRebuildReportDateLocked($lock_rebuild_reports_range,$from,$to,$rlt)){
				$this->utils->error_log(sprintf(lang("Regenarate All Report has lock - should not be equal or older than  %s  "),$rlt['cutoff_day']));
				$this->alertMessage(self::MESSAGE_TYPE_ERROR, sprintf(lang("Regenarate All Report has lock - should not be equal or older than  %s  "),$rlt['cutoff_day']));
				return redirect('/system_management/other_functions');
			}
		}

		$this->utils->debug_log('post_rebuild_games_total','by_date_from', $by_date_from, 'by_date_to', $by_date_to, 'rebuild_hour',$rebuild_hour, 'rebuild_minute' , $rebuild_minute);

		$success=true;
		$dry_run=$this->input->post('dry_run')=='true';

		$dry_run= $dry_run ? 'true' : 'false';

		$this->load->library(['lib_queue', 'language_function']);
		$this->load->model(['queue_result']);
		$caller=$this->authentication->getUserId();
		$state=null;
		$lang=$this->language_function->getCurrentLanguage();
		//$commandFunc = 'do_rebuild_games_total_job';
		$funcName = 'rebuild_games_total';
		$callerType = Queue_result::CALLER_TYPE_ADMIN;
		$systemId = Queue_result::SYSTEM_UNKNOWN;

		$params = ['endDateTimeStr' => $by_date_to,
			'fromDateTimeStr' => $by_date_from,
			'rebuild_hour' => $rebuild_hour,
			'rebuild_minute' => $rebuild_minute,
			'dry_run'=>$dry_run,
			];


		//$token = $this->lib_queue->addAnyJob($commandFunc, $funcName, $params, $callerType, $caller, $state, $lang, $systemId);
		//redirect('/system_management/common_queue/' . $token);

		$token=$this->lib_queue->commonAddRemoteJob($systemId,$funcName, $params, $callerType, $caller, $state, $lang);
		//goto queue page
		redirect('/system_management/common_queue/'.$token);
	}

	public function post_rebuild_seamless_balance_history(){
		$data = array('title' => lang('Dev Functions'), 'sidebar' => 'system_management/sidebar',
			'activenav' => 'dev_functions');

		if (!$this->permissions->checkPermissions('sync_game_logs') || !$this->users->isT1User($this->authentication->getUsername())) {
			return $this->showErrorAccess($data['title'], $data['sidebar'], $data['activenav']);
		}

		$by_game_platform_id=$this->input->post('by_game_platform_id');
		$by_date_from=$this->input->post('by_date_from');
		$by_date_to=$this->input->post('by_date_to');
		$rebuild_game_transactions=$this->input->post('rebuild_game_transactions')=='true';
		$rebuild_game_balance_transfers=$this->input->post('rebuild_game_balance_transfers')=='true';
		$rebuild_game_transactions = $rebuild_game_transactions ? 'true' : 'false';
		$rebuild_game_balance_transfers = $rebuild_game_balance_transfers ? 'true' : 'false';

		$lock_rebuild_reports_range= $this->utils->getConfig('lock_rebuild_reports_range');

		if(!empty($lock_rebuild_reports_range)){
			$from = $this->utils->formatDateForMysql(new DateTime($by_date_from));
			$to = $this->utils->formatDateForMysql(new DateTime($by_date_to));
			if($this->utils->isRebuildReportDateLocked($lock_rebuild_reports_range,$from,$to,$rlt)){
				$this->utils->error_log(sprintf(lang("Regenarate All Report has lock - should not be equal or older than  %s  "),$rlt['cutoff_day']));
				$this->alertMessage(self::MESSAGE_TYPE_ERROR, sprintf(lang("Regenarate All Report has lock - should not be equal or older than  %s  "),$rlt['cutoff_day']));
				return redirect('/system_management/other_functions');
			}
		}

		$this->utils->debug_log('post_rebuild_seamless_balance_history','by_date_from', $by_date_from, 'by_date_to', $by_date_to, 'rebuild_game_transactions',$rebuild_game_transactions, 'rebuild_game_balance_transfers' , $rebuild_game_balance_transfers);

		$success=true;
		$dry_run=$this->input->post('dry_run')=='true';

		$dry_run= $dry_run ? 'true' : 'false';

		$this->load->library(['lib_queue', 'language_function']);
		$this->load->model(['queue_result']);
		$caller=$this->authentication->getUserId();
		$state=null;
		$lang=$this->language_function->getCurrentLanguage();

		$funcName = 'rebuild_seamless_balance_history';
		$callerType = Queue_result::CALLER_TYPE_ADMIN;
		$systemId = Queue_result::SYSTEM_UNKNOWN;

		$params = ['endDateTimeStr' => $by_date_to,
			'fromDateTimeStr' => $by_date_from,
			'rebuild_game_transactions' => $rebuild_game_transactions,
			'rebuild_game_balance_transfers' => $rebuild_game_balance_transfers,
			'by_game_platform_id' => $by_game_platform_id,
			'dry_run'=>$dry_run,
			];

		$token=$this->lib_queue->commonAddRemoteJob($systemId,$funcName, $params, $callerType, $caller, $state, $lang);
		//goto queue page
		redirect('/system_management/common_queue/'.$token);
	}

	public function post_batch_sync_balance(){
		$data = array(
			'title' => lang('Dev Functions'),
			'sidebar' => 'system_management/sidebar',
			'activenav' => 'dev_functions'
		);

		// -- Check if user has permission
		if (!$this->permissions->checkPermissions('dev_functions') || !$this->users->isT1User($this->authentication->getUsername())) {
			return $this->showErrorAccess($data['title'], $data['sidebar'], $data['activenav']);
		}

		$params = array(
			'mode' 	 		=> $this->input->post('mode') ?: 'available',
			'dry_run' 	 	=> $this->input->post('dry_run') ? 'true' : 'false',
			'max_number' 	=> $this->input->post('max_number') ?: '10',
			'apiId' 	 	=> $this->input->post('apiId') ?: '',
		);

		$this->utils->debug_log('post_batch_sync_balance','mode', $params['mode'], 'dry_run', $params['dry_run'], 'max_number', $params['max_number'], 'apiId', $params['apiId'] );

		$success = true;

		$this->load->library(['lib_queue', 'language_function']);
		$this->load->model(['queue_result']);

		$caller = $this->authentication->getUserId();
		$state 	= null;
		$lang 	= $this->language_function->getCurrentLanguage();

		$funcName 	= 'batch_sync_balance_by';
		$callerType = Queue_result::CALLER_TYPE_ADMIN;
		$systemId 	= Queue_result::SYSTEM_UNKNOWN;

		$token = $this->lib_queue->addRemoteBatchSyncBalanceByJob($systemId, $funcName, $params, $callerType, $caller, $state, $lang);

		redirect('/system_management/common_queue/'.$token);
	}

	/**
	 * View remote log file content
	 *
	 * @param  string $token
	 * @param  string $logfile
	 * @return HTML template
	 */
	public function view_remote_log($token,$logfile){

		$data = array('title' => lang('View Remote Log'), 'sidebar' => 'system_management/sidebar',
			'activenav' => 'dev_functions');

		$userId = $this->authentication->getUserId();
		$username = $this->authentication->getUsername();

		// -- Check if user has permission
		if (!$this->permissions->checkPermissions('dev_functions') || !$this->users->isT1User($this->authentication->getUsername()) || empty($token) || empty($logfile)) {
			return $this->showErrorAccess($data['title'], $data['sidebar'], $data['activenav']);
		}

		$this->load->model('queue_result');
		$queue_result = $this->queue_result->getResult($token);

		// -- check if token really exists
		if(empty($queue_result))
			return $this->showErrorAccess($data['title'], $data['sidebar'], $data['activenav']);

		// -- get file path
		$logfile = rtrim($logfile,'.log') . '.log';
		$remote_log_file_path = $this->utils->getSharingUploadPath('/remote_logs');
		$file_path = rtrim($remote_log_file_path, '/').'/'.$logfile;
		$file_content = '';

		// -- retrieve file content
		$file_size = @filesize($file_path);
		$file_size_limit = $this->utils->getConfig('remote_log_viewing_file_size_limit');

		if($file_size !== FALSE && $file_size > $file_size_limit){
			$data['filesize_limit'] = $file_size_limit;
		}
		else{
			// -- retrieve file content
			$file_content = @file_get_contents($file_path);

			if($file_content === FALSE) {
				$this->utils->error_log('FAILED TO OPEN FILE: '.$file_path);

				$this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('Remote log file does not exist.'));
				return $this->loadDefaultTemplate(null, null, array('title' => $data['title']), $data['sidebar'], null, null, true);
			}
		}

		// -- prepare data
		$data['file_content'] = $file_content;
		$data['logfile'] = $logfile;
		$data['token'] = $token;


		$this->loadDefaultTemplate(
			['resources/js/highlight.pack.js'],
			['resources/css/general/style.css',
			'resources/css/hljs.tomorrow.css'],
			array('title' => $data['title'],
				'activenav' => $data['activenav'],
				'userId' => $userId,
				'username' => $username), $data['sidebar'],
			'includes/parse_remote_log', $data, true);

		// }
	}

	public function other_functions(){
		$data = array('title' => lang('role.310'), 'sidebar' => 'system_management/sidebar',
			'activenav' => 'system');

		if (!$this->permissions->checkPermissions('dev_functions') && !$this->users->isT1User($this->authentication->getUsername())) {
			return $this->showErrorAccess($data['title'], $data['sidebar'], $data['activenav']);
		}

        $this->load->model(array('operatorglobalsettings','external_system', 'marketing', 'transactions', 'multiple_db_model'));
		$this->load->library(['lib_queue', 'language_function']);

		$userId = $this->authentication->getUserId();
		$username = $this->authentication->getUsername();
		$render = true;

		$this->addBoxDialogToTemplate();

		$by_date_to = $this->utils->getNowForMysql();
		$by_date_from = (new DateTime($by_date_to))->modify('-1 hour')->format('Y-m-d H').':00:00';

		$lock_rebuild_reports_range = $this->utils->getConfig('lock_rebuild_reports_range');
		if(!empty($lock_rebuild_reports_range)){
			$data['lock_rebuild_reports_range'] = $this->utils->isRebuildReportDateLocked($lock_rebuild_reports_range,null,null,$rlt,true);
		}else{
			$data['lock_rebuild_reports_range'] = null;
		}
		$data['by_date_from']  = $by_date_from;
		$data['by_date_to']    = $by_date_to;
		$data['cashback_date'] = null;
		$data['playerName']    = null;
		//show queue server
		$data['queue_server_info'] = $this->utils->getConfig('rabbitmq_server');
        $data['redis_channel_key'] = $this->lib_queue->getRabbitmqQueueKey();


        // -- get all game API from DB
        $all_apis = $this->external_system->getAllGameApisWithSystemNameAndStatus() ?: array();

        // -- get all ignored APIs via config
		$ignore_apis = $this->utils->getConfig('og_sync_ignore_api') ?: array();

		// -- get all APIs that are currently under maintenance
        $maintenance_apis = array_column($this->external_system->getGameApiMaintenanceOrPauseSyncing('maintenance_mode'), 'id');

        // -- get all paused syncing APIs via DB
        $pauseSync_apis = array_column($this->external_system->getGameApiMaintenanceOrPauseSyncing('pause_sync'), 'id');

    	// -- get all active java synced apis
    	$java_synced_apis = array_column($this->external_system->getAllApiActivelySyncedThruJava(), 'id');

		$game_api_with_free_rounds = $this->utils->getConfig('game_api_with_free_rounds') ? $this->utils->getConfig('game_api_with_free_rounds') : [];
		$games_with_free_rounds = $this->external_system->getGamesByIds($game_api_with_free_rounds);
		$data['games_with_free_rounds'] = $games_with_free_rounds;


    	foreach ($all_apis as $all_api_key => &$API) {
    		$API['sync_status'] = null;
    		$is_java = false;

    		// -- Set all Java APIs
    		if(in_array($API['id'], $java_synced_apis)){
    			$is_java = true;
    			$API['sync_status'] = lang('Java');
    		}

    		// -- Check if api PHP syncing is paused or not
    		if( !in_array($API['id'], $ignore_apis) &&
				!in_array($API['id'], $maintenance_apis) &&
				!in_array($API['id'], $pauseSync_apis) &&
				$API['status'] == External_system::STATUS_NORMAL) {
				if($is_java)
					$API['sync_status'] = lang('Java & PHP');
				else
					$API['sync_status'] = lang('PHP');
			} elseif (!$is_java){
				$API['sync_status'] = lang('Paused');
			}
    	}

		$this->load->library(array('player_manager'));
        $data['api_with_sync_method'] = $all_apis;

		$data['allLevels'] = $this->group_level->getAllPlayerLevelsDropdown();
		$tags_all = $this->player->getAllTags();
		$tags = [];
		if(!empty($tags_all)){
			foreach ($tags_all as $tag) {
				$tags[$tag['tagId']] = $tag['tagName'];
			}
		}

		$data['tags']  = $tags;

        $sort = "vipSettingId";
        $vipSettingList = $this->group_level->getVIPSettingList($sort, null, null);
        $vipSettingListKV = [];
        $vipSettingListKV[0] = lang('NONE');
        $_formater = '%s - %s %s '; // 3 params
        foreach($vipSettingList as $_row){
            $_lang_level = lang('level');
            if($_row['groupLevelCount'] > 1){
                $_lang_level = lang('levels');
            }

            $_labeStr = sprintf($_formater, lang($_row['groupName']), $_row['groupLevelCount'], $_lang_level);
            $vipSettingListKV[$_row['vipSettingId']] = $_labeStr;
        }
        $data['vipSettingList']  = $vipSettingListKV;

        $dlu_time = $this->transactions->getDashboardLastUpdateTime();
        $data = array_merge($data, $dlu_time);

        if ($this->utils->getConfig('enable_cancel_game_round')) {
            $cancel_round_game_apis = [];

            foreach ($all_apis as $api) {
                if ($api['status']) {
                    $game_api = $this->utils->loadExternalSystemLibObject($api['id']);

                    if ($game_api) {
                        if (method_exists($game_api, 'cancelGameRound')) {
                            array_push($cancel_round_game_apis, [
                                'game_platform_id' => $api['id'],
                                'game_platform_name' => $api['system_name'],
                            ]);
                        }
                    }
                }
            }

            $data['cancel_round_game_apis'] = $cancel_round_game_apis;
        }

		$this->loadDefaultTemplate(
			['resources/js/highlight.pack.js',
			'resources/js/datatables.min.js',
            'resources/js/select2.min.js',
            'resources/js/player_management/vipsetting_sync.js',
            'resources/third_party/bootstrap-multiselect-master/dist/js/bootstrap-multiselect.js'],
			['resources/css/general/style.css',
			'resources/css/hljs.tomorrow.css',
			'resources/css/datatables.min.css',
            'resources/css/select2.min.css',
            'resources/third_party/bootstrap-multiselect-master/dist/css/bootstrap-multiselect.css'],
			array('title' => $data['title'],
				'activenav' => $data['activenav'],
				'userId' => $userId,
				'username' => $username), $data['sidebar'],
			'system_management/view_other_functions', $data, $render);
	}

	public function view_task_list(){
		$data = array('title' => lang('View Task List'), 'sidebar' => 'system_management/sidebar',
			'activenav' => 'system');

		if (!$this->permissions->checkPermissions('view_task_list')) {
            return $this->error_redirection();
        }

        $userId = $this->authentication->getUserId();
		$username = $this->authentication->getUsername();
		$render = true;

		$start_today = date("Y-m-d") . ' 00:00:00';
		$end_today = date("Y-m-d") . ' 23:59:59';
		$data['conditions'] = $this->safeLoadParams(array(
			'datetime_from' => $this->utils->getTodayForMysql().' '.Utils::FIRST_TIME,
			'datetime_to' => $this->utils->getTodayForMysql().' '.Utils::LAST_TIME,
			'username' => '',
		));

		$data['export_report_permission']=!!$this->permissions->checkPermissions('export_task_list_report');

		$this->addBoxDialogToTemplate();

		$this->loadDefaultTemplate([
				'resources/js/datatables.min.js',
			],
			['resources/css/general/style.css',
			'resources/css/datatables.min.css'],
			array('title' => $data['title'],
				'activenav' => $data['activenav'],
				'userId' => $userId,
				'username' => $username), $data['sidebar'],
			'system_management/view_task_list', $data, $render);
	}

	public function common_queue_syncgamelogs($token){
		$data = array('title' => lang('Task Progress'), 'sidebar' => 'player_management/sidebar',
			'activenav' => 'view_task_list');
		$userId = $this->authentication->getUserId();
		$username = $this->authentication->getUsername();
		$render = true;

		if (empty($userId)) {
			return $this->showErrorAccess($data['title'], $data['sidebar'], $data['activenav']);
		}

		$this->load->model(['queue_result']);
		$data['result_token']=$token;
		$affId = $this->getSessionAffId();
		if(!empty($affId)){
			$this->template->write_view('nav_right', 'affiliate/navigation');
		}
		$this->loadDefaultTemplate(array(),
			array('resources/css/general/style.css'),
			array('title' => $data['title'],
				'activenav' => $data['activenav'],
				'userId' => $userId,
				'username' => $username), $data['sidebar'],
			'includes/game_logs_task_progress', $data, $render);
	}

	public function common_queue($token){
		$data = array('title' => lang('Task Progress'), 'sidebar' => 'system_management/sidebar',
			'activenav' => 'view_task_list');
		$userId = $this->authentication->getUserId();
		$username = $this->authentication->getUsername();
		$render = true;

		if (empty($userId)) {
			return $this->showErrorAccess($data['title'], $data['sidebar'], $data['activenav']);
		}

		$this->load->model(['queue_result']);

		$data['result_token']=$token;

		$affId = $this->getSessionAffId();
		if(!empty($affId)){
			$this->template->write_view('nav_right', 'affiliate/navigation');
		}

		//load function template
		$data['result_template']='common_task_result.php';
		$data['func_name']=null;
		//load queue first
		$qRlt=$this->queue_result->getResult($token);
		$data['is_remote_export'] = substr($qRlt['func_name'], 0, 6) == 'remote';
		$data['is_remote_sync_game_logs'] = strpos($qRlt['func_name'], 'sync_game_logs');
		if(!empty($qRlt)){
			//check file exists
			$viewfile=$qRlt['func_name'].'_result.php';
			if(file_exists(APPPATH.'/views/includes/'.$viewfile)){
				$data['result_template']=$viewfile;
			}

			$data['func_name']=lang($qRlt['func_name']);
		}else{
			$this->utils->error_log('not found token', $token);
			return $this->showErrorAccess($data['title'], $data['sidebar'], $data['activenav']);
		}

		$this->loadDefaultTemplate(
			['resources/js/highlight.pack.js'],
			['resources/css/general/style.css',
			'resources/css/hljs.tomorrow.css'],
			array('title' => $data['title'],
				'activenav' => $data['activenav'],
				'userId' => $userId,
				'username' => $username), $data['sidebar'],
			'includes/task_progress', $data, $render);
	}

	public function check_queue($token){
		$rlt=['success'=>true, 'done'=>false];
		$this->load->model(['queue_result']);
		$queue_result=$this->queue_result->getResult($token);
		$rlt['queue_result']=$this->utils->decodeJson($queue_result['result']);
		$rlt['full_params']=$this->utils->decodeJson($queue_result['full_params']);
		$rlt['final_result']=$this->utils->decodeJson($queue_result['final_result']);
		// extract log file
		if(!empty($rlt['full_params']) && array_key_exists('_log_file', $rlt['full_params'])){
			$rlt['queue_result']['_log_file']=$rlt['full_params']['_log_file'];
		}

		// $log_server_link=$this->utils->getConfig('log_server_link');

		// if(!empty($rlt['queue_result']) && is_array($rlt['queue_result'])){
			//add link to request id
			// foreach ($rlt['queue_result'] as &$qRlt) {
			// 	if(isset($qRlt['request_id']) && !empty($qRlt['request_id'])){
			// 		$link=str_replace('{{request_id}}', $qRlt['request_id'], $log_server_link);
			// 		$qRlt['request_id']=["link"=>$link, "id"=>$qRlt['request_id']];
			// 	}
			// }
		// }

		$rlt['done']=isset($queue_result['status']) ? $queue_result['status']==Queue_result::STATUS_DONE : false;

		$paramsAndResult=['full_params'=>$rlt['full_params'], 'result'=>$rlt['queue_result'], 'final_result'=>$rlt['final_result']];

		$rlt['queue_original_result']=json_encode($paramsAndResult,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
		$rlt['status']=@$queue_result['status'];
		$this->returnJsonResult($rlt);
	}

	public function view_resp_result(){
		$data = array('title' => lang('View Response Result'), 'sidebar' => 'system_management/sidebar',
			'activenav' => 'system');

		if (!$this->permissions->checkPermissions('view_resp_result') || !$this->users->isT1User($this->authentication->getUsername())) {
			return $this->showErrorAccess($data['title'], $data['sidebar'], $data['activenav']);
		}

		$userId = $this->authentication->getUserId();
		$username = $this->authentication->getUsername();
		$render = true;

		$start_today = date("Y-m-d") . ' 00:00:00';
		$end_today = date("Y-m-d") . ' 23:59:59';
		$data['conditions'] = $this->safeLoadParams(array(
			'response_table' => '',
			'datetime_from' => $this->utils->getTodayForMysql().' '.Utils::FIRST_TIME,
			'datetime_to' => $this->utils->getTodayForMysql().' '.Utils::LAST_TIME,
			'api_id' => '',
			'method'=>'',
			'order_id'=>'',
			'result_id'=>'',
			'mobile'=>'',
			'email'=>'',
			'username'=>'',
            'flag'=>'',
			'no_query_balance'=>'',
		));

		$data['conditions']['show_sync_data']=$this->safeGetParam('show_sync_data', true, true);

		$data['conditions']['show_gamegateway_api']=$this->safeGetParam('show_gamegateway_api', true, true);

		$this->utils->debug_log($data['conditions']);

		$data['apiMap'] = $this->utils->getAllSystemMap();
		$data['apiMap']['game'] = $this->utils->getGameSystemMap();
		$data['apiMap']['payment'] = $this->utils->getPaymentSystemMap();
		//This is not used anymore.
		$data['export_report_permission']=!!$this->permissions->checkPermissions('export_report');

		asort($data['apiMap']['game']);
		asort($data['apiMap']['payment']);

		$this->addBoxDialogToTemplate();

		$this->loadDefaultTemplate([
				'resources/js/datatables.min.js',
			],
			['resources/css/general/style.css',
			'resources/css/datatables.min.css'],
			array('title' => $data['title'],
				'activenav' => $data['activenav'],
				'userId' => $userId,
				'username' => $username), $data['sidebar'],
			'system_management/view_resp_result', $data, $render);
	}

	public function view_sms_api_settings(){
		$data = array('title' => lang('view_sms_api_settings'), 'sidebar' => 'system_management/sidebar',
			'activenav' => 'system');

		if (!$this->permissions->checkPermissions('view_sms_api_settings')) {
			return $this->error_redirection();
		}

		$userId = $this->authentication->getUserId();
		$username = $this->authentication->getUsername();
		$render = true;
		$sms_api_setting_list = $this->operatorglobalsettings->getSystemSettings(self::SMS_API_SETTING_LIST);
		$sms_api_list = $this->utils->getConfig('sms_api');

		$this->utils->debug_log(__METHOD__, 'sms_api_setting_list', $sms_api_setting_list, $sms_api_list);

		$enable_list = [];
		$mode_list = [];
		$map_sms_lang = [];

		foreach ($sms_api_list as $key => $api) {
			$map_sms_lang[] = lang('operator_settings.sms_api_list.'.$key);
		}

		foreach ($sms_api_setting_list as $key => $setting) {
			$set = json_decode($setting['value'],true);

			$data[$key.'_enabled'] = $set['enabled'];
			$data[$key.'_mode'] = $set['mode'];
			$data[$key.'_single'] = $set['single'];
			$data[$key.'_rotation'] = $set['rotation'];

			$mode_list[] = $key.'_mode';
			$enable_list[] = $key.'_enabled';
		}

		$data['enable_list'] = $enable_list;
		$data['mode_list'] = $mode_list;
		$data['sms_api_list'] = empty($sms_api_list) ? [] : $sms_api_list;

		$sms_api_rotation_order_settings =  $this->operatorglobalsettings->getSettingValueWithoutCache('sms_api_rotation_order_settings');
		$set = json_decode($sms_api_rotation_order_settings,true);
		$api_rotation_order = empty($set['rotationOrder']) ? $sms_api_list : $set['rotationOrder'];

		if (count($sms_api_list) > count($api_rotation_order)) {
			$api_rotation_order = array_merge($api_rotation_order,array_diff($sms_api_list,$api_rotation_order));
		}else{
			$diff_api = array_diff($api_rotation_order,$sms_api_list);
			foreach ($diff_api as $key => $api_name) {
				unset($api_rotation_order[$key]);
			}
		}

		$data['map_sms_lang'] = array_combine($sms_api_list,$map_sms_lang);
		$data['use_random_order'] = $set['random'];
		$data['api_rotation_order'] = $api_rotation_order;

		$this->utils->debug_log(__METHOD__, 'settings', $sms_api_list, $sms_api_rotation_order_settings, $set, $data);

		// var_dump($data);
		$this->addBoxDialogToTemplate();
		$this->loadDefaultTemplate([
				'resources/js/datatables.min.js',
				'resources/js/system_management/system_management.js'
			],
			['resources/css/general/style.css',
			'resources/css/datatables.min.css','resources/css/collapse-style.css','resources/css/jquery-checktree.css'],
			array('title' => $data['title'],
				'activenav' => $data['activenav'],
				'userId' => $userId,
				'username' => $username), $data['sidebar'],
			'system_management/view_sms_api_settings', $data, $render);
	}

	/**
     *
     * $POST saveSmsApiSettings
     * @return void
     */
    public function saveSmsApiSettings() {
        if (!$this->permissions->checkPermissions('edit_sms_api_settings')) {
			$this->error_access();
		} else {
			$this->load->model('operatorglobalsettings');
			$this->utils->debug_log(__METHOD__, 'post:', $this->input->post());

			foreach (self::SMS_API_SETTING_LIST as $key) {

				$settings = [];
				$settings['enabled'] = $this->input->post($key.'_enabled');
				$settings['mode'] = $this->input->post($key.'_mode');
				$settings['single'] = $this->input->post($key.'_single');
				$settings['rotation'] = is_array($this->input->post($key.'_rotation')) ? $this->input->post($key.'_rotation') : array();;

				$this->utils->debug_log(__METHOD__, 'syncSettingJson setting ', $settings);
				$this->operatorglobalsettings->syncSettingJson($key, $settings);
			}

			$use_random_order = $this->input->post('use_random_order');
			$api_rotation_order =  is_array($this->input->post('api_rotation_order')) ? $this->input->post('api_rotation_order') : array();

			$params = [];
			$params['random'] = $use_random_order;
			$params['rotationOrder'] = $api_rotation_order;
			$this->operatorglobalsettings->syncSettingJson('sms_api_rotation_order_settings',$params);

	        $message = lang('view_sms_api_settings') . ' ' . lang('con.pym11');
			$this->saveAction(self::ACTION_MANAGEMENT_TITLE, 'Update SMS API Settings', "User " . $this->authentication->getUsername() . " has successfully update sms api settings.");

	        $this->alertMessage(self::MESSAGE_TYPE_SUCCESS, $message);
	        redirect('system_management/view_sms_api_settings');
		}
    }


	public function view_sms_report(){
		$data = array('title' => lang('View SMS Report'), 'sidebar' => 'system_management/sidebar',
			'activenav' => 'system');

		if (!$this->permissions->checkPermissions('view_sms_report')) {
			return $this->error_redirection();
		}

		$userId = $this->authentication->getUserId();
		$username = $this->authentication->getUsername();
		$render = true;

		$start_today = date("Y-m-d") . ' 00:00:00';
		$end_today = date("Y-m-d") . ' 23:59:59';
		$data['conditions'] = $this->safeLoadParams(array(
			'datetime_from' => $this->utils->getTodayForMysql().' '.Utils::FIRST_TIME,
			'datetime_to' => $this->utils->getTodayForMysql().' '.Utils::LAST_TIME,
			'mobile'=>'',
            'flag'=>'',
		));


		// This is not used anymore.
		$data['export_report_permission']=!!$this->permissions->checkPermissions('export_report');

		$this->addBoxDialogToTemplate();

		$this->loadDefaultTemplate([
				'resources/js/datatables.min.js',
			],
			['resources/css/general/style.css',
			'resources/css/datatables.min.css'],
			array('title' => $data['title'],
				'activenav' => $data['activenav'],
				'userId' => $userId,
				'username' => $username), $data['sidebar'],
			'system_management/view_sms_report', $data, $render);
	}

	public function view_smtp_api_report(){
		$data = array('title' => lang('View SMTP API Report'), 'sidebar' => 'system_management/sidebar',
			'activenav' => 'system');

		if (!$this->permissions->checkPermissions('view_smtp_api_report')) {
			return $this->error_redirection();
		}

		$userId = $this->authentication->getUserId();
		$username = $this->authentication->getUsername();
		$render = true;

		$start_today = date("Y-m-d") . ' 00:00:00';
		$end_today = date("Y-m-d") . ' 23:59:59';
		$data['conditions'] = $this->safeLoadParams(array(
			'datetime_from' => $this->utils->getTodayForMysql().' '.Utils::FIRST_TIME,
			'datetime_to' => $this->utils->getTodayForMysql().' '.Utils::LAST_TIME,
			'username'=>'',
			'email'=>'',
            'flag'=>'',
		));


		$data['export_report_permission']=!!$this->permissions->checkPermissions('export_report');

		$this->addBoxDialogToTemplate();

		$this->loadDefaultTemplate([
				'resources/js/datatables.min.js',
				'resources/js/system_management/system_management.js' ,
			],
			['resources/css/general/style.css',
			'resources/css/datatables.min.css'],
			array('title' => $data['title'],
				'activenav' => $data['activenav'],
				'userId' => $userId,
				'username' => $username), $data['sidebar'],
			'system_management/view_smtp_report', $data, $render);
	}

	public function response_result_detail(){

		if (!$this->permissions->checkAnyPermissions(['view_resp_result', 'transfer_request']) ) {
			return show_error('No permission', 403);
		}

		$result=['success'=>false, 'message'=>lang('Not found')];
		$id=$this->input->get('id');
		if(!empty($id)){

			$this->load->model(['response_result']);

			$rlt=$this->response_result->getRespResultByTableField($id);
			$rlt['original_content']=json_encode(json_decode(@$rlt['content']), JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_PARTIAL_OUTPUT_ON_ERROR);
			$rlt['resp_file_id']=$id;

			$result['success']=true;
			$result['message']=null;
			$result['response_result']=$rlt;
		}

		$this->returnJsonResult($result);
	}

	public function risk_score_setting(){

		$data = array('title' => lang('risk score'), 'sidebar' => 'system_management/sidebar',
			'activenav' => 'system');

		if (!$this->permissions->checkPermissions('risk_score_settings')) {
			return $this->error_redirection();
		}

		$this->load->model(array('risk_score_model'));

		$data['risk_score'] = $this->risk_score_model->getRiskScoreCategory();
		//var_dump($data['risk_score_category']);die();
		$userId = $this->authentication->getUserId();
		$username = $this->authentication->getUsername();
		$render = true;

		$this->loadDefaultTemplate(
			array(
				'resources/js/highlight.pack.js',
				'resources/js/ace/ace.js',
				'resources/js/ace/mode-json.js',
				'resources/js/ace/theme-tomorrow.js'
			),
			array(
				'resources/css/general/style.css',
				'resources/css/hljs.tomorrow.css'
			),
			array('title' => $data['title'],
				'activenav' => $data['activenav'],
				'userId' => $userId,
				'username' => $username), $data['sidebar'],
			'system_management/view_risk_score', $data, $render);
	}

	public function update_risk_score_setting(){
		$input = $this->input->post();
		$this->load->model(array('risk_score_model'));

		foreach ($input as $key => $value) {
			$data['rules'] = $value;
				$this->risk_score_model->updateRiksScoreByCategoryName($key,$data);
		}

		$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang('Save system settings successfully'));
	}

	public function kyc_setting(){

		$data = array('title' => lang('KYC'), 'sidebar' => 'system_management/sidebar',
			'activenav' => 'system');

		if (!$this->permissions->checkPermissions('kyc_settings')) {
			return $this->error_redirection();
		}

		$this->load->model(array('kyc_status_model','risk_score_model'));

		$kyc_list = $this->kyc_status_model->getAllKycStatus();
		$risk_score_info = $this->risk_score_model->getRiskScoreInfo(self::RC);
		$risk_score_list = json_decode($risk_score_info['rules'],true);

		$data['kyc_list'] = $kyc_list;
		$data['risk_score_list'] = $risk_score_list;
		$data['target_function'] = $this->getTargetFunctionList();
		$data['renderChart'] = $this->render_kyc_riskscore_chart();
		//echo "<pre>";print_r($data['renderChart']);die();
		$lang = $this->language_function->getCurrentLanguage();

		if(!empty($data['kyc_list'])){
			foreach ($data['kyc_list'] as $key => $value) {
				switch ($lang) {
					case 1:
						$data['kyc_list'][$key]['description'] = $value['description_english'];
						break;
					case 2:
						$data['kyc_list'][$key]['description'] = $value['description_chinese'];
						break;
					case 3:
						$data['kyc_list'][$key]['description'] = $value['description_indonesian'];
						break;
					case 4:
						$data['kyc_list'][$key]['description'] = $value['description_vietnamese'];
						break;
					default:
						$data['kyc_list'][$key]['description'] = $value['description_english'];
						break;
				}
			}
		}


		$userId = $this->authentication->getUserId();
		$username = $this->authentication->getUsername();
		$render = true;

		$this->loadDefaultTemplate(
			array(
				'resources/js/system_management/kyc_settings.js'
			),
			array(
				'resources/css/general/style.css',
				'resources/css/hljs.tomorrow.css'
			),
			array('title' => $data['title'],
				'activenav' => $data['activenav'],
				'userId' => $userId,
				'username' => $username), $data['sidebar'],
			'system_management/view_kyc', $data, $render);
	}

	public function post_adjust_game_report(){

		$data = array('title' => lang('Sync Game Logs'), 'sidebar' => 'system_management/sidebar',
			'activenav' => 'sync_game_logs');

		$password=$this->input->post('password');

		//permission and only allow superadmin
		if (!$this->permissions->checkPermissions('sync_game_logs') || !$this->authentication->isSuperAdmin()
				|| $password!=$this->utils->getConfig('password_adjust_game_report')) {
			return $this->showErrorAccess($data['title'], $data['sidebar'], $data['activenav']);
		}

		$this->load->model(['total_player_game_minute']);

		//insert into
		$success=$this->dbtransOnly(function() use(&$message){

			$success=false;
			$adjust_year_month=$this->input->post('adjust_year_month');
			$adjust_content=$this->input->post('adjust_content');

			$message='adjust_year_month: '.$adjust_year_month.', adjust_content:'.$adjust_content;


			//format: username,game_description_id,bet,result
			$adjust_arr=str_getcsv($adjust_content, "\n");
			$adjust_totals=[];
			foreach ($adjust_arr as $row) {
				$arr=str_getcsv($row, ',');

				$this->utils->debug_log('load content', $arr, $row);
				$adjust_totals[]=[
					'username'=>$arr[0],
					'game_description_id'=>$arr[1],
					'bet'=>$arr[2],
					'result'=>$arr[3],
				];
			}

			if(!empty($adjust_year_month) && !empty($adjust_content)){
				//insert into totals
				$success=$this->total_player_game_minute->adjust_month_data($adjust_year_month, $adjust_totals);
			}

			return $success;

		});

		//go back
	    if ($success) {
	        $this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang('Adjust Game Report').' '.$message);
	    } else {
	        $this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('error.default.db.message').' '.$message);
	    }

		redirect('/system_management/other_functions');
	}

	public function post_clear_memory_cache($isAll="true"){

		$data = array('title' => lang('Clear memory cache'), 'sidebar' => 'system_management/sidebar',
			'activenav' => 'dev_functions');

		if (!$this->permissions->checkPermissions('dev_functions') || !$this->users->isT1User($this->authentication->getUsername())) {
			return $this->showErrorAccess($data['title'], $data['sidebar'], $data['activenav']);
		}

		$success=$this->utils->deleteCache();

	    if ($success) {
	        $this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang('Clear cache successfully'));
	    } else {
	        $this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('Clear failed'));
	    }

		if($isAll=="true"){
			redirect('/system_management/other_functions');
		}else{
			redirect('/system_management/dev_clear_cache');
		}
	}

	/**
	 * overview : view withdrawal declined category
	 *
	 * detail: view the lists withdrawal declined category
	 * @return load template
	 */
	public function view_withdrawal_declined_category() {
		$data = array('title' => lang('Withdrawal Declined Category'), 'sidebar' => 'system_management/sidebar',
			'activenav' => 'system');
		if (!$this->permissions->checkPermissions('view_withdrawal_declined_category')) {

			$this->error_redirection();
		} else {
			$this->load->model(array('roles'));
			if (($this->session->userdata('sidebar_status') == NULL)) {
				$this->session->set_userdata(array('sidebar_status' => 'active'));
			}

			if (($this->session->userdata('well_crumbs') == NULL)) {
				$this->session->set_userdata(array('well_crumbs' => 'active', 'system_crumb' => 'active'));
			}

			$userId = $this->authentication->getUserId();
			$username = $this->authentication->getUsername();
			$render = true;

			$this->loadDefaultTemplate(
			array('resources/js/datatables.min.js','resources/js/system_management/common_category.js'),
			array('resources/css/general/style.css','resources/css/datatables.min.css'),
			array('title' => $data['title'],
				'activenav' => $data['activenav'],
				'userId' => $userId,
				'username' => $username), $data['sidebar'],
			'system_management/view_withdrawal_declined_category', $data, $render);
		}
	}

	public function add_update_category(){
		$input = $this->input->post();
		$this->load->model(array('common_category'));
		$data = array();
		$response = array();
		if(!empty($input)){
			if(isset($input['category_name'])){
				$data['category_name'] = '_json:'.json_encode($input['category_name']);
			}
			if(isset($input['category_type'])){
				$data['category_type'] = $input['category_type'];
			}
			if(isset($input['order_by'])){
				$data['order_by'] = $input['order_by'];
			}
			if(isset($input['category_id'])){
				$data['id'] = $input['category_id'];
			}

			$queryReponse = $this->common_category->addUpdateCategory($data);
			if($queryReponse){
				$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang('sys.gt25'));

			} else {
				$this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('sys.gt26'));
			}
		}
		$url = "";
		if(isset($input['category_type'])){
			switch ($input['category_type']) {
				case common_category::CATEGORY_WITHRAWAL_DECLINED:
					$url = 'view_withdrawal_declined_category';
					if($queryReponse){
						$this->saveAction(self::ACTION_MANAGEMENT_TITLE, 'Add / Update Withdrawal Declined Category', "User " . $this->authentication->getUsername() . " add / update Withdrawal Declined Category");
					}
					break;
				case common_category::CATEGORY_ADJUSTMENT :
					$url = 'view_adjustment_category';
					if($queryReponse){
						$this->saveAction(self::ACTION_MANAGEMENT_TITLE, 'Add / Update Adjustment Category', "User " . $this->authentication->getUsername() . " add / update Adjustment Category");
					}
					break;
				default:
					$url = 'view_withdrawal_declined_category';
					break;
			}
		} else {
			$url = 'view_withdrawal_declined_category';
		}
		redirect('/system_management/'.$url);
	}

	public function getCategoryById(){
		$input = $this->input->post();
		$response = null;
		if(!empty($input)) {
			if(isset($input['id'])){
				$this->load->model(array('common_category'));
				$response = $this->common_category->getCategoryInfoById($input['id']);
			}
		}
		if ($this->input->is_ajax_request()) {
			$this->returnJsonResult($response);
			return;
		}

		return $response;
	}

	public function updateStatusCategoryById(){
		$input = $this->input->post();
		$response = null;
		if(!empty($input)) {
			if(isset($input['id'])){
				$this->load->model(array('common_category'));
				$queryReponse = $this->common_category->updateCategoryById($input);

				if($queryReponse){
					switch ($input['status']) {
						case common_category::CATEGORY_STATUS_INACTIVE:
							$message = lang('Category status succesfully change to inactive!');
							break;
						case common_category::CATEGORY_STATUS_ACTIVE:
							$message = lang('Category status succesfully change to active!');
							break;
						case common_category::CATEGORY_STATUS_DELETED:
							$message = lang('Successfully Deleted!');
							break;
					}
					$response = array('status' => 'error', 'msg' => $message);
				} else {
					$response = array('status' => 'error', 'msg' => lang('sys.gt26'));
				}
			}
		}
		if ($this->input->is_ajax_request()) {
			$this->returnJsonResult($response);
			return;
		}

		return $response;
	}


	public function delete_adjusted_deposits_game_totals(){
		if (!$this->permissions->checkPermissions('modified_adjusted_deposits_game_totals')) {
			$this->error_redirection();
		} else {
			$this->load->model(['player_basic_amount_list']);

			$request = $this->getInputGetAndPost();

			$response['status'] = false;
			$response['msg'] = '';

			try {
				$this->player_basic_amount_list->db->where('id', $request['data_id']);
				$rlt = $this->player_basic_amount_list->runRealDelete('player_basic_amount_list', $this->db);
				if($rlt){
					$response['status'] = true;
					$response['msg'] = lang('Done');
				}
			} catch (Exception $ex) {
				$response['result_code'] = Player_basic_amount_list::SYNC_RESULT_CODE_UNKNOWN_ERROR;
				$response['status'] = false;
				$response['msg'] = $ex->getMessage();
			}
		}
		return $this->returnJsonResult($response);
	}

	/**
	 * Update / Insert data into the data-table,"player_basic_amount_list"
	 *
	 * @return void
	 */
	public function sync_adjusted_deposits_game_totals(){
		if (!$this->permissions->checkPermissions('modified_adjusted_deposits_game_totals')) {
			$this->error_redirection();
		} else {
			$this->load->model(['player_basic_amount_list']);

			$request = $this->getInputGetAndPost();


			$this->utils->debug_log('sync_adjusted_deposits_game_totals.request:', $request);
			if( ! empty($_FILES) ){
				$this->utils->debug_log('sync_adjusted_deposits_game_totals._FILES:', $_FILES
					,'size:' , $_FILES['csv_file_batch_sync']['size']
					,'name:' , $_FILES['csv_file_batch_sync']['name']
					,'type:' , $_FILES['csv_file_batch_sync']['type'] );
			}

			if( ! empty($_FILES['csv_file_batch_sync']['size']) ){
				$is_cvs_batch_insert = true;
			}else{
				$is_cvs_batch_insert = false;
			}

			$response['status'] = false;
			$response['msg'] = '';

			if( ! $is_cvs_batch_insert ){
				// a data update/insert
				$response = array_merge($response, $this->sync_a_data_in_adjusted_deposits_game_totals($request) );
			}else{
				$response = array_merge($response, $this->cvs_batch_insert_in_adjusted_deposits_game_totals($_FILES['csv_file_batch_sync']) );
			}
			return $this->returnJsonResult($response);
		}
	} // EOF sync_adjusted_deposits_game_totals

	public function sync_a_data_in_adjusted_deposits_game_totals($request){
		$this->load->model(['player_basic_amount_list', 'player_model']);
		$response['status'] = null;
		$response['msg'] = '';

		$isExist = null;
		if( ! empty( $request['data_id'] ) ){
			// maybe exists
			$isExist = $this->player_basic_amount_list->isFieldExist('id', $request['data_id']);
		}
		$isUsernameExist = null;
		if( ! empty( $request['player_username'] ) ){
			$isUsernameExist = $this->player_model->getPlayerArrayByUsername($request['player_username']);
			if($isUsernameExist){
				$isExist = true;
			}
		}

		$this->utils->debug_log('sync_a_data_in_adjusted_deposits_game_totals.request:', $request);
		$data = [];
		if(isset($request['player_username'])){
			$data['player_username'] = $request['player_username'];
		}
		if(isset($request['total_bet_amount'])){
			$data['total_bet_amount'] = $request['total_bet_amount'];
		}
		if(isset($request['total_deposit_amount'])){
			$data['total_deposit_amount'] = $request['total_deposit_amount'];
		}

		try {
			if( empty( $request ) ){
				$response['status'] = false;
				$response['msg'] = 'The data is empty';
				$response['result_code'] = Player_basic_amount_list::SYNC_RESULT_CODE_EMPTY_DATA;
			}else if( ! empty($isExist) && ! empty($request['data_id'])){
				// will be update by id
				$data['id'] = $request['data_id'];
				$result = $this->player_basic_amount_list->syncAmountsByUsername($data, 'id');
				$response['result'] = $result;
			}else{
				// will be insert
				$is_only_username_exist = true;
				$result = $this->player_basic_amount_list->syncAmountsByUsername($data, 'player_username', $is_only_username_exist);
				$response['result'] = $result;
			}


			if( isset($result['is_done']) ){
				if( $result['is_done'] == true){
					$response['before_row'] = $result['before_row'];
					$response['after_row'] = $result['after_row'];
					$response['result_code'] = $result['code'];
					$response['status'] = true;
					$response['msg'] = 'Done';
				}else{
					$response['result_code'] = $result['code'];
					$response['status'] = false;
					$response['msg'] = $this->player_basic_amount_list->getReasonBySyncResultCode($result['code']);
				}
			}
		} catch (Exception $ex) {
			$response['result_code'] = Player_basic_amount_list::SYNC_RESULT_CODE_UNKNOWN_ERROR;
			$response['status'] = false;
			$response['msg'] = $ex->getMessage();
		}
		return $response;
	} // EOF sync_a_data_in_adjusted_deposits_game_totals

	public function cvs_batch_insert_in_adjusted_deposits_game_totals($uploaded_file){
		$this->load->model(['player_basic_amount_list']);
		$response['status'] = null;
		$response['msg'] = '';

		$cvsFileType = strtolower(pathinfo($uploaded_file["name"], PATHINFO_EXTENSION));
		if($cvsFileType != "csv") {
			$response['status'] = false;
			$response['msg'] = 'Sorry, only csv files are allowed.';
		}

		if($response['status'] === null){
			// for the header of the results detail in the CSV file
			$csv_headers = [ lang('Username')
			, lang('Status')
			, lang('Reason')
			, lang('Before Deposit Amount')
			, lang('Before Bet Amount')
			, lang('After Deposit Amount')
			, lang('After Bet Amount')
			];
			$csv_log =[];
			$method_name = 'bulk_adjusted_deposits_game_totals';
			$log_filepath = null;
			$d = new DateTime();
			$token= $d->format('Y_m_d_H_i_s'). '_'. sprintf('%04d', rand(1, 9999) );
			// $token = $this->utils->getRequestId();
			$writeToCsv = true;
			$this->utils->_appendSaveDetailedResultToRemoteLog($token, $method_name, $csv_log, $log_filepath, $writeToCsv, $csv_headers);

			$_csv_file= $uploaded_file["tmp_name"];
			$ignore_first_row = true;
			$cnt = 0;
			$message = null;
			$controller = $this;
			$fp = file($_csv_file);
			$totalCount =  count($fp) - 1;
			$failedCnt = 0 ;
			// $successWithFailCnt=0;
			$successCnt=0;
			$result_info['success']=$this->utils->loopCSV( $_csv_file // #1
													, $ignore_first_row // #2
													, $cnt // #3
													, $message // #4
			, function( $cnt, $tmpData, &$stop_flag=false ) use ( $controller, $method_name, &$failedCnt, &$successCnt, $totalCount, $token ){ // #5

				$request = [];
				$row_data = $tmpData;
				if( ! empty($row_data) ){
					if(count($row_data) == 3){
						$request['player_username'] = $row_data[0];
						$request['total_deposit_amount'] = $row_data[1];
						$request['total_bet_amount'] = $row_data[2];
					}
				}
				$row_rlt = $controller->sync_a_data_in_adjusted_deposits_game_totals($request);
				$controller->utils->debug_log('cvs_batch_insert_in_adjusted_deposits_game_totals.row_rlt:', $row_rlt, 'request:', $request );
				$_username = $row_data[0];
				$_status = ($row_rlt['status'])? '1':'0';
				$_reason = $controller->player_basic_amount_list->getReasonBySyncResultCode($row_rlt['result_code']);

				$_deposit_before = null;
				$_bet_before = null;
				$_deposit_after = null;
				$_bet_after = null;
				if($row_rlt['status']){
					$successCnt++;
					if( ! empty($row_rlt['before_row']['total_deposit_amount']) ){
						$_deposit_before = $row_rlt['before_row']['total_deposit_amount'];
					}else{
						$_deposit_before = 0; // for inserted
					}
					if( ! empty($row_rlt['before_row']['total_bet_amount']) ){
						$_bet_before = $row_rlt['before_row']['total_bet_amount'];
					}else{
						$_bet_before = 0; // for inserted
					}

					$_deposit_after = $row_rlt['after_row']['total_deposit_amount'];
					$_bet_after = $row_rlt['after_row']['total_bet_amount'];
				}else{
					$failedCnt++;
				}
				$csv_log = [ $_username
					, $_status
					, $_reason
					, $_deposit_before
					, $_bet_before
					, $_deposit_after
					, $_bet_after
				];
				$writeToCsv = true;
				$controller->utils->_appendSaveDetailedResultToRemoteLog($token, $method_name, $csv_log, $log_filepath, $writeToCsv, []);
				// $controller->utils->debug_log('sync_a_data_in_adjusted_deposits_game_totals.loopCSV.tmpData:', $tmpData);
			});
			$download_link =  site_url().'remote_logs/'.basename($log_filepath);
			$response['log_filepath'] = $download_link;
			$response['successCnt'] = $successCnt;
			$response['failedCnt'] = $failedCnt;
			$response['totalCount'] = $totalCount;
			$response['status'] = true;
			$response['msg'] = lang('Done');
		} // EOF if($response['status'] === null){...

		return $response;
	}// EOF cvs_batch_insert_in_adjusted_deposits_game_totals

	public function view_adjusted_deposits_game_totals() {
		if (!$this->permissions->checkPermissions('view_adjusted_deposits_game_totals')) {

			$this->error_redirection();
		} else {
			$this->load->model(array('Player_basic_amount_list'));


			$data = array('title' => lang('Adjustment Category')
			, 'sidebar' => 'system_management/sidebar',
			'activenav' => 'system');

			$userId = $this->authentication->getUserId();
			$username = $this->authentication->getUsername();
			$render = true;


			$from = $this->utils->getNowSub(600); // 600 has ref. by the setting of config, moniter_player_login_via_same_ip.query_interval
			$to = $this->utils->getNowForMysql();
			/// alert_command_module::monitorManyPlayerLoginViaSameIp() has referred the params,
			// created_at_enabled_date, logged_in_at_enabled_date, logged_in_at_date_from and logged_in_at_date_to
			$data['conditions'] = $this->safeLoadParams(array(
				'start_date' => $from,
				'end_date' => $to,
				'date_mode' => Player_basic_amount_list::DATE_MODE_UPDATED, // updated, created
				'is_enabled_date' => 1,
				// 'logged_in_at_date_from' => $from,
				// 'logged_in_at_date_to' => $to,
				// 'logged_in_at_enabled_date' => 0,
				// 'search_by' => 2,
				'bet_amount_greater_equal' => '',
				'bet_amount_less_equal' => '',
				'deposit_amount_greater_equal' => '',
				'deposit_amount_less_equal' => '',
				'username' => '',
			));


			// // temp
			// $data['managements'] = [];// $this->reports->getDistinct('management');
			// $data['roles'] = []; //$this->roles->retrieveAllRoles();


			$this->loadDefaultTemplate(
				[ 'resources/js/datatables.min.js'
					, 'resources/js/system_management/adjusted_deposits_game_totals.js'
					, 'resources/js/select2.full.min.js'
				], [ 'resources/css/general/style.css'
					, 'resources/css/datatables.min.css'
					, 'resources/css/select2.min.css'
				], [ 'title' => $data['title']
					, 'activenav' => $data['activenav']
					, 'userId' => $userId
					, 'username' => $username
				], $data['sidebar']
				, 'system_management/view_adjusted_deposits_game_totals', $data, $render
			);
		}
	}

	/**
	 * overview : view adjustment category
	 *
	 * detail: view the lists adjustment category
	 * @return load template
	 */
	public function view_adjustment_category() {
		$data = array('title' => lang('Adjustment Category'), 'sidebar' => 'system_management/sidebar',
			'activenav' => 'system');
		if (!$this->permissions->checkPermissions('view_adjustment_category')) {

			$this->error_redirection();
		} else {
			$this->load->model(array('roles'));
			if (($this->session->userdata('sidebar_status') == NULL)) {
				$this->session->set_userdata(array('sidebar_status' => 'active'));
			}

			if (($this->session->userdata('well_crumbs') == NULL)) {
				$this->session->set_userdata(array('well_crumbs' => 'active', 'system_crumb' => 'active'));
			}

			// -- OGP-10383 | i will comment this line; this is no longer used for view adjustment category
			//$data['managements'] = $this->reports->getDistinct('management');
			$data['roles'] = $this->roles->retrieveAllRoles();

			$userId = $this->authentication->getUserId();
			$username = $this->authentication->getUsername();
			$render = true;

			$this->loadDefaultTemplate(
			array('resources/js/datatables.min.js','resources/js/system_management/common_category.js'),
			array('resources/css/general/style.css','resources/css/datatables.min.css'),
			array('title' => $data['title'],
				'activenav' => $data['activenav'],
				'userId' => $userId,
				'username' => $username), $data['sidebar'],
			'system_management/view_adjustment_category', $data, $render);
		}
	}

	public function download_response_result($id){

		if (!$this->permissions->checkAnyPermissions(['view_resp_result', 'transfer_request']) ) {
			return show_error('No permission', 403);
		}

		//only allow admin
		if(!$this->users->isT1Admin($this->authentication->getUsername())){
			return show_error('No permission', 403);
		}

		$result=['success'=>false, 'message'=>lang('Not found')];
		// $id=$this->input->get('id');
		if(!empty($id)){

			$this->load->model(['response_result']);

			$rlt=$this->response_result->getRespFileInfoByTableField($id);

			// $rlt['original_content']=json_encode(json_decode(@$rlt['content']), JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_PARTIAL_OUTPUT_ON_ERROR);
			//load response result file
			// $rlt['original_content']= !empty($rlt['filepath']) ? $this->utils->loadResponseFile($rlt['filepath']) : $rlt['status_text'];

			// if(!empty($rlt['original_content'])){
			// 	$rlt['original_content']=htmlspecialchars($rlt['original_content']);
			// }

			if(!empty($rlt)){
				$result['success']=true;
				$result['message']=null;
				// $result['response_result']=$rlt;

				$result['content']=$rlt['content'];
			}

		}

		$this->returnJsonResult($result);
	}

	/**
	 * Get the Goder and Decoded Data
	 * @param string $decodeStr The string maybe the following formats,
	 * - http query string
	 * - json string
	 * - xml string
	 * @return array $return The formats,
	 * - $return['data'] srting The Data after decode.
	 * - $return['coder'] string The Coder for decode, ex: parse_str, json_decode and simplexml_load_string.
	 *
	 */
	public function getCoderAndDecodeDataFromEncodeStr($decodeStr){
		$return = array();
		$decodeData = NULL;
		if( empty($decodeData) ){
			$json_decode = json_decode($decodeStr);
			if( ! empty($json_decode) ){
				$decodeData = $decodeStr;
				$coder = 'json_decode';
			}
		}

		if( empty($decodeData) ){
			parse_str($decodeStr, $parse_str);
			if( ! isset($parse_str) ){
				$decodeData = $decodeStr;
				$coder = 'parse_str';
			}
		}

		if( empty($decodeData) ){
			$xml = simplexml_load_string($decodeStr);
			if( ! empty($xml) ){
				$decodeData = $decodeStr;
				$coder = 'simplexml_load_string';
			}
		}

		$return['data'] = $decodeData;
		$return['coder'] = $coder;
		return $return;
	} // EOF getCoderAndDecodeDataFromEncodeStr
	/**
	 * Get MINE for httpCall().
	 * @param string $coder Ref.to result['coder'] of getCoderAndDecodeDataFromEncodeStr().
	 * @param string $data Ref.to result['data'] of getCoderAndDecodeDataFromEncodeStr().
	 * @return string $Mine The Mine-type for httpCall().
	 */
	public function getMineFromCoderAndData($coder, $data){
		$Mine = NULL;
		switch ($coder){
			case 'parse_str':{
				$Mine = \Httpful\Mime::FORM;
				break;
			}
			case 'json_decode':{
				$Mine = \Httpful\Mime::FORM;
				break;
			}
			case 'simplexml_load_string':{
				$Mine = \Httpful\Mime::FORM;
				break;
			}
		}
		return $Mine;
	} // EOF getMineFromCoderAndData

	/**
	 * Resend the request by resp_XXX.id
	 *
	 * OGP-12954 在 payment api callback request 內加上可重複發送的機制
	 *
	 * @param string $respId The table name and resp_XXX.id, ex: "resp_20190703.22"
	 * @return void Echo Json String for tip something, result/view uri after resend at admin.
	 */
	public function resend_response_content($respId = NULL){
		$status = '0'; // 0: false , 1:true.
		$allow_resend = false;

		$isSourceSaleOrderNotFind = true; // Not Foind Source SaleOrder with $respId.
		$isRespIdEmpty = true; // $respId is empty?
		$isloadExternalSystemLib = false; // Check loadExternalSystemLib is loaded.
		$isBuildOrderForResend = false; //

		#check permission
		if (!$this->permissions->checkAnyPermissions(['view_resp_result', 'transfer_request']) ) {
			return show_error('No permission', 403);
		}
		if(!$this->users->isT1Admin($this->authentication->getUsername())){
			return show_error('No permission', 403);
		}

		$this->load->model(['sale_order', 'sale_orders_status_history', 'sale_orders_notes']);
		// like function autoDeposit3rdParty  NOooooo...
		// Resend like that form 3rd payment provider after callback .

		if(!empty($respId)){
			$isRespIdEmpty = false;
		}
		$request = $this->getInputGetAndPost();
		if( ! empty( $request['respId'] ) ){
			$respId = $request['respId'];
			$isRespIdEmpty = false;
		}


		if( ! $isRespIdEmpty ){
			$respFileInfoByTableField = $this->response_result->getRespFileInfoByTableField($respId);

			$jsonStr = json_encode(array());
			if( ! empty($respFileInfoByTableField['content']['content']) ){
				$jsonStr = $respFileInfoByTableField['content']['content'];
			}
			if( ! empty($respFileInfoByTableField['content']['resultText']) ){
				$jsonStr = $respFileInfoByTableField['content']['resultText'];
			}
			$decode_content = json_decode($jsonStr, true);

			$data['response_result_id'] = $respFileInfoByTableField['response_result_id'];
			$responseResultInfo = $this->response_result->getResponseResultInfoById($data['response_result_id']);
			// Get sale_order from secure_id
			$saleOrder = (array)$this->sale_order->getSaleOrderBySecureId($respFileInfoByTableField['content']['related_id2']);

			if( ! empty($saleOrder) ){
				$isSourceSaleOrderNotFind = false;
			}

			/// Clone New sale_order for resend
			// loading ExternalSystemLib
			$controller = $this;
			try {
				list($loaded, $apiClassName) = $controller->utils->loadExternalSystemLib( $saleOrder['system_id'] );
			} catch (Exception $e) {
				$this->utils->error_log('loadExternalSystemLib error, system_id='. $saleOrder['system_id']. ', e: ', $e);
				$loaded = 0;
			}

			if($loaded){
				$isloadExternalSystemLib = true;
			}

			$this->utils->debug_log('loadExternalSystemLib.loaded:', $loaded);
			$this->utils->debug_log('loadExternalSystemLib.apiClassName:', $apiClassName);
			if( ! $isloadExternalSystemLib ){
				$msg = 'loading ExternalSystemLib Failed.';
			}else{
				$api = $this->$apiClassName;

				if(false){ // New order for resend
					$player_id = $saleOrder['player_id'];
					$deposit_amount = $saleOrder['amount'];
					$player_promo_id = NULL; // default
					if( ! empty($saleOrder['player_promo_id'])){
						$player_promo_id = ['player_promo_id'];
					}
					$extra_info_order = json_encode( array() );
					if( ! empty($respFileInfoByTableField['content']['content']) ){
						$extra_info_order = $respFileInfoByTableField['content']['content'];
					}
					$sub_wallet_id = $saleOrder['sub_wallet_id'];
					$group_level_id = $saleOrder['group_level_id'];
					$player_deposit_reference_no = $saleOrder['player_deposit_reference_no'];
					$promo_info = NULL; // default
					if( ! empty($saleOrder['player_promo_id'])){
						$promo_info = $saleOrder['player_promo_id'];
					}
					$deposit_time = NULL; // default
					if( $saleOrder['player_deposit_time'] != '0000-00-00 00:00:00' ){
						$deposit_time = $saleOrder['player_deposit_time'];
					}
					/// Build order for resend - ref. to autoDeposit3rdParty().
					// $orderId for resend
					$orderId = $api->createSaleOrder( $player_id // #1
						, $deposit_amount // #2
						, $player_promo_id // #3
						, $extra_info_order // #4
						, $sub_wallet_id // #5
						, $group_level_id // #6
						, $this->utils->is_mobile() // #7
						, $player_deposit_reference_no // #8
						, $deposit_time // #9
						, $promo_info // #10
					);
				}else{
					// resend under the same order.
					$orderId = $saleOrder['id'];
				}

				if( !empty($orderId) ){
					$isBuildOrderForResend = true; // pass assign source order.
				}
			}

			if( ! $isSourceSaleOrderNotFind
				&& $isBuildOrderForResend
			){
				$actionlog = 'Resend form '. $saleOrder['secure_id']; // $id = source orderId.
				$this->sale_orders_notes->add($actionlog, Users::SUPER_ADMIN_ID, Sale_orders_notes::ACTION_LOG, $orderId);
				if( ! empty($saleOrder['direct_pay_extra_info'])){
					$this->sale_order->updateSaleOrderDirectPayExtraInfoById($orderId, $saleOrder['direct_pay_extra_info']);
				}
				$this->sale_orders_status_history->createSaleOrderStatusHistory($orderId,Sale_order::DEPOSIT_STATUS_RESEND_CALLBACK);
			}

			if( $isBuildOrderForResend
				&& $isloadExternalSystemLib
			){
				// CURL
				// $decode_content['_REQUEST']
				// $decode_content['_SERVER']['REQUEST_METHOD']
				// $url = $api->getNotifyUrl($orderId); // Fatal Error (E_ERROR): Call to private method Abstract_payment_api_yangpay::getNotifyUrl() from context 'System_management'
				$uri = '/callback/process/' . $api->getPlatformCode(). '/'. $orderId;
				if( ! empty($saleOrder['secure_id']) ){ // append source saleOrder.secure_id
					$uri .= '/?reSendBySecureId='. $saleOrder['secure_id']; // append resend info.
				}

				$url = $api->getServerCallbackUrl($uri);
				$mine = \Httpful\Mime::FORM;
				$params = array();
				$reSendType = NULL;
				if( ! empty($decode_content['callbackExtraInfo']) ){
					$params = array_merge($params, $decode_content['callbackExtraInfo']) ;
				}
				$method = $decode_content['_SERVER']['REQUEST_METHOD'];
				if( ! empty($decode_content['_RAW_POST']) ){

					$b64DecodeStr = base64_decode( $decode_content['_RAW_POST'] );
					$resultCoderAndData = $this->getCoderAndDecodeDataFromEncodeStr($b64DecodeStr);
					$mine = $this->getMineFromCoderAndData($resultCoderAndData['coder'], $resultCoderAndData['data']);
					$params = $resultCoderAndData['data']; // give up $params array, just send json data.
					$reSendType = $resultCoderAndData['coder'];
				}
				if( ! empty($decode_content['_RAW_POST_XML_JSON']) ){
					$b64DecodeStr = base64_decode( $decode_content['_RAW_POST_XML_JSON'] );
					$resultCoderAndData = $this->getCoderAndDecodeDataFromEncodeStr($b64DecodeStr);
					$mine = $this->getMineFromCoderAndData($resultCoderAndData['coder'], $resultCoderAndData['data']);
					$params = $resultCoderAndData['data']; // give up $params array, just send xml data.
					$reSendType = $resultCoderAndData['coder'];
				}

				$response = $api->resendHttpCall($orderId, $url, $params, $method, $mine, $reSendType); // Ref. to Abstract_payment_api::resendHttpCall
				if( ! empty($response) ){
					$msg = 'success';
					$status = '1';
				}
			} // EOF if( $isBuildOrderForResend && $isloadExternalSystemLib )
		} // EOF if( ! $isRespIdEmpty )

		$respJson = array();
		$respJson['status'] = $status; // 1 or 0 , completed / failed
		$respJson['decode_content'] = $decode_content;
		if( ! empty($saleOrder) ){

			// patch for parse error
			unset($saleOrder['direct_pay_extra_info']);
			unset($saleOrder['payment_type_name']);
			unset($respJson['decode_content']['_SERVER']);

			if( ! empty($method) ){ // loading ExternalSystemLib Failed.
				$respJson['resentMethod'] = $method;
			}
			if( ! empty($url) ){ // loading ExternalSystemLib Failed.
				$respJson['resentUrl'] = $url;
			}
			if( ! empty($params) ){ // loading ExternalSystemLib Failed.
				$respJson['resentParams'] = $params;
			}
			$respJson['sourceSaleOrder'] = $saleOrder; // Source SaleOrder
		}

		if( ! empty($response) ){
			$respJson['response'] = $response->raw_body;
		}
		$respJson['msg'] = $msg;

		$this->utils->error_log('resend_response_content.respJson:', $respJson);
		$this->returnJsonResult($respJson);

	} // EOF resend_response_content

	/**
	 * Show Response Content By resp_XXX.id
	 *
	 * OGP-12954 在 payment api callback request 內加上可重複發送的機制
	 *
	 * @param string $id The table name and resp_XXX.id, ex: "resp_20190703.22"
	 */
	public function show_response_content($id){
		$this->load->model(['response_result']);

		$data = array('title' => lang('Response Content'), 'sidebar' => 'system_management/sidebar',
			'activenav' => 'view_resp_result');

		#check permission
		if (!$this->permissions->checkAnyPermissions(['view_resp_result', 'transfer_request']) ) {
			return show_error('No permission', 403);
		}
		if(!$this->users->isT1Admin($this->authentication->getUsername())){
			return show_error('No permission', 403);
		}
		if(!empty($id)){ // OGP-12954 新增「重發」按鈕。（ request_api = deposit ）
			$respFileInfoByTableField = $this->response_result->getRespFileInfoByTableField($id);
			$dcodeResultText = $this->getDecodeResultTextByRespId($id);

			if(!empty($respFileInfoByTableField)){
				$result = json_decode($respFileInfoByTableField['content']['resultText'] , true);
				if($result == NULL){
					$data['error'] = lang('Result Text not found');
				}
				else{
					$raw = []; #to check if callback are same
					$content = [];
					$data['fail_msg'] = '';
					$data['error_msg'] = '';
			        foreach ($result as $key => $value) {
			        	if($value == NULL || empty($value) || in_array($value, $raw, true)){
			        		continue;
			        	} elseif($key == 'fail_msg'){
			            	$data['fail_msg'] = $value;
			            } elseif(strpos($key,'error_msg') === 0){
			                $data['error_msg'] .= '<li>'.$value.'</li>';
			            } elseif($key == 'url'){
			            	$content[$key] = $value;
			            } elseif($key == 'content' || $key == '_RAW_POST' || $key == '_RAW_POST_XML_JSON'){
			            	if($key == '_RAW_POST' || $key == '_RAW_POST_XML_JSON'){
			            		$raw[$key] = $value;
			            		$value = base64_decode($value);
							}

			            	if(is_array($value)){
								$content[$key] = htmlentities(json_encode($value, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_PARTIAL_OUTPUT_ON_ERROR));
			            	}
	                        else if(is_null(json_decode($value))){
	                            $content[$key] = htmlentities($value);
	                        }
	                        else{
	                            $content[$key] = htmlentities(json_encode(json_decode($value), JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_PARTIAL_OUTPUT_ON_ERROR));
							}
			            } elseif($key == 'params' || $key == 'callbackExtraInfo' || $key == '_REQUEST'){
			            	$raw[$key] = $value;
			            	if(is_array($value)){
								$content[$key] = htmlentities(json_encode($value, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_PARTIAL_OUTPUT_ON_ERROR));
			            	} else {
			            		$content[$key] = $value;
			            	}
			            } elseif($key == '_SERVER'){
			                $data['server'] = $value;
			            }
			        }
					$data['content'] = $content;
				}
				if( ! empty($dcodeResultText) ){
					$data['dcodeResultText'] = $dcodeResultText;
				}
				// Condition for display resend-panel
				$data['request_api'] = $respFileInfoByTableField['content']['request_api'];
				$data['respIdStr'] = $id;
			}
			else{
				$data['error'] = lang('ID Not found');
			}
		}

		$userId = $this->authentication->getUserId();
		$username = $this->authentication->getUsername();
		$render = true;

		$this->loadDefaultTemplate(['resources/js/datatables.min.js',],
			['resources/css/general/style.css','resources/css/datatables.min.css'],
			array('title' => $data['title'],
				'activenav' => $data['activenav'],
				'userId' => $userId,
				'username' => $username), $data['sidebar'],
			    'system_management/show_response_content', $data, $render);
	} // EOF show_response_content

	/**
	 * Get the Content of Callback from 3rd-party payment while response_results.request_api="deposit".
	 * @param string $respId The table name and resp_XXX.id, ex: "resp_20190703.22"
	 * @return object $decode_content The formats,
	 * - $decode_content['_REQUEST'] For resend params.
	 * - $decode_content['_SERVER'] For resend METHOD, Ref. to $decode_content['_SERVER']['REQUEST_METHOD']
	 * - $decode_content['_RAW_POST'] For handle resend, with httpCall().
	 * - $decode_content['_RAW_POST_XML_JSON'] For handle resend, with httpCall().
	 *
	 */
	public function getDecodeResultTextByRespId($respId){
		$this->load->model(['response_result']);
		$respFileInfoByTableField = $this->response_result->getRespFileInfoByTableField($respId);
			$jsonStr = json_encode(array());
			if( ! empty($respFileInfoByTableField['content']['content']) ){
				$jsonStr = $respFileInfoByTableField['content']['content'];
			}
			if( ! empty($respFileInfoByTableField['content']['resultText']) ){
				$jsonStr = $respFileInfoByTableField['content']['resultText'];
			}
			$decode_content = json_decode($jsonStr, true);
			return $decode_content;
	} // EOF getDecodeResultTextByRespId


    public function show_sms_content($id){
        $data = array('title' => lang('SMS Content'), 'sidebar' => 'system_management/sidebar',
            'activenav' => 'view_sms_result');

        #check permission
        if (!$this->permissions->checkPermissions('view_sms_report')) {
            return $this->showErrorAccess($data['title'], $data['sidebar'], $data['activenav']);
        }

        if(!empty($id)){
            $this->load->model(['response_result']);
            $result = $this->response_result->getRespFileInfoByTableField($id);
            if(!empty($result)){
                $result = json_decode($result['content']['resultText'] , true);
                if($result == NULL){
                    $data['error'] = lang('Result Text not found');
                }
                else{
                    $data['result'] = $result;
                }
            }
            else{
                $data['error'] = lang('ID Not found');
            }
        }

        $userId = $this->authentication->getUserId();
        $username = $this->authentication->getUsername();
        $render = true;
        $this->loadDefaultTemplate(['resources/js/datatables.min.js',],
            ['resources/css/general/style.css','resources/css/datatables.min.css'],
            array('title' => $data['title'],
                'activenav' => $data['activenav'],
                'userId' => $userId,
                'username' => $username), $data['sidebar'],
            'system_management/show_sms_content', $data, $render);
    }

	public function show_smtp_api_content($id){
		$data = array('title' => lang('SMTP API Content'), 'sidebar' => 'system_management/sidebar',
			'activenav' => 'view_smtp_api_result');

		#check permission
		if (!$this->permissions->checkPermissions('view_smtp_api_report')) {
			return $this->showErrorAccess($data['title'], $data['sidebar'], $data['activenav']);
		}

		if(!empty($id)){
			$this->load->model(['response_result']);
			$result = $this->response_result->getRespFileInfoByTableField($id);
			if(!empty($result)){
				$result = json_decode($result['content']['resultText'] , true);
				if($result == NULL){
					$data['error'] = lang('Result Text not found');
				}
				else{
					$data['result'] = $result;
				}
			}
			else{
				$data['error'] = lang('ID Not found');
			}
		}

		$userId = $this->authentication->getUserId();
		$username = $this->authentication->getUsername();
		$render = true;
		$this->loadDefaultTemplate(['resources/js/datatables.min.js',],
			['resources/css/general/style.css','resources/css/datatables.min.css'],
			array('title' => $data['title'],
				'activenav' => $data['activenav'],
				'userId' => $userId,
				'username' => $username), $data['sidebar'],
			'system_management/show_smtp_content', $data, $render);
	}

    public function game_wallet_settings(){
        $data = array('title' => lang('role.393'), 'sidebar' => 'system_management/sidebar',
            'activenav' => 'system');

        #check permission
        if (!$this->permissions->checkPermissions('game_wallet_settings')) {
            return $this->error_redirection();
        }

        $data['game_wallet_settings'] = $this->operatorglobalsettings->getGameWalletSettings();

        $userId = $this->authentication->getUserId();
        $username = $this->authentication->getUsername();
        $render = true;
        $this->loadDefaultTemplate([ 'resources/js/system_management/system_management.js' ] ,
            ['resources/css/general/style.css'],
            array('title' => $data['title'],
                'activenav' => $data['activenav'],
                'userId' => $userId,
                'username' => $username), $data['sidebar'],
            'system_management/game_wallet_settings', $data, $render);
    }

    public function save_game_wallet_settings(){
        $data = array('title' => lang('Game Wallet Settings'), 'sidebar' => 'system_management/sidebar',
            'activenav' => 'game_wallet_settings');

        if (!$this->permissions->checkPermissions('game_wallet_settings')) {
            return $this->showErrorAccess($data['title'], $data['sidebar'], $data['activenav']);
        }

        $game_wallet_settings = json_decode($this->input->post('game_wallet_settings'), TRUE);

        if ($game_wallet_settings) {
            $this->operatorglobalsettings->syncSettingJson("game_wallet_settings", $game_wallet_settings , 'template');
            return $this->returnCommon(self::MESSAGE_TYPE_SUCCESS, "Game Wallet Settings successfully update.");
        } else {
            return $this->returnCommon(self::MESSAGE_TYPE_ERROR, lang('player.mp14'));
        }
    }

	public function run_task_again($oldToken){
		$data = array('title' => lang('Run task again'), 'sidebar' => 'system_management/sidebar',
			'activenav' => 'view_other_functions');

		if (!$this->permissions->checkPermissions('dev_functions') || !$this->users->isT1User($this->authentication->getUsername())) {
			return $this->showErrorAccess($data['title'], $data['sidebar'], $data['activenav']);
		}
		$token=null;
		$oldResult=$this->queue_result->getResult($oldToken);
		if(!empty($oldResult)){
			$token=$oldToken;

			//func name starts with remote
			if(substr($oldResult['func_name'], 0, 6)=='remote'){

				//try copy queue job and run again
				$token=$this->queue_result->runTaskAgain($oldResult, $oldToken);

				if(empty($token)){
					$this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('Run Task Again Failed'));
					$token=$oldToken;
					// return $this->showErrorAccess($data['title'], $data['sidebar'], $data['activenav']);

				}else{
					$this->load->library(['lib_queue']);
					$this->lib_queue->addJobToRabbitMQ($token);
				}
			}else{
				$this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('Only Allow Remote Task to restart'));
			}

		}else{
			$this->utils->error_log('not found token', $oldToken);
			return $this->showErrorAccess($data['title'], $data['sidebar'], $data['activenav']);
		}

		redirect('/system_management/common_queue/'.$token);
	}

	public function post_debug_queue($isAll="true"){
		$data = array('title' => lang('Debug queue'), 'sidebar' => 'system_management/sidebar',
			'activenav' => 'dev_functions');

		if (!$this->permissions->checkPermissions('dev_functions') || !$this->users->isT1User($this->authentication->getUsername())) {
			return $this->showErrorAccess($data['title'], $data['sidebar'], $data['activenav']);
		}

		$this->load->library(['lib_queue']);

		$callerType=Queue_result::CALLER_TYPE_ADMIN;
		$caller=$this->authentication->getUserId();
		$state=null;
		$lang=$this->language_function->getCurrentLanguage();
		$token=$this->lib_queue->addRemoteDebugQueue($callerType, $caller, $state, $lang);
		// $token=$this->lib_queue->triggerAsyncRemoteDebugEvent($callerType, $caller, $state, $lang);

	    if (!empty($token)) {

	        $this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang('Debug queue successfully'));
			return redirect('/system_management/common_queue/'.$token);

	    } else {
	        $this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('Debug queue failed').' '.$resp);
	    }

		if($isAll=="true"){
			redirect('/system_management/other_functions');
		}else{
			redirect('/system_management/dev_debug_queue');
		}
	}

	public function post_debug_async_event($isAll="true"){

		$data = array('title' => lang('Debug queue'), 'sidebar' => 'system_management/sidebar',
			'activenav' => 'dev_functions');

		if (!$this->permissions->checkPermissions('dev_functions') || !$this->users->isT1User($this->authentication->getUsername())) {
			return $this->showErrorAccess($data['title'], $data['sidebar'], $data['activenav']);
		}

		$this->load->library(['lib_queue']);

		$callerType=Queue_result::CALLER_TYPE_ADMIN;
		$caller=$this->authentication->getUserId();
		$state=null;
		$lang=$this->language_function->getCurrentLanguage();
		// $token=$this->lib_queue->addRemoteDebugQueue($callerType, $caller, $state, $lang);
		$token=$this->lib_queue->triggerAsyncRemoteDebugEvent($callerType, $caller, $state, $lang);

	    if (!empty($token)) {

	        $this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang('Debug event successfully'));
			return redirect('/system_management/common_queue/'.$token);

	    } else {
	        $this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('Debug event failed').' '.$token);
	    }

		if($isAll=="true"){
			redirect('/system_management/other_functions');
		}else{
			redirect('/system_management/dev_debug_queue');
		}
	}

	public function post_debug_auto_queue($isAll="true"){

		$data = array('title' => lang('Debug queue'), 'sidebar' => 'system_management/sidebar',
			'activenav' => 'dev_functions');

		if (!$this->permissions->checkPermissions('dev_functions') || !$this->users->isT1User($this->authentication->getUsername())) {
			return $this->showErrorAccess($data['title'], $data['sidebar'], $data['activenav']);
		}

		$this->load->library(['lib_queue']);

		$callerType=Queue_result::CALLER_TYPE_ADMIN;
		$caller=$this->authentication->getUserId();
		$state=null;
		$lang=$this->language_function->getCurrentLanguage();
		// $token=$this->lib_queue->addRemoteDebugQueue($callerType, $caller, $state, $lang);
		$params=['player_name'=>'', 'game_platform_id'=>0];
		$token=$this->lib_queue->addRemoteToKickPlayerByGamePlatformId($params, $callerType, $caller, $state, $lang);

	    if (!empty($token)) {

	        $this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang('Debug auto queue successfully'));
			return redirect('/system_management/common_queue/'.$token);

	    } else {
	        $this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('Debug auto queue failed').' '.$token);
	    }

		if($isAll=="true"){
			redirect('/system_management/other_functions');
		}else{
			redirect('/system_management/dev_debug_queue');
		}
	}

	/**
	 * run remote job for sync t1 gamegateway
	 *
	 */
	public function post_sync_t1_gamegateway($isAll="true"){

		$data = array('title' => lang('Dev Functions'), 'sidebar' => 'system_management/sidebar',
			'activenav' => 'dev_functions');

		if (!$this->permissions->checkPermissions('sync_game_logs') || !$this->users->isT1User($this->authentication->getUsername())) {
			return $this->showErrorAccess($data['title'], $data['sidebar'], $data['activenav']);
		}

		$by_date_from=$this->input->post('by_date_from');
		$by_date_to=$this->input->post('by_date_to');
		$playerName=$this->input->post('playerName');

		$this->utils->debug_log('post_sync_t1_gamegateway','by_date_from', $by_date_from, 'by_date_to', $by_date_to, 'playerName', $playerName);

		$success=true;

		$this->load->library(['lib_queue', 'language_function']);
		$this->load->model(['queue_result']);
		$caller=$this->authentication->getUserId();
		$state=null;
		$lang=$this->language_function->getCurrentLanguage();
		//$commandFunc = 'do_rebuild_games_total_job';
		$funcName = 'sync_t1_gamegateway';
		$callerType = Queue_result::CALLER_TYPE_ADMIN;
		$systemId = Queue_result::SYSTEM_UNKNOWN;

		$params = [
			'date_to' => $by_date_to,
			'date_from' => $by_date_from,
			'playerName' => $playerName,
		];

		$token=$this->lib_queue->commonAddRemoteJob($systemId,$funcName, $params, $callerType, $caller, $state, $lang);
		//goto queue page
		redirect('/system_management/common_queue/'.$token);
	}

	public function manage_currency(){
		$data = array('title' => lang('Manage Currency'), 'sidebar' => 'currency_management/sidebar',
			'activenav' => 'currency');

		if (!$this->permissions->checkPermissions('manage_currency')) {
			return $this->showErrorAccess($data['title'], $data['sidebar'], $data['activenav']);
		}

		$this->load->model(array('operatorglobalsettings'));
		$this->load->library(['lib_queue', 'language_function']);

		$userId = $this->authentication->getUserId();
		$username = $this->authentication->getUsername();
		$render=true;
		$this->addBoxDialogToTemplate();

		$data['conditions']=[
			'player_username'=>$this->input->get('player_username'),
			'admin_username'=>$this->input->get('admin_username'),
			'affiliate_username'=>$this->input->get('affiliate_username'),
			'agency_username'=>$this->input->get('agency_username'),
		];

		$this->loadDefaultTemplate(array(),
			array('resources/css/general/style.css'),
			array('title' => $data['title'],
				'activenav' => $data['activenav'],
				'userId' => $userId,
				'username' => $username), $data['sidebar'],
			'currency_management/manage_currency', $data, $render);
	}

	public function preview_manage_currency(){
		$data = array('title' => lang('Manage Currency'), 'sidebar' => 'currency_management/sidebar',
			'activenav' => 'currency');

		if (!$this->permissions->checkPermissions('manage_currency')) {
			return $this->showErrorAccess($data['title'], $data['sidebar'], $data['activenav']);
		}

		$this->load->model(['player_model', 'users', 'affiliatemodel', 'agency_model']);

		$data['conditions']=[
			'player_username'=>$this->input->post('player_username'),
			'admin_username'=>$this->input->post('admin_username'),
			'affiliate_username'=>$this->input->post('affiliate_username'),
			'agency_username'=>$this->input->post('agency_username'),
		];

		$data['player_id']=null;
		$data['admin_id']=null;
		$data['affiliate_id']=null;
		$data['agency_id']=null;
		$data['error_message']=[];

		//search and confirm username
		if(!empty($data['conditions']['player_username'])){
			$player_id=$this->player_model->getPlayerIdByUsername($data['conditions']['player_username']);
			if(empty($player_id)){
				$data['error_message'][]=lang('Not found player username');
			}else{
				$data['player_id']=$player_id;
			}
		}
		if(!empty($data['conditions']['admin_username'])){
			$admin_id=$this->users->getIdByUsername($data['conditions']['admin_username']);
			if(empty($admin_id)){
				$data['error_message'][]=lang('Not found admin username');
			}else{
				$data['admin_id']=$admin_id;
			}
		}
		if(!empty($data['conditions']['affiliate_username'])){
			$affiliate_id=$this->affiliatemodel->getAffiliateIdByUsername($data['conditions']['affiliate_username']);
			if(empty($affiliate_id)){
				$data['error_message'][]=lang('Not found affiliate username');
			}else{
				$data['affiliate_id']=$affiliate_id;
			}
		}
		if(!empty($data['conditions']['agency_username'])){
			$agency_id=$this->agency_model->getAgentIdByUsername($data['conditions']['agency_username']);
			if(empty($agency_id)){
				$data['error_message'][]=lang('Not found agency username');
			}else{
				$data['agency_id']=$agency_id;
			}
		}
		$data['enable_currency_arr']=$this->input->post('enable_currency');

		if(empty($data['player_id']) && empty($data['admin_id']) && empty($data['affiliate_id']) && empty($data['agency_id'])){
			$this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('please input any username'));
			return redirect('/system_management/manage_currency');
		}

		$userId = $this->authentication->getUserId();
		$username = $this->authentication->getUsername();
		$render=true;
		$this->addBoxDialogToTemplate();

		$this->loadDefaultTemplate(array(),
			array('resources/css/general/style.css'),
			array('title' => $data['title'],
				'activenav' => $data['activenav'],
				'userId' => $userId,
				'username' => $username), $data['sidebar'],
			'currency_management/preview_manage_currency', $data, $render);
	}

	public function post_manage_currency(){
		$data = array('title' => lang('Manage Currency'), 'sidebar' => 'currency_management/sidebar',
			'activenav' => 'currency');

		if (!$this->permissions->checkPermissions('manage_currency')) {
			return $this->showErrorAccess($data['title'], $data['sidebar'], $data['activenav']);
		}

		$this->load->model(['multiple_db_model']);
        $enable_currency_arr=$this->input->post('enable_currency');

        if(empty($enable_currency_arr)){
        	//error
        	return $this->showErrorAccess($data['title'], $data['sidebar'], $data['activenav']);
        }

        $player_id=$this->input->post('player_id');
        $admin_id=$this->input->post('admin_id');
        $affiliate_id=$this->input->post('affiliate_id');
        $agency_id=$this->input->post('agency_id');

        $disableCurrencyKeyList=[];
        $enableCurrencyKeyList=[];
        $availableCurrencyList=$this->utils->getAvailableCurrencyList();
        foreach ($availableCurrencyList as $currencyKey=>$currencyInfo) {
        	if(!in_array($currencyKey, $enable_currency_arr)){
        		$disableCurrencyKeyList[]=$currencyKey;
        	}else{
        		$enableCurrencyKeyList[]=$currencyKey;
        	}
        }

        $db=$this->multiple_db_model->getSuperDBFromMDB();
        $result=null;
        $success=$this->multiple_db_model->runDBTransOnly($db, $result, function($db, &$result)
        		use($disableCurrencyKeyList, $enableCurrencyKeyList, $player_id, $admin_id, $affiliate_id, $agency_id){

        	$success=false;
        	if(!empty($player_id)){
        		$id=$player_id;
        		$type=Multiple_db_model::ID_TYPE_PLAYER_ID;
	        	$success=$this->multiple_db_model->enableDisableCurrencyOnDB($id, $type,
	        		$enableCurrencyKeyList, $disableCurrencyKeyList, $db);
        	}
        	if(!empty($admin_id)){
        		$id=$admin_id;
        		$type=Multiple_db_model::ID_TYPE_USER_ID;
	        	$success=$this->multiple_db_model->enableDisableCurrencyOnSuper($id, $type,
	        		$enableCurrencyKeyList, $disableCurrencyKeyList, $db);
        	}
        	if(!empty($affiliate_id)){
        		$id=$affiliate_id;
        		$type=Multiple_db_model::ID_TYPE_AFFILIATE_ID;
	        	$success=$this->multiple_db_model->enableDisableCurrencyOnSuper($id, $type,
	        		$enableCurrencyKeyList, $disableCurrencyKeyList, $db);
        	}
        	if(!empty($agency_id)){
        		$id=$agency_id;
        		$type=Multiple_db_model::ID_TYPE_AGENCY_ID;
	        	$success=$this->multiple_db_model->enableDisableCurrencyOnSuper($id, $type,
	        		$enableCurrencyKeyList, $disableCurrencyKeyList, $db);
        	}
        	return $success;

        });

        if($success){
			$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang('Save currency permission successfully'));
        }else{
			$this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('Save currency permission failed'));
        }

		return redirect('/system_management/manage_currency');
	}

	public function sync_player_reg_setting_to_mdb(){
		$data = array('title' => lang('Sync Player Registration Settings'), 'sidebar' => 'system_management/sidebar',
			'activenav' => 'view_registration_setting');

		if (!$this->permissions->checkPermissions('registration_setting')) {
			return $this->showErrorAccess($data['title'], $data['sidebar'], $data['activenav']);
		}

		$this->load->model(['multiple_db_model', 'marketing']);
		$type=Marketing::TYPE_PLAYER_REGISTRATION;
		$rlt=null;
		$success=$this->syncPlayerRegSettingsCurrentToMDBWithLock($type, $rlt);

		if($success){
		    $this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang('Sync Player Successfully'));
		}else{
			$errKeys=[];
			foreach ($rlt as $dbKey => $dbRlt) {
				if(!$dbRlt['success']){
					$errKeys[]=$dbKey;
				}
			}
			$errorMessage=implode(',', $errKeys);
		    $this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('Sync Player Failed').': '.$errorMessage);
		}
		redirect('/marketing_management/viewRegistrationSettings');
	}

	public function sync_aff_reg_setting_to_mdb(){
		$data = array('title' => lang('Sync Affiliate Registration Settings'), 'sidebar' => 'system_management/sidebar',
			'activenav' => 'view_registration_setting');

		if (!$this->permissions->checkPermissions('registration_setting')) {
			return $this->showErrorAccess($data['title'], $data['sidebar'], $data['activenav']);
		}

		$this->load->model(['multiple_db_model', 'marketing']);
		$type=Marketing::TYPE_AFFILIATE_REGISTRATION;
		$rlt=null;
		$success=$this->syncAffiliateRegSettingsCurrentToMDBWithLock($type, $rlt);

		if($success){
		    $this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang('Sync Affiliate Successfully'));
		}else{
			$errKeys=[];
			foreach ($rlt as $dbKey => $dbRlt) {
				if(!$dbRlt['success']){
					$errKeys[]=$dbKey;
				}
			}
			$errorMessage=implode(',', $errKeys);
		    $this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('Sync Affiliate Failed').': '.$errorMessage);
		}
		redirect('/marketing_management/viewRegistrationSettings');
	}

	public function post_sync_mdb(){
		$callerType = Queue_result::CALLER_TYPE_ADMIN;
		$caller=$this->authentication->getUserId();
		$state=null;
		$lang=$this->language_function->getCurrentLanguage();

		$playerUsername=$this->input->post('playerUsername');
        $playerLevelByUsername=$this->input->post('playerLevelByUsername');
		$agencyUsername=$this->input->post('agencyUsername');
		$affiliateUsername=$this->input->post('affiliateUsername');
		$adminUsername=$this->input->post('adminUsername');
		$roleName=$this->input->post('roleName');
		$regSettingType=$this->input->post('regSettingType');
        $vipsettingid=$this->input->post('vipsettingid');
        $dryrun_in_vipsettingid=$this->input->post('dryrun_in_vipsettingid');

        $trigger_method= __METHOD__;

		$targetIdArr=[];
		if(!empty($playerUsername)){
			$this->load->model(['player_model']);
			$playerId=$this->player_model->getPlayerIdByUsername($playerUsername);
			if(!empty($playerId)){
				$targetIdArr['player_id']=$playerId;
				$targetIdArr['player_lock_unique_name']=$playerUsername;
			}
		}
        if(!empty($playerLevelByUsername)){
			$this->load->model(['player_model']);
			$playerId=$this->player_model->getPlayerIdByUsername($playerLevelByUsername);
			if(!empty($playerId)){
				$targetIdArr['player_id']=$playerId;
                $targetIdArr['source_currency'] = $this->utils->getActiveTargetDB();
                $targetIdArr['trigger_method'] = $trigger_method;
				$targetIdArr['playerlevel_lock_unique_name']=$playerLevelByUsername;
			}
		}
		if(!empty($agencyUsername)){
			$this->load->model(['agency_model']);
			$agentId=$this->agency_model->getAgentIdByUsername($agencyUsername);
			if(!empty($agentId)){
				$targetIdArr['agent_id']=$agentId;
				$targetIdArr['agent_lock_unique_name']=$agencyUsername;
			}
		}
		if(!empty($affiliateUsername)){
			$this->load->model(['affiliatemodel']);
			$affiliateId=$this->affiliatemodel->getAffiliateIdByUsername($affiliateUsername);
			if(!empty($affiliateId)){
				$targetIdArr['affiliate_id']=$affiliateId;
				$targetIdArr['affiliate_lock_unique_name']=$affiliateUsername;
			}
		}
		if(!empty($adminUsername)){
			$this->load->model(['users']);
			$userId=$this->users->getIdByUsername($adminUsername);
			if(!empty($userId)){
				$targetIdArr['admin_user_id']=$userId;
				$targetIdArr['admin_user_lock_unique_name']=$adminUsername;
			}
		}
		if(!empty($roleName)){
			$this->load->model(['roles']);
			$roleId=$this->roles->getRoleIdByName($roleName);
			if(!empty($userId)){
				$targetIdArr['role_id']=$roleId;
				$targetIdArr['role_lock_unique_name']=$roleName;
			}
		}
        if(!empty($vipsettingid)){
            $filter_deleted = true;
            $vipLevel = $this->group_level->getVIPGroupRules($vipsettingid, $filter_deleted);
            if(!empty($vipLevel)){ // VIP Group usually contains one or more level.
                $targetIdArr['source_currency'] = $this->utils->getActiveTargetDB();
				$targetIdArr['vipsettingid'] = $vipsettingid;
				$targetIdArr['vipsettingid_lock_unique_name'] = $vipsettingid;
                $targetIdArr['dryrun_in_vipsettingid'] = $dryrun_in_vipsettingid;
                $targetIdArr['extra_info'] = '{}'; // always default, for add group
			}
        }
		$message=null;
		$this->utils->debug_log('triggerRemoteSyncMDBEvent', $targetIdArr, $callerType, $caller, $state, $lang, $message);
		$this->load->library(['lib_queue']);
		$token=$this->lib_queue->triggerAsyncRemoteSyncMDBEvent($targetIdArr, $callerType, $caller, $state, $lang, $message);
	    if (!empty($token)) {
	        $this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang('Add job to queue successfully'));
			return redirect('/system_management/common_queue/'.$token);
	    } else {
	    	if(empty($message)){
	    		$message=lang('Add job to queue failed');
	    	}
	        $this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);
			return redirect('/system_management/other_functions');
	    }
	}

	public function regenerate_all_report(){
		$data = array('title' => lang('Dev Functions'), 'sidebar' => 'system_management/sidebar',
			'activenav' => 'dev_functions');

		$by_date_from=$this->input->post('by_date_from');
		$by_date_to=$this->input->post('by_date_to');

		$lock_rebuild_reports_range= $this->utils->getConfig('lock_rebuild_reports_range');

        if(!empty($lock_rebuild_reports_range)){
        	$from = $this->utils->formatDateForMysql(new DateTime($by_date_from));
        	$to = $this->utils->formatDateForMysql(new DateTime($by_date_to));
            if($this->utils->isRebuildReportDateLocked($lock_rebuild_reports_range,$from,$to,$rlt)){
                $this->utils->error_log(sprintf(lang("Regenarate All Report has lock - should not be equal or older than  %s  "),$rlt['cutoff_day']));
                $this->alertMessage(self::MESSAGE_TYPE_ERROR, sprintf(lang("Regenarate All Report has lock - should not be equal or older than  %s  "),$rlt['cutoff_day']));
                return redirect('/system_management/other_functions');
            }
        }

		$this->utils->debug_log('regenerate_all_report','by_date_from', $by_date_from, 'by_date_to', $by_date_to);

		$success=true;
		$this->load->library(['lib_queue', 'language_function']);
		$this->load->model(['queue_result']);
		$caller=$this->authentication->getUserId();
		$state=null;
		$lang=$this->language_function->getCurrentLanguage();
		$funcName = 'regenerate_all_report';
		$callerType = Queue_result::CALLER_TYPE_ADMIN;
		$systemId = Queue_result::SYSTEM_UNKNOWN;

		$params = ['endDateTimeStr' => $by_date_from,
		'fromDateTimeStr' => $by_date_to
		];

		$token=$this->lib_queue->commonAddRemoteJob($systemId,$funcName, $params, $callerType, $caller, $state, $lang);
	        //goto queue page
		redirect('/system_management/common_queue/'.$token);
	}

	public function batch_move_deposit_note_to_sale_orders_notes(){
		$data = array('title' => lang('Batch move deposit note to sale_orders_notes'), 'sidebar' => 'system_management/sidebar', 'activenav' => 'dev_functions');
		if (!$this->permissions->checkPermissions('dev_functions')) {
			return $this->showErrorAccess($data['title'], $data['sidebar'], $data['activenav']);
		}

		$is_blocked = false;
		$this->triggerGenerateCommandEvent('batchCopyDataFromSaleOrdersAndTransactionNotes', [], $is_blocked);

		$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang('Please check later.'));
		redirect('/system_management/other_functions');
	}

	public function batch_move_withdrawal_note_to_walletaccount_notes(){
		$data = array('title' => lang('Batch move deposit note to walletaccount_notes'), 'sidebar' => 'system_management/sidebar', 'activenav' => 'dev_functions');
		if (!$this->permissions->checkPermissions('dev_functions')) {
			return $this->showErrorAccess($data['title'], $data['sidebar'], $data['activenav']);
		}

		$is_blocked = false;
		$this->triggerGenerateCommandEvent('batchCopyDataFromWalletaccountAndTransactionNotes', [], $is_blocked);

		$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang('Please check later.'));
		redirect('/system_management/other_functions');
	}

	public function post_update_admin_dashboard() {
		$date_range_to		= $this->input->post('date_range_to', 1);
		$date_range_from	= $this->input->post('date_range_from', 1);
		$date_base			= $this->input->post('date_base', 1);
		$date_disp			= $this->input->post('date_disp', 1);

		$dates = [
			'date_range_to'		=> $date_range_to ,
			'date_range_from'	=> $date_range_from ,
			'date_base'			=> $date_base ,
			'date_disp'			=> $date_disp
		];

		$this->load->model([ 'transactions' ]);
		$this->transactions->syncDashboard($dates);

		$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang('devfunc.admin_dashboard_updated'));
		redirect('/system_management/other_functions');
	}

	public function setting_admin_auto_logout() {
		$enable_admin_auto_logout = $this->input->post('enable_admin_auto_logout');
		$auto_logout_sess_expiration = $this->input->post('auto_logout_sess_expiration');
		if( $enable_admin_auto_logout && ($auto_logout_sess_expiration<2||$auto_logout_sess_expiration>720) ) {
			$auto_logout_sess_expiration = $this->utils->getConfig('default_auto_logout_sess_expiration');
		}
		$settingName = 'admin_sess_expire';
		$sysValue = $this->operatorglobalsettings->getSettingJson($settingName);

		$settingValue = array('enable' => $enable_admin_auto_logout=='on'? 1:0, 'sess_expiration' => $auto_logout_sess_expiration*60);

		if (!count($sysValue)) {
			// Insert setting
			$this->operatorglobalsettings->insertSettingJson($settingName, $settingValue);
		} else {
			// Update setting
			$this->operatorglobalsettings->putSettingJson($settingName, $settingValue);
		}
		$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, "Auto logout update successfully.");
		redirect('system_management/system_settings');
	}

	public function view_user_backendapi($targetUserId){

		if (!$this->utils->isEnabledFeature('enabled_backendapi') || !$this->permissions->checkPermissions('view_backendapi_keys')) {
			$this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('No permission'));
			return redirect('/user_management/viewUser/'.$targetUserId);
		}

		$data = array('title' => lang('View Backend API'), 'sidebar' => 'system_management/sidebar',
			'activenav' => 'view_user_backendapi');

		$userId = $this->authentication->getUserId();
		$username = $this->authentication->getUsername();
		$render = true;

		if(empty($targetUserId)){
			//error
			$this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('Not found user'));
			return redirect('/user_management/viewUser/'.$targetUserId);
		}
		$this->load->model(['users']);
		$keys=$this->users->getKeysByUserId($targetUserId);
		if(empty($keys)){
			//error
			$this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('Not found user'));
			return redirect('/user_management/viewUser/'.$targetUserId);
		}
		$data['keys']=$keys;
		$data['targetUserId']=$targetUserId;

		$this->loadDefaultTemplate(['resources/js/datatables.min.js',],
			['resources/css/general/style.css','resources/css/datatables.min.css'],
			array('title' => $data['title'],
				'activenav' => $data['activenav'],
				'userId' => $userId,
				'username' => $username), $data['sidebar'],
			'system_management/view_user_backendapi', $data, $render);
	}

	public function regenerate_user_backendapi($targetUserId){
		if (!$this->utils->isEnabledFeature('enabled_backendapi') || !$this->permissions->checkPermissions('regenerate_backendapi_keys')) {
			$this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('No permission'));
			return $this->returnJsonResult(['success'=>false, 'error'=>lang('No permission')]);
		}

		$this->load->model(['users']);
		$success=$this->users->generateKeys($targetUserId);
		$result=['success'=>$success];

		$this->returnJsonResult($result);
	}

	/**
	 *
	 * detail: display the lists of the Transactions Summary Reports Settings
	 *
	 * @return load template
	 */
	public function transactionsDailySummaryReportSettings()
	{
		if (!$this->permissions->checkPermissions('transactions_daily_summary_report'))
		{
			$this->error_access();
		} else {
			$render = true;
			$data = array('title' => lang('sys.gm.transactionsdailysummaryreportsettings'), 'sidebar' => 'system_management/sidebar',
			'activenav' => 'dev_functions');

			$data['conditions'] = array(
				'day_starttime' => $this->operatorglobalsettings->getSettingValue('transactions_daily_summary_report_day_starttime'),
				// 'day_endtime' => $this->operatorglobalsettings->getSettingValue('transactions_daily_summary_report_day_endtime'),
				// 'day_synctime' => $this->operatorglobalsettings->getSettingValue('transactions_daily_summary_report_sync_time'),
			);

			$this->loadDefaultTemplate(
				[
					'resources/js/datatables.min.js',
					'resources/js/system_management/system_management.js'
				],
				['resources/css/general/style.css','resources/css/datatables.min.css'],
				array('title' => $data['title'],
					'activenav' => $data['activenav']), $data['sidebar'],
				'system_management/view_transaction_summary_report_settings', $data, $render);
		}
	}

    public function ajax_set_transaction_summary_setting()
    {
    	if (!$this->permissions->checkPermissions('transactions_daily_summary_report')) {
			$this->error_access();
		} else {
			$this->load->model(array('Operatorglobalsettings'));
			$this->db->trans_start();
			$this->operatorglobalsettings->putSetting('transactions_daily_summary_report_day_starttime',$this->input->post('day_starttime'));
        	// $this->operatorglobalsettings->putSetting('transactions_daily_summary_report_day_endtime', $this->input->post('day_endtime'));
        	// $this->utils->debug_log("Settings",$this->input->post('day_starttime'),$this->input->post('day_endtime'));

			# Update the Settings
			$data = array(
				'username' => $this->authentication->getUsername(),
				'management' => 'System Management',
				'userRole' => $this->rolesfunctions->getRoleByUserId($this->authentication->getUserId())['roleName'],
				'action' => 'Edit Transaction Daily Summary Report Setting',
				'description' => "User " . $this->authentication->getUsername() . " edit transaction daily summary report setting",
				'logDate' => $this->utils->getNowForMysql(),
				'status' => '0',
			);

			$this->report_functions->recordAction($data);
			$this->db->trans_commit();

			if ($this->db->trans_status() === FALSE) {
				$result = array(
					'status' => 'error',
					'msg' => 'Error Occured',
				);
			} else {
				$result = array(
					'status' => 'success',
				);
				$this->returnJsonResult($result);
			}
		}
    }

    public function clear_acl_ip($ip=null){
    	if(!$this->users->isT1User($this->authentication->getUsername())) {
			return $this->returnErrorStatus();
		}
    	$config_key=$this->input->post('acl_player_config_key');
    	if(empty($config_key)){
    		$config_key='iframe_login';
    	}
    	if(empty($ip)){
	    	$ip=$this->input->post('acl_player_ip');
    	}
    	$key = $config_key . '-ip_bandwidth-' . $ip;
    	$success=$this->utils->deleteCache($key);
    	$this->utils->debug_log('delete cache', $key, $success);
    	if($success){
		    $this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang('Clear Key Successfully').' '.$key);
    	}else{
		    $this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('Clear Key Failed').' '.$key);
    	}

    	redirect('/system_management/other_functions');
    }

    public function post_sync_after_blance(){
		$data = array('title' => lang('Sync Game Logs'), 'sidebar' => 'player_management/sidebar', 'activenav' => 'sync_game_logs');

		if (!$this->permissions->checkPermissions('sync_game_logs')) {
			return $this->showErrorAccess($data['title'], $data['sidebar'], $data['activenav']);
		}


		$this->load->library(['lib_queue', 'language_function']);
		$this->load->model(['queue_result','player_model']);

		$by_date_from_str = $this->input->post('by_date_from');
		$by_date_to_str   = $this->input->post('by_date_to');
		$player_name = $this->input->post('player_name');
		$by_game_platform_id = $this->input->post('by_game_platform_id');

		$this->utils->debug_log('by_date_from', $by_date_from_str, 'by_date_to', $by_date_to_str, 'by_game_platform_id', $by_game_platform_id, 'playe_name', $player_name);

		//run command
		$caller = $this->authentication->getUserId();
		$state = null;
		$lang = $this->language_function->getCurrentLanguage();
		$caller_type = Queue_result::CALLER_TYPE_ADMIN;
		$systemId = Queue_result::SYSTEM_UNKNOWN;
		$funcName = 'sync_game_after_balance';

		$params = array(
			"by_game_platform_id" => $by_game_platform_id,
			"player_name" => $player_name,
			"from" => $by_date_from_str,
			"to" => $by_date_to_str,
		);

		$token=$this->lib_queue->commonAddRemoteJob($systemId,$funcName, $params, $caller_type, $caller, $state, $lang);
		redirect('/system_management/common_queue/'.$token);
	}


	public function post_check_mgquickfire_livedealer_data(){

		$this->load->library(['lib_queue', 'language_function']);
		$this->load->model(['queue_result','player_model']);

		$data = array('title' => lang('Sync Game Logs'), 'sidebar' => 'player_management/sidebar', 'activenav' => 'sync_game_logs');

		if (!$this->permissions->checkPermissions('sync_game_logs')) {
			return $this->showErrorAccess($data['title'], $data['sidebar'], $data['activenav']);
		}

		$by_date_from_str = $this->input->post('mg_by_date_from');
		$by_date_to_str   = $this->input->post('mg_by_date_to');


		$by_game_platform_id = MG_QUICKFIRE_API;
		$player_name = !empty($this->input->post('playerName')) ? $this->input->post('playerName') : '';
	    $player_id =  $this->player_model->getPlayerIdByUsername($player_name);

		$this->utils->debug_log('by_date_from', $by_date_from_str, 'by_date_to', $by_date_to_str, 'by_game_platform_id', $by_game_platform_id);
		//run command
		$caller = $this->authentication->getUserId();
		$state = null;
		$lang = $this->language_function->getCurrentLanguage();
		$caller_type = Queue_result::CALLER_TYPE_ADMIN;
		$systemId = Queue_result::SYSTEM_UNKNOWN;
		$funcName = 'check_mgquickfire_data';

		$params = array(
			"by_game_platform_id" => $by_game_platform_id,
			"player_id" => $player_id,
			"from" => $by_date_from_str,
			"to" => $by_date_to_str,
		);

		$token=$this->lib_queue->commonAddRemoteJob($systemId,$funcName, $params, $caller_type, $caller, $state, $lang);
		redirect('/system_management/common_queue/'.$token);
	}

	public function manual_fix_missing_payout(){

		$data = array('title' => lang('Manual fix missing payout'), 'sidebar' => 'system_management/sidebar',
			'activenav' => 'dev_functions');

		if (!$this->permissions->checkPermissions('dev_functions') || !$this->users->isT1User($this->authentication->getUsername())) {
			return $this->showErrorAccess($data['title'], $data['sidebar'], $data['activenav']);
		}

		//load api
		$gamePlatformId = $this->input->post('by_game_platform_id');
		$jsonParameters = $this->input->post('json_parameters');

		if(empty($gamePlatformId) || empty($jsonParameters)){
			$this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('Incomplete parameters.'));
			redirect('/system_management/other_functions');
			exit;
		}

		$api = $this->utils->loadExternalSystemLibObject($gamePlatformId);
		if(!$api){
			$this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('Error loading API').' '.$gamePlatformId);
			redirect('/system_management/other_functions');
			exit;
		}

		if(!$api->isSeamLessGame()){
			$this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('Feature is for seamless only.'));
			redirect('/system_management/other_functions');
			exit;
		}

		//call api
		$result = $api->triggerInternalPayoutRound($jsonParameters);

		if(isset($result) && isset($result['success']) && $result['success']=='true'){
			if(isset($result['unimplemented']) && $result['unimplemented']==true){
				$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang('Feature is not available in this game.'));
			}else{
				$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang('Successfully process payout manually.'));
			}
    	}else{
		    $this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('Error sending manual payout. '. $result['message']));
    	}

		redirect('/system_management/other_functions');
	}

	public function manual_fix_missing_bet(){

		$data = array('title' => lang('Manual fix missing bet'), 'sidebar' => 'system_management/sidebar',
			'activenav' => 'dev_functions');

		if (!$this->permissions->checkPermissions('dev_functions') || !$this->users->isT1User($this->authentication->getUsername())) {
			return $this->showErrorAccess($data['title'], $data['sidebar'], $data['activenav']);
		}

		//load api
		$gamePlatformId = $this->input->post('by_game_platform_id');
		$jsonParameters = $this->input->post('json_parameters');

		if(empty($gamePlatformId) || empty($jsonParameters)){
			$this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('Incomplete parameters.'));
			redirect('/system_management/other_functions');
			exit;
		}

		$api = $this->utils->loadExternalSystemLibObject($gamePlatformId);
		if(!$api){
			$this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('Error loading API').' '.$gamePlatformId);
			redirect('/system_management/other_functions');
			exit;
		}

		if(!$api->isSeamLessGame()){
			$this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('Feature is for seamless only.'));
			redirect('/system_management/other_functions');
			exit;
		}

		//call api
		$result = $api->triggerInternalBetRound($jsonParameters);
		//$this->utils->debug_log('manual_fix_missing_bet triggerInternalPayoutRound', 'jsonParameters', $jsonParameters, 'result', $result);
		if(isset($result) && isset($result['success']) && $result['success']=='true'){
			if(isset($result['unimplemented']) && $result['unimplemented']==true){
				$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang('Feature is not available in this game.'));
			}else{
				$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang('Successfully process bet manually.'));
			}
    	}else{
		    $this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('Error sending manual bet. View response result.'));
    	}

		redirect('/system_management/other_functions');
	}

    public function manual_fix_missing_refund(){
		$data = array('title' => lang('Manual fix missing refund'), 'sidebar' => 'system_management/sidebar',
			'activenav' => 'dev_functions');

		if (!$this->permissions->checkPermissions('dev_functions') || !$this->users->isT1User($this->authentication->getUsername())) {
			return $this->showErrorAccess($data['title'], $data['sidebar'], $data['activenav']);
		}

		//load api
		$gamePlatformId = $this->input->post('by_game_platform_id');
		$jsonParameters = $this->input->post('json_parameters');

		if(empty($gamePlatformId) || empty($jsonParameters)){
			$this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('Incomplete parameters.'));
			redirect('/system_management/other_functions');
			exit;
		}

		$api = $this->utils->loadExternalSystemLibObject($gamePlatformId);
		if(!$api){
			$this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('Error loading API').' '.$gamePlatformId);
			redirect('/system_management/other_functions');
			exit;
		}

		if(!$api->isSeamLessGame()){
			$this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('Feature is for seamless only.'));
			redirect('/system_management/other_functions');
			exit;
		}

		//call api
		$result = $api->triggerInternalRefundRound($jsonParameters);

		if(isset($result) && isset($result['success']) && $result['success']=='true'){
			if(isset($result['unimplemented']) && $result['unimplemented']==true){
				$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang('Feature is not available in this game.'));
			}else{
				$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang('Successfully process refund manually.'));
			}
    	}else{
			$msg = lang('Error sending manual refund. View response result.');
			if(isset($result['message'])){
				$msg = $result['message'];
			}
		    $this->alertMessage(self::MESSAGE_TYPE_ERROR, $msg);
    	}

		redirect('/system_management/other_functions');
	}

    public function post_rebuild_points_transaction_report_hour()
    {
        $this->load->library(['lib_queue', 'language_function']);
        $this->load->model(['player_model']);

        $success = true;
		$msg = '';

		$data = [
            'title' => lang('Rebuild Points Transaction Report Hour'),
            'sidebar' => 'system_management/sidebar',
            'activenav' => 'dev_functions'
        ];

		if(!$this->permissions->checkPermissions('dev_functions') || !$this->users->isT1User($this->authentication->getUsername()))
        {
			return $this->showErrorAccess($data['title'], $data['sidebar'], $data['activenav']);
		}

		$by_date_from = $this->input->post('by_date_from');
		$by_date_to = $this->input->post('by_date_to');
        $player_name = !empty($this->input->post('player_name')) ? $this->input->post('player_name') : '_null';
        $is_sync_player_points = $this->input->post('is_sync_player_points') ? strval($this->input->post('is_sync_player_points')) : 'false';

        if($player_name != '_null')
        {
            $player_id = intval($this->player_model->getPlayerIdByUsername($player_name));

            if(empty($player_id))
            {
                $success = false;
                $msg = lang('Username does not exist!');
            }

        }else{
            $player_id = '_null';
        }

		if($by_date_from > $by_date_to)
        {
            $success = false;
			$msg = lang('Invalid datetime range.');
		}

		//go back
	    if(!$success)
        {
	       $this->alertMessage(self::MESSAGE_TYPE_ERROR, $msg);
	       return redirect('/system_management/other_functions');
	    }

		$systemId = Queue_result::SYSTEM_UNKNOWN;
        $funcName = 'rebuild_points_transaction_report_hour';
		$params = [
            'from_date_time' => $by_date_from,
            'to_date_time' => $by_date_to,
            'player_id' => $player_id,
            'player_name' => $player_name,
            'is_sync_player_points' => $is_sync_player_points
        ];
        $callerType = Queue_result::CALLER_TYPE_ADMIN;
        $caller = $this->authentication->getUserId();
        $state = null;
        $lang = $this->language_function->getCurrentLanguage();

		$token = $this->lib_queue->commonAddRemoteJob($systemId, $funcName, $params, $callerType, $caller, $state, $lang);

        $this->utils->debug_log('post_rebuild_points_transaction_report_hour_result', 'token: ' . $token, 'params: ' . json_encode($params), 'isSuccess: ' . $success);

		//goto queue page
		redirect('/system_management/common_queue/' . $token);
	}


	public function post_restart_queue_server(){

		$data = array('title' => lang('Restart queue server'), 'sidebar' => 'system_management/sidebar',
			'activenav' => 'dev_functions');

		if (!$this->permissions->checkPermissions('dev_functions') || !$this->users->isT1User($this->authentication->getUsername())) {
			return $this->showErrorAccess($data['title'], $data['sidebar'], $data['activenav']);
		}
		$key = "restart_queue_program";
		$success=$this->utils->writeRedis($key, true);
	    if ($success) {
	        $this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang('Restart queue program successfully'));
	    } else {
	        $this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('Restart queue program failed'));
	    }

		redirect('/system_management/other_functions');
	}

	public function get_key_value_restart_queue_server($key="restart_queue_program"){
		// $value = $this->utils->getTextFromCache($key);
		$value=$this->utils->readRedis($key);
		// $value =  $this->operatorglobalsettings->getSettingValueWithoutCache($key);
		$this->utils->info_log('get_key_value... key:' . $key, $value);
		echo $value;
	}

	public function view_player_center_api_domain(){
        if (!$this->permissions->checkPermissions('player_center_api_domains')) {
            $this->error_access();
        } else {
            $data = array('title' => lang('Player Center API Domains'), 'sidebar' => 'system_management/sidebar',
			'activenav' => 'system');

		// if (!$this->users->isT1User($this->authentication->getUsername())) {
		// 	return $this->showErrorAccess($data['title'], $data['sidebar'], $data['activenav']);
		// }

		$this->load->model(array('operatorglobalsettings','external_system', 'marketing', 'transactions'));
		$this->load->library(['lib_queue', 'language_function']);

		$userId = $this->authentication->getUserId();
		$username = $this->authentication->getUsername();

		// echo $username;exit();
		$render = true;
		$this->loadDefaultTemplate(
			['resources/js/highlight.pack.js',
			'resources/js/datatables.min.js',
			'resources/js/select2.min.js',
			'resources/js/system_management/system_management.js'],
			['resources/css/general/style.css',
			'resources/css/hljs.tomorrow.css',
			'resources/css/datatables.min.css',
			'resources/css/select2.min.css'],
			array('title' => $data['title'],
				'activenav' => $data['activenav'],
				'userId' => $userId,
				'username' => $username), $data['sidebar'],
			'system_management/view_player_center_api_domain', $data, $render);
        }
    }

    public function player_center_api_domain_list(){
        if (!$this->permissions->checkPermissions('player_center_api_domains')) {
            $this->error_access();
        }
        $this->load->model(array('player_center_api_domains'));
        $result =  $this->player_center_api_domains->getPlayerCenterApiDomainList();
        return $this->returnJsonResult($result);
    }

    public function add_player_center_api_domain($domainId = null){
    	$this->load->model(array('player_center_api_domains'));


    	$domain	= $this->input->post('domain');
        $note = $this->input->post('note');
        $status = $this->input->post('status');
        $this->form_validation->set_rules('domain', 'Domain', 'required|trim|xss_clean|is_unique[player_center_api_domain.domain.'.$domain.']|callback_isValidPlayerCenterDomain');

        if ($this->form_validation->run() == false && !$domainId) {
        	$message = lang('save.failed');
        	$error = validation_errors();

        	if(str_contains($error, 'isValidPlayerCenterDomain')){
        		$message = lang('Invalid domain');
        	}

        	if(str_contains($error, 'is_unique')){
        		$message = $domain . "</b> " . lang('con.i02');
        	}

        	$msgType = self::MESSAGE_TYPE_ERROR;
			$this->alertMessage($msgType, $message);
            redirect(BASEURL . 'system_management/view_player_center_api_domain');
			return;
		}

        $adminUsername = $this->authentication->getUsername();

    	$data = array(
            'domain' => $domain,
            'note' => $note,
            'status' => $status,
            'created_by' => $adminUsername,
            'updated_by' => $adminUsername
        );
    	if($domainId){
    		$result = $this->player_center_api_domains->editPlayerCenterApiDomain($data, $domainId);

	        if($result){
	            $msgType = self::MESSAGE_TYPE_SUCCESS;
	            $message = $domain . "</b> " . lang('con.cb04');
	        }else{
	            $msgType = self::MESSAGE_TYPE_ERROR;
	            $message = lang('save.failed');
	        }

	        $this->saveAction(lang('Edit player center api domain'), $this->authentication->getUsername() . lang('con.usm32'));
	        $this->alertMessage($msgType, $message);
			redirect(BASEURL . 'system_management/view_player_center_api_domain');
			return null;
    	}

        $result = $this->player_center_api_domains->addPlayerCenterApiDomain($data);

        if($result){
            $msgType = self::MESSAGE_TYPE_SUCCESS;
            $message = $domain . "</b> " . lang('con.cb05');
        }else{
            $msgType = self::MESSAGE_TYPE_ERROR;
            $message = lang('save.failed');
        }

        $this->saveAction(lang('Add player center api domain'), $this->authentication->getUsername() . lang('con.usm33'));
        $this->alertMessage($msgType, $message);
		redirect(BASEURL . 'system_management/view_player_center_api_domain');
		return null;
    }

    public function deletePlayerCenterDomains() {
    	$this->load->model(array('player_center_api_domains'));
        if (!$this->permissions->checkPermissions('player_center_api_domains')) {
            $this->error_access();
        }

        $this->saveAction(lang('Delete player center api domain'), $this->authentication->getUsername());
        $this->returnJsonResult($this->player_center_api_domains->deleteDomains($this->input->post('domainIds')));
    }

    public function blockPlayerCenterDomains() {
    	$this->load->model(array('player_center_api_domains'));
        if (!$this->permissions->checkPermissions('player_center_api_domains')) {
            $this->error_access();
        }
        $this->saveAction(lang('Blocked player center api domain'), $this->authentication->getUsername());
        $this->returnJsonResult($this->player_center_api_domains->blockedDomains($this->input->post('domainIds'), $this->authentication->getUsername()));
    }

    public function unBlockPlayerCenterDomains() {
    	$this->load->model(array('player_center_api_domains'));
        if (!$this->permissions->checkPermissions('player_center_api_domains')) {
            $this->error_access();
        }
        $this->saveAction(lang('Unblocked player center api domain'), $this->authentication->getUsername());
        $this->returnJsonResult($this->player_center_api_domains->unBlockedDomains($this->input->post('domainIds'), $this->authentication->getUsername()));
    }

    function isValidPlayerCenterDomain($domain_name)
	{
	    $valid =   (preg_match("/^([a-z\d](-*[a-z\d])*)(\.([a-z\d](-*[a-z\d])*))*$/i", $domain_name)
	            && preg_match("/^.{1,253}$/", $domain_name)
	            && preg_match("/^[^\.]{1,63}(\.[^\.]{1,63})*$/", $domain_name)   );
	    return $valid;
	}

    public function player_lock_balance()
    {
        $this->load->library(['lib_queue', 'language_function', 'session']);
        $this->load->model(['player_model']);

        $data = [
            'title' => lang('Player Lock Balance'),
            'sidebar' => 'system_management/sidebar',
            'activenav' => 'dev_functions'
        ];

        if (!$this->permissions->checkPermissions('dev_functions') || !$this->users->isT1User($this->authentication->getUsername())) {
            return $this->showErrorAccess($data['title'], $data['sidebar'], $data['activenav']);
        }

        $success = true;
        $username = $this->input->post('username');
        $seconds = $this->input->post('seconds');
        $systemId = Queue_result::SYSTEM_UNKNOWN;
        $funcName = 'player_lock_balance';
        $callerType = Queue_result::CALLER_TYPE_ADMIN;
        $caller = $this->authentication->getUserId();
        $state = null;
        $lang = $this->language_function->getCurrentLanguage();

        $params = [
            'username' => $username,
            'seconds' => $seconds,
        ];

        $player_id = intval($this->player_model->getPlayerIdByUsername($username));

        if (empty($player_id)) {
            $success = false;
			$msg = lang('Player not found.');
		}

        if ($seconds < 10) {
            $success = false;
			$msg = lang('Minimum is 10 seconds.');
		}

		//go back
	    if (!$success) {
	       $this->alertMessage(self::MESSAGE_TYPE_ERROR, $msg);
	       return redirect('/system_management/other_functions');
	    }

        $token = $this->lib_queue->commonAddRemoteJob($systemId, $funcName, $params, $callerType, $caller, $state, $lang);

        $this->utils->debug_log('manual player_lock_balance', 'token', $token, 'params', $params);
        $this->session->set_userdata(['test_player_lock_balance_seconds' => $seconds]);

        //goto queue page
        redirect('/system_management/common_queue/' . $token);
    }

    public function test_lock_table($table = null)
    {
        $this->load->library(['lib_queue', 'language_function', 'session']);
        $this->load->model(['player_model']);

        $data = [
            'title' => lang('Test Lock Table'),
            'sidebar' => 'system_management/sidebar',
            'activenav' => 'dev_functions'
        ];

        if(!$this->utils->getConfig('enable_test_lock_table')){
        	return $this->showErrorAccess($data['title'], $data['sidebar'], $data['activenav']);
    	}

        if(empty($table)){
        	$data = file_get_contents('php://input');
        	$params = json_decode($data, true);
        	// parse_str($data, $params);
        } else {
        	$params['table'] = $table;
        	$params['sleep'] = 20;
        }

        if (!$this->permissions->checkPermissions('dev_functions') || !$this->users->isT1User($this->authentication->getUsername())) {
            return $this->showErrorAccess($data['title'], $data['sidebar'], $data['activenav']);
        }

		$success = $this->player_model->testlockTable($params['table'], $params['sleep']);
        $this->returnJsonResult(array("success" => $success, "params" => $params));
    }

    public function get_transaction_table_by_platform_id($gamePlatformId = null){
    	if(!$this->utils->getConfig('enable_test_lock_table') || empty($gamePlatformId)){
        	$this->returnJsonResult(array("success" => false));
    	}

    	$api = $this->utils->loadExternalSystemLibObject($gamePlatformId);
    	if($api){
    		$table = $api->getTransactionsTable();
    		$this->returnJsonResult(array("success" => true, "table" => $table));
    	} else{
    		$this->returnJsonResult(array("success" => false));
    	}
    }

    public function remote_lock_table(){
    	$this->load->library(['lib_queue', 'language_function', 'session']);
        $this->load->model(['player_model']);

        $data = [
            'title' => lang('Player Lock Balance'),
            'sidebar' => 'system_management/sidebar',
            'activenav' => 'dev_functions'
        ];

        if (!$this->permissions->checkPermissions('dev_functions') || !$this->users->isT1User($this->authentication->getUsername())) {
            return $this->showErrorAccess($data['title'], $data['sidebar'], $data['activenav']);
        }

        $success = true;
        $table = $this->input->post('lock_table_name');
        $seconds = $this->input->post('lock_table_sec');
        $systemId = Queue_result::SYSTEM_UNKNOWN;
        $funcName = 'lock_table';
        $callerType = Queue_result::CALLER_TYPE_ADMIN;
        $caller = $this->authentication->getUserId();
        $state = null;
        $lang = $this->language_function->getCurrentLanguage();

        $params = [
            'table' => $table,
            'seconds' => $seconds,
        ];

        if ($seconds < 10) {
            $success = false;
			$msg = lang('Minimum is 10 seconds.');
		}

		//go back
	    if (!$success) {
	       $this->alertMessage(self::MESSAGE_TYPE_ERROR, $msg);
	       return redirect('/system_management/other_functions');
	    }

        $token = $this->lib_queue->commonAddRemoteJob($systemId, $funcName, $params, $callerType, $caller, $state, $lang);

        $this->utils->debug_log('manual lock_table', 'token', $token, 'params', $params);
        $this->session->set_userdata(['remote_lock_table_seconds' => $seconds]);

        //goto queue page
        redirect('/system_management/common_queue/' . $token);
    }

	public function create_free_rounds()
	{
		$game_platform_id = $this->input->post('free_round_game_platform_id');
		$parameters = json_decode($this->input->post('free_round_parameters'));
		$msg = '';

		$this->game_api = $this->utils->loadExternalSystemLibObject($game_platform_id);
        $result = $this->game_api->createFreeRound($parameters->player_username, $parameters);
		$msg = isset($result['message']) ? $result['message'] : '';

		$success = isset($result['success']) ? $result['success'] : false;
		$unimplemented = isset($result['unimplemented']) ? $result['unimplemented'] : false;
        $data = [
            'title' => lang('Create Free Round'),
            'sidebar' => 'system_management/sidebar',
            'activenav' => 'dev_functions'
        ];

        if (!$this->permissions->checkPermissions('dev_functions') || !$this->users->isT1User($this->authentication->getUsername())) {
            return $this->showErrorAccess($data['title'], $data['sidebar'], $data['activenav']);
        }

		//go back
	    if (!$success) {
	       $this->alertMessage(self::MESSAGE_TYPE_ERROR, $msg);
	       return redirect('/system_management/other_functions');
	    }

		if($unimplemented){
			$msg = "Unimplemented on game";
			$this->alertMessage(self::MESSAGE_TYPE_ERROR, $msg);
	       	return redirect('/system_management/other_functions');
		}
		$msg = "Successfully created free round";

		$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, $msg);
		return redirect('/system_management/other_functions');
    }

	public function remote_batch_refund()
	{
		$this->load->library(['lib_queue', 'language_function', 'session']);
        $this->load->model(['player_model']);
		$data = [
			'title' => lang('Batch refund'),
			'sidebar' => 'system_management/sidebar',
			'activenav' => 'dev_functions'
		];

		if (!$this->permissions->checkPermissions('dev_functions') || !$this->users->isT1User($this->authentication->getUsername())) {
			return $this->showErrorAccess($data['title'], $data['sidebar'], $data['activenav']);
		}

		$gamePlatformId = $this->input->post('game_platform_id');
		$api = $this->utils->loadExternalSystemLibObject($gamePlatformId);

		$batch_refund_file = isset($_FILES['batch_refund_file']) ? $_FILES['batch_refund_file'] : null;
		$redirect = '/system_management/other_functions';

		$rlt = $api->batchRefund();

		$unimplemented = (isset($rlt['unimplemented']) && $rlt['unimplemented']) ? true : false;
		if($unimplemented){
			return $this->errorRedirect("Batch Refund is unimplemented", $redirect);
		}

		if (!$batch_refund_file) {
			return $this->errorRedirect("File not exist", $redirect);
		}
		$allowed_types = array('csv');
		$file_path = isset($_FILES['batch_refund_file']['name']) ? $_FILES['batch_refund_file']['name'] : null;

		$file_ext = pathinfo($file_path, PATHINFO_EXTENSION);

		if (!in_array($file_ext, $allowed_types)) {
			$this->form_validation->set_message('validate_csv', 'The {field} field must be a CSV file.');
			return $this->errorRedirect("Invalid file type", $redirect);
		}


		if ($batch_refund_file['error'] != UPLOAD_ERR_OK) {
			return $this->errorRedirect("File upload error: " . $batch_refund_file['error'], $redirect);
		}

		$file_content = file_get_contents($batch_refund_file['tmp_name']);
		$bet_ids = str_getcsv($file_content, "\n");

		$params = [
			'bet_ids' => $bet_ids,
			'game_platform_id' => $gamePlatformId
		];

		$this->utils->info_log('========= end batch refund ============================');


		$success = true;
        $systemId = Queue_result::SYSTEM_UNKNOWN;
        $funcName = 'batch_refund';
        $callerType = Queue_result::CALLER_TYPE_ADMIN;
        $caller = $this->authentication->getUserId();
        $state = null;
        $lang = $this->language_function->getCurrentLanguage();
		$token = $this->lib_queue->commonAddRemoteJob($systemId, $funcName, $params, $callerType, $caller, $state, $lang);

		$this->utils->debug_log('manual batch refund', 'token', $token, 'params', $params);

        //goto queue page
        redirect('/system_management/common_queue/' . $token);
	}

	private function errorRedirect($msg, $redirect)
	{
		$this->alertMessage(self::MESSAGE_TYPE_ERROR, $msg);
		return redirect($redirect);
	}
	private function successRedirect($msg, $redirect)
	{
		$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, $msg);
		return redirect($redirect);
	}

	public function set_game_provider_bet_limit(){
		$game_platform_id = $this->input->post('set_bet_limit_game_platform_id');
		$username = $this->input->post('set_bet_limit_username');
		$limit_type = $this->input->post('set_bet_limit_type');
		$min_bet_limit = $this->input->post('min_bet_limit');
		$max_bet_limit = $this->input->post('max_bet_limit');
		$range_limit_id = $this->input->post('range_limit_id');

		#set bet limit settings
		$bet_limit_setting = array();
		if($limit_type == 'min_max'){
			if(empty($min_bet_limit) || empty($max_bet_limit)){
				$this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('Incomplete parameters.'));
				redirect('/system_management/other_functions');
				exit;
			}
			$bet_limit_setting['min_bet_limit'] = $min_bet_limit;
			$bet_limit_setting['max_bet_limit'] = $max_bet_limit;
		}elseif($limit_type == 'range'){
			if(empty($range_limit_id)){
				$this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('Incomplete parameters.'));
				redirect('/system_management/other_functions');
				exit;
			}
			#make sure that range limit id is integer and array
			$range_limit_id = explode(',', $range_limit_id);
			$range_limit_id = array_map('intval', $range_limit_id);
			$bet_limit_setting['limit_id'] = $range_limit_id;
		}

		#call api
		$this->game_api = $this->utils->loadExternalSystemLibObject($game_platform_id);
		$this->utils->debug_log('game_provider_bet_limit' ,$username, $bet_limit_setting, $game_platform_id);
		$result = $this->game_api->setMemberBetSetting($username, $bet_limit_setting);

		if(isset($result) && isset($result['success']) && $result['success']=='true'){
			$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang('Successfully set bet limit.'));
			redirect('/system_management/other_functions');
			exit;
		}
		$this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('Error setting bet limit. View response result.'));
		redirect('/system_management/other_functions');
		exit;
	}

	public function remote_batch_export_player_id(){
    	$this->load->library(['lib_queue', 'language_function', 'session']);
        $this->load->model(['player_model']);

        $data = [
            'title' => lang('Batch Export Player Id'),
            'sidebar' => 'system_management/sidebar',
            'activenav' => 'dev_functions'
        ];

        if (!$this->permissions->checkPermissions('dev_functions') || !$this->users->isT1User($this->authentication->getUsername())) {
            return $this->showErrorAccess($data['title'], $data['sidebar'], $data['activenav']);
        }

		$batch_export_player_id_file = isset($_FILES['batch_export_player_id_file']) ? $_FILES['batch_export_player_id_file'] : null;

		$redirect = '/system_management/other_functions';

		if (!$batch_export_player_id_file) {
			return $batch_export_player_id_file->errorRedirect("File not exist", $redirect);
		}

		$allowed_types = array('csv');
		$file_path = isset($_FILES['batch_export_player_id_file']['name']) ? $_FILES['batch_export_player_id_file']['name'] : null;

		$file_ext = pathinfo($file_path, PATHINFO_EXTENSION);

		if (!in_array($file_ext, $allowed_types)) {
			$this->form_validation->set_message('validate_csv', 'The {field} field must be a CSV file.');
			return $this->errorRedirect("Invalid file type", $redirect);
		}

		if ($batch_export_player_id_file['error'] != UPLOAD_ERR_OK) {
			return $this->errorRedirect("File upload error: " . $batch_export_player_id_file['error'], $redirect);
		}

		$file_content = file_get_contents($batch_export_player_id_file['tmp_name']);
		$usernames = str_getcsv($file_content, "\n");

        $params = [
            'usernames' => $usernames,
        ];

		$success = true;
        $systemId = Queue_result::SYSTEM_UNKNOWN;
        $funcName = 'batch_export_player_id';
        $callerType = Queue_result::CALLER_TYPE_ADMIN;
        $caller = $this->authentication->getUserId();
        $state = null;
        $lang = $this->language_function->getCurrentLanguage();
		$token = $this->lib_queue->commonAddRemoteJob($systemId, $funcName, $params, $callerType, $caller, $state, $lang);

		$this->utils->debug_log('Batch export player ids: ', 'token', $token, 'params', $params);

        //goto queue page
        redirect('/system_management/common_queue/' . $token);
    }

	public function dev_rebuild_totals(){
		$data = array('title' => lang('role.310'), 'sidebar' => 'system_management/sidebar',
			'activenav' => 'system');

		if (!$this->permissions->checkPermissions('dev_functions') && !$this->users->isT1User($this->authentication->getUsername())) {
			return $this->showErrorAccess($data['title'], $data['sidebar'], $data['activenav']);
		}

        $this->load->model(array('operatorglobalsettings','external_system', 'marketing', 'transactions', 'multiple_db_model'));
		$this->load->library(['lib_queue', 'language_function']);

		$userId = $this->authentication->getUserId();
		$username = $this->authentication->getUsername();
		$render = true;

		$by_date_to = $this->utils->getNowForMysql();
		$by_date_from = (new DateTime($by_date_to))->modify('-1 hour')->format('Y-m-d H').':00:00';

		$lock_rebuild_reports_range = $this->utils->getConfig('lock_rebuild_reports_range');
		if(!empty($lock_rebuild_reports_range)){
			$data['lock_rebuild_reports_range'] = $this->utils->isRebuildReportDateLocked($lock_rebuild_reports_range,null,null,$rlt,true);
		}else{
			$data['lock_rebuild_reports_range'] = null;
		}
		$data['by_date_from']  = $by_date_from;
		$data['by_date_to']    = $by_date_to;

		$this->loadDefaultTemplate(
			[],
			['resources/css/general/style.css'],
			array('title' => $data['title'],
				'activenav' => $data['activenav'],
				'userId' => $userId,
				'username' => $username), $data['sidebar'],
			'system_management/dev_functions/view_dev_rebuild_totals', $data, $render);
	}

	public function dev_sync_game_logs(){
		$data = array('title' => lang('role.310'), 'sidebar' => 'system_management/sidebar',
			'activenav' => 'system');

		if (!$this->permissions->checkPermissions('dev_functions') && !$this->users->isT1User($this->authentication->getUsername())) {
			return $this->showErrorAccess($data['title'], $data['sidebar'], $data['activenav']);
		}

        $this->load->model(array('operatorglobalsettings','external_system', 'marketing', 'transactions', 'multiple_db_model'));
		$this->load->library(['lib_queue', 'language_function']);

		$userId = $this->authentication->getUserId();
		$username = $this->authentication->getUsername();
		$render = true;

		$by_date_to = $this->utils->getNowForMysql();
		$by_date_from = (new DateTime($by_date_to))->modify('-1 hour')->format('Y-m-d H').':00:00';

		$data['by_date_from']  = $by_date_from;
		$data['by_date_to']    = $by_date_to;
		$data['playerName']    = null;

		$this->loadDefaultTemplate(
			[],
			['resources/css/general/style.css'],
			array('title' => $data['title'],
				'activenav' => $data['activenav'],
				'userId' => $userId,
				'username' => $username), $data['sidebar'],
			'system_management/dev_functions/view_dev_sync_game_logs', $data, $render);
	}

	public function dev_debug_queue(){
		$data = array('title' => lang('role.310'), 'sidebar' => 'system_management/sidebar',
			'activenav' => 'system');

		if (!$this->permissions->checkPermissions('dev_functions') && !$this->users->isT1User($this->authentication->getUsername())) {
			return $this->showErrorAccess($data['title'], $data['sidebar'], $data['activenav']);
		}

        $this->load->model(array('operatorglobalsettings','external_system', 'marketing', 'transactions', 'multiple_db_model'));
		$this->load->library(['lib_queue', 'language_function']);

		$userId = $this->authentication->getUserId();
		$username = $this->authentication->getUsername();
		$render = true;

		$data['queue_server_info'] = $this->utils->getConfig('rabbitmq_server');
        $data['redis_channel_key'] = $this->lib_queue->getRabbitmqQueueKey();

		$this->loadDefaultTemplate(
			['resources/js/highlight.pack.js'],
			['resources/css/general/style.css', 'resources/css/hljs.tomorrow.css'],
			array('title' => $data['title'],
				'activenav' => $data['activenav'],
				'userId' => $userId,
				'username' => $username), $data['sidebar'],
			'system_management/dev_functions/view_dev_debug_queue', $data, $render);
	}

	public function dev_clear_cache(){
		$data = array('title' => lang('role.310'), 'sidebar' => 'system_management/sidebar',
			'activenav' => 'system');

		if (!$this->permissions->checkPermissions('dev_functions') && !$this->users->isT1User($this->authentication->getUsername())) {
			return $this->showErrorAccess($data['title'], $data['sidebar'], $data['activenav']);
		}

        $this->load->model(array('operatorglobalsettings','external_system', 'marketing', 'transactions', 'multiple_db_model'));
		$this->load->library(['lib_queue', 'language_function']);

		$userId = $this->authentication->getUserId();
		$username = $this->authentication->getUsername();
		$render = true;

		$this->loadDefaultTemplate(
			[],
			['resources/css/general/style.css'],
			array('title' => $data['title'],
				'activenav' => $data['activenav'],
				'userId' => $userId,
				'username' => $username), $data['sidebar'],
			'system_management/dev_functions/view_dev_clear_cache', $data, $render);
	}

	public function dev_rebuild_seamless_balance_history(){
		$data = array('title' => lang('role.310'), 'sidebar' => 'system_management/sidebar',
			'activenav' => 'system');

		if (!$this->permissions->checkPermissions('dev_functions') && !$this->users->isT1User($this->authentication->getUsername())) {
			return $this->showErrorAccess($data['title'], $data['sidebar'], $data['activenav']);
		}

        $this->load->model(array('operatorglobalsettings','external_system', 'marketing', 'transactions', 'multiple_db_model'));
		$this->load->library(['lib_queue', 'language_function']);

		$userId = $this->authentication->getUserId();
		$username = $this->authentication->getUsername();
		$render = true;

		$by_date_to = $this->utils->getNowForMysql();
		$by_date_from = (new DateTime($by_date_to))->modify('-1 hour')->format('Y-m-d H').':00:00';

		$data['by_date_from']  = $by_date_from;
		$data['by_date_to']    = $by_date_to;
		$data['playerName']    = null;

		$this->loadDefaultTemplate(
			[],
			['resources/css/general/style.css'],
			array('title' => $data['title'],
				'activenav' => $data['activenav'],
				'userId' => $userId,
				'username' => $username), $data['sidebar'],
			'system_management/dev_functions/view_dev_rebuild_seamless_balance_history', $data, $render);
	}

	public function dev_sync_t1_gamegateway(){
		$data = array('title' => lang('role.310'), 'sidebar' => 'system_management/sidebar',
			'activenav' => 'system');

		if (!$this->permissions->checkPermissions('dev_functions') && !$this->users->isT1User($this->authentication->getUsername())) {
			return $this->showErrorAccess($data['title'], $data['sidebar'], $data['activenav']);
		}

        $this->load->model(array('operatorglobalsettings','external_system', 'marketing', 'transactions', 'multiple_db_model'));
		$this->load->library(['lib_queue', 'language_function']);

		$userId = $this->authentication->getUserId();
		$username = $this->authentication->getUsername();
		$render = true;

		$by_date_to = $this->utils->getNowForMysql();
		$by_date_from = (new DateTime($by_date_to))->modify('-1 hour')->format('Y-m-d H').':00:00';

		$data['by_date_from']  = $by_date_from;
		$data['by_date_to']    = $by_date_to;
		$data['playerName']    = null;

		$this->loadDefaultTemplate(
			[],
			['resources/css/general/style.css'],
			array('title' => $data['title'],
				'activenav' => $data['activenav'],
				'userId' => $userId,
				'username' => $username), $data['sidebar'],
			'system_management/dev_functions/view_dev_sync_t1_gamegateway', $data, $render);
	}

	public function dev_sync_games_report_timezones(){
		$data = array('title' => lang('role.310'), 'sidebar' => 'system_management/sidebar',
			'activenav' => 'system');

		if (!$this->permissions->checkPermissions('dev_functions') && !$this->users->isT1User($this->authentication->getUsername())) {
			return $this->showErrorAccess($data['title'], $data['sidebar'], $data['activenav']);
		}

        $this->load->model(array('operatorglobalsettings','external_system', 'marketing', 'transactions', 'multiple_db_model'));
		$this->load->library(['lib_queue', 'language_function']);

		$userId = $this->authentication->getUserId();
		$username = $this->authentication->getUsername();
		$render = true;

		$dateTo = $this->utils->getNowForMysql();
		$dateFrom = (new DateTime($dateTo))->modify('-1 hour')->format('Y-m-d H').':00:00';

		$data['dateFrom'] = $dateFrom;
		$data['dateTo'] = $dateTo;
		$data['playerName'] = null;

		$this->loadDefaultTemplate(
			[],
			['resources/css/general/style.css'],
			array('title' => $data['title'],
				'activenav' => $data['activenav'],
				'userId' => $userId,
				'username' => $username), $data['sidebar'],
			'system_management/dev_functions/view_dev_sync_games_report_timezones', $data, $render);
	}

	public function dev_balance_check_report(){
		$data = array('title' => lang('role.310'), 'sidebar' => 'system_management/sidebar',
			'activenav' => 'system');

		if(!$this->utils->getConfig('enable_balance_check_report')){
			return $this->showErrorAccess($data['title'], $data['sidebar'], $data['activenav']);
			$this->returnJsonResult(array("success" => false));
		}
		
		if (!$this->permissions->checkPermissions('dev_functions') && !$this->users->isT1User($this->authentication->getUsername())) {
			return $this->showErrorAccess($data['title'], $data['sidebar'], $data['activenav']);
		}
		$this->load->model([
			'balance_check_report','operatorglobalsettings', 'external_system', 'marketing', 'transactions', 'multiple_db_model',
			'payment_account', 'affiliatemodel', 'common_category', 'player', 'group_level'
		]);

		$this->load->model(array('payment_account', 'affiliatemodel','common_category','player','group_level'));
		$this->load->library(['lib_queue', 'language_function']);

		$userId = $this->authentication->getUserId();
		$username = $this->authentication->getUsername();
		$render = true;

		$dateTo = $this->utils->getNowForMysql();
		$dateFrom = (new DateTime($dateTo))->modify('-1 hour')->format('Y-m-d H').':00:00';

		$data['dateFrom'] = $dateFrom;
		$data['dateTo'] = $dateTo;
		$data['playerName'] = null;

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
		if ($this->permissions->checkPermissions('friend_referral_player')) {

			$data['referrer'] = $this->input->get('referrer');

		}

		$data['enable_freeze_top_in_list'] = $this->utils->_getEnableFreezeTopWithMethod(__METHOD__, $this->config->item('enable_freeze_top_method_list'));

		$this->loadDefaultTemplate(
			array(
				'resources/js/chosen.jquery.min.js',
				'resources/js/datatables.min.js',
				'resources/js/dataTables.responsive.min.js',
				'resources/js/dataTables.order.dom-checkbox.js',
				'resources/js/highlight.pack.js',
				'resources/js/chosen.jquery.min.js',
				'resources/third_party/bootstrap-multiselect-master/dist/js/bootstrap-multiselect.js'
			),
			array(
				'resources/css/general/style.css',
				'resources/css/datatables.min.css',
				'resources/css/hljs.tomorrow.css',
				'resources/css/chosen.min.css',
				'resources/css/select2.min.css',
				'resources/third_party/bootstrap-multiselect-master/dist/css/bootstrap-multiselect.css'
			),
			array('title' => $data['title'],
				'activenav' => $data['activenav'],
				'userId' => $userId,
				'username' => $username), $data['sidebar'],
			'system_management/dev_functions/view_dev_balance_check_report', $data, $render);

	}

	public function post_sync_games_report_timezones(){
		$data = array('title' => lang('Sync Games Report Timezones'), 'sidebar' => 'system_management/sidebar',
			'activenav' => 'system');

		if (!$this->permissions->checkPermissions('dev_functions') || !$this->users->isT1User($this->authentication->getUsername())) {
			return $this->showErrorAccess($data['title'], $data['sidebar'], $data['activenav']);
		}
		$this->load->library(['lib_queue', 'language_function']);
		$this->load->model(['queue_result']);

		$success=true;
		$dateTimeFrom=$this->input->post('dateFrom');
		$dateTimeTo=$this->input->post('dateTo');
		$dateFrom = date('Y-m-d',strtotime($dateTimeFrom));
		$dateTo = date('Y-m-d',strtotime($dateTimeTo));
		$gameApiId=$this->input->post('gameApiId');
		$playerName=$this->input->post('playerName');
		$this->utils->debug_log('post_sync_games_report_timezones','dateFrom', $dateFrom, 'dateTo', $dateTo, 'gameApiId', $gameApiId, 'playerName', $playerName);

		
		$caller=$this->authentication->getUserId();
		$state=null;
		$lang=$this->language_function->getCurrentLanguage();
		$funcName = 'sync_games_report_timezones';
		$callerType = Queue_result::CALLER_TYPE_ADMIN;
		$systemId = Queue_result::SYSTEM_UNKNOWN;
		$playerId = null;
		if(!empty($playerName)){
			$playerId = intval($this->player_model->getPlayerIdByUsername($playerName));
	        if (!$playerId) {
	        	$this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('Player not found.'));
		    	return redirect('/system_management/dev_sync_games_report_timezones');
			}
		}

		$params = [
			'dateTo' => $dateTo,
			'dateFrom' => $dateFrom,
			'gameApiId' => $gameApiId,
			'playerId' => $playerId ? $playerId : '_null',
		];

		$token=$this->lib_queue->commonAddRemoteJob($systemId,$funcName, $params, $callerType, $caller, $state, $lang);

		//goto queue page
		redirect('/system_management/common_queue/'.$token);
	}

    public function post_sync_latest_game_records() {
        $this->load->library(['lib_queue', 'language_function', 'session']);

        $data = [
            'title' => lang('Sync Latest Game Records'),
            'sidebar' => 'system_management/sidebar',
            'activenav' => 'dev_functions'
        ];

        if (!$this->permissions->checkPermissions('dev_functions') || !$this->users->isT1User($this->authentication->getUsername())) {
            return $this->showErrorAccess($data['title'], $data['sidebar'], $data['activenav']);
        }

        $success = true;
        $date_from = $this->input->post('date_from');
        $date_to = $this->input->post('date_to');
        $systemId = Queue_result::SYSTEM_UNKNOWN;
        $funcName = 'sync_latest_game_records';
        $callerType = Queue_result::CALLER_TYPE_ADMIN;
        $caller = $this->authentication->getUserId();
        $state = null;
        $lang = $this->language_function->getCurrentLanguage();

        $params = [
            'date_from' => $date_from,
            'date_to' => $date_to,
        ];

        if ($date_from >= $date_to) {
            $success = false;
            $msg = lang('Invalid datetime range.');
        }

        //go back
        if (!$success) {
            $this->alertMessage(self::MESSAGE_TYPE_ERROR, $msg);
            return redirect('/system_management/other_functions');
        }

        $token = $this->lib_queue->commonAddRemoteJob($systemId, $funcName, $params, $callerType, $caller, $state, $lang);

        $this->utils->debug_log('manual sync_latest_game_records', 'token', $token, 'params', $params);

        //goto queue page
        redirect('/system_management/common_queue/' . $token);
    }

	public function dev_sync_cashback(){
		$data = array('title' => lang('role.310'), 'sidebar' => 'system_management/sidebar',
			'activenav' => 'system');

		if (!$this->permissions->checkPermissions('dev_functions') && !$this->users->isT1User($this->authentication->getUsername())) {
			return $this->showErrorAccess($data['title'], $data['sidebar'], $data['activenav']);
		}

		if (!$this->users->isT1Admin($this->authentication->getUsername())) {
			return $this->showErrorAccess($data['title'], $data['sidebar'], $data['activenav']);
		}

		if($this->utils->isEnabledFeature('close_cashback')){
			return $this->showErrorAccess($data['title'], $data['sidebar'], $data['activenav']);
		}

        $this->load->model(array('operatorglobalsettings','external_system', 'marketing', 'transactions', 'multiple_db_model'));
		$this->load->library(['lib_queue', 'language_function']);

		$userId = $this->authentication->getUserId();
		$username = $this->authentication->getUsername();
		$render = true;
		$data['cashback_date'] = null;

		$this->loadDefaultTemplate(
			[],
			['resources/css/general/style.css'],
			array('title' => $data['title'],
				'activenav' => $data['activenav'],
				'userId' => $userId,
				'username' => $username), $data['sidebar'],
			'system_management/dev_functions/view_dev_sync_cashback', $data, $render);
	}

    public function dev_regenerate_all_report(){
        $data = array('title' => lang('role.310'), 'sidebar' => 'system_management/sidebar', 'activenav' => 'system');

        if (!$this->permissions->checkPermissions('dev_functions') && !$this->users->isT1User($this->authentication->getUsername())) {
            return $this->showErrorAccess($data['title'], $data['sidebar'], $data['activenav']);
        }

        $this->load->model(array('operatorglobalsettings','external_system', 'marketing', 'transactions', 'multiple_db_model'));
        $this->load->library(['lib_queue', 'language_function']);

        $userId = $this->authentication->getUserId();
        $username = $this->authentication->getUsername();
        $render = true;

        $by_date_to = $this->utils->getNowForMysql();
        $by_date_from = (new DateTime($by_date_to))->modify('-1 hour')->format('Y-m-d H').':00:00';

        $data['by_date_from']  = $by_date_from;
        $data['by_date_to']    = $by_date_to;

        $this->loadDefaultTemplate(
            [],
            ['resources/css/general/style.css'],
            array('title' => $data['title'],
                'activenav' => $data['activenav'],
                'userId' => $userId,
                'username' => $username), $data['sidebar'],
            'system_management/dev_functions/view_dev_regenerate_all_report', $data, $render);
    }

    public function dev_sync_summary_game_total_bet_daily(){
		$data = array('title' => lang('role.310'), 'sidebar' => 'system_management/sidebar',
			'activenav' => 'system');

		if (!$this->permissions->checkPermissions('dev_functions') && !$this->users->isT1User($this->authentication->getUsername())) {
			return $this->showErrorAccess($data['title'], $data['sidebar'], $data['activenav']);
		}

		$userId = $this->authentication->getUserId();
		$username = $this->authentication->getUsername();
		$render = true;
		$data['date'] = $this->utils->getTodayForMysql();

		$this->loadDefaultTemplate(
			[],
			['resources/css/general/style.css'],
			array('title' => $data['title'],
				'activenav' => $data['activenav'],
				'userId' => $userId,
				'username' => $username), $data['sidebar'],
			'system_management/dev_functions/view_dev_sync_summary_game_total_bet_daily', $data, $render);
	}

	public function post_sync_summary_game_total_bet_daily(){
		$data = array('title' => lang('Sync Games Report Timezones'), 'sidebar' => 'system_management/sidebar',
			'activenav' => 'system');

		if (!$this->permissions->checkPermissions('dev_functions') || !$this->users->isT1User($this->authentication->getUsername())) {
			return $this->showErrorAccess($data['title'], $data['sidebar'], $data['activenav']);
		}

		$this->load->library(['lib_queue', 'language_function']);
		$success=true;
		$date=$this->input->post('summary_game_total_date');
		$this->utils->debug_log('post_sync_summary_game_total_bet_daily','date', $date);

		
		$caller=$this->authentication->getUserId();
		$state=null;
		$lang=$this->language_function->getCurrentLanguage();
		$funcName = 'sync_summary_game_total_bet_daily';
		$callerType = Queue_result::CALLER_TYPE_ADMIN;
		$systemId = Queue_result::SYSTEM_UNKNOWN;

		$params = [
			'date' => $date,
			// 'currency' => $this->utils->getActiveTargetDB(),
		];

		$token=$this->lib_queue->commonAddRemoteJob($systemId,$funcName, $params, $callerType, $caller, $state, $lang);

		//goto queue page
		redirect('/system_management/common_queue/'.$token);
	}

    public function dev_batch_export_player_id(){
        $data = array('title' => lang('role.310'), 'sidebar' => 'system_management/sidebar', 'activenav' => 'system');

        if (!$this->permissions->checkPermissions('dev_functions') && !$this->users->isT1User($this->authentication->getUsername())) {
            return $this->showErrorAccess($data['title'], $data['sidebar'], $data['activenav']);
        }

        $this->load->model(array('operatorglobalsettings','external_system', 'marketing', 'transactions', 'multiple_db_model'));
        $this->load->library(['lib_queue', 'language_function']);

        $userId = $this->authentication->getUserId();
        $username = $this->authentication->getUsername();
        $render = true;

        $this->loadDefaultTemplate(
            [],
            ['resources/css/general/style.css'],
            array('title' => $data['title'],
                'activenav' => $data['activenav'],
                'userId' => $userId,
                'username' => $username), $data['sidebar'],
            'system_management/dev_functions/view_dev_batch_export_player_id', $data, $render);
    }

    public function dev_sync_affiliate_earnings(){
		$data = array('title' => lang('role.310'), 'sidebar' => 'system_management/sidebar',
			'activenav' => 'system');

		if (!$this->permissions->checkPermissions('dev_functions') && !$this->users->isT1User($this->authentication->getUsername())) {
			return $this->showErrorAccess($data['title'], $data['sidebar'], $data['activenav']);
		}

		if (!$this->users->isT1Admin($this->authentication->getUsername())) {
			return $this->showErrorAccess($data['title'], $data['sidebar'], $data['activenav']);
		}

		if($this->utils->isEnabledFeature('close_aff_and_agent')){
			return $this->showErrorAccess($data['title'], $data['sidebar'], $data['activenav']);
		}

		$userId = $this->authentication->getUserId();
		$username = $this->authentication->getUsername();
		$render = true;

		$this->loadDefaultTemplate(
			[],
			['resources/css/general/style.css'],
			array('title' => $data['title'],
				'activenav' => $data['activenav'],
				'userId' => $userId,
				'username' => $username), $data['sidebar'],
			'system_management/dev_functions/view_dev_sync_affiliate_earnings', [], $render);
	}

	public function dev_sync_missing_payout_report(){
		$data = array('title' => lang('role.310'), 'sidebar' => 'system_management/sidebar',
			'activenav' => 'system');

		if (!$this->permissions->checkPermissions('dev_functions') && !$this->users->isT1User($this->authentication->getUsername())) {
			return $this->showErrorAccess($data['title'], $data['sidebar'], $data['activenav']);
		}

        $this->load->model(array('operatorglobalsettings','external_system', 'marketing', 'transactions', 'multiple_db_model'));
		$this->load->library(['lib_queue', 'language_function']);

		$userId = $this->authentication->getUserId();
		$username = $this->authentication->getUsername();
		$render = true;

		$by_date_to = $this->utils->getNowForMysql();
		$by_date_from = (new DateTime($by_date_to))->modify('-1 hour')->format('Y-m-d H').':00:00';

		$data['by_date_from']  = $by_date_from;
		$data['by_date_to']    = $by_date_to;
		$data['playerName']    = null;

		$this->loadDefaultTemplate(
			[],
			['resources/css/general/style.css'],
			array('title' => $data['title'],
				'activenav' => $data['activenav'],
				'userId' => $userId,
				'username' => $username), $data['sidebar'],
			'system_management/dev_functions/view_dev_sync_missing_payout_report', $data, $render);
	}

	public function post_missing_payout_report($isAll="true"){

		$data = array('title' => lang('Dev Functions'), 'sidebar' => 'system_management/sidebar',
			'activenav' => 'dev_functions');

		if (!$this->permissions->checkPermissions('dev_functions') ||
			!$this->users->isT1User($this->authentication->getUsername()) ||
			!$this->users->isT1Admin($this->authentication->getUsername())
		) {
			return $this->showErrorAccess($data['title'], $data['sidebar'], $data['activenav']);
		}

		$by_date_from=$this->input->post('by_date_from');
		$by_date_to=$this->input->post('by_date_to');
		$playerName=$this->input->post('playerName');

		$this->utils->debug_log('post_missing_payout_report','by_date_from', $by_date_from, 'by_date_to', $by_date_to, 'playerName', $playerName);

		$success=true;

		$this->load->library(['lib_queue', 'language_function']);
		$this->load->model(['queue_result']);
		$caller=$this->authentication->getUserId();
		$state=null;
		$lang=$this->language_function->getCurrentLanguage();
		//$commandFunc = 'do_rebuild_games_total_job';
		$funcName = 'check_seamless_round_status';
		$callerType = Queue_result::CALLER_TYPE_ADMIN;
		$systemId = Queue_result::SYSTEM_UNKNOWN;

		$params = [
			'date_to' => $by_date_to,
			'date_from' => $by_date_from,
			'playerName' => $playerName,
		];

		$token=$this->lib_queue->commonAddRemoteJob($systemId,$funcName, $params, $callerType, $caller, $state, $lang);
		//goto queue page
		redirect('/system_management/common_queue/'.$token);
	}

    public function post_cancel_game_round() {
        $this->load->library(['lib_queue', 'language_function', 'session']);

        $data = [
            'title' => lang('Cancel Game Round'),
            'sidebar' => 'system_management/sidebar',
            'activenav' => 'dev_functions'
        ];

        if (!$this->permissions->checkPermissions('dev_functions') || !$this->users->isT1User($this->authentication->getUsername())) {
            return $this->showErrorAccess($data['title'], $data['sidebar'], $data['activenav']);
        }

        $success = true;
        $systemId = Queue_result::SYSTEM_UNKNOWN;
        $funcName = 'cancel_game_round';
        $callerType = Queue_result::CALLER_TYPE_ADMIN;
        $caller = $this->authentication->getUserId();
        $state = null;
        $lang = $this->language_function->getCurrentLanguage();

        $params = [
            'game_platform_id' => $this->input->post('game_platform_id'),
            'game_username' => $this->input->post('game_username'),
            'round_id' => $this->input->post('round_id'),
            'game_code' => $this->input->post('game_code'),
        ];

        //go back
        /* if (!$success) {
            $this->alertMessage(self::MESSAGE_TYPE_ERROR, $msg);
            return redirect('/system_management/other_functions');
        } */

        $token = $this->lib_queue->commonAddRemoteJob($systemId, $funcName, $params, $callerType, $caller, $state, $lang);

        $this->utils->debug_log(__METHOD__, 'token', $token, 'params', $params);

        //goto queue page
        redirect('/system_management/common_queue/' . $token);
    }

    public function dev_cancel_game_round() {
        $data = array('title' => lang('role.310'), 'sidebar' => 'system_management/sidebar', 'activenav' => 'system');

        if (!$this->permissions->checkPermissions('dev_functions') && !$this->users->isT1User($this->authentication->getUsername())) {
            return $this->showErrorAccess($data['title'], $data['sidebar'], $data['activenav']);
        }

        $this->load->model(array('operatorglobalsettings','external_system', 'marketing', 'transactions', 'multiple_db_model'));
        $this->load->library(['lib_queue', 'language_function']);

        $userId = $this->authentication->getUserId();
        $username = $this->authentication->getUsername();
        $render = true;

        // -- get all game API from DB
        $all_apis = $this->external_system->getAllGameApisWithSystemNameAndStatus() ?: array();
        $cancel_round_game_apis = [];

        foreach ($all_apis as $api) {
            if ($api['status']) {
                $game_api = $this->utils->loadExternalSystemLibObject($api['id']);

                if ($game_api) {
                    if (method_exists($game_api, 'cancelGameRound')) {
                        array_push($cancel_round_game_apis, [
                            'game_platform_id' => $api['id'],
                            'game_platform_name' => $api['system_name'],
                        ]);
                    }
                }
            }
        }

        $data['cancel_round_game_apis'] = $cancel_round_game_apis;

        $this->loadDefaultTemplate(
            [],
            ['resources/css/general/style.css'],
            array('title' => $data['title'],
                'activenav' => $data['activenav'],
                'userId' => $userId,
                'username' => $username), $data['sidebar'],
            'system_management/dev_functions/view_dev_cancel_game_round', $data, $render);
    }

	public function dev_refresh_all_player_balance_in_specific_game_provider(){
		$data = array('title' => lang('Refresh all player balance'), 'sidebar' => 'system_management/sidebar',
			'activenav' => 'system');

		if (!$this->utils->getConfig('enable_refresh_all_player_balance_in_specific_game_provider')){
			return $this->showErrorAccess($data['title'], $data['sidebar'], $data['activenav']);
		}
		
		if (!$this->permissions->checkPermissions('dev_functions') && !$this->users->isT1User($this->authentication->getUsername())) {
			return $this->showErrorAccess($data['title'], $data['sidebar'], $data['activenav']);
		}

		$userId = $this->authentication->getUserId();
		$username = $this->authentication->getUsername();
		$render = true;
		$data['date'] = $this->utils->getTodayForMysql();

		$this->loadDefaultTemplate(
			[],
			['resources/css/general/style.css'],
			array('title' => $data['title'],
				'activenav' => $data['activenav'],
				'userId' => $userId,
				'username' => $username), $data['sidebar'],
			'system_management/dev_functions/view_dev_refresh_all_player_balance_in_specific_game_provider', $data, $render);
	}

	public function post_refresh_all_player_balance_in_specific_game_provider(){

		$data = array('title' => lang('Refresh all player balance'), 'sidebar' => 'system_management/sidebar',
			'activenav' => 'system');

		if (!$this->utils->getConfig('enable_refresh_all_player_balance_in_specific_game_provider')){
			return $this->showErrorAccess($data['title'], $data['sidebar'], $data['activenav']);
		}

		if (!$this->permissions->checkPermissions('dev_functions') || !$this->users->isT1User($this->authentication->getUsername())) {
			return $this->showErrorAccess($data['title'], $data['sidebar'], $data['activenav']);
		}

		$params = array(
			"game_platform_id" 	 => $this->input->post('game_platform_id'),
			"is_only_registered" => $this->input->post('is_only_registered'),
		);

		$this->load->library(['lib_queue', 'language_function']);
		$this->utils->debug_log('post_refresh_all_player_balance_in_specific_game_provider','params', $params);
		
		$success = true;
		$caller = $this->authentication->getUserId();
		$state = null;
		$lang = $this->language_function->getCurrentLanguage();
		$funcName = 'refresh_all_player_balance_in_specific_game_provider';
		$callerType = Queue_result::CALLER_TYPE_ADMIN;
		$systemId = Queue_result::SYSTEM_UNKNOWN;


		if(empty($params['game_platform_id'])){
			$success = false;
			$msg = 'Game Platform is required';
		}

		$api = $this->utils->loadExternalSystemLibObject($params['game_platform_id']);
		if($success && $api->isSeamLessGame()){
			$success = false;
			$msg = 'Game api should be a transfer game!';
		}

        if (!$success) {
            $this->alertMessage(self::MESSAGE_TYPE_ERROR, $msg);
            return redirect('/system_management/dev_refresh_all_player_balance_in_specific_game_provider');
        }

		$token=$this->lib_queue->commonAddRemoteJob($systemId,$funcName, $params, $callerType, $caller, $state, $lang);

		//goto queue page
		redirect('/system_management/common_queue/'.$token);
	}

	public function dev_transfer_all_players_subwallet_to_main_wallet(){

		$data = array('title' => lang('role.310'), 'sidebar' => 'system_management/sidebar',
			'activenav' => 'system');
		$message = lang('Deprecated!');
		$this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);
		$this->loadDefaultTemplate(null, null, array('title' => $data['title']), $data['sidebar'], null, null, true);

		// if (!$this->permissions->checkPermissions('dev_functions') && !$this->users->isT1User($this->authentication->getUsername())) {
		// 	return $this->showErrorAccess($data['title'], $data['sidebar'], $data['activenav']);
		// }

		// $userId = $this->authentication->getUserId();
		// $username = $this->authentication->getUsername();
		// $render = true;
		// $data['date'] = $this->utils->getTodayForMysql();

		// $this->loadDefaultTemplate(
		// 	[],
		// 	['resources/css/general/style.css'],
		// 	array('title' => $data['title'],
		// 		'activenav' => $data['activenav'],
		// 		'userId' => $userId,
		// 		'username' => $username), $data['sidebar'],
		// 	'system_management/dev_functions/view_dev_transfer_all_players_subwallet_to_main_wallet', $data, $render);
	}

	public function post_transfer_all_players_subwallet_to_main_wallet(){
		$data = array('title' => lang('role.310'), 'sidebar' => 'system_management/sidebar',
			'activenav' => 'system');
		$message = lang('Deprecated!');
		$this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);
		$this->loadDefaultTemplate(null, null, array('title' => $data['title']), $data['sidebar'], null, null, true);
		
		// $data = array('title' => lang('Transfer All To Main Wallet'), 'sidebar' => 'system_management/sidebar',
		// 	'activenav' => 'system');

		// if (!$this->permissions->checkPermissions('dev_functions') || !$this->users->isT1User($this->authentication->getUsername())) {
		// 	return $this->showErrorAccess($data['title'], $data['sidebar'], $data['activenav']);
		// }

		// $params = array(
		// 	"game_id" => $this->input->post('game_id'),
		// 	"min_balance" => $this->input->post('min_balance'),
		// 	"max_balance" => $this->input->post('max_balance'),
		// );
		// // echo "<pre>";
		// // print_r($params);exit();


		// $this->load->library(['lib_queue', 'language_function']);
		// $success=true;
		// $date=$this->input->post('summary_game_total_date');
		// $this->utils->debug_log('post_transfer_all_players_subwallet_to_main_wallet','params', $params);

		
		// $caller = $this->authentication->getUserId();
		// $state = null;
		// $lang = $this->language_function->getCurrentLanguage();
		// $funcName = 'transfer_all_players_subwallet_to_main_wallet';
		// $callerType = Queue_result::CALLER_TYPE_ADMIN;
		// $systemId = Queue_result::SYSTEM_UNKNOWN;


		// $token=$this->lib_queue->commonAddRemoteJob($systemId,$funcName, $params, $callerType, $caller, $state, $lang);

		// //goto queue page
		// redirect('/system_management/common_queue/'.$token);
	}

    public function post_clear_game_logs_md5_sum($isAll="true"){

        $data = array('title' => lang('Clear Game Logs Md5 Sum'), 'sidebar' => 'system_management/sidebar',
            'activenav' => 'dev_functions');

        if (!$this->permissions->checkPermissions('dev_functions') || !$this->users->isT1User($this->authentication->getUsername())) {
            return $this->showErrorAccess($data['title'], $data['sidebar'], $data['activenav']);
        }

        if ($isAll == "true") {
            $uri = '/system_management/other_functions';
        } else {
            $uri = '/system_management/dev_clear_game_logs_md5_sum';
        }

        $game_platform_id = $this->input->post('game_platform_id');
        $external_unique_ids = !empty($this->input->post('external_unique_ids')) ? json_decode($this->input->post('external_unique_ids'), true) : [];

        if (empty($game_platform_id)) {
            $msg = 'Select Game API';
            $this->alertMessage(self::MESSAGE_TYPE_ERROR, $msg);
            redirect($uri);
        }

        if (!is_array($external_unique_ids)) {
            $msg = 'External unique ids must be an array';
            $this->alertMessage(self::MESSAGE_TYPE_ERROR, $msg);
            redirect($uri);
        }

        if (empty($external_unique_ids)) {
            $msg = 'External unique ids must not be empty';
            $this->alertMessage(self::MESSAGE_TYPE_ERROR, $msg);
            redirect($uri);
        }

        $this->load->model(['game_logs']);
        $success = $this->game_logs->clearMd5SumByExternalUniqueIds($game_platform_id, $external_unique_ids);

        if ($success) {
            $this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang('Clear md5 sum successfully'));
        } else {
            $this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('Clear failed'));
        }

        redirect($uri);
    }

    public function dev_clear_game_logs_md5_sum() {
        $data = array('title' => lang('Clear Game Logs Md5 Sum'), 'sidebar' => 'system_management/sidebar', 'activenav' => 'system');

        if (!$this->permissions->checkPermissions('dev_functions') || !$this->users->isT1User($this->authentication->getUsername())) {
            return $this->showErrorAccess($data['title'], $data['sidebar'], $data['activenav']);
        }

        $userId = $this->authentication->getUserId();
        $username = $this->authentication->getUsername();
        $render = true;

        $this->loadDefaultTemplate(
            [],
            ['resources/css/general/style.css'],
            array('title' => $data['title'],
                'activenav' => $data['activenav'],
                'userId' => $userId,
                'username' => $username), $data['sidebar'],
            'system_management/dev_functions/view_dev_clear_game_logs_md5_sum', $data, $render);
    }

    public function dev_update_player_agent_and_affiliate(){
		$data = array('title' => lang('role.310'), 'sidebar' => 'system_management/sidebar',
			'activenav' => 'system');

		if (!$this->permissions->checkPermissions('dev_functions') && !$this->users->isT1User($this->authentication->getUsername())) {
			return $this->showErrorAccess($data['title'], $data['sidebar'], $data['activenav']);
		}

		if( !$this->permissions->checkPermissions('assign_player_under_affiliate') || !$this->permissions->checkPermissions('assign_player_under_agent') ){
			return $this->showErrorAccess($data['title'], $data['sidebar'], $data['activenav']);
		}

		$userId = $this->authentication->getUserId();
		$username = $this->authentication->getUsername();
		$render = true;

		$this->load->model(array('affiliatemodel','agency_model'));
        $affiliates = $this->affiliatemodel->getAllActivtedAffiliates(false, true);
        $data['affiliates'] = array_column(is_array($affiliates) ? $affiliates : array(), 'username', 'affiliateId');
        $agents = $this->agency_model->get_active_agents(false, true);
        $data['agents'] = array_column(is_array($agents) ? $agents : array(), 'agent_name', 'agent_id');

		$this->loadDefaultTemplate(
			['resources/js/highlight.pack.js',
			'resources/js/datatables.min.js',
            'resources/js/select2.min.js',
            'resources/js/player_management/vipsetting_sync.js',
            'resources/third_party/bootstrap-multiselect-master/dist/js/bootstrap-multiselect.js'],
			['resources/css/general/style.css',
			'resources/css/hljs.tomorrow.css',
			'resources/css/datatables.min.css',
            'resources/css/select2.min.css',
            'resources/third_party/bootstrap-multiselect-master/dist/css/bootstrap-multiselect.css'],
			array('title' => $data['title'],
				'activenav' => $data['activenav'],
				'userId' => $userId,
				'username' => $username), $data['sidebar'],
			'system_management/dev_functions/view_dev_update_player_agent_and_affiliate', $data, $render);
	}
}

/////END OF FILE//////////////////