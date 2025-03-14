<?php

/**
 * uri: /promotion
 *
 * @property playerapi_lib $playerapi_lib
 * @property Playerapi_model $playerapi_model
 */
trait player_roulette_module{

	public function roulette($action, $additional=null)
	{
		if(!$this->initApi()){
			return;
		}
		$this->load->library(['playerapi_lib']);
		$this->load->model(['playerapi_model', 'player_model', 'player_promo', 'roulette_api_record']);

		$request_method = $this->input->server('REQUEST_METHOD');

		switch ($action) {
			case 'apply':
				if($request_method == 'POST') {
					return $this->apply($this->player_id);
				}
				break;
			case 'info':
				if($request_method == 'POST') {
					return $this->rouletteInfo($this->player_id);
				}
				break;
			case 'records':
				if($request_method == 'POST') {
					return $this->records($this->player_id);
				}
				break;
            case 'list':
                if($request_method == 'GET') {
                    return $this->listRouletteType();
                }
                break;
            case 'latest':
                if($request_method == 'GET') {
                    return $this->rouletteLatest();
                }
                break;
		}
		return $this->returnErrorWithCode(Playerapi::CODE_GENERAL_CLIENT_ERROR);
	}

    public function listRouletteType()
    {
        $result = [
            'code' => Playerapi::CODE_OK,
            'data' => []
        ];

        $roulette_setting = $this->utils->getConfig('fallback_currency_for_roulette_type');
        $odds = $this->utils->getConfig('roulette_reward_odds_settings');
        $roulette = !empty($roulette_setting)?$roulette_setting:[];

        $this->comapi_log(__METHOD__, 'roulette', $roulette, $odds);

        foreach ($roulette as $type => $items) {
            $roulette_name = $items['roultte_name'];

            if (!empty($odds[$roulette_name])) {

                $settings = $odds[$roulette_name];
                // $_name = $this->utils->safeGetArray($items, 'name', '');
                $currency = strtoupper($items['currency']);

                $promo_cms_setting_id = $this->utils->getCmsIdByRouletteName($roulette_name);
                $roulette_promo_details = $this->playerapi_lib->switchCurrencyForAction($currency, function() use ($currency, $promo_cms_setting_id){
                    $promo_list = $this->promorules->getAllPromo(null, null, $promo_cms_setting_id);
                    $this->load->model(['cms_model']);
                    $wrapped_promo_data = array_map(function($promo) use ($currency) {
                        $promo['promoDetails'] =  $this->cms_model->decodePromoDetailItem($promo['promoDetails']);
                        $current_lang = $this->playerapi_lib->getIsoLang($this->indexLanguage);
                        $multi_lang = $this->promoItemMultiLangFields($promo, $current_lang, true);
                        return [
                            'currency' => strtoupper($currency ?: $this->currency),
                            'promoId' => (int)$promo['promoCmsSettingId'],
                            'promoCategory' => (int)$promo['promo_category'],
                            'promoName' => $multi_lang['promoName'][0]['content'],
                            'promoDescription' => $multi_lang['promoDescription'][0]['content'],
                            'promoDetails' => $multi_lang['promoDetails'][0]['content'],
                            'promoThumbnail' => $multi_lang['promoThumbnail'][0]['content'],
                            'promoCode' => $promo['promo_code'],
                            'isNew' => (bool)$promo['tag_as_new_flag'],
                            'startAt' => $this->playerapi_lib->formatDateTime($promo['applicationPeriodStart']),
                            'expiredAt' => $this->playerapi_lib->formatDateTime($promo['hide_date']),
                            // 'sort' => 0,
                        ];
                    }, is_array($promo_list) ? $promo_list : []);
                    $result = !empty($wrapped_promo_data)? array_pop($wrapped_promo_data) : '';
                    return $result;
                });
                if (empty($roulette_promo_details)) {
                    continue;
                }
                $data = [
                    'currency' => $currency,
                    'rouletteType' => $type,
                    'name' => $roulette_promo_details['promoName'],
                    'description' => $roulette_promo_details['promoDetails'],
                    'rouletteContent' => array_map(function ($setting) {
                        return [
                            'id' => $setting['id'],
                            'amount' => $setting['bonus'],
                            'prize' => $setting['prize'],
                            'awardType' => $this->utils->safeGetArray($setting, 'awardType', roulette_api_record::ROULETTE_AWARDTYPE_CASH_BONUS),
                        ];
                    }, $settings)
                ];

                $result['data'][] = $data;
            }
        }

        return $this->returnSuccessWithResult($result);
    }

