<?php

/**
 * uri: see routes.php /payment-methods, /payment-requests, /withdraw-conditions, /withdraw-requests
 *
 * @property playerapi_lib $playerapi_lib
 * @property Playerapi_model $playerapi_model
 */
trait player_responsible_game_module {

    public function responsible_game($action, $additional=null, $append=null){
        if(!$this->initApi()){
            return;
        }

        $this->load->library(['playerapi_lib', 'player_library', 'player_responsible_gaming_library']);
        $this->load->model(['comapi_reports', 'playerapi_model']);
        $rg_data = $this->player_responsible_gaming_library->getActiveResponsibleGamingSettings($this->player_id);

        $request_method = $this->input->server('REQUEST_METHOD');

        switch ($action) {
            case 'apply':
                if($request_method == 'POST'){
                    if($additional == 'self-exclusion') {
                        return $this->postSelfExclusion($this->player_id, $rg_data);
                    }
                    if($additional == 'cool-off') {
                        return $this->postCoolOff($this->player_id, $rg_data);
                    }
                    if($additional == 'deposit-limits') {
                        return $this->postDepositLimits($this->player_id);
                    }
                }
                break;
            case 'info':
                if($request_method == 'GET'){
                    return $this->getRespGameStatus($this->player_id);
                }
                break;
        }
        $this->returnErrorWithCode(self::CODE_GENERAL_CLIENT_ERROR);
    }

    protected function rg_format($rg_item, $txt_ar, $txt_default = null) {
        $rg_item_txt = isset($txt_ar[$rg_item]) ? $txt_ar[$rg_item] : $txt_default;
        return $rg_item_txt;
    }

    protected function rg_format_length_unit($lu) {
        return $this->rg_format($lu, [ 1 => 'days' , 2 => 'weeks' , 3 => 'months', 4 => 'unlimited' ], '(unknown)');
    }

    protected function rg_format_status($stat_val, $stat_txt_ar) {
        return $this->rg_format($stat_val, $stat_txt_ar);
    }

    protected function rg_format_self_exclusion_type($se_type) {
        return $this->rg_format($se_type, [ 1 => 'Temporary' , 2 => 'Permanent' ]);
    }
  
