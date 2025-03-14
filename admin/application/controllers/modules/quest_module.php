<?php

/**
 * Class quest_module
 *
 * 
 * * Add/update/delete quest 
 * * Activate/deactivate quest 
 * @property quest_category $quest_category
 * @property quest_manager $quest_manager
 *
*/
trait quest_module {

	/**
	 * overview : quest category
	 *
	 * detail : view page for quest category setting list
	 *
	 * @return  redered template
	 */
	public function quest_category() {
		$this->loadTemplate(lang('cms.questCategorySetting'), '', '', 'marketing');
		$this->template->write_view('sidebar', 'marketing_management/sidebar');

        if (!$this->permissions->checkPermissions('quest_category_setting')){
            $this->error_access();
            return;
        }

		if (!$this->permissions->checkPermissions('export_report')) {
			$data['export_report_permission'] = FALSE;
		} else {
			$data['export_report_permission'] = TRUE;
		}

		$this->load->model(array('quest_category'));

		$data['questCategory'] = $this->quest_category->getQuestCategory();

        $quest_setting = $this->utils->getConfig('quest_setting');
        $data['questCategoryContent'] = $this->utils->safeGetArray($quest_setting, 'quest_category_content', '');

		$this->template->write_view('main_content', 'marketing_management/quest/quest_category', $data);
		$this->template->render();
	}

	public function getQuestCategoryDetails($questCategoryId) {
		$result = $this->quest_category->getQuestCategoryDetails($questCategoryId);
		$result[0]['icon_path'] = $this->utils->getQuestCategoryIcon($result[0]['iconPath']);
        $result[0]['startDate'] = date("Y-m-d", strtotime($result[0]['startAt']));
		$result[0]['startTime'] = date("H:i:s", strtotime($result[0]['startAt']));	
		$result[0]['endDate'] = date("Y-m-d", strtotime($result[0]['endAt']));
		$result[0]['endTime'] = date("H:i:s", strtotime($result[0]['endAt']));
        $result[0]['existQuestManager'] = $this->quest_category->checkQuestManager($questCategoryId);
		$this->returnJsonResult($result);
	}

	public function addQuestCategory(){
		$this->load->model('quest_category');
		$icon_file_name = null;

        if (!$this->permissions->checkPermissions('quest_category_add')){
            $this->error_access();
            return;
        }

        $startAt = $this->input->post("startDate"). " ". $this->input->post("startTime");
        $endAt = $this->input->post("endDate"). " ". $this->input->post("endTime");
        if($endAt < $startAt){
            return $this->returnJsonResult(array('success' => false, 'noteType' => 'date'));
        }

		if (!empty($_FILES['uesrfile'])) {
			# upload bank icon
			$upload_response = $this->quest_category->uploadQuestCategoryIcon($_FILES['uesrfile']);

			if ($upload_response['status'] == 'success' && isset($upload_response['fileName'])) {
				if (!empty($upload_response['fileName'])) {
					$icon_file_name = $upload_response['fileName'];
				}
			}
		}
        $questCatecoryTitle = lang($this->input->post("questCatecoryTitleView"));

		$questCategoryOrder = $this->input->post("questCategoryOrderId");
        $questCategorOrderLen = strlen((int)$questCategoryOrder);

        $questCategoryDescLen  = mb_strlen($this->input->post("questCategoryDesc"), "utf-8");

        if($questCategorOrderLen > Promo_type::PROMO_CATEGORY_ORDER_MAX_CHARACTERS){
			return $this->returnJsonResult(array('success' => false, 'noteType' => 'orderMaxChar'));
        }

		$questCatecoryTitleLen  = mb_strlen($questCatecoryTitle, "utf-8");
		if($questCatecoryTitleLen > Promo_type::PROMO_CATEGORY_NAME_MAX_CHARACTERS){
			return $this->returnJsonResult(array('success' => false, 'noteType' => 'nameLen'));
        }

        if($questCategoryDescLen > Promo_type::PROMO_CATEGORY_INTERNAL_REMARK_MAX_CHARACTERS){
			return $this->returnJsonResult(array('success' => false, 'noteType' => 'descLen'));
        }

        $nextOrder = $this->quest_category->getNextOrder();
		if(!isset($questCategoryOrder) || empty($questCategoryOrder)){
            $questCategoryOrder = $nextOrder;
        }

		$iconPathName = $this->input->post('userfile');
		$iconUrl = $this->input->post('icon_url');

		if(!empty($iconUrl) && strrpos($iconUrl, '.') > 0) {
			$fileType = substr($iconUrl, strrpos($iconUrl, '.') + 1);
		}
		
		$path_image = $_FILES['userfile']['name'];
		$image = isset($_FILES['userfile']) ? $_FILES['userfile'] : null;

		if (!empty($path_image[0])) {

			$this->load->library('multiple_image_uploader');

			//we can use new upload library here
			$questIconName = 'questIcon-' . uniqid();

			$response = $this->multiple_image_uploader->do_multiple_uploads($image, $this->utils->getQuestThumbnails(), $this->getQuestUploadConfig($questIconName), $questIconName);
			if ($response['status'] == "fail") {
				$this->alertMessage(self::MESSAGE_TYPE_ERROR, $response['message']);
				redirect(BASEURL . 'marketing_management/quest_category');
			}
			$iconUrl = $questIconName . '.' . $fileType;
		}

		$questcategorydata = array(
			'title'       	=> $this->input->post("questCatecoryTitle"),
			'description' 	=> htmlspecialchars($this->input->post("questCategoryDesc")),
			'sort'		  	=> $questCategoryOrder,
			'iconPath'	  	=> $iconUrl,
			'createdBy'   	=> $this->authentication->getUserId(),
			'updatedBy'   	=> $this->authentication->getUserId(),
			'status'	  	=> $this->input->post("questCategoryStatus"),
            'showTimer'     => $this->input->post("showTimer"),
            'startAt'       => $startAt,
            'endAt'         => $endAt,
            'coverQuestTime'=> $this->input->post("coverQuestTime"),
            'period'        => ($this->input->post("period")) ? $this->input->post("period") : 0,
		);

		$res = $this->quest_category->addQuestCategory($questcategorydata);

		if ($res) {
			$this->saveAction(self::MANAGEMENT_TITLE, 'Added Quest Category', "User " . $this->authentication->getUsername() . " has successfully added promo type.");
			$this->returnJsonResult(array('success' => true, 'msg' => lang('Quest Category saved.')));
		}

	}

