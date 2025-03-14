<?php

/**
 * player account sso function
 *
 * uri: 
 *
 * @property playerapi_lib $playerapi_lib
 * @property Playerapi_model $playerapi_model
 * @property Player_friend_referral $player_friend_referral
 * @property Third_party_login $third_party_login
 */
trait player_sso_module
{
    protected function sso($action)
    {
        $this->load->model(['player_model', 'third_party_login']);
        switch ($action) {
            case 'google':
                return $this->_google_login();
                break;
            case 'facebook':
                return $this->_facebook_login();
                break;
            case 'dummy':
                return $this->_dummy_login();
                break;
            default:
                // $this->returnErrorWithCode(Playerapi::CODE_GENERAL_CLIENT_ERROR);
                $this->handle_redirect_error(null, self::CODE_GENERAL_CLIENT_ERROR, lang('Invalid action'));
                break;
        }
    }
    /**
     * _dummy_login sso function
     *
     * @param array $extra_info
     * @return void
     * 
     * url: /playerapi/sso/dummy
     */
    protected function _dummy_login($extra_info = []){
        try{
            $credential_setting = $this->utils->getConfig('dummy_credential');
            if ( !$credential_setting) {
                throw new Exception('dummy_credential not found');
            }
            $this->load->model('third_party_login');
            $this->CI->load->helper('string');
            $uuid = uniqid('dummy_');
            $ip = $this->utils->getIP();
            $status = Third_party_login::THIRD_PARTY_LOGIN_STATUS_REQUEST;
            $this->generateExtraInfo($extra_info);
            $this->utils->debug_log('=============dummy_login extra_info', $extra_info);
    
            $this->third_party_login->insertThirdPartyLogin($uuid, $ip, $status, json_encode($extra_info), null);
            $redirect_uri = '/sso/dummy_auth2';
            $currDomain = $this->utils->getHttpHost();
            $redirect_uri = $this->utils->paddingHostHttp($redirect_uri, $currDomain);
    
            $login_query_params = [
                'state' => $uuid,
                'redirect_to' => $extra_info['redirect_to'],
            ];
            $url = $redirect_uri . '?' . http_build_query($login_query_params, '', '&', PHP_QUERY_RFC3986);
            $this->utils->debug_log('=============dummy_login login_query_params', $login_query_params, $url);
        }catch  (\Throwable $th){
            $this->utils->debug_log('=============dummy_login error', $th->getMessage());
            $this->handle_redirect_error($credential_setting, $th->getCode(), $th->getMessage());
            return;
        }

        redirect($url);
    }

    /**
     * _google_login sso function
     *
     * @param array $extra_info
     * @return void
     * 
     * url: /playerapi/sso/google
     */
    protected function _google_login($extra_info = [])
    {
        try {
            $credential_setting = $this->utils->getConfig('google_credential');
            if ( !$credential_setting) {
                throw new Exception('google_credential not found');
            }

            $this->load->model('third_party_login');
            $this->CI->load->helper('string');
            $uuid = uniqid('google_');
            $ip = $this->utils->getIP();
            $status = Third_party_login::THIRD_PARTY_LOGIN_STATUS_REQUEST;

            $this->generateExtraInfo($extra_info);
            $this->utils->debug_log('=============google_login extra_info', $extra_info);

            $this->third_party_login->insertThirdPartyLogin($uuid, $ip, $status, json_encode($extra_info), null);

            $redirect_uri = $credential_setting['playerapi_redirect_uri'];
            $currDomain = $this->utils->getHttpHost();
            $redirect_uri = $this->utils->paddingHostHttp($redirect_uri, $currDomain);

            $login_query_params = [
                'client_id' => $credential_setting['client_id'],
                'redirect_uri' => $redirect_uri,
                'response_type' => $credential_setting['response_type'],
                'state' => $uuid,
                'scope' => $credential_setting['scope'],
                'prompt' => $credential_setting['prompt'],
                'redirect_to' => $extra_info['redirect_to'],
            ];
            $url = $credential_setting['auth_url'] . '?' . http_build_query($login_query_params, '', '&', PHP_QUERY_RFC3986);
            $this->utils->debug_log('=============google_login login_query_params', $login_query_params, $url);
        } catch (\Throwable $th) {
            $this->utils->debug_log('=============google_login error', $th->getMessage());
            $this->handle_redirect_error($credential_setting, $th->getCode(), $th->getMessage());
            return;
        }
        
        redirect($url);
    }

    /**
     * _facebook_login sso function
     *
     * @param array $extra_info
     * @return void
     * 
     * url: /playerapi/sso/facebook
     */
    protected function _facebook_login($extra_info = []){
        try{
            $credential_setting = $this->utils->getConfig('facebook_credential');
            if ( !$credential_setting) {
                throw new Exception('facebook_credential not found');
            }
            $this->load->model('third_party_login');
            $this->CI->load->helper('string');
            $uuid = uniqid('facebook_');
            $ip = $this->utils->getIP();
            $status = Third_party_login::THIRD_PARTY_LOGIN_STATUS_REQUEST;
            $this->generateExtraInfo($extra_info);
            $this->utils->debug_log('=============facebook_login extra_info', $extra_info);
    
            $this->third_party_login->insertThirdPartyLogin($uuid, $ip, $status, json_encode($extra_info), null);
    
            $redirect_uri = $credential_setting['playerapi_redirect_uri'];
            $currDomain = $this->utils->getHttpHost();
            $redirect_uri = $this->utils->paddingHostHttp($redirect_uri, $currDomain);
    
            $login_query_params = [
                'client_id' => $credential_setting['client_id'],
                'redirect_uri' => $redirect_uri,
                'response_type' => $credential_setting['response_type'],
                'state' => $uuid,
                'scope' => $credential_setting['scope'],
                'redirect_to' => $extra_info['redirect_to'],
            ];
            $url = $credential_setting['auth_url'] . '?' . http_build_query($login_query_params, '', '&', PHP_QUERY_RFC3986);
            $this->utils->debug_log('=============facebook_login login_query_params', $login_query_params, $url);
        }catch  (\Throwable $th){
            $this->utils->debug_log('=============facebook_login error', $th->getMessage());
            $this->handle_redirect_error($credential_setting, $th->getCode(), $th->getMessage());
            return;
        }

        redirect($url);
    }
}