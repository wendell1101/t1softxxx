<?php

require_once dirname(__FILE__) . '/iframe_module.php';

class Facebook_auth extends Iframe_module
{
    private $uuid;
    private $credential;
    private $facebook_host = 'https://graph.facebook.com/';
    private $api_vers = 'v12.0';
    private $get_token_url = 'https://graph.facebook.com/v12.0/oauth/access_token';
    private $debug_token_url = 'https://graph.facebook.com/debug_token';
    private $get_profile_url = 'https://graph.facebook.com/me';
    private $profile_endpoint = 'me';
    private $access_token = null;
    const RETURN_SUCCESS_CODE = 'success';
    const CONFIG_KEY_CREDENTIAL = 'facebook_credential';

    public function __construct()
    {
        parent::__construct();
        $this->load->model('third_party_login');
        $this->load->library(['player_library']);
        $this->credential = $this->utils->getConfig(self::CONFIG_KEY_CREDENTIAL);
    }

    public function index($ot_token = null)
    {
        $enable_OGP19808 = $this->utils->getConfig('enable_OGP19808');
        if (!$this->credential) {
            $this->goPlayerLogin();
        }

        $params = $this->getInputGetAndPost();
        $this->checkError($params);
        $thirdPartyLogin = [];
        if (!$this->utils->safeGetArray($params, 'ot_token')) {
            $thirdPartyLogin = $this->third_party_login->getThirdPartyLoginByUuid($params['state']);
            $this->checkError($thirdPartyLogin);
            $this->uuid = $thirdPartyLogin['uuid'];

            $data = [
                'status' => Third_party_login::THIRD_PARTY_LOGIN_STATUS_AUTH
            ];
            $this->third_party_login->updateThirdPartyLoginByUuid($this->uuid, $data);

            if (!empty($params['code'])) {
                $redirect_uri = $this->credential['redirect_uri'];
                $currDomain = $this->utils->getHttpHost();
                $redirect_uri = sprintf($redirect_uri, $currDomain);

                $get_token_params = [
                    'client_id'     => $this->credential['client_id'],
                    'redirect_uri'  => $redirect_uri,
                    'client_secret' => $this->credential['client_secret'],
                    'code'          => $params['code'],
                    //'grant_type'    => 'client_credentials'
                ];
                $this->utils->debug_log('========================Facebook_auth get_token_params : ', $get_token_params);
                $token = $this->processCurl($this->get_token_url, $get_token_params);
                $this->access_token = $token['access_token'];
                $this->utils->debug_log('========================Facebook_auth get_token_url result : ', $token);

                // $debug_token_params = [
                //     'input_token' => $token['access_token'],
                //     'access_token'  => $token['access_token']
                // ];

                // $this->utils->debug_log('========================Facebook_auth debug_token_params : ', $debug_token_params);

                // $debug_token_result = $this->processCurl($this->debug_token_url, $debug_token_params);

                // $this->utils->debug_log('========================Facebook_auth debug_token_url result : ', $debug_token_result);


                // $exchange_token_params = [
                //     'grant_type'=> 'fb_exchange_token',
                //     'client_id'     => $this->credential['client_id'],
                //     'client_secret' => $this->credential['client_secret'],
                //     'fb_exchange_token' => $token['access_token'],
                // ];

                // $this->utils->debug_log('========================Facebook_auth exchange_token_params : ', $exchange_token_params);
                // $exchange_token_result = $this->processCurl($this->get_token_url, $exchange_token_params);

                // $this->utils->debug_log('========================Facebook_auth exchange_tokenn_url result : ', $exchange_token_result);


            }
        } else {
            // to do debug_token
            $uuid = uniqid('facebook_');
            $ip = $this->utils->getIP();
            $status = Third_party_login::THIRD_PARTY_LOGIN_STATUS_REQUEST;
            $extra_info = $pre_register_form = array();
            $extra_info['btag']                       = $this->input->get('btag') ?: $this->utils->getBtagCookie();
            $extra_info['tracking_code']              = $this->input->get('tracking_code') ?: $this->getTrackingCode();
            $extra_info['tracking_source_code']       = $this->input->get('tracking_source_code') ?: $this->getTrackingSourceCode();
            $extra_info['agent_tracking_code']        = $this->input->get('agent_tracking_code') ?: $this->getAgentTrackingCode();
            $extra_info['agent_tracking_source_code'] = $this->input->get('agent_tracking_source_code') ?: $this->getAgentTrackingSourceCode();
            $extra_info['invitationCode']             = $this->input->get('referral_code') ?: $this->utils->getReferralCodeCookie();
            $this->third_party_login->insertThirdPartyLogin($uuid, $ip, $status, json_encode($extra_info), json_encode($pre_register_form));
            $this->access_token = $this->utils->safeGetArray($params, 'ot_token');
            $thirdPartyLogin = $this->third_party_login->getThirdPartyLoginByUuid($uuid);
        }
        $get_profile_params = [
            'access_token'  => $this->access_token
        ];

        $this->utils->debug_log('========================Facebook_auth get_profile_params : ', $get_profile_params);

        $profile = $this->processCurl(sprintf($this->get_profile_url, $this->profile_endpoint), $get_profile_params);

        $this->utils->debug_log('========================Facebook_auth get_profile_url : ', $profile);

        $facebook_user_id = $profile['id'];
        $facebook_username = $profile['name'];

        $data = [
            'third_party_user_id' => $facebook_user_id,
            'status' => Third_party_login::THIRD_PARTY_LOGIN_STATUS_SUCCESS
        ];
        $this->third_party_login->updateThirdPartyLoginByUuid($this->uuid, $data);

        $facebook_player = $this->third_party_login->getFacebookPlayersByUserId($facebook_user_id);

        if (!empty($facebook_player['player_id'])) { #login
            $data = [
                'facebook_username' => $facebook_username,
                'id_token' => $this->access_token, // @todo id_token.id_token data type,"varchar(255)" too short and will ignore over-long string.
            ];
            $this->third_party_login->updateFacebookPlayersByUserId($facebook_user_id, $data);

            $player = $this->player_model->getPlayerArrayById($facebook_player['player_id']);
            $result = $this->player_library->login_by_player_info($player);
            if ($result['success']) {
                $url = $this->utils->getPlayerHomeUrl('home');

                // if( ! empty($enable_OGP19808) ){
                //     // OGP-19808 check  real name & SMS OTP
                //     $playerId = $player['playerId'];
                //     $result4fromLine = $this->player_model->check_playerDetail_from_line($playerId);
                //     if($result4fromLine['success'] === false ){
                //         $url = $this->utils->getPlayerHomeUrl('home');
                //     }else{
                //         $url = site_url($this->utils->getPlayerProfileSetupUrl());
                //     }
                // }

                if ($this->utils->getConfig('enable_fast_track_integration')) {
                    $this->load->library('fast_track');
                    $this->fast_track->login($facebook_player['player_id']);
                }
                
                redirect($url);
            } else {
                $this->checkError($result);
            }
        } else { #register
            // for load $extra_info (in order to check player had filled basic info (username, password)
            $extra_info = json_decode($thirdPartyLogin['extra_info'], true);

            if (!empty($thirdPartyLogin['pre_register_form'])) {
                $pre_register_form = json_decode($thirdPartyLogin['pre_register_form'], true);
                $extra_info = array_merge($extra_info, $pre_register_form);
            }

            if (empty($facebook_player)) {
                $isUsernameFilled = !empty($extra_info['username']);
                if ($this->utils->getConfig('force_facebook_player_to_register') && !$isUsernameFilled) {
                    $go_register = $this->utils->getSystemUrl('player', '/player_center/iframe_register');
                    return redirect($go_register);
                }

                $data = [
                    'facebook_user_id'   => $facebook_user_id,
                    'facebook_username' => $facebook_username,
                    'id_token' => $this->access_token
                ];
                $this->third_party_login->insertFacebookPlayers($data);
            } else { #incase failed for last try
                $data = [
                    'facebook_username' => $facebook_username,
                    'id_token' => $this->access_token,
                ];
                $this->third_party_login->updateFacebookPlayersByUserId($facebook_user_id, $data);
            }

            $password_min_max_enabled = $this->utils->isPasswordMinMaxEnabled();
            $max_username_length = $this->utils->getConfig('default_max_size_username');
            $max_password_length = !empty($password_min_max_enabled['max']) ? $password_min_max_enabled['max'] : $this->utils->getConfig('default_max_size_password');

            $player_data['username'] = strtolower(random_string('alnum', $max_username_length - 1)) . random_string('numeric', 1);
            if (!empty($extra_info['username'])) {
                $player_data['username'] = $extra_info['username'];
            }
            $player_data['password'] = strtolower(random_string('alnum', $max_password_length - 1)) . random_string('numeric', 1);
            if (!empty($extra_info['password'])) {
                $player_data['password'] = $extra_info['password'];
            }

            $player_data['thirdPartyLoginType'] = Third_party_login::THIRD_PARTY_LOGIN_TYPE_FACEBOOK;
            $player_data['facebook_user_id'] = $facebook_user_id;

            $player_data['goto_url'] = ($this->operatorglobalsettings->getSettingJson('redirect_after_registration') == 2) ? site_url($this->utils->getSystemUrl('www')) : site_url($this->utils->getPlayerHomeUrl('home'));

            // because extra_info['password'] from $thirdPartyLogin['pre_register_form'].
            if (!empty($extra_info['password'])) { // for detect applied OGP-19860
                // should be into Add Bank
                if (!empty($this->utils->getConfig('gotoAddBankAfterRegister'))) {
                    $url = $this->utils->getPlayerBankAccountUrl();
                    $url .= '#triggerAddBank';
                    $player_data['goto_url'] = $url;
                }
            } else {
                // will apply into OGP-19808
                if (!empty($enable_OGP19808)) {
                    $player_data['goto_url'] = site_url($this->utils->getPlayerProfileSetupUrl());
                }
            }

            //                moved to "load $extra_info".
            //                $extra_info = json_decode($thirdPartyLogin['extra_info'], true);
            //                if( ! empty($thirdPartyLogin['pre_register_form']) ){
            //                    $pre_register_form = json_decode($thirdPartyLogin['pre_register_form'], true);
            //                    $extra_info = array_merge($extra_info, $pre_register_form);
            //                }

            $this->utils->debug_log('========================facebook_auth_data player_data', $player_data);
            $this->utils->debug_log('========================facebook_auth_data extra_info', $extra_info);

            $data = array_merge($player_data, $extra_info);
            $this->_process_register($data, $extra_info['tracking_code'], $extra_info['tracking_source_code'], $extra_info['agent_tracking_code'], $extra_info['agent_tracking_source_code'], $extra_info['invitationCode'], $extra_info['btag']);

            list($html, $formId) = $this->createHtmlForm(site_url('iframe_module/postRegisterPlayer'), $data);
            $data = array('form_html' => $html, 'form_id' => $formId);
            $this->utils->debug_log('========================facebook_auth_data createHtmlForm data', $data);

            $this->load->view('player/redirect', $data);
        }
    }