    protected function getRespGameStatus($player_id){
        try {
            $validate_fields = [
                ['name' => 'currency', 'type' => 'string', 'required' => false, 'length' => 0],
            ];
            $result=['code'=>Playerapi::CODE_OK];
            $request_body = $this->playerapi_lib->getRequestPramas();
            $currency = $this->utils->safeGetArray($request_body, "currency", $this->currency);
            $this->comapi_log(__METHOD__, '=======request_body', $request_body, 'player_id', $player_id, 'currency', $currency);

            $is_validate_basic_passed = $this->playerapi_lib->validParmasBasic($request_body, $validate_fields);
            $this->comapi_log(__METHOD__, '=======is_validate_basic_passed', $is_validate_basic_passed);

            if(!$is_validate_basic_passed['validate_flag']) {
                throw new \APIException($is_validate_basic_passed['validate_msg'], self::CODE_INVALID_PARAMETER);
            }

            if($this->utils->isEnabledFeature('responsible_gaming')){
                $rg_stat = [];
                $requestSucc = $this->playerapi_lib->switchCurrencyForAction($currency, function() use ($player_id, &$rg_stat, $currency) {
                    $rg_data = $this->player_responsible_gaming_library->fetchResponsibleGamingDataForSBE($player_id, 'player_responsible_game_module');

                    $rg_details = $rg_data['responsible_gaming'];

                    // self exclusion: temporary
                    $temp_self_excl_data = !empty($rg_details['temp_self_exclusion']) ? $rg_details['temp_self_exclusion'] : null;        
                    $self_excl_type = empty($temp_self_excl_data) ? null : Responsible_gaming::SELF_EXCLUSION_TEMPORARY;    
                    $temp_self_excl_stat = empty($temp_self_excl_data) ? null : [
                        'status'    => $this->rg_format_status($temp_self_excl_data->status, $rg_data['statusType']) ,
                        'datetimeFrom'   => $this->playerapi_lib->formatDateTime($temp_self_excl_data->date_from) ,
                        'datetimeTo'   => $this->playerapi_lib->formatDateTime($temp_self_excl_data->date_to)
                    ];

                    // self exclusion: permanent
                    $perm_self_excl_data = !empty($rg_details['permanent_self_exclusion']) ? $rg_details['permanent_self_exclusion'] : null;  
                    $self_excl_type = empty($perm_self_excl_data) ? $self_excl_type : Responsible_gaming::SELF_EXCLUSION_PERMANENT;
                    $perm_self_excl_stat = empty($perm_self_excl_data) ? null : [
                        'status'    => $this->rg_format_status($perm_self_excl_data->status, $rg_data['statusType']) ,
                        'datetimeFrom'   => $this->playerapi_lib->formatDateTime($perm_self_excl_data->date_from) ,
                        'datetimeTo'   => null
                    ];

                    // time out
                    $time_out_data = !isset($rg_details['cool_off']) ? null : $rg_details['cool_off'];
                    $hours_to_start = 0;
                    if (!empty($time_out_data)) {
                        $hours_to_start = intval((strtotime($time_out_data->date_from) - time()) / 3600);
                        $hours_to_start = $hours_to_start <= 0 ? 0 : $hours_to_start;
                    }

                    $time_out_stat = empty($time_out_data) ? null : [
                        'status'    => $this->rg_format_status($time_out_data->status, $rg_data['statusType']) ,
                        'datetimeFrom'   => $this->playerapi_lib->formatDateTime($time_out_data->date_from) ,
                        'datetimeTo'   => $this->playerapi_lib->formatDateTime($time_out_data->date_to) ,
                        'hoursToStart'  => $hours_to_start
                    ];


                    // deposit limits
                    $del_res = $this->player_responsible_gaming_library->getActiveResponsibleGamingSettings($player_id);
                    $dep_lim_current = isset($rg_details['deposit_limits']) ? $rg_details['deposit_limits'] : null;
                    $dep_lim_current_status = empty($dep_lim_current) ? null : $dep_lim_current->status;

                    if(empty($rg_data['depositlimitFlag'])){
                        $del_stat = null;
                    }else{
                        $depositLimitResetPeriodStart = null;
                        if(strtotime($del_res['depositLimitResetPeriodStart']) != false){
                            $depositLimitResetPeriodStart = $this->playerapi_lib->formatDateTime($del_res['depositLimitResetPeriodStart']);
                        }
                        $depositLimitResetPeriodEnd = null;
                        if(strtotime($del_res['depositLimitResetPeriodEnd']) != false){
                            $depositLimitResetPeriodEnd = $this->playerapi_lib->formatDateTime($del_res['depositLimitResetPeriodEnd']);
                        }
                        $depositLimits_latest_amount = null;
                        if(is_numeric($del_res['depositLimits_latest_amount'])){
                            $depositLimits_latest_amount = floatval($del_res['depositLimits_latest_amount']);
                        }
                        $depositLimits_latest_date_from = null;
                        if(strtotime($del_res['depositLimits_latest_date_from']) != false){
                            $depositLimits_latest_date_from = $this->playerapi_lib->formatDateTime($del_res['depositLimits_latest_date_from']);
                        }
                        $depositLimits_latest_date_to = null;
                        if(strtotime($del_res['depositLimits_latest_date_to']) != false){
                            $depositLimits_latest_date_to = $this->playerapi_lib->formatDateTime($del_res['depositLimits_latest_date_to']);
                        }
                        $del_stat['current'] = [
                            'limit'     => floatval($del_res['depositLimitsAmount']) ,
                            'remaining'   => floatval($del_res['depositLimitRemainTotalAmount']) ,
                            'datetimeFrom' => $depositLimitResetPeriodStart ,
                            'datetimeTo' => $depositLimitResetPeriodEnd ,
                        ];

                        $del_stat['nextCycle'] = [
                            'limit'     => $depositLimits_latest_amount ,
                            'datetimeFrom' => $depositLimits_latest_date_from ,
                            'datetimeTo' => $depositLimits_latest_date_to ,
                            ];            

                    }

                    // summary for responsible gaming
                    $rg_stat = [
                        'selfExclusion' => [
                            'active'  => $rg_data['selfexclusionFlag'] ,
                            'selfExclusionType'  => $this->rg_format_self_exclusion_type($self_excl_type) ,
                            'details' => ($self_excl_type == Responsible_gaming::SELF_EXCLUSION_PERMANENT) ? $perm_self_excl_stat : $temp_self_excl_stat
                        ] ,
                        'coolOff' => [
                            'active'  => $rg_data['timeoutFlag'] ,
                            'details' => $time_out_stat
                        ] ,
                        'depositLimits' => [
                            'currency' => $currency,
                            'active' => $rg_data['depositlimitFlag'] ,
                            'details'  => $del_stat
                        ] ,
                    ];
                });

                $output = $rg_stat;
                $result['data'] = $this->playerapi_lib->convertOutputFormat($output);
            }

            return $this->returnSuccessWithResult($result);
        } catch (\APIException $ex) {
            $result['code'] = $ex->getCode();
            $result['errorMessage']= $ex->getMessage();
            $this->comapi_log(__METHOD__, 'APIException', $result);
            return $this->returnErrorWithResult($result);
        }
    }

