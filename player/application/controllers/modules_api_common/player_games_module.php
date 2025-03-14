<?php

/**
 * uri: /favorite-game, /launch-game, /launch-game-lobby, /random-games, /games
 *
 * @property playerapi_lib $playerapi_lib
 * @property Playerapi_model $playerapi_model
 * @property int $indexLanguage
 * @property Multiple_db_model $multiple_db_model
 */
trait player_games_module{
	public function games($action, $additional=null){
		if(!$this->initApi()){
			return;
		}

		$request_method = $this->input->server('REQUEST_METHOD');

		switch ($action) {
			case 'favorite-game':
				if($request_method == 'POST') {
					if($additional=='delete'){
						return $this->removeFavoriteGame();
					}
					else {
						return $this->addFavoriteGame();
					}
				}
				else if($request_method == 'GET') {
					return $this->getPlayerFavoriteGame();
				}
				break;
			case 'search':
				if($request_method == 'GET') {
					return $this->searchGame($additional);
				}
				break;
			// case 'launch-game':
			// 	if($request_method == 'GET') {
			// 		return $this->launchGame($additional);
			// 	}
			// 	break;
			// case 'launch-game-lobby':
			// 	if($request_method == 'GET') {
			// 		return $this->launchGameLobby($additional);
			// 	}
			// 	break;
			case 'random-games':
				if($request_method == 'GET') {
					return $this->randomGames($additional);
				}
				break;
            case 'platform-list':
                if($this->_isGetMethodRequest()) {
                    return $this->gamePlatformList($additional);
                }
                break;
		}

		$this->returnErrorWithCode(self::CODE_GENERAL_CLIENT_ERROR);

	}


	public function game($action, $additional=null){
		if(!$this->initApi()){
			return;
		}

		$request_method = $this->input->server('REQUEST_METHOD');

		switch ($action) {
            case 'launch':
                if($request_method == 'POST') {
                    return $this->gameLaunch($additional);
                }
                break;
            case 'launchLobby':
                if($request_method == 'POST') {
                    return $this->gameLaunchLobby($additional);
                }
                break;
			case 'launchLobbyDemo':
				if($request_method == 'POST') {
					return $this->gameLaunchLobbyDemo($additional);
				}
				break;
			case 'bets':
                if($request_method == 'GET' && $additional == "latest") {
                    return $this->latest_bets();
                }
                break;
            case 'rollers':
                if($request_method == 'GET' && $additional == "high") {
                    return $this->high_rollers($additional);
                }
                break;
            case 'latest':
                if($request_method == 'GET' && $additional == "high") {
                    return $this->latest_high_rollers($additional);
                }
                break;
            case 'launchDemo':
                if($request_method == 'POST') {
                    return $this->gameLaunchDemo($additional);
                }
                break;
			case 'favorite':
				if(empty($additional)){
					if($this->_isPostMethodRequest()){
						// post means add
						// post /game/favorite
						return $this->addFavoriteGame();
					}
				}else if($additional=='delete'){
					if($this->_isPostMethodRequest()){
						// delete
						// post /game/favorite/delete
						return $this->removeFavoriteGame();
					}
				}else if($additional='list'){
					if($this->_isGetMethodRequest()){
						// get /game/favorite/list
						return $this->listFavoriteGame();
					}
				}
				break;
			case 'detail':
				// $gamePlatformId=$this->input->get('gamePlatformId');
				// if(empty($gamePlatformId)){
				// 	return $this->returnErrorWithCode(self::CODE_GAME_PLATFORM_ID_IS_REQUIRED);
				// }
				// $platformUniqueId=$this->input->get('platformUniqueId');
				// if(empty($platformUniqueId)){
				// 	return $this->returnErrorWithCode(self::CODE_GAME_PLATFORM_ID_IS_REQUIRED);
				// }
				$virtualGameId=$this->input->get('virtualGameId');
				list($gamePlatformId, $gameUniqueId)=$this->extractVirtualGameId($virtualGameId);
				// $gameUniqueId=$this->input->get('virtualGameId');
				if(empty($gamePlatformId)){
					return $this->returnErrorWithCode(self::CODE_GAME_PLATFORM_ID_IS_REQUIRED);
				}
				if(empty($gameUniqueId)){
					return $this->returnErrorWithCode(self::CODE_GAME_UNIQUE_ID_IS_REQUIRED);
				}
				return $this->getGameDetail($gamePlatformId, $gameUniqueId);
				break;
			case 'recent':
				if($additional=='list' && $this->_isGetMethodRequest()){
					// show list
					$limit=intval($this->input->get('limit'));
					if(empty($limit)){
						$limit=20;
					}
					if($limit>100){
						return $this->returnErrorWithCode(self::CODE_GENERAL_CLIENT_ERROR);
					}
					return $this->listRecentGame($limit);
				}
				break;
			case 'top':
				if($additional=='player' && $this->_isGetMethodRequest()){
					$limit = intval($this->input->get('limit'));
					$date = $this->input->get('date');
					$tags = $this->input->get('tags');

					if(empty($limit)){
						$limit = 20;
					}
					if(empty($date)){
						$date = $this->utils->getYesterdayForMysql();
					}

                    if (empty($tags) || !is_array($tags)) {
                        $tags = [];
                    }

					return $this->topGamesByPlayers($date, $limit, $tags);
				}
				break;
			case 'nav':
				if($additional=='tags' && $this->_isGetMethodRequest()){
					$default = $this->input->get('default');
					$orderType = $this->input->get('orderType');
					if(empty($orderType)){
						$orderType = "asc";
					}
					return $this->listGameTags($default, $orderType);
				}
				break;
			case 'appearance':
				if($this->_isGetMethodRequest()){
					return $this->listAppearance();
				}
				break;
			case 'big':
				if($additional=='win' && $this->_isGetMethodRequest()){
					$tag = $this->input->get('tag');
					$limit = $this->input->get('limit');
					return $this->high_payout_game($tag, $limit);
				}
				break;
		}

		$this->returnErrorWithCode(self::CODE_GENERAL_CLIENT_ERROR);
	}

	public function game_currency($action, $additional=null){
		if(!$this->initApi()){
			return;
		}

		$this->utils->debug_log('enter game_currency', $action, $this->_isPostMethodRequest());
		switch($action){
			case 'list':
				if($this->_isPostMethodRequest()){
					$gamePlatformId=$this->getParam('virtualGamePlatform');
					$pageNumber=$this->getIntParam('pageNumber', 1);
					$sizePerPage=$this->getIntParam('sizePerPage', 15);
					$gameName=$this->getParam('gameName');
					$gameTypeCode=$this->getParam('gameTypeCode');
					$gameTags=$this->getParam('gameTags');
					$this->utils->debug_log('call currencyGameList', $gamePlatformId, $pageNumber, $sizePerPage, $gameName, $gameTypeCode, $gameTags);
					return $this->currencyGameList($gamePlatformId, $pageNumber, $sizePerPage, $gameName, $gameTypeCode, $gameTags);
				}
				break;
		}

		$this->returnErrorWithCode(self::CODE_GENERAL_CLIENT_ERROR);
	}

	protected function currencyGameList($gamePlatformId, $pageNumber, $sizePerPage, $gameName, $gameTypeCode, $gameTags){
		$this->load->library(['game_list_lib', 'language_function']);
		$this->load->model(['multiple_db_model']);
		$languageIndex=$this->indexLanguage;
		$currencyKey=null;
		$uiOnly=true;
		// $uniqueIdList=null;
		// gamePlatformId, gameTypeCode, gameName, gameTags
		// $languageIndex, $currencyKey=null, $uiOnly=true, $page=null, $limit=null, $gamePlatformId=null, $gameTypeCode=null, $gameTags=null, $gameName=null
		list($list, $totalPages, $currentPage, $totalRowsCurrentPage, $totalCount)=$this->multiple_db_model->queryGameListOverCurrency(
			$languageIndex, $currencyKey, $uiOnly, $pageNumber, $sizePerPage,
			$gamePlatformId, $gameTypeCode, $gameTags, $gameName);

		$result=['code'=>self::CODE_OK, 'data'=>['list'=>$list, 'totalPages'=>$totalPages,
			'currentPage'=>$currentPage, 'totalRowsCurrentPage'=>$totalRowsCurrentPage, 'totalCount'=>$totalCount]];
		return $this->returnSuccessWithResult($result);
	}

	protected function listRecentGame($limit){
		// query recent game of player
		$playerId = $this->player_id;
		$this->load->library(['game_list_lib', 'language_function']);
		$this->load->model(['player_recent_game_model']);
		// $languageIndex = $this->language_function->getCurrentLanguage();
		$languageIndex=$this->indexLanguage;
		$isoLang = Language_function::ISO2_LANG[$languageIndex];
		// $taggedGames = $this->game_tags->getTagGames();
		$list=$this->player_recent_game_model->queryRecentGame($playerId, $limit);
		if(!empty($list)){
			foreach($list as &$row){
				$this->processGameDetail($row, $isoLang);
				if(!empty($row['tags']) && $row['tags']!='[null]'){
					// not null , not empty
					$row['tags'] = $this->utils->decodeJson($row['tags']);
				}else{
					$row['tags'] =[];
				}
			}
		}
		$result=['code'=>self::CODE_OK, 'data'=>['list'=>$list, 'totalCount'=>count($list)]];
		return $this->returnSuccessWithResult($result);
	}

	protected function getGameDetail($gamePlatformId, $gameUniqueId){
		$this->load->library(['game_list_lib', 'language_function']);
		$this->load->model(['game_description_model']);
		$data=$this->game_description_model->getGameDetailBy($gamePlatformId, $gameUniqueId);
		if(empty($data)){
			return $this->returnErrorWithCode(self::CODE_GAME_NOT_FOUND);
		}
		// $languageIndex = $this->language_function->getCurrentLanguage();
		$languageIndex=$this->indexLanguage;
		$isoLang = Language_function::ISO2_LANG[$languageIndex];

		// process detail
		$this->processGameDetail($data, $isoLang);
		if(!empty($data['tags']) && $data['tags']!='[null]'){
			// not null , not empty
			$data['tags'] = $this->utils->decodeJson($data['tags']);
		}else{
			$data['tags'] =[];
		}
		$result=['code'=>self::CODE_OK, 'data'=>$data];
		return $this->returnSuccessWithResult($result);
	}

	protected function processGameDetail(&$row, $isoLang, $taggedGames=null){
		// process name
		if(strpos($row["gameName"], '_json')!==false){
			$lang=$this->utils->extractLangJson($row['gameName']);
			$row['gameName']=$lang[Language_function::ISO2_LANG[Language_function::INT_LANG_ENGLISH]];
			if(isset($lang[$isoLang])){
				$row['gameName']=$lang[$isoLang];
			}
		}
		// $row['gamePlatformId']=intval($row['gamePlatformId']);
		// $row['platformUniqueId'] = $this->utils->getActiveCurrencyKey().'-'.$row['gamePlatformId'];
		if(!empty($taggedGames)){
			// get tags
			$row['tags'] = [];
			foreach($taggedGames as $key => $tagRow){
				if($row['game_description_id']==$tagRow['game_description_id']){
					$row['tags'][] = $tagRow['tag_code'];
				}
			}
		}
		// flags
		$row['pcEnable'] = false;
		if($row['flash_enabled']==1 || $row['html_five_enabled']==1){
			$row['pcEnable'] = true;
		}
		$row['mobileEnable'] = false;
		if($row['mobile_enabled']==1){
			$row['mobileEnable'] = true;
		}
		$row['demoEnable'] = !empty($row['demo_link']);

		$this->load->library(['game_list_lib']);
		// process iamge path
		$tempGameImgUrl = $this->game_list_lib->processGameImagePath([
			'game_platform_id'=>$row['gamePlatformId'],
			'game_code'=>$row['game_code'],
			'external_game_id'=>$row['gameUniqueId'],
			'attributes'=>@$row['attributes'],
		]);
		$row['gameImgUrl'] = isset($tempGameImgUrl[$isoLang]) ? $tempGameImgUrl[$isoLang] : $tempGameImgUrl[Language_function::ISO2_LANG[Language_function::INT_LANG_ENGLISH]];

		if(in_array($row['gamePlatformId'], $this->utils->getConfig('no_game_img_url_game_api_list'))){
			$row['gameImgUrl'] = null;
		}

		if($this->utils->isEnabledMDB()){
			$currency=$this->utils->getActiveCurrencyKeyOnMDB();
		}else{
			$currency=$this->utils->getActiveCurrencyKey();
		}
		$row['currencies']=[strtoupper($currency)];
		// $row['playerImgUrl'] = null;
		$row['underMaintenance'] = boolval(@$row['underMaintenance']);

		$row['onlineCount']=0;
		$row['bonusTag'] = null;
		$row['virtualGamePlatform']=$row['gamePlatformId'];
		$row['virtualGameId']=$row['gamePlatformId'].'-'.$row['gameUniqueId'];
		$row['screenMode']='portrait';
		if(!empty($row['screen_mode'])){
			if($row['screen_mode']==Game_description_model::SCREEN_MODE_LANDSCAPE){
				$row['screenMode']='landscape';
			}else if($row['screen_mode']==Game_description_model::SCREEN_MODE_PORTRAIT){
				$row['screenMode']='portrait';
			}
		}

        $row['rtp'] = !empty($row['rtp']) ? $row['rtp'] : null;

		// unset flash_enabled, mobile_enabled, html_five_enabled, demo_link
		unset($row['flash_enabled'], $row['mobile_enabled'], $row['html_five_enabled'], $row['attributes'],
			$row['demo_link'], $row['game_code'], $row['gamePlatformId'], $row['gameUniqueId'], $row['screen_mode']);
	}

