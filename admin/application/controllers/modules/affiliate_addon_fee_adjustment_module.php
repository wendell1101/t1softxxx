<?php
trait affiliate_addon_fee_adjustment_module
{
    //platform fee adjustment
    public function affiliate_addon_platform_fee_adjustment($affiliate_commission_id, $yearmonth, $affiliate_id)
    {
        $addon_platform_fee = $this->affiliatemodel->getAddonAffiliatePlatformFee($affiliate_id, $yearmonth);
        $data['affiliate_commission_id'] = $affiliate_commission_id;
        $data['affiliate_id'] = $affiliate_id;
        $data['yearmonth'] = $yearmonth;
        $data['addon_platform_fee'] = $addon_platform_fee;

        $this->load->view('affiliate_management/earnings/affiliate_addon_platform_fee_adjustment', $data);
    }

    public function batch_affiliate_addon_platform_fee_adjustment()
    {
        $data=[];
        $earningids = $this->input->post('earningids')?:[];
        $aff_editable_list = [];

        if (!empty($earningids)) {
            foreach ($earningids as $key => $earningid) {
                $affiliate_commission_record = $this->affiliate_earnings->getAffiliateMonthlyCommission($earningid);
                $aff = $this->affiliatemodel->getAffiliateById($affiliate_commission_record['affiliate_id']);
                if (!empty($aff)) {
                    $aff['earningid'] = $earningid;
                    $aff['addon_platform_fee'] = $this->affiliatemodel->getAddonAffiliatePlatformFee($affiliate_commission_record['affiliate_id'], $affiliate_commission_record['year_month']);
                    array_push($aff_editable_list, $aff);
                }
            }
        }

        $data['earningids'] = $earningids;
        $data['yearmonth'] = $this->input->post('yearmonth');
        $data['aff_editable_list'] = $aff_editable_list;
        $this->load->view('affiliate_management/earnings/batch_affiliate_addon_platform_fee_adjustment', $data);
    }

    public function updatePlatformFeeForOne($affiliate_commission_id)
    {
        $this->load->model(['affiliate_earnings']);
        $success = false;
        if (!empty($affiliate_commission_id)) {
            $affiliate_commission_record = $this->affiliate_earnings->getAffiliateMonthlyCommission($affiliate_commission_id);
            $new_addon_platform_fee = $this->input->post('new_addon_platform_fee');
            $yearmonth = $affiliate_commission_record['year_month'];
            $affiliate_id = $affiliate_commission_record['affiliate_id'];
            $self = $this;
            $success = $this->lockAndTransForAffiliateBalance($affiliate_id, function () use ($self, $affiliate_id, $yearmonth, $new_addon_platform_fee) {
                $success = $self->affiliatemodel->updateAddonPlatformFee($affiliate_id, $yearmonth, $new_addon_platform_fee);
                return $success;
            });

            if ($success) {
                $success = false;
                $aff = $this->affiliatemodel->getAffiliateById($affiliate_id);
                $affiliate_username = $aff['username'];
                $success = $this->affiliate_commission->generate_monthly_earnings_for_all($yearmonth, $affiliate_username, null, null, 0, false);
            }
        }
        if ($success) {
            $this->alertMessage(Affiliate_management::MESSAGE_TYPE_SUCCESS, lang('Update Successfully'));
        } else {
            $this->alertMessage(Affiliate_management::MESSAGE_TYPE_ERROR, lang('Update Failed'));
        }
        redirect($this->agent->referrer());
    }

    public function batchUpdateAddonPlatformFee()
    {
        $update_data = $this->input->post();

        $this->load->library(['lib_queue', 'language_function']);
        $this->load->model(['queue_result']);

        $caller=$this->authentication->getUserId();
        $state=null;
        $lang=$this->language_function->getCurrentLanguage();
        $funcName = 'calculate_selected_aff_monthly_earnings';
        $callerType = Queue_result::CALLER_TYPE_ADMIN;
        $systemId = Queue_result::SYSTEM_UNKNOWN;

        $params = [
            'type' => 'addon_platform_fee',
            'update_data' => $update_data
        ];
        $token=$this->lib_queue->commonAddRemoteJob($systemId, $funcName, $params, $callerType, $caller, $state, $lang);
        if ($token) {
            redirect('/system_management/common_queue/'.$token);
        } else {
            $this->alertMessage(Affiliate_management::MESSAGE_TYPE_ERROR, lang('Update Failed'));
            redirect($this->agent->referrer());
        }
    }