    public function rouletteInfo($player_id)
    {
		try {
		    $result=['code'=>Playerapi::CODE_OK];
			$request_body = $this->playerapi_lib->getRequestPramas();
			$this->comapi_log(__METHOD__, '=======request_body', $request_body, 'player_id', $player_id);

			$validate_fields = [
				['name' => 'rouletteType', 'type' => 'string', 'required' => true, 'length' => 0, 'allowed_content' => $this->getRouletteTypeKey()]
			];

			$is_validate_basic_passed = $this->playerapi_lib->validParmasBasic($request_body, $validate_fields);
			$this->comapi_log(__METHOD__, '=======is_validate_basic_passed', $is_validate_basic_passed);

			if(!$is_validate_basic_passed['validate_flag']) {
				throw new \APIException($is_validate_basic_passed['validate_msg'], self::CODE_INVALID_PARAMETER);
			}

            $type_config = $this->getRouletteSettingByType($request_body['rouletteType']);
            $roulette_name = $type_config['roultte_name'];
            $currency = $type_config['currency'];
            $type = $type_config['type'];

            $verify_function_list = [
                [ 'name' => 'verifyRouletteName', 'params' => [$roulette_name] ],
            ];

            foreach ($verify_function_list as $method) {
                $this->utils->debug_log('============verifyRouletteName verify_function', $method);
                $verify_result = call_user_func_array([$this, $method['name']], $method['params']);
                $exec_continue = $this->playerapi_lib->checkIfExecContinueAfterVerify($verify_result);

                if(!$exec_continue) {
                    throw new \APIException($verify_result['error_message'], $verify_result['error_code']);
                }
            }

            $roulette_api = $this->getRouletteApi($roulette_name);
            $promo_cms_id = $roulette_api->getCmsIdByRouletteName($roulette_name);
            $moduleType = $roulette_api->getModuleType();
            if ($this->utils->getConfig('enabled_roulette_transactions') && $moduleType === Abstract_roulette_api::MODULE_TYPE_TRANS ) {
               if (empty($promo_cms_id)) {
                    $ret = $this->timesByTransactions($player_id, $roulette_name, $roulette_api);
                    return $ret;
                }
            }

            $message = '';
            $error_code = 0;
            $roulette_res = null;
            $succ = $this->playerapi_lib->switchCurrencyForAction($currency, function() use ($roulette_api, $player_id, $promo_cms_id, &$message, &$errcode, &$roulette_res) {

                // $promo_res = $this->utils->getPlayerAvailablePromoList($player_id, $promo_cms_id);
                // $this->comapi_log(__METHOD__, 'promo_res', $promo_res);
                // if (count($promo_res['promo_list']) == 0) {
                //     $this->comapi_log(__METHOD__, 'promo_list is empty');
                //     $message = lang('You are not suited for this roulette yet');
                //     $errcode = self::CODE_ROULETTE_REQUEST_FAIL;
                //     return false;
                // }
                
                $roulette_res = $roulette_api->generateRouletteSpinTimes($player_id, $promo_cms_id);
                $this->comapi_log(__METHOD__, 'roulette_res', $roulette_res);
                if ($roulette_res['success'] == false) {
                    $message = $roulette_res['mesg'];
                    $errcode = self::CODE_ROULETTE_REQUEST_FAIL;
                    return false;
                }


                return true;
            });

            if (!$succ) {
                throw new \APIException($message, $errcode);
            }

            $output = $this->playerapi_lib->matchOutputRouletteTypeInfo($type, $roulette_res);
            $result['data'] = $this->playerapi_lib->convertOutputFormat($output);
            $this->comapi_log(__METHOD__, 'Successful response', $result);

            return $this->returnSuccessWithResult($result);
        }catch (\APIException $ex) {
            $result['code'] = $ex->getCode();
			$result['errorMessage']= $ex->getMessage();
            $this->comapi_log(__METHOD__, 'APIException', $result);

            return $this->returnErrorWithResult($result);
        }
    }// End function times()

