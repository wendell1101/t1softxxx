<?php
/**
 * static_redemption_code_module
 *
 * @property Static_redemption_code_model $static_redemption_code_model
 */
trait static_redemption_code_module
{

	/**
	 * static_redemption_code_module
	 * $config['enable_static_redemption_code_system']
 	 * $config['enable_static_redemption_code_system_in_playercenter']
 	 * $config['static_redemption_code_promo_cms_id']
	 */
	public function staticRedemptionCodeCategoryManager()
	{
		if (!$this->static_redemption_code_model->checkRedemptionCodeEnable() || !$this->permissions->checkPermissions('view_static_redemption_code_category')) {
			$this->error_access();
			return;
		}

		$this->loadTemplate(lang('redemptionCode.staticRedemptionCodeCategoryManager'), '', '', 'marketing');
		$this->template->add_js($this->utils->thirdpartyUrl('bootstrap-switch/3.3.4/js/bootstrap-switch.min.js'));
		$this->template->add_css($this->utils->thirdpartyUrl('bootstrap-switch/3.3.4/css/bootstrap3/bootstrap-switch.min.css'));
		$this->template->add_css('resources/css/select2.min.css');
		$this->template->add_js('resources/js/select2.full.min.js');
		$this->template->add_css('resources/third_party/bootstrap-multiselect-master/dist/css/bootstrap-multiselect.css');
        $this->template->add_js('resources/third_party/bootstrap-multiselect-master/dist/js/bootstrap-multiselect.js');
		$this->template->write_view('sidebar', 'marketing_management/sidebar');


		$data = [];
		if (!$this->permissions->checkPermissions('manage_static_redemption_code_category')) {
			$data['export_report_permission'] = FALSE;
		} else {
			$data['export_report_permission'] = TRUE;
		}
        $data['conditions'] = $this->safeLoadParams(array(
			'by_date_from' => $this->utils->get7DaysAgoForMysql(),
			'by_date_to' => $this->utils->getTodayForMysql(),
            'redemption_code' => '',
            'codeStatus' => 'All',
		));

        $data['codeStatus_options'] = [
			'All' => lang('All'),
			static_redemption_code_model::CATEGORY_STATUS_ACTIVATED => lang('redemptionCode.categoryActive'),
			static_redemption_code_model::CATEGORY_STATUS_DEACTIVATE => lang('redemptionCode.categoryDeactive'),
		];

		$data['manage_static_redemption_code_category'] = $this->permissions->checkPermissions('manage_static_redemption_code_category') ? TRUE : FALSE;

		$data['levels'] = $this->group_level->getAllPlayerLevelsDropdown(false);

        $data['_controller'] = $this;
		$this->template->write_view('main_content', 'marketing_management/redemptioncode/static_category_manager', $data);
		$this->template->render();
	}

	public function staticRedemptionCodeList($search_apply_date = 'off')
	{
		if (!$this->static_redemption_code_model->checkRedemptionCodeEnable() || !$this->permissions->checkPermissions('manage_static_redemption_code')) {
			$this->error_access();
			return;
		}

		$this->loadTemplate(lang('redemptionCode.staticRedemptionCodeList'), '', '', 'marketing');
		// $this->template->add_js($this->utils->thirdpartyUrl('bootstrap-switch/3.3.4/js/bootstrap-switch.min.js'));
		// $this->template->add_css($this->utils->thirdpartyUrl('bootstrap-switch/3.3.4/css/bootstrap3/bootstrap-switch.min.css'));
		$this->template->add_css($this->utils->thirdpartyUrl('bootstrap-multiselect-master/dist/css/bootstrap-multiselect.css'));
		$this->template->add_js($this->utils->thirdpartyUrl('bootstrap-multiselect-master/dist/js/bootstrap-multiselect.js'));
		$this->template->write_view('sidebar', 'marketing_management/sidebar');

		$data = [];
		if (!$this->permissions->checkPermissions('manage_static_redemption_code')) {
			$data['export_report_permission'] = FALSE;
		} else {
			$data['export_report_permission'] = TRUE;
		}

		$exclud_deleted_type = $this->utils->getConfig('redemption_code_exclud_deleted_type');
		$data['redemptionCodeCategorys'] = $this->static_redemption_code_model->getAllCategoryTypeName($exclud_deleted_type);
		list($from, $to) = $this->utils->getTodayStringRange();
		$data['conditions'] = $this->safeLoadParams([
			'codeType' => '',
			'redemptionCode' => '',
			'username' => '',
			'bonus' => null,
			'bonusRange' => "equalTo",
			'codeStatus' => "All",
			'apply_date_from' => $from,
			'apply_date_to' => $to,
			'enable_apply_date' => '0',
			'create_date_from' => $from,
			'create_date_to' => $to,
			'enable_create_date'  => '1',
		]);
		$data['default_date_from'] = $from;
		$data['default_date_to'] = $to;
		$data['selected_include_tags'] = $this->input->get_post('tag_list_included');
		$data['player_tags'] = $this->player->getAllTagsOnly();

		$data['bonusRange_options'] = [
			'equalTo' => lang('symbol.equalTo'),
			'greaterThanOrEqualTo' => lang('symbol.greaterThanOrEqualTo'),
			'lessThanOrEqualTo' => lang('symbol.lessThanOrEqualTo'),
			'lessThan' => lang('symbol.lessThan'),
			'greaterThan' => lang('symbol.greaterThan'),
		];
		$data['codeStatus_options'] = [
			'All' => lang('All'),
			static_redemption_code_model::CODE_STATUS_UNUSED => lang('redemptionCode.codeUnused'),
			static_redemption_code_model::CODE_STATUS_USED => lang('redemptionCode.codeUsed'),
			static_redemption_code_model::CODE_STATUS_EXPIRED => lang('redemptionCode.codeExpired'),
		];
		$this->template->write_view('main_content', 'marketing_management/redemptioncode/view_static_redemptioncode_report', $data);

		$this->template->render();
	}

