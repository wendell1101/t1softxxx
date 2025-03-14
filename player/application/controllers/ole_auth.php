<?php

require_once dirname(__FILE__) . '/iframe_module.php';

class Ole_auth extends Iframe_module {

    private $uuid;
    private $credential;
    private $ole_auth_player_tableName = 'ole_auth_player';
    const RETURN_SUCCESS_CODE = 'success';

    function __construct()
    {
        parent::__construct();
        $this->load->model('third_party_login');
        $this->load->library(['player_library']);
        $this->credential = $this->utils->getConfig('ole_credential');
    }

    public function index()
    {
        if(!$this->credential){
            $this->goPlayerLogin();
        }

        $params = $this->getInputGetAndPost();

        // $states = explode('|', $params['state']);
        // $uuid = $states[0];

        $uuid = uniqid('ole_auth_');
        $ip = $this->utils->getIP();
        $status = Third_party_login::THIRD_PARTY_LOGIN_STATUS_REQUEST;
        $pre_register_form = [];

        $extra_info['btag']                       = $this->input->get('btag')?: $this->utils->getBtagCookie();
        $extra_info['tracking_code']              = $this->input->get('tracking_code')?: $this->getTrackingCode();
        $extra_info['tracking_source_code']       = $this->input->get('tracking_source_code')?: $this->getTrackingSourceCode();
        $extra_info['agent_tracking_code']        = $this->input->get('agent_tracking_code')?: $this->getAgentTrackingCode();
        $extra_info['agent_tracking_source_code'] = $this->input->get('agent_tracking_source_code')?: $this->getAgentTrackingSourceCode();
		$extra_info['invitationCode']             = $this->input->get('referral_code')?: $this->utils->getReferralCodeCookie();

		if (!empty($_SERVER['QUERY_STRING'])) {
            $_SERVER['QUERY_STRING'] = str_replace('+', '%2B', $_SERVER['QUERY_STRING']);
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
            if (isset($query_params['access_token'])) {
                $params['access_token'] = $query_params['access_token'];
            }
		}
        $this->checkError($params);

        $this->third_party_login->insertThirdPartyLogin($uuid, $ip, $status, json_encode($extra_info), json_encode($pre_register_form));


        $thirdPartyLogin = $this->third_party_login->getThirdPartyLoginByUuid($uuid);
        $this->checkError($thirdPartyLogin);
        $this->uuid = $thirdPartyLogin['uuid'];

        $goto_url = isset($params['goto_url']) ? $params['goto_url'] : '';
        $post_data = [
            'client_id'    => $this->credential['client_id'],
            'secret_key'   => $this->credential['secret_key'],
            'access_token' => $params['access_token'],
        ];

        $check_access_token = $this->processCurl($this->credential['check_token_url'], $post_data);
        $access_token = $params['access_token'];
        if($check_access_token && $check_access_token['data']['status'] == true){
            $player_info = $this->processCurl($this->credential['get_user_info_url'], $post_data);
            $ole_username = $player_info['data']['user_id'];
            $ole_user_id = $player_info['data']['user_id'];
            
            $ole_player = $this->third_party_login->getPlayerByOleId($ole_user_id);
            $this->utils->debug_log('========================ole_auth_data ole_player', $ole_player);

            if(!empty($ole_player['player_id'])){
                $this->ole_login($ole_username, $ole_user_id, $access_token, $ole_player, $goto_url);
            }else{
                $this->ole_register($ole_username, $ole_user_id, $access_token, $ole_player, $extra_info, $goto_url);
            }
        }
        
    }

    private function checkError($params)
    {
        if(empty($params)){
            $this->utils->error_log('Ole Sso Validation Failed');
            $this->utils->flash_message(FLASH_MESSAGE_TYPE_DANGER, lang('Ole Sso Validation Failed'));
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

            $this->utils->error_log('Ole Sso Validation Failed', $params);
            $this->utils->flash_message(FLASH_MESSAGE_TYPE_DANGER, lang('Ole Sso Validation Failed'));
            redirect('/iframe/auth/login');
        }
    }

    private function processCurl($url, $params)
    {
        try {
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));

            $response = curl_exec($ch);
            $errCode     = curl_errno($ch);
            $error       = curl_error($ch);
            $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $statusCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $last_url    = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
            $header      = substr($response, 0, $header_size);
            $content     = substr($response, $header_size);
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

