<?php

/**
 * uri: /favorite-game, /launch-game, /launch-game-lobby, /random-games, /games
 */
trait player_game_list_module{

	public function gameList($action, $additional=null){
		if(!$this->initApi()){
			return;
		}

		$request_method = $this->input->server('REQUEST_METHOD');

		switch ($action) {
            case 'special':
                if($request_method == 'POST') {
                    return $this->specialGameList($additional);
                }
                break;
            case 'list':
                if($request_method == 'POST') {
                    return $this->searchGameList($additional);
                }
                break;
		}

		$this->returnErrorWithCode(self::CODE_GENERAL_CLIENT_ERROR);

	}

	protected function searchGameList($additional = null) {

        try{
            $this->load->library(['game_list_lib', 'language_function']);
            $this->load->model(['game_tags', 'game_description_model', 'external_system']);

            // $languageIndex = 1;
            // $languageIndex = $this->language_function->getCurrentLanguage();
            $languageIndex = $this->indexLanguage;
            $isoLang = Language_function::ISO2_LANG[$languageIndex];

            $result=['code'=>self::CODE_OK];
            $request_body = $this->playerapi_lib->getRequestPramas();

            # determine if refresh
            $forceDB = false;
            if(isset($request_body['refresh'])&&!empty($request_body['refresh'])&&$request_body['refresh']==true){
                $forceDB = true;
            }

            # check cache
            $cache_key = 'searchGameList';
            $cache_string = '';

            foreach($request_body as $key => $val){
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
            // convert params
            if(!empty($request_body['virtualGamePlatform'])){
                $request_body['gamePlatformId']=$request_body['virtualGamePlatform'];
            }

            # not using cache query db
            $respData = $this->game_description_model->getGameListData($request_body);

            # get tagged games
            $taggedGames = $this->game_tags->getTagGamesList();

            $data = [];
            $gameList = [];

            if(!empty($respData['records'])){
                foreach($respData['records'] as $row){
                    //$gameApiObj = $this->utils->loadExternalSystemLibObject((int)$row['game_platform_id']);

                   $temp = [];
                   $temp['virtualGamePlatform'] = strval($row['game_platform_id']);
                    // $temp['platformUniqueId'] = $this->utils->getActiveCurrencyKey().'-'.$row['game_platform_id'];
                    // $temp['gameUniqueId'] = (string)$row['external_game_id'];//using external_game_id as game code
                    $temp['virtualGameId'] = $row['game_platform_id'].'-'.$row['external_game_id'];
                    $temp["gameName"] = $row['game_name'];

                    //some game name is in json
                    if(strpos($temp["gameName"], '_json')!==false){
                        $game_name_arr = json_decode(str_replace('_json:', '', $temp["gameName"]),true);
                        $temp["gameName"]=$game_name_arr[Language_function::INT_LANG_ENGLISH];
                        if(isset($game_name_arr[$languageIndex])){
                            $temp["gameName"]=$game_name_arr[$languageIndex];
                        }
                    }

                    // process tags
                    $temp['tags'] = [];
                    foreach($taggedGames as $key => $tagRow){
                        if($row['game_description_id']==$tagRow['game_description_id']){
                            $temp['tags'][] = $tagRow['tag_code'];
                        }
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

                    if($this->utils->isEnabledMDB()){
                        $is_mdb = true;
                        $currency=$this->utils->getActiveCurrencyKeyOnMDB();
                    }else{
                        $is_mdb = false;
                        $currency=$this->utils->getActiveCurrencyKey();
                    }
                    $temp['currencies']=[strtoupper($currency)];

                    // OGP-31223 start
                    foreach ($temp['currencies'] as $key => $currency) {
                        if (!empty($currency)) {
                            if ($is_mdb) {
                                if (!$this->CI->external_system->isFlagShowInSiteInAttributes($temp['virtualGamePlatform'], strtolower($currency))) {
                                    unset($temp['currencies'][$key]);
                                }
                            } else {
                                if (!$this->CI->external_system->isFlagShowInSite($temp['virtualGamePlatform'])) {
                                    unset($temp['currencies'][$key]);
                                }
                            }
                        }
                    }
                    // OGP-31223 end

                    // $temp['playerImgUrl'] = null;

                    $temp['screenMode']='portrait';
                    if(!empty($row['screen_mode'])){
                        if($row['screen_mode']==Game_description_model::SCREEN_MODE_LANDSCAPE){
                            $temp['screenMode']='landscape';
                        }else if($row['screen_mode']==Game_description_model::SCREEN_MODE_PORTRAIT){
                            $temp['screenMode']='portrait';
                        }
                    }

                    // $temp['underMaintenance']=false;
                    $temp['underMaintenance'] = boolval($row['under_maintenance']);
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

	protected function specialGameList($additional = null) {

        try{
            $this->load->library(['game_list_lib', 'language_function']);
            $this->load->model(['game_tags', 'external_system']);

            // $languageIndex = 1;
            // $languageIndex = $this->language_function->getCurrentLanguage();
            $languageIndex = $this->indexLanguage;
            $isoLang = Language_function::ISO2_LANG[$languageIndex];

            $specialTagList =$this->utils->getConfig('player_center_api_special_game_tags');

            //$specialTagList = ['original', 'popular', 'epic', 'recent'];

            $tagCode = '_all_';
            $forceDB = false;

            $result=['code'=>self::CODE_OK];
            $request_body = $this->playerapi_lib->getRequestPramas();

            if(isset($request_body['tag'])&&!empty($request_body['tag'])){
                $tagCode = $request_body['tag'];
            }else{
                $tagCode = $specialTagList;
            }

            if($request_body['tag']=='_all_'||!in_array($request_body['tag'],$specialTagList)){
                $tagCode = $specialTagList;
            }

            //determine if refresh
            if(isset($request_body['refresh'])&&!empty($request_body['refresh'])&&$request_body['refresh']==true){
                $forceDB = true;
            }

            //get game list with tag
            $hashkey = '';
            if(!is_array($tagCode)){
                $hashkey = md5($tagCode);
            }else{
                $hashkey = implode('',$tagCode);
                $hashkey = md5($hashkey);
            }
            $cache_key_games='playerapi-game-speciallist-games-'.$hashkey;
            $cachedGamesResult = $this->utils->getJsonFromCache($cache_key_games);
            if(!empty($cachedGamesResult)&&!$forceDB){
                $result['data'] = $cachedGamesResult;
                return $this->returnSuccessWithResult($result);
            }

            //get tagged games
            $taggedGames = $this->game_tags->getTagGamesList($specialTagList);

            $data = [];
            $data['totalCount'] = 0;
            $data['list'] = [];
            $gameList = [];

            //get games tagged
            $gamesTagged = $this->game_tags->getGamesWithTag($tagCode);

            foreach($gamesTagged as $row){

                // $gameApiObj = $this->utils->loadExternalSystemLibObject((int)$row['game_platform_id']);

                $temp = [];
                $temp['virtualGamePlatform'] = strval($row['game_platform_id']);
                // $temp['gamePlatformId'] = (int)$row['game_platform_id'];
                // $temp['platformUniqueId'] = $this->utils->getActiveCurrencyKey().'-'.$row['game_platform_id'];
                // $temp['gameUniqueId'] = (string)$row['external_game_id'];//using external_game_id as game code
                $temp['virtualGameId'] = $row['game_platform_id'].'-'.$row['external_game_id'];
                // $temp['virtualGamePlatform '] = (string)$row['game_platform_id'];
                $temp["gameName"] = $row['game_name'];

                //some game name is in json
                if(strpos($temp["gameName"], '_json')!==false){
                    $game_name_arr = json_decode(str_replace('_json:', '', $temp["gameName"]),true);
                    $temp["gameName"]=$game_name_arr[Language_function::INT_LANG_ENGLISH];
                    if(isset($game_name_arr[$languageIndex])){
                        $temp["gameName"]=$game_name_arr[$languageIndex];
                    }
                }

                // process tags
                $temp['tags'] = [];
                foreach($taggedGames as $key => $tagRow){
                    if($row['game_description_id']==$tagRow['game_description_id']){
                        $temp['tags'][] = $tagRow['tag_code'];
                    }
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

                if($this->utils->isEnabledMDB()){
                    $is_mdb = true;
                    $currency=$this->utils->getActiveCurrencyKeyOnMDB();
                }else{
                    $is_mdb = false;
                    $currency=$this->utils->getActiveCurrencyKey();
                }
                $temp['currencies']=[strtoupper($currency)];
                // $temp['playerImgUrl'] = null;

                // OGP-31223 start
                foreach ($temp['currencies'] as $key => $currency) {
                    if (!empty($currency)) {
                        if ($is_mdb) {
                            if (!$this->CI->external_system->isFlagShowInSiteInAttributes($temp['virtualGamePlatform'], strtolower($currency))) {
                                unset($temp['currencies'][$key]);
                            }
                        } else {
                            if (!$this->CI->external_system->isFlagShowInSite($temp['virtualGamePlatform'])) {
                                unset($temp['currencies'][$key]);
                            }
                        }
                    }
                }
                // OGP-31223 end

                $temp['screenMode']='portrait';
                if(!empty($row['screen_mode'])){
                    if($row['screen_mode']==Game_description_model::SCREEN_MODE_LANDSCAPE){
                        $temp['screenMode']='landscape';
                    }else if($row['screen_mode']==Game_description_model::SCREEN_MODE_PORTRAIT){
                        $temp['screenMode']='portrait';
                    }
                }

                // $temp['underMaintenance']=false;
                $temp['underMaintenance'] = boolval($row['under_maintenance']);
                $temp['rtp'] = !empty($row['rtp']) ? $row['rtp'] : null;

                $gameList[] = $temp;

            }

            // OGP-31223 start
            // unset game list that game platform level flag show in site is false
            foreach ($gameList as $key => $list) {
                if (empty($list['currencies'])) {
                    unset($gameList[$key]);
                }
            }

            $gameList = array_values($gameList);
            // OGP-31223 end

            //var_dump($gameList);exit;

            $data['totalCount'] = count($gameList);
            $data['list'] = $gameList;
            //cache result
            $this->utils->saveJsonToCache($cache_key_games, $data, 3600);
            $result['data'] = $data;

            return $this->returnSuccessWithResult($result);

        }catch(\Exception $e){
			$result=['code'=>self::CODE_SERVER_ERROR, 'message'=>'Server error'];
			return $this->returnErrorWithResult($result);
        }
	}
}