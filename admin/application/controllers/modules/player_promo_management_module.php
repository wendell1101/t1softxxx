<?php
trait player_promo_management_module {

	/**
	 * promoApplicationList
	 *
	 *
	 * @return	redered template
	 */
	public function promoApplicationList($status=Player_promo::TRANS_STATUS_APPROVED, $transactionDateType=Player_promo::TRANSACTION_DATE_TYPE_PROCESSED_TIME, $player_promo_status=Player_promo::TRANS_STATUS_APPROVED) {
		if (!$this->permissions->checkPermissions('promoapp_list')) {
			$this->error_access();
		} else {
			$this->load->model(array('promorules', 'player_promo','users'));

			$this->loadTemplate(lang('cms.promoReqAppList'), '', '', 'marketing');
			$this->template->write_view('sidebar', 'marketing_management/sidebar');

			if (!$this->permissions->checkPermissions('export_report')) {
				$data['export_report_permission'] = FALSE;
			} else {
				$data['export_report_permission'] = TRUE;
			}

			$hide_system = !$this->utils->getConfig('enabled_display_system_promo');
            $sortSetting = $this->utils->getConfig('promo_application_list_default_sort');
            $data['promoList'] = $this->promorules->getPromoSettingList($sortSetting['sort'], null, null, false, $sortSetting['orderBy'], null, $hide_system);

			/// disabled here, and the data move to the moment of ajax after query data_table.
            $data['countAllStatus'] = [];
            $data['countAllStatus'][Player_promo::TRANS_STATUS_REQUEST] = null;
            $data['countAllStatus'][Player_promo::TRANS_STATUS_APPROVED] = null;
            $data['countAllStatus'][Player_promo::TRANS_STATUS_FINISHED_WITHDRAW_CONDITION] = null;
            $data['countAllStatus'][Player_promo::TRANS_STATUS_DECLINED] = null;

			$data['allLevels'] = $this->player_manager->getAllPlayerLevels();
            $data['allPlayerPromoStatus'] = [
                'all' => lang('All'),
                Player_promo::TRANS_STATUS_REQUEST => lang('promo.request_list.search_status.pending'),
                Player_promo::TRANS_STATUS_APPROVED => lang('promo.request_list.search_status.approved'),
                Player_promo::TRANS_STATUS_DECLINED => lang('promo.request_list.search_status.declined'),
                Player_promo::TRANS_STATUS_FINISHED_WITHDRAW_CONDITION => lang('promo.request_list.search_status.finished')
            ];
			//$data['users'] = $this->users->getAllUsernames();
            $data['date_from'] = $date_from = $this->utils->getFirstDateOfCurrentMonth() . ' 00:00:00';
            $data['date_to'] = $date_to = date("Y-m-d") . ' 23:59:59';

            $custom_default_status = (int)$this->utils->getConfig('custom_default_status_in_player_promo');
            if(!empty($data['allPlayerPromoStatus'][$custom_default_status])){
                $status = $custom_default_status;
                $player_promo_status = $custom_default_status;
            }

            $conditions = array(
                'status'=>$status,
                'player_promo_status'=>$player_promo_status,
                'request_date_from' => $date_from,
                'request_date_to' => $date_to,
                'promorulesId' => '',
                'promoCmsSettingId' => '',
                'username' => '',
                'vipsettingcashbackruleId' => '',
                //'processed_by' => '',
                'transactionDateType' => $transactionDateType,
                'search_by_date' => '1',
                'isClickSearch' => '0',
                'only_show_active_promotion' => 'active',
            );

            $data['conditions'] = $this->safeLoadParams($conditions);
            $data['enable_go_1st_page_another_search_in_list'] =  $this->utils->_getEnableGo1stPageAnotherSearchWithMethod(__METHOD__);

            $userId=$this->authentication->getUserId();
			$data['double_submit_hidden_field']=$this->initDoubleSubmitAndReturnHiddenFieldForAdmin($userId);

            $this->template->add_css('resources/css/dashboard.css');
			$this->template->add_js('resources/third_party/datatables/datatables.min.js');
			$this->template->add_css('resources/third_party/datatables/datatables.min.css');
			$this->template->add_css('resources/third_party/bootstrap-multiselect-master/dist/css/bootstrap-multiselect.css');
			$this->template->add_js('resources/third_party/bootstrap-multiselect-master/dist/js/bootstrap-multiselect.js');

			$this->template->add_js('resources/js/marketing_management/append_haba_results.js');
			$this->template->add_js('resources/js/ace/ace.js');
			$this->template->add_js('resources/js/ace/mode-json.js');
			$this->template->add_js('resources/js/ace/theme-tomorrow.js');

			$this->template->write_view('main_content', 'marketing_management/promorules/promoapplication_list', $data);
			$this->template->render();
		}
	}

    /**
     * custom promoApplicationList
     *
     *
     * @return	redered template
     */
    public function referralPromoApplicationList($status=Player_promo::TRANS_STATUS_APPROVED, $transactionDateType=Player_promo::TRANSACTION_DATE_TYPE_PROCESSED_TIME, $player_promo_status=Player_promo::TRANS_STATUS_APPROVED) {
        if (!$this->permissions->checkPermissions('promoapp_list')) {
            $this->error_access();
        } else {
            $this->load->model(array('promorules', 'player_promo','users'));

            $this->loadTemplate(lang('Friend Referral Request Report'), '', '', 'marketing');
            $this->template->write_view('sidebar', 'marketing_management/sidebar');

            if (!$this->permissions->checkPermissions('export_report')) {
                $data['export_report_permission'] = FALSE;
            } else {
                $data['export_report_permission'] = TRUE;
            }

            $sortSetting = $this->utils->getConfig('promo_application_list_default_sort');

            $customList = [];
            $promoList = $this->promorules->getPromoSettingList($sortSetting['sort'], null, null, false, $sortSetting['orderBy']);
            $custom_friend_referral_promo_cms_id = $this->utils->getConfig('custom_friend_referral_promo_cms_id');
            if(!empty($custom_friend_referral_promo_cms_id) && !empty($promoList)){
                foreach ($promoList as $list){
                    if(in_array($list['promoCmsSettingId'], $custom_friend_referral_promo_cms_id)){
                        $customList[] = $list;
                    }
                }
            }
            $data['promoList'] = $customList;
            $data['countAllStatus'] = $this->player_promo->countAllStatusOfPromoApplicationByIds($custom_friend_referral_promo_cms_id);
            $data['allLevels'] = $this->player_manager->getAllPlayerLevels();
            $data['allPlayerPromoStatus'] = [
                'all' => lang('All'),
                Player_promo::TRANS_STATUS_REQUEST => lang('promo.request_list.search_status.pending'),
                Player_promo::TRANS_STATUS_APPROVED => lang('promo.request_list.search_status.approved'),
                Player_promo::TRANS_STATUS_DECLINED => lang('promo.request_list.search_status.declined'),
                Player_promo::TRANS_STATUS_FINISHED_WITHDRAW_CONDITION => lang('promo.request_list.search_status.finished')
            ];
            //$data['users'] = $this->users->getAllUsernames();
            $data['date_from'] = $date_from = $this->utils->getFirstDateOfCurrentMonth() . ' 00:00:00';
            $data['date_to'] = $date_to = date("Y-m-d") . ' 23:59:59';

            $custom_default_status = (int)$this->utils->getConfig('custom_default_status_in_player_promo');
            if(!empty($data['allPlayerPromoStatus'][$custom_default_status])){
                $status = $custom_default_status;
                $player_promo_status = $custom_default_status;
            }

            $conditions = array(
                'status'=>$status,
                'player_promo_status'=>$player_promo_status,
                'request_date_from' => $date_from,
                'request_date_to' => $date_to,
                'promorulesId' => '',
                'promoCmsSettingId' => '',
                'username' => '',
                'vipsettingcashbackruleId' => '',
                //'processed_by' => '',
                'transactionDateType' => $transactionDateType,
                'search_by_date' => '1',
                'isClickSearch' => '0'
            );

            $data['conditions'] = $this->safeLoadParams($conditions);

            $userId=$this->authentication->getUserId();
            $data['double_submit_hidden_field']=$this->initDoubleSubmitAndReturnHiddenFieldForAdmin($userId);

            $this->template->add_css('resources/css/dashboard.css');
            $this->template->add_js('resources/third_party/datatables/datatables.min.js');
            $this->template->add_css('resources/third_party/datatables/datatables.min.css');
            $this->template->add_css('resources/third_party/bootstrap-multiselect-master/dist/css/bootstrap-multiselect.css');
            $this->template->add_js('resources/third_party/bootstrap-multiselect-master/dist/js/bootstrap-multiselect.js');

            $this->template->add_js('resources/js/marketing_management/append_haba_results.js');
            $this->template->add_js('resources/js/ace/ace.js');
            $this->template->add_js('resources/js/ace/mode-json.js');
            $this->template->add_js('resources/js/ace/theme-tomorrow.js');

            $this->template->write_view('main_content', 'marketing_management/promorules/referral_promoapplication_list', $data);
            $this->template->render();
        }
    }