    protected function postSelfExclusion($player_id, $rg_data) {
        try {   
            $result=['code'=>Playerapi::CODE_OK]; 

            $typeFormat = [
                'Temporary' => Responsible_gaming::SELF_EXCLUSION_TEMPORARY,
                'Permanent' => Responsible_gaming::SELF_EXCLUSION_PERMANENT
            ];

            $allowTypeOfSelfExclusion = ['Temporary', 'Permanent']; 
            $current_currency = null;
            $request_body = $this->playerapi_lib->getRequestPramas();
            $this->comapi_log(__METHOD__, '=======request_body', $request_body, 'player_id', $player_id, 'currency', $current_currency);

            $validate_fields = [
                ['name' => 'selfExclusionType', 'type' => 'string', 'required' => true, 'length' => 0, 'allowed_content' => $allowTypeOfSelfExclusion]
            ];

            if(!empty($request_body['selfExclusionType']) && $request_body['selfExclusionType'] === 'Temporary'){
                $period_cnt_arr = ['name' => 'periodType', 'type' => 'int', 'required' => true, 'length' => 0];
                array_push($validate_fields, $period_cnt_arr);
            }

            $is_validate_basic_passed = $this->playerapi_lib->validParmasBasic($request_body, $validate_fields);
            $this->comapi_log(__METHOD__, '=======is_validate_basic_passed', $is_validate_basic_passed);

            if(!$is_validate_basic_passed['validate_flag']) {
                throw new \APIException($is_validate_basic_passed['validate_msg'], self::CODE_INVALID_PARAMETER);
            }

            if(!$this->utils->isEnabledFeature('responsible_gaming')){
                $is_validate_basic_passed['validate_msg'] = lang('One of BO System features: responsible_gaming is not enabled');
                throw new \APIException($is_validate_basic_passed['validate_msg'], self::CODE_INVALID_PARAMETER);
            }

            //TODO check status by function
            if(!empty($rg_data['selfExclusionRequestExists'])){
                $message = lang("Your request has been sent.");
                throw new \APIException($message, self::CODE_RPG_REQ_ALREADY_SENT_NO_MORE_ALLOWED);
            }

            //TODO check period cnt is valid

            $output = [];
            $request = $this->playerapi_lib->loopCurrencyForAction($current_currency, function($currency) use ($player_id, $request_body, $current_currency, $typeFormat, &$output){
                $this->utils->debug_log(__METHOD__,'current_currency : ', $current_currency, 'loop_currency : ', $currency);
                $type = $typeFormat[$request_body['selfExclusionType']];
                $period_cnt = $this->utils->safeGetArray($request_body, 'periodType', 0);
                switch ($type){
                    case Responsible_gaming::SELF_EXCLUSION_TEMPORARY:
                        $success = $this->player_responsible_gaming_library->RequestSelfExclusionTemporary($player_id, $period_cnt);
                        break;
                    case Responsible_gaming::SELF_EXCLUSION_PERMANENT:
                        $success = $this->player_responsible_gaming_library->RequestSelfExclusionPermanent($player_id, $period_cnt);
                        break;
                }

                if($success){
                    $message = lang('You\'ve successfully sent request!');
                }else{
                    $message = lang('error.default.db.message');
                }

                $output = [
                    'success' => $success ? true : false,
                    'message' => $message
                ];
                $this->comapi_log(__METHOD__, 'success response', $output);
            });

            if(empty($output['success'])){
                throw new \APIException($output['message'], self::CODE_SERVER_ERROR);
            }

            // $result['successMessage'] = $output['message'];
            return $this->returnSuccessWithResult($result);
        } catch (\APIException $ex) {
            $result['code'] = $ex->getCode();
            $result['errorMessage']= $ex->getMessage();
            $this->comapi_log(__METHOD__, 'APIException', $result);

            return $this->returnErrorWithResult($result);
        }
    }