	private function getQuestUploadConfig($questThumbnailName) {
		$config = array(
			'allowed_types' => 'jpg|jpeg|gif|png|PNG',
			'upload_path' => $this->utils->getQuestThumbnails(),
			'max_size' => 500000,
			'overwrite' => true,
			'remove_spaces' => true,
		);
		return $config;
	}

	public function editQuestCategory() {
		$this->load->model('quest_category');

        if (!$this->permissions->checkPermissions('quest_category_edit')){
            $this->error_access();
            return;
        }

        $startAt = $this->input->post("editstartDate"). " ". $this->input->post("editstartTime");
        $endAt = $this->input->post("editendDate"). " ". $this->input->post("editendTime");

        if($endAt < $startAt){
            return $this->returnJsonResult(array('success' => false, 'noteType' => 'date'));
        }

		$questCategory = $this->input->post("editquestCategoryId");
        $questCategoryLen = strlen((int)$questCategory);
        $editquestCatecoryTitle = lang($this->input->post("editeditquestCatecoryTitle"));
        $questCategoryDescLen  = mb_strlen($this->input->post("editquestCategoryDesc"), "utf-8");

        if($questCategoryLen > Promo_type::PROMO_CATEGORY_ORDER_MAX_CHARACTERS){
			return $this->returnJsonResult(array('success' => false, 'noteType' => 'orderMaxChar'));
        }

		$editquestCatecoryTitleLen  = mb_strlen($editquestCatecoryTitle, "utf-8");
		if($editquestCatecoryTitleLen > Promo_type::PROMO_CATEGORY_NAME_MAX_CHARACTERS){
			return $this->returnJsonResult(array('success' => false, 'noteType' => 'nameLen'));
        }

        if($questCategoryDescLen > Promo_type::PROMO_CATEGORY_INTERNAL_REMARK_MAX_CHARACTERS){
			return $this->returnJsonResult(array('success' => false, 'noteType' => 'descLen'));
        }

		$iconPathName = $this->input->post('edit_userfile');
		$iconUrl = $this->input->post('icon_url');

		if(!empty($iconUrl) && strrpos($iconUrl, '.') > 0) {
			$fileType = substr($iconUrl, strrpos($iconUrl, '.') + 1);
		}
		
		$path_image = $_FILES['edit_userfile']['name'];
		$image = isset($_FILES['edit_userfile']) ? $_FILES['edit_userfile'] : null;

		if (!empty($path_image[0])) {

			$this->load->library('multiple_image_uploader');

			//we can use new upload library here
			$questIconName = 'questIcon-' . uniqid();

			$response = $this->multiple_image_uploader->do_multiple_uploads($image, $this->utils->getQuestThumbnails(), $this->getQuestUploadConfig($questIconName), $questIconName);
			if ($response['status'] == "fail") {
				$this->alertMessage(self::MESSAGE_TYPE_ERROR, $response['message']);
				redirect(BASEURL . 'marketing_management/quest_category');
			}
			$iconUrl = $questIconName . '.' . $fileType;
		}

		$questCategorydata = array(
			'questCategoryId' => $this->input->post("questCategoryId"),
            'sort' 			  => $this->input->post("editquestCategoryOrderId"),
			'title' 		  => $this->input->post("editquestCategoryTitle"),
			'description'	  => htmlspecialchars($this->input->post("editquestCategoryDesc")),
			'status' 		  => $this->input->post("editquestCategoryStatus"),
			'iconPath' 	      => $iconUrl,
			'updatedBy' 	  => $this->authentication->getUserId(),
            'showTimer'       => $this->input->post("editshowTimer"),
            'startAt'         => $startAt,
            'endAt'           => $endAt,
            'coverQuestTime'  => $this->input->post("editcoverQuestTime"),
            'period'          => ($this->input->post("editperiod")) ? $this->input->post("editperiod") : 0,
		);

		if (!empty($_FILES['edit_userfile'])) {
			# upload bank icon
            $this->load->model(['quest_category']);
			$upload_response = $this->quest_category->uploadQuestCategoryIcon($_FILES['edit_userfile']);

			if ($upload_response['status'] == 'success' && isset($upload_response['fileName'])) {
				if (!empty($upload_response['fileName'])) {
					$icon_file_name = $upload_response['fileName'];
				}
			}
		}

		$this->quest_category->editQuestCategory($questCategorydata);

		// $currency = strtoupper($this->utils->getActiveTargetDB());
		// $categoryId = $this->input->post("questCategoryId");
		// $this->quest_library->deleteQuestCategoryCache($categoryId, $currency);
		// $this->quest_library->deleteQuestManagerCache($categoryId, $currency);

		$this->saveAction(self::MANAGEMENT_TITLE, 'Edit Promo Type', "User " . $this->authentication->getUsername() . " has successfully edited quest category.");
		$this->returnJsonResult(array('success' => true, 'msg' => lang('Quest Category saved.')));
	}