    public function viewBatchAddonPlatformFeeAdjustment()
    {
        if (!$this->permissions->checkPermissions('adjust_addon_affiliates_platform_fee') || !$this->utils->isEnabledFeature('enable_addon_affiliate_platform_fee')) {
            $this->error_access();
        } else {
            $data['year_month_list'] = $this->affiliate_earnings->getYearMonthListToNow_2();

            $this->loadTemplate(lang('Batch Addon Platform Fee Adjustment'), '', '', 'affiliate');
            $this->template->write_view('main_content', 'affiliate_management/earnings/view_batch_addon_platform_fee_adjustment', $data);
            $this->template->render();
        }
    }

    public function post_batch_addon_platform_fee_adjustment()
    {
        if (!$this->permissions->checkPermissions('adjust_addon_affiliates_platform_fee') || !$this->utils->isEnabledFeature('enable_addon_affiliate_platform_fee')) {
            return $this->error_access();
        }
        $uploadFieldName = 'batch_addon_platform_fee_adjustment_csv_file';

        $filepath='';
        $msg='';
        if (empty(trim($this->input->post('reason')))) {
            $this->alertMessage(Affiliate_management::MESSAGE_TYPE_ERROR, lang('Reason field is required'));
            redirect('/affiliate_management/viewBatchAddonPlatformFeeAdjustment');
        }
        if ($this->existsUploadField($uploadFieldName)) {
            //check file type
            if ($this->saveUploadFileToRemote($uploadFieldName, ['csv'], $filepath, $msg)) {
                //get $filepath
                //echo 'uploaded';
            } else {
                $message=lang('Upload csv file failed').', '.$msg;
                return false;
            }
        }
        
        $this->load->library(['lib_queue']);
        $callerType=Queue_result::CALLER_TYPE_ADMIN;
        $caller=$this->authentication->getUserId();
        $state=null;
        $lang=$this->language_function->getCurrentLanguage();
        $file= empty($filepath) ? null : basename($filepath);
        //save csv file
        #params
        $adminUserId=$this->authentication->getUserId();
        $adminUsername=$this->authentication->getUsername();
        $reason=$this->input->post('reason');
        $yearmonth=$this->input->post('year_month');

        if (!empty($file)) {
            $token=$this->lib_queue->addRemoteBatchAddonPlatformFeeAdjustment($file, $adminUserId, $adminUsername, $reason, $yearmonth, $callerType, $caller, $state, $lang);

            if (!empty($token)) {
                $this->alertMessage(Affiliate_management::MESSAGE_TYPE_SUCCESS, lang('Create importing job successfully'));
                return redirect('affiliate_management/post_batch_addon_platform_fee_adjustment_result/'.$token);
            } else {
                $this->alertMessage(Affiliate_management::MESSAGE_TYPE_ERROR, lang('Create importing job failed'));
                redirect('/affiliate_management/viewBatchAddonPlatformFeeAdjustment');
            }
        } else {
            $this->alertMessage(Affiliate_management::MESSAGE_TYPE_ERROR, lang('Upload csv file failed'));
            redirect('/affiliate_management/viewBatchAddonPlatformFeeAdjustment');
        }
    }

    public function post_batch_addon_platform_fee_adjustment_result($token)
    {
        $data['result_token']=$token;
        $this->loadTemplate(lang('Batch Addon Platform Fee Adjustment'), '', '', 'affiliate');
        // $this->template->write_view('sidebar', 'affiliate_management/sidebar');
        $this->template->write_view('main_content', 'affiliate_management/earnings/view_batch_addon_platform_fee_adjustment_result', $data);
        $this->template->render();
    }











