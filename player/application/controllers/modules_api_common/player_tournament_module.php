<?php

/**
 * uri: /tournament
 *
 * @property Tournament_lib $tournament_lib
 * @property Tournament_model $tournament_model
 */
trait player_tournament_module{

	public function tournament($action, $additional=null)
	{
		if(!$this->initApi()){
			return;
		}
		$this->load->library(['playerapi_lib','tournament_lib']);
		$this->load->model(['tournament_model', 'playerapi_model', 'player_model']);
		$request_method = $this->input->server('REQUEST_METHOD');
		$additional = $additional ? trim($additional, "\t\n\r\0\x0B\x2C") : null;

		switch ($action) {
			case 'list':
				if($request_method == 'GET') {
					return $this->getTournamentList();
				}
                break;
            case 'detail':
                if($request_method == 'GET') {
                    return $this->getTournamentDetail($additional);
                }
				break;
            case 'apply':
                if($request_method == 'GET'){
                    if($additional == 'history'){
                        return $this->getTournamentApplyHistory($this->player_id);
                    }elseif($additional == 'summary'){
                        return $this->getTournamentApplySummary($this->player_id);
                    }
                }elseif($request_method == 'POST'){
                    return $this->applyTournament($this->player_id);
                }
                break;
            case 'player-event-rank':
                if($request_method == 'GET') {
                    return $this->getPlayerEventRank($additional, $this->player_id);
                }
                break;
            case 'game' :
                if($additional == 'list'){
                    if($this->_isPostMethodRequest()) {
                        // return $this->currencySpecialGameList($additional);
                        return $this->tournamentGameList($additional);
                    }
                }
                break;
            case 'rank-list' :
                if($request_method == 'GET') {
                    return $this->getTournamentRankList();
                }
                break;
		}
		return $this->returnErrorWithCode(Playerapi::CODE_GENERAL_CLIENT_ERROR);
	}