	public function changeQuestStatus(){
		$this->load->model('quest_category');
        
		$questCategoryId = $this->input->post("questCategoryId");
		$status = $this->input->post("status");

        if($status == 0){
            if (!$this->permissions->checkPermissions('quest_category_enable')){
                $this->error_access();
                return;
            }
        }else{
            if (!$this->permissions->checkPermissions('quest_category_disable')){
                $this->error_access();
                return;
            }
        }
		$questData = [
			'questCategoryId' => $questCategoryId,
			'status' 		  => ($status == 0) ? 1 : 0,
			'updatedBy'       => $this->authentication->getUserId(),
		];
		$this->utils->debug_log('=========questData', $questData);
		$this->quest_category->editQuestCategory($questData);

        $currency = strtoupper($this->utils->getActiveTargetDB());
		$this->quest_library->deleteQuestCategoryCache($questCategoryId, $currency);
		$this->quest_library->deleteQuestManagerCache($questCategoryId, $currency);

		$this->saveAction(self::MANAGEMENT_TITLE, 'Change Quest Category Status', "User " . $this->authentication->getUsername() . " has successfully changed quest category status.");
		$this->returnJsonResult(array('success' => true, 'msg' => lang('Quest Category status changed.')));
	}

    public function setPlayerQuestState($questManagerId)
    {
        $this->utils->info_log('start setPlayerQuestState ', $questManagerId);

        $isBlocked = false;
        $db = $this->CI->db;
        $res = null;

        $dbName = !empty($db) ? $db->getOgTargetDB() : null;
        $fileList = [];

        $commandParams = [
            'questManagerId' => $questManagerId,
        ];

        $cmd = $this->utils->generateCommandLine('commandSetPlayerQuestState', $commandParams, $isBlocked, $fileList, $dbName);
        $this->utils->info_log('setPlayerQuestState cmd' . (empty($db) ? ' empty db' : ' db'), $cmd, $dbName);

        if (!empty($cmd)) {
            $res = $this->utils->runCmd($cmd);
        }

        $this->utils->info_log('end setPlayerQuestState ', $res);
        return $res;
    }

	public function deleteQuestCategory() {
		$this->load->model('quest_category');

        if (!$this->permissions->checkPermissions('quest_category_delete')){
            $this->error_access();
            return;
        }

		$questCategoryId = $this->input->post("questCategoryId");

        $checkQuestManager = $this->quest_category->checkQuestManager($questCategoryId);
        if($checkQuestManager){
            return $this->returnJsonResult(array('success' => false, 'msg' => 'Category is used in Quest Manager.'));
        }
		$questData = [
			'questCategoryId' => $questCategoryId,
			'deleted' 		  => 1,
			'updatedBy'       => $this->authentication->getUserId(),
		];
		$this->quest_category->editQuestCategory($questData);

        $currency = strtoupper($this->utils->getActiveTargetDB());
		$this->quest_library->deleteQuestCategoryCache($questCategoryId, $currency);
		$this->quest_library->deleteQuestManagerCache($questCategoryId, $currency);

		$this->saveAction(self::MANAGEMENT_TITLE, 'Delete Quest Category', "User " . $this->authentication->getUsername() . " has successfully deleted quest category.");
		$this->returnJsonResult(array('success' => true, 'msg' => lang('Quest Category deleted.')));
	}

	/**
	 * overview : quest manager
	 *
	 * detail : view page for quest manager setting list
	 *
	 * @return  redered template
	*/

