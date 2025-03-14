<?php

/**
 * uri: /bonuses, /campaigns, /promotions
 *
 * @property playerapi_lib $playerapi_lib
 * @property Playerapi_model $playerapi_model
 * @property Promorules $promorules
 */
trait player_promotion_module{

	public function bonuses($action, $additional=null){
		if(!$this->initApi()){
			return;
		}
		$this->load->library(['playerapi_lib']);
		$this->load->model(['playerapi_model', 'promorules']);
		$request_method = $this->input->server('REQUEST_METHOD');

		switch ($action) {
			case 'campaign':
				if($request_method == 'GET') {
					return $this->getPromotionReuquetsByPlayerId($this->player_id);
				}
				break;
			case 'cashback':
				if($request_method == 'GET') {
					return $this->getCashbackReuquetsByPlayerId($this->player_id);
				}
				break;
			case 'referral':
				break;
		}

		return $this->returnErrorWithCode(Playerapi::CODE_GENERAL_CLIENT_ERROR);
	}

	public function campaigns($action=null, $additional=null, $append=null)
	{
		if (!$this->initApi()) {
			return;
		}
		$this->load->library(['playerapi_lib']);
		$this->load->model(['playerapi_model']);
		$request_method = $this->input->server('REQUEST_METHOD');

		$additional = $additional ? trim($additional, "\t\n\r\0\x0B\x2C") : null;
		$this->utils->debug_log(__METHOD__, '======campaigns',$action, $additional, $append);

		switch($action) {
			case 'public':
				if($additional == 'info' && !empty($append)){
					return $this->getPublicPromotionsById($append);
				}else{
					return $this->getPromotions();
				}
				break;
			case 'category':
				return $this->getPromotionCategory();
				break;
			case 'info':
				if(is_numeric($additional) && $additional > 0 && $additional == round($additional, 0)){
					return $this->getPromoCmsSettingById($this->player_id, $additional);
				}
				break;
			case 'items':
			default:
				if ($request_method == 'GET') {
					if (!empty($additional)) {
						return $this->getPromoCmsSettingByPlayerId($this->player_id, $additional);
					}
					return $this->getPromoCmsSettingByPlayerId($this->player_id);
				}
				break;
		}

		return $this->returnErrorWithCode(Playerapi::CODE_GENERAL_CLIENT_ERROR);
	}

	public function campaign($action=null, $id=null)
	{
		if (!$this->initApi()) {
			return;
		}
		$this->load->library(['playerapi_lib']);
		$this->load->model(['playerapi_model']);

		$id = $id ? trim($id, "\t\n\r\0\x0B\x2C") : null;

		switch($action) {
			case 'check':
				return $this->checkOnlyPromotion($this->player_id, $id);
				break;
			case 'apply':
				return $this->applyPromotion($this->player_id, $id);
				break;
			case 'info':
				return $this->getPromoCmsSettingById($this->player_id, $id);
				break;
		}

		return $this->returnErrorWithCode(Playerapi::CODE_GENERAL_CLIENT_ERROR);
	}

	public function redemption($action=null)
	{
		if (!$this->initApi()) {
			return;
		}
		$this->load->library(['playerapi_lib']);
		$this->load->model(['playerapi_model']);

		switch($action) {
			case 'apply':
				return $this->applyRedemptionCode($this->player_id);
				break;
		}

		return $this->returnErrorWithCode(Playerapi::CODE_GENERAL_CLIENT_ERROR);
	}

	public function promotions($action, $additional=null){
		if(!$this->initApi()){
			return;
		}

		$this->load->model(array('group_level'));

		switch ($action) {
			case 'cashback-setting':
				return $this->getCashbackSettings();
				break;
			case 'referral-custom-info':
				return $this->getReferralCustomInfo($this->player_id);
				break;
		}

		return $this->returnErrorWithCode(Playerapi::CODE_GENERAL_CLIENT_ERROR);
	}