	protected function listFavoriteGame(){
		$this->load->model(['favorite_game_model', 'game_tags']);
		$this->load->library(['game_list_lib', 'language_function']);
		$playerId = $this->player_id;
		// get from query string
		$limit=$this->input->get('limit');
		if(empty($limit)){
			$limit=50;
		}
		$list=$this->favorite_game_model->get_favorite_list($playerId, $limit);

		// $languageIndex = $this->language_function->getCurrentLanguage();
		$languageIndex=$this->indexLanguage;
		$isoLang = Language_function::ISO2_LANG[$languageIndex];
		$taggedGames = $this->game_tags->getTagGamesList();
		foreach($list as &$row){
			$this->processGameDetail($row, $isoLang, $taggedGames);
		}
		$result=['code'=>self::CODE_OK, 'data'=>['list'=>$list, 'totalCount'=>count($list)]];
		return $this->returnSuccessWithResult($result);
	}

	protected function addFavoriteGame() {

		$this->load->library(["game_list_lib", 'language_function']);
		$this->load->model(array('common_token','favorite_game_model','game_description_model', 'player_model', 'external_system'));
		// $languageIndex = 1;
		$languageIndex=$this->indexLanguage;
		$isoLang = Language_function::ISO2_LANG[$languageIndex];

		// parameters validation
		$validate_fields = [
			['name' => 'virtualGameId', 'type' => 'string', 'required' => true, 'length' => 0],
		];

		$result=['code'=>self::CODE_OK];
		$request_body = $this->playerapi_lib->getRequestPramas();
		$this->comapi_log(__METHOD__, '=======request_body', $request_body);
		$is_validate_basic_passed = $this->playerapi_lib->validParmasBasic($request_body, $validate_fields);
		$this->comapi_log(__METHOD__, '=======is_validate_basic_passed', $is_validate_basic_passed);

		if(!$is_validate_basic_passed['validate_flag']) {
			$result['code'] = self::CODE_INVALID_PARAMETER;
			$result['errorMessage']= $is_validate_basic_passed['validate_msg'];
			return $this->returnErrorWithResult($result);
		}

		// get game details
		$virtualGameId=$request_body['virtualGameId'];
		list($gameApiId, $externalGameId)=$this->extractVirtualGameId($virtualGameId);
		// $externalGameId = $request_body['gameUniqueId'];
		// $gameApiId = (int)$request_body['gamePlatformId'];
		$gameImageUrl = null;
		$gameLaunchUrl = null;

		$api = $this->utils->loadExternalSystemLibObject($gameApiId);
		if(empty($api)){
        	return $this->returnErrorWithCode(self::CODE_API_IS_UNAVAILABLE);
        }

		$playerInfo = $this->player_model->getPlayerInfoById($this->player_id, null);
		if(empty($playerInfo)){
			return $this->returnErrorWithCode(self::CODE_PLAYER_NOT_FOUND);
		}

		$game=$this->game_description_model->getGameDetailsByExternalGameIdAndGamePlatform($gameApiId,$externalGameId, true);
		//var_dump($game);
		if(empty($game)){
			$result=['code'=>self::CODE_GAME_UNIQUE_ID_IS_REQUIRED, 'message'=>'Unknown game'];
			return $this->returnErrorWithResult($result);
		}

		$tempGameImgUrl = $this->game_list_lib->processGameImagePath($game);
		$gameImageUrl = (isset($tempGameImgUrl[$isoLang])?$tempGameImgUrl[$isoLang]:$tempGameImgUrl[Language_function::ISO2_LANG[Language_function::INT_LANG_ENGLISH]]);

		$extra = [];
		$extra['game_unique_code'] = $externalGameId;
		$apiResponse = $api->getGotoUrl($playerInfo['username'], $extra);
		if($apiResponse['success']) {
			if(isset($apiResponse['url'])){
				$gameLaunchUrl = $apiResponse['url'];
			}
		}

		$playerId = $this->player_id;
		$insertData = array(
			'player_id' => $playerId,
			'game_platform_id' => $gameApiId,
			'external_game_id' => $externalGameId,
			'game_type_id' => $game['game_type_id'],
			'game_description_id' => $game['game_description_id'],
			'name' => $game['game_name'],
			'image' => $gameImageUrl,
			'url' => $gameLaunchUrl
		);

		// check if already exist
		$isExist = $this->favorite_game_model->exists_by_platform_ext_game_id($playerId,$gameApiId,$externalGameId);
		if($isExist){
			return $this->returnSuccessWithResult($result);
		}

		$success = $this->favorite_game_model->add_to_favorites($insertData);
		if(!$success){
			return $this->returnErrorWithCode(self::CODE_SERVER_ERROR);
		}

		return $this->returnSuccessWithResult($result);
	}

	protected function removeFavoriteGame() {

		// parameters validation
		$validate_fields = [
			['name' => 'virtualGameId', 'type' => 'string', 'required' => true, 'length' => 0],
		];

		$result=['code'=>self::CODE_OK];
		$request_body = $this->playerapi_lib->getRequestPramas();
		$this->comapi_log(__METHOD__, '=======request_body', $request_body);
		$is_validate_basic_passed = $this->playerapi_lib->validParmasBasic($request_body, $validate_fields);
		$this->comapi_log(__METHOD__, '=======is_validate_basic_passed', $is_validate_basic_passed);

		if(!$is_validate_basic_passed['validate_flag']) {
			$result['code'] = self::CODE_INVALID_PARAMETER;
			$result['errorMessage']= $is_validate_basic_passed['validate_msg'];
			return $this->returnErrorWithResult($result);
		}

		// get game details
		// $externalGameId = $request_body['gameUniqueId'];
		// $gameApiId = (int)$request_body['gamePlatformId'];
		$virtualGameId=$request_body['virtualGameId'];
		list($gameApiId, $externalGameId)=$this->extractVirtualGameId($virtualGameId);

		// prepare data so save in favorites
		$this->load->model(array('common_token','favorite_game_model'));

		$playerId = $this->player_id;
		$isExist = $this->favorite_game_model->exists_by_platform_ext_game_id($playerId,$gameApiId,$externalGameId);
		if(!$isExist){
			$result=['code'=>self::CODE_SERVER_ERROR, 'message'=>'Unknown game'];
			return $this->returnErrorWithResult($result);
		}

		$success = $this->favorite_game_model->remove_by_platform_ext_game_id($playerId, $gameApiId, $externalGameId);
		if(!$success){
			return $this->returnErrorWithCode(self::CODE_SERVER_ERROR);
		}

		return $this->returnSuccessWithResult($result);
	}