    public function quest_manager() {
        $this->loadTemplate(lang('cms.questManagerSetting'), '', '', 'marketing');
        $this->template->write_view('sidebar', 'marketing_management/sidebar');

        if (!$this->permissions->checkPermissions('quest_manager_setting')){
            $this->error_access();
            return;
        }

        if (!$this->permissions->checkPermissions('export_report')) {
            $data['export_report_permission'] = FALSE;
        } else {
            $data['export_report_permission'] = TRUE;
        }

        $this->load->model(array('quest_manager', 'quest_category'));

        $data['questManager'] = $this->quest_manager->getQuestManager();
        $data['questCategory'] = $this->quest_manager->getQuestCategory();

        $quest_setting = $this->utils->getConfig('quest_setting');
        $data['questDisplayPanel'] = $this->utils->safeGetArray($quest_setting, 'quest_display_panel', []);
        $data['singleConditionType'] = $this->utils->safeGetArray($quest_setting, 'single_condition_type', []);
        $data['multipleConditionType'] = $this->utils->safeGetArray($quest_setting, 'multiple_condition_type', []);
        $data['ladderQuestLimit'] = $this->utils->safeGetArray($quest_setting, 'ladder_quest_limit', 10);
        $data['questManagerContent'] = $this->utils->safeGetArray($quest_setting, 'quest_manager_content', '');

        $this->template->write_view('main_content', 'marketing_management/quest/quest_manager', $data);
        $this->template->render();
    }