	public function addStaticRedemptionCodeCategory()
	{
		if (!$this->permissions->checkPermissions('manage_static_redemption_code_category')) {
			$result = [
				'success' => false,
				'errorMsg' => lang('role.nopermission')
			];
			return $this->returnCommon(BaseController::MESSAGE_TYPE_ERROR, $result['errorMsg'], $result, '/marketing_management/staticRedemptionCodeCategoryManager');
		}

		$form_input = $this->input->post();
		$operator = $this->authentication->getUsername();
		$timestamp = $this->utils->getNowForMysql();
		$action_logs = "|[$timestamp]add by $operator|";
		$quantity = $form_input['totalRedeemable'];

		if (empty($quantity)) {
			$result = [
				'success' => false,
				'noteType' => 'quantity',
				'errorMsg' => lang('Kindly fill up total redeemable count'),
			];
			return $this->returnCommon(BaseController::MESSAGE_TYPE_ERROR, $result['errorMsg'], $result, '/marketing_management/staticRedemptionCodeCategoryManager');
		}

		$totalRedeemableLimit = $this->utils->getConfig('redemption_code_total_redeemable_limit')?:600000;
		if (floatval($quantity) > $totalRedeemableLimit || floatval($quantity) <= 0) {

			$result = [
				'success' => false,
				'noteType' => 'quantity',
				'errorMsg' => sprintf(lang('redemptionCode.static.quantityRule'), $totalRedeemableLimit),//sprintf(lang('Total Redeemable Quantity need to be greater than 0 less than %s'), $totalRedeemableLimit),
			];
			return $this->returnCommon(BaseController::MESSAGE_TYPE_ERROR, $result['errorMsg'], $result, '/marketing_management/staticRedemptionCodeCategoryManager');
		}

		$_withdrawal_rules = $this->setWithdrawalRules($form_input);
		$_withdrawal_rules['bonusApplicationLimit'] = $this->setBonusApplicationLimit($form_input);
		$_bonus_rules = $this->setBonusReleaseRule($form_input);

		if($this->utils->safeGetArray($form_input, 'enableSameDayDeposit') === 'on'){
			$_bonus_rules['enableSameDayDeposit'] = true;
			$_bonus_rules['sameDayDepositAmount'] = $this->utils->safeGetArray($form_input, 'sameDayDepositAmount') ?: 0;
		} else {
			$_bonus_rules['enableSameDayDeposit'] = false;
		}
		if($this->utils->safeGetArray($form_input, 'enablePastDayDeposit') === 'on'){
			$_bonus_rules['enablePastDayDeposit'] = true;
			$_bonus_rules['pastDayDepositDays'] = $this->utils->safeGetArray($form_input, 'pastDayDepositDays') ?: 0;
			$_bonus_rules['pastDayDepositAmount'] = $this->utils->safeGetArray($form_input, 'pastDayDepositAmount') ?: 0;
		} else {
			$_bonus_rules['enablePastDayDeposit'] = false;
		}

		if($this->utils->safeGetArray($form_input, 'enablePastDaysTotalDeposit') === 'on'){
			$_bonus_rules['enablePastDaysTotalDeposit'] = true;
			$_bonus_rules['pastDaysTotalDeposit'] = $this->utils->safeGetArray($form_input, 'pastDaysTotalDeposit') ?: 0;
			$_bonus_rules['pastDaysTotalDepositAmount'] = $this->utils->safeGetArray($form_input, 'pastDaysTotalDepositAmount') ?: 0;
		} else {
			$_bonus_rules['enablePastDaysTotalDeposit'] = false;
		}

		if(isset($form_input["a_affiliates"])){
			$_bonus_rules['allowedAffiliates'] = $form_input["a_affiliates"];
		}

		if(isset($form_input["a_players"])){
			$_bonus_rules['allowedPlayers'] = $form_input["a_players"];
		}

		if(isset($form_input["a_player_levels"])){
			$_bonus_rules['allowedPlayerLevels'] = $form_input["a_player_levels"];
		}

		$is_valid_forever = isset($form_input['isValidForever']) && $form_input['isValidForever'] == "on" ? true : false;

		$length = 12;
		$_redemption_code = trim($this->utils->safeGetArray($form_input, 'redemptionCode', ''));
		$redemption_code = !empty($_redemption_code) ? $_redemption_code : $this->getNewRedeemCode("", false);

		$new_category = array(
			'category_name' => trim($form_input['categoryName']),
			'withdrawal_rules' => json_encode($_withdrawal_rules),
			'bonus' => $_bonus_rules['bonus'],
			'created_by' => $this->authentication->getUsername(),
			'expires_at' => $is_valid_forever ? null : $form_input['hideDate'],
			'valid_forever' => $is_valid_forever,
			'status' => static_redemption_code_model::CATEGORY_STATUS_DEACTIVATE,
			'notes' => htmlentities($form_input['catenote']),
			'action_logs' => htmlentities($action_logs),
			'bonus_rules' => json_encode($_bonus_rules),
			'total_redeemable_count' => isset($quantity) ? $quantity : NULL,
			'redemption_code' => $redemption_code,
		);

		if ($this->static_redemption_code_model->checkCategoryNameExist($form_input['categoryName'])) {
			$result = [
				'success' => false,
				'noteType' => 'name',
				'errorMsg' => lang('Type exist.'),
			];
			return $this->returnCommon(BaseController::MESSAGE_TYPE_ERROR, $result['errorMsg'], $result, '/marketing_management/staticRedemptionCodeCategoryManager');
		}

		#check redemption code exist
		if ($this->checkRedemptionCodeExist($redemption_code)) {
			$result = [
				'success' => false,
				'noteType' => 'code',
				'errorMsg' => lang('Redemption code exist.'),
			];
			return $this->returnCommon(BaseController::MESSAGE_TYPE_ERROR, $result['errorMsg'], $result, '/marketing_management/staticRedemptionCodeCategoryManager');
		}

		$this->startTrans();
		$category_id = $this->static_redemption_code_model->insertCategory($new_category);
		$success_category = $this->endTransWithSucc() && $category_id;
		$this->utils->debug_log(__METHOD__, 'success_category', $success_category);

		if ($success_category) {
			$token = $this->generateStaticRedemptionCodeByQueue($category_id, $quantity);
			if ($token) {
				$result = [
					'success' => true,
					// 'data' => $redemptionCode_array,
					'successMsg' => lang('redemptionCode.generateSuccess'),
					'redriectGenerateProgress' => '/system_management/common_queue/' . $token,
					'redriectCodeReport' => "/marketing_management/staticRedemptionCodeList?codeStatus=1&codeType=$category_id"
				];
				return $this->returnCommon(BaseController::MESSAGE_TYPE_SUCCESS, $result['successMsg'], $result, '/marketing_management/staticRedemptionCodeCategoryManager');
			} else {
				$result = [
					'success' => false,
					// 'data' => $redemptionCode_array,
					'noteType' => 'job',
					'errorMsg' => lang('Create job failed'),
					'redriectCodeReport' => "/marketing_management/staticRedemptionCodeList?codeStatus=1&codeType=$category_id"
				];
				return $this->returnCommon(BaseController::MESSAGE_TYPE_ERROR, $result['errorMsg'], $result, '/marketing_management/staticRedemptionCodeCategoryManager');
			}
		}
		$result = [
			'success' => false,
			'errorMsg' => lang('redemptionCode.categoryAddedFail'),
		];
		return $this->returnCommon(BaseController::MESSAGE_TYPE_ERROR, $result['errorMsg'], $result, '/marketing_management/staticRedemptionCodeCategoryManager');
	}

