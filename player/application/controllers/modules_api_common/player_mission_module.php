<?php

/**
 * uri: /promotion
 *
 * @property playerapi_lib $playerapi_lib
 * @property Playerapi_model $playerapi_model
 * @property Promorules $promorules
 * 
 */
trait player_mission_module{
	public function missions($action=null, $additional=null, $append=null)
	{
		if (!$this->initApi()) {
			return;
		}
		$this->load->library(['playerapi_lib']);
		$this->load->model(['playerapi_model']);
		$request_method = $this->input->server('REQUEST_METHOD');
		$additional = $additional ? trim($additional, "\t\n\r\0\x0B\x2C") : null;

		switch($action) {
			case 'apply':
				return $this->applyMission($this->player_id);
				break;
				
			case 'list':
				if ($request_method == 'GET') {
					
					return $this->getMissionsInfoByPlayer($this->player_id);
				}
				break;
			case 'interact':
				if ($request_method == 'POST') {
					return $this->interactMission($this->player_id);
				}
				break;
		}

		return $this->returnErrorWithCode(Playerapi::CODE_GENERAL_CLIENT_ERROR);
	}

	protected function getMissionsInfoByPlayer($player_id)
	{

		$request_body = $this->playerapi_lib->getRequestPramas();
		$curr_currency = !empty($request_body['currency']) ? $request_body['currency'] : null;
		$fliterSubtype = $this->utils->safeGetArray($request_body, 'subtype');
		$result = ['code' => Playerapi::CODE_OK];
		$result['data']['list'] = [];
		if($this->useMockData()){

			$result['data'] = $this->_mockDataForPlayerapi();
		}
		else if(!empty($request_body['missionCode'])){

			$mission_list_all = $this->playerapi_lib->loopCurrencyForAction($curr_currency, function($currency) use ($player_id, $request_body, $curr_currency, $fliterSubtype, &$mission_list) {

				$this->utils->debug_log(__METHOD__,'current_currency : ', $curr_currency, 'loop_currency : ', $currency);
				$currency = strtoupper($currency);
				$_current_mission_category_id = $this->getMissionSettingItemBytype($currency, $request_body['missionCode'], 'category');

				$missions_cache_key = "missioncate-". $currency."-". $_current_mission_category_id;
				$missions_cached_result = $this->utils->getJsonFromCache($missions_cache_key);
				if (!empty($missions_cached_result)) {
					$this->comapi_log(__METHOD__, ['cached_result' => $missions_cached_result]);
					$missions = $missions_cached_result;
				} else {
					$missions = $this->promorules->getPromorulesByPromoTypeId($_current_mission_category_id);
					$ttl = 20 * 60;
					$this->utils->saveJsonToCache($missions_cache_key, $missions, $ttl);
				}
				
				// $missions = $this->promorules->getAllPromoPagination(null, $_current_mission_category_id, 1);
				$mission_list = [];
				foreach ($missions as $key => $promorule) {
					$mission_item_cache_key = "playermission-". $player_id . $currency."-". $promorule['promorulesId'];
					$cached_result = $this->utils->getJsonFromCache($mission_item_cache_key);
					if (!empty($cached_result)) {
						$this->comapi_log(__METHOD__, ['cached_result' => $cached_result]);
						$mission_list[] = $cached_result;
					} else {
						$_promocms = $this->promorules->getPromoCmsByPromoruleId($promorule['promorulesId']);
						if(!empty($_promocms[0])) 
						{
							$promo_item = $_promocms[0];
							if($promo_item['hide_on_player'] > 0){
								//ignore
								$outputItem = $this->_mappingInfoOutputDefault($promorule, $promo_item, $request_body, $currency);
								// $outputItem = $this->_mappingInfoOutputResult($promorule, $promo_item, $outputItem);
								// if(trim($fliterSubtype) != '' && $fliterSubtype != $outputItem['subtype']){
								// 	continue;
								// }
								if(!empty($outputItem)){
									$mission_list[] = $outputItem;
									$ttl = 1 * 60;
									$this->utils->saveJsonToCache($mission_item_cache_key, $outputItem, $ttl);
								}
								
							}
						}
					}
				}
				
				return $mission_list;
			});

			$_output = [];
			if(!empty($mission_list_all)){
				foreach ($mission_list_all as $mission_list_curreency) {
					if(!empty($mission_list_curreency)){
						array_push($_output, ...$mission_list_curreency);
					}
				}
				$_output = $this->playerapi_lib->convertOutputFormat($_output, ['promoId', 'promoCode', 'prize', 'subtype']);
			}
			$result['data']['list'] = $_output;
		}

		return $this->returnSuccessWithResult($result);
	}

