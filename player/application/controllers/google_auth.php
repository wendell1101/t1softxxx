<?php

require_once dirname(__FILE__) . '/iframe_module.php';

class Google_auth extends Iframe_module {

    private $uuid;
    private $credential;
    private $get_token_url = 'https://oauth2.googleapis.com/token';
    // private $debug_token_url = 'https://graph.facebook.com/debug_token';
    private $get_profile_url = 'https://www.googleapis.com/oauth2/v2/userinfo';
    const RETURN_SUCCESS_CODE = 'success';
    const CONFIG_KEY_CREDENTIAL = 'google_credential';

    function __construct()
    {
        parent::__construct();
        $this->load->model('third_party_login');
        $this->load->library(['player_library']);
        $this->credential = $this->utils->getConfig(self::CONFIG_KEY_CREDENTIAL);
    }

    public function index()
    {
        $enable_OGP19808 = $this->utils->getConfig('enable_OGP19808');
        if(!$this->credential){
            $this->goPlayerLogin();
        }

        $params = $this->getInputGetAndPost();
        $this->checkError($params);

        $thirdPartyLogin = $this->third_party_login->getThirdPartyLoginByUuid($params['state']);
        $this->checkError($thirdPartyLogin);
        $this->uuid = $thirdPartyLogin['uuid'];

        $data = [
            'status' => Third_party_login::THIRD_PARTY_LOGIN_STATUS_AUTH
        ];
        $this->third_party_login->updateThirdPartyLoginByUuid($this->uuid, $data);

        if(!empty($params['code'])) {

            $redirect_uri = $this->credential['redirect_uri'];
            $currDomain = $this->utils->getHttpHost();
            $redirect_uri = sprintf($redirect_uri, $currDomain);

            $get_token_params = [
                'code'          => $params['code'],
                'client_id'     => $this->credential['client_id'],
                'client_secret' => $this->credential['client_secret'],
                'redirect_uri'  => $redirect_uri,
                'grant_type'    => 'authorization_code',
                'access_type'   => 'offline',
            ];
            $this->utils->debug_log('========================Google_auth get_token_params : ', $get_token_params);
            $token = $this->processCurl($this->get_token_url, $get_token_params);

            $this->utils->debug_log('========================Google_auth get_token_url result : ', $token);

            // $debug_token_params = [
            //     'input_token' => $token['access_token'],
            //     'access_token'  => $token['access_token']
            // ];
            // $this->utils->debug_log('========================Google_auth debug_token_params : ', $debug_token_params);

            // $debug_token_result = $this->processCurl($this->debug_token_url, $debug_token_params);

            // $profile = $this->processCurl($debug_url, $get_profile_params, CURLOPT_HTTPGET);

            // $this->utils->debug_log('========================Google_auth debug_token_url result : ', $debug_token_result);

            $get_profile_params = [
                'access_token'  => $token['access_token']
            ];

            $this->utils->debug_log('========================Google_auth get_profile_params : ', $get_profile_params);

            $profile = $this->processCurl($this->get_profile_url, $get_profile_params);

            $this->utils->debug_log('========================Google_auth get_profile_url : ', $profile);

            $thirdparty_user_id = $profile['id'];
            $thirdparty_username = $profile['name'];
            $thirdparty_email = $profile['email'];

            $data = [
                'third_party_user_id' => $thirdparty_user_id,
                'status' => Third_party_login::THIRD_PARTY_LOGIN_STATUS_SUCCESS
            ];
            $this->third_party_login->updateThirdPartyLoginByUuid($this->uuid, $data);

            $thirdparty_player = $this->third_party_login->getGooglePlayersByUserId($thirdparty_user_id);

            if(!empty($thirdparty_player['player_id'])){ #login
                $data = [
                   'google_username' => $thirdparty_username,
                   'id_token' => $token['access_token'], // @todo id_token.id_token data type,"varchar(255)" too short and will ignore over-long string.
                ];
                $this->third_party_login->updateGooglePlayersByUserId($thirdparty_user_id, $data);

                $player = $this->player_model->getPlayerArrayById($thirdparty_player['player_id']);
                $result = $this->player_library->login_by_player_info($player);
                if($result['success']){
                    if($this->utils->getConfig('enable_stay_on_home_page')){
                        $url = $this->utils->getPlayerHomeUrl('www');
                    }else{
                        $url = $this->utils->getPlayerHomeUrl('home');
                    }

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

                    if($this->utils->getConfig('enable_fast_track_integration')) {
                        $this->load->library('fast_track');
                        $this->fast_track->login($thirdparty_player['player_id']);
                    }


                    redirect($url);
                } else {
                    $this->checkError($result);
                }
            } else { #register
                // for load $extra_info (in order to check player had filled basic info (username, password)
                $extra_info = json_decode($thirdPartyLogin['extra_info'], true);

                if( ! empty($thirdPartyLogin['pre_register_form']) ){
                    $pre_register_form = json_decode($thirdPartyLogin['pre_register_form'], true);
                    $extra_info = array_merge($extra_info, $pre_register_form);
                }

                if(empty($thirdparty_player)){
                    $isUsernameFilled = !empty($extra_info['username']);
                    if($this->utils->getConfig('force_google_player_to_register') && !$isUsernameFilled){
                        $go_register = $this->utils->getSystemUrl('player', '/player_center/iframe_register');
                        return redirect($go_register);
                    }

                    $data = [
                       'google_user_id'   => $thirdparty_user_id,
                       'google_username' => $thirdparty_username,
                       'id_token' => $token['access_token']
                    ];
                    $this->third_party_login->insertGooglePlayers($data);
                } else { #incase failed for last try
                    $data = [
                       'google_username' => $thirdparty_username,
                       'id_token' => $token['access_token'],
                    ];
                    $this->third_party_login->updateGooglePlayersByUserId($thirdparty_user_id, $data);
                }

                $password_min_max_enabled = $this->utils->isPasswordMinMaxEnabled();
                $max_username_length = $this->utils->getConfig('default_max_size_username');
                $max_password_length = !empty($password_min_max_enabled['max']) ? $password_min_max_enabled['max'] : $this->utils->getConfig('default_max_size_password');

                $player_data['username'] = strtolower(random_string('alnum', $max_username_length - 1)).random_string('numeric', 1);
                if(! empty($extra_info['username']) ){
                    $player_data['username'] = $extra_info['username'];
                }
                $player_data['password'] = strtolower(random_string('alnum', $max_password_length - 1)).random_string('numeric', 1);
                if( ! empty($extra_info['password']) ){
                    $player_data['password'] = $extra_info['password'];
                }

                if( ! empty($thirdparty_email) ){
                    $player_data['email'] = $thirdparty_email;
                }

                $player_data['thirdPartyLoginType'] = Third_party_login::THIRD_PARTY_LOGIN_TYPE_GOOGLE;
                $player_data['google_user_id'] = $thirdparty_user_id;

                $player_data['goto_url'] = ($this->operatorglobalsettings->getSettingJson('redirect_after_registration') == 2) ? site_url($this->utils->getSystemUrl('www')) : site_url($this->utils->getPlayerHomeUrl('home'));

                // because extra_info['password'] from $thirdPartyLogin['pre_register_form'].
                if( ! empty($extra_info['password']) ){ // for detect applied OGP-19860
                    // should be into Add Bank
                    if( ! empty( $this->utils->getConfig('gotoAddBankAfterRegister') ) ){
                        $url = $this->utils->getPlayerBankAccountUrl();
                        $url .= '#triggerAddBank';
                        $player_data['goto_url'] = $url;
                    }
                }else{
                    // will apply into OGP-19808
                    if( ! empty($enable_OGP19808) ){
                        $player_data['goto_url'] = site_url($this->utils->getPlayerProfileSetupUrl());
                    }
                }

//                moved to "load $extra_info".
//                $extra_info = json_decode($thirdPartyLogin['extra_info'], true);
//                if( ! empty($thirdPartyLogin['pre_register_form']) ){
//                    $pre_register_form = json_decode($thirdPartyLogin['pre_register_form'], true);
//                    $extra_info = array_merge($extra_info, $pre_register_form);
//                }

                $this->utils->debug_log('========================google_auth_data player_data', $player_data);
                $this->utils->debug_log('========================google_auth_data extra_info', $extra_info);

                $data = array_merge($player_data, $extra_info);
                $this->_process_register($data, $extra_info['tracking_code'], $extra_info['tracking_source_code'], $extra_info['agent_tracking_code'] , $extra_info['agent_tracking_source_code'], $extra_info['invitationCode'], $extra_info['btag']);

                list($html, $formId) = $this->createHtmlForm(site_url('iframe_module/postRegisterPlayer'), $data);
                $data = array('form_html' => $html, 'form_id' => $formId);
                $this->utils->debug_log('========================google_auth_data createHtmlForm data', $data);

                $this->load->view('player/redirect', $data);
            }
        }
    }