	public function updateStaticRedemptionCodeCategory()
	{
		if (!$this->permissions->checkPermissions('manage_static_redemption_code_category')) {
			$result = [
				'success' => false,
				'errorMsg' => lang('role.nopermission')
			];
			return $this->returnCommon(BaseController::MESSAGE_TYPE_ERROR, $result['errorMsg'], $result, '/marketing_management/staticRedemptionCodeCategoryManager');
		}
		$result = [
			'success' => false
		];
		$form_input = $this->input->post();
		$operator = $this->authentication->getUsername();
		$timestamp = $this->utils->getNowForMysql();
		$categoryId = isset($form_input['categoryId']) ? $form_input['categoryId'] : null;
		$is_valid_forever = isset($form_input['isValidForever']) && $form_input['isValidForever'] == "on" ? true : false;
		if (!is_null($categoryId)) {
			$currentCategory = $this->static_redemption_code_model->getCategory($categoryId);
			// $currentActionLogs = $currentCategory['action_logs'];
			// $newActionLogs = $currentActionLogs . "</br>[$timestamp] edit by $operator |";
			$newCategoryName = trim($form_input['categoryName']);
			$_withdrawal_rules = $this->setWithdrawalRules($form_input);
			$_withdrawal_rules['bonusApplicationLimit'] = $this->setBonusApplicationLimit($form_input);
			$_bonus_rules = $this->setBonusReleaseRule($form_input);

			if($this->utils->safeGetArray($form_input, 'enableSameDayDeposit') === 'on'){
				$_bonus_rules['enableSameDayDeposit'] = true;
				$_bonus_rules['sameDayDepositAmount'] = $this->utils->safeGetArray($form_input, 'sameDayDepositAmount') ?: 0;
			} else {
				$_bonus_rules['enableSameDayDeposit'] = false;
			}
			if($this->utils->safeGetArray($form_input, 'enablePastDayDeposit') === 'on'){
				$_bonus_rules['enablePastDayDeposit'] = true;
				$_bonus_rules['pastDayDepositDays'] = $this->utils->safeGetArray($form_input, 'pastDayDepositDays') ?: 0;
				$_bonus_rules['pastDayDepositAmount'] = $this->utils->safeGetArray($form_input, 'pastDayDepositAmount') ?: 0;
			} else {
				$_bonus_rules['enablePastDayDeposit'] = false;
			}
			if($this->utils->safeGetArray($form_input, 'enablePastDaysTotalDeposit') === 'on'){
				$_bonus_rules['enablePastDaysTotalDeposit'] = true;
				$_bonus_rules['pastDaysTotalDeposit'] = $this->utils->safeGetArray($form_input, 'pastDaysTotalDeposit') ?: 0;
				$_bonus_rules['pastDaysTotalDepositAmount'] = $this->utils->safeGetArray($form_input, 'pastDaysTotalDepositAmount') ?: 0;
			} else {
				$_bonus_rules['enablePastDaysTotalDeposit'] = false;
			}

			if(isset($form_input["e_affiliates"])){
				$_bonus_rules['allowedAffiliates'] = $form_input["e_affiliates"];
			}
			
			if(isset($form_input["e_players"])){
				$_bonus_rules['allowedPlayers'] = $form_input["e_players"];
			}

			if(isset($form_input["e_player_levels"])){
				$_bonus_rules['allowedPlayerLevels'] = $form_input["e_player_levels"];
			}

			// $this->startTrans();
			$edit_category = array(
				'category_name' => $newCategoryName,
				'withdrawal_rules' => json_encode($_withdrawal_rules),
				'bonus' => $_bonus_rules['bonus'],
				'updated_by' => $operator,
				'expires_at' => $is_valid_forever ? null : $form_input['hideDate'],
				'valid_forever' => $is_valid_forever,
				'notes' => htmlentities($form_input['catenote']),
				'bonus_rules' => json_encode($_bonus_rules),
				'total_redeemable_count' => isset($form_input['totalRedeemable']) ? $form_input['totalRedeemable'] : NULL
				// 'action_logs' => $newActionLogs
			);

			if (trim($currentCategory['category_name']) != $newCategoryName && $this->static_redemption_code_model->checkCategoryNameExist($newCategoryName)) {
				$result = [
					'success' => false,
					'noteType' => 'name',
					'errorMsg' => lang('Type exist.'),
				];
				return $this->returnCommon(BaseController::MESSAGE_TYPE_ERROR, $result['errorMsg'], $result, '/marketing_management/staticRedemptionCodeCategoryManager');
			}
			$redemptionCode = trim($currentCategory['redemption_code']);
			$newRedemptionCode = trim($form_input['redemptionCode']);

			if(empty($newRedemptionCode)) {
				$newRedemptionCode = $redemptionCode;
			} else if ( $redemptionCode != $newRedemptionCode && $this->checkRedemptionCodeExist($newRedemptionCode)) {
				$result = [
					'success' => false,
					'noteType' => 'code',
					'errorMsg' => lang('Code exist.'),
				];
				return $this->returnCommon(BaseController::MESSAGE_TYPE_ERROR, $result['errorMsg'], $result, '/marketing_management/staticRedemptionCodeCategoryManager');
			} else {
				$edit_category['redemption_code'] = $newRedemptionCode;
			}

			$controller = $this;
			$success_category = $this->lockAndTransForStaticRedemptionCode($newRedemptionCode, function () use ($controller, $categoryId, $edit_category, &$new_category) {
				$category_update_succ = false;
				$delete_unusing = $controller->realDeleteUnusingStaticCodeByCateId($categoryId);
				if ($delete_unusing) {
					$category_update_succ = $controller->static_redemption_code_model->updateCategory($categoryId, $edit_category);
					$new_category = $controller->static_redemption_code_model->getCategory($categoryId);
				}
				return $category_update_succ;
			});

			$this->utils->debug_log(__METHOD__, 'success_category', $success_category);

			if ($success_category) {
				$token = $this->generateStaticRedemptionCodeByQueue($categoryId, $new_category['total_redeemable_count']);
				if ($token) {
					$result = [
						'success' => true,
						// 'data' => $redemptionCode_array,
						'successMsg' => lang('redemptionCode.generateSuccess'),
						'redriectGenerateProgress' => '/system_management/common_queue/' . $token,
						'redriectCodeReport' => "/marketing_management/staticRedemptionCodeList?codeStatus=1&codeType=$categoryId"
					];
					return $this->returnCommon(BaseController::MESSAGE_TYPE_SUCCESS, $result['successMsg'], $result, '/marketing_management/staticRedemptionCodeCategoryManager');
				} else {
					$result = [
						'success' => false,
						// 'data' => $redemptionCode_array,
						'noteType' => 'job',
						'errorMsg' => lang('Create job failed'),
						'redriectCodeReport' => "/marketing_management/staticRedemptionCodeList?codeStatus=1&codeType=$categoryId"
					];
					return $this->returnCommon(BaseController::MESSAGE_TYPE_ERROR, $result['errorMsg'], $result, '/marketing_management/staticRedemptionCodeCategoryManager');
				}
			}
		}
		$result = [
			'success' => false,
			'errorMsg' => lang('redemptionCode.categoryAddedFail'),
		];
		return $this->returnCommon(BaseController::MESSAGE_TYPE_ERROR, $result['errorMsg'], $result, '/marketing_management/staticRedemptionCodeCategoryManager');
	}

	public function updateStaticRedemptionCodeCategoryStatus()
	{
		if (!$this->permissions->checkPermissions('manage_static_redemption_code_category')) {
			$result = [
				'success' => false,
				'errorMsg' => lang('role.nopermission')
			];
			$this->returnJsonResult($result);
			return;
		}
		$success = false;
		$categoryId = $this->input->post('category_id');
		$currentCategoryStatus = $this->static_redemption_code_model->getCategory($categoryId, 'status');
		$operator = $this->authentication->getUsername();
		$newStatus = ($currentCategoryStatus == static_redemption_code_model::CATEGORY_STATUS_ACTIVATED) ? static_redemption_code_model::CATEGORY_STATUS_DEACTIVATE : static_redemption_code_model::CATEGORY_STATUS_ACTIVATED;

		// $currentActionLogs = $this->static_redemption_code_model->getCategory($categoryId, 'action_logs');
		// $staus_map = [
		// 	static_redemption_code_model::CATEGORY_STATUS_ACTIVATED => lang('redemptionCode.categoryActive'),
		// 	static_redemption_code_model::CATEGORY_STATUS_DEACTIVATE => lang('redemptionCode.categoryDeactive'),
		// ];
		// $timestamp = $this->utils->getNowForMysql();
		// $newActionLogs = $currentActionLogs . "</br>[$timestamp] $operator update Status to {$staus_map[$newStatus]}|";
		$update_arr = [
			'status' => $newStatus,
			'updated_by' => $operator,
			// 'action_logs' => $newActionLogs
		];

		$result = $this->static_redemption_code_model->updateCategory($categoryId, $update_arr);
		$success = $result;
		$return = [
			'success' => $success,
			'data' => $update_arr
		];
		$this->returnJsonResult($return);
	}

	// edit , get Category Detail
	public function getStaticRedemptionCodeCategoryDetailByCategoryId()
	{
		$data = [];
		$return = [
			'success' => false
		];
		$categoryId = $this->input->post('category_id');
		if (!empty($categoryId)) {
			$currentCategory = $this->static_redemption_code_model->getCategory($categoryId);
			// $currentCategory = isset($_currentCategory[0]) ? $_currentCategory[0] : null;
			if (!is_null($currentCategory)) {
				$withdrawal_rules = isset($currentCategory['withdrawal_rules']) ? json_decode($currentCategory['withdrawal_rules'], true) : null;
				$application_limit_setting = isset($withdrawal_rules['bonusApplicationLimit']) ? $withdrawal_rules['bonusApplicationLimit'] : null;
				unset($withdrawal_rules['bonusApplicationLimit']);
				$bonus_rules = isset($currentCategory['bonus_rules']) ? json_decode($currentCategory['bonus_rules'], true) : null;
				if(isset($bonus_rules['allowedPlayers'])){

					$allowedPlayers = $bonus_rules['allowedPlayers'];
					$currentCategory['allowedPlayers'] = $this->static_redemption_code_model->getAllowedPlayers($allowedPlayers);
				}
				if(isset($bonus_rules['allowedAffiliates'])){
					$allowedAffiliates = $bonus_rules['allowedAffiliates'];
					$currentCategory['allowedAffiliates'] = $this->static_redemption_code_model->getAllowedAffiliates($allowedAffiliates);
				}
				if(isset($bonus_rules['allowedPlayerLevels'])){
					$allowedPlayerLevels = $bonus_rules['allowedPlayerLevels'];
					$this->utils->debug_log(__METHOD__, 'allowedPlayerLevels', $allowedPlayerLevels);
					$currentCategory['allowedPlayerLevels'] = $this->static_redemption_code_model->getAllowedPlayerLevels($allowedPlayerLevels);
				}

				$currentCategory['withdrawal_rules'] = $withdrawal_rules;
				$currentCategory['application_limit_setting'] = $application_limit_setting;
				$currentCategory['bonus_rules'] = $bonus_rules;
				$data['currentCategory'] = $currentCategory;
				$return = [
					'success' => true,
					'result' => $data
				];
			}
		}
		$this->returnJsonResult($return);
	}

