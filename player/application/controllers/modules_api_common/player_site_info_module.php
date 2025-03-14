<?php

/**
 * player site info function
 * uri: /site-properties, /site-config
 */
trait player_site_info_module{

	public function site_config($action, $additional=null){
		if(!$this->initApi()){
			return;
		}
		$this->load->library(['playerapi_lib']);
		$this->load->model(['playerapi_model']);
		$request_method = $this->input->server('REQUEST_METHOD');

		switch ($action) {
			case 'banks':
				if($request_method == 'GET') {
					return $this->getAvailableBanks();
				}
				break;
			case 'currencies':
				// no auth
				if($request_method == 'GET') {
					return $this->getAllCurrencies();
				}
				// $result=[
				// 	'code'=>self::CODE_OK,
				// 	'data'=>$this->_mockDataForPlayerapi(),
				// ];
				// return $this->returnSuccessWithResult($result);
				break;
			case 'languages':
				// no auth
				$result=[
					'code'=>self::CODE_OK,
					'data'=>$this->_mockDataForPlayerapi(),
				];
				return $this->returnSuccessWithResult($result);
				break;
			case 'available-game-apis':
				break;
			case 'available-payment-apis':
				break;
			// case 'banks':
			// 	break;
			case 'credit-point':
				break;
			case 'error-codes':
				break;
			case 'game-apis':
				break;
			case 'game-log-search-limit':
				break;
			case 'game-types':
				// no auth
				$result=[
					'code'=>self::CODE_OK,
					'data'=>$this->_mockDataForPlayerapi(),
				];
				return $this->returnSuccessWithResult($result);
				break;
			case 'game-types2':
				break;
			case 'locale':
				break;
			case 'payment-apis':
				break;
			case 'phone-codes':
				return $this->getCountryPhoneCodes();
				break;
			case 'templates':
				break;
			case 'time-zone':
				break;
		}

		$this->returnErrorWithCode(self::CODE_GENERAL_CLIENT_ERROR);
	}

	public function site_properties($action, $additional=null){
		if(!$this->initApi()){
			return;
		}

		switch ($action) {
			case 'site-info':
				// no auth
				$result=[
					'code'=>self::CODE_OK,
					'data'=>$this->_mockDataForPlayerapi(),
				];
				return $this->returnSuccessWithResult($result);
				break;
			case 'customer-service-info':
				break;
			case 'customer-service-info-all':
				break;
		}

		$this->returnErrorWithCode(self::CODE_GENERAL_CLIENT_ERROR);

	}

	protected function getAvailableBanks() {
		$result=['code'=>self::CODE_OK];
		$request_body = $this->playerapi_lib->getRequestPramas();
		$this->comapi_log(__METHOD__, '=======request_body', $request_body);
		$currency = !empty($request_body['currency']) ? $request_body['currency'] : null;
		$available_banks = $this->playerapi_lib->switchCurrencyForAction($currency, function() {
			return $this->playerapi_model->getAvailableBankTypes();
		});
		$output = [];
		foreach ($available_banks as $key => $bank_item) {
			$output[$key] = $bank_item;
			$output[$key]['name'] = lang($bank_item['bankName']);
			unset($output[$key]['bankName']);
			$output[$key]['type'] = $this->playerapi_lib->matchOutputPaymentTypeFlag($bank_item['payment_type_flag']);
			$output[$key]['enabled'] = (bool)$bank_item['enabled'];
			$output[$key]['icon'] = (!empty($bank_item['icon'])) ? $this->utils->getBankIcon($bank_item['icon'], true) : '';

            $method = [];
            if($bank_item['enabled_deposit']){
                array_push($method, 'deposit');
            }
            if($bank_item['enabled_withdrawal']){
                array_push($method, 'withdraw');
            }
            $output[$key]['method'] = $method;
		}

		$output = $this->playerapi_lib->convertOutputFormat($output);
		$result['data'] = $output;
		return $this->returnSuccessWithResult($result);
	}

	protected function getAllCurrencies() {
		$result=['code'=>self::CODE_OK];
		$all_currencies = $this->utils->getAvailableCurrencyList();
        $all_currencies = $this->utils->filterAvailableCurrencyList4enableSelection($all_currencies, 'new_player_center_api');
		// $active_currency_key = $this->utils->getActiveCurrencyKeyOnMDB();
		$output_currncies = [];
		$count = 0;
		if(!empty($all_currencies)) {
			foreach ($all_currencies as $currency_key => $currency_info) {
				$output_currncies[$count]['currency'] = strtoupper($currency_key);
				$output_currncies[$count]['active'] = true;
				// $output_currncies[$count]['active'] = false;
				// if($currency_key == $active_currency_key) {
				// 	$output_currncies[$count]['active'] = true;
				// }
				$count++;
			}
		}
		$result['data'] = $output_currncies;
		return $this->returnSuccessWithResult($result);
	}

	protected function getCurrenciesRate() {
		$result=['code'=>self::CODE_OK];
		$this->load->model('currency_conversion_rate');
		$request_body = $this->playerapi_lib->getRequestPramas();
		$this->comapi_log(__METHOD__, '=======request_body', $request_body);
		$currency = !empty($request_body['currency']) ? $request_body['currency'] : null;
		$output = $this->playerapi_lib->switchCurrencyForAction($currency, function() {
			$rateList = $this->currency_conversion_rate->getCurrencyRateList();
			$output = [];
			foreach ($rateList as $key => $rate_item) {
				$output[$key]['baseCurrency'] = $rate_item['resource_currency'];
				$output[$key]['targetCurrency'] = $rate_item['target_currency'];
				$output[$key]['rate'] = $rate_item['rate'];
				$output[$key]['updatedAt'] = $rate_item['updatedAt'];
			}
			return $output;
		});
		$result['data'] = $this->playerapi_lib->convertOutputFormat($output);
		return $this->returnSuccessWithResult($result);
	}

