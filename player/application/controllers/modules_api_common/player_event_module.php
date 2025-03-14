<?php

/**
 * uri: /event
 *
 * @property playerapi_lib $playerapi_lib
 * @property Playerapi_model $playerapi_model
 * @property int $indexLanguage
 * @property Multiple_db_model $multiple_db_model
 */
trait player_event_module{


	public function event($action) {
		if(!$this->initApi()){
			return;
		}

		switch($action){
			case 'list':
				return $this->listEvent();
			case 'launch':
				return $this->launchEvent();
		}

		$this->returnErrorWithCode(self::CODE_GENERAL_CLIENT_ERROR);
	}

	protected function listEvent(){
		$this->load->model('game_description_model');
		$list=$this->game_description_model->queryEventList();
		$result=['code'=>self::CODE_OK, 'data'=>['list'=>$list]];
		return $this->returnSuccessWithResult($result);
	}

	protected function launchEvent() {
        try{
            // parameters validation
            $validate_fields = [
                ['name' => 'virtualEventId', 'type' => 'string', 'required' => true, 'length' => 0],
                ['name' => 'platform', 'type' => 'string', 'required' => false, 'length' => 0],
                ['name' => 'redirection', 'type' => 'string', 'required' => false, 'length' => 0],
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

            if(!isset($request_body['virtualEventId']) && empty($request_body['virtualEventId'])){
                return $this->returnErrorWithCode(self::CODE_INVALID_PARAMETER);
            }

            # check if api exist
            $this->load->model(array('game_provider_auth', 'external_system', 'game_description_model', 'player_model'));

            // $gameApiId = (int)$request_body['gamePlatformId'];
			$virtualEventId=$request_body['virtualEventId'];
			list($gameApiId, $gameEventId)=$this->extractVirtualEventId($virtualEventId);
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
            $extra['game_unique_code'] = null;
            $extra['game_event_id'] = $gameEventId;
            $extra['merchant_code'] = '_null';
			$extra['append_target_db']=true;
			$extra['is_redirect']=true;

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
            if(isset($request_body['homeLink']) && !empty($request_body['homeLink'])){
                $extra['logo_link'] = $request_body['homeLink'];
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

}