    //player benefit fee adjustment
    public function affiliate_player_benefit_fee_adjustment($affiliate_commission_id, $yearmonth, $affiliate_id)
    {
        $player_benefit_fee = $this->affiliatemodel->getPlayerBenefitFee($affiliate_id, $yearmonth);
        $data['affiliate_commission_id'] = $affiliate_commission_id;
        $data['affiliate_id'] = $affiliate_id;
        $data['yearmonth'] = $yearmonth;
        $data['player_benefit_fee'] = $player_benefit_fee;

        $this->load->view('affiliate_management/affiliate_player_benefit_fee_adjustment', $data);
    }
    public function batch_affiliate_player_benefit_fee_adjustment()
    {
        $data=[];
        $earningids = $this->input->post('earningids')?:[];
        $aff_editable_list = [];

        if (!empty($earningids)) {
            foreach ($earningids as $key => $earningid) {
                $affiliate_commission_record = $this->affiliate_earnings->getAffiliateMonthlyCommission($earningid);
                $aff = $this->affiliatemodel->getAffiliateById($affiliate_commission_record['affiliate_id']);
                if (!empty($aff)) {
                    $aff['earningid'] = $earningid;
                    $aff['player_benefit_fee'] = $this->affiliatemodel->getPlayerBenefitFee($affiliate_commission_record['affiliate_id'], $affiliate_commission_record['year_month']);
                    array_push($aff_editable_list, $aff);
                }
            }
        }

        $data['earningids'] = $earningids;
        $data['yearmonth'] = $this->input->post('yearmonth');
        $data['aff_editable_list'] = $aff_editable_list;
        $this->load->view('affiliate_management/batch_affiliate_player_benefit_fee_adjustment', $data);
    }

    public function updatePlayerBenefitFeeForOne($affiliate_commission_id)
    {
        if (!$this->permissions->checkPermissions('adjust_player_benefit_fee') || !$this->utils->isEnabledFeature('enable_player_benefit_fee')) {
            $this->alertMessage(BaseController::MESSAGE_TYPE_ERROR, lang('role.nopermission'));
            redirect($this->agent->referrer());
        }

        $this->load->model(['affiliate_earnings']);
        $success = false;
        if (!empty($affiliate_commission_id)) {
            $affiliate_commission_record = $this->affiliate_earnings->getAffiliateMonthlyCommission($affiliate_commission_id);
            $player_benefit_fee = $this->input->post('player_benefit_fee');
            $yearmonth = $this->utils->safeGetArray($affiliate_commission_record, 'year_month');
            $affiliate_id = $this->utils->safeGetArray($affiliate_commission_record, 'affiliate_id');
            if(empty($yearmonth) || empty($affiliate_id) || empty($player_benefit_fee)) {
                $this->alertMessage(BaseController::MESSAGE_TYPE_ERROR, lang('Update Failed'));
                redirect($this->agent->referrer());
            }
            $self = $this;
            $success = $this->lockAndTransForAffiliateBalance($affiliate_id, function () use ($self, $affiliate_id, $yearmonth, $player_benefit_fee) {
                $success = $self->affiliatemodel->updatePlayerBenefitFee($affiliate_id, $yearmonth, $player_benefit_fee);
                return $success;
            });
            
            if ($success) {
                $aff = $this->affiliatemodel->getAffiliateById($affiliate_id);
                $affiliate_username = $aff['username'];
                $success = false;
                if($this->utils->getConfig('enable_player_benefit_fee_queue')) {
                    $this->alertMessage(BaseController::MESSAGE_TYPE_SUCCESS, lang('Update Processing'));
                    return $this->updatePlayerBenefitFeeForOneByQueue($yearmonth, $affiliate_username, $player_benefit_fee);
                }
                $success = $this->affiliate_commission->generate_monthly_earnings_for_all($yearmonth, $affiliate_username, null, null, 0, false);
            }
        }
        if ($success) {
            $this->alertMessage(BaseController::MESSAGE_TYPE_SUCCESS, lang('Update Successfully'));
        } else {
            $this->alertMessage(BaseController::MESSAGE_TYPE_ERROR, lang('Update Failed'));
        }
        redirect($this->agent->referrer());
    }

    public function updatePlayerBenefitFeeForOneByQueue($yearmonth, $affiliate_username, $player_benefit_fee) {
        $result = [
            'success' => false,
            'message' => lang('Update Failed')
        ];
        $this->load->model(['affiliate_earnings']);
		$this->load->library(['lib_queue', 'authentication']);
		$this->load->model(['queue_result']);
		$caller = $this->authentication->getUserId();
		$operator = $this->authentication->getUsername();
		$state = null;
		$callerType = Queue_result::CALLER_TYPE_ADMIN;
		$params = [
            'yearmonth' => $yearmonth,
            'affiliate_username' => $affiliate_username,
            'player_benefit_fee' => $player_benefit_fee,
            'operator' => $operator,
		];
        $token = $this->lib_queue->addUpdatePlayerBenefitFeeJob($params, $callerType, $caller, $state);
        if ($token) {
            redirect('/system_management/common_queue/'.$token);
		} else {
            $this->alertMessage(BaseController::MESSAGE_TYPE_ERROR, lang('Update Failed'));
        }
        redirect($this->agent->referrer());
    }