	private function setBonusReleaseRule($form_input)
	{
		$_bonusReleaseRule = [
			'bonusReleaseTypeOption' => Promorules::BONUS_RELEASE_RULE_FIXED_AMOUNT,
			'nonfixedBonusMinAmount' => null,
			'nonfixedBonusMaxAmount' => null,
			'bonusCap' => 0,
			'bonus' => 0,
		];

		foreach ($_bonusReleaseRule as $key => $values) {
			if (isset($form_input[$key])) {
				$_bonusReleaseRule[$key] = $form_input[$key];
			}
		}
		return $_bonusReleaseRule;
	}

	public function generateStaticRedemptionCodeByQueue($categoryId, $quantity)
	{
		$result = [
			'success' => false
		];

		$success = true;
		$this->load->library(['lib_queue', 'language_function', 'authentication']);
		$this->load->model(['queue_result']);
		$caller = $this->authentication->getUserId();
		$operator = $this->authentication->getUsername();
		$state = null;
		$lang = $this->language_function->getCurrentLanguage();
		$callerType = Queue_result::CALLER_TYPE_ADMIN;
		$params = [
			'categoryId' => $categoryId,
			'total_redeemable_count' => $quantity,
			'operator' => $operator,
		];

		$token = $this->lib_queue->addStaticRemoteGenerateRedemptionCode($params, $callerType, $caller, $state);

		return $token;
	}

	public function generate_static_redemption_code_job($token)
	{
		$this->load->library(array('authentication'));
		$this->load->model(array('static_redemption_code_model'));
        $static_settings = $this->utils->getConfig('static_redemption_code_setting');
        if( empty($static_settings['decimals']) ){
            $static_settings['decimals'] = 0;
        }

		$codeDenominationRules = $this->utils->safeGetArray($static_settings, 'enable_new_redemption_code_denomination_rules', []);

		$queue_result_model = $this->queue_result;
		$data = $this->initJobData($token);
		$this->utils->debug_log('running generate_redemption_code_queue', $data);
		$count_success = 0;
		$count_failed = 0;

		$params = [];
		if (isset($data['params']) && !empty($data['params'])) {
			$params = $data['params'];
		}

		$categoryId = isset($params['categoryId']) ? $params['categoryId'] : null;
		$quantity = isset($params['total_redeemable_count']) ? $params['total_redeemable_count'] : null;
		$operator = isset($params['operator']) ? $params['operator'] : null;

		$rlt['categoryId'] = $categoryId;
		$rlt['total_redeemable_count'] = $quantity;
		$rlt['params'] = $params;
		$rlt['message'] = 'Processing';

		if (empty($categoryId) || empty($quantity)) {
			$rlt['success'] = false;
			$rlt['done'] = true;
			$rlt['count_success'] = $count_success;
			$rlt['count_failed'] = $count_failed;
			$rlt['progress'] = 0;
			$rlt['process_status'] = 0;
			$rlt['message'] = 'Invalid parameters! ';
			$this->utils->error_log('running generate_redemption_code_queue error', 'rlt', $rlt);
			$queue_result_model->updateResultWithCustomStatus($token, $rlt, true, true);
			return false;
		}

		//update running
		$totalCount = 0;
		$rlt['success'] = true;
		$rlt['done'] = false;
		$rlt['count_success'] = $count_success;
		$rlt['count_failed'] = $count_failed;
		$rlt['progress'] = $count_success;
		$rlt['process_status'] = 0;
		$queue_result_model->updateResultRunning($token, $rlt);

		//sleep 2secs before update
		sleep(1);
		$currentCategory = $this->static_redemption_code_model->getCategory($categoryId);
		$this->utils->info_log("============generateRedemptionCode============ currentCategory ", ['currentCategory' => $currentCategory]);

		$timestamp = $this->utils->getNowForMysql();
		$action_logs = "|[$timestamp]generate by $operator|";
		// run generate
		$redemptionCode_array = [];
		$redemptionCode_item = [
			'category_id' => $categoryId,
			'redemption_code' => $currentCategory['redemption_code'],
			'current_withdrawal_rules' => $currentCategory['withdrawal_rules'],
			'current_bonus' => 0,
			// 'current_bonus_rules' => $currentCategory['bonus_rules'],
			'created_by' => $operator,
			'status' =>  static_redemption_code_model::ITEM_STATUS_ACTIVATED,
			'action_logs' => $action_logs
		];
		$last_quantity = $currentCategory['total_redeemable_count'] ?: 0;
		$category_name = $currentCategory['category_name'];
		$redemption_code = $currentCategory['redemption_code'];
		$bonus_rules = json_decode($currentCategory['bonus_rules'], true);
		$bonusReleaseTypeOption = (int)$bonus_rules['bonusReleaseTypeOption'];
		$bonus = (int)$bonus_rules['bonus'];
		$min = (float)$bonus_rules['nonfixedBonusMinAmount'];
		$max = (float)$bonus_rules['nonfixedBonusMaxAmount'];
		$cap = (int)$bonus_rules['bonusCap'];

		$this->load->model(array('static_redemption_code_model'));
		// $this->startTrans();

		$amount_count = 0;
		$amount = 0;
		$insertId = null;
		$affRows = 0;
		$controller = $this;
		$insertSuccess = false;

		if(!empty($codeDenominationRules)){
			$redemptionCode_array = $this->generate_redemption_codes($quantity, $min, $max, $cap, $bonus, $bonusReleaseTypeOption, $codeDenominationRules, $redemptionCode_item, $static_settings['decimals']);

			$amount_count = array_sum(array_column($redemptionCode_array, 'current_bonus'));
		}else{
			for ($i = 0; $i < $quantity; $i++) {
				switch ($bonusReleaseTypeOption) {
					case Promorules::BONUS_RELEASE_RULE_FIXED_AMOUNT:
						$amount = $bonus;
						break;
					case Promorules::BONUS_RELEASE_RULE_CUSTOM:
						if ($amount_count >= $cap) {
							break 2;
						}
						$amount = $this->generateRandomAmount($min, $max, $static_settings['decimals']);

						if ($amount_count + $amount > $cap) {
							$amount = $cap - $amount_count;
						}
						break;
				}

				if ($amount <= 0) {
					break;
				}

				$amount_count += $amount;

				$redemptionCode_item['current_bonus'] = $amount;
				$redemptionCode_array[] = $redemptionCode_item;

				// $this->utils->info_log("============map count i============", ['i' => $i,'redemptionCode_item' => $redemptionCode_item,'redemptionCode_array' => $redemptionCode_array]);
			}
		}

		$this->utils->info_log("============map redemptionCode_array============", ['quantity' => $quantity,'redemptionCode_array' => $redemptionCode_array,'amount' => $amount,'amount_count' => $amount_count, 'redemptionCode_item' => $redemptionCode_item]);

		$insertSuccess = $this->lockAndTransForStaticRedemptionCode($redemption_code, function () use ($controller, $redemptionCode_array, &$insertId, &$affRows) {
			$insertId = $controller->static_redemption_code_model->batchInsertItem($redemptionCode_array);
			$affRows = $this->db->affected_rows();
			$this->utils->printLastSQL();
			return $insertId;
		});
		$this->utils->info_log("============generateRedemptionCode============", ['insertSuccess' => $insertSuccess, 'count_failed' => $count_failed, 'affRows' => $affRows]);

		if ($insertSuccess && !empty($insertId)) {
			// $redemptionCode_array[] = $redemptionCode_item;
			$this->utils->info_log("============generateRedemptionCode============ insertid : [$insertId]", $redemptionCode_item);
			$count_success = $affRows;
			$rlt['success'] = true;
			$rlt['done'] = false;
			$rlt['count_success'] = $count_success;
			$rlt['count_failed'] = $count_failed;
			$rlt['progress'] = $count_success;
			$rlt['process_status'] = 0;
			$queue_result_model->updateResultRunning($token, $rlt);
		}

		$success = $insertSuccess && !empty($insertId);
		$this->utils->info_log("============generateRedemptionCode============final", ['success' => $success]);

		if (!$success) {
			$rlt['success'] = true;
			$rlt['done'] = true;
			$rlt['count_success'] = $count_success;
			$rlt['count_failed'] = $count_failed;
			$rlt['process_status'] = 3;
			$rlt['params'] = $params;
			$rlt['message'] = 'Unknown error.';
			$this->utils->error_log('running generate_static_redemption_code_job error', 'rlt', $rlt);
			$queue_result_model->updateResultWithCustomStatus($token, $rlt, true, true);
			return false;
		}

		$new_quantity = $currentItemsCount = $this->static_redemption_code_model->countCodeUnderCategory($categoryId);

		$rlt['success'] = true;
		$rlt['done'] = true;
		$rlt['count_success'] = $count_success;
		$rlt['count_failed'] = $count_failed;
		$rlt['currentItemsCount'] = $currentItemsCount;
		$rlt['process_status'] = 0;
		$rlt['params'] = $params;
		$rlt['message'] = 'Completed.';
		$this->utils->debug_log('running generate_static_redemption_code_job success', 'rlt', $rlt);
		$queue_result_model->updateResultWithCustomStatus($token, $rlt, true);

		$result = [
			'success' => true,
			'successMsg' => lang('redemptionCode.generateSuccess'),
			'count_success' => $count_success,
			'count_failed' => $count_failed,
			// 'redriectCodeReport' => "/marketing_management/staticRedemptionCodeList?codeType=$categoryId"
		];
		$queue_result_model->updateFinalResult($token, $rlt['success'], $rlt['message'], $rlt['progress'], ((int)$count_success + (int)$count_failed), $rlt['done']);
		$this->utils->debug_log(__METHOD__ . 'result', $result);

		// $this->static_redemption_code_model->updateCategory($categoryId, [
		// 	'total_redeemable_count' => $new_quantity
		// ]);
		return true;
	}