    public function apply($player_id)
    {
        try {
            $result=['code'=>Playerapi::CODE_OK];
			$request_body = $this->playerapi_lib->getRequestPramas();
			$this->comapi_log(__METHOD__, '=======request_body', $request_body, 'player_id', $player_id);

			$validate_fields = [
				['name' => 'rouletteType', 'type' => 'string', 'required' => true, 'length' => 0, 'allowed_content' => $this->getRouletteTypeKey()],
			];

			$is_validate_basic_passed = $this->playerapi_lib->validParmasBasic($request_body, $validate_fields);
			$this->comapi_log(__METHOD__, '=======is_validate_basic_passed', $is_validate_basic_passed);

			if(!$is_validate_basic_passed['validate_flag']) {
				throw new \APIException($is_validate_basic_passed['validate_msg'], self::CODE_INVALID_PARAMETER);
			}

            $type_config = $this->getRouletteSettingByType($request_body['rouletteType']);
			$roulette_name = $type_config['roultte_name'];
            $currency = $type_config['currency'];

            $verify_function_list = [
                [ 'name' => 'verifyRouletteName', 'params' => [$roulette_name] ],
            ];

            foreach ($verify_function_list as $method) {
                $this->utils->debug_log('============verifyRouletteName verify_function', $method);
                $verify_result = call_user_func_array([$this, $method['name']], $method['params']);
                $exec_continue = $this->playerapi_lib->checkIfExecContinueAfterVerify($verify_result);

                if(!$exec_continue) {
                    throw new \APIException($verify_result['error_message'], $verify_result['error_code']);
                }
            }

            $roulette_api = $this->getRouletteApi($roulette_name);
            $promo_cms_id = $roulette_api->getCmsIdByRouletteName($roulette_name);
            $moduleType = $roulette_api->getModuleType();
            if ($this->utils->getConfig('enabled_roulette_transactions') && $moduleType === Abstract_roulette_api::MODULE_TYPE_TRANS ) {
                if (empty($promo_cms_id)) {
                    $ret = $this->applyByTransactions($player_id, $roulette_name, $roulette_api);
                    return $ret;
                }
            }

            //start switchCurrencyForAction
            $message = '';
            $error_code = 0;
            $chance_res = null;
            $controller = $this;

            $success = $this->playerapi_lib->switchCurrencyForAction($currency, function() use ($controller, $roulette_api, $player_id, $promo_cms_id, $roulette_name, &$chance_res, &$message, &$errcode) {

                #verify spin times
                $verify_res = $roulette_api->verifyRouletteSpinTimes($player_id, $promo_cms_id);
                $this->comapi_log('apply verifyRouletteSpinTimes verify_res', $verify_res);

                if ($verify_res['remain_times'] == 0) {
                    $message = lang('You are not suited for this roulette yet');
                    $errcode = self::CODE_ROULETTE_REQUEST_FAIL;
                    return false;
                }

                #sign token
                $username = $controller->player_model->getUsernameById($player_id);
                $released_bonus_today = $verify_res['used_times'];
                $sign_str = $username.$released_bonus_today.$roulette_name.$promo_cms_id;
                $player_token = md5($sign_str);

                #get bonus amount from api
                $chance_res = $roulette_api->playerRouletteRewardOdds();

                if ($chance_res['success'] != true) {
                    $message = $chance_res['mesg'];
                    $errcode = self::CODE_ROULETTE_REQUEST_FAIL;
                    return false;
                }

                $bonus = $chance_res['chance_res']['bonus'];
                $extra_info = [
                    'order_generated_by' => Player_promo::ORDER_GENERATED_BY_ROULETTE,
                    'player_request_ip' => $this->utils->getIP(),
                    'is_roulette_api' => true,
                    'bonus_amount' => $bonus,
                    'player_token' => $player_token
                ];

                if (isset($verify_res['available_list'])) {
                    $extra_info['verify_res'] = $verify_res;
                }

                $this->comapi_log(__METHOD__, "roulette apply promo params {$player_id}", [
                    'extra_info' => $extra_info,
                    'bonus' => $bonus,
                    'player_token' => $player_token
                ]);

                #lock player roulette
                $msg='';
                $err_code = 0;
                $apply_res = null;
                $roulette_res = null;
                $succ = $this->player_model->lockAndTransForApplyRoulette($player_id, function()
                    use($controller, $player_id, &$extra_info, $promo_cms_id, $verify_res, &$msg, &$err_code, $roulette_api, &$apply_res, &$roulette_res, $bonus, $roulette_name, $chance_res){

                        $apply_res = $controller->request_promo($promo_cms_id, 0, null, false, 'ret_to_api', $player_id, $extra_info);

                        if ($apply_res['success'] != true) {
                            $msg = $apply_res['message'];
                            $err_code = $apply_res['code'];
                            return false;
                        }

                        $after_spin_times = $roulette_api->verifyRouletteSpinTimes($player_id, $promo_cms_id);

                        $rt_data = [
                            'player_id' => $player_id,
                            'player_promo_id' => $apply_res['player_promo_request_id'],
                            'promo_cms_id' => $promo_cms_id,
                            'bonus_amount' => $bonus,
                            'created_at' => $this->utils->getNowForMysql(),
                            'type' => $roulette_api->getRouletteType($roulette_name),
                            'notes' => lang('by comapi '. $roulette_name .' applyRoulette'),
                            'product_id' => isset($chance_res['chance_res']['product_id']) ? $chance_res['chance_res']['product_id'] : null,
                            'prize' => json_encode($chance_res['chance_res']),
                            'deposit_amount' => isset($verify_res['deposit']) ? $verify_res['deposit'] : null,
                            'total_times' => $after_spin_times['total_times'],
                            'used_times' => $after_spin_times['used_times'],
                            'valid_date' => $after_spin_times['valid_date'],
                        ];

                        $roulette_res = $roulette_api->createRoulette($player_id, $rt_data);

                        if ($roulette_res['success'] == false) {
                            $msg = $roulette_res['mesg'];
                            $err_code = $roulette_res['code'];
                            return false;
                        }
                        return true;
                });

                $this->comapi_log(__METHOD__, 'chance_res, apply_res, roulette_res ',$chance_res, $apply_res, $roulette_res, $succ);

                if(!$succ) {
                    $message = $msg;
                    $errcode = $err_code;
                    return false;
                }
                return true;
            });

            if (!$success) {
                throw new \APIException($message, $errcode);
            }//end switchCurrencyForAction

            $output['rid'] = $chance_res['chance_res']['rid'];
            $output['prize'] = $chance_res['chance_res']['prize'];
            $output['bonus'] = $chance_res['chance_res']['bonus'];
            $output['productId'] = isset($chance_res['chance_res']['product_id']) ? $chance_res['chance_res']['product_id'] : '';
            $output['monthLimit'] = isset($chance_res['chance_res']['monthLimit']) ? $chance_res['chance_res']['monthLimit'] : 0;
            $output['awardType'] = $this->utils->safeGetArray($chance_res['chance_res'], 'awardType', roulette_api_record::ROULETTE_AWARDTYPE_CASH_BONUS);
            $result['data'] = $this->playerapi_lib->convertOutputFormat($output);

            $this->comapi_log(__METHOD__, 'Successful response', $result);

            $this->returnSuccessWithResult($result);
        }
        catch (\APIException $ex) {
            $result['code'] = $ex->getCode();
			$result['errorMessage']= $ex->getMessage();
            $this->comapi_log(__METHOD__, 'APIException', $result);

            return $this->returnErrorWithResult($result);
        }
        finally {
        	$this->comapi_log(__METHOD__, 'finally process', $player_id);
            if (!empty($roulette_api)) {
                $roulette_api->processAfteraApply($player_id, $promo_cms_id, $roulette_name, true);
            }
        }
    }