	protected function searchGame($additional = null) {
		$this->load->library(["game_list_lib", 'language_function']);

		// $languageIndex = 1;
		$languageIndex=$this->indexLanguage;
		$isoLang = Language_function::ISO2_LANG[$languageIndex];

		$sortColumnList = [
			'gameName'=>'game_description.game_name',
			'gameApiId'=>'game_description.game_platform_id',
			'gameTypeId'=>'game_description.game_type_id'
		];

		// parameters validation
		$validate_fields = [
			//['name' => 'favorite', 'type' => 'boolean', 'required' => false, 'length' => 0],
			//['name' => 'featured', 'type' => 'boolean', 'required' => false, 'length' => 0],
			['name' => 'gameApiId', 'type' => 'int', 'required' => false, 'length' => 0],
			['name' => 'gameName', 'type' => 'string', 'required' => false, 'length' => 0],
			['name' => 'gameTypeId', 'type' => 'int', 'required' => false, 'length' => 0],
			['name' => 'limit', 'type' => 'int', 'required' => false, 'length' => 0],
			//['name' => 'mobile', 'type' => 'boolean', 'required' => false, 'length' => 0],
			['name' => 'page', 'type' => 'int', 'required' => false, 'length' => 0],
			['name' => 'sort', 'type' => 'string', 'required' => false, 'length' => 0],
			//['name' => 'web', 'type' => 'boolean', 'required' => false, 'length' => 0],
		];

		$result=['code'=>self::CODE_OK];
		$request_body = $this->playerapi_lib->getRequestPramas();
		$this->comapi_log(__METHOD__, '=======request_body', $request_body);
		$is_validate_basic_passed = $this->playerapi_lib->validParmasBasic($request_body, $validate_fields);
		$this->comapi_log(__METHOD__, '=======is_validate_basic_passed', $is_validate_basic_passed);


		if(!$is_validate_basic_passed['validate_flag']) {
			$result['code'] = self::CODE_INVALID_PARAMETER;
			$result['errorMessage']= $is_validate_basic_passed['validate_msg'];
			return $this->returnErrorWithResult($result);
		}

		$playerId = $this->player_id;

		$this->load->model(array('common_token','game_description_model','favorite_game_model'));

		//get favorites
		$playerFavorites = $this->favorite_game_model->get_player_favorites($playerId);
		//var_dump($playerFavorites);

		//get game description data
		$table = 'game_description';
		$select = 'game_description.*, external_system.system_code game_api_system_code, external_system.status game_api_status, game_type.game_type game_type_name';
		$where = "game_description.`status` = 1 AND game_description.`flag_show_in_site` = 1 AND game_type.`game_type` not like '%unknown%' ";

		$joins = [
			'external_system'=>'external_system.id=game_description.game_platform_id',
			'game_type'=>'game_type.id=game_description.game_type_id',
		];

		if(isset($request_body['favorite']) && !empty($playerId)){
			$favoriteGameDescriptionIds = array_column($playerFavorites, 'game_description_id');

			if(empty($favoriteGameDescriptionIds)){
				$favoriteGameDescriptionIds = [0];
			}

			if($request_body['favorite']=='true'){
				$where .=  " AND game_description.id in ('".implode("','", $favoriteGameDescriptionIds)."')";
			}elseif($request_body['favorite']=='false'){
				$where .=  " AND game_description.id not in ('".implode("','", $favoriteGameDescriptionIds)."')";
			}
		}

		if(isset($request_body['featured'])){
			if($request_body['featured']=='true'){
				$where .=  " AND game_description.flag_hot_game = 1";
			}else{
				$where .=  " AND game_description.flag_hot_game = 0";
			}
		}

		if(isset($request_body['gameApiId']) && !empty($request_body['gameApiId'])){
			$where .=  " AND game_description.game_platform_id = ".(int)$request_body['gameApiId'];
		}

		if(isset($request_body['gameName']) && !empty($request_body['gameName'])){
			$where .=  " AND game_description.game_name like '%".$request_body['gameName']."%'";
		}

		if(isset($request_body['gameTypeId']) && !empty($request_body['gameTypeId'])){
			$where .=  " AND game_description.game_type_id = ".(int)$request_body['gameTypeId'];
		}

		if(isset($request_body['mobile'])){
			if($request_body['mobile']=='true'){
				$where .=  " AND game_description.mobile_enabled = 1";
			}else{
				$where .=  " AND game_description.mobile_enabled = 0";
			}
		}

		if(isset($request_body['web'])){
			if($request_body['web']=='true'){
				$where .=  " AND game_description.html_five_enabled = 1";
			}else{
				$where .=  " AND game_description.html_five_enabled = 0";
			}
		}

		$page = isset($request_body['page'])?(int)$request_body['page']:1;
		$limit = isset($request_body['limit']) || !empty($request_body['limit'])?(int)$request_body['limit']:50;
		$group_by = null;
		$order_by = null;

		// process sort
		if(isset($request_body['sort'])){
			preg_match_all('/[A-Za-z0-9]+/', $request_body['sort'], $matches);

			if( isset($matches[0]) && isset($matches[0][0]) && isset($matches[0][1])){
				$sortColumn = $matches[0][0];
				//echo $sortColumn;return;
				//var_dump(array_key_exists($sortColumn, $sortColumnList));
				if(!array_key_exists($sortColumn, $sortColumnList)){
					$sortColumn = '';
				}
				$sortType = strtolower($matches[0][1]);
				if(!in_array($sortType, ["asc","desc"])){
					$sortType = '';
				}

				if(!empty($sortColumn)&&!empty($sortType)){
					$order_by = $sortColumnList[$sortColumn].' ' . $sortType;
				}
			}
		}

		$respData = $this->game_description_model->getDataWithPaginationData($table, $select, $where, $joins, $limit, $page, $group_by, $order_by);

		//generate
		$data = [];
		$data['endRow'] = $respData['last_row'];
		$data['startRow'] = $respData['first_row'];
		$data['hasNextPage'] = $respData['has_next_page'];
		$data['hasPreviousPage'] = $respData['has_prev_page'];
		$data['isFirstPage'] = $respData['is_first_page'];
		$data['isLastPage'] = $respData['is_last_page'];
		$data['navigateFirstPage'] = $respData['first_page'];
		$data['navigateLastPage'] = $respData['end_page'];
		$data['navigatePages'] = $respData['current_page'];
		$data['navigatepageNums'] = $respData['pages'];
		$data['nextPage'] = $respData['next_page'];
		$data['prePage'] = $respData['prev_page'];
		$data['pageNum'] = $respData['current_page'];
		$data['pageSize'] = $respData['record_count'];
		$data['pages'] = $respData['total_pages'];
		$data['size'] =  $respData['record_count'];//??
		$data['total'] = $respData['total_record_count'];//total count of result
		$data['list'] = [];
		$tempRecords = $respData['records'];
		//post process records
		foreach($tempRecords as $row){
			$temp = [];
			$temp['channel'] = [];
			if(isset($row['mobile_enabled']) && $row['mobile_enabled']==1){
				$temp['channel'][] = 'mobile';
			}
			if(isset($row['html_five_enabled']) && $row['html_five_enabled']==1){
				$temp['channel'][] = 'web';
			}

			// process favorite
			$temp['favorite'] = false;
			foreach($playerFavorites as $favoriteRow){
				if(
					$favoriteRow['game_platform_id']==$row['game_platform_id'] &&
					$favoriteRow['game_description_id']==$row['id']
				){
					$temp['favorite'] = true;
				}
			}

			// process featured
			$temp['featured'] = ($row['flag_hot_game']==1?true:false);

			// process game details
			$temp['gameApiCode'] = (string)$row['game_api_system_code'];//to test
			$temp['gameApiId'] = $row['game_platform_id'];//to test
			//$temp['gameCode'] = (string)$row['game_code'];//to test
			$temp['gameCode'] = (string)$row['external_game_id'];//using external_game_id as game code
			$attributes = json_decode($row['attributes'], true);
			//some game launch code not the game_code
			if(isset($attributes['game_launch_code'])){
				//$temp['gameCode'] = (string)$attributes['game_launch_code'];//to test
			}
			$temp["gameName"] = $row['game_name'];
			//some game name is in json
			if(strpos($temp["gameName"], '_json')!==false){
				$game_name_arr = json_decode(str_replace('_json:', '', $temp["gameName"]),true);
				$temp["gameName"]=$game_name_arr[Language_function::INT_LANG_ENGLISH];
				if(isset($game_name_arr[$languageIndex])){
					$temp["gameName"]=$game_name_arr[$languageIndex];
				}
			}

			$temp["gameNameId"] = $row['id'];
			$temp["gameTypeName"] = $row['game_type_name'];
			// some game type name is in json
			if(strpos($temp["gameTypeName"], '_json')!==false){
				$game_name_arr = json_decode(str_replace('_json:', '', $temp["gameTypeName"]),true);
				$temp["gameTypeName"]=$game_name_arr[$languageIndex];
			}

			$temp["releasedDate"] = $row['created_on'];
			$temp["status"] = 0;
			if($row['status']==1&&$row['flag_show_in_site']==1){
				$temp["status"] = 1;
			}
			$temp["userEnabled"] = true;//todo ??

			// process iamge path
			$tempGameImgUrl = $this->game_list_lib->processGameImagePath($row);
			$temp["gameImgUrl"] = (isset($tempGameImgUrl[$isoLang])?$tempGameImgUrl[$isoLang]:$tempGameImgUrl[Language_function::ISO2_LANG[Language_function::INT_LANG_ENGLISH]]);

			// override status if game api is disabled
			// if($row['game_api_status']==0){
				//$temp["gameApiStatus"] = 0;
			// }else{
				//$temp["gameApiStatus"] = 1;
			// }

			$data['list'][] = $temp;
		}


		$success = true;
		if(!$success){
			return $this->returnErrorWithCode(self::CODE_SERVER_ERROR);
		}

		$result['data'] = $data;

		return $this->returnSuccessWithResult($result);
	}

    public function gamePlatformList($additional = null) {


        $this->load->library(['game_list_lib', 'language_function']);
        $this->load->model(['game_tags', 'game_description_model']);
            # not using cache query db

        try{

            // $languageIndex = 1;
            // $languageIndex = $this->language_function->getCurrentLanguage();
			$languageIndex=$this->indexLanguage;
            $isoLang = Language_function::ISO2_LANG[$languageIndex];

            $result=['code'=>self::CODE_OK];
            $request_body = $this->playerapi_lib->getRequestPramas();

            # determine if refresh
            $forceDB = false;
            if(isset($request_body['refresh'])&&$request_body['refresh']){
                $forceDB = true;
            }

            # check cache
            $cache_key = 'gamePlatformList';
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
                $result['data'] = $cachedGamesResult;
                return $this->returnSuccessWithResult($result);
            }


            $respData = $this->game_description_model->getGamePlatformListData($request_body);

            $data = [];
            $list = [];

			$basePath = $this->utils->getSystemUrl('www');

            if(!empty($respData)){
                foreach($respData as $row){
					$game_platform_id = $row['gamePlatformId'];
                    $temp = [];
                    $temp['name'] = lang($row['systemCode']);
                    //$temp['systemCode'] = $row['systemCode'];
                    $temp['virtualGamePlatform'] = $row['gamePlatformId'];
                    $temp['currencies'] = [];
                    $currency = null;
                    if($this->utils->isEnabledMDB()){
                        $currency=$this->utils->getActiveCurrencyKeyOnMDB();
                    }else{
                        $currency=$this->utils->getActiveCurrencyKey();
                    }
                    $temp['currencies']=[strtoupper($currency)];

                    $temp['tags'] = $this->utils->decodeJson($row['tags']);

					$api = $this->utils->loadExternalSystemLibObject($game_platform_id);
					$dir=$api->getGameImageDirectory();
					$logUrls = $api->getLogoImageUrl();
                    $temp['logoUrl'] = !empty($logUrls)?:$this->utils->getLogoImageUrl($basePath, $dir);



					$temp['launcherMode'] = 'lobbyAndSingle';
					$temp['enabledIframe'] = true;
					$temp['lobbyMode'] = 'postMessage';

					$extraInfo=$row['extra_info'];
                    if(isset($row['live_mode'])&&!$row['live_mode']){
                        $extraInfo=$row['sandbox_extra_info'];
                    }

					if(!empty($extraInfo)){
						//get prefix
						$arr=json_decode($extraInfo, true);
						if(isset($arr['launcher_mode']) && !empty($arr['launcher_mode'])){
							$temp['launcherMode']=$arr['launcher_mode'];
						}
						if(isset($arr['enabled_in_iframe'])){
							$temp['enabledIframe']=$arr['enabled_in_iframe'];
						}

						if(isset($arr['lobby_mode'])){
							$temp['lobbyMode']=$arr['lobby_mode'];
						}
					}
					$this->utils->debug_log('gamePlatformList =====================>', 'temp', $temp);
                    $list[] = $temp;
                }
            }

            //cache result
            $data = $list;
            $ttl = $this->utils->getConfig('playerapi_api_cache_ttl');
            $this->utils->saveJsonToCache($cache_key, $data, $ttl);

            //$result['message'] = lang('Success');
            $result['data'] = $data;

            return $this->returnSuccessWithResult($result);

        }catch(\Exception $e){
			$result=['code'=>self::CODE_SERVER_ERROR, 'message'=>'Server error'];
			return $this->returnErrorWithResult($result);
        }
	}