	/**
	 * mappingInfoOutput function
	 *
	 * @param array $promorule
	 * @param array $promocms
	 * @param array $request_body
	 * @return array $outputItem
	 */
	protected function _mappingInfoOutputDefault($promorule, $promocms, $request_body, $currency)
	{
		$outputItem = [];
		$current_lang = $this->playerapi_lib->getIsoLang($this->indexLanguage);
		$multi_lang = $this->promoItemMultiLangFields($promocms, $current_lang);
		// $currency = $this->utils->safeGetArray($request_body, 'currency');
		$fliterSubtype = $this->utils->safeGetArray($request_body, 'subtype');
		$description = null;
		$outputItem = [
			"currency"=> "",
			"subtype" => "",
			"promoId"=> "",
			"promoCode"=> "",
			"currentTotal"=> 0,
			"threshHold"=> 1,
			"status"=> Promorules::MISSION_STATUS_CONDICTION_NOT_MET,
			"prize" => "",
		];


		$outputItem['currency'] = strtoupper($currency ?: $this->currency);
		$outputItem['promoId'] = $this->utils->safeGetArray($promocms, 'promoCmsSettingId');
		$outputItem['promoCode'] = $this->utils->safeGetArray($promocms, 'promo_code');


		$outputItem['promoName'] = $multi_lang['promoName'];
		$outputItem['promoDescription'] = $multi_lang['promoDescription'];
		$outputItem['promoDetails'] = $multi_lang['promoDetails'];
		$outputItem['promoThumbnail'] = $multi_lang['promoThumbnail'];

		$formula = $this->utils->json_decode_handleErr($promorule['formula'], true);
		if( !empty($formula['bonus_release']) ){
			$description = $this->CI->utils->json_decode_handleErr($formula['bonus_release'], true);
			// $outputItem['description'] = $description;
			//setup default value from formula
			$this->_outputReferBy($description, $outputItem);
		}
		if(trim($fliterSubtype) != '' && $fliterSubtype != $outputItem['subtype']){

			return [];
		}
		return $this->_mappingInfoOutputResult($promorule, $promocms, $outputItem);
	}
	
	protected function _mappingInfoOutputResult($promorule, $promocms, $outputItem){
		$promo_cms_setting_id = $promocms['promoCmsSettingId'];
		$checkResult = $this->checkMissionRules($this->player_id, $promorule, $promo_cms_setting_id);
		$_missionResult = $this->utils->safeGetArray($checkResult, 'mission');
		if($this->checkMissionApplicable($_missionResult)){
			$this->_outputReferBy($_missionResult, $outputItem);
			return $outputItem;
		}
		return null;
	}

	protected function _outputReferBy($referValue, &$outData){
		if($referValue){
			$outData['subtype'] = $this->utils->safeGetArray($referValue, 'subtype', $outData['subtype']);
			$outData['currentTotal'] = $this->utils->safeGetArray($referValue, 'current_total', $outData['currentTotal']);
			$outData['threshHold'] = $this->utils->safeGetArray($referValue, 'thresh_hold', $outData['threshHold']);
			$outData['prize'] = strval($this->utils->safeGetArray($referValue, 'bonus_amount', $outData['prize']));
			$outData['status'] = $this->utils->safeGetArray($referValue, 'status', $outData['status']);
			$outData['currentTotal'] = floatval($outData['currentTotal']) > floatval($outData['threshHold'] ) ? $outData['threshHold'] : $outData['currentTotal'];
		}
	}