    public function records($player_id)
    {
        try {
            $validate_fields = [
                ['name' => 'rouletteType', 'type' => 'string', 'required' => true, 'length' => 0, 'allowed_content' => $this->getRouletteTypeKey()],
				['name' => 'sizePerPage', 'type' => 'int', 'required' => false, 'length' => 0],
				['name' => 'pageNumber', 'type' => 'int', 'required' => false, 'length' => 0],
				['name' => 'createdAtEnd', 'type' => 'date-time', 'required' => false, 'length' => 0],
				['name' => 'createdAtStart', 'type' => 'date-time', 'required' => false, 'length' => 0],
				['name' => 'sort', 'type' => 'string', 'required' => false, 'length' => 0],
				['name' => 'type', 'type' => 'int', 'required' => false, 'length' => 0],
			];

			$result=['code'=>self::CODE_OK];
			$request_body = $this->playerapi_lib->getRequestPramas();
			$this->comapi_log(__METHOD__, '=======request_body', $request_body, 'player_id', $player_id);
			$is_validate_basic_passed = $this->playerapi_lib->validParmasBasic($request_body, $validate_fields);
			$this->comapi_log(__METHOD__, '=======is_validate_basic_passed', $is_validate_basic_passed);

			if(!$is_validate_basic_passed['validate_flag']) {
				throw new \APIException($is_validate_basic_passed['validate_msg'], self::CODE_INVALID_PARAMETER);
			}

            $type_config = $this->getRouletteSettingByType($request_body['rouletteType']);
            $roulette_name = $type_config['roultte_name'];
            $currency = $type_config['currency'];

            $today = $this->utils->getTodayForMysql();
            $default_date = date('Y-m-d', strtotime($today . ' -180 days')) .' '.Utils::FIRST_TIME;

			$time_start = !empty($request_body['createdAtStart']) ? $request_body['createdAtStart'] : $default_date;
			$time_end = !empty($request_body['createdAtEnd']) ? $request_body['createdAtEnd'] : '';
			$limit = !empty($request_body['sizePerPage']) ? $request_body['sizePerPage'] : 20;
			$sort = !empty($request_body['sort']) ? $request_body['sort'] : 'DESC';
			$type = isset($request_body['type']) ? $request_body['type'] : null;
			$page = !empty($request_body['pageNumber']) ? $request_body['pageNumber'] : 1;

            $verify_function_list = [
                [ 'name' => 'verifyRouletteName', 'params' => [$roulette_name] ],
            ];

            foreach ($verify_function_list as $method) {
                $this->utils->debug_log('============verifyRouletteName verify_function', $method);
                $verify_result = call_user_func_array([$this, $method['name']], $method['params']);
                $exec_continue = $this->playerapi_lib->checkIfExecContinueAfterVerify($verify_result);

                if(!$exec_continue) {
                    throw new \APIException($verify_result['error_message'], $verify_result['error_code']);
                }
            }

            $roulette_api = $this->getRouletteApi($roulette_name);
            $roulette_res = $this->playerapi_lib->switchCurrencyForAction($currency, function() use ($roulette_api, $time_start, $time_end, $sort, $limit, $page, $player_id) {
                return $roulette_api->getRouletteRecords($time_start, $time_end, $sort, $limit, $page, $player_id, false);
            });
            $this->comapi_log(__METHOD__, 'roulette_res', $roulette_res);

            if (!isset($roulette_res['list'])) {
                throw new \APIException(lang('Roulette records fail.'), self::CODE_ROULETTE_RECORDS_FAIL);
            }

			$result['data'] = $roulette_res;
			$result['data']['list'] = $this->playerapi_lib->customizeApiOutput($roulette_res['list'], ['roulette_records']);
            $result['data']['list'] = $this->playerapi_lib->convertOutputFormat($result['data']['list']);

            foreach($result['data']['list'] as &$entry) {
                $entry['rouletteType'] = $request_body['rouletteType'];
                $entry['currency'] = strtoupper($currency);
                $entry['awardType'] = $this->utils->safeGetArray($entry, 'awardType', roulette_api_record::ROULETTE_AWARDTYPE_CASH_BONUS);
            }

            $this->comapi_log(__METHOD__, 'Successful response', $result);

            return $this->returnSuccessWithResult($result);
        }
        catch (\APIException $ex) {
            $result['code'] = $ex->getCode();
			$result['errorMessage']= $ex->getMessage();
            $this->comapi_log(__METHOD__, 'APIException', $result);

            return $this->returnErrorWithResult($result);
        }
    }