    public function batchUpdatePlayerBenefitFee()
    {
        $update_data = $this->input->post();

        $this->load->library(['lib_queue', 'language_function']);
        $this->load->model(['queue_result']);

        $caller=$this->authentication->getUserId();
        $state=null;
        $lang=$this->language_function->getCurrentLanguage();
        $funcName = 'calculate_selected_aff_monthly_earnings';
        $callerType = Queue_result::CALLER_TYPE_ADMIN;
        $systemId = Queue_result::SYSTEM_UNKNOWN;

        $params = [
            'type' => 'player_benefit_fee',
            'update_data' => $update_data
        ];
        $token=$this->lib_queue->commonAddRemoteJob($systemId, $funcName, $params, $callerType, $caller, $state, $lang);
        if ($token) {
            redirect('/system_management/common_queue/'.$token);
        } else {
            $this->alertMessage(Affiliate_management::MESSAGE_TYPE_ERROR, lang('Update Failed'));
            redirect($this->agent->referrer());
        }
    }

    public function viewBatchBenefitFeeAdjustment()
    {
        if (!$this->permissions->checkPermissions('adjust_player_benefit_fee') || !$this->utils->isEnabledFeature('enable_player_benefit_fee')) {
            $this->error_access();
        } else {
            $data['year_month_list'] = $this->affiliate_earnings->getYearMonthListToNow_2();

            $this->loadTemplate(lang('Batch Benefit Fee Adjustment'), '', '', 'affiliate');
            $this->template->write_view('main_content', 'affiliate_management/view_batch_benefit_fee_adjustment', $data);
            $this->template->render();
        }
    }

    public function post_batch_benefit_fee_adjustment()
    {
        if (!$this->permissions->checkPermissions('adjust_player_benefit_fee') || !$this->utils->isEnabledFeature('enable_player_benefit_fee')) {
            return $this->error_access();
        }
        $uploadFieldName = 'batch_benefit_fee_adjustment_csv_file';

        $filepath='';
        $msg='';
        if (empty(trim($this->input->post('reason')))) {
            $this->alertMessage(Affiliate_management::MESSAGE_TYPE_ERROR, lang('Reason field is required'));
            redirect('/affiliate_management/viewBatchBenefitFeeAdjustment');
        }
        if ($this->existsUploadField($uploadFieldName)) {
            //check file type
            if ($this->saveUploadFileToRemote($uploadFieldName, ['csv'], $filepath, $msg)) {
                //get $filepath
                //echo 'uploaded';
            } else {
                $message=lang('Upload csv file failed').', '.$msg;
                return false;
            }
        }
        
        $this->load->library(['lib_queue']);
        $callerType=Queue_result::CALLER_TYPE_ADMIN;
        $caller=$this->authentication->getUserId();
        $state=null;
        $lang=$this->language_function->getCurrentLanguage();
        $file= empty($filepath) ? null : basename($filepath);
        //save csv file
        #params
        $adminUserId=$this->authentication->getUserId();
        $adminUsername=$this->authentication->getUsername();
        $reason=$this->input->post('reason');
        $yearmonth=$this->input->post('year_month');

        if (!empty($file)) {
            $token=$this->lib_queue->addRemoteBatchBenefitFeeAdjustment($file, $adminUserId, $adminUsername, $reason, $yearmonth, $callerType, $caller, $state, $lang);

            if (!empty($token)) {
                $this->alertMessage(Affiliate_management::MESSAGE_TYPE_SUCCESS, lang('Create importing job successfully'));
                return redirect('affiliate_management/post_batch_benefit_fee_adjustment_result/'.$token);
            } else {
                $this->alertMessage(Affiliate_management::MESSAGE_TYPE_ERROR, lang('Create importing job failed'));
                redirect('/affiliate_management/viewBatchBenefitFeeAdjustment');
            }
        } else {
            $this->alertMessage(Affiliate_management::MESSAGE_TYPE_ERROR, lang('Upload csv file failed'));
            redirect('/affiliate_management/viewBatchBenefitFeeAdjustment');
        }
    }

    public function post_batch_benefit_fee_adjustment_result($token)
    {
        $data['result_token']=$token;
        $this->loadTemplate(lang('Batch Benefit Fee Adjustment'), '', '', 'affiliate');
        // $this->template->write_view('sidebar', 'affiliate_management/sidebar');
        $this->template->write_view('main_content', 'affiliate_management/view_batch_benefit_fee_adjustment_result', $data);
        $this->template->render();
    }
}
