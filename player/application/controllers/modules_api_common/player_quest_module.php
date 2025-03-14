<?php
/**
 * uri: /quest
 *
 * @property playerapi_lib $playerapi_lib
 * @property Playerapi_model $playerapi_model
 * @property quest_library $quest_library
 * 
 */
trait player_quest_module{
	public function quest($action=null, $additional=null, $append=null)
	{
		if (!$this->initApi()) {
			return;
		}
		$this->load->library(['playerapi_lib', 'quest_library']);
		$this->load->model(['playerapi_model', 'sale_order', 'player_friend_referral', 'quest_manager']);
		$request_method = $this->input->server('REQUEST_METHOD');
		$additional = $additional ? trim($additional, "\t\n\r\0\x0B\x2C") : null;

		switch($action) {
			case 'claim':
				if ($request_method == 'POST') {
					return $this->claimQuest($this->player_id);
				}
				break;
			case 'category':
				if ($request_method == 'GET') {
					return $this->getQuestCategory();
				}
				break;
			case 'list':
				if ($request_method == 'GET') {
					return $this->getQuestManager($additional);
				}
				break;
			case 'progress':
				if ($request_method == 'GET'){
					return $this->getQuestProgressByPlayer($this->player_id);
				}
				break;
			case 'quest-request':
				if ($request_method == 'GET') {
					return $this->getQuestRequestByPlayer($this->player_id);
				}
				break;
			case 'interact':
				if ($request_method == 'POST') {
					return $this->interactQuest($this->player_id);
				}
				break;
		}
		return $this->returnErrorWithCode(Playerapi::CODE_GENERAL_CLIENT_ERROR);
	}

	protected function getQuestCategory()
	{
		$result = ['code' => Playerapi::CODE_OK];

		try {
			$request_body = $this->playerapi_lib->getRequestPramas();
			$validate_fields = [
				['name' => 'categoryCode', 'type' => 'currency_id', 'required' => false, 'length' => 0],
			];
			$is_validate_basic_passed = $this->playerapi_lib->validParmasBasic($request_body, $validate_fields);

			if (!$is_validate_basic_passed['validate_flag']) {
				throw new \APIException($is_validate_basic_passed['validate_msg'], Playerapi::CODE_INVALID_PARAMETER);
			}

			$categoryCode = (isset($request_body['categoryCode'])) ? $request_body['categoryCode'] : null;
			$categoryId = null;
			$currency = null;

			if(!is_null($categoryCode)){
				list($currency, $categoryId) = $this->playerapi_lib->parseCurrencyAndIdFromCode($categoryCode);
			}

			if(is_null($currency)){
				$curr_currency = $this->currency;
				$dataResult = $this->playerapi_lib->loopCurrencyForAction($curr_currency, function($currency) use ($categoryId) {
					$currency = strtoupper($currency);
					$getQuestCategoryKey = "getQuestCategory-loop-$currency";
					$getQuestCategoryCacheResult = $this->utils->getJsonFromCache($getQuestCategoryKey);
	
					if (!empty($getQuestCategoryCacheResult)) {
						$this->comapi_log(__METHOD__, ['cached_result' => $getQuestCategoryCacheResult]);
						$questCategory = $getQuestCategoryCacheResult;
					} else {
						$questCategory = $this->playerapi_model->getQuestCategorylist($categoryId);
						$questCategory = $this->_mappingQuestCategoryInfoOutputResult($questCategory, $currency);
	
						$ttl = 4 * 60 * 60;
						$this->utils->saveJsonToCache($getQuestCategoryKey, $questCategory, $ttl);
					}
					return $questCategory;
				});
				$dataResult = $this->filterEmptyArrays($dataResult);
			}else{
				$dataResult = $this->playerapi_lib->switchCurrencyForAction($currency, function() use ($categoryId,$currency) {
					$getQuestCategoryKey = "getQuestCategory-switch-$currency-$categoryId";
					$getQuestCategoryCacheResult = $this->utils->getJsonFromCache($getQuestCategoryKey);
	
					if (!empty($getQuestCategoryCacheResult)) {
						$this->comapi_log(__METHOD__, ['cached_result' => $getQuestCategoryCacheResult]);
						$questCategory = $getQuestCategoryCacheResult;
					} else {
						$questCategory = $this->playerapi_model->getQuestCategorylist($categoryId);
						$questCategory = $this->_mappingQuestCategoryInfoOutputResult($questCategory, $currency);
	
						$ttl = 4 * 60 * 60;
						$this->utils->saveJsonToCache($getQuestCategoryKey, $questCategory, $ttl);
					}
					return $questCategory;
				});
			}

			$result['data']['list'] = $this->playerapi_lib->convertOutputFormat($dataResult);

			return $this->returnSuccessWithResult($result);
		} catch (\APIException $ex) {
			$result['code'] = $ex->getCode();
			$result['errorMessage']= $ex->getMessage();
            $this->comapi_log(__METHOD__, 'APIException', $result);

            return $this->returnErrorWithResult($result);
		}
	}

	protected function filterEmptyArrays($data) {
		$filteredSubArrays = [];
		foreach ($data as $subArray) {
			if (!empty($subArray)) {
				$filteredSubArrays = array_merge($filteredSubArrays, $subArray);
			}
		}
		return $filteredSubArrays;
	}

