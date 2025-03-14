<?php

trait redemption_code_module
{

	/**
	 * Redemption_code_module
	 * $config['enable_redemption_code_system']
	 * $config['enable_redemption_code_system_in_playercenter']
	 * $config['redemption_code_promo_cms_id'] 
	 */
	public function redemptionCodeCategoryManager()
	{
		if (!$this->redemption_code_model->checkRedemptionCodeEnable() || !$this->permissions->checkPermissions('view_redemption_code_category')) {
			$this->error_access();
			return;
		}

		$this->loadTemplate(lang('redemptionCode.redemptionCodeCategoryManager'), '', '', 'marketing');
		$this->template->add_js($this->utils->thirdpartyUrl('bootstrap-switch/3.3.4/js/bootstrap-switch.min.js'));
		$this->template->add_css($this->utils->thirdpartyUrl('bootstrap-switch/3.3.4/css/bootstrap3/bootstrap-switch.min.css'));
		$this->template->add_css('resources/css/select2.min.css');
		$this->template->add_js('resources/js/select2.full.min.js');
		$this->template->write_view('sidebar', 'marketing_management/sidebar');


		$data = [];
		if (!$this->permissions->checkPermissions('manage_redemption_code_category')) {
			$data['export_report_permission'] = FALSE;
		} else {
			$data['export_report_permission'] = TRUE;
		}



		$data['manage_redemption_code_category'] = $this->permissions->checkPermissions('manage_redemption_code_category') ? TRUE : FALSE;

		$this->template->write_view('main_content', 'marketing_management/redemptioncode/category_manager', $data);
		$this->template->render();
	}

	public function redemptionCodeList($search_apply_date = 'off')
	{
		if (!$this->redemption_code_model->checkRedemptionCodeEnable() || !$this->permissions->checkPermissions('manage_redemption_code')) {
			$this->error_access();
			return;
		}

		$this->loadTemplate(lang('redemptionCode.redemptionCodeList'), '', '', 'marketing');
		// $this->template->add_js($this->utils->thirdpartyUrl('bootstrap-switch/3.3.4/js/bootstrap-switch.min.js'));
		// $this->template->add_css($this->utils->thirdpartyUrl('bootstrap-switch/3.3.4/css/bootstrap3/bootstrap-switch.min.css'));
		$this->template->add_css($this->utils->thirdpartyUrl('bootstrap-multiselect-master/dist/css/bootstrap-multiselect.css'));
		$this->template->add_js($this->utils->thirdpartyUrl('bootstrap-multiselect-master/dist/js/bootstrap-multiselect.js'));
		$this->template->write_view('sidebar', 'marketing_management/sidebar');


		$data = [];
		if (!$this->permissions->checkPermissions('manage_redemption_code')) {
			$data['export_report_permission'] = FALSE;
		} else {
			$data['export_report_permission'] = TRUE;
		}

		$exclud_deleted_type = $this->utils->getConfig('redemption_code_exclud_deleted_type');
		$data['redemptionCodeCategorys'] = $this->redemption_code_model->getAllCategoryTypeName($exclud_deleted_type);
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
			redemption_code_model::CODE_STATUS_UNUSED => lang('redemptionCode.codeUnused'),
			redemption_code_model::CODE_STATUS_USED => lang('redemptionCode.codeUsed'),
			redemption_code_model::CODE_STATUS_EXPIRED => lang('redemptionCode.codeExpired'),
		];
		$this->template->write_view('main_content', 'marketing_management/redemptioncode/view_redemptioncode_report', $data);