	public function generate_redemption_codes($quantity, $min_value, $max_value, $cap, $bonus, $bonusReleaseTypeOption, $codeDenominationRules, $redemptionCodeItem, $decimals = 0) {

		$this->utils->debug_log(__METHOD__, 'quantity', $quantity, 'min_value', $min_value, 'max_value', $max_value, 'cap', $cap, 'bonusReleaseTypeOption', $bonusReleaseTypeOption, 'codeDenominationRules', $codeDenominationRules, 'decimals', $decimals, 'redemptionCodeItem', $redemptionCodeItem);
		$min_max_threshold = $this->utils->safeGetArray($codeDenominationRules, 'min_max_threshold', 4);
		$small_quantity_percentage = $this->utils->safeGetArray($codeDenominationRules, 'small_quantity_percentage', 0.8);
		$medium_quantity_percentage = $this->utils->safeGetArray($codeDenominationRules, 'medium_quantity_percentage', 0.15);
		$scope = $this->utils->safeGetArray($codeDenominationRules, 'scope', 1);

		// 檢查是否需要生成固定面額的兌換碼
		if ($bonusReleaseTypeOption == Promorules::BONUS_RELEASE_RULE_FIXED_AMOUNT) {
			return $this->generate_fixed_value_codes($quantity, $bonus, $cap, $redemptionCodeItem);
		}

		// 如果 $max - $min 的範圍小於 $min_max_threshold，直接依據 $min ~ $max 生成兌換碼
		if ($bonusReleaseTypeOption == Promorules::BONUS_RELEASE_RULE_CUSTOM && ($max_value - $min_value) < $min_max_threshold) {
			return $this->generate_codes_within_range($quantity, $min_value, $max_value, $cap, $redemptionCodeItem, $decimals);
		}

		// 計算小、中、大面額的數量
		$small_quantity = floor($quantity * $small_quantity_percentage);
		$medium_quantity = floor($quantity * $medium_quantity_percentage);
		$large_quantity = $quantity - $small_quantity - $medium_quantity;

		// 初始化結果陣列
		$redemption_codes = array();

		// 初始化剩餘額度
		$remaining_cap = $cap;

		$this->generate_codes($redemption_codes, $remaining_cap, $min_value, $min_value + $scope, $small_quantity, $redemptionCodeItem, $decimals);
		$this->generate_codes($redemption_codes, $remaining_cap, $min_value + $scope, $max_value -$scope, $medium_quantity, $redemptionCodeItem, $decimals);
		$this->generate_codes($redemption_codes, $remaining_cap, $max_value - $scope, $max_value, $large_quantity, $redemptionCodeItem, $decimals);

		return $redemption_codes;
	}

	// 生成指定範圍內的兌換碼
	public function generate_codes_within_range($quantity, $min_value, $max_value, $cap, $redemptionCode_item, $decimals = 0) {
		$redemption_codes = array();
		$remaining_cap = $cap;

		for ($i = 0; $i < $quantity; $i++) {
			if ($remaining_cap < $min_value) {
				break;
			}
			$value = $this->randFloat($min_value, $max_value, $decimals);
			$value = min($value, $remaining_cap);

			if ($value <= 0) {
				break;
			}

			$redemptionCode_item['current_bonus'] = $value;
			$redemption_codes[] = $redemptionCode_item;

			$remaining_cap -= $value;
		}

		$this->utils->debug_log(__METHOD__, 'redemption_codes', $redemption_codes, 'remaining_cap', $remaining_cap);
		return $redemption_codes;
	}

	// 生成固定面額的兌換碼
	public function generate_fixed_value_codes($quantity, $value, $cap, $redemptionCode_item) {
		$redemption_codes = array();

		for ($i = 0; $i < $quantity; $i++) {
			if ($value <= 0) {
				break;
			}

			$redemptionCode_item['current_bonus'] = $value;
			$redemption_codes[] = $redemptionCode_item;
		}

		$this->utils->debug_log(__METHOD__, 'redemption_codes', $redemption_codes);
		return $redemption_codes;
	}

	// 生成兌換碼
	public function generate_codes(&$redemption_codes, &$remaining_cap, $min_value, $max_value, $quantity, $redemptionCode_item, $decimals = 0) {

		$this->utils->debug_log(__METHOD__, '---------------redemption_codes', $redemption_codes, 'remaining_cap', $remaining_cap, 'redemptionCode_item', $redemptionCode_item);
		for ($i = 0; $i < $quantity; $i++) {
			// 如果剩餘額度不足以生成下一個兌換碼，則退出迴圈
			if ($remaining_cap < $min_value) {
				break;
			}

			$value = $this->randFloat($min_value, $max_value, $decimals);
			$value = min($value, $remaining_cap);

			if ($value <= 0) {
				break;
			}

			$redemptionCode_item['current_bonus'] = $value;
			$redemption_codes[] = $redemptionCode_item;
			$remaining_cap -= $value;
		}
		$this->utils->debug_log(__METHOD__, 'redemption_codes', $redemption_codes, 'remaining_cap', $remaining_cap);
	}