	protected function _mappingQuestCategoryInfoOutputResult($questData, $currency)
	{
		$this->utils->debug_log(__METHOD__, 'questData', $questData, 'currency', $currency);

		$resultData = [];

		$resultData = array_map(function ($category) use ($currency) {
			$iconUrl = !empty($category['iconPath']) ? $this->utils->getSystemUrl('player') . $this->utils->getQuestCategoryIcon($category['iconPath']) : '';

			list($fromDatetime, $toDatetime) = $this->quest_library->getQuestPeriodTypeDatetime($category);

			return [
				'categoryCode' => $currency . '_' . $category['questCategoryId'],
				'title' => lang($category['title']),
				'description' => $category['description'],
				'countdownEnabled' => (bool)$category['showTimer'],//是否顯示倒數計時,SBE為下架起迄時間
				'overrideMnangerCountdown' => (bool)$category['coverQuestTime'],//是否覆蓋任務時間,預設為true
				'startAt' => is_null($fromDatetime) ? '' : $this->playerapi_lib->formatDateTime($fromDatetime),//依據週期拿開始時間,無的話拿活動開始時間
				'endAt' => is_null($toDatetime) ? '' : $this->playerapi_lib->formatDateTime($toDatetime),//依據週期拿結束時間,無的話拿活動結束時間
				'resetPeriodType' => $category['period'],//週期type
				'nextDateTime' => $this->matchOutputNextDateTime($category['period']),//下個週期開始時間
				'expiryTime' => $category['showTimer'] == 1 ? $this->playerapi_lib->formatDateTime($category['endAt']) : '',//下架時間
				'iconUrl' => $iconUrl,
				'bannerUrl' => $category['bannerPath'],
				'currency' => $currency,
			];
		}, $questData);
		return $resultData;
	}

	protected function getQuestManager($categoryCode){
		$result = ['code' => Playerapi::CODE_OK];
		try{
			$request_body = $this->playerapi_lib->getRequestPramas();
			$request_body['categoryCode'] = $categoryCode;
			$validate_fields = [
				['name' => 'categoryCode', 'type' => 'currency_id', 'required' => true, 'length' => 0],
				['name' => 'limit', 'type' => 'int', 'required' => false, 'length' => 0],
				['name' => 'page', 'type' => 'int', 'required' => false, 'length' => 0],
			];
			$is_validate_basic_passed = $this->playerapi_lib->validParmasBasic($request_body, $validate_fields);

			if (!$is_validate_basic_passed['validate_flag']) {
				throw new \APIException($is_validate_basic_passed['validate_msg'], Playerapi::CODE_INVALID_PARAMETER);
			}

			$hierarchy = isset($request_body['hierarchy']) ? $request_body['hierarchy'] : null;
			$limit = (isset($request_body['limit'])) ? $request_body['limit'] : 200;
			$page = (isset($request_body['page'])) ? $request_body['page'] : 1;

			list($currency, $categoryId) = $this->playerapi_lib->parseCurrencyAndIdFromCode($categoryCode);

			$result['data'] = [
				'totalRecordCount' => 0,
				'totalPages' => 0,
				'totalRowsCurrentPage' => 0,
				'currentPage' => 0,
				'list' => []
			];

			$dataResult = $this->playerapi_lib->switchCurrencyForAction($currency, function() use ($categoryId, $hierarchy, $limit, $page, $currency) {
				if (!$this->verifyQuestCategoryCountdownExpired($categoryId)) {
					$this->quest_library->deleteQuestManagerCache($categoryId, $currency);
					return [];
				}

				$cacheKey = "getQuestManagerCacheKey-$currency-$categoryId";
				$cachedResult = $this->utils->getJsonFromCache($cacheKey);

				if (!empty($cachedResult)) {
					$this->comapi_log(__METHOD__, ['cached_result' => $cachedResult]);
					return $cachedResult;
				}

				$questManager = $this->getQuestManagerlist($categoryId, $currency, $hierarchy, $limit, $page);
				$this->utils->saveJsonToCache($cacheKey, $questManager, 4 * 60 * 60);

				return $questManager;
			});

			if (empty($dataResult)) {
				return $this->returnSuccessWithResult($result);
			}

			$result['data'] = $this->playerapi_lib->convertOutputFormat($dataResult);

			return $this->returnSuccessWithResult($result);
		}catch (\APIException $ex) {
			$result['code'] = $ex->getCode();
			$result['errorMessage']= $ex->getMessage();
			$this->comapi_log(__METHOD__, 'APIException', $result);
			return $this->returnErrorWithResult($result);
		}
	}

	protected function getQuestManagerList($categoryId, $currency, $hierarchy, $limit, $page)
	{
		$questManager = $this->playerapi_model->getQuestManagerlistById($categoryId, $hierarchy, $limit, $page);
		$currency = strtoupper($currency);
		$questManager = $this->_mappingQuestManagerInfoOutputResult($questManager, $currency);

		return $questManager;
	}

	protected function _mappingQuestManagerInfoOutputResult($questData, $currency)
	{
		$questData['list'] = array_map(function ($quest) use ($currency){

			if ($quest['levelType'] == Quest_manager::QUEST_LEVEL_TYPE_HIERARCHY) {
				$outputItem = $this->_mappingLadderQuestInfoOutputResult($quest, $currency);
			}else {
				$outputItem = $this->_mappingSignleQuestInfoOutputResult($quest, $currency);
			}
			
			return $this->playerapi_lib->convertOutputFormat($outputItem);

		}, $questData['list']);
		return $questData;
	}

	protected function _mappingLadderQuestInfoOutputResult($questData, $currency)
	{
		$this->utils->debug_log(__METHOD__, 'ladder questData', $questData);

		$outputItem = $this->_commonMapping($questData, $currency);
		$outputItem['subQuestJobs'] = array();

		$questJobs = $this->quest_manager->getQuestJobByQuestManagerId($questData['questManagerId']);
		$this->utils->debug_log(__METHOD__, 'subQuestJobs', $questJobs);

		if(!empty($questJobs)) {
			foreach ($questJobs as $item) {
				$subQuestJob = array(
					"questJobId" => $item["questJobId"],
					"title" => $item["title"],
					"condition" => array(
						"type" => $item["questConditionType"],
						"value" => floatval($item["questConditionValue"]),
						"personalInfoType" => $item["personalInfoType"],
						"communityOptions" => $item["communityOptions"]
					),
					"bonus" => array(
						"type" => $item["bonusConditionType"],
						"value" => floatval($item["bonusConditionValue"])
					),
					"withdrawalCondition" => $this->_mappingWithdrawalConditionInfo($item),
				);
				array_push($outputItem["subQuestJobs"], $subQuestJob);
			}
		}

		return $outputItem;
	}