	protected function getCashbackSettings(){
		$result = [
			'code' => Playerapi::CODE_OK,
			'data' => []
		];

		$playerId = $this->player_id;
		$curr_currency = $this->currency;

		$this->utils->debug_log(__METHOD__, 'curr_currency', $curr_currency, $playerId);

		$res = $this->playerapi_lib->loopCurrencyForAction($curr_currency, function($currency) use ($playerId){

			$cashBackSettings = $this->group_level->getCashbackSettings($playerId);
			$currentDateTime = new DateTime();
			$currentDate = $this->utils->formatDateForMysql($currentDateTime);
			$payDateTime = $currentDate . ' ' . $cashBackSettings->payTimeHour . ':00';

			return [
				'currency' => strtoupper($currency),
				'enabled' => true,
				'dailyCashbackStartTime' => $this->playerapi_lib->formatDateTime($payDateTime),
				'nextCalculationTime' => $this->playerapi_lib->formatDateTime($payDateTime),
				'maxBonusAmount' => $cashBackSettings->max_cashback_amount,
				'minBonusAmount' => $cashBackSettings->min_cashback_amount,
				'withdrawConditionAmount' => 0,
				'withdrawConditionBonusMultiplier' => 0,
				'withdrawConditionCalculationType' => 0,
				'withdrawConditionDepositCalculationType' => 0,
				'withdrawConditionDepositMultiplier' => 0,
				'withdrawConditionMinDeposit' => 0,
			];
		});

		$result['data'] = $this->playerapi_lib->convertOutputFormat($res);

		return $this->returnSuccessWithResult($result);
	}

	protected function getReferralCustomInfo($player_id)
	{
		try {
			$request_body = $this->playerapi_lib->getRequestPramas();
			$validate_fields = [
				['name' => 'referralType', 'type' => 'string', 'required' => false, 'length' => 0]
			];
			$is_validate_basic_passed = $this->playerapi_lib->validParmasBasic($request_body, $validate_fields);

			if (!$is_validate_basic_passed['validate_flag']) {
				throw new \APIException($is_validate_basic_passed['validate_msg'], Playerapi::CODE_INVALID_PARAMETER);
			}

			$referral_type = !empty($request_body['referralType'])? $request_body['referralType'] : null;
			$result = $this->getCustInfoByReferralType($player_id, $referral_type, $request_body);

			return $this->returnSuccessWithResult($result);
		} catch (\APIException $ex) {
			$result['code'] = $ex->getCode();
			$result['errorMessage']= $ex->getMessage();
            $this->comapi_log(__METHOD__, 'APIException', $result);

            return $this->returnErrorWithResult($result);
		}
	}

	protected function getPromotionCategory()
	{
		$result = [
			'code' => Playerapi::CODE_OK,
			'data' => []
		];

		$request_body = $this->playerapi_lib->getRequestPramas();
		$this->comapi_log(__METHOD__, '=======request_body', $request_body);
		$currency = !empty($request_body['currency']) ? $request_body['currency'] : null;

		if(is_null($currency)){
			$curr_currency = $this->currency;
			$public_campaigns_category_key = "public-campaigns-category";
			$public_campaigns_category_result = $this->utils->getJsonFromCache($public_campaigns_category_key);
			if (!empty($public_campaigns_category_result)) {
				$this->comapi_log(__METHOD__, ['cached_result' => $public_campaigns_category_result]);
				$result = $public_campaigns_category_result;
			} else {
				$rawData = $this->playerapi_lib->loopCurrencyForAction($curr_currency, function($currency) {
					$promo_data = array_map(function ($promo) use ($currency){
						$iconUrl = $this->playerapi_lib->ci->utils->getSystemUrl('player', $this->playerapi_lib->ci->utils->getPromoCategoryIcon($promo['icon']));

						return [
							'id' => $promo['id'],
							'name' => $promo['name'],
							'description' => $promo['promoTypeDesc'],
							'icon' => $iconUrl,
							'display' => $promo['displayPromo'],
							'currency' => strtoupper($currency),
							'nameLang' => $this->utils->replaceKeyToIsoLang($promo['nameLang']),
						];
					}, $this->utils->getAllPromoType());
					return $this->playerapi_lib->convertOutputFormat($promo_data);
				});
                foreach ($rawData as $key => $currencyList) {
                    $result['data'] = array_merge($result['data'], $currencyList);
                }
				$ttl = 60 * 60 * 8; // 8 hours
				$this->utils->saveJsonToCache($public_campaigns_category_key, $result, $ttl);
			}
		} else {
			$result['data'] = $this->playerapi_lib->switchCurrencyForAction($currency, function() use ($currency){
				$promo_data = array_map(function ($promo) use ($currency){
					$iconUrl = $this->playerapi_lib->ci->utils->getSystemUrl('player', $this->playerapi_lib->ci->utils->getPromoCategoryIcon($promo['icon']));
					
					return [
						'id' => $promo['id'],
						'name' => $promo['name'],
						'description' => $promo['promoTypeDesc'],
						'icon' => $iconUrl,
						'display' => $promo['displayPromo'],
						'currency' => strtoupper($currency ?: $this->currency),
						'nameLang' => $this->utils->replaceKeyToIsoLang($promo['nameLang']),
					];
				}, $this->utils->getAllPromoType());
				
				return $this->playerapi_lib->convertOutputFormat($promo_data);
			});
		}
		return $this->returnSuccessWithResult($result);
	}