	protected function getCountryPhoneCodes() {
		$result=['code'=>self::CODE_OK];
		$all_country_phone_codes = unserialize(COUNTRY_NUMBER_LIST_FULL);
		$frequent_country_phone_codes = $this->utils->getConfig('player_center_api_frequent_phone_code');
		$out_all_country_phone_codes = [];
		$out_frequent_country_phone_codes = [];
		foreach ($all_country_phone_codes as $country => $phone_code) {
			$each_country_phone_code['country'] = $country;
			$each_country_phone_code['phoneCode'] = (int)$phone_code;
			$out_all_country_phone_codes[] = $each_country_phone_code;
		}
		if(!empty($frequent_country_phone_codes)) {
			foreach ($frequent_country_phone_codes as $country => $phone_code) {
				$each_country_phone_code['country'] = $country;
				$each_country_phone_code['phoneCode'] = (int)$phone_code;
				$out_frequent_country_phone_codes[] = $each_country_phone_code;
			}
		}
		$result['data']['all'] = $out_all_country_phone_codes;
		$result['data']['frequent'] = $out_frequent_country_phone_codes;
		return $this->returnSuccessWithResult($result);
	}

	public function site($action, $additional=null){
		if(!$this->initApi()){
			return;
		}

		$request_method = $this->input->server('REQUEST_METHOD');

		switch($action){
			case 'switch':
				// site/switch/currency, post only
				if($additional=='currency' && $this->_isPostMethodRequest()){
					// switch currency
					$currency=strtolower($this->getParam('currency'));
					if($this->utils->isAvailableCurrencyKey($currency, false)){
						//switch to target db
						$_multiple_db=Multiple_db::getSingletonInstance();
						$_multiple_db->switchCIDatabase($currency);
						$result=['code'=>self::CODE_OK];
						return $this->returnSuccessWithResult($result);
					}else{
						return $this->returnErrorWithCode(self::CODE_CURRENCY_NOT_AVAILABLE);
					}
				}
				break;
			case 'currencies':
				if ($additional=='rate') {
					return $this->getCurrenciesRate();
				} else {
					return $this->getAllCurrencies();
				}
				break;
			case 'support':
				if ($additional=='country') {
					return $this->getAllCountryInfo();
				}
				break;
			case 'fields':
				if($additional == 'aliases'){
					$result=['code'=>self::CODE_OK];
					$player_profile = [
					   'im1'=> lang('Instant Message 1'),
					   'im2'=> lang('Instant Message 2'),
					   'im3'=> lang('Instant Message 3')
					];

					$visable_check_list = [
					   'im1'=>'imAccount',
					   'im2'=>'imAccount2',
					   'im3'=>'imAccount3'
					];

					$this->playerapi_lib->filterPlayerProfileVisable($player_profile, $visable_check_list);

					$result['data']['player']['profile'] = (object)$player_profile;
					return $this->returnSuccessWithResult($result);
				}
				break;
			case 'config':
				return $this->getSiteConfigs();
				break;
			case 'traffic_stat':
				if($request_method == 'POST'){
					return $this->traffic_statistics($additional);
				}
				break;	
			case 'cms':
				if($additional == 'popup'){
					return $this->getActiveCmsPopupBanner();
				}
				break;
			case 'crypto':
				if($additional == 'rate'){
					return $this->getCryptoRate();
				}
				break;
		}
	}

	public function currency($action, $additional=null){
		if(!$this->initApi()){
			return;
		}

		switch($action){
			case 'config':
				return $this->getCurrencyConfig();
				break;
		}
	}

	protected function getCryptoRate(){
		try{

			$allowed_crypto_currency = $this->utils->getConfig('cryptocurrencies');
			$allowed_currency = array_map('strtoupper',array_keys($this->utils->getConfig('multiple_currency_list')));
			$validate_fields = [
				['name' => 'baseCurrency', 'type' => 'string', 'required' => true, 'length' => 0, 'allowed_content' => $allowed_crypto_currency],
				['name' => 'targetCurrency', 'type' => 'string', 'required' => true, 'length' => 0, 'allowed_content' => $allowed_currency],
				['name' => 'amount', 'type' => 'string', 'required' => false, 'length' => 0],
			];

			$result=['code'=>self::CODE_OK];
			$request_body = $this->playerapi_lib->getRequestPramas();
			$this->comapi_log(__METHOD__, '=======request_body', $request_body);

			$request_body['baseCurrency'] = strtoupper($request_body['baseCurrency']);
			$request_body['targetCurrency'] = strtoupper($request_body['targetCurrency']);

			$is_validate_basic_passed = $this->playerapi_lib->validParmasBasic($request_body, $validate_fields);
			$this->comapi_log(__METHOD__, '=======is_validate_basic_passed', $is_validate_basic_passed);

			if(!$is_validate_basic_passed['validate_flag']) {
				throw new APIException($is_validate_basic_passed['validate_msg'], self::CODE_INVALID_PARAMETER);
			}

			$baseCurrency = $request_body['baseCurrency'];
			$targetCurrency = $request_body['targetCurrency'];
			$amount = !empty($request_body['amount']) ? $request_body['amount'] : 1;

			$cryptoRate = $this->playerapi_lib->switchCurrencyForAction($targetCurrency, function() use ($targetCurrency, $baseCurrency, $amount){
				return $this->calculateCryptoRate($targetCurrency, $baseCurrency, $amount);
			});

			$exec_continue = $this->playerapi_lib->checkIfExecContinueAfterVerify($cryptoRate);

			if(!$exec_continue) {
				throw new APIException($cryptoRate['error_message'], self::CODE_INVALID_PARAMETER);
			}

			$result['data'] = $this->playerapi_lib->convertOutputFormat($cryptoRate['result']);
			return $this->returnSuccessWithResult($result);

		}catch(\APIException $ex){
			$result['code'] = $ex->getCode();
			$result['errorMessage']= $ex->getMessage();
			$this->comapi_log(__METHOD__, 'APIException', $result);

			return $this->returnErrorWithResult($result);
		}
	}