	protected function _mappingSignleQuestInfoOutputResult($questData, $currency)
	{
		$this->utils->debug_log(__METHOD__, 'signle questData', $questData);
		$outputItem = $this->_commonMapping($questData, $currency);

		$value = in_array($questData['questConditionType'], [6,7,8,9,10,11,12]) ? 1 : floatval($questData['questConditionValue']);

		$outputItem['condition'] = [
			'type' => $questData['questConditionType'],
			'value' => $value,
			'personalInfoType' => $questData['personalInfoType'],
			'communityOptions' => $questData['communityOptions']
		];
		$outputItem['bonus'] = [
			'type' => $questData['bonusConditionType'],
			'value' => floatval($questData['bonusConditionValue'])
		];
		$outputItem['withdrawalCondition'] = $this->_mappingWithdrawalConditionInfo($questData);

		return $outputItem;
	}

	protected function _commonMapping($quest, $currency)
	{
		$iconUrl = !empty($quest['iconPath']) ? $this->utils->getSystemUrl('player') . $this->utils->getQuestCategoryIcon($quest['iconPath']) : '';
		$bannerUrl = !empty($quest['bannerPath']) ? $this->utils->getSystemUrl('player') . $this->utils->getQuestCategoryIcon($quest['bannerPath']) : '';

		$outputItem = [
			'questCode' => $currency.'_'.$quest['questManagerId'],
			'questType' => $quest['levelType'],
			'title' => $quest['title'],
			'description' => $quest['description'],
			'iconUrl' => $iconUrl,
			'bannerUrl' => $bannerUrl,
			'questManagerType' => $quest['questManagerType'],
			'resetPeriodType' => $quest['period'],
			'panelDisplayType' => $this->quest_library->mappingQuestPanel($quest['displayPanel']),
			'autoClaimEnabled' => (bool)$quest['showOneClick'],
			'countdownEnabled' => (bool)$quest['showTimer'],
			'startAt' => $this->playerapi_lib->formatDateTime($quest['startAt']),
			'endAt' => $this->playerapi_lib->formatDateTime($quest['endAt']),
			'nextDateTime' => $this->matchOutputNextDateTime($quest['period']),
			'externalLink' => $quest['claimOtherUrl'],
			'currency' => $currency,
		];

		return $outputItem;
	}

	protected function _mappingWithdrawalConditionInfo($quest)
	{
		$withdrawalCondition = [];
		$withdrawalCondition['type'] = $quest['withdrawalConditionType'];

		switch ($withdrawalCondition['type']) {
			case Quest_manager::WITHDRAW_CONDITION_TYPE_FIXED_AMOUNT:
				$withdrawalCondition['value'] = $quest['withdrawReqBetAmount'];
				break;
			case Quest_manager::WITHDRAW_CONDITION_TYPE_BONUS_TIMES:
				$withdrawalCondition['value'] = $quest['withdrawReqBonusTimes'];
				break;
			case Quest_manager::WITHDRAW_CONDITION_TYPE_BETTING_TIMES:
				$withdrawalCondition['value'] = $quest['withdrawReqBettingTimes'];
				break;
			default:
				$withdrawalCondition['type'] = 999;
				$withdrawalCondition['value'] = 0;
				break;
		}

		return $withdrawalCondition;
	}

	protected function matchOutputNextDateTime($period)
	{
		$nextDateTime = '';
		switch ($period) {
			case Quest_manager::BONUS_APPLICATION_LIMIT_DATE_TYPE_DAILY:
				$nextDateTime = date('Y-m-d H:i:s', strtotime('tomorrow'));
				$nextDateTime = $this->playerapi_lib->formatDateTime($nextDateTime);
				break;
			case Quest_manager::BONUS_APPLICATION_LIMIT_DATE_TYPE_WEEKLY:
				$nextDateTime = date('Y-m-d H:i:s', strtotime('next monday'));
				$nextDateTime = $this->playerapi_lib->formatDateTime($nextDateTime);
				break;
			case Quest_manager::BONUS_APPLICATION_LIMIT_DATE_TYPE_MONTHLY:
				$nextDateTime = date('Y-m-d H:i:s', strtotime('next month first day'));
				$nextDateTime = $this->playerapi_lib->formatDateTime($nextDateTime);
				break;
		}
		$this->utils->debug_log(__METHOD__, 'period', $period, 'nextDateTime', $nextDateTime);
		return $nextDateTime;
	}

	protected function getQuestProgressByPlayer($playerId)
	{
		$result = ['code' => Playerapi::CODE_OK];

		try {
			$request_body = $this->playerapi_lib->getRequestPramas();
			$validate_fields = [
				['name' => 'questCode', 'type' => 'currency_id', 'required' => true, 'length' => 0],
			];

			$is_validate_basic_passed = $this->playerapi_lib->validParmasBasic($request_body, $validate_fields);

			if (!$is_validate_basic_passed['validate_flag']) {
				throw new \APIException($is_validate_basic_passed['validate_msg'], Playerapi::CODE_INVALID_PARAMETER);
			}

			$questCode = $request_body['questCode'];
			$questType = $request_body['questType'];
			$questId = null;
			$currency = null;

			list($currency, $questId) = $this->playerapi_lib->parseCurrencyAndIdFromCode($questCode);
			$isHierarchy = $questType == quest_manager::QUEST_LEVEL_TYPE_HIERARCHY ? true : false;

			$questProgress =  $this->playerapi_lib->switchCurrencyForAction($currency, function() use ($playerId, $questId, $isHierarchy, $currency, &$errorMessage) {
				$verify = $this->quest_library->verifyQuestCountdownExpired($questId);
				if (!$verify['passed']) {
					$errorMessage = $verify['errorMessage'];
					return [];
				}

				$cacheKey = "getQuestProgressByPlayerCacheKey-$currency-$playerId-$questId";
				$cachedResult = $this->utils->getJsonFromCache($cacheKey);

				if (!empty($cachedResult)) {
					$this->comapi_log(__METHOD__, ['cached_result' => $cachedResult]);
					return $cachedResult;
				}

				$progressRes = $this->getQuestProgressList($playerId, $questId, $isHierarchy, $currency);
				$this->utils->saveJsonToCache($cacheKey, $progressRes, 5 * 60);

				return $progressRes;
			});

			if (empty($questProgress)) {
				throw new \APIException($errorMessage, $result['code']);
			}

			$result['data'] = $this->playerapi_lib->convertOutputFormat($questProgress);

			return $this->returnSuccessWithResult($result);
		} catch (\APIException $ex) {
			$result['code'] = $ex->getCode();
			$result['errorMessage']= $ex->getMessage();
			$this->comapi_log(__METHOD__, 'APIException', $result);
			return $this->returnErrorWithResult($result);
		}
	}