		$this->template->render();
	}

	public function addRedemptionCodeCategory()
	{
		if (!$this->permissions->checkPermissions('manage_redemption_code_category')) {
			$result = [
				'success' => false,
				'errorMsg' => lang('role.nopermission')
			];
			$this->returnJsonResult($result);
			return;
		}
		$form_input = $this->input->post();
		$operator = $this->authentication->getUsername();
		$timestamp = $this->utils->getNowForMysql();
		$action_logs = "|[$timestamp]add by $operator|";

		$_withdrawal_rules = $this->setWithdrawalRules($form_input);
		$_withdrawal_rules['bonusApplicationLimit'] = $this->setBonusApplicationLimit($form_input);
		$is_valid_forever = isset($form_input['isValidForever']) && $form_input['isValidForever'] == "on" ? true : false;
		$new_category = array(
			'category_name' => trim($form_input['categoryName']),
			'withdrawal_rules' => json_encode($_withdrawal_rules),
			'bonus' => $form_input['bonus'],
			'created_by' => $this->authentication->getUsername(),
			'expires_at' => $is_valid_forever ? null : $form_input['hideDate'],
			'valid_forever' => $is_valid_forever,
			'status' => redemption_code_model::CATEGORY_STATUS_DEACTIVATE,
			'notes' => htmlentities($form_input['catenote']),
			'action_logs' => htmlentities($action_logs)
		);

		if ($this->redemption_code_model->checkCategoryNameExist($form_input['categoryName'])) {
			$result = [
				'success' => false,
				'noteType' => 'name',
				'errorMsg' => lang('Type exist.')
			];
			$this->returnJsonResult($result);
			return;
		}

		$category_id = $this->redemption_code_model->insertCategory($new_category);
		// return $category_id;
		$result = [
			'success' => true,
			'successMsg' => lang('redemptionCode.categoryAdded'),
			'data' => $new_category
		];
		$this->returnJsonResult($result);
	}

	public function updateRedemptionCodeCategory()
	{
		if (!$this->permissions->checkPermissions('manage_redemption_code_category')) {
			$result = [
				'success' => false,
				'errorMsg' => lang('role.nopermission')
			];
			$this->returnJsonResult($result);
			return;
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
			$currentCategory = $this->redemption_code_model->getCategory($categoryId);
			// $currentActionLogs = $currentCategory['action_logs'];
			// $newActionLogs = $currentActionLogs . "</br>[$timestamp] edit by $operator |";
			$newCategoryName = trim($form_input['categoryName']);
			$_withdrawal_rules = $this->setWithdrawalRules($form_input);
			$_withdrawal_rules['bonusApplicationLimit'] = $this->setBonusApplicationLimit($form_input);
			$edit_category = array(
				'category_name' => $newCategoryName,
				'withdrawal_rules' => json_encode($_withdrawal_rules),
				'bonus' => $form_input['bonus'],
				'updated_by' => $operator,
				'expires_at' => $is_valid_forever ? null : $form_input['hideDate'],
				'valid_forever' => $is_valid_forever,
				'notes' => htmlentities($form_input['catenote']),
				// 'action_logs' => $newActionLogs
			);
			if (trim($currentCategory['category_name']) != $newCategoryName && $this->redemption_code_model->checkCategoryNameExist($newCategoryName)) {
				$result = [
					'success' => false,
					'noteType' => 'name',
					'errorMsg' => lang('Type exist.')
				];
				$this->returnJsonResult($result);
				return;
			}

			$category_id = $this->redemption_code_model->updateCategory($categoryId, $edit_category);

			$result = [
				'success' => true,
				'successMsg' => lang('redemptionCode.categoryUpdated'),
				'data' => $edit_category
			];
		}
		$this->returnJsonResult($result);
	}

	public function updateRedemptionCodeCategoryStatus()
	{
		if (!$this->permissions->checkPermissions('manage_redemption_code_category')) {
			$result = [
				'success' => false,
				'errorMsg' => lang('role.nopermission')
			];
			$this->returnJsonResult($result);
			return;
		}
		$success = false;
		$categoryId = $this->input->post('category_id');
		$currentCategoryStatus = $this->redemption_code_model->getCategory($categoryId, 'status');
		$operator = $this->authentication->getUsername();
		$newStatus = ($currentCategoryStatus == redemption_code_model::CATEGORY_STATUS_ACTIVATED) ? redemption_code_model::CATEGORY_STATUS_DEACTIVATE : redemption_code_model::CATEGORY_STATUS_ACTIVATED;

		// $currentActionLogs = $this->redemption_code_model->getCategory($categoryId, 'action_logs');
		// $staus_map = [
		// 	redemption_code_model::CATEGORY_STATUS_ACTIVATED => lang('redemptionCode.categoryActive'),
		// 	redemption_code_model::CATEGORY_STATUS_DEACTIVATE => lang('redemptionCode.categoryDeactive'),
		// ];
		// $timestamp = $this->utils->getNowForMysql();
		// $newActionLogs = $currentActionLogs . "</br>[$timestamp] $operator update Status to {$staus_map[$newStatus]}|";
		$update_arr = [
			'status' => $newStatus,
			'updated_by' => $operator,
			// 'action_logs' => $newActionLogs
		];

		$result = $this->redemption_code_model->updateCategory($categoryId, $update_arr);
		$success = $result;
		$return = [
			'success' => $success,
			'data' => $update_arr
		];
		$this->returnJsonResult($return);
	}

	public function getRedemptionCodeCategoryDetailByCategoryId()
	{
		$data = [];
		$return = [
			'success' => false
		];
		$categoryId = $this->input->post('category_id');
		if (!empty($categoryId)) {

			$currentCategory = $this->redemption_code_model->getCategory($categoryId);
			// $currentCategory = isset($_currentCategory[0]) ? $_currentCategory[0] : null;
			if (!is_null($currentCategory)) {
				$withdrawal_rules = isset($currentCategory['withdrawal_rules']) ? json_decode($currentCategory['withdrawal_rules'], true) : null;
				$application_limit_setting = isset($withdrawal_rules['bonusApplicationLimit']) ? $withdrawal_rules['bonusApplicationLimit'] : null;
				unset($withdrawal_rules['bonusApplicationLimit']);
				$currentCategory['withdrawal_rules'] = $withdrawal_rules;
				$currentCategory['application_limit_setting'] = $application_limit_setting;
				$data['currentCategory'] = $currentCategory;
				$return = [
					'success' => true,
					'result' => $data
				];
			}
		}
		$this->returnJsonResult($return);
	}


	private function setWithdrawalRules($form_input)
	{
		$_withdrawal_rules = [
			'withdrawRequirementBettingConditionOption' => null,
			'withdrawReqBonusTimes' => null,
			'withdrawReqBetAmount' => null,
			'withdrawRequirementDepositConditionOption' => null,
			'withdrawReqDepMinLimit' => null,
			'withdrawReqDepMinLimitSinceRegistration' => null,
		];
		foreach ($_withdrawal_rules as $key => $values) {
			if (isset($form_input[$key])) {

				$_withdrawal_rules[$key] = $form_input[$key];
			}
		}
		return $_withdrawal_rules;
	}
	private function setBonusApplicationLimit($form_input)
	{
		$_bonusApplicationLimit = [
			'bonusApplicationLimitDateType' => Promorules::BONUS_APPLICATION_LIMIT_DATE_TYPE_NONE,
			'bonusReleaseTypeOptionByNonSuccessionLimitOption' => Promorules::BONUS_APPLICATION_LIMIT_RULE_NO_LIMIT,
			'limitCnt' => 0,
		];

		foreach ($_bonusApplicationLimit as $key => $values) {
			if (isset($form_input[$key])) {

				$_bonusApplicationLimit[$key] = $form_input[$key];
			}
		}
		if ($_bonusApplicationLimit['limitCnt'] == 0) {
			$_bonusApplicationLimit['bonusReleaseTypeOptionByNonSuccessionLimitOption'] = Promorules::BONUS_APPLICATION_LIMIT_RULE_NO_LIMIT;
		}
		return $_bonusApplicationLimit;
	}


	public function generateRedemptionCodeForType($categoryId = null, $quantity = 0, $length = 10)
	{
		$result = [
			'success' => false
		];
		if (!$this->permissions->checkPermissions('manage_redemption_code_category')) {
			$result = [
				'success' => false,
				'errorMsg' => lang('role.nopermission')
			];
			// $this->returnJsonResult($result);
			$this->returnCommon(BaseController::MESSAGE_TYPE_ERROR, lang('role.nopermission'), $result, '/marketing_management/redemptionCodeCategoryManager');
			return;
		}
		if ($this->input->is_ajax_request()) {
			$categoryId = $this->input->post('category_id');
			$quantity = $this->input->post('quantity');
			$length = $this->input->post('code_length') ?: 10;
		}
		if (!empty($categoryId) && floatval($quantity) <= 10000 && floatval($quantity) > 0) {
			$operator = $this->authentication->getUsername();
			$currentCategory = $this->redemption_code_model->getCategory($categoryId);
			// $currentCategory = isset($_currentCategory[0]) ? $_currentCategory[0] : null;
			$timestamp = $this->utils->getNowForMysql();
			$action_logs = "|[$timestamp]generate by $operator|";
			// run generate
			$redemptionCode_array = [];
			$redemptionCode_item = [
				'category_id' => $categoryId,
				'redemption_code' => '',
				'current_withdrawal_rules' => $currentCategory['withdrawal_rules'],
				'current_bonus' => $currentCategory['bonus'],
				'created_by' => $operator,
				'status' =>  redemption_code_model::ITEM_STATUS_ACTIVATED,
				'action_logs' => $action_logs
			];
			$last_quantity = $currentCategory['quantity'] ?: 0;
			$category_name = $currentCategory['category_name'];
			$count_success = 0;
			$count_failed = 0;
			for ($i = 0; $i < $quantity; $i++) {
				if ($count_failed > 10) {
					break;
				}
				try {
					$redemption_code = $this->createRedemptionCode($length, $category_name);
					$redemptionCode_item['redemption_code'] = $redemption_code;
					$insertId = null;
					$controller = $this;
					$insertSuccess = $this->lockAndTransForRedemptionCode($categoryId, function () use ($controller, $categoryId, $redemptionCode_item, &$insertId) {
						$insertId = $controller->redemption_code_model->insertItem($redemptionCode_item);
						return $insertId;
					});
					if ($insertSuccess && !empty($insertId)) {
						$redemptionCode_array[] = $redemptionCode_item;
						$this->utils->info_log("============generateRedemptionCode============ insertid : [$insertId]", $redemptionCode_item);
						$count_success++;
					}
				} catch (Exception $e) {
					$is_exist = $this->redemption_code_model->checkRedemptionCodeExist($redemption_code);
					$this->utils->info_log(__METHOD__ . ' error create redemption_code', $e->getMessage());
					if ($is_exist) {
						$this->utils->info_log("============RedemptionCodeExist============", $redemption_code);
						$i--;
					}
					$count_failed++;
				}
			}
			$result = [
				'success' => true,
				// 'data' => $redemptionCode_array,
				'successMsg' => lang('redemptionCode.generateSuccess'),
				'count_success' => $count_success,
				'count_failed' => $count_failed,
				'redriectCodeReport' => "/marketing_management/redemptionCodeList?codeType=$categoryId"
			];
			$this->utils->debug_log(__METHOD__ . 'result', $result);

			// $new_quantity = $count_success + $last_quantity;
			$new_quantity = $currentItemsCount = $this->redemption_code_model->countCodeUnderCategory($categoryId);
			$this->redemption_code_model->updateCategory($categoryId, [
				'quantity' => $new_quantity
			]);
			$this->returnCommon(BaseController::MESSAGE_TYPE_SUCCESS, $result['successMsg'], $result, '/marketing_management/redemptionCodeCategoryManager');
		} else {
			if (floatval($quantity) > 10000 || floatval($quantity) <= 0) {
				$result['errorMsg'] = lang('redemptionCode.quantityRule'); //lang('Quantity need to be greater than 0 less than 10000');
			}
			$this->returnCommon(BaseController::MESSAGE_TYPE_ERROR, $result['errorMsg'], $result, '/marketing_management/redemptionCodeCategoryManager');
		}
	}


	public function generateRedemptionCodeByQueue()
	{
		$result = [
			'success' => false
		];
		if (!$this->permissions->checkPermissions('manage_redemption_code_category')) {
			$result['errorMsg'] = lang('role.nopermission');
			return $this->returnCommon(BaseController::MESSAGE_TYPE_ERROR, lang('role.nopermission'), $result, '/marketing_management/redemptionCodeCategoryManager');
		}

		$categoryId = $this->input->post('category_id');
		$quantity = $this->input->post('quantity');

		if (empty($categoryId) || empty($quantity)) {
			$result['errorMsg'] = lang('redemptionCode.fillQuantity');//lang('Kindly fill up quantity');
			return $this->returnCommon(BaseController::MESSAGE_TYPE_ERROR, $result['errorMsg'], $result, '/marketing_management/redemptionCodeCategoryManager');

		}
		if (floatval($quantity) > 10000 || floatval($quantity) <= 0) {
			$result['errorMsg'] = lang('redemptionCode.quantityRule');//lang('Quantity need to be greater than 0 less than 10000');
			return $this->returnCommon(BaseController::MESSAGE_TYPE_ERROR, $result['errorMsg'], $result, '/marketing_management/redemptionCodeCategoryManager');
		}

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
			'quantity' => $quantity,
			'operator' => $operator,
		];

		$token = $this->lib_queue->addRemoteGenerateRedemptionCode($params, $callerType, $caller, $state);
		if ($token) {
			$currentCategoryQuantity = $this->redemption_code_model->getCategory($categoryId, 'quantity');
			$new_quantity = $currentCategoryQuantity + $quantity;
			$this->redemption_code_model->updateCategory($categoryId, [
				'quantity' => $new_quantity
			]);

			$result = [
				'success' => true,
				// 'data' => $redemptionCode_array,
				'successMsg' => lang('redemptionCode.generateSuccess'),
				'redriectGenerateProgress' => '/system_management/common_queue/' . $token,
				'redriectCodeReport' => "/marketing_management/redemptionCodeList?codeStatus=1&codeType=$categoryId"
			];
			return $this->returnCommon(BaseController::MESSAGE_TYPE_SUCCESS, $result['successMsg'], $result, '/marketing_management/redemptionCodeCategoryManager');
		} else {
			$result = [
				'success' => false,
				// 'data' => $redemptionCode_array,
				'errorMsg' => lang('Create job failed'),
				'redriectCodeReport' => "/marketing_management/redemptionCodeList?codeStatus=1&codeType=$categoryId"
			];
			return $this->returnCommon(BaseController::MESSAGE_TYPE_ERROR, $result['errorMsg'], $result, '/marketing_management/redemptionCodeCategoryManager');
		}
	}

	public function generate_redemption_code_job($token)
	{
		$this->load->library(array('authentication'));
		$this->load->model(array('redemption_code_model'));
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
		$quantity = isset($params['quantity']) ? $params['quantity'] : null;
		$operator = isset($params['operator']) ? $params['operator'] : null;

		$rlt['categoryId'] = $categoryId;
		$rlt['quantity'] = $quantity;
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
		sleep(2);
		$currentCategory = $this->redemption_code_model->getCategory($categoryId);
		$timestamp = $this->utils->getNowForMysql();
		$action_logs = "|[$timestamp]generate by $operator|";
		// run generate
		$redemptionCode_array = [];
		$redemptionCode_item = [
			'category_id' => $categoryId,
			'redemption_code' => '',
			'current_withdrawal_rules' => $currentCategory['withdrawal_rules'],
			'current_bonus' => $currentCategory['bonus'],
			'created_by' => $operator,
			'status' =>  redemption_code_model::ITEM_STATUS_ACTIVATED,
			'action_logs' => $action_logs
		];
		$last_quantity = $currentCategory['quantity'] ?: 0;
		$category_name = $currentCategory['category_name'];

		for ($i = 0; $i < $quantity; $i++) {
			if ($count_failed > 10) {
				break;
			}
			try {

				list($insertSuccess, $insertId, $redemption_code) = $this->generateCode($redemptionCode_item);
				if ($insertSuccess && !empty($insertId)) {
					// $redemptionCode_array[] = $redemptionCode_item;
					$this->utils->info_log("============generateRedemptionCode============ insertid : [$insertId]", $redemptionCode_item);
					$count_success++;
					$rlt['success'] = true;
					$rlt['done'] = false;
					$rlt['count_success'] = $count_success;
					$rlt['count_failed'] = $count_failed;
					$rlt['progress'] = $count_success;
					$rlt['process_status'] = 0;
					$queue_result_model->updateResultRunning($token, $rlt);
				}
			} catch (Exception $e) {
				$this->utils->info_log(__METHOD__ . ' error create redemption_code', $e->getMessage());
				$count_failed++;
			}
		}

		$success = true;

		if (!$success) {
			$rlt['success'] = true;
			$rlt['done'] = true;
			$rlt['count_success'] = $count_success;
			$rlt['count_failed'] = $count_failed;
			$rlt['process_status'] = 3;
			$rlt['params'] = $params;
			$rlt['message'] = 'Unknown error.';
			$this->utils->error_log('running generate_redemption_code_job error', 'rlt', $rlt);
			$queue_result_model->updateResultWithCustomStatus($token, $rlt, true, true);
			return false;
		}

		$new_quantity = $currentItemsCount = $this->redemption_code_model->countCodeUnderCategory($categoryId);

		$rlt['success'] = true;
		$rlt['done'] = true;
		$rlt['count_success'] = $count_success;
		$rlt['count_failed'] = $count_failed;
		$rlt['currentItemsCount'] = $currentItemsCount;
		$rlt['process_status'] = 0;
		$rlt['params'] = $params;
		$rlt['message'] = 'Completed.';
		$this->utils->debug_log('running generate_redemption_code_job success', 'rlt', $rlt);
		$queue_result_model->updateResultWithCustomStatus($token, $rlt, true);

		$result = [
			'success' => true,
			'successMsg' => lang('redemptionCode.generateSuccess'),
			'count_success' => $count_success,
			'count_failed' => $count_failed,
			// 'redriectCodeReport' => "/marketing_management/redemptionCodeList?codeType=$categoryId"
		];
		$queue_result_model->updateFinalResult($token, $rlt['success'], $rlt['message'], $rlt['progress'], ((int)$count_success + (int)$count_failed), $rlt['done']);
		$this->utils->debug_log(__METHOD__ . 'result', $result);

		$this->redemption_code_model->updateCategory($categoryId, [
			'quantity' => $new_quantity
		]);
		return true;
	}

	public function generateCodeWithInternalMessage()
	{
		$result = [
			'success' => false,
			'errorMsg' => lang('role.nopermission')
		];
		if (!$this->permissions->checkPermissions('manage_redemption_code_category')) {
			$result['errorMsg'] = lang('role.nopermission');
			return $this->returnCommon(BaseController::MESSAGE_TYPE_ERROR, lang('role.nopermission'), $result, '/marketing_management/redemptionCodeCategoryManager');
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
				return $this->returnCommon(BaseController::MESSAGE_TYPE_ERROR, $message, $result, '/marketing_management/redemptionCodeCategoryManager');
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

		// $this->returnCommon(BaseController::MESSAGE_TYPE_SUCCESS, $result['successMsg'], $result, '/marketing_management/redemptionCodeCategoryManager');

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

			$token = $this->lib_queue->addRemoteGenerateRedemptionCodeByMessage($params, $callerType, $caller, $state, $lang);

			if (!empty($token)) {
				// $this->alertMessage(BaseController::MESSAGE_TYPE_SUCCESS, lang('Create importing job successfully'));
				// return redirect('marketing_management/post_manually_batch_add_cashback_bonus_result/'.$token);
				// $this->returnCommon(BaseController::MESSAGE_TYPE_SUCCESS, lang('Create job successfully'), $result, '/system_management/common_queue/' . $token);
				$result = [
					'success' => true,
					'successMsg' => lang('redemptionCode.generateSuccess'),
					'redriectCodeReport' => "/marketing_management/redemptionCodeList?codeStatus=".redemption_code_model::CODE_STATUS_UNUSED."&codeType=$categoryId"
				];
				return $this->returnCommon(BaseController::MESSAGE_TYPE_SUCCESS, lang('Create job successfully'), $result, '/marketing_management/generate_redemption_code_with_internal_message_job_result/' . $token);
				// marketing_management/redemptioncode/generate_redemption_code_with_internal_message_job_result.php
			} else {
				$result = [
					'success' => false,
					'successMsg' => lang('Create job failed'),
					'errorMsg' => lang('Create job failed'),
					'redriectCodeReport' => "/marketing_management/redemptionCodeCategoryManager"
				];
				return $this->returnCommon(BaseController::MESSAGE_TYPE_ERROR, lang('Create job failed'), $result, '/marketing_management/redemptionCodeCategoryManager');
			}
		} else {
			$result = [
				'success' => false,
				'successMsg' => lang('Upload csv file failed'),
				'errorMsg' => lang('Upload csv file failed'),
				'redriectCodeReport' => "/marketing_management/redemptionCodeCategoryManager"
			];
			return $this->returnCommon(BaseController::MESSAGE_TYPE_ERROR, lang('Upload csv file failed'), $result, '/marketing_management/redemptionCodeCategoryManager');
		}
	}
	public function generate_redemption_code_with_internal_message_job_result($token){
		$data['result_token']=$token;
		$this->loadTemplate(lang('Marketing Management'), '', '', 'marketing');
		$this->template->write_view('sidebar', 'marketing_management/sidebar');
		$this->template->write_view('main_content', 'marketing_management/redemptioncode/generate_redemption_code_with_internal_message_job_result', $data);
		$this->template->render();
	}

	public function generate_redemption_code_with_internal_message_job($token)
	{

		$this->load->model(['queue_result', 'player_model', 'redemption_code_model']);

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
		$currentCategory = $this->redemption_code_model->getCategory($categoryId);
		$timestamp = $this->utils->getNowForMysql();
		$action_logs = "|[$timestamp]generate by $adminUsername|";
		// run generate
		$redemptionCode_array = [];
		$redemptionCode_item = [
			'category_id' => $categoryId,
			'redemption_code' => '',
			'current_withdrawal_rules' => $currentCategory['withdrawal_rules'],
			'current_bonus' => $currentCategory['bonus'],
			'created_by' => $adminUsername,
			'status' =>  redemption_code_model::ITEM_STATUS_ACTIVATED,
			'action_logs' => $action_logs,
			'notes' => '',
		];
		$last_quantity = $currentCategory['quantity'] ?: 0;
		$category_name = $currentCategory['category_name'];

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
				$redemptionCode_item,
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

					$lockedKey = null;
					$lock_it = $controller->lockPlayerBalanceResource($player_id, $lockedKey);
					$controller->utils->info_log("csv_row", $csv_row, 'count_loop', $count_loop, 'totalCount', $totalCount);

					try {
						if ($lock_it) {

							$controller->startTrans();
							$timestamp = $this->utils->getNowForMysql();
							$redemptionCode_item['notes'] = "[$timestamp]:send to $username|";
							list($insertSuccess, $insertId, $redemption_code) = $controller->generateCode($redemptionCode_item);
							if($insertSuccess && !empty($redemption_code)) {

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

							if (empty($message_id) || !$success || empty($insertSuccess)) {
								$failCount++;
								$message_log = ['username' => $row['username'], 'reason' => 'fail to send message'];
								$controller->utils->_appendSaveDetailedResultToRemoteLog($token, $funcName . '_failed_results', $message_log, $failed_log_filepath, true, []);
							} else {
								$successCount++;
							}
						} //lockit end
					} finally {
						$controller->releasePlayerBalanceResource($player_id, $lockedKey);
					}
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
		$new_quantity = $this->redemption_code_model->countCodeUnderCategory($categoryId);
		$this->redemption_code_model->updateCategory($categoryId, [
			'quantity' => $new_quantity
		]);
		$controller->utils->debug_log("generate_redemption_code_with_internal_message_job, [$successCount] out of [$totalCount] succeed.  failed_log_filepath: " . $failed_log_filepath);
	}

	private function createRedemptionCode($length, $category_name = null)
	{
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$string = '';

		for ($i = 0; $i < $length; $i++) {
			$string .= $characters[mt_rand(0, strlen($characters) - 1)];
		}

		return $string;
	}

	public function generateCode(&$redemptionCode_item)
	{
		$this->load->model(array('redemption_code_model'));
		$insertId = null;
		$controller = $this;
		$redemption_code = null;
		$length = 10;
		$insertSuccess = false;
		$failCount = 0;
		do {
			$redemption_code = $controller->createRedemptionCode($length);
			$redemptionCode_item['redemption_code'] = $redemption_code;
			$insertSuccess = $this->lockAndTransForRedemptionCode($redemption_code, function () use ($controller, $redemptionCode_item, &$insertId) {
				$insertId = $controller->redemption_code_model->insertItem($redemptionCode_item);
				return $insertId;
			});
			if(empty($insertSuccess) && ($failCount < 10)) {
				$failCount++;
			}
			$this->utils->info_log("============generateRedemptionCode============", ['insertSuccess' => $insertSuccess, 'failCount' => $failCount]);
		} while (empty($insertSuccess) && ($failCount < 10));

		return [$insertSuccess, $insertId, $redemption_code];
	}

	public function ClearUnusingCodeByCateId($cateId = null, $runRealDelete = false)
	{

		if (!$this->permissions->checkPermissions('manage_redemption_code_category')) {
			$result = [
				'success' => false,
				'errorMsg' => lang('role.nopermission')
			];
			// $this->returnJsonResult($result);
			$this->returnCommon(BaseController::MESSAGE_TYPE_ERROR, lang('role.nopermission'), $result, '/marketing_management/redemptionCodeCategoryManager');
			return;
		}

		$cateId = $cateId ?: $this->input->post('cateId');
		if (!empty($cateId)) {
			$controller = $this;
			$clearCode = $this->lockAndTransForRedemptionCode($cateId, function () use ($controller, $cateId, $runRealDelete) {

				return $controller->redemption_code_model->softClearCodeUnderCategory($cateId, $runRealDelete);
			});
			if ($clearCode) {
				$count = $this->redemption_code_model->countCodeUnderCategory($cateId);
				$this->redemption_code_model->updateCategory($cateId, ['quantity' => $count]);
				$this->returnCommon(BaseController::MESSAGE_TYPE_SUCCESS, lang("redemptionCode.type.deleteSuccess"), NULL, '/marketing_management/redemptionCodeCategoryManager');
			} else {
				$this->returnCommon(BaseController::MESSAGE_TYPE_ERROR, lang('redemptionCode.type.deleteFailed'), NULL, '/marketing_management/redemptionCodeCategoryManager');
			}
		}
		$this->returnCommon(BaseController::MESSAGE_TYPE_WARNING, lang('Empty TypeId'), NULL, '/marketing_management/redemptionCodeCategoryManager');
	}

	public function ClearUnusingCodeByCateIdCommand($cateId = null, $runRealDelete = false)
	{
		$this->load->model(['redemption_code_model']);
		if (!empty($cateId)) {
			$controller = $this;
			$clearCode = $this->redemption_code_model->lockAndTransForRedemptionCode($cateId, function () use ($controller, $cateId, $runRealDelete) {

				return $controller->redemption_code_model->softClearCodeUnderCategory($cateId, $runRealDelete);
			});
			if ($clearCode) {
				$count = $this->redemption_code_model->countCodeUnderCategory($cateId);
				$this->redemption_code_model->updateCategory($cateId, ['quantity' => $count]);
				$this->utils->info_log('ClearUnusingCodeByCateIdCommand',  lang("redemptionCode.type.deleteSuccess"));
			} else {
				$this->utils->info_log('ClearUnusingCodeByCateIdCommand',  lang('redemptionCode.type.deleteFailed'));
			}
		} else {

			$this->utils->info_log('ClearUnusingCodeByCateIdCommand',  lang('Empty TypeId'));
		}
		$this->utils->info_log('ClearUnusingCodeByCateIdCommand done');
	}

	public function deleteTypeAndClearUnusingCode($cateId, $runRealDelete = false)
	{
		if (!$this->permissions->checkPermissions('manage_redemption_code_category')) {
			$result = [
				'success' => false,
				'errorMsg' => lang('role.nopermission')
			];
			// $this->returnJsonResult($result);
			$this->returnCommon(BaseController::MESSAGE_TYPE_ERROR, lang('role.nopermission'), $result, '/marketing_management/redemptionCodeCategoryManager');
			return;
		}
		$cateId = $cateId ?: $this->input->post('cateId');
		$success = false;
		$controller = $this;
		$success = $this->lockAndTransForRedemptionCode($cateId, function () use ($controller, $cateId, $runRealDelete) {
			return $controller->redemption_code_model->softDeleteCategory($cateId, $runRealDelete);
		});
		if ($success) {

			$this->ClearUnusingCodeByCateId($cateId);
		} else {
			$this->returnCommon(BaseController::MESSAGE_TYPE_ERROR, lang('Delete Failed'), NULL, '/marketing_management/redemptionCodeCategoryManager');
		}
	}

	public function updateRedemptionCodeStatus()
	{
		$success = false;
		$itemId = $this->input->post('item_id');
		$currentCategoryStatus = $this->redemption_code_model->getItemField($itemId, 'status');
		$operator = $this->authentication->getUsername();
		$newStatus = ($currentCategoryStatus == redemption_code_model::ITEM_STATUS_ACTIVATED) ? redemption_code_model::ITEM_STATUS_DEACTIVATE : redemption_code_model::ITEM_STATUS_ACTIVATED;

		// $currentActionLogs = $this->redemption_code_model->getItemField($itemId, 'action_logs');
		$staus_map = [
			redemption_code_model::ITEM_STATUS_ACTIVATED => lang('redemptionCode.active'),
			redemption_code_model::ITEM_STATUS_DEACTIVATE => lang('redemptionCode.deactive'),
		];
		$timestamp = $this->utils->getNowForMysql();
		// $newActionLogs = $currentActionLogs . "</br>[$timestamp] $operator update Status to {$staus_map[$newStatus]}|";
		$update_arr = [
			'status' => $newStatus,
			// 'action_logs' => $newActionLogs
		];

		$result = $this->redemption_code_model->updateItem($itemId, $update_arr);
		$success = $result;
		$return = [
			'success' => $success,
			'data' => $update_arr
		];
		$this->returnJsonResult($return);
	}

	public function getRedemptionCodeDetailById()
	{
		$data = [];
		$return = [
			'success' => false
		];
		$itemId = $this->input->post('item_id');
		if (!empty($categoryId)) {

			$_currentItem = $this->redemption_code_model->getItemField($itemId);
			$currentItem = isset($_currentItem[0]) ? $_currentItem[0] : null;
			if (!is_null($currentItem)) {
				$currentItem['withdrawal_rules'] =  isset($currentItem['withdrawal_rules']) ? json_decode($currentItem['withdrawal_rules']) : null;
				$data['currentCategory'] = $currentItem;
				$return = [
					'success' => true,
					'result' => $data
				];
			}
		}
		$this->returnJsonResult($return);
	}
}