	private function calculateCryptoRate($targetCurrency, $baseCurrency, $amount)
    {
        $targetCurrency = strtoupper($targetCurrency);
        $baseCurrency = strtoupper($baseCurrency);
		$this->utils->debug_log(__METHOD__, 'start', ['targetCurrency' => $targetCurrency, 'baseCurrency' => $baseCurrency, 'amount' => $amount]);

        $verify_result = [
            'passed' => true,
            'error_message' => '',
            'result' => [
                'baseCurrency' => $baseCurrency,
                'targetCurrency' => $targetCurrency,
                'rate' => 0,
                'reverseRate' => 0,
            ],
        ];

        if($targetCurrency === $baseCurrency){
            $verify_result['result']['rate'] = 1;
            $verify_result['result']['reverseRate'] = 1;      
            return $verify_result; 
        }

        $force_using_fixed_usd_stablecoin_rate = $this->utils->getConfig('force_using_fixed_usd_stablecoin_rate');
        if($force_using_fixed_usd_stablecoin_rate && $targetCurrency === 'USD' && in_array($baseCurrency, ['USDT', 'USDC'])){
            $fixed_usd_stablecoin_rate = !empty($this->utils->getConfig('fixed_usd_stablecoin_rate'))? $this->utils->getConfig('fixed_usd_stablecoin_rate') : 1;
            $verify_result['result']['rate'] = $fixed_usd_stablecoin_rate;
            $verify_result['result']['reverseRate'] = 1 / $fixed_usd_stablecoin_rate;   
            return $verify_result; 
        }

        // @todo refactor : only get rate from db
        $convertCryptoCurrency = $this->utils->convertCryptoCurrency($amount, $targetCurrency, $baseCurrency, '', true);
        if(is_array($convertCryptoCurrency)){
            $verify_result['result']['rate'] = $convertCryptoCurrency[1];
            $verify_result['result']['reverseRate'] = $convertCryptoCurrency[0];
            return $verify_result;
        }

		return $verify_result;
	}

	private function getActiveCmsPopupBanner(){
		$result=['code'=>self::CODE_OK];
		$request_body = $this->playerapi_lib->getRequestPramas();
		$this->comapi_log(__METHOD__, '=======request_body', $request_body);
		$currency = !empty($request_body['currency']) ? $request_body['currency'] : null;

		if(!$this->utils->getConfig('enable_pop_up_banner_function')){
            $result['data'] = [];
			return $this->returnSuccessWithResult($result);
		}

		$popupBanner = $this->playerapi_lib->switchCurrencyForAction($currency, function() {
			return $this->getPopupBanner($this->player_id);
		});

		$result['data'][] = $this->playerapi_lib->convertOutputFormat($popupBanner);
		return $this->returnSuccessWithResult($result);
	}

	protected function traffic_statistics($trackingCode){
		try{
            $success = false;
            $result=['code'=>Playerapi::CODE_OK];
            $validate_fields = [
                ['name' => 'affTrackingSourceCode', 'type' => 'string', 'required' => false, 'length' => 0]
            ];

            $request_body = $this->playerapi_lib->getRequestPramas();
            $this->comapi_log(__METHOD__, '=======request_body', $request_body);

            $is_validate_basic_passed = $this->playerapi_lib->validParmasBasic($request_body, $validate_fields);
            $this->comapi_log(__METHOD__, '=======is_validate_basic_passed', $is_validate_basic_passed);
            if(!$is_validate_basic_passed['validate_flag']) {
                $result['code'] = Playerapi::CODE_INVALID_PARAMETER;
                $result['errorMessage']= $is_validate_basic_passed['validate_msg'];
                return $this->returnErrorWithResult($result);
            }

            $trackingSourceCode = !empty($request_body['affTrackingSourceCode']) ? $request_body['affTrackingSourceCode'] : null;

            if(!empty($trackingCode)){
                $this->load->model(['Affiliatemodel']);
                $affiliate = $this->Affiliatemodel->checkTrackingCode($trackingCode);
            }

            if(empty($affiliate)){
                throw new \APIException($this->codes[Playerapi::CODE_INVALID_AFFILIATE_CODE], Playerapi::CODE_INVALID_AFFILIATE_CODE);
            }

            $visitRecordId = null;
            if(!empty($trackingCode)){
                $this->load->model(['http_request']);
                $visitRecordId = $this->http_request->recordPlayerRegistration(null, $trackingCode, $trackingSourceCode);
                $this->comapi_log(__METHOD__, ['trackingCode'=>$trackingCode, 'trackingSourceCode'=>$trackingSourceCode]);
            }

            if(!empty($visitRecordId)){
                $success = true;
                $result['data']['visitRecordId'] = $visitRecordId;
            }

            if(!$success){
                throw new \APIException($this->codes[Playerapi::CODE_SERVER_ERROR], Playerapi::CODE_SERVER_ERROR);
            }

            return $this->returnSuccessWithResult($result);
        } catch  (\APIException $ex){
            $result['code'] = $ex->getCode();
			$result['errorMessage']= $ex->getMessage();
            $this->comapi_log(__METHOD__, 'APIException', $result);

            return $this->returnErrorWithResult($result);
        }
	}