	protected function getQuestProgressList($playerId, $questId, $isHierarchy, $currency)
	{	
		$outputItem = [];

		$result = [
			'code' => self::CODE_REQUEST_QUEST_PROGRESS_FAILED
		];

		$verify_function_list = [
			[ 'name' => 'verifyQuestManagerExist', 'params' => [$questId] ],
			[ 'name' => 'verifyQuestType', 'params' => [$questId, $isHierarchy] ],
		];
		
		foreach ($verify_function_list as $method) {
			$this->utils->debug_log('============getQuestProgressList verify_function', $method);
			$verify_result = call_user_func_array([$this, $method['name']], $method['params']);
			$exec_continue = $this->playerapi_lib->checkIfExecContinueAfterVerify($verify_result);
			if(!$exec_continue) {
				$result['errorMessage'] = $verify_result['error_message'];
				throw new APIException($result['errorMessage'], $result['code']);
			}
		}

		list($managerDetail, $categoryDetails) = $this->quest_library->getQuestManagerDetailsWithCategory($questId);

		if (empty($managerDetail) || empty($categoryDetails)){
			$message = lang('Quest Ctegory or Manager is not exist.');
			$this->utils->debug_log(__METHOD__, $message, $questId, $managerDetail, $categoryDetails);
			throw new APIException($message, $result['code']);
		}

		list($fromDatetime, $toDatetime) = $this->quest_library->getQuestPeriodTypeDatetime($categoryDetails);

		$success = $this->lockAndTransForPlayerQuest($playerId, function () use ($playerId, $questId, $isHierarchy, &$questProgress, $fromDatetime, $toDatetime, $managerDetail) {

			$categoryId = $managerDetail['questCategoryId'];
			$questProgress = $this->getQuestProgress($playerId, $questId, $fromDatetime, $toDatetime, $isHierarchy);

			if ($isHierarchy){
				$questRule = $this->quest_manager->getQuestJobByQuestManagerId($questId);
				$this->utils->debug_log(__METHOD__."-questRule-$playerId", $questRule);

				$cntSuccess = 0;
				$cntSuccessArr = [];
				$newRules = [];
				foreach ($questRule as $rule) {

					$questJobId = $rule['questJobId'];
					$questProgressJobIds = array_column($questProgress, 'questJobId');
					$this->utils->debug_log(__METHOD__, "questProgressJobIds-$playerId", $questProgressJobIds, $questJobId, count($questRule));

					if (!in_array($questJobId, $questProgressJobIds)) {
						$newRules[] = $rule;
						$createPlayer = $this->quest_library->createQuestProgress($playerId, $questId, $rule, $isHierarchy, $fromDatetime, $toDatetime, $categoryId);

						if ($createPlayer) {
							$cntSuccess++;
							$cntSuccessArr[] = $createPlayer;
						}
					}
				}

				$questProgress = $this->getQuestProgress($playerId, $questId, $fromDatetime, $toDatetime, $isHierarchy, count($questRule));

				$this->utils->debug_log(__METHOD__, "createQuestProgressJob-$playerId", $cntSuccess, $cntSuccessArr, $questProgress);
			}else{
				if(empty($questProgress)){
					$questRuleId = $managerDetail['questRuleId'];
					$questRule = $this->quest_manager->getQuestRuleByQuestRuleId($questRuleId);

					$this->utils->debug_log(__METHOD__, "questRule-$playerId", $questRule);

					$questProgress = $this->quest_library->createQuestProgress($playerId, $questId, $questRule, $isHierarchy, $fromDatetime, $toDatetime, false, $categoryId);
					if ($questProgress) {
						$questProgress = $this->getQuestProgress($playerId, $questId, $fromDatetime, $toDatetime);
					}
				}
			}
			return true;
		});

		$outputItem = $this->_mappingQuestProgressInfoOutputResult($questProgress, $isHierarchy, $currency);

		$this->utils->debug_log(__METHOD__, "questProgress-$playerId", $outputItem, 'success', $success);
		return $outputItem;
	}

	protected function _mappingQuestProgressInfoOutputResult($questProgress, $isHierarchy, $currency)
	{
		$outputItem = [];

		$this->utils->debug_log(__METHOD__, "mappingQuestProgress-$isHierarchy-$currency", $questProgress);
		if ($isHierarchy){
			$questManagerIds = array_column($questProgress, 'questManagerId');
			if(count(array_unique($questManagerIds)) > 1) {
				return $outputItem;
			}

			$subQuestJobs = array_map(function ($progress){
				return [
					'id' => $progress['id'],
					'questJobId' => $progress['questJobId'],
					'jobProgress' => $progress['jobStats'],
					'rewardStatus' => $progress['rewardStatus'],
				];
			}, $questProgress);

			$outputItem['questCode'] = strtoupper($currency).'_'.$questProgress[0]['questManagerId'];
			$outputItem['subQuestJobs'] = $subQuestJobs;
		}else{
			$questProgress = $questProgress[0];
			$outputItem['questCode'] = strtoupper($currency).'_'.$questProgress['questManagerId'];
			$outputItem['jobProgress'] = $questProgress['jobStats'];
			$outputItem['rewardStatus'] = $questProgress['rewardStatus'];
		}
		return $outputItem;
	}