/*
	protected function launchGame() {

		// parameters validation
		$validate_fields = [
			// ['name' => 'gameApiId', 'type' => 'int', 'required' => true, 'length' => 0],
			['name' => 'virtualGameId', 'type' => 'string', 'required' => true, 'length' => 0],
		];

		$result=['code'=>self::CODE_OK];
		$request_body = $this->playerapi_lib->getRequestPramas();
		$this->comapi_log(__METHOD__, '=======request_body', $request_body);
		$is_validate_basic_passed = $this->playerapi_lib->validParmasBasic($request_body, $validate_fields);
		$this->comapi_log(__METHOD__, '=======is_validate_basic_passed', $is_validate_basic_passed);

		if(!$is_validate_basic_passed['validate_flag']) {
			$result['code'] = self::CODE_INVALID_PARAMETER;
			$result['errorMessage']= $is_validate_basic_passed['validate_msg'];
			return $this->returnErrorWithResult($result);
		}

		if(!isset($request_body['gameApiId']) && empty($request_body['gameApiId'])){
			return $this->returnErrorWithCode(self::CODE_INVALID_PARAMETER);
		}

		$this->load->model(array('game_provider_auth', 'external_system', 'game_description_model', 'player_model'));
		list($gameApiId, $gameUniqueId)=$this->extractVirtualGameId($request_body['virtualGameId']);
		if(empty($gameUniqueId)){
			return $this->returnErrorWithCode(self::CODE_GAME_UNIQUE_ID_IS_REQUIRED);
		}
		// $gameApiId = (int)$request_body['gameApiId'];
		$api = $this->utils->loadExternalSystemLibObject($gameApiId);
		if(empty($api)){
        	return $this->returnErrorWithCode(self::CODE_API_IS_UNAVAILABLE);
        }

		// check if game in maintenance
		if ($this->utils->setNotActiveOrMaintenance($gameApiId)) {
        	return $this->returnErrorWithCode(self::CODE_API_UNDER_MAINTENANCE);
		}

		// check if player is blocked
		if($this->CI->utils->blockLoginGame($this->player_id)){
			return $this->returnErrorWithCode(self::CODE_PLAYER_BLOCKED);
		}

		// get player complete details by player id
		$playerInfo = $this->player_model->getPlayerInfoById($this->player_id);
		if(empty($playerInfo)){
			return $this->returnErrorWithCode(self::CODE_PLAYER_NOT_FOUND);
		}

		// check player exist
		$playerExist = $api->isPlayerExist($playerInfo['username']);

		// if not exist create player
		if (isset($playerExist['exists']) && !$playerExist['exists']) {
			if(!is_null($playerExist['exists'])){
			   $this->createPlayerOnGamePlatform($gameApiId, $this->player_id, $api);
			}
		}

		// check if player is blocked to game
		$blocked = $api->isBlocked($playerInfo['username']);
		if ($blocked) {
			return $this->returnErrorWithCode(self::CODE_PLAYER_BLOCKED);
		}

		// get url from api
		$url = '';
		$extra = [];
		$extra['game_unique_code'] = $gameUniqueId; // = isset($request_body['gameCode'])?$request_body['gameCode']:null;
        $extra['merchant_code'] = '_null';
        $extra['append_target_db']=true;
        $extra['is_redirect']=true;
        $extra['try_get_real_url'] = true;

		//identify what is used in game launch
		$game=$this->game_description_model->getGameDetailsByExternalGameIdAndGamePlatform($gameApiId,$gameUniqueId);
		if(!empty($game)){
			$extra['game_unique_code'] =$game->game_code;
			if(isset($game->attributes) && !empty($game->attributes)){
				$attributes = json_decode($game->attributes, true);
				if(isset($attributes['game_launch_code'])){
					$extra['game_unique_code'] = (string)$attributes['game_launch_code'];//to test
				}
			}
		}

		if(isset($request_body['mode']) && !empty($request_body['mode'])){
			$extra['mode'] = $request_body['mode'];
		}
		if(isset($request_body['language']) && !empty($request_body['language'])){
			$extra['language'] = $request_body['language'];
		}
		if(isset($request_body['platform']) && !empty($request_body['platform'])){
			$extra['platform'] = $request_body['platform'];
		}
		if(isset($request_body['redirection']) && !empty($request_body['redirection'])){
			$extra['redirection'] = $request_body['redirection'];
		}
		if(isset($request_body['tryGetRealUrl']) && !empty($request_body['tryGetRealUrl'])){
			$extra['try_get_real_url'] = $request_body['tryGetRealUrl'];
		}
		if(isset($request_body['homeLink']) && !empty($request_body['homeLink'])){
			$extra['home_link'] = $request_body['homeLink'];
		}
		if(isset($request_body['cashierLink']) && !empty($request_body['cashierLink'])){
			$extra['cashier_link'] = $request_body['cashierLink'];
		}
		if(isset($request_body['onErrorRedirect']) && !empty($request_body['onErrorRedirect'])){
			$extra['on_error_redirect'] = $request_body['onErrorRedirect'];
		}
		if(isset($request_body['isRedirect']) && !empty($request_body['isRedirect'])){
			$extra['is_redirect'] = $request_body['isRedirect'];
		}

		//$apiResponse = $api->queryForwardGame($playerInfo['username'],$extra);
		$apiResponse = $api->getGotoUrl($playerInfo['username'], $extra);
		if($apiResponse['success']) {
			if(isset($apiResponse['url'])){
				$url = $apiResponse['url'];
			}
		}

		// check if url is empty
		if(empty($url)){
			return $this->returnErrorWithCode(self::CODE_EXTERNAL_GAME_API_ERROR);
		}

		$result['data'] = $url;

		return $this->returnSuccessWithResult($result);
	}
*/
/*
	protected function launchGameLobby() {

		// parameters validation
		$validate_fields = [
			['name' => 'virtualGamePlatform', 'type' => 'string', 'required' => true, 'length' => 0],
		];

		$result=['code'=>self::CODE_OK];
		$request_body = $this->playerapi_lib->getRequestPramas();
		$this->comapi_log(__METHOD__, '=======request_body', $request_body);
		$is_validate_basic_passed = $this->playerapi_lib->validParmasBasic($request_body, $validate_fields);
		$this->comapi_log(__METHOD__, '=======is_validate_basic_passed', $is_validate_basic_passed);

		if(!$is_validate_basic_passed['validate_flag']) {
			$result['code'] = self::CODE_INVALID_PARAMETER;
			$result['errorMessage']= $is_validate_basic_passed['validate_msg'];
			return $this->returnErrorWithResult($result);
		}

		if(!isset($request_body['gameApiId']) && empty($request_body['gameApiId'])){
			return $this->returnErrorWithCode(self::CODE_INVALID_PARAMETER);
		}


		$this->load->model(array('game_provider_auth', 'external_system', 'game_description_model', 'game_type_model', 'player_model'));
		$gameApiId = (int)$request_body['virtualGamePlatform'];
		$api = $this->utils->loadExternalSystemLibObject($gameApiId);
		if(empty($api)){
        	return $this->returnErrorWithCode(self::CODE_API_IS_UNAVAILABLE);
        }

		// check if game in maintenance
		if ($this->utils->setNotActiveOrMaintenance($gameApiId)) {
        	return $this->returnErrorWithCode(self::CODE_API_UNDER_MAINTENANCE);
		}

		// check if player is blocked
		if($this->CI->utils->blockLoginGame($this->player_id)){
			return $this->returnErrorWithCode(self::CODE_PLAYER_BLOCKED);
		}

		// get player complete details by player id
		$playerInfo = $this->player_model->getPlayerInfoById($this->player_id);
		if(empty($playerInfo)){
			return $this->returnErrorWithCode(self::CODE_PLAYER_NOT_FOUND);
		}

		// check player exist
		$playerExist = $api->isPlayerExist($playerInfo['username']);

		// if not exist create player
		if (isset($playerExist['exists']) && !$playerExist['exists']) {
			if(!is_null($playerExist['exists'])){
			   $this->createPlayerOnGamePlatform($gameApiId, $this->player_id, $api);
			}
		}

		// check if player is blocked to game
		$blocked = $api->isBlocked($playerInfo['username']);
		if ($blocked) {
			return $this->returnErrorWithCode(self::CODE_PLAYER_BLOCKED);
		}

		// get url from api
		$url = '';
		$extra = [];
		$is_demo_only = false;
		$extra['game_unique_code'] = isset($request_body['gameCode'])?$request_body['gameCode']:null;
		if(isset($request_body['mode']) && !empty($request_body['mode'])){
			$gameTypeId = (int)$request_body['gameTypeId'];
			$gameTypeObj = $this->game_type_model->getGameTypeById($gameTypeId);
			$extra['game_type'] = $gameTypeObj->game_type_code;
		}
		if(isset($request_body['mode']) && !empty($request_body['mode'])){
			$mode = $request_body['mode'];
			if(in_array($mode, ['trial', 'demo'])){
				$is_demo_only = true;
			}
		}
		if(isset($request_body['language']) && !empty($request_body['language'])){
			$extra['language'] = $request_body['language'];
		}
		if(isset($request_body['platform']) && !empty($request_body['platform'])){
			$extra['platform'] = $request_body['platform'];
		}
		if(isset($request_body['redirection']) && !empty($request_body['redirection'])){
			$extra['redirection'] = $request_body['redirection'];
		}
		if(isset($request_body['tryGetRealUrl']) && !empty($request_body['tryGetRealUrl'])){
			$extra['try_get_real_url'] = $request_body['tryGetRealUrl'];
		}
		if(isset($request_body['logoLink']) && !empty($request_body['logoLink'])){
			$extra['logo_link'] = $request_body['logoLink'];
		}
		if(isset($request_body['homeLink']) && !empty($request_body['homeLink'])){
			$extra['home_link'] = $request_body['homeLink'];
		}
		if(isset($request_body['cashierLink']) && !empty($request_body['cashierLink'])){
			$extra['cashier_link'] = $request_body['cashierLink'];
		}
		if(isset($request_body['onErrorRedirect']) && !empty($request_body['onErrorRedirect'])){
			$extra['on_error_redirect'] = $request_body['onErrorRedirect'];
		}

		if($is_demo_only){
			$url = $api->getDemoLobbyUrl();
		}else{
			$url = $api->getPlayerLobbyUrl($playerInfo['username'], null, $extra);
		}

		// check if url is empty
		if(empty($url)){
			return $this->returnErrorWithCode(self::CODE_EXTERNAL_GAME_API_ERROR);
		}

		$result['data'] = $url;

		return $this->returnSuccessWithResult($result);
	}
*/
	protected function randomGames($additional = null) {
		$this->load->library(["game_list_lib", 'language_function']);

		// $languageIndex = 1;
		$languageIndex=$this->indexLanguage;
		$isoLang = Language_function::ISO2_LANG[$languageIndex];

		$sortColumnList = [
			'gameName'=>'game_description.game_name',
			'gameApiId'=>'game_description.game_platform_id',
			'gameTypeId'=>'game_description.game_type_id'
		];

		// parameters validation
		$validate_fields = [
			//['name' => 'count', 'type' => 'int', 'required' => false, 'length' => 0],
		];

		$result=['code'=>self::CODE_OK];
		$request_body = $this->playerapi_lib->getRequestPramas();
		$this->comapi_log(__METHOD__, '=======request_body', $request_body);
		$is_validate_basic_passed = $this->playerapi_lib->validParmasBasic($request_body, $validate_fields);
		$this->comapi_log(__METHOD__, '=======is_validate_basic_passed', $is_validate_basic_passed);


		if(!$is_validate_basic_passed['validate_flag']) {
			$result['code'] = self::CODE_INVALID_PARAMETER;
			$result['errorMessage']= $is_validate_basic_passed['validate_msg'];
			return $this->returnErrorWithResult($result);
		}

		$playerId = $this->player_id;

		$this->load->model(array('common_token','game_description_model','favorite_game_model'));

		//get favorites
		$playerFavorites = $this->favorite_game_model->get_player_favorites($playerId);

		//get game description data
		$table = 'game_description';
		$select = 'game_description.*, external_system.system_code game_api_system_code, external_system.status game_api_status, game_type.game_type game_type_name, game_type.id game_type_id';
		$where = "game_description.`status` = 1 AND game_description.`flag_show_in_site` = 1 AND game_type.`game_type` not like '%unknown%' ";

		$joins = [
			'external_system'=>'external_system.id=game_description.game_platform_id',
			'game_type'=>'game_type.id=game_description.game_type_id',
		];

		$page = isset($request_body['page'])?(int)$request_body['page']:1;
		$limit = isset($request_body['count']) || !empty($request_body['count'])?(int)$request_body['count']:50;
		$group_by = null;
		$order_by = 'rand()';

		$respData = $this->game_description_model->getDataWithPaginationData($table, $select, $where, $joins, $limit, $page, $group_by, $order_by);

		//generate
		$data = [];
		$tempRecords = $respData['records'];
		//post process records
		foreach($tempRecords as $row){
			$temp = [];
			$temp['available'] = true;
			// process featured
			$temp['featured'] = ($row['flag_hot_game']==1?true:false);
			// process game details
			$temp['gameApiCode'] = (string)$row['game_api_system_code'];//to test
			$temp['gameApiId'] = $row['game_platform_id'];//to test
			$temp['gameApiName'] = $row['game_api_system_code'];//to test
			$temp['gameCode'] = (string)$row['game_code'];//to test
			$attributes = json_decode($row['attributes'], true);
			//some game launch code not the game_code
			if(isset($attributes['game_launch_code'])){
				$temp['gameCode'] = (string)$attributes['game_launch_code'];//to test
			}
			// process iamge path
			$tempGameImgUrl = $this->game_list_lib->processGameImagePath($row);
			$temp["gameImgUrl"] = (isset($tempGameImgUrl[$isoLang])?$tempGameImgUrl[$isoLang]:$tempGameImgUrl[Language_function::ISO2_LANG[Language_function::INT_LANG_ENGLISH]]);
			$temp["gameName"] = $row['game_name'];
			//some game name is in json
			if(strpos($temp["gameName"], '_json')!==false){
				$game_name_arr = json_decode(str_replace('_json:', '', $temp["gameName"]),true);
				$temp["gameName"]=$game_name_arr[Language_function::INT_LANG_ENGLISH];
				if(isset($game_name_arr[$languageIndex])){
					$temp["gameName"]=$game_name_arr[$languageIndex];
				}
			}
			$temp["gameNameId"] = $row['id'];
			$temp["gameTypeId"] = $row['game_type_id'];
			$temp["gameTypeName"] = $row['game_type_name'];
			// some game type name is in json
			if(strpos($temp["gameTypeName"], '_json')!==false){
				$game_name_arr = json_decode(str_replace('_json:', '', $temp["gameTypeName"]),true);
				$temp["gameTypeName"]=$game_name_arr[$languageIndex];
			}
			//$temp["gameTypeSub"] = ['id'=>0,'name'=>''];
			$temp["releasedDate"] = $row['created_on'];
			$temp["showInSite"] = ($row['flag_show_in_site']==1?true:false);
			$temp["sort"] = 0;//todo ??
			$temp["status"] = 1;//todo ??
			$temp["userEnabled"] = true;//todo ??

			$data[] = $temp;
		}


		$success = true;
		if(!$success){
			return $this->returnErrorWithCode(self::CODE_SERVER_ERROR);
		}

		$result['data'] = $data;

		return $this->returnSuccessWithResult($result);
	}

    /**
     * https://apidoc.tot.bet/#/game/post_game_launch
     *
     * {
     *   "gamePlatformId": 0,
     *   "gameUniqueId": "string",
     *   "platform": "pc",
     *   "redirection": "iframe",
     *   "tryGetRealUrl": true
     *  }
     */
	protected function gameLaunch() {
        try{
            // parameters validation
            $validate_fields = [
                ['name' => 'virtualGameId', 'type' => 'string', 'required' => true, 'length' => 0],
                ['name' => 'platform', 'type' => 'string', 'required' => false, 'length' => 0],
                ['name' => 'redirection', 'type' => 'string', 'required' => false, 'length' => 0],
                //['name' => 'tryGetRealUrl', 'type' => 'boolean', 'required' => false, 'length' => 0],
            ];

            $result=['code'=>self::CODE_OK];
            $request_body = $this->playerapi_lib->getRequestPramas();

            $this->comapi_log(__METHOD__, '=======request_body', $request_body);
            $is_validate_basic_passed = $this->playerapi_lib->validParmasBasic($request_body, $validate_fields);
            $this->comapi_log(__METHOD__, '=======is_validate_basic_passed', $is_validate_basic_passed);

            # check parameters
            if(!$is_validate_basic_passed['validate_flag']) {
                $result['code'] = self::CODE_INVALID_PARAMETER;
                $result['errorMessage']= $is_validate_basic_passed['validate_msg'];
                return $this->returnErrorWithResult($result);
            }

            if(!isset($request_body['virtualGameId']) && empty($request_body['virtualGameId'])){
                return $this->returnErrorWithCode(self::CODE_INVALID_PARAMETER);
            }

            # check if api exist
			$this->load->model(array('game_provider_auth', 'external_system', 'game_description_model', 'player_model', 'operatorglobalsettings'));

			# check if player tag is not allowed games
			$player_tags = array_column($this->player_model->getPlayerTags($this->player_id), 'tagId');
			$no_game_allowed_tag = json_decode($this->operatorglobalsettings->getSettingJson('no_game_allowed_tag'), true);
			if (!empty($player_tags) && !empty($no_game_allowed_tag)) {
				foreach ($player_tags as $tag) {
					if (in_array($tag, $no_game_allowed_tag)) {
						$result['code'] = self::CODE_NO_PERMISSION;
						$result['errorMessage']= lang('Please download the APP for a better experience!');
						return $this->returnErrorWithResult($result);
					}
				}
			}

            // $gameApiId = (int)$request_body['gamePlatformId'];
			$virtualGameId=$request_body['virtualGameId'];
			list($gameApiId, $gameUniqueCode)=$this->extractVirtualGameId($virtualGameId);
            $api = $this->utils->loadExternalSystemLibObject($gameApiId);
            if(empty($api)){
                return $this->returnErrorWithCode(self::CODE_API_CONFIG_NOT_FOUND);
            }

            # check if api is under maintenance
            if ($this->utils->setNotActiveOrMaintenance($gameApiId)) {
                return $this->returnErrorWithCode(self::CODE_API_UNDER_MAINTENANCE);
            }

            # check if player is blocked
            if($this->CI->utils->blockLoginGame($this->player_id)){
                return $this->returnErrorWithCode(self::CODE_PLAYER_BLOCKED);
            }

            # cehck if player information exist
            $playerInfo = $this->player_model->getPlayerInfoById($this->player_id, null);
            if(empty($playerInfo)){
                return $this->returnErrorWithCode(self::CODE_PLAYER_NOT_FOUND);
            }

            # check if player is blocked to game
            $blocked = $api->isBlocked($playerInfo['username']);
            if ($blocked) {
                return $this->returnErrorWithCode(self::CODE_PLAYER_BLOCKED);
            }

            # check player exist
            $playerExist = $api->isPlayerExist($playerInfo['username']);

            # if not exist create player
            if (isset($playerExist['exists']) && !$playerExist['exists']) {
                if(!is_null($playerExist['exists'])){
                $this->createPlayerOnGamePlatform($gameApiId, $this->player_id, $api);
                }
            }

            // get url from api
            $url = '';
            $extra = [];
            $extra['game_unique_code'] = $gameUniqueCode;
            $extra['merchant_code'] = '_null';
			$extra['append_target_db']=true;
			$extra['is_redirect']=true;

            //identify what is used in game launch
            $game=$this->game_description_model->getGameDetailsByExternalGameIdAndGamePlatform($gameApiId,$gameUniqueCode);
            if(!empty($game)){
                $extra['game_type'] = (isset($game->game_type_code)&&!empty($game->game_type_code)?$game->game_type_code:'_null');
                // $extra['game_unique_code'] =$game->game_code;
                // if(isset($game->attributes) && !empty($game->attributes)){
                //     $attributes = json_decode($game->attributes, true);
                //     if(isset($attributes['game_launch_code'])){
                //         $extra['game_unique_code'] = (string)$attributes['game_launch_code'];//to test
                //     }
                // }
            }else{
                return $this->returnErrorWithCode(self::CODE_INVALID_PARAMETER);
            }

            # check if game launch is disabled
            if($game->status==0 &&$game->flag_show_in_site==0){
                return $this->returnErrorWithCode(self::CODE_GAME_LAUNCH_DISABLED);
            }

            ####
            $extra['mode'] = 'real';
            if(isset($request_body['platform']) && !empty($request_body['platform'])){
                $extra['platform'] = $request_body['platform'];
            }
			$extra['try_get_real_url'] = true;
            if(isset($request_body['tryGetRealUrl'])){
                $extra['try_get_real_url'] = $request_body['tryGetRealUrl'];
            }
            if(isset($request_body['language']) && !empty($request_body['language'])){
                $extra['language'] = $request_body['language'];
            }else{
				# retrieve from headers
				$lang=$this->language_function->indexToisoLangCountry($this->indexLanguage);
				$this->utils->debug_log('launch =====================>', 'lang', $lang, 'indexLanguage', $this->indexLanguage);
				if(!empty($lang)){
					$extra['language'] = $lang;
				}
			}

            if(isset($request_body['redirection']) && !empty($request_body['redirection'])){
                $extra['redirection'] = $request_body['redirection'];
            }
            if(isset($request_body['homeLink']) && !empty($request_body['homeLink'])){
                $extra['home_link'] = $request_body['homeLink'];
            }
            if(isset($request_body['cashierLink']) && !empty($request_body['cashierLink'])){
                $extra['cashier_link'] = $request_body['cashierLink'];
            }
            if(isset($request_body['onErrorRedirect']) && !empty($request_body['onErrorRedirect'])){
                $extra['on_error_redirect'] = $request_body['onErrorRedirect'];
            }
            if(isset($request_body['postMessageOnError']) && !empty($request_body['postMessageOnError'])){
                $extra['post_message_on_error'] = $request_body['postMessageOnError'];
            }
            if(isset($request_body['isRedirect']) && !empty($request_body['isRedirect'])){
                $extra['is_redirect'] = $request_body['isRedirect'];
            }
            ####

            //$apiResponse = $api->queryForwardGame($playerInfo['username'],$extra);
            $apiResponse = $api->getGotoUrl($playerInfo['username'], $extra);
            if($apiResponse['success']) {
                if(isset($apiResponse['url'])){
                    $url = $apiResponse['url'];
                }
            }

            # return error if external game error
            if(empty($url)){
                return $this->returnErrorWithCode(self::CODE_EXTERNAL_GAME_API_ERROR);
            }

            $result['data'] = [];
            $result['data']['launchGameUrl'] = $url;
            $result['data']['launchGameHtml'] = null;


            return $this->returnSuccessWithResult($result);
        }catch(\Exception $e){
			$result=['code'=>self::CODE_SERVER_ERROR, 'message'=>'Server error'];
			return $this->returnErrorWithResult($result);
        }



	}

    /**
     * https://apidoc.tot.bet/#/game/post_game_launch_demo
     *
     * {
     *   "gamePlatformId": 0,
     *   "gameUniqueId": "string",
     *   "platform": "pc",
     *   "redirection": "iframe",
     *   "tryGetRealUrl": true
     *  }
     */
	protected function gameLaunchDemo() {
        try{
            // parameters validation
            $validate_fields = [
                ['name' => 'virtualGameId', 'type' => 'string', 'required' => true, 'length' => 0],
                ['name' => 'currency', 'type' => 'string', 'required' => true, 'length' => 0],
                ['name' => 'platform', 'type' => 'string', 'required' => false, 'length' => 0],
                ['name' => 'redirection', 'type' => 'string', 'required' => false, 'length' => 0],
                //['name' => 'tryGetRealUrl', 'type' => 'boolean', 'required' => false, 'length' => 0],
            ];

            $result=['code'=>self::CODE_OK];
            $request_body = $this->playerapi_lib->getRequestPramas();

            $this->comapi_log(__METHOD__, '=======request_body', $request_body);
            $is_validate_basic_passed = $this->playerapi_lib->validParmasBasic($request_body, $validate_fields);
            $this->comapi_log(__METHOD__, '=======is_validate_basic_passed', $is_validate_basic_passed);

            # check parameters
            if(!$is_validate_basic_passed['validate_flag']) {
                $result['code'] = self::CODE_INVALID_PARAMETER;
                $result['errorMessage']= $is_validate_basic_passed['validate_msg'];
                return $this->returnErrorWithResult($result);
            }

            if(!isset($request_body['virtualGameId'])){
                return $this->returnErrorWithCode(self::CODE_INVALID_PARAMETER);
            }

            # check if api exist
            $this->load->model(array('game_provider_auth', 'external_system', 'game_description_model', 'player_model'));
            // $gameApiId = (int)$request_body['gamePlatformId'];
			$virtualGameId=$request_body['virtualGameId'];
			list($gameApiId, $gameUniqueCode)=$this->extractVirtualGameId($virtualGameId);

			$api = $this->utils->loadExternalSystemLibObject($gameApiId);
            if(empty($api)){
                return $this->returnErrorWithCode(self::CODE_API_CONFIG_NOT_FOUND);
            }

            # check if api is under maintenance
            if ($this->utils->setNotActiveOrMaintenance($gameApiId)) {
                return $this->returnErrorWithCode(self::CODE_API_UNDER_MAINTENANCE);
            }

            // get url from api
            $url = '';
            $extra = [];
            $extra['game_unique_code'] = $gameUniqueCode;
            $extra['merchant_code'] = '_null';
			$extra['append_target_db']=true;
			$extra['is_redirect']=true;

            //identify what is used in game launch
            $game=$this->game_description_model->getGameDetailsByExternalGameIdAndGamePlatform($gameApiId,$gameUniqueCode);
            if(!empty($game)){
                $extra['game_type'] = (isset($game->game_type_code)&&!empty($game->game_type_code)?$game->game_type_code:'_null');
                // $extra['game_unique_code'] =$game->game_code;
                // if(isset($game->attributes) && !empty($game->attributes)){
                //     $attributes = json_decode($game->attributes, true);
                //     if(isset($attributes['game_launch_code'])){
                //         $extra['game_unique_code'] = (string)$attributes['game_launch_code'];//to test
                //     }
                // }
            }else{
                return $this->returnErrorWithCode(self::CODE_INVALID_PARAMETER);
            }

            # check if game launch is disabled
            if($game->status==0 &&$game->flag_show_in_site==0){
                return $this->returnErrorWithCode(self::CODE_GAME_LAUNCH_DISABLED);
            }

            ####
            $extra['mode'] = 'demo';
            if(isset($request_body['platform']) && !empty($request_body['platform'])){
                $extra['platform'] = $request_body['platform'];
            }
			$extra['try_get_real_url'] = true;
            if(isset($request_body['tryGetRealUrl'])){
                $extra['try_get_real_url'] = $request_body['tryGetRealUrl'];
            }
            if(isset($request_body['language']) && !empty($request_body['language'])){
                $extra['language'] = $request_body['language'];
            }else{
				# retrieve from headers
				$lang=$this->language_function->indexToisoLangCountry($this->indexLanguage);
				if(!empty($lang)){
					$extra['language'] = $lang;
				}
			}
            if(isset($request_body['redirection']) && !empty($request_body['redirection'])){
                $extra['redirection'] = $request_body['redirection'];
            }
            if(isset($request_body['homeLink']) && !empty($request_body['homeLink'])){
                $extra['home_link'] = $request_body['homeLink'];
            }
            if(isset($request_body['cashierLink']) && !empty($request_body['cashierLink'])){
                $extra['cashier_link'] = $request_body['cashierLink'];
            }
            if(isset($request_body['onErrorRedirect']) && !empty($request_body['onErrorRedirect'])){
                $extra['on_error_redirect'] = $request_body['onErrorRedirect'];
            }
            if(isset($request_body['postMessageOnError']) && !empty($request_body['postMessageOnError'])){
                $extra['post_message_on_error'] = $request_body['postMessageOnError'];
            }
            if(isset($request_body['isRedirect']) && !empty($request_body['isRedirect'])){
                $extra['is_redirect'] = $request_body['isRedirect'];
            }
            ####

            # check if game provider supports demo
            if(!$api->isSupportsDemo()){
                return $this->returnErrorWithCode(self::CODE_GAME_DOES_NOT_SUPPORT_DEMO);
            }

            # check by game type if demo is not supported
            $game_type_demo_not_supported = $api->getGameTypeDemoNotSupported();

            if (!empty($game_type_demo_not_supported)) {
                if (in_array($extra['game_type'], $game_type_demo_not_supported)) {
                    return $this->returnErrorWithCode(self::CODE_GAME_DOES_NOT_SUPPORT_DEMO);
                }
            }

            # check by external_game_id if demo is not supported
            $is_game_support_demo = !empty($game->demo_link) && strtolower($game->demo_link) == 'supported' ? true : false;

            if (!$is_game_support_demo) {
                return $this->returnErrorWithCode(self::CODE_GAME_DOES_NOT_SUPPORT_DEMO);
            }

            //$apiResponse = $api->queryForwardGame($playerInfo['username'],$extra);
            $apiResponse = $api->getGotoUrl(null, $extra);
            if($apiResponse['success']) {
                if(isset($apiResponse['url'])){
                    $url = $apiResponse['url'];
                }
            }

            # return error if external game error
            if(empty($url)){
                return $this->returnErrorWithCode(self::CODE_GAME_DOES_NOT_SUPPORT_DEMO);
            }

            $result['data'] = [];
            $result['data']['launchGameUrl'] = $url;
            $result['data']['launchGameHtml'] = null;


            return $this->returnSuccessWithResult($result);
        }catch(\Exception $e){
			$result=['code'=>self::CODE_SERVER_ERROR, 'message'=>'Server error'];
			return $this->returnErrorWithResult($result);
        }



	}

    /**
     * https://apidoc.tot.bet/#/game/post_game_launch_lobby
     */
	protected function gameLaunchLobby() {
        try{
            // parameters validation
            $validate_fields = [
                ['name' => 'virtualGamePlatform', 'type' => 'string', 'required' => true, 'length' => 0],
                ['name' => 'currency', 'type' => 'string', 'required' => true, 'length' => 0],
                ['name' => 'gameTypeCode', 'type' => 'string', 'required' => false, 'length' => 0],
                ['name' => 'platform', 'type' => 'string', 'required' => false, 'length' => 0],
                ['name' => 'redirection', 'type' => 'string', 'required' => false, 'length' => 0],
                ['name' => 'homeLink', 'type' => 'string', 'required' => false, 'length' => 0],
                ['name' => 'cashierLink', 'type' => 'string', 'required' => false, 'length' => 0],
                ['name' => 'onErrorRedirect', 'type' => 'string', 'required' => false, 'length' => 0],
            ];

            $result=['code'=>self::CODE_OK];
            $request_body = $this->playerapi_lib->getRequestPramas();

            $this->comapi_log(__METHOD__, '=======request_body', $request_body);
            $is_validate_basic_passed = $this->playerapi_lib->validParmasBasic($request_body, $validate_fields);
            $this->comapi_log(__METHOD__, '=======is_validate_basic_passed', $is_validate_basic_passed);

            # check parameters
            if(!$is_validate_basic_passed['validate_flag']) {
                $result['code'] = self::CODE_INVALID_PARAMETER;
                $result['errorMessage']= $is_validate_basic_passed['validate_msg'];
                return $this->returnErrorWithResult($result);
            }

            if(!isset($request_body['virtualGamePlatform']) && empty($request_body['virtualGamePlatform'])){
                return $this->returnErrorWithCode(self::CODE_INVALID_PARAMETER);
            }

            # check if api exist
            $this->load->model(array('game_provider_auth', 'external_system', 'game_description_model', 'player_model', 'operatorglobalsettings'));

			# check if player tag is not allowed games
			$player_tags = array_column($this->player_model->getPlayerTags($this->player_id), 'tagId');
			$no_game_allowed_tag = json_decode($this->operatorglobalsettings->getSettingJson('no_game_allowed_tag'), true);
			if (!empty($player_tags) && !empty($no_game_allowed_tag)) {
				foreach ($player_tags as $tag) {
					if (in_array($tag, $no_game_allowed_tag)) {
						$result['code'] = self::CODE_NO_PERMISSION;
						$result['errorMessage']= lang('Please download the APP for a better experience!');
						return $this->returnErrorWithResult($result);
					}
				}
			}

            $gameApiId = (int)$request_body['virtualGamePlatform'];
            $api = $this->utils->loadExternalSystemLibObject($gameApiId);
            if(empty($api)){
                return $this->returnErrorWithCode(self::CODE_API_CONFIG_NOT_FOUND);
            }

            # check if api is under maintenance
            if ($this->utils->setNotActiveOrMaintenance($gameApiId)) {
                return $this->returnErrorWithCode(self::CODE_API_UNDER_MAINTENANCE);
            }

            # check if player is blocked
            if($this->CI->utils->blockLoginGame($this->player_id)){
                return $this->returnErrorWithCode(self::CODE_PLAYER_BLOCKED);
            }

            # cehck if player information exist
            $playerInfo = $this->player_model->getPlayerInfoById($this->player_id, null);
            if(empty($playerInfo)){
                return $this->returnErrorWithCode(self::CODE_PLAYER_NOT_FOUND);
            }

            # check if player is blocked to game
            $blocked = $api->isBlocked($playerInfo['username']);
            if ($blocked) {
                return $this->returnErrorWithCode(self::CODE_PLAYER_BLOCKED);
            }

            # check player exist
            $playerExist = $api->isPlayerExist($playerInfo['username']);

            # if not exist create player
            if (isset($playerExist['exists']) && !$playerExist['exists']) {
                if(!is_null($playerExist['exists'])){
                $this->createPlayerOnGamePlatform($gameApiId, $this->player_id, $api);
                }
            }

            // get url from api
            $url = '';
            $extra = [];
            $extra['merchant_code'] = '_null';
            $extra['mode'] = 'real';
			$extra['append_target_db']=true;
            if(isset($request_body['platform']) && !empty($request_body['platform'])){
                $extra['platform'] = $request_body['platform'];
            }
			$extra['try_get_real_url'] = true;
            if(isset($request_body['tryGetRealUrl'])){
                $extra['try_get_real_url'] = $request_body['tryGetRealUrl'];
            }
            if(isset($request_body['language']) && !empty($request_body['language'])){
                $extra['language'] = $request_body['language'];
            }else{
				# retrieve from headers
				$lang=$this->language_function->indexToisoLangCountry($this->indexLanguage);
				if(!empty($lang)){
					$extra['language'] = $lang;
				}
			}
            if(isset($request_body['redirection']) && !empty($request_body['redirection'])){
                $extra['redirection'] = $request_body['redirection'];
            }
            if(isset($request_body['homeLink']) && !empty($request_body['homeLink'])){
                $extra['home_link'] = $request_body['homeLink'];
            }
            if(isset($request_body['cashierLink']) && !empty($request_body['cashierLink'])){
                $extra['cashier_link'] = $request_body['cashierLink'];
            }
            if(isset($request_body['onErrorRedirect']) && !empty($request_body['onErrorRedirect'])){
                $extra['on_error_redirect'] = $request_body['onErrorRedirect'];
            }
            if(isset($request_body['postMessageOnError']) && !empty($request_body['postMessageOnError'])){
                $extra['post_message_on_error'] = $request_body['postMessageOnError'];
            }
            if(isset($request_body['gameTypeCode']) && !empty($request_body['gameTypeCode'])){
                $extra['game_type'] = $request_body['gameTypeCode'];
            }

            /* if (!in_array($gameApiId, Game_description_model::GAME_API_WITH_LOBBYS)) {
                return $this->returnErrorWithCode(self::CODE_GAME_DOES_NOT_SUPPORT_LOBBY);
            } */

            if (!$api->isSupportsLobby()) {
                return $this->returnErrorWithCode(self::CODE_GAME_DOES_NOT_SUPPORT_LOBBY);
            }

            $gameTypeLobbySupported = $api->getGameTypeLobbySupported();

            if (isset($extra['game_type']) && !empty($gameTypeLobbySupported)) {
                if (!in_array($extra['game_type'], $gameTypeLobbySupported)) {
                    return $this->returnErrorWithCode(self::CODE_GAME_DOES_NOT_SUPPORT_LOBBY);
                }
            }

			#ezugi_evolution_default_lobby_game_unique_code
			$defaultLobbyGameUniqueCode = $api->getDefaultLobbyGameUniqueCode();
			$extra['game_unique_code'] = $defaultLobbyGameUniqueCode;

            $launchGameExtra = null;

			// use launch game
            $apiResponse = $api->getGotoUrl($playerInfo['username'], $extra);
            if($apiResponse['success']) {
                if(isset($apiResponse['url'])){
                    $url = $apiResponse['url'];
                }

                if(isset($apiResponse['launchGameExtra'])){
                    $launchGameExtra = $apiResponse['launchGameExtra'];
                }
            }
            // $url = $api->getPlayerLobbyUrl($playerInfo['username'], $extra);
            // if(empty($url)){
            //     return $this->returnErrorWithCode(self::CODE_SERVER_ERROR);
            // }

            # return error if external game error
            if(empty($url)){
                return $this->returnErrorWithCode(self::CODE_EXTERNAL_GAME_API_ERROR);
            }

            $result['data'] = [];
            $result['data']['launchGameUrl'] = $url;
            $result['data']['launchGameHtml'] = null;
            $result['data']['launchGameExtra'] = $launchGameExtra;


            return $this->returnSuccessWithResult($result);
        }catch(\Exception $e){
			$result=['code'=>self::CODE_SERVER_ERROR, 'message'=>'Server error'];
			return $this->returnErrorWithResult($result);
        }



	}

	protected function gameLaunchLobbyDemo() {
        try{
            // parameters validation
            $validate_fields = [
                ['name' => 'virtualGamePlatform', 'type' => 'string', 'required' => true, 'length' => 0],
                ['name' => 'currency', 'type' => 'string', 'required' => true, 'length' => 0],
                ['name' => 'gameTypeCode', 'type' => 'string', 'required' => false, 'length' => 0],
                ['name' => 'platform', 'type' => 'string', 'required' => false, 'length' => 0],
                ['name' => 'redirection', 'type' => 'string', 'required' => false, 'length' => 0],
                ['name' => 'homeLink', 'type' => 'string', 'required' => false, 'length' => 0],
                ['name' => 'cashierLink', 'type' => 'string', 'required' => false, 'length' => 0],
                ['name' => 'onErrorRedirect', 'type' => 'string', 'required' => false, 'length' => 0],
            ];

            $result=['code'=>self::CODE_OK];
            $request_body = $this->playerapi_lib->getRequestPramas();
            $extra = [];
            if(isset($request_body['gameTypeCode']) && !empty($request_body['gameTypeCode'])){
                $extra['game_type'] = $request_body['gameTypeCode'];
            }

            $this->comapi_log(__METHOD__, '=======request_body', $request_body);
            $is_validate_basic_passed = $this->playerapi_lib->validParmasBasic($request_body, $validate_fields);
            $this->comapi_log(__METHOD__, '=======is_validate_basic_passed', $is_validate_basic_passed);

            # check parameters
            if(!$is_validate_basic_passed['validate_flag']) {
                $result['code'] = self::CODE_INVALID_PARAMETER;
                $result['errorMessage']= $is_validate_basic_passed['validate_msg'];
                return $this->returnErrorWithResult($result);
            }

            if(!isset($request_body['virtualGamePlatform']) && empty($request_body['virtualGamePlatform'])){
                return $this->returnErrorWithCode(self::CODE_INVALID_PARAMETER);
            }

            # check if api exist
            $this->load->model(array('game_provider_auth', 'external_system', 'game_description_model', 'player_model'));
            $gameApiId = (int)$request_body['virtualGamePlatform'];
            $api = $this->utils->loadExternalSystemLibObject($gameApiId);
            if(empty($api)){
                return $this->returnErrorWithCode(self::CODE_API_CONFIG_NOT_FOUND);
            }

            # check if api is under maintenance
            if ($this->utils->setNotActiveOrMaintenance($gameApiId)) {
                return $this->returnErrorWithCode(self::CODE_API_UNDER_MAINTENANCE);
            }

            # check if player is blocked
            if($this->CI->utils->blockLoginGame($this->player_id)){
                return $this->returnErrorWithCode(self::CODE_PLAYER_BLOCKED);
            }

            if (!$api->isSupportsLobby()) {
                return $this->returnErrorWithCode(self::CODE_GAME_DOES_NOT_SUPPORT_LOBBY);
            }

            $gameTypeLobbySupported = $api->getGameTypeDemoLobbySupported();

            if (isset($extra['game_type']) && !empty($gameTypeLobbySupported)) {
                if (!in_array($extra['game_type'], $gameTypeLobbySupported)) {
                    return $this->returnErrorWithCode(self::CODE_GAME_DOES_NOT_SUPPORT_LOBBY);
                }
            }

            # cehck if player information exist
            // $playerInfo = $this->player_model->getPlayerInfoById($this->player_id);
            // if(empty($playerInfo)){
            //     return $this->returnErrorWithCode(self::CODE_PLAYER_NOT_FOUND);
            // }

            # check if player is blocked to game
            // $blocked = $api->isBlocked($playerInfo['username']);
            // if ($blocked) {
            //     return $this->returnErrorWithCode(self::CODE_PLAYER_BLOCKED);
            // }

            # check player exist
            // $playerExist = $api->isPlayerExist($playerInfo['username']);

            # if not exist create player
            // if (isset($playerExist['exists']) && !$playerExist['exists']) {
            //     if(!is_null($playerExist['exists'])){
            //     $this->createPlayerOnGamePlatform($gameApiId, $this->player_id, $api);
            //     }
            // }

            // get url from api
            $url = '';
            $extra = [];
            $extra['merchant_code'] = '_null';
            $extra['mode'] = 'trial';
			$extra['append_target_db']=true;
            if(isset($request_body['platform']) && !empty($request_body['platform'])){
                $extra['platform'] = $request_body['platform'];
            }
			$extra['try_get_real_url'] = true;
            if(isset($request_body['tryGetRealUrl'])){
                $extra['try_get_real_url'] = $request_body['tryGetRealUrl'];
            }
            if(isset($request_body['language']) && !empty($request_body['language'])){
                $extra['language'] = $request_body['language'];
            }else{
				# retrieve from headers
				$lang=$this->language_function->indexToisoLangCountry($this->indexLanguage);
				if(!empty($lang)){
					$extra['language'] = $lang;
				}
			}
            if(isset($request_body['redirection']) && !empty($request_body['redirection'])){
                $extra['redirection'] = $request_body['redirection'];
            }
            if(isset($request_body['homeLink']) && !empty($request_body['homeLink'])){
                $extra['home_link'] = $request_body['homeLink'];
            }
            if(isset($request_body['cashierLink']) && !empty($request_body['cashierLink'])){
                $extra['cashier_link'] = $request_body['cashierLink'];
            }
            if(isset($request_body['onErrorRedirect']) && !empty($request_body['onErrorRedirect'])){
                $extra['on_error_redirect'] = $request_body['onErrorRedirect'];
            }
            if(isset($request_body['postMessageOnError']) && !empty($request_body['postMessageOnError'])){
                $extra['post_message_on_error'] = $request_body['postMessageOnError'];
            }
            if(isset($request_body['gameTypeCode']) && !empty($request_body['gameTypeCode'])){
                $extra['game_type'] = $request_body['gameTypeCode'];
            }

            $launchGameExtra = [];
            $apiResponse = $api->getGotoUrl(null, $extra);
            if($apiResponse['success']) {
                if(isset($apiResponse['url'])){
                    $url = $apiResponse['url'];
                }

                if(isset($apiResponse['launchGameExtra'])){
                    $launchGameExtra = $apiResponse['launchGameExtra'];
                }
            }

			// $url = $api->getDemoLobbyUrl(null, $extra);
            // if(empty($url)){
            //     return $this->returnErrorWithCode(self::CODE_SERVER_ERROR);
            // }

            # return error if external game error
            if(empty($url)){
                return $this->returnErrorWithCode(self::CODE_EXTERNAL_GAME_API_ERROR);
            }

            $result['data'] = [];
            $result['data']['launchGameUrl'] = $url;
            $result['data']['launchGameHtml'] = null;
            $result['data']['launchGameExtra'] = $launchGameExtra;


            return $this->returnSuccessWithResult($result);
        }catch(\Exception $e){
			$result=['code'=>self::CODE_SERVER_ERROR, 'message'=>'Server error'];
			return $this->returnErrorWithResult($result);
        }



	}

	/**
	 * UTILS FUNCTIONS
	 */
	protected function createPlayerOnGamePlatform($gameApiId, $playerId, $api, $extra= null) {
        $this->utils->debug_log('CREATEPLAYERONGAMEPLATFORM PLAYER =====================>', $gameApiId);

		# LOAD MODEL AND LIBRARIES
		$this->load->model('player_model');
		$this->load->library('salt');

		# GET PLAYER
		$player = $this->player_model->getPlayer(array('playerId' => $playerId));
		# DECRYPT PASSWORD
		$decryptedPwd = $this->salt->decrypt($player['password'], $this->getDeskeyOG());
		if(empty($extra)){
			$extra=[];
		}
		$extra['ip']=$this->utils->getIP();
		# CREATE PLAYER
		$player = $api->createPlayer($player['username'], $playerId, $decryptedPwd, NULL, $extra);

		$this->utils->debug_log('CREATEPLAYERONGAMEPLATFORM PLAYER =====================>['.$gameApiId.']:',$player);

		if ($player['success']) {
			$api->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
		}

	}

	protected function latest_bets() {
        $this->load->model(['player_latest_game_logs']);
		$currency_info = $this->utils->getCurrentCurrency();
		$currencyCode=$currency_info['currency_code'];
        $request_body = $this->playerapi_lib->getRequestPramas();
		$defaultLatestBetLimit = $this->utils->getConfig("default_latest_bet_limit");
		$limit = isset($request_body['limit']) ? $request_body['limit'] : $defaultLatestBetLimit;

		$refreshCache = isset($request_body['refresh']) && $request_body['refresh'] ? $request_body['refresh'] : false;

        $this->comapi_log(__METHOD__, '=======request_body', $request_body);

        $validate_fields = [
            ['name' => 'sortKey', 'type' => 'string', 'required' => false, 'length' => 0],
            ['name' => 'sortType', 'type' => 'string', 'required' => false, 'length' => 0],
        ];

        $is_validate_basic_passed = $this->playerapi_lib->validParmasBasic($request_body, $validate_fields);

        if (!$is_validate_basic_passed['validate_flag']) {
            $result['code'] = self::CODE_INVALID_PARAMETER;
            $result['errorMessage']= $is_validate_basic_passed['validate_msg'];
            return $this->returnErrorWithResult($result);
        }

        $sort_key = !empty($request_body['sortKey']) ? $request_body['sortKey'] : '';
        $sort_type = !empty($request_body['sortType']) ? $request_body['sortType'] : 'desc';

		// try get cache first
		$cache_key='_get_player_latest_game_logs-'.$currencyCode.'-limit-'.$limit;
        $cachedResult = $this->utils->getJsonFromCache($cache_key);
		if(empty($cachedResult) || $refreshCache){
			$this->utils->debug_log('NO CACHE latest_bets');
			$result = $this->player_latest_game_logs->get_player_latest_game_logs($limit, $sort_key, $sort_type);
			// $currency_info = $this->utils->getCurrentCurrency();
			if(!empty($result)){
				array_walk($result, function($value, $key) use (&$result, $currency_info) {
					$_currency_decimals= $currency_info['currency_decimals'];
					$count = strlen($value['playerUsername']);
					$playerUsername = substr_replace($value['playerUsername'], str_repeat('*', $count-5), 1, -4);
					$result[$key]['playerUsername'] = $playerUsername;

					$result[$key]['betTime'] = $this->playerapi_lib->formatDateTime($value["betTime"]);
					$result[$key]['payoutTime'] = $this->playerapi_lib->formatDateTime($value["payoutTime"]);

					// $result[$key]['realBetAmount'] = number_format($value["realBetAmount"], $_currency_decimals);
					// $result[$key]['payoutAmount'] = number_format($value["payoutAmount"], $_currency_decimals);
					// $result[$key]['resultAmount'] = number_format($value["resultAmount"], $_currency_decimals);

                    $result[$key]['realBetAmount'] = floatval($value["realBetAmount"]);
					$result[$key]['payoutAmount'] = floatval($value["payoutAmount"]);
					$result[$key]['resultAmount'] = floatval($value["resultAmount"]);

					if(isset($currency_info['currency_code'])){
						$result[$key]['currency'] = $currency_info['currency_code'];
					}
					$result[$key]["multiplier"] = ($value["multiplier"] == 0 || $value["multiplier"] == null) ? "-" : number_format($value["multiplier"], 2);
					// $result[$key]["platformUniqueId"] = $this->utils->getActiveCurrencyKey().'-'.$value['gamePlatformId'];
				});
			}

			$ttl = $this->utils->getConfig('sync_latest_game_records_cache_ttl');
			$this->utils->saveJsonToCache($cache_key, $result, $ttl);
		}else{
			$result=$cachedResult;
			$this->utils->debug_log('HIT CACHE latest_bets');
		}

        $detail=[
            'code' => self::CODE_OK,
            'data' => array("gameLogs" => $result)
        ];
        return $this->returnSuccessWithResult($detail);
    }

    protected function high_rollers() {
        $this->load->model(array('player_high_rollers_stream'));
		$currency_info = $this->utils->getCurrentCurrency();
		$currencyCode=$currency_info['currency_code'];
		// try get cache first
		$cache_key='_get_player_latest_game_logs_high_rollers-'.$currencyCode;
        $cachedResult = $this->utils->getJsonFromCache($cache_key);
		if(empty($cachedResult)){
			$this->utils->debug_log('NO CACHE high_rollers');
			$result = $this->player_high_rollers_stream->get_player_latest_game_logs_high_rollers();
			// $currency_info = $this->utils->getCurrentCurrency();
			if(!empty($result)){
				array_walk($result, function($value, $key) use (&$result, $currency_info) {
					$_currency_decimals= $currency_info['currency_decimals'];
					$count = strlen($value['playerUsername']);
					$playerUsername = substr_replace($value['playerUsername'], str_repeat('*', $count-5), 1, -4);
					$result[$key]['playerUsername'] = $playerUsername;

					$result[$key]['betTime'] = $this->playerapi_lib->formatDateTime($value["betTime"]);
					$result[$key]['payoutTime'] = $this->playerapi_lib->formatDateTime($value["payoutTime"]);

					$result[$key]['realBetAmount'] = number_format($value["realBetAmount"], $_currency_decimals);
					$result[$key]['payoutAmount'] = number_format($value["payoutAmount"], $_currency_decimals);
					$result[$key]['resultAmount'] = number_format($value["resultAmount"], $_currency_decimals);
					if(isset($currency_info['currency_code'])){
						$result[$key]['currency'] = $currency_info['currency_code'];
					}
					$result[$key]["multiplier"] = ($value["multiplier"] == 0 || $value["multiplier"] == null) ? "-" : number_format($value["multiplier"], 2);
					// $result[$key]["platformUniqueId"] = $this->utils->getActiveCurrencyKey().'-'.$value['gamePlatformId'];
				});
			}

			$ttl = $this->utils->getConfig('sync_latest_game_records_cache_ttl');
			$this->utils->saveJsonToCache($cache_key, $result, $ttl);
		}else{
			$result=$cachedResult;
			$this->utils->debug_log('HIT CACHE high_rollers');
		}
        $detail=[
            'code' => self::CODE_OK,
            'data' => array("gameLogs" => $result)
        ];
        return $this->returnSuccessWithResult($detail);
    }

    protected function latest_high_rollers() {
        $this->load->model(array('player_high_rollers_stream'));
		$currency_info = $this->utils->getCurrentCurrency();
		$currencyCode=$currency_info['currency_code'];
		// try get cache first
		$cache_key='_get_latest_high_rollers-'.$currencyCode;
        $cachedResult = $this->utils->getJsonFromCache($cache_key);
		if(empty($cachedResult)){
			$this->utils->debug_log('NO CACHE latest_high_rollers');
			$result = $this->player_high_rollers_stream->get_latest_high_rollers();
			if(!empty($result)){
				array_walk($result, function($value, $key) use (&$result, $currency_info) {
					$_currency_decimals= $currency_info['currency_decimals'];
					$count = strlen($value['playerUsername']);
					$playerUsername = substr_replace($value['playerUsername'], str_repeat('*', $count-5), 1, -4);
					$result[$key]['playerUsername'] = $playerUsername;

					$result[$key]['betTime'] = $this->playerapi_lib->formatDateTime($value["betTime"]);
					$result[$key]['payoutTime'] = $this->playerapi_lib->formatDateTime($value["payoutTime"]);

					//$result[$key]['realBetAmount'] = number_format($value["realBetAmount"], $_currency_decimals);
					//$result[$key]['payoutAmount'] = number_format($value["payoutAmount"], $_currency_decimals);
					//$result[$key]['resultAmount'] = number_format($value["resultAmount"], $_currency_decimals);

					$result[$key]['realBetAmount'] = floatval($value["realBetAmount"]);
					$result[$key]['payoutAmount'] = floatval($value["payoutAmount"]);
					$result[$key]['resultAmount'] = floatval($value["resultAmount"]);

					if(isset($currency_info['currency_code'])){
						$result[$key]['currency'] = $currency_info['currency_code'];
					}
					$result[$key]["multiplier"] = ($value["multiplier"] == 0 || $value["multiplier"] == null) ? "-" : number_format($value["multiplier"], 2);
				});
			}

			$ttl = $this->utils->getConfig('sync_latest_game_records_cache_ttl');
			$this->utils->saveJsonToCache($cache_key, $result, $ttl);
		}else{
			$result=$cachedResult;
			$this->utils->debug_log('HIT CACHE latest_high_rollers');
		}

        $detail=[
            'code' => self::CODE_OK,
            'data' => array("gameLogs" => $result)
        ];

        return $this->returnSuccessWithResult($detail);
    }

    public function topGamesByPlayers($date, $limit, $tags){
    	$this->load->model(array('total_player_game_day', 'game_description_model'));

		$request_body = $this->playerapi_lib->getRequestPramas();

		# determine if refresh
		$forceDB = false;
		// if(isset($request_body['refresh'])&&$request_body['refresh']){
		// 	$forceDB = true;
		// }

		# check cache
		$cache_key = 'topGamesByPlayers';
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
			$result = [];
			$result['code'] = self::CODE_OK;
			$result['message'] = 'Successfully fetched top game list from cache.';
			$result['data'] = $cachedGamesResult;
			$this->utils->debug_log('topGamesByPlayers from cache', 'cache_key', $cache_key, 'request_body', $request_body);
			return $this->returnSuccessWithResult($result);
		}

		$this->utils->debug_log('topGamesByPlayers not from cache', 'cache_key', $cache_key, 'request_body', $request_body);

        // $results = $this->CI->total_player_game_hour->getTopGamesByPlayers($date, $limit, $tags);
        $results = $this->CI->total_player_game_day->getTopGamesByPlayerBetAndCountDaily($date, $limit, $tags);
        $lists = [];
        if(!empty($results)){
        	foreach ($results as $key => $result) {
        		$gamePlatformId = $result['game_platform_id'];
        		$gameUniqueId = $result['external_game_id'];
        		$data = $this->game_description_model->getGameDetailBy($gamePlatformId, $gameUniqueId);
        		if(empty($data)){
					continue;
				}
				$languageIndex=$this->indexLanguage;
				$isoLang = Language_function::ISO2_LANG[$languageIndex];
        		$this->processGameDetail($data, $isoLang);
				if(!empty($data['tags']) && $data['tags']!='[null]'){
					// not null , not empty
					$data['tags'] = $this->utils->decodeJson($data['tags']);
				}else{
					$data['tags'] =[];
				}
				$data['totalPlayers'] = (int)$result['total_players'];
				$data['totalBets'] = (float)$result['total_bets'];
				$lists[] = $data;
        	}
        }

		$data = ['list'=>$lists];
		$ttl = $this->utils->getConfig('playerapi_api_cache_ttl_for_top_games_player');
		$this->utils->saveJsonToCache($cache_key, $data, $ttl);

        $response = ['code'=>self::CODE_OK, 'message'=>'Success', 'data'=>$data];
		return $this->returnSuccessWithResult($response);
    }

    protected function listGameTags($default, $orderType){
		$playerId = $this->player_id;
		$this->load->library(['game_list_lib', 'language_function']);
		$this->load->model(['game_tags']);
		$languageIndex = $this->indexLanguage;
		$isoLang = Language_function::ISO2_LANG[$languageIndex];
		$list = $this->game_tags->queryGameTagsForNavigation($default, $orderType);
		$landing_page = $this->utils->getOperatorSetting('casino_navigation_landing_page');
		if(!empty($list)){
			foreach($list as &$row){
				if(strpos($row["title"], '_json')!==false){
					$lang=$this->utils->extractLangJson($row['title']);
					$row['title']=$lang[Language_function::ISO2_LANG[Language_function::INT_LANG_ENGLISH]];
					if(isset($lang[$isoLang])){
						$row['title']=$lang[$isoLang];
					}
				}
				if(isset($row['gt_order'])){
					unset($row['gt_order']);
				}

				$row['isDefault']= false;
				if(!empty($landing_page) && $row['tag'] == $landing_page){
					$row['isDefault']= true;
				}
			}
		}
		$result=['code'=>self::CODE_OK, 'data'=>$list];
		return $this->returnSuccessWithResult($result);
	}

	protected function listAppearance(){
		$this->load->library(['language_function']);
		$this->load->model(['game_tags']);
		$languageIndex = $this->indexLanguage;
		$isoLang = Language_function::ISO2_LANG[$languageIndex];
		$tags = $this->game_tags->queryGameTagsForNavigation(null, "asc");
		$landing_page = $this->utils->getOperatorSetting('casino_navigation_landing_page');
		if(!empty($tags)){
			foreach($tags as &$tag){
				if(strpos($tag["title"], '_json')!==false){
					$lang=$this->utils->extractLangJson($tag['title']);
					$tag['title']=$lang[Language_function::ISO2_LANG[Language_function::INT_LANG_ENGLISH]];
					if(isset($lang[$isoLang])){
						$tag['title']=$lang[$isoLang];
					}
				}
				if(isset($tag['gt_order'])){
					unset($tag['gt_order']);
				}

				$tag['isDefault']= false;
				if(!empty($landing_page) && $tag['tag'] == $landing_page){
					$tag['isDefault']= true;
				}
			}
		}

		$data['gameNav'] = array(
			"showFavorites" => $this->utils->getOperatorSetting('casino_navigation_favorite_games_enabled'),
			"showRecents" => $this->utils->getOperatorSetting('casino_navigation_recent_games_enabled'),
			"tags" => $tags
		);

		$result=['code'=>self::CODE_OK, 'data'=>$data];
		return $this->returnSuccessWithResult($result);
	}

	protected function high_payout_game($tag = null, $limit = 10) {
        $this->load->model(array('player_high_rollers_stream', 'game_description_model'));
        $this->load->library(['game_list_lib', 'language_function']);
		$currency_info = $this->utils->getCurrentCurrency();
		$currencyCode=$currency_info['currency_code'];
		// try get cache first
		$cache_key='_get_high_payout_game-'.$currencyCode.$tag.$limit;
        $cachedResult = $this->utils->getJsonFromCache($cache_key);
		if(empty($cachedResult)){
			$this->utils->debug_log('NO CACHE high_payout_game');
			$result = $this->player_high_rollers_stream->get_high_payout_games($tag, $limit);
			$currency_info = $this->utils->getCurrentCurrency();
			if(!empty($result)){
				array_walk($result, function($value, $key) use (&$result, $currency_info) {
					$languageIndex=$this->indexLanguage;
					$isoLang = Language_function::ISO2_LANG[$languageIndex];

					$gameImageUrl = null;
					$virtualGameId=$value['virtualGameId'];
					list($gameApiId, $externalGameId)=$this->extractVirtualGameId($virtualGameId);

					$game=$this->game_description_model->getGameDetailsByExternalGameIdAndGamePlatform($gameApiId,$externalGameId, true);
					if(!empty($game)){
						$tempGameImgUrl = $this->game_list_lib->processGameImagePath($game);
						$gameImageUrl = (isset($tempGameImgUrl[$isoLang])?$tempGameImgUrl[$isoLang]:$tempGameImgUrl[Language_function::ISO2_LANG[Language_function::INT_LANG_ENGLISH]]);
					}

				
					$_currency_decimals= $currency_info['currency_decimals'];
					$count = strlen($value['playerUsername']);
					$playerUsername = substr_replace($value['playerUsername'], str_repeat('*', $count-5), 1, -4);
					$result[$key]['playerUsername'] = $playerUsername;
					$result[$key]['resultAmount'] = number_format($value["resultAmount"], $_currency_decimals);
					if(isset($currency_info['currency_code'])){
						$result[$key]['currency'] = $currency_info['currency_code'];
					}
					$result[$key]['gameImgUrl'] = $gameImageUrl;
				});
			}

			$ttl = $this->utils->getConfig('sync_latest_game_records_cache_ttl');
			$this->utils->saveJsonToCache($cache_key, $result, $ttl);
		}else{
			$result=$cachedResult;
			$this->utils->debug_log('HIT CACHE high_payout_game');
		}
        $detail=[
            'code' => self::CODE_OK,
            'data' => array("gameLogs" => $result)
        ];
        return $this->returnSuccessWithResult($detail);
    }
}