    private function ole_login($ole_username, $ole_user_id, $access_token, $ole_player, $goto_url){
        $this->utils->debug_log('========================ole_auth_data ole_login', $ole_player);

        $data = [
            'ole_username' => $ole_username,
            'access_token' => $access_token,
            'ole_user_id'  => $ole_user_id,
        ];
        $this->third_party_login->updatePlayersByPlayerId($this->ole_auth_player_tableName, $ole_user_id, $data);

        $player = $this->player_model->getPlayerArrayById($ole_player['player_id']);
        $result = $this->player_library->login_by_player_info($player);
        $this->utils->debug_log('========================ole_auth_data ole_login result', $result);

        if($result['success']){
            $url = $this->utils->getPlayerHomeUrl('home');

            if( ! empty($enable_OGP19808) ){
                // OGP-19808 check  real name & SMS OTP
                $playerId = $player['playerId'];
                $result4fromLine = $this->player_model->check_playerDetail_from_line($playerId);
                if($result4fromLine['success'] === false ){
                    $url = $this->utils->getPlayerHomeUrl('home');
                }else{
                    $url = site_url($this->utils->getPlayerProfileSetupUrl());
                }
            }

            if($this->utils->getConfig('enable_fast_track_integration')) {
                $this->load->library('fast_track');
                $this->fast_track->login($ole_player['player_id']);
            }
            
            if($goto_url != ""){
                $player_token = $this->common_token->getPlayerToken($player['playerId']);

                $ret = [
                    'code'      => 0 ,
                    'mesg'      => 'Player logged in',
                    'result'    => [
                        'playerName'    => $player['username'] ,
                        'playerId'      => $player['playerId'],
                        'token'         => $player_token
                    ]
                ];
                redirect($goto_url."?token=".$player_token.'&username='.$player['username']);
                // header('Content-Type: application/json');
                // echo json_encode($ret);
            }

            redirect($url);
        } else {
            $this->checkError($result);
        }
    }

    private function ole_register($ole_username, $ole_user_id, $access_token, $ole_player, $extra_info, $goto_url){
        $this->utils->debug_log('========================ole_auth_data ole_register', $ole_player);
        $data = [
            'ole_username' => $ole_username,
            'access_token' => $access_token,
            'ole_user_id'  => $ole_user_id,
        ];
        if(empty($ole_player)){
            $this->third_party_login->insertOlePlayers($this->ole_auth_player_tableName, $data);
        } else { #incase failed for last try        
            $this->third_party_login->updatePlayersByPlayerId($this->ole_auth_player_tableName, $ole_user_id, $data);
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

        $player_data['thirdPartyLoginType'] = Third_party_login::THIRD_PAETY_LOGIN_TYPE_OLE;

        $player_data['goto_url'] = ($this->operatorglobalsettings->getSettingJson('redirect_after_registration') == 2) ? site_url($this->utils->getSystemUrl('www')) : site_url($this->utils->getPlayerHomeUrl('home'));

        $player_data['access_token'] = $access_token;
        $player_data['ole_user_id']  = $ole_user_id;

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

        //    moved to "load $extra_info".
        //    $extra_info = json_decode($thirdPartyLogin['extra_info'], true);
        //    if( ! empty($thirdPartyLogin['pre_register_form']) ){
        //        $pre_register_form = json_decode($thirdPartyLogin['pre_register_form'], true);
        //        $extra_info = array_merge($extra_info, $pre_register_form);
        //    }

        if($goto_url != ""){
            $player_data['goto_url'] = $goto_url;
            $player_data['from_api'] = true;
        }

        $this->utils->debug_log('========================ole_auth_data player_data', $player_data);
        $this->utils->debug_log('========================ole_auth_data extra_info', $extra_info);

        $data = array_merge($player_data, $extra_info);
        $this->_process_register($data, $extra_info['tracking_code'], $extra_info['tracking_source_code'], $extra_info['agent_tracking_code'] , $extra_info['agent_tracking_source_code'], $extra_info['invitationCode'], $extra_info['btag']);
        $this->utils->debug_log('========================ole_auth_data  data', $data);

        list($html, $formId) = $this->createHtmlForm(site_url('iframe_module/postRegisterPlayer'), $data);
        $data = array('form_html' => $html, 'form_id' => $formId);
        $this->utils->debug_log('========================ole_auth_data createHtmlForm data', $data);

        $this->load->view('player/redirect', $data);
    }
}