	protected function _mappingQuestProgressExpiredOutputResult($managerDetail, $isHierarchy, $currency)
	{
		$outputItem = [];

		if ($isHierarchy){
			$questRule = $this->quest_manager->getQuestJobByQuestManagerId($managerDetail['questManagerId']);
			$subQuestJobs = array_map(function ($rule){
				return [
					'questJobId' => $rule['questJobId'],
					'jobProgress' => 0,
					'rewardStatus' => 4,
				];
			}, $questRule);

			$outputItem['questCode'] = strtoupper($currency).'_'.$managerDetail['questManagerId'];
			$outputItem['subQuestJobs'] = $subQuestJobs;
		}else{
			$outputItem['questCode'] = strtoupper($currency).'_'.$managerDetail['questManagerId'];
			$outputItem['jobProgress'] = 0;
			$outputItem['rewardStatus'] = 4;
		}
		return $outputItem;
	}

	protected function claimQuest($playerId)
	{
		$result = ['code' => Playerapi::CODE_OK];

		try {
			$request_body = $this->playerapi_lib->getRequestPramas();
			$this->utils->debug_log(__METHOD__, 'request_body', $request_body);
			$validate_fields = [
				['name' => 'questCode', 'type' => 'currency_id', 'required' => true, 'length' => 0],
				['name' => 'questType', 'type' => 'int', 'required' => true, 'length' => 0, 'allowed_content' => [1, 2]],
				['name' => 'subQuestJobs', 'type' => 'array', 'required' => false, 'length' => 0],
			];

			$is_validate_basic_passed = $this->playerapi_lib->validParmasBasic($request_body, $validate_fields);

			if (!$is_validate_basic_passed['validate_flag']) {
				throw new \APIException($is_validate_basic_passed['validate_msg'], Playerapi::CODE_INVALID_PARAMETER);
			}

			$questCode = $request_body['questCode'];
			$questType = $request_body['questType'];
			$questId = null;
			$currency = null;
			$subQuestJobs = isset($request_body['subQuestJobs']) ? $request_body['subQuestJobs'] : [];

			list($currency, $questId) = $this->playerapi_lib->parseCurrencyAndIdFromCode($questCode);
			$isHierarchy = $questType == quest_manager::QUEST_LEVEL_TYPE_HIERARCHY ? true : false;

			$output =  $this->playerapi_lib->switchCurrencyForAction($currency, function() use ($playerId, $questId, $isHierarchy, $currency, $subQuestJobs) {
				return $this->handleQuestRequest($playerId, $questId, $isHierarchy, $subQuestJobs, $currency);
			});

			$result['data'] = $this->playerapi_lib->convertOutputFormat($output);

			return $this->returnSuccessWithResult($result);
		} catch (\APIException $ex) {
			$result['code'] = $ex->getCode();
			$result['errorMessage']= $ex->getMessage();
			$this->comapi_log(__METHOD__, 'APIException', $result);
			return $this->returnErrorWithResult($result);
		}
	}

	protected function handleQuestRequest($playerId, $questId, $isHierarchy, $subQuestJobs, $currency)
	{
		$outputItem = [];

		$result = [
			'code' => self::CODE_REQUEST_QUEST_APPLY_FAILED
		];

		$verify_function_list = [
			[ 'name' => 'verifyQuestManagerExist', 'params' => [$questId] ],
			[ 'name' => 'verifyQuestManagerAvailable', 'params' => [$questId] ],
			[ 'name' => 'verifyQuestType', 'params' => [$questId, $isHierarchy] ],
			[ 'name' => 'verifyQuestCountdownExpired', 'params' => [$questId] ],
		];
		
		if ($isHierarchy) {
			if (!empty($subQuestJobs)) {
				//檢查SubQuestJobs id 是否屬於questId階梯任務內的
				$verify_function_list[] = [ 'name' => 'verifySubQuestJobs', 'params' => [$questId, $subQuestJobs] ];
				$verify_function_list[] = [ 'name' => 'verifyRequestSameIps', 'params' => [$questId, $playerId, $isHierarchy, $subQuestJobs] ];
			}
		}

		foreach ($verify_function_list as $method) {
			$this->utils->debug_log('============getQuestProgressList verify_function', $method);
			$verify_result = call_user_func_array([$this, $method['name']], $method['params']);
			$exec_continue = $this->playerapi_lib->checkIfExecContinueAfterVerify($verify_result);
			if(!$exec_continue) {
				$result['errorMessage'] = $verify_result['error_message'];
				throw new APIException($result['errorMessage'], $result['code']);
			}
		}

		$outputItem = $this->applyQuest($playerId, $questId, $isHierarchy, $currency, $subQuestJobs);

		$currency = strtoupper($currency);
		$getQuestProgressByPlayerCacheKey = "getQuestProgressByPlayerCacheKey-$currency-$playerId-$questId";
		$this->utils->deleteCache($getQuestProgressByPlayerCacheKey);

		$this->utils->debug_log(__METHOD__, "applyQuest-$playerId", $outputItem);
		return $outputItem;
	}

	protected function fetchQuestRuleDetails($questRule, $subQuestJobs){
		$result = [];

		foreach ($subQuestJobs as $subQuestJob) {
			$questJobId = $subQuestJob['questJobId'];
			foreach ($questRule as $data) {
				if ($data['questJobId'] == $questJobId) {
					$result[] = $data;
					break;
				}
			}
		}

		return $result;
	}