    public function rouletteLatest($player_id = NULL)
    {
        try {
            $validate_fields = [
                ['name' => 'rouletteType', 'type' => 'string', 'required' => true, 'length' => 0, 'allowed_content' => $this->getRouletteTypeKey()],
                ['name' => 'createdAtEnd', 'type' => 'date-time', 'required' => false, 'length' => 0],
                ['name' => 'createdAtStart', 'type' => 'date-time', 'required' => false, 'length' => 0],
                ['name' => 'sort', 'type' => 'string', 'required' => false, 'length' => 0],
                ['name' => 'limit', 'type' => 'int', 'required' => false, 'length' => 0],
                ['name' => 'offset', 'type' => 'int', 'required' => false, 'length' => 0],
            ];

            $result=['code'=>self::CODE_OK];
            $request_body = $this->playerapi_lib->getRequestPramas();
            $this->comapi_log(__METHOD__, '=======request_body', $request_body, 'player_id', $player_id);
            $is_validate_basic_passed = $this->playerapi_lib->validParmasBasic($request_body, $validate_fields);
            $this->comapi_log(__METHOD__, '=======is_validate_basic_passed', $is_validate_basic_passed);

            if(!$is_validate_basic_passed['validate_flag']) {
                throw new \APIException($is_validate_basic_passed['validate_msg'], self::CODE_INVALID_PARAMETER);
            }

            $type_config = $this->getRouletteSettingByType($request_body['rouletteType']);
            $roulette_name = $type_config['roultte_name'];
            $currency = $type_config['currency'];
            $today = $this->utils->getTodayForMysql();
            $config_default_date = $this->utils->getConfig('public_roulette_latest_record_default_date');
            $default_date = date('Y-m-d', strtotime($today . ' ' . $config_default_date)) .' '.Utils::FIRST_TIME;
            $time_start = !empty($request_body['createdAtStart']) ? $request_body['createdAtStart'] : $default_date;
            $time_end = !empty($request_body['createdAtEnd']) ? $request_body['createdAtEnd'] : '';
            $limit = !empty($request_body['limit']) ? $request_body['limit'] : 5;
            $offset = !empty($request_body['offset']) ? $request_body['offset'] : 0;
            $sort = !empty($request_body['sort']) ? $request_body['sort'] : 'DESC';

            $verify_function_list = [
                [ 'name' => 'verifyRouletteName', 'params' => [$roulette_name] ],
            ];

            foreach ($verify_function_list as $method) {
                $this->utils->debug_log('============verifyRouletteName verify_function', $method);
                $verify_result = call_user_func_array([$this, $method['name']], $method['params']);
                $exec_continue = $this->playerapi_lib->checkIfExecContinueAfterVerify($verify_result);

                if(!$exec_continue) {
                    throw new \APIException($verify_result['error_message'], $verify_result['error_code']);
                }
            }

            $roulette_api = $this->getRouletteApi($roulette_name);
            $roulette_res = $this->playerapi_lib->switchCurrencyForAction($currency, function() use ($roulette_api, $time_start, $time_end, $limit, $offset, $player_id) {
                return $roulette_api->getRouletteLatest($time_start, $time_end, $offset, $limit, $player_id);
            });
            $this->comapi_log(__METHOD__, 'roulette_res', $roulette_res);

            $output = [];
            foreach($roulette_res as $key => $entry) {
                $output[$key]['id'] = (int)$entry['id'];

                // mask username
                $_username = $this->player_model->getUsernameById($entry['player_id']);
                $username = $this->utils->maskMiddleStringLite($_username, 2);

                $output[$key]['username'] = $username;
                $output[$key]['rouletteType'] = $request_body['rouletteType'];
                $output[$key]['currency'] = strtoupper($currency);
                $output[$key]['createdAt'] = $this->playerapi_lib->formatDateTime($entry['created_at']);
                $output[$key]['bonusAmount'] = $entry['bonus_amount'];
                $output[$key]['avatarUrl'] = $this->setProfilePicture($entry['player_id']);
                $output[$key]['awardType'] = $this->utils->safeGetArray($entry, 'awardType', roulette_api_record::ROULETTE_AWARDTYPE_CASH_BONUS);
            }

            $result['data'] = $this->playerapi_lib->convertOutputFormat($output);

            $this->comapi_log(__METHOD__, 'Successful response', $result);
            return $this->returnSuccessWithResult($result);
        }
        catch (\APIException $ex) {
            $result['code'] = $ex->getCode();
            $result['errorMessage']= $ex->getMessage();
            $this->comapi_log(__METHOD__, 'APIException', $result);

            return $this->returnErrorWithResult($result);
        }
    }