	public function getPopupBanner($playerId){
		$this->load->model(['cms_model']);
		$this->load->library(array('cmsbanner_library'));
		$this->utils->debug_log(__METHOD__, 'start', ['playerId' => $playerId]);

		$availableBanner = [];
		$accessTime = $this->utils->getNowForMysql();
		$popupBanner = $this->cms_model->getVisiblePopupBanner();

		if(empty($popupBanner)){
			return $availableBanner;
		}

		$isDaterange = true;
		if($popupBanner['is_daterange'] == 1){
			$startDate = new DateTime($popupBanner['start_date']);
			$endDate = new DateTime($popupBanner['end_date']);

			$startDate = $this->utils->formatDateTimeForMysql($startDate);
			$endDate = $this->utils->formatDateTimeForMysql($endDate);

			if($accessTime < $startDate || $endDate < $accessTime){
				$this->utils->debug_log(__METHOD__, "not in the date range expect start[$startDate] end[$endDate]", ['playerId' => $playerId]);
				$isDaterange = false;
			}
		}

		$displayIn = json_decode($popupBanner['display_in'],true);

		if (is_array($displayIn)) {
			$displayInDesktop = (in_array(1, $displayIn) || in_array(3, $displayIn)) ? true : false;
			$displayInMobile = (in_array(2, $displayIn) || in_array(4, $displayIn)) ? true : false;
		}

		$redriectBtnName = (!empty(trim($popupBanner['redirect_btn_name']))) ? $popupBanner['redirect_btn_name'] : '';
		$redirectTo = false;
		if($popupBanner['redirect_to'] == 'enable'){
			$redirectTo = true;
			switch ($popupBanner['redirect_type']) {
				case '1':
					$redirectType = 'deposit';
					$redriectBtnName = $redriectBtnName?:lang('Deposit');
					break;
				case '2':
					$redirectType = 'referral';
					$redriectBtnName = $redriectBtnName?:lang('Refer a Friend');
					break;
				case '3':
					$redirectType = 'promotion';
					$redriectBtnName = $redriectBtnName?:lang('Promotions');
					break;
			}
		}

		$bannerSrc = '';
		$backgroundColor = '';
		if(!empty($popupBanner['banner_url'])){
			if($popupBanner['is_default_banner'] == 1) {
				$backgroundColor = $popupBanner['banner_url'];
			} else {

				if (!empty($popupBanner['banner_url']) && file_exists($this->cmsbanner_library->getUploadPath($popupBanner['banner_url']))) {
					$popupBanner['banner_url'] = $this->utils->getSystemUrl('player') . $this->cmsbanner_library->getPublicPath($popupBanner['banner_url']);
				}

				$bannerSrc = $popupBanner['banner_url'];
			}
		}

		$availableBanner = array(
			'popupBannerId' => $popupBanner['id'],
			'isActive' => isset($isDaterange) ? $isDaterange : false,
			'backgroundColor' => $backgroundColor,
			'bannerImageUrl' => $bannerSrc,
			'displayInDesktop' => isset($displayInDesktop) ? $displayInDesktop : false,
			'displayInMobile' => isset($displayInMobile) ? $displayInMobile : false,
			'redriectEnabled' => $redirectTo,
			'redirectType' => isset($redirectType) ? $redirectType : '',
			'redirectButtonText' => $redriectBtnName,
			'title' => ($popupBanner['title']?:''),
			'content' => $this->cms_model->decodePromoDetailItem($popupBanner['content']?:''),
		);

		return $availableBanner;
	}

	protected function getSiteConfigs(){
		try {
			$request_body = $this->playerapi_lib->getRequestPramas();
			$this->comapi_log(__METHOD__, '=======request_body', $request_body);
			$currency = !empty($request_body['currency']) ? $request_body['currency'] : null;
			$data = $this->playerapi_lib->switchCurrencyForAction($currency, function(){
	
				$data['cashierManagement'] = $this->getCashierManagementSetting();
				$data['withdrawalPasswordPolicy'] = $this->utils->getConfig('withdraw_verification') == 'withdrawal_password' ? self::WITHDRAWAL_PASSWORD_POLICY_ENABLE : self::WITHDRAWAL_PASSWORD_POLICY_DISABLE;
				$data['registrationFields'] = $this->getRegistrationFieldsSetting();
				$data['profileFields'] = $this->getProfileFieldsSetting();
				$data['registrationSettings'] = $this->getRegistrationSettings();
				$data['loginSettings'] = $this->getLoginSettings();
				$data['responsibleSettings'] = $this->getResponsibleGameSettings();
				$data['questEnabled'] = $this->utils->getConfig('enabled_quest');
                $data['sso'] = $this->getEnabledSsoSettings();
                $data['walletInfo'] = $this->getEnabledCryptoWalletSso();
				$data['kycConfig'] = $this->getKycEnabledSettings();
				$data['siteLanguages'] = $this->getSiteLanguages();
				return $data;
			});
			$result['code'] = Playerapi::CODE_OK;
			$result['data'] = $data;
            return $this->returnSuccessWithResult($result);
        }
        catch (\APIException $ex) {
            $result['code'] = $ex->getCode();
			$result['errorMessage']= $ex->getMessage();
            $this->comapi_log(__METHOD__, 'APIException', $result);

            return $this->returnErrorWithResult($result);
        }
	}