    public function addQuestManager(){
        $this->utils->debug_log('=========addQuestManager', $this->input->post());
        
        $this->load->model(array('quest_manager', 'quest_category'));
        $icon_file_name = null;
        $banner_file_name = null;

        if (!$this->permissions->checkPermissions('quest_manager_add')){
            $this->error_access();
            return;
        }
        
        $levelType = $this->input->post("levelType");

        if($levelType == 2){
            $startAt = $this->input->post("startDate"). " ". $this->input->post("startTime");
            $endAt = $this->input->post("endDate"). " ". $this->input->post("endTime");
            if($endAt < $startAt){
                return $this->returnJsonResult(array('success' => false, 'noteType' => 'date'));
            }
        }

        if (!empty($_FILES['uesrfile'])) {
            # upload bank icon
            $upload_response = $this->quest_category->uploadQuestCategoryIcon($_FILES['uesrfile']);

            if ($upload_response['status'] == 'success' && isset($upload_response['fileName'])) {
                if (!empty($upload_response['fileName'])) {
                    $icon_file_name = $upload_response['fileName'];
                }
            }
        }

        if (!empty($_FILES['uesrfile_banner'])) {
            # upload bank icon
            $upload_response = $this->quest_category->uploadQuestCategoryIcon($_FILES['uesrfile_banner']);

            if ($upload_response['status'] == 'success' && isset($upload_response['fileName'])) {
                if (!empty($upload_response['fileName'])) {
                    $banner_file_name = $upload_response['fileName'];
                }
            }
        }
        
        $questConditionType = $this->input->post("questConditionType");
        foreach($questConditionType as $key => $value){
            $questRuleData = array(
                "questConditionType" => $value,
                "questConditionValue" => $this->input->post("questConditionValue")[$key],
                "bonusConditionType" => $this->input->post("bonusConditionType")[$key],
                "bonusConditionValue" => $this->input->post("bonusConditionValue")[$key],
                "withdrawalConditionType" => $this->input->post("withdrawalConditionType")[$key],
                "withdrawReqBetAmount" => ($this->input->post("withdrawalConditionType")[$key] == 1) ? $this->input->post("withdrawalValue")[$key] : 0,
                "withdrawReqBettingTimes" => ($this->input->post("withdrawalConditionType")[$key] == 2) ? $this->input->post("withdrawalValue")[$key] : 0,
                "withdrawReqBonusTimes" => ($this->input->post("withdrawalConditionType")[$key] == 3) ? $this->input->post("withdrawalValue")[$key] : 0,
                "personalInfoType" => ($value == 7) ? $this->input->post("personalInfoType") : 0,
                "communityOptions" => ($value == 10) ? $this->input->post("communityOptions") : 0,
            );
            $this->utils->debug_log('=========questRuleData'.$key, $questRuleData);
            $res = $this->quest_manager->addQuestRule($questRuleData);
            $questRuleId[] = $res;
        }

        // image upload
        $iconUrl = $this->input->post('icon_url');
        $bannerUrl = $this->input->post('banner_url');

        if(!empty($iconUrl) && strrpos($iconUrl, '.') > 0) {
            $fileTypeIcon = substr($iconUrl, strrpos($iconUrl, '.') + 1);
        }
        if(!empty($bannerUrl) && strrpos($bannerUrl, '.') > 0) {
            $fileTypeBanner = substr($bannerUrl, strrpos($bannerUrl, '.') + 1);
        }
        
        $path_image_icon = $_FILES['userfile']['name'];
        $image_icon = isset($_FILES['userfile']) ? $_FILES['userfile'] : null;

        if (!empty($path_image_icon[0])) {

            $this->load->library('multiple_image_uploader');

            //we can use new upload library here
            $questIconName = 'questManagerIcon-' . uniqid();

            $response = $this->multiple_image_uploader->do_multiple_uploads($image_icon, $this->utils->getQuestThumbnails(), $this->getQuestUploadConfig($questIconName), $questIconName);
            if ($response['status'] == "fail") {
                $this->alertMessage(self::MESSAGE_TYPE_ERROR, $response['message']);
                redirect(BASEURL . 'marketing_management/quest_manager');
            }
            $iconUrl = $questIconName . '.' . $fileTypeIcon;
        }

        $path_image_banner = $_FILES['userfile_banner']['name'];
        $image_banner = isset($_FILES['userfile_banner']) ? $_FILES['userfile_banner'] : null;

        if (!empty($path_image_banner[0])) {

            $this->load->library('multiple_image_uploader');

            //we can use new upload library here
            $questBannerName = 'questManagerBanner-' . uniqid();

            $response = $this->multiple_image_uploader->do_multiple_uploads($image_banner, $this->utils->getQuestThumbnails(), $this->getQuestUploadConfig($questBannerName), $questBannerName);
            if ($response['status'] == "fail") {
                $this->alertMessage(self::MESSAGE_TYPE_ERROR, $response['message']);
                redirect(BASEURL . 'marketing_management/quest_manager');
            }
            $bannerUrl = $questBannerName . '.' . $fileTypeBanner;
        }
        // end image upload

        $questManagerData = array(
            "levelType" => $levelType,
            "questCategoryId" => $this->input->post("questCategoryId"),			
            "questManagerType" => $this->input->post("questManagerType"),
            "period" => ($this->input->post("period")) ? $this->input->post("period") : 0,
            "claimOtherUrl" => "",
            "iconPath" => $iconUrl,
            "bannerPath" => $bannerUrl,
            "description" => htmlspecialchars($this->input->post("questManagerDesc")),
            "title" => ($levelType == 1) ? $this->input->post("questTitle")[0] : 0,
            "questRuleId" => ($levelType == 1) ? $questRuleId[0] : 0,
            "createdBy" => $this->authentication->getUserId(),
            "updatedBy" => $this->authentication->getUserId(),
            "status"    => 1,
            "auto_tick_new_game_in_cashback_tree" => $this->input->post("auto_tick_new_games_in_game_type")=='true' ? '1' : '0'
        );

        if($levelType == 2){
            $questManagerData["displayPanel"] = ($this->input->post("displayPanel")) ? $this->input->post("displayPanel") : 0;
            $questManagerData["allowSameIPBonusReceipt"] = ($this->input->post("allowSameIpReceive")) ? $this->input->post("allowSameIpReceive") : 0;
            $questManagerData["showOneClick"] = ($this->input->post("showOneClick")) ? $this->input->post("showOneClick") : 0;
            $questManagerData["showTimer"] = ($this->input->post("showTimer")) ? $this->input->post("showTimer") : 0;
            $questManagerData["startAt"] = $startAt;
            $questManagerData["endAt"] = $endAt;
        }
        
        $this->utils->debug_log('=========questManagerData', $questManagerData);
        $managerId = $this->quest_manager->addQuestManager($questManagerData);
        $this->utils->debug_log('=========managerId', $managerId);

        // add game type
        $showGameTree = $this->config->item('show_particular_game_in_tree');
		// $this->load->model('game_description_model');
		// $promoType = $this->input->post("promoType");
		// $nonDepositOption = $this->input->post("nonDepositOption");

		$gamesAptList = $this->loadSubmitGameTreeWithNumber($showGameTree);
		if (!empty($gamesAptList)) {
			$this->quest_manager->batchAddAllowedGames($managerId, $gamesAptList);
		}
        // end add game type


        if($levelType == 2){
            foreach($questRuleId as $key => $value){
                $questJobData = array(
                    "questManagerId" => $managerId,
                    "questRuleId" => $value,
                    "title" => $this->input->post("questTitle")[$key],
                );
                $this->quest_manager->addQuestJob($questJobData);
            }
        }
        
        $this->saveAction(self::MANAGEMENT_TITLE, 'Added Quest Manager', "User " . $this->authentication->getUsername() . " has successfully added quest manager.");
        $this->returnJsonResult(array('success' => true, 'msg' => lang('Quest Manager saved.')));
    }

