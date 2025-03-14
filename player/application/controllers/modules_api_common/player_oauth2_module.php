<?php

/**
 * oauth2 of player
 */
trait player_oauth2_module{

	/**
	 * oauth2 service
	 *
	 * /oauth/token
	 * /oauth/token/refresh
	 *
	 * @param string $action
	 * @param string $additional
	 *
	 */

	public function oauth($action, $additional=null){
		if(!$this->initApi()){
			return;
		}

		if (static::API_ACL_RESULT_SUCCESS !== $this->_check_api_acl(__FUNCTION__, 'iframe_login')) {
			$requestBody = $this->playerapi_lib->getRequestPramas();
			$username = !empty($requestBody['username'])? $requestBody['username'] : '';
		
            $this->load->model(['player_model']);
            $this->utils->debug_log('block login on api login', $username, $this->utils->tryGetRealIPWithoutWhiteIP());
            $blockedIp=$this->utils->getIp();
            $blockedRealIp=$this->utils->tryGetRealIPWithoutWhiteIP();
            $succBlocked=$this->player_model->writeBlockedPlayerRecord($username, $blockedIp,
                $blockedRealIp, Player_model::BLOCKED_SOURCE_PLAYER_CENTER_API);
            if(!$succBlocked){
                $this->utils->error_log('write blocked record failed', $username, $blockedIp, $blockedRealIp);
            }
			return $this->returnErrorWithCode(self::CODE_IP_RESTRICTED);
        }

        $errorResponse=null;

        if($action=='phone'){
            $_rlt = $this->_oauthPhone2username();
            if($_rlt['code'] == self::CODE_OK){
                $_POST['username'] = $_rlt['username'];
                $_POST['grant_type'] = 'password';
                $action = 'token';
            }else if($_rlt['code'] = self::CODE_INVALID_PARAMETER){
                $libPlayerOauth2=$this->loadOauth2Lib($errorResponse);
                $response=$libPlayerOauth2->makeInvalidCredentialsResponse($_rlt['errorMessage']);
                $this->returnErrorFromResponse($response);
                return false;
            }else if($_rlt['code'] = self::CODE_INVALID_PARAMETER){
                return $this->returnErrorWithResult($_rlt);
            }
        } // EOF if($action=='phone'...

		/**
		 * @var LibPlayerOauth2 $libPlayerOauth2
		 */
		$libPlayerOauth2=$this->loadOauth2Lib($errorResponse);
		if(empty($libPlayerOauth2)){
			$this->returnErrorFromResponse($errorResponse);
			return;
		}

		$isPost=$this->_isPostMethodRequest();
		$request=$libPlayerOauth2->generatePsr7Request();
		$is_captcha_enabled = ($this->operatorglobalsettings->getSettingJson('login_captcha_enabled'));
		if($is_captcha_enabled){
			$valid_captcha = $this->handleValidateCaptcha();
			if(!$valid_captcha['passed']){
				if(!empty($valid_captcha['code']) && !empty($valid_captcha['error_message'])){
					return $this->returnErrorWithCode($valid_captcha['code'], $valid_captcha['error_message']);
				}else{
					return $this->returnErrorWithCode(self::CODE_INVALID_PARAMETER);
				}
			}
		}
		/**
		 * @var ResponseInterface $response
		 */
		$response=null;
		$success=false;
		$customized_error_code=null;
		if($action=='token'){
			if($isPost){
				if($additional==null){
					// oauth/token
					// login
					$this->load->model(['player_oauth2_model']);
					$success=$this->dbtransOnly(function () use($libPlayerOauth2, $request, &$response){
						$success=$libPlayerOauth2->issueToken($request, $response);
						return $success;
					});

					$this->_processLogin($libPlayerOauth2, $success, $response, $customized_error_code);

				}else if($additional=='refresh'){
					// oauth/token/refresh
					// refresh token
					$success=$this->dbtransOnly(function () use($libPlayerOauth2, $request, &$response){
						$success=$libPlayerOauth2->refreshToken($request, $response);
						return $success;
					});
				}
			}
		}else if($action=='tokens'){
			if($this->_isDeleteMethodRequest() && $additional=='self'){
				$this->utils->debug_log('delete token', $this->oauth_access_token_id, $this->username);
				// DELETE  oauth/tokens/self
				if($this->utils->getConfig('playerapi_sync_auth_token_to_all_currency') && $this->utils->isEnabledMDB()){
					$success=$this->player_oauth2_model->deleteAllTokenToOtherCurrency($this->oauth_access_token_id, $this->username);
				}else{
					$success=$this->dbtransOnly(function () use($libPlayerOauth2){
						$success=$libPlayerOauth2->deleteToken($this->oauth_access_token_id, $this->username);
						return $success;
					});
				}

				if(!$success){
					$response=$libPlayerOauth2->makeInternalErrorResponse('delete failed');
				}else{
					//return empty
					$this->load->library(['player_library']);
					$player = $this->player->getPlayerByLogin($this->username);
					$this->utils->debug_log('kick out player', $player->playerId);
					$this->player_library->kickPlayerGamePlatform($player->playerId, $this->username);
					$this->utils->debug_log('kick out player done', $player->playerId);
					return true;
				}
			}
        }

		if($success){
			$this->returnSuccessFromResponse($response);
		}else{
			if(!empty($customized_error_code)){
				$this->returnErrorWithCode($customized_error_code, $this->codes[$customized_error_code]);
			}elseif(!empty($response)){
				$this->returnErrorFromResponse($response);
			}else{
				$this->returnErrorWithCode(self::CODE_INVALID_PARAMETER);
			}
		}
	}