	protected function checkMissionApplicable($referValue){
		$is_expired = $this->utils->safeGetArray($referValue, 'is_expired', false);
		return !$is_expired;
	}

	protected function checkMissionRules($player_id, $promorule, $promo_cms_setting_id, $extra_info=[]) {

		$extra_info['order_generated_by'] = Player_promo::ORDER_GENERATED_BY_PLAYER_MISSION;
		$result = $this->promorules->checkOnlyPromotion($player_id, $promorule, $promo_cms_setting_id, false, null, $extra_info, true);
		$result['extra_info'] = $extra_info;
		$mission_desc = $this->utils->safeGetArray($extra_info, 'mission_desc');
		return $mission_desc;
	}

	public function applyMission($player_id)
	{
		if($this->useMockData()){
			return $this->_mockDataForMissionApply();
		}
		$request_body = $this->playerapi_lib->getRequestPramas();
		$currency = $this->utils->safeGetArray($request_body, "currency", null);
		if(empty($currency)) {

			$this->returnErrorWithCode(Playerapi::CODE_PROMOTION_CMS_ID_INVALID, lang('currency_invalid'));
			return;
		}
		return $this->playerapi_lib->switchCurrencyForAction($currency, function() use ($player_id, $request_body, $currency) {
			if(empty($request_body['promoId'])) {
				$this->returnErrorWithCode(Playerapi::CODE_PROMOTION_CMS_ID_INVALID);
				return;
			}
			if(!$this->isMissionPromo($currency, $request_body['promoId'])){
				$this->returnErrorWithCode(Playerapi::CODE_PROMOTION_CMS_ID_INVALID);
				return;
			}
			return $this->applyPromotion($player_id, $request_body['promoId'], ['order_generated_by' => Player_promo::ORDER_GENERATED_BY_PLAYER_MISSION]);
		});
	}

	public function isMissionPromo($currency, $promoId){
		$categoryId = $this->promorules->getPromoTypeIdByPromocmsId($promoId);
		if(!$categoryId){
			return false;
		}

		$mission_setting = $this->config->item('mission_promo_setting');
		$list = [];
		foreach ($mission_setting as $key => $value) {
			if(array_key_exists($currency, $value)){
				$list[] = $this->utils->safeGetArray($value[$currency], "category");
			}
		}

		$promoruleid = $this->promorules->getPromorulesIdByPromoCmsId($promoId);
		$mission_item_cache_key = "playermission-". $this->player_id . $currency."-". $promoruleid;
		$this->utils->deleteCache($mission_item_cache_key);

		// return false;
		return in_array($categoryId, $list);
	}

	public function getMissionSettingBytype($currency, $type){
		// $config['mission_promo_setting'] = [
		// 	"newbie" =>[
		// 		"CNY" =>[
		// 			"category" => 8,
		// 		],
		// 	],
		// ];
		$mission_setting = $this->config->item('mission_promo_setting');
		if($type){
			if(!empty($mission_setting[$type])){
				return $this->utils->safeGetArray($mission_setting[$type], $currency);
			}
		}
	}

	public function getMissionSettingItemBytype($currency, $type, $key){
		// $config['mission_promo_setting'] = [
		// 	"newbie" =>[
		// 		"CNY" =>[
		// 			"category" => 8,
		// 		],
		// 	],
		// ];
		// $mission_setting = [];
		if($type){
			$mission_setting = $this->getMissionSettingBytype($currency, $type);
			if($key){
				return $this->utils->safeGetArray($mission_setting, $key);
			}
		}
		return [];
	}

