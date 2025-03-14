<?php

/**
 * Class player_auth_facebook_module
 *
 * General behaviors include :
 *
 * * Login player
 * * Logout player
 * * Register
 * * Check if exist (email, username, contact, referral )
 * * Check sms verification
 * *
 * @category Player Management
 * @version 1.8.10
 * @copyright 2013-2016 TripleOneTech
 *
 * @property Player_library $player_library
 */
trait player_auth_facebook_module {

    public function fb_login($extra_info = [], $pre_register_form = []){
        // return var_dump([
        //     'extra_info' => '',
        //     'pre_register_form' => '',
        // ]);

        if($this->authentication->isLoggedIn()){
            $this->goPlayerHome('home');
        }

        if(!$this->utils->getConfig('facebook_credential')){
            $this->goPlayerLogin();
        }

        if(!$this->checkBlockPlayerIPOnly()){
            return false;
        }

        $this->load->model('third_party_login');
        $this->CI->load->helper('string');
        $credential_setting = $this->utils->getConfig('facebook_credential');
        $uuid = uniqid('facebook_');
        $ip = $this->utils->getIP();
        $status = Third_party_login::THIRD_PARTY_LOGIN_STATUS_REQUEST;

        $extra_info['btag']                       = $this->input->get('btag')?: $this->utils->getBtagCookie();
        $extra_info['tracking_code']              = $this->input->get('tracking_code')?: $this->getTrackingCode();
        $extra_info['tracking_source_code']       = $this->input->get('tracking_source_code')?: $this->getTrackingSourceCode();
        $extra_info['agent_tracking_code']        = $this->input->get('agent_tracking_code')?: $this->getAgentTrackingCode();
        $extra_info['agent_tracking_source_code'] = $this->input->get('agent_tracking_source_code')?: $this->getAgentTrackingSourceCode();
		$extra_info['invitationCode']             = $this->input->get('referral_code')?: $this->utils->getReferralCodeCookie();

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
		}

        $this->utils->debug_log('=============fb_login extra_info', $extra_info);

        $this->third_party_login->insertThirdPartyLogin($uuid, $ip, $status, json_encode($extra_info), json_encode($pre_register_form));

		$redirect_uri = $credential_setting['redirect_uri'];
		$currDomain = $this->utils->getHttpHost();
		$redirect_uri = sprintf($redirect_uri, $currDomain);

        $login_query_params = [
            'response_type' => $credential_setting['response_type'],
            'client_id'     => $credential_setting['client_id'],
            'redirect_uri'  => $redirect_uri,
            'state'         => $uuid,
            'scope'         => $credential_setting['scope']
        ];

        $url = $credential_setting['auth_url'].'?'.http_build_query($login_query_params, '', '&', PHP_QUERY_RFC3986);
        $this->utils->debug_log('=============fb_login login_query_params', $login_query_params, $url );
        redirect($url);
    }

}
////END OF FILE/////////