	public function generateRandomAmount($min_amount, $max_amount, $decimals) {
        return $this->randFloat($min_amount, $max_amount, $decimals);
    }

    public function randFloat($min = 0.5, $max = 1.5, $decimals = 2){
        $randFloat = ($min + ($max - $min) * (mt_rand() / mt_getrandmax()));
        return round($randFloat, $decimals);
    }

    public function decimals2floatStep($decimals = 2){
        $baseOnFloat = 1;
        for($i = 0 ; $i < $decimals; $i++){
            $baseOnFloat = $baseOnFloat / 10;
        }
        return number_format($baseOnFloat, $decimals);
    }

	public function generateStaticCodeWithInternalMessage()
	{
		$result = [
			'success' => false,
			'errorMsg' => lang('role.nopermission')
		];
		if (!$this->permissions->checkPermissions('manage_static_redemption_code_category')) {
			$result['errorMsg'] = lang('role.nopermission');
			return $this->returnCommon(BaseController::MESSAGE_TYPE_ERROR, lang('role.nopermission'), $result, '/marketing_management/staticRedemptionCodeCategoryManager');
		}

		$uploadFieldName = 'generate_code_with_message_csv_file';
		$filepath = '';
		$msg = '';
		if ($this->existsUploadField($uploadFieldName)) {
			//check file type
			if ($this->saveUploadFileToRemote($uploadFieldName, ['csv'], $filepath, $msg)) {
				//get $filepath
				//echo 'uploaded';
			} else {
				$result['errorMsg'] = $message = lang('Upload csv file failed') . ', ' . $msg;
				return $this->returnCommon(BaseController::MESSAGE_TYPE_ERROR, $message, $result, '/marketing_management/staticRedemptionCodeCategoryManager');
			}
		}
		$categoryId = $this->input->post('categoryId');
		$subject = $this->input->post('batch_mail_subject');
		$messageBody = $this->input->post('batch_mail_message_body');
		$messageContent = $this->input->post('summernoteDetails');


		$this->load->library(['lib_queue']);
		$callerType = Queue_result::CALLER_TYPE_ADMIN;
		$caller = $this->authentication->getUserId();
		$state = null;
		$lang = $this->language_function->getCurrentLanguage();
		$file = empty($filepath) ? null : basename($filepath);

		// $this->returnCommon(BaseController::MESSAGE_TYPE_SUCCESS, $result['successMsg'], $result, '/marketing_management/staticRedemptionCodeCategoryManager');

		//save csv file
		if (!empty($file)) {
			$adminUsername = $this->authentication->getUsername();
			$params = [
				"file" => $file,
				"adminUserId" => $caller,
				"adminUsername" => $adminUsername,
				"categoryId" => $categoryId,
				"subject"=>$subject,
				"messageBody" => $messageBody,
				"messageContent" => $messageContent
			];

			$token = $this->lib_queue->addStaticRemoteGenerateRedemptionCodeByMessage($params, $callerType, $caller, $state, $lang);

			if (!empty($token)) {
				// $this->alertMessage(BaseController::MESSAGE_TYPE_SUCCESS, lang('Create importing job successfully'));
				// return redirect('marketing_management/post_manually_batch_add_cashback_bonus_result/'.$token);
				// $this->returnCommon(BaseController::MESSAGE_TYPE_SUCCESS, lang('Create job successfully'), $result, '/system_management/common_queue/' . $token);
				$result = [
					'success' => true,
					'successMsg' => lang('redemptionCode.generateSuccess'),
					'redriectCodeReport' => "/marketing_management/staticRedemptionCodeList?codeStatus=".static_redemption_code_model::CODE_STATUS_UNUSED."&codeType=$categoryId"
				];
				return $this->returnCommon(BaseController::MESSAGE_TYPE_SUCCESS, lang('Create job successfully'), $result, '/marketing_management/generate_redemption_code_with_internal_message_job_result/' . $token);
				// marketing_management/redemptioncode/generate_redemption_code_with_internal_message_job_result.php
			} else {
				$result = [
					'success' => false,
					'successMsg' => lang('Create job failed'),
					'errorMsg' => lang('Create job failed'),
					'redriectCodeReport' => "/marketing_management/staticRedemptionCodeCategoryManager"
				];
				return $this->returnCommon(BaseController::MESSAGE_TYPE_ERROR, lang('Create job failed'), $result, '/marketing_management/staticRedemptionCodeCategoryManager');
			}
		} else {
			$result = [
				'success' => false,
				'successMsg' => lang('Upload csv file failed'),
				'errorMsg' => lang('Upload csv file failed'),
				'redriectCodeReport' => "/marketing_management/staticRedemptionCodeCategoryManager"
			];
			return $this->returnCommon(BaseController::MESSAGE_TYPE_ERROR, lang('Upload csv file failed'), $result, '/marketing_management/staticRedemptionCodeCategoryManager');
		}
	}
	// public function generate_redemption_code_with_internal_message_job_result($token){
	// 	$data['result_token']=$token;
	// 	$this->loadTemplate(lang('Marketing Management'), '', '', 'marketing');
	// 	$this->template->write_view('sidebar', 'marketing_management/sidebar');
	// 	$this->template->write_view('main_content', 'marketing_management/redemptioncode/generate_redemption_code_with_internal_message_job_result', $data);
	// 	$this->template->render();
	// }