    protected function postCoolOff($player_id, $rg_data) {
        try {
            $result=['code'=>Playerapi::CODE_OK];   

            $validate_fields = [
                ['name' => 'periodType', 'type' => 'int', 'required' => true, 'length' => 0]
            ];

            $current_currency = null;
            $request_body = $this->playerapi_lib->getRequestPramas();
            $this->comapi_log(__METHOD__, '=======request_body', $request_body, 'player_id', $player_id);

            $is_validate_basic_passed = $this->playerapi_lib->validParmasBasic($request_body, $validate_fields);
            $this->comapi_log(__METHOD__, '=======is_validate_basic_passed', $is_validate_basic_passed);

            if(!$is_validate_basic_passed['validate_flag']) {
                throw new \APIException($is_validate_basic_passed['validate_msg'], self::CODE_INVALID_PARAMETER);
            }

            if(!$this->utils->isEnabledFeature('responsible_gaming')){
                $is_validate_basic_passed['validate_msg'] = lang('One of BO System features: responsible_gaming is not enabled');
                throw new \APIException($is_validate_basic_passed['validate_msg'], self::CODE_INVALID_PARAMETER);
            }

            //TODO check status by function
            if(!empty($rg_data['coolingOffRequestExists'])){
                $message = lang("Your request has been sent.");
                throw new \APIException($message, self::CODE_RPG_REQ_ALREADY_SENT_NO_MORE_ALLOWED);
            }

            //TODO check period cnt is valid

            $output = [];
            $request = $this->playerapi_lib->loopCurrencyForAction($current_currency, function($currency) use ($player_id, $request_body, $current_currency, &$output){
                $this->utils->debug_log(__METHOD__,'current_currency : ', $current_currency, 'loop_currency : ', $currency);
                $period_cnt = $request_body['periodType'];
                $success = $this->player_responsible_gaming_library->RequestCoolOff($player_id, $period_cnt);
                if($success){
                    $message = lang('You\'ve successfully sent request!');
                }else{
                    $message = lang('error.default.db.message');
                }

                $output = [
                'success' => $success ? true : false,
                'message' => $message
                ];
                $this->comapi_log(__METHOD__, 'success response', $output);
            });

            if(empty($output['success'])){
                throw new \APIException($output['message'], self::CODE_SERVER_ERROR);
            }

            // $result['successMessage'] = $output['message'];
            return $this->returnSuccessWithResult($result);
        } catch (\APIException $ex) {
            $result['code'] = $ex->getCode();
            $result['errorMessage']= $ex->getMessage();
            $this->comapi_log(__METHOD__, 'APIException', $result);

            return $this->returnErrorWithResult($result);
        }
    }

    protected function postDepositLimits($player_id) {
        try {
            $result=['code'=>Playerapi::CODE_OK];
            $allowTypeOfSelfExclusion = [Responsible_gaming::SELF_EXCLUSION_TEMPORARY, Responsible_gaming::SELF_EXCLUSION_PERMANENT];
            $validate_fields = [
                ['name' => 'currency', 'type' => 'string', 'required' => false, 'length' => 0],
                ['name' => 'amount', 'type' => 'int', 'required' => true, 'length' => 0],
                // ['name' => 'amount', 'type' => 'positive_double', 'required' => true, 'length' => 0],
                ['name' => 'periodType', 'type' => 'int', 'required' => true, 'length' => 0]
            ];

            $request_body = $this->playerapi_lib->getRequestPramas();
            $currency = $this->utils->safeGetArray($request_body, "currency", $this->currency);
            // $result['currency'] = $currency;
            $this->comapi_log(__METHOD__, '=======request_body', $request_body, 'player_id', $player_id, 'currency', $currency);

            $is_validate_basic_passed = $this->playerapi_lib->validParmasBasic($request_body, $validate_fields);
            $this->comapi_log(__METHOD__, '=======is_validate_basic_passed', $is_validate_basic_passed);

            if(!$is_validate_basic_passed['validate_flag']) {
            throw new \APIException($is_validate_basic_passed['validate_msg'], self::CODE_INVALID_PARAMETER);
            }

            if(!$this->utils->isEnabledFeature('responsible_gaming')){
            $is_validate_basic_passed['validate_msg'] = lang('One of BO System features: responsible_gaming is not enabled');
            throw new \APIException($is_validate_basic_passed['validate_msg'], self::CODE_INVALID_PARAMETER);
            }

            //TODO check period cnt is valid

            $output = [];
            $requestSucc = $this->playerapi_lib->switchCurrencyForAction($currency, function() use ($player_id, $request_body, &$output) {
                $success = false;
                $amount = $request_body['amount'];
                $period_cnt = $request_body['periodType'];
                $request = $this->player_responsible_gaming_library->RequestDepositLimit($player_id, $amount, $period_cnt);
                $this->comapi_log(__METHOD__, 'success response', $request);

                if(!is_array($request)){
                    $output['success'] = $success;
                    $output['message'] = lang('error.default.db.message');
                    return false;
                }

                $message = $request['message'];
                if($request['status'] != BaseController::MESSAGE_TYPE_SUCCESS){
                    $output['success'] = $success;
                    $output['message'] = $message;
                    return false;
                }

                $success = true;
                $output['success'] = $success;
                $output['message'] = $message;
                return true;
            });

            if(empty($output['success'])){
                throw new \APIException($output['message'], self::CODE_SERVER_ERROR);
            }

            // $result['successMessage'] = $output['message'];
            return $this->returnSuccessWithResult($result);

        } catch (\APIException $ex) {
            $result['code'] = $ex->getCode();
            $result['errorMessage']= $ex->getMessage();
            $this->comapi_log(__METHOD__, 'APIException', $result);
            return $this->returnErrorWithResult($result);
        }
    }
}