	protected function getCurrencyConfig(){
		try {
			$allowed_currency = array_map('strtoupper',array_keys($this->utils->getConfig('multiple_currency_list')));
			$validate_fields = [
				['name' => 'currency', 'type' => 'string', 'required' => true, 'length' => 0, 'allowed_content' => $allowed_currency]
			];
			$this->comapi_log(__METHOD__, '=======validate_fields', $validate_fields);

			$result=['code'=>self::CODE_OK];
			$request_body = $this->playerapi_lib->getRequestPramas();
			$this->comapi_log(__METHOD__, '=======request_body', $request_body);

			$is_validate_basic_passed = $this->playerapi_lib->validParmasBasic($request_body, $validate_fields);
			$this->comapi_log(__METHOD__, '=======is_validate_basic_passed', $is_validate_basic_passed);

			if(!$is_validate_basic_passed['validate_flag']) {
				throw new APIException($is_validate_basic_passed['validate_msg'], self::CODE_INVALID_PARAMETER);
			}

			$currency = $request_body['currency'];
			$data = $this->playerapi_lib->switchCurrencyForAction($currency, function(){
				$data['cashierManagement'] = $this->getCashierManagementSetting();
				// $data['withdrawalPasswordPolicy'] = $this->utils->getConfig('withdraw_verification') == 'withdrawal_password' ? self::WITHDRAWAL_PASSWORD_POLICY_ENABLE : self::WITHDRAWAL_PASSWORD_POLICY_DISABLE;
				$data['registrationFields'] = $this->getRegistrationFieldsSetting();
				$data['profileFields'] = $this->getProfileFieldsSetting();
				$data['registrationSettings'] = $this->getRegistrationSettings();
				$data['loginSettings'] = $this->getLoginSettings();
                $data['responsibleSettings'] = $this->getResponsibleGameSettings();
                $data['kycConfig'] = $this->getKycEnabledSettings();
				// $data['questEnabled'] = $this->utils->getConfig('enabled_quest');
                // $data['sso'] = $this->getEnabledSsoSettings();
				return $data;
			});
			$result['code'] = Playerapi::CODE_OK;
			$result['data'] = $data;
            return $this->returnSuccessWithResult($result);
        }
        catch (\APIException $ex) {
            $result['code'] = $ex->getCode();
			$result['errorMessage']= $ex->getMessage();
            $this->comapi_log(__METHOD__, 'APIException', $result);

            return $this->returnErrorWithResult($result);
        }
	}

	private function getRegistrationSettings(){
		$result = [];
		$result['captcha'] = $this->getCaptchaSetting('register');
		return $result;
	}

	private function getLoginSettings(){
		$result = [];
		$result['captcha'] = $this->getCaptchaSetting('login');
		return $result;
	}

	private function getResponsibleGameSettings(){
		$this->load->model(['responsible_gaming']);

		$result = [
	        'selfExclusion' => [],
	        'coolOff' => [],
	        'depositLimits' => [],
	    ];

    	$options_self_excl = $this->responsible_gaming->getTempPeriodList();
    	foreach ($options_self_excl as $key => $op) {
    		$options_self_excl[(int) $key] = $op;
    		unset($options_self_excl[$key]);
    	}

		$options_time_out = [
			1 => lang('24 Hours'),
			7 => lang('One Week'),
			30  => lang('One Month'),
			42  => lang('6 Weeks'),
		];


    	$options_dep_lim0 = $this->utils->getConfig('deposit_limits_day_options');
    	$options_dep_lim = [];
    	foreach ($options_dep_lim0 as $key => $op) {
    		$options_dep_lim[$op] = "{$op} days";
    	}

		$result = [
	        'selfExclusion' => [
				'temporary' => $options_self_excl,
			],
	        'coolOff' => $options_time_out,
	        'depositLimits' => $options_dep_lim,
	    ];

		return $result;
	}

	private function getCaptchaSetting($source){
		$result = [
			"enabled" => false
		];
		$is_captcha_enabled = false;
		$third_party_captcha_settings = $this->utils->getConfig('enabled_captcha_of_3rdparty');
		$is_thirdparty_captcha_enabled = !empty($third_party_captcha_settings['active_region']) && in_array($source, $third_party_captcha_settings['active_region']);
		if($source == 'register'){
			$is_captcha_enabled = ($this->utils->getConfig('enabled_registration_captcha') && $this->operatorglobalsettings->getSettingJson('registration_captcha_enabled'));
		}
		if($source == 'login'){
			$is_captcha_enabled = ($this->operatorglobalsettings->getSettingJson('login_captcha_enabled'));
		}
		if($is_captcha_enabled){
			$result['enabled'] = (bool)$is_captcha_enabled;
			if($is_thirdparty_captcha_enabled){
				$result['requireParams'][] = 'captchaToken';
			}else{
				$result['requireParams'][] = 'captchaCode';
				$result['requireParams'][] = 'captchaKey';
			}
		}
		return $result;
	}