    private function checkError($params)
    {
        if (empty($params)) {
            $this->utils->error_log('Facebook Validation Failed');
            $this->utils->flash_message(FLASH_MESSAGE_TYPE_DANGER, lang('Facebook Validation Failed'));
            redirect('/iframe/auth/login');
        } elseif (isset($params['error'])) {
            if (!empty($this->uuid)) {
                $error_note = json_encode($params);
                $data = [
                    'error_note' => $error_note,
                    'status' => Third_party_login::THIRD_PARTY_LOGIN_STATUS_FAILED
                ];
                $this->third_party_login->updateThirdPartyLoginByUuid($this->uuid, $data);
            }

            $this->utils->error_log('Facebook Validation Failed', $params);
            $this->utils->flash_message(FLASH_MESSAGE_TYPE_DANGER, lang('Facebook Validation Failed'));
            redirect('/iframe/auth/login');
        }
    }

    private function processCurl($url, $params, $type = CURLOPT_POST)
    {
        try {
            // $url = $url.'?'.http_build_query($params);
            $ch = curl_init();

            curl_setopt_array($ch, array(
                CURLOPT_URL => $url . '?' . http_build_query($params),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => ['Content-Type: application/json']
            ));
            $response = curl_exec($ch);


            curl_close($ch);
            $result = json_decode($response, true);
            $this->checkError($result);

            return $result;
        } catch (Exception $e) {
            $this->utils->error_log('POST failed', $e);
        }
    }

    public function createHtmlForm($url, $params)
    {
        $formId = 'f_' . random_string('unique');
        $html = '<form name="' . $formId . '" id="' . $formId . '" method="POST" action="' . $url . '">';
        if (is_array($params)) {
            foreach ($params as $name => $val) {
                $html = $html . "<input type=\"hidden\" name=\"" . $name . "\" value=\"" . htmlentities($val) . "\">\n";
            }
        }
        $html = $html . "<button type=\"hidden\" style=\"display:none\" id=\"submit_form_btn_" . $formId . "\">\n";
        $html = $html . '</form>';
        return array($html, $formId);
    }
}
