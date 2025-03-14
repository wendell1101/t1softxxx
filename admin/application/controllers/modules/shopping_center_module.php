<?php

/**
 * Class shopping_center_module
 *
 * General behaviors include
 *
 * * Add/update/delete shopping center
 * * Activate/deactivate shopping center
 * * Export report to excel
 *
 * @category Marketing Management
 * @version 1.8.10
 * @copyright 2013-2022 tot
 *
 */
trait shopping_center_module {
	/**
	 * overview : shopping center items list
	 *
	 * @return  redered template
	 */
	public function shoppingCenterItemList() {
		if (!$this->permissions->checkPermissions('shopping_center_manager')) {
			$this->error_access();
		} else {
			$this->load->model(array('shopping_center', 'shopper_list'));

			$data['shoppingItemList'] = $this->shopping_center->getData();
			
			foreach ($data['shoppingItemList'] as $key => $value) {
				$usedItem = count($this->shopper_list->getShopperList($value['id'], Shopper_list::APPROVED));
				$totalItem = $value['how_many_available'];
				$available  = (float) $totalItem - (float) $usedItem;
				$data['shoppingItemList'][$key]['how_many_available'] = $available . " " . lang("left out of") . " " . $totalItem;
			}
			
			//export report permission checking
			if (!$this->permissions->checkPermissions('export_report')) {
				$data['export_report_permission'] = FALSE;
			} else {
				$data['export_report_permission'] = TRUE;
			}

			$this->loadTemplate('Marketing Management', '', '', 'shopping center');
			$this->template->write_view('sidebar', 'marketing_management/sidebar');
			$this->template->add_css('resources/css/shopping_center/shopping_center.css');
			$this->template->write_view('main_content', 'shopping_center/shopping_center_list', $data);
			$this->template->render();
		}
	}