	private function getRegistrationFieldsSetting(){
		$this->load->model(['registration_setting']);
		$result = [];
		$excludedList = $this->utils->getConfig('excluded_in_registration_settings');
		$playerPreferenceList = $this->registration_setting->getPlayerPreferenceAlias();
		$registrationFields = $this->registration_setting->getRegistrationFieldsByAlias();
		$registrationFields['username']['visible']   = Registration_setting::VISIBLE;
		$registrationFields['username']['required']  = Registration_setting::REQUIRED;
		$registrationFields['username']['fieldType'] = Registration_setting::FIELD_TYPE_FREE_INPUT;
		$registrationFields['password']['visible']   = Registration_setting::VISIBLE;
		$registrationFields['password']['required']  = Registration_setting::REQUIRED;
		$registrationFields['password']['fieldType'] = Registration_setting::FIELD_TYPE_FREE_INPUT;

		foreach ($registrationFields as $alias => $settings) {
			$data = [];
			if(!in_array($alias, $excludedList) && !empty($alias) && $settings['visible'] == Registration_setting::VISIBLE){
				if(in_array($alias, $playerPreferenceList) && $registrationFields['player_preference']['visible'] != Registration_setting::VISIBLE){
					continue;
				}
				if($alias == 'player_preference'){
					continue;
				}
				$data['name'] = $this->playerapi_lib->matchOutputRegistrationNames($alias);
				$data['required'] = ($settings['required'] == Registration_setting::REQUIRED)? true : false;
				$data['fieldType'] = $this->playerapi_lib->matchOutputRegistrationFieldType($settings['fieldType']);
				$formatRestriction = $this->getFormatRestrictionByAlias($alias);
				if(!empty($formatRestriction)){
					$data['formatRestriction'] = $formatRestriction;
				}
				if(!empty($settings['options'])){
					$optionList = json_decode($settings['options'], true);
					$data['options'] = $optionList;
				}
				switch ($data['name']) {
					case 'birthday':
						$data['ageRestrictions'] = $this->operatorglobalsettings->getSettingJson('registration_age_limit');
						break;
					case 'ageRestrictions':
						$data['ageRestrictions'] = $this->operatorglobalsettings->getSettingJson('registration_age_limit');
						break;
				}
                array_push($result, $data);
            }
        }
		return $result;
	}

    private function getEnabledSsoSettings(){
        $result = [];
        if(!empty($this->utils->getConfig('google_credential'))){
            $result[] = 'google'; 
        }
        if(!empty($this->utils->getConfig('facebook_credential'))){
            $result[] = 'facebook'; 
        }
        return $result;
    }

    private function getEnabledCryptoWalletSso(){
        $credential = $this->utils->getConfig('crypto_wallet_credential');
        $result = [
            'enable' => !empty($credential),
            'projectId' => !empty($credential['project_id']) ? $credential['project_id'] : '',
            'appName' =>  !empty($credential['app_name']) ? $credential['app_name'] : '',
        ];
        return $result;
    }

    private function getKycEnabledSettings(){
	    $this->load->model(['multiple_db_model','system_feature']);

        $configDepositNeedKyc = $this->utils->getConfig('deposit_need_kyc');
        $curCurrency = strtolower($this->utils->getCurrentCurrency()['currency_code']);
		$currencyInfo = $this->multiple_db_model->getCurrencyByDB($this->db);
		
        $result = [];

        $showKycStatus = $this->system_feature->isEnabledFeatureWithoutCache('show_kyc_status');
        $showUploadDocuments = $this->system_feature->isEnabledFeatureWithoutCache('show_upload_documents'); 
		$enabledSettings = $showKycStatus && $showUploadDocuments;
		$enableSubKyc = $this->system_feature->isEnabledFeatureWithoutCache('show_player_upload_realname_verification') || $this->system_feature->isEnabledFeatureWithoutCache('show_player_upload_proof_of_address') || $this->utils->isEnabledFeature('show_player_upload_proof_of_income') || $this->system_feature->isEnabledFeatureWithoutCache('show_player_upload_proof_of_deposit_withdrawal');

		$result['kycEnable'] = $enabledSettings && $enableSubKyc;
        $result['withdrawNeedKyc'] = $this->system_feature->isEnabledFeatureWithoutCache('show_allowed_withdrawal_status') && $this->system_feature->isEnabledFeatureWithoutCache('show_risk_score') && $showKycStatus;

        $currencyCode = strtolower($currencyInfo['code']);
        $depositNeedKyc = false;
        if ($configDepositNeedKyc && isset($configDepositNeedKyc[$currencyCode]) && $configDepositNeedKyc[$currencyCode]) {
            $depositNeedKyc = true;
        }
        
		$result['depositNeedKyc'] = $depositNeedKyc && $showKycStatus; 

		return $result;
	}

	public function getSiteLanguages() {
		$this->load->model(['multiple_db_model', 'operatorglobalsettings']);
	
		$db = $this->db;
		$currencyInfo = $this->multiple_db_model->getCurrencyByDB($db);
		$playerDefaultLanguage = $currencyInfo['player_default_language'];
		// $this->utils->debug_log(__METHOD__, 'playerDefaultLanguage:', $playerDefaultLanguage);
		
		$playerDefaultLanguageCode = !empty(Language_function::ISO_PLAYER_LANG_COUNTRY[$playerDefaultLanguage]) 
			? Language_function::ISO_PLAYER_LANG_COUNTRY[$playerDefaultLanguage] 
			: Language_function::ISO_PLAYER_LANG_COUNTRY[Language_function::PLAYER_LANG_ENGLISH];
		$siteLanguages = [];
		$enabled_language = $this->operatorglobalsettings->getSettingJson('player_center_enabled_language');

		if (!empty($enabled_language)) {
			foreach ($enabled_language as $lang) {
				$langCode= Language_function::ISO_LANG_COUNTRY[$lang];
				if($langCode == $playerDefaultLanguageCode){
					array_unshift($siteLanguages, $langCode);
					continue;
				}
				array_push($siteLanguages, $langCode);
			}
		}else{
			$siteLanguages = [$playerDefaultLanguageCode];
		}
		
		return $siteLanguages;
	}