	protected function interactMission($player_id) {
		$request_body = $this->playerapi_lib->getRequestPramas();
		$currency = $this->utils->safeGetArray($request_body, "currency", null);
		if(empty($currency)) {

			$this->returnErrorWithCode(Playerapi::CODE_PROMOTION_CMS_ID_INVALID, lang('currency_invalid'));
			return;
		}
		return $this->playerapi_lib->switchCurrencyForAction($currency, function() use ($player_id, $request_body, $currency) {
			if(empty($request_body['promoId'])) {
				$this->returnErrorWithCode(Playerapi::CODE_PROMOTION_CMS_ID_INVALID);
				return;
			}
			if(!$this->isMissionPromo($currency, $request_body['promoId'])){
				$this->returnErrorWithCode(Playerapi::CODE_PROMOTION_CMS_ID_INVALID);
				return;
			}
			return $this->interactPromotion($player_id, $currency, $request_body['promoId']);
		});
	}

	protected function interactPromotion($player_id, $currency, $promo_cms_setting_id) {

		$result['code'] = Playerapi::CODE_OK;
		$result['errorMessage'] = null;
		$result['data'] = [];
		
		$promorule = $this->promorules->getPromoruleByPromoCms($promo_cms_setting_id);
		$formula = $this->utils->json_decode_handleErr($promorule['formula'], true);
		try {

			if( empty($formula['bonus_release']) ){
				throw new Exception(lang("ERROR_SETUP"), Playerapi::CODE_PROMOTION_CMS_ID_INVALID);
			}

			$description = $this->CI->utils->json_decode_handleErr($formula['bonus_release'], true);
			$interactType = $this->utils->safeGetArray($description, 'interact_type', null);
			$this->utils->debug_log(__METHOD__, ['$interactType' => $interactType]);
			// if(empty($interactType)){
			// 	throw new Exception("", Playerapi::CODE_PROMOTION_CMS_ID_INVALID);
			// }
			$extra_info = [
				'is_interact' => true,
				'interactType' => $interactType,
			];
		} catch (Exception $e) {
			$result['code'] = $e->getCode();
			$result['errorMessage'] = $e->getMessage();
			return $this->returnSuccessWithResult($result);
		}
		
		$checkResult = $this->checkMissionRules($this->player_id, $promorule, $promo_cms_setting_id, $extra_info);

		$result['data'] = [
			"currency"=> $currency,
			"subtype" => "",
			"promoId"=> "",
			"promoCode"=> "",
			"currentTotal"=> 0,
			"threshHold"=> 1,
			"status"=> Promorules::MISSION_STATUS_CONDICTION_NOT_MET,
			"prize" => "",
		];
		$result['data']['promoId'] = $promo_cms_setting_id;
		$this->_outputReferBy($checkResult['mission'], $result['data']);
		unset($result['data']['prize']);
		unset($result['data']['promoCode']);
		unset($result['data']['threshHold']);
		return $this->returnSuccessWithResult($result);

	}

	protected function useMockData(){
		$mission_setting = $this->config->item('mission_promo_setting');
		if(!empty($mission_setting['use_mock_data'])){
			return boolval($mission_setting['use_mock_data'] == true);
		}
		return false;
	}

	protected function _mockDataForMissionApply(){

		$request_method = $this->input->server('REQUEST_METHOD');
		$request_body = $this->playerapi_lib->getRequestPramas();
		// $promoId = !empty($request_body['promoId']) ? $request_body['promoId'] : null;
		$result = ['code' => Playerapi::CODE_OK];
		$result['errorMessage'] = null;
		$message=null;
		$promoId = rand(1, 5);
		switch($promoId){
			case "1":
				$result['code'] = Playerapi::CODE_CAMPAIGN_CONDITIONS_NOT_MET;
				$message = 'mission_not_qualified';
				break;

			case "2":
				// $result['code'] = Playerapi::CODE_CAMPAIGN_CONDITIONS_NOT_MET;
				break;

			case "3":
				$result['code'] = Playerapi::CODE_CAMPAIGN_CONDITIONS_NOT_MET;
				$message = 'mission finished';
				break;
			default:
				// $result['data']['success'] = (bool)true;
				$result['code'] = Playerapi::CODE_CAMPAIGN_CONDITIONS_NOT_MET;
				break;
		}

		$result['errorMessage'] = (empty($message)) ? null : lang($message);
		return $this->returnSuccessWithResult($result);
	}
}