    public function editQuestManager(){
        $this->utils->debug_log('=========editQuestManager', $this->input->post());

        $this->load->model(array('quest_manager', 'quest_category'));
        $icon_file_name = null;
        $banner_file_name = null;
        $levelType = $this->input->post("editlevelType");

        if($levelType == 2){
            $startAt = $this->input->post("editstartDate"). " ". $this->input->post("editstartTime");
            $endAt = $this->input->post("editendDate"). " ". $this->input->post("editendTime");

            if($endAt < $startAt){
                return $this->returnJsonResult(array('success' => false, 'noteType' => 'date'));
            }
        }

        if (!empty($_FILES['edit_uesrfile'])) {
            # upload bank icon
            $upload_response = $this->quest_category->uploadQuestCategoryIcon($_FILES['edit_uesrfile']);

            if ($upload_response['status'] == 'success' && isset($upload_response['fileName'])) {
                if (!empty($upload_response['fileName'])) {
                    $icon_file_name = $upload_response['fileName'];
                }
            }
        }

        if (!empty($_FILES['edit_uesrfile_banner'])) {
            # upload bank icon
            $upload_response = $this->quest_category->uploadQuestCategoryIcon($_FILES['edit_uesrfile_banner']);

            if ($upload_response['status'] == 'success' && isset($upload_response['fileName'])) {
                if (!empty($upload_response['fileName'])) {
                    $banner_file_name = $upload_response['fileName'];
                }
            }
        }
        
        $questConditionType = $this->input->post("editquestConditionType");
        foreach($questConditionType as $key => $value){
            $questRuleData = array(
                "questConditionType" => $value,
                "questConditionValue" => $this->input->post("editquestConditionValue")[$key],
                "bonusConditionType" => $this->input->post("editbonusConditionType")[$key],
                "bonusConditionValue" => $this->input->post("editbonusConditionValue")[$key],
                "withdrawalConditionType" => $this->input->post("editwithdrawalConditionType")[$key],
                "withdrawReqBetAmount" => ($this->input->post("editwithdrawalConditionType")[$key] == 1) ? $this->input->post("editwithdrawalValue")[$key] : 0,
                "withdrawReqBettingTimes" => ($this->input->post("editwithdrawalConditionType")[$key] == 2) ? $this->input->post("editwithdrawalValue")[$key] : 0,
                "withdrawReqBonusTimes" => ($this->input->post("editwithdrawalConditionType")[$key] == 3) ? $this->input->post("editwithdrawalValue")[$key] : 0,
                "personalInfoType" => ($value == 7) ? $this->input->post("editpersonalInfoType") : 0,
                "communityOptions" => ($value == 10) ? $this->input->post("editcommunityOptions") : 0,
            );
            $this->utils->debug_log('=========questRuleData'.$key, $questRuleData);
            if($this->input->post("editquestRuleId")[$key]){
                $res = $this->quest_manager->editQuestRule($questRuleData, $this->input->post("editquestRuleId")[$key]);
                $questRuleJobId[] = [$this->input->post("editquestRuleId")[$key] ,$this->input->post("editquestJobId")[$key]];
            }else{
                $res = $this->quest_manager->addQuestRule($questRuleData);
                $questRuleJobId[] = [$res ,$this->input->post("editquestJobId")[$key]];
            }
        }
        $this->utils->debug_log('=========questRuleJobId', $questRuleJobId);

        $delRule = explode(',', $this->input->post("deleteRuleId"));
        foreach($delRule as $value){
            $this->quest_manager->editQuestRule(['status' => 0], $value);
        }
        // image upload
        // icon
        $iconUrl = $this->input->post('edit_icon_url');

        if(!empty($iconUrl) && strrpos($iconUrl, '.') > 0) {
            $fileTypeIcon = substr($iconUrl, strrpos($iconUrl, '.') + 1);
        }
        
        $path_image_icon = $_FILES['edit_userfile']['name'];
        $image_icon = isset($_FILES['edit_userfile']) ? $_FILES['edit_userfile'] : null;

        if (!empty($path_image_icon[0])) {

            $this->load->library('multiple_image_uploader');

            //we can use new upload library here
            $questIconName = 'questManagerIcon-' . uniqid();

            $response = $this->multiple_image_uploader->do_multiple_uploads($image_icon, $this->utils->getQuestThumbnails(), $this->getQuestUploadConfig($questIconName), $questIconName);
            if ($response['status'] == "fail") {
                $this->alertMessage(self::MESSAGE_TYPE_ERROR, $response['message']);
                redirect(BASEURL . 'marketing_management/quest_manager');
            }
            $iconUrl = $questIconName . '.' . $fileTypeIcon;
        }

        // banner
        $bannerUrl = $this->input->post('edit_banner_url');
        $path_image_banner = $_FILES['edit_userfile_banner']['name'];
        $image_banner = isset($_FILES['edit_userfile_banner']) ? $_FILES['edit_userfile_banner'] : null;

        if(!empty($bannerUrl) && strrpos($bannerUrl, '.') > 0) {
            $fileTypeBanner = substr($bannerUrl, strrpos($bannerUrl, '.') + 1);
        }

        if (!empty($path_image_banner[0])) {

            $this->load->library('multiple_image_uploader');

            //we can use new upload library here
            $questBannerName = 'questManagerBanner-' . uniqid();

            $response = $this->multiple_image_uploader->do_multiple_uploads($image_banner, $this->utils->getQuestThumbnails(), $this->getQuestUploadConfig($questBannerName), $questBannerName);
            if ($response['status'] == "fail") {
                $this->alertMessage(self::MESSAGE_TYPE_ERROR, $response['message']);
                redirect(BASEURL . 'marketing_management/quest_manager');
            }
            $bannerUrl = $questBannerName . '.' . $fileTypeBanner;
        }
        // end image upload

        $questManagerData = array(
            "levelType" => $levelType,
            "questCategoryId" => $this->input->post("editquestCategoryId"),			
            "questManagerType" => $this->input->post("editquestManagerType"),
            "period" => ($this->input->post("editperiod")) ? $this->input->post("editperiod") : 0,
            "claimOtherUrl" => "",
            "iconPath" => $iconUrl,
            "bannerPath" => $bannerUrl,
            "description" => htmlspecialchars($this->input->post("editquestManagerDesc")),
            "title" => ($levelType == 1) ? $this->input->post("editquestTitle")[0] : 0,
            "questRuleId" => ($levelType == 1) ? $questRuleJobId[0][0] : 0,
            "createdBy" => $this->authentication->getUserId(),
            "updatedBy" => $this->authentication->getUserId(),
            "auto_tick_new_game_in_cashback_tree" => $this->input->post("edit_auto_tick_new_games_in_game_type")=='true' ? '1' : '0'
        );

        if($levelType == 2){
            $questManagerData["displayPanel"] = ($this->input->post("editdisplayPanel")) ? $this->input->post("editdisplayPanel") : 0;
            $questManagerData["allowSameIPBonusReceipt"] = ($this->input->post("editallowSameIpReceive")) ? $this->input->post("editallowSameIpReceive") : 0;
            $questManagerData["showOneClick"] = ($this->input->post("editshowOneClick")) ? $this->input->post("editshowOneClick") : 0;
            $questManagerData["showTimer"] = ($this->input->post("editshowTimer")) ? $this->input->post("editshowTimer") : 0;
            $questManagerData["startAt"] = $startAt;
            $questManagerData["endAt"] = $endAt;
        }
        
        $this->utils->debug_log('=========questManagerData', $questManagerData);
        $this->quest_manager->editQuestManager($questManagerData, $this->input->post("editquestManagerId"));

        // edit game type
        $showGameTree = $this->config->item('show_particular_game_in_tree');
		// $this->load->model('game_description_model');
		// $promoType = $this->input->post("promoType");
		// $nonDepositOption = $this->input->post("nonDepositOption");

		$gamesAptList = $this->loadSubmitGameTreeWithNumber($showGameTree);
		if (!empty($gamesAptList)) {
			$this->quest_manager->batchAddAllowedGames($this->input->post("editquestManagerId"), $gamesAptList);
		}
        // end edit game type


        if($levelType == 2){
            foreach($questRuleJobId as $key => $value){
                $questJobData = array(
                    "questManagerId" => $this->input->post("editquestManagerId"),
                    "questRuleId" => $value[0],
                    "title" => $this->input->post("editquestTitle")[$key],
                );

                if($value[1]){
                    $this->quest_manager->editQuestJob($questJobData, $value[1]);
                }else{
                    $this->quest_manager->addQuestJob($questJobData);
                }
            }
        }

        $this->setPlayerQuestState($this->input->post("editquestManagerId"));

        $categroyId = $this->quest_manager->getQuestManagerDetailsById($this->input->post("editquestManagerId"))['questCategoryId'];
        $this->quest_library->deleteQuestManagerCache($categroyId, strtoupper($this->utils->getActiveTargetDB()));

        $this->saveAction(self::MANAGEMENT_TITLE, 'Edit Quest Manager', "User " . $this->authentication->getUsername() . " has successfully edit quest manager.");
        $this->returnJsonResult(array('success' => true, 'msg' => lang('Quest Manager saved.')));
    }