	public function generate_static_redemption_code_with_internal_message_job($token)
	{
		$this->load->model(['queue_result', 'player_model', 'static_redemption_code_model']);

		$queue_result_model = $this->queue_result;
		$player_model = $this->player_model;
		$controller = $this;

		$data = $controller->initJobData($token);
		$params = $data['params'];
		$uploadCsvFilepath = $controller->utils->getSharingUploadPath('/upload_temp_csv');
		$csv_file = rtrim($uploadCsvFilepath, '/') . '/' . $params['file'];

		$fp = file($csv_file); // this one works
		$totalCount =  count($fp);

		if (!file_exists($csv_file)) {
			$rlt = ['success' => false, 'failCount' => 0, 'errorDetail' => 'CSV file is not exist', 'failedList' => 0,  'successCount' => 0,  'processedRows' => 0, 'totalCount' => $totalCount, 'progress' => 0];
			$queue_result_model->failedResult($token, $rlt);
			return $controller->utils->error_log("File not exist!");
		}

		$message_log = '';
		$failed_log_filepath = '';
		$csv_logs_header = ['username', 'reason'];
		$funcName = __FUNCTION__;
		$controller->utils->_appendSaveDetailedResultToRemoteLog($token, $funcName . '_failed_results', $message_log, $failed_log_filepath, true, $csv_logs_header);

		// start process
		$state = array('processId' => getmypid());
		$rlt = ['success' => false, 'failCount' => 0, 'failed_log_filepath' => site_url() . 'remote_logs/' . basename($failed_log_filepath),   'successCount' => 0,  'processedRows' => 0, 'totalCount' => $totalCount, 'progress' => 0];
		$queue_result_model->updateResultRunning($token, [], $state);

		$max_error_to_stop = $controller->utils->getConfig('remote_batch_add_cashback_bonus_max_error_stop'); //100

		$count_loop = 0;
		$failCount = 0;
		$successCount = 0;
		$percentage_steps = [];

		for ($i = .1; $i <= 10; $i += .1) {
			array_push($percentage_steps, ceil($i / 10 * $totalCount));
		};

		$this->load->model(array('internal_message'));
		$this->load->library(array('player_message_library'));
		$msgSenderName = $this->player_message_library->getDefaultAdminSenderName();

		$adminUserId = $params['adminUserId'];
		$adminUsername = $params['adminUsername'];
		$subject = $params['subject'];
		$messageBody = $params['messageBody'];
		$messageContent = urldecode(base64_decode($params['messageContent']));
		$messageBody = $messageContent?:$messageBody;

		$categoryId = $params['categoryId'];
		$currentCategory = $this->static_redemption_code_model->getCategory($categoryId);
		$timestamp = $this->utils->getNowForMysql();
		// $action_logs = "|[$timestamp]generate by $adminUsername|";
		$redemption_code = $currentCategory['redemption_code'];
		// run generate
		// $redemptionCode_array = [];
		// $redemptionCode_item = [
		// 	'category_id' => $categoryId,
		// 	'redemption_code' => '',
		// 	'current_withdrawal_rules' => $currentCategory['withdrawal_rules'],
		// 	'current_bonus' => $currentCategory['bonus'],
		// 	'created_by' => $adminUsername,
		// 	'status' =>  static_redemption_code_model::ITEM_STATUS_ACTIVATED,
		// 	'action_logs' => $action_logs,
		// 	'notes' => '',
		// ];
		// $last_quantity = $currentCategory['quantity'] ?: 0;
		// $category_name = $currentCategory['category_name'];

		$ignore_first_row = false;
		$cnt = 0;
		$message = '';
		$controller->utils->loopCSV(
			$csv_file,
			$ignore_first_row,
			$cnt,
			$message,
			function ($cnt, $csv_row, $stop_flag)
			use (
				$controller,
				$queue_result_model,
				$player_model,
				$token,
				$state,
				$percentage_steps,
				&$count_loop,
				&$failCount,
				&$successCount,
				&$totalCount,
				$adminUserId,
				$adminUsername,
				$max_error_to_stop,
				$funcName,
				$failed_log_filepath,
				$subject,
				$messageBody,
				$redemption_code,
				// $redemptionCode_item,
				$msgSenderName
			) {
				// if(count($csv_row) == 0){
				//     $totalCount--;
				// }
				print_r($csv_row);
				print_r($cnt);               // $count_loop++;
				$row['username'] = $csv_row[0];
				$success = false;
				$username = trim($row['username']);
				$player_id = $player_model->getPlayerIdByUsername($username);
				if (empty($player_id)) {
					$failCount++;
					$controller->utils->error_log("PLAYER NOT EXIST", $row);
					$message_log = ['username' => isset($row['username']) ? $row['username'] : '', 'reason' => 'Player not exist'];
					$controller->utils->_appendSaveDetailedResultToRemoteLog($token, $funcName . '_failed_results', $message_log, $failed_log_filepath, true, []);
				} else {

					// $lockedKey = null;
					// $lock_it = $controller->lockPlayerBalanceResource($player_id, $lockedKey);
					$controller->utils->info_log("csv_row", $csv_row, 'count_loop', $count_loop, 'totalCount', $totalCount);

					try {
						// if ($lock_it) {

							$controller->startTrans();
							$timestamp = $this->utils->getNowForMysql();
							// $redemptionCode_item['notes'] = "[$timestamp]:send to $username|";
							// list($insertSuccess, $insertId, $redemption_code) = $controller->generateCode($redemptionCode_item);
							if(!empty($redemption_code)) {

								$messageBody = str_replace('[username]', $username, $messageBody);
								$messageBody = str_replace('[code]', $redemption_code, $messageBody);

								$is_system_message = TRUE;
								$message_id = $controller->internal_message->addNewMessageAdmin(
									$adminUserId,
									$player_id,
									$msgSenderName,
									$subject,
									$messageBody,
									$is_system_message
								);
								if(empty($message_id)){
									$this->rollbackTrans();

								}
							} else {
								$this->rollbackTrans();
							}

							$success = $controller->endTransWithSucc();

							if (empty($message_id) || !$success) {
								$failCount++;
								$message_log = ['username' => $row['username'], 'reason' => 'fail to send message'];
								$controller->utils->_appendSaveDetailedResultToRemoteLog($token, $funcName . '_failed_results', $message_log, $failed_log_filepath, true, []);
							} else {
								$successCount++;
							}
						// } //lockit end
					} catch(Exception $e){
						$this->utils->info_log(__METHOD__ . ' error send internal message', $e->getMessage());
					}
					// finally {
					// 	$controller->releasePlayerBalanceResource($player_id, $lockedKey);
					// }
				} //end check empty playerId
				$count_loop++;
				//update front end progress
				$rlt = ['success' => false, 'failCount' => $failCount, 'failed_log_filepath' => site_url() . 'remote_logs/' . basename($failed_log_filepath), 'successCount' => $successCount,  'processedRows' => $count_loop, 'totalCount' => $totalCount, 'progress' => ceil($count_loop / $totalCount * 100)];
				$queue_result_model->updateResultRunning($token, $rlt, $state);

				if ($count_loop == $totalCount) {
					$controller->utils->info_log('count_loop == totalCount', $count_loop == $totalCount);
					//update last - Done
					$rlt = ['success' => true, 'failCount' => $failCount, 'failed_log_filepath' => site_url() . 'remote_logs/' . basename($failed_log_filepath), 'successCount' => $successCount,  'processedRows' => $count_loop, 'totalCount' => $totalCount, 'progress' => 100];
					$queue_result_model->updateResult($token, $rlt);
				}
			}
		); //loop csv;
		$successCount = $totalCount - $failCount;
		// $new_quantity = $this->static_redemption_code_model->countCodeUnderCategory($categoryId);
		// $this->static_redemption_code_model->updateCategory($categoryId, [
		// 	'quantity' => $new_quantity
		// ]);
		$controller->utils->debug_log("generate_static_redemption_code_with_internal_message_job, [$successCount] out of [$totalCount] succeed.  failed_log_filepath: " . $failed_log_filepath);
	}

	private function createStaticRedemptionCode($length, $category_name = null)
	{
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$string = '';

		for ($i = 0; $i < $length; $i++) {
			$string .= $characters[mt_rand(0, strlen($characters) - 1)];
		}

		return $string;
	}

		/**
	 * Get a redeem code for new.
	 *
	 * @param string $redeemCode The promo code, if empty than generated by server.
	 * @return mixed json format,
	 * - status string always be "ok".
	 * - newPromoCode string The promo code for new.
	 */
	public function getNewRedeemCode($redeemCode = '', $is_ajax = true){
		$length = 12;
		if( empty($redeemCode) ){
			$redeemCode = $this->createStaticRedemptionCode($length);
		}

		while($this->checkRedemptionCodeExist($redeemCode)){

			$redeemCode = $this->createStaticRedemptionCode($length);
        }
		if($is_ajax){

			return $this->returnJsonResult(array('status' => 'ok', 'newCode' => $redeemCode));
		}

		return $redeemCode;
	}// EOF getNewPromoCode

	public function checkRedemptionCodeExist($code, $type = null){
        $exist = false;
        $this->load->model(array('static_redemption_code_model'));
		switch ($type) {
			case 'category':
				$exist = $this->static_redemption_code_model->checkCategoryRedemptionCodeExist($code);
				break;

			case 'item':
				$exist = $this->static_redemption_code_model->checkRedemptionCodeExist($code);
				break;

			default:
				$exist_item = $this->static_redemption_code_model->checkRedemptionCodeExist($code);
				$exist_cate = $this->static_redemption_code_model->checkCategoryRedemptionCodeExist($code);
				$exist = $exist_item || $exist_cate;
				break;
		}
		return $exist;
	}

	public function generateStaticCode(&$redemptionCode_item)
	{
		$this->load->model(array('static_redemption_code_model'));
		$insertId = null;
		$controller = $this;
		$redemption_code = null;
		$length = 14;
		$insertSuccess = false;
		$failCount = 0;
		do {
			$redemption_code = $controller->createStaticRedemptionCode($length);
			$redemptionCode_item['redemption_code'] = $redemption_code;
			$insertSuccess = $this->lockAndTransForStaticRedemptionCode($redemption_code, function () use ($controller, $redemptionCode_item, &$insertId) {
				$insertId = $controller->static_redemption_code_model->insertItem($redemptionCode_item);
				return $insertId;
			});
			if(empty($insertSuccess) && ($failCount < 10)) {
				$failCount++;
			}
			$this->utils->info_log("============generateRedemptionCode============", ['insertSuccess' => $insertSuccess, 'failCount' => $failCount]);
		} while (empty($insertSuccess) && ($failCount < 10));

		return [$insertSuccess, $insertId, $redemption_code];
	}