	public function getFormatRestrictionByAlias($alias){
		$result = [];
		$alias = $this->utils->toSnakeCase($alias);
		if($alias == 'pix_number'){
			$alias =  'cpf_number';
		}
		$validateFieldSetting = $this->playerapi_lib->getValidateFieldSetting($alias);

		if(!empty($validateFieldSetting)){
			if(!empty($validateFieldSetting['min'])){
				$result['minLength'] = (int)$validateFieldSetting['min'];
			}

			if(!empty($validateFieldSetting['max'])){
				$result['maxLength'] = (int)$validateFieldSetting['max'];
			}

			if(!empty($validateFieldSetting['regexType'])){
				$result['restrictionType'] = (int)$validateFieldSetting['regexType'];
			}

			if(!empty($validateFieldSetting['excludeFields'])){
				$result['excludeFields'] = $validateFieldSetting['excludeFields'];
			}
		}
		return $result;
	}

	private function getProfileFieldsSetting(){
		$this->load->model(['registration_setting']);
		$result = [];
		$excludedList = $this->utils->getConfig('excluded_in_account_info_settings');
		$registrationFields = $this->registration_setting->getRegistrationFieldsByAlias();
		$edited_only_once_in_player = $this->utils->getConfig('only_edited_once_in_player');

		if(!$this->utils->getConfig('temp_disable_sbe_acc_setting')){
			foreach ($registrationFields as $alias => $settings) {
				$data = [];
				if(!in_array($alias, $excludedList) && !empty($alias) && $settings['account_visible'] == Registration_setting::VISIBLE){
					$account_edit = ($settings['account_edit'] == Registration_setting::EDIT_ENABLED) ? 'always' : 'once';
					$data['name'] = $this->playerapi_lib->matchOutputRegistrationNames($alias);
					$data['required'] = ($settings['account_required'] == Registration_setting::REQUIRED)? true : false;
					$data['editable'] = in_array($alias, $edited_only_once_in_player) ? 'once' : $account_edit;

					$formatRestriction = $this->getFormatRestrictionByAlias($alias);
					if(!empty($formatRestriction)){
						$data['formatRestriction'] = $formatRestriction;
					}
                    if(!empty($settings['options'])){
                        $optionList = json_decode($settings['options'], true);
                        $data['options'] = $optionList;
                    }
					switch ($data['name']) {
						case 'birthday':
							$data['ageRestrictions'] = $this->operatorglobalsettings->getSettingJson('registration_age_limit');
							break;
					}
					array_push($result, $data);
				}
			}
		}else{
			foreach ($edited_only_once_in_player as $fieldName) {
				$result[] = [
					'name' => $this->playerapi_lib->matchOutputProfileColumnName($fieldName),
					'editable' => "once",
				];
			}
		}
		return $result;
	}

	public function getFinancialAccountSetting($payment_type_flag = '1'){
		$this->load->model(['financial_account_setting']);
		$financial_account_rule = $this->financial_account_setting->getPlayerFinancialAccountRulesByPaymentAccountFlag($payment_type_flag);
		return $this->convertFinancialAccountFormat($financial_account_rule);
	}

	public function convertFinancialAccountFormat($data) {
		$result = [];

		$fieldRequired = explode(',', $data['field_required']);
		$fieldShow = explode(',', $data['field_show']);
		$paymentTypeFlag = $data['payment_type_flag'];

		$result['otpVerify'] = in_array(Financial_account_setting::FIELD_OTP_VERIFY, $fieldShow) ? true : false;

		$result['accountNumber'] = [
			'maxLength' => (int)$data['account_number_max_length'],
			'minLength' => (int)$data['account_number_min_length'],
			'allowNumberOnly' => $data['account_number_only_allow_numeric'] === "1",
		];

		$isFieldEnabledAndRequired = function($fieldName) use ($fieldShow, $fieldRequired) {
			return [
				'enabled' => in_array($fieldName, $fieldShow),
				'required' => in_array($fieldName, $fieldRequired),
			];
		};

		$result['bankAccountFullName'] = array_merge(
			$isFieldEnabledAndRequired(Financial_account_setting::FIELD_NAME),
			['editAccountName' => $data['account_name_allow_modify_by_players'] === "1"]
		);

		$fields = [];
		switch ($paymentTypeFlag) {
			case Financial_account_setting::PAYMENT_TYPE_FLAG_BANK:
				$fields = [
					'phoneNumber' => Financial_account_setting::FIELD_PHONE,
					'branchName' => Financial_account_setting::FIELD_BANK_BRANCH,
					'provinceAndCity' => Financial_account_setting::FIELD_BANK_AREA,
					'branchAddress' => Financial_account_setting::FIELD_BANK_ADDRESS,
				];
				break;
			case Financial_account_setting::PAYMENT_TYPE_FLAG_EWALLET:
				$fields = [
					'phoneNumber' => Financial_account_setting::FIELD_PHONE,
				];
				break;
		}

		foreach ($fields as $key => $field) {
			$result[$key] = $isFieldEnabledAndRequired($field);
		}

		return $result;
	}

	private function initializeResult(){
		return [
			'deposit' => [],
			'withdraw' => [],
			'previousBalanceSetting' => ''
		];
	}