    /**
     * custom promoApplicationList for hugembet
     *
     *
     * @return	redered template
     */
		public function hugebetPromoApplicationList($status=Player_promo::TRANS_STATUS_APPROVED, $transactionDateType=Player_promo::TRANSACTION_DATE_TYPE_PROCESSED_TIME, $player_promo_status=Player_promo::TRANS_STATUS_APPROVED){
		if (!$this->permissions->checkPermissions('promoapp_list')) {
			return $this->error_access();
		}

		$this->load->model(array('promorules', 'player_promo','users'));

		$this->loadTemplate(lang('Friend Referral Request Report'), '', '', 'marketing');
		$this->template->write_view('sidebar', 'marketing_management/sidebar');

		if (!$this->permissions->checkPermissions('export_report')) {
			$data['export_report_permission'] = FALSE;
		} else {
			$data['export_report_permission'] = TRUE;
		}

		$sortSetting = $this->utils->getConfig('promo_application_list_default_sort');

		$customList = [];
		$promoList = $this->promorules->getPromoSettingList($sortSetting['sort'], null, null, false, $sortSetting['orderBy']);
		$custom_friend_referral_promo_cms_id = $this->utils->getConfig('t1t_common_brazil_friend_referral_promo_cms_id');
		if(!empty($custom_friend_referral_promo_cms_id) && !empty($promoList)){
			foreach ($promoList as $list){
				if(in_array($list['promoCmsSettingId'], $custom_friend_referral_promo_cms_id)){
					$customList[] = $list;
				}
			}
		}
		$data['promoList'] = $customList;
		$data['countAllStatus'] = $this->player_promo->countAllStatusOfPromoApplicationByIds($custom_friend_referral_promo_cms_id);
		$data['allLevels'] = $this->player_manager->getAllPlayerLevels();
		$data['allPlayerPromoStatus'] = [
			'all' => lang('All'),
			Player_promo::TRANS_STATUS_REQUEST => lang('promo.request_list.search_status.pending'),
			Player_promo::TRANS_STATUS_APPROVED => lang('promo.request_list.search_status.approved'),
			Player_promo::TRANS_STATUS_DECLINED => lang('promo.request_list.search_status.declined'),
			Player_promo::TRANS_STATUS_FINISHED_WITHDRAW_CONDITION => lang('promo.request_list.search_status.finished')
		];
		//$data['users'] = $this->users->getAllUsernames();
		$data['date_from'] = $date_from = $this->utils->getFirstDateOfCurrentMonth() . ' 00:00:00';
		$data['date_to'] = $date_to = date("Y-m-d") . ' 23:59:59';

		$custom_default_status = (int)$this->utils->getConfig('custom_default_status_in_player_promo');
		if(!empty($data['allPlayerPromoStatus'][$custom_default_status])){
			$status = $custom_default_status;
			$player_promo_status = $custom_default_status;
		}

		$conditions = array(
			'status'=>$status,
			'player_promo_status'=>$player_promo_status,
			'request_date_from' => $date_from,
			'request_date_to' => $date_to,
			'promorulesId' => '',
			'promoCmsSettingId' => '',
			'username' => '',
			'vipsettingcashbackruleId' => '',
			//'processed_by' => '',
			'transactionDateType' => $transactionDateType,
			'search_by_date' => '1',
			'isClickSearch' => '0'
		);

		$data['conditions'] = $this->safeLoadParams($conditions);

		$userId=$this->authentication->getUserId();
		$data['double_submit_hidden_field']=$this->initDoubleSubmitAndReturnHiddenFieldForAdmin($userId);

		$this->template->add_css('resources/css/dashboard.css');
		$this->template->add_js('resources/third_party/datatables/datatables.min.js');
		$this->template->add_css('resources/third_party/datatables/datatables.min.css');
		$this->template->add_css('resources/third_party/bootstrap-multiselect-master/dist/css/bootstrap-multiselect.css');
		$this->template->add_js('resources/third_party/bootstrap-multiselect-master/dist/js/bootstrap-multiselect.js');

		$this->template->add_js('resources/js/marketing_management/append_haba_results.js');
		$this->template->add_js('resources/js/ace/ace.js');
		$this->template->add_js('resources/js/ace/mode-json.js');
		$this->template->add_js('resources/js/ace/theme-tomorrow.js');

		$this->template->write_view('main_content', 'marketing_management/promorules/hugebet_referral_promoapplication_list', $data);
		$this->template->render();
	}

	public function release_promo($playerpromoId = null, $bonusAmount=null){
		$this->load->model(array('promorules', 'player_promo'));
		$success=false;
        $promorule = NULL;

        if (empty($playerpromoId)) {
			$playerpromoId = $this->input->post('releasePlayerPromoId');
		}

		if (empty($bonusAmount) && $this->input->post('bonusAmount') !== null) {
			$bonusAmount = $this->input->post('bonusAmount');
		}

        $this->utils->debug_log(__METHOD__,'post',$this->input->post(),$playerpromoId,$bonusAmount);

		if($playerpromoId){
			$playerPromo = $this->player_promo->getPlayerPromo($playerpromoId);
			$promoCmsSettingId = $playerPromo->promoCmsSettingId;
			$playerId = $playerPromo->playerId;

			if(empty($playerPromo)){
                $message = lang('error.default.db.message');
                if ($this->input->is_ajax_request()) {
                    $this->returnJsonResult(array('success' => false, 'message'=>$message, 'playerPromoId'=>$playerpromoId));
                    return;
                }
                $this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);
                return redirect('marketing_management/promoApplicationList');
            }

            $actionStatus = $this->input->post('actionStatus');

			if (!empty($actionStatus)) {
				if (!$this->player_promo->isVerifiedActionStatus($playerpromoId, $actionStatus)) {
					$message = lang('Processed by another user');
					$this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);
					redirect('marketing_management/promoApplicationList');
					return;
				}
			}

	        $lockedKey=null;
			$lock_it = $this->utils->lockResourceBy($playerId, Utils::LOCK_ACTION_BALANCE, $lockedKey);

            $promorulesId = $playerPromo->promorulesId;
            $promorule = $this->promorules->getPromorule($promorulesId);

