<?php

// /playerapi/sso/google
trait player_sso_utils_module {
    protected function handle_redirect_error($credential_setting = null, $errorCode = null, $errorMessage = null, $request_domain = null)
    {
        $redirect_uri = '/';
        $http_domain = $this->utils->getHttpHost();
        $_redirect_domain = $request_domain ?: $http_domain;
        $query_string = http_build_query(array( 'errorCode' => $errorCode, 'errorMessage' => $errorMessage, ), '', '&', PHP_QUERY_RFC3986);
        $message_endpoint = $this->utils->safeGetArray($credential_setting, 'message_endpoint', '/auth/message');
        $redirect_domain = $this->getRedirectDomain($_redirect_domain);
        $redirect_uri = $redirect_domain . $message_endpoint.'?'.$query_string;
        redirect($redirect_uri);
    }

    protected function handle_redirect_success($credential_setting = null, $oauth, $request_domain = null){
        $redirect_uri = '/';
        $http_domain = $this->utils->getHttpHost();
        $_redirect_domain = $request_domain ?: $http_domain;
        $refresh_token = $oauth['refresh_token'];
        $query_string = http_build_query(['refresh_token'=> $refresh_token], '', '&', PHP_QUERY_RFC3986);
        $success_endpoint = $this->utils->safeGetArray($credential_setting, 'success_endpoint', '/auth/token');
        $redirect_domain = $this->getRedirectDomain($_redirect_domain);
        $redirect_uri = $redirect_domain . $success_endpoint.'?'.$query_string;
        redirect($redirect_uri);
    }

    private function getRedirectDomain($redirect_domain){
        if($this->utils->notExistHttp($redirect_domain)){
            return ($this->utils->isHttps() ? 'https://' : 'http://') . $redirect_domain;
        } 
         return $redirect_domain;
    }
    protected function generateExtraInfo(&$extra_info = null){
        $extra_info['btag'] = $this->input->get('btag') ?: $this->utils->getBtagCookie();
        $extra_info['tracking_code'] = $this->input->get('tracking_code') ?: $this->getTrackingCode();
        $extra_info['tracking_source_code'] = $this->input->get('tracking_source_code') ?: $this->getTrackingSourceCode();
        $extra_info['agent_tracking_code'] = $this->input->get('agent_tracking_code') ?: $this->getAgentTrackingCode();
        $extra_info['agent_tracking_source_code'] = $this->input->get('agent_tracking_source_code') ?: $this->getAgentTrackingSourceCode();
        $extra_info['invitationCode'] = $this->input->get('referral_code') ?: $this->utils->getReferralCodeCookie();
        $extra_info['redirect_to'] = $this->input->get('redirect_to') ?: $this->utils->getHttpHost();
        $extra_info['currency'] = $this->input->get('currency');

        if (!empty($_SERVER['QUERY_STRING'])) {
            parse_str($_SERVER['QUERY_STRING'], $query_params);
            if (isset($query_params['tracking_code'])) {
                $extra_info['tracking_code'] = $query_params['tracking_code'];
            }
            if (isset($query_params['tracking_source_code'])) {
                $extra_info['tracking_source_code'] = $query_params['tracking_source_code'];
            }
            if (isset($query_params['agent_tracking_code'])) {
                $extra_info['agent_tracking_code'] = $query_params['agent_tracking_code'];
            }
            if (isset($query_params['agent_tracking_source_code'])) {
                $extra_info['agent_tracking_source_code'] = $query_params['agent_tracking_source_code'];
            }
            if (isset($query_params['referral_code'])) {
                $extra_info['invitationCode'] = $query_params['referral_code'];
            }
            if (isset($query_params['redirect_to'])) {
                $extra_info['redirect_to'] = $query_params['redirect_to'];
            }
            if (isset($query_params['currency'])) {
                $extra_info['currency'] = $query_params['currency'];
            }
        }
    }
    /**
     * generatePassword function
     *
     * @return string
     */
    protected function generatePassword(){
        $password_min_max_enabled = $this->utils->isPasswordMinMaxEnabled();
        $max_password_length = !empty($password_min_max_enabled['max']) ? $password_min_max_enabled['max'] : $this->utils->getConfig('default_max_size_password');
        return strtolower(random_string('alnum', $max_password_length - 1)) . random_string('numeric', 1);
    }

    /**
     * generateUsername function
     *
     * @return string
     */
    protected function generateUsername(){
        $max_username_length = $this->utils->getConfig('default_max_size_username');
        return strtolower(random_string('alnum', $max_username_length - 1)) . random_string('numeric', 1);
    }

    protected function ssoOauthToken($username, $password, $currCurrency, $login_from_player = true)
    {
		$errorResponse=null;
		/**
		 * @var LibPlayerOauth2 $libPlayerOauth2
		 */
		$libPlayerOauth2=$this->loadOauth2Lib($errorResponse);
		if(empty($libPlayerOauth2)){
			$this->returnErrorFromResponse($errorResponse);
			return;
		}

        $clientName = $this->utils->safeGetArray($this->credential, 'api_client_name', '');
        $clientSecret = $this->utils->safeGetArray($this->credential, 'api_client_secret', '');
        $basicAuth = 'Basic ' . base64_encode($clientName . ':' . $clientSecret);
		$isPost=$this->_isPostMethodRequest();
		$request=$libPlayerOauth2->generatePsr7Request();
        $request=$request->withParsedBody([
            'grant_type' => 'password',
            'username' => $username,
            'password' => $password,
            'currency' => $currCurrency,
        ])->withHeader('Authorization', $basicAuth)
        ;
		/**
		 * @var ResponseInterface $response
		 */
		$response=null;
		$success=false;
		$customized_error_code=null;

        // oauth/token
        // login
        $this->load->model(['player_oauth2_model']);
        $success=$this->dbtransOnly(function () use($libPlayerOauth2, $request, &$response){
            $success=$libPlayerOauth2->issueShortTermToken($request, $response);
            // $success=$libPlayerOauth2->issueToken($request, $response);
            return $success;
        });

        $this->_processLogin($libPlayerOauth2, $success, $response, $customized_error_code);

        if($success){
			// $this->returnSuccessFromResponse($response);
            return $response->getBody()->__toString();
		} else { 
            if(!empty($customized_error_code)){
				// $this->returnErrorWithCode($customized_error_code, $this->codes[$customized_error_code]);
                $_return = array(
                    'code' => $customized_error_code,
                    'errorMessage' => $this->codes[$customized_error_code],
                );
			}else if(!empty($response)){
				// $this->returnErrorFromResponse($response);
                $_return = array(
                    'code' => $response->getStatusCode(),
                    'errorMessage' => !empty($response->getReasonPhrase())?:'',
                );
			}else{
				// $this->returnErrorWithCode(self::CODE_INVALID_PARAMETER);
                $_return = array(
                    'code' => self::CODE_INVALID_PARAMETER,
                    'errorMessage' => $this->codes[self::CODE_INVALID_PARAMETER],
                );
			}
            return json_encode($_return);
        }
    }
}