	protected function applyQuest($playerId, $questId, $isHierarchy, $currency, $subQuestJobs = array())
	{
		$ret =[];

		list($managerDetail, $categoryDetails) = $this->quest_library->getQuestManagerDetailsWithCategory($questId);

		if (empty($managerDetail) || empty($categoryDetails)){
			$message = lang('Quest Ctegory or Manager is not exist.');
			$this->utils->debug_log(__METHOD__, $message, $questId, $managerDetail, $categoryDetails);
			return $ret;
		}

		if ($isHierarchy) {
			$questRule = $this->quest_manager->getQuestJobByQuestManagerId($questId);
			if (!empty($subQuestJobs)){
				$questRule = $this->fetchQuestRuleDetails($questRule, $subQuestJobs);
			}
		}else{
			$questRuleId = $managerDetail['questRuleId'];
			$questRule = $this->quest_manager->getQuestRuleByQuestRuleId($questRuleId);
		}

		list($fromDatetime, $toDatetime) = $this->quest_library->getQuestPeriodTypeDatetime($categoryDetails);
		$this->utils->debug_log(__METHOD__."-questRule-$playerId", $questRule, $fromDatetime, $toDatetime);
		$controller = $this;
		$successCount = 0;
		$success = $this->lockAndTransForPlayerQuest($playerId, function () use ($controller, $playerId, $questId, &$ret, $isHierarchy , $questRule, $currency, $fromDatetime, $toDatetime, $categoryDetails, &$successCount) {

			if ($isHierarchy) {
				foreach ($questRule as $key => $rule) {
					$this->utils->debug_log(__METHOD__."-isHierarchy rule-$playerId", $rule);

					$playerQuest = $this->quest_manager->getQuestProgressByQuestJobId($playerId, $rule['questJobId'], $fromDatetime, $toDatetime);

					list($success, $message) = $controller->quest_library->requestQuest($playerId #1
																						, $questId #2
																						, $rule #3
																						, $playerQuest #4
																						, $isHierarchy #5
																						, $fromDatetime #6
																						, $toDatetime #7
																						, $categoryDetails #8
																						);

					$this->utils->debug_log(__METHOD__."apply quest isHierarchy-$playerId", $success, $message);

					$ret['questCode'] = strtoupper($currency).'_'.$questId;

					$ret['subQuestJobs'][$key] = array(
						'questJobId' => $rule['questJobId'],
					);

					if ($success){
						$ret['subQuestJobs'][$key]['successMessage'] = $message;
						$successCount++;
					}else{
						$ret['subQuestJobs'][$key]['errorMessage'] = $message;
					}
				}
			}else{
				$playerQuest = $this->getQuestProgress($playerId, $questId, $fromDatetime, $toDatetime);
				list($success, $message) = $controller->quest_library->requestQuest($playerId #1
																, $questId #2
																, $questRule #3
																, $playerQuest #4
																, $isHierarchy #5
																, $fromDatetime #6
																, $toDatetime #7
																, $categoryDetails #8
																);

				$this->utils->debug_log(__METHOD__."apply quest-$playerId", $success, $message);

				$ret['questCode'] = strtoupper($currency).'_'.$questId;

				if($success){
					$ret['successMessage'] = $message;
				} else {
					$ret['errorMessage'] = $message;
				}
			}

			return true;
		});

		$this->utils->debug_log(__METHOD__, "applyQuest-ret-$playerId", $success, $ret, $successCount);

		if ($successCount > 0){
			$categoryId = $categoryDetails['questCategoryId'];
			$token = $this->updatePlayerQuestRewardStatusByQueue($categoryId, $questId, $playerId);
			$this->utils->debug_log(__METHOD__, "updatePlayerQuestRewardStatusByQueueWhenApplyQuest-$playerId", $token);
		}

		return $ret;
	}

	//start verify function
	private function verifyQuestManagerAvailable($questId)
	{
		$verify_result = ['passed' => true, 'error_message' => ''];
		try {

			list($managerDetail, $categoryDetails) = $this->quest_library->getQuestManagerDetailsWithCategory($questId);

			if (empty($managerDetail) || empty($categoryDetails)){
				$message = lang('Quest Ctegory or Manager is not exist.');
				$this->utils->debug_log(__METHOD__, $message, $questId, $managerDetail, $categoryDetails);
				$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, $message);
				return $verify_result;
			}

			if (!$this->config->item('enabled_quest')) {
				$message = lang('Quest config is not enable.');
				$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, $message);
				return $verify_result;
			}
			