	public function realDeleteUnusingStaticCodeByCateId($cateId, $runRealDelete = true){
		if (!$this->permissions->checkPermissions('manage_static_redemption_code_category')) {
			$result = [
				'success' => false,
				'errorMsg' => lang('role.nopermission')
			];
			return false;
		}
		if (!empty($cateId)) {
			$controller = $this;
			$clearCode = $this->lockAndTransForStaticRedemptionCode($cateId, function () use ($controller, $cateId, $runRealDelete) {
				return $controller->static_redemption_code_model->softClearCodeUnderCategory($cateId, $runRealDelete);
			});
			if ($clearCode) {
				$count = $this->static_redemption_code_model->countCodeUnderCategory($cateId);
				$this->static_redemption_code_model->updateCategory($cateId, ['total_redeemable_count' => $count]);
				$this->utils->info_log('realDeleteUnusingStaticCodeByCateId',  lang("redemptionCode.type.deleteSuccess"));
				return true;
			} else {
				$this->utils->info_log('realDeleteUnusingStaticCodeByCateId',  lang('redemptionCode.type.deleteFailed'));
				return false;
			}
		}
	}

	public function ClearUnusingStaticCodeByCateId($cateId = null, $runRealDelete = false)
	{
		if (!$this->permissions->checkPermissions('manage_static_redemption_code_category')) {
			$result = [
				'success' => false,
				'errorMsg' => lang('role.nopermission')
			];
			// $this->returnJsonResult($result);
			$this->returnCommon(BaseController::MESSAGE_TYPE_ERROR, lang('role.nopermission'), $result, '/marketing_management/staticRedemptionCodeCategoryManager');
			return;
		}

		$cateId = $cateId ?: $this->input->post('cateId');
		if (!empty($cateId)) {
			$controller = $this;
			$clearCode = $this->lockAndTransForStaticRedemptionCode($cateId, function () use ($controller, $cateId, $runRealDelete) {
				return $controller->static_redemption_code_model->softClearCodeUnderCategory($cateId, $runRealDelete);
			});
			if ($clearCode) {
				$count = $this->static_redemption_code_model->countCodeUnderCategory($cateId);
				$this->static_redemption_code_model->updateCategory($cateId, ['total_redeemable_count' => $count]);
				$this->returnCommon(BaseController::MESSAGE_TYPE_SUCCESS, lang("redemptionCode.type.deleteSuccess"), NULL, '/marketing_management/staticRedemptionCodeCategoryManager');
			} else {
				$this->returnCommon(BaseController::MESSAGE_TYPE_ERROR, lang('redemptionCode.type.deleteFailed'), NULL, '/marketing_management/staticRedemptionCodeCategoryManager');
			}
		}
		$this->returnCommon(BaseController::MESSAGE_TYPE_WARNING, lang('Empty TypeId'), NULL, '/marketing_management/staticRedemptionCodeCategoryManager');
	}

	public function ClearUnusingStaticCodeByCateIdCommand($cateId = null, $runRealDelete = false)
	{
		$this->load->model(['static_redemption_code_model']);
		if (!empty($cateId)) {
			$controller = $this;
			$clearCode = $this->static_redemption_code_model->lockAndTransForStaticRedemptionCode($cateId, function () use ($controller, $cateId, $runRealDelete) {

				return $controller->static_redemption_code_model->softClearCodeUnderCategory($cateId, $runRealDelete);
			});
			if ($clearCode) {
				$count = $this->static_redemption_code_model->countCodeUnderCategory($cateId);
				$this->static_redemption_code_model->updateCategory($cateId, ['total_redeemable_count' => $count]);
				$this->utils->info_log('ClearUnusingStaticCodeByCateIdCommand',  lang("redemptionCode.type.deleteSuccess"));
			} else {
				$this->utils->info_log('ClearUnusingStaticCodeByCateIdCommand',  lang('redemptionCode.type.deleteFailed'));
			}
		} else {

			$this->utils->info_log('ClearUnusingStaticCodeByCateIdCommand',  lang('Empty TypeId'));
		}
		$this->utils->info_log('ClearUnusingStaticCodeByCateIdCommand done');
	}

	public function deleteTypeAndClearUnusingStaticCode($cateId, $runRealDelete = false)
	{
		if (!$this->permissions->checkPermissions('manage_static_redemption_code_category')) {
			$result = [
				'success' => false,
				'errorMsg' => lang('role.nopermission')
			];
			// $this->returnJsonResult($result);
			$this->returnCommon(BaseController::MESSAGE_TYPE_ERROR, lang('role.nopermission'), $result, '/marketing_management/staticRedemptionCodeCategoryManager');
			return;
		}
		$cateId = $cateId ?: $this->input->post('cateId');
		$success = false;
		$controller = $this;
		$success = $this->lockAndTransForStaticRedemptionCode($cateId, function () use ($controller, $cateId, $runRealDelete) {
			return $controller->static_redemption_code_model->softDeleteCategory($cateId, $runRealDelete);
		});
		if ($success) {
			$this->ClearUnusingStaticCodeByCateId($cateId);
		} else {
			$this->returnCommon(BaseController::MESSAGE_TYPE_ERROR, lang('Delete Failed'), NULL, '/marketing_management/staticRedemptionCodeCategoryManager');
		}
	}


	/// no use
	// public function updateRedemptionCodeStatus()
	// {
	// 	$success = false;
	// 	$itemId = $this->input->post('item_id');
	// 	$currentCategoryStatus = $this->static_redemption_code_model->getItemField($itemId, 'status');
	// 	$operator = $this->authentication->getUsername();
	// 	$newStatus = ($currentCategoryStatus == static_redemption_code_model::ITEM_STATUS_ACTIVATED) ? static_redemption_code_model::ITEM_STATUS_DEACTIVATE : static_redemption_code_model::ITEM_STATUS_ACTIVATED;

	// 	// $currentActionLogs = $this->static_redemption_code_model->getItemField($itemId, 'action_logs');
	// 	$staus_map = [
	// 		static_redemption_code_model::ITEM_STATUS_ACTIVATED => lang('redemptionCode.active'),
	// 		static_redemption_code_model::ITEM_STATUS_DEACTIVATE => lang('redemptionCode.deactive'),
	// 	];
	// 	$timestamp = $this->utils->getNowForMysql();
	// 	// $newActionLogs = $currentActionLogs . "</br>[$timestamp] $operator update Status to {$staus_map[$newStatus]}|";
	// 	$update_arr = [
	// 		'status' => $newStatus,
	// 		// 'action_logs' => $newActionLogs
	// 	];

	// 	$result = $this->static_redemption_code_model->updateItem($itemId, $update_arr);
	// 	$success = $result;
	// 	$return = [
	// 		'success' => $success,
	// 		'data' => $update_arr
	// 	];
	// 	$this->returnJsonResult($return);
	// }


	// no use
	// public function getRedemptionCodeDetailById()
	// {
	// 	$data = [];
	// 	$return = [
	// 		'success' => false
	// 	];
	// 	$itemId = $this->input->post('item_id');
	// 	if (!empty($categoryId)) {

	// 		$_currentItem = $this->static_redemption_code_model->getItemField($itemId);
	// 		$currentItem = isset($_currentItem[0]) ? $_currentItem[0] : null;
	// 		if (!is_null($currentItem)) {
	// 			$currentItem['withdrawal_rules'] =  isset($currentItem['withdrawal_rules']) ? json_decode($currentItem['withdrawal_rules']) : null;
	// 			$data['currentCategory'] = $currentItem;
	// 			$return = [
	// 				'success' => true,
	// 				'result' => $data
	// 			];
	// 		}
	// 	}
	// 	$this->returnJsonResult($return);
	// }
}