    public function timesByTransactions($player_id, $roulette_name, $roulette_api)
    {
        try {
            $this->comapi_log(__METHOD__, "{$player_id} roulette_name", $roulette_name);

            $roulette_res = $roulette_api->generateRouletteSpinTimes($player_id, $roulette_name);

            if ($roulette_res['success'] == false) {
                throw new \APIException($roulette_res['mesg'], self::CODE_ROULETTE_REQUEST_FAIL);
            }

            $this->comapi_log(__METHOD__, 'roulette_res', $roulette_res);

            $spin_times_data = $this->utils->safeGetArray($roulette_res, 'spin_times_data', []);
            $output = [
                'totalTimes' => $this->utils->safeGetArray($spin_times_data, 'total_times'),
                'usedTimes' => $this->utils->safeGetArray($spin_times_data, 'used_times'),
                'remainTimes' => $this->utils->safeGetArray($spin_times_data, 'remain_times'),
                'type' => strval($roulette_res['type']),
                // '_remain_times' => $this->playerapi_lib->formatDateTime($this->utils->safeGetArray($spin_times_data, 'getRetention')),
                // 'valid_date' => $this->utils->safeGetArray($spin_times_data, 'valid_date'),
                // 'available_list' => $this->utils->safeGetArray($spin_times_data, 'available_list',[]),
            ];
		    $output['base'] = $this->utils->safeGetArray($spin_times_data, 'base', 0);
            $result['data'] = $this->playerapi_lib->convertOutputFormat($output);
            $this->comapi_log(__METHOD__, 'Successful response', $result);

            return $this->returnSuccessWithResult($result);
        }
        catch (APIException $ex) {
            $result['code'] = $ex->getCode();
			$result['errorMessage']= $ex->getMessage();
            $this->comapi_log(__METHOD__, 'APIException', $result);

            return $this->returnErrorWithResult($result);
        }
    }// End function timesByTransactions()