	/**
	 * add new shopping
	 *
	 * @return  rendered template
	 */
	public function addNewshoppingItem() {
		$this->form_validation->set_rules('title', 'Title', 'trim|required|xss_clean');
		$this->form_validation->set_rules('short_description', 'Short Description', 'trim|required|xss_clean');
		$this->form_validation->set_rules('item_details', 'Details', 'trim|required');
		$this->form_validation->set_rules('how_many_available', 'How Many Available', 'trim|required');
		$this->form_validation->set_rules('item_order', 'Item Order', 'trim');
		$this->load->library('Multiple_image_uploader');
		if ($this->form_validation->run() == false) {
			$message = lang('Please make sure required items are not blank');
			$this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);
		} else {

			$title = $this->input->post('title');
			$shortDescription = $this->input->post('short_description');
			$requiredPoints = $this->input->post('required_points');
			$howManyAvailable = $this->input->post('how_many_available');
			$itemOrder = $this->input->post('item_order');
			$details = $this->input->post('item_details');
			$today = $this->utils->getNowForMysql();
			$bannerImgName = $this->input->post('userfile');
			$bannerURL = $this->input->post('banner_url');
			$is_default_banner_flag = $this->input->post('is_default_banner_flag');
			$shoppingCenterItemId = $this->input->post('itemId');
			$hideOnPlayerCenter = $this->input->post('hideOnPlayerCenter') ? 1 : 0;

			//new feature
			$tagAsNewFlag = $this->input->post('tagAsNewFlag');
			$fileType = substr($bannerURL, strrpos($bannerURL, '.') + 1);
			$path_image = $_FILES['userfile']['name'];
			$image = isset($_FILES['userfile']) ? $_FILES['userfile'] : null;
			//$ext = pathinfo($path_image, PATHINFO_EXTENSION);

			/*if (!empty($path_image) && strcasecmp($ext, 'jpg') != 0 && strcasecmp($ext, 'jpeg') != 0 && strcasecmp($ext, 'gif') != 0 && strcasecmp($ext, 'png') != 0) {
				$message = lang('con.aff46');
				$this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);
				redirect('marketing_management/shoppingCenterItemList');
			}*/

			$this->load->model('shopping_center');

			if ($shoppingCenterItemId) {
				$data = array(
					'id' => $shoppingCenterItemId,
					'title' => $title,
					'short_description' => $shortDescription,
					'how_many_available' => $howManyAvailable,
					'item_order' => $itemOrder,
					'details' => $details ?: '',
					'requirements' => json_encode(array("required_points" => $requiredPoints)),
					'updated_by' => $this->authentication->getUserId(),
					'updated_at' => $today,
					'tag_as_new' => $tagAsNewFlag == "on" ? true : false,
					'is_default_banner_flag' => $is_default_banner_flag == "true" ? 1 : 0,
					'status' => Shopping_center::STATUS_NORMAL,
					'hide_it_on_player_center' => $hideOnPlayerCenter,
				);
				$rawPost = $this->input->post('is_default_banner_flag');
				// var_dump($data, $rawPost);exit();

				if ($is_default_banner_flag) {
					$data['banner_url'] = $bannerURL;
				}

				if (!empty($path_image[0])) {
					$bannerImgName = 'shopping-item-' . uniqid();

					$config = array(
						'allowed_types' => 'jpg|jpeg|gif|png|PNG',
						'upload_path' => $this->utils->getShopThumbnailsPath(),
						'max_size' => 100000,
						'overwrite' => true,
						'file_name' => $bannerImgName,
					);

					$response = $this->multiple_image_uploader->do_multiple_uploads($image, $this->utils->getShopThumbnailsPath(), $config, $bannerImgName);
					if ($response['status'] == "fail") {
						$this->alertMessage(self::MESSAGE_TYPE_ERROR, $response['message']);
						redirect(BASEURL . 'marketing_management/shoppingCenterItemList');
					}

					$data['banner_url'] = $bannerImgName. '.' . $fileType;
				}

				$this->shopping_center->updateItemData($data);
				$message = " <b>" . $title . "</b> " . lang('con.cms05');

				$data = array(
					'username' => $this->authentication->getUsername(),
					'management' => 'Marketing Management',
					'userRole' => $this->rolesfunctions->getRoleByUserId($this->authentication->getUserId())['roleName'],
					'action' => 'Edit Shopping Center Item: ' . $shoppingCenterItemId,
					'description' => "User " . $this->authentication->getUsername() . " edit shoppping center item id: " . $shoppingCenterItemId,
					'logDate' => $today,
					'status' => self::FALSE,
				);
				$this->report_functions->recordAction($data);
			} else {

				$data = array(
					'title' => $title,
					'short_description' => $shortDescription,
					'how_many_available' => $howManyAvailable,
					'item_order' => $itemOrder,
					'requirements' => json_encode(array("required_points" => $requiredPoints)),
					'details' => $details,
					'tag_as_new' => $tagAsNewFlag,
					'created_by' => $this->authentication->getUserId(),
					'created_at' => $today,
					'status' => self::TRUE,
					'is_default_banner_flag' => $is_default_banner_flag == "true" ? 1 : 0,
					'hide_it_on_player_center' => $hideOnPlayerCenter,
				);

				// var_dump($data);exit();

				if (!empty($path_image[0])) {
					// upload image
					$bannerImgName = 'shopping-item-' . uniqid();
					$config = array(
						'allowed_types' => 'jpg|jpeg|gif|png|PNG',
						'upload_path' => $this->utils->getShopThumbnailsPath(),
						'max_size' => 100000,
						'overwrite' => true,
						'file_name' => $bannerImgName,
					);

					$response = $this->multiple_image_uploader->do_multiple_uploads($image, $this->utils->getShopThumbnailsPath(), $config, $bannerImgName);
					if ($response['status'] == "fail") {
						$this->alertMessage(self::MESSAGE_TYPE_ERROR, $response['message']);
						redirect(BASEURL . 'marketing_management/shoppingCenterItemList');
					}
					$data['banner_url'] = $bannerImgName . '.' . $fileType;
				} else {
					//will save the preset banner choosen
					$data['banner_url'] = $bannerURL;
				}

				//record action
				$this->saveAction('Add Shopping Center Item', "User " . $this->authentication->getUsername() . " has successfully added shopping center item: " . $title);
				$this->shopping_center->insertShoppingCenterItem($data);

				$message = "<b>" . $title . "</b> " . lang('con.cms07');
			}
			$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, $message);
		}
		redirect(BASEURL . 'marketing_management/shoppingCenterItemList');
	}

	public function getShoppingItemDetails($itemId) {
		$this->load->model('shopping_center');
		// echo json_encode($this->shopping_center->getItemDetails($itemId));
		$itemDetails = $this->shopping_center->getItemDetails($itemId);
		if (!$itemDetails['is_default_banner_flag']) {
			if ($this->utils->isEnabledMDB()) {
				$active_db = $this->utils->getActiveTargetDB();
				$itemDetails['img_path'] = base_url().'upload/'.$active_db.'/shopthumbnails/';
			} else {
				$itemDetails['img_path'] = base_url().'upload/shopthumbnails/';
			}
		}
        echo json_encode($itemDetails);
	}

	public function getShoppingItemDetailsWithPlayerId($itemId, $playerId) {
		$this->load->model(array('shopping_center', 'shopper_list'));
		$isPlayerItemReqExists = $this->shopper_list->isplayerItemRequestExists($itemId, $playerId);
		$res = $this->shopping_center->getItemDetails($itemId);
		if ($res['is_default_banner_flag']) {
            $res['banner_url'] = $this->utils->imageUrl('shopping_banner/' . $res['banner_url']);
        } else {
            if ($this->utils->isEnabledMDB()) {
                $activeDB = $this->utils->getActiveTargetDB();
                $res['banner_url'] = base_url().'upload/'.$activeDB.'/shopthumbnails/'.$res['banner_url'];
            } else {
                $res['banner_url'] = base_url().'upload/shopthumbnails/'.$res['banner_url'];
            }
        }

		$res['is_player_item_req_exists'] = $isPlayerItemReqExists ? true : false;
		echo json_encode($res);
	}

	/**
	 * activate shopping item
	 *
	 * @param   id
	 * @param   status
	 * @return  redirect
	 */
	public function activateShoppingItem($id, $status) {
		$this->load->model('shopping_center');
		$today = $this->utils->getNowForMysql();
		$data = array(
			'updated_by' => $this->authentication->getUserId(),
			'updated_at' => $this->utils->getNowForMysql(),
			'status' => $status == 'active' ? 1 : 0,
			'id' => $id,
		);

		$this->load->model('shopping_center');
		$this->shopping_center->activateShoppingCenterItem($data);

		$data = array(
			'username' => $this->authentication->getUsername(),
			'management' => 'Marketing Management',
			'userRole' => $this->rolesfunctions->getRoleByUserId($this->authentication->getUserId())['roleName'],
			'action' => 'Update status of shopping item: ' . $id . ' to status: ' . $status,
			'description' => "User " . $this->authentication->getUsername() . " edit shopping item id: " . $id . ' to status: ' . $status,
			'logDate' => $today,
			'status' => self::FALSE,
		);

		$this->report_functions->recordAction($data);
		redirect(BASEURL . 'marketing_management/shoppingCenterItemList');
	}

	/**
	 * Delete selected shopping item
	 *
	 * @param   int
	 * @return  redirect
	 */
	public function deleteSelectedShoppingItem() {
		$this->load->model('shopping_center');
		$items = $this->input->post('items');
		if ($items) {
			foreach ($items as $itemsId) {
				$this->shopping_center->deleteItem($itemsId);

				$data = array(
					'username' => $this->authentication->getUsername(),
					'management' => 'Marketing Management',
					'userRole' => $this->rolesfunctions->getRoleByUserId($this->authentication->getUserId())['roleName'],
					'action' => 'Delete shopping item id:' . $itemsId,
					'description' => "User " . $this->authentication->getUsername() . " deleted shopping item id: " . $itemsId,
					'logDate' => $this->utils->getNowForMysql(),
					'status' => self::FALSE,
				);

				$this->report_functions->recordAction($data);
			}

			$message = lang('Successfully Deleted!');
			$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, $message); //will set and send message to the user
		} else {
			$message = lang('You must select item first!');
			$this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);
		}

		redirect(BASEURL . 'marketing_management/shoppingCenterItemList');
	}

	/**
	 * Delete shopping item
	 *
	 * @param   int
	 * @return  redirect
	 */
	public function deleteShoppingItem($id) {
		$this->load->model('shopping_center');
		$this->shopping_center->deleteItem($id);

		$data = array(
			'username' => $this->authentication->getUsername(),
			'management' => 'Marketing Management',
			'userRole' => $this->rolesfunctions->getRoleByUserId($this->authentication->getUserId())['roleName'],
			'action' => 'Delete shopping item item id:' . $id,
			'description' => "User " . $this->authentication->getUsername() . " deleted shopping item id: " . $id,
			'logDate' => $this->utils->getNowForMysql(),
			'status' => self::FALSE,
		);

		$this->report_functions->recordAction($data);

		$message = lang('Successfully Deleted!');
		$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, $message);
		redirect(BASEURL . 'marketing_management/shoppingCenterItemList');
	}

	public function claimShoppingItem($itemId, $playerId) {
		// OGP-25463
		$enable_auto_approve_shop_items = $this->utils->getConfig('enable_auto_approve_shop_items');
		if($enable_auto_approve_shop_items){
			return $this->claimAndApproveShoppingItem($itemId, $playerId);
		}
		
		$isAjaxCall = $this->input->is_ajax_request() ? true : false;
		$this->load->model(array('shopper_list', 'shopping_center', 'point_transactions', 'player_points'));
		if ($this->shopper_list->isplayerItemRequestExists($itemId, $playerId)) {
			return;
		}
		$shopperList = $this->shopper_list->getShopperList($itemId, Shopper_list::APPROVED);
		$firstChild = 0;
		$itemDetails = $this->shopping_center->getData($itemId)[$firstChild];

		$availableShoppingItem = $itemDetails['how_many_available'];
		$availableShoppingItemRequiredPts = json_decode($itemDetails['requirements'], true)['required_points'];

		$totalPoints = $this->getPlayerTotalPoints($playerId);

		// OGP-25463
		$frozenPoints = $this->player_points->getFozenPlayerPoints($playerId);
		$totalPoints = $totalPoints-$frozenPoints;

		if ($totalPoints >= $availableShoppingItemRequiredPts) {
			if (count($shopperList) < $availableShoppingItem) {
				$playerid = $this->authentication->getPlayerId();

				#insert to shopper_list table
				$data = array(
					"player_id" => $playerid,
					"player_username" => $this->authentication->getUsername(),
					"shopping_item_id" => $itemId,
					"required_points" => $availableShoppingItemRequiredPts,
					"status" => Shopper_list::REQUEST,
					"application_datetime" => $this->utils->getNowForMysql(),
				);

				$id = $this->shopper_list->addRequestToShopperList($data);
				if(empty($id)){
					$status = "failed";
					$message = lang('Error processing your request.');						
					$this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);
					if ($isAjaxCall) {													
						$this->returnJsonResult(array('status' => $status, 'msg' => lang($message)));
						return;
					}
					$this->utils->debug_log("Message: =============================================> " . $message);
					redirect('player_center');
				}				

				// OGP-25463 add deduct points and add to frozen
				$enable_shop_frozen_points = $this->utils->getConfig('enable_shop_frozen_points');
				if($enable_shop_frozen_points){
					$isFrozenSuccess = $this->player_points->incrementPlayerPoints($playerid, $availableShoppingItemRequiredPts, 'frozen');
					if(!$isFrozenSuccess){
						$status = "failed";
						$message = lang('Error processing your request. Cannot add points to frozen account.');						
						$this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);
						if ($isAjaxCall) {							
							$this->returnJsonResult(array('status' => $status, 'msg' => lang($message)));
							return;
						}
						$this->utils->debug_log("Message: =============================================> " . $message);
						redirect('player_center');
					}
				}

				$status = "success";
				$message = lang('Your request has successfully sent!');
				if (!$isAjaxCall) {
					$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, $message);
				}
			} else {
				$status = "failed";
				$message = lang('Request limit has been reached, pls choose another item..');
				if (!$isAjaxCall) {
					$this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);
				}
			}
		} else {
			$status = "failed";
			$message = lang('Your points is insufficient to claim this item!');
			if (!$isAjaxCall) {
				$this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);
			}
		}

		if ($isAjaxCall) {
			$this->returnJsonResult(array(
                'status' => $status,
                'msg' => lang($message),
                'enable_shop_claim_item_auto_reload_desktop' => $this->utils->getConfig('enable_shop_claim_item_auto_reload_desktop'),
				'enable_hide_shop_claim_button' => $this->utils->getConfig('enable_hide_shop_claim_button'),
            ));
			return;
		}
		$this->utils->debug_log("Message: =============================================> " . $message);
		redirect('player_center');
	}

	public function claimAndApproveShoppingItem($itemId, $playerId) {
		$isAjaxCall = $this->input->is_ajax_request() ? true : false;
		$this->load->model(array('shopper_list', 'shopping_center', 'point_transactions', 'player_points'));
		if ($this->shopper_list->isplayerItemRequestExists($itemId, $playerId)) {
			return;
		}				

		$status = "failed";
		$message = '';		
		$data = [];

		try{			

			$success = $this->lockAndTrans(Utils::LOCK_ACTION_POINTS, $playerId, function () use ($playerId, 			
			$itemId,
			&$data, &$message, &$status) {				

				$shopperList = $this->shopper_list->getShopperList($itemId, Shopper_list::APPROVED);
				$firstChild = 0;
				$itemDetails = $this->shopping_center->getData($itemId)[$firstChild];

				$availableShoppingItem = $itemDetails['how_many_available'];
				$availableShoppingItemRequiredPts = json_decode($itemDetails['requirements'], true)['required_points'];

				$totalPoints = $this->getPlayerTotalPoints($playerId);

				//prepare data
				$playerid = $this->authentication->getPlayerId();			
				$data = array(
					"player_id" => $playerid,
					"player_username" => $this->authentication->getUsername(),
					"shopping_item_id" => $itemId,
					"required_points" => $availableShoppingItemRequiredPts,
					"status" => Shopper_list::REQUEST,
					"application_datetime" => $this->utils->getNowForMysql(),
				);
		

				$success = false;
				//check if balance is sufficient
				if ($availableShoppingItemRequiredPts>$totalPoints) {
					$message = lang('Your points is insufficient to claim this item!');
					return false;;
				}
	
				//check invenstory
				if (count($shopperList) >= $availableShoppingItem) {
					$message = lang('Request limit has been reached, pls choose another item..');
					return false;
				}
	
				//insert data
				$id = $this->shopper_list->addRequestToShopperList($data);
				if(empty($id)){
					$status = "failed";
					$message = lang('Error processing your request.');	
					return false;
				}
				
				//update status approve
				$data['id'] = $id;
				$data['status'] = Shopper_list::APPROVED;
				$data['processed_datetime'] = $this->utils->getNowForMysql();
				$data['processed_by'] = point_transactions::ADMIN;
				
				$result = $this->shopper_list->approveOrDeclinedShopItemClaimRequest($data);
				if (!$result) {					
					$status = "failed";
					$message = lang('Error processing your request. Cannot auto approve shop item.');						
					return false;
				}

				//deduct points
				$playerTotalPoints = $this->getPlayerTotalPoints($playerId);
				$newPointsBalance = $playerTotalPoints - $availableShoppingItemRequiredPts;
				$tran_id = $this->point_transactions->createPointTransaction(
					point_transactions::ADMIN,
					$playerId,
					$availableShoppingItemRequiredPts,
					$playerTotalPoints,
					$newPointsBalance,
					null, null, Point_transactions::DEDUCT_POINT, null, null,
					Point_transactions::DEDUCT
				);

				if(empty($tran_id)){
					$status = "failed";
					$message = lang('Error processing your request.');	
					return false;
				}

				$point_blance_update['id'] = $id;
				$point_blance_update["before_points"] = $playerTotalPoints;
				$point_blance_update["after_points"] = $newPointsBalance;
				$point_blance_update["trans_id"] = $tran_id;
				$this->shopper_list->updatePointStatusForOrder($point_blance_update);
				//update player point balance
				$successUpdateBal = $this->CI->player_model->updatePlayerPointBalance($playerId, $newPointsBalance);
				if(!$successUpdateBal){
					$status = "failed";
					$message = lang('Error processing your request.');	
					return false;
				}
				$msgSenderUserId = Users::SUPER_ADMIN_ID;
				$msgSenderName = lang('Shop_msg_sender_name');
				$subject = lang('Shop_msg_subject');				
				$playerUsername = $this->player_model->getUsernameById($playerId);
				$approvedContent = sprintf(lang('shop_approved_message'), $playerUsername);		
				$this->sendInternalMessageToPlayer($msgSenderUserId, $playerId, $msgSenderName, $subject, $approvedContent, TRUE);
				return true;
			});
	
			if(!$success){
				throw new Exception($message);
			}

			$status = "success";
			$message = lang('Your request has successfully sent and approved!');
			$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, $message);
			
		}catch(\Exception $e){			
			$status = "failed";
			$message = $e->getMessage();			
			$this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);
		}
		
		if ($isAjaxCall) {
			$this->returnJsonResult(array(
				'status' => $status, 
				'msg' => lang($message),			
				'enable_shop_claim_item_auto_reload_desktop' => $this->utils->getConfig('enable_shop_claim_item_auto_reload_desktop'),
				'enable_hide_shop_claim_button' => $this->utils->getConfig('enable_hide_shop_claim_button')
			));
			return;
		}

		$this->utils->debug_log("Message: =============================================> " . $message);
		redirect('player_center');
	}

	/**
	 * shoppingClaimRequestList
	 *
	 *
	 * @return	redered template
	 */
	public function shoppingClaimRequestList() {
		if (!$this->permissions->checkPermissions('shopping_claim_request_list')) {
			$this->error_access();
		} else {
			$this->load->model(array('shopper_list', 'users', 'shopping_center'));

			$this->loadTemplate(lang('role.59'), '', '', 'marketing');
			$this->template->write_view('sidebar', 'marketing_management/sidebar');

			if (!$this->permissions->checkPermissions('export_report')) {
				$data['export_report_permission'] = FALSE;
			} else {
				$data['export_report_permission'] = TRUE;
			}

			$data['countAllStatus'] = $this->shopper_list->countAllStatusOfShoppingList();
			$data['allLevels'] = $this->player_manager->getAllPlayerLevels();
			$data['allShoppingItem'] = $this->shopping_center->getData(null, Shopping_center::STATUS_NORMAL);
			$data['users'] = $this->users->getAllUsernames();

			$start_today = date("Y-m-d") . ' 00:00:00';
			$end_today = date("Y-m-d") . ' 23:59:59';

			$data['conditions'] = $this->safeLoadParams(array(
				'status' => Shopper_list::REQUEST,
				'request_date_from' => $start_today,
				'request_date_to' => $end_today,
				'username' => '',
				'processed_by' => '',
				'vipsettingcashbackruleId' => '',
				'shoppingItemId' => ''

			));

			$this->template->add_css('resources/css/dashboard.css');
			$this->template->add_js('resources/third_party/datatables/datatables.min.js');
			$this->template->add_css('resources/third_party/datatables/datatables.min.css');
			$this->template->write_view('main_content', 'shopping_center/shopping_claim_request_list', $data);
			$this->template->render();
		}
	}

	/**
	 * shoppingPointExpiration
	 *
	 *
	 * @return	redered template
	 */
	public function shoppingPointExpiration() {
		if (!$this->permissions->checkPermissions('shop_point_expiration')) {
			$this->error_access();
		} else {
			$this->load->model(array('shopper_list', 'users', 'shopping_center'));

			$data = [];
			$this->loadTemplate(lang('role.59'), '', '', 'marketing');
			$this->template->write_view('sidebar', 'marketing_management/sidebar');
			$this->template->add_css('resources/css/dashboard.css');
			$this->template->write_view('main_content', 'shopping_center/view_shop_point_expiration', $data);
			$this->template->render();
		}
	}

	public function updateShoppingPointExpirationSetting() {
		$this->load->model(array('operatorglobalsettings'));
		$settings = $this->input->post();
		if($this->operatorglobalsettings->existsSetting('shopping_point_expiration_setting')) {
			$success = $this->operatorglobalsettings->saveSettings(array('shopping_point_expiration_setting' => $settings));
		} else {
			$success = $this->operatorglobalsettings->insertSettingJson('shopping_point_expiration_setting',$settings);
		}
		$message = lang('Successfully Update Setting');
		$this->alertMessage(parent::MESSAGE_TYPE_SUCCESS, $message);
		redirect('marketing_management/shoppingPointExpiration');
	}

	public function getShoppingPointExpirationSetting() {
		$setting = [];
		$this->load->model(array('operatorglobalsettings'));
		if($this->operatorglobalsettings->existsSetting('shopping_point_expiration_setting')){
			$setting = $this->operatorglobalsettings->getSettingJson('shopping_point_expiration_setting');
		}
		if($this->input->is_ajax_request()) {
			echo json_encode($setting);
			exit;
		} else {
			return $setting;
		}
	}

	public function approveOrDeclinedShopItemClaimRequest($playerId, $itemId, $status) {
		$this->load->model(array('shopper_list', 'point_transactions', 'shopping_center', 'internal_message', 'player_points'));
		$shoppingItemRequiredPoints = $this->shopper_list->getItemDetails($itemId)['required_points'];
		$playerTotalPoints = $this->getPlayerTotalPoints($playerId);

		$shoppingItemId = $this->shopper_list->getItemDetails($itemId)['shopping_item_id'];
		$shoppingUsedItem = $this->shopper_list->getShoppingItemAvailableSlot($shoppingItemId)['usedItem'];
		$shoppingItemLimit = $this->shopping_center->getData($shoppingItemId)[0]['how_many_available'];

		$data = array(
			"id" => $itemId,
			"player_id" => $playerId,
			"processed_datetime" => $this->utils->getNowForMysql(),
			"processed_by" => $this->authentication->getUserId(),
		);

		// set message params
		$msgSenderUserId = $this->authentication->getUserId();
		$msgSenderName = lang('Shop_msg_sender_name');
		$subject = lang('Shop_msg_subject');
		$playerUsername = $this->player_model->getUsernameById($playerId);
		$approvedContent = sprintf(lang('shop_approved_message'), $playerUsername);
		$declinedContent = sprintf(lang('shop_declined_message'), $playerUsername);
		// $disable_send_internal_message = $this->utils->getConfig('disable_send_internal_message_in_shop_process');
				#todo deduct frozen points here
		// OGP-25463 deduct frozen points
		$enable_shop_frozen_points = $this->utils->getConfig('enable_shop_frozen_points');
		if($enable_shop_frozen_points){
			$isFrozenSuccess = $this->player_points->decrementPlayerPoints($playerId, $shoppingItemRequiredPoints, 'frozen');
			if(!$isFrozenSuccess){
				$status = "failed";
				$message = lang('Error processing your request. Cannot return frozen points.');			
				//record action								
				$this->saveAction('Failed to process shopping claim request due to failed in DB!', "User " . $this->authentication->getUsername() . " has failed to process shopping item claim request: " . $itemId);
				$this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);
				// $this->sendInternalMessageToPlayer($msgSenderUserId, $playerId, $msgSenderName, $subject, $declinedContent, TRUE);
				if ($this->input->is_ajax_request()) {
					$this->returnJsonResult(array('status' => 'success'));
					return;
				}

                // OGP-25553 Keep search filter condition
                $keep_shop_request_filter = $this->utils->getConfig('keep_shop_request_filter');
                $shopUrl = ('marketing_management/shoppingClaimRequestList');
                    if($keep_shop_request_filter && !empty($_SERVER['HTTP_REFERER'])){
                        $shopUrl = $_SERVER['HTTP_REFERER'];
                    }
                redirect($shopUrl);
			}
		}

		if ($status == Shopper_list::APPROVED) {
			$action = "Approved";


			//if shopping item reached do not approve the request
			if ($shoppingUsedItem >= $shoppingItemLimit) {
				//record action
				$this->saveAction($action . ' Shopping Item Claim Request Denied', "User " . $this->authentication->getUsername() . " has failed to " . $action . " shopping item claim request: " . $itemId . " because shopping item limit has been reached");

				$message = lang('Shopping Item Claim Approval was declined because shopping item\'s limit has been reached!');
				$this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);
                $this->sendInternalMessageToPlayer($msgSenderUserId, $playerId, $msgSenderName, $subject, $declinedContent, TRUE);

				 // OGP-25553 Keep search filter condition
                 $keep_shop_request_filter = $this->utils->getConfig('keep_shop_request_filter');
                 $shopUrl = ('marketing_management/shoppingClaimRequestList');
                     if($keep_shop_request_filter && !empty($_SERVER['HTTP_REFERER'])){
                         $shopUrl = $_SERVER['HTTP_REFERER'];
                     }
                 redirect($shopUrl);
			}

			//if

			//if player points is insufficient do not approve the request
			if ($playerTotalPoints < $shoppingItemRequiredPoints) {
				$this->saveAction($action . ' Shopping Item Claim Request Denied', "User " . $this->authentication->getUsername() . " has failed to " . $action . " shopping item claim request: " . $itemId . " because player points is insufficient");
				$message = lang('Shopping Item Claim Approval was declined because player points is insufficient!');
				$this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);
                $this->sendInternalMessageToPlayer($msgSenderUserId, $playerId, $msgSenderName, $subject, $declinedContent, TRUE);

				// OGP-25553 Keep search filter condition
                $keep_shop_request_filter = $this->utils->getConfig('keep_shop_request_filter');
                $shopUrl = ('marketing_management/shoppingClaimRequestList');
                    if($keep_shop_request_filter && !empty($_SERVER['HTTP_REFERER'])){
                        $shopUrl = $_SERVER['HTTP_REFERER'];
                    }
                redirect($shopUrl);
			}

		} elseif ($status == Shopper_list::DECLINED) {

			$action = "Declined";
			$message = lang('Successfully Declined shopping request!');
			$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, $message);
			$data["notes"] = $this->input->post("reason") ?: null;

            $this->sendInternalMessageToPlayer($msgSenderUserId, $playerId, $msgSenderName, $subject, $declinedContent, TRUE);
		}

		$data["status"] = $status;
		$result = $this->shopper_list->approveOrDeclinedShopItemClaimRequest($data);

		if ($result) {

			$message = lang('Successfully ' . $action . ' shopping request!');
			$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, $message);

			if ($status == Shopper_list::APPROVED) {

				#todo deduct points of player here
				$newPointsBalance = $playerTotalPoints - $shoppingItemRequiredPoints;
				$tran_id = $this->point_transactions->createPointTransaction(
					point_transactions::ADMIN,
					$playerId,
					$shoppingItemRequiredPoints,
					$playerTotalPoints,
					$newPointsBalance,
					null, null, Point_transactions::DEDUCT_POINT, null, null,
					Point_transactions::DEDUCT
				);

				$point_blance_update['id'] = $itemId;
				$point_blance_update["before_points"] = $playerTotalPoints;
				$point_blance_update["after_points"] = $newPointsBalance;
				$point_blance_update["trans_id"] = $tran_id;
				$this->shopper_list->updatePointStatusForOrder($point_blance_update);
				//update player point balance
				$this->CI->player_model->updatePlayerPointBalance($playerId, $newPointsBalance);
				$this->sendInternalMessageToPlayer($msgSenderUserId, $playerId, $msgSenderName, $subject, $approvedContent, TRUE);
			}

			//record action
			$this->saveAction($action . ' Shopping Item Claim Request', "User " . $this->authentication->getUsername() . " has successfully " . $action . " shopping item claim request: " . $itemId);

		} else {

			$message = lang('Failed to Save in DB!');
			$this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);

			//record action
			$this->saveAction('Failed to processe shopping claim request due to failed in DB!', "User " . $this->authentication->getUsername() . " has failed to process shopping item claim request: " . $itemId);
		}

		if ($this->input->is_ajax_request()) {
			$this->returnJsonResult(array('status' => 'success'));
			return;
		}

		// OGP-25553 Keep search filter condition
        $keep_shop_request_filter = $this->utils->getConfig('keep_shop_request_filter');
        $shopUrl = ('marketing_management/shoppingClaimRequestList');
            if($keep_shop_request_filter && !empty($_SERVER['HTTP_REFERER'])){
                $shopUrl = $_SERVER['HTTP_REFERER'];
            }
        redirect($shopUrl);
	}

	public function getPlayerTotalPoints($playerId) {
		// $expiration_at = $this->point_transactions->getShoppingPointExpirationAt();
		// $totalPoints = $this->point_transactions->getPlayerTotalPoints($playerId, $expiration_at);
		// $playerTotalDeductedPoints = 0;
		// $playerTotalPoints = 0;
		// if (!empty($totalPoints)) {
		// 	$playerTotalPoints = array_sum(array_column($totalPoints, 'points'));
		// }
		// $deductedPointsDetail = $this->point_transactions->getPlayerTotalDeductedPoints($playerId, $expiration_at);
		// if (!empty($deductedPointsDetail) && key_exists('points', $deductedPointsDetail)) {
		// 	$playerTotalDeductedPoints = $deductedPointsDetail['points'];
		// }
		// $remainingPoints = $playerTotalPoints - $playerTotalDeductedPoints;

		return $this->point_transactions->getPlayerAvailablePoints($playerId);
	}

	public function getShoppingTransactionHistory($playerId) {
		$this->load->model('shopper_list');
		$data = $this->shopper_list->getShopperList(null, null, $playerId);
		return $this->returnJsonResult($data);
	}

	public function sendInternalMessageToPlayer($msgSenderUserId // #1
	, $playerId // #2
	, $msgSenderName // #3
	, $subject // #4
	, $message // #5
	, $is_system_message = FALSE // #6
	, $disabled_replay = FALSE // #7
	, $broadcast_id = NULL // #8
	) {
		$this->load->model(array('internal_message'));
		if($this->utils->getConfig('enable_send_internal_message_in_shop_process') != false){

			$this->internal_message->addNewMessageAdmin($msgSenderUserId, $playerId, $msgSenderName, $subject, $message, $is_system_message, $disabled_replay, $broadcast_id);
		}
	}

	// public function export(){
	// 	$this->exportToExcel("shopmanageritemlist","Marketing Management");
	// }

}