    protected function _oauthPhone2username(){
        // for return
        $_result = [];
        $_result['username'] = '';

        $validate_fields = [
            ['name' => 'password', 'type' => 'string', 'required' => true, 'length' => 0],
            ['name' => 'username', 'type' => 'string', 'required' => true, 'length' => 0], // its should be phone
        ];
        $request_body = $this->playerapi_lib->getRequestPramas();
        $this->comapi_log(__METHOD__, '=======request_body', $request_body);
        $is_validate_basic_passed = $this->playerapi_lib->validParmasBasic($request_body, $validate_fields);
        $this->comapi_log(__METHOD__, '=======is_validate_basic_passed', $is_validate_basic_passed);
        if(!$is_validate_basic_passed['validate_flag']) {
            $_result['code'] = self::CODE_INVALID_PARAMETER;
            $_result['errorMessage']= $is_validate_basic_passed['validate_msg'];
            return $_result;
        }

        $password = '';
        $contact_number = '';
        if(! empty($request_body['password'])) {
            $password = $request_body['password'];
        }
        if(! empty($request_body['username'])) {
            $contact_number = $request_body['username']; // its should be phone
        }
        /// search username useing contact number and password
        // convert to use the username and the password to login.
        $playerInfo_list = [];
        if(! empty($contact_number) && ! empty($password) ){
            $playerInfo_list = $this->player_model->getPlayersLoginInfoByNumberAndPassword($contact_number, $password);
        }

        if(!empty($playerInfo_list)){
            $duplicateCount = count($playerInfo_list);
            if( $duplicateCount > 1 ){
                $_result['code'] = self::CODE_LOGIN_FAILED_WITH_DUPLICATE_PHONE;
                $_result['errorMessage'] = $this->codes[self::CODE_LOGIN_FAILED_WITH_DUPLICATE_PHONE];
            }else{
                $_result['code'] = self::CODE_OK;
                $_result['errorMessage'] = '';
                // only one data
                $username = $playerInfo_list[0]['username'];
                $_result['username'] = $username;
            }
        }else{
            $_result['code'] = self::CODE_INVALID_CREDENTIALS;
            $_result['errorMessage'] = $this->codes[self::CODE_INVALID_CREDENTIALS];
        } // EOF if(!empty($playerInfo_list)){...

        return $_result;
    } // EOF _oauthPhone2username()

	protected function _processLogin($libPlayerOauth2, &$success, &$response, &$customized_error_code=null){
		if($success){
			if(method_exists($this, 'verifyLoginPriv')){
				list($username, $tokenId)=$libPlayerOauth2->decodeResponse($response);
				$result=$this->verifyLoginPriv($username);
				$success=$result['success'];
				$customized_error_code=$result['errorCode'];
			}
		}

		if($this->utils->getConfig('playerapi_sync_auth_token_to_all_currency') && $this->utils->isEnabledMDB()){
			if($success){
				list($username, $tokenId)=$libPlayerOauth2->decodeResponse($response);
				$success=$this->player_oauth2_model->syncTokenToOtherCurrency($tokenId, $username);
			}
		}

		if ($this->utils->getConfig('send_msg_to_remind_change_password')['enable']) {
			if($success){
				list($username, $tokenId) = $libPlayerOauth2->decodeResponse($response);
				$this->sendMsgToRemindChangePassword($username);
			}
		}

		if ($this->utils->getConfig('send_msg_to_remind_re_kyc')['enable']) {
			if($success){
				list($username, $tokenId) = $libPlayerOauth2->decodeResponse($response);
				$this->sendMsgToRemindReKyc($username);
			}
		}

		//description : Doesn't allow single player login in the different browser or device at the same time.
		if ($this->operatorglobalsettings->getSettingValue('single_player_session')) {
			if($success){
				list($username, $tokenId)=$libPlayerOauth2->decodeResponse($response);
				$success = $this->dbtransOnly(function () use($username, $tokenId){
					$success = $this->player_oauth2_model->retainCurrentToken($username, $tokenId);
					return $success;
				});
			}
			if($this->utils->getConfig('playerapi_sync_auth_token_to_all_currency') && $this->utils->isEnabledMDB()){
				if($success){
					$success=$this->player_oauth2_model->retainCurrentTokenToOtherCurrency($username, $tokenId);
				}
			}
		}

		if (!$success) {
			$this->load->model(array('operatorglobalsettings', 'player_model'));
			$request_body = $this->playerapi_lib->getRequestPramas();
			$username = !empty($request_body['username']) ? $request_body['username'] : null;
			$player_id = $this->player_model->getPlayerIdByUsername($username);

			if(!empty($player_id) && $this->operatorglobalsettings->getSettingBooleanValue('player_login_failed_attempt_blocked')){
				$max_login_attempt = (int)$this->operatorglobalsettings->getSettingIntValue('player_login_failed_attempt_times');
				$totalWrongLoginAttempt = (int)$this->player_model->getPlayerTotalWrongLoginAttempt($player_id);
				$updateAttempt = $totalWrongLoginAttempt + 1;

				if($updateAttempt >= $max_login_attempt){
					$this->player_model->updatePlayer($player_id, [
						'blocked' => Player_model::STATUS_BLOCKED_FAILED_LOGIN_ATTEMPT,
						'blocked_status_last_update' => $this->utils->getNowForMysql(),
						'failed_login_attempt_timeout_until' => $this->utils->getNowForMysql(),
					]);
				}
				$this->player_model->updatePlayerTotalWrongLoginAttempt($player_id, $updateAttempt);
			}
		}

		if( !empty($username) && method_exists($this, 'processLoginExtra')){
			$this->processLoginExtra($username);
		}
	}
}