    public function applyByTransactions($player_id, $roulette_name, $roulette_api)
    {
        try {
            $this->comapi_log(__METHOD__, "apply Roulette Transactions {$player_id} roulette_name", $roulette_name);

            $player = $this->player_model->getPlayerById($player_id);
            $roulette_desc = $roulette_api->rouletteDescription($roulette_name);
            if($player->disabled_promotion && $this->utils->safeGetArray($roulette_desc, 'check_player_disabled_promotion', false)){
                throw new \APIException(lang('Promotion has been disabled.'), self::CODE_PROMOTION_DISABLED);
            }

            //Check player Spin Times
            $verify_res = $roulette_api->verifyRouletteSpinTimes($player_id, $roulette_name);
            $this->comapi_log(__METHOD__, 'verifyRouletteSpinTimes verify_res', $verify_res);
            if ($verify_res['remain_times'] == 0) {
                throw new \APIException(lang('You are not suited for this roulette yet'), self::CODE_ROULETTE_REQUEST_FAIL);
            }

            //get bonus amount from api
            $chance_res = $roulette_api->playerRouletteRewardOdds();
            if ($chance_res['success'] != true) {
                throw new \APIException($chance_res['mesg'], self::CODE_ROULETTE_REQUEST_FAIL);
            }
            $bonus = $chance_res['chance_res']['bonus'];

            $this->comapi_log(__METHOD__, "roulette apply transactions params {$player_id}", ['bonus' => $bonus, 'verify_res' => $verify_res, 'chance_res' => $chance_res]);

            $msg='';
            $error_code = 0;
            $controller = $this;
            $roulette_res = null;
            $promo_cms_id = 0;//db cannot be null so set to 0
            $admin_user_id = $this->authentication->getUserId() ? $this->authentication->getUserId() : Users::SUPER_ADMIN_ID;
            $bonus_trans_id = null;
            $skip_transaction = false;
            if($bonus == 0 && $this->utils->safeGetArray($chance_res['chance_res'], 'skip_tran', false)) {
                unset($chance_res['chance_res']['skip_tran']);
                $skip_transaction = true;
            }

            $trans_succ = $this->player_model->lockAndTransForPlayerBalance($player_id, function()
                use($controller, $player_id, $promo_cms_id, $verify_res, &$msg, &$error_code, $roulette_api, $bonus, $roulette_name, $chance_res, $admin_user_id, &$bonus_trans_id, $skip_transaction){

                    if($skip_transaction) {
                        return true;
                    }
                    $this->load->model(['transactions', 'wallet_model', 'withdraw_condition']);

                    $before_balance = $controller->wallet_model->getMainWalletBalance($player_id);
                    $note = 'admin user: ' . $admin_user_id . ', added roulette bonus amount of: ' . $bonus . ' to ' . $player_id;
                    $controller->comapi_log('roulette transactions note', $note);
                    $bonus_trans_id = $controller->transactions->createRouletteBonusTransaction($admin_user_id, $player_id, $bonus, $before_balance, $note);

                    $controller->comapi_log('roulette bonus_trans_id', $bonus_trans_id);
                    if(empty($bonus_trans_id)){
                        $msg = lang('Created roulette transactions failed.');
                        $error_code = self::CODE_ROULETTE_TRANSACTIONS_FAILED;
                        return false;
                    }

                    #create roulette withdrawal condition
                    $description = $roulette_api->rouletteDescription($roulette_name);
                    $bet_times = isset($description['withdrawal_condition']) ? $description['withdrawal_condition'] : 1;
                    $withdrawal_condition = $bet_times * $bonus;
                    $wc_res = $controller->withdraw_condition->createWithdrawConditionForRouletteBonus($player_id, $bonus_trans_id, $withdrawal_condition, $bonus, $bet_times);

                    $controller->comapi_log('roulette withdrawal condition result', $description, $bet_times, $withdrawal_condition, $wc_res);

                    return true;
            });

            if(!$trans_succ) {
                throw new \APIException($msg, $error_code);
            }
            $message = $err_code = '';
            $succ = $this->player_model->lockAndTransForApplyRoulette($player_id, function()
                use($controller, $player_id, $promo_cms_id, $verify_res, &$message, &$err_code, $roulette_api, &$roulette_res, $bonus, $roulette_name, $chance_res, $bonus_trans_id){

                    $after_spin_times = $roulette_api->verifyRouletteSpinTimes($player_id, $roulette_name);
                    $this->comapi_log(__METHOD__, "after_spin_times {$player_id}", ['verify_res' => $verify_res, 'after_spin_times' => $after_spin_times]);

                    $rt_data = [
                        'player_id' => $player_id,
                        // 'player_promo_id' => $apply_res['player_promo_request_id'],
                        'promo_cms_id' => $promo_cms_id,
                        'bonus_amount' => $bonus,
                        'created_at' => $this->utils->getNowForMysql(),
                        'type' => $roulette_api->getRouletteType($roulette_name),
                        'notes' => lang('by comapi '. $roulette_name .' applyRoulette'),
                        'product_id' => isset($chance_res['chance_res']['product_id']) ? $chance_res['chance_res']['product_id'] : null,
                        'prize' => json_encode($chance_res['chance_res']),
                        'deposit_amount' => isset($verify_res['deposit']) ? $verify_res['deposit'] : null,
                        'total_times' => $after_spin_times['total_times'],
                        'used_times' => $after_spin_times['used_times']+1,
                        'valid_date' => $after_spin_times['valid_date'],
                        'transaction_id' => $bonus_trans_id
                    ];

                    $roulette_res = $roulette_api->createRoulette($player_id, $rt_data);

                    if ($roulette_res['success'] == false) {
                        $message = $roulette_res['mesg'];
                        $err_code = $roulette_res['code'];
                        return false;
                    }
                    return true;
            });

            if(!$succ) {
                throw new \APIException($message, $err_code);
            }

            $this->comapi_log(__METHOD__, 'chance_res, roulette_res ',$chance_res, $roulette_res);

            $output['rid'] = (string)$chance_res['chance_res']['rid'];
            $output['prize'] = $chance_res['chance_res']['prize'];
            $output['bonus'] = $chance_res['chance_res']['bonus'];
            $output['productId'] = $chance_res['chance_res']['product_id'];
            $output['monthLimit'] = isset($chance_res['chance_res']['monthLimit']) ? $chance_res['chance_res']['monthLimit'] : 0;

            $result['data'] = $this->playerapi_lib->convertOutputFormat($output);

            $this->comapi_log(__METHOD__, 'Successful response', $result);

            $this->returnSuccessWithResult($result);
        }
        catch (APIException $ex) {
            $result['code'] = $ex->getCode();
			$result['errorMessage']= $ex->getMessage();
            $this->comapi_log(__METHOD__, 'APIException', $result);

            return $this->returnErrorWithResult($result);
        }
    }// End of trait applyByTransactions