	private function initializeCashierManagementType(){
		return [
			Financial_account_setting::PAYMENT_TYPE_FLAG_BANK    => false,
			Financial_account_setting::PAYMENT_TYPE_FLAG_EWALLET => false,
			Financial_account_setting::PAYMENT_TYPE_FLAG_CRYPTO  => false,
			Financial_account_setting::PAYMENT_TYPE_FLAG_PIX     => false,
		];
	}

	private function processPaymentType($type, $activeFlag, $enabled, $outputType, &$result){
		if(in_array($type, $activeFlag['deposit'])){
			$enabled = (bool)$this->operatorglobalsettings->getSettingValueWithoutCache('financial_account_enable_deposit_bank');
			$result['deposit'][$outputType] = $this->getPaymentTypeInfo($type, $enabled);
		}

		if(in_array($type, $activeFlag['withdrawal'])){
			$result['withdraw'][$outputType] = $this->getPaymentTypeInfo($type, $enabled);
		}
	}

	private function getPaymentTypeInfo($type, $enabled){
		if($type == Financial_account_setting::PAYMENT_TYPE_FLAG_PIX){
			$pixSystemInfo = $this->utils->getConfig('pix_system_info');
			$enabled = $pixSystemInfo['edit_pix_account']['enabled'];

			return ['enabled' => $enabled, 'editable' => $enabled];
		}else{
			$financialAccount = $this->getFinancialAccountSetting($type);
			return array_merge(['enabled' => $enabled, 'editable' => $enabled], $financialAccount);
		}
	}

	private function finalizeResult(&$result){
		$result['requireDepositBank'] = (bool)$this->operatorglobalsettings->getSettingValueWithoutCache('financial_account_require_deposit_bank_account');
		$result['requireWithdrawBank'] = (bool)$this->operatorglobalsettings->getSettingValueWithoutCache('financial_account_require_withdraw_bank_account');
		$result['singleAccountPerBank'] = (bool)$this->operatorglobalsettings->getSettingValueWithoutCache('financial_account_one_account_per_institution');

		$previousBalanceSetting = $this->payment_manager->getOperatorGlobalSetting('previous_balance_set_amount');
		$result['previousBalanceSetting'] = (float)$previousBalanceSetting[0]['value'];
	}

	private function getCashierManagementSetting(){
		$this->load->model(['banktype','financial_account_setting']);
		$this->load->library(['payment_manager']);
		$activeFlag = $this->banktype->getDistinctActivePaymentTypeFlag();
		$result = $this->initializeResult();

		$cashierManagementType = $this->initializeCashierManagementType();

		foreach ($cashierManagementType as $type => $editable) {
			$enabled = true;
			$outputType = $this->playerapi_lib->matchOutputPaymentTypeFlag($type);
			$this->processPaymentType($type, $activeFlag, $enabled, $outputType, $result);
		}

		$this->finalizeResult($result);
		return $result;
	}

	protected function getAllCountryInfo() {
		$result = ['code' => self::CODE_OK];
		$all_country_iso2_codes = $this->playerapi_lib->getCountryCodeList();
		$all_country_phone_codes = $this->playerapi_lib->getCountryPhoneCodeList();
		$all_iso_lang_country = Language_function::ISO_LANG_COUNTRY;
		$enable_multiple_lang = $this->utils->getConfig('enable_multiple_langs_when_getting_the_country_list');
		$current_lang = $this->indexLanguage;
        $enable_default_dialing_code_by_ip = $this->utils->getConfig('enable_default_dialing_code_by_ip');
		$cache_key = "getAllCountryInfo-$current_lang-$enable_multiple_lang";
		$cached_result = $this->utils->getJsonFromCache($cache_key);
		if (!empty($cached_result) && !$enable_default_dialing_code_by_ip) {
			$this->comapi_log(__METHOD__, ['cached_result' => $cached_result]);
			$result['data'] = $cached_result;
			return $this->returnSuccessWithResult($result);
		}

		$output = [];
		foreach ($all_country_iso2_codes as $country => $code) {
			$lang_result = $enable_multiple_lang ?
				array_map(function ($int_lang) use ($country, $all_iso_lang_country) {
					return [
						'lang' => $all_iso_lang_country[$int_lang],
						'content' => lang('country.' . $country, $int_lang)
					];
				}, array_keys($all_iso_lang_country)) :
				lang('country.' . $country, $current_lang);

			$phone_code = isset($all_country_phone_codes[$country]) ? $all_country_phone_codes[$country] : '';

			$output[] = [
				'iso2' => $code,
				'name' => $lang_result,
				'phoneCode' => $phone_code,
				'isDefault' => $this->isDefaultDialingCode($country, $phone_code)
			];
		}

		$ttl = 6 * 60 * 60;
		$this->utils->saveJsonToCache($cache_key, $output, $ttl);

		$result['data'] = $output;
		return $this->returnSuccessWithResult($result);
	}

    protected function isDefaultDialingCode($countryName, $countryPhoneCode) {
        $defaultByCountry = $this->utils->getConfig('enable_default_dialing_code');
        $defaultByIp = $this->utils->getConfig('enable_default_dialing_code_by_ip');

        // first priority
        if(!empty($defaultByCountry)) {
            /* construct default dialing code by country
                $config['enable_default_dialing_code'] = [
                    "Philippines" => "63",
                ];
            */
            if(isset($defaultByCountry[$countryName])){
                return true;    
            }

            if(in_array($countryPhoneCode, $defaultByCountry)){
                return true;
            }

            return false;
        }

        // second priority
        if($defaultByIp){
            $ip = $this->utils->getIP();
            $ipCountry = $this->utils->getCountry($ip);

            if($ipCountry == $countryName){
                return true;
            }

            return false;
        }

        return false;
    }

}