    private function checkError($params)
    {
        $this->utils->debug_log('========================google_auth_data checkError', $params);
        if(empty($params)){
            $this->utils->error_log('Google Validation Failed');
            $this->utils->flash_message(FLASH_MESSAGE_TYPE_DANGER, lang('Google Validation Failed'));
            redirect('/iframe/auth/login');

        } elseif(isset($params['error'])){
            if(!empty($this->uuid)){
                $error_note = json_encode($params);
                $data = [
                    'error_note' => $error_note,
                    'status' => Third_party_login::THIRD_PARTY_LOGIN_STATUS_FAILED
                ];
                $this->third_party_login->updateThirdPartyLoginByUuid($this->uuid, $data);
            }

            $this->utils->error_log('Google Validation Failed', $params);
            $this->utils->flash_message(FLASH_MESSAGE_TYPE_DANGER, lang('Google Validation Failed'));
            redirect('/iframe/auth/login');
        }
    }

    private function processCurl($url, $params)
    {
        try {
            $ch = curl_init();

            if (isset($params['access_token'])) {
                curl_setopt_array($ch, array(
                            CURLOPT_URL => $url.'?'.http_build_query($params),
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_ENCODING => '',
                            CURLOPT_MAXREDIRS => 10,
                            CURLOPT_TIMEOUT => 0,
                            CURLOPT_FOLLOWLOCATION => true,
                            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                            CURLOPT_CUSTOMREQUEST => 'GET',
                            CURLOPT_HTTPHEADER => ['Content-Type: application/json']
                            ));
                $content = curl_exec($ch);
            } else {
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['application/x-www-form-urlencoded']);
                curl_setopt($ch, CURLOPT_HEADER, true);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
                $response   = curl_exec($ch);
                $errCode     = curl_errno($ch);
                $error       = curl_error($ch);
                $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
                $statusCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $last_url    = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
                $header      = substr($response, 0, $header_size);
                $content     = substr($response, $header_size);
            }
            curl_close($ch);
            $result = json_decode($content, true);
            $this->checkError($result);

            return $result;
        } catch (Exception $e) {
            $this->utils->error_log('POST failed', $e);
        }
    }

    public function createHtmlForm($url, $params) {
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