    public function getTournamentApplyHistory($playerId)
    {
        $result = ['code' => Playerapi::CODE_OK];
		try{
			$request_body = $this->playerapi_lib->getRequestPramas();
			$validate_fields = [
				['name' => 'limit', 'type' => 'int', 'required' => false, 'length' => 0],
				['name' => 'page', 'type' => 'int', 'required' => false, 'length' => 0],
			];
			$is_validate_basic_passed = $this->playerapi_lib->validParmasBasic($request_body, $validate_fields);

			if (!$is_validate_basic_passed['validate_flag']) {
				throw new \APIException($is_validate_basic_passed['validate_msg'], Playerapi::CODE_INVALID_PARAMETER);
			}

			$currency = !empty($request_body['currency']) ? $request_body['currency'] : $this->currency;
			$limit = (isset($request_body['limit'])) ? $request_body['limit'] : 10;
			$page = (isset($request_body['page'])) ? $request_body['page'] : 1;

            $output = $this->playerapi_lib->switchCurrencyForAction($currency, function() use ($playerId, $limit, $page, $currency) {
                $tournamentApplyHistoryCacheKey = "tournamentApplyHistoryCacheKey-$currency-$playerId";
				$tournamentApplyHistoryCacheResult = $this->utils->getJsonFromCache($tournamentApplyHistoryCacheKey);

				if (!empty($tournamentApplyHistoryCacheResult)) {
					$this->comapi_log(__METHOD__, ['cached_result' => $tournamentApplyHistoryCacheResult]);
					$applyHistory = $tournamentApplyHistoryCacheResult;
				} else {
                    $applyHistory = $this->tournament_model->getTournamentApplyHistoryPagination($playerId, $limit, $page);
                    $applyHistory['list'] = array_map(function($history) use ($currency){
                        return [
                            'tournamentCode' => $this->tournament_lib->getCombineCode($currency, $history['tournamentId']),
                            'tournamentName' => $history['tournamentName'],
                            'scheduleCode' => $this->tournament_lib->getCombineCode($currency, $history['scheduleId']),
                            'scheduleName' => $history['scheduleName'],
                            'eventCode' => $this->tournament_lib->getCombineCode($currency, $history['eventId']),
                            'eventName' => $history['eventName'],
                            'applyTime' => $this->playerapi_lib->formatDateTime($history['applyTime']),
                            'distributionTime' => $this->_getDistributionTime($history['distributionTime'], $history['contestEndedAt']),
                            'tournamentBonusAmount' => $this->tournament_lib->getScheduleTotalBonusByType($history['scheduleId'], $history['bonusType']),
                            'rank' => isset($history['rank']) ? $history['rank'] : 0,
                            'currency' => strtoupper($currency),
                            'playerBonusAmount' => $history['bonusAmount'],
                            'tournamentStatus' => $this->tournament_lib->getScheduleStatus($history['contestStartedAt'], $history['contestEndedAt']),
                        ];
                    }, $applyHistory['list']);

					$ttl = 2 * 60;
					$this->utils->saveJsonToCache($tournamentApplyHistoryCacheKey, $applyHistory, $ttl);
				}
                return $applyHistory;
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

    public function getTournamentApplySummary($playerId)
    {
        $result = ['code' => Playerapi::CODE_OK];
        try{
            $request_body = $this->playerapi_lib->getRequestPramas();
            $validate_fields = [
                ['name' => 'currency', 'type' => 'string', 'required' => false, 'length' => 3],
            ];
            $is_validate_basic_passed = $this->playerapi_lib->validParmasBasic($request_body, $validate_fields);

            if (!$is_validate_basic_passed['validate_flag']) {
                throw new \APIException($is_validate_basic_passed['validate_msg'], Playerapi::CODE_INVALID_PARAMETER);
            }

            $currency = !empty($request_body['currency']) ? $request_body['currency'] : $this->currency;
            $output = $this->playerapi_lib->switchCurrencyForAction($currency, function() use ($playerId, $currency) {
                $applySummary = $this->tournament_model->getTournamentApplySummary($playerId);
                $applySummary['currency'] = strtoupper($currency);
                return $applySummary;
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

    public function getTournamentList()
    {
        try {
			$validateFields = [
				['name' => 'page', 'type' => 'int', 'required' => false, 'length' => 0],
				['name' => 'limit', 'type' => 'int', 'required' => false, 'length' => 0]
			];
			$requestBody = $this->playerapi_lib->getRequestPramas();
			$isValidateBasicPassed = $this->playerapi_lib->validParmasBasic($requestBody, $validateFields);
			if($isValidateBasicPassed['validate_flag']){
                $currency = !empty($requestBody['currency'])? $requestBody['currency'] : $this->utils->getConfig('fallback_target_db');
				$page = !empty($requestBody['page'])? $requestBody['page'] : 1; //default 1
				$limit = !empty( $requestBody['limit'])? $requestBody['limit'] : 10; //default 10
				$output = $this->playerapi_lib->switchCurrencyForAction($currency, function() use ($currency, $page, $limit){
					$conditions = [
                        //default condition
                        'tournamentStatus' => Tournament_model::STATUS_ACTIVE,
                        'scheduleStatus' => Tournament_model::STATUS_ACTIVE,
                        'activeTournamentDate' => $this->utils->getNowForMysql(),
                        'currency' => strtoupper($currency),
                    ];
                    $orderby = ['order','tournamentStartedAt'];
					$tournamentData = $this->tournament_model->getTournamentAPIListPagination($limit, $page, $conditions, $orderby);
                    $result = [
                        "totalRecordCount" => $tournamentData['totalRecordCount'],
                        "totalPages" => $tournamentData['totalPages'],
                        "totalRowsCurrentPage" => $tournamentData['totalRowsCurrentPage'],
                        "currentPage" => $tournamentData['currentPage'],
                        'list' => [],
                    ];
                    if(!empty($tournamentData['list']) && is_array($tournamentData['list'])){
                        foreach ($tournamentData['list'] as $tournament) {     
                            $result['list'][] = [
                                'tournamentCode' => $this->tournament_lib->getCombineCode($tournament['currency'], $tournament['tournamentId']),
                                'currency' => $tournament['currency'],
                                'order' => $tournament['order'],
                                'tournamentName' => $tournament['tournamentName'],
                                'scheduleCode' => $this->tournament_lib->getCombineCode($tournament['currency'], $tournament['scheduleId']),
                                'scheduleName' => $tournament['scheduleName'],
                                'applyCount' => $this->tournament_lib->getScheduleApplyCount($tournament['scheduleId']),
                                'totalBonus' => $this->tournament_lib->getScheduleTotalBonusByType($tournament['scheduleId'], $tournament['bonusType']),
                                'applyStartedAt' => $this->playerapi_lib->formatDateTime($tournament['applyStartedAt']),
                                'applyEndedAt' => $this->playerapi_lib->formatDateTime($tournament['applyEndedAt']),
                                'contestStartedAt' => $this->playerapi_lib->formatDateTime($tournament['contestStartedAt']),
                                'contestEndedAt' => $this->playerapi_lib->formatDateTime($tournament['contestEndedAt']),
                                'tournamentStatus' => $this->tournament_lib->getScheduleStatus($tournament['contestStartedAt'], $tournament['contestEndedAt']),
                                'banner' => $this->tournament_lib->getBannerImgPath($tournament['banner']),
                                'icon' => $this->tournament_lib->getIconImgPath($tournament['icon']),
                            ];
                        }
                    }
                    return $result;
				});
				$result['code'] = Playerapi::CODE_OK;
				$result['data'] = $this->playerapi_lib->convertOutputFormat($output);
				return $this->returnSuccessWithResult($result);
			}
			throw new APIException($isValidateBasicPassed['validate_msg'], self::CODE_INVALID_PARAMETER);
        }
        catch (\APIException $ex) {
            $result['code'] = $ex->getCode();
			$result['errorMessage']= $ex->getMessage();
            $this->comapi_log(__METHOD__, 'APIException', $result);

            return $this->returnErrorWithResult($result);
        }
    }

    public function getTournamentDetail($scheduleCode)
    {
        try {
            $expansionCode = $this->tournament_lib->getExpansionCode($scheduleCode);
            list($currency, $scheduleId) = [null, null];
            if(!empty($expansionCode)){
                list($currency, $scheduleId) = $expansionCode;
            }
            $validCurrency = $this->utils->isAvailableCurrencyKey($currency, false);
            $output = [];
            if(!empty($currency) && !empty($scheduleId) && $validCurrency){
                $output = $this->playerapi_lib->switchCurrencyForAction($currency, function() use ($currency, $scheduleId){
                    $conditions['scheduleId'] = $scheduleId;
                    $conditions = [
                        //default condition
                        'scheduleStatus' => Tournament_model::STATUS_ACTIVE,
                        'tournamentStatus' => Tournament_model::STATUS_ACTIVE,
                        'eventStatus' => Tournament_model::STATUS_ACTIVE,
                        'scheduleId' => $scheduleId,
                    ];
                    $scheduleData = $this->tournament_model->getTournamentSchedule($conditions);
                    $result = [];      
                    if(!empty($scheduleData)){
                        //current total bonus based on scheduleId and bonusType
                        $distributionType = $scheduleData['distributionType'];
                        $scheduleTotalBonus = $this->tournament_lib->getScheduleTotalBonusByType($scheduleId, $scheduleData['bonusType']);
                        $scheduleApplyCounts =  $this->tournament_lib->getScheduleApplyCount($scheduleId);
                        $result = [
                            'tournamentCode' => $this->tournament_lib->getCombineCode($currency, $scheduleData['tournamentId']),
                            'scheduleCode' => $this->tournament_lib->getCombineCode($currency, $scheduleId),
                            'currency' => $currency,
                            'scheduleName' => $scheduleData['scheduleName'],
                            'tournamentTemplate' => $scheduleData['tournamentTemplate'],
                            'tournamentType' => $scheduleData['tournamentType'],
                            'applyStartedAt' => $this->playerapi_lib->formatDateTime($scheduleData['applyStartedAt']),
                            'applyEndedAt' => $this->playerapi_lib->formatDateTime($scheduleData['applyEndedAt']),
                            'contestStartedAt' => $this->playerapi_lib->formatDateTime($scheduleData['contestStartedAt']),
                            'contestEndedAt' => $this->playerapi_lib->formatDateTime($scheduleData['contestEndedAt']),
                            'distributionTime' => $this->_getDistributionTime($scheduleData['distributionTime'], $scheduleData['contestEndedAt']),
                            'banner' => $this->tournament_lib->getBannerImgPath($scheduleData['banner']),
                            'icon' => $this->tournament_lib->getIconImgPath($scheduleData['icon']),
                            'tournamentStatus' => $this->tournament_lib->getScheduleStatus($scheduleData['contestStartedAt'], $scheduleData['contestEndedAt'])
                        ];
                        $events = $this->tournament_model->getTournamentEvents($conditions);
                        $result['tournamentEvents'] = [];
                        if(!empty($events)){
                            $conditions['eventId'] = [];
                            foreach ($events as $index => $event) {                            
                                array_push($conditions['eventId'], $event['id']);
                                $counter[$event['id']] = $index;
                                $result['tournamentEvents'][$index] = [
                                    'eventCode' => $this->tournament_lib->getCombineCode($currency, $event['id']),
                                    'eventName' => $event['eventName'],
                                    'targetPlayerType' => $event['targetPlayerType'],
                                    'applyAmount' => $event['registrationFee'],
                                    'applyConditionDepositAmount' =>  $event['applyConditionDepositAmount'],
                                    'applyConditionCountPeriod' => $event['applyConditionCountPeriod'],
                                    'applyConditionCountPeriodStartAt' => $this->playerapi_lib->formatDateTime($event['applyConditionCountPeriodStartAt']),
                                    'applyConditionCountPeriodEndAt' => $this->playerapi_lib->formatDateTime($event['applyConditionCountPeriodEndAt']),
                                    'applyCountThreshold' => $event['applyCountThreshold'],
                                    'applyCount' => $scheduleApplyCounts,
                                    'totalBonus' => $scheduleTotalBonus,
                                ];
                            }                            
                            $ranks = $this->tournament_model->getTournamentRanks($conditions);
                            foreach ($ranks as $rank) {
                                $index = $counter[$rank['eventId']];
                                $result['tournamentEvents'][$index]['rankTier'][] = [
                                    'rankFrom' => $rank['rankFrom'],
                                    'rankTo' => $rank['rankTo'],
                                    'bonus' => $this->tournament_lib->getBonusValueByDistributionType($rank['bonusValue'], $distributionType),
                                    'quota' => $this->tournament_lib->calculateRankQuota($rank['rankFrom'], $rank['rankTo']),
                                ];
                            }
                        }
                    }
                    return $result;
                });
            }      
            $result['code'] = Playerapi::CODE_OK;
            $result['data'] = $this->playerapi_lib->convertOutputFormat($output);
            return $this->returnSuccessWithResult($result);  
        }
        catch (\APIException $ex) {
            $result['code'] = $ex->getCode();
			$result['errorMessage']= $ex->getMessage();
            $this->comapi_log(__METHOD__, 'APIException', $result);

            return $this->returnErrorWithResult($result);
        }
    }	

    protected function getPlayerEventRank($eventCode, $player_id){
        try {
            list($currency, $event_id) = $this->tournament_lib->getExpansionCode($eventCode);
            $output = $this->playerapi_lib->switchCurrencyForAction($currency, function() use ($event_id, $player_id, $currency){
                $result = [
                    "isApply" => tournament_model::PLAYER_APPLY_RECORDS_UNAPPLIED,
                    "rank" => 0,
                    "currency" => $currency,
                    "bonusAmount" => 0,
                    "applyAt" => null,
                ];
                if(!$this->tournament_model->checkEventExist($event_id)){
                    throw new APIException(lang('Event not found'), Playerapi::CODE_TOURNAMENT_EVENT_NOT_FOUND);
                }
                $verify_function_list = [
                    ['name' => 'checkEventPlayer', 'params' => [$event_id, $player_id, 'reverse']],
                ];
                foreach ($verify_function_list as $method) {
                    $this->utils->debug_log('============processDelete verify_function', $method);
                    $verify_result = call_user_func_array([$this, $method['name']], $method['params']);
                    $exec_continue = $this->playerapi_lib->checkIfExecContinueAfterVerify($verify_result);
        
                    if(!$exec_continue) {
                        return $result;
                    }
                }
                $playerEventRank = $this->tournament_model->getPlayerEventRank($event_id, $player_id);
                if(!empty($playerEventRank)){
                    $result['isApply'] = tournament_model::PLAYER_APPLY_RECORDS_APPLIED;
                    // $result['rank'] = empty($playerEventRank['rank']) ? 0 : $playerEventRank['rank'];
                    $result['bonusAmount'] = $playerEventRank['bonusAmount'];
                    $result['applyAt'] = $this->playerapi_lib->formatDateTime($playerEventRank['applyTime']);
                }
                return $result;
            });
            $result['code'] = Playerapi::CODE_OK;
            $result['data'] = $this->playerapi_lib->convertOutputFormat($output);
            return $this->returnSuccessWithResult($result);
        }
        catch (\APIException $ex) {
            $result['code'] = $ex->getCode();
            $result['errorMessage']= $ex->getMessage();
            $this->comapi_log(__METHOD__, 'APIException', $result);

            return $this->returnErrorWithResult($result);
        }
    }

    protected function tournamentGameList($additional = null) {

        try{
            $this->load->library(['game_list_lib', 'language_function']);
            $this->load->model(['game_tags', 'game_description_model', 'external_system']);

			$this->utils->debug_log('tournamentGameList start');
            // $languageIndex = 1;
            // $languageIndex = $this->language_function->getCurrentLanguage();
            $languageIndex = $this->indexLanguage;
            $isoLang = Language_function::ISO2_LANG[$languageIndex];

            $result=['code'=>self::CODE_OK];
            // $request_body = $this->playerapi_lib->getRequestPramas();

			$params=$this->params;
			// if params is empty, set default to empty array
			if(empty($params)){
				$params=[];
			}
            $scheduleCode = $this->getParam('scheduleCode');
            if(empty($scheduleCode)){
                $result=['code'=>self::CODE_INVALID_PARAMETER, 'message'=>'Invalid parameter'];
                return $this->returnErrorWithResult($result);
            }
            # determine if refresh
            $forceDB = $this->getBoolParam('refresh', false);
            $forceCurrentDB = $this->getBoolParam('fdb', false);

            # check cache
            $cache_key = 'playerapi-tournamentGameList-';
            $cache_string = '';

            foreach($params as $key => $val){
                if(is_array($val)){
                    $cache_string .= $key. implode($val);
                }else{
                    $cache_string .= $key.$val;
                }
            }

            if(!empty($cache_string)){
                $cache_key .= md5($cache_string);
            }


            $cachedGamesResult = $this->utils->getJsonFromCache($cache_key);
            if(!empty($cachedGamesResult)&&!$forceDB){
                $result['message'] = lang('Successfully fetched data from cache');
                $result['data'] = $cachedGamesResult;
                return $this->returnSuccessWithResult($result);
            }

            list($currency, $scheduleId) = $this->tournament_lib->getExpansionCode($scheduleCode);
            $tournamentGames = $this->playerapi_lib->switchCurrencyForAction($currency, function() use ($scheduleId) {
                return $this->tournament_model->getTournamentGamesByScheduleId($scheduleId);
            });

            if(empty($tournamentGames)){
                $result=['code'=>playerapi::CODE_TOURNAMENT_EVENT_NOT_FOUND, 'message'=>lang('Tournament not found')];
                return $this->returnErrorWithResult($result);
            }

            $gamePlatformId = json_decode($tournamentGames['gamePlatformId'], true);
            $gameDescriptionId = json_decode($tournamentGames['gameDescriptionId'], true);
            
            $this->utils->debug_log('tournamentGameList ', $params, $gameDescriptionId);
            $superDB = null;
            if(is_array($gamePlatformId) && !empty($gamePlatformId[0])){
                //  not using cache query db
                $params['gamePlatformId']= $gamePlatformId[0];
                $params['gameDescriptionId']= $gameDescriptionId;
                // $superDB = $this->game_description_model->getSuperDBFromMDB();
                $taggedGames = $this->game_tags->getTagGamesList(null, $superDB, $gameDescriptionId);
                # get tagged games
                // $tournamentGameList = $this->tournament_model->getTournamentGamesWithTag(null, $superDB, $gameDescriptionId);
                $respData = $this->tournament_model->getTournamentGameListData($params, $superDB);
                $this->utils->debug_log('tournamentGameList ', $taggedGames, $respData);
            }

            $data = [];
            $gameList = [];

            if(!empty($respData['records'])){

                foreach($respData['records'] as $row){
                    $temp = [];
                    $temp['virtualGamePlatform'] = strval($row['game_platform_id']);
                    // $temp['platformUniqueId'] = $this->utils->getActiveCurrencyKey().'-'.$row['game_platform_id'];
                    // $temp['gameUniqueId'] = (string)$row['external_game_id'];//using external_game_id as game code
                    $temp['virtualGameId'] = $row['game_platform_id'].'-'.$row['external_game_id'];
                    $temp["gameName"] = $row['game_name'];

                    //some game name is in json
                    if(strpos($temp["gameName"], '_json')!==false){
                        $game_name_arr = json_decode(str_replace('_json:', '', $temp["gameName"]),true);
						if(isset($game_name_arr[$languageIndex])){
							$temp["gameName"]=$game_name_arr[$languageIndex];
						}else{
							$temp["gameName"]=$game_name_arr[Language_function::INT_LANG_ENGLISH];
						}
                    }

                    // process tags
                    $temp['tags'] = [];
					foreach($taggedGames as $key => $tagRow){
						if($row['game_description_id']==$tagRow['game_description_id']){
							$temp['tags'][] = $tagRow['tag_code'];
						}
					}



					$this->utils->debug_log('tournamentGameList ', $row, $temp['tags']);
					
					// if tags is empty, try load it from game platform
					if(empty($temp['tags'])){
					}

                    $temp['onlineCount'] = 0;//TO DO add getting online in game level
                    $temp['bonusTag'] = null;//TO DO
                    $temp['pcEnable'] = false;
                    if($row['flash_enabled']==1||$row['html_five_enabled']==1){
                        $temp['pcEnable'] = true;
                    }

                    $temp['mobileEnable'] = false;
                    if(isset($row['mobile_enabled']) && $row['mobile_enabled']==1){
                        $temp['mobileEnable'] = true;
                    }

                    //TO DO add getting online in game level
                    $temp['demoEnable'] = !empty($row['demo_link']);

                    // process iamge path
                    $tempGameImgUrl = $this->game_list_lib->processGameImagePath($row);
                    $temp['gameImgUrl'] = (isset($tempGameImgUrl[$isoLang])?$tempGameImgUrl[$isoLang]:$tempGameImgUrl[Language_function::ISO2_LANG[Language_function::INT_LANG_ENGLISH]]);

                    if(in_array($row['game_platform_id'], $this->utils->getConfig('no_game_img_url_game_api_list'))){
                        $temp['gameImgUrl'] = null;
                    }

                    $temp['currencies']=$this->getCurrencyListFromAttributes($row['attributes']);

                    // OGP-31223 start
                    foreach ($temp['currencies'] as $key => $currency) {
                        if (!empty($currency)) {
                            if (!$this->CI->external_system->isFlagShowInSiteInAttributes($temp['virtualGamePlatform'], strtolower($currency))) {
                                unset($temp['currencies'][$key]);
                            }
                        }
                    }
                    // OGP-31223 end

					// $temp['playerImgUrl'] = null;
                    // $temp['underMaintenance']=false;
					$temp['underMaintenance'] = boolval($row['under_maintenance']);

                    $temp['screenMode']='portrait';
                    if(!empty($row['screen_mode'])){
                        if($row['screen_mode']==Game_description_model::SCREEN_MODE_LANDSCAPE){
                            $temp['screenMode']='landscape';
                        }else if($row['screen_mode']==Game_description_model::SCREEN_MODE_PORTRAIT){
                            $temp['screenMode']='portrait';
                        }
                    }

                    $temp['rtp'] = !empty($row['rtp']) ? $row['rtp'] : null;

                    $gameList[] = $temp;
                }
            }

            // OGP-31223 start
            // unset game list that game platform level flag show in site is false
            foreach ($gameList as $key => $list) {
                if (empty($list['currencies'])) {
                    unset($gameList[$key]);
                    $respData['record_count'] = !empty($respData['record_count']) ? $respData['record_count'] - 1: 0;
                    $respData['total_record_count'] = !empty($respData['total_record_count']) ? $respData['total_record_count'] - 1: 0;
                }
            }

            if (empty($gameList)) {
                $respData['total_pages'] = $respData['record_count'] = $respData['total_record_count'] = 0;
            }

            $gameList = array_values($gameList);
            // OGP-31223 end

            $data['totalPages'] = isset($respData['total_pages'])?$respData['total_pages']:0;
            $data['currentPage'] = isset($respData['current_page'])?$respData['current_page']:0;
            $data['totalRowsCurrentPage'] = isset($respData['record_count'])?$respData['record_count']:0;
            $data['totalRecordCount'] = isset($respData['total_record_count'])?$respData['total_record_count']:0;
            $data['list'] = $gameList;
            //cache result
            $this->utils->saveJsonToCache($cache_key, $data, 3600);

            $result['message'] = lang('Success');
            $result['data'] = $data;

            return $this->returnSuccessWithResult($result);

        }catch(\Exception $e){
			$result=['code'=>self::CODE_SERVER_ERROR, 'message'=>'Server error'];
			return $this->returnErrorWithResult($result);
        }
	}

    protected function applyTournament($player_id) {
        $result = ['code' => Playerapi::CODE_OK];
        $validateFields = [
            ['name' => 'eventCode', 'type' => 'string', 'required' => true, 'length' => 0],
        ];
        $request_body = $this->playerapi_lib->getRequestPramas();
        $isValidateBasicPassed = $this->playerapi_lib->validParmasBasic($request_body, $validateFields);
        try{
            if(!$isValidateBasicPassed['validate_flag']){
                throw new \APIException(null, Playerapi::CODE_INVALID_PARAMETER);
            }
                // check eventCode
                $eventCode = $request_body['eventCode'];
                list($currency, $event_id) = $this->tournament_lib->getExpansionCode($eventCode);
                //insert record
                $rlt = $this->playerapi_lib->switchCurrencyForAction($currency, function() use ($event_id, $player_id) {
                    return $this->applyTournamentEvent($event_id, $player_id);
                });
                if(!$rlt['success']){
                    throw new \APIException($rlt['message'], $rlt['code']);
                }
                return $this->returnSuccessWithResult($result);
        }
        catch (\APIException $ex) {
            $result['code'] = $ex->getCode();
            $result['errorMessage']= $ex->getMessage();
            $this->comapi_log(__METHOD__, 'APIException', $result);

            return $this->returnErrorWithResult($result);
        }
    }

    protected function getTournamentRankList(){
        $result = ['code' => Playerapi::CODE_OK];
        try {
			$validateFields = [
				['name' => 'eventCode', 'type' => 'string', 'required' => true, 'length' => 1],
				['name' => 'limit', 'type' => 'int', 'required' => false, 'length' => 0],
                ['name' => 'page', 'type' => 'int', 'required' => false, 'length' => 0]
			];
            $request_body = $this->playerapi_lib->getRequestPramas();
			$isValidateBasicPassed = $this->playerapi_lib->validParmasBasic($request_body, $validateFields);

            if($isValidateBasicPassed['validate_flag']){

                $eventCode = $request_body['eventCode'];
				$page = !empty($request_body['page'])? $request_body['page'] : 1; //default 1
				$limit = !empty( $request_body['limit'])? $request_body['limit'] : 10; //default 10

                $this->utils->debug_log('OGP-32471',"eventCode", $eventCode, "page", $page, "limit", $limit);

                // if(empty($eventCode)){
                //     $result=['code'=>self::CODE_INVALID_PARAMETER, 'message'=>'Invalid parameter'];
                //     return $this->returnErrorWithResult($result);
                // }

                list($currency, $event_id) = $this->tournament_lib->getExpansionCode($eventCode);
                $output = $this->playerapi_lib->switchCurrencyForAction($currency, function() use ($event_id, $currency,$page, $limit){
                    $TournamentRank = $this->tournament_model->getTournamentEventRank($event_id,$limit,$page);
                    
                    $list = $TournamentRank['list'];        

                    ###Temporary solution start
                    foreach($list as $key => $value){
                        if(!isset($value['rank'])){
                            $list[$key]['rank'] = 0;
                        }
                        $list[$key]['username'] = substr_replace($list[$key]['username'], "***", 2, 3);
                    }
                    $TournamentRank['list'] = $list;
                    ###Temporary solution end
                    return $TournamentRank;
                });
                $result['code'] = Playerapi::CODE_OK;
                $result['data'] = $this->playerapi_lib->convertOutputFormat($output);
                return $this->returnSuccessWithResult($result);
            }
            throw new APIException($isValidateBasicPassed['validate_msg'], self::CODE_INVALID_PARAMETER);
        }
        catch (\APIException $ex) {
            $result['code'] = $ex->getCode();
            $result['errorMessage']= $ex->getMessage();
            $this->comapi_log(__METHOD__, 'APIException', $result);

            return $this->returnErrorWithResult($result);
        }
        return $this->returnSuccessWithResult($result);
    }
    
}