	public function getQuestManagerDetails($questManagerId) {
		$questManagerData = $this->quest_manager->getQuestManagerDetails($questManagerId);
		if($questManagerData[0]['questRuleId'] != 0){
			$questRuleData = $this->quest_manager->getQuestRuleDetails($questManagerData[0]['questRuleId']);
            $questRuleData = $this->checkSingleQuestJobUsed($questRuleData, $questManagerId);
		}else{
			$questRuleData = $this->quest_manager->getQuestRuleDetailsWithJob($questManagerId);
            $questRuleData = $this->checkQuestJobUsed($questRuleData, $questManagerId);
		}
		$questManagerData[0]['icon_path'] = $this->utils->getQuestCategoryIcon($questManagerData[0]['iconPath']);
		$questManagerData[0]['banner_path'] = $this->utils->getQuestCategoryIcon($questManagerData[0]['bannerPath']);
		$questManagerData[0]['startDate'] = date("Y-m-d", strtotime($questManagerData[0]['startAt']));
		$questManagerData[0]['startTime'] = date("H:i:s", strtotime($questManagerData[0]['startAt']));	
		$questManagerData[0]['endDate'] = date("Y-m-d", strtotime($questManagerData[0]['endAt']));
		$questManagerData[0]['endTime'] = date("H:i:s", strtotime($questManagerData[0]['endAt']));

		$questManagerData[1] = $questRuleData;
		$this->returnJsonResult($questManagerData);
	}