    public function getRouletteApi($roulette_name){
        $api_name = 'roulette_api_' . $roulette_name;
        $this->load->library('roulette/'.$api_name);
        $roulette_api = $this->$api_name;
        return $roulette_api;
    }

	public function verifyRouletteName($roulette_name)
	{
        $verify_result = ['passed' => true, 'error_message' => ''];

        try {
            $api_name = 'roulette_api_' . $roulette_name;
            $classExists = file_exists(strtolower(APPPATH.'libraries/roulette/'.$api_name.".php"));

            if (!$classExists) {
                $this->comapi_log(__METHOD__, lang('Cannot find class ' . $classExists));
                $verify_result['error_code'] = self::CODE_ROULETTE_NOT_FOUND;
                $verify_result['error_message'] = lang('Cannot find roulette.');
                $verify_result['passed'] = false;
                return $verify_result;
            }

            $this->load->library('roulette/'.$api_name);
            $roulette_api = $this->$api_name;

            if (!$roulette_api) {
                $this->comapi_log(__METHOD__, lang('Cannot find ' . $api_name . ' api'));
                $verify_result['error_code'] = self::CODE_ROULETTE_NOT_FOUND;
                $verify_result['error_message'] = lang('Cannot find roulette.');
                $verify_result['passed'] = false;
                return $verify_result;
            }
            return $verify_result;
        } catch (APIException $ex) {
            $this->utils->debug_log('============'. __METHOD__ .' APIException', $ex->getMessage());
            $verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, 'Unknown Error during '.__METHOD__);
            return $verify_result;
        }
	}

    public function getRouletteSettingByType($roulette_type)
    {
        $config = $this->utils->getConfig('fallback_currency_for_roulette_type');
        $roulette = isset($config[$roulette_type]) ? $config[$roulette_type] : null;
        $this->comapi_log(__METHOD__, '=======roulette', $roulette);
        return $roulette;
    }

    public function getRouletteTypeKey()
    {
        $res = [];
        $config = $this->utils->getConfig('fallback_currency_for_roulette_type');
        if (!empty($config) && is_array($config)) {
            $res = array_keys($config);
        }
        return $res;
    }
}