	protected function getPromotions()
	{
		$result = [
			'code' => Playerapi::CODE_OK,
			'data' => []
		];

		try {
			$request_body = $this->playerapi_lib->getRequestPramas();
			$validate_fields = [
				['name' => 'categoryId', 'type' => 'int', 'required' => false, 'length' => 0],
				['name' => 'limit', 'type' => 'int', 'required' => false, 'length' => 0],
				['name' => 'page', 'type' => 'int', 'required' => false, 'length' => 0],
			];
			$is_validate_basic_passed = $this->playerapi_lib->validParmasBasic($request_body, $validate_fields);

			if (!$is_validate_basic_passed['validate_flag']) {
				throw new \APIException($is_validate_basic_passed['validate_msg'], Playerapi::CODE_INVALID_PARAMETER);
			}

			$currency = !empty($request_body['currency']) ? $request_body['currency'] : null;
			$category_id = (isset($request_body['categoryId'])) ? $request_body['categoryId'] : null;
			$limit = (isset($request_body['limit'])) ? $request_body['limit'] : 20;
			$page = (isset($request_body['page'])) ? $request_body['page'] : 1;
			$pagination = array('limit' => $limit, 'page' => $page);
			if(empty($category_id)){
				$pagination['only_visible_category'] = $this->utils->safeGetArray($request_body, 'onlyVisibleCategory', true);
			}
			$this->load->model(['cms_model']);

			if(is_null($currency)){
				//loop currency
				$curr_currency = $this->currency;
				unset($pagination['page']);
				unset($pagination['limit']);

				$public_campaigns_key = "public-campaigns";
				$params = [$category_id, $limit, $page];

				foreach ($params as $param) {
					if (!empty($param)) {
						$public_campaigns_key .= "-" . $param;
					}
				}
				$this->comapi_log(__METHOD__, '======public_campaigns_key', $public_campaigns_key);
				$public_campaigns_result = $this->utils->getJsonFromCache($public_campaigns_key);
				if (!empty($public_campaigns_result)) {
					$this->comapi_log(__METHOD__, ['cached_result' => $public_campaigns_result]);
					$result = $public_campaigns_result;
				} else {
					$result['data'] = $this->playerapi_lib->loopCurrencyForAction($curr_currency, function($currency) use ($category_id, $pagination){
						$promo_list_with_pagination = $this->promorules->getAllPromoPagination(null, $category_id, $pagination);
						$wrapped_promo_data = array_map(function($promo) use ($currency) {
							$promo['promoDetails'] =  $this->cms_model->decodePromoDetailItem($promo['promoDetails']);
							$current_lang = $this->playerapi_lib->getIsoLang($this->indexLanguage);
							$multi_lang = $this->promoItemMultiLangFields($promo, $current_lang);
							$map_data = [
								'currency' => strtoupper($currency),
								'promoId' => $promo['promoCmsSettingId'],
								'promoCategory' => $promo['promo_category'],
								'promoName' => $multi_lang['promoName'],
								'promoDescription' => $multi_lang['promoDescription'],
								'promoDetails' => $multi_lang['promoDetails'],
								'promoThumbnail' => $multi_lang['promoThumbnail'],
								'promoCode' => $promo['promo_code'],
								'isNew' => (bool)$promo['tag_as_new_flag'],
								'startAt' => $this->playerapi_lib->formatDateTime($promo['applicationPeriodStart']),
								'expiredAt' => $this->playerapi_lib->formatDateTime($promo['hide_date']),
								'displayType' => $this->playerapi_lib->matchOutputPromoDisplayType($promo['hide_on_player']),
								'promoOrder' => $promo['promoOrder'],
							];
							return $this->playerapi_lib->convertOutputFormat($map_data);
						}, is_array($promo_list_with_pagination['list']) ? $promo_list_with_pagination['list'] : []);
						$promo_list_with_pagination['list'] = array_values($wrapped_promo_data);
						return $promo_list_with_pagination;
					});

					$result = $this->playerapi_lib->adjustloopCurrencyDataStructure($result, $limit, $page);
					$ttl = 10 * 60;
					$this->utils->saveJsonToCache($public_campaigns_key, $result, $ttl);
				}
			}else{
				$result['data'] = $this->playerapi_lib->switchCurrencyForAction($currency, function() use ($currency, $category_id, $pagination){
					$promo_list_with_pagination = $this->promorules->getAllPromoPagination(null, $category_id, $pagination);
					$wrapped_promo_data = array_map(function($promo) use ($currency) {
						$promo['promoDetails'] =  $this->cms_model->decodePromoDetailItem($promo['promoDetails']);
						$current_lang = $this->playerapi_lib->getIsoLang($this->indexLanguage);
						$multi_lang = $this->promoItemMultiLangFields($promo, $current_lang);
						$map_data = [
							'currency' => strtoupper($currency),
							'promoId' => $promo['promoCmsSettingId'],
							'promoCategory' => $promo['promo_category'],
							'promoName' => $multi_lang['promoName'],
							'promoDescription' => $multi_lang['promoDescription'],
							'promoDetails' => $multi_lang['promoDetails'],
							'promoThumbnail' => $multi_lang['promoThumbnail'],
							'promoCode' => $promo['promo_code'],
							'isNew' => (bool)$promo['tag_as_new_flag'],
							'startAt' => $this->playerapi_lib->formatDateTime($promo['applicationPeriodStart']),
							'expiredAt' => $this->playerapi_lib->formatDateTime($promo['hide_date']),
                            'displayType' => $this->playerapi_lib->matchOutputPromoDisplayType($promo['hide_on_player']),
							'promoOrder' => $promo['promoOrder'],
						];
						return $this->playerapi_lib->convertOutputFormat($map_data);
					}, is_array($promo_list_with_pagination['list']) ? $promo_list_with_pagination['list'] : []);
					$promo_list_with_pagination['list'] = array_values($wrapped_promo_data);
					return $promo_list_with_pagination;
				});
			}
			return $this->returnSuccessWithResult($result);
		} catch (\APIException $ex) {
			$result['code'] = $ex->getCode();
			$result['errorMessage']= $ex->getMessage();
            $this->comapi_log(__METHOD__, 'APIException', $result);

            return $this->returnErrorWithResult($result);
		}
	}
	protected function getPublicPromotionsById($promo_cms_setting_id)
	{
		$output = [
			'code' => Playerapi::CODE_OK,
			'data' => ''
		];

		try {
			$request_body = $this->playerapi_lib->getRequestPramas();
			$currency = !empty($request_body['currency']) ? $request_body['currency'] : null;
			$output['data'] = $this->playerapi_lib->switchCurrencyForAction($currency, function() use ($currency, $promo_cms_setting_id){
				$promo_list = $this->promorules->getAllPromo(null, null, $promo_cms_setting_id);
				$this->load->model(['cms_model']);
				$wrapped_promo_data = array_map(function($promo) use ($currency) {
					$promo['promoDetails'] =  $this->cms_model->decodePromoDetailItem($promo['promoDetails']);
					$current_lang = $this->playerapi_lib->getIsoLang($this->indexLanguage);
					$multi_lang = $this->promoItemMultiLangFields($promo, $current_lang);
					return [
						'currency' => strtoupper($currency ?: $this->currency),
						'promoId' => (int)$promo['promoCmsSettingId'],
						'promoCategory' => (int)$promo['promo_category'],
						'promoName' => $multi_lang['promoName'],
						'promoDescription' => $multi_lang['promoDescription'],
						'promoDetails' => $multi_lang['promoDetails'],
						'promoThumbnail' => $multi_lang['promoThumbnail'],
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

			return $this->returnSuccessWithResult($output);
		} catch (\APIException $ex) {
			$result['code'] = $ex->getCode();
			$result['errorMessage']= $ex->getMessage();
            $this->comapi_log(__METHOD__, 'APIException', $result);

            return $this->returnErrorWithResult($result);
		}
	}

	protected function getPromotionReuquetsByPlayerId($player_id) {
		$validate_fields = [
			['name' => 'bonusDateStart', 'type' => 'date-time', 'required' => false, 'length' => 0],
			['name' => 'bonusDateEnd', 'type' => 'date-time', 'required' => false, 'length' => 0],
			['name' => 'limit', 'type' => 'int', 'required' => false, 'length' => 0],
			['name' => 'page', 'type' => 'int', 'required' => false, 'length' => 0],
			['name' => 'ruleId', 'type' => 'int', 'required' => false, 'length' => 0],
			['name' => 'sort', 'type' => 'string', 'required' => false, 'length' => 0],
			['name' => 'status', 'type' => 'int', 'required' => false, 'length' => 0],
			['name' => 'promoTypes', 'type' => 'array[int]', 'required' => false, 'length' => 0],
		];

		$result=['code'=>Playerapi::CODE_OK];
		$request_body = $this->playerapi_lib->getRequestPramas();
		$this->comapi_log(__METHOD__, '=======request_body', $request_body);
		$is_validate_basic_passed = $this->playerapi_lib->validParmasBasic($request_body, $validate_fields);
		$this->comapi_log(__METHOD__, '=======is_validate_basic_passed', $is_validate_basic_passed);

		if(!$is_validate_basic_passed['validate_flag']) {
			$result['code'] = Playerapi::CODE_INVALID_PARAMETER;
			$result['error_description']= $is_validate_basic_passed['validate_msg'];
			return $this->returnErrorWithResult($result);
		}
		$time_start = !empty($request_body['bonusDateStart']) ? $request_body['bonusDateStart'] : null;
		$time_end = !empty($request_body['bonusDateEnd']) ? $request_body['bonusDateEnd'] : null;
		$limit = !empty($request_body['limit']) ? $request_body['limit'] : 20;
		$rule_id = isset($request_body['ruleId']) ? $request_body['ruleId'] : null;
		$sort = !empty($request_body['sort']) ? $request_body['sort'] : 'DESC';
		$status = isset($request_body['status']) ? $request_body['status'] : null;
		$status = $this->playerapi_lib->matchInputCampaignStatus($status);
		$page = !empty($request_body['page']) ? $request_body['page'] : 1;
		$promoTypes = isset($request_body['promoTypes']) ? $request_body['promoTypes'] : null;
		$promoTypes = (!is_null($promoTypes)) ? $this->playerapi_lib->matchInputPromoType($promoTypes) : null;

		$promotion_requests = $this->playerapi_model->getPromotionReuquetsByPlayerId($player_id, $time_start, $time_end, null, $rule_id, $sort, $status, $promoTypes, $limit, $page);
		// $promotion_requests['list'] = $this->playerapi_lib->matchOutputCampaignStatus($promotion_requests['list']);
		$promotion_requests['list'] = $this->playerapi_lib->customizeApiOutput($promotion_requests['list'], ['bonusDate']);
		$promotion_requests['list'] = $this->playerapi_lib->convertOutputFormat($promotion_requests['list']);
		$result['data'] = $promotion_requests;
		// $result['code'] = empty($page_promotion_requests) ? Playerapi::CODE_CAMPAIGN_NOT_FOUND : $result['code'];
		return $this->returnSuccessWithResult($result);
	}

	protected function getCashbackReuquetsByPlayerId($player_id) {
		$validate_fields = [
			['name' => 'limit', 'type' => 'int', 'required' => false, 'length' => 0],
			['name' => 'page', 'type' => 'int', 'required' => false, 'length' => 0],
			['name' => 'bonusDateStart', 'type' => 'date-time', 'required' => false, 'length' => 0],
			['name' => 'bonusDateEnd', 'type' => 'date-time', 'required' => false, 'length' => 0],
			// ['name' => 'ruleId', 'type' => 'int', 'required' => false, 'length' => 0],
			['name' => 'sort', 'type' => 'string', 'required' => false, 'length' => 0],
			['name' => 'status', 'type' => 'int', 'required' => false, 'length' => 0],
		];

		$result=['code'=>Playerapi::CODE_OK];
		$request_body = $this->playerapi_lib->getRequestPramas();
		$this->comapi_log(__METHOD__, '=======request_body', $request_body);
		$is_validate_basic_passed = $this->playerapi_lib->validParmasBasic($request_body, $validate_fields);
		$this->comapi_log(__METHOD__, '=======is_validate_basic_passed', $is_validate_basic_passed);

		if(!$is_validate_basic_passed['validate_flag']) {
			$result['code'] = Playerapi::CODE_INVALID_PARAMETER;
			$result['errorMessage']= $is_validate_basic_passed['validate_msg'];
			return $this->returnErrorWithResult($result);
		}

		$time_start = !empty($request_body['bonusDateStart']) ? $request_body['bonusDateStart'] : null;
		$time_end = !empty($request_body['bonusDateEnd']) ? $request_body['bonusDateEnd'] : null;
		$limit = !empty($request_body['limit']) ? $request_body['limit'] : 20;
		// $rule_id = isset($request_body['ruleId']) ? $request_body['ruleId'] : null;
		$sort = !empty($request_body['sort']) ? $request_body['sort'] : 'DESC';
		$status = isset($request_body['status']) ? $request_body['status'] : null;
		// $status = $this->playerapi_lib->matchInputDepositStatus($status);
		$page = !empty($request_body['page']) ? $request_body['page'] : 1;

		$cashbcak_requests = $this->playerapi_model->getCashbackReuquetsByPlayerId($player_id, $time_start, $time_end, $sort, $status, $limit, $page);
		// $page_cashbcak_requests = $this->playerapi_lib->buildPageOutput($cashbcak_requests, $page, $limit);
		$cashbcak_requests['list'] = $this->playerapi_lib->customizeApiOutput($cashbcak_requests['list'], ['status']);
		$cashbcak_requests['list'] = $this->playerapi_lib->convertOutputFormat($cashbcak_requests['list']);
		$result['data'] = $cashbcak_requests;
		// $result['code'] = empty($page_cashbcak_requests['list']) ? Playerapi::CODE_CAMPAIGN_NOT_FOUND : $result['code'];
		return $this->returnSuccessWithResult($result);
	}

	protected function getPromoCmsSettingByPlayerId($player_id, $promo_type = null) {
		$result = [
			'code' => Playerapi::CODE_OK,
			'data' => []
		];

		try {
			$request_body = $this->playerapi_lib->getRequestPramas();
			if (!empty($promo_type)) {
				$validate_fields = [
					['name' => 'type', 'type' => 'string', 'required' => false, 'length' => 0, 'allowed_content' => ['deposit', 's-deposit', 'task', 'rescue', 'login', 'next-day', 'spending', 'bet', 'continuous-online', 'continuous-win', 'register', 'birthday', 'weekly']],
					['name' => 'categoryId', 'type' => 'int', 'required' => false, 'length' => 0],
					['name' => 'limit', 'type' => 'int', 'required' => false, 'length' => 0],
					['name' => 'page', 'type' => 'int', 'required' => false, 'length' => 0],
                    ['name' => 'amount', 'type' => 'double', 'required' => false, 'length' => 0], /// for deposit amount in type=deposit.
				];
			}else{
				$validate_fields = [
					['name' => 'categoryId', 'type' => 'int', 'required' => false, 'length' => 0],
					['name' => 'limit', 'type' => 'int', 'required' => false, 'length' => 0],
					['name' => 'page', 'type' => 'int', 'required' => false, 'length' => 0],
				];
			}

			$is_validate_basic_passed = $this->playerapi_lib->validParmasBasic($request_body, $validate_fields);

			if (!$is_validate_basic_passed['validate_flag']) {
				throw new \APIException($is_validate_basic_passed['validate_msg'], Playerapi::CODE_INVALID_PARAMETER);
			}

			$currency = !empty($request_body['currency']) ? $request_body['currency'] : null;
			$category_id = (isset($request_body['categoryId'])) ? $request_body['categoryId'] : null;
			$limit = (isset($request_body['limit'])) ? $request_body['limit'] : 20;
			$page = (isset($request_body['page'])) ? $request_body['page'] : 1;
            $amount = isset($request_body['amount'])? $request_body['amount']: null; // deposit amount for deposit promo
			$pagination = array('limit' => $limit, 'page' => $page, 'force_show_deposit_promo' => true, 'promo_type' => $promo_type);
			if(empty($category_id)){
				$pagination['only_visible_category'] = $this->utils->safeGetArray($request_body, 'onlyVisibleCategory', true);
			}

			if (is_null($currency)){
				//loop currency
				$curr_currency = $this->currency;
				unset($pagination['page']);
				unset($pagination['limit']);

				$campaigns_key = "campaigns";
				$params = [$category_id, $limit, $page, $promo_type, $amount];

				foreach ($params as $param) {
					if (!empty($param)) {
						$campaigns_key .= "-" . $param;
					}
				}
				$this->comapi_log(__METHOD__, '======campaigns_key', $campaigns_key);
				$campaigns_result = $this->utils->getJsonFromCache($campaigns_key);
				if (!empty($campaigns_result) && empty($promo_type)) {
					$this->comapi_log(__METHOD__, ['cached_result' => $campaigns_result]);
					$result = $campaigns_result;
				} else {
					$promo_list_with_pagination = $this->playerapi_lib->loopCurrencyForAction($curr_currency, function($currency) use ($player_id, $promo_type, $category_id, $pagination, $amount){
						return $this->getPlayerPromo( $player_id // #1
													, $promo_type // #2
													, null // #3
													, $category_id // #4
													, $currency // #5
													, $pagination // #6
													, $amount
												);
					});
					$this->comapi_log(__METHOD__, '=======promo_list_with_pagination', $promo_list_with_pagination);
					$result['data'] = $this->playerapi_lib->convertOutputFormat($promo_list_with_pagination);
					$result = $this->playerapi_lib->adjustloopCurrencyDataStructure($result, $limit, $page);
					$ttl = 10 * 60;
					$this->utils->saveJsonToCache($campaigns_key, $result, $ttl);
				}
			}else{
				$promo_list_with_pagination = $this->playerapi_lib->switchCurrencyForAction($currency, function() use($player_id, $promo_type, $category_id, $currency, $pagination, $amount){
					return $this->getPlayerPromo( $player_id // #1
												, $promo_type // #2
												, null // #3
												, $category_id // #4
												, $currency // #5
												, $pagination // #6
												, $amount
											);
				});
				$this->comapi_log(__METHOD__, '=======promo_list_with_pagination', $promo_list_with_pagination);
				$result['data'] = $this->playerapi_lib->convertOutputFormat($promo_list_with_pagination);
			}
			return $this->returnSuccessWithResult($result);
		} catch (\APIException $ex) {
			$result['code'] = $ex->getCode();
			$result['errorMessage']= $ex->getMessage();
            $this->comapi_log(__METHOD__, 'APIException', $result);

            return $this->returnErrorWithResult($result);
		}
	}

	protected function getPromoCmsSettingById($player_id, $promo_cms_setting_id) {
		$result=['code'=>Playerapi::CODE_OK];
		$request_body = $this->playerapi_lib->getRequestPramas();
		$this->comapi_log(__METHOD__, '=======request_body', $request_body);
		$currency = !empty($request_body['currency']) ? $request_body['currency'] : null;

		$promo_cms_list = $this->playerapi_lib->switchCurrencyForAction($currency, function() use($player_id, $promo_cms_setting_id, $currency){
			return $this->getPlayerPromo($player_id, null, $promo_cms_setting_id, null, $currency);
		});

		$promo_entry = (!empty($promo_cms_list)) ? $promo_cms_list[0] : new ArrayObject();
		$code = (!empty($promo_entry)) ? $result['code'] : Playerapi::CODE_CAMPAIGN_NOT_FOUND;

		$promo_entry = $this->playerapi_lib->convertOutputFormat($promo_entry);
		$result['code'] = $code;
		$result['data'] = $promo_entry;
		return $this->returnSuccessWithResult($result);
	}

	protected function checkOnlyPromotion($player_id, $promo_cms_setting_id)
	{
		$result = ['code' => Playerapi::CODE_OK];

		$promorule = $this->promorules->getPromoruleByPromoCms($promo_cms_setting_id);

		list($success, $message) = $this->promorules->checkOnlyPromotion($player_id, $promorule, $promo_cms_setting_id);

		$result['code'] = ($success) ? $result['code'] : Playerapi::CODE_CAMPAIGN_CONDITIONS_NOT_MET;
		$result['data']['success'] = (bool)$success;
		if($result['data']['success']) {
			$result['data']['message'] = null;
		} else {
			$result['data']['message'] = (empty($message)) ? null : lang($message);
		}
		return $this->returnSuccessWithResult($result);
	}

	protected function applyPromotion($player_id, $promo_cms_setting_id, $extra_info = array())
	{
		$result = ['code' => Playerapi::CODE_OK];

		$controller = $this;

		$message = null;
		$success = $this->lockAndTransForPlayerPromo($player_id, function ()
		use ($controller, $player_id, $promo_cms_setting_id, &$message, $extra_info) {
			$promorule = $controller->promorules->getPromoruleByPromoCms($promo_cms_setting_id);

			if(array_key_exists('redeemCode', $extra_info)){
				$extra_info['redemption_code'] = $this->utils->safeGetArray($extra_info, 'redeemCode', '');
			}
			$extra_info['order_generated_by'] = $this->utils->safeGetArray($extra_info, 'order_generated_by', Player_promo::ORDER_GENERATED_BY_PLAYER_CENTER_API_N);
			// list($success, $message) = $controller->promorules->checkAndProcessPromotion(
			// 	$player_id,
			// 	$promorule,
			// 	$promo_cms_setting_id,
			// 	false,
			// 	null,
			// 	$extra_info,
			// 	null,
			// 	false
			// );
			$ret = $controller->request_promo(
									$promo_cms_setting_id // #1
									, 0 // #2
									, null // #3
									, false  // #4
									, 'ret_to_api'  // #5
									, $player_id // #6
									, $extra_info// #6.1
			);

			$success = $this->utils->safeGetArray($ret, 'success', false);
			$message = $this->utils->safeGetArray($ret, 'message', null);
			$this->utils->debug_log('apply promo', $extra_info, $ret, $success, $message);
			return $success;
		});

		$result['code'] = ($success) ? $result['code'] : Playerapi::CODE_APPLY_PROMO_FAILED;
		if($success){
			$result['successMessage'] = (empty($message)) ? null : lang($message);
		} else {
			$result['errorMessage'] = (empty($message)) ? null : lang($message);
		}
		// $result['data']['success'] = (bool)$success;
		// if($result['data']['success']) {
		// 	$result['data']['message'] = null;
		// } else {
		// 	$result['data']['message'] = (empty($message)) ? null : lang($message);
		// }
		return $this->returnSuccessWithResult($result);
	}

	protected function applyRedemptionCode($player_id){
		// return $this->returnSuccessWithResult(['code' => Playerapi::CODE_CAMPAIGN_NOT_FOUND, 'errormessage' => lang("NOT_ENABLED")]);

		$result = ['code' => Playerapi::CODE_OK];
		$extra_info = [];
		try {
			if(!$this->isRedemptionCodeEnabled()){
				throw new \Exception( lang("NOT_ENABLED"), Playerapi::CODE_CAMPAIGN_NOT_FOUND);
			}

			$promo_cms_setting_id = $this->getRedemptioncodePromocmsid($this->currency);
			if(empty($promo_cms_setting_id)){
				throw new \Exception( lang("NOT_ENABLED"), Playerapi::CODE_CAMPAIGN_NOT_FOUND);
			}

			$request_body = $this->playerapi_lib->getRequestPramas();

			$this->utils->debug_log('start applyRedemptionCode_'.$player_id, $request_body);
			if(!array_key_exists('redeemCode', $request_body)){
				throw new \Exception( null, Playerapi::CODE_CAMPAIGN_NOT_FOUND);
			}

			$redeemCode = $this->utils->safeGetArray($request_body, 'redeemCode', '');
			$extra_info['redeemCode'] = $redeemCode;
			if(empty($extra_info['redeemCode'])){
				throw new \Exception( null, Playerapi::CODE_CAMPAIGN_NOT_FOUND);
			}
			$extra_info['order_generated_by'] = Player_promo::ORDER_GENERATED_BY_REDEMPTIONCODE;
			if(array_key_exists('redeemCode', $extra_info)){
				$extra_info['redemption_code'] = $this->utils->safeGetArray($extra_info, 'redeemCode', '');
			}

			// return $this->applyPromotion($player_id, $promo_cms_setting_id, $additional_info);
		} catch (\Throwable $th) {
			$_error_message = $th->getMessage();
			$_error_code = $th->getCode();
			$result['code'] = $_error_code;
			$result['errormessage'] = $_error_message;
			$this->returnErrorWithResult($result);
		}

		$controller = $this;
		$message = null;
		$success = false;
		$this->lockAndTransForPlayerPromo($player_id, function ()
		use ($controller, $player_id, $promo_cms_setting_id, $extra_info, &$success, &$message) {
			$promorule = $controller->promorules->getPromoruleByPromoCms($promo_cms_setting_id);

			$ret = $controller->request_redemption(
									$promo_cms_setting_id // #1
									, 0 // #2
									, null // #3
									, false  // #4
									, 'ret_to_api'  // #5
									, $player_id // #6
									, $extra_info// #6.1
			);

			$success = $this->utils->safeGetArray($ret, 'success', false);
			$message = $this->utils->safeGetArray($ret, 'message', null);
			$this->utils->debug_log('apply promo', $extra_info, $ret, $success, $message);
			return true;
		});

		$result['code'] = ($success) ? $result['code'] : Playerapi::CODE_APPLY_PROMO_FAILED;
		if($success){
			$result['successMessage'] = (empty($message)) ? null : lang($message);
		} else {
			$result['errorMessage'] = (empty($message)) ? null : lang($message);
		}
		return $this->returnSuccessWithResult($result);
	}
}