    public function deleteQuestManager(){
		$this->load->model('quest_manager');
		$questManagerId = $this->input->post("questManagerId");
        $questCategoryId = $this->input->post("questCategoryId");
		$questData = [
			'questManagerId' => $questManagerId,
			'deleted' 		  => 1,
			'updatedBy'       => $this->authentication->getUserId(),
		];
		$this->quest_manager->editQuestManager($questData, $questManagerId);

        $this->quest_library->deleteQuestManagerCache($questCategoryId, strtoupper($this->utils->getActiveTargetDB()));
		$this->saveAction(self::MANAGEMENT_TITLE, 'Delete Quest Manager', "User " . $this->authentication->getUsername() . " has successfully deleted quest manager.");
		$this->returnJsonResult(array('success' => true, 'msg' => lang('Quest Manager deleted.')));
	}

    public function checkQuestJobUsed($questRuleData, $questManagerId){
        $this->load->model('quest_manager');

        $maxQuestJobId = $this->quest_manager->getMaxQuestJobId($questManagerId);

        $this->utils->debug_log('=========maxQuestJobId', $maxQuestJobId);

        foreach($questRuleData as $index => $value){
            if($value['questJobId'] > $maxQuestJobId){
                $questRuleData[$index]['isApply'] = '0';
            }else{
                $questRuleData[$index]['isApply'] = '1';
            }
        }

        return $questRuleData;
    }

    public function checkSingleQuestJobUsed($questRuleData, $questManagerId){
        $this->load->model('quest_manager');

        $res = $this->quest_manager->existsSinglePlayerQuestJobState($questManagerId);
        $this->utils->debug_log('=========res', $res);

        if ($res) {
            $questRuleData[0]['isApply'] = '1';
        } else {
            $questRuleData[0]['isApply'] = '0';
        }

        return $questRuleData;
    }

	public function changeQuestManagerStatus(){
		$this->load->model('quest_manager');
		$questManagerId = $this->input->post("questManagerId");
        $questCategoryId = $this->input->post("questCategoryId");
		$status = $this->input->post("status");
		$questData = [
			'questManagerId' => $questManagerId,
			'status' 		 => ($status == 0) ? 1 : 0,//0:inactive, 1:active
			'updatedBy'      => $this->authentication->getUserId(),
		];

        $this->startTrans();
        $this->utils->debug_log('=========questData', $questData);
		$result = $this->quest_manager->editQuestManager($questData, $questManagerId);
        $successManager = $this->endTransWithSucc() && $result;
        $this->utils->debug_log(__METHOD__, 'successManager', $successManager, $result);

        $this->quest_library->deleteQuestManagerCache($questCategoryId, strtoupper($this->utils->getActiveTargetDB()));
		$this->saveAction(self::MANAGEMENT_TITLE, 'Change Quest Manager Status', "User " . $this->authentication->getUsername() . " has successfully changed quest manager status.");

        if ($questData['status'] == 1 && $successManager) {
            $token = $this->updatePlayerQuestRewardStatusByQueue($questCategoryId, $questManagerId);
            if ($token) {
                $result = [
                    'success' => true,
                    'token' => $token,
                    'questCategoryId' => $questCategoryId,
                    'questManagerId' => $questManagerId,
                    'successMsg' => lang('Quest Manager status changed.'),
                    'redriectGenerateProgress' => '/system_management/common_queue/' . $token,
                    'isJob' => true,
                ];
                return $this->returnCommon(BaseController::MESSAGE_TYPE_SUCCESS, $result['successMsg'], $result, '/marketing_management/quest_manager');
            } else {
                $result = [
                    'success' => false,
                    'noteType' => 'job',
                    'errorMsg' => lang('Create job failed'),
                    'isJob' => true,
                ];
                return $this->returnCommon(BaseController::MESSAGE_TYPE_ERROR, $result['errorMsg'], $result, '/marketing_management/quest_manager');
            }
        } else {
            $result = [
                'success' => true,
                'errorMsg' => lang('Quest Manager status deactive.'),
                'isJob' => false,
            ];
            return $this->returnCommon(BaseController::MESSAGE_TYPE_SUCCESS, $result['errorMsg'], $result, '/marketing_management/quest_manager');
        }
	}

    public function updatePlayerQuestRewardStatusByQueue($categoryId, $managerId)
	{
		$this->load->library(['lib_queue', 'language_function', 'authentication']);
		$this->load->model(['queue_result']);
		$caller = $this->authentication->getUserId();
		$operator = $this->authentication->getUsername();
		$state = null;
		$callerType = Queue_result::CALLER_TYPE_ADMIN;
		$params = [
            'categoryId' => $categoryId,
			'managerId' => $managerId,
			'operator' => $operator,
		];

		$token = $this->lib_queue->addUpdatePlayerQuestRewardStatus($params, $callerType, $caller, $state);

		return $token;
	}
}
////END OF FILE/////////