			if ($categoryDetails['status'] != 1) {
				$message = lang('Quest Category is inactive.');
				$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, $message);
				return $verify_result;
			}

			if ($managerDetail['status'] != 1) {
				$message = lang('Quest Manager is inactive.');
				$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, $message);
				return $verify_result;
			}

			return $verify_result;
		} catch (Exception $ex) {
			$this->utils->debug_log('============'. __METHOD__ .' Exception', $ex->getMessage());
			$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, 'Unknown Error during '.__METHOD__);
			return $verify_result;
		}
	}

	private function verifyQuestType($questId, $isHierarchy)
	{
		$verify_result = ['passed' => true, 'error_message' => ''];
		try {
			$managerDetail = $this->quest_library->getQuestManagerDetails($questId);

			if (empty($managerDetail)){
				$message = lang('Quest Manager is not exist.');
				$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, $message);
				return $verify_result;
			}

			$questType = $isHierarchy ? Quest_manager::QUEST_LEVEL_TYPE_HIERARCHY : Quest_manager::QUEST_LEVEL_TYPE_SINGLE;

			if ($managerDetail['levelType'] != $questType) {
				$message = lang('Quest Type is not match.');
				$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, $message);
				return $verify_result;
			}

			return $verify_result;
		} catch (Exception $ex) {
			$this->utils->debug_log('============'. __METHOD__ .' Exception', $ex->getMessage());
			$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, 'Unknown Error during '.__METHOD__);
			return $verify_result;
		}
	}

	private function verifyQuestManagerExist($questId)
	{
		$verify_result = ['passed' => true, 'error_message' => ''];
		try {

			if (!$this->playerapi_model->checkQuestManagerExist($questId)) {
				$message = lang('Quest Manager is not exist.');
				$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, $message);
			}
			
			return $verify_result;
		} catch (Exception $ex) {
			$this->utils->debug_log('============'. __METHOD__ .' Exception', $ex->getMessage());
			$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, 'Unknown Error during '.__METHOD__);
			return $verify_result;
		}
	}

	private function verifyQuestCountdownExpired($questId)
	{
		$verify_result = ['passed' => true, 'error_message' => ''];
		try {

			$verify = $this->quest_library->verifyQuestCountdownExpired($questId);
			
			if (!$verify['passed']) {
				$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, $verify['errorMessage']);
				$this->utils->debug_log(__METHOD__, $verify, $questId);
			}

			return $verify_result;
		} catch (Exception $ex) {
			$this->utils->debug_log('============'. __METHOD__ .' Exception', $ex->getMessage());
			$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, 'Unknown Error during '.__METHOD__);
			return $verify_result;
		}
	}

	private function verifySubQuestJobs($questId, $subQuestJobs)
	{
		$verify_result = ['passed' => true, 'error_message' => ''];
		try {
			$questRule = $this->quest_manager->getQuestJobByQuestManagerId($questId);
			$questRule = $this->fetchQuestRuleDetails($questRule, $subQuestJobs);

			if (count($questRule) != count($subQuestJobs)) {
				$message = lang('SubQuestJobs questJobId is not match.');
				$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, $message);
			}

			return $verify_result;
		} catch (Exception $ex) {
			$this->utils->debug_log('============'. __METHOD__ .' Exception', $ex->getMessage());
			$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, 'Unknown Error during '.__METHOD__);
			return $verify_result;
		}
	}

	private function verifyQuestManagerConditionType($questId)
	{
		$verify_result = ['passed' => true, 'error_message' => ''];
		try {
			$managerDetail = $this->quest_library->getQuestManagerDetails($questId);

			if (empty($managerDetail)){
				$message = lang('Quest Manager is not exist.');
				$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, $message);
				return $verify_result;
			}

			$questRuleId = $managerDetail['questRuleId'];
			$questRule = $this->quest_manager->getQuestRuleByQuestRuleId($questRuleId);

			$questConditionType = $questRule['questConditionType'];

			if (!in_array($questConditionType, [8, 9, 10, 11, 12])) {//download app, Follow channel, community Option
				$message = lang('Quest Condition Type is not match.');
				$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, $message);
			}

			return $verify_result;
		} catch (Exception $ex) {
			$this->utils->debug_log('============'. __METHOD__ .' Exception', $ex->getMessage());
			$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, 'Unknown Error during '.__METHOD__);
			return $verify_result;
		}
	}

	protected function verifyRequestSameIps($questId, $playerId, $isHierarchy, $subQuestJobs = [])
	{
		$verify_result = ['passed' => true, 'error_message' => ''];
		try {
			$managerDetail = $this->quest_library->getQuestManagerDetails($questId);

			if (empty($managerDetail)){
				$message = lang('Quest Manager is not exist.');
				$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, $message);
				return $verify_result;
			}

			$allowSameIPBonusReceipt = $managerDetail['allowSameIPBonusReceipt'];
			$playerIp = $this->utils->getIp();

			if (!$allowSameIPBonusReceipt) {
				foreach ($subQuestJobs as $subQuestJob) {
					$questJobId = $subQuestJob['questJobId'];
					if($this->quest_manager->existsPlayerQuestFromSameIp($questId, $playerIp, $questJobId, $playerId)){
						$message = lang('dont allow request quest from same ips.');
						$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, $message);
						return $verify_result;
					}
				}
			}

			return $verify_result;
		} catch (Exception $ex) {
			$this->utils->debug_log('============'. __METHOD__ .' Exception', $ex->getMessage());
			$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, 'Unknown Error during '.__METHOD__);
			return $verify_result;
		}
	}

	protected function verifyQuestCategoryCountdownExpired($categoryId)
	{
		$result = true;
		$categoryDetails = $this->quest_library->getQuestCategoryDetails($categoryId);

		if (empty($categoryDetails)) {
            $message = lang('Quest category is not found.');
            $result = false;
            $this->utils->debug_log(__METHOD__, $message, $categoryId);
            return $result;
        }

		$showTimer = $categoryDetails['showTimer'];
		$endAt = $categoryDetails['endAt'];

		if ($showTimer) {
			$endAt = $categoryDetails['endAt'];

			$currTime = $this->utils->getNowForMysql();
			if ($currTime > $endAt) {
				$message = lang('Quest countdown is expired.');
				$result = false;
				$this->utils->debug_log(__METHOD__, $message, $currTime, $endAt, $categoryId, $categoryDetails);
			}
		}

		return $result;
	}
	//end verify function

	protected function interactQuest($playerId)
	{
		$result = ['code' => Playerapi::CODE_OK];
		try{
			$request_body = $this->playerapi_lib->getRequestPramas();
			$validate_fields = [
				['name' => 'questCode', 'type' => 'currency_id', 'required' => true, 'length' => 0],
			];

			$is_validate_basic_passed = $this->playerapi_lib->validParmasBasic($request_body, $validate_fields);

			if (!$is_validate_basic_passed['validate_flag']) {
				throw new \APIException($is_validate_basic_passed['validate_msg'], Playerapi::CODE_INVALID_PARAMETER);
			}

			$questCode = $request_body['questCode'];
			$questId = null;
			$currency = null;

			list($currency, $questId) = $this->playerapi_lib->parseCurrencyAndIdFromCode($questCode);

			$verify = $this->quest_library->verifyQuestCountdownExpired($questId);
			if (!$verify['passed']) {
				throw new \APIException($verify['errorMessage'], $result['code']);
			}

			$output =  $this->playerapi_lib->switchCurrencyForAction($currency, function() use ($playerId, $questId, $currency) {
				return $this->handleInteractQuestRequest($playerId, $questId, $currency);
			});

			$result['data'] = $this->playerapi_lib->convertOutputFormat($output);

			return $this->returnSuccessWithResult($result);
		}catch (\APIException $ex) {
			$result['code'] = $ex->getCode();
			$result['errorMessage']= $ex->getMessage();
			$this->comapi_log(__METHOD__, 'APIException', $result);
			return $this->returnErrorWithResult($result);
		}
	}

	protected function handleInteractQuestRequest($playerId, $questId, $currency)
	{
		$outputItem = [];

		$result = [
			'code' => self::CODE_REQUEST_QUEST_PROGRESS_FAILED
		];

		$verify_function_list = [
			[ 'name' => 'verifyQuestManagerExist', 'params' => [$questId] ],
			[ 'name' => 'verifyQuestType', 'params' => [$questId, false] ],
			[ 'name' => 'verifyQuestManagerConditionType', 'params' => [$questId] ],
			[ 'name' => 'verifyQuestManagerAvailable', 'params' => [$questId] ],
		];
		
		foreach ($verify_function_list as $method) {
			$this->utils->debug_log('============getQuestProgressList verify_function', $method);
			$verify_result = call_user_func_array([$this, $method['name']], $method['params']);
			$exec_continue = $this->playerapi_lib->checkIfExecContinueAfterVerify($verify_result);
			if(!$exec_continue) {
				$result['errorMessage'] = $verify_result['error_message'];
				throw new APIException($result['errorMessage'], $result['code']);
			}
		}

		$outputItem = $this->checkInteractQuest($playerId, $questId, $currency);

		$this->utils->debug_log(__METHOD__."checkInteractQuest-ret-$playerId", $outputItem);

		$currency = strtoupper($currency);
		$getQuestProgressByPlayerCacheKey = "getQuestProgressByPlayerCacheKey-$currency-$playerId-$questId";
		$this->utils->deleteCache($getQuestProgressByPlayerCacheKey);

		return $outputItem;
	}

	protected function checkInteractQuest($playerId, $questId, $currency)
	{
		$outputItem = [];

		list($managerDetail, $categoryDetails) = $this->quest_library->getQuestManagerDetailsWithCategory($questId);

		if (empty($managerDetail) || empty($categoryDetails)){
			$message = lang('Quest Ctegory or Manager is not exist.');
			$this->utils->debug_log(__METHOD__, $message, $questId, $managerDetail, $categoryDetails);
			return $outputItem;
		}

		list($fromDatetime, $toDatetime) = $this->quest_library->getQuestPeriodTypeDatetime($categoryDetails);
		$questProgress = $this->getQuestProgress($playerId, $questId, $fromDatetime, $toDatetime);
		$questRuleId = $managerDetail['questRuleId'];
		$questRule = $this->quest_manager->getQuestRuleByQuestRuleId($questRuleId);

		$this->utils->debug_log(__METHOD__. "-$playerId", $questRule);

		if (empty($questProgress)) {
			$questProgress = $this->createAndFetchQuestProgress($playerId, $questId, $questRule, $fromDatetime, $toDatetime);
		} elseif ($questProgress[0]['rewardStatus'] == 1) {
			$questProgress = $this->updateAndFetchQuestProgress($playerId, $questId, $questProgress, $questRule, $fromDatetime, $toDatetime);
		}

		$outputItem = $this->_mappingQuestInteractInfoOutputResult($questProgress, $currency);

		$this->utils->debug_log(__METHOD__."-$playerId", $outputItem);
		return $outputItem;
	}

	private function getQuestProgress($playerId, $questId, $fromDatetime, $toDatetime, $isHierarchy = false, $limit = 0)
	{
		return $this->quest_manager->getQuestProgressByPlayer($playerId, $questId, $fromDatetime, $toDatetime, $isHierarchy, $limit);
	}

	private function createAndFetchQuestProgress($playerId, $questId, $questRule, $fromDatetime, $toDatetime)
	{
		$isInteract = true;
		$this->quest_library->createQuestProgress($playerId, $questId, $questRule, false, $fromDatetime, $toDatetime, $isInteract);
		return $this->getQuestProgress($playerId, $questId, $fromDatetime, $toDatetime);
	}

	private function updateAndFetchQuestProgress($playerId, $questId, $questProgress, $questRule, $fromDatetime, $toDatetime)
	{
		$questConditionResult = $this->quest_library->getPlayerQuestProgressStatus($playerId, $questId, $questRule, $fromDatetime, $toDatetime, true);
		$this->utils->debug_log("Quest Condition Result-$playerId", $questConditionResult);

		$statsData = [
			'jobStats' => $questConditionResult['jobStats'],
			'rewardStatus' => $questConditionResult['rewardStatus'],
			'updatedAt' => $this->utils->getNowForMysql(),
		];

		$questStateId = $questProgress[0]['id'];
		if ($this->quest_manager->updatePlayerQuestJobState($playerId, $questId, $questStateId, $statsData)) {
			return $this->getQuestProgress($playerId, $questId, $fromDatetime, $toDatetime);
		}
		return $questProgress;
	}

	protected function _mappingQuestInteractInfoOutputResult($questProgress, $currency)
	{
		$outputItem = [];

		$this->utils->debug_log(__METHOD__."-$currency", $questProgress);
		$questProgress = $questProgress[0];
		$outputItem['questCode'] = strtoupper($currency).'_'.$questProgress['questManagerId'];
		$outputItem['jobProgress'] = $questProgress['jobStats'];
		$outputItem['rewardStatus'] = $questProgress['rewardStatus'];

		return $outputItem;
	}

	public function updatePlayerQuestRewardStatusByQueue($categoryId, $managerId, $playerId)
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
			'playerId' => $playerId,
			'operator' => $operator,
		];

		$token = $this->lib_queue->addUpdatePlayerQuestRewardStatus($params, $callerType, $caller, $state);

		return $token;
	}
}