            if(empty($promorule)){
                $message = lang('error.default.db.message');
                if ($this->input->is_ajax_request()) {
                    $this->returnJsonResult(array('success' => false, 'message'=>$message, 'playerPromoId'=>$playerpromoId));
                    return;
                }
                $this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);
                return redirect('marketing_management/promoApplicationList');
            }

			// $lock_it = $this->lockActionById($playerId, Utils::LOCK_ACTION_BALANCE);
			$this->utils->debug_log('lock admin promo', $playerId, $lock_it);

			$success = $lock_it;
			$extra_info=['release_to_real'=>true, 'promorule' => $promorule, 'player_promo_request_id' => $playerpromoId];
			$result=null;

            $playerPromoStatus = $this->player_promo->getPlayerPromoStatusById($playerpromoId);
            $allow_release_promo = in_array($playerPromoStatus, [Player_promo::TRANS_STATUS_REQUEST, Player_promo::TRANS_STATUS_APPROVED_WITHOUT_RELEASE_BONUS]);
			if ($lock_it) {
				if($allow_release_promo){
					//lock success
					try {
						//check status again


						$this->startTrans();
						$adminUserId = $this->authentication->getUserId();

						// $playerPromo = $this->player_promo->getPlayerPromo($playerpromoId);
						// $promoCmsSettingId = $playerPromo->promoCmsSettingId;
						// $playerId = $playerPromo->playerId;

						// $promotion_rules=$this->utils->getConfig('promotion_rules');
						//default is false
						// $release_on_admin_approve=$promotion_rules['release_on_admin_approve'];

						// if($release_on_admin_approve){
						$tranId=null;
						$reason= $this->input->post('reasonToRelease');
						$extra_info['bonusAmount']=$bonusAmount;
						$success=!!$this->promorules->approvePromo($playerId, $promorule, $promoCmsSettingId,
								$adminUserId, $playerPromo->depositAmount, $tranId, $playerpromoId, $extra_info, $reason);

						// $success=$result['success'];
						// }else{

							// $this->promorules->approvePromoWithoutRelease($playerId, $promorule, $promoCmsSettingId,
								// $adminUserId, $playerPromo->depositAmount, null, $playerpromoId);

							//should checkfirst
							// $result=$this->promorules->checkAndProcessPromotion($playerId, $promorule, $promoCmsSettingId);

						// }


						//log admin action
						$this->saveAction(self::MANAGEMENT_TITLE, 'Release Player Promo Application', "User " . $this->authentication->getUsername() . " has successfully approved player promo :".$playerpromoId);

						// $this->group_level->endTrans();
						// if ($this->group_level->isErrorInTrans()) {
						// 	//show error
						// 	$this->alertMessage(1, lang('save.failed'));
						// }
						if($success){
							$success = $this->endTransWithSucc();
						}else{
							//false rollback
							$this->rollbackTrans();
							$this->utils->error_log('rollback release_promo playerpromoId:', $playerpromoId);
						}
						// $success = $this->endTransWithSucc() && $success;
					} finally {
						$rlt = $this->utils->releaseResourceBy($playerId, Utils::LOCK_ACTION_BALANCE, $lockedKey);
						// release it
						// $rlt = $this->releaseActionById($playerId, Utils::LOCK_ACTION_BALANCE);
						// $rlt = $this->player_model->transReleaseLock($trans_key);
						$this->utils->debug_log('release admin promo lock', $playerId, $rlt);
					}
				}else{
					$success = false;
				}
			}
		}

		if($success){
			// $result['message'].=' '.lang($message);

			$bonusResult=$this->promorules->releaseToAfterApplyPromoV2($promorule, $extra_info);
		}else{
			// $result['success']=false;
			// $result['message'].=' '.lang($message);
			//don't show error, only show success
			$this->utils->debug_log('apply promotion failed', $success, $playerId, $playerpromoId);
			// $message=null;
		}

		if ($success && $lock_it) {
			//Promo application has been approved!
			$message = lang('Released bonus to player');
			if ($this->input->is_ajax_request()) {
				$this->returnJsonResult(array('success' => true, 'message'=>$message, 'playerPromoId'=>$playerpromoId));
				return;
			}
			$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, $message);
		} else {
			$message = lang('error.default.db.message');
			if ($this->input->is_ajax_request()) {
				$this->returnJsonResult(array('success' => false, 'message'=>$message, 'playerPromoId'=>$playerpromoId));
				return;
			}
			$this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);

		}

		redirect('marketing_management/promoApplicationList');

	}

	/**
     * @deprecated marked by curtis
	 * approvePromoApplication
	 *
	 *
	 * @return	redered template
	 */
	public function approve_player_promo($playerpromoId) {
    /*
		// $this->group_level->startTrans();

		$this->load->model(array('promorules', 'player_promo'));
		$checkPromoIfUpdated = $this->player_promo->checkPromoIfUpdated($playerpromoId);
		$success=false;
		if($playerpromoId && !$checkPromoIfUpdated){

			$playerPromo = $this->player_promo->getPlayerPromo($playerpromoId);
			$promoCmsSettingId = $playerPromo->promoCmsSettingId;
			$playerId = $playerPromo->playerId;

	        $lockedKey=null;
			$lock_it = $this->utils->lockResourceBy($playerId, Utils::LOCK_ACTION_BALANCE, $lockedKey);
			// $lock_it = $this->lockActionById($playerId, Utils::LOCK_ACTION_BALANCE);
			$this->utils->debug_log('lock admin promo', $playerId, $lock_it);

			$success = $lock_it;
			if ($lock_it) {
				//lock success
				try {

					$this->startTrans();
					$adminUserId = $this->authentication->getUserId();

					// $playerPromo = $this->player_promo->getPlayerPromo($playerpromoId);
					// $promoCmsSettingId = $playerPromo->promoCmsSettingId;
					// $playerId = $playerPromo->playerId;
					$promorulesId = $playerPromo->promorulesId;
					$promorule = $this->promorules->getPromorule($promorulesId);

					$promotion_rules=$this->utils->getConfig('promotion_rules');
					//default is false
					// $release_on_admin_approve=$promotion_rules['release_on_admin_approve'];

					$reason=null;
					$extra_info=null;
					$success=!!$this->promorules->approvePrePromo($playerId, $promorule, $promoCmsSettingId,
						$adminUserId, $playerPromo->depositAmount, null, $playerpromoId, $extra_info, $reason);

					// if($promorule['add_withdraw_condition_as_bonus_condition']=='1'){

						// $this->promorules->approvePromoWithoutRelease($playerId, $promorule, $promoCmsSettingId,
						// 	$adminUserId, $playerPromo->depositAmount, null, $playerpromoId);

					// }else{

						// $this->promorules->approvePromo($playerId, $promorule, $promoCmsSettingId,
						// 	$adminUserId, $playerPromo->depositAmount, null, $playerpromoId);

						//should checkfirst
						// $result=$this->promorules->checkAndProcessPromotion($playerId, $promorule, $promoCmsSettingId);

					// }

					//log admin action
					$this->saveAction(self::MANAGEMENT_TITLE, 'Approve Player Promo Application', "User " . $this->authentication->getUsername() . " has successfully approved player promo :".$playerpromoId);

					if($success){
						$success = $this->endTransWithSucc();
					}else{
						//false rollback
						$this->rollbackTrans();
						$this->utils->error_log('rollback approvePrePromo playerpromoId:', $playerpromoId);
					}

					// $this->group_level->endTrans();
					// if ($this->group_level->isErrorInTrans()) {
					// 	//show error
					// 	$this->alertMessage(1, lang('save.failed'));
					// }
					// $success = $this->endTransWithSucc();
				} finally {
					// release it
					$rlt = $this->utils->releaseResourceBy($playerId, Utils::LOCK_ACTION_BALANCE, $lockedKey);
					// $rlt = $this->releaseActionById($playerId, Utils::LOCK_ACTION_BALANCE);
					// $rlt = $this->player_model->transReleaseLock($trans_key);
					$this->utils->debug_log('release admin promo lock', $playerId, $rlt);
				}
			}
		}

		if ($success && $lock_it) {
			//Promo application has been approved!
			$message = lang('cms.promoApproved');
			$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, $message);
		} else {
			$message = lang('error.default.db.message');
			$this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);

		}

		redirect('marketing_management/promoApplicationList');
    */
	}

	//Deprecated by curtis
	public function expire_promo(){
    /*
		$this->load->model(array('player_promo', 'promorules'));

		$playerPromoId = $this->input->post('expirePlayerPromoId');
		$adminUserId = $this->authentication->getUserId();
		$reason = $this->input->post('reasonToExpire');
		// $playerpromodata = array(
		// 	'playerpromoId' => $this->input->post('declinePlayerPromoId'),
		// 	'declinedApplicationReason' => $this->input->post('reasonToCancel'),
		// 	'dateApplicationDeclined' => date('Y-m-d H:i:s'),
		// 	'processedBy' => $this->authentication->getUserId(),
		// 	'transactionStatus' => Player_promo::TRANS_STATUS_DECLINED,
		// 	'dateProcessed' => $this->utils->getNowForMysql(),
		// );
		// $this->player_promo->processPromoApplication($playerpromodata);
		$success=false;
		if(!empty($playerPromoId)){

			$this->startTrans();

			$playerPromo=$this->player_promo->getPlayerPromo($playerPromoId);
			$promorule=$this->promorules->getPromoRuleRow($playerPromo->promorulesId);
			$promoCmsSettingId=$playerPromo->promoCmsSettingId;
			$playerId=$playerPromo->playerId;
			$bonusAmount=null;
			$depositAmount = null;
			$withdrawConditionAmount=null;
			$betTimes=null;
			// $reason = null;

			$success=!!$this->promorules->expirePromo($playerId, $promorule, $promoCmsSettingId, $adminUserId,
				$bonusAmount, $depositAmount, $withdrawConditionAmount, $betTimes, $reason, $playerPromoId);

			// $this->promorules->expirePromo(null, null, null, null,
			// 	$adminUserId, $reason, $playerPromoId);

			$this->saveAction(self::MANAGEMENT_TITLE, 'Expire Player Promo Application', "User " . $this->authentication->getUsername() . " has successfully expire player promo application request.");

			if($success){
				$success = $this->endTransWithSucc();
			}else{
				//false rollback
				$this->rollbackTrans();
				$this->utils->error_log('rollback expire_promo playerpromoId:', $playerpromoId);
			}
			// $success=$this->endTransWithSucc();
		}

		if ($success) {
			//Promo application has been approved!
			$message = lang('Set this promotion to expired');
			$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, $message);
		} else {
			$message = lang('error.default.db.message');
			$this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);

		}

		redirect('marketing_management/promoApplicationList');
    */
	}

	public function finish_promo($playerpromoId){

		$this->load->model(array('promorules', 'player_promo'));

		$success=false;
		if($playerpromoId){
			$playerPromo = $this->player_promo->getPlayerPromo($playerpromoId);
			$promoCmsSettingId = $playerPromo->promoCmsSettingId;
			$playerId = $playerPromo->playerId;

	        $lockedKey=null;
			$lock_it = $this->utils->lockResourceBy($playerId, Utils::LOCK_ACTION_BALANCE, $lockedKey);
			// $lock_it = $this->lockActionById($playerId, Utils::LOCK_ACTION_BALANCE);
			$this->utils->debug_log('lock admin promo', $playerId, $lock_it);

			$success = $lock_it;
			$extra_info=['release_to_real'=>true];
			$result=null;
			if ($lock_it) {
				//lock success
				try {

					$this->startTrans();
					$adminUserId = $this->authentication->getUserId();

					$reason=null;
					// $playerPromo=$this->player_promo->getPlayerPromo($playerpromoId);
					$promorule=$this->promorules->getPromoRuleRow($playerPromo->promorulesId);
					// $promoCmsSettingId=$playerPromo->promoCmsSettingId;
					// $playerId=$playerPromo->playerId;
					$bonusAmount=null;
					$depositAmount = null;
					$withdrawConditionAmount=null;
					$betTimes=null;

					// $promotion_rules=$this->utils->getConfig('promotion_rules');
					//default is false
					// $release_on_admin_approve=$promotion_rules['release_on_admin_approve'];

					// if($release_on_admin_approve){

					$success=!!$this->promorules->finishPromo($playerId, $promorule, $promoCmsSettingId,
							$adminUserId, $bonusAmount, $depositAmount,$withdrawConditionAmount, $betTimes,
							$playerpromoId, $extra_info, $reason);

					// $success=$result['success'];
					// }else{

						// $this->promorules->approvePromoWithoutRelease($playerId, $promorule, $promoCmsSettingId,
							// $adminUserId, $playerPromo->depositAmount, null, $playerpromoId);

						//should checkfirst
						// $result=$this->promorules->checkAndProcessPromotion($playerId, $promorule, $promoCmsSettingId);

					// }


					//log admin action
					$this->saveAction(self::MANAGEMENT_TITLE, 'Finish Player Promo Application', "User " . $this->authentication->getUsername() . " has successfully approved player promo :".$playerpromoId);

					if($success){
						$success = $this->endTransWithSucc();
					}else{
						//false rollback
						$this->rollbackTrans();
						$this->utils->error_log('rollback finishPromo playerpromoId:', $playerpromoId);
					}

					// $this->group_level->endTrans();
					// if ($this->group_level->isErrorInTrans()) {
					// 	//show error
					// 	$this->alertMessage(1, lang('save.failed'));
					// }
					// $success = $this->endTransWithSucc();
				} finally {
					// release it
					$rlt = $this->utils->releaseResourceBy($playerId, Utils::LOCK_ACTION_BALANCE, $lockedKey);
					// $rlt = $this->releaseActionById($playerId, Utils::LOCK_ACTION_BALANCE);
					// $rlt = $this->player_model->transReleaseLock($trans_key);
					$this->utils->debug_log('release admin promo lock', $playerId, $rlt);
				}
			}
		}

		if($success){
			// $result['message'].=' '.lang($message);
		}else{
			// $result['success']=false;
			// $result['message'].=' '.lang($message);
			//don't show error, only show success
			$this->utils->debug_log('apply promotion failed', $success, $playerId, $playerpromoId);
			// $message=null;
		}

		if ($success && $lock_it) {
			//Promo application has been approved!
			$message = lang('Set to Finished');
			if ($this->input->is_ajax_request()) {
				$this->returnJsonResult(array('success' => true, 'message'=>$message, 'playerPromoId'=>$playerpromoId));
				return;
			}
			$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, $message);
		} else {
			$message = lang('error.default.db.message');
			if ($this->input->is_ajax_request()) {
				$this->returnJsonResult(array('success' => false, 'message'=>$message, 'playerPromoId'=>$playerpromoId));
				return;
			}
			$this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);

		}

		redirect('marketing_management/promoApplicationList');

	}

	/**
	 *
	 *
	 *
	 * @return	redered template
	 */
	public function decline_player_promo() {
		$this->load->model(array('player_promo', 'promorules'));

		$playerPromoId = $this->input->post('declinePlayerPromoId');
        $playerPromoStatus = $this->player_promo->getPlayerPromoStatusById($playerPromoId);
        $allow_decline_player_promo = in_array($playerPromoStatus, [PLAYER_PROMO::TRANS_STATUS_REQUEST,
                                                                    Player_promo::TRANS_STATUS_MANUAL_REQUEST_APPROVED_WITHOUT_RELEASE_BONUS,
                                                                    Player_promo::TRANS_STATUS_APPROVED_WITHOUT_RELEASE_BONUS]);
		$adminId = $this->authentication->getUserId();
		if(!$this->verifyAndResetDoubleSubmitForAdmin($adminId)){
			$message = lang('Please refresh and try, and donot allow double submit');
			$this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);
			redirect('marketing_management/promoApplicationList');
			return;
		}

		$success=false;
		if(!empty($playerPromoId) && $allow_decline_player_promo){
			$this->startTrans();

			$reason = $this->input->post('reasonToCancel');
			$actionStatus = $this->input->post('actionStatus');

			if (!empty($actionStatus)) {
				if (!$this->player_promo->isVerifiedActionStatus($playerPromoId, $actionStatus)) {
					$message = lang('Processed by another user');
					$this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);
					redirect('marketing_management/promoApplicationList');
					return;
				}
			}

			// $this->promorules->declinePromo(null, null, null, null,
			// 	$adminUserId, $reason, $playerPromoId);

			$playerPromo=$this->player_promo->getPlayerPromo($playerPromoId);
			$promorule=$this->promorules->getPromoRuleRow($playerPromo->promorulesId);
			$promoCmsSettingId=$playerPromo->promoCmsSettingId;
			$playerId=$playerPromo->playerId;
			$bonusAmount=null;
			$depositAmount = null;
			$withdrawConditionAmount=null;
			$betTimes=null;
			// $reason = null;

			$this->promorules->declinePromo($playerId, $promorule, $promoCmsSettingId, $adminId,
				$bonusAmount, $depositAmount, $withdrawConditionAmount, $betTimes,
				$reason, $playerPromoId);

			$this->saveAction(self::MANAGEMENT_TITLE, 'Decline Player Promo Application', "User " . $this->authentication->getUsername() . " has successfully declined player promo application request.");

			$success=$this->endTransWithSucc();

		}
		if ($success) {
			//Promo application has been approved!
			$message = lang('Declined this promotion');
			if ($this->input->is_ajax_request()) {
				$this->returnJsonResult(array('success' => true, 'message'=>$message, 'playerPromoId'=>$playerPromoId));
				return;
			}
			$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, $message);
		} else {
			$message = lang('error.default.db.message');
			if ($this->input->is_ajax_request()) {
				$this->returnJsonResult(array('success' => false, 'message'=>$message, 'playerPromoId'=>$playerPromoId));
				return;
			}
			$this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);

		}

		redirect('marketing_management/promoApplicationList');
	}

	public function record_action_status(){
		$this->load->model(array('player_promo', 'promorules'));

		$playerPromoId = $this->input->post('playerpromoId');
		$lockStatus = $this->input->post('playerPromoStatus');

		$this->utils->debug_log(__METHOD__,$playerPromoId ,$lockStatus);

		if (!empty($playerPromoId) && !empty($lockStatus)) {
			$success =  $this->player_promo->updateActionStatusPlayerPromo($playerPromoId, $lockStatus);
			$this->utils->printLastSQL();
			if ($success) {
				//Promo application has been approved!
				$message = sprintf(lang('Set current status to %s success') , $lockStatus);
				if ($this->input->is_ajax_request()) {
					$this->returnJsonResult(array('success' => true, 'message'=>$message, 'playerPromoId'=>$playerPromoId));
					$playerpromo = $this->player_promo->getPlayerPromo($playerPromoId);
					$promorule = $this->promorules->getPromoCmsDetails($playerpromo->promoCmsSettingId);
					$this->utils->debug_log('=======promorule=======',$promorule);
					switch($lockStatus){
						case '1':
							$this->promoTracking($playerpromo->playerId, 'TRACKINGEVENT_SOURCE_TYPE_PROMO_APPROVED', $promorule[0]['promoName'], 'Approved');
							break;
						case '2':
							$this->promoTracking($playerpromo->playerId, 'TRACKINGEVENT_SOURCE_TYPE_PROMO_APPROVED', $promorule[0]['promoName'], 'Approved');
							break;
						case '3':
							$this->promoTracking($playerpromo->playerId, 'TRACKINGEVENT_SOURCE_TYPE_PROMO_REJECTED', $promorule[0]['promoName'], 'Rejected');
							break;
					}
					return;
				}
			} else {
				$message = sprintf(lang('Set current status to %s failed') , $lockStatus);
				if ($this->input->is_ajax_request()) {
					$this->returnJsonResult(array('success' => false, 'message'=>$message, 'playerPromoId'=>$playerPromoId));
					return;
				}
			}
		}
	}

	public function promoTracking($playerId, $source_type, $title, $status){
		$this->utils->debug_log("======source_type======", $source_type);
		$this->utils->playerTrackingEvent($playerId, $source_type, array(
			'PromoTitle' => $title,
			'Status'	  => $status
		));
	}

	public function manually_add_bonus($playerId=null){
		if (!$this->permissions->checkPermissions('manually_add_bonus')) {
			return $this->error_access();
		}

		if(empty($playerId)){
			return $this->error_access();
		}
		$userId=$this->authentication->getUserId();

		$this->load->model(array('promorules', 'external_system', 'player_model', 'transactions'));
		// $platform_name = $platform_id == '0' ? lang('pay.mainwallt') : $this->external_system->getNameById($platform_id) . ' ' . lang('cashier.42');

		$username=$this->player_model->getUsernameById($playerId);
		$data = array(
			// 'platform_id' => $platform_id,
			// 'platform_name' => $platform_name,
			// 'transaction_type' => $transaction_type,
			'player_id' => $playerId,
			'username' => $username,
		);
		// $data['promoCategory'] = $this->promorules->getAllPromoCategory();
		// $data['promoRules'] = $this->promorules->getAvailablePromoruleList();
		$sortSetting = $this->utils->getConfig('promo_application_list_default_sort');
		if($this->utils->isEnabledFeature('only_manually_add_active_promotion')){
			$data['promoCms'] = $this->promorules->getAvailablePromoCMSList(true, $sortSetting['sort'], $sortSetting['orderBy']);
		}else{
			$data['promoCms'] = $this->promorules->getAllPromoCMSList(true, $sortSetting['sort'], $sortSetting['orderBy']);
		}
		$dt=new DateTime('-3 months');

		//load all deposit transaction without applying promo
		$data['transaction_list']=$this->transactions->getDepositWithoutApplyingPromoList($playerId, $this->utils->formatDateTimeForMysql($dt), $this->utils->getNowForMysql());

		$data['double_submit_hidden_field']=$this->initDoubleSubmitAndReturnHiddenFieldForAdmin($userId);

		// $this->session->set_flashdata('prevent_refresh', true);
		// $is_own_page= $is_own_page=='true';

		// $data['return_url']=site_url('/payment_management/manually_add_bonus/'.$playerId);

		// $data['is_own_page'] = $is_own_page ;
		$this->template->add_css('resources/third_party/bootstrap-multiselect-master/dist/css/bootstrap-multiselect.css');
        $this->template->add_js('resources/third_party/bootstrap-multiselect-master/dist/js/bootstrap-multiselect.js');
		$this->loadTemplate(lang('Marketing Management'), '', '', 'marketing');
		$this->template->write_view('sidebar', 'marketing_management/sidebar');
		$this->template->write_view('main_content', 'marketing_management/view_add_bonus', $data);
		$this->template->render();

	}

	public function post_manually_add_bonus(){
		if (!$this->permissions->checkPermissions('manually_add_bonus')) {
			return $this->error_access();
		}

		$userId=$this->authentication->getUserId();
		$player_id=$this->input->post('player_id');

		if(!$this->verifyAndResetDoubleSubmitForAdmin($userId)){

			$message = lang('Please refresh and try, and donot allow double submit');
			$this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);
			redirect('/marketing_management/manually_add_bonus/'.$player_id);
			return;

		}

        $this->form_validation->set_rules('reason', lang('pay.reason'), 'trim|required|xss_clean|strip_tags');
        if($this->form_validation->run() == false){
            $error_msg = validation_errors();
            $this->alertMessage(self::MESSAGE_TYPE_ERROR, $error_msg);
            return redirect('/marketing_management/manually_add_bonus/'.$player_id);
        }

		$this->load->model(array('transactions', 'external_system', 'player_model', 'users', 'player_promo', 'promorules','withdraw_condition', 'wallet_model'));

		$player_name=$this->input->post('username');
		// $deposit_amt_condition = $this->input->post('depositAmtCondition') ? $this->input->post('depositAmtCondition') : null;
		if(empty($player_id)){
			$player_id=$this->player_model->getPlayerIdByUsername($player_name);
		}else{
			$player_name=$this->player_model->getUsernameById($player_id);
		}

		$promoCmsSettingId = $this->input->post('promo_cms_id');
		$promoRuleId  = $this->promorules->getPromorulesIdByPromoCmsId($promoCmsSettingId);
		$promorule=$this->promorules->getPromoRuleRow($promoRuleId);
		if(!empty($promoCmsSettingId) && !empty($promorule)){

			$controller=$this;
			$message=lang('Add Bonus Failed');
			$success=$this->lockAndTransForPlayerBalance($player_id, function()
					use($controller, $player_id, $player_name, &$message) {

			$current_timestamp = $controller->utils->getNowForMysql();

			//get logged user
			$adminUserId=$controller->authentication->getUserId();
			$adminUsername=$controller->authentication->getUsername();

			$promoCmsSettingId = $controller->input->post('promo_cms_id');
			$promoRuleId  = $controller->promorules->getPromorulesIdByPromoCmsId($promoCmsSettingId);
			$release_date=$controller->input->post('release_date');

			//set promo category
			$promo_category = null;
			if (!empty($promoRuleId)) {
				$promorule = $controller->promorules->getPromoRuleRow($promoRuleId);
				$promo_category = $promorule['promoCategory'];
			}

			$action_name = 'Add';
			$adjustment_type = Transactions::ADD_BONUS;

			$status = $controller->input->post('status');
			$amount = $controller->input->post('amount');
			$betTimes = $controller->input->post('betTimes');
			$deposit_amt_condition=$controller->input->post('depositAmtCondition');
			$reason=$controller->input->post('reason');
			$show_in_front_end=$controller->input->post('show_in_front_end');
			// $withdraw_condition_amount=$deposit_amount * $betTimes;

            $role_id = $this->users->getRoleIdByUserId($adminUserId);
            $limit_by_role = $this->utils->getConfig('limit_manual_adjustment_by_roles');
            if(array_key_exists($role_id, $limit_by_role)) {

                if($amount > $limit_by_role[$role_id]['max_amount_for_add_bonus']) {
                    $message = sprintf(lang('manual_adjust.max_add_bonus'), $limit_by_role[$role_id]['max_amount_for_add_bonus']);
                    return false;

                }
                $transactions_by_user = $this->transactions->getTransactionTotalByTransactionTypesAndDayAndUserId([transactions::ADD_BONUS], date('Y-m-d'), $adminUserId);
                $manual_add_bonus_today = !empty($transactions_by_user[transactions::ADD_BONUS]) ? $transactions_by_user[transactions::ADD_BONUS] : 0;
                if(($manual_add_bonus_today + $amount) > $limit_by_role[$role_id]['max_daily_add_bonus']) {
                    $message = lang('manual_adjust.max_daily_add_bonus');
                    return false;
                }

            }


			$deductDeposit = $controller->input->post('deductDeposit');
			if ($deductDeposit) {
				$condition = (($amount + $deposit_amt_condition) * $betTimes) - $deposit_amt_condition;
				$condition_desc='(bonus '.$amount.' + deposit '.$deposit_amt_condition.') x '.$betTimes.' - depost '.$deposit_amt_condition;
			} else {
				$condition = ($amount + $deposit_amt_condition) * $betTimes;
				$condition_desc='(bonus '.$amount.' + deposit '.$deposit_amt_condition.') x '.$betTimes;
			}

			if($condition <= 0){
                $condition = 0;
            }

			$transaction_id=$this->input->post('transaction_id');

			$note = 'add bonus '.number_format($amount, 2).' to '.$player_name.' by '.$adminUsername.', with deposit condition of ' . $condition;
            $extra_info = ['order_generated_by' => Player_promo::ORDER_GENERATED_BY_SBE_ADD_BONUS];

			#if want pending, don't create transaction, only create player promo
			if($status == Player_promo::TRANS_STATUS_REQUEST ){
				$player_promo_id=$controller->player_promo->requestPromoToPlayer($player_id, $promoRuleId, $amount, $promoCmsSettingId, null, $deposit_amt_condition,
					$condition , Player_promo::TRANS_STATUS_REQUEST, $betTimes, $reason, null, null, $extra_info);

				if(!empty($player_promo_id) && !empty($transaction_id)){
					$controller->transactions->updatePlayerPromoId($transaction_id, $player_promo_id, $promo_category);
				}

				$success=!empty($player_promo_id);

				if(!$success){
					$message=lang('Request Promotion Failed');
				}else{
					$message=lang('Request Promotion Successfully');
				}
			}else{
				$transaction = $this->transactions->createAdjustmentTransaction($adjustment_type,
					$adminUserId, $player_id, $amount, null, $note, null,
					$promo_category, $show_in_front_end, $reason, $promoRuleId, null, Transactions::MANUALLY_ADJUSTED);

				$success=!!$transaction;
				if($success){
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
						// 'show_flag' => $show_in_front_end == '1',
					));

					//save to player promo
					$playerBonusAmount = $amount;
					$player_promo_id = $controller->player_promo->approvePromoToPlayer($player_id, $promoRuleId, $playerBonusAmount,
						$promoCmsSettingId, $adminUserId, null, $condition, $extra_info, $deposit_amt_condition, $betTimes,$reason );

					//move to withdraw_condition
					$promorule=$this->promorules->getPromoruleById($promoRuleId);
					$bonusTransId=$transaction['id'];
					$controller->withdraw_condition->createWithdrawConditionForManual($player_id, $bonusTransId,
							$condition, $deposit_amt_condition, $amount, $betTimes, $promorule,$reason, $player_promo_id);

					//update player promo id of transaction
					$controller->transactions->updatePlayerPromoId($transaction['id'], $player_promo_id, $promo_category);
					$controller->transactions->updatePlayerPromoId($transaction_id, $player_promo_id, $promo_category);
					// }
					$success=true;
					$message=lang('Add Bonus Successfully');
				}else{
					$message=lang('Add Bonus Failed');
				}
			}

			if(!empty($player_promo_id)){
                //add requestAdmin
                $this->player_promo->addPlayerPromoRequestBy($player_promo_id, $adminUserId, null);
            }

			return $success;

			});
		}else{
			$success=false;
			$message=lang('lost promo id');
		}

		$this->saveAction(self::MANAGEMENT_TITLE, 'Add Bonus', $message);

		if($success){
			$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, $message);
			redirect('/player_management/userInformation/'.$player_id);
		}else{
			$this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);
			redirect('/marketing_management/manually_add_bonus/'.$player_id);
		}

	}

	public function manually_batch_bonus(){
		if (!$this->permissions->checkPermissions('manually_batch_bonus')) {
			return $this->error_access();
		}

		$this->load->model(array('promorules', 'external_system', 'player_model'));
		$data = array();
		if($this->utils->isEnabledFeature('only_manually_add_active_promotion')){
			$data['promoCms'] = $this->promorules->getAvailablePromoCMSList();
		}else{
			$data['promoCms'] = $this->promorules->getAllPromoCMSList();
		}

		$allowed_csv_max_size = ' <= 10mb';
		$upload_promo_csv_max_row = $this->utils->getConfig('upload_promo_csv_max_row');
		$data['csv_note'] = sprintf(lang("%s size of csv could be uploaded."), $allowed_csv_max_size);
		$this->loadTemplate(lang('Marketing Management'), '', '', 'marketing');
		// $this->template->write_view('sidebar', 'marketing_management/sidebar');
		// $this->template->write_view('main_content', 'marketing_management/view_batch_add_bonus', $data);
		$this->load->view('marketing_management/view_batch_add_bonus', $data);
		// $this->template->render();
	}

    public function manually_batch_subtract_bonus(){
        if (!$this->permissions->checkPermissions('manually_batch_subtract_bonus')) {
            return $this->error_access();
        }

        $allowed_csv_max_size = ' <= 10mb';
        $upload_promo_csv_max_row = $this->utils->getConfig('upload_promo_csv_max_row');
        $data['csv_note'] = sprintf(lang("%s size of csv could be uploaded."), $allowed_csv_max_size);
        $this->loadTemplate(lang('Marketing Management'), '', '', 'marketing');
        $this->load->view('marketing_management/view_batch_subtract_bonus', $data);
    }

	public function post_manually_batch_bonus(){
		if (!$this->permissions->checkPermissions('manually_batch_bonus')) {
			return $this->error_access();
		}

		$this->load->model(array('transactions', 'external_system', 'player_model', 'users', 'player_promo', 'promorules','withdraw_condition', 'wallet_model'));

		$path='/tmp';
		$random_csv=random_string('unique').'.csv';

		$config['upload_path'] = $path;
		$config['allowed_types'] = '*';
		$config['max_size'] = $this->utils->getMaxUploadSizeByte();
		$config['remove_spaces'] = true;
		$config['overwrite'] = true;
		$config['file_name'] = $random_csv;
		$config['max_width'] = '';
		$config['max_height'] = '';
		$this->load->library('upload', $config);
		$this->upload->initialize($config);

		$do_run = $this->upload->do_upload('csv_file');

		if ($do_run) {

			$csv_file_data = $this->upload->data();

			//process cvs file
			$this->utils->debug_log('upload csv_file_data', $csv_file_data);

			//not allow excel
			$file_ext=$csv_file_data['file_ext'];
			$not_allow_types=['xsl', 'xslx'];

			if(in_array($file_ext, $not_allow_types)){
				$this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('Note: Upload file format must be CSV'));
				return redirect('/marketing_management/manually_batch_bonus');
			}
		}

		if($do_run){

			//get logged user
	 		$admin_user_id=$this->authentication->getUserId();

			$csv_fullpath=$csv_file_data['full_path'];
			$csv_filename=$csv_file_data['client_name'].time();
			$exists=false;

			//write to history, lock all
			$success=$this->lockAndTrans(Utils::LOCK_ACTION_BATCH_ADD_BONUS, 0, function()
				use($admin_user_id, $csv_fullpath, $csv_filename, &$exists){
					$success=$this->transactions->checkAndAddUploadCSVHistory($admin_user_id,$csv_filename, $csv_fullpath,
						Transactions::CSV_TYPE_BATCH_ADD_BONUS, $exists);
					return $success;
				}
			);

			if($exists){
				$message=lang('Cannot upload duplicate csv file').' '.$csv_filename;
				//block it
				$this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);
				redirect('/marketing_management/manually_batch_bonus');
			}

			if(!$success){
				$message=lang('Write upload history failed');
				//block it
				$this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);
				redirect('/marketing_management/manually_batch_bonus');
			}

			$this->load->library(['lib_queue']);
            //add it to queue job
			$callerType=Queue_result::CALLER_TYPE_ADMIN;
			$caller=$this->authentication->getUserId();
			$state='';

			$charset_code = 2;

            //copy file to sharing private
			$success=$this->utils->copyFileToSharingPrivate($csv_file_data['full_path'], $target_file_path, $charset_code);

			$this->utils->debug_log($csv_file_data['full_path'].' to '.$target_file_path, $success);

			if($success){

				$promoCmsSettingId = intval($this->input->post('promo_cms_id'));
				$release_date=$this->input->post('release_date');// not used
				$status = intval($this->input->post('status'));
				$reason=$this->input->post('reason');
                $show_in_front_end=false; //$this->input->post('show_in_front_end')=='1'; //not used

                $token=$this->lib_queue->addRemoteBatchAddBonusJob(basename($target_file_path),$promoCmsSettingId,
                	$release_date, $status, $reason, $show_in_front_end, $callerType, $caller, $state);

                $success=!empty($token);
                if(!$success){
                	$message=lang('Create batch job failed');
                }else{
                    //redirect to queue
                	redirect('/marketing_management/manually_batch_add_bonus_result/'.$token);
                }
            }else{
            	$message=lang('Copy file failed');
            }

            if(!$success){
            	$message=lang('Add Bonus Failed');
            	$this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);
            	redirect('/marketing_management/manually_batch_bonus');
            }

        }else{
            //failed
        	$success=false;
        	$message=lang('Upload CSV Failed')."\n".$this->upload->display_errors();
        }

        if($success){

        	$this->utils->debug_log('==============post_manually_batch_bonus->success');

        	$data['success_amount'] = $success_amount;
        	$data['success_count'] = count($success_usernames);
        	$data['failed_count'] = count($failed_usernames);
        	$data['failed_usernames'] = $failed_usernames;
        	$this->loadTemplate(lang('Marketing Management'), '', '', 'marketing');
        	$this->template->write_view('sidebar', 'marketing_management/sidebar');
        	$this->template->write_view('main_content', 'marketing_management/promorules/post_manually_batch_bonus_result', $data);
        	$this->template->render();

        	$this->utils->debug_log('==============post_manually_batch_bonus->render');
        }else{
        	$this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);
        	redirect('/marketing_management/manually_batch_bonus');
        }
    }

    public function post_manually_batch_subtract_bonus(){
        if (!$this->permissions->checkPermissions('manually_batch_subtract_bonus')) {
            return $this->error_access();
        }

        $filepath = '';
        $msg = '';
        $_reason = trim($this->input->post('reason'));

        if (empty($_reason)) {
            $this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('Reason field is required'));
            return redirect('/marketing_management/batchBalanceAdjustment');
        }

        $reason = htmlentities($_reason, ENT_QUOTES);

        $uploadFieldName = 'batch_subtract_bonus_csv_file';
        if ($this->existsUploadField($uploadFieldName)) {
            //check file type
            $success = $this->saveUploadFileToRemote($uploadFieldName, ['csv'], $filepath, $msg);
            if (!$success) {
                $this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('Upload csv file failed').', '.$msg);
                return redirect('/marketing_management/batchBalanceAdjustment');
            }
        }

        $file = empty($filepath) ? null : basename($filepath);
        if(empty($file)){
            $this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('Upload csv file failed'));
            return redirect('/marketing_management/batchBalanceAdjustment');
        }

        $this->load->library(['lib_queue']);
        #params
        $callerType = Queue_result::CALLER_TYPE_ADMIN;
        $caller = $this->authentication->getUserId();
        $state = '';

        if (!empty($file)) {
            $token=$this->lib_queue->addRemoteBatchSubtractBonusJob($file, $reason, $callerType, $caller, $state);
            if(!empty($token)){
                $this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang('Create importing job successfully'));
                return redirect('/marketing_management/manually_batch_subtract_bonus_result/'.$token);
            }else{
                $this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('Create importing job failed'));
                return redirect('/marketing_management/batchBalanceAdjustment');
            }
        } else {
            $this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('Upload csv file failed'));
            return redirect('/marketing_management/batchBalanceAdjustment');
        }
    }

    public function manually_batch_subtract_bonus_result($token){
        $data['result_token']=$token;
        $this->loadTemplate(lang('Marketing Management'), '', '', 'marketing');
        $this->template->write_view('sidebar', 'marketing_management/sidebar');
        $this->template->write_view('main_content', 'marketing_management/manually_batch_subtract_bonus_result', $data);
        $this->template->render();
    }

    public function manually_batch_add_bonus_result($token){
    	$data['result_token']=$token;
    	$this->loadTemplate(lang('Marketing Management'), '', '', 'marketing');
    	$this->template->write_view('sidebar', 'marketing_management/sidebar');
    	$this->template->write_view('main_content', 'marketing_management/manually_batch_bonus_result', $data);
    	$this->template->render();
    }

	public function addBonusToPlayer($player_id, $player_name){
		 $controller=$this;

		$success=$this->lockAndTransForPlayerBalance($player_id, function()
		use($controller, $player_id, $player_name, &$message) {

			$current_timestamp = $controller->utils->getNowForMysql();

			//get logged user
			$adminUserId=$controller->authentication->getUserId();
			$adminUsername=$controller->authentication->getUsername();

			$promoCmsSettingId = $controller->input->post('promo_cms_id');
			$promoRuleId  = $controller->promorules->getPromorulesIdByPromoCmsId($promoCmsSettingId);
			$release_date=$controller->input->post('release_date');

			//set promo category
			$promo_category = null;
			if (!empty($promoRuleId)) {
				$promorule = $controller->promorules->getPromoRuleRow($promoRuleId);
				$promo_category = $promorule['promoCategory'];
			}

			$action_name = 'Add';
			$adjustment_type = Transactions::ADD_BONUS;

			$status = $controller->input->post('status');
			$amount = $controller->input->post('amount');
			$betTimes = $controller->input->post('betTimes');
			$deposit_amt_condition=$controller->input->post('depositAmtCondition');
			$reason=$controller->input->post('reason');
			$show_in_front_end=$controller->input->post('show_in_front_end');
			// $withdraw_condition_amount=$deposit_amount * $betTimes;
			$deductDeposit = $controller->input->post('deductDeposit');
			if ($deductDeposit) {
				$condition = (($amount + $deposit_amt_condition) * $betTimes) - $deposit_amt_condition;
				$condition_desc='(bonus '.$amount.' + deposit '.$deposit_amt_condition.') x '.$betTimes.' - depost '.$deposit_amt_condition;
			} else {
				$condition = ($amount + $deposit_amt_condition) * $betTimes;
				$condition_desc='(bonus '.$amount.' + deposit '.$deposit_amt_condition.') x '.$betTimes;
			}

			$note = 'add bonus '.number_format($amount, 2).' to '.$player_name.' by '.$adminUsername.', with deposit condition of ' . $condition;

			#if want pending, don't create transaction, only create player promo
			if($status == Player_promo::TRANS_STATUS_REQUEST ){
				// request promo
				$success=!!$controller->player_promo->requestPromoToPlayer($player_id, $promoRuleId, $amount, $promoCmsSettingId, $adminUserId, $deposit_amt_condition,
					$condition , Player_promo::TRANS_STATUS_REQUEST, $betTimes, $reason );
				if(!$success){
					$message=lang('Request Promotion Failed');
				}else{
					$message=lang('Request Promotion Successfully');
				}
			}else{
				$transaction = $this->transactions->createAdjustmentTransaction($adjustment_type,
					$adminUserId, $player_id, $amount, null, $note, null,
					$promo_category, $show_in_front_end, $reason, $promoRuleId);

				$success=!!$transaction;
				if($success){
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
						// 'show_flag' => $show_in_front_end == '1',
					));

					//move to withdraw_condition
					$promorule=$this->promorules->getPromoruleById($promoRuleId);
					$bonusTransId=$transaction['id'];
					$controller->withdraw_condition->createWithdrawConditionForManual($player_id, $bonusTransId,
						$condition, $deposit_amt_condition, $amount, $betTimes, $promorule,$reason);

					//save to player promo
					$playerBonusAmount = $amount;
					$extra_info=[];
					$player_promo_id = $controller->player_promo->approvePromoToPlayer($player_id, $promoRuleId, $playerBonusAmount,
						$promoCmsSettingId, $adminUserId, null, $condition, $extra_info, $deposit_amt_condition, $betTimes,$reason );
					//update player promo id of transaction
					$controller->transactions->updatePlayerPromoId($transaction['id'], $player_promo_id, $promo_category);
					// }
					$success=true;
					$message=lang('Add Bonus Successfully');
				}else{
					$message=lang('Add Bonus Failed');
				}
			}

			return $success;

		});
	}

	public function ajax_promo_setting_list(){
		try{
			$this->load->model(array('promorules'));
			$conditions = !empty($this->input->post('conditions'))? $this->input->post('conditions') : [];
			$search = $this->switch_condition_keys_for_promo_setting_list($conditions);
			if(count($conditions) !== count($search)){
        		throw new Exception('search keys are wrong, check conditions again');
        	}
			$sortSetting = $this->utils->getConfig('promo_application_list_default_sort');
			$hide_system = !$this->utils->getConfig('enabled_display_system_promo');
			$promoSettingList = $this->promorules->getPromoSettingList($sortSetting['sort'], null, null, false, $sortSetting['orderBy'], $search, $hide_system);
        	return $this->returnJsonResult(array('status' => 'success', 'data' => $promoSettingList, 'message' => 'success'));
		} catch (Exception $e) {
			return $this->returnJsonResult(array('status' => 'error', 'message' => $e->getMessage()));
		}
	}

    public function ajax_countAllStatusOfPromoApplication(){
		try{
            $this->load->model(array('player_promo'));
            $_date_from = $this->input->post('date_from');
		    $_date_to = $this->input->post('date_to');
            $_force_refresh = $this->input->post('force_refresh');

            if( ! isset($_POST['ttl']) ){
                $ttl = 30; // default
            }else{
                $ttl = $this->input->post('ttl');
            }

            if( !empty($_force_refresh) ){
                $_cacheKey = $this->player_promo->getCacheKey4CountAllStatusOfPromoApplication($_date_from, $_date_to);
                $this->utils->deleteCache($_cacheKey);
            }
            $_countAllStatus = $this->player_promo->countAllStatusOfPromoApplication($_date_from, $_date_to, $ttl);

        	return $this->returnJsonResult(array('status' => 'success', 'data' => $_countAllStatus, 'message' => 'success'));
		} catch (Exception $e) {
			return $this->returnJsonResult(array('status' => 'error', 'message' => $e->getMessage()));
		}
	}

	public function switch_condition_keys_for_promo_setting_list($conditions = []){
		$result = [];
		if(is_array($conditions)){
			foreach ($conditions as $key => $value) {
				switch ($key) {
					case 'onlyShowActive':
						$result['promocmssetting.status'] = 'active';
				}
			}
		}
		return $result;
	}
}
////END OF FILE/////////